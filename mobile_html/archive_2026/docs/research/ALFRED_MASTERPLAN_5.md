# ALFRED UPGRADE MASTER PLAN 5 — "PROJECT RESURRECTION"
### From Built to Running — Deploy What Already Exists
### Version 14.0 — June 2025

---

## TABLE OF CONTENTS

1. [Executive Summary — The Deployment Gap](#1-executive-summary)
2. [The Brutal Truth: Built vs Running](#2-built-vs-running)
3. [TIER 0: Emergency Fixes (Do Today)](#3-tier-0-emergency)
4. [TIER 1: Deploy What's Already Built (Week 1-2)](#4-tier-1-deploy)
5. [TIER 2: Wire the Missing Connections (Week 3-4)](#5-tier-2-wire)
6. [TIER 3: New Infrastructure (Month 2)](#6-tier-3-new-infra)
7. [TIER 4: Major Upgrades (Month 3+)](#7-tier-4-major)
8. [The VAPI Monolith — Decomposition Plan](#8-vapi-monolith)
9. [15-Area Upgrade Opportunity Matrix](#9-upgrade-matrix)
10. [Error Triage — What's Actively Broken](#10-error-triage)
11. [Security Audit Results](#11-security-audit)
12. [Deployment Score — Before & After](#12-deployment-score)
13. [Implementation Sequence](#13-implementation-sequence)

---

## 1. EXECUTIVE SUMMARY — THE DEPLOYMENT GAP

**Master Plans 1-4 built a platform. Master Plan 5 turns it on.**

After an exhaustive audit of every file, API, document, log, config, and service in the ecosystem, one finding dominates everything else:

> **The ratio of BUILT to RUNNING is approximately 4:1.**
> For every 4 things built, only 1 is actually deployed and functioning.

This isn't a feature gap. This isn't a code quality problem. This is a **deployment gap** — the single biggest upgrade available is deploying what already exists.

### The Numbers

| Metric | Count | Status |
|--------|-------|--------|
| PHP API files | 132 | 80,075 lines of code |
| Frontend pages | 55 | Built, serving |
| Research/plan documents | 24 | 25,284 lines of documentation |
| VAPI tool cases | 508 | Live via webhook |
| Extended tool definitions | 455+ | **NOT routed through VAPI** |
| Database tables designed | 92+ | **Unknown how many are deployed** |
| Docker services configured | 8 | **ZERO running** |
| Crontab entries | 0 | **Empty — nothing scheduled** |
| Background services (Node.js) | 4 | **ZERO running** |
| Backup script | 1 | **Not in crontab — no backups running** |
| Test files | 0 | **Zero application tests** |
| Billing error rate | ~400/day | **Actively broken** |

### The Verdict

**YES — there is enormous room for upgrade. But the upgrade isn't building more. It's DEPLOYING what's already built.**

The single highest-ROI action isn't writing a single line of new code. It's:
1. Configuring crontab (backups + autonomy heartbeat)
2. Starting Redis (one command)
3. Fixing DirectAdmin API credentials (stops 400 errors/day)
4. Putting Cloudflare in front (free tier)

These four actions would transform the platform more than any amount of new development.

---

## 2. THE BRUTAL TRUTH: BUILT VS RUNNING

### 2.1 What's Actually LIVE Right Now

| Component | Evidence | Health |
|-----------|----------|--------|
| VAPI Voice Webhook | `api/vapi-tools.php` — 508 tool cases | ✅ Working |
| Session Auth | PIN + multi-factor | ✅ Working |
| WHMCS Billing | Full client management | ✅ Working |
| Shield DDoS/Bot | `includes/shield.php` + `.htaccess` | ✅ Working |
| Stripe Payments | Subscriptions, invoicing, checkout | ✅ Working |
| Solana Crypto | 20 tools — DEX swaps, trading, GSM token | ✅ Working |
| Pay System | 674 PHP files, 45 tables, most mature subsystem | ✅ Working |
| PWA | Service worker v3.1, push notifications, offline | ✅ Working |
| SSL/HTTPS | Let's Encrypt via DirectAdmin, HSTS preload | ✅ Working |
| Apache + PHP 8.3 | Serving all 55 pages | ✅ Working |
| PM2 | 5 processes managed | ✅ Working |
| API v1 Rate Limiting | Per-key tiered limits (free/starter/pro/enterprise) | ✅ Working |

### 2.2 What's BUILT But NOT Running

| Component | Lines of Code | What's Needed to Deploy |
|-----------|:---:|---|
| **Docker stack** (Redis, Meilisearch, Ollama, ChromaDB, Caddy) | 188 | Remove Ollama GPU requirement, set real secrets, `docker compose up -d` |
| **WebSocket server** (real-time events) | 722 | `npm install && pm2 start` |
| **Job Queue** (BullMQ async processing) | 496 | Start Redis first, then `pm2 start` |
| **MCP Client** (tool federation) | 668 | `pm2 start` |
| **Discord Bot** (100+ commands, 31 modules) | 361 + modules | `pm2 start` |
| **Backup script** (Restic → B2, 7/4/6 retention) | 141 | Set B2 credentials, add to crontab |
| **Autonomy Cron** (perceive/reason/decide/act heartbeat) | ~200 | Add to crontab: `*/5 * * * *` |
| **Webhook dispatch engine** (HMAC-SHA256) | ~150 | Verify `dispatchWebhook()` called from event sources |
| **Meilisearch** (instant search) | config | Index tools table, create `/api/v1/search` |
| **AI failover cascade** (Groq → Together → OpenAI → Anthropic) | documented | Implement in `callAlfred()` |
| **alfred_schema.sql** (17 tables, FK constraints) | ~400 | `mysql < config/alfred_schema.sql` |

### 2.3 What's NOT Built At All

| Component | Priority | Effort |
|-----------|----------|--------|
| Application tests (PHPUnit/Vitest) | 🔴 Critical | Medium |
| Transactional email (SendGrid integration) | 🔴 Critical | Small |
| CDN (Cloudflare) | 🔴 Critical | Small |
| Mobile native app (iOS/Android) | 🟡 Medium | Large |
| i18n translation framework | 🟡 Medium | Medium |
| Structured data (FAQ/Product schema) | 🟢 Low | Small |
| Multi-currency support | 🟢 Low | Medium |

---

## 3. TIER 0: EMERGENCY FIXES (Do Today)

These are active fires burning right now.

### 3.0.1 — Fix DirectAdmin API Credentials

**Impact:** Stops ~400 errors/day in billing-errors.log

The `billing-errors.log` has 5,768 lines. Three recurring errors all trace back to one root cause: **DirectAdmin API credentials are expired or broken.**

```
Error pattern 1 (60%): Autopilot disk check → Division by zero
  → DA API returns HTML (302 redirect) instead of JSON
  → PHP tries to parse HTML as number → division by zero

Error pattern 2 (25%): DA API returns HTTP 302 / Unauthorized
  → Credentials expired or IP changed

Error pattern 3 (10%): Schema mismatch — missing columns
  → `first_name` column missing from SELECT in service-api.php
  → `da_username` column missing from Autopilot SELECT
```

**Fix:**
1. Log into DirectAdmin admin panel
2. Create new API key or reset credentials
3. Update the config file wherever DA credentials are stored
4. Fix the two SQL queries with missing column references
5. Add response validation: `if (!is_array($response)) { log_error(); return; }`

### 3.0.2 — Remove Hardcoded Secrets from Source Code

**Found:**
- `includes/db-config.inc.php` — hardcoded DB password as fallback
- 4 files contain `sk-` API key patterns (OpenAI/Anthropic keys in source)
- Docker-compose has dev-mode secrets as defaults

**Fix:**
1. Move all secrets to environment variables or a `.env` file outside web root
2. Remove hardcoded fallbacks
3. Add `.env` to `.gitignore`
4. Rotate any exposed API keys immediately

### 3.0.3 — Configure Crontab (Currently EMPTY)

The crontab has **zero entries**. Nothing runs on schedule. Not backups. Not the autonomy heartbeat. Not billing reconciliation.

**Fix — Add these entries:**
```cron
# Alfred Autonomy Heartbeat — every 5 minutes
*/5 * * * * /usr/bin/php /home/gositeme/domains/gositeme.com/public_html/scripts/autonomy-cron.php >> /home/gositeme/domains/gositeme.com/public_html/logs/autonomy.log 2>&1

# Daily backup — 3 AM
0 3 * * * /home/gositeme/domains/gositeme.com/public_html/scripts/backup.sh >> /home/gositeme/domains/gositeme.com/public_html/logs/backup.log 2>&1

# Billing reconciliation (if applicable)
0 */6 * * * /usr/bin/php /home/gositeme/domains/gositeme.com/public_html/pay/cron/billing.php >> /home/gositeme/domains/gositeme.com/public_html/logs/billing-cron.log 2>&1
```

---

## 4. TIER 1: DEPLOY WHAT'S ALREADY BUILT (Week 1-2)

These are fully-built systems that just need to be started.

### 4.1 — Start Redis

**Why:** Unlocks WebSocket pub/sub, job queue, session caching, API response caching, rate limiter backend.

**Current:** Configured in docker-compose.yml (port 6379, maxmemory 512mb, allkeys-lru).

**Options:**
- A: `docker compose up -d redis` (if Docker is available)
- B: Install via package manager: `apt install redis-server` (if Docker isn't viable on shared hosting)
- C: Use a managed Redis instance (Upstash free tier — 10K cmds/day)

**Effort:** 15 minutes

### 4.2 — Start WebSocket Server

**Why:** Enables real-time dashboard updates, live agent activity feeds, chat without polling.

**Current:** 722 lines, production-quality code in `websocket/server.js` with health checks and Redis fallback.

**Steps:**
```bash
cd websocket
npm install
pm2 start server.js --name "alfred-ws" -- --port 3010
```

**Effort:** 30 minutes

### 4.3 — Start Job Queue

**Why:** Enables async webhook delivery, email sending, heavy AI operations without blocking HTTP responses.

**Current:** 496 lines in `websocket/queue.js`, uses BullMQ. Requires Redis.

**Steps:**
```bash
pm2 start websocket/queue.js --name "alfred-queue" -- --port 3011
```

**Effort:** 15 minutes (after Redis is running)

### 4.4 — Deploy Database Schema

**Why:** 17 tables for consciousness, memory, learning, agent registry — the foundation of Alfred's intelligence.

**Current:** `config/alfred_schema.sql` — well-designed with FK constraints, indexes, migration tracking.

**Steps:**
```bash
mysql -u root -p gositeme < config/alfred_schema.sql
```

**Effort:** 5 minutes

### 4.5 — Initialize Backup System

**Why:** Production platform handling payments and crypto with ZERO running backups.

**Current:** `scripts/backup.sh` is complete — Restic → Backblaze B2, 7/4/6 retention policy.

**Steps:**
1. Create Backblaze B2 bucket and application key
2. Create `/etc/alfred/backup.env` with B2 credentials
3. Initialize Restic repository: `restic init`
4. Test: `./scripts/backup.sh --db-only`
5. Add to crontab (see Tier 0)

**Effort:** 1 hour

---

## 5. TIER 2: WIRE THE MISSING CONNECTIONS (Week 3-4)

### 5.1 — Wire SendGrid into Email

**Why:** `toolSendEmail()` currently uses PHP `mail()` — emails likely land in spam. No tracking, no templates, no analytics.

**Current:** `SENDGRID_API_KEY` env var documented in `docs/API_KEYS_SETUP.md`. WHMCS has SendGrid module installed. The env var infrastructure exists — nobody wired it up.

**Fix:** Replace the `mail()` call in `api/vapi-tools.php` `toolSendEmail()` with SendGrid API call. ~20 lines of code.

**Effort:** 1 hour

### 5.2 — Put Cloudflare in Front

**Why:** All assets (VR worlds, downloads, images) served from a single origin server. No edge caching. No DDoS protection beyond Shield. Downloads directory is 559MB served raw.

**Current:** `.htaccess` has proper cache headers. Cloudflare referenced in docs but NOT configured on the domain.

**Steps:**
1. Add domain to Cloudflare (free tier)
2. Update nameservers at registrar
3. Enable "Full (Strict)" SSL mode
4. Turn on Auto Minify, Brotli, Polish (image optimization)
5. Create page rules for `/downloads/*` (cache everything)

**Impact:** Immediate global CDN, DDoS protection layer, ~40% bandwidth reduction, faster page loads worldwide.

**Effort:** 1 hour (plus 24-48h DNS propagation)

### 5.3 — Implement AI Failover Cascade

**Why:** Chat likely hits a single AI provider. If Groq goes down, Alfred goes silent.

**Current:** Three providers have real API calls:
- **Groq** — `api/comms-v2.php` (primary chat)
- **OpenAI** — `editor/api/ai.php` (GoCodeMe editor)
- **Anthropic** — `editor/api/ai.php` (editor fallback)

FAILSAFE_OPERATIONS documents the cascade: Groq → Together → OpenAI → Anthropic → Ollama (local).

**Fix:** Implement try/catch cascade in `callAlfred()` or equivalent function. ~50 lines of code.

**Effort:** 2 hours

### 5.4 — Fix Pay System Schema Mismatches

**Why:** 10% of billing errors are schema column mismatches — queries reference columns that don't exist.

**Fix:**
1. `service-api.php`: Add `first_name` to the services table or fix the SELECT query
2. Autopilot: Add `da_username` column or fix the SELECT
3. `gsm-marketplace.php`: Fix undefined function call

**Effort:** 1 hour

### 5.5 — Index Meilisearch

**Why:** With 1,290+ tools + marketplace items + docs, SQL LIKE queries are slow and inaccurate. Meilisearch is configured in docker-compose but never started or indexed.

**Steps:**
1. Start Meilisearch: `docker compose up -d meilisearch`
2. Create index script to load tools table into Meilisearch
3. Create `/api/v1/search` endpoint
4. Wire frontend search bar to use Meilisearch

**Effort:** 4 hours

---

## 6. TIER 3: NEW INFRASTRUCTURE (Month 2)

### 6.1 — Testing Framework

**Priority:** 🔴 **10/10 — Highest priority new infrastructure**

**Current state:** ZERO tests. The deploy.yml CI pipeline literally says: `"No tests configured yet"`.

A production platform handling real payments, crypto trading, voice calls, and 100+ AI agents has no safety net. Any deployment could break critical financial paths.

**Plan:**
```
Phase 1: API smoke tests (PHPUnit)
  - Auth endpoints (login, verify PIN, session)
  - Payment endpoints (Stripe webhook, subscription create)
  - Core tool dispatch (VAPI webhook routing)
  - 20 tests = 80% critical path coverage

Phase 2: Node.js service tests (Vitest)
  - WebSocket connection/disconnect
  - Job queue enqueue/process
  - MCP tool routing
  - 15 tests

Phase 3: E2E tests (Playwright)
  - User signup → dashboard → first agent call
  - Payment flow → subscription active
  - 5 critical user journeys
```

**Effort:** 1 week for Phase 1 (API smoke tests)

### 6.2 — Application-Level Caching (Redis)

Once Redis is running (Tier 1), add caching layers:

```php
// API response caching — wrap expensive queries
$cache_key = "tools:list:" . md5($query);
$cached = $redis->get($cache_key);
if ($cached) return json_decode($cached);

$result = $pdo->query(/* expensive query */);
$redis->setex($cache_key, 300, json_encode($result)); // 5 min TTL
```

**Targets:**
- `/api/v1/tools` — cache tool list (5 min TTL)
- `/api/v1/agents` — cache agent registry (1 min TTL)
- `/api/v1/marketplace` — cache marketplace listings (10 min TTL)
- Session storage — move from file-based to Redis
- Rate limiter — move from filesystem to Redis

**Effort:** 1 day

### 6.3 — Mobile App via Capacitor

**Why:** PWA is excellent but App Store presence unlocks push notification reliability, biometric auth, and discovery.

**Current:** Android TWA wrapper exists but has placeholder cert fingerprint. No iOS project at all.

**Approach:** Capacitor wraps the existing web app with native capabilities. Fastest path to App Store.

```bash
npx @capacitor/cli init GoSiteMe com.gositeme.app --web-dir=.
npx cap add ios
npx cap add android
```

**Effort:** 1 week to App Store submission

---

## 7. TIER 4: MAJOR UPGRADES (Month 3+)

### 7.1 — i18n Translation Framework

**Current:** EN/FR inline ternaries on ~10 pages. Unmaintainable.

**Fix:** Extract to JSON translation files + helper function:

```php
// includes/i18n.php
function t($key) {
    global $translations, $current_lang;
    return $translations[$current_lang][$key] ?? $translations['en'][$key] ?? $key;
}

// translations/en.json — { "nav.home": "Home", "nav.pricing": "Pricing", ... }
// translations/fr.json — { "nav.home": "Accueil", "nav.pricing": "Tarification", ... }
```

**Effort:** 3 days (extract + refactor all 55 pages)

### 7.2 — Structured Data Expansion

**Current:** Good foundation — JSON-LD on index.php (7 blocks), sitemap with 284 URLs.

**Missing:** FAQ schema, Product schema on pricing, Article schema on blog posts, HowTo schema on tools.

**Impact:** Higher CTR in Google search results, rich snippets, potential knowledge panel.

**Effort:** 2 days

### 7.3 — Apple Pay / Google Pay

**Current:** Stripe handles payments. Apple Pay and Google Pay are configuration flags in Stripe — minimal code change.

```js
// Already have Stripe.js — just enable:
const paymentRequest = stripe.paymentRequest({
  country: 'CA',
  currency: 'cad',
  requestPayerName: true,
  requestPayerEmail: true,
});
```

**Effort:** 4 hours

---

## 8. THE VAPI MONOLITH — DECOMPOSITION PLAN

### The Problem

`api/vapi-tools.php` is a **913KB / 14,216-line single PHP file** containing:

| Metric | Count |
|--------|-------|
| `case` statements | 552 |
| Unique tool cases | 508 |
| Tool functions | 448 |
| AI prompt calls | 608 |
| Database operations | 833 |

**Why it must be split:**
- A single syntax error anywhere in 14,216 lines kills ALL 508 tools
- No developer can navigate or understand a 913KB file
- Merge conflicts on every change
- PHP opcache treats it as one unit — any change invalidates all caching
- ~55% of tools are AI prompt relays that don't need to be in the same file

### The Decomposition Strategy

The Discord bot already demonstrates the right pattern: 28 module files loaded dynamically.

**Proposed structure:**

```
api/
  vapi-tools.php          ← Slim router (~200 lines)
  vapi-tools/
    hosting.php            ← 15 tools: DNS, SSL, domains, crons
    billing.php            ← 20 tools: subscriptions, invoicing, payments
    communication.php      ← 25 tools: email, SMS, calls, fax
    fleet.php              ← 18 tools: agent management, fleet ops
    crypto.php             ← 20 tools: Solana, DEX, trading, GSM token
    legal.php              ← 24 tools: court filings, fax, case mgmt
    database.php           ← 12 tools: MySQL operations
    ai-creative.php        ← 30 tools: image, audio, video generation
    ai-analysis.php        ← 25 tools: research, summarization, analysis
    ai-writing.php         ← 20 tools: blog, social media, copywriting
    consciousness.php      ← 15 tools: memory, learning, personality
    marketplace.php        ← 12 tools: listings, reviews, purchases
    weather.php            ← 8 tools: forecasts, alerts
    devops.php             ← 15 tools: server, monitoring, deployment
    utilities.php          ← remaining tools
```

**Router pattern:**

```php
// api/vapi-tools.php (new slim version)
$tool_map = [
    'get_dns_records' => 'hosting',
    'create_invoice' => 'billing',
    'send_email' => 'communication',
    'deploy_agent' => 'fleet',
    'swap_tokens' => 'crypto',
    // ... 508 mappings
];

$module = $tool_map[$tool_name] ?? 'utilities';
require_once __DIR__ . "/vapi-tools/{$module}.php";
$result = call_user_func("tool_{$tool_name}", $params, $client_id);
```

**Migration strategy:**
1. Create the router + module structure
2. Move 10 tools at a time (start with smallest categories)
3. Test each batch via VAPI voice call
4. Keep original file as fallback until complete
5. Once all tools are migrated, delete the monolith

**Effort:** 1 week (can be done incrementally)

---

## 9. 15-AREA UPGRADE OPPORTUNITY MATRIX

Full audit of 15 previously unexplored areas:

| # | Area | Currently Running? | Upgrade Potential | Effort | Top Action |
|---|------|:--:|:--:|:--:|---|
| 1 | **Testing** | ❌ Zero tests | **10/10** | Medium | PHPUnit for API endpoints |
| 2 | **Backups** | ❌ Script exists, not running | **10/10** | Small | Crontab + B2 credentials |
| 3 | **Mobile App** | ❌ TWA placeholder | **9/10** | Large | Capacitor wrapper |
| 4 | **Email** | ⚠️ `mail()` only | **9/10** | Small | Wire SendGrid API key |
| 5 | **CDN/Media** | ❌ No CDN | **9/10** | Small | Cloudflare free tier |
| 6 | **Meilisearch** | ❌ Config only | **8/10** | Medium | Start + index tools |
| 7 | **Caching** | ⚠️ Browser only | **8/10** | Small | Start Redis + query caching |
| 8 | **AI Models** | ✅ 3 providers | **7/10** | Medium | Failover cascade |
| 9 | **i18n** | ⚠️ EN/FR inline | **7/10** | Medium | JSON translation files |
| 10 | **SEO** | ✅ Good foundation | **6/10** | Small | FAQ/Product schema |
| 11 | **Rate Limiting** | ✅ Working | **5/10** | Small | Move to Redis backend |
| 12 | **Payments** | ✅ Stripe + Solana | **5/10** | Small | Apple Pay/Google Pay |
| 13 | **Webhooks** | ✅ Built | **4/10** | Small | Verify events fire |
| 14 | **SSL/TLS** | ✅ Working | **3/10** | Tiny | OCSP stapling |
| 15 | **PWA** | ✅ Excellent | **3/10** | Tiny | Web Share Target |

---

## 10. ERROR TRIAGE — WHAT'S ACTIVELY BROKEN

### 10.1 billing-errors.log — 5,768 Lines

**Error rate:** ~400+ new errors per day

| Error Category | % | Root Cause | Fix |
|---|:--:|---|---|
| Autopilot disk check division by zero | 60% | DA API returns HTML redirect instead of JSON | Validate API response format before parsing |
| DirectAdmin API 302/Unauthorized | 25% | API credentials expired or IP whitelisting changed | Refresh DA credentials |
| Schema column mismatch (`first_name`, `da_username`) | 10% | SQL queries reference non-existent columns | Fix SELECT queries or run ALTER TABLE |
| Other (misc) | 5% | Various edge cases | Review individually |

**Single fix that resolves 85%:** Refresh DirectAdmin API credentials.
**Second fix that resolves 10%:** Fix two SQL queries.

### 10.2 Silent Failures (No Errors, Just Not Running)

| System | Expected State | Actual State |
|--------|----------------|--------------|
| Autonomy heartbeat | Running every 5 min | Not in crontab |
| Backups | Daily at 3 AM | Not in crontab |
| Redis | Running on :6379 | Not started |
| WebSocket | Running on :3010 | Not started |
| Job Queue | Running on :3011 | Not started |
| MCP Client | Running on :3005 | Not started |
| Discord Bot | Running | Not started |
| Meilisearch | Running on :7700 | Not started |
| Docker stack | 8 services | Never deployed |

---

## 11. SECURITY AUDIT RESULTS

### 11.1 Findings

| Severity | Finding | Location | Status |
|----------|---------|----------|--------|
| 🔴 HIGH | Hardcoded DB password in source | `includes/db-config.inc.php` | Fix immediately |
| 🔴 HIGH | 4 files with `sk-` API keys in source | Various | Rotate keys, use env vars |
| 🟡 MEDIUM | Docker-compose dev secrets as defaults | `docker-compose.yml` | Set real secrets before deploy |
| 🟢 LOW | No OCSP stapling | Apache config | Minor improvement |

### 11.2 What's Good

| Area | Status |
|------|--------|
| SQL Injection | ✅ 116 files use PDO prepared statements — LOW risk |
| XSS | ✅ Shield provides bot/scraper protection |
| HTTPS | ✅ HSTS preload, Let's Encrypt auto-renewal |
| Rate Limiting | ✅ Multi-layer: Shield + mod_evasive + API v1 per-key limits |
| Auth | ✅ PIN + multi-factor, session management |
| DDoS | ✅ Shield + mod_evasive (page: 2/sec, site: 50/sec) |
| CORS | ✅ Configured in API endpoints |
| Webhook Security | ✅ HMAC-SHA256 signatures |

**Overall security posture:** GOOD on application layer, POOR on secrets management.

---

## 12. DEPLOYMENT SCORE — BEFORE & AFTER

### Current State: Deployment Score **3.2 / 10**

| Category | Score | Why |
|----------|:--:|---|
| Core web serving | 9/10 | Apache, PHP, MySQL, SSL all working |
| Payment processing | 8/10 | Stripe + Solana live, pay system mature |
| Voice AI | 7/10 | VAPI webhook dispatching 508 tools |
| Background services | 0/10 | Nothing running (Redis, WS, queue, Discord, MCP) |
| Scheduled tasks | 0/10 | Empty crontab |
| Data protection | 1/10 | No running backups |
| Testing | 0/10 | Zero tests |
| Search | 0/10 | No search engine running |
| Email deliverability | 2/10 | Raw `mail()` |
| CDN/performance | 1/10 | No CDN, no Redis caching |

**Weighted average: 3.2/10** (heavily penalized by zero scores in critical areas)

### After Tier 0-2 (First Month): Projected Score **7.5 / 10**

| Category | Before | After | Change |
|----------|:--:|:--:|---|
| Core web serving | 9 | 9 | (unchanged) |
| Payment processing | 8 | 9 | Schema fixes, error rate → 0 |
| Voice AI | 7 | 7 | (unchanged until monolith split) |
| Background services | 0 | 8 | Redis + WS + queue + MCP started |
| Scheduled tasks | 0 | 8 | Crontab configured |
| Data protection | 1 | 8 | Backups running, secrets in env vars |
| Testing | 0 | 4 | API smoke tests (PHPUnit Phase 1) |
| Search | 0 | 7 | Meilisearch indexed |
| Email deliverability | 2 | 8 | SendGrid wired |
| CDN/performance | 1 | 8 | Cloudflare + Redis caching |

**Weighted average: 7.5/10** — a 2.3x improvement with mostly DEPLOYMENT work, not development.

---

## 13. IMPLEMENTATION SEQUENCE

### Week 0 (Today) — TIER 0: Emergency

```
□ Fix DirectAdmin API credentials (stops 400 errors/day)
□ Move hardcoded secrets to .env file
□ Rotate exposed API keys
□ Configure crontab (backups + autonomy heartbeat)
```

### Week 1 — TIER 1: Start Services

```
□ Start Redis (docker or native)
□ Deploy alfred_schema.sql to MySQL
□ Start WebSocket server via PM2
□ Start Job Queue via PM2
□ Initialize backup system (B2 credentials + Restic init)
□ Test backup: ./scripts/backup.sh --db-only
```

### Week 2 — TIER 1 continued + TIER 2 begins

```
□ Start MCP Client via PM2
□ Wire SendGrid into toolSendEmail()
□ Add Cloudflare to domain (free tier)
□ Fix pay system schema mismatches
□ Start Meilisearch + index tools table
```

### Week 3-4 — TIER 2: Wire Connections

```
□ Implement AI failover cascade
□ Create /api/v1/search endpoint
□ Wire frontend search to Meilisearch
□ Move rate limiter to Redis backend
□ Add Redis session storage
□ Add Redis API response caching
```

### Month 2 — TIER 3: New Infrastructure

```
□ PHPUnit API smoke tests (20 tests, critical paths)
□ Vitest Node.js service tests (15 tests)
□ Begin VAPI monolith decomposition (10 tools/batch)
□ Capacitor mobile app init
□ OPcache tuning
```

### Month 3+ — TIER 4: Major Upgrades

```
□ Complete VAPI monolith split
□ i18n translation framework
□ Structured data expansion (FAQ/Product/Article schema)
□ Apple Pay / Google Pay
□ E2E Playwright tests (5 critical journeys)
□ Mobile app submission to App Store / Play Store
```

---

## APPENDIX A: ECOSYSTEM SIZE INVENTORY

| Component | Size | Files |
|-----------|------|-------|
| Workspace total | 9.5 GB | — |
| gocodeme (VS Code fork) | 3.9 GB | — |
| gocodeme-editor (Void fork) | 2.9 GB | — |
| downloads | 559 MB | — |
| whmcs | 492 MB | — |
| quickqr | 85 MB | — |
| pay system | 8.4 MB | 674 PHP files |
| ai-servers configurator | 3.2 MB | — |
| PHP API files | — | 132 files, 80,075 lines |
| Frontend pages | — | 55 PHP files |
| VR worlds | — | 14 experiences, 26,892 lines |
| Research documents | — | 24 files, 25,284 lines |
| Database tables designed | — | 92+ across all schemas |

## APPENDIX B: CROSS-REFERENCE TO PREVIOUS MASTERPLANS

| Document | Codename | Focus | Relationship |
|----------|----------|-------|---|
| MASTERPLAN 1 | Project Sentience | Build the Product | Built 1,290+ tools, consciousness layer |
| MASTERPLAN 2 | Project Ignition | Wire the Infrastructure | Created callAlfred(), PM2, schema |
| MASTERPLAN 3 | Project Phoenix | Monetize & Scale | Developer API, enterprise, mobile |
| MASTERPLAN 4 | Project Sovereignty | True Autonomy | 7 pillars, 100-agent hierarchy, metaverse |
| **MASTERPLAN 5** | **Project Resurrection** | **Deploy What's Built** | **Turns on everything Plans 1-4 created** |
| ORDER_BROTHERHOOD | Project Dawn | Identity & Growth | Essence Card, 33 Degrees, Garden Economy |
| FAILSAFE_OPERATIONS | — | Disaster Recovery | SRE, incident response, compliance |
| AUTONOMY_METAVERSE | Project Kingdom | VR Districts | Metaverse worlds, KGD economy |

---

*Generated from exhaustive audit: 132 API files, 55 pages, 24 documents, 9.5 GB workspace, every log file, every config, every Docker service, every script, every crontab entry (zero), and every error log.*

*The biggest upgrade is not building more. It's deploying what already exists.*
