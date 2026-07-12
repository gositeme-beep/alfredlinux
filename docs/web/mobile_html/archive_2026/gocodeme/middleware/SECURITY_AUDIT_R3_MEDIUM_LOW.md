# GoCodeMe Middleware — Security Audit Round 3 (MEDIUM & LOW)

**Date:** 2025-01-XX  
**Scope:** `middleware/src/` — all route files, auth/, billing/, directadmin/, monitoring/, tokens/, git/  
**Focus:** Remaining MEDIUM and LOW severity vulnerabilities after 45 Round 1-2 fixes + 9 Round 3 Critical/High fixes  

---

## Summary

| Severity | Count |
|----------|-------|
| MEDIUM   | 11    |
| LOW      | 12    |
| **Total**| **23**|

---

## MEDIUM Severity Findings

---

### M-01 · Error Message Information Disclosure (Systematic)

**Category:** Error Message Leak / Information Disclosure  
**Severity:** MEDIUM  
**Files affected (20+):**

| File | Lines (examples) |
|------|-----------------|
| `routes/sso.js` | 35, 63, 85, 122 |
| `routes/git.js` | 52, 81, 102, 117, 131, 179, 192, 275 |
| `routes/files.js` | 47, 61, 78, 93, 110, 124, 139 |
| `routes/billing.js` | 45, 60, 81, 98, 112 |
| `routes/claude.js` | all catch blocks |
| `routes/developer.js` | 129, 168, 208, 277, 324, 524 |
| `routes/teams.js` | all catch blocks |
| `routes/reseller.js` | all catch blocks |
| `routes/onboarding.js` | all catch blocks |
| `routes/referral.js` | all catch blocks |
| `routes/openclaw.js` | 74, 141, 158, 182, 207 |
| `routes/access.js` | 58, 72, 86, 100, 129 |
| `routes/tokens.js` | all catch blocks |
| `routes/usage.js` | all catch blocks |
| `routes/alfred.js` | all catch blocks (~30 endpoints) |
| `routes/admin.js` | all catch blocks |
| `routes/autopilotProxy.js` | 234, 300, 319, 349, 370, 391, 408, 424, 443, 459, 479, 503 |
| `routes/templates.js` | 253 |
| `routes/voiceRelay.js` | 212 |
| `server.js` | 313 (global error handler) |

**Description:** Raw `err.message` is returned to clients in HTTP responses across virtually every route file. The only exception is `routes/hosting.js` which correctly uses a `safeError()` wrapper (fixed in R3-05). Error messages may reveal:
- Internal file paths and directory structure
- Database connection details
- Redis key patterns and state
- Stack traces from unhandled exceptions
- Third-party API error details (Anthropic, WHMCS)

**Code example (routes/git.js:52):**
```js
  } catch (err) {
    logger.error(`git checkpoint error: ${err.message}`);
    res.status(500).json({ ok: false, error: err.message });  // ← raw leak
  }
```

**Code example (server.js:313 — global error handler):**
```js
app.use((err, _req, res, _next) => {
  logger.error(err.stack || err.message);
  const status = err.status || 500;
  res.status(status).json({ ok: false, error: err.message || 'Internal server error' });
});
```

**Recommended fix:** Create a centralized `safeError()` helper (like hosting.js already has) and apply it globally:
```js
// src/utils/safeError.js
function safeError(err) {
  const msg = err.message || '';
  // Only pass through safe, known error strings
  if (msg.includes('required') || msg.includes('not found') || msg.includes('Invalid'))
    return msg;
  return 'An internal error occurred';
}

// Global error handler (server.js):
app.use((err, _req, res, _next) => {
  logger.error(err.stack || err.message);
  const status = err.status || 500;
  res.status(status).json({ ok: false, error: status >= 500 ? 'Internal server error' : (err.message || 'Error') });
});
```

---

### M-02 · SSRF via User-Registered Webhooks

**Category:** SSRF (Server-Side Request Forgery)  
**Severity:** MEDIUM  
**File:** `routes/extras.js`  
**Lines:** 282-301

**Description:** The `fireWebhooks()` function sends HTTP POST requests to URLs stored in Redis (`webhooks:<clientId>`). These URLs are user-supplied when registering webhooks. While the function uses `new URL()` to parse the URL, there is **no validation** against internal/private IP ranges. An attacker can register a webhook URL targeting:
- `http://127.0.0.1:3001/...` (the middleware itself)
- `http://127.0.0.1:6379/` (Redis)
- `http://169.254.169.254/` (cloud metadata service)
- `http://10.x.x.x/` or `http://192.168.x.x/` (internal network)

**Code:**
```js
async function fireWebhooks(clientId, event, payload) {
  // ...
  for (const wh of matching) {
    try {
      const url = new URL(wh.url);
      const body = JSON.stringify({ event, timestamp: new Date().toISOString(), ...payload });
      const req = https.request(url.href, { method: 'POST', ... });  // ← no IP/host validation
      req.write(body);
      req.end();
    } catch (_) {}
  }
}
```

**Recommended fix:** Validate webhook URLs at registration time AND at fire time:
```js
const BLOCKED_HOSTS = /^(127\.|10\.|172\.(1[6-9]|2\d|3[01])\.|192\.168\.|169\.254\.|0\.|localhost|::1)/;
function isAllowedWebhookUrl(urlStr) {
  try {
    const u = new URL(urlStr);
    if (u.protocol !== 'https:') return false;
    if (BLOCKED_HOSTS.test(u.hostname)) return false;
    if (u.port && u.port !== '443') return false;
    return true;
  } catch { return false; }
}
```

---

### M-03 · `redis.keys()` DoS in Production Paths

**Category:** Denial of Service  
**Severity:** MEDIUM  
**Files & Lines:**

| File | Line | Pattern | Context |
|------|------|---------|---------|
| `server.js` | 372 | `redis.keys('launch:sessions:*')` | Idle session reaper (runs every 5 min) |
| `routes/launch.js` | 70 | `redis.keys(...)` | `findFreePorts()` — called on every IDE launch |
| `billing/emailAutomation.js` | 285, 396, 487, 562 | `redis.keys('da_username:*')` | Email batch jobs |
| `monitoring/healthMonitor.js` | 143 | `redis.keys(...)` | Health monitoring |

**Description:** `redis.keys()` is O(N) and blocks the single-threaded Redis event loop while scanning all keys. In production with thousands of keys, these calls can cause latency spikes that affect all Redis-dependent operations. The `routes/admin.js` uses (lines 27, 205, 242) are behind `requireWhmcsSecret` (admin-only) so are lower risk.

**Code (server.js:372):**
```js
const keys = await redis.keys('launch:sessions:*');
```

**Recommended fix:** Replace with `SCAN` cursors:
```js
async function scanKeys(redis, pattern) {
  const results = [];
  let cursor = '0';
  do {
    const [next, keys] = await redis.scan(cursor, 'MATCH', pattern, 'COUNT', 100);
    cursor = next;
    results.push(...keys);
  } while (cursor !== '0');
  return results;
}
```

---

### M-04 · Shell Injection in `killPort()`

**Category:** Command Injection  
**Severity:** MEDIUM  
**File:** `routes/launch.js`  
**Line:** 145

**Description:** The `killPort()` function interpolates a `port` variable directly into a shell command via `execSync`. While `port` comes from internally-assigned port numbers (not direct user input), a bug in port allocation or unexpected value could lead to command injection. This violates defense-in-depth.

**Code:**
```js
function killPort(port) {
  try {
    const { execSync } = require('child_process');
    execSync(`fuser -k ${port}/tcp 2>/dev/null`, { timeout: 5000 });
  } catch { /* no process or fuser not available */ }
}
```

**Recommended fix:** Use `execFile` (no shell) or validate the port is numeric:
```js
function killPort(port) {
  const p = parseInt(port, 10);
  if (!Number.isInteger(p) || p < 1 || p > 65535) return;
  try {
    const { execFileSync } = require('child_process');
    execFileSync('fuser', ['-k', `${p}/tcp`], { timeout: 5000, stdio: 'ignore' });
  } catch { /* no process or fuser not available */ }
}
```

---

### M-05 · Shell Injection in `admin.js` `execSync`

**Category:** Command Injection  
**Severity:** MEDIUM  
**File:** `routes/admin.js`  
**Line:** 67

**Description:** Admin dashboard calls `execSync('npx pm2 jlist 2>/dev/null', ...)` through a shell. While admin-only and the command is static (not user-interpolated), using `execSync` with shell is a risk vector if this pattern is copied elsewhere. The 2>/dev/null redirection forces shell invocation.

**Code:**
```js
const raw = execSync('npx pm2 jlist 2>/dev/null', { timeout: 5000, encoding: 'utf8' });
```

**Recommended fix:** Use `execFileSync` without shell:
```js
const { execFileSync } = require('child_process');
const raw = execFileSync('npx', ['pm2', 'jlist'], { timeout: 5000, encoding: 'utf8' });
```

---

### M-06 · Voice Relay Sessions Not Tied to Authenticated Users

**Category:** Session Fixation / Missing Authentication  
**Severity:** MEDIUM  
**File:** `routes/voiceRelay.js`  
**Lines:** 62-78 (connect), 185-191 (requireSession)

**Description:** Voice relay sessions are created via `POST /connect` without any authentication check. The session is identified by a random 16-byte hex ID, and anyone who knows (or brute-forces) the session ID can send messages through (`/send`) or read messages from (`/poll`) the relay. There is no binding to the authenticated user who created the session.

**Code:**
```js
// POST /connect — no auth middleware applied
router.post('/connect', (req, res) => {
  const sessionId = crypto.randomBytes(16).toString('hex');
  // ... creates session, no user binding
});

// requireSession only checks session_id existence — not ownership
function requireSession(req, res, next) {
  const sid = req.body?.session_id || req.query?.session_id || req.headers['x-voice-session'];
  const sess = sessions.get(sid);
  if (!sess) return res.status(404).json({ ok: false, error: 'Session not found or expired' });
  req.voiceSession = sess;
  next();
}
```

**Recommended fix:** Require the GoCodeMe session JWT on `/connect` and bind the session to the authenticated user. Validate ownership on subsequent requests:
```js
router.post('/connect', requireGoCodeMeSession, (req, res) => {
  // ...
  const sess = { id: sessionId, owner: req.user.daUsername, ... };
});
```

---

### M-07 · Missing Rate Limit on SSO Login Endpoint

**Category:** Missing Rate Limiting  
**Severity:** MEDIUM  
**File:** `routes/sso.js`  
**Line:** 24

**Description:** `POST /api/sso/login` is the primary authentication endpoint. It has no route-specific rate limit — only the global 180 req/min limit on all `/api/*` routes. This is insufficient for an auth endpoint. An attacker could attempt credential/token brute-forcing at 180 attempts/minute, or 3 per second.

**Code:**
```js
router.post('/login', async (req, res) => {
  // No specific rate limiter
  const { token } = req.body;
  // ...
});
```

**Recommended fix:** Add a strict rate limit (e.g., 10 req/min per IP):
```js
const rateLimit = require('express-rate-limit');
const ssoLoginLimiter = rateLimit({
  windowMs: 60 * 1000, max: 10, keyGenerator: (req) => req.ip,
  message: { ok: false, error: 'Too many login attempts. Try again in 1 minute.' },
});
router.post('/login', ssoLoginLimiter, async (req, res) => { ... });
```

---

### M-08 · Template `execSync` with Shell via `npm`/`npx`/`composer` Commands

**Category:** Command Injection  
**Severity:** MEDIUM  
**File:** `routes/templates.js`  
**Lines:** 87 (express-api setup), 125 (python-flask setup), 253 (apply endpoint)

**Description:** Template setup functions and the apply endpoint use `execSync` which invokes a shell. While the `templateId` is validated against a hardcoded allowlist (not user-supplied), the templates themselves contain shell commands with `&&`, `2>/dev/null`, and other shell constructs. The `cwd` parameter is derived from `daUsername` which could theoretically contain special characters.

**Code (line 253):**
```js
execSync(template.command, {
  cwd: workDir,       // workDir = `/tmp/gocodeme-workspace-${daUsername}`
  stdio: 'pipe',
  timeout: 120000,
  env: { ...process.env, HOME: workDir },
});
```

**Code (line 87 — express-api setup):**
```js
execSync('npm install express cors dotenv', { cwd: dir, stdio: 'pipe', timeout: 60000 });
```

**Recommended fix:** Use `execFile` for the npm/pip calls that don't need shell features:
```js
const { execFileSync } = require('child_process');
execFileSync('npm', ['install', 'express', 'cors', 'dotenv'], { cwd: dir, stdio: 'pipe', timeout: 60000 });
```
For template commands that require shell features (e.g., `&&`, `2>/dev/null`), validate `daUsername` against a strict pattern before constructing `workDir`:
```js
if (!/^[a-z][a-z0-9]{2,15}$/.test(daUsername)) {
  return res.status(400).json({ ok: false, error: 'Invalid username format' });
}
```

---

### M-09 · Templates `err.message` Leak Including Command Output

**Category:** Error Message Leak / Information Disclosure  
**Severity:** MEDIUM  
**File:** `routes/templates.js`  
**Line:** 253

**Description:** When template application fails, the raw `err.message` (which for `execSync` failures includes the full stderr/stdout of the failed command) is returned to the client, truncated to 200 chars. This can reveal internal filesystem paths, installed package versions, and system configuration.

**Code:**
```js
  } catch (err) {
    logger.error(`Template apply error (${templateId}):`, err.message);
    res.status(500).json({ ok: false, error: `Failed to apply template: ${err.message.slice(0, 200)}` });
  }
```

**Recommended fix:**
```js
res.status(500).json({ ok: false, error: 'Failed to apply template. Please try again or contact support.' });
```

---

### M-10 · SSRF in Performance Benchmark Endpoint

**Category:** SSRF (Server-Side Request Forgery)  
**Severity:** MEDIUM  
**File:** `routes/alfred.js`  
**Lines:** ~845-860 (performance-benchmark endpoint)

**Description:** The `/api/alfred/performance-benchmark` endpoint accepts a `url` parameter from the request body and makes an HTTP request to it using `fetch()`. While this endpoint is behind `requireWhmcsSecret` (service-to-service), the `url` value originates from a user's Alfred chat request. No validation is performed against internal IP ranges.

**Code:**
```js
router.post('/performance-benchmark', async (req, res) => {
  const { client_id, url } = req.body;
  // ...
  const resp = await fetch(url, { signal: AbortSignal.timeout(10000) });
  statusCode = resp.status;
});
```

**Recommended fix:** Validate the URL against a private IP blocklist:
```js
const parsedUrl = new URL(url);
if (isPrivateIP(parsedUrl.hostname)) {
  return res.json({ ok: false, error: 'Cannot benchmark internal addresses' });
}
```

---

### M-11 · `POST /api/openclaw/link` Accepts Arbitrary JWT Without Rate Limiting

**Category:** Missing Rate Limiting / Token Brute Force  
**Severity:** MEDIUM  
**File:** `routes/openclaw.js`  
**Lines:** 101-140

**Description:** The `/api/openclaw/link` endpoint accepts a JWT token in the request body and verifies it. It has no authentication requirement (by design — the token IS the credential) and no rate limiting. An attacker could attempt brute-force or timing attacks against the JWT verification.

**Code:**
```js
router.post('/link', async (req, res) => {
  // No rate limiting, no auth
  const { token, channel, channelUserId } = req.body;
  let payload;
  try {
    payload = jwt.verify(token, JWT_SECRET);
  } catch (err) {
    return res.status(401).json({ ok: false, error: 'Invalid or expired link token' });
  }
  // ...
});
```

**Recommended fix:** Add rate limiting:
```js
const linkLimiter = rateLimit({ windowMs: 60000, max: 10 });
router.post('/link', linkLimiter, async (req, res) => { ... });
```

---

## LOW Severity Findings

---

### L-01 · Log Injection via User-Controlled Values

**Category:** Log Injection  
**Severity:** LOW  
**Files affected:** All route files that call `logger.info/error/warn` with user-controlled values

**Description:** User-controlled values like `daUsername`, `err.message`, `templateId`, `event`, `domain` are interpolated directly into log messages. An attacker could craft values containing newline characters (`\n`) or log format strings to inject fake log entries, potentially misleading forensic analysis.

**Examples:**
```js
// routes/templates.js:251
logger.info(`Template ${templateId} applied to workspace for ${daUsername}`);

// routes/git.js:49
logger.info(`git checkpoint: ${username} @ ${workDir}`);

// routes/extras.js:338
logger.error(`deploy: error for ${daUsername}: ${err.message}`);
```

**Recommended fix:** Sanitize logged values by stripping newlines and control characters:
```js
function sanitizeForLog(str) {
  return String(str).replace(/[\n\r\t\x00-\x1f]/g, '_').slice(0, 200);
}
logger.info(`Template ${sanitizeForLog(templateId)} applied for ${sanitizeForLog(daUsername)}`);
```

---

### L-02 · `Math.random()` for Tool-Use ID Generation

**Category:** Insecure Randomness  
**Severity:** LOW  
**File:** `billing/formatTranslator.js`  
**Line:** 246

**Description:** `Math.random()` is used to generate a fallback tool_use ID when converting OpenAI format to Anthropic format. `Math.random()` is not cryptographically secure. While this ID is used only as an internal API protocol identifier (not for security), it could theoretically cause collisions in high-throughput scenarios.

**Code:**
```js
id: tc.id || `toolu_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`,
```

**Recommended fix:** Use `crypto.randomBytes()`:
```js
const crypto = require('crypto');
id: tc.id || `toolu_${Date.now()}_${crypto.randomBytes(4).toString('hex')}`,
```

---

### L-03 · Missing Rate Limit on Public Referral Validation

**Category:** Missing Rate Limiting  
**Severity:** LOW  
**File:** `routes/referral.js`  
**Line:** (GET /api/referral/validate/:code endpoint)

**Description:** The `GET /api/referral/validate/:code` endpoint is public (no authentication required) and has no route-specific rate limit beyond the global 180/min. An attacker could enumerate valid referral codes.

**Recommended fix:** Add a stricter rate limit:
```js
const validateLimiter = rateLimit({ windowMs: 60000, max: 30 });
router.get('/validate/:code', validateLimiter, async (req, res) => { ... });
```

---

### L-04 · `console.error` Instead of Logger in `openclaw.js`

**Category:** Inconsistent Logging  
**Severity:** LOW  
**File:** `routes/openclaw.js`  
**Line:** 56

**Description:** One error case uses `console.error` instead of the structured `logger.error` used everywhere else. This bypasses log rotation, formatting, and any future log ingestion pipeline.

**Code:**
```js
if (err.code !== 'ECONNREFUSED') console.error('[openclaw/status] stats error:', err.message);
```

**Recommended fix:**
```js
if (err.code !== 'ECONNREFUSED') logger.warn(`openclaw/status stats error: ${err.message}`);
```

---

### L-05 · `billing.js` Returns `err.message` in Warning Field

**Category:** Information Disclosure  
**Severity:** LOW  
**File:** `routes/billing.js`  
**Line:** 60

**Description:** The `/api/billing/invoices` endpoint returns `err.message` in a `warning` field in a successful (200) response. This means even non-error responses can leak internal error details.

**Code:**
```js
  } catch (err) {
    logger.warn(`billing/invoices: ${err.message}`);
    res.json({ ok: true, invoices: [], warning: err.message });
  }
```

**Recommended fix:**
```js
res.json({ ok: true, invoices: [], warning: 'Could not retrieve invoices from billing system' });
```

---

### L-06 · `sso.js` Leaks JWT Verification Error Details

**Category:** Information Disclosure  
**Severity:** LOW  
**File:** `routes/sso.js`  
**Lines:** 35, 63

**Description:** The SSO login and exchange endpoints return raw `err.message` which can include JWT-specific error strings like "jwt malformed", "jwt expired", "invalid signature" etc. This helps attackers understand the token format and timing.

**Code (line 63):**
```js
return res.status(401).json({ ok: false, error: `Invalid token: ${err.message}` });
```

**Recommended fix:** Return a generic message:
```js
return res.status(401).json({ ok: false, error: 'Invalid or expired token' });
```

---

### L-07 · `git.js` Clone Error Leaks Command Output

**Category:** Information Disclosure  
**Severity:** LOW  
**File:** `routes/git.js`  
**Lines:** 270-275

**Description:** The git clone error handler concatenates stderr, stdout, and err.message and returns up to 200 characters to the client. This can reveal filesystem paths, git internal messages, and server configuration.

**Code:**
```js
const msg = ((err.stderr || '') + (err.stdout || '') + err.message).trim();
// ...
res.status(500).json({ ok: false, error: `Clone failed: ${msg.slice(0, 200)}` });
```

**Recommended fix:**
```js
res.status(500).json({ ok: false, error: 'Clone failed. Ensure the repo URL is correct and publicly accessible.' });
```

---

### L-08 · `voiceRelay.js` Send Error Leaks `err.message`

**Category:** Information Disclosure  
**Severity:** LOW  
**File:** `routes/voiceRelay.js`  
**Line:** 212

**Description:** The voice relay `/send` endpoint concatenates a string with `err.message`, which could expose WebSocket internals.

**Code:**
```js
res.status(500).json({ ok: false, error: 'Send failed: ' + err.message });
```

**Recommended fix:**
```js
res.status(500).json({ ok: false, error: 'Send failed' });
```

---

### L-09 · Reseller Branding `logoUrl` — No URL Validation

**Category:** Stored XSS / SSRF Risk  
**Severity:** LOW  
**File:** `routes/reseller.js`  
**Lines:** ~200-230 (branding update endpoint)

**Description:** Resellers can set a `logoUrl` in their branding configuration, and this URL is stored in Redis and later retrieved for rendering in sub-account IDEs. No validation is performed on the URL format, protocol, or host. A malicious reseller could set a `javascript:` URL (stored XSS) or an internal IP (SSRF when the logo is fetched server-side).

**Recommended fix:** Validate the logo URL at storage time:
```js
if (logoUrl) {
  try {
    const u = new URL(logoUrl);
    if (!['http:', 'https:'].includes(u.protocol)) throw new Error('bad proto');
  } catch {
    return res.status(400).json({ ok: false, error: 'Invalid logo URL — must be https://' });
  }
}
```

---

### L-10 · No Input Length Validation on Several Endpoints

**Category:** Missing Input Validation  
**Severity:** LOW  
**Files affected:**

| File | Endpoint | Field |
|------|----------|-------|
| `routes/extras.js` | POST /notes | `content` — no max length |
| `routes/extras.js` | POST /snippets | `code`, `description` — no max length |
| `routes/extras.js` | POST /webhooks | `url` — no max length |
| `routes/teams.js` | POST /create | `name` — no max length |
| `routes/reseller.js` | POST /accounts/create | `name` — no max length |
| `routes/referral.js` | POST /custom-code | `code` — relies on regex only |
| `routes/openclaw.js` | POST /send | `text` — no max length |
| `routes/git.js` | POST /checkpoint | `message` — no max length |

**Description:** Multiple endpoints accept string inputs without enforcing maximum length limits. While Express's `body-parser` has a default limit (100KB), individual fields should have application-level limits to prevent abuse (e.g., storing gigantic notes, webhook URLs, or git commit messages).

**Recommended fix:** Add per-field length validation:
```js
if (content && content.length > 10000) {
  return res.status(400).json({ ok: false, error: 'Content too long (max 10,000 characters)' });
}
```

---

### L-11 · `developer.js` Chat Response Uses `crypto.randomBytes` for Non-Security ID

**Category:** Code Quality (not a vulnerability)  
**Severity:** LOW  
**File:** `routes/developer.js`  
**Line:** ~480

**Description:** The OpenAI-compatible response format generates a `chatcmpl-` ID using `crypto.randomBytes(12).toString('hex')`. This is fine security-wise but is overkill for a response correlation ID. Noting for consistency — not a vulnerability.

---

### L-12 · Helmet CSP Relaxed for Dashboard and Fully Disabled for IDE/Agent Routes

**Category:** Missing Security Headers  
**Severity:** LOW  
**File:** `server.js`  
**Lines:** ~50-80 (helmet configuration)

**Description:** Helmet security headers are conditionally applied:
- **Completely skipped** for `/middleware/ide/` and `/middleware/agent/` routes (to allow Theia/OpenHands to function)
- **CSP relaxed** for `/middleware/dashboard/` (allows `unsafe-inline`, `unsafe-eval`, `data:`)

While functional requirements justify these relaxations, the IDE/agent paths have zero security headers (no X-Frame-Options, no X-Content-Type-Options, no HSTS). This is a reduced attack surface concern since these routes are behind auth tokens.

**Recommended fix:** Apply at minimum `X-Content-Type-Options: nosniff` and `X-Frame-Options: DENY` even on IDE routes:
```js
app.use('/middleware/ide/', (req, res, next) => {
  res.setHeader('X-Content-Type-Options', 'nosniff');
  res.setHeader('X-Frame-Options', 'SAMEORIGIN');
  next();
});
```

---

## Excluded (Already Fixed in R1/R2/R3)

The following patterns were confirmed as already fixed and are NOT reported above:

| Fix ID | Pattern | Status |
|--------|---------|--------|
| R1-01 through R1-10 | Path traversal, DA credential handling, XSS, CORS, etc. | ✅ Fixed |
| R2-01 through R2-35 | HMAC timing, JWT fallback, open redirect, URL validation, etc. | ✅ Fixed |
| R3-01 | `rejectUnauthorized: false` (documented, localhost only) | ✅ Accepted |
| R3-02 | `execSync` in deploy (changed to `execFile`) | ✅ Fixed |
| R3-03 | IDE proxy auth bypass → UUID token validation | ✅ Fixed |
| R3-04 | Unauthenticated agent proxy | ✅ Fixed |
| R3-05 | `safeError()` in hosting.js | ✅ Fixed |
| R3-06 | `redis.keys()` in ideProxy.js | ✅ Fixed |
| R3-07 | WHMCS secret HMAC comparison | ✅ Fixed |
| R3-08 | Access status check on every request | ✅ Fixed |
| R3-09 | DA username ownership check (requireOwnDAUser) | ✅ Fixed |

---

## Priority Recommendation

**Immediate (this sprint):**
1. **M-01** — Create centralized `safeError()` and apply across all routes + global handler. Estimated: 1-2 hours. Highest impact — affects 20+ files.
2. **M-07** — Add rate limiter to SSO login. Estimated: 5 minutes.
3. **M-04** — Fix `killPort()` shell injection. Estimated: 5 minutes.

**Next sprint:**
4. **M-02** — SSRF protection for webhooks.
5. **M-06** — Bind voice relay sessions to authenticated users.
6. **M-03** — Replace `redis.keys()` with `SCAN` in production paths.
7. **M-10, M-11** — Add rate limiters and URL validation.

**Backlog:**
8. All LOW findings — incremental improvements.
