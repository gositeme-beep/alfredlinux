# GoSiteMe ecosystem — complete Ubuntu setup & restore (mesh / disaster)

**Use this document when OVH is gone or you’re standing up a new Ubuntu node.**  
You do **not** need to remember the old server — follow sections in order.

**Companion files:** `ECOSYSTEM-RESTORE.md`, `ORGANIZED-DATABASES.md` (multi-DB layout), `ecosystem-full-backup.sh`.

---

## Part A — What you must have before you start

### A.1 Files from your off-server copy of `ecosystem-export-YYYYMMDD-HHMMSS/`

| File | Required? |
|------|-----------|
| `databases/*.sql.gz` | **Yes** — one file per MySQL database (organized backup) |
| `databases/MANIFEST-databases.json` | **Yes** — which DB is which |
| `gositeme-db-*.sql.gz` | **Yes** if present — symlink to `databases/gositeme_whmcs.sql.gz` |
| `all-domains-*.tar.gz` | **Yes** — all sites under `~/domains/` |
| `SYSTEM-*.txt` | **Recommended** — PHP/MySQL versions from old host |
| `crontab-*.txt` | **Recommended** — copy jobs back after restore |
| `domain-directories-*.txt` | **Helpful** — list of domain folder names |
| `MANIFEST-ECOSYSTEM-*.txt` | Optional — file list / sizes |

Copy the whole folder to the new machine, e.g. `/root/gositeme-restore/` or your home.

### A.2 Things the backup does **not** include (save separately today)

Write these down or export them **before** you rely only on the tarball:

| Item | What to save |
|------|----------------|
| **DNS** | Registrar zone export or screenshot of A/AAAA/CNAME/MX/TXT |
| **Email** | If you use host mail: export mailboxes or plan new mail host |
| **TLS** | You will **re-issue** certs on the new server (Let’s Encrypt, etc.) |
| **OVH** | Firewall rules, LB, Object Storage buckets — screenshot or doc |
| **Hidden DBs** | If a site keeps DB creds outside `domains/` (not in `.env` / `database.env.php` / `wp-config`), add them there or dump manually |
| **Outside `~/domains/`** | e.g. `~/.ssh`, custom scripts in home — not in `all-domains` tarball |

### A.3 What the tarball **skips** inside `domains/` (you regenerate on Ubuntu)

- `node_modules` → `npm install`
- `.next` / `.nuxt` → `npm run build` (or project’s build command)
- `.venv` / `venv` → recreate Python venv + `pip install`
- `*.log` → ignore
- `.git` → **not** in archive unless you backed up with `ECOSYSTEM_INCLUDE_GIT=1`

**Your live `.env` files and `vendor/` (Composer) are included** — you are not missing PHP app code or secrets that live in `.env`.

---

## Part B — Fresh Ubuntu (22.04 or 24.04 LTS)

Assume: **sudo** user, clean server or VM. Replace `YOUR_SUDO_USER` where needed.

### B.1 Base system

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y curl wget gnupg2 software-properties-common unzip git ca-certificates
```

### B.2 Create the `gositeme` user (match production paths)

The backup expects sites under **`/home/gositeme/domains/`**.

```bash
sudo adduser gositeme
# set a password; you can skip “full name” fields

sudo mkdir -p /home/gositeme/domains
sudo chown -R gositeme:gositeme /home/gositeme
```

You will run **web server as `www-data`** but files owned by **`gositeme`**; we add `gositeme` to `www-data` group and set group permissions (see Part E).

### B.3 MariaDB (matches production class: MariaDB 10.6+)

```bash
sudo apt install -y mariadb-server mariadb-client
sudo systemctl enable mariadb
sudo systemctl start mariadb
sudo mysql_secure_installation
# follow prompts: set root password, remove test DB, disallow remote root if you want
```

**Large imports:** raise packet size (WHMCS / big dumps need this).

```bash
sudo tee /etc/mysql/mariadb.conf.d/99-gositeme-restore.cnf <<'EOF'
[mysqld]
max_allowed_packet = 512M
innodb_buffer_pool_size = 512M
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci
[client]
default-character-set = utf8mb4
EOF

sudo systemctl restart mariadb
```

### B.4 Databases + users + import (often **several** DBs)

Backups now include **`databases/*.sql.gz`** plus **`databases/MANIFEST-databases.json`**.  
Open the manifest: each entry is one MySQL database discovered from your sites (e.g. `gositeme_whmcs`, `gositeme_soundstudiopro`).

**Rule:** On the new server, create **the same database name + MySQL user** as on the old server (passwords must match what’s in the **`.env` / `database.env.php`** you restored from the file tarball — copy from those files if you don’t remember).

Example (adjust users/passwords to match **your** manifest + configs):

```bash
sudo mysql -u root -p <<'EOF'
CREATE DATABASE IF NOT EXISTS gositeme_whmcs
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'gositeme_whmcs'@'localhost' IDENTIFIED BY 'PASSWORD_FROM_GOSITEME_ENV';
GRANT ALL PRIVILEGES ON gositeme_whmcs.* TO 'gositeme_whmcs'@'localhost';

CREATE DATABASE IF NOT EXISTS gositeme_soundstudiopro
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'gositeme_soundstudiopro'@'localhost' IDENTIFIED BY 'PASSWORD_FROM_SOUNDSTUDIOPRO_CONFIG';
GRANT ALL PRIVILEGES ON gositeme_soundstudiopro.* TO 'gositeme_soundstudiopro'@'localhost';

FLUSH PRIVILEGES;
EOF
```

**Import each** dump into the matching database (from your export folder):

```bash
cd /path/to/ecosystem-export-YYYYMMDD-HHMMSS/databases
gunzip -c gositeme_whmcs.sql.gz           | mysql -u gositeme_whmcs -p gositeme_whmcs
gunzip -c gositeme_soundstudiopro.sql.gz  | mysql -u gositeme_soundstudiopro -p gositeme_soundstudiopro
# repeat for every other *.sql.gz in this folder
```

If you still have the top-level symlink **`gositeme-db-*.sql.gz`**, it points at **`gositeme_whmcs`** — same as `databases/gositeme_whmcs.sql.gz`.

If import fails with “packet too large”, confirm `99-gositeme-restore.cnf` and restart MariaDB again.

More detail: **[`ORGANIZED-DATABASES.md`](ORGANIZED-DATABASES.md)**.

### B.5 PHP 8.3 + extensions (production uses PHP 8.3.x class)

```bash
sudo apt install -y \
  php8.3-fpm php8.3-cli php8.3-common \
  php8.3-mysql php8.3-xml php8.3-mbstring php8.3-curl \
  php8.3-gd php8.3-zip php8.3-intl php8.3-bcmath \
  php8.3-soap php8.3-readline

sudo systemctl enable php8.3-fpm
sudo systemctl start php8.3-fpm
```

**WHMCS / IonCube:** If `SYSTEM-*.txt` or old host used IonCube, install the matching IonCube loader for PHP 8.3 from [ioncube.com](https://www.ioncube.com/loaders.php) and add the `zend_extension` line to a `.ini` under `/etc/php/8.3/mods-available/`, then `phpenmod` and restart `php8.3-fpm`.

### B.6 Nginx + PHP-FPM (recommended on Ubuntu)

```bash
sudo apt install -y nginx
sudo systemctl enable nginx
sudo systemctl start nginx
```

You will add **one server block per domain** (Part D). PHP runs via **Unix socket** to `php8.3-fpm`.

### B.7 Optional: Redis (if `.env` references Redis)

```bash
sudo apt install -y redis-server
sudo systemctl enable redis-server
sudo systemctl start redis-server
```

Check each site’s `.env` for `REDIS_*` or `CACHE_DRIVER=redis`.

### B.8 Optional: Node.js (for frontends that need rebuild)

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
node -v
npm -v
```

---

## Part C — Restore files from `all-domains-*.tar.gz`

### C.1 Extract as root, then fix ownership

```bash
sudo tar -xzf /path/to/all-domains-YYYYMMDD-HHMMSS.tar.gz -C /home/gositeme
```

The archive root is **`domains/`**, so you get `/home/gositeme/domains/...`.

```bash
sudo chown -R gositeme:gositeme /home/gositeme/domains
sudo usermod -aG www-data gositeme
```

Log out and back in as `gositeme` if you need the new group in your session.

### C.2 Update `.env` files for the new machine

For **each** site that talks to MySQL (at minimum **gositeme.com** main app):

1. Open ` /home/gositeme/domains/<domain>/public_html/.env` (if present).
2. Set:
   - `GOSITEME_DB_HOST=127.0.0.1` or `localhost`
   - `GOSITEME_DB_NAME=gositeme_whmcs`
   - `GOSITEME_DB_USER=gositeme_whmcs`
   - `GOSITEME_DB_PASS=` **the password you chose in B.4**
3. Set any `APP_URL`, `SITE_URL`, or `WHMCS` system URL to **`http://<domain>.local`** or your real hostname while testing.

Use `domain-directories-*.txt` to find which folders exist; not every folder has a `.env`.

### C.3 Composer (PHP dependencies)

Where `composer.json` exists (e.g. `gositeme.com/public_html`):

```bash
sudo apt install -y composer
sudo -u gositeme -H bash -c 'cd /home/gositeme/domains/gositeme.com/public_html && composer install --no-dev --optimize-autoloader'
```

Repeat `-cd` for each site that has `composer.json` (see your tree).

### C.4 npm (where `package.json` exists and you need a dev/build)

```bash
sudo -u gositeme -H bash -c 'cd /home/gositeme/domains/SOME_SITE/public_html && npm ci && npm run build'
```

Only for sites that actually use Node; skip others.

---

## Part D — Nginx: one vhost per domain

### D.1 Pattern

Document root for each site:  
`/home/gositeme/domains/<folder>/public_html`

`<folder>` is usually the domain name, e.g. `gositeme.com`, `lavocat.quebec`.

### D.2 Example server block (replace DOMAIN and folder name)

Create `/etc/nginx/sites-available/gositeme.com.conf`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name gositeme.com www.gositeme.com;

    root /home/gositeme/domains/gositeme.com/public_html;
    index index.php index.html;

    client_max_body_size 64M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable and reload:

```bash
sudo ln -sf /etc/nginx/sites-available/gositeme.com.conf /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

**Repeat** for every domain in `domain-directories-*.txt` (adjust `server_name` and `root` paths). Subdomains like `presser.gositeme.com` use folder `presser.gositeme.com` if that’s how the tree was extracted.

### D.3 PHP-FPM user (optional hardening)

Default `www-data` can read files if `gositeme` group has `rx` on paths. Ensure:

```bash
sudo chmod g+rx /home/gositeme /home/gositeme/domains
sudo find /home/gositeme/domains -type d -exec chmod g+rx {} \;
sudo find /home/gositeme/domains -type f -exec chmod g+r {} \;
```

Writable dirs (uploads, cache) may need `www-data` write:

```bash
# Example only — adjust paths your app actually uses
sudo chown -R gositeme:www-data /home/gositeme/domains/gositeme.com/public_html/storage 2>/dev/null || true
sudo chmod -R g+rwX /home/gositeme/domains/gositeme.com/public_html/storage 2>/dev/null || true
```

---

## Part E — Local DNS without real DNS (`/etc/hosts`)

On the **Ubuntu machine** (or your laptop if you browse via SSH tunnel), map hostnames to the server IP.

```bash
sudo nano /etc/hosts
```

Add lines (use your server’s LAN or `127.0.0.1` if browser runs on same box):

```
127.0.0.1   gositeme.com www.gositeme.com
127.0.0.1   lavocat.quebec www.lavocat.quebec
# ... one line per server_name you configured in nginx
```

---

## Part F — Cron

```bash
sudo crontab -u gositeme -e
```

Paste contents of `crontab-*.txt` from the backup. Fix **paths** inside cron lines if they referenced `/home/gositeme/...` on the old host — they may still be valid if you kept the same layout.

---

## Part G — PM2 (if `pm2-list-*.txt` existed)

```bash
sudo npm install -g pm2
# log in as gositeme, reinstall apps from project docs — PM2 list is only a hint
```

Recreate `pm2 start` / ecosystem file from your **project documentation**; the backup does not include full PM2 dump JSON.

---

## Part H — HTTPS (after HTTP works)

- **Public internet:** use **Certbot** + Let’s Encrypt for real domains.
- **Lab / mesh only:** use **mkcert** on your laptop, or a single self-signed cert for testing.

```bash
sudo apt install -y certbot python3-certbot-nginx
# sudo certbot --nginx -d gositeme.com -d www.gositeme.com
```

---

## Part I — Verification checklist (do not skip)

1. [ ] `sudo systemctl status mariadb php8.3-fpm nginx` — all active  
2. [ ] `mysql -u gositeme_whmcs -p -e 'USE gositeme_whmcs; SHOW TABLES;'` — tables present  
3. [ ] `curl -I http://127.0.0.1/ -H 'Host: gositeme.com'` — HTTP 200 or 302, not 502  
4. [ ] Browser with `/etc/hosts` — main site loads  
5. [ ] Login / admin — if fails, check `.env` DB URL and `APP_URL`  
6. [ ] Cron: `grep CRON /var/log/syslog` next day or run one job manually  

---

## Part J — Troubleshooting

| Symptom | Likely fix |
|---------|------------|
| 502 Bad Gateway | `php8.3-fpm` down; wrong socket path in nginx; `nginx -t` |
| 403 / permission denied | `chmod`/`chown` on `/home/gositeme`; nginx needs `x` on all parent dirs |
| Blank PHP page | `tail -f /var/log/nginx/error.log` and `php8.3-fpm` log |
| DB connection refused | MariaDB running; user host `localhost`; password matches `.env` |
| Import too large | `max_allowed_packet` in MariaDB config; restart DB |

---

## Part K — Mesh / offsite discipline

1. Run **`ecosystem-full-backup.sh`** on a schedule (quiet hours).  
2. Copy **`ecosystem-export-*`** to **two** places outside OVH; **encrypt** (age/gpg) if it contains production `.env`.  
3. Once a year: **restore on a scratch Ubuntu VM** using **this guide** to prove the bundle works.  
4. Keep **this file** and **`ECOSYSTEM-RESTORE.md`** in the same backup or in git so you always have instructions.

---

## Quick command summary (order of operations)

```text
1. apt update / upgrade / base packages
2. adduser gositeme + mkdir /home/gositeme/domains
3. install mariadb-server → tune config → restart
4. CREATE each DATABASE + USER (see MANIFEST-databases.json) → import each databases/*.sql.gz
5. install php8.3-fpm + extensions (+ ioncube if needed)
6. install nginx
7. tar -xzf all-domains-*.tar.gz -C /home/gositeme
8. chown gositeme:gositeme; usermod -aG www-data gositeme
9. edit .env files (DB password, URLs)
10. composer install / npm ci where needed
11. nginx site configs per domain → nginx -t → reload
12. /etc/hosts for local names
13. crontab -u gositeme
14. certbot when ready
```

**You are not “missing” the app** if you have the ecosystem export + this guide — only DNS/mail/certs/panel must be handled outside the tarball.
