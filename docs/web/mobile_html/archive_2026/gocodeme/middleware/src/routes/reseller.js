'use strict';

/**
 * routes/reseller.js — White-Label / Reseller Program
 *
 * Allows resellers to buy AI IDE capacity at wholesale and resell to their clients.
 * Think of it like a hosting reseller but for AI coding IDEs.
 *
 * Reseller Plans (WHMCS products to create):
 *   Reseller Bronze  — 10M tokens, 10 sub-accounts, $399/mo  (60% margin)
 *   Reseller Silver  — 30M tokens, 25 sub-accounts, $899/mo  (65% margin)
 *   Reseller Gold    — 100M tokens, 100 sub-accounts, $2499/mo (70% margin)
 *
 * Features:
 *   - Sub-account management (create, suspend, unsuspend)
 *   - Token allocation per sub-account
 *   - Reseller branding (custom company name, support email, logo URL)
 *   - Revenue tracking + commission dashboard
 *   - Bulk account provisioning
 *
 * Redis Keys:
 *   reseller:<clientId>              → JSON { plan, branding, created, maxAccounts, totalPool }
 *   reseller:accounts:<clientId>     → JSON array of sub-accounts
 *   reseller:branding:<clientId>     → JSON { companyName, supportEmail, logoUrl, primaryColor }
 *   reseller:revenue:<clientId>      → JSON { totalRevenue, thisMonth, commission }
 *
 * Endpoints:
 *   POST /api/reseller/provision        — (WHMCS webhook) provision reseller plan
 *   GET  /api/reseller                  — get reseller dashboard info
 *   PUT  /api/reseller/branding         — update branding settings
 *   POST /api/reseller/accounts         — create a sub-account
 *   GET  /api/reseller/accounts         — list sub-accounts
 *   PATCH /api/reseller/accounts/:id    — update sub-account (tokens, status)
 *   DELETE /api/reseller/accounts/:id   — remove sub-account
 *   GET  /api/reseller/revenue          — revenue & commission report
 *   POST /api/reseller/accounts/bulk    — bulk create sub-accounts
 */

const express = require('express');
const router  = express.Router();
const crypto  = require('crypto');

const { requireSession } = require('../auth/middleware');
const tc = require('../tokens/tokenCounter');
const { getRedis } = require('../redis');
const { callWhmcs, getClient } = require('../billing/whmcs');
const logger = require('../logger');
const { requireWhmcsSecret } = require('../auth/whmcsSecret');
const safeError = require('../utils/safeError');
const RESELLER_PLANS = {
  bronze: { maxAccounts: 10,  totalPool: 10_000_000,   price: 399,  commission: 0.30, pid: 46, name: 'Reseller Bronze' },
  silver: { maxAccounts: 25,  totalPool: 30_000_000,   price: 899,  commission: 0.35, pid: 47, name: 'Reseller Silver' },
  gold:   { maxAccounts: 100, totalPool: 100_000_000,  price: 2499, commission: 0.40, pid: 48, name: 'Reseller Gold' },
};

const TTL_90_DAYS = 90 * 24 * 60 * 60;

// ── Helper: require reseller status ─────────────────────────────────────────
async function requireReseller(req, res, next) {
  const { whmcsClientId } = req.user;
  const redis = getRedis();
  const resellerRaw = await redis.get(`reseller:${whmcsClientId}`);
  if (!resellerRaw) {
    return res.status(403).json({ ok: false, error: 'Reseller account required. Apply at gositeme.com/reseller' });
  }
  req.reseller = JSON.parse(resellerRaw);
  req.reseller.clientId = whmcsClientId;
  next();
}

// ── POST /api/reseller/provision — WHMCS webhook ───────────────────────────
router.post('/provision', requireWhmcsSecret, async (req, res) => {
  try {
    const { whmcsClientId, plan } = req.body;
    if (!whmcsClientId || !plan) {
      return res.status(400).json({ ok: false, error: 'whmcsClientId and plan required' });
    }

    const planConfig = RESELLER_PLANS[plan];
    if (!planConfig) {
      return res.status(400).json({ ok: false, error: `Invalid plan: ${plan}` });
    }

    const redis = getRedis();

    const resellerData = {
      plan,
      maxAccounts: planConfig.maxAccounts,
      totalPool: planConfig.totalPool,
      commission: planConfig.commission,
      created: new Date().toISOString(),
      active: true,
    };

    await redis.setex(`reseller:${whmcsClientId}`, TTL_90_DAYS, JSON.stringify(resellerData));

    // Initialize empty accounts list
    const existingAccounts = await redis.get(`reseller:accounts:${whmcsClientId}`);
    if (!existingAccounts) {
      await redis.set(`reseller:accounts:${whmcsClientId}`, '[]');
    }

    // Initialize branding
    const existingBranding = await redis.get(`reseller:branding:${whmcsClientId}`);
    if (!existingBranding) {
      const client = await getClient(whmcsClientId);
      await redis.set(`reseller:branding:${whmcsClientId}`, JSON.stringify({
        companyName: client.companyname || client.firstname + ' ' + client.lastname,
        supportEmail: client.email,
        logoUrl: null,
        primaryColor: '#2563eb',
      }));
    }

    // Initialize revenue tracking
    await redis.set(`reseller:revenue:${whmcsClientId}`, JSON.stringify({
      totalRevenue: 0,
      thisMonth: 0,
      commission: 0,
      lastUpdated: new Date().toISOString(),
    }));

    logger.info(`reseller: provisioned ${plan} for client ${whmcsClientId}`);

    res.json({
      ok: true,
      plan,
      maxAccounts: planConfig.maxAccounts,
      totalPool: planConfig.totalPool,
    });
  } catch (err) {
    logger.error(`reseller/provision: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── GET /api/reseller — dashboard info ──────────────────────────────────────
router.get('/', requireSession, requireReseller, async (req, res) => {
  try {
    const { whmcsClientId } = req.user;
    const redis = getRedis();

    const accountsRaw = await redis.get(`reseller:accounts:${whmcsClientId}`);
    let accounts = [];
    try { accounts = JSON.parse(accountsRaw) || []; } catch {}

    const brandingRaw = await redis.get(`reseller:branding:${whmcsClientId}`);
    let branding = {};
    try { branding = JSON.parse(brandingRaw) || {}; } catch {}

    const revenueRaw = await redis.get(`reseller:revenue:${whmcsClientId}`);
    let revenue = {};
    try { revenue = JSON.parse(revenueRaw) || {}; } catch {}

    // Calculate total tokens allocated to sub-accounts
    let totalAllocated = 0;
    let totalUsed = 0;
    for (const acc of accounts) {
      totalAllocated += acc.tokenAllocation || 0;
      const usage = await tc.getUsage(acc.clientId || '0');
      totalUsed += usage.used;
    }

    const planConfig = RESELLER_PLANS[req.reseller.plan] || {};

    res.json({
      ok: true,
      reseller: {
        plan: req.reseller.plan,
        planName: planConfig.name || req.reseller.plan,
        maxAccounts: req.reseller.maxAccounts,
        currentAccounts: accounts.length,
        totalPool: req.reseller.totalPool,
        totalAllocated,
        totalUsed,
        poolRemaining: req.reseller.totalPool - totalAllocated,
        percentUsed: req.reseller.totalPool > 0 ? Math.round((totalUsed / req.reseller.totalPool) * 100) : 0,
        active: req.reseller.active,
      },
      branding,
      revenue,
      accounts: accounts.map(a => ({
        id: a.id,
        name: a.name,
        email: a.email,
        clientId: a.clientId,
        tokenAllocation: a.tokenAllocation,
        status: a.status,
        created: a.created,
      })),
    });
  } catch (err) {
    logger.error(`reseller/dashboard: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── PUT /api/reseller/branding — update branding ────────────────────────────
router.put('/branding', requireSession, requireReseller, async (req, res) => {
  try {
    const { whmcsClientId } = req.user;
    const { companyName, supportEmail, logoUrl, primaryColor } = req.body;
    const redis = getRedis();

    const brandingRaw = await redis.get(`reseller:branding:${whmcsClientId}`);
    let branding = {};
    try { branding = JSON.parse(brandingRaw) || {}; } catch {}

    if (companyName !== undefined) branding.companyName = companyName.slice(0, 100);
    if (supportEmail !== undefined) branding.supportEmail = supportEmail.slice(0, 200);
    if (logoUrl !== undefined) {
      if (logoUrl) {
        // SECURITY (R3 L-09): Validate logo URL protocol
        try {
          const u = new URL(logoUrl);
          if (!['http:', 'https:'].includes(u.protocol)) throw new Error('bad proto');
          branding.logoUrl = logoUrl.slice(0, 500);
        } catch {
          return res.status(400).json({ ok: false, error: 'Invalid logo URL — must be http:// or https://' });
        }
      } else {
        branding.logoUrl = null;
      }
    }
    if (primaryColor !== undefined) branding.primaryColor = /^#[0-9a-fA-F]{6}$/.test(primaryColor) ? primaryColor : branding.primaryColor;

    await redis.set(`reseller:branding:${whmcsClientId}`, JSON.stringify(branding));

    logger.info(`reseller: branding updated for ${whmcsClientId}`);
    res.json({ ok: true, branding });
  } catch (err) {
    logger.error(`reseller/branding: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── POST /api/reseller/accounts — create sub-account ────────────────────────
router.post('/accounts', requireSession, requireReseller, async (req, res) => {
  try {
    const { whmcsClientId } = req.user;
    const { name, email, tokenAllocation = 100000 } = req.body;
    const redis = getRedis();

    if (!name || !email) {
      return res.status(400).json({ ok: false, error: 'name and email required' });
    }

    const accountsRaw = await redis.get(`reseller:accounts:${whmcsClientId}`);
    let accounts = [];
    try { accounts = JSON.parse(accountsRaw) || []; } catch {}

    // Check seat limit
    if (accounts.length >= req.reseller.maxAccounts) {
      return res.status(429).json({
        ok: false,
        error: `Maximum ${req.reseller.maxAccounts} sub-accounts. Upgrade your reseller plan.`,
      });
    }

    // Check pool allocation
    const totalAllocated = accounts.reduce((sum, a) => sum + (a.tokenAllocation || 0), 0);
    if (totalAllocated + tokenAllocation > req.reseller.totalPool) {
      return res.status(400).json({
        ok: false,
        error: `Not enough pool tokens. Available: ${(req.reseller.totalPool - totalAllocated).toLocaleString()}`,
      });
    }

    // Create WHMCS client for the sub-account
    let subClientId = null;
    try {
      const result = await callWhmcs('AddClient', {
        firstname: name.split(' ')[0] || name,
        lastname:  name.split(' ').slice(1).join(' ') || 'User',
        email,
        password2: crypto.randomBytes(16).toString('hex'),
        address1:  'Managed by reseller',
        city:      'N/A',
        state:     'N/A',
        postcode:  '00000',
        country:   'US',
        phonenumber: '0000000000',
        notes:     `Reseller sub-account. Reseller ID: ${whmcsClientId}`,
      });
      subClientId = result.clientid;
    } catch (err) {
      logger.error(`reseller: WHMCS AddClient failed: ${err.message}`);
      // Use placeholder if WHMCS fails
      subClientId = `sub_${crypto.randomBytes(4).toString('hex')}`;
    }

    // Set token limit for sub-account
    if (typeof subClientId === 'number' || !String(subClientId).startsWith('sub_')) {
      await redis.set(`tokens:limit:${subClientId}`, String(tokenAllocation));
    }

    const accountId = crypto.randomBytes(8).toString('hex');
    const accountData = {
      id: accountId,
      name,
      email,
      clientId: subClientId,
      tokenAllocation,
      status: 'active',
      created: new Date().toISOString(),
      resellerId: whmcsClientId,
    };

    accounts.push(accountData);
    await redis.set(`reseller:accounts:${whmcsClientId}`, JSON.stringify(accounts));

    // Map sub-account to reseller
    await redis.set(`reseller:by_sub:${subClientId}`, whmcsClientId);

    logger.info(`reseller: created sub-account ${accountId} (${name}) for reseller ${whmcsClientId}`);

    res.json({ ok: true, account: accountData });
  } catch (err) {
    logger.error(`reseller/accounts/create: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── GET /api/reseller/accounts — list sub-accounts ──────────────────────────
router.get('/accounts', requireSession, requireReseller, async (req, res) => {
  try {
    const { whmcsClientId } = req.user;
    const redis = getRedis();

    const accountsRaw = await redis.get(`reseller:accounts:${whmcsClientId}`);
    let accounts = [];
    try { accounts = JSON.parse(accountsRaw) || []; } catch {}

    // Enrich with usage data
    const enriched = [];
    for (const acc of accounts) {
      let usage = { used: 0, limit: 0, percentUsed: 0 };
      try {
        if (acc.clientId) usage = await tc.getUsage(acc.clientId);
      } catch {}

      enriched.push({
        ...acc,
        tokensUsed: usage.used,
        percentUsed: acc.tokenAllocation > 0 ? Math.round((usage.used / acc.tokenAllocation) * 100) : 0,
      });
    }

    res.json({ ok: true, accounts: enriched });
  } catch (err) {
    logger.error(`reseller/accounts/list: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── PATCH /api/reseller/accounts/:id — update sub-account ───────────────────
router.patch('/accounts/:id', requireSession, requireReseller, async (req, res) => {
  try {
    const { whmcsClientId } = req.user;
    const { id } = req.params;
    const { tokenAllocation, status } = req.body;
    const redis = getRedis();

    const accountsRaw = await redis.get(`reseller:accounts:${whmcsClientId}`);
    let accounts = [];
    try { accounts = JSON.parse(accountsRaw) || []; } catch {}

    const account = accounts.find(a => a.id === id);
    if (!account) {
      return res.status(404).json({ ok: false, error: 'Sub-account not found' });
    }

    // Update token allocation
    if (tokenAllocation !== undefined) {
      const newAllocation = parseInt(tokenAllocation, 10);
      const otherAllocated = accounts.reduce((sum, a) => a.id === id ? sum : sum + (a.tokenAllocation || 0), 0);

      if (otherAllocated + newAllocation > req.reseller.totalPool) {
        return res.status(400).json({ ok: false, error: 'Not enough pool tokens' });
      }

      account.tokenAllocation = newAllocation;
      // Update Redis token limit
      if (account.clientId) {
        await redis.set(`tokens:limit:${account.clientId}`, String(newAllocation));
      }
    }

    // Update status
    if (status && ['active', 'suspended'].includes(status)) {
      account.status = status;
      // Suspend/unsuspend in WHMCS
      if (account.clientId && typeof account.clientId === 'number') {
        try {
          if (status === 'suspended') {
            await redis.set(`access:${account.clientId}`, 'suspended');
          } else {
            await redis.set(`access:${account.clientId}`, 'active');
          }
        } catch {}
      }
    }

    await redis.set(`reseller:accounts:${whmcsClientId}`, JSON.stringify(accounts));

    res.json({ ok: true, account });
  } catch (err) {
    logger.error(`reseller/accounts/update: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── DELETE /api/reseller/accounts/:id — remove sub-account ──────────────────
router.delete('/accounts/:id', requireSession, requireReseller, async (req, res) => {
  try {
    const { whmcsClientId } = req.user;
    const { id } = req.params;
    const redis = getRedis();

    const accountsRaw = await redis.get(`reseller:accounts:${whmcsClientId}`);
    let accounts = [];
    try { accounts = JSON.parse(accountsRaw) || []; } catch {}

    const idx = accounts.findIndex(a => a.id === id);
    if (idx < 0) {
      return res.status(404).json({ ok: false, error: 'Sub-account not found' });
    }

    const removed = accounts.splice(idx, 1)[0];
    await redis.set(`reseller:accounts:${whmcsClientId}`, JSON.stringify(accounts));

    // Clean up reverse mapping
    if (removed.clientId) {
      await redis.del(`reseller:by_sub:${removed.clientId}`);
    }

    logger.info(`reseller: removed sub-account ${id} for reseller ${whmcsClientId}`);

    res.json({ ok: true, removed: id });
  } catch (err) {
    logger.error(`reseller/accounts/delete: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── POST /api/reseller/accounts/bulk — bulk create sub-accounts ─────────────
router.post('/accounts/bulk', requireSession, requireReseller, async (req, res) => {
  try {
    const { whmcsClientId } = req.user;
    const { accounts: newAccounts = [] } = req.body;
    const redis = getRedis();

    if (!Array.isArray(newAccounts) || newAccounts.length === 0) {
      return res.status(400).json({ ok: false, error: 'accounts array required' });
    }

    if (newAccounts.length > 50) {
      return res.status(400).json({ ok: false, error: 'Maximum 50 accounts per bulk create' });
    }

    const accountsRaw = await redis.get(`reseller:accounts:${whmcsClientId}`);
    let existingAccounts = [];
    try { existingAccounts = JSON.parse(accountsRaw) || []; } catch {}

    // Check seat limit
    if (existingAccounts.length + newAccounts.length > req.reseller.maxAccounts) {
      return res.status(429).json({
        ok: false,
        error: `Would exceed ${req.reseller.maxAccounts} accounts limit. Available: ${req.reseller.maxAccounts - existingAccounts.length}`,
      });
    }

    // Check total allocationc
    const currentAllocated = existingAccounts.reduce((sum, a) => sum + (a.tokenAllocation || 0), 0);
    const newAllocated = newAccounts.reduce((sum, a) => sum + (a.tokenAllocation || 100000), 0);
    if (currentAllocated + newAllocated > req.reseller.totalPool) {
      return res.status(400).json({ ok: false, error: 'Not enough pool tokens for bulk allocation' });
    }

    const created = [];
    const failed = [];

    for (const acc of newAccounts) {
      try {
        const accountId = crypto.randomBytes(8).toString('hex');
        const tokenAllocation = acc.tokenAllocation || 100000;

        // Create WHMCS client
        let subClientId;
        try {
          const result = await callWhmcs('AddClient', {
            firstname: (acc.name || 'User').split(' ')[0],
            lastname:  (acc.name || 'User').split(' ').slice(1).join(' ') || 'User',
            email:     acc.email,
            password2: crypto.randomBytes(16).toString('hex'),
            address1:  'Managed by reseller',
            city: 'N/A', state: 'N/A', postcode: '00000', country: 'US',
            phonenumber: '0000000000',
            notes: `Reseller sub-account. Reseller ID: ${whmcsClientId}`,
          });
          subClientId = result.clientid;
        } catch {
          subClientId = `sub_${crypto.randomBytes(4).toString('hex')}`;
        }

        if (typeof subClientId === 'number' || !String(subClientId).startsWith('sub_')) {
          await redis.set(`tokens:limit:${subClientId}`, String(tokenAllocation));
        }

        const accountData = {
          id: accountId,
          name: acc.name || 'User',
          email: acc.email,
          clientId: subClientId,
          tokenAllocation,
          status: 'active',
          created: new Date().toISOString(),
          resellerId: whmcsClientId,
        };

        existingAccounts.push(accountData);
        await redis.set(`reseller:by_sub:${subClientId}`, whmcsClientId);
        created.push(accountData);
      } catch (err) {
        failed.push({ email: acc.email, error: safeError(err) });
      }
    }

    await redis.set(`reseller:accounts:${whmcsClientId}`, JSON.stringify(existingAccounts));

    logger.info(`reseller: bulk created ${created.length}/${newAccounts.length} accounts for ${whmcsClientId}`);

    res.json({ ok: true, created: created.length, failed: failed.length, accounts: created, errors: failed });
  } catch (err) {
    logger.error(`reseller/accounts/bulk: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── GET /api/reseller/revenue — revenue & commission report ─────────────────
router.get('/revenue', requireSession, requireReseller, async (req, res) => {
  try {
    const { whmcsClientId } = req.user;
    const redis = getRedis();

    const revenueRaw = await redis.get(`reseller:revenue:${whmcsClientId}`);
    let revenue = {};
    try { revenue = JSON.parse(revenueRaw) || {}; } catch {}

    const accountsRaw = await redis.get(`reseller:accounts:${whmcsClientId}`);
    let accounts = [];
    try { accounts = JSON.parse(accountsRaw) || []; } catch {}

    // Calculate theoretical revenue from sub-accounts
    // (reseller charges their clients whatever they want, we just track our side)
    const planConfig = RESELLER_PLANS[req.reseller.plan] || {};
    const costPerToken = planConfig.price / (planConfig.totalPool || 1);

    let totalSubTokensUsed = 0;
    for (const acc of accounts) {
      if (acc.clientId) {
        try {
          const usage = await tc.getUsage(acc.clientId);
          totalSubTokensUsed += usage.used;
        } catch {}
      }
    }

    // Our cost to serve (estimated)
    const apiCostPer100K = 0.30; // rough average across models
    const ourCost = (totalSubTokensUsed / 100000) * apiCostPer100K;
    const resellerPays = planConfig.price || 0;
    const margin = resellerPays - ourCost;

    res.json({
      ok: true,
      revenue: {
        ...revenue,
        // Calculated fields
        plan: planConfig.name || req.reseller.plan,
        monthlyPlanCost: resellerPays,
        totalSubAccountTokensUsed: totalSubTokensUsed,
        estimatedApiCost: Math.round(ourCost * 100) / 100,
        estimatedMargin: Math.round(margin * 100) / 100,
        activeSubAccounts: accounts.filter(a => a.status === 'active').length,
        commission: planConfig.commission || 0,
      },
    });
  } catch (err) {
    logger.error(`reseller/revenue: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

/**
 * Get reseller branding for a sub-account's client.
 * Used by the IDE to show custom branding if the user belongs to a reseller.
 *
 * @param {string|number} clientId
 * @returns {Promise<object|null>}  branding object or null
 */
async function getResellerBranding(clientId) {
  const redis = getRedis();
  const resellerId = await redis.get(`reseller:by_sub:${clientId}`);
  if (!resellerId) return null;

  const brandingRaw = await redis.get(`reseller:branding:${resellerId}`);
  if (!brandingRaw) return null;

  try { return JSON.parse(brandingRaw); } catch { return null; }
}

module.exports = router;
module.exports.getResellerBranding = getResellerBranding;
module.exports.RESELLER_PLANS = RESELLER_PLANS;
