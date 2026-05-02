#!/usr/bin/env bash
set -euo pipefail
ROOT=/home/gositeme/law/alfredlinux-com-source-live
SRC=$ROOT/public-status-site/
DEST=${ALFRED_PUBLIC_STATUS_PUBLISH_DIR:-/home/gositeme/domains/alfredlinux.com/public_html/status/}
mkdir -p "$DEST"
rsync -a --delete --exclude '.gitkeep' "$SRC" "$DEST"
echo "Published $SRC -> $DEST"
