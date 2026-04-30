#!/usr/bin/env bash
# SPDX-License-Identifier: AGPL-3.0-or-later
# Keep .forgejo/workflows/security-audit.yml identical to GoForge canonical
# .gitea/workflows/security-audit.yml (on-disk path the Actions engine expects).
# Some installs dispatch only one tree — run after editing the canonical file.
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
  echo '# See: docs/GOFORGE-INFRASTRUCTURE-UPGRADE.txt'
  tail -n +4 "$SRC"
} >"$DST.tmp"
mv -f "$DST.tmp" "$DST"
# Preserve Forgejo banner if someone edited only comments — body must match SRC from line 4
if ! tail -n +4 "$SRC" | cmp -s - <(tail -n +4 "$DST"); then
  echo "internal error: sync produced mismatch" >&2
  exit 1
fi
echo "OK: synced $DST from $SRC (body identical from name: line onward)."
