#!/bin/bash
set -euo pipefail

OPS_DIR="/home/root/gohostme/control-plane/ops"
REPORT_DIR="$OPS_DIR/reports"
NOTIFY_SCRIPT="$OPS_DIR/notify-alert.sh"
API_URL="https://127.0.0.1/control/billing/index.php"
KEY_FILE="/home/root/.vault/control-api-key"

mkdir -p "$REPORT_DIR"

ts="$(date +%Y%m%d-%H%M%S)"
out="$REPORT_DIR/resilience-$ts.json"
latest="$REPORT_DIR/latest-resilience.json"

if [[ ! -f "$KEY_FILE" ]]; then
  echo "missing control api key" >&2
  if [[ -x "$NOTIFY_SCRIPT" ]]; then
    "$NOTIFY_SCRIPT" "critical" "resilience-report" "control api key missing" >/dev/null 2>&1 || true
  fi
  exit 1
fi

KEY="$(tr -d '\r\n' < "$KEY_FILE")"

payload='{"action":"migration_confidence"}'
resp=$(curl -ksS -X POST -H "Content-Type: application/json" -H "X-Control-Key: ${KEY}" -d "$payload" "$API_URL")

echo "$resp" > "$out"
cp "$out" "$latest"

readiness=$(echo "$resp" | sed -n 's/.*"readiness":"\([^"]*\)".*/\1/p' | head -n 1)
score=$(echo "$resp" | sed -n 's/.*"score":\([0-9][0-9]*\).*/\1/p' | head -n 1)
level=$(echo "$resp" | sed -n 's/.*"level":\([0-9][0-9]*\).*/\1/p' | head -n 1)

if [[ -z "$readiness" ]]; then
  readiness="unknown"
fi
if [[ -z "$score" ]]; then
  score="0"
fi
if [[ -z "$level" ]]; then
  level="0"
fi

if [[ "$readiness" == "blocked" || "$score" -lt 85 ]]; then
  sev="warning"
  [[ "$readiness" == "blocked" ]] && sev="critical"
  if [[ -x "$NOTIFY_SCRIPT" ]]; then
    "$NOTIFY_SCRIPT" "$sev" "resilience-report" "daily score=$score level=$level readiness=$readiness" "report=$out" >/dev/null 2>&1 || true
  fi
fi

echo "RESILIENCE_DAILY_REPORT_OK:$out"
