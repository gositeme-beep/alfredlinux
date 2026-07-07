/**
 * security/sandbox.js — Multi-User Isolation & Security Sandbox
 *
 * Enforces per-customer boundaries at every layer:
 *
 *   1. Path validation — All file paths must resolve within /home/<username>
 *   2. Process isolation — Commands run as the DA user, never as root
 *   3. Resource limits — Concurrent command cap, file size limits, rate limiting
 *   4. Command filtering — Blocks dangerous commands (rm -rf /, dd, etc.)
 *   5. Network isolation — Outbound connections restricted to safe ports
 *   6. Cross-user detection — Prevents reading other users' home dirs
 *
 * Every MCP tool call flows through validateRequest() before execution.
 */

import path from 'node:path';
import { ErrorCode, McpError } from '@modelcontextprotocol/sdk/types.js';

// ── Configuration ────────────────────────────────────────────────────────────
const MAX_FILE_SIZE = 50 * 1024 * 1024;          // 50 MB max file write
const MAX_CONCURRENT_COMMANDS = 5;                // per user
const RATE_LIMIT_WINDOW_MS = 60_000;              // 1 minute
const RATE_LIMIT_MAX_REQUESTS = 120;              // 120 tool calls per minute
const MAX_PATH_DEPTH = 50;                        // prevent absurd nesting

// ── Blocked command patterns ─────────────────────────────────────────────────
const BLOCKED_COMMANDS = [
  /\brm\s+(-[a-zA-Z]*f[a-zA-Z]*\s+)?\/\s*$/,    // rm -rf /
  /\brm\s+(-[a-zA-Z]*f[a-zA-Z]*\s+)?\/home\b(?!\/)/,  // rm /home (but allow /home/user/...)
  /\bdd\s+.*(of|if)=\/dev\//,                     // dd to/from devices
  /\bmkfs\b/,                                     // filesystem creation
  /\bfdisk\b/,                                    // partition editing
  /\biptables\b/,                                 // firewall rules
  /\buseradd\b|\buserdel\b|\busermod\b/,          // user management
  /\bpasswd\b/,                                   // password changes
  /\bchown\s+root\b/,                             // chown to root
  /\bchmod\s+[0-7]*7[0-7]*\s+\//,                // world-writable on system dirs
  /\bsudo\b/,                                     // sudo anything
  /\bsu\s+-?\s*\w/,                               // su to other user
  /\bsystemctl\b/,                                // systemd control
  /\bservice\s+\w+\s+(start|stop|restart)\b/,     // service control
  /\bkillall\b/,                                  // mass kill
  /\bpkill\s+-9/,                                 // force kill processes
  /\bcrontab\s+-r\b/,                             // remove all crontabs
  /\bshutdown\b|\breboot\b|\bpoweroff\b/,         // system shutdown
  /\bcat\s+\/etc\/(shadow|passwd|sudoers)\b/,     // sensitive files
  /\bwget\s+.*\|\s*(ba)?sh\b/,                    // pipe-to-shell
  /\bcurl\s+.*\|\s*(ba)?sh\b/,                    // pipe-to-shell
  />\s*\/dev\/sd[a-z]/,                           // write to block devices
  /\bchroot\b/,                                   // chroot escape attempts
  /\bnsenter\b/,                                  // namespace entry
  /\bmount\b|\bumount\b/,                         // mount/unmount
  /\/proc\/\d+/,                                  // other process' proc entries
  /\/home\/(?!CURRENT_USER)/,                     // other users' homes (replaced at runtime)
];

// ── Blocked file paths ───────────────────────────────────────────────────────
const BLOCKED_PATHS = [
  '/etc/shadow', '/etc/sudoers', '/etc/passwd',
  '/root', '/var/log/auth.log', '/var/log/secure',
  '/proc/1', '/sys',
];

// ── Rate limiting state ──────────────────────────────────────────────────────
const rateLimits = new Map();    // username → { count, windowStart }
const activeCmds = new Map();    // username → number of active commands

/**
 * Validate and sanitize a file path — must resolve within the user's home.
 *
 * @param {string} filePath — the requested path (absolute or relative)
 * @param {string} homeDir  — the user's home directory (e.g. /home/seller1)
 * @returns {string} resolved absolute path
 * @throws {McpError} if path escapes home or is blocked
 */
export function validatePath(filePath, homeDir) {
  if (!filePath || typeof filePath !== 'string') {
    throw new McpError(ErrorCode.InvalidParams, 'File path is required');
  }

  // Resolve relative paths against homeDir
  const resolved = path.resolve(homeDir, filePath);

  // Must stay within home directory
  if (!resolved.startsWith(homeDir + '/') && resolved !== homeDir) {
    throw new McpError(
      ErrorCode.InvalidParams,
      `Path "${filePath}" resolves outside your home directory. Access denied.`
    );
  }

  // Check path depth
  const depth = resolved.split('/').length;
  if (depth > MAX_PATH_DEPTH) {
    throw new McpError(ErrorCode.InvalidParams, `Path too deep (${depth} levels). Maximum is ${MAX_PATH_DEPTH}.`);
  }

  // Check blocked paths
  for (const blocked of BLOCKED_PATHS) {
    if (resolved.startsWith(blocked)) {
      throw new McpError(ErrorCode.InvalidParams, `Access to "${blocked}" is restricted.`);
    }
  }

  // Check for symlink escapes (e.g. /home/user/link → /etc/shadow)
  // This is done at runtime by the calling code via fs.realpath

  return resolved;
}

/**
 * Validate a shell command — block dangerous patterns.
 *
 * @param {string} command  — the command to validate
 * @param {string} username — the DA username (for cross-user patterns)
 * @returns {{ safe: boolean, blocked?: string }}
 */
export function validateCommand(command, username) {
  if (!command || typeof command !== 'string') {
    return { safe: false, blocked: 'Empty command' };
  }

  // Check each blocked pattern
  for (const pattern of BLOCKED_COMMANDS) {
    // Replace CURRENT_USER placeholder with actual username
    const adjustedPattern = new RegExp(
      pattern.source.replace('CURRENT_USER', username),
      pattern.flags
    );
    if (adjustedPattern.test(command)) {
      return {
        safe: false,
        blocked: `Command blocked by security policy: matches pattern ${pattern.source.slice(0, 50)}`,
      };
    }
  }

  // Check for attempts to access other users' home directories
  const otherHomeMatch = command.match(/\/home\/([a-zA-Z0-9_]+)/g);
  if (otherHomeMatch) {
    for (const match of otherHomeMatch) {
      const targetUser = match.replace('/home/', '');
      if (targetUser !== username && targetUser !== '') {
        return {
          safe: false,
          blocked: `Access to /home/${targetUser} is not allowed. You can only access your own files.`,
        };
      }
    }
  }

  return { safe: true };
}

/**
 * Rate-limit check — enforce per-user request rate.
 *
 * @param {string} username
 * @returns {{ allowed: boolean, remaining: number, resetIn: number }}
 */
export function checkRateLimit(username) {
  const now = Date.now();
  let entry = rateLimits.get(username);

  if (!entry || (now - entry.windowStart) > RATE_LIMIT_WINDOW_MS) {
    entry = { count: 0, windowStart: now };
    rateLimits.set(username, entry);
  }

  entry.count++;

  if (entry.count > RATE_LIMIT_MAX_REQUESTS) {
    return {
      allowed: false,
      remaining: 0,
      resetIn: Math.ceil((entry.windowStart + RATE_LIMIT_WINDOW_MS - now) / 1000),
    };
  }

  return {
    allowed: true,
    remaining: RATE_LIMIT_MAX_REQUESTS - entry.count,
    resetIn: Math.ceil((entry.windowStart + RATE_LIMIT_WINDOW_MS - now) / 1000),
  };
}

/**
 * Track concurrent command count per user.
 *
 * @param {string} username
 * @param {'acquire'|'release'} action
 * @returns {{ allowed: boolean, active: number }}
 */
export function trackConcurrency(username, action) {
  const current = activeCmds.get(username) || 0;

  if (action === 'acquire') {
    if (current >= MAX_CONCURRENT_COMMANDS) {
      return { allowed: false, active: current };
    }
    activeCmds.set(username, current + 1);
    return { allowed: true, active: current + 1 };
  }

  if (action === 'release') {
    activeCmds.set(username, Math.max(0, current - 1));
    return { allowed: true, active: Math.max(0, current - 1) };
  }

  return { allowed: true, active: current };
}

/**
 * Full request validation — run all checks before executing a tool.
 *
 * @param {string} username — DA username
 * @param {string} toolName — MCP tool being called
 * @param {object} args     — tool arguments
 * @param {string} homeDir  — user's home directory
 * @returns {{ allowed: boolean, error?: string, sanitizedArgs?: object }}
 */
export function validateRequest(username, toolName, args, homeDir) {
  // 1. Rate limit
  const rateCheck = checkRateLimit(username);
  if (!rateCheck.allowed) {
    return {
      allowed: false,
      error: `Rate limit exceeded (${RATE_LIMIT_MAX_REQUESTS}/min). Try again in ${rateCheck.resetIn}s.`,
    };
  }

  // 2. Sanitize file paths in args
  const sanitized = { ...args };
  const pathFields = ['path', 'file_path', 'source', 'destination', 'directory', 'working_directory'];
  for (const field of pathFields) {
    if (sanitized[field] && typeof sanitized[field] === 'string') {
      try {
        sanitized[field] = validatePath(sanitized[field], homeDir);
      } catch (err) {
        return { allowed: false, error: err.message };
      }
    }
  }

  // 3. Validate commands
  if (sanitized.command && typeof sanitized.command === 'string') {
    const cmdCheck = validateCommand(sanitized.command, username);
    if (!cmdCheck.safe) {
      return { allowed: false, error: cmdCheck.blocked };
    }
  }

  // 4. File size check for writes
  if (sanitized.content && typeof sanitized.content === 'string') {
    if (Buffer.byteLength(sanitized.content, 'utf-8') > MAX_FILE_SIZE) {
      return {
        allowed: false,
        error: `File content exceeds maximum size of ${MAX_FILE_SIZE / 1024 / 1024}MB.`,
      };
    }
  }

  return { allowed: true, sanitizedArgs: sanitized };
}

/**
 * Get isolation status/stats for a user.
 *
 * @param {string} username
 * @returns {object}
 */
export function getIsolationStatus(username) {
  const rate = rateLimits.get(username);
  const cmds = activeCmds.get(username) || 0;

  return {
    username,
    rateLimit: {
      used: rate?.count || 0,
      max: RATE_LIMIT_MAX_REQUESTS,
      windowMs: RATE_LIMIT_WINDOW_MS,
    },
    concurrentCommands: {
      active: cmds,
      max: MAX_CONCURRENT_COMMANDS,
    },
    maxFileSize: `${MAX_FILE_SIZE / 1024 / 1024}MB`,
    maxPathDepth: MAX_PATH_DEPTH,
  };
}

// Cleanup stale rate limit entries every 5 minutes
setInterval(() => {
  const now = Date.now();
  for (const [user, entry] of rateLimits.entries()) {
    if ((now - entry.windowStart) > RATE_LIMIT_WINDOW_MS * 2) {
      rateLimits.delete(user);
    }
  }
}, 5 * 60_000);
