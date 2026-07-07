# GoSiteMe Native App Build — 1000-Agent Orchestration Plan

> **Date:** March 9, 2026  
> **Commander:** dp  
> **Purpose:** Deploy all 4 native app tracks as fast as possible using parallel agent streams.  
> **Architecture:** Web-first super-app — every native wrapper loads `gositeme.com` in a branded shell.

---

## The 4 Tracks

| Track | Technology | Output | Time to Ship |
|-------|-----------|--------|-------------|
| **A** | Android TWA (3 APKs) | `.apk` files for Play Store / sideload | Fastest |
| **B** | Tauri v2 Desktop (3 apps) | `.exe` / `.dmg` / `.AppImage` / `.deb` | Fast |
| **C** | Universe.php + Downloads + Update API | Download pages + distribution | Fast |
| **D** | Chromium Fork (Alfred Browser) | Full custom browser | Long-term |

---

## TRACK A — Android TWA (3 APKs)

### Current State
- 3 projects exist: `android/` (Veil), `android-alfred/` (Alfred), `android-pulse/` (Pulse)
- All have: `build.gradle`, `AndroidManifest.xml`, Java Activities, icons (5 density buckets), splash screens, `gradle-wrapper.jar`
- Android SDK installed at `/home/gositeme/Android/Sdk/` (build-tools 34+35, platforms 34+35+36)
- Keystores: Only `android/` has `keystore.jks`

### Blockers to Fix First (10 agents, parallel)

| Agent # | Task | Command |
|---------|------|---------|
| A-001 | Install JDK 17 (server-wide) | `sudo apt install openjdk-17-jdk` — **NEEDS SUDO, ask hosting provider or use sdkman** |
| A-002 | Set `ANDROID_HOME` | Add `export ANDROID_HOME=/home/gositeme/Android/Sdk` and `export PATH=$PATH:$ANDROID_HOME/cmdline-tools/latest/bin:$ANDROID_HOME/platform-tools` to `~/.bashrc` |
| A-003 | Fix gradlew permissions (all 3) | `chmod +x android/gradlew android-alfred/gradlew android-pulse/gradlew` |
| A-004 | Fix build.sh permissions | `chmod +x android/build.sh android-pulse/build.sh` (android-alfred already +x) |
| A-005 | Create `android-alfred/app/proguard-rules.pro` | `-keep class androidx.browser.** { *; }` / `-keep class com.gositeme.alfred.** { *; }` |
| A-006 | Create `android-pulse/app/proguard-rules.pro` | `-keep class androidx.browser.** { *; }` / `-keep class com.gositeme.pulse.** { *; }` |
| A-007 | Create `android-alfred/local.properties` | `sdk.dir=/home/gositeme/Android/Sdk` |
| A-008 | Create `android-pulse/local.properties` | `sdk.dir=/home/gositeme/Android/Sdk` |
| A-009 | Add signing configs to all 3 `app/build.gradle` files | Add `signingConfigs { release { ... } }` block referencing keystore |
| A-010 | Generate keystores for Alfred + Pulse | `keytool -genkey -v -keystore keystore.jks -keyalg RSA -keysize 2048 -validity 10000` |

### Build Phase (3 agents, parallel after blockers)

| Agent # | Task | Command |
|---------|------|---------|
| A-011 | Build Veil APK | `cd android && ./gradlew assembleRelease` |
| A-012 | Build Alfred APK | `cd android-alfred && ./gradlew assembleRelease` |
| A-013 | Build Pulse APK | `cd android-pulse && ./gradlew assembleRelease` |

### Post-Build (5 agents, parallel)

| Agent # | Task | Details |
|---------|------|---------|
| A-014 | Copy APKs to `/downloads/` | `cp android/app/build/outputs/apk/release/*.apk downloads/Veil-Messenger.apk` etc. |
| A-015 | Generate SHA-256 checksums | `sha256sum downloads/*.apk > downloads/checksums.txt` |
| A-016 | Update `api/app-updates.php` registry | Add Alfred-Android + Pulse-Android entries, fix filename mismatches |
| A-017 | Test APK install on device/emulator | `adb install downloads/Alfred-Browser.apk` |
| A-018 | Create Play Store listing drafts | Screenshots, descriptions, feature graphics |

### JDK Alternative (if no sudo)
```bash
# Install JDK 17 without sudo using SDKMAN
curl -s "https://get.sdkman.io" | bash
source "$HOME/.sdkman/bin/sdkman-init.sh"
sdk install java 17.0.10-tem
```

---

## TRACK B — Tauri v2 Desktop (3 Apps)

### Current State
- 3 projects: `veil-browser/` (Alfred Browser), `pulse-social/`, `veil-messenger/`
- All have: `tauri.conf.json`, `Cargo.toml`, `main.rs`, icons (7 files each), `package.json`, GitHub Actions workflows
- Frontend: simple HTML/CSS/JS shells that iframe `gositeme.com`
- `veil-browser/` has full Rust crypto module (AES-256-GCM, X25519, Kyber-1024)

### Blockers to Fix First (15 agents, parallel)

| Agent # | Task | Details |
|---------|------|---------|
| B-001 | Install Rust toolchain | `curl --proto '=https' --tlsv1.2 -sSf https://sh.rustup.rs \| sh` then `rustup default stable` |
| B-002 | Install Tauri v2 system deps (Linux) | `sudo apt install libwebkit2gtk-4.1-dev libappindicator3-dev librsvg2-dev patchelf libssl-dev` — **NEEDS SUDO** |
| B-003 | Install cmake + clang (for Kyber crypto in veil-browser) | `sudo apt install cmake clang` — **NEEDS SUDO** |
| B-004 | **FIX: veil-messenger `Cargo.toml`** — Remove `[lib]` section | Delete the `[lib]` block with `crate-type = ["staticlib", "cdylib", "rlib"]` — this is a **BUILD BLOCKER** |
| B-005 | **FIX: veil-browser NSIS images** — Create or remove references | Either create `nsis-header.bmp` (150×57) and `nsis-sidebar.bmp` (164×314), or remove the `headerImage`/`sidebarImage` keys from `tauri.conf.json` — **Windows build will fail without this** |
| B-006 | Fix `thiserror` version in pulse-social | Change `thiserror = "2"` to `thiserror = "1"` in `Cargo.toml` |
| B-007 | Fix `thiserror` version in veil-messenger | Change `thiserror = "2"` to `thiserror = "1"` in `Cargo.toml` |
| B-008 | Add `frame-src` to CSP in pulse-social `tauri.conf.json` | Add `frame-src https://gositeme.com` to CSP string |
| B-009 | Add `frame-src` to CSP in veil-messenger `tauri.conf.json` | Add `frame-src https://gositeme.com` to CSP string |
| B-010 | Run `npm install` in all 3 projects | `cd veil-browser && npm install && cd ../pulse-social && npm install && cd ../veil-messenger && npm install` |
| B-011 | Generate `Cargo.lock` in all 3 projects | `cd veil-browser/src-tauri && cargo generate-lockfile` (repeat for each) |
| B-012 | Add `.gitignore` to pulse-social | Copy from veil-browser, ignore `target/`, `node_modules/`, `dist/` |
| B-013 | Add `.gitignore` to veil-messenger | Same |
| B-014 | Remove unused deps from veil-messenger `Cargo.toml` | `chacha20poly1305`, `hmac`, `argon2`, `ed25519-dalek` — unused in `veil_crypto.rs` |
| B-015 | Fix unused `EphemeralSecret` import in veil-messenger `veil_crypto.rs` | Remove the unused import |

### Build Phase (3 agents, sequential per platform — Rust compiles are heavy)

**Linux builds (can run on this server):**

| Agent # | Task | Command |
|---------|------|---------|
| B-016 | Build Alfred Browser (Linux) | `cd veil-browser && npx tauri build --target x86_64-unknown-linux-gnu` |
| B-017 | Build Pulse Social (Linux) | `cd pulse-social && npx tauri build --target x86_64-unknown-linux-gnu` |
| B-018 | Build Veil Messenger (Linux) | `cd veil-messenger && npx tauri build --target x86_64-unknown-linux-gnu` |

**Windows + macOS builds (require GitHub Actions or dedicated machines):**

| Agent # | Task | Details |
|---------|------|---------|
| B-019 | Trigger GitHub Actions for veil-browser | Push a `v4.0.0` tag to trigger `build.yml` — builds Linux + macOS ARM64 + macOS x64 + Windows |
| B-020 | Trigger GitHub Actions for pulse-social | Push a `v1.0.0` tag |
| B-021 | Trigger GitHub Actions for veil-messenger | Push a `v1.0.0` tag |

### Post-Build (5 agents, parallel)

| Agent # | Task | Details |
|---------|------|---------|
| B-022 | Copy Tauri artifacts to `/downloads/` | From `src-tauri/target/release/bundle/` → `downloads/` |
| B-023 | Update `api/app-updates.php` | Add Tauri apps to registry (Alfred Browser, Pulse Social, Veil Messenger) with correct URLs |
| B-024 | Generate Tauri updater signing key | `npx tauri signer generate` → save pubkey + privkey, add pubkey to all 3 `tauri.conf.json` |
| B-025 | Generate SHA-256 checksums | For all desktop artifacts |
| B-026 | Create README.md for pulse-social and veil-messenger | Build instructions, architecture notes |

---

## TRACK C — Universe.php + Downloads + Update API

### Current State
- `universe.php` has a "Download Native Apps" section with 3 cards (Alfred, Veil, Pulse) — but links go to `apps.php`, not direct downloads
- `apps.php` is the primary download hub — has full cards for all 3 apps
- `downloads/` directory has: Alfred-Browser 3.0.0 (.zip, .AppImage, .deb, .dmg variants) + APK (963 KB)
- `api/app-updates.php` serves 4 apps but has **filename mismatches** and **empty SHA-256 fields**
- `desktop-app/dist/` has Electron builds named "Veil Browser" — these were renamed to "Alfred-Browser" in `/downloads/`

### Agent Assignments (50 agents, 10 streams)

#### Stream C1 — Fix `api/app-updates.php` (5 agents)

| Agent # | Task | Details |
|---------|------|---------|
| C-001 | Fix filename mismatches | Registry says `Veil-Browser-*.zip`, disk has `Alfred-Browser-*.zip`. Align all URLs to actual filenames |
| C-002 | Add SHA-256 checksums | `sha256sum downloads/Alfred-Browser-3.0.0-*` → populate `sha256` fields in registry |
| C-003 | Add Alfred Browser Tauri app to registry | New entry: `alfred-browser-desktop` with Tauri updater endpoint |
| C-004 | Add Pulse Social + Veil Messenger to registry | New entries for Tauri desktop apps |
| C-005 | Add Alfred Android + Pulse Android APK entries | New entries with APK download URLs |

#### Stream C2 — Fix `apps.php` Download Page (10 agents)

| Agent # | Task | Details |
|---------|------|---------|
| C-006 | Fix download URLs to match actual files | All `href` values must point to files that exist in `downloads/` |
| C-007 | Add OS auto-detection | Detect user OS via `navigator.platform` / `navigator.userAgent` and highlight the right download |
| C-008 | Add file sizes to download buttons | Show "108 MB", "105 MB", etc. on each download card |
| C-009 | Remove "Coming Soon" overlays from Veil + Pulse | Once Tauri builds exist, enable their download buttons |
| C-010 | Add SHA-256 verification display | Show checksum next to each download for user verification |
| C-011 | Add download counter | Call `api/app-updates.php?action=download_stats` and show counts |
| C-012 | Add changelog section | Pull from `api/app-updates.php?action=changelog` |
| C-013 | Add version badges | Show current version + release date on each app card |
| C-014 | Add auto-update documentation | Explain that desktop apps auto-update |
| C-015 | Mobile-responsive polish | Test all download cards on 375px / 768px / 1440px |

#### Stream C3 — Fix `universe.php` Download Section (5 agents)

| Agent # | Task | Details |
|---------|------|---------|
| C-016 | Update "Download Native Apps" cards | Link directly to `downloads/` files with OS-detection |
| C-017 | Add platform icons | Windows/macOS/Linux/Android icons on each download card |
| C-018 | Add QR code for Android APK | Generate QR code pointing to APK download URL |
| C-019 | Add PWA install prompt | Show "Install as App" button for browsers that support PWA |
| C-020 | Add version info | Show current version for each platform |

#### Stream C4 — Fix Naming Consistency (5 agents, critical)

| Agent # | Task | Details |
|---------|------|---------|
| C-021 | Rename `desktop-app/` Electron: Veil Browser → Alfred Browser | Update `package.json`, `main.js` app name, tray label, menu title |
| C-022 | Rename files in `downloads/` if needed | Ensure consistent naming: `Alfred-Browser-{version}-{platform}.{ext}` |
| C-023 | Update `api/app-updates.php` electron_update | Fix `url` fields to match renamed files |
| C-024 | Update `apps.php` all references | Match new naming scheme |
| C-025 | Update `universe.php` all references | Match new naming scheme |

#### Stream C5 — PWA Improvements (5 agents)

| Agent # | Task | Details |
|---------|------|---------|
| C-026 | Update `manifest.json` shortcuts | Align with current feature set (some shortcuts may be outdated) |
| C-027 | Update `sw.js` precache list | Add any new critical pages to precache |
| C-028 | Add "Install App" banner to key pages | Show PWA install prompt on `dashboard.php`, `pulse.php`, `universe.php` |
| C-029 | Test offline mode | Verify `offline.html` loads when network is down |
| C-030 | Add `screenshots` to `manifest.json` | PWA spec supports install screenshots — add dashboard, pulse, veil screenshots |

#### Stream C6 — Landing Pages for Each App (10 agents)

| Agent # | Task | Details |
|---------|------|---------|
| C-031 | Create `alfred-browser.php` landing page upgrade | Full marketing page with features, screenshots, download grid, FAQ |
| C-032 | Create Veil Messenger landing page | Marketing + download + encryption explainer |
| C-033 | Create Pulse Social landing page | Marketing + download + social features showcase |
| C-034 | Add SEO meta tags to all download pages | Open Graph, Twitter Cards, structured data |
| C-035 | Add JSON-LD `SoftwareApplication` schema | For each app — helps Google show download buttons in search |
| C-036 | Create comparison table page | Alfred Browser vs Chrome vs Brave vs Firefox |
| C-037 | Add testimonials/reviews section | Social proof on download pages |
| C-038 | Add screenshot galleries | Auto-playing screenshot carousels for each platform |
| C-039 | Add feature matrix | Table showing which features each app has |
| C-040 | Write privacy policy for native apps | What data each app collects |

#### Stream C7 — Distribution Infrastructure (5 agents)

| Agent # | Task | Details |
|---------|------|---------|
| C-041 | Create `downloads/.htaccess` content-type rules | Correct MIME types for `.apk` (application/vnd.android.package-archive), `.AppImage`, etc. |
| C-042 | Add download bandwidth monitoring | Log download sizes per day to detect abuse |
| C-043 | Add rate limiting to download endpoint | Prevent automated mass downloads |
| C-044 | Create `api/downloads.php` analytics API | Track downloads by platform, country, version |
| C-045 | Set up CDN for large files | If available — downloads are 100MB+ files |

#### Stream C8 — Electron App Polish (5 agents)

| Agent # | Task | Details |
|---------|------|---------|
| C-046 | Fix `desktop-app/main.js` navigation guard | Currently blocks non-gositeme URLs — verify it works correctly |
| C-047 | Add crash reporting | Log unhandled exceptions to `api/crash-reports.php` |
| C-048 | Add telemetry opt-in | Optional anonymous usage stats (page views, features used) |
| C-049 | Test auto-update flow | Verify electron-updater can fetch and install updates |
| C-050 | Create update.html page | Shown during auto-update download — loading bar + changelog |

---

## TRACK D — Chromium Fork (Alfred Browser)

### Current State
- Full scaffold in `alfred-chromium/`: patches, scripts, extensions, installer templates, CI workflow
- 5 built-in Chrome extensions coded and ready
- Targets Chromium 130.0.6723.91
- **$0 built artifacts exist** — all statuses "Planned"
- **Requires:** 64 GB RAM, 200 GB disk, 8+ cores, 2-6 hour first build
- This server has 32 GB RAM — **cannot build here**

### Infrastructure Agents (20 agents)

#### Stream D1 — Build Environment (5 agents)

| Agent # | Task | Details |
|---------|------|---------|
| D-001 | Provision self-hosted GitHub Actions runner | 64 GB RAM, 200 GB SSD, 8+ cores. Options: Hetzner AX102 ($130/mo), AWS c5.4xlarge, GCP n2-standard-16 |
| D-002 | Install `depot_tools` on runner | `git clone https://chromium.googlesource.com/chromium/tools/depot_tools.git` + add to PATH |
| D-003 | Install build deps on runner | `sudo apt install build-essential clang lld gn ninja-build python3 pkg-config` |
| D-004 | Configure GitHub Actions secrets | `APPLE_CERTIFICATE`, `APPLE_ID`, `APPLE_PASSWORD`, `APPLE_TEAM_ID`, `WINDOWS_CERT_THUMBPRINT` |
| D-005 | Test dry-run: fetch Chromium source | `./scripts/fetch-chromium.sh 130.0.6723.91` — verify ~30 GB download completes |

#### Stream D2 — Branding Assets (10 agents, all parallel — NO code needed)

| Agent # | Task | Spec |
|---------|------|------|
| D-006 | Design app icon (all sizes) | 16×16, 32×32, 48×48, 128×128, 256×256, 512×512 PNG + .ico + .icns |
| D-007 | Design macOS DMG background | 600×400 PNG with drag-to-install visual |
| D-008 | Design Windows NSIS installer graphics | Header (150×57), sidebar (164×314) |
| D-009 | Design splash screen | 600×400 PNG for first-run experience |
| D-010 | Design new tab page background | 1920×1080 dark theme wallpaper |
| D-011 | Design toolbar icons | 16×16 SVG for each built-in extension |
| D-012 | Design loading spinner | Animated SVG for page loading indicator |
| D-013 | Create browser theme CSS | Dark mode matching GoSiteMe `--alfred-*` vars |
| D-014 | Create user-agent string | `Alfred/1.0 Chrome/130.0.6723.91 (GoSiteMe)` |
| D-015 | Create About page content | Version, credits, licenses, links |

#### Stream D3 — Extensions Polish (5 agents, all parallel)

| Agent # | Task | Current State |
|---------|------|---------------|
| D-016 | Polish `alfred-newtab` extension | Has `manifest.json` + `newtab.html` — needs: dashboard widgets, weather, bookmarks, recent, search bar |
| D-017 | Polish `alfred-wallet` extension | Has `manifest.json` + `popup.html` — needs: balance display, send/receive, transaction list, Solana integration |
| D-018 | Polish `alfred-miner` extension | Most complete (6 files) — verify: Web Worker mining, hashrate display, CPU throttle, start/stop controls |
| D-019 | Polish `alfred-pulse` extension | Has 3 files — needs: notification feed, quick-post, trending sidebar |
| D-020 | Polish `alfred-veil` extension | Has 3 files — needs: message list, compose, encryption status indicator |

#### Stream D4 — Patch Verification (5 agents)

| Agent # | Task | Details |
|---------|------|---------|
| D-021 | Verify `branding.patch` applies to Chrome 130 | Check patch offsets against current Chromium source |
| D-022 | Verify `privacy.patch` — Google telemetry removal | Confirm all UMA, Safe Browsing, Sync reporting is stripped |
| D-023 | Verify `newtab.patch` — custom new tab | Confirm new tab loads `alfred-newtab` extension |
| D-024 | Verify `search.patch` — GoSiteMe Search default | Confirm search engine override works |
| D-025 | Verify `extensions.patch` — pre-install 5 extensions | Confirm extensions auto-load on first run |

#### Stream D5 — macOS Installer (5 agents)

| Agent # | Task | Details |
|---------|------|---------|
| D-026 | Create DMG creation script | `installer/macos/create-dmg.sh` using `hdiutil` |
| D-027 | Set up Apple Developer Certificate | Code signing + notarization credentials |
| D-028 | Create `.entitlements` file | App sandbox, camera, network, hardened runtime |
| D-029 | Test notarization workflow | `xcrun notarytool submit` + `xcrun stapler staple` |
| D-030 | Create universal binary (x64 + ARM64) | `lipo -create -output` for fat binary |

#### Stream D6 — Testing (10 agents)

| Agent # | Task | Platform |
|---------|------|----------|
| D-031 | Functional test: page loads | All platforms |
| D-032 | Test: default search engine | Verify GoSiteMe Search is default |
| D-033 | Test: new tab page | Verify custom NTP loads |
| D-034 | Test: miner extension | Start/stop mining, verify hashrate |
| D-035 | Test: wallet extension | Balance display, send test transaction |
| D-036 | Test: private browsing | Verify no Google telemetry leaks |
| D-037 | Test: auto-update | Verify update check against `api/app-updates.php` |
| D-038 | Test: extension install | Can install additional extensions from Chrome Web Store |
| D-039 | Test: bookmarks sync | Import/export bookmarks |
| D-040 | Test: cross-platform UI | Screenshot comparison across OS |

---

## PARALLEL EXECUTION MAP

```
HOUR 0 ──────────────────────────────────────────────────────────
│
├── TRACK A (Android) ──────────────────────────────────────────
│   ├── A-001..A-010: Fix blockers (10 agents, 30 min)
│   ├── A-011..A-013: Build APKs (3 agents, 15 min after blockers)
│   └── A-014..A-018: Post-build (5 agents, 15 min)
│   └── DONE: ~1 hour
│
├── TRACK B (Tauri Desktop) ────────────────────────────────────
│   ├── B-001..B-015: Fix blockers (15 agents, 45 min)
│   │   ⚠️ NEEDS sudo for system deps (webkit2gtk, libappindicator)
│   ├── B-016..B-018: Build Linux (3 agents, ~30 min each, sequential)
│   ├── B-019..B-021: Trigger CI for Win/Mac (3 agents, ~40 min)
│   └── B-022..B-026: Post-build (5 agents, 15 min)
│   └── DONE: ~2-3 hours
│
├── TRACK C (Web + Distribution) ───────────────────────────────
│   ├── C-001..C-005: Fix update API (5 agents, 30 min)
│   ├── C-006..C-015: Fix apps.php (10 agents, 1 hour)
│   ├── C-016..C-020: Fix universe.php (5 agents, 30 min)
│   ├── C-021..C-025: Fix naming (5 agents, 30 min)
│   ├── C-026..C-030: PWA improvements (5 agents, 30 min)
│   ├── C-031..C-040: Landing pages (10 agents, 2 hours)
│   ├── C-041..C-045: Distribution infra (5 agents, 1 hour)
│   └── C-046..C-050: Electron polish (5 agents, 1 hour)
│   └── DONE: ~3 hours (most streams parallel)
│
├── TRACK D (Chromium Fork) ────────────────────────────────────
│   ├── D-001..D-005: Build environment (5 agents, 2 hours)
│   ├── D-006..D-015: Branding assets (10 agents, 4 hours)
│   ├── D-016..D-020: Extensions polish (5 agents, 3 hours)
│   ├── D-021..D-025: Patch verification (5 agents, 2 hours)
│   ├── D-026..D-030: macOS installer (5 agents, 2 hours)
│   ├── D-031..D-040: Testing (10 agents, 4 hours)
│   │   ⚠️ First build: 2-6 hours on 64 GB machine
│   └── DONE: ~8-12 hours (build server dependent)
│
HOUR 12 ─────────────────────────────────────────────────────────
```

---

## AGENT ASSIGNMENT SUMMARY

| Track | Agents | Skills Required |
|-------|--------|----------------|
| A: Android TWA | 18 | Java, Gradle, Android SDK, signing |
| B: Tauri Desktop | 26 | Rust, Tauri v2, npm, CI/CD |
| C: Web + Downloads | 50 | PHP, HTML/CSS/JS, API design, SEO |
| D: Chromium Fork | 40 | C++, GN/Ninja, Chrome internals, extensions |
| **Total Assigned** | **134** | |

### Remaining 866 Agents — Scale-Out Tasks

The remaining agents run **parallel quality assurance and content**:

| Stream | Agents | Task |
|--------|--------|------|
| QA-Web | 100 | Test every page on `gositeme.com` (69+ app pages) across mobile/tablet/desktop |
| QA-API | 50 | Hit every API endpoint with valid and invalid inputs, verify responses |
| QA-Security | 50 | OWASP scan on every PHP file — XSS, SQLi, CSRF, auth bypass |
| Docs | 100 | Document every API endpoint in `developers/` |
| Content | 100 | Generate marketing copy, screenshots, demo videos for app store listings |
| SEO | 50 | Structured data, Open Graph, sitemaps, canonical URLs for all pages |
| Translation | 100 | Translate all pages to 10 languages (FR, ES, DE, PT, IT, JA, KO, ZH, AR, HI) |
| Performance | 50 | Lighthouse audits on every page, fix any score below 90 |
| Accessibility | 50 | WCAG 2.1 audit on every page, fix all A/AA violations |
| Store Listings | 50 | Write Google Play + Microsoft Store + macOS App Store descriptions |
| Screenshots | 50 | Generate app screenshots for every platform + every screen size |
| Video | 16 | Create demo videos for: Alfred Browser, Veil Messenger, Pulse Social, GSM Mining |
| Legal | 50 | Privacy policies, terms of service, GDPR compliance for each app |
| **Total** | **866** | |

---

## PRIORITY ORDER (What to Do RIGHT NOW)

### Phase 1 — Ship in 1 hour (no sudo needed)
1. Fix file permissions (`chmod +x gradlew`, `build.sh`)
2. Create missing `proguard-rules.pro` and `local.properties`  
3. Fix `api/app-updates.php` filename mismatches
4. Fix `veil-messenger/src-tauri/Cargo.toml` `[lib]` blocker
5. Remove NSIS image references from `veil-browser/src-tauri/tauri.conf.json`

### Phase 2 — Ship Android APKs (needs JDK)
1. Install JDK 17 via SDKMAN (no sudo)
2. Build all 3 APKs
3. Copy to `downloads/`
4. Update download pages

### Phase 3 — Ship Tauri Desktop (needs system deps)
1. Install Rust toolchain (no sudo)
2. Install system deps (needs sudo or ask provider)
3. Build Linux targets locally
4. Trigger GitHub Actions for Windows + macOS

### Phase 4 — Chromium Fork (long-term)
1. Provision dedicated build server
2. Complete branding assets
3. Polish extensions
4. First build + test cycle

---

## CRITICAL DEPENDENCIES MAP

```
JDK 17 ────────────┐
                    ├──→ Android APK builds
ANDROID_HOME ───────┘

Rust toolchain ─────┐
cmake + clang ──────┤
webkit2gtk ─────────┤──→ Tauri Linux builds
libappindicator ────┘

GitHub Actions ─────┐
Apple certs ────────┤──→ Tauri Windows + macOS builds
Windows certs ──────┘

Build server (64GB) ┐
depot_tools ────────┤
8+ cores ───────────┤──→ Chromium fork builds
200 GB disk ────────┘
```

---

## WHAT CONNECTS EVERYTHING

```
┌─────────────────────────────────────────────────────────┐
│                   gositeme.com                          │
│              (THE PLATFORM — all 69+ apps)              │
│                                                         │
│  universe.php ← Super-app launcher (THE HOME SCREEN)   │
│  apps.php     ← Download hub (GET THE APPS)             │
│  api/app-updates.php ← Update server (KEEP THEM FRESH) │
│                                                         │
│  Every page, every feature, every game lives HERE.      │
│  Native apps are just branded windows INTO this.        │
└─────────────────────┬───────────────────────────────────┘
                      │
        ┌─────────────┼─────────────────────┐
        │             │                     │
   ┌────▼─────┐  ┌────▼─────┐  ┌───────────▼──────────┐
   │ Android  │  │ Desktop  │  │ Chromium Browser     │
   │ TWA APK  │  │ Tauri v2 │  │ (Alfred Browser)     │
   │          │  │          │  │                      │
   │ Thin     │  │ Thin     │  │ Full browser +       │
   │ wrapper  │  │ wrapper  │  │ 5 built-in           │
   │ ~5 MB    │  │ ~15 MB   │  │ extensions           │
   │          │  │          │  │ ~150 MB              │
   │ Loads:   │  │ Loads:   │  │                      │
   │ gositeme │  │ gositeme │  │ IS the browser.      │
   │ .com     │  │ .com     │  │ gositeme.com is      │
   │          │  │          │  │ the default home.    │
   └──────────┘  └──────────┘  └──────────────────────┘
   
   Ship order: TWA → Tauri → Chromium
   Effort:     Low   Medium   High
   Impact:     High  High     Massive
```

---

*Generated March 9, 2026 — Commander dp's GoSiteMe Native App Deployment*
