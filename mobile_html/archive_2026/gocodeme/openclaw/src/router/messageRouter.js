'use strict';

/**
 * Message Router
 *
 * Central hub: receives an inbound message from any channel adapter,
 * handles built-in commands (/link, /unlink, /reset, /status, /help),
 * and routes all other messages to the agent bridge.
 *
 * Built-in commands (no agent needed):
 *   /link <token>   — link this channel user to a GoCodeMe account
 *   /unlink         — unlink and clear history
 *   /reset          — clear conversation history (keep link)
 *   /status         — show token usage summary
 *   /help           — show available commands
 */

const jwt      = require('jsonwebtoken');
const axios    = require('axios');
const conv     = require('../store/conversations');
const { sendToAgent } = require('../agent/bridge');
const config   = require('../config');
const logger   = require('../logger');

const MIDDLEWARE = config.middleware.url;

/**
 * Route an inbound message.
 *
 * @param {object} msg
 * @param {string}   msg.channel        'telegram' | 'whatsapp' | 'discord'
 * @param {string}   msg.channelUserId  Telegram chat_id / WA phone / Discord userId
 * @param {string}   msg.text           Raw message text
 * @param {string}   [msg.displayName]  User's display name from the channel
 * @returns {Promise<string>}  The reply to send back
 */
async function routeMessage({ channel, channelUserId, text, displayName }) {
  const trimmed = (text || '').trim();
  const lower   = trimmed.toLowerCase();
  const parts   = trimmed.split(/\s+/);
  const cmd     = parts[0]?.toLowerCase();

  logger.info(`router: [${channel}] ${channelUserId} → "${trimmed.slice(0, 80)}"`);

  // ── /help ─────────────────────────────────────────────────────────────────
  if (cmd === '/help' || trimmed === '') {
    return [
      '👋 *GoCodeMe Assistant*',
      '',
      'I can answer questions about your code or run tasks in your workspace.',
      '',
      '*Commands:*',
      '`/link <token>` — connect your GoCodeMe account',
      '`/unlink`       — disconnect your account',
      '`/reset`        — start a fresh conversation',
      '`/status`       — check your token usage',
      '`/help`         — show this message',
      '',
      'Get your link token from the GoCodeMe dashboard → Settings → Messaging.',
    ].join('\n');
  }

  // ── /link <token> ─────────────────────────────────────────────────────────
  if (cmd === '/link') {
    const linkToken = parts[1];
    if (!linkToken) return '❌ Usage: `/link <token>` — get your token from the GoCodeMe dashboard.';

    // Validate the token against middleware
    try {
      const resp = await axios.post(`${MIDDLEWARE}/api/openclaw/link`, {
        channel, channelUserId, linkToken, displayName,
      }, { timeout: 8000 });

      if (!resp.data?.ok) {
        return `❌ Link failed: ${resp.data?.error || 'invalid token'}`;
      }

      const { daUsername, whmcsClientId, plan } = resp.data;
      await conv.linkUser(channel, channelUserId, daUsername, whmcsClientId, plan);

      return [
        `✅ Linked to GoCodeMe account *${daUsername}* (${plan} plan).`,
        'You can now chat with your AI assistant here.',
        'Type `/help` for available commands.',
      ].join('\n');
    } catch (err) {
      logger.error(`router: link failed for ${channel}:${channelUserId}: ${err.message}`);
      return '❌ Could not verify your link token. Please try again or contact support.';
    }
  }

  // ── /unlink ───────────────────────────────────────────────────────────────
  if (cmd === '/unlink') {
    const user = await conv.getLinkedUser(channel, channelUserId);
    if (!user) return 'ℹ️ No account linked. Use `/link <token>` to connect your GoCodeMe account.';
    await conv.unlinkUser(channel, channelUserId);
    return '✅ Account unlinked. Your conversation history has been cleared.';
  }

  // ── /reset ────────────────────────────────────────────────────────────────
  if (cmd === '/reset') {
    await conv.clearHistory(channel, channelUserId);
    return '🔄 Conversation cleared. Starting fresh!';
  }

  // ── /status ───────────────────────────────────────────────────────────────
  if (cmd === '/status') {
    const user = await conv.getLinkedUser(channel, channelUserId);
    if (!user) return 'ℹ️ No account linked. Use `/link <token>` to connect.';

    try {
      // Build a minimal session JWT to call the middleware
      const sessionJwt = jwt.sign(
        { daUsername: user.daUsername, whmcsClientId: user.whmcsClientId, plan: user.plan },
        config.jwt.secret,
        { expiresIn: '5m' }
      );
      const resp = await axios.get(`${MIDDLEWARE}/api/tokens/usage`, {
        headers: { Authorization: `Bearer ${sessionJwt}` },
        timeout: 5000,
      });
      const u = resp.data?.usage || {};
      const pct   = u.percentUsed || 0;
      const bar   = buildBar(pct);
      const emoji = pct >= 90 ? '🔴' : pct >= 70 ? '🟡' : '🟢';
      return [
        `${emoji} *Token Usage — ${user.plan} plan*`,
        `${bar} ${pct}%`,
        `Used: ${fmt(u.used)} / ${fmt(u.limit)}`,
        `Remaining: ${fmt((u.limit || 0) - (u.used || 0))}`,
      ].join('\n');
    } catch (err) {
      return `❌ Could not fetch usage: ${err.message}`;
    }
  }

  // ── All other messages — route to agent ───────────────────────────────────
  const user = await conv.getLinkedUser(channel, channelUserId);
  if (!user) {
    return [
      '👋 Hi! I\'m the GoCodeMe AI assistant.',
      'To get started, link your account with:',
      '`/link <token>`',
      '',
      'Get your token from the GoCodeMe dashboard → Settings → Messaging.',
    ].join('\n');
  }

  // Load history and build session JWT
  const history    = await conv.getHistory(channel, channelUserId);
  const sessionJwt = jwt.sign(
    { daUsername: user.daUsername, whmcsClientId: user.whmcsClientId, plan: user.plan },
    config.jwt.secret,
    { expiresIn: '5m' }
  );

  // Send to agent
  const { reply } = await sendToAgent({
    daUsername:    user.daUsername,
    whmcsClientId: user.whmcsClientId,
    plan:          user.plan,
    userMessage:   trimmed,
    history,
    sessionJwt,
  });

  // Persist both sides of the conversation
  await conv.appendMessage(channel, channelUserId, 'user', trimmed);
  await conv.appendMessage(channel, channelUserId, 'assistant', reply);
  await conv.bumpStats(user.daUsername);

  return reply;
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function buildBar(pct) {
  const filled = Math.round(Math.min(pct, 100) / 10);
  return '█'.repeat(filled) + '░'.repeat(10 - filled);
}

function fmt(n) {
  if (!n) return '0';
  if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M';
  if (n >= 1_000)     return (n / 1_000).toFixed(0) + 'K';
  return String(n);
}

module.exports = { routeMessage };
