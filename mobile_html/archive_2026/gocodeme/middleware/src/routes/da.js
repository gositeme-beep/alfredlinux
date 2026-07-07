'use strict';

/**
 * DirectAdmin Routes
 *
 * POST /api/da/login-key  - Generate a one-time DA login URL for a customer
 *
 * Called by the WHMCS GoCodeMe module to provide "Login to DirectAdmin" SSO.
 *
 * Approach: DA's /api/login endpoint returns a one-time loginURL with a
 * redirect key that sets a session cookie in the browser.
 * Since seller1 is a reseller (not admin), impersonation and login-as
 * don't work.  Instead we:
 *   1. Generate a random temp password
 *   2. Change the DA user's password via CMD_API_USER_PASSWD (reseller can do this)
 *   3. Call POST /api/login with {username, password} to get a loginURL
 *   4. Immediately change the password again (invalidate temp)
 *   5. Return the loginURL for browser redirect
 */

const crypto = require('crypto');
const axios  = require('axios');
const https  = require('https');
const express = require('express');
const router  = express.Router();
const { createAdminClient } = require('../directadmin/client');
const config  = require('../config');
const logger  = require('../logger');
const { requireWhmcsSecret } = require('../auth/whmcsSecret');

const httpsAgent = new https.Agent({ rejectUnauthorized: false });
const DA_PUBLIC_HOST = 'https://gositeme.com:2222';
const DA_INTERNAL    = config.directAdmin.host; // https://127.0.0.1:2222

/**
 * Generate a cryptographically random password (40 chars, base64url safe).
 */
function randomPassword() {
  return crypto.randomBytes(30).toString('base64url');
}

/**
 * Change a DirectAdmin user's password.
 * Uses the reseller's (seller1) credentials to call CMD_API_USER_PASSWD.
 */
async function changeDAPassword(daUsername, newPassword) {
  const adminClient = createAdminClient();
  const params = new URLSearchParams({
    username: daUsername,
    passwd:   newPassword,
    passwd2:  newPassword,
  });
  const resp = await adminClient.post('/CMD_API_USER_PASSWD', params.toString());
  const body = typeof resp.data === 'string' ? resp.data : JSON.stringify(resp.data);

  if (body.includes('error=1') || body.includes('Unable')) {
    throw new Error(`Password change failed: ${body.substring(0, 200)}`);
  }
  return true;
}

/**
 * Call DA /api/login to get a one-time loginURL.
 */
async function daApiLogin(username, password) {
  const resp = await axios.post(`${DA_INTERNAL}/api/login`, {
    username,
    password,
  }, {
    httpsAgent,
    headers: { 'Content-Type': 'application/json' },
    timeout: 15000,
  });

  const { sessionID, loginURL } = resp.data;
  if (!loginURL) {
    throw new Error('DA /api/login did not return a loginURL');
  }

  // Replace 127.0.0.1 with public hostname
  const publicLoginURL = loginURL.replace(/https?:\/\/127\.0\.0\.1:2222/, DA_PUBLIC_HOST);
  return { sessionID, loginURL: publicLoginURL };
}

/**
 * POST /api/da/login-key
 *
 * Creates a one-time DA session for the user via temp-password SSO,
 * then returns the login URL for browser redirect.
 *
 * Body: { daUsername: string, whmcsClientId: number }
 * Returns: { ok: true, loginUrl: string }
 */
router.post('/login-key', requireWhmcsSecret, async (req, res) => {
  const { daUsername } = req.body;

  if (!daUsername) {
    return res.status(400).json({ ok: false, error: 'daUsername required' });
  }

  const tempPass1 = randomPassword();
  const tempPass2 = randomPassword(); // second rotation

  try {
    // Step 1: Change user's DA password to temp password
    logger.info(`da-sso: changing password for ${daUsername} (step 1)`);
    await changeDAPassword(daUsername, tempPass1);

    // Step 2: Login as the user with the temp password to get a session URL
    logger.info(`da-sso: calling /api/login for ${daUsername}`);
    const { loginURL } = await daApiLogin(daUsername, tempPass1);

    // Step 3: Immediately rotate the password so temp is invalid
    logger.info(`da-sso: rotating password for ${daUsername} (step 2)`);
    await changeDAPassword(daUsername, tempPass2).catch((err) => {
      // Non-fatal — the temp password will expire with the session anyway
      logger.warn(`da-sso: post-rotation password change failed: ${err.message}`);
    });

    logger.info(`da-sso: SSO login URL created for ${daUsername}`);
    return res.json({ ok: true, loginUrl: loginURL });

  } catch (err) {
    logger.error(`da-sso: failed for ${daUsername}: ${err.message}`);

    // Try to rotate password anyway to not leave temp password active
    await changeDAPassword(daUsername, randomPassword()).catch(() => {});

    // Fallback: send error with link to DA login page
    return res.status(502).json({ ok: false, error: 'SSO login failed. Please try again.', fallbackUrl: `${DA_PUBLIC_HOST}/` });
  }
});

module.exports = router;
