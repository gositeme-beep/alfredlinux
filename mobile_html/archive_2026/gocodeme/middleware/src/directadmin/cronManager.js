'use strict';

/**
 * DirectAdmin Cron Job Manager
 *
 * Create, list, and delete cron jobs via DirectAdmin's CMD_API.
 * Uses admin|user impersonation.
 *
 * DA API Endpoints:
 *   CMD_API_CRON_JOBS — list / create / delete cron jobs
 */

const { createDAClient } = require('./client');
const logger = require('../logger');

/**
 * List all cron jobs for a user.
 *
 * @param {string} daUsername
 * @returns {Promise<Array>}
 */
async function listCronJobs(daUsername) {
  const client = createDAClient(daUsername);
  const resp   = await client.get('/CMD_API_CRON_JOBS');
  const data   = resp.data;

  if (Array.isArray(data)) return data;
  if (typeof data === 'object') {
    // DA may return numbered keys with encoded cron entries
    return Object.values(data);
  }
  if (typeof data === 'string') {
    const jobs = [];
    const params = new URLSearchParams(data);
    for (const [key, val] of params) {
      if (key.startsWith('list')) {
        jobs.push(decodeURIComponent(val));
      }
    }
    return jobs;
  }
  return [];
}

/**
 * Create a cron job.
 *
 * @param {string} daUsername
 * @param {string} minute    — minute field (0-59, *, etc.)
 * @param {string} hour      — hour field (0-23, *, etc.)
 * @param {string} dayOfMonth — day of month (1-31, *, etc.)
 * @param {string} month      — month (1-12, *, etc.)
 * @param {string} dayOfWeek  — day of week (0-7, *, etc.)
 * @param {string} command    — the command to execute
 * @returns {Promise<void>}
 */
async function createCronJob(daUsername, minute, hour, dayOfMonth, month, dayOfWeek, command) {
  const client = createDAClient(daUsername);

  const params = new URLSearchParams({
    action:   'create',
    minute,
    hour,
    dayofmonth: dayOfMonth,
    month,
    dayofweek:  dayOfWeek,
    command,
  });

  const resp = await client.post('/CMD_API_CRON_JOBS', params.toString());
  const body = typeof resp.data === 'string' ? resp.data : JSON.stringify(resp.data);

  if (body.includes('error=1')) {
    throw new Error(`Cron job creation failed: ${body.substring(0, 300)}`);
  }

  logger.info(`da-cron: created cron "${minute} ${hour} ${dayOfMonth} ${month} ${dayOfWeek} ${command}" for ${daUsername}`);
}

/**
 * Delete a cron job by index.
 *
 * @param {string} daUsername
 * @param {number} index — 0-based index of the cron to delete
 * @returns {Promise<void>}
 */
async function deleteCronJob(daUsername, index) {
  const client = createDAClient(daUsername);

  const params = new URLSearchParams({
    action:  'delete',
    select0: String(index),
  });

  const resp = await client.post('/CMD_API_CRON_JOBS', params.toString());
  const body = typeof resp.data === 'string' ? resp.data : JSON.stringify(resp.data);

  if (body.includes('error=1')) {
    throw new Error(`Cron job deletion failed: ${body.substring(0, 300)}`);
  }

  logger.info(`da-cron: deleted cron job #${index} for ${daUsername}`);
}

module.exports = {
  listCronJobs,
  createCronJob,
  deleteCronJob,
};
