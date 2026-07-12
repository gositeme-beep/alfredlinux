/**
 * dependencyAudit.js — Dependency Security & Health Auditor
 *
 * Scans project dependencies for vulnerabilities, outdated packages,
 * and license issues. Supports Node.js (npm) and PHP (Composer) projects.
 *
 * Uses CLI tools: npm audit, composer audit, npm outdated, composer outdated.
 */

import { exec as execCb } from 'node:child_process';
import { promisify } from 'node:util';
import { readFile } from 'node:fs/promises';
import { existsSync } from 'node:fs';
import path from 'node:path';

const exec = promisify(execCb);
const TIMEOUT = 60_000;
const MAX_OUTPUT = 128 * 1024;

async function tryExec(cmd, cwd) {
  try {
    const { stdout, stderr } = await exec(cmd, { cwd, timeout: TIMEOUT, maxBuffer: MAX_OUTPUT });
    return { success: true, stdout: stdout.trim(), stderr: stderr.trim() };
  } catch (err) {
    return { success: false, stdout: err.stdout?.trim() || '', stderr: err.stderr?.trim() || '', code: err.code };
  }
}

// ═══════════════════════════════════════════════════════════════════
// NPM AUDIT
// ═══════════════════════════════════════════════════════════════════

async function npmAudit(projectDir) {
  const pkgPath = path.join(projectDir, 'package.json');
  const lockPath = path.join(projectDir, 'package-lock.json');

  if (!existsSync(pkgPath)) return null;

  const result = { type: 'npm', project: projectDir };

  // Read package.json for overview
  try {
    const pkg = JSON.parse(await readFile(pkgPath, 'utf-8'));
    result.name = pkg.name || 'unknown';
    result.version = pkg.version || 'unknown';
    result.deps = Object.keys(pkg.dependencies || {}).length;
    result.devDeps = Object.keys(pkg.devDependencies || {}).length;
  } catch { /* ignore */ }

  // Run npm audit (requires lock file)
  if (existsSync(lockPath)) {
    const audit = await tryExec('npm audit --json 2>/dev/null', projectDir);
    if (audit.stdout) {
      try {
        const data = JSON.parse(audit.stdout);
        result.audit = {
          vulnerabilities: data.metadata?.vulnerabilities || {},
          total: data.metadata?.vulnerabilities
            ? Object.values(data.metadata.vulnerabilities).reduce((a, b) => a + b, 0)
            : 0,
          advisories: Object.values(data.vulnerabilities || {}).slice(0, 20).map(v => ({
            name: v.name,
            severity: v.severity,
            title: v.title || v.via?.[0]?.title || 'Unknown',
            range: v.range,
            fixAvailable: !!v.fixAvailable,
          })),
        };
      } catch {
        result.audit = { raw: audit.stdout.slice(0, 2000), parse_error: true };
      }
    }
  } else {
    result.audit = { skipped: true, reason: 'No package-lock.json found. Run npm install first.' };
  }

  // Check outdated
  const outdated = await tryExec('npm outdated --json 2>/dev/null', projectDir);
  if (outdated.stdout) {
    try {
      const data = JSON.parse(outdated.stdout);
      result.outdated = Object.entries(data).slice(0, 30).map(([name, info]) => ({
        name,
        current: info.current,
        wanted: info.wanted,
        latest: info.latest,
        type: info.type,
        major: info.current !== info.latest && info.current?.split('.')[0] !== info.latest?.split('.')[0],
      }));
      result.outdatedCount = Object.keys(data).length;
    } catch {
      result.outdated = [];
    }
  } else {
    result.outdated = [];
    result.outdatedCount = 0;
  }

  return result;
}

// ═══════════════════════════════════════════════════════════════════
// COMPOSER AUDIT
// ═══════════════════════════════════════════════════════════════════

async function composerAudit(projectDir) {
  const composerPath = path.join(projectDir, 'composer.json');
  if (!existsSync(composerPath)) return null;

  const result = { type: 'composer', project: projectDir };

  // Read composer.json
  try {
    const pkg = JSON.parse(await readFile(composerPath, 'utf-8'));
    result.name = pkg.name || 'unknown';
    result.deps = Object.keys(pkg.require || {}).length;
    result.devDeps = Object.keys(pkg['require-dev'] || {}).length;
  } catch { /* ignore */ }

  // composer audit (Composer 2.4+)
  const audit = await tryExec('composer audit --format=json 2>/dev/null', projectDir);
  if (audit.success && audit.stdout) {
    try {
      const data = JSON.parse(audit.stdout);
      result.audit = {
        advisories: (data.advisories || []).slice(0, 20).map(a => ({
          package: a.packageName,
          title: a.title,
          cve: a.cve,
          link: a.link,
          affectedVersions: a.affectedVersions,
        })),
        total: (data.advisories || []).length,
      };
    } catch {
      result.audit = { raw: audit.stdout.slice(0, 2000), parse_error: true };
    }
  } else {
    result.audit = { skipped: true, reason: 'composer audit not available or failed.' };
  }

  // composer outdated
  const outdated = await tryExec('composer outdated --format=json --direct 2>/dev/null', projectDir);
  if (outdated.stdout) {
    try {
      const data = JSON.parse(outdated.stdout);
      result.outdated = (data.installed || []).slice(0, 30).map(p => ({
        name: p.name,
        current: p.version,
        latest: p.latest,
        status: p['latest-status'],
        description: p.description?.slice(0, 80),
      }));
      result.outdatedCount = (data.installed || []).length;
    } catch {
      result.outdated = [];
    }
  } else {
    result.outdated = [];
    result.outdatedCount = 0;
  }

  return result;
}

// ═══════════════════════════════════════════════════════════════════
// PIP AUDIT (Python)
// ═══════════════════════════════════════════════════════════════════

async function pipAudit(projectDir) {
  const reqPath = path.join(projectDir, 'requirements.txt');
  const pipfilePath = path.join(projectDir, 'Pipfile');
  const pyproject = path.join(projectDir, 'pyproject.toml');

  if (!existsSync(reqPath) && !existsSync(pipfilePath) && !existsSync(pyproject)) return null;

  const result = { type: 'pip', project: projectDir };

  // Count deps from requirements.txt
  if (existsSync(reqPath)) {
    try {
      const content = await readFile(reqPath, 'utf-8');
      const deps = content.split('\n').filter(l => l.trim() && !l.startsWith('#') && !l.startsWith('-'));
      result.deps = deps.length;
    } catch { /* ignore */ }
  }

  // Try pip-audit if available
  const audit = await tryExec('pip-audit --format=json 2>/dev/null', projectDir);
  if (audit.success && audit.stdout) {
    try {
      const data = JSON.parse(audit.stdout);
      result.audit = {
        vulnerabilities: (data.dependencies || [])
          .filter(d => d.vulns && d.vulns.length > 0)
          .slice(0, 20)
          .map(d => ({
            name: d.name,
            version: d.version,
            vulns: d.vulns.map(v => ({ id: v.id, description: v.description?.slice(0, 100) })),
          })),
        total: (data.dependencies || []).filter(d => d.vulns?.length > 0).length,
      };
    } catch {
      result.audit = { raw: audit.stdout.slice(0, 2000) };
    }
  } else {
    result.audit = { skipped: true, reason: 'pip-audit not installed. Install with: pip install pip-audit' };
  }

  return result;
}

// ═══════════════════════════════════════════════════════════════════
// PUBLIC API
// ═══════════════════════════════════════════════════════════════════

/**
 * Run a full dependency audit on a project directory.
 *
 * @param {string} homeDir    — user home dir
 * @param {string} projectPath — relative path from homeDir/public_html/
 * @returns {Promise<object>}
 */
export async function auditDependencies(homeDir, projectPath = '') {
  const projectDir = path.join(homeDir, 'public_html', projectPath);

  // Run all audits in parallel
  const [npm, composer, pip] = await Promise.all([
    npmAudit(projectDir).catch(e => ({ type: 'npm', error: e.message })),
    composerAudit(projectDir).catch(e => ({ type: 'composer', error: e.message })),
    pipAudit(projectDir).catch(e => ({ type: 'pip', error: e.message })),
  ]);

  const results = [npm, composer, pip].filter(Boolean);

  // Compute summary
  let totalVulnerabilities = 0;
  let totalOutdated = 0;

  for (const r of results) {
    if (r.audit?.total) totalVulnerabilities += r.audit.total;
    if (r.outdatedCount) totalOutdated += r.outdatedCount;
  }

  return {
    project: projectDir,
    timestamp: new Date().toISOString(),
    package_managers: results.map(r => r.type),
    total_vulnerabilities: totalVulnerabilities,
    total_outdated: totalOutdated,
    audits: results,
    health: totalVulnerabilities === 0 ? 'healthy' : totalVulnerabilities <= 3 ? 'warning' : 'critical',
  };
}
