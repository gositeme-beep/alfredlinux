# GoCodeMe — Build Status

**Last updated:** 2026-02-25  
**Project root:** `/home/gositeme/domains/gositeme.com/public_html/gocodeme/`

---

## Ports In Use

| Port | Service | Notes |
|------|---------|-------|
| 2222 | DirectAdmin | DO NOT USE — DA admin panel |
| 3001 | Middleware | Node.js DA API bridge |
| 3005 | MCP Server | StreamableHTTP + SSE at `/mcp` (default; override with MCP_PORT) |
| 4000+ | Theia IDE | Per-customer, auto-allocated by `/api/launch` (even ports) |
| 4001+ | OpenHands | Per-customer, Theia port +1 (odd ports) |
| 3004 | OpenClaw | Messaging gateway — Telegram/WhatsApp/Discord; tmux session `openclaw` |

---

## Credentials & Config

- **DA admin:** `seller1` / see `middleware/.env` → `DA_ADMIN_USER` / `DA_ADMIN_PASS`
- **DA host:** `https://15.235.50.60:2222`
- **Anthropic key:** `middleware/.env` → `ANTHROPIC_API_KEY`
- **JWT secret:** `middleware/.env` → `JWT_SECRET`
- **MCP server `.env`:** symlink → `middleware/.env`

---

## Component Status

### ✅ Weeks 1-2 — DA API Bridge (COMPLETE)

**`middleware/`** — Node.js/Express on port 3001  
- All DA file operations working: list, read, write, delete, rename, stat  
- Key fixes applied (see below)  
- Routes: `/files/:username`, `/git/:username`, `/sso`, `/tokens`, `/access`  
- Start: `cd middleware && node src/server.js`

**DA API bug fixes applied in `middleware/src/directadmin/fileManager.js`:**
- `writeFile()` — uses `action=upload` + multipart `file1=Buffer` (not `action=save`)
- `readFile()` — parses URL-encoded response via `URLSearchParams`, extracts `TEXT=` field
- `deleteFile()` — uses `action=multiple&button=delete` (not `action=delete`)

---

### ✅ Week 2 — MCP Server (COMPLETE)

**`mcp-server/`** — Node.js on port 3005  
- Transport: StreamableHTTP (primary) + SSE fallback  
- `POST /mcp` — StreamableHTTP endpoint  
- `GET /mcp` — SSE fallback  
- `DELETE /mcp` — close session  
- `GET /mcp/health` — returns `{"ok":true,"sessions":N}`  
- Auth: `Authorization: Bearer <JWT>` with `daUsername` claim  
- Start: `bash mcp-server/start.sh` (or `scripts/start-agent.sh` auto-starts it)  
- Port override: `MCP_PORT=XXXX` env var

---

### ✅ Week 3 — Theia IDE Fork (COMPLETE)

**`theia-fork/`** — Eclipse Theia v1.68.201, fully built  
- Rebranded: GoCodeMe IDE, brand color `#0ea5e9`  
- Built output: `theia-fork/applications/browser/lib/backend/main.js`  
- 70+ VS Code plugins in `theia-fork/plugins/`  
- MCP server config baked in: connects to `http://localhost:3005/mcp` on start  
  (**Note:** update Theia's MCP config if re-building — see `mcp-server/start.sh`)
- AI: claude-sonnet-4-6, all AI features enabled by default  

**Build notes (needed for rebuilds):**
```bash
cd theia-fork
yarn --pure-lockfile --ignore-engines --ignore-scripts   # --ignore-scripts required (no xkbfile)

# After yarn install, manually build native binaries:
cd node_modules/@vscode/ripgrep && node ./lib/postinstall.js && cd ../../..
cd node_modules/drivelist && npm run install && cd ../..
cd node_modules/keytar && npm run install && cd ../..
cd node_modules/node-pty && ../../.bin/node-gyp rebuild && cd ../..

yarn build:extensions
yarn download:plugins
yarn browser build
```

**Start Theia:**
```bash
export ANTHROPIC_API_KEY=$(grep ANTHROPIC_API_KEY middleware/.env | cut -d= -f2)
node theia-fork/applications/browser/lib/backend/main.js /path/to/workspace \
  --hostname=0.0.0.0 --port=3000 \
  --plugins="local-dir:theia-fork/plugins"

# Or use the launch script:
./scripts/start-theia.sh <da_username> /path/to/workspace 3000
```

---

### ✅ WHMCS Module (COMPLETE)

**`whmcs-module/modules/servers/gocodeme/`**  
- `gocodeme.php` — all 7 WHMCS provisioning functions  
- `templates/clientarea.tpl` — client area with 'Open GoCodeMe Editor' button  
- **Not yet deployed to WHMCS** — copy to WHMCS `modules/servers/gocodeme/` when ready

---

### ✅ Week 4 — OpenHands Fork (COMPLETE)

**`openhands-fork/`** — OpenHands v1.4.0, rebranded as GoCodeMe Agent  
- Python 3.12.9 via pyenv (`~/.pyenv/`), Poetry 2.3.2  
- Rebranded: `GoCodeMe Agent` title confirmed via HTTP 200 at port 3003 ✅  
- Runtime: `local` (no Docker) — uses libtmux to manage shell sessions  
- MCP: `config.toml` → `[mcp] shttp_servers` → `http://localhost:3005/mcp`  
- Claude: `anthropic/claude-sonnet-4-6` locked in `config.toml`  
- Launch: `./scripts/start-agent.sh <da_username> <workspace_path> <port> [jwt_token]`  

**Bug fixes applied in `openhands-fork/`:**
- `openhands/runtime/impl/local/local_runtime.py:118` — `session.attached_pane` → `session.active_pane` (libtmux 0.31+)
- `openhands/runtime/impl/local/local_runtime.py:121` — `session.kill_session()` → `session.kill()` (libtmux 0.30+)
- `openhands/runtime/impl/local/local_runtime.py:115` — Added stale test-session cleanup before `new_session()`

**Start OpenHands:**
```bash
# One-liner for testing:
cd openhands-fork
export PYENV_ROOT="$HOME/.pyenv" && export PATH="$PYENV_ROOT/bin:$PATH" && eval "$(pyenv init - bash)"
export ANTHROPIC_API_KEY=<key> && export GOCODEME_JWT_TOKEN=<jwt> && export OPENHANDS_CONFIG_FILE="$(pwd)/config.toml" && export port=3003
poetry run python -m openhands.server

# Or via script (production):
./scripts/start-agent.sh seller1 /home/seller1/domains/seller1.com/public_html 3003 <jwt>
```

**Important:** Port is set via lowercase env var `port=3003` (not `--port`).

**Generate test JWT** (for MCP auth testing):
```bash
cd middleware
node -e "const jwt=require('jsonwebtoken'); const s=require('fs').readFileSync('.env','utf8').match(/JWT_SECRET=(.+)/)[1]; console.log(jwt.sign({daUsername:'seller1'},s,{expiresIn:'1d'}))"
```

---

### ✅ Week 5-6 — OpenHands ↔ MCP Integration (COMPLETE)

**All 7 MCP tools tested end-to-end via curl against live MCP server:**

| Tool | Status | Notes |
|------|--------|-------|
| `read_file` | ✅ | reads via `CMD_API_FILE_MANAGER?action=edit` |
| `write_file` | ✅ | multipart upload via `CMD_API_FILE_MANAGER` |
| `list_directory` | ✅ | GET `CMD_API_FILE_MANAGER?path=<rel>` |
| `delete_file` | ✅ | POST `action=multiple&button=delete` |
| `create_directory` | ✅ | POST `action=newfolder` |
| `rename_file` | ✅ | read+write+delete workaround (DA `action=rename` broken on this server) |
| `search_files` | ✅ | recursive `listDirectory` + client-side regex (DA `CMD_API_SYSTEM_EXECUTE` returns 405) |

**Key fixes applied in `mcp-server/src/daClient.js`:**
- Rewrote all DA calls to use `CMD_API_FILE_MANAGER` (DA Evolution JSON API returns 405)
- `renameFile()` — read+write+delete workaround (server's `action=rename` rejects all path formats)
- `searchFiles()` — recursive directory walk + regex filter (no shell exec permission)

**`mcp-server/src/mcpHttpServer.js`:**
- Removed global `express.json()` (was consuming body before MCP transport)
- Default port changed 3002 → 3005 (3002 locked by root process)
- Added `EADDRINUSE` error handler on `httpServer`

**`openhands-fork/config.toml`:** MCP URL updated to `http://localhost:3005/mcp`  
**`scripts/start-agent.sh`:** Auto-starts MCP server if not already running on `$MCP_PORT`  

**Remaining:** Full Claude→MCP→DA pipeline test requires real `ANTHROPIC_API_KEY`

---

### ✅ Week 7-8 — Unified UI + Token Counter (COMPLETE)

**Customer Dashboard** — single-page HTML at `GET /dashboard`  
- Token usage widget — live bar chart, used/limit/remaining with colour coding  
- "Launch IDE" button → `POST /api/launch` — spawns Theia + OpenHands, returns URLs  
- "Stop" button → `POST /api/launch/stop`  
- Active sessions table — auto-pruned on refresh (checks PIDs)  
- WHMCS SSO handshake: redirect target is `http://<host>:3001/dashboard#token=<jwt>`  
- No build step — vanilla JS, served as static HTML from `middleware/public/`  

**New middleware routes:**  
- `GET  /dashboard`            — serves `middleware/public/dashboard.html`  
- `POST /api/launch`           — allocates free port pair, spawns Theia + Agent  
- `GET  /api/launch/sessions`  — lists active sessions (prunes dead PIDs)  
- `POST /api/launch/stop`      — kills all sessions for authenticated user  

**Files added/changed:**  
- `middleware/public/dashboard.html` — dashboard UI  
- `middleware/src/routes/launch.js`  — IDE launch/stop/sessions logic  
- `middleware/src/server.js`         — registers launch route + static files + `/dashboard`  

**Port allocation:** Theia starts at `THEIA_PORT_BASE` (default 4000), Agent at +1; scans up to find a free pair.  
**Public URL:** set `PUBLIC_HOST` + `PUBLIC_SCHEME` env vars for production URLs in session links.

---

### ✅ Week 9-10 — WHMCS SSO Wiring (COMPLETE)

Full SSO handshake wired end-to-end. All 19 E2E tests pass.

**Files changed / created:**
| File | Change |
|------|--------|
| `whmcs-module/modules/servers/gocodeme/gocodeme.php` | Fixed `sso_redirect` to redirect to `{serverhostname}/dashboard#token=<jwt>` (was hardcoded `gocodeme.com/editor/?sso=`) |
| `whmcs-module/modules/servers/gocodeme/gocodeme.php` | Added `gocodeme_AddonConfig/Activate/Terminate/Suspend/Unsuspend` for Token Top-Up product addon |
| `middleware/src/routes/sso.js` | Added `GET|POST /api/sso/exchange` — accepts short SSO JWT, verifies access, re-issues as full session token (used for email/link SSO paths) |
| `middleware/src/routes/launch.js` | Added `requireActiveAccess` middleware — blocks `POST /api/launch` with HTTP 402 if account is suspended/terminated |
| `scripts/test-sso-e2e.sh` | Created comprehensive E2E test (19 assertions, no jq dependency) |

**SSO Flow (end-to-end):**
```
Customer clicks "Open GoCodeMe Editor" in WHMCS client area
  → WHMCS calls gocodeme_sso_redirect()
    → POST /api/sso/generate  (X-WHMCS-Secret auth)
    → Middleware verifies access:active in Redis, looks up daUsername
    → Returns signed JWT (whmcsClientId + daUsername + plan, 1h expiry)
  → WHMCS redirects browser to:
      http://<middleware_host>/dashboard#token=<jwt>
  → dashboard.html reads location.hash, stores JWT in sessionStorage
  → All subsequent API calls: Authorization: Bearer <jwt>
  → GET /api/sso/me  → confirms identity
  → GET /api/tokens/usage  → loads token widget
  → GET /api/launch/sessions  → loads sessions table
```

**Alternate path (email/link SSO):**
```
GET /api/sso/exchange?sso=<jwt>
  or
POST /api/sso/exchange  body: { token: "<jwt>" }
→ Re-issues as fresh session token (useful for email campaign links)
```

**Access gate on launch:**
- `POST /api/launch` returns `HTTP 402` with `{ error: "Account suspended — please renew..." }` if `access:<id>` ≠ `active`
- Fails open (does not block) on Redis errors to avoid bricking customers

**Top-up addon (gocodeme.php):**
- `gocodeme_AddonConfig` — dropdown: 500k / 1M / 2.5M / 5M tokens
- `gocodeme_AddonActivate` — calls `POST /api/tokens/topup`, credits immediately
- WHMCS admin creates "GoCodeMe Token Top-Up" addon product pointing to this module

**E2E test:**
```bash
cd ~/domains/gositeme.com/public_html/gocodeme
WHMCS_WEBHOOK_SECRET=$(grep WHMCS_WEBHOOK_SECRET middleware/.env | cut -d= -f2)
WHMCS_WEBHOOK_SECRET="$WHMCS_WEBHOOK_SECRET" bash scripts/test-sso-e2e.sh
# → ALL 19 TESTS PASSED
```

**Still needed before production:**
- Deploy `whmcs-module/` to live WHMCS installation
- Set `WHMCS_API_IDENTIFIER` in `middleware/.env` (for `checkSubscription` path)
- Set `PUBLIC_HOST` + `PUBLIC_SCHEME` in `middleware/.env` (for IDE redirect URLs)

---

### ✅ Weeks 11-12 — OpenClaw Messaging Gateway (COMPLETE)

**Port:** 3004 | **Tmux session:** `openclaw` | **Start:** `bash scripts/start-openclaw.sh`

**What was built:**
- Multi-channel messaging gateway supporting Telegram (webhook + long-poll), WhatsApp (Meta Cloud API), Discord (HTTP interactions / slash commands)
- Agent bridge: routes messages to live per-customer agent → falls back to direct Claude API; token-gates every request via `/api/tokens/usage`
- Conversation history in Redis (`openclaw:conv:<channel>:<userId>`, TTL 7 days, max 50 messages)
- User channel linking via one-time JWT tokens (1h expiry)
- Built-in commands: `/help`, `/link <token>`, `/unlink`, `/reset`, `/status`
- Middleware `/api/openclaw/*` routes: `link-token`, `link`, `stats/:daUsername`, `links/:daUsername`, `send`
- Dashboard widget: linked channels display, message count, last-seen, link token generator with copy button

**Linking flow:**
```
Dashboard → GET /api/openclaw/link-token  → {token, expiresIn:3600}
User sends → /link <token>  in Telegram/WhatsApp/Discord
OpenClaw → POST /api/openclaw/link  → Redis openclaw:user:<ch>:<id>
```

**Files created:**
- `openclaw/src/server.js` — Express entry point (port 3004)
- `openclaw/src/config.js`, `logger.js`, `redis.js` — infrastructure
- `openclaw/src/store/conversations.js` — Redis conversation + link store
- `openclaw/src/agent/bridge.js` — agent bridge with token gating + fallback
- `openclaw/src/router/messageRouter.js` — central message dispatcher
- `openclaw/src/adapters/telegram.js` — Telegram adapter
- `openclaw/src/adapters/whatsapp.js` — WhatsApp adapter
- `openclaw/src/adapters/discord.js` — Discord HTTP interactions adapter
- `middleware/src/routes/openclaw.js` — middleware proxy routes
- `scripts/start-openclaw.sh` — tmux start/stop/status script
- `scripts/test-openclaw.sh` — E2E test suite

**Required env vars** (add to `middleware/.env`):
```bash
TELEGRAM_BOT_TOKEN=        # enables Telegram
WHATSAPP_TOKEN=             # Meta Cloud API access token
WHATSAPP_PHONE_ID=          # Meta phone number ID
WHATSAPP_VERIFY_TOKEN=      # webhook verify token
DISCORD_BOT_TOKEN=          # Discord bot token
DISCORD_APP_ID=             # Discord application ID
DISCORD_PUBLIC_KEY=         # Ed25519 public key for interaction verification
OPENCLAW_URL=http://localhost:3004   # set if using a different port
```

**E2E tests:** `bash scripts/test-openclaw.sh` → **ALL 23 TESTS PASSED** ✅

---

### ✅ Week 13 — Git Auto-Commit (COMPLETE)

**Delivered:**
- `middleware/src/git/worker.js` — local git execution engine
  - `runGit()` — execFile-based, 30s timeout, subcommand allowlist
  - `ensureRepo()` — idempotent `git init -b main` + .gitignore + local identity
  - `scheduleCommit()` — debounced (default 3s, `GIT_DEBOUNCE_MS` env override), batches rapid writes
  - `commitNow()` — immediate commit bypassing debounce (used by /checkpoint)
  - `resolveWorkDir()` — resolves `~/gocodeme-workspace/` (auto-created, clean git dir)
  - Queue system — per-workspace serialised Promise chain prevents race conditions

- `middleware/src/routes/git.js` — rewritten (DA API → local exec)
  - `POST /api/git/:username/checkpoint` — stage + commit immediately
  - `POST /api/git/:username/revert` — `git revert HEAD --no-edit` (new commit, non-destructive)
  - `GET  /api/git/:username/log?limit=N` — `{ok, commits:[{hash,subject,author,date}]}`
  - `GET  /api/git/:username/status` — `{ok, clean:bool, files:[{flag,file}]}`
  - `GET  /api/git/:username/diff?file=` — diff HEAD vs working tree
  - `POST /api/git/:username/init` — explicit repo initialisation

- `middleware/src/routes/files.js` — auto-commit hooks
  - `scheduleCommit()` fires after: write, delete, rename, mkdir
  - Fire-and-forget — never blocks HTTP response

- `middleware/public/dashboard.html` — Git History card
  - "Save Checkpoint" button, "Undo Last AI Change" button (with confirm)
  - Status badge (clean / N uncommitted changes)
  - Recent 10-commit log table (hash | message | date)

- `scripts/test-git.sh` — 19/19 E2E tests pass
  - init, checkpoint, log, status, diff, auth guard, auto-commit (debounce), revert

**Workspace:** `~/gocodeme-workspace/` (dedicated, no embedded repos)
**Commit author:** `GoCodeMe[<daUsername>]` + `<daUsername>@gocodeme.local` (per-repo local config)

---

### ✅ Week 14 — Billing Engine (COMPLETE)

**Date:** 2026-02-26  
**Tests:** 27/27 (`bash scripts/test-billing.sh`)

#### What was built

| File | Purpose |
|------|---------|
| `middleware/src/billing/whmcs.js` | WHMCS API client — `createOverageInvoice`, `getClientInvoices`, `getClient`, `getClientProducts` |
| `middleware/src/billing/alerts.js` | Token threshold alert engine — 80% warning push, 100% overage invoice + push |
| `middleware/src/openclaw/sender.js` | Internal helper — reads `openclaw:links:<daUsername>` Redis Set, POSTs to OpenClaw |
| `middleware/src/routes/billing.js` | 5 billing HTTP endpoints (usage-report, invoices, invoice, reset-alerts, whmcs-client) |

#### Alert deduplication (Redis)
- `billing:alert:80:<id>` — 35-day TTL, set once per billing cycle at 80%
- `billing:alert:100:<id>` — 35-day TTL, set once per billing cycle at 100%
- `billing:invoice:<id>` — stores WHMCS invoice ID for the cycle

#### Dashboard
- Billing card added to `public/dashboard.html` with token usage progress bar + invoices table
- `loadBilling()` JS function: parallel fetch of usage-report + invoices

#### Credentials (real WHMCS)
- `WHMCS_API_IDENTIFIER` / `WHMCS_API_SECRET` / `WHMCS_API_URL` — set in `.env`

#### Tests
```
T01  Middleware health
T02  Auth guard (3 endpoints → 401)
T03  WHMCS secret guard (2 endpoints → 401)
T04  Usage report (ok, usage, alerts, whmcsClientId)
T05  Invoices list (ok, array)
T06  WHMCS client info (graceful degradation)
T07  Reset alerts (Redis keys deleted)
T08  80% alert hook fires + Redis key set
T09  100% alert hook fires + Redis key set
T10  Manual invoice endpoint (WHMCS call)
T11  Billing cycle reset clears alert keys
T12  Token usage visible in billing report
```

---

### ✅ Week 15 — Onboarding Wizard & Claude Chat (COMPLETE)

**Date:** 2026-02-26

#### Onboarding Wizard
- `middleware/src/routes/onboarding.js` — 4-step guided wizard
  - `GET  /api/onboarding/:username/status` — current step + completion state
  - `POST /api/onboarding/:username/advance` — move to next step
  - `POST /api/onboarding/:username/complete` — mark onboarding finished
  - `POST /api/onboarding/:username/reset` — restart from step 1
  - Steps: welcome → workspace → messaging → first-task
  - State persisted in Redis (`onboarding:<username>`)

#### Claude Chat Endpoint
- `middleware/src/routes/claude.js` — real Anthropic API proxy
  - `POST /api/claude/:username/chat` — streaming SSE response
  - Model locked to `claude-sonnet-4-6` (from `ANTHROPIC_MODEL` env var)
  - Token tracking: every request metered via `tokenCounter.addUsage()`
  - Checks allowance before sending request → returns 429 if exhausted
  - System prompt includes customer context (username, workspace path)

#### Dashboard Integration
- Onboarding card in `public/dashboard.html`
- Claude Chat panel in dashboard with streaming output

**Note:** `ANTHROPIC_API_KEY` must be set to a real key in `.env` before Claude chat is functional.

---

### ⚠️ Week 16 — Production Hardening (REMAINING)

**Still needed:**
- [ ] Set real `ANTHROPIC_API_KEY` in `.env`
- [ ] Switch WHMCS products from `directadmin` to `gocodeme` server type
- [ ] Create GoCodeMe welcome email template in WHMCS
- [ ] Full end-to-end integration test (WHMCS order → provision → SSO → IDE)
- [ ] Security audit (input validation, rate limits, path traversal review)
- [ ] SSL certificate setup (certbot not installed — DA handles SSL)
- [ ] EN/FR bilingual dashboard
- [ ] Terms of Service addendum for AI token usage

---

## How to Start Everything

```bash
BASE=/home/gositeme/domains/gositeme.com/public_html/gocodeme

# 1. Middleware
cd $BASE/middleware && node src/server.js &

# 2. MCP server
bash $BASE/mcp-server/start.sh &

# 3. OpenClaw messaging gateway
bash $BASE/scripts/start-openclaw.sh

# 4. Theia + Agent (via dashboard — auto-allocates ports)
# Customer visits:  http://<host>:3001/dashboard#token=<jwt>
# Then clicks 'Launch IDE' — middleware handles the rest.
#
# Or manually:
$BASE/scripts/start-theia.sh <da_username> /home/<da_username>/domains/<domain>/public_html 4000
JWT=$(node -e "const jwt=require('jsonwebtoken'); const s=require('fs').readFileSync('$BASE/middleware/.env','utf8').match(/JWT_SECRET=(.+)/)[1]; console.log(jwt.sign({daUsername:'<da_username>'},s,{expiresIn:'1d'}))")  
$BASE/scripts/start-agent.sh <da_username> /home/<da_username>/domains/<domain>/public_html 4001 "$JWT"
```

---

## Architecture Summary

```
Browser (customer)
    │
    ▼
Theia IDE (port 3000)  ←──→  OpenHands Agent (port 3003, planned)
    │                              │
    └──────────┬───────────────────┘
               ▼
         MCP Server (port 3005)
               │
               ▼
         Middleware (port 3001)
               │
               ▼
         DirectAdmin API (port 2222)
               │
               ▼
         Customer's public_html (live on their domain)
```
