# ALFRED ANALYTICS, MONITORING & OBSERVABILITY RESEARCH
### Comprehensive Tool Evaluation for a PHP + Node.js + Python AI Platform
### March 2026

---

## CURRENT STATE

| Component | Status |
|-----------|--------|
| MySQL (14+ tables) | ✅ Live |
| Redis (cache + pub/sub) | ✅ Live |
| PM2 (process management) | ✅ Live |
| Basic analytics (XP, streaks, tool usage) | ✅ Live |
| Structured logging | ❌ None |
| APM / Distributed tracing | ❌ None |
| Error tracking | ❌ None |
| Uptime monitoring | ❌ None |
| Business intelligence dashboards | ❌ None |
| Feature flags | ❌ None |

---

## TABLE OF CONTENTS

1. [Observability Stack](#1-observability-stack)
2. [Log Management](#2-log-management)
3. [Error Tracking](#3-error-tracking)
4. [Uptime Monitoring](#4-uptime-monitoring)
5. [Analytics Platforms](#5-analytics-platforms)
6. [Business Intelligence](#6-business-intelligence)
7. [Time-Series Databases](#7-time-series-databases)
8. [Data Pipelines](#8-data-pipelines)
9. [Search](#9-search)
10. [Feature Flags & A/B Testing](#10-feature-flags--ab-testing)
11. [Recommended Stack for Alfred](#recommended-stack-for-alfred)

---

## 1. OBSERVABILITY STACK

> Goal: Metrics, traces, and logs from PHP, Node.js, Python, and PM2 processes in a unified dashboard.

### Full Comparison

| Tool | Type | Self-Hosted | Cost | PHP | Node.js | Python | Integration Effort | Priority |
|------|------|-------------|------|-----|---------|--------|--------------------|----------|
| **Grafana** | Visualization/Dashboards | ✅ Yes | Free (OSS), Cloud free tier 10k metrics | ✅ via datasources | ✅ | ✅ | Low — just connects to backends | 🔴 P0 |
| **Prometheus** | Metrics collection | ✅ Yes | Free (OSS) | ✅ via exporters | ✅ client lib | ✅ client lib | Medium — needs exporters per service | 🔴 P0 |
| **Grafana Loki** | Log aggregation | ✅ Yes | Free (OSS) | ✅ via Alloy/Promtail | ✅ | ✅ | Medium — log shipping agent | 🟡 P1 |
| **Grafana Tempo** | Distributed tracing | ✅ Yes | Free (OSS) | ⚠️ via OTel | ✅ OTel SDK | ✅ OTel SDK | Medium — instrument code with OTel | 🟡 P1 |
| **OpenTelemetry** | Instrumentation standard | ✅ Yes (Collector) | Free (CNCF) | ✅ SDK | ✅ SDK | ✅ SDK | Medium — add SDK to each service | 🔴 P0 |
| **Grafana Alloy** | OTel Collector + log agent | ✅ Yes | Free (OSS) | N/A (agent) | N/A | N/A | Low — replaces Promtail + OTel Collector | 🟡 P1 |
| **Jaeger** | Distributed tracing | ✅ Yes | Free (CNCF) | ⚠️ via OTel | ✅ | ✅ | Medium | 🟢 P2 (Tempo preferred) |
| **Zipkin** | Distributed tracing | ✅ Yes | Free (OSS) | ⚠️ Limited | ✅ | ✅ | Medium | 🟢 P2 (legacy) |
| **Datadog** | Full-stack observability | ❌ SaaS only | $15/host/mo + add-ons | ✅ | ✅ | ✅ | Low — agent-based | ⛔ Too expensive |
| **New Relic** | Full-stack APM | ❌ SaaS only | Free 100GB/mo, then $0.35/GB | ✅ | ✅ | ✅ | Low — agent-based | 🟢 P2 (free tier decent) |
| **Elastic APM** | APM within ELK | ✅ Yes | Free (basic), $$$ for features | ✅ | ✅ | ✅ | Medium — needs Elasticsearch | 🟢 P2 (heavy) |

### Verdict for Alfred

**Best free stack: Grafana + Prometheus + Loki + Tempo + OpenTelemetry**

This is known as the **LGTM stack** (Loki, Grafana, Tempo, Mimir/Prometheus) and is the industry standard for self-hosted observability. All components are open source (AGPLv3 for Loki, Apache 2.0 for others).

```
┌─────────────┐    ┌──────────────┐    ┌──────────────┐
│  PHP App    │    │  Node.js MCP │    │  Python AI   │
│  (OTel SDK) │    │  (OTel SDK)  │    │  (OTel SDK)  │
└──────┬──────┘    └──────┬───────┘    └──────┬───────┘
       │                  │                   │
       └──────────┬───────┴───────────────────┘
                  │
           ┌──────▼──────┐
           │ Grafana     │
           │ Alloy       │  ← Collects metrics, logs, traces
           │ (OTel       │
           │  Collector) │
           └──┬───┬───┬──┘
              │   │   │
     ┌────────┘   │   └────────┐
     ▼            ▼            ▼
┌─────────┐ ┌─────────┐ ┌─────────┐
│Prometheus│ │  Loki   │ │  Tempo  │
│(Metrics) │ │ (Logs)  │ │(Traces) │
└────┬─────┘ └────┬────┘ └────┬────┘
     └────────┬───┴────────────┘
              ▼
        ┌──────────┐
        │ GRAFANA  │  ← Single dashboard for everything
        │ (UI)     │
        └──────────┘
```

**RAM requirement**: ~2-4 GB total for all components at Alfred's scale.

---

## 2. LOG MANAGEMENT

> Goal: Centralized, searchable logs from PHP, Node.js, Python, PM2, nginx, MySQL.

### Full Comparison

| Tool | Self-Hosted | Cost | Search | Query Language | RAM Needs | PHP/Node/Python | Priority |
|------|-------------|------|--------|----------------|-----------|-----------------|----------|
| **Grafana Loki** | ✅ Yes | Free (AGPLv3) | Label-based, LogQL | LogQL (like PromQL) | **Low** (~512MB) | ✅ All via Alloy | 🔴 P0 |
| **ELK Stack** (Elasticsearch + Logstash + Kibana) | ✅ Yes | Free (basic), SSPL | **Full-text** (best) | KQL, Lucene | **Very High** (4-8GB+ min) | ✅ All | 🟢 P2 (too heavy) |
| **Graylog** | ✅ Yes | Free (SSPL) | Full-text via OpenSearch | Custom query lang | **High** (2-4GB) | ✅ All | 🟡 P1 (good alt) |
| **Fluentd** | ✅ Yes | Free (Apache 2.0) | N/A (forwarder only) | N/A | Low | ✅ All | 🟢 P2 (use Alloy instead) |
| **Vector** | ✅ Yes | Free (MPL 2.0) | N/A (pipeline only) | VRL (transform language) | **Very Low** (Rust-based) | ✅ All | 🟡 P1 (great pipeline) |

### Detailed Analysis

**Loki** — The clear winner for Alfred. Unlike Elasticsearch, Loki does NOT index log content — it indexes only labels (like service name, log level). This means:
- 10-100x less storage than ELK
- Runs on 512MB RAM vs 4GB+ for Elasticsearch
- Integrates natively with Grafana (same dashboard as metrics)
- LogQL query language is intuitive: `{service="alfred-mcp"} |= "error" | json`

**ELK** — The gold standard for full-text log search, but massively over-engineered for Alfred's current scale. Elasticsearch alone needs 2-4GB RAM minimum. Only consider if you need full-text search across millions of log lines.

**Vector** — Excellent as a log *pipeline* tool. Written in Rust, uses ~10MB RAM. Can sit between your apps and Loki to transform, filter, and route logs. Good complement to Loki.

### Verdict for Alfred

**Loki + Grafana** with Alloy as the log shipper. If you later need log transformations (redacting PII, parsing JSON), add **Vector** as a pipeline between apps and Loki.

---

## 3. ERROR TRACKING

> Goal: Catch, group, and alert on errors across PHP, Node.js, and Python services.

### Full Comparison

| Tool | Self-Hosted | Cost (SaaS) | PHP | Node.js | Python | Key Differentiator | Priority |
|------|-------------|-------------|-----|---------|--------|--------------------|----------|
| **Sentry** | ✅ (complex) | Free: 5k errors/mo, Team: $26/mo (50k errors) | ✅ | ✅ | ✅ | Best-in-class, AI debugging (Seer), session replay, performance monitoring | 🔴 P0 |
| **GlitchTip** | ✅ Easy | Free self-host; Hosted: $0 (1k events), $15/mo (100k) | ✅ (uses Sentry SDKs) | ✅ | ✅ | Self-hosted Sentry alternative, uses same SDKs, much simpler to deploy | 🔴 P0 (alt) |
| **Bugsnag** | ❌ SaaS only | Free: 7.5k events/mo, $59/mo (25k events) | ✅ | ✅ | ✅ | Good stability scores, release tracking | 🟢 P2 |
| **Rollbar** | ❌ SaaS only | Free: 5k events/mo, $13/mo (25k events) | ✅ | ✅ | ✅ | Good grouping, deploy tracking | 🟢 P2 |

### Detailed Analysis

**Sentry** is the undisputed leader. In 2026 it includes:
- **Error monitoring**: Auto-groups errors, shows stack traces, source maps
- **Tracing**: Distributed tracing across PHP → Node.js → Python
- **Session Replay**: See exactly what users did before the error
- **Uptime + Cron monitoring**: Built-in (though limited on free tier)
- **Seer AI debugger**: Root cause analysis and fix suggestions
- **MCP monitoring**: Monitors MCP tool calls (directly relevant to Alfred's 807 MCP tools)
- SDKs for PHP, Node.js, Python, JavaScript (frontend)

Free tier: 1 user, 5k errors/mo, 5M spans, 5GB logs — very generous for early stage.

**GlitchTip** is the budget self-hosted alternative. It's compatible with Sentry's client SDKs, meaning you instrument your code with `sentry-php`, `@sentry/node`, `sentry-sdk` (Python), and point them at your GlitchTip server. Simpler than self-hosting real Sentry (which requires Kafka, Redis, PostgreSQL, ClickHouse, etc.).

### Verdict for Alfred

**Sentry SaaS (Developer tier)** — Start free. The MCP monitoring feature alone is worth it for Alfred. When you exceed 5k errors/mo, either upgrade to Team ($26/mo) or deploy **GlitchTip** self-hosted as a fallback.

---

## 4. UPTIME MONITORING

> Goal: Monitor Alfred's web UI, API endpoints, MCP server (port 3005), WebSocket (port 3010), voice systems.

### Full Comparison

| Tool | Self-Hosted | Cost | Check Interval | Monitors (Free) | Status Page | Notifications | Priority |
|------|-------------|------|----------------|-----------------|-------------|---------------|----------|
| **Uptime Kuma** | ✅ Yes (Node.js) | Free (MIT) | 20 seconds | **Unlimited** | ✅ Multiple | 90+ services (Telegram, Discord, Slack, email, webhooks) | 🔴 P0 |
| **UptimeRobot** | ❌ SaaS | Free: $0, Solo: $7/mo | 5 min (free), 60s (paid) | 50 (free) | ✅ Basic | Email, SMS, Slack, webhooks | 🟡 P1 |
| **Better Stack** | ❌ SaaS | Free tier, ~$29/mo (team) | 30 seconds | 10 (free) | ✅ Beautiful | Voice calls, SMS, email, Slack | 🟡 P1 |
| **Pingdom** | ❌ SaaS | From $15/mo | 1 minute | 0 free | ✅ | Multi-channel | 🟢 P2 |
| **StatusCake** | ❌ SaaS | Free: 10 monitors | 5 min (free), 30s (paid) | 10 (free) | ✅ | Multi-channel | 🟢 P2 |

### Detailed Analysis

**Uptime Kuma** is the standout choice. 83.7k GitHub stars, MIT licensed, runs on Node.js (which Alfred already uses). Features:
- HTTP(s), TCP, Ping, DNS, WebSocket, Docker container, Steam, Push monitoring
- 20-second check intervals (better than most paid SaaS tools)
- 90+ notification integrations (Telegram, Discord, Slack, email, webhooks, etc.)
- Beautiful reactive UI
- Multiple status pages with custom domains
- SSL certificate expiry monitoring
- Runs on PM2 (which Alfred already uses!)
- Can be deployed in Docker or directly via `npm`

**Alfred-specific monitors to configure**:
1. `https://gositeme.com` — Main website
2. `:3005` — MCP Server
3. `:3010` — WebSocket Server
4. Voice/VAPI endpoints
5. API endpoints (`/api/alfred-chat.php`, `/api/fleet.php`, etc.)
6. MySQL connectivity
7. Redis connectivity
8. SSL certificate expiry

### Verdict for Alfred

**Uptime Kuma** (self-hosted). Deploy alongside existing PM2 processes. Supplement with **UptimeRobot free tier** (50 external monitors) for redundant external monitoring — you need an external monitor to alert you if your entire server goes down (Uptime Kuma can't alert you if it's down too).

---

## 5. ANALYTICS PLATFORMS

> Goal: Privacy-first web analytics for gositeme.com, developer portal, marketplace.

### Full Comparison

| Tool | Self-Hosted | Cost (SaaS) | Privacy | Script Size | Key Features | Priority |
|------|-------------|-------------|---------|-------------|--------------|----------|
| **Plausible** | ✅ Yes | $9/mo (10k pageviews) | 🏆 No cookies, GDPR-compliant, EU-hosted | **<1KB** | Simple dashboard, goals, funnels, UTM tracking, AI traffic tracking | 🟡 P1 |
| **Umami** | ✅ Yes | Free (100k events/mo), $20/mo (1M) | 🏆 No cookies, GDPR-compliant | **<2KB** | Real-time, custom events, funnels, retention, revenue tracking | 🔴 P0 |
| **PostHog** | ✅ Yes | Free (1M events/mo), $0/mo generous free tier | Good (some cookies) | ~20KB | Product analytics, session replay, feature flags, A/B testing, surveys — **all-in-one** | 🔴 P0 |
| **Matomo** | ✅ Yes | Free self-hosted, Cloud from $23/mo | 🏆 Full GDPR | ~22KB | GA4 replacement, heatmaps, session recording (paid), full SQL access | 🟡 P1 |
| **Amplitude** | ❌ SaaS | Free (50k MTU), from $61/mo | Moderate | SDK-based | Product analytics, cohort analysis, experimentation | 🟢 P2 |
| **Mixpanel** | ❌ SaaS | Free (20M events/mo — very generous) | Moderate | SDK-based | Event-based analytics, user flows, funnels, retention | 🟢 P2 |

### Detailed Analysis

**PostHog** is the most compelling option for Alfred. It's essentially 6 tools in one:
1. **Product analytics** — Events, funnels, retention, user paths
2. **Session replay** — Watch users interact with Alfred
3. **Feature flags** — Roll out features gradually (eliminates need for separate FF tool)
4. **A/B testing** — Test different UI/UX changes
5. **Surveys** — Collect user feedback in-app
6. **Data warehouse** — Query your data with SQL

Self-hosted: completely free, runs on ClickHouse + PostgreSQL.
Cloud free tier: 1M events/mo, 5k session replays, unlimited feature flags — extremely generous.

**Umami** is the best pure analytics tool. Self-hosted, runs on Node.js + PostgreSQL/MySQL (Alfred already has MySQL). Tiny script, beautiful UI, completely free to self-host.

### Verdict for Alfred

**PostHog** (cloud free tier or self-hosted) for product analytics, session replay, and possibly feature flags. Add **Umami** (self-hosted on existing MySQL) for lightweight public-facing web analytics on marketing pages.

---

## 6. BUSINESS INTELLIGENCE

> Goal: Visualize revenue (Stripe), user growth, tool usage, fleet metrics, marketplace activity.

### Full Comparison

| Tool | Self-Hosted | Cost | MySQL Support | Key Features | Ease of Use | Priority |
|------|-------------|------|---------------|--------------|-------------|----------|
| **Metabase** | ✅ Yes | Free (OSS), Starter: $100/mo | ✅ Native | No-code query builder, drag-and-drop dashboards, scheduled reports, embedding, Metabot AI | 🏆 Easiest | 🔴 P0 |
| **Apache Superset** | ✅ Yes | Free (Apache 2.0) | ✅ Native | 40+ viz types, SQL IDE, semantic layer, extensible, petabyte-scale | Moderate | 🟡 P1 |
| **Redash** | ✅ Yes | Free (BSD) | ✅ Native (35+ data sources) | SQL-first, simple sharing, alerts, scheduled refreshes, REST API | 🏆 Easy | 🟡 P1 |
| **Grafana Dashboards** | ✅ Yes | Free (via Grafana OSS) | ✅ via plugin | Great for time-series, alerting, more ops-focused | Moderate | Already recommended |

### Detailed Analysis

**Metabase** is the best BI tool for Alfred because:
- **Zero SQL required**: Business users can build queries with a visual query builder
- **MySQL native support**: Connects directly to Alfred's existing database
- **Self-hosted is free**: Single JAR file or Docker container
- **Embeddable**: Can embed dashboards directly into Alfred's dashboard.php
- **Scheduled reports**: Auto-email weekly revenue reports
- **46.4k GitHub stars**, trusted by 90,000+ companies

Example dashboards for Alfred:
- Revenue by plan tier (Free/Starter/Pro/Enterprise)
- User growth over time
- Most-used tools (from MCP usage logs)
- Fleet creation and completion rates
- Marketplace listing activity
- XP and achievement tracking
- Voice call volume and duration

**Superset** is more powerful but more complex to set up (requires Redis, metadata DB, Celery workers). Better suited for data teams.

**Redash** is excellent for SQL-savvy teams. Simpler than Superset, more SQL-focused than Metabase. Supports 35+ data sources including MySQL, PostgreSQL, Prometheus, and JSON APIs.

### Verdict for Alfred

**Metabase OSS** (self-hosted). Connect it directly to Alfred's MySQL and have dashboards running in 30 minutes. If you already adopt Grafana for ops monitoring, use **Grafana dashboards** for operational metrics and Metabase for business metrics.

---

## 7. TIME-SERIES DATABASES

> Goal: Store sensor data from robots (ROS 2), IoT telemetry, and high-frequency metrics.

### Full Comparison

| Tool | Self-Hosted | Cost | Query Language | Compression | RAM Usage | Grafana Integration | Priority |
|------|-------------|------|----------------|-------------|-----------|---------------------|----------|
| **VictoriaMetrics** | ✅ Yes | Free (Apache 2.0) | PromQL + MetricsQL | 🏆 Best (70x better than TimescaleDB) | 🏆 Lowest (10x less than InfluxDB) | ✅ Native | 🔴 P0 |
| **Prometheus** | ✅ Yes | Free (Apache 2.0) | PromQL | Good | Moderate | ✅ Native | 🔴 P0 (already recommended) |
| **InfluxDB** | ✅ Yes | Free (OSS v2), Cloud from $0 | Flux / InfluxQL | Good | High | ✅ Native | 🟡 P1 |
| **TimescaleDB** | ✅ Yes | Free (Apache 2.0 for community) | **SQL** (PostgreSQL extension) | Good | Moderate-High | ✅ Plugin | 🟡 P1 |
| **QuestDB** | ✅ Yes | Free (Apache 2.0) | SQL + InfluxDB line protocol | Very Good | Low | ✅ Plugin | 🟢 P2 |

### Detailed Analysis

For **robot/IoT sensor data** (temperature, motor RPM, battery levels, joint angles from ROS 2):

**VictoriaMetrics** is the standout:
- **10x less RAM** than InfluxDB for the same dataset
- **70x more data points** stored per GB than TimescaleDB
- **20x faster** ingestion than InfluxDB and TimescaleDB
- Drop-in Prometheus replacement (accepts Prometheus remote write)
- Supports InfluxDB line protocol, OpenTelemetry, Graphite, DataDog protocols
- Can run as a single binary with zero dependencies
- Used by Grammarly, Roblox, Wix, Spotify at massive scale

**TimescaleDB** is interesting if you want to use SQL for IoT queries (it's a PostgreSQL extension), but it's heavier.

### Verdict for Alfred

Use **Prometheus** for application metrics now (it's part of the LGTM stack). When robot embodiment goes live and you need to store millions of sensor readings per second, add **VictoriaMetrics** as a long-term storage backend for Prometheus (it accepts Prometheus remote write natively).

---

## 8. DATA PIPELINES

> Goal: ETL for analytics, real-time data processing, message queuing between services.

### Full Comparison

| Tool | Type | Self-Hosted | Cost | Use Case | Integration Effort | Priority |
|------|------|-------------|------|----------|--------------------|---------| 
| **Redis (existing)** | Pub/Sub + Queue | ✅ Already deployed | Free | Simple message passing, cache invalidation | Already in use | 🔴 P0 (already have it) |
| **Apache Kafka** | Event streaming | ✅ Yes | Free (Apache 2.0) | High-throughput event streaming, event sourcing | High (JVM, ZooKeeper/KRaft) | 🟢 P2 (overkill for now) |
| **RabbitMQ** | Message broker | ✅ Yes | Free (MPL 2.0) | Task queues, RPC, routing patterns | Medium | 🟡 P1 |
| **Apache Airflow** | Workflow orchestration | ✅ Yes | Free (Apache 2.0) | Scheduled ETL, data pipelines, batch processing | Medium-High | 🟡 P1 |
| **Dagster** | Data orchestration | ✅ Yes | Free (Apache 2.0) | Modern Airflow alternative, better testing | Medium | 🟢 P2 |
| **Prefect** | Workflow orchestration | ✅ Yes | Free (Apache 2.0), Cloud free tier | Easy Python-native workflows | Low-Medium | 🟡 P1 |
| **dbt** | SQL transformations | ✅ Yes | Free (Apache 2.0) | Transform data in warehouse, analytics engineering | Low (SQL-based) | 🟢 P2 |
| **BullMQ** | Node.js job queue | ✅ Yes | Free (MIT) | Background jobs in Node.js, backed by Redis | **Very Low** (uses existing Redis) | 🔴 P0 |

### Detailed Analysis

Alfred's immediate needs are:
1. **Background job processing** — Processing voice transcriptions, AI completions, fleet agent tasks
2. **Event-driven communication** — Between PHP, Node.js (MCP), and Python services
3. **Scheduled tasks** — Daily analytics aggregation, cleanup, report generation

**BullMQ** is the highest-impact, lowest-effort addition. It's a Node.js job queue that uses Redis (which Alfred already has). Use it for:
- Queueing AI API calls with retries and rate limiting
- Processing fleet agent tasks asynchronously
- Voice transcription processing
- Scheduled marketplace analytics

**RabbitMQ** makes sense if you need pub/sub patterns beyond what Redis offers (guaranteed delivery, complex routing, multiple consumer groups).

**Airflow** and **Prefect** are for when you need scheduled data pipelines (e.g., daily aggregation of usage stats into analytics tables, syncing Stripe data with your DB).

### Verdict for Alfred

- **Now**: **BullMQ** (zero new infrastructure — uses existing Redis)
- **Soon**: **Prefect** for scheduled ETL pipelines (easier than Airflow)
- **Later**: **RabbitMQ** if you need guaranteed message delivery between services
- **Much Later**: **Kafka** only if you need to process 100k+ events/second

---

## 9. SEARCH

> Goal: Search across 1,290+ tools, marketplace listings, documentation, conversations.

### Full Comparison

| Tool | Self-Hosted | Cost (SaaS) | Typo Tolerance | Vector/Semantic Search | PHP SDK | Node.js SDK | Python SDK | RAM Usage | Priority |
|------|-------------|-------------|----------------|------------------------|---------|-------------|------------|-----------|----------|
| **Meilisearch** | ✅ Yes | Free OSS, Cloud from $30/mo | ✅ | ✅ (AI-powered) | ✅ | ✅ | ✅ | ~100MB | 🔴 P0 |
| **Typesense** | ✅ Yes | Cloud starts ~$30/mo | ✅ | ✅ built-in | ✅ | ✅ | ✅ | ~100MB (in-memory) | 🔴 P0 (alt) |
| **Elasticsearch** | ✅ Yes | Free (SSPL basic) | ✅ | ✅ (with plugins) | ✅ | ✅ | ✅ | **2-4GB+** | 🟢 P2 (too heavy) |
| **Algolia** | ❌ SaaS only | Free: 10k searches/mo, from $1/1k requests | ✅ | ✅ via NeuralSearch | ✅ | ✅ | ✅ | N/A | 🟢 P2 (expensive at scale) |

### Detailed Analysis

**Meilisearch** and **Typesense** are both excellent modern alternatives to Elasticsearch:

**Meilisearch**:
- Written in Rust, single binary, ~100MB RAM
- Sub-50ms search results
- Built-in typo tolerance, faceting, filtering, geo search
- AI-powered search (vector + keyword hybrid)
- Crawls websites for documentation search
- RESTful API, SDKs for PHP, Node.js, Python, and more
- 50k GitHub stars
- Instant "search as you type" experience

**Typesense**:
- Written in C++, single binary, runs in-memory for max speed
- Sub-5ms search results (even faster than Meilisearch)
- Vector search, semantic search, built-in RAG
- Scoped API keys for multi-tenant search
- JOINs across collections
- Used by Codecademy, Logitech, ElevenLabs, Kick.com
- 24k GitHub stars
- PHP, Node.js, Python, Go SDKs (with Laravel Scout driver)

**Alfred-specific search use cases**:
1. **Tool search**: 1,290+ tools with categories, descriptions → instant fuzzy search
2. **Marketplace search**: Listings, templates, workflows
3. **Documentation search**: Developer portal docs
4. **Conversation search**: Historical conversation search
5. **Voice command disambiguation**: When Alfred hears "create a flea" → match to "create a fleet"

### Verdict for Alfred

**Meilisearch** (self-hosted) — Slightly easier setup, built-in AI search, excellent faceting for marketplace filtering. Or **Typesense** if you need the absolute fastest search speed and JOINs. Both are excellent; Meilisearch has more momentum (50k vs 24k stars).

---

## 10. FEATURE FLAGS & A/B TESTING

> Goal: Safely roll out new features, test different AI models, experiment with pricing.

### Full Comparison

| Tool | Self-Hosted | Cost (SaaS) | PHP SDK | Node.js SDK | Python SDK | Key Features | Priority |
|------|-------------|-------------|---------|-------------|------------|--------------|----------|
| **GrowthBook** | ✅ Yes | Free (3 users), $40/user/mo (Pro) | ✅ | ✅ | ✅ | A/B testing with stats engine, warehouse-native, feature flags, product analytics (beta) | 🔴 P0 |
| **Unleash** | ✅ Yes | Free (OSS), $75/seat/mo (Enterprise) | ✅ | ✅ | ✅ | Enterprise feature management, 25+ SDKs, lifecycle management, change requests | 🟡 P1 |
| **PostHog** | ✅ Yes | Free (1M events/mo) | ✅ | ✅ | ✅ | Feature flags + analytics + session replay + A/B testing (all-in-one) | 🔴 P0 (if using PostHog for analytics) |
| **Flagsmith** | ✅ Yes | Free (50k requests/mo), $45/mo (startup) | ✅ | ✅ | ✅ | Edge API, multi-environment, segments, A/B + MVT testing | 🟡 P1 |
| **LaunchDarkly** | ❌ SaaS only | From $10/seat/mo | ✅ | ✅ | ✅ | Industry leader, enterprise governance, but $$$$ at scale | 🟢 P2 (too expensive) |

### Detailed Analysis

**GrowthBook** is the best self-hosted option:
- **Warehouse-native**: Connects to your existing MySQL/analytics data for experiment analysis
- **Statistical engine**: Proper Bayesian or Frequentist statistics for A/B tests
- **CUPED variance reduction**: Get significant results faster
- **Multi-arm bandits**: Auto-optimize allocations
- Free self-hosted version includes unlimited feature flags and experiments
- SDKs for PHP, Node.js, Python, and frontend JavaScript

**Alfred-specific A/B test use cases**:
1. Test Claude vs GPT-4.1 vs Groq for different query types
2. Test different pricing page layouts
3. Test new tool UI designs
4. Gradually roll out new fleet strategies
5. Test voice cloning models
6. A/B test onboarding flows

**PostHog** already includes feature flags and A/B testing, so if you use PostHog for analytics (recommended in Section 5), you get feature flags for free.

### Verdict for Alfred

If you adopt **PostHog** for analytics → use its **built-in feature flags** (simplest, no extra tool).
If you want a dedicated best-in-class experimentation platform → **GrowthBook** (self-hosted, free, warehouse-native).

---

## RECOMMENDED STACK FOR ALFRED

### Tier 1 — Deploy Now (Week 1-2)

| Category | Tool | Cost | Why |
|----------|------|------|-----|
| **Uptime Monitoring** | Uptime Kuma | Free | Runs on PM2, monitors all endpoints, 90+ alert channels |
| **Error Tracking** | Sentry (Developer tier) | Free | Best error tracking, MCP monitoring, PHP+Node+Python SDKs |
| **Background Jobs** | BullMQ | Free | Uses existing Redis, immediate value for async processing |
| **External Uptime** | UptimeRobot (free tier) | Free | 50 external monitors as backup to Uptime Kuma |

**Total cost: $0/month. Setup time: 1-2 days.**

### Tier 2 — Deploy Soon (Week 3-6)

| Category | Tool | Cost | Why |
|----------|------|------|-----|
| **Metrics** | Prometheus + Grafana | Free | Industry standard, visualize everything |
| **Logs** | Loki + Grafana Alloy | Free | Lightweight log aggregation, same Grafana dashboard |
| **Analytics** | PostHog (cloud free tier) | Free | Product analytics + feature flags + session replay |
| **Web Analytics** | Umami (self-hosted) | Free | Privacy-first, runs on existing MySQL |
| **BI Dashboards** | Metabase OSS | Free | Business intelligence from MySQL, embeddable |

**Total cost: $0/month. Setup time: 1-2 weeks.**

### Tier 3 — Deploy When Needed (Month 2-3)

| Category | Tool | Cost | Why |
|----------|------|------|-----|
| **Tracing** | Tempo + OpenTelemetry | Free | Distributed tracing across PHP↔Node↔Python |
| **Search** | Meilisearch (self-hosted) | Free | Tool search, marketplace search, docs search |
| **ETL/Pipelines** | Prefect | Free | Scheduled data aggregation and analytics pipelines |
| **A/B Testing** | GrowthBook or PostHog | Free | Proper experiment statistics |

**Total cost: $0/month.**

### Tier 4 — Deploy for Scale (Month 4+)

| Category | Tool | Cost | Why |
|----------|------|------|-----|
| **TSDB for IoT** | VictoriaMetrics | Free | Robot sensor data, 10x less RAM than InfluxDB |
| **Message Queue** | RabbitMQ | Free | Guaranteed delivery between services |
| **Log Pipeline** | Vector | Free | Transform/filter/route logs before Loki |

---

## TOTAL STACK COST

| Scenario | Monthly Cost |
|----------|-------------|
| **Everything self-hosted** | **$0** |
| **With Sentry Team (50k errors)** | $26/mo |
| **With PostHog Cloud** | $0 (under 1M events) |
| **Enterprise equivalent** (Datadog + PagerDuty + LaunchDarkly + Amplitude) | $500-2000+/mo |

### RAM Budget for Full Stack

| Component | RAM |
|-----------|-----|
| Prometheus | 512MB-1GB |
| Grafana | 256MB |
| Loki | 512MB |
| Tempo | 256MB |
| Uptime Kuma | 128MB |
| Metabase | 512MB-1GB |
| Meilisearch | 128MB-512MB |
| Umami | 128MB |
| **Total** | **~2.5-4GB** |

This is a fraction of what Datadog or ELK would require. All tools are proven at scale, open source, and have active communities.

---

## QUICK-START COMMANDS

### 1. Uptime Kuma (via PM2)
```bash
git clone https://github.com/louislam/uptime-kuma.git
cd uptime-kuma && npm run setup
pm2 start server/server.js --name uptime-kuma
```

### 2. Sentry (PHP SDK)
```bash
composer require sentry/sentry
```
```php
\Sentry\init(['dsn' => 'https://your-dsn@sentry.io/project']);
```

### 3. Sentry (Node.js SDK)
```bash
npm install @sentry/node
```
```javascript
const Sentry = require("@sentry/node");
Sentry.init({ dsn: "https://your-dsn@sentry.io/project" });
```

### 4. BullMQ (using existing Redis)
```bash
npm install bullmq
```
```javascript
const { Queue, Worker } = require('bullmq');
const queue = new Queue('alfred-tasks', { connection: { host: '127.0.0.1' } });
```

### 5. Grafana + Prometheus + Loki (Docker Compose)
```yaml
# docker-compose.monitoring.yml
version: '3.8'
services:
  prometheus:
    image: prom/prometheus:latest
    volumes:
      - ./prometheus.yml:/etc/prometheus/prometheus.yml
    ports:
      - "9090:9090"

  loki:
    image: grafana/loki:latest
    ports:
      - "3100:3100"

  grafana:
    image: grafana/grafana:latest
    ports:
      - "3000:3000"
    environment:
      - GF_SECURITY_ADMIN_PASSWORD=alfred-secure-pass
```

### 6. Umami (on existing MySQL)
```bash
git clone https://github.com/umami-software/umami.git
cd umami && npm install
# Configure DATABASE_URL to existing MySQL
npm run build && pm2 start npm --name umami -- start
```

### 7. Metabase (Docker)
```bash
docker run -d -p 3030:3000 \
  -e MB_DB_TYPE=mysql \
  -e MB_DB_DBNAME=alfred_analytics \
  -e MB_DB_PORT=3306 \
  -e MB_DB_USER=metabase \
  -e MB_DB_PASS=secure-pass \
  -e MB_DB_HOST=localhost \
  --name metabase metabase/metabase
```

### 8. Meilisearch
```bash
curl -L https://install.meilisearch.com | sh
./meilisearch --master-key="alfred-search-key" --db-path ./meili_data
```

---

*This document provides a comprehensive evaluation of all major tools in each category. All recommended tools are free and open source. Total infrastructure cost for a complete observability, analytics, and business intelligence stack: $0/month.*
