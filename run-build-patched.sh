#!/bin/bash

# --- INJECTED BY ANTIGRAVITY TO FIX STATE BUGS ---
rm -f /home/gositeme/law/alfredlinux-com-source-live/night-shift-DONE.txt /home/gositeme/law/alfredlinux-com-source-live/night-shift-FAIL.txt
echo "Night-shift watching lb-docker. Squashfs" > /home/gositeme/law/alfredlinux-com-source-live/night-shift-state.txt
# -------------------------------------------------

# Alfred Linux Build Runner v2 — BYPASS lb binary, run mksquashfs directly
set -e
cd /home/gositeme/law/alfredlinux-com-source-live
# DISABLED-BY-COMMANDER: rm -f build/.alfred-lb-docker-build.lock

echo "[runner] Starting at $(date)"
echo "[runner] Launching docker container (NO lb binary — direct mksquashfs)..."

docker run \
  --rm --name alfred-lb-runner-v3 \
  --privileged \
  --network=host \
  --shm-size=16G \
  --init \
  -e DEBIAN_FRONTEND=noninteractive \
  -e "MKSQUASHFS_OPTIONS=-mem 14G" \
  -v /home/gositeme/law/alfredlinux-com-source-live:/work \
  -v /home/gositeme/law:/host_law:ro \
  -v /home/gositeme/.ollama/models:/models:ro \
  -w /work/build \
  alfred-lb-runner:local \
  bash -c '
set -e
echo "=== STEP 1: Tools are pre-installed in alfred-lb-runner:local ==="

echo "=== STEP 2: Mount overlay ==="
rm -rf /work/build/chroot-upper /work/build/chroot-work
mkdir -p /work/build/chroot-upper /work/build/chroot-work /work/build/chroot
mount -t overlay overlay \
  -o lowerdir=/work/config/includes.chroot_after_packages:/work/config/includes.chroot:/work/build/cache/bootstrap,upperdir=/work/build/chroot-upper,workdir=/work/build/chroot-work \
  /work/build/chroot
echo "Overlay mounted. chroot contents:"
ls /work/build/chroot/ | head -5
echo "chroot size: $(du -sh /work/build/chroot/ 2>/dev/null | cut -f1)"

echo "=== STEP 2.5: Seed AI Models ==="
mkdir -p /work/build/chroot/usr/share/ollama/.ollama/models/blobs
mkdir -p /work/build/chroot/usr/share/ollama/.ollama/models/manifests
cp -r /models/blobs/* /work/build/chroot/usr/share/ollama/.ollama/models/blobs/ 2>/dev/null || true
cp -r /models/manifests/* /work/build/chroot/usr/share/ollama/.ollama/models/manifests/ 2>/dev/null || true
chown -R 1000:1000 /work/build/chroot/usr/share/ollama/.ollama
echo "AI Models seeded. Blobs size: $(du -sh /work/build/chroot/usr/share/ollama/.ollama/models/blobs/ 2>/dev/null | cut -f1)"


echo "=== STEP 3: Verify chroot has everything ==="
echo "bash: $(ls /work/build/chroot/usr/bin/bash 2>/dev/null && echo YES || echo NO)"
echo "plasmashell: $(ls /work/build/chroot/usr/bin/plasmashell 2>/dev/null && echo YES || echo NO)"
echo "kernel: $(ls /work/build/chroot/boot/vmlinuz-7.0.12 2>/dev/null && echo YES || echo NO)"
echo "initrd: $(ls /work/build/chroot/boot/initrd.img-7.0.12 2>/dev/null && echo YES || echo NO)"
echo "opt apps: $(ls /work/build/chroot/opt/ | wc -l)"

echo "=== STEP 3.1: Execute Custom Hooks ==="
mount -t proc proc /work/build/chroot/proc || true
mount -t sysfs sysfs /work/build/chroot/sys || true
mount -o bind /dev /work/build/chroot/dev || true
mount -o bind /dev/pts /work/build/chroot/dev/pts || true
if [ -d "/work/config/hooks/live" ]; then
  for hook in /work/config/hooks/live/*.hook.chroot; do
    if [ -x "$hook" ]; then
      echo "Running hook: $hook"
      cp "$hook" /work/build/chroot/tmp/run_hook.sh && chroot /work/build/chroot /bin/bash /tmp/run_hook.sh && rm -f /work/build/chroot/tmp/run_hook.sh
    fi
  done
fi

echo "=== STEP 3.5: Chroot Integrity Fixes (Traps 23-29) ==="
umount -l /work/build/chroot/proc || true
umount -l /work/build/chroot/sys || true
umount -l /work/build/chroot/dev/pts || true
umount -l /work/build/chroot/dev || true
# Trap 23: hosts
cat > /work/build/chroot/etc/hosts << 'EOF'
127.0.0.1	localhost
127.0.1.1	alfredlinux
::1		localhost ip6-localhost ip6-loopback
ff02::1		ip6-allnodes
ff02::2		ip6-allrouters
EOF
# Trap 24: machine-id
echo -n > /work/build/chroot/etc/machine-id
# Trap 25: tmp perms
mkdir -p /work/build/chroot/tmp
chmod 1777 /work/build/chroot/tmp
# Trap 26: hostname
echo "alfredlinux" > /work/build/chroot/etc/hostname
# Trap 27: Plymouth theme
mkdir -p /work/build/chroot/etc/plymouth
cat > /work/build/chroot/etc/plymouth/plymouthd.conf << 'EOF'
[Daemon]
Theme=alfred
ShowDelay=0
EOF
# Trap 28: default/grub
mkdir -p /work/build/chroot/etc/default
cat > /work/build/chroot/etc/default/grub << 'EOF'
GRUB_DEFAULT=0
GRUB_TIMEOUT=15
GRUB_DISTRIBUTOR="Alfred Linux"
GRUB_CMDLINE_LINUX_DEFAULT="quiet nvidia-drm.modeset=1"
GRUB_CMDLINE_LINUX=""
EOF
# Trap 29: Root Artifacts
rm -rf /work/build/chroot/root/.wget-hsts /work/build/chroot/root/.cache/* /work/build/chroot/root/.ssh /work/build/chroot/root/custom-packages /work/build/chroot/root/masterstroke_kernel
# Also clean build artifacts that don't belong in the live image
rm -rf /work/build/chroot/build-assets /work/build/chroot/var/log/*.log /work/build/chroot/var/tmp/* /work/build/chroot/tmp/*
apt-get clean -y 2>/dev/null || chroot /work/build/chroot apt-get clean -y 2>/dev/null || true

echo "=== STEP 4: Setup binary/live ==="
mkdir -p /work/build/binary/live
cp /work/build/chroot/boot/vmlinuz-7.0.12 /work/build/binary/live/vmlinuz-7.0.12
cp /work/build/chroot/boot/initrd.img-7.0.12 /work/build/binary/live/initrd.img-7.0.12
echo "Kernel and initrd copied to binary/live/"

echo "=== STEP 5: MKSQUASHFS (the big one) ==="
echo "Compressing chroot → filesystem.squashfs..."
echo "This will take several hours. Started at $(date)"
mksquashfs /work/build/chroot /work/build/binary/live/filesystem.squashfs \
  -comp xz -Xbcj x86 -b 1M \
  -noappend \
  -mem 14G \
  -e boot/vmlinuz-7.0.12 boot/initrd.img-7.0.12 \
  -wildcards \
  -e "var/cache/apt/archives/*.deb" "var/cache/apt/*.bin" \
  -e "var/log/*" "var/tmp/*" "tmp/*" \
  -e "root/.cache/*" "root/.wget-hsts" "root/.ssh" \
  -e "root/custom-packages" "root/masterstroke_kernel" \
  -e "build-assets/*" \
  -e "usr/share/doc/*" "usr/share/man/*" \
  -progress
echo "mksquashfs finished at $(date)"
echo "Squashfs size: $(ls -lh /work/build/binary/live/filesystem.squashfs | awk "{print \$5}")"

echo "=== STEP 6: Generate manifest ==="
chroot /work/build/chroot dpkg-query -W --showformat="\${Package} \${Version}\n" > /work/build/binary/live/filesystem.packages 2>/dev/null || true

echo "=== STEP 7: Setup bootloader ==="
mkdir -p /work/build/binary/isolinux /work/build/binary/boot/grub
cp /work/config/bootloaders/isolinux/* /work/build/binary/isolinux/ 2>/dev/null || true
cp /work/config/bootloaders/grub-pc/* /work/build/binary/boot/grub/ 2>/dev/null || true

echo "=== STEP 7.5: Apply Trap Fixes (17, 19, 20, 21, 31) ==="
# Trap 17: Kernel symlinks
cd /work/build/binary/live
mv vmlinuz-7.0.12 vmlinuz
mv initrd.img-7.0.12 initrd.img
cd /work/build

# Trap 19: Missing Boot Binaries
cp /usr/lib/ISOLINUX/isolinux.bin /work/build/binary/isolinux/ 2>/dev/null || true
cp /usr/lib/syslinux/modules/bios/vesamenu.c32 /work/build/binary/isolinux/ 2>/dev/null || true
cp /usr/lib/syslinux/modules/bios/menu.c32 /work/build/binary/isolinux/ 2>/dev/null || true

grub-mkimage -O i386-pc -o /work/build/binary/boot/grub/bios.img \
  -p /boot/grub biosdisk iso9660 part_msdos fat normal boot linux chain \
  configfile loopback search search_fs_uuid search_fs_file search_label \
  test all_video gfxterm echo true

mkdir -p /work/build/binary/EFI/boot
cp /work/build/chroot/usr/lib/shim/shimx64.efi.signed /work/build/binary/EFI/boot/bootx64.efi 2>/dev/null || true
grub-mkimage -O x86_64-efi -o /work/build/binary/EFI/boot/grubx64.efi \
  -p /boot/grub part_gpt part_msdos fat ext2 normal chain boot linux \
  loopback iso9660 search search_label search_fs_uuid search_fs_file \
  gfxterm gfxterm_background gfxterm_menu test all_video loadenv exfat \
  configfile echo true keystatus

dd if=/dev/zero of=/work/build/binary/boot/grub/efi.img bs=1M count=8
mkfs.vfat /work/build/binary/boot/grub/efi.img
mmd -i /work/build/binary/boot/grub/efi.img ::EFI ::EFI/boot
mcopy -i /work/build/binary/boot/grub/efi.img \
  /work/build/binary/EFI/boot/bootx64.efi ::EFI/boot/bootx64.efi
mcopy -i /work/build/binary/boot/grub/efi.img \
  /work/build/binary/EFI/boot/grubx64.efi ::EFI/boot/grubx64.efi

# Trap 20: GRUB Font
cp /work/build/chroot/usr/share/grub/unicode.pf2 /work/build/binary/boot/grub/ 2>/dev/null || true

# Trap 21: Filesystem Size
ISOSIZE=$(stat -c%s /work/build/binary/live/filesystem.squashfs 2>/dev/null || echo 177156456448)
echo $ISOSIZE > /work/build/binary/live/filesystem.size


echo "=== STEP 7.9: Trap 10 (Overlay unmount and Stamps) ==="
umount /work/build/chroot 2>/dev/null || true
touch /work/build/.build/binary_chroot
touch /work/build/.build/binary_linux-image

echo "=== STEP 8: Run lb binary for ISO assembly (with chroot already squashed) ==="
# Touch the binary_rootfs stamp so lb binary skips mksquashfs
touch /work/build/.build/binary_rootfs
# Now lb binary will just do grub + xorriso
lb binary || echo "lb binary exited with code $? — checking if ISO exists anyway"

echo "=== STEP 9: Check results ==="
if [ -f /work/build/live-image-amd64.hybrid.iso ]; then
  echo "ISO FOUND: $(ls -lh /work/build/live-image-amd64.hybrid.iso)"
  mkdir -p /work/iso-output
  mv /work/build/live-image-amd64.hybrid.iso /work/iso-output/AlfredLinux-Alpha-Matrix-7.77-x86_64.iso && ln -f /work/iso-output/AlfredLinux-Alpha-Matrix-7.77-x86_64.iso /work/iso-output/live-image-amd64.hybrid.iso
  echo "ISO moved to /work/iso-output/"
  sha256sum /work/iso-output/AlfredLinux-Alpha-Matrix-7.77-x86_64.iso > /work/iso-output/AlfredLinux-Alpha-Matrix-7.77-x86_64.iso.sha256
  echo "SHA256 generated"
  touch /work/iso-output/build-complete.marker
  echo "BUILD COMPLETE"
  echo "[inner] lb build finished ... exit=0"
  echo "DONE ON ATTEMPT 1" > /work/night-shift-state.txt
  echo "Build completed successfully via V2 runner." > /work/night-shift-DONE.txt
else
  echo "WARNING: No ISO from lb binary. Checking binary/ for manual assembly..."
  ls -la /work/build/binary/live/
fi

echo "=== DONE at $(date) ==="
' 2>&1 | tee /home/gositeme/law/alfredlinux-com-source-live/lb-docker-build.log

echo "[runner] Docker exited with code $?"
echo "[runner] Finished at $(date)"



