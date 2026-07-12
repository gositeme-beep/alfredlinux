'use strict';

/**
 * Dashboard Extras Routes — /api/extras/*
 *
 * Bundled backend for all the new dashboard features:
 *   - Quick Notes (Redis-persisted per-user notepad)
 *   - Snippets Library (Redis-persisted code snippets)
 *   - Activity Feed (recent events from Redis)
 *   - Achievements/Badges (gamification)
 *   - Webhooks Manager (user-defined webhooks)
 *   - One-Click Deploy (sync workspace → public_html)
 *   - Project Health Score (analyze workspace)
 *   - Marketplace (npm package search + install)
 *   - AI Image Generator (via OpenAI DALL-E)
 */

const express = require('express');
const router  = express.Router();
const https   = require('https');
const { execSync, exec } = require('child_process');
const path    = require('path');
const fs      = require('fs');

const { requireSession } = require('../auth/middleware');
const logger = require('../logger');
const { isAllowedUrl } = require('../utils/ssrfGuard');
const safeError = require('../utils/safeError');
const config  = require('../config');

// Redis helper
function getRedis() {
  try { return require('../tokens/tokenCounter').getRedisClient(); } catch (_) {}
  try { return require('ioredis') && global._redisClient; } catch (_) {}
  return null;
}

// Try to get a Redis client, with fallback
let _redis = null;
function redis() {
  if (_redis) return _redis;
  try {
    const IORedis = require('ioredis');
    _redis = new IORedis({ host: '127.0.0.1', port: 6379, maxRetriesPerRequest: 1, lazyConnect: true });
    _redis.connect().catch(() => {});
    return _redis;
  } catch (_) {
    return null;
  }
}

// ═══════════════════════════════════════════════════════════════════════════
// ── Quick Notes ─────────────────────────────────────────────────────────────
// ═══════════════════════════════════════════════════════════════════════════

router.get('/notes', requireSession, async (req, res) => {
  try {
    const clientId = req.user?.whmcsClientId || req.user?.clientId;
    const r = redis();
    const content = r ? await r.get(`notes:${clientId}`) : '';
    res.json({ ok: true, content: content || '' });
  } catch (e) {
    res.json({ ok: true, content: '' });
  }
});

router.post('/notes', requireSession, async (req, res) => {
  try {
    const clientId = req.user?.whmcsClientId || req.user?.clientId;
    const { content } = req.body;
    if (typeof content !== 'string') return res.status(400).json({ ok: false, error: 'content required' });
    const r = redis();
    if (r) await r.set(`notes:${clientId}`, content.slice(0, 50000)); // 50KB max
    // Log activity
    await logActivity(clientId, '📝', 'Note updated', 'Quick notes saved');
    res.json({ ok: true });
  } catch (e) {
    res.status(500).json({ ok: false, error: safeError(e) });
  }
});

// ═══════════════════════════════════════════════════════════════════════════
// ── Snippets Library ────────────────────────────────────────────────────────
// ═══════════════════════════════════════════════════════════════════════════

router.get('/snippets', requireSession, async (req, res) => {
  try {
    const clientId = req.user?.whmcsClientId || req.user?.clientId;
    const r = redis();
    const raw = r ? await r.get(`snippets:${clientId}`) : null;
    const snippets = raw ? JSON.parse(raw) : [];
    res.json({ ok: true, snippets });
  } catch (e) {
    res.json({ ok: true, snippets: [] });
  }
});

router.post('/snippets', requireSession, async (req, res) => {
  try {
    const clientId = req.user?.whmcsClientId || req.user?.clientId;
    const { title, code, language } = req.body;
    if (!title || code === undefined) return res.status(400).json({ ok: false, error: 'title and code required' });
    const r = redis();
    const raw = r ? await r.get(`snippets:${clientId}`) : null;
    const snippets = raw ? JSON.parse(raw) : [];
    snippets.push({ title, code: code.slice(0, 20000), language: language || 'text', created: new Date().toISOString() });
    if (snippets.length > 100) snippets.splice(0, snippets.length - 100); // cap at 100
    if (r) await r.set(`snippets:${clientId}`, JSON.stringify(snippets));
    await logActivity(clientId, '📋', 'Snippet saved', title);
    res.json({ ok: true, snippets });
  } catch (e) {
    res.status(500).json({ ok: false, error: safeError(e) });
  }
});

router.delete('/snippets/:index', requireSession, async (req, res) => {
  try {
    const clientId = req.user?.whmcsClientId || req.user?.clientId;
    const idx = parseInt(req.params.index, 10);
    const r = redis();
    const raw = r ? await r.get(`snippets:${clientId}`) : null;
    const snippets = raw ? JSON.parse(raw) : [];
    if (idx >= 0 && idx < snippets.length) snippets.splice(idx, 1);
    if (r) await r.set(`snippets:${clientId}`, JSON.stringify(snippets));
    res.json({ ok: true, snippets });
  } catch (e) {
    res.status(500).json({ ok: false, error: safeError(e) });
  }
});

// ═══════════════════════════════════════════════════════════════════════════
// ── Activity Feed ───────────────────────────────────────────────────────────
// ═══════════════════════════════════════════════════════════════════════════

async function logActivity(clientId, icon, title, description) {
  try {
    const r = redis();
    if (!r) return;
    const event = JSON.stringify({ icon, title, description, timestamp: new Date().toISOString() });
    await r.lpush(`ui_activity:${clientId}`, event);
    await r.ltrim(`ui_activity:${clientId}`, 0, 99); // keep last 100
  } catch (_) {}
}

router.get('/activity', requireSession, async (req, res) => {
  try {
    const clientId = req.user?.whmcsClientId || req.user?.clientId;
    const r = redis();
    const raw = r ? await r.lrange(`ui_activity:${clientId}`, 0, 49) : [];
    const events = raw.map(s => { try { return JSON.parse(s); } catch (_) { return null; } }).filter(Boolean);
    res.json({ ok: true, events });
  } catch (e) {
    res.json({ ok: true, events: [] });
  }
});

// ═══════════════════════════════════════════════════════════════════════════
// ── Achievements ────────────────────────────────────────────────────────────
// ═══════════════════════════════════════════════════════════════════════════

const ACHIEVEMENT_DEFS = [
  { id: 'first_login',    icon: '🎉', name: 'Welcome!',        description: 'First dashboard login' },
  { id: 'first_chat',     icon: '💬', name: 'Conversationalist', description: 'Send first AI chat message' },
  { id: 'first_commit',   icon: '📝', name: 'First Commit',     description: 'Make your first git commit' },
  { id: 'first_deploy',   icon: '🚀', name: 'Deployer',         description: 'Deploy to live for the first time' },
  { id: 'power_user',     icon: '⚡', name: 'Power User',       description: 'Use 100K+ tokens in a month' },
  { id: 'template_user',  icon: '🧩', name: 'Template Master',  description: 'Apply a project template' },
  { id: 'team_player',    icon: '👥', name: 'Team Player',      description: 'Create or join a team' },
  { id: 'api_builder',    icon: '🔑', name: 'API Builder',      description: 'Generate a developer API key' },
  { id: 'referrer',       icon: '🎁', name: 'Ambassador',       description: 'Refer your first friend' },
  { id: 'streak_7',       icon: '🔥', name: '7-Day Streak',     description: 'Use GoCodeMe 7 days in a row' },
  { id: 'snippet_saver',  icon: '📋', name: 'Snippet Collector', description: 'Save 10 code snippets' },
  { id: 'model_explorer', icon: '🤖', name: 'Model Explorer',   description: 'Try 5 different AI models' },
];

router.get('/achievements', requireSession, async (req, res) => {
  try {
    const clientId = req.user?.whmcsClientId || req.user?.clientId;
    const r = redis();
    const unlocked = r ? await r.smembers(`achievements:${clientId}`) : [];
    const unlockedSet = new Set(unlocked);

    // Auto-check some achievements
    await checkAutoAchievements(clientId, r, unlockedSet);

    const achievements = ACHIEVEMENT_DEFS.map(a => ({
      ...a,
      unlocked: unlockedSet.has(a.id),
    }));
    res.json({ ok: true, achievements, total: achievements.length, unlocked: unlockedSet.size });
  } catch (e) {
    res.json({ ok: true, achievements: ACHIEVEMENT_DEFS.map(a => ({ ...a, unlocked: false })), total: ACHIEVEMENT_DEFS.length, unlocked: 0 });
  }
});

async function checkAutoAchievements(clientId, r, unlockedSet) {
  if (!r) return;
  // First login — always unlocked if they're here
  if (!unlockedSet.has('first_login')) {
    await r.sadd(`achievements:${clientId}`, 'first_login');
    unlockedSet.add('first_login');
  }
  // Check power user (100K+ tokens)
  if (!unlockedSet.has('power_user')) {
    try {
      const usage = await r.get(`output_tokens:${clientId}`);
      if (parseInt(usage || '0', 10) >= 100000) {
        await r.sadd(`achievements:${clientId}`, 'power_user');
        unlockedSet.add('power_user');
      }
    } catch (_) {}
  }
  // Check snippet saver (10+ snippets)
  if (!unlockedSet.has('snippet_saver')) {
    try {
      const raw = await r.get(`snippets:${clientId}`);
      if (raw && JSON.parse(raw).length >= 10) {
        await r.sadd(`achievements:${clientId}`, 'snippet_saver');
        unlockedSet.add('snippet_saver');
      }
    } catch (_) {}
  }
}

// Unlock an achievement (called from other routes)
async function unlockAchievement(clientId, achievementId) {
  try {
    const r = redis();
    if (r) await r.sadd(`achievements:${clientId}`, achievementId);
  } catch (_) {}
}

// ═══════════════════════════════════════════════════════════════════════════
// ── Webhooks Manager ────────────────────────────────────────────────────────
// ═══════════════════════════════════════════════════════════════════════════

const VALID_EVENTS = ['deploy', 'commit', 'usage-limit', 'login', 'chat', 'model-change'];

router.get('/webhooks', requireSession, async (req, res) => {
  try {
    const clientId = req.user?.whmcsClientId || req.user?.clientId;
    const r = redis();
    const raw = r ? await r.get(`webhooks:${clientId}`) : null;
    const webhooks = raw ? JSON.parse(raw) : [];
    res.json({ ok: true, webhooks });
  } catch (e) {
    res.json({ ok: true, webhooks: [] });
  }
});

router.post('/webhooks', requireSession, async (req, res) => {
  try {
    const clientId = req.user?.whmcsClientId || req.user?.clientId;
    const { url, event } = req.body;
    if (!url || !url.startsWith('http')) return res.status(400).json({ ok: false, error: 'Valid URL required' });
    // SECURITY (R3 M-02): Validate webhook URL against SSRF blocklist
    const urlCheck = isAllowedUrl(url, { requireHttps: true });
    if (!urlCheck.ok) return res.status(400).json({ ok: false, error: urlCheck.error });
    if (!event || !VALID_EVENTS.includes(event)) return res.status(400).json({ ok: false, error: 'Valid event required: ' + VALID_EVENTS.join(', ') });
    const r = redis();
    const raw = r ? await r.get(`webhooks:${clientId}`) : null;
    const webhooks = raw ? JSON.parse(raw) : [];
    if (webhooks.length >= 20) return res.status(400).json({ ok: false, error: 'Max 20 webhooks' });
    webhooks.push({ url, event, created: new Date().toISOString() });
    if (r) await r.set(`webhooks:${clientId}`, JSON.stringify(webhooks));
    res.json({ ok: true, webhooks });
  } catch (e) {
    res.status(500).json({ ok: false, error: safeError(e) });
  }
});

router.delete('/webhooks/:index', requireSession, async (req, res) => {
  try {
    const clientId = req.user?.whmcsClientId || req.user?.clientId;
    const idx = parseInt(req.params.index, 10);
    const r = redis();
    const raw = r ? await r.get(`webhooks:${clientId}`) : null;
    const webhooks = raw ? JSON.parse(raw) : [];
    if (idx >= 0 && idx < webhooks.length) webhooks.splice(idx, 1);
    if (r) await r.set(`webhooks:${clientId}`, JSON.stringify(webhooks));
    res.json({ ok: true, webhooks });
  } catch (e) {
    res.status(500).json({ ok: false, error: safeError(e) });
  }
});

// Fire webhook (utility — called from other routes)
async function fireWebhooks(clientId, event, payload) {
  try {
    const r = redis();
    const raw = r ? await r.get(`webhooks:${clientId}`) : null;
    const webhooks = raw ? JSON.parse(raw) : [];
    const matching = webhooks.filter(w => w.event === event);
    for (const wh of matching) {
      try {
        // SECURITY (R3 M-02): Double-check URL at fire time (in case Redis was tampered)
        const urlCheck = isAllowedUrl(wh.url, { requireHttps: false });
        if (!urlCheck.ok) continue;
        const url = new URL(wh.url);
        const body = JSON.stringify({ event, timestamp: new Date().toISOString(), ...payload });
        const req = https.request(url.href, { method: 'POST', headers: { 'content-type': 'application/json', 'content-length': Buffer.byteLength(body) } });
        req.on('error', () => {});
        req.setTimeout(5000, () => req.destroy());
        req.write(body);
        req.end();
      } catch (_) {}
    }
  } catch (_) {}
}

// ═══════════════════════════════════════════════════════════════════════════
// ── One-Click Deploy ────────────────────────────────────────────────────────
// ═══════════════════════════════════════════════════════════════════════════

router.post('/deploy', requireSession, async (req, res) => {
  try {
    const clientId = req.user?.whmcsClientId || req.user?.clientId;
    const daUsername = req.user?.daUsername;
    if (!daUsername) return res.status(400).json({ ok: false, error: 'No workspace linked' });

    const { target } = req.body; // 'live' or 'preview'
    const workDir = `/home/${daUsername}/ide-workspace`;
    const pubDir  = `/home/${daUsername}/domains/${daUsername}.gocodeme.com/public_html`;

    if (!fs.existsSync(workDir)) return res.status(400).json({ ok: false, error: 'No workspace found' });

    if (target === 'preview') {
      // Preview: just return a URL
      const previewUrl = `https://${daUsername}.gocodeme.com`;
      return res.json({ ok: true, url: previewUrl, message: 'Preview available at ' + previewUrl });
    }

    // SECURITY (R3-04): Validate daUsername to prevent path injection
    if (!/^[a-zA-Z0-9_]{1,16}$/.test(daUsername)) {
      return res.status(400).json({ ok: false, error: 'Invalid username' });
    }

    // Live deploy: rsync workspace → public_html
    // SECURITY (R3-04): Use execFile (no shell) to prevent injection via crafted paths
    const { execFile } = require('child_process');
    execFile('rsync', [
      '-av', '--delete',
      '--exclude=node_modules', '--exclude=.git', '--exclude=.env',
      workDir + '/', pubDir + '/'
    ], { timeout: 30000 }, async (err, stdout, stderr) => {
      if (err) {
        logger.error(`deploy: error for ${daUsername}: ${err.message}`);
        return res.json({ ok: false, error: 'Deploy failed', log: (stdout + stderr).slice(0, 500) });
      }
      await logActivity(clientId, '🚀', 'Deployed to live', 'Files synced to production');
      await unlockAchievement(clientId, 'first_deploy');
      await fireWebhooks(clientId, 'deploy', { daUsername, target: 'live' });
      res.json({ ok: true, message: 'Deployed successfully!', log: stdout.slice(0, 500) });
    });
  } catch (e) {
    res.status(500).json({ ok: false, error: safeError(e) });
  }
});

// ═══════════════════════════════════════════════════════════════════════════
// ── Project Health Score ────────────────────────────────────────────────────
// ═══════════════════════════════════════════════════════════════════════════

router.get('/health-score', requireSession, async (req, res) => {
  try {
    const daUsername = req.user?.daUsername;
    if (!daUsername) return res.json({ ok: true, score: 0, grade: 'N/A', summary: 'No workspace', checks: [] });

    const workDir = `/home/${daUsername}/ide-workspace`;
    if (!fs.existsSync(workDir)) return res.json({ ok: true, score: 50, grade: 'Setup Needed', summary: 'Create a project first', checks: [] });

    const checks = [];
    let score = 0;

    // 1. Has package.json or requirements.txt
    const hasPkg = fs.existsSync(path.join(workDir, 'package.json')) || fs.existsSync(path.join(workDir, 'requirements.txt'));
    checks.push({ label: 'Project manifest (package.json/requirements.txt)', passed: hasPkg });
    if (hasPkg) score += 15;

    // 2. Has git repo
    const hasGit = fs.existsSync(path.join(workDir, '.git'));
    checks.push({ label: 'Git repository initialized', passed: hasGit });
    if (hasGit) score += 15;

    // 3. Has .gitignore
    const hasGitignore = fs.existsSync(path.join(workDir, '.gitignore'));
    checks.push({ label: '.gitignore file present', passed: hasGitignore });
    if (hasGitignore) score += 10;

    // 4. Has README
    const hasReadme = fs.existsSync(path.join(workDir, 'README.md')) || fs.existsSync(path.join(workDir, 'readme.md'));
    checks.push({ label: 'README.md documentation', passed: hasReadme });
    if (hasReadme) score += 10;

    // 5. No .env in git
    let envSafe = true;
    if (hasGit && fs.existsSync(path.join(workDir, '.env'))) {
      try {
        const gitignoreContent = fs.existsSync(path.join(workDir, '.gitignore')) ? fs.readFileSync(path.join(workDir, '.gitignore'), 'utf8') : '';
        envSafe = gitignoreContent.includes('.env');
      } catch (_) {}
    }
    checks.push({ label: '.env file excluded from git', passed: envSafe });
    if (envSafe) score += 10;

    // 6. Has tests
    const hasTests = fs.existsSync(path.join(workDir, 'tests')) || fs.existsSync(path.join(workDir, '__tests__')) ||
                     fs.existsSync(path.join(workDir, 'test')) || fs.existsSync(path.join(workDir, 'spec'));
    checks.push({ label: 'Test directory present', passed: hasTests });
    if (hasTests) score += 15;

    // 7. No node_modules committed (quick check)
    const noNodeModules = !hasGit || !fs.existsSync(path.join(workDir, 'node_modules', '.package-lock.json'));
    checks.push({ label: 'node_modules not committed', passed: noNodeModules });
    if (noNodeModules) score += 10;

    // 8. Has at least some code files
    let codeFiles = 0;
    try {
      const files = fs.readdirSync(workDir);
      codeFiles = files.filter(f => /\.(js|ts|py|php|rb|go|rs|java|c|cpp|html|css)$/i.test(f)).length;
    } catch (_) {}
    const hasCode = codeFiles > 0;
    checks.push({ label: 'Has code files', passed: hasCode });
    if (hasCode) score += 15;

    const grade = score >= 90 ? 'Excellent' : score >= 70 ? 'Good' : score >= 50 ? 'Fair' : 'Needs Work';
    const passedCount = checks.filter(c => c.passed).length;

    res.json({ ok: true, score, grade, summary: `${passedCount}/${checks.length} checks passed`, checks });
  } catch (e) {
    res.json({ ok: true, score: 0, grade: 'Error', summary: e.message, checks: [] });
  }
});

// ═══════════════════════════════════════════════════════════════════════════
// ── Marketplace (npm package search + install) ──────────────────────────────
// ═══════════════════════════════════════════════════════════════════════════

router.get('/marketplace', requireSession, (req, res) => {
  const q = req.query.q;
  if (!q) return res.json({ ok: true, packages: [] });

  // Search npm registry
  const url = `https://registry.npmjs.org/-/v1/search?text=${encodeURIComponent(q)}&size=12`;
  https.get(url, (npmRes) => {
    const chunks = [];
    npmRes.on('data', c => chunks.push(c));
    npmRes.on('end', () => {
      try {
        const data = JSON.parse(Buffer.concat(chunks).toString('utf8'));
        const packages = (data.objects || []).map(o => ({
          name: o.package.name,
          description: (o.package.description || '').slice(0, 100),
          version: o.package.version,
          score: o.score?.final || 0,
        }));
        res.json({ ok: true, packages });
      } catch (e) {
        res.json({ ok: true, packages: [] });
      }
    });
  }).on('error', () => res.json({ ok: true, packages: [] }));
});

router.post('/marketplace/install', requireSession, (req, res) => {
  const daUsername = req.user?.daUsername;
  const pkgName = req.body?.package;
  if (!daUsername || !pkgName) return res.status(400).json({ ok: false, error: 'Missing params' });

  // SECURITY (R3-04): Validate daUsername
  if (!/^[a-zA-Z0-9_]{1,16}$/.test(daUsername)) {
    return res.status(400).json({ ok: false, error: 'Invalid username' });
  }

  // Stricter package name validation
  if (!/^(@[a-z0-9][a-z0-9._-]*\/)?[a-z0-9][a-z0-9._-]*$/i.test(pkgName)) {
    return res.status(400).json({ ok: false, error: 'Invalid package name' });
  }

  const workDir = `/home/${daUsername}/ide-workspace`;
  if (!fs.existsSync(workDir)) return res.status(400).json({ ok: false, error: 'No workspace found' });

  // SECURITY (R3-04): Use execFile (no shell) to prevent command injection
  const { execFile: execFilePkg } = require('child_process');
  execFilePkg('npm', ['install', '--save', pkgName], { cwd: workDir, timeout: 60000 }, (err, stdout, stderr) => {
    if (err) return res.json({ ok: false, error: 'Install failed', message: (stdout || '').slice(0, 300) });
    logActivity(req.user?.whmcsClientId || req.user?.clientId, '📦', 'Package installed', pkgName);
    res.json({ ok: true, message: `${pkgName} installed successfully!`, output: stdout.slice(0, 300) });
  });
});

// ═══════════════════════════════════════════════════════════════════════════
// ── AI Image Generator ──────────────────────────────────────────────────────
// ═══════════════════════════════════════════════════════════════════════════

router.post('/generate-image', requireSession, async (req, res) => {
  try {
    const { prompt } = req.body;
    if (!prompt) return res.status(400).json({ ok: false, error: 'Prompt required' });

    const apiKey = config.apiKeys.openai;
    if (!apiKey) return res.status(503).json({ ok: false, error: 'Image generation not configured' });

    const body = JSON.stringify({
      model: 'dall-e-3',
      prompt: prompt.slice(0, 1000),
      n: 1,
      size: '1024x1024',
      quality: 'standard',
    });

    const url = new URL('/v1/images/generations', 'https://api.openai.com');
    const apiReq = https.request(url.href, {
      method: 'POST',
      headers: {
        'content-type': 'application/json',
        'authorization': `Bearer ${apiKey}`,
        'content-length': Buffer.byteLength(body),
      },
    }, (apiRes) => {
      const chunks = [];
      apiRes.on('data', c => chunks.push(c));
      apiRes.on('end', () => {
        try {
          const data = JSON.parse(Buffer.concat(chunks).toString('utf8'));
          if (data.data?.[0]?.url) {
            const clientId = req.user?.whmcsClientId || req.user?.clientId;
            logActivity(clientId, '🖼️', 'Image generated', prompt.slice(0, 50));
            res.json({ ok: true, url: data.data[0].url, revised_prompt: data.data[0].revised_prompt });
          } else {
            res.json({ ok: false, error: data.error?.message || 'Generation failed' });
          }
        } catch (e) {
          res.json({ ok: false, error: 'Failed to parse response' });
        }
      });
    });

    apiReq.on('error', (e) => res.json({ ok: false, error: safeError(e) }));
    apiReq.setTimeout(30000, () => { apiReq.destroy(); res.json({ ok: false, error: 'Timeout' }); });
    apiReq.write(body);
    apiReq.end();
  } catch (e) {
    res.status(500).json({ ok: false, error: safeError(e) });
  }
});

// ═══════════════════════════════════════════════════════════════════════════
// ── AI Video Generator (via Together.ai — Veo 2 / async polling) ────────────
// ═══════════════════════════════════════════════════════════════════════════

// In-memory job tracker (video jobs are long-running, 1-5 min)
const VIDEO_JOBS = new Map();

// Supported video models via Together.ai
const VIDEO_MODELS = {
  'veo-2':              'google/veo-2.0',
  'veo-3':              'google/veo-3.0',
  'veo-3-fast':         'google/veo-3.0-fast',
  'hailuo-02':          'minimax/hailuo-02',
  'kling-2.1-standard': 'kwaivgI/kling-2.1-standard',
  'wan-t2v':            'Wan-AI/Wan2.2-T2V-A14B',
  'default':            'google/veo-2.0',
};

router.post('/generate-video', requireSession, async (req, res) => {
  try {
    const { prompt, model, duration } = req.body;
    if (!prompt) return res.status(400).json({ ok: false, error: 'Prompt required' });

    const apiKey = config.apiKeys.together;
    if (!apiKey) return res.status(503).json({ ok: false, error: 'Video generation not configured' });

    const clientId = req.user?.whmcsClientId || req.user?.clientId;

    // Check token allowance (video costs 25,000 tokens)
    try {
      const tc = require('../tokens/tokenCounter');
      const usage = await tc.getUsage(clientId);
      if (usage.limit > 0 && usage.used + 25000 > usage.limit) {
        return res.status(429).json({ ok: false, error: 'Insufficient tokens for video generation (costs 25,000 tokens)' });
      }
    } catch (_) {}

    const modelId = VIDEO_MODELS[model] || VIDEO_MODELS['default'];
    const dur = String(Math.min(Math.max(Math.round(duration || 5), 1), 10));

    const body = JSON.stringify({
      model: modelId,
      prompt: prompt.slice(0, 2000),
      payload: {},
      seconds: dur,
    });

    // Submit to Together.ai async video API
    const submitUrl = new URL('https://api.together.xyz/v2/videos');
    const submitReq = https.request(submitUrl.href, {
      method: 'POST',
      headers: {
        'content-type': 'application/json',
        'authorization': `Bearer ${apiKey}`,
        'content-length': Buffer.byteLength(body),
      },
    }, (apiRes) => {
      const chunks = [];
      apiRes.on('data', c => chunks.push(c));
      apiRes.on('end', () => {
        try {
          const data = JSON.parse(Buffer.concat(chunks).toString('utf8'));
          if (data.id) {
            // Store job metadata
            VIDEO_JOBS.set(data.id, {
              clientId,
              model: modelId,
              prompt: prompt.slice(0, 200),
              status: 'queued',
              submittedAt: Date.now(),
              duration: dur,
            });

            // Start background polling
            pollVideoJob(data.id, apiKey, clientId);

            logActivity(clientId, '🎬', 'Video generation started', prompt.slice(0, 50));
            res.json({ ok: true, jobId: data.id, status: 'queued', message: `Video generation started! Model: ${modelId}, Duration: ${dur}s. Poll /api/extras/video-status/${data.id} for updates.` });
          } else {
            res.json({ ok: false, error: data.error?.message || 'Failed to submit video job' });
          }
        } catch (e) {
          res.json({ ok: false, error: 'Failed to parse Together.ai response' });
        }
      });
    });

    submitReq.on('error', (e) => res.json({ ok: false, error: safeError(e) }));
    submitReq.setTimeout(30000, () => { submitReq.destroy(); res.json({ ok: false, error: 'Timeout submitting video job' }); });
    submitReq.write(body);
    submitReq.end();
  } catch (e) {
    logger.error(`generate-video: ${e.message}`);
    res.status(500).json({ ok: false, error: safeError(e) });
  }
});

router.get('/video-status/:jobId', requireSession, async (req, res) => {
  try {
    const { jobId } = req.params;
    if (!jobId || !/^[a-zA-Z0-9_-]+$/.test(jobId)) {
      return res.status(400).json({ ok: false, error: 'Invalid job ID' });
    }

    // Check local cache first
    const cached = VIDEO_JOBS.get(jobId);
    if (cached && cached.status === 'completed') {
      return res.json({ ok: true, ...cached });
    }
    if (cached && cached.status === 'failed') {
      return res.json({ ok: false, ...cached });
    }

    // Poll Together.ai directly
    const apiKey = config.apiKeys.together;
    if (!apiKey) return res.status(503).json({ ok: false, error: 'Not configured' });

    const pollUrl = `https://api.together.xyz/v2/videos/${encodeURIComponent(jobId)}`;
    https.get(pollUrl, {
      headers: { 'authorization': `Bearer ${apiKey}` },
    }, (apiRes) => {
      const chunks = [];
      apiRes.on('data', c => chunks.push(c));
      apiRes.on('end', () => {
        try {
          const data = JSON.parse(Buffer.concat(chunks).toString('utf8'));
          const result = {
            ok: true,
            jobId,
            status: data.status || 'unknown',
            videoUrl: data.outputs?.video_url || null,
            model: cached?.model || data.model,
            prompt: cached?.prompt || '',
          };
          res.json(result);
        } catch (e) {
          res.json({ ok: false, error: 'Failed to parse status response' });
        }
      });
    }).on('error', (e) => res.json({ ok: false, error: safeError(e) }));
  } catch (e) {
    res.status(500).json({ ok: false, error: safeError(e) });
  }
});

// Background poller — updates VIDEO_JOBS map when job completes
async function pollVideoJob(jobId, apiKey, clientId) {
  const POLL_INTERVAL = 8000;  // 8 seconds
  const MAX_POLLS = 75;        // 10 minutes max

  for (let i = 0; i < MAX_POLLS; i++) {
    await new Promise(r => setTimeout(r, POLL_INTERVAL));

    try {
      const data = await new Promise((resolve, reject) => {
        const pollUrl = `https://api.together.xyz/v2/videos/${encodeURIComponent(jobId)}`;
        https.get(pollUrl, {
          headers: { 'authorization': `Bearer ${apiKey}` },
        }, (apiRes) => {
          const chunks = [];
          apiRes.on('data', c => chunks.push(c));
          apiRes.on('end', () => {
            try { resolve(JSON.parse(Buffer.concat(chunks).toString('utf8'))); }
            catch (e) { reject(e); }
          });
        }).on('error', reject);
      });

      if (data.status === 'completed') {
        const job = VIDEO_JOBS.get(jobId) || {};
        job.status = 'completed';
        job.videoUrl = data.outputs?.video_url;
        job.completedAt = Date.now();
        VIDEO_JOBS.set(jobId, job);

        // Charge tokens
        try {
          const tc = require('../tokens/tokenCounter');
          await tc.addUsage(clientId, 0, 25000, 1);
        } catch (_) {}

        logActivity(clientId, '🎬', 'Video generated', job.prompt || '');
        logger.info(`video-poll: job ${jobId} completed in ${Math.round((Date.now() - (job.submittedAt || Date.now())) / 1000)}s`);

        // Clean up after 30 min
        setTimeout(() => VIDEO_JOBS.delete(jobId), 30 * 60 * 1000);
        return;
      }

      if (data.status === 'failed' || data.status === 'cancelled') {
        const job = VIDEO_JOBS.get(jobId) || {};
        job.status = 'failed';
        job.error = data.error?.message || data.status;
        VIDEO_JOBS.set(jobId, job);
        logger.error(`video-poll: job ${jobId} ${data.status}: ${job.error}`);
        setTimeout(() => VIDEO_JOBS.delete(jobId), 10 * 60 * 1000);
        return;
      }

      // Update status
      const job = VIDEO_JOBS.get(jobId) || {};
      job.status = data.status || 'in_progress';
      VIDEO_JOBS.set(jobId, job);
    } catch (err) {
      logger.warn(`video-poll: error polling ${jobId}: ${err.message}`);
    }
  }

  // Timed out
  const job = VIDEO_JOBS.get(jobId) || {};
  job.status = 'failed';
  job.error = 'Timed out after 10 minutes';
  VIDEO_JOBS.set(jobId, job);
  logger.error(`video-poll: job ${jobId} timed out`);
}

// ═══════════════════════════════════════════════════════════════════════════

module.exports = router;
module.exports.logActivity = logActivity;
module.exports.unlockAchievement = unlockAchievement;
module.exports.fireWebhooks = fireWebhooks;
