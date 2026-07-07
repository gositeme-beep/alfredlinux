'use strict';

/**
 * Express authentication middleware
 *
 * Validates the GoCodeMe session JWT on every protected route.
 * Attaches { whmcsClientId, daUsername, plan } to req.user.
 */

const jwt = require('jsonwebtoken');
const { verifySessionToken, issueSessionToken } = require('./sso');
const config = require('../config');
const logger = require('../logger');

/**
 * Maximum age (in seconds) for an expired token to still be auto-refreshed.
 * Tokens expired longer than this require a full re-login via WHMCS SSO.
 */
// SECURITY (R2-18): Reduced from 7 days to 4 hours to limit window for
// stolen/expired token abuse while still allowing reasonable session continuity
const REFRESH_GRACE_PERIOD = 4 * 60 * 60; // 4 hours

/**
 * Require a valid GoCodeMe session token.
 * Token must be in the Authorization header as "Bearer <token>".
 *
 * Auto-refresh: if the token is expired but less than 7 days old,
 * re-issue a fresh token and include it in the X-New-Token response header.
 * The client should watch for this header and update its stored token.
 */
async function requireSession(req, res, next) {
  const authHeader = req.headers.authorization || '';
  const token = authHeader.startsWith('Bearer ') ? authHeader.slice(7) : null;

  if (!token) {
    return res.status(401).json({ error: 'Missing session token' });
  }

  try {
    req.user = verifySessionToken(token);

    // SECURITY (R3-08): Check access status on EVERY request, not just refresh.
    // Without this, suspended/terminated accounts with unexpired JWTs
    // can continue using the service until the token naturally expires.
    const { getRedis } = require('../redis');
    const redis = getRedis();
    const accessStatus = await redis.get(`access:${req.user.whmcsClientId}`);
    if (accessStatus === 'suspended' || accessStatus === 'terminated') {
      logger.warn(`Auth: blocked active token for ${accessStatus} client ${req.user.whmcsClientId} (${req.user.daUsername})`);
      return res.status(401).json({ error: 'Account suspended — contact support' });
    }

    next();
  } catch (err) {
    // ── Auto-refresh expired-but-recent tokens ────────────────────────
    if (err.message.includes('expired')) {
      try {
        const decoded = jwt.verify(token, config.jwt.secret, { ignoreExpiration: true });
        const expiredAgo = Math.floor(Date.now() / 1000) - (decoded.exp || 0);
        if (expiredAgo < REFRESH_GRACE_PERIOD && decoded.whmcsClientId && decoded.daUsername) {
          // SECURITY (VULN-07): Verify subscription is still active before refreshing
          const { getRedis } = require('../redis');
          const redis = getRedis();
          const accessStatus = await redis.get(`access:${decoded.whmcsClientId}`);
          if (accessStatus === 'suspended' || accessStatus === 'terminated') {
            logger.warn(`Auth: blocked token refresh for suspended/terminated client ${decoded.whmcsClientId}`);
            return res.status(401).json({ error: 'Account suspended — contact support' });
          }
          // Re-issue a fresh token
          const newToken = issueSessionToken(decoded.whmcsClientId, decoded.daUsername, decoded.plan);
          res.setHeader('X-New-Token', newToken);
          res.setHeader('Access-Control-Expose-Headers', 'X-New-Token');
          req.user = { whmcsClientId: decoded.whmcsClientId, daUsername: decoded.daUsername, plan: decoded.plan };
          logger.info(`Auth: auto-refreshed expired token for ${decoded.daUsername} (expired ${Math.round(expiredAgo / 60)}min ago)`);
          return next();
        }
      } catch (refreshErr) {
        // Refresh attempt failed — fall through to reject
      }
    }
    logger.warn(`Auth rejected: ${err.message} — IP ${req.ip}`);
    return res.status(401).json({ error: 'Invalid or expired session token' });
  }
}

/**
 * Validate that the authenticated user is accessing only their own resources.
 * Compares the :username route param (if present) against req.user.daUsername.
 */
function requireOwnResource(req, res, next) {
  const { username } = req.params;
  if (username && username !== req.user.daUsername) {
    logger.warn(
      `Isolation violation: user ${req.user.daUsername} attempted to access ${username}`
    );
    return res.status(403).json({ error: 'Access denied: not your workspace' });
  }
  next();
}

module.exports = { requireSession, requireOwnResource };
