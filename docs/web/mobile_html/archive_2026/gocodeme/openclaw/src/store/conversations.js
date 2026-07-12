'use strict';

/**
 * Conversation Store
 *
 * Persists per-channel conversation history in Redis.
 *
 * Redis keys:
 *   openclaw:conv:<channel>:<channelUserId>   → JSON array of message objects
 *   openclaw:user:<channel>:<channelUserId>   → JSON { daUsername, whmcsClientId, plan }
 *   openclaw:stats:<daUsername>               → JSON { msgCount, lastSeen }
 *
 * Message object:
 *   { role: 'user'|'assistant', content: string, ts: ISO }
 */

const { getRedis } = require('../redis');
const config = require('../config');
const logger = require('../logger');

const CONV_PREFIX = 'openclaw:conv';
const USER_PREFIX = 'openclaw:user';
const STAT_PREFIX = 'openclaw:stats';

function convKey(channel, channelUserId) {
  return `${CONV_PREFIX}:${channel}:${channelUserId}`;
}
function userKey(channel, channelUserId) {
  return `${USER_PREFIX}:${channel}:${channelUserId}`;
}
function statKey(daUsername) {
  return `${STAT_PREFIX}:${daUsername}`;
}

/**
 * Get conversation history for a channel user.
 * Returns [] if no history exists.
 */
async function getHistory(channel, channelUserId) {
  const raw = await getRedis().get(convKey(channel, channelUserId));
  if (!raw) return [];
  try { return JSON.parse(raw); } catch { return []; }
}

/**
 * Append a message to the conversation history.
 * Automatically trims to maxHistory.
 */
async function appendMessage(channel, channelUserId, role, content) {
  const key = convKey(channel, channelUserId);
  const history = await getHistory(channel, channelUserId);

  history.push({ role, content, ts: new Date().toISOString() });

  // Trim oldest messages if over limit
  while (history.length > config.queue.maxHistory) history.shift();

  await getRedis().set(key, JSON.stringify(history), 'EX', config.queue.ttl);
  return history;
}

/**
 * Clear conversation history (user said /reset or /new).
 */
async function clearHistory(channel, channelUserId) {
  await getRedis().del(convKey(channel, channelUserId));
  logger.info(`conv: cleared history for ${channel}:${channelUserId}`);
}

/**
 * Look up the GoCodeMe user linked to this channel user ID.
 * Returns null if not linked.
 */
async function getLinkedUser(channel, channelUserId) {
  const raw = await getRedis().get(userKey(channel, channelUserId));
  if (!raw) return null;
  try { return JSON.parse(raw); } catch { return null; }
}

/**
 * Link a channel user ID to a GoCodeMe account.
 * Called during the /link flow.
 */
async function linkUser(channel, channelUserId, daUsername, whmcsClientId, plan) {
  const data = { daUsername, whmcsClientId, plan, linkedAt: new Date().toISOString() };
  await getRedis().set(userKey(channel, channelUserId), JSON.stringify(data), 'EX', config.queue.ttl);
  logger.info(`conv: linked ${channel}:${channelUserId} → ${daUsername}`);
  return data;
}

/**
 * Unlink a channel user (they called /unlink).
 */
async function unlinkUser(channel, channelUserId) {
  await getRedis().del(userKey(channel, channelUserId));
  await getRedis().del(convKey(channel, channelUserId));
  logger.info(`conv: unlinked ${channel}:${channelUserId}`);
}

/**
 * Increment message count stat for a GoCodeMe user.
 */
async function bumpStats(daUsername) {
  const key = statKey(daUsername);
  const redis = getRedis();
  await redis.hincrby(key, 'msgCount', 1);
  await redis.hset(key, 'lastSeen', new Date().toISOString());
  await redis.expire(key, config.queue.ttl);
}

/**
 * Get stats for a GoCodeMe user (for dashboard widget).
 */
async function getStats(daUsername) {
  const raw = await getRedis().hgetall(statKey(daUsername));
  return {
    msgCount: parseInt(raw?.msgCount || '0', 10),
    lastSeen: raw?.lastSeen || null,
  };
}

/**
 * List all channel links for a GoCodeMe user (across all channels).
 * Scans Redis for keys matching openclaw:user:*
 * Returns [{ channel, channelUserId, linkedAt }]
 */
async function listLinksForUser(daUsername) {
  const redis = getRedis();
  const keys = await redis.keys(`${USER_PREFIX}:*`);
  const links = [];

  for (const key of keys) {
    const raw = await redis.get(key);
    if (!raw) continue;
    try {
      const data = JSON.parse(raw);
      if (data.daUsername === daUsername) {
        // key format: openclaw:user:<channel>:<channelUserId>
        const parts = key.split(':');
        links.push({
          channel: parts[2],
          channelUserId: parts.slice(3).join(':'),
          linkedAt: data.linkedAt,
        });
      }
    } catch { /* skip */ }
  }
  return links;
}

module.exports = {
  getHistory, appendMessage, clearHistory,
  getLinkedUser, linkUser, unlinkUser,
  bumpStats, getStats, listLinksForUser,
};
