#!/bin/bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# Patch live-build chroot teardown: use lazy umount for /sys, /proc, /dev/pts.
# Prevents lb abort during chroot_sysfs remove when something still holds
# chroot/sys (Docker + hooks + udev): "umount: .../chroot/sys: target is busy".
#
# Idempotent: skips files already containing "umount -l chroot/sys" (etc.).
set -euo pipefail
TARGET_DIR="${1:-/usr/lib/live/build}"

patch_sysfs() {
  local f
  for f in "$TARGET_DIR"/lb_chroot_sysfs "$TARGET_DIR"/chroot_sysfs; do
    [[ -f "$f" ]] || continue
    if grep -Fq 'umount -l chroot/sys' "$f" 2>/dev/null; then
      echo "skip patch (already applied): $f"
      continue
    fi
    if ! grep -Fq 'umount chroot/sys' "$f" 2>/dev/null; then
      echo "skip patch (pattern missing — different live-build?): $f" >&2
      continue
    fi
    sed -i 's|${LB_ROOT_COMMAND} umount chroot/sys|${LB_ROOT_COMMAND} umount -l chroot/sys|g' "$f"
    chmod +x "$f" 2>/dev/null || true
    echo "patched $f (sysfs lazy umount)"
  done
}

patch_proc() {
  local f
  for f in "$TARGET_DIR"/lb_chroot_proc "$TARGET_DIR"/chroot_proc; do
    [[ -f "$f" ]] || continue
    if grep -Fq 'umount -l chroot/proc' "$f" 2>/dev/null; then
      echo "skip patch (already applied): $f"
      continue
    fi
    if grep -Fq 'umount chroot/proc/sys/fs/binfmt_misc' "$f" 2>/dev/null; then
      sed -i 's|${LB_ROOT_COMMAND} umount chroot/proc/sys/fs/binfmt_misc|${LB_ROOT_COMMAND} umount -l chroot/proc/sys/fs/binfmt_misc|g' "$f"
    fi
    if grep -Fq 'umount chroot/proc' "$f" 2>/dev/null; then
      sed -i 's|${LB_ROOT_COMMAND} umount chroot/proc|${LB_ROOT_COMMAND} umount -l chroot/proc|g' "$f"
    fi
    chmod +x "$f" 2>/dev/null || true
    echo "patched $f (proc lazy umount)"
  done
}

patch_devpts() {
  local f
  for f in "$TARGET_DIR"/lb_chroot_devpts "$TARGET_DIR"/chroot_devpts; do
    [[ -f "$f" ]] || continue
    if grep -Fq 'umount -l chroot/dev/pts' "$f" 2>/dev/null; then
      echo "skip patch (already applied): $f"
      continue
    fi
    if ! grep -Fq 'umount chroot/dev/pts' "$f" 2>/dev/null; then
      echo "skip patch (pattern missing): $f" >&2
      continue
    fi
    # Only the non -f branch (line that does plain umount)
    sed -i 's|${LB_ROOT_COMMAND} umount chroot/dev/pts|${LB_ROOT_COMMAND} umount -l chroot/dev/pts|g' "$f"
    chmod +x "$f" 2>/dev/null || true
    echo "patched $f (devpts lazy umount)"
  done
}

patch_sysfs
patch_proc
patch_devpts
echo "patch-live-build-lazy-umount-chroot-fs: done ($TARGET_DIR)"
