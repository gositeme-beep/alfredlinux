/**
 * GoCodeMe MCP Server
 *
 * A Model Context Protocol server that wraps the DirectAdmin API.
 * Both Theia IDE and OpenHands agent connect to this server — one bridge serves
 * both components, giving them a shared view of the customer's live hosting.
 *
 * Transport: stdio (default for MCP) — the middleware spawns this process per
 * session and communicates over stdin/stdout.  For multi-session deployments
 * use the HTTP/SSE transport variant (see mcpHttpServer.js).
 *
 * Tool categories:
 *   File management     — read, write, list, delete, rename, search, stat
 *   Database management — create/list/delete MySQL databases
 *   Domain management   — list domains, create/delete subdomains
 *   Email management    — create/delete email accounts, forwarders, autoresponders
 *   DNS management      — list/add/delete DNS records
 *   SSL management      — Let's Encrypt, force HTTPS
 *   Cron management     — list/create/delete cron jobs
 *   Backup management   — create/list/restore backups
 *   Account stats       — usage, limits, summary
 */

import 'dotenv/config';
import { Server } from '@modelcontextprotocol/sdk/server/index.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import {
  CallToolRequestSchema,
  ListToolsRequestSchema,
} from '@modelcontextprotocol/sdk/types.js';
import { DirectAdminClient } from './daClient.js';
import { WhmcsClient } from './whmcsClient.js';
import { toolDefinitions } from './tools.js';
import { dispatchTool } from './toolDispatch.js';

// ── Resolve runtime config from env ───────────────────────────────────────
const DA_USERNAME  = process.env.GOCODEME_DA_USERNAME;
const DA_HOST      = process.env.DA_HOST      || 'https://localhost:2222';
const DA_ADMIN     = process.env.DA_ADMIN_USER || 'admin';
const DA_PASS      = process.env.DA_ADMIN_PASS || '';

if (!DA_USERNAME) {
  console.error('[MCP] GOCODEME_DA_USERNAME is required');
  process.exit(1);
}

const daClient = new DirectAdminClient({
  host: DA_HOST,
  adminUser: DA_ADMIN,
  adminPass: DA_PASS,
  targetUsername: DA_USERNAME,
});

// WHMCS client for commerce tools (optional — only if client ID is set)
const WHMCS_CLIENT_ID = process.env.GOCODEME_WHMCS_CLIENT_ID;
const whmcsClient = WHMCS_CLIENT_ID ? new WhmcsClient(WHMCS_CLIENT_ID) : null;

// ── MCP Server ─────────────────────────────────────────────────────────────
const server = new Server(
  { name: 'gocodeme-directadmin', version: '4.0.0' },
  { capabilities: { tools: {} } }
);

// ── List available tools ───────────────────────────────────────────────────
server.setRequestHandler(ListToolsRequestSchema, async () => ({
  tools: toolDefinitions,
}));

// ── Handle tool calls ──────────────────────────────────────────────────────
server.setRequestHandler(CallToolRequestSchema, async (request) => {
  const { name, arguments: args } = request.params;
  return dispatchTool(name, args, daClient, whmcsClient);
});

// ── Connect transport and run ──────────────────────────────────────────────
const transport = new StdioServerTransport();
await server.connect(transport);
console.error(`[MCP] GoCodeMe MCP server v4.1.0 running for user: ${DA_USERNAME} (WHMCS: ${WHMCS_CLIENT_ID || 'none'})`);
