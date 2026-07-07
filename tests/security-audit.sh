#!/bin/bash
# ═══════════════════════════════════════════════════════════════
# Alfred Linux — Security Audit Test Suite
# Validates the security hardening stack is intact
# Usage: bash tests/security-audit.sh [chroot-path]
# ═══════════════════════════════════════════════════════════════
set -euo pipefail

CHROOT="${1:-/}"

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
echo "  Alfred Linux — Security Audit Test Suite"
echo "  CIS Level 2 + Sovereign Hardening Validation"
echo "═══════════════════════════════════════════════════════════"
echo ""

# ── Kernel Hardening (sysctl) ──────────────────────────────────
info "Checking kernel hardening parameters..."

declare -A SYSCTL_CHECKS=(
    ["kernel.kptr_restrict"]="2"
    ["kernel.dmesg_restrict"]="1"
    ["kernel.yama.ptrace_scope"]="1"
    ["net.ipv4.conf.all.rp_filter"]="1"
    ["net.ipv4.tcp_syncookies"]="1"
    ["net.ipv4.conf.all.accept_redirects"]="0"
    ["net.ipv4.conf.all.send_redirects"]="0"
    ["net.ipv6.conf.all.accept_redirects"]="0"
    ["kernel.randomize_va_space"]="2"
    ["net.ipv4.icmp_echo_ignore_broadcasts"]="1"
    ["net.ipv4.conf.all.log_martians"]="1"
)

for key in "${!SYSCTL_CHECKS[@]}"; do
    TESTS=$((TESTS+1))
    EXPECTED="${SYSCTL_CHECKS[$key]}"
    ACTUAL=$(sysctl -n "$key" 2>/dev/null || echo "NOT_SET")
    if [ "$ACTUAL" = "$EXPECTED" ]; then
        pass "$key = $ACTUAL"
    elif [ "$ACTUAL" = "NOT_SET" ]; then
        warn "$key not set (expected $EXPECTED)"
    else
        fail "$key = $ACTUAL (expected $EXPECTED)"
    fi
done

# ── SSH Hardening ──────────────────────────────────────────────
info "Checking SSH hardening..."

SSHD_CONFIG="${CHROOT}/etc/ssh/sshd_config"
if [ -f "$SSHD_CONFIG" ]; then
    TESTS=$((TESTS+1))
    if grep -qE '^\s*PermitRootLogin\s+no' "$SSHD_CONFIG" 2>/dev/null; then
        pass "SSH: PermitRootLogin no"
    else
        fail "SSH: PermitRootLogin is not set to 'no'"
    fi

    TESTS=$((TESTS+1))
    if grep -qE '^\s*PasswordAuthentication\s+no' "$SSHD_CONFIG" 2>/dev/null; then
        pass "SSH: PasswordAuthentication no (pubkey-only)"
    else
        warn "SSH: PasswordAuthentication not explicitly disabled"
    fi

    TESTS=$((TESTS+1))
    if grep -qE 'sntrup761' "$SSHD_CONFIG" "${CHROOT}/etc/ssh/sshd_config.d/"* 2>/dev/null; then
        pass "SSH: Post-quantum sntrup761 KEX configured"
    else
        warn "SSH: sntrup761 hybrid key exchange not found in config"
    fi
else
    warn "SSH config not found at $SSHD_CONFIG"
fi

# ── Firewall ───────────────────────────────────────────────────
info "Checking firewall..."

TESTS=$((TESTS+1))
if command -v nft &>/dev/null; then
    if nft list ruleset 2>/dev/null | grep -q 'chain'; then
        pass "nftables: Active ruleset detected"
    else
        warn "nftables: No active rules (may need root)"
    fi
else
    fail "nftables: nft command not found"
fi

# ── AppArmor ───────────────────────────────────────────────────
info "Checking AppArmor..."

TESTS=$((TESTS+1))
if command -v aa-status &>/dev/null; then
    ENFORCED=$(aa-status 2>/dev/null | grep -c 'enforce' || echo 0)
    if [ "$ENFORCED" -gt 0 ]; then
        pass "AppArmor: $ENFORCED profiles in enforce mode"
    else
        warn "AppArmor: No profiles in enforce mode (may need root)"
    fi
else
    if [ -d "${CHROOT}/etc/apparmor.d" ]; then
        PROFILES=$(find "${CHROOT}/etc/apparmor.d" -maxdepth 1 -type f | wc -l)
        pass "AppArmor: $PROFILES profiles installed in /etc/apparmor.d"
    else
        fail "AppArmor: Not installed"
    fi
fi

# ── DNS Configuration ─────────────────────────────────────────
info "Checking DNS (Quad9 primary)..."

TESTS=$((TESTS+1))
RESOLV="${CHROOT}/etc/resolv.conf"
if [ -f "$RESOLV" ]; then
    FIRST_NS=$(grep -m1 'nameserver' "$RESOLV" | awk '{print $2}')
    if [ "$FIRST_NS" = "9.9.9.9" ]; then
        pass "DNS: Primary nameserver is Quad9 (9.9.9.9)"
    else
        warn "DNS: Primary nameserver is $FIRST_NS (expected 9.9.9.9)"
    fi
else
    fail "DNS: /etc/resolv.conf not found"
fi

# ── MAC Randomization ─────────────────────────────────────────
info "Checking MAC randomization..."

TESTS=$((TESTS+1))
NM_CONF="${CHROOT}/etc/NetworkManager/conf.d"
if [ -d "$NM_CONF" ]; then
    if grep -rq 'wifi.scan-rand-mac-address=yes\|ethernet.cloned-mac-address=random\|wifi.cloned-mac-address=random' "$NM_CONF" 2>/dev/null; then
        pass "MAC randomization configured in NetworkManager"
    else
        warn "MAC randomization not found in NetworkManager conf.d"
    fi
else
    warn "NetworkManager conf.d not found"
fi

# ── Post-Quantum Crypto ───────────────────────────────────────
info "Checking post-quantum cryptography..."

TESTS=$((TESTS+1))
if [ -d "${CHROOT}/opt/pq-crypto" ]; then
    pass "Post-quantum toolkit installed at /opt/pq-crypto"
else
    warn "Post-quantum toolkit not found at /opt/pq-crypto"
fi

TESTS=$((TESTS+1))
if command -v kyber-keygen &>/dev/null || [ -f "${CHROOT}/opt/pq-crypto/bin/kyber-keygen" ]; then
    pass "Kyber-1024 keygen binary present"
else
    warn "Kyber-1024 keygen not found in PATH or /opt/pq-crypto/bin"
fi

# ── ZFS ────────────────────────────────────────────────────────
info "Checking ZFS..."

TESTS=$((TESTS+1))
if command -v zpool &>/dev/null; then
    ZFS_VER=$(zfs version 2>/dev/null | head -1 || echo "unknown")
    pass "ZFS installed: $ZFS_VER"
else
    if [ -f "${CHROOT}/sbin/zpool" ]; then
        pass "ZFS binaries present in chroot"
    else
        warn "ZFS not found (may not be loaded)"
    fi
fi

# ── Plymouth Ban ───────────────────────────────────────────────
info "Checking Plymouth splash ban..."

TESTS=$((TESTS+1))
if grep -rq 'splash plymouth.enable=1' "${CHROOT}/etc/default/grub" "${CHROOT}/boot/grub/"* 2>/dev/null; then
    fail "BANNED: 'splash plymouth.enable=1' found in bootloader config"
else
    pass "No 'splash plymouth.enable=1' in bootloader (GPU freeze prevention)"
fi

# ── Omahon Seal Modules ───────────────────────────────────────
info "Checking Omahon Seal..."

TESTS=$((TESTS+1))
OMAHON_DIR="${CHROOT}/opt/omahon-seal"
if [ -d "$OMAHON_DIR" ] || [ -f "${CHROOT}/usr/local/bin/omahon-seal" ]; then
    pass "Omahon Seal installation detected"
else
    warn "Omahon Seal not found at /opt/omahon-seal"
fi

# ── Summary ────────────────────────────────────────────────────
echo ""
echo "═══════════════════════════════════════════════════════════"
echo "  Results: $((TESTS - FAILURES))/$TESTS passed, $WARNINGS warnings"
if [ "$FAILURES" -gt 0 ]; then
    echo -e "  ${RED}$FAILURES FAILURES${NC}"
    exit 1
else
    echo -e "  ${GREEN}ALL SECURITY TESTS PASSED${NC}"
    echo "  The Sovereign Seal holds."
    exit 0
fi
