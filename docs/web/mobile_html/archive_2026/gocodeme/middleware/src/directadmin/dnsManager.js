'use strict';

/**
 * DirectAdmin DNS Manager
 *
 * Manage DNS records for domains via DirectAdmin's CMD_API.
 * Uses admin|user impersonation.
 *
 * DA API Endpoints:
 *   CMD_API_DNS_CONTROL — list / add / delete DNS records
 */

const { createDAClient } = require('./client');
const logger = require('../logger');

/**
 * List all DNS records for a domain.
 *
 * @param {string} daUsername
 * @param {string} domain
 * @returns {Promise<Array<{name: string, type: string, value: string, ttl?: string}>>}
 */
async function listDnsRecords(daUsername, domain) {
  const client = createDAClient(daUsername);
  const resp   = await client.get('/CMD_API_DNS_CONTROL', {
    params: { domain },
  });
  const data = resp.data;

  // DA returns records in URL-encoded format:
  // A=name%3Dexample.com.%26value%3D1.2.3.4%26ttl%3D14400
  if (typeof data === 'string') {
    const records = [];
    const params  = new URLSearchParams(data);
    for (const [recordType, encodedVal] of params) {
      if (recordType === 'error' || recordType === 'text' || recordType === 'details') continue;
      const attrs  = new URLSearchParams(decodeURIComponent(encodedVal));
      records.push({
        type:  recordType.replace(/\d+$/, ''), // strip trailing index numbers
        name:  attrs.get('name') || '',
        value: attrs.get('value') || '',
        ttl:   attrs.get('ttl') || '14400',
      });
    }
    return records;
  }

  if (Array.isArray(data)) return data;
  if (typeof data === 'object') return Object.values(data);
  return [];
}

/**
 * Add a DNS record.
 *
 * @param {string} daUsername
 * @param {string} domain
 * @param {string} type   — A, AAAA, CNAME, MX, TXT, SRV, etc.
 * @param {string} name   — record name (e.g. "www", "@", "mail")
 * @param {string} value  — record value (e.g. IP address, hostname, TXT content)
 * @param {number} [ttl=14400]
 * @returns {Promise<void>}
 */
async function addDnsRecord(daUsername, domain, type, name, value, ttl = 14400) {
  const client = createDAClient(daUsername);

  const params = new URLSearchParams({
    action:  'add',
    domain,
    type:    type.toUpperCase(),
    name,
    value,
    ttl:     String(ttl),
  });

  const resp = await client.post('/CMD_API_DNS_CONTROL', params.toString());
  const body = typeof resp.data === 'string' ? resp.data : JSON.stringify(resp.data);

  if (body.includes('error=1')) {
    throw new Error(`DNS record add failed: ${body.substring(0, 300)}`);
  }

  logger.info(`da-dns: added ${type} record ${name}.${domain} → ${value} for ${daUsername}`);
}

/**
 * Delete a DNS record.
 *
 * @param {string} daUsername
 * @param {string} domain
 * @param {string} type
 * @param {string} name
 * @param {string} value
 * @returns {Promise<void>}
 */
async function deleteDnsRecord(daUsername, domain, type, name, value) {
  const client = createDAClient(daUsername);

  const params = new URLSearchParams({
    action:  'select',
    domain,
    aression0: `name=${name}&value=${value}`,
    [`${type.toLowerCase()}recs0`]: `name=${encodeURIComponent(name)}&value=${encodeURIComponent(value)}`,
  });

  const resp = await client.post('/CMD_API_DNS_CONTROL', params.toString());
  const body = typeof resp.data === 'string' ? resp.data : JSON.stringify(resp.data);

  if (body.includes('error=1')) {
    throw new Error(`DNS record delete failed: ${body.substring(0, 300)}`);
  }

  logger.info(`da-dns: deleted ${type} record ${name}.${domain} for ${daUsername}`);
}

module.exports = {
  listDnsRecords,
  addDnsRecord,
  deleteDnsRecord,
};
