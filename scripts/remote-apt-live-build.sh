#!/usr/bin/env bash
# Run ON THE UBUNTU BUILDER as root after: sudo su -
set -euo pipefail
export DEBIAN_FRONTEND=noninteractive
apt-get update -y
apt-get install -y \
  live-build debootstrap squashfs-tools xorriso isolinux syslinux syslinux-common \
  grub-pc-bin grub-efi-amd64-bin mtools dosfstools rsync curl ca-certificates \
  build-essential git fakeroot cpio

echo "OK: which lb -> $(command -v lb)"; lb --version | head -1
