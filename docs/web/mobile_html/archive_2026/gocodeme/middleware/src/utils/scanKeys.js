'use strict';

/**
 * Redis SCAN helper — replaces all redis.keys() calls.
 *
 * SECURITY (R3 M-03): redis.keys() is O(N) and blocks the single-threaded
 * Redis event loop for the duration of the scan.  In production with
 * thousands of keys this causes latency spikes.  SCAN is cursor-based and
 * returns a batch at a time, avoiding blocking.
 *
 * @param {import('ioredis').Redis} redis  — ioredis client
 * @param {string}                 pattern — glob pattern (e.g. 'launch:sessions:*')
 * @param {number}                [count=100] — hint for how many keys per iteration
 * @returns {Promise<string[]>}    matching key names
 */
async function scanKeys(redis, pattern, count = 100) {
  const results = [];
  let cursor = '0';
  do {
    const [next, keys] = await redis.scan(cursor, 'MATCH', pattern, 'COUNT', count);
    cursor = next;
    results.push(...keys);
  } while (cursor !== '0');
  return results;
}

module.exports = scanKeys;
