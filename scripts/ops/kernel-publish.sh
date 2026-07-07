#!/usr/bin/env bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# Alfred Linux — Kernel Publisher Script
# Takes built kernel .deb files, hashes them, signs them, and stages them for the web server

set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
source "$ROOT/config/kernel.env"

ASSETS_DIR="$ROOT/build-assets/kernel-${ALFRED_KERNEL_VERSION}-debs"
PUBLISH_DIR="$ROOT/build-assets/kernel-publish"
LATEST_FILE="$PUBLISH_DIR/LATEST"

echo "=== Alfred Kernel Publisher ==="

if [[ ! -d "$ASSETS_DIR" ]]; then
    echo "ERROR: Built kernel debs not found in $ASSETS_DIR" >&2
    echo "Please run the kernel docker build first." >&2
    exit 1
fi

# DISABLED-v14.14: rm -rf "$PUBLISH_DIR"
mkdir -p "$PUBLISH_DIR"

echo "Copying .deb files..."
cp "$ASSETS_DIR"/*.deb "$PUBLISH_DIR/"

echo "Generating cryptographic hashes and signatures..."
cd "$PUBLISH_DIR"

for deb in *.deb; do
    echo "Hashing $deb..."
    sha256sum "$deb" > "$deb.sha256"
    
    # We use a placeholder local key for signing in the dev environment.
    # In production, this would use the official Alfred release key.
    # If GPG is not configured, we'll create a mock signature for testing,
    # but the real server will replace this with a real signature.
    if gpg --list-secret-keys >/dev/null 2>&1; then
        echo "Signing $deb.sha256..."
        gpg --yes --armor --detach-sign "$deb.sha256"
    else
        echo "WARN: No GPG secret keys found. Creating mock signature for $deb.sha256" >&2
        echo "MOCK_SIGNATURE" > "$deb.sha256.asc"
    fi
done

echo "$ALFRED_KERNEL_VERSION" > "$LATEST_FILE"

echo "=== Publish Complete ==="
echo "Artifacts staged at: $PUBLISH_DIR"
echo "To deploy, copy the contents of $PUBLISH_DIR to /var/www/alfredlinux.com/downloads/kernel/"
