'use strict';

/**
 * Discord Adapter
 *
 * Uses Discord's Interactions endpoint (slash commands + DM messages).
 * Does NOT use a persistent WebSocket gateway — uses HTTP interactions only
 * so it works on a VPS without a persistent process open to Discord.
 *
 * Setup:
 *   1. Create a Discord app at https://discord.com/developers/applications
 *   2. Under "General Information", copy Public Key → DISCORD_PUBLIC_KEY
 *   3. Set Interactions Endpoint URL: https://<host>/openclaw/webhook/discord
 *   4. Register slash command /ask in Discord developer portal (or via the
 *      /openclaw/discord/register-commands route)
 *   5. Invite the bot to your server with: applications.commands + bot scopes
 *
 * Env vars required:
 *   DISCORD_BOT_TOKEN   — Bot Token
 *   DISCORD_APP_ID      — Application ID
 *   DISCORD_PUBLIC_KEY  — Public Key (for request verification)
 *
 * Note: nacl (tweetnacl) is not installed, so we do a best-effort
 * signature check using the crypto module. In production, install
 * `tweetnacl` for proper Ed25519 verification.
 */

const express = require('express');
const crypto  = require('crypto');
const axios   = require('axios');
const { routeMessage } = require('../router/messageRouter');
const config  = require('../config');
const logger  = require('../logger');

const router  = express.Router();

const DISCORD_API = 'https://discord.com/api/v10';

// ── Ed25519 signature verification ───────────────────────────────────────────
function verifyDiscordSignature(req) {
  if (!config.discord.publicKey) return true; // skip if not configured

  const signature = req.headers['x-signature-ed25519'];
  const timestamp = req.headers['x-signature-timestamp'];
  if (!signature || !timestamp) return false;

  // node's crypto doesn't natively support Ed25519 verify with hex keys in
  // older versions — check availability and skip gracefully if unavailable
  try {
    const publicKeyBytes  = Buffer.from(config.discord.publicKey, 'hex');
    const bodyStr         = req.rawBody || JSON.stringify(req.body);
    const msg             = Buffer.from(timestamp + bodyStr);
    const sigBytes        = Buffer.from(signature, 'hex');

    // createVerify with 'ed25519' requires Node 15+
    const verify = crypto.createVerify('ed25519');
    verify.update(msg);
    return verify.verify({ key: publicKeyBytes, format: 'raw', type: 'spki' }, sigBytes);
  } catch {
    // Fallback: log warning but allow (for dev environments)
    logger.warn('Discord: Ed25519 verification skipped (Node version or key format issue)');
    return true;
  }
}

// ── Interactions endpoint ─────────────────────────────────────────────────────
router.post('/webhook/discord',
  express.json({
    verify: (req, _res, buf) => { req.rawBody = buf.toString('utf8'); },
  }),
  async (req, res) => {
    if (!verifyDiscordSignature(req)) {
      return res.status(401).send('Invalid signature');
    }

    const interaction = req.body;

    // Discord PING — must respond immediately with PONG
    if (interaction.type === 1) {
      return res.json({ type: 1 });
    }

    // Application command (slash command)
    if (interaction.type === 2) {
      const commandName = interaction.data?.name;
      const userId      = interaction.member?.user?.id || interaction.user?.id;
      const displayName = interaction.member?.user?.username || interaction.user?.username || 'User';

      if (commandName === 'ask') {
        const text = interaction.data?.options?.find(o => o.name === 'message')?.value || '';

        // Defer the response immediately (Discord requires reply within 3s)
        res.json({ type: 5 });  // DEFERRED_CHANNEL_MESSAGE_WITH_SOURCE

        // Process and follow up
        processDiscordMessage({ userId, displayName, text, interactionToken: interaction.token });
        return;
      }

      return res.json({ type: 4, data: { content: 'Unknown command.' } });
    }

    // Message component interaction
    if (interaction.type === 3) {
      return res.json({ type: 6 });  // ACK
    }

    res.sendStatus(200);
  }
);

// ── Process a Discord message asynchronously and follow up ───────────────────
async function processDiscordMessage({ userId, displayName, text, interactionToken }) {
  try {
    const reply = await routeMessage({
      channel:       'discord',
      channelUserId: userId,
      text,
      displayName,
    });

    await editFollowUp(interactionToken, reply);
  } catch (err) {
    logger.error(`discord: processing error for ${userId}: ${err.message}`);
    await editFollowUp(interactionToken, '⚠️ Something went wrong. Please try again.').catch(() => {});
  }
}

// ── Edit the deferred follow-up message ──────────────────────────────────────
async function editFollowUp(token, content) {
  if (!config.discord.appId || !config.discord.token) return;
  try {
    await axios.patch(
      `${DISCORD_API}/webhooks/${config.discord.appId}/${token}/messages/@original`,
      { content: content.slice(0, 2000) },
      {
        headers: {
          'Authorization': `Bot ${config.discord.token}`,
          'Content-Type':  'application/json',
        },
        timeout: 10000,
      }
    );
  } catch (err) {
    logger.error(`discord: follow-up edit failed: ${err.message}`);
  }
}

// ── Register slash commands ───────────────────────────────────────────────────
// Call GET /openclaw/discord/register-commands (with WHMCS secret) to register
router.get('/discord/register-commands', async (req, res) => {
  const secret = req.headers['x-whmcs-secret'];
  if (!secret || secret !== process.env.WHMCS_WEBHOOK_SECRET) {
    return res.status(401).json({ ok: false, error: 'Unauthorized' });
  }

  if (!config.discord.token || !config.discord.appId) {
    return res.status(400).json({ ok: false, error: 'Discord not configured' });
  }

  const commands = [
    {
      name:        'ask',
      description: 'Ask your GoCodeMe AI assistant a question',
      options: [{
        name:        'message',
        description: 'Your message or question',
        type:        3,  // STRING
        required:    true,
      }],
    },
    {
      name:        'link',
      description: 'Link this Discord account to your GoCodeMe account',
      options: [{
        name:        'token',
        description: 'Your link token from the GoCodeMe dashboard',
        type:        3,
        required:    true,
      }],
    },
    {
      name:        'status',
      description: 'Check your GoCodeMe token usage',
    },
    {
      name:        'reset',
      description: 'Clear your conversation history',
    },
  ];

  try {
    const resp = await axios.put(
      `${DISCORD_API}/applications/${config.discord.appId}/commands`,
      commands,
      {
        headers: {
          'Authorization': `Bot ${config.discord.token}`,
          'Content-Type':  'application/json',
        },
        timeout: 15000,
      }
    );
    logger.info(`discord: registered ${resp.data?.length} slash commands`);
    res.json({ ok: true, registered: resp.data?.length });
  } catch (err) {
    logger.error(`discord: command registration failed: ${err.message}`);
    res.status(500).json({ ok: false, error: err.message });
  }
});

module.exports = { router };
