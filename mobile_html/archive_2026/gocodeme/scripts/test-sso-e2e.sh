#!/usr/bin/env bash
# ──────────────────────────────────────────────────────────────────────────────
# GoCodeMe SSO End-to-End Test Script
#
# Tests the full WHMCS SSO → Dashboard flow without a real WHMCS instance:
#
#   1. Seed Redis: set access:active + da_username for a test client
#   2. Call POST /api/access/activate  (simulates WHMCS CreateAccount hook)
#   3. Call POST /api/sso/generate     (simulates WHMCS sso_redirect)
#   4. Use the returned token as Bearer (simulates dashboard /api/sso/me)
#   5. Call GET  /api/tokens/usage     (dashboard token widget)
#   6. Call POST /api/sso/exchange     (query-param SSO alternate path)
#   7. Call GET  /api/launch/sessions  (dashboard sessions widget)
#   8. Test suspension / unsuspension gate on /api/launch
#   9. Test top-up webhook
#
# Usage:
#   cd ~/domains/gositeme.com/public_html/gocodeme
#   bash scripts/test-sso-e2e.sh
#
# Requires: curl, jq (apt install jq), middleware running on :3001
# ──────────────────────────────────────────────────────────────────────────────
set -uo pipefail

BASE_URL="${MIDDLEWARE_URL:-http://localhost:3001}"
WHMCS_SECRET="${WHMCS_WEBHOOK_SECRET:-test-whmcs-secret-dev}"
CLIENT_ID=9001
DA_USER="testuser9001"
PLAN="professional"

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; NC='\033[0m'
PASS=0; FAIL=0

pass() { echo -e "${GREEN}✔ $1${NC}"; PASS=$((PASS+1)); }
fail() { echo -e "${RED}✗ $1${NC}"; FAIL=$((FAIL+1)); }
info() { echo -e "${YELLOW}→ $1${NC}"; }

# JSON field extractor using python3 (no jq dependency)
# Usage: jv '{"ok":true}' ok  →  "true"
jv() {
    local json=$1 key=$2
    python3 -c "
import json,sys
try:
    d = json.loads(sys.argv[1])
    keys = sys.argv[2].lstrip('.').split('.')
    for k in keys:
        d = d[k]
    print(str(d).lower() if isinstance(d,(bool,)) else d)
except Exception:
    print('')
" "$json" "$key" 2>/dev/null
}

check_json() {
    local label=$1 json=$2 field=$3 expected=$4
    local actual
    actual=$(jv "$json" "$field")
    if [[ "$actual" == "$expected" ]]; then
        pass "$label"
    else
        fail "$label — got: $actual (expected: $expected)"
        echo "  Full response: $json"
    fi
}

echo ""
echo "═══════════════════════════════════════════════════════"
echo " GoCodeMe SSO E2E Test"
echo " Middleware: $BASE_URL"
echo "═══════════════════════════════════════════════════════"
echo ""

# ── 0. Health check ───────────────────────────────────────────────────────────
info "0. Health check"
HEALTH=$(curl -sf "$BASE_URL/health" || echo '{}')
check_json "Middleware health" "$HEALTH" ".ok" "true"

# ── 1. Activate account (WHMCS CreateAccount hook) ───────────────────────────
info "1. Activate account (POST /api/access/activate)"
ACTIVATE=$(curl -sf -X POST "$BASE_URL/api/access/activate" \
    -H "Content-Type: application/json" \
    -H "X-WHMCS-Secret: $WHMCS_SECRET" \
    -d "{\"whmcsClientId\": $CLIENT_ID, \"daUsername\": \"$DA_USER\", \"plan\": \"$PLAN\"}" \
    || echo '{}')
check_json "Account activated" "$ACTIVATE" ".ok" "true"

# ── 2. Provision tokens ────────────────────────────────────────────────────────
info "2. Provision token limit (POST /api/tokens/provision)"
PROVISION=$(curl -sf -X POST "$BASE_URL/api/tokens/provision" \
    -H "Content-Type: application/json" \
    -H "X-WHMCS-Secret: $WHMCS_SECRET" \
    -d "{\"whmcsClientId\": $CLIENT_ID, \"plan\": \"$PLAN\"}" \
    || echo '{}')
check_json "Tokens provisioned" "$PROVISION" ".ok" "true"
LIMIT=$(jv "$PROVISION" 'limit')
LIMIT=${LIMIT:-0}
info "  Token limit set: $LIMIT"

# ── 3. Generate SSO token (WHMCS sso_redirect call) ──────────────────────────
info "3. Generate SSO token (POST /api/sso/generate)"
SSO_RESP=$(curl -sf -X POST "$BASE_URL/api/sso/generate" \
    -H "Content-Type: application/json" \
    -H "X-WHMCS-Secret: $WHMCS_SECRET" \
    -d "{\"whmcsClientId\": $CLIENT_ID, \"plan\": \"$PLAN\"}" \
    || echo '{}')
check_json "SSO token issued" "$SSO_RESP" ".ok" "true"
SSO_TOKEN=$(jv "$SSO_RESP" 'token')

if [[ -z "$SSO_TOKEN" ]]; then
    fail "No SSO token in response — aborting remaining tests"
    echo ""
    echo "PASS: $PASS  FAIL: $FAIL"
    exit 1
fi
info "  Token: ${SSO_TOKEN:0:40}…"

# ── 4. Authenticate as customer (GET /api/sso/me) ────────────────────────────
info "4. Who am I? (GET /api/sso/me with SSO token as Bearer)"
ME=$(curl -sf "$BASE_URL/api/sso/me" \
    -H "Authorization: Bearer $SSO_TOKEN" \
    || echo '{}')
check_json "Session valid" "$ME" ".ok" "true"
check_json "Correct daUsername" "$ME" ".user.daUsername" "$DA_USER"
check_json "Correct plan" "$ME" ".user.plan" "$PLAN"

# ── 5. Token usage ────────────────────────────────────────────────────────────
info "5. Token usage (GET /api/tokens/usage)"
USAGE=$(curl -sf "$BASE_URL/api/tokens/usage" \
    -H "Authorization: Bearer $SSO_TOKEN" \
    || echo '{}')
check_json "Usage endpoint OK" "$USAGE" ".ok" "true"
check_json "Token limit matches" "$USAGE" ".usage.limit" "$LIMIT"

# ── 6. SSO Exchange (query-param path) ───────────────────────────────────────
info "6. SSO exchange (GET /api/sso/exchange?sso=<token>)"
ENCODED_TOKEN=$(python3 -c "import urllib.parse,sys; print(urllib.parse.quote(sys.argv[1]))" "$SSO_TOKEN" 2>/dev/null || echo "$SSO_TOKEN")
EXCHANGE=$(curl -sf "$BASE_URL/api/sso/exchange?sso=${ENCODED_TOKEN}" \
    -H "Content-Type: application/json" \
    || echo '{}')
check_json "Exchange OK" "$EXCHANGE" ".ok" "true"
EXCHANGED_TOKEN=$(jv "$EXCHANGE" 'token')
[[ -n "$EXCHANGED_TOKEN" ]] && pass "Exchange returned new token" || fail "Exchange returned no token"

# Also test POST form
EXCHANGE_POST=$(curl -sf -X POST "$BASE_URL/api/sso/exchange" \
    -H "Content-Type: application/json" \
    -d "{\"token\": \"$SSO_TOKEN\"}" \
    || echo '{}')
check_json "Exchange POST OK" "$EXCHANGE_POST" ".ok" "true"

# ── 7. Launch sessions list ───────────────────────────────────────────────────
info "7. Sessions list (GET /api/launch/sessions)"
SESSIONS=$(curl -sf "$BASE_URL/api/launch/sessions" \
    -H "Authorization: Bearer $SSO_TOKEN" \
    || echo '{}')
check_json "Sessions endpoint OK" "$SESSIONS" ".ok" "true"

# ── 8. Suspension gate ────────────────────────────────────────────────────────
info "8. Suspension gate (suspend → try launch → unsuspend)"

# Suspend
SUSPEND=$(curl -sf -X POST "$BASE_URL/api/access/suspend" \
    -H "Content-Type: application/json" \
    -H "X-WHMCS-Secret: $WHMCS_SECRET" \
    -d "{\"whmcsClientId\": $CLIENT_ID}" \
    || echo '{}')
check_json "Suspend OK" "$SUSPEND" ".ok" "true"

# Try to launch while suspended — should get 402
LAUNCH_BLOCKED=$(curl -s -o /dev/null -w "%{http_code}" \
    -X POST "$BASE_URL/api/launch" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $SSO_TOKEN" \
    -d '{"workspace":"public_html"}')
if [[ "$LAUNCH_BLOCKED" == "402" ]]; then
    pass "Launch blocked while suspended (HTTP 402)"
else
    fail "Launch should be blocked while suspended — got HTTP $LAUNCH_BLOCKED"
fi

# Unsuspend
UNSUSPEND=$(curl -sf -X POST "$BASE_URL/api/access/unsuspend" \
    -H "Content-Type: application/json" \
    -H "X-WHMCS-Secret: $WHMCS_SECRET" \
    -d "{\"whmcsClientId\": $CLIENT_ID}" \
    || echo '{}')
check_json "Unsuspend OK" "$UNSUSPEND" ".ok" "true"

# ── 9. Top-up webhook ─────────────────────────────────────────────────────────
info "9. Token top-up (POST /api/tokens/topup)"
TOPUP=$(curl -sf -X POST "$BASE_URL/api/tokens/topup" \
    -H "Content-Type: application/json" \
    -H "X-WHMCS-Secret: $WHMCS_SECRET" \
    -d "{\"whmcsClientId\": $CLIENT_ID, \"bonusTokens\": 100000}" \
    || echo '{}')
check_json "Top-up OK" "$TOPUP" ".ok" "true"
NEW_LIMIT=$(jv "$TOPUP" 'newLimit')
NEW_LIMIT=${NEW_LIMIT:-0}
EXPECTED_LIMIT=$(( LIMIT + 100000 )) || true
if [[ "$NEW_LIMIT" == "$EXPECTED_LIMIT" ]]; then
    pass "New limit is $NEW_LIMIT ($LIMIT + 100000)"
else
    fail "New limit should be $EXPECTED_LIMIT — got $NEW_LIMIT"
fi

# ── 10. Dashboard HTML sanity ─────────────────────────────────────────────────
info "10. Dashboard HTML served (GET /dashboard)"
DASH_CODE=$(curl -sf -o /dev/null -w "%{http_code}" "$BASE_URL/dashboard" || echo "000")
if [[ "$DASH_CODE" == "200" ]]; then
    pass "Dashboard HTTP 200"
else
    fail "Dashboard HTTP $DASH_CODE (expected 200)"
fi

# ── Cleanup ───────────────────────────────────────────────────────────────────
info "Cleanup: terminate test account"
TERMINATE=$(curl -sf -X POST "$BASE_URL/api/access/terminate" \
    -H "Content-Type: application/json" \
    -H "X-WHMCS-Secret: $WHMCS_SECRET" \
    -d "{\"whmcsClientId\": $CLIENT_ID}" \
    || echo '{}')
[[ "$(jv "$TERMINATE" 'ok')" == "true" ]] \
    && info "  Test account terminated" \
    || info "  Warning: terminate returned: $TERMINATE"

# ── Summary ───────────────────────────────────────────────────────────────────
echo ""
echo "═══════════════════════════════════════════════════════"
TOTAL=$((PASS + FAIL))
if [[ $FAIL -eq 0 ]]; then
    echo -e "${GREEN}ALL $TOTAL TESTS PASSED${NC}"
else
    echo -e "${RED}$FAIL/$TOTAL TESTS FAILED${NC}"
    echo -e "  ${GREEN}PASS: $PASS${NC}  ${RED}FAIL: $FAIL${NC}"
fi
echo "═══════════════════════════════════════════════════════"
echo ""
exit $FAIL
