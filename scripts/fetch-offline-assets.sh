#!/bin/bash
set -e

# AlfredLinux Offline Asset Fetcher
# This script downloads all external binaries into the local cache before a build
# so that the chroot hooks do not need internet access, preventing random build crashes.

ASSETS_DIR="$(dirname "$0")/../config/includes.chroot/opt/alfred-offline-assets"
mkdir -p "$ASSETS_DIR"

echo "[+] Fetching Bun..."
curl --retry 3 -fsSL "https://github.com/oven-sh/bun/releases/latest/download/bun-linux-x64.zip" -o "$ASSETS_DIR/bun.zip"

echo "[+] Fetching uv..."
curl --retry 3 -fsSL "https://github.com/astral-sh/uv/releases/latest/download/uv-x86_64-unknown-linux-gnu.tar.gz" -o "$ASSETS_DIR/uv.tar.gz"

echo "[+] Fetching three.min.js..."
curl --retry 3 --max-time 900 -fsSL "https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js" -o "$ASSETS_DIR/three.min.js"

echo "[+] Fetching jQuery..."
curl --retry 3 --max-time 900 -fsSL "https://code.jquery.com/jquery-3.7.1.min.js" -o "$ASSETS_DIR/jquery-3.7.1.min.js"

echo "[+] Fetching Kubectl..."
KUBE_VERSION="$(curl -L -s https://dl.k8s.io/release/stable.txt)"
curl --retry 3 --max-time 900 -fsSL "https://dl.k8s.io/release/${KUBE_VERSION}/bin/linux/amd64/kubectl" -o "$ASSETS_DIR/kubectl"

echo "[+] Fetching Yggdrasil..."
# The 0166 hook curls the deb file URL directly
YGG_URL="$(curl -s https://api.github.com/repos/yggdrasil-network/yggdrasil-go/releases/latest | grep browser_download_url | grep 'amd64.deb' | head -n 1 | cut -d '"' -f 4)"
curl --retry 3 --max-time 900 -fsSL "$YGG_URL" -o "$ASSETS_DIR/yggdrasil-amd64.deb"

echo "[+] Done. Offline assets are cached in $ASSETS_DIR"
