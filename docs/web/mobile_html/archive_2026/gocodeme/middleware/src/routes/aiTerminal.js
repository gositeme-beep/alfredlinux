'use strict';

/**
 * AI Terminal Routes — /api/ai-terminal/*
 *
 * Translates natural language commands to shell commands using Claude.
 * The customer describes what they want in English, and Alfred returns
 * the appropriate bash command(s) to execute.
 *
 * Endpoints:
 *   POST /api/ai-terminal/translate   — NL → shell command translation
 *   POST /api/ai-terminal/explain     — Explain what a command does
 *
 * Token usage is tracked against the customer's plan allowance.
 */

const express = require('express');
const router  = express.Router();
const https   = require('https');
const { URL } = require('url');

const { requireSession } = require('../auth/middleware');
const tc     = require('../tokens/tokenCounter');
const logger = require('../logger');
const config = require('../config');
const { getRedis } = require('../redis');
const { recordUsage } = require('../tokens/usageTracker');
const { calculateCost } = require('../billing/pricing');

const ANTHROPIC_API_HOST = 'https://api.anthropic.com';
const ANTHROPIC_API_KEY  = config.anthropic.apiKey;
const UPGRADE_URL = process.env.AI_UPGRADE_URL || 'https://gositeme.com/pricing.php';
const TOPUP_URL = process.env.AI_TOPUP_URL || 'https://gositeme.com/pricing.php#topup';

// ── Helper: resolve whmcsClientId from session ───────────────────────────────
async function resolveClientId(daUsername) {
  const redis = getRedis();
  const cached = await redis.get(`client_id_by_da:${daUsername}`);
  if (cached) return cached;

  let cursor = '0';
  do {
    const [nextCursor, keys] = await redis.scan(cursor, 'MATCH', 'da_username:*', 'COUNT', 100);
    cursor = nextCursor;
    for (const key of keys) {
      const val = await redis.get(key);
      if (val === daUsername) {
        const clientId = key.split(':')[1];
        await redis.set(`client_id_by_da:${daUsername}`, clientId, 'EX', 86400);
        return clientId;
      }
    }
  } while (cursor !== '0');
  return null;
}

// ── Helper: call Anthropic API (non-streaming) ──────────────────────────────
function callAnthropic(body) {
  return new Promise((resolve, reject) => {
    const url = new URL('/v1/messages', ANTHROPIC_API_HOST);
    const postData = JSON.stringify(body);

    const req = https.request({
      hostname: url.hostname,
      port: 443,
      path: url.pathname,
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'x-api-key': ANTHROPIC_API_KEY,
        'anthropic-version': '2023-06-01',
        'Content-Length': Buffer.byteLength(postData),
      },
    }, (res) => {
      let data = '';
      res.on('data', chunk => data += chunk);
      res.on('end', () => {
        try {
          resolve(JSON.parse(data));
        } catch (e) {
          reject(new Error(`Anthropic API parse error: ${e.message}`));
        }
      });
    });

    req.on('error', reject);
    req.write(postData);
    req.end();
  });
}

// ── POST /translate — Natural language → shell command ──────────────────────
router.post('/translate', requireSession, async (req, res) => {
  const { prompt, cwd, shell } = req.body;
  if (!prompt) return res.status(400).json({ ok: false, error: 'prompt required' });

  const { daUsername, whmcsClientId } = req.user;
  const clientId = whmcsClientId || await resolveClientId(daUsername);

  // Check token allowance
  if (clientId) {
    const usage = await tc.getUsage(clientId);
    if (usage.limit > 0 && usage.used >= usage.limit * 2) {
      return res.status(429).json({ ok: false, error: `Token limit exceeded - AI is paused. Buy tokens at ${TOPUP_URL} or upgrade at ${UPGRADE_URL}` });
    }
  }

  try {
    const modelId = 'claude-haiku-4-5-20251001'; // Use Haiku for speed + cost
    const systemPrompt = `You are Alfred Coder, the AI terminal assistant for GoCodeMe IDE (by GoSiteMe, the parent hosting company). You translate natural language instructions into bash shell commands.

Rules:
- Return ONLY the command(s) to execute, no explanation, no markdown
- If multiple commands are needed, separate with && or newlines
- Use safe defaults (e.g., -i for interactive, --dry-run if destructive)
- For destructive operations (rm -rf, drop, etc.), add a comment warning
- The user's current directory is: ${cwd || '~'}
- Shell: ${shell || 'bash'}
- Platform: Linux
- The user may have node, python3, git, npm, yarn installed
- If the request is unclear or dangerous, prefix with: # WARNING: `;

    const result = await callAnthropic({
      model: modelId,
      max_tokens: 500,
      system: systemPrompt,
      messages: [{ role: 'user', content: prompt }],
    });

    // Track usage
    const inputTokens  = result.usage?.input_tokens || 0;
    const outputTokens = result.usage?.output_tokens || 0;

    if (clientId) {
      const usageResult = await tc.addUsage(clientId, inputTokens, outputTokens);
      await recordUsage(clientId, { model: modelId, inputTokens, outputTokens, daUsername });
      // Fire billing alerts
      if (usageResult.limit > 0) {
        const { checkAlerts } = require('../billing/alerts');
        checkAlerts({ whmcsClientId: clientId, daUsername, used: usageResult.used, limit: usageResult.limit, percentUsed: usageResult.percentUsed })
          .catch(e => logger.error(`aiTerminal billing alert: ${e.message}`));
      }
    }

    const command = result.content?.[0]?.text?.trim() || '';
    const cost = calculateCost(modelId, inputTokens, outputTokens);

    res.json({
      ok: true,
      command,
      model: modelId,
      tokens: { input: inputTokens, output: outputTokens },
      cost: cost.totalCost,
    });
  } catch (err) {
    logger.error('AI Terminal translate error:', err.message);
    res.status(500).json({ ok: false, error: 'Translation failed' });
  }
});

// ── POST /explain — Explain what a command does ─────────────────────────────
router.post('/explain', requireSession, async (req, res) => {
  const { command } = req.body;
  if (!command) return res.status(400).json({ ok: false, error: 'command required' });

  const { daUsername, whmcsClientId } = req.user;
  const clientId = whmcsClientId || await resolveClientId(daUsername);

  if (clientId) {
    const usage = await tc.getUsage(clientId);
    if (usage.limit > 0 && usage.used >= usage.limit * 2) {
      return res.status(429).json({ ok: false, error: `Token limit exceeded - AI is paused. Buy tokens at ${TOPUP_URL} or upgrade at ${UPGRADE_URL}` });
    }
  }

  try {
    const modelId = 'claude-haiku-4-5-20251001';
    const result = await callAnthropic({
      model: modelId,
      max_tokens: 500,
      system: 'You are Alfred Coder, the AI coding assistant. Explain what the given bash command does in 1-3 sentences. Be concise. Mention any risks (file deletion, network access, permissions changes).',
      messages: [{ role: 'user', content: `Explain: ${command}` }],
    });

    const inputTokens  = result.usage?.input_tokens || 0;
    const outputTokens = result.usage?.output_tokens || 0;

    if (clientId) {
      const usageResult = await tc.addUsage(clientId, inputTokens, outputTokens);
      await recordUsage(clientId, { model: modelId, inputTokens, outputTokens, daUsername });
      // Fire billing alerts
      if (usageResult.limit > 0) {
        const { checkAlerts } = require('../billing/alerts');
        checkAlerts({ whmcsClientId: clientId, daUsername, used: usageResult.used, limit: usageResult.limit, percentUsed: usageResult.percentUsed })
          .catch(e => logger.error(`aiTerminal billing alert: ${e.message}`));
      }
    }

    res.json({
      ok: true,
      explanation: result.content?.[0]?.text?.trim() || '',
      tokens: { input: inputTokens, output: outputTokens },
    });
  } catch (err) {
    logger.error('AI Terminal explain error:', err.message);
    res.status(500).json({ ok: false, error: 'Explanation failed' });
  }
});

module.exports = router;
