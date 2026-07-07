# Alfred Sovereignty — API Keys & Environment Setup Guide
> Complete instructions for every API key, secret, and service Alfred needs.

---

## Quick Start

```bash
# 1. Copy the template
cp .env.example .env

# 2. Generate your internal secret
echo "INTERNAL_SECRET=$(openssl rand -hex 32)" >> .env

# 3. Fill in the keys below (see sections for where to get each one)

# 4. Load environment (add to your shell profile or hosting panel)
source .env

# 5. Seed the database
php scripts/seed-sovereignty.php

# 6. Activate the cron (every minute)
crontab -e
# Add: * * * * * cd /home/gositeme/domains/gositeme.com/public_html && php scripts/autonomy-cron.php >> /dev/null 2>&1
```

For **DirectAdmin** hosting, set env vars in:
- **Setup PHP → Environment Variables** panel per domain
- Or in `.htaccess`: `SetEnv VARIABLE_NAME value` (one per line)

---

## 1. CORE / INTERNAL SECRETS

| Variable | Required | Description |
|----------|----------|-------------|
| `INTERNAL_SECRET` | **YES** | Secret for API-to-API internal calls. Used by cron, delegation, comm bus, orchestrator. |
| `OUTBOUND_SECRET` | No | Outbound API authentication secret. |
| `SEED_KEY` | No | Web access key for seed script (default: `alfred-sovereignty-2025`). |

**How to generate:**
```bash
openssl rand -hex 32
# Example output: a3f7b2c9d1e4f5a6b7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9
```

---

## 2. AI / LLM PROVIDERS

### Groq (Primary — FREE tier available)
| Variable | Required | Where to get it |
|----------|----------|-----------------|
| `GROQ_API_KEY` | **YES** | https://console.groq.com/keys |

- Free tier: 14,400 requests/day for most models
- Used by: Alfred chat, voice transcription, tool dispatch
- Models: llama-3.3-70b, mixtral-8x7b, whisper-large-v3

### OpenAI (Fallback + Embeddings)
| Variable | Required | Where to get it |
|----------|----------|-----------------|
| `OPENAI_API_KEY` | Recommended | https://platform.openai.com/api-keys |

- Used by: Deep research, creative writing, embeddings
- Cost: Pay-per-use, ~$0.01-0.03/1K tokens for GPT-4o

### OpenRouter (Multi-Model)
| Variable | Required | Where to get it |
|----------|----------|-----------------|
| `OPENROUTER_API_KEY` | Optional | https://openrouter.ai/keys |

- Aggregates 100+ models (Claude, GPT, Gemini, etc.)
- Useful for A/B testing different models via Learning Engine

### Ollama (Local LLM — Free, Self-Hosted)
| Variable | Default | Notes |
|----------|---------|-------|
| `OLLAMA_HOST` | `http://localhost:11434` | No API key needed! |

```bash
# Install Ollama
curl -fsSL https://ollama.com/install.sh | sh
# Pull a model
ollama pull llama3.2
ollama pull nomic-embed-text  # for embeddings
```

---

## 3. VOICE / TELEPHONY

### VAPI (Voice AI Platform)
| Variable | Required | Where to get it |
|----------|----------|-----------------|
| `VAPI_API_KEY` | **YES** (for voice) | https://dashboard.vapi.ai → API Keys |
| `VAPI_ASSISTANT_ID` | **YES** (for voice) | Dashboard → Assistants → copy ID |
| `VAPI_PHONE_ID` | For calls | Dashboard → Phone Numbers → copy ID |
| `VAPI_WEBHOOK_SECRET` | **YES** | Dashboard → Account → Webhook Secret |

- Set webhook URL: `https://gositeme.com/api/vapi-webhook.php`
- Enable: Call events, transcript events, tool calls

### Telnyx (SMS + SIP)
| Variable | Required | Where to get it |
|----------|----------|-----------------|
| `TELNYX_API_KEY` | For SMS/calls | https://portal.telnyx.com → API Keys |
| `TELNYX_CONNECTION_ID` | For calls | Portal → SIP Connections → copy ID |
| `TELNYX_FROM_NUMBER` | For SMS | Portal → Numbers → your number (E.164 format) |

---

## 4. CREATIVE / MEDIA

### Replicate (Image/Video Generation)
| Variable | Required | Where to get it |
|----------|----------|-----------------|
| `REPLICATE_API_TOKEN` | For creative | https://replicate.com/account/api-tokens |

- Models: Flux (images), SDXL, Stable Video, etc.
- Cost: ~$0.01-0.05 per generation

### Fal.ai (Fast Image Generation)
| Variable | Required | Where to get it |
|----------|----------|-----------------|
| `FAL_API_KEY` | For creative | https://fal.ai/dashboard/keys |

- Fastest Flux inference available
- Cost: ~$0.01 per image

### ElevenLabs (Voice Synthesis)
| Variable | Required | Where to get it |
|----------|----------|-----------------|
| `ELEVENLABS_API_KEY` | For TTS | https://elevenlabs.io → Profile → API Key |

- Free tier: 10,000 characters/month
- Used by: Voice cloning, text-to-speech

### Meshy (3D Asset Generation)
| Variable | Required | Where to get it |
|----------|----------|-----------------|
| `MESHY_API_KEY` | For 3D assets | https://app.meshy.ai → Settings → API |

- Used by: 3D Asset Pipeline (assets-3d.php)
- Generates 3D models from text prompts
- Rate limit enforced: 5 generations/day per user

---

## 5. SEARCH / DATA

### Brave Search (Web Search via MCP)
| Variable | Required | Where to get it |
|----------|----------|-----------------|
| `BRAVE_API_KEY` | For web search | https://brave.com/search/api/ |

- Free tier: 2,000 queries/month
- Used by: MCP client, web-search API

### Meilisearch (Instant Search)
| Variable | Default | Notes |
|----------|---------|-------|
| `MEILI_HOST` | `http://localhost:7700` | Self-hosted search engine |
| `MEILI_KEY` | (empty) | Master key — set during Meili startup |

```bash
# Install Meilisearch
curl -L https://install.meilisearch.com | sh
# Run with a master key
./meilisearch --master-key="YOUR_MEILI_MASTER_KEY"
```

---

## 6. MESSAGING / NOTIFICATIONS

### Telegram Bot
| Variable | Required | Where to get it |
|----------|----------|-----------------|
| `TELEGRAM_BOT_TOKEN` | **YES** | https://t.me/BotFather → /newbot |
| `TELEGRAM_WEBHOOK_SECRET` | Recommended | Any random string (you choose) |

```bash
# After creating the bot, set the webhook:
curl "https://api.telegram.org/bot<YOUR_TOKEN>/setWebhook?url=https://gositeme.com/api/telegram-bot.php&secret_token=<YOUR_SECRET>"
```

### Discord Bot
| Variable | Required | Where to get it |
|----------|----------|-----------------|
| `DISCORD_BOT_TOKEN` | For Discord | https://discord.com/developers/applications → Bot → Token |
| `DISCORD_APP_ID` | For Discord | Same page → General Information → Application ID |

- Enable intents: Message Content, Server Members, Presence
- Invite URL: `https://discord.com/api/oauth2/authorize?client_id=APP_ID&permissions=274877975552&scope=bot%20applications.commands`

### SendGrid (Email Delivery)
| Variable | Required | Where to get it |
|----------|----------|-----------------|
| `SENDGRID_API_KEY` | For email | https://app.sendgrid.com → Settings → API Keys |

- Used by: Job queue email worker, messaging gateway
- Verify sender domain first

### Push Notifications (VAPID)
| Variable | Required | How to generate |
|----------|----------|-----------------|
| `VAPID_PUBLIC_KEY` | For push | See command below |
| `VAPID_PRIVATE_KEY` | For push | See command below |
| `PUSH_SECRET` | For push | Any secret (default: `alfred-push-dev`) |

```bash
npx web-push generate-vapid-keys
# Output:
# Public Key: BNx...
# Private Key: a4F...
```

---

## 7. EXTERNAL INTEGRATIONS

### Composio (11,000+ Tool Aggregation)
| Variable | Required | Where to get it |
|----------|----------|-----------------|
| `COMPOSIO_API_KEY` | For tools | https://app.composio.dev → Settings → API Key |

- Connects to GitHub, Slack, Google, Salesforce, 400+ apps
- Each app requires OAuth connection via Composio dashboard

### GitHub (OAuth + MCP)
| Variable | Required | Where to get it |
|----------|----------|-----------------|
| `GITHUB_TOKEN` | For MCP | https://github.com/settings/tokens → Fine-grained token |
| `GITHUB_CLIENT_ID` | For OAuth | https://github.com/settings/developers → OAuth Apps |
| `GITHUB_CLIENT_SECRET` | For OAuth | Same page → Client Secret |

### Google (OAuth Login)
| Variable | Required | Where to get it |
|----------|----------|-----------------|
| `GOOGLE_CLIENT_ID` | For login | https://console.cloud.google.com → APIs & Services → Credentials |
| `GOOGLE_CLIENT_SECRET` | For login | Same page |

- Set authorized redirect: `https://gositeme.com/api/auth.php?action=google-callback`

---

## 8. VERTICAL / SPECIALTY APIs

| Variable | Required | Where to get it | Used by |
|----------|----------|-----------------|---------|
| `DEEPL_API_KEY` | For translation | https://www.deepl.com/pro-api | verticals.php |
| `WOLFRAM_APP_ID` | For computation | https://developer.wolframalpha.com/portal/myapps/ | verticals.php |
| `CANLII_API_KEY` | For legal | https://developer.canlii.org/ | verticals.php, vapi-tools |
| `LIBRETRANSLATE_URL` | For translation | Self-host or `https://libretranslate.com` | verticals.php |

---

## 9. BLOCKCHAIN / DeFi

| Variable | Required | Where to get it | Used by |
|----------|----------|-----------------|---------|
| `GSM_TREASURY_WALLET` | For token | Your Solana wallet public key | ssp-events.php |
| `GSM_TOKEN_MINT` | For token | Your SPL token mint address | ssp-events.php |

- DeFi API (defi.php) manages portfolio tracking — no additional keys needed
- Chain RPC endpoints are configured within the API (public defaults)

---

## 10. INFRASTRUCTURE

### Redis
| Variable | Default | Notes |
|----------|---------|-------|
| `REDIS_URL` | `redis://localhost:6379` | Used by WebSocket, job queue, pub/sub |

```bash
# Install Redis
sudo apt install redis-server
sudo systemctl enable redis-server
```

### WebSocket / Job Queue / MCP Secrets
| Variable | Default | Used by |
|----------|---------|---------|
| `WS_SECRET` | `alfred-ws-dev-secret` | websocket/server.js |
| `JOB_SECRET` | `alfred-job-dev-secret` | websocket/job-queue.js |
| `JOB_PORT` | `3011` | websocket/job-queue.js |
| `JOB_CONCURRENCY` | `5` | websocket/job-queue.js |
| `MCP_PORT` | `3005` | websocket/mcp-client.js |
| `MCP_SECRET` | `alfred-mcp-dev-secret` | websocket/mcp-client.js |
| `ALFRED_API_URL` | `https://gositeme.com` | websocket/discord-bot.js |

> **IMPORTANT:** Change all `*-dev-secret` values to real secrets in production!

### PostgreSQL (Optional — MCP Server)
| Variable | Required | Notes |
|----------|----------|-------|
| `POSTGRES_URL` | Optional | Only if using PostgreSQL MCP server |

---

## 11. HOSTING / ADMIN

| Variable | Required | Where to get it | Used by |
|----------|----------|-----------------|---------|
| `DA_ADMIN_PASSWORD` | For email mgmt | DirectAdmin admin password | email-api.php |
| `GOCODEME_BILLING_SECRET` | For billing | Internal billing webhook secret | pay/api/provision.php |
| `CRON_SECRET` | For crons | Any secret string | pay/scripts/cron-runner.php |
| `UPTIME_CRON_KEY` | For uptime | Any secret (default: `gositeme-uptime-2026`) | pay/api/uptime.php |
| `SITEDOCTOR_CRON_KEY` | For doctor | Any secret (default: `gositeme-doctor-2026`) | pay/api/site-doctor.php |

---

## Cron Jobs — Complete List

Add all these to `crontab -e`:

```bash
# ═══════════════════════════════════════════════════════════════════
# ALFRED SOVEREIGNTY — CRON JOBS
# ═══════════════════════════════════════════════════════════════════

# Alfred Autonomy Heartbeat — every minute
# Handles: agents, goals, treasury, feeds, self-healing, comm bus,
# DeFi alerts, invoice chasing, daily briefing, metaverse cleanup,
# experiment evaluation, orchestrator monitoring
* * * * * cd /home/gositeme/domains/gositeme.com/public_html && php scripts/autonomy-cron.php >> /dev/null 2>&1
```

### What the cron manages automatically:

| System | Frequency | What it does |
|--------|-----------|-------------|
| Agent Registry | Every minute | Recover error agents, unstick busy agents >30min |
| Task Queue | Every minute | Assign queued tasks to idle agents |
| Goals | Every minute | Flag overdue goals, trigger reviews |
| Treasury | Every minute | Check budget thresholds, send alerts |
| Feeds | Every minute | Poll stale RSS/API sources |
| Self-Healing | Every minute | Run health checks, auto-heal (disk/memory/CPU/service) |
| Comm Bus | Every minute | Send scheduled messages, fire proactive triggers |
| DeFi | Every minute | Check position alerts (APY, PnL thresholds) |
| Accounting | Every minute | Chase overdue invoices via comm bus |
| Daily Briefing | 8:00 AM UTC | Compile and send daily status briefing |
| Metaverse | Every 5 min | Set inactive players offline |
| Learning Engine | Hourly | Evaluate running A/B experiments |

---

## Priority Order — What to Set Up First

### Tier 1: Essential (Alfred won't function without these)
1. `INTERNAL_SECRET` — Generate immediately
2. `GROQ_API_KEY` — Free, 2 minutes to get
3. `TELEGRAM_BOT_TOKEN` — Free, 5 minutes with BotFather

### Tier 2: Core Features (Set up within first week)
4. `OPENAI_API_KEY` — Deep research, embeddings
5. `VAPI_API_KEY` + `VAPI_ASSISTANT_ID` + `VAPI_WEBHOOK_SECRET` — Voice calls
6. `SENDGRID_API_KEY` — Email delivery
7. `VAPID_PUBLIC_KEY` + `VAPID_PRIVATE_KEY` — Push notifications

### Tier 3: Enhanced (Set up within first month)
8. `TELNYX_API_KEY` — SMS + SIP telephony
9. `MESHY_API_KEY` — 3D asset generation
10. `REPLICATE_API_TOKEN` — Image/video generation
11. `ELEVENLABS_API_KEY` — Text-to-speech
12. `COMPOSIO_API_KEY` — 11,000+ tool connections
13. `BRAVE_API_KEY` — Web search
14. `DISCORD_BOT_TOKEN` — Discord integration

### Tier 4: Specialty (As needed)
15. `DEEPL_API_KEY` — Professional translation
16. `WOLFRAM_APP_ID` — Math/science computation
17. `CANLII_API_KEY` — Canadian legal research
18. `FAL_API_KEY` — Fast image generation
19. `OPENROUTER_API_KEY` — Multi-model access
20. `GITHUB_TOKEN` — GitHub MCP integration

---

## Verification Checklist

After setting your keys, verify each system:

```bash
# Test internal API connectivity
curl -s "https://gositeme.com/api/agent-registry.php?action=stats" \
  -H "X-Internal-Secret: YOUR_SECRET" | python3 -m json.tool

# Test Groq
curl -s "https://gositeme.com/api/alfred-chat.php?action=health" | python3 -m json.tool

# Test Telegram bot
curl -s "https://api.telegram.org/botYOUR_TOKEN/getMe" | python3 -m json.tool

# Test self-healing
curl -s "https://gositeme.com/api/self-healing.php?action=health" \
  -H "X-Internal-Secret: YOUR_SECRET" | python3 -m json.tool

# Run full seed
php scripts/seed-sovereignty.php

# Test cron manually
php scripts/autonomy-cron.php

# Check cron is installed
crontab -l | grep autonomy
```

---

*Last updated: March 2026 — Alfred Sovereignty Phase 4*
