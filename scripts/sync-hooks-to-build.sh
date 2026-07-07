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
binary_hooks=( "$BINARY_SRC"/*.binary "$CHROOT_SRC"/*.binary )

# Guard against duplicated kernel enforcer variants that bypass canonical naming
_extra_enforcers=( "$ROOT"/config/hooks/0999-kernel-*-enforcer.chroot "$ROOT"/config/hooks/live/0999-kernel-*-enforcer.sh )
for _ef in "${_extra_enforcers[@]}"; do
  if [[ -f "$_ef" ]]; then
    echo "WARN: Removing stale non-live hook: $_ef" >&2
    echo "Keep only config/hooks/live/0999-kernel-enforcer.hook.chroot" >&2
    exit 1
  fi
done

if ((${#chroot_hooks[@]} == 0)); then
  echo "error: no *.hook.chroot under $CHROOT_SRC" >&2
  exit 1
fi

for f in "${chroot_hooks[@]}"; do
  install -m0755 -d "$DST/live"
  install -m0755 "$f" "$DST/live/$(basename "$f")"
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

for f in "$DST"/live/*.hook.chroot "$DST"/*.binary; do
  [[ -e "$f" ]] || continue
  b="$(basename "$f")"
  [[ -L "$f" ]] && continue
  [[ -n "${valid[$b]+x}" ]] && continue
# DISABLED-BY-COMMANDER: rm -f "$f"
  echo "Removed orphan hook: $f"
done

# --- Sync includes.binary/ (splash, isolinux, grub) ---
INC_SRC="${ROOT}/config/includes.binary"
INC_DST="${ALFRED_BUILD_DIR:-${ROOT}/build}/config/includes.binary"
if [[ -d "$INC_SRC" ]]; then
  mkdir -p "$INC_DST"
  if command -v rsync >/dev/null 2>&1; then
    rsync -a --delete --exclude="live/alfred-models" "$INC_SRC/" "$INC_DST/"
  else
    # Remove everything except live/alfred-models if falling back to cp
    # DISABLED-v14.14: find "$INC_DST" -mindepth 1 -maxdepth 1 ! -name .live. -exec rm -rf {} + 2>/dev/null || true
    cp -a "$INC_SRC"/* "$INC_DST"/ 2>/dev/null || true
  fi
  _ic=$(find "$INC_DST" -type f 2>/dev/null | wc -l)
  echo "[sync-hooks-to-build] synced includes.binary ($_ic files)"
fi

# Removed destructive rm -rf of nested live/ directory because live-build requires it for chroot hooks.

echo "[sync-hooks-to-build] installed ${#chroot_hooks[@]} chroot hooks and ${#binary_hooks[@]} binary hooks to $DST"
