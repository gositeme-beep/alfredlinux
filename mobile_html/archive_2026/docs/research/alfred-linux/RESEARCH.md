# Alfred Linux — Technology Research

## Overview
This document catalogs the open-source technologies evaluated for integration into Alfred Linux. Each technology has been assessed for maturity, community health, licensing compatibility, and alignment with Alfred Linux's architecture.

---

## Desktop Environment & Compositor

### COSMIC Desktop (System76)
- **Status**: Primary reference implementation
- **Language**: Rust
- **Compositor**: Smithay-based (iced-sctk)
- **License**: GPL-3.0
- **Relevance**: Closest existing project to ADE vision. Full Rust desktop with custom toolkit.
- **Key Takeaways**:
  - Proves Rust desktop environment is viable at production quality
  - Their libcosmic toolkit is well-architected
  - App ecosystem growing (COSMIC Files, Editor, Terminal, Store)
  - Wayland-native, no X11 dependency
- **Integration Plan**: Study architecture, don't fork — build ADE independently using same foundations (Smithay + iced)

### Smithay
- **Status**: Core dependency candidate
- **Language**: Rust
- **Role**: Wayland compositor library
- **License**: MIT
- **Relevance**: Foundation for ADE's compositor. Handles Wayland protocol, input, output management.
- **Maturity**: Active development, used by COSMIC and other projects
- **Integration Plan**: Use as compositor foundation, extend with Alfred-specific protocols

### Niri
- **Status**: Evaluated, reference only
- **Language**: Rust
- **Role**: Scrollable tiling Wayland compositor
- **License**: GPL-3.0
- **Relevance**: Innovative window management approach (infinite horizontal scroll)
- **Key Takeaways**: Interesting UX paradigm but too opinionated for general-purpose ADE
- **Integration Plan**: Borrow scrolling workspace concept as optional ADE mode

### Hyprland
- **Status**: Evaluated, reference only
- **Language**: C++
- **Role**: Dynamic tiling Wayland compositor
- **License**: BSD-3-Clause
- **Relevance**: Popular, visually impressive, smooth animations
- **Key Takeaways**:
  - Excellent animation system worth studying
  - Plugin architecture is flexible
  - C++ codebase doesn't align with Rust direction
- **Integration Plan**: Reference for animation and visual effects implementation in ADE

---

## GUI Frameworks

### Iced
- **Status**: Primary GUI toolkit candidate
- **Language**: Rust
- **License**: MIT
- **Relevance**: Elm-inspired reactive GUI framework. Used by COSMIC for applications.
- **Strengths**:
  - Pure Rust, no C bindings
  - Reactive architecture fits AI-driven UI updates
  - GPU-accelerated rendering
  - Cross-platform (also runs on web via WASM)
- **Weaknesses**:
  - Still maturing (pre-1.0)
  - Accessibility support incomplete
  - Some performance gaps for complex layouts
- **Integration Plan**: Primary toolkit for ADE applications and system UI

### Clay
- **Status**: Evaluated for specific use cases
- **Language**: C (single header)
- **License**: Zlib
- **Relevance**: High-performance UI layout library
- **Strengths**:
  - Microsecond layout calculations
  - Zero dependencies
  - Memory-safe by design (arena allocator)
- **Weaknesses**:
  - Layout only — no rendering
  - C library, needs Rust bindings
- **Integration Plan**: Potential use for performance-critical layout calculations (agent dashboard, fleet view)

---

## AI & Machine Learning

### Whisper (OpenAI)
- **Status**: Selected for voice input
- **Model**: whisper.cpp (C++ port) / whisper-rs (Rust bindings)
- **License**: MIT
- **Role**: Speech-to-text for Alfred voice commands
- **Performance**: Real-time on CPU (base model), near-real-time for larger models
- **Integration Plan**: Core component of Alfred voice pipeline, runs locally

### Kokoro TTS
- **Status**: Selected for voice output
- **Role**: Text-to-speech for Alfred responses
- **Relevance**: High-quality, local, offline-capable TTS
- **Integration Plan**: Default voice for Alfred assistant, customizable voice profiles

### Ollama
- **Status**: Selected for local LLM
- **Role**: Local large language model runtime
- **License**: MIT
- **Relevance**: Run AI models locally without cloud dependency
- **Integration Plan**: Backend for Alfred's natural language understanding, configurable models

### Claude / Anthropic API
- **Status**: Optional cloud AI
- **Role**: Enhanced AI capabilities when user opts in
- **Integration Plan**: Optional cloud backend for complex reasoning, user must explicitly enable

---

## Security

### Post-Quantum Cryptography
- **Kyber-1024**: Selected for key encapsulation (NIST standard)
- **Dilithium**: Selected for digital signatures
- **SPHINCS+**: Backup signature scheme (hash-based, conservative)
- **Implementation**: Via liboqs (Open Quantum Safe) + Rust bindings
- **Integration Plan**: Veil security layer uses PQC for all new key exchanges and signatures

### WireGuard
- **Status**: Selected for VPN
- **License**: GPL-2.0
- **Relevance**: Fast, modern, simple VPN with minimal attack surface
- **Integration Plan**: Built into Veil for secure networking, one-click VPN

---

## IoT & Robotics

### ROS 2 (Robot Operating System)
- **Status**: Evaluated for Alfred Vehicle and Robot editions
- **License**: Apache-2.0
- **Relevance**: Standard framework for robotics, sensor fusion, navigation
- **Integration Plan**: Optional package for Alfred IoT/Vehicle editions

### Matter / Thread
- **Status**: Evaluated for IoT connectivity
- **Relevance**: Emerging smart home standards (backed by Apple, Google, Amazon)
- **Integration Plan**: Alfred Home edition will support Matter for device discovery and control

### Zigbee / Z-Wave
- **Status**: Evaluated for legacy IoT
- **Relevance**: Existing smart home protocols still widely used
- **Integration Plan**: Support via USB dongles and gateway integration

---

## VR/AR

### Monado
- **Status**: Evaluated for future VR support
- **Language**: C
- **License**: BSL-1.0
- **Relevance**: Open-source OpenXR runtime for Linux
- **Integration Plan**: Future ADE extension for VR workspace mode (Year 2+)

### OpenHMD
- **Status**: Evaluated
- **Relevance**: Open-source HMD driver library
- **Integration Plan**: Hardware abstraction for VR headset support

---

## System Infrastructure

### systemd
- **Status**: Core dependency
- **Role**: Init system, service management, journaling
- **Integration Plan**: Standard system init, with Alfred-specific service units

### PipeWire
- **Status**: Selected for audio/video
- **License**: MIT
- **Relevance**: Modern replacement for PulseAudio and JACK
- **Integration Plan**: Audio backbone for Alfred voice pipeline, media playback, and screen sharing

### Flatpak
- **Status**: Evaluated for app sandboxing
- **License**: LGPL-2.1
- **Relevance**: Application sandboxing and distribution
- **Integration Plan**: Support alongside native apkg package manager

---

## Networking

### NetworkManager
- **Status**: Selected for network management
- **Integration Plan**: Backend for ADE's network settings panel

### Avahi
- **Status**: Selected for service discovery
- **Integration Plan**: Local network device and service discovery for Alfred Home

---

## Package Management

### apkg (Custom)
- **Status**: In design
- **Role**: Alfred Linux native package manager
- **Design Goals**:
  - Atomic upgrades (rollback support)
  - AI-assisted dependency resolution
  - Sandboxed package installation
  - GSM token integration for marketplace
  - APT compatibility layer
- **Integration Plan**: Primary package manager, with apt as fallback

### Nix (Reference)
- **Status**: Studied for concepts
- **Relevance**: Reproducible builds, atomic upgrades, rollback
- **Key Takeaways**: Borrow atomic upgrade and rollback concepts for apkg
- **Integration Plan**: Conceptual influence only, not direct dependency

---

## Summary of Technology Decisions

| Component | Selected Technology | Alternative |
|-----------|-------------------|-------------|
| Compositor | Smithay (Rust) | — |
| GUI Toolkit | Iced (Rust) | Clay (layout assist) |
| Voice Input | Whisper.cpp | — |
| Voice Output | Kokoro TTS | — |
| Local AI | Ollama | — |
| Cloud AI | Claude API (optional) | — |
| PQC | Kyber-1024 + Dilithium | SPHINCS+ (backup) |
| VPN | WireGuard | — |
| Audio | PipeWire | — |
| Init | systemd | — |
| Packages | apkg (custom) | apt (compat) |
| Networking | NetworkManager | — |
| IoT | Matter/Thread | Zigbee/Z-Wave (legacy) |

---

*Research brief v1.0 — March 2026*
*Alfred Linux is a product of GoSiteMe Inc.*
