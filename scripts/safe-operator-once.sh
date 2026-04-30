#!/usr/bin/env bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# safe-operator-once.sh — run ON the Alfred build/web host with sudo (password once).
# Idempotent-ish: safe to re-run. Does not print secrets.
#
# Usage:  sudo bash scripts/safe-operator-once.sh
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
[[ "$(id -u)" -eq 0 ]] || { echo "error: run with sudo" >&2; exit 1; }

export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get install -y --no-install-recommends shellcheck

SRC="$ROOT/build-assets/forge/docs/index.html"
DST="/home/gositeme/domains/alfredlinux.com/public_html/forge/docs/index.html"
if [[ -f "$SRC" ]]; then
  install -D -m0644 -o apache -g apache "$SRC" "$DST"
  echo "OK: installed GoForge docs to $DST"
else
  echo "warn: missing $SRC (skip docs install)" >&2
fi

FORGE_DIR="/home/gositeme/domains/alfredlinux.com/public_html/forge"
if [[ -d "$FORGE_DIR" ]]; then
  chown -R gositeme:gositeme "$FORGE_DIR" 2>/dev/null || chown -R apache:apache "$FORGE_DIR" || true
  find "$FORGE_DIR" -type d -exec chmod 0755 {} \;
  find "$FORGE_DIR" -type f -exec chmod 0644 {} \;
  echo "OK: adjusted ownership/permissions under $FORGE_DIR (best-effort)"
fi

echo "Done. Rotate any deploy token that was ever embedded in a git remote URL."
