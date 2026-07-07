/**
 * a2aClient.js — Outbound A2A Client
 *
 * Connects to remote A2A agents to delegate tasks.
 * Discovers agent capabilities via /.well-known/agent.json,
 * sends tasks, and polls for results.
 */

'use strict';

const axios = require('axios');
const logger = require('../logger');

/**
 * Discover a remote A2A agent by fetching its Agent Card.
 * @param {string} agentUrl — base URL of the agent
 * @returns {Promise<object>} — the Agent Card
 */
async function discoverAgent(agentUrl) {
  const cleanUrl = agentUrl.replace(/\/$/, '');
  const cardUrl = `${cleanUrl}/.well-known/agent.json`;

  try {
    const resp = await axios.get(cardUrl, { timeout: 10000 });
    return {
      status: 'discovered',
      url: cleanUrl,
      card: resp.data,
      skills: (resp.data.skills || []).map(s => ({ id: s.id, name: s.name, description: s.description })),
    };
  } catch (err) {
    return {
      status: 'not_found',
      url: cleanUrl,
      error: `Could not fetch Agent Card: ${err.message}`,
    };
  }
}

/**
 * Send a task to a remote A2A agent.
 * @param {object} opts
 * @param {string} opts.agentUrl — base URL of the remote agent
 * @param {string} opts.message — task message/instruction
 * @param {object} [opts.metadata={}]
 * @param {string} [opts.authToken] — bearer token for auth
 * @returns {Promise<object>}
 */
async function sendTask({ agentUrl, message, metadata = {}, authToken }) {
  const cleanUrl = agentUrl.replace(/\/$/, '');

  try {
    const resp = await axios.post(`${cleanUrl}/a2a/tasks/send`, {
      message: {
        role: 'user',
        parts: [{ type: 'text', text: message }],
      },
      metadata,
    }, {
      timeout: 60000,
      headers: {
        'Content-Type': 'application/json',
        ...(authToken ? { Authorization: `Bearer ${authToken}` } : {}),
      },
    });

    return {
      status: 'sent',
      taskId: resp.data.id,
      state: resp.data.status?.state,
      agentUrl: cleanUrl,
    };
  } catch (err) {
    logger.error(`A2A send task failed to ${cleanUrl}: ${err.message}`);
    return {
      status: 'error',
      agentUrl: cleanUrl,
      error: err.response?.data?.message || err.message,
    };
  }
}

/**
 * Get task status from a remote agent.
 */
async function getRemoteTaskStatus({ agentUrl, taskId, authToken }) {
  const cleanUrl = agentUrl.replace(/\/$/, '');

  try {
    const resp = await axios.get(`${cleanUrl}/a2a/tasks/${taskId}`, {
      timeout: 10000,
      headers: authToken ? { Authorization: `Bearer ${authToken}` } : {},
    });

    return {
      status: 'success',
      task: resp.data,
    };
  } catch (err) {
    return {
      status: 'error',
      error: err.response?.data?.message || err.message,
    };
  }
}

module.exports = {
  discoverAgent,
  sendTask,
  getRemoteTaskStatus,
};
