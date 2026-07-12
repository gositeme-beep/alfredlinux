/**
 * workflowTemplates.js — Pre-built n8n Workflow Templates
 *
 * Ready-to-deploy workflow templates for common automation tasks.
 * Each template is a simplified JSON that the n8nBridge converts to n8n format.
 */

export const TEMPLATES = {
  // ── Deploy Notification ──────────────────────────────────────────
  'deploy-notification': {
    name: 'Deploy Notification',
    description: 'Sends a notification when a deployment happens (webhook trigger → Slack/email)',
    nodes: [
      { type: 'n8n-nodes-base.webhook', name: 'Webhook', position: [250, 300], parameters: { path: 'deploy', httpMethod: 'POST' } },
      { type: 'n8n-nodes-base.set', name: 'Format', position: [450, 300], parameters: { values: { string: [{ name: 'message', value: '=🚀 Deployment: {{$json.project}} v{{$json.version}} by {{$json.user}}' }] } } },
      { type: 'n8n-nodes-base.slack', name: 'Slack', position: [650, 300], parameters: { channel: '#deployments', text: '={{$json.message}}' } },
    ],
    connections: { Webhook: { main: [[{ node: 'Format', type: 'main', index: 0 }]] }, Format: { main: [[{ node: 'Slack', type: 'main', index: 0 }]] } },
  },

  // ── Health Check ─────────────────────────────────────────────────
  'health-check': {
    name: 'Health Check Monitor',
    description: 'Runs every 5 minutes, checks URLs, alerts on failure',
    nodes: [
      { type: 'n8n-nodes-base.cron', name: 'Schedule', position: [250, 300], parameters: { cronExpression: '*/5 * * * *' } },
      { type: 'n8n-nodes-base.httpRequest', name: 'check', position: [450, 300], parameters: { url: '={{$parameter.url}}', method: 'GET' } },
      { type: 'n8n-nodes-base.if', name: 'IsDown', position: [650, 300], parameters: { conditions: { number: [{ value1: '={{$json.statusCode}}', value2: 200, operation: 'notEqual' }] } } },
      { type: 'n8n-nodes-base.emailSend', name: 'Alert', position: [850, 200], parameters: { subject: '🚨 Site Down: {{$parameter.url}}', text: 'Status code: {{$json.statusCode}}' } },
    ],
    connections: { Schedule: { main: [[{ node: 'check', type: 'main', index: 0 }]] }, check: { main: [[{ node: 'IsDown', type: 'main', index: 0 }]] }, IsDown: { main: [[{ node: 'Alert', type: 'main', index: 0 }], []] } },
  },

  // ── Backup ───────────────────────────────────────────────────────
  'backup': {
    name: 'Scheduled Backup',
    description: 'Daily backup of configured directories',
    nodes: [
      { type: 'n8n-nodes-base.cron', name: 'Daily', position: [250, 300], parameters: { cronExpression: '0 2 * * *' } },
      { type: 'n8n-nodes-base.executeCommand', name: 'Backup', position: [450, 300], parameters: { command: 'tar -czf /tmp/backup-$(date +%Y%m%d).tar.gz {{$parameter.directory}}' } },
      { type: 'n8n-nodes-base.set', name: 'Result', position: [650, 300], parameters: { values: { string: [{ name: 'status', value: '=Backup complete: {{$json.stdout}}' }] } } },
    ],
    connections: { Daily: { main: [[{ node: 'Backup', type: 'main', index: 0 }]] }, Backup: { main: [[{ node: 'Result', type: 'main', index: 0 }]] } },
  },

  // ── RSS Feed Monitor ─────────────────────────────────────────────
  'rss-monitor': {
    name: 'RSS Feed Monitor',
    description: 'Monitors RSS feeds and sends new items to a webhook',
    nodes: [
      { type: 'n8n-nodes-base.cron', name: 'Every30Min', position: [250, 300], parameters: { cronExpression: '*/30 * * * *' } },
      { type: 'n8n-nodes-base.rssFeedRead', name: 'ReadFeed', position: [450, 300], parameters: { url: '={{$parameter.feedUrl}}' } },
      { type: 'n8n-nodes-base.httpRequest', name: 'Notify', position: [650, 300], parameters: { url: '={{$parameter.webhookUrl}}', method: 'POST', body: '={{JSON.stringify($json)}}' } },
    ],
    connections: { Every30Min: { main: [[{ node: 'ReadFeed', type: 'main', index: 0 }]] }, ReadFeed: { main: [[{ node: 'Notify', type: 'main', index: 0 }]] } },
  },

  // ── Data Pipeline ────────────────────────────────────────────────
  'data-pipeline': {
    name: 'Data Pipeline (API → Transform → Store)',
    description: 'Fetches data from an API, transforms it, and stores in a webhook endpoint',
    nodes: [
      { type: 'n8n-nodes-base.cron', name: 'Hourly', position: [250, 300], parameters: { cronExpression: '0 * * * *' } },
      { type: 'n8n-nodes-base.httpRequest', name: 'Fetch', position: [450, 300], parameters: { url: '={{$parameter.apiUrl}}', method: 'GET' } },
      { type: 'n8n-nodes-base.code', name: 'Transform', position: [650, 300], parameters: { code: 'return items.map(item => ({ json: { ...item.json, processedAt: new Date().toISOString() } }))' } },
      { type: 'n8n-nodes-base.httpRequest', name: 'Store', position: [850, 300], parameters: { url: '={{$parameter.storeUrl}}', method: 'POST', body: '={{JSON.stringify($json)}}' } },
    ],
    connections: { Hourly: { main: [[{ node: 'Fetch', type: 'main', index: 0 }]] }, Fetch: { main: [[{ node: 'Transform', type: 'main', index: 0 }]] }, Transform: { main: [[{ node: 'Store', type: 'main', index: 0 }]] } },
  },
};

/**
 * Get a template by ID.
 */
export function getTemplate(id) {
  return TEMPLATES[id] || null;
}

/**
 * List available templates.
 */
export function listTemplates() {
  return Object.entries(TEMPLATES).map(([id, t]) => ({
    id,
    name: t.name,
    description: t.description,
    nodeCount: t.nodes.length,
  }));
}
