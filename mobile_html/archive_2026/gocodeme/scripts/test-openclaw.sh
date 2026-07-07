#!/usr/bin/env bash
# test-openclaw.sh — OpenClaw E2E smoke tests
# Tests: openclaw health, middleware /api/openclaw/* routes, link flow
#
# Requires:
#   - Middleware running on port 3001
#   - OpenClaw running on port 3004
#   - A valid WHMCS access token in TEST_JWT env var, OR the test will create one
#
# Usage:
#   cd gocodeme && bash scripts/test-openclaw.sh
#   TEST_JWT=<jwt> bash scripts/test-openclaw.sh

set -uo pipefail

MW="http://localhost:3001"
OC="http://localhost:3004"
PASS=0
FAIL=0
TOTAL=0

# Load env vars from middleware .env if not already set
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
ENV_FILE="$SCRIPT_DIR/../middleware/.env"
if [[ -f "$ENV_FILE" ]]; then
  # shellcheck disable=SC1090
  set -a; source "$ENV_FILE"; set +a
fi
WHMCS_SECRET="${WHMCS_WEBHOOK_SECRET:-dev-whmcs-secret}"

# ── Helpers ────────────────────────────────────────────────────────────────────
G='\033[0;32m'; R='\033[0;31m'; Y='\033[1;33m'; NC='\033[0m'

# Extract JSON field: jv '["field"]' '{"field":"val"}'
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
echo "  OpenClaw E2E Test Suite"
echo "=============================================="

# ── 1. OpenClaw health ────────────────────────────────────────────────────────
echo ""
echo "1. OpenClaw Service Health"
body=$(curl -sf "$OC/health" 2>&1) || body="__CONN_REFUSED__"
assert "health returns ok" "$(jv '["ok"]' "$body")" "True"
assert "service name correct" "$(jv '["service"]' "$body")" "openclaw"

# ── 2. Middleware health ──────────────────────────────────────────────────────
echo ""
echo "2. Middleware Health"
body=$(curl -sf "$MW/health" 2>&1) || body="__CONN_REFUSED__"
assert "middleware returns ok" "$(jv '["ok"]' "$body")" "True"

# ── 3. Get session JWT ────────────────────────────────────────────────────────
echo ""
echo "3. Session Token"

if [[ -n "${TEST_JWT:-}" ]]; then
  JWT="$TEST_JWT"
  echo "  Using TEST_JWT from environment"
else
  echo "  Provisioning test user..."
  prov=$(curl -sf -X POST "$MW/api/access/activate" \
    -H "Content-Type: application/json" \
    -d '{"whmcsClientId":"oc-test-99","daUsername":"octestuser","plan":"starter"}' \
    -H "X-WHMCS-Secret: ${WHMCS_SECRET:-dev-whmcs-secret}" 2>&1) || prov='{"ok":false}'
  assert "provision ok" "$(jv '["ok"]' "$prov")" "True"

  sso=$(curl -sf -X POST "$MW/api/sso/generate" \
    -H "Content-Type: application/json" \
    -d '{"whmcsClientId":"oc-test-99","daUsername":"octestuser","plan":"starter"}' \
    -H "X-WHMCS-Secret: ${WHMCS_SECRET:-dev-whmcs-secret}" 2>&1) || sso='{"ok":false}'
  SSO_TOKEN=$(jv '["token"]' "$sso")
  assert "SSO token issued" "$SSO_TOKEN" "ey"

  # Exchange SSO token for full session JWT
  exch=$(curl -sf -X POST "$MW/api/sso/exchange" \
    -H "Content-Type: application/json" \
    -d "{\"token\":\"$SSO_TOKEN\"}" 2>&1) || exch='{"ok":false}'
  JWT=$(jv '["token"]' "$exch")
  assert "session JWT obtained" "$JWT" "ey"
fi

# ── 4. /api/openclaw/link-token ───────────────────────────────────────────────
echo ""
echo "4. Link Token Generation"
lt=$(curl -sf "$MW/api/openclaw/link-token" \
  -H "Authorization: Bearer $JWT" 2>&1) || lt='{"ok":false}'
assert "link-token ok" "$(jv '["ok"]' "$lt")" "True"
LINK_TOKEN=$(jv '["token"]' "$lt")
assert "link-token is JWT" "$LINK_TOKEN" "ey"
assert "expiresIn present" "$(jv '["expiresIn"]' "$lt")" "3600"
assert "hint present" "$(jv '["hint"]' "$lt")" "/link"

# ── 5. /api/openclaw/link ─────────────────────────────────────────────────────
echo ""
echo "5. Channel Linking"
link=$(curl -sf -X POST "$MW/api/openclaw/link" \
  -H "Content-Type: application/json" \
  -d "{\"token\":\"$LINK_TOKEN\",\"channel\":\"telegram\",\"channelUserId\":\"tg-test-42\",\"displayName\":\"TestUser\"}" \
  2>&1) || link='{"ok":false}'
assert "link ok" "$(jv '["ok"]' "$link")" "True"
assert "link returns daUsername" "$(jv '["daUsername"]' "$link")" "octestuser"
assert "link returns plan" "$(jv '["plan"]' "$link")" "starter"

# ── 6. Invalid link token ─────────────────────────────────────────────────────
echo ""
echo "6. Link Token Validation"
bad=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$MW/api/openclaw/link" \
  -H "Content-Type: application/json" \
  -d '{"token":"badtoken","channel":"telegram","channelUserId":"x"}' 2>&1)
assert "bad token returns 401" "$bad" "401"

missing=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$MW/api/openclaw/link" \
  -H "Content-Type: application/json" \
  -d '{"channel":"telegram","channelUserId":"x"}' 2>&1)
assert "missing token returns 400" "$missing" "400"

# ── 7. /api/openclaw/stats ────────────────────────────────────────────────────
echo ""
echo "7. Stats Endpoint"
stats=$(curl -sf "$MW/api/openclaw/stats/octestuser" \
  -H "Authorization: Bearer $JWT" 2>&1) || stats='{"ok":false}'
assert "stats returns ok" "$(jv '["ok"]' "$stats")" "True"
# Either real data or graceful offline message
assert "stats has links field" "$stats" '"links"'

# Unauthorized access to another user's stats
other_status=$(curl -s -o /dev/null -w "%{http_code}" \
  "$MW/api/openclaw/stats/someoneelse" \
  -H "Authorization: Bearer $JWT" 2>&1)
assert "cannot access other user stats (403)" "$other_status" "403"

# ── 8. /api/openclaw/links ────────────────────────────────────────────────────
echo ""
echo "8. Links Endpoint"
links=$(curl -sf "$MW/api/openclaw/links/octestuser" \
  -H "Authorization: Bearer $JWT" 2>&1) || links='{"ok":false}'
assert "links ok" "$(jv '["ok"]' "$links")" "True"
assert "links has array" "$links" '"links"'
# Our telegram link should be there
assert "linked telegram channel present" "$links" "telegram"

# ── 9. No auth returns 401 ────────────────────────────────────────────────────
echo ""
echo "9. Auth Guard"
noauth=$(curl -s -o /dev/null -w "%{http_code}" "$MW/api/openclaw/link-token" 2>&1)
assert "link-token requires auth (401)" "$noauth" "401"

# ── 10. OpenClaw 404 ─────────────────────────────────────────────────────────
echo ""
echo "10. OpenClaw 404 Handling"
oc404=$(curl -s -o /dev/null -w "%{http_code}" "$OC/nonexistent-path" 2>&1)
assert "openclaw 404 for unknown path" "$oc404" "404"

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
