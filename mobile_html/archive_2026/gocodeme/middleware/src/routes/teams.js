'use strict';

/**
 * routes/teams.js — Team/Org Plans with Shared Token Pools
 *
 * Allows organizations to buy a team plan with a shared token pool.
 * Team admins can invite members, set per-member limits, and view team usage.
 *
 * Team Plans (WHMCS products to be created):
 *   Team 5   — 5 seats, 2M shared tokens, $149/mo
 *   Team 10  — 10 seats, 5M shared tokens, $279/mo
 *   Team 25  — 25 seats, 15M shared tokens, $599/mo
 *   Custom   — via Enterprise inquiry
 *
 * Redis Keys:
 *   team:<teamId>              → JSON { name, ownerClientId, plan, maxSeats, sharedPool, created }
 *   team:members:<teamId>      → JSON array of { clientId, role, addedDate, memberLimit }
 *   team:by_client:<clientId>  → teamId (reverse lookup: which team is this user in?)
 *   team:by_owner:<clientId>   → teamId (teams owned by this client)
 *   team:used:<teamId>         → integer — shared pool tokens consumed
 *   team:invites:<teamId>      → JSON array of { email, code, created, claimed }
 *
 * Token Counting Integration:
 *   When a team member makes an API call, the middleware checks:
 *   1. Personal limit first (if set by team admin)
 *   2. Then shared team pool
 *   3. Team-level overage applies (same $2/100K rate)
 *
 * Endpoints:
 *   POST /api/teams                     — create a team (team plan owners only)
 *   GET  /api/teams                     — get team info + members
 *   PUT  /api/teams                     — update team settings
 *   POST /api/teams/invite              — invite a member by email
 *   POST /api/teams/accept              — accept a team invitation
 *   DELETE /api/teams/member/:clientId  — remove a member
 *   PATCH /api/teams/member/:clientId   — update member settings (limit, role)
 *   GET  /api/teams/usage               — team usage breakdown by member
 *   POST /api/teams/provision           — (WHMCS webhook) provision team plan
 */

const express = require('express');
const router  = express.Router();
const crypto  = require('crypto');

const { requireSession } = require('../auth/middleware');
const TEAM_INVITE_BASE_URL = process.env.TEAM_INVITE_BASE_URL || 'https://gocodeme.com/middleware/dashboard?teamInvite=';
const tc = require('../tokens/tokenCounter');
const { getRedis } = require('../redis');
const { getClient, callWhmcs } = require('../billing/whmcs');
const { sendEmail } = require('../billing/emailAutomation');
const logger = require('../logger');
const { requireWhmcsSecret } = require('../auth/whmcsSecret');
const safeError = require('../utils/safeError');
const TEAM_PLANS = {
  team5:  { maxSeats: 5,  sharedPool: 2_000_000,  price: 149, pid: 40, name: 'Team 5' },
  team10: { maxSeats: 10, sharedPool: 5_000_000,  price: 279, pid: 41, name: 'Team 10' },
  team25: { maxSeats: 25, sharedPool: 15_000_000, price: 599, pid: 42, name: 'Team 25' },
};

const TTL_90_DAYS = 90 * 24 * 60 * 60;
const INVITE_TTL  = 7 * 24 * 60 * 60; // 7 days

// ── Helper: get team for a client ───────────────────────────────────────────
async function getTeamForClient(clientId) {
  const redis = getRedis();
  const teamId = await redis.get(`team:by_client:${clientId}`) || await redis.get(`team:by_owner:${clientId}`);
  if (!teamId) return null;

  const teamRaw = await redis.get(`team:${teamId}`);
  if (!teamRaw) return null;

  const team = JSON.parse(teamRaw);
  team.id = teamId;

  const membersRaw = await redis.get(`team:members:${teamId}`);
  team.members = [];
  try { team.members = JSON.parse(membersRaw) || []; } catch {}

  const used = parseInt(await redis.get(`team:used:${teamId}`) || '0', 10);
  team.tokensUsed = used;

  return team;
}

// ── Helper: check if client is team admin ───────────────────────────────────
async function isTeamAdmin(clientId, teamId) {
  const redis = getRedis();
  const teamRaw = await redis.get(`team:${teamId}`);
  if (!teamRaw) return false;
  const team = JSON.parse(teamRaw);
  if (String(team.ownerClientId) === String(clientId)) return true;

  // Check admin role
  const membersRaw = await redis.get(`team:members:${teamId}`);
  let members = [];
  try { members = JSON.parse(membersRaw) || []; } catch {}
  return members.some(m => String(m.clientId) === String(clientId) && m.role === 'admin');
}

function utcDate() {
  return new Date().toISOString().slice(0, 10);
}

function utcMonth() {
  return new Date().toISOString().slice(0, 7);
}

async function getTeamBudget(teamId) {
  const redis = getRedis();
  const raw = await redis.get(`team:budget:${teamId}`);
  if (!raw) {
    return {
      enforce: false,
      dailyTokenCap: 0,
      dailyUsdCap: 0,
      monthlyTokenCap: 0,
    };
  }
  try {
    return JSON.parse(raw);
  } catch {
    return {
      enforce: false,
      dailyTokenCap: 0,
      dailyUsdCap: 0,
      monthlyTokenCap: 0,
    };
  }
}

async function getTeamBudgetStatus(teamId) {
  const redis = getRedis();
  const budget = await getTeamBudget(teamId);
  const dailyTokens = parseInt((await redis.get(`team:budget:daily:tokens:${teamId}:${utcDate()}`)) || '0', 10);
  const dailyUsd = parseFloat((await redis.get(`team:budget:daily:usd:${teamId}:${utcDate()}`)) || '0');
  const monthlyTokens = parseInt((await redis.get(`team:budget:monthly:tokens:${teamId}:${utcMonth()}`)) || '0', 10);
  return { budget, usage: { dailyTokens, dailyUsd, monthlyTokens } };
}

// ── POST /api/teams — create a team ─────────────────────────────────────────
router.post('/', requireSession, async (req, res) => {
  try {
    const { whmcsClientId } = req.user;
    const { name, plan } = req.body;
    const redis = getRedis();

    if (!name || !plan) {
      return res.status(400).json({ ok: false, error: 'name and plan required' });
    }

    const planConfig = TEAM_PLANS[plan];
    if (!planConfig) {
      return res.status(400).json({ ok: false, error: `Invalid plan. Choose: ${Object.keys(TEAM_PLANS).join(', ')}` });
    }

    // Check if already owns a team
    const existingTeam = await redis.get(`team:by_owner:${whmcsClientId}`);
    if (existingTeam) {
      return res.status(409).json({ ok: false, error: 'You already own a team' });
    }

    // Create team
    const teamId = crypto.randomBytes(8).toString('hex');
    const teamData = {
      name,
      ownerClientId: whmcsClientId,
      plan,
      maxSeats: planConfig.maxSeats,
      sharedPool: planConfig.sharedPool,
      created: new Date().toISOString(),
    };

    await redis.setex(`team:${teamId}`, TTL_90_DAYS, JSON.stringify(teamData));
    await redis.set(`team:by_owner:${whmcsClientId}`, teamId);
    await redis.set(`team:by_client:${whmcsClientId}`, teamId);
    await redis.set(`team:members:${teamId}`, JSON.stringify([
      { clientId: whmcsClientId, role: 'owner', addedDate: new Date().toISOString(), memberLimit: 0 },
    ]));
    await redis.set(`team:used:${teamId}`, '0');

    logger.info(`teams: created team ${teamId} (${name}) by client ${whmcsClientId}, plan ${plan}`);

    res.json({
      ok: true,
      teamId,
      team: { ...teamData, id: teamId, tokensUsed: 0, members: 1 },
    });
  } catch (err) {
    logger.error(`teams/create: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── GET /api/teams — get team info ──────────────────────────────────────────
router.get('/', requireSession, async (req, res) => {
  try {
    const { whmcsClientId } = req.user;
    const team = await getTeamForClient(whmcsClientId);

    if (!team) {
      return res.json({ ok: true, team: null, message: 'You are not in a team' });
    }

    const planConfig = TEAM_PLANS[team.plan] || {};

    res.json({
      ok: true,
      team: {
        id: team.id,
        name: team.name,
        plan: team.plan,
        planName: planConfig.name || team.plan,
        maxSeats: team.maxSeats,
        currentSeats: team.members.length,
        sharedPool: team.sharedPool,
        tokensUsed: team.tokensUsed,
        percentUsed: team.sharedPool > 0 ? Math.round((team.tokensUsed / team.sharedPool) * 100) : 0,
        isOwner: String(team.ownerClientId) === String(whmcsClientId),
        members: team.members.map(m => ({
          clientId: m.clientId,
          role: m.role,
          addedDate: m.addedDate,
          memberLimit: m.memberLimit || 0,
        })),
        created: team.created,
      },
    });
  } catch (err) {
    logger.error(`teams/get: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── POST /api/teams/invite — invite a member ────────────────────────────────
router.post('/invite', requireSession, async (req, res) => {
  try {
    const { whmcsClientId } = req.user;
    const { email } = req.body;
    const redis = getRedis();

    if (!email) {
      return res.status(400).json({ ok: false, error: 'email required' });
    }

    const team = await getTeamForClient(whmcsClientId);
    if (!team) {
      return res.status(404).json({ ok: false, error: 'You are not in a team' });
    }

    // Check admin rights
    if (!await isTeamAdmin(whmcsClientId, team.id)) {
      return res.status(403).json({ ok: false, error: 'Only team admins can invite members' });
    }

    // Check seat limit
    if (team.members.length >= team.maxSeats) {
      return res.status(429).json({ ok: false, error: `Team is full (${team.maxSeats} seats). Upgrade your team plan to add more.` });
    }

    // Generate invite code
    const inviteCode = crypto.randomBytes(16).toString('hex');
    const invitesRaw = await redis.get(`team:invites:${team.id}`);
    let invites = [];
    try { invites = JSON.parse(invitesRaw) || []; } catch {}

    invites.push({
      email,
      code: inviteCode,
      created: new Date().toISOString(),
      claimed: false,
    });

    await redis.setex(`team:invites:${team.id}`, TTL_90_DAYS, JSON.stringify(invites));
    // Store invite code lookup
    await redis.setex(`team:invite:${inviteCode}`, INVITE_TTL, JSON.stringify({
      teamId: team.id,
      email,
    }));

    // Send invitation email
    sendEmail({
      clientId: whmcsClientId, // send from team owner's context
      subject: `You've been invited to join "${team.name}" on GoCodeMe!`,
      body: `Hi,\n\nYou've been invited to join the "${team.name}" team on GoCodeMe - an AI-powered coding IDE.\n\nAccept your invitation: ${TEAM_INVITE_BASE_URL}${inviteCode}\n\nThis invite expires in 7 days.\n\n- The GoCodeMe Team`,
      email,
    }).catch(err => logger.warn(`teams: invite email failed: ${err.message}`));

    logger.info(`teams: invite sent to ${email} for team ${team.id} by client ${whmcsClientId}`);

    res.json({
      ok: true,
      inviteCode,
      email,
      expiresIn: '7 days',
    });
  } catch (err) {
    logger.error(`teams/invite: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── POST /api/teams/accept — accept a team invitation ───────────────────────
router.post('/accept', requireSession, async (req, res) => {
  try {
    const { whmcsClientId } = req.user;
    const { code } = req.body;
    const redis = getRedis();

    if (!code) {
      return res.status(400).json({ ok: false, error: 'invite code required' });
    }

    // Validate invite
    const inviteRaw = await redis.get(`team:invite:${code}`);
    if (!inviteRaw) {
      return res.status(404).json({ ok: false, error: 'Invalid or expired invite code' });
    }

    const invite = JSON.parse(inviteRaw);

    // SECURITY (R2-08): Verify accepting user's email matches invite email
    // Prevents invite code theft — only the intended recipient can accept
    if (invite.email) {
      const { getClient } = require('../billing/whmcs');
      try {
        const client = await getClient(whmcsClientId);
        const userEmail = (client.email || '').toLowerCase().trim();
        const inviteEmail = (invite.email || '').toLowerCase().trim();
        if (userEmail !== inviteEmail) {
          logger.warn(`teams/accept: email mismatch — invite for ${inviteEmail}, accepted by ${userEmail} (client ${whmcsClientId})`);
          return res.status(403).json({ ok: false, error: 'This invite was sent to a different email address' });
        }
      } catch (emailErr) {
        logger.error(`teams/accept: email verification failed: ${emailErr.message}`);
        return res.status(500).json({ ok: false, error: 'Could not verify email — try again' });
      }
    }

    // Check not already in a team
    const existingTeam = await redis.get(`team:by_client:${whmcsClientId}`);
    if (existingTeam) {
      return res.status(409).json({ ok: false, error: 'You are already in a team. Leave your current team first.' });
    }

    // Load team and check seats
    const teamRaw = await redis.get(`team:${invite.teamId}`);
    if (!teamRaw) {
      return res.status(404).json({ ok: false, error: 'Team no longer exists' });
    }

    const team = JSON.parse(teamRaw);
    const membersRaw = await redis.get(`team:members:${invite.teamId}`);
    let members = [];
    try { members = JSON.parse(membersRaw) || []; } catch {}

    if (members.length >= team.maxSeats) {
      return res.status(429).json({ ok: false, error: 'Team is full' });
    }

    // Add member
    members.push({
      clientId: whmcsClientId,
      role: 'member',
      addedDate: new Date().toISOString(),
      memberLimit: 0, // 0 = no per-member limit (uses shared pool)
    });

    await redis.set(`team:members:${invite.teamId}`, JSON.stringify(members));
    await redis.set(`team:by_client:${whmcsClientId}`, invite.teamId);

    // Clean up invite
    await redis.del(`team:invite:${code}`);

    // Mark invite as claimed
    const invitesRaw = await redis.get(`team:invites:${invite.teamId}`);
    let invites = [];
    try { invites = JSON.parse(invitesRaw) || []; } catch {}
    const idx = invites.findIndex(i => i.code === code);
    if (idx >= 0) {
      invites[idx].claimed = true;
      await redis.setex(`team:invites:${invite.teamId}`, TTL_90_DAYS, JSON.stringify(invites));
    }

    logger.info(`teams: client ${whmcsClientId} joined team ${invite.teamId}`);

    res.json({
      ok: true,
      teamId: invite.teamId,
      teamName: team.name,
      role: 'member',
    });
  } catch (err) {
    logger.error(`teams/accept: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── DELETE /api/teams/member/:clientId — remove a member ────────────────────
router.delete('/member/:clientId', requireSession, async (req, res) => {
  try {
    const { whmcsClientId } = req.user;
    const targetClientId = req.params.clientId;
    const redis = getRedis();

    const team = await getTeamForClient(whmcsClientId);
    if (!team) {
      return res.status(404).json({ ok: false, error: 'You are not in a team' });
    }

    // Must be admin, or removing yourself
    const isAdmin = await isTeamAdmin(whmcsClientId, team.id);
    if (!isAdmin && String(whmcsClientId) !== String(targetClientId)) {
      return res.status(403).json({ ok: false, error: 'Only admins can remove members' });
    }

    // Can't remove the owner
    if (String(targetClientId) === String(team.ownerClientId)) {
      return res.status(400).json({ ok: false, error: 'Cannot remove the team owner. Transfer ownership first.' });
    }

    // Remove member
    const membersRaw = await redis.get(`team:members:${team.id}`);
    let members = [];
    try { members = JSON.parse(membersRaw) || []; } catch {}

    const newMembers = members.filter(m => String(m.clientId) !== String(targetClientId));
    await redis.set(`team:members:${team.id}`, JSON.stringify(newMembers));
    await redis.del(`team:by_client:${targetClientId}`);

    logger.info(`teams: client ${targetClientId} removed from team ${team.id} by ${whmcsClientId}`);

    res.json({ ok: true, removed: targetClientId });
  } catch (err) {
    logger.error(`teams/remove: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── PATCH /api/teams/member/:clientId — update member settings ──────────────
router.patch('/member/:clientId', requireSession, async (req, res) => {
  try {
    const { whmcsClientId } = req.user;
    const targetClientId = req.params.clientId;
    const { role, memberLimit } = req.body;
    const redis = getRedis();

    const team = await getTeamForClient(whmcsClientId);
    if (!team) {
      return res.status(404).json({ ok: false, error: 'You are not in a team' });
    }

    if (!await isTeamAdmin(whmcsClientId, team.id)) {
      return res.status(403).json({ ok: false, error: 'Only admins can update member settings' });
    }

    const membersRaw = await redis.get(`team:members:${team.id}`);
    let members = [];
    try { members = JSON.parse(membersRaw) || []; } catch {}

    const member = members.find(m => String(m.clientId) === String(targetClientId));
    if (!member) {
      return res.status(404).json({ ok: false, error: 'Member not found' });
    }

    if (role && ['admin', 'member'].includes(role)) {
      member.role = role;
    }
    if (memberLimit !== undefined) {
      member.memberLimit = Math.max(0, parseInt(memberLimit, 10) || 0);
    }

    await redis.set(`team:members:${team.id}`, JSON.stringify(members));

    res.json({ ok: true, member: { clientId: targetClientId, role: member.role, memberLimit: member.memberLimit } });
  } catch (err) {
    logger.error(`teams/update-member: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── GET /api/teams/usage — team usage breakdown ─────────────────────────────
router.get('/usage', requireSession, async (req, res) => {
  try {
    const { whmcsClientId } = req.user;
    const redis = getRedis();

    const team = await getTeamForClient(whmcsClientId);
    if (!team) {
      return res.status(404).json({ ok: false, error: 'You are not in a team' });
    }

    // Get per-member usage within the team
    const memberUsage = [];
    for (const m of team.members) {
      const usage = await tc.getUsage(m.clientId);
      const daUser = await redis.get(`da_username:${m.clientId}`) || '(unmapped)';
      memberUsage.push({
        clientId: m.clientId,
        daUsername: daUser,
        role: m.role,
        memberLimit: m.memberLimit || 0,
        tokensUsed: usage.used,
        percentOfPool: team.sharedPool > 0 ? Math.round((usage.used / team.sharedPool) * 100) : 0,
      });
    }

    memberUsage.sort((a, b) => b.tokensUsed - a.tokensUsed);

    const planConfig = TEAM_PLANS[team.plan] || {};

    const budgetStatus = await getTeamBudgetStatus(team.id);

    res.json({
      ok: true,
      team: {
        id: team.id,
        name: team.name,
        plan: team.plan,
        planName: planConfig.name || team.plan,
      },
      pool: {
        total: team.sharedPool,
        used: team.tokensUsed,
        percentUsed: team.sharedPool > 0 ? Math.round((team.tokensUsed / team.sharedPool) * 100) : 0,
        remaining: Math.max(0, team.sharedPool - team.tokensUsed),
      },
      budget: budgetStatus,
      members: memberUsage,
    });
  } catch (err) {
    logger.error(`teams/usage: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── GET /api/teams/budget — team budget controls/status ───────────────────
router.get('/budget', requireSession, async (req, res) => {
  try {
    const { whmcsClientId } = req.user;
    const team = await getTeamForClient(whmcsClientId);
    if (!team) {
      return res.status(404).json({ ok: false, error: 'You are not in a team' });
    }
    if (!await isTeamAdmin(whmcsClientId, team.id)) {
      return res.status(403).json({ ok: false, error: 'Only team admins can view team budget controls' });
    }
    const status = await getTeamBudgetStatus(team.id);
    res.json({ ok: true, teamId: team.id, ...status });
  } catch (err) {
    logger.error(`teams/budget:get: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── PUT /api/teams/budget — update team budget controls ───────────────────
router.put('/budget', requireSession, async (req, res) => {
  try {
    const { whmcsClientId } = req.user;
    const team = await getTeamForClient(whmcsClientId);
    if (!team) {
      return res.status(404).json({ ok: false, error: 'You are not in a team' });
    }
    if (!await isTeamAdmin(whmcsClientId, team.id)) {
      return res.status(403).json({ ok: false, error: 'Only team admins can update team budget controls' });
    }

    const enforce = !!req.body?.enforce;
    const dailyTokenCap = Math.max(0, parseInt(req.body?.dailyTokenCap || '0', 10));
    const dailyUsdCap = Math.max(0, parseFloat(req.body?.dailyUsdCap || '0'));
    const monthlyTokenCap = Math.max(0, parseInt(req.body?.monthlyTokenCap || '0', 10));

    const budget = {
      enforce,
      dailyTokenCap,
      dailyUsdCap: Math.round(dailyUsdCap * 100) / 100,
      monthlyTokenCap,
      updatedAt: new Date().toISOString(),
      updatedBy: String(whmcsClientId),
    };

    await getRedis().set(`team:budget:${team.id}`, JSON.stringify(budget));
    const status = await getTeamBudgetStatus(team.id);
    res.json({ ok: true, teamId: team.id, ...status });
  } catch (err) {
    logger.error(`teams/budget:update: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── POST /api/teams/provision — WHMCS webhook to provision team ─────────────
// Called when a team plan is purchased in WHMCS.
// Body: { whmcsClientId, plan (team5|team10|team25), teamName }
router.post('/provision', requireWhmcsSecret, async (req, res) => {
  try {
    const { whmcsClientId, plan, teamName } = req.body;
    if (!whmcsClientId || !plan) {
      return res.status(400).json({ ok: false, error: 'whmcsClientId and plan required' });
    }

    const planConfig = TEAM_PLANS[plan];
    if (!planConfig) {
      return res.status(400).json({ ok: false, error: `Invalid plan: ${plan}` });
    }

    const redis = getRedis();

    // Check if already owns a team
    let teamId = await redis.get(`team:by_owner:${whmcsClientId}`);
    if (teamId) {
      // Upgrade existing team
      const teamRaw = await redis.get(`team:${teamId}`);
      if (teamRaw) {
        const team = JSON.parse(teamRaw);
        team.plan = plan;
        team.maxSeats = planConfig.maxSeats;
        team.sharedPool = planConfig.sharedPool;
        await redis.setex(`team:${teamId}`, TTL_90_DAYS, JSON.stringify(team));
        logger.info(`teams: upgraded team ${teamId} to ${plan}`);
        return res.json({ ok: true, teamId, action: 'upgraded', plan });
      }
    }

    // Create new team
    teamId = crypto.randomBytes(8).toString('hex');
    const teamData = {
      name: teamName || `${planConfig.name} Team`,
      ownerClientId: whmcsClientId,
      plan,
      maxSeats: planConfig.maxSeats,
      sharedPool: planConfig.sharedPool,
      created: new Date().toISOString(),
    };

    await redis.setex(`team:${teamId}`, TTL_90_DAYS, JSON.stringify(teamData));
    await redis.set(`team:by_owner:${whmcsClientId}`, teamId);
    await redis.set(`team:by_client:${whmcsClientId}`, teamId);
    await redis.set(`team:members:${teamId}`, JSON.stringify([
      { clientId: whmcsClientId, role: 'owner', addedDate: new Date().toISOString(), memberLimit: 0 },
    ]));
    await redis.set(`team:used:${teamId}`, '0');

    // Set token limit to shared pool size
    await tc.setLimit(whmcsClientId, 'business'); // Base limit for the owner
    // Override with the team pool
    await redis.set(`tokens:limit:${whmcsClientId}`, String(planConfig.sharedPool));

    logger.info(`teams: provisioned team ${teamId} (${planConfig.name}) for client ${whmcsClientId}`);

    res.json({ ok: true, teamId, action: 'created', plan, sharedPool: planConfig.sharedPool });
  } catch (err) {
    logger.error(`teams/provision: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

/**
 * Check team allowance for a client.
 * Called from anthropicProxy before personal limit check.
 *
 * @param {string|number} clientId
 * @returns {Promise<{isTeamMember: boolean, teamAllowed?: boolean, teamUsed?: number, teamPool?: number}>}
 */
async function checkTeamAllowance(clientId) {
  const redis = getRedis();
  const teamId = await redis.get(`team:by_client:${clientId}`);
  if (!teamId) return { isTeamMember: false };

  const teamRaw = await redis.get(`team:${teamId}`);
  if (!teamRaw) return { isTeamMember: false };

  const team = JSON.parse(teamRaw);
  const used = parseInt(await redis.get(`team:used:${teamId}`) || '0', 10);

  // Team overage: allow up to 150% of pool
  const hardCap = Math.round(team.sharedPool * 1.5);

  return {
    isTeamMember: true,
    teamId,
    teamAllowed: used < hardCap,
    teamUsed: used,
    teamPool: team.sharedPool,
    teamPercentUsed: team.sharedPool > 0 ? Math.round((used / team.sharedPool) * 100) : 0,
  };
}

/**
 * Add usage to team shared pool.
 *
 * @param {string|number} clientId
 * @param {number} outputTokens
 */
async function addTeamUsage(clientId, outputTokens) {
  const redis = getRedis();
  const teamId = await redis.get(`team:by_client:${clientId}`);
  if (!teamId) return;

  await redis.incrby(`team:used:${teamId}`, outputTokens);
}

module.exports = router;
module.exports.checkTeamAllowance = checkTeamAllowance;
module.exports.addTeamUsage = addTeamUsage;
module.exports.getTeamBudget = getTeamBudget;
module.exports.getTeamBudgetStatus = getTeamBudgetStatus;
module.exports.TEAM_PLANS = TEAM_PLANS;
