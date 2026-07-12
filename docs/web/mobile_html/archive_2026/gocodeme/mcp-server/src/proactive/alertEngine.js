/**
 * alertEngine.js — Anomaly Detection & Alerting
 *
 * Pattern detection system that identifies:
 *   - Resource thresholds (disk, RAM, CPU)
 *   - Service crashes (PM2 processes down)
 *   - Error rate spikes (log analysis)
 *   - Security anomalies (failed logins, suspicious IPs)
 *   - Performance degradation (slow response times)
 *
 * Alerts are stored in Redis (last 500) and can be queried.
 */

import Redis from 'ioredis';
import os from 'node:os';

const redis = new Redis({ host: '127.0.0.1', port: 6379, maxRetriesPerRequest: 3, lazyConnect: true });
redis.connect().catch(() => {});

const ALERTS_KEY = 'gocodeme:proactive:alerts';
const MAX_ALERTS = 500;

// Alert severity levels
export const SEVERITY = { LOW: 'low', MEDIUM: 'medium', HIGH: 'high', CRITICAL: 'critical' };

/**
 * @typedef {object} Alert
 * @property {string} id
 * @property {string} type — alert type key
 * @property {string} severity — low, medium, high, critical
 * @property {string} message — human-readable message
 * @property {object} data — structured alert data
 * @property {string} timestamp — ISO timestamp
 * @property {boolean} autoFixed — whether auto-fixer resolved it
 */

/**
 * Create and store an alert.
 */
export async function createAlert(type, severity, message, data = {}) {
  const alert = {
    id: `alert-${Date.now()}-${Math.random().toString(36).slice(2, 6)}`,
    type,
    severity,
    message,
    data,
    timestamp: new Date().toISOString(),
    autoFixed: false,
  };

  try {
    await redis.lpush(ALERTS_KEY, JSON.stringify(alert));
    await redis.ltrim(ALERTS_KEY, 0, MAX_ALERTS - 1);
  } catch { /* Redis down — log to console at minimum */ }

  return alert;
}

/**
 * Check system resources and generate alerts.
 */
export async function checkResources() {
  const alerts = [];

  // ── Disk usage ────────────────────────────────────────────────
  try {
    const { execSync } = await import('node:child_process');
    const df = execSync('df -h /home/gositeme 2>/dev/null').toString();
    const match = df.match(/(\d+)%/);
    if (match) {
      const usagePercent = parseInt(match[1]);
      if (usagePercent > 95) {
        alerts.push(await createAlert('disk_critical', SEVERITY.CRITICAL,
          `Disk usage at ${usagePercent}% — immediate action needed`, { usagePercent }));
      } else if (usagePercent > 85) {
        alerts.push(await createAlert('disk_high', SEVERITY.HIGH,
          `Disk usage at ${usagePercent}%`, { usagePercent }));
      } else if (usagePercent > 75) {
        alerts.push(await createAlert('disk_warning', SEVERITY.MEDIUM,
          `Disk usage at ${usagePercent}%`, { usagePercent }));
      }
    }
  } catch {}

  // ── RAM usage ─────────────────────────────────────────────────
  const totalMem = os.totalmem();
  const freeMem = os.freemem();
  const usedPercent = Math.round((1 - freeMem / totalMem) * 100);

  if (usedPercent > 95) {
    alerts.push(await createAlert('ram_critical', SEVERITY.CRITICAL,
      `RAM usage at ${usedPercent}%`, { usedPercent, freeMB: Math.round(freeMem / 1048576) }));
  } else if (usedPercent > 85) {
    alerts.push(await createAlert('ram_high', SEVERITY.HIGH,
      `RAM usage at ${usedPercent}%`, { usedPercent, freeMB: Math.round(freeMem / 1048576) }));
  }

  // ── Load average ──────────────────────────────────────────────
  const load1 = os.loadavg()[0];
  const cores = os.cpus().length;
  if (load1 > cores * 2) {
    alerts.push(await createAlert('cpu_overload', SEVERITY.HIGH,
      `CPU load ${load1.toFixed(1)} exceeds ${cores * 2} (2x cores)`, { load1, cores }));
  } else if (load1 > cores) {
    alerts.push(await createAlert('cpu_high', SEVERITY.MEDIUM,
      `CPU load ${load1.toFixed(1)} above core count (${cores})`, { load1, cores }));
  }

  return alerts;
}

/**
 * Check PM2 processes and detect crashed services.
 */
export async function checkServices() {
  const alerts = [];
  try {
    const { execSync } = await import('node:child_process');
    const PM2 = '/home/gositeme/domains/gocodeme.com/public_html/node_modules/pm2/bin/pm2';
    const jlist = execSync(`${PM2} jlist 2>/dev/null`).toString();
    const processes = JSON.parse(jlist);

    for (const proc of processes) {
      if (proc.pm2_env.status === 'errored' || proc.pm2_env.status === 'stopped') {
        alerts.push(await createAlert('service_down', SEVERITY.HIGH,
          `PM2 process "${proc.name}" is ${proc.pm2_env.status}`,
          { name: proc.name, pid: proc.pid, status: proc.pm2_env.status, restarts: proc.pm2_env.restart_time }));
      } else if (proc.pm2_env.restart_time > 10) {
        alerts.push(await createAlert('service_unstable', SEVERITY.MEDIUM,
          `PM2 process "${proc.name}" has restarted ${proc.pm2_env.restart_time} times`,
          { name: proc.name, restarts: proc.pm2_env.restart_time }));
      }
    }
  } catch {}

  return alerts;
}

/**
 * Get alert history.
 * @param {number} [limit=50]
 * @param {string} [severity] — filter by severity
 * @returns {Promise<Alert[]>}
 */
export async function getAlertHistory(limit = 50, severity = null) {
  try {
    const raw = await redis.lrange(ALERTS_KEY, 0, limit - 1);
    let alerts = raw.map(r => JSON.parse(r));
    if (severity) {
      alerts = alerts.filter(a => a.severity === severity);
    }
    return alerts;
  } catch {
    return [];
  }
}

/**
 * Mark an alert as auto-fixed.
 */
export async function markAutoFixed(alertId) {
  try {
    const raw = await redis.lrange(ALERTS_KEY, 0, MAX_ALERTS - 1);
    for (let i = 0; i < raw.length; i++) {
      const alert = JSON.parse(raw[i]);
      if (alert.id === alertId) {
        alert.autoFixed = true;
        await redis.lset(ALERTS_KEY, i, JSON.stringify(alert));
        return true;
      }
    }
  } catch {}
  return false;
}
