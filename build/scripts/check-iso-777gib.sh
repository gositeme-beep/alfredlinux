#!/usr/bin/env bash
# Kingdom GA size gate: frozen hybrid ISO must be at least ~7.77 GiB **binary** (1024³).
# Usage: check-iso-777gib.sh /path/to/frozen.iso
# Exit 0 if size >= floor(7.77 * 1024^3); else 1. See includes/GA-LAUNCH-CHECKLIST.txt B6.
set -euo pipefail
iso="${1:-}"
if [[ -z "$iso" || "$iso" == "-h" || "$iso" == "--help" ]]; then
  echo "Usage: $0 /path/to/frozen.iso" >&2
  exit 2
fi
if [[ ! -f "$iso" ]]; then
  echo "check-iso-777gib: not a file: $iso" >&2
  exit 1
fi
bytes=$(stat -c '%s' "$iso" 2>/dev/null || stat -f '%z' "$iso")
min=$(awk 'BEGIN { print int(7.77 * 1024^3) }')
if (( bytes < min )); then
  echo "check-iso-777gib: FAIL size=$bytes bytes (need >= $min for 7.77 GiB binary)" >&2
  exit 1
fi
echo "check-iso-777gib: OK size=$bytes bytes (>= 7.77 GiB binary)"
exit 0
