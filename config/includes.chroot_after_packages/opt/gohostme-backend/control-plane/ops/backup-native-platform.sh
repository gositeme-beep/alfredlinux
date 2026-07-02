#!/bin/bash
set -euo pipefail

TS="$(date +%Y%m%d-%H%M%S)"
BASE="/home/root/backups/native-platform"
RUN_DIR="$BASE/$TS"
NOTIFY_SCRIPT="/home/root/gohostme/control-plane/ops/notify-alert.sh"
mkdir -p "$RUN_DIR"

on_err() {
  if [[ -x "$NOTIFY_SCRIPT" ]]; then
    "$NOTIFY_SCRIPT" "critical" "backup-native" "backup-native-platform.sh failed at line $1" >/dev/null 2>&1 || true
  fi
}
trap 'on_err $LINENO' ERR

DB_DUMP="$RUN_DIR/root_whmcs-native.sql.gz"
CODE_ARCHIVE="$RUN_DIR/control-plane.tar.gz"
META="$RUN_DIR/manifest.txt"

mysqldump --defaults-file=/home/root/.my.cnf -S /run/mysql/mysql.sock root_whmcs \
  control_jobs control_events gateway_webhook_events billing_events control_settings services invoices orders order_items payment_transactions products clients \
  | gzip -9 > "$DB_DUMP"

tar -czf "$CODE_ARCHIVE" \
  /home/root/gohostme/control-plane \
  /home/root/domains/root.com/public_html/control \
  /home/root/domains/root.com/public_html/whmcs/modules/servers/gohostme

{
  echo "timestamp=$TS"
  echo "host=$(hostname)"
  echo "db_dump=$(basename "$DB_DUMP")"
  echo "code_archive=$(basename "$CODE_ARCHIVE")"
  echo "mode=$(mysql --defaults-file=/home/root/.my.cnf -S /run/mysql/mysql.sock root_whmcs -N -e \"SELECT value FROM control_settings WHERE \\\`key\\\`='whmcs_cutover_mode' LIMIT 1;\" 2>/dev/null || echo unknown)"
} > "$META"

( cd "$RUN_DIR" && sha256sum *.gz manifest.txt > SHA256SUMS.txt )

# Keep 30 daily backup folders
find "$BASE" -mindepth 1 -maxdepth 1 -type d | sort | head -n -30 | xargs -r rm -rf

echo "NATIVE_BACKUP_OK:$RUN_DIR"
