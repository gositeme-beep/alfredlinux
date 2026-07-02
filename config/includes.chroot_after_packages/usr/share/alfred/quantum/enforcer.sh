#!/bin/bash
# kyber-1024-enforcer.sh
# Rewrites EVERY kyber-512/kyber-768/ML-KEM-512/ML-KEM-768 reference
# to KYBER-1024 / ML-KEM-1024 across the codebase + chroot config.
#
# Skips: chroot binaries (would corrupt them — handled separately by
#        rebuilding liboqs/oqs-provider during 0166-alfred-quantum.hook).
#        VCS dirs, node_modules, vendor, build/binary, prior backups.
#
# Run from the build host. No sudo needed for the source tree;
# sudo IS needed for chroot/etc and chroot/usr/local/etc paths.
#
#   sudo bash /home/root/kyber-1024-enforcer.sh

set -euo pipefail

TS=$(date +%Y%m%d-%H%M%S)
LOG=/tmp/kyber-1024-rewrite-$TS.log
echo "kyber-1024 enforcer  ·  $(date)" | tee "$LOG"

ROOTS=(
  /home/root/alfred-linux-v2
  /home/root/public_html
  /home/root/.gocodeme
  /home/root/env
)

# Build the file list ONCE — only text files, exclude binaries + heavy dirs
echo "[1/3] Scanning…" | tee -a "$LOG"
> /tmp/kyber-targets.txt
for r in "${ROOTS[@]}"; do
    [ -d "$r" ] || continue
    grep -rIlE \
      --exclude-dir=.git \
      --exclude-dir=node_modules \
      --exclude-dir=vendor \
      --exclude-dir=binary \
      --exclude-dir='hooks.bak-*' \
      --exclude-dir='.level777-bak-*' \
      --exclude='*.bak' --exclude='*.broken-*' --exclude='*.old-*' \
      --exclude='*.png' --exclude='*.jpg' --exclude='*.mp4' --exclude='*.iso' \
      --exclude='*.squashfs' --exclude='*.gz' --exclude='*.xz' --exclude='*.zst' \
      --exclude='*.so*' --exclude='*.a' \
      'kyber.?(512|768)|ML.?KEM.?(512|768)|MLKEM(512|768)|KYBER(512|768)' \
      "$r" 2>/dev/null \
    | grep -v '/build/chroot/usr/' \
    | grep -v '/build/chroot/var/' \
    | grep -v '/build/chroot/lib/' \
    | grep -v '/build/chroot/sbin/' \
    | grep -v '/build/chroot/bin/' \
    >> /tmp/kyber-targets.txt || true
done
N=$(wc -l < /tmp/kyber-targets.txt)
echo "  $N file(s) need rewrites" | tee -a "$LOG"
[ "$N" = "0" ] && { echo "  ✓ already canonical"; exit 0; }

echo "[2/3] Rewriting (with .bak-$TS backups)…" | tee -a "$LOG"
while IFS= read -r f; do
    [ -f "$f" ] || continue
    cp -a "$f" "$f.bak-$TS"
    # Cover every spelling: kyber512, kyber-512, kyber_512, Kyber512, KYBER768,
    # ml-kem-512, ML-KEM-768, mlkem512, MLKEM768. Always ⇒ 1024 variant.
    sed -i -E '
        s/(KYBER|Kyber|kyber)([._-]?)(512|768)/\11024/g;
        s/(ML[._-]?KEM)([._-]?)(512|768)/ML-KEM-1024/g;
        s/(ml[._-]?kem)([._-]?)(512|768)/ml-kem-1024/g;
        s/(MLKEM)(512|768)/MLKEM1024/g;
        s/(mlkem)(512|768)/mlkem1024/g;
    ' "$f"
    echo "  ✓ $f" | tee -a "$LOG"
done < /tmp/kyber-targets.txt

echo "[3/3] Verifying zero 768/512 hits remain…" | tee -a "$LOG"
REM=0
for r in "${ROOTS[@]}"; do
    [ -d "$r" ] || continue
    H=$(grep -rIlE \
        --exclude-dir=.git --exclude-dir=node_modules --exclude-dir=vendor \
        --exclude-dir=binary --exclude='*.bak-*' --exclude='*.broken-*' \
        --exclude='*.png' --exclude='*.jpg' --exclude='*.mp4' --exclude='*.iso' \
        --exclude='*.so*' --exclude='*.a' \
        'kyber.?(512|768)|ML.?KEM.?(512|768)|MLKEM(512|768)|KYBER(512|768)' \
        "$r" 2>/dev/null \
        | grep -v '/build/chroot/usr/' \
        | grep -v '/build/chroot/var/' \
        | grep -v '/build/chroot/lib/' \
        | wc -l)
    REM=$((REM + H))
done
echo "  remaining 512/768 references: $REM" | tee -a "$LOG"

if [ "$REM" = "0" ]; then
    echo "  ✓ KYBER-1024 EVERYWHERE — Soli Deo Gloria" | tee -a "$LOG"
else
    echo "  ⚠ inspect the log: $LOG" | tee -a "$LOG"
fi
echo
echo "Backups: *.bak-$TS alongside each modified file"
echo "Log:     $LOG"
