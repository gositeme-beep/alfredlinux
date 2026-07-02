#!/bin/bash
# Day 1 OTA: KDE Plasma 6 Holographic Upgrades
# Run this script on your fresh boot to push Plasma 6 to its extreme limits.

echo "╔═══════════════════════════════════════════════════════════╗"
echo "║  [PHASE 4.5] PLASMA 6 HOLOGRAPHIC UPGRADES INITIATED      ║"
echo "╚═══════════════════════════════════════════════════════════╝"

export DEBIAN_FRONTEND=noninteractive

echo "=> Installing Kvantum Engine (Translucent UI Glass)..."
# Kvantum allows us to bypass standard Plasma themes and render the entire UI 
# as heavily blurred, translucent glass (perfect for AR/XR).
sudo apt-get update
sudo apt-get install -y qt6-kvantum qt6-kvantum-themes

echo "=> Compiling the legendary Plasma 6 3D Desktop Cube..."
# KDE officially removed the Desktop Cube in Plasma 6. 
# We are downloading the source code for the 3rd-party port and compiling it natively!
sudo apt-get install -y git cmake g++ extra-cmake-modules qt6-base-dev \
    libkf6config-dev libkf6coreaddons-dev libkf6globalaccel-dev \
    libkf6windowsystem-dev kwin-dev qt6-declarative-dev || true

cd /tmp
git clone https://github.com/zzag/kwin-effects-cube.git || true
cd kwin-effects-cube
mkdir build && cd build
cmake ..
make -j$(nproc)
sudo make install
cd ~

echo "=> Configuring Holographic 'Ghost Text' Terminal..."
# This makes the terminal background almost completely invisible but heavily blurred,
# leaving only floating green text suspended over your wallpaper.
mkdir -p ~/.local/share/konsole
cat << 'EOF' > ~/.local/share/konsole/Holographic.profile
[Appearance]
ColorScheme=HolographicMatrix
Font=Hack,12,-1,5,50,0,0,0,0,0

[General]
Name=Holographic

[Scrolling]
HistoryMode=2
EOF

cat << 'EOF' > ~/.local/share/konsole/HolographicMatrix.colorscheme
[Background]
Color=0,0,0
Transparency=true
Opacity=0.15

[Color0]
Color=0,255,100
EOF

echo "=> Injecting Space Parallax Wallpaper engine..."
sudo apt-get install -y plasma6-wallpapers-dynamic || echo "Install via KDE Store."

echo "╔═══════════════════════════════════════════════════════════╗"
echo "║ [PHASE 4.5] PLASMA 6 HOLOGRAPHIC UPGRADES COMPLETE.       ║"
echo "║ Please log out and log back in to apply the 3D Shaders!   ║"
echo "╚═══════════════════════════════════════════════════════════╝"
exit 0
