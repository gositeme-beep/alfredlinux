#!/usr/bin/env node
'use strict';

/**
 * GoCodeMe — File Integrity Monitor
 * ─────────────────────────────────────────────────────────────────────────────
 * Monitors critical files across all domains for unauthorized modifications.
 * Detects: malware injection, .htaccess tampering, new PHP files in static dirs.
 *
 * Run modes:
 *   --baseline   Generate initial checksums (run once after cleanup)
 *   --check      Compare current state vs baseline, alert on changes
 *   --cron       Same as --check, for use in PM2 scheduler
 *
 * Storage: /home/gositeme/.gocodeme/integrity-baseline.json
 */

const fs    = require('fs');
const path  = require('path');
const crypto = require('crypto');
const { execSync } = require('child_process');

const HOME         = '/home/gositeme';
const DOMAINS_DIR  = path.join(HOME, 'domains');
const BASELINE_FILE = path.join(HOME, '.gocodeme', 'integrity-baseline.json');
const ALERT_LOG    = path.join(HOME, '.gocodeme', 'integrity-alerts.log');

const LOG = (msg) => console.log(`[integrity] ${new Date().toISOString()} — ${msg}`);
const ALERT = (msg) => {
  const line = `[ALERT] ${new Date().toISOString()} — ${msg}`;
  console.error(line);
  fs.appendFileSync(ALERT_LOG, line + '\n');
};

// ── Files to monitor per domain ──────────────────────────────────────────────
// Key files that malware typically targets
const CRITICAL_FILES = [
  'public_html/index.php',
  'public_html/index.html',
  'public_html/.htaccess',
  'public_html/wp-config.php',
  'public_html/wp-settings.php',
  'public_html/wp-includes/version.php',
  'public_html/robots.txt',
];

// Additional files specific to gositeme.com
const GOSITEME_EXTRAS = [
  'public_html/gocodeme.php',
  'public_html/dashboard.php',
  'public_html/includes/site-header.inc.php',
  'public_html/includes/site-footer.inc.php',
  'public_html/includes/shield.php',
  'public_html/includes/ddos_protection.php',
  'public_html/includes/lang.php',
  'public_html/api/config.php',
  'public_html/editor/config.php',
  'public_html/editor/index.php',
];

// Patterns that indicate malware in PHP files
const MALWARE_SIGNATURES = [
  /\beval\s*\(\s*(?:base64_decode|gzinflate|gzuncompress|str_rot13|rawurldecode)/i,
  /\b(?:shell_exec|passthru|system|proc_open|popen)\s*\(/i,
  /\$_(?:GET|POST|REQUEST|COOKIE)\s*\[.*?\]\s*\(/i,
  /file_get_contents\s*\(\s*['"]https?:\/\/.*?rakuten/i,
  /base64_decode\s*\(\s*['"][A-Za-z0-9+\/=]{100,}['"]\s*\)/,
  /\bcurl_exec\s*\(.*\$_(?:GET|POST|REQUEST)/i,
  /preg_replace\s*\(\s*['"]\/.*\/e['"]/i,
  /assert\s*\(\s*(?:\$_|stripslashes|base64)/i,
  /\brakuten\d*jp\b/i,
  /\bcreate_function\s*\(/i,
];

// ── Helpers ──────────────────────────────────────────────────────────────────
function sha256(filePath) {
  try {
    const content = fs.readFileSync(filePath);
    return crypto.createHash('sha256').update(content).digest('hex');
  } catch {
    return null; // file doesn't exist
  }
}

function fileSize(filePath) {
  try {
    return fs.statSync(filePath).size;
  } catch {
    return null;
  }
}

function fileMtime(filePath) {
  try {
    return fs.statSync(filePath).mtimeMs;
  } catch {
    return null;
  }
}

function scanForMalware(filePath) {
  try {
    if (!filePath.endsWith('.php') && !filePath.endsWith('.htaccess')) return [];
    const content = fs.readFileSync(filePath, 'utf-8');
    const findings = [];
    for (const sig of MALWARE_SIGNATURES) {
      if (sig.test(content)) {
        findings.push(sig.source.substring(0, 60));
      }
    }
    return findings;
  } catch {
    return [];
  }
}

function listDomains() {
  try {
    return fs.readdirSync(DOMAINS_DIR)
      .filter(d => {
        const full = path.join(DOMAINS_DIR, d);
        return fs.statSync(full).isDirectory() && fs.existsSync(path.join(full, 'public_html'));
      });
  } catch {
    return [];
  }
}

function findNewPhpFiles(domainDir) {
  // Check for unexpected PHP files in domains that should be static-only
  const staticDomains = ['gocodeme.com'];
  const domain = path.basename(domainDir);

  if (!staticDomains.includes(domain)) return [];

  const pubHtml = path.join(domainDir, 'public_html');
  const suspicious = [];

  try {
    const files = execSync(
      `find "${pubHtml}" -name "*.php" -type f -not -path "*/cgi-bin/*" 2>/dev/null`,
      { encoding: 'utf-8', timeout: 5000 }
    ).trim().split('\n').filter(Boolean);

    for (const f of files) {
      suspicious.push(f);
    }
  } catch { /* ignore */ }

  return suspicious;
}

// ── Generate baseline ────────────────────────────────────────────────────────
function generateBaseline() {
  LOG('Generating integrity baseline...');
  const baseline = { generated: new Date().toISOString(), files: {} };
  const domains = listDomains();

  for (const domain of domains) {
    const domainDir = path.join(DOMAINS_DIR, domain);
    const filesToCheck = [...CRITICAL_FILES];

    if (domain === 'gositeme.com') {
      filesToCheck.push(...GOSITEME_EXTRAS);
    }

    for (const relPath of filesToCheck) {
      const fullPath = path.join(domainDir, relPath);
      const hash = sha256(fullPath);
      if (hash) {
        const key = `${domain}/${relPath}`;
        baseline.files[key] = {
          sha256: hash,
          size: fileSize(fullPath),
          mtime: fileMtime(fullPath),
        };
        LOG(`  ✓ ${key}`);
      }
    }
  }

  // Also baseline the .env symlink target
  const envHash = sha256(path.join(HOME, '.gocodeme', '.env'));
  if (envHash) {
    baseline.files['~/.gocodeme/.env'] = {
      sha256: envHash,
      size: fileSize(path.join(HOME, '.gocodeme', '.env')),
      mtime: fileMtime(path.join(HOME, '.gocodeme', '.env')),
    };
  }

  fs.mkdirSync(path.dirname(BASELINE_FILE), { recursive: true });
  fs.writeFileSync(BASELINE_FILE, JSON.stringify(baseline, null, 2));
  LOG(`Baseline saved: ${Object.keys(baseline.files).length} files tracked → ${BASELINE_FILE}`);
}

// ── Check integrity ──────────────────────────────────────────────────────────
function checkIntegrity() {
  if (!fs.existsSync(BASELINE_FILE)) {
    LOG('No baseline found. Run with --baseline first.');
    process.exit(1);
  }

  const baseline = JSON.parse(fs.readFileSync(BASELINE_FILE, 'utf-8'));
  LOG(`Checking integrity against baseline from ${baseline.generated}`);

  let alerts = 0;
  let checked = 0;

  // Check all baselined files
  for (const [key, expected] of Object.entries(baseline.files)) {
    let fullPath;
    if (key.startsWith('~/.gocodeme/')) {
      fullPath = path.join(HOME, '.gocodeme', key.replace('~/.gocodeme/', ''));
    } else {
      const [domain, ...rest] = key.split('/');
      fullPath = path.join(DOMAINS_DIR, domain, rest.join('/'));
    }

    const currentHash = sha256(fullPath);
    checked++;

    if (currentHash === null) {
      ALERT(`FILE DELETED: ${key}`);
      alerts++;
      continue;
    }

    if (currentHash !== expected.sha256) {
      const currentSize = fileSize(fullPath);
      ALERT(`FILE MODIFIED: ${key} (size: ${expected.size} → ${currentSize})`);
      alerts++;

      // Scan modified file for malware signatures
      const malwareHits = scanForMalware(fullPath);
      if (malwareHits.length > 0) {
        ALERT(`  ⚠ MALWARE DETECTED in ${key}: ${malwareHits.join(', ')}`);
      }
    }
  }

  // Check for new PHP files in static domains
  const domains = listDomains();
  for (const domain of domains) {
    const domainDir = path.join(DOMAINS_DIR, domain);
    const newPhpFiles = findNewPhpFiles(domainDir);
    for (const f of newPhpFiles) {
      ALERT(`NEW PHP FILE in static domain: ${f}`);

      const malwareHits = scanForMalware(f);
      if (malwareHits.length > 0) {
        ALERT(`  ⚠ MALWARE DETECTED: ${malwareHits.join(', ')}`);
      }
      alerts++;
    }
  }

  // Scan all .htaccess files for rewrites to suspicious domains
  for (const domain of domains) {
    const htaccess = path.join(DOMAINS_DIR, domain, 'public_html', '.htaccess');
    if (fs.existsSync(htaccess)) {
      try {
        const content = fs.readFileSync(htaccess, 'utf-8');
        if (/rakuten|\.click|\.buzz|\beval\s*\(|base64_decode\s*\(/i.test(content)) {
          ALERT(`SUSPICIOUS .htaccess in ${domain}: contains malware indicators`);
          alerts++;
        }
      } catch { /* permission error */ }
    }
  }

  // Check that .env is still a symlink (not replaced with a real file)
  const envSymlink = path.join(HOME, 'domains/gositeme.com/public_html/gocodeme/middleware/.env');
  try {
    const stat = fs.lstatSync(envSymlink);
    if (!stat.isSymbolicLink()) {
      ALERT('.env in middleware/ is no longer a symlink — may have been tampered with');
      alerts++;
    }
  } catch { /* missing is fine, htaccess blocks it */ }

  LOG(`Check complete: ${checked} files checked, ${alerts} alert(s)`);

  if (alerts > 0) {
    LOG(`⚠ ${alerts} INTEGRITY ALERT(S) — review ${ALERT_LOG}`);
    return false;
  }

  LOG('✓ All files intact');
  return true;
}

// ── Main ─────────────────────────────────────────────────────────────────────
const mode = process.argv[2] || '--check';

switch (mode) {
  case '--baseline':
    generateBaseline();
    break;
  case '--check':
  case '--cron':
    const ok = checkIntegrity();
    if (!ok) process.exitCode = 1;
    break;
  default:
    console.log('Usage: integrity-monitor.js [--baseline|--check|--cron]');
    process.exit(1);
}
