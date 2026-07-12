# Building Alfred from Source

## Prerequisites

### All Platforms
```bash
# Install depot_tools (Google's Chromium build toolchain)
git clone https://chromium.googlesource.com/chromium/tools/depot_tools.git
export PATH="$PWD/depot_tools:$PATH"
```

### Linux (Ubuntu 22.04)
```bash
sudo apt-get install -y \
  build-essential clang lld \
  python3 python3-pip \
  git curl wget \
  libcups2-dev libdrm-dev libgbm-dev \
  libpango1.0-dev libcairo2-dev \
  libgtk-3-dev libnotify-dev \
  libnss3-dev libnspr4-dev \
  libatk1.0-dev libatk-bridge2.0-dev \
  libx11-xcb-dev libxcomposite-dev libxdamage-dev \
  libxrandr-dev libxcursor-dev \
  mesa-common-dev libgl1-mesa-dev
```

### macOS
```bash
xcode-select --install
# Xcode 15+ required
```

### Windows
- Visual Studio 2022 with C++ workload
- Windows 11 SDK (10.0.22621.0+)
- Debugging Tools for Windows

---

## Step 1: Fetch Chromium Source

```bash
cd alfred-chromium
./scripts/fetch-chromium.sh
```

This downloads ~30 GB of Chromium source into `chromium/src/`. Takes 30-60 minutes depending on connection.

The script pins to a specific Chromium version tag for reproducible builds.

---

## Step 2: Apply Patches

```bash
./scripts/apply-patches.sh
```

Applies all patches from `patches/` directory:
- `branding.patch` — App name, icons, about:version page
- `privacy.patch` — Strips Google telemetry (UMA, Safe Browsing reporting, etc.)
- `newtab.patch` — Custom new tab page loading alfred-newtab extension
- `search.patch` — GoSiteMe Search as default engine
- `extensions.patch` — Pre-install 5 built-in extensions

---

## Step 3: Build

```bash
# Full build (first time: 2-6 hours)
./scripts/build.sh

# Debug build (faster, larger binary)
./scripts/build.sh --debug

# Release build (optimized, code-signed)
./scripts/build.sh --release
```

### Build Flags (GN args)

```gn
# Release build args
is_official_build = true
is_debug = false
symbol_level = 0
enable_nacl = false
proprietary_codecs = true
ffmpeg_branding = "Chrome"
is_component_build = false

# Alfred-specific
alfred_branding = true
alfred_default_search = "https://gositeme.com/search"
alfred_newtab_extension_id = "alfred-newtab"
alfred_builtin_extensions = ["alfred-newtab", "alfred-wallet", "alfred-miner", "alfred-veil", "alfred-pulse"]

# Privacy
safe_browsing_mode = 1
google_api_key = ""
google_default_client_id = ""
google_default_client_secret = ""
```

---

## Step 4: Package Installer

```bash
./scripts/package.sh
```

### Output by Platform

| Platform | Output | Location |
|----------|--------|----------|
| Windows | `Alfred-Setup-x64.exe` | `out/release/installer/` |
| macOS | `Alfred.dmg` | `out/release/` |
| Linux | `alfred_*.deb`, `alfred_*.rpm`, `Alfred.AppImage` | `out/release/` |

---

## Development Workflow

### Incremental Builds
After initial build, changes rebuild in 10-30 minutes:
```bash
cd chromium/src
autoninja -C out/Release chrome
```

### Updating Chromium Version
```bash
# Update to new Chrome stable
./scripts/fetch-chromium.sh --update 130.0.6723.91

# Re-apply patches (may need conflict resolution)
./scripts/apply-patches.sh

# Rebuild
./scripts/build.sh
```

### Creating New Patches
```bash
cd chromium/src
# Make your changes...
git diff > ../../patches/my-change.patch
```

---

## CI/CD

GitHub Actions builds all platforms on every tag push. Requires self-hosted runners with:
- 64 GB RAM minimum
- 200 GB disk
- 8+ CPU cores

See `.github/workflows/build.yml` for the full pipeline.
