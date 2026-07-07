/**
 * sentinelEngine.js — SENTINEL: Security Monitoring & Threat Detection Engine
 *
 * Provides real-time security monitoring, vulnerability scanning, threat detection,
 * access control auditing, and incident response capabilities.
 *
 * Capabilities:
 *  - File integrity monitoring (FIM)
 *  - Access log analysis for suspicious activity
 *  - Vulnerability scanning (outdated deps, misconfigs)
 *  - IP reputation checking
 *  - Security policy enforcement
 *  - Incident logging and response
 */

import { randomUUID } from 'node:crypto';
import fs from 'node:fs/promises';
import path from 'node:path';
import { createHash } from 'node:crypto';
import { execFile } from 'node:child_process';
import { promisify } from 'node:util';

const execFileAsync = promisify(execFile);
const SENTINEL_BASE = '/home/gositeme/.gocodeme/sentinel';

async function ensureDir(dir) { await fs.mkdir(dir, { recursive: true }); }
async function loadJSON(file, fallback = {}) {
  try { return JSON.parse(await fs.readFile(file, 'utf8')); } catch { return fallback; }
}
async function saveJSON(file, data) {
  await ensureDir(path.dirname(file));
  await fs.writeFile(file, JSON.stringify(data, null, 2));
}

// ── File Integrity Monitoring ───────────────────────────────────────────────

function fimPath(user) { return path.join(SENTINEL_BASE, user, 'fim_baseline.json'); }
function incidentsPath(user) { return path.join(SENTINEL_BASE, user, 'incidents.json'); }
function policiesPath(user) { return path.join(SENTINEL_BASE, user, 'policies.json'); }

async function hashFile(filePath) {
  const content = await fs.readFile(filePath);
  return createHash('sha256').update(content).digest('hex');
}

export async function createBaseline(user, homeDir, directories) {
  const dirs = directories || ['public_html'];
  const baseline = { created: new Date().toISOString(), files: {} };
  let count = 0;

  for (const dir of dirs) {
    const fullDir = path.join(homeDir, dir);
    const scan = async (d) => {
      try {
        const entries = await fs.readdir(d, { withFileTypes: true });
        for (const e of entries) {
          if (['node_modules', '.git', 'vendor', 'cache'].includes(e.name)) continue;
          const full = path.join(d, e.name);
          if (e.isFile()) {
            const relPath = path.relative(homeDir, full);
            const stat = await fs.stat(full);
            if (stat.size < 5_000_000) { // Skip files > 5MB
              baseline.files[relPath] = {
                hash: await hashFile(full),
                size: stat.size,
                mode: stat.mode.toString(8),
                modified: stat.mtime.toISOString(),
              };
              count++;
            }
          } else if (e.isDirectory()) {
            await scan(full);
          }
        }
      } catch {}
    };
    await scan(fullDir);
  }

  await saveJSON(fimPath(user), baseline);
  return { files_baselined: count, directories: dirs, message: `Baseline created: ${count} files hashed.` };
}

export async function checkIntegrity(user, homeDir) {
  const baseline = await loadJSON(fimPath(user), null);
  if (!baseline) return { error: true, message: 'No baseline found. Run sentinel_create_baseline first.' };

  const changes = { modified: [], added: [], deleted: [], permission_changed: [] };
  const currentFiles = new Set();

  for (const [relPath, info] of Object.entries(baseline.files)) {
    const full = path.join(homeDir, relPath);
    try {
      const stat = await fs.stat(full);
      currentFiles.add(relPath);
      const currentHash = await hashFile(full);
      if (currentHash !== info.hash) {
        changes.modified.push({ path: relPath, old_hash: info.hash.slice(0, 12), new_hash: currentHash.slice(0, 12), size_change: stat.size - info.size });
      }
      if (stat.mode.toString(8) !== info.mode) {
        changes.permission_changed.push({ path: relPath, old_mode: info.mode, new_mode: stat.mode.toString(8) });
      }
    } catch {
      changes.deleted.push({ path: relPath });
    }
  }

  // Check for new files in baselined directories
  const scan = async (dir) => {
    try {
      const entries = await fs.readdir(dir, { withFileTypes: true });
      for (const e of entries) {
        if (['node_modules', '.git', 'vendor', 'cache'].includes(e.name)) continue;
        const full = path.join(dir, e.name);
        const rel = path.relative(homeDir, full);
        if (e.isFile() && !baseline.files[rel]) {
          changes.added.push({ path: rel });
        } else if (e.isDirectory()) {
          await scan(full);
        }
      }
    } catch {}
  };
  await scan(path.join(homeDir, 'public_html'));

  const total = changes.modified.length + changes.added.length + changes.deleted.length + changes.permission_changed.length;
  const severity = total > 20 ? 'CRITICAL' : total > 5 ? 'HIGH' : total > 0 ? 'MEDIUM' : 'CLEAN';

  return {
    severity,
    total_changes: total,
    ...changes,
    baseline_date: baseline.created,
    message: `Integrity check: ${severity} — ${total} change(s) detected.`,
  };
}

// ── Access Log Analysis ─────────────────────────────────────────────────────

export async function analyzeAccessLogs(homeDir, options = {}) {
  const logDir = path.join(homeDir, '..', 'logs');
  const findings = {
    suspicious_ips: [],
    brute_force_attempts: [],
    sql_injection_attempts: [],
    xss_attempts: [],
    path_traversal: [],
    scanner_bots: [],
    high_error_rates: {},
  };

  const patterns = {
    sql_injection: /('|"|;|--|union\s+select|or\s+1\s*=\s*1|drop\s+table)/i,
    xss: /(<script|javascript:|onerror=|onload=|eval\()/i,
    path_traversal: /(\.\.\/(\.\.)?|\/etc\/passwd|\/proc\/|\/var\/log)/i,
    scanner: /(nikto|sqlmap|nmap|masscan|burp|acunetix|nessus|openvas)/i,
  };

  try {
    const files = await fs.readdir(logDir);
    const accessLogs = files.filter(f => f.includes('access') && f.endsWith('.log'));

    for (const logFile of accessLogs.slice(0, 3)) {
      const content = await fs.readFile(path.join(logDir, logFile), 'utf8');
      const lines = content.split('\n').filter(l => l.trim());
      const ipCounts = {};
      const ip4xx = {};

      for (const line of lines.slice(-10000)) { // Last 10k lines
        const ipMatch = line.match(/^(\d+\.\d+\.\d+\.\d+)/);
        if (!ipMatch) continue;
        const ip = ipMatch[1];
        ipCounts[ip] = (ipCounts[ip] || 0) + 1;

        const statusMatch = line.match(/ (\d{3}) /);
        const status = statusMatch ? parseInt(statusMatch[1]) : 0;
        if (status >= 400 && status < 500) {
          ip4xx[ip] = (ip4xx[ip] || 0) + 1;
        }

        // Check for attack patterns
        if (patterns.sql_injection.test(line)) findings.sql_injection_attempts.push({ ip, sample: line.slice(0, 200) });
        if (patterns.xss.test(line)) findings.xss_attempts.push({ ip, sample: line.slice(0, 200) });
        if (patterns.path_traversal.test(line)) findings.path_traversal.push({ ip, sample: line.slice(0, 200) });
        if (patterns.scanner.test(line)) findings.scanner_bots.push({ ip, agent: line.slice(0, 200) });
      }

      // Brute force detection (>100 requests from single IP)
      for (const [ip, count] of Object.entries(ipCounts)) {
        if (count > 100) findings.brute_force_attempts.push({ ip, requests: count });
      }

      // High 4xx rates
      for (const [ip, count] of Object.entries(ip4xx)) {
        if (count > 20) findings.suspicious_ips.push({ ip, error_count: count, total: ipCounts[ip] || count });
      }
    }
  } catch {}

  // Deduplicate
  findings.sql_injection_attempts = findings.sql_injection_attempts.slice(0, 20);
  findings.xss_attempts = findings.xss_attempts.slice(0, 20);
  findings.path_traversal = findings.path_traversal.slice(0, 20);
  findings.scanner_bots = [...new Map(findings.scanner_bots.map(b => [b.ip, b])).values()];

  const totalThreats = findings.sql_injection_attempts.length + findings.xss_attempts.length +
    findings.path_traversal.length + findings.brute_force_attempts.length;
  const severity = totalThreats > 50 ? 'CRITICAL' : totalThreats > 10 ? 'HIGH' : totalThreats > 0 ? 'MEDIUM' : 'CLEAN';

  return { severity, total_threats: totalThreats, ...findings, message: `Log analysis: ${severity} — ${totalThreats} threat(s) detected.` };
}

// ── Vulnerability Scanning ──────────────────────────────────────────────────

export async function vulnerabilityScan(homeDir) {
  const vulns = [];

  // Check for common misconfigurations
  const checks = [
    { file: 'public_html/.env', issue: '.env file exposed in web root', severity: 'CRITICAL' },
    { file: 'public_html/wp-config.php', check: async (f) => {
      const c = await fs.readFile(f, 'utf8');
      return c.includes("define('WP_DEBUG', true)") ? 'WP_DEBUG enabled in production' : null;
    }, severity: 'HIGH' },
    { file: 'public_html/.git/config', issue: '.git directory exposed', severity: 'CRITICAL' },
    { file: 'public_html/phpinfo.php', issue: 'phpinfo.php exposed', severity: 'HIGH' },
    { file: 'public_html/adminer.php', issue: 'Database admin tool exposed', severity: 'CRITICAL' },
    { file: 'public_html/phpmyadmin', issue: 'phpMyAdmin directory exposed', severity: 'HIGH', isDir: true },
  ];

  for (const check of checks) {
    const full = path.join(homeDir, check.file);
    try {
      const stat = await fs.stat(full);
      if (stat) {
        if (check.check) {
          const result = await check.check(full);
          if (result) vulns.push({ file: check.file, issue: result, severity: check.severity });
        } else {
          vulns.push({ file: check.file, issue: check.issue, severity: check.severity });
        }
      }
    } catch {} // File doesn't exist = not vulnerable
  }

  // Check file permissions
  const permChecks = [
    { file: 'public_html/.htaccess', max_mode: 0o644 },
    { file: 'public_html/wp-config.php', max_mode: 0o640 },
  ];
  for (const pc of permChecks) {
    try {
      const stat = await fs.stat(path.join(homeDir, pc.file));
      const mode = stat.mode & 0o777;
      if (mode > pc.max_mode) {
        vulns.push({ file: pc.file, issue: `Permissions too open (${mode.toString(8)} > ${pc.max_mode.toString(8)})`, severity: 'MEDIUM' });
      }
    } catch {}
  }

  // Check for outdated Node.js dependencies
  try {
    const pkg = JSON.parse(await fs.readFile(path.join(homeDir, 'public_html', 'package.json'), 'utf8'));
    const deps = { ...pkg.dependencies, ...pkg.devDependencies };
    for (const [name, version] of Object.entries(deps)) {
      if (version.startsWith('^0.') || version.startsWith('~0.')) {
        vulns.push({ file: 'package.json', issue: `Dependency "${name}" at unstable v0.x`, severity: 'LOW' });
      }
    }
  } catch {}

  const severity = vulns.some(v => v.severity === 'CRITICAL') ? 'CRITICAL' :
    vulns.some(v => v.severity === 'HIGH') ? 'HIGH' :
    vulns.some(v => v.severity === 'MEDIUM') ? 'MEDIUM' : 'CLEAN';

  return { severity, vulnerabilities: vulns, count: vulns.length, message: `Vulnerability scan: ${severity} — ${vulns.length} issue(s) found.` };
}

// ── IP Reputation ───────────────────────────────────────────────────────────

export async function checkIpReputation(ip) {
  // Check against local blocklist and known patterns
  const results = { ip, checks: [] };

  // Known bad ranges (simplified)
  const suspiciousRanges = [
    { range: '0.0.0.0/8', reason: 'Invalid/reserved' },
    { range: '10.0.0.0/8', reason: 'Private network' },
    { range: '127.0.0.0/8', reason: 'Loopback' },
    { range: '192.168.0.0/16', reason: 'Private network' },
  ];

  for (const r of suspiciousRanges) {
    const [network, bits] = r.range.split('/');
    const netParts = network.split('.').map(Number);
    const ipParts = ip.split('.').map(Number);
    const mask = parseInt(bits);
    let match = true;
    for (let i = 0; i < Math.floor(mask / 8); i++) {
      if (netParts[i] !== ipParts[i]) { match = false; break; }
    }
    if (match) results.checks.push({ check: 'range', status: 'suspicious', reason: r.reason });
  }

  // Try AbuseIPDB-style check via public API
  try {
    const resp = await fetch(`https://api.abuseipdb.com/api/v2/check?ipAddress=${ip}`, {
      headers: { Key: process.env.ABUSEIPDB_KEY || '', Accept: 'application/json' },
      signal: AbortSignal.timeout(5000),
    });
    if (resp.ok) {
      const data = await resp.json();
      if (data.data?.abuseConfidenceScore > 50) {
        results.checks.push({ check: 'abuseipdb', status: 'malicious', score: data.data.abuseConfidenceScore });
      }
    }
  } catch {}

  results.reputation = results.checks.length > 0 ? 'suspicious' : 'clean';
  results.message = `IP ${ip}: ${results.reputation} (${results.checks.length} finding(s))`;
  return results;
}

// ── Incident Management ─────────────────────────────────────────────────────

export async function logIncident(user, incident) {
  const incidents = await loadJSON(incidentsPath(user), { incidents: [] });
  const id = `inc_${randomUUID().slice(0, 8)}`;
  incidents.incidents.unshift({
    id,
    type: incident.type || 'general',
    severity: incident.severity || 'MEDIUM',
    title: incident.title,
    description: incident.description || '',
    source_ip: incident.source_ip || null,
    affected_files: incident.affected_files || [],
    status: 'open',
    created: new Date().toISOString(),
    resolved: null,
    resolution: null,
  });
  if (incidents.incidents.length > 1000) incidents.incidents = incidents.incidents.slice(0, 1000);
  await saveJSON(incidentsPath(user), incidents);
  return { id, message: `Incident "${incident.title}" logged (${incident.severity}).` };
}

export async function listIncidents(user, status) {
  const incidents = await loadJSON(incidentsPath(user), { incidents: [] });
  let list = incidents.incidents;
  if (status) list = list.filter(i => i.status === status);
  return {
    incidents: list.slice(0, 50),
    total: list.length,
    open: incidents.incidents.filter(i => i.status === 'open').length,
    message: `${list.length} incident(s)${status ? ` (${status})` : ''}.`,
  };
}

export async function resolveIncident(user, incidentId, resolution) {
  const incidents = await loadJSON(incidentsPath(user), { incidents: [] });
  const inc = incidents.incidents.find(i => i.id === incidentId);
  if (!inc) return { message: `Incident ${incidentId} not found.` };
  inc.status = 'resolved';
  inc.resolved = new Date().toISOString();
  inc.resolution = resolution;
  await saveJSON(incidentsPath(user), incidents);
  return { message: `Incident "${inc.title}" resolved.` };
}

// ── Security Policies ───────────────────────────────────────────────────────

export async function setPolicy(user, policy) {
  const policies = await loadJSON(policiesPath(user), { policies: {} });
  const id = policy.id || `pol_${randomUUID().slice(0, 8)}`;
  policies.policies[id] = {
    id,
    name: policy.name,
    type: policy.type,  // 'file_access', 'ip_whitelist', 'rate_limit', 'content_security'
    rules: policy.rules || [],
    active: true,
    created: new Date().toISOString(),
  };
  await saveJSON(policiesPath(user), policies);
  return { id, message: `Policy "${policy.name}" set.` };
}

export async function listPolicies(user) {
  const policies = await loadJSON(policiesPath(user), { policies: {} });
  return {
    policies: Object.values(policies.policies).map(p => ({
      id: p.id, name: p.name, type: p.type, active: p.active, rules_count: p.rules.length,
    })),
    message: `${Object.keys(policies.policies).length} security policy(s).`,
  };
}
