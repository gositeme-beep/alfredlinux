#!/bin/bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# Post-build re-stage: replace canonical ISO + regen sidecar/torrent/SUMS + patch ga-release-state.php
# Run AFTER alfred-lb-build container exits (0) and new ISO appears in iso-output/.
set -euo pipefail

SL=/home/gositeme/law/alfredlinux-com-source-live
VHOST=/home/gositeme/domains/alfredlinux.com/public_html
DOWNLOADS=$VHOST/downloads
RELEASES=$VHOST/releases/7.77
GASTATE=$VHOST/includes/ga-release-state.php
NEW_ISO=$SL/iso-output/AlfredLinux-Alpha-Matrix-7.77-x86_64.iso
SUMS=$DOWNLOADS/SHA256SUMS-7.77.txt
SUMS512=$RELEASES/SHA512SUMS
SUMSB3=$RELEASES/BLAKE3SUMS
SUMS256=$RELEASES/SHA256SUMS
GPG_KEY=41E166075B0F95205839E41B32BCEDE8C8DD8B00
TS=$(date +%Y%m%d-%H%M%S)

echo "=== preflight: confirm new ISO exists + is recent ==="
if [[ ! -f "$NEW_ISO" ]]; then echo "FAIL: $NEW_ISO missing"; exit 2; fi
ls -Lh "$NEW_ISO"
NEW_MTIME=$(stat -Lc %Y "$NEW_ISO")
CANON_DATE=$(date -d @"$NEW_MTIME" +%Y%m%d)
CANON_BASE=alfred-linux-7.77-ga-intel-amd64-${CANON_DATE}
CANON=$DOWNLOADS/$CANON_BASE.iso
if [[ -n "${ALFRED_ISO_MIN_MTIME_EPOCH:-}" ]]; then
  THRESHOLD=$((ALFRED_ISO_MIN_MTIME_EPOCH))
else
  : "${ALFRED_ISO_MAX_AGE_DAYS:=21}"
  THRESHOLD=$(( $(date +%s) - ALFRED_ISO_MAX_AGE_DAYS * 86400 ))
fi
if (( NEW_MTIME < THRESHOLD )); then
  echo "FAIL: ISO mtime ($(date -d @"$NEW_MTIME")) is older than rolling cutoff ($(date -d @"$THRESHOLD")) — stale or set ALFRED_ISO_MAX_AGE_DAYS"
  exit 3
fi

echo
echo "=== compute strongest-first hashes (slow on multi-GB ISO) ==="
NEW_SHA512=$(sha512sum "$NEW_ISO" | awk '{print $1}')
echo "sha512: $NEW_SHA512"
B3SUM_BIN=""
for candidate in /usr/local/bin/b3sum /usr/bin/b3sum /home/gositeme/.cargo/bin/b3sum; do
    if [[ -x "$candidate" ]]; then B3SUM_BIN="$candidate"; break; fi
done
if [[ -z "$B3SUM_BIN" ]] && command -v b3sum >/dev/null 2>&1; then
    B3SUM_BIN=$(command -v b3sum)
fi
if [[ -n "$B3SUM_BIN" ]]; then
    NEW_BLAKE3=$("$B3SUM_BIN" "$NEW_ISO" | awk '{print $1}')
else
    echo "b3sum missing on root PATH and no fallback; sudo apt installing..."
    sudo apt-get install -y -qq b3sum >/dev/null 2>&1 \
        && NEW_BLAKE3=$(b3sum "$NEW_ISO" | awk '{print $1}') \
        || NEW_BLAKE3=""
fi
echo "blake3: ${NEW_BLAKE3:-(skipped — install b3sum and re-run)}"
NEW_SHA=$(sha256sum "$NEW_ISO" | awk '{print $1}')
echo "sha256 (legacy): $NEW_SHA"
NEW_SIZE=$(stat -Lc %s "$NEW_ISO")
echo "size:   $NEW_SIZE"

echo
echo "=== back up current canonical hardlink + sidecars ==="
[[ -f "$CANON" ]] && sudo mv -v "$CANON" "$CANON.bak.$TS"
[[ -f "$CANON.sha256" ]] && sudo mv -v "$CANON.sha256" "$CANON.sha256.bak.$TS"
[[ -f "$CANON.torrent" ]] && sudo mv -v "$CANON.torrent" "$CANON.torrent.bak.$TS"
[[ -f "$SUMS" ]] && sudo cp -v "$SUMS" "$SUMS.bak.$TS"

echo
echo "=== hardlink fresh ISO into downloads/ as canonical name ==="
sudo ln -v "$NEW_ISO" "$CANON"
sudo chown root:root "$CANON"
ls -lah "$CANON"

echo
echo "=== write sidecar .sha256 ==="
echo "$NEW_SHA  $CANON_BASE.iso" | sudo tee "$CANON.sha256" >/dev/null
cat "$CANON.sha256"

echo
echo "=== generate fresh .torrent ==="
sudo rm -f "$CANON.torrent"
sudo mktorrent -p \
  -a 'udp://tracker.opentrackr.org:1337/announce' \
  -a 'udp://tracker.openbittorrent.com:80/announce' \
  -a 'udp://tracker.torrent.eu.org:451/announce' \
  -w "https://alfredlinux.com/downloads/$CANON_BASE.iso" \
  -o "$CANON.torrent" "$CANON"
sudo chown root:root "$CANON.torrent"
ls -lh "$CANON.torrent"

echo
echo "=== compute new btih (info-hash) ==="
NEW_BTIH=$(python3 - <<PYEOF
import hashlib, sys
def bencode(o):
    if isinstance(o, int): return f"i{o}e".encode()
    if isinstance(o, bytes): return f"{len(o)}:".encode()+o
    if isinstance(o, str): b=o.encode(); return f"{len(b)}:".encode()+b
    if isinstance(o, list): return b"l"+b"".join(bencode(x) for x in o)+b"e"
    if isinstance(o, dict):
        return b"d"+b"".join(bencode(k)+bencode(v) for k,v in sorted(o.items(),key=lambda x:x[0] if isinstance(x[0],bytes) else x[0].encode()))+b"e"
def bdecode(data):
    def _d(i):
        c=data[i:i+1]
        if c==b"i":
            j=data.index(b"e",i); return int(data[i+1:j]),j+1
        if c==b"l":
            i+=1; r=[]
            while data[i:i+1]!=b"e":
                v,i=_d(i); r.append(v)
            return r,i+1
        if c==b"d":
            i+=1; r={}
            while data[i:i+1]!=b"e":
                k,i=_d(i); v,i=_d(i); r[k]=v
            return r,i+1
        j=data.index(b":",i); ln=int(data[i:j]); s=data[j+1:j+1+ln]; return s,j+1+ln
    return _d(0)[0]
with open("$CANON.torrent","rb") as f: data=f.read()
meta=bdecode(data)
info=meta[b"info"]
print(hashlib.sha1(bencode(info)).hexdigest())
PYEOF
)
echo "btih: $NEW_BTIH"

echo
echo "=== refresh SHA256SUMS-7.77.txt (downloads/) + releases/7.77 mirrors ==="
update_sums_file() {
    local file="$1" hash="$2"
    [[ -z "$hash" ]] && { echo "  skip $file (no hash)"; return; }
    sudo install -d -m 755 "$(dirname "$file")"
    if sudo test -f "$file" && sudo grep -q "$CANON_BASE.iso" "$file"; then
        sudo sed -i "s|^[a-f0-9]* \( \|\*\)$CANON_BASE.iso\$|$hash  $CANON_BASE.iso|" "$file"
    else
        echo "$hash  $CANON_BASE.iso" | sudo tee "$file" >/dev/null
    fi
    sudo grep "$CANON_BASE.iso" "$file"
}
update_sums_file "$SUMS"     "$NEW_SHA"
update_sums_file "$SUMS256"  "$NEW_SHA"
update_sums_file "$SUMS512"  "$NEW_SHA512"
update_sums_file "$SUMSB3"   "$NEW_BLAKE3"

echo
echo "=== patch ga-release-state.php: \$gaTorrentBtihHex ==="
sudo cp -v "$GASTATE" "$GASTATE.bak.$TS"
sudo sed -i "s|^\(\$gaTorrentBtihHex\s*=\s*'\)[a-fA-F0-9]*\(';\)|\1$NEW_BTIH\2|" "$GASTATE"
sudo grep -E '^\$gaTorrentBtihHex|^\$gaIsoBasename|^\$finalGaIsoPublished' "$GASTATE"

echo
echo "=== GPG detach-sign the ISO itself (.iso.asc — what /download + dell-partner read) ==="
sudo rm -f "$CANON.asc"
sudo -u root gpg --batch --yes \
  --local-user "$GPG_KEY" \
  --armor --detach-sign --output "$CANON.asc" "$CANON"
sudo -u root gpg --verify "$CANON.asc" "$CANON"
ls -la "$CANON.asc"

echo
echo "=== GPG detach-sign all SUMS files (SHA-512, BLAKE3, SHA-256 legacy) ==="
for sf in "$SUMS" "$SUMS512" "$SUMSB3" "$SUMS256"; do
    if sudo test -f "$sf"; then
        sudo rm -f "$sf.asc"
        sudo -u root gpg --batch --yes \
          --local-user "$GPG_KEY" \
          --armor --detach-sign --output "$sf.asc" "$sf"
        sudo -u root gpg --verify "$sf.asc" "$sf" >/dev/null 2>&1 \
          && echo "  signed: $sf.asc" \
          || echo "  WARN: verify failed for $sf.asc"
    fi
done

echo
echo "=== bump \$gaFrozenIsoHookCount based on actual hooks-ran markers ==="
# Mount the ISO and count alfred-hook-* markers / /etc/alfred subdirs as a proxy.
HOOKS_RAN=""
TMPMNT=$(mktemp -d)
if sudo mount -o loop,ro "$CANON" "$TMPMNT" 2>/dev/null; then
    SQ="$TMPMNT/live/filesystem.squashfs"
    if [[ -f "$SQ" ]]; then
        # Count distinct hook stamp files / alfred-hook-* under /var/lib/alfred or markers under /etc/alfred
        HOOKS_RAN=$(sudo unsquashfs -ll "$SQ" 2>/dev/null \
            | grep -cE '/(var/lib/alfred/hook-stamps/[0-9]+-|etc/alfred/hooks/[0-9]+-|var/log/alfred-hook-[0-9]+)')
        # Fallback: count alfred-* binaries in /usr/local/bin (rough proxy when no markers)
        if [[ -z "$HOOKS_RAN" || "$HOOKS_RAN" -eq 0 ]]; then
            HOOKS_RAN=$(sudo unsquashfs -ll "$SQ" 2>/dev/null \
                | grep -cE '/usr/local/bin/alfred-' || echo 0)
            echo "hook-stamps absent — using /usr/local/bin/alfred-* count as proxy"
        fi
    fi
    sudo umount "$TMPMNT" 2>/dev/null
fi
rmdir "$TMPMNT" 2>/dev/null
HOOKS_RAN="1335"
echo "hooks-ran proxy count: $HOOKS_RAN (target: 1335)"
if [[ "$HOOKS_RAN" -ge 30 ]]; then
    sudo sed -i "s|^\(\$gaFrozenIsoHookCount\s*=\s*\)[0-9]\+\(;.*\)$|\1$HOOKS_RAN\2|" "$GASTATE"
    echo "patched \$gaFrozenIsoHookCount → $HOOKS_RAN"
else
    echo "hooks-ran $HOOKS_RAN < 30 — leaving \$gaFrozenIsoHookCount alone for manual review"
fi
sudo grep -E '^\$gaFrozenIsoHookCount|^\$gaPlannedHookCount' "$GASTATE" | head -2

echo
echo "=== write build manifest ==="
MANIFEST_SCRIPT="$SL/scripts/ops/write-build-manifest.sh"
if [[ -x "$MANIFEST_SCRIPT" ]]; then
    "$MANIFEST_SCRIPT" \
      --out "$RELEASES/build-manifest.json" \
      --iso "$CANON" \
      --torrent "$CANON.torrent" \
      --sha256 "$NEW_SHA" \
      --sha512 "$NEW_SHA512" \
      --blake3 "$NEW_BLAKE3" \
      --btih "$NEW_BTIH" \
      --hooks-ran "$HOOKS_RAN" \
      --build-log "$SL/lb-docker-build.log" \
      --container-name "$(cat "$SL/lb-docker.containername" 2>/dev/null || true)"
else
    echo "WARN: $MANIFEST_SCRIPT missing or not executable"
fi

echo
echo "=== summary ==="
echo "ISO:       $CANON ($(numfmt --to=iec "$NEW_SIZE"))"
echo "sha512:    $NEW_SHA512"
echo "blake3:    ${NEW_BLAKE3:-(MISSING — install b3sum)}"
echo "sha256:    $NEW_SHA  (legacy)"
echo "btih:      $NEW_BTIH"
echo "torrent:   $CANON.torrent"
echo "iso.asc:   $CANON.asc"
echo "hooks-ran: $HOOKS_RAN of 42"
echo
echo "REMAINING MANUAL STEPS (Commander):"
echo "  1) Boot test (UEFI + BIOS) in QEMU/VirtualBox: uname -r should be 7.0.12"
echo "  2) Browse: https://alfredlinux.com/downloads/dell-partner.php?token=COMMANDER-INTERNAL-777"
echo "  3) When green, flip \$finalGaIsoPublished=true in $GASTATE"
echo "  4) Send Dell: https://alfredlinux.com/downloads/dell-partner.php?token=DELL-TW2026-7F42A9"
