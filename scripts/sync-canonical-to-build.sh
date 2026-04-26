#!/usr/bin/env bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# Merge **canonical** Alfred inputs at repo root into **build/config/** so `lb`
# from ./build matches this tree (hooks, package lists, local debs, build-assets).
#
# - Hooks:        config/hooks/live/*.hook.chroot → build/config/hooks/
# - Packages:     config/packages.chroot/*       → build/config/packages.chroot/
# - Package lists: config/package-lists/*.list.chroot → build/config/package-lists/
#   (does not remove build-only lists such as live.list.chroot)
# - Payload:      build-assets/ → build/config/includes.chroot/build-assets/
#   • ALFRED_FULL_BUILD_ASSETS=1 (set by lb-docker-inner-build.sh): full `rsync -a`
#     merge (no --delete) so missing gitignored media on thin checkouts is preserved
#     in build/includes.
#   • Otherwise: rsync only hook-critical subtrees (bible, kingdom-documents, omahon,
#     ollama) + root-level *.sh / *.py / *.txt / *.tar.gz — fast for CI and dev.
#
# Usage (repo root):  bash scripts/sync-canonical-to-build.sh
# Docker: lb-docker-inner-build.sh runs this before lb config.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

bash "$ROOT/scripts/sync-hooks-to-build.sh"

if [[ -d config/packages.chroot ]]; then
  mkdir -p build/config/packages.chroot
  shopt -s nullglob
  pkgs=(config/packages.chroot/*)
  if ((${#pkgs[@]})); then
    for f in "${pkgs[@]}"; do
      [[ -f "$f" ]] || continue
      install -m0644 "$f" "build/config/packages.chroot/$(basename "$f")"
    done
    echo "[sync-canonical] packages.chroot → build/config/packages.chroot (${#pkgs[@]} entries)"
  else
    echo "[sync-canonical] WARN: config/packages.chroot is empty (kernel .deb may be gitignored — copy from builder)" >&2
  fi
fi

if [[ -d config/package-lists ]]; then
  mkdir -p build/config/package-lists
  shopt -s nullglob
  lists=(config/package-lists/*.list.chroot)
  for f in "${lists[@]}"; do
    install -m0644 "$f" "build/config/package-lists/$(basename "$f")"
  done
  echo "[sync-canonical] package-lists (${#lists[@]} *.list.chroot)"
fi

if [[ -d build-assets ]]; then
  dst="${ROOT}/build/config/includes.chroot/build-assets"
  mkdir -p "$dst"
  if [[ "${ALFRED_FULL_BUILD_ASSETS:-}" == 1 ]]; then
    rsync -a "${ROOT}/build-assets/" "$dst/"
    echo "[sync-canonical] build-assets/ → includes/build-assets/ (FULL rsync)"
  else
    for sub in bible kingdom-documents omahon ollama; do
      if [[ -d "build-assets/${sub}" ]]; then
        mkdir -p "$dst/${sub}"
        rsync -a "build-assets/${sub}/" "$dst/${sub}/"
      fi
    done
    shopt -s nullglob
    for f in build-assets/*.sh build-assets/*.py build-assets/*.txt build-assets/*.tar.gz; do
      [[ -f "$f" ]] || continue
      install -m0644 "$f" "$dst/$(basename "$f")"
    done
    echo "[sync-canonical] build-assets/ (subtree + root artifacts → includes/build-assets/)"
  fi
fi
