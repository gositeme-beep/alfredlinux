#!/usr/bin/env bash
# Warn when changes are being made in disposable build paths.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

if ! command -v git >/dev/null 2>&1; then
  exit 0
fi

warn_paths=("build/" "build/chroot/" "build/config/")
if git status --porcelain -- "${warn_paths[@]}" | grep -q .; then
  echo "[guard] NOTICE: changes detected under disposable build paths (build/, build/chroot/, build/config/)." >&2
  echo "[guard] Put persistent customizations in state/experience-overlay/, config/hooks/live/, config/includes.chroot/, or build-assets/." >&2
fi
