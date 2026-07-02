#!/bin/bash
set -euo pipefail

MODE="${1:---check-only}"
FORCE="${2:-}"

DB_SOCKET="/run/mysql/mysql.sock"
DB_NAME="root_whmcs"
DB_CREDS="/home/root/.my.cnf"
OPS_DIR="/home/root/gohostme/control-plane/ops"
ALERT_FILE="$OPS_DIR/OFFSITE_ALERT.txt"
NOTIFY_SCRIPT="$OPS_DIR/notify-alert.sh"
BACKUP_BASE="/home/root/backups/native-platform"
DRILL_LOG="$OPS_DIR/restore-drill-native.log"
SNAPSHOT_DIR="$BACKUP_BASE/migration-snapshots"
OFFSITE_SUCCESS_FILE="$OPS_DIR/OFFSITE_LAST_SUCCESS.txt"

sql() {
  mysql --defaults-file="$DB_CREDS" -S "$DB_SOCKET" "$DB_NAME" -N -e "$1"
}

notify() {
  local severity="$1"
  local message="$2"
  if [[ -x "$NOTIFY_SCRIPT" ]]; then
    "$NOTIFY_SCRIPT" "$severity" "native-cutover" "$message" >/dev/null 2>&1 || true
  fi
}

ratio_pct() {
  local native="$1"
  local legacy="$2"
  if [[ "$legacy" -le 0 ]]; then
    echo 100
    return
  fi
  awk -v n="$native" -v l="$legacy" 'BEGIN { p=(n/l)*100; if (p>100) p=100; if (p<0) p=0; printf "%d", p }'
}

mode_now() {
  sql "SELECT COALESCE((SELECT value FROM control_settings WHERE \`key\`='whmcs_cutover_mode' LIMIT 1),'hybrid');"
}

set_mode() {
  local new_mode="$1"
  sql "INSERT INTO control_settings (\`key\`, value, updated_at) VALUES ('whmcs_cutover_mode', '$new_mode', NOW()) ON DUPLICATE KEY UPDATE value=VALUES(value), updated_at=NOW();"
  sql "INSERT INTO billing_events (event_type, actor_type, actor_id, entity_type, entity_id, amount, currency, description, metadata, ip_address, created_at) VALUES ('cutover.mode_updated','ops',NULL,'webhook',0,NULL,'USD','Cutover mode set by native-cutover.sh', JSON_OBJECT('mode','$new_mode'), '127.0.0.1', NOW(3));"
}

freshness_hours() {
  local ts="$1"
  local now
  now=$(date +%s)
  if [[ "$ts" -le 0 ]]; then
    echo 99999
    return
  fi
  echo $(( (now - ts) / 3600 ))
}

is_external_offsite_detail() {
  local method="$1"
  local detail="$2"

  if [[ "$method" == "ftp" ]]; then
    return 0
  fi

  local host
  host="$detail"
  [[ "$host" == *"@"* ]] && host="${host##*@}"
  host="${host%%:*}"
  host=$(echo "$host" | tr '[:upper:]' '[:lower:]')
  [[ -n "$host" ]] || return 1

  if [[ "$host" == "localhost" || "$host" == "127.0.0.1" || "$host" == "::1" ]]; then
    return 1
  fi

  local self_short self_fqdn
  self_short=$(hostname 2>/dev/null | tr '[:upper:]' '[:lower:]' || true)
  self_fqdn=$(hostname -f 2>/dev/null | tr '[:upper:]' '[:lower:]' || true)
  if [[ "$host" == "$self_short" || "$host" == "$self_fqdn" ]]; then
    return 1
  fi

  for ip in $(hostname -I 2>/dev/null || true); do
    [[ "$host" == "$ip" ]] && return 1
  done

  if [[ "$host" =~ ^10\. || "$host" =~ ^127\. || "$host" =~ ^192\.168\. || "$host" =~ ^172\.(1[6-9]|2[0-9]|3[0-1])\. ]]; then
    return 1
  fi

  if [[ "$host" =~ ^(fc|fd|fe80:) ]]; then
    return 1
  fi

  return 0
}

precheck() {
  local blockers=0

  local latest_backup
  latest_backup=$(find "$BACKUP_BASE" -maxdepth 1 -mindepth 1 -type d -name '20*' | sort | tail -n 1)
  local backup_ts=0
  if [[ -n "$latest_backup" ]]; then
    backup_ts=$(stat -c %Y "$latest_backup" 2>/dev/null || echo 0)
  fi
  local backup_age
  backup_age=$(freshness_hours "$backup_ts")

  local drill_ts=0
  if [[ -f "$DRILL_LOG" ]]; then
    local last_line
    last_line=$(grep 'RESTORE_DRILL_OK:' "$DRILL_LOG" | tail -n 1 || true)
    if [[ -n "$last_line" ]]; then
      local stamp
      stamp=$(echo "$last_line" | sed -n 's/^\[\([^]]*\)\].*$/\1/p')
      if [[ -n "$stamp" ]]; then
        drill_ts=$(date -d "$stamp" +%s 2>/dev/null || echo 0)
      fi
    fi
  fi
  local drill_age
  drill_age=$(freshness_hours "$drill_ts")

  local latest_snapshot
  latest_snapshot=$(find "$SNAPSHOT_DIR" -maxdepth 1 -type f -name 'migration-*.json' 2>/dev/null | sort | tail -n 1 || true)
  local snap_ts=0
  if [[ -n "$latest_snapshot" ]]; then
    snap_ts=$(stat -c %Y "$latest_snapshot" 2>/dev/null || echo 0)
  fi
  local snap_age
  snap_age=$(freshness_hours "$snap_ts")

  local offsite_ts=0
  local offsite_method=""
  local offsite_detail=""
  local offsite_external=0
  if [[ -f "$OFFSITE_SUCCESS_FILE" ]]; then
    local offsite_line
    offsite_line=$(cat "$OFFSITE_SUCCESS_FILE" 2>/dev/null || true)
    local offsite_stamp
    offsite_stamp=$(echo "$offsite_line" | sed -n 's/.*ts=\([^ ]*\).*/\1/p')
    offsite_method=$(echo "$offsite_line" | sed -n 's/.*method=\([^ ]*\).*/\1/p')
    offsite_detail=$(echo "$offsite_line" | sed -n 's/.*detail=\(.*\)$/\1/p')
    if [[ -n "$offsite_stamp" ]]; then
      offsite_ts=$(date -d "$offsite_stamp" +%s 2>/dev/null || echo 0)
    fi
    if is_external_offsite_detail "$offsite_method" "$offsite_detail"; then
      offsite_external=1
    fi
  fi
  local offsite_age
  offsite_age=$(freshness_hours "$offsite_ts")

  local n_services n_invoices n_orders l_services l_invoices l_orders
  n_services=$(sql "SELECT COUNT(*) FROM services;")
  n_invoices=$(sql "SELECT COUNT(*) FROM invoices;")
  n_orders=$(sql "SELECT COUNT(*) FROM orders;")
  l_services=$(sql "SELECT COUNT(*) FROM tblhosting;")
  l_invoices=$(sql "SELECT COUNT(*) FROM tblinvoices;")
  l_orders=$(sql "SELECT COUNT(*) FROM tblorders;")

  local p1 p2 p3 avg
  p1=$(ratio_pct "$n_services" "$l_services")
  p2=$(ratio_pct "$n_invoices" "$l_invoices")
  p3=$(ratio_pct "$n_orders" "$l_orders")
  avg=$(( (p1 + p2 + p3) / 3 ))

  echo "PRECHECK backup_age_h=$backup_age drill_age_h=$drill_age snapshot_age_h=$snap_age offsite_age_h=$offsite_age offsite_external=$offsite_external parity_avg_pct=$avg"

  if [[ -f "$ALERT_FILE" ]]; then
    echo "BLOCKER offsite_alert_active"
    blockers=$((blockers + 1))
  fi
  if [[ "$backup_age" -gt 36 ]]; then
    echo "BLOCKER backup_too_old"
    blockers=$((blockers + 1))
  fi
  if [[ "$drill_age" -gt 192 ]]; then
    echo "BLOCKER restore_drill_too_old"
    blockers=$((blockers + 1))
  fi
  if [[ "$snap_age" -gt 24 ]]; then
    echo "BLOCKER migration_snapshot_too_old"
    blockers=$((blockers + 1))
  fi
  if [[ "$offsite_age" -gt 36 ]]; then
    echo "BLOCKER offsite_not_fresh"
    blockers=$((blockers + 1))
  fi
  if [[ "$offsite_external" -ne 1 ]]; then
    echo "BLOCKER offsite_not_external"
    blockers=$((blockers + 1))
  fi
  if [[ "$avg" -lt 85 ]]; then
    echo "BLOCKER migration_parity_below_85"
    blockers=$((blockers + 1))
  fi

  if [[ "$blockers" -gt 0 && "$FORCE" != "--force" ]]; then
    return 1
  fi
  return 0
}

current_mode="$(mode_now)"
echo "CURRENT_MODE:$current_mode"

if [[ "$MODE" == "--check-only" ]]; then
  if precheck; then
    echo "NATIVE_CUTOVER_PRECHECK_OK"
    exit 0
  fi
  echo "NATIVE_CUTOVER_PRECHECK_FAILED"
  exit 2
fi

if [[ "$MODE" == "--rollback" ]]; then
  set_mode "hybrid"
  echo "NATIVE_CUTOVER_ROLLBACK_OK"
  notify "warning" "rollback executed to hybrid"
  exit 0
fi

if [[ "$MODE" == "--apply" ]]; then
  if ! precheck; then
    echo "NATIVE_CUTOVER_BLOCKED"
    notify "critical" "cutover blocked by precheck"
    exit 3
  fi

  set_mode "native"
  new_mode="$(mode_now)"
  if [[ "$new_mode" != "native" ]]; then
    echo "NATIVE_CUTOVER_VERIFY_FAILED"
    notify "critical" "cutover apply failed verification"
    exit 4
  fi

  echo "NATIVE_CUTOVER_OK"
  notify "info" "cutover applied to native"
  exit 0
fi

echo "USAGE: $0 --check-only | --apply [--force] | --rollback"
exit 1
