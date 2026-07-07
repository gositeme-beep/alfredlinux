#!/bin/bash
for f in config/hooks/live/*.hook.chroot build-assets/*.sh build-assets/wallpapers/scripts/*.sh; do
  [ -f "$f" ] || continue
  if ! head -n 12 "$f" | grep -q '^# SPDX-License-Identifier: AGPL-3.0-or-later'; then
    awk 'NR==1 && /^#!/ {print; print "# SPDX-License-Identifier: AGPL-3.0-or-later"; next} NR==1 {print "# SPDX-License-Identifier: AGPL-3.0-or-later"; print; next} 1' "$f" > "$f.tmp" && mv "$f.tmp" "$f"
  fi
done
