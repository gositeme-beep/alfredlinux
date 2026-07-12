/**
 * projectSnapshot.js — Project Status Snapshot
 *
 * Takes a comprehensive snapshot of a project's health, structure, and state.
 * Combines file counts, dependency info, git status, disk usage, and more
 * into a single unified view.
 *
 * Alfred can use this to quickly understand a project before working on it.
 */

import { exec as execCb } from 'node:child_process';
import { promisify } from 'node:util';
import { readFile, readdir, stat } from 'node:fs/promises';
import path from 'node:path';

const exec = promisify(execCb);
const TIMEOUT = 15000;

async function tryExec(cmd, cwd) {
  try {
    const { stdout } = await exec(cmd, { cwd, timeout: TIMEOUT, maxBuffer: 256 * 1024 });
    return stdout.trim();
  } catch {
    return null;
  }
}

async function tryReadJson(filePath) {
  try {
    return JSON.parse(await readFile(filePath, 'utf-8'));
  } catch {
    return null;
  }
}

/**
 * Take a full project snapshot.
 *
 * @param {string} homeDir — user's home directory (e.g., /home/gositeme)
 * @param {string} [projectPath] — specific project subdirectory (relative to homeDir/public_html)
 * @returns {Promise<object>}
 */
export async function takeSnapshot(homeDir, projectPath = '') {
  const baseDir = path.join(homeDir, 'public_html', projectPath);
  const snapshot = {
    path: baseDir,
    timestamp: new Date().toISOString(),
    structure: {},
    dependencies: {},
    git: {},
    disk: {},
    environment: {},
    health: [],
  };

  // ── File structure analysis ──────────────────────────────────────────
  try {
    const fileCount = await tryExec(`find . -type f | wc -l`, baseDir);
    const dirCount = await tryExec(`find . -type d | wc -l`, baseDir);
    const extensionBreakdown = await tryExec(
      `find . -type f -name '*.*' | sed 's/.*\\.//' | sort | uniq -c | sort -rn | head -15`,
      baseDir
    );

    snapshot.structure = {
      total_files: parseInt(fileCount) || 0,
      total_dirs: parseInt(dirCount) || 0,
      top_extensions: extensionBreakdown
        ? extensionBreakdown.split('\n').filter(Boolean).map(l => {
            const [count, ext] = l.trim().split(/\s+/);
            return { extension: `.${ext}`, count: parseInt(count) };
          })
        : [],
    };

    // Detect project type
    const entries = await readdir(baseDir).catch(() => []);
    const entrySet = new Set(entries);
    const types = [];
    if (entrySet.has('package.json')) types.push('node');
    if (entrySet.has('composer.json')) types.push('php/composer');
    if (entrySet.has('requirements.txt') || entrySet.has('setup.py') || entrySet.has('pyproject.toml')) types.push('python');
    if (entrySet.has('Gemfile')) types.push('ruby');
    if (entrySet.has('go.mod')) types.push('go');
    if (entrySet.has('Cargo.toml')) types.push('rust');
    if (entrySet.has('wp-config.php') || entrySet.has('wp-content')) types.push('wordpress');
    if (entrySet.has('.env')) types.push('env-configured');
    if (entrySet.has('Dockerfile') || entrySet.has('docker-compose.yml')) types.push('docker');
    snapshot.structure.project_types = types;
  } catch (err) {
    snapshot.health.push({ level: 'warn', message: `Structure analysis failed: ${err.message}` });
  }

  // ── Dependencies ─────────────────────────────────────────────────────
  try {
    // Node.js
    const pkg = await tryReadJson(path.join(baseDir, 'package.json'));
    if (pkg) {
      snapshot.dependencies.node = {
        name: pkg.name,
        version: pkg.version,
        dependencies: Object.keys(pkg.dependencies || {}).length,
        devDependencies: Object.keys(pkg.devDependencies || {}).length,
        scripts: Object.keys(pkg.scripts || {}),
        engines: pkg.engines || null,
      };
    }

    // PHP/Composer
    const composer = await tryReadJson(path.join(baseDir, 'composer.json'));
    if (composer) {
      snapshot.dependencies.php = {
        name: composer.name,
        require: Object.keys(composer.require || {}).length,
        requireDev: Object.keys(composer['require-dev'] || {}).length,
      };
    }
  } catch { /* non-fatal */ }

  // ── Git status ────────────────────────────────────────────────────────
  try {
    const branch = await tryExec('git branch --show-current', baseDir);
    const statusCount = await tryExec('git status --short | wc -l', baseDir);
    const lastCommit = await tryExec('git log --oneline -1', baseDir);
    const totalCommits = await tryExec('git rev-list --count HEAD', baseDir);

    if (branch !== null) {
      snapshot.git = {
        is_repo: true,
        branch,
        uncommitted_changes: parseInt(statusCount) || 0,
        last_commit: lastCommit,
        total_commits: parseInt(totalCommits) || 0,
      };
    } else {
      snapshot.git = { is_repo: false };
    }
  } catch {
    snapshot.git = { is_repo: false };
  }

  // ── Disk usage ────────────────────────────────────────────────────────
  try {
    const duOutput = await tryExec(`du -sh .`, baseDir);
    const inodeOutput = await tryExec(`find . -maxdepth 3 | wc -l`, baseDir);

    snapshot.disk = {
      total_size: duOutput ? duOutput.split('\t')[0] : 'unknown',
      approx_inodes: parseInt(inodeOutput) || 0,
    };
  } catch { /* non-fatal */ }

  // ── Environment checks ────────────────────────────────────────────────
  try {
    const nodeVersion = await tryExec('node --version', baseDir);
    const npmVersion = await tryExec('npm --version', baseDir);
    const phpVersion = await tryExec('php --version | head -1', baseDir);
    const gitVersion = await tryExec('git --version', baseDir);

    snapshot.environment = {
      node: nodeVersion,
      npm: npmVersion,
      php: phpVersion,
      git: gitVersion,
    };
  } catch { /* non-fatal */ }

  // ── Health checks ─────────────────────────────────────────────────────
  if (snapshot.structure.total_files > 10000) {
    snapshot.health.push({ level: 'warn', message: `Large project: ${snapshot.structure.total_files} files may slow indexing.` });
  }
  if (snapshot.git.uncommitted_changes > 50) {
    snapshot.health.push({ level: 'warn', message: `${snapshot.git.uncommitted_changes} uncommitted changes — consider committing.` });
  }
  if (snapshot.health.length === 0) {
    snapshot.health.push({ level: 'ok', message: 'No issues detected.' });
  }

  return snapshot;
}
