#!/bin/bash
# SPDX-License-Identifier: Apache-2.0
# SPDX-FileCopyrightText: 2026 Alfred Trust <alfred@alfredlinux.com>
# In the name of Yeshua, Jesus Christ of Bethlehem, King of the Universe.
# seal-iso.sh — seal a finished ISO into the covenant chain.
# Usage: seal-iso.sh <path-to-iso>
# In the name of Yeshua, Jesus Christ of Bethlehem, King of the Universe.
set -uo pipefail

ISO="${1:-}"
if [[ -z "$ISO" || ! -f "$ISO" ]]; then
    echo "Usage: $0 <path-to-iso>"
    exit 1
fi

SRC="/home/root/law/alfredlinux-com-source-live"
WEB="/home/root/domains/alfredlinux.com/public_html"
CHAIN="$WEB/covenant-chain.log"
MANIFEST="$SRC/BUILD-MANIFEST.json"
TS=$(date -u +%Y-%m-%dT%H:%M:%SZ)

ISO_NAME=$(basename "$ISO")
ISO_SIZE=$(stat -c%s "$ISO")
echo "[SEAL] Hashing $ISO_NAME ($((ISO_SIZE/1024/1024)) MiB)..."
ISO_SHA=$(sha256sum "$ISO" | awk '{print $1}')
echo "[SEAL]   sha256: $ISO_SHA"

# Manifest hash (whatever was sealed alongside)
MANIFEST_SHA=""
if [[ -f "$MANIFEST" ]]; then
    MANIFEST_SHA=$(sha256sum "$MANIFEST" | awk '{print $1}')
fi

# Previous chain head
PREV="0000000000000000000000000000000000000000000000000000000000000000"
if [[ -f "$CHAIN" && -s "$CHAIN" ]]; then
    PREV=$(tail -1 "$CHAIN" | python3 -c 'import sys,json
try: print(json.loads(sys.stdin.read()).get("hash",""))
except: print("")')
    [[ -z "$PREV" ]] && { echo "[SEAL] ✗ chain tail unparsable"; exit 1; }
fi
echo "[SEAL]   prev: ${PREV:0:16}..."

# Build entry
DATA=$(python3 -c "
import json
d={
  'event':'iso_sealed',
  'iso_name': '$ISO_NAME',
  'iso_size': $ISO_SIZE,
  'iso_sha256': '$ISO_SHA',
  'manifest_sha256': '$MANIFEST_SHA',
  'declaration': 'In the name of Yeshua, Jesus Christ of Bethlehem, King of the Universe.',
  'scripture': 'It is finished. — John 19:30',
  'ts': '$TS'
}
print(json.dumps(d, separators=(',',':')))
")
HASH=$(printf '%s%s' "$PREV" "$DATA" | sha256sum | awk '{print $1}')
ENTRY=$(python3 -c "
import json
print(json.dumps({'prev':'$PREV','data':$DATA,'hash':'$HASH'}, separators=(',',':')))
")
echo "$ENTRY" >> "$CHAIN"
echo "[SEAL]   appended chain entry: ${HASH:0:16}..."

# Sidecar files next to ISO
ISO_DIR=$(dirname "$ISO")
echo "$ISO_SHA  $ISO_NAME" > "$ISO_DIR/$ISO_NAME.sha256"
cp "$MANIFEST" "$ISO_DIR/$ISO_NAME.manifest.json" 2>/dev/null || true
echo "$ENTRY" > "$ISO_DIR/$ISO_NAME.chain.json"
echo "[SEAL]   sidecars: .sha256 .manifest.json .chain.json"

# Optional GPG sign
if command -v gpg >/dev/null && gpg --list-secret-keys 2>/dev/null | grep -q '^sec'; then
    gpg --armor --detach-sign --output "$ISO_DIR/$ISO_NAME.sig" "$ISO" 2>/dev/null \
        && echo "[SEAL]   ✓ GPG-signed → $ISO_NAME.sig" \
        || echo "[SEAL]   ⚠ GPG sign failed (skip)"
else
    echo "[SEAL]   ⚠ no GPG secret key — skipping signature"
fi

echo "[SEAL] ✓ DONE — ISO sealed in covenant chain. John 19:30 — It is finished."
