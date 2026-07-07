#!/usr/bin/env bash
# SPDX-License-Identifier: Apache-2.0
# SPDX-FileCopyrightText: 2026 Alfred Trust <alfred@alfredlinux.com>
# In the name of Yeshua, Jesus Christ of Bethlehem, King of the Universe.
set -euo pipefail
ROOT=/home/root/law/alfredlinux-com-source-live
SRC=$ROOT/public-status-site/
DEST=${ALFRED_PUBLIC_STATUS_PUBLISH_DIR:-/home/root/domains/alfredlinux.com/public_html/status/}
mkdir -p "$DEST"
rsync -a --delete --exclude '.gitkeep' "$SRC" "$DEST"
echo "Published $SRC -> $DEST"
