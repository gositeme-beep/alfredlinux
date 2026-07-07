#!/bin/bash
# Repair hooks damaged by multiple SPDX injection passes:
# 1. Remove all duplicate SPDX lines
# 2. Ensure shebang is on line 1
# 3. Place exactly one SPDX on line 2

for f in config/hooks/live/*.hook.chroot build-assets/*.sh build-assets/wallpapers/scripts/*.sh; do
  [ -f "$f" ] || continue

  # Strip all SPDX lines from the file
  grep -v '^# SPDX-License-Identifier: AGPL-3.0-or-later$' "$f" > "$f.tmp"

  # Check if line 1 is a shebang
  first=$(head -n 1 "$f.tmp")
  if echo "$first" | grep -q '^#!'; then
    # Insert single SPDX after shebang
    sed -i '1a# SPDX-License-Identifier: AGPL-3.0-or-later' "$f.tmp"
  else
    # Insert shebang + SPDX at top (default to /bin/sh)
    sed -i '1i#!/bin/sh\n# SPDX-License-Identifier: AGPL-3.0-or-later' "$f.tmp"
  fi

  mv "$f.tmp" "$f"
done
