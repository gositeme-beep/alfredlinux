'use strict';

/**
 * SSRF protection — validate URLs before making server-side requests.
 *
 * SECURITY (R3 M-02 / M-10): Prevents Server-Side Request Forgery by blocking
 * requests to private/internal IP ranges, cloud metadata endpoints, and
 * non-HTTPS protocols.
 */

const { URL } = require('url');

// Private / reserved IP ranges (RFC 1918, link-local, loopback, cloud metadata)
const BLOCKED_PATTERNS = [
  /^127\./,                         // loopback
  /^10\./,                          // 10.0.0.0/8
  /^172\.(1[6-9]|2\d|3[01])\./,    // 172.16.0.0/12
  /^192\.168\./,                    // 192.168.0.0/16
  /^169\.254\./,                    // link-local / cloud metadata
  /^0\./,                           // 0.0.0.0/8
  /^100\.(6[4-9]|[7-9]\d|1[01]\d|12[0-7])\./, // CGNAT 100.64.0.0/10
  /^::1$/,                          // IPv6 loopback
  /^fc00:/i,                        // IPv6 ULA
  /^fe80:/i,                        // IPv6 link-local
];

const BLOCKED_HOSTNAMES = new Set([
  'localhost',
  'metadata.google.internal',
  'metadata.internal',
]);

/**
 * Check whether a URL string is safe for server-side fetching.
 *
 * @param {string} urlStr — the URL to validate
 * @param {object} [opts]
 * @param {boolean} [opts.requireHttps=true] — reject non-HTTPS URLs
 * @returns {{ ok: boolean, error?: string }} — `ok` true if safe
 */
function isAllowedUrl(urlStr, opts = {}) {
  const requireHttps = opts.requireHttps !== false;
  try {
    const u = new URL(urlStr);

    // Protocol check
    if (requireHttps && u.protocol !== 'https:') {
      return { ok: false, error: 'Only HTTPS URLs are allowed' };
    }
    if (!['http:', 'https:'].includes(u.protocol)) {
      return { ok: false, error: 'Invalid protocol' };
    }

    // Hostname blocklist
    const host = u.hostname.toLowerCase();
    if (BLOCKED_HOSTNAMES.has(host)) {
      return { ok: false, error: 'Internal hostnames are not allowed' };
    }

    // IP range blocklist
    for (const pat of BLOCKED_PATTERNS) {
      if (pat.test(host)) {
        return { ok: false, error: 'Internal/private IP addresses are not allowed' };
      }
    }

    return { ok: true };
  } catch {
    return { ok: false, error: 'Invalid URL format' };
  }
}

module.exports = { isAllowedUrl };
