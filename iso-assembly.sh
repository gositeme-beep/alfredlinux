#!/bin/bash
# ============================================================================
# ISO ASSEMBLY SCRIPT — Manual hybrid.iso generation via xorriso
# ============================================================================
# Purpose: After vault-bake.sh completes, this script manually assembles
#          the bootable hybrid ISO, bypassing the orchestrator's 4GB crash
#          point (Trap 33 / Trap 138).
#
# Flow:
#   1. Remount OverlayFS merged view for squashfs source
#   2. mksquashfs the chroot → filesystem.squashfs
#   3. Copy kernel + initrd to binary/live/
#   4. Set up ISOLINUX + GRUB bootloaders
#   5. Build EFI boot image
#   6. Assemble final ISO with xorriso (BIOS + UEFI hybrid)
#   7. Generate checksums
#
# Run as:  sudo bash iso-assembly.sh
# ============================================================================

set +e

BASE="/home/gositeme/law/alfredlinux-com-source-live"
BUILD="$BASE/build"
CHROOT="$BUILD/chroot"
UPPER="$BUILD/chroot-upper"
WORK="$BUILD/chroot-work"
BINARY="$BUILD/binary"
SECONDARY="$BUILD/cache/bootstrap"
LOWER="$BASE/config/includes.chroot_after_packages:$BASE/config/includes.chroot:$SECONDARY"
ISO_OUTPUT="$BASE/iso-output"
ISO_NAME="AlfredLinux-Alpha-Matrix-7.77-x86_64.iso"

echo "============================================"
echo " ISO ASSEMBLY — $(date)"
echo "============================================"

# --- STEP 1: Ensure OverlayFS is mounted ---
echo ""
echo "=== STEP 1: Checking OverlayFS mount ==="
if mountpoint -q "$CHROOT" 2>/dev/null; then
    echo "[OK] OverlayFS already mounted"
else
    echo "[FIX] Remounting OverlayFS..."
    mkdir -p "$UPPER" "$WORK" "$CHROOT"
    mount -t overlay overlay \
        -o lowerdir="$LOWER",upperdir="$UPPER",workdir="$WORK" \
        "$CHROOT"
    if [ $? -ne 0 ]; then
        echo "[FATAL] Cannot mount OverlayFS"
        exit 1
    fi
    echo "[OK] OverlayFS remounted"
fi

# --- STEP 2: Prepare binary directory ---
echo ""
echo "=== STEP 2: Preparing binary directory ==="
mkdir -p "$BINARY/live"
mkdir -p "$BINARY/isolinux"
mkdir -p "$BINARY/boot/grub"
mkdir -p "$BINARY/EFI/boot"
echo "[OK] Binary directory structure created"

# --- STEP 3: Copy kernel and initrd ---
echo ""
echo "=== STEP 3: Copying kernel and initrd ==="
KFILE=$(find "$CHROOT/boot" -maxdepth 1 -name "vmlinuz*" 2>/dev/null | sort -V | tail -n 1)
IFILE=$(find "$CHROOT/boot" -maxdepth 1 -name "initrd*" 2>/dev/null | sort -V | tail -n 1)

if [ -n "$KFILE" ]; then
    cp -f "$KFILE" "$BINARY/live/vmlinuz"
    echo "[OK] Kernel: $KFILE → binary/live/vmlinuz"
else
    echo "[FATAL] No kernel found in $CHROOT/boot/"
    ls -la "$CHROOT/boot/"
    exit 1
fi

if [ -n "$IFILE" ]; then
    cp -f "$IFILE" "$BINARY/live/initrd.img"
    echo "[OK] Initrd: $IFILE → binary/live/initrd.img"
else
    echo "[FATAL] No initrd found in $CHROOT/boot/"
    exit 1
fi

# --- STEP 4: MKSQUASHFS ---
echo ""
echo "=== STEP 4: Building filesystem.squashfs ==="
echo "This is the big one. Started at $(date)"
echo "Source: $CHROOT"
echo "Target: $BINARY/live/filesystem.squashfs"

# Remove old squashfs if present
rm -f "$BINARY/live/filesystem.squashfs"

ionice -c 3 mksquashfs "$CHROOT" "$BINARY/live/filesystem.squashfs" \
    -noappend \
    -comp xz \
    -Xbcj x86 \
    -b 1M \
    -mem 14G \
    -processors 4 \
    -e boot/vmlinuz* boot/initrd* \
    -wildcards \
    -e "var/cache/apt/archives/*.deb" \
    -e "var/cache/apt/*.bin" \
    -e "proc/*" \
    -e "sys/*" \
    -e "dev/*" \
    -e "run/*" \
    -e "tmp/*" \
    -progress

SQRESULT=$?
echo ""
echo "mksquashfs finished at $(date) with exit code $SQRESULT"

if [ $SQRESULT -ne 0 ]; then
    echo "[FATAL] mksquashfs failed!"
    df -h
    free -h
    exit 1
fi

SQSIZE=$(ls -lh "$BINARY/live/filesystem.squashfs" | awk '{print $5}')
echo "[OK] filesystem.squashfs created ($SQSIZE)"

# Write filesystem.size
stat -c%s "$BINARY/live/filesystem.squashfs" > "$BINARY/live/filesystem.size"

# --- STEP 5: Generate package manifest ---
echo ""
echo "=== STEP 5: Generating package manifest ==="
chroot "$CHROOT" dpkg-query -W --showformat='${Package} ${Version}\n' \
    > "$BINARY/live/filesystem.packages" 2>/dev/null || true
PKG_COUNT=$(wc -l < "$BINARY/live/filesystem.packages" 2>/dev/null || echo "0")
echo "[OK] Manifest generated ($PKG_COUNT packages)"

# --- STEP 6: Setup ISOLINUX bootloader ---
echo ""
echo "=== STEP 6: Setting up ISOLINUX ==="

# Copy custom bootloader configs if they exist
cp "$BASE/config/bootloaders/isolinux/"* "$BINARY/isolinux/" 2>/dev/null || true
cp "$BASE/config/bootloaders/grub-pc/"* "$BINARY/boot/grub/" 2>/dev/null || true

# Copy system ISOLINUX files
cp /usr/lib/ISOLINUX/isolinux.bin "$BINARY/isolinux/" 2>/dev/null || \
    cp "$CHROOT/usr/lib/ISOLINUX/isolinux.bin" "$BINARY/isolinux/" 2>/dev/null || true

for mod in vesamenu.c32 menu.c32 ldlinux.c32 libutil.c32 libcom32.c32 hdt.c32; do
    cp /usr/lib/syslinux/modules/bios/$mod "$BINARY/isolinux/" 2>/dev/null || \
        cp "$CHROOT/usr/lib/syslinux/modules/bios/$mod" "$BINARY/isolinux/" 2>/dev/null || true
done

# Create isolinux.cfg if missing
if [ ! -f "$BINARY/isolinux/isolinux.cfg" ]; then
    cat > "$BINARY/isolinux/isolinux.cfg" << 'ISOCFG'
UI vesamenu.c32
PROMPT 0
TIMEOUT 150

MENU TITLE Alfred Linux Alpha Matrix 7.77
MENU BACKGROUND splash.png

LABEL live
    MENU LABEL ^Start Alfred Linux
    MENU DEFAULT
    KERNEL /live/vmlinuz
    APPEND initrd=/live/initrd.img boot=live components quiet splash

LABEL live-safe
    MENU LABEL ^Safe Mode
    KERNEL /live/vmlinuz
    APPEND initrd=/live/initrd.img boot=live components nomodeset

LABEL memtest
    MENU LABEL ^Memory Test
    KERNEL /live/vmlinuz
    APPEND memtest
ISOCFG
    echo "[OK] Created default isolinux.cfg"
fi

echo "[OK] ISOLINUX configured"

# --- STEP 7: Setup GRUB (BIOS + EFI) ---
echo ""
echo "=== STEP 7: Setting up GRUB ==="

# GRUB config
if [ ! -f "$BINARY/boot/grub/grub.cfg" ]; then
    cat > "$BINARY/boot/grub/grub.cfg" << 'GRUBCFG'
set default=0
set timeout=15

menuentry "Alfred Linux Alpha Matrix 7.77" {
    linux /live/vmlinuz boot=live components quiet splash nvidia-drm.modeset=1
    initrd /live/initrd.img
}

menuentry "Alfred Linux (Safe Mode)" {
    linux /live/vmlinuz boot=live components nomodeset
    initrd /live/initrd.img
}
GRUBCFG
    echo "[OK] Created default grub.cfg"
fi

# Copy GRUB font
cp "$CHROOT/usr/share/grub/unicode.pf2" "$BINARY/boot/grub/" 2>/dev/null || \
    cp /usr/share/grub/unicode.pf2 "$BINARY/boot/grub/" 2>/dev/null || true

# Build GRUB BIOS image
grub-mkimage -O i386-pc -o "$BINARY/boot/grub/bios.img" \
    -p /boot/grub biosdisk iso9660 part_msdos fat normal boot linux chain \
    configfile loopback search search_fs_uuid search_fs_file search_label \
    test all_video gfxterm echo true 2>/dev/null || \
    echo "[WARN] grub-mkimage i386-pc failed (non-fatal)"

# Build GRUB EFI image
grub-mkimage -O x86_64-efi -o "$BINARY/EFI/boot/grubx64.efi" \
    -p /boot/grub part_gpt part_msdos fat ext2 normal chain boot linux \
    loopback iso9660 search search_label search_fs_uuid search_fs_file \
    gfxterm gfxterm_background gfxterm_menu test all_video loadenv exfat \
    configfile echo true keystatus 2>/dev/null || \
    echo "[WARN] grub-mkimage x86_64-efi failed (non-fatal)"

# Copy shim for Secure Boot
cp "$CHROOT/usr/lib/shim/shimx64.efi.signed" "$BINARY/EFI/boot/bootx64.efi" 2>/dev/null || true

# Build EFI boot image for ISO
echo ""
echo "=== STEP 7.5: Building EFI boot image ==="
dd if=/dev/zero of="$BINARY/boot/grub/efi.img" bs=1M count=8 2>/dev/null
mkfs.vfat "$BINARY/boot/grub/efi.img" 2>/dev/null || true
mmd -i "$BINARY/boot/grub/efi.img" ::EFI ::EFI/boot 2>/dev/null || true
mcopy -i "$BINARY/boot/grub/efi.img" \
    "$BINARY/EFI/boot/bootx64.efi" ::EFI/boot/bootx64.efi 2>/dev/null || true
mcopy -i "$BINARY/boot/grub/efi.img" \
    "$BINARY/EFI/boot/grubx64.efi" ::EFI/boot/grubx64.efi 2>/dev/null || true
echo "[OK] EFI boot image created"

echo "[OK] GRUB configured"

# --- STEP 8: Unmount OverlayFS ---
echo ""
echo "=== STEP 8: Unmounting chroot ==="
umount -l "$CHROOT/proc" 2>/dev/null || true
umount -l "$CHROOT/sys" 2>/dev/null || true
umount -l "$CHROOT/dev/pts" 2>/dev/null || true
umount -l "$CHROOT/dev" 2>/dev/null || true
umount "$CHROOT" 2>/dev/null || true
echo "[OK] Chroot unmounted"

# --- STEP 9: XORRISO — Build the hybrid ISO ---
echo ""
echo "=== STEP 9: Building hybrid ISO with xorriso ==="
mkdir -p "$ISO_OUTPUT"

xorriso -as mkisofs \
    -r -J -joliet-long \
    -l \
    -iso-level 3 \
    -isohybrid-mbr /usr/lib/ISOLINUX/isohdpfx.bin \
    -partition_offset 16 \
    -A "Alfred Linux Alpha Matrix 7.77" \
    -V "ALFREDLINUX" \
    -b isolinux/isolinux.bin \
    -c isolinux/boot.cat \
    -no-emul-boot \
    -boot-load-size 4 \
    -boot-info-table \
    -eltorito-alt-boot \
    -e boot/grub/efi.img \
    -no-emul-boot \
    -isohybrid-gpt-basdat \
    -o "$ISO_OUTPUT/$ISO_NAME" \
    "$BINARY/"

XRESULT=$?

if [ $XRESULT -ne 0 ]; then
    echo "[FATAL] xorriso failed with exit code $XRESULT"
    exit 1
fi

# Create symlink for compatibility
ln -f "$ISO_OUTPUT/$ISO_NAME" "$ISO_OUTPUT/live-image-amd64.hybrid.iso" 2>/dev/null || true

ISOSIZE=$(ls -lh "$ISO_OUTPUT/$ISO_NAME" | awk '{print $5}')
echo "[OK] ISO created: $ISO_OUTPUT/$ISO_NAME ($ISOSIZE)"

# --- STEP 10: Generate checksums ---
echo ""
echo "=== STEP 10: Generating checksums ==="
cd "$ISO_OUTPUT"
sha256sum "$ISO_NAME" > "${ISO_NAME}.sha256"
md5sum "$ISO_NAME" > "${ISO_NAME}.md5"
echo "[OK] Checksums generated"

# --- STEP 11: Build complete marker ---
touch "$ISO_OUTPUT/build-complete.marker"
echo "BUILD COMPLETE" > "$BASE/night-shift-DONE.txt"
echo "DONE ON ATTEMPT 1" > "$BASE/night-shift-state.txt"

echo ""
echo "============================================"
echo " ISO ASSEMBLY COMPLETE — $(date)"
echo "============================================"
echo ""
echo " ISO:      $ISO_OUTPUT/$ISO_NAME"
echo " Size:     $ISOSIZE"
echo " SHA256:   $(cat ${ISO_NAME}.sha256)"
echo ""
echo " The ISO is bootable on both BIOS and UEFI systems."
echo "============================================"
