#!/usr/bin/env bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# fakeroot make bindeb-pkg in background for linux-7.0.1.
# KERNEL_WORK default: <repo>/../kernel-7.0.3-work  (put linux-7.0.3.tar.xz there; use kernel-download-7.0.1.sh)
# Log: $KERNEL_WORK/bindeb-pkg.log
# Optional: ALFRED_KERNEL_CONFIG=/path/.config
set -euo pipefail
REPO="$(cd "$(dirname "$0")/.." && pwd)"
WORK="${KERNEL_WORK:-$REPO/../kernel-7.0.3-work}"
TAR="${WORK}/linux-7.0.3.tar.xz"
LOG="${WORK}/bindeb-pkg.log"
NJOBS="${NJOBS:-$(nproc)}"

[[ -f "$TAR" ]] || { echo "Missing $TAR — run: bash scripts/kernel-download-7.0.1.sh" >&2; exit 1; }

for p in debhelper libdw-dev; do
  dpkg -s "$p" &>/dev/null || {
    echo "Missing: $p — run: sudo bash $REPO/scripts/kernel-install-build-deps.sh" >&2
    exit 1
  }
done

cd "$WORK"
if [[ ! -d linux-7.0.1 ]]; then
  echo "Extracting $TAR ..."
  tar xf "$TAR"
fi

cd linux-7.0.1

if [[ -n "${ALFRED_KERNEL_CONFIG:-}" && -f "${ALFRED_KERNEL_CONFIG}" ]]; then
  cp -a "${ALFRED_KERNEL_CONFIG}" .config
else
  make ARCH=x86_64 x86_64_defconfig
fi

if [[ -x ./scripts/config ]]; then
  # Safe-mode boot requires /dev/fuse support for ntfs-3g paths.
  ./scripts/config --enable FUSE_FS || true
  # Keep modern in-kernel NTFS driver available as fallback.
  ./scripts/config --module NTFS3_FS || true
fi
make ARCH=x86_64 olddefconfig

: >"$LOG"
echo "Starting fakeroot make bindeb-pkg (jobs=$NJOBS). Log: $LOG"
nohup fakeroot make -j"$NJOBS" ARCH=x86_64 bindeb-pkg LOCALVERSION= KDEB_PKGVERSION=7.0.3-1alfred >>"$LOG" 2>&1 &
echo $! >"${WORK}/bindeb-pkg.pid"
echo "PID $(cat "${WORK}/bindeb-pkg.pid") — tail -f $LOG"
