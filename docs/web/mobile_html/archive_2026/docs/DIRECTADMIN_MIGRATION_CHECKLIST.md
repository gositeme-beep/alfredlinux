# DirectAdmin → GoHostMe migration checklist

Use this for **planned** migration off DirectAdmin UI toward **GoHostMe** + standard stack (Apache/CustomBuild, local scripts, panel APIs). Adjust per tenant.

## 0. Preconditions

- [ ] Inventory: domains, subdomains, SSL mode (AutoSSL/Let’s Encrypt), PHP versions per vhost
- [ ] Mail: Exim/Dovecot/Roundcube — list accounts, forwarders, DKIM/DMARC
- [ ] DNS: authoritative zones; note TTL before changes
- [ ] Backups: confirm **MySQL** + **files** retention and restore drill
- [ ] GoHostMe admin access and bridge (`/opt/gohostme/bridge.sh`) tested

## 1. Parallel run (no cutover)

- [ ] Document current DA-only workflows (FTP users, DB users, cron jobs)
- [ ] Map each workflow to GoHostMe or CLI (`scripts/`, panel)
- [ ] Run **Load & DB diagnostics** in Server Hardening; fix slow queries / capacity before traffic events

## 2. Data and services

- [ ] Databases: dump/restore naming; users/grants reviewed
- [ ] Files: `public_html`, outside-webroot secrets, `.env` patterns
- [ ] Cron: move to user crontab or system cron with documented paths
- [ ] SSL: verify cert coverage and HTTP→HTTPS redirects

## 3. Cutover day

- [ ] Lower DNS TTL 24–48h ahead (if applicable)
- [ ] Final backup immediately before switch
- [ ] Point DNS / update vhosts; smoke test: HTTP 200, login, API, mail send/receive
- [ ] Monitor load, MySQL threads, PHP-FPM pool, application logs

## 4. Post-cutover

- [ ] Decommission or freeze DA-only accounts after validation window
- [ ] Update internal runbooks and **OPS-COMPLETE-PACK** links
- [ ] Optional: hardening follow-up (sysctl, Fail2Ban jails, Meilisearch/Redis sizing)

## Rollback

- [ ] Keep previous DNS snapshot and last full backup location written in the ticket

---

*GoHostMe does not replicate every DA screen; it replaces **control-plane** and **narrative** with APIs + docs + scripts.*
