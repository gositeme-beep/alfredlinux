/**
 * contextBuilder.js — Proactive Suggestion Builder
 *
 * Analyzes the current project state and builds proactive suggestions.
 * Examines: package.json outdated deps, security vulnerabilities,
 * code quality issues, performance opportunities.
 */

/**
 * Analyze a project directory and generate suggestions.
 * @param {string} projectPath — path to the project root
 * @returns {Promise<Array<{type: string, priority: string, message: string, detail: string}>>}
 */
export async function analyzeProject(projectPath) {
  const suggestions = [];
  const { execSync } = await import('node:child_process');
  const { existsSync, readFileSync } = await import('node:fs');
  const path = await import('node:path');

  // ── Check for outdated dependencies ─────────────────────────────
  const pkgPath = path.join(projectPath, 'package.json');
  if (existsSync(pkgPath)) {
    try {
      const outdated = execSync('npm outdated --json 2>/dev/null', { cwd: projectPath, timeout: 30000 }).toString();
      const deps = JSON.parse(outdated || '{}');
      const count = Object.keys(deps).length;
      if (count > 0) {
        const critical = Object.entries(deps)
          .filter(([, v]) => v.current !== v.wanted)
          .map(([name, v]) => `${name}: ${v.current} → ${v.wanted}`)
          .slice(0, 5);

        suggestions.push({
          type: 'outdated_deps',
          priority: count > 10 ? 'high' : 'medium',
          message: `${count} outdated dependencies found`,
          detail: critical.join(', ') + (count > 5 ? ` ... and ${count - 5} more` : ''),
        });
      }
    } catch { /* npm outdated exits non-zero when packages are outdated */ }

    // ── Check for security vulnerabilities ──────────────────────────
    try {
      const audit = execSync('npm audit --json 2>/dev/null', { cwd: projectPath, timeout: 30000 }).toString();
      const data = JSON.parse(audit || '{}');
      const vulns = data.metadata?.vulnerabilities || {};
      const totalVulns = (vulns.high || 0) + (vulns.critical || 0);

      if (totalVulns > 0) {
        suggestions.push({
          type: 'security_vulnerabilities',
          priority: 'high',
          message: `${totalVulns} high/critical security vulnerabilities`,
          detail: `Critical: ${vulns.critical || 0}, High: ${vulns.high || 0}, Moderate: ${vulns.moderate || 0}`,
        });
      }
    } catch {}

    // ── Check for missing .env.example ──────────────────────────────
    if (existsSync(path.join(projectPath, '.env')) && !existsSync(path.join(projectPath, '.env.example'))) {
      suggestions.push({
        type: 'missing_env_example',
        priority: 'low',
        message: 'Found .env but no .env.example — team members may miss required env vars',
        detail: 'Create a .env.example with placeholder values',
      });
    }
  }

  // ── Check for large log files ───────────────────────────────────
  try {
    const bigFiles = execSync(
      `find ${projectPath} -name "*.log" -size +50M -printf "%p (%s bytes)\\n" 2>/dev/null`,
      { timeout: 10000 }
    ).toString().trim();

    if (bigFiles) {
      suggestions.push({
        type: 'large_logs',
        priority: 'medium',
        message: 'Large log files detected (>50MB)',
        detail: bigFiles.split('\n').slice(0, 3).join(', '),
      });
    }
  } catch {}

  // ── Check for .git repo health ──────────────────────────────────
  if (existsSync(path.join(projectPath, '.git'))) {
    try {
      const status = execSync('git status --porcelain 2>/dev/null', { cwd: projectPath, timeout: 5000 }).toString();
      const uncommitted = status.split('\n').filter(l => l.trim()).length;
      if (uncommitted > 20) {
        suggestions.push({
          type: 'uncommitted_changes',
          priority: 'medium',
          message: `${uncommitted} uncommitted changes — consider committing or stashing`,
          detail: `Run: git add -A && git commit -m "checkpoint"`,
        });
      }
    } catch {}
  }

  return suggestions;
}
