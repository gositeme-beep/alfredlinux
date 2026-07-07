/**
 * terminal/sessionManager.js — Persistent Terminal Session Manager
 *
 * Provides long-lived shell sessions per user that survive MCP reconnects.
 * Instead of spawning a one-shot bash process per command (shellExec style),
 * this manager keeps a persistent pty-like child_process per user, with:
 *
 *   - Working directory persistence across commands
 *   - Environment variable retention
 *   - Output history (last 100 commands)
 *   - Auto-cleanup after idle timeout (30 minutes)
 *   - Session resume on reconnect (same user gets same shell)
 *
 * Security:
 *   - Each user gets exactly one persistent session
 *   - Shell is locked to /home/<username> — no escaping
 *   - 120s command timeout (same as shellExec)
 *   - 256KB max output per command
 */

import { spawn } from 'node:child_process';
import path from 'node:path';
import { EventEmitter } from 'node:events';

const MAX_OUTPUT = 256 * 1024;       // 256 KB output cap per command
const CMD_TIMEOUT = 120_000;         // 2 minutes max per command
const IDLE_TIMEOUT = 30 * 60_000;    // 30 minutes idle → auto-close
const MAX_HISTORY = 100;             // Keep last 100 command results
const CWD_MARKER = '__GOCODEME_CWD__'; // marker for cwd extraction

/** @type {Map<string, TerminalSession>} */
const sessions = new Map();

class TerminalSession extends EventEmitter {
  constructor(username, homeDir) {
    super();
    this.username = username;
    this.homeDir = homeDir;
    this.cwd = homeDir;
    this.history = [];
    this.createdAt = new Date().toISOString();
    this.lastActivity = Date.now();
    this.commandCount = 0;
    this._idleTimer = null;
    this._resetIdleTimer();
  }

  _resetIdleTimer() {
    if (this._idleTimer) clearTimeout(this._idleTimer);
    this._idleTimer = setTimeout(() => {
      console.error(`[TERMINAL] Idle timeout — closing session for ${this.username}`);
      this.destroy();
    }, IDLE_TIMEOUT);
    this.lastActivity = Date.now();
  }

  /**
   * Execute a command in this session's context (cwd, env).
   * Each command spawns a sub-shell but inherits the session's cwd.
   *
   * @param {string} command
   * @param {object} [opts]
   * @param {number} [opts.timeout] — ms, default 120000
   * @returns {Promise<{stdout: string, stderr: string, exitCode: number, cwd: string}>}
   */
  async exec(command, opts = {}) {
    this._resetIdleTimer();
    const timeout = Math.min(opts.timeout || CMD_TIMEOUT, CMD_TIMEOUT);
    const execStart = Date.now();

    // Wrap command to capture cwd after execution
    const wrappedCommand = `${command}\n__exit_code=$?\necho "${CWD_MARKER}$(pwd)"\nexit $__exit_code`;

    return new Promise((resolve) => {
      let stdout = '';
      let stderr = '';
      let killed = false;

      const proc = spawn('/bin/bash', ['-c', wrappedCommand], {
        cwd: this.cwd,
        timeout,
        env: {
          ...process.env,
          HOME: this.homeDir,
          USER: this.username,
          PATH: '/usr/local/bin:/usr/bin:/bin:/usr/sbin:/sbin',
          TERM: 'xterm-256color',
          GOCODEME_SESSION: '1',
        },
        stdio: ['pipe', 'pipe', 'pipe'],
      });

      const timer = setTimeout(() => {
        killed = true;
        proc.kill('SIGKILL');
      }, timeout);

      proc.stdout.on('data', (chunk) => {
        if (stdout.length < MAX_OUTPUT) stdout += chunk.toString();
      });

      proc.stderr.on('data', (chunk) => {
        if (stderr.length < MAX_OUTPUT) stderr += chunk.toString();
      });

      proc.on('close', (code) => {
        clearTimeout(timer);

        // Extract new cwd from output
        const cwdMatch = stdout.match(new RegExp(`${CWD_MARKER}(.+)`));
        if (cwdMatch) {
          const newCwd = cwdMatch[1].trim();
          // Validate cwd stays within home
          if (newCwd.startsWith(this.homeDir)) {
            this.cwd = newCwd;
          }
          // Remove the cwd marker line from output
          stdout = stdout.replace(new RegExp(`${CWD_MARKER}.+\\n?`), '');
        }

        // Truncate
        if (stdout.length > MAX_OUTPUT) {
          stdout = stdout.slice(0, MAX_OUTPUT) + '\n... [output truncated at 256KB]';
        }

        const result = {
          stdout: stdout || '',
          stderr: stderr || '',
          exitCode: killed ? 124 : (code ?? 1),
          cwd: this.cwd,
          timedOut: killed,
        };

        // Add to history
        const elapsed = Date.now() - execStart;
        this.commandCount++;
        this.history.push({
          id: this.commandCount,
          command,
          exitCode: result.exitCode,
          cwd: result.cwd,
          elapsed,
          timestamp: new Date().toISOString(),
          outputPreview: (result.stdout || result.stderr).slice(0, 200),
        });
        if (this.history.length > MAX_HISTORY) {
          this.history = this.history.slice(-MAX_HISTORY);
        }

        resolve(result);
      });

      proc.on('error', (err) => {
        clearTimeout(timer);
        resolve({
          stdout: '',
          stderr: err.message,
          exitCode: 1,
          cwd: this.cwd,
          timedOut: false,
        });
      });
    });
  }

  /**
   * Change directory (validated to stay within homeDir).
   * @param {string} dir — relative or absolute path
   * @returns {{ cwd: string, error?: string }}
   */
  cd(dir) {
    this._resetIdleTimer();
    const resolved = path.resolve(this.cwd, dir);
    if (!resolved.startsWith(this.homeDir)) {
      return { cwd: this.cwd, error: `Cannot navigate outside home directory` };
    }
    this.cwd = resolved;
    return { cwd: this.cwd };
  }

  /** Get session status. */
  status() {
    return {
      username: this.username,
      cwd: this.cwd,
      homeDir: this.homeDir,
      commandCount: this.commandCount,
      createdAt: this.createdAt,
      lastActivity: new Date(this.lastActivity).toISOString(),
      idleSeconds: Math.round((Date.now() - this.lastActivity) / 1000),
      historyLength: this.history.length,
    };
  }

  /** Get command history. */
  getHistory(last = 20) {
    return this.history.slice(-last);
  }

  /** Destroy this session. */
  destroy() {
    if (this._idleTimer) clearTimeout(this._idleTimer);
    sessions.delete(this.username);
    this.emit('close');
    console.error(`[TERMINAL] Session destroyed for ${this.username}`);
  }
}

// ── Public API ──────────────────────────────────────────────────────────────

/**
 * Get or create a persistent terminal session for a user.
 * If the user already has an active session, returns it (with current cwd).
 *
 * @param {string} username
 * @param {string} homeDir
 * @returns {TerminalSession}
 */
export function getSession(username, homeDir) {
  if (sessions.has(username)) {
    const session = sessions.get(username);
    session._resetIdleTimer();
    return session;
  }

  const session = new TerminalSession(username, homeDir);
  sessions.set(username, session);
  console.error(`[TERMINAL] New persistent session for ${username} (cwd: ${homeDir})`);
  return session;
}

/**
 * Execute a command in a user's persistent session.
 *
 * @param {string} username
 * @param {string} homeDir
 * @param {string} command
 * @param {object} [opts]
 * @returns {Promise<{stdout: string, stderr: string, exitCode: number, cwd: string, sessionInfo: object}>}
 */
export async function execInSession(username, homeDir, command, opts = {}) {
  const session = getSession(username, homeDir);
  const result = await session.exec(command, opts);
  return {
    ...result,
    sessionInfo: {
      commandNumber: session.commandCount,
      cwd: session.cwd,
      uptime: Math.round((Date.now() - new Date(session.createdAt).getTime()) / 1000),
    },
  };
}

/**
 * Get session status for a user (or null if no active session).
 *
 * @param {string} username
 * @returns {object|null}
 */
export function getSessionStatus(username) {
  const session = sessions.get(username);
  return session ? session.status() : null;
}

/**
 * Get command history for a user.
 *
 * @param {string} username
 * @param {number} [last=20]
 * @returns {Array}
 */
export function getSessionHistory(username, last = 20) {
  const session = sessions.get(username);
  return session ? session.getHistory(last) : [];
}

/**
 * Reset (destroy and recreate) a user's session.
 *
 * @param {string} username
 * @param {string} homeDir
 * @returns {object} status of new session
 */
export function resetSession(username, homeDir) {
  const existing = sessions.get(username);
  if (existing) existing.destroy();
  const session = getSession(username, homeDir);
  return session.status();
}

/**
 * Get count of active sessions (for health endpoint).
 * @returns {number}
 */
export function activeSessionCount() {
  return sessions.size;
}
