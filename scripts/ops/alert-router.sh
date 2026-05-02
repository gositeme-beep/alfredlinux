#!/usr/bin/env bash
set -euo pipefail
EVENT_JSON=/home/gositeme/law/alfred-build-control-plane/last-ops-event.json
STATUS_JSON=/home/gositeme/law/alfred-build-control-plane/last-lb-docker.json
STATE_DIR=/home/gositeme/law/alfred-build-control-plane/alert-router-state
WEBHOOK_URL="${WEBHOOK_URL:-}"
mkdir -p "$STATE_DIR"
[[ -f "$EVENT_JSON" ]] || exit 0

event=$(jq -r '.event // .type // "unknown"' "$EVENT_JSON" 2>/dev/null || echo unknown)
source=$(jq -r ' .source // "unknown"' "$EVENT_JSON" 2>/dev/null || echo unknown)
attempt=$(jq -r '.attempt // "unknown"' "$EVENT_JSON" 2>/dev/null || echo unknown)
phase=$(jq -r '.phase // "unknown"' "$STATUS_JSON" 2>/dev/null || echo unknown)
container=$(cat /home/gositeme/law/alfredlinux-com-source-live/lb-docker.containername 2>/dev/null || echo unknown)

# skip self-generated alert events to avoid alert recursion/noise
if [[ "$event" == "alert_dispatched" || "$source" == "alert-router" ]]; then
  exit 0
fi

severity=info
case "$event" in
  attempt_failed|retries_exhausted|phase_timeout_detected|phase_timeout_recovery_triggered|heartbeat_guard_stale_detected|heartbeat_guard_recovery_triggered) severity=critical ;;
  gate_failed|queue_build_failed) severity=warning ;;
esac

key="$STATE_DIR/${severity}-${event}.ts"
now=$(date +%s)
if [[ -f "$key" ]]; then
  last=$(cat "$key" 2>/dev/null || echo 0)
  if [[ "$last" =~ ^[0-9]+$ ]] && (( now - last < 900 )); then
    exit 0
  fi
fi
echo "$now" > "$key"

payload=$(jq -n --arg severity "$severity" --arg event "$event" --arg attempt "$attempt" --arg phase "$phase" --arg container "$container" '{severity:$severity,event:$event,attempt:$attempt,phase:$phase,container:$container}')
if [[ -n "$WEBHOOK_URL" ]]; then
  curl -fsS -X POST "$WEBHOOK_URL" -H "Content-Type: application/json" -d "$payload" >/dev/null || true
fi

/home/gositeme/law/alfredlinux-com-source-live/scripts/ops/write-ops-event.sh --source alert-router --event alert_dispatched --reason "severity=$severity event=$event" --attempt "$attempt" --container "$container" --phase "$phase" || true
