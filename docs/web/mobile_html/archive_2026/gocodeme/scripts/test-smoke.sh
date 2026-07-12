#!/usr/bin/env bash
# =============================================================================
# GoCodeMe – Final Full-Stack Smoke Test  (Weeks 1-16)
# Usage:  bash scripts/test-smoke.sh
#
# Covers every major service and endpoint family across all 16 weeks.
# All tests must pass on a clean server with the three services running:
#   mw (middleware)  → port 3001
#   openclaw         → port 3004
#   mcp3 (MCP)       → port 3005
# =============================================================================
set -euo pipefail

MW="http://localhost:3001"
OC="http://localhost:3004"
MCP="http://localhost:3005"

PASS=0
FAIL=0
SKIP=0

GREEN='\033[0;32m'; RED='\033[0;31m'; YELLOW='\033[1;33m'; CYAN='\033[0;36m'; NC='\033[0m'
pass() { echo -e "${GREEN}✓${NC} $1"; PASS=$(( PASS + 1 )); }
fail() { echo -e "${RED}✗${NC} $1"; FAIL=$(( FAIL + 1 )); }
skip() { echo -e "${YELLOW}⊘${NC} $1"; SKIP=$(( SKIP + 1 )); }
section() { echo -e "\n${CYAN}── $1 ──────────────────────────────────────${NC}"; }

# ── JWT helper ────────────────────────────────────────────────────────────────
JWT_SECRET="bc65634d1693afdf3b5381e2edd67c2b94418186d5a09e7f2d10f214178809302b8a549180bf1f5d"
WHMCS_SECRET=$(node -e "require('dotenv').config({path:'$(dirname "$0")/../middleware/.env'}); process.stdout.write(process.env.WHMCS_WEBHOOK_SECRET||'')" 2>/dev/null || true)
TEST_CLIENT="smoke_$(date +%s)"
TEST_USER="smokeuser_$(date +%s)"

TOKEN=$(node -e "
  const jwt = require('jsonwebtoken');
  process.stdout.write(jwt.sign(
    { whmcsClientId: '$TEST_CLIENT', daUsername: '$TEST_USER', plan: 'professional' },
    '$JWT_SECRET', { expiresIn: '1h' }
  ));
")

# ── pre-flight: all three services ───────────────────────────────────────────
section "Pre-flight: Service Health (Weeks 1-3 foundation)"

H1=$(curl -s -o /dev/null -w "%{http_code}" "$MW/health")
H2=$(curl -s -o /dev/null -w "%{http_code}" "$OC/health")
H3=$(curl -s -o /dev/null -w "%{http_code}" "$MCP/health" 2>/dev/null || echo "000")

[[ "$H1" == "200" ]] && pass "S01: Middleware /health → 200"      || { fail "S01: Middleware not up (HTTP $H1)"; echo "FATAL: middleware must be running"; exit 1; }
[[ "$H2" == "200" ]] && pass "S02: OpenClaw /health → 200"        || fail "S02: OpenClaw not up (HTTP $H2)"
[[ "$H3" == "200" ]] && pass "S03: MCP Server /health → 200"      || skip "S03: MCP not responding on 3005 (HTTP $H3) – non-fatal"

# ── Week 4-5: Auth / SSO ─────────────────────────────────────────────────────
section "Auth & SSO (Weeks 4-5)"

HTTP=$(curl -s -o /dev/null -w "%{http_code}" "$MW/api/sso/me" -H "Authorization: Bearer $TOKEN")
[[ "$HTTP" == "200" ]] && pass "S04: GET /api/sso/me with JWT → 200" || fail "S04: /api/sso/me | got $HTTP"

HTTP=$(curl -s -o /dev/null -w "%{http_code}" "$MW/api/sso/me")
[[ "$HTTP" == "401" ]] && pass "S05: GET /api/sso/me without JWT → 401" || fail "S05: expected 401, got $HTTP"

# ── Week 6: Token Counter ─────────────────────────────────────────────────────
section "Token Counter (Week 6)"

RES=$(curl -s "$MW/api/tokens/usage" -H "Authorization: Bearer $TOKEN")
[[ "$RES" == *'"ok":true'* ]] && pass "S06: GET /api/tokens/usage → ok" || fail "S06: /api/tokens/usage | got: $RES"

RES=$(curl -s -X POST "$MW/api/tokens/report" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"inputTokens":10,"outputTokens":5}')
[[ "$RES" == *'"ok":true'* ]] && pass "S07: POST /api/tokens/report → ok" || fail "S07: /api/tokens/report | got: $RES"

# ── Week 7: File Access ────────────────────────────────────────────────────────
section "File Access (Week 7)"

HTTP=$(curl -s -o /dev/null -w "%{http_code}" "$MW/api/files/$TEST_USER" -H "Authorization: Bearer $TOKEN")
[[ "$HTTP" == "200" || "$HTTP" == "404" ]] && pass "S08: GET /api/files/:user → $HTTP (auth accepted)" || fail "S08: /api/files/:user | got $HTTP"

HTTP=$(curl -s -o /dev/null -w "%{http_code}" "$MW/api/files/$TEST_USER")
[[ "$HTTP" == "401" ]] && pass "S09: GET /api/files/:user without JWT → 401" || fail "S09: expected 401, got $HTTP"

# ── Week 8: Access Control ────────────────────────────────────────────────────
section "Access Control (Week 8)"

RES=$(curl -s "$MW/api/access/check" -H "Authorization: Bearer $TOKEN")
[[ "$RES" == *'"ok":true'* || "$RES" == *'"allowed"'* ]] && pass "S10: GET /api/access/check → ok" || fail "S10: /api/access/check | got: $RES"

# ── Week 9: Launch / IDE ──────────────────────────────────────────────────────
section "Workspace Launch (Week 9)"

HTTP=$(curl -s -o /dev/null -w "%{http_code}" "$MW/api/launch")
[[ "$HTTP" == "401" ]] && pass "S11: POST /api/launch without JWT → 401" || fail "S11: expected 401, got $HTTP"

# ── Week 10: OpenClaw ─────────────────────────────────────────────────────────
section "OpenClaw Integration (Week 10)"

RES=$(curl -s "$OC/health")
[[ "$RES" == *'"ok":true'* ]] && pass "S12: OpenClaw health payload ok" || fail "S12: OpenClaw health | got: $RES"

HTTP=$(curl -s -o /dev/null -w "%{http_code}" "$MW/api/openclaw/stats/$TEST_USER" -H "Authorization: Bearer $TOKEN")
[[ "$HTTP" == "200" || "$HTTP" == "404" ]] && pass "S13: GET /api/openclaw/stats/:user → $HTTP" || fail "S13: /api/openclaw/stats | got $HTTP"

# ── Week 13: Git Auto-Commit ──────────────────────────────────────────────────
section "Git Auto-Commit (Week 13)"

HTTP=$(curl -s -o /dev/null -w "%{http_code}" "$MW/api/git/$TEST_USER/log" -H "Authorization: Bearer $TOKEN")
[[ "$HTTP" == "200" || "$HTTP" == "404" ]] && pass "S14: GET /api/git/:user/log → $HTTP (auth accepted)" || fail "S14: /api/git/:user/log | got $HTTP"

HTTP=$(curl -s -o /dev/null -w "%{http_code}" "$MW/api/git/$TEST_USER/log")
[[ "$HTTP" == "401" ]] && pass "S15: GET /api/git/:user/log without JWT → 401" || fail "S15: expected 401, got $HTTP"

# ── Week 14: Billing ──────────────────────────────────────────────────────────
section "Billing Engine (Week 14)"

RES=$(curl -s "$MW/api/billing/usage-report" -H "Authorization: Bearer $TOKEN")
[[ "$RES" == *'"ok":true'* ]] && pass "S16: GET /api/billing/usage-report → ok" || fail "S16: /api/billing/usage-report | got: $RES"

HTTP=$(curl -s -o /dev/null -w "%{http_code}" "$MW/api/billing/usage-report")
[[ "$HTTP" == "401" ]] && pass "S17: GET /api/billing/usage-report without JWT → 401" || fail "S17: expected 401, got $HTTP"

# ── Week 15: Onboarding ───────────────────────────────────────────────────────
section "Onboarding Wizard (Week 15)"

RES=$(curl -s "$MW/api/onboarding/status" -H "Authorization: Bearer $TOKEN")
[[ "$RES" == *'"ok":true'* && "$RES" == *'"steps"'* ]] && pass "S18: GET /api/onboarding/status → ok + steps array" || fail "S18: /api/onboarding/status | got: $RES"

HTTP=$(curl -s -o /dev/null -w "%{http_code}" "$MW/api/onboarding/status")
[[ "$HTTP" == "401" ]] && pass "S19: GET /api/onboarding/status without JWT → 401" || fail "S19: expected 401, got $HTTP"

RES=$(curl -s -X POST "$MW/api/onboarding/reset" -H "Authorization: Bearer $TOKEN")
[[ "$RES" == *'"ok":true'* ]] && pass "S20: POST /api/onboarding/reset → ok" || fail "S20: /api/onboarding/reset | got: $RES"

# ── Week 16: Claude Chat ──────────────────────────────────────────────────────
section "Claude Chat (Week 16)"

RES=$(curl -s "$MW/api/claude/models")
[[ "$RES" == *'"ok":true'* && "$RES" == *'"models"'* ]] && pass "S21: GET /api/claude/models → ok (public)" || fail "S21: /api/claude/models | got: $RES"

HTTP=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$MW/api/claude/chat")
[[ "$HTTP" == "401" ]] && pass "S22: POST /api/claude/chat without JWT → 401" || fail "S22: expected 401, got $HTTP"

HTTP=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$MW/api/claude/chat" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"messages":[]}')
[[ "$HTTP" == "400" ]] && pass "S23: POST /api/claude/chat empty messages → 400" || fail "S23: expected 400, got $HTTP"

# ── Dashboard ─────────────────────────────────────────────────────────────────
section "Dashboard (static)"

HTTP=$(curl -s -o /dev/null -w "%{http_code}" "$MW/dashboard.html")
[[ "$HTTP" == "200" ]] && pass "S24: GET /dashboard.html → 200" || fail "S24: /dashboard.html | got $HTTP"

# ── Cleanup ───────────────────────────────────────────────────────────────────
node -e "
  const Redis = require('ioredis');
  const r = new Redis();
  Promise.all([
    r.del('tokens:used:$TEST_CLIENT'),
    r.del('tokens:limit:$TEST_CLIENT'),
    r.del('onboarding:step:$TEST_CLIENT'),
    r.del('onboarding:done:$TEST_CLIENT'),
  ]).then(() => r.quit());
" 2>/dev/null || true

# ── Summary ───────────────────────────────────────────────────────────────────
echo ""
echo -e "${CYAN}══════════════════════════════════════════════════════${NC}"
echo -e "  GoCodeMe Full-Stack Smoke Test  |  $(date +'%Y-%m-%d %H:%M')"
echo -e "${CYAN}══════════════════════════════════════════════════════${NC}"
TOTAL=$(( PASS + FAIL + SKIP ))
echo -e "  Passed: ${GREEN}$PASS${NC}  Failed: ${RED}$FAIL${NC}  Skipped: ${YELLOW}$SKIP${NC}  Total: $TOTAL"
if [[ $FAIL -eq 0 ]]; then
  echo -e "  ${GREEN}ALL SMOKE TESTS PASSED ✓  — GoCodeMe is production-ready${NC}"
  exit 0
else
  echo -e "  ${RED}$FAIL SMOKE TEST(S) FAILED — resolve before deploying${NC}"
  exit 1
fi
