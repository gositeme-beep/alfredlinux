'use strict';

/**
 * alfredChat.js — Full Alfred chat proxy for IDE/Dashboard
 *
 * POST /api/alfred-chat/send
 *   Body: { message, context? }
 *   Auth: requireSession (GoCodeMe JWT)
 *
 * Proxies to gositeme.com/api/alfred-chat.php with INTERNAL_SECRET,
 * giving IDE and Dashboard users access to the full Alfred with all tools
 * (sovereignty, MCP, ops directives, agent tasking, etc.)
 */

const express = require('express');
const router = express.Router();
const https = require('https');
const { requireSession } = require('../auth/middleware');
const alfredMemory = require('../alfredMemory');
const logger = require('../logger');

// Must match PHP alfred-chat.php getenv('INTERNAL_SECRET'). Prefer INTERNAL_SECRET, then legacy name.
const INTERNAL_SECRET = process.env.INTERNAL_SECRET || process.env.INTERNAL_RELAY_SECRET || '';

router.post('/send', requireSession, async (req, res) => {
  const { whmcsClientId, daUsername } = req.user;
  const { message, context } = req.body;

  if (!message || typeof message !== 'string' || message.trim().length === 0) {
    return res.status(400).json({ ok: false, error: 'message is required' });
  }

  try {
    const payload = JSON.stringify({
      action: 'chat',
      message: message.trim(),
      channel: 'ide-dashboard',
      context: context || `IDE/Dashboard user: ${daUsername}`,
      system_note: 'This user is chatting from the GoCodeMe IDE/Dashboard. They may ask about server management, code deployment, agent tasks, system health, or any Alfred capability.',
      client_id: whmcsClientId,
    });

    const chatReq = https.request({
      hostname: 'gositeme.com',
      port: 443,
      path: '/api/alfred-chat.php',
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Content-Length': Buffer.byteLength(payload),
        'X-Internal-Secret': INTERNAL_SECRET,
      },
      // Match voiceRelay (180s) — long tool chains in alfred-chat.php need headroom
      timeout: 180000,
    }, (chatRes) => {
      let body = '';
      chatRes.on('data', chunk => body += chunk);
      chatRes.on('end', () => {
        try {
          const data = JSON.parse(body);
          const replyText = data.message || data.response || data.error || 'No response from Alfred.';

          alfredMemory.recordInteraction(whmcsClientId, {
            source: 'ide',
            userMessage: message.trim(),
            alfredResponse: replyText,
            model: 'alfred-chat',
            agent: 'alfred',
          }).catch(() => {});

          return res.json({
            ok: true,
            content: replyText,
            model: data.model || 'alfred',
            tools_used: data.tools_used || [],
          });
        } catch (e) {
          logger.error(`alfredChat: parse error: ${e.message}`);
          return res.status(502).json({ ok: false, error: 'Failed to parse Alfred response' });
        }
      });
    });

    chatReq.on('error', (e) => {
      logger.error(`alfredChat: request error: ${e.message}`);
      return res.status(502).json({ ok: false, error: 'Failed to reach Alfred' });
    });

    chatReq.write(payload);
    chatReq.end();
  } catch (err) {
    logger.error(`alfredChat: ${err.message}`);
    return res.status(500).json({ ok: false, error: 'Internal error' });
  }
});

module.exports = router;
