# “Don’t miss anything” backup (full home)

If the panel says **~700 GB** but `all-domains-*.tar.gz` is much smaller, that’s normal: the **domains** archive skips `node_modules`, `.git`, logs, caches, etc., and it **does not** include other folders under your user (extra backups, tools, mail dirs under home, etc.).

## Turn on the paranoid archive

Same script, one extra flag:

```bash
cd /home/gositeme/domains/gositeme.com/public_html
nohup env PARANOID_FULL_HOME=1 ECOSYSTEM_INCLUDE_GIT=1 bash scripts/ecosystem-full-backup.sh \
  >> ~/backups/ecosystem-nohup.log 2>&1 &
```

- **`PARANOID_FULL_HOME=1`** — creates **`home-gositeme-FULL-*.tar.gz`**: almost everything under **`/home/gositeme`**, including `node_modules`, `.git`, old tarballs under `backups/`, etc. **Only** the current **`OUT_DIR`** (the folder this run writes into) is excluded so tar doesn’t read the file it’s writing.
- **`ECOSYSTEM_INCLUDE_GIT=1`** — also puts **`.git`** inside **`all-domains-*.tar.gz`** (optional; makes that part much larger).

You still get **`gositeme-db-*.sql.gz`** and the smaller **`all-domains-*.tar.gz`** as before.

## Files you’ll see

| File | What |
|------|------|
| `home-gositeme-FULL-*.tar.gz` | Near-complete copy of `$HOME` (compressed). |
| `paranoid-tar-*.log` | Warnings / files **skipped** because the user couldn’t read them (e.g. root-owned). |

## Still not on the server (nothing in `$HOME` can fix this)

- DNS at registrar  
- OVH firewall / LB / object storage  
- Mail on the host (if outside your home tree)  
- MySQL data directory / other users’ DBs (we only dump **`gositeme_whmcs`** with your app credentials)  
- System TLS certs in `/etc`  

For those, use **OVH account backup** / **export mail** / **document DNS** separately.

## Restore hint

Extract **`home-gositeme-FULL-*.tar.gz`** as **`gositeme`** into `/home/gositeme` on the new machine **after** creating the user and **carefully** merging (or onto an empty home). Then use **`UBUNTU-COMPLETE-RESTORE-GUIDE.md`** for MariaDB, nginx, and `.env`.
