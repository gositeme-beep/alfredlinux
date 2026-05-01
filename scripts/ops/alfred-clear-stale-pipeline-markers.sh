#!/usr/bin/env bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# Clear stale night-shift FAIL + refresh last-lb-docker.json when a NEW lb-docker
# container is already running (e.g. after manual `lb-docker-build.sh detach` while
# night-shift left night-shift-FAIL.txt from an exhausted-retry run).
#
# Usage:
#   bash scripts/ops/alfred-clear-stale-pipeline-markers.sh --yes
# Or:  ALFRED_PIPELINE_CLEAR_FORCE=1 bash scripts/ops/alfred-clear-stale-pipeline-markers.sh
#
# Does NOT stop containers or touch the chroot. Backs up FAIL before removal (see BACKUP_DIR).
set -euo pipefail

LAW=/home/gositeme/law
SL=$LAW/alfredlinux-com-source-live
NAME_FILE=$SL/lb-docker.containername
FAIL=$LAW/night-shift-FAIL.txt
STATE=$LAW/night-shift-state.txt
# night-shift-logs/ is often root-owned from systemd; default backup dir is user-writable.
BACKUP_DIR="${ALFRED_CLEAR_BACKUP_DIR:-$LAW/night-shift-marker-backups}"
STATUS_JSON="${ALFRED_STATUS_JSON:-$LAW/alfred-build-control-plane/last-lb-docker.json}"

if [[ "${1:-}" != "--yes" && "${ALFRED_PIPELINE_CLEAR_FORCE:-}" != "1" ]]; then
  echo "Refusing: pass --yes or set ALFRED_PIPELINE_CLEAR_FORCE=1" >&2
  echo "This removes $FAIL when the container in $NAME_FILE is Running." >&2
  exit 2
fi

[[ -f "$NAME_FILE" ]] || { echo "Missing $NAME_FILE"; exit 1; }
NAME="$(tr -d '\n\r' <"$NAME_FILE")"
[[ -n "$NAME" ]] || { echo "Empty container name"; exit 1; }

if ! docker inspect "$NAME" &>/dev/null; then
  echo "Container $NAME not found — not clearing markers (may be real failure)." >&2
  exit 3
fi
running="$(docker inspect -f '{{.State.Running}}' "$NAME" 2>/dev/null || echo false)"
if [[ "$running" != "true" ]]; then
  echo "Container $NAME exists but is not Running — not clearing markers." >&2
  exit 4
fi

mkdir -p "$BACKUP_DIR"
if [[ -f "$FAIL" ]]; then
  if cp -a "$FAIL" "$BACKUP_DIR/night-shift-FAIL.cleared-$(date +%s).txt" 2>/dev/null && rm -f "$FAIL" 2>/dev/null; then
    echo "Removed stale $FAIL (backup under $BACKUP_DIR/)"
  else
    echo "WARN: could not remove $FAIL (may be root-owned). Try: sudo rm -f $FAIL" >&2
    cp -a "$FAIL" "$BACKUP_DIR/night-shift-FAIL.attempt-$(date +%s).txt" 2>/dev/null || true
  fi
else
  echo "No $FAIL — skip remove"
fi

ts="$(date -Is)"
msg="$ts RECOVERED: container $NAME is Running — stale FAIL cleared; re-run night-shift or wait for next cycle."
if [[ -w "$STATE" ]] || [[ ! -e "$STATE" && -w "$(dirname "$STATE")" ]]; then
  printf '%s\n' "$msg" >"$STATE"
  echo "Wrote $STATE"
else
  echo "WARN: cannot write $STATE (often root-owned from systemd). Skipped state reset." >&2
  echo "  Fix: sudo tee $STATE <<<\"$msg\"   or   sudo chown gositeme:gositeme $STATE" >&2
fi

# shellcheck disable=SC1091
source "$SL/scripts/lb-nap-helpers.sh"
alfred_status_json_waiting "$STATUS_JSON" "$NAME"
echo "Refreshed $STATUS_JSON for container $NAME (waiting_for_container snapshot)"

echo "=== done. Optional: sudo systemctl restart alfred-night-shift ==="
