'use strict';
/**
 * Health Monitor — crash alerts, uptime checks, Redis TTL hygiene
 *
 * Responsibilities:
 * 1. Self-ping /health every 60s — if it fails, alert via WHMCS email
 * 2. Redis hygiene — enforce TTLs on keys that should expire
 * 3. Memory & budget threshold alerts
 * 4. Expose /api/admin/health for external monitors (UptimeRobot, etc.)
 */

const http = require('http');
const { getRedis } = require('../redis');
const { callWhmcs } = require('../billing/whmcs');
const logger = require('../logger');

const ADMIN_EMAIL = process.env.ADMIN_EMAIL || 'gositeme@gmail.com';
const CHECK_INTERVAL = parseInt(process.env.HEALTH_CHECK_INTERVAL, 10) || 60_000;
const PORT = parseInt(process.env.PORT, 10) || 3001;

// ── Alert dedup: max 1 email per issue per 30 minutes ──────────────────────
const alertCooldowns = new Map();
const COOLDOWN_MS = 30 * 60 * 1000;

async function sendAlert(subject, message) {
  const key = subject;
  const last = alertCooldowns.get(key) || 0;
  if (Date.now() - last < COOLDOWN_MS) return; // still in cooldown
  alertCooldowns.set(key, Date.now());

  logger.warn(`[HealthMonitor] ALERT: ${subject} — ${message}`);

  try {
    await callWhmcs('SendAdminEmail', {
      customsubject: `🚨 GoCodeMe: ${subject}`,
      custommessage: `${message}\n\nTimestamp: ${new Date().toISOString()}\nServer: ${require('os').hostname()}`,
      type: 'system',
    });
  } catch (e) {
    // Fallback: try SendEmail to admin
    try {
      await callWhmcs('SendEmail', {
        customtype: 'general',
        customsubject: `🚨 GoCodeMe: ${subject}`,
        custommessage: `<p>${message.replace(/\n/g, '<br>')}</p><p>Time: ${new Date().toISOString()}</p>`,
        id: 1, // admin client id
      });
    } catch (e2) {
      logger.error('[HealthMonitor] Failed to send alert email:', e2.message);
    }
  }
}

// ── 1. Self-ping health check ──────────────────────────────────────────────
let consecutiveFailures = 0;

function selfPing() {
  return new Promise((resolve) => {
    const req = http.get(`http://127.0.0.1:${PORT}/health`, { timeout: 5000 }, (res) => {
      let body = '';
      res.on('data', (c) => { body += c; });
      res.on('end', () => {
        try {
          const data = JSON.parse(body);
          if (data.ok) {
            if (consecutiveFailures > 0) {
              logger.info(`[HealthMonitor] Recovered after ${consecutiveFailures} failures`);
            }
            consecutiveFailures = 0;
            resolve(true);
          } else {
            resolve(false);
          }
        } catch {
          resolve(false);
        }
      });
    });
    req.on('error', () => resolve(false));
    req.on('timeout', () => { req.destroy(); resolve(false); });
  });
}

async function runHealthCheck() {
  const ok = await selfPing();
  if (!ok) {
    consecutiveFailures++;
    if (consecutiveFailures >= 3) {
      await sendAlert('Middleware Down', 
        `Health check failed ${consecutiveFailures} consecutive times.\nEndpoint: http://127.0.0.1:${PORT}/health\nAction required: check PM2 logs.`);
    }
  }
}

// ── 2. Redis TTL Hygiene ────────────────────────────────────────────────────
// Keys that SHOULD have TTLs but might not (set reasonable defaults)
const TTL_POLICIES = [
  { pattern: 'usage:*',           ttl: 40 * 24 * 3600 },  // 40 days (monthly + buffer)
  { pattern: 'mcp:tokens:*',      ttl: 40 * 24 * 3600 },  // 40 days
  { pattern: 'onboarding:*',      ttl: 90 * 24 * 3600 },  // 90 days
  { pattern: 'launch:*',          ttl: 7 * 24 * 3600 },   // 7 days
  { pattern: 'workspace:*',       ttl: 7 * 24 * 3600 },   // 7 days
  { pattern: 'budget:daily:*',    ttl: 2 * 24 * 3600 },   // 2 days
  { pattern: 'billing:daily:*',   ttl: 2 * 24 * 3600 },   // 2 days
  { pattern: 'model_mode:*',      ttl: 90 * 24 * 3600 },  // 90 days
  { pattern: 'dedup:*',           ttl: 60 },               // 60 seconds
  { pattern: 'email:*',           ttl: 90 * 24 * 3600 },  // 90 days
  { pattern: 'referral:*',        ttl: 365 * 24 * 3600 },  // 1 year
  { pattern: 'team:*',            ttl: 365 * 24 * 3600 },  // 1 year
  { pattern: 'apikey:rate:*',     ttl: 120 },              // 2 minutes
  { pattern: 'apikey:usage:*',    ttl: 40 * 24 * 3600 },  // 40 days
  { pattern: 'reseller:revenue:*', ttl: 40 * 24 * 3600 }, // 40 days
];

// Keys that should NEVER expire (core state)
const PERMANENT_PREFIXES = [
  'tokens:limit:',    // plan limits
  'tokens:used:',     // resets monthly by billing code
  'access:',          // active plan mapping
  'da_username:',     // DA ↔ client mapping
  'client_id_by_da:', // reverse mapping
  'apikey:',          // API key hashes (except rate/usage)
  'apikeys:client:',  // key indexes
  'reseller:',        // reseller config (except revenue)
  'team:by_',         // team lookups
];

function isPermanent(key) {
  // apikey:rate: and apikey:usage: are NOT permanent
  if (key.startsWith('apikey:rate:') || key.startsWith('apikey:usage:')) return false;
  // reseller:revenue: is NOT permanent
  if (key.startsWith('reseller:revenue:')) return false;
  return PERMANENT_PREFIXES.some(p => key.startsWith(p));
}

async function enforceRedisHygiene() {
  const redis = getRedis();
  if (!redis) return;

  let fixed = 0;
  try {
    // SECURITY (R3 M-03): Use SCAN instead of KEYS
    const scanKeys = require('../utils/scanKeys');
    for (const policy of TTL_POLICIES) {
      const keys = await scanKeys(redis, policy.pattern);
      for (const key of keys) {
        if (isPermanent(key)) continue;
        const ttl = await redis.ttl(key);
        if (ttl === -1) { // no TTL set
          await redis.expire(key, policy.ttl);
          fixed++;
        }
      }
    }
    if (fixed > 0) {
      logger.info(`[HealthMonitor] Set TTL on ${fixed} keys`);
    }
  } catch (e) {
    logger.error('[HealthMonitor] Redis hygiene error:', e.message);
  }
}

// ── 3. Redis memory check ──────────────────────────────────────────────────
async function checkRedisMemory() {
  const redis = getRedis();
  if (!redis) return;

  try {
    const info = await redis.info('memory');
    const usedMatch = info.match(/used_memory:(\d+)/);
    const maxMatch = info.match(/maxmemory:(\d+)/);
    if (usedMatch && maxMatch) {
      const used = parseInt(usedMatch[1], 10);
      const max = parseInt(maxMatch[1], 10);
      if (max > 0) {
        const pct = (used / max) * 100;
        if (pct > 85) {
          await sendAlert('Redis Memory Critical',
            `Redis is at ${pct.toFixed(1)}% memory (${(used / 1024 / 1024).toFixed(1)}MB / ${(max / 1024 / 1024).toFixed(0)}MB).\nEviction policy is active but consider cleaning up.`);
        }
      }
    }
  } catch (e) {
    logger.error('[HealthMonitor] Redis memory check error:', e.message);
  }
}

// ── 4. Express route for external monitors ─────────────────────────────────
function healthRoute(router) {
  const { Router } = require('express');
  const r = router || Router();

  // Detailed health for admin only
  // SECURITY (R3-01): Use timing-safe comparison via shared middleware
  const { requireWhmcsSecret: _hmSecret } = require('../auth/whmcsSecret');
  r.get('/api/admin/health', _hmSecret, async (req, res) => {
    try {
      const redis = getRedis();
      const redisOk = redis ? await redis.ping().then(() => true).catch(() => false) : false;
      const info = redis ? await redis.info('memory').catch(() => '') : '';
      const usedMatch = info.match(/used_memory_human:(.+)/);
      const maxMatch = info.match(/maxmemory_human:(.+)/);
      const keyCount = redis ? await redis.dbsize().catch(() => 0) : 0;

      const os = require('os');
      const uptime = process.uptime();

      res.json({
        ok: true,
        service: 'gocodeme-middleware',
        uptime: `${Math.floor(uptime / 3600)}h ${Math.floor((uptime % 3600) / 60)}m`,
        node: {
          version: process.version,
          memory: `${Math.round(process.memoryUsage().heapUsed / 1024 / 1024)}MB`,
          pid: process.pid,
        },
        redis: {
          connected: redisOk,
          memory: usedMatch ? usedMatch[1].trim() : 'unknown',
          maxMemory: maxMatch ? maxMatch[1].trim() : 'unknown',
          keys: keyCount,
        },
        system: {
          hostname: os.hostname(),
          loadAvg: os.loadavg().map(l => l.toFixed(2)),
          freeMemGB: (os.freemem() / 1024 / 1024 / 1024).toFixed(1),
          totalMemGB: (os.totalmem() / 1024 / 1024 / 1024).toFixed(1),
        },
        checks: {
          consecutiveFailures,
          lastCheck: new Date().toISOString(),
        },
      });
    } catch (e) {
      res.status(500).json({ ok: false, error: e.message });
    }
  });

  return r;
}

// ── Start monitoring loops ─────────────────────────────────────────────────
let healthInterval = null;
let hygieneInterval = null;

function startMonitoring() {
  logger.info(`[HealthMonitor] Starting — check every ${CHECK_INTERVAL / 1000}s`);

  // Health check every 60s
  healthInterval = setInterval(async () => {
    try {
      await runHealthCheck();
      await checkRedisMemory();
    } catch (e) {
      logger.error('[HealthMonitor] Health check error:', e.message);
    }
  }, CHECK_INTERVAL);

  // Redis TTL hygiene every 6 hours
  hygieneInterval = setInterval(async () => {
    try {
      await enforceRedisHygiene();
    } catch (e) {
      logger.error('[HealthMonitor] Redis hygiene error:', e.message);
    }
  }, 6 * 60 * 60 * 1000);

  // Run hygiene once on startup (30s delay)
  setTimeout(() => {
    enforceRedisHygiene().catch(e => logger.error('[HealthMonitor] Initial hygiene error:', e.message));
  }, 30_000);
}

function stopMonitoring() {
  if (healthInterval) clearInterval(healthInterval);
  if (hygieneInterval) clearInterval(hygieneInterval);
}

module.exports = {
  startMonitoring,
  stopMonitoring,
  healthRoute,
  sendAlert,
  enforceRedisHygiene,
  runHealthCheck,
};
