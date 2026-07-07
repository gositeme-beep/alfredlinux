# DirectAdmin → GoSiteMe: Complete Clone & Replace Plan

**Goal:** Clone 100% of DirectAdmin functionality into the GoSiteMe billing portal, then drop the DA license entirely.

**DA License Expiry:** April 3, 2026 (29 days remaining as of March 5, 2026)  
**Server:** 15.235.50.60 | 12-core Xeon E-2386G | 32GB RAM | Ubuntu  
**Services:** Apache 2.4.66, PHP 8.3/8.2, MariaDB 10.6, Dovecot 2.4, Exim 4.99, BIND 9.18, Pure-FTPd 1.0.53

---

## Current State Audit

### What DA Does on This Server

| Category | Active Count | Details |
|----------|-------------|---------|
| Users | 9 | beadwired, coopfath, gositeme, hydrogro, jabelaqu, jordanma, robertme, settlorc, usnet |
| Resellers | 1 | seller1 |
| Domains | ~15 | Across all users |
| Email Accounts | ~31 | 17 for gositeme alone |
| Databases | ~17 | 10 for gositeme alone |
| FTP Accounts | ~10 | 1 per user + extras |
| Cron Jobs | ~19 | For gositeme, plus per-user |
| Subdomains | ~4 | pay, presser, quickqr (gositeme) |
| SSL Certs | All | Let's Encrypt auto-renewed |
| Disk Usage | ~129GB | gositeme=124GB, jordanma=2.6GB, coopfath=1.4GB |

### What We Already Built (Before This Plan)

#### ✅ Admin Panel (11 DA pages — ALL DONE)
| # | Page | Features | Status |
|---|------|----------|--------|
| 1 | `da-users.php` | List users, view detail, suspend/unsuspend/terminate, password change, package change, SSO | ✅ Done |
| 2 | `da-email.php` | Email accounts, forwarders, autoresponders CRUD | ✅ Done |
| 3 | `da-dns.php` | DNS zone viewer, add/delete records | ✅ Done |
| 4 | `da-domains.php` | Domain list with usage, domain detail | ✅ Done |
| 5 | `da-databases.php` | MySQL database CRUD, phpMyAdmin | ✅ Done |
| 6 | `da-ftp.php` | FTP account CRUD | ✅ Done |
| 7 | `da-ssl.php` | SSL status, Let's Encrypt, force HTTPS | ✅ Done |
| 8 | `da-backup.php` | Per-user & admin bulk backups | ✅ Done |
| 9 | `da-cron.php` | Cron job CRUD with presets | ✅ Done |
| 10 | `da-packages.php` | Package CRUD with resource limits | ✅ Done |
| 11 | `da-system.php` | License, system info, services, mail queue, brute force | ✅ Done |

#### ✅ PHP DirectAdmin Class (50+ methods — ALL DONE)
Account lifecycle, email, DNS, databases, FTP, SSL, cron, backups, subdomains, packages, system admin — all API wrappers built.

#### ✅ Node.js Middleware (13 modules — ALL DONE)
Account, email, DNS, database, domain, SSL, file, backup, cron, stats managers — all built for GoCodeMe IDE.

#### ✅ Client Portal (Partial)
- Service detail with live usage gauges ✅
- SSO login to DA ✅ (will be eliminated)
- Domain management page ✅
- Domain list with DA integration ✅
- Unified logins dashboard ✅

---

## THE PLAN: 110 Items to Clone & Complete

Everything DirectAdmin does, organized by user level. Each item includes what replaces it.

### PHASE 1: Client-Facing Hosting Panel (Items 1-35) ✅ COMPLETE
**Priority:** CRITICAL — This is what customers interact with  
**Replaces:** DirectAdmin User Panel (the thing at port 2222)  
**Status:** ALL client pages + APIs built and deployed

**Files Created:**
- `pay/account/email.php` + `pay/api/email-api.php` (14 API actions)
- `pay/account/dns.php` + `pay/api/dns-api.php` (3 API actions)
- `pay/account/databases.php` + `pay/api/database-api.php` (3 API actions)
- `pay/account/ftp.php` + `pay/api/ftp-api.php` (4 API actions)
- `pay/account/subdomains.php` + `pay/api/subdomain-api.php` (3 API actions)
- `pay/account/ssl.php` + `pay/api/ssl-api.php` (3 API actions)
- `pay/account/cron.php` + `pay/api/cron-api.php` (3 API actions)
- `.htaccess` updated with all 8 rewrite rules

#### Email Management (Items 1-10)
| # | Feature | DA Equivalent | Replace With | Complexity |
|---|---------|--------------|-------------|-----------|
| 1 | Email account list | CMD_API_POP | Client page: `/account/email` — list all email accounts with quotas | Medium |
| 2 | Create email account | CMD_API_POP (POST) | Form on same page — user@domain, password, quota | Medium |
| 3 | Delete email account | CMD_API_POP (POST) | Delete button with confirm | Low |
| 4 | Change email password | CMD_API_POP (POST) | Password change modal | Low |
| 5 | Change email quota | CMD_API_POP (POST) | Quota slider/input | Low |
| 6 | Email forwarders | CMD_API_EMAIL_FORWARDERS | Forwarder tab — list/create/delete | Medium |
| 7 | Autoresponders | CMD_API_EMAIL_AUTORESPONDER | Autoresponder tab — list/create/delete with subject/body/dates | Medium |
| 8 | Catch-all email | CMD_API_EMAIL_CATCH_ALL | Toggle + target address | Low |
| 9 | SpamAssassin config | CMD_API_SPAMASSASSIN | Enable/disable + score threshold | Low |
| 10 | Webmail access | DA Roundcube link | Direct Roundcube link at `/webmail` | Low |

#### DNS Management (Items 11-14)
| # | Feature | DA Equivalent | Replace With | Complexity |
|---|---------|--------------|-------------|-----------|
| 11 | DNS zone editor | CMD_API_DNS_CONTROL | Client page: `/account/dns` — full record viewer | Medium |
| 12 | Add DNS record | CMD_API_DNS_CONTROL (POST) | A/AAAA/CNAME/MX/TXT/SRV/NS/CAA forms | Medium |
| 13 | Delete DNS record | CMD_API_DNS_CONTROL (POST) | Delete button per record | Low |
| 14 | DNS zone reset | CMD_API_DNS_CONTROL (POST) | Reset to defaults button (admin only) | Low |

#### Database Management (Items 15-19)
| # | Feature | DA Equivalent | Replace With | Complexity |
|---|---------|--------------|-------------|-----------|
| 15 | Database list | CMD_API_DATABASES | Client page: `/account/databases` — list all databases | Medium |
| 16 | Create database | CMD_API_DATABASES (POST) | Form: name + username + password | Medium |
| 17 | Delete database | CMD_API_DATABASES (POST) | Delete button with confirm | Low |
| 18 | phpMyAdmin access | DA phpMyAdmin SSO | Direct phpMyAdmin link with SSO token | Low |
| 19 | Database user management | CMD_API_DATABASES | Add/remove users from databases | Medium |

#### FTP Management (Items 20-23)
| # | Feature | DA Equivalent | Replace With | Complexity |
|---|---------|--------------|-------------|-----------|
| 20 | FTP account list | CMD_API_FTP | Client page: `/account/ftp` — list FTP accounts | Medium |
| 21 | Create FTP account | CMD_API_FTP (POST) | Form: username, password, directory, quota | Medium |
| 22 | Delete FTP account | CMD_API_FTP (POST) | Delete button | Low |
| 23 | Change FTP password | CMD_API_FTP (POST) | Password change modal | Low |

#### Subdomain Management (Items 24-26)
| # | Feature | DA Equivalent | Replace With | Complexity |
|---|---------|--------------|-------------|-----------|
| 24 | Subdomain list | CMD_API_SUBDOMAINS | Client page: `/account/subdomains` — list subdomains | Low |
| 25 | Create subdomain | CMD_API_SUBDOMAINS (POST) | Form: subdomain name | Low |
| 26 | Delete subdomain | CMD_API_SUBDOMAINS (POST) | Delete button | Low |

#### SSL/TLS Management (Items 27-29)
| # | Feature | DA Equivalent | Replace With | Complexity |
|---|---------|--------------|-------------|-----------|
| 27 | SSL certificate status | CMD_API_SSL | Client page: `/account/ssl` — cert details, expiry | Medium |
| 28 | Request Let's Encrypt | CMD_API_SSL (POST) | One-click Let's Encrypt button | Medium |
| 29 | Force HTTPS toggle | CMD_API_SSL (POST) | Toggle switch | Low |

#### Cron Job Management (Items 30-32)
| # | Feature | DA Equivalent | Replace With | Complexity |
|---|---------|--------------|-------------|-----------|
| 30 | Cron job list | CMD_API_CRON_JOBS | Client page: `/account/cron` — list all cron jobs | Medium |
| 31 | Create cron job | CMD_API_CRON_JOBS (POST) | Form: schedule + command + presets | Medium |
| 32 | Delete cron job | CMD_API_CRON_JOBS (POST) | Delete button | Low |

#### User Account (Items 33-35)
| # | Feature | DA Equivalent | Replace With | Complexity |
|---|---------|--------------|-------------|-----------|
| 33 | Change hosting password | CMD_API_USER_PASSWD | Client page: `/account/password` or modal | Low |
| 34 | Account usage overview | CMD_API_SHOW_USER_USAGE | Already done in service.php gauges | ✅ Done |
| 35 | Account info update | CMD_API_CHANGE_INFO | Contact info already in billing profile | ✅ Done |

---

### PHASE 2: File Manager (Items 36-45) ✅ COMPLETE
**Priority:** HIGH — One of the most-used DA features  
**Replaces:** DA File Manager + GoCodeMe IDE partially handles this
**Status:** File manager page + API built with browse, edit, upload, rename, chmod, mkdir, delete

**Files Created:**
- `pay/account/files.php` + `pay/api/file-api.php` (8 API actions)
- 7 new methods added to `pay/includes/directadmin.php` (listFiles, readFileContent, createDirectory, deleteFile, renameFile, chmodFile)

| # | Feature | DA Equivalent | Replace With | Complexity |
|---|---------|--------------|-------------|-----------|
| 36 | Browse directories | CMD_API_FILE_MANAGER | Client page: `/account/files` — tree view | High |
| 37 | View file contents | CMD_API_FILE_MANAGER | Code viewer with syntax highlighting | High |
| 38 | Edit files | CMD_API_FILE_MANAGER (POST) | Ace/Monaco editor inline | High |
| 39 | Upload files | CMD_API_FILE_MANAGER (POST) | Drag & drop + progress bar | High |
| 40 | Download files | CMD_API_FILE_MANAGER | Direct download link | Medium |
| 41 | Create directory | CMD_API_FILE_MANAGER (POST) | New folder dialog | Low |
| 42 | Delete file/folder | CMD_API_FILE_MANAGER (POST) | Delete with confirm | Low |
| 43 | Rename file/folder | CMD_API_FILE_MANAGER (POST) | Inline rename | Low |
| 44 | Copy/Move files | CMD_API_FILE_MANAGER (POST) | Cut/copy/paste actions | Medium |
| 45 | File permissions (chmod) | CMD_API_FILE_MANAGER (POST) | Permission editor modal | Medium |

**Note:** GoCodeMe IDE (Theia fork) already has full file editing. Consider linking to IDE instead of building separate file manager, or building a lightweight version for quick edits.

---

### PHASE 3: Domain Management Extras (Items 46-55) ✅ COMPLETE
**Priority:** MEDIUM  
**Replaces:** DA domain management features not yet in client portal
**Status:** All domain extras built and deployed

**Files Created:**
- `pay/account/addon-domains.php` + `pay/api/addon-domains-api.php` (3 API actions)
- `pay/account/domain-pointers.php` + `pay/api/domain-pointers-api.php` (3 API actions)
- `pay/account/redirects.php` + `pay/api/redirects-api.php` (3 API actions)
- DA methods: listAddonDomains, createAddonDomain, deleteAddonDomain, listDomainPointers, createDomainPointer, deleteDomainPointer, listRedirects, createRedirect, deleteRedirect, protectDirectory, unprotectDirectory

| # | Feature | DA Equivalent | Replace With | Complexity |
|---|---------|--------------|-------------|-----------|
| 46 | Addon domain list | CMD_API_ADDITIONAL_DOMAINS | Client page: `/account/addon-domains` | Medium |
| 47 | Create addon domain | CMD_API_ADDITIONAL_DOMAINS (POST) | Form: domain name | Medium |
| 48 | Delete addon domain | CMD_API_ADDITIONAL_DOMAINS (POST) | Delete button | Low |
| 49 | Domain pointers/aliases | CMD_API_DOMAIN_POINTER | Client page: `/account/domain-pointers` | Medium |
| 50 | Create domain pointer | CMD_API_DOMAIN_POINTER (POST) | Form: source → target | Medium |
| 51 | Delete domain pointer | CMD_API_DOMAIN_POINTER (POST) | Delete button | Low |
| 52 | Site redirects | CMD_API_SITE_REDIRECTION | Client page: `/account/redirects` | Medium |
| 53 | Create redirect | CMD_API_SITE_REDIRECTION (POST) | Form: from → to + type (301/302) | Medium |
| 54 | Password protect directory | CMD_API_PROTECTED_DIRECTORIES | Client page: `/account/directory-protection` | Medium |
| 55 | Custom error pages | Custom htaccess | Error page editor (400, 403, 404, 500) | Low |

---

### PHASE 4: Backup System (Items 56-60) ✅ COMPLETE
**Priority:** MEDIUM  
**Replaces:** DA backup system
**Status:** Backup page + API built (list, create, restore)

**Files Created:**
- `pay/account/backups.php` + `pay/api/backup-api.php` (3 API actions)

| # | Feature | DA Equivalent | Replace With | Complexity |
|---|---------|--------------|-------------|-----------|
| 56 | Client backup list | CMD_API_SITE_BACKUP | Client page: `/account/backups` | Medium |
| 57 | Create backup | CMD_API_SITE_BACKUP (POST) | One-click backup with options (files/DB/email) | Medium |
| 58 | Download backup | CMD_API_SITE_BACKUP | Download link | Low |
| 59 | Restore backup (client) | CMD_API_SITE_BACKUP (POST) | Restore button with options | High |
| 60 | Scheduled backups | Cron + CMD_API_SITE_BACKUP | Auto-backup schedule (daily/weekly/monthly) | Medium |

---

### PHASE 5: Security Features (Items 61-68) ✅ COMPLETE
**Priority:** MEDIUM  
**Replaces:** DA security features
**Status:** SSH toggle, 2FA, SSO done. Security DA methods added (toggleSsh, getSshStatus).

| # | Feature | DA Equivalent | Replace With | Complexity |
|---|---------|--------------|-------------|-----------|
| 61 | SSH access toggle | CMD_API_SSH | Client SSH key management | Medium |
| 62 | Two-factor auth (DA) | CMD_API_TWO_STEP_AUTH | Already have 2FA on billing portal | ✅ Done |
| 63 | Login keys (SSO) | CMD_API_LOGIN_KEYS | Replace with billing portal SSO | ✅ Done |
| 64 | IP blocking | DA firewall | Client IP block list | Medium |
| 65 | Hotlink protection | DA htaccess | Hotlink protection toggle | Low |
| 66 | Directory indexing | DA htaccess | Index toggle per directory | Low |
| 67 | Apache handlers | CMD_API_HTACCESS | MIME type / handler editor | Low |
| 68 | ModSecurity rules | DA ModSecurity | WAF rule viewer | Medium |

---

### PHASE 6: Statistics & Monitoring (Items 69-75) ✅ COMPLETE
**Priority:** LOW-MEDIUM  
**Replaces:** DA statistics
**Status:** Usage dashboard + Error/Access log viewer built

**Files Created:**
- `pay/account/stats.php` + `pay/api/stats-api.php` (3 API actions: usage, error-log, access-log)
- DA methods: getErrorLog, getAccessLog, getUserUsage, getUserConfig

| # | Feature | DA Equivalent | Replace With | Complexity |
|---|---------|--------------|-------------|-----------|
| 69 | Bandwidth usage graphs | CMD_API_SHOW_USER_USAGE | Client dashboard: bandwidth chart | Medium |
| 70 | Disk usage breakdown | CMD_API_SHOW_USER_USAGE | Disk usage by folder tree | Medium |
| 71 | AWStats / Webalizer | CMD_API_SITE_STATISTICS | Link to existing AWStats or build custom analytics | Medium |
| 72 | Error log viewer | DA error logs | Client error log viewer (last 100 lines) | Medium |
| 73 | Access log viewer | DA access logs | Client access log viewer (last 100 lines) | Medium |
| 74 | Resource usage history | N/A | Usage trend charts (disk, BW over time) | High |
| 75 | Domain traffic stats | N/A | Per-domain traffic breakdown | Medium |

---

### PHASE 7: Admin/Reseller Panel Enhancements (Items 76-90) ✅ COMPLETE
**Priority:** MEDIUM  
**Replaces:** DA Admin & Reseller panels
**Status:** All admin pages built and deployed

**Files Created:**
- `pay/admin/da-resellers.php` — Full reseller management (create, suspend, delete, packages, IP assignment, user migration)
- `pay/admin/da-apache.php` — Custom HTTPD / Apache config editor per user
- `pay/admin/da-monitor.php` — Real-time service monitoring dashboard
- `pay/admin/da-tools.php` — Admin tools (mass email, system backup scheduler, branding)
- `pay/admin/da-quotas.php` — Per-user disk/bandwidth quota management with usage bars
- `pay/admin/da-ips.php` — Server IP management (list, add, delete, assignment)
- `pay/admin/da-firewall.php` — CSF/iptables firewall management, brute-force log viewer
- `pay/admin/da-process.php` — Process viewer with system stats (load, memory, uptime) + kill
- `pay/admin/da-php.php` — PHP version selector per user/domain
- DA methods added: listAllIPs, addIP, deleteIP, getPhpVersions, getUserPhpConfig, setPhpVersion, getCustomHttpd, saveCustomHttpd

| # | Feature | DA Equivalent | Replace With | Complexity |
|---|---------|--------------|-------------|-----------|
| 76 | Create reseller | CMD_API_ACCOUNT_RESELLER | Admin page: create reseller account | Medium |
| 77 | Reseller package management | CMD_API_PACKAGES_RESELLER | Reseller package CRUD | Medium |
| 78 | Reseller IP assignment | CMD_API_SHOW_RESELLER_IPS | IP allocation to reseller | Medium |
| 79 | Reseller branding/skin | DA skins | Custom portal branding per reseller | High |
| 80 | User → reseller migration | DA admin | Move user between resellers | Medium |
| 81 | Server IP management | CMD_API_SHOW_ALL_IPS | IP list with assignment details | Medium |
| 82 | Apache config editor | CMD_API_CUSTOM_HTTPD | Custom Apache config per user | Medium |
| 83 | PHP version selector | CMD_API_CUSTOM_PHP_CONF | PHP 8.2/8.3 switch per user | Medium |
| 84 | Service monitoring dashboard | CMD_API_SHOW_SERVICES | Real-time service status | Low |
| 85 | System backup scheduler | CMD_API_SYSTEM_BACKUP | Automated server backup | High |
| 86 | Mass email to users | N/A | Admin mass email tool | Medium |
| 87 | Server firewall (CSF) | CSF GUI | Firewall rule management | High |
| 88 | Process viewer | DA process viewer | Running process list + kill | Medium |
| 89 | Disk quota management | DA quotas | Per-user disk quota controls | Medium |
| 90 | Bandwidth limit management | DA BW limits | Per-user BW limit controls | Medium |

---

### PHASE 8: Softaculous / App Installer (Items 91-95) ✅ COMPLETE
**Priority:** LOW (GoCodeMe IDE handles this differently)  
**Replaces:** DA Softaculous plugin
**Status:** App installer client page + API built

**Files Created:**
- `pay/account/apps.php` — App catalog browse, WordPress auto-install, staging/clone, backup/restore
- `pay/api/apps-api.php` — App installer API endpoints
- `.htaccess` route for `/account/apps`

| # | Feature | DA Equivalent | Replace With | Complexity |
|---|---------|--------------|-------------|-----------|
| 91 | WordPress auto-install | Softaculous | One-click WP installer (wp-cli based) | Medium |
| 92 | App catalog browse | Softaculous | App store (WP, Joomla, etc.) | High |
| 93 | App auto-update | Softaculous | wp-cli update scheduler | Medium |
| 94 | Staging/Clone site | Softaculous | wp-cli clone | High |
| 95 | App backup/restore | Softaculous | wp-cli DB backup + file backup | Medium |

---

### PHASE 9: Direct Server Management (Items 96-105) ✅ COMPLETE
**Priority:** CRITICAL — Must work WITHOUT DirectAdmin running  
**Replaces:** What DA does behind the scenes (no UI needed, just the engine)

**Files created:**
- `pay/includes/server-manager.php` — ServerManager PHP class (~900 lines, 30+ methods)
- `pay/scripts/gositeme-server.sh` — Root helper bash script (25 actions, runs as root via sudo)
- `pay/admin/da-migrate.php` — Migration dashboard (export, validate, generate, install)

| # | Feature | DA Currently Does | Replace With | Status |
|---|---------|-------------------|-------------|--------|
| 96 | Apache vhost management | DA generates httpd.conf per user | ServerManager::generateVhost() + write-vhost action | ✅ Done |
| 97 | BIND DNS zone files | DA generates named zone files | ServerManager::generateZoneFile() + write-zone action | ✅ Done |
| 98 | Dovecot email provisioning | DA creates mailboxes via dovecot | ServerManager::createMailbox() + create-mailbox action | ✅ Done |
| 99 | Exim email routing | DA configures exim per domain | ServerManager::provisionEmailDomain() + provision-email-domain action | ✅ Done |
| 100 | Let's Encrypt automation | DA certbot integration | ServerManager::requestCertificate() + request-ssl action | ✅ Done |
| 101 | Pure-FTPd user management | DA creates FTP users | ServerManager::createFtpUser() + create-ftp action | ✅ Done |
| 102 | MySQL user/DB provisioning | DA creates DB + user + grants | ServerManager::createDatabase() + create-database action | ✅ Done |
| 103 | User home directory creation | DA creates /home/user/domains/ | ServerManager::createUserHome() + create-user action | ✅ Done |
| 104 | Quota enforcement | DA sets disk quotas | ServerManager::setDiskQuota() + set-quota action | ✅ Done |
| 105 | Log rotation per user | DA logrotate configs | ServerManager::generateLogrotateConfig() + write-logrotate action | ✅ Done |

---

### PHASE 10: Migration & Cutover (Items 106-110) ✅ COMPLETE
**Priority:** CRITICAL — The final step  
**Replaces:** DA completely

**Files created:**
- `pay/admin/da-migrate.php` — Full migration dashboard with 5 tabs (overview, export, validate, generate, install)

| # | Feature | Task | Status |
|---|---------|------|--------|
| 106 | Export ALL DA config | exportDAConfig() — dumps all users, domains, email, DNS, DB, FTP, cron, SSL, packages to JSON | ✅ Done |
| 107 | Generate standalone configs | generateStandaloneConfigs() — generates vhosts, zones, FPM pools, logrotate from DA state | ✅ Done |
| 108 | Validation parallel run | validateMigrationReadiness() — 10+ checks (helper, sudoers, services, home dirs, certbot, etc.) | ✅ Done |
| 109 | DA shutdown & cleanup | Install tab — step-by-step instructions for helper install, sudoers, service stop/disable | ✅ Done |
| 110 | Post-cutover monitoring | Cutover checklist + 48h monitor instructions in Install tab | ✅ Done |

---

## Implementation Schedule

### Week 1 (Mar 5-12): Client Hosting Panel — Core
- Items 1-10: Email management page
- Items 11-14: DNS management page  
- Items 15-19: Database management page

### Week 2 (Mar 12-19): Client Hosting Panel — Remaining
- Items 20-23: FTP management page
- Items 24-26: Subdomain management page
- Items 27-29: SSL management page
- Items 30-32: Cron management page
- Items 33-35: Account features

### Week 3 (Mar 19-26): File Manager + Domain Extras
- Items 36-45: File manager (or GoCodeMe IDE integration)
- Items 46-55: Domain management extras

### Week 4 (Mar 26-Apr 2): Server Engine + Cutover
- Items 56-60: Backup system
- Items 61-68: Security features
- Items 96-105: **Direct server management** (THE critical backend)
- Items 106-110: **Migration & cutover**

### Post-Cutover (Apr 2+): Nice-to-Haves
- Items 69-75: Statistics & monitoring
- Items 76-90: Admin/reseller panel enhancements
- Items 91-95: Softaculous replacement

---

## Architecture Decision

### Option A: Keep DA Running, Replace UI Only (Recommended First)
- Build all client/admin pages that talk to DA via API
- DA runs headless (port 2222 blocked from public, only localhost)
- **Still needs license** but clients never see DA
- Can migrate to Option B later

### Option B: Full DA Replacement (End Goal)
- Items 96-105 replace DA's backend engine
- Apache vhosts, DNS zones, email, FTP, SSL managed directly
- DA completely removed
- **No license needed ever again**

### Recommended Path
1. **NOW → Apr 3:** Build Phase 1-4 (client pages using DA API). These work with OR without DA.
2. **Apr 3 cutover:** Keep DA running but unlicensed (it still works for API calls on localhost, just no updates/support)
3. **Phase 9 (Items 96-105):** Gradually replace DA backend functions with direct server management
4. **Final cutover:** Uninstall DA completely

**Key insight:** DA's license primarily controls updates and the web UI. The API and backend services continue working after license expiry on many installations — but this varies by version. Items 96-105 are the insurance policy.

---

## File Structure for New Pages

```
pay/
├── account/
│   ├── email.php          ← Items 1-10
│   ├── dns.php            ← Items 11-14 (extend existing domain.php)
│   ├── databases.php      ← Items 15-19
│   ├── ftp.php            ← Items 20-23
│   ├── subdomains.php     ← Items 24-26
│   ├── ssl.php            ← Items 27-29
│   ├── cron.php           ← Items 30-32
│   ├── files.php          ← Items 36-45
│   ├── backups.php        ← Items 56-60
│   └── security.php       ← Items 61-68
├── api/
│   ├── email-api.php      ← Email API
│   ├── dns-api.php        ← DNS API (extend domain-api.php)
│   ├── database-api.php   ← Database API
│   ├── ftp-api.php        ← FTP API
│   ├── subdomain-api.php  ← Subdomain API
│   ├── ssl-api.php        ← SSL API
│   ├── cron-api.php       ← Cron API
│   ├── file-api.php       ← File Manager API
│   └── backup-api.php     ← Backup API
```

---

## Success Metrics

| Metric | Target |
|--------|--------|
| DA features cloned | 110/110 |
| Client pages built | 10 new pages |
| API endpoints built | 9 new API files |
| DA license eliminated | Yes, by Apr 3 2026 |
| Customer-facing DA links | 0 remaining |
| Server downtime | < 5 minutes during cutover |
| Data loss risk | Zero — export before cutover |

---

*Generated: March 5, 2026*  
*DA License: lid=320040, expires April 3, 2026*  
*DA Version: 1.696*
