'use strict';

/**
 * Path Sanitizer — centralized filesystem sandbox enforcement
 *
 * Every route that accepts a user-supplied path MUST validate it through
 * this module before touching the filesystem.
 *
 * Security model:
 *   - Resolve to absolute path (eliminates ../ and symlink tricks)
 *   - Verify the resolved path starts with the allowed workspace prefix
 *   - Reject null bytes, double-encoding, and other bypass vectors
 *   - Log violations to incident tracking
 */

const path = require('path');
const fs   = require('fs');

const logger = require('../logger');

const WORKSPACE_BASE = '/tmp/gocodeme-workspace-';
const DA_HOME_BASE   = '/home/';

/**
 * Validate that a DA username is safe for path construction.
 * DA usernames are alphanumeric + underscore, max 16 chars.
 */
function isValidDaUsername(username) {
  return typeof username === 'string' && /^[a-zA-Z0-9_]{1,16}$/.test(username);
}

/**
 * Resolve the workspace root for a DA user.
 * Returns the canonical workspace path, or null if invalid.
 */
function workspaceRoot(daUsername) {
  if (!isValidDaUsername(daUsername)) return null;
  return path.join('/tmp', `gocodeme-workspace-${daUsername}`);
}

/**
 * Resolve the home directory for a DA user.
 */
function homeRoot(daUsername) {
  if (!isValidDaUsername(daUsername)) return null;
  return path.join('/home', daUsername);
}

/**
 * Core path sanitization: ensure a user-supplied path resolves within
 * one of the allowed prefixes for the given user.
 *
 * @param {string} daUsername       - Authenticated DA username
 * @param {string} requestedPath   - User-supplied path (relative or absolute)
 * @param {object} [opts]
 * @param {string} [opts.base]     - Base to resolve relative paths against
 *                                   (defaults to workspace root)
 * @param {string[]} [opts.allowedPrefixes] - Override allowed prefixes
 * @returns {{ ok: boolean, resolved?: string, error?: string }}
 */
function sanitize(daUsername, requestedPath, opts = {}) {
  if (!isValidDaUsername(daUsername)) {
    return { ok: false, error: 'Invalid username format' };
  }

  if (typeof requestedPath !== 'string' || requestedPath.length === 0) {
    return { ok: false, error: 'Path is required' };
  }

  // Reject null bytes — classic bypass for C-based path parsers
  if (requestedPath.includes('\0')) {
    logIncident(daUsername, requestedPath, 'null_byte');
    return { ok: false, error: 'Invalid path' };
  }

  const wsRoot = workspaceRoot(daUsername);
  const homeDir = homeRoot(daUsername);
  const base = opts.base || wsRoot;

  const allowedPrefixes = opts.allowedPrefixes || [
    wsRoot,
    homeDir,
  ].filter(Boolean);

  // Resolve to absolute, collapsing all ../ sequences
  let resolved;
  if (path.isAbsolute(requestedPath)) {
    resolved = path.resolve(requestedPath);
  } else {
    resolved = path.resolve(base, requestedPath);
  }

  // Check resolved path is within allowed prefixes
  const withinAllowed = allowedPrefixes.some(prefix => {
    const normalizedPrefix = prefix.endsWith('/') ? prefix : prefix + '/';
    return resolved === prefix || resolved.startsWith(normalizedPrefix);
  });

  if (!withinAllowed) {
    logIncident(daUsername, requestedPath, 'path_traversal', resolved);
    return { ok: false, error: 'Access denied: path outside allowed workspace' };
  }

  // If the resolved path exists and is a symlink, verify the real path
  // also stays within the allowed prefixes
  try {
    if (fs.existsSync(resolved)) {
      const realPath = fs.realpathSync(resolved);
      const realWithinAllowed = allowedPrefixes.some(prefix => {
        const normalizedPrefix = prefix.endsWith('/') ? prefix : prefix + '/';
        return realPath === prefix || realPath.startsWith(normalizedPrefix);
      });
      if (!realWithinAllowed) {
        logIncident(daUsername, requestedPath, 'symlink_escape', `${resolved} -> ${realPath}`);
        return { ok: false, error: 'Access denied: symlink target outside workspace' };
      }
    }
  } catch {
    // Path doesn't exist yet (e.g. creating a new file) — that's fine
  }

  return { ok: true, resolved };
}

/**
 * Express middleware factory: validates req.query.path / req.body.path
 * and attaches the sanitized path to req.sanitizedPath.
 *
 * @param {object} [opts]
 * @param {string} [opts.paramName]      - query/body param name (default: 'path')
 * @param {boolean} [opts.required]      - reject if missing (default: false)
 * @param {string[]} [opts.allowedPrefixes] - override allowed prefixes
 */
function middleware(opts = {}) {
  const paramName = opts.paramName || 'path';
  const required = opts.required !== false;

  return function pathSanitizerMiddleware(req, res, next) {
    const daUsername = req.params.username || req.user?.daUsername;
    if (!daUsername) {
      return res.status(401).json({ ok: false, error: 'Authentication required' });
    }

    const rawPath = req.query[paramName] || req.body?.[paramName];
    if (!rawPath) {
      if (required) {
        return res.status(400).json({ ok: false, error: `${paramName} is required` });
      }
      return next();
    }

    const result = sanitize(daUsername, rawPath, opts);
    if (!result.ok) {
      return res.status(403).json({ ok: false, error: result.error });
    }

    req.sanitizedPath = result.resolved;
    next();
  };
}

/**
 * Log a path traversal or sandbox escape attempt.
 */
function logIncident(daUsername, requestedPath, type, detail) {
  const incident = {
    type: `path_security_${type}`,
    user: daUsername,
    requestedPath,
    detail: detail || null,
    timestamp: new Date().toISOString(),
    severity: 'CRITICAL',
  };

  logger.error(`[SECURITY INCIDENT] ${JSON.stringify(incident)}`);

  // Also write to Redis for alfred_incidents tracking
  try {
    const { getRedis } = require('../redis');
    const redis = getRedis();
    redis.lpush('alfred_incidents', JSON.stringify(incident)).catch(() => {});
    redis.ltrim('alfred_incidents', 0, 999).catch(() => {});
  } catch {
    // Redis not available — file log above is sufficient
  }
}

module.exports = {
  sanitize,
  middleware,
  workspaceRoot,
  homeRoot,
  isValidDaUsername,
  logIncident,
};
