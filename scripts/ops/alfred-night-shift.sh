#!/bin/bash
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
NAME_FILE=$SL/lb-docker.containername
STATUS_JSON=$ABCP/last-lb-docker.json
SMOKE=$LAW/smoke-test-iso.sh
RESTAGE=$LAW/post-build-restage.sh
ISO=$SL/iso-output/live-image-amd64.hybrid.iso

LOGDIR=$LAW/night-shift-logs
STATE=$LAW/night-shift-state.txt
DONE_MARKER=$LAW/night-shift-DONE.txt
FAIL_MARKER=$LAW/night-shift-FAIL.txt
ATTEMPT_FILE=$LAW/night-shift-attempt.txt

ABCP_BASE=http://127.0.0.1:18787
ABCP_TOKEN=KGhXExKUmFcG3JRPhQpxNvZF5C1KyYIe7Nwi8lTOJHU
MAX_RETRIES=2          # extra builds beyond the one already running
MIN_DISK_GB=40         # need ~30GB chroot + binary headroom
THRESHOLD=$(date -d '2026-04-29 19:00:00' +%s)

mkdir -p "$LOGDIR"
LOG="$LOGDIR/night-$(date +%Y%m%d-%H%M%S).log"
exec > >(tee -a "$LOG") 2>&1

log()   { echo "[night $(date -Is)] $*"; }
state() { echo "$(date -Is) $*" > "$STATE"; }
fail()  { echo "FAIL at $(date -Is): $*. See $LOG" > "$FAIL_MARKER"; state "FAIL: $*"; log "FAIL: $*"; exit "${2:-1}"; }

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

trap 'log "received signal — exiting"; state "EXITED on signal"; exit 130' INT TERM

# Track attempt across systemd restarts (if Restart= ever flipped to on-failure)
[[ -f "$ATTEMPT_FILE" ]] || echo 0 > "$ATTEMPT_FILE"
ATTEMPT=$(cat "$ATTEMPT_FILE")

log "=== Alfred night shift v2 starting (attempt #$ATTEMPT) ==="
log "watch:   $WATCH"
log "smoke:   $SMOKE"
log "restage: $RESTAGE"
log "max retries beyond in-flight: $MAX_RETRIES"

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
  log "requeueing new build via ABCP"
  cd "$ABCP" || return 2
  local note resp bid
  note="auto_requeue_attempt$((ATTEMPT+1))_$(date +%s)"
  resp=$(python3 ctl.py --base "$ABCP_BASE" queue-build --token "$ABCP_TOKEN" --profile iso-docker --note "$note" 2>&1) || {
    log "ctl.py failed: $resp"; return 3
  }
  log "queue response: $resp"
  bid=$(echo "$resp" | python3 -c 'import sys,json; print(json.load(sys.stdin).get("build_id",""))' 2>/dev/null)
  [[ -n "$bid" ]] || { log "no build_id in response"; return 4; }
  log "queued build #$bid — waiting for container to spawn"
  state "REQUEUED build #$bid (attempt #$((ATTEMPT+1)))"

  # Wait up to 5 minutes for worker to pick up + container to start + name file to update
  rm -f "$NAME_FILE"
  local waits=0
  while (( waits < 30 )); do
    sleep 10
    waits=$((waits+1))
    if [[ -s "$NAME_FILE" ]]; then
      local newname
      newname=$(tr -d '\n' < "$NAME_FILE")
      if sudo docker inspect "$newname" &>/dev/null; then
        log "new container: $newname"
        return 0
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
  log "running watch-lb-docker-build.sh"
  # sudo clears env by default — pass flock bypass explicitly for gositeme.
  set +e
  sudo -u gositeme env ALFRED_NO_INHIBIT_SLEEP=1 ALFRED_WATCH_NO_FLOCK=1 \
    bash "$WATCH" --status-json "$STATUS_JSON"
  local watch_rc=$?
  set -e
  log "watch-lb-docker-build.sh exit=$watch_rc"

  if write_fail_if_log_fatal "$cname"; then
    log "live-build fatal in log after watch — aborting this attempt (ISO/smoke skipped)"
    return 8
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
  local mtime; mtime=$(stat -c %Y "$ISO")
  if (( mtime < THRESHOLD )); then
    log "ISO is stale (mtime $(date -d @$mtime))"
    return 5
  fi
  log "ISO fresh: $(ls -lh $ISO | awk '{print $5, $9}')"

  # --- Smoke test ---
  state "SMOKE-TESTING (attempt #$ATTEMPT)"
  if ! bash "$SMOKE"; then
    log "smoke-test failed"; return 6
  fi
  log "smoke PASSED"

  # --- Restage + GPG sign ---
  state "RESTAGING + GPG sign (attempt #$ATTEMPT)"
  if ! bash "$RESTAGE"; then
    log "restage failed"; return 7
  fi
  log "restage PASSED"
  return 0
}

# === Main retry loop ===
while true; do
  ATTEMPT=$((ATTEMPT+1))
  echo "$ATTEMPT" > "$ATTEMPT_FILE"
  log "=== ATTEMPT #$ATTEMPT ==="

  if run_one_attempt; then
    # SUCCESS
    log "=== ATTEMPT #$ATTEMPT SUCCEEDED ==="
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

  # Decide retry
  if (( ATTEMPT > MAX_RETRIES + 1 )); then
    log "exhausted retry budget ($MAX_RETRIES retries beyond initial)"
    {
      echo "FAIL at $(date -Is) — exhausted $MAX_RETRIES retries"
      echo "Last attempt rc=$RC"
      echo "Log: $LOG"
      echo
      echo "Diagnose: cat $LOGDIR/build-tail-attempt*.log"
      echo "Manual requeue: cd $ABCP && python3 ctl.py --base $ABCP_BASE queue-build --token $ABCP_TOKEN --profile iso-docker --note manual_after_night_shift_failed"
    } > "$FAIL_MARKER"
    state "FAIL: exhausted retries"
    exit $RC
  fi

  if ! check_disk; then
    fail "disk space too low for retry — manual cleanup needed" 10
  fi

  log "requeueing for attempt #$((ATTEMPT+1))..."
  if ! queue_new_build; then
    fail "could not requeue build via ABCP" 11
  fi
  log "new container spawned, looping back to wait/watch"
done
