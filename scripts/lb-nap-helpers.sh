#!/usr/bin/env bash
# Sourced by watch-lb-docker-build.sh and supervise-lb-docker-nap.sh — do not run standalone.
# Sleep inhibit: blocks suspend/idle while `docker wait` runs (unless ALFRED_NO_INHIBIT_SLEEP=1).

alfred_maybe_inhibit_exec() {
  if [[ -n "${ALFRED_NO_INHIBIT_SLEEP:-}" ]]; then
    "$@"
    return $?
  fi
  if command -v systemd-inhibit &>/dev/null; then
    systemd-inhibit \
      --who="alfred-lb-docker" \
      --why="Alfred live-build ISO (docker wait)" \
      --what=sleep:idle:handle-lid-switch \
      --mode=block \
      -- "$@"
  else
    "$@"
  fi
}

# Snapshot while the lb container is still running (so last-lb-docker.json is not stale).
alfred_status_json_waiting() {
  local path=$1 name=$2
  python3 - "$path" "$name" <<'PY'
import json, sys, time
path, name = sys.argv[1:3]
data = {
    "phase": "waiting_for_container",
    "ts": time.time(),
    "container": name,
    "docker_exit": "pending",
    "iso_paths": [],
    "iso_count": 0,
    "nap_ok": None,
    "note": "Build still running. nap_ok is authoritative when phase is done.",
}
with open(path, "w") as out:
    json.dump(data, out, indent=2)
PY
}

# Background: touch heartbeat file every ALFRED_NAP_HEARTBEAT_SEC (default 120). Prints PID.
alfred_start_heartbeat() {
  local hb=$1
  local sec="${ALFRED_NAP_HEARTBEAT_SEC:-120}"
  if [[ "$sec" == "0" ]]; then
    echo ""
    return
  fi
  mkdir -p "$(dirname "$hb")" 2>/dev/null || true
  (
    while true; do
      date -Is >"${hb}.tmp" 2>/dev/null && mv -f "${hb}.tmp" "$hb" 2>/dev/null || true
      sleep "$sec"
    done
  ) &
  echo $!
}

alfred_stop_heartbeat() {
  local pid=$1
  [[ -n "$pid" ]] && kill "$pid" 2>/dev/null || true
}
