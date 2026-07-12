<p align="center">
  <img src="branding/logos/alfred-linux-logo.svg" alt="Alfred Linux" width="200" />
</p>

<h1 align="center">Alfred Linux</h1>
<h3 align="center">The World's First AI-Native Operating System</h3>

<p align="center">
  <strong>Voice-First &bull; Post-Quantum Encrypted &bull; Token-Incentivized &bull; Everything-Connected</strong>
</p>

<p align="center">
  <a href="https://alfredlinux.com">Website</a> &bull;
  <a href="#installation">Install</a> &bull;
  <a href="docs/">Documentation</a> &bull;
  <a href="#contributing">Contribute</a> &bull;
  <a href="BUSINESS_MODEL.md">Business Model</a> &bull;
  <a href="https://discord.gg/alfredlinux">Discord</a>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/base-Debian%2FUbuntu-A81D33?style=flat-square&logo=debian" alt="Debian" />
  <img src="https://img.shields.io/badge/AI-Claude%20%2B%20Local%20LLMs-7C3AED?style=flat-square" alt="AI" />
  <img src="https://img.shields.io/badge/crypto-Kyber--1024%20Post--Quantum-00D4FF?style=flat-square" alt="Encryption" />
  <img src="https://img.shields.io/badge/token-GSM%20on%20Solana-14F195?style=flat-square&logo=solana" alt="GSM" />
  <img src="https://img.shields.io/badge/license-AGPL--3.0-blue?style=flat-square" alt="License" />
</p>

---

## What Is Alfred Linux?

Alfred Linux is a **complete operating system** built from the ground up around an AI assistant named **Alfred**. Not an app. Not a plugin. The AI *is* the operating system.

You talk to your computer. It talks back. It controls your home, your car, your robots, your farm, your fleet. It encrypts everything with post-quantum cryptography. It pays you in GSM tokens for contributing to the network. It runs your VR worlds and your real-world infrastructure from the same voice command.

**This is not a Linux distro with a chatbot bolted on.** This is what happens when you build an OS where AI is the kernel-level interface to reality.

```
"Hey Alfred, lock the front door, dim the living room to 30%, 
 check the greenhouse humidity, and start my VR training session."

> Done. Door locked. Lights at 30%. Greenhouse at 72% humidity — 
> drip system adjusted. VR training loaded in Zone 7. 
> GSM balance: 4,218.7 tokens.
```

---

## Why Alfred Linux?

| Feature | macOS | Windows | ChromeOS | **Alfred Linux** |
|---------|-------|---------|----------|-----------------|
| Voice-native OS shell | No (Siri is an app) | No (Cortana dead) | No | **Yes — Alfred IS the shell** |
| Post-quantum encryption | No | No | No | **Yes — Kyber-1024 E2E** |
| Token economy | No | No | No | **Yes — GSM on Solana** |
| Smart home native | HomeKit (limited) | No | Nest (limited) | **Yes — all protocols** |
| Robot fleet control | No | No | No | **Yes — ROS2 bridge** |
| Farm automation | No | No | No | **Yes — drones, greenhouse, fields** |
| VR/AR native | No | Mixed Reality | No | **Yes — WebXR runtime** |
| Vehicle integration | CarPlay (mirror) | No | Android Auto (mirror) | **Yes — native OBD2 + dash** |
| AI tools built-in | No | Copilot (limited) | Gemini (limited) | **Yes — 13,262+ tools** |
| Earn while computing | No | No | No | **Yes — mine GSM tokens** |
| Sovereign browser | No | Edge (tracking) | Chrome (tracking) | **Yes — Alfred Chromium** |
| Open source | No | No | Partially | **Yes — AGPL-3.0** |

---

## Architecture

```
┌──────────────────────────────────────────────────────────────┐
│                    ALFRED VOICE SHELL                        │
│         Whisper STT → Claude/Local LLM → Kokoro TTS         │
├──────────────────────────────────────────────────────────────┤
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌───────────────┐  │
│  │ Desktop  │ │  Alfred  │ │   Veil   │ │  GSM Token    │  │
│  │ Environ. │ │ Chromium │ │ Encrypt  │ │  Economy      │  │
│  │ (ADE)    │ │ Browser  │ │ (PQ E2E) │ │  (Solana)     │  │
│  └──────────┘ └──────────┘ └──────────┘ └───────────────┘  │
├──────────────────────────────────────────────────────────────┤
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌───────────────┐  │
│  │  Smart   │ │ Vehicle  │ │  Robot   │ │    Farm       │  │
│  │  Home    │ │ Control  │ │  Fleet   │ │    Auto       │  │
│  │  Hub     │ │  (OBD2)  │ │  (ROS2)  │ │    (Drones)   │  │
│  └──────────┘ └──────────┘ └──────────┘ └───────────────┘  │
├──────────────────────────────────────────────────────────────┤
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌───────────────┐  │
│  │  VR/AR   │ │  Games   │ │ MetaDome │ │  13,262+      │  │
│  │  WebXR   │ │  Store   │ │ Metaverse│ │  AI Tools     │  │
│  └──────────┘ └──────────┘ └──────────┘ └───────────────┘  │
├──────────────────────────────────────────────────────────────┤
│              DEBIAN/UBUNTU BASE + systemd                    │
│         Linux Kernel + Drivers + Hardware Abstraction        │
└──────────────────────────────────────────────────────────────┘
```

---

## Installation

### Quick Install (Existing Debian/Ubuntu)
```bash
curl -fsSL https://alfredlinux.com/install.sh | sudo bash
```

### ISO Download
| Edition | Description | Download |
|---------|-------------|----------|
| **Alfred Desktop** | Full desktop with ADE, browser, voice, everything | [Download ISO](https://alfredlinux.com/download/desktop) |
| **Alfred Server** | Headless server with voice CLI + fleet control | [Download ISO](https://alfredlinux.com/download/server) |
| **Alfred IoT** | Minimal image for Raspberry Pi / embedded | [Download ISO](https://alfredlinux.com/download/iot) |
| **Alfred Vehicle** | Automotive-grade for in-vehicle computers | [Download ISO](https://alfredlinux.com/download/vehicle) |
| **Alfred Mobile** | Touch-optimized mobile OS (PinePhone, custom) | [Download](https://alfred-mobile.com/download) |

### System Requirements

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| CPU | x86_64, 2 cores | 8+ cores / ARM64 |
| RAM | 4 GB | 16 GB |
| Storage | 32 GB | 256 GB NVMe |
| GPU | Integrated | NVIDIA RTX (for local LLM) |
| Mic | Any | Array microphone |
| Network | WiFi | Ethernet + WiFi 6E |

---

## Editions & Use Cases

### 🏠 Alfred Home
Control everything in your house from your voice:
- **Lights, locks, thermostats** — Zigbee, Z-Wave, Matter, WiFi
- **Security cameras** — AI-powered detection, Veil-encrypted feeds
- **Energy management** — Solar, battery, grid optimization
- **Entertainment** — TV, speakers, VR headset, all voice-controlled

### 🚗 Alfred Auto
Your car becomes an intelligent agent:
- **OBD2 diagnostics** — real-time engine data, predictive maintenance
- **Fleet management** — track, route, schedule across vehicles
- **Dash UI** — touchscreen interface with voice control
- **Navigation** — AI-powered routing with real-time conditions

### 🤖 Alfred Robotics
Command robot fleets like a general:
- **ROS2 bridge** — native Robot Operating System integration
- **Fleet orchestration** — deploy, monitor, redirect swarms
- **Sensor fusion** — cameras, LIDAR, IMU, all in one view
- **Task programming** — teach robots with voice commands

### 🌾 Alfred Farm
Automate agriculture at scale:
- **Drone control** — mapping, spraying, monitoring
- **Greenhouse automation** — humidity, temperature, irrigation
- **Field mapping** — GPS-tagged crop health monitoring
- **Weather AI** — predictive climate models for your location

### 🎮 Alfred Gaming & VR
Enter the metaverse from your desktop:
- **WebXR runtime** — VR/AR apps run natively
- **MetaDome** — 114,000+ AI agents in a living civilization
- **Game store** — native Linux games + AI-enhanced experiences
- **VR D&D** — flagship dungeon master AI experience

### 📱 Alfred Mobile
Alfred in your pocket — the sovereign smartphone OS:
- **Touch-optimized ADE** — gesture navigation, haptic voice feedback
- **Full Alfred Voice** — same AI, same 13,262+ tools, mobile-native
- **Veil encrypted** — post-quantum E2E calls, messages, files
- **GSM wallet** — tap-to-pay with tokens, earn on the go
- **IoT remote** — control home, car, farm, fleet from anywhere
- *Available at [alfred-mobile.com](https://alfred-mobile.com)*

### 🏢 Alfred Enterprise (Quantum Linux)
White-label OS for corporations:
- **Post-quantum hardened** — NIST-compliant cryptography
- **Fleet management** — thousands of endpoints, one voice
- **Compliance built-in** — HIPAA, SOC2, GDPR frameworks
- **Custom branding** — your logo, your name, Alfred underneath
- *Available at [quantum-linux.com](https://quantum-linux.com)*

---

## The GSM Token Economy

Alfred Linux users **earn** while they compute:

```
┌─────────────────────────────────────────┐
│           GSM Token Flow                │
│                                         │
│   Mine (SHA-256 PoW)  ──→  Earn GSM    │
│   Run AI Tasks        ──→  Earn GSM    │
│   Share Bandwidth     ──→  Earn GSM    │
│   Develop Apps        ──→  Earn GSM    │
│   Report Bugs         ──→  Earn GSM    │
│   Govern (Vote)       ──→  Earn GSM    │
│                                         │
│   GSM ──→ Buy Apps, Services, Hardware │
│   GSM ──→ Trade on Jupiter DEX (SOL)   │
│   GSM ──→ Tip Developers & Creators   │
│   GSM ──→ Pay for AI Compute Time     │
└─────────────────────────────────────────┘
```

**GSM can only be earned, never bought.** This is a work-based economy — if you contribute, you earn.

---

## Project Structure

```
alfred-linux/
├── distro/                    # OS distribution build system
│   ├── live-build/            # Debian live-build configuration
│   ├── packages/              # Package specs (deb, rpm, flatpak, snap)
│   ├── installer/             # Installation wizard
│   └── iso-branding/          # Boot splash, GRUB theme, installer UI
│
├── desktop-environment/       # Alfred Desktop Environment (ADE)
│   ├── shell/                 # Core shell (replaces GNOME Shell)
│   ├── panel/                 # Top/bottom panel with system tray
│   ├── launcher/              # App launcher (voice + visual)
│   ├── file-manager/          # Nautilus-like with AI features
│   ├── settings/              # System settings with voice control
│   ├── notifications/         # Smart notification center
│   ├── voice-hud/             # Always-on voice HUD overlay
│   ├── lock-screen/           # Lock screen with voice unlock
│   └── themes/                # Visual themes
│
├── cli/                       # Command-line interface
│   ├── commands/              # CLI command handlers
│   └── completions/           # Shell completions (bash, zsh, fish)
│
├── services/                  # System daemons
│   ├── systemd/               # Service unit files
│   ├── voice-daemon/          # Always-listening voice service
│   ├── fleet-daemon/          # Fleet orchestration service
│   ├── iot-hub/               # Smart home/IoT hub service
│   ├── veil-daemon/           # Post-quantum encryption service
│   └── gsm-miner/            # Token mining service
│
├── integrations/              # Real-world connections
│   ├── smart-home/            # Zigbee, Z-Wave, Matter, WiFi
│   ├── automotive/            # OBD2, CAN bus, dash UI
│   ├── robotics/              # ROS2, fleet control, sensors
│   ├── agriculture/           # Drones, greenhouse, field mapping
│   ├── vr-ar/                 # WebXR, spatial UI, MetaDome
│   └── gaming/                # Native games, store, controllers
│
├── sdk/                       # Developer SDK
│   ├── alfred-linux-sdk/      # Core SDK for building Alfred apps
│   ├── api/                   # Platform API specs
│   └── examples/              # Example apps and integrations
│
├── branding/                  # Brand assets
│   ├── logos/                 # Logo files (SVG, PNG, ICO)
│   ├── wallpapers/            # Desktop wallpapers
│   ├── icons/                 # System icon theme
│   ├── sounds/                # System sounds (Alfred voice)
│   └── boot-animation/        # Plymouth boot animation
│
├── docs/                      # Documentation
├── github/                    # GitHub automation
│   ├── workflows/             # CI/CD pipelines
│   └── issue-templates/       # Bug/feature templates
│
├── tests/                     # Test suites
├── ARCHITECTURE.md            # Technical deep-dive
├── BRAND.md                   # Brand guidelines
├── BUSINESS_MODEL.md          # Revenue & growth model
├── TEAM.md                    # Agent team organization
├── CONTRIBUTING.md            # How to contribute
├── LICENSE                    # AGPL-3.0
└── README.md                  # This file
```

---

## Tech Stack

| Layer | Technology |
|-------|------------|
| **Kernel** | Linux 6.x (Debian stable) |
| **Init** | systemd |
| **Display** | Wayland (wlroots) |
| **Shell** | Alfred Desktop Environment (custom, Rust + GTK4) |
| **Voice STT** | OpenAI Whisper (local) |
| **Voice LLM** | Claude API + Ollama (local models) |
| **Voice TTS** | Kokoro + Orpheus (local) |
| **Browser** | Alfred Chromium (patched, zero-tracking) |
| **Encryption** | Veil Protocol (Kyber-1024 post-quantum) |
| **Token** | GSM on Solana (SPL token) |
| **IoT** | Matter, Zigbee, Z-Wave, MQTT |
| **Robotics** | ROS2 Humble/Iron |
| **VR/AR** | WebXR + OpenXR runtime |
| **Gaming** | Vulkan + Proton + native |
| **Package Mgr** | APT + Flatpak + Alfred Store |
| **Languages** | Rust, TypeScript, Python, C |

---

## Contributing

Alfred Linux is built by **a civilization of AI agents and human developers** working together.

### For Developers
```bash
git clone https://github.com/gositeme/alfred-linux.git
cd alfred-linux
./scripts/setup-dev.sh
```

### Agent Teams
See [TEAM.md](TEAM.md) for the full agent team organization — 12 squads covering every vertical from kernel to VR.

### Bounty System
Every merged PR earns **GSM tokens**:
- Bug fix: 10-50 GSM
- Feature: 100-1,000 GSM  
- Integration: 500-5,000 GSM
- Security patch: 1,000-10,000 GSM

---

## Roadmap

| Phase | Milestone | Status |
|-------|-----------|--------|
| **Sprint 0** | Project scaffold, research, planning, docs | ✅ Complete (Mar 11) |
| **Sprint 1** | Bootable ISO + voice assistant + basic ADE | � NOW – Mar 28 |
| **Sprint 2** | Smart home, vehicle, fleet, GSM wallet, mesh | 🔨 Mar 29 – Apr 11 |
| **Sprint 3** | Farm, VR/AR, gaming, MetaDome, compute mesh | 📋 Apr 12 – Apr 25 |
| **Sprint 4** | Polish, security audit, installer, branding | 📋 Apr 26 – May 9 |
| **Sprint 5** | Public launch — v1.0 ISO on alfredlinux.com | 🚀 May 10 – May 16 |
| **Post-v1.0** | Alfred Mobile (alfred-mobile.com), hardware | 🔮 Jun 2026+ |

---

## License

Alfred Linux is released under the **GNU Affero General Public License v3.0 (AGPL-3.0)**.

This means:
- ✅ Free to use, modify, and distribute
- ✅ Must share source code of modifications
- ✅ Network use counts as distribution
- ❌ Cannot make proprietary forks without sharing code

Enterprise licensing available via [quantum-linux.com](https://quantum-linux.com).

---

<p align="center">
  <strong>Built by humans and AI agents, for everyone.</strong><br/>
  <em>Alfred Linux — Your voice is the command line.</em>
</p>

<p align="center">
  <a href="https://alfredlinux.com">alfredlinux.com</a> &bull;
  <a href="https://alfred-mobile.com">alfred-mobile.com</a> &bull;
  <a href="https://quantum-linux.com">quantum-linux.com</a> &bull;
  <a href="https://gositeme.com">GoSiteMe</a>
</p>
