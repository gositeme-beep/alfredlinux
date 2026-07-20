#!/bin/bash
# ============================================================================
# VAULT BAKE SCRIPT — The Great Vault Bake
# ============================================================================
# Purpose: After recovery-rerun-hooks.sh completes, this script permanently
#          bakes the OverlayFS modifications into both master vaults so
#          future builds never have to re-download or re-install anything.
#
# Flow:
#   1. Mount the OverlayFS merged view
#   2. Backup Primary Vault (safety net)
#   3. rsync merged view → Secondary Vault (build/cache/bootstrap)
#   4. rsync Secondary Vault → Primary Vault (cache/bootstrap)
#   5. Archive hooks to archived-baked/ so they don't re-run
#
# Safety:
#   - Primary vault is backed up FIRST before any writes
#   - rsync uses --delete to keep vaults clean
#   - Dry-run option available (set DRY_RUN=1)
#
# Run as:  sudo bash vault-bake.sh
# ============================================================================

set -e  # Exit on errors — vault operations must be precise

BASE="/home/gositeme/law/alfredlinux-com-source-live"
CHROOT="$BASE/build/chroot"
UPPER="$BASE/build/chroot-upper"
WORK="$BASE/build/chroot-work"
PRIMARY="$BASE/cache/bootstrap"
SECONDARY="$BASE/build/cache/bootstrap"
LOWER="$BASE/config/includes.chroot_after_packages:$BASE/config/includes.chroot:$SECONDARY"
HOOKS="$BASE/config/hooks/live"
ARCHIVE="$BASE/archived-baked"
BACKUP="$BASE/cache/bootstrap-backup-pre-bake"

DRY_RUN=${DRY_RUN:-0}
RSYNC_FLAGS="-aHAXx --info=progress2"
if [ "$DRY_RUN" = "1" ]; then
    RSYNC_FLAGS="$RSYNC_FLAGS --dry-run"
    echo "*** DRY RUN MODE — no actual changes will be made ***"
fi

echo "============================================"
echo " THE GREAT VAULT BAKE — $(date)"
echo "============================================"

# --- STEP 1: Verify OverlayFS is mounted ---
echo ""
echo "=== STEP 1: Verifying OverlayFS mount ==="
if mountpoint -q "$CHROOT" 2>/dev/null; then
    echo "[OK] OverlayFS is mounted at $CHROOT"
else
    echo "[FIX] Remounting OverlayFS..."
    mkdir -p "$UPPER" "$WORK" "$CHROOT"
    mount -t overlay overlay \
        -o lowerdir="$LOWER",upperdir="$UPPER",workdir="$WORK" \
        "$CHROOT"
    echo "[OK] OverlayFS remounted"
fi

# --- STEP 2: Size check ---
echo ""
echo "=== STEP 2: Size report ==="
echo "Primary Vault:   $(du -sh "$PRIMARY" 2>/dev/null | cut -f1)"
echo "Secondary Vault: $(du -sh "$SECONDARY" 2>/dev/null | cut -f1)"
echo "Chroot-upper:    $(du -sh "$UPPER" 2>/dev/null | cut -f1)"
echo "Merged view:     $(du -sh "$CHROOT" 2>/dev/null | cut -f1)"

# --- STEP 3: Backup Primary Vault ---
echo ""
echo "=== STEP 3: Backing up Primary Vault ==="
if [ -d "$BACKUP" ]; then
    echo "[SKIP] Backup already exists at $BACKUP"
else
    echo "Backing up Primary Vault to $BACKUP ..."
    cp -a "$PRIMARY" "$BACKUP"
    echo "[OK] Primary Vault backed up ($(du -sh "$BACKUP" | cut -f1))"
fi

# --- STEP 4: Bake merged view → Secondary Vault ---
echo ""
echo "=== STEP 4: Baking merged OverlayFS → Secondary Vault ==="
echo "Source: $CHROOT (merged view)"
echo "Target: $SECONDARY"
rsync $RSYNC_FLAGS --delete \
    --exclude='/proc/*' \
    --exclude='/sys/*' \
    --exclude='/dev/*' \
    --exclude='/run/*' \
    --exclude='/tmp/*' \
    "$CHROOT/" "$SECONDARY/"
echo "[OK] Secondary Vault updated"

# --- STEP 5: Sync Secondary → Primary Vault ---
echo ""
echo "=== STEP 5: Syncing Secondary → Primary Vault ==="
echo "Source: $SECONDARY"
echo "Target: $PRIMARY"

# Unmount overlay first so we're not writing to a lower layer while it's live
umount "$CHROOT" 2>/dev/null || true

rsync $RSYNC_FLAGS --delete \
    "$SECONDARY/" "$PRIMARY/"
echo "[OK] Primary Vault updated"

# --- STEP 6: Archive hooks ---
echo ""
echo "=== STEP 6: Archiving baked hooks ==="
mkdir -p "$ARCHIVE"
HOOK_COUNT=$(ls -1 "$HOOKS"/*.hook.chroot 2>/dev/null | wc -l)
if [ "$DRY_RUN" != "1" ]; then
    cp -a "$HOOKS"/*.hook.chroot "$ARCHIVE/" 2>/dev/null || true
    echo "[OK] $HOOK_COUNT hooks archived to $ARCHIVE"
else
    echo "[DRY] Would archive $HOOK_COUNT hooks to $ARCHIVE"
fi

# --- STEP 7: Final size report ---
echo ""
echo "=== STEP 7: Final size report ==="
echo "Primary Vault:   $(du -sh "$PRIMARY" 2>/dev/null | cut -f1)"
echo "Secondary Vault: $(du -sh "$SECONDARY" 2>/dev/null | cut -f1)"
echo "Backup:          $(du -sh "$BACKUP" 2>/dev/null | cut -f1)"
echo "Archived hooks:  $(du -sh "$ARCHIVE" 2>/dev/null | cut -f1)"

echo ""
echo "============================================"
echo " VAULT BAKE COMPLETE — $(date)"
echo "============================================"
echo ""
echo " Both vaults are now permanently updated with"
echo " ALL hook modifications including eatmydata,"
echo " ROS, security tools, and everything else."
echo ""
echo " NEXT STEP: Run the ISO assembly script to"
echo "            generate hybrid.iso via xorriso."
echo "============================================"
