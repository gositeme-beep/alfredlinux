'use strict';

const Redis = require('ioredis');
const config = require('./config');
const logger = require('./logger');

let redisClient = null;

function getRedis() {
  if (!redisClient) {
    redisClient = new Redis(config.redis.url, {
      maxRetriesPerRequest: 3,
      enableReadyCheck: true,
      lazyConnect: false,
    });

    redisClient.on('connect', () => logger.info('Redis connected'));
    redisClient.on('error', (err) => logger.error(`Redis error: ${err.message}`));
    redisClient.on('close', () => logger.warn('Redis connection closed'));
  }

  return redisClient;
}

async function closeRedis() {
  if (redisClient) {
    await redisClient.quit();
    redisClient = null;
  }
}

module.exports = { getRedis, closeRedis };
