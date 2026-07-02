#!/bin/bash
set -e

echo "=================================================="
echo "    ALFRED LINUX : OFFLINE ASSET STAGING SCRIPT   "
echo "=================================================="

CHROOT_OPT="config/includes.chroot/opt"
mkdir -p "$CHROOT_OPT"

# ---------------------------------------------------------
# 1. THE ALVR VR BRIDGE (Automated Public Download)
# ---------------------------------------------------------
echo "[1/2] Fetching ALVR (Meta Quest 3 Bridge)..."
ALVR_DIR="$CHROOT_OPT/alvr"
if [ ! -d "$ALVR_DIR" ]; then
    echo "Downloading ALVR v20.6.1 directly from GitHub..."
    wget -qO /tmp/alvr.tar.gz "https://github.com/alvr-org/ALVR/releases/download/v20.6.1/alvr_streamer_linux.tar.gz"
    mkdir -p "$ALVR_DIR"
    tar -xzf /tmp/alvr.tar.gz -C "$ALVR_DIR"
    rm /tmp/alvr.tar.gz
    echo "ALVR successfully downloaded and staged into the Golden ISO!"
else
    echo "ALVR already staged."
fi

# ---------------------------------------------------------
# 2. UNREAL ENGINE 5 (Source Code Clone & Compilation)
# ---------------------------------------------------------
echo "[2/2] Fetching Unreal Engine 5 Source Code..."
UE_DIR="$CHROOT_OPT/unreal-engine"

if [ ! -d "$UE_DIR" ]; then
    echo "Attempting to clone Unreal Engine from Epic Games..."
    # Epic Games requires an authenticated GitHub account linked to an Epic account.
    if ssh -o StrictHostKeyChecking=accept-new -T git@github.com 2>&1 | grep -q "successfully authenticated"; then
        echo "GitHub Authentication verified. Cloning Unreal Engine 5.8..."
        git clone -b 5.8 git@github.com:EpicGames/UnrealEngine.git "$UE_DIR"
        
        echo "Compiling Unreal Engine 5.8 from source (This will take hours)..."
        cd "$UE_DIR"
        ./Setup.sh
        ./GenerateProjectFiles.sh
        make
        echo "Unreal Engine 5.8 compiled and successfully staged into the Golden ISO!"
        cd -
    else
        echo "WARNING: GitHub Authentication Failed (Permission denied)."
        echo "Epic Games strictly requires your GitHub account to be linked to your Epic account to download the engine."
        echo "Please add your GitHub SSH key to the 'gositeme' server, then re-run this script."
        echo "Skipping Unreal Engine staging..."
    fi
else
    echo "Unreal Engine already staged."
fi

echo "Asset staging complete."
