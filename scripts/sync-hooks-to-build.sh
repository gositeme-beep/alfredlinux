#!/usr/bin/env bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# Copy canonical hooks from config/hooks/ into build/config/hooks/.
# Chroot hooks: config/hooks/live/*.hook.chroot
# Binary hooks: config/hooks/*.hook.binary
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
CHROOT_SRC="${ROOT}/config/hooks/live"
BINARY_SRC="${ROOT}/config/hooks"
DST="${ALFRED_BUILD_DIR:-${ROOT}/build}/config/hooks"

if [[ ! -d "$CHROOT_SRC" ]]; then
  echo "error: missing chroot hook source dir: $CHROOT_SRC" >&2
  exit 1
fi

mkdir -p "$DST"
shopt -s nullglob
chroot_hooks=( "$CHROOT_SRC"/*.hook.chroot )
binary_hooks=( "$BINARY_SRC"/*.binary )

if ((${#chroot_hooks[@]} == 0)); then
  echo "error: no *.hook.chroot under $CHROOT_SRC" >&2
  exit 1
fi

for f in "${chroot_hooks[@]}"; do
  install -m0755 "$f" "$DST/$(basename "$f")"
done

for f in "${binary_hooks[@]}"; do
  install -m0755 "$f" "$DST/$(basename "$f")"
done

declare -A valid=()
for f in "${chroot_hooks[@]}"; do
  valid["$(basename "$f")"]=1
done
for f in "${binary_hooks[@]}"; do
  valid["$(basename "$f")"]=1
done

for f in "$DST"/*.hook.chroot "$DST"/*.binary; do
  [[ -e "$f" ]] || continue
  b="$(basename "$f")"
  [[ -L "$f" ]] && continue
  [[ -n "${valid[$b]+x}" ]] && continue
  rm -f "$f"
  echo "Removed orphan hook: $f"
done

# --- Sync includes.binary/ (splash, isolinux, grub) ---
INC_SRC="${ROOT}/config/includes.binary"
INC_DST="${ALFRED_BUILD_DIR:-${ROOT}/build}/config/includes.binary"
if [[ -d "$INC_SRC" ]]; then
  mkdir -p "$INC_DST"
  if command -v rsync >/dev/null 2>&1; then
    rsync -a --delete "$INC_SRC/" "$INC_DST/"
  else
    rm -rf "$INC_DST"
    cp -a "$INC_SRC" "$INC_DST"
  fi
  _ic=$(find "$INC_DST" -type f 2>/dev/null | wc -l)
  echo "[sync-hooks-to-build] synced includes.binary ($_ic files)"
fi

# Stale nested live/ (not used by this profile; confuses greps). May be root-owned.
_nested="${DST}/live"
if [[ -d "$_nested" ]] || [[ -L "$_nested" ]]; then
  if rm -rf "$_nested" 2>/dev/null; then
    :
  elif [[ "${ALFRED_SYNC_HOOKS_SKIP_DOCKER:-}" != 1 ]] && command -v docker >/dev/null 2>&1 \
    && docker run --rm -v "${DST}:/h" alpine:3.19 rm -rf /h/live >/dev/null 2>&1; then
    echo "[sync-hooks-to-build] removed nested live/ via docker (root-owned files on bind mount)"
  else
    echo "[sync-hooks-to-build] WARN: could not remove ${_nested} (install docker or run: sudo rm -rf ${_nested})" >&2
  fi
fi

echo "[sync-hooks-to-build] installed ${#chroot_hooks[@]} chroot hooks and ${#binary_hooks[@]} binary hooks to $DST"
