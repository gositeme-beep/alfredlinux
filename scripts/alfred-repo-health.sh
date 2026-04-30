#!/usr/bin/env bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# alfred-repo-health.sh — metadata gate + security sweep (for CI or systemd timer).
# Exit non-zero if release-integrity, security-audit, or audit-law-wrappers fails.
#
# Optional: ALFRED_REPO_HEALTH_SHELLCHECK_ALL=1 runs security-audit with ALFRED_SHELLCHECK_ALL=1
# (full shellcheck of scripts/ops/shlib/build-assets; slower — use in dedicated CI or manual runs).
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

# Log elapsed seconds for each gate (systemd journal / CI timing).
phase() {
  local name=$1
  shift
  log "alfred-repo-health: START $name"
  local t0=$SECONDS
  "$@"
  log "alfred-repo-health: END $name ($((SECONDS - t0))s)"
}

log "alfred-repo-health: ROOT=$ROOT"
phase release-integrity bash "$ROOT/scripts/release-integrity.sh" check-repo
if [[ "${ALFRED_REPO_HEALTH_SHELLCHECK_ALL:-0}" == 1 ]]; then
  export ALFRED_SHELLCHECK_ALL=1
  log "alfred-repo-health: ALFRED_SHELLCHECK_ALL=1 (ALFRED_REPO_HEALTH_SHELLCHECK_ALL)"
fi
phase security-audit bash "$ROOT/scripts/security-audit.sh"
phase audit-law-wrappers bash "$ROOT/scripts/audit-law-wrappers.sh"
log "alfred-repo-health: all checks passed"
