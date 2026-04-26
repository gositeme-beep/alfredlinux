#!/usr/bin/env bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# Run before lb build. Exit 1 if ISO hook 0050 would fail (missing linux-image-7.0.1).
set -euo pipefail
ROOT="${ALFRED_SRC:-$(cd "$(dirname "$0")/.." && pwd)}"
PC="${ROOT}/config/packages.chroot"

echo "=== Alfred ISO preflight ==="
echo "Tree: $ROOT"

if ! command -v lb >/dev/null 2>&1; then
  echo "WARN: lb (live-build) not in PATH. On Ubuntu builder: bash scripts/remote-apt-live-build.sh"
else
  echo "OK: lb -> $(command -v lb) ($(lb --version 2>/dev/null | head -1 || echo '?'))"
fi

echo "--- disk (repo parent) ---"
df -h "$(dirname "$ROOT")" | tail -1

echo "--- kernel packages in $PC ---"
shopt -s nullglob
debs=( "$PC"/linux-image-7.0.1*.deb )
if ((${#debs[@]})); then
  for f in "${debs[@]}"; do echo "  OK $f"; done
else
  echo "  FAIL: no linux-image-7.0.1*.deb — hook 0050 will abort lb build."
  echo "  See: $ROOT/config/packages.chroot/README-KERNEL7.txt"
  echo "  Fetch sources: bash scripts/kernel-download-7.0.1.sh"
  exit 1
fi

hdr=( "$PC"/linux-headers-7.0.1*.deb )
if ((${#hdr[@]})); then
  for f in "${hdr[@]}"; do echo "  OK $f"; done
else
  echo "  WARN: no linux-headers-7.0.1*.deb (often required alongside image)."
fi

echo "=== preflight OK ==="
