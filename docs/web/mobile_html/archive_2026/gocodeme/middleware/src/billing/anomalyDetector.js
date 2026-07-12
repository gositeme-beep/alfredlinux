'use strict';

/**
 * Usage Anomaly Detector — flags suspicious spending patterns.
 *
 * Checks:
 * 1. Spend velocity — if spend rate in last hour exceeds 5× average
 * 2. Request burst — if request count in 5min exceeds plan rate limit ×3
 * 3. Night owl — high spend during off-hours (02:00–06:00 UTC)
 * 4. New account spike — heavy usage within first 24 hours
 * 5. Model hopping — rapidly switching between expensive models
 *
 * Returns a fraud score (0–100) and an array of triggered flags.
 * Score thresholds: 0-30 clean, 31-60 watch, 61-80 suspicious, 81+ block candidate
 */

const { getRedis } = require('../redis');
const logger = require('../logger');

const SCORE_THRESHOLDS = {
  clean: 30,
  watch: 60,
  suspicious: 80,
  // Above 80 = block candidate
};

const VELOCITY_WINDOW = 3600;      // 1 hour in seconds
const BURST_WINDOW = 300;          // 5 minutes
const BURST_THRESHOLD = 30;        // base burst limit (scaled by plan)
const VELOCITY_MULTIPLIER = 5;     // 5× average = flagged
const NEW_ACCOUNT_HOURS = 24;
const MODEL_HOP_WINDOW = 600;      // 10 minutes
const MODEL_HOP_THRESHOLD = 5;     // 5 different models in 10min = suspicious

// Track each request for anomaly analysis
async function trackRequest(clientId, modelId, costUsd) {
  try {
    const redis = getRedis();
    const now = Date.now();
    const hourKey = `anomaly:reqs:${clientId}`;
    const burstKey = `anomaly:burst:${clientId}`;
    const modelKey = `anomaly:models:${clientId}`;
    const spendKey = `anomaly:spend:${clientId}`;

    // Track request timestamps (sliding window)
    await redis.zadd(hourKey, now, `${now}:${modelId}`);
    await redis.zremrangebyscore(hourKey, 0, now - VELOCITY_WINDOW * 1000);
    await redis.expire(hourKey, VELOCITY_WINDOW + 60);

    // Track burst (5min window)
    await redis.incr(burstKey);
    const burstTTL = await redis.ttl(burstKey);
    if (burstTTL < 0) await redis.expire(burstKey, BURST_WINDOW);

    // Track model usage diversity (10min window)
    await redis.sadd(modelKey, modelId);
    const modelTTL = await redis.ttl(modelKey);
    if (modelTTL < 0) await redis.expire(modelKey, MODEL_HOP_WINDOW);

    // Track hourly spend
    if (costUsd > 0) {
      await redis.incrbyfloat(spendKey, costUsd);
      const spendTTL = await redis.ttl(spendKey);
      if (spendTTL < 0) await redis.expire(spendKey, VELOCITY_WINDOW);
    }
  } catch (err) {
    logger.warn(`anomaly-tracker: ${err.message}`);
  }
}

// Score a user's current session for fraud risk
async function scoreUser(clientId) {
  const flags = [];
  let score = 0;

  try {
    const redis = getRedis();
    const now = Date.now();

    // 1. Spend velocity — compare current hour to 7-day average
    const hourlySpend = parseFloat(await redis.get(`anomaly:spend:${clientId}`) || '0');
    const dailyAvg = parseFloat(await redis.get(`budget:daily:usd:avg:${clientId}`) || '0');
    const hourlyAvg = dailyAvg / 24;
    if (hourlyAvg > 0 && hourlySpend > hourlyAvg * VELOCITY_MULTIPLIER) {
      score += 25;
      flags.push({
        type: 'spend_velocity',
        severity: 'high',
        detail: `$${hourlySpend.toFixed(2)}/hr vs avg $${hourlyAvg.toFixed(2)}/hr (${(hourlySpend / hourlyAvg).toFixed(1)}× normal)`,
      });
    } else if (hourlySpend > 5) {
      // Absolute threshold — $5+ in a single hour is notable
      score += 10;
      flags.push({
        type: 'high_hourly_spend',
        severity: 'medium',
        detail: `$${hourlySpend.toFixed(2)} in last hour`,
      });
    }

    // 2. Request burst — too many requests in 5 minutes
    const burstCount = parseInt(await redis.get(`anomaly:burst:${clientId}`) || '0', 10);
    if (burstCount > BURST_THRESHOLD * 3) {
      score += 20;
      flags.push({
        type: 'request_burst',
        severity: 'high',
        detail: `${burstCount} requests in 5min (threshold: ${BURST_THRESHOLD * 3})`,
      });
    } else if (burstCount > BURST_THRESHOLD) {
      score += 8;
      flags.push({
        type: 'elevated_requests',
        severity: 'low',
        detail: `${burstCount} requests in 5min`,
      });
    }

    // 3. Off-hours usage (02:00–06:00 UTC) — not inherently bad, but adds to score
    const hour = new Date().getUTCHours();
    if (hour >= 2 && hour <= 6 && hourlySpend > 1) {
      score += 10;
      flags.push({
        type: 'off_hours_spend',
        severity: 'low',
        detail: `Active spending at ${hour}:00 UTC ($${hourlySpend.toFixed(2)})`,
      });
    }

    // 4. New account spike — heavy usage within first 24h
    const accountCreated = await redis.get(`account:created:${clientId}`);
    if (accountCreated) {
      const ageMs = now - parseInt(accountCreated, 10);
      if (ageMs < NEW_ACCOUNT_HOURS * 3600 * 1000 && hourlySpend > 2) {
        score += 20;
        flags.push({
          type: 'new_account_spike',
          severity: 'high',
          detail: `Account ${Math.round(ageMs / 3600000)}h old, spending $${hourlySpend.toFixed(2)}/hr`,
        });
      }
    }

    // 5. Model hopping — rapid switching between expensive models
    const uniqueModels = await redis.scard(`anomaly:models:${clientId}`);
    if (uniqueModels >= MODEL_HOP_THRESHOLD) {
      score += 15;
      flags.push({
        type: 'model_hopping',
        severity: 'medium',
        detail: `${uniqueModels} different models in ${MODEL_HOP_WINDOW / 60}min`,
      });
    }

    // 6. Check for previous fraud flags
    const prevScore = parseInt(await redis.get(`fraud:score:${clientId}`) || '0', 10);
    if (prevScore > SCORE_THRESHOLDS.suspicious) {
      score += 10;
      flags.push({
        type: 'repeat_offender',
        severity: 'high',
        detail: `Previous fraud score: ${prevScore}`,
      });
    }

    // Store the current score
    score = Math.min(score, 100);
    await redis.set(`fraud:score:${clientId}`, score, 'EX', 3600);

    // If score is suspicious, add to watchlist
    if (score > SCORE_THRESHOLDS.watch) {
      await redis.zadd('fraud:watchlist', score, String(clientId));
      await redis.expire('fraud:watchlist', 7 * 86400); // keep 7 days
      logger.warn(`anomaly: client ${clientId} fraud score ${score} — ${flags.map(f => f.type).join(', ')}`);
    }

  } catch (err) {
    logger.error(`anomaly-scorer: ${err.message}`);
  }

  return {
    score,
    level: score > SCORE_THRESHOLDS.suspicious ? 'block_candidate'
         : score > SCORE_THRESHOLDS.watch ? 'suspicious'
         : score > SCORE_THRESHOLDS.clean ? 'watch'
         : 'clean',
    flags,
  };
}

// Get the fraud watchlist (owner/admin use)
async function getWatchlist(limit = 50) {
  try {
    const redis = getRedis();
    // Returns highest-scored users first
    const members = await redis.zrevrangebyscore('fraud:watchlist', '+inf', '1', 'WITHSCORES', 'LIMIT', '0', String(limit));
    const results = [];
    for (let i = 0; i < members.length; i += 2) {
      const clientId = members[i];
      const score = parseInt(members[i + 1], 10);
      const flags = [];
      // Retrieve latest flags from tracking keys
      const hourlySpend = parseFloat(await redis.get(`anomaly:spend:${clientId}`) || '0');
      const burstCount = parseInt(await redis.get(`anomaly:burst:${clientId}`) || '0', 10);
      results.push({ clientId, score, hourlySpend, burstCount });
    }
    return results;
  } catch (err) {
    logger.error(`anomaly-watchlist: ${err.message}`);
    return [];
  }
}

// Clear a user's fraud flags (admin action after review)
async function clearFraudScore(clientId) {
  const redis = getRedis();
  await redis.del(`fraud:score:${clientId}`);
  await redis.zrem('fraud:watchlist', String(clientId));
  await redis.del(`anomaly:spend:${clientId}`);
  await redis.del(`anomaly:burst:${clientId}`);
  await redis.del(`anomaly:models:${clientId}`);
  await redis.del(`anomaly:reqs:${clientId}`);
}

module.exports = {
  trackRequest,
  scoreUser,
  getWatchlist,
  clearFraudScore,
  SCORE_THRESHOLDS,
};
