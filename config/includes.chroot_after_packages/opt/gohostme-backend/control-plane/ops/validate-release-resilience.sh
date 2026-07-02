#!/bin/bash
set -euo pipefail

OPS="/home/root/gohostme/control-plane/ops"
KEY_FILE="/home/root/.vault/control-api-key"
API_URL="https://127.0.0.1/control/billing/index.php"

fail() {
  echo "FAIL:$1"
  exit 1
}

pass() {
  echo "PASS:$1"
}

[[ -f "$KEY_FILE" ]] || fail "missing_control_api_key"
KEY="$(tr -d '\r\n' < "$KEY_FILE")"

[[ -f "$OPS/OFFSITE_LAST_SUCCESS.txt" ]] || fail "missing_offsite_success_marker"
if [[ -f "$OPS/OFFSITE_ALERT.txt" ]]; then
  fail "offsite_alert_present"
fi
pass "offsite_markers"

CONF=$(curl -ksS -X POST -H "Content-Type: application/json" -H "X-Control-Key: ${KEY}" -d '{"action":"migration_confidence"}' "$API_URL")

echo "$CONF" | grep -q '"ok":true' || fail "confidence_api_not_ok"
echo "$CONF" | grep -q '"readiness":"ready"' || fail "confidence_not_ready"
echo "$CONF" | grep -q '"offsite_external":true' || fail "offsite_not_external"
pass "confidence_policy"

if ! $OPS/native-cutover.sh --check-only >/tmp/native-cutover-check.out 2>&1; then
  cat /tmp/native-cutover-check.out
  fail "cutover_precheck_failed"
fi
pass "cutover_precheck"

$OPS/resilience-daily-report.sh >/tmp/resilience-report-check.out 2>&1 || {
  cat /tmp/resilience-report-check.out
  fail "daily_report_failed"
}
pass "daily_report"

echo "RELEASE_RESILIENCE_VALIDATION_OK"
