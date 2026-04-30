#!/usr/bin/env bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# Wait for detached `lb-docker-build.sh` container to finish; summarize + optional webhook/status JSON.
#
#   bash scripts/watch-lb-docker-build.sh
#   bash scripts/watch-lb-docker-build.sh --webhook https://example.com/hook
#   bash scripts/watch-lb-docker-build.sh --status-json /path/to/last-lb-docker.json
#
# Reads container name from lb-docker.containername in repo root (written by lb-docker-build.sh detach).
#
# Before sleep: uses systemd-inhibit (when installed) so the host does not suspend during docker wait.
#   ALFRED_NO_INHIBIT_SLEEP=1  — skip inhibit.  ALFRED_NAP_HEARTBEAT_SEC=120 — heartbeat interval.
#   ALFRED_DOCKER_WAIT_MAX_SEC=<seconds> — wrap `docker wait` in `timeout` (GNU coreutils); on timeout
#   exit is treated as unknown so night-shift / JSON can fall back to log-based checks.
# With --status-json: writes phase=waiting immediately, then .lb-docker-watch.heartbeat timestamps.
# One writer at a time: flock on .lb-docker-watch.lock (ALFRED_WATCH_NO_FLOCK=1 to bypass).
set -euo pipefail
REPO="$(cd "$(dirname "$0")/.." && pwd)"
# shellcheck disable=SC1091
source "$(cd "$(dirname "$0")" && pwd)/lb-nap-helpers.sh"
NAME_FILE="$REPO/lb-docker.containername"
LOG="$REPO/lb-docker-build.log"
WEBHOOK=""
STATUS_JSON=""

while [[ $# -gt 0 ]]; do
  case "$1" in
    --webhook) WEBHOOK="${2:?}"; shift 2 ;;
    --status-json) STATUS_JSON="${2:?}"; shift 2 ;;
    *) echo "Unknown arg: $1" >&2; exit 2 ;;
  esac
done

# Same env as supervise-lb-docker-nap.sh so one export works for watch-after-detach.
[[ -z "$WEBHOOK" && -n "${NAP_WEBHOOK:-}" ]] && WEBHOOK="$NAP_WEBHOOK"

[[ -f "$NAME_FILE" ]] || { echo "Missing $NAME_FILE — run lb-docker-build.sh detach first." >&2; exit 1; }
NAME="$(tr -d '\n' <"$NAME_FILE")"
[[ -n "$NAME" ]] || { echo "Empty container name in $NAME_FILE" >&2; exit 1; }

if [[ -n "$STATUS_JSON" && -z "${ALFRED_WATCH_NO_FLOCK:-}" ]]; then
  exec 201>>"$REPO/.lb-docker-watch.lock"
  if ! flock -n 201; then
    echo "Another watch-lb-docker-build.sh holds $REPO/.lb-docker-watch.lock — exiting. Use ALFRED_WATCH_NO_FLOCK=1 to bypass." >&2
    exit 3
  fi
fi

EXIT="unknown"
HB_PID=""
cleanup_watch_heartbeat() { alfred_stop_heartbeat "$HB_PID"; }
trap cleanup_watch_heartbeat EXIT

if docker inspect "$NAME" &>/dev/null; then
  if [[ -n "$STATUS_JSON" ]]; then
    alfred_status_json_waiting "$STATUS_JSON" "$NAME"
    HB_PID="$(alfred_start_heartbeat "$REPO/.lb-docker-watch.heartbeat")"
  fi
  echo "Waiting for Docker container: $NAME (docker wait)…"
  [[ -z "${ALFRED_NO_INHIBIT_SLEEP:-}" ]] && command -v systemd-inhibit &>/dev/null \
    && echo "(systemd-inhibit: blocking sleep until container exits — set ALFRED_NO_INHIBIT_SLEEP=1 to skip)"
  set +e
  if [[ -n "${ALFRED_DOCKER_WAIT_MAX_SEC:-}" ]] && [[ "${ALFRED_DOCKER_WAIT_MAX_SEC}" =~ ^[0-9]+$ ]] && (( ALFRED_DOCKER_WAIT_MAX_SEC > 0 )); then
    EXIT="$(alfred_maybe_inhibit_exec timeout --signal=TERM --kill-after=60 "${ALFRED_DOCKER_WAIT_MAX_SEC}" \
      docker wait "$NAME" 2>/dev/null | tr -d '\n')"
    tw_rc=$?
    if (( tw_rc == 124 )) || (( tw_rc == 137 )); then
      echo "docker wait timed out after ${ALFRED_DOCKER_WAIT_MAX_SEC}s (timeout rc=$tw_rc) — treating docker_exit as unknown"
      EXIT="unknown"
    fi
  else
    EXIT="$(alfred_maybe_inhibit_exec docker wait "$NAME" 2>/dev/null | tr -d '\n')"
  fi
  [[ -n "$EXIT" ]] || EXIT="unknown"
  set -e
  alfred_stop_heartbeat "$HB_PID"
  HB_PID=""
else
  echo "Container $NAME not found (already exited and removed?). Summarizing from disk…"
fi

ISO_LIST="$(mktemp)"
find "$REPO/build" "$REPO/iso-output" -maxdepth 5 -name '*.iso' -type f 2>/dev/null | head -50 >"$ISO_LIST" || true
ISO_COUNT="$(wc -l <"$ISO_LIST" | tr -d ' ')"
LOG_TAIL="$(tail -60 "$LOG" 2>/dev/null || echo '(no log)')"

{
  echo "=== watch-lb-docker-build $(date -Is) ==="
  echo "container: $NAME"
  echo "docker_exit: $EXIT"
  echo "iso_count: $ISO_COUNT"
  if [[ "$ISO_COUNT" != "0" ]]; then echo "iso_paths:"; cat "$ISO_LIST"; fi
  echo "--- last 60 lines: $LOG ---"
  echo "$LOG_TAIL"
}

if [[ -n "$STATUS_JSON" ]]; then
  python3 - "$STATUS_JSON" "$NAME" "$EXIT" "$ISO_LIST" <<'PY'
import json, sys, time
path, name, exit_s, iso_list = sys.argv[1:5]
with open(iso_list) as f:
    iso_paths = [ln.strip() for ln in f if ln.strip()]
# docker wait often returns empty after --rm (unknown) even when lb build succeeded and wrote .iso
nap_ok = len(iso_paths) > 0 and exit_s in ("0", "unknown")
data = {
    "phase": "done",
    "ts": time.time(),
    "container": name,
    "docker_exit": exit_s,
    "iso_paths": iso_paths,
    "iso_count": len(iso_paths),
    "nap_ok": nap_ok,
}
with open(path, "w") as out:
    json.dump(data, out, indent=2)
print("Wrote", path, "nap_ok=", nap_ok)
PY
fi

if [[ -n "$WEBHOOK" ]]; then
  BODY=$(python3 -c "import json,time; n=int(${ISO_COUNT}); ex='${EXIT}'; print(json.dumps({'ts':time.time(),'container':'${NAME}','docker_exit':ex,'iso_count':n,'nap_ok':(n>0 and ex in ('0','unknown'))}))")
  curl -sS -X POST -H 'Content-Type: application/json' -d "$BODY" "$WEBHOOK" -o /dev/null -w 'webhook_http:%{http_code}\n' || echo "webhook: curl failed"
fi

rm -f "$ISO_LIST"

# Success: clean container exit, or we found an ISO even if `docker wait` missed (--rm race).
if [[ "$EXIT" == "0" ]] || [[ "$ISO_COUNT" != "0" ]]; then
  exit 0
fi
exit 1
