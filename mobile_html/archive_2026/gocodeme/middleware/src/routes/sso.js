'use strict';

/**
 * SSO Routes
 *
 * POST /api/sso/login     - Validate WHMCS SSO token, return GoCodeMe session token
 * GET  /api/sso/me        - Return current session info (for frontend hydration)
 * POST /api/sso/generate  - Generate a signed SSO token for WHMCS → IDE redirect
 *                           (alias for /api/access/sso/generate — WHMCS module calls this path)
 */

const express = require('express');
const router = express.Router();
const jwt = require('jsonwebtoken');
const { ssoLogin } = require('../auth/sso');
const { requireSession } = require('../auth/middleware');
const config = require('../config');
const logger = require('../logger');
const { getRedis } = require('../redis');
const { requireWhmcsSecret } = require('../auth/whmcsSecret');
const safeError = require('../utils/safeError');
const rateLimit = require('express-rate-limit');

// SECURITY (R3 M-07): Strict rate limit on auth endpoint — prevents brute-force
const ssoLoginLimiter = rateLimit({
  windowMs: 60 * 1000,
  max: 10,
  keyGenerator: (req) => req.ip,
  message: { ok: false, error: 'Too many login attempts. Try again in 1 minute.' },
});

// ── SSO Login ──────────────────────────────────────────────────────────────
router.post('/login', ssoLoginLimiter, async (req, res) => {
  try {
    const { token } = req.body;
    if (!token) {
      return res.status(400).json({ ok: false, error: 'WHMCS SSO token required' });
    }

    const result = await ssoLogin(token);
    res.json({ ok: true, ...result });
  } catch (err) {
    logger.warn(`SSO login failed: ${err.message}`);
    const status = err.message.includes('subscription') ? 402 : 401;
    res.status(status).json({ ok: false, error: safeError(err) });
  }
});

// ── Who am I ───────────────────────────────────────────────────────────────
router.get('/me', requireSession, (req, res) => {
  res.json({ ok: true, user: req.user });
});

// ── Exchange (POST-only SSO → JSON session token) ─────────────────────────
// POST /api/sso/exchange   body: { token: "<short_jwt>" }
//
// SECURITY (R2-07): POST-only — prevents SSO token leaking in URL,
// browser history, Referer headers, and server access logs.
//
// Accepts the short-lived JWT issued by /api/sso/generate and returns a full
// session token.  The short JWT already contains whmcsClientId + daUsername +
// plan so we just re-issue it with the standard session expiry.
router.post('/exchange', async (req, res) => {
  try {
    const raw = req.body?.token;
    if (!raw) return res.status(400).json({ ok: false, error: 'token required (POST body only)' });

    // Verify the incoming JWT (same secret — from /api/sso/generate)
    let payload;
    try {
      payload = jwt.verify(raw, config.jwt.secret);
    } catch (err) {
      return res.status(401).json({ ok: false, error: 'Invalid or expired token' });
    }

    const { whmcsClientId, daUsername, plan } = payload;

    // Verify access is still active
    const status = await getRedis().get(`access:${whmcsClientId}`);
    if (status && status !== 'active') {
      return res.status(402).json({ ok: false, error: `Account ${status}` });
    }

    // Re-issue as a full session token with standard expiry
    const sessionToken = jwt.sign(
      { whmcsClientId, daUsername, plan: plan || 'professional' },
      config.jwt.secret,
      { expiresIn: config.jwt.expiresIn }
    );

    logger.info(`sso/exchange: refreshed session for WHMCS client ${whmcsClientId}`);
    res.json({ ok: true, token: sessionToken, daUsername, plan });
  } catch (err) {
    logger.error(`sso/exchange error: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── Generate SSO token (WHMCS → IDE redirect) ─────────────────────────────
// WHMCS module calls POST /api/sso/generate (not /api/access/sso/generate)
// This is the authoritative implementation; access.js /sso/generate is kept
// for backwards compatibility with any direct callers.
router.post('/generate', requireWhmcsSecret, async (req, res) => {
  try {
    const { whmcsClientId, plan } = req.body;
    if (!whmcsClientId) {
      return res.status(400).json({ ok: false, error: 'whmcsClientId required' });
    }

    // Verify subscription is active
    const status = await getRedis().get(`access:${whmcsClientId}`);
    if (status !== 'active') {
      return res.status(402).json({ ok: false, error: 'No active GoCodeMe subscription' });
    }

    const daUsername = await getRedis().get(`da_username:${whmcsClientId}`);
    if (!daUsername) {
      return res.status(404).json({ ok: false, error: 'DirectAdmin username not found' });
    }

    // Short-lived token — only for the SSO redirect handshake
    const token = jwt.sign(
      { whmcsClientId, daUsername, plan: plan || 'professional' },
      config.jwt.secret,
      { expiresIn: '1h' }
    );

    logger.info(`sso/generate: issued token for WHMCS client ${whmcsClientId} (${plan})`);
    res.json({ ok: true, token });
  } catch (err) {
    logger.error(`sso/generate error: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── Billing Token Exchange ─────────────────────────────────────────────────
// POST /api/sso/billing-exchange   body: { token: "<raw hex from billing portal>" }
//
// The billing portal (pay/api/service-api.php) generates a random hex token
// and stores its SHA-256 hash in the `sso_tokens` table in MySQL.
// This route validates that token, marks it used, and returns a proper JWT.
const billingExchangeLimiter = rateLimit({
  windowMs: 60 * 1000,
  max: 10,
  keyGenerator: (req) => req.ip,
  message: { ok: false, error: 'Too many attempts. Try again in 1 minute.' },
});

router.post('/billing-exchange', billingExchangeLimiter, async (req, res) => {
  let conn;
  try {
    const rawToken = (req.body?.token || '').trim();
    if (!rawToken) {
      return res.status(400).json({ ok: false, error: 'Token required' });
    }

    const crypto = require('crypto');
    const tokenHash = crypto.createHash('sha256').update(rawToken).digest('hex');

    // Connect to billing DB
    const mysql = require('mysql2/promise');
    conn = await mysql.createConnection({
      host: process.env.GOSITEME_DB_HOST || 'localhost',
      user: process.env.GOSITEME_DB_USER || 'gositeme_whmcs',
      password: process.env.GOSITEME_DB_PASS || '',
      database: process.env.GOSITEME_BILLING_DB || 'gositeme_whmcs',
    });

    // Validate token
    const [rows] = await conn.execute(
      'SELECT client_id, email, name, expires_at, used FROM sso_tokens WHERE token = ? LIMIT 1',
      [tokenHash]
    );

    if (!rows.length) {
      return res.status(401).json({ ok: false, error: 'Invalid token' });
    }

    const row = rows[0];
    if (row.used) {
      return res.status(401).json({ ok: false, error: 'Token already used' });
    }
    if (new Date(row.expires_at) < new Date()) {
      return res.status(401).json({ ok: false, error: 'Token expired' });
    }

    // Mark token as used
    await conn.execute('UPDATE sso_tokens SET used = 1 WHERE token = ?', [tokenHash]);

    // Issue a proper JWT session token
    const whmcsClientId = row.client_id;
    const plan = 'enterprise'; // Default — could look up from services table

    // Try to resolve DA username
    let daUsername = 'gositeme'; // fallback
    try {
      const { getDaUsernameByWhmcsId } = require('../directadmin/userLookup');
      daUsername = await getDaUsernameByWhmcsId(whmcsClientId);
    } catch (e) {
      logger.warn(`billing-exchange: could not resolve DA username for client ${whmcsClientId}: ${e.message}`);
    }

    const sessionToken = jwt.sign(
      { whmcsClientId, daUsername, plan },
      config.jwt.secret,
      { expiresIn: config.jwt.expiresIn }
    );

    logger.info(`sso/billing-exchange: issued JWT for billing client ${whmcsClientId} (${row.email})`);
    res.json({ ok: true, token: sessionToken, daUsername, plan, email: row.email, name: row.name });
  } catch (err) {
    logger.error(`sso/billing-exchange error: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  } finally {
    if (conn) await conn.end().catch(() => {});
  }
});

module.exports = router;
