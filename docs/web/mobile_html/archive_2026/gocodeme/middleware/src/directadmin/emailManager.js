'use strict';

/**
 * DirectAdmin Email Manager
 *
 * Create, list, and manage email accounts via DirectAdmin's CMD_API.
 * Uses admin|user impersonation.
 *
 * DA API Endpoints:
 *   CMD_API_POP       — list / create / delete POP/IMAP email accounts
 *   CMD_API_EMAIL_FORWARDERS — email forwarding
 *   CMD_API_EMAIL_AUTORESPONDER — auto-responders
 */

const { createDAClient } = require('./client');
const logger = require('../logger');

/**
 * List all email accounts for a domain.
 *
 * @param {string} daUsername
 * @param {string} domain
 * @returns {Promise<string[]>}  Array of email usernames (without @domain)
 */
async function listEmailAccounts(daUsername, domain) {
  const client = createDAClient(daUsername);
  const resp   = await client.get('/CMD_API_POP', {
    params: { domain, action: 'list' },
  });
  const data = resp.data;

  if (Array.isArray(data)) return data;
  if (typeof data === 'object' && data.list) return [].concat(data.list);

  if (typeof data === 'string') {
    const result = [];
    const params = new URLSearchParams(data);
    for (const [key, val] of params) {
      if (key.startsWith('list')) result.push(val);
      else if (val && !key.startsWith('error')) result.push(key);
    }
    return result.filter(Boolean);
  }
  return [];
}

/**
 * Create an email account.
 *
 * @param {string} daUsername
 * @param {string} domain      — e.g. "example.com"
 * @param {string} emailUser   — e.g. "info" (creates info@example.com)
 * @param {string} password    — email account password
 * @param {number} [quota=200] — mailbox quota in MB (0 = unlimited)
 * @returns {Promise<{email: string, server: string, ports: object}>}
 */
async function createEmailAccount(daUsername, domain, emailUser, password, quota = 200) {
  const client = createDAClient(daUsername);

  const params = new URLSearchParams({
    action: 'create',
    domain,
    user:    emailUser,
    passwd:  password,
    passwd2: password,
    quota:   String(quota),
  });

  const resp = await client.post('/CMD_API_POP', params.toString());
  const body = typeof resp.data === 'string' ? resp.data : JSON.stringify(resp.data);

  if (body.includes('error=1') || body.includes('already exists')) {
    throw new Error(`Email creation failed: ${body.substring(0, 300)}`);
  }

  const email = `${emailUser}@${domain}`;
  logger.info(`da-email: created ${email} for ${daUsername}`);

  return {
    email,
    server: domain,
    ports: {
      imap:     993,
      imapTls:  true,
      smtp:     587,
      smtpTls:  true,
      pop3:     995,
      pop3Tls:  true,
    },
  };
}

/**
 * Delete an email account.
 *
 * @param {string} daUsername
 * @param {string} domain
 * @param {string} emailUser — e.g. "info"
 * @returns {Promise<void>}
 */
async function deleteEmailAccount(daUsername, domain, emailUser) {
  const client = createDAClient(daUsername);

  const params = new URLSearchParams({
    action:  'delete',
    domain,
    select0: emailUser,
  });

  const resp = await client.post('/CMD_API_POP', params.toString());
  const body = typeof resp.data === 'string' ? resp.data : JSON.stringify(resp.data);

  if (body.includes('error=1')) {
    throw new Error(`Email deletion failed: ${body.substring(0, 300)}`);
  }

  logger.info(`da-email: deleted ${emailUser}@${domain} for ${daUsername}`);
}

/**
 * Change email account password.
 *
 * @param {string} daUsername
 * @param {string} domain
 * @param {string} emailUser
 * @param {string} newPassword
 * @returns {Promise<void>}
 */
async function changeEmailPassword(daUsername, domain, emailUser, newPassword) {
  const client = createDAClient(daUsername);

  const params = new URLSearchParams({
    action:  'modify',
    domain,
    user:    emailUser,
    passwd:  newPassword,
    passwd2: newPassword,
  });

  const resp = await client.post('/CMD_API_POP', params.toString());
  const body = typeof resp.data === 'string' ? resp.data : JSON.stringify(resp.data);

  if (body.includes('error=1')) {
    throw new Error(`Email password change failed: ${body.substring(0, 300)}`);
  }

  logger.info(`da-email: changed password for ${emailUser}@${domain}`);
}

/**
 * Create an email forwarder.
 *
 * @param {string} daUsername
 * @param {string} domain
 * @param {string} emailUser     — local part (e.g. "info")
 * @param {string} forwardTo     — destination email (e.g. "admin@other.com")
 * @returns {Promise<void>}
 */
async function createForwarder(daUsername, domain, emailUser, forwardTo) {
  const client = createDAClient(daUsername);

  const params = new URLSearchParams({
    action:  'create',
    domain,
    user:    emailUser,
    email:   forwardTo,
  });

  const resp = await client.post('/CMD_API_EMAIL_FORWARDERS', params.toString());
  const body = typeof resp.data === 'string' ? resp.data : JSON.stringify(resp.data);

  if (body.includes('error=1')) {
    throw new Error(`Forwarder creation failed: ${body.substring(0, 300)}`);
  }

  logger.info(`da-email: created forwarder ${emailUser}@${domain} → ${forwardTo}`);
}

/**
 * Create an auto-responder for an email address.
 *
 * @param {string} daUsername
 * @param {string} domain
 * @param {string} emailUser
 * @param {string} subject   — auto-reply subject
 * @param {string} message   — auto-reply body
 * @returns {Promise<void>}
 */
async function createAutoResponder(daUsername, domain, emailUser, subject, message) {
  const client = createDAClient(daUsername);

  const params = new URLSearchParams({
    action:  'create',
    domain,
    user:    emailUser,
    text:    `Subject: ${subject}\n\n${message}`,
  });

  const resp = await client.post('/CMD_API_EMAIL_AUTORESPONDER', params.toString());
  const body = typeof resp.data === 'string' ? resp.data : JSON.stringify(resp.data);

  if (body.includes('error=1')) {
    throw new Error(`Auto-responder creation failed: ${body.substring(0, 300)}`);
  }

  logger.info(`da-email: created auto-responder for ${emailUser}@${domain}`);
}

module.exports = {
  listEmailAccounts,
  createEmailAccount,
  deleteEmailAccount,
  changeEmailPassword,
  createForwarder,
  createAutoResponder,
};
