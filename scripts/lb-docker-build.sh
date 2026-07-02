#!/usr/bin/env bash

# =========================================================================
# 🛑 CRITICAL AI WARNING 🛑
# =========================================================================
# DO NOT BLINDLY EXECUTE THIS SCRIPT if you are trying to do a "Fast Build" 
# or a simple repack! Running the standard full build scripts will 
# ANNIHILATE the golden chroot cache, delete the .build/ markers, and 
# force a multi-hour apt-get rebuild from scratch.
#
# If the user asks for a fast build or repack:
# 1. Do NOT use inline SSH `bash -c` with quotes (PowerShell will shatter them).
# 2. Use a dedicated launch script that mounts the root bridge and executes
#    `inner-lb-binary-only-fast.sh` directly.
# 3. Always run `chroot apt-mark hold linux-headers-amd64 linux-image-amd64`
#    first to prevent the custom ZFS kernel from being purged.
# 4. Pipe logs directly to `lb-docker-build.log` so the dashboard stays alive.
# =========================================================================

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
# Default 0: keys-only sshd (0100). Set ALFRED_ALLOW_SSH_PASSWORD_AUTH=1 only for bootstrap ISOs.
export ALFRED_ALLOW_SSH_PASSWORD_AUTH="${ALFRED_ALLOW_SSH_PASSWORD_AUTH:-0}"
# If not set, default to Debian Trixie (for live-build)
IMAGE="${DOCKER_LB_IMAGE:-debian:trixie}"
INNER="$REPO/scripts/lb-docker-inner-build.sh"
NAME="${ALFRED_LB_DOCKER_NAME:-alfred-lb-build-$(date +%s)}"

[[ -f "$INNER" ]] || { echo "Missing $INNER" >&2; exit 1; }
chmod 750 "$INNER" 2>/dev/null || true

# docker pull "$IMAGE" >/dev/null

# Match host uid so new files on bind mount are not all root:root (optional cleanup).
run=( docker run --init --rm --name alfred-lb-main-build --privileged --network=host --shm-size=16G
  --label alfred.compiler=active \
  -e "DEBIAN_FRONTEND=noninteractive"
  -e "BUILD_UID=$(id -u)" -e "BUILD_GID=$(id -g)"
  -e "ALFRED_LB_DOCKER_FLOCK_BLOCKING=${ALFRED_LB_DOCKER_FLOCK_BLOCKING}"
  -e "ALFRED_ALLOW_SSH_PASSWORD_AUTH=${ALFRED_ALLOW_SSH_PASSWORD_AUTH}"
  -v "$REPO:/work" \
  -v /home/gositeme/law:/host_law:ro \
  -v /home/root/law/models-vault:/models:ro
  -w /work
  "$IMAGE" bash /work/scripts/lb-docker-inner-build.sh )

if [[ "${1:-}" == "detach" ]]; then
  docker run -d --init --rm --privileged --network=host --shm-size=16G --name "$NAME" \
    --label alfred.compiler=active \
  -e "DEBIAN_FRONTEND=noninteractive" \
    -e "BUILD_UID=$(id -u)" -e "BUILD_GID=$(id -g)" \
    -e "ALFRED_LB_DOCKER_FLOCK_BLOCKING=${ALFRED_LB_DOCKER_FLOCK_BLOCKING}" \
    -e "ALFRED_ALLOW_SSH_PASSWORD_AUTH=${ALFRED_ALLOW_SSH_PASSWORD_AUTH}" \
    -v "$REPO:/work" \
  -v /home/gositeme/law:/host_law:ro \
  -v /home/root/law/models-vault:/models:ro \
    -w /work \
    "$IMAGE" bash /work/scripts/lb-docker-inner-build.sh
  echo "$NAME" >"$REPO/lb-docker.containername"
  echo "Started: $NAME"
  echo "Log:     $REPO/lb-docker-build.log"
  echo "Follow:  docker logs -f $NAME"
  exit 0
fi

"${run[@]}"

# Added by Agent for automated website sync
if [ -f /home/gositeme/law/alfredlinux-com-source-live/iso-output/packages.json ]; then
    cp /home/gositeme/law/alfredlinux-com-source-live/iso-output/packages.json /home/gositeme/domains/alfredlinux.com/public_html/packages.json
    echo "[TELEMETRY] packages.json successfully synced to live alfredlinux.com production website!"
fi
