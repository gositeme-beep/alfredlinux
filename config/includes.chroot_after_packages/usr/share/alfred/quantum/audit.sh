#!/bin/bash
# kyber-audit.sh — find every kyber/ML-KEM reference and flag any 768 occurrences.
# Read-only; reports only. Followup script will rewrite.

echo "════════ KYBER / ML-KEM AUDIT ════════"
echo "Goal: KYBER-1024 / ML-KEM-1024 EVERYWHERE.  Zero 768.  Zero 512."
echo

ROOTS=(
  /home/root/alfred-linux-v2
  /home/root/public_html
  /home/root/.gocodeme
  /home/root/env
)

echo "─── 1. ALL kyber/ML-KEM hits across roots ───"
for r in "${ROOTS[@]}"; do
    [ -d "$r" ] || continue
    grep -rIn --exclude-dir={.git,node_modules,vendor,build/chroot/usr,build/chroot/var,build/binary,.bak*} \
        -E 'kyber[_-]?(512|768|1024)|ml[_-]?kem[_-]?(512|768|1024)|KYBER(512|768|1024)|MLKEM(512|768|1024)' \
        "$r" 2>/dev/null
done > /tmp/kyber-all.txt

TOTAL=$(wc -l < /tmp/kyber-all.txt)
echo "Total kyber/ml-kem hits: $TOTAL"
echo

echo "─── 2. ⚠ FORBIDDEN: any 768 hits ───"
grep -E '768' /tmp/kyber-all.txt | tee /tmp/kyber-768.txt
N768=$(wc -l < /tmp/kyber-768.txt)
echo "→ 768 hits: $N768   (must be zero)"
echo

echo "─── 3. ⚠ FORBIDDEN: any 512 hits ───"
grep -E '512' /tmp/kyber-all.txt | tee /tmp/kyber-512.txt
N512=$(wc -l < /tmp/kyber-512.txt)
echo "→ 512 hits: $N512   (must be zero)"
echo

echo "─── 4. ✓ DESIRED: 1024 hits ───"
grep -cE '1024' /tmp/kyber-all.txt
echo

echo "─── 5. Quick sample of all hits ───"
head -40 /tmp/kyber-all.txt

echo
echo "─── 6. Inside chroot binaries (oqs, openssl, liboqs) ───"
C=/home/root/alfred-linux-v2/build/chroot
for bin in "$C/usr/bin/openssl" "$C/usr/local/bin/alfred-encrypt-quantum" "$C/usr/local/bin/alfred-encrypt-status"; do
    if [ -f "$bin" ]; then
        echo "  ▸ $bin"
        strings "$bin" 2>/dev/null | grep -iE 'kyber|ml.?kem' | sort -u | head -10
    fi
done

echo
echo "─── 7. liboqs config / openssl provider config in chroot ───"
find "$C/etc" "$C/usr/local/etc" -type f \( -name '*.cnf' -o -name '*.conf' -o -name '*.json' \) 2>/dev/null | \
    xargs grep -lIE 'kyber|ml.?kem|oqs' 2>/dev/null | head

echo
echo "════════ END AUDIT ════════"
echo "Reports:"
echo "  /tmp/kyber-all.txt"
echo "  /tmp/kyber-768.txt"
echo "  /tmp/kyber-512.txt"
