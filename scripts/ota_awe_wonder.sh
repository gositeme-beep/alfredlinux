#!/bin/bash
# Phase 4: Awe & Wonder (Day 1 OTA Enhancement)
# Run this directly on your fresh Alfred OS boot to enable maximum visual awe.

echo "╔═══════════════════════════════════════════════════════════╗"
echo "║  [PHASE 4] OTA AWE & WONDER INITIATED                     ║"
echo "╚═══════════════════════════════════════════════════════════╝"

export DEBIAN_FRONTEND=noninteractive

echo "=> Installing GLava (Holographic Audio Visualizer)..."
sudo apt-get update
sudo apt-get install -y glava

echo "=> Installing KWin 'Burn-My-Windows' FX..."
# The burn-my-windows package may not be in official debian repos by default.
# It can also be installed via KDE Discover or standard kpackagetool6.
# Let's attempt the easiest route:
sudo apt-get install -y kwin-effects-burn-my-windows || echo "Warning: Please install 'Burn-My-Windows' via KDE Discover."

echo "=> Setting up Ghost in the Shell AI Companion stub..."
# Creating the python script stub for the PyQT6 orb
sudo mkdir -p /usr/local/bin
cat << 'EOF' | sudo tee /usr/local/bin/alfred-ghost-orb.py > /dev/null
#!/usr/bin/env python3
import sys
import time
print("Ghost Orb Initialized. Waiting for AI Core sync...")
while True:
    time.sleep(10)
EOF
sudo chmod +x /usr/local/bin/alfred-ghost-orb.py

echo "=> Preparing 22nd-Century Cyberpunk GRUB Bootloader..."
# Installs a high tech grub theme
sudo apt-get install -y grub2-themes-ubuntu-mate || true

echo "[PHASE 4] OTA COMPLETE. Welcome to the Holodeck."
exit 0
