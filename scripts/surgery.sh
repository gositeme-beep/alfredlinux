#!/bin/bash
export DEBIAN_FRONTEND=noninteractive
mount -t proc proc /work/build/chroot/proc || true
mount -t sysfs sys /work/build/chroot/sys || true
chroot /work/build/chroot dpkg --configure -a
chroot /work/build/chroot apt-get install -f -y
umount /work/build/chroot/proc || true
umount /work/build/chroot/sys || true
