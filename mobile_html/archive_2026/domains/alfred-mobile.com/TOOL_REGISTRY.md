# GoSiteMe Tool Naming Convention & Registry Spec

**Version:** 2.0  
**Date:** 2026-03-06  
**Status:** Active  
**Applies to:** All tools across Alfred Chat, VAPI Voice, MCP Server, GoCodeMe IDE, API, UI, External MCP, and Composio

---

## 1. The Problem (v1.0) → The Solution (v2.0)

### v1.0: GoSiteMe had **~900 tools** across **6 registries** with no single source of truth.

### v2.0: Alfred now aggregates **13,000+ tools** from **6 providers** via a unified provider system.

| # | Provider | Tool Count | Type | API |
|---|---|---|---|---|
| 1 | **Native** (`api/tools.php` `$TOOL_REGISTRY`) | 400+ | Built-in | `?action=list` |
| 2 | **MCP Server** (port 3005) | 807 | MCP protocol | `tools/list` via MCP |
| 3 | **External MCP** (870+ servers) | 1,200+ | MCP client | `?action=discover&provider=mcp-external` |
| 4 | **Composio** (850+ apps) | 11,000+ | REST + OAuth | `?action=discover&provider=composio` |
| 5 | **VAPI Voice** (`api/vapi-tools.php`) | 85 | Webhook | Voice-activated only |
| 6 | **Marketplace** (community) | Growing | Community | `?action=discover&provider=marketplace` |

### Provider API Endpoints

```
GET /api/tools.php?action=providers           → List all 6 providers with counts
GET /api/tools.php?action=discover&provider=X → Explore tools from a specific provider
GET /api/tools.php?action=list                → List native tools (with pagination)
GET /api/tools.php?action=search&q=email      → Search native tools
POST /api/tools.php?action=execute            → Execute a tool
```

### SDK Usage

```typescript
// Node.js
const { providers, total_available } = await alfred.tools.providers();
// → { providers: [...], active_tools: 1062, total_available: 13262 }

const composio = await alfred.tools.discover('composio');
// → { sample_apps: [{name:'Gmail', actions:20}, ...], total_actions: 11000 }
```

```python
# Python
result = alfred.tools.providers()
print(f"{result['total_available']} tools")  # 13262 tools

native = alfred.tools.discover('native')
print(f"{len(native['tools'])} native tools")  # 170 native tools
```

```php
// PHP
$result = $alfred->tools->providers();
echo $result['total_available']; // 13262

$mcp = $alfred->tools->discover('mcp-external');
print_r($mcp['sample_servers']); // Brave Search, GitHub, Puppeteer...
```

---

## 2. Naming Convention

### 2.1 Three-Layer Architecture

Every tool has exactly **three names**:

| Layer | Key | Format | Example | Where Used |
|---|---|---|---|---|
| **Canonical ID** | `id` | `snake_case` | `check_domain` | DB, API calls, MCP, switch cases, everywhere code touches it |
| **Display Name** | `display_name` | Title Case | `Check Domain` | UI titles, cards, search results, breadcrumbs |
| **MCP Wire Name** | `mcp_name` | `prefix_name` | `mcp_gocodeme-files_check_domain` | MCP protocol only (never shown to users) |

### 2.2 Canonical ID Rules (`id` / `name`)

```
{verb}_{noun}
{verb}_{noun}_{qualifier}
{category}_{verb}_{noun}       (only for category-specific tools)
```

| Rule | Good | Bad |
|---|---|---|
| `snake_case` only | `check_domain` | `checkDomain`, `Check-Domain` |
| `verb_noun` pattern | `send_sms` | `sms_send`, `sms` |
| Max 3 words for user-facing | `list_my_agents` | `list_all_my_voice_agents_with_details` |
| Max 4 words for internal | `sentinel_check_integrity` | `sentinel_security_check_file_integrity_scan` |
| No platform prefixes | `check_domain` | `mcp_check_domain`, `vapi_check_domain` |
| Category prefix only for engine-level tools | `cortex_set_goal` | `set_goal` (too generic for a reasoning engine tool) |

### 2.3 Standard Verbs

| Verb | Meaning | Example |
|---|---|---|
| `get` | Read one item | `get_profile`, `get_call_details` |
| `list` | Read multiple items | `list_my_agents`, `list_campaigns` |
| `create` | Make a new thing | `create_client`, `create_campaign` |
| `update` | Modify existing | `update_my_agent`, `update_campaign` |
| `delete` | Remove permanently | `delete_my_agent`, `delete_document` |
| `check` | Verify/validate | `check_domain`, `check_site_health` |
| `send` | Transmit outbound | `send_sms`, `send_fax`, `send_email` |
| `order` | Purchase/provision | `order_hosting`, `order_phone_number` |
| `search` | Find by query | `search_tools`, `search_files` |
| `assign` | Link two things | `assign_phone_to_agent` |
| `process` | Execute an operation | `process_payment`, `process_image` |
| `run` | Execute code/command | `run_code`, `run_tests` |
| `setup` | Configure/initialize | `setup_ssl`, `setup_oauth` |
| `generate` | AI-create content | `generate_image`, `generate_blog_post` |
| `draft` | Create a document draft | `legal_draft_motion` |
| `analyze` | Deep inspection | `analyze_errors`, `prism_analyze_layout` |

### 2.4 Category Prefixes (for Engine/System Tools Only)

Used **only** for internal subsystem tool families where generic names would collide:

| Prefix | Subsystem | Example |
|---|---|---|
| `cortex_` | Reasoning & Planning | `cortex_set_goal`, `cortex_decompose` |
| `nexus_` | Knowledge Graph | `nexus_add_entity`, `nexus_query` |
| `echo_` | Anomaly Detection | `echo_detect_anomaly`, `echo_forecast` |
| `sentinel_` | Security | `sentinel_vuln_scan`, `sentinel_check_ip` |
| `empathy_` | Sentiment/Tone | `empathy_detect_tone`, `empathy_deescalate` |
| `tempo_` | Time Analytics | `tempo_predict`, `tempo_velocity` |
| `pulse_` | Engagement | `pulse_churn_predict`, `pulse_satisfaction` |
| `muse_` | Creative Generation | `muse_brainstorm`, `muse_copywrite` |
| `sage_` | Text Analysis | `sage_summarize`, `sage_translate` |
| `prism_` | Design Analysis | `prism_check_contrast`, `prism_typography` |
| `forge_` | Code Generation | `forge_generate_component`, `forge_generate_tests` |
| `conduit_` | API Pipelines | `conduit_create_pipeline`, `conduit_call_api` |
| `commerce_` | E-commerce Ops | `commerce_list_orders`, `commerce_process_refund` |
| `fleet_` | Call Center Fleet | `fleet_create`, `fleet_call_whisper` |
| `chronicle_` | Session Tracking | `chronicle_start_session`, `chronicle_log_event` |
| `legal_` | Legal Aid | `legal_draft_motion`, `legal_search` |
| `alfred_` | Consciousness Layer | `alfred_remember`, `alfred_self_reflect` |
| `messaging_` | Multi-Channel Messaging | `messaging_send_sms`, `messaging_create_campaign` |
| `a2a_` | Agent-to-Agent | `a2a_send_task`, `a2a_discover` |

**User-facing tools NEVER get a prefix.** `check_domain` not `hosting_check_domain`.

### 2.5 Display Name Generation

The `humanizeToolName()` function converts canonical IDs to display names:

```
snake_case → Title Case, with special-case overrides
```

| Canonical ID | Display Name |
|---|---|
| `check_domain` | Check Domain |
| `send_sms` | Send SMS |
| `legal_draft_motion` | Draft Motion |
| `get_my_calls` | My Calls |
| `cortex_set_goal` | Set Goal |
| `three_d_print_slicer` | 3D Print Slicer |

Rules:
1. Strip category prefix for display (e.g., `legal_` → show only the action)
2. Capitalize known acronyms: SMS, DNS, SSL, SEO, PDF, HTML, CSS, API, AI, MCP, CI, CD, IDE, URL, SSO, CORS, GDPR, CCPA, HIPAA, OKR, ROI, SLA, KPI, CRM, NPS, IEP, GPA, CMA, MLS
3. Preserve special words: `OAuth`, `WordPress`, `GitHub`, `PayPal`, `LiveKit`, `CanLII`, `VAPI`, `Telnyx`, `GoCodeMe`, `GoSiteMe`
4. `get_*` / `list_*` verbs may be dropped for card titles: `get_invoices` → "Invoices" or "My Invoices"

### 2.6 VAPI Voice Naming (camelCase Aliases)

VAPI tools use `camelCase` wire format but map 1:1 to canonical IDs:

| Canonical ID | VAPI Alias | Same Tool |
|---|---|---|
| `check_domain` | `checkDomainAvailability` | ✓ |
| `list_my_agents` | `listMyAgents` | ✓ |
| `send_sms` | `sendSMS` | ✓ |
| `create_client` | `createClient` | ✓ |

VAPI aliases are generated automatically — never hand-maintained separately.

### 2.7 MCP Wire Names (Never User-Facing)

The MCP protocol prepends `mcp_{serverName}_` to all tools from external servers:

```
mcp_gocodeme-files_read_file     → canonical: read_file
mcp_gocodeme-files_db_query      → canonical: db_query
mcp_gocodeme-files_client_services → canonical: get_services (after alias resolution)
```

**Rule:** MCP wire names are stripped at the protocol boundary. No UI, log, API response, or error message should ever contain a `mcp_` prefix.

---

## 3. Category Taxonomy

### 3.1 User-Facing Categories (for UI/Marketing)

| Category ID | Display Label | Icon | Tool Count |
|---|---|---|---|
| `students_k12` | K-12 Students | 📚 | 14 |
| `university` | University | 🎓 | 15 |
| `professionals` | Professionals | 💼 | 15+ |
| `small_business` | Small Business | 🏪 | 38+ |
| `content_creators` | Content Creators | 🎬 | 14 |
| `healthcare` | Healthcare | 🏥 | 35+ |
| `real_estate` | Real Estate | 🏠 | 10+ |
| `legal` | Legal | ⚖️ | 15 |
| `legal_aid` | Legal Aid | 🏛️ | 4+ |
| `parents` | Parents & Family | 👨‍👩‍👧 | 8+ |
| `seniors` | Seniors | 🧓 | 11+ |
| `teachers` | Teachers | 📐 | 15 |
| `freelancers` | Freelancers | ✏️ | 9+ |
| `nonprofits` | Nonprofits | 🎗️ | 12 |
| `devops` | Developer Tools | 💻 | 10+ |
| `hosting` | Hosting & Domains | 🌐 | 4+ |
| `ecommerce` | E-Commerce | 🛒 | 4+ |
| `seo_marketing` | SEO & Marketing | 🔍 | 5 |
| `communication` | Communication | 💬 | 4+ |
| `security` | Security | 🛡️ | 4 |
| `ai_media` | AI Media | 🎨 | 4 |
| `future_tech` | Future Tech | ⚛️ | 15 |
| `agent_orchestration` | Agent Orchestration | 🚀 | 10 |
| `collaboration` | Collaboration | 👥 | 33+ |
| `conferencing` | Conferencing | 📹 | 10+ |
| `reporting` | Reporting | 📊 | 15+ |
| `marketplace_tools` | Marketplace | 🛒 | 19+ |
| `gamification` | Gamification | 🎮 | 15+ |
| `offline` | Offline | 📴 | 5 |

### 3.2 Internal-Only Categories (IDE/Engine — Not Shown to Users)

| Category ID | Display Label | Purpose |
|---|---|---|
| `filesystem` | File System | read/write/delete/rename files |
| `terminal` | Terminal | Run commands, session management |
| `database` | Database | SQL, schema, migrations |
| `infrastructure` | Infrastructure | SSH, Docker, K8s |
| `memory` | Memory | Remember/recall/forget |
| `self_evolution` | Self-Evolution | Auto-generate new tools |
| `predictive` | Predictive Build | Predict & pre-build changes |
| `reasoning` | Reasoning (Cortex) | Planning, decomposition, decisions |
| `knowledge_graph` | Knowledge (Nexus) | Entity relations, impact analysis |
| `anomaly` | Anomaly (Echo) | Pattern detection, forecasting |
| `sentiment` | Sentiment (Empathy) | Tone detection, de-escalation |
| `design` | Design (Prism) | Layout, color, typography analysis |
| `creative` | Creative (Muse) | Brainstorming, copywriting |
| `text` | Text (Sage) | Summarize, translate, simplify |
| `code_gen` | Code (Forge) | Component, test, CRUD generation |
| `pipelines` | API (Conduit) | Pipelines, webhooks, API calls |

---

## 4. The `humanizeToolName()` Function

### PHP (includes/tool-helpers.php)

```php
function humanizeToolName(string $toolId): string
```

Converts `snake_case` canonical IDs to human-readable display names.

### JavaScript (assets/js/tool-helpers.js)

```javascript
function humanizeToolName(toolId) { ... }
```

Same logic, client-side.

### Both implementations:
1. Check a `DISPLAY_OVERRIDES` map first (hand-curated names for exceptions)
2. Strip known category prefixes for user-facing display
3. Apply acronym uppercasing (SMS, DNS, SSL, etc.)
4. Title-case the remaining words

### Display Override Examples

| Canonical ID | Override Display Name |
|---|---|
| `three_d_print_slicer` | 3D Print Slicer |
| `two_factor_setup` | Two-Factor Setup |
| `ci_cd_pipeline` | CI/CD Pipeline |
| `wp_install` | WordPress Install |
| `a2a_send_task` | Agent-to-Agent Task |
| `rag_query` | RAG Query |
| `k8s_manage` | Kubernetes Manager |
| `da_git_status` | Git Status |
| `client_sso_login` | SSO Login |
| `iep_goal_writer` | IEP Goal Writer |
| `soap_note_writer` | SOAP Note Writer |
| `hipaa_compliance` | HIPAA Compliance |
| `gdpr_audit` | GDPR Audit |
| `og_preview` | OpenGraph Preview |

---

## 5. API Contract

### `GET /api/tools.php?action=list`

Every tool returned MUST include:

```json
{
  "name": "check_domain",
  "display_name": "Check Domain",
  "category": "hosting",
  "category_label": "Hosting & Domains",
  "description": "Check if a domain name is available for registration",
  "icon": "🌐",
  "tier": "starter",
  "demographics": ["webmasters", "small_business"]
}
```

### `GET /api/tools.php?action=categories`

Every category MUST include:

```json
{
  "name": "small_business",
  "label": "Small Business",
  "icon": "🏪",
  "count": 15,
  "tools": ["invoice_generator", "expense_tracker", ...]
}
```

---

## 6. Adding New Tools — Checklist

When adding a new tool:

- [ ] **Canonical ID** follows `verb_noun` pattern (max 3 words user-facing, 4 internal)
- [ ] **Display name** renders correctly via `humanizeToolName()` — if not, add to `DISPLAY_OVERRIDES`
- [ ] **Category** uses an existing category ID from Section 3 — or propose a new one
- [ ] **Added to canonical source:** `gocodeme/mcp-server/src/tools.js` (schema + description)
- [ ] **Added to dispatcher:** `gocodeme/mcp-server/src/toolDispatch.js` (implementation)
- [ ] **If user-facing via chat:** Added to `api/alfred-chat.php` → `getChatTools()`
- [ ] **If user-facing via voice:** Added to `api/vapi-tools.php` with camelCase alias
- [ ] **If shown in tool directory:** Added to `api/tools.php` → `$TOOL_REGISTRY`
- [ ] **If backed by API module:** Added to routing array in `api/alfred-chat.php` (e.g. `$gamificationToolRouting`, `$healthcareToolRouting`)
- [ ] **If module tool:** Added to `includes/extended-tools.php` with input schema & `includes/tool-helpers.php` display overrides
- [ ] **Tier assigned:** `starter` (free), `professional` ($14.99+), `enterprise` ($49.99+)
- [ ] **No `mcp_` prefix** in any user-facing surface

---

## 7.5. API Backend Module Registry

Tools with real backend implementations are routed via `api/alfred-chat.php` routing arrays to dedicated API modules:

| Module | API File | Tools | Routing Array | Dashboard |
|---|---|---|---|---|
| **Financial** | `api/financial/*.php` (8 files) | 82 | `$financialToolRouting` | `/finance-dashboard` |
| **Gamification** | `api/gamification.php` | 10+5 aliases | `$gamificationToolRouting` | `/gamification-dashboard` |
| **Reporting** | `api/reporting-engine.php` | 12+3 aliases | `$reportingToolRouting` | `/reporting-dashboard` |
| **Marketplace** | `api/marketplace-backend.php` | 16 | `$marketplaceToolRouting` | `/marketplace` |
| **Small Business** | `api/small-biz.php` | 23 | `$smallBizToolRouting` | `/biz-dashboard` |
| **Collaboration** | `api/collaboration.php` | 28+5 aliases | `$collaborationToolRouting` | `/collaboration-dashboard` |
| **Healthcare** | `api/healthcare.php` | 29+5 aliases | `$healthcareToolRouting` | `/healthcare-dashboard` |

### Specialist Agents (agent-registry.php)

| Agent ID | Name | Domain | Parent Director | Tools Pattern |
|---|---|---|---|---|
| `gamemaster` | GameMaster | gamification | Herald | `gamify_*` |
| `analyst` | Analyst | reporting | Oracle | `report_*` |
| `curator` | Curator | marketplace | Herald | `marketplace_*` |
| `bizops` | BizOps | small_business | Atlas | `crm_*`, `time_*`, `biz_*` |
| `collaborator` | Collaborator | collaboration | Pulse | `collab_*` |
| `clinician` | Clinician | healthcare | Sage | `hc_*` |

---

## 8. Migration Plan (Existing Tools)

### Phase 1: Add `display_name` to API (DONE with this commit)
- `api/tools.php` now returns `display_name` and `category_label` on every tool
- `includes/tool-helpers.php` provides `humanizeToolName()` and `humanizeCategoryName()`

### Phase 2: Audit VAPI aliases (Future)
- Map all ~460 VAPI `camelCase` names to canonical `snake_case` IDs
- Generate VAPI aliases automatically from canonical registry

### Phase 3: Multi-Provider Architecture ✅ (v2.0 — March 2026)
- `$TOOL_PROVIDERS` array in `api/tools.php` — 6 providers with metadata
- `?action=providers` — enumerate all providers with tool counts
- `?action=discover&provider=X` — explore tools from any provider
- SDK `tools.providers()` and `tools.discover()` in Node.js, Python, PHP
- Dynamic tool aggregation — native tools served directly, external tools discovered at runtime
- MCP Client capability — Alfred connects to 870+ external MCP servers
- Composio integration — 850+ apps, 11,000+ actions with managed OAuth

### Phase 4: Runtime Discovery (Next)
- Build MCP client in `websocket/mcp-client.js` — connect to external MCP servers
- Integrate Composio SDK for OAuth-managed tool execution
- Deploy Meilisearch for instant search across all 13,000+ tools
- Auto-register discovered tools into searchable index

---

## 8. Quick Reference

```
User sees:    "Check Domain"         (display_name)
API sends:    "check_domain"         (canonical ID)
MCP routes:   "mcp_gocodeme-files_check_domain"  (wire name, never seen)
VAPI calls:   "checkDomainAvailability"           (camelCase alias)
Provider:     "native"               (provider ID for multi-provider filtering)
```

**Tool Counts by Provider:**
```
Native:         170 tools   (hardcoded in $TOOL_REGISTRY)
MCP Server:     807 tools   (MCP protocol on port 3005)
External MCP: 1,200+ tools  (870+ community MCP servers)
Composio:    11,000+ tools  (850+ apps with managed OAuth)
VAPI Voice:      85 tools   (voice-activated webhook)
Marketplace:     0+ tools   (community-published, growing)
─────────────────────────────
TOTAL:       13,262+ tools
```

**Golden Rule:** If a human can see it, use `display_name`. If code touches it, use `canonical ID`. MCP wire names never leave the server. Provider IDs are used for filtering and discovery.
