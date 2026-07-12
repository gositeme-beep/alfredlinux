'use strict';

/**
 * File Manager Routes
 *
 * GET    /api/files/:username          - List files in a directory
 * GET    /api/files/:username/read     - Read a single file
 * POST   /api/files/:username          - Write/create a file
 * DELETE /api/files/:username          - Delete a file or directory
 * PATCH  /api/files/:username/rename   - Rename/move a file
 * GET    /api/files/:username/stat     - Stat a file
 * POST   /api/files/:username/mkdir    - Create a directory
 *
 * All routes require:
 *  - Valid session JWT (requireSession)
 *  - Username in path matching the authenticated user (requireOwnResource)
 */

const express = require('express');
const router = express.Router({ mergeParams: true });

const fm = require('../directadmin/fileManager');
const { requireSession, requireOwnResource } = require('../auth/middleware');
const { scheduleCommit, resolveWorkDir } = require('../git/worker');
const logger = require('../logger');
const safeError = require('../utils/safeError');

// Trigger a debounced auto-commit after any mutating file operation.
// Fails silently — git errors never break the file API response.
function autoCommit(daUsername, filePath, action) {
  try {
    const workDir = resolveWorkDir(daUsername);
    if (workDir) scheduleCommit({ workDir, daUsername, filePath, action });
  } catch (_) { /* non-fatal */ }
}

router.use(requireSession);
router.use(requireOwnResource);

// ── List files ─────────────────────────────────────────────────────────────
router.get('/', async (req, res) => {
  try {
    const dirPath = req.query.path || 'public_html';
    const files = await fm.listFiles(req.params.username, dirPath);
    res.json({ ok: true, files });
  } catch (err) {
    logger.error(`listFiles error: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── Read file ──────────────────────────────────────────────────────────────
router.get('/read', async (req, res) => {
  try {
    const filePath = req.query.path;
    if (!filePath) return res.status(400).json({ ok: false, error: 'path query param required' });

    const content = await fm.readFile(req.params.username, filePath);
    res.json({ ok: true, content });
  } catch (err) {
    logger.error(`readFile error: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── Write/create file ──────────────────────────────────────────────────────
router.post('/', async (req, res) => {
  try {
    const { path: filePath, content } = req.body;
    if (!filePath) return res.status(400).json({ ok: false, error: 'path is required' });
    if (content === undefined) return res.status(400).json({ ok: false, error: 'content is required' });

    await fm.writeFile(req.params.username, filePath, content);
    autoCommit(req.params.username, filePath, 'write');
    res.json({ ok: true, message: `File written: ${filePath}` });
  } catch (err) {
    logger.error(`writeFile error: ${err.message}`);
    const status = err.message.includes('traversal') ? 403 : 500;
    res.status(status).json({ ok: false, error: safeError(err) });
  }
});

// ── Delete file ────────────────────────────────────────────────────────────
router.delete('/', async (req, res) => {
  try {
    const filePath = req.query.path || req.body.path;
    if (!filePath) return res.status(400).json({ ok: false, error: 'path is required' });

    await fm.deleteFile(req.params.username, filePath);
    autoCommit(req.params.username, filePath, 'delete');
    res.json({ ok: true, message: `Deleted: ${filePath}` });
  } catch (err) {
    logger.error(`deleteFile error: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── Rename/move file ───────────────────────────────────────────────────────
router.patch('/rename', async (req, res) => {
  try {
    const { oldPath, newPath } = req.body;
    if (!oldPath || !newPath) {
      return res.status(400).json({ ok: false, error: 'oldPath and newPath are required' });
    }

    await fm.renameFile(req.params.username, oldPath, newPath);
    autoCommit(req.params.username, newPath, 'rename');
    res.json({ ok: true, message: `Renamed: ${oldPath} → ${newPath}` });
  } catch (err) {
    logger.error(`renameFile error: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── Stat file ──────────────────────────────────────────────────────────────
router.get('/stat', async (req, res) => {
  try {
    const filePath = req.query.path;
    if (!filePath) return res.status(400).json({ ok: false, error: 'path query param required' });

    const stat = await fm.statFile(req.params.username, filePath);
    res.json({ ok: true, stat });
  } catch (err) {
    logger.error(`statFile error: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

// ── Create directory ───────────────────────────────────────────────────────
router.post('/mkdir', async (req, res) => {
  try {
    const { path: dirPath } = req.body;
    if (!dirPath) return res.status(400).json({ ok: false, error: 'path is required' });

    await fm.createDirectory(req.params.username, dirPath);
    autoCommit(req.params.username, dirPath, 'mkdir');
    res.json({ ok: true, message: `Directory created: ${dirPath}` });
  } catch (err) {
    logger.error(`createDirectory error: ${err.message}`);
    res.status(500).json({ ok: false, error: safeError(err) });
  }
});

module.exports = router;
