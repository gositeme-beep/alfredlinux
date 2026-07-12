'use strict';

/**
 * tokenBudget.js — Hard per-user and global token spend caps
 *
 * Enforces FIVE layers of protection (all are hard stops):
 *
 *  1. GLOBAL CIRCUIT BREAKER (monthly)
 *     If total Anthropic spend across ALL users in the current calendar month
 *     exceeds GLOBAL_MONTHLY_USD_CAP (default $200), ALL requests are blocked
 *     until the admin resets it.
 *
 *  2. GLOBAL DAILY USD CAP
 *     If total Anthropic spend across ALL users TODAY exceeds
 *     GLOBAL_DAILY_USD_CAP (default $25), ALL requests are blocked until
 *     midnight UTC. Prevents one bad day from eating the whole month.
 *
 *  3. PER-USER DAILY USD CAP
 *     No single user can spend more than USER_DAILY_USD_CAP (default $10) in
 *     actual Anthropic cost per calendar day (UTC). This is the KEY control —
 *     input tokens are the real cost driver and this catches runaway sessions.
 *
 *  4. PER-USER DAILY TOKEN CAP
 *     No single user can burn more than DAILY_TOKEN_CAP combined tokens in one
 *     calendar day (UTC). Default: 500,000 tokens/day. Secondary guard.
 *
 *  5. PER-REQUEST MAX TOKENS
 *     Any single API call estimated > REQUEST_MAX_TOKENS is rejected before it
 *     reaches Anthropic. Default: 150,000 tokens.
 *
 * Redis keys:
 *   budget:global:spend:{YYYY-MM}           → float, total USD spent this month
 *   budget:global:tripped                   → "1" if circuit breaker tripped
 *   budget:global:daily:{YYYY-MM-DD}        → float, total USD spent today (all users)
 *   budget:daily:usd:{clientId}:{YYYY-MM-DD} → float, USD spent today (per user)
 *   budget:daily:{clientId}:{YYYY-MM-DD}    → integer, tokens used today
 *
 * All keys auto-expire (global: 40 days, daily: 2 days).
 */

const { getRedis } = require('../redis');
const logger = require('../logger');
const config = require('../config');
const { getSpendMode, getSpendModeMultiplier } = require('../billing/spendMode');

// ── Configurable limits (override in .env) ─────────────────────────────────
const GLOBAL_MONTHLY_USD_CAP  = parseFloat(process.env.GLOBAL_MONTHLY_USD_CAP  || '200');
const GLOBAL_DAILY_USD_CAP    = parseFloat(process.env.GLOBAL_DAILY_USD_CAP    || '25');
const USER_DAILY_USD_CAP      = parseFloat(process.env.USER_DAILY_USD_CAP      || '10');
const DAILY_TOKEN_CAP         = parseInt(process.env.DAILY_TOKEN_CAP            || '500000', 10);
const REQUEST_MAX_TOKENS      = parseInt(process.env.REQUEST_MAX_TOKENS         || '150000', 10);
const MONTHLY_OVERAGE_CAP_PCT = parseFloat(process.env.MONTHLY_OVERAGE_CAP_PCT || '110');

const OWNER_CLIENT_ID = parseInt(process.env.OWNER_CLIENT_ID || '33', 10);
const ADMIN_BYPASS_CLIENT_IDS = new Set(
  (process.env.ADMIN_BYPASS_CLIENT_IDS || String(OWNER_CLIENT_ID))
    .split(',')
    .map(s => s.trim())
    .filter(Boolean)
);

// Optional plan-sensitive daily USD caps (fallback remains USER_DAILY_USD_CAP)
const USER_DAILY_USD_CAP_FREE         = parseFloat(process.env.USER_DAILY_USD_CAP_FREE         || String(USER_DAILY_USD_CAP));
const USER_DAILY_USD_CAP_BUILDER      = parseFloat(process.env.USER_DAILY_USD_CAP_BUILDER      || '20');
const USER_DAILY_USD_CAP_PROFESSIONAL = parseFloat(process.env.USER_DAILY_USD_CAP_PROFESSIONAL || '40');
const USER_DAILY_USD_CAP_STUDIO       = parseFloat(process.env.USER_DAILY_USD_CAP_STUDIO       || '80');
const USER_DAILY_USD_CAP_BUSINESS     = parseFloat(process.env.USER_DAILY_USD_CAP_BUSINESS     || '150');
const USER_DAILY_USD_CAP_ENTERPRISE   = parseFloat(process.env.USER_DAILY_USD_CAP_ENTERPRISE   || '300');

const TTL_40_DAYS = 40 * 24 * 60 * 60;
const TTL_2_DAYS  =  2 * 24 * 60 * 60;

// ── Helpers ────────────────────────────────────────────────────────────────

function utcDate() {
  return new Date().toISOString().slice(0, 10); // YYYY-MM-DD
}

function utcMonth() {
  return new Date().toISOString().slice(0, 7); // YYYY-MM
}

function isAdminBypassClient(clientId) {
  return ADMIN_BYPASS_CLIENT_IDS.has(String(clientId));
}

async function getUserDailyUsdCap(clientId) {
  if (isAdminBypassClient(clientId)) {
    return Infinity;
  }

  const redis = getRedis();
  const rawLimit = await redis.get(`tokens:limit:${clientId}`);
  const planLimit = parseInt(rawLimit || '0', 10);

  // 0 means unlimited in tokenCounter (typically admin/unlimited accounts)
  if (planLimit === 0) {
    return Infinity;
  }

  const overrideRaw = await redis.get(`budget:daily:usd:override:${clientId}`);
  const override = parseFloat(overrideRaw || '0');
  if (Number.isFinite(override) && override > 0) {
    return override;
  }

  const limits = config.tokenLimits || {};
  let baseCap;
  if (planLimit <= (limits.free || 50000)) baseCap = USER_DAILY_USD_CAP_FREE;
  else if (planLimit <= (limits.builder || 300000)) baseCap = USER_DAILY_USD_CAP_BUILDER;
  else if (planLimit <= (limits.professional || 600000)) baseCap = USER_DAILY_USD_CAP_PROFESSIONAL;
  else if (planLimit <= (limits.studio || 1500000)) baseCap = USER_DAILY_USD_CAP_STUDIO;
  else if (planLimit <= (limits.business || 3000000)) baseCap = USER_DAILY_USD_CAP_BUSINESS;
  else baseCap = USER_DAILY_USD_CAP_ENTERPRISE;

  const spendMode = await getSpendMode(clientId);
  const multiplier = getSpendModeMultiplier(spendMode);
  const effective = baseCap * multiplier;
  return Math.max(1, Math.round(effective * 100) / 100);
}

async function getUserDailyUsdCapDetails(clientId) {
  const redis = getRedis();
  const bypass = isAdminBypassClient(clientId);
  const spendMode = await getSpendMode(clientId);
  const multiplier = getSpendModeMultiplier(spendMode);
  const overrideRaw = await redis.get(`budget:daily:usd:override:${clientId}`);
  const override = parseFloat(overrideRaw || '0');

  if (bypass) {
    return {
      bypass: true,
      spendMode,
      multiplier,
      override: Number.isFinite(override) && override > 0 ? override : null,
      effectiveCap: 'unlimited',
    };
  }

  const effectiveCap = await getUserDailyUsdCap(clientId);
  return {
    bypass: false,
    spendMode,
    multiplier,
    override: Number.isFinite(override) && override > 0 ? override : null,
    effectiveCap,
  };
}

async function setUserDailyUsdCapOverride(clientId, capUsd) {
  const parsed = parseFloat(String(capUsd));
  if (!Number.isFinite(parsed) || parsed <= 0) {
    throw new Error('dailyUsdCapOverride must be a positive number');
  }
  const redis = getRedis();
  await redis.set(`budget:daily:usd:override:${clientId}`, String(Math.round(parsed * 100) / 100));
  return Math.round(parsed * 100) / 100;
}

async function clearUserDailyUsdCapOverride(clientId) {
  const redis = getRedis();
  await redis.del(`budget:daily:usd:override:${clientId}`);
}

// ── 1. Global circuit breaker ───────────────────────────────────────────────

/**
 * Record actual USD cost for a completed request.
 * If cumulative monthly spend exceeds GLOBAL_MONTHLY_USD_CAP, trip the breaker.
 *
 * @param {number} costUsd
 * @returns {Promise<{tripped: boolean, totalSpend: number, cap: number}>}
 */
async function recordGlobalSpend(costUsd) {
  if (!costUsd || costUsd <= 0) return { tripped: false, totalSpend: 0, cap: GLOBAL_MONTHLY_USD_CAP };

  const redis = getRedis();
  const key   = `budget:global:spend:${utcMonth()}`;

  // INCRBYFLOAT — atomic
  const newTotal = parseFloat(await redis.incrbyfloat(key, costUsd));
  await redis.expire(key, TTL_40_DAYS);

  if (newTotal >= GLOBAL_MONTHLY_USD_CAP) {
    const alreadyTripped = await redis.get('budget:global:tripped');
    if (!alreadyTripped) {
      await redis.setex('budget:global:tripped', TTL_40_DAYS, '1');
      logger.error(`[tokenBudget] ⚡ GLOBAL CIRCUIT BREAKER TRIPPED — monthly spend $${newTotal.toFixed(2)} >= cap $${GLOBAL_MONTHLY_USD_CAP}`);
    }
    return { tripped: true, totalSpend: newTotal, cap: GLOBAL_MONTHLY_USD_CAP };
  }

  logger.debug(`[tokenBudget] global spend this month: $${newTotal.toFixed(4)} / $${GLOBAL_MONTHLY_USD_CAP}`);
  return { tripped: false, totalSpend: newTotal, cap: GLOBAL_MONTHLY_USD_CAP };
}

/**
 * Check if global circuit breaker is tripped.
 * @returns {Promise<boolean>}
 */
async function isGlobalTripped() {
  const redis = getRedis();
  const val = await redis.get('budget:global:tripped');
  return val === '1';
}

/**
 * Admin reset: clear the global circuit breaker.
 * @returns {Promise<void>}
 */
async function resetGlobalBreaker() {
  const redis = getRedis();
  await redis.del('budget:global:tripped');
  logger.info('[tokenBudget] Global circuit breaker manually reset by admin');
}

/**
 * Get current global monthly spend.
 * @returns {Promise<{spend: number, cap: number, tripped: boolean, pct: number}>}
 */
async function getGlobalSpend() {
  const redis = getRedis();
  const spend = parseFloat((await redis.get(`budget:global:spend:${utcMonth()}`)) || '0');
  const tripped = await isGlobalTripped();
  return {
    spend,
    cap: GLOBAL_MONTHLY_USD_CAP,
    tripped,
    pct: GLOBAL_MONTHLY_USD_CAP > 0 ? Math.round((spend / GLOBAL_MONTHLY_USD_CAP) * 100) : 0,
  };
}

// ── 2. Global daily USD cap ────────────────────────────────────────────────

/**
 * Check if ALL users combined have exceeded the global daily USD cap.
 * This prevents a single bad day from eating the entire monthly budget.
 *
 * @returns {Promise<{allowed: boolean, spent: number, cap: number}>}
 */
async function checkGlobalDailyUsd() {
  const redis = getRedis();
  const key   = `budget:global:daily:${utcDate()}`;
  const spent = parseFloat((await redis.get(key)) || '0');

  if (spent >= GLOBAL_DAILY_USD_CAP) {
    return { allowed: false, spent, cap: GLOBAL_DAILY_USD_CAP };
  }
  return { allowed: true, spent, cap: GLOBAL_DAILY_USD_CAP };
}

/**
 * Record USD spent today (all users combined).
 * @param {number} costUsd
 * @returns {Promise<number>} new daily total
 */
async function recordGlobalDailySpend(costUsd) {
  if (!costUsd || costUsd <= 0) return 0;
  const redis = getRedis();
  const key   = `budget:global:daily:${utcDate()}`;
  const newTotal = parseFloat(await redis.incrbyfloat(key, costUsd));
  await redis.expire(key, TTL_2_DAYS);

  if (newTotal >= GLOBAL_DAILY_USD_CAP) {
    logger.error(`[tokenBudget] ⚡ GLOBAL DAILY USD CAP HIT — $${newTotal.toFixed(2)} today >= cap $${GLOBAL_DAILY_USD_CAP}`);
  }
  return newTotal;
}

// ── 3. Per-user daily USD cap ──────────────────────────────────────────────

/**
 * Check if a single user has exceeded their daily USD cap.
 * This is the KEY protection — prevents any one user from burning through
 * excessive Anthropic spend in a single day (input tokens are expensive).
 *
 * @param {string|number} clientId
 * @returns {Promise<{allowed: boolean, spent: number, cap: number}>}
 */
async function checkUserDailyUsd(clientId) {
  const redis = getRedis();
  const key   = `budget:daily:usd:${clientId}:${utcDate()}`;
  const spent = parseFloat((await redis.get(key)) || '0');
  const cap   = await getUserDailyUsdCap(clientId);

  if (cap === Infinity) {
    return { allowed: true, spent, cap: 'unlimited', bypass: true };
  }

  if (spent >= cap) {
    return { allowed: false, spent, cap };
  }
  return { allowed: true, spent, cap };
}

/**
 * Record USD spent today for a specific user.
 * @param {string|number} clientId
 * @param {number} costUsd
 * @returns {Promise<number>} new daily total
 */
async function recordUserDailySpend(clientId, costUsd) {
  if (!costUsd || costUsd <= 0) return 0;
  const cap = await getUserDailyUsdCap(clientId);

  const redis = getRedis();
  const key   = `budget:daily:usd:${clientId}:${utcDate()}`;
  const newTotal = parseFloat(await redis.incrbyfloat(key, costUsd));
  await redis.expire(key, TTL_2_DAYS);

  if (cap !== Infinity && newTotal >= cap) {
    logger.warn(`[tokenBudget] User ${clientId} hit daily USD cap — $${newTotal.toFixed(2)} >= $${cap}`);
  }
  return newTotal;
}

// ── 4. Per-user daily token cap ────────────────────────────────────────────

/**
 * Check if a user has hit their daily token cap (output tokens).
 * Exempt: users with limit=0 (admin/unlimited).
 *
 * @param {string|number} clientId
 * @param {number} planLimit  — monthly token limit (0 = unlimited)
 * @returns {Promise<{allowed: boolean, used: number, cap: number}>}
 */
async function checkDailyCap(clientId, planLimit) {
  if (planLimit === 0) return { allowed: true, used: 0, cap: 0 }; // unlimited

  const redis = getRedis();
  const key   = `budget:daily:${clientId}:${utcDate()}`;
  const used  = parseInt((await redis.get(key)) || '0', 10);

  if (used >= DAILY_TOKEN_CAP) {
    return { allowed: false, used, cap: DAILY_TOKEN_CAP };
  }
  return { allowed: true, used, cap: DAILY_TOKEN_CAP };
}

/**
 * Record tokens used today for a user.
 *
 * @param {string|number} clientId
 * @param {number} tokens
 * @returns {Promise<number>} new daily total
 */
async function recordDailyUsage(clientId, tokens) {
  const redis = getRedis();
  const key   = `budget:daily:${clientId}:${utcDate()}`;
  const newTotal = await redis.incrby(key, tokens);
  await redis.expire(key, TTL_2_DAYS);
  return newTotal;
}

/**
 * Get today's usage for a user.
 * @param {string|number} clientId
 * @returns {Promise<{used: number, cap: number, pct: number}>}
 */
async function getDailyUsage(clientId) {
  const redis = getRedis();
  const key   = `budget:daily:${clientId}:${utcDate()}`;
  const used  = parseInt((await redis.get(key)) || '0', 10);
  return {
    used,
    cap: DAILY_TOKEN_CAP,
    pct: DAILY_TOKEN_CAP > 0 ? Math.round((used / DAILY_TOKEN_CAP) * 100) : 0,
  };
}

// ── 3. Per-request token estimation ───────────────────────────────────────

/**
 * Estimate token count from a raw request body string.
 * Uses the same rough chars/4 heuristic as the conversation pruner.
 *
 * @param {string} bodyStr — JSON-serialised request body
 * @returns {number} estimated tokens
 */
function estimateRequestTokens(bodyStr) {
  return Math.ceil((bodyStr || '').length / 4);
}

/**
 * Check if an estimated request size exceeds the per-request cap.
 *
 * @param {string} bodyStr
 * @returns {{allowed: boolean, estimated: number, cap: number}}
 */
function checkRequestSize(bodyStr) {
  const estimated = estimateRequestTokens(bodyStr);
  return {
    allowed:   estimated <= REQUEST_MAX_TOKENS,
    estimated,
    cap:       REQUEST_MAX_TOKENS,
  };
}

// ── 4. Monthly overage hard-block threshold ────────────────────────────────

/**
 * Returns true if the user should be hard-blocked based on their monthly
 * overage percentage. Replaces the 200% threshold in anthropicProxy.js
 * with the configurable MONTHLY_OVERAGE_CAP_PCT (default 110%).
 *
 * @param {number} percentUsed
 * @returns {boolean}
 */
function isOverHardBlock(percentUsed) {
  return percentUsed >= MONTHLY_OVERAGE_CAP_PCT;
}

// ── Admin helpers ──────────────────────────────────────────────────────────

// ── 5. Prepaid Credit Balance ──────────────────────────────────────────────
//
// Redis keys:
//   credits:balance:{clientId}    → float, current USD credit balance
//   credits:required              → "1" if credit-gating is enabled globally
//   credits:exempt:{clientId}     → "1" if this user is exempt from credit check
//
// Credits are deducted AFTER each request (same as other billing).
// Pre-request check: if credits:required is set and balance <= 0, block.

const CREDIT_REQUIRED = process.env.CREDIT_REQUIRED === '1'; // env override

/**
 * Check if user has sufficient credit balance to proceed.
 * Returns { allowed, balance, required } 
 */
async function checkCreditBalance(clientId) {
  if (isAdminBypassClient(clientId)) return { allowed: true, balance: Infinity, required: false };

  const redis = getRedis();

  // Check if credit-gating is enabled
  const globalRequired = CREDIT_REQUIRED || (await redis.get('credits:required')) === '1';
  if (!globalRequired) return { allowed: true, balance: null, required: false };

  // Check exemptions
  const exempt = await redis.get(`credits:exempt:${clientId}`);
  if (exempt === '1') return { allowed: true, balance: null, required: false };

  // Check balance
  const raw = await redis.get(`credits:balance:${clientId}`);
  const balance = parseFloat(raw || '0');

  return {
    allowed: balance > 0,
    balance: Math.round(balance * 100) / 100,
    required: true,
  };
}

/**
 * Deduct credit after a request completes.
 * @param {string|number} clientId
 * @param {number} costUsd — actual cost of the request
 * @returns {Promise<{newBalance: number, deducted: number}>}
 */
async function deductCredit(clientId, costUsd) {
  if (!costUsd || costUsd <= 0 || isAdminBypassClient(clientId)) {
    return { newBalance: 0, deducted: 0 };
  }
  const redis = getRedis();
  const newBalance = parseFloat(await redis.incrbyfloat(`credits:balance:${clientId}`, -costUsd));
  return { newBalance: Math.round(newBalance * 100) / 100, deducted: costUsd };
}

/**
 * Add credits to a user's balance.
 * @param {string|number} clientId
 * @param {number} amountUsd
 * @returns {Promise<{newBalance: number}>}
 */
async function addCredit(clientId, amountUsd) {
  if (!amountUsd || amountUsd <= 0) return { newBalance: 0 };
  const redis = getRedis();
  const newBalance = parseFloat(await redis.incrbyfloat(`credits:balance:${clientId}`, amountUsd));
  return { newBalance: Math.round(newBalance * 100) / 100 };
}

/**
 * Get current credit balance.
 */
async function getCreditBalance(clientId) {
  const redis = getRedis();
  const raw = await redis.get(`credits:balance:${clientId}`);
  return Math.round(parseFloat(raw || '0') * 100) / 100;
}

// ── Auto-replenish ─────────────────────────────────────────────────────────
// Users can set a threshold: when credits drop below $X, auto-add $Y.
// Redis keys:
//   credits:autoreplenish:{clientId} → JSON { threshold, amount, enabled }

async function getAutoReplenish(clientId) {
  const redis = getRedis();
  const raw = await redis.get(`credits:autoreplenish:${clientId}`);
  if (!raw) return { enabled: false, threshold: 0, amount: 0 };
  try {
    return JSON.parse(raw);
  } catch { return { enabled: false, threshold: 0, amount: 0 }; }
}

async function setAutoReplenish(clientId, { threshold, amount, enabled }) {
  const redis = getRedis();
  const settings = {
    enabled: !!enabled,
    threshold: Math.max(0, Math.min(parseFloat(threshold) || 0, 1000)),
    amount: Math.max(0, Math.min(parseFloat(amount) || 0, 10000)),
  };
  await redis.set(`credits:autoreplenish:${clientId}`, JSON.stringify(settings));
  return settings;
}

// Check if auto-replenish should trigger (called after deductCredit)
async function checkAutoReplenish(clientId) {
  const settings = await getAutoReplenish(clientId);
  if (!settings.enabled || settings.threshold <= 0 || settings.amount <= 0) return null;

  const balance = await getCreditBalance(clientId);
  if (balance < settings.threshold) {
    // Prevent double-trigger with a lock
    const redis = getRedis();
    const lockKey = `credits:autoreplenish:lock:${clientId}`;
    const locked = await redis.set(lockKey, '1', 'EX', 300, 'NX'); // 5min lock
    if (!locked) return null;

    const result = await addCredit(clientId, settings.amount);
    logger.info(`auto-replenish: client ${clientId} — balance $${balance} < threshold $${settings.threshold}, added $${settings.amount} → new balance $${result.newBalance}`);
    return { triggered: true, added: settings.amount, newBalance: result.newBalance };
  }
  return null;
}

// ── Account freeze ─────────────────────────────────────────────────────────
// Owner can freeze an account to block all API access immediately.

async function freezeAccount(clientId) {
  const redis = getRedis();
  await redis.set(`access:${clientId}`, 'suspended');
  logger.warn(`account-freeze: client ${clientId} FROZEN by admin`);
}

async function unfreezeAccount(clientId) {
  const redis = getRedis();
  await redis.del(`access:${clientId}`);
  logger.info(`account-unfreeze: client ${clientId} unfrozen by admin`);
}

async function isAccountFrozen(clientId) {
  const redis = getRedis();
  const status = await redis.get(`access:${clientId}`);
  return status === 'suspended' || status === 'terminated';
}

/**
 * Get a full budget summary for a user (for admin dashboard).
 */
async function getUserBudgetSummary(clientId, planLimit) {
  const redis = getRedis();
  const userDailyUsdKey = `budget:daily:usd:${clientId}:${utcDate()}`;
  const globalDailyUsdKey = `budget:global:daily:${utcDate()}`;

  const [daily, global, userDailyUsd, globalDailyUsd] = await Promise.all([
    getDailyUsage(clientId),
    getGlobalSpend(),
    redis.get(userDailyUsdKey).then(v => parseFloat(v || '0')),
    redis.get(globalDailyUsdKey).then(v => parseFloat(v || '0')),
  ]);
  const userCap = await getUserDailyUsdCap(clientId);

  return {
    daily,
    global,
    userDailyUsd:    { spent: userDailyUsd, cap: userCap === Infinity ? 'unlimited' : userCap },
    globalDailyUsd:  { spent: globalDailyUsd, cap: GLOBAL_DAILY_USD_CAP },
    hardBlockPct:    MONTHLY_OVERAGE_CAP_PCT,
    requestCap:      REQUEST_MAX_TOKENS,
  };
}

module.exports = {
  // Global monthly
  recordGlobalSpend,
  isGlobalTripped,
  resetGlobalBreaker,
  getGlobalSpend,
  // Global daily USD
  checkGlobalDailyUsd,
  recordGlobalDailySpend,
  // Per-user daily USD
  checkUserDailyUsd,
  recordUserDailySpend,
  getUserDailyUsdCap,
  getUserDailyUsdCapDetails,
  setUserDailyUsdCapOverride,
  clearUserDailyUsdCapOverride,
  // Per-user daily tokens
  checkDailyCap,
  recordDailyUsage,
  getDailyUsage,
  // Request
  checkRequestSize,
  estimateRequestTokens,
  // Monthly
  isOverHardBlock,
  MONTHLY_OVERAGE_CAP_PCT,
  // Summary
  getUserBudgetSummary,
  // Credits
  checkCreditBalance,
  deductCredit,
  addCredit,
  getCreditBalance,
  // Auto-replenish
  getAutoReplenish,
  setAutoReplenish,
  checkAutoReplenish,
  // Account control
  freezeAccount,
  unfreezeAccount,
  isAccountFrozen,
  // Admin bypass
  isAdminBypassClient,
  // Constants (for tests/admin)
  GLOBAL_MONTHLY_USD_CAP,
  GLOBAL_DAILY_USD_CAP,
  USER_DAILY_USD_CAP,
  DAILY_TOKEN_CAP,
  REQUEST_MAX_TOKENS,
};
