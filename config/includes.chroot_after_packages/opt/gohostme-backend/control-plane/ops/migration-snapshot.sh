#!/bin/bash
set -euo pipefail

OUT_DIR="/home/root/backups/native-platform/migration-snapshots"
NOTIFY_SCRIPT="/home/root/gohostme/control-plane/ops/notify-alert.sh"
mkdir -p "$OUT_DIR"
TS="$(date +%Y%m%d-%H%M%S)"
OUT="$OUT_DIR/migration-$TS.json"

on_err() {
  if [[ -x "$NOTIFY_SCRIPT" ]]; then
    "$NOTIFY_SCRIPT" "critical" "migration-snapshot" "migration-snapshot.sh failed at line $1" >/dev/null 2>&1 || true
  fi
}
trap 'on_err $LINENO' ERR

services=$(mysql --defaults-file=/home/root/.my.cnf -S /run/mysql/mysql.sock root_whmcs -N -e "SELECT COUNT(*) FROM services;" 2>/dev/null || echo 0)
invoices=$(mysql --defaults-file=/home/root/.my.cnf -S /run/mysql/mysql.sock root_whmcs -N -e "SELECT COUNT(*) FROM invoices;" 2>/dev/null || echo 0)
orders=$(mysql --defaults-file=/home/root/.my.cnf -S /run/mysql/mysql.sock root_whmcs -N -e "SELECT COUNT(*) FROM orders;" 2>/dev/null || echo 0)
whmcs_hosting=$(mysql --defaults-file=/home/root/.my.cnf -S /run/mysql/mysql.sock root_whmcs -N -e "SELECT COUNT(*) FROM tblhosting;" 2>/dev/null || echo 0)
whmcs_invoices=$(mysql --defaults-file=/home/root/.my.cnf -S /run/mysql/mysql.sock root_whmcs -N -e "SELECT COUNT(*) FROM tblinvoices;" 2>/dev/null || echo 0)
whmcs_orders=$(mysql --defaults-file=/home/root/.my.cnf -S /run/mysql/mysql.sock root_whmcs -N -e "SELECT COUNT(*) FROM tblorders;" 2>/dev/null || echo 0)
mode=$(mysql --defaults-file=/home/root/.my.cnf -S /run/mysql/mysql.sock root_whmcs -N -e "SELECT value FROM control_settings WHERE \`key\`='whmcs_cutover_mode' LIMIT 1;" 2>/dev/null || echo unknown)

cat > "$OUT" <<JSON
{
  "timestamp": "$TS",
  "mode": "$mode",
  "native": {
    "services": $services,
    "invoices": $invoices,
    "orders": $orders
  },
  "whmcs": {
    "tblhosting": $whmcs_hosting,
    "tblinvoices": $whmcs_invoices,
    "tblorders": $whmcs_orders
  },
  "delta": {
    "services_vs_tblhosting": $((services - whmcs_hosting)),
    "invoices_vs_tblinvoices": $((invoices - whmcs_invoices)),
    "orders_vs_tblorders": $((orders - whmcs_orders))
  }
}
JSON

# Keep 60 snapshots
find "$OUT_DIR" -type f -name 'migration-*.json' | sort | head -n -60 | xargs -r rm -f

echo "MIGRATION_SNAPSHOT_OK:$OUT"
