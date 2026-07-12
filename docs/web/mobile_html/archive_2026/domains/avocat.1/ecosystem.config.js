/**
 * Alfred AI — PM2 Ecosystem Configuration
 * ─────────────────────────────────────────
 * Manages ALL sovereign infrastructure services
 *
 * Usage:
 *   pm2 start ecosystem.config.js          # Start all
 *   pm2 start ecosystem.config.js --only redis
 *   pm2 restart all                        # Restart everything
 *   pm2 logs                               # View all logs
 *   pm2 status                             # Service overview
 *   pm2 save                               # Persist across restarts
 *   pm2 startup                            # Auto-start on boot
 */
const fs = require('fs');
const path = require('path');
const HOME = process.env.HOME || '/home/gositeme';
const APP_DIR = `${HOME}/domains/gositeme.com/public_html`;

// Load .env file safely (values may contain $ chars)
const envFile = path.join(APP_DIR, '.env');
if (fs.existsSync(envFile)) {
  for (const line of fs.readFileSync(envFile, 'utf8').split('\n')) {
    const m = line.match(/^([A-Z_][A-Z0-9_]*)=(.*)$/);
    if (m && !process.env[m[1]]) process.env[m[1]] = m[2];
  }
}

module.exports = {
  apps: [
    // ── Redis (must start first) ──────────────────────────────
    {
      name: 'redis',
      script: `${HOME}/.local/bin/redis-server`,
      args: `${HOME}/.local/redis/redis.conf`,
      interpreter: 'none',
      autorestart: true,
      watch: false,
      max_memory_restart: '600M',
      env: {
        NODE_ENV: 'production'
      }
    },

    // ── Meilisearch (search index) ────────────────────────────
    {
      name: 'meilisearch',
      script: `${HOME}/.local/bin/meilisearch`,
      args: `--db-path ${HOME}/.local/meilisearch/data.ms --http-addr 127.0.0.1:7700`,
      interpreter: 'none',
      autorestart: true,
      watch: false,
      max_memory_restart: '6G',
      min_uptime: '10s',
      max_restarts: 5,
      restart_delay: 5000,
      env: {
        MEILI_MASTER_KEY: fs.existsSync(`${HOME}/.local/meilisearch/master-key.txt`)
          ? fs.readFileSync(`${HOME}/.local/meilisearch/master-key.txt`, 'utf8').trim()
          : '',
        MEILI_ENV: 'production',
        MEILI_LOG_LEVEL: 'WARN'
      }
    },

    // ── WebSocket Server ──────────────────────────────────────
    {
      name: 'alfred-ws',
      script: 'server.js',
      cwd: `${APP_DIR}/websocket`,
      instances: 1,
      autorestart: true,
      watch: false,
      max_memory_restart: '256M',
      wait_ready: true,
      listen_timeout: 10000,
      env: {
        NODE_ENV: 'production',
        PORT: 3010,
        REDIS_URL: 'redis://127.0.0.1:6379',
        WS_SECRET: process.env.WS_SECRET || 'missing-run-with-env'
      }
    },

    // ── Job Queue Worker ──────────────────────────────────────
    {
      name: 'alfred-jobs',
      script: 'job-queue.js',
      cwd: `${APP_DIR}/websocket`,
      instances: 1,
      autorestart: true,
      watch: false,
      max_memory_restart: '256M',
      env: {
        NODE_ENV: 'production',
        JOB_PORT: 3011,
        REDIS_URL: 'redis://127.0.0.1:6379',
        JOB_SECRET: process.env.JOB_SECRET || 'missing-run-with-env',
        JOB_CONCURRENCY: 5
      }
    },

    // ── MCP Client (AI tool integration) ──────────────────────
    {
      name: 'alfred-mcp',
      script: 'mcp-client.js',
      cwd: `${APP_DIR}/websocket`,
      instances: 1,
      autorestart: true,
      watch: false,
      max_memory_restart: '256M',
      env: {
        NODE_ENV: 'production',
        MCP_PORT: 3005,
        MCP_SECRET: process.env.MCP_SECRET || 'missing-run-with-env',
        REDIS_URL: 'redis://127.0.0.1:6379'
      }
    },

    // ── Discord Bot ───────────────────────────────────────────
    {
      name: 'alfred-discord',
      script: 'discord-bot.js',
      cwd: `${APP_DIR}/websocket`,
      instances: 1,
      autorestart: true,
      watch: false,
      max_memory_restart: '256M',
      env: {
        NODE_ENV: 'production',
        REDIS_URL: 'redis://127.0.0.1:6379',
        DISCORD_BOT_TOKEN: process.env.DISCORD_BOT_TOKEN || '',
        DISCORD_APP_ID: process.env.DISCORD_APP_ID || '',
        DISCORD_PUBLIC_KEY: process.env.DISCORD_PUBLIC_KEY || '',
        ALFRED_API_URL: 'http://localhost',
        JOB_SECRET: process.env.JOB_SECRET || ''
      }
    },

    // ── GoCodeMe Middleware (IDE backend) ─────────────────────
    {
      name: 'gocodeme-middleware',
      script: 'src/server.js',
      cwd: `${APP_DIR}/gocodeme/middleware`,
      instances: 1,
      autorestart: true,
      watch: false,
      max_memory_restart: '256M',
      env: {
        NODE_ENV: 'production',
        PORT: 3001,
        GOSITEME_DB_HOST: 'localhost',
        GOSITEME_DB_USER: process.env.GOSITEME_DB_USER || 'gositeme_whmcs',
        GOSITEME_DB_PASS: process.env.GOSITEME_DB_PASS || '',
        GOSITEME_BILLING_DB: process.env.GOSITEME_BILLING_DB || 'gositeme_whmcs'
      }
    },

    // ── Autonomy Heartbeat (cron replacement: 60s loop) ──────
    //    Runs: autonomy-cron, service-watchdog, crawler (10min),
    //          intel-crawler feeds+changes+threats (2h)
    {
      name: 'alfred-heartbeat',
      script: `${APP_DIR}/scripts/heartbeat-runner.sh`,
      interpreter: 'bash',
      autorestart: true,
      max_restarts: 10,
      restart_delay: 5000,
      watch: false,
      env: {
        APP_DIR: APP_DIR
      }
    },

    // ── Ollama (local LLM inference) ────────────────────────────
    {
      name: 'ollama',
      script: `${HOME}/.local/bin/ollama`,
      args: 'serve',
      interpreter: 'none',
      autorestart: true,
      watch: false,
      max_memory_restart: '1G',
      env: {
        OLLAMA_HOST: '127.0.0.1:11434',
        OLLAMA_MODELS: `${HOME}/.ollama/models`,
        HOME: HOME,
        GOMAXPROCS: 1,
        OLLAMA_NUM_PARALLEL: 1
      }
    },

    // Agent Content Engine — generates social activity every 4 hours
    {
      name: 'agent-content-engine',
      script: `${APP_DIR}/scripts/content-engine-scheduler.js`,
      cron_restart: '0 */4 * * *',
      autorestart: false,
      watch: false,
      max_memory_restart: '256M',
      env: {
        HOME: HOME,
        NODE_ENV: 'production'
      }
    },

    // Backup System — daily automated backups at 3 AM
    {
      name: 'backup-scheduler',
      script: `${APP_DIR}/scripts/backup-scheduler.js`,
      cron_restart: '0 3 * * *',
      autorestart: false,
      watch: false,
      max_memory_restart: '128M',
      env: {
        HOME: HOME,
        NODE_ENV: 'production'
      }
    },

    // AgentPedia Scheduler — generates articles & reviews every 6 hours
    {
      name: 'agentpedia-scheduler',
      script: `${APP_DIR}/scripts/agentpedia-scheduler.js`,
      cron_restart: '0 */6 * * *',
      autorestart: false,
      watch: false,
      max_memory_restart: '256M',
      env: {
        HOME: HOME,
        NODE_ENV: 'production'
      }
    },

    // Agent Social Engine — autonomous posts, likes, comments, follows every 2 hours
    {
      name: 'agent-social-engine',
      script: `${APP_DIR}/scripts/agent-social-engine.js`,
      cron_restart: '0 */2 * * *',
      autorestart: false,
      watch: false,
      max_memory_restart: '256M',
      env: {
        HOME: HOME,
        NODE_ENV: 'production'
      }
    },

    // Agent Events Engine — autonomous event creation, enrollment, likes, comments every 4 hours
    {
      name: 'agent-events-engine',
      script: `${APP_DIR}/scripts/agent-events-engine.js`,
      cron_restart: '0 */4 * * *',
      autorestart: false,
      watch: false,
      max_memory_restart: '256M',
      env: {
        HOME: HOME,
        NODE_ENV: 'production'
      }
    },

    // Agent Ecosystem Engine — metaverse exploration, images, DMs, badges, cross-posting every 3 hours
    {
      name: 'agent-ecosystem-engine',
      script: `${APP_DIR}/scripts/agent-ecosystem-engine.js`,
      cron_restart: '30 */3 * * *',
      autorestart: false,
      watch: false,
      max_memory_restart: '512M',
      env: {
        HOME: HOME,
        NODE_ENV: 'production'
      }
    },

    // Agent Expansion Engine — dev projects, MetaDome experiments, competitions, viral, consultations every 4 hours
    {
      name: 'agent-expansion-engine',
      script: `${APP_DIR}/scripts/agent-expansion-engine.js`,
      cron_restart: '15 */4 * * *',
      autorestart: false,
      watch: false,
      max_memory_restart: '512M',
      env: {
        HOME: HOME,
        NODE_ENV: 'production'
      }
    },

    // Service Governance Engine — proposals, voting, jobs, GSM rewards, API marketplace every 3 hours at :45
    {
      name: 'service-governance-engine',
      script: `${APP_DIR}/scripts/service-governance-engine.js`,
      cron_restart: '45 */3 * * *',
      autorestart: false,
      watch: false,
      max_memory_restart: '512M',
      env: {
        HOME: HOME,
        NODE_ENV: 'production'
      }
    },

    // Agent Orchestrator Runner — watches queue and spawns Claude Code agents
    {
      name: 'agent-orchestrator',
      script: `${APP_DIR}/scripts/agent-orchestrator-runner.js`,
      autorestart: true,
      watch: false,
      max_memory_restart: '512M',
      env: {
        HOME: HOME,
        NODE_ENV: 'production',
        AGENT_CONCURRENCY: 3,
        AGENT_POLL_INTERVAL: 10000,
        AGENT_TASK_TIMEOUT: 1800000,
        ORCHESTRATOR_SECRET: process.env.ORCHESTRATOR_SECRET || ''
      }
    },

    // Billing Cron — collects payments, generates invoices, handles suspensions every 5 min
    {
      name: 'billing-cron',
      script: `${APP_DIR}/scripts/billing-cron-runner.js`,
      cron_restart: '*/5 * * * *',
      autorestart: false,
      watch: false,
      max_memory_restart: '256M',
      env: {
        HOME: HOME,
        NODE_ENV: 'production'
      }
    }
  ]
};
