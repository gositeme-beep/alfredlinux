'use strict';

/**
 * DirectAdmin User Lookup
 *
 * Maps a WHMCS customer ID to a DirectAdmin username.
 * Two resolution strategies:
 *   1. WHMCS API call — fetch the client's customfields or username stored in WHMCS
 *   2. Direct DA admin API — list users and match by domain or email
 *
 * The lookup result is cached in Redis to avoid repeated API calls on every request.
 */

const axios = require('axios');
const qs = require('querystring');
const config = require('../config');
const logger = require('../logger');
const { getRedis } = require('../redis');

const CACHE_TTL_SECONDS = 300; // 5 minutes

/**
 * Fetch a client's DirectAdmin username from WHMCS by WHMCS client ID.
 * Expects a WHMCS custom field named "DirectAdmin Username" on the GoCodeMe product.
 * Falls back to trying the WHMCS username itself as the DA username.
 *
 * @param {number|string} whmcsClientId
 * @returns {Promise<string>}  DirectAdmin username
 */
async function getDaUsernameByWhmcsId(whmcsClientId) {
  const cacheKey = `da_username:${whmcsClientId}`;
  const redis = getRedis();

  // Try cache first
  const cached = await redis.get(cacheKey);
  if (cached) {
    logger.debug(`userLookup: cache hit for WHMCS client ${whmcsClientId} → ${cached}`);
    return cached;
  }

  // Fetch from WHMCS API
  const params = {
    action: 'GetClientsDetails',
    identifier: config.whmcs.identifier,
    secret: config.whmcs.secret,
    clientid: whmcsClientId,
    stats: false,
    responsetype: 'json',
  };

  const response = await axios.post(config.whmcs.apiUrl, qs.stringify(params), {
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    timeout: 10000,
  });

  const data = response.data;

  if (data.result !== 'success') {
    throw new Error(`WHMCS GetClientsDetails failed: ${data.message}`);
  }

  // Try the customfield "DirectAdmin Username" first
  let daUsername = null;
  const customfields = data.customfields?.customfield || [];
  for (const field of customfields) {
    if (field.name?.toLowerCase().includes('directadmin') || field.name?.toLowerCase().includes('da username')) {
      daUsername = field.value?.trim() || null;
      break;
    }
  }

  // If no custom field, try the WHMCS hosting service username (tblhosting.username)
  // This is the actual DA username set during provisioning.
  if (!daUsername) {
    try {
      const svcParams = {
        action: 'GetClientsProducts',
        identifier: config.whmcs.identifier,
        secret: config.whmcs.secret,
        clientid: whmcsClientId,
        responsetype: 'json',
      };
      const svcResponse = await axios.post(config.whmcs.apiUrl, qs.stringify(svcParams), {
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        timeout: 10000,
      });
      const products = svcResponse.data?.products?.product || [];
      // Find the GoCodeMe service first (server module = 'gocodeme'),
      // then fall back to any active hosting service on this server.
      // This prevents picking up DA usernames from unrelated hosting
      // on different servers (e.g. 'hydrogro' on server 1 vs 'gositeme' on server 5).
      const gocodemeProd = products.find(p => p.username && p.status === 'Active' && p.servermodule === 'gocodeme');
      const hostingProd  = products.find(p => p.username && p.status === 'Active' && p.servermodule === 'directadmin');
      const bestProd = gocodemeProd || hostingProd;
      if (bestProd) {
        daUsername = bestProd.username.trim();
        logger.info(`userLookup: found DA username from ${bestProd.servermodule} service #${bestProd.id}: ${daUsername}`);
      }
    } catch (err) {
      logger.warn(`userLookup: GetClientsProducts failed for client ${whmcsClientId}: ${err.message}`);
    }
  }

  // Last resort: fall back to the WHMCS client username (often wrong for DA)
  if (!daUsername) {
    daUsername = data.username?.trim() || null;
  }

  if (!daUsername) {
    throw new Error(`No DirectAdmin username found for WHMCS client ${whmcsClientId}`);
  }

  // Cache it
  await redis.setex(cacheKey, CACHE_TTL_SECONDS, daUsername);

  logger.info(`userLookup: resolved WHMCS client ${whmcsClientId} → DA user ${daUsername}`);
  return daUsername;
}

/**
 * Invalidate the cached DA username for a WHMCS client.
 * Call this when a customer account changes.
 *
 * @param {number|string} whmcsClientId
 */
async function invalidateCache(whmcsClientId) {
  const redis = getRedis();
  await redis.del(`da_username:${whmcsClientId}`);
}

module.exports = { getDaUsernameByWhmcsId, invalidateCache };
