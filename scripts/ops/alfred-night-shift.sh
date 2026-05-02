#!/bin/bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# alfred-night-shift.sh v2 — resilient overnight pipeline with auto-requeue.
#
# Pipeline per attempt:
#   0. Wait for container name file (or queue a new build if none)
#   1. docker wait via watch-lb-docker-build.sh; then if lb-docker-build.log tail
#      contains "E: An unexpected failure occurred", write night-shift-FAIL.txt and
#      abort this attempt (even when docker wait / JSON are wrong).
#   2. Validate ISO is fresh + has /etc/alfred (smoke-test)
#   3. Restage canonical filenames + GPG sign
#
# On failure:
#   - Check disk + retry budget
#   - Requeue a fresh build via ABCP ctl.py
#   - Wait for new container, loop back to step 1
#   - Max MAX_RETRIES attempts beyond the in-flight build
#
# Stops on success OR on un-recoverable failure (no disk, ABCP down, etc).

set -u

LAW=/home/gositeme/law
SL=$LAW/alfredlinux-com-source-live
ABCP=$LAW/alfred-build-control-plane
WATCH=$SL/scripts/watch-lb-docker-build.sh
FINALIZE_NAP=$SL/scripts/ops/alfred-finalize-nap-json.sh
NAME_FILE=$SL/lb-docker.containername
STATUS_JSON=$ABCP/last-lb-docker.json
SMOKE="${ALFRED_SMOKE_SCRIPT:-$SL/scripts/ops/smoke-test-iso.sh}"
RESTAGE="${ALFRED_RESTAGE_SCRIPT:-$SL/scripts/ops/post-build-restage.sh}"
ISO=$SL/iso-output/live-image-amd64.hybrid.iso
EVENT_WRITER=$SL/scripts/ops/write-ops-event.sh

LOGDIR=$LAW/night-shift-logs
STATE=$LAW/night-shift-state.txt
DONE_MARKER=$LAW/night-shift-DONE.txt
FAIL_MARKER=$LAW/night-shift-FAIL.txt
ATTEMPT_FILE=$LAW/night-shift-attempt.txt

ABCP_BASE=http://127.0.0.1:18787
# Never commit tokens: export ABCP_TOKEN=… or one-line file $LAW/.alfred-abcp-token (chmod 600).
ABCP_TOKEN="${ABCP_TOKEN:-}"
if [[ -z "$ABCP_TOKEN" && -f "$LAW/.alfred-abcp-token" ]]; then
  ABCP_TOKEN="$(tr -d ' \n\r' <"$LAW/.alfred-abcp-token")"
fi
MAX_RETRIES=2          # extra builds beyond the one already running
MIN_DISK_GB=40         # need ~30GB chroot + binary headroom
# ISO mtime must be >= THRESHOLD. Default: rolling window (override with ALFRED_ISO_MIN_MTIME_EPOCH=…).
if [[ -n "${ALFRED_ISO_MIN_MTIME_EPOCH:-}" ]]; then
  THRESHOLD=$((ALFRED_ISO_MIN_MTIME_EPOCH))
else
  : "${ALFRED_ISO_MAX_AGE_DAYS:=21}"
  THRESHOLD=$(( $(date +%s) - ALFRED_ISO_MAX_AGE_DAYS * 86400 ))
fi

# shellcheck disable=SC1091
source "$SL/scripts/lb-nap-helpers.sh"
: "${ALFRED_DOCKER_INSPECT_TIMEOUT_SEC:=30}"

mkdir -p "$LOGDIR"
LOG="$LOGDIR/night-$(date +%Y%m%d-%H%M%S).log"
exec > >(tee -a "$LOG") 2>&1

log()   { echo "[night $(date -Is)] $*"; }
state() { echo "$(date -Is) $*" > "$STATE"; }
fail()  { echo "FAIL at $(date -Is): $*. See $LOG" > "$FAIL_MARKER"; state "FAIL: $*"; log "FAIL: $*"; exit "${2:-1}"; }

emit_ops_event() {
  local event="$1" reason="${2:-}" rc="${3:-}" cname="${4:-}"
  local phase=""
  if [[ -f "$STATUS_JSON" ]]; then
    phase="$(python3 - <<'PY2' "$STATUS_JSON"
import json, sys
try:
    print(json.load(open(sys.argv[1])).get('phase', ''))
except Exception:
    print('')
PY2
)"
  fi
  if [[ -z "$cname" && -s "$NAME_FILE" ]]; then
    cname="$(tr -d '\n\r' < "$NAME_FILE")"
  fi
  if [[ -x "$EVENT_WRITER" ]]; then
    "$EVENT_WRITER" --source alfred-night-shift --event "$event" --reason "$reason" --attempt "$ATTEMPT" --rc "$rc" --container "$cname" --phase "$phase" || true
  fi
}

# Human hint for Dell Watch / operators (night-shift maps all smoke failures to rc=6).
alfred_night_rc_hint() {
  case "${1:-}" in
    2) echo "no lb-docker.containername after wait window" ;;
    3) echo "watcher did not write status JSON" ;;
    4) echo "law hybrid ISO missing at iso-output path" ;;
    5) echo "ISO mtime below rolling THRESHOLD — stale file on disk vs new inner build" ;;
    6) echo "smoke-test-iso.sh failed — see === A–G === in log; common: iso-output/live-image-amd64.hybrid.iso not refreshed after inner lb (old mtime + MISS etc/alfred); fix outer publish path then re-run" ;;
    7) echo "post-build-restage.sh failed" ;;
    8) echo "lb-docker-build.log has fatal E: in current inner slice" ;;
    9) echo watch-lb-docker-build.sh failed ;;
    12) echo build-gates.sh failed - see gates-a*.json and gate_failed events ;;
    *) echo "see log above (rc=$1)" ;;
  esac
}

# If lb-docker-build.log already contains live-build's terminal E: line for the **current** inner run,
# write FAIL_MARKER even when docker wait / watch mis-reports. Scope to log lines after the last
# "[inner] lb build starting" so a previous run's E: in the same file does not block retries forever.
write_fail_if_log_fatal() {
  local cname="$1"
  local log="$SL/lb-docker-build.log"
  local slice=""
  [[ -f "$log" ]] || return 1
  if grep -Fq '[inner] lb build starting' "$log" 2>/dev/null; then
    slice=$(tac "$log" 2>/dev/null | sed '/\[inner\] lb build starting/q' | tac)
  else
    slice=$(tail -400 "$log" 2>/dev/null)
  fi
  [[ -n "$slice" ]] || return 1
  if ! printf '%s\n' "$slice" | grep -Fq 'E: An unexpected failure occurred'; then
    return 1
  fi
  {
    echo "FAIL at $(date -Is) — live-build reported: E: An unexpected failure occurred"
    echo "attempt #$ATTEMPT container $cname"
    echo "Detected in lb-docker-build.log after last [inner] lb build starting (docker wait / watch may have mis-reported)."
    echo
    echo "=== last 60 lines of lb-docker-build.log ==="
    tail -60 "$log" 2>/dev/null
  } > "$FAIL_MARKER"
  log "wrote $FAIL_MARKER — fatal E: in current inner-run slice of lb-docker-build.log"
  return 0
}

extract_inner_failure_snapshot() {
  local reason="${1:-unknown}" out="$LOGDIR/inner-failure-a${ATTEMPT}.log"
  local tool="$SL/scripts/ops/extract-inner-failure.sh"
  if [[ ! -x "$tool" ]]; then
    return 0
  fi
  "$tool" --log "$SL/lb-docker-build.log" --out "$out" --reason "$reason" >/dev/null 2>&1 || true
  log "inner failure snapshot: $out (reason=$reason)"
}

cleanup_stale_watchers() {
  local pidlist="" count=0
  pidlist="$(pgrep -f "$WATCH --status-json $STATUS_JSON" 2>/dev/null || true)"
  count="$(printf '%s\n' "$pidlist" | sed '/^$/d' | wc -l | tr -d ' ' )"
  if [[ "$count" =~ ^[0-9]+$ ]] && (( count > 0 )); then
    log "found $count stale watch-lb-docker-build.sh process(es); terminating before new watch"
    while IFS= read -r pid; do
      [[ -n "$pid" ]] || continue
      sudo kill "$pid" 2>/dev/null || true
    done <<< "$pidlist"
    sleep 1
  fi
}

capture_attempt_diagnostics() {
  local reason="${1:-unknown}" rc="${2:-unknown}"
  local out="$LOGDIR/attempt-diagnostics-a${ATTEMPT}.log"
  local cname="" docker_running="unknown" docker_exit="unknown"
  [[ -s "$NAME_FILE" ]] && cname="$(tr -d '\n\r' < "$NAME_FILE")"
  if [[ -n "$cname" ]]; then
    docker_running="$(alfred_docker_inspect_fmt "$cname" "{{.State.Running}}" | tr -d '\n')"
    docker_exit="$(alfred_docker_inspect_fmt "$cname" "{{.State.ExitCode}}" | tr -d '\n')"
    [[ -n "$docker_running" ]] || docker_running="unknown"
    [[ -n "$docker_exit" ]] || docker_exit="unknown"
  fi
  {
    echo "=== attempt diagnostics ==="
    echo "ts: $(date -Is)"
    echo "attempt: $ATTEMPT"
    echo "reason: $reason"
    echo "rc: $rc"
    echo "container: ${cname:-none}"
    echo "docker_running: $docker_running"
    echo "docker_exit: $docker_exit"
    echo "status_json: $STATUS_JSON"
    if [[ -f "$STATUS_JSON" ]]; then
      cat "$STATUS_JSON"
    else
      echo "(missing)"
    fi
    echo
    echo "=== system snapshot ==="
    df -h /home 2>/dev/null || true
    free -h 2>/dev/null || true
    echo
    echo "=== build log tail (last 120) ==="
    tail -120 "$SL/lb-docker-build.log" 2>/dev/null || true
    echo
    echo "=== smoke log tail ==="
    tail -120 "$LOGDIR/smoke-a${ATTEMPT}.log" 2>/dev/null || echo "(none)"
    echo
    echo "=== restage log tail ==="
    tail -120 "$LOGDIR/restage-a${ATTEMPT}.log" 2>/dev/null || echo "(none)"
    echo
    echo "=== watcher/finalize tails ==="
    tail -120 "$LOGDIR/finalize-nap-a${ATTEMPT}.log" 2>/dev/null || echo "(none)"
  } > "$out"
  log "attempt diagnostics: $out (reason=$reason rc=$rc)"
}

trap 'log "received signal — exiting"; state "EXITED on signal"; exit 130' INT TERM

# Track attempt across systemd restarts (if Restart= ever flipped to on-failure)
[[ -f "$ATTEMPT_FILE" ]] || echo 0 > "$ATTEMPT_FILE"
ATTEMPT=$(cat "$ATTEMPT_FILE")
# If the counter drifted high (manual SIGTERM, operator edits), the next +1 would exceed the retry
# cap before any real work — clamp so a fresh night-shift run has budget left.
if (( ATTEMPT > MAX_RETRIES + 1 )); then
  log "night-shift-attempt.txt was $ATTEMPT — clamping to 0 for a usable retry budget (MAX_RETRIES=$MAX_RETRIES)"
  echo 0 > "$ATTEMPT_FILE"
  ATTEMPT=0
fi

log "=== Alfred night shift v2 starting (attempt #$ATTEMPT) ==="
log "watch:   $WATCH"
log "smoke:   $SMOKE"
log "restage: $RESTAGE"
log "max retries beyond in-flight: $MAX_RETRIES"

# Clear stale first-line FAIL from a prior exited run (same file is often root:root).
state "STARTING night-shift v2 at $(date -Is) — main loop (dashboards: ignore prior FAIL until this line is replaced)"

check_disk() {
  local avail_gb
  avail_gb=$(df -BG --output=avail /home | tail -1 | tr -dc '0-9')
  if (( avail_gb < MIN_DISK_GB )); then
    log "DISK LOW: $avail_gb GB free (< $MIN_DISK_GB)"
    return 1
  fi
  log "disk OK: $avail_gb GB free"
  return 0
}

queue_new_build() {
  if [[ -z "$ABCP_TOKEN" ]]; then
    log "ABCP_TOKEN not set and $LAW/.alfred-abcp-token missing — cannot auto-requeue"
    return 3
  fi
  log "requeueing new build via ABCP"
  cd "$ABCP" || return 2
  local note resp bid py_rc tries max_tries sleep_sec
  note="auto_requeue_attempt$((ATTEMPT+1))_$(date +%s)"
  max_tries="${ALFRED_ABCP_QUEUE_RETRIES:-8}"
  sleep_sec="${ALFRED_ABCP_QUEUE_RETRY_SLEEP_SEC:-4}"
  resp=""
  py_rc=1
  tries=0
  while (( tries < max_tries )); do
    tries=$((tries + 1))
    set +e
    resp=$(python3 ctl.py --base "$ABCP_BASE" queue-build --token "$ABCP_TOKEN" --profile iso-docker --note "$note" 2>&1)
    py_rc=$?
    set -e
    if [[ "$py_rc" -eq 0 ]]; then
      break
    fi
    if echo "$resp" | grep -qiE 'Connection refused|ConnectionRefusedError|URLError|Failed to establish|timed out|Temporary failure in name resolution'; then
      log "ABCP queue-build attempt $tries/$max_tries transient error to $ABCP_BASE (rc=$py_rc); retry in ${sleep_sec}s — ${resp:0:240}"
      (( tries < max_tries )) || { log "ctl.py exhausted retries ($max_tries): $resp"; return 3; }
      sleep "$sleep_sec"
      continue
    fi
    log "ctl.py failed (non-retryable, rc=$py_rc): $resp"
    return 3
  done
  log "queue response: $resp"
  bid=$(echo "$resp" | python3 -c 'import sys,json; print(json.load(sys.stdin).get("build_id",""))' 2>/dev/null)
  [[ -n "$bid" ]] || { log "no build_id in response"; return 4; }
  log "queued build #$bid — waiting for NEW lb-docker.containername (worker must overwrite file)"
  state "REQUEUED build #$bid (attempt #$((ATTEMPT+1)))"

  local prior=""
  [[ -f "$NAME_FILE" ]] && prior=$(tr -d '\n\r' <"$NAME_FILE")
  # Do not rm -f "$NAME_FILE" here — it creates a race where a manual `lb-docker-build.sh detach`
  # sees a missing file. Wait until ABCP writes a *different* container name that exists in Docker.

  local waits=0 newname=""
  while (( waits < 30 )); do
    sleep 10
    waits=$((waits+1))
    if [[ -s "$NAME_FILE" ]]; then
      newname=$(tr -d '\n\r' <"$NAME_FILE")
      if [[ -n "$newname" && "$newname" != "$prior" ]] && sudo timeout "$ALFRED_DOCKER_INSPECT_TIMEOUT_SEC" docker inspect "$newname" &>/dev/null; then
        log "new container: $newname (prior was ${prior:-empty})"
        return 0
      fi
      if [[ -n "$newname" && "$newname" != "$prior" ]]; then
        log "name file -> $newname but docker not ready yet (wait=$waits)"
      fi
    fi
  done
  log "no new container spawned within 5 min"; return 5
}

run_one_attempt() {
  # --- Wait for container name file ---
  state "WAITING for container name file (attempt #$ATTEMPT)"
  local waits=0
  while [[ ! -s "$NAME_FILE" ]] && (( waits < 60 )); do
    log "no $NAME_FILE yet (wait=$waits)"
    sleep 30
    waits=$((waits+1))
  done
  [[ -s "$NAME_FILE" ]] || { log "no container name file after 30 min"; return 2; }

  local cname; cname=$(tr -d '\n' < "$NAME_FILE")
  log "container: $cname"
  # Clear stale FAIL from a prior attempt so Dell Watch is not stuck on FAILED while this run proceeds.
  rm -f "$FAIL_MARKER"
  state "WATCHING container $cname (attempt #$ATTEMPT)"

  # --- docker wait via existing watcher ---
  cleanup_stale_watchers
  log "running watch-lb-docker-build.sh"
  # Keep the watcher lock enabled so duplicate night-shift/watch invocations cannot race JSON updates.
  set +e
  env ALFRED_NO_INHIBIT_SLEEP=1 ALFRED_WATCH_LOCK_STRICT=1 \
    bash "$WATCH" --status-json "$STATUS_JSON"
  local watch_rc=$?
  set -e
  log "watch-lb-docker-build.sh exit=$watch_rc"

  if write_fail_if_log_fatal "$cname"; then
    extract_inner_failure_snapshot "fatal_E_after_watch"
    log "live-build fatal in log after watch — aborting this attempt (ISO/smoke skipped)"
    return 8
  fi

  # `docker wait` can wedge after `--rm` removes the container; watch may also be SIGTERM'd. If the
  # container is already gone, the law hybrid is fresh, and the log slice has no fatal E:, re-run
  # watch once — it exits 0 on unknown+disk ISO (see watch-lb-docker-build.sh).
  if [[ "$watch_rc" -ne 0 ]]; then
    if ! alfred_docker_inspect_ok "$cname" && [[ -f "$ISO" ]]; then
      local im; im=$(stat -Lc %Y "$ISO")
      if (( im >= THRESHOLD )); then
        log "watch rc=$watch_rc but container $cname missing and ISO fresh — re-running watch to finalize JSON + exit code"
        set +e
        env ALFRED_NO_INHIBIT_SLEEP=1 ALFRED_WATCH_LOCK_STRICT=1 \
          bash "$WATCH" --status-json "$STATUS_JSON"
        watch_rc=$?
        set -e
        log "watch-lb-docker-build.sh (container-gone recovery) exit=$watch_rc"
      fi
    fi
  fi

  # watch can exit 0 yet gositeme could not rewrite JSON; or watch failed while inner lb + ISO are
  # already good. Try finalize (no-op if phase=done; refuses if container still running).
  local nap_incomplete=0
  if [[ ! -f "$STATUS_JSON" ]]; then
    nap_incomplete=1
  elif python3 -c "import json,sys;d=json.load(open(sys.argv[1]));sys.exit(0 if d.get('phase')!='done' else 1)" "$STATUS_JSON" 2>/dev/null; then
    nap_incomplete=1
  fi
  if [[ "$nap_incomplete" == "1" && -f "$FINALIZE_NAP" ]]; then
    log "nap JSON missing or not phase=done after watch (rc=$watch_rc) — running $FINALIZE_NAP"
    set +e
    bash "$FINALIZE_NAP" >>"$LOGDIR/finalize-nap-a${ATTEMPT}.log" 2>&1
    local fz=$?
    set -e
    log "alfred-finalize-nap-json.sh exit=$fz (see $LOGDIR/finalize-nap-a${ATTEMPT}.log)"
    if [[ "$fz" -eq 0 ]]; then
      watch_rc=0
      log "nap JSON recovered — continuing pipeline"
    fi
  fi

  if [[ "$watch_rc" -ne 0 ]]; then
    log "watch-lb-docker-build.sh failed (rc=$watch_rc) — skipping ISO/smoke/restage this attempt"
    return 9
  fi

  [[ -f "$STATUS_JSON" ]] || { log "watcher didn't write status JSON"; return 3; }
  log "status JSON:"; cat "$STATUS_JSON"

  # --- Validate ISO ---
  if [[ ! -f "$ISO" ]]; then
    log "no ISO at $ISO"
    {
      echo "=== build log tail (last 80 lines) ==="
      sudo tail -80 "$SL/lb-docker-build.log" 2>/dev/null
    } > "$LOGDIR/build-tail-attempt$ATTEMPT.log"
    return 4
  fi
  local mtime; mtime=$(stat -Lc %Y "$ISO")
  if (( mtime < THRESHOLD )); then
    log "ISO is stale (mtime $(date -d @"$mtime"))"
    return 5
  fi
  log "ISO fresh: $(ls -Lh "$ISO" | awk '{print $5, $9}')"

  # --- Smoke test ---
  state "SMOKE-TESTING (attempt #$ATTEMPT)"
  if ! bash "$SMOKE" > >(tee "$LOGDIR/smoke-a${ATTEMPT}.log") 2>&1; then
    log "smoke-test failed (see $LOGDIR/smoke-a${ATTEMPT}.log)"; return 6
  fi
  log "smoke PASSED"

  # --- Restage + GPG sign ---
  state "RESTAGING + GPG sign (attempt #$ATTEMPT)"
  if ! bash "$SL/scripts/ops/build-gates.sh" enforce > >(tee "$LOGDIR/gates-a${ATTEMPT}.json") 2>&1; then
    log "gate check failed (see $LOGDIR/gates-a${ATTEMPT}.json)"; return 12
  fi
  if ! bash "$RESTAGE" > >(tee "$LOGDIR/restage-a${ATTEMPT}.log") 2>&1; then
    log "restage failed (see $LOGDIR/restage-a${ATTEMPT}.log)"; return 7
  fi
  log "restage PASSED"
  return 0
}

# === Main retry loop ===
while true; do
  ATTEMPT=$((ATTEMPT+1))
  echo "$ATTEMPT" > "$ATTEMPT_FILE"
  log "=== ATTEMPT #$ATTEMPT ==="
  emit_ops_event "attempt_start" "" "" ""

  if run_one_attempt; then
    # SUCCESS
    log "=== ATTEMPT #$ATTEMPT SUCCEEDED ==="
    emit_ops_event "attempt_succeeded" "" "0" ""
    {
      echo "DONE at $(date -Is) on attempt #$ATTEMPT"
      echo "ISO:    $ISO"
      echo "Sha256: $(sha256sum $ISO | awk '{print $1}')"
      echo "Size:   $(stat -c %s $ISO) bytes"
      echo "Log:    $LOG"
      echo
      echo "MANUAL NEXT STEP:"
      echo "  Edit /home/gositeme/domains/alfredlinux.com/public_html/includes/ga-release-state.php"
      echo "  Set: \$finalGaIsoPublished = true;"
    } > "$DONE_MARKER"
    rm -f "$FAIL_MARKER" "$ATTEMPT_FILE"
    state "DONE on attempt #$ATTEMPT — awaiting manual publish flip"
    cat "$DONE_MARKER"
    exit 0
  else
    # Bash: $? after `if cmd; then … fi` without else is 0 when cmd fails — capture here.
    RC=$?
  fi
  log "attempt #$ATTEMPT FAILED rc=$RC"
  emit_ops_event "attempt_failed" "" "$RC" ""
  extract_inner_failure_snapshot "rc_$RC"
  capture_attempt_diagnostics "run_one_attempt_failed" "$RC"

  # Decide retry
  if (( ATTEMPT > MAX_RETRIES + 1 )); then
    log "exhausted retry budget ($MAX_RETRIES retries beyond initial)"
    emit_ops_event "retries_exhausted" "retry_budget" "$RC" ""
    {
      echo "FAIL at $(date -Is) — exhausted $MAX_RETRIES retries"
      echo "Last attempt rc=$RC ($(alfred_night_rc_hint "$RC"))"
      echo "Log: $LOG"
      echo
      echo "Diagnose: cat $LOGDIR/build-tail-attempt*.log"
      echo "Manual requeue: read token from $LAW/.alfred-abcp-token then: cd $ABCP && python3 ctl.py --base $ABCP_BASE queue-build --token \"\$ABCP_TOKEN\" --profile iso-docker --note manual_after_night_shift_failed"
    } > "$FAIL_MARKER"
    state "FAIL: exhausted retries"
    capture_attempt_diagnostics "exhausted_retries" "$RC"
    exit $RC
  fi

  if ! check_disk; then
    fail "disk space too low for retry — manual cleanup needed" 10
  fi

  log "requeueing for attempt #$((ATTEMPT+1))..."
  if ! queue_new_build; then
    emit_ops_event "queue_build_failed" "abcp_queue" "11" ""
    capture_attempt_diagnostics "queue_new_build_failed" "11"
    fail "could not requeue build via ABCP — ensure ABCP listens on $ABCP_BASE (see ALFRED_ABCP_QUEUE_RETRIES / ALFRED_ABCP_QUEUE_RETRY_SLEEP_SEC in scripts/ops/README.md)" 11
  fi
  log "new container spawned, looping back to wait/watch"
done
