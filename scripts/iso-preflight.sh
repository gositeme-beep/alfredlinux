#!/usr/bin/env bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# Run before lb build. Exit 1 if staged kernel debs are missing (linux-image-7.0.3*).
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

if command -v docker >/dev/null 2>&1; then
  echo "--- docker: alfred-lb-build-* (host) ---"
  mapfile -t _lb < <(docker ps --filter 'name=alfred-lb-build' --format '{{.Names}}' 2>/dev/null || true)
  if ((${#_lb[@]} > 1)); then
    echo "  WARN: ${#_lb[@]} running containers match alfred-lb-build — same repo bind mount can corrupt chroot mid-apt."
    printf '    %s\n' "${_lb[@]}"
    echo "  Prefer one detach at a time; lb-docker-build defaults ALFRED_LB_DOCKER_FLOCK_BLOCKING=1 (see scripts/README.txt)."
  elif ((${#_lb[@]} == 1)); then
    echo "  OK: one build container: ${_lb[0]}"
  else
    echo "  (none running — normal before \`lb-docker-build.sh detach\`)"
  fi
else
  echo "--- docker: (not in PATH; skipped overlap check) ---"
fi

_lock="${ROOT}/build/.alfred-lb-docker-build.lock"
if [[ -f "$_lock" ]]; then
  echo "--- build flock: ${_lock} ---"
  if command -v fuser >/dev/null 2>&1; then
    fuser "$_lock" 2>/dev/null || echo "  (fuser reported nothing — container may hold lock from inside mount ns)"
  else
    echo "  (install psmisc for fuser — optional)"
  fi
fi

echo "--- staging kernel debs (archive or KERNEL_WORK) ---"
bash "$ROOT/scripts/stage-kernel-debs-for-iso.sh"

echo "--- kernel packages in $PC ---"
shopt -s nullglob
debs=( "$PC"/linux-image-7.0.3*.deb )
if ((${#debs[@]})); then
  for f in "${debs[@]}"; do echo "  OK $f"; done
else
  echo "  FAIL: no linux-image-7.0.3*.deb — lb/apt will fail without the Alfred kernel debs."
  echo "  See: $ROOT/config/packages.chroot/README-KERNEL7.txt"
  echo "  Fetch sources: bash scripts/kernel-download-7.0.3.sh"
  echo "  Pack debs from a builder: bash scripts/pack-kernel-debs-archive.sh"
  echo "  Then place linux-7.0.3-debs-for-iso.tar.gz under build-assets/kernel-7.0.3-debs/ or set ALFRED_KERNEL_DEBS_ARCHIVE"
  exit 1
fi

hdr=( "$PC"/linux-headers-7.0.3*.deb )
if ((${#hdr[@]})); then
  for f in "${hdr[@]}"; do echo "  OK $f"; done
else
  echo "  WARN: no linux-headers-7.0.3*.deb (often required alongside image)."
fi

echo "=== preflight OK ==="
