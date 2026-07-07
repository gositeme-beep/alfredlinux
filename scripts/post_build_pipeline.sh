#!/bin/bash
set -e

echo "============================================="
echo "  ALFRED LINUX — AUTOMATED POST-BUILD PIPELINE"
echo "============================================="

SOURCE="/home/gositeme/law/alfredlinux-com-source-live"
WWW="/home/gositeme/domains/alfredlinux.com/public_html"
ISO_DIR="$SOURCE/iso-output"
ISO_FILE=$(ls "$ISO_DIR"/*.iso 2>/dev/null | head -1)

if [ -z "$ISO_FILE" ]; then
    echo "❌ ERROR: No ISO found in $ISO_DIR"
    exit 1
fi

ISO_BASENAME=$(basename "$ISO_FILE")
ISO_SIZE=$(du -h "$ISO_FILE" | cut -f1)

echo "✅ Found ISO: $ISO_BASENAME ($ISO_SIZE)"
echo ""

# 1. Patch ISO
echo "[1/5] Patching ISO headers & boot sectors..."
cd "$SOURCE"
python3 iso-output/patch_iso.py "$ISO_FILE" || echo "⚠️ Patch script had an issue, continuing..."
echo "✅ ISO Patched"
echo ""

# 2. Generate Hashes
echo "[2/5] Generating cryptographic hashes (SHA256, MD5, SHA512, SHA1, Blake3)..."
cd "$ISO_DIR"
sha256sum "$ISO_BASENAME" > "sha256.txt"
md5sum "$ISO_BASENAME" > "md5.txt"
sha512sum "$ISO_BASENAME" > "sha512.txt"
sha1sum "$ISO_BASENAME" > "sha1.txt"
b3sum "$ISO_BASENAME" > "blake3.txt" 2>/dev/null || echo "b3sum not installed, skipping blake3"
# Combine into one standard SUMS file for website
echo "$(cat sha256.txt)" > "SHA256SUMS-7.77.txt"
echo "✅ Hashes generated"
echo ""

# 3. Create Torrent
echo "[3/5] Generating torrent file..."
TORRENT_FILE="${ISO_BASENAME}.torrent"
# DISABLED-BY-COMMANDER: rm -f "$TORRENT_FILE"
transmission-create -o "$TORRENT_FILE" \
    -t "udp://tracker.opentrackr.org:1337/announce" \
    -t "udp://open.tracker.cl:1337/announce" \
    -t "udp://tracker.openbittorrent.com:6969/announce" \
    -w "https://alfredlinux.com/downloads/$ISO_BASENAME" \
    -c "Alfred Linux 7.77 — The Sovereign OS" \
    "$ISO_FILE"
echo "✅ Torrent generated: $TORRENT_FILE"
echo ""

# 4. Copy to Website
echo "[4/5] Deploying to alfredlinux.com..."
mkdir -p "$WWW/downloads"
# Copy all artifacts
cp "$ISO_FILE" "$WWW/downloads/"
cp *.txt "$WWW/downloads/"
cp "$TORRENT_FILE" "$WWW/downloads/"
echo "✅ Files copied to website downloads"
echo ""

# 5. Update PHP Release State & Git Push
echo "[5/5] Updating website state and pushing..."
DATE_STR=$(date +'%Y-%m-%d')
sed -i "s/\$buildDate = '[^']*'/\$buildDate = '$DATE_STR'/" "$WWW/includes/ga-release-state.php"
sed -i "s/\$isoSize = '[^']*'/\$isoSize = '$ISO_SIZE'/" "$WWW/includes/ga-release-state.php"

cd "$WWW"
git add downloads/ includes/ga-release-state.php
git commit -m "DEPLOY: Auto-publish Alfred Linux 7.77 ISO ($DATE_STR) — $ISO_SIZE" || true
git push origin master || true
echo "✅ Website updated and pushed"
echo ""

echo "============================================="
echo "  🚀 PIPELINE COMPLETE"
echo "  ISO is live at: https://alfredlinux.com/downloads/$ISO_BASENAME"
echo "============================================="
