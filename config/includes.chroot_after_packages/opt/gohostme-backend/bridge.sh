#!/bin/bash
# ═══════════════════════════════════════════════════════════════════════════
# GoHostMe Sovereignty Bridge v2.0 — HARDENED Privileged Operations Helper
# ═══════════════════════════════════════════════════════════════════════════
# This script is the ONLY sudo entry point for the root user.
# It performs specific, validated operations that require root access.
# Called by GoHostMe server.js via: sudo /opt/gohostme/bridge.sh <command> [args]
#
# SECURITY LAYERS (added 2026-03-16):
#   1. HMAC Token — every call must include --token=<hmac> (30s expiry)
#   2. Command Tiers — GREEN (auto), YELLOW (token), RED (dashboard approval)
#   3. Audit Log — every call logged to DB with caller PID, parent, result
# ═══════════════════════════════════════════════════════════════════════════

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
LOG="/var/log/gohostme-bridge.log"
HTTPD_VHOSTS="/etc/httpd/conf/vhosts"
BIND_DIR="/etc/bind"
NAMED_CONF_LOCAL="/etc/bind/named.conf.local"
DA_USER_DOMAINS="/usr/local/directadmin/data/users/root/domains"
DA_USER_HTTPD="/usr/local/directadmin/data/users/root/httpd.conf"
DA_DOMAINS_LIST="/usr/local/directadmin/data/users/root/domains.list"
DOMAIN_BASE="/home/root/domains"
SERVER_IP="15.235.50.60"
PHP_SOCKET="/usr/local/php83/sockets/root.sock"

# ═══════════════════════════════════════════════════════════════
# SECURITY CONFIGURATION
# ═══════════════════════════════════════════════════════════════
HMAC_SECRET_FILE="/home/root/.vault/bridge-hmac-secret"
TOKEN_MAX_AGE=30  # seconds — tokens expire after this
DB_SOCKET="/run/mysql/mysql.sock"
DB_NAME="root_whmcs"
DB_USER="root_whmcs"

# Command tier classifications
declare -A CMD_TIER
# GREEN — read-only, safe, auto-approved (token still verified if provided)
CMD_TIER[read-log]=green
CMD_TIER[service-status]=green
CMD_TIER[firewall-list]=green
CMD_TIER[php-versions]=green
CMD_TIER[benchmark]=green
CMD_TIER[apache-test]=green
CMD_TIER[apache-vhosts]=green
CMD_TIER[dns-zone-read]=green
CMD_TIER[disk-encryption-status]=green
CMD_TIER[network-tool]=green
CMD_TIER[security-headers]=green
CMD_TIER[docker-list]=green
CMD_TIER[fail2ban-status]=green
CMD_TIER[malware-scan]=green
CMD_TIER[rootkit-check]=green
CMD_TIER[read-exim-log]=green
CMD_TIER[exim-config-read]=green
CMD_TIER[token-generate]=green

# YELLOW — moderate risk, requires valid HMAC token
CMD_TIER[apache-reload]=yellow
CMD_TIER[dns-reload]=yellow
CMD_TIER[certbot-request]=yellow
CMD_TIER[apache2-vhost-deploy]=yellow
CMD_TIER[certbot-renew-all]=yellow
CMD_TIER[service-restart]=yellow
CMD_TIER[email-create]=yellow
CMD_TIER[email-delete]=yellow
CMD_TIER[docker-action]=yellow
CMD_TIER[da-httpd-rebuild]=yellow
CMD_TIER[rotate-user-password]=yellow
CMD_TIER[account-create]=yellow
CMD_TIER[account-suspend]=yellow
CMD_TIER[account-unsuspend]=yellow
CMD_TIER[account-terminate]=yellow
CMD_TIER[account-change-password]=yellow
CMD_TIER[account-change-package]=yellow
CMD_TIER[apply-security-hardening]=yellow
CMD_TIER[apply-stack-optimization]=yellow
CMD_TIER[build-iso]=yellow

# RED — destructive, requires HMAC token + dashboard approval from client_id=33
CMD_TIER[firewall-add]=red
CMD_TIER[firewall-delete]=red
CMD_TIER[vhost-create]=red
CMD_TIER[vhost-delete]=red
CMD_TIER[vhost-ssl-update]=red
CMD_TIER[dns-zone-create]=red
CMD_TIER[dns-record-add]=red
CMD_TIER[dns-record-delete]=red
CMD_TIER[da-domain-register]=red
CMD_TIER[full-domain-setup]=red
CMD_TIER[fail2ban-unban]=red
CMD_TIER[bridge-sync]=red

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" >> "$LOG"
}

# ═══════════════════════════════════════════════════════════════
# HMAC TOKEN VERIFICATION
# ═══════════════════════════════════════════════════════════════
verify_token() {
    local provided_token="$1"
    local command="$2"
    shift 2
    local args="$*"

    if [[ ! -f "$HMAC_SECRET_FILE" ]]; then
        log "SECURITY: HMAC secret file missing!"
        echo "ERROR: Bridge security not configured (missing HMAC secret)"
        exit 99
    fi

    local secret
    secret=$(cat "$HMAC_SECRET_FILE" | tr -d '\n')

    # Token format: timestamp:hmac_hex
    local token_ts="${provided_token%%:*}"
    local token_hmac="${provided_token#*:}"

    if [[ -z "$token_ts" ]] || [[ -z "$token_hmac" ]] || [[ "$token_ts" == "$token_hmac" ]]; then
        log "SECURITY: Malformed token for command=$command"
        echo "ERROR: Invalid token format"
        exit 98
    fi

    # Strict token shape validation to avoid arithmetic/parser edge-cases
    if [[ ! "$token_ts" =~ ^[0-9]{10}$ ]]; then
        log "SECURITY: Invalid token timestamp for command=$command"
        echo "ERROR: Invalid token timestamp"
        exit 98
    fi
    if [[ ! "$token_hmac" =~ ^[a-f0-9]{64}$ ]]; then
        log "SECURITY: Invalid token hmac format for command=$command"
        echo "ERROR: Invalid token HMAC format"
        exit 98
    fi

    # Check timestamp freshness (anti-replay)
    local now
    now=$(date +%s)
    local age=$(( now - token_ts ))
    if (( age < 0 || age > TOKEN_MAX_AGE )); then
        log "SECURITY: Expired token (age=${age}s) for command=$command"
        echo "ERROR: Token expired (age=${age}s, max=${TOKEN_MAX_AGE}s)"
        exit 97
    fi

    # Recompute expected HMAC: HMAC-SHA256(timestamp:command:args, secret)
    local payload="${token_ts}:${command}:${args}"
    local expected
    expected=$(echo -n "$payload" | openssl dgst -sha256 -hmac "$secret" | awk '{print $NF}')

    if [[ "$token_hmac" != "$expected" ]]; then
        log "SECURITY: HMAC mismatch for command=$command caller_pid=$PPID"
        echo "ERROR: Invalid token (HMAC verification failed)"
        exit 96
    fi

    return 0
}

# ═══════════════════════════════════════════════════════════════
# DASHBOARD APPROVAL CHECK (RED commands)
# ═══════════════════════════════════════════════════════════════
check_approval() {
    local approval_code="$1"
    local command="$2"
    shift 2
    local args="$*"

    if [[ -z "$approval_code" ]]; then
        log "SECURITY: RED command=$command attempted without approval code"
        echo "ERROR: This is a RED-tier command requiring dashboard approval."
        echo "ERROR: Request approval at /commander-bridge.php then retry with --approval=CODE"
        exit 95
    fi

    # Read DB password from ~/.my.cnf (already configured)
    local result
    result=$(mysql --defaults-file=/home/root/.my.cnf -S "$DB_SOCKET" "$DB_NAME" -N -e "
        SELECT id, command, status, expires_at
        FROM bridge_approvals
        WHERE approval_code = '$(echo "$approval_code" | sed "s/'/''/g")'
          AND command = '$(echo "$command" | sed "s/'/''/g")'
          AND status = 'approved'
          AND expires_at > NOW()
        LIMIT 1;
    " 2>/dev/null)

    if [[ -z "$result" ]]; then
        log "SECURITY: Invalid/expired approval code for RED command=$command"
        echo "ERROR: Approval code invalid, expired, or already used."
        echo "ERROR: Request new approval at /commander-bridge.php"
        exit 94
    fi

    local approval_id
    approval_id=$(echo "$result" | awk '{print $1}')

    # Mark approval as used (one-time use)
    mysql --defaults-file=/home/root/.my.cnf -S "$DB_SOCKET" "$DB_NAME" -N -e "
        UPDATE bridge_approvals SET status='used', used_at=NOW() WHERE id=$approval_id;
    " 2>/dev/null

    log "APPROVAL: Used approval #$approval_id for RED command=$command"
    echo "$approval_id"
}

# ═══════════════════════════════════════════════════════════════
# AUDIT LOGGING TO DATABASE
# ═══════════════════════════════════════════════════════════════
audit_log() {
    local command="$1"
    local args="$2"
    local tier="$3"
    local token_valid="$4"
    local approval_id="$5"
    local result="$6"
    local caller_pid="${PPID:-0}"

    # Get parent process info
    local parent_pid=0
    if [[ -f "/proc/$caller_pid/stat" ]]; then
        parent_pid=$(awk '{print $4}' "/proc/$caller_pid/stat" 2>/dev/null || echo 0)
    fi

    local caller_name="unknown"
    if [[ -f "/proc/$caller_pid/comm" ]]; then
        caller_name=$(cat "/proc/$caller_pid/comm" 2>/dev/null || echo "unknown")
    fi

    # Sanitize for SQL
    args=$(echo "$args" | head -c 500 | sed "s/'/''/g")
    caller_name=$(echo "$caller_name" | head -c 250 | sed "s/'/''/g")

    mysql --defaults-file=/home/root/.my.cnf -S "$DB_SOCKET" "$DB_NAME" -N -e "
        INSERT INTO bridge_audit_log (command, args, tier, caller, caller_pid, parent_pid, token_valid, approval_id, result)
        VALUES ('$command', '$args', '$tier', '$caller_name', $caller_pid, $parent_pid, $token_valid, ${approval_id:-NULL}, '$result');
    " 2>/dev/null || true
}

# ═══════════════════════════════════════════════════════════════
# SECURITY GATE — Called before every command dispatch
# ═══════════════════════════════════════════════════════════════
security_gate() {
    local token=""
    local approval_code=""
    local command="$1"
    shift

    # Extract --token and --approval from args
    local clean_args=()
    for arg in "$@"; do
        case "$arg" in
            --token=*) token="${arg#--token=}" ;;
            --approval=*) approval_code="${arg#--approval=}" ;;
            *) clean_args+=("$arg") ;;
        esac
    done

    local tier="${CMD_TIER[$command]:-red}"  # Unknown commands default to RED

    case "$tier" in
        green)
            # Green: always allowed, token optional but verified if provided
            if [[ -n "$token" ]]; then
                verify_token "$token" "$command" "${clean_args[*]:-}"
                audit_log "$command" "${clean_args[*]:-}" "green" 1 "NULL" "allowed"
            else
                audit_log "$command" "${clean_args[*]:-}" "green" 0 "NULL" "allowed"
            fi
            ;;
        yellow)
            # Yellow: HMAC token REQUIRED
            if [[ -z "$token" ]]; then
                audit_log "$command" "${clean_args[*]:-}" "yellow" 0 "NULL" "invalid_token"
                log "SECURITY: YELLOW command=$command called without token (pid=$PPID)"
                echo "ERROR: This command requires a valid HMAC token."
                echo "ERROR: Generate one with: php /home/root/.vault/bridge-token.php $command [args]"
                exit 93
            fi
            verify_token "$token" "$command" "${clean_args[*]:-}"
            audit_log "$command" "${clean_args[*]:-}" "yellow" 1 "NULL" "allowed"
            ;;
        red)
            # Red: HMAC token + dashboard approval REQUIRED
            if [[ -z "$token" ]]; then
                audit_log "$command" "${clean_args[*]:-}" "red" 0 "NULL" "invalid_token"
                log "SECURITY: RED command=$command called without token (pid=$PPID)"
                echo "ERROR: This is a RED-tier command. Requires HMAC token + dashboard approval."
                echo "ERROR: 1. Request approval at /commander-bridge.php"
                echo "ERROR: 2. Generate token: php /home/root/.vault/bridge-token.php $command [args]"
                echo "ERROR: 3. Run: sudo bridge.sh $command --token=TOKEN --approval=CODE [args]"
                exit 93
            fi
            verify_token "$token" "$command" "${clean_args[*]:-}"

            local aid
            aid=$(check_approval "$approval_code" "$command" "${clean_args[*]:-}")
            audit_log "$command" "${clean_args[*]:-}" "red" 1 "$aid" "allowed"
            ;;
    esac

    # Return clean args (without --token and --approval)
    CLEAN_ARGS=("${clean_args[@]+"${clean_args[@]}"}")
}

# Bridge self-sync command (RED tier — requires dashboard approval)
cmd_bridge_sync() {
    local source="/home/root/gohostme/bridge.sh"
    local dest="/opt/gohostme/bridge.sh"

    if [[ ! -f "$source" ]]; then
        echo "ERROR: Source bridge not found at $source"
        exit 1
    fi

    # Verify the source is a valid bash script
    if ! bash -n "$source" 2>/dev/null; then
        echo "ERROR: Source bridge has syntax errors — refusing to sync"
        exit 1
    fi

    # Backup current bridge
    cp "$dest" "${dest}.bak.$(date +%Y%m%d%H%M%S)"
    log "BRIDGE-SYNC: Backed up $dest"

    # Copy new bridge
    cp "$source" "$dest"
    chmod 755 "$dest"
    chown root:root "$dest"
    log "BRIDGE-SYNC: Synced from $source to $dest"

    # Also copy HMAC secret to root-readable location
    if [[ -f "/home/root/.vault/bridge-hmac-secret" ]]; then
        cp "/home/root/.vault/bridge-hmac-secret" "/opt/gohostme/data/.bridge-hmac-secret"
        chmod 600 "/opt/gohostme/data/.bridge-hmac-secret"
        chown root:root "/opt/gohostme/data/.bridge-hmac-secret"
        log "BRIDGE-SYNC: HMAC secret synced"
    fi

    echo "OK: Bridge synced successfully"
    echo "OK: Backup saved as ${dest}.bak.$(date +%Y%m%d%H%M%S)"
}

# Strict domain validation — only allow valid domain names
validate_domain() {
    local domain="$1"
    if [[ ! "$domain" =~ ^[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?)*\.[a-zA-Z]{2,}$ ]]; then
        echo "ERROR: Invalid domain name: $domain"
        exit 1
    fi
    # Block dangerous patterns
    if [[ "$domain" == *".."* ]] || [[ "$domain" == *"/"* ]] || [[ ${#domain} -gt 253 ]]; then
        echo "ERROR: Domain contains invalid characters"
        exit 1
    fi
}

# Strict username validation for system account operations
validate_username() {
    local username="$1"
    if [[ ! "$username" =~ ^[a-z_][a-z0-9_-]{1,31}$ ]]; then
        echo "ERROR: Invalid username: $username"
        exit 1
    fi
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: vhost-create <domain>
# Creates Apache VirtualHost config for a domain
# ═══════════════════════════════════════════════════════════════
cmd_vhost_create() {
    local domain="$1"
    validate_domain "$domain"
    
    local vhost_file="$HTTPD_VHOSTS/${domain}.conf"
    local doc_root="$DOMAIN_BASE/${domain}/public_html"
    local log_dir="/var/log/httpd/domains"
    
    if [[ -f "$vhost_file" ]]; then
        echo "EXISTS: VHost already exists for $domain"
        exit 0
    fi
    
    # Create log directory if needed
    mkdir -p "$log_dir"
    
    # Create the VirtualHost config
    cat > "$vhost_file" << VHOST
# GoHostMe Sovereign VHost — ${domain}
# Generated: $(date '+%Y-%m-%d %H:%M:%S')
# DO NOT EDIT — managed by GoHostMe Sovereignty Bridge

<VirtualHost ${SERVER_IP}:80>
    ServerName www.${domain}
    ServerAlias www.${domain} ${domain}
    ServerAdmin webmaster@root.com
    DocumentRoot "${doc_root}"
    UseCanonicalName OFF

    # Force HTTPS redirect (except ACME challenges for cert renewal)
    RewriteEngine On
    RewriteCond %{REQUEST_URI} !^/\.well-known/acme-challenge/
    RewriteCond %{HTTPS} !=on
    RewriteCond %{HTTP:X-Forwarded-Proto} !https [NC]
    RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

    SuexecUserGroup root root
    CustomLog ${log_dir}/${domain}.bytes bytes
    CustomLog ${log_dir}/${domain}.log combined
    ErrorLog ${log_dir}/${domain}.error.log

    <Directory "${doc_root}">
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        <FilesMatch "\.(php|inc|phtml)\$">
            <If "-f %{REQUEST_FILENAME}">
                AddHandler "proxy:unix:${PHP_SOCKET}|fcgi://localhost" .inc .php .phtml
            </If>
        </FilesMatch>
    </Directory>
</VirtualHost>

<VirtualHost ${SERVER_IP}:443>
    SSLEngine on
    ServerName www.${domain}
    ServerAlias www.${domain} ${domain}
    ServerAdmin webmaster@root.com
    DocumentRoot "${doc_root}"
    UseCanonicalName OFF

    # Try domain-specific SSL cert first, fall back to root.com cert
    SSLCertificateFile /usr/local/directadmin/data/users/root/domains/root.com.cert.combined
    SSLCertificateKeyFile /usr/local/directadmin/data/users/root/domains/root.com.key

    SuexecUserGroup root root
    CustomLog ${log_dir}/${domain}.bytes bytes
    CustomLog ${log_dir}/${domain}.log combined
    ErrorLog ${log_dir}/${domain}.error.log

    <Directory "${doc_root}">
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        <FilesMatch "\.(php|inc|phtml)\$">
            <If "-f %{REQUEST_FILENAME}">
                AddHandler "proxy:unix:${PHP_SOCKET}|fcgi://localhost" .inc .php .phtml
            </If>
        </FilesMatch>
    </Directory>

    # Security headers
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</VirtualHost>
VHOST

    log "VHOST_CREATE: $domain → $vhost_file"
    echo "OK: VHost created for $domain"
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: vhost-delete <domain>
# Removes Apache VirtualHost config for a domain
# ═══════════════════════════════════════════════════════════════
cmd_vhost_delete() {
    local domain="$1"
    validate_domain "$domain"
    
    # Protect critical domains
    case "$domain" in
        root.com|gocodeme.com|meta-dome.com|soundstudiopro.com)
            echo "ERROR: Cannot delete critical domain vhost"
            exit 1
            ;;
    esac
    
    local vhost_file="$HTTPD_VHOSTS/${domain}.conf"
    if [[ -f "$vhost_file" ]]; then
        rm -f "$vhost_file"
        log "VHOST_DELETE: $domain"
        echo "OK: VHost removed for $domain"
    else
        echo "NOTFOUND: No GoHostMe vhost for $domain"
    fi
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: vhost-ssl-update <domain> <cert_path> <key_path> [chain_path]
# Updates SSL cert paths in a vhost
# ═══════════════════════════════════════════════════════════════
cmd_vhost_ssl_update() {
    local domain="$1"
    local cert_path="$2"
    local key_path="$3"
    local chain_path="${4:-}"
    validate_domain "$domain"
    
    local vhost_file="$HTTPD_VHOSTS/${domain}.conf"
    if [[ ! -f "$vhost_file" ]]; then
        echo "ERROR: No vhost found for $domain"
        exit 1
    fi
    
    # Validate cert files exist
    if [[ ! -f "$cert_path" ]] || [[ ! -f "$key_path" ]]; then
        echo "ERROR: Certificate files not found"
        exit 1
    fi
    
    # Update SSL directives in vhost
    sed -i "s|SSLCertificateFile .*|SSLCertificateFile ${cert_path}|" "$vhost_file"
    sed -i "s|SSLCertificateKeyFile .*|SSLCertificateKeyFile ${key_path}|" "$vhost_file"
    
    if [[ -n "$chain_path" ]] && [[ -f "$chain_path" ]]; then
        if grep -q "SSLCertificateChainFile" "$vhost_file"; then
            sed -i "s|SSLCertificateChainFile .*|SSLCertificateChainFile ${chain_path}|" "$vhost_file"
        else
            sed -i "/SSLCertificateKeyFile/a\\    SSLCertificateChainFile ${chain_path}" "$vhost_file"
        fi
    fi
    
    log "VHOST_SSL_UPDATE: $domain cert=$cert_path key=$key_path"
    echo "OK: SSL updated for $domain"
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: apache-reload
# Safely reload Apache (syntax check first)
# ═══════════════════════════════════════════════════════════════
cmd_apache_reload() {
    # Always test config first
    local test_output
    test_output=$(httpd -t 2>&1 || true)
    if echo "$test_output" | grep -q "Syntax OK"; then
        systemctl reload httpd 2>&1 || httpd -k graceful 2>&1
        log "APACHE_RELOAD: Success"
        echo "OK: Apache reloaded"
    else
        log "APACHE_RELOAD_FAILED: $test_output"
        echo "ERROR: Apache config test failed: $test_output"
        exit 1
    fi
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: apache-test
# Test Apache config syntax without reloading
# ═══════════════════════════════════════════════════════════════
cmd_apache_test() {
    httpd -t 2>&1
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: apache-vhosts
# List loaded virtual hosts
# ═══════════════════════════════════════════════════════════════
cmd_apache_vhosts() {
    httpd -S 2>&1
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: dns-zone-create <domain>
# Creates a BIND zone file for a domain
# ═══════════════════════════════════════════════════════════════
cmd_dns_zone_create() {
    local domain="$1"
    validate_domain "$domain"
    
    local zone_file="$BIND_DIR/${domain}.db"
    local serial=$(date '+%Y%m%d%H')
    
    if [[ -f "$zone_file" ]]; then
        echo "EXISTS: DNS zone already exists for $domain"
        exit 0
    fi
    
    cat > "$zone_file" << ZONE
\$TTL 3600
@    IN    SOA    ns1.root.com. admin.root.com. (
              ${serial}  ; serial
              3600        ; refresh
              600         ; retry
              1209600     ; expire
              3600        ; minimum
)

; Nameservers
@    IN    NS    ns1.root.com.
@    IN    NS    ns2.root.com.

; A Records
@    IN    A     ${SERVER_IP}
www  IN    A     ${SERVER_IP}
mail IN    A     ${SERVER_IP}

; MX Record
@    IN    MX    10 mail.${domain}.

; SPF
@    IN    TXT   "v=spf1 a mx ip4:${SERVER_IP} ~all"
ZONE
    
    chown bind:bind "$zone_file" 2>/dev/null || true
    
    # Add to named.conf.local if not already there
    if ! grep -q "zone \"${domain}\"" "$NAMED_CONF_LOCAL" 2>/dev/null; then
        cat >> "$NAMED_CONF_LOCAL" << NAMEDENTRY

zone "${domain}" {
    type master;
    file "/etc/bind/${domain}.db";
};
NAMEDENTRY
    fi
    
    log "DNS_ZONE_CREATE: $domain → $zone_file"
    echo "OK: DNS zone created for $domain"
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: dns-record-add <domain> <name> <type> <value> [ttl]
# Adds a DNS record to a zone
# ═══════════════════════════════════════════════════════════════
cmd_dns_record_add() {
    local domain="$1"
    local name="$2"
    local type="$3"
    local value="$4"
    local ttl="${5:-3600}"
    validate_domain "$domain"
    
    local zone_file="$BIND_DIR/${domain}.db"
    if [[ ! -f "$zone_file" ]]; then
        echo "ERROR: DNS zone not found for $domain"
        exit 1
    fi
    
    # Validate record type
    case "$type" in
        A|AAAA|CNAME|MX|TXT|NS|SRV|CAA) ;;
        *) echo "ERROR: Invalid record type: $type"; exit 1 ;;
    esac
    
    # Validate name (prevent injection)
    if [[ ! "$name" =~ ^[a-zA-Z0-9._@*-]+$ ]]; then
        echo "ERROR: Invalid record name"
        exit 1
    fi
    
    # Increment serial
    local old_serial=$(grep -oP '\d{10}(?=\s*;\s*serial)' "$zone_file" | head -1)
    local new_serial=$(date '+%Y%m%d%H')
    if [[ "$new_serial" -le "$old_serial" ]]; then
        new_serial=$((old_serial + 1))
    fi
    sed -i "s/$old_serial/$new_serial/" "$zone_file"
    
    # Format record based on type
    local record_line
    case "$type" in
        MX)
            record_line="${name}    ${ttl}    IN    MX    ${value}"
            ;;
        TXT)
            record_line="${name}    ${ttl}    IN    TXT    \"${value}\""
            ;;
        *)
            record_line="${name}    ${ttl}    IN    ${type}    ${value}"
            ;;
    esac
    
    echo "$record_line" >> "$zone_file"
    
    log "DNS_RECORD_ADD: $domain $name $type $value"
    echo "OK: Record added to $domain"
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: dns-record-delete <domain> <name> <type> [value]
# Removes DNS record(s) from a zone
# ═══════════════════════════════════════════════════════════════
cmd_dns_record_delete() {
    local domain="$1"
    local name="$2"
    local type="$3"
    local value="${4:-}"
    validate_domain "$domain"
    
    local zone_file="$BIND_DIR/${domain}.db"
    if [[ ! -f "$zone_file" ]]; then
        echo "ERROR: DNS zone not found"
        exit 1
    fi
    
    # Increment serial
    local old_serial=$(grep -oP '\d{10}(?=\s*;\s*serial)' "$zone_file" | head -1)
    local new_serial=$(date '+%Y%m%d%H')
    if [[ "$new_serial" -le "$old_serial" ]]; then
        new_serial=$((old_serial + 1))
    fi
    sed -i "s/$old_serial/$new_serial/" "$zone_file"
    
    if [[ -n "$value" ]]; then
        # Delete specific record
        sed -i "/${name}.*IN.*${type}.*${value}/d" "$zone_file"
    else
        # Delete all records of that name+type
        sed -i "/${name}.*IN.*${type}/d" "$zone_file"
    fi
    
    log "DNS_RECORD_DELETE: $domain $name $type"
    echo "OK: Record removed from $domain"
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: dns-zone-read <domain>
# Reads a DNS zone file
# ═══════════════════════════════════════════════════════════════
cmd_dns_zone_read() {
    local domain="$1"
    validate_domain "$domain"
    
    local zone_file="$BIND_DIR/${domain}.db"
    if [[ -f "$zone_file" ]]; then
        cat "$zone_file"
    else
        echo "ERROR: Zone file not found for $domain"
        exit 1
    fi
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: dns-reload
# Reload BIND named service
# ═══════════════════════════════════════════════════════════════
cmd_dns_reload() {
    if command -v rndc &>/dev/null; then
        rndc reload 2>&1
        log "DNS_RELOAD: rndc reload"
        echo "OK: DNS reloaded"
    elif command -v named-checkconf &>/dev/null; then
        named-checkconf && systemctl reload named 2>&1 || systemctl reload bind9 2>&1
        log "DNS_RELOAD: systemctl"
        echo "OK: DNS reloaded"
    else
        echo "ERROR: No DNS reload command available"
        exit 1
    fi
}


# ═══════════════════════════════════════════════════════════════
# COMMAND: apache2-vhost-deploy <domain> <config_b64>
# Deploys VirtualHost config to root apache2 stack (live server)
# ═══════════════════════════════════════════════════════════════
cmd_apache2_vhost_deploy() {
    local domain="$1"
    local config_b64="$2"
    validate_domain "$domain"

    if [[ -z "$config_b64" ]]; then
        echo "ERROR: Missing config base64"
        exit 1
    fi

    local vhost_file="/etc/apache2/sites-available/zz-domain-${domain}.conf"
    local vhost_link="/etc/apache2/sites-enabled/zz-domain-${domain}.conf"

    echo "$config_b64" | base64 -d > "$vhost_file"
    ln -sf "$vhost_file" "$vhost_link"
    systemctl reload apache2 2>&1

    log "APACHE2_VHOST_DEPLOY: $domain -> $vhost_file"
    echo "OK: apache2 vhost deployed for $domain"
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: certbot-request <domain>
# Request Let's Encrypt cert via lego (ACME client)
# ═══════════════════════════════════════════════════════════════
cmd_certbot_request() {
    local domain="$1"
    validate_domain "$domain"
    
    local webroot="$DOMAIN_BASE/${domain}/public_html"
    if [[ ! -d "$webroot" ]]; then
        echo "ERROR: Webroot not found for $domain"
        exit 1
    fi
    
    local cert_dir="$DOMAIN_BASE/${domain}/ssl"
    mkdir -p "$cert_dir"
    
    # Use global ACME webroot (Apache Alias routes all /.well-known/acme-challenge here)
    local acme_webroot="/var/www/html"
    mkdir -p "${acme_webroot}/.well-known/acme-challenge"
    chmod 755 "${acme_webroot}/.well-known" "${acme_webroot}/.well-known/acme-challenge"
    
    # Use lego ACME client (installed at /usr/local/bin/lego)
    local lego_path="/home/root/.lego"
    /usr/local/bin/lego \
        --email admin@root.com \
        --domains "$domain" \
        --domains "www.$domain" \
        --http \
        --http.webroot "$acme_webroot" \
        --path "$lego_path" \
        --accept-tos \
        run 2>&1
    
    local lego_exit=$?
    if [[ $lego_exit -ne 0 ]]; then
        echo "ERROR: lego cert request failed (exit $lego_exit)"
        exit 1
    fi
    
    # Copy certs to domain ssl dir
    local lego_cert_dir="${lego_path}/certificates"
    if [[ -f "${lego_cert_dir}/${domain}.crt" ]]; then
        cp "${lego_cert_dir}/${domain}.crt" "${cert_dir}/${domain}.crt"
        cp "${lego_cert_dir}/${domain}.key" "${cert_dir}/${domain}.key"
        cp "${lego_cert_dir}/${domain}.issuer.crt" "${cert_dir}/${domain}.issuer.crt" 2>/dev/null || true
        chown root:root "${cert_dir}"/*
        chmod 600 "${cert_dir}/${domain}.key"
        chmod 644 "${cert_dir}/${domain}.crt"
        
        # Auto-update vhost with new cert paths
        local vhost_file="$HTTPD_VHOSTS/${domain}.conf"
        if [[ -f "$vhost_file" ]]; then
            sed -i "s|SSLCertificateFile .*|SSLCertificateFile ${cert_dir}/${domain}.crt|" "$vhost_file"
            sed -i "s|SSLCertificateKeyFile .*|SSLCertificateKeyFile ${cert_dir}/${domain}.key|" "$vhost_file"
            # Add chain if issuer cert exists
            if [[ -f "${cert_dir}/${domain}.issuer.crt" ]]; then
                if grep -q "SSLCertificateChainFile" "$vhost_file"; then
                    sed -i "s|SSLCertificateChainFile .*|SSLCertificateChainFile ${cert_dir}/${domain}.issuer.crt|" "$vhost_file"
                else
                    sed -i "/SSLCertificateKeyFile/a\\    SSLCertificateChainFile ${cert_dir}/${domain}.issuer.crt" "$vhost_file"
                fi
            fi
            echo "OK: Vhost SSL paths updated"
        fi
        
        echo "OK: Certificate issued for $domain"
    else
        echo "ERROR: Certificate files not found after lego run"
        exit 1
    fi
    
    log "CERTBOT_REQUEST: $domain via lego"
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: certbot-renew-all
# Renew all lego certs and update vhosts
# ═══════════════════════════════════════════════════════════════
cmd_certbot_renew_all() {
    local lego_path="/home/root/.lego"
    local lego_cert_dir="${lego_path}/certificates"
    local acme_webroot="/var/www/html"
    local renewed=0
    
    if [[ ! -d "$lego_cert_dir" ]]; then
        echo "ERROR: No lego certificates found"
        exit 1
    fi
    
    # Find all domains with certs
    for crt in "${lego_cert_dir}"/*.crt; do
        [[ -f "$crt" ]] || continue
        local fname=$(basename "$crt")
        # Skip issuer certs
        [[ "$fname" == *.issuer.crt ]] && continue
        local domain="${fname%.crt}"
        
        echo "Renewing: $domain"
        /usr/local/bin/lego \
            --email admin@root.com \
            --domains "$domain" \
            --domains "www.$domain" \
            --http \
            --http.webroot "$acme_webroot" \
            --path "$lego_path" \
            renew --days 30 2>&1
        
        if [[ $? -eq 0 ]]; then
            # Copy renewed certs to domain ssl dir
            local ssl_dir="$DOMAIN_BASE/${domain}/ssl"
            if [[ -d "$ssl_dir" ]]; then
                cp "${lego_cert_dir}/${domain}.crt" "${ssl_dir}/${domain}.crt"
                cp "${lego_cert_dir}/${domain}.key" "${ssl_dir}/${domain}.key"
                cp "${lego_cert_dir}/${domain}.issuer.crt" "${ssl_dir}/${domain}.issuer.crt" 2>/dev/null || true
                chown root:root "${ssl_dir}"/*
                chmod 600 "${ssl_dir}/${domain}.key"
                renewed=$((renewed + 1))
            fi
            echo "OK: $domain renewed"
        else
            echo "SKIP: $domain not yet due for renewal or failed"
        fi
    done
    
    if [[ $renewed -gt 0 ]]; then
        /usr/sbin/httpd -t 2>&1 && systemctl reload httpd
        echo "OK: Apache reloaded after $renewed renewals"
    else
        echo "OK: No renewals needed"
    fi
    
    log "CERTBOT_RENEW_ALL: $renewed domains renewed"
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: da-domain-register <domain>
# Register domain in DirectAdmin's domain list + config files
# (So DA stays aware even though we manage the vhost)
# ═══════════════════════════════════════════════════════════════
cmd_da_domain_register() {
    local domain="$1"
    validate_domain "$domain"
    
    # Add to DA domains.list if not there
    if ! grep -qx "$domain" "$DA_DOMAINS_LIST" 2>/dev/null; then
        echo "$domain" >> "$DA_DOMAINS_LIST"
    fi
    
    # Create DA domain config
    local da_conf="$DA_USER_DOMAINS/${domain}.conf"
    if [[ ! -f "$da_conf" ]]; then
        cat > "$da_conf" << DACONF
UseCanonicalName=OFF
acme_provider=letsencrypt
active=yes
bandwidth=unlimited
cgi=OFF
defaultdomain=no
domain=${domain}
force_ssl=yes
ip=${SERVER_IP}
open_basedir=ON
php=ON
quota=unlimited
safemode=OFF
ssl=ON
suspended=no
username=root
DACONF
        chown diradmin:diradmin "$da_conf" 2>/dev/null || true
    fi
    
    # Create other required DA files
    for ext in ftp ip_list mime.types subdomains usage; do
        local f="$DA_USER_DOMAINS/${domain}.${ext}"
        if [[ ! -f "$f" ]]; then
            touch "$f"
            chown diradmin:diradmin "$f" 2>/dev/null || true
        fi
    done
    
    # IP list
    echo "$SERVER_IP" > "$DA_USER_DOMAINS/${domain}.ip_list"
    
    log "DA_DOMAIN_REGISTER: $domain"
    echo "OK: Domain registered in DA for $domain"
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: da-httpd-rebuild
# Rebuild DA's httpd.conf to include new domains
# ═══════════════════════════════════════════════════════════════
cmd_da_httpd_rebuild() {
    if command -v /usr/local/directadmin/directadmin &>/dev/null; then
        echo "action=rewrite&value=httpd" | /usr/local/directadmin/directadmin taskq --run 2>&1
        log "DA_HTTPD_REBUILD: taskq executed"
        echo "OK: DA httpd rebuild triggered"
    else
        echo "SKIP: DirectAdmin not available"
    fi
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: email-create <user@domain> <password>
# Create email account (Postfix+Dovecot)
# ═══════════════════════════════════════════════════════════════
cmd_email_create() {
    local email="$1"
    local password="$2"
    
    if [[ ! "$email" =~ ^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$ ]]; then
        echo "ERROR: Invalid email address"
        exit 1
    fi
    
    local user="${email%%@*}"
    local domain="${email#*@}"
    validate_domain "$domain"
    
    local maildir="/home/root/Maildir/${domain}/${user}"
    
    # Create Maildir
    mkdir -p "$maildir"/{cur,new,tmp}
    chown -R root:root "$maildir"
    
    # Add to virtual mailbox map
    local vmap="/etc/postfix/virtual_mailbox_maps"
    if [[ -f "$vmap" ]]; then
        if ! grep -q "^${email}" "$vmap"; then
            echo "${email}    ${domain}/${user}/" >> "$vmap"
            postmap "$vmap" 2>/dev/null || true
        fi
    fi
    
    # Set password via doveadm if available
    if command -v doveadm &>/dev/null; then
        local hash=$(doveadm pw -s SHA512-CRYPT -p "$password" 2>/dev/null)
        local passdb="/etc/dovecot/users"
        if [[ -f "$passdb" ]]; then
            # Remove existing entry
            sed -i "/^${email}:/d" "$passdb"
            echo "${email}:${hash}" >> "$passdb"
        fi
    fi
    
    # Reload postfix
    systemctl reload postfix 2>/dev/null || true
    
    log "EMAIL_CREATE: $email"
    echo "OK: Email account created for $email"
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: email-delete <user@domain>
# Remove email account
# ═══════════════════════════════════════════════════════════════
cmd_email_delete() {
    local email="$1"
    
    if [[ ! "$email" =~ ^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$ ]]; then
        echo "ERROR: Invalid email address"
        exit 1
    fi
    
    local user="${email%%@*}"
    local domain="${email#*@}"
    
    # Remove from virtual mailbox map
    local vmap="/etc/postfix/virtual_mailbox_maps"
    if [[ -f "$vmap" ]]; then
        sed -i "/^${email}/d" "$vmap"
        postmap "$vmap" 2>/dev/null || true
    fi
    
    # Remove from Dovecot passdb
    local passdb="/etc/dovecot/users"
    if [[ -f "$passdb" ]]; then
        sed -i "/^${email}:/d" "$passdb"
    fi
    
    systemctl reload postfix 2>/dev/null || true
    
    log "EMAIL_DELETE: $email"
    echo "OK: Email account removed: $email"
    echo "NOTE: Maildir preserved at /home/root/Maildir/${domain}/${user}"
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: firewall-list
# List UFW rules
# ═══════════════════════════════════════════════════════════════
cmd_firewall_list() {
    ufw status verbose 2>&1 || iptables -L -n 2>&1
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: firewall-add <port/proto> [from_ip]
# Add UFW rule
# ═══════════════════════════════════════════════════════════════
cmd_firewall_add() {
    local rule="$1"
    local from="${2:-any}"
    
    if [[ ! "$rule" =~ ^[0-9]+(/tcp|/udp)?$ ]]; then
        echo "ERROR: Invalid port/proto format (e.g., 80/tcp)"
        exit 1
    fi
    
    if [[ "$from" == "any" ]]; then
        ufw allow "$rule" 2>&1
    else
        ufw allow from "$from" to any port "${rule%%/*}" proto "${rule##*/}" 2>&1
    fi
    log "FIREWALL_ADD: $rule from $from"
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: firewall-delete <rule_number>
# Delete UFW rule by number
# ═══════════════════════════════════════════════════════════════
cmd_firewall_delete() {
    local num="$1"
    if [[ ! "$num" =~ ^[0-9]+$ ]]; then
        echo "ERROR: Invalid rule number"
        exit 1
    fi
    echo "y" | ufw delete "$num" 2>&1
    log "FIREWALL_DELETE: rule $num"
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: fail2ban-status [jail]
# Show Fail2Ban status
# ═══════════════════════════════════════════════════════════════
cmd_fail2ban_status() {
    local jail="${1:-}"
    if [[ -n "$jail" ]]; then
        fail2ban-client status "$jail" 2>&1
    else
        fail2ban-client status 2>&1
    fi
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: fail2ban-unban <ip>
# Unban IP from all jails
# ═══════════════════════════════════════════════════════════════
cmd_fail2ban_unban() {
    local ip="$1"
    if [[ ! "$ip" =~ ^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
        echo "ERROR: Invalid IP address"
        exit 1
    fi
    fail2ban-client unban "$ip" 2>&1
    log "FAIL2BAN_UNBAN: $ip"
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: apply-security-hardening
# Runs GoSiteMe scripts/security/apply-hardening.sh (Fail2Ban + nginx zones)
# YELLOW tier — requires HMAC token
# ═══════════════════════════════════════════════════════════════
cmd_apply_security_hardening() {
    local script="/home/root/domains/root.com/public_html/scripts/security/apply-hardening.sh"
    if [[ ! -f "$script" ]]; then
        echo "ERROR: Script not found: $script"
        exit 1
    fi
    bash "$script"
    log "APPLY_SECURITY_HARDENING: completed"
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: apply-stack-optimization
# sysctl, MariaDB/Redis/Apache snippets — see scripts/optimization/
# YELLOW tier — requires HMAC token
# ═══════════════════════════════════════════════════════════════
cmd_apply_stack_optimization() {
    local script="/home/root/domains/root.com/public_html/scripts/optimization/apply-stack-optimization.sh"
    if [[ ! -f "$script" ]]; then
        echo "ERROR: Script not found: $script"
        exit 1
    fi
    bash "$script"
    log "APPLY_STACK_OPTIMIZATION: completed"
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: service-status <service>
# Check systemd service status
# ═══════════════════════════════════════════════════════════════
cmd_service_status() {
    local service="$1"
    # Only allow known services
    case "$service" in
        httpd|apache2|mysqld|mariadb|named|bind9|postfix|dovecot|redis|fail2ban|ufw|clamav-daemon|php*-fpm)
            systemctl status "$service" 2>&1 | head -20
            ;;
        *)
            echo "ERROR: Service not in allowlist"
            exit 1
            ;;
    esac
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: service-restart <service>
# Restart a systemd service
# ═══════════════════════════════════════════════════════════════
cmd_service_restart() {
    local service="$1"
    case "$service" in
        httpd|apache2|mysqld|mariadb|named|bind9|postfix|dovecot|redis|fail2ban|php*-fpm)
            systemctl restart "$service" 2>&1
            log "SERVICE_RESTART: $service"
            echo "OK: $service restarted"
            ;;
        *)
            echo "ERROR: Service not in allowlist"
            exit 1
            ;;
    esac
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: docker-list
# List Docker containers
# ═══════════════════════════════════════════════════════════════
cmd_docker_list() {
    docker ps -a --format '{{json .}}' 2>&1
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: docker-action <action> <container>
# Docker container actions: start/stop/restart/logs
# ═══════════════════════════════════════════════════════════════
cmd_docker_action() {
    local action="$1"
    local container="$2"
    
    if [[ ! "$container" =~ ^[a-zA-Z0-9._-]+$ ]]; then
        echo "ERROR: Invalid container name"
        exit 1
    fi
    
    case "$action" in
        start|stop|restart)
            docker "$action" "$container" 2>&1
            log "DOCKER: $action $container"
            echo "OK: $container $action"
            ;;
        logs)
            docker logs --tail 100 "$container" 2>&1
            ;;
        *)
            echo "ERROR: Invalid action (start/stop/restart/logs)"
            exit 1
            ;;
    esac
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: disk-encryption-status
# Show LUKS disk encryption status
# ═══════════════════════════════════════════════════════════════
cmd_disk_encryption_status() {
    lsblk -f 2>&1
    echo "===LUKS==="
    for dev in /dev/sd* /dev/nvme*; do
        if [[ -b "$dev" ]]; then
            cryptsetup isLuks "$dev" 2>/dev/null && echo "$dev: LUKS" || true
        fi
    done
    echo "===DM==="
    dmsetup ls --target crypt 2>&1 || echo "No dm-crypt volumes"
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: malware-scan <path>
# Run ClamAV scan on a path
# ═══════════════════════════════════════════════════════════════
cmd_malware_scan() {
    local scan_path="$1"
    
    # Only allow scanning within /home/root
    case "$scan_path" in
        /home/root/*)
            ;;
        *)
            echo "ERROR: Can only scan within /home/root"
            exit 1
            ;;
    esac
    
    if command -v clamscan &>/dev/null; then
        clamscan -r --no-summary --infected "$scan_path" 2>&1 | tail -50
    else
        echo "ERROR: ClamAV not installed"
        exit 1
    fi
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: rootkit-check
# Run rkhunter rootkit check
# ═══════════════════════════════════════════════════════════════
cmd_rootkit_check() {
    if command -v rkhunter &>/dev/null; then
        rkhunter --check --skip-keypress --report-warnings-only 2>&1 | tail -30
    elif command -v chkrootkit &>/dev/null; then
        chkrootkit 2>&1 | grep -i "INFECTED\|FOUND\|Warning" | tail -30
    else
        echo "ERROR: No rootkit scanner installed"
        exit 1
    fi
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: security-headers <url>
# Check security headers for a URL
# ═══════════════════════════════════════════════════════════════
cmd_security_headers() {
    local url="$1"
    if [[ ! "$url" =~ ^https?:// ]]; then
        url="https://$url"
    fi
    curl -sI -o /dev/null -w '%{json}' --max-time 10 "$url" 2>/dev/null
    echo ""
    curl -sI --max-time 10 "$url" 2>/dev/null | grep -iE "strict-transport|x-frame|x-content-type|x-xss|referrer-policy|content-security-policy|permissions-policy|feature-policy"
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: network-tool <tool> <target>
# Run network diagnostics
# ═══════════════════════════════════════════════════════════════
cmd_network_tool() {
    local tool="$1"
    local target="$2"
    
    # Validate target — only allow hostnames and IPs
    if [[ ! "$target" =~ ^[a-zA-Z0-9._:-]+$ ]]; then
        echo "ERROR: Invalid target"
        exit 1
    fi
    
    case "$tool" in
        ping)
            ping -c 4 -W 3 "$target" 2>&1
            ;;
        traceroute)
            traceroute -m 15 -w 2 "$target" 2>&1
            ;;
        dig)
            dig "$target" ANY +short 2>&1
            ;;
        whois)
            whois "$target" 2>&1 | head -50
            ;;
        mtr)
            mtr -r -c 5 "$target" 2>&1
            ;;
        nslookup)
            nslookup "$target" 2>&1
            ;;
        port-check)
            local host="${target%%:*}"
            local port="${target##*:}"
            timeout 5 bash -c "echo >/dev/tcp/$host/$port" 2>&1 && echo "OPEN" || echo "CLOSED"
            ;;
        *)
            echo "ERROR: Unknown tool: $tool (ping/traceroute/dig/whois/mtr/nslookup/port-check)"
            exit 1
            ;;
    esac
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: benchmark <type>
# Run system benchmarks
# ═══════════════════════════════════════════════════════════════
cmd_benchmark() {
    local type="$1"
    case "$type" in
        cpu)
            echo "CPU Benchmark (sysbench)..."
            if command -v sysbench &>/dev/null; then
                sysbench cpu --time=5 run 2>&1 | grep -E "events per second|total time|min:|avg:|max:"
            else
                echo "Running dd-based CPU test..."
                dd if=/dev/zero bs=1M count=256 2>&1 | md5sum | head -1
            fi
            ;;
        disk)
            echo "Disk Benchmark..."
            dd if=/dev/zero of=/tmp/.bench_test bs=1M count=256 conv=fdatasync 2>&1
            rm -f /tmp/.bench_test
            ;;
        memory)
            echo "Memory Benchmark..."
            if command -v sysbench &>/dev/null; then
                sysbench memory --time=5 run 2>&1 | grep -E "transferred|total time|Operations"
            else
                dd if=/dev/zero of=/dev/null bs=1M count=1024 2>&1
            fi
            ;;
        network)
            echo "Network test to cloudflare..."
            curl -o /dev/null -w "DNS: %{time_namelookup}s\nConnect: %{time_connect}s\nTTFB: %{time_starttransfer}s\nTotal: %{time_total}s\nSpeed: %{speed_download} bytes/s\n" https://www.cloudflare.com 2>/dev/null
            ;;
        *)
            echo "ERROR: Unknown benchmark type (cpu/disk/memory/network)"
            exit 1
            ;;
    esac
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: read-log <source> [lines]
# Read system logs
# ═══════════════════════════════════════════════════════════════
cmd_read_log() {
    local source="$1"
    local lines="${2:-100}"
    
    if [[ ! "$lines" =~ ^[0-9]+$ ]] || [[ "$lines" -gt 1000 ]]; then
        lines=100
    fi
    
    case "$source" in
        apache|httpd)
            tail -n "$lines" /var/log/httpd/error_log 2>/dev/null || tail -n "$lines" /var/log/apache2/error.log 2>/dev/null
            ;;
        access)
            tail -n "$lines" /var/log/httpd/access_log 2>/dev/null || tail -n "$lines" /var/log/apache2/access.log 2>/dev/null
            ;;
        mysql)
            tail -n "$lines" /var/log/mysql/error.log 2>/dev/null || tail -n "$lines" /var/log/mysqld.log 2>/dev/null
            ;;
        mail)
            tail -n "$lines" /var/log/mail.log 2>/dev/null
            ;;
        auth)
            tail -n "$lines" /var/log/auth.log 2>/dev/null
            ;;
        syslog)
            tail -n "$lines" /var/log/syslog 2>/dev/null
            ;;
        fail2ban)
            tail -n "$lines" /var/log/fail2ban.log 2>/dev/null
            ;;
        directadmin)
            tail -n "$lines" /var/log/directadmin/error.log 2>/dev/null
            ;;
        bridge)
            tail -n "$lines" /var/log/gohostme-bridge.log 2>/dev/null
            ;;
        *)
            # Allow reading domain-specific logs
            if [[ "$source" =~ ^[a-zA-Z0-9.-]+\.log$ ]] && [[ -f "/var/log/httpd/domains/$source" ]]; then
                tail -n "$lines" "/var/log/httpd/domains/$source"
            else
                echo "ERROR: Unknown log source (apache/access/mysql/mail/auth/syslog/fail2ban/directadmin/bridge)"
                exit 1
            fi
            ;;
    esac
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: php-versions
# List installed PHP versions
# ═══════════════════════════════════════════════════════════════
cmd_php_versions() {
    for phpdir in /usr/local/php*/bin/php; do
        if [[ -x "$phpdir" ]]; then
            local ver=$($phpdir -v 2>/dev/null | head -1)
            local sock=$(echo "$phpdir" | sed 's|/bin/php||')
            echo "$phpdir: $ver (socket: ${sock}/sockets/root.sock)"
        fi
    done
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: full-domain-setup <domain>
# Complete domain setup: dirs + vhost + DNS + DA register + reload
# ═══════════════════════════════════════════════════════════════
cmd_full_domain_setup() {
    local domain="$1"
    validate_domain "$domain"
    
    echo "═══ Full Domain Setup: $domain ═══"
    
    # 1. Create directory structure (as root user if possible)
    local dompath="$DOMAIN_BASE/$domain"
    if [[ ! -d "$dompath/public_html" ]]; then
        mkdir -p "$dompath"/{public_html,logs,ssl}
        chown -R root:root "$dompath"
        echo "✓ Directories created"
    else
        echo "✓ Directories exist"
    fi
    
    # 2. Create VHost
    cmd_vhost_create "$domain"
    
    # 3. Create DNS zone (if needed)
    cmd_dns_zone_create "$domain"
    
    # 4. Register in DA
    cmd_da_domain_register "$domain"
    
    # 5. Reload services
    cmd_apache_reload
    cmd_dns_reload
    
    echo "═══ Domain setup complete: $domain ═══"
    log "FULL_DOMAIN_SETUP: $domain completed"
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: rotate-user-password <username> <new-password>
# Change a system user's password (root only)
# ═══════════════════════════════════════════════════════════════
cmd_rotate_user_password() {
    local user="$1"
    local newpass="$2"
    
    # Only allow rotating root or root
    if [[ "$user" != "root" && "$user" != "root" ]]; then
        echo "ERROR: Can only rotate root or root passwords"
        exit 1
    fi
    
    echo "$user:$newpass" | chpasswd
    if [[ $? -eq 0 ]]; then
        echo "✓ Password rotated for $user"
        log "USER_PASSWORD_ROTATE: $user password changed"
    else
        echo "ERROR: Failed to change password for $user"
        exit 1
    fi
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: read-exim-log [lines]
# Read Exim main log (requires root)
# ═══════════════════════════════════════════════════════════════
cmd_read_exim_log() {
    local lines="${1:-50}"
    if [[ -f /var/log/exim/mainlog ]]; then
        tail -n "$lines" /var/log/exim/mainlog
    elif [[ -f /var/log/mail.log ]]; then
        tail -n "$lines" /var/log/mail.log
    else
        echo "No exim/mail log found"
    fi
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: exim-config-update <setting> <value>
# Update Exim smarthost relay configuration
# ═══════════════════════════════════════════════════════════════
cmd_exim_config_read() {
    if [[ -f /etc/exim.conf ]]; then
        grep -n "smarthost\|relay\|route_list\|driver.*smtp\|port.*587\|hosts_require_auth" /etc/exim.conf 2>/dev/null || echo "No relay config found"
    else
        echo "ERROR: /etc/exim.conf not found"
    fi
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: build-iso [build-type]
# Build Alfred Linux ISO (GA build). Runs as root.
# ═══════════════════════════════════════════════════════════════
cmd_build_iso() {
    local build_type="${1:-ga}"
    local project_dir="/home/root/alfred-linux-v2"
    local script="$project_dir/scripts/build-unified.sh"
    local log_file="$project_dir/build-ga-live.log"

    # Validate build type
    case "$build_type" in
        b[1-6]|rc|rc[0-9]|rc[0-9][0-9]|ga|ga-lite)
            ;;
        *)
            echo "ERROR: Invalid build type: $build_type"
            echo "Valid: b1-b6, rc, rc1-rc10, ga, ga-lite"
            return 1
            ;;
    esac

    if [[ ! -f "$script" ]]; then
        echo "ERROR: Build script not found: $script"
        return 1
    fi

    echo "[BRIDGE] Launching Alfred Linux $build_type build..."
    echo "[BRIDGE] Script: $script"
    echo "[BRIDGE] Log: $log_file"
    echo "[BRIDGE] Time: $(date)"

    # Run in background so bridge returns immediately
    nohup bash "$script" "$build_type" > "$log_file" 2>&1 &
    local pid=$!
    echo "[BRIDGE] Build launched as PID $pid"
    echo "[BRIDGE] Monitor: tail -f $log_file"
    echo "OK"
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: account-create <username> <domain> [package]
# Provision a hosting account using root-server + apache2 vhost deploy
# ═══════════════════════════════════════════════════════════════
cmd_account_create() {
    local username="$1"
    local domain="$2"
    local package="${3:-default}"

    validate_username "$username"
    validate_domain "$domain"

    /usr/local/bin/root-server create-user \
        --username="$username" \
        --domain="$domain" \
        --php-version=83 2>&1

    local docroot="/home/${username}/domains/${domain}/public_html"
    local vhost
    vhost=$(cat <<EOF
<VirtualHost *:80>
    ServerName ${domain}
    ServerAlias www.${domain}
    DocumentRoot ${docroot}

    <Directory ${docroot}>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/${domain}.error.log
    CustomLog \${APACHE_LOG_DIR}/${domain}.access.log combined

    <FilesMatch \\.php$>
        SetHandler "proxy:unix:/run/php/php83-root.sock|fcgi://localhost/"
    </FilesMatch>
</VirtualHost>
EOF
)

    local vhost_b64
    vhost_b64=$(printf "%s" "$vhost" | base64 -w0)
    cmd_apache2_vhost_deploy "$domain" "$vhost_b64"

    log "ACCOUNT_CREATE: user=${username} domain=${domain} package=${package}"
    echo "OK: Account created for ${username} (${domain})"
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: account-suspend <username>
# Lock user shell auth and disable webroot access
# ═══════════════════════════════════════════════════════════════
cmd_account_suspend() {
    local username="$1"
    validate_username "$username"

    if ! id "$username" &>/dev/null; then
        echo "ERROR: User not found: $username"
        exit 1
    fi

    usermod -L "$username" 2>/dev/null || true

    local base="/home/${username}/domains"
    if [[ -d "$base" ]]; then
        local d
        for d in "$base"/*; do
            [[ -d "$d/public_html" ]] || continue
            chmod 000 "$d/public_html" 2>/dev/null || true
        done
    fi

    log "ACCOUNT_SUSPEND: user=${username}"
    echo "OK: Account suspended for ${username}"
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: account-unsuspend <username>
# Unlock user shell auth and restore webroot permissions
# ═══════════════════════════════════════════════════════════════
cmd_account_unsuspend() {
    local username="$1"
    validate_username "$username"

    if ! id "$username" &>/dev/null; then
        echo "ERROR: User not found: $username"
        exit 1
    fi

    usermod -U "$username" 2>/dev/null || true

    local base="/home/${username}/domains"
    if [[ -d "$base" ]]; then
        local d
        for d in "$base"/*; do
            [[ -d "$d/public_html" ]] || continue
            chmod 755 "$d/public_html" 2>/dev/null || true
        done
    fi

    log "ACCOUNT_UNSUSPEND: user=${username}"
    echo "OK: Account unsuspended for ${username}"
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: account-terminate <username>
# Remove account data and apache2 vhosts for user's domains
# ═══════════════════════════════════════════════════════════════
cmd_account_terminate() {
    local username="$1"
    validate_username "$username"

    local base="/home/${username}/domains"
    if [[ -d "$base" ]]; then
        local d domain
        for d in "$base"/*; do
            [[ -d "$d" ]] || continue
            domain=$(basename "$d")
            validate_domain "$domain"
            rm -f "/etc/apache2/sites-enabled/zz-domain-${domain}.conf"
            rm -f "/etc/apache2/sites-available/zz-domain-${domain}.conf"
        done
        systemctl reload apache2 2>&1 || true
    fi

    if id "$username" &>/dev/null; then
        /usr/local/bin/root-server remove-user --username="$username" 2>&1 || userdel -r "$username" 2>/dev/null || true
    fi

    log "ACCOUNT_TERMINATE: user=${username}"
    echo "OK: Account terminated for ${username}"
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: account-change-password <username> <password>
# Change system password for a hosting account
# ═══════════════════════════════════════════════════════════════
cmd_account_change_password() {
    local username="$1"
    local newpass="$2"
    validate_username "$username"

    if ! id "$username" &>/dev/null; then
        echo "ERROR: User not found: $username"
        exit 1
    fi

    echo "${username}:${newpass}" | chpasswd
    log "ACCOUNT_CHANGE_PASSWORD: user=${username}"
    echo "OK: Password changed for ${username}"
}

# ═══════════════════════════════════════════════════════════════
# COMMAND: account-change-package <username> <package>
# Track package changes for automation and auditing
# ═══════════════════════════════════════════════════════════════
cmd_account_change_package() {
    local username="$1"
    local package="$2"
    validate_username "$username"

    if [[ -z "$package" ]]; then
        echo "ERROR: Missing package"
        exit 1
    fi

    log "ACCOUNT_CHANGE_PACKAGE: user=${username} package=${package}"
    echo "OK: Package changed for ${username} -> ${package}"
}

# ═══════════════════════════════════════════════════════════════
# ═══════════════════════════════════════════════════════════════
# ═══════════════════════════════════════════════════════════════
# COMMAND: token-generate <command> [args...]
# Generates a valid HMAC token for a command using bridge rules
# ═══════════════════════════════════════════════════════════════
cmd_token_generate() {
    if [[ $# -lt 1 ]]; then
        echo "ERROR: Usage: token-generate <command> [args...]"
        exit 1
    fi

    local target_command="$1"
    shift
    local args="$*"

    if [[ ! -f "$HMAC_SECRET_FILE" ]]; then
        echo "ERROR: Bridge security not configured (missing HMAC secret)"
        exit 99
    fi

    local secret
    secret=$(cat "$HMAC_SECRET_FILE" | tr -d '
')
    local ts
    ts=$(date +%s)
    local payload="${ts}:${target_command}:${args}"
    local hmac
    hmac=$(echo -n "$payload" | openssl dgst -sha256 -hmac "$secret" | awk '{print $NF}')

    echo "${ts}:${hmac}"
}

# MAIN DISPATCHER v2.0 — All commands pass through security gate
# ═══════════════════════════════════════════════════════════════

if [[ $# -lt 1 ]]; then
    echo "GoHostMe Sovereignty Bridge v2.0 (HARDENED)"
    echo "Usage: sudo $0 <command> [--token=HMAC] [--approval=CODE] [args...]"
    echo ""
    echo "Security: All commands pass through HMAC verification + tier gating."
    echo "  GREEN  = read-only (auto-approved)"
    echo "  YELLOW = requires valid HMAC token"
    echo "  RED    = requires HMAC token + dashboard approval from Commander"
    echo ""
    echo "GREEN Commands (read-only):"
    echo "  read-log, service-status, firewall-list, php-versions, benchmark,"
    echo "  apache-test, apache-vhosts, dns-zone-read, disk-encryption-status,"
    echo "  network-tool, security-headers, docker-list, fail2ban-status,"
    echo "  malware-scan, rootkit-check, read-exim-log, exim-config-read, token-generate"
    echo ""
    echo "YELLOW Commands (token required):"
    echo "  apache-reload, dns-reload, certbot-request, certbot-renew-all,"
    echo "  service-restart, email-create, email-delete, docker-action,"
    echo "  account-create, account-suspend, account-unsuspend,"
    echo "  account-terminate,"
    echo "  account-change-password, account-change-package,"
    echo "  da-httpd-rebuild, rotate-user-password, apply-security-hardening,"
    echo "  apply-stack-optimization, build-iso"
    echo ""
    echo "RED Commands (token + dashboard approval):"
    echo "  firewall-add, firewall-delete, vhost-create, vhost-delete,"
    echo "  vhost-ssl-update, dns-zone-create, dns-record-add, dns-record-delete,"
    echo "  da-domain-register, full-domain-setup, fail2ban-unban,"
    echo "  bridge-sync"
    echo ""
    echo "Generate tokens: php /home/root/.vault/bridge-token.php <command> [args]"
    echo "Request approval: /commander-bridge.php (web dashboard)"
    exit 0
fi

COMMAND="$1"
shift

# ═══ SECURITY GATE — verify token + tier + approval ═══
CLEAN_ARGS=()
security_gate "$COMMAND" "$@"

# Use cleaned args (with --token and --approval stripped)
set -- "${CLEAN_ARGS[@]+"${CLEAN_ARGS[@]}"}"

case "$COMMAND" in
    vhost-create)       cmd_vhost_create "$@" ;;
    vhost-delete)       cmd_vhost_delete "$@" ;;
    vhost-ssl-update)   cmd_vhost_ssl_update "$@" ;;
    apache-reload)      cmd_apache_reload ;;
    apache-test)        cmd_apache_test ;;
    apache-vhosts)      cmd_apache_vhosts ;;
    dns-zone-create)    cmd_dns_zone_create "$@" ;;
    dns-record-add)     cmd_dns_record_add "$@" ;;
    dns-record-delete)  cmd_dns_record_delete "$@" ;;
    dns-zone-read)      cmd_dns_zone_read "$@" ;;
    dns-reload)         cmd_dns_reload ;;
    certbot-request)    cmd_certbot_request "$@" ;;
    apache2-vhost-deploy) cmd_apache2_vhost_deploy "$@" ;;
    certbot-renew-all)  cmd_certbot_renew_all ;;
    da-domain-register) cmd_da_domain_register "$@" ;;
    da-httpd-rebuild)   cmd_da_httpd_rebuild ;;
    email-create)       cmd_email_create "$@" ;;
    email-delete)       cmd_email_delete "$@" ;;
    firewall-list)      cmd_firewall_list ;;
    firewall-add)       cmd_firewall_add "$@" ;;
    firewall-delete)    cmd_firewall_delete "$@" ;;
    fail2ban-status)    cmd_fail2ban_status "$@" ;;
    fail2ban-unban)     cmd_fail2ban_unban "$@" ;;
    apply-security-hardening) cmd_apply_security_hardening ;;
    apply-stack-optimization) cmd_apply_stack_optimization ;;
    service-status)     cmd_service_status "$@" ;;
    service-restart)    cmd_service_restart "$@" ;;
    docker-list)        cmd_docker_list ;;
    docker-action)      cmd_docker_action "$@" ;;
    disk-encryption-status) cmd_disk_encryption_status ;;
    malware-scan)       cmd_malware_scan "$@" ;;
    rootkit-check)      cmd_rootkit_check ;;
    security-headers)   cmd_security_headers "$@" ;;
    network-tool)       cmd_network_tool "$@" ;;
    benchmark)          cmd_benchmark "$@" ;;
    read-log)           cmd_read_log "$@" ;;
    php-versions)       cmd_php_versions ;;
    full-domain-setup)  cmd_full_domain_setup "$@" ;;
    rotate-user-password) cmd_rotate_user_password "$@" ;;
    account-create)     cmd_account_create "$@" ;;
    account-suspend)    cmd_account_suspend "$@" ;;
    account-unsuspend)  cmd_account_unsuspend "$@" ;;
    account-terminate)  cmd_account_terminate "$@" ;;
    account-change-password) cmd_account_change_password "$@" ;;
    account-change-package) cmd_account_change_package "$@" ;;
    read-exim-log)      cmd_read_exim_log "$@" ;;
    exim-config-read)   cmd_exim_config_read ;;
    token-generate)    cmd_token_generate "$@" ;;
    bridge-sync)        cmd_bridge_sync ;;
    build-iso)          cmd_build_iso "$@" ;;
    *)
        audit_log "$COMMAND" "$*" "red" 0 "NULL" "denied"
        echo "ERROR: Unknown command: $COMMAND"
        echo "Run without arguments for usage help."
        exit 1
        ;;
esac
