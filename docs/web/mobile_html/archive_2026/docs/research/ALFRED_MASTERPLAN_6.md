# ALFRED UPGRADE MASTER PLAN 6 — "PROJECT GENESIS"
### The Final Audit: Deploy, Sovereignty, Token Launch, & Open-Source Arsenal
### Version 15.0 — March 6, 2026

---

## TABLE OF CONTENTS

1. [Executive Summary](#1-executive-summary)
2. [Complete Re-Audit: What's Real](#2-complete-re-audit)
3. [The ICO / Token Launch Plan](#3-ico-token-launch)
4. [Open-Source Arsenal — 85 Projects for Autonomy](#4-open-source-arsenal)
5. [SoundStudioPro Integration Status](#5-soundstudiopro)
6. [Sovereignty Assessment — The Planet Checklist](#6-sovereignty-assessment)
7. [The Deployment Gap: Built vs Running](#7-deployment-gap)
8. [Active Fires — What's Broken Right Now](#8-active-fires)
9. [Implementation Sequence](#9-implementation-sequence)
10. [Budget & Revenue Model](#10-budget-revenue)

---

## 1. EXECUTIVE SUMMARY

After an exhaustive re-audit of every file, API, document, log, config, service, token, and infrastructure component across the 9.5 GB workspace, here are the findings:

### The Big Numbers

| Metric | Count |
|--------|-------|
| Total PHP files | 18,990 |
| API endpoints | 132 files / 80,075 lines |
| Frontend pages | 55 |
| VAPI voice tool cases | 508 |
| Registered tools | 1,290+ (13,262+ with external) |
| Agents | 107+ (+ 33 Dawn Agents designed) |
| VR worlds | 14 |
| Database tables designed | 92+ |
| Research/plan docs | 24 / 25,284 lines |
| Docker services configured | 8 |
| Docker services running | **0** |
| Crontab entries | **0** |
| Automated tests | **0** |
| Running backups | **0** |
| GSM token on-chain | **NO — MySQL ledger only** |
| Sovereignty score | **33%** (19/58 capabilities live) |

### Three Priorities

1. **DEPLOY** — The built-to-running ratio is 4:1. Most infrastructure is code-complete but not started.
2. **TOKEN LAUNCH** — GSM exists as an off-chain MySQL ledger. The on-chain SPL token + ICO infrastructure needs to be built.
3. **OPEN-SOURCE ARSENAL** — 85 projects identified to replace $1,250-$4,200/mo in paid API dependencies with $90-$250/mo self-hosted alternatives.

---

## 2. COMPLETE RE-AUDIT: WHAT'S REAL

### 2.1 Document Inventory

| Document | Lines | Codename | Status |
|----------|------:|----------|--------|
| MASTERPLAN 1 | ~2,000 | Project Sentience | Plan complete |
| MASTERPLAN 2 | ~1,800 | Project Ignition | Plan complete |
| MASTERPLAN 3 | 3,599 | Project Phoenix | Plan complete |
| MASTERPLAN 4 | 1,667 | Project Sovereignty | Plan complete |
| MASTERPLAN 5 | 743 | Project Resurrection | Audit findings |
| **MASTERPLAN 6** | **THIS** | **Project Genesis** | **Active** |
| ORDER_BROTHERHOOD | 883 | Project Dawn | Architecture |
| FAILSAFE_OPERATIONS | 1,759 | — | Runbook |
| MISSING_TOOLS | 580 | — | Gap analysis |
| METAVERSE_DEEP_DIVE | 240 | Kingdom | Analysis |
| TOOL_REGISTRY | 428 | — | Active spec v2.0 |
| CONTENT_AUDIT | 681 | — | Audit findings |
| WHMCS_MIGRATION_PLAN | 404 | — | Mostly done |

Plus: OPEN_SOURCE_AUTONOMY_CATALOG.md (85 projects), SOVEREIGNTY_ASSESSMENT.md (58-capability audit)

### 2.2 What's ACTUALLY Running

| Component | Status | Evidence |
|-----------|--------|----------|
| Apache + PHP 8.3.30 | ✅ LIVE | Serving all 55 pages |
| MySQL 8 | ✅ LIVE | 92+ tables, DirectAdmin hosted |
| VAPI voice webhook | ✅ LIVE | 508 tool cases, production calls |
| Stripe payments | ✅ LIVE | Subscriptions, invoices, checkout |
| Solana crypto tools | ✅ LIVE | 20 tools — DEX quotes, wallet balance, portfolio (read-only) |
| Pay system | ✅ LIVE | 674 PHP files, 45 tables, most mature subsystem |
| SSL/HTTPS | ✅ LIVE | Let's Encrypt via DirectAdmin, HSTS preload |
| Shield DDoS/bot | ✅ LIVE | IP blocking, rate limiting, bot detection |
| PWA | ✅ LIVE | Service worker v3.1, push notifications, offline |
| Auth system | ✅ LIVE | PIN + multi-factor |
| API v1 rate limiting | ✅ LIVE | Per-key tiered (free/starter/pro/enterprise) |
| 14 VR worlds | ✅ LIVE | Three.js, browser-accessible |
| Blog/articles | ✅ LIVE | 10+ posts with  Schema.org markup |

### 2.3 What's BUILT But NOT Running

| Component | Lines | What's Needed |
|-----------|:---:|---|
| Docker stack (8 services) | 188 | `docker compose up -d` |
| WebSocket server | 722 | `pm2 start` |
| Job Queue (BullMQ) | 496 | Start Redis first |
| MCP Client | 668 | `pm2 start` |
| Discord Bot (100+ commands) | 361+ | `pm2 start` |
| Backup script (Restic → B2) | 141 | B2 creds + crontab |
| Autonomy cron heartbeat | ~200 | Add to crontab |
| Webhook dispatch (HMAC-SHA256) | ~150 | Wire to event sources |
| Meilisearch | config | Start + index tools |
| ChromaDB | config | Start + embed tools |
| alfred_schema.sql | ~400 | `mysql < config/alfred_schema.sql` |
| AI failover cascade | documented | Implement in callAlfred() |
| Conference room (LiveKit) | built | Deploy LiveKit server |
| Messaging gateway | built | Start WebSocket |

### 2.4 Tool Count Clarification

The "1,290+ tools" number needs context:

| Source | Count | Type |
|--------|:---:|---|
| VAPI switch cases | 508 | Live, dispatched via voice |
| Native tool registry | 170 | Hardcoded in $TOOL_REGISTRY |
| MCP Server | 807 | Routed when MCP is running |
| External MCP servers | 870+ | Community servers |
| Composio | 11,000+ | Third-party integrations |
| **Total native + MCP** | **~1,062** | **GoSiteMe's own tools** |
| **Total accessible** | **13,262+** | **Including all third-party** |

The 1,290+ number used on marketing pages is the native+MCP count before deduplication. Honest count of GoSiteMe-built tools: **~508-1,062** depending on how MCP tools are counted.

---

## 3. THE ICO / TOKEN LAUNCH PLAN

### 3.1 Current GSM Token Status

**The GSM token does NOT exist on Solana mainnet.** It is an off-chain MySQL ledger.

| Component | Status |
|-----------|--------|
| `GSM_TOKEN_MINT` | Empty string (`''`) |
| `GSM_TREASURY_WALLET` | Empty string (`''`) |
| Token design | ✅ Complete — 1B supply, 9 decimals, distribution defined |
| Token-launch admin page | ✅ Built (`pay/token-launch.php`) with 8-step roadmap |
| GSM balance/history tools | ✅ Working — MySQL-based ledger |
| 20 crypto tools in Alfred | ✅ Routing works — but 10/20 are DB-only, none execute on-chain |
| Solana RPC integration | ✅ Real — `getBalance`, `getTokenBalances`, `getTransaction` work |
| Jupiter DEX integration | ✅ Real — price queries and swap quotes work |
| On-chain trade execution | ❌ **Not implemented** — agents propose trades, but nothing executes |
| Wallet signature verification | ❌ **Stubbed** — comment says "verify ed25519 signature" |
| Smart contracts | ❌ **Zero** — no .sol files, no Anchor programs, no deployment scripts |

### 3.2 Token Architecture Decision

Two paths for the token launch:

#### Option A: GSM Utility Token (Simpler)

Single token. Utility within the GoSiteMe ecosystem.

```
GSM Token (Solana SPL)
├── In-ecosystem use: tool credits, marketplace, VR land, wagers
├── Staking tiers: Bronze/Silver/Gold/Platinum
├── KGD bridge: 1000 KGD = 1 GSM (3% fee)
├── NOT a security — pure utility, no profit promises
└── No ICO — earn through platform use, buy on DEX
```

**Regulatory risk:** LOW (utility token, no profit promises, no ICO)
**Revenue model:** Platform fees (0.3%-5% on transactions)

#### Option B: Dual Token System — GSM + NRG (Richer Economy)

Two tokens serving different purposes:

```
GSM (GoSiteMe Token) — Governance + Value Store
├── Fixed supply: 1,000,000,000 (1B)
├── Use: Staking, governance votes, premium features
├── Earn: Platform activity, agent performance
├── Trade: Jupiter DEX, Raydium liquidity pool
└── The "equity" of the ecosystem (but NOT a security)

NRG (Energy Token) — Transaction Fuel
├── Inflationary (minted per action, burned on use)
├── Use: Pay for AI calls, tool usage, compute time
├── Earn: Contributing data, teaching agents, giving
├── Burn: Every API call, every image gen, every voice minute
└── The "gas" of the ecosystem — ties to Garden Economy concept

BRIDGE:
  GSM ↔ NRG at dynamic rate (based on supply/demand)
  GSM ↔ SOL via Jupiter DEX
  NRG ↔ KGD at fixed rate (1000 KGD = 1 NRG)
```

**Regulatory risk:** MEDIUM (dual tokens need clear utility documentation)
**Revenue model:** NRG burn creates deflationary pressure on GSM value

#### Recommendation: **Start with Option A (GSM only)**, add NRG later if economy demands it.

### 3.3 GSM Token Deployment Steps

The `token-launch.php` admin page already has this roadmap. Here's the technical execution:

```
STEP 1: Create SPL Token on Solana Mainnet (~$2)
────────────────────────────────────────────────
Tools: @solana/spl-token CLI or Anchor program
  $ spl-token create-token --decimals 9
  → Returns: GSM_TOKEN_MINT address
  $ spl-token create-account <MINT_ADDRESS>
  → Returns: Treasury token account

STEP 2: Upload Metadata via Metaplex (~$1)
──────────────────────────────────────────
  - Name: "GoSiteMe Token"
  - Symbol: "GSM"
  - Image: upload to Arweave
  - Description: "Utility token for the GoSiteMe AI platform"
  $ metaboss create metadata -a <MINT> -m metadata.json

STEP 3: Mint 1B GSM to Treasury (~$0.02)
─────────────────────────────────────────
  $ spl-token mint <MINT_ADDRESS> 1000000000 <TREASURY_ACCOUNT>

STEP 4: Revoke Mint Authority (~$0.01)
──────────────────────────────────────
  $ spl-token authorize <MINT_ADDRESS> mint --disable
  → IRREVERSIBLE — proves fixed supply

STEP 5: Create Raydium GSM/SOL Liquidity Pool (~$756)
─────────────────────────────────────────────────────
  - Initial liquidity: ~$750 in SOL + equivalent GSM
  - Creates the market for trading
  - Sets initial price

STEP 6: Submit to Jupiter Verified Token List (Free)
────────────────────────────────────────────────────
  - PR to Jupiter's token list repo
  - Requires: metadata, liquidity, community validation

STEP 7: Airdrop to Early Users (~$35)
────────────────────────────────────
  - Map MySQL gsm_balances to on-chain airdrop
  - Scripts exist in token-launch.php

STEP 8: Update Platform Config (Free)
────────────────────────────────────
  - Set GSM_TOKEN_MINT env variable
  - Set GSM_TREASURY_WALLET env variable
  - All existing crypto tools auto-connect

TOTAL COST: ~$795
```

### 3.4 ICO / Token Sale Structure

**IMPORTANT: This is NOT financial or legal advice. Consult a securities lawyer before any public token sale.**

Three approaches, from lowest to highest regulatory risk:

#### Approach 1: Fair Launch (Lowest Risk) ✅ RECOMMENDED

No ICO. No presale. No promises of profit.

```
Distribution:
  30% Platform Reserve (locked 2 years, linear vest)
  25% Agent Rewards Pool (earned by agent performance)
  20% Community Airdrops (earned, not bought)
  15% Development Fund (team, locked 1 year cliff + 2 year vest)
  10% DEX Liquidity (Raydium/Jupiter LP)

How users get GSM:
  - Earn through platform use (tools, agent training, giving)
  - Buy on Jupiter DEX (open market, not from GoSiteMe)
  - Receive as agent rewards
  - KGD → GSM bridge (earn KGD in VR games → convert)
```

No marketing around price appreciation. No "invest in GSM" language.

#### Approach 2: Limited Token Sale (Medium Risk)

SAFT (Simple Agreement for Future Tokens) to accredited investors only.

```
Pre-sale: $50K-$200K raise
  - SAFT agreements (like SAFE but for tokens)
  - Accredited investors only
  - 6-month lock-up after token generation
  - Discount: 20-40% below public listing price
  - Cap: 5% of total supply

Public sale: Via Raydium LaunchPad or custom page
  - Fixed price, limited per wallet
  - KYC required (use Civic or similar)
  - Geofenced (exclude US if needed)
```

#### Approach 3: Full ICO (Highest Risk — NOT recommended for Canada)

Public token sale with marketing. Requires legal framework, possibly securities registration.

```
⚠️ Canadian securities law (CSA) treats most ICOs as securities offerings
⚠️ Ontario Securities Commission actively enforces against unregistered offerings
⚠️ Unless GSM clearly passes the Howey test as utility, this is a compliance minefield
```

### 3.5 What Needs to Be Built for Token Launch

| Component | Status | Work Needed |
|-----------|--------|-------------|
| SPL token creation | ❌ | CLI commands (~1 hour) |
| Metaplex metadata | ❌ | Upload image + JSON to Arweave |
| Raydium LP creation | ❌ | ~$756 in liquidity |
| Jupiter listing | ❌ | PR to token list repo |
| On-chain trade execution | ❌ | Anchor program or client-side signing |
| Wallet signature verification | ❌ | ~50 lines of PHP/JS |
| Airdrop script (MySQL → on-chain) | ❌ | ~200 lines of JS |
| Token vesting contract | ❌ | Anchor program (~500 lines Rust) |
| Staking contract | ❌ | Anchor program (~300 lines Rust) |
| Update crypto-config.php | ❌ | Set 2 env variables |
| Update invest.php (if adding token) | ❌ | Separate token section from equity SAFE |
| Legal review | ❌ | **Consult securities lawyer** |
| Squads multisig treasury | ❌ | Create 2-of-3 multisig wallet |

### 3.6 invest.php Audit Findings

The current invest.php is a **SAFE equity raise**, not a token sale.

**Issues found:**
- "OpenAI Partner" trust badge — misleading if just using the API (anyone can)
- Live stats counters start at $0 / 0 investors — animated up to hardcoded values
- "195+ Countries Served" — technically any website is accessible globally
- CONTENT_AUDIT flags testimonials as potentially fabricated
- Projected ROI tables (5-40x) are aggressive for a bootstrapped startup
- No disclosure of current paying customer count
- Fund allocation shown (40% engineering, 20% infra) but no quarterly reporting mechanism

**Recommendation:** Keep SAFE equity raise and token sale **completely separate**:
- invest.php = SAFE equity for company ownership (existing)
- New page for GSM token: utility explanation, how to earn, DEX link (NOT an "investment")

---

## 4. OPEN-SOURCE ARSENAL — 85 PROJECTS FOR AUTONOMY

Full catalog at: [OPEN_SOURCE_AUTONOMY_CATALOG.md](OPEN_SOURCE_AUTONOMY_CATALOG.md)

### 4.1 Summary by Category

| Category | Projects | Top Pick | Replaces | Est. Savings |
|----------|:---:|---|---|---|
| **A. LLM Inference** | 10 | vLLM + Ollama + Qwen 2.5 | Groq/OpenAI/Anthropic | $600-1,600/mo |
| **B. Voice AI** | 13 | LiveKit Agents + Faster-Whisper + Piper | VAPI ($0.05/min) | $500-2,000/mo |
| **C. Image Generation** | 6 | ComfyUI + FLUX | fal.ai/Replicate | $50-200/mo |
| **D. Music/Audio** | 5 | AudioCraft + Demucs | SoundStudioPro backend | $0 (new capability) |
| **E. Video Generation** | 6 | MoviePy + FFmpeg | OpenCut backend | $0 (new capability) |
| **F. Code AI** | 8 | Tabby + Qwen 2.5 Coder | OpenAI for GoCodeMe | $300-800/mo |
| **G. Search & RAG** | 10 | Meilisearch + ChromaDB + LlamaIndex | OpenAI embeddings | $20-100/mo |
| **H. Communication** | 9 | FreeSWITCH + Postal + Conduit | Telnyx + SendGrid | $70-300/mo |
| **I. DevOps** | 8 | Gitea + Coolify + n8n | GitHub + Vercel | $0-70/mo |
| **J. Blockchain** | 5 | Anchor + SPL Token | Token tooling | $0 |
| **K. Metaverse** | 7 | Colyseus + A-Frame | Game servers | $0 (new capability) |
| **L. Security** | 6 | Keycloak + CrowdSec | Custom auth | $0-100/mo |
| **M. Monitoring** | 6 | Uptime Kuma + Grafana + Prometheus | Paid monitoring | $50-200/mo |
| **N. Database** | 5 | Redis + MinIO | No caching/storage | $0-50/mo |
| **O. Workflow** | 5 | n8n + MCP Servers | Composio | $0-50/mo |
| **TOTAL** | **85** | | | **$1,100-3,950/mo saved** |

### 4.2 Top 10 Most Impactful for Alfred

| Rank | Project | Why | Effort |
|:---:|---|---|---|
| 1 | **Ollama** | Already in docker-compose. One command to self-host LLM. CPU fallback so Groq outage doesn't kill Alfred | 15 min |
| 2 | **vLLM + Qwen 2.5** | OpenAI-compatible API. Change one URL in callAlfred(). Replaces $600-1,600/mo in LLM costs | 1 day |
| 3 | **Redis** | Already in docker-compose. Unlocks WebSocket, job queue, caching, sessions | 15 min |
| 4 | **Meilisearch** | Already in docker-compose. Instant search for 1,290+ tools | 15 min |
| 5 | **LiveKit Agents** | Already using LiveKit for conferencing. Add Agents framework to replace VAPI entirely | 1 week |
| 6 | **Faster-Whisper** | 4x faster than Whisper. Runs on CPU. Self-hosted STT | 4 hours |
| 7 | **Piper/Kokoro** | Already using Kokoro via VAPI — self-host it, cut out VAPI. CPU-capable TTS | 4 hours |
| 8 | **Colyseus** | Real multiplayer game server. Turns VR worlds from single-player demos to live spaces | 1 week |
| 9 | **ComfyUI + FLUX** | Self-hosted image generation. Needs GPU | 1 day (if GPU available) |
| 10 | **AudioCraft** | MusicGen for SoundStudioPro backend. Text-to-music | 1 day (if GPU available) |

### 4.3 GPU Strategy

**Current server: CPU-only.** For AI sovereignty, need GPU access.

| Option | Cost | GPU | Best For |
|--------|------|-----|----------|
| **RunPod Serverless** | $0.00036/sec (A100) | A100 80GB | Pay-per-inference, no idle cost |
| **RunPod Community** | $0.20-0.70/hr | A100 40-80GB | 24/7 vLLM serving |
| **Vast.ai** | $0.10-0.40/hr | RTX 4090 | Cheapest GPU, dev/testing |
| **Self-purchase RTX 4090** | $1,500 one-time | 24GB VRAM | Pays for itself in ~2 months vs API costs |
| **Hetzner GPU** | €2.49/hr | L40S 48GB | EU-based, reliable |

**Recommended:** Start with RunPod Serverless ($0 when idle) + Ollama on CPU for fallback.

---

## 5. SOUNDSTUDIOPRO INTEGRATION STATUS

### 5.1 What Exists

| Component | Location | Status |
|-----------|----------|--------|
| VR DJ Studio | `vr/dj-studio/index.html` (2,700 lines) | ✅ LIVE — Full Three.js nightclub with dual decks, crossfader, EQ, spatial audio, WASD walking |
| SSP Music API | `api/ssp-music.php` | ✅ LIVE — 40+ tracks, 10 artists, genre browsing, leaderboard |
| SSP Events API | `api/ssp-events.php` | ✅ LIVE — Event ticketing with SOL/GSM/fiat, 5 tiers, 85/10/5 artist/platform/venue split |
| SSP Gospel API | `api/ssp-gospel.php` | ✅ Built — Faith-themed events/music |
| DJ World branding | VR lobby | ✅ "GoSiteMe × SoundStudioPro.com" — links to external domain |
| Live Event Bridge | v119.2 changelog | ✅ DJ mixing on SSP streams into VR world in real-time |
| Email integration | `includes/sites-created-counter.inc.php` | ✅ Sends to `admin@soundstudiopro.com` |

### 5.2 What's Missing for SoundStudioPro

| Gap | Solution | Open-Source Tool |
|-----|----------|-----------------|
| AI music generation | Text-to-music backend | **AudioCraft (MusicGen)** — MIT license, 22K stars |
| Source separation (stems) | Split vocals/drums/bass | **Demucs** — MIT license, 8K stars |
| Audio effects processing | Reverb, EQ, compression | **Pedalboard (Spotify)** — GPL-3.0, 5K stars |
| Audio streaming to VR | Live DJ stream transcoding | **FFmpeg** + WebRTC/WebSocket |
| Sound effects generation | Text-to-sound-effects | **Stable Audio Open** — 2K stars |
| Multiplayer DJ sessions | Multiple DJs in one room | **Colyseus** — game server for state sync |

### 5.3 SoundStudioPro as VR Game

The DJ Studio already IS a VR game — you walk around a 3D nightclub, approach speakers (spatial audio gets louder), control DJ decks, trigger effects. To make it a proper **multiplayer** VR game:

1. Add Colyseus for multiplayer state sync (see others' avatars)
2. Add AudioCraft for AI-generated beats (type prompt → music plays)
3. Add crowd simulation (NPCs that react to music BPM)
4. Add competition mode (DJ battles, crowd scoring)
5. Add VR item shop (custom decks, skins, effects — paid with GSM/KGD)

---

## 6. SOVEREIGNTY ASSESSMENT — THE PLANET CHECKLIST

Full assessment at: [SOVEREIGNTY_ASSESSMENT.md](SOVEREIGNTY_ASSESSMENT.md)

### 6.1 Current Score: 33% Sovereign (19/58 capabilities live)

| Domain | Live/Total | Score | Strongest Asset |
|--------|:---:|:---:|---|
| Communication | 0/6 | 🔴 0% | Messaging gateway built, just not running |
| Compute | 0/6 | 🔴 0% | Docker config ready, zero services running |
| Data | 1/6 | 🟡 17% | MySQL live, Redis/Meili/Chroma built but off |
| Identity | 2/5 | 🟡 40% | Auth + SSL working |
| Financial | 4/6 | ✅ 67% | Stripe + Solana + Pay system + Jupiter DEX |
| Content | 1/5 | 🟡 20% | Blog live, landing pages for open-source |
| Network | 4/6 | ✅ 67% | DNS + domains + SSL + Shield |
| Intelligence | 6/8* | ⚠️ 75%* | *ALL via external APIs — 0% self-hosted* |
| Governance | 0/6 | 🔴 0% | Zero monitoring, zero tests, zero backups |
| Entertainment | 1/4 | 🟡 25% | 14 VR worlds live |

### 6.2 THE CRITICAL FINDING

> **100% of Alfred's intelligence runs on paid external APIs.**
> Groq goes down = Alfred can't think. VAPI goes down = Alfred can't speak.
> This is a $1,250-$4,200/mo dependency that could be $90-$250/mo self-hosted.

### 6.3 Path to 84% Sovereign (8 Actions)

| # | Action | Autonomy Gain |
|:---:|---|:---:|
| 1 | `docker compose up -d` (6 services) | +15% |
| 2 | Pull Ollama model (llama3.1:8b CPU fallback) | +8% |
| 3 | Rent A100 + deploy vLLM with Qwen 2.5 | +20% |
| 4 | Set up cron + Restic backup | +5% |
| 5 | Deploy Uptime Kuma (monitoring) | +3% |
| 6 | Deploy Postal (self-hosted email) | +3% |
| 7 | Add Cloudflare free CDN | +3% |
| 8 | Deploy Faster-Whisper + Piper (STT/TTS) | +5% |
| | **TOTAL** | **+62%** → **~84%** |

### 6.4 What "A Planet Needs" — Full Sovereign Stack

```
┌─────────────────── THE SOVEREIGN STACK ───────────────────┐
│                                                            │
│  LAYER 7 — INTELLIGENCE                                    │
│  vLLM/Ollama (LLM) + Faster-Whisper (STT) + Piper (TTS)  │
│  ComfyUI (images) + AudioCraft (music) + Tabby (code)     │
│  ChromaDB (vectors) + Meilisearch (search) + LlamaIndex   │
│                                                            │
│  LAYER 6 — APPLICATION                                     │
│  508 VAPI tools + 107 agents + 14 VR worlds               │
│  Pay system + Marketplace + Fleet management               │
│  Order of the New Dawn + Brotherhood + Essence Cards       │
│                                                            │
│  LAYER 5 — COMMUNICATION                                   │
│  LiveKit Agents (voice) + Matrix/Conduit (chat)            │
│  FreeSWITCH (telephony) + Postal (email)                   │
│  WebSocket (real-time) + PWA Push (notifications)          │
│                                                            │
│  LAYER 4 — ECONOMY                                         │
│  Stripe (fiat) + GSM SPL Token (crypto)                    │
│  Jupiter DEX (trading) + Squads (treasury)                 │
│  KGD (in-game) + NRG (compute fuel, future)                │
│                                                            │
│  LAYER 3 — DATA                                            │
│  MySQL (primary) + Redis (cache) + MinIO (objects)         │
│  ChromaDB (vectors) + Restic → B2 (backups)                │
│                                                            │
│  LAYER 2 — INFRASTRUCTURE                                  │
│  Docker (containers) + PM2 (processes) + Cron (schedule)   │
│  Caddy (reverse proxy) + Cloudflare (CDN)                  │
│  Gitea (code) + Coolify (PaaS) + n8n (workflows)          │
│                                                            │
│  LAYER 1 — SECURITY & IDENTITY                             │
│  Keycloak (SSO) + Shield (DDoS) + CrowdSec (IPS)          │
│  Infisical (secrets) + Let's Encrypt (TLS)                 │
│  Grafana+Prometheus (monitoring) + Sentry (errors)         │
│                                                            │
│  LAYER 0 — HARDWARE                                        │
│  VPS (CPU) + RunPod GPU + Backblaze B2 (storage)           │
│  SIP Trunk (telephony) + Domain (eNom)                     │
│                                                            │
└────────────────────────────────────────────────────────────┘
```

The metaverse/autonomy documents already cover most of this. What's missing:
- **SIP telephony** (not mentioned in any doc)
- **Object storage** (MinIO — not in any doc)
- **Self-hosted email** (Postal — mentioned as TODO only)
- **Multiplayer game server** (Colyseus/Nakama — not in any doc)
- **Physical layer** details (GPU rental strategy, bandwidth planning)

---

## 7. THE DEPLOYMENT GAP: BUILT VS RUNNING

### 7.1 The Core Problem

```
BUILT:    ██████████████████████████████████████████  85%
RUNNING:  ████████████░░░░░░░░░░░░░░░░░░░░░░░░░░░░  33%
                      ↑
              THE DEPLOYMENT GAP (52%)
```

### 7.2 What Would Change With Just Deployments (No New Code)

| Action | Services Activated | New Capabilities |
|--------|---|---|
| `docker compose up -d` | Redis, Meilisearch, ChromaDB, Caddy | Caching, search, vectors, reverse proxy |
| `pm2 start ecosystem.config.js` | WebSocket, Job Queue, MCP Client, Discord Bot | Real-time, async processing, tool federation, community |
| `crontab -e` (add 3 entries) | Autonomy heartbeat, backups, billing | Self-maintenance, data protection, error reduction |
| `mysql < alfred_schema.sql` | 17 AI tables | Consciousness, memory, learning, agent registry |

**4 commands. Zero code changes. Transforms the platform.**

---

## 8. ACTIVE FIRES — WHAT'S BROKEN RIGHT NOW

| # | Fire | Severity | Fix |
|:---:|---|:---:|---|
| 1 | **billing-errors.log** — 5,768 lines, ~400+ errors/day | 🔴 CRITICAL | Refresh DirectAdmin API credentials |
| 2 | **Zero running backups** — production payments/crypto with no backup | 🔴 CRITICAL | `scripts/backup.sh` + crontab |
| 3 | **Zero crontab entries** — nothing automated | 🔴 CRITICAL | Add autonomy heartbeat + backup + billing crons |
| 4 | **Hardcoded DB password** in `includes/db-config.inc.php` | 🔴 CRITICAL | Move to env variable, rotate password |
| 5 | **4 files with sk- API keys** in source code | 🟠 HIGH | Move to .env, rotate keys |
| 6 | **Docker-compose dev secrets** as defaults | 🟠 HIGH | Set real secrets before deploying |
| 7 | **"OpenAI Partner" badge** on invest.php | 🟡 MEDIUM | Remove or clarify "OpenAI API User" |
| 8 | **Testimonials flagged as fabricated** | 🟡 MEDIUM | Replace with real customer quotes or remove |
| 9 | **Schema mismatches** (`first_name`, `da_username`) | 🟡 MEDIUM | Fix 2 SQL queries |
| 10 | **GSM token presented as live** in some UIs | 🟡 MEDIUM | Clearly mark as "Coming Soon" until mainnet |

---

## 9. IMPLEMENTATION SEQUENCE

### Phase 0 — TODAY: Emergency Fixes

```
□ Fix DirectAdmin API credentials (stops 400 errors/day)
□ Move hardcoded secrets to .env file
□ Rotate exposed API keys
□ Configure crontab:
    */5 * * * * php scripts/autonomy-cron.php
    0 3 * * * scripts/backup.sh
    0 */6 * * * php pay/cron/billing.php
□ Fix "OpenAI Partner" badge on invest.php
```

### Phase 1 — WEEK 1: Deploy What Exists

```
□ docker compose up -d redis meilisearch chromadb
□ pm2 start websocket/server.js --name alfred-ws
□ pm2 start websocket/queue.js --name alfred-queue
□ mysql < config/alfred_schema.sql
□ Initialize Restic backup (B2 credentials + test backup)
□ Pull Ollama model: ollama pull llama3.1:8b (CPU fallback)
```

### Phase 2 — WEEK 2: AI Sovereignty Begins

```
□ Rent RunPod A100 GPU
□ Deploy vLLM with Qwen 2.5 72B (OpenAI-compatible API)
□ Update callAlfred() to use self-hosted first, Groq as fallback
□ Deploy Faster-Whisper (self-hosted STT)
□ Deploy Piper/Kokoro self-hosted (TTS)
□ Add Cloudflare free CDN
□ Deploy Uptime Kuma (monitoring in 30 min)
□ Wire SendGrid into toolSendEmail()
```

### Phase 3 — WEEK 3-4: Token Launch

```
□ Consult securities lawyer on GSM token structure
□ Create SPL token on Solana mainnet ($2)
□ Upload Metaplex metadata ($1)
□ Mint 1B GSM to treasury ($0.02)
□ Revoke mint authority ($0.01)
□ Create Squads multisig treasury
□ Create Raydium GSM/SOL liquidity pool (~$756)
□ Submit to Jupiter verified token list
□ Map MySQL gsm_balances → airdrop script
□ Execute airdrop to existing users (~$35)
□ Update GSM_TOKEN_MINT and GSM_TREASURY_WALLET env vars
□ Implement wallet signature verification (unstub it)
□ Update invest.php — separate equity section from token section
```

### Phase 4 — MONTH 2: Infrastructure Sovereignty

```
□ Deploy Postal (self-hosted email)
□ Deploy Grafana + Prometheus (observability)
□ Deploy MinIO (S3-compatible object storage)
□ Deploy Keycloak/Authentik (SSO)
□ Deploy Gitea (self-hosted git — landing page ready)
□ Begin VAPI monolith decomposition (508 cases → 15 module files)
□ Write PHPUnit API smoke tests (20 tests, critical paths)
□ Deploy Colyseus (multiplayer game server for VR worlds)
```

### Phase 5 — MONTH 3: Voice & Creative Sovereignty

```
□ Deploy LiveKit Agents (replace VAPI entirely)
□ Deploy ComfyUI + FLUX (self-hosted image gen)
□ Deploy AudioCraft/MusicGen (SoundStudioPro backend)
□ Deploy Demucs (stem separation for DJ features)
□ Deploy Tabby (self-hosted Copilot for GoCodeMe)
□ Deploy FreeSWITCH + SIP trunk (self-hosted telephony)
□ Write on-chain trade execution engine (Anchor or client-side signing)
□ Deploy GSM staking contract
```

### Phase 6 — MONTH 4+: Full Sovereignty

```
□ Deploy Matrix/Conduit (self-hosted messaging, replace Discord dependency)
□ Deploy RustDesk server (landing page ready)
□ Deploy OnlyOffice (landing page ready)
□ Deploy CrowdSec + Suricata (advanced security)
□ Deploy Sentry self-hosted (error tracking)
□ Mobile app via Capacitor → App Store / Play Store
□ Implement NRG token if economy demands it
□ i18n translation framework
□ E2E Playwright tests (5 critical journeys)
□ Full VAPI monolith decomposition complete
```

---

## 10. BUDGET & REVENUE MODEL

### 10.1 Infrastructure Costs

| Item | Monthly Cost | One-Time |
|------|:---:|:---:|
| Current VPS (OVH) | ~$50 | — |
| RunPod GPU (A100) | $100-300 | — |
| Backblaze B2 (10GB free) | $0-5 | — |
| SIP trunk (VoIP.ms) | $10-30 | — |
| Raydium LP initial liquidity | — | ~$756 |
| GSM token deployment | — | ~$40 |
| Domain renewals | ~$15 | — |
| **TOTAL** | **$175-400/mo** | **~$800** |

### 10.2 Revenue Streams (Existing + Token)

| # | Stream | Current Status | Monthly Potential |
|:---:|---|---|---|
| 1 | SaaS Subscriptions ($4-199/mo) | ✅ Live via WHMCS/Stripe | $500-5,000 |
| 2 | API/Developer Access | 📋 Rate limits built, portal not live | $200-2,000 |
| 3 | Voice AI minutes | ✅ Live via VAPI | $100-1,000 |
| 4 | Hosting (DirectAdmin) | ✅ Live | $200-800 |
| 5 | Marketplace Commission (20%) | 🟡 Built, needs sellers | $50-500 |
| 6 | White-Label Agents | 📋 Designed in MP3 | $500-5,000 |
| 7 | GPU Server Hosting | 📋 ai-servers configurator built | $200-2,000 |
| 8 | GSM Token Transaction Fees | ❌ Token not live | $100-1,000 |
| 9 | VR Land Sales (1% fee) | 🟡 DB-only | $50-500 |
| 10 | Chess/Game Wagering (5% rake) | 🟡 DB-only | $50-500 |
| 11 | SSP Event Tickets (2.5% fee) | 🟡 API built | $50-500 |
| 12 | NRG Token Burns (future) | ❌ Not built | $0-1,000 |
| | **TOTAL POTENTIAL** | | **$2,000-19,800/mo** |

### 10.3 The Path to $100K MRR (Masterplan 3 Target)

```
Current:    ~$500/mo (est. from WHMCS/hosting revenue)
Month 3:    ~$2,000/mo (subscriptions + API + token fees)
Month 6:    ~$8,000/mo (enterprise + white-label + marketplace)
Month 12:   ~$25,000/mo (GPU servers + developer ecosystem)
Month 18:   ~$50,000/mo (mobile apps + international)
Month 24:   ~$100,000/mo (full ecosystem network effects)
```

---

## APPENDIX A: ALL MASTERPLANS CROSS-REFERENCE

| # | Document | Codename | Focus | Status |
|:---:|----------|----------|-------|--------|
| 1 | MASTERPLAN 1 | Project Sentience | Build the Product | ✅ Plan complete |
| 2 | MASTERPLAN 2 | Project Ignition | Wire Infrastructure | ✅ Plan complete |
| 3 | MASTERPLAN 3 | Project Phoenix | Monetize & Scale | ✅ Plan complete |
| 4 | MASTERPLAN 4 | Project Sovereignty | True Autonomy | ✅ Plan complete |
| 5 | MASTERPLAN 5 | Project Resurrection | Deploy What's Built | ✅ Audit complete |
| **6** | **MASTERPLAN 6** | **Project Genesis** | **Deploy + Token + OSS Arsenal** | **🔥 ACTIVE** |
| — | ORDER_BROTHERHOOD | Project Dawn | 33 Degrees + Garden Economy | ✅ Architecture |
| — | FAILSAFE_OPERATIONS | — | Disaster Recovery + SRE | ✅ Runbook |
| — | AUTONOMY_METAVERSE | Project Kingdom | VR Districts + KGD Economy | ✅ Architecture |
| — | OPEN_SOURCE_CATALOG | — | 85 OSS Projects for Autonomy | ✅ Research |
| — | SOVEREIGNTY_ASSESSMENT | — | 58-Capability Planet Checklist | ✅ Audit |

## APPENDIX B: COMPANION DOCUMENTS

| Document | Lines | Purpose |
|----------|:---:|---|
| [OPEN_SOURCE_AUTONOMY_CATALOG.md](OPEN_SOURCE_AUTONOMY_CATALOG.md) | ~600 | 85 open-source projects across 15 categories |
| [SOVEREIGNTY_ASSESSMENT.md](SOVEREIGNTY_ASSESSMENT.md) | ~450 | Complete 58-capability sovereignty audit |

---

*Plans 1-4 built the blueprint. Plan 5 audited the gap. Plan 6 deploys the stack, launches the token, and arms Alfred with 85 open-source weapons for permanent autonomy.*

*The mission: own the stack, own the data, own the intelligence, own the future.*
