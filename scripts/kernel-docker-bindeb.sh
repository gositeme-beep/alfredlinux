#!/usr/bin/env bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# Same as /home/gositeme/law/alfred-kernel-docker-bindeb.sh — Docker Trixie, no host sudo.
# KERNEL_WORK default: <repo>/../kernel-7.0.12-work
set -euo pipefail
REPO="$(cd "$(dirname "$0")/.." && pwd)"
source "$REPO/config/kernel.env"
WORK="${KERNEL_WORK:-$REPO/../kernel-${ALFRED_KERNEL_VERSION}-work}"
IMAGE="${DOCKER_KERNEL_IMAGE:-debian:trixie}"
NAME="${ALFRED_KERNEL_DOCKER_NAME:-alfred-kernel-bindeb-$(date +%s)}"
INNER_SRC="$REPO/scripts/kernel-docker-inner-bindeb.sh"
INNER_DST="$WORK/docker-inner-bindeb.sh"

mkdir -p "$WORK"
cp -a "$INNER_SRC" "$INNER_DST"
chmod 750 "$INNER_DST"

[[ -f "$WORK/linux-${ALFRED_KERNEL_VERSION}.tar.xz" ]] || { echo "Run first: bash scripts/kernel-download.sh" >&2; exit 1; }
[[ -f "$WORK/linux-${ALFRED_KERNEL_VERSION}/Makefile" ]] || tar xf "$WORK/linux-${ALFRED_KERNEL_VERSION}.tar.xz" -C "$WORK"

docker pull "$IMAGE" >/dev/null

if [[ -n "${ALFRED_KERNEL_CONFIG:-}" && -f "${ALFRED_KERNEL_CONFIG}" ]]; then
  cp -a "${ALFRED_KERNEL_CONFIG}" "$WORK/kernel-hardened.config"
fi

common_env=( -e DEBIAN_FRONTEND=noninteractive
  -e "BUILD_UID=$(id -u)" -e "BUILD_GID=$(id -g)" -e "NJOBS=${NJOBS:-$(nproc)}"
  -e "ALFRED_KERNEL_CONFIG=/work/kernel-hardened.config"
  -v "$WORK:/work" "$IMAGE" bash /work/docker-inner-bindeb.sh )

if [[ "${1:-}" == "detach" ]]; then
  docker run -d --rm --name "$NAME" "${common_env[@]}"
  echo "$NAME" >"${WORK}/docker-bindeb.containername"
  echo "Started: $NAME"
  echo "Logs: docker logs -f $NAME"
  exit 0
fi

docker run --rm --name alfred-kernel-bindeb "${common_env[@]}"

echo "=== .deb under $WORK ==="
ls -lh "$WORK"/linux-image-${ALFRED_KERNEL_VERSION}*.deb 2>/dev/null || ls -lh "$WORK"/*.deb 2>/dev/null | head -30
