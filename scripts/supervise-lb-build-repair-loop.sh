#!/usr/bin/env bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# Authenticated supervisor: start detached lb Docker build, wait (watch-lb-docker-build),
# on failure run bounded host-side recovery, then retry up to N times.
#
# Auth (optional): create a one-line secret file (chmod 600), then export the same value.
#   openssl rand -hex 24 | tee .lb-supervisor-auth >/dev/null && chmod 600 .lb-supervisor-auth
#   export ALFRED_LB_SUPERVISOR_TOKEN="$(head -1 .lb-supervisor-auth)"
#   bash scripts/supervise-lb-build-repair-loop.sh
# If .lb-supervisor-auth is missing, token check is skipped (dev convenience only).
#
# Env:
#   ALFRED_LB_MAX_RETRIES        default 5
#   ALFRED_LB_RETRY_COOLDOWN_SEC default 45
#   ALFRED_LB_GIT_PULL=1         git pull origin main before each retry (after first failure)
#   ALFRED_LB_CLEAN_CHROOT=1      rm -rf build/chroot before retry (slow; last resort)
#   ALFRED_LB_LOOP_FOREVER       default 1 — after inner cycle exhausts MAX attempts, sleep
#                                ALFRED_LB_EXHAUST_COOLDOWN_SEC (default 900) and start a **new**
#                                full supervise cycle so nohup/systemd never sits idle after GAVE UP.
#                                Set to 0 for one-shot behaviour (exit 1 after exhaustion).
#   ALFRED_LB_EXHAUST_COOLDOWN_SEC  default 900 — pause between outer cycles when LOOP_FOREVER=1
#   ALFRED_LB_SUCCESS_WEBHOOK       optional URL — POST application/json {"text":"…"} (Slack incoming-style)
#   ALFRED_LB_SUCCESS_SCRIPT       optional path — executable; invoked as SCRIPT /path/to/repo on verified SUCCESS
#   NAP_WEBHOOK / pass-through:  same extra args as watch-lb-docker-build.sh (--webhook URL)
#   ALFRED_LB_DOCKER_FLOCK_BLOCKING  see lb-docker-build.sh (default 1 = queue on shared-repo lock).
#
# Exit: 0 on success; 2 on auth failure; with LOOP_FOREVER=0 only: 1 after exhausting retries.
set -euo pipefail
REPO="$(cd "$(dirname "$0")/.." && pwd)"
AUTH_FILE="$REPO/.lb-supervisor-auth"
MAX="${ALFRED_LB_MAX_RETRIES:-5}"
COOLDOWN="${ALFRED_LB_RETRY_COOLDOWN_SEC:-45}"
EXHAUST_SLEEP="${ALFRED_LB_EXHAUST_COOLDOWN_SEC:-900}"
# Default on: overnight / nohup must not stop forever after one "GAVE UP" (set ALFRED_LB_LOOP_FOREVER=0 to opt out).
LOOP_FOREVER="${ALFRED_LB_LOOP_FOREVER:-1}"
WATCH_ARGS=()

while [[ $# -gt 0 ]]; do
  case "$1" in
    --webhook) WATCH_ARGS+=(--webhook "${2:?}"); shift 2 ;;
    --status-json) WATCH_ARGS+=(--status-json "${2:?}"); shift 2 ;;
    *) echo "Unknown arg: $1 (supported: --webhook URL --status-json PATH)" >&2; exit 2 ;;
  esac
done

alfred_lb_check_auth() {
  if [[ -f "$AUTH_FILE" ]]; then
    local expected
    expected="$(head -1 "$AUTH_FILE" | tr -d '\r\n')"
    [[ -n "$expected" ]] || { echo "Refusing: $AUTH_FILE is empty" >&2; exit 2; }
    if [[ "${ALFRED_LB_SUPERVISOR_TOKEN:-}" != "$expected" ]]; then
      echo "Refusing: set ALFRED_LB_SUPERVISOR_TOKEN to match first line of $AUTH_FILE" >&2
      exit 2
    fi
  fi
}

alfred_lb_recovery() {
  local log="$REPO/lb-docker-build.log"
  echo "[recovery] $(date -Is) analyzing + host fixes…"
  if [[ -f "$log" ]]; then
    if tail -n 120 "$log" | grep -q 'exclusive build lock is busy'; then
      echo "[recovery] log mentions flock collision on build/.alfred-lb-docker-build.lock — only one build per repo; next detach uses blocking flock (lb-docker-build.sh defaults ALFRED_LB_DOCKER_FLOCK_BLOCKING=1)"
    fi
    if tail -n 120 "$log" | grep -q 'target is busy'; then
      echo "[recovery] log mentions target is busy — lazy-umount host paths if mounted"
      local _ch="$REPO/build/chroot"
      if [[ -d "$_ch" ]] && command -v mountpoint >/dev/null 2>&1; then
        for _m in "$_ch/run/shm" "$_ch/run" "$_ch/dev/pts" "$_ch/dev" "$_ch/proc" "$_ch/sys"; do
          if mountpoint -q "$_m" 2>/dev/null; then
            echo "[recovery] umount -l $_m"
            umount -l "$_m" 2>/dev/null || true
          fi
        done
      fi
    fi
  fi
  if [[ -d "$REPO/build/config/packages.chroot" ]]; then
    chmod -R a+rX "$REPO/build/config/packages.chroot" 2>/dev/null || true
    echo "[recovery] chmod a+rX build/config/packages.chroot"
  fi
  local name_file="$REPO/lb-docker.containername"
  if [[ -f "$name_file" ]]; then
    local cname
    cname="$(tr -d '\n' <"$name_file")"
    if [[ -n "$cname" ]] && ! docker inspect "$cname" &>/dev/null; then
      echo "[recovery] removing stale $name_file (container $cname gone)"
      rm -f "$name_file"
    fi
  fi
  if [[ "${ALFRED_LB_GIT_PULL:-}" == "1" ]]; then
    echo "[recovery] git pull origin main"
    ( cd "$REPO" && git pull origin main ) || true
  fi
  if [[ "${ALFRED_LB_CLEAN_CHROOT:-}" == "1" ]] && [[ -d "$REPO/build/chroot" ]]; then
    echo "[recovery] ALFRED_LB_CLEAN_CHROOT=1 — removing build/chroot"
    rm -rf "$REPO/build/chroot" 2>/dev/null || true
  fi
  echo "[recovery] done; sleeping ${COOLDOWN}s before retry"
}

alfred_lb_check_auth

# watch-lb-docker-build may exit 0 on unknown docker_exit only when the container is gone and an ISO
# exists (--rm race). Stale ISO + failed container exit is not success. Still require proof this attempt
# completed: marker in lb-docker-build.log plus inner success line after that marker.
alfred_lb_verify_inner_success() {
  local log="$REPO/lb-docker-build.log" sid="$1"
  [[ -f "$log" ]] || return 1
  awk -v s="=== SUPERVISE_ATTEMPT_BEGIN $sid" '
    index($0, s) { p = 1 }
    p && /\[inner\] lb build finished/ && /exit=0/ { ok = 1 }
    END { exit(ok ? 0 : 1) }
  ' "$log"
}

alfred_lb_notify_success() {
  local msg="Alfred live-build supervise SUCCESS at $(date -Is) repo=$REPO"
  echo "[notify-success] $msg"
  if [[ -n "${ALFRED_LB_SUCCESS_SCRIPT:-}" ]]; then
    if [[ -x "${ALFRED_LB_SUCCESS_SCRIPT}" ]]; then
      set +e
      "${ALFRED_LB_SUCCESS_SCRIPT}" "$REPO"
      local ns=$?
      set -e
      (( ns == 0 )) || echo "[notify-success] ALFRED_LB_SUCCESS_SCRIPT exit=$ns" >&2
    else
      echo "[notify-success] skip: not executable: ${ALFRED_LB_SUCCESS_SCRIPT}" >&2
    fi
  fi
  if [[ -n "${ALFRED_LB_SUCCESS_WEBHOOK:-}" ]]; then
    set +e
    local body
    body="$(python3 -c 'import json,sys; print(json.dumps({"text": sys.argv[1]}))' "$msg" 2>/dev/null)" || body=
    if [[ -z "$body" ]]; then
      echo "[notify-success] skip webhook: could not build JSON (python3?)" >&2
    else
      curl -fsS -m 25 -X POST -H 'Content-Type: application/json' -d "$body" "${ALFRED_LB_SUCCESS_WEBHOOK}" -o /dev/null \
        || echo "[notify-success] webhook POST failed" >&2
    fi
    set -e
  fi
}

alfred_lb_one_inner_cycle() {
  local attempt sid rc
  attempt=1
  while (( attempt <= MAX )); do
    echo "=== supervise-lb-build-repair-loop attempt ${attempt}/${MAX} $(date -Is) ==="
    SID="$(openssl rand -hex 8)"
    printf '\n=== SUPERVISE_ATTEMPT_BEGIN %s attempt=%s ===\n' "$SID" "$attempt" >>"$REPO/lb-docker-build.log"
    bash "$REPO/scripts/lb-docker-build.sh" detach
    set +e
    bash "$REPO/scripts/watch-lb-docker-build.sh" "${WATCH_ARGS[@]}"
    rc=$?
    set -e
    if [[ "$rc" -eq 0 ]] && alfred_lb_verify_inner_success "$SID"; then
      echo "=== supervise-lb-build-repair-loop SUCCESS $(date -Is) ==="
      alfred_lb_notify_success
      return 0
    fi
    if [[ "$rc" -eq 0 ]]; then
      echo "=== supervise-lb-build-repair-loop FALSE_SUCCESS (stale ISO or incomplete log) $(date -Is) — retrying ===" >&2
      rc=1
    fi
    echo "=== supervise-lb-build-repair-loop FAIL rc=$rc $(date -Is) ==="
    if (( attempt < MAX )); then
      alfred_lb_recovery
      sleep "$COOLDOWN"
    fi
    (( attempt++ )) || true
  done

  echo "=== supervise-lb-build-repair-loop GAVE UP after $MAX attempts $(date -Is) ==="
  return 1
}

if [[ "$LOOP_FOREVER" == "0" ]]; then
  alfred_lb_one_inner_cycle
  exit $?
fi

while true; do
  if alfred_lb_one_inner_cycle; then
    exit 0
  fi
  echo "=== supervise-lb-build-repair-loop outer: exhausted ${MAX} attempts; recovery then ${EXHAUST_SLEEP}s before new cycle (ALFRED_LB_LOOP_FOREVER=1) $(date -Is) ===" >&2
  alfred_lb_recovery || true
  sleep "$EXHAUST_SLEEP"
done
