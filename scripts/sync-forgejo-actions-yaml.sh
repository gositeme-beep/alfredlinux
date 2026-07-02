#!/usr/bin/env bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# Keep .forgejo/workflows/security-audit.yml identical to GoForge canonical
# .gitea/workflows/security-audit.yml (on-disk path the Actions engine expects on
# https://alfredlinux.com/forge/). Some installs dispatch only one tree — run after edits.
set -euo pipefail
ROOT=$(cd "$(dirname "$0")/.." && pwd)
SRC="$ROOT/.gitea/workflows/security-audit.yml"
DST="$ROOT/.forgejo/workflows/security-audit.yml"
if [[ ! -f "$SRC" ]]; then
  echo "missing: $SRC" >&2
  exit 1
fi
mkdir -p "$(dirname "$DST")"
{
  echo '# Forgejo Actions — GENERATED from GoForge canonical .gitea/workflows/security-audit.yml'
  echo '# Do not edit by hand; run: bash scripts/sync-forgejo-actions-yaml.sh'
  tail -n +6 "$SRC"
} >"$DST.tmp"
mv -f "$DST.tmp" "$DST"
# Body is YAML from `name:` onward — must match canonical from the same line.
if ! tail -n +6 "$SRC" | cmp -s - <(tail -n +3 "$DST"); then
  echo "internal error: sync produced mismatch" >&2
  exit 1
fi
echo "OK: synced $DST from $SRC (YAML body from name: onward is identical)."
