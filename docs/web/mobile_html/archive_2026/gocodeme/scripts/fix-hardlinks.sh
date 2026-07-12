#!/bin/bash
# Fix hard links in node_modules that break DirectAdmin backups.
# Safe to run anytime — only touches files with link count > 1.
# Usage: ./fix-hardlinks.sh [directory]  (defaults to gocodeme project root)

DIR="${1:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
FIXED=0

while IFS= read -r -d '' f; do
  cp --remove-destination "$f" "$f.tmp" && mv "$f.tmp" "$f"
  FIXED=$((FIXED + 1))
done < <(find "$DIR" -type f -links +1 -print0 2>/dev/null)

if [ "$FIXED" -gt 0 ]; then
  echo "fix-hardlinks: broke $FIXED hard link(s) in $DIR"
else
  echo "fix-hardlinks: no hard links found"
fi
