# Organized multi-database backups

One MySQL user (`gositeme_whmcs`) cannot dump **every** database on the account. Each site may use its **own** DB user (e.g. Sound Studio Pro → `gositeme_soundstudiopro`).

## What runs automatically

**`organized-mysql-backup.php`** is called by:

- `ecosystem-full-backup.sh`
- `full-sftp-export.sh`

It **scans** `/home/gositeme/domains/*/public_html` (and `private_html`) for:

| File | Parsed keys |
|------|-------------|
| `.env`, `.env.local`, `.env.production` | `GOSITEME_DB_*`, `DB_HOST`, `DB_NAME` / `DB_DATABASE`, `DB_USER` / `DB_USERNAME`, `DB_PASS` / `DB_PASSWORD` |
| `config/database.env.php`, `wp-config.php` | `define('DB_HOST'…)`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` |

Each **unique** `(host, database, user, password)` is dumped **once**.

## Output layout

Inside your backup folder:

```
databases/
  MANIFEST-databases.json    ← list of DB names + which files they came from (no passwords)
  gositeme_whmcs.sql.gz      ← chunked dump (huge tables)
  gositeme_soundstudiopro.sql.gz
  …
```

`gositeme-db-*.sql.gz` at the top level is a **symlink** to `databases/gositeme_whmcs.sql.gz` when that dump exists.

## Adding a new database later

Put credentials where the scanner looks:

- Add a `.env` in that site’s `public_html`, **or**
- Use `public_html/config/database.env.php` with `define('DB_*'…)` like Sound Studio Pro.

Then the next backup picks it up automatically.

## What is still skipped

- **SQLite** / `DATABASE_URL=file:…` (e.g. Prisma dev DB) — not MySQL; not dumped.
- **Databases with no discoverable config** under `domains/` — add a stub `.env` or dump manually once.
- **System databases** (`mysql`, etc.) — never dumped.

## Restore

On the new server, create each MySQL user/database, then:

```bash
for f in databases/*.sql.gz; do
  echo "Importing $f — set DB name inside file or from MANIFEST"
done
```

See **`UBUNTU-COMPLETE-RESTORE-GUIDE.md`** for creating users and importing each `.sql.gz` to the matching database name from `MANIFEST-databases.json`.
