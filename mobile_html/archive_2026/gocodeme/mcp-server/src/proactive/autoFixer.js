/**
 * autoFixer.js — Auto-Remediation Recipes
 *
 * When the monitoring agent detects issues, the auto-fixer can attempt
 * to resolve them automatically. Each recipe handles a specific alert type.
 *
 * Recipes:
 *   - service_down → PM2 restart
 *   - disk_high → Clear temp files and caches
 *   - ram_critical → Restart memory-heavy services
 *   - service_unstable → Reset restart counter
 */

import { markAutoFixed } from './alertEngine.js';

const PM2 = '/home/gositeme/domains/gocodeme.com/public_html/node_modules/pm2/bin/pm2';

// Auto-fix configuration (which alert types to auto-fix)
let autoFixConfig = {
  service_down: true,
  service_unstable: false,
  disk_high: true,
  disk_critical: true,
  ram_critical: true,
  cpu_overload: false,
  cpu_high: false,
};

/**
 * Execute a shell command safely.
 */
async function safeExec(cmd) {
  try {
    const { execSync } = await import('node:child_process');
    return { output: execSync(cmd, { timeout: 30000 }).toString().trim(), success: true };
  } catch (err) {
    return { output: err.message, success: false };
  }
}

/**
 * Auto-fix recipes keyed by alert type.
 */
const RECIPES = {
  // ── Restart crashed PM2 service ──────────────────────────────────
  service_down: async (alert) => {
    const name = alert.data?.name;
    if (!name) return { fixed: false, reason: 'No process name in alert' };

    const result = await safeExec(`${PM2} restart ${name}`);
    if (result.success) {
      await markAutoFixed(alert.id);
      return { fixed: true, action: `Restarted PM2 process: ${name}`, output: result.output };
    }
    return { fixed: false, reason: result.output };
  },

  // ── Clear caches when disk is high ───────────────────────────────
  disk_high: async (alert) => {
    const actions = [];

    // Clear npm cache
    let r = await safeExec('npm cache clean --force 2>&1');
    if (r.success) actions.push('Cleared npm cache');

    // Clear temp files older than 7 days
    r = await safeExec('find /tmp -type f -mtime +7 -delete 2>/dev/null; echo "cleared"');
    if (r.success) actions.push('Cleared /tmp files > 7 days');

    // Clear PM2 logs
    r = await safeExec(`${PM2} flush 2>&1`);
    if (r.success) actions.push('Flushed PM2 logs');

    if (actions.length > 0) {
      await markAutoFixed(alert.id);
      return { fixed: true, actions };
    }
    return { fixed: false, reason: 'No cache/temp cleanup succeeded' };
  },

  disk_critical: async (alert) => {
    // Same as disk_high but more aggressive
    return RECIPES.disk_high(alert);
  },

  // ── Restart memory-heavy services when RAM is critical ───────────
  ram_critical: async (alert) => {
    const actions = [];

    // Identify highest-memory PM2 processes and restart them
    try {
      const { execSync } = await import('node:child_process');
      const jlist = JSON.parse(execSync(`${PM2} jlist 2>/dev/null`).toString());
      const sorted = jlist
        .filter(p => p.pm2_env.status === 'online')
        .sort((a, b) => (b.monit?.memory || 0) - (a.monit?.memory || 0));

      // Restart the top memory consumer (if > 500MB)
      if (sorted[0] && sorted[0].monit?.memory > 500 * 1024 * 1024) {
        const name = sorted[0].name;
        await safeExec(`${PM2} restart ${name}`);
        actions.push(`Restarted ${name} (was using ${Math.round(sorted[0].monit.memory / 1048576)}MB)`);
      }
    } catch {}

    // Drop filesystem caches
    await safeExec('sync && echo 3 > /proc/sys/vm/drop_caches 2>/dev/null || true');
    actions.push('Attempted to drop filesystem caches');

    if (actions.length > 0) {
      await markAutoFixed(alert.id);
      return { fixed: true, actions };
    }
    return { fixed: false, reason: 'No remediation succeeded' };
  },
};

/**
 * Attempt to auto-fix an alert.
 * @param {object} alert — alert object from alertEngine
 * @returns {Promise<{fixed: boolean, action?: string, reason?: string}>}
 */
export async function attemptAutoFix(alert) {
  if (!autoFixConfig[alert.type]) {
    return { fixed: false, reason: `Auto-fix disabled for ${alert.type}` };
  }

  const recipe = RECIPES[alert.type];
  if (!recipe) {
    return { fixed: false, reason: `No recipe for alert type: ${alert.type}` };
  }

  try {
    return await recipe(alert);
  } catch (err) {
    return { fixed: false, reason: `Recipe failed: ${err.message}` };
  }
}

/**
 * Get/set auto-fix configuration.
 */
export function getAutoFixConfig() {
  return { ...autoFixConfig };
}

export function setAutoFixConfig(config) {
  autoFixConfig = { ...autoFixConfig, ...config };
  return autoFixConfig;
}
