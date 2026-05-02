#!/usr/bin/env bash
set -euo pipefail
ROOT=/home/gositeme/law/alfredlinux-com-source-live
LOG=$ROOT/night-shift-logs/public-status-update.log
mkdir -p "$ROOT/night-shift-logs"
{
  echo "[public-update $(date -Is)] start"
  "$ROOT/scripts/ops/export-public-status.py"
  "$ROOT/scripts/ops/generate-public-images.py"
  "$ROOT/scripts/ops/publish-public-status-site.sh"
  echo "[public-update $(date -Is)] done"
} >>"$LOG" 2>&1
