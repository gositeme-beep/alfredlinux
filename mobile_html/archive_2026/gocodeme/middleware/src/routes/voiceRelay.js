'use strict';

/**
 * Voice Relay — REST-to-WebSocket bridge for voice.php
 *
 * Problem: Apache's [P] proxy flag strips hop-by-hop headers (Upgrade,
 *          Connection) so direct browser WebSocket to the voice server
 *          (port 3006) through Apache is impossible.
 *
 * Solution: This module maintains server-side WebSocket connections to
 *           the voice server and exposes REST endpoints that voice.php
 *           uses via HTTP long-polling.
 *
 * Flow:  Browser ──HTTP──> Apache ──proxy.php──> Express:3001 ──WS──> Voice:3006
 *
 * Endpoints:
 *   POST /api/voice-relay/connect    — Open session, returns { session_id }
 *   POST /api/voice-relay/send       — Send JSON message through session's WS
 *   GET  /api/voice-relay/poll       — Long-poll for queued responses (30s timeout)
 *   POST /api/voice-relay/disconnect — Close session & WS connection
 */

const express = require('express');
const WebSocket = require('ws');
const crypto = require('crypto');
const router = express.Router();
const logger = require('../logger');
const safeError = require('../utils/safeError');
const alfredMemory = require('../alfredMemory');
const { requireSession: requireGoCodeMeSession } = require('../auth/middleware');
const config = require('../config');

// ── Server-side TTS via OpenAI Speech API ──
async function generateTTS(text, voice) {
  // Try config first, then resolve directly with explicit .env path (PM2 cluster CWD may differ)
  let apiKey = config.apiKeys && config.apiKeys.openai;
  if (!apiKey) {
    try {
      const envPath = require('path').join(__dirname, '../../.env');
      require('dotenv').config({ path: envPath, override: true });
      const vault = require('../utils/vault');
      apiKey = vault.resolve(process.env.OPENAI_API_KEY || '');
    } catch (_) {}
  }
  if (!apiKey) throw new Error('No OpenAI key configured');
  const ttsText = text.replace(/\*\*/g, '').replace(/[#_`]/g, '').replace(/\[.*?\]/g, '').substring(0, 4000).trim();
  if (!ttsText) throw new Error('Empty TTS text');
  const https = require('https');
  const payload = JSON.stringify({
    model: 'tts-1',
    input: ttsText,
    voice: 'onyx',  // LOCKED: Always use Alfred's canonical onyx voice (matches toll-free)
    response_format: 'mp3',
    speed: 1.0,
  });
  return new Promise((resolve, reject) => {
    const req = https.request({
      hostname: 'api.openai.com',
      port: 443,
      path: '/v1/audio/speech',
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Content-Length': Buffer.byteLength(payload),
        'Authorization': 'Bearer ' + apiKey,
      },
      timeout: 15000,
    }, (res) => {
      const chunks = [];
      res.on('data', c => chunks.push(c));
      res.on('end', () => {
        if (res.statusCode === 200) resolve(Buffer.concat(chunks));
        else reject(new Error('TTS API HTTP ' + res.statusCode));
      });
    });
    req.on('error', reject);
    req.on('timeout', () => { req.destroy(); reject(new Error('TTS timeout')); });
    req.write(payload);
    req.end();
  });
}


// Optional auth: GoCodeMe JWT, or Alfred IDE hex session token (not a JWT — do not run JWT middleware)
function optionalSession(req, res, next) {
  const authHeader = (req.headers.authorization || '').trim();
  const xIde = String(req.headers['x-alfred-ide-token'] || '').trim();
  const rawBearer = authHeader.startsWith('Bearer ') ? authHeader.slice(7).trim() : '';
  const raw = rawBearer || xIde;
  // IDE tokens from alfred-ide-auth are long hex strings; JWT always contains '.'
  if (raw && !raw.includes('.') && /^[a-f0-9]{32,256}$/i.test(raw)) {
    req.alfredIdeRawToken = raw;
    req.user = null;
    return next();
  }
  if (authHeader.startsWith('Bearer ') && authHeader.length > 10) {
    return requireGoCodeMeSession(req, res, next);
  }
  req.user = null;
  next();
}

// ── Session store ──────────────────────────────────────────────────────────
const sessions = new Map();
const VOICE_PORT = process.env.VOICE_PORT || 3006;
const SESSION_TTL = 30 * 60 * 1000;   // 30 min idle timeout
const POLL_TIMEOUT = 25000;           // 25s long-poll (under typical 30s proxy timeout)
const MAX_QUEUE = 200;                // max queued messages per session
const MAX_SESSIONS = 100;             // server-wide cap

// Cleanup interval — reap stale sessions every 2 min
setInterval(() => {
  const now = Date.now();
  for (const [id, sess] of sessions) {
    if (now - sess.lastActivity > SESSION_TTL) {
      logger.info(`voice-relay: reaping idle session ${id}`);
      destroySession(id);
    }
  }
}, 2 * 60 * 1000);

function destroySession(id) {
  const sess = sessions.get(id);
  if (!sess) return;
  if (sess.pollRes) {
    try { sess.pollRes.json({ ok: true, messages: [] }); } catch (_) {}
    sess.pollRes = null;
  }
  if (sess.pollTimer) clearTimeout(sess.pollTimer);
  if (sess.ws && sess.ws.readyState <= WebSocket.OPEN) {
    try { sess.ws.close(); } catch (_) {}
  }
  sess.chatHistory = [];
  sessions.delete(id);
}

// ── POST /connect — create session + open WS to voice server ───────────────
// SECURITY (R3 M-06): Require authenticated session and bind to user
router.post('/connect', optionalSession, (req, res) => {
  if (sessions.size >= MAX_SESSIONS) {
    return res.status(503).json({ ok: false, error: 'Server busy — too many voice sessions' });
  }

  const sessionId = crypto.randomBytes(16).toString('hex');
  const queue = [];

  // Alfred IDE widget: always text-only → alfred-chat.php (voice WS path drops / misroutes typed chat)
  const forceTextOnly = !!(req.body && (req.body.text_only === true || req.body.ide_chat === true));

  // Try WebSocket to voice server; fall back to text-only mode
  // Voice server uses WSS (self-signed cert) on VOICE_PORT
  const wsUrl = `wss://127.0.0.1:${VOICE_PORT}/`;
  let ws = null;
  let textOnly = false;

  if (forceTextOnly) {
    textOnly = true;
    logger.info(`voice-relay: session ${sessionId} — forced text-only (IDE chat)`);
  } else {
    try {
      ws = new WebSocket(wsUrl, { rejectUnauthorized: false });
    } catch (err) {
      logger.info(`voice-relay: WS unavailable, using text-only mode: ${err.message}`);
      ws = null;
      textOnly = true;
    }
  }

  const sess = {
    id: sessionId,
    owner: req.user?.daUsername || null,  // SECURITY (R3 M-06): bind session to authenticated user (null for guests)
    ws,
    textOnly,
    queue,
    pollRes: null,
    pollTimer: null,
    lastActivity: Date.now(),
    connected: false,
    chatHistory: [],  // for text-only AI chat context
  };
  sessions.set(sessionId, sess);

  // ── Text-only mode (no voice server) ─────────────────────────────────
  if (textOnly || !ws) {
    sess.connected = true;
    sess.textOnly = true;
    logger.info(`voice-relay: session ${sessionId} — text-only mode (no voice server)`);
    // Queue a ready message so the client knows we're connected
    sess.queue.push({ type: 'status', message: 'Alfred Coder is ready (text mode)' });
    return res.json({ ok: true, session_id: sessionId, mode: 'text' });
  }

  // ── WebSocket mode (voice server available) ──────────────────────────
  ws.on('open', () => {
    sess.connected = true;
    logger.info(`voice-relay: session ${sessionId} — WS connected to voice server`);
  });

  ws.on('message', (data) => {
    sess.lastActivity = Date.now();
    let msg;
    if (Buffer.isBuffer(data) || data instanceof ArrayBuffer) {
      const buf = Buffer.isBuffer(data) ? data : Buffer.from(data);
      msg = { type: 'audio_binary', data: buf.toString('base64') };
    } else {
      try {
        msg = JSON.parse(data.toString());
      } catch (_) {
        msg = { type: 'raw', data: data.toString() };
      }
    }

    if (sess.pollRes) {
      try {
        if (sess.pollTimer) clearTimeout(sess.pollTimer);
        sess.pollRes.json({ ok: true, messages: [msg] });
      } catch (_) {}
      sess.pollRes = null;
      sess.pollTimer = null;
    } else {
      if (sess.queue.length < MAX_QUEUE) sess.queue.push(msg);
    }
  });

  ws.on('close', () => {
    logger.info(`voice-relay: session ${sessionId} — WS closed`);
    // If already in text-only mode, ignore the WS close
    if (sess.textOnly) return;
    sess.connected = false;
    const closeMsg = { type: 'ws_closed' };
    if (sess.pollRes) {
      try {
        if (sess.pollTimer) clearTimeout(sess.pollTimer);
        sess.pollRes.json({ ok: true, messages: [closeMsg] });
      } catch (_) {}
      sess.pollRes = null;
      sess.pollTimer = null;
    } else {
      if (sess.queue.length < MAX_QUEUE) sess.queue.push(closeMsg);
    }
  });

  ws.on('error', (err) => {
    logger.error(`voice-relay: session ${sessionId} WS error: ${err.message}`);
    // Fall back to text-only mode instead of failing
    sess.textOnly = true;
    sess.connected = true;
    logger.info(`voice-relay: session ${sessionId} — falling back to text-only mode`);
    const fallbackMsg = { type: 'status', message: 'Voice unavailable — switched to text mode' };
    if (sess.pollRes) {
      try {
        if (sess.pollTimer) clearTimeout(sess.pollTimer);
        sess.pollRes.json({ ok: true, messages: [fallbackMsg] });
      } catch (_) {}
      sess.pollRes = null;
      sess.pollTimer = null;
    } else {
      if (sess.queue.length < MAX_QUEUE) sess.queue.push(fallbackMsg);
    }
  });

  // Wait briefly for connection before responding
  const connectTimeout = setTimeout(() => {
    if (sess.connected) {
      res.json({ ok: true, session_id: sessionId });
    } else {
      // WS failed: fall back to text-only instead of dying
      sess.textOnly = true;
      sess.connected = true;
      logger.info(`voice-relay: session ${sessionId} — WS timeout, falling back to text-only`);
      sess.queue.push({ type: 'status', message: 'Alfred Coder is ready (text mode)' });
      if (sess.ws) { try { sess.ws.close(); } catch(_){} sess.ws = null; }
      res.json({ ok: true, session_id: sessionId, mode: 'text' });
    }
  }, 3000);

  ws.once('open', () => {
    clearTimeout(connectTimeout);
    if (!res.headersSent) res.json({ ok: true, session_id: sessionId, mode: 'voice' });
  });

  ws.once('error', () => {
    clearTimeout(connectTimeout);
    if (!res.headersSent) {
      // Fall back to text-only instead of returning error
      sess.textOnly = true;
      sess.connected = true;
      if (sess.ws) { try { sess.ws.close(); } catch(_){} sess.ws = null; }
      sess.queue.push({ type: 'status', message: 'Alfred Coder is ready (text mode)' });
      res.json({ ok: true, session_id: sessionId, mode: 'text' });
    }
  });
});

// ── Middleware: extract session ─────────────────────────────────────────────
function requireVoiceSession(req, res, next) {
  const sid = req.body?.session_id || req.query?.session_id || req.headers['x-voice-session'];
  if (!sid) return res.status(400).json({ ok: false, error: 'session_id required' });
  const sess = sessions.get(sid);
  if (!sess) return res.status(404).json({ ok: false, error: 'Session not found or expired' });
  // SECURITY (R3 M-06): Verify session ownership
  if (sess.owner && req.user && sess.owner !== req.user.daUsername) {
    return res.status(403).json({ ok: false, error: 'Not your voice session' });
  }
  sess.lastActivity = Date.now();
  req.voiceSession = sess;
  next();
}

// ── POST /send — send a message through the session's WS or text-only API ──
router.post('/send', optionalSession, requireVoiceSession, async (req, res) => {
  const sess = req.voiceSession;
  const { message } = req.body;

  if (!message) return res.status(400).json({ ok: false, error: 'message required' });

  let msgObj;
  try {
    msgObj = typeof message === 'string' ? JSON.parse(message) : message;
  } catch (_) {
    return res.status(400).json({ ok: false, error: 'Invalid message JSON' });
  }

  const isTypedChat =
    (msgObj.type === 'text' || msgObj.type === 'chat') && typeof msgObj.text === 'string' && msgObj.text.length > 0;

  // WS can drop after /connect (voice server restart, TLS glitch). 410 left users stuck on "thinking".
  if (!sess.textOnly && isTypedChat && (!sess.ws || sess.ws.readyState !== WebSocket.OPEN)) {
    logger.warn(
      `voice-relay: session ${sess.id} WS not open (readyState=${sess.ws ? sess.ws.readyState : 'null'}) — alfred-chat fallback`,
    );
    sess.textOnly = true;
  }

  // ── Text-only mode: route through alfred-chat API ──
  if (sess.textOnly) {

    // For non-text messages (audio, auth, etc.) in text-only mode
    if (msgObj.type === 'auth') {
      sess.authToken = msgObj.token;
      sess.authUsername = msgObj.username;
      const readyMsg = { type: 'status', message: 'Authenticated as ' + msgObj.username };
      if (sess.pollRes) {
        try { if (sess.pollTimer) clearTimeout(sess.pollTimer); sess.pollRes.json({ ok: true, messages: [readyMsg] }); } catch(_){}
        sess.pollRes = null; sess.pollTimer = null;
      } else { if (sess.queue.length < MAX_QUEUE) sess.queue.push(readyMsg); }
      return res.json({ ok: true });
    }

    if ((msgObj.type === 'text' || msgObj.type === 'chat') && msgObj.text) {
      sess.chatHistory.push({ role: 'user', content: msgObj.text });
      if (sess.chatHistory.length > 20) sess.chatHistory = sess.chatHistory.slice(-20);

      const voiceUserId = sess.owner || sess.authUsername || null;

      // Resolve whmcsClientId so alfred-chat.php can identify the user
      let resolvedClientId = null;
      if (voiceUserId) {
        try {
          const { getRedis } = require('../redis');
          const redis = getRedis();
          resolvedClientId = await redis.get(`client_id_by_da:${voiceUserId}`);
        } catch (_) {}
      }
      // Also use whmcsClientId from JWT if available
      if (!resolvedClientId && req.user && req.user.whmcsClientId) {
        resolvedClientId = String(req.user.whmcsClientId);
      }

      try {
        const https = require('https');
        const INTERNAL_SECRET = process.env.INTERNAL_SECRET || process.env.INTERNAL_RELAY_SECRET || '';

        const chatBody = {
          action: 'chat',
          message: msgObj.text,
          channel: 'ide-chat',
          context: 'GoCodeMe IDE — Alfred AI chat widget inside the coding IDE.',
          system_note: 'GoCodeMe/Alfred IDE: logged-in developer. Use tools; write/run code when asked. Short replies like "try again" mean retry the last dev task — never claim the user message was truncated or ask about email/SMS apps.',
          external_user_id: voiceUserId || undefined,
          agent: msgObj.agent || undefined,
        };
        if (resolvedClientId) {
          chatBody.client_id = parseInt(resolvedClientId, 10);
          chatBody.relay_username = voiceUserId;
        }
        const chatPayload = JSON.stringify(chatBody);

        const queueError = (text) => {
          const errMsg = { type: 'text_response', text, agent: 'alfred' };
          if (sess.pollRes) {
            try { if (sess.pollTimer) clearTimeout(sess.pollTimer); sess.pollRes.json({ ok: true, messages: [errMsg] }); } catch (_) {}
            sess.pollRes = null;
            sess.pollTimer = null;
          } else if (sess.queue.length < MAX_QUEUE) {
            sess.queue.push(errMsg);
          }
        };

        const requestAlfredChat = ({ csrfToken = '', cookie = '', attempt = 1 } = {}) => new Promise((resolve, reject) => {
          const headers = {
            'Content-Type': 'application/json',
            'Content-Length': Buffer.byteLength(chatPayload),
          };
          if (INTERNAL_SECRET) headers['X-Internal-Secret'] = INTERNAL_SECRET;
          if (csrfToken) headers['X-CSRF-Token'] = csrfToken;
          if (cookie) headers['Cookie'] = cookie;
          if (req.alfredIdeRawToken) {
            headers.Authorization = `Bearer ${req.alfredIdeRawToken}`;
            headers['X-Alfred-IDE-Token'] = req.alfredIdeRawToken;
          }
          const chatReq = https.request({
            hostname: 'gositeme.com',
            port: 443,
            path: '/api/alfred-chat.php',
            method: 'POST',
            headers,
            timeout: 180000,
          }, (chatRes) => {
            let body = '';
            chatRes.on('data', chunk => body += chunk);
            chatRes.on('end', () => {
              let parsed = null;
              try { parsed = JSON.parse(body); } catch (_) {}
              const setCookie = chatRes.headers['set-cookie'];
              const sessionCookie = Array.isArray(setCookie)
                ? setCookie.map(v => String(v).split(';')[0]).join('; ')
                : '';
              const nextCookie = sessionCookie || cookie;
              if (
                parsed &&
                parsed.csrf_refresh === true &&
                parsed.csrf_token &&
                attempt < 2
              ) {
                return resolve(requestAlfredChat({
                  csrfToken: String(parsed.csrf_token),
                  cookie: nextCookie,
                  attempt: attempt + 1,
                }));
              }
              resolve({ parsed, rawBody: body });
            });
          });
          chatReq.on('error', (e) => reject(e));
          chatReq.on('timeout', () => {
            try { chatReq.destroy(); } catch (_) {}
            reject(new Error('alfred-chat timeout'));
          });
          chatReq.write(chatPayload);
          chatReq.end();
        });

        requestAlfredChat()
          .then(({ parsed, rawBody }) => {
            if (!parsed) {
              logger.error(`voice-relay: alfred-chat response parse error body=${String(rawBody).slice(0,200)}`);
              queueError('Sorry, I encountered an error processing your request.');
              return;
            }
            const replyText = parsed.message || parsed.response || parsed.error || 'No response';
            sess.chatHistory.push({ role: 'assistant', content: replyText });

            if (voiceUserId) {
              alfredMemory.recordInteraction(voiceUserId, {
                source: 'voice',
                userMessage: msgObj.text,
                alfredResponse: replyText,
                model: 'alfred-chat',
                agent: 'alfred',
              }).catch(() => {});
            }

            const responseMsg = { type: 'text_response', text: replyText, agent: 'alfred' };
            const ttsVoice = sess.ttsVoice || 'onyx';
            generateTTS(replyText, ttsVoice).then(audioBuf => {
              const audioMsg = { type: 'audio_binary', data: audioBuf.toString('base64'), format: 'mp3' };
              if (sess.pollRes) {
                try { if (sess.pollTimer) clearTimeout(sess.pollTimer); sess.pollRes.json({ ok: true, messages: [responseMsg, audioMsg] }); } catch(_){}
                sess.pollRes = null; sess.pollTimer = null;
              } else {
                if (sess.queue.length < MAX_QUEUE) sess.queue.push(responseMsg);
                if (sess.queue.length < MAX_QUEUE) sess.queue.push(audioMsg);
              }
            }).catch(ttsErr => {
              logger.info('voice-relay: TTS unavailable, text-only fallback: ' + ttsErr.message);
              if (sess.pollRes) {
                try { if (sess.pollTimer) clearTimeout(sess.pollTimer); sess.pollRes.json({ ok: true, messages: [responseMsg] }); } catch(_){}
                sess.pollRes = null; sess.pollTimer = null;
              } else if (sess.queue.length < MAX_QUEUE) {
                sess.queue.push(responseMsg);
              }
            });
          })
          .catch((e) => {
            if ((e && e.message) === 'alfred-chat timeout') {
              logger.error('voice-relay: alfred-chat HTTPS request timeout');
              queueError('Alfred backend timed out before finishing (long model or tool run). Narrow the question or say try again to retry.');
            } else {
              logger.error(`voice-relay: alfred-chat error: ${e.message}`);
              queueError('Connection to Alfred failed. Please try again.');
            }
          });

        return res.json({ ok: true });
      } catch (err) {
        logger.error(`voice-relay: text-only send error: ${err.message}`);
        return res.status(500).json({ ok: false, error: 'AI chat request failed' });
      }
    }

    // For set_voice messages in text-only mode — just acknowledge
    if (msgObj.type === 'set_voice') {
      // Map kokoro/agent voices to OpenAI TTS voices
      const voiceMap = { onyx:'onyx', nova:'nova', shimmer:'shimmer', echo:'echo', fable:'fable', alloy:'alloy',
        am_eric:'echo', af_bella:'nova', bm_george:'onyx', bf_emma:'shimmer' };
      sess.ttsVoice = voiceMap[msgObj.voice] || 'onyx';
      return res.json({ ok: true });
    }
    // For audio messages in text-only mode — not supported
    if (msgObj.type === 'audio') {
      const errMsg = { type: 'error', message: 'Voice is unavailable in text-only mode. Please type your message.' };
      if (sess.queue.length < MAX_QUEUE) sess.queue.push(errMsg);
      return res.json({ ok: true });
    }

    return res.json({ ok: true });
  }

  // ── WebSocket mode: forward through voice server ──
  if (!sess.ws || sess.ws.readyState !== WebSocket.OPEN) {
    return res.status(410).json({ ok: false, error: 'Voice connection closed' });
  }

  try {
    const payload = typeof message === 'string' ? message : JSON.stringify(message);
    sess.ws.send(payload);
    res.json({ ok: true });
  } catch (err) {
    res.status(500).json({ ok: false, error: 'Send failed' });
  }
});

// ── GET /poll — long-poll for queued messages ──────────────────────────────
// SECURITY (R3 M-06): Ownership enforced per-session; guests can only poll their own sessions
router.get('/poll', optionalSession, (req, res) => {
  const sid = req.query?.session_id || req.headers['x-voice-session'];
  if (!sid) return res.status(400).json({ ok: false, error: 'session_id required' });
  const sess = sessions.get(sid);
  if (!sess) return res.status(404).json({ ok: false, error: 'Session not found or expired' });
  // Ownership check
  if (sess.owner && req.user && sess.owner !== req.user.daUsername) {
    return res.status(403).json({ ok: false, error: 'Not your voice session' });
  }
  sess.lastActivity = Date.now();

  // If messages are already queued, return immediately
  if (sess.queue.length > 0) {
    const messages = sess.queue.splice(0);
    return res.json({ ok: true, messages });
  }

  // No messages yet — hold the connection (long-poll)
  // If there's already a pending poll, cancel it
  if (sess.pollRes) {
    try { sess.pollRes.json({ ok: true, messages: [] }); } catch (_) {}
    if (sess.pollTimer) clearTimeout(sess.pollTimer);
  }

  sess.pollRes = res;
  sess.pollTimer = setTimeout(() => {
    sess.pollRes = null;
    sess.pollTimer = null;
    if (!res.headersSent) {
      res.json({ ok: true, messages: [] });
    }
  }, POLL_TIMEOUT);

  // If client disconnects, clean up
  req.on('close', () => {
    if (sess.pollRes === res) {
      sess.pollRes = null;
      if (sess.pollTimer) clearTimeout(sess.pollTimer);
      sess.pollTimer = null;
    }
  });
});

// ── POST /disconnect — close session ───────────────────────────────────────
router.post('/disconnect', optionalSession, requireVoiceSession, (req, res) => {
  const sess = req.voiceSession;
  logger.info(`voice-relay: client disconnecting session ${sess.id}`);
  destroySession(sess.id);
  res.json({ ok: true });
});

// ── GET /status — check session health ─────────────────────────────────────
router.get('/status', (req, res) => {
  const sid = req.query?.session_id || req.headers['x-voice-session'];
  if (!sid) return res.json({ ok: true, active_sessions: sessions.size });
  const sess = sessions.get(sid);
  if (!sess) return res.status(404).json({ ok: false, error: 'Session not found' });
  res.json({
    ok: true,
    connected: sess.connected,
    queue_length: sess.queue.length,
    last_activity: new Date(sess.lastActivity).toISOString(),
  });
});


// ── POST /simli-token — Get a Simli session token for lip-sync avatar ──
router.post('/simli-token', optionalSession, async (req, res) => {
  if (!config.simli || !config.simli.apiKey || !config.simli.faceId) {
    return res.json({ ok: false, error: 'Simli not configured', available: false });
  }

  try {
    const https = require('https');
    const payload = JSON.stringify({
      faceId: config.simli.faceId,
      apiVersion: 'v2',
      handleSilence: true,
      maxSessionLength: 120,
      maxIdleTime: 30,
      audioInputFormat: 'pcm16',
    });

    const tokenData = await new Promise((resolve, reject) => {
      const req = https.request({
        hostname: 'api.simli.ai',
        port: 443,
        path: '/compose/token',
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Content-Length': Buffer.byteLength(payload),
          'x-simli-api-key': config.simli.apiKey,
        },
        timeout: 10000,
      }, (res) => {
        let body = '';
        res.on('data', c => body += c);
        res.on('end', () => {
          try { resolve(JSON.parse(body)); } catch(e) { reject(new Error('Invalid Simli response: ' + body.slice(0, 200))); }
        });
      });
      req.on('error', reject);
      req.write(payload);
      req.end();
    });

    if (!tokenData.session_token) {
      logger.info('voice-relay: Simli token error: ' + JSON.stringify(tokenData));
      return res.json({ ok: false, error: tokenData.detail || 'Failed to get Simli token', available: false });
    }

    res.json({
      ok: true,
      available: true,
      session_token: tokenData.session_token,
      faceId: config.simli.faceId,
    });
  } catch (e) {
    logger.error('voice-relay: Simli token error: ' + e.message);
    res.json({ ok: false, error: 'Simli service unavailable', available: false });
  }
});

// ── POST /tts — Text-to-speech endpoint (returns MP3 audio) ──
router.post('/tts', optionalSession, async (req, res) => {
  const { text, voice } = req.body;
  if (!text) return res.status(400).json({ ok: false, error: 'text required' });
  try {
    const audioBuf = await generateTTS(text, voice || 'onyx');
    res.set('Content-Type', 'audio/mpeg');
    res.set('Content-Length', audioBuf.length);
    res.send(audioBuf);
  } catch (e) {
    logger.info('voice-relay: TTS endpoint error: ' + e.message);
    res.status(500).json({ ok: false, error: 'TTS generation failed' });
  }
});

module.exports = router;
