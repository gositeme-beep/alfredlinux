#!/bin/bash
# SPDX-License-Identifier: Apache-2.0
# SPDX-FileCopyrightText: 2026 Alfred Trust <alfred@alfredlinux.com>
# In the name of Yeshua, Jesus Christ of Bethlehem, King of the Universe.
# kernel-swap-iso.sh — swap kernel debs into a built ISO without full rebuild.
# Reads config/packages.chroot/linux-image-*.deb and replaces vmlinuz/initrd in ISO.
# (Stub: full implementation pending — emits plan only.)
set -uo pipefail
ISO="${1:-}"
[[ -z "$ISO" || ! -f "$ISO" ]] && { echo "Usage: $0 <iso>"; exit 1; }
SRC="/home/gositeme/law/alfredlinux-com-source-live"
KDEB=$(ls "$SRC/config/packages.chroot/linux-image-"*.deb 2>/dev/null | head -1)
[[ -z "$KDEB" ]] && { echo "✗ no kernel deb in packages.chroot/"; exit 1; }
echo "[SWAP] Plan:"
echo "  ISO:    $ISO"
echo "  Kernel: $KDEB"
echo "  Steps:  1) extract vmlinuz+initrd from deb"
echo "          2) mount/extract ISO live/ dir"
echo "          3) replace vmlinuz/initrd"
echo "          4) regenerate squashfs, hybrid ISO"
echo "          5) re-run seal-iso.sh on result"
echo "[SWAP] Stub — implement when needed (use unsquashfs + mksquashfs + xorriso)"
