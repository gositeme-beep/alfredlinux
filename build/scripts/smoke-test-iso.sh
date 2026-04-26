#!/usr/bin/env bash
# GA ISO operator smoke — size gate (7.77 GiB binary) + optional site alignment check.
# VERIFY_SITE=1 runs verify-ga-publish-alignment.sh (on-disk SUMS/torrent vs ga-release-state).
# Typical deploy: copy to /home/ubuntu/smoke-test-iso.sh on the build host (see /reseal operator block).
#
# Usage:
#   smoke-test-iso.sh /path/to/frozen.iso
#   VERIFY_SITE=1 ALFRED_SITE_ROOT=/path/to/public_html smoke-test-iso.sh /path/to/frozen.iso
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
iso="${1:-}"
if [[ -z "$iso" ]]; then
  echo "Usage: $0 /path/to/frozen.iso" >&2
  echo "Optional: VERIFY_SITE=1 ALFRED_SITE_ROOT=/path/to/public_html $0 /path/to/frozen.iso" >&2
  exit 2
fi

echo "[smoke] 1/2 — 7.77 GiB binary gate"
"$SCRIPT_DIR/check-iso-777gib.sh" "$iso"

if [[ "${VERIFY_SITE:-}" == "1" ]]; then
  echo "[smoke] 2/2 — site SUMS + torrent btih vs ga-release-state.php"
  if [[ -z "${ALFRED_SITE_ROOT:-}" ]]; then
    echo "smoke-test-iso: VERIFY_SITE=1 requires ALFRED_SITE_ROOT" >&2
    exit 1
  fi
  ALFRED_SITE_ROOT="$ALFRED_SITE_ROOT" "$SCRIPT_DIR/verify-ga-publish-alignment.sh"
else
  echo "[smoke] 2/2 — SKIP (set VERIFY_SITE=1 ALFRED_SITE_ROOT=... to run verify-ga-publish-alignment.sh)"
fi

echo "[smoke] OK — $iso"
