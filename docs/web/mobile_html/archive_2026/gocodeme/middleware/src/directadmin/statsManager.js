'use strict';

/**
 * DirectAdmin Stats / Usage Manager
 *
 * Retrieve account usage statistics like disk, bandwidth, database, and email usage.
 * Uses admin|user impersonation.
 *
 * DA API Endpoints:
 *   CMD_API_SHOW_USER_USAGE    — disk/bandwidth usage
 *   CMD_API_SHOW_USER_CONFIG   — account limits/config
 */

const { createDAClient } = require('./client');
const logger = require('../logger');

/**
 * Get account usage stats (disk, bandwidth, domains, databases, email, etc.)
 *
 * @param {string} daUsername
 * @returns {Promise<object>}  Usage object with quota, used, etc.
 */
async function getUsage(daUsername) {
  const client = createDAClient(daUsername);
  const resp   = await client.get('/CMD_API_SHOW_USER_USAGE');
  const data   = resp.data;

  if (typeof data === 'object') return data;

  // Parse URL-encoded response
  if (typeof data === 'string') {
    const result = {};
    const params = new URLSearchParams(data);
    for (const [key, val] of params) {
      result[key] = isNaN(val) ? val : Number(val);
    }
    return result;
  }
  return {};
}

/**
 * Get account config/limits (max domains, max databases, max email, disk quota, etc.)
 *
 * @param {string} daUsername
 * @returns {Promise<object>}
 */
async function getAccountConfig(daUsername) {
  const client = createDAClient(daUsername);
  const resp   = await client.get('/CMD_API_SHOW_USER_CONFIG');
  const data   = resp.data;

  if (typeof data === 'object') return data;

  if (typeof data === 'string') {
    const result = {};
    const params = new URLSearchParams(data);
    for (const [key, val] of params) {
      result[key] = isNaN(val) ? val : Number(val);
    }
    return result;
  }
  return {};
}

/**
 * Get a combined summary with usage and limits in a human-friendly format.
 *
 * @param {string} daUsername
 * @returns {Promise<object>}
 */
async function getAccountSummary(daUsername) {
  const [usage, config] = await Promise.all([
    getUsage(daUsername),
    getAccountConfig(daUsername),
  ]);

  return {
    username: daUsername,
    disk: {
      used:  usage.quota   || usage.disk || 0,
      limit: config.quota  || 'unlimited',
    },
    bandwidth: {
      used:  usage.bandwidth || 0,
      limit: config.bandwidth || 'unlimited',
    },
    domains: {
      used:  usage.vdomains  || usage.ndomains || 0,
      limit: config.vdomains || 'unlimited',
    },
    subdomains: {
      used:  usage.nsubdomains || 0,
      limit: config.nsubdomains || 'unlimited',
    },
    databases: {
      used:  usage.mysql   || usage.nemailf || 0,
      limit: config.mysql  || 'unlimited',
    },
    email: {
      used:  usage.nemails || usage.pop || 0,
      limit: config.nemails || config.pop || 'unlimited',
    },
    php_version: config.php1_select || config.php_version || 'default',
    suspended: config.suspended === 'yes',
    package: config.package || 'unknown',
  };
}

module.exports = {
  getUsage,
  getAccountConfig,
  getAccountSummary,
};
