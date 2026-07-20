#!/bin/bash
# ============================================================================
# RECOVERY HOOK RE-RUN SCRIPT
# ============================================================================
# Purpose: After the build crashes at Step 7.9 (Trap 138), this script
#          re-mounts the OverlayFS and re-runs ALL hooks so that every
#          'eatmydata apt-get install' call succeeds (since eatmydata is
#          now installed from our 1335 fix).
#
# Safety:  - Read-only vaults are NEVER touched directly
#          - All writes go to chroot-upper (OverlayFS upper layer)
#          - Hooks are idempotent (already-installed packages just say
#            "already the newest version")
#
# Run as:  sudo bash recovery-rerun-hooks.sh
# ============================================================================

set +e  # Don't exit on errors — match the build behavior

BASE="/home/gositeme/law/alfredlinux-com-source-live"
CHROOT="$BASE/build/chroot"
UPPER="$BASE/build/chroot-upper"
WORK="$BASE/build/chroot-work"
HOOKS="$BASE/config/hooks/live"
LOWER="$BASE/config/includes.chroot_after_packages:$BASE/config/includes.chroot:$BASE/build/cache/bootstrap"

echo "============================================"
echo " RECOVERY HOOK RE-RUN — $(date)"
echo "============================================"

# --- STEP 1: Ensure OverlayFS is mounted ---
echo ""
echo "=== STEP 1: Checking OverlayFS mount ==="
if mountpoint -q "$CHROOT" 2>/dev/null; then
    echo "[OK] OverlayFS is already mounted at $CHROOT"
else
    echo "[FIX] Remounting OverlayFS..."
    mkdir -p "$UPPER" "$WORK" "$CHROOT"
    mount -t overlay overlay \
        -o lowerdir="$LOWER",upperdir="$UPPER",workdir="$WORK" \
        "$CHROOT"
    if [ $? -eq 0 ]; then
        echo "[OK] OverlayFS remounted successfully"
    else
        echo "[FATAL] Failed to mount OverlayFS — cannot continue"
        exit 1
    fi
fi

# --- STEP 2: Mount virtual filesystems for chroot ---
echo ""
echo "=== STEP 2: Mounting virtual filesystems ==="
mount -t proc proc "$CHROOT/proc" 2>/dev/null || true
mount -t sysfs sysfs "$CHROOT/sys" 2>/dev/null || true
mount -o bind /dev "$CHROOT/dev" 2>/dev/null || true
mount -o bind /dev/pts "$CHROOT/dev/pts" 2>/dev/null || true

# Ensure DNS works inside chroot
cp /etc/resolv.conf "$CHROOT/etc/resolv.conf" 2>/dev/null || true

echo "[OK] Virtual filesystems mounted"

# --- STEP 3: Verify eatmydata is present ---
echo ""
echo "=== STEP 3: Verifying eatmydata is installed ==="
if chroot "$CHROOT" which eatmydata >/dev/null 2>&1; then
    echo "[OK] eatmydata is present — all hooks will work"
else
    echo "[FIX] Installing eatmydata first..."
    chroot "$CHROOT" apt-get update
    chroot "$CHROOT" apt-get install -y eatmydata
    if chroot "$CHROOT" which eatmydata >/dev/null 2>&1; then
        echo "[OK] eatmydata installed successfully"
    else
        echo "[FATAL] Cannot install eatmydata — DNS or apt broken"
        exit 1
    fi
fi

# --- STEP 4: Re-run ALL hooks in order ---
echo ""
echo "=== STEP 4: Re-running ALL hooks ==="
TOTAL=0
SUCCESS=0
FAILED=0
FAILED_LIST=""

for hook in "$HOOKS"/*.hook.chroot; do
    [ -f "$hook" ] || continue
    HOOKNAME=$(basename "$hook")
    TOTAL=$((TOTAL + 1))

    echo ""
    echo "--- [$TOTAL] Running: $HOOKNAME ---"

    # Copy hook into chroot and execute
    cp "$hook" "$CHROOT/tmp/run_hook.sh"
    chmod +x "$CHROOT/tmp/run_hook.sh"

    chroot "$CHROOT" /bin/bash /tmp/run_hook.sh
    RESULT=$?

    rm -f "$CHROOT/tmp/run_hook.sh"

    if [ $RESULT -eq 0 ]; then
        SUCCESS=$((SUCCESS + 1))
        echo "--- [$HOOKNAME] OK ---"
    else
        FAILED=$((FAILED + 1))
        FAILED_LIST="$FAILED_LIST $HOOKNAME"
        echo "--- [$HOOKNAME] FAILED (exit $RESULT) — continuing ---"
    fi
done

# --- STEP 5: Unmount virtual filesystems ---
echo ""
echo "=== STEP 5: Unmounting virtual filesystems ==="
umount -l "$CHROOT/dev/pts" 2>/dev/null || true
umount -l "$CHROOT/dev" 2>/dev/null || true
umount -l "$CHROOT/sys" 2>/dev/null || true
umount -l "$CHROOT/proc" 2>/dev/null || true
echo "[OK] Virtual filesystems unmounted"

# --- STEP 6: Report ---
echo ""
echo "============================================"
echo " RECOVERY COMPLETE — $(date)"
echo "============================================"
echo " Total hooks:    $TOTAL"
echo " Succeeded:      $SUCCESS"
echo " Failed:         $FAILED"
if [ -n "$FAILED_LIST" ]; then
    echo " Failed hooks:  $FAILED_LIST"
fi
echo ""
echo " OverlayFS is still mounted at $CHROOT"
echo " chroot-upper has all modifications at $UPPER"
echo ""
echo " NEXT STEP: Run the vault-bake script to permanently"
echo "            sync chroot-upper into the master vaults."
echo "============================================"
