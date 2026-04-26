#!/usr/bin/env bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# Copy lives next to repo: same as /home/gositeme/law/alfred-build-on-ubuntu.sh
#   export BUILD_HOST=203.0.113.50
#   export ALFRED_SRC=$(cd "$(dirname "$0")/.." && pwd)
#   ./scripts/build-on-ubuntu.sh sync-and-ssh
set -euo pipefail

ALFRED_SRC="${ALFRED_SRC:-$(cd "$(dirname "$0")/.." && pwd)}"
REMOTE_DIR="${REMOTE_DIR:-alfredlinux-com-source-live}"

usage() {
  echo "Usage:" >&2
  echo "  BUILD_HOST=ip.or.dns $0 ssh|sync|sync-and-ssh" >&2
  echo "  $0 ip.or.dns ssh|sync|sync-and-ssh" >&2
  exit 1
}

HOST="${BUILD_HOST:-}"
CMD=""

if [[ "$#" -eq 2 ]]; then
  HOST="$1"
  CMD="$2"
elif [[ "$#" -eq 1 ]]; then
  CMD="$1"
else
  usage
fi

[[ "$CMD" =~ ^(ssh|sync|sync-and-ssh)$ ]] || usage
[[ -n "$HOST" ]] || usage

do_sync() {
  [[ -d "$ALFRED_SRC" ]] || {
    echo "Missing local tree: $ALFRED_SRC" >&2
    exit 1
  }
  echo "==> rsync $ALFRED_SRC/ -> ubuntu@${HOST}:~/${REMOTE_DIR}/"
  rsync -az --info=stats2 \
    --exclude 'build/' \
    --exclude 'binary/' \
    --exclude 'chroot/' \
    --exclude 'cache/' \
    --exclude 'iso-output/*.iso' \
    --exclude 'iso-output/*.squashfs' \
    --exclude 'build-assets/wallpapers/' \
    --exclude 'build-assets/videos/' \
    --exclude 'build-assets/music/' \
    "$ALFRED_SRC/" "ubuntu@${HOST}:~/${REMOTE_DIR}/"
}

case "$CMD" in
  ssh)
    echo "==> ssh ubuntu@${HOST}  (then: sudo su -  and cd ~/${REMOTE_DIR}/build)"
    exec ssh -o "BatchMode=no" "ubuntu@${HOST}"
    ;;
  sync)
    do_sync
    ;;
  sync-and-ssh)
    do_sync
    echo "==> ssh ubuntu@${HOST}"
    exec ssh -o "BatchMode=no" "ubuntu@${HOST}"
    ;;
esac
