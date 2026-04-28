#!/bin/bash
# Install a newly generated omahon seal master and rebuild 8K / 4K / 1080p pack.
# Run this the same day (or just before) you run build-unified.sh ga so the ISO
# does not pin an old seal you have rejected.
#
# Usage:
#   bash build-assets/wallpapers/scripts/apply-new-omahon-source.sh /path/to/your-new-seal.png
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SRC="${1:-}"
if [[ -z "$SRC" || ! -f "$SRC" ]]; then
  echo "Usage: $0 /path/to/new-seal.png" >&2
  exit 1
fi
if ! command -v identify &>/dev/null; then
  echo "ImageMagick (identify) required." >&2
  exit 1
fi
W=$(identify -format '%w' "$SRC" 2>/dev/null || echo 0)
H=$(identify -format '%h' "$SRC" 2>/dev/null || echo 0)
MIN="$W"; [[ "$H" -lt "$W" ]] && MIN="$H"
MINREQ="${OMAHON_MIN_SHORT_EDGE:-2048}"
if [[ "${OMAHON_ALLOW_SMALL_SOURCE:-}" != "1" && "$MIN" -lt "$MINREQ" ]]; then
  echo "ERROR: $SRC is ${W}x${H} — short edge ${MIN} px; need at least ${MINREQ} px for 8K/4K that stay sharp (ideal 4096+ on the short edge)." >&2
  echo "Re-export your seal at higher resolution, or for non-GA / dev only:  OMAHON_ALLOW_SMALL_SOURCE=1  $0 \"$SRC\"" >&2
  exit 1
fi
ARCH="$ROOT/raw/archive"
mkdir -p "$ARCH"
STAMP="$(date -u +%Y%m%d-%H%M%S)"
if [[ -f "$ROOT/raw/omahon-seal-source.png" ]]; then
  cp -v "$ROOT/raw/omahon-seal-source.png" "$ARCH/omahon-seal-source-backup-$STAMP.png"
  echo "Backed up previous raw to: $ARCH/omahon-seal-source-backup-$STAMP.png"
fi
cp -v "$SRC" "$ROOT/raw/omahon-seal-source.png"
bash "$ROOT/scripts/prepare-omahon-seal-for-build-assets.sh"
echo ""
echo "=== SHA-256 (paste into OMAHON-SEAL-PACK-READY.txt for your frozen build) ==="
( cd "$ROOT" && sha256sum raw/omahon-seal-source.png 8k/omahon-seal.png 4k/omahon-seal.png 1080p/omahon-seal.png )
echo "Done. Next: commit or keep tree, then run your ISO build when other GA gates are met."
