'use strict';

/**
 * Token Counter
 *
 * Tracks per-customer Anthropic API token usage in Redis.
 * Keys:
 *   tokens:used:<whmcsClientId>     → integer, current month's usage
 *   tokens:limit:<whmcsClientId>    → integer, monthly limit for their plan
 *   tokens:reset:<whmcsClientId>    → ISO timestamp of next billing cycle reset
 *
 * On every Claude API call the caller must report consumed tokens via addUsage().
 * The middleware checks allowance before forwarding requests to Claude.
 */

const config = require('../config');
const logger = require('../logger');
const { getRedis } = require('../redis');

const GRACE_TOKENS_DEFAULT = parseInt(process.env.GRACE_TOKENS_DEFAULT || '20000', 10);

function utcMonth() {
  return new Date().toISOString().slice(0, 7);
}

/**
 * Increment token usage for a customer.
 *
 * IMPORTANT: Only OUTPUT tokens count toward the user's plan limit.
 * Input tokens are the platform's cost of re-sending conversation context —
 * users shouldn't burn through their allocation just because they have a
 * long chat.  Input cost is tracked separately via the global/daily USD
 * caps in tokenBudget.js which protect the platform.
 *
 * tokenMultiplier: Expensive models (Opus=5x, Sonnet=3x) consume more plan
 * tokens per API output token. Economy/free models (0.5x) give users more
 * value. This ensures ALL models are profitable regardless of user choice.
 *
 * @param {string|number} whmcsClientId
 * @param {number} inputTokens  — tracked for logging only, NOT counted toward plan
 * @param {number} outputTokens — counted toward user plan limit (after multiplier)
 * @param {number} [tokenMultiplier=1] — model-specific multiplier (e.g. Opus=5, Sonnet=3, economy=0.5)
 * @returns {Promise<{used: number, limit: number, percentUsed: number}>}
 */
async function addUsage(whmcsClientId, inputTokens, outputTokens, tokenMultiplier = 1) {
  const redis = getRedis();
  const key = `tokens:used:${whmcsClientId}`;
  // Apply model multiplier — expensive models burn plan tokens faster
  const billable = Math.round((outputTokens || 0) * tokenMultiplier);

  // SECURITY (VULN-R2-06): Atomic check-and-increment via Lua script
  // Prevents TOCTOU race where concurrent requests all pass the budget check
  // then collectively exceed the limit. Returns -1 if over limit.
  const limitKey = `tokens:limit:${whmcsClientId}`;
  const luaScript = `
    local used = tonumber(redis.call('GET', KEYS[1]) or '0')
    local limit = tonumber(redis.call('GET', KEYS[2]) or '0')
    if limit > 0 and used >= limit then return -1 end
    return redis.call('INCRBY', KEYS[1], ARGV[1])
  `;
  const newTotal = await redis.eval(luaScript, 2, key, limitKey, billable);
  if (newTotal === -1) {
    const limit = await getLimit(whmcsClientId);
    const used = parseInt((await redis.get(key)) || '0', 10);
    return { used, limit, percentUsed: 100, overLimit: true };
  }

  // Ensure the key expires at the end of the billing period (safety net — 35 days)
  const ttl = await redis.ttl(key);
  if (ttl < 0) {
    await redis.expire(key, 35 * 24 * 60 * 60);
  }

  const limit = await getLimit(whmcsClientId);
  const percentUsed = limit > 0 ? Math.round((newTotal / limit) * 100) : 0;

  logger.debug(`tokens: client ${whmcsClientId} plan usage ${newTotal}/${limit} (${percentUsed}%) — billed ${billable} plan tokens (${outputTokens || 0} output × ${tokenMultiplier}x, ignored ${inputTokens || 0} input)`);
  return { used: newTotal, limit, percentUsed };
}

/**
 * Get current usage for a customer.
 *
 * @param {string|number} whmcsClientId
 * @returns {Promise<{used: number, limit: number, percentUsed: number, withinLimit: boolean}>}
 */
async function getUsage(whmcsClientId) {
  const redis = getRedis();
  const used = parseInt((await redis.get(`tokens:used:${whmcsClientId}`)) || '0', 10);
  const limit = await getLimit(whmcsClientId);
  const percentUsed = limit > 0 ? Math.round((used / limit) * 100) : 0;
  const withinLimit = limit === 0 || used < limit;

  return { used, limit, percentUsed, withinLimit };
}

/**
 * Get the token limit for a customer (stored in Redis when provisioned).
 * Returns 0 if no limit is set (unlimited — admin accounts).
 *
 * @param {string|number} whmcsClientId
 * @returns {Promise<number>}
 */
async function getLimit(whmcsClientId) {
  const redis = getRedis();
  const stored = await redis.get(`tokens:limit:${whmcsClientId}`);
  return parseInt(stored || '0', 10);
}

/**
 * Set the token limit for a customer (called during WHMCS provisioning/upgrade).
 *
 * @param {string|number} whmcsClientId
 * @param {string} planName  - 'builder' | 'professional' | 'studio' | 'business' | 'enterprise'
 * @returns {Promise<number>}  The limit that was set
 */
async function setLimit(whmcsClientId, planName) {
  const redis = getRedis();
  const limit = config.tokenLimits[planName.toLowerCase()] || config.tokenLimits.professional;
  await redis.set(`tokens:limit:${whmcsClientId}`, limit);
  logger.info(`tokens: set limit for client ${whmcsClientId} → ${limit} (plan: ${planName})`);
  return limit;
}

/**
 * Add bonus tokens (from a top-up purchase).
 *
 * @param {string|number} whmcsClientId
 * @param {number} bonusTokens
 * @returns {Promise<number>}  New limit
 */
async function addTopUp(whmcsClientId, bonusTokens) {
  const redis = getRedis();
  const currentLimit = await getLimit(whmcsClientId);
  const newLimit = currentLimit + bonusTokens;
  await redis.set(`tokens:limit:${whmcsClientId}`, newLimit);
  logger.info(`tokens: top-up ${bonusTokens} tokens for client ${whmcsClientId} → new limit ${newLimit}`);
  return newLimit;
}

/**
 * Reset usage counter (called on billing cycle renewal via WHMCS webhook).
 *
 * @param {string|number} whmcsClientId
 * @returns {Promise<void>}
 */
async function resetUsage(whmcsClientId) {
  const redis = getRedis();
  await redis.set(`tokens:used:${whmcsClientId}`, 0);
  logger.info(`tokens: reset usage counter for client ${whmcsClientId}`);
}

/**
 * Check whether a customer is allowed to make a Claude API call.
 *
 * Overage model:
 *   - Free plan (limit ≤ 50000): HARD BLOCK at 100%. Must upgrade.
 *   - Paid plans: soft limit. At 100%, switches to overage mode.
 *     Overage tokens tracked in Redis — billed at $2/100K tokens.
 *     Hard cap at 200% of plan limit (safety net).
 *
 * @param {string|number} whmcsClientId
 * @returns {Promise<{allowed: boolean, reason?: string, overage?: boolean, usage: object}>}
 */
async function checkAllowance(whmcsClientId) {
  const usage = await getUsage(whmcsClientId);
  const redis = getRedis();

  if (!usage.withinLimit) {
    // Free plan → hard block, no overage
    if (usage.limit <= 50000) {
      return { allowed: false, reason: 'free_plan_limit_reached', usage };
    }

    // One-time monthly grace bucket for paid plans (soft landing)
    // Allows users slightly over limit to keep going once per cycle.
    if (GRACE_TOKENS_DEFAULT > 0) {
      const month = utcMonth();
      const graceKey = `tokens:grace:used:${whmcsClientId}:${month}`;
      const graceUsed = parseInt((await redis.get(graceKey)) || '0', 10);
      const over = Math.max(0, usage.used - usage.limit);

      if (over < GRACE_TOKENS_DEFAULT && graceUsed < GRACE_TOKENS_DEFAULT) {
        const grantRemaining = GRACE_TOKENS_DEFAULT - graceUsed;
        const consume = Math.min(grantRemaining, GRACE_TOKENS_DEFAULT - over);
        if (consume > 0) {
          await redis.incrby(graceKey, consume);
          await redis.expire(graceKey, 40 * 24 * 60 * 60);
          return {
            allowed: true,
            warning: 'grace_active',
            grace: { monthly: GRACE_TOKENS_DEFAULT, used: graceUsed + consume, remaining: Math.max(0, GRACE_TOKENS_DEFAULT - (graceUsed + consume)) },
            usage,
          };
        }
      }
    }

    // Paid plan → allow overage up to 200% of plan limit
    const overageLimit = usage.limit * 2;
    if (usage.used >= overageLimit) {
      return { allowed: false, reason: 'overage_cap_reached', overage: true, usage };
    }

    // Track overage tokens
    const overageTokens = usage.used - usage.limit;
    return {
      allowed: true,
      warning: 'in_overage',
      overage: true,
      overageTokens,
      usage,
    };
  }

  // Warning flags (informational only — does NOT affect allowed)
  if (usage.percentUsed >= 90) {
    return { allowed: true, warning: 'critical', usage };
  }
  if (usage.percentUsed >= 80) {
    return { allowed: true, warning: 'approaching_limit', usage };
  }
  if (usage.percentUsed >= 50) {
    return { allowed: true, warning: 'halfway', usage };
  }

  return { allowed: true, usage };
}

/**
 * Get overage details for a client (for billing/dashboard).
 * Overage rate: $2.00 per 100K tokens ($0.00002 per token).
 *
 * @param {string|number} whmcsClientId
 * @returns {Promise<{overageTokens: number, overageCost: number, inOverage: boolean}>}
 */
async function getOverageDetails(whmcsClientId) {
  const usage = await getUsage(whmcsClientId);
  const overageTokens = Math.max(0, usage.used - usage.limit);
  const overageCost = (overageTokens / 100000) * 2.00; // $2 per 100K
  return {
    overageTokens,
    overageCost: Math.round(overageCost * 100) / 100, // round to cents
    inOverage: overageTokens > 0,
    planLimit: usage.limit,
    totalUsed: usage.used,
  };
}

module.exports = { addUsage, getUsage, getLimit, setLimit, addTopUp, resetUsage, checkAllowance, getOverageDetails };
