/**
 * GoCodeMe Filesystem Sandbox
 * 
 * Loaded via --require BEFORE Theia starts.
 * Patches the core Node.js `fs` module at the prototype level
 * to prevent directory traversal attacks.
 *
 * Security model:
 *   - ALLOWLIST for readdir (directory listing) — blocks browsing outside sandbox
 *   - ALLOWLIST for write operations — blocks writing outside workspace + /tmp
 *   - BLOCKLIST for read operations — blocks known sensitive paths while
 *     allowing Theia's own module loading from system paths
 *
 * Allowed paths:
 *   - Customer workspace:  /tmp/gocodeme-workspace-<username>/
 *   - Theia application:   read-only access for serving frontend bundles, plugins
 *   - /tmp/                for temporary files (uploads, downloads, etc.)
 *   - Node.js internals:   /proc/self/*, etc.
 *
 * Security surfaces covered:
 *   1. readdir/readdirSync — ALLOWLIST prevents browsing outside sandbox
 *   2. writeFile/mkdir/unlink etc — ALLOWLIST prevents writing outside sandbox
 *   3. readFile/stat/open — BLOCKLIST prevents reading sensitive files
 *   4. EnvVariablesServer — hides system env vars, fakes home dir
 *   5. Terminal shell — uses restricted shell wrapper + HOME override
 */

'use strict';

const path = require('path');
const Module = require('module');

// ── Determine sandbox boundaries from process arguments ──────────────────
const argv = process.argv;
let WORKSPACE_ROOT = null;
let DA_USERNAME = null;
let THEIA_DIR = null;

// Parse workspace from argv (first non-flag arg after main.js)
for (let i = 2; i < argv.length; i++) {
  if (!argv[i].startsWith('--')) {
    const resolved = path.resolve(argv[i]);
    // If a .code-workspace file is passed, use its parent directory
    WORKSPACE_ROOT = resolved.endsWith('.code-workspace')
      ? path.dirname(resolved)
      : resolved;
    break;
  }
}

// Parse DA username from --GOCODEME_DA_USER=xxx
for (const arg of argv) {
  const match = arg.match(/^--GOCODEME_DA_USER=(.+)$/);
  if (match) {
    DA_USERNAME = match[1];
    break;
  }
}

// Derive THEIA_DIR from the main script path
const mainScript = argv[1];
if (mainScript) {
  const idx = mainScript.indexOf('/theia-fork/');
  if (idx !== -1) {
    THEIA_DIR = mainScript.substring(0, idx + '/theia-fork'.length);
  }
}

// Fallback if we couldn't determine the workspace
if (!WORKSPACE_ROOT) {
  WORKSPACE_ROOT = DA_USERNAME ? `/tmp/gocodeme-workspace-${DA_USERNAME}` : '/tmp/gocodeme-workspace-NONE';
}

// ── Define allowed path prefixes ─────────────────────────────────────────
// User-scoped tmp directory for temp files (uploads, downloads, etc.)
// SECURITY: Do NOT allow broad /tmp/ access — it leaks other workspaces.
const USER_TMP = WORKSPACE_ROOT + '/.tmp';

// SECURITY FIX (2026-03-18): Build per-user domain allowlist instead of
// allowing ALL of /home/gositeme/domains/ which enabled cross-tenant access.
// Parse user-specific domains from GOCODEME_USER_DOMAINS env var (set by
// start-theia.sh). Falls back to only the workspace root (no domain access)
// if the env var is missing — fail-closed, not fail-open.
const USER_DOMAIN_DIRS = (() => {
  const raw = process.env.GOCODEME_USER_DOMAINS || '';
  if (!raw) return [];
  return raw.split(',').filter(Boolean).map(d => path.resolve(d));
})();

// Also derive user's home directory for their own domains only
const USER_HOME = DA_USERNAME ? `/home/${DA_USERNAME}` : null;
const USER_DOMAINS_DIR = USER_HOME ? `${USER_HOME}/domains` : null;

// ALLOWLIST for directory browsing (readdir) — strict
const ALLOWED_BROWSE_PREFIXES = [
  WORKSPACE_ROOT,
  ...USER_DOMAIN_DIRS,
];
// Only allow browsing user's own domain directory, not the parent /home/gositeme/domains/
if (USER_DOMAINS_DIR) {
  ALLOWED_BROWSE_PREFIXES.push(USER_DOMAINS_DIR);
}

// ALLOWLIST for read operations — includes Theia dir + Node.js system paths
// SECURITY: This is the PRIMARY access control. Anything not listed is blocked.
const ALLOWED_READ_PREFIXES = [
  WORKSPACE_ROOT,
  ...USER_DOMAIN_DIRS,
  '/proc/',
  '/dev/null',
  '/dev/urandom',
  // System paths needed for Node.js module loading (read-only)
  '/usr/',
  '/lib/',
  '/lib64/',
  '/bin/',
  '/sbin/',
  '/etc/',
];
if (USER_DOMAINS_DIR) {
  ALLOWED_READ_PREFIXES.push(USER_DOMAINS_DIR);
}
if (THEIA_DIR) {
  ALLOWED_READ_PREFIXES.push(THEIA_DIR);
  ALLOWED_BROWSE_PREFIXES.push(THEIA_DIR);
}

// ALLOWLIST for write operations — most restricted
const ALLOWED_WRITE_PREFIXES = [
  WORKSPACE_ROOT,
  USER_TMP,
  ...USER_DOMAIN_DIRS,
  '/tmp/github-remote',       // Theia GitHub remote extension
  '/tmp/theia_upload',        // Theia file upload temp dir
];
if (USER_DOMAINS_DIR) {
  ALLOWED_WRITE_PREFIXES.push(USER_DOMAINS_DIR);
}

// Note: User domain files are synced INTO the workspace by syncWorkspace.js
// (at WORKSPACE_ROOT/domains/<domain>/), so they're already covered by the
// WORKSPACE_ROOT prefix in all allowlists.

/**
 * Check if a resolved path is within allowed boundaries.
 */
function isPathAllowed(resolvedPath, allowedPrefixes) {
  if (!resolvedPath) return false;
  const normalized = path.resolve(resolvedPath);
  return allowedPrefixes.some(prefix => {
    if (normalized === prefix) return true;
    const prefixWithSlash = prefix.endsWith('/') ? prefix : prefix + '/';
    return normalized.startsWith(prefixWithSlash);
  });
}

function isBrowseAllowed(resolvedPath) {
  return isPathAllowed(resolvedPath, ALLOWED_BROWSE_PREFIXES);
}

function isReadAllowed(resolvedPath) {
  return isPathAllowed(resolvedPath, ALLOWED_READ_PREFIXES);
}

function isWriteAllowed(resolvedPath) {
  return isPathAllowed(resolvedPath, ALLOWED_WRITE_PREFIXES);
}

// ── BLOCKLIST for sensitive files (defense-in-depth for read operations) ─
const BLOCKED_PATTERNS = [
  /\/\.env(\..*)?$/,  // Block .env files in any domain
  /\/wp-config\.php$/,  // Block WordPress configs
  /^\/home\/gositeme\/\.(bash_history|ssh|gnupg|env|my\.cnf|mysql_history)/,
  /^\/home\/gositeme\/public_html\/whmcs\/configuration\.php/,
  /^\/home\/gositeme\/redis-/,
  /^\/home\/gositeme\/domains\/gositeme\.com\/public_html\/gocodeme\/middleware\/\.env/,
  /^\/etc\/(shadow|gshadow|sudoers)/,
  /^\/home\/gositeme\/public_html\/gocodeme\/middleware\/\.env/,
  // Block access to other users' workspaces (defense-in-depth)
  /^\/tmp\/gocodeme-workspace-(?!PLACEHOLDER_USERNAME)/,
  // Block middleware source/config (API keys, secrets in source)
  /^\/home\/gositeme\/domains\/gositeme\.com\/public_html\/gocodeme\/middleware\/src\//,
  /^\/home\/gositeme\/domains\/gositeme\.com\/public_html\/gocodeme\/middleware\/package\.json/,
];

// Replace placeholder with actual username for cross-workspace blocking
if (DA_USERNAME) {
  BLOCKED_PATTERNS[BLOCKED_PATTERNS.length - 3] =
    new RegExp(`^/tmp/gocodeme-workspace-(?!${DA_USERNAME.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\b)`);
}

function isBlockedPath(resolvedPath) {
  if (!resolvedPath) return false;
  try {
    const normalized = path.resolve(resolvedPath);
    return BLOCKED_PATTERNS.some(pattern => pattern.test(normalized));
  } catch {
    return false;
  }
}

// ── Log sandbox activation ───────────────────────────────────────────────
console.log(`[GoCodeMe Sandbox] Activated for user=${DA_USERNAME || 'unknown'}`);
console.log(`[GoCodeMe Sandbox] Workspace: ${WORKSPACE_ROOT}`);
console.log(`[GoCodeMe Sandbox] Theia dir: ${THEIA_DIR || 'unknown'}`);

// ── LAYER 1: Patch Module._load to intercept Theia module loading ────────
let _patchesApplied = false;
const _origEmit = process.emit.bind(process);
process.emit = function(event, ...args) {
  if (!_patchesApplied && event === 'listening') {
    _patchesApplied = true;
  }
  return _origEmit(event, ...args);
};

// ── LAYER 2: Patch fs module ─────────────────────────────────────────────
const fs = require('fs');

// Save ALL originals FIRST before any wrapping
const originals = {
  readdir:      fs.readdir,
  readdirSync:  fs.readdirSync,
  open:         fs.open,
  readFile:     fs.readFile,
  readFileSync: fs.readFileSync,
  writeFile:    fs.writeFile,
  mkdir:        fs.mkdir,
  rmdir:        fs.rmdir,
  unlink:       fs.unlink,
  access:       fs.access,
  accessSync:   fs.accessSync,
  stat:         fs.stat,
  statSync:     fs.statSync,
  lstat:        fs.lstat,
  lstatSync:    fs.lstatSync,
  rename:       fs.rename,
  copyFile:     fs.copyFile,
};

function extractPath(arg) {
  if (typeof arg === 'string') return arg;
  if (arg instanceof URL) return arg.pathname;
  if (Buffer.isBuffer(arg)) return arg.toString();
  return null;
}

function createBlockedError(filepath) {
  const err = new Error(`EACCES: permission denied, access '${filepath}'`);
  err.code = 'EACCES';
  err.errno = -13;
  err.syscall = 'access';
  err.path = filepath;
  return err;
}

// ── READDIR wrappers (ALLOWLIST — blocks directory browsing outside sandbox) ─

function wrapReaddir(original) {
  return function(filepath, ...args) {
    const p = extractPath(filepath);
    if (p && !isBrowseAllowed(p)) {
      const lastArg = args[args.length - 1];
      if (typeof lastArg === 'function') {
        return lastArg(createBlockedError(p));
      }
      throw createBlockedError(p);
    }
    return original.call(this, filepath, ...args);
  };
}

function wrapReaddirSync(original) {
  return function(filepath, ...args) {
    const p = extractPath(filepath);
    if (p && !isBrowseAllowed(p)) {
      throw createBlockedError(p);
    }
    return original.call(this, filepath, ...args);
  };
}

fs.readdir     = wrapReaddir(originals.readdir);
fs.readdirSync = wrapReaddirSync(originals.readdirSync);

// ── READ wrappers (ALLOWLIST + BLOCKLIST defense-in-depth) ─────────────────
// SECURITY: Use ALLOWLIST (isReadAllowed) as primary check. Blocklist is
// defense-in-depth for allowed paths that contain sensitive sub-paths.
// This prevents cross-tenant reads via /tmp/gocodeme-workspace-OTHERUSER/.

function wrapFsRead(original) {
  return function(filepath, ...args) {
    const p = extractPath(filepath);
    if (p) {
      const resolved = path.resolve(p);
      if (!isReadAllowed(resolved) || isBlockedPath(resolved)) {
        const lastArg = args[args.length - 1];
        if (typeof lastArg === 'function') {
          return lastArg(createBlockedError(p));
        }
        throw createBlockedError(p);
      }
    }
    return original.call(this, filepath, ...args);
  };
}

function wrapFsReadSync(original) {
  return function(filepath, ...args) {
    const p = extractPath(filepath);
    if (p) {
      const resolved = path.resolve(p);
      if (!isReadAllowed(resolved) || isBlockedPath(resolved)) {
        throw createBlockedError(p);
      }
    }
    return original.call(this, filepath, ...args);
  };
}

fs.readFile     = wrapFsRead(originals.readFile);
fs.readFileSync = wrapFsReadSync(originals.readFileSync);
fs.open         = wrapFsRead(originals.open);
fs.access       = wrapFsRead(originals.access);
fs.accessSync   = wrapFsReadSync(originals.accessSync);
fs.stat         = wrapFsRead(originals.stat);
fs.statSync     = wrapFsReadSync(originals.statSync);
fs.lstat        = wrapFsRead(originals.lstat);
fs.lstatSync    = wrapFsReadSync(originals.lstatSync);

// ── WRITE wrappers (ALLOWLIST — only workspace + /tmp) ───────────────────

function wrapFsWrite(original) {
  return function(filepath, ...args) {
    const p = extractPath(filepath);
    if (p && !isWriteAllowed(p)) {
      const lastArg = args[args.length - 1];
      if (typeof lastArg === 'function') {
        return lastArg(createBlockedError(p));
      }
      throw createBlockedError(p);
    }
    return original.call(this, filepath, ...args);
  };
}

fs.writeFile = wrapFsWrite(originals.writeFile);
fs.mkdir     = wrapFsWrite(originals.mkdir);
fs.rmdir     = wrapFsWrite(originals.rmdir);
fs.unlink    = wrapFsWrite(originals.unlink);

fs.rename = function(src, dest, ...args) {
  const s = extractPath(src);
  const d = extractPath(dest);
  if ((s && !isWriteAllowed(s)) || (d && !isWriteAllowed(d))) {
    const lastArg = args[args.length - 1];
    if (typeof lastArg === 'function') {
      return lastArg(createBlockedError(s || d));
    }
    throw createBlockedError(s || d);
  }
  return originals.rename.call(this, src, dest, ...args);
};

fs.copyFile = function(src, dest, ...args) {
  const s = extractPath(src);
  const d = extractPath(dest);
  // Source: blocklist check (read). Dest: allowlist check (write).
  if (s && isBlockedPath(s)) {
    const lastArg = args[args.length - 1];
    if (typeof lastArg === 'function') return lastArg(createBlockedError(s));
    throw createBlockedError(s);
  }
  if (d && !isWriteAllowed(d)) {
    const lastArg = args[args.length - 1];
    if (typeof lastArg === 'function') return lastArg(createBlockedError(d));
    throw createBlockedError(d);
  }
  return originals.copyFile.call(this, src, dest, ...args);
};

// ── PROMISES API wrappers ────────────────────────────────────────────────
if (fs.promises) {
  const origP = {
    readdir:   fs.promises.readdir,
    readFile:  fs.promises.readFile,
    stat:      fs.promises.stat,
    lstat:     fs.promises.lstat,
    access:    fs.promises.access,
    open:      fs.promises.open,
    writeFile: fs.promises.writeFile,
    mkdir:     fs.promises.mkdir,
    rmdir:     fs.promises.rmdir,
    unlink:    fs.promises.unlink,
    rename:    fs.promises.rename,
    copyFile:  fs.promises.copyFile,
  };

  // readdir — ALLOWLIST
  fs.promises.readdir = async function(filepath, ...args) {
    const p = extractPath(filepath);
    if (p && !isBrowseAllowed(p)) throw createBlockedError(p);
    return origP.readdir.call(this, filepath, ...args);
  };

  // reads — ALLOWLIST + BLOCKLIST defense-in-depth
  function wrapPromiseRead(original) {
    return async function(filepath, ...args) {
      const p = extractPath(filepath);
      if (p) {
        const resolved = path.resolve(p);
        if (!isReadAllowed(resolved) || isBlockedPath(resolved)) throw createBlockedError(p);
      }
      return original.call(this, filepath, ...args);
    };
  }
  fs.promises.readFile = wrapPromiseRead(origP.readFile);
  fs.promises.stat     = wrapPromiseRead(origP.stat);
  fs.promises.lstat    = wrapPromiseRead(origP.lstat);
  fs.promises.access   = wrapPromiseRead(origP.access);
  fs.promises.open     = wrapPromiseRead(origP.open);

  // writes — ALLOWLIST
  function wrapPromiseWrite(original) {
    return async function(filepath, ...args) {
      const p = extractPath(filepath);
      if (p && !isWriteAllowed(p)) throw createBlockedError(p);
      return original.call(this, filepath, ...args);
    };
  }
  fs.promises.writeFile = wrapPromiseWrite(origP.writeFile);
  fs.promises.mkdir     = wrapPromiseWrite(origP.mkdir);
  fs.promises.rmdir     = wrapPromiseWrite(origP.rmdir);
  fs.promises.unlink    = wrapPromiseWrite(origP.unlink);

  fs.promises.rename = async function(src, dest) {
    const s = extractPath(src), d = extractPath(dest);
    if ((s && !isWriteAllowed(s)) || (d && !isWriteAllowed(d))) throw createBlockedError(s || d);
    return origP.rename.call(this, src, dest);
  };
  fs.promises.copyFile = async function(src, dest, ...args) {
    const s = extractPath(src), d = extractPath(dest);
    if (s && isBlockedPath(s)) throw createBlockedError(s);
    if (d && !isWriteAllowed(d)) throw createBlockedError(d);
    return origP.copyFile.call(this, src, dest, ...args);
  };
}

// ── LAYER 3: Environment variable sanitization ──────────────────────────
const SENSITIVE_ENV_KEYS = [
  'ANTHROPIC_API_KEY',
  'JWT_SECRET',
  'DA_ADMIN_USER',
  'DA_ADMIN_PASSWORD',
  'DA_ADMIN_PASS',
  'DA_API_KEY',
  'GOCODEME_JWT_TOKEN',
  'REDIS_URL',
  'DATABASE_URL',
  'DB_PASSWORD',
  'MYSQL_PASSWORD',
  'SECRET_KEY',
  'AWS_SECRET_ACCESS_KEY',
  'STRIPE_SECRET_KEY',
  'WHMCS_WEBHOOK_SECRET',
  'WHMCS_API_SECRET',
  'WHMCS_API_IDENTIFIER',
];

const _sensitiveBackup = {};
for (const key of SENSITIVE_ENV_KEYS) {
  if (process.env[key]) {
    _sensitiveBackup[key] = process.env[key];
    // NOTE: We intentionally do NOT delete these from process.env here because
    // the Theia backend (Anthropic SDK, etc.) still needs them. Instead,
    // gocodeme-shell.sh uses bwrap --unsetenv to strip them from terminals.
  }
}

// ── LAYER 4: Override HOME for terminals ─────────────────────────────────
process.env.HOME = WORKSPACE_ROOT;

// ── LAYER 5: Restrict terminal shell ─────────────────────────────────────
// Handled by workspace settings + the start-theia.sh wrapper

console.log(`[GoCodeMe Sandbox] Security layers active:`);
console.log(`[GoCodeMe Sandbox]   Browse allowlist: ${ALLOWED_BROWSE_PREFIXES.length} prefixes`);
console.log(`[GoCodeMe Sandbox]   Read blocklist:   ${BLOCKED_PATTERNS.length} patterns`);
console.log(`[GoCodeMe Sandbox]   Write allowlist:  ${ALLOWED_WRITE_PREFIXES.length} prefixes`);
console.log(`[GoCodeMe Sandbox]   User domains:     ${USER_DOMAIN_DIRS.length} directories`);
console.log(`[GoCodeMe Sandbox]   HOME set to: ${WORKSPACE_ROOT}`);
