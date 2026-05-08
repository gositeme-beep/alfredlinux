#!/usr/bin/env bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# Copy linux-image-7.0.4*.deb (+ headers, libc-dev) from KERNEL_WORK into repo config/packages.chroot/
# (strict: exit 1 if nothing to copy). Prefer stage-kernel-debs-for-iso.sh for archive + work in one path.
set -euo pipefail
REPO="$(cd "$(dirname "$0")/.." && pwd)"
exec bash "$REPO/scripts/stage-kernel-debs-for-iso.sh" --strict
