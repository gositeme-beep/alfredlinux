#!/bin/bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# Patch live-build chroot teardown: use lazy umount for virtual filesystems.
# Prevents lb abort: "umount: .../chroot/sys: target is busy"
#
# Debian trixie/bookworm live-build uses plain "umount chroot/..." in
# /usr/lib/live/build/chroot_{sysfs,proc,devpts,selinuxfs} (no LB_ROOT_COMMAND).
# Ubuntu-derived live-build uses lb_chroot_* with ${LB_ROOT_COMMAND} umount ...
#
# Idempotent: safe to run twice.
set -euo pipefail
TARGET_DIR="${1:-/usr/lib/live/build}"

# $1=file $2=description
patch_file() {
  local f="$1" desc="$2"
  [[ -f "$f" ]] || return 0
  local tmp
  tmp=$(mktemp)
  cp -a "$f" "$tmp"
  # Nested paths first (avoid partial replacements).
  sed -i \
    -e 's|${LB_ROOT_COMMAND} umount chroot/sys/firmware/efi/efivars|${LB_ROOT_COMMAND} umount -l chroot/sys/firmware/efi/efivars|g' \
    -e 's|umount chroot/sys/firmware/efi/efivars|umount -l chroot/sys/firmware/efi/efivars|g' \
    -e 's|${LB_ROOT_COMMAND} umount chroot/sys/fs/selinux|${LB_ROOT_COMMAND} umount -l chroot/sys/fs/selinux|g' \
    -e 's|umount chroot/sys/fs/selinux|umount -l chroot/sys/fs/selinux|g' \
    -e 's|${LB_ROOT_COMMAND} umount chroot/sys|${LB_ROOT_COMMAND} umount -l chroot/sys|g' \
    -e 's|umount chroot/sys|umount -l chroot/sys|g' \
    -e 's|${LB_ROOT_COMMAND} umount chroot/proc/sys/fs/binfmt_misc|${LB_ROOT_COMMAND} umount -l chroot/proc/sys/fs/binfmt_misc|g' \
    -e 's|umount chroot/proc/sys/fs/binfmt_misc|umount -l chroot/proc/sys/fs/binfmt_misc|g' \
    -e 's|${LB_ROOT_COMMAND} umount chroot/proc|${LB_ROOT_COMMAND} umount -l chroot/proc|g' \
    -e 's|umount chroot/proc|umount -l chroot/proc|g' \
    -e 's|${LB_ROOT_COMMAND} umount chroot/dev/pts|${LB_ROOT_COMMAND} umount -l chroot/dev/pts|g' \
    -e 's|\bumount chroot/dev/pts\b|umount -l chroot/dev/pts|g' \
    "$tmp"
  if cmp -s "$f" "$tmp"; then
# DISABLED-BY-COMMANDER: rm -f "$tmp"
    echo "skip patch (no changes): $f"
    return 0
  fi
  mv "$tmp" "$f"
  chmod +x "$f" 2>/dev/null || true
  echo "patched $f ($desc)"
}

# chroot_* (Debian) and lb_chroot_* (Ubuntu-style)
for base in chroot_sysfs lb_chroot_sysfs; do
  patch_file "$TARGET_DIR/$base" "sysfs lazy umount"
done
for base in chroot_proc lb_chroot_proc; do
  patch_file "$TARGET_DIR/$base" "proc lazy umount"
done
for base in chroot_devpts lb_chroot_devpts; do
  patch_file "$TARGET_DIR/$base" "devpts lazy umount"
done
for base in chroot_selinuxfs lb_chroot_selinuxfs; do
  patch_file "$TARGET_DIR/$base" "selinuxfs lazy umount"
done

# chroot_hooks can umount chroot/proc during hook teardown (same busy class).
for base in chroot_hooks lb_chroot_hooks; do
  [[ -f "$TARGET_DIR/$base" ]] || continue
  if grep -Fq 'umount chroot/proc' "$TARGET_DIR/$base" 2>/dev/null \
    && ! grep -Fq 'umount -l chroot/proc' "$TARGET_DIR/$base" 2>/dev/null; then
    patch_file "$TARGET_DIR/$base" "hooks proc lazy umount"
  fi
done

echo "patch-live-build-lazy-umount-chroot-fs: done ($TARGET_DIR)"
