#!/bin/bash
# ═══════════════════════════════════════════════════════════════
# Alfred Linux — Automated Boot Test via QEMU
# Tests that the ISO boots to a login prompt within 120 seconds
# Usage: bash tests/boot-test.sh path/to/alfred.iso
# ═══════════════════════════════════════════════════════════════
set -euo pipefail

ISO="${1:-}"
TIMEOUT=120
LOGFILE="/tmp/alfred-boot-test-$$.log"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

pass() { echo -e "${GREEN}[PASS]${NC} $1"; }
fail() { echo -e "${RED}[FAIL]${NC} $1"; FAILURES=$((FAILURES+1)); }
info() { echo -e "${YELLOW}[INFO]${NC} $1"; }

FAILURES=0
TESTS=0

echo "═══════════════════════════════════════════════════════════"
echo "  Alfred Linux — Automated Boot Test"
echo "═══════════════════════════════════════════════════════════"

if [ -z "$ISO" ]; then
    fail "No ISO path provided. Usage: $0 path/to/iso"
    exit 1
fi

if [ ! -f "$ISO" ]; then
    fail "ISO not found: $ISO"
    exit 1
fi

TESTS=$((TESTS+1))
pass "ISO exists: $ISO ($(du -h "$ISO" | cut -f1))"

if ! command -v qemu-system-x86_64 &>/dev/null; then
    fail "qemu-system-x86_64 not found. Install: apt install qemu-system-x86"
    exit 1
fi
TESTS=$((TESTS+1))
pass "QEMU available"

# ── Boot test ───────────────────────────────────────────────────
info "Booting ISO in headless QEMU (timeout: ${TIMEOUT}s)..."

timeout "$TIMEOUT" qemu-system-x86_64 \
    -m 4096 \
    -smp 2 \
    -cdrom "$ISO" \
    -boot d \
    -nographic \
    -serial file:"$LOGFILE" \
    -no-reboot \
    2>/dev/null &

QEMU_PID=$!
BOOT_SUCCESS=false

for i in $(seq 1 "$TIMEOUT"); do
    if [ -f "$LOGFILE" ]; then
        if grep -qiE 'login:|lightdm|gdm|sddm|alfred.*started|systemd.*reached.*graphical' "$LOGFILE" 2>/dev/null; then
            BOOT_SUCCESS=true
            break
        fi
        if grep -qi 'kernel panic' "$LOGFILE" 2>/dev/null; then
            fail "Kernel panic detected"
            kill "$QEMU_PID" 2>/dev/null || true
            break
        fi
    fi
    sleep 1
done

kill "$QEMU_PID" 2>/dev/null || true
wait "$QEMU_PID" 2>/dev/null || true

TESTS=$((TESTS+1))
if [ "$BOOT_SUCCESS" = true ]; then
    pass "ISO boots to login/desktop within ${TIMEOUT}s"
else
    fail "ISO did not reach login/desktop within ${TIMEOUT}s"
fi

# ── Check kernel version ───────────────────────────────────────
TESTS=$((TESTS+1))
if grep -q '7.0.12' "$LOGFILE" 2>/dev/null; then
    pass "Kernel 7.0.12 detected in boot log"
else
    fail "Kernel 7.0.12 not found in boot log"
fi

# ── Check for critical errors ──────────────────────────────────
TESTS=$((TESTS+1))
CRITICAL_ERRORS=$(grep -ciE 'fatal|critical|emergency' "$LOGFILE" 2>/dev/null || echo 0)
if [ "$CRITICAL_ERRORS" -eq 0 ]; then
    pass "No fatal/critical/emergency errors in boot log"
else
    fail "Found $CRITICAL_ERRORS critical errors in boot log"
fi

# ── Summary ────────────────────────────────────────────────────
echo ""
echo "═══════════════════════════════════════════════════════════"
echo "  Results: $((TESTS - FAILURES))/$TESTS passed"
if [ "$FAILURES" -gt 0 ]; then
    echo -e "  ${RED}$FAILURES FAILURES${NC}"
    echo "  Boot log: $LOGFILE"
    exit 1
else
    echo -e "  ${GREEN}ALL TESTS PASSED${NC}"
    rm -f "$LOGFILE"
    exit 0
fi
