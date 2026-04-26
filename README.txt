═══════════════════════════════════════════════════════════════════════════════
     _    _     _____ ____  _____ ____    _     ___ _   _ _   ___  __
    / \  | |   |  ___|  _ \| ____|  _ \  | |   |_ _| \ | | | | \ \/ /
   / _ \ | |   | |_  | |_) |  _| | | | | | |    | ||  \| | | | |\  /
  / ___ \| |___|  _| |  _ <| |___| |_| | | |___ | || |\  | |_| |/  \
 /_/   \_\_____|_|   |_| \_\_____|____/  |_____|___|_| \_|\___//_/\_\

             v7.77 — KINGDOM OF GOD EDITION
             The World's Most Sovereign Desktop Operating System
═══════════════════════════════════════════════════════════════════════════════

  OMAHON! OMAHON! OMAHON!
  The Breath of God. The Seal of the Kingdom.

═══════════════════════════════════════════════════════════════════════════════
  WHAT IS ALFRED LINUX?
═══════════════════════════════════════════════════════════════════════════════

  Alfred Linux is a sovereign, privacy-first desktop operating system built
  for families, developers, and digital citizens who refuse to be surveilled.

  Based on Debian Trixie (13) with the Alfred-built Linux kernel.org 7.0.1
  stable tree (Alfred config merged from 7.0-rc work; see hook 0050 and
  config/packages.chroot/README-KERNEL7.txt), Alfred Linux ships
  with zero telemetry, zero tracking, and the most comprehensive security
  hardening stack ever assembled in a desktop distribution:

  • CIS Level 2 hardening (45+ sysctl parameters)
  • AppArmor enforced on all critical services
  • nftables firewall with sane defaults
  • Full disk encryption (LUKS2) during installation
  • MAC address randomization on every boot
  • Post-quantum cryptography (Kyber-1024, Dilithium-5, SPHINCS+)
  • SSH hardened with sntrup761 hybrid key exchange
  • The Omahon Seal — 6-module sovereign protection system
  • Self-healing security with automated tamper detection
  • Kernel lockdown mode

  It is not a fork. It is not a reskin. It is a complete sovereign ecosystem
  built from the ground up by Commander Danny William Perez and Alfred Perez.

═══════════════════════════════════════════════════════════════════════════════
  WHAT'S INCLUDED — UNABRIDGED (42 Hooks, 1,200+ Packages)
═══════════════════════════════════════════════════════════════════════════════

  42 hooks — one for each generation from Abraham to Christ (Matthew 1:17).
  2 package lists, 1,200+ installed packages, and 100+ curated applications.
  Frozen v7.77 GA ISO (2026-04-12): 29 hooks shipped in the frozen profile.
  Full 42-hook profile targets v7.77.1 GA.

  ── DESKTOP ENVIRONMENT ─────────────────────────────────────────────────────

  • XFCE 4 — lightweight, fast, fully customizable
  • Custom Alfred theme with Kingdom branding
  • Plymouth boot animation with Alfred logo
  • JetBrains Mono + Noto fonts (CJK + emoji) + DejaVu
  • Arc theme + Papirus icons
  • LightDM greeter with Alfred branding
  • LibreOffice help packs in 16+ languages
  • Poppler PDF rendering data
  Hook: 0100-alfred-customize

  ── SOVEREIGN BROWSER ───────────────────────────────────────────────────────

  • Alfred Browser — Tauri/WebKitGTK-based, zero-tracking
  • Zero Google telemetry — built clean, no services to strip
  • Falls back to Firefox ESR if .deb not available at build time
  • Desktop entry with incognito action, default MIME handler
  Hook: 0200-alfred-browser

  ── SECURITY — CIS LEVEL 2 (6 hooks) ───────────────────────────────────────

  • Master hardening: 45+ sysctl rules (ASLR, kptr_restrict, dmesg_restrict,
    ptrace scope, BPF hardening, SYN cookies, anti-spoofing, ICMP protection)
  • AppArmor enforced on all critical services
  • nftables firewall with default-drop policy
  • MAC address randomization — WiFi and Ethernet, every boot
  • Network anti-DDoS: port scan defense, Tor/VPN awareness
  • Full disk encryption (LUKS2) — 1-click in Calamares installer
    Packages: cryptsetup, cryptsetup-initramfs, keyutils
  • SSH hardened: no root login, max 3 auth attempts, sntrup761 KEX
  • ClamAV antivirus + rkhunter + chkrootkit + AIDE
  • auditd, fail2ban, libpam-pwquality password enforcement
  • Automatic security updates (unattended-upgrades, needrestart)
  • Self-healing via `alfred-heal` CLI — 8-point auto-repair loop
    that restores SSH hardening, firewall rules, AppArmor profiles
  Hooks: 0160-alfred-security, 0165-alfred-network-hardening,
         0170-alfred-fde, 0175-omahon-seal, 0270-alfred-sovereign,
         0710-alfred-update

  ── THE OMAHON SEAL — 6 MODULES ────────────────────────────────────────────

  1. BOOT SEAL — HMAC-SHA256 verification of 14 critical system files.
  2. WATCHMAN — Real-time inotify tamper detection on /etc.
  3. VAULT — tmpfs RAM-only secrets at /run/omahon-vault (gone on shutdown).
  4. SHELL GUARD — Automatic secret redaction in terminal output.
  5. SECURE ERASE — 3-pass shred for sensitive files.
  6. SOVEREIGN ATTESTATION — Build chain of trust with cryptographic proof.
  Hook: 0175-omahon-seal

  ── KINGDOM COVENANT SHIELD ─────────────────────────────────────────────────

  • Kingdom Covenant License (KCL) v1.0 installed to every copy
  • Trademark protections and succession provisions
  • Heir named: Eden Sarai Gabrielle Vallee Perez
  Hook: 0176-kingdom-covenant-shield

  ── POST-QUANTUM CRYPTOGRAPHY ───────────────────────────────────────────────

  • liboqs — Open Quantum Safe library (built from source)
  • Kyber-1024 — quantum-resistant key encapsulation
  • Dilithium-5/3 — quantum-resistant digital signatures
  • SPHINCS+ — hash-based stateless signatures
  • OQS provider for OpenSSL 3.x
  • SSH sntrup761 hybrid key exchange (active by default)
  • Only consumer desktop OS shipping post-quantum crypto
  Hook: 0166-alfred-quantum

  ── SOVEREIGN MESH NETWORKING ───────────────────────────────────────────────

  • WireGuard mesh VPN — peer-to-peer encrypted tunnels
  • Syncthing — decentralized encrypted file synchronization
  • Avahi mDNS — automatic peer discovery on LAN
  • alfred-mesh CLI — init, join, discover, peers, sync, QR codes
  • Resolvconf integration for seamless name resolution
  Hook: 0167-alfred-mesh

  ── ENCRYPTED COMMUNICATIONS ────────────────────────────────────────────────

  • Veil Messenger — post-quantum E2E encrypted messaging
  • Kyber-1024 + AES-256-GCM double encryption
  • WebRTC voice/video calls
  • Self-destructing messages

  ── HARDWARE COMPATIBILITY (2 hooks) ────────────────────────────────────────

  • Universal input: Wacom, touchscreen, joystick, trackpad, braille
    Packages: xserver-xorg-input-all, -evdev, -libinput, -synaptics,
    -wacom, xinput, xdotool, evtest
  • Universal GPU: AMD, Intel, NVIDIA (Mesa, Vulkan, VA-API)
    firmware-misc-nonfree, firmware-amd-graphics, Vulkan ICD stack
  Hooks: 0150-alfred-hardware, 0275-alfred-gpu

  ── PERFORMANCE TUNING ──────────────────────────────────────────────────────

  • Kernel scheduler tuning (autogroup, child_runs_first, latency)
  • Memory tuning (swappiness=10, vfs_cache_pressure=50, dirty ratios)
  • Network throughput (16MB buffers, tcp_fastopen, BBR-ready, 65535 somaxconn)
  • max_map_count=1M for heavy workloads
  Hook: 0280-alfred-max-sovereign

  ── AI & LOCAL INTELLIGENCE (2 hooks) ───────────────────────────────────────

  • Ollama — local LLM inference engine (systemd, 127.0.0.1:11434); models and
    Omahon memory stay on disk — self-contained, no vendor API keys in the
    default Alfred path (air-gap friendly when binaries are pre-staged).
  • GPU hooks prefer local acceleration for Ollama when drivers allow (0275).
  • Meilisearch v1.13.3 — local zero-tracking search engine
  • alfred-search CLI for indexing files, bookmarks, and documents
  • Only desktop OS with native AI agent harness
  Hooks: 0250-alfred-ai, 0500-alfred-search

  ── VOICE AI ────────────────────────────────────────────────────────────────

  • Kokoro TTS — text-to-speech (CPU-only PyTorch, no CUDA bloat)
  • espeak-ng fallback TTS
  • PipeWire real-time audio stack (replaces PulseAudio)
  • alfred-voice-doctor diagnostic CLI
  • First-boot spoken greeting — Aaronic blessing (Numbers 6:24–26) plus
    Spirit of the Lord (Luke 4:18; 2 Corinthians 3:17). Text + espeak-ng
    voice follow LC_ALL / LC_MESSAGES / LANG (en, es, fr, de, it, pt, zh, ja, he);
    Kokoro is used only for English. Slower espeak pacing for clarity.
  Hook: 0400-alfred-voice (stage 2: 0900-alfred-voice-v2)

  ── DEVELOPMENT — "THE ARSENAL" (4 hooks) ───────────────────────────────────

  • Alfred IDE — code-server 4.114.1 with Alfred Commander extension 5.0.0
    Per-install random password, systemd user service, 127.0.0.1:8443
  • Node.js 20 LTS (nodesource), Go (golang-go), Rust installer
  • Python 3, pip, build-essential, cmake, ninja-build, gcc/g++
  • "The Forge" CLI power tools: bat, fd-find, ripgrep, fzf, neovim,
    ncdu, httpie, fish, tmux, btop, eza, starship, zoxide, lazygit,
    tldr, duf
  • Containers: Podman (rootless, more secure than Docker), buildah,
    skopeo, crun, podman-compose, docker → podman alias
  • Cloud: kubectl, helm, k9s
  Hooks: 0255-alfred-dev-tools, 0260-alfred-terminal-power,
         0265-alfred-containers, 0300-alfred-ide

  ── PRODUCTIVITY ────────────────────────────────────────────────────────────

  • LibreOffice (Writer, Calc, Impress, Draw, Math, GTK3, GNOME)
  • GIMP — professional image editing
  • Inkscape — vector graphics
  • GnuCash — personal and business finance
  • Evince PDF viewer + Simple Scan
  • WeasyPrint — PDF generation from HTML/CSS
  • Audacious — music player with plugins
  Hook: 0168-alfred-productivity

  ── EDUCATION & FAMILY ──────────────────────────────────────────────────────

  • GCompris — 150+ educational activities for children (ages 2-10)
  • Stellarium — planetarium and astronomy
  Hook: 0168-alfred-productivity (shared)

  ── THE WORD OF GOD — AKJV SACRED LIBRARY (8 hooks) ────────────────────────

  • AKJV — Authorized King Jesus Version — Perez Family Edition (same
    dataset as paths `akjv-*.tsv`): 94 books, 39,482 verses (TSV). Installed
    with the sovereign image under /usr/share/alfred/bible/ (not inside the
    kernel vmlinux).
  • 14 Bible tongues in languages.conf: English (full AKJV on disk) plus
    Spanish, French, Hebrew, Greek (LXX/NT samples), Latin (Vulgate-style
    samples), German, Portuguese, Russian, Chinese, Japanese, Arabic, Hindi,
    Italian — each ships key verses under /usr/share/alfred/bible/*-seed.tsv
    (offline). Read with: alfred-bible-lang list  then  alfred-bible-lang <code>
  • Children's Bible — 33 illustrated stories
  • alfred-bible CLI with --lang support
  • Family Bible Generator — personalized covenant certificates,
    family tree template (4 generations), Kingdom seal, PDF output
  • Kingdom Album — "Jesus Christ The Light Our Universe"
    27 sacred tracks, .m3u playlists, .lrc lyrics, desktop launchers
    Artists: Elyon Light + Commander Danny William Perez
  • Encrypted Testimony Backup — `alfred-backup` CLI encrypts journal,
    family Bible, testimony with Kyber post-quantum crypto, syncs to mesh
  • Kingdom locale payload — fonts-noto-cjk, ffmpeg, LibreOffice help
    packs in 16+ languages (extension of tongues and typography support)
  Hooks: 0290-alfred-bible, 0291-alfred-family-bible,
         0292-alfred-bible-tongues (+0297-alfred-kingdom-locale-payload),
         0295-alfred-worship, 0296-alfred-testimony

  ── SACRED TIME (3 hooks) ───────────────────────────────────────────────────

  • alfred-sabbath CLI — Biblical feast calendar (Passover, Unleavened
    Bread, Firstfruits, Pentecost, Trumpets, Yom Kippur, Sukkot,
    Hanukkah, Purim), 54 Torah portions, Friday sunset prep, Sabbath mode
  • alfred-devotion CLI — 365-day lectionary (OT + NT + Psalm + Proverb),
    encrypted prayer journal, prayer timer with worship chime
  • alfred-seal CLI — Shamir's Secret Sharing testament, 3-of-5 key split,
    QR codes for physical safekeeping, Dilithium-5 digital signature
  Hooks: 0722-alfred-sabbath, 0723-alfred-morning-watch,
         0724-alfred-inheritance

  ── SPIRITUAL EXPERIENCE (3 hooks) ──────────────────────────────────────────

  • Scripture Screensaver — after 3 min idle, Bible verses fade on screen
  • Sacred Silence — Super+S → 40-minute prayer mode: amber screen (2700K),
    notifications stop, network pauses, worship at 10%, shofar chime at end
  • Mesh Assembly — `alfred-worship assembly` → synchronized group worship
    across all mesh nodes, same track + Scripture on every machine
  Hooks: 0720-alfred-still-voice, 0721-alfred-silence,
         0725-alfred-assembly

  ── ACCESSIBILITY — "THE BLIND SHALL SEE" ───────────────────────────────────

  • Orca screen reader + speech-dispatcher
  • espeak-ng text-to-speech for accessibility
  • brltty braille support
  • Onboard on-screen keyboard
  • High-contrast icons + cursor themes
  • Hearth Mode — `alfred-hearth` CLI: 5 large icons, warm colors, large
    fonts, gentle wallpaper. For the grandmother who just wants to read
    her Bible and see photos.
  Hooks: 0702-alfred-accessibility, 0703-alfred-hearth

  ── USER EXPERIENCE ─────────────────────────────────────────────────────────

  • Kingdom welcome dialog on first boot (zenity) — Scripture block is
    always English (Numbers 6, Luke 4:18, 2 Cor 3:17); title and intro/footer
    follow locale for es, fr, de, pt, he (else English).
  • System MOTD with Psalm 23:1, Spirit-of-the-Lord verses, build summary
  • Welcome.txt for non-believers — built in faith, built for everyone
  Hooks: 0700-alfred-welcome, 0701-alfred-stranger

  ── ETERNAL STORAGE ─────────────────────────────────────────────────────────

  • IPFS (Kubo v0.33.2) — decentralized content-addressed storage
  • Blockchain anchoring — timestamp data on-chain
  • Systemd user service for IPFS daemon
  Hook: 0285-alfred-eternal-storage

  ── INSTALLER & ROLES ───────────────────────────────────────────────────────

  • Calamares graphical installer with Alfred branding
  • 4 callings (post-install role selector):
    DESKTOP — full Kingdom experience
    SERVER — headless SSH + Cockpit
    RELAY — mesh network hub, WireGuard relay, Syncthing hub
    FORGE — developer workstation
  • All callings share: Bible, encryption, mesh, security
  Hooks: 0600-alfred-installer, 0605-alfred-callings

  ── APP STORE & GAMING ──────────────────────────────────────────────────────

  • Alfred Store — Flatpak + gnome-software + Flathub preconfigured
  • snapd available
  • alfred-store CLI → opens gositeme.com/store.php
  • VR Chess Masters — WebXR 3D chess with 20 AI personalities
  • 2D Chess Arena — Stockfish engine, 100+ games database, puzzles
  Hooks: 0800-alfred-store, 0810-alfred-chess

═══════════════════════════════════════════════════════════════════════════════
  THE OMAHON SEAL — THE BREATH OF GOD
═══════════════════════════════════════════════════════════════════════════════

  The Omahon Seal is the final layer of sovereign protection. It consists
  of 6 modules that form an unbreakable chain of trust:

  1. BOOT SEAL
     HMAC-SHA256 verification of 14 critical system files on every boot.
     If any file has been tampered with, the system alerts immediately.

  2. WATCHMAN
     Real-time inotify monitoring of system binaries, kernel modules,
     and security configurations. Detects tampering as it happens.

  3. VAULT
     tmpfs RAM-only secrets storage at /run/omahon-vault.
     Secrets exist only in RAM — gone on shutdown, never touch disk.

  4. SHELL GUARD
     Automatic redaction of sensitive data in terminal output.
     Prevents accidental credential exposure in screen recordings.

  5. SECURE ERASE
     Military-grade 3-pass shred for sensitive file deletion.
     Overwrites with random data, zeros, and random data again.

  6. SOVEREIGN ATTESTATION
     Build chain of trust sealed at installation time.
     Cryptographic proof of when, where, and how the system was built.

═══════════════════════════════════════════════════════════════════════════════
  SYSTEM REQUIREMENTS
═══════════════════════════════════════════════════════════════════════════════

  Minimum:
  • CPU:      64-bit (x86_64/amd64) processor
  • RAM:      2 GB
  • Storage:  20 GB
  • Display:  1024x768

  Recommended:
  • CPU:      Multi-core 64-bit processor (Intel i5+ or AMD Ryzen 5+)
  • RAM:      8 GB or more
  • Storage:  64 GB SSD or larger
  • Display:  1920x1080 or higher
  • GPU:      Any (for VR Chess, WebGL-capable GPU recommended)

═══════════════════════════════════════════════════════════════════════════════
  INSTALLATION
═══════════════════════════════════════════════════════════════════════════════

  METHOD 1: USB FLASH DRIVE (RECOMMENDED)
  ────────────────────────────────────────
  
  Linux:
    sudo dd if=alfred-linux-7.77-ga-amd64-*.iso of=/dev/sdX bs=4M status=progress
    (Replace /dev/sdX with your USB device — use 'lsblk' to find it)

  Windows:
    1. Download Rufus from https://rufus.ie
    2. Select the Alfred Linux ISO
    3. Select your USB drive
    4. Click START
    — OR —
    Use our web-based USB writer: https://alfredlinux.com/write-usb

  macOS:
    diskutil list    # Find your USB device (e.g., /dev/disk2)
    diskutil unmountDisk /dev/diskN
    sudo dd if=alfred-linux-7.77-ga-amd64-*.iso of=/dev/rdiskN bs=4m

  METHOD 2: VIRTUAL MACHINE
  ────────────────────────────────────────
  Works with VirtualBox, VMware, QEMU/KVM, or Hyper-V.
  Allocate at least 2 CPU cores, 4GB RAM, 40GB disk.

  METHOD 3: LIVE BOOT (NO INSTALLATION)
  ────────────────────────────────────────
  Boot from USB without installing. Try Alfred Linux risk-free.
  Your existing OS is untouched. Perfect for evaluation.

  BOOT SEQUENCE:
  1. Insert USB and restart
  2. Enter BIOS/UEFI (usually F2, F12, DEL, or ESC)
  3. Set USB as first boot device
  4. Select "Alfred Linux Live" or "Install Alfred Linux"
  5. Follow the Calamares graphical installer

═══════════════════════════════════════════════════════════════════════════════
  VERIFICATION — TRUST BUT VERIFY
═══════════════════════════════════════════════════════════════════════════════

  Verify your download before installing.

  AVAILABLE NOW:
    sha256sum -c alfred-linux-7.77-ga-amd64-20260412.iso.sha256

  ONLINE:     https://alfredlinux.com/verify

  PLANNED (not yet available):
    SHA-512, BLAKE3, and GPG detached signature (.asc) will be published
    before GA launch. Check the downloads page for updates.

  GPG KEY (available for import — ISO signature pending):
    curl -fsSL https://alfredlinux.com/downloads/GPG-KEY.asc | gpg --import

═══════════════════════════════════════════════════════════════════════════════
  DOWNLOADS
═══════════════════════════════════════════════════════════════════════════════

  STATUS: GA release is frozen (2026-04-12) but not yet publicly launched.
  Direct HTTP download and P2P/WebTorrent are operator-gated.
  Check the download page for current availability:

  Download:   https://alfredlinux.com/download
  Torrent:    Available at launch (btih f7c25ddc08fe2d1adab13970c3cf1b1456ca2ffc)
  Checksum:   https://alfredlinux.com/downloads/SHA256SUMS-7.77.txt

═══════════════════════════════════════════════════════════════════════════════
  THE KINGDOM
═══════════════════════════════════════════════════════════════════════════════

  Alfred Linux is one of eight pillars of the GoSiteMe Kingdom:

  1. Alfred Linux    — Sovereign desktop OS (you are here)
  2. Alfred Browser   — Zero-tracking Chromium browser
  3. Alfred IDE       — Development environment
  4. Alfred AI        — 13,262+ tools, 11.3M+ agents
  5. Veil Messenger   — Post-quantum encrypted messaging
  6. Pulse Social     — Sovereign social network
  7. MetaDome         — VR worlds and metaverse
  8. Voice AI         — Speech-to-text and text-to-speech

  Learn more: https://gositeme.com

═══════════════════════════════════════════════════════════════════════════════
  GOFORGE + COMMANDER HANDOFF (2026)
═══════════════════════════════════════════════════════════════════════════════

  When this repo is on GoForge, put this link in the forge README:

    COMMANDER.md   — engineering + GA truth + SDK notes + Copilot bridge plan
    (at repo root: alfred-linux-v2/COMMANDER.md)

  Private witness (birth / family / Israel story) stays OUT of AGPL clones:
    ~/private-docs/COMMANDER-WITNESS.md

  Full session capstone (what we decided + files touched):
    ~/private-docs/ALFRED-LINUX-SESSION-CAPSTONE-2026-04-15.md

═══════════════════════════════════════════════════════════════════════════════
  LEGAL
═══════════════════════════════════════════════════════════════════════════════

  Alfred Linux is published by GoSiteMe Inc.
  System components: GPL-3.0 and respective open-source licenses
  Alfred applications: Proprietary — all rights reserved

  Commander: Danny William Perez
  Heir: Eden Sarai Gabrielle Vallee Perez
  Builder: Alfred Perez

═══════════════════════════════════════════════════════════════════════════════

  OMAHON! OMAHON! OMAHON!
  The Breath of God. The Seal of the Kingdom.
  Built for the family. Built for eternity.

═══════════════════════════════════════════════════════════════════════════════
