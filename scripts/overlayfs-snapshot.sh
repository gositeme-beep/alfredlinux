#!/usr/bin/env bash
set -e

ACTION=$1
REPO=$(pwd)

# Use the master cache directly as the lowerdir
LOWER="$REPO/cache/bootstrap"
UPPER="$REPO/build/chroot-upper"
WORK="$REPO/build/chroot-work"
CHROOT="$REPO/build/chroot"

if [ "$ACTION" == "lock" ]; then
    if [ ! -d "$LOWER" ]; then
        echo "Error: Lower directory ($LOWER) does not exist."
        exit 1
    fi
    # If CHROOT is already mounted, unmount it first
    if mountpoint -q "$CHROOT"; then
        echo "Already locked. Unmounting first..."
        sudo umount "$CHROOT" || true
    fi
    
    echo "Locking chroot into OverlayFS..."
    # Ensure directories exist
    mkdir -p "$UPPER" "$WORK" "$CHROOT"
    
    # Nuke upper and work to ensure a pristine slate
    sudo rm -rf "${UPPER:?}"/* "${WORK:?}"/* 2>/dev/null || true
    
    # Mount overlay
    sudo mount -t overlay overlay -o lowerdir="$LOWER",upperdir="$UPPER",workdir="$WORK" "$CHROOT"
    echo "OverlayFS lock active."
    
elif [ "$ACTION" == "rollback" ]; then
    if [ ! -d "$LOWER" ]; then
        echo "Error: Lower directory not found."
        exit 1
    fi
    echo "Rolling back chroot changes..."
    if mountpoint -q "$CHROOT"; then
        sudo umount "$CHROOT" || true
    fi
    sudo rm -rf "${UPPER:?}"/* "${WORK:?}"/* 2>/dev/null || true
    sudo mount -t overlay overlay -o lowerdir="$LOWER",upperdir="$UPPER",workdir="$WORK" "$CHROOT"
    echo "Rollback complete. Chroot is pristine."
    
elif [ "$ACTION" == "teardown" ]; then
    if mountpoint -q "$CHROOT"; then
        echo "Tearing down OverlayFS mount..."
        sudo umount "$CHROOT" || true
    fi
    sudo rm -rf "$UPPER" "$WORK" "$CHROOT" 2>/dev/null || true
    echo "Teardown complete."
else
    echo "Usage: $0 {lock|rollback|teardown}"
    exit 1
fi
