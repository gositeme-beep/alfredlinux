'use strict';

/**
 * Token Usage Routes
 *
 * GET  /api/tokens/usage        - Current customer's token usage
 * POST /api/tokens/report       - (internal) Report tokens used after a Claude call
 *
 * WHMCS webhook routes (called by WHMCS provisioning module, not the customer):
 * POST /api/tokens/provision    - Set token limit on purchase/upgrade
 * POST /api/tokens/reset        - Reset counter on renewal
 * POST /api/tokens/topup        - Add bonus tokens from a top-up purchase
 */

const express = require('express');
const router = express.Router();
const tc = require('../tokens/tokenCounter');
const { requireSession } = require('../auth/middleware');
const logger = require('../logger');
const crypto = require('crypto');
const { requireWhmcsSecret } = require('../auth/whmcsSecret');
const config = require('../config');

// Timing-safe inline check for dual-auth routes (WHMCS secret OR Bearer token)
function isValidWhmcsSecret(secret) {
  const expected = config.whmcsWebhookSecret;
  if (!secret || !expected || secret.length !== expected.length) return false;
  return crypto.timingSafeEqual(Buffer.from(secret), Buffer.from(expected));
}

// ── Customer: get own usage ────────────────────────────────────────────────
// Also accepts X-WHMCS-Secret + ?whmcsClientId= for WHMCS UsageMetrics hook
router.get('/usage', async (req, res) => {
  try {
    const whmcsSecret = req.headers['x-whmcs-secret'];
    const isWhmcsCaller = whmcsSecret && isValidWhmcsSecret(whmcsSecret);

    let clientId;
    if (isWhmcsCaller) {
      // Called by WHMCS provisioning module (UsageMetrics / ClientArea)
      clientId = req.query.whmcsClientId;
      if (!clientId) {
        return res.status(400).json({ ok: false, error: 'whmcsClientId query param required' });
      }
    } else {
      // Called by logged-in customer via Bearer session token
      const authHeader = req.headers['authorization'] || '';
      const token = authHeader.replace(/^Bearer\s+/i, '');
      if (!token) {
        return res.status(401).json({ ok: false, error: 'Authorization required' });
      }
      try {
        const { verifySessionToken } = require('../auth/sso');
        const session = verifySessionToken(token);
        clientId = session.whmcsClientId;
        // Resolve canonical clientId from DA username (JWT may have stale value)
        if (session.daUsername) {
          try {
            const { getRedis } = require('../redis');
            const resolved = await getRedis().get(`client_id_by_da:${session.daUsername}`);
            if (resolved) clientId = resolved;
          } catch (_) {}
        }
      } catch {
        return res.status(401).json({ ok: false, error: 'Invalid or expired session' });
      }
    }

    const usage = await tc.getUsage(clientId);
    // Include optimization savings if available
    let savings = 0;
    try {
      const { getRedis } = require('../redis');
      const redis = getRedis();
      const raw = await redis.get(`savings:${clientId}`);
      if (raw) savings = parseFloat(raw) || 0;
    } catch (_) {}
    // Include overage details
    const overage = await tc.getOverageDetails(clientId);
    res.json({ ok: true, usage, savings: Math.round(savings * 100) / 100, overage });
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── Internal: report token usage after Claude call ─────────────────────────
router.post('/report', requireSession, async (req, res) => {
  try {
    const { inputTokens = 0, outputTokens = 0, model = 'unknown' } = req.body;
    const result = await tc.addUsage(req.user.whmcsClientId, inputTokens, outputTokens);
    res.json({ ok: true, ...result });

    // Record detailed usage for dashboard charts/model breakdown
    const { recordUsage } = require('../tokens/usageTracker');
    recordUsage(req.user.whmcsClientId, {
      model, inputTokens, outputTokens, daUsername: req.user.daUsername,
    }).catch(e => logger.error(`tokens/report recordUsage: ${e.message}`));

    // Fire billing alerts (fire-and-forget — never block the response)
    if (result.limit > 0) {
      const { checkAlerts } = require('../billing/alerts');
      checkAlerts({
        whmcsClientId: req.user.whmcsClientId,
        daUsername:    req.user.daUsername,
        used:          result.used,
        limit:         result.limit,
        percentUsed:   result.percentUsed,
      }).catch(e => logger.error(`billing alert error: ${e.message}`));
    }
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── WHMCS webhook: provision / upgrade ────────────────────────────────────
router.post('/provision', requireWhmcsSecret, async (req, res) => {
  try {
    const { whmcsClientId, plan } = req.body;
    if (!whmcsClientId || !plan) {
      return res.status(400).json({ ok: false, error: 'whmcsClientId and plan required' });
    }
    const limit = await tc.setLimit(whmcsClientId, plan);
    logger.info(`tokens/provision: client ${whmcsClientId} set to ${plan} (${limit} tokens)`);

    // Seed starter credits for new users ($2 USD to try AI immediately)
    try {
      const budget = require('../tokens/tokenBudget');
      const currentCredits = await budget.getCredits(whmcsClientId);
      if (currentCredits === 0) {
        await budget.addCredit(whmcsClientId, 2);
        logger.info(`tokens/provision: seeded $2 starter credits for new user ${whmcsClientId}`);
      }
    } catch (e) { logger.error(`tokens/provision: starter credit error: ${e.message}`); }

    // Trigger welcome email series for new users (fire-and-forget)
    try {
      const { startWelcomeSeries } = require('../billing/emailAutomation');
      const { getClient } = require('../billing/whmcs');
      const client = await getClient(whmcsClientId);
      startWelcomeSeries(whmcsClientId, client.firstname, limit).catch(() => {});
    } catch {}

    res.json({ ok: true, limit });
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── WHMCS webhook: renewal reset ──────────────────────────────────────────
router.post('/reset', requireWhmcsSecret, async (req, res) => {
  try {
    const { whmcsClientId } = req.body;
    if (!whmcsClientId) return res.status(400).json({ ok: false, error: 'whmcsClientId required' });
    await tc.resetUsage(whmcsClientId);
    // Also reset billing alert flags so fresh alerts fire next billing cycle
    const { resetAlerts } = require('../billing/alerts');
    resetAlerts(whmcsClientId).catch(e => logger.error(`billing reset: ${e.message}`));
    // Reset detailed usage tracking data
    const { resetDetailedUsage } = require('../tokens/usageTracker');
    resetDetailedUsage(whmcsClientId).catch(e => logger.error(`usage-tracker reset: ${e.message}`));
    res.json({ ok: true });
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── WHMCS webhook: top-up credit ──────────────────────────────────────────
router.post('/topup', requireWhmcsSecret, async (req, res) => {
  try {
    const { whmcsClientId, bonusTokens } = req.body;
    if (!whmcsClientId || !bonusTokens) {
      return res.status(400).json({ ok: false, error: 'whmcsClientId and bonusTokens required' });
    }
    const newLimit = await tc.addTopUp(whmcsClientId, parseInt(bonusTokens, 10));
    res.json({ ok: true, newLimit });
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── Model mode management ─────────────────────────────────────────────────
// GET  /api/tokens/mode         - Get user's current model mode + available modes
// POST /api/tokens/mode         - Set user's model mode (auto/sonnet/opus/haiku/turbo)
const { getUserMode, setUserMode, getAvailableModes } = require('../billing/modelRouter');
const { VALID_SPEND_MODES, getSpendMode, setSpendMode } = require('../billing/spendMode');
const budget = require('../tokens/tokenBudget');
const safeError = require('../utils/safeError');

router.get('/mode', requireSession, async (req, res) => {
  try {
    const whmcsClientId = req.user?.whmcsClientId;
    if (!whmcsClientId) return res.status(400).json({ ok: false, error: 'No client ID' });

    const mode = await getUserMode(whmcsClientId);
    const modes = getAvailableModes();
    res.json({ ok: true, mode, modes });
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

router.post('/mode', requireSession, async (req, res) => {
  try {
    const whmcsClientId = req.user?.whmcsClientId;
    if (!whmcsClientId) return res.status(400).json({ ok: false, error: 'No client ID' });

    const { mode } = req.body;
    if (!mode) return res.status(400).json({ ok: false, error: 'mode required' });

    const newMode = await setUserMode(whmcsClientId, mode);
    logger.info(`model-mode: client ${whmcsClientId} set mode → ${newMode}`);
    res.json({ ok: true, mode: newMode });
  } catch (err) {
    res.status(400).json({ ok: false, error: safeError(err) });
  }
});

// Also allow setting via WHMCS webhook (admin override)
router.post('/mode/admin', requireWhmcsSecret, async (req, res) => {
  try {
    const { whmcsClientId, mode } = req.body;
    if (!whmcsClientId || !mode) return res.status(400).json({ ok: false, error: 'whmcsClientId and mode required' });

    const newMode = await setUserMode(whmcsClientId, mode);
    logger.info(`model-mode [admin]: client ${whmcsClientId} set mode → ${newMode}`);
    res.json({ ok: true, mode: newMode });
  } catch (err) {
    res.status(400).json({ ok: false, error: safeError(err) });
  }
});

// ── Spend mode management (user-controlled x-you-can-afford) ─────────────
// GET  /api/tokens/spend-mode
// POST /api/tokens/spend-mode { mode: economy|balanced|power }
router.get('/spend-mode', requireSession, async (req, res) => {
  try {
    const whmcsClientId = req.user?.whmcsClientId;
    if (!whmcsClientId) return res.status(400).json({ ok: false, error: 'No client ID' });

    const mode = await getSpendMode(whmcsClientId);
    const capDetails = await budget.getUserDailyUsdCapDetails(whmcsClientId);

    res.json({
      ok: true,
      mode,
      validModes: VALID_SPEND_MODES,
      cap: capDetails,
    });
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

router.post('/spend-mode', requireSession, async (req, res) => {
  try {
    const whmcsClientId = req.user?.whmcsClientId;
    if (!whmcsClientId) return res.status(400).json({ ok: false, error: 'No client ID' });

    const mode = String(req.body?.mode || '').toLowerCase().trim();
    if (!VALID_SPEND_MODES.includes(mode)) {
      return res.status(400).json({ ok: false, error: 'Invalid spend mode. Valid: economy, balanced, power' });
    }

    const newMode = await setSpendMode(whmcsClientId, mode);
    const capDetails = await budget.getUserDailyUsdCapDetails(whmcsClientId);

    logger.info(`spend-mode: client ${whmcsClientId} set spend mode -> ${newMode}`);
    res.json({ ok: true, mode: newMode, cap: capDetails });
  } catch (err) {
    res.status(400).json({ ok: false, error: safeError(err) });
  }
});

module.exports = router;
