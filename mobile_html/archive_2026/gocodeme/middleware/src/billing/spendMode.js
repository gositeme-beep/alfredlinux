'use strict';

const { getRedis } = require('../redis');

const VALID_SPEND_MODES = ['economy', 'balanced', 'power'];
const DEFAULT_SPEND_MODE = (process.env.DEFAULT_SPEND_MODE || 'balanced').toLowerCase();

const SPEND_MODE_MULTIPLIERS = {
  economy: parseFloat(process.env.SPEND_MODE_MULTIPLIER_ECONOMY || '0.75'),
  balanced: parseFloat(process.env.SPEND_MODE_MULTIPLIER_BALANCED || '1.0'),
  power: parseFloat(process.env.SPEND_MODE_MULTIPLIER_POWER || '3.0'),
};

const SPEND_MODE_MODEL_POLICY = {
  economy: {
    allowedTiers: new Set(['free', 'economy']),
    fallbackModelKey: process.env.SPEND_MODE_FALLBACK_ECONOMY || 'qwen3-coder',
  },
  balanced: {
    allowedTiers: new Set(['free', 'economy', 'standard']),
    fallbackModelKey: process.env.SPEND_MODE_FALLBACK_BALANCED || 'claude-sonnet-4-6',
  },
  power: {
    allowedTiers: new Set(['free', 'economy', 'standard', 'premium']),
    fallbackModelKey: process.env.SPEND_MODE_FALLBACK_POWER || 'claude-opus-4-6',
  },
};

function normalizeSpendMode(mode) {
  const m = String(mode || '').toLowerCase().trim();
  if (VALID_SPEND_MODES.includes(m)) return m;
  return VALID_SPEND_MODES.includes(DEFAULT_SPEND_MODE) ? DEFAULT_SPEND_MODE : 'balanced';
}

function getSpendModeMultiplier(mode) {
  const m = normalizeSpendMode(mode);
  const raw = SPEND_MODE_MULTIPLIERS[m];
  const parsed = Number.isFinite(raw) ? raw : 1;
  return parsed > 0 ? parsed : 1;
}

async function getSpendMode(clientId) {
  try {
    const redis = getRedis();
    const raw = await redis.get(`spend_mode:${clientId}`);
    return normalizeSpendMode(raw);
  } catch (_) {
    return normalizeSpendMode();
  }
}

const SPEND_MODE_COOLDOWN = parseInt(process.env.SPEND_MODE_COOLDOWN || '60', 10);

async function setSpendMode(clientId, mode) {
  const normalized = normalizeSpendMode(mode);
  if (!VALID_SPEND_MODES.includes(normalized)) {
    throw new Error('Invalid spend mode');
  }
  const redis = getRedis();

  // Cooldown: prevent rapid mode flipping (exploitable for cap manipulation)
  const cooldownKey = `spend_mode:cooldown:${clientId}`;
  const cooldownActive = await redis.get(cooldownKey);
  if (cooldownActive) {
    const ttl = await redis.ttl(cooldownKey);
    const err = new Error(`Spend mode cooldown: wait ${ttl > 0 ? ttl : SPEND_MODE_COOLDOWN}s before switching again`);
    err.code = 'COOLDOWN';
    err.retryAfter = ttl > 0 ? ttl : SPEND_MODE_COOLDOWN;
    throw err;
  }

  await redis.set(`spend_mode:${clientId}`, normalized);
  await redis.set(cooldownKey, '1', 'EX', SPEND_MODE_COOLDOWN);
  return normalized;
}

function enforceModelForSpendMode(model, spendMode, getModelByKey) {
  const mode = normalizeSpendMode(spendMode);
  const policy = SPEND_MODE_MODEL_POLICY[mode] || SPEND_MODE_MODEL_POLICY.balanced;
  const tier = String(model?.tier || 'standard').toLowerCase();

  if (policy.allowedTiers.has(tier)) {
    return { model, downgraded: false, mode, reason: null };
  }

  const fallback = typeof getModelByKey === 'function' ? getModelByKey(policy.fallbackModelKey) : null;
  if (fallback) {
    return {
      model: fallback,
      downgraded: true,
      mode,
      reason: `spend-mode:${mode}:${tier}->${fallback.modelId}`,
    };
  }

  return { model, downgraded: false, mode, reason: null };
}

module.exports = {
  VALID_SPEND_MODES,
  SPEND_MODE_MULTIPLIERS,
  SPEND_MODE_COOLDOWN,
  normalizeSpendMode,
  getSpendModeMultiplier,
  getSpendMode,
  setSpendMode,
  enforceModelForSpendMode,
};
