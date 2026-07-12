# GoSiteMe Platform — System Architecture

> **Version:** 1.0 | **Last updated:** 2026-03-11 | **Platform:** v14.0+

## Overview

GoSiteMe is a full-stack AI platform built on PHP 8.3, vanilla JavaScript, MySQL, and Redis. The platform powers **Alfred AI** — an AI assistant with 1,220+ native tools across 29 categories, voice commands, agent fleets, and enterprise features.

```
┌─────────────────────────────────────────────────────────────┐
│                     Caddy Reverse Proxy                      │
│              TLS 1.3 · HTTP/2 · Auto HTTPS                  │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────┐  │
│  │  PHP Pages   │  │   API Layer  │  │   WebSocket      │  │
│  │  101 .php    │  │  183 endpts  │  │   (PM2/Node)     │  │
│  │  site-header │  │  JSON REST   │  │   Real-time      │  │
│  │  site-footer │  │  Rate limited│  │                   │  │
│  └──────┬───────┘  └──────┬───────┘  └──────────────────┘  │
│         │                 │                                  │
│  ┌──────┴─────────────────┴──────────────────────────────┐  │
│  │              Shared Infrastructure                     │  │
│  │  ┌──────────┐  ┌────────────┐  ┌───────────────────┐  │  │
│  │  │  MySQL   │  │   Redis    │  │   File Storage    │  │  │
│  │  │  PDO     │  │   Cache    │  │   /comms_storage  │  │  │
│  │  │          │  │   Sessions │  │   /ai-images      │  │  │
│  │  └──────────┘  └────────────┘  └───────────────────┘  │  │
│  └───────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

## Directory Structure

```
public_html/
├── *.php                    # 101 frontend pages (PHP + inline CSS/HTML)
├── api/                     # 183 REST API endpoints (PHP)
├── assets/
│   ├── css/                 # 9 shared stylesheets (design-tokens, components)
│   └── js/                  # 87 JavaScript modules (engines, utilities, UI)
├── includes/                # 24 PHP includes (header, footer, auth, security)
├── config/                  # Database config, schema files, validation
├── middleware/              # Request middleware (proxy)
├── templates/               # Email & page templates
├── scripts/                 # Automation, cron jobs, crawlers
├── tests/                   # PHPUnit test suites
├── docs/                    # Documentation
├── sdks/                    # Official SDKs (Node, Python, PHP)
├── voice/                   # Voice AI features
├── veil/                    # Encrypted communications (E2E)
├── comms/                   # Communications UI (chat, WebRTC)
├── ai-servers/              # GPU server marketplace
├── gocodeme/                # Code editor & playground
├── pay/                     # Billing & domain management
└── websocket/               # WebSocket server (Node.js)
```

## Frontend Architecture

### Page Pattern

Every PHP page follows the same structure:

```php
<?php
$pageTitle = "Page Name";
$pageDescription = "SEO description";
include 'includes/site-header.inc.php';
?>
<style>/* Page-specific CSS */</style>
<!-- Page HTML content -->
<script src="/assets/js/page-engine.js"></script>
<?php include 'includes/site-footer.inc.php'; ?>
```

### Key Frontend Components

| Component | Description |
|-----------|-------------|
| `site-header.inc.php` | Navigation, meta tags, GDS inclusion, CSRF token (`window.AW_CSRF_TOKEN`) |
| `site-footer.inc.php` | Footer, analytics, common scripts |
| `assets/css/design-tokens.css` | CSS custom properties with `--gds-` prefix |
| `assets/css/components.css` | 14 reusable UI components (549 lines) |
| `assets/js/gds-utils.js` | Utility module with `GDS.fetch()` (auto-injects CSRF) |
| `assets/js/gds-toast.js` | Toast notification system |
| `assets/js/gds-modal.js` | Modal dialog system |

### Design System (GDS)

- **CSS Variables:** `--gds-primary`, `--gds-bg`, `--gds-surface`, etc.
- **Theme:** Dark mode default, CSS custom properties for theming
- **Icons:** Font Awesome 6 Pro + inline SVG
- **Responsive:** Mobile-first, `pointer: coarse` media queries for touch
- **No build step:** Raw JS/CSS served directly, no bundler

### JavaScript Pattern

```javascript
// ES module pattern — no dependencies, no build tools
(function() {
    'use strict';
    // Module code
    function init() { /* ... */ }
    document.addEventListener('DOMContentLoaded', init);
})();
```

- Use `fetch()` for API calls (never jQuery)
- Export via `window.ModuleName` or ES `export`
- `GDS.fetch()` auto-injects CSRF tokens

## API Architecture

### Request Flow

```
Client Request
    ↓
Caddy (TLS termination, static files, blocked patterns)
    ↓
PHP-FPM 8.3
    ↓
includes/api-security.php (auto-loaded via config)
    ├── Security headers (CSP, X-Content-Type, X-Frame-Options)
    ├── CORS handling (preflight OPTIONS)
    ├── Global rate limiting (60 req/min per IP)
    ├── CSRF enforcement on POST/PUT/PATCH/DELETE
    └── Input validation (includes/input-validator.inc.php)
    ↓
API Endpoint (switch on ?action=)
    ├── PDO prepared statements (SQL injection prevention)
    ├── Input sanitization
    └── JSON response
```

### Security Stack

| Layer | Implementation |
|-------|---------------|
| **TLS** | Caddy auto-HTTPS, TLS 1.3 |
| **Rate Limiting** | 60 req/min global API, 10 auth attempts/15min |
| **CSRF** | Auto-enforcement via `api-security.php` on mutating methods |
| **SQL Injection** | PDO prepared statements (audited: ALL CLEAR) |
| **XSS** | Output escaping with `htmlspecialchars()`, CSP headers |
| **CORS** | Origin whitelist: `gositeme.com`, `www.gositeme.com` |
| **File Blocking** | Caddy blocks `*.env *.sql *.log *.bak *.backup *.orig *.old .htaccess` |

### API Response Helpers

```php
// includes/api-response.inc.php
apiSuccess($data);                          // 200 + success envelope
apiError($message, $code);                  // Error with HTTP status
apiPaginated($items, $page, $perPage, $total); // Paginated response
apiRequireFields($data, $fields);           // Validate required fields
apiGetBody();                               // Parse JSON request body
```

### CSRF Exemptions

13 API files are CSRF-exempt (webhooks, external callbacks):
`stripe.php`, `webhooks.php`, `auth.php`, `health.php`, `vapi-*.php`, `discord-linked-roles.php`, `composio.php`, `game-subscription.php`, `investor.php`

> **Critical:** `$GLOBALS['CSRF_EXEMPT'] = true;` must be set BEFORE `require_once config.php` in exempt files.

## Database Layer

### Connection

```php
// includes/db-config.inc.php or config/database.php
$pdo = getSharedDB();  // Returns shared PDO instance
```

- **Engine:** MySQL via PDO
- **Character set:** UTF-8mb4
- **Error mode:** `PDO::ERRMODE_EXCEPTION`
- **Transactions:** Supported for atomic operations

### Key Tables

| Table | Purpose |
|-------|---------|
| `clients` | User accounts |
| `alfred_tool_usage` | Tool execution logs |
| `alfred_marketplace_items` | Marketplace listings |
| `comms_messages` | Encrypted message relay |
| `comms_contacts` | Contact lists |
| `comms_groups` | Group chat |
| `user_agents` | AI agent registry |

## Authentication

### Session-Based Auth

```php
// Check via session
$clientId = $_SESSION['client_id'] ?? $_SESSION['uid'] ?? null;
$_SESSION['logged_in'] = true;
```

### Auth API (`api/auth.php`)

12 actions: `login`, `logout`, `register`, `check`, `forgot`, `reset`, `google-login`, `google-callback`, `github-login`, `github-callback`, `verify-2fa`, handled by action parameter.

- **Passwords:** bcrypt (`password_hash` / `password_verify`)
- **2FA:** TOTP support
- **OAuth:** Google configured, GitHub pending
- **Rate limiting:** 10 attempts per 15 minutes per IP

## Caching

- **Redis:** Used for sessions and application caching
- **HTTP Cache:** `Cache-Control` headers per endpoint
- **API caching:** Read-only endpoints return cacheable responses

## Key Subsystems

### Alfred AI Tools

- **1,220+ native tools** in `$TOOL_REGISTRY` array (`api/tools.php`)
- **13,262+ total tools** across 6 providers (native, MCP, Composio, VAPI, marketplace, external)
- **29 categories** (legal, medical, finance, marketing, voice, etc.)

### Veil Communications

- **Zero-knowledge encrypted messaging** (E2E encryption)
- **Server is a dumb relay** — never sees plaintext
- **Features:** 1-to-1 DMs, group chat, WebRTC calls, file sharing, reactions, typing indicators, multi-device, post-quantum crypto (Kyber-1024)
- **API:** `api/comms.php` (15 v1 actions) + `api/comms-v2.php` (30+ v2 actions)

### Voice System

- **Voice commands** via VAPI integration
- **Phone access** for voice-enabled plans
- **Voice cloning** for Enterprise Plus+
- **IVR Builder** for call routing flows

### Agent Fleets

- **Agent management:** Create, configure, and deploy AI agents
- **Fleet orchestration:** Group agents into fleets with specializations
- **Marketplace:** Publish and discover community agents/tools

## Deployment

### Server

- **Host:** `server-15-235-50-60`
- **User:** `gositeme`
- **Root:** `/home/gositeme/domains/gositeme.com/public_html/`
- **PHP:** 8.3.30 (PHP-FPM)
- **Node:** 20+
- **OS:** Linux

### Process Management

- **PM2** for Node.js services (WebSocket, background workers)
- **Configuration:** `ecosystem.config.js`

### Web Server

- **Caddy** as reverse proxy
- **Configuration:** `Caddyfile`
- Auto-HTTPS, HTTP/2, static file serving
- Blocks sensitive file patterns

## Testing

### Test Suite

| Suite | File | Tests | Focus |
|-------|------|-------|-------|
| Smoke | `SmokeTest.php` | 108 | All pages return HTTP 200 |
| Response | `ResponseFormatTest.php` | 47 | API response structure |
| Security | `SecurityTest.php` | 40 | Security headers, XSS, auth |
| Accessibility | `AccessibilityTest.php` | 58 | WCAG, semantic HTML, ARIA |
| API Endpoints | `ApiEndpointTest.php` | 106 | Endpoint availability |
| Performance | `PerformanceTest.php` | 46 | TTFB, page size, compression |
| CSRF | `CsrfEnforcementTest.php` | 17 | CSRF token enforcement |
| Integration | `IntegrationTest.php` | 14 | Cross-component integration |
| Auth | `AuthTest.php` | 29 | Authentication API |
| Tools | `ToolsTest.php` | 34 | Tools API |

**Total: 499 tests, 800+ assertions**

### Running Tests

```bash
# All tests
php vendor/bin/phpunit

# Specific suite
php vendor/bin/phpunit tests/Api/SmokeTest.php

# Rate-limited suites (need pauses)
php vendor/bin/phpunit tests/Api/AuthTest.php    # ~30s
php vendor/bin/phpunit tests/Api/ToolsTest.php   # ~21s
```

## Internationalization

- **Bilingual:** English and French (Quebec law requirement)
- **Implementation:** `includes/lang.php` + per-page `$page_translations` arrays
- **Helper:** `PT('key')` function for translated strings
- **Cookie:** `lang` cookie stores preference (`en` or `fr`)

## Monitoring

- **Health endpoint:** `api/health.php`
- **PM2 monitoring:** `pm2 monit`
- **Error logging:** PHP error logs, structured application logs
- **Status page:** `status.php`
