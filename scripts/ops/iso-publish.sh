#!/bin/bash
# /home/gositeme/iso-publish.sh — auto-publish a newly-built ISO
# Idempotent: safe to re-run; won't double-publish the same ISO.
# In the name of Yeshua, Jesus Christ of Bethlehem, King of the Universe.
set -uo pipefail

REPO="/home/gositeme/law/alfredlinux-com-source-live"
SRC_ISO_DIR="$REPO/iso-output"
DST="/home/gositeme/domains/alfredlinux.com/public_html/downloads"
LOG="/home/gositeme/law/iso-publish.log"
STATE="/home/gositeme/law/iso-publish.state"
KEYID="04426AB7A3988D84559D9B92B3BFEC4C80900BF9"

ts() { date '+%Y-%m-%d %H:%M:%S'; }
log() { echo "[$(ts)] $*" | tee -a "$LOG"; }

# Find the newest ISO in iso-output/
LATEST=$(ls -t "$SRC_ISO_DIR"/alfred-linux-7.77-*.iso 2>/dev/null | head -1)
if [[ -z "$LATEST" ]]; then
    # Also look in build/ in case it's there
    LATEST=$(ls -t "$REPO/build/binary"*.iso "$REPO/build/"*.hybrid.iso 2>/dev/null | head -1)
    if [[ -z "$LATEST" ]]; then
        log "no ISO found yet in $SRC_ISO_DIR or $REPO/build/"
        exit 0
    fi
fi

# Determine canonical name
CANON_DATE=$(date -u -d "@$(stat -c%Y "$LATEST")" +%Y%m%d)
CANON_NAME="alfred-linux-7.77-ga-intel-amd64-${CANON_DATE}.iso"
DST_ISO="$DST/$CANON_NAME"

# Idempotency: if already published with same checksum, skip
if [[ -f "$DST_ISO" ]]; then
    SRC_HASH=$(sha256sum "$LATEST" | awk '{print $1}')
    DST_HASH=$(sha256sum "$DST_ISO" | awk '{print $1}')
    if [[ "$SRC_HASH" == "$DST_HASH" ]]; then
        log "ALREADY PUBLISHED (matching sha256): $CANON_NAME — nothing to do"
        exit 0
    else
        log "REPLACING (sha256 differs): $CANON_NAME"
    fi
fi

log "PUBLISH START: $LATEST -> $DST_ISO"

# 1) Copy ISO atomically
TMP_ISO="${DST_ISO}.tmp.$$"
cp "$LATEST" "$TMP_ISO"
mv "$TMP_ISO" "$DST_ISO"
chmod 644 "$DST_ISO"
log "  copied $(du -h "$DST_ISO" | cut -f1)"

# 2) sha256 sidecar
ISO_HASH=$(sha256sum "$DST_ISO" | awk '{print $1}')
echo "$ISO_HASH  $CANON_NAME" > "$DST_ISO.sha256"
log "  sha256 sidecar written"

# 3) BLAKE3 sidecar (if available)
if command -v b3sum >/dev/null; then
    (cd "$DST" && b3sum "$CANON_NAME" > "$DST_ISO.blake3")
    log "  blake3 sidecar written"
fi

# 4) SHA256SUMS-7.77.txt — single line (only one ISO live at a time)
SUMS="$DST/SHA256SUMS-7.77.txt"
{
  echo "# Alfred Linux 7.77 ISO sha256 — generated $(date -Iseconds)"
  echo "# Verify: gpg --verify SHA256SUMS-7.77.txt.asc SHA256SUMS-7.77.txt"
  echo "# Then:   sha256sum -c SHA256SUMS-7.77.txt"
  echo "# In the name of Yeshua, Jesus Christ of Bethlehem, King of the Universe."
  echo "$ISO_HASH  $CANON_NAME"
} > "$SUMS"
chmod 644 "$SUMS"

# 5) Detached GPG signature on SHA256SUMS
SIG="$SUMS.asc"
rm -f "$SIG"
gpg --batch --yes --pinentry-mode=loopback \
    --local-user "$KEYID" \
    --armor --detach-sign --output "$SIG" "$SUMS" 2>&1 | head -3
chmod 644 "$SIG"

# Verify locally
if gpg --verify "$SIG" "$SUMS" 2>&1 | grep -q '^gpg: Good signature'; then
    log "  GPG sig: GOOD"
else
    log "  GPG sig: FAILED — investigate!"
    exit 1
fi

# 6) Detached GPG signature on the ISO itself (for direct partner verify)
ISO_SIG="$DST_ISO.asc"
rm -f "$ISO_SIG"
gpg --batch --yes --pinentry-mode=loopback \
    --local-user "$KEYID" \
    --armor --detach-sign --output "$ISO_SIG" "$DST_ISO" 2>&1 | head -3
chmod 644 "$ISO_SIG"
log "  ISO .asc written"

# 7) Torrent (if mktorrent available)
if command -v mktorrent >/dev/null; then
    TORRENT="$DST_ISO.torrent"
    rm -f "$TORRENT"
    mktorrent -l 20 -p \
        -a "udp://tracker.opentrackr.org:1337/announce" \
        -a "udp://tracker.openbittorrent.com:6969/announce" \
        -a "wss://alfredlinux.com/announce" \
        -o "$TORRENT" \
        -n "$CANON_NAME" \
        "$DST_ISO" 2>&1 | tail -2
    log "  torrent written: $(du -h "$TORRENT" | cut -f1)"
fi

# 8) Smoke test (if qemu available + script present)
SMOKE="$REPO/scripts/qemu-smoke-test.sh"
if [[ -x "$SMOKE" ]] && command -v qemu-system-x86_64 >/dev/null; then
    log "  running qemu smoke test (90s timeout)..."
    if "$SMOKE" "$DST_ISO" 2>&1 | tee -a "$LOG" | tail -3 | grep -q 'PASS\|ok'; then
        log "  smoke test: PASS"
    else
        log "  smoke test: did not pass — keeping ISO published but flagging"
    fi
fi

# 9) Update version.json
VJSON="$DST/../api/version.json"
if [[ -f "$VJSON" ]]; then
    BUILD_HASH=$(cd "$REPO" && git rev-parse --short HEAD 2>/dev/null || echo unknown)
    cat > "$VJSON" <<EOF
{
  "kernel": "7.0.3",
  "build": "$BUILD_HASH",
  "iso": "$CANON_NAME",
  "iso_sha256": "$ISO_HASH",
  "iso_size": $(stat -c%s "$DST_ISO"),
  "updated": "$(date -u +%Y-%m-%dT%H:%M:%SZ)",
  "scripture": "Matthew 1:17",
  "in_the_name_of": "Yeshua, Jesus Christ of Bethlehem, King of the Universe"
}
EOF
    chmod 644 "$VJSON"
    log "  /api/version.json updated"
fi

# 10) Append to publish state
echo "[$(ts)] published $CANON_NAME sha256=$ISO_HASH" >> "$STATE"

log "PUBLISH COMPLETE: https://alfredlinux.com/downloads/$CANON_NAME"
log "  sha256: $ISO_HASH"
log "  SHA256SUMS-7.77.txt + .asc updated"

exit 0
