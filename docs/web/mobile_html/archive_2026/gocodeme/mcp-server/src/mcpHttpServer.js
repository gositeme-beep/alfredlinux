/**
 * GoCodeMe MCP HTTP Server (Streamable HTTP + SSE fallback)
 *
 * Implements the MCP Streamable HTTP transport (new standard) at POST /mcp
 * with SSE fallback at GET /mcp/sse for older clients.
 *
 * Each request carries an Authorization: Bearer <session-token> header.
 * The token is validated against the GoCodeMe middleware JWT secret to resolve
 * the customer's DirectAdmin username before opening a DA session.
 *
 * Endpoints:
 *   POST /mcp             — Streamable HTTP MCP endpoint
 *   GET  /mcp             — SSE stream fallback
 *   DELETE /mcp           — Close StreamableHTTP session
 *   GET  /mcp/health      — health check
 *   POST /telnyx/call     — Make an outbound call
 *   POST /telnyx/hangup   — Hang up a call
 *   GET  /telnyx/calls    — List active calls
 *   GET  /telnyx/numbers  — List your Telnyx phone numbers
 *   POST /telnyx/webhook  — Receive Telnyx call events
 */

import 'dotenv/config';
import express from 'express';
import { Server } from '@modelcontextprotocol/sdk/server/index.js';
import { StreamableHTTPServerTransport } from '@modelcontextprotocol/sdk/server/streamableHttp.js';
import { SSEServerTransport } from '@modelcontextprotocol/sdk/server/sse.js';
import {
  CallToolRequestSchema,
  ListToolsRequestSchema,
} from '@modelcontextprotocol/sdk/types.js';
import { randomUUID } from 'crypto';
import jwt from 'jsonwebtoken';
import Redis from 'ioredis';
import { DirectAdminClient } from './daClient.js';
import { WhmcsClient } from './whmcsClient.js';
import { toolDefinitions } from './tools.js';
import { dispatchTool } from './toolDispatch.js';
import { warmup as warmupEmbeddings } from './embeddings.js';
import { bootScheduler, setPlaybookExecutor } from './scheduler/schedulerEngine.js';
import { getMemoryContext } from './memory/memoryEngine.js';
import { renderPlaybook } from './playbooks/playbookEngine.js';
import { activeSessionCount } from './terminal/sessionManager.js';
import { getErrorSummary } from './resilience/errorRecovery.js';
import { getDocsJSON, getDocsMarkdown, searchTools, getDocsSummary } from './docs/toolDocs.js';
import { startVoiceServer } from './voice/voiceServer.js';
import { artifactMiddleware } from './artifacts/artifactServer.js';
import {
  makeCall,
  hangupCall,
  getCallStatus,
  listActiveCalls,
  handleWebhook,
  listTelnyxNumbers,
} from './voice/telnyxCalls.js';

const PORT       = process.env.MCP_PORT       || 3005;
// SECURITY (VULN-R2-02): Never fall back to a guessable secret
const JWT_SECRET = process.env.JWT_SECRET;
if (!JWT_SECRET) {
  console.error('FATAL: JWT_SECRET environment variable is required but not set.');
  process.exit(1);
}
const DA_HOST    = process.env.DA_HOST         || 'https://localhost:2222';
const DA_ADMIN   = process.env.DA_ADMIN_USER   || 'admin';
const DA_PASS    = process.env.DA_ADMIN_PASS   || '';

const app = express();

// Active transports keyed by sessionId
const sessions = new Map();

// Redis client for WHMCS client ID fallback lookup
let redis;
try {
  redis = new Redis({ lazyConnect: true, maxRetriesPerRequest: 1 });
  redis.connect().catch(() => { redis = null; });
} catch { redis = null; }

function resolveUser(req) {
  // ── SECURITY: Always require JWT — no localhost bypass ─────────────────
  // The old localhost bypass trusted ?daUsername= from any localhost caller,
  // but bwrap terminals have --share-net, so any IDE user could impersonate
  // another customer by curling localhost:3005/mcp?daUsername=VICTIM.
  // Now we ALWAYS require a signed JWT, regardless of source IP.

  const auth = req.headers.authorization || '';
  const token = auth.startsWith('Bearer ') ? auth.slice(7) : null;

  // Also check query param for the token (Claude Code sends it this way)
  const queryToken = req.query?.token;
  const effectiveToken = token || queryToken || null;

  if (!effectiveToken) {
    throw new Error('Missing Authorization Bearer token — MCP requires authentication');
  }

  try {
    const payload = jwt.verify(effectiveToken, JWT_SECRET);
    if (!payload.daUsername) throw new Error('Token missing daUsername');
    return payload;
  } catch (err) {
    throw new Error(`Invalid session token: ${err.message}`);
  }
}

async function resolveWhmcsClientId(daUsername, jwtClientId) {
  if (jwtClientId) return jwtClientId;
  if (!redis) return undefined;
  try {
    const id = await redis.get(`client_id_by_da:${daUsername}`);
    if (id) {
      console.error(`[MCP-HTTP] Resolved WHMCS clientId ${id} from Redis for ${daUsername}`);
      return parseInt(id, 10) || id;
    }
  } catch (err) {
    console.error(`[MCP-HTTP] Redis lookup failed for ${daUsername}: ${err.message}`);
  }
  return undefined;
}

function buildMcpServer(daUsername, whmcsClientId) {
  const daClient = new DirectAdminClient({
    host: DA_HOST,
    adminUser: DA_ADMIN,
    adminPass: DA_PASS,
    targetUsername: daUsername,
  });

  const whmcsClient = whmcsClientId ? new WhmcsClient(whmcsClientId) : null;

  const server = new Server(
    { name: 'gocodeme-directadmin', version: '6.0.0' },
    { capabilities: { tools: {} } }
  );

  server.setRequestHandler(ListToolsRequestSchema, async () => ({ tools: toolDefinitions }));

  server.setRequestHandler(CallToolRequestSchema, async (request) => {
    const { name, arguments: args } = request.params;
    return dispatchTool(name, args, daClient, whmcsClient);
  });

  return server;
}

// ── Streamable HTTP transport ─────────────────────────────────────────────
app.post('/mcp', async (req, res) => {
  let user;
  try {
    user = resolveUser(req);
  } catch (err) {
    return res.status(401).json({ error: err.message });
  }

  const existingSessionId = req.headers['mcp-session-id'];
  if (existingSessionId && sessions.has(existingSessionId)) {
    const { transport } = sessions.get(existingSessionId);
    await transport.handleRequest(req, res);
    return;
  }

  if (existingSessionId) {
    const whmcsId = await resolveWhmcsClientId(user.daUsername, user.whmcsClientId);
    const server    = buildMcpServer(user.daUsername, whmcsId);
    const transport = new StreamableHTTPServerTransport({
      sessionIdGenerator: () => existingSessionId,
    });
    const inner = transport._webStandardTransport;
    inner._initialized = true;
    inner.sessionId = existingSessionId;
    sessions.set(existingSessionId, { transport, server });
    transport.onclose = () => {
      sessions.delete(existingSessionId);
    };
    await server.connect(transport);
    await transport.handleRequest(req, res);
    return;
  }

  const whmcsId = await resolveWhmcsClientId(user.daUsername, user.whmcsClientId);
  const sessionId = randomUUID();
  const server    = buildMcpServer(user.daUsername, whmcsId);
  const transport = new StreamableHTTPServerTransport({
    sessionIdGenerator: () => sessionId,
    onsessioninitialized: (sid) => {
      sessions.set(sid, { transport, server });
      console.error(`[MCP-HTTP] StreamableHTTP session opened: ${sid} → DA user ${user.daUsername}`);
    },
  });

  transport.onclose = () => {
    sessions.delete(sessionId);
  };

  await server.connect(transport);
  await transport.handleRequest(req, res);
});

app.get('/mcp', async (req, res) => {
  const sessionId = req.headers['mcp-session-id'];
  if (sessionId && sessions.has(sessionId)) {
    const { transport } = sessions.get(sessionId);
    await transport.handleRequest(req, res);
    return;
  }

  if (sessionId) {
    let user;
    try { user = resolveUser(req); } catch (err) { return res.status(401).json({ error: err.message }); }
    const whmcsId = await resolveWhmcsClientId(user.daUsername, user.whmcsClientId);
    const server    = buildMcpServer(user.daUsername, whmcsId);
    const transport = new StreamableHTTPServerTransport({ sessionIdGenerator: () => sessionId });
    const inner = transport._webStandardTransport;
    inner._initialized = true;
    inner.sessionId = sessionId;
    sessions.set(sessionId, { transport, server });
    transport.onclose = () => { sessions.delete(sessionId); };
    await server.connect(transport);
    await transport.handleRequest(req, res);
    return;
  }

  let user;
  try { user = resolveUser(req); } catch (err) { return res.status(401).json({ error: err.message }); }

  const whmcsId      = await resolveWhmcsClientId(user.daUsername, user.whmcsClientId);
  const server       = buildMcpServer(user.daUsername, whmcsId);
  const sseTransport = new SSEServerTransport('/mcp', res);
  const sseSessionId = sseTransport.sessionId;

  sessions.set(sseSessionId, { transport: sseTransport, server });

  res.on('close', () => {
    sessions.delete(sseSessionId);
  });

  await server.connect(sseTransport);
});

app.delete('/mcp', async (req, res) => {
  const sessionId = req.headers['mcp-session-id'];
  if (sessionId && sessions.has(sessionId)) {
    const { transport } = sessions.get(sessionId);
    await transport.handleRequest(req, res);
    sessions.delete(sessionId);
  } else {
    res.status(404).json({ error: 'Session not found' });
  }
});

// ── Health check ──────────────────────────────────────────────────────────
app.get('/mcp/health', async (_req, res) => {
  const uptime = process.uptime();
  const mem = process.memoryUsage();
  let analyticsData = null;
  try {
    const { getAnalytics } = await import('./analytics/analytics.js');
    analyticsData = await getAnalytics({ topN: 5 });
  } catch { /* analytics not loaded yet */ }

  const errorSummary = getErrorSummary();

  res.json({
    ok: true,
    version: '6.0.0',
    uptime_seconds: Math.round(uptime),
    uptime_human: `${Math.floor(uptime / 3600)}h ${Math.floor((uptime % 3600) / 60)}m`,
    sessions: sessions.size,
    terminal_sessions: activeSessionCount(),
    memory: {
      rss_mb: Math.round(mem.rss / 1024 / 1024),
      heap_used_mb: Math.round(mem.heapUsed / 1024 / 1024),
      heap_total_mb: Math.round(mem.heapTotal / 1024 / 1024),
    },
    tools: toolDefinitions.length,
    analytics: analyticsData ? {
      total_calls: analyticsData.total_calls,
      unique_tools: analyticsData.unique_tools_used,
    } : null,
    errors: errorSummary,
    telnyx: {
      configured: !!process.env.TELNYX_API_KEY,
      from_number: process.env.TELNYX_FROM_NUMBER || 'not set',
      active_calls: listActiveCalls().length,
    },
  });
});

// ── Telnyx Call API ───────────────────────────────────────────────────────

// POST /telnyx/call  { to, from? }
app.post('/telnyx/call', express.json(), async (req, res) => {
  try {
    const { to, from } = req.body || {};
    if (!to) return res.status(400).json({ error: 'Missing "to" phone number' });
    const result = await makeCall(to, from);
    res.json(result);
  } catch (err) {
    console.error('[Telnyx] makeCall error:', err.message);
    res.status(500).json({ error: err.message });
  }
});

// POST /telnyx/hangup  { callControlId }
app.post('/telnyx/hangup', express.json(), async (req, res) => {
  try {
    const { callControlId } = req.body || {};
    if (!callControlId) return res.status(400).json({ error: 'Missing callControlId' });
    const result = await hangupCall(callControlId);
    res.json(result);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// GET /telnyx/calls
app.get('/telnyx/calls', (_req, res) => {
  res.json({ calls: listActiveCalls() });
});

// GET /telnyx/numbers
app.get('/telnyx/numbers', async (_req, res) => {
  try {
    const numbers = await listTelnyxNumbers();
    res.json({ numbers });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// GET /telnyx/call/:callControlId
app.get('/telnyx/call/:id', async (req, res) => {
  try {
    const status = await getCallStatus(req.params.id);
    res.json(status);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// POST /telnyx/webhook  — Telnyx posts call events here
// Set this URL in your Telnyx portal: https://gositeme.com/telnyx/webhook
app.post('/telnyx/webhook', express.json(), (req, res) => {
  try {
    handleWebhook(req.body, (event) => {
      console.log(`[Telnyx] Event: ${event.eventType} | call=${event.callControlId}`);
    });
    res.json({ ok: true });
  } catch (err) {
    console.error('[Telnyx] Webhook error:', err.message);
    res.status(500).json({ error: err.message });
  }
});

// ── Artifacts ─────────────────────────────────────────────────────────────
app.use('/artifacts', artifactMiddleware);

// ── Tool Documentation API ────────────────────────────────────────────────
app.get('/mcp/docs', (_req, res) => {
  try {
    const docs = getDocsJSON();
    res.json({ tools: docs, total: docs.length });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.get('/mcp/docs/markdown', (_req, res) => {
  try {
    const md = getDocsMarkdown();
    res.type('text/markdown').send(md);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.get('/mcp/docs/summary', (_req, res) => {
  try {
    const summary = getDocsSummary();
    res.type('text/plain').send(summary);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.get('/mcp/docs/search', express.json(), (req, res) => {
  try {
    const q = req.query.q || '';
    if (!q) return res.status(400).json({ error: 'Missing ?q= parameter' });
    const results = searchTools(q);
    res.json({ query: q, results, total: results.length });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// ── Memory context ────────────────────────────────────────────────────────
app.get('/mcp/memory-context', async (req, res) => {
  const { daUsername, hint } = req.query;
  if (!daUsername) return res.status(400).json({ error: 'Missing daUsername' });
  try {
    const context = await getMemoryContext(daUsername, hint || 'general conversation', 10);
    res.json({ context: context || '' });
  } catch (err) {
    res.json({ context: '', error: err.message });
  }
});

// ── Internal REST bridge for middleware tool calls ───────────────────────
// Bypasses MCP Streamable HTTP session requirements for trusted internal callers
app.post('/api/tool', express.json(), async (req, res) => {
  let user;
  try {
    user = resolveUser(req);
  } catch (err) {
    return res.status(401).json({ error: err.message });
  }

  const { name, arguments: args } = req.body;
  if (!name) return res.status(400).json({ error: 'Missing tool name' });

  const whmcsId = await resolveWhmcsClientId(user.daUsername, user.whmcsClientId);
  const daClient = new DirectAdminClient({
    host: DA_HOST, adminUser: DA_ADMIN, adminPass: DA_PASS,
    targetUsername: user.daUsername,
  });
  const whmcsClient = whmcsId ? new WhmcsClient(whmcsId) : null;

  try {
    const result = await dispatchTool(name, args || {}, daClient, whmcsClient);
    res.json({ ok: true, result });
  } catch (err) {
    res.status(500).json({ ok: false, error: err.message });
  }
});

// ── Start ─────────────────────────────────────────────────────────────────
const httpServer = app.listen(PORT, '127.0.0.1', async () => {
  console.error(`[MCP-HTTP] GoCodeMe MCP server on 127.0.0.1:${PORT} (StreamableHTTP + SSE fallback)`);

  try {
    const warmStart = Date.now();
    await warmupEmbeddings();
    console.error(`[MCP-HTTP] ONNX embedding model warmed up in ${Date.now() - warmStart}ms`);
  } catch (err) {
    console.error(`[MCP-HTTP] ONNX warmup failed (non-fatal): ${err.message}`);
  }

  setPlaybookExecutor(async (daUsername, playbookName, parameters) => {
    const homeDir = `/home/${daUsername}`;
    try {
      const rendered = await renderPlaybook(homeDir, playbookName, parameters);
      const steps = rendered.steps.map((s, i) => `${i + 1}. ${s}`).join('\n');
      return `Playbook "${rendered.name}" rendered with ${rendered.total_steps} steps:\n${steps}`;
    } catch (err) {
      throw new Error(`Playbook "${playbookName}" failed: ${err.message}`);
    }
  });

  try {
    const activated = await bootScheduler();
    if (activated > 0) {
      console.error(`[MCP-HTTP] Scheduler booted — ${activated} tasks activated`);
    }
  } catch (err) {
    console.error(`[MCP-HTTP] Scheduler boot failed (non-fatal): ${err.message}`);
  }

  try {
    const voicePort = parseInt(process.env.VOICE_PORT || '3006', 10);
    startVoiceServer({ port: voicePort });
    console.error(`[MCP-HTTP] Voice WebSocket server started on port ${voicePort}`);
  } catch (err) {
    console.error(`[MCP-HTTP] Voice server boot failed (non-fatal): ${err.message}`);
  }

  console.error(`[Telnyx] Call API ready — POST /telnyx/call to make calls`);
  console.error(`[Telnyx] Webhook URL: https://gositeme.com/telnyx/webhook`);
});

httpServer.on('error', (err) => {
  if (err.code === 'EADDRINUSE') {
    console.error(`[MCP-HTTP] ERROR: Port ${PORT} is already in use.`);
  } else {
    console.error('[MCP-HTTP] Server error:', err);
  }
  process.exit(1);
});

function gracefulShutdown(signal) {
  console.error(`[MCP-HTTP] ${signal} received — shutting down gracefully`);
  httpServer.close(() => {
    console.error('[MCP-HTTP] HTTP server closed');
    process.exit(0);
  });
  setTimeout(() => process.exit(0), 5000);
}
process.on('SIGINT', () => gracefulShutdown('SIGINT'));
process.on('SIGTERM', () => gracefulShutdown('SIGTERM'));
