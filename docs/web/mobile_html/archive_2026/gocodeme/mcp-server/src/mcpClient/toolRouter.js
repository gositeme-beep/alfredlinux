/**
 * toolRouter.js — Remote Tool Discovery & Namespace Router
 *
 * When an MCP client connection is established, this module:
 *   1. Discovers all tools exposed by the remote server
 *   2. Namespaces them as mcp_{serverName}_{toolName}
 *   3. Provides a router to dispatch calls to the right server
 */

// Map<serverName, Array<{name, description, inputSchema}>>
const remoteTools = new Map();

/**
 * Register tools from a remote MCP server.
 * @param {string} serverName — the short name of the server
 * @param {Array<{name: string, description: string, inputSchema: object}>} tools
 */
export function registerRemoteTools(serverName, tools) {
  remoteTools.set(serverName, tools);
}

/**
 * Unregister tools from a server.
 */
export function unregisterRemoteTools(serverName) {
  remoteTools.delete(serverName);
}

/**
 * Get all remote tools, namespaced.
 * @returns {Array<{name: string, description: string, inputSchema: object, serverName: string, originalName: string}>}
 */
export function getRemoteTools() {
  const all = [];
  for (const [serverName, tools] of remoteTools) {
    for (const tool of tools) {
      all.push({
        name: `mcp_${serverName}_${tool.name}`,
        description: `[${serverName}] ${tool.description || tool.name}`,
        inputSchema: tool.inputSchema || { type: 'object', properties: {} },
        serverName,
        originalName: tool.name,
      });
    }
  }
  return all;
}

/**
 * Resolve a namespaced tool name to its server and original name.
 * @param {string} namespacedName — e.g. 'mcp_github_create_issue'
 * @returns {{serverName: string, toolName: string} | null}
 */
export function resolveRemoteTool(namespacedName) {
  for (const [serverName, tools] of remoteTools) {
    const prefix = `mcp_${serverName}_`;
    if (namespacedName.startsWith(prefix)) {
      const toolName = namespacedName.slice(prefix.length);
      if (tools.some(t => t.name === toolName)) {
        return { serverName, toolName };
      }
    }
  }
  return null;
}

/**
 * Get tool count per server.
 */
export function getRemoteToolStats() {
  const stats = {};
  for (const [name, tools] of remoteTools) {
    stats[name] = tools.length;
  }
  return stats;
}
