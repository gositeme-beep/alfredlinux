/**
 * monitoringAgent.js — Proactive Monitoring Agent
 *
 * Autonomous monitoring loop that runs checks at configurable intervals.
 * Uses CLOCKWORK scheduler for timing and ELEPHANT memory for context.
 *
 * Monitoring cycles:
 *   - Every 60s: Resource checks (disk, RAM, CPU)
 *   - Every 5min: Service health checks (PM2 processes)
 *   - Every 30min: Project analysis (outdated deps, security, etc.)
 *
 * When issues are detected:
 *   1. Alert is created and stored in Redis
 *   2. If auto-fix is enabled for this type, attempt remediation
 *   3. Summary is available via enable_monitoring / alert_history tools
 */

import { checkResources, checkServices, getAlertHistory } from './alertEngine.js';
import { attemptAutoFix, getAutoFixConfig, setAutoFixConfig } from './autoFixer.js';
import { analyzeProject } from './contextBuilder.js';

// Monitoring state
let monitoringActive = false;
let monitoringTimers = [];
let monitoredProjects = new Set();
let lastCycleResults = null;

/**
 * Run a monitoring cycle.
 */
async function runCycle() {
  const start = Date.now();
  const results = {
    timestamp: new Date().toISOString(),
    resourceAlerts: [],
    serviceAlerts: [],
    projectSuggestions: [],
    autoFixResults: [],
  };

  // 1. Check system resources
  try {
    results.resourceAlerts = await checkResources();
  } catch (err) {
    results.resourceAlerts = [{ error: err.message }];
  }

  // 2. Check services
  try {
    results.serviceAlerts = await checkServices();
  } catch (err) {
    results.serviceAlerts = [{ error: err.message }];
  }

  // 3. Auto-fix any detected issues
  const allAlerts = [...results.resourceAlerts, ...results.serviceAlerts];
  for (const alert of allAlerts) {
    if (!alert.error) {
      try {
        const fixResult = await attemptAutoFix(alert);
        results.autoFixResults.push({ alertId: alert.id, type: alert.type, ...fixResult });
      } catch {}
    }
  }

  results.timing = Date.now() - start;
  lastCycleResults = results;
  return results;
}

/**
 * Run project analysis for monitored projects.
 */
async function runProjectAnalysis() {
  const suggestions = [];
  for (const projectPath of monitoredProjects) {
    try {
      const result = await analyzeProject(projectPath);
      suggestions.push({ projectPath, suggestions: result });
    } catch (err) {
      suggestions.push({ projectPath, error: err.message });
    }
  }
  if (lastCycleResults) {
    lastCycleResults.projectSuggestions = suggestions;
  }
  return suggestions;
}

/**
 * Start proactive monitoring.
 *
 * @param {object} opts
 * @param {string[]} [opts.projects=[]] — project paths to monitor
 * @param {number} [opts.resourceInterval=60000] — resource check interval (ms)
 * @param {number} [opts.serviceInterval=300000] — service check interval (ms)
 * @param {number} [opts.projectInterval=1800000] — project analysis interval (ms)
 * @returns {object}
 */
export function enableMonitoring(opts = {}) {
  const {
    projects = [],
    resourceInterval = 60_000,
    serviceInterval = 300_000,
    projectInterval = 1_800_000,
  } = opts;

  if (monitoringActive) {
    return {
      status: 'already_active',
      message: 'Monitoring is already running',
      config: getMonitoringStatus(),
    };
  }

  // Register projects
  for (const p of projects) {
    monitoredProjects.add(p);
  }

  // Start monitoring timers
  monitoringTimers = [
    setInterval(() => runCycle().catch(() => {}), resourceInterval),
    setInterval(() => runProjectAnalysis().catch(() => {}), projectInterval),
  ];

  // Unref timers so they don't keep the process alive
  monitoringTimers.forEach(t => t.unref());
  monitoringActive = true;

  // Run first cycle immediately
  runCycle().catch(() => {});

  return {
    status: 'started',
    resourceInterval: `${resourceInterval / 1000}s`,
    serviceInterval: `${serviceInterval / 1000}s`,
    projectInterval: `${projectInterval / 1000}s`,
    monitoredProjects: [...monitoredProjects],
    autoFixConfig: getAutoFixConfig(),
  };
}

/**
 * Stop proactive monitoring.
 */
export function disableMonitoring() {
  for (const timer of monitoringTimers) {
    clearInterval(timer);
  }
  monitoringTimers = [];
  monitoringActive = false;

  return {
    status: 'stopped',
    message: 'Proactive monitoring stopped',
  };
}

/**
 * Get monitoring status and last results.
 */
export function getMonitoringStatus() {
  return {
    active: monitoringActive,
    monitoredProjects: [...monitoredProjects],
    autoFixConfig: getAutoFixConfig(),
    lastCycle: lastCycleResults,
  };
}

/**
 * Get alert history wrapper.
 */
export async function getAlerts(limit = 50, severity = null) {
  return {
    alerts: await getAlertHistory(limit, severity),
    monitoringActive,
  };
}

/**
 * Configure auto-fix settings.
 */
export function configureAutoFix(config) {
  const updated = setAutoFixConfig(config);
  return {
    status: 'updated',
    autoFixConfig: updated,
  };
}
