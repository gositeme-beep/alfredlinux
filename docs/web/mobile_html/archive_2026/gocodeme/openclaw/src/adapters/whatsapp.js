'use strict';

/**
 * WhatsApp Adapter (Meta Cloud API)
 *
 * Handles the Meta webhook verification challenge + inbound messages.
 *
 * Setup in Meta Developer Console:
 *   1. Create a WhatsApp Business app
 *   2. Set webhook URL: https://<host>/openclaw/webhook/whatsapp
 *   3. Set verify token: process.env.WHATSAPP_VERIFY_TOKEN
 *   4. Subscribe to: messages
 *
 * Env vars required:
 *   WHATSAPP_TOKEN         — permanent access token from Meta
 *   WHATSAPP_PHONE_ID      — Phone Number ID from Meta (not the phone number itself)
 *   WHATSAPP_VERIFY_TOKEN  — any string you set in the Meta console
 */

const express = require('express');
const axios   = require('axios');
const { routeMessage } = require('../router/messageRouter');
const config  = require('../config');
const logger  = require('../logger');

const router  = express.Router();
const WA_API  = `https://graph.facebook.com/v18.0/${config.whatsapp.phoneId}/messages`;

// ── Webhook verification (GET) ────────────────────────────────────────────────
router.get('/webhook/whatsapp', (req, res) => {
  const mode      = req.query['hub.mode'];
  const token     = req.query['hub.verify_token'];
  const challenge = req.query['hub.challenge'];

  if (mode === 'subscribe' && token === config.whatsapp.verifyToken) {
    logger.info('WhatsApp: webhook verified');
    return res.status(200).send(challenge);
  }
  res.sendStatus(403);
});

// ── Inbound messages (POST) ───────────────────────────────────────────────────
router.post('/webhook/whatsapp', express.json(), async (req, res) => {
  res.sendStatus(200);  // always ack immediately

  const entry    = req.body?.entry?.[0];
  const changes  = entry?.changes?.[0];
  const value    = changes?.value;
  const messages = value?.messages;

  if (!messages || messages.length === 0) return;

  for (const msg of messages) {
    if (msg.type !== 'text') {
      await sendWhatsApp(msg.from, '📎 Please send text messages. Voice notes, images and documents are not supported yet.');
      continue;
    }

    const displayName = value?.contacts?.[0]?.profile?.name || msg.from;

    try {
      const reply = await routeMessage({
        channel:       'whatsapp',
        channelUserId: msg.from,  // international format phone number e.g. 15551234567
        text:          msg.text?.body || '',
        displayName,
      });
      await sendWhatsApp(msg.from, reply);
    } catch (err) {
      logger.error(`whatsapp: handler error for ${msg.from}: ${err.message}`);
      await sendWhatsApp(msg.from, '⚠️ Something went wrong. Please try again.').catch(() => {});
    }
  }
});

// ── Send a message via Meta Cloud API ────────────────────────────────────────
async function sendWhatsApp(to, text) {
  if (!config.whatsapp.token || !config.whatsapp.phoneId) return;
  try {
    await axios.post(WA_API, {
      messaging_product: 'whatsapp',
      to,
      type: 'text',
      text: { body: text.slice(0, 4096) },
    }, {
      headers: {
        Authorization: `Bearer ${config.whatsapp.token}`,
        'Content-Type': 'application/json',
      },
      timeout: 10000,
    });
  } catch (err) {
    logger.error(`whatsapp: send to ${to} failed: ${err.message}`);
  }
}

module.exports = { router, sendWhatsApp };
