#!/usr/bin/env bash
# =============================================================================
# GoCodeMe – Week 16: Claude Chat Route Tests
# Usage:  bash scripts/test-claude.sh
# =============================================================================
set -euo pipefail

BASE="http://localhost:3001"
PASS=0
FAIL=0
SKIP=0

# ── colours ──────────────────────────────────────────────────────────────────
GREEN='\033[0;32m'; RED='\033[0;31m'; YELLOW='\033[1;33m'; NC='\033[0m'
pass() { echo -e "${GREEN}✓ PASS${NC} $1"; PASS=$(( PASS + 1 )); }
fail() { echo -e "${RED}✗ FAIL${NC} $1"; FAIL=$(( FAIL + 1 )); }
skip() { echo -e "${YELLOW}⊘ SKIP${NC} $1"; SKIP=$(( SKIP + 1 )); }

# ── helpers ───────────────────────────────────────────────────────────────────
JWT_SECRET="bc65634d1693afdf3b5381e2edd67c2b94418186d5a09e7f2d10f214178809302b8a549180bf1f5d"

# Generate a JWT for a test user
make_jwt() {
  local client_id="${1:-9001}"
  local plan="${2:-professional}"
  node -e "
    const jwt = require('jsonwebtoken');
    const token = jwt.sign(
      { whmcsClientId: '${client_id}', daUsername: 'claude_test_${client_id}', plan: '${plan}' },
      '${JWT_SECRET}',
      { expiresIn: '1h' }
    );
    process.stdout.write(token);
  "
}

TOKEN=$(make_jwt 9001 professional)

# ── service check ─────────────────────────────────────────────────────────────
HEALTH=$(curl -s -o /dev/null -w "%{http_code}" "$BASE/health")
if [[ "$HEALTH" != "200" ]]; then
  echo -e "${RED}FATAL${NC}: Middleware not running on $BASE (HTTP $HEALTH)"
  exit 1
fi
echo "── Middleware up ✓ ──────────────────────────────────────────────────────"

# Check if real Anthropic key is set
ANTHROPIC_KEY=$(node -e "require('dotenv').config({ path: '$(dirname "$0")/../middleware/.env' }); process.stdout.write(process.env.ANTHROPIC_API_KEY || '')" 2>/dev/null || true)
HAS_REAL_KEY=false
if [[ -n "$ANTHROPIC_KEY" && "$ANTHROPIC_KEY" != "YOUR_ANTHROPIC_API_KEY_HERE" ]]; then
  HAS_REAL_KEY=true
fi

echo "── Claude Route Tests ───────────────────────────────────────────────────"

# T01: GET /api/claude/models – no auth – should still work (public endpoint)
RES=$(curl -s "$BASE/api/claude/models")
if echo "$RES" | grep -q '"ok":true' && echo "$RES" | grep -q '"models"'; then
  pass "T01: GET /api/claude/models returns ok + models array"
else
  fail "T01: GET /api/claude/models | got: $RES"
fi

# T02: models response contains default field
if echo "$RES" | grep -q '"default"'; then
  pass "T02: models response contains 'default' field"
else
  fail "T02: models response missing 'default' | got: $RES"
fi

# T03: models response contains claude-sonnet
if echo "$RES" | grep -qi '"claude-sonnet'; then
  pass "T03: models list includes claude-sonnet model"
else
  fail "T03: claude-sonnet not in models | got: $RES"
fi

# T04: POST /api/claude/chat without JWT → 401
HTTP=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE/api/claude/chat" \
  -H "Content-Type: application/json" \
  -d '{"messages":[{"role":"user","content":"hi"}]}')
if [[ "$HTTP" == "401" ]]; then
  pass "T04: POST /api/claude/chat without JWT → 401"
else
  fail "T04: expected 401, got $HTTP"
fi

# T05: POST /api/claude/chat with invalid JWT → 401
HTTP=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE/api/claude/chat" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer not.a.real.token" \
  -d '{"messages":[{"role":"user","content":"hi"}]}')
if [[ "$HTTP" == "401" ]]; then
  pass "T05: POST /api/claude/chat with invalid JWT → 401"
else
  fail "T05: expected 401, got $HTTP"
fi

# T06: POST /api/claude/chat with no body → 400
HTTP=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE/api/claude/chat" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{}')
if [[ "$HTTP" == "400" ]]; then
  pass "T06: POST /api/claude/chat with empty body → 400"
else
  fail "T06: expected 400, got $HTTP"
fi

# T07: POST /api/claude/chat with empty messages array → 400
HTTP=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE/api/claude/chat" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"messages":[]}')
if [[ "$HTTP" == "400" ]]; then
  pass "T07: POST /api/claude/chat with empty messages array → 400"
else
  fail "T07: expected 400, got $HTTP"
fi

# T08: Token limit enforcement (seed a user with exhausted tokens via Redis)
TOKEN_EXHAUSTED=$(make_jwt 9002 starter)
# Set limit=300000 and used=9999999 directly in Redis (simulates exhausted plan)
node -e "
  const Redis = require('ioredis');
  const r = new Redis();
  Promise.all([
    r.set('tokens:limit:9002', '300000'),
    r.set('tokens:used:9002',  '9999999'),
  ]).then(() => r.quit());
" 2>/dev/null || true
sleep 0.3

HTTP=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE/api/claude/chat" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN_EXHAUSTED" \
  -d '{"messages":[{"role":"user","content":"hello"}]}')
if [[ "$HTTP" == "402" ]]; then
  pass "T08: POST /api/claude/chat over token limit → 402"
else
  fail "T08: expected 402 (token limit exceeded), got $HTTP"
fi

# Cleanup exhausted test user
node -e "
  const Redis=require('ioredis');const r=new Redis();
  Promise.all([r.del('tokens:limit:9002'),r.del('tokens:used:9002')]).then(()=>r.quit());
" 2>/dev/null || true

# T09: POST /api/claude/chat – placeholder API key returns 503 (not a crash)
if [[ "$HAS_REAL_KEY" == "false" ]]; then
  HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" -X POST "$BASE/api/claude/chat" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $TOKEN" \
    -d '{"messages":[{"role":"user","content":"ping"}]}')
  RES=$(curl -s -X POST "$BASE/api/claude/chat" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $TOKEN" \
    -d '{"messages":[{"role":"user","content":"ping"}]}')
  if [[ "$HTTP_CODE" == "503" ]] || echo "$RES" | grep -q '"ok":false'; then
    pass "T09: Placeholder API key returns error (not a crash) – HTTP $HTTP_CODE"
  else
    fail "T09: Expected 503 or ok:false for placeholder key | got HTTP $HTTP_CODE: $RES"
  fi
else
  skip "T09: Placeholder key test (real key is set)"
fi

# T10: GET /api/claude/models – response structure validation
RES=$(curl -s "$BASE/api/claude/models")
MODEL_COUNT=$(echo "$RES" | node -e "let d='';process.stdin.on('data',c=>d+=c).on('end',()=>{try{const o=JSON.parse(d);process.stdout.write(String(o.models.length))}catch(e){process.stdout.write('0')}});")
if [[ "$MODEL_COUNT" -ge "1" ]]; then
  pass "T10: /api/claude/models returns at least 1 model (got $MODEL_COUNT)"
else
  fail "T10: Expected ≥1 model, got: $RES"
fi

# T11: Live API call (only if real key is set)
if [[ "$HAS_REAL_KEY" == "true" ]]; then
  echo "── Live API test (real ANTHROPIC_API_KEY detected) ─────────────────────"
  RES=$(curl -s -X POST "$BASE/api/claude/chat" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $TOKEN" \
    -d '{"messages":[{"role":"user","content":"Say only: OK"}],"maxTokens":10}')
  if echo "$RES" | grep -q '"ok":true' && echo "$RES" | grep -q '"content"'; then
    pass "T11: Live Anthropic API call succeeds"
  else
    fail "T11: Live API call failed | got: $RES"
  fi
else
  skip "T11: Live API call (set ANTHROPIC_API_KEY in .env to enable)"
fi

# =============================================================================
echo ""
echo "── Results ──────────────────────────────────────────────────────────────"
TOTAL=$(( PASS + FAIL + SKIP ))
echo -e "  Passed: ${GREEN}$PASS${NC}  Failed: ${RED}$FAIL${NC}  Skipped: ${YELLOW}$SKIP${NC}  Total: $TOTAL"
if [[ $FAIL -eq 0 ]]; then
  echo -e "${GREEN}ALL TESTS PASSED ✓${NC}"
  exit 0
else
  echo -e "${RED}$FAIL TEST(S) FAILED${NC}"
  exit 1
fi
