GoSiteMe — SFTP full export
===========================

Output directory on server:
  ~/backups/sftp-export/   ( /home/gositeme/backups/sftp-export/ )

SFTP: cd backups/sftp-export and download .sql.gz + .tar.gz

Time:
  • BACKUP_MODE=standard (DB + public_html): often ~20–90 min
  • BACKUP_MODE=extended (large $HOME tree): often many hours

Run:
  cd /home/gositeme/domains/gositeme.com/public_html
  nohup env BACKUP_MODE=standard bash scripts/full-sftp-export.sh >/dev/null 2>&1 &
  tail -f ~/backups/sftp-export/export-*.log

Huge tables (e.g. alfred_agent_registry, crawler_pages_v2) are dumped in id
chunks (see MYSQLDUMP_CHUNK_SPECS in full-sftp-export.sh). If Error 1317 persists,
lower the chunk size for that table in the env var and re-run.

Full ecosystem (ALL domains + DB + metadata) — disaster / mesh replica
======================================================================
See scripts/ECOSYSTEM-RESTORE.md

  cd /home/gositeme/domains/gositeme.com/public_html
  nohup bash scripts/ecosystem-full-backup.sh >>~/backups/ecosystem-nohup.log 2>&1 &

Output: ~/backups/ecosystem-export-YYYYMMDD-HHMMSS/
  • gositeme-db-*.sql.gz
  • all-domains-*.tar.gz  (entire ~/domains/)
  • SYSTEM-*.txt, crontab-*.txt, domain list
