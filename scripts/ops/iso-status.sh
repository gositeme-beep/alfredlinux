#!/bin/bash
# /home/gositeme/bin/iso-status.sh — single-command snapshot of the live build pipeline
# In the name of Yeshua, Jesus Christ of Bethlehem, King of the Universe.
set -uo pipefail

REPO=/home/gositeme/law/alfredlinux-com-source-live
DST=/home/gositeme/domains/alfredlinux.com/public_html/downloads
WLOG=/home/gositeme/law/iso-watchdog.log
PLOG=/home/gositeme/law/iso-publish.log

bold(){ printf '\033[1m%s\033[0m\n' "$*"; }
green(){ printf '\033[32m%s\033[0m' "$*"; }
yellow(){ printf '\033[33m%s\033[0m' "$*"; }
red(){ printf '\033[31m%s\033[0m' "$*"; }

bold "=== Alfred Linux 7.77 — Build Pipeline Status ==="
echo "  $(date -Is)"
echo

# 1) Container
CONTAINER=$(docker ps --no-trunc --format '{{.Names}}\t{{.Command}}\t{{.RunningFor}}\t{{.Status}}' 2>/dev/null \
    | grep -E 'lb-docker-inner-build|live-build|/usr/lib/live/build|alfred-lb' | head -1)
if [[ -n "$CONTAINER" ]]; then
    NAME=$(echo "$CONTAINER" | awk -F'\t' '{print $1}')
    UPFOR=$(echo "$CONTAINER" | awk -F'\t' '{print $3}')
    bold "[1/6] Container:  $(green ALIVE)  $NAME  (up $UPFOR)"
else
    NAME=""
    bold "[1/6] Container:  $(yellow none) (build idle or finished)"
fi

# 2) Phase detection (mksquashfs/xorriso/chroot)
if [[ -n "$NAME" ]]; then
    SQS=$(docker top "$NAME" 2>/dev/null | grep mksquashfs | head -1)
    XOR=$(docker top "$NAME" 2>/dev/null | grep xorriso | head -1)
    if [[ -n "$XOR" ]]; then
        bold "[2/6] Phase:      $(green xorriso) (writing ISO image)"
    elif [[ -n "$SQS" ]]; then
        SQT=$(echo "$SQS" | awk '{print $7}')
        bold "[2/6] Phase:      $(green mksquashfs) (compressing chroot, cpu=$SQT)"
    else
        bold "[2/6] Phase:      chroot/binary stage"
    fi
else
    bold "[2/6] Phase:      $(yellow n/a)"
fi

# 3) Sizes
CHROOT=$(du -sh "$REPO/build/chroot" 2>/dev/null | awk '{print $1}')
BINARY=$(du -sh "$REPO/build/binary" 2>/dev/null | awk '{print $1}')
SQUASH=$(stat -c%s "$REPO/build/binary/live/filesystem.squashfs" 2>/dev/null)
SQUASH_H="not-yet"
[[ -n "${SQUASH:-}" ]] && SQUASH_H=$(numfmt --to=iec "$SQUASH")
bold "[3/6] Sizes:      chroot=${CHROOT:-n/a}  binary=${BINARY:-n/a}  squashfs=$SQUASH_H"

# 4) Built ISO (in build/ or iso-output/)
BUILD_ISO=$(ls -t "$REPO/build/"*.iso 2>/dev/null | head -1)
OUT_ISO=$(ls -t "$REPO/iso-output/"*.iso 2>/dev/null | head -1)
if [[ -n "$OUT_ISO" ]]; then
    SZ=$(du -h "$OUT_ISO" | awk '{print $1}')
    AGE=$(( $(date +%s) - $(stat -c%Y "$OUT_ISO") ))
    bold "[4/6] Built ISO:  $(green $SZ)  $(basename "$OUT_ISO")  (${AGE}s old)"
elif [[ -n "$BUILD_ISO" ]]; then
    SZ=$(du -h "$BUILD_ISO" | awk '{print $1}')
    bold "[4/6] Built ISO:  $(yellow $SZ)  $(basename "$BUILD_ISO")  (in build/, not yet copied)"
else
    bold "[4/6] Built ISO:  $(yellow none yet)"
fi

# 5) Published ISO
PUB_ISO=$(ls -t "$DST"/alfred-linux-7.77-*.iso 2>/dev/null | head -1)
if [[ -n "$PUB_ISO" ]]; then
    SZ=$(du -h "$PUB_ISO" | awk '{print $1}')
    SHA=$(awk '{print $1}' "$PUB_ISO.sha256" 2>/dev/null | cut -c1-12)
    AGE=$(( $(date +%s) - $(stat -c%Y "$PUB_ISO") ))
    AGE_H=$(( AGE / 60 ))
    bold "[5/6] Published:  $(green LIVE)  $(basename "$PUB_ISO")  ${SZ}  sha=${SHA}…  (${AGE_H} min ago)"
    echo "         URL: https://alfredlinux.com/downloads/$(basename "$PUB_ISO")"
    [[ -f "$PUB_ISO.asc" ]] && echo "         GPG: $(green signed) ($PUB_ISO.asc)"
    [[ -f "$PUB_ISO.torrent" ]] && echo "         BT:  $(basename "$PUB_ISO.torrent")"
else
    bold "[5/6] Published:  $(yellow none yet) — waiting for build to finish"
fi

# 6) Recent log lines
echo
bold "[6/6] Recent activity:"
echo "  build.log:"
tail -3 "$REPO/build.log" 2>/dev/null | sed 's/^/    /'
echo "  iso-watchdog.log:"
tail -2 "$WLOG" 2>/dev/null | sed 's/^/    /'
echo "  iso-publish.log:"
tail -2 "$PLOG" 2>/dev/null | sed 's/^/    /' || echo "    (none yet)"

echo
bold "==============================================="
