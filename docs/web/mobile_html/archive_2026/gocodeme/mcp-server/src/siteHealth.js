/**
 * siteHealth.js — Site health checks for MCP Server
 *
 * Performs live HTTP checks, DNS resolution, SSL validation, and response time
 * measurements. Also gathers PHP and server environment information.
 *
 * Alfred can say "check the health of myblog.com" and get a comprehensive
 * report of the site's operational status.
 */

import { shellExec } from './shellExec.js';
import https from 'https';
import http from 'http';
import dns from 'dns';
import { promisify } from 'util';

const dnsResolve = promisify(dns.resolve);
const dnsResolve4 = promisify(dns.resolve4);

export class SiteHealth {
  /**
   * @param {string} homeDir — absolute path to customer home
   */
  constructor(homeDir) {
    this.homeDir = homeDir;
  }

  /**
   * Comprehensive health check for a domain.
   * @param {string} domain
   */
  async checkHealth(domain) {
    const results = {
      domain,
      timestamp: new Date().toISOString(),
      dns: null,
      http: null,
      https: null,
      ssl: null,
      performance: null,
      status: 'unknown',
    };

    // 1. DNS Check
    try {
      const ips = await dnsResolve4(domain);
      results.dns = { ok: true, records: ips };
    } catch (err) {
      results.dns = { ok: false, error: `DNS resolution failed: ${err.code || err.message}` };
    }

    // 2. HTTP Check (should redirect to HTTPS ideally)
    try {
      const httpResult = await this._httpCheck(`http://${domain}`, 10000);
      results.http = httpResult;
    } catch (err) {
      results.http = { ok: false, error: err.message };
    }

    // 3. HTTPS Check
    try {
      const httpsResult = await this._httpCheck(`https://${domain}`, 10000);
      results.https = httpsResult;
    } catch (err) {
      results.https = { ok: false, error: err.message };
    }

    // 4. SSL Certificate Info
    try {
      const sslInfo = await this._sslCheck(domain);
      results.ssl = sslInfo;
    } catch (err) {
      results.ssl = { ok: false, error: err.message };
    }

    // 5. Performance (basic — uses curl for timing)
    try {
      const perf = this._perfCheck(domain);
      results.performance = perf;
    } catch (err) {
      results.performance = { error: err.message };
    }

    // Determine overall status
    const httpsOk = results.https?.ok;
    const dnsOk = results.dns?.ok;
    const sslOk = results.ssl?.ok;

    if (dnsOk && httpsOk && sslOk) {
      results.status = 'HEALTHY';
    } else if (dnsOk && (httpsOk || results.http?.ok)) {
      results.status = 'DEGRADED';
    } else {
      results.status = 'DOWN';
    }

    // Summary text
    const issues = [];
    if (!dnsOk) issues.push('DNS resolution failed');
    if (!httpsOk) issues.push('HTTPS not working');
    if (!sslOk) issues.push(results.ssl?.error || 'SSL issue');
    if (results.http?.ok && !results.http?.redirectsToHttps) issues.push('HTTP not redirecting to HTTPS');

    results.summary = issues.length === 0
      ? `${domain} is healthy — HTTPS working, SSL valid, DNS resolving.`
      : `${domain} has ${issues.length} issue(s): ${issues.join('; ')}.`;

    return results;
  }

  /**
   * Get PHP and server environment info.
   */
  getServerInfo() {
    const info = {};

    // PHP version
    const php = shellExec('php -v 2>&1 | head -1', this.homeDir);
    info.phpVersion = php.stdout.trim();

    // Available PHP versions
    const phpVersions = shellExec('ls /usr/local/php*/bin/php 2>/dev/null', this.homeDir);
    info.availablePhpVersions = phpVersions.stdout.trim().split('\n').filter(Boolean).map(p => {
      const match = p.match(/php(\d+)/);
      return match ? `${match[1][0]}.${match[1].slice(1)}` : p;
    });

    // Node version
    const node = shellExec('node -v 2>&1', this.homeDir);
    info.nodeVersion = node.stdout.trim();

    // Git version
    const git = shellExec('git --version 2>&1', this.homeDir);
    info.gitVersion = git.stdout.trim();

    // WP-CLI version
    const wp = shellExec('wp --version 2>&1', this.homeDir);
    info.wpCliVersion = wp.stdout.trim();

    // Disk usage
    const disk = shellExec('du -sh . 2>/dev/null | cut -f1', this.homeDir);
    info.totalDiskUsage = disk.stdout.trim();

    // Domain count
    const domains = shellExec('ls -d domains/*/public_html 2>/dev/null | wc -l', this.homeDir);
    info.domainCount = parseInt(domains.stdout.trim()) || 0;

    // OS info
    const os = shellExec('cat /etc/os-release 2>/dev/null | head -2', this.homeDir);
    info.os = os.stdout.trim();

    return info;
  }

  /**
   * HTTP request check with timing.
   * @private
   */
  _httpCheck(url, timeout = 10000) {
    return new Promise((resolve, reject) => {
      const start = Date.now();
      const mod = url.startsWith('https') ? https : http;

      const req = mod.get(url, {
        timeout,
        rejectUnauthorized: false,
        headers: { 'User-Agent': 'GoCodeMe-HealthCheck/1.0' },
      }, (res) => {
        const elapsed = Date.now() - start;
        let body = '';
        res.on('data', chunk => { body += chunk; if (body.length > 10000) body = body.slice(0, 10000); });
        res.on('end', () => {
          const isRedirect = res.statusCode >= 300 && res.statusCode < 400;
          const redirectsToHttps = isRedirect && (res.headers.location || '').startsWith('https');

          resolve({
            ok: res.statusCode >= 200 && res.statusCode < 400,
            statusCode: res.statusCode,
            responseTimeMs: elapsed,
            redirectsToHttps,
            redirectUrl: isRedirect ? res.headers.location : null,
            contentLength: res.headers['content-length'] || body.length,
            server: res.headers.server || 'unknown',
          });
        });
      });
      req.on('error', reject);
      req.on('timeout', () => { req.destroy(); reject(new Error('Request timed out')); });
    });
  }

  /**
   * SSL certificate check.
   * @private
   */
  _sslCheck(domain) {
    return new Promise((resolve, reject) => {
      const req = https.get({
        hostname: domain,
        port: 443,
        path: '/',
        rejectUnauthorized: true,
        timeout: 10000,
        headers: { 'User-Agent': 'GoCodeMe-HealthCheck/1.0' },
      }, (res) => {
        const cert = res.socket.getPeerCertificate();
        if (!cert || !cert.valid_to) {
          resolve({ ok: false, error: 'No certificate returned' });
          return;
        }

        const validTo = new Date(cert.valid_to);
        const validFrom = new Date(cert.valid_from);
        const now = new Date();
        const daysRemaining = Math.floor((validTo - now) / (1000 * 60 * 60 * 24));

        resolve({
          ok: daysRemaining > 0,
          issuer: cert.issuer?.O || cert.issuer?.CN || 'Unknown',
          validFrom: validFrom.toISOString().split('T')[0],
          validTo: validTo.toISOString().split('T')[0],
          daysRemaining,
          subject: cert.subject?.CN || domain,
          altNames: cert.subjectaltname?.split(', ').map(s => s.replace('DNS:', '')) || [],
          warning: daysRemaining < 14 ? `SSL expires in ${daysRemaining} days!` : null,
        });
        res.destroy();
      });
      req.on('error', (err) => {
        if (err.code === 'CERT_HAS_EXPIRED') {
          resolve({ ok: false, error: 'SSL certificate has expired!' });
        } else if (err.code === 'ERR_TLS_CERT_ALTNAME_INVALID') {
          resolve({ ok: false, error: 'SSL certificate name mismatch — wrong domain.' });
        } else {
          resolve({ ok: false, error: err.message });
        }
      });
      req.on('timeout', () => { req.destroy(); reject(new Error('SSL check timed out')); });
    });
  }

  /**
   * Performance check using curl timing.
   * @private
   */
  _perfCheck(domain) {
    const result = shellExec(
      `curl -sS -o /dev/null -w "dns:%{time_namelookup} connect:%{time_connect} ttfb:%{time_starttransfer} total:%{time_total} size:%{size_download} code:%{http_code}" "https://${domain}/" --max-time 15 2>&1`,
      this.homeDir,
      { timeout: 20000 }
    );

    const output = result.stdout.trim();
    const metrics = {};
    for (const part of output.split(' ')) {
      const [key, val] = part.split(':');
      if (key && val) metrics[key] = val;
    }

    return {
      dnsLookupMs: Math.round(parseFloat(metrics.dns || 0) * 1000),
      connectMs: Math.round(parseFloat(metrics.connect || 0) * 1000),
      ttfbMs: Math.round(parseFloat(metrics.ttfb || 0) * 1000),
      totalMs: Math.round(parseFloat(metrics.total || 0) * 1000),
      sizeBytes: parseInt(metrics.size || 0),
      httpCode: metrics.code || 'unknown',
      rating: parseFloat(metrics.ttfb || 0) < 0.5 ? 'Fast'
        : parseFloat(metrics.ttfb || 0) < 1.5 ? 'Acceptable'
        : 'Slow',
    };
  }
}
