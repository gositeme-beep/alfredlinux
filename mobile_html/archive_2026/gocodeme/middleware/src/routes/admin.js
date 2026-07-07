'use strict';

/**
 * Admin Status API (called by WHMCS GoCodeMe addon)
 *
 * GET /api/admin/status   — Aggregate system status for the admin dashboard
 *
 * Requires X-WHMCS-Secret header.
 */

const express = require('express');
const router  = express.Router();
const os      = require('os');
const { getRedis } = require('../redis');
const logger  = require('../logger');
const { requireWhmcsSecret } = require('../auth/whmcsSecret');
const scanKeys = require('../utils/scanKeys');
const safeError = require('../utils/safeError');
const { getOfferTelemetry } = require('../billing/offerTelemetry');
const { MODELS, getAvailableModels } = require('../billing/modelRouter');
const { SPEND_MODE_MULTIPLIERS } = require('../billing/spendMode');
const crypto = require('crypto');
const https = require('https');
const http = require('http');

// Alias used by budget/admin endpoints (same auth gate)
const requireAdmin = requireWhmcsSecret;

// ── GET /api/admin/status ──────────────────────────────────────────────────
router.get('/status', requireWhmcsSecret, async (req, res) => {
  try {
    const redis = getRedis();

    // Gather all access keys → user list with status
    const accessKeys = await scanKeys(redis, 'access:*');
    const users = [];
    for (const key of accessKeys) {
      const clientId = key.split(':')[1];
      const status   = await redis.get(key);
      const daUser   = await redis.get(`da_username:${clientId}`);
      const tokensUsed  = parseInt(await redis.get(`tokens:used:${clientId}`) || '0', 10);
      const tokensLimit = parseInt(await redis.get(`tokens:limit:${clientId}`) || '0', 10);

      // Check for active sessions
      let sessions = [];
      if (daUser) {
        const sessRaw = await redis.get(`launch:sessions:${daUser}`);
        try { sessions = JSON.parse(sessRaw) || []; } catch {}
      }

      users.push({
        whmcsClientId: clientId,
        daUsername:     daUser || '(unmapped)',
        accessStatus:  status,
        tokensUsed:    tokensUsed,
        tokensLimit:   tokensLimit,
        activeSessions: sessions.length,
        sessions:       sessions.map(s => ({
          service: s.service,
          port:    s.port,
          started: s.started,
          url:     s.url,
        })),
      });
    }

    // System health
    const uptime  = process.uptime();
    const memUsed = process.memoryUsage();

    // PM2 info (basic)
    let pm2Services = [];
    try {
      // SECURITY (R3 M-05): Use execFileSync (no shell) for PM2 commands
      const { execFileSync } = require('child_process');
      const raw = execFileSync('npx', ['pm2', 'jlist'], { timeout: 5000, encoding: 'utf8' });
      const list = JSON.parse(raw);
      pm2Services = list.map(p => ({
        name:      p.name,
        status:    p.pm2_env?.status,
        cpu:       p.monit?.cpu,
        memory:    p.monit?.memory,
        restarts:  p.pm2_env?.restart_time,
        uptime:    p.pm2_env?.pm_uptime,
      }));
    } catch {}

    // Redis ping
    let redisOk = false;
    try { redisOk = (await redis.ping()) === 'PONG'; } catch {}

    res.json({
      ok: true,
      system: {
        nodeVersion:   process.version,
        platform:      os.platform(),
        uptime:        Math.round(uptime),
        memoryMB:      Math.round(memUsed.rss / 1024 / 1024),
        redisConnected: redisOk,
        pm2Services,
      },
      totalUsers:       users.length,
      activeUsers:      users.filter(u => u.accessStatus === 'active').length,
      suspendedUsers:   users.filter(u => u.accessStatus === 'suspended').length,
      totalActiveSessions: users.reduce((sum, u) => sum + u.activeSessions, 0),
      users,
    });
  } catch (err) {
    logger.error(`admin status error: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── POST /api/admin/kill-session ───────────────────────────────────────────
router.post('/kill-session', requireWhmcsSecret, async (req, res) => {
  try {
    const { daUsername, port } = req.body;
    if (!daUsername) return res.status(400).json({ ok: false, error: 'daUsername required' });

    const redis   = getRedis();
    const sessRaw = await redis.get(`launch:sessions:${daUsername}`);
    let sessions  = [];
    try { sessions = JSON.parse(sessRaw) || []; } catch {}

    if (sessions.length === 0) {
      return res.json({ ok: true, message: 'No sessions found' });
    }

    // Kill specific port or all sessions
    const toKill = port ? sessions.filter(s => s.port === parseInt(port)) : sessions;
    const remaining = port ? sessions.filter(s => s.port !== parseInt(port)) : [];

    for (const s of toKill) {
      if (s.pid) {
        try { process.kill(s.pid, 'SIGTERM'); } catch {}
        setTimeout(() => {
          try { process.kill(s.pid, 'SIGKILL'); } catch {}
        }, 3000);
      }
    }

    if (remaining.length > 0) {
      await redis.set(`launch:sessions:${daUsername}`, JSON.stringify(remaining), 'EX', 86400);
    } else {
      await redis.del(`launch:sessions:${daUsername}`);
    }

    res.json({ ok: true, killed: toKill.length, remaining: remaining.length });
  } catch (err) {
    logger.error(`admin kill-session error: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});


// ── Budget / circuit-breaker admin endpoints ─────────────────────────────────

const budget = require('../tokens/tokenBudget');
const { VALID_SPEND_MODES, getSpendMode, setSpendMode } = require('../billing/spendMode');

// GET /api/admin/budget — global spend summary
router.get('/budget', requireAdmin, async (req, res) => {
  try {
    const global = await budget.getGlobalSpend();
    res.json({ ok: true, global, limits: {
      globalMonthlyCap:    budget.GLOBAL_MONTHLY_USD_CAP,
      dailyTokenCap:       budget.DAILY_TOKEN_CAP,
      requestMaxTokens:    budget.REQUEST_MAX_TOKENS,
      monthlyOveragePct:   budget.MONTHLY_OVERAGE_CAP_PCT,
    }});
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// POST /api/admin/budget/reset-breaker — reset global circuit breaker
router.post('/budget/reset-breaker', requireAdmin, async (req, res) => {
  try {
    await budget.resetGlobalBreaker();
    res.json({ ok: true, message: 'Global circuit breaker reset. Traffic will resume.' });
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// GET /api/admin/budget/user/:clientId — per-user budget summary
router.get('/budget/user/:clientId', requireAdmin, async (req, res) => {
  try {
    const { clientId } = req.params;
    const tc = require('../tokens/tokenCounter');
    const usage = await tc.getUsage(clientId);
    const overage = await tc.getOverageDetails(clientId);
    const summary = await budget.getUserBudgetSummary(clientId, usage.limit);
    res.json({ ok: true, clientId, monthly: usage, overage, ...summary });
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── Spend-mode admin controls ───────────────────────────────────────────
// GET /api/admin/spend/user/:clientId
router.get('/spend/user/:clientId', requireAdmin, async (req, res) => {
  try {
    const { clientId } = req.params;
    const mode = await getSpendMode(clientId);
    const cap = await budget.getUserDailyUsdCapDetails(clientId);
    res.json({ ok: true, clientId, mode, validModes: VALID_SPEND_MODES, cap });
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// POST /api/admin/spend/user/:clientId
// body: { mode?: economy|balanced|power, dailyUsdCapOverride?: number, clearOverride?: boolean }
router.post('/spend/user/:clientId', requireAdmin, async (req, res) => {
  try {
    const { clientId } = req.params;
    const { mode, dailyUsdCapOverride, clearOverride } = req.body || {};

    if (mode !== undefined) {
      const normalized = String(mode).toLowerCase().trim();
      if (!VALID_SPEND_MODES.includes(normalized)) {
        return res.status(400).json({ ok: false, error: 'Invalid mode. Valid: economy, balanced, power' });
      }
      await setSpendMode(clientId, normalized);
    }

    if (clearOverride) {
      await budget.clearUserDailyUsdCapOverride(clientId);
    } else if (dailyUsdCapOverride !== undefined && dailyUsdCapOverride !== null && dailyUsdCapOverride !== '') {
      await budget.setUserDailyUsdCapOverride(clientId, dailyUsdCapOverride);
    }

    const updatedMode = await getSpendMode(clientId);
    const cap = await budget.getUserDailyUsdCapDetails(clientId);
    res.json({ ok: true, clientId, mode: updatedMode, cap });
  } catch (err) {
    res.status(400).json({ ok: false, error: safeError(err) });
  }
});

// ── GET /api/admin/offers/telemetry ─────────────────────────────────────
// Returns A/B offer funnel metrics for the last N days (default: 7).
router.get('/offers/telemetry', requireAdmin, async (req, res) => {
  try {
    const days = parseInt(req.query.days || '7', 10);
    const telemetry = await getOfferTelemetry(days);
    res.json({ ok: true, ...telemetry });
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── GET /api/admin/costs — comprehensive cost & revenue dashboard ────────
// Shows: daily API spend, per-user cost breakdown, model routing stats,
// optimization savings, revenue vs cost, margin analysis.
router.get('/costs', requireAdmin, async (req, res) => {
  try {
    const redis = getRedis();
    const tc = require('../tokens/tokenCounter');
    const today = new Date().toISOString().slice(0, 10);
    const month = new Date().toISOString().slice(0, 7);

    // ── Global spend ─────────────────────────────────────────────────
    const globalMonthly = parseFloat(await redis.get(`budget:global:spend:${month}`) || '0');
    const globalDaily = parseFloat(await redis.get(`budget:global:daily:${today}`) || '0');

    // ── Per-user breakdown ───────────────────────────────────────────
    const accessKeys = await scanKeys(redis, 'access:*');
    const perUser = [];
    let totalSavings = 0;
    let totalOverageRevenue = 0;

    for (const key of accessKeys) {
      const cid = key.split(':')[1];
      const daUser = await redis.get(`da_username:${cid}`) || '(unmapped)';
      const tokensUsed = parseInt(await redis.get(`tokens:used:${cid}`) || '0', 10);
      const tokensLimit = parseInt(await redis.get(`tokens:limit:${cid}`) || '0', 10);
      const dailySpend = parseFloat(await redis.get(`budget:user:daily:${cid}:${today}`) || '0');
      const dailyTokens = parseInt(await redis.get(`budget:daily:${cid}:${today}`) || '0', 10);
      const savings = parseFloat(await redis.get(`savings:${cid}`) || '0');
      const overage = await tc.getOverageDetails(cid);

      totalSavings += savings;
      totalOverageRevenue += overage.overageCost;

      perUser.push({
        whmcsClientId: cid,
        daUsername: daUser,
        planLimit: tokensLimit,
        monthlyUsed: tokensUsed,
        percentUsed: tokensLimit > 0 ? Math.round((tokensUsed / tokensLimit) * 100) : 0,
        todaySpendUSD: Math.round(dailySpend * 1000000) / 1000000,
        todayTokens: dailyTokens,
        savings: Math.round(savings * 100) / 100,
        overage: overage.inOverage ? overage : null,
      });
    }

    // Sort by today's spend descending (top cost users first)
    perUser.sort((a, b) => b.todaySpendUSD - a.todaySpendUSD);

    // ── Model routing stats (from last 7 days of logs) ──────────────
    // We'll use Redis keys for daily spend per model if they exist,
    // otherwise just show today's global data
    const routingKeys = await scanKeys(redis, 'routing:model:*');
    const modelStats = {};
    for (const rk of routingKeys) {
      const model = rk.replace('routing:model:', '');
      const count = parseInt(await redis.get(rk) || '0', 10);
      modelStats[model] = count;
    }

    // ── Revenue estimate (from WHMCS active services) ────────────────
    // Approximate: sum of per-user plan amounts
    // perUser already has plan limits — map back to pricing
    const planPricing = { 50000: 0, 300000: 15, 600000: 29, 1500000: 59, 3000000: 99, 5000000: 199 };
    let monthlyRevenue = 0;
    for (const u of perUser) {
      const price = planPricing[u.planLimit] || 0;
      monthlyRevenue += price;
    }

    // ── Margin analysis ─────────────────────────────────────────────
    const projectedMonthlyCost = globalDaily > 0 ? globalDaily * 30 : globalMonthly;
    const margin = monthlyRevenue > 0 ? ((monthlyRevenue - projectedMonthlyCost) / monthlyRevenue * 100) : 0;

    res.json({
      ok: true,
      period: { today, month },
      costs: {
        monthToDate: Math.round(globalMonthly * 1000000) / 1000000,
        today: Math.round(globalDaily * 1000000) / 1000000,
        projected30Day: Math.round(projectedMonthlyCost * 100) / 100,
      },
      revenue: {
        monthlySubscriptions: monthlyRevenue,
        overageCharges: Math.round(totalOverageRevenue * 100) / 100,
        totalEstimated: Math.round((monthlyRevenue + totalOverageRevenue) * 100) / 100,
      },
      margin: {
        percentage: Math.round(margin),
        profitEstimate: Math.round((monthlyRevenue + totalOverageRevenue - projectedMonthlyCost) * 100) / 100,
      },
      optimizations: {
        totalSavingsUSD: Math.round(totalSavings * 100) / 100,
        modelRoutingStats: modelStats,
      },
      users: perUser,
      limits: {
        globalMonthlyCap: budget.GLOBAL_MONTHLY_USD_CAP,
        globalDailyCap: budget.GLOBAL_DAILY_USD_CAP,
        perUserDailyCap: budget.USER_DAILY_USD_CAP,
      },
    });
  } catch (err) {
    logger.error(`admin costs error: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── GET /api/admin/multipliers — view all model multipliers ──────────────────
// Returns the full model registry with multipliers and spend mode multipliers.
// Admins can see exactly how each model's tokens are billed.
router.get('/multipliers', requireAdmin, async (req, res) => {
  try {
    const redis = getRedis();
    const models = {};
    for (const [key, m] of Object.entries(MODELS)) {
      // Check for Redis override
      const override = await redis.get(`multiplier:override:${key}`);
      models[key] = {
        displayName: m.displayName,
        emoji: m.emoji || '',
        provider: m.provider,
        tier: m.tier,
        defaultMultiplier: m.tokenMultiplier || 1,
        activeMultiplier: override !== null ? parseFloat(override) : (m.tokenMultiplier || 1),
        overridden: override !== null,
        inputPer1M: m.inputPer1M,
        outputPer1M: m.outputPer1M,
        codeQuality: m.codeQuality,
      };
    }
    res.json({
      ok: true,
      models,
      spendModeMultipliers: SPEND_MODE_MULTIPLIERS,
      formula: 'billable = outputTokens × tokenMultiplier × spendModeMultiplier',
    });
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── PUT /api/admin/multipliers/:modelKey — update a model's multiplier ───────
// Sets a Redis override for the model's token multiplier. Pass { multiplier: N }.
// To reset to default, send { reset: true }.
router.put('/multipliers/:modelKey', requireAdmin, async (req, res) => {
  try {
    const { modelKey } = req.params;
    if (!MODELS[modelKey]) {
      return res.status(404).json({ ok: false, error: `Model "${modelKey}" not found` });
    }
    const { multiplier, reset } = req.body || {};
    const redis = getRedis();

    if (reset) {
      await redis.del(`multiplier:override:${modelKey}`);
      return res.json({
        ok: true, modelKey,
        multiplier: MODELS[modelKey].tokenMultiplier || 1,
        source: 'default',
      });
    }

    const val = parseFloat(multiplier);
    if (!Number.isFinite(val) || val < 0.1 || val > 999) {
      return res.status(400).json({ ok: false, error: 'Multiplier must be 0.1–999' });
    }

    await redis.set(`multiplier:override:${modelKey}`, val.toString());
    logger.info(`admin: multiplier override ${modelKey} → ${val}x (was ${MODELS[modelKey].tokenMultiplier}x)`);

    res.json({ ok: true, modelKey, multiplier: val, source: 'override' });
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── POST /api/admin/multipliers/bulk — apply factor to all models ────────────
// { action: "multiply", factor: 3 }  → multiplies all current multipliers by 3
// { action: "divide", factor: 3 }    → divides all current multipliers by 3
// { action: "reset" }                → resets ALL overrides to defaults
router.post('/multipliers/bulk', requireAdmin, async (req, res) => {
  try {
    const { action, factor } = req.body || {};
    const redis = getRedis();

    if (action === 'reset') {
      const keys = [];
      for (const key of Object.keys(MODELS)) {
        keys.push(`multiplier:override:${key}`);
      }
      if (keys.length) await redis.del(keys);
      logger.info('admin: bulk reset all multiplier overrides');
      return res.json({ ok: true, action: 'reset', modelsAffected: keys.length });
    }

    if (action !== 'multiply' && action !== 'divide') {
      return res.status(400).json({ ok: false, error: 'action must be multiply, divide, or reset' });
    }

    const f = parseFloat(factor);
    if (!Number.isFinite(f) || f <= 0 || f > 999) {
      return res.status(400).json({ ok: false, error: 'factor must be 0.01–999' });
    }

    const results = {};
    for (const [key, m] of Object.entries(MODELS)) {
      const override = await redis.get(`multiplier:override:${key}`);
      const current = override !== null ? parseFloat(override) : (m.tokenMultiplier || 1);
      let newVal = action === 'multiply' ? current * f : current / f;
      newVal = Math.round(newVal * 100) / 100; // 2 decimal places
      newVal = Math.max(0.1, Math.min(999, newVal));
      await redis.set(`multiplier:override:${key}`, newVal.toString());
      results[key] = { was: current, now: newVal };
    }

    logger.info(`admin: bulk ${action} ×${f} across ${Object.keys(results).length} models`);
    res.json({ ok: true, action, factor: f, results });
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── GET /api/admin/compact-stats — compact savings across all users ──────────
router.get('/compact-stats', requireAdmin, async (req, res) => {
  try {
    const redis = getRedis();
    const accessKeys = await scanKeys(redis, 'access:*');
    let totalSavedTokens = 0;
    let totalCompacts = 0;
    const perUser = [];
    for (const key of accessKeys) {
      const cid = key.split(':')[1];
      const saved = parseInt(await redis.get(`compact:saved_tokens:${cid}`) || '0', 10);
      const count = parseInt(await redis.get(`compact:count:${cid}`) || '0', 10);
      if (saved > 0 || count > 0) {
        totalSavedTokens += saved;
        totalCompacts += count;
        perUser.push({ clientId: cid, savedTokens: saved, compactCount: count });
      }
    }
    res.json({ ok: true, totalSavedTokens, totalCompacts, perUser });
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── GET /api/admin/sessions — live IDE sessions with access links ────────────
// Returns all active IDE sessions with their URLs for Commander access.
router.get('/sessions', requireAdmin, async (req, res) => {
  try {
    const redis = getRedis();
    const keys = await scanKeys(redis, 'launch:sessions:*');
    const sessions = [];
    for (const key of keys) {
      const daUser = key.replace('launch:sessions:', '');
      const raw = await redis.get(key);
      let list = [];
      try { list = JSON.parse(raw) || []; } catch {}
      const clientId = await redis.get(`whmcs_client:${daUser}`) || '(unknown)';
      const lastActive = await redis.get(`activity:${daUser}`);
      for (const s of list) {
        sessions.push({
          daUsername: daUser,
          whmcsClientId: clientId,
          service: s.service,
          port: s.port,
          url: s.url,
          started: s.started,
          lastActive: lastActive ? parseInt(lastActive, 10) : null,
          idleMinutes: lastActive ? Math.round((Date.now() - parseInt(lastActive, 10)) / 60000) : null,
        });
      }
    }
    res.json({ ok: true, count: sessions.length, sessions });
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── POST /api/admin/view-session — generate admin access token for IDE ───────
// Creates a temporary token (15 min TTL) that grants the Commander read-only
// access to a user's IDE session. The token is appended to the IDE URL.
router.post('/view-session', requireAdmin, async (req, res) => {
  try {
    const { daUsername } = req.body || {};
    if (!daUsername || !/^[a-zA-Z0-9_-]+$/.test(daUsername)) {
      return res.status(400).json({ ok: false, error: 'Valid daUsername required' });
    }
    const redis = getRedis();
    const sessRaw = await redis.get(`launch:sessions:${daUsername}`);
    let sessions = [];
    try { sessions = JSON.parse(sessRaw) || []; } catch {}
    if (sessions.length === 0) {
      return res.status(404).json({ ok: false, error: 'No active sessions for this user' });
    }

    // Generate a short-lived admin view token
    const token = crypto.randomBytes(32).toString('hex');
    await redis.set(`admin_view:${token}`, daUsername, 'EX', 900); // 15 min

    // Return the IDE URL with the admin token
    const ideSession = sessions[0]; // Primary IDE session
    const viewUrl = ideSession.url
      ? `${ideSession.url}${ideSession.url.includes('?') ? '&' : '?'}admin_view=${token}`
      : null;

    logger.info(`admin: Commander generated view token for ${daUsername} session (port ${ideSession.port})`);

    res.json({
      ok: true,
      daUsername,
      viewUrl,
      port: ideSession.port,
      token,
      expiresIn: '15 minutes',
    });
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── GET /api/admin/domain-health — check all domains for HTTP 200 + SSL ──────
router.get('/domain-health', requireAdmin, async (req, res) => {
  try {
    const fs = require('fs');
    const domainDir = '/home/gositeme/domains';
    const entries = fs.readdirSync(domainDir, { withFileTypes: true });
    const domains = entries
      .filter(e => e.isDirectory() && !e.name.startsWith('.'))
      .map(e => e.name)
      .filter(d => d.includes('.'));  // must have a dot to be a domain

    const results = await Promise.all(domains.map(domain => {
      return new Promise(resolve => {
        const start = Date.now();
        const req = https.request({
          hostname: domain,
          port: 443,
          path: '/',
          method: 'HEAD',
          timeout: 8000,
          rejectAuthorized: false,
        }, (r) => {
          resolve({
            domain,
            status: r.statusCode,
            ok: r.statusCode >= 200 && r.statusCode < 400,
            ssl: true,
            latencyMs: Date.now() - start,
            redirect: r.headers.location || null,
          });
        });
        req.on('error', (err) => {
          resolve({ domain, status: 0, ok: false, ssl: false, latencyMs: Date.now() - start, error: err.code || err.message });
        });
        req.on('timeout', () => {
          req.destroy();
          resolve({ domain, status: 0, ok: false, ssl: false, latencyMs: Date.now() - start, error: 'TIMEOUT' });
        });
        req.end();
      });
    }));

    const healthy = results.filter(r => r.ok).length;
    res.json({ ok: true, total: domains.length, healthy, unhealthy: domains.length - healthy, domains: results });
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── GET /api/admin/usage-dashboard — aggregate token/cost stats ──────────────
router.get('/usage-dashboard', requireAdmin, async (req, res) => {
  try {
    const redis = getRedis();
    const days = parseInt(req.query.days) || 7;
    const now = new Date();
    const dailyData = [];

    // Gather daily usage for the last N days
    const allClientKeys = await scanKeys(redis, 'usage:daily:*');
    const clientIds = new Set();
    for (const k of allClientKeys) {
      const parts = k.split(':');
      if (parts.length >= 4) clientIds.add(parts[2]);
    }

    for (let i = 0; i < days; i++) {
      const d = new Date(now);
      d.setDate(d.getDate() - i);
      const dateStr = d.toISOString().slice(0, 10);
      let dayInput = 0, dayOutput = 0, dayCost = 0, dayRequests = 0;

      for (const cid of clientIds) {
        const hash = await redis.hgetall(`usage:daily:${cid}:${dateStr}`);
        if (hash && hash['_total:input']) {
          dayInput += parseFloat(hash['_total:input'] || 0);
          dayOutput += parseFloat(hash['_total:output'] || 0);
          dayCost += parseFloat(hash['_total:cost'] || 0);
          dayRequests += parseInt(hash['_total:requests'] || 0, 10);
        }
      }

      dailyData.push({ date: dateStr, inputTokens: Math.round(dayInput), outputTokens: Math.round(dayOutput), cost: parseFloat(dayCost.toFixed(6)), requests: dayRequests });
    }

    // Model breakdown (aggregate across all users)
    const modelTotals = {};
    for (const cid of clientIds) {
      const hash = await redis.hgetall(`usage:models:${cid}`);
      if (!hash) continue;
      for (const [model, jsonStr] of Object.entries(hash)) {
        try {
          const d = JSON.parse(jsonStr);
          if (!modelTotals[model]) modelTotals[model] = { inputTokens: 0, outputTokens: 0, cost: 0, requests: 0 };
          modelTotals[model].inputTokens += d.inputTokens || 0;
          modelTotals[model].outputTokens += d.outputTokens || 0;
          modelTotals[model].cost += d.cost || 0;
          modelTotals[model].requests += d.requests || 0;
        } catch {}
      }
    }

    // Global totals
    const globalSpend = parseFloat(await redis.get('budget:global_spend') || '0');
    const globalDailySpend = parseFloat(await redis.get('budget:global_daily_spend') || '0');

    res.json({
      ok: true,
      period: `${days} days`,
      daily: dailyData.reverse(),
      models: modelTotals,
      globalSpend: parseFloat(globalSpend.toFixed(6)),
      todaySpend: parseFloat(globalDailySpend.toFixed(6)),
      activeUsers: clientIds.size,
    });
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── GET /api/admin/conversations — recent AI conversation history ────────────
router.get('/conversations', requireAdmin, async (req, res) => {
  try {
    const redis = getRedis();
    const limit = Math.min(parseInt(req.query.limit) || 50, 200);

    // Gather usage history entries from all users
    const historyKeys = await scanKeys(redis, 'usage:history:*');
    const conversations = [];
    for (const key of historyKeys) {
      const cid = key.replace('usage:history:', '');
      const items = await redis.lrange(key, 0, Math.min(limit, 50) - 1);
      for (const item of items) {
        try {
          const entry = JSON.parse(item);
          entry.clientId = cid;
          conversations.push(entry);
        } catch {}
      }
    }

    // Sort by timestamp descending, limit
    conversations.sort((a, b) => (b.timestamp || 0) - (a.timestamp || 0));

    res.json({
      ok: true,
      count: Math.min(conversations.length, limit),
      conversations: conversations.slice(0, limit),
    });
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

module.exports = router;
