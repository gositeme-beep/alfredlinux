#!/bin/bash
# ═══════════════════════════════════════════════════════════════════
# DEPRECATED / NOT FOR GA — zoompan on stills; use a real master MP4 instead.
#
# This is honest “expensive motion on photographs,” fit for wallpaper reels —
# not the same as commissioned footage, 3D, or generative video (Veo, Runway,
# Luma, etc.). For the Kingdom ISO story you want people to *feel*, prefer a
# hand-authored master: place kingdom-of-god-edition.mp4 (+ optional clips/)
# from your cinematic pipeline and SKIP this script (see videos/
# README-PREMIUM-BIBLICAL-VIDEO.txt).
#
# Phase 1: Render each scene individually with zoompan
# Phase 2: Crossfade all scenes together
# ═══════════════════════════════════════════════════════════════════
set -e

if [[ "${KINGDOM_I_WANT_KEN_BURNS_STILLS:-}" != "1" ]]; then
  cat >&2 <<'EOF'
[build-kingdom-video] REFUSED — this script only zooms/pans static PNGs (Ken Burns).
It is NOT a biblical motion picture and will burn CPU/GPU time re-encoding stills.

For the real story: commission or generate true video, export
  build-assets/videos/kingdom-of-god-edition.mp4
and read: build-assets/videos/README-PREMIUM-BIBLICAL-VIDEO.txt

To run this legacy slideshow anyway (you accept stills-in-motion):
  KINGDOM_I_WANT_KEN_BURNS_STILLS=1 bash build-assets/build-kingdom-video.sh
EOF
  exit 3
fi

# Optional env for heavier payload (8K, longer scenes, lower CRF = bigger files):
#   KINGDOM_W=7680 KINGDOM_H=4320 KINGDOM_DUR=15 KINGDOM_FPS=24 \
#   KINGDOM_CRF_SCENE=12 KINGDOM_CRF_MERGE=14 ./build-kingdom-video.sh
FFMPEG="${FFMPEG:-/home/gositeme/bin/ffmpeg}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WALLDIR="${KINGDOM_WALLDIR:-$SCRIPT_DIR/wallpapers/4k}"
OUTDIR="${KINGDOM_OUTDIR:-$SCRIPT_DIR/videos}"
CLIPS="$OUTDIR/clips"
OUTPUT="$OUTDIR/kingdom-of-god-edition.mp4"

W="${KINGDOM_W:-3840}"
H="${KINGDOM_H:-2160}"
FPS="${KINGDOM_FPS:-24}"
DUR="${KINGDOM_DUR:-10}"
CR_SCENE="${KINGDOM_CRF_SCENE:-16}"
CR_MERGE="${KINGDOM_CRF_MERGE:-18}"
# Supersampled canvas for zoompan (1.5× output — same ratio as legacy 4K preset)
SUP_W=$((W * 3 / 2))
SUP_H=$((H * 3 / 2))

mkdir -p "$CLIPS"
rm -f "$CLIPS"/*.mp4 2>/dev/null

MISSING=0
for i in "${!IMGS[@]}"; do
  IMG="${IMGS[$i]}"
  if [[ ! -f "$WALLDIR/$IMG" ]]; then
    echo "[build-kingdom-video] MISSING still: $WALLDIR/$IMG" >&2
    MISSING=1
  fi
done
if [[ "$MISSING" -ne 0 ]]; then
  echo "[build-kingdom-video] Fix missing PNGs under $WALLDIR or edit IMGS[] in this script." >&2
  exit 2
fi

echo "╔═══════════════════════════════════════════════════════════╗"
echo "║  THE KING'S VIDEO — Full Ken Burns 4K Cinematic          ║"
echo "║  Phase 1: Render 18 scenes with zoompan                  ║"
echo "║  Phase 2: Crossfade into one glorious video              ║"
echo "║  Resolution: ${W}×${H} @ ${FPS}fps (CRF scene/merge ${CR_SCENE}/${CR_MERGE}) ║"
echo "║  THE EXPENSIVE ONE — fit for a King                      ║"
echo "╚═══════════════════════════════════════════════════════════╝"

# Images in narrative order
declare -a IMGS=(
  "kingdom-throne-4k.png"
  "gods-throne-4k.png"
  "risen-king-4k.png"
  "crown-of-glory-4k.png"
  "lion-of-judah-4k.png"
  "daniel-lions-den-4k.png"
  "mount-sinai-4k.png"
  "sanctuary-dawn-4k.png"
  "living-waters-4k.png"
  "eden-garden-4k.png"
  "edens-garden-4k.png"
  "new-jerusalem-4k.png"
  "new-jerusalem-descending-4k.png"
  "perez-crest-4k.png"
  "dynasty-gateway-4k.png"
  "perez-family-legacy-4k.png"
  "kingdom-seal-4k.png"
  "sovereign-dark-4k.png"
)

NFRAMES=$((DUR * FPS))

# Ken Burns motions: zoom_expr, x_expr, y_expr
# Each scene gets a different cinematic motion
declare -a ZOOM_EXPRS
declare -a X_EXPRS
declare -a Y_EXPRS

# 0:  Slow zoom in, center
ZOOM_EXPRS[0]="'min(zoom+0.001,1.25)'"
X_EXPRS[0]="'iw/2-(iw/zoom/2)'"
Y_EXPRS[0]="'ih/2-(ih/zoom/2)'"
# 1:  Slow zoom out, center
ZOOM_EXPRS[1]="'if(eq(on,1),1.25,max(zoom-0.001,1.0))'"
X_EXPRS[1]="'iw/2-(iw/zoom/2)'"
Y_EXPRS[1]="'ih/2-(ih/zoom/2)'"
# 2:  Zoom in, drift right
ZOOM_EXPRS[2]="'min(zoom+0.0008,1.2)'"
X_EXPRS[2]="'min(on*0.4,iw-iw/zoom)'"
Y_EXPRS[2]="'ih/2-(ih/zoom/2)'"
# 3:  Zoom in, drift left
ZOOM_EXPRS[3]="'min(zoom+0.0008,1.2)'"
X_EXPRS[3]="'max(iw-iw/zoom-on*0.4,0)'"
Y_EXPRS[3]="'ih/2-(ih/zoom/2)'"
# 4:  Zoom in, drift down-right
ZOOM_EXPRS[4]="'min(zoom+0.001,1.15)'"
X_EXPRS[4]="'min(on*0.3,iw-iw/zoom)'"
Y_EXPRS[4]="'min(on*0.2,ih-ih/zoom)'"
# 5:  Zoom out, drift up
ZOOM_EXPRS[5]="'if(eq(on,1),1.25,max(zoom-0.001,1.0))'"
X_EXPRS[5]="'iw/2-(iw/zoom/2)'"
Y_EXPRS[5]="'max(ih-ih/zoom-on*0.3,0)'"
# 6:  Gentle zoom in from top-left corner
ZOOM_EXPRS[6]="'min(zoom+0.0012,1.3)'"
X_EXPRS[6]="'0'"
Y_EXPRS[6]="'0'"
# 7:  Zoom out revealing from center
ZOOM_EXPRS[7]="'if(eq(on,1),1.3,max(zoom-0.0012,1.0))'"
X_EXPRS[7]="'iw/2-(iw/zoom/2)'"
Y_EXPRS[7]="'ih/2-(ih/zoom/2)'"
# 8:  Pan right, slight zoom
ZOOM_EXPRS[8]="'min(zoom+0.0005,1.1)'"
X_EXPRS[8]="'min(on*0.5,iw-iw/zoom)'"
Y_EXPRS[8]="'ih/3'"

N=${#IMGS[@]}

# ═══════════════════════════════════════════════════
# PHASE 1: Render individual Ken Burns clips
# ═══════════════════════════════════════════════════
echo ""
echo "═══ PHASE 1: Rendering ${N} Ken Burns scenes ═══"
echo ""

for i in "${!IMGS[@]}"; do
  IMG="${IMGS[$i]}"
  IDX=$((i % 9))
  CLIPOUT=$(printf "$CLIPS/scene-%02d.mp4" "$i")
  
  if [[ -f "$CLIPOUT" ]]; then
    echo "  [$(($i+1))/${N}] $IMG — already done, skipping"
    continue
  fi
  
  echo "  [$(($i+1))/${N}] $IMG — motion pattern $IDX..."
  
  if ! $FFMPEG -y -loop 1 -i "$WALLDIR/$IMG" \
    -vf "scale=${SUP_W}x${SUP_H},zoompan=z=${ZOOM_EXPRS[$IDX]}:d=${NFRAMES}:s=${W}x${H}:fps=${FPS}:x=${X_EXPRS[$IDX]}:y=${Y_EXPRS[$IDX]}" \
    -c:v libx264 -preset fast -crf "$CR_SCENE" -pix_fmt yuv420p \
    -t $DUR "$CLIPOUT"; then
    echo "[build-kingdom-video] ffmpeg FAILED on $IMG — see errors above (often: bad PNG, wrong ffmpeg build)." >&2
    exit 1
  fi
  
  SIZE=$(du -sh "$CLIPOUT" | cut -f1)
  echo "         → $SIZE"
done

echo ""
echo "═══ PHASE 1 COMPLETE — All ${N} scenes rendered ═══"
ls -lh "$CLIPS"/scene-*.mp4 | awk '{print "  "$NF, $5}'

# ═══════════════════════════════════════════════════
# PHASE 2: Crossfade all clips together
# ═══════════════════════════════════════════════════
echo ""
echo "═══ PHASE 2: Crossfading all scenes ═══"
echo ""

FADE=1.5
TRANSITIONS=(fade dissolve slideright slideleft circleopen radial wiperight wipeleft smoothup smoothdown)

# Build input args
INPUT_ARGS=""
for i in $(seq 0 $((N-1))); do
  CLIP=$(printf "$CLIPS/scene-%02d.mp4" "$i")
  INPUT_ARGS="$INPUT_ARGS -i $CLIP"
done

# Build xfade chain
FILTER=""
LAST="[0:v]"

for (( i=1; i<N; i++ )); do
  OFFSET=$(python3 -c "print(round($i * ($DUR - $FADE), 2))")
  TIDX=$(( (i-1) % ${#TRANSITIONS[@]} ))
  TRANS=${TRANSITIONS[$TIDX]}
  OUT="[xf$i]"
  FILTER="${FILTER}${LAST}[$i:v]xfade=transition=${TRANS}:duration=${FADE}:offset=${OFFSET}${OUT};"
  LAST="$OUT"
done

FILTER="${FILTER%;}"

echo "Crossfading ${N} scenes with transitions..."
echo "Output: $OUTPUT"
echo ""

$FFMPEG -y $INPUT_ARGS \
  -filter_complex "$FILTER" \
  -map "[$LAST]" \
  -c:v libx264 -preset medium -crf "$CR_MERGE" \
  -pix_fmt yuv420p -movflags +faststart \
  -metadata title="Alfred Linux 7.77 — Kingdom of God Edition" \
  -metadata artist="Commander Danny William Perez" \
  -metadata comment="SOLI DEO GLORIA — Every king needs a kingdom." \
  "$OUTPUT" 2>&1 | grep -E "frame=|fps=|time=|size=|bitrate=" | tail -3

echo ""
echo "╔═══════════════════════════════════════════════════════════╗"
echo "║  THE KING'S VIDEO — COMPLETE                             ║"
ls -lh "$OUTPUT" | awk '{printf "║  Size: %-48s ║\n", $5}'
echo "║  SOLI DEO GLORIA                                         ║"
echo "╚═══════════════════════════════════════════════════════════╝"
