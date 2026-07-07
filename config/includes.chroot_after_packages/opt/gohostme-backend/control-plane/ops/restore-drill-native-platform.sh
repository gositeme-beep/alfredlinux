#!/bin/bash
set -euo pipefail

BASE="/home/root/backups/native-platform"
LOG="/home/root/gohostme/control-plane/ops/restore-drill-native.log"
TMP="/tmp/native-restore-drill"
NOTIFY_SCRIPT="/home/root/gohostme/control-plane/ops/notify-alert.sh"

mkdir -p "$(dirname "$LOG")" "$TMP"

log() {
  echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "$LOG"
}

on_err() {
  if [[ -x "$NOTIFY_SCRIPT" ]]; then
    "$NOTIFY_SCRIPT" "critical" "restore-drill" "restore-drill-native-platform.sh failed at line $1" >/dev/null 2>&1 || true
  fi
}
trap 'on_err $LINENO' ERR

log "RESTORE_DRILL_START"

latest_dir=$(find "$BASE" -maxdepth 1 -mindepth 1 -type d -name '20*' | sort | tail -n 1)
if [[ -z "$latest_dir" ]]; then
  log "ERROR: no dated native backup directory found"
  exit 1
fi

db_gz=$(find "$latest_dir" -maxdepth 1 -type f -name '*native.sql.gz' | head -n 1)
code_tgz=$(find "$latest_dir" -maxdepth 1 -type f -name 'control-plane.tar.gz' | head -n 1)
sha_file="$latest_dir/SHA256SUMS.txt"

[[ -f "$db_gz" ]] || { log "ERROR: db dump missing"; exit 1; }
[[ -f "$code_tgz" ]] || { log "ERROR: code archive missing"; exit 1; }
[[ -f "$sha_file" ]] || { log "ERROR: checksum file missing"; exit 1; }

( cd "$latest_dir" && sha256sum -c SHA256SUMS.txt ) >/tmp/native-drill-sha.out 2>&1 || {
  log "ERROR: checksum verification failed"
  cat /tmp/native-drill-sha.out >> "$LOG"
  exit 1
}

# Validate archive and dump integrity.
gzip -t "$db_gz"
tar -tzf "$code_tgz" >/tmp/native-drill-tar.list

# Lightweight SQL parse test by extracting first statements without SIGPIPE issues.
gzip -dc "$db_gz" | awk 'NR<=200{print}' > "$TMP/head.sql"
if ! grep -Eiq 'CREATE TABLE|INSERT INTO|LOCK TABLES' "$TMP/head.sql"; then
  log "ERROR: SQL dump head does not look valid"
  exit 1
fi

log "RESTORE_DRILL_OK:$(basename "$latest_dir")"
