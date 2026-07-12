#!/usr/bin/env bash
# ── Week 14 Billing Engine Tests ─────────────────────────────────────────────
# Tests: WHMCS API client, alert engine, billing HTTP endpoints
# Usage: bash scripts/test-billing.sh
set -euo pipefail

BASE="http://localhost:3001"
MW_DIR="$(cd "$(dirname "$0")/../middleware" && pwd)"

# ── Load env vars ─────────────────────────────────────────────────────────────
set -a; source "$MW_DIR/.env"; set +a

JWT_SEC="$JWT_SECRET"
WHMCS_SECRET="$WHMCS_WEBHOOK_SECRET"

# ── Helpers ───────────────────────────────────────────────────────────────────
PASS=0; FAIL=0
pass() { echo "  ✓ $1"; PASS=$(( PASS + 1 )); }
fail() { echo "  ✗ $1"; FAIL=$(( FAIL + 1 )); }

assert_json_field() {
  local label="$1" json="$2" field="$3" expected="$4"
  local actual
  actual=$(echo "$json" | node -pe "JSON.parse(require('fs').readFileSync('/dev/stdin','utf8'))['$field']" 2>/dev/null || echo "__PARSE_ERROR__")
  if [[ "$actual" == "$expected" ]]; then
    pass "$label"
  else
    fail "$label (expected '$expected', got '$actual')"
    echo "     JSON: $json" | head -c 300
    echo
  fi
}

assert_contains() {
  local label="$1" haystack="$2" needle="$3"
  if echo "$haystack" | grep -q "$needle"; then
    pass "$label"
  else
    fail "$label (missing '$needle')"
    echo "     Response: $haystack" | head -c 300
    echo
  fi
}

assert_status() {
  local label="$1" actual="$2" expected="$3"
  if [[ "$actual" == "$expected" ]]; then
    pass "$label"
  else
    fail "$label (HTTP $actual, expected $expected)"
  fi
}

# ── Generate a test session JWT ───────────────────────────────────────────────
TEST_WHMCS_ID=9999
TEST_DA_USER="testuser_billing"
TEST_PLAN="pro"

SESSION_JWT=$(node -e "
const jwt = require('jsonwebtoken');
const token = jwt.sign(
  { whmcsClientId: $TEST_WHMCS_ID, daUsername: '$TEST_DA_USER', plan: '$TEST_PLAN' },
  '$JWT_SEC',
  { expiresIn: '1h' }
);
console.log(token);
" 2>/dev/null)

if [[ -z "$SESSION_JWT" ]]; then
  echo "ERROR: Could not generate test JWT — is jsonwebtoken installed in middleware?"
  exit 1
fi

# ── Redis helper (uses ioredis — no redis-cli needed) ─────────────────────────
IOREDIS="$MW_DIR/node_modules/ioredis"
rset()  { node -e "const r=new(require('$IOREDIS'))();r.set('$1','$2').then(()=>r.quit())" 2>/dev/null; }
rsetex(){ node -e "const r=new(require('$IOREDIS'))();r.set('$1','$2','ex',$3).then(()=>r.quit())" 2>/dev/null; }
rdel()  { node -e "const r=new(require('$IOREDIS'))();r.del('$1').then(()=>r.quit())" 2>/dev/null; }
rget()  { node -e "const r=new(require('$IOREDIS'))();r.get('$1').then(v=>{process.stdout.write(v||'');r.quit()})" 2>/dev/null; }

# ── Seed test usage data in Redis ─────────────────────────────────────────────
# First clear any existing test data
rdel "tokens:used:$TEST_WHMCS_ID"
rdel "billing:alert:80:$TEST_WHMCS_ID"
rdel "billing:alert:100:$TEST_WHMCS_ID"
rdel "billing:invoice:$TEST_WHMCS_ID"

echo
echo "════════════════════════════════════════════════════"
echo "  GoCodeMe Week 14 — Billing Engine Tests"
echo "════════════════════════════════════════════════════"

# ── T01: Health check ─────────────────────────────────────────────────────────
echo
echo "── T01: Middleware health ──"
RESP=$(curl -s "$BASE/health")
assert_contains "GET /health → ok" "$RESP" "ok"

# ── T02: Auth guard — no token → 401 ─────────────────────────────────────────
echo
echo "── T02: Auth guard ──"
HTTP=$(curl -s -o /dev/null -w "%{http_code}" "$BASE/api/billing/usage-report")
assert_status "GET /api/billing/usage-report without JWT → 401" "$HTTP" "401"

HTTP=$(curl -s -o /dev/null -w "%{http_code}" "$BASE/api/billing/invoices")
assert_status "GET /api/billing/invoices without JWT → 401" "$HTTP" "401"

HTTP=$(curl -s -o /dev/null -w "%{http_code}" "$BASE/api/billing/whmcs-client")
assert_status "GET /api/billing/whmcs-client without JWT → 401" "$HTTP" "401"

# ── T03: WHMCS secret guard ───────────────────────────────────────────────────
echo
echo "── T03: WHMCS secret guard ──"
HTTP=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE/api/billing/invoice" \
  -H "Content-Type: application/json" -d '{"whmcsClientId":1}')
assert_status "POST /api/billing/invoice without secret → 401" "$HTTP" "401"

HTTP=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE/api/billing/reset-alerts" \
  -H "Content-Type: application/json" -d '{"whmcsClientId":1}')
assert_status "POST /api/billing/reset-alerts without secret → 401" "$HTTP" "401"

# ── T04: Usage report — authenticated ─────────────────────────────────────────
echo
echo "── T04: Usage report ──"
RESP=$(curl -s "$BASE/api/billing/usage-report" \
  -H "Authorization: Bearer $SESSION_JWT")
assert_json_field "usage-report → ok=true" "$RESP" "ok" "true"
assert_contains "usage-report has .usage field" "$RESP" '"usage"'
assert_contains "usage-report has .alerts field" "$RESP" '"alerts"'
assert_contains "usage-report has whmcsClientId=$TEST_WHMCS_ID" "$RESP" "$TEST_WHMCS_ID"

# ── T05: Invoices endpoint — authenticated, graceful on WHMCS error ───────────
echo
echo "── T05: Invoices list ──"
RESP=$(curl -s "$BASE/api/billing/invoices" \
  -H "Authorization: Bearer $SESSION_JWT")
assert_json_field "invoices → ok=true" "$RESP" "ok" "true"
assert_contains "invoices has .invoices array" "$RESP" '"invoices"'

# ── T06: WHMCS client info — graceful degradation ─────────────────────────────
echo
echo "── T06: WHMCS client info ──"
RESP=$(curl -s "$BASE/api/billing/whmcs-client" \
  -H "Authorization: Bearer $SESSION_JWT")
# Either ok:true with data, or ok:false with error — both are valid (depends on WHMCS)
assert_contains "whmcs-client returns JSON" "$RESP" '"ok"'

# ── T07: Reset alerts — WHMCS secret ─────────────────────────────────────────
echo
echo "── T07: Reset alerts ──"
# First seed some alert keys
rsetex "billing:alert:80:$TEST_WHMCS_ID" "1" 100
rsetex "billing:alert:100:$TEST_WHMCS_ID" "1" 100

RESP=$(curl -s -X POST "$BASE/api/billing/reset-alerts" \
  -H "Content-Type: application/json" \
  -H "x-whmcs-secret: $WHMCS_SECRET" \
  -d "{\"whmcsClientId\": $TEST_WHMCS_ID}")
assert_json_field "reset-alerts → ok=true" "$RESP" "ok" "true"

# Verify Redis keys are gone
KEY80=$(rget "billing:alert:80:$TEST_WHMCS_ID")
KEY100=$(rget "billing:alert:100:$TEST_WHMCS_ID")
[[ -z "$KEY80" ]] && pass "billing:alert:80 key deleted from Redis" || fail "billing:alert:80 key still exists: $KEY80"
[[ -z "$KEY100" ]] && pass "billing:alert:100 key deleted from Redis" || fail "billing:alert:100 key still exists: $KEY100"

# ── T08: Alert hook — 80% threshold via tokens/report ─────────────────────────
echo
echo "── T08: 80% alert hook (via tokens/report) ──"
# First provision a token limit for the test user (600k = 'professional' plan)
# Uses WHMCS secret endpoint
rdel "tokens:used:$TEST_WHMCS_ID"
rdel "tokens:limit:$TEST_WHMCS_ID"
rdel "billing:alert:80:$TEST_WHMCS_ID"

RESP=$(curl -s -X POST "$BASE/api/tokens/provision" \
  -H "Content-Type: application/json" \
  -H "x-whmcs-secret: $WHMCS_SECRET" \
  -d "{\"whmcsClientId\": $TEST_WHMCS_ID, \"plan\": \"professional\"}")
assert_contains "provision plan for test user" "$RESP" '"ok"'

# pro plan limit per config = TOKEN_LIMIT_PROFESSIONAL (default 600000)
PRO_LIMIT=600000
# Seed usage above 80%: 81% = 486001 tokens
TARGET=$(( PRO_LIMIT * 81 / 100 ))
rset "tokens:used:$TEST_WHMCS_ID" "$TARGET"

# Trigger tokens/report with 0 new tokens (checkAlerts reads current counter)
RESP=$(curl -s -X POST "$BASE/api/tokens/report" \
  -H "Authorization: Bearer $SESSION_JWT" \
  -H "Content-Type: application/json" \
  -d '{"inputTokens": 0, "outputTokens": 0}')
assert_contains "tokens/report response ok" "$RESP" '"ok"'

# Wait for async alert processing
sleep 2

KEY80=$(rget "billing:alert:80:$TEST_WHMCS_ID")
[[ -n "$KEY80" ]] && pass "billing:alert:80 key set in Redis after 80% crossed" || fail "billing:alert:80 key NOT set (alert hook may not have fired)"

# ── T09: Alert hook — 100% threshold ─────────────────────────────────────────
echo
echo "── T09: 100% alert hook (over limit) ──"
rdel "billing:alert:100:$TEST_WHMCS_ID"
rdel "billing:invoice:$TEST_WHMCS_ID"

# Set usage above 100%
OVER_LIMIT=$(( PRO_LIMIT + 10000 ))
rset "tokens:used:$TEST_WHMCS_ID" "$OVER_LIMIT"

RESP=$(curl -s -X POST "$BASE/api/tokens/report" \
  -H "Authorization: Bearer $SESSION_JWT" \
  -H "Content-Type: application/json" \
  -d '{"inputTokens": 0, "outputTokens": 0}')
assert_contains "tokens/report (over limit) response ok" "$RESP" '"ok"'

sleep 2

KEY100=$(rget "billing:alert:100:$TEST_WHMCS_ID")
[[ -n "$KEY100" ]] && pass "billing:alert:100 key set in Redis after 100% crossed" || fail "billing:alert:100 key NOT set"
# Invoice key may or may not be set depending on WHMCS reachability — just check alert key

# ── T10: Manual invoice creation ─────────────────────────────────────────────
echo
echo "── T10: Manual invoice (WHMCS live call) ──"
# This calls real WHMCS — if WHMCS is reachable, invoice should be created.
# clientId 9999 is a test user; WHMCS will likely return an error — but the
# route itself must respond with a proper JSON body (not crash with 500).
HTTP=$(curl -s -o /tmp/billing_invoice_resp.json -w "%{http_code}" -X POST "$BASE/api/billing/invoice" \
  -H "Content-Type: application/json" \
  -H "x-whmcs-secret: $WHMCS_SECRET" \
  -d "{\"whmcsClientId\": $TEST_WHMCS_ID, \"description\": \"[TEST] overage\", \"amountUsd\": 0.01}")
INV_RESP=$(cat /tmp/billing_invoice_resp.json)
if echo "$INV_RESP" | grep -q '"ok":true'; then
  INVOICE_ID=$(echo "$INV_RESP" | node -pe "JSON.parse(require('fs').readFileSync('/dev/stdin','utf8')).invoiceId" 2>/dev/null || echo "?")
  pass "POST /api/billing/invoice → WHMCS invoice created (id=$INVOICE_ID)"
elif echo "$INV_RESP" | grep -q '"ok"'; then
  # Route responded with JSON error (WHMCS rejected test clientId) — route works
  pass "POST /api/billing/invoice route works (WHMCS rejected test clientId — expected)"
else
  fail "POST /api/billing/invoice returned HTTP $HTTP — not JSON or crashed"
fi

# ── T11: Invoice deduplication (billing cycle reset) ─────────────────────────
echo
echo "── T11: Billing cycle reset (tokens/reset) ──"
rsetex "billing:alert:80:$TEST_WHMCS_ID" "1" 100
rsetex "billing:alert:100:$TEST_WHMCS_ID" "1" 100

# tokens/reset requires WHMCS webhook secret (not session JWT)
RESP=$(curl -s -X POST "$BASE/api/tokens/reset" \
  -H "Content-Type: application/json" \
  -H "x-whmcs-secret: $WHMCS_SECRET" \
  -d "{\"whmcsClientId\": $TEST_WHMCS_ID}")
assert_contains "tokens/reset response ok" "$RESP" '"ok"'
sleep 1

KEY80=$(rget "billing:alert:80:$TEST_WHMCS_ID")
KEY100=$(rget "billing:alert:100:$TEST_WHMCS_ID")
[[ -z "$KEY80" ]] && pass "billing:alert:80 cleared on tokens/reset" || fail "billing:alert:80 still set after reset"
[[ -z "$KEY100" ]] && pass "billing:alert:100 cleared on tokens/reset" || fail "billing:alert:100 still set after reset"

# ── T12: Tokens report usage aggregation visible in billing ──────────────────
echo
echo "── T12: Usage visible in billing report after token add ──"
rdel "tokens:used:$TEST_WHMCS_ID"

# Report 50k tokens using correct field names: inputTokens + outputTokens
RESP=$(curl -s -X POST "$BASE/api/tokens/report" \
  -H "Authorization: Bearer $SESSION_JWT" \
  -H "Content-Type: application/json" \
  -d '{"inputTokens": 30000, "outputTokens": 20000}')
assert_contains "50k token report accepted" "$RESP" '"ok"'

# Fetch usage-report and verify count
RESP=$(curl -s "$BASE/api/billing/usage-report" \
  -H "Authorization: Bearer $SESSION_JWT")
USED=$(echo "$RESP" | node -pe "
  const d = JSON.parse(require('fs').readFileSync('/dev/stdin','utf8'));
  d.usage ? d.usage.used : 'MISSING'
" 2>/dev/null || echo "PARSE_ERR")
[[ "$USED" == "50000" ]] && pass "usage-report shows 50000 tokens used" || fail "usage-report shows '$USED' (expected 50000)"

# ── Cleanup ────────────────────────────────────────────────────────────────────
rdel "tokens:used:$TEST_WHMCS_ID"
rdel "billing:alert:80:$TEST_WHMCS_ID"
rdel "billing:alert:100:$TEST_WHMCS_ID"
rdel "billing:invoice:$TEST_WHMCS_ID"

# ── Summary ───────────────────────────────────────────────────────────────────
echo
echo "════════════════════════════════════════════════════"
TOTAL=$(( PASS + FAIL ))
echo "  Results: $PASS/$TOTAL passed"
if [[ $FAIL -eq 0 ]]; then
  echo "  ALL TESTS PASSED ✓"
else
  echo "  $FAIL TEST(S) FAILED ✗"
fi
echo "════════════════════════════════════════════════════"
echo

[[ $FAIL -eq 0 ]]
