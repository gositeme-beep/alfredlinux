# GoCodeMe MCP Server — Complete Codebase Audit Report

**Date:** 2025-07-17  
**Auditor:** Automated code audit (Claude Opus 4.6)  
**Scope:** `/gocodeme/mcp-server/src/` — all 77 JavaScript source files  
**Package:** `gocodeme-mcp-server` v1.0.0  
**Runtime:** Node.js ≥ 18.0.0 (ESM `"type": "module"`)

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Architecture Overview](#2-architecture-overview)
3. [Complete Directory Tree](#3-complete-directory-tree)
4. [Core Module Inventory](#4-core-module-inventory)
5. [Complete Tool Inventory (218 tools)](#5-complete-tool-inventory-218-tools)
6. [Dependency Analysis](#6-dependency-analysis)
7. [Security Assessment](#7-security-assessment)
8. [Bug Report](#8-bug-report)
9. [Dead Code & Incomplete Implementations](#9-dead-code--incomplete-implementations)
10. [Performance Concerns](#10-performance-concerns)
11. [Recommendations](#11-recommendations)

---

## 1. Executive Summary

The GoCodeMe MCP Server is a **massive** Model Context Protocol server that bridges an AI assistant ("Alfred") to a DirectAdmin web hosting control panel, a WHMCS billing system, and over a dozen external APIs. It exposes **218 tools** organized across 30+ categories spanning file management, database operations, domain/DNS/SSL/email, e-commerce, AI media generation, browser automation, voice communication, code analysis, and 10 specialized "intelligence engines" (Empathy, Muse, Prism, Tempo, Echo, Pulse, Sage, Cortex, Nexus, Chronicle, Sentinel, Forge, Conduit, Architect).

**Key Metrics:**
- **Total source lines:** ~20,500 lines across 77 JS files + 1 Python script
- **Largest files:** `tools.js` (6,073 lines), `toolDispatch.js` (5,084 lines)
- **Tools defined:** 218
- **Dependencies:** 19 npm packages
- **Transport modes:** 2 (stdio, HTTP/SSE)

**Health Rating: 🟡 GOOD with notable concerns**
- Strong security sandbox and multi-layer middleware
- Well-structured modular architecture
- Several bugs and inconsistencies found (see §8)
- Many Phase 27 tools are stub/placeholder implementations
- Version numbering is inconsistent

---

## 2. Architecture Overview

```
┌─────────────────────────────────────────────────────────┐
│                    TRANSPORT LAYER                       │
│  index.js (stdio)    │    mcpHttpServer.js (HTTP/SSE)   │
│  Port: N/A           │    Port: 3005                    │
│  1 session at a time │    Multi-session + JWT auth      │
└───────────┬──────────┴────────────┬─────────────────────┘
            │                       │
            ▼                       ▼
┌─────────────────────────────────────────────────────────┐
│                    MCP SDK LAYER                         │
│  ListToolsRequestSchema → toolDefinitions (tools.js)    │
│  CallToolRequestSchema  → dispatchTool (toolDispatch.js)│
└──────────────────────────┬──────────────────────────────┘
                           │ 5-layer middleware:
                           │ 1. sandbox.validateRequest()
                           │ 2. tokenGate.checkAllowance()
                           │ 3. CircuitBreaker.check()
                           │ 4. withRetry() wrapper
                           │ 5. _dispatchToolInner() switch
                           ▼
┌─────────────────────────────────────────────────────────┐
│                    ENGINE LAYER                          │
│                                                         │
│  ┌──────────┐ ┌────────────┐ ┌──────────┐ ┌──────────┐│
│  │ daClient │ │whmcsClient │ │gitClient │ │wpManager ││
│  │(DirectAd)│ │  (WHMCS)   │ │  (Git)   │ │(WordPress││
│  └──────────┘ └────────────┘ └──────────┘ └──────────┘│
│  ┌──────────┐ ┌────────────┐ ┌──────────┐ ┌──────────┐│
│  │shellExec │ │ together   │ │ browser  │ │  voice   ││
│  │(terminal)│ │ Client(AI) │ │(Playwrgt)│ │(WS+STT)  ││
│  └──────────┘ └────────────┘ └──────────┘ └──────────┘│
│  ┌──────────┐ ┌────────────┐ ┌──────────┐ ┌──────────┐│
│  │ memory   │ │    RAG     │ │  cortex  │ │ sentinel ││
│  │(vectors) │ │ (pipeline) │ │(planning)│ │(security)││
│  └──────────┘ └────────────┘ └──────────┘ └──────────┘│
│  + 20 more engines (forge, chronicle, nexus, muse...) ││
└─────────────────────────────────────────────────────────┘
```

### Transport Details

| Mode | Entry Point | Auth | Sessions |
|------|-------------|------|----------|
| stdio | `index.js` | Env vars (DA_HOST, DA_ADMIN_USER, DA_ADMIN_PASS) | Single |
| HTTP/SSE | `mcpHttpServer.js` | JWT Bearer token → resolves daUsername | Multi (Map) |

### Middleware Pipeline (toolDispatch.js lines 56-141)

Every tool call passes through 5 layers in order:

1. **Sandbox** (`sandbox.validateRequest`) — Path validation, command filtering, rate limiting (120 req/min), concurrency cap (5 concurrent)
2. **Token Gate** (`tokenGate.checkAllowance`) — Redis-backed token balance check against WHMCS billing. Costs range from 0 (billing tools) to 25,000 (video generation). Fails open if Redis unavailable.
3. **Circuit Breaker** (`CircuitBreaker.check`) — Opens after 5 consecutive failures. 60s cooldown. Half-open probe after reset.
4. **Retry** (`withRetry`) — 2 retries with exponential backoff (500ms base, 5s max). Only retries "retryable" errors (network, timeout, 5xx). Permanent errors fail immediately.
5. **Inner Dispatch** (`_dispatchToolInner`) — The actual tool implementation via a massive switch statement.

### Auto-Checkpoint System (toolDispatch.js lines 25-52)

Destructive operations (`write_file`, `delete_file`, `rename_file`, `create_email_account`, etc.) auto-create a checkpoint before executing (with 60s cooldown between auto-checkpoints).

---

## 3. Complete Directory Tree

```
src/
├── index.js                   (78 lines)   — Stdio MCP transport entry
├── mcpHttpServer.js          (466 lines)   — HTTP/SSE transport + Telnyx API
├── tools.js                 (6,073 lines)  — 218 tool definitions
├── toolDispatch.js          (5,084 lines)  — Tool dispatcher + switch cases
├── daClient.js               (574 lines)   — DirectAdmin API client
├── whmcsClient.js            (664 lines)   — WHMCS billing API + RDAP
├── gitClient.js              (452 lines)   — Git operations via DA
├── wpManager.js              (337 lines)   — WordPress management
├── shellExec.js              (113 lines)   — Shell command execution
├── logAnalyzer.js            (185 lines)   — Error/access log analysis
├── securityScanner.js        (301 lines)   — Malware/permission scanning
├── analyticsParser.js        (206 lines)   — Visitor/bandwidth stats
├── siteHealth.js             (272 lines)   — Site health checks
├── imageGenerator.js         (442 lines)   — Image generation wrapper
├── docGenerator.js           (487 lines)   — Word document generation
├── pdfGenerator.js           (440 lines)   — PDF document generation
├── togetherClient.js         (467 lines)   — Together.ai unified API client
├── embeddings.js              (83 lines)   — ONNX embedding model (Xenova)
├── vectorStore.js            (231 lines)   — File-based vector store
│
├── analytics/
│   └── analytics.js          (176 lines)   — Tool call analytics
│
├── architect/
│   └── architectEngine.js    (321 lines)   — Infrastructure/DevOps engine
│
├── artifacts/
│   ├── artifactServer.js     (159 lines)   — Express middleware for artifacts
│   ├── chartGenerator.js     (103 lines)   — Chart.js SVG generation
│   ├── diagramRenderer.js    (111 lines)   — Mermaid diagram rendering
│   └── htmlPreview.js         (72 lines)   — HTML preview sandbox
│
├── audit/
│   └── dependencyAudit.js    (255 lines)   — npm/composer vulnerability scan
│
├── billing/
│   └── tokenGate.js          (250 lines)   — WHMCS token metering
│
├── browser/
│   ├── autopilotSession.js   (548 lines)   — Live browser automation agent
│   ├── browserPool.js        (186 lines)   — Playwright browser pool
│   ├── pageAnalyzer.js       (142 lines)   — Page content extraction
│   └── webAgent.js           (306 lines)   — Stateless web agent tools
│
├── chronicle/
│   └── chronicleEngine.js    (322 lines)   — Audit trail & activity logging
│
├── conduit/
│   └── conduitEngine.js      (298 lines)   — API gateway & pipeline engine
│
├── cortex/
│   └── cortexEngine.js       (423 lines)   — Planning & reasoning engine
│
├── database/
│   └── mysqlTools.js         (331 lines)   — MySQL query/schema tools
│
├── docs/
│   └── toolDocs.js           (339 lines)   — Tool documentation generator
│
├── echo/
│   └── echoEngine.js         (393 lines)   — Pattern detection engine
│
├── empathy/
│   └── empathyEngine.js      (410 lines)   — Emotional intelligence engine
│
├── forge/
│   └── forgeEngine.js        (468 lines)   — Code generation & scaffolding
│
├── git/
│   ├── gitContext.js          (211 lines)  — Git status/diff/log helpers
│   └── smartCommit.js         (264 lines)  — AI-powered commit messages
│
├── indexer/
│   └── codeIndexer.js        (465 lines)   — Semantic code search indexer
│
├── interpreter/
│   ├── codeInterpreter.js    (195 lines)   — Sandboxed code execution
│   ├── outputCapture.js       (81 lines)   — Output capture for interpreter
│   └── sessionPool.js        (182 lines)   — Interpreter session pool
│
├── localLlm/
│   ├── hybridRouter.js       (219 lines)   — Route between local/cloud LLM
│   ├── modelManager.js       (211 lines)   — Ollama model management
│   └── ollamaClient.js       (148 lines)   — Ollama API client
│
├── mcpClient/
│   ├── mcpClientManager.js   (225 lines)   — Connect to other MCP servers
│   ├── serverRegistry.js     (126 lines)   — MCP server registry
│   └── toolRouter.js          (76 lines)   — Route tools to MCP servers
│
├── media/
│   └── mediaProcessor.js     (479 lines)   — FFmpeg video/image processing
│
├── memory/
│   ├── memoryEngine.js       (168 lines)   — Vector-based long-term memory
│   ├── contextPruner.js      (166 lines)   — Memory ranking & pruning
│   └── conversationSummarizer.js (58 lines) — Conversation compression
│
├── muse/
│   └── museEngine.js         (288 lines)   — Creative intelligence engine
│
├── nexus/
│   └── nexusEngine.js        (370 lines)   — Knowledge graph engine
│
├── orchestrator/
│   └── agentOrchestrator.js  (310 lines)   — Multi-agent spawning
│
├── playbooks/
│   └── playbookEngine.js     (411 lines)   — Playbook renderer/executor
│
├── prism/
│   └── prismEngine.js        (352 lines)   — Visual/design intelligence
│
├── proactive/
│   ├── monitoringAgent.js    (191 lines)   — Periodic site monitoring
│   ├── alertEngine.js        (174 lines)   — Alert management
│   ├── autoFixer.js          (150 lines)   — Auto-remediation
│   └── contextBuilder.js    (104 lines)   — Monitoring context builder
│
├── project/
│   └── projectSnapshot.js   (180 lines)   — Project state snapshot
│
├── pulse/
│   └── pulseEngine.js        (406 lines)   — Social/engagement intelligence
│
├── rag/
│   ├── ragPipeline.js        (241 lines)   — RAG query pipeline
│   ├── documentIngester.js   (203 lines)   — Document ingestion
│   ├── chunker.js            (237 lines)   — Document chunking
│   └── vectorCollection.js  (198 lines)   — Named vector collections
│
├── resilience/
│   └── errorRecovery.js      (390 lines)   — Retry, circuit breaker, rollback
│
├── review/
│   └── codeReview.js         (158 lines)   — AI-powered diff review
│
├── sage/
│   └── sageEngine.js         (360 lines)   — Linguistic intelligence engine
│
├── scheduler/
│   └── schedulerEngine.js    (293 lines)   — Cron/scheduled task engine
│
├── security/
│   └── sandbox.js            (300 lines)   — Multi-user isolation sandbox
│
├── sentinel/
│   └── sentinelEngine.js     (395 lines)   — Security monitoring engine
│
├── tempo/
│   └── tempoEngine.js        (352 lines)   — Temporal intelligence engine
│
├── terminal/
│   └── sessionManager.js     (297 lines)   — Persistent shell sessions
│
├── voice/
│   ├── voiceServer.js        (559 lines)   — WebSocket voice server (STT+TTS)
│   ├── voiceToolBridge.js    (343 lines)   — Voice-to-tool bridge
│   ├── telnyxCalls.js        (263 lines)   — Telnyx phone call API
│   └── livekitService.js     (207 lines)   — LiveKit room management
│
├── watcher/
│   └── fileWatcher.js        (248 lines)   — File change auto-indexer
│
└── workflows/
    ├── n8nBridge.js          (237 lines)   — n8n workflow automation
    └── workflowTemplates.js   (89 lines)   — Built-in workflow templates

scripts/
└── html_parser.py             — BeautifulSoup fallback for fetch_url
```

**Total: 77 JavaScript files + 1 Python helper = ~20,500 lines of code**

---

## 4. Core Module Inventory

### 4.1 Entry Points

| File | Purpose | Status |
|------|---------|--------|
| `index.js` | Stdio MCP transport — single session per process. Reads DA creds from env vars. | ✅ Working |
| `mcpHttpServer.js` | HTTP + SSE MCP transport — multi-session with JWT auth. Also hosts Telnyx call API, artifact server, tool docs API, memory context endpoint. Boots scheduler, voice server, embeddings warmup on startup. | ✅ Working |

### 4.2 Core Clients

| File | Purpose | Dependencies | Status |
|------|---------|-------------|--------|
| `daClient.js` | DirectAdmin control panel API wrapper. Uses classic `CMD_API_*` URL-encoded endpoints. Covers: files, databases, domains, subdomains, email, DNS, SSL, cron, backups, account stats. Path safety via `safePath()`. | axios, form-data | ✅ Working |
| `whmcsClient.js` | WHMCS billing API client. Scoped per-customer via `clientId`. SAFETY: financial operations require `confirmed: true` double-call pattern. Includes RDAP fallback for domain availability (bypasses blocked WHOIS port 43). | native https | ✅ Working |
| `togetherClient.js` | Together.ai unified API client. Covers: chat, image gen (27 models), video gen (23 models, async polling), TTS (Kokoro/Cartesia/Orpheus), STT (Whisper), vision (Qwen3-VL), embeddings, reranking. | axios | ✅ Working |
| `gitClient.js` | Git operations via DirectAdmin. Status, log, diff, commit, revert, init, clone. | shellExec | ✅ Working |
| `wpManager.js` | WordPress management. Install, info, plugins, themes, updates, DB optimize, search catalog. Uses WP-CLI via shell. | shellExec | ✅ Working |
| `shellExec.js` | Safe shell execution. Locked to homeDir, 120s timeout, 256KB output cap. Two modes: `shellExec` (shell string) and `shellExecFile` (no injection). | child_process | ✅ Working |

### 4.3 Infrastructure Modules

| File | Purpose | Status |
|------|---------|--------|
| `security/sandbox.js` | Path validation, command filtering (28 blocked patterns), rate limiting (120/min), concurrent command cap (5), cross-user detection. | ✅ Solid |
| `billing/tokenGate.js` | Redis-backed token metering. Per-tool costs (0–25,000 tokens). 10% soft overage allowed. Fails open on Redis failure. | ✅ Working |
| `resilience/errorRecovery.js` | Error classification (retryable vs permanent), retry with exponential backoff, circuit breaker (5 failures → 60s cooldown), multi-step rollback, error telemetry. | ✅ Well-designed |
| `terminal/sessionManager.js` | Persistent per-user shell sessions. CWD persistence, 30min idle timeout, 100-command history, cwd-escape prevention. | ✅ Working |
| `memory/memoryEngine.js` | Vector-based long-term memory per user. Semantic recall via embeddings. Auto-injection into system prompt. | ✅ Working |
| `embeddings.js` | ONNX embedding model via `@xenova/transformers`. Feature extraction pipeline. | ✅ Working |
| `vectorStore.js` | File-based JSON vector store with cosine similarity search. | ✅ Working |

---

## 5. Complete Tool Inventory (218 tools)

### File Management (9 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `read_file` | path, offset?, length? | ✅ |
| `write_file` | path, content | ✅ |
| `list_directory` | path?, recursive?, pattern? | ✅ |
| `delete_file` | path | ✅ |
| `rename_file` | old_path, new_path | ⚠️ Text-only (DA workaround) |
| `create_directory` | path | ✅ |
| `search_files` | pattern, path?, case_sensitive? | ✅ |
| `find_file` | pattern, directory? | ⚠️ Bug: uses text-search not glob |
| `get_file_info` | path | ✅ |

### Database — DirectAdmin (4 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `list_databases` | — | ✅ |
| `create_database` | name, user?, password? | ✅ |
| `delete_database` | name | ✅ |
| `get_database_info` | name | ✅ |

### Domain Management (4 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `list_domains` | — | ✅ |
| `list_subdomains` | domain | ✅ |
| `create_subdomain` | domain, subdomain | ✅ |
| `delete_subdomain` | domain, subdomain | ✅ |

### Email Management (6 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `list_email_accounts` | domain | ✅ |
| `create_email_account` | domain, user, password, quota? | ✅ |
| `delete_email_account` | domain, user | ✅ |
| `create_email_forwarder` | domain, user, forward_to | ✅ |
| `create_autoresponder` | domain, user, subject, message | ✅ |
| `send_email` | to, subject, body, from?, html? | ✅ Uses local sendmail |

### DNS Management (3 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `list_dns_records` | domain | ✅ |
| `add_dns_record` | domain, type, name, value, ttl? | ✅ |
| `delete_dns_record` | domain, type, name, value | ✅ |

### SSL Management (3 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `request_ssl_certificate` | domain, wildcard? | ✅ Let's Encrypt |
| `get_ssl_status` | domain | ✅ |
| `force_https` | domain | ✅ |

### Cron Jobs (3 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `list_cron_jobs` | — | ✅ |
| `create_cron_job` | minute, hour, doM, month, doW, command | ✅ |
| `delete_cron_job` | index | ✅ |

### Backup (3 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `create_backup` | files?, databases?, email? | ✅ |
| `list_backups` | — | ✅ |
| `restore_backup` | backup_file | ✅ |

### Account Stats (3 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `get_account_usage` | — | ✅ |
| `get_account_limits` | — | ✅ |
| `get_account_summary` | — | ✅ |

### Commerce / WHMCS (15 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `get_my_profile` | — | ✅ |
| `get_my_services` | — | ✅ |
| `get_product_catalog` | — | ✅ |
| `check_domain_availability` | domain | ✅ RDAP fallback |
| `search_domains` | keyword, tlds? | ✅ |
| `get_domain_pricing` | tld? | ✅ |
| `register_domain` | domain, years, confirmed | ✅ Two-step |
| `order_hosting` | productId, domain, billingCycle, confirmed | ✅ Two-step |
| `get_invoices` | limit? | ✅ |
| `get_invoice_details` | invoiceId | ✅ |
| `pay_invoice` | invoiceId, confirmed | ✅ Two-step |
| `order_addon` | addonId, serviceId, confirmed | ✅ Two-step |
| `get_support_tickets` | status? | ✅ |
| `open_support_ticket` | subject, message, deptId?, priority?, confirmed | ✅ Two-step |
| `client_sso_login` | destination? | ✅ |

### Git — DirectAdmin (6 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `da_git_status` | domain | ✅ |
| `da_git_log` | domain, count? | ✅ |
| `da_git_diff` | domain | ✅ |
| `git_commit` | domain, message | ✅ |
| `git_revert` | domain, commit | ✅ |
| `git_init` | domain | ✅ |

### Checkpoint / Restore (3 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `create_checkpoint` | name, description? | ✅ |
| `list_checkpoints` | — | ✅ |
| `restore_checkpoint` | checkpoint_id | ✅ |

### WordPress (11 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `wp_install` | domain, site_title, admin_user, admin_pass, admin_email | ✅ |
| `wp_site_info` | domain | ✅ |
| `wp_list_plugins` | domain | ✅ |
| `wp_install_plugin` | domain, plugin | ✅ |
| `wp_remove_plugin` | domain, plugin | ✅ |
| `wp_list_themes` | domain | ✅ |
| `wp_install_theme` | domain, theme | ✅ |
| `wp_update_all` | domain | ✅ |
| `wp_db_optimize` | domain | ✅ |
| `wp_search_plugins` | query | ✅ |
| `wp_search_themes` | query | ✅ |

### Diagnostics (3 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `read_error_log` | domain?, lines? | ✅ |
| `read_access_log` | domain?, lines? | ✅ |
| `analyze_errors` | domain? | ✅ |

### Security (3 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `scan_malware` | path? | ✅ |
| `audit_permissions` | path? | ✅ |
| `security_scan` | domain | ✅ |

### Analytics (3 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `get_visitor_stats` | domain, period? | ✅ |
| `get_bandwidth_stats` | domain | ✅ |
| `get_traffic_report` | domain | ✅ |

### Site Health (2 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `check_site_health` | domain | ✅ |
| `get_server_info` | — | ✅ |

### Image Generation (2 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `generate_image` | prompt, domain, model?, width?, height?, steps?, filename? | ✅ Together.ai |
| `list_generated_images` | domain | ✅ |

### Document Generation (2 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `create_word_document` | filename, title, sections, domain? | ✅ docx library |
| `create_pdf_document` | filename, title, sections, domain? | ✅ pdfkit |

### Terminal (1 tool)
| Tool | Parameters | Status |
|------|-----------|--------|
| `run_terminal_command` | command, working_directory? | ✅ Persistent sessions |

### Web / Fetch (2 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `fetch_url` | url, method?, selector? | ✅ Python BeautifulSoup fallback |
| `read_pdf` | path | ⚠️ Complex fallback chain |

### Memory (4 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `alfred_remember` | text, category? | ✅ Vector-based |
| `alfred_recall` | query, topK?, category? | ✅ |
| `alfred_forget` | memory_id | ✅ |
| `alfred_memory_summary` | — | ✅ |

### Playbooks (3 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `run_playbook` | name, parameters? | ✅ |
| `list_playbooks` | — | ✅ |
| `save_playbook` | name, description, steps | ✅ |

### Scheduled Tasks (4 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `create_scheduled_task` | name, schedule, command | ✅ node-cron |
| `list_scheduled_tasks` | — | ✅ |
| `delete_scheduled_task` | task_id | ✅ |
| `get_scheduled_task_logs` | task_id? | ✅ |

### Semantic Search (3 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `semantic_code_search` | query, directory? | ✅ ONNX embeddings |
| `reindex_workspace` | directory? | ✅ |
| `get_index_stats` | — | ✅ |

### Multi-Agent (2 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `spawn_subagent` | task, context? | ✅ |
| `collect_results` | agent_ids | ✅ |

### Git Context (6 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `git_status` | path? | ✅ |
| `git_diff` | path?, staged? | ✅ |
| `git_log` | path?, count? | ✅ |
| `git_branches` | path? | ✅ |
| `smart_commit` | path?, message? | ✅ AI-generated |
| `amend_commit` | path?, message? | ✅ |

### Project (2 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `project_snapshot` | path? | ✅ |
| `save_session_summary` | summary | ✅ |

### Analytics / Status (4 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `tool_analytics` | topN? | ✅ |
| `get_isolation_status` | — | ✅ |
| `get_mcp_usage` | — | ✅ Requires WHMCS |
| `get_error_summary` | — | ✅ |

### Code Review (1 tool)
| Tool | Parameters | Status |
|------|-----------|--------|
| `code_review` | diff, context? | ✅ AI-powered |

### Database Tools (5 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `db_list` | — | ✅ |
| `db_schema` | database, table? | ✅ |
| `db_query` | database, query | ✅ |
| `db_stats` | database | ✅ |
| `db_backup` | database | ✅ |

### Dependency Audit (1 tool)
| Tool | Parameters | Status |
|------|-----------|--------|
| `dependency_audit` | path?, type? | ✅ |

### File Watcher (1 tool)
| Tool | Parameters | Status |
|------|-----------|--------|
| `toggle_auto_index` | enabled | ✅ |

### Terminal Session (3 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `terminal_session_status` | — | ✅ |
| `terminal_history` | last? | ✅ |
| `terminal_reset` | — | ✅ |

### Tool Documentation (3 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `search_tools` | query | ✅ |
| `get_tool_docs` | format?, category? | ✅ |
| `get_tool_doc` | tool_name | ✅ |

### Media Generation (3 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `generate_video` | prompt, domain, model?, duration?, image_url?, filename? | ✅ Together.ai async |
| `generate_audio` | text, domain, model?, voice?, filename? | ✅ TTS |
| `vision_analyze` | prompt, image, model? | ✅ Qwen3-VL |

### Media Processing (3 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `process_video` | input, action, output, options? | ✅ FFmpeg |
| `process_image` | input, action, output?, options? | ✅ ImageMagick |
| `download_media` | url, output_dir, format?, audio_only?, metadata_only? | ✅ yt-dlp |

### SQL / PHP (2 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `execute_sql` | database, query, format? | ✅ |
| `switch_php_version` | version, domain? | ✅ |

### Voice (1 tool)
| Tool | Parameters | Status |
|------|-----------|--------|
| `voice_status` | — | ✅ |

### AI Models (1 tool)
| Tool | Parameters | Status |
|------|-----------|--------|
| `list_ai_models` | category? | ✅ |

### RAG Pipeline (4 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `rag_ingest` | source, collection, chunkStrategy?, chunkSize? | ✅ |
| `rag_query` | query, collection, topK?, generateAnswer? | ✅ |
| `rag_list_collections` | — | ✅ |
| `rag_delete` | collection, source? | ✅ |

### Code Interpreter (3 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `run_code` | code, language?, sessionId? | ✅ Sandboxed |
| `list_interpreter_sessions` | — | ✅ |
| `kill_interpreter_session` | sessionId | ✅ |

### Browser Agent (6 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `browse_web` | url, waitFor?, extractLinks? | ✅ Playwright |
| `screenshot_page` | url, fullPage?, selector? | ✅ Returns image |
| `click_element` | url, selector | ✅ |
| `fill_form` | url, fields, submitSelector? | ✅ |
| `extract_data` | url, selectors?, mode? | ✅ |
| `web_search` | query, maxResults? | ✅ |

### MCP Client Gateway (4 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `mcp_connect` | name, command?, args?, env?, url?, transport? | ✅ |
| `mcp_disconnect` | name | ✅ |
| `mcp_list_servers` | — | ✅ |
| `mcp_call_tool` | server, tool, arguments? | ✅ |

### Workflows (4 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `workflow_create` | template?, name?, nodes?, connections? | ✅ n8n bridge |
| `workflow_execute` | workflowId, data? | ✅ |
| `workflow_list` | — | ✅ |
| `workflow_status` | workflowId | ✅ |

### Monitoring (3 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `enable_monitoring` | enabled, autoFix? | ✅ |
| `alert_history` | severity?, limit? | ✅ |
| `auto_fix_config` | action?, settings? | ✅ |

### A2A Protocol (4 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `a2a_discover` | url | ⚠️ Dynamic import from openclaw |
| `a2a_send_task` | url, message, skill? | ⚠️ Dynamic import |
| `a2a_list_tasks` | state? | ⚠️ Dynamic import |
| `a2a_publish_card` | — | ⚠️ Dynamic import |

### Artifacts (4 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `create_chart` | type, labels, datasets, title?, width?, height? | ✅ |
| `create_diagram` | code, theme? | ✅ Mermaid |
| `preview_html` | html, title?, tailwind?, alpine? | ✅ |
| `list_artifacts` | — | ✅ |

### Voice Rooms (3 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `voice_room_create` | name, maxParticipants? | ⚠️ Requires LiveKit |
| `voice_room_join` | room, identity | ⚠️ Requires LiveKit |
| `voice_room_list` | — | ⚠️ Requires LiveKit |

### Local LLM / Ollama (4 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `local_llm_chat` | messages, model?, temperature? | ⚠️ Requires Ollama |
| `local_llm_list` | — | ⚠️ Requires Ollama |
| `local_llm_pull` | model | ⚠️ Requires Ollama |
| `local_llm_route` | messages, preference?, analyzeOnly? | ✅ |

### E-Commerce (8 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `create_online_store` | domain, description, platform?, products? | 🟡 Stub (AI scaffolds text only) |
| `add_product` | domain, name, price, description?, category?, sku? | 🟡 Stub (returns text) |
| `setup_payment_gateway` | domain, gateway, api_key, secret, currency?, test_mode? | 🟡 Stub |
| `generate_invoice` | business_name, client_name, items, tax_rate?, currency? | ✅ Real HTML + email |
| `setup_recurring_billing` | domain, product, price, interval, trial_days? | 🟡 Stub |
| `get_revenue_analytics` | domain, period? | 🟡 Stub |
| `setup_shipping` | domain, zones | 🟡 Stub |
| `create_checkout_page` | domain, title, price, description?, success_url? | 🟡 Stub |

### SEO (7 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `seo_audit` | domain, depth? | ✅ Real HTTP checks |
| `generate_sitemap` | domain, max_pages?, exclude? | ✅ Generates + writes |
| `generate_robots_txt` | domain, disallow?, sitemap_url? | ✅ Generates + writes |
| `setup_google_analytics` | domain, measurement_id | ✅ Returns snippet |
| `setup_search_console` | domain, verification_code, submit_sitemap? | ✅ Writes verification file |
| `generate_social_cards` | domain, site_name?, default_image? | ✅ Returns meta tags |
| `keyword_research` | keywords, market?, intent? | ✅ AI-powered |

### Communication (6 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `send_sms` | to, message | 🟡 Stub (no SMS gateway) |
| `send_push_notification` | title, body, url?, icon? | 🟡 Stub |
| `create_contact_form` | domain, email_to, fields?, spam_protect? | ✅ Generates HTML form |
| `setup_live_chat` | domain, provider, widget_id, color?, position? | ✅ Returns embed code |
| `create_newsletter` | domain, list_name, from_email | 🟡 Stub |
| `schedule_email_campaign` | domain, subject, body, send_at? | 🟡 Stub |

### DevOps (7 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `setup_ci_cd` | domain, repo_url?, branch?, build_cmd?, type? | ✅ Writes GitHub Actions YAML |
| `create_staging_site` | domain, staging_subdomain?, include_database? | ⚠️ Partial (creates subdomain only) |
| `promote_staging` | domain, staging_subdomain?, backup_first? | ✅ Real rsync + backup |
| `setup_docker` | path?, services?, node_version?, php_version? | ✅ Writes Dockerfile + compose |
| `run_tests` | path?, framework?, filter?, coverage? | ✅ Auto-detects framework |
| `performance_benchmark` | url, concurrency?, total_requests? | ✅ Apache Bench |
| `setup_webhook` | domain, path, direction, events?, target_url?, secret? | 🟡 Partial (returns config only) |

### Design (6 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `generate_logo` | business_name, description, style?, colors? | ✅ Uses image gen |
| `generate_favicon` | domain, source?, color?, install? | 🟡 Returns HTML only |
| `generate_color_palette` | description, image_path?, count?, format? | ✅ AI-powered |
| `create_landing_page` | domain, title, description, sections?, style? | ✅ AI generates + writes HTML |
| `optimize_images` | path, quality?, max_width?, to_webp?, recursive? | ✅ ImageMagick + cwebp |
| `generate_css_theme` | description, colors?, framework?, dark_mode? | ✅ AI generates + writes |

### Authentication (5 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `setup_auth` | domain, type?, language?, features? | 🟡 AI generates code (not deployed) |
| `create_user_table` | database, table_name?, extra_columns? | ✅ Executes CREATE TABLE |
| `generate_api_keys` | domain, database, rate_limit? | 🟡 Stub |
| `setup_oauth` | domain, providers, client_ids?, callback_path? | 🟡 Stub |
| `setup_2fa` | domain, app_name?, backup_codes? | 🟡 Stub |

### Data Integration (6 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `import_csv` | file_path, database, table, delimiter?, column_map? | ✅ Real SQL inserts |
| `export_data` | database, query, format?, output_path?, limit? | ✅ Real query + export |
| `connect_api` | url, method?, headers?, body?, language? | ✅ Real HTTP request |
| `setup_cors` | domain, origins, methods?, headers?, max_age? | ✅ Writes .htaccess |
| `create_rest_api` | database, tables?, language?, auth?, prefix? | 🟡 Stub (returns description) |
| `migrate_site` | source_url, target_domain, source_type? | 🟡 Stub |

### Content Generation (5 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `generate_blog_post` | domain, topic, keywords?, tone?, word_count? | ✅ AI generates + writes |
| `generate_product_description` | product_name, features, target_audience? | ✅ AI generates |
| `translate_content` | target_lang, file_path?, text?, output_path? | ✅ AI translates |
| `generate_legal_pages` | domain, business_name, pages?, country? | ✅ AI generates + deploys |
| `generate_readme` | path, project_name?, sections? | ✅ AI generates + writes |

### Accessibility (4 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `accessibility_audit` | domain, level?, pages? | ✅ AI-powered |
| `cookie_consent_setup` | domain, style?, position?, color? | ✅ Returns HTML banner |
| `gdpr_audit` | domain, framework? | ✅ AI-powered |
| `ada_fix` | domain, fix_types?, dry_run? | 🟡 Stub |

### Customer Success (6 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `get_customer_journey` | client_id? | ✅ Real WHMCS data |
| `calculate_churn_risk` | client_id? | ✅ Simple algorithm |
| `suggest_upsell` | client_id? | ✅ AI-powered |
| `get_satisfaction_score` | client_id? | ✅ Ticket analysis |
| `create_onboarding_checklist` | client_id?, goal? | ✅ AI-generated |
| `send_nps_survey` | client_id?, template? | 🟡 Stub |

### Project Intelligence (5 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `detect_framework` | path? | ✅ File-based detection |
| `project_health_report` | domain, include? | ✅ AI-powered |
| `estimate_complexity` | path, language?, detail? | ✅ File counting |
| `suggest_improvements` | file_path, focus?, max_suggestions? | ✅ AI-powered |
| `generate_documentation` | path, format?, output?, include? | ✅ AI + file scan |

### Scheduling & Automation (4 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `setup_uptime_monitor` | url, interval?, alert_email?, expected_code? | ✅ Real cron + script |
| `create_maintenance_window` | domain, end_at, start_at?, message? | ✅ Writes 503 page |
| `auto_backup_schedule` | domain, frequency?, retention?, time? | ✅ Real cron + script |
| `dead_link_scan` | domain, max_pages?, check_external?, check_images? | ✅ Real crawler |

### Conduit Engine (13 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `conduit_register_api` | name, base_url, auth_type?, auth_value? | ✅ |
| `conduit_list_apis` | — | ✅ |
| `conduit_call_api` | api_name, method?, path?, body?, query? | ✅ |
| `conduit_remove_api` | name | ✅ |
| `conduit_create_webhook` | name, secret?, events? | ✅ |
| `conduit_list_webhooks` | — | ✅ |
| `conduit_test_webhook` | name, payload? | ✅ |
| `conduit_delete_webhook` | name | ✅ |
| `conduit_create_pipeline` | name, steps | ✅ |
| `conduit_list_pipelines` | — | ✅ |
| `conduit_run_pipeline` | name, input? | ✅ |
| `conduit_delete_pipeline` | name | ✅ |
| `conduit_get_logs` | api_name?, status?, limit? | ✅ |

### Architect Engine (9 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `architect_env_list` | show_values? | ✅ |
| `architect_env_get` | key | ✅ |
| `architect_env_set` | key, value | ✅ |
| `architect_scaffold` | template, name, target_dir?, options? | ✅ |
| `architect_create_deployment` | name, type, build_command?, deploy_steps? | ✅ |
| `architect_list_deployments` | — | ✅ |
| `architect_run_deployment` | name, dry_run? | ✅ |
| `architect_analyze` | target_dir?, depth? | ✅ |
| `architect_resources` | — | ✅ |

### Sentinel Engine (10 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `sentinel_create_baseline` | directory, name, exclude? | ✅ |
| `sentinel_check_integrity` | name | ✅ |
| `sentinel_analyze_access_logs` | log_file?, last_lines? | ✅ |
| `sentinel_vuln_scan` | directory?, scan_type? | ✅ |
| `sentinel_check_ip` | ip | ✅ |
| `sentinel_log_incident` | title, severity, description?, affected? | ✅ |
| `sentinel_list_incidents` | severity?, status? | ✅ |
| `sentinel_resolve_incident` | incident_id, resolution | ✅ |
| `sentinel_set_policy` | name, type, rule, action? | ✅ |
| `sentinel_list_policies` | — | ✅ |

### Forge Engine (7 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `forge_generate_crud` | model_name, fields, framework?, database? | ✅ |
| `forge_generate_component` | name, framework?, props?, features? | ✅ |
| `forge_generate_tests` | target, framework?, style?, code? | ✅ |
| `forge_analyze_code` | file_path, metrics? | ✅ |
| `forge_save_snippet` | name, language, code, description?, tags? | ✅ |
| `forge_list_snippets` | language?, tag?, search? | ✅ |
| `forge_get_snippet` | name | ✅ |

### Chronicle Engine (11 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `chronicle_log_event` | category, action, details?, metadata?, severity? | ✅ |
| `chronicle_query_events` | category?, severity?, since?, until?, search? | ✅ |
| `chronicle_verify_integrity` | — | ✅ |
| `chronicle_track_activity` | activity_type, target, details? | ✅ |
| `chronicle_activity_summary` | period? | ✅ |
| `chronicle_record_change` | file_path, change_type, before?, after?, reason? | ✅ |
| `chronicle_change_history` | file_path, limit? | ✅ |
| `chronicle_start_session` | name, description?, tags? | ✅ |
| `chronicle_end_session` | session_id, summary? | ✅ |
| `chronicle_list_sessions` | status? | ✅ |
| `chronicle_compliance_report` | since?, until?, format? | ✅ |

### Nexus Engine (11 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `nexus_add_entity` | name, type, properties?, tags? | ✅ |
| `nexus_add_relation` | from, to, relation, weight?, metadata? | ✅ |
| `nexus_remove_entity` | name | ✅ |
| `nexus_query` | type?, pattern?, tag?, related_to? | ✅ |
| `nexus_neighbors` | name, relation?, depth? | ✅ |
| `nexus_impact_analysis` | name, depth? | ✅ |
| `nexus_discover_dependencies` | directory?, language? | ✅ |
| `nexus_stats` | — | ✅ |
| `nexus_add_knowledge` | title, content, category?, related?, tags? | ✅ |
| `nexus_search_knowledge` | query, category? | ✅ |
| `nexus_list_knowledge` | category?, limit? | ✅ |

### Cortex Engine (15 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `cortex_decompose` | description, title?, subtasks? | ✅ |
| `cortex_update_step` | plan_id, step_id, status?, notes? | ✅ |
| `cortex_get_plan` | plan_id | ✅ |
| `cortex_list_plans` | — | ✅ |
| `cortex_set_goal` | title, description?, category?, priority?, deadline? | ✅ |
| `cortex_update_goal` | goal_id, current?, status?, notes? | ✅ |
| `cortex_list_goals` | category? | ✅ |
| `cortex_analyze_decision` | question, context?, options | ✅ |
| `cortex_record_decision` | decision_id, chosen, reasoning? | ✅ |
| `cortex_list_decisions` | status? | ✅ |
| `cortex_add_reasoning` | type, content, chain_id?, topic?, evidence? | ✅ |
| `cortex_get_reasoning` | chain_id | ✅ |
| `cortex_list_reasoning` | — | ✅ |
| `cortex_score_priority` | items | ✅ |
| `cortex_context` | — | ✅ |

### Empathy Engine (11 tools) — delegated directly to engine
### Muse Engine (10 tools) — delegated directly to engine
### Prism Engine (9 tools) — delegated directly to engine
### Tempo Engine (9 tools) — delegated directly to engine
### Echo Engine (9 tools) — delegated directly to engine
### Pulse Engine (9 tools) — delegated directly to engine
### Sage Engine (10 tools) — delegated directly to engine

### Autopilot (4 tools)
| Tool | Parameters | Status |
|------|-----------|--------|
| `autopilot_start` | task, url? | ✅ Playwright live session |
| `autopilot_action` | action, url?, selector?, text?, key?, etc. | ✅ |
| `autopilot_observe` | — | ✅ Accessibility tree |
| `autopilot_stop` | reason? | ✅ |

---

## 6. Dependency Analysis

### package.json Dependencies (19)

| Package | Version | Purpose | Risk |
|---------|---------|---------|------|
| `@anthropic-ai/sdk` | ^0.52.0 | Anthropic Claude API | Low |
| `@modelcontextprotocol/sdk` | ^1.0.4 | MCP server SDK | Low |
| `@xenova/transformers` | ^2.17.2 | ONNX embedding model | Medium — large package |
| `axios` | ^1.8.4 | HTTP client | Low |
| `docx` | ^9.5.0 | Word document generation | Low |
| `dotenv` | ^16.5.0 | Environment variables | Low |
| `express` | ^5.2.1 | HTTP server | ⚠️ Express 5 is RC, not GA |
| `form-data` | ^4.0.2 | Multipart form uploads | Low |
| `ioredis` | ^5.6.1 | Redis client | Low |
| `jsonwebtoken` | ^9.0.2 | JWT authentication | Low |
| `node-cron` | ^3.0.3 | Scheduled tasks | Low |
| `nodemailer` | ^6.10.1 | Email sending | Low |
| `pdf-parse` | ^1.1.1 | PDF text extraction | Low |
| `pdfkit` | ^0.16.0 | PDF generation | Low |
| `playwright` | ^1.52.0 | Browser automation | Medium — requires browser binaries |
| `ws` | ^8.18.2 | WebSocket server | Low |
| `zod` | ^3.25.67 | Input validation | Low |
| `together-ai` | ^0.15.0 | Together.ai SDK | Low — but code uses axios directly instead |
| `sharp` | ^0.34.2 | Image processing | Medium — native binary |

### Missing / Implied Dependencies

| Import | Used In | Notes |
|--------|---------|-------|
| `crypto` | mcpHttpServer, toolDispatch | Node.js built-in — OK |
| `child_process` | shellExec, sessionManager | Node.js built-in — OK |
| `together-ai` | Listed in package.json | ⚠️ **Never imported** — code uses `togetherClient.js` with raw axios. Dead dependency. |

---

## 7. Security Assessment

### ✅ Strengths

1. **Path Traversal Protection** — All file paths validated via `safePath()` in daClient.js + `validatePath()` in sandbox.js. Double-checked with `path.resolve()` against homeDir.
2. **Command Injection Prevention** — 28 blocked command patterns in sandbox.js. Commands run via `execSync` with restricted PATH.
3. **Cross-User Isolation** — Each MCP session is scoped to a single DA username. Path checks prevent accessing other users' home directories.
4. **Rate Limiting** — 120 tool calls/minute per user, 5 concurrent commands max.
5. **Financial Safety** — All WHMCS purchase/payment operations require `confirmed: true` (double-call pattern).
6. **Token Metering** — Per-tool cost tracking via Redis. Soft overage (10%) with account blocking for unpaid.
7. **Circuit Breakers** — Automatic tool disabling after 5 consecutive failures. 60s cooldown.
8. **Error Sanitization** — Stack traces, paths, and credentials stripped from user-facing errors.
9. **JWT Authentication** — HTTP transport requires valid JWT with `daUsername` claim.
10. **Auto-Checkpoint** — Destructive operations auto-create checkpoint before executing.

### ⚠️ Concerns

1. **Token Gate Fails Open** — If Redis is down, all tool calls are allowed without billing (tokenGate.js line 137). This is by design for availability but could lead to unbilled usage.

2. **`setup_webhook` uses `require('crypto')`** — Uses CommonJS `require()` in an ESM module (toolDispatch.js ~line 3183). This may throw at runtime depending on Node.js version.

3. **Autopilot URL Blocking** — Blocks `localhost`, `127.0.0.1`, `10.x`, `172.16-31.x`, `192.168.x`, metadata endpoints. Good but missing `[::ffff:` IPv4-mapped IPv6 addresses.

4. **Shell Execution** — While `shellExec` locks cwd to homeDir and has blocked patterns, the `run_terminal_command` tool uses `execInSession` which spawns a real bash process. The command filtering in sandbox.js is pattern-based (regex) and could potentially be bypassed with creative encoding.

5. **SQL Injection Surface** — `import_csv` tool constructs SQL directly from CSV content (toolDispatch.js ~lines 3370-3395). Values are escaped with simple `replace(/'/g, "''")` which does NOT protect against all injection vectors (e.g., backslash escapes).

6. **Missing CORS on HTTP Server** — `mcpHttpServer.js` doesn't set CORS headers on the `/mcp` endpoint. This is correct for server-to-server, but the artifact and Telnyx endpoints might need CORS.

7. **Sendmail Transport** — `send_email` uses local sendmail binary (nodemailer createTransport path: '/usr/sbin/sendmail'). No SPF/DKIM validation. Could be used for spam if not rate-limited.

---

## 8. Bug Report

### 🔴 Critical Bugs

**BUG-1: `find_file` tool uses text search, not glob search**
- **Location:** toolDispatch.js, `find_file` case
- **Issue:** The tool description says "Find files matching a glob pattern" but the implementation calls `daClient.searchFiles(pattern)` which does regex text search on file contents/names, not glob matching.
- **Impact:** Users expecting glob behavior (`*.js`) will get unexpected results.
- **Fix:** Implement actual glob matching or update the tool description.

**BUG-2: `require('crypto')` in ESM module**
- **Location:** toolDispatch.js line ~3183, `setup_webhook` case
- **Issue:** Uses `require('crypto')` which is CommonJS syntax in an ESM (`"type": "module"`) package. This will throw `ReferenceError: require is not defined`.
- **Fix:** Replace with `import { randomBytes } from 'crypto'` or use dynamic import.

**BUG-3: SQL injection in `import_csv`**
- **Location:** toolDispatch.js lines ~3370-3395
- **Issue:** CSV values are escaped with only `replace(/'/g, "''")`. MySQL with `NO_BACKSLASH_ESCAPES` disabled allows backslash-escaped quotes (`\'`) to bypass this.
- **Fix:** Use parameterized queries or proper MySQL escaping.

### 🟡 Medium Bugs

**BUG-4: `rename_file` only works for text files**
- **Location:** daClient.js lines 141-145
- **Issue:** Comment says "only works for text files; binary files would need a different approach" — the implementation reads content as text via DA edit API, writes to new path, deletes old. Binary files (images, PDFs) will be corrupted.
- **Fix:** Use DA's file manager binary download/upload for rename.

**BUG-5: Version mismatch**
- **Location:** package.json says `1.0.0`; index.js banner says `v4.1.0`; mcpHttpServer.js `buildMcpServer` says `6.0.0`; health endpoint says `6.0.0`
- **Impact:** Confusing version reporting. No single source of truth.
- **Fix:** Centralize version in package.json and import it.

**BUG-6: `daUsername` variable shadowing in toolDispatch.js**
- **Location:** toolDispatch.js — multiple engine case blocks (conduit, architect, sentinel, forge, chronicle, nexus, cortex)
- **Issue:** These blocks declare `const daUsername = daClient.targetUsername || 'default'` which shadows the `daUsername` parameter passed to `dispatchTool()`. While not currently broken (they resolve to the same value), this creates a maintenance hazard.
- **Fix:** Remove the redundant declarations and use the function parameter.

**BUG-7: `homeDir` variable shadowing in architect cases**
- **Location:** toolDispatch.js architect_env_list, architect_env_get, architect_env_set, architect_scaffold, architect_run_deployment
- **Issue:** These cases declare `const homeDir = daClient.homeDir || ...` which shadows the `homeDir` const defined at the top of the function (line 67).
- **Fix:** Use a different variable name or the top-level `homeDir`.

**BUG-8: `seo_audit` backslash escaping in template literals**
- **Location:** toolDispatch.js ~line 2960
- **Issue:** Uses `\\n` inside a template literal string for join separator, which produces a literal `\n` in the output instead of a newline.
- **Fix:** Use `\n` (single backslash) in template literals.

### 🟢 Low Bugs

**BUG-9: `generate_video` timing may be undefined**
- **Location:** toolDispatch.js `generate_video` case
- **Issue:** If `result.timing` is undefined (API failure path), `(result.timing / 1000).toFixed(1)` will produce `"NaN"`.

**BUG-10: Stale timer in sessionManager**
- **Location:** terminal/sessionManager.js
- **Issue:** If `getSession()` is called with a different `homeDir` than the existing session, the homeDir is not updated. This could happen if a user is moved between servers.

**BUG-11: Missing error handling in `dead_link_scan`**
- **Location:** toolDispatch.js `dead_link_scan` case
- **Issue:** The HEAD request fallback `resp.text()` reads the body after a HEAD request (which has no body). While this works in practice (returns empty string), it's semantically incorrect.

---

## 9. Dead Code & Incomplete Implementations

### Dead Dependencies
- **`together-ai`** package (listed in package.json) — Never imported anywhere. The codebase uses `togetherClient.js` which calls the Together.ai API via raw axios.

### Stub / Placeholder Tools (~25 tools)
The following Phase 27 tools return hardcoded success messages without performing real operations:

| Tool | Nature of Stub |
|------|---------------|
| `create_online_store` | AI generates text description only |
| `add_product` | Returns confirmation text, no actual store integration |
| `setup_payment_gateway` | Returns mock configuration text |
| `setup_recurring_billing` | Returns mock configuration text |
| `get_revenue_analytics` | Returns placeholder text |
| `setup_shipping` | Returns mock configuration text |
| `create_checkout_page` | Returns mock configuration text |
| `send_sms` | Returns "requires SMS gateway configuration" |
| `send_push_notification` | Returns mock text |
| `create_newsletter` | Returns mock endpoints |
| `schedule_email_campaign` | Returns mock text |
| `setup_webhook` (partial) | Returns config text but doesn't create webhook handler |
| `generate_api_keys` | Returns mock descriptions |
| `setup_oauth` | Returns mock route descriptions |
| `setup_2fa` | Returns mock endpoint descriptions |
| `create_rest_api` | Returns mock API descriptions |
| `migrate_site` | Returns "in progress" text |
| `send_nps_survey` | Returns "queued" text |
| `ada_fix` | Returns mock fix descriptions |
| `generate_favicon` | Returns HTML only, doesn't generate actual files |
| `create_staging_site` | Creates subdomain only, doesn't clone files/DB |

### Unused but Well-Implemented Files
- No truly dead code files found. All 77 source files are imported somewhere in the chain.

---

## 10. Performance Concerns

1. **Giant Switch Statement** — `_dispatchToolInner()` is a single switch with 218+ cases across 5,000 lines. This is O(n) lookup in V8 (which optimizes large switches to jump tables). Not a real performance issue but makes maintenance difficult.

2. **Synchronous File I/O in `shellExec`** — Uses `execSync` and `execFileSync`. This blocks the Node.js event loop during shell execution (up to 120s). The `sessionManager.js` correctly uses async `spawn()`.

3. **Sequential Domain Availability Checks** — `whmcsClient.searchDomains()` checks domains sequentially (one RDAP lookup at a time). Could be parallelized with `Promise.allSettled()`.

4. **Heavy Dependency Loading** — `@xenova/transformers` (ONNX runtime) loads a ~100MB model on first use. The `warmupEmbeddings()` at startup mitigates cold-start but adds to boot time.

5. **In-Memory State** — Error log (200 entries), rate limits, circuit breakers, terminal sessions, and MCP sessions are all in-memory Maps. Server restart loses all state. Consider Redis for persistence.

6. **`dead_link_scan` Timeout Risk** — The crawler checks up to 50 pages sequentially with 10s timeout each. Worst case: 500s (8+ minutes) for a single tool call. Add an overall timeout.

---

## 11. Recommendations

### Priority 1 — Fix Critical Bugs
1. Fix `require('crypto')` in ESM (BUG-2) — will crash at runtime
2. Fix SQL injection in `import_csv` (BUG-3) — use parameterized queries
3. Fix `find_file` semantics (BUG-1) — match description to implementation

### Priority 2 — Security Hardening
4. Add IPv4-mapped IPv6 (`::ffff:127.0.0.1`) to Autopilot URL blocklist
5. Add proper MySQL escaping or parameterized queries for all SQL construction
6. Consider fail-closed option for token gate (or at least logging) when Redis is down

### Priority 3 — Code Quality
7. Remove dead `together-ai` dependency from package.json
8. Centralize version number (read from package.json)
9. Fix variable shadowing in engine dispatch blocks
10. Fix `\\n` → `\n` in `seo_audit` template literals (BUG-8)

### Priority 4 — Architecture
11. Consider splitting `toolDispatch.js` (5,084 lines) into per-category dispatch files
12. Consider splitting `tools.js` (6,073 lines) into per-category definition files
13. Move stub tools to a separate "roadmap" category so users know they're placeholders
14. Add integration tests for the middleware pipeline

### Priority 5 — Documentation
15. Add JSDoc to all exported functions
16. Document required environment variables in README
17. Document the 5-layer middleware pipeline
18. Create an API reference for the HTTP endpoints

---

## Appendix A: Environment Variables

| Variable | Required | Default | Used By |
|----------|----------|---------|---------|
| `GOCODEME_DA_USERNAME` | Yes (stdio) | — | index.js |
| `DA_HOST` | Yes | `https://localhost:2222` | daClient.js |
| `DA_ADMIN_USER` | Yes | `admin` | daClient.js |
| `DA_ADMIN_PASS` | Yes | — | daClient.js |
| `JWT_SECRET` | Yes (HTTP) | `changeme` | mcpHttpServer.js |
| `MCP_PORT` | No | `3005` | mcpHttpServer.js |
| `TOGETHER_API_KEY` | Yes (AI tools) | — | togetherClient.js |
| `WHMCS_API_URL` | No | — | whmcsClient.js |
| `WHMCS_API_IDENTIFIER` | No | — | whmcsClient.js |
| `WHMCS_API_SECRET` | No | — | whmcsClient.js |
| `VOICE_PORT` | No | `3006` | voiceServer.js |
| `TELNYX_API_KEY` | No | — | telnyxCalls.js |
| `TELNYX_FROM_NUMBER` | No | — | telnyxCalls.js |
| `LIVEKIT_URL` | No | — | livekitService.js |
| `LIVEKIT_API_KEY` | No | — | livekitService.js |
| `LIVEKIT_API_SECRET` | No | — | livekitService.js |
| `OLLAMA_URL` | No | `http://localhost:11434` | ollamaClient.js |

---

*End of audit report. No code was modified.*
