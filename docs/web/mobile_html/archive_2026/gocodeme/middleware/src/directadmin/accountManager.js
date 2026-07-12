'use strict';

/**
 * DirectAdmin Account Manager
 *
 * Create, delete, suspend, and manage DirectAdmin user accounts via admin API.
 * Uses admin-only authentication (no impersonation).
 *
 * Tested against DirectAdmin 1.695+.
 */

const { createAdminClient } = require('./client');
const logger = require('../logger');

/* ── helpers ─────────────────────────────────────────────────────────── */

/**
 * Parse a DA response body into a string regardless of type.
 */
function bodyStr(data) {
  if (typeof data === 'string') return data;
  if (Buffer.isBuffer(data)) return data.toString('utf-8');
  return JSON.stringify(data);
}

/**
 * Check a DA response for an error indicator.
 * DA legacy API returns URL-encoded: error=0&text=... or error=1&text=...
 * Newer JSON responses may use { error: "1", text: "..." }.
 */
function assertNoError(body, context) {
  const s = bodyStr(body);

  // URL-encoded: match error=1 as a standalone param (not error=10, error=100)
  if (/(?:^|&)error=1(?:&|$)/.test(s)) {
    const parsed = new URLSearchParams(s);
    const text = parsed.get('text') || parsed.get('details') || s.substring(0, 300);
    throw new Error(`${context}: ${decodeURIComponent(text)}`);
  }

  // JSON: { "error": "1" } or { "error": 1 }
  try {
    const json = typeof body === 'object' ? body : JSON.parse(s);
    if (json && (json.error === '1' || json.error === 1)) {
      throw new Error(`${context}: ${json.text || json.details || JSON.stringify(json).substring(0, 300)}`);
    }
  } catch (e) {
    if (e.message.startsWith(context)) throw e; // re-throw our own errors
    // not JSON — ignore parse error
  }
}

/**
 * Validate that all required fields exist on an object.
 */
function requireFields(obj, fields, context) {
  for (const f of fields) {
    if (!obj[f] && obj[f] !== 0) {
      throw new Error(`${context}: missing required field "${f}"`);
    }
  }
}

/* ── CRUD operations ─────────────────────────────────────────────────── */

/**
 * List all user accounts (admin-level).
 * @returns {Promise<string[]>} Array of usernames
 */
async function listUsers() {
  const client = createAdminClient();
  const resp = await client.get('/CMD_API_SHOW_ALL_USERS');
  const body = bodyStr(resp.data);

  // DA returns: list[]=user1&list[]=user2  or  a JSON array
  try {
    const json = typeof resp.data === 'object' ? resp.data : JSON.parse(body);
    if (Array.isArray(json)) return json;
    if (json.list) return [].concat(json.list);
  } catch (_) { /* not JSON */ }

  // URL-encoded fallback
  const parsed = new URLSearchParams(body);
  return parsed.getAll('list[]');
}

/**
 * Get detailed info for a single user account.
 * @param {string} username
 * @returns {Promise<object>} User configuration object
 */
async function getUserInfo(username) {
  requireFields({ username }, ['username'], 'getUserInfo');
  const client = createAdminClient();
  const resp = await client.get('/CMD_API_SHOW_USER_CONFIG', {
    params: { user: username },
  });
  const body = bodyStr(resp.data);
  assertNoError(resp.data, `getUserInfo(${username})`);

  // Parse URL-encoded config into an object
  try {
    const json = typeof resp.data === 'object' ? resp.data : JSON.parse(body);
    return json;
  } catch (_) { /* not JSON */ }
  const result = {};
  const parsed = new URLSearchParams(body);
  for (const [k, v] of parsed) result[k] = v;
  return result;
}

/**
 * Create a new DirectAdmin user account.
 * @param {object} opts - { username, email, password, domain, package }
 * @returns {Promise<object>} - Result object
 */
async function createUser(opts) {
  requireFields(opts, ['username', 'email', 'password', 'domain'], 'createUser');
  const client = createAdminClient();
  const params = new URLSearchParams({
    action: 'create',
    add: 'Submit',
    username: opts.username,
    email: opts.email,
    passwd: opts.password,
    passwd2: opts.password,
    domain: opts.domain,
    package: opts.package || 'default',
    notify: opts.notify === false ? 'no' : 'yes',
  });
  const resp = await client.post('/CMD_API_ACCOUNT_USER', params.toString());
  assertNoError(resp.data, `createUser(${opts.username})`);
  logger.info(`da-account: created user ${opts.username}`);
  return { status: 'created', username: opts.username };
}

/**
 * Delete a DirectAdmin user account.
 * @param {string} username
 * @returns {Promise<void>}
 */
async function deleteUser(username) {
  requireFields({ username }, ['username'], 'deleteUser');
  const client = createAdminClient();
  const params = new URLSearchParams({
    confirmed: 'Confirm',
    delete: 'yes',
    select0: username,
  });
  const resp = await client.post('/CMD_API_SELECT_USERS', params.toString());
  assertNoError(resp.data, `deleteUser(${username})`);
  logger.info(`da-account: deleted user ${username}`);
}

/**
 * Suspend a DirectAdmin user account.
 * @param {string} username
 * @returns {Promise<void>}
 */
async function suspendUser(username) {
  requireFields({ username }, ['username'], 'suspendUser');
  const client = createAdminClient();
  const params = new URLSearchParams({
    suspend: 'Suspend',
    select0: username,
  });
  const resp = await client.post('/CMD_API_SELECT_USERS', params.toString());
  assertNoError(resp.data, `suspendUser(${username})`);
  logger.info(`da-account: suspended user ${username}`);
}

/**
 * Unsuspend a DirectAdmin user account.
 * @param {string} username
 * @returns {Promise<void>}
 */
async function unsuspendUser(username) {
  requireFields({ username }, ['username'], 'unsuspendUser');
  const client = createAdminClient();
  const params = new URLSearchParams({
    unsuspend: 'Unsuspend',
    select0: username,
  });
  const resp = await client.post('/CMD_API_SELECT_USERS', params.toString());
  assertNoError(resp.data, `unsuspendUser(${username})`);
  logger.info(`da-account: unsuspended user ${username}`);
}

/**
 * Reset a DirectAdmin user password.
 * @param {string} username
 * @param {string} newPassword
 * @returns {Promise<void>}
 */
async function resetPassword(username, newPassword) {
  requireFields({ username, newPassword }, ['username', 'newPassword'], 'resetPassword');
  const client = createAdminClient();
  const params = new URLSearchParams({
    action: 'single',
    passwd: newPassword,
    passwd2: newPassword,
  });
  // DA expects user as a query param for CMD_API_MODIFY_USER
  const resp = await client.post(`/CMD_API_MODIFY_USER?user=${encodeURIComponent(username)}`, params.toString());
  assertNoError(resp.data, `resetPassword(${username})`);
  logger.info(`da-account: reset password for user ${username}`);
}

/**
 * Change the package (hosting plan) for a user account.
 * @param {string} username
 * @param {string} packageName
 * @returns {Promise<void>}
 */
async function changePackage(username, packageName) {
  requireFields({ username, packageName }, ['username', 'packageName'], 'changePackage');
  const client = createAdminClient();
  const params = new URLSearchParams({
    action: 'package',
    user: username,
    package: packageName,
  });
  const resp = await client.post('/CMD_API_MODIFY_USER', params.toString());
  assertNoError(resp.data, `changePackage(${username})`);
  logger.info(`da-account: changed package for ${username} → ${packageName}`);
}

module.exports = {
  listUsers,
  getUserInfo,
  createUser,
  deleteUser,
  suspendUser,
  unsuspendUser,
  resetPassword,
  changePackage,
};
