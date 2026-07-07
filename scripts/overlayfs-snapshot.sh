#!/usr/bin/env bash
set -e

ACTION=\
REPO="/work"
LOWER="\/build/chroot-lower"
UPPER="\/build/chroot-upper"
WORK="\/build/chroot-work"
CHROOT="\/build/chroot"

if [ "\" == "lock" ]; then
    if [ ! -d "\" ]; then
        echo "Error: \ does not exist."
        exit 1
    fi
    if [ -d "\" ]; then
        echo "Already locked. Use rollback."
        exit 0
    fi
    echo "Locking chroot into OverlayFS..."
    mv "\" "\"
    mkdir -p "\" "\" "\"
    mount -t overlay overlay -o lowerdir="\",upperdir="\",workdir="\" "\"
    echo "OverlayFS lock active."
elif [ "\" == "rollback" ]; then
    if [ ! -d "\" ]; then
        echo "Error: Lower directory not found."
        exit 1
    fi
    echo "Rolling back chroot changes..."
    umount "\" || true
# DISABLED-v14.14:     rm -rf "\"/* "\"/*
    mount -t overlay overlay -o lowerdir="\",upperdir="\",workdir="\" "\"
    echo "Rollback complete. Chroot is pristine."
elif [ "\" == "commit" ]; then
    echo "OverlayFS commit requires manual rsync. (Not implemented)"
else
    echo "Usage: \ {lock|rollback}"
    exit 1
fi