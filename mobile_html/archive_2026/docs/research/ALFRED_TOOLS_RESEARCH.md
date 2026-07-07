# Alfred AI — External Tools & Integration Research Report

**Date:** July 2026  
**Current State:** 1,290+ tools, 807 MCP tools, 25+ named agents, Fleet Orchestration  
**Goal:** Identify, evaluate, and map external tools across 12 categories for Alfred enhancement  
**Focus:** Self-hostable, open-source options preferred. For each: name, what it does, self-hosted option, pricing, integration complexity, and which Alfred agent team would use it.

---

## Table of Contents

1. [Workflow Automation & Orchestration](#1-workflow-automation--orchestration)
2. [API Integration Platforms](#2-api-integration-platforms)
3. [Web Scraping & Browser Automation](#3-web-scraping--browser-automation)
4. [Document Processing & Parsing](#4-document-processing--parsing)
5. [Calendar & Scheduling](#5-calendar--scheduling)
6. [Project Management](#6-project-management)
7. [CRM (Customer Relationship Management)](#7-crm-customer-relationship-management)
8. [Knowledge Base & Wiki](#8-knowledge-base--wiki)
9. [Forms & Surveys](#9-forms--surveys)
10. [E-Commerce Platforms](#10-e-commerce-platforms)
11. [Social Media APIs](#11-social-media-apis)
12. [Maps & Location Services](#12-maps--location-services)
13. [Priority Integration Matrix](#13-priority-integration-matrix)
14. [Recommended Phase Plan](#14-recommended-phase-plan)

---

## 1. Workflow Automation & Orchestration

> **Alfred Gap:** Alfred has 1,290+ tools but lacks workflow automation — chaining tools together into multi-step processes. n8n, Make, Zapier, IFTTT are scaffolded but NOT implemented.

### n8n ⭐ RECOMMENDED
| Field | Details |
|-------|---------|
| **What it does** | Visual workflow automation platform with drag-and-drop editor. 500+ native integrations. Supports both no-code UI and custom JavaScript/Python code nodes. AI workflow automation with LLM chains, AI agents, vector stores. |
| **Self-hosted** | ✅ Yes — Docker, npm, or Kubernetes. Full self-hosted with no feature restrictions. |
| **Open Source** | ✅ Yes — Fair-code license (Sustainable Use License + n8n Enterprise License). 177.9k GitHub stars. |
| **Pricing** | Self-hosted: Free (community). Cloud: Starter €24/mo, Pro €60/mo, Enterprise custom. |
| **Integration Complexity** | 🟢 Low-Medium — REST API, webhook triggers, Docker deploy alongside Alfred. Can call Alfred's MCP server tools as HTTP endpoints. |
| **Alfred Agent Team** | **ARCHITECT** (infrastructure), **CATALYST** (automation), **NEXUS** (orchestration) |
| **Key Features** | SSO/SAML/LDAP, 500+ integrations, AI agent workflows, credential management, error handling with retry, sub-workflows |
| **Why for Alfred** | Direct replacement for Alfred's missing workflow chaining. Alfred agents could trigger n8n workflows, and n8n could orchestrate multi-tool Alfred sequences. |

### Temporal
| Field | Details |
|-------|---------|
| **What it does** | Durable execution platform for building reliable distributed systems. Workflows survive process crashes, server restarts, and network failures. Used by NVIDIA, Salesforce, Twilio, OpenAI. |
| **Self-hosted** | ✅ Yes — MIT-licensed, deploy via Docker/Kubernetes. |
| **Open Source** | ✅ Yes — MIT License. 18.7k GitHub stars. |
| **Pricing** | Self-hosted: Free. Cloud: $1,000 free credits, then usage-based (~$25/mo for 1M actions). |
| **Integration Complexity** | 🟡 Medium — SDKs for Python, Go, TypeScript, Ruby, C#, Java, PHP. Requires understanding of workflow/activity patterns. |
| **Alfred Agent Team** | **ARCHITECT** (infrastructure), **FLUX** (state management), **NEXUS** (orchestration) |
| **Key Features** | Durable execution, automatic retries, workflow versioning, visibility/debugging tools, timer/cron workflows, signal/query support |
| **Why for Alfred** | Ideal for Alfred's fleet orchestration — long-running multi-agent tasks that must survive failures. PHP SDK available. |

### Inngest
| Field | Details |
|-------|---------|
| **What it does** | Durable workflow engine using `step.run()` primitives. "Write functions, not infrastructure." Serverless-compatible with auto-retries and state recovery. |
| **Self-hosted** | ✅ Yes — open-source server available. |
| **Open Source** | ✅ Yes — Apache 2.0 License. |
| **Pricing** | Free tier available. Pro from $50/mo. Enterprise custom. SOC 2 / HIPAA certified. |
| **Integration Complexity** | 🟢 Low — TypeScript/JavaScript SDK with event-driven architecture. HTTP-based event ingestion. |
| **Alfred Agent Team** | **CATALYST** (automation), **ARCHITECT** (infrastructure) |
| **Key Features** | Step functions, fan-out/fan-in, concurrency controls, event replay, cron scheduling, throttling |
| **Why for Alfred** | Lightweight alternative to Temporal. Good for Alfred's Node.js MCP server to add durable step-by-step tool execution. |

### Pipedream
| Field | Details |
|-------|---------|
| **What it does** | Integration platform with 3,000+ app connectors and 10,000+ pre-built tools. Recently acquired by Workday. Built-in MCP server support for AI agents. |
| **Self-hosted** | ❌ No — Cloud-only SaaS. |
| **Open Source** | Partial — Component registry is open-source on GitHub. |
| **Pricing** | Free tier (100 daily invocations). Plus $29/mo. Business $59/mo. Enterprise custom. |
| **Integration Complexity** | 🟢 Low — REST API, pre-built connectors, MCP server for AI tools. |
| **Alfred Agent Team** | **CATALYST** (automation), **HERALD** (communications), **NEXUS** (orchestration) |
| **Key Features** | 3,000+ app connectors, MCP servers, AI agent builder, SOC 2 Type II / HIPAA / GDPR, workflow builder |
| **Why for Alfred** | Fastest path to 3,000+ app integrations via their MCP server. Alfred agents could call Pipedream MCP tools directly. |

### Trigger.dev
| Field | Details |
|-------|---------|
| **What it does** | Open-source background job framework for TypeScript. Long-running tasks (up to 24h+), AI/ML workloads, event-driven processing. |
| **Self-hosted** | ✅ Yes — Docker self-host available. |
| **Open Source** | ✅ Yes — Apache 2.0 License. ~17k GitHub stars. |
| **Pricing** | Free tier (30 runs/day). Hobby $30/mo. Pro $200/mo. Enterprise custom. |
| **Integration Complexity** | 🟡 Medium — TypeScript SDK, requires Node.js runtime. |
| **Alfred Agent Team** | **ARCHITECT** (infrastructure), **CATALYST** (automation) |
| **Key Features** | Long-running tasks, retry/resume, concurrency management, real-time logs, AI task support |
| **Why for Alfred** | Perfect for Alfred's long-running agent tasks. Background processing for fleet operations, AI generation, bulk operations. |

---

## 2. API Integration Platforms

> **Alfred Gap:** Alfred integrates with APIs one-by-one. Unified API platforms provide 100-700+ integrations through a single API.

### Unified.to ⭐ RECOMMENDED
| Field | Details |
|-------|---------|
| **What it does** | Unified API platform with 413 integrations across 25 categories (CRM, ATS, Ticketing, Accounting, E-commerce, Messaging, Storage, etc). Real-time pass-through with zero data storage. |
| **Self-hosted** | ❌ No — Cloud API. |
| **Open Source** | Partial — Client SDKs are open-source (TypeScript, Python, Go, Ruby, C#, Java, PHP). |
| **Pricing** | Usage-based. Free tier available. Growth from $250/mo. Enterprise custom. SOC 2 / GDPR / HIPAA / CCPA / PIPEDA. |
| **Integration Complexity** | 🟢 Low — Single unified REST API. PHP SDK available. MCP server support. |
| **Alfred Agent Team** | **NEXUS** (orchestration), **MAVEN** (data), **PRISM** (integration) |
| **Key Features** | 413 integrations, 25 API categories, zero-storage pass-through, webhook events, unified data models, MCP support |
| **Why for Alfred** | Instantly gives Alfred access to 413+ third-party apps through ONE API. Direct PHP SDK. Would revolutionize Alfred's integration capabilities. |

### Nango
| Field | Details |
|-------|---------|
| **What it does** | Code-first integration platform. Manages OAuth flows, token refresh, rate limiting, and provides pre-built integration scripts for 700+ APIs. |
| **Self-hosted** | ✅ Yes — Enterprise plan includes self-hosting. |
| **Open Source** | ✅ Yes — MIT License. 7k GitHub stars. |
| **Pricing** | Free tier. Starter $50/mo. Growth $500/mo. Enterprise (self-hosted) custom. |
| **Integration Complexity** | 🟡 Medium — TypeScript integration scripts, REST API, webhooks. |
| **Alfred Agent Team** | **ARCHITECT** (infrastructure), **PRISM** (integration), **NEXUS** (orchestration) |
| **Key Features** | 700+ API pre-built integrations, OAuth management, rate limiting, webhook syncing, real-time data, MCP support |
| **Why for Alfred** | Solves Alfred's OAuth/token management problem. Pre-built API connectors with automatic token refresh. |

### Merge.dev
| Field | Details |
|-------|---------|
| **What it does** | Unified API for 6 categories: Accounting, ATS, CRM, File Storage, HR/Payroll, Ticketing. Plus "Agent Handler" for AI agent integration. Powers Mistral, Perplexity, Ramp. |
| **Self-hosted** | ❌ No — Cloud API only. |
| **Open Source** | ❌ No — Proprietary. Client SDKs available. |
| **Pricing** | Launch: Free (up to 3 linked accounts). Professional from $650/mo. Enterprise custom. SOC 2 Type II / ISO 27001 / HIPAA / GDPR. |
| **Integration Complexity** | 🟢 Low — Unified REST API with standardized data models. |
| **Alfred Agent Team** | **NEXUS** (orchestration), **MAVEN** (data) |
| **Key Features** | Unified API, AI Agent Handler, webhook notifications, common data models, comprehensive logging |
| **Why for Alfred** | Their "Agent Handler" is specifically designed for AI agents to interact with 200+ SaaS tools. Natural fit for Alfred's agent architecture. |

### Paragon
| Field | Details |
|-------|---------|
| **What it does** | Integration platform with 130+ native connectors. Includes RAG ingestion pipeline, AI agent tool calling, and workflow orchestration. Forward-deploy/self-host options. |
| **Self-hosted** | ✅ Yes — "Forward deploy" option for enterprise. |
| **Open Source** | ❌ No — Proprietary with self-host option. |
| **Pricing** | Custom pricing. Free trial available. |
| **Integration Complexity** | 🟡 Medium — REST API, embedded integration UIs, webhook triggers. |
| **Alfred Agent Team** | **NEXUS** (orchestration), **CURATOR** (knowledge), **PRISM** (integration) |
| **Key Features** | 130+ connectors, RAG ingestion, AI agent tool calling, workflow engine, embedded UI for end-users, self-host option |
| **Why for Alfred** | RAG ingestion pipeline could feed Alfred's knowledge base. AI agent tool calling maps directly to Alfred's agent architecture. Used by CrewAI, Copy.ai. |

---

## 3. Web Scraping & Browser Automation

> **Alfred Gap:** Alfred's SCOUT agent does research but lacks dedicated scraping infrastructure. No browser automation for JS-rendered pages.

### Crawlee (by Apify) ⭐ RECOMMENDED
| Field | Details |
|-------|---------|
| **What it does** | Web scraping and browser automation library. Supports Cheerio (HTTP), Playwright, and Puppeteer crawlers. Automatic anti-blocking, proxy rotation, session management. |
| **Self-hosted** | ✅ Yes — Run anywhere (Node.js, Docker). |
| **Open Source** | ✅ Yes — Apache 2.0 License. 22k+ GitHub stars. JavaScript + Python libraries. |
| **Pricing** | Free (forever open-source). Apify cloud platform for scaling: Free tier, Personal $49/mo, Team $499/mo. |
| **Integration Complexity** | 🟢 Low — npm install, JavaScript/TypeScript API. Can integrate with Alfred's Node.js MCP server directly. |
| **Alfred Agent Team** | **SCOUT** (research/scraping), **CURATOR** (knowledge gathering) |
| **Key Features** | HTTP + browser crawling, auto-retry, proxy rotation, queue management, configurable concurrency, JSON/CSV/Excel export |
| **Why for Alfred** | Runs directly in Alfred's Node.js environment. SCOUT agent gets reliable web scraping with JS rendering support. |

### Firecrawl ⭐ RECOMMENDED
| Field | Details |
|-------|---------|
| **What it does** | Converts entire websites into LLM-ready markdown or structured data. Handles JS rendering, anti-bot, sitemaps. Outputs clean markdown perfect for AI consumption. |
| **Self-hosted** | ✅ Yes — Docker self-host available. |
| **Open Source** | ✅ Yes — AGPL-3.0. 88.9k GitHub stars (massive community). |
| **Pricing** | Free: 500 credits. Hobby: $22/mo (3k credits). Standard: $113/mo (100k credits). Growth: $452/mo (500k credits). Scale: $813/mo. SOC 2 Type 2. |
| **Integration Complexity** | 🟢 Low — REST API, JavaScript/Python SDKs. Single API call to crawl + convert. |
| **Alfred Agent Team** | **SCOUT** (research), **CURATOR** (content extraction), **SAGE** (analysis) |
| **Key Features** | Crawl entire sites, extract to markdown/JSON, LLM-ready output, batch operations, webhooks, screenshot capture |
| **Why for Alfred** | Converts any website to markdown that Alfred's AI models can directly consume. Perfect for RAG, research, competitive analysis. |

### Browserless
| Field | Details |
|-------|---------|
| **What it does** | Browser-as-a-Service. Run Puppeteer/Playwright in the cloud. BrowserQL for anti-detection. Handles CAPTCHAs, bot detection, and scales on demand. |
| **Self-hosted** | ✅ Yes — Docker self-host for enterprise. |
| **Open Source** | Partial — Some components open-source. Enterprise features proprietary. |
| **Pricing** | Usage-based. Plans from $200/mo. Enterprise with self-hosted option. |
| **Integration Complexity** | 🟢 Low — Drop-in replacement for Puppeteer/Playwright endpoint URL. |
| **Alfred Agent Team** | **SCOUT** (web automation), **ARCHITECT** (infrastructure) |
| **Key Features** | BrowserQL anti-detection, Puppeteer/Playwright compatible, CAPTCHA solving, session management, screenshot/PDF APIs |
| **Why for Alfred** | Provides Alfred with reliable browser automation for web tasks that require JavaScript rendering or authentication. |

### Jina Reader
| Field | Details |
|-------|---------|
| **What it does** | Converts any URL to LLM-ready text. Prefix any URL with `r.jina.ai/` to get clean markdown. Also provides `s.jina.ai/` for search queries. |
| **Self-hosted** | ❌ No — Cloud API. |
| **Open Source** | ❌ No — Free API with rate limits. |
| **Pricing** | Free tier with rate limits. Paid plans available. |
| **Integration Complexity** | 🟢 Very Low — Single HTTP GET request. No SDK needed. |
| **Alfred Agent Team** | **SCOUT** (research), **CURATOR** (content extraction) |
| **Key Features** | URL to markdown, search to markdown, no JavaScript needed, handles anti-bot, clean output |
| **Why for Alfred** | Simplest possible web-to-text conversion. One HTTP call. Perfect for quick URL content extraction by any agent. |

---

## 4. Document Processing & Parsing

> **Alfred Gap:** No document parsing capability. Cannot process PDFs, DOCX, PPTX, spreadsheets, or images into structured data.

### Docling (by IBM) ⭐ RECOMMENDED
| Field | Details |
|-------|---------|
| **What it does** | Document processing library that parses PDF, DOCX, PPTX, XLSX, HTML, images, audio (WAV/MP3), LaTeX, WebVTT, and XBRL. Advanced PDF understanding including layout analysis, table extraction, OCR, formulas, code blocks. Unified DoclingDocument format. |
| **Self-hosted** | ✅ Yes — pip install, runs locally. |
| **Open Source** | ✅ Yes — MIT License. 55.1k GitHub stars. LF AI & Data Foundation project. IBM-backed. |
| **Pricing** | Free (MIT license). No usage limits. |
| **Integration Complexity** | 🟡 Medium — Python library. Would need a Python microservice or integration via MCP tool. |
| **Alfred Agent Team** | **CURATOR** (document processing), **SAGE** (analysis), **MAVEN** (data extraction) |
| **Key Features** | Multi-format parsing, page layout detection, table extraction, OCR, formula recognition, image classification, MCP server included, integrations with LangChain/LlamaIndex/CrewAI/Haystack |
| **Why for Alfred** | Most comprehensive open-source document processor. Has its own MCP server — could be added directly to Alfred's MCP tool collection. MIT licensed. IBM-backed stability. |

### Marker (by Datalab)
| Field | Details |
|-------|---------|
| **What it does** | Converts documents to markdown, JSON, chunks, and HTML. Supports PDF, images, PPTX, DOCX, XLSX, HTML, EPUB. Benchmarks favorably vs. LlamaParse and Mathpix. Hybrid mode with LLMs for highest accuracy. |
| **Self-hosted** | ✅ Yes — pip install, runs on GPU/CPU/MPS. Self-hosted API server included. |
| **Open Source** | ✅ Yes — GPL-3.0 (code), custom license for models ($2M revenue threshold for free use). 32.2k GitHub stars. |
| **Pricing** | Free (open-source, see model license). Hosted API from Datalab: 1/4 price of competitors. On-prem licensing available. |
| **Integration Complexity** | 🟡 Medium — Python library. Built-in FastAPI server for API access. Can use Gemini/Ollama/Claude/OpenAI for LLM mode. |
| **Alfred Agent Team** | **CURATOR** (document parsing), **SAGE** (analysis) |
| **Key Features** | PDF→markdown (primary strength), table/form extraction, LaTeX equations, structured JSON extraction (beta), multi-GPU support, 25 pages/sec on H100, LLM hybrid mode with multiple providers |
| **Why for Alfred** | Best-in-class PDF-to-markdown conversion. Can self-host the FastAPI server. Supports Alfred's existing AI providers (OpenAI, Anthropic, Ollama). Output directly consumable by Alfred's AI models. |

### Unstructured
| Field | Details |
|-------|---------|
| **What it does** | Enterprise document processing ETL platform. Transforms 64+ file types into clean, structured data. Partners with Databricks, Snowflake, IBM, NVIDIA, MongoDB, Pinecone. |
| **Self-hosted** | Partial — Open-source library available, enterprise platform is SaaS. |
| **Open Source** | ✅ Yes — Apache 2.0 (core library). Enterprise SaaS platform is proprietary. |
| **Pricing** | Open-source library: Free. SaaS platform: Custom enterprise pricing. CB Insights Top 100 AI. Forbes Top 50 AI. |
| **Integration Complexity** | 🟢 Low (library) / 🟡 Medium (platform) — Python library or REST API. 30+ source/destination connectors. |
| **Alfred Agent Team** | **CURATOR** (document processing), **MAVEN** (data pipelines), **ARCHITECT** (infrastructure) |
| **Key Features** | 64+ file types, chunking/embedding/enrichment, 30+ connectors, 1,250+ pipelines, 24/7 pipeline monitoring, UI + API interface |
| **Why for Alfred** | Best for building document processing pipelines that feed into Alfred's knowledge base. Open-source core can be self-hosted. |

### Apache Tika
| Field | Details |
|-------|---------|
| **What it does** | Content extraction toolkit. Detects and extracts metadata and text from 1,000+ file types (PDF, DOC, PPT, XLS, etc). Apache Software Foundation project. |
| **Self-hosted** | ✅ Yes — Java application, Docker available. |
| **Open Source** | ✅ Yes — Apache 2.0 License. Mature project (since 2007). |
| **Pricing** | Free. |
| **Integration Complexity** | 🟢 Low — REST API via Tika Server. Docker deployment. |
| **Alfred Agent Team** | **CURATOR** (document processing), **MAVEN** (metadata extraction) |
| **Key Features** | 1,000+ file types, metadata extraction, language detection, MIME type detection, OCR integration (Tesseract), REST server |
| **Why for Alfred** | Broadest file format support. Simple REST API. Perfect as a fallback/general-purpose parser behind Docling or Marker. |

### LlamaParse (by LlamaIndex)
| Field | Details |
|-------|---------|
| **What it does** | AI-powered document parser optimized for RAG. Converts complex documents into RAG-ready chunks. Part of the LlamaIndex ecosystem. |
| **Self-hosted** | ❌ No — Cloud API only. |
| **Open Source** | ❌ No — Proprietary API (LlamaIndex framework is open-source). |
| **Pricing** | Free: 1,000 pages/day. Starter: $3.50/1k pages. Enterprise: Custom. |
| **Integration Complexity** | 🟢 Low — REST API, Python SDK. |
| **Alfred Agent Team** | **CURATOR** (document processing), **SAGE** (RAG) |
| **Key Features** | AI-powered parsing, RAG-optimized output, table/chart extraction, multi-modal support |
| **Why for Alfred** | Best for RAG-specific use cases. But cloud-only and less flexible than Docling/Marker. |

---

## 5. Calendar & Scheduling

> **Alfred Gap:** No calendar management. Cannot book meetings, check availability, or manage scheduling for users.

### Cal.com ⭐ RECOMMENDED
| Field | Details |
|-------|---------|
| **What it does** | Scheduling infrastructure. Booking pages, availability management, team scheduling, round-robin, collective events, payments via Stripe, built-in video (Cal Video), 65+ languages. AI phone agent (Cal.ai). |
| **Self-hosted** | ✅ Yes — full self-hosted via Docker/GitHub. |
| **Open Source** | ✅ Yes — AGPL-3.0 License. 39k+ GitHub stars. |
| **Pricing** | Individuals: Free (unlimited). Teams: $12/user/mo. Organizations: $37/user/mo. Enterprise: Custom. |
| **Integration Complexity** | 🟡 Medium — REST API (v2), embeddable widgets, React components (Cal Atoms), webhooks, OAuth. |
| **Alfred Agent Team** | **PIERRE** (scheduling/concierge), **HERALD** (communications), **SOFIA** (customer service) |
| **Key Features** | ISO 27001, SOC 2, CCPA, GDPR, HIPAA compliant. Routing forms, workflows, round-robin, Cal.ai phone agent, embeddable, 65+ languages, Zapier/webhook integration |
| **Why for Alfred** | Perfect scheduling backbone for Alfred. Self-hosted, open-source, with an AI phone agent that complements Alfred's VAPI voice tools. Enterprise API for white-label. |

### Calendly
| Field | Details |
|-------|---------|
| **What it does** | Market-leading scheduling platform. Simple booking links, team scheduling, routing, analytics. |
| **Self-hosted** | ❌ No — Cloud only. |
| **Open Source** | ❌ No — Proprietary. |
| **Pricing** | Free (basic). Standard: $10/user/mo. Teams: $16/user/mo. Enterprise: Custom. |
| **Integration Complexity** | 🟢 Low — REST API v2, webhooks, embeddable widgets. |
| **Alfred Agent Team** | **PIERRE** (scheduling), **HERALD** (communications) |
| **Key Features** | Simple UX, routing forms, team pages, analytics, CRM integrations (Salesforce, HubSpot), Zoom/Teams/Google Meet |
| **Why for Alfred** | Easiest to integrate but no self-hosting. Good if Alfred users already use Calendly. API is well-documented. |

### Google Calendar API
| Field | Details |
|-------|---------|
| **What it does** | Direct access to Google Calendar. Create/read/update/delete events, check free/busy, manage calendars. |
| **Self-hosted** | ❌ No — Google Cloud service. |
| **Open Source** | N/A — Google API with client libraries. |
| **Pricing** | Free (with Google Cloud quotas). |
| **Integration Complexity** | 🟡 Medium — OAuth 2.0, Google API client libraries. PHP client available. |
| **Alfred Agent Team** | **PIERRE** (scheduling), **ATLAS** (productivity) |
| **Key Features** | Full calendar CRUD, free/busy queries, recurring events, attendee management, push notifications |
| **Why for Alfred** | Most users have Google Calendar. Essential integration for any scheduling capability. |

### Microsoft Graph (Outlook Calendar)
| Field | Details |
|-------|---------|
| **What it does** | Access to Outlook/Microsoft 365 calendars, email, contacts, OneDrive via unified Microsoft Graph API. |
| **Self-hosted** | ❌ No — Microsoft Cloud service. |
| **Open Source** | N/A — Microsoft API with SDKs. |
| **Pricing** | Free (with Microsoft 365 subscription). |
| **Integration Complexity** | 🟡 Medium — OAuth 2.0 with Azure AD. PHP SDK available. |
| **Alfred Agent Team** | **PIERRE** (scheduling), **ATLAS** (productivity), **HERALD** (email) |
| **Key Features** | Calendar CRUD, meeting scheduling, email, contacts, OneDrive files, Teams presence |
| **Why for Alfred** | Essential for enterprise users on Microsoft 365. One API covers calendar + email + files + Teams. |

---

## 6. Project Management

> **Alfred Gap:** No project management integration. Cannot create/track tasks, manage sprints, or connect to existing PM tools.

### Linear ⭐ RECOMMENDED
| Field | Details |
|-------|---------|
| **What it does** | Modern product development system for teams and AI agents. Issue tracking, project management, cycles, roadmaps, AI agent integration (works with Cursor, Codex, GitHub Copilot). Built-in MCP server. |
| **Self-hosted** | ❌ No — Cloud only. |
| **Open Source** | ❌ No — Proprietary. |
| **Pricing** | Free (up to 100 members). Standard: $8/user/mo. Plus: $14/user/mo. Enterprise: Custom. |
| **Integration Complexity** | 🟢 Low — GraphQL API, webhooks, MCP server, Slack/GitHub/GitLab integrations. |
| **Alfred Agent Team** | **ATLAS** (productivity), **ARCHITECT** (development), **VANGUARD** (project leadership) |
| **Key Features** | AI-native (built for human+agent workflows), MCP server, GraphQL API, Slack integration (auto issue creation), cycles, initiatives, insights, dashboards, audio project updates |
| **Why for Alfred** | Built specifically for AI agent workflows. MCP server means Alfred can directly create/manage issues. Used by OpenAI, Ramp, Opendoor. |

### Jira (Atlassian)
| Field | Details |
|-------|---------|
| **What it does** | Enterprise project management. Agile boards, sprints, roadmaps, custom workflows. Industry standard for software development. |
| **Self-hosted** | ✅ Yes — Jira Data Center (on-premise). |
| **Open Source** | ❌ No — Proprietary. |
| **Pricing** | Free (up to 10 users). Standard: $8.15/user/mo. Premium: $16/user/mo. Enterprise: Custom. |
| **Integration Complexity** | 🟡 Medium — REST API v3, webhooks, Atlassian Connect apps. Well-documented but complex. |
| **Alfred Agent Team** | **ATLAS** (productivity), **ARCHITECT** (development) |
| **Key Features** | Agile boards, sprints, custom workflows, automation rules, advanced roadmaps, 3,000+ marketplace apps |
| **Why for Alfred** | Industry standard PM tool. Many Alfred enterprise customers likely use Jira. REST API is comprehensive. |

### Todoist API
| Field | Details |
|-------|---------|
| **What it does** | Task management platform. Simple, powerful task/project management with natural language processing. |
| **Self-hosted** | ❌ No — Cloud only. |
| **Open Source** | ❌ No — Proprietary. Official REST API and Sync API. |
| **Pricing** | Free (5 projects). Pro: $4/mo. Business: $6/user/mo. |
| **Integration Complexity** | 🟢 Low — REST API v2, webhooks, well-documented. Python/JS clients available. |
| **Alfred Agent Team** | **ATLAS** (productivity), **PIERRE** (personal assistant) |
| **Key Features** | Natural language task creation, labels/filters/priorities, Kanban boards, productivity tracking, 80+ integrations |
| **Why for Alfred** | Simplest task management integration. Users can say "add a task" to Alfred and it goes to Todoist. Low barrier. |

### Notion API
| Field | Details |
|-------|---------|
| **What it does** | All-in-one workspace — docs, databases, project management, wikis. Extensive API for reading/writing pages and databases. |
| **Self-hosted** | ❌ No — Cloud only. |
| **Open Source** | ❌ No — Proprietary. |
| **Pricing** | Free (basic). Plus: $10/user/mo. Business: $18/user/mo. Enterprise: Custom. |
| **Integration Complexity** | 🟡 Medium — REST API, OAuth 2.0, block-based content model. |
| **Alfred Agent Team** | **ATLAS** (productivity), **CURATOR** (knowledge management), **SAGE** (research) |
| **Key Features** | Databases, pages, blocks API, search, comments, users, rich text support, file uploads |
| **Why for Alfred** | Many knowledge workers use Notion. Alfred could read/write Notion pages, query databases, manage project boards. |

---

## 7. CRM (Customer Relationship Management)

> **Alfred Gap:** No CRM capabilities. Cannot manage contacts, deals, pipelines, or customer interactions.

### Twenty ⭐ RECOMMENDED
| Field | Details |
|-------|---------|
| **What it does** | Modern open-source CRM. Contacts, companies, deals/pipelines, tasks, notes, email sync, kanban views, keyboard shortcuts, command palette. "Operating system for your customer data." |
| **Self-hosted** | ✅ Yes — Docker self-hosting. |
| **Open Source** | ✅ Yes — AGPL-3.0 License. 40.3k+ GitHub stars. YC-backed (S23). |
| **Pricing** | Self-hosted: Free. Cloud: Free (up to 3 seats). Pro: $9/seat/mo. Enterprise: Custom. |
| **Integration Complexity** | 🟡 Medium — REST API, GraphQL API, webhooks, customizable data model, workflows engine. |
| **Alfred Agent Team** | **SOFIA** (customer relations), **HERALD** (communications), **MAVEN** (data management) |
| **Key Features** | Custom objects/fields, email sync, views/filters, workflows, API & webhooks, dark mode, keyboard-driven, extensible |
| **Why for Alfred** | Best open-source CRM. Self-hostable, modern UX, GraphQL API. Alfred could become the AI layer on top of Twenty for automated CRM management. |

### Attio
| Field | Details |
|-------|---------|
| **What it does** | Next-generation CRM that adapts to your workflows. Real-time data enrichment, relationship intelligence, customizable objects. |
| **Self-hosted** | ❌ No — Cloud only. |
| **Open Source** | ❌ No — Proprietary. |
| **Pricing** | Free (up to 3 seats). Plus: $29/seat/mo. Pro: $59/seat/mo. Enterprise: $119/seat/mo. |
| **Integration Complexity** | 🟢 Low — REST API v2, webhooks, extensible data model. |
| **Alfred Agent Team** | **SOFIA** (customer relations), **MAVEN** (data enrichment) |
| **Key Features** | Dynamic data model, real-time enrichment, email sequences, automations, reporting, API |
| **Why for Alfred** | Modern API-first CRM. Better developer experience than legacy CRMs. Good alternative if self-hosting isn't required. |

### HubSpot API
| Field | Details |
|-------|---------|
| **What it does** | All-in-one CRM platform. Marketing, sales, service, CMS. Industry-leading free CRM tier. |
| **Self-hosted** | ❌ No — Cloud only. |
| **Open Source** | ❌ No — Proprietary. Extensive API documentation. |
| **Pricing** | Free CRM (generous). Starter: $15/mo. Professional: $800/mo. Enterprise: $3,600/mo. |
| **Integration Complexity** | 🟢 Low — REST API v3, PHP client, extensive docs, app marketplace. |
| **Alfred Agent Team** | **SOFIA** (customer relations), **HERALD** (marketing), **MAVEN** (analytics) |
| **Key Features** | Contacts, companies, deals, tickets, email, forms, landing pages, workflows, reporting |
| **Why for Alfred** | Massive market share. Many Alfred customers likely use HubSpot. Free CRM tier is very generous. PHP client available. |

### Salesforce API
| Field | Details |
|-------|---------|
| **What it does** | Enterprise CRM market leader. Contacts, accounts, opportunities, cases, analytics. |
| **Self-hosted** | ❌ No — Cloud only. |
| **Open Source** | ❌ No — Proprietary. |
| **Pricing** | Starter: $25/user/mo. Professional: $80/user/mo. Enterprise: $165/user/mo. |
| **Integration Complexity** | 🔴 High — Complex API (REST + SOAP + Bulk), OAuth 2.0, SOQL query language. |
| **Alfred Agent Team** | **SOFIA** (customer relations), **MAVEN** (analytics) |
| **Key Features** | Industry standard enterprise CRM, AppExchange marketplace, Einstein AI, Flow automation |
| **Why for Alfred** | Enterprise customers expect Salesforce integration. Complex but necessary for enterprise Alfred deployments. |

---

## 8. Knowledge Base & Wiki

> **Alfred Gap:** Alfred has consciousness/learning systems but no dedicated knowledge base solution for team documentation.

### Outline ⭐ RECOMMENDED
| Field | Details |
|-------|---------|
| **What it does** | Team knowledge base. Beautiful editor with markdown, slash commands, real-time collaboration. Integrated with Slack. AI-powered search and question answering. Public sharing. |
| **Self-hosted** | ✅ Yes — Docker self-hosting available. |
| **Open Source** | ✅ Yes — BSL License (source-available). GitHub. |
| **Pricing** | Cloud: Free trial. $10/user/mo. Business: $15/user/mo. Self-hosted: Free. |
| **Integration Complexity** | 🟢 Low — REST API, 20+ integrations (Slack, Figma, Loom etc), custom domains, webhooks. |
| **Alfred Agent Team** | **CURATOR** (knowledge management), **SAGE** (research), **ECHO** (documentation) |
| **Key Features** | Real-time collaboration, markdown editor, AI search, Slack integration, dark mode, 20+ languages, public sharing, custom domains, permissions |
| **Why for Alfred** | Could serve as Alfred's knowledge base backend. Agents write documentation here, users search via Alfred. REST API for full CRUD. |

### BookStack ⭐ RECOMMENDED (for PHP stack)
| Field | Details |
|-------|---------|
| **What it does** | Simple, self-hosted wiki platform. Books → Chapters → Pages hierarchy. WYSIWYG + Markdown editors, built-in diagrams.net, full-text search, OIDC/SAML/LDAP auth. |
| **Self-hosted** | ✅ Yes — PHP/Laravel + MySQL. Runs on minimal hardware. |
| **Open Source** | ✅ Yes — MIT License. |
| **Pricing** | Free. |
| **Integration Complexity** | 🟢 Very Low — PHP/Laravel (same stack as Alfred!), MySQL (same DB), REST API. |
| **Alfred Agent Team** | **CURATOR** (knowledge management), **ECHO** (documentation), **ARCHITECT** (infrastructure) |
| **Key Features** | Books/Chapters/Pages hierarchy, WYSIWYG + Markdown, diagrams.net built-in, search, LDAP/SAML/OIDC auth, MFA, dark mode, API, multi-language |
| **Why for Alfred** | Same tech stack as Alfred (PHP + MySQL). Could literally share the same database server. MIT licensed. Minimal resource usage. Perfect internal knowledge base. |

### Obsidian (via API/plugins)
| Field | Details |
|-------|---------|
| **What it does** | Local-first markdown knowledge management. Graph view of linked notes. 1,000+ community plugins. |
| **Self-hosted** | ✅ Yes — Local files (markdown). |
| **Open Source** | ❌ No — Proprietary app, but data is open markdown files. |
| **Pricing** | Personal: Free. Commercial: $50/user/year. Sync: $4/mo. Publish: $8/mo. |
| **Integration Complexity** | 🟡 Medium — Local REST API plugin, file-based. No native cloud API. |
| **Alfred Agent Team** | **CURATOR** (knowledge management), **SAGE** (research) |
| **Key Features** | Local-first markdown, graph view, backlinks, 1,000+ plugins, canvas, templates |
| **Why for Alfred** | Popular with power users. Alfred could read/write Obsidian vaults via Local REST API plugin. Data is just markdown files. |

---

## 9. Forms & Surveys

> **Alfred Gap:** No form/survey creation or data collection capability.

### Formbricks ⭐ RECOMMENDED
| Field | Details |
|-------|---------|
| **What it does** | Open-source experience management platform. In-app surveys, website surveys, email surveys, link surveys. Pre-segmentation, event triggers, NPS/CSAT/CES. Self-hosted or cloud. |
| **Self-hosted** | ✅ Yes — Docker self-hosting. |
| **Open Source** | ✅ Yes — AGPL-3.0 License. GitHub. |
| **Pricing** | Free (self-hosted). Cloud: Free tier. Scale: Custom pricing. SOC 2 Type II. |
| **Integration Complexity** | 🟢 Low — JavaScript SDK (script tag), REST API, webhooks. React/Vue/Svelte support. |
| **Alfred Agent Team** | **HERALD** (communications/feedback), **SOFIA** (customer experience), **MAVEN** (data) |
| **Key Features** | In-app/website/email/link surveys, no-code targeting, event triggers, NPS/CSAT/CES, partial submissions, GDPR/CCPA compliant, white-label |
| **Why for Alfred** | Perfect for collecting user feedback within Alfred-powered apps. Self-hosted, open-source, privacy-first. Used by Siemens, FlixBus, GitHub, IKEA. |

### SurveyJS
| Field | Details |
|-------|---------|
| **What it does** | JavaScript form/survey library suite. Form Library (renderer), Survey Creator (drag-and-drop builder), Dashboard (analytics), PDF Generator. Embeds directly in your app. |
| **Self-hosted** | ✅ Yes — JavaScript libraries run in your app. All data stays on your server. |
| **Open Source** | ✅ Yes — MIT License (Form Library). Survey Creator/Dashboard/PDF require license. |
| **Pricing** | Form Library: Free (MIT). Survey Creator: One-time $899/dev. Full suite: One-time $1,599/dev. Includes 12mo maintenance. |
| **Integration Complexity** | 🟢 Low — JavaScript/TypeScript, React/Angular/Vue/jQuery. JSON-based form definitions. |
| **Alfred Agent Team** | **HERALD** (communications), **MAVEN** (data collection) |
| **Key Features** | 40+ question types, conditional logic, scoring, offline mode, AI form generation, PDF export, accessibility (WCAG/508), unlimited usage, white-label |
| **Why for Alfred** | One-time purchase (no recurring fees). Alfred could generate SurveyJS JSON schemas via AI and render them for users. Offline-capable. |

### Typeform API
| Field | Details |
|-------|---------|
| **What it does** | Conversational form/survey platform. Beautiful, interactive surveys with high completion rates. |
| **Self-hosted** | ❌ No — Cloud only. |
| **Open Source** | ❌ No — Proprietary. |
| **Pricing** | Free (limited). Basic: $25/mo. Plus: $50/mo. Business: $83/mo. Enterprise: Custom. |
| **Integration Complexity** | 🟢 Low — REST API, webhooks, embeddable. |
| **Alfred Agent Team** | **HERALD** (communications), **SOFIA** (customer experience) |
| **Key Features** | Conversational UI, logic jumps, calculation, file upload, payment, video questions |
| **Why for Alfred** | Best UX for conversational surveys. Could complement Alfred's voice interface with visual surveys. |

### Google Forms API
| Field | Details |
|-------|---------|
| **What it does** | Simple form creation and response collection. Part of Google Workspace. |
| **Self-hosted** | ❌ No — Google Cloud. |
| **Open Source** | N/A |
| **Pricing** | Free (with Google account). |
| **Integration Complexity** | 🟢 Low — Google Forms API, Google Sheets integration. |
| **Alfred Agent Team** | **HERALD** (communications), **ATLAS** (productivity) |
| **Key Features** | Simple forms, auto Google Sheets integration, quizzes, templates |
| **Why for Alfred** | Most accessible option. Zero cost. Responses auto-flow to Google Sheets. |

---

## 10. E-Commerce Platforms

> **Alfred Gap:** Alfred has Stripe integration for payments but no full e-commerce/product management capability.

### Medusa.js ⭐ RECOMMENDED
| Field | Details |
|-------|---------|
| **What it does** | Open-source headless commerce platform. Product management, orders, customers, inventory, multi-region, multi-currency, promotions, sales channels. "Bloom" AI webshop builder. |
| **Self-hosted** | ✅ Yes — Node.js, Docker, self-hosted. |
| **Open Source** | ✅ Yes — MIT License. #1 of 15,000 GitHub commerce projects. 2.5M+ yearly downloads. $10B+ GMV powered. |
| **Pricing** | Self-hosted: Free. Medusa Cloud: Starter ~$45/mo. Growth: Custom. No GMV tax, no licenses. |
| **Integration Complexity** | 🟡 Medium — REST API + JS SDK + Admin API. 100+ community integrations. MCP server + Docs MCP for AI development. |
| **Alfred Agent Team** | **MAVEN** (commerce), **SOFIA** (customer service), **HERALD** (marketing) |
| **Key Features** | Headless API, multi-region/currency, advanced promotions, inventory management, sales channels, AI development tools, 100+ integrations, React admin + starters |
| **Why for Alfred** | Best open-source commerce. Already has MCP server for agentic development. Alfred could manage stores, process orders, handle customer inquiries. Used by Heineken, Mitsubishi. |

### Saleor
| Field | Details |
|-------|---------|
| **What it does** | Open-source headless commerce platform. GraphQL-first. PIM, OMS, promotions, checkout, extensions. "Commerce as Code" approach. Agentic Commerce Protocol for AI checkout (ChatGPT integration). |
| **Self-hosted** | ✅ Yes — Docker, self-hosted. Saleor Cloud also available. |
| **Open Source** | ✅ Yes — BSD-3-Clause License. 22.7k GitHub stars. |
| **Pricing** | Self-hosted: Free. Cloud: Custom pricing. SOC 2 Type 2, GDPR, PCI-DSS compliant. |
| **Integration Complexity** | 🟡 Medium — GraphQL API, 160+ webhooks, dashboard extensions (45+ mount points), multi-language SDK support (TS, Python, PHP, Ruby, Go, React, .NET). |
| **Alfred Agent Team** | **MAVEN** (commerce), **ARCHITECT** (infrastructure) |
| **Key Features** | GraphQL API, 160+ webhooks, dashboard UI extensions, OpenTelemetry observability, configurator CLI, dynamic data models, Agentic Commerce Protocol |
| **Why for Alfred** | GraphQL API is excellent. "Agentic Commerce Protocol" means it's built for AI-driven commerce. PHP SDK available. Used by Lush, Breitling. |

### Shopify API
| Field | Details |
|-------|---------|
| **What it does** | Market-leading e-commerce platform. Storefront API, Admin API, Plus features. |
| **Self-hosted** | ❌ No — Cloud only. |
| **Open Source** | ❌ No — Proprietary. |
| **Pricing** | Basic: $29/mo. Shopify: $79/mo. Advanced: $299/mo. Plus: $2,300/mo. |
| **Integration Complexity** | 🟡 Medium — GraphQL Admin API, REST Admin API, Storefront API, webhooks. PHP SDK available. |
| **Alfred Agent Team** | **MAVEN** (commerce), **SOFIA** (customer service) |
| **Key Features** | Massive ecosystem, Shopify Markets (international), Shopify Payments, Shop Pay, 8,000+ apps |
| **Why for Alfred** | Huge market share. Many Alfred users likely have Shopify stores. Essential integration for e-commerce vertical. |

### WooCommerce API
| Field | Details |
|-------|---------|
| **What it does** | WordPress-based e-commerce. Open source, extensible, self-hosted. |
| **Self-hosted** | ✅ Yes — WordPress + WooCommerce plugin. |
| **Open Source** | ✅ Yes — GPL License. |
| **Pricing** | Free (core). Extensions vary. |
| **Integration Complexity** | 🟢 Low — REST API v3, PHP native (WordPress), webhooks. |
| **Alfred Agent Team** | **MAVEN** (commerce), **ARCHITECT** (WordPress integration) |
| **Key Features** | WordPress ecosystem, 800+ extensions, REST API, webhooks, customizable |
| **Why for Alfred** | Many small business Alfred users run WooCommerce. PHP-native integration. Massive extension ecosystem. |

---

## 11. Social Media APIs

> **Alfred Gap:** HERALD agent handles communications but has no social media posting/monitoring capabilities.

### X (Twitter) API
| Field | Details |
|-------|---------|
| **What it does** | Post tweets, read timelines, manage DMs, search, trends, user management. New pay-per-use model (credit-based). |
| **Self-hosted** | ❌ No — Cloud API. |
| **Open Source** | N/A — Twitter API client libraries available. |
| **Pricing** | Pay-per-use: Posts Read $0.005/resource, User Read $0.010/resource, Content Create $0.010/request, DM Create $0.015/request. Estimated ~$215/mo for moderate use. |
| **Integration Complexity** | 🟡 Medium — OAuth 2.0, REST API v2, webhooks. |
| **Alfred Agent Team** | **HERALD** (social media), **SCOUT** (social monitoring) |
| **Key Features** | Tweet CRUD, search, DMs, user lookup, trends, streaming, media upload |
| **Why for Alfred** | Essential social media platform. New pay-per-use removes old tier restrictions. Alfred could post, monitor, and engage on X. |

### Bluesky AT Protocol
| Field | Details |
|-------|---------|
| **What it does** | Open social network built on AT Protocol. Post, follow, custom feeds, labeling. Decentralized architecture. |
| **Self-hosted** | ✅ Yes — Can run your own PDS (Personal Data Server). |
| **Open Source** | ✅ Yes — MIT License. AT Protocol and Bluesky app are open-source. |
| **Pricing** | Free. |
| **Integration Complexity** | 🟢 Low — HTTP API, TypeScript SDK (official), Python SDK (community), Go SDK (official). |
| **Alfred Agent Team** | **HERALD** (social media), **SCOUT** (social monitoring) |
| **Key Features** | Decentralized, open protocol, custom feeds (algorithms), labeling, TypeScript/Python/Dart/Go SDKs, starter templates for bots |
| **Why for Alfred** | Growing platform, fully open. Alfred could run custom feed algorithms, bots. Starter templates make it easy. |

### YouTube Data API v3
| Field | Details |
|-------|---------|
| **What it does** | Upload videos, manage playlists, subscriptions, channel settings, search content, retrieve video statistics. |
| **Self-hosted** | ❌ No — Google Cloud API. |
| **Open Source** | N/A — Google API with client libraries (Apps Script, Go, Java, JS, .NET, PHP, Python, Ruby). |
| **Pricing** | Free (10,000 quota units/day default). Additional quota available via application. |
| **Integration Complexity** | 🟡 Medium — OAuth 2.0, REST API, PHP client library available. |
| **Alfred Agent Team** | **HERALD** (content distribution), **EMBER** (creative), **SCOUT** (research) |
| **Key Features** | Video upload, playlist management, search, analytics, captions, live streaming, channel management |
| **Why for Alfred** | Second-largest search engine. Alfred could manage YouTube channels, upload content, analyze performance. |

### Meta Graph API (Instagram/Facebook)
| Field | Details |
|-------|---------|
| **What it does** | Manage Facebook Pages, Instagram Business profiles. Post content, read insights, moderate comments, messenger chatbots. |
| **Self-hosted** | ❌ No — Meta Cloud API. |
| **Open Source** | N/A — Meta SDKs available. |
| **Pricing** | Free (with approved app). |
| **Integration Complexity** | 🔴 High — Complex OAuth, app review process, permissions system, token management. |
| **Alfred Agent Team** | **HERALD** (social media), **EMBER** (creative) |
| **Key Features** | Page/profile management, content publishing, insights/analytics, comments moderation, Messenger API |
| **Why for Alfred** | Massive user base. Complex but necessary for social media management. |

### LinkedIn API
| Field | Details |
|-------|---------|
| **What it does** | Share posts, company pages, profile data, messaging (with partnership). |
| **Self-hosted** | ❌ No — LinkedIn Cloud API. |
| **Open Source** | N/A |
| **Pricing** | Free (with approved app). Marketing API requires partnership. |
| **Integration Complexity** | 🔴 High — Restrictive API access, complex app approval, limited endpoints for most developers. |
| **Alfred Agent Team** | **HERALD** (professional networking), **SOFIA** (business development) |
| **Key Features** | Share API, profile API, company pages, UGC posts |
| **Why for Alfred** | Essential for B2B users. Most restrictive API of all social platforms. |

### Reddit API
| Field | Details |
|-------|---------|
| **What it does** | Submit posts/comments, search, subreddit management, user data, voting. |
| **Self-hosted** | ❌ No — Reddit API. |
| **Open Source** | N/A — PRAW (Python) and Snoowrap (JS) are popular wrappers. |
| **Pricing** | Free tier: 100 queries/min. Paid tiers for higher volume. |
| **Integration Complexity** | 🟢 Low — OAuth 2.0, REST API, well-documented. |
| **Alfred Agent Team** | **HERALD** (community engagement), **SCOUT** (research/monitoring) |
| **Key Features** | Post/comment CRUD, search, subreddit listing, user data, moderation tools, trending |
| **Why for Alfred** | Great for community monitoring, research, and content distribution. |

### TikTok API
| Field | Details |
|-------|---------|
| **What it does** | Video upload, content publishing, analytics, user data. |
| **Self-hosted** | ❌ No — TikTok Cloud API. |
| **Open Source** | N/A |
| **Pricing** | Free (with approved developer account). |
| **Integration Complexity** | 🟡 Medium — OAuth 2.0, REST API. App review required. |
| **Alfred Agent Team** | **HERALD** (social media), **EMBER** (creative) |
| **Key Features** | Video upload/publish, analytics, user info, hashtag research |
| **Why for Alfred** | Fastest-growing social platform. Important for Alfred users in marketing/content creation. |

---

## 12. Maps & Location Services

> **Alfred Gap:** No geospatial or location capabilities. Cannot geocode, route, or display maps.

### Mapbox ⭐ RECOMMENDED
| Field | Details |
|-------|---------|
| **What it does** | Complete location platform. Maps, navigation, search/geocoding, data products (traffic, movement, boundaries). Used by BMW, Toyota. MapGPT for AI-powered location answers. |
| **Self-hosted** | Partial — Atlas (on-premise maps) available for enterprise. |
| **Open Source** | Partial — Mapbox GL JS is source-available. Many SDKs open-source. |
| **Pricing** | Free tier: 50k map loads/mo, 100k geocoding requests/mo. Pay-as-you-go after. Enterprise pricing available. |
| **Integration Complexity** | 🟢 Low — REST APIs, JavaScript/mobile SDKs. Simple API key auth. |
| **Alfred Agent Team** | **MERIDIAN** (location/navigation), **SCOUT** (geospatial research), **ATLAS** (productivity) |
| **Key Features** | Maps (GL JS, Mobile SDK), Navigation (routing, ETA, turn-by-turn), Search (geocoding, address autofill, POI), Data (traffic, movement, boundaries), Studio (custom styles), Atlas (on-prem) |
| **Why for Alfred** | Most developer-friendly mapping platform. Free tier is generous. MapGPT provides AI-powered location Q&A. Used by Toyota, BMW. |

### OpenStreetMap + Nominatim
| Field | Details |
|-------|---------|
| **What it does** | Free, editable map of the world (OSM). Nominatim provides geocoding/reverse geocoding on OSM data. |
| **Self-hosted** | ✅ Yes — Full self-hosted geocoding with Nominatim. |
| **Open Source** | ✅ Yes — ODbL (data), GPL (Nominatim). |
| **Pricing** | Free (self-hosted). Public API: Free with usage policy (1 req/sec). |
| **Integration Complexity** | 🟢 Low — REST API for geocoding. Tile servers for maps. |
| **Alfred Agent Team** | **MERIDIAN** (location), **SCOUT** (research) |
| **Key Features** | Global map data, geocoding/reverse geocoding, POI search, self-hostable, community-maintained |
| **Why for Alfred** | Completely free and self-hostable. No API key restrictions. Good for cost-sensitive deployments. |

### Google Maps Platform
| Field | Details |
|-------|---------|
| **What it does** | Maps, routes, places, geocoding, distance matrix. Industry standard. |
| **Self-hosted** | ❌ No — Google Cloud. |
| **Open Source** | ❌ No — Proprietary. |
| **Pricing** | $200/mo free credit. Then: Maps $7/1k loads, Geocoding $5/1k, Directions $5-10/1k, Places $17-40/1k. |
| **Integration Complexity** | 🟢 Low — REST APIs, JavaScript SDK, PHP client. |
| **Alfred Agent Team** | **MERIDIAN** (location), **PIERRE** (concierge) |
| **Key Features** | Maps, directions, geocoding, places, Street View, distance matrix, elevation |
| **Why for Alfred** | Most comprehensive mapping API. $200/mo free credit covers most use cases. Everyone knows Google Maps. |

### What3Words
| Field | Details |
|-------|---------|
| **What it does** | Divides world into 3m x 3m squares, each with unique 3-word address. Geocoding and reverse geocoding with 3-word addresses. |
| **Self-hosted** | ❌ No — Cloud API. |
| **Open Source** | ❌ No — Proprietary. |
| **Pricing** | Free tier (limited). Paid plans from usage-based. |
| **Integration Complexity** | 🟢 Low — REST API, SDKs for major languages. |
| **Alfred Agent Team** | **MERIDIAN** (location), **PIERRE** (concierge) |
| **Key Features** | 3-word addresses, multilingual (50+ languages), offline SDK, voice input compatible |
| **Why for Alfred** | Unique for voice-based location (Alfred's VAPI integration). Users can say 3 words instead of complex addresses. |

---

## 13. Priority Integration Matrix

Based on impact, feasibility, and synergy with Alfred's existing architecture:

### 🔴 CRITICAL (Implement First)
| Tool | Category | Why Critical |
|------|----------|-------------|
| **n8n** | Workflow | Fills Alfred's biggest gap — workflow chaining. Already scaffolded. Docker deploy. |
| **Firecrawl** | Scraping | Gives every agent web-to-markdown. Self-hostable. 89k stars. |
| **Docling** | Documents | MIT license, MCP server included. Parses everything. IBM-backed. |
| **Unified.to** | Integration | 413 integrations via single API. PHP SDK. Instant scale. |
| **Cal.com** | Scheduling | Self-hosted, open-source. Same-stack potential (PHP). AI phone agent. |

### 🟡 HIGH (Implement Second)
| Tool | Category | Why High Priority |
|------|----------|------------------|
| **Twenty** | CRM | Open-source CRM. Self-hosted. GraphQL API. |
| **BookStack** | Knowledge | Same tech stack (PHP+MySQL). MIT license. Minimal resources. |
| **Formbricks** | Forms | Open-source, self-hosted feedback collection. |
| **Medusa.js** | E-Commerce | Open-source commerce. Already has MCP server. |
| **Linear** | PM | AI-native PM with MCP server. Used by OpenAI. |
| **Crawlee** | Scraping | Runs in Node.js (same as Alfred MCP). Browser automation. |

### 🟢 MEDIUM (Implement Third)
| Tool | Category | Why Medium Priority |
|------|----------|---------------------|
| **Temporal** | Workflow | Durable execution for fleet orchestration. PHP SDK. |
| **Nango** | Integration | OAuth management for 700+ APIs. Open-source. |
| **Marker** | Documents | Best PDF-to-markdown. Supports Alfred's AI providers. |
| **Bluesky AT** | Social | Open protocol, free, growing. Bot templates. |
| **X API** | Social | Essential but new pay-per-use pricing. |
| **Mapbox** | Maps | Best developer maps experience. Free tier. |
| **SurveyJS** | Forms | One-time purchase. Embeddable. AI form generation. |

### 🔵 LOW (Future/As-Needed)
| Tool | Category | Notes |
|------|----------|-------|
| Salesforce | CRM | Enterprise-only need. Complex. |
| LinkedIn API | Social | Very restrictive API access. |
| Meta Graph | Social | Complex approval process. |
| Jira | PM | Enterprise alternative to Linear. |
| What3Words | Maps | Niche use case. |

---

## 14. Recommended Phase Plan

### Phase 1: Foundation (Core Infrastructure)
**Goal:** Fill the biggest gaps with self-hosted, open-source solutions.

1. **n8n** — Deploy via Docker alongside Alfred. Create webhook triggers for Alfred agent actions. Build template workflows for common multi-tool chains.
2. **Firecrawl** — Self-host. Add `web_scrape` and `web_crawl` tools to Alfred's MCP server. Every agent gets web access.
3. **Docling** — Deploy Python microservice. Add `parse_document` MCP tool. Supports PDF, DOCX, PPTX, XLSX, HTML, images.
4. **Cal.com** — Self-host. Add scheduling tools to Pierre agent. Booking pages for Alfred-powered businesses.

### Phase 2: Data & Integration
**Goal:** Massively expand Alfred's integration reach.

5. **Unified.to** — Connect via PHP SDK. Add 413+ integration tools across CRM, ATS, Ticketing, Accounting, Messaging, etc.
6. **Twenty CRM** — Self-host. Add CRM tools to Sofia agent. Contact/deal/pipeline management.
7. **BookStack** — Self-host (shares PHP+MySQL stack). Add knowledge base tools to Curator agent. Alfred agents can read/write documentation.
8. **Crawlee** — Integrate into Node.js MCP server. Browser automation for dynamic pages.

### Phase 3: User-Facing
**Goal:** Add capabilities that directly impact end users.

9. **Formbricks** — Self-host. Add survey/feedback tools. Herald agent collects user feedback.
10. **Linear** — Connect via MCP server. Atlas agent manages tasks/projects.
11. **Medusa.js** — Self-host for e-commerce customers. Maven agent manages stores.
12. **Social Media APIs** — X API + Bluesky first. Herald agent posts/monitors.

### Phase 4: Scale & Enterprise
**Goal:** Enterprise features and advanced capabilities.

13. **Temporal** — For durable fleet orchestration (replaces simple PM2 process management).
14. **Nango** — OAuth management for customer-specific integrations.
15. **Mapbox** — Location tools for Meridian agent.
16. **Google/Microsoft Calendar** — For users already on those ecosystems.
17. **HubSpot/Salesforce** — Enterprise CRM integration on demand.

---

## Summary Statistics

| Metric | Count |
|--------|-------|
| **Tools Researched** | 50+ |
| **Categories Covered** | 12 |
| **Self-Hostable Options** | 25+ |
| **Open Source (MIT/Apache)** | 15+ |
| **Free Tier Available** | 35+ |
| **Has MCP Server** | 8 (n8n, Docling, Unified.to, Nango, Linear, Medusa, Pipedream, Paragon) |
| **PHP SDK Available** | 10+ (Unified.to, Temporal, HubSpot, Google APIs, Shopify, WooCommerce, etc.) |
| **Same Stack as Alfred (PHP+MySQL)** | 2 (BookStack, WooCommerce) |

### Estimated Impact
- **Current Alfred tools:** 1,290+
- **With Phase 1-2 integrations:** ~1,800+ tools (500+ new via Unified.to alone)
- **With all phases:** ~2,500+ tools
- **Unique third-party apps accessible:** 700+ (via Unified.to + Nango combined)
