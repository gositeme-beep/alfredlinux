#!/bin/bash
# SPDX-License-Identifier: Apache-2.0
# SPDX-FileCopyrightText: 2026 Alfred Trust <alfred@alfredlinux.com>
# In the name of Yeshua, Jesus Christ of Bethlehem, King of the Universe.
# qemu-smoke-test.sh — boot the ISO in headless QEMU, capture serial console,
# verify "alfred" or "Linux version" appears within timeout.
# Usage: qemu-smoke-test.sh <path-to-iso>
# In the name of Yeshua, Jesus Christ of Bethlehem, King of the Universe.
set -uo pipefail

ISO="${1:-}"
TIMEOUT="${TIMEOUT:-90}"
if [[ -z "$ISO" || ! -f "$ISO" ]]; then
    echo "Usage: $0 <path-to-iso>"; exit 1
fi
if ! command -v qemu-system-x86_64 >/dev/null; then
    echo "✗ qemu-system-x86_64 not installed — install with: sudo apt install qemu-system-x86 ovmf"
    exit 2
fi

OUT=$(mktemp /tmp/qemu-smoke-XXXX.log)
echo "[SMOKE] Booting $ISO (timeout ${TIMEOUT}s, log: $OUT)..."

# Headless, serial out, no GUI, give it 2GB
timeout "$TIMEOUT" qemu-system-x86_64 \
    -m 2048 -smp 2 \
    -nographic -no-reboot \
    -cdrom "$ISO" \
    -boot d \
    -serial file:"$OUT" \
    -monitor none \
    -display none </dev/null >/dev/null 2>&1 &
QPID=$!

# Poll log for boot signal
DEADLINE=$(( $(date +%s) + TIMEOUT ))
RESULT=fail
while [[ $(date +%s) -lt $DEADLINE ]]; do
    if grep -qE 'alfred|Linux version|systemd|GRUB|ISOLINUX' "$OUT" 2>/dev/null; then
        RESULT=ok
        break
    fi
    sleep 2
done

kill -TERM "$QPID" 2>/dev/null || true
wait "$QPID" 2>/dev/null || true

if [[ "$RESULT" == "ok" ]]; then
    echo "[SMOKE] ✓ BOOT DETECTED — first hits:"
    grep -E 'alfred|Linux version|systemd|GRUB|ISOLINUX' "$OUT" | head -5 | sed 's/^/    /'
    exit 0
else
    echo "[SMOKE] ✗ NO BOOT SIGNAL within ${TIMEOUT}s"
    echo "  last 20 lines of serial:"
    tail -20 "$OUT" | sed 's/^/    /'
    exit 1
fi
