'use strict';

/**
 * openclaw/sender.js — Internal OpenClaw message sender
 *
 * Used by the billing alert engine (and any other internal code) to push
 * messages to a customer's linked messaging channels without going through
 * the HTTP route layer.
 *
 * Reads channel links directly from Redis (same source as the route does),
 * then POSTs to the OpenClaw service for each linked account.
 */

const axios  = require('axios');
const logger = require('../logger');
const { getRedis } = require('../redis');

const OPENCLAW_URL = process.env.OPENCLAW_URL || 'http://localhost:3004';

/**
 * Send a text message to all channels linked by a given DA username.
 * Silently skips channels that fail (best-effort delivery).
 *
 * @param {string} daUsername
 * @param {string} text
 * @returns {Promise<number>}  number of channels successfully notified
 */
async function sendToLinkedChannels(daUsername, text) {
  const redis = getRedis();
  const indexKey = `openclaw:links:${daUsername}`;
  const members  = await redis.smembers(indexKey);

  if (!members || members.length === 0) {
    logger.debug(`openclaw/sender: no linked channels for ${daUsername}`);
    return 0;
  }

  let sent = 0;
  for (const member of members) {
    try {
      // member format: "<channel>:<channelUserId>"
      const [channel, ...rest] = member.split(':');
      const channelUserId = rest.join(':'); // re-join in case userId contains ':'

      const resp = await axios.post(
        `${OPENCLAW_URL}/api/openclaw/send`,
        { channel, channelUserId, text },
        { timeout: 8_000 },
      );

      if (resp.data?.ok) {
        sent++;
        logger.debug(`openclaw/sender: sent to ${channel}:${channelUserId} for ${daUsername}`);
      } else {
        logger.warn(`openclaw/sender: send rejected for ${channel}:${channelUserId}: ${JSON.stringify(resp.data)}`);
      }
    } catch (err) {
      logger.warn(`openclaw/sender: send failed for member "${member}": ${err.message}`);
    }
  }

  return sent;
}

module.exports = { sendToLinkedChannels };
