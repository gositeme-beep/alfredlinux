#!/usr/bin/env bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# Copy every Kingdom hook from the single source of truth (config/hooks/live/)
# into the live-build tree under build/config/hooks/ (flat layout for this repo's
# lb profile). Includes e.g. 0010-alfred-bootstrap, 0050-alfred-kernel7,
# 9999-fix-kernel-names, and the rest of the numbered hooks under live/.
#
# After install, removes orphan flat *.hook.chroot in the destination (stale
# copies no longer present under live/). Skips symlinks. Nested DST/live/ is
# removed -- hooks belong only in the flat hooks directory.
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

# Stale nested copy (not used by lb profile; confuses greps) -- remove if present.
rm -rf "${DST}/live"

echo "[sync-hooks-to-build] installed ${#hooks[@]} hooks from $SRC -> $DST"
