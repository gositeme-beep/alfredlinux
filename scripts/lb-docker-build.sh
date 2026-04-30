#!/usr/bin/env bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# Run full `lb build` as root inside Docker (no host sudo). Requires docker + --privileged.
#
#   bash scripts/lb-docker-build.sh           # foreground (hours)
#   bash scripts/lb-docker-build.sh detach   # background; logs: docker logs -f NAME
#
# Log on host bind mount: lb-docker-build.log (repo root).
# Do not start a second container on the same repo until the first exits: inner script
# uses flock on build/.alfred-lb-docker-build.lock; concurrent runs used to corrupt chroot mid-apt.
# ALFRED_LB_DOCKER_FLOCK_BLOCKING: this script defaults to 1 (queue on lock — ABCP Reseal / retries line up).
# Use ALFRED_LB_DOCKER_FLOCK_BLOCKING=0 for fail-fast if another build still holds the lock.
set -euo pipefail
REPO="$(cd "$(dirname "$0")/.." && pwd)"
export ALFRED_LB_DOCKER_FLOCK_BLOCKING="${ALFRED_LB_DOCKER_FLOCK_BLOCKING:-1}"
IMAGE="${DOCKER_LB_IMAGE:-debian:bookworm}"
INNER="$REPO/scripts/lb-docker-inner-build.sh"
NAME="${ALFRED_LB_DOCKER_NAME:-alfred-lb-build-$(date +%s)}"

[[ -f "$INNER" ]] || { echo "Missing $INNER" >&2; exit 1; }
chmod 750 "$INNER"

docker pull "$IMAGE" >/dev/null

# Match host uid so new files on bind mount are not all root:root (optional cleanup).
run=( docker run --init --rm --privileged --network=host
  -e "DEBIAN_FRONTEND=noninteractive"
  -e "BUILD_UID=$(id -u)" -e "BUILD_GID=$(id -g)"
  -e "ALFRED_LB_DOCKER_FLOCK_BLOCKING=${ALFRED_LB_DOCKER_FLOCK_BLOCKING}"
  -v "$REPO:/work"
  -w /work
  "$IMAGE" bash /work/scripts/lb-docker-inner-build.sh )

if [[ "${1:-}" == "detach" ]]; then
  docker run -d --init --rm --privileged --network=host --name "$NAME" \
    -e "DEBIAN_FRONTEND=noninteractive" \
    -e "BUILD_UID=$(id -u)" -e "BUILD_GID=$(id -g)" \
    -e "ALFRED_LB_DOCKER_FLOCK_BLOCKING=${ALFRED_LB_DOCKER_FLOCK_BLOCKING}" \
    -v "$REPO:/work" \
    -w /work \
    "$IMAGE" bash /work/scripts/lb-docker-inner-build.sh
  echo "$NAME" >"$REPO/lb-docker.containername"
  echo "Started: $NAME"
  echo "Log:     $REPO/lb-docker-build.log"
  echo "Follow:  docker logs -f $NAME"
  exit 0
fi

"${run[@]}"
