'use strict';

/**
 * Agent Bridge
 *
 * Sends a conversation turn to the GoCodeMe OpenHands agent and returns the
 * assistant's reply.
 *
 * The agent exposes a simple HTTP API on AGENT_URL (default port 4001).
 * We call POST /api/agent/chat with the full conversation history and receive
 * back { reply: string, tokensUsed: { input, output } }.
 *
 * If the per-customer agent is not running, we fall back to calling Claude
 * directly via the middleware's internal token-gated endpoint.
 *
 * Token reporting:
 *   After every successful reply we call POST /api/tokens/report on the
 *   middleware so usage is tracked regardless of which path was taken.
 */

const axios = require('axios');
const jwt   = require('jsonwebtoken');
const config = require('../config');
const logger = require('../logger');

const MIDDLEWARE = config.middleware.url;

/**
 * Get the agent URL for a specific customer.
 * Sessions are stored in middleware Redis as launch:sessions:<daUsername>.
 * We ask the middleware launch/sessions endpoint for the agent port.
 *
 * Returns the agent base URL or null if no running session.
 */
async function resolveAgentUrl(daUsername, sessionJwt) {
  try {
    const resp = await axios.get(`${MIDDLEWARE}/api/launch/sessions`, {
      headers: { Authorization: `Bearer ${sessionJwt}` },
      timeout: 3000,
    });
    const sessions = resp.data?.sessions || [];
    const agentSession = sessions.find(s => s.service === 'agent');
    if (agentSession?.url) return agentSession.url;
  } catch { /* agent not running — fall through to direct Claude path */ }
  return null;
}

/**
 * Send a message to the agent (or direct Claude fallback).
 *
 * @param {object} opts
 * @param {string}   opts.daUsername
 * @param {number}   opts.whmcsClientId
 * @param {string}   opts.plan
 * @param {string}   opts.userMessage      The current user message
 * @param {Array}    opts.history           [{role,content}…] prior turns
 * @param {string}   opts.sessionJwt        GoCodeMe session JWT for this user
 * @returns {Promise<{ reply: string, tokensUsed: { input: number, output: number } }>}
 */
async function sendToAgent({ daUsername, whmcsClientId, plan, userMessage, history, sessionJwt }) {
  // ── 1. Check token allowance ─────────────────────────────────────────────
  try {
    const usageResp = await axios.get(`${MIDDLEWARE}/api/tokens/usage`, {
      headers: { Authorization: `Bearer ${sessionJwt}` },
      timeout: 5000,
    });
    const usage = usageResp.data?.usage;
    if (usage && !usage.withinLimit) {
      return {
        reply: `⚠️ You've used all your GoCodeMe tokens for this month (${usage.used.toLocaleString()} / ${usage.limit.toLocaleString()}). Purchase a top-up pack in your client area to continue.`,
        tokensUsed: { input: 0, output: 0 },
      };
    }
  } catch (err) {
    logger.warn(`agent bridge: token check failed: ${err.message}`);
  }

  // ── 2. Try the live per-customer agent ───────────────────────────────────
  const agentUrl = await resolveAgentUrl(daUsername, sessionJwt);

  const messages = [
    ...history.map(h => ({ role: h.role, content: h.content })),
    { role: 'user', content: userMessage },
  ];

  let reply = null;
  let tokensUsed = { input: 0, output: 0 };

  if (agentUrl) {
    try {
      const resp = await axios.post(`${agentUrl}/api/agent/chat`, {
        messages,
        daUsername,
      }, {
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${sessionJwt}`,
        },
        timeout: 60000,  // agent can take a while
      });
      reply      = resp.data?.reply;
      tokensUsed = resp.data?.tokensUsed || { input: 0, output: 0 };
      logger.info(`agent bridge: agent replied for ${daUsername} (${tokensUsed.input + tokensUsed.output} tokens)`);
    } catch (err) {
      logger.warn(`agent bridge: agent at ${agentUrl} failed: ${err.message} — falling back to direct Claude`);
    }
  }

  // ── 3. Direct Claude fallback via middleware ─────────────────────────────
  if (!reply) {
    try {
      const resp = await axios.post(`${MIDDLEWARE}/api/claude/chat`, {
        messages,
        daUsername,
        whmcsClientId,
      }, {
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${sessionJwt}`,
        },
        timeout: 60000,
      });
      reply      = resp.data?.reply;
      tokensUsed = resp.data?.tokensUsed || { input: 0, output: 0 };
    } catch (err) {
      logger.error(`agent bridge: direct Claude fallback also failed: ${err.message}`);
      reply = '⚠️ GoCodeMe Agent is temporarily unavailable. Please try again in a moment or launch your IDE to use the full agent.';
      tokensUsed = { input: 0, output: 0 };
    }
  }

  // ── 4. Report token usage to middleware ──────────────────────────────────
  if (tokensUsed.input + tokensUsed.output > 0) {
    axios.post(`${MIDDLEWARE}/api/tokens/report`, {
      inputTokens: tokensUsed.input,
      outputTokens: tokensUsed.output,
    }, {
      headers: { Authorization: `Bearer ${sessionJwt}` },
      timeout: 5000,
    }).catch(err => logger.warn(`agent bridge: token report failed: ${err.message}`));
  }

  return { reply: reply || '(no reply)', tokensUsed };
}

module.exports = { sendToAgent, resolveAgentUrl };
