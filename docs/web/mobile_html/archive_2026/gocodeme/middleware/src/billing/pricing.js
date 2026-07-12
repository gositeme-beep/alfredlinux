'use strict';

/**
 * billing/pricing.js — Per-model token pricing engine
 *
 * Maps Anthropic model IDs to per-token costs (USD).
 * Prices are per 1M tokens (MTok) matching Anthropic's public pricing,
 * stored here as per-token (micro) values for computation.
 *
 * Pricing tiers:
 *   - Models included in plan (standard): no extra charge
 *   - Premium models: charged at on-demand rates when plan tokens run out
 *
 * This module is used by:
 *   1. anthropicProxy.js — to calculate cost per request
 *   2. usage dashboard — to show cost breakdown by model
 *   3. overage billing — to calculate per-token overage charges
 */

// ── Model pricing (per 1M tokens, in USD) ────────────────────────────────
// Source: https://platform.claude.com/docs/en/about-claude/pricing
// Last verified: 2026-02-27 from Anthropic API /v1/models + pricing page
const MODEL_PRICING = {
  // ── Claude Opus family (premium — most intelligent) ──────────────────
  'claude-opus-4-6': {
    displayName: 'Claude Opus 4.6',
    inputPer1M:  5.00,
    outputPer1M: 25.00,
    tier: 'premium',
  },
  'claude-opus-4-5-20251101': {
    displayName: 'Claude Opus 4.5',
    inputPer1M:  5.00,
    outputPer1M: 25.00,
    tier: 'premium',
  },
  'claude-opus-4-1-20250805': {
    displayName: 'Claude Opus 4.1',
    inputPer1M:  15.00,
    outputPer1M: 75.00,
    tier: 'premium',
  },
  'claude-opus-4-20250514': {
    displayName: 'Claude Opus 4',
    inputPer1M:  15.00,
    outputPer1M: 75.00,
    tier: 'premium',
  },

  // ── Claude Sonnet family (standard — best speed/intelligence) ────────
  'claude-sonnet-4-6': {
    displayName: 'Claude Sonnet 4.6',
    inputPer1M:  3.00,
    outputPer1M: 15.00,
    tier: 'standard',
  },
  'claude-sonnet-4-5-20250929': {
    displayName: 'Claude Sonnet 4.5',
    inputPer1M:  3.00,
    outputPer1M: 15.00,
    tier: 'standard',
  },
  'claude-sonnet-4-20250514': {
    displayName: 'Claude Sonnet 4',
    inputPer1M:  3.00,
    outputPer1M: 15.00,
    tier: 'standard',
  },

  // ── Claude Haiku family (standard — fastest) ────────────────────────
  'claude-haiku-4-5-20251001': {
    displayName: 'Claude Haiku 4.5',
    inputPer1M:  1.00,
    outputPer1M: 5.00,
    tier: 'standard',
  },

  // ── Legacy models (deprecated but may still be used by older sessions) ─
  'claude-3-5-haiku-20241022': {
    displayName: 'Claude 3.5 Haiku',
    inputPer1M:  0.80,
    outputPer1M: 4.00,
    tier: 'standard',
    deprecated: true,
  },
  'claude-3-opus-20240229': {
    displayName: 'Claude 3 Opus',
    inputPer1M:  15.00,
    outputPer1M: 75.00,
    tier: 'premium',
    deprecated: true,
  },
  'claude-3-haiku-20240307': {
    displayName: 'Claude 3 Haiku',
    inputPer1M:  0.25,
    outputPer1M: 1.25,
    tier: 'standard',
    deprecated: true,
  },

  // ── Together.ai models (OpenAI-compatible, economy tier) ─────────────
  'Qwen/Qwen3-Coder-Next-FP8': {
    displayName: 'Qwen3 Coder',
    inputPer1M:  0.50,
    outputPer1M: 1.20,
    tier: 'economy',
  },
  'deepseek-ai/DeepSeek-V3.1': {
    displayName: 'DeepSeek V3.1',
    inputPer1M:  0.60,
    outputPer1M: 1.70,
    tier: 'economy',
  },
  'zai-org/GLM-5': {
    displayName: 'GLM-5',
    inputPer1M:  1.00,
    outputPer1M: 3.20,
    tier: 'economy',
  },
  'moonshotai/Kimi-K2.5': {
    displayName: 'Kimi K2.5',
    inputPer1M:  0.50,
    outputPer1M: 2.80,
    tier: 'economy',
  },
  'meta-llama/Llama-4-Maverick-17B-128E-Instruct-FP8': {
    displayName: 'Llama 4 Maverick',
    inputPer1M:  0.27,
    outputPer1M: 0.85,
    tier: 'economy',
  },
  'Qwen/Qwen3-Coder-480B-A35B-Instruct-FP8': {
    displayName: 'Qwen3 Coder 480B',
    inputPer1M:  2.00,
    outputPer1M: 2.00,
    tier: 'standard',
  },
  'Qwen/Qwen3.5-397B-A17B': {
    displayName: 'Qwen 3.5',
    inputPer1M:  0.60,
    outputPer1M: 3.60,
    tier: 'economy',
  },
  'deepseek-ai/DeepSeek-R1': {
    displayName: 'DeepSeek R1',
    inputPer1M:  3.00,
    outputPer1M: 7.00,
    tier: 'premium',
  },
  'zai-org/GLM-4.6': {
    displayName: 'GLM-4.6',
    inputPer1M:  0.60,
    outputPer1M: 2.20,
    tier: 'economy',
  },
  'moonshotai/Kimi-K2-Thinking': {
    displayName: 'Kimi K2 Thinking',
    inputPer1M:  1.20,
    outputPer1M: 4.00,
    tier: 'standard',
  },
  'meta-llama/Llama-4-Scout-17B-16E-Instruct': {
    displayName: 'Llama 4 Scout',
    inputPer1M:  0.18,
    outputPer1M: 0.59,
    tier: 'economy',
  },
  'mistralai/Mistral-Small-24B-Instruct-2501': {
    displayName: 'Mistral Small',
    inputPer1M:  0.10,
    outputPer1M: 0.30,
    tier: 'economy',
  },

  // ── Google Gemini 3 models (latest, Feb/Mar 2026) ─────────────────────
  'gemini-3.1-pro-preview': {
    displayName: 'Gemini 3.1 Pro',
    inputPer1M:  2.00,
    outputPer1M: 12.00,
    tier: 'premium',
  },
  'gemini-3-flash-preview': {
    displayName: 'Gemini 3 Flash',
    inputPer1M:  0.50,
    outputPer1M: 3.00,
    tier: 'standard',
  },
  'gemini-3.1-flash-lite-preview': {
    displayName: 'Gemini 3.1 Flash Lite',
    inputPer1M:  0.25,
    outputPer1M: 1.50,
    tier: 'economy',
  },

  // ── Google Gemini Image Gen (Nano Banana 2) ────────────────────────────
  'gemini-3.1-flash-image-preview': {
    displayName: 'Gemini Image Gen',
    inputPer1M:  0.10,
    outputPer1M: 0.40,
    tier: 'economy',
  },

  // ── Google Gemini 2.5 stable ──────────────────────────────────────────
  'gemini-2.5-pro': {
    displayName: 'Gemini 2.5 Pro',
    inputPer1M:  1.25,
    outputPer1M: 10.00,
    tier: 'standard',
  },
  'gemini-2.5-flash': {
    displayName: 'Gemini 2.5 Flash',
    inputPer1M:  0.30,
    outputPer1M: 2.50,
    tier: 'economy',
  },
  'gemini-2.5-flash-lite': {
    displayName: 'Gemini 2.5 Flash Lite',
    inputPer1M:  0.10,
    outputPer1M: 0.40,
    tier: 'economy',
  },

  // ── Google Gemini 2.0 (deprecated, shutting down June 2026) ───────────
  'gemini-2.0-flash': {
    displayName: 'Gemini 2.0 Flash',
    inputPer1M:  0.10,
    outputPer1M: 0.40,
    tier: 'economy',
  },

  // ── xAI Grok models (OpenAI-compatible) ────────────────────────────────
  'grok-3': {
    displayName: 'Grok 3',
    inputPer1M:  0.00,
    outputPer1M: 0.00,
    tier: 'free',
  },
  'grok-3-mini': {
    displayName: 'Grok 3 Mini',
    inputPer1M:  0.00,
    outputPer1M: 0.00,
    tier: 'free',
  },

  // ── Video Generation (Together.ai) ─────────────────────────────────────
  'google/veo-2.0': {
    displayName: 'Veo 2 Video',
    inputPer1M:  0,
    outputPer1M: 0,
    flatRate: 0.50,
    tier: 'standard',
  },

  // ── Groq models (free tier) ──────────────────────────────────────────
  'llama-3.3-70b-versatile': {
    displayName: 'Llama 3.3 70B (Groq)',
    inputPer1M:  0.00,
    outputPer1M: 0.00,
    tier: 'free',
  },
  'llama-3.1-8b-instant': {
    displayName: 'Llama 3.1 8B (Groq)',
    inputPer1M:  0.00,
    outputPer1M: 0.00,
    tier: 'free',
  },

  // ── OpenAI models (native OpenAI format) ──────────────────────────────
  'gpt-4.1': {
    displayName: 'GPT-4.1',
    inputPer1M:  2.00,
    outputPer1M: 8.00,
    tier: 'standard',
  },
  'gpt-4.1-mini': {
    displayName: 'GPT-4.1 Mini',
    inputPer1M:  0.40,
    outputPer1M: 1.60,
    tier: 'economy',
  },
  'gpt-4.1-nano': {
    displayName: 'GPT-4.1 Nano',
    inputPer1M:  0.10,
    outputPer1M: 0.40,
    tier: 'economy',
  },
  'gpt-4o': {
    displayName: 'GPT-4o',
    inputPer1M:  2.50,
    outputPer1M: 10.00,
    tier: 'standard',
  },
  'gpt-4o-mini': {
    displayName: 'GPT-4o Mini',
    inputPer1M:  0.15,
    outputPer1M: 0.60,
    tier: 'economy',
  },
  'o3': {
    displayName: 'o3 (Reasoning)',
    inputPer1M:  2.00,
    outputPer1M: 8.00,
    tier: 'premium',
  },
  'o3-mini': {
    displayName: 'o3 Mini',
    inputPer1M:  1.10,
    outputPer1M: 4.40,
    tier: 'standard',
  },
  'o4-mini': {
    displayName: 'o4 Mini',
    inputPer1M:  1.10,
    outputPer1M: 4.40,
    tier: 'standard',
  },
};

// ── Fallback pricing for unknown models ──────────────────────────────────
const DEFAULT_PRICING = {
  displayName: 'Unknown Model',
  inputPer1M:  3.00,
  outputPer1M: 15.00,
  tier: 'standard',
};

/**
 * Get pricing info for a model.
 * Normalizes model IDs (strips date suffixes, etc.) for matching.
 *
 * @param {string} modelId — e.g. 'claude-sonnet-4-20250514'
 * @returns {{ displayName: string, inputPer1M: number, outputPer1M: number, tier: string }}
 */
function getModelPricing(modelId) {
  if (!modelId) return DEFAULT_PRICING;

  // Direct match
  if (MODEL_PRICING[modelId]) return MODEL_PRICING[modelId];

  // Try alias matching (e.g. 'claude-sonnet-4-6' → 'claude-sonnet-4-20250514')
  const lower = modelId.toLowerCase();
  for (const [key, val] of Object.entries(MODEL_PRICING)) {
    if (lower.startsWith(key.split('-2')[0])) return val;
  }

  return DEFAULT_PRICING;
}

/**
 * Calculate the cost of a request in USD.
 *
 * Anthropic prompt caching pricing:
 *   - cache_creation_input_tokens: 1.25× normal input price (25% surcharge to write)
 *   - cache_read_input_tokens: 0.1× normal input price (90% discount to read)
 *   - Regular input_tokens still reported for non-cached content
 *
 * The usage object from Anthropic may include cache_creation and cache_read
 * tokens IN ADDITION to the base input_tokens count. The input_tokens field
 * always represents the total input (including cached), so we subtract
 * cache tokens to avoid double-counting.
 *
 * @param {string} modelId
 * @param {number} inputTokens      — total input tokens reported by API
 * @param {number} outputTokens     — output tokens
 * @param {number} cacheCreation    — tokens written to cache (1.25× price)
 * @param {number} cacheRead        — tokens read from cache (0.1× price)
 * @returns {{ inputCost: number, outputCost: number, cacheSavings: number, totalCost: number, pricing: object }}
 */
function calculateCost(modelId, inputTokens, outputTokens, cacheCreation = 0, cacheRead = 0) {
  const pricing = getModelPricing(modelId);

  // Non-cached input = total input minus any cached tokens
  const regularInput = Math.max(0, inputTokens - cacheCreation - cacheRead);

  // Cost breakdown:
  //   Regular input:   normal price
  //   Cache creation:  1.25× normal price (25% surcharge)
  //   Cache read:      0.10× normal price (90% discount)
  //   Output:          normal price
  const regularInputCost = (regularInput   / 1_000_000) * pricing.inputPer1M;
  const cacheWriteCost   = (cacheCreation  / 1_000_000) * pricing.inputPer1M * 1.25;
  const cacheReadCost    = (cacheRead      / 1_000_000) * pricing.inputPer1M * 0.1;
  const outputCost       = (outputTokens   / 1_000_000) * pricing.outputPer1M;

  const totalInputCost = regularInputCost + cacheWriteCost + cacheReadCost;

  // What it WOULD have cost without caching (all input at full price)
  const uncachedInputCost = (inputTokens / 1_000_000) * pricing.inputPer1M;
  const cacheSavings = Math.max(0, uncachedInputCost - totalInputCost);

  return {
    inputCost:    Math.round(totalInputCost * 1_000_000) / 1_000_000,
    outputCost:   Math.round(outputCost * 1_000_000) / 1_000_000,
    cacheSavings: Math.round(cacheSavings * 1_000_000) / 1_000_000,
    totalCost:    Math.round((totalInputCost + outputCost) * 1_000_000) / 1_000_000,
    pricing,
  };
}

/**
 * Get all available model pricing for display.
 * @returns {object[]} Array of model pricing objects
 */
function getAllModelPricing() {
  return Object.entries(MODEL_PRICING).map(([modelId, info]) => ({
    modelId,
    ...info,
    deprecated: !!info.deprecated,
  }));
}

module.exports = { getModelPricing, calculateCost, getAllModelPricing, MODEL_PRICING };
