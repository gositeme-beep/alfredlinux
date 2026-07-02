#!/usr/bin/env bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# Pack linux-image-7.0.12 / headers / linux-libc-dev .deb files into a portable archive for other
# hosts, thin checkouts, or Forge runners. Output is gitignored — copy onto the build host or set
# ALFRED_KERNEL_DEBS_ARCHIVE when running stage-kernel-debs-for-iso.sh / iso-preflight.
#
# Sources (first with any matching .deb wins): KERNEL_WORK (default ../kernel-7.0.12-work), else
# config/packages.chroot/ if debs already there.
#
# Usage:
#   bash scripts/pack-kernel-debs-archive.sh
#   KERNEL_WORK=/path/to/debs bash scripts/pack-kernel-debs-archive.sh
#   OUT=/tmp/k.tgz bash scripts/pack-kernel-debs-archive.sh   # override output path
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
source "$ROOT/config/kernel.env"
OUT_DIR="$ROOT/build-assets/kernel-${ALFRED_KERNEL_VERSION}-debs"
OUT="${OUT:-$OUT_DIR/linux-${ALFRED_KERNEL_VERSION}-debs-for-iso.tar.gz}"
WORK="${KERNEL_WORK:-$ROOT/../kernel-${ALFRED_KERNEL_VERSION}-work}"
PC="$ROOT/config/packages.chroot"
mkdir -p "$(dirname "$OUT")"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

find_debs() {
  local dir="$1"
  local found=()
  shopt -s nullglob
  for f in "$dir"/linux-image-"${ALFRED_KERNEL_VERSION}"*.deb "$dir"/linux-headers-"${ALFRED_KERNEL_VERSION}"*.deb "$dir"/linux-libc-dev_*.deb; do
    [[ -f "$f" ]] && found+=("$f")
  done
  shopt -u nullglob
  if ((${#found[@]} > 0)); then
    for f in "${found[@]}"; do cp -a "$f" "$TMP/"; done
    return 0
  fi
  return 1
}

if find_debs "$WORK"; then
  echo "[pack-kernel] using debs from $WORK"
elif find_debs "$PC"; then
  echo "[pack-kernel] using debs from $PC"
else
  echo "No linux-image-${ALFRED_KERNEL_VERSION}*.deb (or headers/libc) in $WORK or $PC" >&2
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
echo "OK. On another checkout: place under build-assets/kernel-${ALFRED_KERNEL_VERSION}-debs/ or set ALFRED_KERNEL_DEBS_ARCHIVE, then: bash scripts/iso-preflight.sh"
