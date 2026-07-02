#!/bin/bash
set -e
export DEBIAN_FRONTEND=noninteractive

cd /work/build

echo "=== acquire exclusive build lock ==="
exec 9>/work/build/.alfred-lb-docker-build.lock
flock -n 9 || { echo "[inner] ERROR: exclusive build lock is busy"; exit 1; }
echo "   -> lock acquired"

echo "=== fix CRLF in build/config files ==="
for f in config/chroot config/binary config/common config/bootstrap config/source; do
  [ -f "$f" ] && sed -i 's/\r$//' "$f"
done

echo "=== install live-build ==="
apt-get update -qq
apt-get install -y --no-install-recommends \
  live-build xorriso isolinux syslinux-common \
  grub-pc-bin grub-efi-amd64-bin grub-efi-amd64-signed shim-signed \
  mtools dosfstools squashfs-tools cpio rsync zstd file

echo "=== setting MKSQUASHFS memory limit ==="
export MKSQUASHFS_OPTIONS="-mem 14G"

echo " === INSTANTLY SYNCING MASTER CHROOT + ASSETS VIA OVERLAYFS === "
mkdir -p /work/config/includes.chroot_after_packages /work/config/includes.chroot /work/build/cache/bootstrap
mkdir -p /work/build/chroot-upper /work/build/chroot-work /work/build/chroot
mount -t overlay overlay -o lowerdir=/work/config/includes.chroot_after_packages:/work/config/includes.chroot:/work/build/cache/bootstrap,upperdir=/work/build/chroot-upper,workdir=/work/build/chroot-work /work/build/chroot
echo "=== MERGE COMPLETE ==="

# Delete 80-timeshift to prevent apt syntax error crash
rm -f /work/build/chroot/etc/apt/apt.conf.d/80-timeshift
# Create /sys mountpoint if missing
mkdir -p /work/build/chroot/sys

# Fix PATH so start-stop-daemon and other sbin tools are found
export PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin

# Remove stale 0-byte kernel from binary/live
mkdir -p /work/build/binary/live
rm -f /work/build/binary/live/vmlinuz-7.0.12

echo "=== checking if kernel needs to be installed ==="
if [ ! -f chroot/boot/vmlinuz-7.0.12 ] || [ ! -f chroot/boot/initrd.img-7.0.12 ]; then
    echo "WARNING: Kernel missing. Installing custom kernel deb packages..."
    if ls /work/config/packages.chroot/linux-*7.0.12*.deb 1> /dev/null 2>&1; then
        mkdir -p chroot/tmp; cp /work/config/packages.chroot/linux-*7.0.12*.deb chroot/tmp/
        
        mount -t proc proc chroot/proc || true
        mount -t sysfs sys chroot/sys || true
        mount -o bind /dev chroot/dev || true
        
        chroot chroot bash -c "dpkg --remove --force-all linux-headers-amd64 linux-headers-7.0.12 2>/dev/null || true; dpkg -i --force-overwrite /tmp/linux-*7.0.12*.deb || true"
        rm -f chroot/tmp/linux-*7.0.12*.deb
    else
        echo "ERROR: Kernel .deb packages not found in /work/config/packages.chroot/"
        exit 1
    fi
    
    if [ ! -f chroot/boot/initrd.img-7.0.12 ]; then
        echo "WARNING: initrd still missing after chroot_linux-image. Generating it..."
        mkdir -p chroot/proc chroot/sys chroot/dev/pts
        mount -t proc proc chroot/proc || true
        mount -t sysfs sys chroot/sys || true
        mount -o bind /dev chroot/dev || true
        mount -o bind /dev/pts chroot/dev/pts || true
        
        echo "nameserver 9.9.9.9" > chroot/etc/resolv.conf
        echo "nameserver 8.8.8.8" >> chroot/etc/resolv.conf
        echo "nameserver 1.1.1.1" >> chroot/etc/resolv.conf
        
        chroot chroot bash -c "export DEBIAN_FRONTEND=noninteractive

cd /work/build && apt-get update && apt-get install -y --no-install-recommends initramfs-tools initramfs-tools-core dracut-core" || true
        
        chroot chroot bash -c "DPKG_MAINTSCRIPT_PACKAGE='' export PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin; /usr/sbin/update-initramfs -c -k 7.0.12" || true
        
        umount -lf chroot/dev/pts || true
        umount -lf chroot/dev || true
        umount -lf chroot/sys || true
        umount -lf chroot/proc || true
    fi
fi

chroot chroot apt-mark hold linux-headers-amd64 linux-image-amd64 || true
echo "=== clear failed-stamp files so lb redoes those steps ==="
B=.build
mkdir -p $B
chroot chroot dpkg --configure -a || true
chroot chroot dpkg --configure -a || true

rm -f chroot/root/custom-packages/*99alfred*.deb || true
rm -f chroot/root/masterstroke_kernel/*99alfred*.deb || true
chroot chroot dpkg --configure -a || true

rm -f $B/binary_memtest $B/binary_grub-legacy \
      $B/binary_grub-pc $B/binary_grub_cfg $B/binary_syslinux \
      $B/binary_loadlin $B/binary_disk $B/binary_iso $B/binary_iso-hybrid \
      $B/binary_netboot $B/binary_tar $B/binary_zsync $B/binary_hdd \
      $B/binary_checksums $B/binary_hooks $B/binary_dm-verity \
      $B/binary_manifest $B/binary_package-lists$B

rm -f /work/build/chroot/var/lib/dpkg/info/linux-image*.postinst
chroot /work/build/chroot dpkg --configure -a || true


echo "=== manually running mksquashfs ==="
mkdir -p binary/live
rm -f binary/live/filesystem.squashfs
cp chroot/boot/vmlinuz-7.0.12 binary/live/vmlinuz-7.0.12 || true
cp chroot/boot/initrd.img-7.0.12 binary/live/initrd.img-7.0.12 || true


echo " === Setting up Hooks === "
mkdir -p /work/build/config/hooks/live /work/build/config/hooks/normal
cp -a /work/config/hooks/live/* /work/build/config/hooks/live/ 2>/dev/null || true
cp -a /work/config/hooks/normal/* /work/build/config/hooks/normal/ 2>/dev/null || true

# GLOBAL SWARM OVERRIDE
sed -i 's|\[ -d binary \]|\[ -d /work/build/binary \]|g' /work/build/config/hooks/live/*.hook.binary 2>/dev/null || true
sed -i 's|\[ -d binary/live \]|\[ -d /work/build/binary/live \]|g' /work/build/config/hooks/live/*.hook.binary 2>/dev/null || true
sed -i 's|BIN=binary|BIN=/work/build/binary|g' /work/build/config/hooks/live/*.hook.binary 2>/dev/null || true
sed -i 's|BIN="binary"|BIN="/work/build/binary"|g' /work/build/config/hooks/live/*.hook.binary 2>/dev/null || true
sed -i 's|LIVE_DIR="binary/live"|LIVE_DIR="/work/build/binary/live"|g' /work/build/config/hooks/live/*.hook.binary 2>/dev/null || true
sed -i 's|GRUB_CFG="binary/boot/grub/grub.cfg"|GRUB_CFG="/work/build/binary/boot/grub/grub.cfg"|g' /work/build/config/hooks/live/*.hook.binary 2>/dev/null || true
sed -i 's|ISO_CFG="binary/isolinux/live.cfg"|ISO_CFG="/work/build/binary/isolinux/live.cfg"|g' /work/build/config/hooks/live/*.hook.binary 2>/dev/null || true

cat << 'INNEREOF' > /work/build/config/hooks/live/0924-alfred-bootloader-override.hook.binary
#!/bin/bash
INC=/work/config/includes.binary
if [ -d "$INC/isolinux" ]; then
  for f in "$INC/isolinux"/*; do
    [ -f "$f" ] || continue
    name=$(basename "$f")
    cp -fv "$f" "/work/build/binary/isolinux/$name"
  done
fi
if [ -d "$INC/boot/grub" ]; then
  for f in "$INC/boot/grub"/*; do
    [ -f "$f" ] || continue
    name=$(basename "$f")
    cp -fv "$f" "/work/build/binary/boot/grub/$name"
  done
fi
add_lsm_safe() {
  local f="$1"
  [ -f "$f" ] || return 0
  if grep -q 'lsm=' "$f"; then return 0; fi
  sed -i -E 's|(append[[:space:]]+initrd=[^[:space:]]+[[:space:]]+boot=live[^\r\n]*)|\1 components lsm=apparmor apparmor=1 security=apparmor|I' "$f"
  sed -i -E 's|(linux[[:space:]]+/live/vmlinuz[^\r\n]*)|\1 components lsm=apparmor apparmor=1 security=apparmor|I' "$f"
}
for cfg in /work/build/binary/isolinux/live.cfg /work/build/binary/isolinux/install.cfg /work/build/binary/boot/grub/grub.cfg /work/build/binary/boot/grub/live.cfg; do
  add_lsm_safe "$cfg"
done
INNEREOF

chmod +x /work/build/config/hooks/live/0924-alfred-bootloader-override.hook.binary

# Neuter the default plocate hook — linkat() fails inside Docker containers
echo '#!/bin/sh
exit 0' > /usr/share/live/build/hooks/normal/5030-update-plocate-database.hook.chroot || true

echo " === Executing Chroot Hooks and Hacks === "
# lb chroot_hooks || true
lb chroot_hacks || true

echo "=== UNMOUNTING OVERLAYFS before lb binary ==="
umount /work/build/chroot 2>/dev/null || umount -lf /work/build/chroot 2>/dev/null || true
echo "=== overlay unmounted ==="

echo "=== run lb binary ==="
lb binary
echo "=== lb binary finished with exit code: $? ==="

echo '=== Official Artifact Integration ==='
OUT_DIR=/work/iso-output
OUT_ISO=\/AlfredLinux-Alpha-Matrix-7.77-x86_64.iso
if [ -f /work/build/live-image-amd64.hybrid.iso ]; then
  mkdir -p $OUT_DIR
  mv /work/build/live-image-amd64.hybrid.iso $OUT_DIR$OUT_ISO
  echo '[inner] Signaling Host-side Signer Daemon...'
  touch $OUT_DIR/build-complete.marker
fi
