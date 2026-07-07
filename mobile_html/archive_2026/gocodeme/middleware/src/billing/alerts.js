'use strict';

/**
 * billing/alerts.js — Token usage alert engine
 *
 * Fires at two thresholds:
 *   80%  → warning push via OpenClaw + optional email via WHMCS SendEmail
 *  100%  → hard-limit hit → overage invoice in WHMCS + push alert
 *
 * Redis keys used (to prevent duplicate alerts in the same billing period):
 *   billing:alert:80:<whmcsClientId>   → "1" (set after 80% alert sent)
 *   billing:alert:100:<whmcsClientId>  → "1" (set after 100% alert sent)
 *   billing:invoice:<whmcsClientId>    → invoiceId (most recent overage invoice)
 *
 * These keys carry a 35-day TTL so they reset naturally with the billing cycle.
 */

const logger  = require('../logger');
const { getRedis } = require('../redis');
const { createOverageInvoice } = require('./whmcs');
const { sendTokenAlertEmail } = require('./emailAutomation');

// Overage price per 100 k tokens over limit (configurable via env)
const OVERAGE_PRICE_USD = parseFloat(process.env.OVERAGE_PRICE_USD || '4.99');
const OVERAGE_TOKEN_BLOCK = parseInt(process.env.OVERAGE_TOKEN_BLOCK || '100000', 10);

const TTL_35_DAYS = 35 * 24 * 60 * 60;

/**
 * Check usage thresholds and fire alerts as needed.
 * Called after every token usage report (non-blocking fire-and-forget).
 *
 * @param {object} opts
 * @param {string|number} opts.whmcsClientId
 * @param {string}        opts.daUsername
 * @param {number}        opts.used           — current total tokens used
 * @param {number}        opts.limit          — monthly token limit
 * @param {number}        opts.percentUsed    — 0-100+
 */
async function checkAlerts({ whmcsClientId, daUsername, used, limit, percentUsed }) {
  if (!limit || limit === 0) return; // unlimited plan — nothing to track

  try {
    const redis = getRedis();

    // ── 50% heads-up ──────────────────────────────────────────────────────
    if (percentUsed >= 50 && percentUsed < 80) {
      const already = await redis.get(`billing:alert:50:${whmcsClientId}`);
      if (!already) {
        await redis.setex(`billing:alert:50:${whmcsClientId}`, TTL_35_DAYS, '1');
        await sendAlert({
          daUsername,
          whmcsClientId,
          level: 'info',
          message: `📊 GoCodeMe: halfway there — you've used ${percentUsed}% of your monthly AI tokens (${used.toLocaleString()} / ${limit.toLocaleString()}). You're on track. No action needed yet.`,
        });
        logger.info(`billing: 50% heads-up sent for client ${whmcsClientId} (${percentUsed}%)`);
        // Send email alongside push
        sendTokenAlertEmail({ whmcsClientId, percentUsed, used, limit, isFree: limit <= 50000 }).catch(() => {});
      }
    }

    // ── 80% warning ────────────────────────────────────────────────────────
    if (percentUsed >= 80 && percentUsed < 100) {
      const already = await redis.get(`billing:alert:80:${whmcsClientId}`);
      if (!already) {
        await redis.setex(`billing:alert:80:${whmcsClientId}`, TTL_35_DAYS, '1');
        await sendAlert({
          daUsername,
          whmcsClientId,
          level: 'warning',
          message: `⚠️ GoCodeMe: you've used ${percentUsed}% of your monthly AI tokens (${used.toLocaleString()} / ${limit.toLocaleString()}). Consider upgrading your plan.`,
        });
        logger.info(`billing: 80% alert sent for client ${whmcsClientId} (${percentUsed}%)`);
        // Send email alongside push
        sendTokenAlertEmail({ whmcsClientId, percentUsed, used, limit, isFree: limit <= 50000 }).catch(() => {});
      }
    }

    // ── 100% hard limit ────────────────────────────────────────────────────
    if (percentUsed >= 100) {
      const already = await redis.get(`billing:alert:100:${whmcsClientId}`);
      if (!already) {
        await redis.setex(`billing:alert:100:${whmcsClientId}`, TTL_35_DAYS, '1');

        // Calculate overage amount
        const overageTokens = used - limit;
        const blocks = Math.ceil(overageTokens / OVERAGE_TOKEN_BLOCK) || 1;
        const amountUsd = blocks * OVERAGE_PRICE_USD;

        // Create WHMCS invoice for overage
        let invoiceId = null;
        try {
          const inv = await createOverageInvoice({
            clientId:    whmcsClientId,
            description: `GoCodeMe Token Overage — ${overageTokens.toLocaleString()} tokens over plan limit`,
            amountUsd,
          });
          invoiceId = inv.invoiceId;
          await redis.setex(`billing:invoice:${whmcsClientId}`, TTL_35_DAYS, String(invoiceId));
        } catch (invErr) {
          logger.error(`billing: invoice creation failed for ${whmcsClientId}: ${invErr.message}`);
          // Don't abort — still send push alert
        }

        await sendAlert({
          daUsername,
          whmcsClientId,
          level: 'critical',
          message: `🚫 GoCodeMe: your monthly AI token limit has been reached (${limit.toLocaleString()} tokens). An overage invoice${invoiceId ? ` (#${invoiceId})` : ''} of $${amountUsd.toFixed(2)} has been created. Top up or upgrade to continue.`,
        });
        logger.info(`billing: 100% alert sent for client ${whmcsClientId}, invoice ${invoiceId}`);
        // Send email alongside push
        sendTokenAlertEmail({ whmcsClientId, percentUsed, used, limit, isFree: limit <= 50000 }).catch(() => {});
      }
    }
  } catch (err) {
    // Never crash the request — billing alerts are fire-and-forget
    logger.error(`billing: checkAlerts error for ${whmcsClientId}: ${err.message}`);
  }
}

/**
 * Send an alert via OpenClaw (linked messaging channel) if available,
 * otherwise log it.
 */
async function sendAlert({ daUsername, whmcsClientId, level, message }) {
  try {
    // Use OpenClaw internal send (same-process require)
    const { sendToLinkedChannels } = require('../openclaw/sender');
    await sendToLinkedChannels(daUsername, message);
    logger.info(`billing: alert sent via OpenClaw to ${daUsername} [${level}]`);
  } catch (err) {
    // OpenClaw not linked / unavailable — log only
    logger.warn(`billing: OpenClaw send failed for ${daUsername}: ${err.message} — message: ${message}`);
  }
}

/**
 * Get the current alert state for a customer.
 * Returns which alerts have already been sent this billing period.
 */
async function getAlertState(whmcsClientId) {
  const redis = getRedis();
  const [a80, a100, invoiceId] = await Promise.all([
    redis.get(`billing:alert:80:${whmcsClientId}`),
    redis.get(`billing:alert:100:${whmcsClientId}`),
    redis.get(`billing:invoice:${whmcsClientId}`),
  ]);
  return {
    warned80:  !!a80,
    warned100: !!a100,
    overageInvoiceId: invoiceId ? parseInt(invoiceId, 10) : null,
  };
}

/**
 * Reset alert flags (called on billing cycle reset / renewal).
 */
async function resetAlerts(whmcsClientId) {
  const redis = getRedis();
  await redis.del(
    `billing:alert:80:${whmcsClientId}`,
    `billing:alert:100:${whmcsClientId}`,
    `billing:invoice:${whmcsClientId}`,
  );
  logger.info(`billing: alerts reset for client ${whmcsClientId}`);
}

module.exports = { checkAlerts, getAlertState, resetAlerts };
