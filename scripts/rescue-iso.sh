#!/bin/bash
set -e
export DEBIAN_FRONTEND=noninteractive

echo "=== install live-build ==="
apt-get update -qq
apt-get install -y --no-install-recommends live-build xorriso isolinux syslinux-common grub-pc-bin grub-efi-amd64-bin grub-efi-amd64-signed shim-signed mtools dosfstools squashfs-tools cpio rsync zstd file

cd /work/build

echo "=== Fixing tmp permissions ==="
chmod 1777 chroot/tmp || true

echo "=== Marking rootfs as done ==="
mkdir -p .build
touch .build/binary_rootfs
touch .build/binary_chroot

echo "=== Resuming lb binary ==="
lb binary

echo "=== Moving ISO ==="
OUT_DIR=/work/iso-output
OUT_ISO=/AlfredLinux-Alpha-Matrix-7.77-x86_64.iso
if [ -f /work/build/live-image-amd64.hybrid.iso ]; then
  mkdir -p $OUT_DIR
  mv /work/build/live-image-amd64.hybrid.iso $OUT_DIR$OUT_ISO
  echo '[inner] Signaling Host-side Signer Daemon...'
  touch $OUT_DIR/build-complete.marker
fi
