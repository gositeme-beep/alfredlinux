'use strict';

/**
 * claude.js — Real Anthropic API chat endpoint
 *
 * POST /api/claude/chat
 *   Body: { messages: [{role, content}], system?, model?, maxTokens? }
 *   Auth: requireSession + token allowance check
 *
 * Supports two modes:
 *   - Streaming (Accept: text/event-stream)  → SSE stream, token count reported at end
 *   - Non-streaming (default)               → JSON { ok, content, usage }
 *
 * Token reporting:
 *   After each successful response the used tokens are reported back into the
 *   customer's counter (same path as a real agent call) and billing alerts fire.
 *
 * Rate limits:
 *   Inherits the global express-rate-limit on /api/* (60 req/min by default).
 *   Per-customer token allowance enforced via tc.checkAllowance().
 */

const express    = require('express');
const router     = express.Router();
const Anthropic  = require('@anthropic-ai/sdk');
const { requireSession } = require('../auth/middleware');
const tc         = require('../tokens/tokenCounter');
const { recordUsage } = require('../tokens/usageTracker');
const alfredMemory  = require('../alfredMemory');
const logger     = require('../logger');
const config     = require('../config');
const safeError = require('../utils/safeError');

const DEFAULT_MODEL      = process.env.ANTHROPIC_MODEL || 'claude-sonnet-4-6';
const DEFAULT_MAX_TOKENS = 4096;
const MAX_MAX_TOKENS     = 8192;
const UPGRADE_URL = process.env.AI_UPGRADE_URL || 'https://gositeme.com/pricing.php';
const TOPUP_URL = process.env.AI_TOPUP_URL || 'https://gositeme.com/pricing.php#topup';

// Lazy-init Anthropic client (so the module loads even without a real key)
let _anthropic = null;
function getAnthropic() {
  if (!_anthropic) {
    const apiKey = config.anthropic.apiKey;
    if (!apiKey || apiKey === 'YOUR_ANTHROPIC_API_KEY_HERE') {
      throw new Error('ANTHROPIC_API_KEY is not configured — set it in .env');
    }
    _anthropic = new Anthropic({ apiKey });
  }
  return _anthropic;
}

// ── POST /api/claude/chat ─────────────────────────────────────────────────────
router.post('/chat', requireSession, async (req, res) => {
  const { whmcsClientId, daUsername } = req.user;

  // ── 1. Check token allowance before hitting Anthropic ─────────────────────
  const allowance = await tc.checkAllowance(whmcsClientId);
  if (!allowance.allowed) {
    return res.status(402).json({
      ok:    false,
      error: `Monthly token limit reached - AI is paused. Buy a token pack at ${TOPUP_URL} or upgrade your plan at ${UPGRADE_URL}`,
      usage: allowance.usage,
    });
  }

  // ── 2. Validate request body ───────────────────────────────────────────────
  const {
    messages,
    system     = undefined,
    model      = DEFAULT_MODEL,
    maxTokens  = DEFAULT_MAX_TOKENS,
    stream     = false,
  } = req.body;

  if (!Array.isArray(messages) || messages.length === 0) {
    return res.status(400).json({ ok: false, error: 'messages array is required' });
  }

  const clampedMaxTokens = Math.min(parseInt(maxTokens, 10) || DEFAULT_MAX_TOKENS, MAX_MAX_TOKENS);

  // Build params
  const params = {
    model,
    max_tokens: clampedMaxTokens,
    messages,
  };
  if (system) params.system = system;

  // ── Cross-instance unified memory ─────────────────────────────────────
  try {
    const crossCtx = await alfredMemory.getCrossContext(whmcsClientId, 'ide');
    if (crossCtx) {
      params.system = (params.system || '') + crossCtx;
    }
  } catch (_) {}

  // ── 3a. Non-streaming response ────────────────────────────────────────────
  const wantsStream = stream === true || req.headers.accept === 'text/event-stream';

  if (!wantsStream) {
    try {
      const anthropic = getAnthropic();
      const msg = await anthropic.messages.create(params);

      const inputTokens  = msg.usage?.input_tokens  || 0;
      const outputTokens = msg.usage?.output_tokens || 0;
      const content      = msg.content?.[0]?.text   || '';

      // Report tokens consumed
      const usageResult = await tc.addUsage(whmcsClientId, inputTokens, outputTokens);
      recordUsage(whmcsClientId, { model, inputTokens, outputTokens, daUsername }).catch(e => logger.error(`claude recordUsage: ${e.message}`));
      fireAlerts(whmcsClientId, daUsername, usageResult);

      logger.info(`claude/chat: client ${whmcsClientId} used ${inputTokens}in+${outputTokens}out tokens`);

      // Cross-instance memory write
      alfredMemory.recordInteraction(whmcsClientId, {
        source: 'ide',
        userMessage: (messages[messages.length - 1]?.content || '').toString(),
        alfredResponse: content,
        model,
        agent: 'alfred',
      }).catch(() => {});

      return res.json({
        ok:      true,
        content,
        model:   msg.model,
        usage:   {
          inputTokens,
          outputTokens,
          totalTokens: inputTokens + outputTokens,
          running: usageResult,
        },
        stopReason: msg.stop_reason,
      });
    } catch (err) {
      logger.error(`claude/chat error: ${err.message}`);
      const status = err.status || 500;
      return res.status(status).json({ ok: false, error: safeError(err) });
    }
  }

  // ── 3b. Streaming response (SSE) ──────────────────────────────────────────
  res.setHeader('Content-Type', 'text/event-stream');
  res.setHeader('Cache-Control', 'no-cache');
  res.setHeader('Connection', 'keep-alive');
  res.flushHeaders();

  let inputTokens  = 0;
  let outputTokens = 0;
  let fullContent  = '';

  try {
    const anthropic = getAnthropic();
    const stream = await anthropic.messages.stream({ ...params, stream: undefined });

    stream.on('text', (text) => {
      fullContent += text;
      res.write(`data: ${JSON.stringify({ type: 'text', text })}\n\n`);
    });

    stream.on('message', (msg) => {
      inputTokens  = msg.usage?.input_tokens  || 0;
      outputTokens = msg.usage?.output_tokens || 0;
    });

    await stream.finalMessage();

    // Report tokens
    const usageResult = await tc.addUsage(whmcsClientId, inputTokens, outputTokens);
    recordUsage(whmcsClientId, { model, inputTokens, outputTokens, daUsername }).catch(e => logger.error(`claude stream recordUsage: ${e.message}`));
    fireAlerts(whmcsClientId, daUsername, usageResult);

    logger.info(`claude/chat stream: client ${whmcsClientId} used ${inputTokens}in+${outputTokens}out tokens`);

    res.write(`data: ${JSON.stringify({
      type:   'done',
      usage:  { inputTokens, outputTokens, totalTokens: inputTokens + outputTokens, running: usageResult },
    })}\n\n`);
    res.end();
  } catch (err) {
    logger.error(`claude/chat stream error: ${err.message}`);
    res.write(`data: ${JSON.stringify({ type: 'error', error: safeError(err) })}\n\n`);
    res.end();
  }
});

// ── GET /api/claude/models ────────────────────────────────────────────────────
// Returns the list of available models + current default
router.get('/models', (_req, res) => {
  res.json({
    ok: true,
    default: DEFAULT_MODEL,
    models: [
      { id: 'claude-opus-4-5',         name: 'Claude Opus 4.5',        tier: 'power'   },
      { id: 'claude-sonnet-4-6',       name: 'Claude Sonnet 4.6',      tier: 'standard', recommended: true },
      { id: 'claude-haiku-3-5',        name: 'Claude Haiku 3.5',       tier: 'fast'    },
    ],
  });
});

// ── Helpers ───────────────────────────────────────────────────────────────────

function fireAlerts(whmcsClientId, daUsername, usageResult) {
  if (usageResult.limit > 0) {
    try {
      const { checkAlerts } = require('../billing/alerts');
      checkAlerts({
        whmcsClientId,
        daUsername,
        used:        usageResult.used,
        limit:       usageResult.limit,
        percentUsed: usageResult.percentUsed,
      }).catch(e => logger.error(`billing alert: ${e.message}`));
    } catch (_) {}
  }
}

module.exports = router;
