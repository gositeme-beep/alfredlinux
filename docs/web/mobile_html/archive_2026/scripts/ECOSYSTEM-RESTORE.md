# GoSiteMe ecosystem — restore locally (disaster / OVH / mesh replica)

**→ Full step-by-step Ubuntu install + nginx + MariaDB + import + `.env` + cron:**  
**[`UBUNTU-COMPLETE-RESTORE-GUIDE.md`](UBUNTU-COMPLETE-RESTORE-GUIDE.md)** — use this if you won’t remember how to set things up.

**→ Afraid of missing data outside `domains/` or want `node_modules` + `.git` too:**  
**[`PARANOID-BACKUP.md`](PARANOID-BACKUP.md)** — run with `PARANOID_FULL_HOME=1` (and optionally `ECOSYSTEM_INCLUDE_GIT=1`).

**→ Multiple MySQL databases (Sound Studio Pro, WordPress, etc.):**  
**[`ORGANIZED-DATABASES.md`](ORGANIZED-DATABASES.md)** — backups now dump **every** DB found under `domains/`.

This document matches **`ecosystem-full-backup.sh`**, which produces:

| Artifact | Purpose |
|----------|---------|
| `databases/*.sql.gz` | **Every MySQL database** found under `domains/` (`.env`, `database.env.php`, `wp-config.php`). |
| `databases/MANIFEST-databases.json` | Which DB name came from which path (no passwords). |
| `gositeme-db-*.sql.gz` | Symlink to **`databases/gositeme_whmcs.sql.gz`** when present (WHMCS/main app DB, chunked dump). |
| `all-domains-*.tar.gz` | All sites under **`~/domains/`** (every `public_html`, shared assets, etc.). |
| `SYSTEM-*.txt` | Hostname, kernel, `mysql`/`php` versions — match or approximate when rebuilding. |
| `crontab-*.txt` | Your user crontab — re-install after restore. |
| `domain-directories-*.txt` | List of domain folders for a quick sanity check. |

**Important:** On this server, the MySQL user used for backups typically only has **one** logical database (`gositeme_whmcs`). Multi-site data for the ecosystem is expected to live **there** plus **files under `domains/`**. If you later add separate databases per site, extend the backup script to dump those too (needs credentials).

---

## What’s excluded from `all-domains-*.tar.gz` (and what you’ll need to recreate)

The backup only archives **`~/domains/`**. Inside that tree, these are **excluded** so the tarball stays smaller and doesn’t fail on unreadable/volatile files:

| Excluded | Why | When restoring locally |
|----------|-----|-------------------------|
| `node_modules/` | Huge, reproducible with `npm install` | Run `npm install` (and `npm run build` if the app has a build step) in each project that needs it. |
| `.next/`, `.nuxt/` | Build output | Run the app’s build command (e.g. `npm run build`). |
| `.cache/`, `__pycache__/`, `.npm/`, `.venv/`, `venv/` | Caches and virtualenvs | Recreate: `python -m venv venv && pip install -r requirements.txt` (or equivalent). |
| `*.log`, `*.log.*` | Log files | Not needed for app code; can be ignored or recreated empty. |
| `*/.env.php.bak*`, `*.bak.*`, `*.bak`, `*.swp`, `*~` | Backup/swap copies of configs | Use the real `.env` / config files that **are** in the archive. Only custom edits that existed only in a `.bak` file would be missing (rare). |
| `.git/` (default) | Repo history | **Included** if you run with `ECOSYSTEM_INCLUDE_GIT=1` (larger archive). Otherwise you get the working tree only; fine for “run the app,” not for full git history. |

**Included (not excluded):** All site code, `public_html/`, `.env` (live config), `vendor/` (PHP), static assets, uploads — everything needed to run the stack. You are **not** missing app logic or config; you are missing only **regeneratable** build artifacts and **optional** git history.

---

## What this does *not* replace (outside the backup entirely)

- **DNS** at your registrar — export zone files or screenshot records.
- **Email** (inboxes, MX) — back up mail separately if you rely on host mail.
- **TLS certificates** — on a new machine use Let’s Encrypt / internal CA / `mkcert` for local dev.
- **OVH-specific** firewall, Load Balancer, Object Storage — document separately.
- **Secrets** — `.env` files are **inside** `all-domains-*.tar.gz`; store archives **encrypted at rest** (e.g. age, gpg) when off-server.

---

## Local recreation (developer machine)

### 1. Prerequisites

- **MariaDB or MySQL** (version close to `SYSTEM-*.txt`).
- **PHP** with extensions your apps need (see each project’s `composer.json` / docs).
- **Node.js** only for sites that build frontends (optional if you only serve built assets).

### 2. Restore database

```bash
# create empty database + user (adjust names/passwords)
mysql -u root -p -e "CREATE DATABASE gositeme_whmcs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p -e "CREATE USER 'gositeme_whmcs'@'localhost' IDENTIFIED BY 'your_secret';"
mysql -u root -p -e "GRANT ALL ON gositeme_whmcs.* TO 'gositeme_whmcs'@'localhost'; FLUSH PRIVILEGES;"

# import
gunzip -c gositeme-db-YYYYMMDD-HHMMSS.sql.gz | mysql -u gositeme_whmcs -p gositeme_whmcs
```

### 3. Restore files

Layout on Linux should mirror production for fewer surprises:

```bash
sudo mkdir -p /home/gositeme/domains
sudo tar -xzf all-domains-YYYYMMDD-HHMMSS.tar.gz -C /home/gositeme
sudo chown -R "$USER:$USER" /home/gositeme/domains   # or your deploy user
```

On macOS, use a Linux VM or Docker for path fidelity (`/home/gositeme/...`).

### 4. Configuration

- Copy or edit **`.env`** under each site’s `public_html` (paths, `APP_URL`, DB host/user/pass).
- Point **`/etc/hosts`** (or local DNS) for each domain to `127.0.0.1` while testing.
- Configure **nginx/Apache** vhosts to match each `domains/<name>/public_html`.

### 5. Cron & workers

- Re-apply **`crontab-*.txt`** for the deploy user.
- Restore **PM2** / systemd units if you use them (see `pm2-list-*.txt` if present).

---

## Mesh / “future network” use

Treat each **`ecosystem-export-*`** directory as an **immutable snapshot** of the stack:

1. **Schedule** `ecosystem-full-backup.sh` on a quiet window (large `all-domains` tarball).
2. **Replicate** the output to **two** off-OVH locations (another cloud, NAS, encrypted object storage).
3. **Test restore** yearly: import DB + extract `domains/` on a spare VM to prove the archive is good.
4. **Version your scripts** (`scripts/`, `lib/`) in git so the restore procedure stays accurate.

This gives you a **portable bundle** to stand up the same PHP/MySQL + multi-domain file tree anywhere the mesh lands—OVH is just one runtime.

---

## Quick verification checklist

- [ ] `mysql` client can import without errors (watch for `max_allowed_packet` if import fails — raise server limit).
- [ ] At least one domain serves HTTP 200 locally.
- [ ] Login flows that depend on **Redis**, **queues**, or **S3** have those services configured or stubbed in `.env`.

---

## Related scripts

- **`full-sftp-export.sh`** — smaller/faster: one DB + **only** `gositeme.com/public_html`.
- **`lib/backup-mysql-core.sh`** — shared mysqldump logic (chunked huge tables).
