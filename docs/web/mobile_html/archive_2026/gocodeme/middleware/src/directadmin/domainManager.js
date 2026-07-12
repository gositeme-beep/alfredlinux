'use strict';

/**
 * DirectAdmin Domain Manager
 *
 * Create, list, and manage domains and subdomains via DirectAdmin's CMD_API.
 * Uses admin|user impersonation.
 *
 * DA API Endpoints:
 *   CMD_API_SHOW_DOMAINS     — list domains
 *   CMD_API_DOMAIN           — create / delete domains
 *   CMD_API_SUBDOMAINS       — list / create / delete subdomains
 *   CMD_API_ADDITIONAL_DOMAINS — addon domains
 */

const { createDAClient } = require('./client');
const logger = require('../logger');

/**
 * List all domains for a DirectAdmin user.
 *
 * @param {string} daUsername
 * @returns {Promise<string[]>}
 */
async function listDomains(daUsername) {
  const client = createDAClient(daUsername);
  const resp   = await client.get('/CMD_API_SHOW_DOMAINS');
  const data   = resp.data;

  if (Array.isArray(data)) return data;
  if (typeof data === 'object' && data.list) return [].concat(data.list);

  if (typeof data === 'string') {
    const result = [];
    const params = new URLSearchParams(data);
    for (const [key, val] of params) {
      if (key.startsWith('list') || key === 'domain') result.push(val);
      else result.push(key); // DA sometimes returns domain=value pairs
    }
    return result.filter(Boolean);
  }
  return [];
}

/**
 * List subdomains for a domain.
 *
 * @param {string} daUsername
 * @param {string} domain
 * @returns {Promise<string[]>}
 */
async function listSubdomains(daUsername, domain) {
  const client = createDAClient(daUsername);
  const resp   = await client.get('/CMD_API_SUBDOMAINS', {
    params: { domain },
  });
  const data = resp.data;

  if (Array.isArray(data)) return data;
  if (typeof data === 'object' && data.list) return [].concat(data.list);

  if (typeof data === 'string') {
    const result = [];
    const params = new URLSearchParams(data);
    for (const [, val] of params) {
      if (val) result.push(val);
    }
    return result;
  }
  return [];
}

/**
 * Create a subdomain.
 *
 * @param {string} daUsername
 * @param {string} domain     - parent domain (e.g. "example.com")
 * @param {string} subdomain  - subdomain name (e.g. "blog")
 * @returns {Promise<string>}  Full subdomain URL
 */
async function createSubdomain(daUsername, domain, subdomain) {
  const client = createDAClient(daUsername);

  const params = new URLSearchParams({
    action:    'create',
    domain,
    subdomain,
  });

  const resp = await client.post('/CMD_API_SUBDOMAINS', params.toString());
  const body = typeof resp.data === 'string' ? resp.data : JSON.stringify(resp.data);

  if (body.includes('error=1')) {
    throw new Error(`Subdomain creation failed: ${body.substring(0, 300)}`);
  }

  const full = `${subdomain}.${domain}`;
  logger.info(`da-domain: created subdomain ${full} for ${daUsername}`);
  return full;
}

/**
 * Delete a subdomain.
 *
 * @param {string} daUsername
 * @param {string} domain
 * @param {string} subdomain
 * @returns {Promise<void>}
 */
async function deleteSubdomain(daUsername, domain, subdomain) {
  const client = createDAClient(daUsername);

  const params = new URLSearchParams({
    action:   'delete',
    domain,
    select0:  subdomain,
  });

  const resp = await client.post('/CMD_API_SUBDOMAINS', params.toString());
  const body = typeof resp.data === 'string' ? resp.data : JSON.stringify(resp.data);

  if (body.includes('error=1')) {
    throw new Error(`Subdomain deletion failed: ${body.substring(0, 300)}`);
  }

  logger.info(`da-domain: deleted subdomain ${subdomain}.${domain} for ${daUsername}`);
}

/**
 * Get domain configuration / document root info.
 *
 * @param {string} daUsername
 * @param {string} domain
 * @returns {Promise<object>}
 */
async function getDomainInfo(daUsername, domain) {
  const client = createDAClient(daUsername);

  const resp = await client.get('/CMD_API_DOMAIN', {
    params: { domain },
  });

  return resp.data;
}

module.exports = {
  listDomains,
  listSubdomains,
  createSubdomain,
  deleteSubdomain,
  getDomainInfo,
};
