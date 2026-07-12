/**
 * mcpClientManager.js — Outbound MCP Client Connection Manager
 *
 * Manages connections from Alfred to external MCP servers.
 * Supports transport types:
 *   - stdio: Launch a local process (npx, python, etc.)
 *   - sse: Connect to a remote SSE endpoint
 *   - streamablehttp: Connect to a StreamableHTTP endpoint
 *
 * Uses the official @modelcontextprotocol/sdk Client.
 */

import { Client } from '@modelcontextprotocol/sdk/client/index.js';
import { StdioClientTransport } from '@modelcontextprotocol/sdk/client/stdio.js';
import { SSEClientTransport } from '@modelcontextprotocol/sdk/client/sse.js';
import { StreamableHTTPClientTransport } from '@modelcontextprotocol/sdk/client/streamableHttp.js';
import { getKnownServer, listKnownServers } from './serverRegistry.js';
import { registerRemoteTools, unregisterRemoteTools, getRemoteTools, resolveRemoteTool, getRemoteToolStats } from './toolRouter.js';

// Active connections: Map<serverName, { client, transport, tools, connectedAt }>
const connections = new Map();

/**
 * Connect to an MCP server.
 *
 * @param {object} opts
 * @param {string} opts.serverName — identifier for this connection (or name from registry)
 * @param {string} [opts.transport='stdio'] — 'stdio', 'sse', 'streamablehttp'
 * @param {string} [opts.command] — for stdio: command to run
 * @param {string[]} [opts.args] — for stdio: command arguments
 * @param {string} [opts.url] — for sse/streamablehttp: server URL
 * @param {object} [opts.env] — extra env vars for stdio transport
 * @returns {Promise<object>}
 */
export async function mcpConnect(opts) {
  const { serverName, transport: transportType, command, args, url, env } = opts;

  if (!serverName) throw new Error('serverName is required');
  if (connections.has(serverName)) {
    throw new Error(`Already connected to "${serverName}". Disconnect first.`);
  }

  const start = Date.now();

  // Check if it's a known server
  const known = getKnownServer(serverName);
  const effectiveTransport = transportType || known?.transport || 'stdio';
  const effectiveCommand = command || known?.command;
  const effectiveArgs = args || known?.args || [];

  // Create transport
  let transport;
  switch (effectiveTransport) {
    case 'stdio': {
      if (!effectiveCommand) throw new Error('command is required for stdio transport');
      transport = new StdioClientTransport({
        command: effectiveCommand,
        args: effectiveArgs,
        env: { ...process.env, ...(env || {}) },
      });
      break;
    }
    case 'sse': {
      const sseUrl = url || opts.url;
      if (!sseUrl) throw new Error('url is required for SSE transport');
      transport = new SSEClientTransport(new URL(sseUrl));
      break;
    }
    case 'streamablehttp': {
      const httpUrl = url || opts.url;
      if (!httpUrl) throw new Error('url is required for StreamableHTTP transport');
      transport = new StreamableHTTPClientTransport(new URL(httpUrl));
      break;
    }
    default:
      throw new Error(`Unknown transport: ${effectiveTransport}. Use: stdio, sse, streamablehttp`);
  }

  // Create and connect client
  const client = new Client({
    name: `alfred-mcp-client-${serverName}`,
    version: '6.0.0',
  }, {
    capabilities: {},
  });

  try {
    await client.connect(transport);
  } catch (err) {
    throw new Error(`Failed to connect to "${serverName}": ${err.message}`);
  }

  // Discover tools
  let tools = [];
  try {
    const toolsResult = await client.listTools();
    tools = toolsResult.tools || [];
  } catch (err) {
    // Server might not expose tools — that's OK
  }

  // Register tools in the router
  registerRemoteTools(serverName, tools);

  // Store connection
  connections.set(serverName, {
    client,
    transport,
    tools,
    connectedAt: Date.now(),
    transportType: effectiveTransport,
    serverConfig: known || { command: effectiveCommand, args: effectiveArgs, url },
  });

  return {
    status: 'connected',
    serverName,
    transport: effectiveTransport,
    toolsDiscovered: tools.length,
    tools: tools.map(t => t.name),
    timing: Date.now() - start,
  };
}

/**
 * Disconnect from an MCP server.
 * @param {string} serverName
 */
export async function mcpDisconnect(serverName) {
  const conn = connections.get(serverName);
  if (!conn) throw new Error(`Not connected to "${serverName}"`);

  try {
    await conn.client.close();
  } catch { /* ignore close errors */ }

  unregisterRemoteTools(serverName);
  connections.delete(serverName);

  return {
    status: 'disconnected',
    serverName,
  };
}

/**
 * Call a tool on a remote MCP server.
 *
 * @param {object} opts
 * @param {string} opts.serverName — server to call
 * @param {string} opts.toolName — tool name on the remote server
 * @param {object} [opts.arguments={}] — tool arguments
 * @returns {Promise<object>}
 */
export async function mcpCallTool(opts) {
  const { serverName, toolName, arguments: toolArgs = {} } = opts;

  const conn = connections.get(serverName);
  if (!conn) throw new Error(`Not connected to "${serverName}". Use mcp_connect first.`);

  const start = Date.now();

  try {
    const result = await conn.client.callTool({
      name: toolName,
      arguments: toolArgs,
    });

    return {
      status: 'success',
      serverName,
      toolName,
      result: result.content || result,
      timing: Date.now() - start,
    };
  } catch (err) {
    return {
      status: 'error',
      serverName,
      toolName,
      error: err.message,
      timing: Date.now() - start,
    };
  }
}

/**
 * Call a namespaced remote tool (e.g., 'mcp_github_create_issue').
 * Auto-resolves the server and tool name.
 */
export async function mcpCallNamespacedTool(namespacedName, toolArgs = {}) {
  const resolved = resolveRemoteTool(namespacedName);
  if (!resolved) throw new Error(`Unknown remote tool: ${namespacedName}`);

  return mcpCallTool({
    serverName: resolved.serverName,
    toolName: resolved.toolName,
    arguments: toolArgs,
  });
}

/**
 * List connected servers and their tools.
 */
export function mcpListServers() {
  const servers = [];
  for (const [name, conn] of connections) {
    servers.push({
      name,
      transport: conn.transportType,
      toolCount: conn.tools.length,
      tools: conn.tools.map(t => t.name),
      connectedAt: new Date(conn.connectedAt).toISOString(),
      uptime: Math.round((Date.now() - conn.connectedAt) / 1000) + 's',
    });
  }

  return {
    connected: servers,
    totalServers: servers.length,
    totalRemoteTools: getRemoteTools().length,
    toolStats: getRemoteToolStats(),
    knownServers: listKnownServers(),
  };
}
