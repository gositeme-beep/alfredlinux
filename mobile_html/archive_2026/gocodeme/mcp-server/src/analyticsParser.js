/**
 * analyticsParser.js — Site analytics from Webalizer/AWStats data for MCP Server
 *
 * DirectAdmin generates Webalizer stats and AWStats reports per domain.
 * This module parses Webalizer history files and AWStats HTML to provide
 * visitor stats, bandwidth data, and traffic analysis.
 *
 * Data sources:
 *   /home/<user>/domains/<domain>/stats/webalizer.hist — monthly totals
 *   /home/<user>/domains/<domain>/stats/usage_YYYYMM.html — detailed monthly
 *   /home/<user>/domains/<domain>/awstats/awstats.<domain>.*  — AWStats data
 */

import { shellExec } from './shellExec.js';
import path from 'path';
import fs from 'fs';

export class AnalyticsParser {
  /**
   * @param {string} homeDir — absolute path to customer home
   */
  constructor(homeDir) {
    this.homeDir = homeDir;
  }

  /**
   * Get visitor stats from Webalizer history for a domain.
   * Returns monthly summaries: hits, files, pages, visits, bandwidth.
   *
   * @param {string} domain
   * @param {number} [months=12] — how many months of history
   */
  getVisitorStats(domain, months = 12) {
    const histFile = path.join(this.homeDir, 'domains', domain, 'stats', 'webalizer.hist');

    if (!fs.existsSync(histFile)) {
      return { found: false, message: `No Webalizer data found for ${domain}. The domain may not have received traffic yet.` };
    }

    const content = fs.readFileSync(histFile, 'utf-8');
    const lines = content.trim().split('\n').filter(l => !l.startsWith('#') && l.trim());

    const records = lines.map(line => {
      // Webalizer .hist format: Month Hits Files Pages Visits Sites KBytes FirstDay LastDay
      const parts = line.trim().split(/\s+/);
      if (parts.length < 8) return null;
      const [monthCode, hits, files, pages, visits, sites, kbytes] = parts;

      // MonthCode is YYYYMM
      const year = monthCode.slice(0, 4);
      const month = monthCode.slice(4, 6);

      return {
        period: `${year}-${month}`,
        hits: parseInt(hits) || 0,
        files: parseInt(files) || 0,
        pages: parseInt(pages) || 0,
        visits: parseInt(visits) || 0,
        uniqueSites: parseInt(sites) || 0,
        bandwidthMB: ((parseInt(kbytes) || 0) / 1024).toFixed(1),
      };
    }).filter(Boolean);

    // Sort by period descending, limit
    records.sort((a, b) => b.period.localeCompare(a.period));
    const recent = records.slice(0, months);

    // Calculate totals
    const totals = recent.reduce((acc, r) => ({
      hits: acc.hits + r.hits,
      pages: acc.pages + r.pages,
      visits: acc.visits + r.visits,
      bandwidthMB: acc.bandwidthMB + parseFloat(r.bandwidthMB),
    }), { hits: 0, pages: 0, visits: 0, bandwidthMB: 0 });

    totals.bandwidthMB = totals.bandwidthMB.toFixed(1);

    // Current month
    const current = recent[0] || null;

    return {
      found: true,
      domain,
      currentMonth: current,
      monthlyData: recent,
      totals,
      summary: current
        ? `${domain}: ${current.visits.toLocaleString()} visits this month (${current.period}), ${current.pages.toLocaleString()} pages, ${current.bandwidthMB} MB bandwidth.`
        : `${domain}: No traffic data available.`,
    };
  }

  /**
   * Get bandwidth breakdown by domain (all domains).
   */
  getBandwidthStats() {
    const domainsDir = path.join(this.homeDir, 'domains');
    if (!fs.existsSync(domainsDir)) return { found: false, message: 'No domains directory found.' };

    const domains = fs.readdirSync(domainsDir).filter(d => {
      const statsDir = path.join(domainsDir, d, 'stats');
      return fs.existsSync(statsDir);
    });

    const stats = domains.map(domain => {
      const data = this.getVisitorStats(domain, 1);
      if (!data.found) return null;
      return {
        domain,
        currentMonth: data.currentMonth,
      };
    }).filter(Boolean);

    // Sort by visits
    stats.sort((a, b) => (b.currentMonth?.visits || 0) - (a.currentMonth?.visits || 0));

    const totalBandwidthMB = stats.reduce((sum, s) => sum + parseFloat(s.currentMonth?.bandwidthMB || 0), 0);

    return {
      found: true,
      domainCount: stats.length,
      totalBandwidthMB: totalBandwidthMB.toFixed(1),
      domains: stats,
    };
  }

  /**
   * Get top pages, referrers, and 404 errors from AWStats HTML reports.
   * @param {string} domain
   */
  getTrafficReport(domain) {
    const awstatsDir = path.join(this.homeDir, 'domains', domain, 'awstats');
    if (!fs.existsSync(awstatsDir)) {
      return { found: false, message: `No AWStats data found for ${domain}.` };
    }

    // Find the most recent main report
    const files = fs.readdirSync(awstatsDir)
      .filter(f => f.match(/^awstats\.[^.]+\.\d{4}\.html$/))
      .sort()
      .reverse();

    if (files.length === 0) {
      return { found: false, message: 'No AWStats report files found.' };
    }

    const report = {};

    // Get top pages report
    const topPagesFile = fs.readdirSync(awstatsDir)
      .filter(f => f.includes('.urldetail.html'))
      .sort()
      .reverse()[0];

    if (topPagesFile) {
      const content = fs.readFileSync(path.join(awstatsDir, topPagesFile), 'utf-8');
      // Parse simple stats from HTML tables
      const urlMatches = content.match(/<td[^>]*class="aws"[^>]*>\/[^<]+<\/td>/g) || [];
      report.topPages = urlMatches.slice(0, 20).map(m => m.replace(/<[^>]+>/g, '').trim());
    }

    // Get 404 errors report
    const errors404File = fs.readdirSync(awstatsDir)
      .filter(f => f.includes('.errors404.html'))
      .sort()
      .reverse()[0];

    if (errors404File) {
      const content = fs.readFileSync(path.join(awstatsDir, errors404File), 'utf-8');
      const urlMatches = content.match(/<td[^>]*class="aws"[^>]*>\/[^<]+<\/td>/g) || [];
      report.top404s = urlMatches.slice(0, 20).map(m => m.replace(/<[^>]+>/g, '').trim());
    }

    // Get robots report
    const robotsFile = fs.readdirSync(awstatsDir)
      .filter(f => f.includes('.allrobots.html'))
      .sort()
      .reverse()[0];

    if (robotsFile) {
      const content = fs.readFileSync(path.join(awstatsDir, robotsFile), 'utf-8');
      const botMatches = content.match(/<td[^>]*class="aws"[^>]*>[^<]*bot[^<]*<\/td>/gi) || [];
      report.topBots = botMatches.slice(0, 15).map(m => m.replace(/<[^>]+>/g, '').trim());
    }

    // Get country report
    const countryFile = fs.readdirSync(awstatsDir)
      .filter(f => f.includes('.alldomains.html'))
      .sort()
      .reverse()[0];

    if (countryFile) {
      const content = fs.readFileSync(path.join(awstatsDir, countryFile), 'utf-8');
      // Extract country names from the report
      const countryMatches = content.match(/<td[^>]*class="aws"[^>]*>[A-Z][a-z]+ ?[A-Za-z]*<\/td>/g) || [];
      report.topCountries = countryMatches.slice(0, 15).map(m => m.replace(/<[^>]+>/g, '').trim());
    }

    return {
      found: true,
      domain,
      latestReport: files[0],
      ...report,
    };
  }
}
