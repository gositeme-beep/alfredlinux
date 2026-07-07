'use strict';

/**
 * OpenClaw — GoCodeMe Messaging Gateway
 *
 * Listens on OPENCLAW_PORT (default 3004).
 * Routes inbound messages from Telegram, WhatsApp, and Discord to the
 * GoCodeMe AI agent and sends replies back.
 *
 * Routes:
 *   GET  /health                             — health check
 *   POST /webhook/telegram                   — Telegram webhook
 *   GET  /webhook/whatsapp                   — WhatsApp verify challenge
 *   POST /webhook/whatsapp                   — WhatsApp inbound
 *   POST /webhook/discord                    — Discord interactions
 *   GET  /discord/register-commands          — Register Discord slash commands
 *   GET  /api/openclaw/stats/:daUsername     — Messaging stats (middleware calls this)
 */

require('dotenv').config({ path: require('path').join(__dirname, '../../middleware/.env') });

const express = require('express');
const helmet  = require('helmet');
const cors    = require('cors');
const morgan  = require('morgan');

const config  = require('./config');
const logger  = require('./logger');
const { getRedis } = require('./redis');

// ── Channel adapters ──────────────────────────────────────────────────────────
const { router: telegramRouter, startPolling } = require('./adapters/telegram');
const { router: whatsappRouter }               = require('./adapters/whatsapp');
const { router: discordRouter }                = require('./adapters/discord');

// ── Stats API (called by middleware /api/openclaw/stats) ─────────────────────
const { getStats, listLinksForUser } = require('./store/conversations');

const app = express();

// ── Security ──────────────────────────────────────────────────────────────────
app.use(helmet({ contentSecurityPolicy: false }));
app.use(cors({ origin: ['https://gocodeme.com', 'https://gositeme.com', 'http://localhost:3001'] }));

// ── Logging ───────────────────────────────────────────────────────────────────
app.use(morgan('combined', {
  stream: { write: msg => logger.info(msg.trim()) },
  skip: req => req.path === '/health',
}));

// ── Health ────────────────────────────────────────────────────────────────────
app.get('/health', (_req, res) => {
  res.json({
    ok:      true,
    service: 'openclaw',
    ts:      new Date().toISOString(),
    channels: {
      telegram:  config.telegram.enabled,
      whatsapp:  config.whatsapp.enabled,
      discord:   config.discord.enabled,
    },
  });
});

// ── Channel webhook routes ────────────────────────────────────────────────────
app.use('/', telegramRouter);
app.use('/', whatsappRouter);
app.use('/', discordRouter);

// ── Internal stats API (called by middleware proxy) ───────────────────────────
// Middleware calls: GET http://localhost:3004/api/openclaw/stats/:daUsername
app.get('/api/openclaw/stats/:daUsername', async (req, res) => {
  try {
    const { daUsername } = req.params;
    const [stats, links] = await Promise.all([
      getStats(daUsername),
      listLinksForUser(daUsername),
    ]);
    res.json({ ok: true, stats, links });
  } catch (err) {
    res.status(500).json({ ok: false, error: err.message });
  }
});

// ── Link token validation (called by message router) ─────────────────────────
// Middleware exposes POST /api/openclaw/link — OpenClaw calls that.
// This endpoint validates link tokens issued by the middleware.

// ── 404 ───────────────────────────────────────────────────────────────────────
app.use((_req, res) => res.status(404).json({ ok: false, error: 'Not found' }));

// ── Error handler ─────────────────────────────────────────────────────────────
// eslint-disable-next-line no-unused-vars
app.use((err, _req, res, _next) => {
  logger.error(err.stack || err.message);
  res.status(err.status || 500).json({ ok: false, error: err.message });
});

// ── Start ─────────────────────────────────────────────────────────────────────
async function start() {
  getRedis();  // warm connection

  app.listen(config.port, '127.0.0.1', () => {
    logger.info(`OpenClaw running on 127.0.0.1:${config.port} [${config.env}]`);
    logger.info(`Channels: Telegram=${config.telegram.enabled} WhatsApp=${config.whatsapp.enabled} Discord=${config.discord.enabled}`);
  });

  // Start Telegram long-polling if no webhook is configured
  // (In production, set TELEGRAM_WEBHOOK_URL and register via Telegram BotAPI)
  if (config.telegram.enabled && !process.env.TELEGRAM_WEBHOOK_URL) {
    startPolling();
  }

  process.on('SIGTERM', () => { logger.info('OpenClaw shutting down'); process.exit(0); });
  process.on('SIGINT',  () => { logger.info('OpenClaw shutting down'); process.exit(0); });
}

start().catch(err => {
  logger.error(`OpenClaw failed to start: ${err.message}`);
  process.exit(1);
});

module.exports = app;
