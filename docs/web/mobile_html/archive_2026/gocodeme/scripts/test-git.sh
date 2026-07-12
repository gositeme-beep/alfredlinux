#!/usr/bin/env bash
# test-git.sh — Git auto-commit E2E smoke tests
#
# Tests the full chain:
#   1. Git init → checkpoint → log → status → diff → revert
#   2. Auto-commit fires after file write (debounced, 3s wait)
#
# Usage:
#   cd gocodeme && bash scripts/test-git.sh
#   TEST_JWT=<jwt> bash scripts/test-git.sh

set -uo pipefail

MW="http://localhost:3001"
PASS=0
FAIL=0
TOTAL=0

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
ENV_FILE="$SCRIPT_DIR/../middleware/.env"
if [[ -f "$ENV_FILE" ]]; then
  set -a; source "$ENV_FILE"; set +a
fi
WHMCS_SECRET="${WHMCS_WEBHOOK_SECRET:-dev-whmcs-secret}"
DA_USERNAME="gositeme"   # this user's home dir actually exists on this server

G='\033[0;32m'; R='\033[0;31m'; Y='\033[1;33m'; NC='\033[0m'

jv() { python3 -c "import sys,json; print(json.loads(sys.argv[2])${1})" "$1" "$2" 2>/dev/null || echo "__MISSING__"; }

assert() {
  local name="$1" got="$2" want="$3"
  TOTAL=$((TOTAL+1))
  if [[ "$got" == *"$want"* ]]; then
    echo -e "  ${G}✓${NC} $name"
    PASS=$((PASS+1))
  else
    echo -e "  ${R}✗${NC} $name  [got: $got]  [want: *${want}*]"
    FAIL=$((FAIL+1))
  fi
}

echo "=============================================="
echo "  Git Auto-Commit E2E Test Suite"
echo "=============================================="

# ── 1. Middleware health ───────────────────────────────────────────────────────
echo ""
echo "1. Middleware Health"
body=$(curl -sf "$MW/health" 2>&1) || body='{"ok":false}'
assert "middleware up" "$(jv '["ok"]' "$body")" "True"

# ── 2. Get session JWT ────────────────────────────────────────────────────────
echo ""
echo "2. Session Token"
if [[ -n "${TEST_JWT:-}" ]]; then
  JWT="$TEST_JWT"
  echo "  Using TEST_JWT from environment"
else
  prov=$(curl -sf -X POST "$MW/api/access/activate" \
    -H "Content-Type: application/json" \
    -d "{\"whmcsClientId\":\"git-test-77\",\"daUsername\":\"$DA_USERNAME\",\"plan\":\"starter\"}" \
    -H "X-WHMCS-Secret: $WHMCS_SECRET" 2>&1) || prov='{"ok":false}'
  assert "provision ok" "$(jv '["ok"]' "$prov")" "True"

  sso=$(curl -sf -X POST "$MW/api/sso/generate" \
    -H "Content-Type: application/json" \
    -d "{\"whmcsClientId\":\"git-test-77\",\"daUsername\":\"$DA_USERNAME\",\"plan\":\"starter\"}" \
    -H "X-WHMCS-Secret: $WHMCS_SECRET" 2>&1) || sso='{"ok":false}'
  SSO_TOKEN=$(jv '["token"]' "$sso")

  exch=$(curl -sf -X POST "$MW/api/sso/exchange" \
    -H "Content-Type: application/json" \
    -d "{\"token\":\"$SSO_TOKEN\"}" 2>&1) || exch='{"ok":false}'
  JWT=$(jv '["token"]' "$exch")
  assert "JWT obtained" "$JWT" "ey"
fi

# ── 3. Git init ───────────────────────────────────────────────────────────────
echo ""
echo "3. Git Init"
init=$(curl -sf -X POST "$MW/api/git/$DA_USERNAME/init" \
  -H "Authorization: Bearer $JWT" \
  -H "Content-Type: application/json" \
  -d '{}' 2>&1) || init='{"ok":false}'
assert "init ok" "$(jv '["ok"]' "$init")" "True"
assert "workDir present" "$init" "workDir"

# ── 4. Manual checkpoint ──────────────────────────────────────────────────────
echo ""
echo "4. Checkpoint (Manual Commit)"
chk=$(curl -sf -X POST "$MW/api/git/$DA_USERNAME/checkpoint" \
  -H "Authorization: Bearer $JWT" \
  -H "Content-Type: application/json" \
  -d '{"message":"E2E test checkpoint"}' 2>&1) || chk='{"ok":false}'
assert "checkpoint ok" "$(jv '["ok"]' "$chk")" "True"
assert "checkpoint has output" "$chk" "output"

# ── 5. Git log ────────────────────────────────────────────────────────────────
echo ""
echo "5. Git Log"
log=$(curl -sf "$MW/api/git/$DA_USERNAME/log?limit=5" \
  -H "Authorization: Bearer $JWT" 2>&1) || log='{"ok":false}'
assert "log ok" "$(jv '["ok"]' "$log")" "True"
assert "log has commits array" "$log" '"commits"'

# ── 6. Git status ─────────────────────────────────────────────────────────────
echo ""
echo "6. Git Status"
status=$(curl -sf "$MW/api/git/$DA_USERNAME/status" \
  -H "Authorization: Bearer $JWT" 2>&1) || status='{"ok":false}'
assert "status ok" "$(jv '["ok"]' "$status")" "True"
assert "status has clean field" "$status" '"clean"'

# ── 7. Git diff ───────────────────────────────────────────────────────────────
echo ""
echo "7. Git Diff"
diff=$(curl -sf "$MW/api/git/$DA_USERNAME/diff" \
  -H "Authorization: Bearer $JWT" 2>&1) || diff='{"ok":false}'
assert "diff ok" "$(jv '["ok"]' "$diff")" "True"

# ── 8. Auto-commit via file write ─────────────────────────────────────────────
echo ""
echo "8. Auto-Commit After File Write"
TS=$(date +%s)
TEST_FILE="gocodeme-git-test-${TS}.txt"
write=$(curl -sf -X POST "$MW/api/files/$DA_USERNAME" \
  -H "Authorization: Bearer $JWT" \
  -H "Content-Type: application/json" \
  -d "{\"path\":\"public_html/$TEST_FILE\",\"content\":\"git auto-commit test $TS\"}" \
  2>&1) || write='{"ok":false}'
assert "file write ok" "$(jv '["ok"]' "$write")" "True"

# Wait for the debounce (default 3s) + a small buffer
echo "  Waiting 5s for debounce timer..."
sleep 5

# Re-check log — should have a new auto-save commit
log2=$(curl -sf "$MW/api/git/$DA_USERNAME/log?limit=5" \
  -H "Authorization: Bearer $JWT" 2>&1) || log2='{"ok":false}'
assert "auto-commit appears in log" "$log2" "write"  # auto-save via files.js hook

# ── 9. Auth guard ─────────────────────────────────────────────────────────────
echo ""
echo "9. Auth Guard"
noauth=$(curl -s -o /dev/null -w "%{http_code}" \
  "$MW/api/git/$DA_USERNAME/log" 2>&1)
assert "git log requires auth (401)" "$noauth" "401"

# ── 10. Revert ────────────────────────────────────────────────────────────────
echo ""
echo "10. Revert Last Commit"
# First write a tracked test file to gocodeme-workspace and commit it
RVT_TS=$(date +%s)
RVT_FILE="/home/$DA_USERNAME/gocodeme-workspace/revert-test-${RVT_TS}.txt"
echo "revert target $RVT_TS" > "$RVT_FILE"

# Commit it via checkpoint
chk_rvt=$(curl -sf -X POST "$MW/api/git/$DA_USERNAME/checkpoint" \
  -H "Authorization: Bearer $JWT" \
  -H "Content-Type: application/json" \
  -d "{\"message\":\"revert-target: revert-test-${RVT_TS}.txt\"}" 2>&1) || chk_rvt='{"ok":false}'
assert "pre-revert checkpoint ok" "$(jv '["ok"]' "$chk_rvt")" "True"

# Now revert that commit
revert=$(curl -sf -X POST "$MW/api/git/$DA_USERNAME/revert" \
  -H "Authorization: Bearer $JWT" \
  -H "Content-Type: application/json" \
  -d '{}' 2>&1) || revert='{"ok":false}'
assert "revert ok" "$(jv '["ok"]' "$revert")" "True"
assert "revert has output" "$revert" "output"

# Re-check log to confirm revert commit was created
log3=$(curl -sf "$MW/api/git/$DA_USERNAME/log?limit=10" \
  -H "Authorization: Bearer $JWT" 2>&1) || log3='{"ok":false}'
assert "revert commit in log" "$log3" "Revert"

# Cleanup revert test file
rm -f "$RVT_FILE"

# ── Cleanup ───────────────────────────────────────────────────────────────────
curl -sf -X DELETE "$MW/api/files/$DA_USERNAME?path=public_html/$TEST_FILE" \
  -H "Authorization: Bearer $JWT" >/dev/null 2>&1 || true
curl -sf -X POST "$MW/api/access/terminate" \
  -H "Content-Type: application/json" \
  -d '{"whmcsClientId":"git-test-77"}' \
  -H "X-WHMCS-Secret: $WHMCS_SECRET" >/dev/null 2>&1 || true

# ── Summary ───────────────────────────────────────────────────────────────────
echo ""
echo "=============================================="
if [[ $FAIL -eq 0 ]]; then
  echo -e "  ${G}ALL $TOTAL TESTS PASSED${NC}"
else
  echo -e "  ${R}$FAIL/$TOTAL FAILED${NC}  (${PASS} passed)"
fi
echo "=============================================="

exit $FAIL
