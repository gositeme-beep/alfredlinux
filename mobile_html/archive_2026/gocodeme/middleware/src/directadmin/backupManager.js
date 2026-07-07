'use strict';

/**
 * DirectAdmin Backup Manager
 *
 * Create and manage backups via DirectAdmin's CMD_API.
 * Uses admin|user impersonation.
 *
 * DA API Endpoints:
 *   CMD_API_SITE_BACKUP     — create a user backup (files, databases, email, etc.)
 *   CMD_API_SITE_RESTORE    — restore from a backup
 */

const { createDAClient } = require('./client');
const logger = require('../logger');

/**
 * Create a full backup of the user's account.
 *
 * @param {string} daUsername
 * @param {object} [options]
 * @param {boolean} [options.files=true]    — include files
 * @param {boolean} [options.databases=true] — include databases
 * @param {boolean} [options.email=true]     — include email
 * @returns {Promise<{status: string, message: string}>}
 */
async function createBackup(daUsername, options = {}) {
  const client = createDAClient(daUsername);

  const { files = true, databases = true, email = true } = options;

  const params = new URLSearchParams({
    action: 'backup',
  });

  if (files)     params.append('ftp_files', 'yes');
  if (databases) params.append('ftp_databases', 'yes');
  if (email)     params.append('ftp_email', 'yes');

  const resp = await client.post('/CMD_API_SITE_BACKUP', params.toString());
  const body = typeof resp.data === 'string' ? resp.data : JSON.stringify(resp.data);

  if (body.includes('error=1')) {
    throw new Error(`Backup creation failed: ${body.substring(0, 300)}`);
  }

  logger.info(`da-backup: created backup for ${daUsername} (files=${files}, db=${databases}, email=${email})`);

  return {
    status: 'started',
    message: 'Backup has been queued. It will be available in the backups directory when complete.',
  };
}

/**
 * List available backups.
 *
 * @param {string} daUsername
 * @returns {Promise<string[]>}  Array of backup file names
 */
async function listBackups(daUsername) {
  const client = createDAClient(daUsername);
  const resp   = await client.get('/CMD_API_SITE_BACKUP');
  const data   = resp.data;

  if (Array.isArray(data)) return data;
  if (typeof data === 'object' && data.list) return [].concat(data.list);

  if (typeof data === 'string') {
    const backups = [];
    const params  = new URLSearchParams(data);
    for (const [key, val] of params) {
      if (key.startsWith('list') || val.endsWith('.tar.gz')) {
        backups.push(val || key);
      }
    }
    return backups;
  }
  return [];
}

/**
 * Restore from a backup file.
 *
 * @param {string} daUsername
 * @param {string} backupFile — backup filename (e.g. "user.admin.domain.com.tar.gz")
 * @param {object} [options]
 * @param {boolean} [options.files=true]
 * @param {boolean} [options.databases=true]
 * @param {boolean} [options.email=true]
 * @returns {Promise<{status: string}>}
 */
async function restoreBackup(daUsername, backupFile, options = {}) {
  const client = createDAClient(daUsername);

  const { files = true, databases = true, email = true } = options;

  const params = new URLSearchParams({
    action: 'restore',
    select0: backupFile,
  });

  if (files)     params.append('ftp_files', 'yes');
  if (databases) params.append('ftp_databases', 'yes');
  if (email)     params.append('ftp_email', 'yes');

  const resp = await client.post('/CMD_API_SITE_BACKUP', params.toString());
  const body = typeof resp.data === 'string' ? resp.data : JSON.stringify(resp.data);

  if (body.includes('error=1')) {
    throw new Error(`Backup restore failed: ${body.substring(0, 300)}`);
  }

  logger.info(`da-backup: restoring ${backupFile} for ${daUsername}`);

  return { status: 'restoring' };
}

module.exports = {
  createBackup,
  listBackups,
  restoreBackup,
};
