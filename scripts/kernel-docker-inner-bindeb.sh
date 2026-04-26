#!/bin/bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# Executed *inside* Debian container; /work is bind-mounted from the host.
set -euo pipefail
export DEBIAN_FRONTEND=noninteractive
NJOBS="${NJOBS:-12}"
UIDH="${BUILD_UID:-1000}"
GIDH="${BUILD_GID:-1000}"

apt-get update -y
apt-get install -y --no-install-recommends \
  build-essential fakeroot bc bison flex \
  libssl-dev libelf-dev libncurses-dev dwarves pahole \
  libdw-dev debhelper devscripts rsync cpio kmod wget xz-utils \
  gcc g++ make python3

cd /work/linux-7.0.1
test -f .config || make ARCH=x86_64 x86_64_defconfig

fakeroot make -j"$NJOBS" ARCH=x86_64 bindeb-pkg LOCALVERSION= KDEB_PKGVERSION=7.0.1-1alfred

chown "$UIDH:$GIDH" /work/linux-image-*.deb /work/linux-headers-*.deb /work/linux-libc-dev_*.deb 2>/dev/null || true
ls -lh /work/linux-image-*.deb /work/linux-headers-*.deb 2>/dev/null || true
