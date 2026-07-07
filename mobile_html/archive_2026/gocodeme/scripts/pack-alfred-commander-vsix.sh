#!/bin/bash
# Re-pack Alfred Commander for Theia (Alfred IDE). Run after editing the extension source.
set -e
SRC="${ALFRED_COMMANDER_SRC:-$HOME/.local/share/code-server/extensions/gositeme.alfred-commander-1.0.0}"
OUT="${ALFRED_COMMANDER_VSIX:-$(dirname "$0")/../theia-fork/plugins/gositeme.alfred-commander-1.0.0.vsix}"
if [[ ! -f "$SRC/package.json" ]]; then
  echo "Missing $SRC/package.json"
  exit 1
fi
cd "$SRC"
npx --yes @vscode/vsce@2.24.0 package --allow-missing-repository --out "$OUT"
echo "OK: $OUT"
ls -la "$OUT"
