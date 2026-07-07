/**
 * a2aServer.js — A2A Protocol HTTP Endpoints
 *
 * Implements Google's Agent-to-Agent Protocol server endpoints.
 * Mount this as an Express router on the OpenClaw server.
 *
 * Endpoints:
 *   GET  /.well-known/agent.json  — Agent Card
 *   POST /a2a/tasks/send          — Receive a task from another agent
 *   GET  /a2a/tasks/:id           — Get task status
 *   POST /a2a/tasks/:id/cancel    — Cancel a task
 *   GET  /a2a/tasks               — List recent tasks
 */

'use strict';

const express = require('express');
const { generateAgentCard } = require('./agentCard');
const { createTask, getTask, updateTaskState, listTasks, cancelTask, STATES } = require('./taskManager');
const { sendToAgent } = require('../agent/bridge');
const logger = require('../logger');

const router = express.Router();

// ── Agent Card ──────────────────────────────────────────────────────────────
router.get('/.well-known/agent.json', (req, res) => {
  res.json(generateAgentCard());
});

// ── Receive Task ────────────────────────────────────────────────────────────
router.post('/a2a/tasks/send', async (req, res) => {
  try {
    const { message, metadata = {} } = req.body;

    if (!message || !message.parts?.length) {
      return res.status(400).json({ error: 'message with parts is required' });
    }

    const fromAgent = req.headers['x-agent-name'] || req.headers['user-agent'] || 'unknown';
    const textParts = message.parts.filter(p => p.type === 'text').map(p => p.text);
    const userMessage = textParts.join('\n');

    // Create task in submitted state
    const task = await createTask({ fromAgent, message, metadata });

    // Start processing asynchronously
    processTask(task.id, userMessage, req.headers.authorization).catch(err => {
      logger.error(`A2A task ${task.id} processing failed: ${err.message}`);
    });

    // Return immediately with task ID
    res.status(200).json(task);
  } catch (err) {
    logger.error(`A2A /tasks/send error: ${err.message}`);
    res.status(500).json({ error: err.message });
  }
});

// ── Get Task Status ─────────────────────────────────────────────────────────
router.get('/a2a/tasks/:id', async (req, res) => {
  const task = await getTask(req.params.id);
  if (!task) return res.status(404).json({ error: 'Task not found' });
  res.json(task);
});

// ── Cancel Task ─────────────────────────────────────────────────────────────
router.post('/a2a/tasks/:id/cancel', async (req, res) => {
  try {
    const task = await cancelTask(req.params.id);
    res.json(task);
  } catch (err) {
    res.status(404).json({ error: err.message });
  }
});

// ── List Tasks ──────────────────────────────────────────────────────────────
router.get('/a2a/tasks', async (req, res) => {
  const limit = parseInt(req.query.limit) || 50;
  const tasks = await listTasks(limit);
  res.json({ tasks, total: tasks.length });
});

// ── Async Task Processor ────────────────────────────────────────────────────
async function processTask(taskId, userMessage, authHeader) {
  // Move to working state
  await updateTaskState(taskId, STATES.WORKING, 'Processing task...');

  try {
    // Use the agent bridge to process the task
    const result = await sendToAgent({
      daUsername: 'a2a-agent',
      whmcsClientId: 0,
      plan: 'a2a',
      userMessage,
      history: [],
      sessionJwt: authHeader?.replace('Bearer ', '') || '',
    });

    // Complete the task with the result
    await updateTaskState(taskId, STATES.COMPLETED, null, [
      {
        type: 'text',
        parts: [{ type: 'text', text: result.reply }],
      },
    ]);
  } catch (err) {
    await updateTaskState(taskId, STATES.FAILED, `Processing failed: ${err.message}`);
  }
}

module.exports = router;
