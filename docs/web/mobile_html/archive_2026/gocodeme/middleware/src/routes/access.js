'use strict';

/**
 * Access Management Routes (called by WHMCS provisioning module)
 *
 * POST /api/access/activate    - Enable a customer's GoCodeMe access
 * POST /api/access/suspend     - Suspend access (payment failed)
 * POST /api/access/unsuspend   - Restore access (payment cleared)
 * POST /api/access/terminate   - Permanently revoke access
 *
 * POST /api/sso/generate       - Generate a signed SSO token for WHMCS → IDE redirect
 *
 * All routes require X-WHMCS-Secret header matching WHMCS_WEBHOOK_SECRET env var.
 */

const express = require('express');
const router  = express.Router();
const jwt     = require('jsonwebtoken');
const config  = require('../config');
const logger  = require('../logger');
const { getRedis } = require('../redis');
const { setLimit } = require('../tokens/tokenCounter');
const crypto = require('crypto');
const safeError = require('../utils/safeError');

function requireWhmcsSecret(req, res, next) {
  const secret = req.headers['x-whmcs-secret'];
  const expected = config.whmcsWebhookSecret;
  // Use timing-safe comparison to prevent timing attacks (VULN-13)
  if (!secret || !expected || secret.length !== expected.length ||
      !crypto.timingSafeEqual(Buffer.from(secret), Buffer.from(expected))) {
    return res.status(401).json({ ok: false, error: 'Unauthorized' });
  }
  next();
}

// ── Activate ───────────────────────────────────────────────────────────────
router.post('/activate', requireWhmcsSecret, async (req, res) => {
  try {
    const { whmcsClientId, daUsername, plan } = req.body;
    if (!whmcsClientId || !daUsername) {
      return res.status(400).json({ ok: false, error: 'whmcsClientId and daUsername required' });
    }
    // SECURITY (VULN-17): Validate whmcsClientId is a positive integer
    const clientIdNum = parseInt(whmcsClientId, 10);
    if (!Number.isInteger(clientIdNum) || clientIdNum <= 0 || String(clientIdNum) !== String(whmcsClientId)) {
      return res.status(400).json({ ok: false, error: 'Invalid whmcsClientId format' });
    }

    const redis = getRedis();
    await redis.set(`access:${whmcsClientId}`, 'active');
    await redis.set(`da_username:${whmcsClientId}`, daUsername);
    await redis.set(`client_id_by_da:${daUsername}`, String(whmcsClientId), 'EX', 86400 * 30);
    if (plan) await setLimit(whmcsClientId, plan);

    logger.info(`access: activated client ${whmcsClientId} (DA: ${daUsername})`);
    res.json({ ok: true });
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── Suspend ────────────────────────────────────────────────────────────────
router.post('/suspend', requireWhmcsSecret, async (req, res) => {
  try {
    const { whmcsClientId } = req.body;
    if (!whmcsClientId) return res.status(400).json({ ok: false, error: 'whmcsClientId required' });

    await getRedis().set(`access:${whmcsClientId}`, 'suspended');
    logger.info(`access: suspended client ${whmcsClientId}`);
    res.json({ ok: true });
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── Unsuspend ──────────────────────────────────────────────────────────────
router.post('/unsuspend', requireWhmcsSecret, async (req, res) => {
  try {
    const { whmcsClientId } = req.body;
    if (!whmcsClientId) return res.status(400).json({ ok: false, error: 'whmcsClientId required' });

    await getRedis().set(`access:${whmcsClientId}`, 'active');
    logger.info(`access: unsuspended client ${whmcsClientId}`);
    res.json({ ok: true });
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── Terminate ──────────────────────────────────────────────────────────────
router.post('/terminate', requireWhmcsSecret, async (req, res) => {
  try {
    const { whmcsClientId } = req.body;
    if (!whmcsClientId) return res.status(400).json({ ok: false, error: 'whmcsClientId required' });

    await getRedis().set(`access:${whmcsClientId}`, 'terminated');
    logger.info(`access: terminated client ${whmcsClientId}`);
    res.json({ ok: true });
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── SSO token generation (called by WHMCS before redirecting to IDE) ───────
router.post('/sso/generate', requireWhmcsSecret, async (req, res) => {
  try {
    const { whmcsClientId, plan } = req.body;
    if (!whmcsClientId) return res.status(400).json({ ok: false, error: 'whmcsClientId required' });

    // Verify access is active
    const status = await getRedis().get(`access:${whmcsClientId}`);
    if (status !== 'active') {
      return res.status(402).json({ ok: false, error: 'No active GoCodeMe subscription' });
    }

    const daUsername = await getRedis().get(`da_username:${whmcsClientId}`);
    if (!daUsername) {
      return res.status(404).json({ ok: false, error: 'DirectAdmin username not found' });
    }

    const token = jwt.sign(
      { whmcsClientId, daUsername, plan: plan || 'professional' },
      config.jwt.secret,
      { expiresIn: '1h' }  // short-lived — for the SSO redirect only
    );

    res.json({ ok: true, token });
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

module.exports = router;
