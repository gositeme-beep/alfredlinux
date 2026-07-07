#!/bin/bash
# iso-signer-daemon.sh
# Monitors the iso-output directory for the build-complete.marker
# and cryptographically signs the new ISO using the host's GPG key.

set -euo pipefail

REPO="/home/gositeme/law/alfredlinux-com-source-live"
OUTPUT_DIR="$REPO/iso-output"
MARKER="$OUTPUT_DIR/build-complete.marker"

echo "[Signer Daemon] Started. Watching $OUTPUT_DIR for $MARKER..."

while true; do
  if [ -f "$MARKER" ]; then
    echo "[Signer Daemon] Marker detected at $(date -Is)!"
    
    # We sleep briefly to ensure any filesystem caches are fully flushed
    # and the ISO file descriptor from the container has completely closed.
    sleep 5
    
    ISO_PATH="$OUTPUT_DIR/AlfredLinux-Alpha-Matrix-7.77-x86_64.iso"
    
    if [ -f "$ISO_PATH" ]; then
      SIG_PATH="${ISO_PATH}.asc"
      
      # If a previous signature exists, remove it
      if [ -f "$SIG_PATH" ]; then
        echo "[Signer Daemon] Removing old signature..."
# DISABLED-BY-COMMANDER: rm -f "$SIG_PATH"
      fi
      
      echo "[Signer Daemon] Cryptographically signing $ISO_PATH..."
      
      # Sign the ISO. We use the default key of 'gositeme'.
      # We use --batch --yes to never prompt for confirmation.
      # The user's GPG agent should be running or the key should have no passphrase.
      if gpg --batch --yes --armor --detach-sign "$ISO_PATH"; then
        echo "[Signer Daemon] Successfully created signature $SIG_PATH"
      else
        echo "[Signer Daemon] ERROR: Failed to sign ISO!" >&2
      fi
      
    else
      echo "[Signer Daemon] ERROR: Marker found but $ISO_PATH is missing!" >&2
    fi
    
    # Remove the marker so we don't trigger again until the next build
# DISABLED-BY-COMMANDER: rm -f "$MARKER"
    echo "[Signer Daemon] Marker cleared. Waiting for next build..."
  fi
  
  sleep 10
done
