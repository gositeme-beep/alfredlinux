'use strict';

/**
 * onboarding.js — Customer onboarding wizard routes
 *
 * Tracks a 4-step onboarding flow per WHMCS client:
 *   Step 0  Not started (default — wizard shown on first login)
 *   Step 1  Workspace confirmed (customer sees their DA workspace path)
 *   Step 2  Messaging linked (customer linked at least one OpenClaw channel)
 *   Step 3  Test task run (customer ran their first agent task)
 *   Step 4  Complete (wizard dismissed, never shown again)
 *
 * Redis keys:
 *   onboarding:step:<whmcsClientId>  → "0".."4"  (current step; no TTL)
 *   onboarding:done:<whmcsClientId>  → "1"        (wizard fully dismissed)
 *
 * GET  /api/onboarding/status         → { ok, step, done, steps[] }
 * POST /api/onboarding/advance        → { ok, step }   advance 1 step
 * POST /api/onboarding/complete       → { ok }         mark fully done
 * POST /api/onboarding/reset          → { ok }         dev/support reset
 */

const express = require('express');
const router  = express.Router();
const { requireSession } = require('../auth/middleware');
const { getRedis }       = require('../redis');
const logger             = require('../logger');
const safeError = require('../utils/safeError');

// ── Step metadata (sent to client so UI is data-driven) ──────────────────────
const STEPS = [
  {
    id:          0,
    title:       'Welcome to GoCodeMe',
    description: 'Your AI-powered coding environment is ready. Let\'s get you set up in 3 quick steps.',
    action:      null,
  },
  {
    id:          1,
    title:       'Your Workspace',
    description: 'Your code lives in your DirectAdmin home directory. GoCodeMe auto-commits every AI file write so you never lose work.',
    action:      'confirm_workspace',
  },
  {
    id:          2,
    title:       'Connect Messaging',
    description: 'Get token alerts and task updates via Telegram, Discord, or WhatsApp. Click "Get Link Token" then follow the bot instructions.',
    action:      'link_channel',
  },
  {
    id:          3,
    title:       'Run Your First Task',
    description: 'Ask the AI to do something — fix a bug, create a file, or explain your codebase. Launch the IDE and type a task.',
    action:      'launch_ide',
  },
  {
    id:          4,
    title:       'All Done!',
    description: 'You\'re all set. The wizard won\'t appear again unless you reset it.',
    action:      null,
  },
];

// ── Helpers ───────────────────────────────────────────────────────────────────

async function getStep(redis, whmcsClientId) {
  const raw = await redis.get(`onboarding:step:${whmcsClientId}`);
  return raw ? parseInt(raw, 10) : 0;
}

async function isDone(redis, whmcsClientId) {
  return !!(await redis.get(`onboarding:done:${whmcsClientId}`));
}

// ── GET /api/onboarding/status ────────────────────────────────────────────────
router.get('/status', requireSession, async (req, res) => {
  try {
    const { whmcsClientId, daUsername } = req.user;
    const redis  = getRedis();
    const step   = await getStep(redis, whmcsClientId);
    const done   = await isDone(redis, whmcsClientId);

    // Auto-detect step 2 completion: check if user has linked channels
    let effectiveStep = step;
    if (step === 2) {
      const linked = await redis.smembers(`openclaw:links:${daUsername}`);
      if (linked && linked.length > 0) {
        // Auto-advance past step 2 if a channel is already linked
        effectiveStep = 3;
        await redis.set(`onboarding:step:${whmcsClientId}`, '3');
      }
    }

    res.json({
      ok:    true,
      step:  effectiveStep,
      done,
      steps: STEPS,
    });
  } catch (err) {
    logger.error(`onboarding/status error: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── POST /api/onboarding/advance ──────────────────────────────────────────────
router.post('/advance', requireSession, async (req, res) => {
  try {
    const { whmcsClientId } = req.user;
    const redis = getRedis();
    const step  = await getStep(redis, whmcsClientId);

    const nextStep = Math.min(step + 1, STEPS.length - 1);
    await redis.set(`onboarding:step:${whmcsClientId}`, String(nextStep));

    // If reaching final step, auto-mark done
    if (nextStep >= STEPS.length - 1) {
      await redis.set(`onboarding:done:${whmcsClientId}`, '1');
    }

    logger.info(`onboarding: client ${whmcsClientId} advanced to step ${nextStep}`);
    res.json({ ok: true, step: nextStep, done: nextStep >= STEPS.length - 1 });
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── POST /api/onboarding/complete ─────────────────────────────────────────────
router.post('/complete', requireSession, async (req, res) => {
  try {
    const { whmcsClientId } = req.user;
    const redis = getRedis();

    await redis.set(`onboarding:step:${whmcsClientId}`, String(STEPS.length - 1));
    await redis.set(`onboarding:done:${whmcsClientId}`, '1');

    logger.info(`onboarding: client ${whmcsClientId} completed wizard`);
    res.json({ ok: true });
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── POST /api/onboarding/reset (dev / support tool) ───────────────────────────
router.post('/reset', requireSession, async (req, res) => {
  try {
    const { whmcsClientId } = req.user;
    const redis = getRedis();

    await redis.del(
      `onboarding:step:${whmcsClientId}`,
      `onboarding:done:${whmcsClientId}`,
    );

    logger.info(`onboarding: reset for client ${whmcsClientId}`);
    res.json({ ok: true, step: 0, done: false });
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

module.exports = router;
