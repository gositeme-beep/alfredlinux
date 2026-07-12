#!/usr/bin/env bash
# ═══════════════════════════════════════════════════════════════
#  GoSiteMe — Add Site to Nginx
#  Usage: bash add-site.sh example.com [/path/to/root]
# ═══════════════════════════════════════════════════════════════
set -euo pipefail

DOMAIN="${1:?Usage: add-site.sh domain.com [webroot]}"
WEBROOT="${2:-/home/gositeme/domains/${DOMAIN}/public_html}"
NGINX_CONF="/etc/nginx/sites-available/${DOMAIN}.conf"

[[ $EUID -ne 0 ]] && { echo "Run as root"; exit 1; }

mkdir -p "$WEBROOT"
chown -R gositeme:gositeme "$(dirname "$WEBROOT")"

cat > "$NGINX_CONF" << VHOST
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN} www.${DOMAIN};

    root ${WEBROOT};
    index index.php index.html;

    # Security
    location ~ /\\.ht { deny all; }
    location ~ /\\.(git|env|config) { deny all; }

    # Static file caching
    location ~* \\.(css|js|png|jpg|jpeg|gif|ico|svg|woff2|woff|ttf|webp|avif)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
        try_files \$uri =404;
    }

    # PHP
    location ~ \\.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    # SPA / Clean URLs
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    # WebSocket proxy
    location /ws {
        proxy_pass http://127.0.0.1:9090;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host \$host;
        proxy_read_timeout 86400;
    }

    # Upload limits
    client_max_body_size 256M;
}
VHOST

ln -sf "$NGINX_CONF" /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx

echo "✅ ${DOMAIN} configured → ${WEBROOT}"
echo "   Run: certbot --nginx -d ${DOMAIN} -d www.${DOMAIN}"
