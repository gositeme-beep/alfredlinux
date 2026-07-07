# Private Data Locations
*In the name of Yeshua, Jesus Christ of Bethlehem, King of the Universe.*

## Rule
**No partner audit logs, internal tokens, or operational state files
are stored under DocumentRoot.** Web-facing PHP reads/writes them via
absolute paths or symlinks pointing outside the public tree.

## Locations
- Partner logs: `/home/gositeme/private/alfredlinux/logs/`
  - `dell-download-log.txt` (symlinked into `public_html/downloads/`
    only so dell-partner.php can append; `.htaccess` denies HTTP reads).
- Future: any `*-state.json`, `*-audit*.txt`, `*.log` MUST go here.

## Defense in depth
1. File moved out of DocumentRoot.
2. Symlink + suexec lets server-side PHP still read/write.
3. `downloads/.htaccess` `<FilesMatch>` denies HTTP access.
4. Root `.htaccess` `<FilesMatch>` denies `.env|.log|.bak|.key|.pem|...`
   anywhere under DocumentRoot.

## How to verify
```
curl -sI https://alfredlinux.com/downloads/dell-download-log.txt
# expect: HTTP/1.1 403 Forbidden
```

## Audit trail
- p16 commit (this change): hardened web access; moved dell-download-log.txt
  to private dir, added global deny rules.
