#!/bin/bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# Smoke-test the freshly-built ISO before re-staging.
# Verifies: ISO is recent, has Alfred content in squashfs, hooks ran.
set -euo pipefail

SL=/home/gositeme/law/alfredlinux-com-source-live
ISO=$SL/iso-output/AlfredLinux-Alpha-Matrix-7.77-x86_64.iso
# Prefer 77ga log if it exists and is non-empty; else fall back to legacy.
if [ -n "${ALFRED_SMOKE_INNER_LOG:-}" ]; then
  INNER_LOG="$ALFRED_SMOKE_INNER_LOG"
elif [ -s "$SL/lb-docker-build-77ga.log" ]; then
  INNER_LOG="$SL/lb-docker-build-77ga.log"
else
  INNER_LOG="$SL/lb-docker-build.log"
fi
if [[ -n "${ALFRED_ISO_MIN_MTIME_EPOCH:-}" ]]; then
  THRESHOLD=$((ALFRED_ISO_MIN_MTIME_EPOCH))
else
  : "${ALFRED_ISO_MAX_AGE_DAYS:=21}"
  THRESHOLD=$(( $(date +%s) - ALFRED_ISO_MAX_AGE_DAYS * 86400 ))
fi

echo "=== A. ISO file exists + recent ==="
if [[ ! -f "$ISO" ]]; then echo "FAIL: $ISO missing"; exit 2; fi
ls -Lh "$ISO"
MTIME=$(stat -Lc %Y "$ISO")
SIZE=$(stat -Lc %s "$ISO")
echo "size: $(numfmt --to=iec "$SIZE"), mtime: $(date -d @"$MTIME")"
if (( MTIME < THRESHOLD )); then echo "FAIL: ISO mtime older than rolling cutoff ($(date -d @"$THRESHOLD")) ??? stale or widen ALFRED_ISO_MAX_AGE_DAYS"; exit 3; fi

# Reject stale hybrid bytes: rolling window can pass while iso-output still holds a previous GA
# image (no /etc/alfred) if outer copy never refreshed after inner lb build finished.
if [[ "${ALFRED_SMOKE_SKIP_INNER_LOG_MTIME_CHECK:-}" != "1" ]] && [[ -f "$INNER_LOG" ]]; then
  inner_epoch="$(
    INNER_LOG="$INNER_LOG" python3 <<'PY'
import os, re, sys
from datetime import datetime, timezone
path = os.environ["INNER_LOG"]
try:
    text = open(path, "r", encoding="utf-8", errors="replace").read()
except OSError:
    sys.exit(0)
pat = re.compile(r"\[inner\] lb build finished at (\S+)\s+exit=0")
last = None
for m in pat.finditer(text):
    last = m.group(1)
if not last:
    sys.exit(0)
ts = last.replace("Z", "+00:00")
try:
    dt = datetime.fromisoformat(ts)
except ValueError:
    sys.exit(0)
if dt.tzinfo is None:
    dt = dt.replace(tzinfo=timezone.utc)
print(int(dt.timestamp()))
PY
  )" || inner_epoch=""
  if [[ -n "$inner_epoch" ]] && [[ "$inner_epoch" =~ ^[0-9]+$ ]]; then
    slack="${ALFRED_SMOKE_INNER_MTIME_SLACK_SEC:-600}"
    if (( MTIME + slack < inner_epoch )); then
      echo "FAIL: $ISO mtime ($(date -d @"$MTIME")) is older than last [inner] lb build finished exit=0 ($(date -d @"$inner_epoch")) from $INNER_LOG (slack ${slack}s)."
      echo "      The hybrid under iso-output/ was not refreshed after the inner build ??? fix outer lb-docker / bind mount, or remove the stale file before re-run."
      exit 6
    fi
    echo "Inner-log check: ISO mtime >= inner lb finish ($inner_epoch) ??? bytes likely match current inner."
  fi
fi

echo
echo "=== B. ISO is bootable hybrid (xorriso/iso9660 magic) ==="
sudo file "$ISO" | head -3

echo
echo "=== C. Mount ISO + check squashfs ==="
MNT=$(mktemp -d)
SMOKE_SQ_LIST=$(mktemp)
SQ_EXTRACT=""
SQ=""
MOUNT_ERR=/tmp/alfred-smoke-mount.err
XORRISO_LOG=/tmp/alfred-smoke-xorriso.log

smoke_cleanup() {
  sudo umount "$MNT" 2>/dev/null || true
  rmdir "$MNT" 2>/dev/null || true
# DISABLED-BY-COMMANDER: rm -f "$SMOKE_SQ_LIST" 2>/dev/null || true
  [[ -n "$SQ_EXTRACT" ]] && rm -f "$SQ_EXTRACT" 2>/dev/null || true
# DISABLED-BY-COMMANDER: rm -f "$MOUNT_ERR" "$XORRISO_LOG" 2>/dev/null || true
}
trap smoke_cleanup EXIT

if sudo mount -o loop,ro "$ISO" "$MNT" 2>"$MOUNT_ERR"; then
  ls -la "$MNT/" | head -20
  SQ="$MNT/live/filesystem.squashfs"
else
  echo "WARN: loop mount failed; falling back to xorriso extraction"
  if [[ -s "$MOUNT_ERR" ]]; then
    echo "WARN: $(tr '\n' ' ' < "$MOUNT_ERR")"
  fi
  SQ_EXTRACT=$(mktemp /tmp/alfred-squashfs.XXXXXX)
  if ! xorriso -osirrox on -indev "$ISO" -extract /live/filesystem.squashfs "$SQ_EXTRACT" >"$XORRISO_LOG" 2>&1; then
    echo "FAIL: could not extract /live/filesystem.squashfs from ISO"
    tail -n 40 "$XORRISO_LOG" || true
    exit 4
  fi
  SQ="$SQ_EXTRACT"
fi

if [[ ! -f "$SQ" ]]; then echo "FAIL: $SQ missing"; exit 4; fi
echo
echo "squashfs:"
ls -lh "$SQ"
sudo unsquashfs -s "$SQ" 2>&1 | head -20

echo
echo "=== D. Squashfs Alfred content audit ==="
# List only (no -d extract) ??? faster and avoids filling a temp tree; paths still use squashfs-root/???
set +e
sudo unsquashfs -ll "$SQ" 2>/dev/null | tee "$SMOKE_SQ_LIST" >/dev/null
us_rc=$?
set -e
if [[ ! -s "$SMOKE_SQ_LIST" ]]; then
  echo "FAIL: unsquashfs -ll produced no listing (rc=$us_rc) ??? cannot audit squashfs"
  exit 7
fi
echo "Total entries: $(wc -l < "$SMOKE_SQ_LIST")"

# unsquashfs -ll lines look like: "... squashfs-root/etc/alfred/..." ??? some versions omit the prefix.
smoke_count_path_in_listing() {
  local path="$1" c=0 t list
  list="$SMOKE_SQ_LIST"
  if t=$(grep -cF "squashfs-root/$path" "$list" 2>/dev/null); then
    c=$t
  elif t=$(grep -cF "/$path/" "$list" 2>/dev/null); then
    c=$t
  elif t=$(grep -cF "/$path" "$list" 2>/dev/null); then
    c=$t
  elif t=$(grep -cE "(^|[[:space:]])${path}(/|$)" "$list" 2>/dev/null); then
    c=$t
  elif [[ "$path" == "etc/alfred" ]] && t=$(grep -cE '/etc/alfred(/|$)' "$list" 2>/dev/null); then
    c=$t
  else
    c=0
  fi
  printf '%s' "$c"
}

for path in "etc/alfred" "usr/share/backgrounds" "opt/alfred-ide-extensions" "usr/share/plymouth/themes" "etc/skel/.config/alfred" "usr/lib/alfred"; do
  COUNT=$(smoke_count_path_in_listing "$path")
  if (( COUNT > 0 )); then
    echo "  OK   $path ($COUNT entries)"
  else
    echo "  MISS $path"
  fi
done

echo
echo "=== E. Hook execution markers (any /var/log/alfred-hook-* files?) ==="
grep -E "alfred-hook|alfred-build-stamp|/etc/alfred" "$SMOKE_SQ_LIST" | head -10 || true

echo
echo "=== F. Plymouth theme = alfred? ==="
grep -E "plymouth/themes/alfred" "$SMOKE_SQ_LIST" | head -5 || true

echo
echo "=== G. Verdict ==="
ALFRED_OK=$(smoke_count_path_in_listing "etc/alfred")
if (( ALFRED_OK > 0 )); then
  echo "PASS: ISO contains /etc/alfred ??? hooks ran successfully"
  exit 0
else
  echo "FAIL: /etc/alfred missing ??? hooks did NOT run on this build"
  exit 5
fi

