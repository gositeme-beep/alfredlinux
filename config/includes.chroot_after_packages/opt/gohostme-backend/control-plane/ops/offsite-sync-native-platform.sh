#!/bin/bash
set -euo pipefail

SRC_BASE="/home/root/backups/native-platform"
LOG="/home/root/gohostme/control-plane/ops/offsite-native-sync.log"
EU_HOST="root@10.66.66.3"
EU_DIR="/home/backup/root-backups/native-platform"
SECONDARY_CFG="/home/root/.vault/native-offsite-secondary"
ALERT_FILE="/home/root/gohostme/control-plane/ops/OFFSITE_ALERT.txt"
SUCCESS_FILE="/home/root/gohostme/control-plane/ops/OFFSITE_LAST_SUCCESS.txt"
NOTIFY_SCRIPT="/home/root/gohostme/control-plane/ops/notify-alert.sh"

mkdir -p "$(dirname "$LOG")"

log() {
  echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" | tee -a "$LOG"
}

notify_alert() {
  local severity="$1"
  local message="$2"
  if [[ -x "$NOTIFY_SCRIPT" ]]; then
    "$NOTIFY_SCRIPT" "$severity" "offsite-sync" "$message" >/dev/null 2>&1 || true
  fi
}

set_alert() {
  local message="$1"
  echo "$(date '+%Y-%m-%d %H:%M:%S') OFFSITE FAILED: $message" > "$ALERT_FILE"
  notify_alert "critical" "$message"
}

clear_alert() {
  if [[ -f "$ALERT_FILE" ]]; then
    rm -f "$ALERT_FILE"
    notify_alert "info" "offsite path recovered"
  fi
}

mark_success() {
  local method="$1"
  local detail="$2"
  echo "ts=$(date -u '+%Y-%m-%dT%H:%M:%SZ') method=$method detail=$detail" > "$SUCCESS_FILE"
}

is_external_host() {
  local host_raw="$1"
  local host
  host=$(echo "$host_raw" | tr '[:upper:]' '[:lower:]')
  host="${host%%:*}"
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

load_secondary_target() {
  if [[ ! -f "$SECONDARY_CFG" ]]; then
    return 1
  fi
  local line
  line=$(tr -d '\r\n' < "$SECONDARY_CFG")
  [[ -n "$line" ]] || return 1
  SECONDARY_HOST="${line%%|*}"
  SECONDARY_DIR="${line#*|}"
  [[ -n "${SECONDARY_HOST:-}" && -n "${SECONDARY_DIR:-}" && "$SECONDARY_HOST" != "$line" ]] || return 1

  local secondary_host_only
  secondary_host_only="${SECONDARY_HOST##*@}"
  if ! is_external_host "$secondary_host_only"; then
    log "WARN: secondary target rejected (not external): $SECONDARY_HOST"
    return 1
  fi

  return 0
}

sync_to_mesh_target() {
  local target_host="$1"
  local target_dir="$2"
  local target_label="$3"

  if ! ssh -o ConnectTimeout=10 -o BatchMode=yes "$target_host" "mkdir -p '$target_dir' '$target_dir/migration-snapshots'"; then
    log "WARN: cannot prepare $target_label directory"
    return 1
  fi

  cd "$SRC_BASE"
  local latest_dirs
  latest_dirs=$(ls -1d 20* 2>/dev/null | sort | tail -n 14 || true)
  for d in $latest_dirs; do
    [[ -d "$d" ]] || continue
    if ! rsync -az --delete --timeout=120 "$SRC_BASE/$d/" "$target_host:$target_dir/$d/"; then
      log "WARN: rsync to $target_label failed for $d"
      return 1
    fi
    log "SYNCED:$target_label:$d"
  done

  if [[ -d "$SRC_BASE/migration-snapshots" ]]; then
    if ! rsync -az --delete --timeout=60 "$SRC_BASE/migration-snapshots/" "$target_host:$target_dir/migration-snapshots/"; then
      log "WARN: snapshot rsync failed for $target_label"
    fi
    log "SYNCED:$target_label:migration-snapshots"
  fi

  ssh -o BatchMode=yes "$target_host" "cd '$target_dir' && ls -1d 20* 2>/dev/null | sort | head -n -14 | xargs -r rm -rf" || true
  return 0
}

get_ftp_creds() {
  php -r '
    require "/home/root/public_html/includes/db-config.inc.php";
    require "/home/root/public_html/scripts/vault-crypto.php";
    $db = getSharedDB();
    $row = $db->query("SELECT * FROM commander_credentials WHERE credential_id = \"ovh-backup-ftp\"")->fetch(PDO::FETCH_ASSOC);
    if (!$row) { echo "ERROR"; exit(1); }
    $dec = vault_decrypt_row($row, ["username","password","service_url"]);
    $url = trim((string) ($dec["service_url"] ?? ""));
    if ($url === "") {
      echo "ERROR";
      exit(1);
    }
    echo $dec["username"] . "\n" . $dec["password"] . "\n" . $url;
  ' 2>/dev/null
}

upload_latest_to_ftp() {
  local latest
  latest=$(find "$SRC_BASE" -maxdepth 1 -mindepth 1 -type d -name '20*' | sort | tail -n 1)
  [[ -n "$latest" ]] || { log "ERROR: no dated native backup for FTP upload"; return 1; }

  local creds ftp_user ftp_pass ftp_url
  creds=$(get_ftp_creds) || { log "ERROR: unable to fetch FTP credentials"; return 1; }
  ftp_user=$(echo "$creds" | sed -n '1p')
  ftp_pass=$(echo "$creds" | sed -n '2p')
  ftp_url=$(echo "$creds" | sed -n '3p')

  [[ -n "$ftp_user" && -n "$ftp_pass" && -n "$ftp_url" ]] || { log "ERROR: incomplete FTP creds"; return 1; }
  if [[ "$ftp_url" != *"://"* ]]; then
    ftp_url="ftp://$ftp_url"
  fi
  local ftp_base
  ftp_base="${ftp_url%/}"

  local netrc
  netrc=$(mktemp /tmp/.netrc-native-offsite-XXXXXX)
  chmod 600 "$netrc"
  local ftp_host
  ftp_host=$(echo "$ftp_base" | sed -E 's#^[a-zA-Z]+://([^/:]+).*$#\1#')
  printf 'machine %s login %s password %s\n' "$ftp_host" "$ftp_user" "$ftp_pass" > "$netrc"

  curl -4 -k --ftp-create-dirs --netrc-file "$netrc" --connect-timeout 30 \
    -Q "MKD /backups/native-platform" "$ftp_base/" >/dev/null 2>&1 || true

  for f in "$latest"/*.gz "$latest"/manifest.txt "$latest"/SHA256SUMS.txt; do
    [[ -f "$f" ]] || continue
    log "FTP_UPLOAD:$(basename "$f")"
    if ! curl -4 -k --netrc-file "$netrc" --connect-timeout 30 --retry 3 --retry-delay 10 \
      -T "$f" "$ftp_base/backups/native-platform/" >/dev/null; then
      rm -f "$netrc"
      log "ERROR: FTP upload failed for $(basename "$f")"
      return 1
    fi
  done

  rm -f "$netrc"
  mark_success "ftp" "$ftp_base"
  log "FTP_FALLBACK_SYNC_OK:$(basename "$latest")"
}

if [[ ! -d "$SRC_BASE" ]]; then
  log "ERROR: source backup directory missing: $SRC_BASE"
  exit 1
fi

if sync_to_mesh_target "$EU_HOST" "$EU_DIR" "mesh-primary"; then
  mark_success "mesh-primary" "$EU_HOST:$EU_DIR"
  clear_alert
  log "OFFSITE_NATIVE_SYNC_OK:mesh-primary"
  exit 0
fi

if load_secondary_target; then
  log "WARN: trying secondary offsite target"
  if sync_to_mesh_target "$SECONDARY_HOST" "$SECONDARY_DIR" "mesh-secondary"; then
    mark_success "mesh-secondary" "$SECONDARY_HOST:$SECONDARY_DIR"
    clear_alert
    log "OFFSITE_NATIVE_SYNC_OK:mesh-secondary"
    exit 0
  fi
  log "WARN: secondary offsite target failed"
fi

log "WARN: mesh targets unavailable, switching to FTP fallback"
if upload_latest_to_ftp; then
  clear_alert
  exit 0
fi

set_alert "all offsite paths failed (mesh-primary/mesh-secondary/ftp)"
exit 0
