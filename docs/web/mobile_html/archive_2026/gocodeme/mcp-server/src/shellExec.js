/**
 * shellExec.js — Safe shell command execution for MCP Server
 *
 * Provides a sandboxed way to run shell commands within a customer's
 * home directory. Used by Git, WordPress, and other tools that need
 * to execute system commands.
 *
 * Security:
 *   - All commands run as the current process user (gositeme / seller1)
 *   - cwd is always locked to /home/<username>
 *   - Timeout enforced (default 30s, max 120s)
 *   - Output truncated to prevent memory exhaustion
 */

import { execSync, execFileSync } from 'child_process';
import path from 'path';

const MAX_OUTPUT = 256 * 1024;  // 256 KB output cap
const DEFAULT_TIMEOUT = 30_000; // 30 seconds
const MAX_TIMEOUT = 120_000;    // 2 minutes max

/**
 * Execute a shell command in a customer's home directory.
 *
 * @param {string} command    — command string to run
 * @param {string} homeDir    — absolute path to customer home (e.g. /home/username)
 * @param {object} [opts]
 * @param {number} [opts.timeout]  — ms, default 30000
 * @param {string} [opts.cwd]     — override cwd (must stay under homeDir)
 * @param {object} [opts.env]     — extra env vars to merge
 * @returns {{ stdout: string, stderr: string, exitCode: number }}
 */
export function shellExec(command, homeDir, opts = {}) {
  const timeout = Math.min(opts.timeout || DEFAULT_TIMEOUT, MAX_TIMEOUT);

  // Resolve and validate cwd
  let cwd = homeDir;
  if (opts.cwd) {
    cwd = path.resolve(homeDir, opts.cwd);
    if (!cwd.startsWith(homeDir)) {
      throw new Error(`CWD escape blocked: "${opts.cwd}" resolves outside home directory`);
    }
  }

  const env = {
    ...process.env,
    HOME: homeDir,
    ...opts.env,
  };

  try {
    const stdout = execSync(command, {
      cwd,
      timeout,
      maxBuffer: MAX_OUTPUT,
      encoding: 'utf-8',
      env,
      stdio: ['pipe', 'pipe', 'pipe'],
    });
    return { stdout: stdout || '', stderr: '', exitCode: 0 };
  } catch (err) {
    return {
      stdout: (err.stdout || '').toString().slice(0, MAX_OUTPUT),
      stderr: (err.stderr || '').toString().slice(0, MAX_OUTPUT),
      exitCode: err.status ?? 1,
    };
  }
}

/**
 * Execute a command safely using execFileSync (no shell injection).
 *
 * @param {string} executable — the binary (e.g. 'git', 'wp')
 * @param {string[]} args     — argument array
 * @param {string} homeDir
 * @param {object} [opts]     — same as shellExec opts
 * @returns {{ stdout: string, stderr: string, exitCode: number }}
 */
export function shellExecFile(executable, args, homeDir, opts = {}) {
  const timeout = Math.min(opts.timeout || DEFAULT_TIMEOUT, MAX_TIMEOUT);

  let cwd = homeDir;
  if (opts.cwd) {
    cwd = path.resolve(homeDir, opts.cwd);
    if (!cwd.startsWith(homeDir)) {
      throw new Error(`CWD escape blocked: "${opts.cwd}" resolves outside home directory`);
    }
  }

  const env = {
    ...process.env,
    HOME: homeDir,
    ...opts.env,
  };

  try {
    const stdout = execFileSync(executable, args, {
      cwd,
      timeout,
      maxBuffer: MAX_OUTPUT,
      encoding: 'utf-8',
      env,
      stdio: ['pipe', 'pipe', 'pipe'],
    });
    return { stdout: stdout || '', stderr: '', exitCode: 0 };
  } catch (err) {
    return {
      stdout: (err.stdout || '').toString().slice(0, MAX_OUTPUT),
      stderr: (err.stderr || '').toString().slice(0, MAX_OUTPUT),
      exitCode: err.status ?? 1,
    };
  }
}
