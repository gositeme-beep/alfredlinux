'use strict';

/**
 * routes/developer.js — API Access Tier for Developers
 *
 * Gives developers programmatic API access to GoCodeMe's AI models.
 * Like OpenAI/Anthropic API but at a fraction of the cost (we handle routing).
 *
 * API Plans (WHMCS products to be created):
 *   API Starter  — 500K tokens/mo, $25/mo, 60 req/min
 *   API Pro      — 2M tokens/mo,   $79/mo, 200 req/min
 *   API Scale    — 10M tokens/mo,  $299/mo, 500 req/min
 *
 * Features:
 *   - API key management (create, revoke, list, rotate)
 *   - Per-key rate limiting
 *   - Usage stats per key
 *   - OpenAI-compatible /v1/chat/completions endpoint
 *   - Automatic model routing (same as IDE)
 *
 * Auth:
 *   API keys are prefixed: "gcm_" + 48 hex chars
 *   Sent as: Authorization: Bearer gcm_xxxx...
 *
 * Redis Keys:
 *   apikey:<hash>               → JSON { clientId, name, created, lastUsed, rateLimit }
 *   apikeys:client:<clientId>   → JSON array of { keyHash, name, created, prefix }
 *   apikey:usage:<hash>:<date>  → integer (daily request count)
 *   apikey:tokens:<hash>:<month>→ integer (monthly token usage)
 *   apikey:rate:<hash>          → rate limit counter (sliding window)
 *
 * Endpoints:
 *   POST /api/developer/keys             — create a new API key
 *   GET  /api/developer/keys             — list all keys (hashed, not full)
 *   DELETE /api/developer/keys/:keyId    — revoke a key
 *   POST /api/developer/keys/:keyId/rotate — rotate a key (new secret, same settings)
 *   GET  /api/developer/usage            — API usage stats
 *   POST /api/developer/v1/chat/completions — OpenAI-compatible chat endpoint
 */

const express = require('express');
const router  = express.Router();
const crypto  = require('crypto');

const { requireSession } = require('../auth/middleware');
const tc = require('../tokens/tokenCounter');
const { getRedis } = require('../redis');
const logger = require('../logger');
const config = require('../config');

// API plan configurations
const API_PLANS = {
  api_starter: { tokens: 500_000,  rateLimit: 60,  price: 25,  pid: 43, name: 'API Starter' },
  api_pro:     { tokens: 2_000_000, rateLimit: 200, price: 79,  pid: 44, name: 'API Pro' },
  api_scale:   { tokens: 10_000_000, rateLimit: 500, price: 299, pid: 45, name: 'API Scale' },
};

const KEY_PREFIX = 'gcm_';
const KEY_TTL    = 365 * 24 * 60 * 60; // keys expire after 1 year if not renewed

// ── Generate API key ────────────────────────────────────────────────────────
function generateApiKey() {
  return KEY_PREFIX + crypto.randomBytes(24).toString('hex');
}

function hashKey(key) {
  return crypto.createHash('sha256').update(key).digest('hex');
}

// ── Create Key: POST /api/developer/keys ────────────────────────────────────
router.post('/keys', requireSession, async (req, res) => {
  try {
    const { whmcsClientId } = req.user;
    const { name = 'Default' } = req.body;
    const redis = getRedis();

    // Check how many keys this client has (max 10)
    const keysRaw = await redis.get(`apikeys:client:${whmcsClientId}`);
    let keys = [];
    try { keys = JSON.parse(keysRaw) || []; } catch {}

    if (keys.length >= 10) {
      return res.status(429).json({ ok: false, error: 'Maximum 10 API keys per account' });
    }

    // Determine rate limit based on customer's plan
    const tokenLimit = await tc.getLimit(whmcsClientId);
    let rateLimit = 60; // default
    for (const [, plan] of Object.entries(API_PLANS)) {
      if (tokenLimit >= plan.tokens) rateLimit = plan.rateLimit;
    }

    // Generate key
    const rawKey = generateApiKey();
    const keyHash = hashKey(rawKey);

    const keyData = {
      clientId: whmcsClientId,
      name,
      created: new Date().toISOString(),
      lastUsed: null,
      rateLimit,
      active: true,
    };

    await redis.setex(`apikey:${keyHash}`, KEY_TTL, JSON.stringify(keyData));

    // Add to client's key list (store hash + prefix for identification)
    keys.push({
      keyHash,
      name,
      created: keyData.created,
      prefix: rawKey.slice(0, 12) + '...',
    });
    await redis.set(`apikeys:client:${whmcsClientId}`, JSON.stringify(keys));

    logger.info(`developer: created API key for client ${whmcsClientId} (${name})`);

    // Return the FULL key only once — user must save it
    res.json({
      ok: true,
      key: rawKey,
      keyId: keyHash.slice(0, 16),
      name,
      rateLimit,
      warning: 'Save this key now — it will not be shown again.',
    });
  } catch (err) {
    logger.error(`developer/keys/create: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── List Keys: GET /api/developer/keys ──────────────────────────────────────
router.get('/keys', requireSession, async (req, res) => {
  try {
    const { whmcsClientId } = req.user;
    const redis = getRedis();

    const keysRaw = await redis.get(`apikeys:client:${whmcsClientId}`);
    let keys = [];
    try { keys = JSON.parse(keysRaw) || []; } catch {}

    // Enrich with last-used and usage stats
    const enriched = [];
    for (const k of keys) {
      const keyDataRaw = await redis.get(`apikey:${k.keyHash}`);
      let keyData = {};
      try { keyData = JSON.parse(keyDataRaw) || {}; } catch {}

      const month = new Date().toISOString().slice(0, 7);
      const monthlyTokens = parseInt(await redis.get(`apikey:tokens:${k.keyHash}:${month}`) || '0', 10);

      enriched.push({
        keyId: k.keyHash.slice(0, 16),
        name: k.name,
        prefix: k.prefix,
        created: k.created,
        lastUsed: keyData.lastUsed || null,
        rateLimit: keyData.rateLimit || 60,
        active: keyData.active !== false,
        monthlyTokens,
      });
    }

    res.json({ ok: true, keys: enriched });
  } catch (err) {
    logger.error(`developer/keys/list: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── Revoke Key: DELETE /api/developer/keys/:keyId ───────────────────────────
router.delete('/keys/:keyId', requireSession, async (req, res) => {
  try {
    const { whmcsClientId } = req.user;
    const { keyId } = req.params;
    const redis = getRedis();

    const keysRaw = await redis.get(`apikeys:client:${whmcsClientId}`);
    let keys = [];
    try { keys = JSON.parse(keysRaw) || []; } catch {}

    const idx = keys.findIndex(k => k.keyHash.startsWith(keyId));
    if (idx < 0) {
      return res.status(404).json({ ok: false, error: 'Key not found' });
    }

    const fullHash = keys[idx].keyHash;

    // Mark key as inactive (don't delete — keep for audit)
    const keyDataRaw = await redis.get(`apikey:${fullHash}`);
    if (keyDataRaw) {
      const keyData = JSON.parse(keyDataRaw);
      keyData.active = false;
      keyData.revokedAt = new Date().toISOString();
      await redis.setex(`apikey:${fullHash}`, KEY_TTL, JSON.stringify(keyData));
    }

    // Remove from client's active list
    keys.splice(idx, 1);
    await redis.set(`apikeys:client:${whmcsClientId}`, JSON.stringify(keys));

    logger.info(`developer: revoked API key ${keyId} for client ${whmcsClientId}`);

    res.json({ ok: true, revoked: keyId });
  } catch (err) {
    logger.error(`developer/keys/revoke: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── Rotate Key: POST /api/developer/keys/:keyId/rotate ──────────────────────
router.post('/keys/:keyId/rotate', requireSession, async (req, res) => {
  try {
    const { whmcsClientId } = req.user;
    const { keyId } = req.params;
    const redis = getRedis();

    const keysRaw = await redis.get(`apikeys:client:${whmcsClientId}`);
    let keys = [];
    try { keys = JSON.parse(keysRaw) || []; } catch {}

    const idx = keys.findIndex(k => k.keyHash.startsWith(keyId));
    if (idx < 0) {
      return res.status(404).json({ ok: false, error: 'Key not found' });
    }

    const oldHash = keys[idx].keyHash;

    // Get old key data
    const oldDataRaw = await redis.get(`apikey:${oldHash}`);
    if (!oldDataRaw) {
      return res.status(404).json({ ok: false, error: 'Key data not found' });
    }
    const oldData = JSON.parse(oldDataRaw);

    // Revoke old key
    oldData.active = false;
    oldData.rotatedAt = new Date().toISOString();
    await redis.setex(`apikey:${oldHash}`, KEY_TTL, JSON.stringify(oldData));

    // Generate new key with same settings
    const rawKey = generateApiKey();
    const newHash = hashKey(rawKey);

    const newData = {
      clientId: whmcsClientId,
      name: oldData.name,
      created: new Date().toISOString(),
      lastUsed: null,
      rateLimit: oldData.rateLimit,
      active: true,
      rotatedFrom: oldHash.slice(0, 16),
    };

    await redis.setex(`apikey:${newHash}`, KEY_TTL, JSON.stringify(newData));

    // Update client's key list
    keys[idx] = {
      keyHash: newHash,
      name: oldData.name,
      created: newData.created,
      prefix: rawKey.slice(0, 12) + '...',
    };
    await redis.set(`apikeys:client:${whmcsClientId}`, JSON.stringify(keys));

    logger.info(`developer: rotated API key for client ${whmcsClientId}`);

    res.json({
      ok: true,
      key: rawKey,
      keyId: newHash.slice(0, 16),
      warning: 'Save this key now — it will not be shown again. Old key is now revoked.',
    });
  } catch (err) {
    logger.error(`developer/keys/rotate: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── Usage Stats: GET /api/developer/usage ───────────────────────────────────
router.get('/usage', requireSession, async (req, res) => {
  try {
    const { whmcsClientId } = req.user;
    const redis = getRedis();

    const keysRaw = await redis.get(`apikeys:client:${whmcsClientId}`);
    let keys = [];
    try { keys = JSON.parse(keysRaw) || []; } catch {}

    const month = new Date().toISOString().slice(0, 7);
    const today = new Date().toISOString().slice(0, 10);

    let totalMonthlyTokens = 0;
    let totalDailyRequests = 0;
    const perKey = [];

    for (const k of keys) {
      const monthlyTokens = parseInt(await redis.get(`apikey:tokens:${k.keyHash}:${month}`) || '0', 10);
      const dailyRequests = parseInt(await redis.get(`apikey:usage:${k.keyHash}:${today}`) || '0', 10);

      totalMonthlyTokens += monthlyTokens;
      totalDailyRequests += dailyRequests;

      perKey.push({
        keyId: k.keyHash.slice(0, 16),
        name: k.name,
        monthlyTokens,
        todayRequests: dailyRequests,
      });
    }

    res.json({
      ok: true,
      total: {
        monthlyTokens: totalMonthlyTokens,
        todayRequests: totalDailyRequests,
        activeKeys: keys.length,
      },
      keys: perKey,
    });
  } catch (err) {
    logger.error(`developer/usage: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── OpenAI-compatible Chat Completions Endpoint ─────────────────────────────
// POST /api/developer/v1/chat/completions
// Authenticates via API key in Bearer token
router.post('/v1/chat/completions', async (req, res) => {
  try {
    const authHeader = req.headers.authorization || '';
    const token = authHeader.startsWith('Bearer ') ? authHeader.slice(7) : null;

    if (!token || !token.startsWith(KEY_PREFIX)) {
      return res.status(401).json({ error: { message: 'Invalid API key', type: 'authentication_error' } });
    }

    const redis = getRedis();
    const keyHash = hashKey(token);
    const keyDataRaw = await redis.get(`apikey:${keyHash}`);

    if (!keyDataRaw) {
      return res.status(401).json({ error: { message: 'Invalid API key', type: 'authentication_error' } });
    }

    const keyData = JSON.parse(keyDataRaw);
    if (!keyData.active) {
      return res.status(401).json({ error: { message: 'API key has been revoked', type: 'authentication_error' } });
    }

    // Rate limiting (sliding window per minute)
    const rateLimitKey = `apikey:rate:${keyHash}`;
    const currentRate = parseInt(await redis.get(rateLimitKey) || '0', 10);
    if (currentRate >= keyData.rateLimit) {
      return res.status(429).json({
        error: { message: `Rate limit exceeded (${keyData.rateLimit} req/min)`, type: 'rate_limit_error' },
      });
    }
    await redis.incr(rateLimitKey);
    const ttl = await redis.ttl(rateLimitKey);
    if (ttl < 0) await redis.expire(rateLimitKey, 60);

    // Update last used time
    keyData.lastUsed = new Date().toISOString();
    await redis.setex(`apikey:${keyHash}`, KEY_TTL, JSON.stringify(keyData));

    // Track daily requests
    const today = new Date().toISOString().slice(0, 10);
    const usageKey = `apikey:usage:${keyHash}:${today}`;
    await redis.incr(usageKey);
    const usageTtl = await redis.ttl(usageKey);
    if (usageTtl < 0) await redis.expire(usageKey, 2 * 24 * 60 * 60);

    // Check token allowance
    const allowance = await tc.checkAllowance(keyData.clientId);
    if (!allowance.allowed) {
      return res.status(429).json({
        error: { message: 'Token limit reached. Upgrade your plan.', type: 'insufficient_quota' },
      });
    }

    // Forward to the AI proxy
    // Build a synthetic Anthropic-format request and pipe through the proxy
    const { model = 'auto', messages = [], temperature, max_tokens, stream = false } = req.body;

    // Get the DA username for this client
    const daUsername = await redis.get(`da_username:${keyData.clientId}`) || 'api';

    // Use the anthropic proxy handler directly
    const anthropicProxy = require('./anthropicProxy');

    // Create a synthetic req/res to pass through the proxy
    const proxyReq = {
      params: { daUsername },
      headers: {
        authorization: `Bearer ${config.anthropic.apiKey}`,
        'content-type': 'application/json',
      },
      body: {
        model: model === 'auto' ? 'claude-sonnet-4-6' : model,
        messages: messages.map(m => ({
          role: m.role,
          content: m.content,
        })),
        max_tokens: max_tokens || 4096,
        stream: stream,
      },
      _apiKeyClient: keyData.clientId,
      _apiKeyHash: keyHash,
    };

    if (temperature !== undefined) proxyReq.body.temperature = temperature;

    // Track tokens after completion
    const originalJson = res.json.bind(res);
    const originalWrite = res.write.bind(res);
    const originalEnd = res.end.bind(res);

    let capturedOutput = 0;
    const proxyRes = {
      status: (code) => { res.status(code); return proxyRes; },
      json: (data) => {
        // Track token usage for billing
        if (data?.usage) {
          const { input_tokens = 0, output_tokens = 0 } = data.usage;
          capturedOutput = output_tokens;

          const month = new Date().toISOString().slice(0, 7);
          const tokenKey = `apikey:tokens:${keyHash}:${month}`;
          redis.incrby(tokenKey, output_tokens).catch(() => {});
          redis.expire(tokenKey, 35 * 24 * 60 * 60).catch(() => {});
        }
        return originalJson(data);
      },
      set: (k, v) => { res.set(k, v); return proxyRes; },
      setHeader: (k, v) => { res.setHeader(k, v); return proxyRes; },
      write: (data) => { return originalWrite(data); },
      end: (data) => { return originalEnd(data); },
      headersSent: false,
    };

    // Forward the request (simplified — doesn't use full proxy pipeline)
    // For v1: direct Anthropic call with our key
    const https = require('https');
const safeError = require('../utils/safeError');
    const anthropicBody = JSON.stringify({
      model: proxyReq.body.model,
      messages: proxyReq.body.messages,
      max_tokens: proxyReq.body.max_tokens,
      stream: false,
    });

    const anthropicReq = https.request({
      hostname: 'api.anthropic.com',
      path: '/v1/messages',
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'x-api-key': config.anthropic.apiKey,
        'anthropic-version': '2023-06-01',
        'Content-Length': Buffer.byteLength(anthropicBody),
      },
      timeout: 60000,
    }, (anthropicRes) => {
      let raw = '';
      anthropicRes.on('data', chunk => { raw += chunk; });
      anthropicRes.on('end', async () => {
        try {
          const result = JSON.parse(raw);

          // Track usage
          const inputTokens = result.usage?.input_tokens || 0;
          const outputTokens = result.usage?.output_tokens || 0;

          // Bill to customer
          tc.addUsage(keyData.clientId, inputTokens, outputTokens).catch(() => {});

          // Track per-key tokens
          const month = new Date().toISOString().slice(0, 7);
          const tokenKey = `apikey:tokens:${keyHash}:${month}`;
          redis.incrby(tokenKey, outputTokens).catch(() => {});
          redis.expire(tokenKey, 35 * 24 * 60 * 60).catch(() => {});

          // Convert to OpenAI format for developer-friendly response
          const response = {
            id: `chatcmpl-${crypto.randomBytes(12).toString('hex')}`,
            object: 'chat.completion',
            created: Math.floor(Date.now() / 1000),
            model: result.model || proxyReq.body.model,
            choices: [{
              index: 0,
              message: {
                role: 'assistant',
                content: result.content?.[0]?.text || '',
              },
              finish_reason: result.stop_reason === 'end_turn' ? 'stop' : (result.stop_reason || 'stop'),
            }],
            usage: {
              prompt_tokens: inputTokens,
              completion_tokens: outputTokens,
              total_tokens: inputTokens + outputTokens,
            },
          };

          res.json(response);
        } catch (e) {
          logger.error(`developer/chat: parse error: ${e.message}`);
          res.status(500).json({ error: { message: 'AI service error', type: 'server_error' } });
        }
      });
    });

    anthropicReq.on('error', (e) => {
      logger.error(`developer/chat: request error: ${e.message}`);
      res.status(502).json({ error: { message: 'AI service unavailable', type: 'server_error' } });
    });

    anthropicReq.write(anthropicBody);
    anthropicReq.end();

  } catch (err) {
    logger.error(`developer/chat: ${err.message}`);
    res.status(500).json({ error: { message: safeError(err), type: 'server_error' } });
  }
});

/**
 * Validate an API key without making a request.
 * Used by middleware to authenticate API requests.
 *
 * @param {string} rawKey   The full gcm_xxxx key
 * @returns {Promise<{valid: boolean, clientId?: string, rateLimit?: number}>}
 */
async function validateApiKey(rawKey) {
  if (!rawKey || !rawKey.startsWith(KEY_PREFIX)) return { valid: false };

  const redis = getRedis();
  const keyHash = hashKey(rawKey);
  const dataRaw = await redis.get(`apikey:${keyHash}`);
  if (!dataRaw) return { valid: false };

  const data = JSON.parse(dataRaw);
  if (!data.active) return { valid: false };

  return {
    valid: true,
    clientId: data.clientId,
    rateLimit: data.rateLimit,
    name: data.name,
  };
}

module.exports = router;
module.exports.validateApiKey = validateApiKey;
module.exports.API_PLANS = API_PLANS;
