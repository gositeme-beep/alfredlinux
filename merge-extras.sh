#!/bin/bash
set -e
cd /home/gositeme/law/alfredlinux-com-source-live/config/hooks/live/

# 1. Merge 0001-alfred-prestage-assets.hook.chroot into 0001-alfred-merged-auto.hook.chroot
echo '# --- PRESTAGE ASSETS MERGED ---' > /tmp/0001.tmp
cat 0001-alfred-prestage-assets.hook.chroot >> /tmp/0001.tmp
cat 0001-alfred-merged-auto.hook.chroot >> /tmp/0001.tmp
mv /tmp/0001.tmp 0001-alfred-merged-auto.hook.chroot
chmod +x 0001-alfred-merged-auto.hook.chroot

# 2. Merge 9999-alfred-poststage-cleanup.hook.chroot into 1335-alfred-merged-auto.hook.chroot
echo '# --- POSTSTAGE CLEANUP MERGED ---' >> 1335-alfred-merged-auto.hook.chroot
cat 9999-alfred-poststage-cleanup.hook.chroot >> 1335-alfred-merged-auto.hook.chroot

# 3. Delete the extras
rm 0001-alfred-prestage-assets.hook.chroot
rm 9999-alfred-poststage-cleanup.hook.chroot

# 4. Count hooks
count=\
echo " Total hooks now: \\
