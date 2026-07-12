/**
 * taskManager.js — A2A Task State Machine
 *
 * Manages the lifecycle of A2A tasks:
 *   submitted → working → completed | failed | canceled
 *
 * Tasks are persisted in Redis for durability across restarts.
 */

'use strict';

const Redis = require('ioredis');
const { v4: uuid } = require('uuid');
const logger = require('../logger');

const redis = new Redis({ host: '127.0.0.1', port: 6379, maxRetriesPerRequest: 3, lazyConnect: true });
redis.connect().catch(() => {});

const TASK_PREFIX = 'a2a:task:';
const TASK_LIST = 'a2a:tasks';
const TASK_TTL = 86400; // 24 hours

/**
 * Task states per A2A spec.
 */
const STATES = {
  SUBMITTED: 'submitted',
  WORKING: 'working',
  INPUT_REQUIRED: 'input-required',
  COMPLETED: 'completed',
  FAILED: 'failed',
  CANCELED: 'canceled',
};

/**
 * Create a new A2A task.
 * @param {object} opts
 * @param {string} opts.fromAgent — requesting agent name/URL
 * @param {object} opts.message — the task message
 * @param {object} [opts.metadata={}] — extra metadata
 * @returns {Promise<object>} — the created task
 */
async function createTask({ fromAgent, message, metadata = {} }) {
  const task = {
    id: uuid(),
    status: {
      state: STATES.SUBMITTED,
      timestamp: new Date().toISOString(),
    },
    fromAgent,
    message,
    metadata,
    history: [
      {
        state: STATES.SUBMITTED,
        timestamp: new Date().toISOString(),
      },
    ],
    artifacts: [],
    createdAt: new Date().toISOString(),
    updatedAt: new Date().toISOString(),
  };

  await redis.setex(TASK_PREFIX + task.id, TASK_TTL, JSON.stringify(task));
  await redis.lpush(TASK_LIST, task.id);
  await redis.ltrim(TASK_LIST, 0, 499); // keep last 500

  logger.info(`A2A task created: ${task.id} from ${fromAgent}`);
  return task;
}

/**
 * Get a task by ID.
 */
async function getTask(taskId) {
  const raw = await redis.get(TASK_PREFIX + taskId);
  if (!raw) return null;
  return JSON.parse(raw);
}

/**
 * Update task state.
 */
async function updateTaskState(taskId, newState, message = null, artifacts = []) {
  const task = await getTask(taskId);
  if (!task) throw new Error(`Task ${taskId} not found`);

  task.status = {
    state: newState,
    timestamp: new Date().toISOString(),
    ...(message ? { message } : {}),
  };
  task.history.push({
    state: newState,
    timestamp: new Date().toISOString(),
    ...(message ? { message } : {}),
  });
  if (artifacts.length > 0) {
    task.artifacts.push(...artifacts);
  }
  task.updatedAt = new Date().toISOString();

  await redis.setex(TASK_PREFIX + task.id, TASK_TTL, JSON.stringify(task));
  logger.info(`A2A task ${taskId} → ${newState}`);
  return task;
}

/**
 * List recent tasks.
 */
async function listTasks(limit = 50) {
  const ids = await redis.lrange(TASK_LIST, 0, limit - 1);
  const tasks = [];
  for (const id of ids) {
    const task = await getTask(id);
    if (task) {
      tasks.push({
        id: task.id,
        state: task.status.state,
        fromAgent: task.fromAgent,
        createdAt: task.createdAt,
        updatedAt: task.updatedAt,
      });
    }
  }
  return tasks;
}

/**
 * Cancel a task.
 */
async function cancelTask(taskId) {
  return updateTaskState(taskId, STATES.CANCELED, 'Task canceled by request');
}

module.exports = {
  STATES,
  createTask,
  getTask,
  updateTaskState,
  listTasks,
  cancelTask,
};
