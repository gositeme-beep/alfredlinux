/**
 * logAnalyzer.js — Error log and access log analysis for MCP Server
 *
 * Reads and analyzes Apache/PHP error logs and access logs from the
 * DirectAdmin domain log structure.
 *
 * DA log locations:
 *   /home/<user>/domains/<domain>/logs/  — contains compressed daily archives
 *   PHP errors also appear in error_log files within docroots
 *
 * Access logs and error logs are archived to tar.gz daily by DA.
 * We read both the current active log and recent archives.
 */

import { shellExec } from './shellExec.js';
import path from 'path';
import fs from 'fs';

export class LogAnalyzer {
  /**
   * @param {string} homeDir — absolute path to customer home
   */
  constructor(homeDir) {
    this.homeDir = homeDir;
  }

  /**
   * Find all error_log files in the customer's domains.
   */
  _findErrorLogs() {
    const result = shellExec(
      'find . -maxdepth 5 -name "error_log" -o -name "error.log" 2>/dev/null | head -50',
      this.homeDir,
      { timeout: 10000 }
    );
    return result.stdout.trim().split('\n').filter(Boolean).map(p => p.replace(/^\.\//, ''));
  }

  /**
   * Read the tail of an error_log file.
   * @param {string} [domain] — specific domain, or auto-detect
   * @param {number} [lines=100]
   */
  readErrorLog(domain, lines = 100) {
    const paths = [];

    if (domain) {
      // Check domain-specific paths
      paths.push(
        path.join('domains', domain, 'public_html', 'error_log'),
        path.join('domains', domain, 'logs', 'error.log'),
      );
    }
    // Always check main public_html
    paths.push('public_html/error_log');

    const results = {};
    for (const p of paths) {
      const abs = path.join(this.homeDir, p);
      if (fs.existsSync(abs)) {
        const result = shellExec(`tail -n ${lines} "${abs}"`, this.homeDir);
        if (result.stdout.trim()) {
          results[p] = result.stdout.trim();
        }
      }
    }

    // Also search for any error_log files we might have missed
    if (Object.keys(results).length === 0) {
      const found = this._findErrorLogs();
      for (const p of found.slice(0, 5)) {
        const abs = path.join(this.homeDir, p);
        if (fs.existsSync(abs)) {
          const result = shellExec(`tail -n ${lines} "${abs}"`, this.homeDir);
          if (result.stdout.trim()) {
            results[p] = result.stdout.trim();
          }
        }
      }
    }

    if (Object.keys(results).length === 0) {
      return { found: false, message: 'No error logs found. This is good — no PHP errors!' };
    }

    return { found: true, logs: results };
  }

  /**
   * Read recent access logs (from DA's compressed archives).
   * @param {string} domain
   * @param {number} [lines=200]
   */
  readAccessLog(domain, lines = 200) {
    const logDir = path.join(this.homeDir, 'domains', domain || '', 'logs');
    if (!fs.existsSync(logDir)) {
      return { found: false, message: `No log directory found for domain: ${domain || 'default'}` };
    }

    // Find the most recent tar.gz (today's)
    const files = fs.readdirSync(logDir)
      .filter(f => f.endsWith('.tar.gz') && !f.includes('.pay.') && !f.includes('.presser.') && !f.includes('.quickqr.'))
      .sort()
      .reverse();

    if (files.length === 0) {
      return { found: false, message: 'No log archives found.' };
    }

    // Extract and read the most recent archive
    const latestArchive = files[0];
    const result = shellExec(
      `tar -xzf "${path.join(logDir, latestArchive)}" -O 2>/dev/null | tail -n ${lines}`,
      this.homeDir,
      { timeout: 30000 }
    );

    if (!result.stdout.trim()) {
      return { found: false, message: `Log archive ${latestArchive} is empty.` };
    }

    return {
      found: true,
      archive: latestArchive,
      lines: result.stdout.trim(),
      lineCount: result.stdout.trim().split('\n').length,
    };
  }

  /**
   * Analyze errors by parsing the error log and grouping by type/frequency.
   * @param {string} [domain]
   */
  analyzeErrors(domain) {
    const logData = this.readErrorLog(domain, 500);
    if (!logData.found) return logData;

    const errorCounts = {};
    const errorExamples = {};
    let totalErrors = 0;

    for (const [logPath, content] of Object.entries(logData.logs)) {
      const lines = content.split('\n');
      for (const line of lines) {
        totalErrors++;

        // Classify the error
        let type = 'Other';
        if (line.includes('PHP Fatal error')) type = 'PHP Fatal';
        else if (line.includes('PHP Warning')) type = 'PHP Warning';
        else if (line.includes('PHP Notice')) type = 'PHP Notice';
        else if (line.includes('PHP Parse error')) type = 'PHP Parse Error';
        else if (line.includes('PHP Deprecated')) type = 'PHP Deprecated';
        else if (line.includes('Permission denied')) type = 'Permission Denied';
        else if (line.includes('404') || line.includes('File not found')) type = 'File Not Found';
        else if (line.includes('500') || line.includes('Internal Server Error')) type = '500 Error';
        else if (line.includes('ModSecurity')) type = 'ModSecurity Block';

        errorCounts[type] = (errorCounts[type] || 0) + 1;
        if (!errorExamples[type]) errorExamples[type] = line.slice(0, 300);
      }
    }

    // Sort by count
    const sorted = Object.entries(errorCounts)
      .sort((a, b) => b[1] - a[1])
      .map(([type, count]) => ({
        type,
        count,
        percentage: `${((count / totalErrors) * 100).toFixed(1)}%`,
        example: errorExamples[type],
      }));

    return {
      totalErrors,
      breakdown: sorted,
      logFiles: Object.keys(logData.logs),
      recommendation: totalErrors > 100
        ? 'High error count — investigate the top error types.'
        : totalErrors > 0
        ? 'Some errors found — review the examples above.'
        : 'No errors found.',
    };
  }
}
