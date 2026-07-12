#!/bin/bash
# fetch-chromium.sh — Download Chromium source for Alfred Browser
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
CHROMIUM_DIR="$PROJECT_DIR/chromium"

# Pin to a stable Chrome version for reproducible builds
CHROMIUM_VERSION="${1:-130.0.6723.91}"

echo "=== Alfred Browser — Fetch Chromium Source ==="
echo "Version: $CHROMIUM_VERSION"
echo "Target:  $CHROMIUM_DIR"
echo ""

# Check depot_tools
if ! command -v gclient &> /dev/null; then
    echo "ERROR: depot_tools not found in PATH"
    echo "Install: git clone https://chromium.googlesource.com/chromium/tools/depot_tools.git"
    echo "Then:    export PATH=\"\$PWD/depot_tools:\$PATH\""
    exit 1
fi

# Create chromium directory
mkdir -p "$CHROMIUM_DIR"
cd "$CHROMIUM_DIR"

if [ ! -f ".gclient" ]; then
    echo "[1/3] Initializing Chromium checkout..."
    fetch --nohooks --no-history chromium
else
    echo "[1/3] Chromium checkout already exists, syncing..."
fi

cd src

echo "[2/3] Checking out version $CHROMIUM_VERSION..."
git fetch --tags
git checkout "tags/$CHROMIUM_VERSION" 2>/dev/null || git checkout "$CHROMIUM_VERSION"

echo "[3/3] Syncing dependencies (this takes a while)..."
gclient sync --with_branch_heads --with_tags -D

echo ""
echo "=== Chromium source ready at $CHROMIUM_DIR/src ==="
echo "Next: Run ./scripts/apply-patches.sh"
