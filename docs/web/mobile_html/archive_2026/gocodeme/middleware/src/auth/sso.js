'use strict';

/**
 * WHMCS SSO Validation
 *
 * WHMCS generates a signed SSO token that GoCodeMe validates before allowing
 * access.  The token contains the WHMCS client ID and is signed with a shared
 * JWT secret.
 *
 * Flow:
 *   1. Customer clicks "Open GoCodeMe Editor" in WHMCS client area
 *   2. WHMCS calls GoCodeMe SSO endpoint with a signed JWT
 *   3. GoCodeMe validates the JWT, checks the customer has an active subscription,
 *      looks up their DirectAdmin username, and issues a session token
 *   4. Customer is redirected to the IDE with the session token in the URL fragment
 *
 * Alternative: WHMCS SSO v2 API — WHMCS can redirect to
 *   /sso?token=<whmcs_signed_token>
 * where the token is HMAC-signed with your WHMCS API secret.
 */

const jwt = require('jsonwebtoken');
const axios = require('axios');
const qs = require('querystring');
const config = require('../config');
const logger = require('../logger');
const { getDaUsernameByWhmcsId } = require('../directadmin/userLookup');
const { getUsage } = require('../tokens/tokenCounter');

/**
 * Validate a WHMCS SSO token (JWT signed with config.jwt.secret).
 * Returns the decoded payload if valid.
 *
 * @param {string} token
 * @returns {{ whmcsClientId: number, plan: string, iat: number, exp: number }}
 */
function validateSsoToken(token) {
  try {
    const payload = jwt.verify(token, config.jwt.secret);
    return payload;
  } catch (err) {
    throw new Error(`Invalid SSO token: ${err.message}`);
  }
}

/**
 * Issue a GoCodeMe session JWT for a validated WHMCS client.
 *
 * @param {number} whmcsClientId
 * @param {string} daUsername
 * @param {string} plan
 * @returns {string}  Signed JWT
 */
function issueSessionToken(whmcsClientId, daUsername, plan) {
  return jwt.sign(
    { whmcsClientId, daUsername, plan },
    config.jwt.secret,
    { expiresIn: config.jwt.expiresIn }
  );
}

/**
 * Verify an existing GoCodeMe session token (used on every protected API call).
 *
 * @param {string} token
 * @returns {{ whmcsClientId: number, daUsername: string, plan: string }}
 */
function verifySessionToken(token) {
  try {
    return jwt.verify(token, config.jwt.secret);
  } catch (err) {
    throw new Error(`Invalid session token: ${err.message}`);
  }
}

/**
 * Verify that a WHMCS client has an active GoCodeMe subscription.
 * Calls the WHMCS API to check product status.
 *
 * @param {number} whmcsClientId
 * @returns {Promise<{ active: boolean, plan: string }>}
 */
async function checkSubscription(whmcsClientId) {
  const params = {
    action: 'GetClientsProducts',
    identifier: config.whmcs.identifier,
    secret: config.whmcs.secret,
    clientid: whmcsClientId,
    responsetype: 'json',
  };

  const response = await axios.post(config.whmcs.apiUrl, qs.stringify(params), {
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    timeout: 10000,
  });

  const data = response.data;

  if (data.result !== 'success') {
    throw new Error(`WHMCS GetClientsProducts failed: ${data.message}`);
  }

  const products = data.products?.product || [];
  const gocodeme = products.find(
    (p) => {
      const gn = (p.groupname || '').toLowerCase();
      const pn = (p.name || '').toLowerCase();
      return gn.includes('gocodeme') || pn.includes('gocodeme') ||
             gn.includes('ai development platform') || gn.includes('ai domain hosting') ||
             gn.includes('ai editor') ||
             pn.startsWith('ai ') ||
             ['builder','professional','studio','business','enterprise'].includes(pn);
    }
  );

  if (!gocodeme) {
    return { active: false, plan: null };
  }

  const active = gocodeme.status?.toLowerCase() === 'active';
  // Map product name → plan key (builder, professional, studio, business, enterprise)
  const rawName = (gocodeme.name || 'professional').toLowerCase().trim();
  // New plan names are single words; legacy names may have "ai " prefix
  const plan = rawName.startsWith('ai ') ? rawName.replace(/^ai\s+/, '').replace(/\s+/g, '_') : rawName.replace(/[^a-z]/g, '');

  return { active, plan };
}

/**
 * Full SSO login flow.
 * Validates token, checks subscription, resolves DA username, issues session.
 *
 * @param {string} ssoToken  - JWT from WHMCS
 * @returns {Promise<{ sessionToken: string, daUsername: string, plan: string, usage: object }>}
 */
async function ssoLogin(ssoToken) {
  const payload = validateSsoToken(ssoToken);
  const { whmcsClientId } = payload;

  const { active, plan } = await checkSubscription(whmcsClientId);

  if (!active) {
    throw new Error('No active GoCodeMe subscription found for this account.');
  }

  const daUsername = await getDaUsernameByWhmcsId(whmcsClientId);
  const usage = await getUsage(whmcsClientId);
  const sessionToken = issueSessionToken(whmcsClientId, daUsername, plan);

  logger.info(`SSO login: WHMCS client ${whmcsClientId} → DA user ${daUsername} (plan: ${plan})`);

  return { sessionToken, daUsername, plan, usage };
}

module.exports = { validateSsoToken, issueSessionToken, verifySessionToken, checkSubscription, ssoLogin };
