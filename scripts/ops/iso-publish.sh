#!/bin/bash
# /home/root/iso-publish.sh — auto-publish a newly-built ISO
# Idempotent: safe to re-run; won't double-publish the same ISO.
# In the name of Yeshua, Jesus Christ of Bethlehem, King of the Universe.
set -uo pipefail

REPO="/home/root/law/alfredlinux-com-source-live"
SRC_ISO_DIR="$REPO/iso-output"
DST="/home/root/domains/alfredlinux.com/public_html/downloads"
LOG="/home/root/law/iso-publish.log"
STATE="/home/root/law/iso-publish.state"
KEYID="04426AB7A3988D84559D9B92B3BFEC4C80900BF9"

ts() { date '+%Y-%m-%d %H:%M:%S'; }
log() { echo "[$(ts)] $*" | tee -a "$LOG"; }

# Find the newest ISO in iso-output/
LATEST=$(ls -t "$SRC_ISO_DIR"/alfred-linux-7.77-*.iso "$SRC_ISO_DIR"/live-image-amd64.hybrid.iso 2>/dev/null | head -1)
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
SMOKE_RESULT="SKIPPED"
if [[ -x "$SMOKE" ]] && command -v qemu-system-x86_64 >/dev/null; then
    log "  running qemu smoke test (90s timeout)..."
    set +e
    "$SMOKE" "$DST_ISO" 2>&1 | tee -a "$LOG" >/dev/null
    SMOKE_RC=${PIPESTATUS[0]}
    set -e 2>/dev/null || true
    set -uo pipefail
    if [[ "$SMOKE_RC" -eq 0 ]]; then
        log "  smoke test: PASS (rc=0)"
        SMOKE_RESULT="PASS"
    else
        log "  smoke test: did not pass (rc=$SMOKE_RC) — keeping ISO published but flagging"
        SMOKE_RESULT="FAIL"
    fi
fi

# 9) Update version.json
VJSON="$DST/../api/version.json"
if [[ -f "$VJSON" ]]; then
    BUILD_HASH=$(cd "$REPO" && git rev-parse --short HEAD 2>/dev/null || echo unknown)
    cat > "$VJSON" <<EOF
{
  "kernel": "7.0.12",
  "build": "$BUILD_HASH",
  "iso": "$CANON_NAME",
  "iso_sha256": "$ISO_HASH",
  "iso_size": $(stat -c%s "$DST_ISO"),
  "smoke_test": "$SMOKE_RESULT",
  "updated": "$(date -u +%Y-%m-%dT%H:%M:%SZ)",
  "scripture": "Matthew 1:17",
  "in_the_name_of": "Yeshua, Jesus Christ of Bethlehem, King of the Universe"
}
EOF
    chmod 644 "$VJSON"
    log "  /api/version.json updated"
fi

# 9b) Update /api/public-status.json — flip dashboard to Published + smoke result (p33)
PSJSON="$DST/../api/public-status.json"
if [[ -d "$(dirname "$PSJSON")" ]]; then
    case "$SMOKE_RESULT" in
        PASS)    PHASE="Published"; PCT=100; NOTE="Release published. Smoke test passed." ;;
        FAIL)    PHASE="Published (smoke flagged)"; PCT=99; NOTE="Release published. Smoke test flagged — under review." ;;
        *)       PHASE="Published"; PCT=100; NOTE="Release published." ;;
    esac
    cat > "$PSJSON" <<EOF
{
  "release_name": "Alfred Linux 7.77",
  "tagline": "$CANON_NAME is available for download.",
  "progress_pct": $PCT,
  "phase_label": "$PHASE",
  "eta_window": "available now",
  "public_note": "$NOTE",
  "iso_name": "$CANON_NAME",
  "iso_sha256": "$ISO_HASH",
  "iso_size": $(stat -c%s "$DST_ISO"),
  "smoke_test": "$SMOKE_RESULT",
  "quality_gates": [
    "Boot test",
    "Integrity checks",
    "Smoke verification",
    "Release signing",
    "Publish"
  ],
  "last_updated_epoch": $(date -u +%s)
}
EOF
    chmod 644 "$PSJSON"
    log "  /api/public-status.json updated (phase=$PHASE smoke=$SMOKE_RESULT)"
fi


# 10) Append to publish state
echo "[$(ts)] published $CANON_NAME sha256=$ISO_HASH" >> "$STATE"

# 11) Self-heal includes/ga-release-state.php
GA_STATE="/home/root/domains/alfredlinux.com/public_html/includes/ga-release-state.php"
if [[ -f "$GA_STATE" ]]; then
    BASENAME_NOEXT="${CANON_NAME%.iso}"
    cp -a "$GA_STATE" "$GA_STATE.bak.$(date +%s)" 2>/dev/null
    # Update $gaIsoBasename
    sed -i -E "s|(\\\$gaIsoBasename\s*=\s*')[^']+(';)|\1${BASENAME_NOEXT}\2|" "$GA_STATE"
    # Extract btih from the freshly-written torrent and update $gaTorrentBtihHex
    if [[ -f "$DST_ISO.torrent" ]] && command -v python3 >/dev/null; then
        BTIH=$(python3 - "$DST_ISO.torrent" <<'PY2'
import sys, hashlib
def decode(d):
    def parse():
        nonlocal i
        c = d[i:i+1]
        if c.isdigit():
            j = d.index(b':', i); n = int(d[i:j]); i = j+1+n; return d[j+1:i]
        if c == b'i':
            j = d.index(b'e', i); v = int(d[i+1:j]); i = j+1; return v
        if c == b'l':
            i += 1; out = []
            while d[i:i+1] != b'e': out.append(parse())
            i += 1; return out
        if c == b'd':
            i += 1; out = {}
            while d[i:i+1] != b'e':
                k = parse(); v = parse(); out[k] = v
            i += 1; return out
    i = 0
    return parse()
data = open(sys.argv[1],'rb').read()
# locate info dict bencoded slice
# simple: re-encode info from parsed dict
def encode(o):
    if isinstance(o,int): return b'i'+str(o).encode()+b'e'
    if isinstance(o,bytes): return str(len(o)).encode()+b':'+o
    if isinstance(o,list): return b'l'+b''.join(encode(x) for x in o)+b'e'
    if isinstance(o,dict):
        keys = sorted(o.keys())
        return b'd'+b''.join(encode(k)+encode(o[k]) for k in keys)+b'e'
top = decode(data)
info = top[b'info']
print(hashlib.sha1(encode(info)).hexdigest())
PY2
)
        if [[ -n "$BTIH" ]]; then
            sed -i -E "s|(\\\$gaTorrentBtihHex\s*=\s*')[0-9a-fA-F]+(';)|\1${BTIH}\2|" "$GA_STATE"
            log "  ga-release-state.php: basename=${BASENAME_NOEXT}, btih=${BTIH}"
        else
            log "  ga-release-state.php: basename updated; btih extraction FAILED"
        fi
    fi
fi

# 12) Self-heal /releases/7.77/ sums (release.php reads from there)
REL_DIR="/home/root/domains/alfredlinux.com/public_html/releases/7.77"
if [[ -d "$REL_DIR" ]]; then
    # SHA256SUMS
    echo "$ISO_HASH  $CANON_NAME" > "$REL_DIR/SHA256SUMS"
    # SHA512SUMS
    SHA512=$(sha512sum "$DST_ISO" | awk '{print $1}')
    echo "$SHA512  $CANON_NAME" > "$REL_DIR/SHA512SUMS"
    # BLAKE3SUMS
    if command -v b3sum >/dev/null; then
        (cd "$DST" && b3sum "$CANON_NAME") > "$REL_DIR/BLAKE3SUMS"
    fi
    # Re-sign each with same key
    for F in SHA256SUMS SHA512SUMS BLAKE3SUMS; do
        [[ -f "$REL_DIR/$F" ]] || continue
        rm -f "$REL_DIR/$F.asc"
        gpg --batch --yes --pinentry-mode=loopback --local-user "$KEYID" \
            --armor --detach-sign --output "$REL_DIR/$F.asc" "$REL_DIR/$F" 2>&1 | head -1
        chmod 644 "$REL_DIR/$F" "$REL_DIR/$F.asc" 2>/dev/null
    done
    # build-manifest.json
    cat > "$REL_DIR/build-manifest.json" <<JSON
{
  "release": "7.77",
  "iso": "$CANON_NAME",
  "sha256": "$ISO_HASH",
  "sha512": "$SHA512",
  "size_bytes": $(stat -c%s "$DST_ISO"),
  "published_utc": "$(date -u +%Y-%m-%dT%H:%M:%SZ)",
  "gpg_key": "$KEYID",
  "scripture": "Matthew 1:17",
  "in_the_name_of": "Yeshua, Jesus Christ of Bethlehem, King of the Universe"
}
JSON
    chmod 644 "$REL_DIR/build-manifest.json"
    log "  releases/7.77/: SHA256SUMS, SHA512SUMS, BLAKE3SUMS, .asc x3, build-manifest.json refreshed"
fi


log "PUBLISH COMPLETE: https://alfredlinux.com/downloads/$CANON_NAME"
log "  sha256: $ISO_HASH"
log "  SHA256SUMS-7.77.txt + .asc updated"

exit 0
