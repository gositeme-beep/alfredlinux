/**
 * chronicleEngine.js — CHRONICLE: Audit Trail & Activity Logging Engine
 *
 * Comprehensive audit trail system with immutable event logging,
 * activity tracking, change history, and compliance reporting.
 *
 * Capabilities:
 *  - Immutable audit log with tamper detection
 *  - User activity tracking
 *  - File change history
 *  - Session recording and playback
 *  - Compliance report generation
 *  - Event stream with filters
 */

import { randomUUID, createHash } from 'node:crypto';
import fs from 'node:fs/promises';
import path from 'node:path';

const CHRONICLE_BASE = '/home/gositeme/.gocodeme/chronicle';

async function ensureDir(dir) { await fs.mkdir(dir, { recursive: true }); }
async function loadJSON(file, fallback = {}) {
  try { return JSON.parse(await fs.readFile(file, 'utf8')); } catch { return fallback; }
}
async function saveJSON(file, data) {
  await ensureDir(path.dirname(file));
  await fs.writeFile(file, JSON.stringify(data, null, 2));
}

function auditPath(user) { return path.join(CHRONICLE_BASE, user, 'audit_log.json'); }
function activityPath(user) { return path.join(CHRONICLE_BASE, user, 'activity.json'); }
function sessionsPath(user) { return path.join(CHRONICLE_BASE, user, 'sessions.json'); }
function changesPath(user) { return path.join(CHRONICLE_BASE, user, 'changes.json'); }

// Hash chain for tamper detection
function chainHash(prevHash, event) {
  return createHash('sha256').update(prevHash + JSON.stringify(event)).digest('hex');
}

// ── Audit Logging ───────────────────────────────────────────────────────────

export async function logEvent(user, event) {
  const log = await loadJSON(auditPath(user), { events: [], chain_hash: '0' });
  const id = `evt_${randomUUID().slice(0, 12)}`;
  const entry = {
    id,
    type: event.type || 'action',       // action, security, access, change, error
    category: event.category || 'general',
    action: event.action,
    actor: event.actor || user,
    target: event.target || null,
    details: event.details || {},
    ip: event.ip || null,
    timestamp: new Date().toISOString(),
    prev_hash: log.chain_hash,
  };
  entry.hash = chainHash(log.chain_hash, entry);
  log.chain_hash = entry.hash;
  log.events.unshift(entry);
  if (log.events.length > 10000) log.events = log.events.slice(0, 10000);
  await saveJSON(auditPath(user), log);
  return { id, hash: entry.hash, message: `Event logged: ${event.action}` };
}

export async function queryEvents(user, filters = {}) {
  const log = await loadJSON(auditPath(user), { events: [] });
  let events = log.events;

  if (filters.type) events = events.filter(e => e.type === filters.type);
  if (filters.category) events = events.filter(e => e.category === filters.category);
  if (filters.actor) events = events.filter(e => e.actor === filters.actor);
  if (filters.action) events = events.filter(e => e.action?.includes(filters.action));
  if (filters.since) {
    const since = new Date(filters.since).getTime();
    events = events.filter(e => new Date(e.timestamp).getTime() >= since);
  }
  if (filters.until) {
    const until = new Date(filters.until).getTime();
    events = events.filter(e => new Date(e.timestamp).getTime() <= until);
  }

  const limit = filters.limit || 50;
  return {
    events: events.slice(0, limit),
    total: events.length,
    showing: Math.min(limit, events.length),
    message: `${events.length} event(s) match filters.`,
  };
}

export async function verifyIntegrity(user) {
  const log = await loadJSON(auditPath(user), { events: [], chain_hash: '0' });
  if (log.events.length === 0) return { valid: true, message: 'No events to verify.' };

  // Verify chain from oldest to newest
  const reversed = [...log.events].reverse();
  let prevHash = '0';
  let valid = true;
  let brokenAt = -1;

  for (let i = 0; i < reversed.length; i++) {
    const event = reversed[i];
    if (event.prev_hash !== prevHash) {
      valid = false;
      brokenAt = i;
      break;
    }
    const computed = chainHash(prevHash, event);
    if (computed !== event.hash) {
      valid = false;
      brokenAt = i;
      break;
    }
    prevHash = event.hash;
  }

  return {
    valid,
    total_events: log.events.length,
    chain_intact: valid,
    broken_at: brokenAt >= 0 ? brokenAt : null,
    message: valid
      ? `Audit log intact: ${log.events.length} events verified.`
      : `TAMPERED: Chain broken at event #${brokenAt}.`,
  };
}

// ── Activity Tracking ───────────────────────────────────────────────────────

export async function trackActivity(user, activity) {
  const data = await loadJSON(activityPath(user), { activities: [], daily_stats: {} });
  const today = new Date().toISOString().split('T')[0];

  data.activities.unshift({
    type: activity.type,  // tool_call, file_edit, deploy, login, etc.
    tool: activity.tool || null,
    file: activity.file || null,
    duration_ms: activity.duration_ms || null,
    success: activity.success !== false,
    timestamp: new Date().toISOString(),
  });

  // Update daily stats
  if (!data.daily_stats[today]) {
    data.daily_stats[today] = { tool_calls: 0, file_edits: 0, errors: 0, total_duration_ms: 0 };
  }
  data.daily_stats[today].tool_calls++;
  if (activity.type === 'file_edit') data.daily_stats[today].file_edits++;
  if (!activity.success) data.daily_stats[today].errors++;
  if (activity.duration_ms) data.daily_stats[today].total_duration_ms += activity.duration_ms;

  // Keep last 5000 activities
  if (data.activities.length > 5000) data.activities = data.activities.slice(0, 5000);
  // Keep last 90 days of stats
  const days = Object.keys(data.daily_stats).sort().reverse();
  if (days.length > 90) {
    for (const d of days.slice(90)) delete data.daily_stats[d];
  }

  await saveJSON(activityPath(user), data);
  return { tracked: true };
}

export async function getActivitySummary(user, days = 7) {
  const data = await loadJSON(activityPath(user), { activities: [], daily_stats: {} });
  const since = new Date(Date.now() - days * 86400000).toISOString().split('T')[0];

  const recentStats = {};
  let totalCalls = 0, totalEdits = 0, totalErrors = 0;
  for (const [day, stats] of Object.entries(data.daily_stats)) {
    if (day >= since) {
      recentStats[day] = stats;
      totalCalls += stats.tool_calls;
      totalEdits += stats.file_edits;
      totalErrors += stats.errors;
    }
  }

  // Top tools used
  const toolCounts = {};
  for (const a of data.activities) {
    if (new Date(a.timestamp).toISOString().split('T')[0] >= since && a.tool) {
      toolCounts[a.tool] = (toolCounts[a.tool] || 0) + 1;
    }
  }
  const topTools = Object.entries(toolCounts).sort((a, b) => b[1] - a[1]).slice(0, 10);

  return {
    period: `${days} days`,
    total_calls: totalCalls,
    total_file_edits: totalEdits,
    total_errors: totalErrors,
    error_rate: totalCalls > 0 ? ((totalErrors / totalCalls) * 100).toFixed(1) + '%' : '0%',
    daily_stats: recentStats,
    top_tools: topTools.map(([tool, count]) => ({ tool, count })),
    message: `Activity summary (${days}d): ${totalCalls} calls, ${totalEdits} edits, ${totalErrors} errors.`,
  };
}

// ── Change History ──────────────────────────────────────────────────────────

export async function recordChange(user, change) {
  const data = await loadJSON(changesPath(user), { changes: [] });
  const id = `chg_${randomUUID().slice(0, 8)}`;
  data.changes.unshift({
    id,
    file: change.file,
    type: change.type || 'edit',  // create, edit, delete, rename, permission
    before_hash: change.before_hash || null,
    after_hash: change.after_hash || null,
    diff_summary: change.diff_summary || null,
    size_before: change.size_before || null,
    size_after: change.size_after || null,
    tool: change.tool || null,
    timestamp: new Date().toISOString(),
  });
  if (data.changes.length > 5000) data.changes = data.changes.slice(0, 5000);
  await saveJSON(changesPath(user), data);
  return { id, message: `Change recorded for ${change.file}.` };
}

export async function getChangeHistory(user, filePath, limit = 20) {
  const data = await loadJSON(changesPath(user), { changes: [] });
  let changes = data.changes;
  if (filePath) changes = changes.filter(c => c.file === filePath || c.file?.includes(filePath));
  return {
    changes: changes.slice(0, limit),
    total: changes.length,
    message: `${changes.length} change(s)${filePath ? ` for ${filePath}` : ''}.`,
  };
}

// ── Session Recording ───────────────────────────────────────────────────────

export async function startSession(user, metadata = {}) {
  const data = await loadJSON(sessionsPath(user), { sessions: [] });
  const id = `sess_${randomUUID().slice(0, 8)}`;
  data.sessions.unshift({
    id,
    started: new Date().toISOString(),
    ended: null,
    duration_ms: null,
    metadata,
    actions: [],
    files_touched: [],
    tools_used: [],
    status: 'active',
  });
  if (data.sessions.length > 100) data.sessions = data.sessions.slice(0, 100);
  await saveJSON(sessionsPath(user), data);
  return { session_id: id, message: `Session ${id} started.` };
}

export async function endSession(user, sessionId) {
  const data = await loadJSON(sessionsPath(user), { sessions: [] });
  const session = data.sessions.find(s => s.id === sessionId);
  if (!session) return { message: `Session ${sessionId} not found.` };
  session.ended = new Date().toISOString();
  session.duration_ms = new Date(session.ended) - new Date(session.started);
  session.status = 'completed';
  await saveJSON(sessionsPath(user), data);
  return {
    session_id: sessionId,
    duration_ms: session.duration_ms,
    actions: session.actions.length,
    files: session.files_touched.length,
    message: `Session ${sessionId} ended (${Math.round(session.duration_ms / 1000)}s, ${session.actions.length} actions).`,
  };
}

export async function listSessions(user, limit = 20) {
  const data = await loadJSON(sessionsPath(user), { sessions: [] });
  return {
    sessions: data.sessions.slice(0, limit).map(s => ({
      id: s.id, started: s.started, ended: s.ended,
      duration_ms: s.duration_ms, status: s.status,
      actions: s.actions.length, files: s.files_touched.length,
    })),
    total: data.sessions.length,
    message: `${data.sessions.length} session(s).`,
  };
}

// ── Compliance Report ───────────────────────────────────────────────────────

export async function generateComplianceReport(user) {
  const [auditLog, activity, changes, sessions] = await Promise.all([
    loadJSON(auditPath(user), { events: [] }),
    loadJSON(activityPath(user), { activities: [], daily_stats: {} }),
    loadJSON(changesPath(user), { changes: [] }),
    loadJSON(sessionsPath(user), { sessions: [] }),
  ]);

  const integrity = await verifyIntegrity(user);
  const last30d = new Date(Date.now() - 30 * 86400000).toISOString();

  return {
    generated: new Date().toISOString(),
    period: 'Last 30 days',
    audit_log: {
      total_events: auditLog.events.length,
      recent_events: auditLog.events.filter(e => e.timestamp >= last30d).length,
      integrity: integrity.valid ? 'INTACT' : 'COMPROMISED',
      security_events: auditLog.events.filter(e => e.type === 'security').length,
    },
    activity: {
      total_activities: activity.activities.length,
      active_days: Object.keys(activity.daily_stats).filter(d => d >= last30d.split('T')[0]).length,
    },
    changes: {
      total_changes: changes.changes.length,
      recent_changes: changes.changes.filter(c => c.timestamp >= last30d).length,
      files_affected: [...new Set(changes.changes.filter(c => c.timestamp >= last30d).map(c => c.file))].length,
    },
    sessions: {
      total_sessions: sessions.sessions.length,
      recent_sessions: sessions.sessions.filter(s => s.started >= last30d).length,
    },
    message: `Compliance report generated. Audit log integrity: ${integrity.valid ? 'INTACT' : 'COMPROMISED'}.`,
  };
}
