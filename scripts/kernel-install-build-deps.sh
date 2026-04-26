#!/usr/bin/env bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# sudo bash scripts/kernel-install-build-deps.sh   — once per machine, before bindeb-pkg
set -euo pipefail
[[ "$(id -u)" -eq 0 ]] || { echo "Run: sudo bash $0" >&2; exit 1; }
apt-get update -y
apt-get install -y debhelper libdw-dev libncurses-dev dwarves pahole
