# ALFRED CREATIVE AI & MEDIA GENERATION RESEARCH
### Comprehensive Tool Audit for EMBER Creative Team (Agents 83–91)
### Research Date: March 6, 2026

---

## TABLE OF CONTENTS

1. [Image Generation](#1-image-generation)
2. [Video Generation](#2-video-generation)
3. [Audio/Music Generation](#3-audiomusic-generation)
4. [Voice Cloning & TTS](#4-voice-cloning--tts)
5. [3D Generation](#5-3d-generation)
6. [Document AI](#6-document-ai)
7. [Design/UI Automation](#7-designui-automation)
8. [Content Management](#8-content-management)
9. [Video Editing (Programmatic)](#9-video-editing-programmatic)
10. [Presentation Generation](#10-presentation-generation)
11. [EMBER Agent Mapping Summary](#11-ember-agent-mapping-summary)
12. [Priority Implementation Plan](#12-priority-implementation-plan)

---

## 1. IMAGE GENERATION

**EMBER Agent: #83 Illustrator** (`generate_image`, `ai_image_*`, `dall_e_*`)

### Current Alfred State
- DALL-E integration (via OpenAI API)
- SDXL implied (ai-images/ directory exists)
- Image generation tools in VAPI (485 tools)

### Tool Matrix

| Tool | Type | API Available | Quality (1-10) | Cost | License | Best For |
|------|------|:---:|:---:|------|---------|----------|
| **DALL-E 3 / GPT Image 1** | Paid API | ✅ REST | 8 | ~$0.04-0.12/img | OpenAI ToS | General-purpose, world knowledge, text rendering |
| **FLUX.2 [max]** | Paid API + Open Weights | ✅ REST | 9.5 | ~$0.04-0.06/img | BFL License | Best prompt adherence, 4MP photorealistic, multi-reference |
| **FLUX.2 [klein]** | Open Weights | ✅ REST | 8 | Sub-second inference | Apache 2.0 (open weights) | Real-time generation, production speed |
| **Stable Diffusion 3.5 Large** | Open + API | ✅ Stability API | 8.5 | ~$0.03-0.065/img | Stability License | Self-hosted, fine-tunable, diverse styles |
| **SD 3.5 Medium** | Open + API | ✅ Stability API | 7.5 | Lower | Community License | Consumer hardware, customizable |
| **SD 3.5 Turbo** | Open + API | ✅ Stability API | 7 | Lowest | Community License | 4-step generation, speed-critical |
| **Midjourney** | Paid (no official API) | ❌ Discord only | 9.5 | $10-60/mo subscription | Proprietary | Artistic quality, aesthetics (no API) |
| **Adobe Firefly API** | Paid API | ✅ REST | 8 | Credits-based | Adobe ToS (commercially safe) | Commercially safe images, enterprise |
| **Ideogram 3.0** | Paid API | ✅ REST | 8.5 | Credits-based | Proprietary | Text-in-image rendering, logos, posters |
| **Google Imagen 3** | Paid API | ✅ Vertex AI | 9 | ~$0.02-0.04/img | Google Cloud ToS | Photorealism, few artifacts |
| **Leonardo.ai** | Paid API | ✅ REST | 8.5 | Credits-based | Proprietary | Multi-model hub (FLUX, Kling, Phoenix, etc.) |

### Backend Comparison: ComfyUI vs Automatic1111

| Feature | ComfyUI | Automatic1111 (FORGE) |
|---------|---------|----------------------|
| **Architecture** | Node-based graph workflow | WebUI with settings panels |
| **API** | REST + WebSocket, JSON workflow format | REST API via `--api` flag |
| **Performance** | Superior — native batching, memory optimization | Good but heavier |
| **Extensibility** | 1000+ custom nodes, modular | Extensions ecosystem |
| **Workflow Reuse** | Saveable JSON workflows = API-callable | Less portable |
| **FLUX Support** | First-class | Via extensions (Forge) |
| **Recommended for Alfred** | ✅ **YES** — API-native, scriptable | Secondary option |

### Integration Recommendations
1. **Primary API**: FLUX.2 via BFL API (best quality-to-price ratio)
2. **Self-hosted**: ComfyUI backend with SD 3.5 + FLUX.2 [klein] for unlimited generation
3. **Fallback**: DALL-E 3 / GPT Image 1 (already integrated)
4. **Enterprise**: Adobe Firefly (commercially safe for white-label clients)
5. **Aggregator**: Leonardo.ai API as a multi-model gateway

### Implementation Path
```
Illustrator (Agent #83) → ComfyUI self-hosted backend
                        → BFL FLUX.2 API (cloud fallback)
                        → Stability API (SD 3.5)
                        → OpenAI GPT Image 1 (existing)
                        → Leonardo.ai (multi-model router)
```

---

## 2. VIDEO GENERATION

**EMBER Agent: #84 Filmmaker** (`generate_video`, `video_*`, `edit_*`)

### Current Alfred State
- No video generation capability identified
- Three.js game environments exist but no AI video creation
- Massive opportunity gap

### Tool Matrix

| Tool | Type | API Available | Quality (1-10) | Cost/Video | Duration | Resolution |
|------|------|:---:|:---:|------|----------|------------|
| **Sora** (OpenAI) | Paid App + API | ✅ API (2026) | 9 | $0.10-0.80/vid | Up to 60s | 1080p |
| **Kling 3.0** (Kuaishou) | Paid API | ✅ REST | 9.5 | ~$0.05-0.30/vid | 5-120s | 1080p, native audio |
| **Runway Gen-3/Gen-4** | Paid API | ✅ REST | 8.5 | $0.05/sec | 5-18s | 1080p |
| **Pika 2.0** | Paid API | ✅ REST | 8 | Credits-based | 3-10s | 1080p |
| **Luma Dream Machine (Ray3)** | Paid API | ✅ REST | 9 | Credits-based | 5-20s | 1080p, char consistency |
| **Stable Video Diffusion** | Open-source | Self-host only | 6.5 | GPU cost | 2-4s | 512×512–1024×576 |
| **CogVideo** (Tsinghua/ZhipuAI) | Open-source | Self-host only | 7 | GPU cost | 4-6s | Up to 720p |
| **HunyuanVideo** (Tencent) | Open-source | Self-host only | 8 | GPU cost | 5-10s | Up to 720p |
| **Wan2.1** (Alibaba) | Open-source | Self-host only | 8.5 | GPU cost | 5-10s | Up to 720p |
| **Google Veo 3.0/3.1** | Paid API | ✅ Vertex AI | 9.5 | Premium | 8-20s | 1080p, native audio |

### Quality Rankings (as of March 2026)
1. **Kling 3.0** — Best multimodal (audio + video native), deep storyboard control
2. **Veo 3.1** — Top motion realism, native sound
3. **Sora** — Hyperreal motion, character cast, remixing
4. **Luma Ray3** — Best character consistency, modify existing videos
5. **Wan2.1** — Best open-source option, competitive quality

### Integration Recommendations
1. **Primary**: Kling 3.0 API (best value, native audio, up to 2min)
2. **Premium**: Sora API or Veo 3.1 (when Alfred needs cinema quality)
3. **Self-hosted**: Wan2.1 or HunyuanVideo (unlimited use, GPU cost only)
4. **Editing**: Runway Gen-4 (best video editing/modification APIs)
5. **Budget**: Pika (cheapest for quick social clips)

### Implementation Path
```
Filmmaker (Agent #84) → Kling 3.0 API (primary generation)
                      → Sora API (premium tier)
                      → Wan2.1 self-hosted (unlimited batch jobs)
                      → Runway Gen-4 (editing/modification)
                      → FFmpeg (post-processing pipeline)
```

---

## 3. AUDIO/MUSIC GENERATION

**EMBER Agent: #85 Composer** (`generate_audio`, `music_*`, `sound_*`)

### Current Alfred State
- No music generation capability identified
- Voice/audio stack exists (TTS engines)
- Game environments need soundtracks, metaverse needs ambient audio

### Tool Matrix

| Tool | Type | API Available | Quality (1-10) | Cost | Music Style | License |
|------|------|:---:|:---:|------|-------------|---------|
| **Suno v4** | Paid API | ✅ REST (via partners) | 9 | $10-30/mo | Full songs with vocals | Proprietary, commercial on paid |
| **Udio** | Paid Web | ⚠️ Limited API | 9 | $10-30/mo | Full songs with vocals | Proprietary |
| **AudioCraft / MusicGen** (Meta) | Open-source | Self-host + HF | 7.5 | GPU cost | Instrumental, text-conditioned | MIT (code) / CC-BY-NC (weights) |
| **AudioGen** (Meta) | Open-source | Self-host + HF | 7 | GPU cost | Sound effects, environments | CC-BY-NC |
| **JASCO** (Meta) | Open-source | Self-host | 8 | GPU cost | Text + chords + drums conditioned | CC-BY-NC |
| **Bark** (Suno) | Open-source | Self-host + HF | 6.5 | GPU cost | Speech + music + SFX | MIT |
| **Stable Audio 2.0** | Paid API | ✅ Stability API | 8 | Credits-based | Long-form music (up to 3min) | Stability License |
| **AIVA** | Paid API | ✅ REST | 8.5 | €0-33/mo | Classical, film score, 250+ styles | Own copyright on Pro (€33/mo) |

### Use Case Mapping for Alfred

| Use Case | Best Tool | Reason |
|----------|-----------|--------|
| **Metaverse ambient music** | MusicGen/JASCO (self-hosted) | Infinite generation, no per-track cost |
| **Game battle music** | Stable Audio 2.0 + MusicGen | Loopable, style-conditioned |
| **Podcast intros** | Suno v4 | Full production quality with vocals |
| **Elevator/hold music** | AIVA | Classical/cinematic, own copyright |
| **Sound effects (SFX)** | AudioGen | Environment sounds, foley |
| **Marketing jingles** | Suno v4 or Udio | Professional quality, fast |
| **Background for voice cloning** | Stable Audio 2.0 | Clean instrumentals |

### Integration Recommendations
1. **Self-hosted primary**: MusicGen + JASCO (AudioCraft) for unlimited metaverse/game audio
2. **Premium songs**: Suno v4 API for full production tracks with vocals
3. **SFX**: AudioGen for environmental sounds, game effects
4. **Cinematic**: AIVA for film scores, pitch decks, presentations
5. **Background**: Stable Audio 2.0 API for quick instrumentals

### Implementation Path
```
Composer (Agent #85) → AudioCraft (MusicGen/JASCO) self-hosted → unlimited game/metaverse music
                     → Suno v4 API → full vocal tracks for marketing
                     → AudioGen → SFX for games/metaverse
                     → AIVA API → cinematic scores
                     → Stable Audio 2.0 → quick instrumentals
```

---

## 4. VOICE CLONING & TTS

**EMBER Agent: #88 Voice-Artist** (`voice_clone`, `tts_*`, `narrate_*`)

### Current Alfred State
- ✅ Voice cloning page (voice-cloning.php)
- ✅ Kokoro TTS
- ✅ Orpheus TTS
- ✅ Cartesia TTS
- ✅ ElevenLabs integration
- ✅ VAPI voice AI (485 tools)

### Tool Matrix

| Tool | Type | API | Quality (1-10) | Latency | Languages | License |
|------|------|:---:|:---:|---------|-----------|---------|
| **ElevenLabs** | Paid API | ✅ REST + WS | 9.5 | <300ms | 32+ | Proprietary |
| **PlayHT 2.0** | Paid API | ✅ REST + WS | 8.5 | <250ms | 30+ | Proprietary |
| **XTTS-v2** (Coqui) | Open-source | Self-host | 8 | <500ms | 16 | MPL-2.0 |
| **OpenVoice V2** (MyShell/MIT) | Open-source | Self-host | 7.5 | ~1s | 6 native | MIT |
| **RVC** (Retrieval Voice Conv.) | Open-source | Self-host | 8 | Real-time | Any | MIT |
| **So-VITS-SVC** | Open-source | Self-host | 7.5 | Near-RT | Any | MIT |
| **F5-TTS** | Open-source | Self-host | 9 | 253ms (server) | Multilingual | MIT (code) / CC-BY-NC (weights) |
| **CosyVoice** (Alibaba) | Open-source | Self-host | 8.5 | ~500ms | Chinese/English | Apache 2.0 |
| **Kokoro** (already in Alfred) | Open-source | ✅ Integrated | 8 | Fast | Multi | Apache 2.0 |
| **Orpheus** (already in Alfred) | Open-source | ✅ Integrated | 8.5 | Fast | English focus | Apache 2.0 |
| **Cartesia** (already in Alfred) | Paid API | ✅ Integrated | 9 | <100ms | English | Proprietary |

### Detailed Analysis

#### F5-TTS ★ TOP RECOMMENDATION
- **Stars**: 14.2k on GitHub
- **Key Feature**: Flow matching architecture, 253ms server latency, zero-shot voice cloning
- **Quality**: Comparable to ElevenLabs for many use cases
- **Training**: Can fine-tune on custom voices
- **Integration**: pip install, Gradio UI, CLI, TensorRT-LLM for production
- **Caveat**: Model weights are CC-BY-NC (non-commercial), but custom-trained models would be yours

#### OpenVoice V2 ★ BEST FREE COMMERCIAL
- **Stars**: 36k on GitHub
- **Key Feature**: Zero-shot cross-lingual voice cloning, granular style control
- **License**: MIT — fully free for commercial use
- **Supported Languages**: English, Spanish, French, Chinese, Japanese, Korean
- **Best For**: Adding voice cloning without API costs

#### XTTS-v2 (Coqui TTS)
- **Stars**: 44.7k on GitHub (entire TTS library)
- **Key Feature**: 1100+ language models via Fairseq, multi-speaker support
- **Streaming**: <200ms latency with streaming mode
- **Docker**: Ready-to-deploy containers available
- **Best For**: Maximum language coverage

### What Alfred Should Add
1. **F5-TTS** — Self-hosted voice cloning engine (quality rival to ElevenLabs)
2. **OpenVoice V2** — Free commercial-use voice cloning for white-label clients
3. **RVC** — Real-time voice conversion for live calls/streams

### Implementation Path
```
Voice-Artist (Agent #88) → ElevenLabs API (premium, existing)
                         → Cartesia (ultra-low latency, existing)
                         → F5-TTS self-hosted (quality cloning, new)
                         → OpenVoice V2 self-hosted (free commercial, new)
                         → RVC (real-time voice conversion, new)
                         → Kokoro/Orpheus (existing engines)
```

---

## 5. 3D GENERATION

**EMBER Agent: #89 Animator** (`animate_*`, `motion_*`, `render_*`)

### Current Alfred State
- Three.js/WebXR game environments (13 VR environments)
- No AI 3D model generation
- Critical gap for metaverse asset creation

### Tool Matrix

| Tool | Type | API | Quality (1-10) | Speed | Output Format | License |
|------|------|:---:|:---:|-------|---------------|---------|
| **Meshy** | Paid API | ✅ REST | 9 | ~60s | FBX, GLB, OBJ, STL, USDZ, BLEND | Proprietary |
| **TripoSR** (Tripo+Stability) | Open-source | Self-host | 7.5 | <0.5s on A100 | OBJ with vertex colors/textures | MIT |
| **InstantMesh** (TencentARC) | Open-source | Self-host | 8 | ~10s | OBJ with textures | Apache 2.0 |
| **OpenLRM** (3DTopia) | Open-source | Self-host | 7 | ~5s | OBJ/PLY | Apache 2.0 |
| **Point-E** (OpenAI) | Open-source | Self-host | 5.5 | ~30s | Point clouds → mesh | MIT |
| **Shap-E** (OpenAI) | Open-source | Self-host | 6 | ~30s | NeRF/mesh | MIT |
| **CSM (Common Sense Machines)** | Paid API | ✅ REST | 8.5 | ~30s | GLB, FBX | Proprietary |
| **Tripo 2.0** (Tripo3D) | Paid API | ✅ REST | 9 | ~10s | GLB, FBX, OBJ | Proprietary |
| **StablePoint3D** (Stability) | Open-source | Self-host | 7 | ~15s | Point cloud → mesh | Stability License |
| **Rodin Gen-2** (Microsoft) | Paid API | ✅ REST | 8.5 | ~60s | GLB, FBX | Proprietary |

### Meshy Deep Dive ★ TOP RECOMMENDATION FOR ALFRED
- **Features**: Text-to-3D, Image-to-3D, AI Texturing, Smart Remesh, Rigging + Animation
- **Formats**: FBX, GLB, OBJ, STL, 3MF, USDZ, BLEND
- **Animation Library**: 500+ game-ready motions (walks, jumps, fights, dances)
- **PBR Maps**: Diffuse, Roughness, Metallic, Normal maps
- **Plugins**: Blender, Unity, Unreal, Godot, Maya, 3ds Max
- **Security**: SOC2 Type II, ISO27001, GDPR certified
- **Enterprise**: Multi-team management, SSO, private licensing
- **API**: REST API for programmatic generation
- **Why Ideal**: Directly maps to Alfred's metaverse (Three.js/GLB format)

### Integration Recommendations
1. **Primary API**: Meshy API — full pipeline (generate → texture → rig → animate → export GLB)
2. **Self-hosted fast**: TripoSR — sub-second generation for rapid prototyping
3. **Self-hosted quality**: InstantMesh — better quality, Apache 2.0 license
4. **Pipeline**: Image (FLUX) → 3D (Meshy/InstantMesh) → Scene (Three.js) → Metaverse

### Implementation Path
```
Animator (Agent #89) → Meshy API (primary, full pipeline)
                     → TripoSR self-hosted (instant prototyping)
                     → InstantMesh self-hosted (quality generation)
                     → Three.js scene integration (existing)
                     → GLB → Metaverse pipeline (new)
```

### Metaverse Asset Pipeline
```
Text Prompt ─→ Meshy Text-to-3D ─→ Auto Rig ─→ Animation ─→ GLB Export
                                                               │
Image Upload ─→ Meshy Image-to-3D ─→ PBR Texture ────────────┘
                                                               │
                                         ┌─────────────────────┘
                                         ▼
                              Three.js Scene Loader
                                         │
                              ┌──────────┴───────────┐
                              ▼                      ▼
                         VR District            Game Environment
                        (Metaverse)            (Chess, Poker, etc.)
```

---

## 6. DOCUMENT AI

**EMBER Agents: #86 Writer** (`blog_*`, `article_*`), **#90 Editor** (`edit_*`, `proofread_*`)

### Current Alfred State
- Blog system exists (blog.php)
- Marketing copy generation implied
- No document parsing/OCR capabilities identified

### Document Parsing

| Tool | Type | Speed | Accuracy | Formats | License |
|------|------|-------|----------|---------|---------|
| **Marker** | Open-source Python | Fast | 9/10 | PDF→Markdown, preserves tables/equations | GPL-3.0 |
| **Docling** (IBM) | Open-source Python | Medium | 9/10 | PDF, DOCX, PPTX, HTML → JSON/Markdown | Apache 2.0 |
| **PyMuPDF** (fitz) | Open-source Python | Very Fast | 7/10 | PDF text/image extraction | AGPL-3.0 |
| **Unstructured** | Open-source + API | Medium | 8.5/10 | 15+ formats, auto-chunking for RAG | Apache 2.0 (OSS) |

### OCR

| Tool | Type | Speed | Accuracy | Languages | License |
|------|------|-------|----------|-----------|---------|
| **Tesseract 5** | Open-source C++ | Fast | 7/10 | 100+ | Apache 2.0 |
| **PaddleOCR** | Open-source Python | Fast | 8.5/10 | 80+ | Apache 2.0 |
| **Surya** | Open-source Python | Medium | 9/10 | 90+ (best for complex layouts) | GPL-3.0 |
| **Google Vision API** | Paid API | Fast | 9.5/10 | 100+ | Google ToS |

### PDF Generation

| Tool | Type | Use Case | Language | License |
|------|------|----------|----------|---------|
| **Puppeteer** | Open-source | HTML→PDF (Chrome headless) | Node.js | Apache 2.0 |
| **TCPDF** | Open-source | PHP-native PDF generation | PHP ✅ | LGPL-3.0 |
| **wkhtmltopdf** | Open-source | HTML/CSS→PDF | CLI | LGPL-3.0 |
| **Gotenberg** | Open-source | PDF API server (Docker) | REST API | MIT |
| **WeasyPrint** | Open-source | CSS-driven PDF layout | Python | BSD |

### Integration Recommendations
1. **Parsing**: Docling (Apache 2.0) + Marker for PDF→Markdown→RAG pipeline
2. **OCR**: PaddleOCR (best balance of speed, accuracy, language coverage)
3. **PDF Gen**: TCPDF (already PHP, matches Alfred's stack) + Puppeteer for complex layouts
4. **Pipeline**: Document → Parse → Chunk → Embed → RAG Knowledge Base

### Implementation Path
```
Writer (Agent #86)  → Docling (document ingestion)
Editor (Agent #90)  → PaddleOCR (image/scan text extraction)
                    → TCPDF (PDF report generation)
                    → Puppeteer (HTML→PDF for invoices, proposals)
                    → Unstructured (RAG chunking)
```

---

## 7. DESIGN/UI AUTOMATION

**EMBER Agent: #87 Designer** (`design_*`, `mockup_*`, `prototype_*`)

### Current Alfred State
- No design automation identified
- GoCodeMe editor exists but appears code-focused
- Marketplace templates exist

### Tool Matrix

| Tool | Type | API | Quality (1-10) | Use Case | License/Cost |
|------|------|:---:|:---:|----------|-------------|
| **Figma API** | Paid API | ✅ REST | 9 | Read/write Figma files, extract components | Free tier + $$ |
| **Canva API** | Paid API | ✅ REST (Connect) | 8 | Social media graphics, templates | Partnership required |
| **v0.dev** (Vercel) | Paid Web | ⚠️ Generate-only | 8.5 | Text→React/Next UI components | $20/mo |
| **Screenshot-to-Code** | Open-source | Self-host | 7.5 | Screenshot→HTML/React/Vue | MIT |
| **draw.io API** | Open-source | Self-host | 7 | Diagrams, flowcharts | Apache 2.0 |
| **Excalidraw** | Open-source | Self-host | 8 | Sketches, wireframes | MIT |
| **Penpot** | Open-source | ✅ Self-host + API | 8 | Full design tool (Figma alternative) | MPL-2.0 |

### Integration Recommendations
1. **Screenshot-to-Code**: Convert any design screenshot to working HTML/CSS for clients
2. **Penpot API**: Self-hosted Figma alternative for Alfred-native design workflow
3. **Figma API**: Read client Figma files, extract assets, auto-implement
4. **draw.io**: Automated diagram generation for documentation

### Implementation Path
```
Designer (Agent #87) → Screenshot-to-Code (design→code automation)
                     → Figma API (client file access)
                     → FLUX.2 (generate design concepts from text)
                     → Penpot API (self-hosted design editing)
                     → TCPDF/Puppeteer (design→PDF for proposals)
```

---

## 8. CONTENT MANAGEMENT

**EMBER Agent: #86 Writer** + **#91 Muse** (`blog_*`, `muse_*`, `brainstorm_*`)

### Current Alfred State
- Blog system (blog.php)
- Articles directory (articles/)
- Changelog, help, docs exist
- No headless CMS or automated pipeline

### Headless CMS Comparison

| Tool | Type | API | Language | Extensibility | License | DB |
|------|------|:---:|----------|---------------|---------|-----|
| **Strapi** | Open-source | ✅ REST + GraphQL | Node.js | Plugins | MIT (Community) | PostgreSQL, MySQL, SQLite |
| **Directus** | Open-source | ✅ REST + GraphQL | Node.js | Extensions, Flows | GPL-3.0 (BSL for cloud) | PostgreSQL, MySQL, SQLite, Oracle |
| **Payload CMS** | Open-source | ✅ REST + GraphQL | Node.js/TypeScript | Full Next.js integration | MIT | MongoDB, PostgreSQL |
| **Sanity** | Paid SaaS + OS client | ✅ GROQ + GraphQL | React | Portable text, schemas | Proprietary (free tier) | Hosted |
| **Ghost** | Open-source | ✅ REST + Content API | Node.js | Themes, integrations | MIT | MySQL |
| **WordPress (Headless)** | Open-source | ✅ REST + GraphQL (WPGraphQL) | PHP ✅ | 60k+ plugins | GPLv2 | MySQL |

### Automated Blogging Pipeline

```
                    ┌──────────────────────────────────┐
                    │     Alfred Content Pipeline       │
                    └──────────────────────────────────┘
                                    │
        ┌───────────────────────────┼───────────────────────────┐
        ▼                          ▼                            ▼
   Muse (#91)                 Writer (#86)                Editor (#90)
   brainstorm_*               blog_*, article_*           edit_*, proofread_*
   │                          │                            │
   ├─ Trend research          ├─ Draft generation          ├─ Grammar check
   ├─ Topic clustering        ├─ SEO optimization          ├─ Fact verification
   ├─ Keyword research        ├─ Tone matching             ├─ Readability score
   └─ Content calendar        └─ Multi-format output       └─ Plagiarism check
                                    │
                                    ▼
                          ┌──────────────────┐
                          │  Publishing       │
                          ├──────────────────┤
                          │ Ghost/WP API     │
                          │ Social scheduling│
                          │ Email newsletter │
                          │ RSS feed update  │
                          └──────────────────┘
```

### SEO Automation Tools

| Tool | Type | Cost | Features |
|------|------|------|----------|
| **Ahrefs API** | Paid API | $99+/mo | Keyword research, backlink analysis, rank tracking |
| **SemRush API** | Paid API | $129+/mo | SEO audit, keyword gap, content templates |
| **Yoast (WP)** | Plugin | Free/Premium | On-page SEO, schema markup |
| **SurferSEO** | Paid API | $69+/mo | Content optimization, NLP analysis |

### Integration Recommendations
1. **CMS**: Ghost (MIT, excellent API, built for publishing) or keep PHP blog + add API layer
2. **Pipeline**: Muse→Writer→Editor→Publisher automated chain
3. **SEO**: Ahrefs/SemRush API for keyword research → inject into Writer prompts
4. **Social**: Buffer/Hootsuite API for cross-posting

---

## 9. VIDEO EDITING (PROGRAMMATIC)

**EMBER Agent: #84 Filmmaker** (`generate_video`, `video_*`, `edit_*`)

### Tool Matrix

| Tool | Type | Language | API | Best For | License |
|------|------|----------|:---:|----------|---------|
| **FFmpeg** | Open-source CLI | C (CLI wrappers everywhere) | CLI/Library | Everything — transcode, cut, overlay, filters | LGPL/GPL |
| **Remotion** | Open-source | React/TypeScript | ✅ Programmatic | React-rendered videos (data-driven, templated) | BSL → MIT after 3 years |
| **Shotstack API** | Paid API | REST | ✅ REST | Cloud video rendering, templates | Pay-per-render |
| **Creatomate** | Paid API | REST | ✅ REST | Automated social video, templates | $9-99/mo |
| **MoviePy** | Open-source | Python | Library | Simple video editing scripts | MIT |
| **Editly** | Open-source | Node.js | CLI + Library | Declarative video editing, transitions | MIT |

### FFmpeg Integration (Already available on Alfred's Linux server)
```bash
# Combine AI-generated clips
ffmpeg -i intro.mp4 -i body.mp4 -i outro.mp4 -filter_complex concat=n=3:v=1:a=1 output.mp4

# Add AI-generated music to AI-generated video
ffmpeg -i video.mp4 -i music.mp3 -shortest -c:v copy -c:a aac final.mp4

# Generate social media formats
ffmpeg -i input.mp4 -vf "scale=1080:1920,setsar=1" -t 60 instagram_reel.mp4
```

### Remotion — ★ TOP RECOMMENDATION
- **Why**: React-based = familiar to web devs, data-driven video templates
- **Use Cases**: 
  - Automated marketing videos with client data
  - Investor reports as video presentations
  - Product demos from API data
  - Social media content at scale
- **Rendering**: Server-side via Lambda or self-hosted

### Integration Recommendations
1. **Core**: FFmpeg (already on server, handles all post-processing)
2. **Templates**: Remotion (programmatic video from React components)
3. **Cloud**: Shotstack API (when FFmpeg isn't enough, need hosted rendering)
4. **Quick Social**: Creatomate (template-driven social clips)

### Implementation Path
```
Filmmaker (Agent #84) → AI Video Gen (Kling/Sora) → raw clips
                      → FFmpeg (cut, merge, transcode, overlay)
                      → Remotion (templated data-driven videos)
                      → Shotstack API (cloud rendering when needed)
                      → MusicGen audio + FFmpeg merge (soundtrack)
```

---

## 10. PRESENTATION GENERATION

**EMBER Agent: #87 Designer** + **#86 Writer** + **#91 Muse**

### Current Alfred State
- No presentation generation identified
- Investor dashboard exists but HTML-based
- Strong opportunity for automated pitch decks

### Tool Matrix

| Tool | Type | Language | API | Quality | License |
|------|------|----------|:---:|---------|---------|
| **Slidev** | Open-source | Vue/Markdown | CLI | 8/10 — dev-focused | MIT |
| **Marp** | Open-source | Markdown→PPTX/PDF/HTML | CLI + VS Code | 7.5/10 | MIT |
| **Reveal.js** | Open-source | HTML/JS | Library | 9/10 — rich interactive | MIT |
| **Beautiful.ai** | Paid SaaS | Web | ⚠️ Limited API | 9/10 — auto-layout | $12-50/mo |
| **python-pptx** | Open-source | Python | Library | 7/10 — full PPTX control | MIT |
| **Gamma.app** | Paid SaaS | Web | ⚠️ No API | 8.5/10 — AI-powered | Proprietary |
| **SliDev + Puppeteer** | Open-source combo | Node + CLI | Full control | 8.5/10 | MIT |

### Recommended Architecture

```
Alfred Presentation Pipeline:
┌──────────────────┐     ┌──────────────────┐     ┌──────────────────┐
│  Muse (Agent #91)│     │ Writer (#86)     │     │ Designer (#87)   │
│  brainstorm_*    │     │ Structure content │     │ Visual design    │
│                  │     │                  │     │                  │
│ • Outline        │ ──→ │ • Slide content  │ ──→ │ • FLUX images    │
│ • Key messages   │     │ • Speaker notes  │     │ • Charts (D3.js) │
│ • Data points    │     │ • Flow logic     │     │ • Layout/theme   │
└──────────────────┘     └──────────────────┘     └──────────────────┘
                                                           │
                                                           ▼
                                                  ┌──────────────────┐
                                                  │ Reveal.js Render │
                                                  ├──────────────────┤
                                                  │ • HTML slides    │
                                                  │ • PDF export     │
                                                  │ • PPTX export    │
                                                  │ • Web hosting    │
                                                  └──────────────────┘
```

### Integration Recommendations
1. **Engine**: Reveal.js (most flexible, web-native, matches Alfred's PHP/JS stack)
2. **Export**: Marp (Markdown→PPTX for client delivery)
3. **Images**: FLUX.2 for slide illustrations
4. **Charts**: D3.js / Chart.js (already in Alfred ecosystem)
5. **PDF**: Puppeteer for HTML→PDF conversion

---

## 11. EMBER AGENT MAPPING SUMMARY

| # | Agent | Current State | Priority Additions | Impact |
|---|-------|--------------|-------------------|--------|
| **83** | **Illustrator** | DALL-E, SDXL | FLUX.2 API, ComfyUI self-hosted, Leonardo.ai gateway | 🟢 High |
| **84** | **Filmmaker** | ❌ None | Kling 3.0 API, Sora, FFmpeg pipeline, Remotion | 🔴 Critical |
| **85** | **Composer** | ❌ None | MusicGen/JASCO self-hosted, Suno v4 API | 🔴 Critical |
| **86** | **Writer** | Blog system exists | Docling parsing, SEO tools, content pipeline | 🟡 Medium |
| **87** | **Designer** | ❌ None | Screenshot-to-Code, Figma API, FLUX concepts | 🟡 Medium |
| **88** | **Voice-Artist** | ✅ 5 engines | F5-TTS, OpenVoice V2, RVC live conversion | 🟢 Enhance |
| **89** | **Animator** | Three.js scenes | Meshy API, TripoSR, InstantMesh, GLB pipeline | 🔴 Critical |
| **90** | **Editor** | ❌ None | PaddleOCR, grammar tools, fact-checking | 🟡 Medium |
| **91** | **Muse** | ❌ None | Trend research, brainstorming prompts, content calendar | 🟡 Medium |

---

## 12. PRIORITY IMPLEMENTATION PLAN

### Phase 1: Critical Gaps (Weeks 1-4)
These capabilities don't exist at all and unlock massive value:

| Priority | Tool | Agent | Integration Method | Estimated Effort |
|----------|------|-------|-------------------|-----------------|
| P0 | **Kling 3.0 API** | #84 Filmmaker | REST API wrapper in PHP | 3 days |
| P0 | **Meshy API** | #89 Animator | REST API wrapper, GLB→Three.js pipeline | 5 days |
| P0 | **MusicGen (AudioCraft)** | #85 Composer | Self-hosted Python service on AI server | 4 days |
| P1 | **FLUX.2 API** | #83 Illustrator | REST API via BFL, add to existing image tools | 2 days |
| P1 | **FFmpeg pipeline** | #84 Filmmaker | PHP exec() wrapper for post-processing | 2 days |
| P1 | **Suno v4 API** | #85 Composer | REST API for vocal tracks | 2 days |

### Phase 2: Enhancement (Weeks 5-8)
Improve existing capabilities:

| Priority | Tool | Agent | Benefit |
|----------|------|-------|---------|
| P2 | **F5-TTS** | #88 Voice-Artist | Self-hosted cloning rival to ElevenLabs |
| P2 | **OpenVoice V2** | #88 Voice-Artist | Free commercial voice cloning |
| P2 | **ComfyUI** | #83 Illustrator | Unlimited self-hosted image generation |
| P2 | **Remotion** | #84 Filmmaker | Programmatic template-based video creation |
| P2 | **TripoSR** | #89 Animator | Sub-second 3D generation (self-hosted) |

### Phase 3: Pipeline (Weeks 9-12)
Build automated creative pipelines:

| Priority | Pipeline | Agents | Description |
|----------|----------|--------|-------------|
| P3 | **Content Factory** | #86+#90+#91 | Muse→Writer→Editor→Publish automated blogging |
| P3 | **Video Factory** | #84+#85+#83 | Script→Images→Video→Music→Final cut pipeline |
| P3 | **Metaverse Assets** | #89+#83 | Text→Image→3D→Rig→Animate→GLB→Scene pipeline |
| P3 | **Pitch Deck Gen** | #87+#86+#91 | Brief→Outline→Content→Design→Reveal.js→PDF |

### Phase 4: Self-Hosted AI Server Stack (Ongoing)
Leverage Alfred's ai-servers/ infrastructure:

```
Alfred AI Server Rack:
├── GPU Server 1: ComfyUI (FLUX.2 klein, SD 3.5) ── Image Gen
├── GPU Server 2: AudioCraft (MusicGen, JASCO, AudioGen) ── Audio Gen
├── GPU Server 3: F5-TTS + OpenVoice V2 ── Voice Cloning
├── GPU Server 4: TripoSR + InstantMesh ── 3D Generation
├── GPU Server 5: Wan2.1 / HunyuanVideo ── Video Generation
└── CPU Server:   FFmpeg + Remotion + Puppeteer ── Post-Processing
```

---

## COST ANALYSIS

### Monthly API Costs (Estimated at Medium Volume)

| Service | Volume | Estimated Cost |
|---------|--------|---------------|
| FLUX.2 API | 10,000 images/mo | ~$500/mo |
| Kling 3.0 API | 1,000 videos/mo | ~$200/mo |
| Meshy API | 2,000 3D models/mo | ~$100/mo |
| Suno v4 | 500 songs/mo | ~$30/mo |
| ElevenLabs | Current usage | Current cost |
| AIVA Pro | Unlimited classical | €33/mo |
| **Total API** | | **~$900/mo** |

### Self-Hosted Costs (One-time + Hosting)

| Service | GPU Required | Monthly Hosting |
|---------|-------------|----------------|
| ComfyUI (FLUX+SD) | 1× A100 80GB or 2× RTX 4090 | ~$300/mo |
| AudioCraft | 1× A100 or 1× RTX 4090 | ~$150/mo |
| F5-TTS + OpenVoice | 1× RTX 4090 | ~$150/mo |
| TripoSR + InstantMesh | 1× A100 or 1× RTX 4090 | ~$150/mo |
| Video Gen (Wan2.1) | 1× A100 80GB | ~$300/mo |
| **Total Self-Hosted** | | **~$1,050/mo** |

**Break-even**: Self-hosted becomes cheaper than APIs at ~15,000 images, ~3,000 videos, ~5,000 3D models per month.

---

## TECHNOLOGY RADAR

### Adopt Now (Proven, API-ready)
- FLUX.2 (image), Kling 3.0 (video), Meshy (3D), ElevenLabs (voice), FFmpeg (video editing)

### Trial (Promising, test in staging)
- F5-TTS, MusicGen/JASCO, Remotion, Reveal.js, TripoSR

### Assess (Watch for maturity)
- Sora API (quality high but API still maturing), Wan2.1 (open-source video gen improving fast), CosyVoice

### Hold (Not ready yet)
- Point-E / Shap-E (quality too low for production), Midjourney (no API), So-VITS-SVC (licensing concerns)

---

*Research compiled March 6, 2026. All pricing and capabilities reflect current state and may change.*
*Recommendations prioritize Alfred's existing PHP/Node.js/Python polyglot stack and EMBER agent architecture.*
