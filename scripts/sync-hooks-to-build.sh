#!/usr/bin/env bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# Copy every Kingdom hook from the single source of truth into the live-build
# tree under build/config/hooks/ (flat layout used by this repo’s lb profile).
# Preserves build-only helpers in that directory (e.g. 0010-alfred-bootstrap,
# 9999-fix-kernel-names) that are not mirrored under config/hooks/live/.
#
# Usage (repo root):
#   bash scripts/sync-hooks-to-build.sh
# Docker: invoked automatically from lb-docker-inner-build.sh before lb config.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SRC="${ROOT}/config/hooks/live"
DST="${ROOT}/build/config/hooks"

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

echo "[sync-hooks-to-build] installed ${#hooks[@]} hooks from $SRC -> $DST"
