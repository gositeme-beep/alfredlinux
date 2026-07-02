#!/bin/bash
set -euo pipefail

SEVERITY="${1:-info}"
COMPONENT="${2:-platform}"
MESSAGE="${3:-event}"
DETAILS="${4:-}"

WEBHOOK_FILE="/home/root/.vault/ops-alert-webhook"
WEBHOOK_FILE_SECONDARY="/home/root/.vault/ops-alert-webhook-secondary"
LOG_FILE="/home/root/gohostme/control-plane/ops/alerts.log"
STATE_FILE="/home/root/gohostme/control-plane/ops/alerts-state.tsv"

mkdir -p "$(dirname "$LOG_FILE")"

ts="$(date '+%Y-%m-%d %H:%M:%S')"
echo "[$ts] severity=$SEVERITY component=$COMPONENT message=$MESSAGE details=$DETAILS" >> "$LOG_FILE"

mkdir -p "$(dirname "$STATE_FILE")"
if [[ ! -f "$STATE_FILE" ]]; then
  touch "$STATE_FILE"
fi

critical_count=0
if [[ "$SEVERITY" == "critical" ]]; then
  current_count=$(awk -F'\t' -v c="$COMPONENT" '$1==c{print $2}' "$STATE_FILE" | tail -n 1)
  if [[ -z "${current_count:-}" ]]; then
    current_count=0
  fi
  critical_count=$((current_count + 1))
  awk -F'\t' -v c="$COMPONENT" '$1!=c{print $0}' "$STATE_FILE" > "$STATE_FILE.tmp" || true
  printf '%s\t%s\n' "$COMPONENT" "$critical_count" >> "$STATE_FILE.tmp"
  mv "$STATE_FILE.tmp" "$STATE_FILE"
else
  awk -F'\t' -v c="$COMPONENT" '$1!=c{print $0}' "$STATE_FILE" > "$STATE_FILE.tmp" || true
  mv "$STATE_FILE.tmp" "$STATE_FILE"
fi

HOST="$(hostname)"
ESCALATION="none"
if [[ "$SEVERITY" == "critical" && "$critical_count" -ge 3 ]]; then
  ESCALATION="page"
fi

JSON_PAYLOAD=$(printf '{"severity":"%s","component":"%s","message":"%s","details":"%s","host":"%s","timestamp":"%s","critical_count":%s,"escalation":"%s"}' \
  "$SEVERITY" "$COMPONENT" "$MESSAGE" "$DETAILS" "$HOST" "$ts" "$critical_count" "$ESCALATION")

send_webhook() {
  local wf="$1"
  [[ -f "$wf" ]] || return 0
  local url
  url="$(tr -d '\r\n' < "$wf")"
  [[ -n "$url" ]] || return 0
  curl -sS --max-time 10 -H "Content-Type: application/json" -X POST -d "$JSON_PAYLOAD" "$url" >/dev/null || true
}

send_webhook "$WEBHOOK_FILE"
send_webhook "$WEBHOOK_FILE_SECONDARY"
