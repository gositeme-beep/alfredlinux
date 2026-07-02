#!/usr/bin/env bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# Run as root (sudo) to fix common Docker/lb fallout: root-owned paths under build/config/.
# No passwords: pair with a tight NOPASSWD rule (see scripts/sudoers.d/).
#
# Safety: refuses unless REPO looks like this tree and lives under an allowlisted prefix.
#
# Usage (from repo root):
#   sudo ./scripts/alfred-build-root-cleanup.sh
#   sudo ./scripts/alfred-build-root-cleanup.sh /path/to/alfredlinux-com-source-live
set -euo pipefail

if [[ "$(id -u)" -ne 0 ]]; then
  echo "error: must run as root — try: sudo $0 [REPO]" >&2
  exit 1
fi

if [[ "${#}" -ge 1 ]]; then
  REPO="$(realpath -e "$1")"
else
  REPO="$(realpath -e "$(cd "$(dirname "$0")/.." && pwd)")"
fi

if [[ ! -f "$REPO/scripts/sync-hooks-to-build.sh" ]] || [[ ! -d "$REPO/config/hooks/live" ]]; then
  echo "error: refuse: not an Alfred linux repo root: $REPO" >&2
  exit 1
fi

# Narrow path allowlist — extend only if you clone elsewhere intentionally.
if [[ ! "$REPO" =~ ^/(home|srv|workspaces)/ ]]; then
  echo "error: refuse: REPO must be under /home, /srv, or /workspaces (got $REPO)" >&2
  exit 1
fi

_nested="${REPO}/build/config/hooks/live"
if [[ -d "$_nested" ]] || [[ -L "$_nested" ]]; then
  rm -rf "$_nested"
  echo "[alfred-build-root-cleanup] removed ${_nested}"
fi

u="$(stat -c '%u' "$REPO")"
g="$(stat -c '%g' "$REPO")"
_lists=(alfred.list.chroot alfred-b2.list.chroot)
for f in "${_lists[@]}"; do
  src="${REPO}/config/package-lists/${f}"
  dst="${REPO}/build/config/package-lists/${f}"
  if [[ -f "$src" ]]; then
    install -d -o "$u" -g "$g" -m0755 "$(dirname "$dst")"
    install -o "$u" -g "$g" -m0644 "$src" "$dst"
    echo "[alfred-build-root-cleanup] refreshed ${dst}"
  fi
done

echo "[alfred-build-root-cleanup] done"
