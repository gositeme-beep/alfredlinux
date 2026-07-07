'use strict';

/**
 * IDE Launch Routes
 *
 * POST /api/launch             - Start Theia + OpenHands for the authenticated customer
 * GET  /api/launch/sessions    - List active sessions for this customer
 * POST /api/launch/stop        - Stop all sessions for this customer
 *
 * Sessions are tracked in Redis:
 *   launch:sessions:<daUsername>  → JSON array of { service, port, workspace, started, pid, url }
 *
 * Port allocation:
 *   Theia    — starts at THEIA_PORT_BASE (default 4000), scans up by 2
 *   Agent    — Theia port + 1
 *
 * The launch route calls scripts/start-theia.sh and scripts/start-agent.sh
 * which are already present in the project.
 */

const express  = require('express');
const router   = express.Router();
const { spawn } = require('child_process');
const path     = require('path');
const net      = require('net');
const { requireSession } = require('../auth/middleware');
const { getRedis }       = require('../redis');
const logger             = require('../logger');
const config             = require('../config');
const safeError          = require('../utils/safeError');

const BASE_DIR       = path.resolve(__dirname, '../../..');
const SCRIPTS_DIR    = path.join(BASE_DIR, 'scripts');
const THEIA_PORT_BASE = parseInt(process.env.THEIA_PORT_BASE || '4000', 10);
const AGENT_PORT_BASE = parseInt(process.env.AGENT_PORT_BASE || '4001', 10);
const MAX_PORT_SCAN   = 500; // scan up to 500 ports — supports 250 concurrent users
const PUBLIC_HOST     = process.env.PUBLIC_HOST || 'localhost';   // e.g. gositeme.com
const PUBLIC_SCHEME   = process.env.PUBLIC_SCHEME || 'http';
const { syncWorkspace } = require('../directadmin/syncWorkspace');

// ── Helpers ────────────────────────────────────────────────────────────────

/** Resolve workspace path for a customer.
 *  On this reseller setup only /home/gositeme exists.
 *  Customer files are accessed via MCP → DirectAdmin API, so the local
 *  workspace is a lightweight temp directory the IDE process opens.
 *  The MCP server bridges to the customer's actual DirectAdmin files. */
function resolveWorkspace(daUsername, relPath) {
  // Each customer gets an isolated local workspace dir
  const base = `/tmp/gocodeme-workspace-${daUsername}`;
  return base;
}

/** Check if a TCP port is free */
function isPortFree(port) {
  return new Promise((resolve) => {
    const srv = net.createServer();
    srv.once('error', () => resolve(false));
    srv.once('listening', () => { srv.close(); resolve(true); });
    srv.listen(port, '127.0.0.1');
  });
}

/** Find the next free pair of ports starting from base.
 *  If preferredPort is given, tries that pair first (sticky port).
 *  Also checks Redis to ensure no OTHER user has the port in an active session. */
async function findFreePorts(base, preferredPort) {
  // Helper: check if a port is claimed by another user in Redis
  async function isPortClaimedInRedis(port) {
    try {
      const redis = getRedis();
      // SECURITY (R3 M-03): Use SCAN instead of KEYS to avoid O(N) Redis blocking
      let cursor = '0';
      do {
        const [next, keys] = await redis.scan(cursor, 'MATCH', 'launch:sessions:*', 'COUNT', 100);
        cursor = next;
        for (const key of keys) {
          try {
            const sessions = JSON.parse(await redis.get(key));
            if (sessions.some(s => s.port === port)) return true;
          } catch {}
        }
      } while (cursor !== '0');
    } catch {}
    return false;
  }

  // Try the user's sticky (preferred) port first
  if (preferredPort && preferredPort >= base && preferredPort < base + MAX_PORT_SCAN * 2) {
    const p1 = preferredPort;
    const p2 = preferredPort + 1;
    const [f1, f2] = await Promise.all([isPortFree(p1), isPortFree(p2)]);
    const [c1, c2] = await Promise.all([isPortClaimedInRedis(p1), isPortClaimedInRedis(p2)]);
    if (f1 && f2 && !c1 && !c2) return [p1, p2];
  }
  // Fallback: scan sequentially from base
  for (let i = 0; i < MAX_PORT_SCAN; i += 2) {
    const p1 = base + i;
    const p2 = base + i + 1;
    const [f1, f2] = await Promise.all([isPortFree(p1), isPortFree(p2)]);
    if (f1 && f2) return [p1, p2];
  }
  throw new Error('No free ports available in range');
}

/** Redis session list key */
const sessionKey = (daUsername) => `launch:sessions:${daUsername}`;

/** Redis key for storing a user's preferred (sticky) port */
const stickyPortKey = (daUsername) => `launch:sticky_port:${daUsername}`;

/** Load sessions for a user from Redis */
async function getSessions(daUsername) {
  const redis = getRedis();
  const raw   = await redis.get(sessionKey(daUsername));
  if (!raw) return [];
  try { return JSON.parse(raw); } catch { return []; }
}

/** Save sessions for a user to Redis (TTL 24h) */
async function saveSessions(daUsername, sessions) {
  const redis = getRedis();
  await redis.set(sessionKey(daUsername), JSON.stringify(sessions), 'EX', 86400);
  // Save the Theia port as sticky for next launch
  const theia = sessions.find(s => s.service === 'theia' && s.port);
  if (theia) {
    await redis.set(stickyPortKey(daUsername), String(theia.port), 'EX', 86400 * 30); // 30 days
  }
}

/** Get the user's last-known sticky port, or null */
async function getStickyPort(daUsername) {
  const redis = getRedis();
  const raw = await redis.get(stickyPortKey(daUsername));
  return raw ? parseInt(raw, 10) : null;
}

/** Kill a process by PID (SIGTERM, then SIGKILL after 3s) */
function killPid(pid) {
  try {
    process.kill(pid, 'SIGTERM');
    setTimeout(() => {
      try { process.kill(pid, 'SIGKILL'); } catch { /* already dead */ }
    }, 3000);
  } catch { /* process already gone */ }
}

/** Kill all Theia/Agent processes listening on a given port (fuser fallback) */
function killPort(port) {
  // SECURITY (R3 M-04): validate port is numeric and use execFileSync (no shell)
  const p = parseInt(port, 10);
  if (!Number.isInteger(p) || p < 1 || p > 65535) return;
  try {
    const { execFileSync } = require('child_process');
    execFileSync('fuser', ['-k', `${p}/tcp`], { timeout: 5000, stdio: 'ignore' });
  } catch { /* no process or fuser not available */ }
}

// ── POST /api/launch ───────────────────────────────────────────────────────
// ── Access guard — requires active subscription ────────────────────────────
async function requireActiveAccess(req, res, next) {
  const { whmcsClientId } = req.user;
  try {
    const status = await getRedis().get(`access:${whmcsClientId}`);
    // Allow if key doesn't exist yet (dev / test mode without WHMCS provisioning)
    if (status && status !== 'active') {
      return res.status(402).json({ ok: false, error: `Account ${status} — please renew your GoCodeMe subscription.` });
    }
    next();
  } catch (err) {
    logger.error(`access guard error: ${err.message}`);
    // SECURITY (VULN-R2-05): Fail closed — don't allow access on Redis errors
    return res.status(503).json({ ok: false, error: 'Service temporarily unavailable — please try again' });
  }
}

router.post('/', requireSession, requireActiveAccess, async (req, res) => {
  const { whmcsClientId, plan } = req.user;
  // Always re-resolve DA username from Redis — JWT may contain a stale value
  let daUsername = req.user.daUsername;
  try {
    const freshUsername = await getRedis().get(`da_username:${whmcsClientId}`);
    if (freshUsername && freshUsername !== daUsername) {
      logger.info(`launch: refreshed DA username from Redis: ${daUsername} → ${freshUsername}`);
      daUsername = freshUsername;
    }
  } catch (err) {
    logger.warn(`launch: could not refresh DA username from Redis: ${err.message}`);
  }
  const rawWorkspace = (req.body && req.body.workspace) || 'public_html';
  const workspace    = resolveWorkspace(daUsername, rawWorkspace);

  // Check for existing sessions — don't double-launch
  let existing = await getSessions(daUsername);

  // Verify PIDs are alive — purge stale sessions with dead processes
  if (existing.length > 0) {
    const theiaSession = existing.find(s => s.service === 'theia');
    if (theiaSession && theiaSession.pid) {
      try {
        process.kill(theiaSession.pid, 0); // signal 0 = check if alive
      } catch {
        logger.warn(`launch: Theia PID ${theiaSession.pid} is dead — clearing stale sessions for ${daUsername}`);
        await getRedis().del(sessionKey(daUsername));
        existing = []; // fall through to fresh launch
      }
    }
  }

  if (existing.length > 0) {
    // Ensure file-sync daemon is running (may be missing from older sessions)
    const hasSync = existing.find(s => s.service === 'file-sync');
    if (!hasSync) {
      const syncScript = path.join(SCRIPTS_DIR, 'gocodeme-file-sync.js');
      const syncLogFile = path.join(BASE_DIR, 'logs', `file-sync-${daUsername}.log`);
      const syncLogFd   = require('fs').openSync(syncLogFile, 'a');
      const syncProc   = spawn('node', [syncScript, daUsername, workspace], {
        detached: true,
        stdio:    ['ignore', syncLogFd, syncLogFd],
        env: { ...Object.fromEntries(Object.entries(process.env).filter(([k]) => k !== 'GOCODEME_DA_USERNAME' && k !== 'GOCODEME_WHMCS_CLIENT_ID')) },
      });
      syncProc.unref();
      require('fs').closeSync(syncLogFd);
      existing.push({
        service:   'file-sync',
        port:      null,
        workspace: rawWorkspace,
        started:   new Date().toISOString(),
        pid:       syncProc.pid,
        url:       null,
      });
      await saveSessions(daUsername, existing);
      logger.info(`launch: started missing File Sync for ${daUsername} (PID ${syncProc.pid})`);
    }

    const ideSession = existing.find(s => s.service === 'theia');
    // Generate fresh IDE auth token for existing sessions (VULN-01/02 fix)
    const crypto = require('crypto');
    const relaunchToken = crypto.randomUUID();
    try {
      await getRedis().set(`ide_auth_token:${relaunchToken}`, daUsername, 'EX', 28800); // 8 hours
    } catch (err) {
      logger.warn(`launch: could not store IDE auth token for existing session: ${err.message}`);
    }
    const existingIdeUrl = ideSession && ideSession.url
      ? `${ideSession.url}${ideSession.url.includes('?') ? '&' : '?'}gcm_auth=${relaunchToken}`
      : null;
    return res.json({
      ok: true,
      message: 'Sessions already running',
      sessions: existing,
      ideUrl: existingIdeUrl,
    });
  }

  // Allocate ports — prefer the user's sticky (last-used) port
  let theiaPort, agentPort;
  try {
    const preferred = await getStickyPort(daUsername);
    [theiaPort, agentPort] = await findFreePorts(THEIA_PORT_BASE, preferred);
    logger.info(`launch: port allocation for ${daUsername} — sticky=${preferred}, assigned=${theiaPort}/${agentPort}`);
  } catch (err) {
    return res.status(503).json({ ok: false, error: safeError(err) });
  }

  // ── Domain access via symlink (no DA API overhead) ────────────────────
  // Create domains/ as a symlink to the user's real home directory.
  // The symlink gives the IDE direct filesystem access to the user's domains
  // without expensive DirectAdmin API calls for file listing/download.
  // SECURITY (2026-03-18): Validate daUsername before constructing symlink target
  const fs = require('fs');
  const domainsPath = path.join(workspace, 'domains');
  if (!/^[a-zA-Z0-9_]{1,16}$/.test(daUsername)) {
    logger.error(`launch: SECURITY — refusing to create symlink for invalid username: ${daUsername}`);
  } else {
    const domainsTarget = `/home/${daUsername}/domains`;
    if (!fs.existsSync(domainsPath)) {
      try {
        if (fs.existsSync(domainsTarget)) {
          fs.symlinkSync(domainsTarget, domainsPath);
          logger.info(`launch: created symlink ${domainsPath} → ${domainsTarget}`);
        } else {
          fs.mkdirSync(domainsPath, { recursive: true });
          logger.info(`launch: created domains directory (no DA home found)`);
        }
      } catch (symlinkErr) {
        logger.warn(`launch: symlink creation failed: ${symlinkErr.message}`);
        fs.mkdirSync(domainsPath, { recursive: true });
      }
    }
  }
  fs.mkdirSync(path.join(workspace, '.gocodeme'), { recursive: true });
  fs.mkdirSync(path.join(workspace, '.gocodeme-ide'), { recursive: true });

  // Generate workspace config (.code-workspace, settings, toolbar).
  // When domains/ is a symlink, syncWorkspace skips DA API file downloads
  // and discovers domains from the local filesystem instead.
  syncWorkspace(daUsername, workspace)
    .then(syncResult => {
      logger.info(`launch: background sync complete — ${syncResult.files} files (${syncResult.skipped || 0} unchanged) in ${syncResult.elapsed || '?'}s for ${daUsername}`);
      getRedis().set(`workspace:remote_path:${daUsername}`, syncResult.remotePath, 'EX', 86400).catch(() => {});
    })
    .catch(err => {
      logger.warn(`launch: background sync failed for ${daUsername}: ${err.message}`);
    });

  // Generate JWT for MCP auth — use MCP server's secret so port 3006 accepts it
  const jwt    = require('jsonwebtoken');
  const crypto = require('crypto');
  const mcpSecret = process.env.MCP_JWT_SECRET || config.jwt.secret;
  const mcpJwt = jwt.sign({ daUsername, whmcsClientId }, mcpSecret, { expiresIn: '24h' });

  // Generate a per-session proxy secret for the Anthropic proxy URL.
  // SECURITY: The old URL format (/api/anthropic-proxy/:daUsername/) was
  // exploitable because terminal users (with --share-net) could curl with
  // a different daUsername. Now we use a random token stored in Redis.
  const proxyToken = crypto.randomUUID();
  try {
    await getRedis().set(`anthropic_proxy_token:${proxyToken}`, daUsername, 'EX', 86400);
    // Seed forward + reverse DA username mappings so the Anthropic proxy
    // can resolve daUsername → whmcsClientId without WHMCS API calls.
    await getRedis().set(`da_username:${whmcsClientId}`, daUsername, 'EX', 86400);
    await getRedis().set(`client_id_by_da:${daUsername}`, String(whmcsClientId), 'EX', 86400);
  } catch (err) {
    logger.warn(`launch: could not store proxy token in Redis: ${err.message}`);
  }

  // Generate IDE auth token for secure proxy access (VULN-01/02 fix)
  // Both Theia and Agent ports use the same token since they belong to the same user.
  const ideAuthToken = crypto.randomUUID();
  try {
    await getRedis().set(`ide_auth_token:${ideAuthToken}`, daUsername, 'EX', 28800); // 8 hours
  } catch (err) {
    logger.warn(`launch: could not store IDE auth token: ${err.message}`);
  }

  const sessions = [];

  // ── Launch Theia ──────────────────────────────────────────────────────────
  const theiaScript = path.join(SCRIPTS_DIR, 'start-theia.sh');
  // Sanitise env — strip per-user defaults that could leak across tenants
  const { GOCODEME_DA_USERNAME: _da, GOCODEME_WHMCS_CLIENT_ID: _wid, ...safeEnv } = process.env;
  const theiaLogFile = path.join(BASE_DIR, 'logs', `theia-${daUsername}.log`);
  const theiaLogFd   = require('fs').openSync(theiaLogFile, 'a');
  const theiaProc    = spawn('bash', [theiaScript, daUsername, workspace, String(theiaPort)], {
    detached: true,
    stdio:    ['ignore', theiaLogFd, theiaLogFd],
    env: {
      ...safeEnv,
      DA_ADMIN_PASS: config.directAdmin.adminPass,  // CRITICAL: pass decrypted password to bash script
      GOCODEME_JWT_TOKEN: mcpJwt,
      GOCODEME_PROXY_TOKEN: proxyToken,           // per-session Anthropic proxy token
      GOCODEME_DA_USERNAME: daUsername,             // per-user override
      GOCODEME_WHMCS_CLIENT_ID: String(whmcsClientId),
    },
  });
  theiaProc.unref();
  require('fs').closeSync(theiaLogFd);

  const theiaUrl = `${PUBLIC_SCHEME}://${PUBLIC_HOST}/middleware/ide/${theiaPort}/?gcm_auth=${ideAuthToken}`;
  sessions.push({
    service:   'theia',
    port:      theiaPort,
    workspace: rawWorkspace,
    started:   new Date().toISOString(),
    pid:       theiaProc.pid,
    url:       theiaUrl,
  });

  logger.info(`launch: started Theia for ${daUsername} on port ${theiaPort} (PID ${theiaProc.pid})`);

  // ── Launch File Sync Daemon ─────────────────────────────────────────────────
  // Watches workspace for file changes and syncs them to DA docroot in real-time.
  // Launched here as a separate detached process (not inside start-theia.sh,
  // because its exec replaces the shell and kills background children).
  const syncScript  = path.join(SCRIPTS_DIR, 'gocodeme-file-sync.js');
  const syncLogFile = path.join(BASE_DIR, 'logs', `file-sync-${daUsername}.log`);
  const syncLogFd   = require('fs').openSync(syncLogFile, 'a');
  const syncProc    = spawn('node', [syncScript, daUsername, workspace], {
    detached: true,
    stdio:    ['ignore', syncLogFd, syncLogFd],
    env: { ...safeEnv },
  });
  syncProc.unref();
  require('fs').closeSync(syncLogFd);

  sessions.push({
    service:   'file-sync',
    port:      null,
    workspace: rawWorkspace,
    started:   new Date().toISOString(),
    pid:       syncProc.pid,
    url:       null,
  });

  logger.info(`launch: started File Sync for ${daUsername} (PID ${syncProc.pid})`);

  // ── Launch OpenHands Agent ─────────────────────────────────────────────────
  const agentScript = path.join(SCRIPTS_DIR, 'start-agent.sh');
  const agentLogFile = path.join(BASE_DIR, 'logs', `agent-${daUsername}.log`);
  const agentLogFd   = require('fs').openSync(agentLogFile, 'a');
  const agentProc    = spawn('bash', [agentScript, daUsername, workspace, String(agentPort), mcpJwt], {
    detached: true,
    stdio:    ['ignore', agentLogFd, agentLogFd],
    env: {
      ...safeEnv,
      GOCODEME_JWT_TOKEN: mcpJwt,
      GOCODEME_DA_USERNAME: daUsername,
      GOCODEME_WHMCS_CLIENT_ID: String(whmcsClientId),
    },
  });
  require('fs').closeSync(agentLogFd);
  agentProc.unref();

  const agentUrl = `${PUBLIC_SCHEME}://${PUBLIC_HOST}/middleware/agent/${agentPort}/?gcm_auth=${ideAuthToken}`;
  sessions.push({
    service:   'agent',
    port:      agentPort,
    workspace: rawWorkspace,
    started:   new Date().toISOString(),
    pid:       agentProc.pid,
    url:       agentUrl,
  });

  logger.info(`launch: started Agent for ${daUsername} on port ${agentPort} (PID ${agentProc.pid})`);

  await saveSessions(daUsername, sessions);

  res.json({
    ok:       true,
    sessions,
    ideUrl:   theiaUrl,
    agentUrl: agentUrl,
  });
});

// ── GET /api/launch/sessions ───────────────────────────────────────────────
router.get('/sessions', requireSession, async (req, res) => {
  const { daUsername } = req.user;
  const sessions = await getSessions(daUsername);

  // Prune sessions whose process is no longer alive
  const alive = sessions.filter(s => {
    if (!s.pid) return true;
    try { process.kill(s.pid, 0); return true; } catch { return false; }
  });

  if (alive.length !== sessions.length) {
    await saveSessions(daUsername, alive);
  }

  res.json({ ok: true, sessions: alive });
});

// ── POST /api/launch/stop ──────────────────────────────────────────────────
router.post('/stop', requireSession, async (req, res) => {
  const { daUsername } = req.user;
  const sessions = await getSessions(daUsername);

  for (const s of sessions) {
    if (s.pid) {
      killPid(s.pid);
      logger.info(`launch: stopping ${s.service} PID ${s.pid} for ${daUsername}`);
    }
    // Also kill by port as fallback (in case PID was a bash parent that exited)
    if (s.port) {
      killPort(s.port);
    }
  }

  const redis = getRedis();
  await redis.del(sessionKey(daUsername));

  res.json({ ok: true, stopped: sessions.length });
});

module.exports = router;
