# Alfred — Chromium-Based Browser

**Alfred** is a Chromium-based browser built by [GoSiteMe](https://gositeme.com) with the entire GoSiteMe ecosystem built in: AI assistant, encrypted messaging, social network, crypto wallet, mining, VR worlds, and 1,220+ AI tools.

Think **Brave** but with a full AI-powered ecosystem instead of just an ad-blocker.

---

## Architecture

Alfred is a patched Chromium build (same approach as Brave, Edge, Opera, Vivaldi). We maintain a set of patches on top of upstream Chromium and rebase with each major Chrome release.

### What We Change from Chromium

| Layer | What We Do |
|-------|-----------|
| **Branding** | Custom icons, splash, installer graphics, app name, user-agent |
| **New Tab Page** | GoSiteMe dashboard with AI tools, quick actions, miner status, wallet |
| **Built-in Extensions** | 5 pre-installed extensions (see below) |
| **Telemetry** | All Google telemetry stripped (Safe Browsing, UMA, etc.) |
| **Default Search** | GoSiteMe Search (earns GSM tokens per query) |
| **Crypto Wallet** | Solana wallet + GSM token + Jupiter DEX integration |
| **Mining** | Background SHA-256 PoW miner (opt-in, 0.001 GSM per 1M hashes) |

### Built-in Extensions

| Extension | Purpose |
|-----------|---------|
| **Alfred New Tab** | Dashboard: AI tools, miner stats, wallet balance, ecosystem shortcuts |
| **Alfred Wallet** | Solana wallet, GSM balance, token swap, QR/NFC payments |
| **Alfred Miner** | Background SHA-256 mining with throttle control, earns GSM |
| **Alfred Veil** | E2E encrypted messaging (Kyber-1024 post-quantum) |
| **Alfred Pulse** | Social network feed, posts, notifications |

---

## Project Structure

```
alfred-chromium/
├── README.md                    # This file
├── BUILD_INSTRUCTIONS.md        # How to build from source
├── patches/                     # Chromium source patches
│   ├── branding.patch           # Icons, names, about page
│   ├── privacy.patch            # Strip telemetry & Google services
│   ├── newtab.patch             # Custom new tab page
│   ├── search.patch             # Default search engine
│   └── extensions.patch         # Pre-install built-in extensions
├── branding/                    # Brand assets
│   ├── icons/                   # App icons (all sizes)
│   └── themes/                  # Default browser theme
├── extensions/                  # Built-in Chrome extensions
│   ├── alfred-newtab/           # New Tab dashboard
│   ├── alfred-wallet/           # Solana wallet
│   ├── alfred-miner/            # Background miner
│   ├── alfred-veil/             # Encrypted messenger
│   └── alfred-pulse/            # Social network
├── scripts/                     # Build & release scripts
│   ├── fetch-chromium.sh        # Download Chromium source
│   ├── apply-patches.sh         # Apply all patches
│   ├── build.sh                 # Build for current platform
│   └── package.sh               # Create installer
├── installer/                   # Platform-specific installer configs
│   ├── windows/                 # NSIS/WiX scripts
│   ├── macos/                   # DMG + notarization
│   └── linux/                   # DEB/RPM/Flatpak specs
└── .github/workflows/           # CI/CD pipeline
    └── build.yml                # Multi-platform build + release
```

---

## Supported Platforms

| Platform | Installer | Status |
|----------|----------|--------|
| Windows 10/11 (x64) | `.exe` (NSIS) | Planned |
| Windows 10/11 (ARM64) | `.exe` (NSIS) | Planned |
| macOS 12+ (Apple Silicon) | `.dmg` | Planned |
| macOS 12+ (Intel) | `.dmg` | Planned |
| Ubuntu/Debian (x64) | `.deb` | Planned |
| Fedora/RHEL (x64) | `.rpm` | Planned |
| Linux (universal) | `.AppImage` | Planned |
| Linux (universal) | Flatpak | Planned |

---

## Build Requirements

- **Disk:** ~100 GB free
- **RAM:** 64 GB recommended (32 GB minimum, will be slow)
- **CPU:** 8+ cores recommended
- **OS:** Ubuntu 22.04 (Linux), macOS 13+ (Mac), Windows 10+ (Windows)
- **Tools:** Python 3, Git, Ninja, GN (included in depot_tools)
- **Time:** First build: 2-6 hours. Incremental: 10-30 minutes.

---

## Quick Start (Development)

```bash
# 1. Clone this repo
git clone https://github.com/gositeme/alfred-chromium.git
cd alfred-chromium

# 2. Fetch Chromium source (~30 GB)
./scripts/fetch-chromium.sh

# 3. Apply Alfred patches
./scripts/apply-patches.sh

# 4. Build
./scripts/build.sh

# 5. Package installer
./scripts/package.sh
```

---

## Token Economics (GSM)

Users earn GSM tokens by:
- **Searching** — 0.0001 GSM per search (100/day cap)
- **Mining** — 0.001 GSM per 1M SHA-256 hashes (opt-in, adjustable CPU %)
- **Daily streak** — 0.01 GSM/day bonus
- **Referrals** — 50 GSM per referral

GSM is a Solana SPL token (1 billion supply, 9 decimals). Trade on Jupiter DEX.

---

## License

Chromium portions: BSD License (Chromium Authors)  
Alfred modifications: Proprietary (GoSiteMe Inc.)  
Built-in extensions: Proprietary (GoSiteMe Inc.)
