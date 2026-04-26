#!/usr/bin/env bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# Download linux-7.0.1 tarball + patch into KERNEL_WORK (default: sibling dir kernel-7.0.1-work). Does NOT compile.
set -euo pipefail
REPO="$(cd "$(dirname "$0")/.." && pwd)"
BASE="${KERNEL_WORK:-$REPO/../kernel-7.0.1-work}"
mkdir -p "$BASE"
cd "$BASE"

TAR="linux-7.0.1.tar.xz"
PATCH="patch-7.0.1.xz"
BASE_URL="https://cdn.kernel.org/pub/linux/kernel/v7.x"

echo "=== Downloading into $BASE ==="
for f in "$TAR" "$PATCH"; do
  if [[ -f "$f" ]]; then
    echo "exists: $f ($(( $(stat -c%s "$f") / 1048576 )) MiB)"
  else
    echo "fetch: $BASE_URL/$f"
    curl -fL --retry 3 -C - -o "$f" "$BASE_URL/$f"
  fi
done

echo "=== Verify sha256 against https://cdn.kernel.org/pub/linux/kernel/v7.x/sha256sums.asc ==="
echo "  (cd \"$BASE\" && sha256sum $TAR $PATCH)"

echo "=== Next: tar xf $TAR ; cd linux-7.0.1 ; xzcat ../$PATCH | patch -p1"
echo "=== Then merge Alfred .config / patches, then bindeb-pkg (README-KERNEL7.txt) ==="
