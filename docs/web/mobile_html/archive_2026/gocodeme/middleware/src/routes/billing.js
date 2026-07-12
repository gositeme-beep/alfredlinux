'use strict';

/**
 * Billing Routes — /api/billing/*
 *
 * Endpoints:
 *   GET  /api/billing/usage-report              — full usage + alert state for customer
 *   POST /api/billing/invoice                   — (admin/internal) manually create overage invoice
 *   GET  /api/billing/invoices                  — list customer's WHMCS invoices
 *   POST /api/billing/reset-alerts              — (WHMCS webhook) reset alert flags on renewal
 *   GET  /api/billing/whmcs-client              — (admin/internal) raw WHMCS client info
 */

const express = require('express');
const router  = express.Router();

const tc      = require('../tokens/tokenCounter');
const { requireSession, requireOwnResource } = require('../auth/middleware');
const { checkAlerts, getAlertState, resetAlerts } = require('../billing/alerts');
const { getClient, createOverageInvoice, getClientInvoices, getClientProducts } = require('../billing/whmcs');
const { getRedis } = require('../redis');
const logger  = require('../logger');
const { requireWhmcsSecret } = require('../auth/whmcsSecret');
const safeError = require('../utils/safeError');

// ── GET /api/billing/usage-report ─────────────────────────────────────────
// Returns complete billing picture for the logged-in customer:
//   token usage, alert state, most recent overage invoice if any.
router.get('/usage-report', requireSession, async (req, res) => {
  try {
    const { whmcsClientId, daUsername } = req.user;
    const [usage, alertState] = await Promise.all([
      tc.getUsage(whmcsClientId),
      getAlertState(whmcsClientId),
    ]);

    res.json({
      ok: true,
      whmcsClientId,
      daUsername,
      usage,
      alerts: alertState,
    });
  } catch (err) {
    logger.error(`billing/usage-report: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── GET /api/billing/invoices ─────────────────────────────────────────────
// Returns the customer's last N invoices from WHMCS (requires real WHMCS API).
router.get('/invoices', requireSession, async (req, res) => {
  try {
    const { whmcsClientId } = req.user;
    const limit = Math.min(parseInt(req.query.limit || '10', 10), 50);
    const invoices = await getClientInvoices(whmcsClientId, limit);
    res.json({ ok: true, invoices });
  } catch (err) {
    // WHMCS API may not be configured in dev — return empty gracefully
    logger.warn(`billing/invoices: ${err.message}`);
    res.json({ ok: true, invoices: [], warning: 'Could not retrieve invoices from billing system' });
  }
});

// ── POST /api/billing/invoice ─────────────────────────────────────────────
// Manually trigger an overage invoice (used by admin / WHMCS cron).
// Body: { whmcsClientId, description, amountUsd }
router.post('/invoice', requireWhmcsSecret, async (req, res) => {
  try {
    const { whmcsClientId, description, amountUsd } = req.body;
    if (!whmcsClientId || !description || !amountUsd) {
      return res.status(400).json({ ok: false, error: 'whmcsClientId, description, amountUsd required' });
    }
    const result = await createOverageInvoice({
      clientId:    whmcsClientId,
      description,
      amountUsd:   parseFloat(amountUsd),
    });
    res.json({ ok: true, ...result });
  } catch (err) {
    logger.error(`billing/invoice: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── POST /api/billing/reset-alerts ───────────────────────────────────────
// Called by WHMCS on renewal (via provisioning module hook).
// Resets the 80%/100% alert dedup flags so fresh alerts fire next month.
router.post('/reset-alerts', requireWhmcsSecret, async (req, res) => {
  try {
    const { whmcsClientId } = req.body;
    if (!whmcsClientId) {
      return res.status(400).json({ ok: false, error: 'whmcsClientId required' });
    }
    await resetAlerts(whmcsClientId);
    res.json({ ok: true });
  } catch (err) {
    logger.error(`billing/reset-alerts: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── GET /api/billing/products ─────────────────────────────────────────────
// Returns the customer's active WHMCS products/services (packages they've purchased).
router.get('/products', requireSession, async (req, res) => {
  try {
    const { whmcsClientId } = req.user;
    const raw = await getClientProducts(whmcsClientId);
    const products = raw.map(p => ({
      id:            p.id,
      name:          p.name || p.translated_name,
      group:         p.groupname || p.translated_groupname,
      status:        p.status,
      billingCycle:  p.billingcycle,
      nextDueDate:   p.nextduedate,
      regDate:       p.regdate,
      domain:        p.domain || null,
      configOptions: (p.configoptions?.configoption || []).map(o => ({
        option: o.option,
        value:  o.value,
      })),
    }));
    res.json({ ok: true, products });
  } catch (err) {
    logger.warn(`billing/products: ${err.message}`);
    res.json({ ok: true, products: [], warning: 'Could not retrieve products from billing system' });
  }
});

// ── GET /api/billing/whmcs-client ─────────────────────────────────────────
// Raw WHMCS client details — useful for dashboard display (name, email, etc.)
router.get('/whmcs-client', requireSession, async (req, res) => {
  try {
    const { whmcsClientId } = req.user;
    const data = await getClient(whmcsClientId);
    res.json({ ok: true, client: data });
  } catch (err) {
    logger.warn(`billing/whmcs-client: ${err.message}`);
    // Gracefully degrade — WHMCS may be unreachable in dev
    res.json({ ok: false, error: safeError(err) });
  }
});

module.exports = router;
