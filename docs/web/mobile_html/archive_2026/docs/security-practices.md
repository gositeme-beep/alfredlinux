# GoSiteMe — Security Practices

> **Version:** 1.0 | **Last updated:** 2026-03-11
> Covers OWASP Top 10, authentication, encryption, and platform-specific security.

## Security Stack

```
Request → Caddy (TLS, headers) → PHP-FPM → api-security.php → endpoint
                                                    ↓
                                        input-validator.inc.php
                                        CSRF enforcement
                                        Rate limiting (Redis)
                                        CORS validation
                                        Security headers (CSP, X-Frame-Options)
```

### Core Security Files

| File | Purpose |
|------|---------|
| `includes/api-security.php` | Master security middleware (auto-loaded by api/config.php) |
| `includes/input-validator.inc.php` | Input validation & sanitization |
| `includes/api-response.inc.php` | Safe JSON response helpers |
| `includes/auth-check.inc.php` | Authentication enforcement |
| `includes/auth-gate.inc.php` | Page-level auth gate (redirects to login) |
| `config/shield_config.php` | Security configuration |

---

## 1. SQL Injection Prevention

**Required:** PDO prepared statements with parameterized queries for ALL database operations.

```php
// ✅ CORRECT — Parameterized query
$stmt = $pdo->prepare("SELECT * FROM clients WHERE email = ? AND status = ?");
$stmt->execute([$email, 'active']);

// ❌ WRONG — String interpolation
$stmt = $pdo->query("SELECT * FROM clients WHERE email = '$email'");

// ❌ WRONG — Even with int cast, use parameters
$stmt = $pdo->query("SELECT * FROM users LIMIT $limit OFFSET $offset");

// ✅ CORRECT — Parameters for LIMIT/OFFSET
$stmt = $pdo->prepare("SELECT * FROM users LIMIT ? OFFSET ?");
$stmt->execute([(int)$limit, (int)$offset]);
```

**Table and column names** cannot be parameterized. Use whitelists:

```php
$allowedColumns = ['name', 'created_at', 'status'];
$sortBy = in_array($input, $allowedColumns) ? $input : 'created_at';
```

---

## 2. Cross-Site Scripting (XSS) Prevention

### Output Escaping

```php
// ✅ HTML context
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');

// ✅ JSON API responses (json_encode escapes by default with JSON_HEX_TAG)
echo json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP);

// ✅ JavaScript context
echo json_encode($value); // For embedding in <script> tags

// ❌ NEVER do this
echo $userInput;
echo "<script>var x = '{$userInput}';</script>";
```

### Content Security Policy

API endpoints set strict CSP:
```
Content-Security-Policy: default-src 'none'; frame-ancestors 'none'
```

Frontend pages allow necessary sources via Caddy headers.

### Security Headers (auto-set by api-security.php)

| Header | Value |
|--------|-------|
| `X-Content-Type-Options` | `nosniff` |
| `X-Frame-Options` | `DENY` |
| `Referrer-Policy` | `strict-origin-when-cross-origin` |
| `Permissions-Policy` | `camera=(), microphone=(), geolocation=()` |

---

## 3. CSRF Protection

### Enforcement

`api-security.php` automatically enforces CSRF tokens on `POST`, `PUT`, `PATCH`, `DELETE` requests.

```php
// Token generation (in session)
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Token validation (automatic via api-security.php)
// Client sends: X-CSRF-Token header or csrf_token body field
```

### CSRF Exemptions

Some endpoints are exempt (webhooks, auth, external callbacks):

```php
// Set BEFORE requiring config.php
$GLOBALS['CSRF_EXEMPT'] = true;
require_once __DIR__ . '/../api/config.php';
```

**Currently exempt:** `api/stripe.php`, `api/webhooks.php`, `api/auth.php`, `api/health.php`, `api/vapi-webhook.php`, and 8 others.

### Frontend Integration

```javascript
// assets/js/gds-utils.js provides apiFetch() with automatic CSRF
import { apiFetch } from '/assets/js/gds-utils.js';
const result = await apiFetch('/api/endpoint.php', { method: 'POST', body: data });
```

---

## 4. Authentication

### Session Management

- PHP sessions via Redis (server-side storage)
- Session regeneration on login (`session_regenerate_id(true)`)
- Session timeout enforcement

### Password Security

```php
// Hashing (bcrypt via password_hash)
$hash = password_hash($password, PASSWORD_DEFAULT);

// Verification
if (password_verify($input, $storedHash)) { /* success */ }
```

### Rate Limiting

```php
// Global API: 60 requests/minute
apiRateLimit(60, 60, 'api_global', true);

// Auth-specific: 10 attempts per 15 minutes per IP
apiRateLimit(10, 900, 'auth_' . $clientIp, true);
```

Rate limit headers returned: `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`.

### OAuth Flows

- Google OAuth: Configured and working
- GitHub OAuth: Endpoint exists but not configured
- 2FA: TOTP-based (verify-2fa action in auth.php)

---

## 5. API Security

### Input Validation

```php
// Use filter_input() for GET/POST parameters
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Or manual validation with the input validator
require_once 'includes/input-validator.inc.php';
```

### Response Helpers

```php
// includes/api-response.inc.php
apiSuccess(['data' => $result]);           // 200 + JSON
apiError('Not found', 404);                // 404 + JSON error
apiRequireFields(['email', 'name']);        // Auto-validate required fields
apiPaginated($items, $total, $page, $perPage);  // Paginated response
```

### CORS Configuration

```php
// api-security.php auto-handles CORS
// Allows: https://gositeme.com origin
// Blocks: external origins
// OPTIONS preflight returns 204
```

---

## 6. Encryption

### Veil E2E Communications

The communications system (Veil) uses:

- **X3DH** (Extended Triple Diffie-Hellman) for key agreement
- **Double Ratchet** for forward secrecy
- **ECDH** + **ECDSA** key pairs per user
- **AES-256-GCM** for message encryption
- Pre-keys for async key exchange

```
Messages stored as: ciphertext + iv (server never sees plaintext)
Files: encrypted_meta + encrypted storage
```

### API Keys

- Stored as `key_hash` (never plaintext)
- Generated with `bin2hex(random_bytes(32))`

### Sensitive Data

- Database credentials: `config/database.php` (file-blocked by Caddy)
- API secrets: Environment variables or config files
- Blocked by Caddy: `*.env *.sql *.log *.bak *.backup *.orig *.old .htaccess`

---

## 7. File Security

### Blocked Files (Caddyfile)

```
*.env, *.sql, *.log, *.bak, *.backup, *.orig, *.old, .htaccess
config/, includes/ (direct access blocked)
```

### Upload Security

- Validate MIME type server-side
- Randomize filenames
- Store outside web root when possible
- Never execute uploaded files

---

## 8. Dependency Security

### PHP Dependencies

```bash
composer audit              # Check for known vulnerabilities
composer update --dry-run   # Preview updates
```

### Monitoring

- GitHub Actions CI runs security scans on push
- PHPUnit security tests: 499+ tests including XSS, SQLi, CSRF, header checks

---

## 9. Security Testing

### Test Suite

| Suite | Tests | Focus |
|-------|-------|-------|
| SecurityTest | 16 | Headers, CORS, path traversal, error leaks |
| CsrfEnforcementTest | 4 | CSRF token validation |
| AuthTest | 29 | Authentication flows, injection attempts |
| ToolsTest | 34 | Tool API security |
| XssTest | 43 | XSS prevention across 20+ endpoints |
| SqlInjectionTest | 33 | SQL injection across 20+ endpoints |

### Running Security Tests

```bash
# All security tests
vendor/bin/phpunit tests/Security/

# Specific suite
vendor/bin/phpunit tests/Security/XssTest.php
vendor/bin/phpunit tests/Security/SqlInjectionTest.php

# Full API test suite (includes security checks)
vendor/bin/phpunit
```

---

## 10. Checklist for New Code

- [ ] All SQL queries use PDO prepared statements
- [ ] All user output escaped with `htmlspecialchars()` or `json_encode()`
- [ ] CSRF token required on POST/PUT/PATCH/DELETE (or explicitly exempt)
- [ ] Authentication checked on protected endpoints
- [ ] Input validated with `filter_input()` or manual sanitization
- [ ] Rate limiting applied to public endpoints
- [ ] No PHP errors/stack traces leaked to users
- [ ] No sensitive data in logs or responses
- [ ] Security headers set (via `api-security.php` or Caddy)
- [ ] File uploads validated (type, size, name)

---

## Known Issues

| Issue | Status | Notes |
|-------|--------|-------|
| `social-feed.php` returns 500 | Known | Pre-existing broken endpoint |
| Some LIMIT/OFFSET use int cast | Low risk | Integer cast is safe but should use params |
| `store.php` returns 500 | Known | Pre-existing broken endpoint |
| GitHub OAuth not configured | Deferred | Endpoint exists, config missing |
