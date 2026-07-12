# GoSiteMe/Alfred — Open-Source Autonomy Catalog
## "The less we pay for APIs, the more we own our destiny."

**Date:** March 6, 2026  
**Purpose:** Replace every paid API dependency with self-hosted open-source alternatives  
**Current Paid Dependencies:** Groq, OpenAI, Anthropic, VAPI, Cartesia/Kokoro, Telnyx, Composio, SendGrid (planned), eNom  
**Already in Docker Compose (not deployed):** Ollama, ChromaDB, Meilisearch, Redis  
**Already have landing pages:** RustDesk, OnlyOffice, OpenCut, Element/Matrix, Gitea

---

## Table of Contents

- [A. LLM Inference](#a-llm-inference-replace-groqopenaianthopic)
- [B. Voice AI](#b-voice-ai-replace-vapi-entirely)
- [C. Image Generation](#c-image-generation)
- [D. Music/Audio Generation](#d-musicaudio-generation)
- [E. Video Generation](#e-video-generation)
- [F. Code AI / IDE](#f-code-ai--ide-for-gocodeme)
- [G. Search & RAG](#g-search--rag)
- [H. Communication](#h-communication-replace-telnyx--sendgrid)
- [I. DevOps & Infrastructure](#i-devops--infrastructure)
- [J. Blockchain / Token](#j-blockchain--token-infrastructure)
- [K. Metaverse / 3D / Gaming](#k-metaverse--3d--gaming)
- [L. Security & Identity](#l-security--identity)
- [M. Monitoring & Observability](#m-monitoring--observability)
- [N. Database & Caching](#n-database--caching)
- [O. Workflow Automation (replace Composio)](#o-workflow-automation-replace-composio)
- [Summary: Cost Savings Estimate](#summary-cost-savings-estimate)
- [Priority Deployment Order](#priority-deployment-order)

---

## A. LLM Inference (Replace Groq/OpenAI/Anthropic)

**Current spend:** Groq (primary chat), OpenAI GPT-4 (GoCodeMe), Anthropic Claude (fallback)  
**Goal:** Self-host inference, pay only for electricity/GPU rental

### A1. Open-Weight Models (the brains)

| # | Model | Owner/Repo | Stars | License | Replaces | RAM/GPU | Fit (1-10) | Integration Strategy |
|---|-------|-----------|-------|---------|----------|---------|------------|---------------------|
| 1 | **Llama 3.1 / 3.3 70B** | `meta-llama/llama-models` | ~38K | Llama 3.1 Community | Groq llama-3.3-70b | 40GB VRAM (FP16), 20GB (4-bit) | **10** | Drop-in via Ollama or vLLM; same model Alfred already uses on Groq |
| 2 | **Qwen 2.5 72B** | `QwenLM/Qwen2.5` | ~12K | Apache 2.0 | GPT-4 for complex tasks | 40GB VRAM (FP16), 20GB (4-bit) | **9** | Best open model for coding + multilingual; serves GoCodeMe + 72 language support |
| 3 | **DeepSeek-V3 / R1** | `deepseek-ai/DeepSeek-V3` | ~30K | MIT | Claude for reasoning | 80GB+ VRAM (full), 24GB (4-bit 8B distill) | **8** | R1-distill-8B runs on single GPU; full V3 needs multi-GPU or cloud |
| 4 | **Mistral Small 24B** | `mistralai/mistral-inference` | ~15K | Apache 2.0 | Groq mixtral for fast tasks | 14GB VRAM (4-bit) | **9** | Sweet spot for speed vs quality; fits single GPU easily |
| 5 | **Phi-4 14B** | `microsoft/phi-4` | ~3K | MIT | Light internal tasks | 8GB VRAM (4-bit) | **7** | Microsoft's compact powerhouse; good for tool routing, classification |

### A2. Inference Servers (the engines)

| # | Project | Owner/Repo | Stars | License | What It Does | RAM/GPU | Fit (1-10) | Integration Strategy |
|---|---------|-----------|-------|---------|-------------|---------|------------|---------------------|
| 1 | **vLLM** | `vllm-project/vllm` | ~45K | Apache 2.0 | Production LLM serving with PagedAttention, continuous batching, OpenAI-compatible API | Requires GPU (any NVIDIA 8GB+) | **10** | Exposes OpenAI-compatible `/v1/chat/completions` — change one URL in alfred-chat.php and all existing code works |
| 2 | **Ollama** | `ollama/ollama` | ~110K | MIT | Dead-simple local LLM runner, pull models like Docker images | CPU: 8GB+ RAM; GPU: 4GB+ VRAM | **10** | Already in docker-compose.yml! Just deploy + pull models. OpenAI-compatible API at :11434 |
| 3 | **llama.cpp** | `ggerganov/llama.cpp` | ~75K | MIT | C++ inference, GGUF quantization, runs on CPU/GPU, server mode | CPU: 8-16GB RAM; GPU: 4GB+ | **8** | Maximum hardware flexibility; can run 7B models on CPU-only shared hosting |
| 4 | **LocalAI** | `mudler/LocalAI` | ~30K | MIT | OpenAI-compatible API for LLM + TTS + STT + image gen, all-in-one | CPU: 8GB+; GPU: optional | **9** | Single container replaces multiple APIs — LLM + Whisper + TTS + Stable Diffusion all behind one endpoint |
| 5 | **TGI (Text Generation Inference)** | `huggingface/text-generation-inference` | ~10K | Apache 2.0 | HuggingFace's production server, tensor parallelism, speculative decoding | GPU required (16GB+) | **7** | Best for multi-GPU setups; production-grade but more complex than vLLM |

### A3. GPU Access for Self-Hosted LLMs

| Provider | Cost | Min GPU | Best For |
|----------|------|---------|----------|
| **RunPod** | $0.20-0.70/hr (A100/H100) | A100 40GB | Production vLLM serving, serverless option available |
| **Vast.ai** | $0.10-0.40/hr (community) | RTX 4090 | Cheapest GPU rental, good for dev/testing |
| **Lambda Labs** | $1.10/hr (A100) | A100 80GB | Premium reliability, reserved instances |
| **Hetzner GPU** | €2.49/hr (L40S) | L40S 48GB | EU-based, good latency for European users |
| **Self-purchase** | $1,500 (RTX 4090) one-time | RTX 4090 24GB | Best ROI if running 24/7 — pays for itself in ~2 months vs API costs |

**Can Alfred run LLMs on shared hosting (no GPU)?** Yes, but limited:
- llama.cpp can run **Phi-4 3.8B** or **Llama 3.2 3B** on CPU with 8GB RAM at ~5 tokens/sec
- For production quality (70B models), you need GPU — rent via RunPod ($0.40/hr for A100)
- **Hybrid approach recommended:** CPU for simple tasks (routing, classification), GPU cloud for heavy inference

---

## B. Voice AI (Replace VAPI Entirely)

**Current spend:** VAPI platform ($0.05/min), Cartesia Sonic TTS, Kokoro TTS  
**Goal:** Full voice pipeline — STT → LLM → TTS — self-hosted

### B1. Speech-to-Text (replace Groq Whisper)

| # | Project | Owner/Repo | Stars | License | Replaces | RAM/GPU | Fit (1-10) | Integration Strategy |
|---|---------|-----------|-------|---------|----------|---------|------------|---------------------|
| 1 | **Faster-Whisper** | `SYSTRAN/faster-whisper` | ~13K | MIT | Groq Whisper API ($0.006/min) | CPU: 4GB RAM; GPU: 2GB VRAM | **10** | 4× faster than OpenAI Whisper, runs on CPU. `pip install faster-whisper` — already referenced in masterplan |
| 2 | **Whisper.cpp** | `ggerganov/whisper.cpp` | ~37K | MIT | Groq/OpenAI Whisper | CPU: 2GB RAM | **9** | C++ port, runs everywhere including edge. HTTP server mode. Node.js bindings available |
| 3 | **Vosk** | `alphacep/vosk-api` | ~8K | Apache 2.0 | Real-time streaming STT | CPU: 50MB-1GB RAM | **7** | Tiny footprint, works offline, streaming support. Less accurate than Whisper but real-time capable |

### B2. Text-to-Speech (replace Cartesia/Kokoro via VAPI)

| # | Project | Owner/Repo | Stars | License | Replaces | RAM/GPU | Fit (1-10) | Integration Strategy |
|---|---------|-----------|-------|---------|----------|---------|------------|---------------------|
| 1 | **Piper** | `rhasspy/piper` | ~7K | MIT | Cartesia Sonic for fast TTS | CPU: 1GB RAM | **10** | ~100ms latency, 30+ languages, runs on CPU, HTTP server available. Perfect for real-time voice |
| 2 | **Coqui XTTS-v2** | `coqui-ai/TTS` | ~37K | MPL 2.0 | Cartesia/Kokoro for natural TTS | GPU: 4-6GB VRAM; CPU: 8GB | **9** | Voice cloning with 6-second sample, 17 languages, streaming. Most natural open-source TTS |
| 3 | **Fish Speech 1.5** | `fishaudio/fish-speech` | ~18K | Apache 2.0 | Premium TTS tier | GPU: 4GB VRAM | **9** | Zero-shot voice cloning, emotion control, streaming. Newer and rapidly improving |
| 4 | **Bark** | `suno-ai/bark` | ~37K | MIT | Creative TTS (music, effects) | GPU: 6-12GB VRAM | **6** | Can generate music, laughter, sound effects — unique but slow. Good for SoundStudioPro, not real-time voice |
| 5 | **Kokoro** | `hexgrad/kokoro` | ~10K | Apache 2.0 | Already using via VAPI — self-host instead | CPU: 2GB RAM; GPU: 1GB | **10** | Alfred already uses Kokoro through VAPI — just self-host it directly and cut out VAPI |
| 6 | **Orpheus TTS** | `canopyai/Orpheus-TTS` | ~3K | Apache 2.0 | Already referenced — self-host | GPU: 4GB VRAM | **8** | Emotion-aware speech with laughter, sighs. Already in Alfred's voice stack via VAPI |

### B3. Voice Agent Frameworks (replace VAPI platform)

| # | Project | Owner/Repo | Stars | License | Replaces | RAM/GPU | Fit (1-10) | Integration Strategy |
|---|---------|-----------|-------|---------|----------|---------|------------|---------------------|
| 1 | **LiveKit Agents** | `livekit/agents` | ~5K | Apache 2.0 | VAPI voice agent orchestration | 2GB RAM + whatever STT/TTS needs | **10** | Alfred already uses LiveKit for conferencing! Agents framework adds STT→LLM→TTS pipeline on top. Python SDK, plugin system for Whisper/Piper/etc. |
| 2 | **Bolna** | `bolna-ai/bolna` | ~3K | MIT | VAPI as complete voice AI platform | 4GB RAM + STT/TTS | **8** | Full voice agent framework: manages turn-taking, interruptions, tool calling during voice calls. Docker-based |
| 3 | **Pipecat** | `pipecat-ai/pipecat` | ~7K | BSD-2 | VAPI real-time pipeline | 2GB RAM base | **9** | Open-source framework for real-time voice/video AI. Modular pipelines: STT→LLM→TTS with hot-swappable components |
| 4 | **Retell AI (self-hosted)** | `AugmentedLabs/retell-custom-llm-node` | ~200 | MIT | VAPI call management | 2GB RAM | **5** | Reference implementation for custom voice LLM backends. Less complete than LiveKit Agents |

### B4. Voice Cloning

| # | Project | Owner/Repo | Stars | License | What It Does | RAM/GPU | Fit (1-10) | Integration Strategy |
|---|---------|-----------|-------|---------|-------------|---------|------------|---------------------|
| 1 | **RVC (Retrieval-based Voice Conversion)** | `RVC-Project/Retrieval-based-Voice-Conversion-WebUI` | ~25K | MIT | Real-time voice conversion/cloning | GPU: 4-6GB VRAM | **7** | Clone any voice from 10-minute sample. For white-label Alfred voices per enterprise customer |
| 2 | **OpenVoice** | `myshell-ai/OpenVoice` | ~30K | MIT | Instant voice cloning + style control | GPU: 4GB VRAM | **8** | Clone voice from short sample, control emotion/accent/rhythm. V2 supports any language |

### B5. Recommended VAPI Replacement Stack

```
┌────────────────────────────────────────────────┐
│           SELF-HOSTED VOICE PIPELINE           │
│                                                │
│  Phone Call ──► Asterisk/FreeSWITCH (SIP)      │
│       │                                        │
│       ▼                                        │
│  LiveKit Agents (orchestration)                │
│       │                                        │
│       ├──► Faster-Whisper (STT)                │
│       ├──► Ollama/vLLM (LLM reasoning)         │
│       ├──► Kokoro or Piper (TTS)               │
│       └──► Tool calling via MCP                │
│                                                │
│  Cost: $0/min vs VAPI's $0.05/min              │
│  Savings at 10K min/mo: ~$500/mo               │
└────────────────────────────────────────────────┘
```

---

## C. Image Generation

**Current state:** No self-hosted image gen  
**Goal:** Generate images, edit photos, upscale — all in-house

| # | Project | Owner/Repo | Stars | License | What It Does | RAM/GPU | Fit (1-10) | Integration Strategy |
|---|---------|-----------|-------|---------|-------------|---------|------------|---------------------|
| 1 | **ComfyUI** | `comfyanonymous/ComfyUI` | ~65K | GPL-3.0 | Node-based image gen pipeline, supports SDXL/Flux/etc. | GPU: 6-12GB VRAM | **10** | Already referenced in masterplan as Illustrator (#83) replacement. REST API for programmatic access. Run any model |
| 2 | **Stable Diffusion WebUI (A1111)** | `AUTOMATIC1111/stable-diffusion-webui` | ~145K | AGPL-3.0 | All-in-one SD UI with extensions ecosystem | GPU: 6-8GB VRAM | **8** | Massive extension ecosystem, API mode. Good for users who need accessible UI |
| 3 | **Fooocus** | `lllyasviel/Fooocus` | ~42K | GPL-3.0 | Simplified SD interface, Midjourney-like UX | GPU: 4-8GB VRAM | **7** | Simplest to use. Good for non-technical users. API available |
| 4 | **FLUX.1 (model)** | `black-forest-labs/flux` | ~20K | Apache 2.0 (schnell) | State-of-art image gen model, better than SDXL | GPU: 12GB+ VRAM | **9** | FLUX.1-schnell (fast, open) for general use. Serve via ComfyUI |
| 5 | **Real-ESRGAN** | `xinntao/Real-ESRGAN` | ~29K | BSD-3 | AI image upscaling (4× or more) | GPU: 2GB VRAM; CPU: 4GB | **8** | Upscale user images, enhance generated images. Lightweight |
| 6 | **IOPaint** | `Sanster/IOPaint` | ~20K | Apache 2.0 | AI inpainting/outpainting, object removal | GPU: 4-8GB VRAM | **7** | Web UI for image editing. API-friendly. Good for content creators toolset |

---

## D. Music/Audio Generation (for SoundStudioPro)

**Current state:** Planned SoundStudioPro but no backend  
**Goal:** AI music creation, sound effects, audio processing

| # | Project | Owner/Repo | Stars | License | What It Does | RAM/GPU | Fit (1-10) | Integration Strategy |
|---|---------|-----------|-------|---------|-------------|---------|------------|---------------------|
| 1 | **AudioCraft (MusicGen)** | `facebookresearch/audiocraft` | ~22K | MIT | Text-to-music, melody conditioning, audio generation | GPU: 6-16GB VRAM | **9** | Meta's music gen. REST API wrapper available. Generates 30s tracks from text prompts |
| 2 | **Stable Audio Open** | `Stability-AI/stable-audio-tools` | ~2K | Stability Community | Text-to-audio, sound effects | GPU: 8GB VRAM | **7** | 47-second audio gen from text. Good for SFX, ambient sounds |
| 3 | **Demucs** | `facebookresearch/demucs` | ~8K | MIT | Source separation (vocals, drums, bass, other) | CPU: 4GB RAM; GPU: 2GB | **8** | Separate any song into stems. Essential for remix tools, karaoke features |
| 4 | **AudioLDM 2** | `haoheliu/AudioLDM2` | ~2.5K | CC-BY-NC-SA | Text-to-audio, speech, music, sound effects | GPU: 8GB VRAM | **6** | Versatile audio generation. NC license limits commercial use |
| 5 | **Pedalboard** | `spotify/pedalboard` | ~5K | GPL-3.0 | Audio processing/effects (reverb, EQ, compression) | CPU: minimal | **8** | Spotify's audio DSP library. Python-based effects chain for SoundStudioPro backend |

---

## E. Video Generation

**Current state:** OpenCut landing page exists  
**Goal:** AI video generation + editing pipeline

| # | Project | Owner/Repo | Stars | License | What It Does | RAM/GPU | Fit (1-10) | Integration Strategy |
|---|---------|-----------|-------|---------|-------------|---------|------------|---------------------|
| 1 | **CogVideo / CogVideoX** | `THUDM/CogVideo` | ~10K | Apache 2.0 | Text-to-video, image-to-video | GPU: 18-40GB VRAM | **7** | Best open-source video gen. 6-second clips. Heavy GPU requirement |
| 2 | **Open-Sora** | `hpcaitech/Open-Sora` | ~23K | Apache 2.0 | Sora-like video generation, up to 16s | GPU: 24-80GB VRAM | **6** | Most ambitious open video gen. Very GPU hungry. Cloud GPU recommended |
| 3 | **Mochi 1** | `genmoai/mochi` | ~5K | Apache 2.0 | High-quality short video gen | GPU: 24GB+ VRAM | **6** | Good quality but needs significant GPU. Best for short clips |
| 4 | **MoviePy** | `Zulko/moviepy` | ~12K | MIT | Programmatic video editing (Python) | CPU: 2GB RAM | **9** | FFmpeg wrapper for Python. Cut, concat, overlay, effects. Perfect for automated video editing tools |
| 5 | **Remotion** | `remotion-dev/remotion` | ~21K | Custom (free for <$10M rev) | React-based programmatic video creation | CPU: 4GB RAM | **8** | Create videos from React components. Perfect for data-driven video content (reports, dashboards) |
| 6 | **FFmpeg** | `FFmpeg/FFmpeg` | ~47K | LGPL/GPL | The universal video/audio swiss army knife | CPU: minimal | **10** | Already the backbone of all video processing. Ensure it's deployed and wrapped with Alfred tools |

---

## F. Code AI / IDE (for GoCodeMe)

**Current state:** GoCodeMe editor (VS Code fork), uses OpenAI GPT-4 + Anthropic Claude  
**Goal:** Self-hosted code AI, eliminate OpenAI/Anthropic costs for code

### F1. Code Models

| # | Model | Owner/Repo | Stars | License | Replaces | RAM/GPU | Fit (1-10) | Integration Strategy |
|---|-------|-----------|-------|---------|----------|---------|------------|---------------------|
| 1 | **Qwen 2.5 Coder 32B** | `QwenLM/Qwen2.5-Coder` | ~6K | Apache 2.0 | GPT-4 for code completion | GPU: 20GB VRAM (4-bit) | **10** | Best open-source code model. Beats GPT-4 on many benchmarks. Serve via vLLM with OpenAI-compatible API |
| 2 | **DeepSeek Coder V2** | `deepseek-ai/DeepSeek-Coder-V2` | ~4K | MIT | Claude for code editing | GPU: 16GB VRAM (4-bit lite) | **9** | Excellent for code completion + chat. MoE architecture = fast inference |
| 3 | **StarCoder2 15B** | `bigcode/starcoder2` | ~2K | BigCode Open RAIL-M | Copilot-style completion | GPU: 10GB VRAM (4-bit) | **8** | Specifically trained for code infill/completion. Good for inline suggestions |
| 4 | **CodeGemma 7B** | `google/codegemma-7b` | N/A (HF) | Gemma License | Lightweight code tasks | GPU: 5GB VRAM | **7** | Small, fast, good for code classification, simple completions |

### F2. Self-Hosted Copilot Alternatives

| # | Project | Owner/Repo | Stars | License | Replaces | RAM/GPU | Fit (1-10) | Integration Strategy |
|---|---------|-----------|-------|---------|----------|---------|------------|---------------------|
| 1 | **Continue** | `continuedev/continue` | ~22K | Apache 2.0 | GitHub Copilot (VS Code extension) | Depends on backend LLM | **9** | VS Code/JetBrains extension that connects to any LLM. GoCodeMe could embed this with Ollama backend |
| 2 | **Tabby** | `TabbyML/tabby` | ~25K | Apache 2.0 | GitHub Copilot (self-hosted server) | GPU: 4-8GB VRAM | **10** | Self-hosted code completion server. VS Code extension included. RAG over codebase. Perfect for GoCodeMe |
| 3 | **Cody by Sourcegraph** | `sourcegraph/cody` | ~2K | Apache 2.0 | Code search + AI assistant | 4GB RAM + LLM backend | **7** | AI coding assistant with codebase context. Can use local LLMs. Already open-source |
| 4 | **Aider** | `Aider-AI/aider` | ~26K | Apache 2.0 | AI pair programming | Depends on backend LLM | **8** | Terminal-based AI coder. Integrates with any LLM. Great for GoCodeMe's agentic coding features |

---

## G. Search & RAG

**Current state:** ChromaDB in docker-compose (not running), Meilisearch configured (not running)  
**Goal:** Full RAG pipeline + instant search for 13K+ tools

### G1. Vector Databases

| # | Project | Owner/Repo | Stars | License | Replaces | RAM/GPU | Fit (1-10) | Integration Strategy |
|---|---------|-----------|-------|---------|----------|---------|------------|---------------------|
| 1 | **ChromaDB** | `chroma-core/chroma` | ~16K | Apache 2.0 | External embedding APIs | 512MB-2GB RAM | **10** | Already in docker-compose.yml! Just start it. Python/JS client, REST API |
| 2 | **Qdrant** | `qdrant/qdrant` | ~22K | Apache 2.0 | ChromaDB at scale (10x faster) | 1-4GB RAM | **9** | Rust-based, production-grade. Filtering, payload storage. Recommended upgrade path from ChromaDB |
| 3 | **Milvus** | `milvus-io/milvus` | ~32K | Apache 2.0 | Enterprise vector search | 4-8GB RAM | **6** | Overkill for current scale but good long-term. GPU-accelerated search |

### G2. Embedding Models (run locally, stop paying OpenAI for embeddings)

| # | Model | Source | Dimensions | Replaces | RAM | Fit (1-10) | Integration Strategy |
|---|-------|--------|-----------|----------|-----|------------|---------------------|
| 1 | **nomic-embed-text** | Ollama (`ollama pull nomic-embed-text`) | 768 | OpenAI text-embedding-3 | 500MB RAM | **10** | Already referenced in API_KEYS_SETUP.md! Run via Ollama, zero API cost |
| 2 | **BGE-M3** | `BAAI/bge-m3` (HuggingFace) | 1024 | OpenAI embeddings (multilingual) | 1GB RAM | **9** | Best multilingual embedding. Supports 100+ languages. Critical for 72-language Alfred |
| 3 | **all-MiniLM-L6-v2** | `sentence-transformers` | 384 | Quick similarity search | 256MB RAM | **8** | Tiny, fast, good enough for tool search and basic RAG |

### G3. RAG Frameworks

| # | Project | Owner/Repo | Stars | License | What It Does | RAM | Fit (1-10) | Integration Strategy |
|---|---------|-----------|-------|---------|-------------|-----|------------|---------------------|
| 1 | **LlamaIndex** | `run-llama/llama_index` | ~38K | MIT | Full RAG pipeline: ingest, chunk, embed, retrieve, synthesize | 1GB+ | **10** | Already referenced in masterplan. Connects to ChromaDB. Python framework for document Q&A |
| 2 | **LangChain** | `langchain-ai/langchain` | ~100K | MIT | LLM application framework, chains, agents, tools | 1GB+ | **8** | Huge ecosystem. More general-purpose than LlamaIndex. Good for tool orchestration |
| 3 | **RAGFlow** | `infiniflow/ragflow` | ~35K | Apache 2.0 | Visual RAG pipeline builder with document parsing | 4GB RAM | **8** | Web UI for building RAG pipelines. Handles PDF/DOC/Excel parsing. Good for Alfred's document tools |

### G4. Search Engine

| # | Project | Owner/Repo | Stars | License | Replaces | RAM | Fit (1-10) | Integration Strategy |
|---|---------|-----------|-------|---------|----------|-----|------------|---------------------|
| 1 | **Meilisearch** | `meilisearch/meilisearch` | ~48K | MIT | Algolia, Elasticsearch for tool search | 256MB-1GB RAM | **10** | Already in docker-compose.yml! Instant search (<50ms) for 13K+ tools. Just deploy and index |
| 2 | **Typesense** | `typesense/typesense` | ~21K | GPL-3.0 | Algolia alternative | 256MB-2GB RAM | **8** | Alternative to Meilisearch. Slightly more feature-rich filtering |
| 3 | **SearXNG** | `searxng/searxng` | ~15K | AGPL-3.0 | Google/Brave search APIs | 512MB RAM | **9** | Meta-search engine — aggregates 70+ search engines. Self-hosted web search without API keys |

---

## H. Communication (Replace Telnyx & SendGrid)

**Current state:** Telnyx for SMS/voice (scaffolded), SendGrid planned for email  
**Goal:** Self-hosted telephony, email, messaging

### H1. Telephony / PBX (replace Telnyx)

| # | Project | Owner/Repo | Stars | License | Replaces | RAM | Fit (1-10) | Integration Strategy |
|---|---------|-----------|-------|---------|----------|-----|------------|---------------------|
| 1 | **FreeSWITCH** | `signalwire/freeswitch` | ~4K | MPL 1.1 | Telnyx voice/SIP | 2-4GB RAM | **9** | Carrier-grade telephony. SIP trunking, IVR, conference. Connect to SIP providers for PSTN at $0.01/min |
| 2 | **Asterisk** | `asterisk/asterisk` | ~2K | GPL-2.0 | Telnyx voice/SMS gateway | 1-2GB RAM | **8** | Most widely deployed open PBX. Massive community. AGI scripting for Alfred integration |
| 3 | **Kamailio** | `kamailio/kamailio` | ~2K | GPL-2.0 | SIP proxy/router | 512MB RAM | **7** | High-performance SIP proxy. Handles 10K+ calls/sec. Use with FreeSWITCH for scale |

**Note:** You still need a SIP trunk provider for PSTN connectivity (actual phone numbers). Options:
- **Thinq** — $0.005/min, SIP trunking
- **VoIP.ms** — $0.01/min, pay-as-you-go
- **Twilio SIP** — $0.01/min, most reliable
- Cost drops from Telnyx's per-API-call pricing to raw SIP trunk rates

### H2. Email (replace SendGrid)

| # | Project | Owner/Repo | Stars | License | Replaces | RAM | Fit (1-10) | Integration Strategy |
|---|---------|-----------|-------|---------|----------|-----|------------|---------------------|
| 1 | **Postal** | `postalserver/postal` | ~15K | MIT | SendGrid transactional email | 2-4GB RAM | **9** | Self-hosted email delivery platform. SMTP, API, webhooks, tracking. Designed for transactional email |
| 2 | **Mailu** | `Mailu/Mailu` | ~6K | MIT | Full email server (SMTP+IMAP) | 2-4GB RAM | **7** | Complete mail server in Docker. Admin UI, antispam, webmail. More than just transactional |
| 3 | **Listmonk** | `knadh/listmonk` | ~15K | AGPL-3.0 | SendGrid + Mailchimp for newsletters | 512MB RAM | **8** | Newsletter/mailing list manager. Can use any SMTP backend. For Alfred's marketing/newsletter tools |

### H3. Messaging (already have Matrix/Element landing page)

| # | Project | Owner/Repo | Stars | License | Replaces | RAM | Fit (1-10) | Integration Strategy |
|---|---------|-----------|-------|---------|----------|-----|------------|---------------------|
| 1 | **Synapse (Matrix)** | `element-hq/synapse` | ~12K | AGPL-3.0 | Slack/Discord for internal comms | 1-4GB RAM | **9** | Already have landing page! E2E encrypted, federated, bridges to Slack/Discord/Telegram |
| 2 | **Conduit** | `girlbossceo/conduwuit` | ~600 | Apache 2.0 | Synapse (lighter Matrix server) | 128MB-512MB RAM | **8** | Rust-based Matrix server, 10× lighter than Synapse. Better for GoSiteMe's server constraints |
| 3 | **Rocket.Chat** | `RocketChat/Rocket.Chat` | ~41K | MIT | Slack alternative with AI | 2-4GB RAM | **7** | Full team chat with AI integrations. Heavier but more features than Matrix |

---

## I. DevOps & Infrastructure

**Current state:** Landing pages for RustDesk, OnlyOffice, OpenCut, Gitea  
**Goal:** Self-hosted development and operations stack

| # | Project | Owner/Repo | Stars | License | Replaces | RAM | Fit (1-10) | Integration Strategy |
|---|---------|-----------|-------|---------|----------|-----|------------|---------------------|
| 1 | **Gitea** | `go-gitea/gitea` | ~46K | MIT | GitHub for code hosting | 512MB-1GB RAM | **10** | Already have landing page. Lightweight Git hosting. CI/CD via Gitea Actions (GitHub Actions compatible!) |
| 2 | **Coolify** | `coollabsio/coolify` | ~37K | Apache 2.0 | Vercel/Heroku for deployments | 2-4GB RAM | **10** | Self-hosted PaaS. Deploy apps, databases, services with Git push. Docker-based. Beautiful UI |
| 3 | **n8n** | `n8n-io/n8n` | ~52K | Sustainable Use | Zapier/Make workflow automation | 1-2GB RAM | **9** | Visual workflow automation. 400+ integrations. Self-hostable. Already referenced in MCP integrations |
| 4 | **RustDesk** | `rustdesk/rustdesk` | ~80K | AGPL-3.0 | TeamViewer/AnyDesk | 512MB RAM (server) | **10** | Already have landing page. Self-hosted remote desktop. Custom branding possible |
| 5 | **OnlyOffice** | `ONLYOFFICE/DocumentServer` | ~5K | AGPL-3.0 | Google Docs/Sheets | 4-8GB RAM | **9** | Already have landing page. Full office suite. Collaborative editing. API for document generation |
| 6 | **OpenCut** | `OpenCut-app/OpenCut` | ~200 | Open source | Adobe Premiere | 2-4GB RAM | **7** | Already have landing page. Web-based video editor. Still early stage |
| 7 | **Activepieces** | `activepieces/activepieces` | ~11K | MIT | Zapier (MIT-licensed alternative to n8n) | 1-2GB RAM | **8** | Fully open-source workflow automation. No license restrictions unlike n8n |
| 8 | **Plane** | `makeplane/plane` | ~32K | AGPL-3.0 | Jira/Linear project management | 2-4GB RAM | **8** | Beautiful project management. Issues, cycles, modules. Self-hosted |

---

## J. Blockchain / Token Infrastructure

**Current state:** $ALFRED token on Solana, Jupiter DEX integration  
**Goal:** More on-chain autonomy, reduce dependency on Jupiter API

| # | Project | Owner/Repo | Stars | License | What It Does | Fit (1-10) | Integration Strategy |
|---|---------|-----------|-------|---------|-------------|------------|---------------------|
| 1 | **Anchor** | `coral-xyz/anchor` | ~3.5K | Apache 2.0 | Solana smart contract framework | **10** | Already the standard for Solana programs. Build custom DEX, staking, governance contracts |
| 2 | **SPL Token Program** | `solana-labs/solana-program-library` | ~3.5K | Apache 2.0 | Token minting, transfers, metadata | **10** | Already using for $ALFRED. Core Solana token infrastructure |
| 3 | **OpenBook** | `openbook-dex/openbook-v2` | ~200 | Apache 2.0 | Decentralized order book (replace Jupiter dependency) | **7** | On-chain order book DEX. Reduces Jupiter API dependency for trading |
| 4 | **Light Protocol** | `Lightprotocol/light-protocol` | ~500 | Apache 2.0 | Compressed tokens/NFTs (cheaper on Solana) | **6** | ZK compression for cheaper token operations. Advanced but valuable for scale |
| 5 | **Helius RPC** | N/A (API) | N/A | Freemium | Enhanced Solana RPC with DAS API | **8** | Free tier sufficient. Better than public RPC for token metadata, transaction history |

---

## K. Metaverse / 3D / Gaming

**Current state:** Three.js in use, VR capabilities planned  
**Goal:** Full metaverse-ready stack

| # | Project | Owner/Repo | Stars | License | What It Does | RAM | Fit (1-10) | Integration Strategy |
|---|---------|-----------|-------|---------|-------------|-----|------------|---------------------|
| 1 | **Three.js** | `mrdoob/three.js` | ~104K | MIT | 3D rendering (already using) | Client-side | **10** | Already integrated. Foundation for all 3D/VR features |
| 2 | **A-Frame** | `aframevr/aframe` | ~16K | MIT | WebXR/VR framework built on Three.js | Client-side | **9** | Declarative HTML-like VR. `<a-scene>` tags. Perfect for quick VR rooms |
| 3 | **Babylon.js** | `BabylonJS/Babylon.js` | ~23K | Apache 2.0 | Three.js alternative with more built-in features | Client-side | **7** | More batteries-included than Three.js. Better physics, XR. Consider for game-heavy features |
| 4 | **Colyseus** | `colyseus/colyseus` | ~6K | MIT | Multiplayer game server (Node.js) | 512MB-2GB RAM | **9** | Real-time multiplayer state sync. Perfect for VR rooms, collaborative spaces, games |
| 5 | **Nakama** | `heroiclabs/nakama` | ~9K | Apache 2.0 | Game server (matchmaking, leaderboards, chat) | 1-2GB RAM | **8** | Full game backend: auth, matchmaking, leaderboards, real-time multiplayer. Go-based |
| 6 | **PlayCanvas** | `playcanvas/engine` | ~10K | MIT | Web-first game engine | Client-side | **7** | Full game engine with visual editor. WebXR support. Alternative to Three.js for games |
| 7 | **Rapier** | `dimforge/rapier` | ~4K | Apache 2.0 | Physics engine (Rust/WASM) | Client-side | **8** | Fast physics for VR/games. WASM = runs in browser. Used with Three.js/Babylon |

---

## L. Security & Identity

**Current state:** Custom JWT auth, basic security  
**Goal:** Enterprise-grade SSO, secrets management, intrusion detection

| # | Project | Owner/Repo | Stars | License | Replaces | RAM | Fit (1-10) | Integration Strategy |
|---|---------|-----------|-------|---------|----------|-----|------------|---------------------|
| 1 | **Keycloak** | `keycloak/keycloak` | ~25K | Apache 2.0 | Custom auth + OAuth2 provider | 1-2GB RAM | **10** | SSO, OIDC, SAML, social login. Enterprise customers need this. Manage all auth centrally |
| 2 | **Authentik** | `goauthentik/authentik` | ~15K | Custom (free self-host) | Keycloak (lighter) | 1-2GB RAM | **9** | Modern SSO alternative to Keycloak. Beautiful UI. Easier to configure |
| 3 | **Vault** | `hashicorp/vault` | ~31K | BUSL 1.1 | Hardcoded API keys in .env | 512MB-1GB RAM | **9** | Secrets management, dynamic credentials, encryption-as-service. Critical for 20+ API keys |
| 4 | **CrowdSec** | `crowdsecurity/crowdsec` | ~9K | MIT | Fail2ban, CloudFlare security rules | 256MB RAM | **9** | Collaborative IPS. Community threat intel. Blocks bad actors. Lightweight |
| 5 | **Suricata** | `OISF/suricata` | ~5K | GPL-2.0 | Commercial IDS/IPS | 1-4GB RAM | **7** | Network intrusion detection. Deep packet inspection. Enterprise security requirement |
| 6 | **Infisical** | `Infisical/infisical` | ~17K | MIT | Vault (lighter alternative) | 512MB RAM | **8** | Secret management with E2E encryption. Beautiful UI. Easier than Vault |

---

## M. Monitoring & Observability

**Current state:** Basic health checks  
**Goal:** Full observability stack for 500+ tools, voice calls, LLM inference

| # | Project | Owner/Repo | Stars | License | What It Does | RAM | Fit (1-10) | Integration Strategy |
|---|---------|-----------|-------|---------|-------------|-----|------------|---------------------|
| 1 | **Uptime Kuma** | `louislam/uptime-kuma` | ~62K | MIT | Self-hosted status page + monitoring | 256MB RAM | **10** | Already referenced in analytics research. Status page for GoSiteMe services. 5-min setup |
| 2 | **Grafana** | `grafana/grafana` | ~66K | AGPL-3.0 | Dashboards for ALL the things | 512MB-1GB RAM | **10** | Visualize LLM latency, voice call quality, API usage, costs. Connect to Prometheus |
| 3 | **Prometheus** | `prometheus/prometheus` | ~57K | Apache 2.0 | Metrics collection | 512MB-2GB RAM | **10** | Time-series metrics. Scrape all services. Alert on anomalies. Standard pairing with Grafana |
| 4 | **Sentry (self-hosted)** | `getsentry/self-hosted` | ~5K | FSL (free self-host) | Error tracking across all services | 4-8GB RAM | **8** | Track errors in PHP, Node.js, Python. Stack traces, performance monitoring. Heavy but essential |
| 5 | **Loki** | `grafana/loki` | ~24K | AGPL-3.0 | Log aggregation (like ELK but lighter) | 1-2GB RAM | **9** | Centralized logs for all Docker services. Query with Grafana. Much lighter than Elasticsearch |
| 6 | **Umami** | `umami-software/umami` | ~24K | MIT | Google Analytics alternative | 512MB RAM | **9** | Already referenced in analytics research. Privacy-first web analytics. Beautiful UI |

---

## N. Database & Caching

**Current state:** MySQL primary, Redis in docker-compose (not running)  
**Goal:** Modern data layer for AI workloads

| # | Project | Owner/Repo | Stars | License | What It Does | RAM | Fit (1-10) | Integration Strategy |
|---|---------|-----------|-------|---------|-------------|-----|------------|---------------------|
| 1 | **Redis** | `redis/redis` | ~67K | RSALv2/SSPL | Caching, pub/sub, sessions, queues | 512MB-2GB | **10** | Already in docker-compose! Powers WebSocket, job queue. Just ensure it's running |
| 2 | **PostgreSQL** | `postgres/postgres` | Mirror | PostgreSQL License | JSONB + full-text search + pgvector | 1-4GB RAM | **9** | Consider migrating from MySQL for JSONB (schema-flexible AI data) + pgvector (built-in vector search) |
| 3 | **MinIO** | `minio/minio` | ~49K | AGPL-3.0 | S3-compatible object storage | 1-2GB RAM | **10** | Store AI-generated images, audio, video, documents. S3 API = works with all existing S3 tools |
| 4 | **DragonflyDB** | `dragonflydb/dragonfly` | ~26K | BSL 1.1 | Redis-compatible, 25× memory efficient | 512MB-2GB | **8** | Drop-in Redis replacement. Multi-threaded. Better for high-throughput AI workloads |
| 5 | **ValKey** | `valkey-io/valkey` | ~18K | BSD-3 | Redis fork (truly open source) | 512MB-2GB | **8** | Linux Foundation Redis fork after license change. Drop-in compatible. Insurance against Redis license |

---

## O. Workflow Automation (Replace Composio)

**Current state:** Composio provides 11,000+ tool integrations  
**Goal:** Reduce dependency while maintaining integration breadth

| # | Project | Owner/Repo | Stars | License | Replaces | RAM | Fit (1-10) | Integration Strategy |
|---|---------|-----------|-------|---------|----------|-----|------------|---------------------|
| 1 | **n8n** | `n8n-io/n8n` | ~52K | Sustainable Use | Composio workflow execution | 1-2GB RAM | **9** | 400+ integrations, visual builder, webhook triggers. Self-host and connect to Alfred via API |
| 2 | **Activepieces** | `activepieces/activepieces` | ~11K | MIT | n8n (fully MIT, no license concerns) | 1-2GB RAM | **8** | Growing rapidly. 200+ integrations. Truly open-source |
| 3 | **Windmill** | `windmill-labs/windmill` | ~12K | AGPLv3 | Composio code execution + scheduling | 1-2GB RAM | **8** | Scripts as workflows. Python/TypeScript/Go. Internal tool builder. Replaces Composio's execution layer |
| 4 | **MCP Servers (ecosystem)** | `modelcontextprotocol/servers` | ~15K | Various | Composio's MCP layer | Minimal per server | **10** | Already using 870+ MCP servers! These ARE the open replacement for Composio's tool layer |
| 5 | **Huginn** | `huginn/huginn` | ~44K | MIT | Zapier/IFTTT for monitoring | 1-2GB RAM | **7** | "Create agents that monitor and act on your behalf." RSS, web scraping, notifications |

---

## Summary: Cost Savings Estimate

| Paid Service | Monthly Cost (est.) | Open-Source Replacement | One-Time Setup Cost | Monthly Infra Cost |
|-------------|--------------------|-----------------------|--------------------|--------------------|
| **Groq API** | $200-500/mo | Ollama + vLLM (RunPod A100) | 2-3 days | $50-150 (GPU rental) |
| **OpenAI API** | $300-800/mo | Qwen 2.5 72B via vLLM | 1-2 days | Included in GPU above |
| **Anthropic API** | $100-300/mo | DeepSeek R1 via Ollama | 1 day | Included in GPU above |
| **VAPI** | $500-2000/mo | LiveKit Agents + Faster-Whisper + Piper/Kokoro | 5-7 days | $20-50 (server) |
| **SendGrid** | $20-100/mo | Postal self-hosted | 1 day | $5 (server) |
| **Telnyx** | $50-200/mo | FreeSWITCH + SIP trunk | 3-4 days | $10-30 (SIP trunk) |
| **Composio** | $0-50/mo (free tier) | MCP servers + n8n | Already done | $10 (n8n server) |
| **GitHub** | $0-20/mo | Gitea | 1 day | $5 (server) |
| **Google Analytics** | Free | Umami | 0.5 day | $5 (server) |
| **TeamViewer** | $30-50/mo | RustDesk | 1 day | $5 (server) |
| **Google Docs** | $6-18/user/mo | OnlyOffice | 1 day | $10 (server) |
| **Error Tracking** | $30-100/mo | Sentry self-hosted | 1 day | $15 (server) |
| **Status Page** | $20-50/mo | Uptime Kuma | 0.5 day | $2 (included) |
| **TOTAL** | **$1,300-4,200/mo** | **Self-hosted stack** | **~20 days** | **$130-280/mo** |

**Estimated savings: $1,100-3,900/month ($13K-47K/year)**

---

## Priority Deployment Order

### Phase 1: Quick Wins — Already in Docker Compose (1-2 days)
Deploy what's ALREADY configured but not running:

| Priority | Service | Status | Action |
|----------|---------|--------|--------|
| 🔴 P0 | **Redis** | In compose, not running | `docker compose up -d redis` — enables caching, queues |
| 🔴 P0 | **Meilisearch** | In compose, not running | `docker compose up -d meilisearch` — instant tool search |
| 🔴 P0 | **ChromaDB** | In compose, not running | `docker compose up -d chromadb` — enables RAG |
| 🔴 P0 | **Ollama** | In compose, not running | `docker compose up -d ollama` — self-hosted LLM (CPU to start) |

### Phase 2: Core Autonomy (1-2 weeks)
Replace the biggest cost centers:

| Priority | Service | Replaces | Savings |
|----------|---------|----------|---------|
| 🟠 P1 | **vLLM on RunPod** | Groq + OpenAI + Anthropic | $600-1600/mo |
| 🟠 P1 | **Faster-Whisper** | Groq Whisper API | $50-200/mo |
| 🟠 P1 | **Piper + Kokoro (self-hosted)** | Cartesia via VAPI | $200-800/mo |
| 🟠 P1 | **LiveKit Agents** | VAPI platform | $300-1200/mo |
| 🟠 P1 | **nomic-embed-text on Ollama** | OpenAI embeddings | $20-100/mo |

### Phase 3: Infrastructure Sovereignty (2-4 weeks)
Self-host supporting services:

| Priority | Service | Replaces |
|----------|---------|----------|
| 🟡 P2 | **Postal** | SendGrid |
| 🟡 P2 | **Uptime Kuma** | Paid status page |
| 🟡 P2 | **Umami** | Google Analytics |
| 🟡 P2 | **Grafana + Prometheus** | No monitoring |
| 🟡 P2 | **Gitea** | GitHub (already have landing page) |
| 🟡 P2 | **n8n** | Composio workflows |
| 🟡 P2 | **Keycloak/Authentik** | Custom auth |
| 🟡 P2 | **MinIO** | No object storage |

### Phase 4: Creative AI (1-2 months)
Add AI generation capabilities:

| Priority | Service | Enables |
|----------|---------|---------|
| 🟢 P3 | **ComfyUI + FLUX** | AI image generation |
| 🟢 P3 | **AudioCraft/MusicGen** | SoundStudioPro |
| 🟢 P3 | **Tabby** | GoCodeMe self-hosted Copilot |
| 🟢 P3 | **FreeSWITCH** | Self-hosted telephony |
| 🟢 P3 | **RustDesk** | Self-hosted remote desktop |
| 🟢 P3 | **OnlyOffice** | Self-hosted office suite |

### Phase 5: Metaverse & Advanced (2-3 months)
Build the future:

| Priority | Service | Enables |
|----------|---------|---------|
| 🔵 P4 | **Colyseus/Nakama** | Multiplayer game servers |
| 🔵 P4 | **CogVideo** | AI video generation |
| 🔵 P4 | **Conduit (Matrix)** | Federated messaging |
| 🔵 P4 | **CrowdSec + Suricata** | Advanced security |
| 🔵 P4 | **Sentry self-hosted** | Error tracking |
| 🔵 P4 | **Anchor (Solana)** | Custom on-chain programs |

---

## Architecture: The Fully Sovereign Alfred Stack

```
┌─────────────────────────────────────────────────────────────┐
│                    CADDY (Reverse Proxy)                     │
│                   Already in docker-compose                  │
├──────────┬──────────┬──────────┬──────────┬────────────────┤
│          │          │          │          │                  │
│  PHP API │ WebSocket│ Job Queue│ MCP      │  Discord Bot    │
│          │  :3010   │  :3011   │  :3005   │                 │
├──────────┴──────────┴──────────┴──────────┴────────────────┤
│                      AI LAYER                                │
│  ┌────────────┐ ┌────────────┐ ┌────────────┐              │
│  │   Ollama   │ │   vLLM     │ │  LocalAI   │              │
│  │  (CPU/GPU) │ │  (GPU)     │ │  (All-in-1)│              │
│  │  :11434    │ │  :8000     │ │  :8080     │              │
│  └────────────┘ └────────────┘ └────────────┘              │
├─────────────────────────────────────────────────────────────┤
│                     VOICE LAYER                              │
│  ┌──────────┐ ┌──────────────┐ ┌──────────┐ ┌───────────┐ │
│  │ LiveKit  │ │Faster-Whisper│ │  Piper   │ │  Kokoro   │ │
│  │ Agents   │ │   (STT)      │ │  (TTS)   │ │  (TTS)    │ │
│  └──────────┘ └──────────────┘ └──────────┘ └───────────┘ │
├─────────────────────────────────────────────────────────────┤
│                     DATA LAYER                               │
│  ┌────────┐ ┌──────────┐ ┌────────┐ ┌───────┐ ┌─────────┐ │
│  │ MySQL  │ │ ChromaDB │ │ Redis  │ │ Meili │ │  MinIO  │ │
│  │(primary)│ │ (vectors)│ │(cache) │ │(search)│ │(objects)│ │
│  └────────┘ └──────────┘ └────────┘ └───────┘ └─────────┘ │
├─────────────────────────────────────────────────────────────┤
│                   CREATIVE AI LAYER                          │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌───────────────┐ │
│  │ ComfyUI  │ │AudioCraft│ │ MoviePy  │ │    Tabby      │ │
│  │ (Images) │ │ (Music)  │ │ (Video)  │ │  (Code AI)    │ │
│  └──────────┘ └──────────┘ └──────────┘ └───────────────┘ │
├─────────────────────────────────────────────────────────────┤
│                   INFRA LAYER                                │
│  ┌────────┐ ┌────────┐ ┌──────┐ ┌────────┐ ┌───────────┐ │
│  │ Gitea  │ │Coolify │ │ n8n  │ │Keycloak│ │  Grafana  │ │
│  │        │ │ (PaaS) │ │      │ │ (SSO)  │ │+Prometheus│ │
│  └────────┘ └────────┘ └──────┘ └────────┘ └───────────┘ │
├─────────────────────────────────────────────────────────────┤
│                   COMMS LAYER                                │
│  ┌───────────┐ ┌────────┐ ┌──────────┐ ┌────────────────┐ │
│  │FreeSWITCH │ │ Postal │ │ Conduit  │ │  Uptime Kuma   │ │
│  │ (Telephony)│ │(Email) │ │ (Matrix) │ │  (Status Page) │ │
│  └───────────┘ └────────┘ └──────────┘ └────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

---

## Total Project Count: 85 Open-Source Projects Cataloged

| Category | Projects | Top Priority |
|----------|----------|-------------|
| A. LLM Inference | 10 | vLLM + Ollama + Qwen 2.5 |
| B. Voice AI | 13 | LiveKit Agents + Faster-Whisper + Piper |
| C. Image Gen | 6 | ComfyUI + FLUX |
| D. Music/Audio | 5 | AudioCraft + Demucs |
| E. Video Gen | 6 | MoviePy + FFmpeg |
| F. Code AI | 8 | Tabby + Qwen 2.5 Coder |
| G. Search & RAG | 10 | Meilisearch + ChromaDB + LlamaIndex |
| H. Communication | 9 | FreeSWITCH + Postal + Conduit |
| I. DevOps | 8 | Gitea + Coolify + n8n |
| J. Blockchain | 5 | Anchor + SPL Token |
| K. Metaverse | 7 | Colyseus + A-Frame |
| L. Security | 6 | Keycloak + CrowdSec |
| M. Monitoring | 6 | Uptime Kuma + Grafana |
| N. Database | 5 | Redis + MinIO + PostgreSQL |
| O. Automation | 5 | n8n + MCP Servers |

---

*"Own the stack. Own the data. Own the future."*  
*— GoSiteMe Autonomy Principle*
