#!/usr/bin/env node
'use strict';

/**
 * GoCodeMe File Sync Daemon
 *
 * Watches the local workspace directory for file changes and syncs them
 * back to the customer's home directory via direct filesystem operations.
 *
 * Usage:
 *   node gocodeme-file-sync.js <da_username> <workspace_path> [domain]
 *
 * The daemon:
 *   - Uses chokidar to watch the workspace recursively
 *   - Debounces rapid changes (500ms per file)
 *   - Copies new/changed files to real docroot
 *   - Creates directories in real docroot
 *   - Deletes removed files/dirs from real docroot
 *   - Skips hidden dirs (.gocodeme, .theia-ide, .cline, .git, etc.)
 *   - Handles binary files natively
 *
 * Uses direct filesystem copy (no API calls).
 */

const fs   = require('fs');
const path = require('path');

// ── Resolve middleware paths ─────────────────────────────────────────────
const SCRIPT_DIR      = __dirname;
const PROJECT_ROOT    = path.resolve(SCRIPT_DIR, '..');
const MIDDLEWARE_ROOT = path.join(PROJECT_ROOT, 'middleware');

// Add middleware node_modules to require path so we can use chokidar etc.
module.paths.unshift(path.join(MIDDLEWARE_ROOT, 'node_modules'));

const chokidar = require('chokidar');

// ── Direct filesystem helpers (replaces DA API) ─────────────────────────
const USER_HOME = `/home/${process.argv[2]}`;  // e.g. /home/gositeme

function remoteToRealPath(remotePath) {
  // remotePath is like "domains/gositeme.com/public_html/index.php"
  // real path is    "/home/gositeme/domains/gositeme.com/public_html/index.php"
  return path.join(USER_HOME, remotePath);
}

// ── CLI args ─────────────────────────────────────────────────────────────
const DA_USERNAME    = process.argv[2];
const WORKSPACE_PATH = process.argv[3];
const DOMAIN_ARG     = process.argv[4] || null;

if (!DA_USERNAME || !WORKSPACE_PATH) {
  console.error('Usage: gocodeme-file-sync.js <da_username> <workspace_path> [domain]');
  process.exit(1);
}

// ── Config ───────────────────────────────────────────────────────────────
const DEBOUNCE_MS  = 500;
const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10 MB — skip huge files

// Directories to ignore (not synced to DA)
const IGNORE_DIRS = new Set([
  '.gocodeme', '.gocodeme-ide', '.theia', '.cline', '.git', 'node_modules', '.npm', '.yarn',
  '__pycache__', '.cache', 'vendor', 'bower_components', '.vscode',
]);

// File patterns to ignore
const IGNORE_PATTERNS = [
  /^\.gocodeme\//,
  /^\.gocodeme-ide\//,
  /^\.theia\//,
  /^\.cline\//,
  /^\.git\//,
  /^\./,           // hidden files at root level
];

// ── State ────────────────────────────────────────────────────────────────
let remotePath = null;  // e.g. "domains/jabela.quebec/public_html" (for legacy single-domain)
const debounceTimers = new Map();
let initialScanComplete = false;
let syncCount = 0;
let errorCount = 0;
let multiDomainMode = false;  // true if workspace has domains/ subdirectory

// ── Logging ──────────────────────────────────────────────────────────────
function log(msg) {
  console.log(`[GoCodeMe Sync] ${msg}`);
}
function logError(msg) {
  console.error(`[GoCodeMe Sync] ERROR: ${msg}`);
  errorCount++;
}

// ── Remote path detection ────────────────────────────────────────────────
async function detectRemotePath() {
  if (DOMAIN_ARG) {
    return `domains/${DOMAIN_ARG}/public_html`;
  }

  // Auto-detect: list domains directory on filesystem
  try {
    const domainsPath = path.join(USER_HOME, 'domains');
    const entries = fs.readdirSync(domainsPath);
    const domainDir = entries.find(e => {
      return e.includes('.') && fs.statSync(path.join(domainsPath, e)).isDirectory();
    });
    if (domainDir) {
      return `domains/${domainDir}/public_html`;
    }
  } catch (err) {
    logError(`Cannot detect domain: ${err.message}`);
  }

  // Fallback
  return 'public_html';
}

// ── Relative path helpers ────────────────────────────────────────────────

/**
 * Convert a local absolute path to the DA remote path.
 *
 * Multi-domain mode (workspace/domains/<domain>/...):
 *   /tmp/workspace/domains/jabela.quebec/index.php → domains/jabela.quebec/public_html/index.php
 *
 * Legacy single-domain mode (workspace/...):
 *   /tmp/workspace/test.php → domains/jabela.quebec/public_html/test.php
 */
function localToRemote(localPath) {
  const rel = path.relative(WORKSPACE_PATH, localPath);
  // Safety: don't sync paths that escape the workspace
  if (rel.startsWith('..') || path.isAbsolute(rel)) return null;

  // Multi-domain mode: workspace/domains/<domain>/file → domains/<domain>/public_html/file
  if (multiDomainMode && rel.startsWith('domains/')) {
    const parts = rel.split(path.sep);
    // parts[0] = "domains", parts[1] = domain name, parts[2...] = file path
    if (parts.length >= 3) {
      const domainName = parts[1];
      const filePath = parts.slice(2).join('/');
      return `domains/${domainName}/public_html/${filePath}`;
    }
    // Just "domains/" or "domains/foo" without a file — skip
    return null;
  }

  // Legacy single-domain mode
  return `${remotePath}/${rel}`;
}

/**
 * Check if a relative path should be ignored.
 */
function shouldIgnore(relPath) {
  if (!relPath) return true;

  // In multi-domain mode, only sync files under domains/<domain>/
  // Ignore files at workspace root that aren't in domains/
  if (multiDomainMode && !relPath.startsWith('domains/')) return true;

  // Check directory components
  const parts = relPath.split(path.sep);
  for (const part of parts) {
    if (IGNORE_DIRS.has(part)) return true;
  }

  // Hidden files/dirs at any level (except .htaccess)
  for (const part of parts) {
    if (part.startsWith('.') && part !== '.htaccess') return true;
  }

  return false;
}

// ── Sync operations (with debounce) ──────────────────────────────────────

function debounce(filePath, fn) {
  const existing = debounceTimers.get(filePath);
  if (existing) clearTimeout(existing);

  debounceTimers.set(filePath, setTimeout(async () => {
    debounceTimers.delete(filePath);
    try {
      await fn();
    } catch (err) {
      logError(`Sync failed for ${path.relative(WORKSPACE_PATH, filePath)}: ${err.message}`);
    }
  }, DEBOUNCE_MS));
}

async function syncFileToDA(localPath) {
  const remoteFile = localToRemote(localPath);
  if (!remoteFile) return;

  const relPath = path.relative(WORKSPACE_PATH, localPath);
  if (shouldIgnore(relPath)) return;

  // Check file size and reject symlinks (VULN-10/11 fix)
  let stat;
  try {
    stat = fs.lstatSync(localPath);
  } catch {
    return; // file gone
  }
  // SECURITY: Never follow symlinks — they could point outside the workspace
  if (stat.isSymbolicLink()) {
    log(`Skipping symlink: ${relPath} (security: symlinks not synced)`);
    return;
  }
  if (!stat.isFile()) return; // skip non-regular files
  if (stat.size > MAX_FILE_SIZE) {
    log(`Skipping large file: ${relPath} (${(stat.size / 1024 / 1024).toFixed(1)} MB)`);
    return;
  }

  // Read file content — use binary for all files
  let content;
  try {
    content = fs.readFileSync(localPath);
  } catch {
    return; // file gone or unreadable
  }

  debounce(localPath, async () => {
    try {
      const realPath = remoteToRealPath(remoteFile);
      const realDir = path.dirname(realPath);
      // Ensure parent directories exist
      fs.mkdirSync(realDir, { recursive: true });
      // Copy file directly
      fs.copyFileSync(localPath, realPath);
      syncCount++;
      log(`↑ ${relPath} → ${path.relative(USER_HOME, realPath)} (${syncCount} synced)`);
    } catch (err) {
      logError(`Copy failed: ${relPath}: ${err.message}`);
    }
  });
}

async function syncDeleteToDA(localPath) {
  const remoteFile = localToRemote(localPath);
  if (!remoteFile) return;

  const relPath = path.relative(WORKSPACE_PATH, localPath);
  if (shouldIgnore(relPath)) return;

  debounce(localPath, async () => {
    try {
      const realPath = remoteToRealPath(remoteFile);
      fs.unlinkSync(realPath);
      syncCount++;
      log(`✗ ${relPath} deleted (${syncCount} synced)`);
    } catch (err) {
      // File might not exist — that's fine
      if (err.code !== 'ENOENT') {
        logError(`Delete failed: ${relPath}: ${err.message}`);
      }
    }
  });
}

async function syncCreateDirToDA(localPath) {
  const remoteDir = localToRemote(localPath);
  if (!remoteDir) return;

  const relPath = path.relative(WORKSPACE_PATH, localPath);
  if (shouldIgnore(relPath)) return;

  debounce(localPath, async () => {
    try {
      const realPath = remoteToRealPath(remoteDir);
      fs.mkdirSync(realPath, { recursive: true });
      log(`📁 ${relPath}/ created`);
    } catch (err) {
      if (err.code !== 'EEXIST') {
        logError(`Mkdir failed: ${relPath}: ${err.message}`);
      }
    }
  });
}

// ── Main ─────────────────────────────────────────────────────────────────
async function main() {
  log(`Starting file sync for user: ${DA_USERNAME}`);
  log(`Workspace: ${WORKSPACE_PATH}`);

  // Check if workspace uses multi-domain layout (workspace/domains/<domain>/...)
  const domainsDir = path.join(WORKSPACE_PATH, 'domains');
  if (fs.existsSync(domainsDir) && fs.statSync(domainsDir).isDirectory()) {
    const entries = fs.readdirSync(domainsDir);
    const domainFolders = entries.filter(e => {
      return e.includes('.') && fs.statSync(path.join(domainsDir, e)).isDirectory();
    });
    if (domainFolders.length > 0) {
      multiDomainMode = true;
      log(`Multi-domain mode: ${domainFolders.length} domain(s) — ${domainFolders.join(', ')}`);
    }
  }

  if (!multiDomainMode) {
    // Detect single remote path (legacy mode)
    remotePath = await detectRemotePath();
    log(`Remote path: ${remotePath}`);
  }

  // Set up chokidar watcher
  const watcher = chokidar.watch(WORKSPACE_PATH, {
    persistent: true,
    ignoreInitial: true,  // Don't fire events for existing files
    depth: 15,
    followSymlinks: false, // SECURITY (VULN-10): Never follow symlinks
    // Ignore hidden dirs and known useless dirs
    ignored: [
      /(^|[\/\\])\..(?!htaccess)/,  // hidden files/dirs except .htaccess
      '**/node_modules/**',
      '**/.git/**',
      '**/__pycache__/**',
      '**/vendor/**',
      '**/.cache/**',
      '**/.npm/**',
      '**/.yarn/**',
    ],
    awaitWriteFinish: {
      stabilityThreshold: 300,
      pollInterval: 100,
    },
  });

  watcher
    .on('add', (filePath) => {
      if (!initialScanComplete) return;
      syncFileToDA(filePath);
    })
    .on('change', (filePath) => {
      syncFileToDA(filePath);
    })
    .on('unlink', (filePath) => {
      syncDeleteToDA(filePath);
    })
    .on('addDir', (dirPath) => {
      if (!initialScanComplete) return;
      if (dirPath === WORKSPACE_PATH) return;
      syncCreateDirToDA(dirPath);
    })
    .on('unlinkDir', (dirPath) => {
      if (dirPath === WORKSPACE_PATH) return;
      syncDeleteToDA(dirPath);
    })
    .on('ready', () => {
      initialScanComplete = true;
      log('File watcher ready — monitoring for changes');
    })
    .on('error', (err) => {
      logError(`Watcher error: ${err.message}`);
    });

  // Graceful shutdown
  process.on('SIGTERM', () => {
    log('Received SIGTERM — shutting down');
    watcher.close();
    process.exit(0);
  });
  process.on('SIGINT', () => {
    log('Received SIGINT — shutting down');
    watcher.close();
    process.exit(0);
  });

  // Periodic status log
  setInterval(() => {
    if (syncCount > 0 || errorCount > 0) {
      log(`Status: ${syncCount} synced, ${errorCount} errors`);
    }
  }, 60000);

  log('Daemon started — press Ctrl+C to stop');
}

main().catch(err => {
  logError(`Fatal: ${err.message}`);
  process.exit(1);
});
