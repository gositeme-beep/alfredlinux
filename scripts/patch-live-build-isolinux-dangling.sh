#!/bin/bash
# Idempotent: patch live-build's binary_syslinux so stacked `cp` into chroot/root/isolinux
# does not fail on dangling symlinks (Debian trixie isolinux/syslinux-common + GNU cp).
#
# Upstream copies in order:
#   cp -a ${_SOURCE_COMMON}/* chroot/root/${_BOOTLOADER}/
#   cp -af ${_SOURCE}/* chroot/root/${_BOOTLOADER}/
# The first copy can leave dangling symlinks; the second `cp -af` then errors with:
#   cp: not writing through dangling symlink 'chroot/root/isolinux/...'
#
# A `find` *before* the final `Chroot ... cp -aL` runs too late — insert cleanup right after
# the first `cp` line instead.
set -euo pipefail
TARGET="${1:-/usr/lib/live/build/binary_syslinux}"
MARK='ALFRED_ISOLINUX_FIND_AFTER_FIRST_CP'
[[ -f "$TARGET" ]] || { echo "skip patch: $TARGET missing" >&2; exit 0; }
if grep -q "$MARK" "$TARGET"; then
  chmod +x "$TARGET" 2>/dev/null || true
  echo "skip patch: already applied ($TARGET)"
  exit 0
fi
needle=$'\t\tcp -a ${_SOURCE_COMMON}/* chroot/root/${_BOOTLOADER}/'
tmp="$(mktemp)"
found=
while IFS= read -r line || [[ -n "$line" ]]; do
  if [[ -z "$found" && "$line" == "$needle" ]]; then
    printf '%s\n' "$line" >>"$tmp"
    printf '%s\n' $'\t\t# '"$MARK"' (Debian trixie isolinux; GNU cp vs dangling symlinks)' >>"$tmp"
    printf '%s\n' $'\t\tfind "chroot/root/${_BOOTLOADER}" -mindepth 1 -maxdepth 1 -xtype l -delete 2>/dev/null || true' >>"$tmp"
    found=1
    continue
  fi
  printf '%s\n' "$line" >>"$tmp"
done <"$TARGET"
if [[ -z "$found" ]]; then
  echo "WARN: patch-live-build-isolinux-dangling: needle not found in $TARGET; live-build layout may have changed" >&2
  rm -f "$tmp"
  exit 0
fi
mv "$tmp" "$TARGET"
chmod +x "$TARGET"
echo "patched $TARGET ($MARK)"
