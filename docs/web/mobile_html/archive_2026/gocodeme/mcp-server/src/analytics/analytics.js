/**
 * analytics.js — Tool Usage Analytics Engine
 *
 * Tracks tool invocations, latency, success/failure rates, and usage patterns.
 * Stored in a rotating log file and aggregated in-memory for fast queries.
 *
 * Storage: ~/.gocodeme/analytics/tool_usage.jsonl
 *
 * Features:
 *  - Per-tool invocation count and latency tracking
 *  - Success/failure rate per tool
 *  - Top-N most used tools
 *  - Time-series data (hourly buckets)
 *  - User-level analytics
 */

import { appendFile, readFile, mkdir } from 'node:fs/promises';
import path from 'node:path';

const ANALYTICS_DIR = '/home/gositeme/.gocodeme/analytics';
const LOG_FILE = path.join(ANALYTICS_DIR, 'tool_usage.jsonl');

// In-memory aggregated stats
const stats = {
  perTool: {},      // toolName → { calls, successes, failures, totalLatency, lastUsed }
  perUser: {},      // daUsername → { calls, lastSeen }
  hourlyBuckets: {},// "YYYY-MM-DD-HH" → { calls, tools: {} }
  startedAt: new Date().toISOString(),
  totalCalls: 0,
};

let initialized = false;

/**
 * Initialize analytics — load existing log and rebuild in-memory stats.
 */
async function init() {
  if (initialized) return;

  try {
    await mkdir(ANALYTICS_DIR, { recursive: true });
  } catch { /* exists */ }

  try {
    const data = await readFile(LOG_FILE, 'utf-8');
    const lines = data.split('\n').filter(Boolean);
    for (const line of lines) {
      try {
        const entry = JSON.parse(line);
        aggregate(entry);
      } catch { /* skip malformed */ }
    }
  } catch { /* no log file yet */ }

  initialized = true;
}

/**
 * Aggregate a single log entry into in-memory stats.
 */
function aggregate(entry) {
  const { tool, user, latencyMs, success, timestamp } = entry;
  if (!tool) return;

  // Per-tool
  if (!stats.perTool[tool]) {
    stats.perTool[tool] = { calls: 0, successes: 0, failures: 0, totalLatency: 0, lastUsed: null };
  }
  const t = stats.perTool[tool];
  t.calls++;
  if (success) t.successes++; else t.failures++;
  t.totalLatency += latencyMs || 0;
  t.lastUsed = timestamp;

  // Per-user
  if (user) {
    if (!stats.perUser[user]) {
      stats.perUser[user] = { calls: 0, lastSeen: null };
    }
    stats.perUser[user].calls++;
    stats.perUser[user].lastSeen = timestamp;
  }

  // Hourly bucket
  if (timestamp) {
    const hour = timestamp.slice(0, 13).replace('T', '-'); // "2026-03-01-05"
    if (!stats.hourlyBuckets[hour]) {
      stats.hourlyBuckets[hour] = { calls: 0, tools: {} };
    }
    stats.hourlyBuckets[hour].calls++;
    stats.hourlyBuckets[hour].tools[tool] = (stats.hourlyBuckets[hour].tools[tool] || 0) + 1;
  }

  stats.totalCalls++;
}

/**
 * Record a tool invocation.
 *
 * @param {string} toolName — name of the tool called
 * @param {string} daUsername — user who called it
 * @param {number} latencyMs — how long the call took
 * @param {boolean} success — whether the call succeeded
 * @param {object} [extra] — optional extra data
 */
export async function recordToolCall(toolName, daUsername, latencyMs, success, extra = {}) {
  await init();

  const entry = {
    tool: toolName,
    user: daUsername,
    latencyMs: Math.round(latencyMs),
    success,
    timestamp: new Date().toISOString(),
    ...extra,
  };

  aggregate(entry);

  // Append to log file (non-blocking)
  try {
    await appendFile(LOG_FILE, JSON.stringify(entry) + '\n');
  } catch (err) {
    console.error(`[ANALYTICS] Write failed: ${err.message}`);
  }
}

/**
 * Get analytics summary.
 *
 * @param {object} [options]
 * @param {number} [options.topN=20] — top N tools by usage
 * @param {string} [options.user] — filter by user
 * @returns {Promise<object>}
 */
export async function getAnalytics(options = {}) {
  await init();

  const { topN = 20, user = null } = options;

  // Sort tools by call count
  const toolList = Object.entries(stats.perTool)
    .map(([name, data]) => ({
      name,
      calls: data.calls,
      successes: data.successes,
      failures: data.failures,
      avgLatencyMs: data.calls > 0 ? Math.round(data.totalLatency / data.calls) : 0,
      successRate: data.calls > 0 ? Math.round((data.successes / data.calls) * 100) : 0,
      lastUsed: data.lastUsed,
    }))
    .sort((a, b) => b.calls - a.calls)
    .slice(0, topN);

  // Recent hourly activity (last 24h)
  const now = new Date();
  const recentHours = [];
  for (let i = 0; i < 24; i++) {
    const d = new Date(now - i * 3600 * 1000);
    const key = d.toISOString().slice(0, 13).replace('T', '-');
    const bucket = stats.hourlyBuckets[key];
    if (bucket) {
      recentHours.push({ hour: key, calls: bucket.calls });
    }
  }

  return {
    total_calls: stats.totalCalls,
    unique_tools_used: Object.keys(stats.perTool).length,
    unique_users: Object.keys(stats.perUser).length,
    tracking_since: stats.startedAt,
    top_tools: toolList,
    recent_24h: recentHours,
    per_user: user ? (stats.perUser[user] || { calls: 0 }) : undefined,
  };
}
