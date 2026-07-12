/**
 * serverRegistry.js — Built-in MCP Server Directory
 *
 * Registry of popular MCP servers with their connection configurations.
 * Users can connect to these by name or provide custom server configs.
 */

/**
 * Built-in MCP server configurations.
 * Each entry describes how to connect to a known MCP server.
 */
export const KNOWN_SERVERS = {
  // ── GitHub ────────────────────────────────────────────────────────
  github: {
    name: 'GitHub',
    description: 'GitHub MCP server — repos, issues, PRs, code search',
    transport: 'stdio',
    command: 'npx',
    args: ['-y', '@modelcontextprotocol/server-github'],
    envRequired: ['GITHUB_PERSONAL_ACCESS_TOKEN'],
    category: 'development',
  },

  // ── Filesystem ────────────────────────────────────────────────────
  filesystem: {
    name: 'Filesystem',
    description: 'Safe filesystem access with configurable allowed directories',
    transport: 'stdio',
    command: 'npx',
    args: ['-y', '@modelcontextprotocol/server-filesystem'],
    category: 'system',
  },

  // ── Brave Search ──────────────────────────────────────────────────
  'brave-search': {
    name: 'Brave Search',
    description: 'Web search via Brave Search API',
    transport: 'stdio',
    command: 'npx',
    args: ['-y', '@modelcontextprotocol/server-brave-search'],
    envRequired: ['BRAVE_API_KEY'],
    category: 'search',
  },

  // ── Slack ─────────────────────────────────────────────────────────
  slack: {
    name: 'Slack',
    description: 'Slack workspace — channels, messages, users',
    transport: 'stdio',
    command: 'npx',
    args: ['-y', '@modelcontextprotocol/server-slack'],
    envRequired: ['SLACK_BOT_TOKEN'],
    category: 'communication',
  },

  // ── PostgreSQL ────────────────────────────────────────────────────
  postgres: {
    name: 'PostgreSQL',
    description: 'PostgreSQL database read-only access',
    transport: 'stdio',
    command: 'npx',
    args: ['-y', '@modelcontextprotocol/server-postgres'],
    category: 'database',
  },

  // ── Puppeteer ─────────────────────────────────────────────────────
  puppeteer: {
    name: 'Puppeteer',
    description: 'Browser automation with Puppeteer',
    transport: 'stdio',
    command: 'npx',
    args: ['-y', '@modelcontextprotocol/server-puppeteer'],
    category: 'automation',
  },

  // ── Memory ────────────────────────────────────────────────────────
  memory: {
    name: 'Memory',
    description: 'Knowledge graph-based persistent memory',
    transport: 'stdio',
    command: 'npx',
    args: ['-y', '@modelcontextprotocol/server-memory'],
    category: 'ai',
  },

  // ── Sequential Thinking ───────────────────────────────────────────
  'sequential-thinking': {
    name: 'Sequential Thinking',
    description: 'Dynamic problem-solving through sequential thought steps',
    transport: 'stdio',
    command: 'npx',
    args: ['-y', '@modelcontextprotocol/server-sequential-thinking'],
    category: 'ai',
  },

  // ── Fetch ─────────────────────────────────────────────────────────
  fetch: {
    name: 'Fetch',
    description: 'HTTP fetch with content extraction',
    transport: 'stdio',
    command: 'npx',
    args: ['-y', '@modelcontextprotocol/server-fetch'],
    category: 'network',
  },
};

/**
 * Get a known server config by name.
 */
export function getKnownServer(name) {
  return KNOWN_SERVERS[name] || null;
}

/**
 * List all known servers.
 */
export function listKnownServers() {
  return Object.entries(KNOWN_SERVERS).map(([id, config]) => ({
    id,
    name: config.name,
    description: config.description,
    transport: config.transport,
    category: config.category,
    envRequired: config.envRequired || [],
  }));
}
