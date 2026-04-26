#!/bin/bash
# Patch live-build's binary_syslinux: add GNU cp --remove-destination for copies into
# chroot/root/${_BOOTLOADER}/ (isolinux path). Prevents:
#   cp: not writing through dangling symlink 'chroot/root/isolinux/…'
# when stacked cp runs (Debian trixie isolinux/syslinux-common + theme files).
#
# Idempotent: safe to run twice (negative lookahead avoids double flags).
set -euo pipefail
TARGET="${1:-/usr/lib/live/build/binary_syslinux}"
[[ -f "$TARGET" ]] || { echo "skip patch: $TARGET missing" >&2; exit 0; }

if grep -Fq 'cp -a --remove-destination ${_SOURCE_COMMON}/* chroot/root/${_BOOTLOADER}/' "$TARGET"; then
  chmod +x "$TARGET" 2>/dev/null || true
  echo "skip patch: already applied ($TARGET)"
  exit 0
fi

perl -i -0pe '
  s/^(\t\tcp -a)(?!\s+--remove-destination)(\s+\$\{_SOURCE_COMMON\}\/\* chroot\/root\/\$\{_BOOTLOADER\}\/)$/${1} --remove-destination${2}/mg;
  s/^(\t\tcp -af)(?!\s+--remove-destination)(\s+\$\{_SOURCE\}\/\* chroot\/root\/\$\{_BOOTLOADER\}\/)$/${1} --remove-destination${2}/mg;
  s/^(\t\t\tcp -af)(?!\s+--remove-destination)(\s+\$\{_SOURCE_USER_COMMON\}\/\* chroot\/root\/\$\{_BOOTLOADER\}\/)$/${1} --remove-destination${2}/mg;
  s/^(\t\t\tcp -af)(?!\s+--remove-destination)(\s+\$\{_SOURCE_USER\}\/\* chroot\/root\/\$\{_BOOTLOADER\}\/)$/${1} --remove-destination${2}/mg;
' "$TARGET"

chmod +x "$TARGET"
echo "patched $TARGET (cp --remove-destination for chroot isolinux/syslinux copies)"
