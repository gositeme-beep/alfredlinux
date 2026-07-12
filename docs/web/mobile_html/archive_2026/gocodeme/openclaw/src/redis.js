'use strict';

const Redis = require('ioredis');
const config = require('./config');
const logger = require('./logger');

let _redis = null;

function getRedis() {
  if (!_redis) {
    _redis = new Redis(config.redis.url, {
      lazyConnect: false,
      maxRetriesPerRequest: 3,
      enableReadyCheck: true,
    });

    _redis.on('connect',   () => logger.info('OpenClaw Redis connected'));
    _redis.on('error',     (err) => logger.error(`OpenClaw Redis error: ${err.message}`));
    _redis.on('reconnecting', () => logger.warn('OpenClaw Redis reconnecting…'));
  }
  return _redis;
}

module.exports = { getRedis };
