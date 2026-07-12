#!/bin/bash
##############################################################################
# POST-SQUASHFS ISO FINALIZATION SCRIPT
# Run this AFTER mksquashfs completes successfully.
# 
# What it does:
#   1. Verifies filesystem.squashfs integrity
#   2. Creates the ISO with xorriso
#   3. Generates SHA256 + BLAKE3 checksums
#   4. Creates .torrent file
#   5. Signs with GPG
##############################################################################

set -euo pipefail

BUILD="/home/gositeme/law/alfredlinux-com-source-live/build"
SQUASHFS="$BUILD/binary/live/filesystem.squashfs"
ISO_NAME="AlfredLinux-Alpha-Matrix-7.77-x86_64.iso"
ISO_OUT="$BUILD/$ISO_NAME"
LOG="/home/gositeme/alfred-iso-finalize.log"

log() { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "$LOG"; }

log "=== ALFRED LINUX ISO FINALIZATION ==="

# ─── STEP 1: VERIFY SQUASHFS ─────────────────────────────────────
log "STEP 1: Verifying filesystem.squashfs..."
if [ ! -f "$SQUASHFS" ]; then
    log "FATAL: $SQUASHFS not found!"
    exit 1
fi

SQSIZE=$(stat -c%s "$SQUASHFS")
log "  Size: $(numfmt --to=iec $SQSIZE)"

# Quick integrity check - unsquashfs stat
if command -v unsquashfs &>/dev/null; then
    SQSTAT=$(unsquashfs -stat "$SQUASHFS" 2>&1 | tail -5)
    log "  Stats: $SQSTAT"
else
    log "  WARN: unsquashfs not available for integrity check"
fi

# ─── STEP 2: PREP BINARY TREE ────────────────────────────────────
log "STEP 2: Preparing binary tree..."

# Ensure GRUB + isolinux are in place
if [ ! -d "$BUILD/binary/boot/grub" ]; then
    log "  Setting up GRUB..."
    mkdir -p "$BUILD/binary/boot/grub"
    # Copy grub config from config/bootloaders
    cp -r /home/gositeme/law/alfredlinux-com-source-live/config/bootloaders/grub-pc/* "$BUILD/binary/boot/grub/" 2>/dev/null || true
fi

# Copy kernel + initrd into binary/live/
if [ ! -f "$BUILD/binary/live/vmlinuz" ]; then
    log "  Copying kernel..."
    cp "$BUILD/chroot/boot/vmlinuz-7.0.12" "$BUILD/binary/live/vmlinuz"
fi
if [ ! -f "$BUILD/binary/live/initrd.img" ]; then
    log "  Copying initrd..."
    INITRD=$(ls "$BUILD/chroot/boot/initrd.img-7.0.12" 2>/dev/null || ls "$BUILD/chroot/boot/initrd.img"* 2>/dev/null | head -1)
    if [ -n "$INITRD" ]; then
        cp "$INITRD" "$BUILD/binary/live/initrd.img"
    else
        log "  WARN: No initrd found — will need to generate"
    fi
fi

# ─── STEP 3: CREATE ISO ──────────────────────────────────────────
log "STEP 3: Creating ISO with xorriso..."

if ! command -v xorriso &>/dev/null; then
    log "  Installing xorriso..."
    apt-get install -y xorriso 2>/dev/null || true
fi

# TRAP 33 FIX: Forcefully inject the missing boot files into the ISO staging folder natively on the host
    log "  Injecting ISOLINUX .c32 files (Trap 33)..."
    sudo apt-get update -qq && sudo apt-get install -y xorriso isolinux syslinux-common
    sudo cp /usr/lib/syslinux/modules/bios/ldlinux.c32 /usr/lib/syslinux/modules/bios/libcom32.c32 /usr/lib/syslinux/modules/bios/libutil.c32 /usr/lib/syslinux/modules/bios/vesamenu.c32 /usr/lib/syslinux/modules/bios/menu.c32 "$BUILD/binary/isolinux/"
    sudo cp /usr/lib/ISOLINUX/isohdpfx.bin /usr/lib/ISOLINUX/isolinux.bin "$BUILD/binary/isolinux/"

    # ULTIMATE FIX: Restore the custom Alfred Linux boot menus and splash screens!
    # lb binary wiped these out with standard Debian defaults before crashing.
    log "  Restoring custom Alfred Linux bootloaders..."
    sudo cp -r /home/gositeme/law/alfredlinux-com-source-live/config/bootloaders/isolinux/* "$BUILD/binary/isolinux/" 2>/dev/null || true
    sudo cp -r /home/gositeme/law/alfredlinux-com-source-live/config/bootloaders/grub-pc/* "$BUILD/binary/boot/grub/" 2>/dev/null || true

    # TRAP 31 & 36 FIX: Re-apply the boot params because lb binary overwrites live.cfg and grub.cfg
    log "  Patching bootloader configs (Trap 31 & 36)..."
    sudo sed -i 's/append boot=live/append boot=live username=alfred user-default-groups=audio,video,render,dialout,sudo,adm,netdev,plugdev quiet nvidia-drm.modeset=1 lsm=lockdown,integrity,tomoyo,apparmor,yama,bpf,landlock/g' "$BUILD/binary/isolinux/live.cfg" 2>/dev/null || true
    sudo sed -i 's/linux.*boot=live.*/& username=alfred user-default-groups=audio,video,render,dialout,sudo,adm,netdev,plugdev quiet nvidia-drm.modeset=1 lsm=lockdown,integrity,tomoyo,apparmor,yama,bpf,landlock/g' "$BUILD/binary/boot/grub/grub.cfg" 2>/dev/null || true
    
    # Trap 36 fallback just in case lb binary used different defaults
    sudo sed -i "s|components nomodeset|components username=alfred user-default-groups=audio,video,render,dialout,sudo,adm,netdev,plugdev quiet nvidia-drm.modeset=1 lsm=lockdown,integrity,tomoyo,apparmor,yama,bpf,landlock|g" "$BUILD/binary/boot/grub/grub.cfg" 2>/dev/null || true
    sudo sed -i "s|components quiet$|components username=alfred user-default-groups=audio,video,render,dialout,sudo,adm,netdev,plugdev quiet nvidia-drm.modeset=1 lsm=lockdown,integrity,tomoyo,apparmor,yama,bpf,landlock|g" "$BUILD/binary/boot/grub/grub.cfg" 2>/dev/null || true

    # TRAP 35 FIX: Ensure gositeme can read all files before xorriso
    sudo chown -R gositeme:gositeme "$BUILD/binary/"

    cd "$BUILD"
    xorriso -as mkisofs \
      -iso-level 3 \
      -o "$ISO_NAME" \
      -isohybrid-mbr binary/isolinux/isohdpfx.bin \
      -c isolinux/boot.cat \
      -b isolinux/isolinux.bin -no-emul-boot -boot-load-size 4 -boot-info-table \
      -eltorito-alt-boot -e boot/grub/efi.img -no-emul-boot \
      -isohybrid-gpt-basdat \
      binary/ \
      2>&1 | tee -a "$LOG"

ISOSIZE=$(stat -c%s "$ISO_OUT" 2>/dev/null || echo 0)
log "  ISO created: $(numfmt --to=iec $ISOSIZE)"

# ─── STEP 4: ALL 5 CHECKSUMS ─────────────────────────────────────
log "STEP 4: Generating ALL 5 checksums..."

cd "$BUILD"
DOWNLOADS="/home/gositeme/domains/alfredlinux.com/public_html/downloads"
HASHES_FILE="HASHES-7.77.txt"

# MD5
md5sum "$ISO_NAME" > "${ISO_NAME}.md5"
log "  MD5:    $(cat ${ISO_NAME}.md5)"

# SHA1
sha1sum "$ISO_NAME" > "${ISO_NAME}.sha1"
log "  SHA1:   $(cat ${ISO_NAME}.sha1)"

# SHA256
sha256sum "$ISO_NAME" > "${ISO_NAME}.sha256"
log "  SHA256: $(cat ${ISO_NAME}.sha256)"

# SHA512
sha512sum "$ISO_NAME" > "${ISO_NAME}.sha512"
log "  SHA512: $(cut -c1-32 ${ISO_NAME}.sha512)..."

# BLAKE3
if command -v b3sum &>/dev/null; then
    b3sum "$ISO_NAME" > "${ISO_NAME}.blake3"
    log "  BLAKE3: $(cat ${ISO_NAME}.blake3)"
else
    /home/gositeme/.cargo/bin/b3sum "$ISO_NAME" > "${ISO_NAME}.blake3"
    log "  BLAKE3: $(cat ${ISO_NAME}.blake3)"
fi

# Combined HASHES file
cat > "$HASHES_FILE" <<HASHEOF
Alfred Linux 7.77 — Alpha Matrix (Kingdom of God Edition)
==========================================================
ISO: $ISO_NAME
Size: $(stat -c%s "$ISO_NAME") bytes ($(numfmt --to=iec $(stat -c%s "$ISO_NAME")))
Date: $(date -u '+%Y-%m-%d %H:%M:%S UTC')

MD5:    $(cat ${ISO_NAME}.md5 | awk '{print $1}')
SHA1:   $(cat ${ISO_NAME}.sha1 | awk '{print $1}')
SHA256: $(cat ${ISO_NAME}.sha256 | awk '{print $1}')
SHA512: $(cat ${ISO_NAME}.sha512 | awk '{print $1}')
BLAKE3: $(cat ${ISO_NAME}.blake3 | awk '{print $1}')

Verify:
  sha256sum -c ${ISO_NAME}.sha256
  b3sum -c ${ISO_NAME}.blake3
HASHEOF
log "  Combined hashes file: $HASHES_FILE"

# ─── STEP 5: TORRENT + MAGNET ────────────────────────────────────
log "STEP 5: Creating torrent + magnet..."

# Remove old torrent if exists
# DISABLED-BY-COMMANDER: rm -f "${ISO_NAME}.torrent" "${ISO_NAME}.iso.torrent"

if command -v mktorrent &>/dev/null; then
    mktorrent \
        -a udp://tracker.opentrackr.org:1337/announce \
        -a udp://tracker.openbittorrent.com:6969/announce \
        -a udp://open.stealth.si:80/announce \
        -a wss://tracker.openwebtorrent.com \
        -a wss://alfredlinux.com/announce \
        -c "Alfred Linux 7.77 — The Sovereign OS — https://alfredlinux.com" \
        -n "$ISO_NAME" \
        -w "https://alfredlinux.com/downloads/$ISO_NAME" \
        -o "${ISO_NAME}.torrent" \
        "$ISO_NAME" 2>&1 | tee -a "$LOG"
    log "  Torrent created: ${ISO_NAME}.torrent"

    # Also create .iso.torrent copy (download page expects both)
    cp "${ISO_NAME}.torrent" "${ISO_NAME}.iso.torrent"

    # Extract BTIH for magnet link
    if command -v transmission-show &>/dev/null; then
        BTIH=$(transmission-show "${ISO_NAME}.torrent" 2>/dev/null | grep 'Hash:' | awk '{print tolower($2)}')
    else
        # Python fallback for BTIH extraction
        BTIH=$(python3 -c "
import hashlib, sys
try:
    import bencodepy
    data = open('${ISO_NAME}.torrent','rb').read()
    d = bencodepy.decode(data)
    info = bencodepy.encode(d[b'info'])
    print(hashlib.sha1(info).hexdigest())
except:
    print('UNKNOWN')
" 2>/dev/null || echo "UNKNOWN")
    fi
    log "  BTIH: $BTIH"

    # Generate magnet URI
    MAGNET="magnet:?xt=urn:btih:${BTIH}&dn=$(python3 -c "import urllib.parse; print(urllib.parse.quote('${ISO_NAME}'))")&tr=udp%3A%2F%2Ftracker.opentrackr.org%3A1337%2Fannounce&tr=udp%3A%2F%2Ftracker.openbittorrent.com%3A6969%2Fannounce&tr=wss%3A%2F%2Ftracker.openwebtorrent.com&tr=wss%3A%2F%2Falfredlinux.com%2Fannounce&ws=https%3A%2F%2Falfredlinux.com%2Fdownloads%2Fwebseed.php"
    echo "$MAGNET" > "${ISO_NAME}.magnet"
    log "  Magnet URI saved to ${ISO_NAME}.magnet"
else
    log "  FATAL: mktorrent not available!"
    exit 1
fi

# ─── STEP 6: GPG SIGN ────────────────────────────────────────────
log "STEP 6: GPG signing ISO + hashes..."

if gpg --list-secret-keys 2>/dev/null | grep -q uid; then
    # Sign the ISO
    gpg --detach-sign --armor "${ISO_NAME}" 2>&1 | tee -a "$LOG"
    log "  GPG signature: ${ISO_NAME}.asc"

    # Sign the hashes file
    gpg --clearsign "$HASHES_FILE" 2>&1 | tee -a "$LOG"
    log "  Signed hashes: ${HASHES_FILE}.asc"

    # Sign the SHA256SUMS too
    cp "${ISO_NAME}.sha256" "SHA256SUMS-7.77.txt"
    gpg --clearsign "SHA256SUMS-7.77.txt" 2>&1 | tee -a "$LOG"
    log "  Signed SHA256: SHA256SUMS-7.77.txt.asc"
else
    log "  WARN: No GPG key available for signing"
fi

# ─── STEP 7: DEPLOY TO DOWNLOADS DIR ─────────────────────────────
log "STEP 7: Deploying to $DOWNLOADS ..."

mkdir -p "$DOWNLOADS"

# Copy all artifacts
cp -v "${ISO_NAME}.torrent" "$DOWNLOADS/" 2>&1 | tee -a "$LOG"
cp -v "${ISO_NAME}.iso.torrent" "$DOWNLOADS/" 2>&1 | tee -a "$LOG"
cp -v "${ISO_NAME}.asc" "$DOWNLOADS/" 2>&1 | tee -a "$LOG"
cp -v "${ISO_NAME}.sha256" "$DOWNLOADS/" 2>&1 | tee -a "$LOG"
cp -v "${ISO_NAME}.sha512" "$DOWNLOADS/" 2>&1 | tee -a "$LOG"
cp -v "${ISO_NAME}.blake3" "$DOWNLOADS/" 2>&1 | tee -a "$LOG"
cp -v "${ISO_NAME}.md5" "$DOWNLOADS/" 2>&1 | tee -a "$LOG"
cp -v "${ISO_NAME}.sha1" "$DOWNLOADS/" 2>&1 | tee -a "$LOG"
cp -v "${ISO_NAME}.magnet" "$DOWNLOADS/" 2>&1 | tee -a "$LOG"
cp -v "HASHES-7.77.txt.asc" "$DOWNLOADS/" 2>&1 | tee -a "$LOG" || true
cp -v "SHA256SUMS-7.77.txt.asc" "$DOWNLOADS/" 2>&1 | tee -a "$LOG" || true

# Also create the latest.iso.torrent symlink
ln -sf "${ISO_NAME}.torrent" "$DOWNLOADS/alfredlinux-latest.iso.torrent"
log "  Symlinked alfredlinux-latest.iso.torrent"

# ─── STEP 8: UPDATE ga-release-state.php WITH NEW BTIH ───────────
log "STEP 8: Updating ga-release-state.php with new BTIH..."

GA_STATE="/home/gositeme/domains/alfredlinux.com/public_html/includes/ga-release-state.php"
if [ -f "$GA_STATE" ] && [ "$BTIH" != "UNKNOWN" ]; then
    # Backup
    cp "$GA_STATE" "${GA_STATE}.bak.$(date +%s)"
    # Update BTIH
    sed -i "s/\$gaTorrentBtihHex = '[a-f0-9]*';/\$gaTorrentBtihHex = '${BTIH}';/" "$GA_STATE"
    log "  Updated BTIH to: $BTIH"
    log "  NOTE: Set \$gaDownloadOfferLive = true when ready to go public"
else
    log "  WARN: Could not update ga-release-state.php (BTIH=$BTIH)"
fi

# ─── DONE ─────────────────────────────────────────────────────────
log ""
log "╔══════════════════════════════════════════════════════════════╗"
log "║          ALFRED LINUX ISO FINALIZATION COMPLETE             ║"
log "╠══════════════════════════════════════════════════════════════╣"
log "║  ISO:       $ISO_OUT"
log "║  Size:      $(numfmt --to=iec $(stat -c%s "$ISO_OUT" 2>/dev/null || echo 0))"
log "║  MD5:       $(cat ${ISO_NAME}.md5 | awk '{print $1}')"
log "║  SHA256:    $(cat ${ISO_NAME}.sha256 | awk '{print $1}')"
log "║  BLAKE3:    $(cat ${ISO_NAME}.blake3 | awk '{print $1}')"
log "║  BTIH:      $BTIH"
log "║  Torrent:   $DOWNLOADS/${ISO_NAME}.torrent"
log "║  Magnet:    ${ISO_NAME}.magnet"
log "║  GPG Sig:   $DOWNLOADS/${ISO_NAME}.asc"
log "║  Deployed:  $DOWNLOADS/"
log "╚══════════════════════════════════════════════════════════════╝"
log ""
log "NEXT: Set \$gaDownloadOfferLive = true in ga-release-state.php to go live"
log "================================================"
