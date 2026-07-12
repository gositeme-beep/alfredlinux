#!/usr/bin/env bash
# One thing to run: full backup in background (all discovered DBs + domains + almost all $HOME + .git in sites).
cd "$(dirname "$0")/.." || exit 1
mkdir -p "$HOME/backups"
nohup env PARANOID_FULL_HOME=1 ECOSYSTEM_INCLUDE_GIT=1 bash "$(dirname "$0")/ecosystem-full-backup.sh" \
  >>"$HOME/backups/ecosystem-nohup.log" 2>&1 &
echo "Started backup PID $!"
echo "Your files will appear under: $HOME/backups/ecosystem-export-YYYYMMDD-HHMMSS/"
echo "Watch the newest ecosystem-*.log inside that folder (tail -f)."
