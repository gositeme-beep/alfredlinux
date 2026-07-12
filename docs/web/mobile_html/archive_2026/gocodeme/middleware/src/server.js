'use strict';

require('dotenv').config();

const express = require('express');
const helmet = require('helmet');
const cors = require('cors');
const morgan = require('morgan');
const rateLimit = require('express-rate-limit');

const config = require('./config');
const logger = require('./logger');
const { getRedis } = require('./redis');

// ── Routes ─────────────────────────────────────────────────────────────────
const ssoRoutes    = require('./routes/sso');
const tokenRoutes  = require('./routes/tokens');
const fileRoutes   = require('./routes/files');
const gitRoutes    = require('./routes/git');
const launchRoutes = require('./routes/launch');
const anthropicProxyRoutes = require('./routes/anthropicProxy');
const { createProxyMiddleware, attachUpgradeHandler } = require('./routes/ideProxy');

const app = express();

// Trust only the local Apache reverse proxy in front of this server (VULN-23 fix)
// Using 'loopback' instead of '1' ensures only 127.0.0.1/::1 is trusted,
// preventing external X-Forwarded-For spoofing.
app.set('trust proxy', 'loopback');

// ── Security headers ───────────────────────────────────────────────────────
// IDE/agent routes need eval/inline scripts for Theia — skip helmet entirely.
// Dashboard/usage routes get helmet with relaxed CSP for inline scripts.
// All other routes get full helmet protection.
const helmetDashboard = helmet({
  contentSecurityPolicy: {
    directives: {
      defaultSrc: ["'self'"],
      scriptSrc: ["'self'", "'unsafe-inline'", "'unsafe-eval'"],
      styleSrc: ["'self'", "'unsafe-inline'"],
      fontSrc: ["'self'"],
      imgSrc: ["'self'", 'data:', 'https:'],
      connectSrc: ["'self'", 'https://gositeme.com', 'wss://gositeme.com'],
    },
  },
});

app.use((req, res, next) => {
  if (
    req.path.startsWith('/ide/') ||
    req.path.startsWith('/agent/')
  ) {
    // SECURITY (R3 L-12): Minimal security headers even on IDE/agent routes
    res.setHeader('X-Content-Type-Options', 'nosniff');
    res.setHeader('X-Frame-Options', 'SAMEORIGIN');
    return next(); // Theia needs eval/inline — no helmet CSP
  }
  if (
    req.path === '/dashboard' ||
    req.path === '/usage' ||
    req.path.startsWith('/public/')
  ) {
    return helmetDashboard(req, res, next); // relaxed CSP for dashboard
  }
  helmet()(req, res, next); // full protection for API routes
});

// ── CORS — allow Theia IDE origin and gocodeme.com ─────────────────────────
const allowedOrigins = [
  'https://gocodeme.com',
  'https://www.gocodeme.com',
  'https://gositeme.com',
];
// Only allow localhost origins in development
if (config.server.env !== 'production') {
  allowedOrigins.push('http://localhost:3000', 'http://localhost:3030');
}

app.use(cors({
  origin(origin, cb) {
    // Allow requests with no origin (server-to-server, curl, WHMCS webhooks)
    if (!origin || allowedOrigins.includes(origin)) return cb(null, true);
    cb(new Error(`CORS: origin ${origin} not allowed`));
  },
  credentials: true,
}));

// ── Body parsing ───────────────────────────────────────────────────────────
// SECURITY (R2-12): Default limit is 1MB. Only the Anthropic proxy route
// gets a higher limit because conversation payloads include base64 images.
app.use('/api/anthropic-proxy', express.json({ limit: '50mb' }));
app.use(express.json({ limit: '1mb' }));
app.use(express.urlencoded({ extended: true, limit: '1mb' }));

// ── HTTP request logging ───────────────────────────────────────────────────
app.use(morgan('combined', {
  stream: { write: (msg) => logger.info(msg.trim()) },
  // Skip health-check noise
  skip: (req) => req.path === '/health',
}));

// ── Global rate limiter ────────────────────────────────────────────────────
const limiter = rateLimit({
  windowMs: config.rateLimit.windowMs,
  max: config.rateLimit.max * 3, // 180/min — dashboard loads ~25 panels
  standardHeaders: true,
  legacyHeaders: false,
  message: { ok: false, error: 'Too many requests — please slow down.' },
  validate: { xForwardedForHeader: false },
  keyGenerator: (req) => {
    // Rate-limit per user (from JWT) instead of per IP
    // This prevents shared-IP users from blocking each other
    try {
      const auth = req.headers.authorization || '';
      if (auth.startsWith('Bearer ')) {
        const jwt = require('jsonwebtoken');
        // SECURITY (VULN-R2-13): Use jwt.verify() not jwt.decode() to prevent
        // targeted DoS via forged tokens consuming another user's rate limit
        const decoded = jwt.verify(auth.slice(7), config.jwt.secret);
        if (decoded?.whmcsClientId) return 'user:' + decoded.whmcsClientId;
      }
    } catch (_) {}
    return req.ip;
  },
});
app.use('/api/', limiter);

// ── Anthropic Proxy (Theia IDE token tracking) ─────────────────────────────
// Mounted AFTER the global rate limiter BUT with its own plan-aware limit.
// Higher-tier plans get more API calls per minute.
// URL: /api/anthropic-proxy/:daUsername/v1/messages
const tc = require('./tokens/tokenCounter');

// Plan-tier rate limits (requests per minute for the Anthropic proxy)
const PLAN_RATE_LIMITS = {
  50000:   30,    // Free:         30 req/min (generous for trial, limits abuse)
  300000:  120,   // Builder:      120 req/min
  600000:  200,   // Professional: 200 req/min
  1500000: 300,   // Studio:       300 req/min
  3000000: 400,   // Business:     400 req/min
  5000000: 500,   // Enterprise:   500 req/min
};
const DEFAULT_RATE_LIMIT = 60;

// In-memory cache of per-user rate limits (refreshed every 5 min)
const userRateLimitCache = new Map();

async function getUserRateLimit(daUsername) {
  const cached = userRateLimitCache.get(daUsername);
  if (cached && Date.now() - cached.ts < 5 * 60 * 1000) return cached.limit;

  try {
    // Resolve client ID from DA username
    const redis = require('./redis').getRedis();
    let clientId = await redis.get(`client_id_by_da:${daUsername}`);
    if (!clientId) {
      // Scan forward mapping
      let cursor = '0';
      do {
        const [next, keys] = await redis.scan(cursor, 'MATCH', 'da_username:*', 'COUNT', 100);
        cursor = next;
        for (const key of keys) {
          if ((await redis.get(key)) === daUsername) {
            clientId = key.split(':')[1];
            break;
          }
        }
      } while (cursor !== '0' && !clientId);
    }

    if (clientId) {
      const tokenLimit = await tc.getLimit(clientId);
      const rateLimit = PLAN_RATE_LIMITS[tokenLimit] || DEFAULT_RATE_LIMIT;
      userRateLimitCache.set(daUsername, { limit: rateLimit, ts: Date.now() });
      return rateLimit;
    }
  } catch (err) {
    logger.error(`plan-rate-limit: ${err.message}`);
  }

  return DEFAULT_RATE_LIMIT;
}

const anthropicProxyLimiter = rateLimit({
  windowMs: 60 * 1000,
  max: async (req) => {
    const daUsername = req.params.daUsername;
    if (!daUsername) return DEFAULT_RATE_LIMIT;
    return getUserRateLimit(daUsername);
  },
  standardHeaders: true,
  legacyHeaders: false,
  keyGenerator: (req) => req.params.daUsername || req.ip,
  message: { type: 'error', error: { type: 'rate_limit_error', message: 'Too many requests' } },
  validate: { xForwardedForHeader: false },
});
// Compact & context-usage — direct routes BEFORE :daUsername to prevent "compact" becoming a username
app.post('/api/anthropic-proxy/compact', express.json({ limit: '4mb' }), (req, res) => {
  // Delegate to the router's /compact handler
  req.url = '/compact';
  anthropicProxyRoutes(req, res, (err) => {
    if (err) res.status(500).json({ ok: false, error: 'Internal error' });
  });
});
app.post('/api/anthropic-proxy/context-usage', express.json({ limit: '4mb' }), (req, res) => {
  req.url = '/context-usage';
  anthropicProxyRoutes(req, res, (err) => {
    if (err) res.status(500).json({ ok: false, error: 'Internal error' });
  });
});
app.get('/api/anthropic-proxy/compact/session', (req, res) => {
  req.url = '/compact/session';
  anthropicProxyRoutes(req, res, (err) => {
    if (err) res.status(500).json({ ok: false, error: 'Internal error' });
  });
});
// Main proxy — with :daUsername param for the catch-all handler
app.use('/api/anthropic-proxy/:daUsername', anthropicProxyLimiter, anthropicProxyRoutes);

// ── Health check ───────────────────────────────────────────────────────────
const healthJson = () => ({ ok: true, service: 'gocodeme-middleware', ts: new Date().toISOString() });
app.get('/health', (_req, res) => {
  res.json(healthJson());
});
// IDE Alfred widget (ideProxy.js) polls /middleware/api/health → upstream /api/health
app.get('/api/health', (_req, res) => {
  res.json(healthJson());
});

// ── Static assets (dashboard) ──────────────────────────────────────────────
const publicDir = require('path').join(__dirname, '../public');
app.use('/public', express.static(publicDir));

// ── Dashboard — served after WHMCS SSO redirect ────────────────────────────
// Serves the single-page dashboard HTML; auth is handled client-side via the
// JWT in the URL fragment (#token=...) that WHMCS SSO puts there.
app.get('/dashboard', (_req, res) => {
  res.sendFile(require('path').join(publicDir, 'dashboard.html'));
});

// Usage & Billing dashboard page
app.get('/usage', (_req, res) => {
  res.sendFile(require('path').join(publicDir, 'usage.html'));
});

// ── API routes ─────────────────────────────────────────────────────────────
const accessRoutes = require('./routes/access');
const adminRoutes  = require('./routes/admin');

const openclawRoutes    = require('./routes/openclaw');
const billingRoutes     = require('./routes/billing');
const onboardingRoutes  = require('./routes/onboarding');
const claudeRoutes      = require('./routes/claude');
const daRoutes          = require('./routes/da');
const hostingRoutes     = require('./routes/hosting');
const usageRoutes       = require('./routes/usage');
const aiTerminalRoutes  = require('./routes/aiTerminal');
const templateRoutes    = require('./routes/templates');
const referralRoutes    = require('./routes/referral');
const teamRoutes        = require('./routes/teams');
const developerRoutes   = require('./routes/developer');
const resellerRoutes    = require('./routes/reseller');
const extrasRoutes      = require('./routes/extras');
const alfredRoutes      = require('./routes/alfred');
const alfredChatRoutes  = require('./routes/alfredChat');
const voiceRelayRoutes  = require('./routes/voiceRelay');
const agentosRoutes     = require('./routes/agentosRuntime');
const trialChatRoutes   = require('./routes/trialChat');
const { registerAutopilotRoutes } = require('./routes/autopilotProxy');
const { startMonitoring, healthRoute } = require('./monitoring/healthMonitor');

app.use('/api/sso',                ssoRoutes);
app.use('/api/tokens',             tokenRoutes);
app.use('/api/access',             accessRoutes);
app.use('/api/admin',              adminRoutes);
app.use('/api/launch',             launchRoutes);
app.use('/api/files/:username',    fileRoutes);
app.use('/api/git/:username',      gitRoutes);
app.use('/api/openclaw',           openclawRoutes);
app.use('/api/billing',            billingRoutes);
app.use('/api/onboarding',         onboardingRoutes);
app.use('/api/claude',             claudeRoutes);
app.use('/api/da',                 daRoutes);
app.use('/api/hosting',            hostingRoutes);
app.use('/api/usage',              usageRoutes);
app.use('/api/ai-terminal',        aiTerminalRoutes);
app.use('/api/templates',          templateRoutes);
app.use('/api/referral',           referralRoutes);
app.use('/api/teams',              teamRoutes);
app.use('/api/developer',          developerRoutes);
app.use('/api/reseller',           resellerRoutes);
app.use('/api/extras',             extrasRoutes);
app.use('/api/alfred',             alfredRoutes);
app.use('/api/alfred-chat',        alfredChatRoutes);
app.use('/api/voice-relay',         voiceRelayRoutes);
app.use('/api/agentos',             agentosRoutes);
app.use('/api/trial',               trialChatRoutes);

// ── Autopilot (Live Browser Agent) REST endpoints ────────────────────────
const autopilotRouter = express.Router();
// Auth middleware: extract daUsername from JWT in Authorization header
autopilotRouter.use((req, res, next) => {
  const auth = req.headers.authorization || '';
  if (!auth.startsWith('Bearer ')) {
    return res.status(401).json({ error: 'Autopilot: authentication required' });
  }
  try {
    const jwt = require('jsonwebtoken');
    const decoded = jwt.verify(auth.slice(7), config.jwt.secret);
    if (decoded?.daUsername) {
      req._daUsername = decoded.daUsername;
    } else if (decoded?.whmcsClientId) {
      req._daUsername = `client_${decoded.whmcsClientId}`;
    } else {
      return res.status(401).json({ error: 'Autopilot: invalid token payload' });
    }
  } catch (err) {
    return res.status(401).json({ error: 'Autopilot: invalid or expired token' });
  }
  next();
});
registerAutopilotRoutes(autopilotRouter);
app.use('/api/autopilot', autopilotRouter);

app.use(healthRoute());

// ── IDE Reverse Proxy (HTTP) ─────────────────────────────────────────────
// Ensure trailing slash so browser resolves relative paths (./bundle.js) correctly
app.get(/^\/(ide|agent)\/(\d+)$/, (req, res) => {
  // Preserve query string when adding trailing slash
  const qs = req.originalUrl.includes('?') ? req.originalUrl.slice(req.originalUrl.indexOf('?')) : '';
  const fullPath = `/middleware${req.path}/${qs}`;
  res.redirect(301, fullPath);
});

// Proxies /ide/:port/* and /agent/:port/* to per-customer Theia/OpenHands
app.use('/ide/:port', createProxyMiddleware('ide'));
app.use('/agent/:port', createProxyMiddleware('agent'));

// ── 404 ────────────────────────────────────────────────────────────────────
app.use((_req, res) => res.status(404).json({ ok: false, error: 'Not found' }));

// ── Global error handler ───────────────────────────────────────────────────
// eslint-disable-next-line no-unused-vars
app.use((err, _req, res, _next) => {
  logger.error(err.stack || err.message);
  const status = err.status || 500;
  // SECURITY (R3 M-01): Never leak raw error messages to clients
  res.status(status).json({ ok: false, error: status >= 500 ? 'Internal server error' : (err.exposedMessage || 'Error') });
});

// ── Start ──────────────────────────────────────────────────────────────────
async function start() {
  // Warm Redis connection early so first requests don't pay connection cost
  getRedis();

  // Bind to 127.0.0.1 only — external access goes through the Apache reverse proxy
  const server = app.listen(config.server.port, '127.0.0.1', () => {
    logger.info(`GoCodeMe middleware running on 127.0.0.1:${config.server.port} [${config.server.env}]`);
  });

  // Attach WebSocket upgrade handler for IDE proxy (/ide/:port, /agent/:port)
  attachUpgradeHandler(server);

  // ── Health monitoring (self-ping, Redis hygiene, memory alerts) ──────────
  startMonitoring();

  // ── Idle session reaper — runs every 5 minutes ──────────────────────────
  // Kills IDE sessions that have had no API/proxy activity for 30 minutes.
  // Prevents zombie sessions from consuming server resources.
  const IDLE_TIMEOUT_MS = 30 * 60 * 1000; // 30 minutes
  const REAP_INTERVAL_MS = 5 * 60 * 1000; // check every 5 minutes

  // ── Email automation crons ──────────────────────────────────────────────
  const emailAuto = require('./billing/emailAutomation');

  // Welcome email drip scheduler — check every 30 minutes
  setInterval(() => {
    emailAuto.runEmailReaper().catch(e => logger.debug(`email-reaper: ${e.message}`));
  }, 30 * 60 * 1000);

  // Winback emails — check every 6 hours
  setInterval(() => {
    emailAuto.processWinbackEmails().catch(e => logger.debug(`email-winback: ${e.message}`));
  }, 6 * 60 * 60 * 1000);

  // Upgrade suggestion emails — check once per day (24 hours)
  setInterval(() => {
    emailAuto.processUpgradeSuggestions().catch(e => logger.debug(`email-upgrade: ${e.message}`));
  }, 24 * 60 * 60 * 1000);

  // Weekly usage digest — every 7 days
  setInterval(() => {
    emailAuto.processWeeklyDigest().catch(e => logger.debug(`email-digest: ${e.message}`));
  }, 7 * 24 * 60 * 60 * 1000);

  // Run once on startup (delayed 60s to let everything initialize)
  setTimeout(() => {
    emailAuto.runEmailReaper().catch(() => {});
    emailAuto.processWinbackEmails().catch(() => {});
    emailAuto.processUpgradeSuggestions().catch(() => {});
    emailAuto.processWeeklyDigest().catch(() => {});
  }, 60 * 1000);

  setInterval(async () => {
    try {
      const redis = getRedis();
      // SECURITY (R3 M-03): Use SCAN instead of KEYS to avoid O(N) Redis blocking
      const scanKeys = require('./utils/scanKeys');
      const keys = await scanKeys(redis, 'launch:sessions:*');
      const now = Date.now();
      for (const key of keys) {
        const daUsername = key.replace('launch:sessions:', '');
        const sessRaw = await redis.get(key);
        let sessions = [];
        try { sessions = JSON.parse(sessRaw) || []; } catch {}
        if (sessions.length === 0) { await redis.del(key); continue; }

        // ── Dead PID detection — clean up sessions where all processes died ──
        const allDead = sessions.every(s => {
          if (!s.pid) return true;
          try { process.kill(s.pid, 0); return false; } catch { return true; }
        });
        if (allDead) {
          await redis.del(key);
          await redis.del(`activity:${daUsername}`);
          logger.info(`idle-reaper: cleaned dead session for ${daUsername} (all ${sessions.length} PIDs dead)`);
          continue;
        }

        // ── Idle timeout — kill sessions inactive for 30+ minutes ──
        const lastActivity = parseInt(await redis.get(`activity:${daUsername}`) || '0', 10);
        // If no activity recorded, skip (could be newly launched)
        if (!lastActivity) continue;
        const idleMs = now - lastActivity;
        if (idleMs > IDLE_TIMEOUT_MS) {
          // Kill all sessions for this idle user
          for (const s of sessions) {
            if (s.pid) {
              try { process.kill(s.pid, 'SIGTERM'); } catch {}
            }
          }
          await redis.del(key);
          await redis.del(`activity:${daUsername}`);
          logger.info(`idle-reaper: killed ${sessions.length} sessions for ${daUsername} (idle ${Math.round(idleMs / 60000)}min)`);
        }
      }
    } catch (err) {
      logger.debug(`idle-reaper: ${err.message}`);
    }
  }, REAP_INTERVAL_MS);

  // Graceful shutdown
  process.on('SIGTERM', () => shutdown(server));
  process.on('SIGINT',  () => shutdown(server));
}

async function shutdown(server) {
  logger.info('Shutting down...');
  server.close(() => {
    logger.info('HTTP server closed');
    process.exit(0);
  });
}

start().catch((err) => {
  logger.error(`Failed to start: ${err.message}`);
  process.exit(1);
});

module.exports = app; // exported for tests
