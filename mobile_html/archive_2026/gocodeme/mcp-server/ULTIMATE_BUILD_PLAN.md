# Alfred AI — Ultimate Agent Platform Build Plan v6.0

> **Current**: v5.0.0 | 141 tools | 18 categories  
> **Target**: v6.0.0 | ~180 tools | 24 categories  
> **Server**: Intel Xeon 12-core, 31GB RAM, 3.2TB disk, NO GPU  
> **Date**: March 2026

---

## Architecture Overview

```
┌────────────────────────────────────────────────────────────────┐
│                    GoCodeMe Alfred v6.0                        │
│                   ~180 Tools · 24 Categories                   │
├──────────┬──────────┬──────────┬──────────┬──────────┬────────┤
│  Phase 1 │ Phase 2  │ Phase 3  │ Phase 4  │ Phase 5  │Phase 6 │
│Knowledge │ Connect  │ Automate │ Protocol │  Rich    │Local AI│
│& Execute │  Out     │          │          │ Output   │        │
├──────────┼──────────┼──────────┼──────────┼──────────┼────────┤
│RAG       │Browser   │n8n       │A2A       │Artifacts │Ollama  │
│Pipeline  │Agent     │ Bridge   │Protocol  │Server    │Gateway │
│          │          │          │          │          │        │
│Code      │MCP Client│Proactive │          │LiveKit   │        │
│Interpret │Gateway   │Agent     │          │Voice     │        │
├──────────┴──────────┴──────────┴──────────┴──────────┴────────┤
│               Existing v5.0 Foundation                         │
│  ELEPHANT Memory · CLOCKWORK Scheduler · ORACLE Search         │
│  PLAYBOOK Workflows · HIVEMIND Multi-Agent                     │
│  Together.ai (80+ models) · Voice WSS · Media Processing       │
├───────────────────────────────────────────────────────────────┤
│  Express :3005 · Redis :6379 · OpenClaw :3004 · TTS :5002     │
│  Middleware :3001 · Voice :3006 · Icecast :8000                │
└───────────────────────────────────────────────────────────────┘
```

---

## Phase 1: Knowledge & Execution (Foundation)

### 1A — RAG Pipeline (`src/rag/`)

**What**: Full Retrieval-Augmented Generation — ingest documents, chunk them,
embed into vector collections, query with semantic search + reranking, then
synthesize answers grounded in your actual documents.

**Files**:
| File | Purpose |
|------|---------|
| `src/rag/documentIngester.js` | PDF (pdf-parse), DOCX (mammoth), Markdown, HTML, plain text ingestion |
| `src/rag/chunker.js` | Recursive character splitting + semantic boundary detection |
| `src/rag/vectorCollection.js` | Named collections on top of existing vectorStore.js |
| `src/rag/ragPipeline.js` | Orchestrator: ingest → chunk → embed → store → query → rerank → answer |

**New Tools** (4):
- `rag_ingest` — Ingest a file/URL/text into a named knowledge collection
- `rag_query` — Query a collection with natural language, get grounded answers
- `rag_list_collections` — List all knowledge collections with stats
- `rag_delete` — Delete a collection or specific documents

**Dependencies**: `pdf-parse`, `mammoth` (npm install)

---

### 1B — Code Interpreter (`src/interpreter/`)

**What**: Jupyter-like code execution environment. Run Python, Node.js, Bash,
Ruby, PHP in isolated sessions. Capture stdout, stderr, images, plots.
Sessions persist across calls so you can build up state.

**Files**:
| File | Purpose |
|------|---------|
| `src/interpreter/codeInterpreter.js` | Multi-language execution engine with output capture |
| `src/interpreter/outputCapture.js` | Captures stdout/stderr/images/plots, converts to base64 |
| `src/interpreter/sessionPool.js` | Per-user execution sessions with 30min timeout + cleanup |

**New Tools** (3):
- `run_code` — Execute code in Python/Node/Bash/Ruby/PHP, get stdout+stderr+images
- `list_interpreter_sessions` — List active sessions with language and uptime
- `kill_interpreter_session` — Kill a specific session

**Dependencies**: None (uses child_process + existing sandbox.js)

---

## Phase 2: External Connectivity

### 2A — Browser Agent (`src/browser/`)

**What**: Headless Chromium via Playwright. Alfred can browse the web, fill forms,
take screenshots, extract structured data, run web searches. Computer Use lite.

**Files**:
| File | Purpose |
|------|---------|
| `src/browser/browserPool.js` | Playwright browser pool (max 3 instances), auto-recycle after 50 pages |
| `src/browser/webAgent.js` | High-level browser actions: navigate, click, type, scroll, screenshot |
| `src/browser/pageAnalyzer.js` | DOM analysis, accessibility tree extraction, content scraping |

**New Tools** (6):
- `browse_web` — Navigate to URL, return page content (text + accessibility tree)
- `screenshot_page` — Full-page or element screenshot → base64
- `click_element` — Click a CSS/XPath selector
- `fill_form` — Fill form fields by selector
- `extract_data` — Extract structured data (tables, lists, links) from a page
- `web_search` — DuckDuckGo/Google search, return top results

**Dependencies**: `playwright` (npm install), Chromium binary (npx playwright install chromium)

**RAM Impact**: ~150MB per browser instance (max 3 = 450MB)

---

### 2B — MCP Client Gateway (`src/mcpClient/`)

**What**: Alfred becomes an MCP **client** that can connect to external MCP servers
(GitHub, Slack, Linear, Notion, filesystem, databases). Auto-discovers remote
tools and makes them callable as `mcp_<server>_<tool>`.

**Files**:
| File | Purpose |
|------|---------|
| `src/mcpClient/mcpClientManager.js` | Manages stdio/SSE/StreamableHTTP connections to remote MCP servers |
| `src/mcpClient/toolRouter.js` | Discovers remote tools, namespaces them, proxies calls |
| `src/mcpClient/serverRegistry.js` | Built-in registry of popular MCP servers (GitHub, Slack, etc.) |

**New Tools** (4):
- `mcp_connect` — Connect to an external MCP server (stdio, SSE, or HTTP)
- `mcp_disconnect` — Disconnect from a server
- `mcp_list_servers` — List connected servers and their tools
- `mcp_call_tool` — Call any tool on a connected server

**Dependencies**: `@modelcontextprotocol/sdk` (already installed — use Client class)

---

## Phase 3: Automation & Proactive Intelligence

### 3A — n8n Workflow Automation (`src/workflows/`)

**What**: Install n8n (the open-source Zapier), connect it to Alfred. Create
visual automation workflows that trigger on events, process data, call APIs.
Alfred can create and execute n8n workflows programmatically.

**Files**:
| File | Purpose |
|------|---------|
| `src/workflows/n8nBridge.js` | n8n REST API client (create, execute, list, status workflows) |
| `src/workflows/workflowTemplates.js` | Pre-built templates: deploy notification, backup, health check, etc. |

**New Tools** (4):
- `workflow_create` — Create an n8n workflow from template or JSON definition
- `workflow_execute` — Trigger a workflow execution
- `workflow_list` — List all workflows with status
- `workflow_status` — Get execution history and status

**Infrastructure**:
- Install: `npm install -g n8n`
- PM2 process: `n8n start` on port 5678
- Env: `N8N_PORT=5678`, `N8N_BASIC_AUTH_ACTIVE=true`

---

### 3B — Proactive Agent (`src/proactive/`)

**What**: Autonomous monitoring loop that watches your project 24/7. Detects
issues before they become problems — disk space, memory leaks, broken builds,
security vulnerabilities, performance regression. Can auto-fix common issues.

**Files**:
| File | Purpose |
|------|---------|
| `src/proactive/monitoringAgent.js` | Main monitoring loop (runs every 60s via CLOCKWORK) |
| `src/proactive/alertEngine.js` | Pattern detection, anomaly scoring, threshold alerts |
| `src/proactive/autoFixer.js` | Auto-remediation recipes (restart crashed services, clear cache, etc.) |
| `src/proactive/contextBuilder.js` | Builds proactive suggestions from project analysis |

**New Tools** (3):
- `enable_monitoring` — Start/stop proactive monitoring for a project
- `alert_history` — View past alerts and actions taken
- `auto_fix_config` — Configure auto-fix rules (what to auto-remediate vs alert)

**Integrates with**: CLOCKWORK Scheduler, ELEPHANT Memory, existing `siteHealth.js`

---

## Phase 4: Agent-to-Agent Protocol

### 4A — A2A Protocol (`openclaw/src/a2a/`)

**What**: Implement Google's Agent-to-Agent Protocol (A2A). Alfred publishes an
Agent Card at `/.well-known/agent.json` describing its capabilities. Other AI
agents can discover Alfred and send it tasks over HTTP. Alfred can also discover
and delegate to remote A2A agents.

**Files**:
| File | Purpose |
|------|---------|
| `openclaw/src/a2a/a2aServer.js` | A2A HTTP server (task lifecycle: submitted → working → completed) |
| `openclaw/src/a2a/a2aClient.js` | Connect to and call remote A2A agents |
| `openclaw/src/a2a/agentCard.js` | Agent Card generator (capabilities, skills, auth) |
| `openclaw/src/a2a/taskManager.js` | Task state machine + Redis persistence |

**New Tools** (4):
- `a2a_publish_card` — Publish/update Alfred's Agent Card
- `a2a_send_task` — Send a task to a remote A2A agent
- `a2a_list_tasks` — List active/completed A2A tasks
- `a2a_discover` — Discover A2A agents at a given URL

**Endpoints added to OpenClaw**:
- `GET /.well-known/agent.json` — Agent Card
- `POST /a2a/tasks/send` — Receive task from another agent
- `GET /a2a/tasks/:id` — Task status
- `POST /a2a/tasks/:id/cancel` — Cancel task

---

## Phase 5: Rich Output & Real-Time

### 5A — Multi-Modal Artifacts (`src/artifacts/`)

**What**: Instead of just text responses, Alfred can produce rich artifacts:
interactive HTML previews, Chart.js charts, Mermaid diagrams → SVG,
code playgrounds, and downloadable files. Artifacts are served from a
light HTTP endpoint and can be opened in the IDE's preview panel.

**Files**:
| File | Purpose |
|------|---------|
| `src/artifacts/artifactServer.js` | Express subrouter serving artifacts at /artifacts/:id |
| `src/artifacts/chartGenerator.js` | Chart.js server-side rendering (bar, line, pie, etc.) |
| `src/artifacts/diagramRenderer.js` | Mermaid CLI → SVG/PNG diagram rendering |
| `src/artifacts/htmlPreview.js` | Live HTML preview with code injection |

**New Tools** (4):
- `create_chart` — Generate a Chart.js chart, return artifact URL + base64
- `create_diagram` — Generate Mermaid diagram → SVG, return artifact URL
- `preview_html` — Create live HTML preview, return artifact URL
- `list_artifacts` — List all generated artifacts for current session

**Dependencies**: `@mermaid-js/mermaid-cli`, `chartjs-node-canvas`, `canvas`

---

### 5B — LiveKit Voice Upgrade (`src/voice/`)

**What**: Replace the basic WebSocket voice server with LiveKit-based real-time
voice rooms. Multi-participant, noise suppression, voice activity detection.
Alfred can join a room, listen, and respond — like a real team member.

**Files**:
| File | Purpose |
|------|---------|
| `src/voice/livekitService.js` | LiveKit server SDK — room management, token generation |
| `src/voice/voiceAgent.js` | AI voice agent that joins rooms, listens, transcribes, and responds |

**New Tools** (3):
- `voice_room_create` — Create a named voice room with settings
- `voice_room_join` — Have Alfred join a room as a voice participant
- `voice_room_list` — List active rooms and participants

**Dependencies**: `livekit-server-sdk`, LiveKit server binary (self-hosted)

**Port**: 7880 (LiveKit), 7881 (LiveKit TCP)

---

## Phase 6: Local AI

### 6A — Ollama Gateway (`src/localLlm/`)

**What**: Install Ollama for running local/private LLMs. No GPU = CPU-only, but
small models (Phi-3, Qwen2.5-0.5B, TinyLlama) run fine on 12-core Xeon.
Privacy-sensitive queries stay on-box. Hybrid router sends tasks to local vs
cloud based on complexity.

**Files**:
| File | Purpose |
|------|---------|
| `src/localLlm/ollamaClient.js` | Ollama REST API client (chat, generate, embeddings) |
| `src/localLlm/modelManager.js` | Pull, list, remove, inspect models |
| `src/localLlm/hybridRouter.js` | Routes requests: small/private → Ollama, complex → Claude/Together |

**New Tools** (4):
- `local_llm_chat` — Chat with a local Ollama model
- `local_llm_list` — List installed local models
- `local_llm_pull` — Pull/download a model from Ollama registry
- `local_llm_route` — Auto-route a query to best model (local vs cloud)

**Infrastructure**:
- Install: `curl -fsSL https://ollama.com/install.sh | sh`
- PM2 process: `ollama serve` on port 11434
- Starter models: `qwen2.5:0.5b` (394MB), `phi3:mini` (2.3GB)

---

## Build Order & Dependencies

```
Phase 1A (RAG Pipeline)          ──┐
Phase 1B (Code Interpreter)      ──┼── No external deps, extend existing
                                   │
Phase 2A (Browser Agent)         ──┤   Needs: playwright install
Phase 2B (MCP Client Gateway)   ──┤   Needs: MCP SDK (already have it)
                                   │
Phase 3A (n8n Integration)       ──┤   Needs: n8n install + PM2
Phase 3B (Proactive Agent)       ──┤   Extends: CLOCKWORK + siteHealth
                                   │
Phase 4A (A2A Protocol)          ──┤   Extends: OpenClaw
                                   │
Phase 5A (Artifacts System)      ──┤   Needs: mermaid-cli, chartjs-canvas
Phase 5B (LiveKit Voice)         ──┤   Needs: livekit-server binary
                                   │
Phase 6A (Ollama Gateway)        ──┘   Needs: ollama install
```

**Independence**: Phases 1-3 are fully independent of each other.
Phase 4 benefits from Phase 2B (MCP Client). Phase 5A is independent.

---

## Tool Summary

| Phase | Module | New Tools | Running Total |
|-------|--------|-----------|---------------|
| — | Existing v5.0 | 141 | 141 |
| 1A | RAG Pipeline | 4 | 145 |
| 1B | Code Interpreter | 3 | 148 |
| 2A | Browser Agent | 6 | 154 |
| 2B | MCP Client Gateway | 4 | 158 |
| 3A | n8n Workflows | 4 | 162 |
| 3B | Proactive Agent | 3 | 165 |
| 4 | A2A Protocol | 4 | 169 |
| 5A | Artifacts | 4 | 173 |
| 5B | LiveKit Voice | 3 | 176 |
| 6 | Ollama Gateway | 4 | **180** |

**Final**: 180 tools · 24 categories · v6.0.0

---

## New File Manifest

```
mcp-server/
├── src/
│   ├── rag/
│   │   ├── documentIngester.js    ← PDF, DOCX, MD, HTML, TXT parser
│   │   ├── chunker.js             ← Recursive + semantic chunking
│   │   ├── vectorCollection.js    ← Named collections over vectorStore
│   │   └── ragPipeline.js         ← Full RAG orchestration
│   ├── interpreter/
│   │   ├── codeInterpreter.js     ← Multi-language execution engine
│   │   ├── outputCapture.js       ← stdout/stderr/image capture
│   │   └── sessionPool.js         ← Per-user session lifecycle
│   ├── browser/
│   │   ├── browserPool.js         ← Playwright browser instance pool
│   │   ├── webAgent.js            ← High-level browser actions
│   │   └── pageAnalyzer.js        ← DOM + content extraction
│   ├── mcpClient/
│   │   ├── mcpClientManager.js    ← Outbound MCP connections
│   │   ├── toolRouter.js          ← Remote tool discovery + namespace
│   │   └── serverRegistry.js      ← Built-in server directory
│   ├── workflows/
│   │   ├── n8nBridge.js           ← n8n REST API client
│   │   └── workflowTemplates.js   ← Pre-built workflow templates
│   ├── proactive/
│   │   ├── monitoringAgent.js     ← Continuous monitoring loop
│   │   ├── alertEngine.js         ← Anomaly detection + alerting
│   │   ├── autoFixer.js           ← Auto-remediation recipes
│   │   └── contextBuilder.js      ← Proactive suggestion builder
│   ├── artifacts/
│   │   ├── artifactServer.js      ← HTTP artifact serving
│   │   ├── chartGenerator.js      ← Chart.js server-side rendering
│   │   ├── diagramRenderer.js     ← Mermaid → SVG
│   │   └── htmlPreview.js         ← Live HTML preview
│   ├── localLlm/
│   │   ├── ollamaClient.js        ← Ollama REST API client
│   │   ├── modelManager.js        ← Model lifecycle management
│   │   └── hybridRouter.js        ← Local vs cloud routing
│   └── voice/
│       ├── livekitService.js      ← LiveKit room management
│       └── voiceAgent.js          ← AI voice room participant

openclaw/
└── src/
    └── a2a/
        ├── a2aServer.js           ← A2A HTTP endpoints
        ├── a2aClient.js           ← Outbound A2A calls
        ├── agentCard.js           ← Agent Card generator
        └── taskManager.js         ← Task state machine

Total new files: 27
```

---

## Resource Budget

| Resource | Current | After v6.0 | Headroom |
|----------|---------|------------|----------|
| RAM | 14GB used | ~18GB | ~13GB free |
| Disk | 217GB used | ~220GB | ~3.3TB free |
| Ports | 6 active | 9 active | plenty |
| PM2 Procs | 8 | 11 | no limit |
| CPU Cores | 12 | 12 | shared |

**New services**:
- n8n → port 5678, ~200MB RAM
- LiveKit → port 7880, ~100MB RAM  
- Ollama → port 11434, ~2GB RAM (with qwen2.5:0.5b loaded)
- Browser (Playwright) → on-demand, ~150MB per instance

---

## Let's Build 🚀
