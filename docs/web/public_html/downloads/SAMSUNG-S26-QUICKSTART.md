# Alfred Linux on Samsung Galaxy S26 Ultra — Quick Start

## What You Get
- Full Debian Linux environment on your phone (no root needed)
- Alfred IDE in your browser (code-server)
- Alfred Voice (Kokoro TTS)
- Alfred Search (Meilisearch)
- Samsung DeX: plug into a monitor for a full desktop IDE experience

## Setup (5 minutes)

### Step 1: Install Termux
- Open your phone browser
- Go to: **https://f-droid.org/en/packages/com.termux/**
- Tap "Download APK" → install it
- ⚠️ Do NOT use the Google Play version (outdated)

### Step 2: Run the Installer
Open Termux and paste this one command:
```
curl -fsSL https://alfredlinux.com/downloads/install-alfred-mobile.sh | bash
```
Wait for it to finish (~5 min on WiFi, downloads ~4 GB total).

### Step 3: Launch Alfred
After install completes, type:
```
alfred
```
This drops you into the Alfred Linux shell. From there:
```
alfred-ide     # Opens Alfred IDE in your browser
alfred-info    # Shows status of all Alfred services
```

## Using with Samsung DeX
1. Connect your S26 to a monitor (USB-C cable or wireless DeX)
2. Open Termux → type `alfred` then `alfred-ide`
3. Open the browser URL shown (usually `http://localhost:8080`)
4. Full desktop IDE experience with keyboard + mouse

## Tips
- **Termux stays alive**: Pull down notification → tap "Acquire wakelock"
- **Split screen**: Use DeX split to have Termux + Browser side by side
- **File access**: Your phone storage is at `/sdcard/` inside Termux
- **Updates**: Re-run the install command to update Alfred components

## Troubleshooting
| Problem | Fix |
|---------|-----|
| "Permission denied" on install | Run `termux-setup-storage` first |
| IDE won't open in browser | Try `http://127.0.0.1:8080` |
| Slow first launch | Normal — proot does initial setup on first run |
| Termux killed in background | Enable "Acquire wakelock" in Termux notification |

## Quick Reference
| Command | What it does |
|---------|-------------|
| `alfred` | Enter Alfred Linux shell |
| `alfred-ide` | Start IDE (opens browser) |
| `alfred-info` | Show service status |
| `exit` | Leave Alfred Linux shell |
