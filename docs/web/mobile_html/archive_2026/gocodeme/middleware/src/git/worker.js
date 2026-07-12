'use strict';

/**
 * git/worker.js — Local git execution worker
 *
 * Runs git commands directly on the server filesystem (no DA API needed —
 * all customer home dirs live on the same machine, accessible to us as
 * the gositeme user which has read/write access).
 *
 * Key design points:
 *  - Debounced auto-commit: rapid file writes are batched into one commit
 *    (default 3-second window) — avoids commit spam from AI agents.
 *  - Idempotent init: first commit to a workspace auto-inits the repo,
 *    sets author identity, adds a .gitignore.
 *  - Commit messages include: daUsername, file path hint, ISO timestamp.
 *  - Operations are queued per-workspace so concurrent writes don't race.
 *  - All git output is captured and returned; errors are logged but never
 *    crash the middleware.
 */

const { execFile } = require('child_process');
const { promisify } = require('util');
const path  = require('path');
const fs    = require('fs');
const logger = require('../logger');

const execFileAsync = promisify(execFile);

// Per-workspace debounce timers   { workspaceKey → { timer, files: Set } }
const pending = new Map();

// Per-workspace serialisation queue  { workspaceKey → Promise }
const queue = new Map();

// Default debounce delay (ms). Can be overridden per-call.
const DEBOUNCE_MS = parseInt(process.env.GIT_DEBOUNCE_MS || '3000', 10);

// Allowlist of git subcommands safe to execute
const SAFE_CMDS = new Set(['init', 'add', 'commit', 'log', 'diff', 'revert',
                           'status', 'show', 'config', 'rm', 'clone']);

// ── Low-level executor ────────────────────────────────────────────────────────

/**
 * Run a git command inside workDir.
 * @param {string}   workDir  — absolute path to the workspace
 * @param {string[]} args     — git arguments
 * @returns {Promise<string>} stdout + stderr
 */
async function runGit(workDir, args) {
  if (!SAFE_CMDS.has(args[0])) {
    throw new Error(`git subcommand not allowed: ${args[0]}`);
  }
  if (!path.isAbsolute(workDir)) {
    throw new Error(`workDir must be absolute: ${workDir}`);
  }

  try {
    const { stdout, stderr } = await execFileAsync('git', args, {
      cwd:     workDir,
      timeout: 30_000,
      env: {
        ...process.env,
        GIT_TERMINAL_PROMPT: '0',   // never prompt for credentials
        HOME: process.env.HOME,     // needed for global git config
      },
    });
    const out = (stdout + stderr).trim();
    // git commit exits non-zero when nothing to commit — treat as success
    return out;
  } catch (err) {
    // execFile rejects with non-zero exit; capture output
    const out = ((err.stdout || '') + (err.stderr || '')).trim() || err.message;
    // Propagate fatal errors so callers can abort gracefully
    if (out.includes('fatal:') || (err.code !== 0 && err.code !== 1)) {
      throw new Error(out);
    }
    return out;
  }
}

// ── Workspace initialisation ──────────────────────────────────────────────────

/**
 * Ensure workDir is a git repo with author identity configured.
 * Idempotent — safe to call before every commit.
 */
async function ensureRepo(workDir, daUsername) {
  const gitDir = path.join(workDir, '.git');
  const isRepo = fs.existsSync(gitDir);

  if (!isRepo) {
    await runGit(workDir, ['init', '-b', 'main']);
    logger.info(`git init: ${workDir}`);

    // Write a sensible .gitignore
    const gitignore = [
      '# GoCodeMe auto-generated',
      'node_modules/',
      '.env',
      '*.log',
      '.DS_Store',
      'tmp/',
      '.gocodeme/',
      '.gocodeme-ide/',
    ].join('\n') + '\n';

    const ignorePath = path.join(workDir, '.gitignore');
    if (!fs.existsSync(ignorePath)) {
      fs.writeFileSync(ignorePath, gitignore);
    }
  }

  // Always (re)set local identity so commits are attributed correctly
  await runGit(workDir, ['config', 'user.email', `${daUsername}@gocodeme.local`]);
  await runGit(workDir, ['config', 'user.name',  `GoCodeMe[${daUsername}]`]);
}

// ── Queue helper ──────────────────────────────────────────────────────────────

/**
 * Serialise git operations per workspace so concurrent writes don't race.
 */
function enqueue(workspaceKey, fn) {
  const prev = queue.get(workspaceKey) || Promise.resolve();
  const next = prev.then(fn).catch(err => {
    logger.error(`git queue error (${workspaceKey}): ${err.message}`);
  });
  queue.set(workspaceKey, next);
  return next;
}

// ── Auto-commit with debounce ─────────────────────────────────────────────────

/**
 * Schedule a debounced auto-commit for a workspace.
 * Multiple calls within DEBOUNCE_MS collapse into one commit.
 *
 * @param {object} opts
 * @param {string} opts.workDir     — absolute path to workspace root
 * @param {string} opts.daUsername  — DA username (for author + message)
 * @param {string} [opts.filePath]  — file that was changed (for commit message)
 * @param {string} [opts.action]    — 'write' | 'delete' | 'rename' | 'mkdir'
 * @param {number} [opts.delay]     — debounce delay ms (default DEBOUNCE_MS)
 * @returns {Promise<void>}
 */
function scheduleCommit({ workDir, daUsername, filePath, action = 'write', delay = DEBOUNCE_MS }) {
  const key = workDir;

  // Accumulate changed files in the pending set
  if (!pending.has(key)) {
    pending.set(key, { timer: null, files: new Set() });
  }
  const state = pending.get(key);
  if (filePath) state.files.add(filePath);

  // Clear existing timer and set a fresh one
  clearTimeout(state.timer);
  state.timer = setTimeout(() => {
    const files = [...state.files];
    pending.delete(key);

    enqueue(key, () => doCommit({ workDir, daUsername, files, action }));
  }, delay);
}

/**
 * Immediately commit all staged changes (no debounce).
 * Used by the /checkpoint endpoint.
 */
async function commitNow({ workDir, daUsername, message }) {
  return enqueue(workDir, () => doCommit({ workDir, daUsername, message, files: [] }));
}

// ── Core commit ───────────────────────────────────────────────────────────────

async function doCommit({ workDir, daUsername, files = [], action = 'write', message }) {
  try {
    if (!fs.existsSync(workDir)) {
      logger.warn(`git: workDir does not exist: ${workDir}`);
      return;
    }

    await ensureRepo(workDir, daUsername);
    await runGit(workDir, ['add', '-A']);

    // Build commit message
    const ts  = new Date().toISOString().replace('T', ' ').slice(0, 19);
    const msg = message ||
      (files.length === 1
        ? `${action}: ${path.basename(files[0])} [${daUsername}] ${ts}`
        : `auto-save: ${files.length || 'all'} file(s) [${daUsername}] ${ts}`);

    const out = await runGit(workDir, ['commit', '-m', msg, '--allow-empty']);
    logger.info(`git commit (${daUsername}): ${msg.slice(0, 80)}`);
    return out;
  } catch (err) {
    logger.error(`doCommit error (${workDir}): ${err.message}`);
  }
}

// ── Public API ────────────────────────────────────────────────────────────────

/**
 * Resolve the workspace root for a given DA username.
 * Returns null if the directory doesn't exist.
 *
 * SECURITY (2026-03-18): relPath is validated to prevent path traversal.
 * The resolved path MUST stay within /home/<daUsername>/.
 */
function resolveWorkDir(daUsername, relPath) {
  // Validate username format
  if (!/^[a-zA-Z0-9_]{1,16}$/.test(daUsername)) return null;

  // Primary: /tmp/gocodeme-workspace-<daUsername> (the IDE workspace)
  const tmpWorkspace = path.join('/tmp', `gocodeme-workspace-${daUsername}`);
  if (fs.existsSync(tmpWorkspace)) return tmpWorkspace;

  const homeDir = path.join('/home', daUsername);

  // If caller provides a relative path, validate it stays within the user's home
  if (relPath) {
    // Reject obvious traversal attempts before resolving
    if (typeof relPath !== 'string') return null;

    const explicit = path.resolve(homeDir, relPath);

    // SECURITY: Verify resolved path is still within user's home directory
    if (!explicit.startsWith(homeDir + '/') && explicit !== homeDir) {
      const logger = require('./logger') || console;
      try {
        const { logIncident } = require('../utils/pathSanitizer');
        logIncident(daUsername, relPath, 'git_workspace_traversal', explicit);
      } catch { /* pathSanitizer may not be loaded yet */ }
      return null;
    }

    return fs.existsSync(explicit) ? explicit : null;
  }

  // Fallback: ~/public_html or ~
  if (!fs.existsSync(homeDir)) return null;
  const pub = path.join(homeDir, 'public_html');
  return fs.existsSync(pub) ? pub : homeDir;
}

module.exports = {
  runGit,
  ensureRepo,
  scheduleCommit,
  commitNow,
  resolveWorkDir,
};
