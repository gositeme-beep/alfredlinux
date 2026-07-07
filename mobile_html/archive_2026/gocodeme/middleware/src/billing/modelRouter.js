'use strict';

/**
 * billing/modelRouter.js — Smart model routing ("Auto" mode)
 *
 * Cursor-style model selection: routes each request to the cheapest model
 * that can handle the task competently. Users can also lock to a specific
 * model (Sonnet, Opus) for maximum quality.
 *
 * Modes (set per-user via Redis or per-request via x-gocodeme-model header):
 *   - "auto"   → smart routing (default): cheapest competent model per task
 *   - "sonnet" → always Claude Sonnet (current default, high quality)
 *   - "opus"   → always Claude Opus (premium, best quality)
 *   - "turbo"  → always use cheapest open-source model (max cost savings)
 *
 * Auto routing heuristics:
 *   1. If request uses tools (file edits, terminal, search) → Sonnet (needs to be reliable)
 *   2. If request is a large multi-file code change → Sonnet
 *   3. If request is conversation / explanation / short question → Cheap model
 *   4. If request is simple code completion / small edit → Cheap model
 *
 * Open-source models use OpenAI-compatible API, so we translate
 * Anthropic-format requests to OpenAI format when routing there.
 */

const logger = require('../logger');
const { getRedis } = require('../redis');

// ── Provider configurations ──────────────────────────────────────────────
const PROVIDERS = {
  anthropic: {
    name: 'Anthropic',
    baseUrl: 'https://api.anthropic.com',
    apiKey: process.env.ANTHROPIC_API_KEY,
    format: 'anthropic',  // native Anthropic Messages API
  },
  openai: {
    name: 'OpenAI',
    baseUrl: 'https://api.openai.com',
    apiKey: process.env.OPENAI_API_KEY,
    format: 'openai',     // native OpenAI Chat Completions
  },
  together: {
    name: 'Open Source',
    baseUrl: 'https://api.together.xyz',
    apiKey: process.env.TOGETHER_API_KEY,
    format: 'openai',     // OpenAI-compatible chat completions
  },
  google: {
    name: 'Google',
    baseUrl: 'https://generativelanguage.googleapis.com',
    apiKey: process.env.GOOGLE_API_KEY,
    format: 'openai',     // Gemini supports OpenAI-compatible format
    chatPath: '/v1beta/openai/chat/completions',  // Google's OpenAI-compat endpoint
  },
  groq: {
    name: 'Groq (Free)',
    baseUrl: 'https://api.groq.com/openai',
    apiKey: process.env.GROQ_API_KEY,
    format: 'openai',     // OpenAI-compatible chat completions
  },
  xai: {
    name: 'xAI',
    baseUrl: 'https://api.x.ai',
    apiKey: process.env.XAI_API_KEY,
    format: 'openai',     // OpenAI-compatible chat completions
  },
};

// ── Model registry ───────────────────────────────────────────────────────
// Each model has: provider, modelId, cost tier, capability flags
// inputPer1M / outputPer1M = OUR COST (what we pay the provider)
// tokenMultiplier = how many plan tokens each output token consumes.
//   - Premium models (high API cost) → 2-5x → users burn plan tokens faster
//   - Economy/free models → 0.5x → users get more value, incentivized to use
//   - Standard models → 1x (baseline)
// This ensures ALL models are profitable regardless of which one users pick.
const MODELS = {
  // ═══════════════════════════════════════════════════════════════════════
  // ── Anthropic (native format) ──────────────────────────────────────────
  // ═══════════════════════════════════════════════════════════════════════
  'claude-opus-4-6': {
    provider: 'anthropic',
    modelId: 'claude-opus-4-6',
    displayName: 'Claude Opus 4.6',
    emoji: '🧠',
    inputPer1M: 5.00,
    outputPer1M: 25.00,
    tokenMultiplier: 5,
    supportsTools: true,
    supportsStreaming: true,
    codeQuality: 'best',
    contextWindow: 200000,
    tier: 'premium',
    description: 'Most intelligent Anthropic model — best for complex reasoning & architecture',
  },
  'claude-sonnet-4-6': {
    provider: 'anthropic',
    modelId: 'claude-sonnet-4-6',
    displayName: 'Claude Sonnet 4.6',
    emoji: '⚡',
    inputPer1M: 3.00,
    outputPer1M: 15.00,
    tokenMultiplier: 3,
    supportsTools: true,
    supportsStreaming: true,
    codeQuality: 'excellent',
    contextWindow: 200000,
    tier: 'standard',
    description: 'Excellent coding with fast speed — best all-rounder',
  },
  'claude-haiku-4-5': {
    provider: 'anthropic',
    modelId: 'claude-haiku-4-5-20251001',
    displayName: 'Claude Haiku 4.5',
    emoji: '💨',
    inputPer1M: 1.00,
    outputPer1M: 5.00,
    tokenMultiplier: 1,
    supportsTools: true,
    supportsStreaming: true,
    codeQuality: 'good',
    contextWindow: 200000,
    tier: 'standard',
    description: 'Fast & efficient — great for quick edits and questions',
  },

  // ═══════════════════════════════════════════════════════════════════════
  // ── OpenAI (native OpenAI format) ──────────────────────────────────────
  // ═══════════════════════════════════════════════════════════════════════
  'gpt-4.1': {
    provider: 'openai',
    modelId: 'gpt-4.1',
    displayName: 'GPT-4.1',
    emoji: '🟢',
    inputPer1M: 2.00,
    outputPer1M: 8.00,
    tokenMultiplier: 2,
    supportsTools: true,
    supportsStreaming: true,
    codeQuality: 'excellent',
    contextWindow: 1047576,
    tier: 'standard',
    description: 'OpenAI flagship — 1M context, excellent coding & instruction following',
  },
  'gpt-4.1-mini': {
    provider: 'openai',
    modelId: 'gpt-4.1-mini',
    displayName: 'GPT-4.1 Mini',
    emoji: '🟡',
    inputPer1M: 0.40,
    outputPer1M: 1.60,
    tokenMultiplier: 0.5,
    supportsTools: true,
    supportsStreaming: true,
    codeQuality: 'good',
    contextWindow: 1047576,
    tier: 'economy',
    description: 'Fast & cheap OpenAI — great balance of speed and quality',
  },
  'gpt-4.1-nano': {
    provider: 'openai',
    modelId: 'gpt-4.1-nano',
    displayName: 'GPT-4.1 Nano',
    emoji: '⚡',
    inputPer1M: 0.10,
    outputPer1M: 0.40,
    tokenMultiplier: 0.5,
    supportsTools: true,
    supportsStreaming: true,
    codeQuality: 'decent',
    contextWindow: 1047576,
    tier: 'economy',
    description: 'Cheapest OpenAI — ultra-fast for simple tasks',
  },
  'gpt-4o': {
    provider: 'openai',
    modelId: 'gpt-4o',
    displayName: 'GPT-4o',
    emoji: '🌈',
    inputPer1M: 2.50,
    outputPer1M: 10.00,
    tokenMultiplier: 2,
    supportsTools: true,
    supportsStreaming: true,
    codeQuality: 'excellent',
    contextWindow: 128000,
    tier: 'standard',
    description: 'Multimodal — supports images, audio, and code',
  },
  'gpt-4o-mini': {
    provider: 'openai',
    modelId: 'gpt-4o-mini',
    displayName: 'GPT-4o Mini',
    emoji: '🔵',
    inputPer1M: 0.15,
    outputPer1M: 0.60,
    tokenMultiplier: 0.5,
    supportsTools: true,
    supportsStreaming: true,
    codeQuality: 'good',
    contextWindow: 128000,
    tier: 'economy',
    description: 'Small multimodal — fast, cheap, good for simple tasks',
  },
  'o3': {
    provider: 'openai',
    modelId: 'o3',
    displayName: 'o3 (Reasoning)',
    emoji: '🔮',
    inputPer1M: 2.00,
    outputPer1M: 8.00,
    tokenMultiplier: 2,
    supportsTools: true,
    supportsStreaming: true,
    codeQuality: 'best',
    contextWindow: 200000,
    tier: 'premium',
    description: 'OpenAI reasoning model — deep thinking for complex problems',
  },
  'o3-mini': {
    provider: 'openai',
    modelId: 'o3-mini',
    displayName: 'o3 Mini',
    emoji: '💎',
    inputPer1M: 1.10,
    outputPer1M: 4.40,
    tokenMultiplier: 1,
    supportsTools: true,
    supportsStreaming: true,
    codeQuality: 'excellent',
    contextWindow: 200000,
    tier: 'standard',
    description: 'Compact reasoning — fast deep-thinking at lower cost',
  },
  'o4-mini': {
    provider: 'openai',
    modelId: 'o4-mini',
    displayName: 'o4 Mini',
    emoji: '✨',
    inputPer1M: 1.10,
    outputPer1M: 4.40,
    tokenMultiplier: 1,
    supportsTools: true,
    supportsStreaming: true,
    codeQuality: 'excellent',
    contextWindow: 200000,
    tier: 'standard',
    description: 'Latest OpenAI reasoning — excellent tool use & coding',
  },

  // ═══════════════════════════════════════════════════════════════════════
  // ── Google (Gemini models) ─────────────────────────────────────────────
  // ═══════════════════════════════════════════════════════════════════════

  // ── Gemini 3 series (latest, Feb/Mar 2026) ────────────────────────────
  'gemini-3.1-pro': {
    provider: 'google',
    modelId: 'gemini-3.1-pro-preview',
    displayName: 'Gemini 3.1 Pro',
    emoji: '🌐',
    inputPer1M: 2.00,
    outputPer1M: 12.00,
    tokenMultiplier: 2,
    supportsTools: true,
    supportsStreaming: true,
    codeQuality: 'best',
    contextWindow: 1048576,
    tier: 'premium',
    description: 'Google flagship — advanced reasoning, agentic & vibe-coding',
  },
  'gemini-3-flash': {
    provider: 'google',
    modelId: 'gemini-3-flash-preview',
    displayName: 'Gemini 3 Flash',
    emoji: '⚡',
    inputPer1M: 0.50,
    outputPer1M: 3.00,
    tokenMultiplier: 1,
    supportsTools: true,
    supportsStreaming: true,
    codeQuality: 'excellent',
    contextWindow: 1048576,
    tier: 'standard',
    description: 'Frontier-class speed — rivals larger models at a fraction of cost',
  },
  'gemini-3.1-flash-lite': {
    provider: 'google',
    modelId: 'gemini-3.1-flash-lite-preview',
    displayName: 'Gemini 3.1 Flash Lite',
    emoji: '🪶',
    inputPer1M: 0.25,
    outputPer1M: 1.50,
    tokenMultiplier: 0.5,
    supportsTools: true,
    supportsStreaming: true,
    codeQuality: 'good',
    contextWindow: 1048576,
    tier: 'economy',
    description: 'Cheapest Gemini 3 — high-volume agentic tasks & translation',
  },

  // ── Gemini 2.5 stable (production-ready) ──────────────────────────────

  // ── Gemini Image Generation (Nano Banana 2) ───────────────────────
  'gemini-image': {
    provider: 'google',
    modelId: 'gemini-3.1-flash-image-preview',
    displayName: 'Gemini Image Gen',
    emoji: '🍌',
    inputPer1M: 0.10,
    outputPer1M: 0.40,
    tokenMultiplier: 1,
    supportsTools: false,
    supportsStreaming: true,
    codeQuality: 'n/a',
    contextWindow: 32768,
    tier: 'economy',
    description: 'Nano Banana 2 — generate & edit images via chat (ask it to draw anything)',
  },

  'gemini-2.5-pro': {
    provider: 'google',
    modelId: 'gemini-2.5-pro',
    displayName: 'Gemini 2.5 Pro',
    emoji: '💎',
    inputPer1M: 1.25,
    outputPer1M: 10.00,
    tokenMultiplier: 2,
    supportsTools: true,
    supportsStreaming: true,
    codeQuality: 'best',
    contextWindow: 1048576,
    tier: 'premium',
    description: 'Google stable flagship — 1M context, deep reasoning & coding',
  },
  'gemini-2.5-flash': {
    provider: 'google',
    modelId: 'gemini-2.5-flash',
    displayName: 'Gemini 2.5 Flash',
    emoji: '💡',
    inputPer1M: 0.30,
    outputPer1M: 2.50,
    tokenMultiplier: 0.5,
    supportsTools: true,
    supportsStreaming: true,
    codeQuality: 'excellent',
    contextWindow: 1048576,
    tier: 'economy',
    description: 'Google stable flash — hybrid reasoning, 1M context, free tier',
  },
  'gemini-2.5-flash-lite': {
    provider: 'google',
    modelId: 'gemini-2.5-flash-lite',
    displayName: 'Gemini 2.5 Flash Lite',
    emoji: '🍃',
    inputPer1M: 0.10,
    outputPer1M: 0.40,
    tokenMultiplier: 0.5,
    supportsTools: true,
    supportsStreaming: true,
    codeQuality: 'good',
    contextWindow: 1048576,
    tier: 'economy',
    description: 'Cheapest Google stable — budget-friendly, at-scale usage',
  },

  // ── Gemini 2.0 (deprecated June 1, 2026) ──────────────────────────────
  'gemini-2.0-flash': {
    provider: 'google',
    modelId: 'gemini-2.0-flash',
    displayName: 'Gemini 2.0 Flash (Deprecated)',
    emoji: '💨',
    inputPer1M: 0.10,
    outputPer1M: 0.40,
    tokenMultiplier: 0.5,
    supportsTools: true,
    supportsStreaming: true,
    codeQuality: 'good',
    contextWindow: 1048576,
    tier: 'economy',
    deprecated: true,
    description: 'Legacy — shutting down June 2026, use Gemini 2.5+ instead',
  },

  // ═══════════════════════════════════════════════════════════════════════
  // ── Open Source (OpenAI-compatible, open-source models) ────────────────
  // ═══════════════════════════════════════════════════════════════════════
  'qwen3-coder': {
    provider: 'together',
    modelId: 'Qwen/Qwen3-Coder-Next-FP8',
    displayName: 'Qwen3 Coder',
    emoji: '🔧',
    inputPer1M: 0.50,
    outputPer1M: 1.20,
    tokenMultiplier: 0.5,
    supportsTools: true,
    supportsStreaming: true,
    codeQuality: 'excellent',
    contextWindow: 262144,
    tier: 'economy',
    description: 'Purpose-built for code — fast, cheap, excellent quality',
  },
  'qwen3-coder-480b': {
    provider: 'together',
    modelId: 'Qwen/Qwen3-Coder-480B-A35B-Instruct-FP8',
    displayName: 'Qwen3 Coder 480B',
    emoji: '🏗️',
    inputPer1M: 2.00,
    outputPer1M: 2.00,
    tokenMultiplier: 1,
    supportsTools: true,
    supportsStreaming: true,
    codeQuality: 'best',
    contextWindow: 262144,
    tier: 'standard',
    description: 'Largest open-source coder — rivals Claude for complex tasks',
  },
  'qwen3.5': {
    provider: 'together',
    modelId: 'Qwen/Qwen3.5-397B-A17B',
    displayName: 'Qwen 3.5',
    emoji: '🌟',
    inputPer1M: 0.60,
    outputPer1M: 3.60,
    tokenMultiplier: 1,
    supportsTools: true,
    supportsStreaming: true,
    codeQuality: 'excellent',
    contextWindow: 262144,
    tier: 'economy',
    description: 'Latest Qwen flagship — excellent general intelligence',
  },
  'deepseek-v3': {
    provider: 'together',
    modelId: 'deepseek-ai/DeepSeek-V3.1',
    displayName: 'DeepSeek V3.1',
    emoji: '🌊',
    inputPer1M: 0.60,
    outputPer1M: 1.70,
    tokenMultiplier: 0.5,
    supportsTools: true,
    supportsStreaming: true,
    codeQuality: 'good',
    contextWindow: 131072,
    tier: 'economy',
    description: 'Strong open-source model — good coding, very affordable',
  },
  'deepseek-r1': {
    provider: 'together',
    modelId: 'deepseek-ai/DeepSeek-R1',
    displayName: 'DeepSeek R1',
    emoji: '🧪',
    inputPer1M: 3.00,
    outputPer1M: 7.00,
    tokenMultiplier: 2,
    supportsTools: true,
    supportsStreaming: true,
    codeQuality: 'excellent',
    contextWindow: 163840,
    tier: 'premium',
    description: 'Reasoning model — chain-of-thought for complex problems',
  },
  'glm-5': {
    provider: 'together',
    modelId: 'zai-org/GLM-5',
    displayName: 'GLM-5',
    emoji: '🔶',
    inputPer1M: 1.00,
    outputPer1M: 3.20,
    tokenMultiplier: 1,
    supportsTools: true,
    supportsStreaming: true,
    codeQuality: 'good',
    contextWindow: 202752,
    tier: 'economy',
    description: 'Zhipu AI flagship — strong coding and reasoning',
  },
  'glm-4.6': {
    provider: 'together',
    modelId: 'zai-org/GLM-4.6',
    displayName: 'GLM-4.6',
    emoji: '🔷',
    inputPer1M: 0.60,
    outputPer1M: 2.20,
    tokenMultiplier: 0.5,
    supportsTools: true,
    supportsStreaming: true,
    codeQuality: 'good',
    contextWindow: 202752,
    tier: 'economy',
    description: 'Fast GLM model — good balance of speed and capability',
  },
  'kimi-k2.5': {
    provider: 'together',
    modelId: 'moonshotai/Kimi-K2.5',
    displayName: 'Kimi K2.5',
    tokenMultiplier: 0.5,
    emoji: '🌙',
    inputPer1M: 0.50,
    outputPer1M: 2.80,
    supportsTools: true,
    supportsStreaming: true,
    codeQuality: 'good',
    contextWindow: 262144,
    tier: 'economy',
    description: 'Moonshot AI — great for long-context & agentic tasks',
  },
  'kimi-k2-thinking': {
    provider: 'together',
    modelId: 'moonshotai/Kimi-K2-Thinking',
    displayName: 'Kimi K2 Thinking',
    tokenMultiplier: 1,
    emoji: '🧠',
    inputPer1M: 1.20,
    outputPer1M: 4.00,
    supportsTools: true,
    supportsStreaming: true,
    codeQuality: 'excellent',
    contextWindow: 262144,
    tier: 'standard',
    description: 'Reasoning model from Moonshot — deep thinking with long context',
  },
  'llama-4-maverick': {
    provider: 'together',
    modelId: 'meta-llama/Llama-4-Maverick-17B-128E-Instruct-FP8',
    displayName: 'Llama 4 Maverick',
    tokenMultiplier: 0.5,
    emoji: '🦙',
    inputPer1M: 0.27,
    outputPer1M: 0.85,
    supportsTools: true,
    supportsStreaming: true,
    codeQuality: 'good',
    contextWindow: 1048576,
    tier: 'economy',
    description: 'Meta open-source — 1M context, very cheap',
  },
  'llama-4-scout': {
    provider: 'together',
    modelId: 'meta-llama/Llama-4-Scout-17B-16E-Instruct',
    displayName: 'Llama 4 Scout',
    tokenMultiplier: 0.5,
    emoji: '🔍',
    inputPer1M: 0.18,
    outputPer1M: 0.59,
    supportsTools: true,
    supportsStreaming: true,
    codeQuality: 'decent',
    contextWindow: 1048576,
    tier: 'economy',
    description: 'Cheapest Llama — 1M context, ultra-fast inference',
  },
  'mistral-small': {
    provider: 'together',
    modelId: 'mistralai/Mistral-Small-24B-Instruct-2501',
    displayName: 'Mistral Small',
    tokenMultiplier: 0.5,
    emoji: '🇫🇷',
    inputPer1M: 0.10,
    outputPer1M: 0.30,
    supportsTools: true,
    supportsStreaming: true,
    codeQuality: 'decent',
    contextWindow: 32768,
    tier: 'economy',
    description: 'European AI — fast, lightweight, good for simple tasks',
  },

  // ═══════════════════════════════════════════════════════════════════════
  // ── xAI (Grok models — OpenAI-compatible) ──────────────────────────────
  // ═══════════════════════════════════════════════════════════════════════
  'grok-3': {
    provider: 'xai',
    modelId: 'grok-3',
    displayName: 'Grok 3',
    tokenMultiplier: 0.5,
    emoji: '🚀',
    inputPer1M: 0.00,
    outputPer1M: 0.00,
    supportsTools: true,
    supportsStreaming: true,
    codeQuality: 'excellent',
    contextWindow: 131072,
    tier: 'free',
    description: 'xAI flagship — excellent coding & real-time knowledge (FREE)',
  },
  'grok-3-mini': {
    provider: 'xai',
    modelId: 'grok-3-mini',
    displayName: 'Grok 3 Mini',
    tokenMultiplier: 0.5,
    emoji: '⚡',
    inputPer1M: 0.00,
    outputPer1M: 0.00,
    supportsTools: true,
    supportsStreaming: true,
    codeQuality: 'good',
    contextWindow: 131072,
    tier: 'free',
    description: 'xAI economy — fast reasoning (FREE)',
  },

  // ═══════════════════════════════════════════════════════════════════════
  // ── Video Generation (Together.ai async API) ───────────────────────────
  // ═══════════════════════════════════════════════════════════════════════
  'veo-2-video': {
    provider: 'together',
    modelId: 'google/veo-2.0',
    displayName: 'Veo 2 Video',
    emoji: '🎬',
    inputPer1M: 0,
    outputPer1M: 0,
    tokenMultiplier: 3,
    supportsTools: false,
    supportsStreaming: false,
    codeQuality: 'n/a',
    contextWindow: 0,
    tier: 'standard',
    tokensPerGeneration: 25000,
    isVideoModel: true,
    description: 'Google Veo 2 — generate videos from text prompts (via Together.ai)',
  },

  // ═══════════════════════════════════════════════════════════════════════
  // ── Groq (free tier — OpenAI-compatible) ───────────────────────────────
  // ═══════════════════════════════════════════════════════════════════════
  'groq-llama-3.3-70b': {
    provider: 'groq',
    modelId: 'llama-3.3-70b-versatile',
    displayName: 'Llama 3.3 70B (Groq)',
    emoji: '⚡',
    inputPer1M: 0.00,
    outputPer1M: 0.00,
    tokenMultiplier: 0.5,
    supportsTools: true,
    supportsStreaming: true,
    codeQuality: 'good',
    contextWindow: 131072,
    tier: 'free',
    description: 'Free via Groq — fast inference, solid general-purpose coding',
  },
  'groq-llama-3.1-8b': {
    provider: 'groq',
    modelId: 'llama-3.1-8b-instant',
    displayName: 'Llama 3.1 8B (Groq)',
    emoji: '🆓',
    inputPer1M: 0.00,
    outputPer1M: 0.00,
    supportsTools: true,
    supportsStreaming: true,
    codeQuality: 'decent',
    contextWindow: 131072,
    tier: 'free',
    description: 'Free via Groq — ultra-fast, lightweight, good for simple tasks',
  },
};

// ── Mode definitions ─────────────────────────────────────────────────────
// "auto" routes based on task complexity. Any model key can also be used directly.
const MODE_DEFAULTS = {
  auto:   null,      // dynamically selected
  sonnet: 'claude-sonnet-4-6',
  opus:   'claude-opus-4-6',
  haiku:  'claude-haiku-4-5',
  turbo:  'qwen3-coder',
  groq:   'groq-llama-3.3-70b',
};

/**
 * Get user's preferred model from Redis.
 * Default: 'auto' for all users.
 * Users can pick:
 *   - 'auto'          → smart routing by complexity
 *   - A MODE key       → 'sonnet', 'opus', 'haiku', 'turbo'
 *   - A MODEL key      → any key from MODELS (e.g. 'gpt-4.1', 'deepseek-r1')
 */
async function getUserMode(whmcsClientId) {
  try {
    const redis = getRedis();
    const mode = await redis.get(`model_mode:${whmcsClientId}`);
    if (!mode) return 'auto';
    // Valid if it's a mode key, a model key, or 'auto'
    if (mode === 'auto' || MODE_DEFAULTS.hasOwnProperty(mode) || MODELS.hasOwnProperty(mode)) {
      return mode;
    }
    return 'auto';
  } catch {
    return 'auto';
  }
}

/**
 * Set user's preferred model.
 * Accepts 'auto', any MODE key, or any MODEL key.
 */
async function setUserMode(whmcsClientId, mode) {
  if (mode !== 'auto' && !MODE_DEFAULTS.hasOwnProperty(mode) && !MODELS.hasOwnProperty(mode)) {
    throw new Error(`Invalid model: ${mode}`);
  }
  const redis = getRedis();
  await redis.set(`model_mode:${whmcsClientId}`, mode);
  return mode;
}

/**
 * Classify a request's complexity to decide routing.
 *
 * Returns: 'complex' | 'moderate' | 'simple'
 *   complex  → needs Sonnet (active tool loop, multi-file edits, long sessions)
 *   moderate → can use Haiku or decent economy model (mid-length, some tool use)
 *   simple   → can use cheapest model (questions, short convos, planning)
 *
 * KEY FIX: Theia ALWAYS sends ~218 tool definitions. The old classifier
 * treated "tools present" as complex, routing everything to Sonnet.
 * Now we check if tools are ACTIVELY BEING USED (tool_result in recent msgs).
 */
function classifyRequest(body) {
  const messages = body.messages || [];
  const msgCount = messages.length;
  const lastMsg = messages[msgCount - 1];

  // ── Check for active tool loop ────────────────────────────────────────
  // A tool loop = the model called a tool → user sent tool_result → model responds
  // This is the most critical signal: tool loops need reliable models.
  const lastUserHasToolResults = lastMsg && lastMsg.role === 'user' &&
    Array.isArray(lastMsg.content) &&
    lastMsg.content.some(b => b.type === 'tool_result');

  // Count how many recent messages contain tool_use / tool_result
  const recentToolActivity = countRecentToolActivity(messages);

  // Active tool loop right now (tool_result is the last message) → complex
  if (lastUserHasToolResults) {
    return 'complex';
  }

  // Recent heavy tool activity (3+ tool exchanges in the last 10 messages) → complex
  if (recentToolActivity >= 3) {
    return 'complex';
  }

  // ── Message length heuristics ─────────────────────────────────────────
  // Very long conversations likely have deep context → moderate at least
  if (msgCount >= 20) {
    return 'complex';
  }

  // Medium conversations (some back-and-forth) with any tool activity → moderate
  if (msgCount >= 8 && recentToolActivity >= 1) {
    return 'moderate';
  }

  // Medium conversations without tool activity → moderate
  if (msgCount >= 10) {
    return 'moderate';
  }

  // ── Content analysis for the last user message ────────────────────────
  const lastUserMsg = [...messages].reverse().find(m => m.role === 'user');
  if (lastUserMsg) {
    const text = extractText(lastUserMsg);
    const len = text.length;

    // Very long user message (probably pasting code, complex request) → moderate
    if (len > 3000) {
      return 'moderate';
    }

    // Short messages that look like questions/acknowledgments → simple
    // "what does this do?", "explain", "thanks", "yes", "fix the bug"
    if (len < 200) {
      return 'simple';
    }
  }

  // Default: short conversation, no active tools → simple
  return 'simple';
}

/**
 * Count tool_use + tool_result exchanges in the last N messages.
 */
function countRecentToolActivity(messages) {
  const window = messages.slice(-10); // last 10 messages
  let count = 0;
  for (const msg of window) {
    if (Array.isArray(msg.content)) {
      if (msg.content.some(b => b.type === 'tool_use' || b.type === 'tool_result')) {
        count++;
      }
    }
  }
  return count;
}

/**
 * Extract text content from a message.
 */
function extractText(msg) {
  if (typeof msg.content === 'string') return msg.content;
  if (Array.isArray(msg.content)) {
    return msg.content
      .filter(b => b.type === 'text')
      .map(b => b.text || '')
      .join('\n');
  }
  return '';
}

/**
 * Route a request to the best model based on mode and task complexity.
 *
 * @param {string} whmcsClientId
 * @param {object} body — the request body (messages, tools, etc.)
 * @param {string} [headerOverride] — x-gocodeme-model header value
 * @returns {{ model: object, mode: string, reason: string }}
 */
async function routeRequest(whmcsClientId, body, headerOverride) {
  // 1. Determine mode
  let mode;
  if (headerOverride && MODE_DEFAULTS.hasOwnProperty(headerOverride)) {
    mode = headerOverride;
  } else {
    mode = await getUserMode(whmcsClientId);
  }

  // 2. Fixed mode or direct model selection → return that model
  if (mode !== 'auto') {
    // Check if it's a direct model key (e.g. 'gpt-4.1', 'deepseek-r1')
    if (MODELS[mode]) {
      return { model: MODELS[mode], mode, reason: `user-selected:${mode}` };
    }
    // Otherwise it's a mode alias (sonnet, opus, haiku, turbo)
    const modelKey = MODE_DEFAULTS[mode];
    const model = MODELS[modelKey];
    if (!model) {
      return { model: MODELS['claude-sonnet-4-6'], mode, reason: 'fallback' };
    }
    return { model, mode, reason: `user-selected:${mode}` };
  }

  // 3. Auto mode — classify and route
  const complexity = classifyRequest(body);

  if (complexity === 'complex') {
    // Active tool loop / long session → Sonnet for reliability
    return {
      model: MODELS['claude-sonnet-4-6'],
      mode: 'auto',
      reason: 'auto:complex→sonnet',
    };
  }

  if (complexity === 'moderate') {
    // Mid-length convo or some tool use → Haiku (good + cheap)
    return {
      model: MODELS['claude-haiku-4-5'],
      mode: 'auto',
      reason: 'auto:moderate→haiku',
    };
  }

  // Simple → cheapest good coder (Qwen3 via open-source provider)
  return {
    model: MODELS['qwen3-coder'],
    mode: 'auto',
    reason: 'auto:simple→qwen3-coder',
  };
}

/**
 * Get the provider config for a model.
 */
function getProvider(model) {
  return PROVIDERS[model.provider] || PROVIDERS.anthropic;
}

/**
 * Get all available models for the settings UI.
 * Groups by provider so users can see the full catalog.
 */
function getAvailableModels() {
  return Object.entries(MODELS).map(([key, m]) => ({
    key,
    displayName: m.displayName,
    emoji: m.emoji || '',
    description: m.description || '',
    provider: PROVIDERS[m.provider]?.name || m.provider,
    codeQuality: m.codeQuality,
    tier: m.tier,
    contextWindow: m.contextWindow,
  }));
}

/**
 * Get available modes for display (simplified presets).
 */
function getAvailableModes() {
  return [
    { key: 'auto',   label: '🤖 Auto',   description: 'Smart routing — picks the best model for each task automatically' },
    { key: 'sonnet', label: '⚡ Sonnet',  description: 'Claude Sonnet 4.6 — excellent all-rounder' },
    { key: 'opus',   label: '🧠 Opus',    description: 'Claude Opus 4.6 — best for complex tasks (premium)' },
    { key: 'haiku',  label: '💨 Haiku',   description: 'Claude Haiku 4.5 — fastest, good quality' },
    { key: 'turbo',  label: '🔧 Turbo',   description: 'Qwen3 Coder — purpose-built for code, most cost-efficient' },
    { key: 'groq',   label: '🆓 Groq',    description: 'Llama 3.3 70B via Groq — free tier, solid coding' },
  ];
}

/**
 * Get Groq fallback model config (for use when Anthropic fails).
 * Returns null if no Groq API key is configured.
 */
function getGroqFallback() {
  if (!PROVIDERS.groq.apiKey) return null;
  return {
    model: MODELS['groq-llama-3.3-70b'],
    provider: PROVIDERS.groq,
    reason: 'groq-fallback',
  };
}

/**
 * Get token multiplier for a model.
 * Checks Redis for admin overrides first, falls back to hardcoded values.
 *
 * @param {string} modelId — the API model ID (e.g. 'claude-sonnet-4-6', 'gpt-4.1')
 * @returns {number}
 */
function getTokenMultiplier(modelId) {
  // Direct key match
  if (MODELS[modelId]) return MODELS[modelId].tokenMultiplier || 1;
  // Search by modelId property (handles cases where the key differs)
  for (const m of Object.values(MODELS)) {
    if (m.modelId === modelId) return m.tokenMultiplier || 1;
  }
  return 1;
}

/**
 * Async version that checks Redis for admin multiplier overrides.
 * Use this in billing paths; the sync version is still available for hot paths.
 */
async function getTokenMultiplierAsync(modelId) {
  // Find the model key
  let key = null;
  if (MODELS[modelId]) { key = modelId; }
  else {
    for (const [k, m] of Object.entries(MODELS)) {
      if (m.modelId === modelId) { key = k; break; }
    }
  }
  if (!key) return 1;

  // Check Redis override
  try {
    const redis = getRedis();
    const override = await redis.get(`multiplier:override:${key}`);
    if (override !== null) {
      const val = parseFloat(override);
      if (Number.isFinite(val) && val > 0) return val;
    }
  } catch (_) {}

  return MODELS[key]?.tokenMultiplier || 1;
}

function getModelByKey(key) {
  return MODELS[key] || null;
}

module.exports = {
  MODELS,
  getModelByKey,
  getTokenMultiplier,
  getTokenMultiplierAsync,
  PROVIDERS,
  getUserMode,
  setUserMode,
  classifyRequest,
  routeRequest,
  getProvider,
  getAvailableModels,
  getAvailableModes,
  getGroqFallback,
};
