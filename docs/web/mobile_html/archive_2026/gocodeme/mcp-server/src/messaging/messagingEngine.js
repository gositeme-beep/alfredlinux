/**
 * messagingEngine.js — MESSAGING: Omnichannel Communication Engine
 *
 * Enables Alfred to communicate with customers across SMS, Email, and
 * eventually social channels. Unified message sending with templates,
 * tracking, and delivery confirmations.
 *
 * Channels:
 *  - SMS via Telnyx (already have infrastructure)
 *  - Email via SMTP (nodemailer)
 *  - Message templates with variable substitution
 *  - Delivery tracking and analytics
 *  - Campaign management (outbound sequences)
 */

import { randomUUID } from 'node:crypto';
import fs from 'node:fs/promises';
import path from 'node:path';
import nodemailer from 'nodemailer';

const MSG_BASE = '/home/gositeme/.gocodeme/messaging';

async function ensureDir(dir) { await fs.mkdir(dir, { recursive: true }); }
async function loadJSON(file, fallback = {}) {
  try { return JSON.parse(await fs.readFile(file, 'utf8')); } catch { return fallback; }
}
async function saveJSON(file, data) {
  await ensureDir(path.dirname(file));
  await fs.writeFile(file, JSON.stringify(data, null, 2));
}

function messagesPath(user)   { return path.join(MSG_BASE, user, 'messages.json'); }
function templatesPath(user)  { return path.join(MSG_BASE, user, 'templates.json'); }
function campaignsPath(user)  { return path.join(MSG_BASE, user, 'campaigns.json'); }
function contactsPath(user)   { return path.join(MSG_BASE, user, 'contacts.json'); }
function configPath(user)     { return path.join(MSG_BASE, user, 'config.json'); }

const now = () => new Date().toISOString();

// ════════════════════════════════════════════════════════════════════════════
// SECTION 1: CHANNEL CONFIGURATION
// ════════════════════════════════════════════════════════════════════════════

export async function configureChannel(user, channel, config) {
  const cfg = await loadJSON(configPath(user), { channels: {} });

  switch (channel) {
    case 'sms':
      cfg.channels.sms = {
        enabled: true,
        provider: 'telnyx',
        apiKey: config.apiKey || process.env.TELNYX_API_KEY || '',
        fromNumber: config.fromNumber || '',
        messagingProfileId: config.messagingProfileId || '',
        configuredAt: now(),
      };
      break;

    case 'email':
      cfg.channels.email = {
        enabled: true,
        provider: config.provider || 'smtp',
        host: config.host || 'localhost',
        port: config.port || 587,
        secure: config.secure !== false,
        user: config.user || '',
        pass: config.pass || '',
        fromName: config.fromName || 'Alfred',
        fromEmail: config.fromEmail || '',
        configuredAt: now(),
      };
      break;

    default:
      throw new Error(`Unknown channel "${channel}". Supported: sms, email`);
  }

  await saveJSON(configPath(user), cfg);
  return { message: `Channel "${channel}" configured successfully.` };
}

export async function listChannels(user) {
  const cfg = await loadJSON(configPath(user), { channels: {} });
  const channels = Object.entries(cfg.channels).map(([name, c]) => ({
    channel: name,
    enabled: c.enabled,
    provider: c.provider,
    configuredAt: c.configuredAt,
    ...(name === 'sms' ? { fromNumber: c.fromNumber } : {}),
    ...(name === 'email' ? { fromEmail: c.fromEmail, fromName: c.fromName } : {}),
  }));
  return { channels, message: channels.length ? `${channels.length} channel(s) configured.` : 'No channels configured yet.' };
}

// ════════════════════════════════════════════════════════════════════════════
// SECTION 2: MESSAGE SENDING
// ════════════════════════════════════════════════════════════════════════════

export async function sendSms(user, to, body, opts = {}) {
  const cfg = await loadJSON(configPath(user), { channels: {} });
  const sms = cfg.channels?.sms;
  if (!sms?.enabled) throw new Error('SMS channel not configured. Use messaging_configure_channel first.');

  const apiKey = sms.apiKey || process.env.TELNYX_API_KEY;
  if (!apiKey) throw new Error('Telnyx API key not configured');

  const payload = {
    from: sms.fromNumber,
    to,
    text: body,
    ...(sms.messagingProfileId ? { messaging_profile_id: sms.messagingProfileId } : {}),
  };

  const resp = await fetch('https://api.telnyx.com/v2/messages', {
    method: 'POST',
    headers: {
      Authorization: `Bearer ${apiKey}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(payload),
  });

  const result = await resp.json();

  // Log the message
  const msgId = `msg_${randomUUID().slice(0, 8)}`;
  const record = {
    id: msgId,
    channel: 'sms',
    direction: 'outbound',
    to,
    from: sms.fromNumber,
    body,
    status: resp.ok ? 'sent' : 'failed',
    providerMessageId: result.data?.id,
    error: resp.ok ? null : result.errors?.[0]?.detail,
    sentAt: now(),
    metadata: opts.metadata || {},
    campaignId: opts.campaignId || null,
  };

  const messages = await loadJSON(messagesPath(user), { messages: [] });
  messages.messages.push(record);
  if (messages.messages.length > 5000) messages.messages = messages.messages.slice(-5000);
  await saveJSON(messagesPath(user), messages);

  return {
    id: msgId,
    success: resp.ok,
    status: record.status,
    providerMessageId: record.providerMessageId,
    message: resp.ok ? `SMS sent to ${to}: "${body.substring(0, 60)}..."` : `SMS failed: ${record.error}`,
  };
}

export async function sendEmail(user, to, subject, body, opts = {}) {
  const cfg = await loadJSON(configPath(user), { channels: {} });
  const email = cfg.channels?.email;
  if (!email?.enabled) throw new Error('Email channel not configured. Use messaging_configure_channel first.');

  const transporter = nodemailer.createTransport({
    host: email.host,
    port: email.port,
    secure: email.secure,
    auth: email.user ? { user: email.user, pass: email.pass } : undefined,
  });

  const mailOpts = {
    from: `"${email.fromName}" <${email.fromEmail}>`,
    to,
    subject,
    ...(opts.html ? { html: body } : { text: body }),
    ...(opts.replyTo ? { replyTo: opts.replyTo } : {}),
    ...(opts.cc ? { cc: opts.cc } : {}),
    ...(opts.bcc ? { bcc: opts.bcc } : {}),
  };

  let info;
  try {
    info = await transporter.sendMail(mailOpts);
  } catch (err) {
    // Log failure
    const msgId = `msg_${randomUUID().slice(0, 8)}`;
    const messages = await loadJSON(messagesPath(user), { messages: [] });
    messages.messages.push({
      id: msgId, channel: 'email', direction: 'outbound', to, from: email.fromEmail,
      subject, body: body.substring(0, 500), status: 'failed', error: err.message,
      sentAt: now(), metadata: opts.metadata || {},
    });
    await saveJSON(messagesPath(user), messages);
    return { id: msgId, success: false, message: `Email failed: ${err.message}` };
  }

  const msgId = `msg_${randomUUID().slice(0, 8)}`;
  const record = {
    id: msgId, channel: 'email', direction: 'outbound', to, from: email.fromEmail,
    subject, body: body.substring(0, 500), status: 'sent',
    providerMessageId: info.messageId, sentAt: now(),
    metadata: opts.metadata || {}, campaignId: opts.campaignId || null,
  };

  const messages = await loadJSON(messagesPath(user), { messages: [] });
  messages.messages.push(record);
  if (messages.messages.length > 5000) messages.messages = messages.messages.slice(-5000);
  await saveJSON(messagesPath(user), messages);

  return {
    id: msgId, success: true, status: 'sent',
    providerMessageId: info.messageId,
    message: `Email sent to ${to}: "${subject}"`,
  };
}

// ════════════════════════════════════════════════════════════════════════════
// SECTION 3: MESSAGE TEMPLATES
// ════════════════════════════════════════════════════════════════════════════

const BUILT_IN_TEMPLATES = {
  order_confirmation: {
    name: 'Order Confirmation',
    channels: ['sms', 'email'],
    sms: 'Hi {{customer_name}}! Your order #{{order_number}} has been confirmed. Total: {{currency}}{{total}}. We\'ll send tracking when it ships!',
    email_subject: 'Order Confirmed - #{{order_number}}',
    email_body: '<h2>Order Confirmed!</h2><p>Hi {{customer_name}},</p><p>Your order <strong>#{{order_number}}</strong> has been confirmed.</p><p>Total: {{currency}}{{total}}</p><p>We\'ll notify you when it ships.</p>',
    variables: ['customer_name', 'order_number', 'total', 'currency'],
  },
  shipping_update: {
    name: 'Shipping Update',
    channels: ['sms', 'email'],
    sms: 'Your order #{{order_number}} has shipped! Tracking: {{tracking_number}} via {{carrier}}. Track: {{tracking_url}}',
    email_subject: 'Your Order Has Shipped - #{{order_number}}',
    email_body: '<h2>Your Order Has Shipped!</h2><p>Hi {{customer_name}},</p><p>Order <strong>#{{order_number}}</strong> is on its way.</p><p>Carrier: {{carrier}}<br>Tracking: <a href="{{tracking_url}}">{{tracking_number}}</a></p>',
    variables: ['customer_name', 'order_number', 'tracking_number', 'carrier', 'tracking_url'],
  },
  refund_processed: {
    name: 'Refund Processed',
    channels: ['sms', 'email'],
    sms: 'Your refund of {{currency}}{{amount}} for order #{{order_number}} has been processed. Allow 5-10 business days.',
    email_subject: 'Refund Processed - #{{order_number}}',
    email_body: '<h2>Refund Processed</h2><p>Hi {{customer_name}},</p><p>Your refund of <strong>{{currency}}{{amount}}</strong> for order #{{order_number}} has been processed.</p><p>Please allow 5-10 business days for the refund to appear on your statement.</p>',
    variables: ['customer_name', 'order_number', 'amount', 'currency'],
  },
  return_approved: {
    name: 'Return Approved',
    channels: ['sms', 'email'],
    sms: 'Your return for order #{{order_number}} has been approved. RMA: {{rma_id}}. Please ship items back within {{days}} days.',
    email_subject: 'Return Approved - RMA {{rma_id}}',
    email_body: '<h2>Return Approved</h2><p>Hi {{customer_name}},</p><p>Your return for order #{{order_number}} has been approved.</p><p>RMA Number: <strong>{{rma_id}}</strong></p><p>Please ship the items back within {{days}} days.</p>',
    variables: ['customer_name', 'order_number', 'rma_id', 'days'],
  },
  appointment_reminder: {
    name: 'Appointment Reminder',
    channels: ['sms', 'email'],
    sms: 'Reminder: Your appointment is on {{date}} at {{time}}. Reply YES to confirm or call us at {{phone}}.',
    email_subject: 'Appointment Reminder - {{date}}',
    email_body: '<h2>Appointment Reminder</h2><p>Hi {{customer_name}},</p><p>This is a reminder that your appointment is scheduled for:</p><p><strong>{{date}} at {{time}}</strong></p><p>Please reply to confirm or call us at {{phone}}.</p>',
    variables: ['customer_name', 'date', 'time', 'phone'],
  },
  cart_recovery: {
    name: 'Cart Recovery',
    channels: ['sms', 'email'],
    sms: 'Hey {{customer_name}}! You left items in your cart. Complete your purchase: {{cart_url}} Use code {{discount_code}} for {{discount}}% off!',
    email_subject: 'You Left Something Behind!',
    email_body: '<h2>Complete Your Purchase</h2><p>Hi {{customer_name}},</p><p>You left some items in your cart. Don\'t miss out!</p><p><a href="{{cart_url}}">Complete your purchase now</a></p><p>Use code <strong>{{discount_code}}</strong> for {{discount}}% off!</p>',
    variables: ['customer_name', 'cart_url', 'discount_code', 'discount'],
  },
  payment_reminder: {
    name: 'Payment Reminder',
    channels: ['sms', 'email'],
    sms: 'Hi {{customer_name}}, a payment of {{currency}}{{amount}} is due on {{due_date}}. Pay now: {{payment_url}}',
    email_subject: 'Payment Reminder - {{currency}}{{amount}} Due',
    email_body: '<h2>Payment Reminder</h2><p>Hi {{customer_name}},</p><p>A payment of <strong>{{currency}}{{amount}}</strong> is due on <strong>{{due_date}}</strong>.</p><p><a href="{{payment_url}}">Pay Now</a></p>',
    variables: ['customer_name', 'amount', 'currency', 'due_date', 'payment_url'],
  },
};

function renderTemplate(templateStr, variables) {
  let result = templateStr;
  for (const [key, value] of Object.entries(variables)) {
    result = result.replace(new RegExp(`\\{\\{${key}\\}\\}`, 'g'), value || '');
  }
  return result;
}

export async function createTemplate(user, name, template) {
  const templates = await loadJSON(templatesPath(user), { templates: {} });
  const id = `tpl_${randomUUID().slice(0, 8)}`;
  templates.templates[id] = {
    id, name,
    channels: template.channels || ['sms', 'email'],
    sms: template.sms || '',
    email_subject: template.email_subject || '',
    email_body: template.email_body || '',
    variables: template.variables || [],
    createdAt: now(),
    usageCount: 0,
  };
  await saveJSON(templatesPath(user), templates);
  return { id, message: `Template "${name}" created. ID: ${id}` };
}

export async function listTemplates(user) {
  const userTemplates = await loadJSON(templatesPath(user), { templates: {} });
  const all = [
    ...Object.entries(BUILT_IN_TEMPLATES).map(([key, t]) => ({
      id: key, name: t.name, channels: t.channels, variables: t.variables, builtIn: true,
    })),
    ...Object.values(userTemplates.templates).map(t => ({
      id: t.id, name: t.name, channels: t.channels, variables: t.variables, builtIn: false, usageCount: t.usageCount,
    })),
  ];
  return { templates: all, message: `${all.length} template(s) available (${Object.keys(BUILT_IN_TEMPLATES).length} built-in).` };
}

export async function sendTemplatedMessage(user, templateId, channel, to, variables, opts = {}) {
  // Find template
  let template = BUILT_IN_TEMPLATES[templateId];
  if (!template) {
    const userTemplates = await loadJSON(templatesPath(user), { templates: {} });
    template = userTemplates.templates[templateId];
  }
  if (!template) throw new Error(`Template "${templateId}" not found. Use messaging_list_templates to see available templates.`);

  let result;
  switch (channel) {
    case 'sms': {
      const body = renderTemplate(template.sms, variables);
      result = await sendSms(user, to, body, opts);
      break;
    }
    case 'email': {
      const subject = renderTemplate(template.email_subject, variables);
      const body = renderTemplate(template.email_body, variables);
      result = await sendEmail(user, to, subject, body, { ...opts, html: true });
      break;
    }
    default:
      throw new Error(`Unknown channel "${channel}". Supported: sms, email`);
  }

  // Track template usage
  const userTemplates = await loadJSON(templatesPath(user), { templates: {} });
  if (userTemplates.templates[templateId]) {
    userTemplates.templates[templateId].usageCount = (userTemplates.templates[templateId].usageCount || 0) + 1;
    await saveJSON(templatesPath(user), userTemplates);
  }

  return result;
}

// ════════════════════════════════════════════════════════════════════════════
// SECTION 4: CAMPAIGNS — Outbound Sequences
// ════════════════════════════════════════════════════════════════════════════

export async function createCampaign(user, config) {
  const campaigns = await loadJSON(campaignsPath(user), { campaigns: {} });
  const id = `camp_${randomUUID().slice(0, 8)}`;
  campaigns.campaigns[id] = {
    id,
    name: config.name,
    channel: config.channel || 'sms',
    templateId: config.templateId,
    recipients: config.recipients || [],
    variables: config.variables || {},
    status: 'draft',
    createdAt: now(),
    scheduledAt: config.scheduledAt || null,
    stats: { sent: 0, delivered: 0, failed: 0, opened: 0, clicked: 0 },
  };
  await saveJSON(campaignsPath(user), campaigns);
  return { id, message: `Campaign "${config.name}" created. ID: ${id}. Status: draft. Add recipients and execute when ready.` };
}

export async function executeCampaign(user, campaignId) {
  const campaigns = await loadJSON(campaignsPath(user), { campaigns: {} });
  const campaign = campaigns.campaigns[campaignId];
  if (!campaign) throw new Error(`Campaign ${campaignId} not found`);

  campaign.status = 'sending';
  await saveJSON(campaignsPath(user), campaigns);

  const results = [];
  for (const recipient of campaign.recipients) {
    try {
      const vars = { ...campaign.variables, ...(recipient.variables || {}) };
      const result = await sendTemplatedMessage(user, campaign.templateId, campaign.channel, recipient.to, vars, {
        campaignId, metadata: { campaignName: campaign.name },
      });
      results.push({ to: recipient.to, success: result.success, id: result.id });
      if (result.success) campaign.stats.sent++;
      else campaign.stats.failed++;
    } catch (err) {
      results.push({ to: recipient.to, success: false, error: err.message });
      campaign.stats.failed++;
    }
  }

  campaign.status = 'completed';
  campaign.completedAt = now();
  await saveJSON(campaignsPath(user), campaigns);

  return {
    campaignId,
    results,
    stats: campaign.stats,
    message: `Campaign "${campaign.name}" completed. Sent: ${campaign.stats.sent}, Failed: ${campaign.stats.failed} out of ${campaign.recipients.length} recipients.`,
  };
}

export async function listCampaigns(user) {
  const campaigns = await loadJSON(campaignsPath(user), { campaigns: {} });
  const list = Object.values(campaigns.campaigns).map(c => ({
    id: c.id, name: c.name, channel: c.channel, templateId: c.templateId,
    status: c.status, recipientCount: c.recipients.length,
    stats: c.stats, createdAt: c.createdAt,
  }));
  return { campaigns: list, message: list.length ? `${list.length} campaign(s).` : 'No campaigns yet.' };
}

// ════════════════════════════════════════════════════════════════════════════
// SECTION 5: CONTACTS
// ════════════════════════════════════════════════════════════════════════════

export async function addContact(user, contact) {
  const contacts = await loadJSON(contactsPath(user), { contacts: {} });
  const id = `con_${randomUUID().slice(0, 8)}`;
  contacts.contacts[id] = {
    id,
    name: contact.name,
    email: contact.email || '',
    phone: contact.phone || '',
    tags: contact.tags || [],
    notes: contact.notes || '',
    source: contact.source || 'manual',
    createdAt: now(),
    lastContacted: null,
    messageCount: 0,
    metadata: contact.metadata || {},
  };
  await saveJSON(contactsPath(user), contacts);
  return { id, message: `Contact "${contact.name}" added. ID: ${id}` };
}

export async function listContacts(user, tag) {
  const contacts = await loadJSON(contactsPath(user), { contacts: {} });
  let list = Object.values(contacts.contacts);
  if (tag) list = list.filter(c => c.tags.includes(tag));
  return {
    contacts: list.map(c => ({
      id: c.id, name: c.name, email: c.email, phone: c.phone,
      tags: c.tags, lastContacted: c.lastContacted, messageCount: c.messageCount,
    })),
    message: `${list.length} contact(s)${tag ? ` with tag "${tag}"` : ''}.`,
  };
}

export async function searchContacts(user, query) {
  const contacts = await loadJSON(contactsPath(user), { contacts: {} });
  const q = query.toLowerCase();
  const results = Object.values(contacts.contacts).filter(c =>
    c.name?.toLowerCase().includes(q) ||
    c.email?.toLowerCase().includes(q) ||
    c.phone?.includes(q) ||
    c.tags?.some(t => t.toLowerCase().includes(q))
  );
  return { results, message: `${results.length} contact(s) matching "${query}".` };
}

// ════════════════════════════════════════════════════════════════════════════
// SECTION 6: MESSAGE HISTORY & ANALYTICS
// ════════════════════════════════════════════════════════════════════════════

export async function getMessageHistory(user, filters = {}) {
  const messages = await loadJSON(messagesPath(user), { messages: [] });
  let list = messages.messages;
  if (filters.channel) list = list.filter(m => m.channel === filters.channel);
  if (filters.to) list = list.filter(m => m.to === filters.to);
  if (filters.status) list = list.filter(m => m.status === filters.status);
  if (filters.since) list = list.filter(m => new Date(m.sentAt) >= new Date(filters.since));
  if (filters.campaignId) list = list.filter(m => m.campaignId === filters.campaignId);

  const limit = filters.limit || 50;
  list = list.slice(-limit);

  return {
    messages: list,
    total: messages.messages.length,
    message: `${list.length} message(s) returned (${messages.messages.length} total).`,
  };
}

export async function getMessagingAnalytics(user) {
  const messages = await loadJSON(messagesPath(user), { messages: [] });
  const stats = {
    total: messages.messages.length,
    byChannel: {},
    byStatus: {},
    byDirection: {},
    last24h: 0,
    last7d: 0,
  };

  const now24h = Date.now() - 86400000;
  const now7d = Date.now() - 86400000 * 7;

  for (const m of messages.messages) {
    stats.byChannel[m.channel] = (stats.byChannel[m.channel] || 0) + 1;
    stats.byStatus[m.status] = (stats.byStatus[m.status] || 0) + 1;
    stats.byDirection[m.direction] = (stats.byDirection[m.direction] || 0) + 1;
    const ts = new Date(m.sentAt).getTime();
    if (ts > now24h) stats.last24h++;
    if (ts > now7d) stats.last7d++;
  }

  return { stats, message: `Messaging stats: ${stats.total} total, last 24h: ${stats.last24h}, last 7d: ${stats.last7d}.` };
}
