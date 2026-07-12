/**
 * autopilotProxy.js — WebSocket + REST Bridge for Alfred Autopilot v8.0
 * ═══════════════════════════════════════════════════════════════════════
 *
 * REST endpoints (called by Alfred chat tools via MCP):
 *   POST /api/autopilot/start       — Start a persistent browser session
 *   POST /api/autopilot/action      — Execute action (navigate, click, type, etc.)
 *   POST /api/autopilot/observe     — Get current page state
 *   POST /api/autopilot/stop        — Stop the session
 *   POST /api/autopilot/approve     — [P1] Human approves pending action
 *   POST /api/autopilot/reject      — [P1] Human rejects pending action
 *   GET  /api/autopilot/pending     — [P1] Get pending action info
 *   GET  /api/autopilot/tabs        — [P1] List open tabs
 *   POST /api/autopilot/viewport    — [P3] Set viewport preset
 *   GET  /api/autopilot/downloads   — [P2] List downloaded files
 *   GET  /api/autopilot/network     — [P3] Get captured API network requests
 *   GET  /api/autopilot/history     — [P2] Get screenshot history timeline
 *   GET  /api/autopilot/status      — Active sessions overview
 *
 *   HUMAN-CENTRIC:
 *   POST /api/autopilot/undo        — [H4] Undo last action
 *   POST /api/autopilot/annotate    — [H12] Add annotation to viewport
 *   DELETE /api/autopilot/annotate  — [H12] Remove annotation
 *   GET  /api/autopilot/annotations — [H12] List annotations
 *   POST /api/autopilot/template/save  — [H13] Save session as template
 *   GET  /api/autopilot/templates      — [H13] List saved templates
 *   GET  /api/autopilot/template/:name — [H13] Get template details
 *   DELETE /api/autopilot/template/:name — [H13] Delete template
 *   POST /api/autopilot/batch          — [H14] Set batch queue
 *   GET  /api/autopilot/batch/status   — [H14] Get batch status
 *   POST /api/autopilot/batch/next     — [H14] Advance to next batch item
 *   POST /api/autopilot/schedule       — [H15] Create a schedule
 *   GET  /api/autopilot/schedules      — [H15] List schedules
 *   DELETE /api/autopilot/schedule/:id — [H15] Delete schedule
 *   GET  /api/autopilot/narration      — [H17] Get narration queue
 *   GET  /api/autopilot/sentiment      — [H6] Get current sentiment
 *
 * WebSocket endpoint (consumed by IDE panel):
 *   WS /api/autopilot/stream — Live JPEG frames + action/cursor/sentiment events
 */

'use strict';

const logger = require('../logger');

// WebSocket clients watching streams
const watchers = new Map();  // daUsername → Set<ws>
// Event handler references for cleanup
const eventHandlers = new Map(); // daUsername → handler function

/**
 * Helper: resolve dynamic import of autopilotSession.js (ESM from CJS).
 */
async function getAutopilotModule() {
  return import('../../../mcp-server/src/browser/autopilotSession.js');
}

/**
 * Register REST routes for autopilot control.
 */
function registerAutopilotRoutes(router) {

  // ── Start Autopilot Session ────────────────────────────────────────────
  router.post('/start', async (req, res) => {
    try {
      const daUsername = req._daUsername || req.body?.daUsername;
      const task = req.body?.task || req.body?.taskDescription || '';
      const opts = {};

      if (!daUsername) {
        return res.status(400).json({ error: 'Missing daUsername' });
      }

      // P0: Accept guardrail overrides
      if (req.body?.maxSteps) opts.maxSteps = Math.min(Number(req.body.maxSteps), 200);
      if (req.body?.maxDuration) opts.maxDuration = Math.min(Number(req.body.maxDuration), 1800_000);
      if (req.body?.viewport) opts.viewport = String(req.body.viewport);
      // P1: Human-in-the-loop
      if (req.body?.humanApproval !== undefined) opts.humanApproval = Boolean(req.body.humanApproval);
      // P2: Cookie persistence
      if (req.body?.persistCookies !== undefined) opts.persistCookies = Boolean(req.body.persistCookies);
      // H9: Geo-fence
      if (req.body?.allowedDomains && Array.isArray(req.body.allowedDomains)) {
        opts.allowedDomains = req.body.allowedDomains.map(String);
      }
      // H1: Sensitive field masking
      if (req.body?.sensitiveFieldMasking !== undefined) opts.sensitiveFieldMasking = Boolean(req.body.sensitiveFieldMasking);
      // H10: Retention policy
      if (req.body?.retentionPolicy) opts.retentionPolicy = String(req.body.retentionPolicy);
      // H16: Smart wait
      if (req.body?.smartWait !== undefined) opts.smartWait = Boolean(req.body.smartWait);
      // H18: High contrast
      if (req.body?.highContrast !== undefined) opts.highContrast = Boolean(req.body.highContrast);

      const { startSession, getSession, autopilotEvents } = await getAutopilotModule();

      // Kill any existing session
      const existing = getSession(daUsername);
      if (existing?.alive) {
        await existing.stop('new_session').catch(() => {});
      }
      // Clean up old event handler
      if (eventHandlers.has(daUsername)) {
        autopilotEvents.removeListener('autopilot', eventHandlers.get(daUsername));
        eventHandlers.delete(daUsername);
      }

      const session = await startSession(daUsername, task, opts);

      // Set up event relay to WebSocket watchers
      const handler = (evt) => {
        if (evt.daUsername !== daUsername) return;
        const clients = watchers.get(daUsername);
        if (!clients || clients.size === 0) return;

        if (evt.event === 'frame' && evt.screenshotBuffer) {
          // Binary JPEG frame — 9-byte header + JPEG payload
          const buf = evt.screenshotBuffer;
          const header = Buffer.alloc(9);
          header.writeUInt8(0x01, 0);
          if (evt.cursor) {
            header.writeUInt16BE(evt.cursor.x || 0, 1);
            header.writeUInt16BE(evt.cursor.y || 0, 3);
          }
          header.writeUInt32BE(evt.step || 0, 5);
          const payload = Buffer.concat([header, buf]);
          for (const ws of clients) {
            try { if (ws.readyState === 1) ws.send(payload, { binary: true }); } catch {}
          }
        } else if (evt.event === 'stopped') {
          const msg = JSON.stringify({ type: 'stopped', reason: evt.reason || 'unknown', daUsername });
          for (const ws of clients) {
            try { ws.send(msg); ws.close(); } catch {}
          }
          watchers.delete(daUsername);
          autopilotEvents.removeListener('autopilot', handler);
          eventHandlers.delete(daUsername);
        } else if (evt.event === 'approval_required') {
          const msg = JSON.stringify({
            type: 'approval_required',
            action: evt.action,
            details: evt.details,
            step: evt.step,
            message: evt.message,
            confidence: evt.confidence,
            preview: evt.preview ? { selector: evt.preview.selector, box: evt.preview.box } : null,
            daUsername,
          });
          for (const ws of clients) {
            try { if (ws.readyState === 1) ws.send(msg); } catch {}
          }
        } else if (evt.event === 'sentiment_changed') {
          const msg = JSON.stringify({
            type: 'sentiment_changed',
            sentiment: evt.sentiment,
            message: evt.message,
            step: evt.step,
            daUsername,
          });
          for (const ws of clients) {
            try { if (ws.readyState === 1) ws.send(msg); } catch {}
          }
        } else if (evt.event === 'frustration_detected') {
          const msg = JSON.stringify({
            type: 'frustration_detected',
            level: evt.level,
            message: evt.message,
            daUsername,
          });
          for (const ws of clients) {
            try { if (ws.readyState === 1) ws.send(msg); } catch {}
          }
        } else if (evt.event === 'celebration') {
          const msg = JSON.stringify({
            type: 'celebration',
            celebrationType: evt.type,
            message: evt.message,
            daUsername,
          });
          for (const ws of clients) {
            try { if (ws.readyState === 1) ws.send(msg); } catch {}
          }
        } else if (evt.event === 'narration') {
          const msg = JSON.stringify({
            type: 'narration',
            text: evt.text,
            priority: evt.priority,
            daUsername,
          });
          for (const ws of clients) {
            try { if (ws.readyState === 1) ws.send(msg); } catch {}
          }
        } else if (evt.event === 'annotation_added' || evt.event === 'annotation_removed' || evt.event === 'annotations_cleared') {
          const msg = JSON.stringify({ type: evt.event, ...evt });
          for (const ws of clients) {
            try { if (ws.readyState === 1) ws.send(msg); } catch {}
          }
        } else {
          // All other events (action, tab_opened, tab_switched, viewport_changed, download)
          const msg = JSON.stringify({ type: evt.event, ...evt });
          for (const ws of clients) {
            try { if (ws.readyState === 1) ws.send(msg); } catch {}
          }
        }
      };

      autopilotEvents.on('autopilot', handler);
      eventHandlers.set(daUsername, handler);

      // Track spectator count
      const watcherCount = watchers.get(daUsername)?.size || 0;
      session._spectatorCount = watcherCount;

      logger.info(`[Autopilot] Started session for ${daUsername}: "${task}" opts=${JSON.stringify(opts)}`);
      res.json({
        status: 'started',
        viewport: session._currentViewport,
        task,
        guardrails: {
          maxSteps: session.opts.maxSteps,
          maxDuration: `${Math.round(session.opts.maxDuration / 60_000)} min`,
          humanApproval: session.opts.humanApproval,
        },
        features: {
          sensitiveFieldMasking: session.opts.sensitiveFieldMasking,
          geoFence: session.opts.allowedDomains.length > 0 ? session.opts.allowedDomains : 'none',
          retentionPolicy: session.opts.retentionPolicy,
          smartWait: session.opts.smartWait,
        },
        streamUrl: `/api/autopilot/stream?user=${encodeURIComponent(daUsername)}`,
      });
    } catch (err) {
      logger.error(`[Autopilot] Start failed: ${err.message}`);
      res.status(500).json({ error: safeError(err) });
    }
  });

  // ── Execute Action ─────────────────────────────────────────────────────
  router.post('/action', async (req, res) => {
    try {
      const daUsername = req._daUsername || req.body?.daUsername;
      const { action, selector, text, value, key, url, direction, amount, script,
              description, timeout, tabIndex, preset, filePath } = req.body;

      if (!daUsername) return res.status(400).json({ error: 'Missing daUsername' });
      if (!action) return res.status(400).json({ error: 'Missing action' });

      const fieldChecks = {
        navigate:    () => url ? null : 'Missing url',
        click:       () => selector ? null : 'Missing selector',
        type:        () => selector ? null : 'Missing selector',
        press:       () => key ? null : 'Missing key',
        select:      () => (selector && value) ? null : 'Missing selector or value',
        hover:       () => selector ? null : 'Missing selector',
        wait:        () => selector ? null : 'Missing selector',
        script:      () => script ? null : 'Missing script',
        scroll:      () => null,
        switch_tab:  () => tabIndex !== undefined ? null : 'Missing tabIndex',
        set_viewport:() => preset ? null : 'Missing preset',
        upload_file: () => (selector && filePath) ? null : 'Missing selector or filePath',
        save_cookies:() => null,
        load_cookies:() => null,
        undo:        () => null,
      };
      if (fieldChecks[action]) {
        const err = fieldChecks[action]();
        if (err) return res.status(400).json({ error: err });
      }

      const { getSession } = await getAutopilotModule();
      const session = getSession(daUsername);
      if (!session?.alive) {
        return res.status(404).json({ error: 'No active autopilot session. Start one first.' });
      }

      let result;
      switch (action) {
        case 'navigate':     result = await session.navigate(url); break;
        case 'click':        result = await session.click(selector, description); break;
        case 'type':         result = await session.type(selector, text || value, description); break;
        case 'press':        result = await session.press(key, description); break;
        case 'scroll':       result = await session.scroll(direction, amount); break;
        case 'select':       result = await session.select(selector, value, description); break;
        case 'hover':        result = await session.hover(selector, description); break;
        case 'wait':         result = await session.waitFor(selector, timeout); break;
        case 'script':       result = await session.executeScript(script); break;
        case 'switch_tab':   result = await session.switchTab(Number(tabIndex)); break;
        case 'set_viewport': result = await session.setViewport(preset); break;
        case 'upload_file':  result = await session.uploadFile(selector, filePath); break;
        case 'save_cookies': result = await session.saveCookies(); break;
        case 'load_cookies': result = await session.loadCookies(); break;
        case 'undo':         result = await session.undo(); break;
        default:
          return res.status(400).json({ error: `Unknown action: ${action}` });
      }

      res.json(result);
    } catch (err) {
      logger.error(`[Autopilot] Action failed: ${err.message}`);
      res.status(500).json({ error: safeError(err) });
    }
  });

  // ── Observe Current State ──────────────────────────────────────────────
  router.post('/observe', async (req, res) => {
    try {
      const daUsername = req._daUsername || req.body?.daUsername;
      if (!daUsername) return res.status(400).json({ error: 'Missing daUsername' });

      const { getSession } = await getAutopilotModule();
      const session = getSession(daUsername);
      if (!session?.alive) {
        return res.status(404).json({ error: 'No active autopilot session' });
      }

      const obs = await session.observe();
      res.json(obs);
    } catch (err) {
      res.status(500).json({ error: safeError(err) });
    }
  });

  // ── Stop Autopilot ─────────────────────────────────────────────────────
  router.post('/stop', async (req, res) => {
    try {
      const daUsername = req._daUsername || req.body?.daUsername;
      if (!daUsername) return res.status(400).json({ error: 'Missing daUsername' });

      const { stopSession, autopilotEvents } = await getAutopilotModule();

      if (eventHandlers.has(daUsername)) {
        autopilotEvents.removeListener('autopilot', eventHandlers.get(daUsername));
        eventHandlers.delete(daUsername);
      }

      const result = await stopSession(daUsername);

      const clients = watchers.get(daUsername);
      if (clients) {
        const msg = JSON.stringify({ type: 'stopped', daUsername, reason: 'user' });
        for (const ws of clients) {
          try { ws.send(msg); ws.close(); } catch {}
        }
        watchers.delete(daUsername);
      }

      res.json(result);
    } catch (err) {
      res.status(500).json({ error: safeError(err) });
    }
  });

  // ── P1: Approve Pending Action ──────────────────────────────────────────
  router.post('/approve', async (req, res) => {
    try {
      const daUsername = req._daUsername || req.body?.daUsername;
      if (!daUsername) return res.status(400).json({ error: 'Missing daUsername' });

      const { getSession } = await getAutopilotModule();
      const session = getSession(daUsername);
      if (!session?.alive) return res.status(404).json({ error: 'No active session' });

      const pending = session.getPendingAction();
      if (!pending) return res.status(400).json({ error: 'No action pending approval' });

      session.resolveApproval(true, req.body?.editedAction || null);
      logger.info(`[Autopilot] Action approved by ${daUsername}: ${pending.action}`);
      res.json({ status: 'approved', action: pending.action });
    } catch (err) {
      res.status(500).json({ error: safeError(err) });
    }
  });

  // ── P1: Reject Pending Action ──────────────────────────────────────────
  router.post('/reject', async (req, res) => {
    try {
      const daUsername = req._daUsername || req.body?.daUsername;
      if (!daUsername) return res.status(400).json({ error: 'Missing daUsername' });

      const { getSession } = await getAutopilotModule();
      const session = getSession(daUsername);
      if (!session?.alive) return res.status(404).json({ error: 'No active session' });

      const pending = session.getPendingAction();
      if (!pending) return res.status(400).json({ error: 'No action pending' });

      session.resolveApproval(false);
      logger.info(`[Autopilot] Action rejected by ${daUsername}: ${pending.action}`);
      res.json({ status: 'rejected', action: pending.action });
    } catch (err) {
      res.status(500).json({ error: safeError(err) });
    }
  });

  // ── P1: Get Pending Action ─────────────────────────────────────────────
  router.get('/pending', async (req, res) => {
    try {
      const daUsername = req.query.user || req._daUsername;
      if (!daUsername) return res.status(400).json({ error: 'Missing user' });

      const { getSession } = await getAutopilotModule();
      const session = getSession(daUsername);
      if (!session?.alive) return res.status(404).json({ error: 'No active session' });

      const pending = session.getPendingAction();
      res.json({ paused: session._paused, pending: pending || null });
    } catch (err) {
      res.status(500).json({ error: safeError(err) });
    }
  });

  // ── P1: List Tabs ──────────────────────────────────────────────────────
  router.get('/tabs', async (req, res) => {
    try {
      const daUsername = req.query.user || req._daUsername;
      if (!daUsername) return res.status(400).json({ error: 'Missing user' });

      const { getSession } = await getAutopilotModule();
      const session = getSession(daUsername);
      if (!session?.alive) return res.status(404).json({ error: 'No active session' });

      res.json({ tabs: session.listTabs() });
    } catch (err) {
      res.status(500).json({ error: safeError(err) });
    }
  });

  // ── P3: Set Viewport ──────────────────────────────────────────────────
  router.post('/viewport', async (req, res) => {
    try {
      const daUsername = req._daUsername || req.body?.daUsername;
      const preset = req.body?.preset;
      if (!daUsername) return res.status(400).json({ error: 'Missing daUsername' });
      if (!preset) return res.status(400).json({ error: 'Missing preset' });

      const { getSession } = await getAutopilotModule();
      const session = getSession(daUsername);
      if (!session?.alive) return res.status(404).json({ error: 'No active session' });

      const result = await session.setViewport(preset);
      res.json(result);
    } catch (err) {
      res.status(500).json({ error: safeError(err) });
    }
  });

  // ── P2: List Downloads ─────────────────────────────────────────────────
  router.get('/downloads', async (req, res) => {
    try {
      const daUsername = req.query.user || req._daUsername;
      if (!daUsername) return res.status(400).json({ error: 'Missing user' });

      const { getSession } = await getAutopilotModule();
      const session = getSession(daUsername);
      if (!session?.alive) return res.status(404).json({ error: 'No active session' });

      res.json({ downloads: session.getDownloads() });
    } catch (err) {
      res.status(500).json({ error: safeError(err) });
    }
  });

  // ── P3: Network Capture Log ────────────────────────────────────────────
  router.get('/network', async (req, res) => {
    try {
      const daUsername = req.query.user || req._daUsername;
      if (!daUsername) return res.status(400).json({ error: 'Missing user' });

      const { getSession } = await getAutopilotModule();
      const session = getSession(daUsername);
      if (!session?.alive) return res.status(404).json({ error: 'No active session' });

      const urlFilter = req.query.urlFilter || undefined;
      const method = req.query.method || undefined;
      const limit = req.query.limit ? Number(req.query.limit) : undefined;

      res.json({ network: session.getNetworkLog({ urlFilter, method, limit }) });
    } catch (err) {
      res.status(500).json({ error: safeError(err) });
    }
  });

  // ── P2: Screenshot History Timeline ────────────────────────────────────
  router.get('/history', async (req, res) => {
    try {
      const daUsername = req.query.user || req._daUsername;
      if (!daUsername) return res.status(400).json({ error: 'Missing user' });

      const { getSession } = await getAutopilotModule();
      const session = getSession(daUsername);
      if (!session?.alive) return res.status(404).json({ error: 'No active session' });

      const step = req.query.step ? Number(req.query.step) : null;
      if (step !== null) {
        const buf = session.getScreenshotAtStep(step);
        if (!buf) return res.status(404).json({ error: `No screenshot for step ${step}` });
        res.set('Content-Type', 'image/jpeg');
        return res.send(buf);
      }

      res.json({ history: session.getScreenshotHistory() });
    } catch (err) {
      res.status(500).json({ error: safeError(err) });
    }
  });

  // ── H4: Undo ───────────────────────────────────────────────────────────
  router.post('/undo', async (req, res) => {
    try {
      const daUsername = req._daUsername || req.body?.daUsername;
      if (!daUsername) return res.status(400).json({ error: 'Missing daUsername' });

      const { getSession } = await getAutopilotModule();
      const session = getSession(daUsername);
      if (!session?.alive) return res.status(404).json({ error: 'No active session' });

      const result = await session.undo();
      res.json(result);
    } catch (err) {
      res.status(500).json({ error: safeError(err) });
    }
  });

  // ── H12: Annotations ──────────────────────────────────────────────────
  router.post('/annotate', async (req, res) => {
    try {
      const daUsername = req._daUsername || req.body?.daUsername;
      const { x, y, text, color } = req.body;
      if (!daUsername) return res.status(400).json({ error: 'Missing daUsername' });
      if (x === undefined || y === undefined) return res.status(400).json({ error: 'Missing x,y coordinates' });

      const { getSession } = await getAutopilotModule();
      const session = getSession(daUsername);
      if (!session?.alive) return res.status(404).json({ error: 'No active session' });

      const ann = session.addAnnotation(x, y, text || '', color || '#fbbf24');
      res.json(ann);
    } catch (err) {
      res.status(500).json({ error: safeError(err) });
    }
  });

  router.delete('/annotate', async (req, res) => {
    try {
      const daUsername = req.query.user || req._daUsername;
      const id = req.query.id ? Number(req.query.id) : null;
      if (!daUsername) return res.status(400).json({ error: 'Missing user' });

      const { getSession } = await getAutopilotModule();
      const session = getSession(daUsername);
      if (!session?.alive) return res.status(404).json({ error: 'No active session' });

      if (id) {
        res.json(session.removeAnnotation(id));
      } else {
        res.json(session.clearAnnotations());
      }
    } catch (err) {
      res.status(500).json({ error: safeError(err) });
    }
  });

  router.get('/annotations', async (req, res) => {
    try {
      const daUsername = req.query.user || req._daUsername;
      if (!daUsername) return res.status(400).json({ error: 'Missing user' });

      const { getSession } = await getAutopilotModule();
      const session = getSession(daUsername);
      if (!session?.alive) return res.status(404).json({ error: 'No active session' });

      res.json({ annotations: session.getAnnotations() });
    } catch (err) {
      res.status(500).json({ error: safeError(err) });
    }
  });

  // ── H13: Task Templates ───────────────────────────────────────────────
  router.post('/template/save', async (req, res) => {
    try {
      const daUsername = req._daUsername || req.body?.daUsername;
      const name = req.body?.name;
      if (!daUsername) return res.status(400).json({ error: 'Missing daUsername' });
      if (!name) return res.status(400).json({ error: 'Missing template name' });

      const { getSession } = await getAutopilotModule();
      const session = getSession(daUsername);
      if (!session?.alive) return res.status(404).json({ error: 'No active session' });

      const result = await session.saveTemplate(name);
      res.json(result);
    } catch (err) {
      res.status(500).json({ error: safeError(err) });
    }
  });

  router.get('/templates', async (req, res) => {
    try {
      const { listTemplates } = await getAutopilotModule();
      res.json({ templates: await listTemplates() });
    } catch (err) {
      res.status(500).json({ error: safeError(err) });
    }
  });

  router.get('/template/:name', async (req, res) => {
    try {
      const { getTemplate } = await getAutopilotModule();
      const tmpl = await getTemplate(req.params.name);
      if (!tmpl) return res.status(404).json({ error: 'Template not found' });
      res.json(tmpl);
    } catch (err) {
      res.status(500).json({ error: safeError(err) });
    }
  });

  router.delete('/template/:name', async (req, res) => {
    try {
      const { deleteTemplate } = await getAutopilotModule();
      const result = await deleteTemplate(req.params.name);
      res.json(result);
    } catch (err) {
      res.status(500).json({ error: safeError(err) });
    }
  });

  // ── H14: Batch Operations ─────────────────────────────────────────────
  router.post('/batch', async (req, res) => {
    try {
      const daUsername = req._daUsername || req.body?.daUsername;
      const tasks = req.body?.tasks;
      if (!daUsername) return res.status(400).json({ error: 'Missing daUsername' });
      if (!Array.isArray(tasks)) return res.status(400).json({ error: 'Missing tasks array' });

      const { getSession } = await getAutopilotModule();
      const session = getSession(daUsername);
      if (!session?.alive) return res.status(404).json({ error: 'No active session' });

      const result = session.setBatchQueue(tasks);
      res.json(result);
    } catch (err) {
      res.status(500).json({ error: safeError(err) });
    }
  });

  router.get('/batch/status', async (req, res) => {
    try {
      const daUsername = req.query.user || req._daUsername;
      if (!daUsername) return res.status(400).json({ error: 'Missing user' });

      const { getSession } = await getAutopilotModule();
      const session = getSession(daUsername);
      if (!session?.alive) return res.status(404).json({ error: 'No active session' });

      res.json(session.getBatchStatus());
    } catch (err) {
      res.status(500).json({ error: safeError(err) });
    }
  });

  router.post('/batch/next', async (req, res) => {
    try {
      const daUsername = req._daUsername || req.body?.daUsername;
      if (!daUsername) return res.status(400).json({ error: 'Missing daUsername' });

      const { getSession } = await getAutopilotModule();
      const session = getSession(daUsername);
      if (!session?.alive) return res.status(404).json({ error: 'No active session' });

      const next = session.nextBatchItem();
      if (!next) return res.json({ done: true, message: 'Batch queue empty or completed' });
      res.json({ next, remaining: session.getBatchStatus().remaining });
    } catch (err) {
      res.status(500).json({ error: safeError(err) });
    }
  });

  // ── H15: Scheduled Autopilot ──────────────────────────────────────────
  router.post('/schedule', async (req, res) => {
    try {
      const { saveSchedule } = await getAutopilotModule();
      const schedule = req.body;
      if (!schedule?.task) return res.status(400).json({ error: 'Missing task in schedule' });
      const result = await saveSchedule(schedule);
      res.json(result);
    } catch (err) {
      res.status(500).json({ error: safeError(err) });
    }
  });

  router.get('/schedules', async (req, res) => {
    try {
      const { listSchedules } = await getAutopilotModule();
      res.json({ schedules: await listSchedules() });
    } catch (err) {
      res.status(500).json({ error: safeError(err) });
    }
  });

  router.delete('/schedule/:id', async (req, res) => {
    try {
      const { deleteSchedule } = await getAutopilotModule();
      const result = await deleteSchedule(req.params.id);
      res.json(result);
    } catch (err) {
      res.status(500).json({ error: safeError(err) });
    }
  });

  // ── H17: Narration Queue ──────────────────────────────────────────────
  router.get('/narration', async (req, res) => {
    try {
      const daUsername = req.query.user || req._daUsername;
      if (!daUsername) return res.status(400).json({ error: 'Missing user' });

      const { getSession } = await getAutopilotModule();
      const session = getSession(daUsername);
      if (!session?.alive) return res.status(404).json({ error: 'No active session' });

      res.json({ narration: session.getNarration() });
    } catch (err) {
      res.status(500).json({ error: safeError(err) });
    }
  });

  // ── H6: Sentiment Status ──────────────────────────────────────────────
  router.get('/sentiment', async (req, res) => {
    try {
      const daUsername = req.query.user || req._daUsername;
      if (!daUsername) return res.status(400).json({ error: 'Missing user' });

      const { getSession } = await getAutopilotModule();
      const session = getSession(daUsername);
      if (!session?.alive) return res.status(404).json({ error: 'No active session' });

      res.json({
        sentiment: session._sentiment,
        confidence: session._confidenceScore,
        frustrationLevel: session._frustrationLevel,
        stuckCounter: session._stuckCounter,
        sentimentHistory: session._sentimentHistory,
      });
    } catch (err) {
      res.status(500).json({ error: safeError(err) });
    }
  });

  // ── Status ─────────────────────────────────────────────────────────────
  router.get('/status', async (req, res) => {
    try {
      const { getActiveSessions } = await getAutopilotModule();
      res.json({ sessions: getActiveSessions() });
    } catch (err) {
      res.status(500).json({ error: safeError(err) });
    }
  });
}

/**
 * Handle WebSocket upgrade for autopilot live stream.
 */
function handleAutopilotWebSocket(ws, req) {
  const url = new URL(req.url, 'http://localhost');

  // SECURITY (VULN-R2-01): Require JWT authentication for WebSocket
  const wsToken = url.searchParams.get('token');
  if (!wsToken) {
    ws.close(4001, 'Authentication required — provide ?token=<jwt>');
    return;
  }
  let decoded;
  try {
    const jwt = require('jsonwebtoken');
    const config = require('../config');
const safeError = require('../utils/safeError');
    decoded = jwt.verify(wsToken, config.jwt.secret);
  } catch (err) {
    ws.close(4001, 'Invalid or expired token');
    return;
  }
  const daUsername = decoded.daUsername;
  if (!daUsername) {
    ws.close(4001, 'Token missing daUsername');
    return;
  }

  // Verify the user param matches the JWT (prevent cross-tenant spectating)
  const requestedUser = url.searchParams.get('user');
  if (requestedUser && requestedUser !== daUsername) {
    ws.close(4003, 'Not authorized to watch this session');
    return;
  }

  if (!watchers.has(daUsername)) {
    watchers.set(daUsername, new Set());
  }
  watchers.get(daUsername).add(ws);

  // H11: Update spectator count
  (async () => {
    try {
      const { getSession } = await getAutopilotModule();
      const session = getSession(daUsername);
      if (session) {
        session._spectatorCount = watchers.get(daUsername)?.size || 0;
      }
    } catch {}
  })();

  logger.info(`[Autopilot] Stream watcher connected for ${daUsername} (${watchers.get(daUsername).size} spectators)`);

  // Send initial state
  (async () => {
    try {
      const { getSession } = await getAutopilotModule();
      const session = getSession(daUsername);
      if (session?.alive) {
        ws.send(JSON.stringify({
          type: 'session_info',
          daUsername,
          viewport: session._currentViewport,
          step: session.stepCount,
          paused: session._paused,
          pendingAction: session._pendingAction,
          tabs: session.listTabs(),
          sentiment: session._sentiment,
          confidence: session._confidenceScore,
          frustrationLevel: session._frustrationLevel,
          spectators: session._spectatorCount,
          annotations: session._annotations,
          undoAvailable: session._undoStack.length > 0,
          batchStatus: session._batchActive ? session.getBatchStatus() : null,
          guardrails: {
            maxSteps: session.opts.maxSteps,
            stepsRemaining: session.opts.maxSteps - session.stepCount,
          },
          features: {
            sensitiveFieldMasking: session.opts.sensitiveFieldMasking,
            geoFence: session.opts.allowedDomains.length > 0,
            smartWait: session.opts.smartWait,
            retentionPolicy: session.opts.retentionPolicy,
          },
          timestamp: Date.now(),
        }));
        if (session.lastScreenshot) {
          const header = Buffer.alloc(9);
          header.writeUInt8(0x01, 0);
          if (session._lastClickCoords) {
            header.writeUInt16BE(session._lastClickCoords.x || 0, 1);
            header.writeUInt16BE(session._lastClickCoords.y || 0, 3);
          }
          header.writeUInt32BE(session.stepCount || 0, 5);
          ws.send(Buffer.concat([header, session.lastScreenshot]), { binary: true });
        }
      } else {
        ws.send(JSON.stringify({
          type: 'waiting',
          message: 'No active autopilot session. Ask Alfred to start one.',
          daUsername,
          timestamp: Date.now(),
        }));
      }
    } catch {}
  })();

  // Handle client messages (approve/reject/annotate from IDE panel)
  ws.on('message', async (data) => {
    try {
      const msg = JSON.parse(data.toString());
      const { getSession } = await getAutopilotModule();
      const session = getSession(daUsername);
      if (!session?.alive) return;

      if (msg.type === 'approve') {
        session.resolveApproval(true, msg.editedAction || null);
        logger.info(`[Autopilot] WS approve from ${daUsername}`);
      } else if (msg.type === 'reject') {
        session.resolveApproval(false);
        logger.info(`[Autopilot] WS reject from ${daUsername}`);
      } else if (msg.type === 'viewport') {
        if (msg.preset) await session.setViewport(msg.preset);
      } else if (msg.type === 'undo') {
        await session.undo();
      } else if (msg.type === 'annotate') {
        session.addAnnotation(msg.x, msg.y, msg.text || '', msg.color || '#fbbf24');
      } else if (msg.type === 'clear_annotations') {
        session.clearAnnotations();
      } else if (msg.type === 'switch_tab') {
        if (msg.tabIndex !== undefined) await session.switchTab(msg.tabIndex);
      }
    } catch {}
  });

  ws.on('close', () => {
    const clients = watchers.get(daUsername);
    if (clients) {
      clients.delete(ws);
      if (clients.size === 0) watchers.delete(daUsername);
    }
    // H11: Update spectator count
    (async () => {
      try {
        const { getSession } = await getAutopilotModule();
        const session = getSession(daUsername);
        if (session) session._spectatorCount = watchers.get(daUsername)?.size || 0;
      } catch {}
    })();
  });

  ws.on('error', () => {
    const clients = watchers.get(daUsername);
    if (clients) {
      clients.delete(ws);
      if (clients.size === 0) watchers.delete(daUsername);
    }
  });
}

module.exports = { registerAutopilotRoutes, handleAutopilotWebSocket };
