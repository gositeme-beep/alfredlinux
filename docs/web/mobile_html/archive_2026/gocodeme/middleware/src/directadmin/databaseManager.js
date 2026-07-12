'use strict';

/**
 * DirectAdmin Database Manager
 *
 * Create, list, and delete MySQL databases and users via DirectAdmin's CMD_API.
 * Uses admin|user impersonation so the customer never needs to know DA credentials.
 *
 * DA API Endpoints used:
 *   CMD_API_DATABASES       — list databases
 *   CMD_API_DATABASES       — create / delete databases
 *   CMD_API_DB_USER         — manage database users (not standard — DA uses combined)
 *
 * DA naming convention: databases and users are prefixed with "{daUsername}_".
 */

const { createDAClient } = require('./client');
const logger = require('../logger');

/**
 * List all MySQL databases for a DirectAdmin user.
 *
 * @param {string} daUsername
 * @returns {Promise<string[]>}  Array of database names
 */
async function listDatabases(daUsername) {
  const client = createDAClient(daUsername);
  const resp   = await client.get('/CMD_API_DATABASES');
  const data   = resp.data;

  // DA returns: list[]=db1&list[]=db2  OR  JSON array
  if (Array.isArray(data)) return data;
  if (typeof data === 'object' && data.list) return [].concat(data.list);

  // URL-encoded fallback
  if (typeof data === 'string') {
    const matches = [];
    const params  = new URLSearchParams(data);
    for (const [key, val] of params) {
      if (key.startsWith('list')) matches.push(val);
    }
    return matches;
  }
  return [];
}

/**
 * Create a new MySQL database with a user and password.
 *
 * DA enforces: db name and user are prefixed with "{daUsername}_".
 * So if you pass name="shop", the actual DB is "{daUsername}_shop".
 *
 * @param {string} daUsername
 * @param {string} dbName     — short name (without prefix), e.g. "shop"
 * @param {string} dbUser     — short username (without prefix), e.g. "shopuser"
 * @param {string} dbPassword — password for the database user
 * @returns {Promise<{database: string, user: string, host: string}>}
 */
async function createDatabase(daUsername, dbName, dbUser, dbPassword) {
  const client = createDAClient(daUsername);

  const params = new URLSearchParams({
    action:  'create',
    name:    dbName,        // DA auto-prepends "{daUsername}_"
    user:    dbUser,        // DA auto-prepends "{daUsername}_"
    passwd:  dbPassword,
    passwd2: dbPassword,
  });

  const resp = await client.post('/CMD_API_DATABASES', params.toString());
  const body = typeof resp.data === 'string' ? resp.data : JSON.stringify(resp.data);

  if (body.includes('error=1') || body.includes('already exists')) {
    throw new Error(`Database creation failed: ${body.substring(0, 300)}`);
  }

  const fullDbName = `${daUsername}_${dbName}`;
  const fullUser   = `${daUsername}_${dbUser}`;

  logger.info(`da-db: created database ${fullDbName} with user ${fullUser} for ${daUsername}`);

  // SECURITY (R2-14): Do NOT return the password in the response
  // The caller already knows it — echoing it back risks leaking it in logs/responses
  return {
    database: fullDbName,
    user:     fullUser,
    host:     'localhost',
  };
}

/**
 * Delete a MySQL database.
 *
 * @param {string} daUsername
 * @param {string} dbName  — full database name (e.g. "username_shop")
 * @returns {Promise<void>}
 */
async function deleteDatabase(daUsername, dbName) {
  const client = createDAClient(daUsername);

  const params = new URLSearchParams({
    action:  'delete',
    select0: dbName,
  });

  const resp = await client.post('/CMD_API_DATABASES', params.toString());
  const body = typeof resp.data === 'string' ? resp.data : JSON.stringify(resp.data);

  if (body.includes('error=1')) {
    throw new Error(`Database deletion failed: ${body.substring(0, 300)}`);
  }

  logger.info(`da-db: deleted database ${dbName} for ${daUsername}`);
}

/**
 * Get details/info for a specific database (users, size).
 *
 * @param {string} daUsername
 * @param {string} dbName — full database name
 * @returns {Promise<object>}
 */
async function getDatabaseInfo(daUsername, dbName) {
  const client = createDAClient(daUsername);

  const resp = await client.get('/CMD_API_DATABASES', {
    params: { db: dbName },
  });

  return resp.data;
}

module.exports = {
  listDatabases,
  createDatabase,
  deleteDatabase,
  getDatabaseInfo,
};
