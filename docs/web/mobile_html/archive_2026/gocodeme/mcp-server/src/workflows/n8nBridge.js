/**
 * n8nBridge.js — n8n Workflow Automation Bridge
 *
 * REST API client for the n8n workflow automation platform.
 * Allows Alfred to create, execute, list, and manage n8n workflows.
 *
 * n8n runs as a separate process (PM2) on port 5678.
 * Communication is via n8n's REST API using API key auth.
 *
 * Setup:
 *   - n8n runs on http://localhost:5678
 *   - N8N_API_KEY env var required for authentication
 */

import axios from 'axios';
import { getTemplate, listTemplates } from './workflowTemplates.js';

const N8N_BASE = process.env.N8N_URL || 'http://localhost:5678';
const N8N_API_KEY = process.env.N8N_API_KEY || '';

/**
 * Get axios config with auth headers.
 */
function apiConfig(timeout = 30000) {
  return {
    timeout,
    headers: {
      'Content-Type': 'application/json',
      ...(N8N_API_KEY ? { 'X-N8N-API-KEY': N8N_API_KEY } : {}),
    },
  };
}

/**
 * Check if n8n is running.
 * @returns {Promise<boolean>}
 */
export async function isN8nRunning() {
  try {
    await axios.get(`${N8N_BASE}/healthz`, { timeout: 5000 });
    return true;
  } catch {
    return false;
  }
}

/**
 * Create a workflow.
 *
 * @param {object} opts
 * @param {string} [opts.templateId] — use a pre-built template
 * @param {string} [opts.name] — workflow name
 * @param {object} [opts.definition] — raw n8n workflow JSON
 * @returns {Promise<object>}
 */
export async function workflowCreate(opts) {
  const { templateId, name, definition } = opts;

  let workflow;
  if (templateId) {
    const template = getTemplate(templateId);
    if (!template) {
      return {
        status: 'error',
        error: `Unknown template: ${templateId}`,
        availableTemplates: listTemplates(),
      };
    }
    workflow = {
      name: name || template.name,
      nodes: template.nodes,
      connections: template.connections,
      active: false,
      settings: {},
    };
  } else if (definition) {
    workflow = {
      name: name || definition.name || 'Alfred Workflow',
      ...definition,
    };
  } else {
    return {
      status: 'error',
      error: 'Provide either templateId or definition',
      availableTemplates: listTemplates(),
    };
  }

  const start = Date.now();

  try {
    const resp = await axios.post(`${N8N_BASE}/api/v1/workflows`, workflow, apiConfig());
    return {
      status: 'created',
      workflowId: resp.data.id,
      name: resp.data.name,
      active: resp.data.active,
      nodeCount: resp.data.nodes?.length || 0,
      timing: Date.now() - start,
    };
  } catch (err) {
    return {
      status: 'error',
      error: err.response?.data?.message || err.message,
      hint: await isN8nRunning() ? 'Check N8N_API_KEY' : 'n8n is not running. Start it with: pm2 start "n8n start" --name n8n',
      timing: Date.now() - start,
    };
  }
}

/**
 * Execute (trigger) a workflow.
 *
 * @param {object} opts
 * @param {string|number} opts.workflowId — workflow ID to execute
 * @param {object} [opts.data={}] — input data for the workflow
 * @returns {Promise<object>}
 */
export async function workflowExecute(opts) {
  const { workflowId, data = {} } = opts;
  if (!workflowId) throw new Error('workflowId is required');

  const start = Date.now();

  try {
    // First activate the workflow if needed
    await axios.patch(`${N8N_BASE}/api/v1/workflows/${workflowId}`, { active: true }, apiConfig());

    // Execute via webhook or test run
    const resp = await axios.post(
      `${N8N_BASE}/api/v1/workflows/${workflowId}/run`,
      { data },
      apiConfig(60000),
    );

    return {
      status: 'executed',
      workflowId,
      executionId: resp.data.data?.executionId || resp.data.id,
      result: resp.data.data || resp.data,
      timing: Date.now() - start,
    };
  } catch (err) {
    return {
      status: 'error',
      workflowId,
      error: err.response?.data?.message || err.message,
      timing: Date.now() - start,
    };
  }
}

/**
 * List all workflows.
 *
 * @param {object} [opts]
 * @param {boolean} [opts.activeOnly=false]
 * @returns {Promise<object>}
 */
export async function workflowList(opts = {}) {
  const { activeOnly = false } = opts;
  const start = Date.now();

  try {
    const resp = await axios.get(`${N8N_BASE}/api/v1/workflows`, apiConfig());
    let workflows = resp.data.data || resp.data || [];

    if (activeOnly) {
      workflows = workflows.filter(w => w.active);
    }

    return {
      status: 'success',
      workflows: workflows.map(w => ({
        id: w.id,
        name: w.name,
        active: w.active,
        createdAt: w.createdAt,
        updatedAt: w.updatedAt,
        nodeCount: w.nodes?.length || 0,
      })),
      total: workflows.length,
      templates: listTemplates(),
      timing: Date.now() - start,
    };
  } catch (err) {
    return {
      status: 'error',
      error: err.response?.data?.message || err.message,
      hint: await isN8nRunning() ? 'Check API key' : 'n8n is not running',
      templates: listTemplates(),
      timing: Date.now() - start,
    };
  }
}

/**
 * Get workflow execution status/history.
 *
 * @param {object} opts
 * @param {string|number} [opts.workflowId] — filter by workflow
 * @param {number} [opts.limit=10] — max results
 * @returns {Promise<object>}
 */
export async function workflowStatus(opts = {}) {
  const { workflowId, limit = 10 } = opts;
  const start = Date.now();

  try {
    let url = `${N8N_BASE}/api/v1/executions?limit=${limit}`;
    if (workflowId) url += `&workflowId=${workflowId}`;

    const resp = await axios.get(url, apiConfig());
    const executions = resp.data.data || resp.data || [];

    return {
      status: 'success',
      executions: executions.map(e => ({
        id: e.id,
        workflowId: e.workflowId,
        workflowName: e.workflowName || e.workflowData?.name,
        status: e.finished ? (e.stoppedAt ? 'success' : 'running') : 'running',
        startedAt: e.startedAt,
        stoppedAt: e.stoppedAt,
        mode: e.mode,
      })),
      total: executions.length,
      timing: Date.now() - start,
    };
  } catch (err) {
    return {
      status: 'error',
      error: err.response?.data?.message || err.message,
      timing: Date.now() - start,
    };
  }
}
