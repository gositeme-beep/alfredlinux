#!/bin/bash
BASE="https://gositeme.com/api/agentos"
SECRET="3996f0ac32cdfb8c3159b653f512efdf0dacf0582d7a75e30af4ef650c6d060d"
PASS=0; FAIL=0; TOTAL=0

test_endpoint() {
    local name="$1" method="$2" url="$3" data="$4"
    TOTAL=$((TOTAL+1))
    if [ "$method" = "GET" ]; then
        RESP=$(curl -sk -H "X-Internal-Secret: $SECRET" "$url" 2>/dev/null)
    else
        RESP=$(curl -sk -X POST -H "X-Internal-Secret: $SECRET" -H "Content-Type: application/json" -d "$data" "$url" 2>/dev/null)
    fi
    if echo "$RESP" | grep -q '"ok":true'; then
        echo "  PASS: $name"
        PASS=$((PASS+1))
    else
        echo "  FAIL: $name"
        echo "   -> $(echo "$RESP" | head -c 200)"
        FAIL=$((FAIL+1))
    fi
}

echo "========================================"
echo "  AgentOS E2E Test Suite"
echo "========================================"
echo ""
echo "--- Capabilities ---"
test_endpoint "List" "GET" "$BASE/capabilities.php?action=list"
echo ""
echo "--- Skills ---"
test_endpoint "List" "GET" "$BASE/skills.php?action=list"
test_endpoint "Create" "POST" "$BASE/skills.php?action=create" '{"display_name":"E2E Skill","description":"test","category":"testing"}'
echo ""
echo "--- Tasks ---"
test_endpoint "List" "GET" "$BASE/tasks.php?action=list"
test_endpoint "Create" "POST" "$BASE/tasks.php?action=create" '{"goal":"E2E test task","priority":5}'
echo ""
echo "--- Memory ---"
test_endpoint "Store Episodic" "POST" "$BASE/memory.php?action=store&type=episodic" '{"episode_type":"test","summary":"E2E memory","details":"{\"t\":1}","outcome":"success","importance":5}'
test_endpoint "Recall Episodic" "GET" "$BASE/memory.php?action=recall&type=episodic"
test_endpoint "Stats" "GET" "$BASE/memory.php?action=stats"
test_endpoint "Search" "GET" "$BASE/memory.php?action=search&q=test"
test_endpoint "Consolidate" "POST" "$BASE/memory.php?action=consolidate" '{}'
echo ""
echo "--- World State ---"
test_endpoint "Get State" "GET" "$BASE/world-state.php?action=get"
test_endpoint "Update" "POST" "$BASE/world-state.php?action=update" '{"updates":[{"key":"e2e_test","value":"working","type":"string"}]}'
test_endpoint "Entities" "GET" "$BASE/world-state.php?action=entities"
echo ""
echo "--- Policy ---"
test_endpoint "List" "GET" "$BASE/policy.php?action=list"
test_endpoint "Check" "POST" "$BASE/policy.php?action=check" '{"capability_id":"cap_web_search","risk_level":"low"}'
echo ""
echo "--- Simulation ---"
test_endpoint "Run" "POST" "$BASE/simulation.php?action=run" '{"capability_id":"cap_web_search","input":{"query":"test"}}'
test_endpoint "List" "GET" "$BASE/simulation.php?action=list"
echo ""
echo "--- Audit ---"
test_endpoint "Stats" "GET" "$BASE/audit.php?action=stats"
test_endpoint "List" "GET" "$BASE/audit.php?action=list"
echo ""
echo "--- Bridge ---"
test_endpoint "List Devices" "GET" "$BASE/bridge.php?action=list"
echo ""
echo "========================================"
echo "  Results: $PASS/$TOTAL passed, $FAIL failed"
echo "========================================"
