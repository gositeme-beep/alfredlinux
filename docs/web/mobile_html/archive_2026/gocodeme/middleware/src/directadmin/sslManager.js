'use strict';

/**
 * DirectAdmin SSL Manager
 *
 * Manage SSL certificates via DirectAdmin's CMD_API.
 * Supports Let's Encrypt auto-provisioning and custom cert installation.
 *
 * DA API Endpoints:
 *   CMD_API_SSL          — install / view SSL certs
 *   CMD_API_LETSENCRYPT  — request Let's Encrypt free SSL
 */

const { createDAClient } = require('./client');
const logger = require('../logger');

/**
 * Request a free Let's Encrypt SSL certificate for a domain.
 *
 * @param {string} daUsername
 * @param {string} domain
 * @param {boolean} [wildcard=false]  — request wildcard cert (*.domain)
 * @returns {Promise<{status: string, domain: string}>}
 */
async function requestLetsEncrypt(daUsername, domain, wildcard = false) {
  const client = createDAClient(daUsername);

  // Extract a friendly name from the domain (letters, spaces, periods only)
  const certName = domain.replace(/[^a-zA-Z. ]/g, ' ').trim() || 'SSL Certificate';

  const formParams = {
    domain,
    action:     'save',
    type:       'create',
    request:    'letsencrypt',
    name:       certName,
    keysize:    'prime256v1',
    le_select0: domain,
    le_select1: `www.${domain}`,
    le_select2: `mail.${domain}`,
  };

  if (wildcard) {
    formParams.le_wc_select0 = `*.${domain}`;
  }

  const params = new URLSearchParams(formParams);
  const resp = await client.post('/CMD_API_SSL', params.toString());
  const body = typeof resp.data === 'string' ? resp.data : JSON.stringify(resp.data);

  if (body.includes('error=1')) {
    throw new Error(`Let's Encrypt request failed: ${body.substring(0, 300)}`);
  }

  logger.info(`da-ssl: requested Let's Encrypt for ${domain} (wildcard=${wildcard}) for ${daUsername}`);

  return { status: 'requested', domain };
}

/**
 * Get SSL certificate status for a domain.
 *
 * @param {string} daUsername
 * @param {string} domain
 * @returns {Promise<object>}
 */
async function getSSLStatus(daUsername, domain) {
  const client = createDAClient(daUsername);
  const resp   = await client.get('/CMD_API_SSL', {
    params: { domain },
  });
  return resp.data;
}

/**
 * Enable HTTPS redirect (force SSL) for a domain.
 *
 * @param {string} daUsername
 * @param {string} domain
 * @returns {Promise<void>}
 */
async function enableForceSSL(daUsername, domain) {
  const client = createDAClient(daUsername);

  const params = new URLSearchParams({
    action:    'save',
    domain,
    force_ssl: 'yes',
  });

  const resp = await client.post('/CMD_API_DOMAIN', params.toString());
  const body = typeof resp.data === 'string' ? resp.data : JSON.stringify(resp.data);

  if (body.includes('error=1')) {
    throw new Error(`Force SSL failed: ${body.substring(0, 300)}`);
  }

  logger.info(`da-ssl: enabled force SSL for ${domain}`);
}

module.exports = {
  requestLetsEncrypt,
  getSSLStatus,
  enableForceSSL,
};
