'use strict';

/**
 * Telegram Adapter
 *
 * Uses Telegram Bot API via long-polling (no webhook needed on dev).
 * In production, register a webhook at:
 *   POST https://api.telegram.org/bot<TOKEN>/setWebhook?url=https://<host>/openclaw/webhook/telegram
 *
 * Incoming update → routeMessage() → sendMessage back to chat.
 *
 * Supports text messages only. Voice/photo/file messages get a friendly prompt
 * to use text.
 */

const express = require('express');
const axios   = require('axios');
const { routeMessage } = require('../router/messageRouter');
const config  = require('../config');
const logger  = require('../logger');

const router  = express.Router();
const TG_API  = `https://api.telegram.org/bot${config.telegram.token}`;
let   polling = false;

// ── Webhook endpoint (production) ────────────────────────────────────────────
router.post('/webhook/telegram', express.json(), async (req, res) => {
  res.sendStatus(200);  // always ack immediately

  const update = req.body;
  const msg    = update?.message || update?.edited_message;
  if (!msg) return;

  await handleTelegramMessage(msg);
});

// ── Handle a single Telegram message object ───────────────────────────────────
async function handleTelegramMessage(msg) {
  const chatId      = String(msg.chat?.id);
  const displayName = msg.from?.first_name || msg.from?.username || 'User';

  // Non-text content
  if (!msg.text) {
    await sendTelegram(chatId, '📎 Please send text messages. Voice notes, photos and files are not supported yet.');
    return;
  }

  try {
    const reply = await routeMessage({
      channel:       'telegram',
      channelUserId: chatId,
      text:          msg.text,
      displayName,
    });
    await sendTelegram(chatId, reply);
  } catch (err) {
    logger.error(`telegram: handler error for chat ${chatId}: ${err.message}`);
    await sendTelegram(chatId, '⚠️ Something went wrong. Please try again.').catch(() => {});
  }
}

// ── Send a message via Telegram Bot API ──────────────────────────────────────
async function sendTelegram(chatId, text) {
  if (!config.telegram.token) return;
  try {
    await axios.post(`${TG_API}/sendMessage`, {
      chat_id:    chatId,
      text:       text.slice(0, 4096),  // Telegram limit
      parse_mode: 'Markdown',
    }, { timeout: 10000 });
  } catch (err) {
    logger.error(`telegram: sendMessage to ${chatId} failed: ${err.message}`);
  }
}

// ── Long-polling loop (development / fallback) ────────────────────────────────
async function startPolling() {
  if (!config.telegram.token) {
    logger.warn('Telegram: no token — polling disabled');
    return;
  }
  if (polling) return;
  polling = true;
  logger.info('Telegram: starting long-poll loop');

  let offset = 0;
  while (polling) {
    try {
      const resp = await axios.get(`${TG_API}/getUpdates`, {
        params: { offset, timeout: 30, allowed_updates: ['message', 'edited_message'] },
        timeout: 35000,
      });
      const updates = resp.data?.result || [];
      for (const upd of updates) {
        offset = upd.update_id + 1;
        const msg = upd.message || upd.edited_message;
        if (msg) await handleTelegramMessage(msg);
      }
    } catch (err) {
      if (!err.message?.includes('ECONNREFUSED')) {
        logger.warn(`Telegram poll error: ${err.message}`);
      }
      await sleep(5000);
    }
  }
}

function stopPolling() { polling = false; }

function sleep(ms) { return new Promise(r => setTimeout(r, ms)); }

module.exports = { router, startPolling, stopPolling, sendTelegram };
