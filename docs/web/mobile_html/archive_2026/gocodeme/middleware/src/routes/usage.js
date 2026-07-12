'use strict';

/**
 * Usage Dashboard Routes — /api/usage/*
 *
 * Customer-facing API endpoints for the usage dashboard.
 * Similar to GitHub Copilot's billing page — shows:
 *   - Current billing period usage summary
 *   - Cost breakdown by model (with per-1M token rates)
 *   - Daily usage chart data
 *   - Recent request history
 *   - Plan limits vs actual usage
 *   - Overage cost if applicable
 *   - Available model pricing
 *
 * All endpoints require session auth (JWT Bearer token).
 */

const express = require('express');
const router  = express.Router();

const tc = require('../tokens/tokenCounter');
const { requireSession } = require('../auth/middleware');
const { getDailyUsage, getModelBreakdown, getTotalCost, getRequestHistory } = require('../tokens/usageTracker');
const { getAlertState } = require('../billing/alerts');
const { getAllModelPricing, calculateCost } = require('../billing/pricing');
const { getClientProducts } = require('../billing/whmcs');
const { getUserMode, setUserMode, getAvailableModels, getAvailableModes, MODELS, getTokenMultiplierAsync } = require('../billing/modelRouter');
const { SPEND_MODE_MULTIPLIERS, VALID_SPEND_MODES, getSpendMode } = require('../billing/spendMode');
const { buildSmartOffer } = require('../billing/smartOffers');
const { trackOfferEvent, getOfferTelemetry, VALID_EVENTS } = require('../billing/offerTelemetry');
const budget = require('../tokens/tokenBudget');
const anomaly = require('../billing/anomalyDetector');
const safeError = require('../utils/safeError');
const logger = require('../logger');
const config = require('../config');

const UPGRADE_URL = process.env.AI_UPGRADE_URL || 'https://gositeme.com/pricing.php';
const TOPUP_URL = process.env.AI_TOPUP_URL || 'https://gositeme.com/pricing.php#topup';

// ── Resolve true WHMCS client ID from DA username ─────────────────────────
// The JWT may contain a stale/incorrect whmcsClientId (e.g. 1 instead of 33).
// The canonical mapping is client_id_by_da:<daUsername> → clientId in Redis,
// populated by WHMCS provisioning. Fall back to JWT value if unavailable.
async function resolveClientId(req) {
  const jwtClientId  = req.user?.whmcsClientId || req.user?.clientId;
  const daUsername   = req.user?.daUsername;
  if (!daUsername) return jwtClientId;

  try {
    const { getRedis } = require('../redis');
    const redis = getRedis();
    const cached = await redis.get(`client_id_by_da:${daUsername}`);
    if (cached) return cached;
  } catch (err) {
    logger.warn(`resolveClientId: Redis lookup failed for ${daUsername}: ${err.message}`);
  }
  return jwtClientId;
}

// ── Nudge/upgrade prompt builder ─────────────────────────────────────────
// Generates smart upgrade and annual billing prompts based on usage patterns.
const UPGRADE_PATH = {
  free:         { next: 'Builder',       price: 15, annualPrice: 144, monthlyTokens: 300000 },
  builder:      { next: 'Professional',  price: 29, annualPrice: 288, monthlyTokens: 600000 },
  professional: { next: 'Studio',        price: 59, annualPrice: 576, monthlyTokens: 1500000 },
  studio:       { next: 'Business',      price: 99, annualPrice: 960, monthlyTokens: 3000000 },
  business:     { next: 'Enterprise',    price: 199, annualPrice: 1899, monthlyTokens: 5000000 },
  enterprise:   null,
};

function buildNudges(planName, usage) {
  const nudges = [];
  const plan = planName.toLowerCase();
  const upgrade = UPGRADE_PATH[plan];

  // Upgrade nudge at 80%+ usage
  if (upgrade && usage.percentUsed >= 80) {
    const annualMonthly = Math.round(upgrade.annualPrice / 12);
    const monthlySave = upgrade.price - annualMonthly;
    nudges.push({
      type: 'upgrade',
      priority: usage.percentUsed >= 95 ? 'high' : 'medium',
      title: `Upgrade to ${upgrade.next}`,
      message: `You've used ${usage.percentUsed}% of your ${planName} tokens. ${upgrade.next} gives you ${(upgrade.monthlyTokens / 1000).toFixed(0)}K tokens/month.`,
      cta: `Upgrade to ${upgrade.next} — $${upgrade.price}/mo`,
      url: UPGRADE_URL,
    });
    // Annual billing nudge
    if (monthlySave > 0) {
      nudges.push({
        type: 'annual',
        priority: 'low',
        title: 'Save with annual billing',
        message: `Switch to annual billing and pay $${annualMonthly}/mo instead of $${upgrade.price}/mo — save $${monthlySave * 12}/year.`,
        cta: `Save $${monthlySave * 12}/year`,
        url: UPGRADE_URL,
      });
    }
  }

  // Token pack nudge at 90%+
  if (usage.percentUsed >= 90) {
    nudges.push({
      type: 'topup',
      priority: 'high',
      title: 'Buy a token pack',
      message: 'Running low? Add tokens instantly with a one-time pack.',
      cta: 'Top Up Tokens — from $5',
      url: TOPUP_URL,
    });
  }

  // Free plan CTA
  if (plan === 'free' && usage.percentUsed >= 50) {
    nudges.push({
      type: 'upgrade',
      priority: 'high',
      title: 'Upgrade to Builder',
      message: `You've used ${usage.percentUsed}% of your free tokens. Builder gives 6x more tokens at $15/mo — or $12/mo billed annually.`,
      cta: 'Start Building — $15/mo',
      url: UPGRADE_URL,
    });
  }

  return nudges;
}

function buildLimitCard(kind, details = {}) {
  if (kind === 'daily_usd_cap') {
    return {
      title: 'Daily AI budget reached',
      subtitle: `Used $${details.spentUsd} of $${details.capUsd} today. Resets at midnight UTC.`,
      cta_label: 'Upgrade Plan',
      cta_url: UPGRADE_URL,
    };
  }

  if (kind === 'daily_token_cap') {
    return {
      title: 'Daily token limit reached',
      subtitle: `Used ${Number(details.usedTokens || 0).toLocaleString()} of ${Number(details.capTokens || 0).toLocaleString()} tokens today.`,
      cta_label: 'Upgrade Plan',
      cta_url: UPGRADE_URL,
    };
  }

  if (kind === 'monthly_token_cap') {
    return {
      title: 'Monthly token limit reached',
      subtitle: 'Add tokens now or upgrade your plan to keep coding.',
      cta_label: 'Add Tokens',
      cta_url: TOPUP_URL,
    };
  }

  return {
    title: 'Limit reached',
    subtitle: 'This request hit a usage limit.',
    cta_label: 'Upgrade Plan',
    cta_url: UPGRADE_URL,
  };
}

// ── GET /api/usage/prelimit-warnings ─────────────────────────────────────
// Early warning system at 70/85/95% with actionable suggestions.
router.get('/prelimit-warnings', requireSession, async (req, res) => {
  try {
    const clientId = await resolveClientId(req);
    const usage = await tc.getUsage(clientId);
    const spendMode = await getSpendMode(clientId);
    const summary = await budget.getUserBudgetSummary(clientId, usage.limit);

    const warnings = [];
    const pct = usage.percentUsed || 0;

    if (pct >= 70) {
      warnings.push({
        stage: 70,
        severity: pct >= 95 ? 'high' : pct >= 85 ? 'medium' : 'low',
        title: 'Usage is getting high',
        message: `You are at ${pct}% of your monthly tokens.`,
        suggestions: [
          'Switch to economy or balanced spend mode for routine tasks.',
          'Use shorter prompts and start new conversations for large projects.',
        ],
      });
    }

    if (pct >= 85) {
      warnings.push({
        stage: 85,
        severity: 'medium',
        title: 'Pre-limit warning',
        message: 'You may hit your monthly cap soon.',
        suggestions: [
          'Top up tokens to avoid interruption.',
          'Upgrade your plan if you expect continued heavy usage.',
        ],
      });
    }

    if (pct >= 95) {
      warnings.push({
        stage: 95,
        severity: 'high',
        title: 'Critical usage warning',
        message: 'You are very close to your monthly limit.',
        suggestions: [
          'Add tokens now to avoid hard stops.',
          'Switch to economy mode temporarily to stretch remaining capacity.',
        ],
      });
    }

    const dailyUsd = summary.userDailyUsd || {};
    if (dailyUsd.cap !== 'unlimited' && dailyUsd.cap > 0) {
      const dailyPct = Math.round((dailyUsd.spent / dailyUsd.cap) * 100);
      if (dailyPct >= 80) {
        warnings.push({
          stage: 'daily-usd',
          severity: dailyPct >= 95 ? 'high' : 'medium',
          title: 'Daily budget warning',
          message: `You are at ${dailyPct}% of today's AI budget.`,
          suggestions: [
            'Move heavy tasks to tomorrow or increase spend mode if available.',
            'Upgrade plan for higher daily budget.',
          ],
        });
      }
    }

    res.json({
      ok: true,
      spend_mode: spendMode,
      percent_used: pct,
      warnings,
      upgrade_url: UPGRADE_URL,
      topup_url: TOPUP_URL,
    });
  } catch (err) {
    logger.error(`usage/prelimit-warnings: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── GET /api/usage/pricing-matrix ───────────────────────────────────────
// Returns spend-mode matrix for frontend pricing/upgrade UI.
router.get('/pricing-matrix', requireSession, async (_req, res) => {
  try {
    const tokenLimits = config.tokenLimits || {};

    const baseDailyCaps = {
      free: parseFloat(process.env.USER_DAILY_USD_CAP_FREE || process.env.USER_DAILY_USD_CAP || '10'),
      builder: parseFloat(process.env.USER_DAILY_USD_CAP_BUILDER || '20'),
      professional: parseFloat(process.env.USER_DAILY_USD_CAP_PROFESSIONAL || '40'),
      studio: parseFloat(process.env.USER_DAILY_USD_CAP_STUDIO || '80'),
      business: parseFloat(process.env.USER_DAILY_USD_CAP_BUSINESS || '150'),
      enterprise: parseFloat(process.env.USER_DAILY_USD_CAP_ENTERPRISE || '300'),
    };

    const planOrder = ['free', 'builder', 'professional', 'studio', 'business', 'enterprise'];
    const matrix = [];

    for (const plan of planOrder) {
      const monthlyTokens = tokenLimits[plan];
      if (!monthlyTokens && monthlyTokens !== 0) continue;

      const row = {
        plan,
        monthly_tokens: monthlyTokens,
        base_daily_usd_cap: baseDailyCaps[plan] || 0,
        modes: {},
      };

      for (const mode of VALID_SPEND_MODES) {
        const mult = SPEND_MODE_MULTIPLIERS[mode] || 1;
        row.modes[mode] = {
          multiplier: mult,
          daily_usd_cap: Math.round((row.base_daily_usd_cap * mult) * 100) / 100,
        };
      }

      matrix.push(row);
    }

    res.json({
      ok: true,
      upgrade_url: UPGRADE_URL,
      topup_url: TOPUP_URL,
      spend_modes: VALID_SPEND_MODES,
      matrix,
    });
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── GET /api/usage/limit-card ───────────────────────────────────────────
// Returns a preformatted card payload so clients can render consistent
// upgrade/topup messaging instead of raw JSON error dumps.
router.get('/limit-card', requireSession, async (req, res) => {
  try {
    const clientId = await resolveClientId(req);
    const usage = await tc.getUsage(clientId);
    const summary = await budget.getUserBudgetSummary(clientId, usage.limit);
    const spendMode = await getSpendMode(clientId);

    let kind = String(req.query.kind || '').trim();
    if (!kind) {
      if (!usage.withinLimit) kind = 'monthly_token_cap';
      else if (summary.daily?.cap > 0 && summary.daily?.used >= summary.daily?.cap) kind = 'daily_token_cap';
      else if (summary.userDailyUsd?.cap !== 'unlimited' && summary.userDailyUsd?.spent >= summary.userDailyUsd?.cap) kind = 'daily_usd_cap';
      else kind = 'none';
    }

    if (kind === 'none') {
      return res.json({ ok: true, limited: false, spend_mode: spendMode, card: null });
    }

    const card = buildLimitCard(kind, {
      spentUsd: summary.userDailyUsd?.spent,
      capUsd: summary.userDailyUsd?.cap,
      usedTokens: summary.daily?.used,
      capTokens: summary.daily?.cap,
    });

    const smart = await buildSmartOffer(clientId, kind, {
      spent_usd: summary.userDailyUsd?.spent,
      cap_usd: summary.userDailyUsd?.cap,
      used_tokens: summary.daily?.used,
      cap_tokens: summary.daily?.cap,
    });

    // Telemetry impression for A/B offer card effectiveness.
    trackOfferEvent({
      clientId,
      daUsername: req.user?.daUsername,
      event: 'shown',
      variant: smart.variant,
      limitKind: kind,
      source: 'usage_limit_card',
    }).catch(() => {});

    res.json({
      ok: true,
      limited: true,
      kind,
      spend_mode: spendMode,
      card: smart.card || card,
      variant: smart.variant,
      reset_at_utc: new Date(Date.UTC(new Date().getUTCFullYear(), new Date().getUTCMonth(), new Date().getUTCDate() + 1, 0, 0, 0)).toISOString(),
      upgrade_url: UPGRADE_URL,
      topup_url: TOPUP_URL,
    });
  } catch (err) {
    logger.error(`usage/limit-card: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── POST /api/usage/offer-event ─────────────────────────────────────────
// Frontend event hook for offer analytics (click-through and upgrade starts).
router.post('/offer-event', requireSession, async (req, res) => {
  try {
    const clientId = await resolveClientId(req);
    const event = String(req.body?.event || '').toLowerCase().trim();
    if (!VALID_EVENTS.has(event)) {
      return res.status(400).json({ ok: false, error: 'Invalid event. Use shown, clicked, or upgrade_started.' });
    }

    await trackOfferEvent({
      clientId,
      daUsername: req.user?.daUsername,
      event,
      variant: req.body?.variant,
      limitKind: req.body?.kind,
      source: req.body?.source || 'dashboard',
    });

    res.json({ ok: true });
  } catch (err) {
    logger.error(`usage/offer-event: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── GET /api/usage/offer-telemetry ──────────────────────────────────────
// User-scoped quick view of global offer conversion stats.
router.get('/offer-telemetry', requireSession, async (req, res) => {
  try {
    const days = parseInt(req.query.days || '7', 10);
    const telemetry = await getOfferTelemetry(days);
    res.json({ ok: true, ...telemetry });
  } catch (err) {
    logger.error(`usage/offer-telemetry: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── GET /api/usage/summary ────────────────────────────────────────────────
// Complete billing dashboard data in a single call.
// Returns everything the frontend needs to render the usage dashboard.
router.get('/summary', requireSession, async (req, res) => {
  try {
    const whmcsClientId = await resolveClientId(req);
    const { daUsername } = req.user;

    const [usage, alertState, totalCost, modelBreakdown, dailyUsage] = await Promise.all([
      tc.getUsage(whmcsClientId),
      getAlertState(whmcsClientId),
      getTotalCost(whmcsClientId),
      getModelBreakdown(whmcsClientId),
      getDailyUsage(whmcsClientId, 30),
    ]);

    // Calculate overage
    const overageTokens = Math.max(0, usage.used - usage.limit);
    const overageBlocks = Math.ceil(overageTokens / 100_000) || 0;
    const overageCost   = overageBlocks * parseFloat(process.env.OVERAGE_PRICE_USD || '4.99');

    // Get plan info
    let planName = 'Unknown';
    for (const [name, limit] of Object.entries(config.tokenLimits)) {
      if (limit === usage.limit) {
        planName = name.charAt(0).toUpperCase() + name.slice(1);
        break;
      }
    }

    // Build daily chart data (last 30 days, most recent first → reverse for chart)
    const chartData = dailyUsage.map(d => ({
      date:     d.date,
      tokens:   d.total.input + d.total.output,
      cost:     Math.round(d.total.cost * 100) / 100,
      requests: d.total.requests,
    })).reverse();

    res.json({
      ok: true,
      billing: {
        plan: planName,
        periodStart: getMonthStart(),
        periodEnd:   getMonthEnd(),
        tokenLimit:  usage.limit,
        tokensUsed:  usage.used,
        percentUsed: usage.percentUsed,
        withinLimit: usage.withinLimit,
        totalCost:   Math.round(totalCost * 1000000) / 1000000,
        overageTokens,
        overageCost: Math.round(overageCost * 100) / 100,
      },
      // ── Upgrade & annual billing nudges ─────────────────────────────
      nudges: buildNudges(planName, usage),
      models: modelBreakdown.map(m => ({
        model:       m.model,
        displayName: m.displayName,
        tier:        m.tier,
        inputTokens: Math.round(m.input),
        outputTokens: Math.round(m.output),
        totalTokens: Math.round(m.input + m.output),
        cost:        Math.round(m.cost * 1000000) / 1000000,
        requests:    m.requests,
        inputPer1M:  m.inputPer1M,
        outputPer1M: m.outputPer1M,
      })),
      chart: chartData,
      alerts: alertState,
    });
  } catch (err) {
    logger.error(`usage/summary: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── GET /api/usage/daily ──────────────────────────────────────────────────
// Detailed daily breakdown (optional ?days=N default 30, or ?year=YYYY&month=MM for specific month)
router.get('/daily', requireSession, async (req, res) => {
  try {
    const whmcsClientId = await resolveClientId(req);
    const year  = req.query.year  ? parseInt(req.query.year, 10)  : undefined;
    const month = req.query.month ? parseInt(req.query.month, 10) - 1 : undefined; // API: 1-indexed → 0-indexed
    const days  = Math.min(parseInt(req.query.days || '30', 10), 90);

    let daily;
    if (year != null && month != null) {
      daily = await getDailyUsage(whmcsClientId, 30, { year, month });
    } else {
      daily = await getDailyUsage(whmcsClientId, days);
    }
    res.json({ ok: true, daily });
  } catch (err) {
    logger.error(`usage/daily: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── GET /api/usage/models ─────────────────────────────────────────────────
// Model-level breakdown for the billing period
router.get('/models', requireSession, async (req, res) => {
  try {
    const whmcsClientId = await resolveClientId(req);
    const models = await getModelBreakdown(whmcsClientId);
    res.json({ ok: true, models });
  } catch (err) {
    logger.error(`usage/models: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── GET /api/usage/history ────────────────────────────────────────────────
// Recent request history (optional ?limit=N, default 50, max 200)
router.get('/history', requireSession, async (req, res) => {
  try {
    const whmcsClientId = await resolveClientId(req);
    const limit = Math.min(parseInt(req.query.limit || '50', 10), 200);
    const history = await getRequestHistory(whmcsClientId, limit);
    res.json({ ok: true, history });
  } catch (err) {
    logger.error(`usage/history: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── GET /api/usage/pricing ────────────────────────────────────────────────
// Public endpoint — available model pricing (no auth required)
router.get('/pricing', (_req, res) => {
  const models = getAllModelPricing();
  const plans = Object.entries(config.tokenLimits).map(([name, limit]) => ({
    name: name.charAt(0).toUpperCase() + name.slice(1),
    tokenLimit: limit,
    tokenLimitFormatted: formatTokens(limit),
  }));

  res.json({
    ok: true,
    models,
    plans,
    overagePriceUsd: parseFloat(process.env.OVERAGE_PRICE_USD || '4.99'),
    overageTokenBlock: parseInt(process.env.OVERAGE_TOKEN_BLOCK || '100000', 10),
  });
});

// ── GET /api/usage/cost-estimate ──────────────────────────────────────────
// Estimate cost for a given model and token count
router.get('/cost-estimate', (req, res) => {
  const model  = req.query.model || config.anthropic.model;
  const input  = parseInt(req.query.inputTokens  || '0', 10);
  const output = parseInt(req.query.outputTokens || '0', 10);

  const result = calculateCost(model, input, output);
  res.json({
    ok: true,
    model,
    inputTokens:  input,
    outputTokens: output,
    ...result,
  });
});

// ── GET /api/usage/available-models ──────────────────────────────────────
// Returns full model catalog for the model selector UI
router.get('/available-models', requireSession, (req, res) => {
  try {
    const models = getAvailableModels();
    const modes  = getAvailableModes();
    res.json({ ok: true, models, modes });
  } catch (err) {
    logger.error(`available-models error: ${err.message}`);
    res.status(500).json({ ok: false, error: 'Failed to load models' });
  }
});

// ── GET /api/usage/current-model ─────────────────────────────────────────
// Returns the user's currently selected model/mode
router.get('/current-model', requireSession, async (req, res) => {
  try {
    const clientId = await resolveClientId(req);
    if (!clientId) return res.status(400).json({ ok: false, error: 'No client ID' });
    const mode = await getUserMode(clientId);
    res.json({ ok: true, model: mode });
  } catch (err) {
    logger.error(`current-model error: ${err.message}`);
    res.status(500).json({ ok: false, error: 'Failed to get model preference' });
  }
});

// ── POST /api/usage/set-model ────────────────────────────────────────────
// Set the user's preferred model (persists to Redis)
router.post('/set-model', requireSession, async (req, res) => {
  try {
    const clientId = await resolveClientId(req);
    if (!clientId) return res.status(400).json({ ok: false, error: 'No client ID' });
    const { model } = req.body;
    if (!model) return res.status(400).json({ ok: false, error: 'Missing model parameter' });
    const result = await setUserMode(clientId, model);
    logger.info(`set-model: client ${clientId} → ${result}`);
    res.json({ ok: true, model: result });
  } catch (err) {
    logger.error(`set-model error: ${err.message}`);
    res.status(400).json({ ok: false, error: safeError(err) });
  }
});

// ── Owner-only multiplier controls ──────────────────────────────────────────
// These endpoints let Commander (client_id 33) adjust multipliers directly
// from the Cost Calculator without needing the admin panel.
const OWNER_CLIENT_ID = '33';

function requireOwner(req, res, next) {
  const clientId = String(req.user?.whmcsClientId || '');
  if (clientId !== OWNER_CLIENT_ID) {
    return res.status(403).json({ ok: false, error: 'Owner access only' });
  }
  next();
}

// GET /api/usage/multipliers — fetch current multipliers for all models
router.get('/multipliers', requireSession, requireOwner, async (req, res) => {
  try {
    const { getRedis } = require('../redis');
    const redis = getRedis();
    const result = {};
    for (const key of Object.keys(MODELS)) {
      const override = await redis.get(`multiplier:override:${key}`);
      result[key] = {
        multiplier: override !== null ? parseFloat(override) : (MODELS[key].tokenMultiplier || 1),
        source: override !== null ? 'override' : 'default',
        defaultMultiplier: MODELS[key].tokenMultiplier || 1,
      };
    }
    res.json({ ok: true, multipliers: result });
  } catch (err) {
    logger.error(`usage/multipliers: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// PUT /api/usage/multiplier/:modelKey — set multiplier for a specific model
router.put('/multiplier/:modelKey', requireSession, requireOwner, async (req, res) => {
  try {
    const { modelKey } = req.params;
    if (!MODELS[modelKey]) {
      return res.status(400).json({ ok: false, error: 'Unknown model key' });
    }
    const { multiplier } = req.body || {};
    const val = parseFloat(multiplier);
    if (!Number.isFinite(val) || val <= 0 || val > 999) {
      return res.status(400).json({ ok: false, error: 'Multiplier must be between 0 and 999' });
    }
    const { getRedis } = require('../redis');
    const redis = getRedis();
    await redis.set(`multiplier:override:${modelKey}`, val.toString());
    logger.info(`owner: multiplier override ${modelKey} → ${val}x`);
    res.json({ ok: true, modelKey, multiplier: val, source: 'override' });
  } catch (err) {
    logger.error(`usage/multiplier PUT: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── Credit Balance Endpoints ────────────────────────────────────────────────

// GET /api/usage/credits — get current user's credit balance
router.get('/credits', requireSession, async (req, res) => {
  try {
    const clientId = String(req.user?.whmcsClientId || '');
    const balance = await budget.getCreditBalance(clientId);
    res.json({ ok: true, balance });
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// POST /api/usage/credits/add — owner adds credits to any user
router.post('/credits/add', requireSession, requireOwner, async (req, res) => {
  try {
    const { clientId, amount } = req.body || {};
    const parsedAmount = parseFloat(amount);
    if (!clientId || !Number.isFinite(parsedAmount) || parsedAmount <= 0 || parsedAmount > 10000) {
      return res.status(400).json({ ok: false, error: 'clientId and amount (0-10000) required' });
    }
    const result = await budget.addCredit(String(clientId), parsedAmount);
    logger.info(`owner: added $${parsedAmount} credits to client ${clientId} → balance: $${result.newBalance}`);
    res.json({ ok: true, clientId: String(clientId), added: parsedAmount, newBalance: result.newBalance });
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// POST /api/usage/credits/enable — owner enables/disables credit-gating globally
router.post('/credits/enable', requireSession, requireOwner, async (req, res) => {
  try {
    const { enabled } = req.body || {};
    const { getRedis } = require('../redis');
    const redis = getRedis();
    if (enabled) {
      await redis.set('credits:required', '1');
      logger.info('owner: credit-gating ENABLED globally');
    } else {
      await redis.del('credits:required');
      logger.info('owner: credit-gating DISABLED globally');
    }
    res.json({ ok: true, creditGating: !!enabled });
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── Auto-Replenish Endpoints ────────────────────────────────────────────────

// GET /api/usage/autoreplenish — get current user's auto-replenish settings
router.get('/autoreplenish', requireSession, async (req, res) => {
  try {
    const clientId = String(req.user?.whmcsClientId || '');
    const settings = await budget.getAutoReplenish(clientId);
    res.json({ ok: true, ...settings });
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// POST /api/usage/autoreplenish — set current user's auto-replenish settings
router.post('/autoreplenish', requireSession, async (req, res) => {
  try {
    const clientId = String(req.user?.whmcsClientId || '');
    const { threshold, amount, enabled } = req.body || {};
    await budget.setAutoReplenish(clientId, { threshold, amount, enabled });
    const settings = await budget.getAutoReplenish(clientId);
    logger.info(`client ${clientId}: auto-replenish updated — threshold: $${settings.threshold}, amount: $${settings.amount}, enabled: ${settings.enabled}`);
    res.json({ ok: true, ...settings });
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── Fraud & Anomaly Dashboard Endpoints (owner-only) ────────────────────────

// GET /api/usage/fraud/watchlist — get sorted list of suspicious users
router.get('/fraud/watchlist', requireSession, requireOwner, async (req, res) => {
  try {
    const limit = Math.min(parseInt(req.query.limit) || 50, 200);
    const watchlist = await anomaly.getWatchlist(limit);
    res.json({ ok: true, watchlist });
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// GET /api/usage/fraud/score/:clientId — get fraud score for a specific user
router.get('/fraud/score/:clientId', requireSession, requireOwner, async (req, res) => {
  try {
    const clientId = String(req.params.clientId);
    if (!clientId || clientId.length > 20) {
      return res.status(400).json({ ok: false, error: 'Invalid clientId' });
    }
    const score = await anomaly.scoreUser(clientId);
    const frozen = await budget.isAccountFrozen(clientId);
    const balance = await budget.getCreditBalance(clientId);
    res.json({ ok: true, clientId, ...score, frozen, balance });
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// POST /api/usage/fraud/clear/:clientId — clear fraud score for a user
router.post('/fraud/clear/:clientId', requireSession, requireOwner, async (req, res) => {
  try {
    const clientId = String(req.params.clientId);
    if (!clientId || clientId.length > 20) {
      return res.status(400).json({ ok: false, error: 'Invalid clientId' });
    }
    await anomaly.clearFraudScore(clientId);
    logger.info(`owner: cleared fraud score for client ${clientId}`);
    res.json({ ok: true, clientId, cleared: true });
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// POST /api/usage/account/freeze/:clientId — freeze a user's account
router.post('/account/freeze/:clientId', requireSession, requireOwner, async (req, res) => {
  try {
    const clientId = String(req.params.clientId);
    if (!clientId || clientId.length > 20) {
      return res.status(400).json({ ok: false, error: 'Invalid clientId' });
    }
    await budget.freezeAccount(clientId);
    logger.info(`owner: FROZE account for client ${clientId}`);
    res.json({ ok: true, clientId, frozen: true });
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// POST /api/usage/account/unfreeze/:clientId — unfreeze a user's account
router.post('/account/unfreeze/:clientId', requireSession, requireOwner, async (req, res) => {
  try {
    const clientId = String(req.params.clientId);
    if (!clientId || clientId.length > 20) {
      return res.status(400).json({ ok: false, error: 'Invalid clientId' });
    }
    await budget.unfreezeAccount(clientId);
    logger.info(`owner: UNFROZE account for client ${clientId}`);
    res.json({ ok: true, clientId, frozen: false });
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── Helpers ──────────────────────────────────────────────────────────────────

function getMonthStart() {
  const d = new Date();
  return new Date(d.getFullYear(), d.getMonth(), 1).toISOString().slice(0, 10);
}

function getMonthEnd() {
  const d = new Date();
  return new Date(d.getFullYear(), d.getMonth() + 1, 0).toISOString().slice(0, 10);
}

function formatTokens(n) {
  if (n >= 1_000_000) return `${(n / 1_000_000).toFixed(1)}M`;
  if (n >= 1_000) return `${(n / 1_000).toFixed(0)}K`;
  return String(n);
}

module.exports = router;
