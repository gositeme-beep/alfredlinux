#!/usr/bin/env bash
set -euo pipefail
REPO=/home/gositeme/law/alfredlinux-com-source-live
STATUS_JSON=/home/gositeme/law/alfred-build-control-plane/last-lb-docker.json
SERVICE=alfred-night-shift
EVENT_WRITER=$REPO/scripts/ops/write-ops-event.sh
RESTART_DOCKER=0
CLEAR_BUILD_ISO=0
KILL_CONTAINER=0
NO_RESTART=0
RECOVERY_REASON=manual_recovery
RECOVERY_ACTOR=operator

emit_event() {
  local event="$1" reason="${2:-}" rc="${3:-}" cname="${4:-}" phase_now="${5:-}"
  if [[ -x "$EVENT_WRITER" ]]; then
    "$EVENT_WRITER" --source recover-night-shift --event "$event" --reason "$reason" --rc "$rc" --container "$cname" --phase "$phase_now" || true
  fi
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --restart-docker) RESTART_DOCKER=1; shift ;;
    --clear-build-iso) CLEAR_BUILD_ISO=1; shift ;;
    --kill-container) KILL_CONTAINER=1; shift ;;
    --no-restart) NO_RESTART=1; shift ;;
    --reason) RECOVERY_REASON="${2:?}"; shift 2 ;;
    --actor) RECOVERY_ACTOR="${2:?}"; shift 2 ;;
    *) echo "Unknown arg: $1" >&2; exit 2 ;;
  esac
done

name="$(cat "$REPO/lb-docker.containername" 2>/dev/null || true)"
phase=""
if [[ -f "$STATUS_JSON" ]]; then
  phase="$(python3 - <<'PY' "$STATUS_JSON"
import json, sys
try:
    print(json.load(open(sys.argv[1])).get('phase', ''))
except Exception:
    print('')
PY
)"
fi

echo "[recover] service=$SERVICE phase=${phase:-unknown} container=${name:-none} reason=${RECOVERY_REASON} actor=${RECOVERY_ACTOR}"
emit_event "recovery_start" "$RECOVERY_REASON" "" "$name" "$phase"

sudo systemctl stop "$SERVICE" || true
sudo pkill -f "$REPO/scripts/watch-lb-docker-build.sh" || true
if (( RESTART_DOCKER == 1 )); then
  echo "[recover] restarting docker"
  sudo systemctl restart docker
fi
if (( KILL_CONTAINER == 1 )) && [[ -n "$name" ]]; then
  echo "[recover] removing container $name"
  sudo docker rm -f "$name" || true
fi
if (( CLEAR_BUILD_ISO == 1 )); then
  echo "[recover] clearing stale build/*.iso"
  sudo rm -f "$REPO"/build/*.iso
fi
if grep -q '\[inner\] lb build finished .* exit=0' "$REPO/lb-docker-build.log" 2>/dev/null; then
  if [[ "$phase" != "done" ]]; then
    echo "[recover] finalizing nap json"
    sudo bash "$REPO/scripts/ops/alfred-finalize-nap-json.sh" "$STATUS_JSON" || true
  fi
fi
if (( NO_RESTART == 0 )); then
  echo "[recover] starting $SERVICE"
  sudo systemctl start "$SERVICE"
  sleep 2
fi

# Stamp the control-plane status with structured recovery metadata for dashboards/ops.
python3 - <<'PY' "$STATUS_JSON" "$RECOVERY_REASON" "$RECOVERY_ACTOR" "$name" "$SERVICE" "$RESTART_DOCKER" "$CLEAR_BUILD_ISO" "$KILL_CONTAINER" "$NO_RESTART"
import json, sys, time
path, reason, actor, container, service, restart_docker, clear_iso, kill_container, no_restart = sys.argv[1:]
try:
    data = json.load(open(path)) if path else {}
except Exception:
    data = {}
data["recovery_reason_code"] = reason
data["recovery_actor"] = actor
data["recovery_ts"] = time.time()
data["last_recovery"] = {
    "ts": time.time(),
    "reason": reason,
    "actor": actor,
    "service": service,
    "container": container,
    "actions": {
        "restart_docker": restart_docker == "1",
        "clear_build_iso": clear_iso == "1",
        "kill_container": kill_container == "1",
        "no_restart": no_restart == "1",
    },
}
try:
    with open(path, "w", encoding="utf-8") as f:
        json.dump(data, f, indent=2)
except Exception:
    pass
PY

if systemctl is-active --quiet "$SERVICE"; then
  emit_event "recovery_done" "$RECOVERY_REASON" "0" "$name" "$phase"
else
  emit_event "recovery_done_service_inactive" "$RECOVERY_REASON" "1" "$name" "$phase"
fi

sudo systemctl status "$SERVICE" --no-pager -l | sed -n '1,80p' || true
echo '---'
sudo cat "$STATUS_JSON" 2>/dev/null || true
