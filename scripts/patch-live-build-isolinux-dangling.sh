#!/bin/bash
# Idempotent: patch live-build's binary_syslinux so stacked `cp` into chroot/root/isolinux
# does not fail on dangling symlinks (Debian trixie isolinux/syslinux-common + GNU cp).
#
# Upstream order: Install_packages, then rm/mkdir chroot/root/${_BOOTLOADER}, then several
# `cp` lines. The first copies can leave dangling symlinks; later `cp -af` errors with:
#   cp: not writing through dangling symlink 'chroot/root/isolinux/...'
#
# We insert a `find -xtype l -delete` immediately before the Chroot line that copies the
# tree out of the chroot staging dir (after all those cp lines).
set -euo pipefail
TARGET="${1:-/usr/lib/live/build/binary_syslinux}"
MARK='ALFRED_DANGLING_SYMLINK_PRE_CP'
[[ -f "$TARGET" ]] || { echo "skip patch: $TARGET missing" >&2; exit 0; }
if grep -q "$MARK" "$TARGET"; then
  echo "skip patch: already applied ($TARGET)"
  exit 0
fi
needle=$'\t\tChroot chroot cp -aL /root/${_BOOTLOADER} /root/${_BOOTLOADER}.tmp > /dev/null 2>&1 || true'
tmp="$(mktemp)"
found=
while IFS= read -r line || [[ -n "$line" ]]; do
  if [[ -z "$found" && "$line" == "$needle" ]]; then
    printf '%s\n' $'\t\t# '"$MARK"' (Debian trixie isolinux; GNU cp vs dangling symlinks)' >>"$tmp"
    printf '%s\n' $'\t\tfind "chroot/root/${_BOOTLOADER}" -mindepth 1 -maxdepth 1 -xtype l -delete 2>/dev/null || true' >>"$tmp"
    found=1
  fi
  printf '%s\n' "$line" >>"$tmp"
done <"$TARGET"
if [[ -z "$found" ]]; then
  echo "WARN: patch-live-build-isolinux-dangling: needle not found in $TARGET; live-build layout may have changed" >&2
  rm -f "$tmp"
  exit 0
fi
mv "$tmp" "$TARGET"
echo "patched $TARGET ($MARK)"
