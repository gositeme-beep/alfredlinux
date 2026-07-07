#!/bin/bash
# build.sh — Build Alfred Browser from patched Chromium source
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
CHROMIUM_SRC="$PROJECT_DIR/chromium/src"

BUILD_TYPE="${1:---release}"
case "$BUILD_TYPE" in
    --debug)   OUT_DIR="out/Debug";   IS_DEBUG="true";  IS_OFFICIAL="false" ;;
    --release) OUT_DIR="out/Release"; IS_DEBUG="false"; IS_OFFICIAL="true"  ;;
    *)         echo "Usage: $0 [--debug|--release]"; exit 1 ;;
esac

echo "=== Alfred Browser — Build ==="
echo "Type: $BUILD_TYPE"
echo "Output: $CHROMIUM_SRC/$OUT_DIR"
echo ""

if [ ! -d "$CHROMIUM_SRC" ]; then
    echo "ERROR: Chromium source not found at $CHROMIUM_SRC"
    echo "Run ./scripts/fetch-chromium.sh && ./scripts/apply-patches.sh first"
    exit 1
fi

cd "$CHROMIUM_SRC"

# Generate build files
echo "[1/2] Generating build configuration..."
mkdir -p "$OUT_DIR"

cat > "$OUT_DIR/args.gn" << GN_ARGS
# Alfred Browser build configuration
is_official_build = $IS_OFFICIAL
is_debug = $IS_DEBUG
target_cpu = "x64"

# Performance
symbol_level = 0
enable_nacl = false
is_component_build = false
blink_symbol_level = 0

# Media codecs (H.264, AAC)
proprietary_codecs = true
ffmpeg_branding = "Chrome"

# Privacy — strip Google services
google_api_key = ""
google_default_client_id = ""
google_default_client_secret = ""
safe_browsing_mode = 0
enable_reporting = false
enable_hangout_services_extension = false

# Branding
chrome_pgo_phase = 0
GN_ARGS

gn gen "$OUT_DIR"

# Build
NPROC=$(nproc 2>/dev/null || sysctl -n hw.ncpu 2>/dev/null || echo 8)
echo "[2/2] Building with $NPROC cores (this takes a while)..."
autoninja -C "$OUT_DIR" chrome

echo ""
echo "=== Build complete: $CHROMIUM_SRC/$OUT_DIR/chrome ==="
echo "Next: Run ./scripts/package.sh"
