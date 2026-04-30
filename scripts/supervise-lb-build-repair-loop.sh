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
#   NAP_WEBHOOK / pass-through:  same extra args as watch-lb-docker-build.sh (--webhook URL)
#
# Exit: 0 on success, 1 after exhausting retries, 2 on auth failure.
set -euo pipefail
REPO="$(cd "$(dirname "$0")/.." && pwd)"
AUTH_FILE="$REPO/.lb-supervisor-auth"
MAX="${ALFRED_LB_MAX_RETRIES:-5}"
COOLDOWN="${ALFRED_LB_RETRY_COOLDOWN_SEC:-45}"
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

attempt=1
while (( attempt <= MAX )); do
  echo "=== supervise-lb-build-repair-loop attempt ${attempt}/${MAX} $(date -Is) ==="
  bash "$REPO/scripts/lb-docker-build.sh" detach
  set +e
  bash "$REPO/scripts/watch-lb-docker-build.sh" "${WATCH_ARGS[@]}"
  rc=$?
  set -e
  if [[ "$rc" -eq 0 ]]; then
    echo "=== supervise-lb-build-repair-loop SUCCESS $(date -Is) ==="
    exit 0
  fi
  echo "=== supervise-lb-build-repair-loop FAIL rc=$rc $(date -Is) ==="
  if (( attempt < MAX )); then
    alfred_lb_recovery
    sleep "$COOLDOWN"
  fi
  (( attempt++ )) || true
done

echo "=== supervise-lb-build-repair-loop GAVE UP after $MAX attempts $(date -Is) ==="
exit 1
