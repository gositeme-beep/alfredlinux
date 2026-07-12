/**
 * schedulerEngine.js — CLOCKWORK: Autonomous Cron & Webhook Trigger Engine
 *
 * Lets users create recurring tasks that execute Alfred playbooks automatically.
 * Uses node-cron for scheduling and Redis for state persistence.
 *
 * Redis keys:
 *   scheduler:{daUsername}:tasks      — Hash of all task definitions
 *   scheduler:{daUsername}:log:{id}   — List of execution logs (last 50)
 *
 * Each task runs a playbook by name with given parameters.
 * The scheduler service must be started once (startScheduler) when the
 * MCP server boots, or lazily on first scheduled task creation.
 */

import cron from 'node-cron';
import Redis from 'ioredis';
import { randomUUID } from 'node:crypto';

// ── Redis connection (lazy singleton) ───────────────────────────────────────
let redis = null;
function getRedis() {
  if (!redis) {
    redis = new Redis(process.env.REDIS_URL || 'redis://localhost:6379', {
      lazyConnect: true,
      maxRetriesPerRequest: 3,
    });
    redis.connect().catch(() => {});
  }
  return redis;
}

// ── In-memory cron job handles ──────────────────────────────────────────────
// Map<string, cron.ScheduledTask> — key is `${daUsername}:${taskId}`
const activeJobs = new Map();

// ── Playbook executor callback ──────────────────────────────────────────────
// Set this from the outside so the scheduler can trigger playbook runs.
let _playbookExecutor = null;

/**
 * Register a callback that executes a playbook.
 * The callback receives (daUsername, playbookName, parameters) and returns a result string.
 *
 * @param {Function} executor
 */
export function setPlaybookExecutor(executor) {
  _playbookExecutor = executor;
}

// ═══════════════════════════════════════════════════════════════════
// TASK MANAGEMENT
// ═══════════════════════════════════════════════════════════════════

/**
 * Create a new scheduled task.
 *
 * @param {string} daUsername
 * @param {object} opts
 * @param {string} opts.name — human-readable name
 * @param {string} opts.cron_expression — cron expression (e.g. "0 3 * * *")
 * @param {string} opts.playbook — name of the playbook to run
 * @param {object} [opts.parameters] — playbook parameters
 * @param {boolean} [opts.enabled=true]
 * @returns {Promise<{id: string, message: string}>}
 */
export async function createTask(daUsername, opts) {
  const r = getRedis();
  const id = `task_${randomUUID().slice(0, 8)}`;

  // Validate cron expression
  if (!cron.validate(opts.cron_expression)) {
    throw new Error(`Invalid cron expression: "${opts.cron_expression}". Use standard 5-field cron syntax (minute hour day month weekday).`);
  }

  // Check max tasks per user
  const existing = await r.hgetall(`scheduler:${daUsername}:tasks`);
  if (Object.keys(existing).length >= 50) {
    throw new Error('Maximum 50 scheduled tasks per user. Delete some before creating new ones.');
  }

  const task = {
    id,
    name: opts.name,
    cron_expression: opts.cron_expression,
    playbook: opts.playbook,
    parameters: opts.parameters || {},
    enabled: opts.enabled !== false,
    created: new Date().toISOString(),
    last_run: null,
    last_status: null,
    run_count: 0,
  };

  // Save to Redis
  await r.hset(`scheduler:${daUsername}:tasks`, id, JSON.stringify(task));

  // Schedule if enabled
  if (task.enabled) {
    scheduleJob(daUsername, task);
  }

  return {
    id,
    message: `Scheduled task "${opts.name}" created. Cron: ${opts.cron_expression}. Playbook: ${opts.playbook}. ${task.enabled ? 'Active.' : 'Disabled.'}`,
  };
}

/**
 * List all scheduled tasks for a user.
 *
 * @param {string} daUsername
 * @returns {Promise<Array>}
 */
export async function listTasks(daUsername) {
  const r = getRedis();
  const raw = await r.hgetall(`scheduler:${daUsername}:tasks`);

  const tasks = Object.values(raw).map(v => {
    const t = JSON.parse(v);
    t.is_active = activeJobs.has(`${daUsername}:${t.id}`);
    return t;
  });

  // Sort by created date
  tasks.sort((a, b) => (a.created || '').localeCompare(b.created || ''));
  return tasks;
}

/**
 * Delete a scheduled task.
 *
 * @param {string} daUsername
 * @param {string} taskId — task ID or name
 * @returns {Promise<{deleted: boolean, message: string}>}
 */
export async function deleteTask(daUsername, taskId) {
  const r = getRedis();

  // Try by ID first, then by name
  let targetId = taskId;
  const raw = await r.hget(`scheduler:${daUsername}:tasks`, taskId);
  if (!raw) {
    // Search by name
    const all = await r.hgetall(`scheduler:${daUsername}:tasks`);
    for (const [id, val] of Object.entries(all)) {
      const t = JSON.parse(val);
      if (t.name.toLowerCase() === taskId.toLowerCase()) {
        targetId = id;
        break;
      }
    }
  }

  const deleted = await r.hdel(`scheduler:${daUsername}:tasks`, targetId);

  // Stop the cron job
  const jobKey = `${daUsername}:${targetId}`;
  if (activeJobs.has(jobKey)) {
    activeJobs.get(jobKey).stop();
    activeJobs.delete(jobKey);
  }

  // Clean up logs
  await r.del(`scheduler:${daUsername}:log:${targetId}`);

  if (deleted) {
    return { deleted: true, message: `Task "${taskId}" deleted and unscheduled.` };
  }
  return { deleted: false, message: `Task "${taskId}" not found.` };
}

/**
 * Get execution logs for a task.
 *
 * @param {string} daUsername
 * @param {string} taskId
 * @param {number} [limit=20]
 * @returns {Promise<Array>}
 */
export async function getTaskLogs(daUsername, taskId, limit = 20) {
  const r = getRedis();
  const logs = await r.lrange(`scheduler:${daUsername}:log:${taskId}`, 0, limit - 1);
  return logs.map(l => JSON.parse(l));
}

// ═══════════════════════════════════════════════════════════════════
// INTERNAL SCHEDULING
// ═══════════════════════════════════════════════════════════════════

/**
 * Schedule a cron job for a task.
 */
function scheduleJob(daUsername, task) {
  const jobKey = `${daUsername}:${task.id}`;

  // Stop existing job if any
  if (activeJobs.has(jobKey)) {
    activeJobs.get(jobKey).stop();
  }

  const job = cron.schedule(task.cron_expression, async () => {
    await executeTask(daUsername, task);
  }, { scheduled: true });

  activeJobs.set(jobKey, job);
}

/**
 * Execute a scheduled task (called by cron).
 */
async function executeTask(daUsername, task) {
  const r = getRedis();
  const start = Date.now();
  let status = 'success';
  let output = '';

  try {
    if (_playbookExecutor) {
      output = await _playbookExecutor(daUsername, task.playbook, task.parameters);
    } else {
      output = `[Scheduler] Playbook executor not registered. Task "${task.name}" would run playbook "${task.playbook}".`;
      status = 'skipped';
    }
  } catch (err) {
    status = 'error';
    output = err.message;
  }

  const elapsed = Date.now() - start;

  // Update task stats
  task.last_run = new Date().toISOString();
  task.last_status = status;
  task.run_count = (task.run_count || 0) + 1;
  await r.hset(`scheduler:${daUsername}:tasks`, task.id, JSON.stringify(task));

  // Log the execution
  const logEntry = {
    timestamp: task.last_run,
    status,
    elapsed_ms: elapsed,
    output: (output || '').slice(0, 5000), // truncate logs
  };
  await r.lpush(`scheduler:${daUsername}:log:${task.id}`, JSON.stringify(logEntry));
  await r.ltrim(`scheduler:${daUsername}:log:${task.id}`, 0, 49); // keep last 50 logs
}

/**
 * Boot the scheduler — load all active tasks from Redis and schedule them.
 * Call this when the MCP server starts.
 *
 * @returns {Promise<number>} — number of tasks activated
 */
export async function bootScheduler() {
  const r = getRedis();

  // Scan for all scheduler keys
  let cursor = '0';
  let totalActivated = 0;

  do {
    const [nextCursor, keys] = await r.scan(cursor, 'MATCH', 'scheduler:*:tasks', 'COUNT', 100);
    cursor = nextCursor;

    for (const key of keys) {
      const username = key.split(':')[1]; // scheduler:{username}:tasks
      const tasks = await r.hgetall(key);

      for (const val of Object.values(tasks)) {
        const task = JSON.parse(val);
        if (task.enabled) {
          scheduleJob(username, task);
          totalActivated++;
        }
      }
    }
  } while (cursor !== '0');

  return totalActivated;
}

/**
 * Get scheduler status summary.
 *
 * @returns {object}
 */
export function getSchedulerStatus() {
  return {
    active_jobs: activeJobs.size,
    has_executor: !!_playbookExecutor,
  };
}
