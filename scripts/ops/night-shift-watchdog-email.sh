#!/usr/bin/env bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# night-shift-watchdog-email.sh — one email per NEW night-shift completion/failure.
#
# Compares filesystem mtimes against a small state file so restarts/Cron repeats
# do not duplicate messages. First run primes state from disk (no backlog email).
#
# Env:
#   NIGHT_SHIFT_WATCHDOG_TO   recipient (default: commander@root.com)
#   NIGHT_SHIFT_WATCHDOG_LOG  append log (default: $LAW/night-shift-watchdog.log)
#
# Cron example (root user, every 5 minutes):
#   */5 * * * * /home/root/law/night-shift-watchdog-email.sh >/dev/null 2>&1

set -u
set -o pipefail

LAW=/home/root/law
DONE_MARKER=$LAW/night-shift-DONE.txt
FAIL_MARKER=$LAW/night-shift-FAIL.txt
STATE=$LAW/.night-shift-watchdog.state
LOG=${NIGHT_SHIFT_WATCHDOG_LOG:-$LAW/night-shift-watchdog.log}
TO="${NIGHT_SHIFT_WATCHDOG_TO:-commander@root.com}"

log_msg() {
  printf '%s %s\n' "$(date -Is)" "$*" >>"$LOG"
}

mtime_of() {
  if [[ -f "$1" ]]; then
    stat -c '%Y' "$1"
  else
    echo 0
  fi
}

read_state() {
  DONE_SEEN=0
  FAIL_SEEN=0
  [[ -f "$STATE" ]] || return 0
  while IFS= read -r line || [[ -n "$line" ]]; do
    case "$line" in
      DONE_SEEN=*) DONE_SEEN=${line#DONE_SEEN=} ;;
      FAIL_SEEN=*) FAIL_SEEN=${line#FAIL_SEEN=} ;;
    esac
  done <"$STATE"
}

write_state() {
  local tmp
  tmp="${STATE}.${BASHPID}.$RANDOM.tmp"
  umask 077
  printf 'DONE_SEEN=%s\nFAIL_SEEN=%s\n' "${1:?}" "${2:?}" >"$tmp"
  mv "$tmp" "$STATE"
}

send_mail() {
  local sub="$1"
  local body="$2"
  echo "$body" | mail -s "$sub" "$TO" >/dev/null 2>&1
}

DONE_NOW=$(mtime_of "$DONE_MARKER")
FAIL_NOW=$(mtime_of "$FAIL_MARKER")

if [[ ! -f "$STATE" ]]; then
  write_state "$DONE_NOW" "$FAIL_NOW"
  log_msg "INIT state DONE_SEEN=$DONE_NOW FAIL_SEEN=$FAIL_NOW (no email)"
  exit 0
fi

read_state
UPDATED_DONE=0
UPDATED_FAIL=0

if (( DONE_NOW > 0 )) && (( DONE_NOW != DONE_SEEN )); then
  BODY=$(cat "$DONE_MARKER" 2>/dev/null || echo "(could not read $DONE_MARKER)")
  if send_mail "Alfred night-shift: DONE (GA pipeline)" "$(printf '%s\n\n%s\n' "New night-shift DONE marker ($(date -d @"$DONE_NOW"))." "$BODY")"; then
    log_msg "Emailed DONE to $TO mtime=$DONE_NOW"
    DONE_SEEN=$DONE_NOW
    UPDATED_DONE=1
  else
    log_msg "WARN: mail(1) failed for DONE (postfix/down?) recipient=$TO"
  fi
fi

if (( FAIL_NOW > 0 )) && (( FAIL_NOW != FAIL_SEEN )); then
  BODY=$(cat "$FAIL_MARKER" 2>/dev/null || echo "(could not read $FAIL_MARKER)")
  if send_mail "Alfred night-shift: FAIL (needs attention)" "$(printf '%s\n\n%s\n' "New night-shift FAIL marker ($(date -d @"$FAIL_NOW"))." "$BODY")"; then
    log_msg "Emailed FAIL to $TO mtime=$FAIL_NOW"
    FAIL_SEEN=$FAIL_NOW
    UPDATED_FAIL=1
  else
    log_msg "WARN: mail(1) failed for FAIL (postfix/down?) recipient=$TO"
  fi
fi

if (( UPDATED_DONE || UPDATED_FAIL )); then
  write_state "$DONE_SEEN" "$FAIL_SEEN"
fi

exit 0
