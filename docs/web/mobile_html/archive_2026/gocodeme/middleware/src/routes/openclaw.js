'use strict';

/**
 * Middleware proxy layer for OpenClaw messaging gateway.
 *
 * Routes:
 *   GET  /api/openclaw/link-token           — issue a short-lived link token (authenticated user)
 *   POST /api/openclaw/link                 — validate link token, persist channel→user mapping
 *   GET  /api/openclaw/stats/:daUsername    — conversation stats (own resource)
 *   GET  /api/openclaw/links/:daUsername    — list linked channel accounts (own resource)
 *   POST /api/openclaw/send                 — admin push a message to a channel user
 *
 * Design:
 *   - /link-token  → session JWT required, issues openclaw-link sub-token
 *   - /link        → called by OpenClaw server (or message router), no session required,
 *                    validates the sub-token and POSTs to openclaw internal /api/openclaw/link
 *   - stats/links  → require session; guards that daUsername matches session (admins exempt)
 */

const express = require('express');
const jwt     = require('jsonwebtoken');
const axios   = require('axios');
const router  = express.Router();

const { getRedis } = require('../redis');
const { requireSession } = require('../auth/middleware');

const OPENCLAW_URL = process.env.OPENCLAW_URL || 'http://localhost:3004';
// SECURITY (VULN-R2-03): Use centralized config instead of hardcoded fallback
const config = require('../config');
const safeError = require('../utils/safeError');
const rateLimit = require('express-rate-limit');
const JWT_SECRET = config.jwt.secret;

// SECURITY (R3 M-11): Rate limit on link endpoint — prevents token brute-force
const linkLimiter = rateLimit({
  windowMs: 60 * 1000,
  max: 10,
  keyGenerator: (req) => req.ip,
  message: { ok: false, error: 'Too many link attempts. Try again in 1 minute.' },
});

// ── Helper: ensure user can only access their own daUsername ──────────────────
function requireOwn(req, res, next) {
  const { daUsername } = req.params;
  if (req.user.daUsername === daUsername || req.user.role === 'admin') return next();
  return res.status(403).json({ ok: false, error: 'Forbidden' });
}

// ── GET /api/openclaw/status ───────────────────────────────────────────────────
// Combined endpoint used by the dashboard — returns stats + linked channels.
router.get('/status', requireSession, async (req, res) => {
  const { daUsername } = req.user;
  if (!daUsername) return res.status(400).json({ ok: false, error: 'No daUsername in session' });

  try {
    // ── Fetch stats from OpenClaw service ──
    let stats = null;
    try {
      const resp = await axios.get(
        `${OPENCLAW_URL}/api/openclaw/stats/${encodeURIComponent(daUsername)}`,
        { timeout: 5000 }
      );
      stats = resp.data.stats || resp.data;
    } catch (err) {
      if (err.code !== 'ECONNREFUSED') logger.warn(`openclaw/status stats error: ${err.message}`);
    }

    // ── Fetch linked channels from Redis ──
    const redis = getRedis();
    const members = await redis.smembers(`openclaw:links:${daUsername}`);
    const links = (await Promise.all(
      members.map(async member => {
        const [channel, channelUserId] = member.split(':');
        const raw = await redis.get(`openclaw:user:${channel}:${channelUserId}`);
        if (!raw) return null;
        const data = JSON.parse(raw);
        return { channel, channelUserId, linkedAt: data.linkedAt, displayName: data.displayName || channelUserId };
      })
    )).filter(Boolean);

    res.json({ ok: true, stats, links });
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── GET /api/openclaw/link-token ──────────────────────────────────────────────
// Logged-in user requests a one-time token to link a messaging channel account.
// Token is valid for 1 hour and encodes daUsername + whmcsClientId + plan.
router.get('/link-token', requireSession, (req, res) => {
  const { daUsername, whmcsClientId, plan } = req.user;

  if (!daUsername) {
    return res.status(400).json({ ok: false, error: 'No daUsername in session' });
  }

  const token = jwt.sign(
    { daUsername, whmcsClientId, plan: plan || 'standard', purpose: 'openclaw-link' },
    JWT_SECRET,
    { expiresIn: '1h' }
  );

  res.json({ ok: true, token, expiresIn: 3600, hint: 'Send /link <token> to the bot' });
});

// ── POST /api/openclaw/link ───────────────────────────────────────────────────
// Called by OpenClaw's message router when a user sends /link <token>.
// Validates the JWT, then stores the channel→user mapping in Redis.
// No session required — the link token IS the credential.
router.post('/link', linkLimiter, async (req, res) => {
  const { token, channel, channelUserId, displayName } = req.body;

  if (!token || !channel || !channelUserId) {
    return res.status(400).json({ ok: false, error: 'token, channel, channelUserId required' });
  }

  let payload;
  try {
    payload = jwt.verify(token, JWT_SECRET);
  } catch (err) {
    return res.status(401).json({ ok: false, error: 'Invalid or expired link token' });
  }

  if (payload.purpose !== 'openclaw-link') {
    return res.status(401).json({ ok: false, error: 'Token not valid for channel linking' });
  }

  const { daUsername, whmcsClientId, plan } = payload;

  try {
    const redis = getRedis();
    const redisKey = `openclaw:user:${channel}:${channelUserId}`;

    const userRecord = {
      daUsername,
      whmcsClientId,
      plan: plan || 'standard',
      displayName: displayName || channelUserId,
      linkedAt: new Date().toISOString(),
    };

    await redis.set(redisKey, JSON.stringify(userRecord));

    // Index: per daUsername so we can list all linked channels
    const indexKey = `openclaw:links:${daUsername}`;
    await redis.sadd(indexKey, `${channel}:${channelUserId}`);

    return res.json({ ok: true, daUsername, plan: plan || 'standard', message: 'Channel linked successfully' });
  } catch (err) {
    return res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── GET /api/openclaw/stats/:daUsername ──────────────────────────────────────
// Returns messaging stats for a user — proxied from OpenClaw service.
router.get('/stats/:daUsername', requireSession, requireOwn, async (req, res) => {
  const { daUsername } = req.params;
  try {
    const resp = await axios.get(`${OPENCLAW_URL}/api/openclaw/stats/${encodeURIComponent(daUsername)}`, {
      timeout: 5000,
    });
    res.json(resp.data);
  } catch (err) {
    if (err.code === 'ECONNREFUSED') {
      return res.json({ ok: true, stats: null, links: [], note: 'OpenClaw service offline' });
    }
    res.status(502).json({ ok: false, error: safeError(err) });
  }
});

// ── GET /api/openclaw/links/:daUsername ──────────────────────────────────────
// Returns all linked channel accounts for a user (read from Redis directly).
router.get('/links/:daUsername', requireSession, requireOwn, async (req, res) => {
  const { daUsername } = req.params;
  try {
    const redis = getRedis();
    const members = await redis.smembers(`openclaw:links:${daUsername}`);

    const links = await Promise.all(
      members.map(async member => {
        const [channel, channelUserId] = member.split(':');
        const raw = await redis.get(`openclaw:user:${channel}:${channelUserId}`);
        if (!raw) return null;
        const data = JSON.parse(raw);
        return { channel, channelUserId, linkedAt: data.linkedAt, displayName: data.displayName || channelUserId };
      })
    );

    res.json({ ok: true, links: links.filter(Boolean) });
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── POST /api/openclaw/send ───────────────────────────────────────────────────
// Admin: push a message to a specific channel user.
// Body: { channel, channelUserId, text }
router.post('/send', requireSession, async (req, res) => {
  if (req.user.role !== 'admin') {
    return res.status(403).json({ ok: false, error: 'Admin only' });
  }

  const { channel, channelUserId, text } = req.body;
  if (!channel || !channelUserId || !text) {
    return res.status(400).json({ ok: false, error: 'channel, channelUserId, text required' });
  }

  try {
    const resp = await axios.post(
      `${OPENCLAW_URL}/api/openclaw/send`,
      { channel, channelUserId, text },
      { timeout: 10000 }
    );
    res.json(resp.data);
  } catch (err) {
    res.status(502).json({ ok: false, error: safeError(err) });
  }
});

module.exports = router;
