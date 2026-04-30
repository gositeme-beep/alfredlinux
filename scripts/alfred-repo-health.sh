#!/usr/bin/env bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# alfred-repo-health.sh — metadata gate + security sweep (for CI or systemd timer).
# Exit non-zero if release-integrity, security-audit, or audit-law-wrappers fails.
#
# Usage (repo root):
#   bash scripts/alfred-repo-health.sh
# Override checkout path:
#   ALFRED_LINUX_REPO=/path/to/alfredlinux-com-source-live bash scripts/alfred-repo-health.sh
set -euo pipefail
ROOT="${ALFRED_LINUX_REPO:-$(cd "$(dirname "$0")/.." && pwd)}"
cd "$ROOT"

ts() { date -Iseconds 2>/dev/null || date; }
log() { printf '[%s] %s\n' "$(ts)" "$*"; }

log "alfred-repo-health: ROOT=$ROOT"
bash "$ROOT/scripts/release-integrity.sh" check-repo
bash "$ROOT/scripts/security-audit.sh"
bash "$ROOT/scripts/audit-law-wrappers.sh"
log "alfred-repo-health: all checks passed"
