'use strict';

/**
 * billing/whmcs.js — WHMCS API Client
 *
 * Thin wrapper around the WHMCS External API v1.
 * Docs: https://developers.whmcs.com/api/
 *
 * All calls are POST to WHMCS_API_URL with action + credentials.
 * On error, throws with a descriptive message.
 */

const https  = require('https');
const http   = require('http');
const qs     = require('querystring');
const logger = require('../logger');
const config = require('../config');

/**
 * Call the WHMCS API.
 *
 * @param {string} action  — WHMCS API action name (e.g. 'CreateInvoice')
 * @param {object} params  — additional params merged with credentials
 * @returns {Promise<object>} parsed WHMCS response
 */
async function callWhmcs(action, params = {}) {
  const url    = config.whmcs.apiUrl;
  const id     = config.whmcs.identifier;
  const secret = config.whmcs.secret;

  if (!url || !id || !secret) {
    throw new Error('WHMCS API credentials not configured (WHMCS_API_URL / WHMCS_API_IDENTIFIER / WHMCS_API_SECRET)');
  }

  const body = qs.stringify({
    action,
    identifier: id,
    secret,
    responsetype: 'json',
    ...params,
  });

  return new Promise((resolve, reject) => {
    const parsedUrl = new URL(url);
    const lib = parsedUrl.protocol === 'https:' ? https : http;

    const options = {
      hostname: parsedUrl.hostname,
      port:     parsedUrl.port || (parsedUrl.protocol === 'https:' ? 443 : 80),
      path:     parsedUrl.pathname + parsedUrl.search,
      method:   'POST',
      headers: {
        'Content-Type':   'application/x-www-form-urlencoded',
        'Content-Length': Buffer.byteLength(body),
        'Accept':         'application/json',
      },
      timeout: 15_000,
    };

    const req = lib.request(options, (res) => {
      let raw = '';
      res.setEncoding('utf8');
      res.on('data', (chunk) => { raw += chunk; });
      res.on('end', () => {
        try {
          const json = JSON.parse(raw);
          if (json.result === 'error') {
            logger.warn(`WHMCS API error [${action}]: ${json.message}`);
            return reject(new Error(`WHMCS ${action}: ${json.message}`));
          }
          resolve(json);
        } catch (e) {
          reject(new Error(`WHMCS API parse error: ${e.message} — raw: ${raw.slice(0, 200)}`));
        }
      });
    });

    req.on('timeout', () => { req.destroy(); reject(new Error('WHMCS API request timed out')); });
    req.on('error',   (e) => reject(e));
    req.write(body);
    req.end();
  });
}

// ── Convenience wrappers ──────────────────────────────────────────────────────

/**
 * Get basic client info from WHMCS.
 * @param {string|number} clientId  — WHMCS client ID
 */
async function getClient(clientId) {
  return callWhmcs('GetClientsDetails', { clientid: clientId, stats: false });
}

/**
 * Create an invoice in WHMCS for token overage.
 *
 * @param {object} opts
 * @param {string|number} opts.clientId      — WHMCS client ID
 * @param {string}        opts.description   — line-item description
 * @param {number}        opts.amountUsd     — amount in USD (e.g. 4.99)
 * @param {string}        [opts.dueDate]     — YYYY-MM-DD, defaults to today
 * @returns {Promise<{invoiceId: number, total: number}>}
 */
async function createOverageInvoice({ clientId, description, amountUsd, dueDate }) {
  const today = new Date().toISOString().slice(0, 10);
  const result = await callWhmcs('CreateInvoice', {
    userid:            clientId,
    status:            'Unpaid',
    taxed:             0,
    sendinvoice:       1,     // email the customer automatically
    duedate:           dueDate || today,
    'itemdescription1': description,
    'itemamount1':      amountUsd.toFixed(2),
    'itemtaxed1':       0,
  });
  logger.info(`billing: created overage invoice #${result.invoiceid} for client ${clientId} — $${amountUsd.toFixed(2)}`);
  return { invoiceId: result.invoiceid, total: amountUsd };
}

/**
 * Get all invoices for a client (recent first, limit 10).
 * @param {string|number} clientId
 */
async function getClientInvoices(clientId, limit = 10) {
  const result = await callWhmcs('GetInvoices', {
    userid:    clientId,
    limitnum:  limit,
    orderby:   'id',
    order:     'desc',
  });
  return (result.invoices?.invoice || []).map(inv => ({
    id:      inv.id,
    date:    inv.date,
    duedate: inv.duedate,
    total:   inv.total,
    status:  inv.status,
  }));
}

/**
 * Fetch a client's current service details (product name, status, etc.)
 * @param {string|number} clientId
 */
async function getClientProducts(clientId) {
  const result = await callWhmcs('GetClientsProducts', { clientid: clientId });
  return result.products?.product || [];
}

module.exports = { callWhmcs, getClient, createOverageInvoice, getClientInvoices, getClientProducts };
