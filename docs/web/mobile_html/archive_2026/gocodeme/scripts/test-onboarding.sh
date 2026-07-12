#!/usr/bin/env bash
# ── Week 15 Onboarding Wizard Tests ──────────────────────────────────────────
# Usage: bash scripts/test-onboarding.sh
set -euo pipefail

BASE="http://localhost:3001"
MW_DIR="$(cd "$(dirname "$0")/../middleware" && pwd)"

set -a; source "$MW_DIR/.env"; set +a

JWT_SEC="$JWT_SECRET"
IOREDIS="$MW_DIR/node_modules/ioredis"

rdel() { node -e "const r=new(require('$IOREDIS'))();r.del('$1').then(()=>r.quit())" 2>/dev/null; }
rget() { node -e "const r=new(require('$IOREDIS'))();r.get('$1').then(v=>{process.stdout.write(v||'');r.quit()})" 2>/dev/null; }

PASS=0; FAIL=0
pass() { echo "  ✓ $1"; PASS=$(( PASS + 1 )); }
fail() { echo "  ✗ $1"; FAIL=$(( FAIL + 1 )); echo "     $2" | head -c 300; echo; }

assert_contains() {
  local label="$1" hay="$2" needle="$3"
  echo "$hay" | grep -q "$needle" && pass "$label" || fail "$label" "missing '$needle' in: $hay"
}
assert_status() {
  [[ "$2" == "$3" ]] && pass "$1" || fail "$1" "HTTP $2, expected $3"
}
json_field() {
  echo "$1" | node -pe "JSON.parse(require('fs').readFileSync('/dev/stdin','utf8'))['$2']" 2>/dev/null || echo "__ERR__"
}
assert_field() {
  local label="$1" json="$2" field="$3" exp="$4"
  local actual; actual=$(json_field "$json" "$field")
  [[ "$actual" == "$exp" ]] && pass "$label" || fail "$label" "field '$field': expected '$exp', got '$actual'"
}

# Generate test JWT
TEST_ID=8888
TEST_USER="testuser_onboarding"

JWT=$(node -e "
const jwt=require('jsonwebtoken');
console.log(jwt.sign({whmcsClientId:$TEST_ID,daUsername:'$TEST_USER',plan:'professional'},'$JWT_SEC',{expiresIn:'1h'}));
" 2>/dev/null)

[[ -z "$JWT" ]] && echo "ERROR: could not generate JWT" && exit 1

# Clear test state
rdel "onboarding:step:$TEST_ID"
rdel "onboarding:done:$TEST_ID"

echo
echo "════════════════════════════════════════════════════"
echo "  GoCodeMe Week 15 — Onboarding Wizard Tests"
echo "════════════════════════════════════════════════════"

# ── T01: Health ───────────────────────────────────────────────────────────────
echo
echo "── T01: Health ──"
RESP=$(curl -s "$BASE/health")
assert_contains "GET /health → ok" "$RESP" "ok"

# ── T02: Auth guards ──────────────────────────────────────────────────────────
echo
echo "── T02: Auth guards ──"
for EP in "onboarding/status" "onboarding/advance" "onboarding/complete" "onboarding/reset"; do
  METHOD="GET"; [[ "$EP" != "onboarding/status" ]] && METHOD="POST"
  HTTP=$(curl -s -o /dev/null -w "%{http_code}" -X "$METHOD" "$BASE/api/$EP")
  assert_status "/$EP without JWT → 401" "$HTTP" "401"
done

# ── T03: Initial status — step 0, not done ───────────────────────────────────
echo
echo "── T03: Initial status ──"
RESP=$(curl -s "$BASE/api/onboarding/status" -H "Authorization: Bearer $JWT")
assert_field "status → ok=true"  "$RESP" "ok"   "true"
assert_field "status → step=0"   "$RESP" "step"  "0"
assert_field "status → done=false" "$RESP" "done" "false"
assert_contains "status has .steps array" "$RESP" '"steps"'
assert_contains "steps has title field" "$RESP" '"title"'

# ── T04: Advance step by step ─────────────────────────────────────────────────
echo
echo "── T04: Advance wizard steps ──"
RESP=$(curl -s -X POST "$BASE/api/onboarding/advance" -H "Authorization: Bearer $JWT")
assert_field "advance step 0→1 → ok" "$RESP" "ok" "true"
assert_field "advance step 0→1 → step=1" "$RESP" "step" "1"

RESP=$(curl -s -X POST "$BASE/api/onboarding/advance" -H "Authorization: Bearer $JWT")
assert_field "advance step 1→2 → step=2" "$RESP" "step" "2"

RESP=$(curl -s -X POST "$BASE/api/onboarding/advance" -H "Authorization: Bearer $JWT")
assert_field "advance step 2→3 → step=3" "$RESP" "step" "3"

RESP=$(curl -s -X POST "$BASE/api/onboarding/advance" -H "Authorization: Bearer $JWT")
assert_field "advance step 3→4 → step=4" "$RESP" "step" "4"
assert_field "advance to final → done=true" "$RESP" "done" "true"

# ── T05: Status after completion ──────────────────────────────────────────────
echo
echo "── T05: Status after completion ──"
RESP=$(curl -s "$BASE/api/onboarding/status" -H "Authorization: Bearer $JWT")
assert_field "status after complete → done=true" "$RESP" "done" "true"

# ── T06: Reset ────────────────────────────────────────────────────────────────
echo
echo "── T06: Reset wizard ──"
RESP=$(curl -s -X POST "$BASE/api/onboarding/reset" -H "Authorization: Bearer $JWT")
assert_field "reset → ok=true"   "$RESP" "ok"   "true"
assert_field "reset → step=0"    "$RESP" "step"  "0"
assert_field "reset → done=false" "$RESP" "done" "false"

RESP=$(curl -s "$BASE/api/onboarding/status" -H "Authorization: Bearer $JWT")
assert_field "status after reset → step=0"    "$RESP" "step" "0"
assert_field "status after reset → done=false" "$RESP" "done" "false"

# ── T07: Complete endpoint (skips directly to done) ───────────────────────────
echo
echo "── T07: Complete endpoint (skip) ──"
RESP=$(curl -s -X POST "$BASE/api/onboarding/complete" -H "Authorization: Bearer $JWT")
assert_field "complete → ok=true" "$RESP" "ok" "true"

STEP=$(rget "onboarding:step:$TEST_ID")
DONE=$(rget "onboarding:done:$TEST_ID")
[[ "$DONE" == "1" ]] && pass "onboarding:done Redis key set to 1" || fail "onboarding:done Redis key not set" "got: $DONE"
[[ -n "$STEP" ]] && pass "onboarding:step Redis key set" || fail "onboarding:step Redis key missing"

# ── T08: Auto-advance step 2 when OpenClaw channel linked ─────────────────────
echo
echo "── T08: Auto-advance at step 2 when channel already linked ──"
# Reset to step 2
RESP=$(curl -s -X POST "$BASE/api/onboarding/reset" -H "Authorization: Bearer $JWT")
curl -s -X POST "$BASE/api/onboarding/advance" -H "Authorization: Bearer $JWT" > /dev/null
curl -s -X POST "$BASE/api/onboarding/advance" -H "Authorization: Bearer $JWT" > /dev/null
STEP=$(json_field "$(curl -s $BASE/api/onboarding/status -H 'Authorization: Bearer '$JWT)" "step")
[[ "$STEP" == "2" ]] && pass "wizard at step 2 before channel link" || fail "expected step 2, got $STEP" ""

# Seed a linked channel in Redis
node -e "const r=new(require('$IOREDIS'))();r.sadd('openclaw:links:$TEST_USER','telegram:123456').then(()=>r.quit())" 2>/dev/null

RESP=$(curl -s "$BASE/api/onboarding/status" -H "Authorization: Bearer $JWT")
assert_field "auto-advance to step 3 when channel linked" "$RESP" "step" "3"

# Cleanup the openclaw link seed
node -e "const r=new(require('$IOREDIS'))();r.del('openclaw:links:$TEST_USER').then(()=>r.quit())" 2>/dev/null

# ── Cleanup ────────────────────────────────────────────────────────────────────
rdel "onboarding:step:$TEST_ID"
rdel "onboarding:done:$TEST_ID"

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
