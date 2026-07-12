'use strict';

/**
 * Alfred OS Runtime — WebSocket + REST bridge for real-time agent operations
 *
 * Provides both REST endpoints for PHP → Node.js integration
 * and WebSocket channels for real-time streaming:
 *
 * REST Endpoints:
 *   POST /api/agentos/push      — Push event to subscribed clients
 *   POST /api/agentos/proxy     — Proxy request to PHP Alfred OS APIs
 *   GET  /api/agentos/status    — Runtime health status
 *
 * WebSocket Channels (via alfred-ws port 3010):
 *   agentos:runtime   — Agent loop lifecycle events
 *   agentos:tasks      — Task progress updates
 *   agentos:world      — World state changes + drift alerts
 *   agentos:devices    — Device telemetry + status
 *   agentos:audit      — Live audit stream
 *   agentos:sim        — Simulation results
 */

const express = require('express');
const http = require('http');
const router = express.Router();
const logger = require('../logger');
const safeError = require('../utils/safeError');
const config = require('../config');

const INTERNAL_SECRET = process.env.INTERNAL_SECRET || process.env.INTERNAL_RELAY_SECRET || '';
const PHP_API_BASE = 'http://127.0.0.1:' + (process.env.PORT || 80) + '/api/agentos';

// ── Auth middleware ────────────────────────────────────────────

function requireInternalOrAuth(req, res, next) {
  // Internal secret bypass (PHP → Node.js calls)
  // SECURITY (R4-01): Use timing-safe comparison to prevent secret recovery
  const internalHeader = req.headers['x-internal-secret'] || '';
  if (INTERNAL_SECRET && internalHeader.length === INTERNAL_SECRET.length) {
    const crypto = require('crypto');
    if (crypto.timingSafeEqual(Buffer.from(internalHeader), Buffer.from(INTERNAL_SECRET))) {
      req._agentosAuth = { internal: true, user_id: 'system' };
      return next();
    }
  }

  // JWT auth
  const auth = req.headers.authorization || '';
  if (auth.startsWith('Bearer ')) {
    try {
      const jwt = require('jsonwebtoken');
      const decoded = jwt.verify(auth.slice(7), config.jwt.secret);
      req._agentosAuth = {
        internal: false,
        user_id: decoded.daUsername || decoded.whmcsClientId || 'unknown',
      };
      return next();
    } catch (err) {
      return res.status(401).json({ error: 'Invalid token' });
    }
  }

  return res.status(401).json({ error: 'Authentication required' });
}

// ── Channel subscription registry ─────────────────────────────

const subscribers = new Map(); // channel → Set<res> for SSE

function addSubscriber(channel, res) {
  if (!subscribers.has(channel)) subscribers.set(channel, new Set());
  subscribers.get(channel).add(res);
  res.on('close', () => {
    subscribers.get(channel)?.delete(res);
  });
}

function broadcast(channel, event, data) {
  const subs = subscribers.get(channel);
  if (!subs || subs.size === 0) return 0;
  const payload = `event: ${event}\ndata: ${JSON.stringify(data)}\n\n`;
  let sent = 0;
  for (const res of subs) {
    try {
      res.write(payload);
      sent++;
    } catch (e) {
      subs.delete(res);
    }
  }
  return sent;
}

// ── REST: Push event ──────────────────────────────────────────

router.post('/push', requireInternalOrAuth, (req, res) => {
  try {
    const { channel, event, data } = req.body;
    if (!channel || !event) {
      return res.status(400).json({ error: 'channel and event required' });
    }
    const sent = broadcast(channel, event, data || {});
    logger.info(`[Alfred OS] Push → ${channel}:${event} (${sent} clients)`);
    res.json({ ok: true, channel, event, clients: sent });
  } catch (err) {
    logger.error('[Alfred OS] Push error:', safeError(err));
    res.status(500).json({ error: 'Push failed' });
  }
});

// ── REST: Proxy to PHP Alfred OS ────────────────────────────────

router.post('/proxy', requireInternalOrAuth, async (req, res) => {
  try {
    const { module, action, body } = req.body;
    if (!module || !action) {
      return res.status(400).json({ error: 'module and action required' });
    }

    const validModules = [
      'runtime', 'capabilities', 'skills', 'tasks',
      'memory', 'world-state', 'policy', 'simulation',
      'audit', 'bridge',
    ];
    if (!validModules.includes(module)) {
      return res.status(400).json({ error: `Invalid module: ${module}` });
    }

    const url = `${PHP_API_BASE}/${module}.php?action=${encodeURIComponent(action)}`;
    const result = await proxyToPhp(url, body || {});
    res.json(result);
  } catch (err) {
    logger.error('[Alfred OS] Proxy error:', safeError(err));
    res.status(502).json({ error: 'PHP proxy failed' });
  }
});

// ── REST: Status ──────────────────────────────────────────────

router.get('/status', (req, res) => {
  const channelStats = {};
  for (const [channel, subs] of subscribers) {
    channelStats[channel] = subs.size;
  }

  res.json({
    ok: true,
    service: 'agentos-runtime-bridge',
    version: '1.0.0',
    channels: channelStats,
    total_subscribers: Array.from(subscribers.values()).reduce((s, set) => s + set.size, 0),
    uptime_seconds: Math.floor(process.uptime()),
  });
});

// ── SSE: Subscribe to channel ─────────────────────────────────

router.get('/subscribe/:channel', requireInternalOrAuth, (req, res) => {
  const channel = req.params.channel;
  const validChannels = [
    'agentos:runtime', 'agentos:tasks', 'agentos:world',
    'agentos:devices', 'agentos:audit', 'agentos:sim',
  ];

  if (!validChannels.includes(channel)) {
    return res.status(400).json({ error: `Invalid channel: ${channel}` });
  }

  // Set up SSE
  res.writeHead(200, {
    'Content-Type': 'text/event-stream',
    'Cache-Control': 'no-cache',
    'Connection': 'keep-alive',
    'X-Accel-Buffering': 'no',
  });

  res.write(`event: connected\ndata: ${JSON.stringify({ channel })}\n\n`);

  // SECURITY (R4-02): Scope channels per user to prevent cross-tenant data leakage
  const userId = req._agentosAuth?.user_id || 'unknown';
  const scopedChannel = req._agentosAuth?.internal ? channel : `${channel}:user_${userId}`;
  addSubscriber(scopedChannel, res);
  logger.info(`[Alfred OS] SSE subscriber ${userId} joined ${scopedChannel}`);

  // Keep-alive every 30s
  const keepAlive = setInterval(() => {
    try { res.write(':keepalive\n\n'); } catch (e) { clearInterval(keepAlive); }
  }, 30000);

  res.on('close', () => {
    clearInterval(keepAlive);
    logger.info(`[Alfred OS] SSE subscriber left ${channel}`);
  });
});

// ── PHP proxy helper ──────────────────────────────────────────

function proxyToPhp(url, body) {
  return new Promise((resolve, reject) => {
    const payload = JSON.stringify(body);
    const parsed = new URL(url);

    const options = {
      hostname: parsed.hostname,
      port: parsed.port || 80,
      path: parsed.pathname + parsed.search,
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Content-Length': Buffer.byteLength(payload),
        'X-Internal-Secret': INTERNAL_SECRET,
      },
      timeout: 30000,
    };

    const req = http.request(options, (res) => {
      let data = '';
      res.on('data', chunk => data += chunk);
      res.on('end', () => {
        try {
          resolve(JSON.parse(data));
        } catch (e) {
          resolve({ raw: data });
        }
      });
    });

    req.on('error', err => reject(err));
    req.on('timeout', () => { req.destroy(); reject(new Error('PHP proxy timeout')); });
    req.write(payload);
    req.end();
  });
}

module.exports = router;
