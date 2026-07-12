# GoCodeMe Middleware — Complete Audit Report

**Date:** 2025-07-10  
**Scope:** `/gocodeme/middleware/src/` — every source file  
**Method:** Line-by-line read of all 50+ source files  

---

## Table of Contents

1. [Architecture Overview](#1-architecture-overview)
2. [Directory Tree](#2-directory-tree)
3. [Infrastructure Files](#3-infrastructure-files)
4. [Route Files — Complete Endpoint Catalog](#4-route-files--complete-endpoint-catalog)
5. [Support Modules](#5-support-modules)
6. [Authentication & Authorization Matrix](#6-authentication--authorization-matrix)
7. [Security Issues](#7-security-issues)
8. [Bugs & Logic Errors](#8-bugs--logic-errors)
9. [Dead Code & Redundancy](#9-dead-code--redundancy)
10. [Performance Concerns](#10-performance-concerns)
11. [Redis Key Map](#11-redis-key-map)
12. [External Service Dependencies](#12-external-service-dependencies)
13. [Background Processes & Crons](#13-background-processes--crons)
14. [Recommendations](#14-recommendations)

---

## 1. Architecture Overview

```
Browser ──HTTPS──► Apache (443/80)
                     │
                     ├──► PHP pages (voice.php, dashboard.php, etc.)
                     │
                     └── ProxyPass /middleware/ ──► Express.js (127.0.0.1:3001)
                                                      │
                          ┌───────────────────────────┤
                          │                           │
                     Theia IDE ◄── WS/HTTP ──► ideProxy.js     ──► localhost:4000+
                     OpenHands Agent ◄── WS ──► ideProxy.js    ──► localhost:4001+
                          │                           │
                     Claude API ◄── HTTPS ──► anthropicProxy.js ──► api.anthropic.com
                                              │                     api.together.xyz
                                              │                     api.openai.com
                                              │                     generativelanguage.googleapis.com
                          │                   │
                     WHMCS ◄── HTTP ──► billing/* ──► WHMCS API
                          │                   │
                     DirectAdmin ◄── HTTP ──► directadmin/* ──► DA API (port 2222)
                          │                   │
                     Redis ◄── TCP ──► ioredis (127.0.0.1:6379)
                          │                   │
                     OpenClaw ◄── HTTP ──► openclaw/* ──► localhost:3004
                          │                   │
                     Voice Server ◄── WSS ──► voiceRelay ──► localhost:3006
                          │                   │
                     MCP Server ◄── HTTP ──► autopilotProxy ──► mcp-server (browser sessions)
                          │
                  MCP ELEPHANT ◄── HTTP ──► localhost:3005 (memory context)
```

**Stack:** Express 4.18.2 · Node.js ≥18 · Redis (ioredis 5.3.2) · JWT · PM2 · Winston  
**Listens:** `127.0.0.1:3001` (loopback only — Apache terminates TLS)  
**Body limit:** 50 MB (for base64 images in AI conversations)  

---

## 2. Directory Tree

```
middleware/src/
├── server.js                (285 lines)  — Express app, route registration, background crons
├── config.js                (88 lines)   — Centralized env-based configuration
├── logger.js                (24 lines)   — Winston logger (console + file transports)
├── redis.js                 (32 lines)   — Singleton ioredis client with retry
├── package.json                          — Dependencies & scripts
├── .env.example                          — Environment variable template
│
├── routes/
│   ├── sso.js               (124 lines)  — WHMCS SSO login/exchange
│   ├── tokens.js            (186 lines)  — Token usage tracking & provisioning
│   ├── files.js             (122 lines)  — File CRUD on DirectAdmin filesystem
│   ├── git.js               (216 lines)  — Git operations (commit, revert, clone)
│   ├── launch.js            (268 lines)  — IDE + Agent session spawning
│   ├── anthropicProxy.js    (1659 lines) — ★ LARGEST: Transparent AI proxy with budget guards
│   ├── ideProxy.js          (1840 lines) — ★ SECOND LARGEST: IDE/Agent HTTP+WS proxy + Alfred widget
│   ├── access.js            (119 lines)  — WHMCS access lifecycle (activate/suspend/terminate)
│   ├── admin.js             (234 lines)  — Admin dashboard (status, budget, costs)
│   ├── openclaw.js          (177 lines)  — OpenClaw messaging integration
│   ├── billing.js           (117 lines)  — Billing reports, invoices, alerts
│   ├── onboarding.js        (146 lines)  — 5-step onboarding wizard
│   ├── claude.js            (183 lines)  — Direct Claude SDK chat endpoint
│   ├── da.js                (121 lines)  — DirectAdmin SSO via temp-password
│   ├── hosting.js           (398 lines)  — Full DA management API (DB, DNS, email, SSL, cron, backups)
│   ├── usage.js             (293 lines)  — Usage dashboard data API
│   ├── aiTerminal.js        (185 lines)  — NL → shell command translator (Haiku)
│   ├── templates.js         (212 lines)  — Workspace project templates
│   ├── referral.js          (225 lines)  — Referral program (give/get 100K tokens)
│   ├── teams.js             (598 lines)  — Team/org plans with shared token pools
│   ├── developer.js         (557 lines)  — Developer API keys + OpenAI-compatible endpoint
│   ├── reseller.js          (612 lines)  — White-label reseller program
│   ├── extras.js            (527 lines)  — Dashboard extras (notes, snippets, deploy, marketplace, achievements)
│   ├── alfred.js            (1088 lines) — Alfred ↔ IDE bridge (40 tool endpoints)
│   ├── voiceRelay.js        (287 lines)  — REST↔WebSocket bridge for voice
│   └── autopilotProxy.js    (280 lines)  — Autopilot browser session bridge
│
├── auth/
│   ├── middleware.js         (80 lines)   — requireSession, requireOwnResource
│   └── sso.js               (155 lines)  — JWT validation, issueSessionToken, checkSubscription
│
├── billing/
│   ├── alerts.js            (170 lines)  — Token alert engine (50%/80%/100%)
│   ├── emailAutomation.js   (662 lines)  — Welcome series, winback, upgrade, weekly digest
│   ├── whmcs.js             (165 lines)  — WHMCS API thin wrapper
│   ├── pricing.js           (320 lines)  — Per-model token pricing (30+ models)
│   ├── modelRouter.js       (703 lines)  — Smart model routing ("Auto" mode)
│   └── formatTranslator.js  (423 lines)  — Anthropic ↔ OpenAI format translation
│
├── tokens/
│   ├── tokenCounter.js      (210 lines)  — Per-customer usage tracking + overage
│   ├── tokenBudget.js       (363 lines)  — 5-layer budget protection system
│   └── usageTracker.js      (290 lines)  — Granular per-model, per-day tracking
│
├── directadmin/
│   ├── fileManager.js                    — File operations via DA API
│   ├── databaseManager.js               — MySQL database CRUD
│   ├── domainManager.js                  — Domain/subdomain management
│   ├── emailManager.js                   — Email accounts, forwarders, autoresponders
│   ├── dnsManager.js                     — DNS record management
│   ├── sslManager.js                     — Let's Encrypt SSL provisioning
│   ├── cronManager.js                    — Cron job CRUD
│   ├── backupManager.js                  — Backup list/create/restore
│   ├── statsManager.js                   — Usage statistics
│   ├── syncWorkspace.js                  — DA→local workspace file sync
│   ├── userLookup.js                     — WHMCS→DA username resolution
│   └── client.js                         — HTTP client for DA API
│
├── monitoring/
│   └── healthMonitor.js     (289 lines)  — Self-ping, Redis hygiene, memory alerts
│
├── openclaw/
│   └── sender.js            (70 lines)   — Push messages to linked channels
│
├── git/
│   └── worker.js            (233 lines)  — Debounced git operations
│
├── test/
│   └── da-api-test.js                    — DirectAdmin API test script
│
└── public/
    ├── dashboard.html                    — Dashboard SPA
    └── usage.html                        — Usage dashboard SPA
```

**Total source lines (estimated):** ~12,500+ lines across 50+ files.

---

## 3. Infrastructure Files

### server.js (285 lines)
- Registers 24 route modules + 2 proxy handlers + 1 WebSocket upgrader
- Global rate limiter: **180 req/min** per user (JWT-based key), plan-aware on anthropic-proxy
- Body parser: **50 MB** limit
- Helmet, CORS (`*`), Morgan HTTP logging
- **Background processes:** idle session reaper (5 min interval, 30 min timeout), 4 email cron jobs
- Graceful shutdown on `SIGTERM`/`SIGINT`
- Static serves: `/dashboard` → dashboard.html, `/usage` → usage.html, `/public` → static assets
- Health endpoint: `GET /health`

### config.js (88 lines)
- Validates required env vars in `NODE_ENV=production`
- Default JWT secret: `'changeme'` (caught by validation only in production)
- Default model: `claude-sonnet-4-6`
- Token limits: Free=50K, Builder=300K, Professional=600K, Studio=1.5M, Business=3M, Enterprise=5M

### redis.js (32 lines)
- Singleton ioredis client, `maxRetriesPerRequest: 3`
- Reconnect strategy: 2s base delay
- `enableReadyCheck: true`

### logger.js (24 lines)
- Console (colorized) + file transports (`error.log`, `combined.log`)
- Default level: `info` (overridable by `LOG_LEVEL` env)

---

## 4. Route Files — Complete Endpoint Catalog

### 4.1 sso.js — `/api/sso/*` (124 lines)

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `POST` | `/login` | None | Validate WHMCS SSO token → return session JWT |
| `GET` | `/me` | `requireSession` | Return current session info |
| `ALL` | `/exchange` | None | Exchange short-lived SSO JWT for full session |
| `POST` | `/generate` | `requireWhmcsSecret` | Generate SSO token for WHMCS→IDE redirect |

### 4.2 tokens.js — `/api/tokens/*` (186 lines)

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `GET` | `/usage` | Bearer or X-WHMCS-Secret | Get token usage stats |
| `POST` | `/report` | `requireSession` | Report token usage after Claude call |
| `POST` | `/provision` | `requireWhmcsSecret` | WHMCS webhook: set token limit |
| `POST` | `/reset` | `requireWhmcsSecret` | WHMCS webhook: reset monthly counter |
| `POST` | `/topup` | `requireWhmcsSecret` | WHMCS webhook: add bonus tokens |
| `GET` | `/mode` | `requireSession` | Get user's model mode |
| `POST` | `/mode` | `requireSession` | Set user's model mode |
| `POST` | `/mode/admin` | `requireWhmcsSecret` | Admin override model mode |

### 4.3 files.js — `/api/files/:username/*` (122 lines)

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `GET` | `/` | `requireSession` + `requireOwnResource` | List directory |
| `GET` | `/read` | `requireSession` + `requireOwnResource` | Read file |
| `POST` | `/` | `requireSession` + `requireOwnResource` | Write/create file |
| `DELETE` | `/` | `requireSession` + `requireOwnResource` | Delete file/directory |
| `PATCH` | `/rename` | `requireSession` + `requireOwnResource` | Rename/move file |
| `GET` | `/stat` | `requireSession` + `requireOwnResource` | Stat file |
| `POST` | `/mkdir` | `requireSession` + `requireOwnResource` | Create directory |

Auto-git-commit on write/delete/rename/mkdir.

### 4.4 git.js — `/api/git/:username/*` (216 lines)

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `POST` | `/checkpoint` | `requireSession` + `requireOwnResource` | Stage + commit all changes |
| `POST` | `/revert` | `requireSession` + `requireOwnResource` | Revert HEAD commit |
| `GET` | `/log` | `requireSession` + `requireOwnResource` | Recent commit log (max 100) |
| `GET` | `/status` | `requireSession` + `requireOwnResource` | Working tree status |
| `GET` | `/diff` | `requireSession` + `requireOwnResource` | Diff HEAD vs working tree |
| `GET` | `/review` | `requireSession` + `requireOwnResource` | Multi-file change summary |
| `POST` | `/init` | `requireSession` + `requireOwnResource` | Initialize git repo |
| `POST` | `/clone` | `requireSession` + `requireOwnResource` | Clone public repo (HTTPS only) |

Clone URL allowlist: `github.com`, `gitlab.com`, `bitbucket.org`.

### 4.5 launch.js — `/api/launch/*` (268 lines)

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `POST` | `/` | `requireSession` + `requireActiveAccess` | Start Theia IDE + OpenHands Agent |
| `GET` | `/sessions` | `requireSession` | List active sessions |
| `POST` | `/stop` | `requireSession` | Kill all user sessions |

Port allocation: scans from 4000, finds free pair (Theia + Agent). Sessions in Redis with 24h TTL.

### 4.6 anthropicProxy.js — `/api/anthropic-proxy/:daUsername/v1/*` (1659 lines)

**The most critical and complex file.** Transparently proxies all Theia IDE → Anthropic API traffic.

| Feature | Detail |
|---------|--------|
| **Budget guards** | 5-layer: global monthly USD cap, global daily USD cap, per-user daily USD cap, per-request size guard, monthly token allowance (output-only) |
| **Deduplication** | MD5 hash of client + last 2 messages, 30s cache, 200 entries max |
| **Model routing** | Auto-routes simple tasks to Together.ai (cheap), complex to Sonnet |
| **Conversation pruning** | Keeps first + last N messages, drops middle with context summary |
| **Tool result truncation** | Caps older tool results at 12K chars |
| **Image stripping** | Removes base64 images from messages older than last 6 |
| **Dynamic tool filtering** | Only sends ~80 core tools instead of all 218+ Theia tools |
| **Tool description compression** | Caps at 300 chars, removes examples |
| **System prompt compression** | Strips markdown decorators, collapses whitespace |
| **Prompt caching** | Marks system/tools/first-message with `cache_control` breakpoints |
| **Extended thinking guard** | Strips thinking budget unless `ALLOW_EXTENDED_THINKING=true` |
| **Custom AI instructions** | Reads `.gocodeme/ai-instructions.md` from workspace |
| **Memory context** | Fetches from MCP ELEPHANT engine at `localhost:3005` |
| **Tiered output caps** | simple→4096, moderate→8192, complex→16384 |
| **Multi-provider** | Anthropic (native SSE) + OpenAI-format (Together.ai) with format translation |
| **Automatic fallback** | Falls back to Sonnet on Together.ai failure (15s timeout) |
| **Full billing** | Per-model cost, cache savings, routing savings vs Sonnet |
| **Overage** | $2/100K at 200% hard cap |

### 4.7 ideProxy.js — `/ide/:port/*` + `/agent/:port/*` (1840 lines)

| Feature | Detail |
|---------|--------|
| **HTTP proxy** | Forwards to `localhost:<port>` for IDE and Agent |
| **WebSocket** | Full WS upgrade handler for both IDE and Agent connections |
| **Port validation** | Only 4000-4040, must have active Redis session |
| **Activity tracking** | Throttled to 1/min per port for idle reaper |
| **HTML injection** | Full Alfred AI widget (CSS+HTML+JS) injected into Theia IDE responses |
| **Alfred widget** | Chat panel, voice recording (WebM), TTS playback, model selector, token usage display, settings panel |
| **Dual-Agent Orchestration** | Relay between Widget Alfred (voice/chat) and IDE Alfred (Theia AI Chat) |
| **Relay modes** | Collab, Consensus (Jaccard), Delegate (keyword-based auto-route) |
| **Agent proxy** | Injects `<base>` tag + rewrites `/assets/` paths for OpenHands |
| **Voice WS proxy** | `/voice-ws` → `wss://127.0.0.1:3006` |
| **502 pages** | Auto-retry loading screens every 2s |

### 4.8 access.js — `/api/access/*` (119 lines)

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `POST` | `/activate` | `requireWhmcsSecret` | Enable customer access |
| `POST` | `/suspend` | `requireWhmcsSecret` | Suspend access (payment failed) |
| `POST` | `/unsuspend` | `requireWhmcsSecret` | Restore access |
| `POST` | `/terminate` | `requireWhmcsSecret` | Permanently revoke access |
| `POST` | `/sso/generate` | `requireWhmcsSecret` | Generate SSO token (duplicate of sso.js) |

### 4.9 admin.js — `/api/admin/*` (234 lines)

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `GET` | `/status` | `requireWhmcsSecret` | System status (all users, sessions, PM2, Redis, memory) |
| `POST` | `/kill-session` | `requireWhmcsSecret` | Kill user IDE sessions |
| `GET` | `/budget` | `requireWhmcsSecret` | Global spend summary + limits |
| `POST` | `/budget/reset-breaker` | `requireWhmcsSecret` | Reset global circuit breaker |
| `GET` | `/budget/user/:clientId` | `requireWhmcsSecret` | Per-user budget summary |
| `GET` | `/costs` | `requireWhmcsSecret` | Cost/revenue dashboard |

### 4.10 openclaw.js — `/api/openclaw/*` (177 lines)

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `GET` | `/status` | `requireSession` | Combined stats + linked channels |
| `GET` | `/link-token` | `requireSession` | Issue short-lived JWT for channel linking |
| `POST` | `/link` | None (token is credential) | Validate link token, store mapping |
| `GET` | `/stats/:daUsername` | `requireSession` + own | Conversation stats |
| `GET` | `/links/:daUsername` | `requireSession` + own | List linked channels |
| `POST` | `/send` | `requireSession` + admin | Push message to channel user |

### 4.11 billing.js — `/api/billing/*` (117 lines)

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `GET` | `/usage-report` | `requireSession` | Complete billing picture |
| `GET` | `/invoices` | `requireSession` | WHMCS invoices (graceful degradation) |
| `POST` | `/invoice` | `requireWhmcsSecret` | Create overage invoice |
| `POST` | `/reset-alerts` | `requireWhmcsSecret` | Reset alert dedup flags |
| `GET` | `/whmcs-client` | `requireSession` | Raw WHMCS client details |

### 4.12 onboarding.js — `/api/onboarding/*` (146 lines)

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `GET` | `/status` | `requireSession` | Current onboarding step + metadata |
| `POST` | `/advance` | `requireSession` | Advance 1 step (auto-completes at step 4) |
| `POST` | `/complete` | `requireSession` | Mark wizard done |
| `POST` | `/reset` | `requireSession` | Dev/support reset |

5-step flow: Welcome → Workspace → Messaging → First Task → Done.

### 4.13 claude.js — `/api/claude/*` (183 lines)

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `POST` | `/chat` | `requireSession` + `checkAllowance` | Direct Claude API call (streaming + non-streaming) |
| `GET` | `/models` | `requireSession` | List available models |

Uses `@anthropic-ai/sdk` (not raw HTTPS like anthropicProxy).

### 4.14 da.js — `/api/da/*` (121 lines)

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `POST` | `/login-key` | `requireWhmcsSecret` | Generate one-time DA login URL via temp-password SSO |

### 4.15 hosting.js — `/api/hosting/*` (398 lines)

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `GET/POST/DELETE` | `/databases/*` | `requireSession` + `requireDaUser` | MySQL CRUD |
| `GET` | `/domains` | `requireSession` + `requireDaUser` | List domains |
| `GET/POST/DELETE` | `/subdomains/*` | `requireSession` + `requireDaUser` | Subdomain CRUD |
| `GET/POST/DELETE/PATCH` | `/email/*` | `requireSession` + `requireDaUser` | Email account management |
| `GET/POST/DELETE` | `/dns/*` | `requireSession` + `requireDaUser` | DNS records |
| `POST/GET` | `/ssl/*` | `requireSession` + `requireDaUser` | Let's Encrypt SSL |
| `GET/POST/DELETE` | `/cron/*` | `requireSession` + `requireDaUser` | Cron jobs |
| `GET/POST` | `/backups/*` | `requireSession` + `requireDaUser` | Backup management |
| `GET` | `/stats/*` | `requireSession` + `requireDaUser` | Usage statistics |

### 4.16 usage.js — `/api/usage/*` (293 lines)

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `GET` | `/summary` | `requireSession` | Complete usage dashboard |
| `GET` | `/daily` | `requireSession` | Daily breakdown (30 days or specific month) |
| `GET` | `/models` | `requireSession` | Model-level token breakdown |
| `GET` | `/history` | `requireSession` | Recent request history |
| `GET` | `/pricing` | None | Public model pricing + plans |
| `GET` | `/cost-estimate` | None | Calculate cost for model+tokens |
| `GET` | `/available-models` | None | Full model catalog for UI |
| `GET` | `/current-model` | `requireSession` | User's current model preference |
| `POST` | `/set-model` | `requireSession` | Set model preference |

### 4.17 aiTerminal.js — `/api/ai-terminal/*` (185 lines)

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `POST` | `/translate` | `requireSession` | NL → bash command (Haiku) |
| `POST` | `/explain` | `requireSession` | Explain a command |

Uses raw HTTPS to Anthropic (not SDK). Tracks usage against user's plan.

### 4.18 templates.js — `/api/templates/*` (212 lines)

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `GET` | `/` | None | List available templates |
| `POST` | `/apply` | `requireSession` | Apply template to workspace |

Templates: Next.js, React+Vite, Vue+Vite, Express API, Python Flask, Static HTML, Laravel, WordPress Theme.

### 4.19 referral.js — `/api/referral/*` (225 lines)

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `GET` | `/code` | `requireSession` | Get/create referral code |
| `POST` | `/custom-code` | `requireSession` | Set custom referral code |
| `GET` | `/stats` | `requireSession` | Referral statistics |
| `POST` | `/claim` | `requireWhmcsSecret` | Claim referral reward (webhook) |
| `GET` | `/validate/:code` | None | Validate referral code (public) |

Reward: 100K tokens for both referrer and referred. Max 50 referrals per user.

### 4.20 teams.js — `/api/teams/*` (598 lines)

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `POST` | `/` | `requireSession` | Create team |
| `GET` | `/` | `requireSession` | Get team info + members |
| `POST` | `/invite` | `requireSession` + admin | Invite member by email |
| `POST` | `/accept` | `requireSession` | Accept invitation |
| `DELETE` | `/member/:clientId` | `requireSession` + admin/self | Remove member |
| `PATCH` | `/member/:clientId` | `requireSession` + admin | Update member settings |
| `GET` | `/usage` | `requireSession` | Team usage breakdown |
| `POST` | `/provision` | `requireWhmcsSecret` | WHMCS webhook: provision team |

Plans: Team 5 ($149, 2M pool), Team 10 ($279, 5M), Team 25 ($599, 15M).
Exports: `checkTeamAllowance()`, `addTeamUsage()` for use in anthropicProxy.

### 4.21 developer.js — `/api/developer/*` (557 lines)

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `POST` | `/keys` | `requireSession` | Create API key (`gcm_` prefix) |
| `GET` | `/keys` | `requireSession` | List keys (hashed) |
| `DELETE` | `/keys/:keyId` | `requireSession` | Revoke key |
| `POST` | `/keys/:keyId/rotate` | `requireSession` | Rotate key |
| `GET` | `/usage` | `requireSession` | API usage stats |
| `POST` | `/v1/chat/completions` | API key (Bearer `gcm_xxx`) | OpenAI-compatible chat endpoint |

Keys: SHA-256 hashed, `gcm_` + 48 hex chars. Max 10 per account. 1-year TTL.
Sliding window rate limit per key (60/200/500 req/min by plan).
Exports: `validateApiKey()`.

### 4.22 reseller.js — `/api/reseller/*` (612 lines)

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `POST` | `/provision` | `requireWhmcsSecret` | Provision reseller plan |
| `GET` | `/` | `requireSession` + `requireReseller` | Dashboard info |
| `PUT` | `/branding` | `requireSession` + `requireReseller` | Update branding |
| `POST` | `/accounts` | `requireSession` + `requireReseller` | Create sub-account |
| `GET` | `/accounts` | `requireSession` + `requireReseller` | List sub-accounts |
| `PATCH` | `/accounts/:id` | `requireSession` + `requireReseller` | Update sub-account |
| `DELETE` | `/accounts/:id` | `requireSession` + `requireReseller` | Remove sub-account |
| `POST` | `/accounts/bulk` | `requireSession` + `requireReseller` | Bulk create (max 50) |
| `GET` | `/revenue` | `requireSession` + `requireReseller` | Revenue report |

Plans: Bronze ($399, 10M, 10 accounts), Silver ($899, 30M, 25), Gold ($2499, 100M, 100).
Exports: `getResellerBranding()`.

### 4.23 extras.js — `/api/extras/*` (527 lines)

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `GET/POST` | `/notes` | `requireSession` | Quick notes (50KB max) |
| `GET/POST/DELETE` | `/snippets` | `requireSession` | Code snippets (100 max) |
| `GET` | `/activity` | `requireSession` | Activity feed (last 50 events) |
| `GET` | `/achievements` | `requireSession` | Gamification badges (12 types) |
| `GET/POST/DELETE` | `/webhooks` | `requireSession` | User webhooks (20 max) |
| `POST` | `/deploy` | `requireSession` | One-click deploy (rsync to public_html) |
| `GET` | `/health-score` | `requireSession` | Project health check (8 criteria) |
| `GET` | `/marketplace` | `requireSession` | npm package search |
| `POST` | `/marketplace/install` | `requireSession` | Install npm package |
| `POST` | `/generate-image` | `requireSession` | DALL-E 3 image generation |

Exports: `logActivity()`, `unlockAchievement()`, `fireWebhooks()`.

### 4.24 alfred.js — `/api/alfred/*` (1088 lines)

All endpoints: `POST`, auth: `requireWhmcsSecret` (X-WHMCS-Secret header).

| Endpoint | Description |
|----------|-------------|
| `/ide-status` | Check active IDE sessions |
| `/launch-ide` | Start IDE session |
| `/stop-ide` | Stop IDE session |
| `/list-files` | List workspace files |
| `/read-file` | Read workspace file |
| `/deploy` | Deploy to live |
| `/token-usage` | AI token usage stats |
| `/hosting-status` | Hosting health |
| `/apply-template` | Apply project template |
| `/create-file` | Write file to workspace |
| `/ai-chat` | Quick AI question |
| `/health-score` | Project health check |
| `/seo-audit` | SEO recommendations (stub) |
| `/customer-journey` | Customer lifecycle data |
| `/suggest-upsell` | Plan upgrade suggestion |
| `/create-staging` | Create staging site (stub) |
| `/run-tests` | Run test suite (stub) |
| `/generate-landing` | Generate landing page (stub) |
| `/migrate-site` | Migrate site (stub) |
| `/detect-framework` | Tech stack detection |
| `/performance-benchmark` | Response time check |
| `/accessibility-audit` | WCAG audit (stub) |
| `/revenue-analytics` | Revenue analytics (stub) |
| `/dead-link-scan` | Dead link scan (stub) |
| `/churn-risk` | Churn risk score |
| `/optimize-images` | Image optimization (stub) |
| `/generate-legal` | Legal pages (stub) |
| `/setup-ssl` | SSL provisioning |
| `/billing-forecast` | Billing estimate |
| `/export-data` | Data export (stub) |
| `/create-contact-form` | Contact form (stub) |
| `/send-status-report` | Status report |

**Note:** "Phase 27" endpoints (21-40) are mostly stubs — return canned responses without real implementation.

### 4.25 voiceRelay.js — `/api/voice-relay/*` (287 lines)

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `POST` | `/connect` | None | Open WS session to voice server |
| `POST` | `/send` | Voice session ID | Send message through WS |
| `GET` | `/poll` | Voice session ID | Long-poll for queued messages (25s) |
| `POST` | `/disconnect` | Voice session ID | Close session |
| `GET` | `/status` | None | Session health / count |

REST↔WS bridge for voice.php. Max 100 sessions, 30 min idle timeout, 200 message queue cap.

### 4.26 autopilotProxy.js — `/api/autopilot/*` (280 lines)

| Method | Path | Auth | Description |
|--------|------|------|-------------|
| `POST` | `/start` | None (uses `req._daUsername`) | Start browser session |
| `POST` | `/action` | None | Execute browser action |
| `POST` | `/observe` | None | Get page state |
| `POST` | `/stop` | None | Stop session |
| `GET` | `/status` | None | List active sessions |
| WS | `/stream` | None | Live screenshot frames |

Bridges MCP server's `AutopilotSession` to IDE panel via WebSocket. Sends JPEG frames as binary.

---

## 5. Support Modules

### auth/middleware.js (80 lines)
- `requireSession`: Validates Bearer JWT. **Auto-refresh**: expired tokens up to 7 days old are re-issued (new token in `X-New-Token` response header).
- `requireOwnResource`: Compares `:username` route param against `req.user.daUsername`.

### auth/sso.js (155 lines)
- `validateSsoToken()`: Verifies WHMCS-issued JWT.
- `issueSessionToken()`: Creates GoCodeMe session JWT.
- `checkSubscription()`: Calls WHMCS `GetClientsProducts` to verify active subscription.
- `ssoLogin()`: Full flow — validate → check subscription → resolve DA username → issue session.

### billing/alerts.js (170 lines)
- Fires at 50%/80%/100% thresholds.
- 100% triggers WHMCS overage invoice ($4.99/100K configurable).
- Sends via OpenClaw push + email.
- Dedup via Redis keys with 35-day TTL.

### billing/emailAutomation.js (662 lines)
- **Welcome series**: 3 emails (immediate, +2 days, +5 days).
- **Token alerts**: 50%, 80%, 100% threshold emails.
- **Winback**: 7+ days inactive → re-engagement email.
- **Upgrade suggestions**: 90%+ usage → upgrade nudge (14-day dedup).
- **Weekly digest**: Usage summary with cost/savings breakdown.
- All sent via WHMCS `SendEmail` API.
- 11 inline email templates.

### billing/whmcs.js (165 lines)
- `callWhmcs(action, params)`: Generic WHMCS API wrapper (POST with credentials).
- `getClient()`, `createOverageInvoice()`, `getClientInvoices()`, `getClientProducts()`.
- Timeout: 15s per WHMCS call.

### billing/pricing.js (320 lines)
- **30+ model pricing entries** covering Anthropic, OpenAI, Google, and open-source (Together.ai).
- `getModelPricing(modelId)`: Fuzzy matching with date-suffix stripping.
- `calculateCost()`: Full cache-aware pricing (cache_creation 1.25×, cache_read 0.1×).
- `getAllModelPricing()`: For dashboard display.

### billing/modelRouter.js (703 lines)
- **4 providers**: Anthropic, OpenAI, Google (Gemini), Together.ai (open-source).
- **22+ models** registered with cost tier, provider, capability flags.
- **5 modes**: auto, sonnet, opus, haiku, turbo.
- **Auto routing**: Classifies requests as complex/moderate/simple based on:
  - Active tool loop detection (tool_result in recent messages).
  - Recent tool activity count (last 10 messages).
  - Message count (≥20 → complex, ≥10 → moderate).
  - Content length analysis.
- Complex → Sonnet, Moderate → Haiku, Simple → Qwen3 Coder.
- `getAvailableModels()`, `getAvailableModes()` for UI.

### billing/formatTranslator.js (423 lines)
- `anthropicToOpenAI()`: Translates Messages API → Chat Completions.
- `openAIToAnthropic()`: Translates Chat Completions → Messages API.
- `createStreamTranslator()`: Streaming SSE format translation (OpenAI chunks → Anthropic events).
- Handles: system prompts, tool_use/tool_result blocks, image blocks, multi-content arrays.

### tokens/tokenCounter.js (210 lines)
- **Only output tokens count toward user plan limits** (input tokens are platform cost).
- `addUsage()`, `getUsage()`, `getLimit()`, `setLimit()`, `addTopUp()`, `resetUsage()`.
- `checkAllowance()`: Free plan → hard block at 100%. Paid plan → overage up to 200%, billed at $2/100K.
- `getOverageDetails()`: For billing dashboard.

### tokens/tokenBudget.js (363 lines)
**5 layers of budget protection:**
1. **Global monthly USD cap**: Default $200. Trips circuit breaker. Admin reset required.
2. **Global daily USD cap**: Default $25. Auto-resets at midnight UTC.
3. **Per-user daily USD cap**: Default $10. Prevents runaway sessions.
4. **Per-user daily token cap**: Default 500K tokens/day.
5. **Per-request max tokens**: Default 150K estimated tokens.

All configurable via env vars. Redis keys with auto-TTL.

### tokens/usageTracker.js (290 lines)
- Granular per-model, per-day tracking in Redis hashes.
- `recordUsage()`: Pipeline write to daily breakdown + model totals + total cost + history.
- `getDailyUsage()`: Returns array of daily summaries (pipeline optimized).
- `getModelBreakdown()`: Aggregated model stats for billing period.
- `getRequestHistory()`: Last 500 requests.

### monitoring/healthMonitor.js (289 lines)
- **Self-ping**: Every 60s → alert after 3 consecutive failures.
- **Redis hygiene**: Enforces TTLs on 15 key patterns, respects permanent key prefixes.
- **Redis memory check**: Alerts at 85% capacity.
- **Admin health route**: `GET /api/admin/health` (requireWhmcsSecret).
- Alert dedup: 30-minute cooldown per issue.
- Alerts via WHMCS `SendAdminEmail`/`SendEmail`.

### openclaw/sender.js (70 lines)
- `sendToLinkedChannels(daUsername, text)`: Reads channel links from Redis, POSTs to OpenClaw service.
- Best-effort delivery (skips failing channels).

### git/worker.js (233 lines)
- **Debounced auto-commit**: 3s window, batches rapid file writes.
- **Idempotent init**: Auto-creates repo, sets `.gitignore`, configures author identity.
- **Per-workspace queue**: Serializes operations to prevent races.
- **Safe command allowlist**: Only `init, add, commit, log, diff, revert, status, show, config, rm, clone`.
- `scheduleCommit()`, `commitNow()`, `runGit()`, `ensureRepo()`.

---

## 6. Authentication & Authorization Matrix

| Mechanism | Implementation | Used By |
|-----------|---------------|---------|
| **JWT Session** | `auth/middleware.js` → `requireSession` | Most `/api/*` routes |
| **Auto-refresh** | 7-day grace period, new token in `X-New-Token` header | auth/middleware.js |
| **WHMCS Secret** | `X-WHMCS-Secret` header = `process.env.WHMCS_WEBHOOK_SECRET` | access, admin, tokens/provision|reset|topup, billing/invoice, alfred, teams/provision, reseller/provision, da |
| **Own Resource** | `requireOwnResource` checks `:username` param vs `req.user.daUsername` | files, git |
| **Active Access** | `requireActiveAccess` checks Redis `access:<clientId>` | launch |
| **DA User** | `requireDaUser` checks Redis `da_username:<clientId>` | hosting |
| **Reseller** | `requireReseller` middleware in reseller.js | reseller routes |
| **Team Admin** | `isTeamAdmin()` helper | teams invite/remove/update |
| **API Key** | `Bearer gcm_xxx` → SHA-256 hash → Redis lookup | developer chat endpoint |
| **Voice Session** | `x-voice-session` header or `session_id` body/query | voiceRelay |
| **No Auth** | Public endpoints | usage/pricing, templates/list, referral/validate, health |

---

## 7. Security Issues

### CRITICAL

1. **`config.js` line 22 — Default JWT secret is `'changeme'`**  
   Only validated in production mode. If `NODE_ENV` isn't `production`, the system runs with a trivially guessable secret. Any attacker can forge session JWTs.

2. **`autopilotProxy.js` — No authentication on any endpoint**  
   `/api/autopilot/start`, `/action`, `/observe`, `/stop` accept `daUsername` from the request body with no authentication. Any unauthenticated caller can start browser sessions, execute actions, and observe page state for any user.

3. **`voiceRelay.js` — No authentication on `/connect`**  
   Anyone can open voice sessions without authentication. While sessions require a session ID to interact, the session creation itself is unprotected, enabling resource exhaustion.

4. **`extras.js` — Command injection in `/marketplace/install`**  
   Package name validation (`/^[@a-z0-9][\w./-]*$/i`) is insufficient. Malicious npm package names could potentially exploit `exec()` despite the regex. The `exec(cd "${workDir}" && npm install --save "${pkgName}")` pattern passes user input into a shell command.

5. **`extras.js` — Command injection in `/deploy`**  
   The `rsync` command includes `${workDir}` and `${pubDir}` constructed from `daUsername` which comes from the JWT. While JWT data should be trusted, if a DA username contained shell metacharacters, this would be exploitable.

### HIGH

6. **`da.js` — Temp password window in SSO flow**  
   Changes DA password to a known temp value, calls login API, then rotates. Brief window where the temp password could be intercepted or used concurrently.

7. **`ideProxy.js` — Uses `redis.keys()` for port validation**  
   `redis.keys('launch:sessions:*')` on every proxied request. O(N) scan of all keys — will degrade with user growth. Should use direct hash lookup.

8. **`admin.js` — Scans ALL Redis `access:*` keys**  
   `/status` and `/costs` endpoints scan all access keys. Blocks the event loop at scale.

9. **`emailAutomation.js` — Uses `redis.keys()` in bulk**  
   `processWelcomeSchedules()`, `processWinbackEmails()`, `processUpgradeSuggestions()`, `processWeeklyDigest()` all use `redis.keys('pattern:*')` — blocks Redis at scale.

10. **`server.js` — CORS set to `origin: '*'`**  
    Allows requests from any origin. Should be restricted to known domains.

### MEDIUM

11. **`developer.js` — Rate limiter doesn't reset atomically**  
    Uses separate `INCR` + `TTL` check for rate limiting. Race condition: multiple requests can pass before TTL is set.

12. **`teams.js`/`reseller.js` — No TTL on team/reseller member data**  
    `team:members:*`, `team:by_client:*`, `reseller:accounts:*` have no TTL. Will accumulate indefinitely.

13. **`extras.js` — Redis client created independently**  
    Creates its own ioredis connection instead of using the shared singleton from `redis.js`. Leads to resource leak and inconsistent connection management.

14. **`anthropicProxy.js` — Memory-based dedup cache not bounded by time for cleanup**  
    `recentHashes` Map grows to 200 entries with 30s individual TTL, but cleanup only happens when checking a hash. No periodic sweep — if traffic stops, stale entries persist.

---

## 8. Bugs & Logic Errors

1. **`tokens.js` `/report` — Reports both input + output but `addUsage()` only counts output**  
   The endpoint accepts `{ inputTokens, outputTokens }` and passes both to `tc.addUsage()`. The function correctly bills only output tokens, but the API contract is misleading — callers may think input tokens affect the limit.

2. **`developer.js` `/v1/chat/completions` — Dead code for proxy handler**  
   Lines 375-400 import `anthropicProxy` module and create a synthetic `proxyReq`/`proxyRes`, but this code is never used. The actual call is made via raw HTTPS directly below. The proxy handler variables are orphaned.

3. **`extras.js` — `getRedis()` function defined differently from everywhere else**  
   Uses `require('../tokens/tokenCounter').getRedisClient()` which doesn't exist (tokenCounter doesn't export `getRedisClient`). Falls back to `global._redisClient` which is never set. Then creates a new ioredis instance as a third attempt. This is fragile.

4. **`alfred.js` — `resolveUsername()` uses `redis.scan()` as fallback**  
   The SCAN-based fallback for client→DA username resolution can be very slow and iterates all matching keys. Should use the established `da_username:<clientId>` key pattern consistently.

5. **`formatTranslator.js` `translateUserMessage()` — Returns first element on multi-result**  
   Line 156: `return result.length === 1 ? result[0] : result[0]` — the ternary always returns `result[0]`, making tool_result messages that also contain text lose the text content.

6. **`teams.js` — Team data TTL is 90 days but member/usage data has no TTL**  
   `team:<teamId>` has 90-day TTL via `setex`, but `team:members:*`, `team:by_client:*`, `team:used:*` use plain `set` — they persist forever and can become orphaned if the team expires.

7. **`referral.js` — No TTL on referral data**  
   All referral Redis keys (`referral:code:*`, `referral:owner:*`, `referral:referred:*`, etc.) have no TTL and accumulate indefinitely.

8. **`billing/alerts.js` — $4.99/100K overage price mismatch**  
   `OVERAGE_PRICE_USD` defaults to `$4.99` per 100K, but `tokenCounter.js` documents $2/100K and `emailAutomation.js` references $2/100K in email templates. Inconsistent pricing.

---

## 9. Dead Code & Redundancy

1. **`access.js` `/sso/generate`** — Duplicate of `sso.js` `/generate`. Comment says "kept for backwards compat."

2. **`developer.js` lines 370-400** — Orphaned `anthropicProxy` import and synthetic request/response objects. The actual implementation bypasses them entirely.

3. **`ideProxy.js.bak`** — Backup file in the routes directory.

4. **`alfred.js` endpoints 21-40 (Phase 27)** — 20 endpoints that are mostly stubs returning canned responses:
   - `/seo-audit` — Returns hardcoded checklist
   - `/create-staging` — Returns stub URL
   - `/run-tests` — Returns "auto-detect" with no action
   - `/generate-landing` — Returns stub message
   - `/migrate-site` — Returns stub message
   - `/accessibility-audit` — Returns hardcoded list
   - `/revenue-analytics` — Returns stub  
   - `/dead-link-scan` — Returns stub
   - `/optimize-images` — Returns stub
   - `/generate-legal` — Returns stub page list
   - `/export-data` — Returns stub
   - `/create-contact-form` — Returns stub

5. **`extras.js` `getRedis()` function** — Tries 3 different approaches to get a Redis client, all fragile. Should use the shared `redis.js` singleton.

---

## 10. Performance Concerns

| Location | Issue | Impact |
|----------|-------|--------|
| `ideProxy.js` | `redis.keys('launch:sessions:*')` on every proxy request | O(N) — blocks Redis at scale |
| `admin.js` `/status` | Scans all `access:*` keys | Blocks event loop with many users |
| `admin.js` `/costs` | Scans all `access:*` keys, then per-user lookups | O(N²) with user count |
| `emailAutomation.js` | Four cron jobs all use `redis.keys()` | Blocks Redis periodically |
| `anthropicProxy.js` | 1659 lines in single file | Hard to maintain, test, refactor |
| `ideProxy.js` | 1840 lines in single file | Hard to maintain |
| `ideProxy.js` | Full HTML/CSS/JS for Alfred widget inlined | ~500 lines of HTML injected on every IDE page load |
| `server.js` | Body limit 50MB | Memory spike risk on large payloads |
| `reseller.js` `/accounts` | Sequential `tc.getUsage()` per sub-account | Slow with many accounts |
| `teams.js` `/usage` | Sequential `tc.getUsage()` per member | Slow with many members |
| `healthMonitor.js` | `redis.keys()` for TTL hygiene on 15 patterns | Periodic O(N) scans |

---

## 11. Redis Key Map

| Pattern | Purpose | TTL | Set By |
|---------|---------|-----|--------|
| `access:<clientId>` | Active/suspended status | None (permanent) | access.js |
| `da_username:<clientId>` | DA username mapping | None | access.js |
| `client_id_by_da:<daUsername>` | Reverse mapping | None | access.js |
| `tokens:used:<clientId>` | Monthly output token counter | 35 days | tokenCounter.js |
| `tokens:limit:<clientId>` | Monthly token limit | None | tokenCounter.js |
| `launch:sessions:<daUsername>` | Active IDE sessions | 24 hours | launch.js |
| `model_mode:<clientId>` | User's preferred model | None | tokens.js |
| `onboarding:<clientId>` | Onboarding wizard state | None | onboarding.js |
| `budget:global:spend:<YYYY-MM>` | Global monthly spend ($) | 40 days | tokenBudget.js |
| `budget:global:tripped` | Circuit breaker flag | 40 days | tokenBudget.js |
| `budget:global:daily:<YYYY-MM-DD>` | Global daily spend ($) | 2 days | tokenBudget.js |
| `budget:daily:usd:<clientId>:<date>` | Per-user daily spend ($) | 2 days | tokenBudget.js |
| `budget:daily:<clientId>:<date>` | Per-user daily tokens | 2 days | tokenBudget.js |
| `usage:daily:<clientId>:<date>` | Detailed daily usage hash | 35 days | usageTracker.js |
| `usage:models:<clientId>` | Model breakdown hash | 35 days | usageTracker.js |
| `usage:total_cost:<clientId>` | Running cost total | 35 days | usageTracker.js |
| `usage:history:<clientId>` | Recent request list (500) | 35 days | usageTracker.js |
| `billing:alert:50:<clientId>` | 50% alert dedup | 35 days | alerts.js |
| `billing:alert:80:<clientId>` | 80% alert dedup | 35 days | alerts.js |
| `billing:alert:100:<clientId>` | 100% alert dedup | 35 days | alerts.js |
| `billing:invoice:<clientId>` | Latest overage invoice ID | 35 days | alerts.js |
| `email:welcome:<clientId>:*` | Welcome series state | 90 days | emailAutomation.js |
| `email:winback:<clientId>` | Winback dedup | 30 days | emailAutomation.js |
| `email:token:<clientId>:N` | Token alert email dedup | 35 days | emailAutomation.js |
| `email:upgrade:<clientId>` | Upgrade email dedup | 14 days | emailAutomation.js |
| `email:digest:<clientId>` | Weekly digest dedup | 8 days | emailAutomation.js |
| `openclaw:links:<daUsername>` | Linked messaging channels | None | openclaw.js |
| `referral:code:<clientId>` | Referral code | None | referral.js |
| `referral:owner:<code>` | Reverse lookup: code→client | None | referral.js |
| `referral:referred:<clientId>` | Referral list (JSON array) | None | referral.js |
| `referral:referred_by:<clientId>` | Who referred this user | None | referral.js |
| `referral:rewards:<clientId>` | Total bonus tokens earned | None | referral.js |
| `team:<teamId>` | Team metadata | 90 days | teams.js |
| `team:members:<teamId>` | Member list (JSON) | None ⚠️ | teams.js |
| `team:by_client:<clientId>` | User→team lookup | None | teams.js |
| `team:by_owner:<clientId>` | Owner→team lookup | None | teams.js |
| `team:used:<teamId>` | Shared pool counter | None ⚠️ | teams.js |
| `team:invites:<teamId>` | Invite list | 90 days | teams.js |
| `team:invite:<code>` | Individual invite | 7 days | teams.js |
| `apikey:<hash>` | API key data | 1 year | developer.js |
| `apikeys:client:<clientId>` | Key index | None | developer.js |
| `apikey:usage:<hash>:<date>` | Daily request count | 2 days | developer.js |
| `apikey:tokens:<hash>:<month>` | Monthly token usage | 35 days | developer.js |
| `apikey:rate:<hash>` | Rate limit counter | 60 seconds | developer.js |
| `reseller:<clientId>` | Reseller config | 90 days | reseller.js |
| `reseller:accounts:<clientId>` | Sub-account list | None | reseller.js |
| `reseller:branding:<clientId>` | Branding settings | None | reseller.js |
| `reseller:revenue:<clientId>` | Revenue tracking | None | reseller.js |
| `reseller:by_sub:<subClientId>` | Sub→reseller lookup | None | reseller.js |
| `notes:<clientId>` | Quick notes | None | extras.js |
| `snippets:<clientId>` | Code snippets | None | extras.js |
| `ui_activity:<clientId>` | Activity feed (list, 100) | None | extras.js |
| `achievements:<clientId>` | Unlocked badges (set) | None | extras.js |
| `webhooks:<clientId>` | User webhooks | None | extras.js |
| `activity:<daUsername>` | Last activity timestamp | None | ideProxy.js |

---

## 12. External Service Dependencies

| Service | URL | Used By | Failure Impact |
|---------|-----|---------|---------------|
| **Anthropic API** | `api.anthropic.com` | anthropicProxy, claude, aiTerminal, developer | AI features down |
| **Together.ai** | `api.together.xyz` | anthropicProxy (auto-routing) | Falls back to Sonnet |
| **OpenAI** | `api.openai.com` | extras (DALL-E), modelRouter | Image gen down |
| **Google Gemini** | `generativelanguage.googleapis.com` | modelRouter | Gemini models unavailable |
| **WHMCS** | `WHMCS_API_URL` | billing, SSO, access, email | Billing/auth degraded |
| **DirectAdmin** | `DA_HOST:2222` | hosting, files, git, launch | Hosting management down |
| **Redis** | `127.0.0.1:6379` | Everything | Complete system failure |
| **OpenClaw** | `localhost:3004` | openclaw, alerts | Messaging down |
| **Voice Server** | `localhost:3006` (WSS) | voiceRelay, ideProxy | Voice features down |
| **MCP Server** | `localhost:3005` | anthropicProxy (ELEPHANT memory) | AI context reduced |
| **MCP Autopilot** | `mcp-server/src/browser/` | autopilotProxy | Browser automation down |
| **npm Registry** | `registry.npmjs.org` | extras marketplace | Package search down |

---

## 13. Background Processes & Crons

| Process | Interval | Location | Purpose |
|---------|----------|----------|---------|
| Idle session reaper | 5 min | server.js | Kill IDE sessions idle >30 min |
| Welcome email processor | 30 min | server.js → emailAutomation | Send scheduled welcome emails |
| Winback email scanner | 6 hours | server.js → emailAutomation | Re-engage inactive users |
| Upgrade suggestion scanner | 24 hours | server.js → emailAutomation | Nudge high-usage users |
| Weekly digest | 7 days | server.js → emailAutomation | Usage summary emails |
| Health check self-ping | 60 sec | healthMonitor.js | Detect middleware crashes |
| Redis TTL hygiene | (manual/periodic) | healthMonitor.js | Fix keys missing TTLs |
| Voice session reaper | 2 min | voiceRelay.js | Reap idle voice sessions |
| Git debounce | 3 sec window | git/worker.js | Batch rapid file writes |
| Dedup cache cleanup | On-access | anthropicProxy.js | Expire 30s-old dedup hashes |

---

## 14. Recommendations

### Priority 1 — Security Fixes

1. **Add authentication to autopilotProxy.js endpoints** — require session JWT or WHMCS secret.
2. **Add authentication to voiceRelay /connect** — at minimum, require a session token.
3. **Fix CORS** — restrict `origin` to `gocodeme.com`, `gositeme.com`, and relevant subdomains.
4. **Use `execFile` instead of `exec`** in extras.js deploy and marketplace install — avoids shell injection.
5. **Enforce `NODE_ENV=production`** or remove the conditional validation of JWT secret.

### Priority 2 — Data Integrity

6. **Replace `redis.keys()` with `SCAN`** everywhere — or better, use Redis Sets/Hashes with known keys.
7. **Add TTLs to all Redis keys** that currently lack them (referral, team members, extras data, etc.).
8. **Fix overage pricing inconsistency** — align alerts.js ($4.99/100K) with tokenCounter.js ($2/100K) and email templates ($2/100K).
9. **Fix `formatTranslator.js` multi-result bug** — line 156 discards tool results with text.

### Priority 3 — Architecture

10. **Split anthropicProxy.js (1659 lines) and ideProxy.js (1840 lines)** into smaller modules.
11. **Extract Alfred widget HTML/CSS/JS** from ideProxy.js into external files served statically.
12. **Remove dead code**: developer.js orphaned proxy handler, access.js duplicate SSO endpoint, ideProxy.js.bak.
13. **Implement or remove 12 stub endpoints** in alfred.js Phase 27.
14. **Fix extras.js Redis client** — use shared singleton from redis.js instead of creating a new connection.

### Priority 4 — Observability

15. **Add request-level tracing** (correlation IDs) across the proxy chain.
16. **Add Prometheus/StatsD metrics** for: request latency per route, model routing distribution, cache hit rates, budget utilization.
17. **Structured logging** — switch Winston format to JSON for log aggregation.

---

*End of audit report.*
