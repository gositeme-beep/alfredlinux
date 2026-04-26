#!/usr/bin/env bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# Copy linux-image-7.0.1*.deb (+ headers, libc-dev) from KERNEL_WORK into repo config/packages.chroot/
set -euo pipefail
REPO="$(cd "$(dirname "$0")/.." && pwd)"
WORK="${KERNEL_WORK:-$REPO/../kernel-7.0.1-work}"
DST="$REPO/config/packages.chroot"
mkdir -p "$DST"
shopt -s nullglob
found=0
for f in "$WORK"/linux-image-7.0.1*.deb "$WORK"/linux-headers-7.0.1*.deb "$WORK"/linux-libc-dev_*.deb; do
  [[ -f "$f" ]] || continue
  echo "cp $f -> $DST/"
  cp -a "$f" "$DST/"
  found=1
done
shopt -u nullglob
if [[ "$found" -eq 0 ]]; then
  echo "No matching .deb in $WORK — wait for Docker build to finish." >&2
  exit 1
fi
echo "OK. Run: bash scripts/iso-preflight.sh"
