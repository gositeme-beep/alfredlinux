#!/usr/bin/env node
'use strict';

/**
 * GoCodeMe — Scheduled Tasks Runner
 * Runs via PM2 with cron_restart: "0 3 * * *" (daily at 3 AM)
 *
 * Tasks:
 *  1. Stale workspace garbage collection (7+ days idle)
 *  2. PM2 process list save (persist for resurrect)
 *  3. Log rotation check
 *  4. File integrity check
 *  5. Record health check
 *  6. Meilisearch re-index
 *  7. Webhook retry
 *  8. Session cleanup
 *  9. Site health audit (images, assets, CSS, rendering)
 */

const fs   = require('fs');
const path = require('path');
const { execSync } = require('child_process');

const LOG = (msg) => console.log(`[scheduler] ${new Date().toISOString()} — ${msg}`);

// ── 1. Stale Workspace GC ───────────────────────────────────────────────────
function gcWorkspaces() {
  const MAX_AGE_MS = 7 * 24 * 60 * 60 * 1000; // 7 days
  const prefix = 'gocodeme-workspace-';
  const tmpDir = '/tmp';
  let removed = 0;

  try {
    const entries = fs.readdirSync(tmpDir).filter(e => e.startsWith(prefix));
    const now = Date.now();

    for (const entry of entries) {
      const fullPath = path.join(tmpDir, entry);
      try {
        const stat = fs.statSync(fullPath);
        if (!stat.isDirectory()) continue;

        const age = now - stat.atimeMs;
        if (age >= MAX_AGE_MS) {
          const username = entry.replace(prefix, '');
          LOG(`  GC: removing ${entry} (user: ${username}, idle: ${Math.round(age / 86400000)}d)`);
          fs.rmSync(fullPath, { recursive: true, force: true });
          removed++;
        }
      } catch (err) {
        LOG(`  GC: error checking ${entry}: ${err.message}`);
      }
    }
  } catch (err) {
    LOG(`GC: error scanning /tmp: ${err.message}`);
  }

  LOG(`GC: removed ${removed} stale workspace(s)`);
}

// ── 2. PM2 Process Save ─────────────────────────────────────────────────────
function pm2Save() {
  try {
    execSync('pm2 save --force', { stdio: 'pipe', timeout: 10000 });
    LOG('PM2: process list saved');
  } catch (err) {
    LOG(`PM2: save failed — ${err.message}`);
  }
}

// ── 3. Log Rotation ─────────────────────────────────────────────────────────
function rotateLogs() {
  const logDir = path.join(__dirname, '..', 'logs');
  if (!fs.existsSync(logDir)) return;

  const MAX_SIZE = 50 * 1024 * 1024; // 50MB

  try {
    const files = fs.readdirSync(logDir).filter(f => f.endsWith('.log'));
    for (const file of files) {
      const filePath = path.join(logDir, file);
      const stat = fs.statSync(filePath);
      if (stat.size > MAX_SIZE) {
        const rotated = filePath + '.' + new Date().toISOString().slice(0, 10);
        fs.renameSync(filePath, rotated);
        fs.writeFileSync(filePath, '');
        LOG(`Logs: rotated ${file} (${Math.round(stat.size / 1024 / 1024)}MB)`);
      }
    }
  } catch (err) {
    LOG(`Logs: rotation error — ${err.message}`);
  }
}

// ── 4. File Integrity Check ──────────────────────────────────────────────────
function integrityCheck() {
  try {
    const result = execSync(
      `node ${path.join(__dirname, 'integrity-monitor.js')} --cron`,
      { encoding: 'utf-8', timeout: 30000 }
    );
    LOG('Integrity: check passed');
    if (result) console.log(result);
  } catch (err) {
    LOG(`Integrity: ⚠ ALERTS DETECTED — check ~/.gocodeme/integrity-alerts.log`);
    if (err.stdout) console.log(err.stdout);
    if (err.stderr) console.error(err.stderr);
  }
}

// ── 5. Record Health Check ───────────────────────────────────────────────────
function recordHealth() {
  try {
    const result = execSync(
      'php ' + path.resolve(__dirname, '../../scripts/record-health.php'),
      { encoding: 'utf-8', timeout: 15000 }
    );
    LOG(`Health: ${result.trim()}`);
  } catch (err) {
    LOG(`Health: recording failed — ${err.message}`);
  }
}

// ── 6. Meilisearch Re-index ─────────────────────────────────────────────────
function reindexMeili() {
  try {
    const result = execSync(
      'php ' + path.resolve(__dirname, '../../scripts/index-meilisearch.php'),
      { encoding: 'utf-8', timeout: 60000 }
    );
    const lines = result.split('\n').filter(l => /Found|documents/.test(l));
    LOG(`Meilisearch: re-indexed — ${lines.join('; ')}`);
  } catch (err) {
    LOG(`Meilisearch: re-index failed — ${err.message}`);
  }
}

// ── 7. Webhook Retry ─────────────────────────────────────────────────────────
function retryWebhooks() {
  try {
    const result = execSync(
      'php ' + path.resolve(__dirname, '../../scripts/retry-webhooks.php'),
      { encoding: 'utf-8', timeout: 30000 }
    );
    LOG(`Webhooks: ${result.trim()}`);
  } catch (err) {
    LOG(`Webhooks: retry failed — ${err.message}`);
  }
}

// ── 8. Session Cleanup ──────────────────────────────────────────────────────
function cleanupSessions() {
  const sessDir = '/tmp';
  const MAX_AGE_MS = 24 * 60 * 60 * 1000; // 24h
  let removed = 0;

  try {
    const entries = fs.readdirSync(sessDir).filter(e => e.startsWith('sess_'));
    const now = Date.now();
    for (const entry of entries) {
      try {
        const fullPath = path.join(sessDir, entry);
        const stat = fs.statSync(fullPath);
        if (now - stat.mtimeMs >= MAX_AGE_MS) {
          fs.unlinkSync(fullPath);
          removed++;
        }
      } catch (_) {}
    }
  } catch (_) {}

  LOG(`Sessions: cleaned ${removed} stale session file(s)`);
}

// ── 9. Site Health Audit ─────────────────────────────────────────────────────
function siteAudit() {
  try {
    const result = execSync(
      'php ' + path.resolve(__dirname, '../../scripts/site-audit.php'),
      { encoding: 'utf-8', timeout: 60000 }
    );
    const lines = result.split('\n').filter(l => l.trim());
    const summary = lines.find(l => /Summary:|All checks passed/.test(l)) || lines[lines.length - 1];
    LOG(`Site Audit: ${summary.trim()}`);
    if (result.includes('✗')) {
      // Log full output when critical issues found
      console.log(result);
    }
  } catch (err) {
    LOG(`Site Audit: issues detected — check ~/.gocodeme/site-audit.log`);
    if (err.stdout) console.log(err.stdout);
  }
}

// ── Run all tasks ────────────────────────────────────────────────────────────
LOG('Starting scheduled tasks...');
gcWorkspaces();
pm2Save();
rotateLogs();
integrityCheck();
recordHealth();
reindexMeili();
retryWebhooks();
cleanupSessions();
siteAudit();
LOG('All tasks complete (9/9). Exiting.');

process.exit(0);
