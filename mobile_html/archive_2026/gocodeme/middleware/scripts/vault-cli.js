#!/usr/bin/env node
'use strict';

/**
 * Vault CLI — encrypt/decrypt values for .env files.
 *
 * Usage:
 *   node scripts/vault-cli.js init          — generate a new vault key
 *   node scripts/vault-cli.js encrypt VALUE — encrypt a value
 *   node scripts/vault-cli.js decrypt BLOB  — decrypt a vault:v1: value
 *   node scripts/vault-cli.js rotate        — re-encrypt .env with a new key
 */

const crypto = require('crypto');
const fs     = require('fs');
const path   = require('path');
const vault  = require('../src/utils/vault');

const KEY_PATH = vault.KEY_PATH;
const ENV_PATH = path.join(process.env.HOME || '/home/gositeme', '.gocodeme', '.env');

const [,, command, ...args] = process.argv;

switch (command) {
  case 'init':
    cmdInit();
    break;
  case 'encrypt':
    cmdEncrypt(args[0]);
    break;
  case 'decrypt':
    cmdDecrypt(args[0]);
    break;
  case 'encrypt-env':
    cmdEncryptEnv(args[0]);
    break;
  default:
    console.log(`Usage:
  node scripts/vault-cli.js init                — generate vault key (if missing)
  node scripts/vault-cli.js encrypt <value>     — encrypt a plaintext value
  node scripts/vault-cli.js decrypt <blob>      — decrypt a vault:v1: blob
  node scripts/vault-cli.js encrypt-env <KEY>   — encrypt a .env variable in-place`);
    process.exit(1);
}

function cmdInit() {
  if (fs.existsSync(KEY_PATH)) {
    console.log(`Vault key already exists at ${KEY_PATH}`);
    process.exit(0);
  }
  const dir = path.dirname(KEY_PATH);
  if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true, mode: 0o700 });
  fs.writeFileSync(KEY_PATH, crypto.randomBytes(32), { mode: 0o600 });
  console.log(`Vault key created at ${KEY_PATH} (0600)`);
}

function cmdEncrypt(value) {
  if (!value) {
    console.error('Usage: node scripts/vault-cli.js encrypt <value>');
    process.exit(1);
  }
  console.log(vault.encrypt(value));
}

function cmdDecrypt(blob) {
  if (!blob) {
    console.error('Usage: node scripts/vault-cli.js decrypt <blob>');
    process.exit(1);
  }
  console.log(vault.decrypt(blob));
}

function cmdEncryptEnv(envKey) {
  if (!envKey) {
    console.error('Usage: node scripts/vault-cli.js encrypt-env <KEY_NAME>');
    process.exit(1);
  }
  if (!fs.existsSync(ENV_PATH)) {
    console.error(`.env not found at ${ENV_PATH}`);
    process.exit(1);
  }

  const lines = fs.readFileSync(ENV_PATH, 'utf8').split('\n');
  let found = false;

  const updated = lines.map(line => {
    const match = line.match(new RegExp(`^(${envKey})=(.+)$`));
    if (!match) return line;

    const [, key, value] = match;
    if (vault.isEncrypted(value)) {
      console.log(`${key} is already encrypted — skipping`);
      found = true;
      return line;
    }

    const encrypted = vault.encrypt(value);
    console.log(`${key} encrypted successfully`);
    found = true;
    return `${key}=${encrypted}`;
  });

  if (!found) {
    console.error(`Key "${envKey}" not found in ${ENV_PATH}`);
    process.exit(1);
  }

  fs.writeFileSync(ENV_PATH, updated.join('\n'));
  console.log(`.env updated at ${ENV_PATH}`);
}
