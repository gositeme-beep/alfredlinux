# ALFRED UPGRADE MASTER PLAN 3 — "PROJECT PHOENIX"
### From Platform to Ecosystem — The Revenue Machine
### Version 12.0 Vision — April 2026

---

## TABLE OF CONTENTS

1. [Executive Summary](#1-executive-summary)
2. [Current State (Post-Sentience)](#2-current-state-post-sentience)
3. [Phase 1: Real-Time Infrastructure](#3-phase-1-real-time-infrastructure)
4. [Phase 2: Developer Ecosystem](#4-phase-2-developer-ecosystem)
5. [Phase 3: Enterprise Features](#5-phase-3-enterprise-features)
6. [Phase 4: Advanced Voice](#6-phase-4-advanced-voice)
7. [Phase 5: AI Evolution](#7-phase-5-ai-evolution)
8. [Phase 6: Monetization Engine](#8-phase-6-monetization-engine)
9. [Phase 7: Growth & Marketing](#9-phase-7-growth-marketing)
10. [Phase 8: Mobile & Native](#10-phase-8-mobile-native)
11. [Implementation Timeline](#11-implementation-timeline)
12. [Technical Architecture](#12-technical-architecture)
13. [Competitive Moat](#13-competitive-moat)
14. [Risk Analysis](#14-risk-analysis)
15. [Success Metrics](#15-success-metrics)

---

## 1. EXECUTIVE SUMMARY

Master Plan 1 ("Project Sentience") **built the product** — 1,290+ tools, consciousness layer, fleet management, voice integration, marketplace.

Master Plan 2 ("Project Ignition") **wired the infrastructure** — `callAlfred()` multi-model AI backbone, PM2 process management, SQL schema deployment, VAPI tool dispatch, MCP server.

Master Plan 3 ("Project Phoenix") **monetizes the machine** — real-time infrastructure, developer ecosystem, enterprise sales, revenue engine, and global expansion.

| Master Plan | Codename | Focus | Status |
|-------------|----------|-------|--------|
| Plan 1 | Project Sentience | Build the Product | ✅ COMPLETE |
| Plan 2 | Project Ignition | Wire the Infrastructure | ✅ COMPLETE |
| **Plan 3** | **Project Phoenix** | **Monetize & Scale** | 🔥 **THIS DOCUMENT** |

### The Phoenix Mandate

```
BUILD IT        →  WIRE IT        →  SELL IT
(Sentience)        (Ignition)        (Phoenix)
1,290 tools          callAlfred()      $100K MRR
17 categories      Multi-model AI    Developer API
Fleet mgmt         PM2 processes     Enterprise SSO
Marketplace        SQL deployment    Mobile apps
Voice rooms        VAPI 485 tools    Global expansion
```

### Target: $100K MRR Within 6 Months

| Revenue Stream | Month 1 | Month 3 | Month 6 |
|----------------|---------|---------|---------|
| Subscriptions (Starter/Pro/Enterprise) | $2,000 | $15,000 | $45,000 |
| API Usage (pay-per-call) | $500 | $5,000 | $20,000 |
| Voice Minutes (above tier) | $200 | $3,000 | $12,000 |
| Marketplace Commission (30%) | $100 | $2,000 | $8,000 |
| Agent Hosting ($1/agent/mo) | $100 | $3,000 | $10,000 |
| Enterprise Custom ($299+/mo) | $0 | $2,000 | $5,000 |
| **TOTAL** | **$2,900** | **$30,000** | **$100,000** |

---

## 2. CURRENT STATE (POST-SENTIENCE)

### 2.1 What Exists and Works

| Component | Count/Detail | Status |
|-----------|-------------|--------|
| Tools (total) | 1,290+ across 17 categories | ✅ Production |
| VAPI Tool Switch Cases | 485 routes in `vapi-tools.php` | ✅ Production |
| MCP Server (Node.js) | 807 tools on port 3005 | ✅ Production |
| VAPI Voice Functions | 391 callable via voice | ✅ Production |
| AI Models | Groq, OpenRouter, Anthropic, Together | ✅ Production |
| `callAlfred()` Backbone | Groq→OpenRouter fallback router | ✅ Production |
| SQL Tables | 14 `alfred_*` tables deployed | ✅ Production |
| PM2 Processes | MCP, middleware, WebSocket, worker | ✅ Production |
| Consciousness Layer | Personality, memory, learning, empathy | ✅ Production |
| Fleet Management | CRUD, deploy, dashboard, monitoring | ✅ Production |
| Marketplace | Browse, publish, install tools | ✅ Production |
| Tool Directory | Searchable, Schema.org markup | ✅ Production |
| Blog Articles | 10 published at `/articles/` | ✅ Production |
| Use-Case Pages | 8 industry-specific pages | ✅ Production |
| Documentation | 4 sub-pages (`/docs/`) | ✅ Production |
| Bilingual | EN/FR across all pages | ✅ Production |
| Stripe Integration | Billing, subscriptions, checkout | ✅ Production |
| SEO Markup | Schema.org on all pages | ✅ Production |
| WHMCS Announcements | 44 announcements | ✅ Production |
| Shield Protection | DDoS, rate limiting, firewall | ✅ Production |
| Auth System | PIN + multi-factor voice auth | ✅ Production |

### 2.2 What Needs Improvement

| Area | Current State | Target State |
|------|---------------|-------------|
| Real-time updates | Polling every 30s | WebSocket push (<100ms) |
| API access | Internal only | Public REST API with OAuth2 |
| Multi-user orgs | Single-user accounts | Teams with roles/permissions |
| Voice conference | Basic 2-4 participants | Full LiveKit with 20+ participants |
| Mobile access | Responsive web only | Native iOS + Android apps |
| Developer tools | None | SDK + API keys + webhooks |
| Enterprise features | None | SSO, audit logging, SLA, white-label |
| Revenue per user | ~$4/mo average | ~$12/mo average |

### 2.3 Revenue Gaps

```
REVENUE LEAK ANALYSIS:
──────────────────────────────────────────────────────────
❌ No public API         → Developers can't build on Alfred
❌ No API billing        → Heavy users cost us money
❌ No enterprise tier    → Big companies can't buy
❌ No marketplace rev    → Tool creators have no incentive
❌ No mobile app         → 60% of users on mobile can't use voice
❌ No annual plans       → No cash-flow predictability
❌ No affiliate program  → Organic growth only
❌ No usage metering     → Can't charge for overages

OPPORTUNITY COST: ~$80,000/month in unrealized revenue
──────────────────────────────────────────────────────────
```

---

## 3. PHASE 1: REAL-TIME INFRASTRUCTURE

**Priority: P0 — Foundation for everything else**
**Effort: 3 weeks (Sprints 1-3)**

### 3.1 WebSocket Server for Live Updates

Everything in Alfred that's currently polling needs to become real-time. Fleet monitoring, agent status, call events, marketplace activity — all push-based.

```javascript
// alfred-websocket-server.js — Real-Time Event Hub
const WebSocket = require('ws');
const Redis = require('ioredis');
const jwt = require('jsonwebtoken');

const wss = new WebSocket.Server({ port: 3010 });
const redis = new Redis(process.env.REDIS_URL);
const sub = new Redis(process.env.REDIS_URL);

// Channel subscriptions
const CHANNELS = {
    'fleet:*':       'Fleet status updates',
    'agent:*':       'Individual agent heartbeats',
    'call:*':        'Call lifecycle events',
    'marketplace:*': 'New listings, purchases',
    'alert:*':       'System alerts, SLA warnings',
    'chat:*':        'Chat messages, typing indicators',
    'presence:*':    'User online/offline status',
    'metrics:*':     'Real-time usage metrics'
};

wss.on('connection', (ws, req) => {
    // JWT authentication on connect
    const token = req.url.split('token=')[1];
    const user = jwt.verify(token, process.env.JWT_SECRET);

    ws.userId = user.id;
    ws.orgId = user.orgId;
    ws.subscriptions = new Set();

    ws.on('message', (data) => {
        const msg = JSON.parse(data);
        switch (msg.type) {
            case 'subscribe':
                ws.subscriptions.add(msg.channel);
                sub.psubscribe(msg.channel);
                break;
            case 'presence':
                redis.setex(`presence:${ws.userId}`, 60, JSON.stringify({
                    status: msg.status,
                    lastSeen: Date.now()
                }));
                break;
            case 'typing':
                redis.publish(`chat:${msg.room}`, JSON.stringify({
                    type: 'typing',
                    userId: ws.userId,
                    typing: msg.typing
                }));
                break;
        }
    });
});

// Fan out Redis pub/sub → WebSocket clients
sub.on('pmessage', (pattern, channel, message) => {
    const data = JSON.parse(message);
    wss.clients.forEach(client => {
        if (client.subscriptions.has(pattern) || client.subscriptions.has(channel)) {
            client.send(JSON.stringify({ channel, data }));
        }
    });
});
```

### 3.2 Redis Pub/Sub Event Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                     EVENT STREAMING LAYER                        │
│                                                                  │
│  Publishers                    Redis                Subscribers  │
│  ─────────                    ─────                ───────────── │
│                                                                  │
│  ┌──────────┐   publish()   ┌───────────┐   psubscribe()        │
│  │ VAPI     │──────────────▶│           │◀───────────┐          │
│  │ Webhook  │               │           │            │          │
│  └──────────┘               │           │   ┌────────┴───────┐  │
│                              │  REDIS    │   │  WebSocket     │  │
│  ┌──────────┐   publish()   │  PUB/SUB  │   │  Server        │  │
│  │ Fleet    │──────────────▶│           │   │  (port 3010)   │  │
│  │ Manager  │               │  Channels:│   └────────────────┘  │
│  └──────────┘               │           │                       │
│                              │  fleet:*  │   ┌────────────────┐  │
│  ┌──────────┐   publish()   │  agent:*  │   │  Dashboard     │  │
│  │ Agent    │──────────────▶│  call:*   │──▶│  (browser)     │  │
│  │ Heartbeat│               │  chat:*   │   └────────────────┘  │
│  └──────────┘               │  alert:*  │                       │
│                              │  metrics:*│   ┌────────────────┐  │
│  ┌──────────┐   publish()   │  presence:*   │  Mobile App    │  │
│  │ MCP      │──────────────▶│           │──▶│  (native)      │  │
│  │ Server   │               │           │   └────────────────┘  │
│  └──────────┘               └───────────┘                       │
│                                                                  │
│  EVENT FORMAT:                                                   │
│  {                                                               │
│    "event": "agent.heartbeat",                                   │
│    "agentId": "agt_abc123",                                      │
│    "data": { "status": "on_call", "cpu": 12, "mem": 45 },       │
│    "timestamp": 1712000000000                                    │
│  }                                                               │
└─────────────────────────────────────────────────────────────────┘
```

### 3.3 LiveKit Full Deployment

Conference rooms currently have scaffolded UI. Project Phoenix deploys the real thing.

```
LIVEKIT DEPLOYMENT:
──────────────────────────────────────────────────
Component              Port    Protocol    Status
──────────────────────────────────────────────────
LiveKit Server         7880    HTTP/WS     Deploy
LiveKit TURN           3478    UDP/TCP     Deploy  
LiveKit RTC            7881    UDP         Deploy
LiveKit Egress         9700    gRPC        Deploy
LiveKit Ingress        9701    gRPC        Deploy
──────────────────────────────────────────────────
```

```yaml
# livekit.yaml — Production Configuration
port: 7880
rtc:
  port_range_start: 50000
  port_range_end: 60000
  tcp_port: 7881
  use_external_ip: true
redis:
  address: localhost:6379
keys:
  ALFRED_LIVEKIT_KEY: <secret>
room:
  max_participants: 50
  empty_timeout: 300
turn:
  enabled: true
  domain: turn.gositeme.com
  tls_port: 5349
  udp_port: 3478
webhook:
  urls:
    - https://gositeme.com/api/livekit-webhook.php
  api_key: ALFRED_LIVEKIT_KEY
```

### 3.4 Real-Time Agent Heartbeats

Every active agent sends a heartbeat every 10 seconds. The fleet dashboard displays live status.

```php
// Agent heartbeat — sent by every active agent process
function sendAgentHeartbeat($agentId, $status, $metrics) {
    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379);

    $heartbeat = [
        'agentId'    => $agentId,
        'status'     => $status,        // idle | on_call | processing | error
        'cpu'        => $metrics['cpu'],
        'memory'     => $metrics['memory'],
        'activeTask' => $metrics['task'] ?? null,
        'callId'     => $metrics['callId'] ?? null,
        'uptime'     => $metrics['uptime'],
        'toolsUsed'  => $metrics['toolsUsed'] ?? 0,
        'timestamp'  => time()
    ];

    // Store latest heartbeat
    $redis->setex("agent:heartbeat:{$agentId}", 30, json_encode($heartbeat));

    // Publish to fleet channel
    $redis->publish("fleet:{$agentId}", json_encode([
        'event' => 'agent.heartbeat',
        'data'  => $heartbeat
    ]));

    // If agent missed 3 heartbeats (30s), mark as dead
    $redis->setex("agent:alive:{$agentId}", 30, '1');
}
```

### 3.5 Live Typing Indicators, Presence, and Cursor Sharing

```javascript
// Client-side presence and typing
class AlfredPresence {
    constructor(ws) {
        this.ws = ws;
        this.typingTimeout = null;
    }

    // Send presence update
    setStatus(status) {
        this.ws.send(JSON.stringify({
            type: 'presence',
            status: status // online | away | busy | dnd
        }));
    }

    // Send typing indicator with debounce
    setTyping(room, isTyping) {
        clearTimeout(this.typingTimeout);
        this.ws.send(JSON.stringify({
            type: 'typing',
            room: room,
            typing: isTyping
        }));
        if (isTyping) {
            this.typingTimeout = setTimeout(() => {
                this.setTyping(room, false);
            }, 3000);
        }
    }

    // Collaborative cursor positions (for shared editor sessions)
    sendCursorPosition(fileId, line, column) {
        this.ws.send(JSON.stringify({
            type: 'cursor',
            fileId: fileId,
            line: line,
            column: column
        }));
    }
}
```

### 3.6 Real-Time Architecture Diagram

```
┌──────────────────────────────────────────────────────────────────────────┐
│                    ALFRED REAL-TIME ARCHITECTURE                         │
│                                                                          │
│    CLIENTS                           SERVERS                             │
│    ───────                           ───────                             │
│                                                                          │
│    ┌────────────┐                    ┌─────────────────┐                 │
│    │ Browser    │◀──── WSS ────────▶│ WS Server       │                 │
│    │ Dashboard  │     (3010)        │ (Node.js)       │                 │
│    └────────────┘                    │ • Auth (JWT)    │                 │
│                                      │ • Channel mgmt │                 │
│    ┌────────────┐                    │ • Fan-out       │                 │
│    │ Mobile App │◀──── WSS ────────▶│ • Rate limiting │                 │
│    │ (iOS/And)  │     (3010)        └────────┬────────┘                 │
│    └────────────┘                             │                          │
│                                      ┌────────▼────────┐                 │
│    ┌────────────┐                    │  REDIS          │                 │
│    │ CLI Tool   │◀──── WSS ────────▶│  (6379)         │                 │
│    │ (alfred)   │     (3010)        │  • Pub/Sub      │                 │
│    └────────────┘                    │  • Presence TTL │                 │
│                                      │  • Session cache│                 │
│    ┌────────────┐                    │  • Rate limits  │                 │
│    │ VS Code    │◀──── WSS ────────▶└────────┬────────┘                 │
│    │ Extension  │     (3010)                 │                           │
│    └────────────┘                    ┌───────┴────────┐                  │
│                                      │                │                  │
│    ┌────────────┐               ┌────▼─────┐   ┌─────▼──────┐          │
│    │ Voice Call │◀── WebRTC ──▶│ LiveKit  │   │ MCP Server │          │
│    │ (browser)  │    (7880)    │ Server   │   │ (3005)     │          │
│    └────────────┘               └──────────┘   └────────────┘          │
│                                                                          │
│    ┌────────────┐               ┌──────────────────────────┐            │
│    │ VAPI Phone │◀── SIP ────▶│ VAPI Webhook             │            │
│    │ Caller     │              │ api/vapi-webhook.php     │            │
│    └────────────┘               └──────────┬───────────────┘            │
│                                             │                            │
│                                    ┌────────▼─────────┐                  │
│                                    │  callAlfred()    │                  │
│                                    │  AI Backbone     │                  │
│                                    │  Groq → OpenRouter│                 │
│                                    │  → Anthropic      │                 │
│                                    └──────────────────┘                  │
└──────────────────────────────────────────────────────────────────────────┘
```

### 3.7 Phase 1 Deliverables

| Task | Est. Time | Sprint | Dependencies |
|------|-----------|--------|-------------|
| WebSocket server (Node.js, port 3010) | 3 days | Sprint 1 | Redis running |
| Redis pub/sub channels (8 channels) | 2 days | Sprint 1 | Redis running |
| JWT auth for WebSocket connections | 1 day | Sprint 1 | Auth system |
| Agent heartbeat system | 2 days | Sprint 1 | WebSocket + Redis |
| Fleet dashboard → real-time upgrade | 3 days | Sprint 2 | WebSocket server |
| LiveKit server deployment | 2 days | Sprint 2 | Server provisioning |
| LiveKit room management API | 2 days | Sprint 2 | LiveKit deployed |
| Presence system (online/away/busy) | 1 day | Sprint 2 | WebSocket + Redis |
| Typing indicators | 1 day | Sprint 3 | WebSocket server |
| Cursor sharing (editor collab) | 2 days | Sprint 3 | WebSocket server |
| Call event streaming (VAPI→WS) | 2 days | Sprint 3 | VAPI webhook |
| PM2 integration (process: alfred-ws) | 1 day | Sprint 3 | PM2 running |

---

## 4. PHASE 2: DEVELOPER ECOSYSTEM

**Priority: P0 — Developers bring compounding growth**
**Effort: 4 weeks (Sprints 4-7)**

### 4.1 Public REST API with OAuth2

Open Alfred's 1,290+ tools to external developers. Every tool available via REST.

```
API BASE URL: https://gositeme.com/api/v1

AUTHENTICATION:
  Bearer Token:  Authorization: Bearer <api_key>
  OAuth2:        Authorization: Bearer <access_token>

ENDPOINTS:
──────────────────────────────────────────────────────────────────
METHOD   PATH                              DESCRIPTION
──────────────────────────────────────────────────────────────────
POST     /v1/chat                          Send a message to Alfred
POST     /v1/tools/{tool_name}/execute     Execute a specific tool
GET      /v1/tools                         List all available tools
GET      /v1/tools/{tool_name}             Get tool details + schema
GET      /v1/tools/categories              List tool categories

POST     /v1/agents                        Create a new agent
GET      /v1/agents                        List your agents
GET      /v1/agents/{id}                   Get agent details
PUT      /v1/agents/{id}                   Update agent config
DELETE   /v1/agents/{id}                   Delete an agent
POST     /v1/agents/{id}/execute           Send a task to an agent

POST     /v1/fleet                         Create a fleet
GET      /v1/fleet                         List your fleets
GET      /v1/fleet/{id}/status             Fleet status + metrics
POST     /v1/fleet/{id}/deploy             Deploy fleet
POST     /v1/fleet/{id}/pause              Pause fleet

POST     /v1/voice/call                    Initiate outbound call
GET      /v1/voice/calls                   List call history
GET      /v1/voice/calls/{id}              Get call details + transcript
POST     /v1/voice/rooms                   Create voice room
GET      /v1/voice/rooms                   List active rooms

GET      /v1/marketplace                   Browse marketplace
GET      /v1/marketplace/{id}              Get listing details
POST     /v1/marketplace/publish           Publish a tool/agent

GET      /v1/usage                         Get usage stats
GET      /v1/billing                       Get billing info
──────────────────────────────────────────────────────────────────
```

### 4.2 OAuth2 Flow

```
┌──────────┐                              ┌──────────────┐
│ Developer│                              │ Alfred Auth  │
│   App    │                              │ Server       │
└────┬─────┘                              └──────┬───────┘
     │                                           │
     │  1. Redirect to /oauth/authorize          │
     │──────────────────────────────────────────▶│
     │                                           │
     │  2. User grants permission                │
     │◀──────────────────────────────────────────│
     │                                           │
     │  3. Callback with authorization code      │
     │──────────────────────────────────────────▶│
     │                                           │
     │  4. Exchange code for access_token        │
     │◀──────────────────────────────────────────│
     │                                           │
     │  5. Use access_token for API calls        │
     │──────────────────────────────────────────▶│
     │                                           │

OAUTH ENDPOINTS:
  GET  /oauth/authorize    — Authorization page
  POST /oauth/token        — Exchange code for token
  POST /oauth/revoke       — Revoke a token
  GET  /oauth/userinfo     — Get authenticated user info

SCOPES:
  tools:read       — List and inspect tools
  tools:execute    — Execute tools
  agents:read      — List agents
  agents:write     — Create/update/delete agents
  fleet:read       — View fleet status
  fleet:write      — Manage fleets
  voice:read       — View call history
  voice:write      — Make calls, manage rooms
  billing:read     — View usage and billing
  marketplace:read — Browse marketplace
  marketplace:write— Publish to marketplace
```

### 4.3 Developer Portal

```
URL: https://developers.gositeme.com

PAGES:
├── /                         Landing page — "Build with Alfred"
├── /register                 Create developer account
├── /dashboard                API key management, usage stats
├── /keys                     Generate/revoke API keys
├── /apps                     OAuth2 app registration
├── /docs                     Full API documentation
│   ├── /docs/authentication  Auth guide (API keys + OAuth2)
│   ├── /docs/tools           Tools API reference
│   ├── /docs/agents          Agents API reference
│   ├── /docs/fleet           Fleet API reference
│   ├── /docs/voice           Voice API reference
│   ├── /docs/webhooks        Webhook event reference
│   ├── /docs/errors          Error codes and handling
│   ├── /docs/rate-limits     Rate limiting details
│   └── /docs/changelog       API changelog
├── /sandbox                  Interactive API playground
├── /sdks                     SDK downloads and guides
├── /webhooks                 Webhook configuration
├── /community                Developer forum
└── /showcase                 Apps built with Alfred API
```

### 4.4 Rate Limiting Tiers

```
RATE LIMITS BY TIER:
┌───────────────┬────────────┬────────────┬────────────────┐
│ Tier          │ Requests   │ Burst      │ Daily Limit    │
│               │ per minute │ (per sec)  │                │
├───────────────┼────────────┼────────────┼────────────────┤
│ Free          │ 100        │ 5          │ 1,000          │
│ Starter       │ 300        │ 10         │ 10,000         │
│ Professional  │ 1,000      │ 30         │ 100,000        │
│ Enterprise    │ 5,000      │ 100        │ 500,000        │
│ Enterprise+   │ 10,000     │ 200        │ Unlimited      │
│ Custom        │ Custom     │ Custom     │ Custom SLA     │
└───────────────┴────────────┴────────────┴────────────────┘

RATE LIMIT HEADERS (every response):
  X-RateLimit-Limit:      1000
  X-RateLimit-Remaining:  987
  X-RateLimit-Reset:      1712001200
  X-RateLimit-Tier:       professional
  Retry-After:            30       (only on 429)
```

### 4.5 SDK Packages

Three official SDKs — npm, pip, and composer:

```javascript
// ─── NPM: alfred-ai-sdk ───────────────────────────────
// npm install alfred-ai-sdk

const Alfred = require('alfred-ai-sdk');

const alfred = new Alfred({
    apiKey: 'ak_live_abc123...',
    baseUrl: 'https://gositeme.com/api/v1' // optional
});

// Chat with Alfred
const response = await alfred.chat('Build me a landing page for a coffee shop');
console.log(response.message);

// Execute a tool directly
const result = await alfred.tools.execute('seo_analyzer', {
    url: 'https://example.com',
    depth: 3
});

// Create an agent
const agent = await alfred.agents.create({
    name: 'Sales Bot',
    personality: 'professional, friendly, persuasive',
    tools: ['crm_lookup', 'email_sender', 'calendar_book'],
    greeting: 'Hi! I\'m your sales assistant. How can I help today?'
});

// Stream a response
const stream = alfred.chat.stream('Explain quantum computing');
for await (const chunk of stream) {
    process.stdout.write(chunk.text);
}
```

```python
# ─── PIP: alfred-ai ────────────────────────────────────
# pip install alfred-ai

from alfred_ai import Alfred

alfred = Alfred(api_key="ak_live_abc123...")

# Chat
response = alfred.chat("Analyze my server logs for anomalies")
print(response.message)

# Execute tool
result = alfred.tools.execute("database_backup", {
    "database": "production_db",
    "format": "sql.gz"
})

# Create fleet
fleet = alfred.fleet.create(
    name="Support Team",
    agents=[
        {"role": "tier1", "skills": ["billing", "password_reset"]},
        {"role": "tier2", "skills": ["technical", "escalation"]},
        {"role": "supervisor", "skills": ["quality", "training"]}
    ],
    routing="skill_based"
)

# Async streaming
async for chunk in alfred.chat.stream("Write a business plan"):
    print(chunk.text, end="")
```

```php
// ─── COMPOSER: gositeme/alfred-ai ─────────────────────
// composer require gositeme/alfred-ai

use GoSiteMe\AlfredAI\Alfred;

$alfred = new Alfred(['apiKey' => 'ak_live_abc123...']);

// Chat
$response = $alfred->chat('Generate a privacy policy for my SaaS app');
echo $response->message;

// Execute tool
$result = $alfred->tools()->execute('ssl_install', [
    'domain' => 'shop.example.com',
    'type'   => 'letsencrypt'
]);

// List agents
$agents = $alfred->agents()->list();
foreach ($agents as $agent) {
    echo "{$agent->name}: {$agent->status}\n";
}

// Webhook verification
$event = $alfred->webhooks()->verify(
    $request->getBody(),
    $request->getHeader('X-Alfred-Signature')
);
```

### 4.6 Webhook System

```
WEBHOOK EVENTS:
──────────────────────────────────────────────────────────────────
Event                        Trigger
──────────────────────────────────────────────────────────────────
agent.created                New agent created
agent.deployed               Agent went live
agent.error                  Agent encountered an error
agent.status_changed         Agent status changed (idle→busy→error)

call.started                 Incoming/outgoing call started
call.ended                   Call ended (with duration, transcript)
call.transferred             Call transferred to another agent
call.recorded                Call recording ready

fleet.deployed               Fleet deployed to production
fleet.alert                  Fleet SLA breach or performance alert
fleet.agent_joined           Agent added to fleet
fleet.agent_left             Agent removed from fleet

tool.executed                Tool was executed (with result summary)
tool.error                   Tool execution failed

marketplace.published        New tool/agent published
marketplace.purchased        Someone purchased your listing
marketplace.review           New review on your listing

billing.payment_succeeded    Payment processed successfully
billing.payment_failed       Payment failed
billing.subscription_changed Subscription tier changed
billing.usage_alert          Usage approaching tier limit (80%, 90%)
──────────────────────────────────────────────────────────────────

WEBHOOK PAYLOAD:
{
    "id": "evt_abc123",
    "event": "call.ended",
    "timestamp": "2026-04-15T10:30:00Z",
    "data": {
        "callId": "call_xyz789",
        "agentId": "agt_def456",
        "duration": 245,
        "transcript": "...",
        "sentiment": "positive",
        "resolution": "resolved"
    }
}

HEADERS:
  X-Alfred-Signature: sha256=abc123...
  X-Alfred-Event: call.ended
  X-Alfred-Delivery: evt_abc123
  Content-Type: application/json
```

### 4.7 API Versioning Strategy

```
VERSION STRATEGY:
  v1 (current)   — Stable, production-ready
  v2 (future)    — Breaking changes, 12-month deprecation window
  
URL:   https://gositeme.com/api/v1/tools
HEADER: Accept: application/vnd.alfred.v1+json  (alternative)

DEPRECATION POLICY:
  • 6-month notice before any breaking change
  • v(N-1) supported for 12 months after v(N) release
  • Sunset header on deprecated endpoints
  • Migration guides for every version bump
```

### 4.8 Sandbox / Testing Environment

```
SANDBOX URL: https://sandbox.gositeme.com/v1

FEATURES:
  • Mirrors production API 1:1
  • Test API keys (ak_test_...)
  • No real charges
  • Simulated voice calls (pre-recorded)
  • Reset-able data (wipe sandbox on demand)
  • Rate limits doubled for testing
  • Webhook replay (re-send any event)
  • Request inspector (see raw request/response)
```

### 4.9 Phase 2 Deliverables

| Task | Est. Time | Sprint | Dependencies |
|------|-----------|--------|-------------|
| REST API framework (routing, auth, errors) | 5 days | Sprint 4 | None |
| OAuth2 server (authorize, token, revoke) | 3 days | Sprint 4 | Auth system |
| API key generation and management | 2 days | Sprint 4 | Database |
| Rate limiting middleware (Redis-backed) | 2 days | Sprint 5 | Redis |
| Tools API endpoints (list, execute, schema) | 3 days | Sprint 5 | MCP server |
| Agents API endpoints (CRUD, execute) | 3 days | Sprint 5 | Fleet system |
| Voice API endpoints (call, rooms, history) | 3 days | Sprint 6 | VAPI + LiveKit |
| Webhook dispatch system | 3 days | Sprint 6 | Redis pub/sub |
| Developer portal (10 pages) | 5 days | Sprint 6 | API live |
| npm SDK (alfred-ai-sdk) | 3 days | Sprint 7 | API stable |
| pip SDK (alfred-ai) | 3 days | Sprint 7 | API stable |
| Composer SDK (gositeme/alfred-ai) | 2 days | Sprint 7 | API stable |
| Sandbox environment | 3 days | Sprint 7 | API + DB |
| API documentation (OpenAPI/Swagger) | 2 days | Sprint 7 | All endpoints |

---

## 5. PHASE 3: ENTERPRISE FEATURES

**Priority: P1 — High-value customers drive revenue**
**Effort: 4 weeks (Sprints 8-11)**

### 5.1 Multi-User Organization Accounts

```sql
-- Organization schema additions
CREATE TABLE alfred_organizations (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    slug            VARCHAR(100) UNIQUE NOT NULL,
    plan            ENUM('starter','professional','enterprise','enterprise_plus','custom') DEFAULT 'starter',
    owner_id        INT NOT NULL,
    logo_url        VARCHAR(500),
    domain          VARCHAR(255),
    sso_provider    ENUM('none','saml','oauth','okta','azure_ad') DEFAULT 'none',
    sso_config      JSON,
    data_residency  ENUM('ca-east','us-east','us-west','eu-west') DEFAULT 'ca-east',
    max_users       INT DEFAULT 5,
    max_agents      INT DEFAULT 10,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES alfred_users(id)
);

CREATE TABLE alfred_org_members (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    org_id          INT NOT NULL,
    user_id         INT NOT NULL,
    role            ENUM('owner','admin','manager','member','viewer') DEFAULT 'member',
    permissions     JSON,
    invited_by      INT,
    invited_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    accepted_at     TIMESTAMP NULL,
    FOREIGN KEY (org_id) REFERENCES alfred_organizations(id),
    FOREIGN KEY (user_id) REFERENCES alfred_users(id),
    UNIQUE KEY (org_id, user_id)
);

CREATE TABLE alfred_org_teams (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    org_id          INT NOT NULL,
    name            VARCHAR(255) NOT NULL,
    description     TEXT,
    team_lead       INT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (org_id) REFERENCES alfred_organizations(id),
    FOREIGN KEY (team_lead) REFERENCES alfred_users(id)
);

CREATE TABLE alfred_org_team_members (
    team_id         INT NOT NULL,
    user_id         INT NOT NULL,
    role            ENUM('lead','member') DEFAULT 'member',
    PRIMARY KEY (team_id, user_id),
    FOREIGN KEY (team_id) REFERENCES alfred_org_teams(id),
    FOREIGN KEY (user_id) REFERENCES alfred_users(id)
);
```

### 5.2 Role-Based Access Control (RBAC)

```
PERMISSION MATRIX:
┌───────────────────┬───────┬───────┬─────────┬────────┬────────┐
│ Action            │ Owner │ Admin │ Manager │ Member │ Viewer │
├───────────────────┼───────┼───────┼─────────┼────────┼────────┤
│ Manage billing    │  ✅   │  ❌   │   ❌    │   ❌   │   ❌   │
│ Manage members    │  ✅   │  ✅   │   ❌    │   ❌   │   ❌   │
│ Create teams      │  ✅   │  ✅   │   ✅    │   ❌   │   ❌   │
│ Create agents     │  ✅   │  ✅   │   ✅    │   ✅   │   ❌   │
│ Deploy fleet      │  ✅   │  ✅   │   ✅    │   ❌   │   ❌   │
│ Execute tools     │  ✅   │  ✅   │   ✅    │   ✅   │   ❌   │
│ View dashboards   │  ✅   │  ✅   │   ✅    │   ✅   │   ✅   │
│ View audit logs   │  ✅   │  ✅   │   ✅    │   ❌   │   ❌   │
│ Manage API keys   │  ✅   │  ✅   │   ❌    │   ❌   │   ❌   │
│ Configure SSO     │  ✅   │  ❌   │   ❌    │   ❌   │   ❌   │
│ White-label config│  ✅   │  ✅   │   ❌    │   ❌   │   ❌   │
│ Export data       │  ✅   │  ✅   │   ✅    │   ❌   │   ❌   │
│ Marketplace pub   │  ✅   │  ✅   │   ✅    │   ✅   │   ❌   │
└───────────────────┴───────┴───────┴─────────┴────────┴────────┘
```

### 5.3 SSO Integration (SAML + OAuth)

```php
// SSO Configuration — supports SAML 2.0, OAuth/OIDC, Okta, Azure AD
class AlfredSSO {
    
    // SAML 2.0
    public function configureSAML($orgId, $config) {
        return [
            'entity_id'       => "https://gositeme.com/saml/{$orgId}",
            'acs_url'         => "https://gositeme.com/saml/{$orgId}/callback",
            'slo_url'         => "https://gositeme.com/saml/{$orgId}/logout",
            'metadata_url'    => "https://gositeme.com/saml/{$orgId}/metadata",
            'idp_entity_id'   => $config['idp_entity_id'],
            'idp_sso_url'     => $config['idp_sso_url'],
            'idp_certificate' => $config['idp_certificate'],
            'name_id_format'  => 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
            'attribute_map'   => [
                'email'      => 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/emailaddress',
                'name'       => 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/name',
                'department' => 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/department'
            ]
        ];
    }

    // OAuth/OIDC — Google Workspace, Microsoft 365, Okta, Auth0
    public function configureOIDC($orgId, $config) {
        return [
            'client_id'      => $config['client_id'],
            'client_secret'  => $config['client_secret'],
            'discovery_url'  => $config['discovery_url'], // .well-known/openid-configuration
            'redirect_uri'   => "https://gositeme.com/oauth/{$orgId}/callback",
            'scopes'         => ['openid', 'profile', 'email'],
            'auto_provision' => $config['auto_provision'] ?? true, // auto-create user on first login
            'domain_lock'    => $config['domain'] ?? null // only allow @company.com
        ];
    }
}
```

### 5.4 Audit Logging

```sql
CREATE TABLE alfred_audit_log (
    id              BIGINT AUTO_INCREMENT PRIMARY KEY,
    org_id          INT NOT NULL,
    user_id         INT NOT NULL,
    action          VARCHAR(100) NOT NULL,
    resource_type   VARCHAR(50) NOT NULL,
    resource_id     VARCHAR(100),
    details         JSON,
    ip_address      VARCHAR(45),
    user_agent      VARCHAR(500),
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_org_time (org_id, created_at),
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    FOREIGN KEY (org_id) REFERENCES alfred_organizations(id)
);
```

```
AUDIT LOG EVENTS:
──────────────────────────────────────────────────────────────────
Category         Action                    Details
──────────────────────────────────────────────────────────────────
AUTH             user.login                IP, device, location
AUTH             user.logout               Session duration
AUTH             user.login_failed         IP, reason
AUTH             user.mfa_enabled          Method (SMS, TOTP, voice)
AUTH             sso.login                 Provider, assertions

AGENT            agent.created             Name, config, tools
AGENT            agent.deployed            Fleet, status
AGENT            agent.deleted             Reason
AGENT            agent.config_changed      Before/after diff

FLEET            fleet.created             Name, strategy, agents
FLEET            fleet.deployed            Agent count, routing
FLEET            fleet.paused              Reason, user
FLEET            fleet.deleted             Reason

TOOL             tool.executed             Tool name, input hash, duration
TOOL             tool.failed              Error, stack trace
TOOL             tool.rate_limited        Limit hit, tier

API              api_key.created           Key prefix, scopes
API              api_key.revoked           Reason
API              api.request              Endpoint, method, status
API              oauth.authorized         App, scopes granted

BILLING          subscription.changed      Old plan → new plan
BILLING          payment.succeeded         Amount, method
BILLING          payment.failed            Reason, retry

ADMIN            member.invited            Email, role
ADMIN            member.removed            Reason
ADMIN            role.changed             Old → new role
ADMIN            sso.configured           Provider, domain
ADMIN            data.exported            Type, format, size
──────────────────────────────────────────────────────────────────
```

### 5.5 White-Label Agent Deployment

Enterprise customers can deploy Alfred-powered agents under their own brand.

```
WHITE-LABEL FEATURES:
┌────────────────────────────────────────────────────────────────┐
│                                                                │
│  CUSTOMIZABLE ELEMENTS:                                        │
│  ─────────────────────                                         │
│  • Agent name (replace "Alfred" with your brand)               │
│  • Agent voice (clone your own voice — see Phase 4)            │
│  • Agent personality (tone, formality, vocabulary)             │
│  • Logo and colors (full CSS theme customization)              │
│  • Domain (agents.yourcompany.com via CNAME)                   │
│  • Email templates (branded notifications)                     │
│  • "Powered by" badge (optional, smaller on higher tiers)      │
│  • Custom greeting and sign-off                                │
│  • Restricted tool set (only expose approved tools)            │
│  • Custom knowledge base (train on your docs)                  │
│                                                                │
│  DEPLOYMENT OPTIONS:                                           │
│  ──────────────────                                            │
│  • Embedded widget (JavaScript snippet)                        │
│  • Standalone page (custom domain)                             │
│  • API-only (build your own UI)                                │
│  • Phone number (dedicated DID)                                │
│  • WhatsApp Business (linked number)                           │
│                                                                │
└────────────────────────────────────────────────────────────────┘
```

### 5.6 SLA Monitoring and Compliance

```
SLA TIERS:
┌──────────────────┬────────────┬────────────┬──────────────────┐
│ Metric           │ Enterprise │ Ent. Plus  │ Custom           │
├──────────────────┼────────────┼────────────┼──────────────────┤
│ Uptime           │ 99.5%      │ 99.9%      │ 99.95%           │
│ API Response     │ <500ms p95 │ <200ms p95 │ <100ms p95       │
│ Voice Latency    │ <300ms     │ <150ms     │ <100ms           │
│ Support Response │ 4 hours    │ 1 hour     │ 15 minutes       │
│ Incident Updates │ 1 hour     │ 30 min     │ 15 min           │
│ Credit Policy    │ 5% / 0.1%  │ 10% / 0.1% │ Custom           │
│ Status Page      │ Shared     │ Shared     │ Dedicated        │
│ Dedicated CSM    │ ❌          │ ✅          │ ✅                │
│ Pentest Reports  │ Annual     │ Quarterly  │ On-demand        │
└──────────────────┴────────────┴────────────┴──────────────────┘
```

### 5.7 Data Residency

```
DATA CENTERS:
┌────────────────┬──────────────┬─────────────────────────────────┐
│ Region         │ Location     │ Compliance                      │
├────────────────┼──────────────┼─────────────────────────────────┤
│ ca-east        │ Montreal, QC │ PIPEDA, Quebec Law 25, PHIPA    │
│ us-east        │ Virginia, US │ SOC 2, HIPAA BAA                │
│ us-west        │ Oregon, US   │ SOC 2, HIPAA BAA                │
│ eu-west        │ Dublin, IE   │ GDPR, EU AI Act                 │
└────────────────┴──────────────┴─────────────────────────────────┘

DATA SOVEREIGNTY GUARANTEES:
  • All data stored in chosen region — never transferred
  • AI model inference in-region (regional GPU allocation)
  • Backups in-region (encrypted, 30-day retention)
  • Audit log confirming data location on demand
  • Contractual guarantees for regulatory compliance
```

### 5.8 Custom Agent Training

```
ENTERPRISE TRAINING PIPELINE:
──────────────────────────────────────────────────────────────────

Step 1: INGEST
  Upload your data:
  • PDFs (contracts, policies, manuals)
  • Websites (crawl your help center)
  • API docs (OpenAPI/Swagger specs)
  • CSV/Excel (product catalogs, FAQ)
  • Slack/Teams export (conversation history)

Step 2: PROCESS
  • Chunk documents (1024 tokens, 256 overlap)
  • Generate embeddings (text-embedding-3-small)
  • Store in vector database (pgvector)
  • Build knowledge graph (entity relationships)

Step 3: FINE-TUNE
  • Extract Q&A pairs from your conversations
  • Generate synthetic training data
  • Fine-tune on domain-specific vocabulary
  • Validate with held-out test set

Step 4: DEPLOY
  • Agent uses your knowledge base first
  • Falls back to general knowledge when needed
  • Confidence scoring on every response
  • "I don't know" when below threshold (no hallucination)

Step 5: MONITOR
  • Track accuracy over time
  • Flag low-confidence responses for human review
  • Auto-retrain on corrections
  • Weekly knowledge freshness report
──────────────────────────────────────────────────────────────────
```

### 5.9 Phase 3 Deliverables

| Task | Est. Time | Sprint | Dependencies |
|------|-----------|--------|-------------|
| Organization model + schema | 3 days | Sprint 8 | Database |
| RBAC permission system | 3 days | Sprint 8 | Org model |
| Team management UI | 3 days | Sprint 8 | RBAC |
| SAML 2.0 SSO integration | 4 days | Sprint 9 | Org model |
| OAuth/OIDC SSO (Google, Azure AD) | 3 days | Sprint 9 | Org model |
| Audit logging system | 3 days | Sprint 9 | Database |
| Audit log viewer UI | 2 days | Sprint 9 | Audit system |
| White-label configuration | 4 days | Sprint 10 | Org model |
| Custom domain (CNAME) support | 2 days | Sprint 10 | DNS + SSL |
| SLA monitoring dashboard | 3 days | Sprint 10 | Metrics pipeline |
| Data residency configuration | 3 days | Sprint 11 | Multi-region infra |
| Custom agent training pipeline | 5 days | Sprint 11 | Vector DB + embedding |
| Enterprise admin console | 3 days | Sprint 11 | All enterprise features |

---

## 6. PHASE 4: ADVANCED VOICE

**Priority: P1 — Voice is Alfred's killer differentiator**
**Effort: 4 weeks (Sprints 12-15)**

### 6.1 Full LiveKit Conference Rooms (20+ Participants)

```
CONFERENCE ROOM TIERS:
┌──────────────┬──────────┬──────────┬──────────────┬────────────┐
│ Feature      │ Free     │ Pro      │ Enterprise   │ Custom     │
├──────────────┼──────────┼──────────┼──────────────┼────────────┤
│ Participants │ 4        │ 10       │ 20           │ 50         │
│ AI Agents    │ 1        │ 3        │ 10           │ Unlimited  │
│ Recording    │ ❌        │ ✅        │ ✅            │ ✅          │
│ Transcription│ ❌        │ ✅        │ ✅ (real-time)│ ✅          │
│ Screen Share │ ❌        │ ✅        │ ✅            │ ✅          │
│ Breakout Rms │ ❌        │ ❌        │ ✅            │ ✅          │
│ Duration     │ 30 min   │ 2 hours  │ 8 hours      │ Unlimited  │
│ Quality      │ Mono     │ Stereo   │ HD Stereo    │ Studio     │
└──────────────┴──────────┴──────────┴──────────────┴────────────┘
```

### 6.2 Voice Cloning

Let users clone their own voice so their AI agent sounds exactly like them.

```
VOICE CLONING PIPELINE:
──────────────────────────────────────────────────────────────────

  1. USER RECORDS 5-MINUTE SAMPLE
     ┌─────────────────┐
     │ "Please read     │
     │  these 50        │──▶ Upload .wav (16kHz, mono)
     │  sentences..."   │
     └─────────────────┘

  2. PREPROCESSING
     • Noise reduction (RNNoise)
     • Silence trimming
     • Speaker diarization (verify single speaker)
     • Quality score (reject if < 80%)

  3. MODEL TRAINING
     • Base model: XTTS v2 (Coqui)
     • Fine-tune on user's voice sample
     • Training time: ~30 minutes (GPU)
     • Output: voice profile (~50MB model)

  4. DEPLOYMENT
     • Voice profile stored encrypted (AES-256)
     • Assigned to user's agents
     • Real-time synthesis latency: <200ms
     • Supported languages: EN, FR, ES, DE, PT

  5. SAFEGUARDS
     • Consent verification (signed agreement)
     • Watermarking (imperceptible audio watermark)
     • Usage logging (who used the voice, when)
     • Kill switch (instant deactivation)
     • Only account owner can clone their own voice
──────────────────────────────────────────────────────────────────
```

### 6.3 Multi-Language Real-Time Translation

```
SUPPORTED LANGUAGE PAIRS:
┌─────────────────────────────────────────────────────┐
│                                                     │
│   EN ◀──▶ FR  (English ↔ French)                    │
│   EN ◀──▶ ES  (English ↔ Spanish)                   │
│   EN ◀──▶ DE  (English ↔ German)                    │
│   EN ◀──▶ PT  (English ↔ Portuguese)                │
│   EN ◀──▶ ZH  (English ↔ Mandarin)                  │
│   EN ◀──▶ JA  (English ↔ Japanese)                   │
│   FR ◀──▶ ES  (French ↔ Spanish)                    │
│   FR ◀──▶ DE  (French ↔ German)                     │
│   + All remaining pair combinations                 │
│                                                     │
│   TOTAL: 21 language pairs                          │
│   LATENCY: <500ms end-to-end                        │
│   PIPELINE: Speech→STT→Translate→TTS→Speech         │
│                                                     │
│   Engine Stack:                                     │
│   • STT: Whisper large-v3 (OpenAI)                  │
│   • Translation: NLLB-200 (Meta) or GPT-4          │
│   • TTS: XTTS v2 (preserves speaker voice)          │
│                                                     │
└─────────────────────────────────────────────────────┘
```

### 6.4 Emotion Detection

```php
// Analyze caller voice for emotional state
function detectEmotion($audioBuffer) {
    // Features extracted:
    // • Pitch variation (high variation = excited/angry)
    // • Speaking rate (fast = anxious, slow = sad/calm)
    // • Volume dynamics (loud = angry, quiet = sad)
    // • Voice quality (breathy = tired, tense = stressed)
    // • Pause patterns (frequent pauses = uncertain)

    $emotions = [
        'happy'      => 0.0,
        'sad'        => 0.0,
        'angry'      => 0.0,
        'frustrated' => 0.0,
        'anxious'    => 0.0,
        'calm'       => 0.0,
        'confused'   => 0.0,
        'excited'    => 0.0,
        'neutral'    => 0.0
    ];

    // Run through emotion classification model
    $result = classifyEmotion($audioBuffer);

    // Agent behavior adjustments:
    // frustrated → apologize, speak slower, escalate sooner
    // happy      → match energy, upsell opportunity
    // confused   → simplify language, offer examples
    // angry      → empathize, don't interrupt, offer supervisor

    return [
        'primary_emotion' => $result['top_emotion'],
        'confidence'      => $result['confidence'],
        'all_scores'      => $emotions,
        'recommended_tone'=> mapEmotionToTone($result['top_emotion']),
        'escalation_risk' => calculateEscalationRisk($result)
    ];
}
```

### 6.5 Voice Biometric Authentication

```
VOICE BIOMETRIC FLOW:
──────────────────────────────────────────────────────────────────

  ENROLLMENT (one-time):
  ┌─────┐                    ┌──────────────┐
  │User │──"My voice is my ──▶│ Voice Print  │
  │     │   password"  (x3)  │ Generator    │
  └─────┘                    └──────┬───────┘
                                    │
                              ┌─────▼──────┐
                              │ Voiceprint │
                              │ (encrypted)│
                              └────────────┘

  VERIFICATION (every call):
  ┌─────┐                    ┌──────────────┐   ┌───────────┐
  │Caller│──speaks──────────▶│ Voice Match  │──▶│ Score     │
  │      │                   │ Engine       │   │ 0.0 - 1.0 │
  └──────┘                   └──────────────┘   └─────┬─────┘
                                                      │
                                              ┌───────▼────────┐
                                              │ ≥ 0.85 → AUTH  │
                                              │ < 0.85 → DENY  │
                                              │ 0.70-0.84 → MFA│
                                              └────────────────┘

  ANTI-SPOOFING:
  • Liveness detection (detect recordings/deepfakes)
  • Challenge-response ("please say: blue falcon seven")
  • Environmental noise analysis
  • Device fingerprint correlation
──────────────────────────────────────────────────────────────────
```

### 6.6 IVR Builder

```
VISUAL IVR BUILDER:
──────────────────────────────────────────────────────────────────

Drag-and-drop IVR flow creation with AI-powered nodes:

  ┌─────────────┐
  │ Incoming    │
  │ Call        │
  └──────┬──────┘
         │
  ┌──────▼──────┐     ┌──────────────┐
  │ Greeting    │────▶│ AI Intent    │
  │ "Welcome    │     │ Detection    │
  │  to..."     │     │ (no "press 1")│
  └─────────────┘     └──────┬───────┘
                             │
              ┌──────────────┼──────────────┐
              │              │              │
       ┌──────▼────┐  ┌─────▼─────┐  ┌────▼──────┐
       │ Sales     │  │ Support   │  │ Billing   │
       │ Agent     │  │ Agent     │  │ Agent     │
       │ (AI)      │  │ (AI)      │  │ (AI)      │
       └──────┬────┘  └─────┬─────┘  └────┬──────┘
              │              │              │
       ┌──────▼────┐  ┌─────▼─────┐  ┌────▼──────┐
       │ Escalate  │  │ Resolved? │  │ Payment   │
       │ to Human? │  │ Y/N       │  │ Portal    │
       └───────────┘  └───────────┘  └───────────┘

NODE TYPES:
  • Greeting       — Play message / TTS
  • AI Intent      — Alfred detects what caller wants (no menus!)
  • Agent          — Route to AI agent with specific skills
  • Transfer       — Transfer to human / external number
  • Voicemail      — Take message, transcribe, email
  • Payment        — Secure payment collection (PCI)
  • SMS            — Send SMS to caller
  • Webhook        — Call external API
  • Schedule       — Book appointment
  • Survey         — Post-call satisfaction survey
  • Condition      — Branch based on time/day/caller/history
──────────────────────────────────────────────────────────────────
```

### 6.7 Call Recording with AI Summaries

```
RECORDING PIPELINE:
──────────────────────────────────────────────────────────────────
  
  CALL IN PROGRESS
       │
       ▼
  ┌──────────────┐
  │ Record       │──▶ Stereo recording (agent L, caller R)
  │ (LiveKit)    │    Format: WAV → OPUS (compressed)
  └──────┬───────┘    Storage: S3-compatible (encrypted)
         │
  CALL ENDS
         │
         ▼
  ┌──────────────┐
  │ Transcribe   │──▶ Whisper large-v3
  │ (async)      │    Speaker diarization (who said what)
  └──────┬───────┘    
         │
         ▼
  ┌──────────────┐
  │ AI Summary   │──▶ callAlfred() generates:
  │              │    • 3-line summary
  └──────┬───────┘    • Action items
         │            • Sentiment analysis
         ▼            • Follow-up recommendations
  ┌──────────────┐    • Key topics discussed
  │ Deliver      │
  │              │──▶ Email summary to manager
  │              │──▶ CRM update (auto-log call)
  │              │──▶ Dashboard (searchable archive)
  │              │──▶ Webhook (external systems)
  └──────────────┘
──────────────────────────────────────────────────────────────────
```

### 6.8 Outbound Calling Campaigns

```
CAMPAIGN BUILDER:
──────────────────────────────────────────────────────────────────

  1. UPLOAD CONTACTS
     • CSV with name, phone, context
     • CRM integration (auto-pull)
     • Max 1000 contacts per campaign

  2. CONFIGURE AGENT
     • Select AI agent (or create new)
     • Script template with personalization variables
     • Objection handling rules
     • Call-to-action goals

  3. SET SCHEDULE
     • Time zone-aware calling windows
     • Respect do-not-call lists
     • Pacing: 1-10 concurrent calls
     • Retry rules (max 3 attempts, 24h gap)

  4. LAUNCH & MONITOR
     • Real-time campaign dashboard
     • Live call listening
     • Conversion tracking
     • Auto-pause on high abandon rate

  5. RESULTS
     • Call outcome breakdown (connected, voicemail, no-answer)
     • Conversion rate
     • Average call duration
     • Sentiment analysis across all calls
     • Full transcripts and recordings

  COMPLIANCE:
  • CRTC DNCL (Canada)
  • TCPA (US)
  • Caller ID verified (STIR/SHAKEN)
  • Opt-out on every call
  • Recording consent announcement
──────────────────────────────────────────────────────────────────
```

### 6.9 SMS / WhatsApp Integration

```
OMNICHANNEL MESSAGING:
┌──────────────────────────────────────────────────────────────┐
│                                                              │
│  ┌───────┐  ┌──────────┐  ┌──────────┐  ┌───────────────┐  │
│  │ Voice │  │   SMS    │  │ WhatsApp │  │   Web Chat    │  │
│  │ (VAPI)│  │ (Telnyx) │  │ (Meta)   │  │ (WebSocket)   │  │
│  └───┬───┘  └────┬─────┘  └────┬─────┘  └──────┬────────┘  │
│      │           │              │               │            │
│      └───────────┴──────┬───────┴───────────────┘            │
│                         │                                    │
│                  ┌──────▼──────┐                              │
│                  │ UNIFIED     │                              │
│                  │ CONVERSATION│                              │
│                  │ MANAGER     │                              │
│                  └──────┬──────┘                              │
│                         │                                    │
│                  ┌──────▼──────┐                              │
│                  │ callAlfred()│                              │
│                  │ AI Backbone │                              │
│                  └─────────────┘                              │
│                                                              │
│  FEATURES:                                                   │
│  • Same agent handles voice AND text seamlessly              │
│  • Conversation context preserved across channels            │
│  • "I'll text you the link" — agent sends SMS mid-call      │
│  • Escalate from WhatsApp → voice call with one tap         │
│  • Rich media in WhatsApp (images, documents, buttons)      │
│  • SMS appointment reminders and confirmations               │
│                                                              │
└──────────────────────────────────────────────────────────────┘
```

### 6.10 Phase 4 Deliverables

| Task | Est. Time | Sprint | Dependencies |
|------|-----------|--------|-------------|
| LiveKit conference rooms (20+ users) | 5 days | Sprint 12 | LiveKit deployed |
| Room management API + UI | 3 days | Sprint 12 | LiveKit |
| Voice cloning pipeline (XTTS v2) | 5 days | Sprint 12 | GPU server |
| Voice clone consent + security | 2 days | Sprint 12 | Legal review |
| Real-time translation pipeline | 5 days | Sprint 13 | Whisper + NLLB |
| Emotion detection model | 4 days | Sprint 13 | Audio ML |
| Voice biometric enrollment + verify | 4 days | Sprint 13 | Audio ML |
| IVR builder (visual editor) | 5 days | Sprint 14 | Voice system |
| Voicemail transcription + routing | 2 days | Sprint 14 | Whisper |
| Call recording + AI summaries | 3 days | Sprint 14 | LiveKit + Whisper |
| Outbound calling campaign engine | 5 days | Sprint 15 | VAPI + Telnyx |
| SMS integration (Telnyx) | 2 days | Sprint 15 | Telnyx API |
| WhatsApp Business integration | 3 days | Sprint 15 | Meta API |
| Unified conversation manager | 3 days | Sprint 15 | All channels |

---

## 7. PHASE 5: AI EVOLUTION

**Priority: P1 — The brain gets smarter**
**Effort: 3 weeks (Sprints 16-18)**

### 7.1 Fine-Tuned Alfred Model

```
FINE-TUNING PIPELINE:
──────────────────────────────────────────────────────────────────

  DATA COLLECTION (ongoing):
  • Every callAlfred() interaction logged (anonymized)
  • Tool usage patterns (which tools, in what order)
  • User corrections (when user overrides Alfred's suggestion)
  • High-rated conversations (NPS 9-10)
  • Low-rated conversations (NPS 1-3, for negative examples)

  TRAINING DATA FORMAT:
  {
    "system": "You are Alfred, an AI assistant by GoSiteMe...",
    "conversations": [
      {"role": "user", "content": "Deploy my WordPress site"},
      {"role": "assistant", "content": "I'll deploy your WordPress...",
       "tool_calls": [
         {"name": "wordpress_install", "args": {"domain": "..."}},
         {"name": "ssl_install", "args": {"domain": "..."}},
         {"name": "dns_update", "args": {"domain": "..."}}
       ]}
    ],
    "quality_score": 0.95,
    "tool_accuracy": 1.0
  }

  MODEL: Fine-tune Llama 3.3 70B (via Together AI)
  DATASET: 50,000+ conversations (target)
  CADENCE: Weekly retrain with new data
  EVAL: Held-out test set — target 95%+ tool selection accuracy
──────────────────────────────────────────────────────────────────
```

### 7.2 Tool Auto-Discovery

Alfred proactively suggests tools based on what the user is doing.

```
AUTO-DISCOVERY ENGINE:
──────────────────────────────────────────────────────────────────

  USER CONTEXT                         ALFRED SUGGESTS
  ────────────                         ───────────────

  Editing .htaccess file              → "Want me to run security_scan
                                         after you save?"

  SSL cert expires in 7 days          → "Your cert expires Thursday.
                                         Run ssl_renew now?"

  3 failed deployments today          → "I notice repeated failures.
                                         Run error_log_analyzer?"

  New WordPress plugin installed      → "New plugin detected. Run
                                         vulnerability_check?"

  Heavy DB queries in slow log        → "Slow queries detected. Run
                                         database_optimizer?"

  IMPLEMENTATION:
  • Background job scans user activity every 5 minutes
  • Matches activity patterns against tool capabilities
  • Scores relevance (0.0 - 1.0), only suggests ≥ 0.7
  • Learns from accept/dismiss to improve suggestions
  • Max 3 suggestions per hour (not annoying)
──────────────────────────────────────────────────────────────────
```

### 7.3 Agent-to-Agent Negotiation

```
AGENT NEGOTIATION PROTOCOL:
──────────────────────────────────────────────────────────────────

  SCENARIO: User says "Plan my product launch"

  ┌──────────────┐
  │ Coordinator  │ ← Alfred (main agent)
  │ Agent        │
  └──────┬───────┘
         │
         │ Decomposes into sub-tasks:
         │
    ┌────┴─────┬──────────┬──────────┬──────────┐
    │          │          │          │          │
  ┌─▼──┐   ┌──▼──┐   ┌──▼──┐   ┌──▼──┐   ┌──▼──┐
  │Mktg│   │Tech │   │Copy │   │  PR │   │Legal│
  │Agt │   │Agt  │   │Agt  │   │Agt  │   │Agt  │
  └─┬──┘   └──┬──┘   └──┬──┘   └──┬──┘   └──┬──┘
    │          │          │          │          │
    │NEGOTIATE │          │          │          │
    │◀────────▶│          │          │          │
    │"I need   │          │          │          │
    │ launch   │"I'll     │          │          │
    │ date to  │ have     │          │          │
    │ plan     │ staging  │          │          │
    │ campaign"│ ready    │          │          │
    │          │ by Wed"  │          │          │
    │          │          │          │          │
    ▼          ▼          ▼          ▼          ▼
  Campaign   Deploy    Landing    Press     Terms of
  Plan       Plan      Page Copy  Release   Service
  
  RESULT: Coordinated plan with dependencies resolved
──────────────────────────────────────────────────────────────────
```

### 7.4 Autonomous Goal Completion

```
AUTONOMOUS MODE:
──────────────────────────────────────────────────────────────────

  USER: "I want a working e-commerce store for selling t-shirts"

  ALFRED AUTONOMOUS PLAN:
  ┌────┬─────────────────────────────────┬──────────┬──────────┐
  │Step│ Action                          │ Tool     │ Status   │
  ├────┼─────────────────────────────────┼──────────┼──────────┤
  │  1 │ Create subdomain shop.user.com  │ dns_add  │ ✅ Done   │
  │  2 │ Install WordPress + WooCommerce │ wp_inst  │ ✅ Done   │
  │  3 │ Install theme (Flavor starter)  │ wp_theme │ ✅ Done   │
  │  4 │ Add 5 sample t-shirt products   │ woo_prod │ ✅ Done   │
  │  5 │ Configure Stripe payments       │ woo_pay  │ ✅ Done   │
  │  6 │ Set up shipping zones (CA/US)   │ woo_ship │ ✅ Done   │
  │  7 │ Install SSL certificate         │ ssl_inst │ ✅ Done   │
  │  8 │ Generate product images (AI)    │ img_gen  │ ✅ Done   │
  │  9 │ Write product descriptions (AI) │ content  │ ✅ Done   │
  │ 10 │ SEO optimization                │ seo_opt  │ ✅ Done   │
  │ 11 │ Create sitemap.xml              │ seo_site │ ✅ Done   │
  │ 12 │ Submit to Google Search Console │ seo_gsc  │ ✅ Done   │
  │ 13 │ Run security scan               │ sec_scan │ ✅ Done   │
  │ 14 │ Performance optimization        │ perf_opt │ ✅ Done   │
  │ 15 │ Send summary to user            │ email    │ ✅ Done   │
  └────┴─────────────────────────────────┴──────────┴──────────┘

  TOTAL TIME: ~8 minutes (no human intervention)
  TOOLS USED: 15
  APPROVAL: User pre-approved "autonomous mode" for this goal

  SAFETY RAILS:
  • User must explicitly enable autonomous mode
  • Spending limits ($0 for free tier, $10/task for pro)
  • Destructive actions always require confirmation
  • Rollback available for every step
  • Real-time progress streaming via WebSocket
──────────────────────────────────────────────────────────────────
```

### 7.5 Code Generation with Multi-File Context

```
VOICE-FIRST CODE GENERATION:
──────────────────────────────────────────────────────────────────

  USER (voice): "Add a dark mode toggle to my React app"

  ALFRED:
  1. Scans project structure (identifies React + Tailwind + Next.js)
  2. Reads relevant files:
     • app/layout.tsx (root layout)
     • app/globals.css (styles)
     • components/Header.tsx (where toggle goes)
     • tailwind.config.ts (dark mode config)
     • package.json (dependencies)
  3. Plans changes across 4 files:
     ┌─────────────────────────────────────────────────────┐
     │ File                    │ Changes                   │
     ├─────────────────────────┼───────────────────────────┤
     │ tailwind.config.ts      │ Add darkMode: 'class'     │
     │ app/globals.css         │ Add dark: variants         │
     │ components/Header.tsx   │ Add ThemeToggle component  │
     │ app/layout.tsx          │ Wrap with ThemeProvider     │
     │ + NEW: hooks/useTheme.ts│ Theme context + hook       │
     └─────────────────────────┴───────────────────────────┘
  4. Generates all changes with proper imports
  5. Shows diff preview (voice: "Here's what I'll change...")
  6. Applies on user confirmation
  7. Runs lint + type check automatically
──────────────────────────────────────────────────────────────────
```

### 7.6 Image & Document Understanding

```
MULTIMODAL CAPABILITIES:
──────────────────────────────────────────────────────────────────

  IMAGE UNDERSTANDING:
  • Upload screenshot → Alfred explains the UI and suggests fixes
  • Upload error screenshot → Alfred reads the error and fixes it
  • Upload design mockup → Alfred generates matching code
  • Upload whiteboard photo → Alfred converts to structured diagram
  • Upload receipt → Alfred extracts and categorizes expenses

  DOCUMENT UNDERSTANDING:
  • Upload PDF contract → Alfred summarizes key terms, risks, obligations
  • Upload invoice → Alfred extracts line items, totals, payment terms
  • Upload resume → Alfred summarizes skills, experience, red flags
  • Upload legal filing → Alfred extracts dates, parties, claims
  • Upload medical report → Alfred summarizes findings (HIPAA compliant)

  MODELS:
  • GPT-4o (vision) — primary
  • Claude 3.5 Sonnet (vision) — fallback
  • Llama 3.2 Vision 90B — cost-effective batch processing

  API ENDPOINT:
  POST /v1/understand
  Content-Type: multipart/form-data

  {
    "file": <binary>,
    "prompt": "Summarize this contract and flag risky clauses",
    "format": "json"  // or "text", "markdown"
  }
──────────────────────────────────────────────────────────────────
```

### 7.7 Phase 5 Deliverables

| Task | Est. Time | Sprint | Dependencies |
|------|-----------|--------|-------------|
| Training data pipeline (anonymize + format) | 3 days | Sprint 16 | callAlfred logs |
| Fine-tune Llama 3.3 70B (Together AI) | 2 days | Sprint 16 | Training data |
| Tool auto-discovery engine | 4 days | Sprint 16 | Activity tracking |
| Agent-to-agent negotiation protocol | 5 days | Sprint 17 | Fleet system |
| Coordinator agent (task decomposition) | 3 days | Sprint 17 | A2A protocol |
| Autonomous goal completion engine | 5 days | Sprint 17 | All tools |
| Safety rails (spending limits, rollback) | 2 days | Sprint 17 | Autonomous mode |
| Multi-file code generation | 4 days | Sprint 18 | Code context |
| Image understanding (GPT-4o vision) | 3 days | Sprint 18 | GPT-4o API |
| Document understanding (PDF/contract) | 3 days | Sprint 18 | Vision + PDF parser |
| Autonomous mode UI (progress stream) | 2 days | Sprint 18 | WebSocket |

---

## 8. PHASE 6: MONETIZATION ENGINE

**Priority: P0 — Revenue is the lifeblood**
**Effort: 2 weeks (concurrent with other phases)**

### 8.1 Tiered Pricing (Refined)

```
PRICING TIERS:
┌───────────────────────────────────────────────────────────────────────────┐
│                                                                         │
│  FREE                    STARTER           PROFESSIONAL                 │
│  $0/mo                   $3.99/mo          $9.99/mo                     │
│  ─────                   ────────          ────────────                 │
│  • 10 tools              • 100 tools       • ALL 1,290+ tools             │
│  • 5 voice min/day       • 60 voice min/day• Unlimited voice            │
│  • 1 agent               • 3 agents        • 5 agents                   │
│  • Web chat only         • Web + voice      • Web + voice + SMS         │
│  • Community support     • Email support    • Priority support           │
│  • 100 API calls/day     • 10,000 API/day  • 100,000 API/day            │
│  • No conference rooms   • 4-person rooms  • 10-person rooms            │
│  • No marketplace        • Browse only      • Browse + publish           │
│  • No fleet              • 1 fleet (3 agts)• 3 fleets (15 agents)      │
│  • 1 GB storage          • 10 GB storage   • 50 GB storage              │
│                                                                         │
│  ─────────────────────────────────────────────────────────────────────  │
│                                                                         │
│  ENTERPRISE              ENTERPRISE PLUS    ENTERPRISE CUSTOM           │
│  $24.99/mo               $99/mo             $299+/mo                    │
│  ──────────              ──────────────     ────────────────            │
│  • ALL tools + priority  • Everything in    • Everything in             │
│  • Unlimited voice         Enterprise +       Enterprise Plus +         │
│  • 20 agents             • SSO (SAML/OIDC)  • White-label deploy       │
│  • All channels          • Audit logging     • Custom SLA (99.95%)     │
│  • 24/7 email support    • Dedicated CSM     • Dedicated support       │
│  • 500,000 API/day       • Unlimited API     • Unlimited everything    │
│  • 20-person rooms       • 50-person rooms   • Custom rooms            │
│  • Full marketplace      • Revenue sharing   • Custom training         │
│  • 10 fleets (50 agents) • Unlimited fleets  • On-site onboarding     │
│  • 200 GB storage        • 1 TB storage      • Unlimited storage       │
│  • Organization accounts • Data residency    • Custom data residency   │
│  • Team management       • Voice cloning     • Dedicated infrastructure│
│  • API access            • Custom branding   • SLA with penalties      │
│                                                                         │
│  ANNUAL DISCOUNT: 2 months free (pay for 10, get 12)                    │
│                                                                         │
└───────────────────────────────────────────────────────────────────────────┘
```

### 8.2 Usage-Based Revenue

```
PAY-PER-USE (above tier limits):
┌──────────────────────┬────────────────────────────────────────┐
│ Resource             │ Overage Price                          │
├──────────────────────┼────────────────────────────────────────┤
│ API calls            │ $0.001 per call (above tier limit)     │
│ Voice minutes        │ $0.05 per minute (above tier limit)    │
│ Agent hosting        │ $1.00 per agent per month (always-on)  │
│ Storage              │ $0.10 per GB per month                 │
│ Conference rooms     │ $0.02 per participant per minute       │
│ Voice cloning        │ $5.00 per voice profile (one-time)     │
│ Outbound calls       │ $0.03 per minute (Telnyx rates + markup│
│ SMS messages         │ $0.01 per message                      │
│ WhatsApp messages    │ $0.02 per message                      │
│ Image generation     │ $0.02 per image                        │
│ Document analysis    │ $0.05 per document                     │
│ Translation minutes  │ $0.08 per minute                       │
└──────────────────────┴────────────────────────────────────────┘
```

### 8.3 Marketplace Revenue

```
MARKETPLACE ECONOMICS:
──────────────────────────────────────────────────────────────────

  COMMISSION STRUCTURE:
  ┌───────────────────────┬─────────────┬──────────────┐
  │ Sale Type             │ Creator Gets│ Platform Gets│
  ├───────────────────────┼─────────────┼──────────────┤
  │ Tool sale             │ 70%         │ 30%          │
  │ Agent template sale   │ 70%         │ 30%          │
  │ Workflow template sale│ 70%         │ 30%          │
  │ First $10K/month      │ 80%         │ 20%          │
  │ Tips / donations      │ 95%         │ 5%           │
  └───────────────────────┴─────────────┴──────────────┘

  CREATOR INCENTIVES:
  • Verified Creator badge (for quality + reviews)
  • Featured placement for top-rated tools
  • Monthly creator spotlight (blog + newsletter)
  • Revenue dashboard with analytics
  • Auto-payout via Stripe Connect (weekly)
  • Creator-to-creator collaboration tools

  EXAMPLE REVENUE PROJECTION:
  ┌──────────────┬────────┬──────────┬────────────┐
  │ Month        │ Listings│ Sales   │ Commission │
  ├──────────────┼────────┼──────────┼────────────┤
  │ Month 1      │ 20      │ $500    │ $150       │
  │ Month 3      │ 100     │ $5,000  │ $1,500     │
  │ Month 6      │ 500     │ $25,000 │ $7,500     │
  │ Month 12     │ 2,000   │ $100,000│ $30,000    │
  └──────────────┴────────┴──────────┴────────────┘
──────────────────────────────────────────────────────────────────
```

### 8.4 Revenue Sharing with Tool Creators

```
CREATOR PROGRAM:
──────────────────────────────────────────────────────────────────

  TIERS:
  ┌──────────────┬──────────────┬──────────────┬────────────────┐
  │              │ New Creator  │ Pro Creator  │ Elite Creator  │
  ├──────────────┼──────────────┼──────────────┼────────────────┤
  │ Requirements │ 1+ tool      │ 5+ tools     │ 20+ tools      │
  │              │              │ 100+ installs│ 1000+ installs │
  │              │              │ 4.0+ rating  │ 4.5+ rating    │
  ├──────────────┼──────────────┼──────────────┼────────────────┤
  │ Revenue Share│ 70%          │ 80%          │ 85%            │
  │ Payout Cycle │ Monthly      │ Bi-weekly    │ Weekly         │
  │ Min Payout   │ $50          │ $25          │ $10            │
  │ Featured     │ ❌            │ Occasionally │ Priority       │
  │ Support      │ Community    │ Email        │ Dedicated      │
  │ Analytics    │ Basic        │ Advanced     │ Full + export  │
  │ Beta Access  │ ❌            │ ✅            │ ✅ + roadmap    │
  └──────────────┴──────────────┴──────────────┴────────────────┘
──────────────────────────────────────────────────────────────────
```

### 8.5 Stripe Billing Integration (Enhanced)

```php
// Enhanced billing with metered usage
class AlfredBilling {

    // Create subscription with metered components
    public function createSubscription($customerId, $plan) {
        return \Stripe\Subscription::create([
            'customer' => $customerId,
            'items' => [
                ['price' => $this->getPlanPriceId($plan)],       // Base plan
                ['price' => $this->getMeteredPrice('api_calls')], // Metered API
                ['price' => $this->getMeteredPrice('voice_min')], // Metered voice
                ['price' => $this->getMeteredPrice('storage')],   // Metered storage
                ['price' => $this->getMeteredPrice('agents')],    // Metered agents
            ],
            'payment_behavior' => 'default_incomplete',
            'expand' => ['latest_invoice.payment_intent']
        ]);
    }

    // Report usage for metered billing
    public function reportUsage($subscriptionItemId, $quantity, $action) {
        \Stripe\SubscriptionItem::createUsageRecord($subscriptionItemId, [
            'quantity'  => $quantity,
            'timestamp' => time(),
            'action'    => $action // 'increment' or 'set'
        ]);
    }

    // Check if user is within tier limits
    public function checkLimits($userId, $resource) {
        $usage = $this->getCurrentUsage($userId, $resource);
        $limit = $this->getTierLimit($userId, $resource);

        if ($usage >= $limit) {
            // Check if overage billing is enabled
            if ($this->hasOverageBilling($userId)) {
                $this->reportUsage($userId, 1, 'increment');
                return ['allowed' => true, 'overage' => true];
            }
            return ['allowed' => false, 'upgrade_url' => '/pricing'];
        }
        return ['allowed' => true, 'overage' => false, 'remaining' => $limit - $usage];
    }
}
```

### 8.6 Phase 6 Deliverables

| Task | Est. Time | Sprint | Dependencies |
|------|-----------|--------|-------------|
| Pricing page update (6 tiers) | 2 days | Concurrent | Design |
| Stripe metered billing setup | 3 days | Concurrent | Stripe API |
| Usage tracking middleware | 3 days | Concurrent | Redis |
| Usage dashboard (user-facing) | 2 days | Concurrent | Usage tracking |
| Overage billing alerts (80%, 90%, 100%) | 1 day | Concurrent | Usage tracking |
| Marketplace commission system | 3 days | Concurrent | Stripe Connect |
| Creator payout automation | 2 days | Concurrent | Stripe Connect |
| Annual billing option | 1 day | Concurrent | Stripe |
| Revenue analytics dashboard (admin) | 3 days | Concurrent | All billing |

---

## 9. PHASE 7: GROWTH & MARKETING

**Priority: P1 — Distribution is everything**
**Effort: Ongoing (starts Sprint 8, never stops)**

### 9.1 Affiliate Program

```
AFFILIATE PROGRAM:
──────────────────────────────────────────────────────────────────

  COMMISSION: 20% recurring (for the lifetime of the referral)

  TIERS:
  ┌───────────────┬────────────┬────────────┬────────────────────┐
  │               │ Bronze     │ Silver     │ Gold               │
  ├───────────────┼────────────┼────────────┼────────────────────┤
  │ Referrals     │ 1-10       │ 11-50      │ 51+                │
  │ Commission    │ 20%        │ 25%        │ 30%                │
  │ Cookie Window │ 30 days    │ 60 days    │ 90 days            │
  │ Payout Min    │ $50        │ $25        │ $10                │
  │ Resources     │ Link + badge│ + banners │ + co-marketing     │
  │ Support       │ Email      │ Slack      │ Dedicated manager  │
  └───────────────┴────────────┴────────────┴────────────────────┘

  TOOLS:
  • Unique referral links (https://gositeme.com/?ref=PARTNER_ID)
  • Real-time dashboard (clicks, signups, conversions, revenue)
  • Marketing assets (banners, social posts, email templates)
  • API for tracking (for advanced affiliates)
  • Sub-affiliate tracking (two-tier)

  PAYOUT:
  • Via Stripe (bank transfer or PayPal)
  • Monthly payout (NET-15)
  • Minimum $50 (or $25/$10 for higher tiers)
──────────────────────────────────────────────────────────────────
```

### 9.2 Partner Program

```
PARTNER TIERS:
──────────────────────────────────────────────────────────────────

  REFERRAL PARTNER (agencies, consultants)
  • Refer clients, earn 20% recurring
  • Co-branded landing page
  • Partner directory listing
  • Quarterly business review

  SOLUTIONS PARTNER (implement for clients)
  • Everything in Referral +
  • Technical training + certification
  • Access to beta features
  • Joint case studies
  • Deal registration (protected leads)
  • 30% discount for client accounts

  TECHNOLOGY PARTNER (integrate with Alfred)
  • API integration support
  • Co-marketing opportunities
  • Joint product development
  • Listed in integration directory
  • Developer sandbox

  STRATEGIC PARTNER (enterprise co-sell)
  • Everything in Solutions +
  • Dedicated partner manager
  • Executive sponsorship
  • Revenue sharing on joint deals
  • Roadmap input
──────────────────────────────────────────────────────────────────
```

### 9.3 Launch Strategy

```
LAUNCH TIMELINE:
──────────────────────────────────────────────────────────────────

  WEEK 1: SOFT LAUNCH
  • Product Hunt launch (target top 5 of the day)
  • Hacker News "Show HN" post
  • Reddit posts (r/webdev, r/artificial, r/SaaS, r/startups)
  • Twitter/X announcement thread (25-tweet deep dive)
  • LinkedIn article from founder

  WEEK 2: PRESS COVERAGE
  • TechCrunch pitch (AI + voice angle)
  • The Verge pitch (1,290 tools angle)
  • VentureBeat pitch (enterprise AI angle)
  • Canadian tech press (BetaKit, IT World Canada)
  • Quebec tech press (Les Affaires, Infopresse)

  WEEK 3: COMMUNITY
  • Discord community launch (alfred.gg)
  • Twitter Spaces: "Building an AI with 1,290 Tools" live Q&A
  • YouTube launch video (3-minute product demo)
  • Dev.to article series (5 posts)

  WEEK 4: CONTENT BLITZ
  • 10 YouTube tutorials (one per tool category)
  • 5 case studies (real customer stories)
  • SEO blog posts (20 articles targeting long-tail keywords)
  • Podcast guest appearances (5 bookings)

  ONGOING:
  • Weekly YouTube video
  • Monthly podcast episode ("Building with Alfred")
  • Weekly newsletter (tips, new tools, case studies)
  • Conference presence (see below)
──────────────────────────────────────────────────────────────────
```

### 9.4 Conference Schedule

```
TARGET CONFERENCES (2026-2027):
┌────────────────────────────┬──────────────┬──────────────────┐
│ Conference                 │ Date         │ Goal             │
├────────────────────────────┼──────────────┼──────────────────┤
│ Collision (Toronto)        │ Jun 2026     │ Booth + talk     │
│ Startupfest (Montreal)     │ Jul 2026     │ Founder pitch    │
│ Web Summit (Lisbon)        │ Nov 2026     │ Alpha booth      │
│ CES (Las Vegas)            │ Jan 2027     │ Demo showcase    │
│ TechCrunch Disrupt (SF)    │ Oct 2026     │ Startup Alley    │
│ AI Summit (New York)       │ Dec 2026     │ Speaker slot     │
│ PyCon (Pittsburgh)         │ May 2026     │ SDK workshop     │
│ JSConf (Berlin)            │ Jun 2026     │ SDK workshop     │
│ WordCamp (Various)         │ Ongoing      │ WordPress tools  │
│ GTEC (Ottawa)              │ Nov 2026     │ Gov/enterprise   │
└────────────────────────────┴──────────────┴──────────────────┘
```

### 9.5 SEO Strategy

```
SEO TARGETS (100 long-tail keywords):
──────────────────────────────────────────────────────────────────

  HIGH INTENT (bottom-of-funnel):
  • "AI tools for web hosting"
  • "AI website builder with voice"
  • "voice AI for customer support"
  • "AI fleet management platform"
  • "white-label AI agent"

  MID INTENT (middle-of-funnel):
  • "best AI tools for small business [YEAR]"
  • "AI vs human customer support"
  • "how to automate web hosting with AI"
  • "AI tools for legal aid"
  • "bilingual AI assistant English French"

  LOW INTENT (top-of-funnel):
  • "what is an AI agent"
  • "how AI tools save time for businesses"
  • "future of voice AI"
  • "AI tools for students"
  • "building AI agents tutorial"

  CONTENT PLAN:
  • 50 blog posts (2,000+ words each, weekly)
  • 20 comparison pages ("Alfred vs ChatGPT", "Alfred vs Intercom")
  • 30 use-case pages ("Alfred for Law Firms", "Alfred for Dentists")
  • 100 tool-specific landing pages (1 per tool category)
  • 10 integration pages ("Alfred + WordPress", "Alfred + Shopify")
──────────────────────────────────────────────────────────────────
```

### 9.6 Student & Non-Profit Programs

```
STUDENT DISCOUNT (50% off):
  • Valid .edu email required
  • Applies to Starter and Professional tiers
  • Annual renewal (re-verify each year)
  • Campus ambassador program (free Pro for referrals)

NON-PROFIT PROGRAM (free tier + extras):
  • Registered 501(c)(3) or Canadian equivalent
  • Free Professional tier (up to 5 users)
  • 50% off Enterprise for larger orgs
  • Featured in "Alfred for Good" showcase

GOVERNMENT / INSTITUTIONAL:
  • RFP response team (dedicated)
  • AODA / ADA accessibility compliance
  • French-language compliance (Quebec Law 101)
  • Security questionnaire auto-fill (SOC 2, PIPEDA)
  • Custom procurement process support
  • Standing offer agreements (Canada)
```

### 9.7 Phase 7 Deliverables

| Task | Est. Time | Sprint | Dependencies |
|------|-----------|--------|-------------|
| Affiliate tracking system | 3 days | Sprint 8 | Stripe |
| Affiliate dashboard | 2 days | Sprint 8 | Tracking system |
| Partner portal (4 tiers) | 3 days | Sprint 9 | Auth system |
| Product Hunt preparation | 2 days | Sprint 10 | Product ready |
| YouTube channel launch (5 videos) | 5 days | Sprint 10 | Product demos |
| SEO content plan (50 articles) | 3 days | Sprint 8 | Keyword research |
| Blog CMS for articles | 2 days | Sprint 9 | None |
| Student verification system | 1 day | Sprint 11 | Auth system |
| Non-profit application flow | 1 day | Sprint 11 | Billing |
| Conference prep materials | 3 days | Sprint 14 | Brand assets |
| Newsletter system (weekly) | 1 day | Sprint 8 | Email service |
| Discord community setup | 1 day | Sprint 8 | None |

---

## 10. PHASE 8: MOBILE & NATIVE

**Priority: P2 — Expand reach after core is solid**
**Effort: 6 weeks (Sprints 16-21) — overlaps with AI Evolution**

### 10.1 iOS App

```
ALFRED iOS APP — "Alfred in Your Pocket"
──────────────────────────────────────────────────────────────────

  CORE FEATURES:
  • Voice chat with Alfred (hold-to-talk or hands-free)
  • Text chat (same as web)
  • Agent management (create, deploy, monitor)
  • Fleet dashboard (real-time status)
  • Tool execution (all 1,290+ tools)
  • Push notifications (agent alerts, call events, SLA warnings)
  • Biometric auth (Face ID, Touch ID, voice print)
  • Offline mode (queue commands, sync when online)
  • Widget (home screen — quick voice command)
  • Shortcuts integration (Siri: "Hey Siri, ask Alfred to...")
  • Apple Watch companion (voice commands from wrist)

  TECH STACK:
  • Swift + SwiftUI
  • WebSocket (real-time)
  • WebRTC (voice calls via LiveKit iOS SDK)
  • Core Data (offline storage)
  • Push: APNs
  • Distribution: App Store

  TIMELINE:
  • Sprint 16-17: Core chat + voice
  • Sprint 18-19: Agent management + fleet
  • Sprint 20-21: Polish, beta, App Store submission
──────────────────────────────────────────────────────────────────
```

### 10.2 Android App

```
ALFRED ANDROID APP — Same Features, Native Experience
──────────────────────────────────────────────────────────────────

  TECH STACK:
  • Kotlin + Jetpack Compose
  • WebSocket (OkHttp)
  • WebRTC (LiveKit Android SDK)
  • Room DB (offline storage)
  • Push: FCM (Firebase Cloud Messaging)
  • Distribution: Google Play Store

  ANDROID-SPECIFIC:
  • Material You theming (dynamic colors)
  • Home screen widget (voice command shortcut)
  • Google Assistant integration ("Hey Google, ask Alfred...")
  • Wear OS companion (voice from wrist)
  • Android Auto support (voice-only while driving)

  TIMELINE: Parallel with iOS (same sprints)
──────────────────────────────────────────────────────────────────
```

### 10.3 Chrome Extension

```
ALFRED CHROME EXTENSION — Alfred on Any Webpage
──────────────────────────────────────────────────────────────────

  FEATURES:
  • Floating Alfred icon on every page
  • Click to chat (text or voice)
  • Right-click context menu:
    - "Ask Alfred about this page"
    - "Summarize this article"
    - "Translate this page"
    - "Check this site's SEO"
    - "Find security issues on this site"
  • Page analysis (auto-detect issues)
  • Screenshot → Alfred (image understanding)
  • Form auto-fill with Alfred-generated content
  • Dev Tools panel (Alfred debugging assistant)

  TECH STACK:
  • Manifest V3
  • React (popup + side panel)
  • WebSocket (real-time)
  • Chrome Storage API (settings)
  • Content Script (page interaction)

  PERMISSIONS:
  • activeTab (current page only)
  • storage (settings)
  • contextMenus (right-click)
  • notifications (alerts)
──────────────────────────────────────────────────────────────────
```

### 10.4 VS Code Extension

```
ALFRED VS CODE EXTENSION — Alfred in Your Editor
──────────────────────────────────────────────────────────────────

  FEATURES:
  • Side panel chat (text + voice)
  • Inline code suggestions (like Copilot but with 1,290 tools)
  • Voice commands: "Alfred, refactor this function"
  • Multi-file edits (voice-driven)
  • Git operations via voice
  • Terminal commands via voice
  • Debug with Alfred (explain error, suggest fix)
  • Code review (select code → "Alfred, review this")
  • Documentation generation
  • Test generation

  COMMANDS:
  • alfred.chat        — Open chat panel
  • alfred.voice       — Start voice session
  • alfred.explain     — Explain selected code
  • alfred.refactor    — Refactor selected code
  • alfred.test        — Generate tests for selected code
  • alfred.document    — Generate JSDoc/docstring
  • alfred.review      — Code review with suggestions
  • alfred.deploy      — Deploy current project
  • alfred.terminal    — Execute terminal command via voice

  TECH STACK:
  • TypeScript
  • VS Code Extension API
  • WebSocket (real-time)
  • WebView (chat UI)
  • Language Server Protocol (code context)
──────────────────────────────────────────────────────────────────
```

### 10.5 Platform Integrations

```
INTEGRATIONS:
┌────────────────────┬─────────────────────────────────────────────┐
│ Platform           │ Features                                    │
├────────────────────┼─────────────────────────────────────────────┤
│ Slack              │ /alfred command, DM bot, channel assistant  │
│ Discord            │ Bot with slash commands, voice channel join │
│ Microsoft Teams    │ Bot framework, meeting assistant            │
│ Zapier             │ 50+ triggers and actions                    │
│ Make (Integromat)  │ Alfred module with all tools                │
│ n8n                │ Custom node (already partial)               │
│ GitHub             │ PR review bot, issue triage, CI assistant   │
│ Notion             │ AI writing assistant, database queries      │
│ Linear             │ Issue creation, sprint planning assistant   │
│ Jira               │ Ticket management, release notes            │
└────────────────────┴─────────────────────────────────────────────┘
```

### 10.6 CLI Tool

```bash
# alfred-cli — Command Line Access to All 1,290+ Tools
# npm install -g alfred-cli

# Authentication
$ alfred login
Enter API key: ak_live_abc123...
✓ Authenticated as user@example.com (Professional plan)

# Chat
$ alfred chat "What's the status of my fleet?"
Your fleet "Support Team" has 5 agents:
  ✅ Agent-1 (idle, 23 calls today)
  ✅ Agent-2 (on call, 4m 23s)
  ✅ Agent-3 (idle, 18 calls today)
  ⚠️  Agent-4 (error, last heartbeat 2m ago)
  ✅ Agent-5 (processing, tool: seo_analyzer)

# Execute tools directly
$ alfred exec ssl_check --domain shop.example.com
SSL Certificate for shop.example.com:
  Issuer: Let's Encrypt
  Expires: 2026-07-15 (102 days remaining)
  Grade: A+

# Pipe input
$ cat error.log | alfred chat "Analyze these errors"

# JSON output (for scripting)
$ alfred exec database_backup --db production --format json
{"status":"success","file":"backup_2026-04-15.sql.gz","size":"145MB"}

# Interactive mode
$ alfred interactive
alfred> deploy my wordpress site to staging
On it. Running 4 tools...
[1/4] dns_check: ✅ staging.example.com resolves
[2/4] wordpress_install: ✅ WordPress 6.8 installed
[3/4] ssl_install: ✅ SSL certificate active
[4/4] site_health: ✅ All checks passed
Your WordPress site is live at https://staging.example.com

# Voice mode (requires microphone)
$ alfred voice
🎤 Listening... (say "Alfred" to activate)
```

### 10.7 Phase 8 Deliverables

| Task | Est. Time | Sprint | Dependencies |
|------|-----------|--------|-------------|
| iOS app (core: chat + voice) | 10 days | Sprint 16-17 | API + WebSocket |
| iOS app (agents + fleet) | 6 days | Sprint 18-19 | iOS core |
| iOS App Store submission | 2 days | Sprint 20 | iOS complete |
| Android app (core: chat + voice) | 10 days | Sprint 16-17 | API + WebSocket |
| Android app (agents + fleet) | 6 days | Sprint 18-19 | Android core |
| Google Play submission | 2 days | Sprint 20 | Android complete |
| Chrome extension | 5 days | Sprint 18 | API |
| VS Code extension | 5 days | Sprint 19 | API + LSP |
| Slack integration | 3 days | Sprint 20 | API |
| Discord bot | 2 days | Sprint 20 | API |
| Microsoft Teams bot | 3 days | Sprint 21 | API |
| CLI tool (alfred-cli) | 3 days | Sprint 17 | API |
| Apple Watch companion | 2 days | Sprint 21 | iOS app |

---

## 11. IMPLEMENTATION TIMELINE

### 20 Sprints Over 20 Weeks (April — August 2026)

```
TIMELINE OVERVIEW:
──────────────────────────────────────────────────────────────────

  APR 2026         MAY 2026         JUN 2026         JUL 2026         AUG 2026
  ┌──┬──┬──┬──┐   ┌──┬──┬──┬──┐   ┌──┬──┬──┬──┐   ┌──┬──┬──┬──┐   ┌──┬──┬──┬──┐
  │S1│S2│S3│S4│   │S5│S6│S7│S8│   │S9│10│11│12│   │13│14│15│16│   │17│18│19│20│
  └──┴──┴──┴──┘   └──┴──┴──┴──┘   └──┴──┴──┴──┘   └──┴──┴──┴──┘   └──┴──┴──┴──┘
  ████████████     ████████████     ████████████     ████████████     ████████████
  REAL-TIME        DEVELOPER        ENTERPRISE       VOICE            MOBILE + AI
  INFRA            ECOSYSTEM        FEATURES         ADVANCED         EVOLUTION
  (Phase 1)        (Phase 2)        (Phase 3)        (Phase 4)        (Phases 5,8)

  ●────────────────────────────────────────────────────────────────●
  MONETIZATION (Phase 6) — runs concurrent across all sprints
  
  ●────────────────────────────────────────────────────────────────●
  GROWTH & MARKETING (Phase 7) — starts Sprint 8, runs forever
```

### Sprint-by-Sprint Breakdown

```
SPRINT 1 (Week 1 — Apr 1-7):
  ✦ WebSocket server (Node.js, port 3010)
  ✦ Redis pub/sub channels (8 channels)
  ✦ JWT auth for WebSocket connections
  ✦ Agent heartbeat system
  MILESTONE: Real-time event streaming operational

SPRINT 2 (Week 2 — Apr 8-14):
  ✦ Fleet dashboard → real-time upgrade
  ✦ LiveKit server deployment
  ✦ LiveKit room management API
  ✦ Presence system (online/away/busy)
  MILESTONE: Fleet dashboard shows live agent status

SPRINT 3 (Week 3 — Apr 15-21):
  ✦ Typing indicators
  ✦ Cursor sharing (editor collaboration)
  ✦ Call event streaming (VAPI → WebSocket)
  ✦ PM2 integration for WebSocket server
  MILESTONE: Full real-time infrastructure deployed
  REVENUE TARGET: Infrastructure ready for API billing

SPRINT 4 (Week 4 — Apr 22-28):
  ✦ REST API framework (routing, auth, error handling)
  ✦ OAuth2 server (authorize, token, revoke)
  ✦ API key generation and management
  MILESTONE: API accepts authenticated requests

SPRINT 5 (Week 5 — May 1-7):
  ✦ Rate limiting middleware (Redis-backed)
  ✦ Tools API endpoints (list, execute, schema)
  ✦ Agents API endpoints (CRUD, execute)
  MILESTONE: Developers can execute tools via API

SPRINT 6 (Week 6 — May 8-14):
  ✦ Voice API endpoints (call, rooms, history)
  ✦ Webhook dispatch system
  ✦ Developer portal (10 pages)
  MILESTONE: Full API surface available

SPRINT 7 (Week 7 — May 15-21):
  ✦ npm SDK (alfred-ai-sdk)
  ✦ pip SDK (alfred-ai)
  ✦ Composer SDK (gositeme/alfred-ai)
  ✦ Sandbox environment
  ✦ API documentation (OpenAPI/Swagger)
  MILESTONE: SDKs published, sandbox live
  REVENUE TARGET: First API revenue ($500+)

SPRINT 8 (Week 8 — May 22-28):
  ✦ Organization model + schema
  ✦ RBAC permission system
  ✦ Team management UI
  ✦ Affiliate tracking system launch
  ✦ SEO content plan + first 5 blog posts
  ✦ Discord community launch
  MILESTONE: Multi-user orgs, affiliate program live

SPRINT 9 (Week 9 — Jun 1-7):
  ✦ SAML 2.0 SSO integration
  ✦ OAuth/OIDC SSO (Google, Azure AD)
  ✦ Audit logging system
  ✦ Audit log viewer UI
  ✦ Partner portal
  ✦ Blog CMS
  MILESTONE: Enterprise SSO ready

SPRINT 10 (Week 10 — Jun 8-14):
  ✦ White-label configuration
  ✦ Custom domain (CNAME) support
  ✦ SLA monitoring dashboard
  ✦ Product Hunt launch preparation
  ✦ YouTube channel launch (5 videos)
  MILESTONE: White-label deployable
  REVENUE TARGET: $15,000 MRR

SPRINT 11 (Week 11 — Jun 15-21):
  ✦ Data residency configuration
  ✦ Custom agent training pipeline
  ✦ Enterprise admin console
  ✦ Student verification system
  ✦ Non-profit application flow
  MILESTONE: Full enterprise feature set
  REVENUE TARGET: First enterprise deal ($299/mo)

SPRINT 12 (Week 12 — Jun 22-28):
  ✦ LiveKit conference rooms (20+ users)
  ✦ Room management API + UI
  ✦ Voice cloning pipeline (XTTS v2)
  ✦ Voice clone consent + security
  MILESTONE: Advanced voice rooms operational

SPRINT 13 (Week 13 — Jul 1-7):
  ✦ Real-time translation pipeline
  ✦ Emotion detection model
  ✦ Voice biometric enrollment + verification
  MILESTONE: Multi-language voice with emotion AI

SPRINT 14 (Week 14 — Jul 8-14):
  ✦ IVR builder (visual editor)
  ✦ Voicemail transcription + routing
  ✦ Call recording + AI summaries
  ✦ Conference materials preparation
  MILESTONE: Enterprise-grade voice system
  REVENUE TARGET: $30,000 MRR

SPRINT 15 (Week 15 — Jul 15-21):
  ✦ Outbound calling campaign engine
  ✦ SMS integration (Telnyx)
  ✦ WhatsApp Business integration
  ✦ Unified conversation manager
  MILESTONE: Omnichannel communication complete

SPRINT 16 (Week 16 — Jul 22-28):
  ✦ Training data pipeline (anonymize + format)
  ✦ Fine-tune Llama 3.3 70B
  ✦ Tool auto-discovery engine
  ✦ iOS app: core chat + voice
  ✦ Android app: core chat + voice
  MILESTONE: Custom AI model + mobile apps in development

SPRINT 17 (Week 17 — Jul 29 - Aug 4):
  ✦ Agent-to-agent negotiation protocol
  ✦ Coordinator agent (task decomposition)
  ✦ Autonomous goal completion engine
  ✦ Safety rails (spending limits, rollback)
  ✦ CLI tool (alfred-cli)
  ✦ iOS app: continued development
  ✦ Android app: continued development
  MILESTONE: Autonomous AI operational
  REVENUE TARGET: $50,000 MRR

SPRINT 18 (Week 18 — Aug 5-11):
  ✦ Multi-file code generation
  ✦ Image understanding (GPT-4o vision)
  ✦ Document understanding (PDF/contract)
  ✦ Autonomous mode UI (progress streaming)
  ✦ Chrome extension
  ✦ iOS app: agents + fleet
  ✦ Android app: agents + fleet
  MILESTONE: Multimodal AI live

SPRINT 19 (Week 19 — Aug 12-18):
  ✦ VS Code extension
  ✦ iOS app: polish + beta test
  ✦ Android app: polish + beta test
  MILESTONE: Alfred available on every platform

SPRINT 20 (Week 20 — Aug 19-25):
  ✦ iOS App Store submission
  ✦ Google Play submission
  ✦ Slack integration
  ✦ Discord bot
  ✦ Final QA across all platforms
  ✦ Performance optimization
  ✦ Security audit
  MILESTONE: v12.0 LAUNCH — Project Phoenix Complete
  REVENUE TARGET: $100,000 MRR
```

### Milestones Tied to Revenue

```
REVENUE MILESTONES:
┌────────────┬────────────┬─────────────────────────────────────────┐
│ Date       │ MRR Target │ Unlock Condition                        │
├────────────┼────────────┼─────────────────────────────────────────┤
│ Apr 30     │ $5,000     │ API billing live, first paying devs     │
│ May 31     │ $15,000    │ SDKs live, Product Hunt launch          │
│ Jun 15     │ $20,000    │ Enterprise SSO, first enterprise deal   │
│ Jun 30     │ $30,000    │ White-label, voice cloning              │
│ Jul 31     │ $50,000    │ Omnichannel, outbound campaigns         │
│ Aug 25     │ $100,000   │ Mobile apps, full platform, scale       │
└────────────┴────────────┴─────────────────────────────────────────┘
```

---

## 12. TECHNICAL ARCHITECTURE

### 12.1 Updated System Diagram

```
┌──────────────────────────────────────────────────────────────────────────────┐
│                          ALFRED v12.0 — PROJECT PHOENIX                      │
│                                                                              │
│  ┌─────────────────────────────────────────────────────────────────────────┐ │
│  │                           CLIENT LAYER                                  │ │
│  │                                                                         │ │
│  │  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐     │ │
│  │  │ Web App  │ │ iOS App  │ │ Android  │ │ Chrome   │ │ VS Code  │     │ │
│  │  │ (React)  │ │ (Swift)  │ │ (Kotlin) │ │ Ext.     │ │ Ext.     │     │ │
│  │  └────┬─────┘ └────┬─────┘ └────┬─────┘ └────┬─────┘ └────┬─────┘     │ │
│  │       │             │            │             │            │           │ │
│  │  ┌────┴─────┐ ┌─────┴────┐ ┌────┴─────┐ ┌────┴─────┐ ┌───┴──────┐   │ │
│  │  │ CLI      │ │ Slack    │ │ Discord  │ │ Teams    │ │ Zapier   │   │ │
│  │  │ (alfred) │ │ Bot      │ │ Bot      │ │ Bot      │ │ Module   │   │ │
│  │  └────┬─────┘ └────┬─────┘ └────┬─────┘ └────┬─────┘ └───┬──────┘   │ │
│  └───────┴────────────┴────────────┴─────────────┴───────────┴──────────┘ │
│          │                         │                         │             │
│  ┌───────▼─────────────────────────▼─────────────────────────▼───────────┐ │
│  │                          GATEWAY LAYER                                │ │
│  │                                                                       │ │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐                │ │
│  │  │ API Gateway  │  │ WebSocket    │  │ LiveKit      │                │ │
│  │  │ (REST + OAuth)│  │ Server       │  │ (WebRTC)     │                │ │
│  │  │ Port 443     │  │ Port 3010    │  │ Port 7880    │                │ │
│  │  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘                │ │
│  │         │                 │                  │                        │ │
│  │  ┌──────▼─────────────────▼──────────────────▼───────┐                │ │
│  │  │                  MIDDLEWARE                         │                │ │
│  │  │  • Rate Limiting    • JWT Validation               │                │ │
│  │  │  • Usage Metering   • RBAC Check                   │                │ │
│  │  │  • Audit Logging    • Request Routing               │                │ │
│  │  └──────────────────────┬─────────────────────────────┘                │ │
│  └─────────────────────────┴─────────────────────────────────────────────┘ │
│                                │                                           │
│  ┌─────────────────────────────▼─────────────────────────────────────────┐ │
│  │                          SERVICE LAYER                                │ │
│  │                                                                       │ │
│  │  ┌───────────┐ ┌───────────┐ ┌───────────┐ ┌───────────┐            │ │
│  │  │callAlfred │ │  MCP      │ │  VAPI     │ │  Fleet    │            │ │
│  │  │(AI Brain) │ │  Server   │ │  Webhook  │ │  Manager  │            │ │
│  │  │Multi-model│ │  807 tools│ │  485 routes│ │           │            │ │
│  │  └─────┬─────┘ └─────┬─────┘ └─────┬─────┘ └─────┬─────┘            │ │
│  │        │              │              │              │                  │ │
│  │  ┌─────┴─────┐ ┌─────┴─────┐ ┌─────┴─────┐ ┌─────┴─────┐            │ │
│  │  │Conscious- │ │Marketplace│ │ Webhook   │ │ Campaign  │            │ │
│  │  │ness Layer │ │ Engine    │ │ Dispatch  │ │ Engine    │            │ │
│  │  │Memory+    │ │           │ │           │ │(Outbound) │            │ │
│  │  │Personality│ │           │ │           │ │           │            │ │
│  │  └───────────┘ └───────────┘ └───────────┘ └───────────┘            │ │
│  └───────────────────────────────────────────────────────────────────────┘ │
│                                │                                           │
│  ┌─────────────────────────────▼─────────────────────────────────────────┐ │
│  │                          DATA LAYER                                   │ │
│  │                                                                       │ │
│  │  ┌───────────┐ ┌───────────┐ ┌───────────┐ ┌───────────┐            │ │
│  │  │  MySQL    │ │  Redis    │ │  pgvector │ │  S3       │            │ │
│  │  │  14+      │ │  Cache +  │ │  Embeddings│ │  Files +  │            │ │
│  │  │  tables   │ │  Pub/Sub  │ │  + RAG    │ │  Recordings│           │ │
│  │  └───────────┘ └───────────┘ └───────────┘ └───────────┘            │ │
│  └───────────────────────────────────────────────────────────────────────┘ │
│                                                                              │
│  ┌───────────────────────────────────────────────────────────────────────┐   │
│  │                          AI MODEL LAYER                               │   │
│  │                                                                       │   │
│  │  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐  │   │
│  │  │ Groq     │ │OpenRouter│ │Anthropic │ │Together  │ │ OpenAI   │  │   │
│  │  │ Llama3.3 │ │ Router   │ │ Claude   │ │ Fine-tune│ │ GPT-4o   │  │   │
│  │  │ (fast)   │ │ (any)    │ │ (smart)  │ │ (custom) │ │ (vision) │  │   │
│  │  └──────────┘ └──────────┘ └──────────┘ └──────────┘ └──────────┘  │   │
│  └───────────────────────────────────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────────────────────────────┘
```

### 12.2 Database Schema Additions

```sql
-- ======================================================================
-- PROJECT PHOENIX — DATABASE SCHEMA ADDITIONS
-- Adds to existing 14 alfred_* tables from Master Plans 1 & 2
-- ======================================================================

-- 1. Organizations (multi-user)
-- alfred_organizations      (see Phase 3, Section 5.1)
-- alfred_org_members         (see Phase 3, Section 5.1)
-- alfred_org_teams           (see Phase 3, Section 5.1)
-- alfred_org_team_members    (see Phase 3, Section 5.1)

-- 2. Audit Log
-- alfred_audit_log           (see Phase 3, Section 5.4)

-- 3. API Management
CREATE TABLE alfred_api_keys (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    org_id          INT,
    key_prefix      VARCHAR(12) NOT NULL,
    key_hash        VARCHAR(64) NOT NULL,
    name            VARCHAR(255) NOT NULL,
    scopes          JSON NOT NULL,
    rate_limit_tier VARCHAR(20) DEFAULT 'free',
    last_used_at    TIMESTAMP NULL,
    expires_at      TIMESTAMP NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    revoked_at      TIMESTAMP NULL,
    INDEX idx_prefix (key_prefix),
    INDEX idx_user (user_id),
    FOREIGN KEY (user_id) REFERENCES alfred_users(id)
);

CREATE TABLE alfred_oauth_apps (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    client_id       VARCHAR(64) UNIQUE NOT NULL,
    client_secret   VARCHAR(128) NOT NULL,
    name            VARCHAR(255) NOT NULL,
    description     TEXT,
    redirect_uris   JSON NOT NULL,
    scopes          JSON NOT NULL,
    logo_url        VARCHAR(500),
    website_url     VARCHAR(500),
    is_approved     BOOLEAN DEFAULT FALSE,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES alfred_users(id)
);

CREATE TABLE alfred_oauth_tokens (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    app_id          INT NOT NULL,
    user_id         INT NOT NULL,
    access_token    VARCHAR(128) NOT NULL,
    refresh_token   VARCHAR(128),
    scopes          JSON NOT NULL,
    expires_at      TIMESTAMP NOT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    revoked_at      TIMESTAMP NULL,
    INDEX idx_token (access_token),
    FOREIGN KEY (app_id) REFERENCES alfred_oauth_apps(id),
    FOREIGN KEY (user_id) REFERENCES alfred_users(id)
);

-- 4. Usage Tracking
CREATE TABLE alfred_usage (
    id              BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    org_id          INT,
    resource        ENUM('api_call','voice_minute','agent_hour','storage_mb',
                         'sms','whatsapp','image_gen','doc_analysis',
                         'translation_min','conference_min') NOT NULL,
    quantity        DECIMAL(10,4) NOT NULL,
    unit_cost       DECIMAL(10,6),
    is_overage      BOOLEAN DEFAULT FALSE,
    metadata        JSON,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_resource (user_id, resource, created_at),
    INDEX idx_org (org_id, created_at)
);

-- 5. Webhooks
CREATE TABLE alfred_webhooks (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    org_id          INT,
    url             VARCHAR(500) NOT NULL,
    events          JSON NOT NULL,
    secret          VARCHAR(128) NOT NULL,
    is_active       BOOLEAN DEFAULT TRUE,
    failure_count   INT DEFAULT 0,
    last_triggered  TIMESTAMP NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES alfred_users(id)
);

CREATE TABLE alfred_webhook_deliveries (
    id              BIGINT AUTO_INCREMENT PRIMARY KEY,
    webhook_id      INT NOT NULL,
    event           VARCHAR(100) NOT NULL,
    payload         JSON NOT NULL,
    response_code   INT,
    response_body   TEXT,
    duration_ms     INT,
    status          ENUM('pending','success','failed','retrying') DEFAULT 'pending',
    attempts        INT DEFAULT 0,
    next_retry_at   TIMESTAMP NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_webhook (webhook_id, created_at),
    FOREIGN KEY (webhook_id) REFERENCES alfred_webhooks(id)
);

-- 6. Voice Cloning
CREATE TABLE alfred_voice_profiles (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    name            VARCHAR(255) NOT NULL,
    status          ENUM('pending','training','ready','failed') DEFAULT 'pending',
    sample_url      VARCHAR(500),
    model_path      VARCHAR(500),
    quality_score   DECIMAL(3,2),
    consent_signed  BOOLEAN DEFAULT FALSE,
    consent_date    TIMESTAMP NULL,
    languages       JSON DEFAULT '["en"]',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES alfred_users(id)
);

-- 7. Campaigns (outbound calling)
CREATE TABLE alfred_campaigns (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    org_id          INT,
    user_id         INT NOT NULL,
    name            VARCHAR(255) NOT NULL,
    agent_id        INT NOT NULL,
    status          ENUM('draft','scheduled','running','paused','completed') DEFAULT 'draft',
    contacts_total  INT DEFAULT 0,
    contacts_called INT DEFAULT 0,
    contacts_reached INT DEFAULT 0,
    conversion_count INT DEFAULT 0,
    schedule        JSON,
    settings        JSON,
    started_at      TIMESTAMP NULL,
    completed_at    TIMESTAMP NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES alfred_users(id)
);

-- 8. Affiliates
CREATE TABLE alfred_affiliates (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    partner_id      VARCHAR(20) UNIQUE NOT NULL,
    tier            ENUM('bronze','silver','gold') DEFAULT 'bronze',
    commission_rate DECIMAL(4,2) DEFAULT 20.00,
    total_referrals INT DEFAULT 0,
    total_revenue   DECIMAL(12,2) DEFAULT 0.00,
    total_paid      DECIMAL(12,2) DEFAULT 0.00,
    payout_method   ENUM('stripe','paypal','bank') DEFAULT 'stripe',
    payout_details  JSON,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES alfred_users(id)
);

CREATE TABLE alfred_referrals (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    affiliate_id    INT NOT NULL,
    referred_user_id INT NOT NULL,
    status          ENUM('clicked','signed_up','converted','churned') DEFAULT 'clicked',
    revenue_generated DECIMAL(12,2) DEFAULT 0.00,
    commission_earned DECIMAL(12,2) DEFAULT 0.00,
    first_click_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    signup_at       TIMESTAMP NULL,
    conversion_at   TIMESTAMP NULL,
    FOREIGN KEY (affiliate_id) REFERENCES alfred_affiliates(id),
    FOREIGN KEY (referred_user_id) REFERENCES alfred_users(id)
);

-- SUMMARY: 14 new tables (30 total with Master Plan 1 & 2)
-- alfred_organizations, alfred_org_members, alfred_org_teams,
-- alfred_org_team_members, alfred_audit_log, alfred_api_keys,
-- alfred_oauth_apps, alfred_oauth_tokens, alfred_usage,
-- alfred_webhooks, alfred_webhook_deliveries, alfred_voice_profiles,
-- alfred_campaigns, alfred_affiliates, alfred_referrals
```

### 12.3 Infrastructure Requirements

```
INFRASTRUCTURE — PROJECT PHOENIX:
──────────────────────────────────────────────────────────────────

  CURRENT (Post-Ignition):
  ┌──────────────────────────────┬────────────────────────────────┐
  │ Component                    │ Spec                           │
  ├──────────────────────────────┼────────────────────────────────┤
  │ Web Server                   │ Shared hosting (LiteSpeed)     │
  │ Database                     │ MySQL 8.0 (shared)             │
  │ Redis                        │ Single instance (6379)         │
  │ Node.js Processes            │ 4 via PM2                      │
  │ Storage                      │ Shared disk (~50GB)            │
  └──────────────────────────────┴────────────────────────────────┘

  TARGET (Phoenix):
  ┌──────────────────────────────┬────────────────────────────────┐
  │ Component                    │ Spec                           │
  ├──────────────────────────────┼────────────────────────────────┤
  │ Web Server                   │ Dedicated (8 vCPU, 32GB RAM)   │
  │ Database                     │ MySQL 8.0 (dedicated, 16GB)    │
  │ Redis                        │ Redis Cluster (3 nodes)        │
  │ Node.js Processes            │ 8+ via PM2 (cluster mode)      │
  │ LiveKit Server               │ Dedicated (4 vCPU, 16GB)      │
  │ GPU Server (AI)              │ 1x A100 or 2x L40S            │
  │ Object Storage (S3)          │ 1 TB (recordings, uploads)     │
  │ CDN                          │ Cloudflare Pro                 │
  │ pgvector (embeddings)        │ PostgreSQL 16 + pgvector       │
  │ Load Balancer                │ HAProxy or Cloudflare LB       │
  │ Monitoring                   │ Grafana + Prometheus           │
  │ CI/CD                        │ GitHub Actions                 │
  └──────────────────────────────┴────────────────────────────────┘
```

### 12.4 Cost Projections

```
MONTHLY COST PROJECTIONS:
┌────────────────────────────┬───────────┬───────────┬───────────┐
│ Item                       │ Month 1   │ Month 3   │ Month 6   │
├────────────────────────────┼───────────┼───────────┼───────────┤
│ Dedicated Server (web)     │ $200      │ $200      │ $400      │
│ Dedicated Server (DB)      │ $150      │ $150      │ $300      │
│ LiveKit Server             │ $100      │ $100      │ $200      │
│ GPU Server (AI training)   │ $300      │ $500      │ $1,000    │
│ Redis Cluster              │ $50       │ $50       │ $100      │
│ Object Storage (S3)        │ $20       │ $50       │ $150      │
│ CDN (Cloudflare Pro)       │ $20       │ $20       │ $200      │
│ Domain + SSL               │ $10       │ $10       │ $10       │
├────────────────────────────┼───────────┼───────────┼───────────┤
│ AI API Costs:              │           │           │           │
│   Groq                     │ $100      │ $500      │ $2,000    │
│   OpenRouter               │ $50       │ $300      │ $1,000    │
│   Anthropic                │ $50       │ $200      │ $800      │
│   OpenAI (GPT-4o vision)   │ $50       │ $200      │ $500      │
│   Together AI (fine-tune)  │ $100      │ $200      │ $500      │
├────────────────────────────┼───────────┼───────────┼───────────┤
│ Telephony:                 │           │           │           │
│   VAPI                     │ $100      │ $500      │ $2,000    │
│   Telnyx (SMS + voice)     │ $50       │ $200      │ $1,000    │
├────────────────────────────┼───────────┼───────────┼───────────┤
│ Services:                  │           │           │           │
│   Stripe (2.9% + $0.30)   │ $90       │ $870      │ $2,900    │
│   Monitoring (Grafana)     │ $0        │ $0        │ $50       │
│   CI/CD (GitHub Actions)   │ $0        │ $0        │ $20       │
├────────────────────────────┼───────────┼───────────┼───────────┤
│ TOTAL COST                 │ $1,440    │ $4,050    │ $13,130   │
│ TOTAL REVENUE              │ $2,900    │ $30,000   │ $100,000  │
│ NET MARGIN                 │ $1,460    │ $25,950   │ $86,870   │
│ MARGIN %                   │ 50%       │ 87%       │ 87%       │
└────────────────────────────┴───────────┴───────────┴───────────┘
```

### 12.5 PM2 Process Map (Target)

```
PM2 PROCESS LIST (Project Phoenix):
┌──────────────────────┬──────┬─────────┬───────┬────────┐
│ Name                 │ Mode │ Status  │ Port  │ Memory │
├──────────────────────┼──────┼─────────┼───────┼────────┤
│ alfred-mcp           │ fork │ online  │ 3005  │ ~120MB │
│ alfred-middleware     │ fork │ online  │ 3006  │ ~60MB  │
│ alfred-websocket     │ fork │ online  │ 3010  │ ~80MB  │
│ alfred-worker        │ fork │ online  │ —     │ ~50MB  │
│ alfred-api           │ cluster│ online│ 3020  │ ~100MB │
│ alfred-webhook-worker│ fork │ online  │ —     │ ~40MB  │
│ alfred-campaign      │ fork │ online  │ —     │ ~60MB  │
│ alfred-heartbeat     │ fork │ online  │ —     │ ~30MB  │
│ livekit-server       │ fork │ online  │ 7880  │ ~200MB │
│ openclaw             │ fork │ online  │ 3001  │ ~50MB  │
│ alfred-sandbox       │ fork │ online  │ 3030  │ ~80MB  │
└──────────────────────┴──────┴─────────┴───────┴────────┘
Total: 11 processes, ~870MB RAM
```

---

## 13. COMPETITIVE MOAT

### 13.1 What Makes Alfred Impossible to Replicate

```
THE FIVE MOATS:
══════════════════════════════════════════════════════════════════

  1. TOOL MOAT — 1,290+ Tools
  ───────────────────────────
  No competitor has 1,290 tools across 17 categories. Building
  1,290 functional tools takes 18+ months. By the time anyone
  catches up, we'll have 2,000+.

  Competitors:
  • ChatGPT: ~20 built-in tools (browsing, DALL-E, code)
  • Claude: ~5 tools (computer use, text editor)
  • Jasper: ~50 marketing tools
  • Alfred: 1,290+ tools with voice-first execution

  2. VOICE MOAT — Multi-Engine Voice Stack
  ────────────────────────────────────────
  VAPI + LiveKit + Telnyx + voice cloning + emotion detection
  + biometric auth + real-time translation. Nobody else has
  this combined stack.

  Unique capabilities:
  • Execute ANY of 1,290 tools via voice
  • Clone your voice for your agent
  • Detect caller emotion and adapt
  • Translate in real-time across 7 languages
  • Authenticate via voice biometrics

  3. DATA MOAT — Consciousness Layer
  ──────────────────────────────────
  Every interaction makes Alfred smarter for THAT specific user.
  Persistent memory, personality adaptation, relationship scoring.
  Switching to a competitor means starting from scratch.

  Lock-in metrics:
  • 30-day relationship score
  • Learned preferences (100+ data points per user)
  • Custom tool configurations
  • Agent training data (enterprise)

  4. NETWORK MOAT — Marketplace Effects
  ─────────────────────────────────────
  More users → more tool creators → more tools → more users.
  Classic network effect. First marketplace for AI tools with
  revenue sharing.

  Flywheel:
  Creators → Tools → Users → Revenue → More Creators → ...

  5. ECOSYSTEM MOAT — Developer Platform
  ──────────────────────────────────────
  SDKs + API + Webhooks + Integrations = developers build ON Alfred.
  Every app built on our API increases switching costs.

  Ecosystem lock-in:
  • Apps built on Alfred API
  • Workflows dependent on Alfred tools
  • Team configurations and RBAC
  • SSO integration with corporate directory
  • Custom training data investment

══════════════════════════════════════════════════════════════════
```

### 13.2 Competitive Landscape

```
COMPETITIVE POSITIONING:
┌─────────────┬────────┬──────┬──────┬──────┬─────────┬─────────┐
│ Feature     │ Alfred │ChatGPT│Claude│Jasper│Intercom │ Bland.ai│
├─────────────┼────────┼──────┼──────┼──────┼─────────┼─────────┤
│ Tools       │ 1,290+   │ ~20  │ ~5   │ ~50  │ ~30     │ ~10     │
│ Voice-first │ ✅      │ ✅    │ ❌    │ ❌    │ ❌       │ ✅       │
│ Fleet mgmt  │ ✅      │ ❌    │ ❌    │ ❌    │ Partial │ ❌       │
│ Marketplace │ ✅      │ ✅    │ ❌    │ ❌    │ ✅       │ ❌       │
│ White-label │ ✅      │ ❌    │ ❌    │ ❌    │ ✅       │ ✅       │
│ Multi-model │ ✅      │ ❌    │ ❌    │ ✅    │ ❌       │ ❌       │
│ SSO/RBAC    │ ✅      │ ✅    │ ✅    │ ✅    │ ✅       │ ❌       │
│ Voice clone │ ✅      │ ❌    │ ❌    │ ❌    │ ❌       │ ✅       │
│ Bilingual   │ ✅ EN/FR│ Multi│ Multi│ Multi│ Multi   │ Multi   │
│ Self-hosted │ ✅      │ ❌    │ ❌    │ ❌    │ ❌       │ ❌       │
│ Starting $  │ $0     │ $20  │ $20  │ $49  │ $74     │ $0.09/m │
└─────────────┴────────┴──────┴──────┴──────┴─────────┴─────────┘
```

---

## 14. RISK ANALYSIS

### 14.1 Technical Risks

```
TECHNICAL RISKS:
┌─────────────────────────────┬──────┬───────────────────────────────────────┐
│ Risk                        │ Prob │ Mitigation                            │
├─────────────────────────────┼──────┼───────────────────────────────────────┤
│ AI API costs exceed revenue │ MED  │ • Fine-tune own model (lower cost)    │
│                             │      │ • Aggressive caching                  │
│                             │      │ • Usage limits per tier               │
│                             │      │ • Groq first (free tier friendly)     │
├─────────────────────────────┼──────┼───────────────────────────────────────┤
│ WebSocket scalability       │ LOW  │ • Redis pub/sub for horizontal scale  │
│ (10,000+ concurrent)        │      │ • WebSocket server cluster mode       │
│                             │      │ • Connection pooling                  │
├─────────────────────────────┼──────┼───────────────────────────────────────┤
│ LiveKit reliability         │ MED  │ • Fallback to Twilio for voice        │
│                             │      │ • TURN servers for NAT traversal      │
│                             │      │ • Health monitoring + auto-restart     │
├─────────────────────────────┼──────┼───────────────────────────────────────┤
│ Voice cloning abuse         │ MED  │ • Strict consent verification         │
│                             │      │ • Audio watermarking                  │
│                             │      │ • Usage logging + kill switch         │
│                             │      │ • Legal ToS + indemnification         │
├─────────────────────────────┼──────┼───────────────────────────────────────┤
│ Data breach                 │ LOW  │ • Encryption at rest (AES-256)        │
│                             │      │ • Encryption in transit (TLS 1.3)     │
│                             │      │ • SOC 2 compliance roadmap            │
│                             │      │ • Regular penetration testing         │
│                             │      │ • Principle of least privilege        │
├─────────────────────────────┼──────┼───────────────────────────────────────┤
│ Single point of failure     │ MED  │ • Multi-region deployment plan        │
│ (shared hosting)            │      │ • Dedicated server migration          │
│                             │      │ • Database replication                │
│                             │      │ • Automated failover                  │
├─────────────────────────────┼──────┼───────────────────────────────────────┤
│ Fine-tuned model quality    │ MED  │ • Extensive eval before production    │
│                             │      │ • A/B test vs base model              │
│                             │      │ • Human-in-the-loop for edge cases    │
│                             │      │ • Gradual rollout (5% → 100%)        │
└─────────────────────────────┴──────┴───────────────────────────────────────┘
```

### 14.2 Market Risks

```
MARKET RISKS:
┌─────────────────────────────┬──────┬───────────────────────────────────────┐
│ Risk                        │ Prob │ Mitigation                            │
├─────────────────────────────┼──────┼───────────────────────────────────────┤
│ OpenAI / Google ships       │ HIGH │ • Differentiate on tools (1,290+)       │
│ similar features            │      │ • Differentiate on voice-first        │
│                             │      │ • Focus on niche (hosting/web dev)    │
│                             │      │ • Offer self-hosted option            │
├─────────────────────────────┼──────┼───────────────────────────────────────┤
│ Slow enterprise adoption    │ MED  │ • Free pilot program (3 months)       │
│                             │      │ • Case studies and ROI calculator     │
│                             │      │ • Partner channel (agencies)          │
│                             │      │ • Compliance certifications           │
├─────────────────────────────┼──────┼───────────────────────────────────────┤
│ Developer SDK adoption      │ MED  │ • Generous free tier (100 req/min)    │
│                             │      │ • Excellent documentation             │
│                             │      │ • SDK examples and tutorials          │
│                             │      │ • Developer advocacy (community)      │
├─────────────────────────────┼──────┼───────────────────────────────────────┤
│ Marketplace supply          │ MED  │ • Seed with 50+ official tools        │
│ (not enough creators)       │      │ • Creator incentive program           │
│                             │      │ • Hackathons with prizes              │
│                             │      │ • Revenue sharing (up to 85%)         │
├─────────────────────────────┼──────┼───────────────────────────────────────┤
│ Pricing pressure            │ MED  │ • Free tier as top-of-funnel          │
│ (race to bottom)            │      │ • Value on enterprise features        │
│                             │      │ • Usage-based = fair pricing          │
│                             │      │ • Tool moat justifies premium         │
└─────────────────────────────┴──────┴───────────────────────────────────────┘
```

### 14.3 Regulatory Risks

```
REGULATORY RISKS:
┌─────────────────────────────┬──────┬───────────────────────────────────────┐
│ Risk                        │ Prob │ Mitigation                            │
├─────────────────────────────┼──────┼───────────────────────────────────────┤
│ EU AI Act compliance        │ HIGH │ • Classify Alfred as "limited risk"   │
│ (effective Aug 2026)        │      │ • Transparency requirements met       │
│                             │      │ • Human oversight for high-risk uses  │
│                             │      │ • Document AI decision-making         │
├─────────────────────────────┼──────┼───────────────────────────────────────┤
│ Quebec Bill 96 /            │ HIGH │ • Full French UI and documentation    │
│ Charter of the French       │      │ • French as default in QC            │
│ language (Law 101)          │      │ • French error messages and alerts    │
│                             │      │ • Quebec-specific terms of service    │
├─────────────────────────────┼──────┼───────────────────────────────────────┤
│ PIPEDA / Quebec Law 25      │ HIGH │ • Privacy impact assessment           │
│ (privacy)                   │      │ • Data minimization                   │
│                             │      │ • Consent management                  │
│                             │      │ • Right to deletion                   │
│                             │      │ • Privacy officer designation         │
│                             │      │ • Breach notification procedures      │
├─────────────────────────────┼──────┼───────────────────────────────────────┤
│ GDPR (for EU customers)     │ MED  │ • Data residency in EU (eu-west)      │
│                             │      │ • Data processing agreements          │
│                             │      │ • Cookie consent management           │
│                             │      │ • Right to portability                │
├─────────────────────────────┼──────┼───────────────────────────────────────┤
│ CRTC telecom regulations    │ MED  │ • Comply with DNCL for outbound       │
│ (outbound calling)          │      │ • STIR/SHAKEN caller ID               │
│                             │      │ • Opt-out on every call               │
│                             │      │ • Recording consent announcements     │
├─────────────────────────────┼──────┼───────────────────────────────────────┤
│ Voice cloning legislation   │ MED  │ • Only clone account holder's voice   │
│ (emerging laws)             │      │ • Watermarking + consent              │
│                             │      │ • No impersonation use cases          │
│                             │      │ • Kill switch capability              │
├─────────────────────────────┼──────┼───────────────────────────────────────┤
│ HIPAA (healthcare tools)    │ LOW  │ • BAA for enterprise healthcare       │
│                             │      │ • PHI encryption and access controls  │
│                             │      │ • Audit logging                       │
│                             │      │ • Data residency in US                │
├─────────────────────────────┼──────┼───────────────────────────────────────┤
│ AODA / ADA (accessibility)  │ LOW  │ • WCAG 2.1 AA compliance              │
│                             │      │ • Screen reader compatible            │
│                             │      │ • Voice-first = inherently accessible │
│                             │      │ • Keyboard navigation                 │
└─────────────────────────────┴──────┴───────────────────────────────────────┘
```

---

## 15. SUCCESS METRICS

### 15.1 Key Performance Indicators (KPIs)

```
PRIMARY METRICS (reviewed weekly):
┌──────────────────────────────┬──────────┬──────────┬──────────┐
│ Metric                       │ Month 1  │ Month 3  │ Month 6  │
├──────────────────────────────┼──────────┼──────────┼──────────┤
│ Monthly Active Users (MAU)   │ 500      │ 5,000    │ 25,000   │
│ Monthly Recurring Revenue    │ $2,900   │ $30,000  │ $100,000 │
│ Annual Run Rate (ARR)        │ $34,800  │ $360,000 │ $1.2M    │
│ Average Revenue Per User     │ $5.80    │ $6.00    │ $4.00    │
│ Customer Acquisition Cost    │ $10      │ $8       │ $5       │
│ Lifetime Value (LTV)         │ $50      │ $80      │ $120     │
│ LTV/CAC Ratio                │ 5x       │ 10x      │ 24x      │
│ Churn Rate (monthly)         │ 8%       │ 5%       │ 3%       │
│ Net Revenue Retention        │ 100%     │ 110%     │ 125%     │
└──────────────────────────────┴──────────┴──────────┴──────────┘
```

### 15.2 Product Metrics

```
PRODUCT METRICS:
┌──────────────────────────────┬──────────┬──────────┬──────────┐
│ Metric                       │ Month 1  │ Month 3  │ Month 6  │
├──────────────────────────────┼──────────┼──────────┼──────────┤
│ Tool Executions (per day)    │ 5,000    │ 50,000   │ 250,000  │
│ Voice Minutes (per day)      │ 100      │ 2,000    │ 15,000   │
│ API Calls (per day)          │ 10,000   │ 100,000  │ 1,000,000│
│ Active Agents                │ 50       │ 500      │ 5,000    │
│ Active Fleets                │ 5        │ 50       │ 500      │
│ Conference Room Sessions/day │ 10       │ 100      │ 500      │
│ Marketplace Listings         │ 20       │ 200      │ 1,000    │
│ Marketplace Transactions/mo  │ 10       │ 200      │ 2,000    │
│ SDK Downloads (cumulative)   │ 100      │ 2,000    │ 15,000   │
│ Webhook Events (per day)     │ 1,000    │ 50,000   │ 500,000  │
│ WebSocket Connections (peak) │ 50       │ 500      │ 5,000    │
└──────────────────────────────┴──────────┴──────────┴──────────┘
```

### 15.3 Quality Metrics

```
QUALITY METRICS:
┌──────────────────────────────┬──────────┬──────────┬──────────┐
│ Metric                       │ Month 1  │ Month 3  │ Month 6  │
├──────────────────────────────┼──────────┼──────────┼──────────┤
│ API Uptime                   │ 99.0%    │ 99.5%    │ 99.9%    │
│ API Response Time (p95)      │ 800ms    │ 400ms    │ 200ms    │
│ Voice Latency (p95)          │ 500ms    │ 250ms    │ 150ms    │
│ Tool Success Rate            │ 90%      │ 95%      │ 98%      │
│ Customer Satisfaction (NPS)  │ 30       │ 50       │ 70       │
│ Support Ticket Resolution    │ 48h      │ 24h      │ 4h       │
│ Bug Fix Time (P1)            │ 24h      │ 8h       │ 2h       │
│ Deploy Frequency             │ Weekly   │ Daily    │ Multi/day│
│ Test Coverage                │ 40%      │ 70%      │ 85%      │
│ Security Audit Findings (P1) │ 0        │ 0        │ 0        │
└──────────────────────────────┴──────────┴──────────┴──────────┘
```

### 15.4 Growth Metrics

```
GROWTH METRICS:
┌──────────────────────────────┬──────────┬──────────┬──────────┐
│ Metric                       │ Month 1  │ Month 3  │ Month 6  │
├──────────────────────────────┼──────────┼──────────┼──────────┤
│ Website Visitors (monthly)   │ 10,000   │ 50,000   │ 200,000  │
│ Signup Conversion Rate       │ 3%       │ 5%       │ 8%       │
│ Free → Paid Conversion       │ 5%       │ 8%       │ 12%      │
│ Organic Traffic (% of total) │ 20%      │ 40%      │ 60%      │
│ Referral Traffic (affiliates)│ 5%       │ 15%      │ 25%      │
│ Blog Posts Published         │ 5        │ 30       │ 60       │
│ YouTube Subscribers          │ 100      │ 2,000    │ 10,000   │
│ Discord Members              │ 50       │ 500      │ 5,000    │
│ Twitter/X Followers          │ 500      │ 5,000    │ 25,000   │
│ Newsletter Subscribers       │ 200      │ 3,000    │ 15,000   │
│ Product Hunt Upvotes         │ —        │ 500+     │ —        │
│ GitHub Stars (SDK repos)     │ 10       │ 200      │ 1,000    │
└──────────────────────────────┴──────────┴──────────┴──────────┘
```

### 15.5 Enterprise Metrics

```
ENTERPRISE METRICS:
┌──────────────────────────────┬──────────┬──────────┬──────────┐
│ Metric                       │ Month 1  │ Month 3  │ Month 6  │
├──────────────────────────────┼──────────┼──────────┼──────────┤
│ Enterprise Accounts          │ 0        │ 5        │ 20       │
│ Enterprise MRR               │ $0       │ $2,000   │ $10,000  │
│ Average Contract Value       │ —        │ $400/mo  │ $500/mo  │
│ Enterprise Pipeline          │ $5,000   │ $50,000  │ $200,000 │
│ SSO Deployments              │ 0        │ 3        │ 15       │
│ White-Label Deployments      │ 0        │ 1        │ 5        │
│ Custom Training Projects     │ 0        │ 2        │ 10       │
│ Enterprise NPS               │ —        │ 50       │ 70       │
└──────────────────────────────┴──────────┴──────────┴──────────┘
```

---

## APPENDIX A: QUICK REFERENCE CARD

```
PROJECT PHOENIX AT A GLANCE:
══════════════════════════════════════════════════════════════════

  VERSION:      12.0
  CODENAME:     Project Phoenix
  DURATION:     20 weeks (April — August 2026)
  SPRINTS:      20 (1-week sprints)
  BUDGET:       ~$4,000/mo → ~$13,000/mo (scaling with revenue)
  REVENUE:      $100,000 MRR by August 2026

  PHASES:
  ┌───┬───────────────────────┬──────────┬──────────────────────┐
  │ # │ Phase                 │ Sprints  │ Key Deliverable      │
  ├───┼───────────────────────┼──────────┼──────────────────────┤
  │ 1 │ Real-Time Infra       │ 1-3      │ WebSocket + LiveKit  │
  │ 2 │ Developer Ecosystem   │ 4-7      │ API + SDKs           │
  │ 3 │ Enterprise Features   │ 8-11     │ SSO + RBAC + Audit   │
  │ 4 │ Advanced Voice        │ 12-15    │ Clone + Translate    │
  │ 5 │ AI Evolution          │ 16-18    │ Autonomous + Vision  │
  │ 6 │ Monetization          │ All      │ 6 tiers + metering   │
  │ 7 │ Growth & Marketing    │ 8-20     │ Affiliate + Content  │
  │ 8 │ Mobile & Native       │ 16-21    │ iOS + Android + Ext  │
  └───┴───────────────────────┴──────────┴──────────────────────┘

  NEW TABLES: 15 (30 total)
  NEW PROCESSES: 7 (11 total)
  NEW ENDPOINTS: 35+ REST API

  THE FORMULA:
  1,290 tools × voice-first × developer API × enterprise SSO
  × marketplace × mobile apps = $1.2M ARR

══════════════════════════════════════════════════════════════════
```

## APPENDIX B: GLOSSARY

```
TERM          DEFINITION
────────────  ──────────────────────────────────────────────────
ARR           Annual Recurring Revenue
CAC           Customer Acquisition Cost
DNCL          Do Not Call List (Canada, CRTC)
LTV           Lifetime Value
MAU           Monthly Active Users
MRR           Monthly Recurring Revenue
NPS           Net Promoter Score
NRR           Net Revenue Retention
PIPEDA        Personal Information Protection and Electronic
              Documents Act (Canada)
RBAC          Role-Based Access Control  
SAML          Security Assertion Markup Language
SLA           Service Level Agreement
SSO           Single Sign-On
STIR/SHAKEN   Secure Telephone Identity Revisited /
              Signature-based Handling of Asserted information
              using toKENs (caller ID verification)
WCAG          Web Content Accessibility Guidelines
XTTS          Cross-lingual Text-to-Speech
```

---

**END OF MASTER PLAN 3 — PROJECT PHOENIX**

```
══════════════════════════════════════════════════════════════════
  Master Plan 1: "Project Sentience"   → BUILD THE PRODUCT    ✅
  Master Plan 2: "Project Ignition"    → WIRE THE INFRA       ✅
  Master Plan 3: "Project Phoenix"     → MONETIZE & SCALE     🔥
══════════════════════════════════════════════════════════════════

  "We built it. We wired it. Now we set it on fire."
                                        — Project Phoenix, 2026
══════════════════════════════════════════════════════════════════
```

---

## EXECUTION STATUS — 10-Agent Run (Project Phoenix Sprint 1)

### Completed by Agent Fleet
| # | Agent | Task | Files Created | Status |
|---|-------|------|---------------|--------|
| 1 | Enterprise DB+RBAC | Schema upgrades + Enterprise API | api/enterprise.php, 2 new tables, 6 altered columns | ✅ COMPLETE |
| 2 | Developer Portal | Developer portal page | developer-portal.php (~1,100 lines) | ✅ COMPLETE |
| 3 | REST API v1 | Public API framework | api/v1/ (12 files, 27+ endpoints) | ✅ COMPLETE |
| 4 | Enterprise Admin | Admin dashboard | enterprise-admin.php (~1,500 lines) | ✅ COMPLETE |
| 5 | Usage + Affiliate | Usage tracking API + Affiliate page | api/usage.php, affiliate.php | ✅ COMPLETE |
| 6 | Extensions | Chrome ext + CLI + Extensions page | extensions/ (15+ files), extensions.php | ✅ COMPLETE |
| 7 | Voice Advanced | IVR Builder + Voice Cloning + Campaigns | 3 PHP pages | ✅ COMPLETE |
| 8 | SEO Content | 5 articles + 3 comparison pages | 8 content files | ✅ COMPLETE |
| 9 | Nav + Sitemap | Updated nav, footer, sitemap, 4 WHMCS announcements | Updated 4 files, 4 DB inserts | ✅ COMPLETE |
| 10 | WebSocket + Status | WS server + Status page + MP3 update | websocket/ server, status.php | ✅ COMPLETE |

### New Pages Added
- /developer-portal.php
- /enterprise-admin.php
- /affiliate.php
- /extensions.php
- /ivr-builder.php
- /voice-cloning.php
- /call-campaigns.php
- /status.php
- /compare/alfred-vs-chatgpt.php
- /compare/alfred-vs-intercom.php
- /compare/alfred-vs-copilot.php
- 5 new blog articles
- /api/v1/ (27+ REST endpoints)

### Infrastructure
- WebSocket server on port 3010 (PM2: alfred-ws)
- Public REST API at /api/v1/
- Enterprise API at /api/enterprise.php
- Usage tracking API at /api/usage.php
- 2 new DB tables, multiple schema upgrades
- Chrome extension source in /extensions/chrome/
- CLI tool source in /extensions/cli/

### Revenue Impact
- Developer Portal enables API monetization
- Usage tracking enables metered billing
- Enterprise admin enables org-level sales
- Affiliate program enables referral revenue
- Voice features enable premium voice revenue

### Next Sprint Priority (Post-Sprint 1)
1. Deploy and test WebSocket server in production
2. Set up Stripe metered billing products
3. Build OAuth2 authorization server
4. Deploy Chrome extension to Chrome Web Store
5. Publish CLI to npm
6. LiveKit server deployment
7. SDKs (npm, pip, composer packages)

---

## EXECUTION STATUS — 10-Agent Run (Project Phoenix Sprint 2)

### Sprint 2 Completed (March 4, 2026)

**Agent 1 — OAuth2 Authorization Server**
- Created: api/oauth.php (1,222 lines)
- OAuth2 authorization code flow with consent screen
- API key management (ak_live_ prefix)
- OAuth app registration (alf_ client_id)
- New table: alfred_oauth_codes
- 14 endpoints

**Agent 2 — Stripe Metered Billing**
- Enhanced: api/stripe.php (3→6 plan tiers)
- Updated: pricing.php (6-tier cards + annual toggle + comparison table)
- Added: OVERAGE_RATES constant (12 resource types)
- New endpoints: report-usage, usage-summary, switch-plan

**Agent 3 — Webhook Dispatch System**
- Created: api/webhooks.php (330 lines)
- Created: api/includes/webhook-dispatch.php (250 lines)
- Created: webhooks.php (580 lines, management UI)
- 6 webhook CRUD endpoints + dispatch engine

**Agent 4 — SDK Scaffolds & Docs**
- Created: sdks/node/ (15 files, TypeScript)
- Created: sdks/python/ (16 files)
- Created: sdks/php/ (18 files)
- Created: sdks.php landing page
- Enhanced: docs/api-reference.php (OpenAPI-style)
- 51 files total

**Agent 5 — Analytics & Notifications**
- Created: analytics.php (1,093 lines, Chart.js dashboards)
- Created: api/analytics.php (568 lines, 6 endpoints)
- Created: api/notifications.php (349 lines, 6 endpoints)
- Updated: site-header.inc.php (notification bell)
- New tables: alfred_notifications, alfred_notification_prefs

**Agent 6 — SEO Content**
- Created: 6 use-case pages (restaurants, ecommerce, dental, insurance, logistics, accounting)
- Created: 6 blog articles (AI phone answering, chatbot vs voice, support costs, voice setup, SMB tools, receptionist cost)
- 3,095 lines across 12 files

**Agent 7 — Marketplace Creator**
- Created: marketplace-creator.php (creator dashboard)
- Created: api/marketplace-creator.php (creator API)
- Enhanced: marketplace.php (filters, reviews, creator CTA)
- New tables: alfred_marketplace_purchases, alfred_marketplace_reviews, alfred_marketplace_payouts

**Agent 8 — French Translations**
- Enhanced: includes/lang.php (185 new translation keys per language, 370 total)
- Covers all Sprint 1 + Sprint 2 pages

**Agent 9 — Navigation & Content Updates**
- Updated: site-header.inc.php, site-footer.inc.php
- Updated: sitemap.xml (216 total URLs, 15+ new entries)
- Created: 4 WHMCS announcements (IDs 49-52)

**Agent 10 — Final Deliverables**
- Created: integrations.php (50+ integrations showcase, ~560 lines)
- Created: white-label.php (enterprise white-label config, ~580 lines)
- Created: api/white-label.php (white-label API, ~230 lines)
- Minified: site.min.css (53,453 → 39,854 bytes, 25.4% reduction)
- Updated: Master Plan 3 progress tracking
- New table: alfred_white_label

### Sprint 2 Summary
| Metric | Count |
|--------|-------|
| New PHP pages | 12+ |
| New API endpoints | 40+ |
| New database tables | 6 |
| New SDK files | 51 |
| New content pages | 12 |
| Translation keys added | 370 |
| WHMCS announcements | 4 |
| Sitemap URLs | 216 |
| CSS reduction | 25.4% |

### New Pages Added (Sprint 2)
- /integrations.php
- /white-label.php
- /analytics.php
- /webhooks.php
- /sdks.php
- /marketplace-creator.php
- /api/oauth.php
- /api/analytics.php
- /api/notifications.php
- /api/webhooks.php
- /api/white-label.php
- /api/includes/webhook-dispatch.php
- /sdks/node/ (15 files)
- /sdks/python/ (16 files)
- /sdks/php/ (18 files)
- 6 use-case pages
- 6 blog articles

### New Database Tables (Sprint 2)
- alfred_oauth_codes
- alfred_notifications
- alfred_notification_prefs
- alfred_marketplace_purchases
- alfred_marketplace_reviews
- alfred_marketplace_payouts
- alfred_white_label

### Revenue Impact (Sprint 2)
- OAuth2 server enables secure third-party integrations → platform fees
- Metered billing (6 tiers) enables usage-based revenue scaling
- Marketplace Creator Program → 30% commission on creator sales
- White-label config → Enterprise+ upsell ($99/mo+)
- Integrations page → conversion funnel for developer adoption
- SDKs → reduced friction for API adoption

### Next Sprint Priority (Sprint 3)
1. Deploy OAuth2 server to production, test flows end-to-end
2. Launch Marketplace Creator beta with first 10 creators
3. Configure Stripe products for 6-tier metered billing
4. Deploy WebSocket server (PM2: alfred-ws on port 3010)
5. LiveKit server deployment for conference rooms
6. Chrome extension → Chrome Web Store submission
7. CLI tool → npm publish (@gositeme/alfred-cli)
8. Mobile app scaffolding (React Native)
9. A/B testing on pricing page (6-tier layout)
10. SEO monitoring — track rankings for 12 new content pages

---

## EXECUTION STATUS — 10-Agent Run (Project Phoenix Sprint 3)

### Sprint 3 Completed (2026-03-04)

**Auth Hardening (Agent 1)**
- Password reset flow with email (api/auth.php)
- Login rate limiting: 10/IP + 5/email per 15min
- CSRF on login/register forms
- Session timeout: 24h absolute + 2h idle
- New tables: alfred_password_resets, alfred_login_attempts

**Onboarding Wizard (Agent 2)**
- onboarding.php: 5-step new user wizard
- Auto-redirect from dashboard for new users
- api/onboarding.php with progress tracking
- New table: alfred_onboarding

**Agent Templates (Agent 3)**
- agent-templates.php: 30 templates across 6 categories
- api/agent-templates.php: deploy, list, popular endpoints
- One-click agent deployment from templates
- New table: alfred_template_deployments

**Conversation History (Agent 4)**
- conversations.php: two-panel chat history viewer
- api/conversations.php: list, get, rename, delete, export, stats
- Markdown rendering, code highlighting, keyboard nav

**Enhanced Dashboard (Agent 5)**
- dashboard.php: +488 lines
- Welcome banner, stats row, quick actions, activity feed
- Usage meters with plan limits and upgrade nudges

**Help Center (Agent 6)**
- help.php: 30 articles across 6 categories
- api/help.php: search, feedback, popular
- Client-side search, scrollspy, accordion articles

**Team Collaboration (Agent 7)**
- team.php: org workspace with 6 tabs
- api/team.php: sharing, invites, activity
- New tables: alfred_shared_agents, alfred_shared_conversations, alfred_invite_codes, alfred_team_activity

**SEO Content (Agent 8)**
- 6 use-case pages: nonprofits, travel, fitness, manufacturing, government, media
- 3 comparison pages: vs-zendesk, vs-dialogflow, vs-twilio
- 4 blog articles

**Security & Performance (Agent 9)**
- security.php: security practices showcase
- Error handler, CORS headers, input validator, API health check
- .htaccess compression + performance headers

**Navigation & Updates (Agent 10)**
- Header/footer updated with Sprint 3 links
- Sitemap: ~232 entries (16 new)
- 4 WHMCS announcements (IDs 53-56)
- Master Plan 3 progress updated

### Cumulative Totals After Sprint 3
- Root PHP pages: ~42+
- API PHP files: ~44+
- DB tables: ~47+
- Use-case pages: 21
- Comparison pages: 6
- Blog articles: 27+
- Help articles: 30
- Agent templates: 30
- WHMCS announcements: 56
