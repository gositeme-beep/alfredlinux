#!/usr/bin/env bash
# Build alfred-remote-ssh-*.vsix (requires Node + npx).
set -euo pipefail
cd "$(dirname "$0")"
exec npx --yes @vscode/vsce package "$@"
