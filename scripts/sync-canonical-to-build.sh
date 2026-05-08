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
#   • Otherwise (“fast”): rsync hook-heavy subtrees + **every file** at build-assets/
#     depth 1 (preserves execute bit via cp -a: omahon, helm, rustup-init, keys,
#     code-server*.deb, etc.) — still skips a full deep walk of huge gitignored trees
#     unless you set ALFRED_FULL_BUILD_ASSETS=1.
#
# Usage (repo root):  bash scripts/sync-canonical-to-build.sh
# Docker: lb-docker-inner-build.sh runs this before lb config.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

bash "$ROOT/scripts/sync-hooks-to-build.sh"

if [[ -d config/packages.chroot ]]; then
  mkdir -p build/config/packages.chroot
  # Purge stale debs from prior runs so the snapshot exactly matches canonical inputs.
  find build/config/packages.chroot -mindepth 1 -maxdepth 1 \( -type f -o -type l \) -delete
  shopt -s nullglob
  pkgs=(config/packages.chroot/*)
  if ((${#pkgs[@]})); then
    for f in "${pkgs[@]}"; do
      [[ -f "$f" ]] || continue
      install -m0644 "$f" "build/config/packages.chroot/$(basename "$f")"
    done
    echo "[sync-canonical] packages.chroot → build/config/packages.chroot (${#pkgs[@]} entries)"
    # live-build bind-mounts this into the chroot as file:/root/packages — world-readable
    # avoids "Permission denied" on ./Packages when apt runs without root in some lb stages.
    chmod -R a+rX "${ROOT}/build/config/packages.chroot" 2>/dev/null || true
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
    # Subtrees hooks read often (dirs only — omahon/ollama may be single files at root).
    for sub in bible kingdom-documents videos wallpapers music nodejs-20-debs; do
      if [[ -d "build-assets/${sub}" ]]; then
        mkdir -p "$dst/${sub}"
        rsync -a "build-assets/${sub}/" "$dst/${sub}/"
      fi
    done
    # Every depth-1 file: binaries (omahon, helm, zoxide, starship, rustup-init…),
    # keys, .deb, .tar.gz, placeholders — cp -a keeps +x.
    while IFS= read -r -d '' f; do
      cp -a "$f" "$dst/"
    done < <(find "${ROOT}/build-assets" -maxdepth 1 -type f -print0)
    echo "[sync-canonical] build-assets/ (FAST: media/dev subtrees + all root files)"
  fi
fi

# live-build `local/` hooks (repo root local/ → build/local) — keep in sync if present.
if [[ -d local ]] && [[ -n "$(ls -A local 2>/dev/null)" ]]; then
  mkdir -p build/local
  rsync -a "${ROOT}/local/" "${ROOT}/build/local/"
  echo "[sync-canonical] local/ → build/local/"
fi

# ─── PATCH: always sync canonical auto/config → build/auto/config ─────────
# Without this, stale build/auto/config from prior builds (e.g. bookworm) wins
# and lb config picks up the wrong distribution/bootloaders.
if [ -f /work/auto/config ] && [ -d /work/build ]; then
  mkdir -p /work/build/auto
  cp -f /work/auto/config /work/build/auto/config
  chmod 0755 /work/build/auto/config
  echo "[sync-canonical] auto/config → build/auto/config (canonical pin)"
fi
