/**
 * agentCard.js — A2A Agent Card Generator
 *
 * Generates the Agent Card (/.well-known/agent.json) per Google's
 * Agent-to-Agent Protocol specification.
 *
 * The Agent Card advertises Alfred's capabilities, supported skills,
 * authentication methods, and endpoint URLs so other agents can
 * discover and interact with Alfred.
 */

const ALFRED_BASE_URL = process.env.A2A_BASE_URL || 'https://gositeme.com/gocodeme/openclaw';

/**
 * Generate the Agent Card.
 * @param {object} [overrides] — optional field overrides
 * @returns {object} — A2A Agent Card JSON
 */
export function generateAgentCard(overrides = {}) {
  return {
    // ── Agent Identity ─────────────────────────────────────────────
    name: 'Alfred AI',
    description: 'GoCodeMe full-stack AI developer assistant — 400+ tools, multi-language code execution, web browsing, RAG knowledge base, media generation, and more.',
    url: ALFRED_BASE_URL,
    version: '6.0.0',
    provider: {
      organization: 'GoCodeMe',
      url: 'https://gocodeme.com',
    },

    // ── Capabilities / Skills ──────────────────────────────────────
    capabilities: {
      streaming: false,
      pushNotifications: false,
      stateTransitionHistory: true,
    },
    skills: [
      {
        id: 'code-generation',
        name: 'Code Generation & Review',
        description: 'Generate, review, and refactor code in 20+ languages',
        tags: ['code', 'development', 'review'],
        examples: [
          'Write a REST API in Node.js with Express',
          'Review this Python code for security issues',
        ],
      },
      {
        id: 'web-browsing',
        name: 'Web Browsing & Search',
        description: 'Browse web pages, take screenshots, extract data, search the web',
        tags: ['browser', 'web', 'search', 'scraping'],
        examples: [
          'Search for the latest Node.js security advisories',
          'Extract the pricing table from this URL',
        ],
      },
      {
        id: 'rag-knowledge',
        name: 'RAG Knowledge Base',
        description: 'Ingest documents and answer questions using retrieval-augmented generation',
        tags: ['rag', 'knowledge', 'documents', 'qa'],
        examples: [
          'Ingest our API documentation and answer questions about it',
        ],
      },
      {
        id: 'media-generation',
        name: 'AI Media Generation',
        description: 'Generate images, videos, audio, analyze images with vision AI (80+ models)',
        tags: ['image', 'video', 'audio', 'ai', 'media'],
        examples: [
          'Generate a logo for my project',
          'Analyze this screenshot and describe what you see',
        ],
      },
      {
        id: 'code-execution',
        name: 'Code Execution',
        description: 'Execute code in Python, Node.js, Bash, Ruby, PHP with output capture',
        tags: ['interpreter', 'execute', 'jupyter', 'repl'],
        examples: [
          'Run this Python data analysis script and show me the chart',
        ],
      },
      {
        id: 'devops',
        name: 'DevOps & Monitoring',
        description: 'Server monitoring, auto-remediation, workflow automation, deployment',
        tags: ['devops', 'monitoring', 'automation', 'deploy'],
        examples: [
          'Monitor my server and alert me if disk usage exceeds 80%',
        ],
      },
    ],

    // ── Authentication ─────────────────────────────────────────────
    authentication: {
      schemes: ['bearer'],
      credentials: null, // agents must present a valid JWT
    },

    // ── Endpoints ──────────────────────────────────────────────────
    defaultInputModes: ['text/plain', 'application/json'],
    defaultOutputModes: ['text/plain', 'application/json'],

    ...overrides,
  };
}
