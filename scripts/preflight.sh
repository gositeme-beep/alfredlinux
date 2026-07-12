#!/bin/bash
echo "=== 11. Master chroot integrity ==="
echo "bash: $(ls /work/build/cache/bootstrap/usr/bin/bash 2>/dev/null && echo YES || echo NO)"
echo "bootstrap size: $(du -sh /work/build/cache/bootstrap/ 2>/dev/null | cut -f1)"
echo "kernel: $(ls /work/build/cache/bootstrap/boot/vmlinuz-7.0.12 2>/dev/null && echo YES || echo NO)"
echo "NVIDIA dir: $(ls /work/build/cache/bootstrap/Nvidia_7.0.12_Support/ 2>/dev/null | head -2)"
echo "UE5: $(ls /work/build/cache/bootstrap/opt/unreal-engine/Engine/ 2>/dev/null && echo YES || echo NO)"
echo "opt apps: $(ls /work/build/cache/bootstrap/opt/ 2>/dev/null | wc -l)"
echo "initrd: $(ls /work/build/cache/bootstrap/boot/initrd.img-7.0.12 2>/dev/null && echo YES || echo NO)"
echo "plasmashell: $(ls /work/build/cache/bootstrap/usr/bin/plasmashell 2>/dev/null && echo YES || echo NO)"
echo "dpkg count: $(chroot /work/build/cache/bootstrap dpkg -l 2>/dev/null | grep '^ii' | wc -l)"

echo ""
echo "=== 12. UID 1004 ghost check ==="
echo "root-owned files sample: $(find /work/build/cache/bootstrap/etc -maxdepth 1 -not -user root 2>/dev/null | head -3)"
NONROOT=$(find /work/build/cache/bootstrap/usr/bin -maxdepth 1 -not -user root 2>/dev/null | wc -l)
echo "Non-root files in /usr/bin: $NONROOT"
if [ "$NONROOT" -gt 0 ]; then
    echo "WARNING: UID ghost detected! Must run chown -R root:root"
else
    echo "OK: All files root-owned"
fi

echo ""
echo "=== 13. Dell Watch log check ==="
ls -la /home/gositeme/law/alfredlinux-com-source-live/build/lb-docker-build.log 2>/dev/null || echo "lb-docker-build.log does not exist"
echo "dell-watch.php: $(ls /home/gositeme/domains/gositeme.com/public_html/veil/dell-watch.php 2>/dev/null && echo EXISTS || echo MISSING)"

echo ""
echo "=== 14. TMP Watchman ==="
ps aux | grep tmp-watchman | grep -v grep || echo "TMP Watchman NOT running"

echo ""
echo "=== 15. Boot config source check ==="
grep -c 'username=alfred' /work/config/bootloaders/grub-pc/grub.cfg 2>/dev/null || echo "grub-pc cfg: no username=alfred"
grep -c 'nvidia-drm.modeset=1' /work/config/bootloaders/grub-pc/grub.cfg 2>/dev/null || echo "grub-pc cfg: no nvidia-drm"

echo ""
echo "=== 16. run-build.sh hook fix verified ==="
grep -c 'cp.*hook.*chroot/tmp/_current_hook' /work/scripts/run-build.sh && echo "Hook fix APPLIED" || echo "Hook fix MISSING"
