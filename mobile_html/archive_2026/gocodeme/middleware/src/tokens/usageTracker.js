'use strict';

/**
 * tokens/usageTracker.js — Detailed per-model, per-day token usage tracking
 *
 * Stores granular usage data in Redis alongside the simple counter.
 * Enables the usage dashboard to show:
 *   - Cost breakdown by model
 *   - Daily usage charts
 *   - Per-request cost
 *   - Running total cost for the billing period
 *
 * Redis keys:
 *   usage:daily:{clientId}:{YYYY-MM-DD}     → Hash { model:input, model:output, model:cost, model:requests }
 *   usage:models:{clientId}                  → Hash { model → JSON({ inputTokens, outputTokens, cost, requests }) }
 *   usage:total_cost:{clientId}              → float string, running total USD cost for billing period
 *   usage:history:{clientId}                 → List of recent request entries (capped at 500)
 *
 * All keys have 35-day TTL to auto-expire with billing cycle.
 */

const { getRedis } = require('../redis');
const { calculateCost } = require('../billing/pricing');
const logger = require('../logger');

const TTL_35_DAYS = 35 * 24 * 60 * 60;
const MAX_HISTORY = 500;

/**
 * Record a detailed usage event (called after every Anthropic API call).
 *
 * @param {string|number} whmcsClientId
 * @param {object} opts
 * @param {string} opts.model        — model ID (e.g. 'claude-sonnet-4-20250514')
 * @param {number} opts.inputTokens  — input tokens consumed
 * @param {number} opts.outputTokens — output tokens consumed
 * @param {string} opts.daUsername    — DA username for context
 * @returns {Promise<{ cost: object, dailyTotal: object }>}
 */
async function recordUsage(whmcsClientId, { model, inputTokens, outputTokens, daUsername }) {
  const redis = getRedis();
  const today = new Date().toISOString().slice(0, 10);
  const month = new Date().toISOString().slice(0, 7);
  const cost  = calculateCost(model, inputTokens, outputTokens);

  const pipeline = redis.pipeline();

  // ── 1. Daily breakdown by model ──────────────────────────────────────────
  const dailyKey = `usage:daily:${whmcsClientId}:${today}`;
  pipeline.hincrbyfloat(dailyKey, `${model}:input`,    inputTokens);
  pipeline.hincrbyfloat(dailyKey, `${model}:output`,   outputTokens);
  pipeline.hincrbyfloat(dailyKey, `${model}:cost`,     cost.totalCost);
  pipeline.hincrby(dailyKey,      `${model}:requests`,  1);
  pipeline.hincrbyfloat(dailyKey, '_total:input',       inputTokens);
  pipeline.hincrbyfloat(dailyKey, '_total:output',      outputTokens);
  pipeline.hincrbyfloat(dailyKey, '_total:cost',        cost.totalCost);
  pipeline.hincrby(dailyKey,      '_total:requests',    1);
  pipeline.expire(dailyKey, TTL_35_DAYS);

  // ── 2. Aggregate model totals for billing period ─────────────────────────
  const modelsKey = `usage:models:${whmcsClientId}`;
  pipeline.hincrbyfloat(modelsKey, `${model}:input`,    inputTokens);
  pipeline.hincrbyfloat(modelsKey, `${model}:output`,   outputTokens);
  pipeline.hincrbyfloat(modelsKey, `${model}:cost`,     cost.totalCost);
  pipeline.hincrby(modelsKey,      `${model}:requests`,  1);
  pipeline.expire(modelsKey, TTL_35_DAYS);

  // ── 3. Running total cost ────────────────────────────────────────────────
  const totalCostKey = `usage:total_cost:${whmcsClientId}`;
  pipeline.incrbyfloat(totalCostKey, cost.totalCost);
  pipeline.expire(totalCostKey, TTL_35_DAYS);

  // ── 4. Request history (recent 500) ──────────────────────────────────────
  const historyKey = `usage:history:${whmcsClientId}`;
  const entry = JSON.stringify({
    ts:     new Date().toISOString(),
    model,
    input:  inputTokens,
    output: outputTokens,
    cost:   cost.totalCost,
    user:   daUsername,
  });
  pipeline.lpush(historyKey, entry);
  pipeline.ltrim(historyKey, 0, MAX_HISTORY - 1);
  pipeline.expire(historyKey, TTL_35_DAYS);

  // ── 5. Team budget usage counters (if client is in a team) ─────────────
  const teamId = await redis.get(`team:by_client:${whmcsClientId}`);
  if (teamId) {
    pipeline.incrby(`team:budget:daily:tokens:${teamId}:${today}`, inputTokens + outputTokens);
    pipeline.incrbyfloat(`team:budget:daily:usd:${teamId}:${today}`, cost.totalCost);
    pipeline.incrby(`team:budget:monthly:tokens:${teamId}:${month}`, inputTokens + outputTokens);
    pipeline.expire(`team:budget:daily:tokens:${teamId}:${today}`, TTL_35_DAYS);
    pipeline.expire(`team:budget:daily:usd:${teamId}:${today}`, TTL_35_DAYS);
    pipeline.expire(`team:budget:monthly:tokens:${teamId}:${month}`, TTL_35_DAYS);
  }

  await pipeline.exec();

  logger.debug(
    `usage-tracker: client ${whmcsClientId} ${model} ` +
    `${inputTokens}in + ${outputTokens}out = $${cost.totalCost.toFixed(6)}`
  );

  return { cost };
}

/**
 * Get daily usage breakdown for a customer.
 * Returns an array of daily summaries for a date range.
 *
 * @param {string|number} whmcsClientId
 * @param {number} [days=30] — how many days back to look (ignored if year/month given)
 * @param {object} [opts] — optional { year, month } (0-indexed month) to fetch a specific calendar month
 * @returns {Promise<object[]>} Array of { date, models: { [model]: { input, output, cost, requests } }, total: {...} }
 */
async function getDailyUsage(whmcsClientId, days = 30, opts = {}) {
  const redis = getRedis();
  const result = [];

  let dates = [];
  if (opts.year != null && opts.month != null) {
    // Specific calendar month
    const y = parseInt(opts.year, 10);
    const m = parseInt(opts.month, 10);
    const daysInMonth = new Date(y, m + 1, 0).getDate();
    for (let d = 1; d <= daysInMonth; d++) {
      const dt = new Date(y, m, d);
      dates.push(dt.toISOString().slice(0, 10));
    }
  } else {
    // Last N days (most recent first)
    for (let i = 0; i < days; i++) {
      const date = new Date();
      date.setDate(date.getDate() - i);
      dates.push(date.toISOString().slice(0, 10));
    }
  }

  // Use pipeline for efficiency instead of sequential calls
  const pipeline = redis.pipeline();
  dates.forEach(dateStr => {
    pipeline.hgetall(`usage:daily:${whmcsClientId}:${dateStr}`);
  });
  const pipelineResults = await pipeline.exec();

  for (let idx = 0; idx < dates.length; idx++) {
    const dateStr = dates[idx];
    const [err, data] = pipelineResults[idx];

    if (err || !data || Object.keys(data).length === 0) {
      result.push({ date: dateStr, models: {}, total: { input: 0, output: 0, cost: 0, requests: 0 } });
      continue;
    }

    // Parse the flat hash into structured data
    const models = {};
    let total = { input: 0, output: 0, cost: 0, requests: 0 };

    for (const [field, value] of Object.entries(data)) {
      const [modelOrTotal, metric] = splitField(field);

      if (modelOrTotal === '_total') {
        total[metric] = parseFloat(value) || 0;
      } else {
        if (!models[modelOrTotal]) {
          models[modelOrTotal] = { input: 0, output: 0, cost: 0, requests: 0 };
        }
        models[modelOrTotal][metric] = parseFloat(value) || 0;
      }
    }

    result.push({ date: dateStr, models, total });
  }

  return result;
}

/**
 * Get aggregated model breakdown for the billing period.
 *
 * @param {string|number} whmcsClientId
 * @returns {Promise<object[]>} Array of { model, displayName, input, output, cost, requests, tier }
 */
async function getModelBreakdown(whmcsClientId) {
  const redis = getRedis();
  const key = `usage:models:${whmcsClientId}`;
  const data = await redis.hgetall(key);

  if (!data || Object.keys(data).length === 0) return [];

  const models = {};
  for (const [field, value] of Object.entries(data)) {
    const [model, metric] = splitField(field);
    if (!models[model]) {
      models[model] = { model, input: 0, output: 0, cost: 0, requests: 0 };
    }
    models[model][metric] = parseFloat(value) || 0;
  }

  // Enrich with display names and tier info
  const { getModelPricing } = require('../billing/pricing');
  return Object.values(models).map(m => {
    const pricing = getModelPricing(m.model);
    return {
      ...m,
      displayName: pricing.displayName,
      tier: pricing.tier,
      inputPer1M: pricing.inputPer1M,
      outputPer1M: pricing.outputPer1M,
    };
  });
}

/**
 * Get running total cost for the billing period.
 *
 * @param {string|number} whmcsClientId
 * @returns {Promise<number>} Total cost in USD
 */
async function getTotalCost(whmcsClientId) {
  const redis = getRedis();
  const val = await redis.get(`usage:total_cost:${whmcsClientId}`);
  return parseFloat(val || '0');
}

/**
 * Get recent request history.
 *
 * @param {string|number} whmcsClientId
 * @param {number} [limit=50]
 * @returns {Promise<object[]>}
 */
async function getRequestHistory(whmcsClientId, limit = 50) {
  const redis = getRedis();
  const entries = await redis.lrange(`usage:history:${whmcsClientId}`, 0, limit - 1);
  return entries.map(e => {
    try { return JSON.parse(e); } catch { return null; }
  }).filter(Boolean);
}

/**
 * Reset all detailed usage data (called on billing cycle renewal).
 *
 * @param {string|number} whmcsClientId
 */
async function resetDetailedUsage(whmcsClientId) {
  const redis = getRedis();

  // Find and delete all daily keys for this client
  let cursor = '0';
  const keysToDelete = [];
  do {
    const [nextCursor, keys] = await redis.scan(cursor, 'MATCH', `usage:daily:${whmcsClientId}:*`, 'COUNT', 100);
    cursor = nextCursor;
    keysToDelete.push(...keys);
  } while (cursor !== '0');

  keysToDelete.push(
    `usage:models:${whmcsClientId}`,
    `usage:total_cost:${whmcsClientId}`,
    `usage:history:${whmcsClientId}`,
  );

  if (keysToDelete.length > 0) {
    await redis.del(...keysToDelete);
  }

  logger.info(`usage-tracker: reset detailed usage for client ${whmcsClientId} (${keysToDelete.length} keys deleted)`);
}

// ── Helpers ──────────────────────────────────────────────────────────────────

/**
 * Split a Redis hash field like "claude-sonnet-4-20250514:input" into [model, metric].
 * The model ID itself may contain colons, so we split on the LAST colon.
 */
function splitField(field) {
  const lastColon = field.lastIndexOf(':');
  if (lastColon === -1) return [field, 'unknown'];
  return [field.slice(0, lastColon), field.slice(lastColon + 1)];
}

module.exports = {
  recordUsage,
  getDailyUsage,
  getModelBreakdown,
  getTotalCost,
  getRequestHistory,
  resetDetailedUsage,
};
