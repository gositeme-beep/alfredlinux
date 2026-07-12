'use strict';

/**
 * anthropicProxy.js — Transparent Anthropic API proxy for Theia IDE
 *
 * Theia's @theia/ai-anthropic module calls the Anthropic SDK directly.
 * By setting ANTHROPIC_BASE_URL to point here, all SDK traffic flows through
 * this proxy, enabling per-customer token tracking.
 *
 * URL pattern:
 *   POST /api/anthropic-proxy/:daUsername/v1/messages
 *
 * The proxy:
 *   1. Resolves daUsername → whmcsClientId via Redis reverse mapping
 *   2. Checks token allowance
 *   3. Forwards the request to https://api.anthropic.com/v1/messages
 *   4. For non-streaming: extracts usage from JSON response
 *   5. For streaming: buffers SSE events to capture final usage
 *   6. Reports consumed tokens via tokenCounter.addUsage()
 */

const express = require('express');
const https   = require('https');
const http    = require('http');
const fs      = require('fs');
const path    = require('path');
const { URL } = require('url');
const router  = express.Router({ mergeParams: true });

const tc     = require('../tokens/tokenCounter');
const logger = require('../logger');
const config = require('../config');
const { getRedis } = require('../redis');
const { recordUsage } = require('../tokens/usageTracker');
const alfredMemory  = require('../alfredMemory');
const budget         = require('../tokens/tokenBudget');
const { calculateCost } = require('../billing/pricing');
const { routeRequest, getProvider, classifyRequest, getTokenMultiplier, getTokenMultiplierAsync, getModelByKey } = require('../billing/modelRouter');
const { getSpendMode, enforceModelForSpendMode } = require('../billing/spendMode');
const { buildSmartOffer } = require('../billing/smartOffers');
const { trackOfferEvent } = require('../billing/offerTelemetry');
const { anthropicToOpenAI, openAIToAnthropic, createStreamTranslator } = require('../billing/formatTranslator');
const anomaly = require('../billing/anomalyDetector');

const ANTHROPIC_API_HOST   = 'https://api.anthropic.com';
const ANTHROPIC_API_KEY    = config.anthropic.apiKey;
const TOGETHER_API_KEY     = config.apiKeys.together;
const UPGRADE_URL          = process.env.AI_UPGRADE_URL || 'https://gositeme.com/pricing.php';
const TOPUP_URL            = process.env.AI_TOPUP_URL || 'https://gositeme.com/pricing.php#topup';
const crypto = require('crypto');

// ── High-multiplier concurrency limiter ──────────────────────────────────────
// Prevents cascade failures when many users hit expensive models simultaneously.
// Redis-backed: tracks in-flight requests per model. Rejects if over limit.
const HIGH_MULT_CONCURRENCY_LIMIT = parseInt(process.env.HIGH_MULT_CONCURRENCY || '3', 10);
const HIGH_MULT_THRESHOLD = parseFloat(process.env.HIGH_MULT_THRESHOLD || '10');
const CONCURRENCY_TTL = 120; // seconds — auto-expire if response never completes

async function acquireHighMultSlot(modelKey) {
  const redis = getRedis();
  const key = `concurrency:inflight:${modelKey}`;
  const current = parseInt(await redis.incr(key), 10);
  await redis.expire(key, CONCURRENCY_TTL);
  if (current > HIGH_MULT_CONCURRENCY_LIMIT) {
    await redis.decr(key);
    return false;
  }
  return true;
}

async function releaseHighMultSlot(modelKey) {
  const redis = getRedis();
  const key = `concurrency:inflight:${modelKey}`;
  const val = parseInt(await redis.get(key) || '0', 10);
  if (val > 0) await redis.decr(key);
}

function nextUtcMidnightIso() {
  const now = new Date();
  const next = new Date(Date.UTC(now.getUTCFullYear(), now.getUTCMonth(), now.getUTCDate() + 1, 0, 0, 0));
  return next.toISOString();
}

function utcDate() {
  return new Date().toISOString().slice(0, 10);
}

function utcMonth() {
  return new Date().toISOString().slice(0, 7);
}

async function getTeamBudgetCheck(clientId) {
  const redis = getRedis();
  const teamId = await redis.get(`team:by_client:${clientId}`);
  if (!teamId) return { isTeamMember: false, blocked: false };

  const budgetRaw = await redis.get(`team:budget:${teamId}`);
  if (!budgetRaw) return { isTeamMember: true, teamId, blocked: false };

  let budgetCfg;
  try { budgetCfg = JSON.parse(budgetRaw); } catch { budgetCfg = null; }
  if (!budgetCfg || !budgetCfg.enforce) return { isTeamMember: true, teamId, blocked: false };

  const day = utcDate();
  const month = utcMonth();
  const dailyTokens = parseInt((await redis.get(`team:budget:daily:tokens:${teamId}:${day}`)) || '0', 10);
  const dailyUsd = parseFloat((await redis.get(`team:budget:daily:usd:${teamId}:${day}`)) || '0');
  const monthlyTokens = parseInt((await redis.get(`team:budget:monthly:tokens:${teamId}:${month}`)) || '0', 10);

  let reason = null;
  if (budgetCfg.dailyTokenCap > 0 && dailyTokens >= budgetCfg.dailyTokenCap) reason = 'team_daily_token_cap';
  if (!reason && budgetCfg.dailyUsdCap > 0 && dailyUsd >= budgetCfg.dailyUsdCap) reason = 'team_daily_usd_cap';
  if (!reason && budgetCfg.monthlyTokenCap > 0 && monthlyTokens >= budgetCfg.monthlyTokenCap) reason = 'team_monthly_token_cap';

  return {
    isTeamMember: true,
    teamId,
    blocked: !!reason,
    reason,
    usage: { dailyTokens, dailyUsd: Math.round(dailyUsd * 100) / 100, monthlyTokens },
    budget: budgetCfg,
  };
}

async function rateLimitPayload(clientId, message, details = {}) {
  const kind = details.kind || 'limit_reached';
  const offer = await buildSmartOffer(clientId, kind, details);

  // Best-effort telemetry for offer impressions at the exact limit moment.
  trackOfferEvent({
    clientId,
    event: 'shown',
    variant: offer.variant,
    limitKind: kind,
    source: 'anthropic_proxy_limit',
  }).catch(() => {});

  return {
    type: 'error',
    error: { type: 'rate_limit_error', message },
    limit: {
      reset_at_utc: nextUtcMidnightIso(),
      upgrade_url: UPGRADE_URL,
      topup_url: TOPUP_URL,
      variant: offer.variant,
      card: offer.card,
      ...details,
    },
  };
}

// ── Per-provider circuit breaker ─────────────────────────────────────────────
// Tracks consecutive failures per provider. After THRESHOLD failures in
// WINDOW_MS, the provider is "open" (broken) for COOLDOWN_MS, then moves to
// "half-open" where a single test request decides if it recovers.
// This ensures if Anthropic/Google/etc. goes down, we auto-route to alternatives.
const CIRCUIT_THRESHOLD = 3;          // consecutive failures to trip
const CIRCUIT_COOLDOWN_MS = 60000;    // 60s cooldown before half-open
const CIRCUIT_WINDOW_MS = 120000;     // 2-min sliding window for failure counting
const providerCircuits = new Map();   // providerName → { failures: [], state, openedAt }

function getCircuit(providerName) {
  if (!providerCircuits.has(providerName)) {
    providerCircuits.set(providerName, { failures: [], state: 'closed', openedAt: 0 });
  }
  return providerCircuits.get(providerName);
}

function recordProviderFailure(providerName) {
  const circuit = getCircuit(providerName);
  const now = Date.now();
  circuit.failures.push(now);
  // Prune old failures outside window
  circuit.failures = circuit.failures.filter(t => now - t < CIRCUIT_WINDOW_MS);
  if (circuit.failures.length >= CIRCUIT_THRESHOLD && circuit.state === 'closed') {
    circuit.state = 'open';
    circuit.openedAt = now;
    logger.warn(`circuit-breaker: ${providerName} OPEN — ${circuit.failures.length} failures in ${CIRCUIT_WINDOW_MS / 1000}s`);
  }
}

function recordProviderSuccess(providerName) {
  const circuit = getCircuit(providerName);
  circuit.failures = [];
  if (circuit.state !== 'closed') {
    logger.info(`circuit-breaker: ${providerName} CLOSED (recovered)`);
    circuit.state = 'closed';
    circuit.openedAt = 0;
  }
}

function isProviderAvailable(providerName) {
  const circuit = getCircuit(providerName);
  if (circuit.state === 'closed') return true;
  if (circuit.state === 'open') {
    // Check if cooldown expired → move to half-open
    if (Date.now() - circuit.openedAt >= CIRCUIT_COOLDOWN_MS) {
      circuit.state = 'half-open';
      logger.info(`circuit-breaker: ${providerName} HALF-OPEN — allowing test request`);
      return true; // allow one test request
    }
    return false; // still in cooldown
  }
  // half-open: allow the request (test probe)
  return true;
}

// ── Request deduplication ────────────────────────────────────────────────────
// Theia retries on timeout, IDE disconnects, SSE reconnects — all send the
// exact same request again. Without dedup, we pay the full API cost twice.
// Cache: hash(clientId + last 2 messages) → response, with 30s TTL.
const DEDUP_CACHE = new Map();
const DEDUP_TTL_MS = 30000; // 30 seconds
const DEDUP_MAX_SIZE = 200; // max entries (LRU-ish eviction)

function getDedupeKey(clientId, messages) {
  // Hash the last 2 messages (unique enough to identify a retry)
  const tail = (messages || []).slice(-2);
  // SECURITY (VULN-R2-20): Use SHA-256 instead of MD5 to prevent hash collisions
  // that could leak cached responses across tenants
  const raw = clientId + ':' + JSON.stringify(tail).slice(0, 4000);
  return crypto.createHash('sha256').update(raw).digest('hex');
}

function setDedupeCache(key, response) {
  // Evict oldest if over capacity
  if (DEDUP_CACHE.size >= DEDUP_MAX_SIZE) {
    const oldest = DEDUP_CACHE.keys().next().value;
    DEDUP_CACHE.delete(oldest);
  }
  DEDUP_CACHE.set(key, { response, ts: Date.now() });
  setTimeout(() => DEDUP_CACHE.delete(key), DEDUP_TTL_MS);
}

function getDedupeCache(key) {
  const entry = DEDUP_CACHE.get(key);
  if (!entry) return null;
  if (Date.now() - entry.ts > DEDUP_TTL_MS) {
    DEDUP_CACHE.delete(key);
    return null;
  }
  return entry.response;
}

// ── Helpers ──────────────────────────────────────────────────────────────────

/**
 * Look up whmcsClientId from DA username.
 * Uses a reverse-mapping cache key: client_id_by_da:<daUsername> → clientId
 * Falls back to scanning da_username:* keys and caching the result.
 */
async function resolveClientId(daUsername) {
  const redis = getRedis();

  // 1. Check reverse cache
  const cached = await redis.get(`client_id_by_da:${daUsername}`);
  if (cached) return cached;

  // 2. Scan forward mapping keys (da_username:<id> → <username>)
  let cursor = '0';
  do {
    const [nextCursor, keys] = await redis.scan(cursor, 'MATCH', 'da_username:*', 'COUNT', 100);
    cursor = nextCursor;

    for (const key of keys) {
      const val = await redis.get(key);
      if (val === daUsername) {
        const clientId = key.split(':')[1];
        // Cache the reverse mapping (24h TTL)
        await redis.set(`client_id_by_da:${daUsername}`, clientId, 'EX', 86400);
        return clientId;
      }
    }
  } while (cursor !== '0');

  return null;
}

/**
 * Fire billing alerts (same logic as claude.js).
 */
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

// ── Raw body capture + custom AI instructions injection ──────────────────────
// We need the raw body to forward to Anthropic. express.json() already parsed it,
// but we can reconstruct from req.body. We also handle raw buffer for safety.
// ── Conversation pruning ──────────────────────────────────────────────────
// Anthropic has a 200K token context window.  Theia sends the full chat
// history with every request, and tool-result messages (file contents, logs,
// etc.) add up fast.  If the serialised body is too large we progressively
// drop the oldest middle messages, keeping:
//   • The first user message   (contains original task context)
//   • The most recent messages  (ongoing conversation)
//   • A "[conversation trimmed]" marker so the model knows it lost context
//
// We estimate ~4 characters per token.
// The hard API limit is 200K tokens for the ENTIRE request (system + tools +
// messages).  We budget a maximum of 120K for messages so that system prompt,
// tool definitions (~25K for 180 tools), and other overhead have room.
const MAX_ESTIMATED_TOKENS = 80000; // messages-only budget (system+tools can take 60-80K)
const COMPACT_THRESHOLD    = 0.75;  // suggest compacting at 75% of message budget
const COMPACT_CRITICAL     = 0.90;  // critical urgency at 90%

// ── Output token cap ─────────────────────────────────────────────────────
// Prevent runaway output generation.  Theia may request max_tokens of 16K+
// with no upper bound.  Output costs $15/MTok on Sonnet — capping saves money.
const MAX_OUTPUT_TOKENS = parseInt(process.env.MAX_OUTPUT_TOKENS, 10) || 16384;

// ── Tool result truncation ──────────────────────────────────────────────
// IDE tool results (file reads, terminal output, search results) can be huge.
// A single read_file can dump 50K chars — burned as input tokens on every turn.
// Truncating old tool results saves massive input costs while keeping recent ones.
const TOOL_RESULT_MAX_CHARS = parseInt(process.env.TOOL_RESULT_MAX_CHARS, 10) || 12000;

// ── Extended thinking protection ────────────────────────────────────────
// Extended thinking generates massive output tokens (often 10K–30K) charged
// at $15/MTok on Sonnet. Strip the beta feature unless explicitly allowed.
// The thinking tokens are output tokens and count toward the user's plan limit,
// but the value-per-token is low (internal reasoning the user can't see).
const ALLOW_EXTENDED_THINKING = process.env.ALLOW_EXTENDED_THINKING === 'true';

/**
 * Estimate token count from a messages array (rough: chars / 4).
 */
function estimateTokens(messages) {
  let chars = 0;
  for (const msg of messages) {
    if (typeof msg.content === 'string') {
      chars += msg.content.length;
    } else if (Array.isArray(msg.content)) {
      for (const block of msg.content) {
        if (block.type === 'text')       chars += (block.text || '').length;
        else if (block.type === 'image') chars += 1000; // base64 images are large but get tokenised differently
        else                             chars += JSON.stringify(block).length;
      }
    }
  }
  return Math.ceil(chars / 4);
}

/**
 * Trim the messages array to stay under the token limit.
 * Strategy: keep first message (original context) + progressively more
 * recent messages.  Drop from index 1 inward until under budget.
 * IMPORTANT: tool_use/tool_result pairs must stay together — never drop
 * a tool_use without also dropping the following tool_result, and vice versa.
 */
function pruneMessages(messages) {
  if (!Array.isArray(messages) || messages.length <= 2) return messages;

  let est = estimateTokens(messages);
  if (est <= MAX_ESTIMATED_TOKENS) return messages;

  logger.info(`conversation-prune: estimated ${est} tokens (${messages.length} msgs) — trimming`);

  // Keep first message + as many recent messages as possible
  const first = messages[0]; // first user message — task context
  let remaining = messages.slice(1);

  // Drop oldest messages from the front of `remaining` until under budget
  // Always drop tool_use + tool_result pairs together
  while (remaining.length > 1 && estimateTokens([first, ...remaining]) > MAX_ESTIMATED_TOKENS) {
    const dropped = remaining.shift();
    // If we just dropped an assistant message with tool_use blocks,
    // the next message is likely a user tool_result — drop it too
    if (dropped.role === 'assistant' && remaining.length > 0 && hasToolContent(dropped, 'tool_use') && hasToolContent(remaining[0], 'tool_result')) {
      remaining.shift();
    }
    // If we land on a tool_result without its tool_use, drop it too
    while (remaining.length > 0 && hasToolContent(remaining[0], 'tool_result')) {
      remaining.shift();
    }
  }

  // If a single remaining message is STILL over budget, truncate its content
  if (remaining.length === 1 && estimateTokens([first, ...remaining]) > MAX_ESTIMATED_TOKENS) {
    const msg = { ...remaining[0] };
    if (typeof msg.content === 'string') {
      const maxChars = MAX_ESTIMATED_TOKENS * 4 - (first.content?.length || 0) - 500;
      msg.content = msg.content.slice(-Math.max(maxChars, 4000));
    }
    remaining = [msg];
  }

  // Final safety: strip any orphaned tool_result messages whose tool_use_id
  // doesn't appear in a prior assistant message
  remaining = stripOrphanedToolResults(remaining);

  // Insert a context summary of the trimmed messages + marker
  const trimmedCount = messages.length - 1 - remaining.length;
  const droppedMessages = messages.slice(1, 1 + trimmedCount);
  const summary = summarizeDroppedMessages(droppedMessages, trimmedCount);

  // We need to ensure valid alternation: user/assistant/user/assistant
  const result = [first];
  result.push({
    role: 'user',
    content: summary,
  });
  result.push({
    role: 'assistant',
    content: 'Understood. I have the context summary and will continue from here.',
  });
  result.push(...remaining);

  logger.info(`conversation-prune: trimmed ${trimmedCount} messages, ${messages.length} → ${result.length}`);
  return result;
}

/**
 * Summarize dropped messages into a compact context block.
 * Extracts key actions (files modified, tools used, decisions made)
 * without calling an external API — instant and free.
 */
function summarizeDroppedMessages(dropped, count) {
  const filesModified = new Set();
  const filesRead = new Set();
  const toolsUsed = new Set();
  const commands = [];
  const keyDecisions = [];

  for (const msg of dropped) {
    if (!Array.isArray(msg.content)) {
      // Plain text — extract short key phrases
      const text = typeof msg.content === 'string' ? msg.content : '';
      if (msg.role === 'user' && text.length > 20 && text.length < 500) {
        keyDecisions.push(text.slice(0, 120).trim());
      }
      continue;
    }

    for (const block of msg.content) {
      if (block.type === 'tool_use') {
        toolsUsed.add(block.name);
        // Extract file paths from common tool inputs
        const input = block.input || {};
        if (input.path || input.filePath) {
          const p = input.path || input.filePath;
          if (block.name === 'write_file') filesModified.add(p);
          else if (block.name === 'read_file') filesRead.add(p);
        }
        if (input.command && block.name === 'run_terminal_command') {
          commands.push(input.command.slice(0, 80));
        }
      }
      if (block.type === 'text' && msg.role === 'user') {
        const t = (block.text || '').trim();
        if (t.length > 20 && t.length < 500 && !t.startsWith('[')) {
          keyDecisions.push(t.slice(0, 120));
        }
      }
    }
  }

  // Build compact summary
  const parts = [`[Context: ${count} earlier messages were trimmed. Summary of what happened:]`];
  if (filesModified.size > 0) parts.push(`Files modified: ${[...filesModified].slice(0, 10).join(', ')}`);
  if (filesRead.size > 0) parts.push(`Files read: ${[...filesRead].slice(0, 8).join(', ')}`);
  if (toolsUsed.size > 0) parts.push(`Tools used: ${[...toolsUsed].slice(0, 10).join(', ')}`);
  if (commands.length > 0) parts.push(`Commands run: ${commands.slice(0, 5).join('; ')}`);
  if (keyDecisions.length > 0) parts.push(`Key context: ${keyDecisions.slice(0, 5).join(' | ')}`);

  return parts.join('\n');
}

/**
 * Check if a message contains content blocks of a specific tool type.
 */
function hasToolContent(msg, blockType) {
  if (!msg || !Array.isArray(msg.content)) return false;
  return msg.content.some(b => b.type === blockType);
}

/**
 * Remove tool_result content blocks that reference tool_use_ids
 * not present in any prior assistant message. This prevents the
 * "unexpected tool_use_id" error from the Anthropic API.
 */
function stripOrphanedToolResults(messages) {
  // Collect all tool_use IDs from assistant messages
  const toolUseIds = new Set();
  const result = [];

  for (const msg of messages) {
    if (msg.role === 'assistant' && Array.isArray(msg.content)) {
      for (const block of msg.content) {
        if (block.type === 'tool_use' && block.id) {
          toolUseIds.add(block.id);
        }
      }
      result.push(msg);
    } else if (msg.role === 'user' && Array.isArray(msg.content)) {
      // Filter out orphaned tool_result blocks
      const filtered = msg.content.filter(block => {
        if (block.type === 'tool_result') {
          return toolUseIds.has(block.tool_use_id);
        }
        return true; // keep text blocks etc.
      });
      // If the message had content but all blocks were orphaned tool_results, skip it
      if (filtered.length === 0) {
        logger.info(`conversation-prune: dropped entirely-orphaned tool_result message`);
        continue;
      }
      result.push({ ...msg, content: filtered });
    } else {
      result.push(msg);
    }
  }
  return result;
}

/**
 * Truncate large tool_result content blocks in messages to save input tokens.
 * Only truncates blocks older than the last N messages (keeps recent results intact).
 */
function truncateToolResults(messages) {
  if (!Array.isArray(messages) || messages.length <= 4) return messages;

  // Keep the last 4 messages intact (current turn needs full context)
  const preserveCount = 4;
  const cutoff = messages.length - preserveCount;
  let truncated = 0;

  for (let i = 0; i < cutoff; i++) {
    const msg = messages[i];
    if (!Array.isArray(msg.content)) continue;

    for (let j = 0; j < msg.content.length; j++) {
      const block = msg.content[j];
      if (block.type === 'tool_result' && typeof block.content === 'string' && block.content.length > TOOL_RESULT_MAX_CHARS) {
        const originalLen = block.content.length;
        msg.content[j] = {
          ...block,
          content: block.content.slice(0, TOOL_RESULT_MAX_CHARS) +
            `\n\n[... truncated ${originalLen - TOOL_RESULT_MAX_CHARS} chars to save context budget ...]`,
        };
        truncated++;
      }
      // Also handle tool_result with nested content array (some SDK versions)
      if (block.type === 'tool_result' && Array.isArray(block.content)) {
        for (let k = 0; k < block.content.length; k++) {
          const inner = block.content[k];
          if (inner.type === 'text' && typeof inner.text === 'string' && inner.text.length > TOOL_RESULT_MAX_CHARS) {
            const originalLen = inner.text.length;
            block.content[k] = {
              ...inner,
              text: inner.text.slice(0, TOOL_RESULT_MAX_CHARS) +
                `\n\n[... truncated ${originalLen - TOOL_RESULT_MAX_CHARS} chars to save context budget ...]`,
            };
            truncated++;
          }
        }
      }
    }
  }

  if (truncated > 0) {
    logger.info(`tool-result-truncate: truncated ${truncated} large tool_result blocks (cap ${TOOL_RESULT_MAX_CHARS} chars)`);
  }
  return messages;
}

/**
 * Strip base64 images from older messages to save massive input tokens.
 * A single base64 image can be 100K+ characters (~25K tokens).
 * Only strips from messages older than the last preserveCount.
 */
function stripOldImages(messages) {
  if (!Array.isArray(messages) || messages.length <= 6) return messages;

  const preserveCount = 6; // keep recent images intact
  const cutoff = messages.length - preserveCount;
  let stripped = 0;

  for (let i = 0; i < cutoff; i++) {
    const msg = messages[i];
    if (!Array.isArray(msg.content)) continue;

    msg.content = msg.content.filter(block => {
      // Remove base64 image blocks
      if (block.type === 'image' && block.source?.type === 'base64') {
        stripped++;
        return false;
      }
      // Remove image_url blocks (OpenAI-style, sometimes forwarded)
      if (block.type === 'image_url') {
        stripped++;
        return false;
      }
      return true;
    });

    // If all content was images, replace with a text note
    if (msg.content.length === 0) {
      msg.content = [{ type: 'text', text: '[image removed to conserve context budget]' }];
    }
  }

  if (stripped > 0) {
    logger.info(`image-strip: removed ${stripped} base64 images from older messages`);
  }
  return messages;
}

// ── Dynamic tool filtering ────────────────────────────────────────────────
// Only send tools that are likely relevant. Saves ~20K input tokens per request.
// Core coding tools + Theia IDE tools are always included.
// Any tool the model has already used in the conversation is also kept.
const CORE_TOOL_NAMES = new Set([
  // ── Theia IDE workspace tools (ai-ide) ──
  'getWorkspaceDirectoryStructure', 'getFileContent', 'getWorkspaceFileList',
  'getFileDiagnostics', 'findFilesByPattern', 'getSkillFileContent',
  'searchInWorkspace',
  // Theia file changeset tools (ai-ide)
  'suggestFileContent', 'writeFileContent',
  'suggestFileReplacements', 'suggestFileReplacements_Simple',
  'writeFileReplacements', 'writeFileReplacements_Simple',
  'simpleSuggestFileReplacements', 'simpleWriteFileReplacements',
  'clearFileChanges', 'getProposedFileState',
  // Theia tasks & launch (ai-ide)
  'listTasks', 'runTask',
  'listLaunchConfigurations', 'runLaunchConfiguration', 'stopLaunchConfiguration',
  // Theia context tools (ai-ide)
  'context_ListChatContext', 'context_ResolveChatContext', 'context_addFile',
  // Theia app tester (ai-ide)
  'launchBrowser', 'closeBrowser', 'isBrowserRunning', 'queryDom',
  // Theia terminal & task context (ai-ide)
  'suggestTerminalCommand',
  'createTaskContext', 'getTaskContext', 'editTaskContext',
  'listTaskContexts', 'rewriteTaskContext',
  'todoWrite',
  // Theia shell execution (ai-terminal)
  'shellExecute',
  // Theia agent delegation (ai-chat)
  'delegateToAgent',
  // ── Legacy backend tools (voice/widget Alfred) ──
  'read_file', 'write_file', 'list_directory', 'delete_file', 'rename_file',
  'create_directory', 'search_files', 'find_file', 'get_file_info',
  // Terminal
  'run_terminal_command', 'terminal_session_status', 'terminal_history', 'terminal_reset',
  // Git
  'git_status', 'git_diff', 'git_log', 'git_branches', 'git_commit', 'git_revert',
  'git_init', 'smart_commit', 'amend_commit', 'da_git_status', 'da_git_log', 'da_git_diff',
  // Code intelligence
  'semantic_code_search', 'reindex_workspace', 'get_index_stats', 'toggle_auto_index',
  'code_review', 'dependency_audit',
  // Checkpoints
  'create_checkpoint', 'list_checkpoints', 'restore_checkpoint', 'project_snapshot',
  // Code execution
  'run_code', 'list_interpreter_sessions', 'kill_interpreter_session',
  // Web / docs
  'fetch_url', 'read_pdf', 'web_search',
  // Memory (small set, useful for context)
  'alfred_remember', 'alfred_recall', 'alfred_forget', 'alfred_memory_summary',
  'save_session_summary',
  // Meta (tool discovery — lets the model find other tools if needed)
  'search_tools', 'get_tool_doc',
  // ── Autopilot (Live Browser Agent) ──
  'autopilot_start', 'autopilot_action', 'autopilot_observe', 'autopilot_stop',
]);

/**
 * Filter tools to only include core coding set + any tools already used.
 * The model can use `search_tools` to discover and request other tools.
 * MCP tools (mcp_*) are always passed through.
 * Theia IDE tools (various naming patterns) are also kept.
 */
function filterTools(tools, messages) {
  const usedToolNames = new Set();
  if (Array.isArray(messages)) {
    for (const msg of messages) {
      if (msg.role === 'assistant' && Array.isArray(msg.content)) {
        for (const block of msg.content) {
          if (block.type === 'tool_use' && block.name) {
            usedToolNames.add(block.name);
          }
        }
      }
    }
  }

  const survivors = [];
  const dropped = [];

  for (const tool of tools) {
    const name = tool.name;
    let keep = false;

    if (CORE_TOOL_NAMES.has(name)) keep = true;
    else if (name.startsWith('mcp_')) keep = true;
    else if (usedToolNames.has(name)) keep = true;
    // Keep Theia IDE tools that use camelCase naming (e.g. getFileContent, suggestTerminalCommand)
    else if (/^(get|set|list|run|create|edit|delete|find|search|suggest|write|clear|read|open|close|launch|stop|save|load|show|toggle|rewrite|context_|todoWrite|delegateToAgent|shellExecute)/.test(name)) keep = true;
    // Keep tools from known Theia AI packages (ai-ide, ai-terminal, ai-chat, ai-workspace)
    else if (name.includes('workspace') || name.includes('file') || name.includes('terminal') || name.includes('task') || name.includes('browser') || name.includes('diagnostic')) keep = true;

    if (keep) {
      survivors.push(tool);
    } else {
      dropped.push(name);
    }
  }

  if (dropped.length > 0 && survivors.length < 5) {
    logger.warn(`tool-filter: aggressive — only ${survivors.length} survivors: [${survivors.map(t => t.name).join(', ')}] | sample-dropped: [${dropped.slice(0, 10).join(', ')}]`);
  }

  return survivors;
}

/**
 * Compress tool descriptions to save input tokens.
 * Removes examples, trims verbose phrasing, caps description length.
 * Average saving: ~40-60% of description token weight.
 */
const DESCRIPTION_MAX_CHARS = 300;
const PARAM_DESC_MAX_CHARS = 150;
function compressToolDescriptions(tools) {
  return tools.map(tool => {
    const compressed = { ...tool };
    // Compress top-level description
    if (compressed.description && compressed.description.length > DESCRIPTION_MAX_CHARS) {
      let desc = compressed.description;
      desc = desc.replace(/\n\s*Example[s]?:[\s\S]*?(?=\n[A-Z]|\n\n|$)/gi, '');
      desc = desc.replace(/\n\s*e\.g\.[\s\S]*?(?=\n[A-Z]|\n\n|$)/gi, '');
      desc = desc.replace(/\n\s*For example[\s\S]*?(?=\n[A-Z]|\n\n|$)/gi, '');
      desc = desc.replace(/\n\s*Note:[\s\S]*?(?=\n[A-Z]|\n\n|$)/gi, '');
      desc = desc.replace(/\n{2,}/g, '\n').replace(/[ \t]+/g, ' ').trim();
      if (desc.length > DESCRIPTION_MAX_CHARS) {
        desc = desc.slice(0, DESCRIPTION_MAX_CHARS - 3).replace(/\s+\S*$/, '') + '...';
      }
      compressed.description = desc;
    }
    // Compress parameter descriptions in input_schema
    if (compressed.input_schema?.properties) {
      const props = { ...compressed.input_schema.properties };
      for (const [key, val] of Object.entries(props)) {
        if (val.description && val.description.length > PARAM_DESC_MAX_CHARS) {
          let pd = val.description;
          pd = pd.replace(/\n\s*Example[s]?:[\s\S]*?(?=\n[A-Z]|\n\n|$)/gi, '');
          pd = pd.replace(/\n\s*e\.g\.[\s\S]*?(?=\n[A-Z]|\n\n|$)/gi, '');
          pd = pd.replace(/\n{2,}/g, '\n').replace(/[ \t]+/g, ' ').trim();
          if (pd.length > PARAM_DESC_MAX_CHARS) {
            pd = pd.slice(0, PARAM_DESC_MAX_CHARS - 3).replace(/\s+\S*$/, '') + '...';
          }
          props[key] = { ...val, description: pd };
        }
      }
      compressed.input_schema = { ...compressed.input_schema, properties: props };
    }
    return compressed;
  });
}

/**
 * Compress system prompt text to reduce input tokens.
 * Preserves semantic meaning while removing verbose formatting.
 */
function compressSystemPrompt(text) {
  if (!text || text.length < 500) return text; // skip short prompts
  let s = text;
  // Remove markdown decorators that add no semantic value
  s = s.replace(/^#{1,4}\s+/gm, '');             // heading markers
  s = s.replace(/\*{2}([^*]+)\*{2}/g, '$1');     // bold markers
  s = s.replace(/_{2}([^_]+)_{2}/g, '$1');        // underline markers
  // Collapse repeated blank lines to single blank line
  s = s.replace(/\n{3,}/g, '\n\n');
  // Compress multiple spaces/tabs
  s = s.replace(/[ \t]{2,}/g, ' ');
  // Strip trailing whitespace per line
  s = s.replace(/[ \t]+$/gm, '');
  // Remove "Note:" and "Important:" label prefixes (keep the content)
  s = s.replace(/^(Note|Important|Remember|Hint|Tip):\s*/gim, '');
  // Remove standalone separator lines (---, ===, ___)
  s = s.replace(/^[-=_]{3,}\s*$/gm, '');
  // Trim leading/trailing whitespace
  s = s.trim();
  return s;
}

function captureRawBody(req, _res, next) {
  // If express.json() already parsed the body, we can re-serialize
  if (req.body && typeof req.body === 'object') {
    // Delegate to async helper
    _captureRawBodyAsync(req).then(() => next()).catch(() => next());
    return;
  }
  next();
}

async function _captureRawBodyAsync(req) {
    // ── Custom AI instructions injection ─────────────────────────────────
    // If the user has a .gocodeme/ai-instructions.md file in their workspace,
    // prepend its content to the system prompt. This lets each project define
    // its own coding conventions, stack preferences, and persona tweaks.
    const daUsername = req.params.daUsername || req.params[0];
    // SECURITY: Validate daUsername format before using in file paths (VULN-12)
    if (daUsername && !/^[a-zA-Z0-9_-]+$/.test(daUsername)) return;
    if (daUsername && req.body.system !== undefined) {
      try {
        const instructionsPath = path.join(
          '/tmp', `gocodeme-workspace-${daUsername}`, '.gocodeme', 'ai-instructions.md'
        );
        if (fs.existsSync(instructionsPath)) {
          const custom = fs.readFileSync(instructionsPath, 'utf8').trim();
          if (custom.length > 0 && custom.length <= 8000) {
            const prefix = `<custom_project_instructions>\n${custom}\n</custom_project_instructions>\n\n`;
            if (typeof req.body.system === 'string') {
              req.body.system = prefix + req.body.system;
            } else if (Array.isArray(req.body.system)) {
              // Anthropic supports system as array of content blocks
              req.body.system.unshift({ type: 'text', text: prefix.trim() });
            }
          }
        }
      } catch (err) {
        // Non-fatal — if file can't be read, proceed without custom instructions
        logger.debug(`custom-instructions: ${err.message}`);
      }
    }

    // ── User identity injection ────────────────────────────────────────────
    // Inject the authenticated user's identity into the system prompt so all
    // agents (Alfred, Cipher, etc.) know who they're talking to and can make
    // authenticated API calls on their behalf via the middleware.
    if (daUsername && req.body.system !== undefined) {
      try {
        const redis = getRedis();
        // Resolve UUID proxy token → actual DA username (token format: UUID v4)
        let resolvedUser = daUsername;
        if (/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i.test(daUsername)) {
          const tokenOwner = await redis.get(`anthropic_proxy_token:${daUsername}`);
          if (tokenOwner) resolvedUser = tokenOwner;
        }
        const clientId = await resolveClientId(resolvedUser);
        const isCommander = clientId === '33';
        const identityBlock = [
          '<gocodeme_session>',
          `Authenticated user: ${resolvedUser}`,
          `Client ID: ${clientId || 'unknown'}`,
          isCommander ? 'Role: Commander Danny William Perez (full admin access, owner of GoSiteMe ecosystem)' : 'Role: Customer',
          'Session: active — user IS logged in, do NOT tell them they are a guest.',
          '',
          'CRITICAL: The user is logged in and fully authenticated. You MUST NOT say they are a guest or unauthenticated.',
          'You are running inside the GoCodeMe IDE. You have full tool access on behalf of this user.',
          '',
          'To access the user\'s account data (domains, hosting, billing, services, fleet, etc.):',
          '- Use the terminal tool to run:',
          '  curl -s -X POST https://gositeme.com/api/alfred-chat.php \\',
          '    -H "Content-Type: application/json" \\',
          '    -H "X-Internal-Secret: $INTERNAL_SECRET" \\',
          `    -d '{"action":"chat","message":"<describe what you need>","channel":"ide-agent","client_id":${clientId || 0},"relay_username":"${resolvedUser}"}'`,
          '- $INTERNAL_SECRET is available as an environment variable in the terminal.',
          '- Parse the JSON response to extract the "response" or "message" field.',
          '- Available env vars: GOCODEME_DA_USERNAME, GOCODEME_WHMCS_CLIENT_ID, GOCODEME_JWT_TOKEN, INTERNAL_SECRET',
          '</gocodeme_session>',
        ].join('\n');

        if (typeof req.body.system === 'string') {
          req.body.system = identityBlock + '\n\n' + req.body.system;
        } else if (Array.isArray(req.body.system)) {
          req.body.system.unshift({ type: 'text', text: identityBlock });
        }
      } catch (err) {
        logger.debug(`identity-inject: ${err.message}`);
      }
    }

    // ── Memory context injection ─────────────────────────────────────────
    // Fetch relevant memories from the MCP server's ELEPHANT engine and
    // append them to the system prompt. This gives Alfred recall of previous
    // conversations, user preferences, and project knowledge.
    if (daUsername && req.body.system !== undefined) {
      try {
        // Use the last user message as a context hint for semantic memory recall
        let hint = 'general conversation';
        const msgs = req.body.messages;
        if (Array.isArray(msgs)) {
          for (let i = msgs.length - 1; i >= 0; i--) {
            if (msgs[i].role === 'user') {
              const content = typeof msgs[i].content === 'string'
                ? msgs[i].content
                : (Array.isArray(msgs[i].content) ? msgs[i].content.map(b => b.text || '').join(' ') : '');
              if (content.length > 10) { hint = content.slice(0, 500); break; }
            }
          }
        }
        const memRes = await fetch(`http://127.0.0.1:3006/mcp/memory-context?daUsername=${encodeURIComponent(daUsername)}&hint=${encodeURIComponent(hint)}`);
        if (memRes.ok) {
          const { context: memCtx } = await memRes.json();
          if (memCtx && memCtx.length > 10) {
            const memBlock = `<alfred_memories>\n${memCtx.trim()}\n</alfred_memories>\n\n`;
            if (typeof req.body.system === 'string') {
              req.body.system = memBlock + req.body.system;
            } else if (Array.isArray(req.body.system)) {
              req.body.system.unshift({ type: 'text', text: memBlock.trim() });
            }
          }
        }
      } catch (err) {
        // Non-fatal — memories are optional context enhancement
        logger.debug(`memory-inject: ${err.message}`);
      }
    }
    // ── Extended thinking guard ──────────────────────────────────────────
    // Strip thinking budget and blocks unless explicitly allowed.
    // Extended thinking generates 10-30K output tokens of internal reasoning
    // at $15/MTok — massive cost with little user-visible benefit.
    if (!ALLOW_EXTENDED_THINKING && req.body.thinking) {
      delete req.body.thinking;
      logger.info(`thinking-guard: stripped extended thinking budget from request`);
    }

    // ── Dynamic tool filtering ───────────────────────────────────────────
    // Theia sends all 218 tools (~25K tokens) on every request.  Most are
    // irrelevant to the current task.  We keep only the core coding + memory
    // tools by default, saving ~78% of tool-definition input tokens.
    // Tools that the model has ALREADY used in the conversation are always kept.
    if (Array.isArray(req.body.tools) && req.body.tools.length > 0) {
      const original = req.body.tools.length;
      req.body.tools = filterTools(req.body.tools, req.body.messages);
      if (req.body.tools.length < original) {
        const survivors = req.body.tools.map(t => t.name);
        logger.info(`tool-filter: ${original} → ${req.body.tools.length} tools | survivors: ${JSON.stringify(survivors.slice(0, 15))}`);
      }
      req.body.tools = compressToolDescriptions(req.body.tools);
    }

    // ── Image stripping ──────────────────────────────────────────────────
    // Base64 images in older messages waste massive input tokens (100K+ chars each).
    if (Array.isArray(req.body.messages)) {
      req.body.messages = stripOldImages(req.body.messages);
    }
    // ── Tool result truncation ────────────────────────────────────────
    // Large file reads, terminal output, etc. waste input tokens on every turn.
    // Truncate older tool results while keeping recent ones intact.
    if (Array.isArray(req.body.messages)) {
      req.body.messages = truncateToolResults(req.body.messages);
    }

    // ── Conversation pruning ───────────────────────────────────────────
    // Trim message history if it would exceed Anthropic's 200K token limit.
    // Tool results (file contents, logs, etc.) accumulate fast in long chats.
    if (Array.isArray(req.body.messages)) {
      req.body.messages = pruneMessages(req.body.messages);

      // Safety net: estimate full request size (system + tools + messages)
      // and aggressively prune if the total would exceed 190K tokens.
      const fullBodyChars = JSON.stringify(req.body).length;
      const fullEstTokens = Math.ceil(fullBodyChars / 4);
      if (fullEstTokens > 140000 && req.body.messages.length > 3) {
        logger.warn(`full-body-prune: full request ~${fullEstTokens} tokens — emergency prune`);
        // Keep dropping oldest middle messages until under 120K total
        // We use a conservative target because chars/4 underestimates real token
        // counts by ~30% for structured JSON / tool-heavy payloads.
        while (req.body.messages.length > 3) {
          // Remove 2nd message (oldest after first)
          const dropped = req.body.messages.splice(1, 1)[0];
          // If we dropped an assistant tool_use, drop the following tool_result too
          if (dropped && dropped.role === 'assistant' && req.body.messages.length > 1 &&
              hasToolContent(dropped, 'tool_use') && hasToolContent(req.body.messages[1], 'tool_result')) {
            req.body.messages.splice(1, 1);
          }
          const newEst = Math.ceil(JSON.stringify(req.body).length / 4);
          if (newEst <= 120000) break;
        }
        // Final orphan cleanup
        req.body.messages = [req.body.messages[0], ...stripOrphanedToolResults(req.body.messages.slice(1))];
        logger.info(`full-body-prune: reduced to ${req.body.messages.length} msgs, ~${Math.ceil(JSON.stringify(req.body).length / 4)} est tokens (target ≤120K)`);
      }
    }

    // ── System prompt compression ────────────────────────────────────────
    // Theia's system prompt can be 5-15K tokens with verbose formatting,
    // repeated instructions, markdown decorators, etc. Compress it to save
    // input tokens on every request.
    if (req.body.system) {
      const originalLen = typeof req.body.system === 'string'
        ? req.body.system.length
        : JSON.stringify(req.body.system).length;

      if (typeof req.body.system === 'string') {
        req.body.system = compressSystemPrompt(req.body.system);
      } else if (Array.isArray(req.body.system)) {
        for (let i = 0; i < req.body.system.length; i++) {
          if (req.body.system[i].type === 'text' && req.body.system[i].text) {
            req.body.system[i] = {
              ...req.body.system[i],
              text: compressSystemPrompt(req.body.system[i].text),
            };
          }
        }
      }

      const newLen = typeof req.body.system === 'string'
        ? req.body.system.length
        : JSON.stringify(req.body.system).length;
      if (newLen < originalLen * 0.9) {
        logger.info(`system-compress: ${originalLen} → ${newLen} chars (saved ${Math.round((1 - newLen / originalLen) * 100)}%)`);
      }
    }

    // ── Prompt caching ────────────────────────────────────────────────────
    // Mark the system prompt and tool definitions for Anthropic's prompt
    // caching.  On the 2nd+ call in a session, cached content is charged at
    // 10% of the normal input rate — saving ~90% on the biggest cost driver.
    //
    // Strategy: put cache_control on the LAST block of the system prompt
    // (Anthropic caches everything UP TO and including the marked block).
    // Also mark the tools array so tool definitions are cached too.
    if (req.body.system) {
      if (Array.isArray(req.body.system)) {
        // System is array of content blocks — mark the last one
        const lastIdx = req.body.system.length - 1;
        if (lastIdx >= 0) {
          req.body.system[lastIdx] = {
            ...req.body.system[lastIdx],
            cache_control: { type: 'ephemeral' },
          };
        }
      } else if (typeof req.body.system === 'string') {
        // Convert string system prompt to array format with cache marker
        req.body.system = [{
          type: 'text',
          text: req.body.system,
          cache_control: { type: 'ephemeral' },
        }];
      }
    }
    // Mark tools for caching (if present)
    if (Array.isArray(req.body.tools) && req.body.tools.length > 0) {
      const lastTool = req.body.tools.length - 1;
      req.body.tools[lastTool] = {
        ...req.body.tools[lastTool],
        cache_control: { type: 'ephemeral' },
      };
    }

    // 3rd cache breakpoint: mark the first user message (task context).
    // This message survives all pruning — caching it saves re-tokenizing
    // the original task description on every turn.
    // Anthropic allows up to 4 cache breakpoints.
    if (Array.isArray(req.body.messages) && req.body.messages.length >= 2) {
      const first = req.body.messages[0];
      if (first.role === 'user') {
        if (typeof first.content === 'string') {
          req.body.messages[0] = {
            role: 'user',
            content: [{
              type: 'text',
              text: first.content,
              cache_control: { type: 'ephemeral' },
            }],
          };
        } else if (Array.isArray(first.content) && first.content.length > 0) {
          const lastBlock = first.content.length - 1;
          first.content[lastBlock] = {
            ...first.content[lastBlock],
            cache_control: { type: 'ephemeral' },
          };
        }
      }
    }

    // ── Sanitize empty text blocks ──────────────────────────────────────
    // Anthropic rejects: "messages: text content blocks must be non-empty"
    // Empty blocks can arise from format translation, orphan stripping, or
    // Theia sending a context file that was empty.
    if (Array.isArray(req.body.messages)) {
      for (const msg of req.body.messages) {
        if (Array.isArray(msg.content)) {
          // Remove empty text blocks
          msg.content = msg.content.filter(block => {
            if (block.type === 'text' && (!block.text || block.text.trim() === '')) {
              return false;
            }
            return true;
          });
          // If all content blocks were removed, add a placeholder
          if (msg.content.length === 0) {
            msg.content = [{ type: 'text', text: '...' }];
          }
        } else if (typeof msg.content === 'string' && msg.content.trim() === '') {
          msg.content = '...';
        }
      }
    }

    // ── Calculate context usage for client awareness ─────────────────────
    // Store on req so response handlers can include it in headers/metadata
    if (Array.isArray(req.body.messages)) {
      req._contextUsage = getContextUsage(req.body.messages);

      // ── Auto-compact at 95% — prevent context overflow failures ────────
      // When context is critically full, automatically compact the conversation
      // in-place before forwarding to the provider. Uses free extraction
      // (no extra API call) to keep latency low.
      if (req._contextUsage.percent >= 95 && req.body.messages.length >= 6) {
        const KEEP_RECENT = 6;
        const first = req.body.messages[0];
        const toSummarize = req.body.messages.slice(1, -KEEP_RECENT);
        const recent = req.body.messages.slice(-KEEP_RECENT);

        if (toSummarize.length >= 2) {
          const freeSummary = summarizeDroppedMessages(toSummarize, toSummarize.length);
          const summaryText = `[Auto-Compacted — ${toSummarize.length} messages summarized at 95% context]\n\n${freeSummary}`;
          req.body.messages = stripOrphanedToolResults([
            first,
            { role: 'user', content: summaryText },
            { role: 'assistant', content: 'Understood. I have the full context from the auto-compacted summary and will continue seamlessly.' },
            ...recent,
          ]);
          req._contextUsage = getContextUsage(req.body.messages);
          req._autoCompacted = true;
          req._autoCompactSaved = estimateTokens(toSummarize) - estimateTokens(req.body.messages.slice(1, 3));
          logger.info(`auto-compact: ${toSummarize.length + KEEP_RECENT + 1} → ${req.body.messages.length} msgs, context now ${req._contextUsage.percent}%`);
        }
      }
    }

    req._rawBody = JSON.stringify(req.body);
}

// ── POST /compact — Proactive conversation compacting ────────────────────────
// Client sends full conversation, server summarizes old messages via a cheap
// model, returns compacted message list.  The user can keep chatting in the
// same session without losing important context.
router.post('/compact', express.json({ limit: '4mb' }), async (req, res) => {
  const { messages } = req.body || {};
  if (!Array.isArray(messages) || messages.length < 4) {
    return res.status(400).json({ ok: false, error: 'Need at least 4 messages to compact' });
  }

  // Auth — require valid session token
  const authHeader = req.headers['authorization'] || '';
  const sessionToken = authHeader.replace(/^Bearer\s+/i, '').trim();
  let daUsername = null;
  if (sessionToken) {
    try {
      const redis = getRedis();
      daUsername = await redis.get(`anthropic_proxy_token:${sessionToken}`);
    } catch (_) {}
  }
  if (!daUsername) {
    return res.status(401).json({ ok: false, error: 'Authentication required' });
  }

  const whmcsClientId = await resolveClientId(daUsername);
  if (!whmcsClientId) {
    return res.status(403).json({ ok: false, error: 'Unknown user' });
  }

  logger.info(`compact: client ${whmcsClientId} (${daUsername}) requested compaction — ${messages.length} msgs`);

  // Split: keep first message (task context) + last N messages intact
  const KEEP_RECENT = 6;
  const first = messages[0];
  const toSummarize = messages.slice(1, messages.length > KEEP_RECENT ? -KEEP_RECENT : undefined);
  const recent = messages.length > KEEP_RECENT ? messages.slice(-KEEP_RECENT) : [];

  if (toSummarize.length < 2) {
    return res.json({
      ok: true, compacted: false,
      reason: 'Conversation too short to compact.',
      messages, context: getContextUsage(messages),
    });
  }

  // Free extraction summary (instant, no API call)
  const freeSummary = summarizeDroppedMessages(toSummarize, toSummarize.length);

  // AI-powered rich summary via Haiku (cheap & fast)
  let aiSummary = null;
  try {
    const excerpts = toSummarize.map(m =>
      `[${m.role}]: ${typeof m.content === 'string' ? m.content.slice(0, 300) : JSON.stringify(m.content).slice(0, 300)}`
    ).join('\n\n');

    const summaryBody = JSON.stringify({
      model: 'claude-haiku-4-5',
      max_tokens: 2048,
      messages: [{
        role: 'user',
        content: `Summarize this coding conversation (${toSummarize.length} messages) for context compaction.\n\nStructured extraction:\n${freeSummary}\n\nMessages:\n${excerpts}\n\nProduce a concise summary (max 800 words) capturing: files modified/created, key decisions, current work state, unresolved issues. Be specific with file names and technical details.`,
      }],
    });

    aiSummary = await new Promise((resolve, reject) => {
      const summaryReq = https.request('https://api.anthropic.com/v1/messages', {
        method: 'POST',
        headers: {
          'content-type': 'application/json',
          'x-api-key': ANTHROPIC_API_KEY,
          'anthropic-version': '2023-06-01',
          'content-length': Buffer.byteLength(summaryBody),
        },
      }, (proxyRes) => {
        const chunks = [];
        proxyRes.on('data', c => chunks.push(c));
        proxyRes.on('end', () => {
          try {
            const data = JSON.parse(Buffer.concat(chunks).toString('utf8'));
            resolve(data.content?.[0]?.text || null);
          } catch (e) { reject(e); }
        });
        proxyRes.on('error', reject);
      });
      summaryReq.on('error', reject);
      summaryReq.setTimeout(15000, () => { summaryReq.destroy(); reject(new Error('timeout')); });
      summaryReq.write(summaryBody);
      summaryReq.end();
    });

    if (aiSummary) {
      const estIn = Math.ceil(summaryBody.length / 4);
      const estOut = Math.ceil(aiSummary.length / 4);
      tc.addUsage(whmcsClientId, estIn, estOut, 0.1).catch(() => {});
      const cost = calculateCost('claude-haiku-4-5', estIn, estOut, 0, 0);
      budget.recordGlobalSpend(cost.totalCost).catch(() => {});
      budget.recordUserDailySpend(whmcsClientId, cost.totalCost).catch(() => {});
      budget.deductCredit(whmcsClientId, cost.totalCost).catch(() => {});
      anomaly.trackRequest(whmcsClientId, 'claude-haiku-4-5', cost.totalCost).catch(() => {});
      budget.checkAutoReplenish(whmcsClientId).catch(() => {});
      logger.info(`compact: AI summary — ${estIn}in/${estOut}out, $${cost.totalCost.toFixed(6)}`);
    }
  } catch (err) {
    logger.warn(`compact: AI summary failed (${err.message}), using extraction only`);
  }

  // Build compacted conversation
  const summaryText = aiSummary
    ? `[Conversation Compacted — ${toSummarize.length} messages summarized]\n\n${aiSummary}\n\n---\nExtraction:\n${freeSummary}`
    : `[Conversation Compacted — ${toSummarize.length} messages summarized]\n\n${freeSummary}`;

  const compactedMessages = [
    first,
    { role: 'user', content: summaryText },
    { role: 'assistant', content: 'Understood. I have the full context from the compacted summary and will continue seamlessly.' },
    ...recent,
  ];

  const cleaned = stripOrphanedToolResults(compactedMessages);
  const newCtx = getContextUsage(cleaned);
  const oldCtx = getContextUsage(messages);

  logger.info(`compact: ${messages.length} → ${cleaned.length} msgs, ${oldCtx.percent}% → ${newCtx.percent}%`);

  // ── Track compact savings in Redis ─────────────────────────────────────
  const savedTokens = oldCtx.estimatedTokens - newCtx.estimatedTokens;
  if (savedTokens > 0) {
    try {
      const redis = getRedis();
      await redis.incrby(`compact:saved_tokens:${whmcsClientId}`, savedTokens);
      await redis.incr(`compact:count:${whmcsClientId}`);
      await redis.expire(`compact:saved_tokens:${whmcsClientId}`, 35 * 86400);
      await redis.expire(`compact:count:${whmcsClientId}`, 35 * 86400);
    } catch (_) {}
  }

  res.json({
    ok: true, compacted: true,
    originalCount: messages.length, newCount: cleaned.length,
    summarized: toSummarize.length,
    context: newCtx, messages: cleaned,
    method: aiSummary ? 'ai' : 'extraction',
  });

  // ── Persist compacted state so it survives IDE refresh ─────────────────
  try {
    const redis = getRedis();
    const persistKey = `compact:session:${daUsername}`;
    await redis.set(persistKey, JSON.stringify({
      messages: cleaned,
      compactedAt: Date.now(),
      originalCount: messages.length,
      method: aiSummary ? 'ai' : 'extraction',
    }), 'EX', 7200); // 2 hour TTL
  } catch (_) {}
});

// ── GET /compact/session — Retrieve persisted compact state ──────────────────
router.get('/compact/session', async (req, res) => {
  const authHeader = req.headers['authorization'] || '';
  const sessionToken = authHeader.replace(/^Bearer\s+/i, '').trim();
  let daUsername = null;
  if (sessionToken) {
    try {
      const redis = getRedis();
      daUsername = await redis.get(`anthropic_proxy_token:${sessionToken}`);
    } catch (_) {}
  }
  if (!daUsername) {
    return res.status(401).json({ ok: false, error: 'Authentication required' });
  }
  try {
    const redis = getRedis();
    const raw = await redis.get(`compact:session:${daUsername}`);
    if (!raw) return res.json({ ok: true, hasCompact: false });
    const data = JSON.parse(raw);
    res.json({ ok: true, hasCompact: true, ...data });
  } catch (err) {
    res.status(500).json({ ok: false, error: 'Failed to retrieve compact state' });
  }
});

// ── POST /context-usage — Check how full the context is ──────────────────────
router.post('/context-usage', express.json({ limit: '4mb' }), (req, res) => {
  const { messages } = req.body || {};
  if (!Array.isArray(messages)) {
    return res.status(400).json({ ok: false, error: 'messages array required' });
  }
  res.json({ ok: true, ...getContextUsage(messages) });
});

// ── Main proxy handler ───────────────────────────────────────────────────────
// Matches:  /api/anthropic-proxy/:daUsername/v1/messages
//           /api/anthropic-proxy/:daUsername/v1/...  (any Anthropic endpoint)
// SECURITY: :daUsername can be either a DA username (legacy) or a per-session
// proxy token (UUID). Tokens are looked up in Redis to resolve the real
// DA username, preventing cross-tenant token theft from shared terminals.
router.all('/*', captureRawBody, async (req, res) => {
  let daUsername = req.params.daUsername;

  // ── SECURITY: Validate daUsername format to prevent path traversal ─────
  // Must be alphanumeric/underscore/hyphen (DA usernames) or a UUID (proxy token)
  if (!daUsername || !/^[a-zA-Z0-9_-]+$/.test(daUsername)) {
    return res.status(400).json({
      type: 'error',
      error: { type: 'invalid_request_error', message: 'Invalid username format' },
    });
  }

  // ── Check if :daUsername is actually a per-session proxy token ──────────
  // Proxy tokens are UUIDs stored in Redis as anthropic_proxy_token:<token>
  if (daUsername && /^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i.test(daUsername)) {
    try {
      const redis = getRedis();
      const resolved = await redis.get(`anthropic_proxy_token:${daUsername}`);
      if (resolved) {
        daUsername = resolved;
      } else {
        logger.warn(`anthropic-proxy: expired or invalid proxy token "${daUsername.slice(0,8)}..."`);
        return res.status(403).json({
          type: 'error',
          error: { type: 'authentication_error', message: 'Session expired — please restart IDE' },
        });
      }
    } catch (err) {
      logger.error(`anthropic-proxy: Redis lookup failed for proxy token: ${err.message}`);
      // Fall through to legacy username lookup
    }
  }

  // ── 1. Resolve customer ────────────────────────────────────────────────────
  const whmcsClientId = await resolveClientId(daUsername);
  if (!whmcsClientId) {
    logger.warn(`anthropic-proxy: unknown DA user "${daUsername}"`);
    return res.status(403).json({
      type: 'error',
      error: { type: 'authentication_error', message: 'Unknown user' },
    });
  }

  // ── Track activity for idle session reaper ────────────────────────────────
  try {
    const redis = getRedis();
    redis.set(`activity:${daUsername}`, Date.now().toString(), 'EX', 7200).catch(() => {});
    // Track auto-compact savings if applicable
    if (req._autoCompacted && req._autoCompactSaved > 0) {
      redis.incrby(`compact:saved_tokens:${whmcsClientId}`, req._autoCompactSaved).catch(() => {});
      redis.incr(`compact:count:${whmcsClientId}`).catch(() => {});
      redis.expire(`compact:saved_tokens:${whmcsClientId}`, 35 * 86400).catch(() => {});
      redis.expire(`compact:count:${whmcsClientId}`, 35 * 86400).catch(() => {});
    }
  } catch (_) {}

  // ── 1b. Account freeze check ─────────────────────────────────────────────
  if (!budget.isAdminBypassClient(whmcsClientId)) {
    const frozen = await budget.isAccountFrozen(whmcsClientId);
    if (frozen) {
      logger.warn(`anthropic-proxy: client ${whmcsClientId} FROZEN — blocking request`);
      return res.status(403).json({
        type: 'error',
        error: { type: 'permission_error', message: 'Account suspended. Contact support for assistance.' },
      });
    }
  }

  // ── 1c. Per-user rate limiter (requests per minute) ──────────────────────
  if (!budget.isAdminBypassClient(whmcsClientId)) {
    const redis = getRedis();
    const rpmKey = `rpm:${whmcsClientId}`;
    const rpmCount = await redis.incr(rpmKey);
    if (rpmCount === 1) await redis.expire(rpmKey, 60);
    const rpmLimit = parseInt(process.env.USER_RPM_LIMIT, 10) || 60;
    if (rpmCount > rpmLimit) {
      logger.warn(`anthropic-proxy: client ${whmcsClientId} RPM exceeded — ${rpmCount}/${rpmLimit}`);
      return res.status(429).json({
        type: 'error',
        error: { type: 'rate_limit_error', message: `Rate limit exceeded (${rpmLimit} requests/minute). Please slow down.` },
        meta: { kind: 'rpm_limit', current: rpmCount, limit: rpmLimit, retryAfter: 60 },
      });
    }
  }

  // ── 2a. Global circuit breaker (monthly USD) ─────────────────────────────
  // Admin bypass: owner can still use the system even when circuit breaker trips
  if (!budget.isAdminBypassClient(whmcsClientId) && await budget.isGlobalTripped()) {
    logger.warn(`anthropic-proxy: GLOBAL CIRCUIT BREAKER — blocking all requests`);
    return res.status(503).json({
      type: 'error',
      error: { type: 'overloaded_error', message: 'Service temporarily paused due to spend cap. Contact support.' },
    });
  }

  // ── 2b. Global daily USD cap ────────────────────────────────────────────
  if (!budget.isAdminBypassClient(whmcsClientId)) {
    const globalDailyCheck = await budget.checkGlobalDailyUsd();
    if (!globalDailyCheck.allowed) {
      logger.warn(`anthropic-proxy: GLOBAL DAILY USD CAP — $${globalDailyCheck.spent.toFixed(2)} / $${globalDailyCheck.cap}`);
      return res.status(503).json({
        type: 'error',
        error: { type: 'overloaded_error', message: 'AI service daily budget reached. Service resumes at midnight UTC. Contact support if urgent.' },
      });
    }
  }

  // ── 2c. Per-user daily USD cap ──────────────────────────────────────────
  const userDailyUsdCheck = await budget.checkUserDailyUsd(whmcsClientId);
  if (!userDailyUsdCheck.allowed) {
    logger.info(`anthropic-proxy: client ${whmcsClientId} daily USD cap — $${userDailyUsdCheck.spent.toFixed(2)} / $${userDailyUsdCheck.cap}`);
    return res.status(429).json(await rateLimitPayload(whmcsClientId,
      `Daily AI budget reached ($${userDailyUsdCheck.cap}/day).`,
      {
        kind: 'daily_usd_cap',
        spent_usd: Number(userDailyUsdCheck.spent.toFixed(2)),
        cap_usd: userDailyUsdCheck.cap,
      }
    ));
  }

  // ── 2d. Per-request size guard ────────────────────────────────────────────
  const reqBodyForCheck = req._rawBody || JSON.stringify(req.body) || '';
  const sizeCheck = budget.checkRequestSize(reqBodyForCheck);
  if (!sizeCheck.allowed && !budget.isAdminBypassClient(whmcsClientId)) {
    logger.warn(`anthropic-proxy: client ${whmcsClientId} request too large — ~${sizeCheck.estimated} tokens (cap ${sizeCheck.cap})`);
    return res.status(413).json({
      type: 'error',
      error: { type: 'invalid_request_error', message: `Request too large (~${sizeCheck.estimated.toLocaleString()} tokens estimated). Maximum is ${sizeCheck.cap.toLocaleString()} tokens per request. Start a new conversation to continue.` },
    });
  }

  // ── 2d½. Prepaid credit balance check ────────────────────────────────────
  const creditCheck = await budget.checkCreditBalance(whmcsClientId);
  if (!creditCheck.allowed && creditCheck.required) {
    logger.info(`anthropic-proxy: client ${whmcsClientId} BLOCKED — no credits (balance: $${creditCheck.balance})`);
    return res.status(402).json({
      type: 'error',
      error: {
        type: 'payment_required',
        message: `Insufficient credits (balance: $${creditCheck.balance.toFixed(2)}). Please add credits to continue using AI features.`,
      },
      meta: { kind: 'credit_balance', balance: creditCheck.balance },
    });
  }

  // ── 2e. Monthly token allowance — output tokens only ────────────────────
  const allowance = await tc.checkAllowance(whmcsClientId);
  if (!allowance.allowed) {
    if (allowance.reason === 'free_plan_limit_reached') {
      logger.info(`anthropic-proxy: client ${whmcsClientId} FREE plan limit — upgrade required`);
      return res.status(429).json(await rateLimitPayload(whmcsClientId,
        'Free plan token limit reached.',
        { kind: 'monthly_token_cap_free' }
      ));
    }
    if (allowance.reason === 'overage_cap_reached') {
      logger.info(`anthropic-proxy: client ${whmcsClientId} overage HARD CAP at 200% — blocked`);
      return res.status(429).json(await rateLimitPayload(whmcsClientId,
        'Monthly token hard cap reached (including overage).',
        { kind: 'monthly_token_cap_hard' }
      ));
    }
    logger.info(`anthropic-proxy: client ${whmcsClientId} BLOCKED at ${allowance.usage.percentUsed}%`);
    return res.status(429).json(await rateLimitPayload(whmcsClientId,
      'Monthly token limit reached.',
      { kind: 'monthly_token_cap' }
    ));
  }
  // Log overage warning
  if (allowance.overage) {
    logger.info(`anthropic-proxy: client ${whmcsClientId} IN OVERAGE — ${allowance.overageTokens} tokens over plan (billed at $2/100K)`);
  }

  // ── 2f. Daily token cap ─────────────────────────────────────────────────
  const dailyCheck = await budget.checkDailyCap(whmcsClientId, allowance.usage.limit);
  if (!dailyCheck.allowed) {
    logger.info(`anthropic-proxy: client ${whmcsClientId} daily cap hit — ${dailyCheck.used} tokens today`);
    return res.status(429).json(await rateLimitPayload(whmcsClientId,
      `Daily token limit reached (${dailyCheck.cap.toLocaleString()} tokens/day).`,
      {
        kind: 'daily_token_cap',
        used_tokens: dailyCheck.used,
        cap_tokens: dailyCheck.cap,
      }
    ));
  }

  // ── 2f¼. Team budget cap enforcement ──────────────────────────────────
  const teamBudget = await getTeamBudgetCheck(whmcsClientId);
  if (teamBudget.blocked) {
    logger.info(`anthropic-proxy: client ${whmcsClientId} blocked by team budget (${teamBudget.reason})`);
    return res.status(429).json(await rateLimitPayload(
      whmcsClientId,
      'Team budget limit reached.',
      {
        kind: 'team_budget_cap',
        team_id: teamBudget.teamId,
        reason: teamBudget.reason,
        usage: teamBudget.usage,
        budget: teamBudget.budget,
      }
    ));
  }

  // ── 2f½. Request deduplication ────────────────────────────────────────────
  // If Theia retries the exact same request within 30s (timeout/reconnect),
  // serve the cached response at zero API cost.
  const isStream = req.body?.stream === true;
  const dedupeKey = getDedupeKey(whmcsClientId, req.body?.messages);
  if (!isStream) {
    const cached = getDedupeCache(dedupeKey);
    if (cached) {
      logger.info(`dedup-cache: HIT for client ${whmcsClientId} — serving cached response (saved full API call)`);
      res.status(200).setHeader('content-type', 'application/json');
      return res.end(cached);
    }
  }

  // ── 2g. Model routing — pick cheapest competent model ──────────────────
  // Like Cursor's "Auto" mode: routes simple tasks to cheap Together.ai models,
  // complex tasks (tool use, long sessions) stay on Sonnet.
  const modelHeader = req.headers['x-gocodeme-model'] || null;
  let routingResult;
  try {
    routingResult = await routeRequest(whmcsClientId, req.body, modelHeader);
  } catch (routeErr) {
    logger.error(`model-router: ${routeErr.message} — falling back to Sonnet`);
    routingResult = {
      model: { provider: 'anthropic', modelId: 'claude-sonnet-4-6', displayName: 'Claude Sonnet 4.6' },
      mode: 'auto',
      reason: 'fallback:error',
    };
  }
  let routedModel = routingResult.model;
  const spendMode = await getSpendMode(whmcsClientId);
  const spendPolicy = enforceModelForSpendMode(routedModel, spendMode, getModelByKey);
  if (spendPolicy.downgraded) {
    logger.info(`spend-mode: client ${whmcsClientId} (${spendPolicy.mode}) downgraded model ${routedModel.modelId} → ${spendPolicy.model.modelId}`);
    routedModel = spendPolicy.model;
  }

  // ── 2g½. Margin guardrails ─────────────────────────────────────────────
  // Admin bypass: owner keeps full model access regardless of overage/margin
  if (!budget.isAdminBypassClient(whmcsClientId)) {
    const enforceOveragePremiumDowngrade = String(process.env.GUARDRAIL_DOWNGRADE_PREMIUM_OVERAGE || 'true') === 'true';
    const maxOutputPer1M = parseFloat(process.env.MARGIN_GUARDRAIL_MAX_OUTPUT_PER_1M || '25');

    if (enforceOveragePremiumDowngrade && allowance.overage && routedModel.tier === 'premium') {
      const fallback = getModelByKey('claude-sonnet-4-6') || routedModel;
      logger.info(`margin-guardrail: overage client ${whmcsClientId} downgraded premium model ${routedModel.modelId} -> ${fallback.modelId}`);
      routedModel = fallback;
    }

    if (Number.isFinite(maxOutputPer1M) && routedModel.outputPer1M > maxOutputPer1M && spendMode !== 'power') {
      const fallback = getModelByKey('claude-haiku-4-5') || getModelByKey('qwen3-coder') || routedModel;
      logger.info(`margin-guardrail: client ${whmcsClientId} model ${routedModel.modelId} exceeds output cap ${maxOutputPer1M}/1M in ${spendMode} mode -> ${fallback.modelId}`);
      routedModel = fallback;
    }
  }

  const routedProvider = getProvider(routedModel);
  logger.info(`model-router: client ${whmcsClientId} → ${routedModel.displayName} (${routingResult.reason})`);

  // ── 2h. High-multiplier concurrency guard ─────────────────────────────────
  // Prevents cascade failures when many users hit high-multiplier models (30x, 600x)
  // simultaneously — this is what crashed Anthropic for 6 hours.
  // Admin bypass: owner (client_id 33) is exempt — unlimited concurrency at any multiplier.
  const effectiveMultiplierForGuard = await getTokenMultiplierAsync(routedModel.modelId);
  let highMultSlotAcquired = false;
  if (effectiveMultiplierForGuard >= HIGH_MULT_THRESHOLD && !budget.isAdminBypassClient(whmcsClientId)) {
    highMultSlotAcquired = await acquireHighMultSlot(routedModel.modelId);
    if (!highMultSlotAcquired) {
      logger.warn(`concurrency-guard: client ${whmcsClientId} BLOCKED — too many concurrent ${effectiveMultiplierForGuard}x requests for ${routedModel.modelId}`);
      return res.status(429).json({
        type: 'error',
        error: { type: 'rate_limit_error', message: `This high-performance model (${effectiveMultiplierForGuard}x) is at capacity. Please wait a moment or switch to a standard model.` },
        meta: { kind: 'high_multiplier_concurrency', model: routedModel.modelId, multiplier: effectiveMultiplierForGuard },
      });
    }
    // Auto-release slot when response finishes (regardless of success/failure)
    res.on('finish', () => releaseHighMultSlot(routedModel.modelId).catch(() => {}));
    res.on('close',  () => releaseHighMultSlot(routedModel.modelId).catch(() => {}));
  }

  // ── 3. Clamp max_tokens (tiered by task complexity) ─────────────────────
  // Output is the most expensive dimension ($15/MTok Sonnet, $1.20/MTok Qwen3).
  // Short questions don't need 16K output. Tier the cap to save output tokens.
  //   simple   → 4096  (question, explanation, short snippet)
  //   moderate → 8192  (medium edit, multi-step answer)
  //   complex  → 16384 (full implementation, tool-heavy)
  {
    const complexity = classifyRequest(req.body);
    const TIERED_CAPS = { simple: 4096, moderate: 8192, complex: 16384 };
    const tierCap = TIERED_CAPS[complexity] || MAX_OUTPUT_TOKENS;

    const remaining = allowance.usage.limit - allowance.usage.used;
    let effectiveMax = Math.min(tierCap, MAX_OUTPUT_TOKENS); // tier cap, bounded by hard cap

    // If user has very few tokens left, clamp to what they can actually use
    if (remaining > 0 && remaining < effectiveMax) {
      effectiveMax = Math.max(remaining, 1024); // never go below 1024
    }

    const requested = req.body?.max_tokens;
    if (!requested || requested > effectiveMax) {
      req.body.max_tokens = effectiveMax;
      // Re-serialize since we modified the body after _captureRawBodyAsync
      req._rawBody = JSON.stringify(req.body);
      if (requested && requested > effectiveMax) {
        logger.info(`anthropic-proxy: clamped max_tokens ${requested} → ${effectiveMax} for client ${whmcsClientId}`);
      }
    }
  }

  // ── 4. Build upstream request ──────────────────────────────────────────────

  // ── Provider circuit breaker check ─────────────────────────────────────
  // If the routed provider is tripped, re-route to Anthropic Sonnet as safe fallback
  const providerName = routedProvider.name || 'Anthropic';
  if (!isProviderAvailable(providerName)) {
    logger.warn(`circuit-breaker: ${providerName} is OPEN — starting fallback cascade`);
    // Use the full cascade, skipping the failed provider
    return fallbackToSonnet(req, res, whmcsClientId, daUsername, isStream, dedupeKey, [providerName]);
  }

  // ── Provider branching ─────────────────────────────────────────────────
  // Anthropic → native format (existing flow)
  // OpenAI-format providers (Together.ai, OpenAI) → translate, send, translate back

  // ── Context usage header — powers IDE "Compact Conversation" button ────
  // Set early so it goes out with both streaming and JSON responses.
  if (req._contextUsage) {
    res.setHeader('x-context-usage', JSON.stringify(req._contextUsage));
  }

  // ── Cross-instance unified memory ─────────────────────────────────────────
  // Inject context from Widget/Voice/Phone Alfred so IDE Alfred knows
  // what the user has been doing across all channels.
  if (whmcsClientId && req.body.system !== undefined) {
    try {
      const crossCtx = await alfredMemory.getCrossContext(whmcsClientId, 'ide');
      if (crossCtx) {
        if (typeof req.body.system === 'string') {
          req.body.system = crossCtx + req.body.system;
        } else if (Array.isArray(req.body.system)) {
          req.body.system.unshift({ type: 'text', text: crossCtx.trim() });
        }
      }
    } catch (err) {
      logger.debug(`cross-ctx-inject: ${err.message}`);
    }
  }

  if (routedProvider.format === 'openai') {
    // ── OPENAI-FORMAT PATH (Together.ai, OpenAI, etc.) ──────────────────
    forwardToOpenAIFormat(req, res, whmcsClientId, daUsername, routedModel, routedProvider, isStream, dedupeKey);
  } else {
    // ── ANTHROPIC PATH (existing flow, unchanged) ───────────────────────
    // Override model in request body to the routed model
    req.body.model = routedModel.modelId;
    req._rawBody = JSON.stringify(req.body);

    const upstreamPath = '/' + (req.params[0] || '');
    const upstreamUrl = new URL(upstreamPath, ANTHROPIC_API_HOST);
    upstreamUrl.search = new URL(req.originalUrl, 'http://localhost').search;

    const requestModel = routedModel.modelId;
    const reqBody = req._rawBody || '';

    // Forward headers, replacing auth with our real API key
    const fwdHeaders = {
      'content-type':      'application/json',
      'x-api-key':         ANTHROPIC_API_KEY,
      'anthropic-version': req.headers['anthropic-version'] || '2023-06-01',
    };
    // Enable prompt caching — reduces repeat input cost by ~90%
    const betaFeatures = new Set(['prompt-caching-2024-07-31']);
    if (req.headers['anthropic-beta']) {
      for (const feat of req.headers['anthropic-beta'].split(',')) {
        const trimmed = feat.trim();
        if (!ALLOW_EXTENDED_THINKING && trimmed.startsWith('extended-thinking')) continue;
        betaFeatures.add(trimmed);
      }
    }
    fwdHeaders['anthropic-beta'] = [...betaFeatures].join(',');
    if (reqBody) {
      fwdHeaders['content-length'] = Buffer.byteLength(reqBody);
    }

    // ── Forward to Anthropic ─────────────────────────────────────────────
    const transport = upstreamUrl.protocol === 'https:' ? https : http;
    const proxyReq = transport.request(
      upstreamUrl.href,
      { method: req.method, headers: fwdHeaders },
      (proxyRes) => {
        if (proxyRes.statusCode >= 500 || proxyRes.statusCode === 401) {
          recordProviderFailure('Anthropic');
          proxyRes.resume();
          if (!res.headersSent) {
            logger.warn(`anthropic-proxy: upstream returned ${proxyRes.statusCode} — starting fallback cascade`);
            fallbackToSonnet(req, res, whmcsClientId, daUsername, isStream, dedupeKey, ['Anthropic']);
          }
          return;
        }
        if (proxyRes.statusCode >= 400) {
          recordProviderFailure('Anthropic');
        } else {
          recordProviderSuccess('Anthropic');
        }
        if (isStream) {
          handleStreamResponse(proxyRes, res, whmcsClientId, daUsername, requestModel);
        } else {
          handleJsonResponse(proxyRes, res, whmcsClientId, daUsername, requestModel, dedupeKey, req);
        }
      }
    );

    proxyReq.on('error', (err) => {
      recordProviderFailure('Anthropic');
      logger.error(`anthropic-proxy: upstream error: ${err.message} — starting fallback cascade`);
      if (!res.headersSent) {
        fallbackToSonnet(req, res, whmcsClientId, daUsername, isStream, dedupeKey, ['Anthropic']);
      }
    });

    if (reqBody) proxyReq.write(reqBody);
    proxyReq.end();
  }
});

// ── OpenAI-format forwarder (Together.ai, OpenAI, etc.) with Sonnet fallback ─
// Translates Anthropic-format request → OpenAI format, sends to the provider,
// translates response back → Anthropic format so Theia IDE understands it.
// On failure → automatically falls back to Anthropic Sonnet (safety net).
function forwardToOpenAIFormat(req, res, whmcsClientId, daUsername, routedModel, provider, isStream, dedupeKey) {
  const openaiBody = anthropicToOpenAI(req.body, routedModel.modelId);
  openaiBody.stream = isStream;
  // If streaming, request usage info in the stream
  if (isStream) {
    openaiBody.stream_options = { include_usage: true };
  }

  const bodyStr = JSON.stringify(openaiBody);
  const chatPath = provider.chatPath || '/v1/chat/completions';
  const providerUrl = new URL(provider.baseUrl + chatPath);
  const providerApiKey = provider.apiKey;
  const providerName = provider.name || 'OpenAI-format';

  const fwdHeaders = {
    'content-type':  'application/json',
    'authorization': `Bearer ${providerApiKey}`,
    'content-length': Buffer.byteLength(bodyStr),
  };

  logger.info(`${providerName.toLowerCase()}-proxy: client ${whmcsClientId} → ${routedModel.displayName} (${routedModel.modelId})`);

  const proxyReq = https.request(
    providerUrl.href,
    { method: 'POST', headers: fwdHeaders },
    (proxyRes) => {
      // If provider returns an error, fall back to Anthropic Sonnet
      if (proxyRes.statusCode >= 400) {
        const errChunks = [];
        proxyRes.on('data', c => errChunks.push(c));
        proxyRes.on('end', () => {
          const errBody = Buffer.concat(errChunks).toString('utf8').slice(0, 300);
          logger.warn(`${providerName.toLowerCase()}-fallback: ${routedModel.displayName} returned ${proxyRes.statusCode} — ${errBody} — falling back to Sonnet`);
          recordProviderFailure(providerName);
          if (!res.headersSent) {
            fallbackToSonnet(req, res, whmcsClientId, daUsername, isStream, dedupeKey);
          }
        });
        return;
      }
      recordProviderSuccess(providerName);
      if (isStream) {
        handleTogetherStream(proxyRes, res, whmcsClientId, daUsername, routedModel);
      } else {
        handleTogetherJson(proxyRes, res, whmcsClientId, daUsername, routedModel, dedupeKey);
      }
    }
  );

  proxyReq.on('error', (err) => {
    recordProviderFailure(providerName);
    logger.warn(`${providerName.toLowerCase()}-fallback: network error (${err.message}) — falling back to Sonnet`);
    if (!res.headersSent) {
      fallbackToSonnet(req, res, whmcsClientId, daUsername, isStream, dedupeKey);
    }
  });

  // 15-second timeout — if provider is slow, bail to Sonnet
  proxyReq.setTimeout(15000, () => {
    logger.warn(`${providerName.toLowerCase()}-fallback: timeout (15s) — falling back to Sonnet`);
    proxyReq.destroy();
    if (!res.headersSent) {
      fallbackToSonnet(req, res, whmcsClientId, daUsername, isStream, dedupeKey);
    }
  });

  proxyReq.write(bodyStr);
  proxyReq.end();
}

/**
 * Fallback cascade: tries multiple providers before giving up.
 * Order: Anthropic Sonnet → Groq (free) → OpenAI → Google → xAI → Ollama (local)
 */
const FALLBACK_CASCADE = [
  { name: 'Anthropic',  provider: 'anthropic', modelId: 'claude-sonnet-4-6',        format: 'anthropic' },
  { name: 'Groq (Free)', provider: 'groq',     modelId: 'llama-3.3-70b-versatile', format: 'openai' },
  { name: 'OpenAI',     provider: 'openai',    modelId: 'gpt-4.1-mini',            format: 'openai' },
  { name: 'Google',     provider: 'google',    modelId: 'gemini-2.5-flash',        format: 'openai' },
  { name: 'xAI',        provider: 'xai',       modelId: 'grok-3-mini',             format: 'openai' },
];

function fallbackToSonnet(req, res, whmcsClientId, daUsername, isStream, dedupeKey, skipProviders) {
  const skip = new Set(skipProviders || []);
  const candidates = FALLBACK_CASCADE.filter(c => !skip.has(c.name) && isProviderAvailable(c.name));

  if (candidates.length === 0) {
    // All cloud providers exhausted — try local Ollama as last resort
    return fallbackToOllama(req, res, whmcsClientId, daUsername, dedupeKey);
  }

  const target = candidates[0];
  skip.add(target.name);
  logger.info(`fallback-cascade: trying ${target.name} (${target.modelId}) for client ${whmcsClientId}`);

  if (target.format === 'anthropic') {
    // Native Anthropic path
    req.body.model = target.modelId;
    const reqBody = JSON.stringify(req.body);
    const upstreamPath = '/' + (req.params[0] || '');
    const upstreamUrl = new URL(upstreamPath, ANTHROPIC_API_HOST);
    const fwdHeaders = {
      'content-type':      'application/json',
      'x-api-key':         ANTHROPIC_API_KEY,
      'anthropic-version': req.headers['anthropic-version'] || '2023-06-01',
      'anthropic-beta':    'prompt-caching-2024-07-31',
      'content-length':    Buffer.byteLength(reqBody),
    };
    const proxyReq = https.request(upstreamUrl.href, { method: 'POST', headers: fwdHeaders }, (proxyRes) => {
      if (proxyRes.statusCode >= 400) {
        proxyRes.resume();
        recordProviderFailure(target.name);
        logger.warn(`fallback-cascade: ${target.name} returned ${proxyRes.statusCode} — trying next`);
        if (!res.headersSent) fallbackToSonnet(req, res, whmcsClientId, daUsername, isStream, dedupeKey, [...skip]);
        return;
      }
      recordProviderSuccess(target.name);
      if (isStream) { handleStreamResponse(proxyRes, res, whmcsClientId, daUsername, target.modelId); }
      else { handleJsonResponse(proxyRes, res, whmcsClientId, daUsername, target.modelId, dedupeKey, req); }
    });
    proxyReq.on('error', (err) => {
      recordProviderFailure(target.name);
      logger.warn(`fallback-cascade: ${target.name} error (${err.message}) — trying next`);
      if (!res.headersSent) fallbackToSonnet(req, res, whmcsClientId, daUsername, isStream, dedupeKey, [...skip]);
    });
    proxyReq.setTimeout(15000, () => { proxyReq.destroy(); });
    proxyReq.write(reqBody);
    proxyReq.end();
  } else {
    // OpenAI-format provider (Groq, OpenAI, Google, xAI)
    const providerCfg = getProvider({ provider: target.provider });
    if (!providerCfg || !providerCfg.apiKey) {
      logger.warn(`fallback-cascade: ${target.name} has no API key — skipping`);
      return fallbackToSonnet(req, res, whmcsClientId, daUsername, isStream, dedupeKey, [...skip]);
    }
    const openaiBody = anthropicToOpenAI(req.body, target.modelId);
    openaiBody.stream = isStream;
    if (isStream) openaiBody.stream_options = { include_usage: true };
    const bodyStr = JSON.stringify(openaiBody);
    const chatPath = providerCfg.chatPath || '/v1/chat/completions';
    const providerUrl = new URL(providerCfg.baseUrl + chatPath);
    const fwdHeaders = {
      'content-type':  'application/json',
      'authorization': `Bearer ${providerCfg.apiKey}`,
      'content-length': Buffer.byteLength(bodyStr),
    };
    const proxyReq = https.request(providerUrl.href, { method: 'POST', headers: fwdHeaders }, (proxyRes) => {
      if (proxyRes.statusCode >= 400) {
        proxyRes.resume();
        recordProviderFailure(target.name);
        logger.warn(`fallback-cascade: ${target.name} returned ${proxyRes.statusCode} — trying next`);
        if (!res.headersSent) fallbackToSonnet(req, res, whmcsClientId, daUsername, isStream, dedupeKey, [...skip]);
        return;
      }
      recordProviderSuccess(target.name);
      const fakeModel = { provider: target.provider, modelId: target.modelId, displayName: target.name + ' (fallback)' };
      if (isStream) { handleTogetherStream(proxyRes, res, whmcsClientId, daUsername, fakeModel); }
      else { handleTogetherJson(proxyRes, res, whmcsClientId, daUsername, fakeModel, dedupeKey); }
    });
    proxyReq.on('error', (err) => {
      recordProviderFailure(target.name);
      logger.warn(`fallback-cascade: ${target.name} error (${err.message}) — trying next`);
      if (!res.headersSent) fallbackToSonnet(req, res, whmcsClientId, daUsername, isStream, dedupeKey, [...skip]);
    });
    proxyReq.setTimeout(15000, () => { proxyReq.destroy(); });
    proxyReq.write(bodyStr);
    proxyReq.end();
  }
}

/**
 * Last-resort fallback: try local Ollama (qwen2.5:3b) when all cloud providers are down.
 */
function fallbackToOllama(req, res, whmcsClientId, daUsername, dedupeKey) {
  logger.warn(`fallback-cascade: ALL cloud providers exhausted — trying local Ollama for client ${whmcsClientId}`);
  const openaiBody = anthropicToOpenAI(req.body, 'qwen2.5:3b');
  openaiBody.stream = false;
  const bodyStr = JSON.stringify(openaiBody);
  const ollamaUrl = 'http://127.0.0.1:11434/v1/chat/completions';
  const fwdHeaders = { 'content-type': 'application/json', 'content-length': Buffer.byteLength(bodyStr) };
  const proxyReq = http.request(ollamaUrl, { method: 'POST', headers: fwdHeaders }, (proxyRes) => {
    if (proxyRes.statusCode >= 400) {
      const errChunks = [];
      proxyRes.on('data', c => errChunks.push(c));
      proxyRes.on('end', () => {
        logger.error(`fallback-ollama: local model returned ${proxyRes.statusCode}`);
        if (!res.headersSent) {
          res.status(502).json({ type: 'error', error: { type: 'api_error', message: 'All AI providers (cloud + local) are unavailable. Please try again later.' } });
        }
      });
      return;
    }
    const fakeModel = { provider: 'ollama', modelId: 'qwen2.5:3b', displayName: 'Qwen 2.5 3B (local fallback)' };
    handleTogetherJson(proxyRes, res, whmcsClientId, daUsername, fakeModel, dedupeKey);
  });
  proxyReq.on('error', (err) => {
    logger.error(`fallback-ollama: local model unreachable: ${err.message}`);
    if (!res.headersSent) {
      res.status(502).json({ type: 'error', error: { type: 'api_error', message: 'All AI providers (cloud + local) are unavailable. Please try again later.' } });
    }
  });
  proxyReq.setTimeout(30000, () => { proxyReq.destroy(); });
  proxyReq.write(bodyStr);
  proxyReq.end();
}

// ── Together.ai JSON response handler ────────────────────────────────────────
function handleTogetherJson(proxyRes, res, whmcsClientId, daUsername, routedModel, dedupeKey) {
  const chunks = [];
  proxyRes.on('data', (chunk) => chunks.push(chunk));
  proxyRes.on('end', async () => {
    const rawBody = Buffer.concat(chunks).toString('utf8');

    if (proxyRes.statusCode >= 200 && proxyRes.statusCode < 300) {
      try {
        const openaiResponse = JSON.parse(rawBody);
        const anthropicResponse = openAIToAnthropic(openaiResponse, routedModel.displayName);

        const inputTokens  = anthropicResponse.usage?.input_tokens  || 0;
        const outputTokens = anthropicResponse.usage?.output_tokens || 0;
        const responseModel = routedModel.modelId;

        // ── Billing (same as Anthropic path) ─────────────────────────────
        if (inputTokens > 0 || outputTokens > 0) {
          const effectiveMultiplier = await getTokenMultiplierAsync(responseModel);
          const usageResult = await tc.addUsage(whmcsClientId, inputTokens, outputTokens, effectiveMultiplier);
          fireAlerts(whmcsClientId, daUsername, usageResult);

          const cost = calculateCost(responseModel, inputTokens, outputTokens, 0, 0);
          recordUsage(whmcsClientId, {
            model: responseModel,
            inputTokens,
            outputTokens,
            daUsername,
          }).catch(e => logger.error(`usage-tracker record error: ${e.message}`));

          budget.recordDailyUsage(whmcsClientId, outputTokens).catch(e => logger.error(`budget daily: ${e.message}`));
          budget.recordGlobalSpend(cost.totalCost).catch(e => logger.error(`budget global: ${e.message}`));
          budget.recordGlobalDailySpend(cost.totalCost).catch(e => logger.error(`budget global-daily: ${e.message}`));
          budget.recordUserDailySpend(whmcsClientId, cost.totalCost).catch(e => logger.error(`budget user-daily: ${e.message}`));
          budget.deductCredit(whmcsClientId, cost.totalCost).catch(e => logger.error(`credit deduct: ${e.message}`));
          anomaly.trackRequest(whmcsClientId, responseModel, cost.totalCost).catch(() => {});
          budget.checkAutoReplenish(whmcsClientId).catch(() => {});

          // Calculate savings vs what Sonnet would have cost
          const sonnetCost = calculateCost('claude-sonnet-4-6', inputTokens, outputTokens, 0, 0);
          const routingSavings = Math.max(0, sonnetCost.totalCost - cost.totalCost);
          if (routingSavings > 0) {
            const redis = getRedis();
            const savingsKey = `savings:${whmcsClientId}`;
            redis.incrbyfloat(savingsKey, routingSavings).catch(() => {});
            redis.expire(savingsKey, 35 * 86400).catch(() => {});
          }

          logger.info(
            `together-proxy: client ${whmcsClientId} (${daUsername}) ` +
            `model=${responseModel} ${inputTokens}in + ${outputTokens}out ` +
            `$${cost.totalCost.toFixed(6)}` +
            (routingSavings > 0 ? ` (saved $${routingSavings.toFixed(4)} vs Sonnet)` : '')
          );
        }

        // Send as Anthropic format to Theia
        const responseBody = JSON.stringify(anthropicResponse);
        if (dedupeKey) setDedupeCache(dedupeKey, responseBody);
        res.status(200).setHeader('content-type', 'application/json');
        res.end(responseBody);
      } catch (parseErr) {
        logger.error(`together-proxy: parse error: ${parseErr.message}`);
        res.status(502).json({
          type: 'error',
          error: { type: 'api_error', message: 'Failed to parse open-source provider response' },
        });
      }
    } else {
      // Forward error in Anthropic format
      logger.error(`together-proxy: ${proxyRes.statusCode} — ${rawBody.slice(0, 500)}`);
      res.status(proxyRes.statusCode >= 400 ? proxyRes.statusCode : 502).json({
        type: 'error',
        error: { type: 'api_error', message: `Open-source provider returned ${proxyRes.statusCode}: ${rawBody.slice(0, 200)}` },
      });
    }
  });
  proxyRes.on('error', (err) => {
    logger.error(`together-proxy: response error: ${err.message}`);
    if (!res.headersSent) {
      res.status(502).json({
        type: 'error',
        error: { type: 'api_error', message: 'Error reading open-source provider response' },
      });
    }
  });
}

// ── Together.ai streaming response handler ───────────────────────────────────
// Translates OpenAI SSE → Anthropic SSE in real-time so Theia sees native events.
function handleTogetherStream(proxyRes, res, whmcsClientId, daUsername, routedModel) {
  // Set SSE headers (Anthropic format)
  res.status(proxyRes.statusCode >= 200 && proxyRes.statusCode < 300 ? 200 : proxyRes.statusCode);
  res.setHeader('content-type', 'text/event-stream');
  res.setHeader('cache-control', 'no-cache');
  res.setHeader('connection', 'keep-alive');
  res.flushHeaders();

  // Handle non-success status
  if (proxyRes.statusCode < 200 || proxyRes.statusCode >= 300) {
    const errChunks = [];
    proxyRes.on('data', (c) => errChunks.push(c));
    proxyRes.on('end', () => {
      const errBody = Buffer.concat(errChunks).toString('utf8');
      logger.error(`together-stream: ${proxyRes.statusCode} — ${errBody.slice(0, 500)}`);
      // Send error event in Anthropic format
      const errEvent = {
        type: 'error',
        error: { type: 'api_error', message: `Open-source provider error ${proxyRes.statusCode}` },
      };
      res.write(`event: error\ndata: ${JSON.stringify(errEvent)}\n\n`);
      res.end();
    });
    return;
  }

  const translator = createStreamTranslator(routedModel.displayName);
  let sseBuffer = '';
  let inputTokens = 0;
  let outputTokens = 0;
  let streamedContentLen = 0; // fallback token estimation from streamed chars
  let doneSignaled = false;    // prevent duplicate [DONE] processing

  proxyRes.on('data', (chunk) => {
    sseBuffer += chunk.toString('utf8');
    // Handle both \r\n and \n line endings (Groq uses \r\n)
    const lines = sseBuffer.split(/\r?\n/);
    sseBuffer = lines.pop() || '';

    for (const line of lines) {
      // Support both "data: " and "data:" (SSE spec allows no space)
      let payload;
      if (line.startsWith('data: ')) {
        payload = line.slice(6).trim();
      } else if (line.startsWith('data:') && !line.startsWith('data: ')) {
        payload = line.slice(5).trim();
      } else {
        continue;
      }
      if (!payload) continue;

      if (payload === '[DONE]') {
        if (doneSignaled) continue; // already processed
        doneSignaled = true;
      }

      // Extract usage from stream chunks before translating
      if (payload !== '[DONE]') {
        try {
          const parsed = JSON.parse(payload);
          if (parsed.usage) {
            inputTokens = parsed.usage.prompt_tokens || inputTokens;
            outputTokens = parsed.usage.completion_tokens || outputTokens;
          }
          // Track content length for fallback estimation
          const delta = parsed.choices?.[0]?.delta;
          if (delta?.content) streamedContentLen += delta.content.length;
        } catch {} // ignore parse errors in tracking
      }

      const events = translator(payload);
      for (const evt of events) {
        res.write(evt + '\n\n');
      }
    }
  });

  proxyRes.on('end', async () => {
    // Flush any remaining buffer
    if (sseBuffer.trim()) {
      const remaining = sseBuffer.trim();
      // Support both "data: " and "data:" prefixes
      let payload;
      if (remaining.startsWith('data: ')) {
        payload = remaining.slice(6).trim();
      } else if (remaining.startsWith('data:')) {
        payload = remaining.slice(5).trim();
      }
      if (payload) {
        if (payload === '[DONE]') {
          if (!doneSignaled) {
            doneSignaled = true;
            const events = translator(payload);
            for (const evt of events) {
              res.write(evt + '\n\n');
            }
          }
        } else {
          try {
            const parsed = JSON.parse(payload);
            if (parsed.usage) {
              inputTokens = parsed.usage.prompt_tokens || inputTokens;
              outputTokens = parsed.usage.completion_tokens || outputTokens;
            }
          } catch {}
          const events = translator(payload);
          for (const evt of events) {
            res.write(evt + '\n\n');
          }
        }
      }
    }

    // Send [DONE] signal ONLY if not already sent
    if (!doneSignaled) {
      doneSignaled = true;
      const doneEvents = translator('[DONE]');
      for (const evt of doneEvents) {
        res.write(evt + '\n\n');
      }
    }

    // ── Billing ─────────────────────────────────────────────────────────
    // Fallback: if provider didn't include usage data, estimate from content
    if (inputTokens === 0 && outputTokens === 0 && streamedContentLen > 0) {
      outputTokens = Math.ceil(streamedContentLen / 4); // ~4 chars per token
      inputTokens = Math.ceil(outputTokens * 0.5);       // conservative input estimate
      logger.warn(`together-stream: no usage in stream for ${routedModel.modelId}, estimated ${inputTokens}/${outputTokens} tokens from ${streamedContentLen} chars`);
    }
    if (inputTokens > 0 || outputTokens > 0) {
      try {
        const effectiveMultiplier = await getTokenMultiplierAsync(routedModel.modelId);
        const usageResult = await tc.addUsage(whmcsClientId, inputTokens, outputTokens, effectiveMultiplier);
        fireAlerts(whmcsClientId, daUsername, usageResult);

        const cost = calculateCost(routedModel.modelId, inputTokens, outputTokens, 0, 0);
        recordUsage(whmcsClientId, {
          model: routedModel.modelId,
          inputTokens,
          outputTokens,
          daUsername,
        }).catch(e => logger.error(`usage-tracker record error: ${e.message}`));

        budget.recordDailyUsage(whmcsClientId, outputTokens).catch(e => logger.error(`budget daily: ${e.message}`));
        budget.recordGlobalSpend(cost.totalCost).catch(e => logger.error(`budget global: ${e.message}`));
        budget.recordGlobalDailySpend(cost.totalCost).catch(e => logger.error(`budget global-daily: ${e.message}`));
        budget.recordUserDailySpend(whmcsClientId, cost.totalCost).catch(e => logger.error(`budget user-daily: ${e.message}`));
        budget.deductCredit(whmcsClientId, cost.totalCost).catch(e => logger.error(`credit deduct: ${e.message}`));
        anomaly.trackRequest(whmcsClientId, routedModel.modelId, cost.totalCost).catch(() => {});
        budget.checkAutoReplenish(whmcsClientId).catch(() => {});

        const sonnetCost = calculateCost('claude-sonnet-4-6', inputTokens, outputTokens, 0, 0);
        const routingSavings = Math.max(0, sonnetCost.totalCost - cost.totalCost);
        if (routingSavings > 0) {
          const redis = getRedis();
          const savingsKey = `savings:${whmcsClientId}`;
          redis.incrbyfloat(savingsKey, routingSavings).catch(() => {});
          redis.expire(savingsKey, 35 * 86400).catch(() => {});
        }

        logger.info(
          `together-stream: client ${whmcsClientId} (${daUsername}) ` +
          `model=${routedModel.modelId} ${inputTokens}in + ${outputTokens}out ` +
          `$${cost.totalCost.toFixed(6)}` +
          (routingSavings > 0 ? ` (saved $${routingSavings.toFixed(4)} vs Sonnet)` : '')
        );
      } catch (err) {
        logger.error(`together-stream: billing error: ${err.message}`);
      }
    }

    res.end();
  });

  proxyRes.on('error', (err) => {
    logger.error(`together-stream: error: ${err.message}`);
    res.end();
  });

  res.on('close', () => {
    proxyRes.destroy();
  });
}

// ── Non-streaming response handler ───────────────────────────────────────────
function handleJsonResponse(proxyRes, res, whmcsClientId, daUsername, requestModel, dedupeKey, req) {
  const chunks = [];

  proxyRes.on('data', (chunk) => chunks.push(chunk));
  proxyRes.on('end', async () => {
    const body = Buffer.concat(chunks);

    // Forward status and relevant headers
    res.status(proxyRes.statusCode);
    const passthroughHeaders = [
      'content-type', 'x-request-id', 'request-id',
      'anthropic-ratelimit-requests-limit',
      'anthropic-ratelimit-requests-remaining',
      'anthropic-ratelimit-tokens-limit',
      'anthropic-ratelimit-tokens-remaining',
    ];
    for (const h of passthroughHeaders) {
      if (proxyRes.headers[h]) res.setHeader(h, proxyRes.headers[h]);
    }

    // Log error body for 4xx responses to aid debugging
    if (proxyRes.statusCode >= 400 && proxyRes.statusCode < 500) {
      const errBody = body.toString('utf8').slice(0, 500);
      logger.warn(`anthropic-proxy: client ${whmcsClientId} got ${proxyRes.statusCode} — body: ${errBody}`);
    }

    // Only count tokens on success
    if (proxyRes.statusCode >= 200 && proxyRes.statusCode < 300) {
      try {
        const data = JSON.parse(body.toString('utf8'));
        const inputTokens         = data.usage?.input_tokens  || 0;
        const outputTokens        = data.usage?.output_tokens || 0;
        const cacheCreationTokens = data.usage?.cache_creation_input_tokens || 0;
        const cacheReadTokens     = data.usage?.cache_read_input_tokens || 0;
        const responseModel = data.model || requestModel;

        if (inputTokens > 0 || outputTokens > 0) {
          // Plan counter — only output tokens count toward user's monthly limit
          const effectiveMultiplier = await getTokenMultiplierAsync(responseModel);
          const usageResult = await tc.addUsage(whmcsClientId, inputTokens, outputTokens, effectiveMultiplier);
          fireAlerts(whmcsClientId, daUsername, usageResult);

          // Detailed per-model tracking
          const totalTokens = inputTokens + outputTokens;
          const cost = calculateCost(responseModel, inputTokens, outputTokens, cacheCreationTokens, cacheReadTokens);
          recordUsage(whmcsClientId, {
            model: responseModel,
            inputTokens,
            outputTokens,
            daUsername,
          }).catch(e => logger.error(`usage-tracker record error: ${e.message}`));

          // ── Budget tracking (actual USD cost — protects the platform) ────
          budget.recordDailyUsage(whmcsClientId, outputTokens).catch(e => logger.error(`budget daily: ${e.message}`));
          budget.recordGlobalSpend(cost.totalCost).catch(e => logger.error(`budget global: ${e.message}`));
          budget.recordGlobalDailySpend(cost.totalCost).catch(e => logger.error(`budget global-daily: ${e.message}`));
          budget.recordUserDailySpend(whmcsClientId, cost.totalCost).catch(e => logger.error(`budget user-daily: ${e.message}`));
          budget.deductCredit(whmcsClientId, cost.totalCost).catch(e => logger.error(`credit deduct: ${e.message}`));
          anomaly.trackRequest(whmcsClientId, responseModel, cost.totalCost).catch(() => {});
          budget.checkAutoReplenish(whmcsClientId).catch(() => {});

          // Track cache savings per user (powers dashboard "GoCodeMe saved you $X")
          if (cost.cacheSavings > 0) {
            const redis = getRedis();
            const savingsKey = `savings:${whmcsClientId}`;
            redis.incrbyfloat(savingsKey, cost.cacheSavings).catch(() => {});
            redis.expire(savingsKey, 35 * 86400).catch(() => {});
          }

          logger.info(
            `anthropic-proxy: client ${whmcsClientId} (${daUsername}) ` +
            `model=${responseModel} ${inputTokens}in + ${outputTokens}out ` +
            `cache:${cacheReadTokens}read/${cacheCreationTokens}write ` +
            `$${cost.totalCost.toFixed(6)}` +
            (cost.cacheSavings > 0 ? ` (saved $${cost.cacheSavings.toFixed(4)})` : '')
          );

          // ── Cross-instance memory write (non-blocking) ──────────────
          alfredMemory.recordInteraction(whmcsClientId, {
            source: 'ide',
            userMessage: (Array.isArray(req.body.messages) && req.body.messages.length > 0)
              ? (typeof req.body.messages[req.body.messages.length - 1].content === 'string'
                ? req.body.messages[req.body.messages.length - 1].content : '')
              : '',
            alfredResponse: data.content?.[0]?.text || '',
            model: responseModel,
            agent: 'alfred',
          }).catch(() => {});
        }
      } catch (parseErr) {
        logger.warn(`anthropic-proxy: failed to parse response for token counting: ${parseErr.message}`);
      }
    }

    // Cache successful non-streaming responses for dedup
    if (dedupeKey && proxyRes.statusCode >= 200 && proxyRes.statusCode < 300) {
      setDedupeCache(dedupeKey, body.toString('utf8'));
    }
    res.end(body);
  });

  proxyRes.on('error', (err) => {
    logger.error(`anthropic-proxy: response error: ${err.message}`);
    if (!res.headersSent) {
      res.status(502).json({
        type: 'error',
        error: { type: 'api_error', message: 'Error reading upstream response' },
      });
    }
  });
}

// ── Streaming response handler ───────────────────────────────────────────────
function handleStreamResponse(proxyRes, res, whmcsClientId, daUsername, requestModel) {
  // Guard: if headers already sent (e.g. fallback race), skip setting them
  if (res.headersSent) {
    logger.warn('anthropic-proxy: headers already sent, piping stream body only');
    proxyRes.pipe(res, { end: false });
    proxyRes.on('end', () => { try { res.end(); } catch(_){} });
    return;
  }
  // Forward status and headers for SSE
  res.status(proxyRes.statusCode);
  res.setHeader('content-type', proxyRes.headers['content-type'] || 'text/event-stream');
  res.setHeader('cache-control', 'no-cache');
  res.setHeader('connection', 'keep-alive');

  // Passthrough rate-limit headers
  const passthroughHeaders = [
    'x-request-id', 'request-id',
    'anthropic-ratelimit-requests-limit',
    'anthropic-ratelimit-requests-remaining',
  ];
  for (const h of passthroughHeaders) {
    if (proxyRes.headers[h]) res.setHeader(h, proxyRes.headers[h]);
  }
  res.flushHeaders();

  // Buffer SSE data to capture usage from message_delta and message_stop events
  let inputTokens         = 0;
  let outputTokens        = 0;
  let cacheCreationTokens = 0;
  let cacheReadTokens     = 0;
  let sseBuffer           = '';
  let streamModel         = requestModel;

  proxyRes.on('data', (chunk) => {
    const text = chunk.toString('utf8');
    // Forward chunk immediately (transparency)
    res.write(chunk);

    // Parse SSE events to extract usage
    sseBuffer += text;
    const lines = sseBuffer.split('\n');
    // Keep the last incomplete line in buffer
    sseBuffer = lines.pop() || '';

    for (const line of lines) {
      if (!line.startsWith('data: ')) continue;
      const payload = line.slice(6).trim();
      if (payload === '[DONE]') continue;

      try {
        const evt = JSON.parse(payload);

        // message_start carries input token count and model
        if (evt.type === 'message_start' && evt.message?.usage) {
          inputTokens = evt.message.usage.input_tokens || 0;
          cacheCreationTokens = evt.message.usage.cache_creation_input_tokens || 0;
          cacheReadTokens = evt.message.usage.cache_read_input_tokens || 0;
          if (evt.message?.model) streamModel = evt.message.model;
        }

        // message_delta carries output token count at stream end
        if (evt.type === 'message_delta' && evt.usage) {
          outputTokens = evt.usage.output_tokens || 0;
        }
      } catch (_) {
        // Not all data lines are JSON (e.g. ping events)
      }
    }
  });

  proxyRes.on('end', async () => {
    // Process any remaining buffered SSE data
    if (sseBuffer.trim()) {
      const remaining = sseBuffer.trim();
      if (remaining.startsWith('data: ')) {
        try {
          const evt = JSON.parse(remaining.slice(6).trim());
          if (evt.type === 'message_start' && evt.message?.usage) {
            inputTokens = evt.message.usage.input_tokens || 0;
          }
          if (evt.type === 'message_delta' && evt.usage) {
            outputTokens = evt.usage.output_tokens || 0;
          }
        } catch (_) {}
      }
    }

    // Count tokens
    if (proxyRes.statusCode >= 200 && proxyRes.statusCode < 300 && (inputTokens > 0 || outputTokens > 0)) {
      try {
        // Plan counter — only output tokens count toward user's monthly limit
        const effectiveMultiplier = await getTokenMultiplierAsync(streamModel);
        const usageResult = await tc.addUsage(whmcsClientId, inputTokens, outputTokens, effectiveMultiplier);
        fireAlerts(whmcsClientId, daUsername, usageResult);

        // Detailed per-model tracking
        const totalTokens = inputTokens + outputTokens;
        const cost = calculateCost(streamModel, inputTokens, outputTokens, cacheCreationTokens, cacheReadTokens);
        recordUsage(whmcsClientId, {
          model: streamModel,
          inputTokens,
          outputTokens,
          daUsername,
        }).catch(e => logger.error(`usage-tracker record error: ${e.message}`));

        // ── Budget tracking (actual USD cost — protects the platform) ────
        budget.recordDailyUsage(whmcsClientId, outputTokens).catch(e => logger.error(`budget daily: ${e.message}`));
        budget.recordGlobalSpend(cost.totalCost).catch(e => logger.error(`budget global: ${e.message}`));
        budget.recordGlobalDailySpend(cost.totalCost).catch(e => logger.error(`budget global-daily: ${e.message}`));
        budget.recordUserDailySpend(whmcsClientId, cost.totalCost).catch(e => logger.error(`budget user-daily: ${e.message}`));
        budget.deductCredit(whmcsClientId, cost.totalCost).catch(e => logger.error(`credit deduct: ${e.message}`));
        anomaly.trackRequest(whmcsClientId, streamModel, cost.totalCost).catch(() => {});
        budget.checkAutoReplenish(whmcsClientId).catch(() => {});

        // Track cache savings per user (powers dashboard "GoCodeMe saved you $X")
        if (cost.cacheSavings > 0) {
          const redis = getRedis();
          const savingsKey = `savings:${whmcsClientId}`;
          redis.incrbyfloat(savingsKey, cost.cacheSavings).catch(() => {});
          redis.expire(savingsKey, 35 * 86400).catch(() => {});
        }

        logger.info(
          `anthropic-proxy [stream]: client ${whmcsClientId} (${daUsername}) ` +
          `model=${streamModel} ${inputTokens}in + ${outputTokens}out ` +
          `cache:${cacheReadTokens}read/${cacheCreationTokens}write ` +
          `$${cost.totalCost.toFixed(6)}` +
          (cost.cacheSavings > 0 ? ` (saved $${cost.cacheSavings.toFixed(4)})` : '')
        );
      } catch (err) {
        logger.error(`anthropic-proxy: token count error: ${err.message}`);
      }
    }

    try { res.end(); } catch(_) {}
  });

  proxyRes.on('error', (err) => {
    logger.error(`anthropic-proxy: stream error: ${err.message}`);
    // Smart retry: if we haven't sent any data yet, retry once
    if (!res.headersSent && !res._smartRetried) {
      res._smartRetried = true;
      logger.info(`anthropic-proxy: smart retry — retrying after stream error`);
      try {
        // Send a retry SSE event so the IDE knows we're retrying
        res.write(`event: gocodeme_retry\ndata: ${JSON.stringify({retrying:true,reason:err.message})}\n\n`);
      } catch(_) {}
    }
    try { res.end(); } catch(_) {}
  });

  // If client disconnects, abort upstream
  res.on('close', () => {
    proxyRes.destroy();
  });
}

// ── Context usage calculation ────────────────────────────────────────────────
// Returns how full the conversation context is (0.0–1.0) and a suggestion
// to compact when approaching the limit.  Exposed as a header on responses
// and used by the IDE widget to show a "Compact Conversation" banner.
function getContextUsage(messages) {
  const est = estimateTokens(messages || []);
  const pct = est / MAX_ESTIMATED_TOKENS;
  let level = 'ok';
  if (pct >= COMPACT_CRITICAL)  level = 'critical';
  else if (pct >= COMPACT_THRESHOLD) level = 'warning';
  return { estimatedTokens: est, maxTokens: MAX_ESTIMATED_TOKENS, percent: Math.round(pct * 100), level };
}
module.exports = router;
