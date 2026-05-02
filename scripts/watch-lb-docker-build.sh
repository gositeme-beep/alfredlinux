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
#   ALFRED_NO_INHIBIT_SLEEP=1  - skip inhibit.  ALFRED_NAP_HEARTBEAT_SEC=120 - heartbeat interval.
#   ALFRED_DOCKER_WAIT_MAX_SEC=<seconds> - wrap `docker wait` in `timeout` (GNU coreutils); on timeout
#   exit is treated as unknown so night-shift / JSON can fall back to log-based checks.
#   ALFRED_DOCKER_INSPECT_TIMEOUT_SEC=<seconds> - bound every `docker inspect` (default 30); avoids
#   multi-day hangs when the Docker daemon is wedged (last-lb-docker.json stuck at waiting_for_container).
#   ALFRED_DOCKER_WAIT_EMPTY_RETRIES=<n> - if `docker wait` returns empty while the container is still
#   Running, retry up to n times (default 600, ~2s apart) before giving up.
# With --status-json: writes phase=waiting immediately, then .lb-docker-watch.heartbeat timestamps.
# One writer at a time for --status-json: flock on .lb-docker-watch.lock.
#   ALFRED_WATCH_NO_FLOCK=1           - skip lock entirely.
#   ALFRED_WATCH_LOCK_WAIT_SEC=<n>   - wait for lock (default 600). Use 0 for old non-blocking (-n) behaviour.
#   ALFRED_WATCH_LOCK_STRICT=1       - exit 3 if wait expires instead of proceeding with a warning.
set -euo pipefail
REPO="$(cd "$(dirname "$0")/.." && pwd)"
# shellcheck disable=SC1091
source "$(cd "$(dirname "$0")" && pwd)/lb-nap-helpers.sh"

# `docker wait` sometimes prints nothing while the container is still Running (daemon/pipe flake).
# Retrying avoids supervise FALSE_SUCCESS: watch exited 0 on stale ISO + unknown, inner never finished.
alfred_lb_docker_wait_until_done() {
  local name="$1" ex="" tw_rc=0 max_empty="${ALFRED_DOCKER_WAIT_EMPTY_RETRIES:-600}" n=0 running="" ec=""
  while true; do
    # If the container was removed (--rm) or never existed, `docker wait` can block indefinitely on
    # some engines. Inspect first so night-shift does not hang at WATCHING after a finished build.
    if ! alfred_docker_inspect_ok "$name"; then
      echo "watch-lb-docker-build: container $name not in docker (removed or unknown) - skipping docker wait" >&2
      printf '%s' "unknown"
      return 0
    fi

    if [[ -n "${ALFRED_DOCKER_WAIT_MAX_SEC:-}" ]] && [[ "${ALFRED_DOCKER_WAIT_MAX_SEC}" =~ ^[0-9]+$ ]] && (( ALFRED_DOCKER_WAIT_MAX_SEC > 0 )); then
      ex="$(alfred_maybe_inhibit_exec timeout --signal=TERM --kill-after=60 "${ALFRED_DOCKER_WAIT_MAX_SEC}" \
        docker wait "$name" 2>/dev/null | tr -d '\n')"
      tw_rc=$?
      if (( tw_rc == 124 )) || (( tw_rc == 137 )); then
        echo "docker wait timed out after ${ALFRED_DOCKER_WAIT_MAX_SEC}s (timeout rc=$tw_rc) - treating docker_exit as unknown" >&2
        ex="unknown"
      fi
    else
      ex="$(alfred_maybe_inhibit_exec docker wait "$name" 2>/dev/null | tr -d '\n')"
    fi
    [[ -n "$ex" ]] || ex="unknown"
    if [[ "$ex" != "unknown" ]]; then
      printf '%s' "$ex"
      return 0
    fi

    if ! alfred_docker_inspect_ok "$name"; then
      printf '%s' "unknown"
      return 0
    fi
    running="$(alfred_docker_inspect_fmt "$name" '{{.State.Running}}')"
    [[ -z "$running" ]] && running="false"
    if [[ "$running" != "true" ]]; then
      ec="$(alfred_docker_inspect_fmt "$name" '{{.State.ExitCode}}' | tr -d '\n')"
      [[ -n "$ec" ]] || ec="unknown"
      printf '%s' "$ec"
      return 0
    fi

    n=$((n + 1))
    if (( n > max_empty )); then
      echo "watch-lb-docker-build: docker wait kept returning empty while $name is still running (${n} tries) - giving up" >&2
      printf '%s' "unknown"
      return 0
    fi
    echo "watch-lb-docker-build: docker wait empty while $name running - retry ${n}/${max_empty}" >&2
    sleep 2
  done
}

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

[[ -z "$WEBHOOK" && -n "${NAP_WEBHOOK:-}" ]] && WEBHOOK="$NAP_WEBHOOK"

[[ -f "$NAME_FILE" ]] || { echo "Missing $NAME_FILE - run lb-docker-build.sh detach first." >&2; exit 1; }
NAME="$(tr -d '\n\r' <"$NAME_FILE")"
[[ -n "$NAME" ]] || { echo "Empty container name in $NAME_FILE" >&2; exit 1; }

if [[ -n "$STATUS_JSON" && -z "${ALFRED_WATCH_NO_FLOCK:-}" ]]; then
  exec 201>>"$REPO/.lb-docker-watch.lock"
  wait_sec="${ALFRED_WATCH_LOCK_WAIT_SEC:-600}"
  if [[ "$wait_sec" =~ ^[0-9]+$ ]] && (( wait_sec == 0 )); then
    if ! flock -n 201; then
      echo "Another watch-lb-docker-build.sh holds $REPO/.lb-docker-watch.lock - exiting. Use ALFRED_WATCH_NO_FLOCK=1 to bypass." >&2
      exit 3
    fi
  else
    if ! flock -w "$wait_sec" 201; then
      echo "watch-lb-docker-build: flock wait (${wait_sec}s) expired on $REPO/.lb-docker-watch.lock" >&2
      if [[ "${ALFRED_WATCH_LOCK_STRICT:-}" == 1 ]]; then
        exit 3
      fi
      echo "watch-lb-docker-build: continuing without exclusive lock (ALFRED_WATCH_LOCK_STRICT=1 to require lock)" >&2
    fi
  fi
fi

EXIT="unknown"
HB_PID=""
cleanup_watch_heartbeat() { alfred_stop_heartbeat "$HB_PID"; }
trap cleanup_watch_heartbeat EXIT

if alfred_docker_inspect_ok "$NAME"; then
  if [[ -n "$STATUS_JSON" ]]; then
    progress_pct="$(alfred_progress_from_log "$LOG" | tr -d "\n\r")"
    alfred_status_json_waiting "$STATUS_JSON" "$NAME" "$progress_pct"
    HB_PID="$(alfred_start_heartbeat "$REPO/.lb-docker-watch.heartbeat")"
  fi
  echo "Waiting for Docker container: $NAME (docker wait)..."
  [[ -z "${ALFRED_NO_INHIBIT_SLEEP:-}" ]] && command -v systemd-inhibit &>/dev/null \
    && echo "(systemd-inhibit: blocking sleep until container exits - set ALFRED_NO_INHIBIT_SLEEP=1 to skip)"
  set +e
  EXIT="$(alfred_lb_docker_wait_until_done "$NAME")"
  set -e
  alfred_stop_heartbeat "$HB_PID"
  HB_PID=""
else
  echo "Container $NAME not found (already exited and removed?). Summarizing from disk..."
fi

ISO_LIST="$(mktemp)"
find "$REPO/build" "$REPO/iso-output" -maxdepth 5 -name '*.iso' -type f 2>/dev/null | head -50 >"$ISO_LIST" || true
ISO_COUNT="$(wc -l <"$ISO_LIST" | tr -d ' ')"
LOG_TAIL="$(tail -60 "$LOG" 2>/dev/null || echo '(no log)')"
progress_pct="$(alfred_progress_from_log "$LOG" | tr -d "\n\r")"

# Align nap_ok / JSON with final exit rules (do not claim nap_ok when we will exit 1 on stale ISO + unknown).
nap_ok_num=0
if [[ "$EXIT" == "0" ]]; then
  nap_ok_num=1
elif [[ "$EXIT" == "unknown" ]] && [[ "$ISO_COUNT" != "0" ]] && ! alfred_docker_inspect_ok "$NAME"; then
  nap_ok_num=1
fi

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
  python3 - "$STATUS_JSON" "$NAME" "$EXIT" "$ISO_LIST" "$nap_ok_num" "$progress_pct" <<'PY'
import json, sys, time
path, name, exit_s, iso_list, nap_s, progress_s = sys.argv[1:7]
with open(iso_list) as f:
    iso_paths = [ln.strip() for ln in f if ln.strip()]
nap_ok = nap_s == "1"
data = {
    "phase": "done",
    "ts": time.time(),
    "container": name,
    "docker_exit": exit_s,
    "iso_paths": iso_paths,
    "iso_count": len(iso_paths),
    "nap_ok": nap_ok,
}
if str(progress_s).strip():
    try:
        data["progress_pct"] = max(1, min(99, int(float(progress_s))))
    except Exception:
        pass
with open(path, "w") as out:
    json.dump(data, out, indent=2)
print("Wrote", path, "nap_ok=", nap_ok)
PY
fi
if [[ -x "$ROOT/scripts/ops/update-public-status-and-media.sh" ]]; then
  # Refresh public-facing status page to keep it in sync with the authoritative watcher JSON.
  "$ROOT/scripts/ops/update-public-status-and-media.sh" 2>&1 | grep -E "^\[public-update|ERROR|error" || true
fi

if [[ -n "$WEBHOOK" ]]; then
  BODY=$(python3 -c "import json,time; n=int(${ISO_COUNT}); ex='${EXIT}'; ok=int(${nap_ok_num}); print(json.dumps({'ts':time.time(),'container':'${NAME}','docker_exit':ex,'iso_count':n,'nap_ok': bool(ok)}))")
  curl -sS -X POST -H 'Content-Type: application/json' -d "$BODY" "$WEBHOOK" -o /dev/null -w 'webhook_http:%{http_code}\n' || echo "webhook: curl failed"
fi

rm -f "$ISO_LIST"

# Success: clean container exit only, or unknown + ISO only when container is already gone (--rm race).
# Do not exit 0 on stale ISO while a failed run left docker_exit non-zero (supervise would FALSE_SUCCESS).
if [[ "$EXIT" == "0" ]]; then
  exit 0
fi
if [[ "$EXIT" == "unknown" ]] && [[ "$ISO_COUNT" != "0" ]] && ! alfred_docker_inspect_ok "$NAME"; then
  exit 0
fi
exit 1
