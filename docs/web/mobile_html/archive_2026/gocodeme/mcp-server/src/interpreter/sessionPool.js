/**
 * sessionPool.js — Per-User Execution Session Pool
 *
 * Manages isolated execution sessions per user. Each session:
 *   - Has its own temp directory for files
 *   - Tracks language and state
 *   - Auto-expires after 30 minutes of inactivity
 *   - Maximum 5 concurrent sessions per user
 */

import { mkdir, rm } from 'node:fs/promises';
import path from 'node:path';
import os from 'node:os';

const SESSION_TIMEOUT = 30 * 60 * 1000; // 30 minutes
const MAX_SESSIONS_PER_USER = 5;
const CLEANUP_INTERVAL = 60 * 1000; // check every 60s

// Map<sessionId, SessionInfo>
const sessions = new Map();

// Map<daUsername, Set<sessionId>>
const userSessions = new Map();

let cleanupTimer = null;

/**
 * @typedef {object} SessionInfo
 * @property {string} id
 * @property {string} daUsername
 * @property {string} language
 * @property {string} workDir — temp directory for this session
 * @property {number} createdAt
 * @property {number} lastUsed
 * @property {number} executionCount
 */

/**
 * Generate a short session ID.
 */
function genId() {
  return 'sess-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 8);
}

/**
 * Start the cleanup timer.
 */
function ensureCleanup() {
  if (cleanupTimer) return;
  cleanupTimer = setInterval(async () => {
    const now = Date.now();
    for (const [id, sess] of sessions) {
      if (now - sess.lastUsed > SESSION_TIMEOUT) {
        await destroySession(id);
      }
    }
  }, CLEANUP_INTERVAL);
  cleanupTimer.unref();
}

/**
 * Create a new execution session.
 * @param {string} daUsername
 * @param {string} language — 'python', 'node', 'bash', 'ruby', 'php'
 * @returns {Promise<SessionInfo>}
 */
export async function createSession(daUsername, language) {
  ensureCleanup();

  // Check per-user limit
  const userSet = userSessions.get(daUsername) || new Set();
  if (userSet.size >= MAX_SESSIONS_PER_USER) {
    // Kill oldest session
    const oldest = [...userSet]
      .map(id => sessions.get(id))
      .filter(Boolean)
      .sort((a, b) => a.lastUsed - b.lastUsed)[0];
    if (oldest) await destroySession(oldest.id);
  }

  const id = genId();
  const workDir = path.join(os.tmpdir(), 'gocodeme-interp', id);
  await mkdir(workDir, { recursive: true });

  const session = {
    id,
    daUsername,
    language,
    workDir,
    createdAt: Date.now(),
    lastUsed: Date.now(),
    executionCount: 0,
  };

  sessions.set(id, session);
  if (!userSessions.has(daUsername)) userSessions.set(daUsername, new Set());
  userSessions.get(daUsername).add(id);

  return session;
}

/**
 * Get a session, refreshing its lastUsed timestamp.
 * @param {string} sessionId
 * @returns {SessionInfo|null}
 */
export function getSession(sessionId) {
  const sess = sessions.get(sessionId);
  if (!sess) return null;
  sess.lastUsed = Date.now();
  return sess;
}

/**
 * Find or create a session for a user + language combination.
 * Reuses existing session if same language exists.
 */
export async function getOrCreateSession(daUsername, language) {
  const userSet = userSessions.get(daUsername) || new Set();
  for (const id of userSet) {
    const sess = sessions.get(id);
    if (sess && sess.language === language) {
      sess.lastUsed = Date.now();
      return sess;
    }
  }
  return createSession(daUsername, language);
}

/**
 * Destroy a session and clean up its temp directory.
 */
export async function destroySession(sessionId) {
  const sess = sessions.get(sessionId);
  if (!sess) return false;

  try {
    await rm(sess.workDir, { recursive: true, force: true });
  } catch { /* already gone */ }

  sessions.delete(sessionId);
  const userSet = userSessions.get(sess.daUsername);
  if (userSet) {
    userSet.delete(sessionId);
    if (userSet.size === 0) userSessions.delete(sess.daUsername);
  }

  return true;
}

/**
 * List sessions for a user.
 */
export function listSessions(daUsername) {
  const userSet = userSessions.get(daUsername) || new Set();
  return [...userSet]
    .map(id => sessions.get(id))
    .filter(Boolean)
    .map(s => ({
      id: s.id,
      language: s.language,
      createdAt: new Date(s.createdAt).toISOString(),
      lastUsed: new Date(s.lastUsed).toISOString(),
      executionCount: s.executionCount,
      idleMinutes: Math.round((Date.now() - s.lastUsed) / 60000),
    }));
}

/**
 * List all sessions across all users.
 */
export function listAllSessions() {
  return [...sessions.values()].map(s => ({
    id: s.id,
    daUsername: s.daUsername,
    language: s.language,
    createdAt: new Date(s.createdAt).toISOString(),
    lastUsed: new Date(s.lastUsed).toISOString(),
    executionCount: s.executionCount,
    idleMinutes: Math.round((Date.now() - s.lastUsed) / 60000),
  }));
}
