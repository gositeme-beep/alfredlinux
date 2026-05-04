#!/usr/bin/env bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# Same as /home/gositeme/law/alfred-kernel-docker-bindeb.sh — Docker Bookworm, no host sudo.
# KERNEL_WORK default: <repo>/../kernel-7.0.3-work
set -euo pipefail
REPO="$(cd "$(dirname "$0")/.." && pwd)"
WORK="${KERNEL_WORK:-$REPO/../kernel-7.0.3-work}"
IMAGE="${DOCKER_KERNEL_IMAGE:-debian:bookworm}"
NAME="${ALFRED_KERNEL_DOCKER_NAME:-alfred-kernel-bindeb-$(date +%s)}"
INNER_SRC="$REPO/scripts/kernel-docker-inner-bindeb.sh"
INNER_DST="$WORK/docker-inner-bindeb.sh"

mkdir -p "$WORK"
cp -a "$INNER_SRC" "$INNER_DST"
chmod 750 "$INNER_DST"

[[ -f "$WORK/linux-7.0.3.tar.xz" ]] || { echo "Run first: bash scripts/kernel-download-7.0.1.sh" >&2; exit 1; }
[[ -f "$WORK/linux-7.0.3/Makefile" ]] || tar xf "$WORK/linux-7.0.3.tar.xz" -C "$WORK"

docker pull "$IMAGE" >/dev/null

common_env=( -e DEBIAN_FRONTEND=noninteractive
  -e "BUILD_UID=$(id -u)" -e "BUILD_GID=$(id -g)" -e "NJOBS=${NJOBS:-$(nproc)}"
  -v "$WORK:/work" "$IMAGE" bash /work/docker-inner-bindeb.sh )

if [[ "${1:-}" == "detach" ]]; then
  docker run -d --rm --name "$NAME" "${common_env[@]}"
  echo "$NAME" >"${WORK}/docker-bindeb.containername"
  echo "Started: $NAME"
  echo "Logs: docker logs -f $NAME"
  exit 0
fi

docker run --rm "${common_env[@]}"

echo "=== .deb under $WORK ==="
ls -lh "$WORK"/linux-image-7.0.3*.deb 2>/dev/null || ls -lh "$WORK"/*.deb 2>/dev/null | head -30
