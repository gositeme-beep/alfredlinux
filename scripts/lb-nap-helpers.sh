#!/usr/bin/env bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# Sourced by watch-lb-docker-build.sh, supervise-lb-docker-nap.sh, alfred-night-shift.sh, etc.
# Do not run standalone.
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

# Bound `docker inspect` so a wedged daemon does not stall watch/night-shift for days.
# ALFRED_DOCKER_INSPECT_TIMEOUT_SEC=0 disables the wrapper (bare docker inspect).
alfred_docker_inspect_ok() {
  local name="$1"
  local sec="${ALFRED_DOCKER_INSPECT_TIMEOUT_SEC:-30}"
  if ! [[ "$sec" =~ ^[0-9]+$ ]] || (( sec <= 0 )); then
    docker inspect "$name" &>/dev/null
    return $?
  fi
  if timeout "$sec" docker inspect "$name" &>/dev/null; then
    return 0
  fi
  local rc=$?
  if (( rc == 124 )) || (( rc == 137 )); then
    echo "alfred_docker_inspect_ok: timeout (${sec}s) inspecting $name - docker daemon may be wedged" >&2
  fi
  return 1
}

# Same timeout as alfred_docker_inspect_ok; prints nothing on failure/timeout.
alfred_docker_inspect_fmt() {
  local name="$1" fmt="$2"
  local sec="${ALFRED_DOCKER_INSPECT_TIMEOUT_SEC:-30}"
  if ! [[ "$sec" =~ ^[0-9]+$ ]] || (( sec <= 0 )); then
    docker inspect -f "$fmt" "$name" 2>/dev/null || true
    return 0
  fi
  timeout "$sec" docker inspect -f "$fmt" "$name" 2>/dev/null || true
}

# Derive coarse build progress from the most recent hook number in the build log.
alfred_progress_from_log() {
  local log_file="$1"
  python3 - "$log_file" <<'PY2'
import re, sys
from pathlib import Path
p = Path(sys.argv[1])
if not p.exists():
    print('')
    raise SystemExit(0)
text = p.read_text(encoding='utf-8', errors='replace')
matches = re.findall(r'Executing hook .*?/([0-9]{4})-[^\s/]+', text)
if not matches:
    print('')
    raise SystemExit(0)
try:
    hook = int(matches[-1])
except Exception:
    print('')
    raise SystemExit(0)
pct = int(round((hook / 470.0) * 100.0))
pct = max(10, min(95, pct))
print(pct)
PY2
}

# Snapshot while the lb container is still running (so last-lb-docker.json is not stale).
alfred_status_json_waiting() {
  local path=$1 name=$2 progress=${3:-}
  python3 - "$path" "$name" "$progress" <<'PY'
import json, sys, time
path, name, progress = sys.argv[1:4]
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
if str(progress).strip():
    try:
        data["progress_pct"] = max(1, min(99, int(float(progress))))
    except Exception:
        pass
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
  # Run heartbeat in a separate `bash -c` process so its cmdline is distinct from
  # watch-lb-docker-build.sh; otherwise `pgrep -f watch-lb-docker-build.sh` overcounts.
  bash -c '
    hb="$1"
    sec="$2"
    exec 201>&- 202>&- || true
    while true; do
      date -Is >"${hb}.tmp" 2>/dev/null && mv -f "${hb}.tmp" "$hb" 2>/dev/null || true
      sleep "$sec"
    done
  ' _ "$hb" "$sec" &
  echo $!
}

alfred_stop_heartbeat() {
  local pid=$1
  [[ -n "$pid" ]] && kill "$pid" 2>/dev/null || true
}
