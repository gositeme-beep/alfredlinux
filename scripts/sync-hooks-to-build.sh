#!/usr/bin/env bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# Copy every Kingdom hook from the single source of truth (config/hooks/live/)
# into the live-build tree under build/config/hooks/ (flat layout for this repo's
# lb profile). The exact set is every *.hook.chroot under live/ (e.g. 0100
# through 0725 in the current tree — not necessarily low-numbered Debian lb hooks).
#
# After install, removes orphan flat *.hook.chroot in the destination (stale
# copies no longer present under live/). Skips symlinks. Nested DST/live/ is
# removed when possible (often root-owned from lb in Docker: host rm, else docker
# alpine on the bind mount, else WARN).
#
# Usage (repo root):
#   bash scripts/sync-hooks-to-build.sh
#   ALFRED_BUILD_DIR=/path/to/build bash scripts/sync-hooks-to-build.sh
# Docker: invoked automatically from lb-docker-inner-build.sh before lb config.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SRC="${ROOT}/config/hooks/live"
DST="${ALFRED_BUILD_DIR:-${ROOT}/build}/config/hooks"

if [[ ! -d "$SRC" ]]; then
  echo "error: missing hook source dir: $SRC" >&2
  exit 1
fi

mkdir -p "$DST"
shopt -s nullglob
hooks=( "$SRC"/*.hook.chroot )
if ((${#hooks[@]} == 0)); then
  echo "error: no *.hook.chroot under $SRC" >&2
  exit 1
fi

for f in "${hooks[@]}"; do
  install -m0755 "$f" "$DST/$(basename "$f")"
done

declare -A valid=()
for f in "${hooks[@]}"; do
  valid["$(basename "$f")"]=1
done
for f in "$DST"/*.hook.chroot; do
  b="$(basename "$f")"
  [[ -L "$f" ]] && continue
  [[ -n "${valid[$b]+x}" ]] && continue
  rm -f "$f"
  echo "Removed orphan hook: $f"
done

# Stale nested live/ (not used by this profile; confuses greps). May be root-owned.
_nested="${DST}/live"
if [[ -d "$_nested" ]] || [[ -L "$_nested" ]]; then
  if rm -rf "$_nested" 2>/dev/null; then
    :
  elif command -v docker >/dev/null 2>&1     && docker run --rm -v "${DST}:/h" alpine:3.19 rm -rf /h/live >/dev/null 2>&1; then
    echo "[sync-hooks-to-build] removed nested live/ via docker (root-owned files on bind mount)"
  else
    echo "[sync-hooks-to-build] WARN: could not remove ${_nested} (install docker or run: sudo rm -rf ${_nested})" >&2
  fi
fi

echo "[sync-hooks-to-build] installed ${#hooks[@]} hooks from $SRC -> $DST"
