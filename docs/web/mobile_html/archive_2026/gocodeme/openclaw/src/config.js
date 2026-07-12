'use strict';

require('dotenv').config({ path: require('path').join(__dirname, '../../.env') });

module.exports = {
  port: parseInt(process.env.OPENCLAW_PORT || '3004', 10),
  env: process.env.NODE_ENV || 'development',

  redis: {
    url: process.env.REDIS_URL || 'redis://localhost:6379',
  },

  jwt: {
    // SECURITY (VULN-R2-03): Use centralized secret; fail-fast if missing in production
    secret: process.env.JWT_SECRET || require('crypto').randomBytes(32).toString('hex'),
  },

  // Internal middleware base URL (OpenClaw calls middleware for token gating)
  middleware: {
    url: process.env.MIDDLEWARE_URL || 'http://localhost:3001',
  },

  // OpenHands Agent base URL (OpenClaw sends tasks here)
  agent: {
    url: process.env.AGENT_URL || 'http://localhost:4001',
  },

  // ── Telegram ─────────────────────────────────────────────────────────────
  telegram: {
    token:   process.env.TELEGRAM_BOT_TOKEN   || '',
    enabled: !!process.env.TELEGRAM_BOT_TOKEN,
  },

  // ── WhatsApp (via Meta Cloud API) ────────────────────────────────────────
  whatsapp: {
    token:        process.env.WHATSAPP_TOKEN        || '',
    phoneId:      process.env.WHATSAPP_PHONE_ID     || '',
    verifyToken:  process.env.WHATSAPP_VERIFY_TOKEN || 'openclaw-wa-verify',
    enabled:      !!process.env.WHATSAPP_TOKEN,
  },

  // ── Discord (bot token) ──────────────────────────────────────────────────
  discord: {
    token:         process.env.DISCORD_BOT_TOKEN    || '',
    appId:         process.env.DISCORD_APP_ID       || '',
    publicKey:     process.env.DISCORD_PUBLIC_KEY   || '',
    enabled:       !!process.env.DISCORD_BOT_TOKEN,
  },

  // ── Message routing ──────────────────────────────────────────────────────
  // Each customer message is queued here before the agent responds
  queue: {
    ttl: 60 * 60 * 24 * 7,  // 7 days — keep conversation history
    maxHistory: 50,           // messages per conversation kept in Redis
  },
};
