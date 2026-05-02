#!/usr/bin/env bash
set -euo pipefail

LAW=/home/gositeme/law
SL=$LAW/alfredlinux-com-source-live
SERVICE=alfred-night-shift
HEARTBEAT=$SL/.lb-docker-watch.heartbeat
STATUS_JSON=$LAW/alfred-build-control-plane/last-lb-docker.json
NAME_FILE=$SL/lb-docker.containername
RECOVER=$SL/scripts/ops/recover-night-shift.sh
EVENT_WRITER=$SL/scripts/ops/write-ops-event.sh
LOCK=$LAW/.night-shift-heartbeat-guard.lock
STATE_FILE=$LAW/.night-shift-heartbeat-guard.state
LOG=$LAW/night-shift-heartbeat-guard.log

: "${ALFRED_WATCH_HEARTBEAT_MAX_AGE_SEC:=900}"
: "${ALFRED_WATCH_RECOVERY_MIN_INTERVAL_SEC:=1800}"
: "${ALFRED_WATCH_RECOVERY_REQUIRE_PHASE:=waiting_for_container}"
: "${ALFRED_WATCH_RECOVERY_RESTART_DOCKER:=0}"

DRY_RUN=0
if [[ "${1:-}" == "--dry-run" ]]; then
  DRY_RUN=1
fi

log() {
  echo "[guard $(date -Is)] $*" | tee -a "$LOG"
}

emit_event() {
  local event="$1" reason="${2:-}" rc="${3:-}" cname="${4:-}" phase="${5:-}"
  if [[ -x "$EVENT_WRITER" ]]; then
    "$EVENT_WRITER" --source night-shift-heartbeat-guard --event "$event" --reason "$reason" --rc "$rc" --container "$cname" --phase "$phase" || true
  fi
}

exec 200>"$LOCK"
if ! flock -n 200; then
  exit 0
fi

if ! systemctl is-active --quiet "$SERVICE"; then
  exit 0
fi

if [[ ! -f "$HEARTBEAT" ]]; then
  exit 0
fi

now="$(date +%s)"
hb_epoch="$(stat -c %Y "$HEARTBEAT" 2>/dev/null || echo 0)"
if [[ ! "$hb_epoch" =~ ^[0-9]+$ ]]; then
  hb_epoch=0
fi
age=$(( now - hb_epoch ))
if (( age < ALFRED_WATCH_HEARTBEAT_MAX_AGE_SEC )); then
  exit 0
fi

phase=""
if [[ -f "$STATUS_JSON" ]]; then
  phase="$(python3 - <<'PY' "$STATUS_JSON"
import json, sys
try:
    d = json.load(open(sys.argv[1]))
    print(d.get('phase', ''))
except Exception:
    print('')
PY
)"
fi

if [[ -n "$ALFRED_WATCH_RECOVERY_REQUIRE_PHASE" && "$phase" != "$ALFRED_WATCH_RECOVERY_REQUIRE_PHASE" ]]; then
  exit 0
fi

last_recovery=0
if [[ -f "$STATE_FILE" ]]; then
  last_recovery="$(grep -E '^LAST_RECOVERY_EPOCH=' "$STATE_FILE" | tail -1 | cut -d= -f2 || true)"
fi
if [[ ! "$last_recovery" =~ ^[0-9]+$ ]]; then
  last_recovery=0
fi
since_last=$(( now - last_recovery ))
if (( last_recovery > 0 && since_last < ALFRED_WATCH_RECOVERY_MIN_INTERVAL_SEC )); then
  log "heartbeat stale (${age}s) but in cooldown (${since_last}s < ${ALFRED_WATCH_RECOVERY_MIN_INTERVAL_SEC}s)"
  exit 0
fi

cname=""
if [[ -f "$STATUS_JSON" ]]; then
  cname="$(python3 - <<'PY' "$STATUS_JSON"
import json, sys
try:
    d = json.load(open(sys.argv[1]))
    print(d.get('container', ''))
except Exception:
    print('')
PY
)"
fi
if [[ -z "$cname" && -s "$NAME_FILE" ]]; then
  cname="$(tr -d '\n\r' < "$NAME_FILE")"
fi
running="unknown"
if [[ -n "$cname" ]]; then
  running="$(timeout 20 docker inspect -f '{{.State.Running}}' "$cname" 2>/dev/null || true)"
  if [[ -z "$running" ]]; then
    running="$(sudo -n timeout 20 docker inspect -f '{{.State.Running}}' "$cname" 2>/dev/null || true)"
  fi
  [[ -n "$running" ]] || running="unknown"
fi

log "heartbeat stale (${age}s) phase=${phase:-none} container=${cname:-none} running=${running}"
emit_event "heartbeat_stale" "stale_heartbeat" "" "$cname" "$phase"

if (( DRY_RUN == 1 )); then
  log "dry-run: would execute recovery"
  emit_event "recovery_dry_run" "stale_heartbeat" "" "$cname" "$phase"
  exit 0
fi

args=()
if [[ "$ALFRED_WATCH_RECOVERY_RESTART_DOCKER" == "1" ]]; then
  args+=(--restart-docker)
fi

if [[ ! -x "$RECOVER" ]]; then
  log "recovery script missing: $RECOVER"
  exit 1
fi

if "$RECOVER" --reason stale_heartbeat --actor heartbeat_guard "${args[@]}" >>"$LOG" 2>&1; then
  {
    echo "LAST_RECOVERY_EPOCH=$now"
    echo "LAST_REASON=stale_heartbeat"
    echo "LAST_PHASE=${phase}"
    echo "LAST_CONTAINER=${cname}"
    echo "LAST_AGE_SEC=${age}"
  } > "$STATE_FILE"
  chmod 600 "$STATE_FILE" || true
  log "recovery completed"
  emit_event "recovery_completed" "stale_heartbeat" "0" "$cname" "$phase"
else
  log "recovery failed"
  emit_event "recovery_failed" "stale_heartbeat" "1" "$cname" "$phase"
  exit 1
fi
