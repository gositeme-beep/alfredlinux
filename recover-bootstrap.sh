#!/bin/bash
set -e
echo 'Starting Trap 89 Recovery: Unsquashing ISO to rebuild cache/bootstrap...'
docker run --rm --privileged -v /home/gositeme/law/alfredlinux-com-source-live:/work debian:trixie bash -c 'apt-get update -qq && apt-get install -y squashfs-tools && mkdir -p /tmp/iso-mount && mount -o loop,ro /work/iso-output/AlfredLinux-Alpha-Matrix-7.77-x86_64.iso /tmp/iso-mount && echo " Unsquashing into /work/cache/bootstrap...\ && mkdir -p /work/cache/bootstrap && unsquashfs -f -d /work/cache/bootstrap /tmp/iso-mount/live/filesystem.squashfs && echo Done && du -sh /work/cache/bootstrap && umount /tmp/iso-mount'
