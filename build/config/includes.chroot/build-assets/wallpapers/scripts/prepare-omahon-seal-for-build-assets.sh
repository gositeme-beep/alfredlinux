#!/bin/bash
# Generate omahon-seal.png in build-assets/wallpapers/{8k,4k,1080p}/ for 0285 §7
# kingdom-media copies (optional). Requires ImageMagick `convert`.
# Source should be a LARGE raster: short edge 2048+ px (4096+ ideal). 768-class
# sources look soft on 8K (7680x4320) — no upscale trick fixes a tiny master.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
RAW="$ROOT/raw/omahon-seal-source.png"
if [[ ! -f "$RAW" ]]; then
  echo "Missing $RAW — add the seal scan or artwork first."
  exit 1
fi
if command -v identify &>/dev/null; then
  W=$(identify -format '%w' "$RAW")
  H=$(identify -format '%h' "$RAW")
  MINSIDE="$W"; [[ "$H" -lt "$W" ]] && MINSIDE="$H"
  if [[ "$MINSIDE" -lt 2048 ]]; then
    echo "[prepare-omahon-seal] WARNING: ${W}x${H} — short edge < 2048 px; 8K wallpaper will look mushy. Replace raw with a 2048+ (ideally 4096+) px export. See BEFORE-GA-SEAL.txt" >&2
  fi
fi
if ! command -v convert &>/dev/null; then
  echo "ImageMagick (convert) not installed on build host."
  exit 1
fi
for spec in "7680x4320:8k" "3840x2160:4k" "1920x1080:1080p"; do
  IFS=: read -r SIZE DIR <<<"$spec"
  mkdir -p "$ROOT/$DIR"
  convert "$RAW" \( -size "$SIZE" xc:'#0a0a14' \) +swap -gravity center -compose over -composite \
    -filter Lanczos -resize "${SIZE}>" -gravity center -extent "$SIZE" \
    -quality 98 "$ROOT/$DIR/omahon-seal.png"
  echo "Wrote $ROOT/$DIR/omahon-seal.png"
done
