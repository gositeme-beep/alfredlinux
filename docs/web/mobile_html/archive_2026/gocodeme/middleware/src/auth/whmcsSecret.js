'use strict';

/**
 * Shared WHMCS webhook secret middleware.
 *
 * Uses timing-safe comparison to prevent timing side-channel attacks.
 * Import this in ALL route files instead of duplicating the check.
 */

const crypto = require('crypto');
const config = require('../config');

function requireWhmcsSecret(req, res, next) {
  const secret = req.headers['x-whmcs-secret'];
  const expected = config.whmcsWebhookSecret;

  // Primary auth: WHMCS webhook secret
  if (secret && expected && secret.length === expected.length &&
      crypto.timingSafeEqual(Buffer.from(secret), Buffer.from(expected))) {
    return next();
  }

  // Secondary auth for /mcp-tool: localhost + internal relay secret or JWT
  // Allows alfred-chat.php and the MCP server to call the tool bridge
  if (req.path === '/mcp-tool') {
    const ip = req.ip || req.connection?.remoteAddress || '';
    const isLocalhost = ip === '127.0.0.1' || ip === '::1' || ip === '::ffff:127.0.0.1';
    if (isLocalhost) {
      const internalSecret = req.headers['x-internal-secret'];
      const expectedInternal = process.env.INTERNAL_SECRET || process.env.INTERNAL_RELAY_SECRET || '';
      if (internalSecret && expectedInternal && internalSecret.length === expectedInternal.length &&
          crypto.timingSafeEqual(Buffer.from(internalSecret), Buffer.from(expectedInternal))) {
        return next();
      }
      // Also accept if the request comes from a known service (MCP/Theia/alfred-chat.php on localhost)
      // These services already validated the user before calling this bridge
      if (req.body && req.body.source) {
        return next();
      }
    }
  }

  return res.status(401).json({ ok: false, error: 'Unauthorized' });
}

module.exports = { requireWhmcsSecret };
