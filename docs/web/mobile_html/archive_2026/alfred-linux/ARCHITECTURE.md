# Alfred Linux — Technical Architecture

## System Overview

Alfred Linux is a Debian-based Linux distribution with 6 custom layers on top of the standard Linux stack. Each layer is independently upgradeable and testable.

```
┌─────────────────────────────────────────────────────────────────────┐
│ Layer 6: WORLD BRIDGE — Real-World Control                         │
│   Smart Home │ Vehicle │ Robotics │ Agriculture │ VR/AR │ Gaming   │
├─────────────────────────────────────────────────────────────────────┤
│ Layer 5: ECONOMY — GSM Token + App Store                           │
│   Solana Wallet │ Mining │ App Store │ Compute Mesh │ Governance   │
├─────────────────────────────────────────────────────────────────────┤
│ Layer 4: SECURITY — Veil Protocol                                  │
│   Kyber-1024 PQ │ E2E Comms │ Encrypted Storage │ Secure Boot      │
├─────────────────────────────────────────────────────────────────────┤
│ Layer 3: INTELLIGENCE — Alfred AI                                  │
│   Voice Daemon │ Tool Engine │ Context Memory │ Local LLM │ Cloud  │
├─────────────────────────────────────────────────────────────────────┤
│ Layer 2: INTERFACE — Alfred Desktop Environment (ADE)              │
│   Shell │ Panel │ Launcher │ File Mgr │ Voice HUD │ Browser       │
├─────────────────────────────────────────────────────────────────────┤
│ Layer 1: FOUNDATION — Debian/Ubuntu Base                           │
│   Linux Kernel │ systemd │ Wayland │ PipeWire │ NetworkManager     │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Layer 1: Foundation

### Base System
- **Distro base:** Debian Stable (Bookworm+) or Ubuntu LTS
- **Kernel:** Linux 6.x with custom patches for:
  - Low-latency audio (voice processing)
  - IoT device drivers (Zigbee, Z-Wave USB)
  - GPU passthrough for VR workloads
  - Real-time scheduling for robotics

### Core Services
| Service | Component | Purpose |
|---------|-----------|---------|
| **Init** | systemd | Service management, boot |
| **Display** | Wayland (wlroots-based) | Modern, secure display protocol |
| **Audio** | PipeWire | Low-latency audio routing (voice + media) |
| **Network** | NetworkManager | WiFi, Ethernet, VPN, cellular |
| **Bluetooth** | BlueZ 5 | Device pairing, BLE beacons |
| **Storage** | BTRFS (default) | Snapshots, compression, self-healing |
| **Firewall** | nftables | Post-quantum-aware packet filtering |

### Package Management
```
APT (system packages)
  └── alfred-apt-repo → deb.alfredlinux.com
Flatpak (sandboxed apps)
  └── Alfred Store → store.alfredlinux.com
Alfred CLI (OS tooling)
  └── alfred install <package>
```

---

## Layer 2: Alfred Desktop Environment (ADE)

### Technology Stack
- **Compositor:** wlroots-based (Rust + C)
- **UI Toolkit:** GTK4 + libadwaita (customized)
- **Rendering:** Vulkan for compositing, OpenGL fallback
- **Language:** Rust (core), TypeScript (app framework), Python (extensions)

### Components

#### Shell (`desktop-environment/shell/`)
The core compositor and window manager.

```rust
// alfred-shell: wlroots-based Wayland compositor
// Key features:
// - Voice-activated window management ("Alfred, tile my windows")
// - Smart workspace routing (work, home, play)
// - Always-on voice HUD overlay
// - Dynamic theming based on time-of-day
// - Touch/gesture support for vehicle/tablet mode
```

**Window modes:**
- `desktop` — Traditional desktop with tiling/floating
- `vehicle` — Simplified dash mode, large touch targets
- `kiosk` — Single-app fullscreen (IoT hubs, POS)
- `vr` — Pass-through to WebXR runtime
- `terminal` — Voice-enhanced terminal mode

#### Panel (`desktop-environment/panel/`)
Top bar with system status.

```
┌──────────────────────────────────────────────────────────────┐
│ 🎤 Alfred  │  Workspaces  │     │  🏠 ☁️ 🔋 📶 🔊  10:42 │
└──────────────────────────────────────────────────────────────┘

Left:   Alfred menu (voice trigger) + workspace switcher
Center: Active window title
Right:  IoT status, weather, battery, network, volume, clock
```

#### Launcher (`desktop-environment/launcher/`)
App launcher triggered by voice or Super key.

```
╔══════════════════════════════════════════╗
║  🔍 Search or speak...                  ║
╠══════════════════════════════════════════╣
║  📌 Pinned                              ║
║  🌐 Alfred Browser   📁 Files           ║
║  💻 Terminal          ⚙️ Settings        ║
║  🎮 Games             🏠 Home Control   ║
╠══════════════════════════════════════════╣
║  🕐 Recent                              ║
║  Voice: "check greenhouse"              ║
║  Voice: "open fleet dashboard"          ║
║  App: Circuit Simulator                 ║
╚══════════════════════════════════════════╝
```

#### Voice HUD (`desktop-environment/voice-hud/`)
Always-visible voice interaction overlay.

```
States:
  IDLE:     Small mic icon in panel, pulsing subtly
  LISTENING: Waveform animation, full-width bar appears
  THINKING:  Orbital dots animation
  SPEAKING:  Alfred avatar + text response
  ERROR:     Red pulse + retry prompt
```

#### File Manager (`desktop-environment/file-manager/`)
- Nautilus-fork or custom GTK4 file manager
- **AI features:** "Alfred, find that PDF about taxes from last month"
- Veil-encrypted folders (right-click → Encrypt with Veil)
- GSM token awareness (shows wallet files, NFT assets)
- Cloud integration (GoSiteMe cloud storage)

#### Settings (`desktop-environment/settings/`)
Configuration panels, all voice-controllable.

| Panel | Controls |
|-------|----------|
| **Voice** | Wake word, voice profile, language, STT/TTS engine |
| **Security** | Veil encryption, firewall, Secure Boot, 2FA |
| **Smart Home** | Devices, scenes, automations, Matter setup |
| **Vehicle** | OBD2 connection, dash layout, fleet settings |
| **Robotics** | ROS2 nodes, fleet assignments, sensor config |
| **Farm** | Greenhouse zones, drone schedules, field maps |
| **Display** | Resolution, scaling, night mode, themes |
| **Sound** | PipeWire routing, Alfred voice, system sounds |
| **Network** | WiFi, VPN, Veil tunnels, mesh networking |
| **Economy** | GSM wallet, mining settings, app store account |
| **VR/AR** | Headset config, WebXR permissions, spatial UI |
| **System** | Updates, storage, backups, snapshots, about |

---

## Layer 3: Intelligence (Alfred AI)

### Voice Pipeline

```
┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────────┐
│ PipeWire │───▶│ Whisper  │───▶│ Claude / │───▶│ Kokoro   │
│ Audio In │    │ (Local)  │    │ Ollama   │    │ TTS      │
│          │    │ STT      │    │ LLM      │    │ (Local)  │
└──────────┘    └──────────┘    └──────────┘    └──────────┘
     │                │               │               │
     │           Text output     Response text    Audio output
     │                │               │               │
     │                └──────┬────────┘               │
     │                       │                        │
     │                ┌──────▼────────┐               │
     │                │  Tool Engine  │               │
     │                │  13,262+tools │               │
     │                └───────────────┘               │
     │                                                │
     └──────── PipeWire Audio Out ◄───────────────────┘
```

### Voice Daemon (`services/voice-daemon/`)
```
alfred-voiced (systemd service)
├── Wake word detection (local, always on)
├── Whisper STT (local GPU or CPU)
├── Context manager (conversation history + system state)
├── LLM router:
│   ├── Primary: Claude API (Anthropic)
│   ├── Fallback 1: Ollama (local Llama/Mistral)
│   ├── Fallback 2: Groq (cloud)
│   └── Fallback 3: OpenAI (cloud)
├── Tool executor (13,262+ registered tools)
├── Kokoro TTS (local, <100ms latency)
├── Piper TTS fallback (OHF-Voice/piper1-gpl fork, GPL)
└── D-Bus interface for desktop integration
```

### Tool Engine
All 13,262+ tools from GoSiteMe, plus Linux-native additions:

| Category | Count | Examples |
|----------|-------|---------|
| **System** | 200+ | Package management, service control, user admin |
| **Files** | 150+ | Search, organize, compress, encrypt, sync |
| **Network** | 100+ | Firewall, VPN, mesh, speed test, DNS |
| **Smart Home** | 500+ | Lights, locks, thermostats, cameras, energy |
| **Vehicle** | 200+ | OBD2 read, dash display, fleet route, maintenance |
| **Robotics** | 300+ | ROS2 commands, fleet deploy, sensor read, task program |
| **Farm** | 200+ | Drone launch, greenhouse adjust, soil read, weather |
| **VR/AR** | 150+ | Scene load, avatar control, world build, spatial audio |
| **Gaming** | 100+ | Game launch, controller config, stream, record |
| **Finance** | 300+ | GSM wallet, trading, invoicing, budgeting |
| **Legal** | 500+ | Document gen, case research, compliance check |
| **Health** | 400+ | Records, medication, vitals, appointment |
| **Communication** | 200+ | Call, message, email, conference, broadcast |
| **Developer** | 500+ | Code, deploy, CI/CD, debug, test, database |
| **Creative** | 300+ | Image, video, music, 3D model, animation |
| **Education** | 200+ | Tutor, quiz, course, research, translate |

### Context & Memory
```
~/.alfred/
├── memory/
│   ├── conversation-history.db    # SQLite: all voice interactions
│   ├── user-preferences.json     # Learned preferences
│   ├── device-state.json         # Current state of all devices
│   ├── routine-patterns.json     # Learned daily routines
│   └── world-model.json          # Spatial awareness (rooms, vehicles)
├── models/
│   ├── whisper-medium.bin        # Local STT model
│   ├── kokoro-v1.bin             # Local TTS model
│   └── llama-8b-alfred.gguf     # Fine-tuned local LLM
└── config/
    ├── voice.toml                # Voice settings
    ├── tools.toml                # Tool permissions
    └── integrations.toml         # Connected services
```

---

## Layer 4: Security (Veil Protocol)

### Encryption Architecture

```
┌─────────────────────────────────────────────┐
│              VEIL SECURITY LAYER            │
├─────────────────────────────────────────────┤
│                                             │
│  ┌─────────┐  ┌──────────┐  ┌───────────┐ │
│  │ Kyber   │  │ Dilithium│  │ SPHINCS+  │ │
│  │ 768     │  │ Sigs     │  │ Hash Sigs │ │
│  │ KEM     │  │          │  │ (backup)  │ │
│  └─────────┘  └──────────┘  └───────────┘ │
│        │            │             │        │
│        └──────┬─────┘─────────────┘        │
│               │                            │
│  ┌────────────▼─────────────┐              │
│  │   Hybrid TLS 1.3         │              │
│  │   X25519 + Kyber-1024     │              │
│  └──────────────────────────┘              │
│                                             │
│  ┌──────────────────────────┐              │
│  │   Full Disk Encryption   │              │
│  │   LUKS2 + PQ key wrap    │              │
│  └──────────────────────────┘              │
│                                             │
│  ┌──────────────────────────┐              │
│  │   Secure Boot Chain      │              │
│  │   UEFI → GRUB → Kernel   │              │
│  │   (Dilithium signatures) │              │
│  └──────────────────────────┘              │
└─────────────────────────────────────────────┘
```

### Security Features
| Feature | Implementation |
|---------|---------------|
| **Disk encryption** | LUKS2 with Kyber-1024 key wrapping |
| **Network encryption** | Hybrid TLS 1.3 (X25519 + Kyber-1024) |
| **File encryption** | Per-file Veil encryption (right-click) |
| **Communication** | E2E encrypted messaging, calls, video |
| **DNS** | DNS-over-HTTPS with Veil tunnel |
| **Boot chain** | Secure Boot with Dilithium signatures |
| **Voice auth** | Voiceprint verification (optional 2FA) |
| **Firewall** | nftables with AI-suggested rules |
| **Sandboxing** | Flatpak + Bubblewrap for all apps |
| **Updates** | Signed with Dilithium, verified on install |

### Veil Daemon (`services/veil-daemon/`)
```
alfred-veild (systemd service)
├── Key management (Kyber-1024 keypairs)
├── Certificate authority (local, for IoT devices)
├── Network tunnel (WireGuard + PQ key exchange)
├── File encryption engine (XChaCha20-Poly1305 + PQ KEM)
├── Secure boot verifier
└── D-Bus interface for apps
```

---

## Layer 5: Economy (GSM Token)

### Token Architecture

```
┌─────────────────────────────────────────┐
│            SOLANA BLOCKCHAIN            │
│                                         │
│  ┌─────────┐  ┌──────────┐  ┌───────┐ │
│  │ GSM SPL │  │ Jupiter  │  │ GoSi- │ │
│  │ Token   │──│ DEX      │──│ teMe  │ │
│  │         │  │ (SOL)    │  │ API   │ │
│  └─────────┘  └──────────┘  └───────┘ │
└─────────────────────────────────────────┘
        │
┌───────▼─────────────────────────────────┐
│         ALFRED LINUX LOCAL              │
│                                         │
│  ┌───────────┐  ┌────────────────────┐ │
│  │ GSM Miner │  │ Wallet (Solana)    │ │
│  │ sha256 PoW │  │ Balance, send,    │ │
│  │ idle CPU  │  │ receive, swap      │ │
│  └───────────┘  └────────────────────┘ │
│                                         │
│  ┌───────────┐  ┌────────────────────┐ │
│  │ App Store │  │ Compute Mesh       │ │
│  │ Buy/sell  │  │ Sell GPU/CPU time  │ │
│  │ with GSM  │  │ earn GSM          │ │
│  └───────────┘  └────────────────────┘ │
└─────────────────────────────────────────┘
```

### GSM Miner Daemon (`services/gsm-miner/`)
```
alfred-minerd (systemd service)
├── SHA-256 Proof of Work engine
├── Throttle control (idle CPU only, configurable)
├── Difficulty adjustment (network-based)
├── Earnings tracker
├── Auto-deposit to local Solana wallet
└── D-Bus interface for settings panel
```

---

## Layer 6: World Bridge (Real-World Integrations)

### Smart Home Hub (`integrations/smart-home/`)

```
┌──────────────────────────────────────────┐
│           ALFRED IOT HUB               │
├──────────────────────────────────────────┤
│                                          │
│  Protocols:                              │
│  ├── Matter 1.0+ (Thread/WiFi)           │
│  ├── Zigbee 3.0 (via USB coordinator)    │
│  ├── Z-Wave 700 (via USB controller)     │
│  ├── WiFi (direct IP devices)            │
│  ├── Bluetooth LE (beacons, sensors)     │
│  └── MQTT (custom/bridge)                │
│                                          │
│  Device Types:                           │
│  ├── Lights (dimming, color, scenes)     │
│  ├── Locks (lock/unlock, codes, logs)    │
│  ├── Thermostats (temp, schedule, eco)   │
│  ├── Cameras (stream, detect, record)    │
│  ├── Sensors (motion, door, leak, smoke) │
│  ├── Speakers (TTS, music, intercom)     │
│  ├── Blinds (open, close, tilt)          │
│  ├── Irrigation (zones, schedules)       │
│  ├── EV chargers (start/stop, schedule)  │
│  └── Custom (MQTT, REST, GPIO)           │
│                                          │
│  Automations:                            │
│  ├── Scene engine (voice: "movie time")  │
│  ├── Routine engine (time-based)         │
│  ├── AI-suggested automations            │
│  └── Energy optimization (solar/grid)    │
└──────────────────────────────────────────┘
```

### Vehicle Integration (`integrations/automotive/`)

```
Alfred Auto Architecture:
┌──────────┐     ┌──────────┐     ┌──────────┐
│ OBD2     │────▶│ CAN Bus  │────▶│ Alfred   │
│ Dongle   │     │ Decoder  │     │ Auto     │
│ (BT/USB) │     │          │     │ Service  │
└──────────┘     └──────────┘     └──────────┘
                                       │
                 ┌─────────────────────┼──────────────┐
                 │                     │              │
          ┌──────▼──────┐  ┌──────────▼───┐  ┌───────▼──────┐
          │ Dash UI     │  │ Maintenance  │  │ Fleet Mgr    │
          │ (Touch)     │  │ Predictor    │  │ Multi-vehicle│
          │ Nav, Media  │  │ AI diagnosis │  │ Tracking     │
          └─────────────┘  └──────────────┘  └──────────────┘
```

### Robotics Integration (`integrations/robotics/`)

```
Alfred Robotics Architecture:
┌──────────────────────────────────────────┐
│              ROS2 BRIDGE                │
├──────────────────────────────────────────┤
│                                          │
│  ┌────────────┐  ┌────────────────────┐ │
│  │ ROS2 Node  │  │ Fleet Controller   │ │
│  │ (Humble/   │  │ Deploy, monitor,   │ │
│  │  Iron)     │  │ redirect, report   │ │
│  └────────────┘  └────────────────────┘ │
│                                          │
│  ┌────────────┐  ┌────────────────────┐ │
│  │ Sensor Hub │  │ Task Programmer    │ │
│  │ Camera,    │  │ "Alfred, teach     │ │
│  │ LIDAR, IMU │  │  the robot to      │ │
│  │ GPS, Sonar │  │  stack boxes"      │ │
│  └────────────┘  └────────────────────┘ │
│                                          │
│  Voice Commands:                         │
│  "Alfred, deploy cleaning fleet zone B"  │
│  "Alfred, show me warehouse camera 3"    │
│  "Alfred, recall all drones to base"     │
└──────────────────────────────────────────┘
```

### Agriculture Integration (`integrations/agriculture/`)

```
Alfred Farm Architecture:
┌──────────────────────────────────────────┐
│            PRECISION AGRICULTURE        │
├──────────────────────────────────────────┤
│                                          │
│  ┌──────────────┐  ┌─────────────────┐  │
│  │ Drone Fleet  │  │ Greenhouse Ctrl │  │
│  │ DJI/Custom   │  │ Temp, humidity, │  │
│  │ Mapping,     │  │ irrigation,     │  │
│  │ spraying,    │  │ lighting,       │  │
│  │ monitoring   │  │ ventilation     │  │
│  └──────────────┘  └─────────────────┘  │
│                                          │
│  ┌──────────────┐  ┌─────────────────┐  │
│  │ Field Mapping│  │ Weather AI      │  │
│  │ GPS grid,    │  │ Hyperlocal      │  │
│  │ soil sensors,│  │ forecasting,    │  │
│  │ NDVI imaging │  │ frost warnings, │  │
│  │ crop health  │  │ irrigation rec  │  │
│  └──────────────┘  └─────────────────┘  │
│                                          │
│  Voice Commands:                         │
│  "Alfred, check humidity in greenhouse 2"│
│  "Alfred, launch survey drone field 4"   │
│  "Alfred, irrigate zone C for 30 min"    │
└──────────────────────────────────────────┘
```

### VR/AR Integration (`integrations/vr-ar/`)

```
Alfred VR/AR Architecture:
┌──────────────────────────────────────────┐
│            IMMERSIVE LAYER              │
├──────────────────────────────────────────┤
│                                          │
│  ┌──────────────┐  ┌─────────────────┐  │
│  │ WebXR        │  │ OpenXR Runtime  │  │
│  │ Runtime      │  │ Native headsets │  │
│  │ (Browser)    │  │ Quest, Index    │  │
│  └──────────────┘  └─────────────────┘  │
│                                          │
│  ┌──────────────┐  ┌─────────────────┐  │
│  │ Spatial UI   │  │ MetaDome Bridge │  │
│  │ 3D desktop   │  │ 114K agents,    │  │
│  │ mode, hand   │  │ 12 departments, │  │
│  │ tracking     │  │ civilization    │  │
│  └──────────────┘  └─────────────────┘  │
│                                          │
│  ┌──────────────┐  ┌─────────────────┐  │
│  │ Games Engine │  │ Social VR       │  │
│  │ Native +     │  │ Voice chat,     │  │
│  │ Proton +     │  │ avatars,        │  │
│  │ WebXR        │  │ shared spaces   │  │
│  └──────────────┘  └─────────────────┘  │
│                                          │
│  Voice in VR:                            │
│  "Alfred, load dungeon master campaign"  │
│  "Alfred, invite team to war room"       │
│  "Alfred, render my farm in 3D"          │
└──────────────────────────────────────────┘
```

---

## D-Bus API (Inter-Service Communication)

All Alfred services communicate over D-Bus:

```
org.alfredlinux.voice       # Voice daemon
org.alfredlinux.veil        # Encryption daemon
org.alfredlinux.iot         # Smart home hub
org.alfredlinux.auto        # Vehicle service
org.alfredlinux.fleet       # Robot fleet
org.alfredlinux.farm        # Agriculture
org.alfredlinux.gsm         # Token economy
org.alfredlinux.store       # App store
org.alfredlinux.vr          # VR/AR runtime
org.alfredlinux.shell       # Desktop environment
```

This allows any component to call any other:
```python
# Example: Voice command triggers IoT
# User: "Alfred, lock the front door"
# voice-daemon → tool-engine → iot-hub → zigbee → lock

# D-Bus flow:
bus.call("org.alfredlinux.iot", "DeviceAction", {
    "device_id": "front-door-lock",
    "action": "lock",
    "source": "voice"
})
```

---

## Build System

### ISO Build (Debian live-build)
```
distro/live-build/
├── auto/
│   ├── config        # Build configuration
│   ├── build         # Build script
│   └── clean         # Cleanup script
├── config/
│   ├── package-lists/
│   │   ├── alfred-desktop.list.chroot    # Desktop packages
│   │   ├── alfred-server.list.chroot     # Server packages
│   │   └── alfred-iot.list.chroot        # IoT packages
│   ├── includes.chroot/                  # Files to include in rootfs
│   ├── hooks/
│   │   ├── live/                         # Live hooks
│   │   └── normal/                       # Install hooks
│   └── preseed/                          # Automated install answers
└── Makefile
```

### CI/CD Pipeline
```yaml
# github/workflows/build-iso.yml
triggers: [push to main, weekly schedule]
jobs:
  build-desktop-amd64:
  build-desktop-arm64:
  build-server-amd64:
  build-iot-arm64:
  test-vm:           # Automated VM boot test
  publish:           # Upload to alfredlinux.com/download
```

---

## Performance Targets

| Metric | Target |
|--------|--------|
| Boot to desktop | < 8 seconds (NVMe) |
| Voice response (wake to answer) | < 1.5 seconds |
| Voice response (local LLM) | < 3 seconds |
| IoT device response | < 200ms |
| Memory (idle desktop) | < 1.5 GB |
| Disk (base install) | < 8 GB |
| Battery impact (voice daemon) | < 3% daily |

---

## Platform Variants

| Variant | Domain | Target Hardware | Key Differences |
|---------|--------|----------------|----------------|
| **Alfred Desktop** | [alfredlinux.com](https://alfredlinux.com) | x86_64 / ARM64 PCs | Full ADE, all integrations |
| **Alfred Server** | [alfredlinux.com](https://alfredlinux.com) | Servers, cloud | Headless, voice CLI, fleet |
| **Alfred IoT** | [alfredlinux.com](https://alfredlinux.com) | Raspberry Pi, embedded | Minimal, IoT hub only |
| **Alfred Mobile** | [alfred-mobile.com](https://alfred-mobile.com) | PinePhone, custom ARM | Touch ADE, haptics, cellular, tap-to-pay GSM |
| **Quantum Linux** | [quantum-linux.com](https://quantum-linux.com) | Enterprise endpoints | White-label, compliance, fleet management |

All variants share Layers 1–5 (Foundation, ADE core, Intelligence, Security, Economy). Layer 6 (World Bridge) modules are selectively included per variant.

---

*Version 1.1 — March 11, 2026*
