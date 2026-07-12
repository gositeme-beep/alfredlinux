# GoCodeMe

Browser-based AI coding platform built on Eclipse Theia + OpenHands + DirectAdmin.

## Project Structure

```
gocodeme/
├── middleware/          Node.js API — DirectAdmin bridge, SSO, token metering
├── mcp-server/         MCP HTTP server — Theia + OpenHands file system bridge
├── whmcs-module/       PHP WHMCS provisioning module
├── theia-fork/         (Week 3) Eclipse Theia fork — clone from github.com/eclipse-theia/theia-ide
├── openhands-fork/     (Week 5) OpenHands fork — clone from github.com/All-Hands-AI/OpenHands
├── openclaw/           (Week 11) Messaging gateway
├── nginx/              Reverse proxy config
├── docker-compose.yml  Full stack
└── docker-compose.env  Environment template → copy to .env
```

## Quick Start (Development — no Docker)

### 1. Prerequisites
- Node.js 20+
- Redis (compiled from source if no root: `make` in redis source dir)
- PHP 8.1+ (for WHMCS module testing)

### 2. Start Redis
```bash
~/redis-7.2.4/src/redis-server --daemonize yes --logfile ~/redis.log --dir /tmp
```

### 3. Configure middleware
```bash
cd middleware
cp .env.example .env
# Fill in DA_HOST, DA_ADMIN_USER, DA_ADMIN_PASS, JWT_SECRET, ANTHROPIC_API_KEY
# Leave WHMCS values blank for local dev
```

### 4. Install and start middleware
```bash
npm install
npm run dev        # nodemon — auto-restarts on change
```

### 5. Run the DA API proof-of-concept test
```bash
node src/test/da-api-test.js <directadmin_username>
```
Expected output: `ALL TESTS PASSED`. Do not proceed to Week 2 until this passes.

### 6. Install MCP server deps
```bash
cd ../mcp-server
npm install
```

---

## Production Deployment (Docker)

### Prerequisites
- Docker 24+ and Docker Compose v2
- SSL certificates in `nginx/ssl/fullchain.pem` and `nginx/ssl/privkey.pem`
- Domain `gocodeme.com` pointing to the server

### Deploy
```bash
cp docker-compose.env .env
# Edit .env — fill in all values
docker compose up -d
docker compose logs -f
```

### Verify
```bash
curl https://gocodeme.com/health
# → {"ok":true,"service":"gocodeme-middleware"}
```

---

## WHMCS Module Installation

1. Copy `whmcs-module/modules/servers/gocodeme/` to `<whmcs_root>/modules/servers/gocodeme/`
2. In WHMCS Admin → Setup → Products/Services → Servers → Add Server:
   - Hostname: `https://gocodeme.com` (or `http://localhost:3001` for dev)
   - Password: your `WHMCS_WEBHOOK_SECRET` value
3. Create a GoCodeMe product pointing to that server
4. Test with Admin → Servers → Test Connection

### WHMCS Product Setup
| Field | Value |
|-------|-------|
| Module | gocodeme |
| Plan (config option) | starter / professional / power / team / agency |
| Pricing | See plan tiers below |

### Plan Tiers
| Plan | Price | Tokens/month |
|------|-------|-------------|
| Starter | $99/mo | 300,000 |
| Professional | $150/mo | 600,000 |
| Power | $250/mo | 1,200,000 |
| Team (5 seats) | $500/mo | 2,500,000 |
| Agency (10 seats) | $800/mo | 5,000,000 |

---

## API Reference

### Authentication
All customer-facing routes require `Authorization: Bearer <session_token>`.  
Session tokens are issued at `POST /api/sso/login` with a WHMCS SSO JWT.

### Key Endpoints
| Method | Path | Description |
|--------|------|-------------|
| GET | `/health` | Health check |
| POST | `/api/sso/login` | WHMCS SSO → session token |
| GET | `/api/sso/me` | Current session info |
| GET | `/api/files/:username` | List directory |
| GET | `/api/files/:username/read?path=` | Read file |
| POST | `/api/files/:username` | Write file |
| DELETE | `/api/files/:username?path=` | Delete file |
| GET | `/api/tokens/usage` | Token usage |
| POST | `/api/git/:username/checkpoint` | Auto-commit |
| POST | `/api/git/:username/revert` | Undo last AI change |

### WHMCS Webhook Endpoints (require `X-WHMCS-Secret` header)
| Method | Path | Description |
|--------|------|-------------|
| POST | `/api/access/activate` | Provision on purchase |
| POST | `/api/access/suspend` | Suspend on failed payment |
| POST | `/api/access/unsuspend` | Restore on payment |
| POST | `/api/access/terminate` | Terminate on cancel |
| POST | `/api/tokens/provision` | Set plan token limit |
| POST | `/api/tokens/reset` | Reset on renewal |
| POST | `/api/tokens/topup` | Add top-up credits |

---

## Environment Variables

| Variable | Required | Description |
|----------|----------|-------------|
| `DA_HOST` | Yes | DirectAdmin URL e.g. `https://gositeme.com:2222` |
| `DA_ADMIN_USER` | Yes | DA admin username |
| `DA_ADMIN_PASS` | Yes | DA admin password — **server-side only, never in browser** |
| `JWT_SECRET` | Yes | 64+ char random string for session tokens |
| `ANTHROPIC_API_KEY` | Yes | Claude API key — **server-side only** |
| `WHMCS_API_IDENTIFIER` | Yes | WHMCS API credentials |
| `WHMCS_API_SECRET` | Yes | WHMCS API credentials |
| `WHMCS_WEBHOOK_SECRET` | Yes | Shared secret for WHMCS → middleware webhooks |
| `REDIS_URL` | Yes | Redis connection URL |

---

## Build Timeline

| Week | Task |
|------|------|
| 1–2 | DA API impersonation working, MCP server reading/writing files ✓ |
| 3–4 | Fork + rebrand Theia, connect to MCP bridge |
| 5–6 | Fork + rebrand OpenHands, connect to MCP bridge |
| 7–8 | Unified UI, Claude API locked, token counter |
| 9–10 | WHMCS module, SSO |
| 11–12 | OpenClaw messaging (WhatsApp/Telegram/Discord) |
| 13–14 | Git auto-commit, usage dashboard, onboarding |
| 15–16 | Testing, security audit, soft launch |

---

## Security Notes

- `DA_ADMIN_PASS` and `ANTHROPIC_API_KEY` must never appear in browser code or client responses
- Every API response is scoped to the authenticated user's DA account — cross-account access is blocked at the auth middleware layer
- Terminal execution (via git routes) uses an allowlist — only `init`, `add`, `commit`, `log`, `diff`, `revert`, `status`, `show` are permitted
- Path traversal is blocked in both the middleware (`fileManager.js`) and MCP server (`daClient.js`)
