# GoCodeMe — Infrastructure Runbook

**Last updated:** 2026-02-28
**Server:** OVH VPS — `15.235.50.60` (Debian/Ubuntu, DirectAdmin)
**SSH user:** `gositeme` (jailed shell, **no root/sudo**)
**DA admin panel:** `https://15.235.50.60:2222` (user: `seller1`)

---

## Table of Contents

1. [The Problem: Why Services Die](#1-the-problem-why-services-die)
2. [Architecture Overview](#2-architecture-overview)
3. [All Services — The Complete Map](#3-all-services--the-complete-map)
4. [How Requests Reach Node Services](#4-how-requests-reach-node-services)
5. [How to Start Everything (From Scratch)](#5-how-to-start-everything-from-scratch)
6. [How to Check If Everything Is Running](#6-how-to-check-if-everything-is-running)
7. [How to Stop Everything](#7-how-to-stop-everything)
8. [Making Services Survive VS Code Disconnect](#8-making-services-survive-vs-code-disconnect)
9. [Service Dependency Map](#9-service-dependency-map)
10. [Per-Service Deep Dive](#10-per-service-deep-dive) (incl. 10.9 Icecast, 10.10 Bridge, 10.11 TTS)
11. [Credentials & Secrets Location](#11-credentials--secrets-location)
12. [Ports In Use](#12-ports-in-use)
13. [Known Issues & Gotchas](#13-known-issues--gotchas)
14. [Emergency Procedures](#14-emergency-procedures)
15. [Glossary for New Managers](#15-glossary-for-new-managers)

---

## 1. The Problem: Why Services Die

### What was happening (FIXED 2026-02-28)

PM2 services used to die when VS Code disconnected. This was because:

1. PM2 was started from a VS Code terminal session
2. No `pm2 startup` systemd service was configured
3. No `loginctl enable-linger` — system killed user processes on SSH disconnect

### What was done to fix it

1. **`pm2 startup systemd`** installed → systemd service `pm2-gositeme.service` at `/etc/systemd/system/pm2-gositeme.service` runs `pm2 resurrect` on boot
2. **`loginctl enable-linger gositeme`** enabled → user processes survive SSH disconnect
3. **All services consolidated under PM2** — GoCodeMe (5) + SoundStudioPro (3) = 8 total processes
4. **Root crontab entries removed** — Icecast/Bridge no longer managed by root cron
5. **DA user crontab cleaned** — `start_services.sh` and broken TTS `@reboot` crons deleted

### The old result (no longer applies)

Every time you open VS Code → SSH → start PM2 → close VS Code:
- PM2 daemon dies (no linger)
- All 5 services die
- Redis data persists (RDB dump on disk), but the server process is gone
- Customer sessions, IDE launches, everything stops

### How to fix (needs OVH/DA admin — one-time)

Ask your hosting provider to run these **two commands as root**:

```bash
# 1. Install PM2 boot service for gositeme user
sudo env PATH=$PATH:/usr/bin /home/gositeme/domains/gocodeme.com/public_html/node_modules/pm2/bin/pm2 startup systemd -u gositeme --hp /home/gositeme

# 2. Allow gositeme's processes to survive logout
sudo loginctl enable-linger gositeme
```

After that, `pm2 save` will persist processes and they'll **auto-start on server reboot**.

### Temporary workaround (right now, no root needed)

Start services from a `screen` or `tmux` session instead of VS Code terminal:

```bash
ssh gositeme@15.235.50.60
screen -S gocodeme            # or: tmux new -s gocodeme
# Start PM2 inside screen (see Section 5)
# Detach: Ctrl+A, D (screen) or Ctrl+B, D (tmux)
# Reattach later: screen -r gocodeme
```

This way, closing VS Code doesn't kill the screen session, and PM2 stays alive.

---

## 2. Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                        INTERNET                              │
│                    gositeme.com (HTTPS)                       │
└──────────────┬──────────────────────────────────────────────┘
               │
               ▼
┌──────────────────────────────────────────────────────────────┐
│           DirectAdmin's Apache / Web Server                   │
│    (Managed by DA — we don't control this directly)           │
│                                                               │
│    /middleware/*  ──► ProxyPass to 127.0.0.1:3001             │
│    /middleware/ide/:port/*  ──► Proxy to 127.0.0.1:<port>    │
│    /middleware/agent/:port/*  ──► Proxy to 127.0.0.1:<port>  │
│    Everything else  ──► public_html (PHP, static files)       │
└────────┬──────┬──────┬──────┬──────┬─────────────────────────┘
         │      │      │      │      │
         ▼      │      │      │      │
┌─────────────┐ │      │      │      │
│ MIDDLEWARE   │ │      │      │      │
│ Port 3001   │◄┘      │      │      │
│ (Express)   │        │      │      │
│             │        │      │      │
│ Dashboard,  │        │      │      │
│ SSO, Tokens,│        │      │      │
│ File API,   │        │      │      │
│ Git, Launch,│        │      │      │
│ Billing,    │        │      │      │
│ Claude Chat │        │      │      │
└──────┬──────┘        │      │      │
       │               │      │      │
       ▼               ▼      │      │
┌─────────────┐ ┌──────────┐  │      │
│   REDIS     │ │MCP SERVER│  │      │
│ Port 6379   │ │Port 3005 │  │      │
│             │ │          │  │      │
│ Sessions,   │ │File ops  │  │      │
│ Tokens,     │ │via DA API│  │      │
│ Links,      │ │for Theia │  │      │
│ Onboarding  │ │+ Agent   │  │      │
└─────────────┘ └──────────┘  │      │
                              │      │
       ┌──────────────────────┘      │
       ▼                             ▼
┌──────────┐                  ┌──────────────┐
│OPENCLAW  │                  │ THEIA IDE    │
│Port 3004 │                  │ Port 4000+   │
│          │                  │ (per customer│
│Telegram, │                  │  even ports) │
│WhatsApp, │                  ├──────────────┤
│Discord,  │                  │ OPENHANDS    │
│SMS       │                  │ Port 4001+   │
│          │                  │ (per customer│
└──────────┘                  │  odd ports)  │
                              └──────────────┘
                                     │
                                     ▼
                              ┌──────────────┐
                              │ DirectAdmin  │
                              │ API :2222    │
                              │              │
                              │ Customer's   │
                              │ public_html  │
                              └──────────────┘
```

---

## 3. All Services — The Complete Map

### Always-Running Services (PM2-managed — 8 total)

| # | PM2 Name | What It Is | Port | Binds To | Language | Entry Point |
|---|----------|-----------|------|----------|---------|-------------|
| 1 | **redis** | In-memory data store | 6379 | 127.0.0.1 | C binary | `~/redis-7.2.4/src/redis-server ~/redis-7.2.4/redis.conf` |
| 2 | **gocodeme-middleware** | Main API + dashboard + IDE proxy | 3001 | 127.0.0.1 | Node.js | `middleware/src/server.js` |
| 3 | **mcp-server** | Model Context Protocol — file operations bridge | 3005 | 127.0.0.1 | Node.js | `mcp-server/src/mcpHttpServer.js` |
| 4 | **openclaw** | Messaging gateway (Telegram, WhatsApp, Discord) | 3004 | 127.0.0.1 | Node.js | `openclaw/src/server.js` |
| 5 | **gocodeme-scheduler** | Nightly housekeeping (workspace GC, log rotation) | — | — | Node.js | `scripts/scheduler.js` |
| 6 | **icecast** | Audio streaming relay (10k+ listeners) | 8000 | 127.0.0.1 | C binary | `/usr/bin/icecast2 -c .../stream_bridge/icecast.xml` |
| 7 | **stream-bridge** | DJ WebSocket → ffmpeg → Icecast | 8765 | 127.0.0.1 | Node.js | `.../stream_bridge/server.js` |
| 8 | **tts-server** | Text-to-speech (Coqui XTTS v2, ~3 GB RAM) | 5002 | 127.0.0.1 | Python | `~/.local/bin/tts-server` |

### On-Demand Services (spawned per customer via middleware `/api/launch`)

| # | Service | Port | Spawned By | Notes |
|---|---------|------|-----------|-------|
| 6 | **Theia IDE** | 4000, 4002, 4004... (even) | `scripts/start-theia.sh` | One instance per customer session |
| 7 | **OpenHands Agent** | 4001, 4003, 4005... (odd) | `scripts/start-agent.sh` | Paired with Theia, port = Theia+1 |

### Not a Service (deployed code, no process)

| # | Component | Where | Notes |
|---|-----------|-------|-------|
| 8 | **WHMCS Module** | `whmcs-module/modules/servers/gocodeme/` | PHP — runs inside WHMCS when customers order/SSO |
| 9 | **Theia fork (source)** | `theia-fork/` | Pre-built; binary at `applications/browser/lib/backend/main.js` |
| 10 | **OpenHands fork (source)** | `openhands-fork/` | Python 3.12.9 (pyenv); needs `poetry run` to launch |

### System Services (not ours — managed by DA/root)

| Service | Port | Our control? |
|---------|------|-------------|
| DirectAdmin | 2222 | No — hosting provider |
| Apache/Web Server | 80, 443 | No — DA managed |
| MySQL | 3306 | No — DA managed (⚠️ bound 0.0.0.0, needs firewall) |

---

## 4. How Requests Reach Node Services

```
Browser → https://gositeme.com/middleware/health
                                    │
                                    ▼
                    DA's Apache catches /middleware/*
                    ProxyPass → http://127.0.0.1:3001/health
                                    │
                                    ▼
                         Middleware Express app
                         responds with JSON
```

**The proxy is configured in DirectAdmin's Apache** (we have limited visibility — it was set up via DA admin panel or custom httpd config by the hosting provider).

Key routes:
- `https://gositeme.com/middleware/*` → `127.0.0.1:3001/*` (middleware)
- `https://gositeme.com/middleware/ide/:port/*` → middleware proxies to `127.0.0.1:<port>` (Theia)
- `https://gositeme.com/middleware/agent/:port/*` → middleware proxies to `127.0.0.1:<port>` (OpenHands)

The middleware itself acts as a **second-level proxy** for IDE and agent routes:
- `app.use('/ide/:port', createProxyMiddleware('ide'))` — proxies to Theia instances
- `app.use('/agent/:port', createProxyMiddleware('agent'))` — proxies to OpenHands instances

---

## 5. How to Start Everything (From Scratch)

**All paths relative to:** `/home/gositeme/domains/gositeme.com/public_html/gocodeme/`
(Also symlinked at: `/home/gositeme/public_html/gocodeme/`)

### One-shot startup script

```bash
#!/bin/bash
# Start ALL GoCodeMe services
# Run from: screen -S gocodeme  (to survive VS Code disconnect)

BASE=/home/gositeme/domains/gositeme.com/public_html/gocodeme
PM2=/home/gositeme/domains/gocodeme.com/public_html/node_modules/pm2/bin/pm2

# 1. Redis (must be first — everything depends on it)
$PM2 start $HOME/redis-7.2.4/src/redis-server \
  --name redis \
  --interpreter none \
  -- $HOME/redis-7.2.4/redis.conf

sleep 2  # Wait for Redis to be ready

# 2. Middleware (depends on Redis)
$PM2 start $BASE/middleware/src/server.js \
  --name gocodeme-middleware \
  --cwd $BASE/middleware

# 3. MCP Server (depends on middleware .env for config)
$PM2 start $BASE/mcp-server/src/mcpHttpServer.js \
  --name mcp-server \
  --cwd $BASE/mcp-server

# 4. OpenClaw messaging gateway (depends on Redis + Middleware)
$PM2 start $BASE/openclaw/src/server.js \
  --name openclaw \
  --cwd $BASE/openclaw

# 5. Scheduler (nightly housekeeping — optional, runs daily at 3AM)
$PM2 start $BASE/scripts/scheduler.js \
  --name gocodeme-scheduler \
  --cwd $BASE/scripts \
  --cron-restart "0 3 * * *"

# 6. Save process list for resurrect
$PM2 save

echo "All services started. Verify with: $PM2 list"
```

### Or just resurrect from saved state

If PM2 daemon is running but empty (like after a VS Code reconnect):
```bash
PM2=/home/gositeme/domains/gocodeme.com/public_html/node_modules/pm2/bin/pm2
$PM2 resurrect
```

---

## 6. How to Check If Everything Is Running

### Quick health check

```bash
PM2=/home/gositeme/domains/gocodeme.com/public_html/node_modules/pm2/bin/pm2

# PM2 process list
$PM2 list

# Quick curl tests
curl -s http://127.0.0.1:3001/health | python3 -m json.tool   # Middleware
curl -s http://127.0.0.1:3005/mcp/health | python3 -m json.tool  # MCP
curl -s http://127.0.0.1:3004/health | python3 -m json.tool   # OpenClaw
~/redis-7.2.4/src/redis-cli ping                               # Redis → PONG

# From outside (via proxy)
curl -s https://gositeme.com/middleware/health
```

### Full status check script

```bash
#!/bin/bash
echo "=== PM2 Processes ==="
/home/gositeme/domains/gocodeme.com/public_html/node_modules/pm2/bin/pm2 list

echo ""
echo "=== Port Status ==="
for port in 6379 3001 3004 3005; do
  if curl -s --max-time 2 http://127.0.0.1:$port/health > /dev/null 2>&1 || \
     ~/redis-7.2.4/src/redis-cli -p $port ping > /dev/null 2>&1; then
    echo "  Port $port: ✅ UP"
  else
    echo "  Port $port: ❌ DOWN"
  fi
done

echo ""
echo "=== External (via Apache proxy) ==="
STATUS=$(curl -s -o /dev/null -w "%{http_code}" https://gositeme.com/middleware/health)
echo "  https://gositeme.com/middleware/health → HTTP $STATUS"
```

---

## 7. How to Stop Everything

```bash
PM2=/home/gositeme/domains/gocodeme.com/public_html/node_modules/pm2/bin/pm2

# Stop all services but keep PM2 daemon alive
$PM2 stop all

# Or kill PM2 daemon entirely (all processes die)
$PM2 kill

# Kill specific service
$PM2 stop gocodeme-middleware
$PM2 restart gocodeme-middleware

# Kill orphan Theia/Agent instances (customer sessions)
pkill -f "main.js /tmp/gocodeme-workspace"
fuser -k 4000/tcp 4001/tcp 4002/tcp 4003/tcp 4004/tcp 4005/tcp 2>/dev/null
```

---

## 8. Making Services Survive VS Code Disconnect

### Option A: Root-level fix (best — ask hosting provider once)

```bash
# Run as root:
sudo env PATH=$PATH:/usr/bin /home/gositeme/domains/gocodeme.com/public_html/node_modules/pm2/bin/pm2 startup systemd -u gositeme --hp /home/gositeme
sudo loginctl enable-linger gositeme
```

Then as `gositeme`:
```bash
pm2 save   # saves the current process list
```

After this, services auto-start on server reboot and survive logout.

### Option B: Screen session (no root — works now)

```bash
# First time:
ssh gositeme@15.235.50.60
screen -S gocodeme
# ...start all PM2 services (Section 5)...
# Press Ctrl+A, then D to detach

# Later (after closing VS Code, reopening):
screen -r gocodeme   # reattach — PM2 is still running inside
```

### Option C: tmux (alternative to screen)

```bash
tmux new -s gocodeme
# ...start services...
# Ctrl+B, then D to detach
# Later: tmux attach -t gocodeme
```

### Option D: nohup + disown (least reliable)

```bash
nohup /home/gositeme/domains/gocodeme.com/public_html/node_modules/pm2/bin/pm2 resurrect &
disown
```

---

## 9. Service Dependency Map

```
Start order (things on the left must start before things on the right):

  Redis ──► Middleware ──► OpenClaw
       │         │
       │         ├──► Scheduler
       │         │
       │         └──► Theia/Agent (on-demand, via /api/launch)
       │
       └──► MCP Server
```

| Service | Depends On | What Breaks If This Dies |
|---------|-----------|-------------------------|
| **Redis** | Nothing | EVERYTHING — sessions, tokens, links, onboarding, conversations all in Redis |
| **Middleware** | Redis | Dashboard, SSO, file API, IDE launch, billing, all customer-facing features |
| **MCP Server** | Middleware .env (shared) | Theia/Agent can't read/write files via DA API |
| **OpenClaw** | Redis, Middleware | Telegram/WhatsApp/Discord messaging stops |
| **Scheduler** | PM2 | Workspace cleanup stops (not critical short-term) |
| **Theia** (on-demand) | Middleware (to launch), MCP (for file ops) | Customer's IDE stops |
| **OpenHands** (on-demand) | Middleware, MCP, Anthropic API key | AI agent stops |

---

## 10. Per-Service Deep Dive

### 10.1 Redis (Port 6379)

**What:** In-memory key-value store. Session store for everything.

**Binary:** `~/redis-7.2.4/src/redis-server` (compiled from source — no root needed)
**Config:** `~/redis-7.2.4/redis.conf`
**Data:** RDB snapshots in `~/domains/gositeme.com/public_html/gocodeme/dump.rdb`

**What's stored in Redis:**
| Key Pattern | Purpose | TTL |
|-------------|---------|-----|
| `access:<whmcsClientId>` | Account status (active/suspended/terminated) | Persistent |
| `tokens:<whmcsClientId>` | Token usage counter (used/limit/plan) | Persistent (reset monthly) |
| `launch:sessions:<daUsername>` | Active Theia/Agent session info (ports, PIDs) | Persistent |
| `onboarding:<daUsername>` | Onboarding wizard step progress | Persistent |
| `openclaw:user:<channel>:<userId>` | Channel link (Telegram/WA user → DA account) | Persistent |
| `openclaw:conv:<channel>:<userId>` | Conversation history | 7 days TTL, max 50 msgs |
| `billing:alert:80:<id>` | 80% token usage alert dedup | 35 days |
| `billing:alert:100:<id>` | 100% token usage alert dedup | 35 days |
| `billing:invoice:<id>` | Overage invoice ID for billing cycle | 35 days |

**Danger:** If Redis data is lost, customers lose their session state, token counts reset, and channel links break. The RDB file on disk provides crash recovery.

**Test:**
```bash
~/redis-7.2.4/src/redis-cli ping    # → PONG
~/redis-7.2.4/src/redis-cli info keyspace   # → shows DB stats
```

---

### 10.2 Middleware (Port 3001)

**What:** The brain of GoCodeMe. Express.js API server that handles everything.

**Entry point:** `middleware/src/server.js`
**Working dir:** `/home/gositeme/public_html/gocodeme/middleware/` (symlink) or
`/home/gositeme/domains/gositeme.com/public_html/gocodeme/middleware/`
**Config:** `middleware/.env`

**All routes:**
| Method | Path | Purpose |
|--------|------|---------|
| GET | `/health` | Health check |
| GET | `/dashboard` | Customer dashboard HTML |
| GET | `/usage` | Token usage page |
| GET | `/public/*` | Static assets (CSS, JS) |
| POST | `/api/sso/login` | WHMCS SSO → session token |
| GET | `/api/sso/me` | Current session info |
| GET/POST | `/api/sso/exchange` | Email/link SSO path |
| GET | `/api/tokens/usage` | Token usage data |
| POST | `/api/tokens/provision` | Set plan token limit (WHMCS) |
| POST | `/api/tokens/reset` | Reset on renewal (WHMCS) |
| POST | `/api/tokens/topup` | Add top-up credits (WHMCS) |
| POST | `/api/access/activate` | Provision on purchase (WHMCS) |
| POST | `/api/access/suspend` | Suspend on failed payment (WHMCS) |
| POST | `/api/access/unsuspend` | Restore on payment (WHMCS) |
| POST | `/api/access/terminate` | Terminate on cancel (WHMCS) |
| POST | `/api/launch` | Spawn Theia + Agent for customer |
| GET | `/api/launch/sessions` | List active sessions |
| POST | `/api/launch/stop` | Kill customer sessions |
| GET | `/api/files/:username` | List directory |
| GET | `/api/files/:username/read` | Read file |
| POST | `/api/files/:username` | Write file |
| DELETE | `/api/files/:username` | Delete file |
| POST | `/api/git/:username/checkpoint` | Git commit |
| POST | `/api/git/:username/revert` | Git revert |
| GET | `/api/git/:username/log` | Git log |
| GET | `/api/git/:username/status` | Git status |
| GET | `/api/git/:username/diff` | Git diff |
| POST | `/api/git/:username/init` | Init repo |
| GET | `/api/openclaw/link-token` | Generate linking token |
| POST | `/api/openclaw/link` | Link channel to account |
| GET | `/api/openclaw/stats/:daUsername` | Messaging stats |
| GET | `/api/openclaw/links/:daUsername` | Linked channels |
| POST | `/api/openclaw/send` | Send message via channel |
| GET | `/api/billing/usage-report` | Token + billing report |
| GET | `/api/billing/invoices` | WHMCS invoices |
| POST | `/api/billing/invoice` | Create overage invoice |
| POST | `/api/billing/reset-alerts` | Clear alert dedup keys |
| GET | `/api/billing/whmcs-client` | WHMCS client info |
| GET | `/api/onboarding/:username/status` | Onboarding step |
| POST | `/api/onboarding/:username/advance` | Next onboarding step |
| POST | `/api/onboarding/:username/complete` | Finish onboarding |
| POST | `/api/claude/:username/chat` | Claude AI chat (SSE streaming) |
| POST | `/api/anthropic-proxy/:daUsername` | Anthropic API proxy |
| — | `/api/da/*` | DirectAdmin operations |
| — | `/api/hosting/*` | Hosting management |
| — | `/api/usage/*` | Usage tracking + pricing |
| — | `/api/ai-terminal/*` | AI terminal sessions |
| — | `/api/templates/*` | Site templates |
| — | `/api/admin/*` | Admin routes |
| — | `/ide/:port/*` | Proxy → Theia instance |
| — | `/agent/:port/*` | Proxy → OpenHands instance |

**Logs:** `~/.pm2/logs/gocodeme-middleware-out.log` and `gocodeme-middleware-error.log`

---

### 10.3 MCP Server (Port 3005)

**What:** Model Context Protocol server. Bridges Theia IDE and OpenHands Agent to DirectAdmin's file system API.

**Entry point:** `mcp-server/src/mcpHttpServer.js`
**Config:** `mcp-server/.env` (symlink → `middleware/.env`)

**Endpoints:**
| Method | Path | Purpose |
|--------|------|---------|
| POST | `/mcp` | StreamableHTTP (primary transport) |
| GET | `/mcp` | SSE fallback transport |
| DELETE | `/mcp` | Close MCP session |
| GET | `/mcp/health` | Health check (`{"ok":true,"sessions":N}`) |

**MCP Tools (7):**
| Tool | DA API Call |
|------|-----------|
| `read_file` | `CMD_API_FILE_MANAGER?action=edit` |
| `write_file` | `CMD_API_FILE_MANAGER` (multipart upload) |
| `list_directory` | `GET CMD_API_FILE_MANAGER?path=<rel>` |
| `delete_file` | `action=multiple&button=delete` |
| `create_directory` | `action=newfolder` |
| `rename_file` | read+write+delete workaround (DA rename is broken) |
| `search_files` | Recursive directory walk + regex |

**Auth:** `Authorization: Bearer <JWT>` with `daUsername` claim.

---

### 10.4 OpenClaw (Port 3004)

**What:** Multi-channel messaging gateway. Lets customers control their hosting via Telegram, WhatsApp, Discord, SMS.

**Entry point:** `openclaw/src/server.js`
**Tmux session name:** `openclaw`

**Supported channels:**
| Channel | Protocol | Required Env Vars |
|---------|----------|-------------------|
| Telegram | Webhook + long-poll | `TELEGRAM_BOT_TOKEN` |
| WhatsApp | Meta Cloud API | `WHATSAPP_TOKEN`, `WHATSAPP_PHONE_ID`, `WHATSAPP_VERIFY_TOKEN` |
| Discord | HTTP interactions / slash | `DISCORD_BOT_TOKEN`, `DISCORD_APP_ID`, `DISCORD_PUBLIC_KEY` |

**Flow:**
1. Customer links their messaging account: sends `/link <token>` in their chat app
2. OpenClaw verifies token → stores mapping in Redis (`openclaw:user:<ch>:<id>`)
3. Future messages from that user routed to their agent (or direct Claude fallback)
4. Responses sent back to their messaging app

**Built-in commands:** `/help`, `/link <token>`, `/unlink`, `/reset`, `/status`

---

### 10.5 Scheduler

**What:** Nightly housekeeping. Runs daily at 3 AM via PM2 cron restart.

**Tasks:**
1. Clean up stale `/tmp/gocodeme-workspace-*` dirs (7+ days idle)
2. `pm2 save` (persist process list for resurrect)
3. Log rotation check

**Not critical** — if it doesn't run, old workspace dirs pile up in `/tmp`.

---

### 10.6 Theia IDE (On-Demand, Port 4000+)

**What:** VS Code-like browser IDE (Eclipse Theia fork, rebranded as "GoCodeMe IDE").

**Binary:** `theia-fork/applications/browser/lib/backend/main.js`
**Start script:** `scripts/start-theia.sh <da_username> <workspace_path> <port>`
**Plugins:** 70+ VS Code extensions in `theia-fork/plugins/`
**AI:** claude-sonnet-4-6, MCP config baked in (connects to `http://localhost:3005/mcp`)

**Port allocation:** Middleware's `POST /api/launch` finds the next free even port starting at 4000.

**How customers access it:**
```
WHMCS Client Area → "Open GoCodeMe Editor" button
  → SSO redirect → https://gositeme.com/middleware/dashboard#token=<jwt>
  → Click "Launch IDE"
  → POST /api/launch → spawns Theia on port 4004 + Agent on 4005
  → Dashboard shows iframe: /middleware/ide/4004/
```

---

### 10.7 OpenHands Agent (On-Demand, Port 4001+)

**What:** AI coding agent (OpenHands fork, rebranded as "GoCodeMe Agent").

**Runtime:** Python 3.12.9 via pyenv (`~/.pyenv/`), Poetry 2.3.2
**Start script:** `scripts/start-agent.sh <da_username> <workspace_path> <port> [jwt_token]`
**Config:** `openhands-fork/config.toml`
**Model:** `anthropic/claude-sonnet-4-6`

**Port:** Always Theia port + 1 (odd numbers: 4001, 4003, 4005...).
**Note:** Port is set via lowercase env var `port=3003` (not `--port`).

---

### 10.8 WHMCS Module

**What:** PHP provisioning module that connects WHMCS billing to GoCodeMe.

**Location:** `whmcs-module/modules/servers/gocodeme/gocodeme.php`
**Deploy to:** `<whmcs_root>/modules/servers/gocodeme/`

**WHMCS hooks:**
| Function | Triggers When | Calls |
|----------|-------------|-------|
| `gocodeme_CreateAccount` | Customer purchases | `POST /api/access/activate` |
| `gocodeme_SuspendAccount` | Payment fails | `POST /api/access/suspend` |
| `gocodeme_UnsuspendAccount` | Payment received | `POST /api/access/unsuspend` |
| `gocodeme_TerminateAccount` | Service cancelled | `POST /api/access/terminate` |
| `gocodeme_sso_redirect` | "Open Editor" click | `POST /api/sso/generate` → redirect |
| `gocodeme_AddonActivate` | Token top-up purchased | `POST /api/tokens/topup` |

### 10.9 Icecast (Port 8000) — SoundStudioPro

**What:** Open-source audio streaming server (like Shoutcast). Relays one incoming source stream to up to 10k+ listeners over HTTP/MP3.

**Domain:** `soundstudiopro.com` (not GoCodeMe — separate product, same server)
**Binary:** `/usr/bin/icecast2` (system-installed package)
**PM2 name:** `icecast`
**Config:** `/home/gositeme/domains/soundstudiopro.com/public_html/stream_bridge/icecast.xml` (generated from `icecast.xml.in` by `run_icecast.sh`)
**Config template:** `.../stream_bridge/icecast.xml.in`
**Passwords:** `.../stream_bridge/.icecast.env` (chmod 600, gitignored — source/relay/admin passwords)
**Logs:** PM2 logs (`pm2 logs icecast`) + `.../stream_bridge/icecast_logs/`
**Bind:** `127.0.0.1:8000` (local only — Apache reverse-proxies public access)
**Limits:** 10k clients, 4 sources, 60s source-timeout

**Managed by:** PM2 (since 2026-02-28). Previously ran via root crontab.

**PM2 start command used:**
```bash
$PM2 start /usr/bin/icecast2 --name icecast --interpreter none -- -c /home/gositeme/domains/soundstudiopro.com/public_html/stream_bridge/icecast.xml
```

### 10.10 Stream Bridge (Port 8766) — SoundStudioPro

**What:** Node.js WebSocket server that receives live audio from DJ mixer browsers, pipes through ffmpeg → HTTP PUT to Icecast. Each DJ gets a unique mount point `/live/{user_id}`.

**Domain:** `soundstudiopro.com`
**PM2 name:** `stream-bridge`
**Entry point:** `/home/gositeme/domains/soundstudiopro.com/public_html/stream_bridge/server.js`
**Port:** 8765 (HTTP, `BEHIND_PROXY=1`) — Apache handles SSL, proxies `wss://soundstudiopro.com/ws` → `ws://127.0.0.1:8765`
**Dependencies:** `ws` (WebSocket lib), ffmpeg (`~/bin/ffmpeg`)
**Security:** `.stream_secret` file — PHP signs WebSocket tokens, Bridge validates them to prevent stream hijacking
**Logs:** PM2 logs (`pm2 logs stream-bridge`)

**Managed by:** PM2 (since 2026-02-28). Previously ran via root crontab.

**Multi-DJ support:** `activeStreams` Map tracks per-user ffmpeg processes. Each DJ's WebSocket → own ffmpeg instance → own Icecast mount.

**PM2 start command used:**
```bash
cd /home/gositeme/domains/soundstudiopro.com/public_html/stream_bridge
BEHIND_PROXY=1 PORT=8765 $PM2 start server.js --name stream-bridge --cwd /home/gositeme/domains/soundstudiopro.com/public_html/stream_bridge
```

**Management scripts:**
| Script | Purpose |
|--------|---------|
| `start_services.sh` | Start Icecast + Bridge + watchdog (respects disabled flags) |
| `stop_services.sh` | Stop all + set disabled flag (prevents auto-restart) |
| `kill_services.sh` | Force-kill everything |
| `watchdog.sh` | Continuous loop — restarts crashed services every 30s |
| `cron_start_or_trigger.sh` | For cron: keepalive + honors admin panel Start button |
| `restart_bridge_now.sh` | Quick restart of just the bridge |
| `check_and_start.sh` | Pre-flight check then start |

**Admin panel control:** `admin.php?tab=live-stream` — Start/Stop buttons write `.start_now`/`.stop_requested` flag files; cron (or keepalive loop) picks them up within ~60s.

**Auto-restart flags:**
| File | Meaning |
|------|---------|
| `.services_disabled` | Admin clicked Stop — nothing auto-starts |
| `.icecast_disabled` | Only Icecast disabled |
| `.bridge_disabled` | Only Bridge disabled |
| `.start_now` | Admin clicked Start — cron/loop starts services on next iteration |

### 10.11 TTS Server (Port 5002) — SoundStudioPro

**What:** Coqui TTS (XTTS v2) — self-hosted multilingual text-to-speech server. Powers guided Hemi-Sync meditation sessions on soundstudiopro.com. Alternative backend to ElevenLabs (paid API).

**Domain:** `soundstudiopro.com`
**Binary:** `~/.local/bin/tts-server` (installed via pip, uses Python venv at `.../tts_venv/`)
**Model:** `tts_models/multilingual/multi-dataset/xtts_v2` (17 languages, EN + FR + 15 others)
**Port:** 5002
**API contract:** `POST /api/tts` — body: `text`, `speaker_id`, `language_id` → returns raw audio (wav/mpeg)
**PHP proxy:** `.../hemi-sync-tts.php` — proxies browser requests to TTS server or ElevenLabs based on config
**Config:** `.../hemi-sync-tts-config.php` (set `HEMI_SYNC_TTS_BACKEND=inhouse`, `HEMI_SYNC_TTS_INHOUSE_URL=http://localhost:5002`)
**Voices config:** `.../hemi-sync-tts-voices.php`
**Start script:** `.../scripts/start_hemi_sync_tts_server_background.sh`
**Logs:** `.../logs/tts_server.log`
**PID file:** `.../logs/tts_server.pid`

**Managed by:** PM2 (since 2026-02-28). Previously ran in a `screen` session.

**PM2 start command used:**
```bash
$PM2 start /home/gositeme/.local/bin/tts-server --name tts-server --interpreter none \
  --cwd /home/gositeme/domains/soundstudiopro.com/public_html \
  --env COQUI_TOS_AGREED=1 --env TTS_SKIP_FFMPEG_CHECK=1 \
  --env LD_LIBRARY_PATH="/lib/x86_64-linux-gnu:/usr/lib/x86_64-linux-gnu:/usr/local/lib" \
  -- --model_name "tts_models/multilingual/multi-dataset/xtts_v2" --port 5002 --no-use_cuda
```
**Memory:** ~3 GB (XTTS v2 model loaded in RAM)

**Known issues:**
- Model takes ~30s to load on startup
- Needs `pypinyin` for Chinese TTS (auto-installed by start script)
- `torchcodec` errors if FFmpeg shared libs missing → set `LD_LIBRARY_PATH` or use `TTS_SKIP_FFMPEG_CHECK=1`
- Speaker must be an XTTS preset name (e.g. "Ana Florence"), not "default"

### SoundStudioPro — How Everything Connects

```
┌─────────────────────────────────────────────────────────┐
│  soundstudiopro.com (same server: 15.235.50.60)         │
│                                                         │
│  Browser DJ Mixer ──WebSocket──► Bridge (:8765)         │
│                                    │                    │
│                                ffmpeg transcode         │
│                                    │                    │
│                              HTTP PUT /live/{uid}       │
│                                    ▼                    │
│                              Icecast (:8000)            │
│                                    │                    │
│              Apache reverse-proxy  │                    │
│                    ▼               ▼                    │
│  Listeners ◄──── stream.soundstudiopro.com/live         │
│                                                         │
│  Hemi-Sync page ──POST──► hemi-sync-tts.php            │
│                               │                        │
│                     ┌─────────┼─────────┐              │
│                     ▼                   ▼              │
│              TTS Server (:5002)   ElevenLabs API       │
│              (Coqui XTTS v2)      (fallback/paid)      │
└─────────────────────────────────────────────────────────┘
```

**File location:** All SoundStudioPro files are under:
`/home/gositeme/domains/soundstudiopro.com/public_html/`

---

## 11. Credentials & Secrets Location

**All GoCodeMe secrets are in ONE file:** `middleware/.env`

| Variable | Purpose | Never Expose To |
|----------|---------|----------------|
| `DA_ADMIN_USER` / `DA_ADMIN_PASS` | DirectAdmin admin API | Browser, logs, errors |
| `JWT_SECRET` | Signs all session tokens | Anywhere outside .env |
| `ANTHROPIC_API_KEY` | Claude AI API | Browser, client responses |
| `WHMCS_API_IDENTIFIER` / `WHMCS_API_SECRET` | WHMCS billing API | Browser |
| `WHMCS_WEBHOOK_SECRET` | Validates WHMCS → middleware calls | Browser |
| `TELEGRAM_BOT_TOKEN` | Telegram bot credentials | Browser |
| `WHATSAPP_TOKEN` / `WHATSAPP_PHONE_ID` | Meta WhatsApp API | Browser |
| `DISCORD_BOT_TOKEN` / `DISCORD_APP_ID` / `DISCORD_PUBLIC_KEY` | Discord bot | Browser |

**MCP Server** uses a symlink: `mcp-server/.env` → `middleware/.env`

**Redis:** No authentication configured (bound to 127.0.0.1, so localhost-only access).

**SoundStudioPro secrets** (separate from GoCodeMe):

| File | Contains |
|------|----------|
| `.../stream_bridge/.icecast.env` | Icecast source/relay/admin passwords (chmod 600, gitignored) |
| `.../stream_bridge/.stream_secret` | HMAC secret for WebSocket token auth (chmod 600) |
| `.../stream_bridge/.cron_start_key` | Cron authentication key (chmod 600) |
| `.../stream_bridge/icecast.xml` | Generated Icecast config with passwords substituted (gitignored) |
| `.../hemi-sync-tts-config.php` | ElevenLabs API key (if using paid TTS) |

(`...` = `/home/gositeme/domains/soundstudiopro.com/public_html`)

---

## 12. Ports In Use

| Port | Service | Bind Address | Public? | Notes |
|------|---------|-------------|---------|-------|
| 2222 | DirectAdmin | * | Yes | **DO NOT TOUCH** — DA admin panel |
| 3001 | Middleware | 127.0.0.1 | No (via Apache proxy) | Main API |
| 3004 | OpenClaw | 127.0.0.1 | No | Messaging gateway |
| 3005 | MCP Server | 127.0.0.1 | No | File ops bridge |
| 3306 | MySQL | 0.0.0.0 | ⚠️ Yes (needs fix) | DA managed |
| 4000+ | Theia IDE | 127.0.0.1 | No (via middleware proxy) | Even ports, per customer |
| 4001+ | OpenHands Agent | 127.0.0.1 | No (via middleware proxy) | Odd ports, per customer |
| 5002 | TTS (Coqui XTTS v2) | 127.0.0.1 | No | SoundStudioPro — Hemi-Sync TTS. PM2: `tts-server` |
| 6379 | Redis | 127.0.0.1 | No | Session store. PM2: `redis` |
| 8000 | Icecast | 127.0.0.1 | No | SoundStudioPro — audio streaming. PM2: `icecast` |
| 8765 | Stream Bridge (Node WS) | 127.0.0.1 | No (via Apache /ws proxy) | SoundStudioPro — DJ WebSocket → ffmpeg → Icecast. PM2: `stream-bridge` |

---

## 13. Known Issues & Gotchas

### Critical

1. **Services die when VS Code disconnects** — See Section 1. Need root to fix properly.

2. **MySQL 3306 bound to 0.0.0.0** — Responds to external connections (rejects with "not allowed" but port is reachable). Needs `bind-address = 127.0.0.1` in MySQL config. **Requires root.**

3. **No crontab access** — `/var/spool/cron` doesn't exist in the jailed shell. Can't use cron for keepalive.

4. **`loginctl` broken** — `libsystemd-shared-249.so` not available in jail. Can't use `loginctl enable-linger` from inside the jail.

### Moderate

5. **~~Bridge port 8765 bound to 0.0.0.0~~** — FIXED. Bridge now runs on 8766 with `BEHIND_PROXY=1`, bound to 127.0.0.1.

6. **PM2 dump cleaned** — Fresh dump saved 2026-02-28 with all 8 services, no stale VS Code env vars.

7. **Scheduler currently `stopped`** — Status was `stopped` in PM2. May need manual `pm2 start gocodeme-scheduler`.

8. **DA's `action=rename` is broken** — MCP server uses read+write+delete workaround for file rename operations.

9. **DA's `CMD_API_SYSTEM_EXECUTE` returns 405** — No shell exec permission. MCP search_files uses recursive directory walk instead.

### Minor

10. **Two path aliases for same dir** — `/home/gositeme/public_html/gocodeme/` is a symlink to `/home/gositeme/domains/gositeme.com/public_html/gocodeme/`. Some PM2 entries use one, some use the other. Both work.

11. **WHMCS module not yet deployed** — Files are in `whmcs-module/` but not copied to live WHMCS installation.

12. **`ANTHROPIC_API_KEY` needed for production** — Must be set to a real key for Claude chat and agent to function.

---

## 14. Emergency Procedures

### Everything is down (503 errors)

```bash
# SSH to server (not via VS Code if possible — use plain terminal)
ssh gositeme@15.235.50.60

# Option 1: In a screen session
screen -S gocodeme

# Check if PM2 daemon exists
PM2=/home/gositeme/domains/gocodeme.com/public_html/node_modules/pm2/bin/pm2
$PM2 list

# If empty list but dump exists:
$PM2 resurrect

# If resurrect fails, start manually:
$PM2 start ~/redis-7.2.4/src/redis-server --name redis --interpreter none -- ~/redis-7.2.4/redis.conf
sleep 2
$PM2 start ~/public_html/gocodeme/middleware/src/server.js --name gocodeme-middleware --cwd ~/public_html/gocodeme/middleware
$PM2 start ~/public_html/gocodeme/mcp-server/src/mcpHttpServer.js --name mcp-server --cwd ~/public_html/gocodeme/mcp-server
$PM2 start ~/public_html/gocodeme/openclaw/src/server.js --name openclaw --cwd ~/public_html/gocodeme/openclaw
$PM2 save

# Verify
curl -s http://127.0.0.1:3001/health
```

### One service is crashing in a loop

```bash
PM2=/home/gositeme/domains/gocodeme.com/public_html/node_modules/pm2/bin/pm2

# Check logs
$PM2 logs gocodeme-middleware --lines 50

# Restart just that service
$PM2 restart gocodeme-middleware

# If still failing, check .env
cat ~/public_html/gocodeme/middleware/.env | head -5
```

### Redis data lost

```bash
# Check if RDB dump exists
ls -la ~/public_html/gocodeme/dump.rdb
ls -la ~/domains/gositeme.com/public_html/gocodeme/dump.rdb

# Redis auto-loads from RDB on startup
# If RDB is gone, all session data is lost — customers need to re-login, re-link channels
```

### Customer's IDE stuck

```bash
# Kill their specific sessions
PM2=/home/gositeme/domains/gocodeme.com/public_html/node_modules/pm2/bin/pm2

# Kill specific ports (e.g., customer on 4004/4005)
fuser -k 4004/tcp 4005/tcp

# Or kill all Theia/Agent instances
pkill -f "main.js /tmp/gocodeme-workspace"

# Clean Redis session entry
~/redis-7.2.4/src/redis-cli DEL "launch:sessions:<da_username>"
```

---

## 15. Glossary for New Managers

| Term | What It Is |
|------|-----------|
| **PM2** | Process manager for Node.js. Keeps services running, restarts on crash. Like systemd but for Node. |
| **DirectAdmin (DA)** | Web hosting control panel (like cPanel). Manages Apache, PHP, MySQL, email, DNS. Runs on port 2222. |
| **Middleware** | Our custom Node.js API server. The central hub everything connects through. |
| **MCP** | Model Context Protocol — standardized way for AI tools to interact with file systems. Our MCP server translates AI requests into DirectAdmin API calls. |
| **OpenClaw** | Our messaging gateway. Connects Telegram/WhatsApp/Discord to the AI assistant. |
| **Theia** | Eclipse Theia — open source VS Code in the browser. We forked and rebranded it as "GoCodeMe IDE". |
| **OpenHands** | Open source AI coding agent. We forked it and configured it to use our MCP server for file access. |
| **WHMCS** | Billing/client management system. Handles orders, invoices, support tickets. Our module automates provisioning. |
| **Redis** | Fast in-memory database. Stores sessions, tokens, conversation history, account status. |
| **Tokens** | AI usage measurement. Each Claude API call consumes tokens. We meter and bill based on token usage. |
| **SSO** | Single Sign-On. Customers click "Open Editor" in WHMCS → redirected to GoCodeMe dashboard already logged in. |
| **pyenv** | Python version manager installed at `~/.pyenv/`. Required for OpenHands (Python 3.12.9). |
| **Poetry** | Python dependency manager. OpenHands uses it instead of pip. |
| **JWT** | JSON Web Token — signed tokens used for authentication between all services. |
| **DA impersonation** | Middleware logs into DA as `seller1` (reseller) and impersonates customer accounts via `seller1|<username>`. |

---

## File System Map

```
/home/gositeme/
├── .pm2/                          ← PM2 daemon files, logs, dump
│   ├── dump.pm2                   ← Saved process list (5 services)
│   ├── logs/                      ← All PM2 service logs
│   └── pm2.log                    ← PM2 daemon log
│
├── .pyenv/                        ← Python 3.12.9 (for OpenHands)
│
├── redis-7.2.4/                   ← Redis compiled from source
│   ├── src/redis-server           ← Redis binary
│   ├── src/redis-cli              ← Redis CLI
│   └── redis.conf                 ← Redis config
│
├── domains/gositeme.com/public_html/
│   └── gocodeme/                  ← PROJECT ROOT
│       ├── middleware/            ← Port 3001 — Main API
│       │   ├── .env               ← ALL SECRETS HERE
│       │   ├── src/server.js      ← Entry point
│       │   ├── src/routes/        ← All API route handlers
│       │   ├── src/billing/       ← WHMCS billing integration
│       │   ├── src/git/           ← Git operations
│       │   ├── public/            ← Dashboard HTML, CSS, JS
│       │   └── package.json
│       │
│       ├── mcp-server/            ← Port 3005 — MCP bridge
│       │   ├── .env → middleware/.env (symlink)
│       │   ├── src/mcpHttpServer.js  ← Entry point
│       │   └── src/daClient.js    ← DA API calls
│       │
│       ├── openclaw/              ← Port 3004 — Messaging
│       │   ├── src/server.js      ← Entry point
│       │   ├── src/adapters/      ← Telegram, WhatsApp, Discord
│       │   └── src/store/         ← Redis conversation store
│       │
│       ├── theia-fork/            ← IDE source + built output
│       │   ├── applications/browser/lib/backend/main.js ← Binary
│       │   └── plugins/           ← 70+ VS Code extensions
│       │
│       ├── openhands-fork/        ← AI agent source
│       │   ├── config.toml        ← Agent config (model, MCP URL)
│       │   └── pyproject.toml     ← Python dependencies
│       │
│       ├── whmcs-module/          ← WHMCS PHP module
│       │   └── modules/servers/gocodeme/gocodeme.php
│       │
│       ├── scripts/               ← Start/test scripts
│       │   ├── start-theia.sh
│       │   ├── start-agent.sh
│       │   ├── start-openclaw.sh
│       │   ├── scheduler.js
│       │   ├── systemd/           ← Service files (NOT INSTALLED)
│       │   └── test-*.sh          ← All test suites
│       │
│       ├── nginx/                 ← Nginx config (for Docker deploy)
│       ├── apache/                ← Apache proxy config
│       ├── docker-compose.yml     ← Docker deployment (alternative)
│       │
│       ├── STATUS.md              ← Build progress tracker
│       ├── README.md              ← Project overview
│       └── INFRASTRUCTURE_RUNBOOK.md ← THIS FILE
│
├── domains/soundstudiopro.com/public_html/
│   ├── stream_bridge/             ← Icecast + Bridge + watchdog
│   │   ├── server.js              ← Bridge entry point (WebSocket → ffmpeg → Icecast)
│   │   ├── icecast.xml            ← Generated Icecast config (DO NOT COMMIT)
│   │   ├── .icecast.env           ← Icecast passwords (chmod 600)
│   │   ├── .stream_secret         ← WebSocket auth HMAC secret
│   │   ├── start_services.sh      ← Master start (Icecast + Bridge + watchdog)
│   │   ├── watchdog.sh            ← Continuous health loop (30s)
│   │   └── icecast_logs/          ← Icecast + cron logs
│   ├── hemi-sync-tts.php          ← TTS proxy (PHP → Coqui or ElevenLabs)
│   ├── hemi-sync-tts-config.php   ← TTS backend config
│   ├── scripts/
│   │   └── start_hemi_sync_tts_server_background.sh
│   ├── logs/tts_server.log        ← TTS server logs
│   └── tts_venv/                  ← Python venv for Coqui TTS
│
├── public_html/                   ← Symlink → domains/gositeme.com/public_html
└── .vscode-server/                ← VS Code Remote SSH server files
```

---

## Quick Reference Card

```
PM2=/home/gositeme/domains/gocodeme.com/public_html/node_modules/pm2/bin/pm2
RCLI=~/redis-7.2.4/src/redis-cli

$PM2 list                          # Show all services
$PM2 resurrect                     # Restore saved services
$PM2 restart all                   # Restart everything
$PM2 logs <name> --lines 50       # View logs
$PM2 save                          # Save process list

$RCLI ping                         # Redis alive?
$RCLI info keyspace                # Redis stats
$RCLI keys "tokens:*"             # Check token data

curl -s localhost:3001/health      # Middleware health
curl -s localhost:3005/mcp/health  # MCP health
curl -s localhost:3004/health      # OpenClaw health
curl -s localhost:8765/health      # Stream Bridge health
curl -s localhost:5002/            # TTS server (returns HTML if alive)

# Systemd (PM2 auto-starts on boot — installed 2026-02-28):
# Service file: /etc/systemd/system/pm2-gositeme.service
# On boot: runs "pm2 resurrect" → restores all 8 saved processes
# loginctl enable-linger gositeme → processes survive SSH disconnect
```
