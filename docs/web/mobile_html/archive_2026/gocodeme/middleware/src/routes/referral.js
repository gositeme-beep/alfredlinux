'use strict';

/**
 * routes/referral.js — Referral Program
 *
 * "Give tokens, get tokens" referral system.
 *
 * How it works:
 *   1. Each user gets a unique referral code (auto-generated or custom)
 *   2. New users sign up with a referral code
 *   3. Both the referrer and referred user get 100K bonus tokens
 *   4. Referral stats are tracked in Redis
 *   5. Dashboard shows referral link, stats, and rewards earned
 *
 * Endpoints:
 *   GET  /api/referral/code           — get or create user's referral code
 *   GET  /api/referral/stats          — referral stats + earnings
 *   POST /api/referral/claim          — claim referral reward (called by webhook on new signup)
 *   POST /api/referral/custom-code    — set a custom referral code
 *   GET  /api/referral/validate/:code — validate a referral code (public, no auth)
 *
 * Redis keys:
 *   referral:code:<clientId>       → unique referral code string
 *   referral:owner:<code>          → clientId (reverse lookup)
 *   referral:referred:<clientId>   → JSON array of { referredClientId, date, rewarded }
 *   referral:referred_by:<clientId>→ referrerClientId (who invited this user)
 *   referral:rewards:<clientId>    → total bonus tokens earned from referrals
 */

const express = require('express');
const router  = express.Router();
const crypto  = require('crypto');

const { requireSession } = require('../auth/middleware');
const REFERRAL_SIGNUP_URL = process.env.REFERRAL_SIGNUP_URL || 'https://gositeme.com/pricing.php';
const tc = require('../tokens/tokenCounter');
const { getRedis } = require('../redis');
const { getClient } = require('../billing/whmcs');
const logger = require('../logger');
const budget = require('../tokens/tokenBudget');
const rateLimit = require('express-rate-limit');

// Reward amounts (tokens + credits)
const REFERRER_REWARD  = 100_000;  // person who referred gets 100K tokens
const REFERRED_REWARD  = 100_000;  // person who was referred gets 100K tokens
const REFERRER_CREDIT  = 5;        // person who referred gets $5 credits
const REFERRED_CREDIT  = 5;        // person who was referred gets $5 credits
const MAX_REFERRALS    = 50;       // max referrals per user (prevent abuse)

// ── Generate a unique referral code ─────────────────────────────────────────
function generateCode() {
  // 8-char alphanumeric code: GCM-XXXXXX
  return 'GCM-' + crypto.randomBytes(4).toString('hex').toUpperCase().slice(0, 6);
}

// ── GET /api/referral/code ──────────────────────────────────────────────────
// Returns the user's referral code (creates one if none exists)
router.get('/code', requireSession, async (req, res) => {
  try {
    const { whmcsClientId } = req.user;
    const redis = getRedis();

    let code = await redis.get(`referral:code:${whmcsClientId}`);
    if (!code) {
      // Generate a new unique code
      code = generateCode();
      // Ensure uniqueness
      while (await redis.get(`referral:owner:${code}`)) {
        code = generateCode();
      }
      await redis.set(`referral:code:${whmcsClientId}`, code);
      await redis.set(`referral:owner:${code}`, whmcsClientId);
      logger.info(`referral: created code ${code} for client ${whmcsClientId}`);
    }

    const referralLink = `${REFERRAL_SIGNUP_URL}?ref=${code}`;

    res.json({
      ok: true,
      code,
      referralLink,
      rewardAmount: REFERRER_REWARD,
      referredRewardAmount: REFERRED_REWARD,
    });
  } catch (err) {
    logger.error(`referral/code: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── POST /api/referral/custom-code ──────────────────────────────────────────
// Let user set a custom referral code (e.g., their name)
router.post('/custom-code', requireSession, async (req, res) => {
  try {
    const { whmcsClientId } = req.user;
    const { code: newCode } = req.body;
    const redis = getRedis();

    if (!newCode || newCode.length < 3 || newCode.length > 20) {
      return res.status(400).json({ ok: false, error: 'Code must be 3-20 characters' });
    }
    // Alphanumeric + hyphens only
    if (!/^[a-zA-Z0-9-]+$/.test(newCode)) {
      return res.status(400).json({ ok: false, error: 'Code must be alphanumeric (hyphens allowed)' });
    }

    const upperCode = newCode.toUpperCase();

    // Check if already taken
    const existingOwner = await redis.get(`referral:owner:${upperCode}`);
    if (existingOwner && existingOwner !== String(whmcsClientId)) {
      return res.status(409).json({ ok: false, error: 'This code is already taken' });
    }

    // Remove old code mapping
    const oldCode = await redis.get(`referral:code:${whmcsClientId}`);
    if (oldCode) {
      await redis.del(`referral:owner:${oldCode}`);
    }

    // Set new code
    await redis.set(`referral:code:${whmcsClientId}`, upperCode);
    await redis.set(`referral:owner:${upperCode}`, whmcsClientId);

    logger.info(`referral: client ${whmcsClientId} set custom code ${upperCode}`);
    res.json({
      ok: true,
      code: upperCode,
      referralLink: `${REFERRAL_SIGNUP_URL}?ref=${upperCode}`,
    });
  } catch (err) {
    logger.error(`referral/custom-code: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── GET /api/referral/stats ─────────────────────────────────────────────────
// Returns referral statistics for the logged-in user
router.get('/stats', requireSession, async (req, res) => {
  try {
    const { whmcsClientId } = req.user;
    const redis = getRedis();

    const code = await redis.get(`referral:code:${whmcsClientId}`);
    const referralsRaw = await redis.get(`referral:referred:${whmcsClientId}`);
    const totalRewards = parseInt(await redis.get(`referral:rewards:${whmcsClientId}`) || '0', 10);
    const referredBy = await redis.get(`referral:referred_by:${whmcsClientId}`);

    let referrals = [];
    try { referrals = JSON.parse(referralsRaw) || []; } catch {}

    res.json({
      ok: true,
      code: code || null,
      referralLink: code ? `${REFERRAL_SIGNUP_URL}?ref=${code}` : null,
      totalReferred: referrals.length,
      totalRewardsEarned: totalRewards,
      maxReferrals: MAX_REFERRALS,
      remainingSlots: Math.max(0, MAX_REFERRALS - referrals.length),
      referredBy: referredBy || null,
      referrals: referrals.map(r => ({
        date: r.date,
        rewarded: r.rewarded,
        // Don't expose the referred client ID for privacy
      })),
      rewardPerReferral: REFERRER_REWARD,
    });
  } catch (err) {
    logger.error(`referral/stats: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── POST /api/referral/claim ────────────────────────────────────────────────
// Called by WHMCS provisioning webhook when a new user signs up with a referral code.
// Body: { referralCode, newClientId }
// Auth: X-WHMCS-Secret header (timing-safe, R3-01)
const { requireWhmcsSecret: _claimWhmcsSecret } = require('../auth/whmcsSecret');
router.post('/claim', _claimWhmcsSecret, async (req, res) => {
  try {
    const { referralCode, newClientId } = req.body;
    if (!referralCode || !newClientId) {
      return res.status(400).json({ ok: false, error: 'referralCode and newClientId required' });
    }

    const redis = getRedis();
    const upperCode = referralCode.toUpperCase();

    // Find the referrer
    const referrerClientId = await redis.get(`referral:owner:${upperCode}`);
    if (!referrerClientId) {
      return res.status(404).json({ ok: false, error: 'Invalid referral code' });
    }

    // Prevent self-referral
    if (String(referrerClientId) === String(newClientId)) {
      return res.status(400).json({ ok: false, error: 'Cannot refer yourself' });
    }

    // Check if already referred
    const existingReferrer = await redis.get(`referral:referred_by:${newClientId}`);
    if (existingReferrer) {
      return res.status(409).json({ ok: false, error: 'User already has a referrer' });
    }

    // Check referral limit
    const referralsRaw = await redis.get(`referral:referred:${referrerClientId}`);
    let referrals = [];
    try { referrals = JSON.parse(referralsRaw) || []; } catch {}

    if (referrals.length >= MAX_REFERRALS) {
      return res.status(429).json({ ok: false, error: 'Referrer has reached maximum referrals' });
    }

    // ── Award tokens ────────────────────────────────────────────────────
    // Referrer gets bonus tokens
    const referrerNewLimit = await tc.addTopUp(referrerClientId, REFERRER_REWARD);
    // Referred user gets bonus tokens
    const referredNewLimit = await tc.addTopUp(newClientId, REFERRED_REWARD);

    // ── Award credits (USD) ─────────────────────────────────────────────
    const referrerCredits = await budget.addCredit(referrerClientId, REFERRER_CREDIT);
    const referredCredits = await budget.addCredit(newClientId, REFERRED_CREDIT);
    logger.info(`referral: credited $${REFERRER_CREDIT} to referrer ${referrerClientId}, $${REFERRED_CREDIT} to new user ${newClientId}`);

    // Track the referral
    referrals.push({
      referredClientId: newClientId,
      date: new Date().toISOString(),
      rewarded: true,
    });
    await redis.set(`referral:referred:${referrerClientId}`, JSON.stringify(referrals));
    await redis.set(`referral:referred_by:${newClientId}`, referrerClientId);

    // Update total rewards earned
    const currentRewards = parseInt(await redis.get(`referral:rewards:${referrerClientId}`) || '0', 10);
    await redis.set(`referral:rewards:${referrerClientId}`, String(currentRewards + REFERRER_REWARD));

    // Send notification emails (fire-and-forget)
    try {
      const { sendEmail } = require('../billing/emailAutomation');
const safeError = require('../utils/safeError');
      const referrerClient = await getClient(referrerClientId);
      sendEmail({
        clientId: referrerClientId,
        subject: '🎉 Referral Reward: 100K Tokens + $5 Credits!',
        body: `Hi ${referrerClient.firstname || 'there'},\n\nGreat news — someone you referred just signed up for GoCodeMe!\n\nWe've added 100,000 bonus tokens and $5.00 in credits to your account as a thank-you. 🎁\n\nYour new token limit: ${referrerNewLimit.toLocaleString()} tokens.\nYour credit balance: $${referrerCredits.toFixed(2)}\n\nKeep sharing your referral link to earn more rewards!\n\n— The GoCodeMe Team`,
      }).catch(() => {});
    } catch {}

    logger.info(`referral: claimed! referrer ${referrerClientId} → new client ${newClientId}. Both rewarded ${REFERRER_REWARD} tokens + $${REFERRER_CREDIT} credits.`);

    res.json({
      ok: true,
      referrerClientId,
      newClientId,
      referrerReward: REFERRER_REWARD,
      referredReward: REFERRED_REWARD,
      referrerNewLimit,
      referredNewLimit,
      referrerCredits,
      referredCredits,
      creditReward: { referrer: REFERRER_CREDIT, referred: REFERRED_CREDIT },
    });
  } catch (err) {
    logger.error(`referral/claim: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── GET /api/referral/validate/:code ────────────────────────────────────────
// Public endpoint — validate a referral code (used on signup page)
// SECURITY (R3 L-03): Rate limit to prevent referral code enumeration
const validateLimiter = rateLimit({ windowMs: 60000, max: 30, keyGenerator: (req) => req.ip });
router.get('/validate/:code', validateLimiter, async (req, res) => {
  try {
    const code = (req.params.code || '').toUpperCase();
    const redis = getRedis();
    const owner = await redis.get(`referral:owner:${code}`);

    if (!owner) {
      return res.json({ ok: true, valid: false });
    }

    res.json({
      ok: true,
      valid: true,
      bonus: REFERRED_REWARD,
      message: `Valid! You'll get ${(REFERRED_REWARD / 1000).toFixed(0)}K bonus tokens when you sign up.`,
    });
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

module.exports = router;
