#!/usr/bin/env bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# Download linux-7.0.1 tarball + patch into KERNEL_WORK (default: sibling dir kernel-7.0.3-work). Does NOT compile.
set -euo pipefail
REPO="$(cd "$(dirname "$0")/.." && pwd)"
BASE="${KERNEL_WORK:-$REPO/../kernel-7.0.3-work}"
mkdir -p "$BASE"
cd "$BASE"

TAR="linux-7.0.3.tar.xz"
PATCH="patch-7.0.3.xz"
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

# Supply-chain: compare tarball + patch to lines in kernel.org signed sha256sums.asc
# (clearsigned body lines are sha256sum-compatible). Skip only for airgap/debug:
#   ALFRED_SKIP_KERNEL_SHA256_VERIFY=1 bash scripts/kernel-download-7.0.1.sh
verify_kernel_sha256() {
  if [[ "${ALFRED_SKIP_KERNEL_SHA256_VERIFY:-}" == 1 ]]; then
    echo "WARN: skipping SHA256 verify (ALFRED_SKIP_KERNEL_SHA256_VERIFY=1) — not for production ISOs" >&2
    return 0
  fi
  local asc sums
  asc=$(mktemp)
  sums=$(mktemp)
  cleanup() { rm -f "$asc" "$sums"; }
  trap cleanup RETURN
  curl -fsSL --retry 3 -o "$asc" "${BASE_URL}/sha256sums.asc"
  grep -E '^[0-9a-f]{64}  linux-7\.0\.1\.tar\.xz$' "$asc" >"$sums" || true
  grep -E '^[0-9a-f]{64}  patch-7\.0\.1\.xz$' "$asc" >>"$sums" || true
  if ! [[ -s "$sums" ]]; then
    echo "error: could not extract expected lines from sha256sums.asc (CDN format change?)" >&2
    return 1
  fi
  ( cd "$BASE" && sha256sum -c "$sums" ) || {
    echo "error: SHA256 mismatch — delete $TAR / $PATCH and retry, or verify mirror" >&2
    return 1
  }
  echo "OK: $TAR and $PATCH match https://cdn.kernel.org/.../sha256sums.asc (GNU sha256sum -c)"
}

verify_kernel_sha256

echo "=== Optional: gpg --verify sha256sums.asc (needs kernel.org signing keys in keyring) ==="
echo "=== Next: tar xf $TAR ; cd linux-7.0.1 ; xzcat ../$PATCH | patch -p1"
echo "=== Then merge Alfred .config / patches, then bindeb-pkg (README-KERNEL7.txt) ==="
