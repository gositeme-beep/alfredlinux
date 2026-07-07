'use strict';

/**
 * DirectAdmin API Client
 *
 * Wraps DirectAdmin's REST API with admin impersonation support.
 * Impersonation format: "admin|targetusername" as the HTTP Basic auth username,
 * using the admin password.  This gives full access to the target user's account
 * without knowing their password.
 *
 * All endpoints documented in the DirectAdmin Swagger UI:
 *   https://<host>:2222/swagger
 *   or via Evolution skin → Support & Help → Live API Documentation
 */

const axios = require('axios');
const https = require('https');
const config = require('../config');
const logger = require('../logger');

// SECURITY NOTE (R3-07): rejectUnauthorized:false is used because DirectAdmin
// commonly uses self-signed certs on port 2222.  DA_HOST defaults to
// https://localhost:2222 (loopback-only), so MITM risk is negligible — an
// attacker with root access to intercept loopback traffic already controls
// the machine.  If DA is moved to a remote host, pin the server certificate
// via the `ca` option or set NODE_EXTRA_CA_CERTS to the DA cert file.
const httpsAgent = new https.Agent({ rejectUnauthorized: false });

/**
 * Build an axios instance scoped to a specific DirectAdmin user via impersonation.
 *
 * @param {string} daUsername - The DirectAdmin username to impersonate
 * @returns {import('axios').AxiosInstance}
 */
function createDAClient(daUsername) {
  const impersonatedUser = config.directAdmin.impersonateUser(daUsername);

  const client = axios.create({
    baseURL: config.directAdmin.host,
    httpsAgent,
    auth: {
      username: impersonatedUser,
      password: config.directAdmin.adminPass,
    },
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
      Accept: 'application/json',
    },
    timeout: 30000,
  });

  // Log every request at debug level (never log passwords)
  client.interceptors.request.use((req) => {
    logger.debug(`DA API → ${req.method?.toUpperCase()} ${req.baseURL}${req.url} (as ${impersonatedUser})`);
    return req;
  });

  // Log errors (throttled to avoid flooding logs during sync)
  let _errCount = 0;
  let _errResetTimer = null;
  client.interceptors.response.use(
    (res) => res,
    (err) => {
      _errCount++;
      const status = err.response?.status;
      const url = err.config?.url;
      if (_errCount <= 3) {
        logger.error(`DA API error ${status} on ${url}: ${err.message}`);
      } else if (_errCount === 4) {
        logger.error(`DA API error ${status} on ${url} (suppressing further — ${_errCount}+ errors)`);
      }
      // Reset counter after 60s of no errors
      clearTimeout(_errResetTimer);
      _errResetTimer = setTimeout(() => { _errCount = 0; }, 60000);
      return Promise.reject(err);
    }
  );

  return client;
}

/**
 * Build an admin-only axios instance (no impersonation) for admin-level operations.
 */
function createAdminClient() {
  const client = axios.create({
    baseURL: config.directAdmin.host,
    httpsAgent,
    auth: {
      username: config.directAdmin.adminUser,
      password: config.directAdmin.adminPass,
    },
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
      Accept: 'application/json',
    },
    timeout: 30000,
  });

  client.interceptors.response.use(
    (res) => res,
    (err) => {
      logger.error(`DA Admin API error: ${err.message}`);
      return Promise.reject(err);
    }
  );

  return client;
}

module.exports = { createDAClient, createAdminClient };
