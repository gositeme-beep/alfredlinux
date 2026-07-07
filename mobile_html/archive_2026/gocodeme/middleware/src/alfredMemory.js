/**
 * Alfred Unified Memory — Cross-Instance Context Layer
 *
 * All Alfred instances (Widget, IDE, Voice) read/write to shared Redis keys
 * so Alfred remembers conversations regardless of WHERE user talked to him.
 *
 * Redis Key Schema:
 *   alfred:ctx:{userId}:latest     — latest interaction summary (STRING, JSON)
 *   alfred:ctx:{userId}:history    — recent cross-instance summaries (LIST, max 20)
 *   alfred:ctx:{userId}:tasks      — active tasks/projects (STRING, JSON)
 *   alfred:ctx:{userId}:prefs      — user preferences learned across instances (STRING, JSON)
 */

const { getRedis } = require('./redis');
const logger = require('./logger');

const MAX_HISTORY = 20;
const LATEST_TTL = 86400 * 7;   // 7 days
const HISTORY_TTL = 86400 * 30; // 30 days

/**
 * Record an interaction from any Alfred instance.
 * Call this after every successful Alfred response.
 *
 * @param {string|number} userId - WHMCS client ID or user identifier
 * @param {object} opts
 * @param {string} opts.source - 'widget' | 'ide' | 'voice' | 'phone'
 * @param {string} opts.userMessage - what the user said (truncated)
 * @param {string} opts.alfredResponse - what Alfred said (truncated)
 * @param {string} [opts.model] - model used
 * @param {string} [opts.agent] - agent persona (alfred, nova, sage, etc.)
 * @param {string} [opts.pageUrl] - page/workspace context
 * @param {string} [opts.convId] - conversation ID if available
 */
async function recordInteraction(userId, opts = {}) {
  if (!userId) return;
  try {
    const redis = getRedis();
    const now = new Date().toISOString();
    const prefix = `alfred:ctx:${userId}`;

    // Truncate messages for storage efficiency
    const userMsg = (opts.userMessage || '').slice(0, 500);
    const alfredMsg = (opts.alfredResponse || '').slice(0, 500);

    // Update latest interaction
    const latest = JSON.stringify({
      source: opts.source || 'unknown',
      agent: opts.agent || 'alfred',
      model: opts.model || '',
      userMessage: userMsg,
      alfredResponse: alfredMsg,
      pageUrl: opts.pageUrl || '',
      convId: opts.convId || '',
      timestamp: now,
    });
    await redis.set(`${prefix}:latest`, latest, 'EX', LATEST_TTL);

    // Push to history list (cross-instance breadcrumb trail)
    const summary = JSON.stringify({
      source: opts.source || 'unknown',
      agent: opts.agent || 'alfred',
      user: userMsg.slice(0, 200),
      alfred: alfredMsg.slice(0, 200),
      ts: now,
    });
    await redis.lpush(`${prefix}:history`, summary);
    await redis.ltrim(`${prefix}:history`, 0, MAX_HISTORY - 1);
    await redis.expire(`${prefix}:history`, HISTORY_TTL);

  } catch (err) {
    logger.error('alfredMemory.recordInteraction error:', err.message);
  }
}

/**
 * Get cross-instance context for injecting into system prompts.
 * Returns a formatted string summarizing recent activity across all Alfred instances.
 *
 * @param {string|number} userId
 * @param {string} [currentSource] - which instance is asking (to exclude self)
 * @returns {string} formatted context block or empty string
 */
async function getCrossContext(userId, currentSource) {
  if (!userId) return '';
  try {
    const redis = getRedis();
    const prefix = `alfred:ctx:${userId}`;

    // Get latest interaction
    const latestRaw = await redis.get(`${prefix}:latest`);
    // Get recent history
    const historyRaw = await redis.lrange(`${prefix}:history`, 0, 9);
    // Get active tasks
    const tasksRaw = await redis.get(`${prefix}:tasks`);
    // Get user prefs
    const prefsRaw = await redis.get(`${prefix}:prefs`);

    const parts = [];

    // Cross-instance recent activity
    if (historyRaw && historyRaw.length > 0) {
      const entries = historyRaw
        .map(r => { try { return JSON.parse(r); } catch { return null; } })
        .filter(e => e && (!currentSource || e.source !== currentSource));

      if (entries.length > 0) {
        parts.push('## Recent Activity Across All Channels');
        for (const e of entries.slice(0, 5)) {
          const src = { widget: '🌐 Website Chat', ide: '💻 GoCodeMe IDE', voice: '🎙️ Voice', phone: '📞 Phone' }[e.source] || e.source;
          const ago = getTimeAgo(e.ts);
          parts.push(`- [${src}] ${ago}: User said: "${e.user}" → Alfred said: "${e.alfred}"`);
        }
      }
    }

    // Active tasks
    if (tasksRaw) {
      try {
        const tasks = JSON.parse(tasksRaw);
        if (tasks.items && tasks.items.length > 0) {
          parts.push('## Active Tasks');
          for (const t of tasks.items) {
            parts.push(`- ${t.status === 'done' ? '✅' : '🔄'} ${t.title}`);
          }
        }
      } catch {}
    }

    // User preferences
    if (prefsRaw) {
      try {
        const prefs = JSON.parse(prefsRaw);
        if (Object.keys(prefs).length > 0) {
          parts.push('## User Preferences');
          for (const [k, v] of Object.entries(prefs)) {
            parts.push(`- ${k}: ${v}`);
          }
        }
      } catch {}
    }

    if (parts.length === 0) return '';

    return '\n\n<cross_instance_context>\n' + parts.join('\n') + '\n</cross_instance_context>\n';
  } catch (err) {
    logger.error('alfredMemory.getCrossContext error:', err.message);
    return '';
  }
}

/**
 * Update active tasks for a user (used by session summarization).
 */
async function setTasks(userId, tasks) {
  if (!userId) return;
  try {
    const redis = getRedis();
    await redis.set(`alfred:ctx:${userId}:tasks`, JSON.stringify(tasks), 'EX', HISTORY_TTL);
  } catch (err) {
    logger.error('alfredMemory.setTasks error:', err.message);
  }
}

/**
 * Update learned user preferences.
 */
async function updatePrefs(userId, key, value) {
  if (!userId || !key) return;
  try {
    const redis = getRedis();
    const raw = await redis.get(`alfred:ctx:${userId}:prefs`);
    const prefs = raw ? JSON.parse(raw) : {};
    prefs[key] = value;
    await redis.set(`alfred:ctx:${userId}:prefs`, JSON.stringify(prefs), 'EX', HISTORY_TTL);
  } catch (err) {
    logger.error('alfredMemory.updatePrefs error:', err.message);
  }
}

/**
 * Get time-ago string from ISO timestamp.
 */
function getTimeAgo(isoStr) {
  const diff = Date.now() - new Date(isoStr).getTime();
  const mins = Math.floor(diff / 60000);
  if (mins < 1) return 'just now';
  if (mins < 60) return `${mins}m ago`;
  const hrs = Math.floor(mins / 60);
  if (hrs < 24) return `${hrs}h ago`;
  const days = Math.floor(hrs / 24);
  return `${days}d ago`;
}

module.exports = {
  recordInteraction,
  getCrossContext,
  setTasks,
  updatePrefs,
};
