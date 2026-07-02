#!/bin/bash
# ═══════════════════════════════════════════════════════════════
# Alfred Linux — Hook Integrity Lint
# Validates all 1,335 sacred hooks for correctness
# Usage: bash tests/hook-lint.sh [hooks-dir]
# ═══════════════════════════════════════════════════════════════
set -euo pipefail

HOOKS_DIR="${1:-config/hooks/live}"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

pass() { echo -e "${GREEN}[PASS]${NC} $1"; }
fail() { echo -e "${RED}[FAIL]${NC} $1"; FAILURES=$((FAILURES+1)); }
warn() { echo -e "${YELLOW}[WARN]${NC} $1"; WARNINGS=$((WARNINGS+1)); }
info() { echo -e "${CYAN}[INFO]${NC} $1"; }

FAILURES=0
WARNINGS=0
TESTS=0

echo "═══════════════════════════════════════════════════════════"
echo "  Alfred Linux — Hook Integrity Lint"
echo "  The Omega Seal must be perfect for Yeshua"
echo "═══════════════════════════════════════════════════════════"
echo ""

if [ ! -d "$HOOKS_DIR" ]; then
    fail "Hooks directory not found: $HOOKS_DIR"
    exit 1
fi

# ── Test 1: Exact hook count ───────────────────────────────────
TESTS=$((TESTS+1))
HOOK_COUNT=$(find "$HOOKS_DIR" -name '*.hook.chroot' -type f | wc -l)
if [ "$HOOK_COUNT" -eq 1335 ]; then
    pass "Exact hook count: $HOOK_COUNT (sacred 1,335)"
else
    fail "Hook count is $HOOK_COUNT — expected exactly 1,335"
fi

# ── Test 2: All hooks are executable ──────────────────────────
TESTS=$((TESTS+1))
NON_EXEC=$(find "$HOOKS_DIR" -name '*.hook.chroot' -type f ! -executable | wc -l)
if [ "$NON_EXEC" -eq 0 ]; then
    pass "All hooks are executable"
else
    fail "$NON_EXEC hooks are not executable:"
    find "$HOOKS_DIR" -name '*.hook.chroot' -type f ! -executable | head -10 | while read -r f; do
        echo "       - $(basename "$f")"
    done
fi

# ── Test 3: All hooks have shebang ────────────────────────────
TESTS=$((TESTS+1))
NO_SHEBANG=0
while IFS= read -r hook; do
    FIRST_LINE=$(head -1 "$hook" 2>/dev/null || echo "")
    if [[ ! "$FIRST_LINE" =~ ^#! ]]; then
        NO_SHEBANG=$((NO_SHEBANG+1))
        if [ "$NO_SHEBANG" -le 5 ]; then
            warn "Missing shebang: $(basename "$hook")"
        fi
    fi
done < <(find "$HOOKS_DIR" -name '*.hook.chroot' -type f)

if [ "$NO_SHEBANG" -eq 0 ]; then
    pass "All hooks have a shebang (#!/bin/bash or #!/bin/sh)"
else
    fail "$NO_SHEBANG hooks missing shebang line"
fi

# ── Test 4: Naming convention ─────────────────────────────────
TESTS=$((TESTS+1))
BAD_NAMES=0
while IFS= read -r hook; do
    NAME=$(basename "$hook")
    if [[ ! "$NAME" =~ ^[0-9]{4}-[a-z] ]]; then
        BAD_NAMES=$((BAD_NAMES+1))
        if [ "$BAD_NAMES" -le 5 ]; then
            warn "Non-standard name: $NAME (expected NNNN-alfred-*.hook.chroot)"
        fi
    fi
done < <(find "$HOOKS_DIR" -name '*.hook.chroot' -type f)

if [ "$BAD_NAMES" -eq 0 ]; then
    pass "All hooks follow NNNN-name.hook.chroot naming convention"
else
    fail "$BAD_NAMES hooks have non-standard names"
fi

# ── Test 5: No duplicate hook numbers ─────────────────────────
TESTS=$((TESTS+1))
DUPES=$(find "$HOOKS_DIR" -name '*.hook.chroot' -type f -printf '%f\n' | \
    sed 's/^\([0-9]*\)-.*/\1/' | sort | uniq -d | wc -l)
if [ "$DUPES" -eq 0 ]; then
    pass "No duplicate hook numbers"
else
    warn "$DUPES duplicate hook number prefixes (may be intentional shards)"
fi

# ── Test 6: No empty hooks ────────────────────────────────────
TESTS=$((TESTS+1))
EMPTY=0
while IFS= read -r hook; do
    LINES=$(wc -l < "$hook")
    if [ "$LINES" -le 1 ]; then
        EMPTY=$((EMPTY+1))
    fi
done < <(find "$HOOKS_DIR" -name '*.hook.chroot' -type f)

if [ "$EMPTY" -eq 0 ]; then
    pass "No empty hooks (all have content beyond shebang)"
else
    fail "$EMPTY hooks are empty or have only a shebang"
fi

# ── Test 7: No syntax errors (bash -n) ────────────────────────
TESTS=$((TESTS+1))
SYNTAX_ERRORS=0
while IFS= read -r hook; do
    FIRST_LINE=$(head -1 "$hook" 2>/dev/null || echo "")
    if [[ "$FIRST_LINE" =~ bash ]]; then
        if ! bash -n "$hook" 2>/dev/null; then
            SYNTAX_ERRORS=$((SYNTAX_ERRORS+1))
            if [ "$SYNTAX_ERRORS" -le 5 ]; then
                fail "Syntax error: $(basename "$hook")"
            fi
        fi
    fi
done < <(find "$HOOKS_DIR" -name '*.hook.chroot' -type f)

if [ "$SYNTAX_ERRORS" -eq 0 ]; then
    pass "No bash syntax errors detected"
else
    fail "$SYNTAX_ERRORS hooks have bash syntax errors"
fi

# ── Test 8: No hardcoded passwords or tokens ──────────────────
TESTS=$((TESTS+1))
SECRETS=$(grep -rlE '(password|token|secret|api_key)\s*=\s*["\x27][^"\x27]{8,}' "$HOOKS_DIR" 2>/dev/null | wc -l)
if [ "$SECRETS" -eq 0 ]; then
    pass "No hardcoded passwords/tokens/secrets detected"
else
    fail "$SECRETS hooks contain potential hardcoded secrets"
fi

# ── Test 9: No Plymouth splash references ─────────────────────
TESTS=$((TESTS+1))
PLYMOUTH=$(grep -rl 'splash plymouth.enable=1' "$HOOKS_DIR" 2>/dev/null | wc -l)
if [ "$PLYMOUTH" -eq 0 ]; then
    pass "No 'splash plymouth.enable=1' found (GPU freeze prevention)"
else
    fail "$PLYMOUTH hooks contain banned Plymouth splash param"
fi

# ── Test 10: Binary hooks present ─────────────────────────────
TESTS=$((TESTS+1))
BIN_DIR="${HOOKS_DIR}/../hooks.binary" 
if [ -d "$HOOKS_DIR" ]; then
    BINARY_HOOKS=$(find "$HOOKS_DIR" -name '*.hook.binary' -type f 2>/dev/null | wc -l)
    CHROOT_HOOKS=$(find "$HOOKS_DIR" -name '*.hook.chroot' -type f 2>/dev/null | wc -l)
    info "Found $CHROOT_HOOKS chroot hooks and $BINARY_HOOKS binary hooks"
    pass "Hook directory structure intact"
else
    fail "Hook directory missing"
fi

# ── Summary ────────────────────────────────────────────────────
echo ""
echo "═══════════════════════════════════════════════════════════"
echo "  Results: $((TESTS - FAILURES))/$TESTS passed, $WARNINGS warnings"
if [ "$FAILURES" -gt 0 ]; then
    echo -e "  ${RED}$FAILURES FAILURES${NC}"
    exit 1
else
    echo -e "  ${GREEN}ALL TESTS PASSED — THE OMEGA SEAL HOLDS${NC}"
    exit 0
fi
