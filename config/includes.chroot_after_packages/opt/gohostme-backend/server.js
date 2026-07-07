require('dotenv').config();

const express = require('express');
const session = require('express-session');
const mysql = require('mysql2/promise');
const mysqlEscape = require('mysql2').escape;
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const https = require('https');
const http = require('http');
const path = require('path');
const crypto = require('crypto');
const si = require('systeminformation');
const compression = require('compression');
const morgan = require('morgan');

const app = express();
const PORT = process.env.GOHOSTME_PORT || 2224;
const JWT_SECRET = process.env.JWT_SECRET || crypto.randomBytes(64).toString('hex');
const SSO_SECRET_FILE = process.env.SSO_SECRET_FILE || '/home/gositeme/.vault/ecosystem-sso-secret';

// ── DB Pool ────────────────────────────────────────────────────────────────
const db = mysql.createPool({
  host: '127.0.0.1',
  user: process.env.DB_USER || 'gositeme_whmcs',
  password: process.env.DB_PASS || '!q@w#e$r5t',
  database: process.env.DB_NAME || 'gositeme_whmcs',
  waitForConnections: true,
  connectionLimit: 10
});

// ── DA API Wrapper ─────────────────────────────────────────────────────────
const DA_HOST = process.env.DA_HOST || 'localhost';
const DA_PORT = process.env.DA_PORT || 2222;
const DA_ADMIN = process.env.DA_ADMIN || '';
const DA_PASS = process.env.DA_PASS || '';

function daRequest(path, params = {}) {
  // DA permanently retired 2026-04-27 - Phase C jump-ship complete.
  // All vhost/SSL/DNS ops now go through /opt/gohostme/bridge.sh (HMAC tier).
  return Promise.reject(new Error('DA_RETIRED'));
}

// ── Bridge Helper — calls bridge.sh with HMAC token ──────────────────────
const BRIDGE_PATH = '/opt/gohostme/bridge.sh';
const BRIDGE_HMAC_FILE = '/home/gositeme/.vault/bridge-hmac-secret';
let _bridgeSecret = null;
let _ssoSecret = null;

function getBridgeSecret() {
  if (!_bridgeSecret) {
    try { _bridgeSecret = require('fs').readFileSync(BRIDGE_HMAC_FILE, 'utf8').trim(); }
    catch { _bridgeSecret = null; }
  }
  return _bridgeSecret;
}

function getSsoSecret() {
  if (_ssoSecret !== null) return _ssoSecret;

  const envSecret = (process.env.SSO_SECRET || '').trim();
  if (envSecret) {
    _ssoSecret = envSecret;
    return _ssoSecret;
  }

  try { _ssoSecret = require('fs').readFileSync(SSO_SECRET_FILE, 'utf8').trim(); }
  catch { _ssoSecret = ''; }

  return _ssoSecret;
}

function bridgeToken(command, args = '') {
  const secret = getBridgeSecret();
  if (!secret) throw new Error('Bridge HMAC secret not available');
  const ts = Math.floor(Date.now() / 1000);
  const payload = `${ts}:${command}:${args}`;
  const hmac = crypto.createHmac('sha256', secret).update(payload).digest('hex');
  return `${ts}:${hmac}`;
}

function bridge(command, args = [], { timeout = 30000, approval } = {}) {
  return new Promise((resolve, reject) => {
    const tier = ['vhost-create','vhost-delete','vhost-ssl-update','dns-zone-create',
      'dns-record-add','dns-record-delete','firewall-add','firewall-delete',
      'da-domain-register','full-domain-setup','fail2ban-unban','bridge-sync'];
    const needsToken = true; // always send token for safety
    const argsStr = args.join(' ');
    const cmdArgs = [BRIDGE_PATH, command];
    if (needsToken) {
      const token = bridgeToken(command, argsStr);
      cmdArgs.push(`--token=${token}`);
    }
    if (approval) cmdArgs.push(`--approval=${approval}`);
    cmdArgs.push(...args);

    const { execFile } = require('child_process');
    execFile('sudo', cmdArgs, { timeout, maxBuffer: 1024 * 1024 }, (err, stdout, stderr) => {
      const output = (stdout || '').trim();
      const errOutput = (stderr || '').trim();
      if (err && !output) return reject(new Error(errOutput || err.message));
      // Bridge returns OK: or ERROR: prefix
      if (output.startsWith('ERROR:')) return reject(new Error(output));
      resolve(output);
    });
  });
}

// ── Middleware ─────────────────────────────────────────────────────────────
app.use(compression());
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true, limit: '10mb' }));
app.use(express.static(path.join(__dirname, 'public')));
app.use(morgan('combined', { stream: { write: msg => process.stdout.write(msg) } }));

app.use(session({
  secret: JWT_SECRET,
  resave: false,
  saveUninitialized: false,
  cookie: { secure: false, httpOnly: true, sameSite: 'lax', maxAge: 8 * 3600 * 1000 }
}));

// ── Auth Middleware ────────────────────────────────────────────────────────
function getSessionClientId(req) {
  return parseInt(req.session?.client_id || req.session?.uid || 0, 10) || 0;
}

function isPrivilegedSession(req) {
  const clientId = getSessionClientId(req);
  return Boolean(req.session?.admin) || clientId === 33;
}

function buildDisplayName(user) {
  return (user.name || `${user.firstname || ''} ${user.lastname || ''}`.trim() || user.email || '').trim();
}

function setAuthenticatedSession(req, user, { admin = false } = {}) {
  const clientId = parseInt(user.client_id || user.id, 10);
  const email = (user.email || '').trim();
  const name = buildDisplayName(user) || `Client ${clientId}`;
  const now = Math.floor(Date.now() / 1000);
  const privileged = Boolean(admin) || clientId === 33;

  req.session.uid = clientId;
  req.session.client_id = clientId;
  req.session.logged_in = true;
  req.session.email = email;
  req.session.client_email = email;
  req.session.name = name;
  req.session.client_name = name;
  req.session.username = name;
  req.session.admin = privileged;
  req.session.last_activity = now;
  if (!req.session.login_time) req.session.login_time = now;

  return {
    id: clientId,
    uid: clientId,
    client_id: clientId,
    email,
    name,
    admin: privileged,
    logged_in: true
  };
}

function issueAuthToken(user) {
  return jwt.sign(
    {
      uid: user.uid || user.id,
      client_id: user.client_id || user.id,
      email: user.email,
      name: user.name,
      admin: Boolean(user.admin)
    },
    JWT_SECRET,
    { expiresIn: '8h' }
  );
}

function decodeSsoToken(token) {
  const ssoSecret = getSsoSecret();
  if (!ssoSecret) throw new Error('SSO is not configured');
  const decoded = Buffer.from(String(token || ''), 'base64').toString('utf8');
  const parts = decoded.split('|');
  if (parts.length !== 3) throw new Error('Malformed SSO token');

  const [clientIdRaw, timestampRaw, signature] = parts;
  const clientId = parseInt(clientIdRaw, 10);
  const timestamp = parseInt(timestampRaw, 10);
  if (!clientId || !timestamp || !signature) throw new Error('Malformed SSO token');

  const age = Math.floor(Date.now() / 1000) - timestamp;
  if (age < 0 || age > 300) throw new Error('SSO token expired');

  const expected = crypto
    .createHmac('sha256', ssoSecret)
    .update(`${clientId}|${timestamp}`)
    .digest('hex');

  if (!crypto.timingSafeEqual(Buffer.from(signature), Buffer.from(expected))) {
    throw new Error('Invalid SSO signature');
  }

  return clientId;
}

async function loadActiveClientById(clientId) {
  const [rows] = await db.query(
    'SELECT id, firstname, lastname, email, status FROM clients WHERE id = ? LIMIT 1',
    [clientId]
  );
  if (!rows[0] || rows[0].status !== 'Active') return null;
  return rows[0];
}

function safeReturnToPath(rawValue, fallback = '/gohostme/dashboard') {
  const value = typeof rawValue === 'string' ? rawValue.trim() : '';
  if (!value.startsWith('/') || value.startsWith('//')) return fallback;
  return value;
}

function sanitizeDomain(value) {
  return String(value || '').replace(/[^a-zA-Z0-9.-]/g, '').toLowerCase();
}

async function canAccessDomain(req, domain) {
  if (!domain) return false;
  if (isPrivilegedSession(req)) return true;

  const clientId = getSessionClientId(req);
  if (!clientId) return false;

  const [rows] = await db.query(
    'SELECT 1 FROM tbldomains WHERE userid = ? AND domain = ? LIMIT 1',
    [clientId, domain]
  ).catch(() => [[]]);

  return Boolean(rows[0]);
}

function requireAuth(req, res, next) {
  if (req.session && (req.session.client_id || req.session.uid)) {
    if (!req.session.uid && req.session.client_id) req.session.uid = req.session.client_id;
    if (!req.session.client_id && req.session.uid) req.session.client_id = req.session.uid;
    return next();
  }
  const tok = req.headers['authorization']?.replace('Bearer ', '');
  if (tok) {
    try {
      const d = jwt.verify(tok, JWT_SECRET);
      setAuthenticatedSession(req, {
        id: d.client_id || d.uid,
        client_id: d.client_id || d.uid,
        email: d.email,
        name: d.name
      }, { admin: d.admin });
      return next();
    } catch (e) { /* fall through */ }
  }
  res.status(401).json({ error: 'Unauthorized' });
}

// ── Routes ─────────────────────────────────────────────────────────────────

// ── Auth ──────────────────────────────────────────────────────────────────
app.post('/api/auth/login', async (req, res) => {
  try {
    const email = String(req.body.email || req.body.username || '').trim();
    const { password } = req.body;
    if (!email || !password) return res.status(400).json({ error: 'Email and password required' });

    // Check GoSiteMe clients table
    const [rows] = await db.query(
      'SELECT id, firstname, lastname, email, password, status FROM clients WHERE email = ? LIMIT 1',
      [email]
    );

    // Also check admin table
    const [adminRows] = await db.query(
      'SELECT id, firstname, lastname, username as email, passwordhash as password FROM tbladmins WHERE username = ? LIMIT 1',
      [email]
    ).catch(() => [[]]);

    let user = rows[0] || null;
    let isAdmin = false;

    if (!user && adminRows[0]) {
      user = adminRows[0];
      isAdmin = true;
    }

    if (!user) return res.status(401).json({ error: 'Invalid credentials' });
    if (!isAdmin && user.status !== 'Active') return res.status(403).json({ error: 'Account not active' });

    let valid = false;
    const pw = user.password || '';
    if (pw.startsWith('$2y$') || pw.startsWith('$2b$') || pw.startsWith('$2a$')) {
      // bcrypt (PHP $2y$ is compatible with JS $2b$)
      const normalized = pw.replace(/^\$2y\$/, '$2b$');
      valid = await bcrypt.compare(password, normalized);
    } else {
      // MD5 fallback
      const md5pass = crypto.createHash('md5').update(password).digest('hex');
      valid = (md5pass === pw || password === pw);
    }

    // Also allow commander override — client_id 33 with vault-stored password
    if (!valid && user.id == 33) {
      const md5pass = crypto.createHash('md5').update(password).digest('hex');
      valid = (md5pass === pw);
    }

    if (!valid) return res.status(401).json({ error: 'Invalid credentials' });

    const sessionUser = setAuthenticatedSession(req, user, { admin: isAdmin });
    const token = issueAuthToken(sessionUser);

    res.json({
      ok: true,
      token,
      user: sessionUser
    });
  } catch (err) {
    console.error('Login error:', err);
    res.status(500).json({ error: 'Login failed' });
  }
});

app.get('/api/auth/sso', async (req, res) => {
  const wantsJson = (req.headers.accept || '').includes('application/json');
  const returnTo = safeReturnToPath(req.query.returnTo);

  try {
    const clientId = decodeSsoToken(req.query.sso);
    const client = await loadActiveClientById(clientId);
    if (!client) return res.status(403).json({ error: 'SSO client not active' });

    const sessionUser = setAuthenticatedSession(req, client, { admin: false });
    const token = issueAuthToken(sessionUser);

    req.session.save((saveErr) => {
      if (saveErr) {
        console.error('SSO session save error:', saveErr);
        return res.status(500).json({ error: 'SSO session failed' });
      }

      if (wantsJson) {
        return res.json({ ok: true, token, user: sessionUser, returnTo });
      }

      res.redirect(returnTo);
    });
  } catch (err) {
    if (wantsJson) return res.status(401).json({ error: err.message || 'Invalid SSO token' });
    res.redirect(`${returnTo}${returnTo.includes('?') ? '&' : '?'}auth_error=sso`);
  }
});

app.post('/api/auth/logout', (req, res) => {
  if (!req.session) return res.json({ ok: true });
  req.session.destroy(() => {
    res.clearCookie('connect.sid');
    res.json({ ok: true });
  });
});

app.get('/api/auth/me', requireAuth, async (req, res) => {
  const clientId = getSessionClientId(req);
  res.json({
    uid: clientId,
    client_id: clientId,
    email: req.session.email || req.session.client_email,
    client_email: req.session.client_email || req.session.email,
    name: req.session.name || req.session.client_name,
    client_name: req.session.client_name || req.session.name,
    admin: isPrivilegedSession(req),
    logged_in: true
  });
});

// ── System Stats ──────────────────────────────────────────────────────────
app.get('/api/system/stats', requireAuth, requireAdmin, async (req, res) => {
  try {
    const [cpu, mem, disk, load, uptime, os] = await Promise.all([
      si.currentLoad(),
      si.mem(),
      si.fsSize(),
      si.currentLoad(),
      Promise.resolve(si.time()),
      si.osInfo()
    ]);
    const mainDisk = disk.find(d => d.mount === '/') || disk[0] || {};
    res.json({
      cpu: Math.round(load.currentLoad),
      memory: {
        total: mem.total,
        used: mem.used,
        free: mem.free,
        percent: Math.round((mem.used / mem.total) * 100)
      },
      disk: {
        total: mainDisk.size,
        used: mainDisk.used,
        free: mainDisk.available,
        percent: Math.round(mainDisk.use)
      },
      uptime: si.time().uptime,
      loadAverage: os.hostname ? require('os').loadavg().map(v => v.toFixed(2)).join(' / ') : null,
      platform: `${os.distro} ${os.release}`,
      hostname: os.hostname
    });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.get('/api/system/processes', requireAuth, requireAdmin, async (req, res) => {
  try {
    const procs = await si.processes();
    const top = procs.list
      .sort((a, b) => b.cpu - a.cpu)
      .slice(0, 20)
      .map(p => ({
        pid: p.pid,
        user: p.user || p.owner || 'unknown',
        cpu: Math.round((p.cpu || 0) * 10) / 10,
        mem: Math.round((p.mem || 0) * 10) / 10,
        command: p.command || p.name || ''
      }));
    res.json({ processes: top });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.get('/api/system/services', requireAuth, requireAdmin, async (req, res) => {
  try {
    const services = await si.services('apache2,mysqld,pm2,redis,nginx,postfix,dovecot,proftpd,bind9');
    res.json({
      services: services.map(s => ({
        name: s.name,
        status: s.running ? 'running' : 'stopped',
        description: s.startmode || ''
      }))
    });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// ── Domains ────────────────────────────────────────────────────────────────
app.get('/api/domains', requireAuth, async (req, res) => {
  try {
    const clientId = getSessionClientId(req);
    const [rows] = await db.query(
      'SELECT domain, status, registrar, expirydate FROM tbldomains WHERE userid = ? ORDER BY domain',
      [clientId]
    ).catch(() => [[]]);

    if (!isPrivilegedSession(req)) {
      return res.json({ source: 'whmcs', domains: rows.map(r => r.domain), whmcs: rows });
    }

    // Try DA API first
    const daResp = await daRequest('/CMD_API_SHOW_DOMAINS').catch(() => null);
    if (daResp && daResp.list) {
      const list = Object.values(daResp).filter(v => v && typeof v === 'string' && !v.includes('='));
      return res.json({ source: 'da', domains: list, whmcs: rows });
    }

    // Fallback: read from filesystem
    const fs = require('fs');
    const domainsDir = '/home/gositeme/domains';
    let doms = [];
    try {
      doms = fs.readdirSync(domainsDir).filter(f => {
        try { return fs.statSync(`${domainsDir}/${f}`).isDirectory(); } catch { return false; }
      });
    } catch (e) { /* ignore */ }

    res.json({ source: 'mixed', domains: doms, whmcs: rows });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// ── Email ──────────────────────────────────────────────────────────────────
app.get('/api/email/accounts', requireAuth, async (req, res) => {
  try {
    const requestedDomain = sanitizeDomain(req.query.domain);
    const domain = requestedDomain || (isPrivilegedSession(req) ? 'gositeme.com' : '');
    if (!domain) return res.status(400).json({ error: 'domain is required' });
    if (!(await canAccessDomain(req, domain))) {
      return res.status(403).json({ error: 'Domain access denied' });
    }

    const fs = require('fs');
    const accounts = [];
    // Read from Maildir structure
    const maildirBase = `/home/gositeme/Maildir/${domain}`;
    try {
      const users = fs.readdirSync(maildirBase, { withFileTypes: true });
      users.filter(u => u.isDirectory()).forEach(u => accounts.push(`${u.name}@${domain}`));
    } catch { /* no Maildir for domain */ }
    // Also check virtual mailbox map
    try {
      const vmap = fs.readFileSync('/etc/postfix/virtual', 'utf8');
      vmap.split('\n')
        .filter(l => l.includes('@' + domain))
        .map(l => l.split(/\s+/)[0])
        .filter(a => a && !accounts.includes(a))
        .forEach(a => accounts.push(a));
    } catch { /* ignore */ }
    // Also check imap dir structure
    try {
      const imapBase = `/home/gositeme/imap/${domain}`;
      const users = fs.readdirSync(imapBase, { withFileTypes: true });
      users.filter(u => u.isDirectory()).forEach(u => {
        const addr = `${u.name}@${domain}`;
        if (!accounts.includes(addr)) accounts.push(addr);
      });
    } catch { /* ignore */ }
    res.json({
      source: 'bridge',
      domain,
      accounts: accounts.map(address => {
        const [user, domainName] = String(address || '').split('@');
        return { address, user: user || '', domain: domainName || domain };
      })
    });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// ── Databases ──────────────────────────────────────────────────────────────
app.get('/api/databases', requireAuth, requireAdmin, async (req, res) => {
  try {
    const [rows] = await db.query("SHOW DATABASES");
    const dbs = rows
      .map(r => r.Database)
      .filter(d => !['information_schema','performance_schema','mysql','sys'].includes(d));
    res.json({ databases: dbs.map(name => ({ name })) });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// ── DNS ────────────────────────────────────────────────────────────────────
app.get('/api/dns/:domain', requireAuth, async (req, res) => {
  try {
    const domain = sanitizeDomain(req.params.domain);
    if (!(await canAccessDomain(req, domain))) {
      return res.status(403).json({ error: 'Domain access denied' });
    }

    // Read via bridge (GREEN — no token needed)
    try {
      const output = await bridge('dns-zone-read', [domain]);
      // Parse zone file into records
      const records = [];
      const lines = output.split('\n').filter(l => l.trim() && !l.startsWith(';'));
      for (const line of lines) {
        const m = line.match(/^(\S+)\s+(\d+)\s+IN\s+(\S+)\s+(.+)$/);
        if (m) records.push({ name: m[1], ttl: parseInt(m[2]), type: m[3], value: m[4].replace(/"$/,'').replace(/^"/,'') });
      }
      return res.json({ source: 'bridge', domain, records, raw: output.slice(0, 4096) });
    } catch (e) {
      // Zone doesn't exist
      return res.json({ source: 'bridge', domain, records: [], error: e.message });
    }
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// ── File System (safe read-only browse) ───────────────────────────────────
app.get('/api/files', requireAuth, requireAdmin, async (req, res) => {
  const fs = require('fs');
  const requestedPath = req.query.path || '/home/gositeme';
  // Security: only allow within home and web root
  const allowed = ['/home/gositeme'];
  const safe = path.normalize(requestedPath);
  if (!allowed.some(a => safe.startsWith(a))) {
    return res.status(403).json({ error: 'Path not allowed' });
  }
  try {
    const entries = fs.readdirSync(safe, { withFileTypes: true });
    const items = entries.map(e => {
      const itemPath = path.join(safe, e.name);
      const stat = fs.statSync(itemPath);
      return {
        name: e.name,
        type: e.isDirectory() ? 'directory' : 'file',
        path: itemPath,
        size: stat.size,
        permissions: (stat.mode & 0o777).toString(8),
        modified: stat.mtime.toISOString()
      };
    });
    res.json({ path: safe, files: items });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// ── PM2 Services ──────────────────────────────────────────────────────────
app.get('/api/pm2/list', requireAuth, requireAdmin, async (req, res) => {
  const { exec } = require('child_process');
  exec('pm2 jlist 2>/dev/null', { timeout: 10000 }, (err, stdout) => {
    try {
      const list = JSON.parse(stdout || '[]');
      res.json({
        processes: list.map(p => ({
          pm_id: p.pm_id,
          name: p.name,
          pm2_env: {
            status: p.pm2_env?.status,
            pm_uptime: p.pm2_env?.pm_uptime,
            restart_time: p.pm2_env?.restart_time
          },
          monit: {
            cpu: p.monit?.cpu || 0,
            memory: p.monit?.memory || 0
          }
        }))
      });

      function handlePm2LogsRequest(req, res) {
        const name = String(req.body?.name || req.query?.name || '').trim();
        const requestedLines = req.body?.lines || req.query?.lines;
        if (!name || !/^[a-zA-Z0-9_-]+$/.test(name)) {
          return res.status(400).json({ error: 'Invalid process name' });
        }

        const numLines = Math.min(parseInt(requestedLines, 10) || 50, 500);
        const { exec } = require('child_process');
        exec(`pm2 logs ${name} --nostream --lines ${numLines} 2>&1`, { timeout: 10000 }, (err, stdout) => {
          res.json({ name, logs: (stdout || '').trim() });
        });
      }

      app.get('/api/pm2/logs', requireAuth, requireAdmin, async (req, res) => {
        handlePm2LogsRequest(req, res);
      });
    } catch (e) {
      res.json({ processes: [] });
    }
  });
});

// ── DA Integration Check ──────────────────────────────────────────────────
app.get('/api/da/status', requireAuth, requireAdmin, async (req, res) => {
  const configured = !!(DA_ADMIN && DA_PASS);
  if (!configured) return res.json({ configured: false, message: 'Set DA_ADMIN and DA_PASS in .env to enable DirectAdmin API integration' });
  try {
    const result = await daRequest('/CMD_API_SHOW_DOMAINS');
    res.json({ configured: true, connected: !result.error, data: result });
  } catch (err) {
    res.json({ configured: true, connected: false, error: err.message });
  }
});

app.post('/api/da/configure', requireAuth, requireAdmin, async (req, res) => {
  const admin = String(req.body.admin || '').trim();
  const pass = String(req.body.pass || req.body.password || '').trim();
  if (!admin || !pass) return res.status(400).json({ error: 'admin and pass required' });

  const fs = require('fs');
  const envPath = path.join(__dirname, '.env');
  let envContent = '';
  try { envContent = fs.readFileSync(envPath, 'utf8'); } catch { /* new file */ }

  // Update or add DA lines
  const lines = envContent.split('\n').filter(l => !l.startsWith('DA_ADMIN=') && !l.startsWith('DA_PASS='));
  lines.push(`DA_ADMIN=${admin}`, `DA_PASS=${pass}`);
  fs.writeFileSync(envPath, lines.join('\n') + '\n');

  // Update live vars
  process.env.DA_ADMIN = admin;
  process.env.DA_PASS = pass;

  res.json({ ok: true, message: 'DA credentials saved. Restart not required.' });
});

// ── Bridge Status ─────────────────────────────────────────────────────────
app.get('/api/bridge/status', requireAuth, requireAdmin, async (req, res) => {
  try {
    const output = await bridge('apache-test', []);
    res.json({ ok: true, bridge: 'operational', output: output.slice(0, 200) });
  } catch (err) {
    res.json({ ok: false, bridge: 'error', error: err.message });
  }
});

// ── DNS Zones List ────────────────────────────────────────────────────────
app.get('/api/dns/zones/list', requireAuth, requireAdmin, async (req, res) => {
  const fs = require('fs');
  try {
    const files = fs.readdirSync('/etc/bind').filter(f => f.endsWith('.db'));
    const zones = files.map(f => f.replace('.db', ''));
    res.json({ zones });
  } catch (err) {
    res.json({ zones: [] });
  }
});

// ── SSL Info ──────────────────────────────────────────────────────────────
app.get('/api/ssl/:domain', requireAuth, async (req, res) => {
  const domain = sanitizeDomain(req.params.domain);
  if (!(await canAccessDomain(req, domain))) {
    return res.status(403).json({ error: 'Domain access denied' });
  }

  const fs = require('fs');
  const paths = [
    `/home/gositeme/domains/${domain}/ssl/${domain}.crt`,
    `/home/gositeme/domains/${domain}/ssl/cert.pem`,
    `/home/gositeme/.lego/certificates/${domain}.crt`,
    `/etc/letsencrypt/live/${domain}/cert.pem`
  ];
  for (const p of paths) {
    try {
      const cert = fs.readFileSync(p, 'utf8');
      // crude expiry parse
      const child = require('child_process').spawnSync('openssl', ['x509', '-noout', '-enddate'], {
        input: cert, encoding: 'utf8', timeout: 5000
      });
      const issuerChild = require('child_process').spawnSync('openssl', ['x509', '-noout', '-issuer'], {
        input: cert, encoding: 'utf8', timeout: 5000
      });
      const expiry = (child.stdout?.trim() || 'unknown').replace('notAfter=', '');
      const issuer = (issuerChild.stdout?.trim() || '').replace(/^issuer=/, '').trim() || 'Unknown';
      return res.json({ domain, path: p, expiry, expires: expiry, issuer, valid: true });
    } catch { /* try next */ }
  }
  res.json({ domain, expiry: null, expires: null, issuer: 'None', valid: false, message: 'SSL cert not found' });
});

// ═══════════════════════════════════════════════════════════════════════════
// ── WRITE OPERATIONS ── (Phase out DirectAdmin 2222)
// ═══════════════════════════════════════════════════════════════════════════

// ── Admin guard ───────────────────────────────────────────────────────────
function requireAdmin(req, res, next) {
  if (!isPrivilegedSession(req)) {
    return res.status(403).json({ error: 'Admin access required' });
  }
  next();
}

// ── Security: path validator ──────────────────────────────────────────────
function safePath(requestedPath) {
  const resolved = path.resolve(requestedPath);
  const allowed = ['/home/gositeme'];
  if (!allowed.some(a => resolved.startsWith(a))) return null;
  // Block vault, .ssh, .my.cnf, etc.
  const blocked = ['.vault', '.ssh', '.my.cnf', '.bash_history', '.vault-master-key'];
  if (blocked.some(b => resolved.includes(b))) return null;
  return resolved;
}

// ═════════════════════════════════════════════════════════════════════════
// PM2 MANAGEMENT — start / stop / restart / delete
// ═════════════════════════════════════════════════════════════════════════

app.post('/api/pm2/action', requireAuth, requireAdmin, async (req, res) => {
  const { action, name } = req.body;
  const validActions = ['start', 'stop', 'restart', 'reload'];
  if (!validActions.includes(action)) return res.status(400).json({ error: `Invalid action. Use: ${validActions.join(', ')}` });
  if (!name || !/^[a-zA-Z0-9_-]+$/.test(name)) return res.status(400).json({ error: 'Invalid process name' });
  const { exec } = require('child_process');
  exec(`pm2 ${action} ${name} 2>&1`, { timeout: 15000 }, (err, stdout, stderr) => {
    res.json({ ok: !err, action, name, output: (stdout || stderr || '').trim() });
  });
});

app.post('/api/pm2/logs', requireAuth, requireAdmin, async (req, res) => {
  handlePm2LogsRequest(req, res);
});

// ═════════════════════════════════════════════════════════════════════════
// FILE MANAGEMENT — write / mkdir / delete / rename / chmod / upload
// ═════════════════════════════════════════════════════════════════════════

app.post('/api/files/write', requireAuth, requireAdmin, async (req, res) => {
  const fp = req.body.filePath || req.body.path;
  const { content } = req.body;
  if (!fp || typeof content !== 'string') return res.status(400).json({ error: 'filePath and content required' });
  const safe = safePath(fp);
  if (!safe) return res.status(403).json({ error: 'Path not allowed' });
  if (Buffer.byteLength(content) > 10 * 1024 * 1024) return res.status(400).json({ error: 'Content exceeds 10 MB' });
  const fs = require('fs');
  try {
    fs.mkdirSync(path.dirname(safe), { recursive: true });
    fs.writeFileSync(safe, content, 'utf8');
    res.json({ ok: true, path: safe, bytes: Buffer.byteLength(content) });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.get('/api/files/read', requireAuth, requireAdmin, async (req, res) => {
  const fp = req.query.path;
  if (!fp) return res.status(400).json({ error: 'path required' });
  const safe = safePath(fp);
  if (!safe) return res.status(403).json({ error: 'Path not allowed' });
  const fs = require('fs');
  try {
    const stat = fs.statSync(safe);
    if (stat.size > 5 * 1024 * 1024) return res.status(400).json({ error: 'File too large (max 5 MB)' });
    const content = fs.readFileSync(safe, 'utf8');
    res.json({ path: safe, content, bytes: stat.size });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.post('/api/files/mkdir', requireAuth, requireAdmin, async (req, res) => {
  const { dirPath } = req.body;
  if (!dirPath) return res.status(400).json({ error: 'dirPath required' });
  const safe = safePath(dirPath);
  if (!safe) return res.status(403).json({ error: 'Path not allowed' });
  const fs = require('fs');
  try {
    fs.mkdirSync(safe, { recursive: true });
    res.json({ ok: true, path: safe });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.delete('/api/files', requireAuth, requireAdmin, async (req, res) => {
  const fp = req.query.path;
  if (!fp) return res.status(400).json({ error: 'path required' });
  const safe = safePath(fp);
  if (!safe) return res.status(403).json({ error: 'Path not allowed' });
  const fs = require('fs');
  try {
    const stat = fs.statSync(safe);
    if (stat.isDirectory()) {
      fs.rmSync(safe, { recursive: true, force: true });
    } else {
      fs.unlinkSync(safe);
    }
    res.json({ ok: true, deleted: safe });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.post('/api/files/rename', requireAuth, requireAdmin, async (req, res) => {
  const { from, to } = req.body;
  if (!from || !to) return res.status(400).json({ error: 'from and to required' });
  const safeFrom = safePath(from);
  const safeTo = safePath(to);
  if (!safeFrom || !safeTo) return res.status(403).json({ error: 'Path not allowed' });
  const fs = require('fs');
  try {
    fs.renameSync(safeFrom, safeTo);
    res.json({ ok: true, from: safeFrom, to: safeTo });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.post('/api/files/chmod', requireAuth, requireAdmin, async (req, res) => {
  const { filePath: fp, mode } = req.body;
  if (!fp || !mode) return res.status(400).json({ error: 'filePath and mode required' });
  const safe = safePath(fp);
  if (!safe) return res.status(403).json({ error: 'Path not allowed' });
  if (!/^[0-7]{3,4}$/.test(mode)) return res.status(400).json({ error: 'Invalid mode (use octal like 755)' });
  const fs = require('fs');
  try {
    fs.chmodSync(safe, parseInt(mode, 8));
    res.json({ ok: true, path: safe, mode });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// ═════════════════════════════════════════════════════════════════════════
// DATABASE MANAGEMENT — create / drop / users
// ═════════════════════════════════════════════════════════════════════════

app.post('/api/databases/create', requireAuth, requireAdmin, async (req, res) => {
  const { name } = req.body;
  if (!name) return res.status(400).json({ error: 'name required' });
  // Must be prefixed with gositeme_ and contain only safe chars
  const dbName = name.startsWith('gositeme_') ? name : `gositeme_${name}`;
  if (!/^gositeme_[a-zA-Z0-9_]{1,50}$/.test(dbName)) {
    return res.status(400).json({ error: 'Invalid DB name (alphanumeric + underscore, max 50 chars, auto-prefixed with gositeme_)' });
  }
  try {
    // Using backtick escaping for identifiers (can't use parameterized for DDL)
    const safeName = dbName.replace(/[^a-zA-Z0-9_]/g, '');
    await db.query(`CREATE DATABASE IF NOT EXISTS \`${safeName}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci`);
    res.json({ ok: true, database: safeName });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.delete('/api/databases/:name', requireAuth, requireAdmin, async (req, res) => {
  const dbName = req.params.name;
  if (!/^gositeme_[a-zA-Z0-9_]{1,50}$/.test(dbName)) {
    return res.status(400).json({ error: 'Can only drop databases with gositeme_ prefix' });
  }
  // Protect critical databases
  const critical = ['gositeme_whmcs'];
  if (critical.includes(dbName)) return res.status(403).json({ error: 'Cannot drop critical database' });
  try {
    const safeName = dbName.replace(/[^a-zA-Z0-9_]/g, '');
    await db.query(`DROP DATABASE IF EXISTS \`${safeName}\``);
    res.json({ ok: true, dropped: safeName });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.get('/api/databases/:name/tables', requireAuth, requireAdmin, async (req, res) => {
  const dbName = req.params.name;
  if (!/^[a-zA-Z0-9_]{1,64}$/.test(dbName)) return res.status(400).json({ error: 'Invalid database name' });
  try {
    const safeName = dbName.replace(/[^a-zA-Z0-9_]/g, '');
    const [rows] = await db.query(`SHOW TABLES FROM \`${safeName}\``);
    const tables = rows.map(r => Object.values(r)[0]);
    res.json({ database: safeName, tables });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.post('/api/databases/query', requireAuth, requireAdmin, async (req, res) => {
  const { database, sql } = req.body;
  if (!sql) return res.status(400).json({ error: 'sql required' });
  // Only allow SELECT for safety
  const trimmed = sql.trim().toUpperCase();
  if (!trimmed.startsWith('SELECT') && !trimmed.startsWith('SHOW') && !trimmed.startsWith('DESCRIBE') && !trimmed.startsWith('EXPLAIN')) {
    return res.status(403).json({ error: 'Only SELECT/SHOW/DESCRIBE/EXPLAIN queries allowed via API' });
  }
  try {
    let conn = db;
    if (database && /^[a-zA-Z0-9_]{1,64}$/.test(database)) {
      const safeName = database.replace(/[^a-zA-Z0-9_]/g, '');
      conn = mysql.createPool({
        host: '127.0.0.1',
        user: process.env.DB_USER || 'gositeme_whmcs',
        password: process.env.DB_PASS || '!q@w#e$r5t',
        database: safeName,
        connectionLimit: 2
      });
    }
    const [rows] = await conn.query(sql);
    const limited = Array.isArray(rows) ? rows.slice(0, 1000) : rows;
    res.json({ rows: limited, count: Array.isArray(rows) ? rows.length : 0 });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// ═════════════════════════════════════════════════════════════════════════
// EMAIL MANAGEMENT — create / delete / update password / forwarders
// ═════════════════════════════════════════════════════════════════════════

app.post('/api/email/create', requireAuth, requireAdmin, async (req, res) => {
  const { user, domain, password } = req.body;
  if (!user || !domain || !password) return res.status(400).json({ error: 'user, domain, password required' });
  if (!/^[a-zA-Z0-9._-]+$/.test(user)) return res.status(400).json({ error: 'Invalid username' });
  if (!/^[a-zA-Z0-9.-]+$/.test(domain)) return res.status(400).json({ error: 'Invalid domain' });

  try {
    // YELLOW tier — email-create via bridge
    const email = `${user}@${domain}`;
    const output = await bridge('email-create', [email, password]);
    return res.json({ ok: true, source: 'bridge', account: email, output });
  } catch (err) {
    return res.status(500).json({ error: err.message });
  }
});

app.delete('/api/email/account', requireAuth, requireAdmin, async (req, res) => {
  const { user, domain } = req.query;
  if (!user || !domain) return res.status(400).json({ error: 'user and domain required' });
  if (!/^[a-zA-Z0-9._-]+$/.test(user) || !/^[a-zA-Z0-9.-]+$/.test(domain)) {
    return res.status(400).json({ error: 'Invalid user or domain' });
  }
  try {
    const email = `${user}@${domain}`;
    const output = await bridge('email-delete', [email]);
    return res.json({ ok: true, source: 'bridge', deleted: email, output });
  } catch (err) {
    return res.status(500).json({ error: err.message });
  }
});

// ═════════════════════════════════════════════════════════════════════════
// DNS MANAGEMENT — add / edit / delete records (via DA API bridge)
// ═════════════════════════════════════════════════════════════════════════

app.post('/api/dns/:domain/record', requireAuth, requireAdmin, async (req, res) => {
  const domain = req.params.domain.replace(/[^a-zA-Z0-9.-]/g, '');
  const { type, name, value, ttl } = req.body;
  if (!type || !name || !value) return res.status(400).json({ error: 'type, name, value required' });
  const validTypes = ['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'SRV', 'NS', 'CAA'];
  if (!validTypes.includes(type.toUpperCase())) return res.status(400).json({ error: `Invalid type. Use: ${validTypes.join(', ')}` });

  try {
    // RED tier — requires approval for dns-record-add
    const output = await bridge('dns-record-add', [domain, name, type.toUpperCase(), value, String(ttl || 3600)], { approval: req.body.approval });
    // Reload DNS after adding record
    await bridge('dns-reload', []).catch(() => {});
    res.json({ ok: true, source: 'bridge', domain, record: { type, name, value }, output });
  } catch (err) {
    res.status(500).json({ ok: false, error: err.message });
  }
});

app.delete('/api/dns/:domain/record', requireAuth, requireAdmin, async (req, res) => {
  const domain = req.params.domain.replace(/[^a-zA-Z0-9.-]/g, '');
  // Accept from body (JSON DELETE) or query params
  const { type, name, value, approval } = { ...req.query, ...req.body };
  if (!type || !name) return res.status(400).json({ error: 'type and name required' });
  try {
    const args = [domain, name, type.toUpperCase()];
    if (value) args.push(value);
    const output = await bridge('dns-record-delete', args, { approval });
    await bridge('dns-reload', []).catch(() => {});
    res.json({ ok: true, source: 'bridge', deleted: { type, name, value }, output });
  } catch (err) {
    res.status(500).json({ ok: false, error: err.message });
  }
});

// ═════════════════════════════════════════════════════════════════════════
// DOMAIN MANAGEMENT — create / delete (via DA API bridge)
// ═════════════════════════════════════════════════════════════════════════

app.post('/api/domains/create', requireAuth, requireAdmin, async (req, res) => {
  const { domain, php, ssl, approval } = req.body;
  if (!domain) return res.status(400).json({ error: 'domain required' });
  if (!/^[a-zA-Z0-9][a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(domain)) {
    return res.status(400).json({ error: 'Invalid domain name' });
  }
  try {
    // Create directory structure
    const fs = require('fs');
    const domPath = `/home/gositeme/domains/${domain}`;
    for (const d of ['', '/public_html', '/logs', '/ssl']) {
      fs.mkdirSync(`${domPath}${d}`, { recursive: true });
    }
    fs.writeFileSync(`${domPath}/public_html/index.html`,
      `<!DOCTYPE html><html><head><title>${domain}</title></head><body><h1>${domain}</h1><p>Domain configured via GoHostMe.</p></body></html>`
    );

    // Create Apache vhost via bridge (RED tier)
    const vhostOutput = await bridge('vhost-create', [domain], { approval }).catch(e => e.message);

    // Create DNS zone via bridge (RED tier)
    const dnsOutput = await bridge('dns-zone-create', [domain], { approval }).catch(e => e.message);

    // Reload Apache
    await bridge('apache-reload', []).catch(() => {});

    return res.json({ ok: true, source: 'bridge', domain, path: domPath, vhost: vhostOutput, dns: dnsOutput });
  } catch (err) {
    return res.status(500).json({ error: err.message });
  }
});

app.delete('/api/domains/:domain', requireAuth, requireAdmin, async (req, res) => {
  const domain = req.params.domain.replace(/[^a-zA-Z0-9.-]/g, '');
  if (!domain) return res.status(400).json({ error: 'Invalid domain' });
  const critical = ['gositeme.com', 'gocodeme.com', 'meta-dome.com', 'soundstudiopro.com'];
  if (critical.includes(domain)) return res.status(403).json({ error: 'Cannot delete critical domain' });
  try {
    const vhostOutput = await bridge('vhost-delete', [domain], { approval: req.query.approval }).catch(e => e.message);
    await bridge('apache-reload', []).catch(() => {});
    return res.json({ ok: true, source: 'bridge', deleted: domain, output: vhostOutput });
  } catch (err) {
    return res.status(500).json({ error: err.message });
  }
});

// ═════════════════════════════════════════════════════════════════════════
// SSL MANAGEMENT — request Let's Encrypt / install custom cert
// ═════════════════════════════════════════════════════════════════════════

app.post('/api/ssl/request', requireAuth, requireAdmin, async (req, res) => {
  const { domain } = req.body;
  if (!domain) return res.status(400).json({ error: 'domain required' });
  if (!/^[a-zA-Z0-9.-]+$/.test(domain)) return res.status(400).json({ error: 'Invalid domain' });

  try {
    // YELLOW tier — certbot-request via bridge (uses lego ACME client)
    const output = await bridge('certbot-request', [domain], { timeout: 120000 });
    // Reload Apache to pick up new cert
    await bridge('apache-reload', []).catch(() => {});
    res.json({ ok: true, source: 'bridge', domain, output });
  } catch (err) {
    res.status(500).json({ ok: false, error: err.message });
  }
});

// ═════════════════════════════════════════════════════════════════════════
// CRON JOB MANAGEMENT — list / add / delete
// ═════════════════════════════════════════════════════════════════════════

app.get('/api/cron', requireAuth, requireAdmin, async (req, res) => {
  const { exec } = require('child_process');
  exec('crontab -l 2>/dev/null', (err, stdout) => {
    const lines = (stdout || '').trim().split('\n').filter(l => l && !l.startsWith('#'));
    const jobs = lines.map(l => {
      const parts = l.match(/^(\S+\s+\S+\s+\S+\s+\S+\s+\S+)\s+(.*)$/);
      return parts ? { schedule: parts[1], command: parts[2] } : { schedule: '', command: l };
    });
    res.json({ jobs });
  });
});

app.post('/api/cron', requireAuth, requireAdmin, async (req, res) => {
  const { schedule, command } = req.body;
  if (!schedule || !command) return res.status(400).json({ error: 'schedule and command required' });
  if (!/^[\d*\/,\s-]+$/.test(schedule)) return res.status(400).json({ error: 'Invalid cron schedule' });
  // Block dangerous commands
  const blocked = ['rm -rf /', 'mkfs', 'dd if=', ':(){', 'chmod -R 777 /'];
  if (blocked.some(b => command.includes(b))) return res.status(403).json({ error: 'Blocked command' });

  const { exec } = require('child_process');
  const entry = `${schedule} ${command}`;
  exec(`(crontab -l 2>/dev/null; echo ${JSON.stringify(entry)}) | crontab -`, { timeout: 5000 }, (err, stdout, stderr) => {
    res.json({ ok: !err, added: entry, output: (stderr || stdout || '').trim() });
  });
});

app.delete('/api/cron/:index', requireAuth, requireAdmin, async (req, res) => {
  const idx = parseInt(req.params.index);
  if (isNaN(idx) || idx < 0) return res.status(400).json({ error: 'Valid index required' });
  const { exec } = require('child_process');
  exec('crontab -l 2>/dev/null', (err, stdout) => {
    const lines = (stdout || '').trim().split('\n');
    let jobIdx = -1;
    const filtered = lines.filter(l => {
      if (!l || l.startsWith('#')) return true;
      jobIdx++;
      return jobIdx !== idx;
    });
    exec(`echo ${JSON.stringify(filtered.join('\n'))} | crontab -`, { timeout: 5000 }, (err2) => {
      res.json({ ok: !err2, removedIndex: idx });
    });
  });
});

// ═════════════════════════════════════════════════════════════════════════
// BACKUP MANAGEMENT — create snapshot / list / restore
// ═════════════════════════════════════════════════════════════════════════

app.get('/api/backups', requireAuth, requireAdmin, async (req, res) => {
  const fs = require('fs');
  const backupDir = '/home/gositeme/backups';
  try {
    const entries = fs.readdirSync(backupDir, { withFileTypes: true });
    const items = entries.map(e => {
      const fp = path.join(backupDir, e.name);
      const stat = fs.statSync(fp);
      return { name: e.name, type: e.isDirectory() ? 'dir' : 'file', size: stat.size, modified: stat.mtime };
    }).sort((a, b) => new Date(b.modified) - new Date(a.modified));
    res.json({ path: backupDir, backups: items });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.post('/api/backups/create', requireAuth, requireAdmin, async (req, res) => {
  const { domain, type } = req.body;
  const { exec } = require('child_process');
  const ts = new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19);

  if (type === 'database') {
    const dbName = (domain || 'gositeme_whmcs').replace(/[^a-zA-Z0-9_]/g, '');
    const file = `/home/gositeme/backups/${dbName}-${ts}.sql.gz`;
    exec(`mysqldump -S /run/mysql/mysql.sock ${dbName} 2>/dev/null | gzip > ${JSON.stringify(file)}`, { timeout: 300000 }, (err) => {
      res.json({ ok: !err, type: 'database', file });
    });
  } else {
    const domName = (domain || 'gositeme.com').replace(/[^a-zA-Z0-9.-]/g, '');
    const srcDir = `/home/gositeme/domains/${domName}`;
    const file = `/home/gositeme/backups/${domName}-${ts}.tar.gz`;
    exec(`tar czf ${JSON.stringify(file)} -C /home/gositeme/domains ${JSON.stringify(domName)} 2>&1`, { timeout: 600000 }, (err, stdout) => {
      res.json({ ok: !err, type: 'domain', file, output: (stdout || '').trim().slice(-200) });
    });
  }
});

// ═════════════════════════════════════════════════════════════════════════
// APACHE MANAGEMENT — list vhosts / reload (info only as gositeme user)
// ═════════════════════════════════════════════════════════════════════════

app.get('/api/apache/vhosts', requireAuth, requireAdmin, async (req, res) => {
  const { exec } = require('child_process');
  exec('apache2ctl -S 2>&1 || httpd -S 2>&1', { timeout: 5000 }, (err, stdout) => {
    const output = (stdout || 'Cannot read vhosts (no sudo)').trim();
    const vhosts = output.split('\n').map(line => {
      const match = line.match(/namevhost\s+(\S+)\s+\(([^:]+):(\d+)\)/i);
      if (!match) return null;
      return {
        domain: match[1],
        config: match[2],
        port: parseInt(match[3], 10) || 80,
        ssl: match[3] === '443'
      };
    }).filter(Boolean);
    res.json({ output, vhosts });
  });
});

// ═════════════════════════════════════════════════════════════════════════
// PANEL OVERVIEW — /api/panel/stats (aggregate dashboard data)
// ═════════════════════════════════════════════════════════════════════════

app.get('/api/panel/stats', requireAuth, async (req, res) => {
  const fs = require('fs');
  const { exec } = require('child_process');

  try {
    if (!isPrivilegedSession(req)) {
      const clientId = getSessionClientId(req);
      const [[{ domains = 0 }]] = await db.query(
        "SELECT COUNT(*) as domains FROM tbldomains WHERE userid = ?",
        [clientId]
      ).catch(() => [[{ domains: 0 }]]);
      const [[{ services = 0 }]] = await db.query(
        "SELECT COUNT(*) as services FROM tblhosting WHERE userid = ? AND domainstatus = 'Active'",
        [clientId]
      ).catch(() => [[{ services: 0 }]]);

      return res.json({
        scope: 'client',
        domains,
        databases: null,
        pm2: null,
        whmcs: { clients: 1, services },
        endpoints: 0,
        version: '4.0.0'
      });
    }

    // Count domains
    let domainCount = 0;
    try { domainCount = fs.readdirSync('/home/gositeme/domains', { withFileTypes: true }).filter(d => d.isDirectory()).length; } catch {}

    // Count databases
    const [dbRows] = await db.query("SHOW DATABASES").catch(() => [[]]);
    const dbCount = dbRows.filter(r => !['information_schema','performance_schema','mysql','sys'].includes(r.Database)).length;

    // PM2 count
    let pm2Online = 0, pm2Total = 0;
    try {
      const pm2Data = await new Promise((resolve) => {
        exec('pm2 jlist 2>/dev/null', { timeout: 5000 }, (e, s) => {
          try { resolve(JSON.parse(s || '[]')); } catch { resolve([]); }
        });
      });
      pm2Total = pm2Data.length;
      pm2Online = pm2Data.filter(p => p.pm2_env?.status === 'online').length;
    } catch {}

    // WHMCS stats
    const [[{ clients = 0 }]] = await db.query("SELECT COUNT(*) as clients FROM clients WHERE status = 'Active'").catch(() => [[{ clients: 0 }]]);
    const [[{ services = 0 }]] = await db.query("SELECT COUNT(*) as services FROM tblhosting WHERE domainstatus = 'Active'").catch(() => [[{ services: 0 }]]);

    res.json({
      scope: 'admin',
      domains: domainCount,
      databases: dbCount,
      pm2: { online: pm2Online, total: pm2Total },
      whmcs: { clients, services },
      endpoints: 46, // our total route count now
      version: '4.0.0'
    });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// ═══════════════════════════════════════════════════════════════════════════
// IDE INSTANCE MANAGEMENT (Phase 4 — Alfred IDE per-customer provisioning)
// ═══════════════════════════════════════════════════════════════════════════

const { exec } = require('child_process');
const execAsync = (cmd, opts = {}) => new Promise((resolve, reject) => {
  exec(cmd, { timeout: 30000, ...opts }, (err, stdout, stderr) => {
    if (err) reject(err);
    else resolve({ stdout: stdout.trim(), stderr: stderr.trim() });
  });
});

// List all IDE instances (PM2 processes matching ide-* pattern)
app.get('/api/ide/instances', requireAuth, requireAdmin, async (req, res) => {
  try {
    const { stdout } = await execAsync('pm2 jlist 2>/dev/null');
    const procs = JSON.parse(stdout || '[]');
    const ideInstances = procs
      .filter(p => p.name.startsWith('ide-') || p.name === 'alfred-ide')
      .map(p => ({
        name: p.name,
        status: p.pm2_env?.status || 'unknown',
        port: p.pm2_env?.env?.CS_PORT || (p.name === 'alfred-ide' ? 8443 : null),
        uptime: p.pm2_env?.pm_uptime || 0,
        restarts: p.pm2_env?.restart_time || 0,
        memory: p.monit?.memory || 0,
        cpu: p.monit?.cpu || 0,
        user: p.pm2_env?.env?.CS_USER || 'commander'
      }));
    res.json({ instances: ideInstances, total: ideInstances.length });
  } catch (err) {
    res.status(500).json({ error: 'Failed to list IDE instances: ' + err.message });
  }
});

// Get a specific IDE instance status
app.get('/api/ide/instance/:name', requireAuth, requireAdmin, async (req, res) => {
  try {
    const name = req.params.name.replace(/[^a-zA-Z0-9_-]/g, '');
    const { stdout } = await execAsync(`pm2 jlist 2>/dev/null`);
    const procs = JSON.parse(stdout || '[]');
    const proc = procs.find(p => p.name === name);
    if (!proc) return res.status(404).json({ error: 'Instance not found' });
    res.json({
      name: proc.name,
      status: proc.pm2_env?.status || 'unknown',
      port: proc.pm2_env?.env?.CS_PORT || null,
      uptime: proc.pm2_env?.pm_uptime || 0,
      restarts: proc.pm2_env?.restart_time || 0,
      memory: proc.monit?.memory || 0,
      cpu: proc.monit?.cpu || 0,
      pid: proc.pid,
      user: proc.pm2_env?.env?.CS_USER || 'unknown'
    });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// Provision a new IDE instance for a customer
app.post('/api/ide/provision', requireAuth, requireAdmin, async (req, res) => {
  try {
    const { username, port, password, workspace } = req.body;
    if (!username || !port) return res.status(400).json({ error: 'username and port are required' });

    // Validate port range (9000-9999 reserved for customer IDE instances)
    const portNum = parseInt(port);
    if (isNaN(portNum) || portNum < 9000 || portNum > 9999) {
      return res.status(400).json({ error: 'Port must be 9000-9999 (reserved for customer IDEs)' });
    }

    // Sanitize
    const safeName = 'ide-' + username.replace(/[^a-zA-Z0-9_-]/g, '').substring(0, 30);
    const safePassword = password || crypto.randomBytes(16).toString('hex');
    const workDir = workspace || `/home/gositeme/domains/${username}/public_html`;

    // Check if instance already exists
    const { stdout: existCheck } = await execAsync('pm2 jlist 2>/dev/null');
    const existProcs = JSON.parse(existCheck || '[]');
    if (existProcs.some(p => p.name === safeName)) {
      return res.status(409).json({ error: 'Instance already exists: ' + safeName });
    }

    // Check port not in use
    try {
      await execAsync(`lsof -i :${portNum} -t 2>/dev/null`);
      return res.status(409).json({ error: `Port ${portNum} is already in use` });
    } catch { /* port is free */ }

    // Create workspace directory if needed
    await execAsync(`mkdir -p "${workDir}"`).catch(() => {});

    // Create launch script for this instance
    const scriptPath = `/home/gositeme/gohostme/ide-scripts/${safeName}.sh`;
    await execAsync(`mkdir -p /home/gositeme/gohostme/ide-scripts`);

    const fs = require('fs');
    const scriptContent = `#!/bin/bash
# Alfred IDE Instance: ${safeName}
# Customer: ${username}
# Port: ${portNum}
unset VSCODE_IPC_HOOK_CLI
export CS_PORT=${portNum}
export CS_USER=${username}
exec /home/gositeme/.local/bin/code-server \\
  --bind-addr 127.0.0.1:${portNum} \\
  --auth password \\
  --disable-telemetry \\
  --disable-update-check \\
  --app-name "Alfred IDE" \\
  --welcome-text "Welcome to Alfred IDE" \\
  "${workDir}"
`;
    fs.writeFileSync(scriptPath, scriptContent, { mode: 0o755 });

    // Set the password in code-server per-instance config
    const csConfigDir = `/home/gositeme/.config/code-server-instances/${safeName}`;
    await execAsync(`mkdir -p "${csConfigDir}"`);
    fs.writeFileSync(`${csConfigDir}/config.yaml`,
      `bind-addr: 127.0.0.1:${portNum}\nauth: password\npassword: ${safePassword}\ncert: false\napp-name: Alfred IDE\nwelcome-text: Welcome to Alfred IDE\ndisable-telemetry: true\n`
    );

    // Start via PM2
    await execAsync(`pm2 start "${scriptPath}" --name "${safeName}" --interpreter /bin/bash`);
    await execAsync('pm2 save');

    res.json({
      success: true,
      instance: safeName,
      port: portNum,
      password: safePassword,
      url: `https://gositeme.com/ide/${safeName}/`,
      workspace: workDir,
      message: `IDE instance ${safeName} provisioned on port ${portNum}`
    });
  } catch (err) {
    res.status(500).json({ error: 'Failed to provision IDE: ' + err.message });
  }
});

// Stop/Start/Restart an IDE instance
app.post('/api/ide/control', requireAuth, requireAdmin, async (req, res) => {
  try {
    const instance = req.body.instance || req.body.name;
    const { action } = req.body;
    if (!instance || !action) return res.status(400).json({ error: 'instance and action required' });

    const safeName = instance.replace(/[^a-zA-Z0-9_-]/g, '');
    if (safeName === 'alfred-ide') return res.status(403).json({ error: 'Cannot control Commander IDE via API' });

    const validActions = ['start', 'stop', 'restart'];
    if (!validActions.includes(action)) return res.status(400).json({ error: 'Action must be: start, stop, or restart' });

    await execAsync(`pm2 ${action} "${safeName}"`);
    res.json({ success: true, instance: safeName, action, message: `${safeName} ${action}ed successfully` });
  } catch (err) {
    res.status(500).json({ error: `Failed to ${req.body.action} instance: ${err.message}` });
  }
});

// Delete/deprovision an IDE instance
app.delete('/api/ide/instance/:name', requireAuth, requireAdmin, async (req, res) => {
  try {
    const safeName = req.params.name.replace(/[^a-zA-Z0-9_-]/g, '');
    if (safeName === 'alfred-ide') return res.status(403).json({ error: 'Cannot delete Commander IDE' });
    if (!safeName.startsWith('ide-')) return res.status(403).json({ error: 'Can only delete ide-* instances' });

    // Stop and delete from PM2
    await execAsync(`pm2 stop "${safeName}" 2>/dev/null`).catch(() => {});
    await execAsync(`pm2 delete "${safeName}" 2>/dev/null`).catch(() => {});
    await execAsync('pm2 save');

    // Clean up script
    const fs = require('fs');
    const scriptPath = `/home/gositeme/gohostme/ide-scripts/${safeName}.sh`;
    if (fs.existsSync(scriptPath)) fs.unlinkSync(scriptPath);

    // Clean up config
    const configDir = `/home/gositeme/.config/code-server-instances/${safeName}`;
    await execAsync(`rm -rf "${configDir}"`).catch(() => {});

    res.json({ success: true, message: `IDE instance ${safeName} deprovisioned` });
  } catch (err) {
    res.status(500).json({ error: 'Failed to deprovision: ' + err.message });
  }
});

// IDE usage stats (aggregate)
app.get('/api/ide/stats', requireAuth, requireAdmin, async (req, res) => {
  try {
    const { stdout } = await execAsync('pm2 jlist 2>/dev/null');
    const procs = JSON.parse(stdout || '[]');
    const ideProcs = procs.filter(p => p.name.startsWith('ide-') || p.name === 'alfred-ide');
    const online = ideProcs.filter(p => p.pm2_env?.status === 'online');
    const totalMem = online.reduce((sum, p) => sum + (p.monit?.memory || 0), 0);

    // WHMCS IDE product stats
    let activeIdeServices = 0;
    try {
      const [[{ cnt }]] = await db.query(
        "SELECT COUNT(*) as cnt FROM tblhosting h JOIN tblproducts p ON h.packageid=p.id JOIN tblproductgroups pg ON p.gid=pg.id WHERE pg.name='AI Development Platform' AND h.domainstatus='Active'"
      );
      activeIdeServices = cnt;
    } catch {}

    res.json({
      instances: { total: ideProcs.length, online: online.length },
      memory: { total_bytes: totalMem, total_mb: Math.round(totalMem / 1048576) },
      whmcs_active: activeIdeServices,
      port_range: { min: 9000, max: 9999, available: 1000 - (ideProcs.length - 1) }
    });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});


// ── Security hardening & Fail2Ban (admin) — docs in public_html/scripts/security/ ──
app.get('/api/security/fail2ban', requireAuth, requireAdmin, async (req, res) => {
  try {
    const output = await bridge('fail2ban-status', [], { timeout: 60000 });
    res.json({ ok: true, output });
  } catch (err) {
    res.status(500).json({ ok: false, error: err.message });
  }
});

app.post('/api/security/apply-hardening', requireAuth, requireAdmin, async (req, res) => {
  try {
    const output = await bridge('apply-security-hardening', [], { timeout: 180000 });
    res.json({ ok: true, output });
  } catch (err) {
    res.status(500).json({ ok: false, error: err.message });
  }
});

app.post('/api/security/apply-optimization', requireAuth, requireAdmin, async (req, res) => {
  try {
    const output = await bridge('apply-stack-optimization', [], { timeout: 180000 });
    res.json({ ok: true, output });
  } catch (err) {
    res.status(500).json({ ok: false, error: err.message });
  }
});



// ── Read last lines of MySQL slow log (admin diagnostics only; path allowlist) ─
function readMysqlSlowLogTail(absPath, maxLines) {
  const maxL = Math.min(Math.max(parseInt(String(maxLines), 10) || 50, 1), 120);
  if (!absPath || typeof absPath !== 'string') return { ok: false, error: 'no path' };
  const t = absPath.trim();
  if (t.includes('..')) return { ok: false, error: 'invalid path' };
  let resolved;
  try {
    resolved = fs.realpathSync(t);
  } catch (e) {
    return { ok: false, error: String(e.message || e) };
  }
  const allowedRoots = ['/var/', '/home/gositeme/', '/usr/local/mysql/', '/tmp/'];
  if (!allowedRoots.some((p) => resolved.startsWith(p))) {
    return { ok: false, error: 'path not in allowlist', path: resolved };
  }
  try {
    const st = fs.statSync(resolved);
    if (!st.isFile()) return { ok: false, error: 'not a regular file', path: resolved };
    if (st.size > 80 * 1024 * 1024) return { ok: false, error: 'slow log file too large', size: st.size };
    const chunk = Math.min(st.size, 512 * 1024);
    const buf = Buffer.alloc(chunk);
    const fd = fs.openSync(resolved, 'r');
    fs.readSync(fd, buf, 0, chunk, Math.max(0, st.size - chunk));
    fs.closeSync(fd);
    const text = buf.toString('utf8');
    const lines = text.split(/\r?\n/);
    const tail = lines.slice(-maxL).join('\n');
    return { ok: true, path: resolved, size_bytes: st.size, lines_returned: Math.min(maxL, lines.length), tail };
  } catch (e) {
    const msg = String(e.message || e);
    const code = e && e.code;
    if (code === 'EACCES' || /EACCES|permission denied/i.test(msg)) {
      return {
        ok: false,
        error: 'permission denied reading slow log (file owned by mysqld)',
        path: resolved,
        hint: `sudo tail -n 80 ${resolved}`
      };
    }
    return { ok: false, error: msg };
  }
}

// ── Server diagnostics (admin) — load, disk, MySQL slow log vars ────────────
app.get('/api/security/diagnostics', requireAuth, requireAdmin, async (req, res) => {
  const { execFileSync } = require('child_process');
  const run = (cmd, args = []) => {
    try {
      return execFileSync(cmd, args, { encoding: 'utf8', maxBuffer: 2 * 1024 * 1024, timeout: 15000 }).trim();
    } catch (e) {
      return String(e.stderr || e.message || e);
    }
  };
  const disk = run('/bin/df', ['-h']);
  const memory = run('/usr/bin/free', ['-m']);
  const loadavg = run('/bin/cat', ['/proc/loadavg']);
  const uptime = run('/usr/bin/uptime', []);
  const cpuCoresRaw = run('/usr/bin/nproc', []);
  const cpu_cores = parseInt(cpuCoresRaw, 10) || null;
  const load1 = parseFloat(String(loadavg).trim().split(/\s+/)[0]) || 0;
  const load_per_core = cpu_cores ? Math.round((load1 / cpu_cores) * 1000) / 1000 : null;
  let load_hint = '';
  if (cpu_cores) {
    const r = load1 / cpu_cores;
    if (r < 0.7) load_hint = 'Below ~70% of core capacity (typical OK)';
    else if (r < 1.0) load_hint = 'Near core count — watch iowait & MySQL';
    else if (r < 1.5) load_hint = 'Above core count — queueing likely';
    else load_hint = 'Well above core count — sustained overload';
  }
  const vmstat = run('/usr/bin/vmstat', []);
  const top_cpu = run('/bin/ps', ['-eo', 'user,pid,pcpu,pmem,args', '--sort=-pcpu']).split('\n').slice(0, 16).join('\n');
  const meminfoRaw = run('/bin/cat', ['/proc/meminfo']);
  const meminfoKb = {};
  for (const line of meminfoRaw.split('\n')) {
    const m = line.match(/^(\w+):\s+(\d+)/);
    if (m) meminfoKb[m[1]] = parseInt(m[2], 10);
  }
  const interpretation = [];
  const swapT = meminfoKb.SwapTotal || 0;
  const swapF = meminfoKb.SwapFree || 0;
  const memAvail = meminfoKb.MemAvailable || 0;
  if (swapT > 0) {
    const swapUsedPct = Math.round((1 - swapF / swapT) * 100);
    if (swapUsedPct >= 95) {
      interpretation.push(`Swap ~${swapUsedPct}% used — RAM pressure. Consider reducing dev/IDE load, Meilisearch footprint, or MySQL buffer pool vs RAM.`);
    } else if (swapUsedPct >= 70) {
      interpretation.push(`Swap ~${swapUsedPct}% used — monitor available RAM.`);
    }
  }
  if (memAvail > 0 && memAvail < 1024 * 1024) {
    interpretation.push(`MemAvailable ~${Math.round(memAvail / 1024)} MiB — low free RAM headroom.`);
  }
  const vmLines = vmstat.split('\n').filter((l) => l.trim());
  const vmData = vmLines.find((l) => /^\s*\d/.test(l)) || '';
  const vmTok = vmData.trim().split(/\s+/);
  const waPct = vmTok.length >= 2 ? parseFloat(vmTok[vmTok.length - 2]) : null;
  if (waPct != null && !Number.isNaN(waPct) && waPct >= 20) {
    interpretation.push(`vmstat since-boot I/O wait (wa) ~${waPct}% — disk subsystem has been busy; correlate with MySQL/slow log and indexing.`);
  }
  let mysqlVars = {};
  let mysqlStatus = {};
  try {
    const names = ['slow_query_log', 'slow_query_log_file', 'long_query_time', 'max_connections', 'innodb_buffer_pool_size', 'datadir'];
    for (const n of names) {
      const [rows] = await db.query(`SHOW VARIABLES LIKE ${mysqlEscape(n)}`);
      mysqlVars[n] = rows[0]?.Value ?? null;
    }
    const sts = ['Threads_connected', 'Threads_running', 'Slow_queries', 'Questions', 'Uptime'];
    for (const s of sts) {
      const [rows] = await db.query(`SHOW GLOBAL STATUS LIKE ${mysqlEscape(s)}`);
      mysqlStatus[s] = rows[0]?.Value ?? null;
    }
  } catch (e) {
    mysqlVars = { error: e.message };
  }
  const swappiness = run('/bin/cat', ['/proc/sys/vm/swappiness']);
  const swapp = parseInt(String(swappiness).trim(), 10);
  if (!Number.isNaN(swapp) && swapp >= 60) {
    interpretation.push(`vm.swappiness=${swapp} — relatively high; values like 10–30 reduce aggressive swapping under RAM pressure (change with sysctl, requires root).`);
  }
  const wantSlow = req.query.slow_log !== '0' && req.query.slow_log !== 'false';
  let slow_log_snapshot = { skipped: true, reason: wantSlow ? 'MySQL variables unavailable' : 'slow_log tail omitted (pass slow_log=0)' };
  if (mysqlVars.error) {
    slow_log_snapshot = { skipped: true, reason: 'MySQL error — no slow log tail', detail: mysqlVars.error };
  } else if (!wantSlow) {
    slow_log_snapshot = { skipped: true, reason: 'slow_log tail omitted (?slow_log=0)' };
  } else if (mysqlVars.slow_query_log === 'ON' && mysqlVars.slow_query_log_file) {
    slow_log_snapshot = readMysqlSlowLogTail(mysqlVars.slow_query_log_file, req.query.slow_log_lines);
    if (slow_log_snapshot.ok && slow_log_snapshot.tail && /Query_time|# Time:/i.test(slow_log_snapshot.tail)) {
      interpretation.push('Slow log tail contains query timing lines — inspect slow_log_snapshot below.');
    } else if (slow_log_snapshot && slow_log_snapshot.ok === false && (slow_log_snapshot.hint || /permission|EACCES/i.test(String(slow_log_snapshot.error || '')))) {
      interpretation.push('Slow log file exists but is not readable by the gohostme process — use the hint in slow_log_snapshot (typically sudo tail as root).');
    }
  } else {
    slow_log_snapshot = { skipped: true, reason: 'slow_query_log is not ON or slow_query_log_file is empty' };
  }
  try {
    const tr = mysqlStatus.Threads_running != null ? parseInt(mysqlStatus.Threads_running, 10) : 0;
    if (tr > 30) interpretation.push(`MySQL Threads_running=${tr} — many concurrent queries; check PROCESSLIST and slow log.`);
    else if (tr > 10) interpretation.push(`MySQL Threads_running=${tr} — elevated; worth a quick SHOW PROCESSLIST.`);
  } catch (e) { /* ignore */ }
  if (interpretation.length === 0) {
    interpretation.push('No major red flags in automated checks — still review top_cpu during incidents.');
  }
  res.json({
    ok: true,
    ts: new Date().toISOString(),
    cpu_cores,
    load_1min: load1,
    load_per_core,
    load_hint,
    interpretation,
    meminfo_kb: {
      MemAvailable: memAvail > 0 ? memAvail : null,
      SwapTotal: swapT > 0 ? swapT : null,
      SwapFree: swapT > 0 ? swapF : null
    },
    vmstat_wa_since_boot_pct: waPct,
    swappiness: String(swappiness).trim(),
    slow_log_snapshot,
    disk,
    memory,
    loadavg,
    uptime,
    vmstat,
    top_cpu,
    mysql_variables: mysqlVars,
    mysql_status: mysqlStatus
  });
});

// ── Serve SPA for any unknown routes (after /api) ─────────────────────────
app.get('*', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

// ── Start ──────────────────────────────────────────────────────────────────
app.listen(PORT, '127.0.0.1', () => {
  console.log(`[GoHostMe] Running on http://127.0.0.1:${PORT}`);
  console.log(`[GoHostMe] DA Integration: ${DA_ADMIN ? 'CONFIGURED' : 'NOT CONFIGURED (set DA_ADMIN + DA_PASS in .env)'}`);
});
