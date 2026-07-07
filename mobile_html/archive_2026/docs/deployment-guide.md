# GoSiteMe — Deployment Guide

> **Version:** 1.0 | **Last updated:** 2026-03-11

## Prerequisites

| Requirement | Version | Notes |
|-------------|---------|-------|
| PHP | 8.3+ | PHP-FPM |
| Node.js | 20+ | For PM2 services |
| MySQL | 8.0+ | Or MariaDB 10.5+ |
| Redis | 6+ | Sessions & caching |
| Caddy | 2.x | Reverse proxy, auto-HTTPS |
| PM2 | 5.x | Process manager for Node services |
| Composer | 2.x | PHP dependency management |

## Server Details

```
Host:     server-15-235-50-60
User:     gositeme
Root:     /home/gositeme/domains/gositeme.com/public_html/
Domain:   gositeme.com
```

## Deployment Methods

### 1. Standard Deploy (Recommended)

```bash
cd /home/gositeme/domains/gositeme.com/public_html
./scripts/deploy.sh
```

**What it does:**
1. Creates timestamped backup
2. Pulls latest code from git
3. Runs `composer install --no-dev`
4. Runs PHP syntax checks (`php -l`) on changed files
5. Runs test suite (`vendor/bin/phpunit`)
6. Restarts PM2 services
7. Verifies HTTP 200 on critical pages
8. Logs deployment result

**Options:**

```bash
./scripts/deploy.sh              # Full deploy (tests + validation)
./scripts/deploy.sh --quick      # Skip tests, fast deploy
./scripts/deploy.sh --rollback   # Rollback to previous version
./scripts/deploy.sh --status     # Check deployment status
```

### 2. Quick Deploy (Skip Tests)

```bash
./scripts/deploy.sh --quick
```

Use for urgent hotfixes only. Skips test suite but still validates PHP syntax.

### 3. Rollback

```bash
./scripts/deploy.sh --rollback
```

Restores from the most recent backup in `/home/gositeme/backups/`.

### 4. CI/CD Pipeline

Push to `main` or `develop` triggers the GitHub Actions workflow (`.github/workflows/`):

1. **Lint** — PHP syntax check, JS validation
2. **Test** — PHPUnit suite (499 tests)
3. **Security** — Dependency audit
4. **Deploy** — SSH deploy to production (main branch only)

### 5. Fresh Server Bootstrap

For deploying to a new server:

```bash
curl -sSL https://gositeme.com/scripts/alfred-deploy.sh | bash
```

Or with options:
```bash
bash alfred-deploy.sh --domain example.com --port 8080 --no-ssl
```

Installs all dependencies, creates user, syncs files, configures DB, sets up PM2 and Caddy.

## Manual Deployment Steps

If the deploy script isn't available:

```bash
# 1. Pull latest code
cd /home/gositeme/domains/gositeme.com/public_html
git pull origin main

# 2. Install PHP dependencies
composer install --no-dev --optimize-autoloader

# 3. Validate PHP syntax (check changed files)
find . -maxdepth 1 -name "*.php" -newer .git/FETCH_HEAD -exec php -l {} \;
find api/ -name "*.php" -newer .git/FETCH_HEAD -exec php -l {} \;

# 4. Run tests
php vendor/bin/phpunit

# 5. Restart services
pm2 restart all

# 6. Verify
curl -sI https://gositeme.com/ | head -1         # Should be HTTP/2 200
curl -sI https://gositeme.com/api/health.php      # Health check
```

## Configuration Files

| File | Purpose | Critical |
|------|---------|----------|
| `Caddyfile` | Web server configuration | Yes |
| `ecosystem.config.js` | PM2 process definitions | Yes |
| `config/database.php` | Database connection | Yes |
| `config/shield_config.php` | Security config | Yes |
| `includes/db-config.inc.php` | DB connection helper | Yes |
| `composer.json` | PHP dependencies | Yes |
| `phpunit.xml` | Test configuration | No |

## Health Checks

After any deployment, verify:

```bash
# Critical pages
curl -sI https://gositeme.com/               # Homepage
curl -sI https://gositeme.com/login           # Auth
curl -sI https://gositeme.com/dashboard       # Dashboard
curl -sI https://gositeme.com/pricing         # Pricing
curl -sI https://gositeme.com/api/health.php  # API health

# Test suite
php vendor/bin/phpunit --no-progress
```

## Log Rotation

Logs are in `logs/`. Rotation script:

```bash
# Manual run
bash scripts/log-rotate.sh

# Cron (recommended): daily at 3 AM
0 3 * * * /home/gositeme/domains/gositeme.com/public_html/scripts/log-rotate.sh
```

Rotates files > 1MB, keeps 7 days of archives in `logs/archive/`.

## Backup

```bash
# Database backup
mysqldump -u gositeme gositeme_db > /home/gositeme/backups/db_$(date +%Y%m%d).sql

# File backup
tar czf /home/gositeme/backups/files_$(date +%Y%m%d).tar.gz \
  --exclude=node_modules --exclude=vendor --exclude=cache --exclude=logs \
  /home/gositeme/domains/gositeme.com/public_html/
```

## Troubleshooting

### PHP errors after deploy
```bash
php -l problematic-file.php    # Check syntax
tail -f /var/log/php-fpm.log   # Check FPM logs
```

### PM2 services not starting
```bash
pm2 status                     # Check service status
pm2 logs service-name          # Check service logs
pm2 restart all                # Restart all services
```

### Caddy not serving
```bash
caddy validate --config Caddyfile  # Validate config
caddy reload --config Caddyfile    # Reload without downtime
systemctl status caddy              # Check service status
```

### Database connection issues
```bash
# Test connection
php -r "require 'includes/db-config.inc.php'; echo getSharedDB() ? 'OK' : 'FAIL';"
```

### Rate limit issues during testing
```bash
# Auth tests have built-in delays (500ms between tests)
# Wait 60+ seconds between test suite runs
# Global API rate limit: 60 req/min per IP
```
