#!/usr/bin/env bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# sbom-dpkg-rootfs.sh — minimal package list from a Debian rootfs (TSV) for SBOM tooling.
#
# Requires root (or CAP_SYS_CHROOT): runs dpkg-query inside the given rootfs via chroot.
# Usage:
#   sudo bash scripts/sbom-dpkg-rootfs.sh /path/to/chroot > packages.tsv
#   sudo bash scripts/sbom-dpkg-rootfs.sh /path/to/chroot /tmp/packages.tsv
#
# See: docs/SBOM-EXPORT.txt (Syft/Trivy for SPDX/CycloneDX JSON).
set -euo pipefail

if [[ "${EUID:-$(id -u)}" -ne 0 ]]; then
  echo "error: must run as root (chroot requires privileges): sudo $0 ..." >&2
  exit 1
fi

ROOT=${1:-}
OUT=${2:-/dev/stdout}

if [[ -z "$ROOT" || ! -d "$ROOT" ]]; then
  echo "usage: sudo $0 /path/to/debian-rootfs [output.tsv]" >&2
  exit 1
fi

if [[ ! -r "$ROOT/var/lib/dpkg/status" ]]; then
  echo "error: missing $ROOT/var/lib/dpkg/status (not a Debian rootfs?)" >&2
  exit 1
fi

if ! command -v chroot >/dev/null 2>&1; then
  echo "error: chroot not found in PATH" >&2
  exit 1
fi

nl=$(chroot "$ROOT" dpkg-query -W -f '${Package}\t${Version}\t${Architecture}\n' | tee "$OUT" | wc -l)
printf 'OK: wrote %s lines to %s\n' "$nl" "$OUT" >&2
