#!/bin/bash
set -e
echo '[fusion] Fusing offline packages from master-vault/packages.chroot into build/cache/bootstrap...'
docker run --rm \
  -v /home/gositeme/law/alfredlinux-com-source-live/build/cache/bootstrap:/chroot \
  -v /home/gositeme/law/alfredlinux-com-source-live/master-vault/packages.chroot:/packages \
  debian:trixie chroot /chroot bash -c 'dpkg -i --force-all -R /packages/ || apt-get install -f -y'
echo '[fusion] Done.'
