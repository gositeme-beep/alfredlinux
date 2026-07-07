'use strict';

require('dotenv').config();
const { resolve } = require('./utils/vault');

const config = {
  server: {
    port: parseInt(process.env.PORT || '3001', 10),
    env: process.env.NODE_ENV || 'development',
  },

  directAdmin: {
    host: process.env.DA_HOST || 'https://localhost:2222',
    adminUser: process.env.DA_ADMIN_USER || 'admin',
    adminPass: resolve(process.env.DA_ADMIN_PASS || ''),
    // Build the impersonation username: "admin|targetuser"
    impersonateUser(username) {
      return `${this.adminUser}|${username}`;
    },
  },

  whmcs: {
    apiUrl: process.env.WHMCS_API_URL || '',
    identifier: resolve(process.env.WHMCS_API_IDENTIFIER || ''),
    secret: resolve(process.env.WHMCS_API_SECRET || ''),
  },

  jwt: {
    // SECURITY: Never fall back to a predictable secret. In dev without JWT_SECRET,
    // each process start gets a random secret (tokens won't survive restarts).
    secret: resolve(process.env.JWT_SECRET || require('crypto').randomBytes(32).toString('hex')),
    expiresIn: '24h',
  },

  anthropic: {
    apiKey: resolve(process.env.ANTHROPIC_API_KEY || ''),
    model: process.env.ANTHROPIC_MODEL || 'claude-sonnet-4-6',
  },

  apiKeys: {
    openai:   resolve(process.env.OPENAI_API_KEY   || ''),
    together: resolve(process.env.TOGETHER_API_KEY  || ''),
    xai:      resolve(process.env.XAI_API_KEY       || ''),
    google:   resolve(process.env.GOOGLE_API_KEY    || ''),
    groq:     resolve(process.env.GROQ_API_KEY      || ''),
  },

  whmcsWebhookSecret: resolve(process.env.WHMCS_WEBHOOK_SECRET || ''),

  tokenLimits: {
    free:         parseInt(process.env.TOKEN_LIMIT_FREE         || '50000',   10),
    builder:      parseInt(process.env.TOKEN_LIMIT_BUILDER      || '300000',  10),
    professional: parseInt(process.env.TOKEN_LIMIT_PROFESSIONAL || '600000',  10),
    studio:       parseInt(process.env.TOKEN_LIMIT_STUDIO       || '1500000', 10),
    business:     parseInt(process.env.TOKEN_LIMIT_BUSINESS     || '3000000', 10),
    enterprise:   parseInt(process.env.TOKEN_LIMIT_ENTERPRISE   || '5000000', 10),
    // Legacy aliases (existing Redis keys may reference old plan names)
    starter:      parseInt(process.env.TOKEN_LIMIT_BUILDER      || '300000',  10),
    power:        parseInt(process.env.TOKEN_LIMIT_STUDIO       || '1500000', 10),
    team:         parseInt(process.env.TOKEN_LIMIT_BUSINESS     || '3000000', 10),
    agency:       parseInt(process.env.TOKEN_LIMIT_ENTERPRISE   || '5000000', 10),
  },

  redis: {
    url: process.env.REDIS_URL || 'redis://localhost:6379',
  },

  rateLimit: {
    windowMs: 60 * 1000,
    max: parseInt(process.env.RATE_LIMIT_PER_MINUTE || '60', 10),
  },

  simli: {
    apiKey: process.env.SIMLI_API_KEY || '',
    faceId: process.env.SIMLI_FACE_ID || '',
  },
};

// Validate required fields at startup
function validate() {
  const required = [
    ['DA_ADMIN_PASS',         config.directAdmin.adminPass],
    ['ANTHROPIC_API_KEY',     config.anthropic.apiKey],
    ['JWT_SECRET',            config.jwt.secret],
    ['WHMCS_API_IDENTIFIER',  config.whmcs.identifier],
    ['WHMCS_API_SECRET',      config.whmcs.secret],
  ];

  if (config.server.env === 'production') {
    const missing = required
      .filter(([, v]) => !v || v === 'changeme')
      .map(([k]) => k);

    if (missing.length) {
      throw new Error(`Missing required environment variables: ${missing.join(', ')}`);
    }
  }
}

validate();

module.exports = config;
