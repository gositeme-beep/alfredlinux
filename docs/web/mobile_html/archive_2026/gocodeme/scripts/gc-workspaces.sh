#!/bin/bash
# ─────────────────────────────────────────────────────────────────────────────
# GoCodeMe — Stale Workspace Garbage Collector
# Cleans up /tmp/gocodeme-workspace-* directories not accessed in 7+ days.
#
# Add to crontab:
#   0 3 * * * /home/gositeme/domains/gositeme.com/public_html/gocodeme/scripts/gc-workspaces.sh >> /home/gositeme/domains/gositeme.com/public_html/gocodeme/logs/gc.log 2>&1
# ─────────────────────────────────────────────────────────────────────────────

set -euo pipefail

MAX_AGE_DAYS=7
WORKSPACE_DIR="/tmp"
PATTERN="gocodeme-workspace-*"
LOG_PREFIX="[gc-workspaces]"

echo "${LOG_PREFIX} $(date '+%Y-%m-%d %H:%M:%S') — Starting garbage collection"

count=0
freed=0

for dir in ${WORKSPACE_DIR}/${PATTERN}; do
  [ -d "$dir" ] || continue

  # Check last access time
  last_access=$(stat -c %X "$dir" 2>/dev/null || echo 0)
  now=$(date +%s)
  age_days=$(( (now - last_access) / 86400 ))

  if [ "$age_days" -ge "$MAX_AGE_DAYS" ]; then
    size=$(du -sh "$dir" 2>/dev/null | awk '{print $1}' || echo "?")
    username=$(basename "$dir" | sed 's/gocodeme-workspace-//')
    echo "${LOG_PREFIX}   Removing ${dir} (user: ${username}, age: ${age_days}d, size: ${size})"
    rm -rf "$dir"
    count=$((count + 1))
  fi
done

echo "${LOG_PREFIX} $(date '+%Y-%m-%d %H:%M:%S') — Done. Removed ${count} stale workspace(s)."
