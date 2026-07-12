'use strict';

/**
 * Hosting Management API Routes
 *
 * Exposes all DirectAdmin management functions via REST API.
 * These routes power both the dashboard UI and the MCP/AI agent.
 *
 * All routes require JWT authentication (the session token from SSO login).
 * The DA username is extracted from the JWT token payload.
 *
 * Prefix: /api/hosting
 *
 * ── Databases ──────────────────────────────────────────────────────────────
 * GET    /databases                     — list databases
 * POST   /databases                     — create database
 * DELETE /databases/:name               — delete database
 * GET    /databases/:name               — get database info
 *
 * ── Domains ────────────────────────────────────────────────────────────────
 * GET    /domains                       — list domains
 * GET    /domains/:domain/subdomains    — list subdomains
 * POST   /domains/:domain/subdomains    — create subdomain
 * DELETE /domains/:domain/subdomains/:sub — delete subdomain
 * GET    /domains/:domain/info          — get domain info
 *
 * ── Email ──────────────────────────────────────────────────────────────────
 * GET    /email/:domain                 — list email accounts
 * POST   /email/:domain                 — create email account
 * DELETE /email/:domain/:user           — delete email account
 * PUT    /email/:domain/:user/password  — change email password
 * POST   /email/:domain/:user/forwarder — create forwarder
 * POST   /email/:domain/:user/autoresponder — create auto-responder
 *
 * ── DNS ────────────────────────────────────────────────────────────────────
 * GET    /dns/:domain                   — list DNS records
 * POST   /dns/:domain                   — add DNS record
 * DELETE /dns/:domain                   — delete DNS record
 *
 * ── SSL ────────────────────────────────────────────────────────────────────
 * POST   /ssl/:domain/letsencrypt       — request Let's Encrypt SSL
 * GET    /ssl/:domain/status            — get SSL status
 * POST   /ssl/:domain/force             — force HTTPS redirect
 *
 * ── Cron ───────────────────────────────────────────────────────────────────
 * GET    /cron                          — list cron jobs
 * POST   /cron                          — create cron job
 * DELETE /cron/:index                   — delete cron job
 *
 * ── Backups ────────────────────────────────────────────────────────────────
 * GET    /backups                       — list backups
 * POST   /backups                       — create backup
 * POST   /backups/restore               — restore from backup
 *
 * ── Stats ──────────────────────────────────────────────────────────────────
 * GET    /stats                         — get usage stats
 * GET    /stats/config                  — get account config/limits
 * GET    /stats/summary                 — get full account summary
 */

const express = require('express');
const router  = express.Router();
const logger  = require('../logger');

// DA management modules
const dbMgr     = require('../directadmin/databaseManager');
const domainMgr = require('../directadmin/domainManager');
const emailMgr  = require('../directadmin/emailManager');
const dnsMgr    = require('../directadmin/dnsManager');
const sslMgr    = require('../directadmin/sslManager');
const cronMgr   = require('../directadmin/cronManager');
const backupMgr = require('../directadmin/backupManager');
const statsMgr  = require('../directadmin/statsManager');
const { requireSession } = require('../auth/middleware');

// SECURITY (R3-05): Sanitize error messages to prevent internal detail leaks
// Server errors get a generic message; client errors (4xx) keep the original
function safeError(err) {
  const msg = err.message || 'Unknown error';
  // DA API errors that are safe to show (user-facing issues)
  if (msg.includes('already exists') || msg.includes('not found') ||
      msg.includes('limit reached') || msg.includes('not allowed')) {
    return msg.slice(0, 200);
  }
  // Strip internal details (file paths, stack traces, connection strings)
  if (msg.includes('/home/') || msg.includes('ECONNREFUSED') || msg.includes('ENOTFOUND') ||
      msg.includes('password') || msg.includes('EACCES') || msg.includes('timeout')) {
    return 'Operation failed — please try again or contact support';
  }
  return msg.slice(0, 200);
}

// ── Auth middleware ────────────────────────────────────────────────────────
// Extract DA username from JWT (set by requireSession)
function requireDaUser(req, res, next) {
  const daUsername = req.user?.daUsername || req.params?.username;
  if (!daUsername) {
    return res.status(401).json({ ok: false, error: 'DA username not found in session' });
  }
  req.daUsername = daUsername;
  next();
}

router.use(requireSession, requireDaUser);

// ══════════════════════════════════════════════════════════════════════════
// DATABASES
// ══════════════════════════════════════════════════════════════════════════

router.get('/databases', async (req, res) => {
  try {
    const databases = await dbMgr.listDatabases(req.daUsername);
    res.json({ ok: true, databases });
  } catch (err) {
    logger.error(`hosting-api: list databases failed: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

router.post('/databases', async (req, res) => {
  const { name, user, password } = req.body;
  if (!name || !user || !password) {
    return res.status(400).json({ ok: false, error: 'name, user, and password are required' });
  }
  try {
    const result = await dbMgr.createDatabase(req.daUsername, name, user, password);
    res.json({ ok: true, ...result });
  } catch (err) {
    logger.error(`hosting-api: create database failed: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

router.delete('/databases/:name', async (req, res) => {
  try {
    await dbMgr.deleteDatabase(req.daUsername, req.params.name);
    res.json({ ok: true });
  } catch (err) {
    logger.error(`hosting-api: delete database failed: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

router.get('/databases/:name', async (req, res) => {
  try {
    const info = await dbMgr.getDatabaseInfo(req.daUsername, req.params.name);
    res.json({ ok: true, info });
  } catch (err) {
    logger.error(`hosting-api: database info failed: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ══════════════════════════════════════════════════════════════════════════
// DOMAINS
// ══════════════════════════════════════════════════════════════════════════

router.get('/domains', async (req, res) => {
  try {
    const domains = await domainMgr.listDomains(req.daUsername);
    res.json({ ok: true, domains });
  } catch (err) {
    logger.error(`hosting-api: list domains failed: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

router.get('/domains/:domain/subdomains', async (req, res) => {
  try {
    const subdomains = await domainMgr.listSubdomains(req.daUsername, req.params.domain);
    res.json({ ok: true, subdomains });
  } catch (err) {
    logger.error(`hosting-api: list subdomains failed: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

router.post('/domains/:domain/subdomains', async (req, res) => {
  const { subdomain } = req.body;
  if (!subdomain) {
    return res.status(400).json({ ok: false, error: 'subdomain name required' });
  }
  try {
    const full = await domainMgr.createSubdomain(req.daUsername, req.params.domain, subdomain);
    res.json({ ok: true, subdomain: full });
  } catch (err) {
    logger.error(`hosting-api: create subdomain failed: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

router.delete('/domains/:domain/subdomains/:sub', async (req, res) => {
  try {
    await domainMgr.deleteSubdomain(req.daUsername, req.params.domain, req.params.sub);
    res.json({ ok: true });
  } catch (err) {
    logger.error(`hosting-api: delete subdomain failed: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

router.get('/domains/:domain/info', async (req, res) => {
  try {
    const info = await domainMgr.getDomainInfo(req.daUsername, req.params.domain);
    res.json({ ok: true, info });
  } catch (err) {
    logger.error(`hosting-api: domain info failed: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ══════════════════════════════════════════════════════════════════════════
// EMAIL
// ══════════════════════════════════════════════════════════════════════════

router.get('/email/:domain', async (req, res) => {
  try {
    const accounts = await emailMgr.listEmailAccounts(req.daUsername, req.params.domain);
    res.json({ ok: true, accounts });
  } catch (err) {
    logger.error(`hosting-api: list email failed: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

router.post('/email/:domain', async (req, res) => {
  const { user, password, quota } = req.body;
  if (!user || !password) {
    return res.status(400).json({ ok: false, error: 'user and password are required' });
  }
  try {
    const result = await emailMgr.createEmailAccount(
      req.daUsername, req.params.domain, user, password, quota
    );
    res.json({ ok: true, ...result });
  } catch (err) {
    logger.error(`hosting-api: create email failed: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

router.delete('/email/:domain/:user', async (req, res) => {
  try {
    await emailMgr.deleteEmailAccount(req.daUsername, req.params.domain, req.params.user);
    res.json({ ok: true });
  } catch (err) {
    logger.error(`hosting-api: delete email failed: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

router.put('/email/:domain/:user/password', async (req, res) => {
  const { password } = req.body;
  if (!password) {
    return res.status(400).json({ ok: false, error: 'password required' });
  }
  try {
    await emailMgr.changeEmailPassword(req.daUsername, req.params.domain, req.params.user, password);
    res.json({ ok: true });
  } catch (err) {
    logger.error(`hosting-api: change email password failed: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

router.post('/email/:domain/:user/forwarder', async (req, res) => {
  const { forwardTo } = req.body;
  if (!forwardTo) {
    return res.status(400).json({ ok: false, error: 'forwardTo email required' });
  }
  try {
    await emailMgr.createForwarder(req.daUsername, req.params.domain, req.params.user, forwardTo);
    res.json({ ok: true });
  } catch (err) {
    logger.error(`hosting-api: create forwarder failed: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

router.post('/email/:domain/:user/autoresponder', async (req, res) => {
  const { subject, message } = req.body;
  if (!subject || !message) {
    return res.status(400).json({ ok: false, error: 'subject and message required' });
  }
  try {
    await emailMgr.createAutoResponder(
      req.daUsername, req.params.domain, req.params.user, subject, message
    );
    res.json({ ok: true });
  } catch (err) {
    logger.error(`hosting-api: create autoresponder failed: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ══════════════════════════════════════════════════════════════════════════
// DNS
// ══════════════════════════════════════════════════════════════════════════

router.get('/dns/:domain', async (req, res) => {
  try {
    const records = await dnsMgr.listDnsRecords(req.daUsername, req.params.domain);
    res.json({ ok: true, records });
  } catch (err) {
    logger.error(`hosting-api: list DNS failed: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

router.post('/dns/:domain', async (req, res) => {
  const { type, name, value, ttl } = req.body;
  if (!type || !name || !value) {
    return res.status(400).json({ ok: false, error: 'type, name, and value are required' });
  }
  try {
    await dnsMgr.addDnsRecord(req.daUsername, req.params.domain, type, name, value, ttl);
    res.json({ ok: true });
  } catch (err) {
    logger.error(`hosting-api: add DNS failed: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

router.delete('/dns/:domain', async (req, res) => {
  const { type, name, value } = req.body;
  if (!type || !name || !value) {
    return res.status(400).json({ ok: false, error: 'type, name, and value are required' });
  }
  try {
    await dnsMgr.deleteDnsRecord(req.daUsername, req.params.domain, type, name, value);
    res.json({ ok: true });
  } catch (err) {
    logger.error(`hosting-api: delete DNS failed: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ══════════════════════════════════════════════════════════════════════════
// SSL
// ══════════════════════════════════════════════════════════════════════════

router.post('/ssl/:domain/letsencrypt', async (req, res) => {
  const { wildcard } = req.body;
  try {
    const result = await sslMgr.requestLetsEncrypt(req.daUsername, req.params.domain, wildcard);
    res.json({ ok: true, ...result });
  } catch (err) {
    logger.error(`hosting-api: LE request failed: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

router.get('/ssl/:domain/status', async (req, res) => {
  try {
    const status = await sslMgr.getSSLStatus(req.daUsername, req.params.domain);
    res.json({ ok: true, status });
  } catch (err) {
    logger.error(`hosting-api: SSL status failed: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

router.post('/ssl/:domain/force', async (req, res) => {
  try {
    await sslMgr.enableForceSSL(req.daUsername, req.params.domain);
    res.json({ ok: true });
  } catch (err) {
    logger.error(`hosting-api: force SSL failed: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ══════════════════════════════════════════════════════════════════════════
// CRON JOBS
// ══════════════════════════════════════════════════════════════════════════

router.get('/cron', async (req, res) => {
  try {
    const jobs = await cronMgr.listCronJobs(req.daUsername);
    res.json({ ok: true, jobs });
  } catch (err) {
    logger.error(`hosting-api: list cron failed: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

router.post('/cron', async (req, res) => {
  const { minute, hour, dayOfMonth, month, dayOfWeek, command } = req.body;
  if (!command) {
    return res.status(400).json({ ok: false, error: 'command is required' });
  }
  // SECURITY (R3-02): Validate cron command — must start with an absolute path
  // under the user's home directory, no shell metacharacters that enable injection
  const daUser = req.daUsername;
  const allowedPrefix = `/home/${daUser}/`;
  if (!command.startsWith(allowedPrefix) && !command.startsWith('/usr/bin/') && !command.startsWith('/usr/local/bin/')) {
    return res.status(400).json({ ok: false, error: `Cron commands must use absolute paths starting with ${allowedPrefix}, /usr/bin/, or /usr/local/bin/` });
  }
  // Block shell metacharacters that could chain commands
  const dangerousChars = /[;|&`$(){}\\<>!\n\r]/;
  if (dangerousChars.test(command)) {
    return res.status(400).json({ ok: false, error: 'Cron command contains disallowed characters (;|&`$(){}\\<>!)' });
  }
  // Validate cron time fields — only digits, commas, hyphens, asterisks, slashes
  const cronFieldPattern = /^[\d,*/-]+$/;
  for (const [field, value] of [['minute', minute], ['hour', hour], ['dayOfMonth', dayOfMonth], ['month', month], ['dayOfWeek', dayOfWeek]]) {
    if (value && !cronFieldPattern.test(value)) {
      return res.status(400).json({ ok: false, error: `Invalid cron ${field}: ${value}` });
    }
  }
  try {
    await cronMgr.createCronJob(
      req.daUsername,
      minute || '*', hour || '*', dayOfMonth || '*', month || '*', dayOfWeek || '*',
      command
    );
    res.json({ ok: true });
  } catch (err) {
    logger.error(`hosting-api: create cron failed: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

router.delete('/cron/:index', async (req, res) => {
  try {
    await cronMgr.deleteCronJob(req.daUsername, parseInt(req.params.index, 10));
    res.json({ ok: true });
  } catch (err) {
    logger.error(`hosting-api: delete cron failed: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ══════════════════════════════════════════════════════════════════════════
// BACKUPS
// ══════════════════════════════════════════════════════════════════════════

router.get('/backups', async (req, res) => {
  try {
    const backups = await backupMgr.listBackups(req.daUsername);
    res.json({ ok: true, backups });
  } catch (err) {
    logger.error(`hosting-api: list backups failed: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

router.post('/backups', async (req, res) => {
  const { files, databases, email } = req.body;
  try {
    const result = await backupMgr.createBackup(req.daUsername, { files, databases, email });
    res.json({ ok: true, ...result });
  } catch (err) {
    logger.error(`hosting-api: create backup failed: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

router.post('/backups/restore', async (req, res) => {
  const { backupFile, files, databases, email } = req.body;
  if (!backupFile) {
    return res.status(400).json({ ok: false, error: 'backupFile is required' });
  }
  try {
    const result = await backupMgr.restoreBackup(req.daUsername, backupFile, { files, databases, email });
    res.json({ ok: true, ...result });
  } catch (err) {
    logger.error(`hosting-api: restore failed: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ══════════════════════════════════════════════════════════════════════════
// STATS / USAGE
// ══════════════════════════════════════════════════════════════════════════

router.get('/stats', async (req, res) => {
  try {
    const usage = await statsMgr.getUsage(req.daUsername);
    res.json({ ok: true, usage });
  } catch (err) {
    logger.error(`hosting-api: get usage failed: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

router.get('/stats/config', async (req, res) => {
  try {
    const config = await statsMgr.getAccountConfig(req.daUsername);
    res.json({ ok: true, config });
  } catch (err) {
    logger.error(`hosting-api: get config failed: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

router.get('/stats/summary', async (req, res) => {
  try {
    const summary = await statsMgr.getAccountSummary(req.daUsername);
    res.json({ ok: true, summary });
  } catch (err) {
    logger.error(`hosting-api: get summary failed: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

module.exports = router;
