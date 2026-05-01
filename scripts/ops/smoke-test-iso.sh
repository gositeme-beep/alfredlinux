#!/bin/bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# Smoke-test the freshly-built ISO before re-staging.
# Verifies: ISO is recent, has Alfred content in squashfs, hooks ran.
set -euo pipefail

SL=/home/gositeme/law/alfredlinux-com-source-live
ISO=$SL/iso-output/live-image-amd64.hybrid.iso
if [[ -n "${ALFRED_ISO_MIN_MTIME_EPOCH:-}" ]]; then
  THRESHOLD=$((ALFRED_ISO_MIN_MTIME_EPOCH))
else
  : "${ALFRED_ISO_MAX_AGE_DAYS:=21}"
  THRESHOLD=$(( $(date +%s) - ALFRED_ISO_MAX_AGE_DAYS * 86400 ))
fi

echo "=== A. ISO file exists + recent ==="
if [[ ! -f "$ISO" ]]; then echo "FAIL: $ISO missing"; exit 2; fi
ls -lh "$ISO"
MTIME=$(stat -c %Y "$ISO")
SIZE=$(stat -c %s "$ISO")
echo "size: $(numfmt --to=iec "$SIZE"), mtime: $(date -d @"$MTIME")"
if (( MTIME < THRESHOLD )); then echo "FAIL: ISO mtime older than rolling cutoff ($(date -d @"$THRESHOLD")) — stale or widen ALFRED_ISO_MAX_AGE_DAYS"; exit 3; fi

echo
echo "=== B. ISO is bootable hybrid (xorriso/iso9660 magic) ==="
sudo file "$ISO" | head -3

echo
echo "=== C. Mount ISO + check squashfs ==="
MNT=$(mktemp -d)
smoke_cleanup_mount_only() {
  sudo umount "$MNT" 2>/dev/null || true
  rmdir "$MNT" 2>/dev/null || true
}
sudo mount -o loop,ro "$ISO" "$MNT"
trap smoke_cleanup_mount_only EXIT

ls -la "$MNT/" | head -20
SQ="$MNT/live/filesystem.squashfs"
if [[ ! -f "$SQ" ]]; then echo "FAIL: $SQ missing"; exit 4; fi
echo
echo "squashfs:"
ls -lh "$SQ"
sudo unsquashfs -s "$SQ" 2>&1 | head -20

echo
echo "=== D. Squashfs Alfred content audit ==="
SMNT=$(mktemp -d)
smoke_cleanup_all() {
  sudo umount "$MNT" 2>/dev/null || true
  sudo rm -rf "$SMNT" 2>/dev/null || true
  rmdir "$MNT" 2>/dev/null || true
}
sudo unsquashfs -d "$SMNT/sq" -ll "$SQ" 2>/dev/null | tee /tmp/sq-listing.txt >/dev/null || true
trap smoke_cleanup_all EXIT
echo "Total entries: $(wc -l < /tmp/sq-listing.txt)"
for path in "etc/alfred" "usr/share/backgrounds" "opt/alfred-ide-extensions" "usr/share/plymouth/themes" "etc/skel/.config/alfred" "usr/lib/alfred"; do
  # grep -c prints 0 and exits 1 when there are no matches — do not append `|| echo 0` or COUNT becomes "0\n0".
  if c=$(grep -cE "^\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+/?$path" /tmp/sq-listing.txt 2>/dev/null); then
    COUNT=$c
  else
    COUNT=0
  fi
  if (( COUNT > 0 )); then
    echo "  OK   $path ($COUNT entries)"
  else
    echo "  MISS $path"
  fi
done

echo
echo "=== E. Hook execution markers (any /var/log/alfred-hook-* files?) ==="
grep -E "alfred-hook|alfred-build-stamp|/etc/alfred" /tmp/sq-listing.txt | head -10

echo
echo "=== F. Plymouth theme = alfred? ==="
grep -E "plymouth/themes/alfred" /tmp/sq-listing.txt | head -5

echo
echo "=== G. Verdict ==="
if c=$(grep -cE "^\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+\S+\s+/?etc/alfred" /tmp/sq-listing.txt 2>/dev/null); then
  ALFRED_OK=$c
else
  ALFRED_OK=0
fi
if (( ALFRED_OK > 0 )); then
  echo "PASS: ISO contains /etc/alfred — hooks ran successfully"
  exit 0
else
  echo "FAIL: /etc/alfred missing — hooks did NOT run on this build"
  exit 5
fi
