# GoCodeMe IDE — Complete System Deep Dive

> **Last updated:** March 9, 2026  
> **Author:** AI Agent (Copilot session with Commander dp)  
> **Purpose:** If you are an AI agent or developer debugging this system, READ THIS FIRST.  
> **Why this exists:** The owner has short-term memory loss. Future agents will arrive
> with zero context. This document is the map.

---

## Table of Contents

1. [The Big Picture](#1-the-big-picture)
2. [The Proxy Chain (A → Z)](#2-the-proxy-chain-a--z)
3. [Key Files & What They Do](#3-key-files--what-they-do)
4. [How the IDE Launches](#4-how-the-ide-launches)
5. [Socket.io Connection Lifecycle](#5-socketio-connection-lifecycle)
6. [The Sandbox (Security)](#6-the-sandbox-security)
7. [PM2 Services](#7-pm2-services)
8. [Redis Keys](#8-redis-keys)
9. [db-query.php (Node→PHP DB Bridge)](#9-db-queryphp-nodephp-db-bridge)
10. [Known Gotchas & Past Bugs](#10-known-gotchas--past-bugs)
11. [Debugging Playbook](#11-debugging-playbook)
12. [Server Constraints](#12-server-constraints)

---

## 1. The Big Picture

GoCodeMe is an online IDE (based on Theia/Eclipse) that lets GoSiteMe customers
edit their websites in a browser. It runs on a single OVH server managed by
DirectAdmin. There is NO Docker, NO Kubernetes, NO load balancer. Everything
runs on one box as user `gositeme` (no sudo).

```
┌─────────────────────────────────────────────────────────────────┐
│                        THE SERVER                               │
│              server-15-235-50-60 / 15.235.50.60                 │
│              Ubuntu · 32GB RAM · user: gositeme                 │
│                                                                 │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │ Apache 2.4.66 (managed by DirectAdmin, port 443 SSL)   │    │
│  │ ┌─────────────────────────────────────────────────────┐ │    │
│  │ │ PHP 8.3 FPM (all .php pages, API endpoints)        │ │    │
│  │ └─────────────────────────────────────────────────────┘ │    │
│  │ ┌─────────────────────────────────────────────────────┐ │    │
│  │ │ /middleware/ dir → proxy.php (PHP curl proxy)       │ │    │
│  │ │   └──→ http://127.0.0.1:3001 (Node middleware)     │ │    │
│  │ │         └──→ http://127.0.0.1:4000 (Theia IDE)     │ │    │
│  │ └─────────────────────────────────────────────────────┘ │    │
│  └─────────────────────────────────────────────────────────┘    │
│                                                                 │
│  ┌──────────────┐  ┌──────────────┐  ┌───────────────────────┐ │
│  │ Redis :6379  │  │ MySQL :3306  │  │ PM2 (17+ services)    │ │
│  └──────────────┘  └──────────────┘  └───────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
```

---

## 2. The Proxy Chain (A → Z)

This is the **most important section**. The IDE proxy chain is unusual because
Apache's `mod_proxy_http` is NOT available (DirectAdmin compiled it out). Instead,
a PHP script acts as the reverse proxy via curl.

### The Full Request Path

```
BROWSER (https://gositeme.com/middleware/ide/4000/?gcm_auth=<token>)
   │
   ▼
APACHE (port 443, SSL termination)
   │  DirectAdmin manages httpd.conf — DO NOT edit it manually,
   │  DA will overwrite your changes on any user config change.
   │
   ▼
PHYSICAL DIRECTORY: /public_html/middleware/
   │  Apache sees this directory exists and serves files from it
   │  (the root .htaccess RewriteRule for /middleware/ is ignored)
   │
   ▼
/middleware/.htaccess
   │  RewriteRule ^(.*)$ proxy.php [L,QSA]
   │  Also sets CSP headers (unsafe-inline, unsafe-eval, blob:, ws:, wss:)
   │  Also disables caching for IDE content
   │
   ▼
/middleware/proxy.php  (THE CRITICAL FILE)
   │  PHP curl-based reverse proxy
   │  1. Reads PHP session (login state for header bar injection)
   │  2. Calls session_write_close() to release session lock
   │  3. set_time_limit(300) — overrides FPM's 30s default
   │  4. Strips /middleware/ prefix from path
   │  5. Forwards request via curl to http://127.0.0.1:3001
   │  6. Forwards response headers (selective allowlist)
   │  7. Injects GoSiteMe header bar into non-IDE HTML responses
   │  8. Passes socket.io responses through unmodified
   │
   ▼
NODE.JS MIDDLEWARE (port 3001, PM2 name: "gocodeme-middleware")
   │  Express app at gocodeme/middleware/src/server.js
   │  Routes:
   │    /health          → health check
   │    /api/launch      → launches Theia instances
   │    /ide/:port/*     → reverse proxies to Theia on that port
   │    /agent/:port/*   → reverse proxies to Agent on that port
   │    /api/anthropic-proxy/:token → Claude API proxy with token tracking
   │
   ▼  (for /ide/:port/ requests)
http-proxy (Node module, in ideProxy.js)
   │  Two proxy objects:
   │    - wsProxy: for WebSocket upgrades (selfHandleResponse: false)
   │    - proxy: for HTTP (selfHandleResponse: true, injects HTML)
   │  Auth: gcm_ide_auth cookie or gcm_auth query param → verified against Redis
   │  Injection: Alfred AI widget, localStorage isolation per port
   │
   ▼
THEIA IDE (port 4000+, spawned per-user)
   │  Theia 1.68, Node.js process
   │  Launched by start-theia.sh (called from launch.js)
   │  Uses socket.io for RPC (not raw WebSocket)
   │  Sandboxed by gocodeme-sandbox.js (--require flag)
   │
   ▼
BROWSER receives IDE HTML + socket.io connection
```

### Why WebSocket Doesn't Work Through the Chain

1. **proxy.php is PHP** — PHP cannot hold persistent WebSocket connections
2. Apache's RewriteRule `[P]` flag strips hop-by-hop headers (Connection, Upgrade)
3. `mod_proxy_http` is not compiled into this Apache build
4. `mod_proxy_wstunnel` IS available but requires VirtualHost ProxyPass directives
5. DirectAdmin regenerates httpd.conf, wiping any manual ProxyPass additions

**Result:** Socket.io falls back to HTTP long-polling through the PHP proxy.

### How Socket.io Long-Polling Works Through proxy.php

```
Browser                    proxy.php (curl)           Middleware           Theia
   │                           │                         │                   │
   ├─ GET /socket.io/?EIO=4 ──►│── curl GET ────────────►│── proxy ─────────►│
   │◄─ {"sid":"xxx",           │◄─────────────────────────│◄──────────────────│
   │    "upgrades":            │   (upgrades now passed   │                   │
   │    ["websocket"]} ────────│    through unmodified)   │                   │
   │                           │                         │                   │
   │  Browser tries WS upgrade │                         │                   │
   │  → FAILS (PHP can't proxy WS)                      │                   │
   │  → Falls back to polling  │                         │                   │
   │                           │                         │                   │
   ├─ GET /socket.io/?sid=xxx ─►│── curl GET (hangs) ───►│── proxy ─────────►│
   │  (long-poll, waits ~25s)  │  (set_time_limit=300)   │  (holds open)     │
   │◄─ data ───────────────────│◄─────────────────────────│◄──────────────────│
   │                           │                         │                   │
   ├─ POST /socket.io/?sid=xxx►│── curl POST ───────────►│── proxy ─────────►│
   │  (sends pong/messages)    │  (session_write_close   │                   │
   │◄─ ok ─────────────────────│   prevents lock wait)   │                   │
```

**Critical settings that make this work:**
- `set_time_limit(300)` in proxy.php — without this, PHP-FPM kills the script at 30s
- `session_write_close()` — without this, GET and POST serialize through session lock
- Socket.io `pingInterval=30000`, `pingTimeout=60000` — server pings every 30s, client has 60s to respond

---

## 3. Key Files & What They Do

### Proxy Layer

| File | Purpose |
|------|---------|
| `/public_html/middleware/proxy.php` | **THE** reverse proxy. All /middleware/ traffic goes through this PHP script via curl to port 3001. |
| `/public_html/middleware/.htaccess` | Routes all requests to proxy.php. Sets CSP headers for IDE (unsafe-eval, blob:, ws:). Disables caching. |
| `/public_html/.htaccess` | Root Apache config (~350 lines). Security headers, WAF, URL rewrites. The middleware RewriteRule [P] was removed (it never worked). |

### Node.js Middleware

| File | Purpose |
|------|---------|
| `gocodeme/middleware/src/server.js` | Express entry point. Mounts routes, trusts loopback proxy, attaches WS upgrade handler. Port 3001. |
| `gocodeme/middleware/src/routes/launch.js` | POST /api/launch — spawns Theia, agent, file-sync processes. Checks PID liveness. Stores sessions in Redis. |
| `gocodeme/middleware/src/routes/ideProxy.js` | /ide/:port/* proxy — two proxy objects (HTTP + WS). Auth via cookie/query param. Injects Alfred widget HTML. 2630 lines. |
| `gocodeme/middleware/src/directadmin/client.js` | DirectAdmin API client (axios). Uses admin impersonation. Throttled error logging (3 per 60s window). |
| `gocodeme/middleware/src/directadmin/syncWorkspace.js` | Downloads customer websites from DA File Manager API into /tmp workspace. MAX_FILES=10000, CONCURRENCY=10. |

### Theia IDE

| File | Purpose |
|------|---------|
| `gocodeme/theia-fork/` | The Theia IDE installation (node_modules, plugins, frontend bundles). |
| `gocodeme/theia-fork/gocodeme-sandbox.js` | **Filesystem sandbox.** Loaded via `--require`. Patches Node.js fs module to restrict reads/writes to allowed prefixes. |
| `gocodeme/theia-fork/applications/browser/lib/backend/main.js` | Theia's main entry point. Launched by start-theia.sh. |
| `gocodeme/scripts/start-theia.sh` | 646-line bash script. Sets up env, sandbox, restricted shell, MCP config, Alfred personality, CSS patches, then `exec node` (replaces bash PID). |
| `gocodeme/logs/theia-<username>.log` | Theia stderr log. Check here for sandbox errors, connection cycling, crashes. |

### Engine Scripts (PM2 cron-like services)

| File | Purpose |
|------|---------|
| `scripts/agent-events-engine.js` | Manages agent events (fundraisers, competitions). Uses db-query.php for DB access. |
| `scripts/agent-social-engine.js` | Generates agent social posts. Uses db-query.php. |
| `scripts/agent-ecosystem-engine.js` | Agent ecosystem simulation. Uses db-query.php. |
| `scripts/agent-expansion-engine.js` | Agent expansion mechanics. Uses db-query.php. |
| `scripts/service-governance-engine.js` | Service governance. Already had --write flag detection. |
| `scripts/db-query.php` | **DB bridge** — Node scripts call `php db-query.php "SQL"` to query MySQL. Has table allowlist, keyword blocklist, --write flag. |

---

## 4. How the IDE Launches

### Step-by-step flow:

1. **User clicks "Open IDE"** on gocodeme.php
2. **Browser POSTs** to `https://gositeme.com/middleware/api/launch` with JWT token
3. **proxy.php** forwards to `http://127.0.0.1:3001/api/launch`
4. **launch.js** (middleware):
   a. Validates JWT session and active subscription
   b. Re-resolves DA username from Redis
   c. Checks Redis for existing sessions (`launch:sessions:<user>`)
   d. **NEW:** Verifies Theia PID is alive via `process.kill(pid, 0)` — if dead, clears sessions
   e. If sessions exist and alive: returns existing IDE URL with fresh auth token
   f. If no sessions: allocates port (sticky or next available), spawns:
      - `bash start-theia.sh <user> <workspace> <port>` (Theia IDE)
      - `node gocodeme-file-sync.js <user> <workspace>` (file sync daemon)
      - `bash start-agent.sh <user> <workspace> <agent-port> <jwt>` (AI agent)
   g. Stores session array in Redis with PIDs and URLs
   h. Returns IDE URL: `https://gositeme.com/middleware/ide/<port>/?gcm_auth=<token>`

5. **start-theia.sh** (646 lines):
   - Validates username (alphanumeric only, max 16 chars)
   - Loads env from middleware/.env (excludes PORT, DA username/client ID)
   - Sets `ANTHROPIC_BASE_URL` to middleware proxy for token tracking
   - Creates workspace directory
   - Resolves user's domains via DirectAdmin API
   - Creates symlinks to domain directories
   - Pre-creates `.claude/hooks/` directory
   - Writes Claude Code MCP config (settings.json with JWT auth)
   - Writes global IDE settings (AI features, language model aliases)
   - Writes Alfred AI personality instructions
   - Patches CSS bundles (dark theme, attachment UI)
   - Patches web.factory.js (exposes VS Code command API)
   - `exec node --require=gocodeme-sandbox.js main.js <workspace> --hostname=127.0.0.1 --port=<port>`
   - `exec` replaces bash PID so middleware's recorded PID matches the node process

6. **Browser loads** `https://gositeme.com/middleware/ide/4000/?gcm_auth=<token>`
   - proxy.php → middleware → ideProxy.js → Theia on port 4000
   - ideProxy.js validates auth token, sets `gcm_ide_auth` cookie
   - Theia HTML is served with injected Alfred widget + localStorage isolation
   - Socket.io connects (long-polling through proxy.php)
   - IDE initializes, file tree loads, editor ready

---

## 5. Socket.io Connection Lifecycle

Theia uses socket.io (Engine.IO v4) for all RPC communication between the
browser and the backend. This includes file operations, terminal, extensions,
AI chat, etc.

### Settings (from Theia's engine.io server):
- `pingInterval: 30000` (30 seconds) — server sends ping every 30s
- `pingTimeout: 60000` (60 seconds) — client must pong within 60s
- `maxPayload: 100000000` (100MB)

### Through the PHP Proxy:
1. Initial handshake: GET `/socket.io/?EIO=4&transport=polling` → returns `sid` + `upgrades:["websocket"]`
2. Browser attempts WebSocket upgrade → **fails** (PHP can't proxy WS)
3. Browser falls back to HTTP long-polling
4. Each poll: GET with `sid` → server holds connection open ~25s until data available
5. Client sends data: POST with `sid` → immediate response
6. Server ping (packet type 2): arrives in poll response
7. Client pong (packet type 3): sent as POST

### What Was Breaking (fixed March 9, 2026):
1. **proxy.php was stripping WebSocket upgrades** — `preg_replace('/"upgrades":\[.*?\]/', '"upgrades":[]', $response)` — this prevented even the initial WS attempt, causing socket.io to skip the upgrade logic entirely and behave differently
2. **PHP-FPM max_execution_time=30** — the long-poll takes ~25-30s, FPM killed the script at exactly 30s, dropping connections
3. **PHP session lock** — `session_start()` without `session_write_close()` blocked concurrent requests through the same session

---

## 6. The Sandbox (Security)

`gocodeme/theia-fork/gocodeme-sandbox.js` is loaded via `--require` before Theia starts.
It monkey-patches Node.js `fs` module to enforce access control.

### Three levels of access:

| Level | Prefixes | Operations |
|-------|----------|------------|
| **Browse** (readdir) | WORKSPACE_ROOT, THEIA_DIR | List directories |
| **Read** (readFile, stat) | WORKSPACE_ROOT, `/home/gositeme/domains/`, THEIA_DIR, `/proc/`, `/dev/null`, `/usr/`, `/lib/`, `/etc/`, `/bin/` | Read files, stat, access |
| **Write** (writeFile, mkdir, unlink) | WORKSPACE_ROOT, WORKSPACE_ROOT/.tmp, `/home/gositeme/domains/`, `/tmp/github-remote`, `/tmp/theia_upload` | Write, create, delete |

### Variables:
- `WORKSPACE_ROOT` = `/tmp/gocodeme-workspace-<username>` (parsed from argv)
- `USER_TMP` = `WORKSPACE_ROOT/.tmp`
- `THEIA_DIR` = path up to `/theia-fork` in the main script path

### What it blocks:
- Writing anywhere outside allowed prefixes → `EACCES`
- Browsing system directories → `EACCES`
- Reading sensitive files (`.env`, `config.php`, `database.php`, etc.) → blocklist at ~line 80

### Common sandbox errors:
- `EACCES: permission denied, access '/tmp/github-remote'` → add to `ALLOWED_WRITE_PREFIXES` (line ~107)
- `EEXIST: file already exists, mkdir '/tmp/theia_upload'` → directory already exists, non-fatal

---

## 7. PM2 Services

Config: `ecosystem.config.js` (root of public_html)

| Name | Port | Type | Purpose |
|------|------|------|---------|
| redis | 6379 | fork | Redis server |
| meilisearch | 7700 | fork | Search index (4GB+ RAM, set max_memory_restart to 6G) |
| alfred-ws | 3010 | cluster | WebSocket server for Alfred AI |
| alfred-jobs | — | cluster | Background job processor |
| alfred-mcp | — | cluster | MCP (Model Context Protocol) server |
| alfred-discord | — | cluster | Discord bot |
| gocodeme-middleware | 3001 | cluster | IDE middleware/proxy (Express) |
| alfred-heartbeat | — | fork | Health monitor |
| ollama | — | fork | Local LLM inference |
| agent-orchestrator | — | fork | Agent task orchestration |
| agent-social-engine | — | fork | Agent social posts (stopped) |
| agent-events-engine | — | fork | Agent events (stopped) |
| agent-ecosystem-engine | — | fork | Agent ecosystem sim (stopped) |
| agent-expansion-engine | — | fork | Agent expansion (stopped) |
| agent-content-engine | — | fork | Content generation (stopped) |
| service-governance-engine | — | fork | Service governance (stopped) |
| backup-scheduler | — | fork | Backup automation (stopped) |
| agentpedia-scheduler | — | fork | Agentpedia content (stopped) |

### PM2 Commands:
```bash
pm2 list                           # Show all services
pm2 logs <name> --lines 20        # View recent logs
pm2 restart <name>                 # Restart a service
pm2 stop <name>                    # Stop a service
pm2 delete <name> && pm2 start ecosystem.config.js --only <name>  # Reset restart counter
pm2 save                           # Persist current state
```

### Important: Meilisearch
- Uses 4GB+ RAM with 2.5GB of index data
- `max_memory_restart` was raised from 4G to 6G (March 2026)
- If it enters a crash loop, it's usually a port conflict from rapid restarts
- Fix: `pm2 stop meilisearch && sleep 2 && fuser -k 7700/tcp; sleep 1 && pm2 delete meilisearch && pm2 start ecosystem.config.js --only meilisearch`

---

## 8. Redis Keys

| Key Pattern | Type | TTL | Purpose |
|-------------|------|-----|---------|
| `launch:sessions:<username>` | string (JSON array) | none | Active Theia/agent/sync sessions with PIDs |
| `launch:sticky_port:<username>` | string | 30d | Last-used IDE port (reuse on next launch) |
| `launch:activity:<username>` | string | — | Last activity timestamp |
| `ide_auth_token:<uuid>` | string | 8h | Maps auth token → DA username |
| `access:<whmcsClientId>` | string | — | Subscription status (active/suspended) |
| `da_username:<whmcsClientId>` | string | — | Maps WHMCS client ID → DA username |

### Clear stale sessions:
```bash
redis-cli DEL "launch:sessions:<username>"
redis-cli DEL "launch:activity:<username>"
```
This forces a fresh Theia launch on next IDE access.

---

## 9. db-query.php (Node→PHP DB Bridge)

Located at `scripts/db-query.php`. Node.js engine scripts use this to query MySQL
because the PHP app owns the database connection and credentials.

### Usage:
```bash
php scripts/db-query.php "SELECT * FROM agent_profiles LIMIT 5"           # read-only
php scripts/db-query.php --write "UPDATE agent_events SET status='done'"   # write mode
```

### Security layers:
1. **CLI-only** — returns 403 if called via HTTP
2. **Read-only by default** — only SELECT without `--write` flag
3. **Keyword blocklist** — DROP, TRUNCATE, GRANT, REVOKE, SLEEP(), BENCHMARK() always blocked
4. **Table allowlist** — every referenced table must be in the `$allowedTables` array (~line 100)
5. **Pattern blocklist** — SQL comments (`--`, `/*`) blocked

### Adding a new table:
Edit `$allowedTables` array in `scripts/db-query.php` (~line 100). Tables must be lowercase.

### Common error:
`{"error":"Table not in allowlist: <table_name>"}` → Add the table to `$allowedTables`.

### Engine scripts calling db-query.php:
All engine scripts have a `dbQuery()` function that auto-detects write operations
and passes `--write` flag for INSERT/UPDATE/CREATE queries. If you create a new
engine script, use this pattern:
```javascript
function dbQuery(sql) {
    const args = [DB_HELPER];
    if (/^\s*(INSERT|UPDATE|CREATE)\s/i.test(sql)) args.push('--write');
    args.push(sql);
    const raw = execFileSync('php', args, { timeout: 30000 }).toString().trim();
    return JSON.parse(raw);
}
```

---

## 10. Known Gotchas & Past Bugs

### DirectAdmin Wipes httpd.conf
- **DO NOT** manually edit `/usr/local/directadmin/data/users/gositeme/httpd.conf`
- DirectAdmin regenerates it on any user config change (DNS, email, SSL renewal, etc.)
- The file is owned by `nobody` — user `gositeme` cannot write it
- Custom Apache directives (ProxyPass, etc.) WILL be lost
- **Workaround:** Use the PHP proxy (proxy.php) instead of Apache ProxyPass

### proxy.php — Session Lock Serialization
- PHP's `session_start()` acquires an exclusive lock on the session file
- If you don't call `session_write_close()`, concurrent requests from the same browser
  serialize through this lock
- Socket.io makes a GET (long-poll, 25s) and POST (pong) concurrently — the POST
  blocks waiting for the GET's session lock to release
- **Fix:** Call `session_write_close()` immediately after reading session data

### proxy.php — PHP-FPM max_execution_time
- PHP-FPM's `php.ini` has `max_execution_time = 30` (NOT unlimited like CLI)
- Socket.io long-polls last ~25-30s — FPM kills the script right at the boundary
- **Fix:** `set_time_limit(300)` at the top of proxy.php

### proxy.php — DO NOT Strip WebSocket Upgrades
- A previous version had `preg_replace('/"upgrades":\[.*?\]/', '"upgrades":[]', $response)`
- This was intended to prevent browser from attempting WebSocket (which fails through PHP)
- But it actually caused socket.io to behave differently and cycle connections every 90s
- **The correct behavior:** Let socket.io advertise WebSocket, browser tries WS → fails → falls back to polling gracefully

### launch.js — Dead PID Detection
- When Theia crashes, the stale session stays in Redis
- Subsequent launch requests return the dead session URL instead of spawning fresh
- **Fix (March 2026):** `process.kill(pid, 0)` check before returning existing sessions
- If PID is dead, clear Redis sessions and fall through to fresh launch

### gocodeme-sandbox.js — Adding Write Paths
- If Theia or its extensions need to write to a new `/tmp/` subdirectory, it will throw EACCES
- Fix: Add the path to `ALLOWED_WRITE_PREFIXES` array at ~line 107
- Currently allowed: WORKSPACE_ROOT, USER_TMP, `/home/gositeme/domains/`, `/tmp/github-remote`, `/tmp/theia_upload`

### start-theia.sh — exec Replaces Bash
- The script ends with `exec node ...` which replaces the bash process with node
- This means the PID that middleware recorded for the bash process IS the node process
- If you change this to not use `exec`, `killPid()` in launch.js will kill bash but not node

### Meilisearch Memory
- Uses 4GB+ RAM with 2.5GB of index data
- `max_memory_restart` must be higher than actual usage (set to 6G)
- Crash loops usually caused by port conflict from rapid PM2 restarts
- Fix: delete from PM2, kill port, re-add from ecosystem.config.js

---

## 11. Debugging Playbook

### IDE stuck on splash screen
1. Check if Theia is running: `ps aux | grep "main.js.*4000"`
2. If not running, check Redis: `redis-cli GET "launch:sessions:gositeme"`
3. If stale: `redis-cli DEL "launch:sessions:gositeme"` then re-launch
4. Check Theia logs: `tail -50 gocodeme/logs/theia-gositeme.log`
5. Look for sandbox EACCES errors → add path to ALLOWED_WRITE_PREFIXES
6. Check middleware logs: `pm2 logs gocodeme-middleware --lines 30`

### Socket.io connection cycling (90s loop)
1. Test direct to middleware: `curl -s http://127.0.0.1:3001/ide/4000/socket.io/?EIO=4&transport=polling`
   - Should return JSON with `"upgrades":["websocket"]`
2. Test through proxy: `curl -sk https://gositeme.com/middleware/ide/4000/socket.io/?EIO=4&transport=polling`
   - Should ALSO return `"upgrades":["websocket"]` (not `[]`)
3. Check proxy.php for any preg_replace on "upgrades"
4. Check PHP-FPM max_execution_time: `php -i | grep max_execution_time` (CLI shows 0, FPM is 30)
5. Verify proxy.php has `set_time_limit(300)` and `session_write_close()`

### Middleware crashing (PM2 restarts)
1. `pm2 logs gocodeme-middleware --lines 30 --nostream`
2. Common: `TypeError: Cannot read properties of undefined` — check launch.js null guards
3. Error flood: DA API 500 errors — throttled at client.js level (3 per 60s)
4. After fixing: `pm2 restart gocodeme-middleware`

### db-query.php "Table not in allowlist"
1. Check which table: the error message includes the table name
2. Add to `$allowedTables` array in `scripts/db-query.php` (~line 100)
3. `php -l scripts/db-query.php` to verify

### db-query.php "Only SELECT queries are allowed"
1. The engine script is sending INSERT/UPDATE without `--write` flag
2. Check the engine's `dbQuery()` function — it should auto-detect write queries
3. Pattern: `if (/^\s*(INSERT|UPDATE|CREATE)\s/i.test(sql)) args.push('--write')`

### Server load spike
1. `uptime` — check load average
2. `pm2 list` — check for crash loops (high restart count)
3. `fuser 7700/tcp` — check for meilisearch port conflicts
4. Previous incident: DA API flood caused load 1238 → fixed with error throttling

---

## 12. Server Constraints

| Constraint | Detail |
|------------|--------|
| **No sudo** | User `gositeme` has no root access |
| **No mod_proxy_http** | Apache compiled without it; can't use ProxyPass for HTTP |
| **DirectAdmin owns httpd.conf** | Manual edits get wiped; owned by `nobody` |
| **No Docker** | Everything runs as native processes under user `gositeme` |
| **Single server** | 15.235.50.60, 32GB RAM, all services colocated |
| **PHP 8.3 FPM** | Socket at `/usr/local/php83/sockets/gositeme.sock`, max_execution_time=30 (FPM) |
| **Node 20+** | `/usr/local/bin/node` |
| **Swap almost full** | 1.0G/1.0G — meilisearch is a heavy consumer |
| **SSL certs** | Managed by DirectAdmin, not readable by `gositeme` |

### File locations quick reference:
```
PHP FPM config:     /usr/local/php83/lib/php.ini  (max_execution_time=30)
Apache binary:      /usr/sbin/httpd
httpd.conf:         /usr/local/directadmin/data/users/gositeme/httpd.conf (DO NOT EDIT)
PM2 home:           /home/gositeme/.pm2/
PM2 logs:           /home/gositeme/.pm2/logs/
PM2 dump:           /home/gositeme/.pm2/dump.pm2
Redis data:         /home/gositeme/.local/redis/
Meilisearch data:   /home/gositeme/.local/meilisearch/data.ms/ (2.5GB)
Middleware .env:     /home/gositeme/domains/gositeme.com/public_html/gocodeme/middleware/.env
Theia logs:         /home/gositeme/domains/gositeme.com/public_html/gocodeme/logs/
Workspace:          /tmp/gocodeme-workspace-<username>/
```

---

## Appendix: ASCII Art of the Full System

```
                    ┌──────────────────────┐
                    │     USER BROWSER     │
                    │  gositeme.com:443    │
                    └──────────┬───────────┘
                               │ HTTPS
                    ┌──────────▼───────────┐
                    │   APACHE 2.4.66      │
                    │   (DirectAdmin)      │
                    │   SSL termination    │
                    └──────────┬───────────┘
                               │
              ┌────────────────┼────────────────┐
              │                │                │
     ┌────────▼─────┐  ┌──────▼──────┐  ┌──────▼──────┐
     │  PHP Pages   │  │  /api/*.php  │  │ /middleware/ │
     │  *.php       │  │  REST JSON   │  │  directory   │
     │  (FPM)       │  │  (FPM)       │  │             │
     └──────────────┘  └──────────────┘  └──────┬──────┘
                                                │
                                    ┌───────────▼───────────┐
                                    │  /middleware/.htaccess │
                                    │  RewriteRule → proxy  │
                                    └───────────┬───────────┘
                                                │
                                    ┌───────────▼───────────┐
                                    │    proxy.php          │
                                    │    PHP curl proxy     │
                                    │    set_time_limit=300 │
                                    │    session_write_close│
                                    └───────────┬───────────┘
                                                │ curl http://127.0.0.1:3001
                                    ┌───────────▼───────────┐
                                    │  Node.js Middleware   │
                                    │  Express :3001        │
                                    │  (PM2: gocodeme-      │
                                    │   middleware)         │
                                    └───────────┬───────────┘
                                                │ http-proxy
                              ┌─────────────────┼─────────────────┐
                              │                 │                 │
                     ┌────────▼──────┐  ┌───────▼──────┐ ┌───────▼──────┐
                     │  Theia IDE    │  │  File Sync   │ │  AI Agent    │
                     │  :4000+       │  │  daemon      │ │  :4001+      │
                     │  (sandboxed)  │  │  (DA API)    │ │  (Claude)    │
                     └───────────────┘  └──────────────┘ └──────────────┘
```

---

*This document was created after a multi-session deep-debugging effort that traced
the IDE splash screen hang to the PHP proxy chain. Every fix described here was
verified in production on March 9, 2026.*
