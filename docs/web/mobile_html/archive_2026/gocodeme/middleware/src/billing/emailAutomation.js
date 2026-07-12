'use strict';

/**
 * billing/emailAutomation.js — Email Automation Engine
 *
 * Automated email campaigns:
 *   1. Welcome series (on signup) — 3-email drip: welcome → tips → upgrade nudge
 *   2. Token-low alerts (50%/80%/100%) — email versions of push alerts
 *   3. Winback (7+ days inactive) — re-engagement email
 *   4. Upgrade suggestions (high usage on lower plans)
 *
 * Uses WHMCS SendEmail API for delivery (built-in templates, DKIM, etc.)
 * Redis keys for dedup / scheduling:
 *   email:welcome:<clientId>:step:N   → "1" (sent flag, 90-day TTL)
 *   email:winback:<clientId>          → timestamp of last winback email (30-day TTL)
 *   email:token:<clientId>:N          → "1" (N = 50|80|100, 35-day TTL)
 *   email:upgrade:<clientId>          → timestamp of last upgrade email (14-day TTL)
 */

const logger = require('../logger');
const { getRedis } = require('../redis');
const { callWhmcs, getClient } = require('./whmcs');
const scanKeys = require('../utils/scanKeys');

const TTL_35_DAYS = 35 * 24 * 60 * 60;
const TTL_90_DAYS = 90 * 24 * 60 * 60;
const TTL_30_DAYS = 30 * 24 * 60 * 60;
const TTL_14_DAYS = 14 * 24 * 60 * 60;

const UPGRADE_URL = process.env.AI_UPGRADE_URL || 'https://gositeme.com/pricing.php';
const TOPUP_URL = process.env.AI_TOPUP_URL || 'https://gositeme.com/pricing.php#topup';
const PORTAL_URL = process.env.AI_PORTAL_URL || 'https://gocodeme.com/middleware/dashboard';
const SUPPORT_URL = process.env.AI_SUPPORT_URL || 'https://gositeme.com/contact.php';
const TEAM_INVITE_BASE_URL = process.env.TEAM_INVITE_BASE_URL || 'https://gocodeme.com/middleware/dashboard?teamInvite=';

// ── Email Templates ─────────────────────────────────────────────────────────
// These use WHMCS SendEmail with 'customtype' = 'general'.
// Template content is inline — no need to create WHMCS email templates.

const TEMPLATES = {
  welcome_1: {
    subject: 'Welcome to GoCodeMe — Your AI-Powered IDE is Ready! 🚀',
    body: `Hi {firstname},

Welcome to GoCodeMe! Your AI development environment is live and ready.

Here's how to get started:
• Open your IDE: ${PORTAL_URL}
• Start coding — Claude AI is built right into your editor
• Use the AI terminal for command-line tasks

Your plan includes {tokenlimit} AI tokens per month. That's plenty to get started!

Quick tip: Press Ctrl+L in the editor to chat with Claude directly.

Happy coding!
— The GoCodeMe Team`,
  },

  welcome_2: {
    subject: 'GoCodeMe Pro Tips — Get More from Your AI IDE',
    delay: 2 * 24 * 60 * 60 * 1000, // 2 days after signup
    body: `Hi {firstname},

Here are some power-user tips for GoCodeMe:

🔧 Multi-file editing — Claude can edit multiple files in one conversation
📦 Git integration — commit, push, pull right from the IDE
🖥️ AI Terminal — run commands with AI-powered suggestions
📊 Usage dashboard — track your token usage at /middleware/usage

Pro tip: For complex tasks, describe the full goal upfront. Claude works best with clear, detailed instructions.

Need help? Our support team is here: ${SUPPORT_URL}

— The GoCodeMe Team`,
  },

  welcome_3: {
    subject: 'Unlock More Power — Upgrade Your GoCodeMe Plan',
    delay: 5 * 24 * 60 * 60 * 1000, // 5 days after signup
    body: `Hi {firstname},

You've been using GoCodeMe for a few days now — nice! 🎉

If you're finding the AI helpful, consider upgrading for:
• More monthly tokens (up to 5M on Enterprise)
• Faster response times with priority routing
• Overage protection so you never get cut off

Plans start at just $15/month: ${UPGRADE_URL}

Save 20% with annual billing!

— The GoCodeMe Team`,
  },

  token_50: {
    subject: 'GoCodeMe: Halfway Through Your Monthly Tokens',
    body: `Hi {firstname},

Quick heads up — you've used {percent}% of your monthly AI tokens ({used} / {limit}).

You're right on track. No action needed yet, but here are your options if you need more:

• Buy a token pack: ${TOPUP_URL}
• Upgrade your plan: ${UPGRADE_URL}

Keep coding! 💪
— The GoCodeMe Team`,
  },

  token_80: {
    subject: '⚠️ GoCodeMe: 80% of Your Monthly Tokens Used',
    body: `Hi {firstname},

You've used {percent}% of your monthly AI tokens ({used} / {limit}).

To avoid interruption, consider:
• Top up with a token pack - instant delivery: ${TOPUP_URL}
• Upgrade to get more monthly tokens: ${UPGRADE_URL}

On paid plans, you can keep coding past your limit — overage is billed at $2/100K tokens.

— The GoCodeMe Team`,
  },

  token_100: {
    subject: '🚫 GoCodeMe: Monthly Token Limit Reached',
    body: `Hi {firstname},

You've used all {limit} of your monthly AI tokens.

{overageinfo}

To continue coding with AI:
• Buy a token pack: ${TOPUP_URL}
• Upgrade your plan for more monthly tokens: ${UPGRADE_URL}

Your tokens reset on the 1st of each month.

— The GoCodeMe Team`,
  },

  winback: {
    subject: 'We miss you at GoCodeMe! Come back and code 💻',
    body: `Hi {firstname},

It's been a while since you've used GoCodeMe. We've been busy making things better:

• Smarter AI — auto-routing picks the best model for each task
• Faster responses — optimized caching and routing
• New models — access to Claude Sonnet 4, Opus, and more

Your workspace is still here, waiting for you: ${PORTAL_URL}

{special_offer}

See you soon!
— The GoCodeMe Team`,
  },

  upgrade_suggestion: {
    subject: 'GoCodeMe: You might need a bigger plan 📈',
    body: `Hi {firstname},

  You've been a power user this month - {percent}% usage on your {plan} plan.

  The {nextplan} plan would give you {nexttokens} tokens/month for just \${nextprice}/mo.

  Save even more with annual billing: just \${annualprice}/mo.

  Upgrade now: ${UPGRADE_URL}

  - The GoCodeMe Team`,
  },

  referral_welcome: {
    subject: '🎁 You\'ve been invited to GoCodeMe!',
    body: `Hi {firstname},

{referrer_name} thinks you'd love GoCodeMe — the AI-powered coding IDE.

As a referred user, you get 100,000 bonus AI tokens on top of your plan!

Get started: ${UPGRADE_URL}

Your bonus tokens will be applied automatically when you sign up.

Happy coding!
— The GoCodeMe Team`,
  },

  referral_reward: {
    subject: '🎉 Referral Reward: 100K Bonus Tokens Added!',
    body: `Hi {firstname},

Great news — someone you referred just signed up for GoCodeMe!

We've added 100,000 bonus tokens to your account as a thank-you. 🎁

Your updated token balance is available in your usage dashboard.

Keep sharing your referral link to earn more rewards!

— The GoCodeMe Team`,
  },

  team_invite: {
    subject: 'You\'ve been invited to a GoCodeMe team!',
    body: `Hi,

You've been invited to join the "{team_name}" team on GoCodeMe.

Click here to accept: ${TEAM_INVITE_BASE_URL}{invite_code}

GoCodeMe is an AI-powered coding IDE with Claude built right in.

— The GoCodeMe Team`,
  },
};

// ── Send Email via WHMCS ────────────────────────────────────────────────────

/**
 * Send a custom email to a WHMCS client.
 *
 * @param {object} opts
 * @param {string|number} opts.clientId     WHMCS client ID
 * @param {string}        opts.subject      Email subject
 * @param {string}        opts.body         Email body (plain text or simple HTML)
 * @param {string}        [opts.email]      Override recipient email (for non-clients)
 * @returns {Promise<boolean>} true if sent successfully
 */
async function sendEmail({ clientId, subject, body, email }) {
  try {
    const params = {
      messagename: 'Custom Message',
      id: clientId,
      customtype:  'general',
      customsubject: subject,
      custommessage: body.replace(/\n/g, '<br>'),
    };
    if (email) params.email = email;

    await callWhmcs('SendEmail', params);
    logger.info(`email: sent "${subject}" to client ${clientId}`);
    return true;
  } catch (err) {
    logger.error(`email: failed to send "${subject}" to client ${clientId}: ${err.message}`);
    return false;
  }
}

// ── Welcome Series ──────────────────────────────────────────────────────────

/**
 * Trigger welcome email series for a new client.
 * Call this from the WHMCS provisioning webhook (on first purchase).
 *
 * Step 1: immediate, Step 2: +2 days, Step 3: +5 days.
 * Scheduling is done via Redis keys checked by the email reaper (cron).
 *
 * @param {string|number} clientId
 * @param {string} firstname  Client's first name
 * @param {number} tokenLimit Plan token limit
 */
async function startWelcomeSeries(clientId, firstname, tokenLimit) {
  const redis = getRedis();
  const key = (step) => `email:welcome:${clientId}:step:${step}`;

  // Only start once
  const already = await redis.get(key(1));
  if (already) return;

  // Step 1 — send immediately
  const tpl = TEMPLATES.welcome_1;
  const body = tpl.body
    .replace(/{firstname}/g, firstname || 'there')
    .replace(/{tokenlimit}/g, (tokenLimit || 50000).toLocaleString());

  await sendEmail({ clientId, subject: tpl.subject, body });
  await redis.setex(key(1), TTL_90_DAYS, '1');

  // Schedule steps 2 & 3 by storing the send-after timestamp
  const now = Date.now();
  await redis.setex(`email:welcome:${clientId}:schedule:2`, TTL_90_DAYS, String(now + TEMPLATES.welcome_2.delay));
  await redis.setex(`email:welcome:${clientId}:schedule:3`, TTL_90_DAYS, String(now + TEMPLATES.welcome_3.delay));

  logger.info(`email: welcome series started for client ${clientId}`);
}

/**
 * Process scheduled welcome emails.
 * Called by the email cron reaper (every 30 min).
 */
async function processWelcomeSchedules() {
  const redis = getRedis();
  const now = Date.now();

  // Find all pending welcome schedules
  const keys = await scanKeys(redis, 'email:welcome:*:schedule:*');
  for (const key of keys) {
    const sendAfter = parseInt(await redis.get(key) || '0', 10);
    if (sendAfter && now >= sendAfter) {
      // Parse: email:welcome:<clientId>:schedule:<step>
      const parts = key.split(':');
      const clientId = parts[2];
      const step = parseInt(parts[4], 10);
      const sentKey = `email:welcome:${clientId}:step:${step}`;

      // Check not already sent
      const already = await redis.get(sentKey);
      if (already) {
        await redis.del(key);
        continue;
      }

      // Get client info
      try {
        const client = await getClient(clientId);
        const firstname = client.firstname || 'there';
        const tokenLimit = parseInt(await redis.get(`tokens:limit:${clientId}`) || '50000', 10);

        const tplKey = `welcome_${step}`;
        const tpl = TEMPLATES[tplKey];
        if (!tpl) { await redis.del(key); continue; }

        const body = tpl.body
          .replace(/{firstname}/g, firstname)
          .replace(/{tokenlimit}/g, tokenLimit.toLocaleString());

        await sendEmail({ clientId, subject: tpl.subject, body });
        await redis.setex(sentKey, TTL_90_DAYS, '1');
        await redis.del(key);
        logger.info(`email: welcome step ${step} sent to client ${clientId}`);
      } catch (err) {
        logger.error(`email: welcome step ${step} failed for client ${clientId}: ${err.message}`);
      }
    }
  }
}

// ── Token Alert Emails ──────────────────────────────────────────────────────

/**
 * Send a token usage alert email.
 * Called from alerts.js alongside the OpenClaw push notification.
 *
 * @param {object} opts
 * @param {string|number} opts.whmcsClientId
 * @param {number}        opts.percentUsed  — 50, 80, or 100
 * @param {number}        opts.used
 * @param {number}        opts.limit
 * @param {boolean}       [opts.isFree]   — true if free plan (different messaging at 100%)
 */
async function sendTokenAlertEmail({ whmcsClientId, percentUsed, used, limit, isFree }) {
  const redis = getRedis();
  const threshold = percentUsed >= 100 ? 100 : percentUsed >= 80 ? 80 : 50;
  const key = `email:token:${whmcsClientId}:${threshold}`;

  const already = await redis.get(key);
  if (already) return;

  try {
    const client = await getClient(whmcsClientId);
    const firstname = client.firstname || 'there';

    const tplKey = `token_${threshold}`;
    const tpl = TEMPLATES[tplKey];
    if (!tpl) return;

    let body = tpl.body
      .replace(/{firstname}/g, firstname)
      .replace(/{percent}/g, String(threshold))
      .replace(/{used}/g, used.toLocaleString())
      .replace(/{limit}/g, limit.toLocaleString());

    // Customize 100% message based on plan type
    if (threshold === 100) {
      if (isFree) {
        body = body.replace(/{overageinfo}/g,
          'Your free plan tokens are fully used. Upgrade to a paid plan to keep coding with AI.');
      } else {
        body = body.replace(/{overageinfo}/g,
          'Your paid plan allows overage at $2/100K tokens. An invoice has been created.\n\nTo avoid overage charges, upgrade or buy a token pack.');
      }
    }

    await sendEmail({ clientId: whmcsClientId, subject: tpl.subject, body });
    await redis.setex(key, TTL_35_DAYS, '1');
    logger.info(`email: token ${threshold}% alert sent to client ${whmcsClientId}`);
  } catch (err) {
    logger.error(`email: token alert failed for ${whmcsClientId}: ${err.message}`);
  }
}

// ── Winback Emails ──────────────────────────────────────────────────────────

/**
 * Check for inactive users and send winback emails.
 * Called by the email reaper cron (every 6 hours).
 *
 * Criteria: last API activity > 7 days ago, not sent winback in last 30 days.
 */
async function processWinbackEmails() {
  const redis = getRedis();
  const now = Date.now();
  const INACTIVITY_THRESHOLD = 7 * 24 * 60 * 60 * 1000; // 7 days

  try {
    // Find all users with activity tracking (only IDE timestamp keys, skip ui_activity lists)
    const activityKeys = await scanKeys(redis, 'activity:*');
    for (const ak of activityKeys) {
      const daUsername = ak.replace('activity:', '');
      // Safety: skip keys that aren't strings (ui_activity uses lists)
      const keyType = await redis.type(ak);
      if (keyType !== 'string') continue;
      const lastActivity = parseInt(await redis.get(ak) || '0', 10);
      if (!lastActivity) continue;

      const idleMs = now - lastActivity;
      if (idleMs < INACTIVITY_THRESHOLD) continue; // Still active

      // Find client ID from DA username
      let clientId = null;
      const reverseKey = await redis.get(`client_id_by_da:${daUsername}`);
      if (reverseKey) {
        clientId = reverseKey;
      } else {
        // Slower scan
        let cursor = '0';
        do {
          const [next, keys] = await redis.scan(cursor, 'MATCH', 'da_username:*', 'COUNT', 100);
          cursor = next;
          for (const key of keys) {
            if ((await redis.get(key)) === daUsername) {
              clientId = key.split(':')[1];
              break;
            }
          }
        } while (cursor !== '0' && !clientId);
      }
      if (!clientId) continue;

      // Check dedup — only one winback per 30 days
      const winbackKey = `email:winback:${clientId}`;
      const lastWinback = await redis.get(winbackKey);
      if (lastWinback) continue;

      // Send winback
      try {
        const client = await getClient(clientId);
        const firstname = client.firstname || 'there';

        const tpl = TEMPLATES.winback;
        const tokenLimit = parseInt(await redis.get(`tokens:limit:${clientId}`) || '0', 10);

        // Free users get a special offer, paid users get a gentle nudge
        let specialOffer = '';
        if (tokenLimit <= 50000) {
          specialOffer = 'Special offer: Use code COMEBACK20 for 20% off your first month on any paid plan!';
        } else {
          specialOffer = 'Your workspace and all your files are right where you left them.';
        }

        const body = tpl.body
          .replace(/{firstname}/g, firstname)
          .replace(/{special_offer}/g, specialOffer);

        await sendEmail({ clientId, subject: tpl.subject, body });
        await redis.setex(winbackKey, TTL_30_DAYS, String(now));
        logger.info(`email: winback sent to client ${clientId} (idle ${Math.round(idleMs / 86400000)}d)`);
      } catch (err) {
        logger.error(`email: winback failed for ${clientId}: ${err.message}`);
      }
    }
  } catch (err) {
    logger.error(`email: winback processing error: ${err.message}`);
  }
}

// ── Upgrade Suggestion Emails ───────────────────────────────────────────────

/**
 * Check usage patterns and send upgrade suggestion emails.
 * Called by the email reaper cron (daily).
 *
 * Criteria: >90% usage, not on highest plan, no upgrade email in 14 days.
 */
async function processUpgradeSuggestions() {
  const redis = getRedis();
  const config = require('../config');

  const UPGRADE_PATH = {
    50000:   { plan: 'Free',         next: 'Builder',       nextTokens: '300K', nextPrice: 15, annualPrice: 12 },
    300000:  { plan: 'Builder',      next: 'Professional',  nextTokens: '600K', nextPrice: 29, annualPrice: 24 },
    600000:  { plan: 'Professional', next: 'Studio',        nextTokens: '1.5M', nextPrice: 59, annualPrice: 48 },
    1500000: { plan: 'Studio',       next: 'Business',      nextTokens: '3M',   nextPrice: 99, annualPrice: 80 },
    3000000: { plan: 'Business',     next: 'Enterprise',    nextTokens: '5M',   nextPrice: 199, annualPrice: 158 },
  };

  try {
    const accessKeys = await scanKeys(redis, 'access:*');
    for (const key of accessKeys) {
      const clientId = key.split(':')[1];
      const tokensUsed = parseInt(await redis.get(`tokens:used:${clientId}`) || '0', 10);
      const tokensLimit = parseInt(await redis.get(`tokens:limit:${clientId}`) || '0', 10);
      if (!tokensLimit || tokensLimit >= 5000000) continue; // Already on max plan

      const percent = Math.round((tokensUsed / tokensLimit) * 100);
      if (percent < 90) continue; // Not a candidate

      // Check dedup
      const upgradeKey = `email:upgrade:${clientId}`;
      const lastUpgrade = await redis.get(upgradeKey);
      if (lastUpgrade) continue;

      const upgrade = UPGRADE_PATH[tokensLimit];
      if (!upgrade) continue;

      try {
        const client = await getClient(clientId);
        const firstname = client.firstname || 'there';

        const tpl = TEMPLATES.upgrade_suggestion;
        const body = tpl.body
          .replace(/{firstname}/g, firstname)
          .replace(/{percent}/g, String(percent))
          .replace(/{plan}/g, upgrade.plan)
          .replace(/{nextplan}/g, upgrade.next)
          .replace(/{nexttokens}/g, upgrade.nextTokens)
          .replace(/\$\{nextprice\}/g, String(upgrade.nextPrice))
          .replace(/\$\{annualprice\}/g, String(upgrade.annualPrice));

        await sendEmail({ clientId, subject: tpl.subject, body });
        await redis.setex(upgradeKey, TTL_14_DAYS, '1');
        logger.info(`email: upgrade suggestion sent to client ${clientId} (${percent}% on ${upgrade.plan})`);
      } catch (err) {
        logger.error(`email: upgrade suggestion failed for ${clientId}: ${err.message}`);
      }
    }
  } catch (err) {
    logger.error(`email: upgrade suggestions processing error: ${err.message}`);
  }
}

// ── Email Reaper (scheduled processor) ──────────────────────────────────────

/**
 * Run all scheduled email tasks.
 * Should be called from a setInterval in server.js.
 */
async function runEmailReaper() {
  logger.debug('email-reaper: running scheduled email tasks...');
  await processWelcomeSchedules();
  // Winback runs every 6 hours (called selectively from server.js)
  // Upgrade runs daily (called selectively from server.js)
}

// ── Weekly Usage Digest ─────────────────────────────────────────────────────

const TTL_8_DAYS = 8 * 24 * 60 * 60;

/**
 * Send weekly usage digest emails to all active customers.
 * Called from server.js every 7 days.
 *
 * Content: tokens used this week, models used, cost savings, upgrade nudge.
 * Redis dedup: email:digest:<clientId> → timestamp (8-day TTL)
 */
async function processWeeklyDigest() {
  const redis = getRedis();
  const tc = require('../tokens/tokenCounter');
  const { getDailyUsage, getModelBreakdown, getTotalCost } = require('../tokens/usageTracker');
  const config = require('../config');

  try {
    const accessKeys = await scanKeys(redis, 'access:*');
    let sent = 0;

    for (const key of accessKeys) {
      const clientId = key.split(':')[1];
      if (!clientId) continue;

      // Dedup: one digest per 7 days
      const digestKey = `email:digest:${clientId}`;
      const lastDigest = await redis.get(digestKey);
      if (lastDigest) continue;

      try {
        const [usage, dailyUsage, modelBreakdown, totalCost, client] = await Promise.all([
          tc.getUsage(clientId),
          getDailyUsage(clientId, 7),
          getModelBreakdown(clientId),
          getTotalCost(clientId),
          getClient(clientId),
        ]);

        // Skip if user has zero usage (don't spam inactive users)
        const weekTokens = dailyUsage.reduce((sum, d) => sum + (d.total?.input || 0) + (d.total?.output || 0), 0);
        if (weekTokens === 0) continue;

        const firstname = client.firstname || 'there';
        const planName = Object.entries(config.tokenLimits).find(([, limit]) => limit === usage.limit)?.[0] || 'unknown';
        const planLabel = planName.charAt(0).toUpperCase() + planName.slice(1);
        const pct = usage.limit > 0 ? Math.min(Math.round((usage.used / usage.limit) * 100), 100) : 0;

        // Models summary
        const topModels = modelBreakdown
          .sort((a, b) => (b.input + b.output) - (a.input + a.output))
          .slice(0, 3)
          .map(m => `• ${m.displayName || m.model}: ${formatDigestTokens(m.input + m.output)} tokens ($${m.cost.toFixed(2)})`)
          .join('\n');

        // Cost savings vs retail
        const retailEquiv = totalCost * 3.2; // Approximate retail markup
        const savings = Math.max(0, retailEquiv - (totalCost * 0.1));

        // Upgrade hint
        let upgradeHint = '';
        if (pct >= 70 && planName !== 'enterprise') {
          upgradeHint = `\n💡 You've used ${pct}% of your ${planLabel} plan. Consider upgrading for more tokens: ${process.env.AI_UPGRADE_URL || 'https://gositeme.com/pricing.php'}`;
        }

        const subject = `Your GoCodeMe Weekly Report — ${formatDigestTokens(weekTokens)} tokens used`;
        const body = `Hi ${firstname},

Here's your weekly GoCodeMe usage summary:

📊 This Week
• Tokens used: ${formatDigestTokens(weekTokens)}
• Total this billing period: ${formatDigestTokens(usage.used)} / ${usage.limit > 0 ? formatDigestTokens(usage.limit) : '∞'} (${pct}%)
• API cost: $${totalCost.toFixed(2)}
• Estimated savings vs direct API: $${savings.toFixed(2)}

🤖 Top Models
${topModels || '• No model usage recorded'}
${upgradeHint}

Keep building! Your AI IDE is ready at https://gocodeme.com

— The GoCodeMe Team

P.S. Need more tokens? Grab a token pack from $5: ${process.env.AI_TOPUP_URL || 'https://gositeme.com/pricing.php#topup'}`;

        await sendEmail({ clientId, subject, body });
        await redis.setex(digestKey, TTL_8_DAYS, Date.now().toString());
        sent++;
        logger.info(`email: weekly digest sent to client ${clientId} (${formatDigestTokens(weekTokens)} tokens this week)`);
      } catch (err) {
        logger.error(`email: weekly digest failed for ${clientId}: ${err.message}`);
      }
    }

    logger.info(`email: weekly digest complete — ${sent} emails sent`);
  } catch (err) {
    logger.error(`email: weekly digest processing error: ${err.message}`);
  }
}

function formatDigestTokens(n) {
  if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M';
  if (n >= 1_000) return (n / 1_000).toFixed(1) + 'K';
  return String(n);
}

module.exports = {
  sendEmail,
  startWelcomeSeries,
  sendTokenAlertEmail,
  processWelcomeSchedules,
  processWinbackEmails,
  processUpgradeSuggestions,
  processWeeklyDigest,
  runEmailReaper,
  TEMPLATES,
};
