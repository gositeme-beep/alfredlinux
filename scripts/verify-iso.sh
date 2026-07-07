#!/usr/bin/env bash
set -e

ISO_PATH="$1"
if [ -z "$ISO_PATH" ] || [ ! -f "$ISO_PATH" ]; then
    echo "Usage: $0 <path-to-iso>"
    exit 1
fi

echo "[QEMU-VERIFY] Initiating headless ISO verification..."
echo "[QEMU-VERIFY] ISO Target: $ISO_PATH"

LOG_FILE="/tmp/qemu_verify_$(date +%s).log"
# Start QEMU in the background, redirecting serial output to a log file
qemu-system-x86_64 -m 4G -cdrom "$ISO_PATH" -nographic \
    -append "console=ttyS0 quiet loglevel=3" \
    -serial file:"$LOG_FILE" >/dev/null 2>&1 &
QEMU_PID=$!

echo "[QEMU-VERIFY] QEMU booted (PID: $QEMU_PID). Waiting up to 180 seconds for kernel signature..."

found_kernel=0
for i in {1..18}; do
    sleep 10
    if grep -q "Linux version 7.0.12-1alfred" "$LOG_FILE" 2>/dev/null; then
        echo "[QEMU-VERIFY] SUCCESS: Custom Kernel 7.0.12 signature detected in boot logs!"
        found_kernel=1
        break
    fi
done

echo "[QEMU-VERIFY] Terminating VM..."
kill -9 $QEMU_PID || true

if [ $found_kernel -eq 1 ]; then
    echo "[QEMU-VERIFY] ISO Validation PASSED."
    exit 0
else
    echo "[QEMU-VERIFY] ERROR: Failed to detect 7.0.12 kernel boot signature."
    exit 1
fi