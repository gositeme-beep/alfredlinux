# GoCodeMe.com

## The #1 AI-Powered IDE & Development Platform

GoCodeMe is a production AI-powered web IDE combining a full VS Code experience with 1,290+ MCP tools, 17 AI engines, voice commands via Alfred AI, and a complete hosting ecosystem.

### Platform Stats

- **251 MCP Tools** across 36+ categories
- **9 AI Engines** — Code, DevOps, SEO, E-Commerce, Design, Legal, Accessibility, Content, Research
- **17 AI Engines** — Anthropic (Claude), OpenAI (GPT/o-series), Google (Gemini), Open Source (Llama/DeepSeek)
- **4 Providers** — Anthropic, OpenAI, Google, Open Source
- **Alfred AI** — 20+ agent personalities, voice commands, phone integration (VAPI/Telnyx)

### Features

- 🌐 **Web-based VS Code** — Full VS Code in-browser with custom GoCodeMe branding
- 🤖 **Multi-Model AI** — Claude Opus/Sonnet, GPT-4.1, o4-mini, Gemini 2.5, Llama 4, DeepSeek R1
- 🎙️ **Alfred Voice** — Voice-steered AI assistant with 20+ personalities
- 📞 **Phone Integration** — Call Alfred via VAPI/Telnyx for hands-free coding
- 🔒 **Self-hosted** — Complete privacy, JWT auth, DirectAdmin SSO
- 🏪 **WHMCS Integration** — Auto-provisioning, billing, token packs
- 📁 **Direct File Editing** — Edit files directly on your hosting server
- 🎨 **Custom Branding** — GoCodeMe branded interface and themes

### Tech Stack

- **Theia IDE** — VS Code web implementation (custom fork)
- **Node.js Middleware** — Express.js backend with 60+ API endpoints
- **MCP Server** — StreamableHTTP + SSE, 251 tools
- **Redis** — Session/cache/rate-limiting
- **PM2** — Process management (8 services)
- **WHMCS** — Billing, support, domain management
- **Nginx** — Reverse proxy, SSL termination
- **VAPI + Telnyx** — Voice/phone AI integration

### Architecture

```
gocodeme.com/
├── middleware/          # Node.js Express middleware (port 3001)
│   ├── routes/         # 60+ API endpoint routes
│   ├── services/       # AI, billing, auth services
│   └── ideProxy.js     # IDE proxy with Alfred injection
├── mcp-server/         # MCP tool server (port 3005)
│   ├── tools/          # 251 MCP tools across 36 categories
│   └── engines/        # 9 AI engine configurations
├── theia-fork/         # Custom Theia IDE build
├── openclaw/           # Legal AI research engine
├── scripts/            # Deployment & maintenance scripts
└── whmcs-module/       # WHMCS provisioning module
```

### Services (PM2)

| Service | Port | Description |
|---------|------|-------------|
| redis | 6379 | Cache & sessions |
| gocodeme-middleware | 3001 | API gateway & IDE proxy |
| openclaw | 3003 | Legal AI engine |
| mcp-server | 3005 | MCP tool server |
| voice-ws | 3006 | Voice WebSocket |
| stream-bridge | 3007 | SSE streaming |
| icecast | 8000 | Audio streaming |
| tts-server | 5002 | Text-to-speech (Coqui) |

### Development Status

- [x] VS Code web IDE (Theia fork)
- [x] AI chat panel with multi-model support
- [x] 251 MCP tools across 36+ categories
- [x] Alfred AI voice assistant (20+ agents)
- [x] VAPI/Telnyx phone integration
- [x] Custom GoCodeMe branding
- [x] DirectAdmin file system integration
- [x] JWT authentication + WHMCS SSO
- [x] Auto-provisioning via WHMCS hooks
- [x] Redis caching & rate limiting
- [x] Production deployment (PM2 managed)
- [x] Token-based billing system
- [x] WHMCS knowledge base (103 articles)
- [x] Dashboard with model management

### License

Proprietary — GoSiteMe Inc. All rights reserved.

MIT License - See LICENSE file for details. 