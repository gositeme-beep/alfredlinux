#!/usr/bin/env bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# Quick triage: active lb-docker container, dashboard JSON, log hints, ISO paths.
# Usage: bash scripts/check-lb-docker-status.sh [STATUS_JSON]
set -euo pipefail
REPO="$(cd "$(dirname "$0")/.." && pwd)"
NAME_FILE="$REPO/lb-docker.containername"
LOG="$REPO/lb-docker-build.log"
STATUS_JSON="${1:-${NAP_STATUS_JSON:-/home/gositeme/law/alfred-build-control-plane/last-lb-docker.json}}"

echo "=== check-lb-docker-status $(date -Is) ==="
echo "repo: $REPO"

LAW=/home/gositeme/law
for f in "$LAW/night-shift-state.txt" "$LAW/night-shift-DONE.txt" "$LAW/night-shift-FAIL.txt"; do
  if [[ -f "$f" ]]; then
    echo "--- $(basename "$f") (first line) ---"
    head -1 "$f" 2>/dev/null || true
  fi
done

if [[ -f "$NAME_FILE" ]]; then
  NAME="$(tr -d '\n' <"$NAME_FILE")"
  echo "lb-docker.containername: $NAME"
  if docker inspect "$NAME" &>/dev/null; then
    echo "docker: container RUNNING"
    docker ps --filter "name=^/${NAME}$" --format 'table {{.Names}}\t{{.Status}}\t{{.ID}}' 2>/dev/null || true
  else
    echo "docker: container not found (exited / --rm removed)"
  fi
else
  echo "lb-docker.containername: (missing — no recent detach?)"
fi

echo "--- watch lock (if present) ---"
if [[ -f "$REPO/.lb-docker-watch.lock" ]]; then
  ls -la "$REPO/.lb-docker-watch.lock" 2>/dev/null || true
  command -v fuser &>/dev/null && fuser "$REPO/.lb-docker-watch.lock" 2>/dev/null || true
fi

echo "--- build lock (exclusive live-build; lb-docker-inner-build flock) ---"
if [[ -f "$REPO/build/.alfred-lb-docker-build.lock" ]]; then
  ls -la "$REPO/build/.alfred-lb-docker-build.lock" 2>/dev/null || true
  command -v fuser &>/dev/null && fuser "$REPO/build/.alfred-lb-docker-build.lock" 2>/dev/null || true
fi

if [[ -f "$STATUS_JSON" ]]; then
  echo "--- $STATUS_JSON ---"
  cat "$STATUS_JSON"
  echo
else
  echo "--- $STATUS_JSON (missing) ---"
fi

echo "--- ISOs under build/ iso-output/ (max depth 5) ---"
find "$REPO/build" "$REPO/iso-output" -maxdepth 5 -name '*.iso' -type f 2>/dev/null | head -20 || true

echo "--- log: inner finished? (grep) ---"
if [[ -f "$LOG" ]]; then
  grep -E '\[inner\].*lb build finished|FATAL|E: |binary_syslinux' "$LOG" 2>/dev/null | tail -25 || true
  echo "--- log tail ---"
  tail -12 "$LOG" 2>/dev/null || true
else
  echo "(no $LOG)"
fi

echo "=== done ==="
