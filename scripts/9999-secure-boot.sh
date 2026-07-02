#!/usr/bin/env bash
set -e

echo "[SECURE-BOOT] Initiating UEFI MOK Generation and Kernel Signing..."
cd /work

if [ ! -d "build/binary/live" ]; then
    echo "[SECURE-BOOT] Error: build/binary/live directory not found."
    exit 0
fi

if [ ! -f "MOK.priv" ]; then
    echo "[SECURE-BOOT] Generating self-signed MOK certificate..."
    openssl req -new -x509 -newkey rsa:2048 -keyout MOK.priv -outform DER -out MOK.der -nodes -days 36500 -subj "/CN=Alfred Linux Secure Boot/" 2>/dev/null || true
fi

for vmlinuz in build/binary/live/vmlinuz*; do
    if [ -f "$vmlinuz" ]; then
        echo "[SECURE-BOOT] Signing kernel: $vmlinuz"
        sbsign --key MOK.priv --cert MOK.der --output "$vmlinuz" "$vmlinuz" 2>/dev/null || echo "[SECURE-BOOT] Warning: sbsign failed or missing. Continuing..."
    fi
done

mkdir -p build/binary/boot/efi
cp MOK.der build/binary/boot/efi/alfred-secure-boot.der 2>/dev/null || true
echo "[SECURE-BOOT] Public MOK copied to /boot/efi/alfred-secure-boot.der for user enrollment."