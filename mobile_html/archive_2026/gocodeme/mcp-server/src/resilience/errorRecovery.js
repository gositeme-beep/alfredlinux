/**
 * resilience/errorRecovery.js — Graceful Error Recovery & Retry Engine
 *
 * Wraps tool execution with:
 *   1. Automatic retry for transient failures (network, timeout, 5xx)
 *   2. Partial rollback for multi-step operations that fail midway
 *   3. Circuit breaker — if a tool fails N times, disable it temporarily
 *   4. Graceful user-facing error messages (no stack traces)
 *   5. Error telemetry for monitoring
 *
 * Usage:
 *   import { withRetry, CircuitBreaker } from './resilience/errorRecovery.js';
 *   const result = await withRetry(() => someToolCall(), { maxRetries: 3 });
 */

import { ErrorCode, McpError } from '@modelcontextprotocol/sdk/types.js';

// ── Configuration ────────────────────────────────────────────────────────────
const DEFAULT_MAX_RETRIES = 2;
const DEFAULT_BASE_DELAY_MS = 500;
const DEFAULT_MAX_DELAY_MS = 5000;
const CIRCUIT_BREAKER_THRESHOLD = 5;     // failures before opening circuit
const CIRCUIT_BREAKER_RESET_MS = 60_000; // 1 minute cooldown

// ── Error classification ─────────────────────────────────────────────────────

/**
 * Classify an error as retryable or permanent.
 *
 * Retryable:
 *   - Network errors (ECONNRESET, ETIMEDOUT, ECONNREFUSED)
 *   - Timeout errors
 *   - HTTP 429 (rate limited), 502, 503, 504
 *   - Redis connection errors
 *
 * Permanent (never retry):
 *   - Validation errors (bad params, path escape)
 *   - Auth errors (expired token)
 *   - Not found (file, database, domain)
 *   - Permission denied
 *
 * @param {Error} err
 * @returns {'retryable'|'permanent'|'circuit-break'}
 */
export function classifyError(err) {
  const msg = (err.message || '').toLowerCase();
  const code = err.code || '';

  // Network / connection
  if (['ECONNRESET', 'ECONNREFUSED', 'ETIMEDOUT', 'EPIPE', 'ENOTFOUND', 'EAI_AGAIN'].includes(code)) {
    return 'retryable';
  }

  // Timeouts
  if (msg.includes('timeout') || msg.includes('timed out') || code === 'ETIMEOUT') {
    return 'retryable';
  }

  // HTTP status-based
  if (err.statusCode) {
    if (err.statusCode === 429) return 'retryable'; // rate limited
    if (err.statusCode >= 500) return 'retryable'; // server error
    if (err.statusCode === 401 || err.statusCode === 403) return 'permanent';
    if (err.statusCode === 404) return 'permanent';
    if (err.statusCode >= 400 && err.statusCode < 500) return 'permanent';
  }

  // Redis errors
  if (msg.includes('redis') && (msg.includes('connect') || msg.includes('closed'))) {
    return 'retryable';
  }

  // DirectAdmin API errors
  if (msg.includes('da api') && msg.includes('error')) {
    return 'retryable'; // DA might be temporarily overloaded
  }

  // MCP validation errors
  if (err instanceof McpError) {
    if (err.code === ErrorCode.InvalidParams) return 'permanent';
    if (err.code === ErrorCode.MethodNotFound) return 'permanent';
  }

  // Zod validation
  if (err.name === 'ZodError') return 'permanent';

  // Path/permission errors
  if (msg.includes('permission denied') || msg.includes('access denied') || msg.includes('escape blocked')) {
    return 'permanent';
  }

  // Default — assume retryable once
  return 'retryable';
}

/**
 * Convert a raw error into a user-friendly MCP error response.
 * Strips stack traces, internal paths, and sensitive info.
 *
 * @param {Error} err
 * @param {string} toolName
 * @returns {{ content: Array<{type: string, text: string}>, isError: true }}
 */
export function friendlyError(err, toolName) {
  const classification = classifyError(err);
  let message;

  if (err instanceof McpError) {
    message = err.message;
  } else if (classification === 'retryable') {
    message = `⚠️ ${toolName} encountered a temporary issue: ${sanitizeMessage(err.message)}. The operation was retried automatically.`;
  } else {
    message = `❌ ${toolName} failed: ${sanitizeMessage(err.message)}`;
  }

  return {
    content: [{ type: 'text', text: message }],
    isError: true,
  };
}

/**
 * Strip sensitive info from error messages.
 * @param {string} msg
 * @returns {string}
 */
function sanitizeMessage(msg) {
  if (!msg) return 'Unknown error';
  return msg
    .replace(/\/home\/\w+/g, '/home/***')          // Hide usernames in paths
    .replace(/password[=:]\S+/gi, 'password=***')  // Hide passwords
    .replace(/api[-_]?key[=:]\S+/gi, 'apikey=***') // Hide API keys
    .replace(/Bearer \S+/g, 'Bearer ***')           // Hide tokens
    .replace(/at\s+.+\(.+\.js:\d+:\d+\)/g, '')     // Strip stack trace lines
    .replace(/\n\s*at\s+.+/g, '')                   // Strip multi-line stacks
    .trim();
}

// ── Retry engine ─────────────────────────────────────────────────────────────

/**
 * Execute a function with automatic retry on transient failures.
 *
 * @param {Function} fn — async function to execute
 * @param {object} [opts]
 * @param {number} [opts.maxRetries=2]
 * @param {number} [opts.baseDelayMs=500]
 * @param {number} [opts.maxDelayMs=5000]
 * @param {string} [opts.toolName] — for logging
 * @param {Function} [opts.onRetry] — callback(attempt, err, delayMs)
 * @returns {Promise<any>}
 */
export async function withRetry(fn, opts = {}) {
  const maxRetries = opts.maxRetries ?? DEFAULT_MAX_RETRIES;
  const baseDelay = opts.baseDelayMs ?? DEFAULT_BASE_DELAY_MS;
  const maxDelay = opts.maxDelayMs ?? DEFAULT_MAX_DELAY_MS;
  const toolName = opts.toolName || 'tool';

  let lastErr;
  for (let attempt = 0; attempt <= maxRetries; attempt++) {
    try {
      return await fn();
    } catch (err) {
      lastErr = err;
      const classification = classifyError(err);

      if (classification === 'permanent' || attempt === maxRetries) {
        throw err;
      }

      // Exponential backoff with jitter
      const delay = Math.min(
        baseDelay * Math.pow(2, attempt) + Math.random() * 200,
        maxDelay
      );

      if (opts.onRetry) {
        opts.onRetry(attempt + 1, err, delay);
      }

      console.error(
        `[RETRY] ${toolName} attempt ${attempt + 1}/${maxRetries} failed: ${err.message}. Retrying in ${Math.round(delay)}ms...`
      );

      await sleep(delay);
    }
  }
  throw lastErr;
}

// ── Circuit Breaker ──────────────────────────────────────────────────────────

const circuits = new Map(); // toolName → { failures, state, lastFailure }

/**
 * Circuit breaker for individual tools.
 * After CIRCUIT_BREAKER_THRESHOLD consecutive failures, the circuit "opens"
 * and rejects calls for CIRCUIT_BREAKER_RESET_MS, giving the dependency time
 * to recover.
 */
export class CircuitBreaker {
  /**
   * Check if a tool is allowed to execute.
   * @param {string} toolName
   * @returns {{ allowed: boolean, state: string, failures: number }}
   */
  static check(toolName) {
    const circuit = circuits.get(toolName);
    if (!circuit) return { allowed: true, state: 'closed', failures: 0 };

    if (circuit.state === 'open') {
      const elapsed = Date.now() - circuit.lastFailure;
      if (elapsed >= CIRCUIT_BREAKER_RESET_MS) {
        // Transition to half-open — allow one probe request
        circuit.state = 'half-open';
        return { allowed: true, state: 'half-open', failures: circuit.failures };
      }
      return {
        allowed: false,
        state: 'open',
        failures: circuit.failures,
        resetIn: Math.ceil((CIRCUIT_BREAKER_RESET_MS - elapsed) / 1000),
      };
    }

    return { allowed: true, state: circuit.state, failures: circuit.failures };
  }

  /**
   * Record a successful execution — reset the circuit.
   * @param {string} toolName
   */
  static recordSuccess(toolName) {
    const circuit = circuits.get(toolName);
    if (circuit) {
      circuit.failures = 0;
      circuit.state = 'closed';
    }
  }

  /**
   * Record a failure.
   * @param {string} toolName
   */
  static recordFailure(toolName) {
    let circuit = circuits.get(toolName);
    if (!circuit) {
      circuit = { failures: 0, state: 'closed', lastFailure: 0 };
      circuits.set(toolName, circuit);
    }

    circuit.failures++;
    circuit.lastFailure = Date.now();

    if (circuit.failures >= CIRCUIT_BREAKER_THRESHOLD) {
      circuit.state = 'open';
      console.error(`[CIRCUIT] ${toolName} circuit OPEN after ${circuit.failures} failures. Cooldown ${CIRCUIT_BREAKER_RESET_MS / 1000}s.`);
    }
  }

  /**
   * Get all circuit states (for health/monitoring).
   * @returns {object}
   */
  static getAllStates() {
    const states = {};
    for (const [name, circuit] of circuits.entries()) {
      states[name] = {
        state: circuit.state,
        failures: circuit.failures,
        lastFailure: circuit.lastFailure ? new Date(circuit.lastFailure).toISOString() : null,
      };
    }
    return states;
  }
}

// ── Rollback registry ────────────────────────────────────────────────────────

/**
 * Execute a multi-step operation with automatic rollback on failure.
 * Each step can register an undo function that runs if a later step fails.
 *
 * @param {Array<{name: string, execute: Function, undo?: Function}>} steps
 * @param {object} [opts]
 * @param {string} [opts.operationName]
 * @returns {Promise<{success: boolean, results: Array, rolledBack?: Array}>}
 */
export async function withRollback(steps, opts = {}) {
  const opName = opts.operationName || 'multi-step operation';
  const completed = [];
  const results = [];

  for (let i = 0; i < steps.length; i++) {
    const step = steps[i];
    try {
      const result = await step.execute();
      results.push({ step: step.name, success: true, result });
      if (step.undo) {
        completed.push(step);
      }
    } catch (err) {
      console.error(`[ROLLBACK] Step "${step.name}" failed in ${opName}: ${err.message}`);
      results.push({ step: step.name, success: false, error: err.message });

      // Rollback completed steps in reverse order
      const rolledBack = [];
      for (let j = completed.length - 1; j >= 0; j--) {
        try {
          await completed[j].undo();
          rolledBack.push(completed[j].name);
          console.error(`[ROLLBACK] Rolled back: ${completed[j].name}`);
        } catch (undoErr) {
          console.error(`[ROLLBACK] Failed to roll back ${completed[j].name}: ${undoErr.message}`);
          rolledBack.push(`${completed[j].name} (FAILED)`);
        }
      }

      return { success: false, results, rolledBack, error: err.message };
    }
  }

  return { success: true, results };
}

// ── Error telemetry ──────────────────────────────────────────────────────────

const errorLog = [];
const MAX_ERROR_LOG = 200;

/**
 * Record an error for telemetry/monitoring.
 *
 * @param {string} toolName
 * @param {Error} err
 * @param {string} username
 * @param {object} [context]
 */
export function recordError(toolName, err, username, context = {}) {
  const entry = {
    timestamp: new Date().toISOString(),
    tool: toolName,
    user: username,
    classification: classifyError(err),
    message: sanitizeMessage(err.message),
    code: err.code || err.statusCode || null,
    ...context,
  };

  errorLog.push(entry);
  if (errorLog.length > MAX_ERROR_LOG) {
    errorLog.splice(0, errorLog.length - MAX_ERROR_LOG);
  }
}

/**
 * Get recent errors (for monitoring/health endpoint).
 *
 * @param {number} [last=20]
 * @returns {Array}
 */
export function getRecentErrors(last = 20) {
  return errorLog.slice(-last);
}

/**
 * Get error summary (counts by tool and classification).
 * @returns {object}
 */
export function getErrorSummary() {
  const byTool = {};
  const byClass = { retryable: 0, permanent: 0, 'circuit-break': 0 };

  for (const entry of errorLog) {
    byTool[entry.tool] = (byTool[entry.tool] || 0) + 1;
    byClass[entry.classification] = (byClass[entry.classification] || 0) + 1;
  }

  return {
    total: errorLog.length,
    byTool,
    byClassification: byClass,
    circuitBreakers: CircuitBreaker.getAllStates(),
  };
}

// ── Utility ──────────────────────────────────────────────────────────────────
function sleep(ms) {
  return new Promise(resolve => setTimeout(resolve, ms));
}
