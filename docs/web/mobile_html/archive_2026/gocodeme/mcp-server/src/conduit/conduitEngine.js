/**
 * conduitEngine.js — CONDUIT: API & Integration Gateway Engine
 *
 * Manages external API connections, webhooks, data pipelines, and integration
 * workflows. Acts as the universal bridge between the IDE and external services.
 *
 * Capabilities:
 *  - Register and manage API connections (REST, GraphQL, WebSocket)
 *  - Webhook management (create, list, test, delete)
 *  - Data transformation pipelines
 *  - Rate limit tracking per API
 *  - API health monitoring
 *  - Request/response logging
 */

import { randomUUID } from 'node:crypto';
import fs from 'node:fs/promises';
import path from 'node:path';

const CONDUIT_BASE = '/home/gositeme/.gocodeme/conduit';

async function ensureDir(dir) {
  await fs.mkdir(dir, { recursive: true });
}

async function loadJSON(file, fallback = {}) {
  try { return JSON.parse(await fs.readFile(file, 'utf8')); }
  catch { return fallback; }
}

async function saveJSON(file, data) {
  await ensureDir(path.dirname(file));
  await fs.writeFile(file, JSON.stringify(data, null, 2));
}

function registryPath(user) { return path.join(CONDUIT_BASE, user, 'apis.json'); }
function webhooksPath(user) { return path.join(CONDUIT_BASE, user, 'webhooks.json'); }
function pipelinesPath(user) { return path.join(CONDUIT_BASE, user, 'pipelines.json'); }
function logPath(user) { return path.join(CONDUIT_BASE, user, 'request_log.json'); }

// ── API Registry ────────────────────────────────────────────────────────────

export async function registerApi(user, name, config) {
  const reg = await loadJSON(registryPath(user), { apis: {} });
  const id = `api_${randomUUID().slice(0, 8)}`;
  reg.apis[id] = {
    id, name,
    base_url: config.base_url,
    auth_type: config.auth_type || 'none',  // none, bearer, api_key, basic, oauth2
    headers: config.headers || {},
    rate_limit: config.rate_limit || null,
    created: new Date().toISOString(),
    last_used: null,
    call_count: 0,
    status: 'active',
  };
  await saveJSON(registryPath(user), reg);
  return { id, message: `API "${name}" registered (${config.base_url}). ID: ${id}` };
}

export async function listApis(user) {
  const reg = await loadJSON(registryPath(user), { apis: {} });
  const apis = Object.values(reg.apis);
  if (apis.length === 0) return { apis: [], message: 'No APIs registered yet.' };
  return {
    apis: apis.map(a => ({
      id: a.id, name: a.name, base_url: a.base_url,
      auth_type: a.auth_type, status: a.status,
      call_count: a.call_count, last_used: a.last_used,
    })),
    message: `${apis.length} API(s) registered.`,
  };
}

export async function callApi(user, apiId, method, endpoint, body, headers) {
  const reg = await loadJSON(registryPath(user), { apis: {} });
  const api = reg.apis[apiId];
  if (!api) throw new Error(`API ${apiId} not found`);

  const url = `${api.base_url}${endpoint}`;
  const mergedHeaders = { ...api.headers, ...(headers || {}) };
  if (api.auth_type === 'bearer' && api.headers?.Authorization) {
    mergedHeaders.Authorization = api.headers.Authorization;
  }

  const opts = { method: method || 'GET', headers: mergedHeaders, signal: AbortSignal.timeout(30000) };
  if (body && method !== 'GET') opts.body = typeof body === 'string' ? body : JSON.stringify(body);
  if (body && !mergedHeaders['Content-Type']) mergedHeaders['Content-Type'] = 'application/json';

  const start = Date.now();
  const resp = await fetch(url, opts);
  const elapsed = Date.now() - start;
  const contentType = resp.headers.get('content-type') || '';
  let responseBody;
  if (contentType.includes('json')) responseBody = await resp.json();
  else responseBody = await resp.text();

  // Update stats
  api.call_count++;
  api.last_used = new Date().toISOString();
  await saveJSON(registryPath(user), reg);

  // Log request
  const log = await loadJSON(logPath(user), { requests: [] });
  log.requests.unshift({
    api_id: apiId, method, url, status: resp.status,
    elapsed_ms: elapsed, timestamp: new Date().toISOString(),
  });
  if (log.requests.length > 500) log.requests = log.requests.slice(0, 500);
  await saveJSON(logPath(user), log);

  return {
    status: resp.status,
    elapsed_ms: elapsed,
    headers: Object.fromEntries(resp.headers.entries()),
    body: responseBody,
  };
}

export async function removeApi(user, apiId) {
  const reg = await loadJSON(registryPath(user), { apis: {} });
  const api = reg.apis[apiId];
  if (!api) return { message: `API ${apiId} not found` };
  const name = api.name;
  delete reg.apis[apiId];
  await saveJSON(registryPath(user), reg);
  return { message: `API "${name}" removed.` };
}

// ── Webhook Management ──────────────────────────────────────────────────────

export async function createWebhook(user, config) {
  const hooks = await loadJSON(webhooksPath(user), { webhooks: {} });
  const id = `wh_${randomUUID().slice(0, 8)}`;
  hooks.webhooks[id] = {
    id,
    name: config.name || id,
    url: config.url,
    events: config.events || ['*'],
    secret: config.secret || randomUUID(),
    active: true,
    created: new Date().toISOString(),
    deliveries: 0,
    last_delivery: null,
  };
  await saveJSON(webhooksPath(user), hooks);
  return { id, secret: hooks.webhooks[id].secret, message: `Webhook "${config.name || id}" created.` };
}

export async function listWebhooks(user) {
  const hooks = await loadJSON(webhooksPath(user), { webhooks: {} });
  const list = Object.values(hooks.webhooks);
  return {
    webhooks: list.map(w => ({
      id: w.id, name: w.name, url: w.url,
      events: w.events, active: w.active,
      deliveries: w.deliveries, last_delivery: w.last_delivery,
    })),
    message: `${list.length} webhook(s).`,
  };
}

export async function testWebhook(user, webhookId) {
  const hooks = await loadJSON(webhooksPath(user), { webhooks: {} });
  const wh = hooks.webhooks[webhookId];
  if (!wh) throw new Error(`Webhook ${webhookId} not found`);

  const payload = { event: 'test', webhook_id: webhookId, timestamp: new Date().toISOString() };
  try {
    const resp = await fetch(wh.url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Webhook-Secret': wh.secret },
      body: JSON.stringify(payload),
      signal: AbortSignal.timeout(10000),
    });
    wh.deliveries++;
    wh.last_delivery = new Date().toISOString();
    await saveJSON(webhooksPath(user), hooks);
    return { status: resp.status, ok: resp.ok, message: `Test delivered to ${wh.url}: ${resp.status}` };
  } catch (err) {
    return { status: 'error', ok: false, message: `Webhook test failed: ${err.message}` };
  }
}

export async function deleteWebhook(user, webhookId) {
  const hooks = await loadJSON(webhooksPath(user), { webhooks: {} });
  if (!hooks.webhooks[webhookId]) return { message: `Webhook ${webhookId} not found.` };
  const name = hooks.webhooks[webhookId].name;
  delete hooks.webhooks[webhookId];
  await saveJSON(webhooksPath(user), hooks);
  return { message: `Webhook "${name}" deleted.` };
}

// ── Data Pipelines ──────────────────────────────────────────────────────────

export async function createPipeline(user, config) {
  const pipes = await loadJSON(pipelinesPath(user), { pipelines: {} });
  const id = `pipe_${randomUUID().slice(0, 8)}`;
  pipes.pipelines[id] = {
    id,
    name: config.name,
    source: config.source,       // { type: 'api'|'webhook'|'file', ref: '...' }
    transforms: config.transforms || [],  // [{ type: 'jq'|'map'|'filter', expr: '...' }]
    destination: config.destination,  // { type: 'file'|'api'|'database', ref: '...' }
    schedule: config.schedule || null,  // cron expression or null (on-demand)
    active: true,
    runs: 0,
    last_run: null,
    created: new Date().toISOString(),
  };
  await saveJSON(pipelinesPath(user), pipes);
  return { id, message: `Pipeline "${config.name}" created.` };
}

export async function listPipelines(user) {
  const pipes = await loadJSON(pipelinesPath(user), { pipelines: {} });
  const list = Object.values(pipes.pipelines);
  return {
    pipelines: list.map(p => ({
      id: p.id, name: p.name, source: p.source?.type,
      destination: p.destination?.type, active: p.active,
      runs: p.runs, last_run: p.last_run,
    })),
    message: `${list.length} pipeline(s).`,
  };
}

export async function runPipeline(user, pipelineId) {
  const pipes = await loadJSON(pipelinesPath(user), { pipelines: {} });
  const pipe = pipes.pipelines[pipelineId];
  if (!pipe) throw new Error(`Pipeline ${pipelineId} not found`);

  const startTime = Date.now();
  let data;

  // Source
  if (pipe.source.type === 'api') {
    const resp = await fetch(pipe.source.ref, { signal: AbortSignal.timeout(30000) });
    data = await resp.json();
  } else if (pipe.source.type === 'file') {
    data = JSON.parse(await fs.readFile(pipe.source.ref, 'utf8'));
  } else {
    data = { message: 'Source type not directly fetchable, use webhook trigger.' };
  }

  // Transforms
  for (const t of pipe.transforms) {
    if (t.type === 'filter' && t.expr) {
      try { data = Array.isArray(data) ? data.filter(item => eval(t.expr)) : data; } catch {}
    } else if (t.type === 'map' && t.expr) {
      try { data = Array.isArray(data) ? data.map(item => eval(t.expr)) : data; } catch {}
    }
  }

  // Destination
  if (pipe.destination.type === 'file') {
    await ensureDir(path.dirname(pipe.destination.ref));
    await fs.writeFile(pipe.destination.ref, JSON.stringify(data, null, 2));
  } else if (pipe.destination.type === 'api') {
    await fetch(pipe.destination.ref, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data),
      signal: AbortSignal.timeout(30000),
    });
  }

  pipe.runs++;
  pipe.last_run = new Date().toISOString();
  await saveJSON(pipelinesPath(user), pipes);

  return {
    pipeline_id: pipelineId,
    elapsed_ms: Date.now() - startTime,
    records: Array.isArray(data) ? data.length : 1,
    message: `Pipeline "${pipe.name}" run #${pipe.runs} complete.`,
  };
}

export async function deletePipeline(user, pipelineId) {
  const pipes = await loadJSON(pipelinesPath(user), { pipelines: {} });
  if (!pipes.pipelines[pipelineId]) return { message: `Pipeline ${pipelineId} not found.` };
  const name = pipes.pipelines[pipelineId].name;
  delete pipes.pipelines[pipelineId];
  await saveJSON(pipelinesPath(user), pipes);
  return { message: `Pipeline "${name}" deleted.` };
}

// ── Request Log ─────────────────────────────────────────────────────────────

export async function getApiLogs(user, limit = 50) {
  const log = await loadJSON(logPath(user), { requests: [] });
  return {
    requests: log.requests.slice(0, limit),
    total: log.requests.length,
    message: `Showing ${Math.min(limit, log.requests.length)} of ${log.requests.length} API requests.`,
  };
}
