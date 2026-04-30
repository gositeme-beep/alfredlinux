#!/usr/bin/env bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# Populate config/packages.chroot/ with linux 7.0.1 .deb files for live-build / iso-preflight.
#
# Order (first hit wins after "already present"):
#   1) Nothing if linux-image-7.0.1*.deb already in config/packages.chroot/
#   2) ALFRED_KERNEL_DEBS_ARCHIVE=path.tar.gz|.tar.zst  OR default archive under build-assets/ (see README there)
#   3) KERNEL_WORK (default: ../kernel-7.0.1-work relative to repo) — same files copy-kernel-debs used
#
# Usage:
#   bash scripts/stage-kernel-debs-for-iso.sh           # idempotent; exit 0 even if nothing found
#   bash scripts/stage-kernel-debs-for-iso.sh --strict # exit 1 if linux-image-7.0.1*.deb still missing after staging
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PC="${ROOT}/config/packages.chroot"
STRICT=0
[[ "${1:-}" == "--strict" ]] && STRICT=1

mkdir -p "$PC"
shopt -s nullglob
have_img=( "$PC"/linux-image-7.0.1*.deb )
if ((${#have_img[@]})); then
  echo "[stage-kernel] OK — linux-image-7.0.1*.deb already in $PC"
  exit 0
fi

stage_from_archive() {
  local arch="$1"
  echo "[stage-kernel] extracting -> $PC"
  case "$arch" in
    *.tar.zst|*.tzst)
      if command -v zstd &>/dev/null; then
        tar -I zstd -xf "$arch" -C "$PC"
      else
        echo "[stage-kernel] ERROR: need zstd to extract $arch" >&2
        return 1
      fi
      ;;
    *.tar.gz|*.tgz) tar -xzf "$arch" -C "$PC" ;;
    *)
      echo "[stage-kernel] ERROR: unsupported archive suffix: $arch" >&2
      return 1
      ;;
  esac
}

ARCHIVE="${ALFRED_KERNEL_DEBS_ARCHIVE:-}"
if [[ -z "$ARCHIVE" ]]; then
  for cand in \
    "$ROOT/build-assets/kernel-7.0.1-debs/linux-7.0.1-debs-for-iso.tar.gz" \
    "$ROOT/build-assets/kernel-7.0.1-debs/linux-7.0.1-debs-for-iso.tar.zst"
  do
    if [[ -f "$cand" ]]; then
      ARCHIVE="$cand"
      break
    fi
  done
fi

if [[ -n "$ARCHIVE" ]]; then
  if [[ ! -f "$ARCHIVE" ]]; then
    echo "[stage-kernel] ERROR: ALFRED_KERNEL_DEBS_ARCHIVE set but not a file: $ARCHIVE" >&2
    exit 1
  fi
  stage_from_archive "$ARCHIVE" || exit 1
fi

shopt -u nullglob
shopt -s nullglob
have_img=( "$PC"/linux-image-7.0.1*.deb )
if ((${#have_img[@]})); then
  echo "[stage-kernel] OK — linux-image-7.0.1*.deb present after archive extract"
  exit 0
fi

WORK="${KERNEL_WORK:-$ROOT/../kernel-7.0.1-work}"
found=0
shopt -s nullglob
for f in "$WORK"/linux-image-7.0.1*.deb "$WORK"/linux-headers-7.0.1*.deb "$WORK"/linux-libc-dev_*.deb; do
  [[ -f "$f" ]] || continue
  echo "[stage-kernel] cp $f -> $PC/"
  cp -a "$f" "$PC/"
  found=1
done
shopt -u nullglob

have_img=( "$PC"/linux-image-7.0.1*.deb )
if ((${#have_img[@]})); then
  echo "[stage-kernel] OK — copied linux 7.0.1 debs from $WORK"
  exit 0
fi

if [[ "$found" -eq 0 ]]; then
  echo "[stage-kernel] WARN: no archive and no matching .deb in $WORK — see config/packages.chroot/README-KERNEL7.txt"
fi

if [[ "$STRICT" -eq 1 ]]; then
  have_img=( "$PC"/linux-image-7.0.1*.deb )
  if ((!${#have_img[@]})); then
    echo "[stage-kernel] FAIL: still no linux-image-7.0.1*.deb under $PC" >&2
    exit 1
  fi
fi
exit 0
