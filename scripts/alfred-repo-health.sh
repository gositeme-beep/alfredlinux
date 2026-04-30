#!/usr/bin/env bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# alfred-repo-health.sh — metadata gate + security sweep (for CI or systemd timer).
# Exit non-zero if release-integrity, security-audit, or audit-law-wrappers fails.
#
# Optional: ALFRED_REPO_HEALTH_SHELLCHECK_ALL=1 runs security-audit with ALFRED_SHELLCHECK_ALL=1
# (full shellcheck of scripts/ops/shlib/build-assets; slower — use in dedicated CI or manual runs).
#
# Optional: ALFRED_REPO_HEALTH_JSON=1 prints one JSON line to stdout (machine-readable summary;
# human logs stay on stderr via log(); child scripts stdout is redirected to stderr so jq works).
#
# Usage (repo root):
#   bash scripts/alfred-repo-health.sh
# Override checkout path:
#   ALFRED_LINUX_REPO=/path/to/alfredlinux-com-source-live bash scripts/alfred-repo-health.sh
set -euo pipefail
ROOT="${ALFRED_LINUX_REPO:-$(cd "$(dirname "$0")/.." && pwd)}"
cd "$ROOT"

ts() { date -Iseconds 2>/dev/null || date; }
log() { printf '[%s] %s\n' "$(ts)" "$*" >&2; }

# Log elapsed seconds for each gate (systemd journal / CI timing).
run_phase() {
  local label=$1
  local -n sec_ref=$2
  shift 2
  log "alfred-repo-health: START $label"
  local t0=$SECONDS
  if [[ "${ALFRED_REPO_HEALTH_JSON:-0}" == 1 ]]; then
    "$@" >&2
  else
    "$@"
  fi
  sec_ref=$((SECONDS - t0))
  log "alfred-repo-health: END $label (${sec_ref}s)"
}

log "alfred-repo-health: ROOT=$ROOT"
ri_sec=0
run_phase release-integrity ri_sec bash "$ROOT/scripts/release-integrity.sh" check-repo
if [[ "${ALFRED_REPO_HEALTH_SHELLCHECK_ALL:-0}" == 1 ]]; then
  export ALFRED_SHELLCHECK_ALL=1
  log "alfred-repo-health: ALFRED_SHELLCHECK_ALL=1 (ALFRED_REPO_HEALTH_SHELLCHECK_ALL)"
fi
sa_sec=0
run_phase security-audit sa_sec bash "$ROOT/scripts/security-audit.sh"
law_sec=0
run_phase audit-law-wrappers law_sec bash "$ROOT/scripts/audit-law-wrappers.sh"
log "alfred-repo-health: all checks passed"

if [[ "${ALFRED_REPO_HEALTH_JSON:-0}" == 1 ]]; then
  REPO_HEALTH_JSON_ROOT="$ROOT" \
    REPO_HEALTH_JSON_RI="$ri_sec" \
    REPO_HEALTH_JSON_SA="$sa_sec" \
    REPO_HEALTH_JSON_LAW="$law_sec" \
    REPO_HEALTH_JSON_SC="${ALFRED_REPO_HEALTH_SHELLCHECK_ALL:-0}" \
    python3 - <<'PY'
import json, os
print(
    json.dumps(
        {
            "event": "alfred_repo_health",
            "status": "ok",
            "root": os.environ.get("REPO_HEALTH_JSON_ROOT", ""),
            "seconds": {
                "release_integrity": int(os.environ.get("REPO_HEALTH_JSON_RI", "0")),
                "security_audit": int(os.environ.get("REPO_HEALTH_JSON_SA", "0")),
                "audit_law_wrappers": int(os.environ.get("REPO_HEALTH_JSON_LAW", "0")),
            },
            "shellcheck_all": os.environ.get("REPO_HEALTH_JSON_SC", "0") == "1",
        },
        separators=(",", ":"),
    )
)
PY
fi
