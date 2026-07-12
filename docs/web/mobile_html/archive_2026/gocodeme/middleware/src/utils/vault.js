'use strict';

/**
 * Vault — encrypts/decrypts sensitive values using AES-256-GCM.
 *
 * Key lives at ~/.gocodeme/.vault-key (32 bytes, 0600 permissions).
 * Encrypted values are stored as:  vault:v1:<iv-hex>:<authTag-hex>:<ciphertext-hex>
 *
 * This keeps secrets encrypted at rest in .env files while remaining
 * decryptable at runtime by the middleware process.
 */

const crypto = require('crypto');
const fs     = require('fs');
const path   = require('path');

const ALGO       = 'aes-256-gcm';
const IV_BYTES   = 16;
const PREFIX     = 'vault:v1:';
const KEY_PATH   = path.join(process.env.HOME || '/home/gositeme', '.gocodeme', '.vault-key');

let _cachedKey = null;

/**
 * Load the 32-byte encryption key from disk (cached after first read).
 */
function loadKey() {
  if (_cachedKey) return _cachedKey;

  if (!fs.existsSync(KEY_PATH)) {
    throw new Error(`Vault key not found at ${KEY_PATH}. Run: node scripts/vault-cli.js init`);
  }

  const key = fs.readFileSync(KEY_PATH);
  if (key.length !== 32) {
    throw new Error(`Vault key must be exactly 32 bytes, got ${key.length}`);
  }

  _cachedKey = key;
  return key;
}

/**
 * Encrypt a plaintext string.
 * @param {string} plaintext
 * @returns {string} Prefixed encrypted blob: vault:v1:<iv>:<tag>:<ct>
 */
function encrypt(plaintext) {
  const key = loadKey();
  const iv  = crypto.randomBytes(IV_BYTES);
  const cipher = crypto.createCipheriv(ALGO, key, iv);

  let ct = cipher.update(plaintext, 'utf8', 'hex');
  ct += cipher.final('hex');
  const tag = cipher.getAuthTag().toString('hex');

  return `${PREFIX}${iv.toString('hex')}:${tag}:${ct}`;
}

/**
 * Decrypt a vault-encrypted string.
 * @param {string} blob - Must start with vault:v1:
 * @returns {string} Decrypted plaintext
 */
function decrypt(blob) {
  if (!blob.startsWith(PREFIX)) {
    throw new Error('Not a vault-encrypted value (missing vault:v1: prefix)');
  }

  const key = loadKey();
  const parts = blob.slice(PREFIX.length).split(':');
  if (parts.length !== 3) {
    throw new Error('Malformed vault blob — expected iv:tag:ciphertext');
  }

  const [ivHex, tagHex, ctHex] = parts;
  const decipher = crypto.createDecipheriv(ALGO, key, Buffer.from(ivHex, 'hex'));
  decipher.setAuthTag(Buffer.from(tagHex, 'hex'));

  let pt = decipher.update(ctHex, 'hex', 'utf8');
  pt += decipher.final('utf8');
  return pt;
}

/**
 * Resolve a value — if it starts with vault:v1: decrypt it, otherwise return as-is.
 * This lets config.js transparently handle both plain and encrypted values.
 * @param {string} value
 * @returns {string}
 */
function resolve(value) {
  if (!value || !value.startsWith(PREFIX)) return value;
  return decrypt(value);
}

/**
 * Check if a value is vault-encrypted.
 * @param {string} value
 * @returns {boolean}
 */
function isEncrypted(value) {
  return typeof value === 'string' && value.startsWith(PREFIX);
}

module.exports = { encrypt, decrypt, resolve, isEncrypted, KEY_PATH };
