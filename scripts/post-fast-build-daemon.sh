#!/bin/bash
while docker ps | grep -q alfred-lb-warp-speed-v12; do
  sleep 5
done
echo 'Docker v12 finished, moving ISO...'
if [ -f /home/gositeme/law/alfredlinux-com-source-live/build/live-image-amd64.hybrid.iso ]; then
  mkdir -p /home/gositeme/law/alfredlinux-com-source-live/iso-output
  mv /home/gositeme/law/alfredlinux-com-source-live/build/live-image-amd64.hybrid.iso /home/gositeme/law/alfredlinux-com-source-live/iso-output/AlfredLinux-Alpha-Matrix-7.77-x86_64.iso
  touch /home/gositeme/law/alfredlinux-com-source-live/iso-output/build-complete.marker
  echo 'Signer daemon signaled.'
fi
