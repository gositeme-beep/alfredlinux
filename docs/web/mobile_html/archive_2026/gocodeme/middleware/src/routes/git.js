'use strict';

/**
 * Git Routes — /api/git/:username/*
 *
 * All operations run git directly on the local filesystem (no DA API round-trip).
 * Customer home dirs live on the same machine, accessible as the hosting user.
 *
 * Endpoints:
 *   POST   /api/git/:username/checkpoint  — stage + commit (AI agent calls this)
 *   POST   /api/git/:username/revert      — revert HEAD (undo last AI commit)
 *   GET    /api/git/:username/log         — recent commit log (structured JSON)
 *   GET    /api/git/:username/status      — working-tree status
 *   GET    /api/git/:username/diff        — diff HEAD vs working tree
 *   POST   /api/git/:username/init        — explicitly initialise repo
 */

const express = require('express');
const router  = express.Router({ mergeParams: true });

const { runGit, ensureRepo, commitNow, resolveWorkDir } = require('../git/worker');
const { requireSession, requireOwnResource } = require('../auth/middleware');
const logger = require('../logger');
const safeError = require('../utils/safeError');

router.use(requireSession);
router.use(requireOwnResource);

// ── Helper: resolve workspace dir, return 400 if not found ────────────────
function getWorkDir(req, res) {
  const { username } = req.params;
  const relPath = req.query.workspace || req.body?.workspace || null;
  const workDir = resolveWorkDir(username, relPath);
  if (!workDir) {
    res.status(400).json({ ok: false, error: `Workspace not found for ${username}` });
    return null;
  }
  return workDir;
}

// ── POST /checkpoint ───────────────────────────────────────────────────────
router.post('/checkpoint', async (req, res) => {
  const workDir = getWorkDir(req, res);
  if (!workDir) return;
  const { username } = req.params;
  const message = req.body?.message || `GoCodeMe checkpoint [${username}]`;
  try {
    const output = await commitNow({ workDir, daUsername: username, message });
    logger.info(`git checkpoint: ${username} @ ${workDir}`);
    res.json({ ok: true, output: output || '(nothing to commit)' });
  } catch (err) {
    logger.error(`git checkpoint error: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── POST /revert ───────────────────────────────────────────────────────────
router.post('/revert', async (req, res) => {
  const workDir = getWorkDir(req, res);
  if (!workDir) return;
  const { username } = req.params;
  try {
    await ensureRepo(workDir, username);
    // Use --no-commit so we can detect the "nothing to revert" case,
    // then manually commit the result (or report it as a no-op).
    const statusBefore = await runGit(workDir, ['status', '--porcelain']);
    const output = await runGit(workDir, ['revert', 'HEAD', '--no-edit']);
    // If output indicates nothing changed, still return ok (idempotent)
    if (output.includes('nothing to commit') || output.includes('nothing added')) {
      logger.info(`git revert (no-op): ${username} @ ${workDir}`);
      return res.json({ ok: true, output: 'Revert was a no-op (HEAD had no tracked changes to undo)', noOp: true });
    }
    logger.info(`git revert: ${username} @ ${workDir}`);
    res.json({ ok: true, output });
  } catch (err) {
    // git revert exits non-zero when nothing to revert — treat gracefully
    const msg = err.message || '';
    if (msg.includes('nothing to commit') || msg.includes('nothing added') || msg.includes('empty commit')) {
      return res.json({ ok: true, output: 'Revert was a no-op (nothing to undo)', noOp: true });
    }
    logger.error(`git revert error: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── GET /log ───────────────────────────────────────────────────────────────
router.get('/log', async (req, res) => {
  const workDir = getWorkDir(req, res);
  if (!workDir) return;
  const limit = Math.min(parseInt(req.query.limit || '20', 10), 100);
  try {
    const raw = await runGit(workDir, [
      'log', `--max-count=${limit}`, '--pretty=format:%H|%s|%an|%ai',
    ]);
    const commits = raw
      ? raw.split('\n').filter(Boolean).map(line => {
          const [hash, subject, author, date] = line.split('|');
          return { hash: hash?.slice(0, 8), subject, author, date };
        })
      : [];
    res.json({ ok: true, commits });
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── GET /status ────────────────────────────────────────────────────────────
router.get('/status', async (req, res) => {
  const workDir = getWorkDir(req, res);
  if (!workDir) return;
  try {
    const output = await runGit(workDir, ['status', '--short']);
    const files = output
      ? output.split('\n').filter(Boolean).map(l => ({ flag: l.slice(0,2).trim(), file: l.slice(3) }))
      : [];
    res.json({ ok: true, clean: files.length === 0, files });
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── GET /diff ──────────────────────────────────────────────────────────────
router.get('/diff', async (req, res) => {
  const workDir = getWorkDir(req, res);
  if (!workDir) return;
  const args = ['diff', 'HEAD'];
  if (req.query.file) args.push('--', req.query.file);
  try {
    const output = await runGit(workDir, args);
    res.json({ ok: true, diff: output });
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── GET /review — Multi-file change summary with per-file diffs ───────────
router.get('/review', async (req, res) => {
  const workDir = getWorkDir(req, res);
  if (!workDir) return;
  try {
    // Stage untracked files so they show in diff
    await runGit(workDir, ['add', '-N', '.']);

    // Get stat summary
    const statRaw = await runGit(workDir, ['diff', 'HEAD', '--stat']);
    const diffRaw = await runGit(workDir, ['diff', 'HEAD', '--name-status']);

    const files = diffRaw
      ? diffRaw.split('\n').filter(Boolean).map(line => {
          const [status, ...pathParts] = line.split('\t');
          const filePath = pathParts.join('\t');
          const statusMap = { M: 'modified', A: 'added', D: 'deleted', R: 'renamed', C: 'copied' };
          return { status: statusMap[status] || status, file: filePath };
        })
      : [];

    // Get per-file short diffs (limited to 50 lines each to avoid huge responses)
    const changes = [];
    for (const f of files.slice(0, 50)) {
      try {
        const fileDiff = await runGit(workDir, ['diff', 'HEAD', '--', f.file]);
        const lines = fileDiff.split('\n');
        changes.push({
          ...f,
          diff: lines.slice(0, 100).join('\n') + (lines.length > 100 ? '\n... (truncated)' : ''),
          linesChanged: lines.filter(l => l.startsWith('+') || l.startsWith('-')).length,
        });
      } catch {
        changes.push({ ...f, diff: '(diff unavailable)', linesChanged: 0 });
      }
    }

    res.json({
      ok: true,
      summary: statRaw || '(no changes)',
      totalFiles: files.length,
      changes,
    });
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── POST /init ─────────────────────────────────────────────────────────────
router.post('/init', async (req, res) => {
  const workDir = getWorkDir(req, res);
  if (!workDir) return;
  const { username } = req.params;
  try {
    await ensureRepo(workDir, username);
    res.json({ ok: true, workDir, message: 'Repository initialised' });
  } catch (err) {
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── POST /clone — Clone a public GitHub/GitLab/Bitbucket repo ──────────────
router.post('/clone', async (req, res) => {
  const { username } = req.params;
  const { repoUrl } = req.body || {};

  if (!repoUrl) {
    return res.status(400).json({ ok: false, error: 'repoUrl required' });
  }

  // Validate URL — only allow public Git hosting providers (HTTPS only)
  // SECURITY (R2-17): Parse with URL constructor to reject credentials, non-https,
  // and hosts not in the allowlist. Regex alone can be bypassed with edge cases.
  let parsedUrl;
  try {
    parsedUrl = new URL(repoUrl);
  } catch {
    return res.status(400).json({ ok: false, error: 'Invalid URL format' });
  }

  const allowedHosts = ['github.com', 'gitlab.com', 'bitbucket.org'];
  if (parsedUrl.protocol !== 'https:') {
    return res.status(400).json({ ok: false, error: 'Only HTTPS URLs are supported' });
  }
  if (!allowedHosts.includes(parsedUrl.hostname)) {
    return res.status(400).json({ ok: false, error: 'Only GitHub, GitLab, and Bitbucket repos are supported' });
  }
  if (parsedUrl.username || parsedUrl.password) {
    return res.status(400).json({ ok: false, error: 'URLs with credentials are not allowed' });
  }
  if (parsedUrl.port) {
    return res.status(400).json({ ok: false, error: 'Non-standard ports are not allowed' });
  }

  // Sanitize: strip query params, fragments, and trailing .git
  const cleanUrl = repoUrl.split('?')[0].split('#')[0];

  // Derive repo name for the target directory
  const repoName = cleanUrl.replace(/\.git$/, '').split('/').pop() || 'project';
  const safeRepoName = repoName.replace(/[^a-zA-Z0-9._-]/g, '_').slice(0, 64);

  const workDir = resolveWorkDir(username, null);
  if (!workDir) {
    return res.status(400).json({ ok: false, error: `Workspace not found for ${username}. Launch IDE first.` });
  }

  const targetDir = require('path').join(workDir, safeRepoName);

  // Check if target already exists
  if (require('fs').existsSync(targetDir)) {
    return res.status(409).json({ ok: false, error: `Directory "${safeRepoName}" already exists in workspace. Delete it first or use a different repo.` });
  }

  try {
    // Clone with depth 1 for speed, timeout 120s
    const { execFile } = require('child_process');
    const { promisify } = require('util');
    const execFileAsync = promisify(execFile);

    const { stdout, stderr } = await execFileAsync('git', [
      'clone', '--depth', '1', cleanUrl, targetDir
    ], {
      timeout: 120_000,
      env: { ...process.env, GIT_TERMINAL_PROMPT: '0' },
    });

    const output = (stdout + stderr).trim();
    logger.info(`git clone: ${username} cloned ${cleanUrl} → ${safeRepoName}`);
    res.json({
      ok: true,
      repoName: safeRepoName,
      message: `Cloned "${safeRepoName}" successfully`,
      output: output.slice(0, 500),
    });
  } catch (err) {
    const msg = ((err.stderr || '') + (err.stdout || '') + err.message).trim();
    logger.error(`git clone error (${username}): ${msg.slice(0, 200)}`);

    if (msg.includes('not found') || msg.includes('404')) {
      return res.status(404).json({ ok: false, error: 'Repository not found. Make sure the URL is correct and the repo is public.' });
    }
    res.status(500).json({ ok: false, error: 'Clone failed. Ensure the repo URL is correct and publicly accessible.' });
  }
});

module.exports = router;
