#!/bin/bash
# apply-patches.sh — Apply Alfred patches to Chromium source
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
CHROMIUM_SRC="$PROJECT_DIR/chromium/src"
PATCHES_DIR="$PROJECT_DIR/patches"

echo "=== Alfred Browser — Apply Patches ==="

if [ ! -d "$CHROMIUM_SRC" ]; then
    echo "ERROR: Chromium source not found at $CHROMIUM_SRC"
    echo "Run ./scripts/fetch-chromium.sh first"
    exit 1
fi

cd "$CHROMIUM_SRC"

# Reset any previous patches
echo "[1/3] Resetting Chromium source to clean state..."
git checkout -- . 2>/dev/null || true
git clean -fd 2>/dev/null || true

# Apply each patch in order
echo "[2/3] Applying Alfred patches..."
PATCH_COUNT=0
FAIL_COUNT=0

for patch in "$PATCHES_DIR"/*.patch; do
    [ -f "$patch" ] || continue
    PATCH_NAME=$(basename "$patch")
    echo -n "  Applying $PATCH_NAME... "
    if git apply --check "$patch" 2>/dev/null; then
        git apply "$patch"
        echo "OK"
        PATCH_COUNT=$((PATCH_COUNT + 1))
    else
        echo "FAILED (conflict)"
        FAIL_COUNT=$((FAIL_COUNT + 1))
        echo "    Try: cd chromium/src && git apply --3way $patch"
    fi
done

# Copy built-in extensions
echo "[3/3] Installing built-in extensions..."
EXTENSIONS_SRC="$PROJECT_DIR/extensions"
EXTENSIONS_DST="$CHROMIUM_SRC/chrome/browser/resources/alfred_extensions"
mkdir -p "$EXTENSIONS_DST"

for ext_dir in "$EXTENSIONS_SRC"/alfred-*; do
    [ -d "$ext_dir" ] || continue
    EXT_NAME=$(basename "$ext_dir")
    cp -r "$ext_dir" "$EXTENSIONS_DST/$EXT_NAME"
    echo "  Installed extension: $EXT_NAME"
done

# Copy branding assets
echo "  Copying branding assets..."
BRAND_SRC="$PROJECT_DIR/branding"
if [ -d "$BRAND_SRC/icons" ]; then
    cp -r "$BRAND_SRC/icons" "$CHROMIUM_SRC/chrome/app/theme/alfred_icons" 2>/dev/null || true
fi

echo ""
echo "=== Patches Applied: $PATCH_COUNT OK, $FAIL_COUNT failed ==="
echo "Next: Run ./scripts/build.sh"
