#!/usr/bin/env bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# Pack linux-image-7.0.3 / headers / linux-libc-dev .deb files into a portable archive for other
# hosts, thin checkouts, or Forge runners. Output is gitignored — copy onto the build host or set
# ALFRED_KERNEL_DEBS_ARCHIVE when running stage-kernel-debs-for-iso.sh / iso-preflight.
#
# Sources (first with any matching .deb wins): KERNEL_WORK (default ../kernel-7.0.3-work), else
# config/packages.chroot/ if debs already there.
#
# Usage:
#   bash scripts/pack-kernel-debs-archive.sh
#   KERNEL_WORK=/path/to/debs bash scripts/pack-kernel-debs-archive.sh
#   OUT=/tmp/k.tgz bash scripts/pack-kernel-debs-archive.sh   # override output path
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT_DIR="$ROOT/build-assets/kernel-7.0.3-debs"
OUT="${OUT:-$OUT_DIR/linux-7.0.3-debs-for-iso.tar.gz}"
WORK="${KERNEL_WORK:-$ROOT/../kernel-7.0.3-work}"
PC="$ROOT/config/packages.chroot"
mkdir -p "$(dirname "$OUT")"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

collect() {
  local dir="$1"
  local f found=0
  shopt -s nullglob
  for f in "$dir"/linux-image-7.0.3*.deb "$dir"/linux-headers-7.0.3*.deb "$dir"/linux-libc-dev_*.deb; do
    [[ -f "$f" ]] || continue
    cp -a "$f" "$TMP/"
    found=1
  done
  shopt -u nullglob
  (( found )) && return 0
  return 1
}

if collect "$WORK"; then
  echo "[pack-kernel] using debs from $WORK"
elif collect "$PC"; then
  echo "[pack-kernel] using debs from $PC"
else
  echo "No linux-image-7.0.3*.deb (or headers/libc) in $WORK or $PC" >&2
  echo "Build with kernel-docker-bindeb / bindeb-pkg, then bash scripts/copy-kernel-debs-to-chroot.sh" >&2
  exit 1
fi

(
  cd "$TMP"
  shopt -s nullglob
  n=( *.deb )
  shopt -u nullglob
  if ((!${#n[@]})); then
    echo "[pack-kernel] internal error: empty temp dir" >&2
    exit 1
  fi
  echo "[pack-kernel] packing ${#n[@]} file(s) -> $OUT"
  tar -czf "$OUT" "${n[@]}"
)
ls -lh "$OUT"
echo "OK. On another checkout: place under build-assets/kernel-7.0.3-debs/ or set ALFRED_KERNEL_DEBS_ARCHIVE, then: bash scripts/iso-preflight.sh"
