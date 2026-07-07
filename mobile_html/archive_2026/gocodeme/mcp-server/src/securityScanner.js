/**
 * securityScanner.js — Security scanning for MCP Server
 *
 * Provides malware detection, file permission audit, and integrity checking.
 * Alfred can scan a customer's website for common backdoors, suspicious code
 * patterns, and insecure configurations.
 *
 * Patterns based on real-world PHP malware observed on GoCodeMe's own servers.
 */

import { shellExec } from './shellExec.js';
import path from 'path';
import fs from 'fs';

// Common PHP backdoor / malware patterns
const MALWARE_PATTERNS = [
  // Obfuscation patterns
  { pattern: 'eval\\s*\\(\\s*base64_decode', label: 'eval(base64_decode()) — classic PHP backdoor', severity: 'critical' },
  { pattern: 'eval\\s*\\(\\s*gzinflate', label: 'eval(gzinflate()) — compressed payload', severity: 'critical' },
  { pattern: 'eval\\s*\\(\\s*str_rot13', label: 'eval(str_rot13()) — ROT13 obfuscation', severity: 'critical' },
  { pattern: 'eval\\s*\\(\\s*\\$_(?:POST|GET|REQUEST|COOKIE)', label: 'eval($_REQUEST) — direct webshell', severity: 'critical' },
  { pattern: 'assert\\s*\\(\\s*\\$_(?:POST|GET|REQUEST)', label: 'assert($_REQUEST) — alternative webshell', severity: 'critical' },
  { pattern: 'preg_replace\\s*\\(.*/e["\']', label: 'preg_replace /e modifier — code execution', severity: 'critical' },

  // Shell access
  { pattern: 'system\\s*\\(\\s*\\$_', label: 'system() with user input — command execution', severity: 'critical' },
  { pattern: 'passthru\\s*\\(\\s*\\$_', label: 'passthru() with user input', severity: 'critical' },
  { pattern: 'shell_exec\\s*\\(\\s*\\$_', label: 'shell_exec() with user input', severity: 'critical' },
  { pattern: 'exec\\s*\\(\\s*\\$_(?:POST|GET|REQUEST)', label: 'exec() with user input', severity: 'critical' },
  { pattern: '\\$_(?:POST|GET|REQUEST).*\\bexec\\b', label: 'User input to exec()', severity: 'high' },

  // C2 / remote code
  { pattern: 'file_get_contents\\s*\\(\\s*["\']https?://.*\\)\\s*;\\s*eval', label: 'Remote code fetch + eval — C2 beacon', severity: 'critical' },
  { pattern: 'curl_exec.*eval', label: 'Curl + eval — remote code execution', severity: 'critical' },
  { pattern: '\\bfsockopen\\b.*\\beval\\b', label: 'Socket + eval', severity: 'critical' },

  // File manipulation
  { pattern: 'file_put_contents\\s*\\(.*\\$_(?:POST|GET|REQUEST)', label: 'file_put_contents from user input — file dropper', severity: 'high' },
  { pattern: 'fwrite\\s*\\(.*\\$_(?:POST|GET|REQUEST)', label: 'fwrite from user input', severity: 'high' },
  { pattern: 'move_uploaded_file.*\\$_FILES', label: 'Unrestricted file upload (needs context)', severity: 'medium' },

  // Suspicious strings
  { pattern: 'FilesMan|WSO|b374k|c99|r57|WebShell|Ani-Shell', label: 'Known webshell signature', severity: 'critical' },
  { pattern: 'GIF89a.*<\\?php', label: 'PHP code disguised as GIF image', severity: 'critical' },
  { pattern: '\\x47\\x49\\x46\\x38\\x39\\x61.*eval', label: 'Binary GIF header + eval', severity: 'critical' },

  // Suspicious obfuscation
  { pattern: 'chr\\s*\\(\\s*\\d+\\s*\\)\\s*\\.\\s*chr\\s*\\(\\s*\\d+', label: 'Heavy chr() concatenation — code obfuscation', severity: 'medium' },
  { pattern: '\\$[a-zA-Z_]{1,3}\\s*=\\s*["\'][A-Za-z0-9+/=]{100,}["\']', label: 'Long base64 string assignment', severity: 'medium' },
  { pattern: 'str_replace\\s*\\([^)]*\\$_SERVER', label: 'Server var manipulation', severity: 'low' },
];

export class SecurityScanner {
  /**
   * @param {string} homeDir — absolute path to customer home
   */
  constructor(homeDir) {
    this.homeDir = homeDir;
  }

  /**
   * Scan files for malware patterns.
   * @param {string} [directory='public_html'] — directory to scan (relative to home)
   * @param {object} [opts]
   * @param {number} [opts.maxFiles=5000]
   * @param {string[]} [opts.extensions] — file extensions to scan
   */
  scanMalware(directory = 'public_html', opts = {}) {
    const maxFiles = opts.maxFiles || 5000;
    const extensions = opts.extensions || ['php', 'phtml', 'php5', 'php7', 'inc', 'js', 'htaccess'];

    const findings = [];
    let filesScanned = 0;
    let filesSkipped = 0;

    // Build find command for target extensions
    const extFilter = extensions.map(e => `-name "*.${e}"`).join(' -o ');
    const findCmd = `find "${directory}" -maxdepth 8 -type f \\( ${extFilter} \\) 2>/dev/null | head -${maxFiles}`;

    const fileList = shellExec(findCmd, this.homeDir, { timeout: 30000 });
    const files = fileList.stdout.trim().split('\n').filter(Boolean);

    for (const relPath of files) {
      const absPath = path.join(this.homeDir, relPath);
      if (!fs.existsSync(absPath)) continue;

      // Skip very large files (>2MB)
      const stat = fs.statSync(absPath);
      if (stat.size > 2 * 1024 * 1024) {
        filesSkipped++;
        continue;
      }

      filesScanned++;
      let content;
      try {
        content = fs.readFileSync(absPath, 'utf-8');
      } catch {
        continue;
      }

      for (const { pattern, label, severity } of MALWARE_PATTERNS) {
        const regex = new RegExp(pattern, 'i');
        if (regex.test(content)) {
          // Find the line number
          const lines = content.split('\n');
          let lineNum = 0;
          for (let i = 0; i < lines.length; i++) {
            if (regex.test(lines[i])) {
              lineNum = i + 1;
              break;
            }
          }
          findings.push({
            file: relPath,
            line: lineNum,
            severity,
            pattern: label,
            snippet: content.split('\n')[lineNum - 1]?.slice(0, 200) || '',
          });
          break; // One finding per file to keep results manageable
        }
      }
    }

    // Sort by severity
    const severityOrder = { critical: 0, high: 1, medium: 2, low: 3 };
    findings.sort((a, b) => (severityOrder[a.severity] || 9) - (severityOrder[b.severity] || 9));

    const critical = findings.filter(f => f.severity === 'critical').length;
    const high = findings.filter(f => f.severity === 'high').length;

    return {
      filesScanned,
      filesSkipped,
      totalFindings: findings.length,
      critical,
      high,
      findings: findings.slice(0, 50), // Cap at 50 findings
      status: critical > 0
        ? 'INFECTED — Critical malware found! Immediate action required.'
        : high > 0
        ? 'WARNING — Suspicious code found. Review needed.'
        : findings.length > 0
        ? 'NOTICE — Minor concerns found.'
        : 'CLEAN — No malware detected.',
    };
  }

  /**
   * Audit file permissions for security issues.
   * @param {string} [directory='public_html']
   */
  auditPermissions(directory = 'public_html') {
    const issues = [];

    // Find world-writable files
    const worldWritable = shellExec(
      `find "${directory}" -maxdepth 6 -perm -o+w -type f 2>/dev/null | head -100`,
      this.homeDir,
      { timeout: 15000 }
    );
    if (worldWritable.stdout.trim()) {
      const files = worldWritable.stdout.trim().split('\n');
      issues.push({
        type: 'world-writable-files',
        severity: 'high',
        count: files.length,
        message: `${files.length} world-writable files found (chmod o+w). These can be modified by any user on the server.`,
        files: files.slice(0, 20),
        fix: 'Run: find public_html -perm -o+w -type f -exec chmod o-w {} \\;',
      });
    }

    // Find world-writable directories
    const worldWritableDirs = shellExec(
      `find "${directory}" -maxdepth 6 -perm -o+w -type d 2>/dev/null | head -100`,
      this.homeDir,
      { timeout: 15000 }
    );
    if (worldWritableDirs.stdout.trim()) {
      const dirs = worldWritableDirs.stdout.trim().split('\n');
      issues.push({
        type: 'world-writable-dirs',
        severity: 'high',
        count: dirs.length,
        message: `${dirs.length} world-writable directories found. Attackers can write files into these.`,
        files: dirs.slice(0, 20),
        fix: 'Run: find public_html -perm -o+w -type d -exec chmod o-w {} \\;',
      });
    }

    // Check for .htaccess files (potential redirect hijacking)
    const htaccess = shellExec(
      `find "${directory}" -maxdepth 6 -name ".htaccess" 2>/dev/null | head -50`,
      this.homeDir,
      { timeout: 10000 }
    );
    if (htaccess.stdout.trim()) {
      const htFiles = htaccess.stdout.trim().split('\n');
      // Check each .htaccess for suspicious redirects
      for (const f of htFiles.slice(0, 10)) {
        const abs = path.join(this.homeDir, f);
        try {
          const content = fs.readFileSync(abs, 'utf-8');
          if (/RewriteRule.*https?:\/\/[^a-zA-Z]*\.(click|buzz|top|xyz|tk|ml|ga|cf)/i.test(content)) {
            issues.push({
              type: 'suspicious-htaccess',
              severity: 'critical',
              count: 1,
              message: `Suspicious redirect in ${f} — possible SEO spam or malware redirect.`,
              files: [f],
              fix: `Review and clean ${f}`,
            });
          }
        } catch { /* skip unreadable */ }
      }
    }

    // Check for PHP files in upload directories
    const phpInUploads = shellExec(
      `find "${directory}" -maxdepth 8 -path "*/uploads/*.php" -o -path "*/upload/*.php" -o -path "*/tmp/*.php" 2>/dev/null | head -50`,
      this.homeDir,
      { timeout: 10000 }
    );
    if (phpInUploads.stdout.trim()) {
      const files = phpInUploads.stdout.trim().split('\n');
      issues.push({
        type: 'php-in-uploads',
        severity: 'high',
        count: files.length,
        message: `${files.length} PHP files found in upload/tmp directories — likely webshells.`,
        files: files.slice(0, 20),
        fix: 'Review and delete these files. Add "php_flag engine off" to uploads/.htaccess.',
      });
    }

    // Check wp-config.php permissions
    const wpConfig = path.join(this.homeDir, directory, 'wp-config.php');
    if (fs.existsSync(wpConfig)) {
      const stat = fs.statSync(wpConfig);
      const perms = (stat.mode & 0o777).toString(8);
      if (parseInt(perms) > 644) {
        issues.push({
          type: 'wp-config-permissions',
          severity: 'medium',
          count: 1,
          message: `wp-config.php has permissions ${perms} (should be 600 or 640).`,
          files: [path.join(directory, 'wp-config.php')],
          fix: `chmod 640 ${path.join(directory, 'wp-config.php')}`,
        });
      }
    }

    return {
      totalIssues: issues.length,
      critical: issues.filter(i => i.severity === 'critical').length,
      high: issues.filter(i => i.severity === 'high').length,
      issues,
      status: issues.some(i => i.severity === 'critical')
        ? 'CRITICAL — Security issues require immediate attention!'
        : issues.some(i => i.severity === 'high')
        ? 'WARNING — Security improvements recommended.'
        : issues.length > 0
        ? 'NOTICE — Minor improvements possible.'
        : 'SECURE — No permission issues found.',
    };
  }

  /**
   * Quick security health check combining malware + permissions.
   * @param {string} [directory='public_html']
   */
  quickScan(directory = 'public_html') {
    const malware = this.scanMalware(directory, { maxFiles: 2000 });
    const permissions = this.auditPermissions(directory);

    return {
      malwareScan: {
        status: malware.status,
        filesScanned: malware.filesScanned,
        findings: malware.totalFindings,
        critical: malware.critical,
      },
      permissionAudit: {
        status: permissions.status,
        totalIssues: permissions.totalIssues,
        critical: permissions.critical,
      },
      overallStatus: malware.critical > 0 || permissions.critical > 0
        ? 'CRITICAL'
        : malware.high > 0 || permissions.high > 0
        ? 'WARNING'
        : 'CLEAN',
      topFindings: [
        ...malware.findings.slice(0, 5),
        ...permissions.issues.slice(0, 5),
      ],
    };
  }
}
