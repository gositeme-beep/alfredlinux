#!/usr/bin/env bash
set -e

echo "════════════════════════════════════════════════════════════════════════"
echo "  ALFRED LINUX — SOVEREIGN ISO TORRENT GENERATOR"
echo "════════════════════════════════════════════════════════════════════════"
echo ""

if ! command -v transmission-create >/dev/null 2>&1; then
    echo "Error: transmission-create is not installed. Please run: sudo apt-get install -y transmission-cli"
    exit 1
fi

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BUILD_DIR="$REPO_ROOT/build"
ISO_FILE=$(find "$BUILD_DIR" -maxdepth 1 -name "*.iso" | head -n 1)
ASSETS_DIR="$REPO_ROOT/build-assets"
TORRENT_FILE="$ASSETS_DIR/alfredlinux-latest.iso.torrent"

if [ -z "$ISO_FILE" ] || [ ! -f "$ISO_FILE" ]; then
    echo "Error: No .iso file found in $BUILD_DIR. Please run the build first."
    exit 1
fi

mkdir -p "$ASSETS_DIR"

echo "=> Generating torrent for $ISO_FILE..."
echo "=> This will take a while for a 48 GB ISO..."

rm -f "$TORRENT_FILE"
transmission-create -o "$TORRENT_FILE" \
    -t "udp://tracker.opentrackr.org:1337/announce" \
    -t "udp://tracker.openbittorrent.com:6969/announce" \
    -w "https://alfredlinux.com/downloads/alfredlinux-latest.iso" \
    "$ISO_FILE"

echo "=> Torrent generated successfully: $TORRENT_FILE"
echo "=> Please seed this torrent using: transmission-cli -p 51413 $TORRENT_FILE -w $BUILD_DIR"
