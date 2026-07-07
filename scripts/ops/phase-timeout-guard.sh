#!/usr/bin/env bash
# SPDX-License-Identifier: Apache-2.0
# SPDX-FileCopyrightText: 2026 Alfred Trust <alfred@alfredlinux.com>
# In the name of Yeshua, Jesus Christ of Bethlehem, King of the Universe.
set -euo pipefail
STATUS_JSON="${STATUS_JSON:-/home/root/law/alfred-build-control-plane/last-lb-docker.json}"
EMIT_EVENT="${EMIT_EVENT:-/home/root/law/alfredlinux-com-source-live/scripts/ops/write-ops-event.sh}"
RECOVER="${RECOVER:-/home/root/law/alfredlinux-com-source-live/scripts/ops/recover-night-shift.sh}"
LOCK_FILE="${LOCK_FILE:-/home/root/law/alfred-build-control-plane/phase-timeout-guard.cooldown}"
COOLDOWN_SECONDS="${COOLDOWN_SECONDS:-900}"
DRY_RUN="${DRY_RUN:-0}"

json_get() {
  python3 - "$1" "$2" <<'PY'
import json,sys
p,k=sys.argv[1:3]
try:
  d=json.load(open(p))
  v=d.get(k,'')
  print(v if v is not None else '')
except Exception:
  print('')
PY
}

now="$(date +%s)"
if [[ -f "$LOCK_FILE" ]]; then
  last="$(cat "$LOCK_FILE" 2>/dev/null || echo 0)"
  if [[ "$last" =~ ^[0-9]+$ ]] && (( now - last < COOLDOWN_SECONDS )); then
    exit 0
  fi
fi

[[ -f "$STATUS_JSON" ]] || exit 0
phase="$(json_get "$STATUS_JSON" phase)"
started="$(json_get "$STATUS_JSON" phase_started_at)"
[[ -n "$started" ]] || started="$(json_get "$STATUS_JSON" updated_at)"
[[ -n "$started" ]] || exit 0
ts="$(date -d "$started" +%s 2>/dev/null || echo 0)"
(( ts > 0 )) || exit 0

case "$phase" in
  waiting_for_container) limit=2700 ;;
  inner_running) limit=7200 ;;
  post_build_finalize) limit=1200 ;;
  *) exit 0 ;;
esac

age=$(( now - ts ))
(( age > limit )) || exit 0
reason="phase=$phase age_s=$age limit_s=$limit dry_run=$DRY_RUN"
"$EMIT_EVENT" --source phase-timeout-guard --event phase_timeout_detected --reason "$reason" || true
(( DRY_RUN == 1 )) && exit 0

echo "$now" > "$LOCK_FILE"
"$RECOVER" --reason "phase_timeout:$phase" --actor phase-timeout-guard || true
"$EMIT_EVENT" --source phase-timeout-guard --event phase_timeout_recovery_triggered --reason "$reason" || true
