/**
 * voiceServer.js — Live Two-Way Voice for Alfred AI
 *
 * Architecture:
 *   Browser mic → WebSocket → VAD → Together.ai Whisper STT → Alfred AI →
 *   Together.ai Kokoro TTS → WebSocket → browser plays
 *
 * Port: 3006 (configurable via VOICE_PORT env)
 * SSL:  Auto-loads cert from ~/.ssl_voice/ for WSS support
 *
 * Telnyx calling is integrated — voice sessions can trigger outbound calls.
 */

import { WebSocketServer } from 'ws';
import https from 'https';
import fs from 'fs';
import path from 'path';
import os from 'os';
import { transcribeAudio, generateSpeech, chatCompletion } from '../togetherClient.js';
import { remember, recall } from '../memory/memoryEngine.js';
import { processVoiceWithTools, isVoiceBridgeAvailable } from './voiceToolBridge.js';
import { makeCall, hangupCall, listActiveCalls } from './telnyxCalls.js';
import jwt from 'jsonwebtoken';

const JWT_SECRET = process.env.JWT_SECRET || '';

const VOICE_PORT = parseInt(process.env.VOICE_PORT || '3006', 10);
const MAX_AUDIO_SIZE = 10 * 1024 * 1024; // 10MB

const SSL_CERT = process.env.VOICE_SSL_CERT || path.join(os.homedir(), '.ssl_voice', 'gositeme.crt');
const SSL_KEY  = process.env.VOICE_SSL_KEY  || path.join(os.homedir(), '.ssl_voice', 'gositeme.key');

const sessions = new Map();

let activeWss = null;

// ── Steering Queue ────────────────────────────────────────────────────────
// While Alfred is processing (tools running, AI thinking), the user can keep
// talking. New utterances are transcribed immediately, acknowledged to the
// client, and queued. When the current pipeline finishes, queued commands
// are fed to Alfred as context so he can "steer" — adjust, cancel, or chain.
// ──────────────────────────────────────────────────────────────────────────

export function startVoiceServer(options = {}) {
  if (activeWss) {
    console.log('[Voice] Server already running, skipping duplicate start');
    return activeWss;
  }

  let wss;

  if (options.server) {
    wss = new WebSocketServer({ server: options.server, path: '/voice' });
    console.log('[Voice] Attached to existing HTTP server on /voice');
  } else {
    const hasSsl = fs.existsSync(SSL_CERT) && fs.existsSync(SSL_KEY);

    if (hasSsl) {
      try {
        const httpsServer = https.createServer({
          cert: fs.readFileSync(SSL_CERT),
          key:  fs.readFileSync(SSL_KEY),
        });
        wss = new WebSocketServer({ server: httpsServer });
        httpsServer.listen(options.port || VOICE_PORT, '127.0.0.1', () => {
          console.log(`[Voice] WSS (secure) server ready on 127.0.0.1:${options.port || VOICE_PORT}`);
        });
      } catch (sslErr) {
        console.warn(`[Voice] SSL load failed (${sslErr.message}), falling back to WS`);
        wss = new WebSocketServer({ port: options.port || VOICE_PORT, host: '127.0.0.1' });
        console.log(`[Voice] WS (plain) server ready on 127.0.0.1:${options.port || VOICE_PORT}`);
      }
    } else {
      wss = new WebSocketServer({ port: options.port || VOICE_PORT, host: '127.0.0.1' });
      console.log(`[Voice] WS (plain) server ready on 127.0.0.1:${options.port || VOICE_PORT}`);
    }
  }

  activeWss = wss;

  wss.on('connection', (ws, req) => {
    const sessionId = `voice_${Date.now()}_${Math.random().toString(36).slice(2, 8)}`;
    const session = {
      id: sessionId,
      ws,
      homeDir: null,
      username: null,
      daUsername: null,
      whmcsClientId: null,
      authenticated: false,
      conversationHistory: [],
      isProcessing: false,
      createdAt: Date.now(),
      lastActivity: Date.now(),
      audioChunks: [],
      voice: 'onyx',
      ttsModel: 'kokoro',
      activeCalls: [],
      // ── Steering queue ──────────────────────────────────────────────
      steeringQueue: [],       // {text, receivedAt} — queued while processing
      pipelineAborted: false,  // set true if user says "stop"/"cancel"
    };
    sessions.set(sessionId, session);

    console.log(`[Voice] New session: ${sessionId}`);

    ws.send(JSON.stringify({
      type: 'session_start',
      sessionId,
      message: 'Alfred voice session started. Send audio chunks or text.',
    }));

    ws.on('message', async (data) => {
      session.lastActivity = Date.now();

      try {
        const str = Buffer.isBuffer(data) ? data.toString('utf-8') : String(data);
        if (str.length > 0 && (str[0] === '{' || str[0] === '[')) {
          try {
            const msg = JSON.parse(str);
            await handleMessage(session, msg);
            return;
          } catch (_jsonErr) {
            // Not valid JSON — fall through to audio
          }
        }

        const audioData = Buffer.isBuffer(data) ? data : Buffer.from(data);
        await handleAudioChunk(session, audioData);
      } catch (err) {
        ws.send(JSON.stringify({ type: 'error', message: err.message }));
      }
    });

    ws.on('close', () => {
      console.log(`[Voice] Session closed: ${sessionId}`);
      sessions.delete(sessionId);
    });

    ws.on('error', (err) => {
      console.error(`[Voice] Session error ${sessionId}:`, err.message);
      sessions.delete(sessionId);
    });
  });

  // Cleanup stale sessions every 5 minutes
  setInterval(() => {
    const now = Date.now();
    for (const [id, s] of sessions) {
      if (now - s.lastActivity > 30 * 60 * 1000) {
        s.ws.close();
        sessions.delete(id);
        console.log(`[Voice] Cleaned up stale session: ${id}`);
      }
    }
  }, 5 * 60 * 1000);

  return wss;
}

async function handleAudioChunk(session, audioData) {
  if (audioData.length > MAX_AUDIO_SIZE) {
    session.ws.send(JSON.stringify({ type: 'error', message: 'Audio chunk too large (max 10MB)' }));
    return;
  }

  if (session.isProcessing) {
    // ── STEERING: Transcribe immediately, queue the text ──────────────
    // Instead of just buffering raw audio, we transcribe it right away so
    // the user gets immediate feedback and Alfred can see it after current op.
    try {
      session.ws.send(JSON.stringify({ type: 'status', stage: 'transcribing_queued' }));
      const sttResult = await transcribeAudio(audioData, 'speech.webm');
      const queuedText = sttResult.text.trim();
      if (queuedText) {
        // Check for abort keywords
        const lowerText = queuedText.toLowerCase();
        const ABORT_PHRASES = ['stop', 'cancel', 'abort', 'never mind', 'nevermind', 'arrête', 'annule'];
        if (ABORT_PHRASES.some(p => lowerText.includes(p))) {
          session.pipelineAborted = true;
          session.steeringQueue = []; // clear queue on abort
          session.ws.send(JSON.stringify({
            type: 'steering_abort',
            text: queuedText,
            message: 'Got it — cancelling the current operation.',
          }));
          return;
        }

        session.steeringQueue.push({ text: queuedText, receivedAt: Date.now() });
        session.ws.send(JSON.stringify({
          type: 'steering_queued',
          text: queuedText,
          position: session.steeringQueue.length,
          message: `Noted! I'll handle "${queuedText.substring(0, 60)}" right after I finish this.`,
        }));
      }
    } catch (sttErr) {
      // STT failed for queued audio — just buffer it the old way as fallback
      session.audioChunks.push(audioData);
    }
    return;
  }

  await processVoicePipeline(session, audioData);
}

async function handleMessage(session, msg) {
  switch (msg.type) {

    case 'auth': {
      if (msg.token && JWT_SECRET) {
        try {
          const decoded = jwt.verify(msg.token, JWT_SECRET, { algorithms: ['HS256'] });
          session.daUsername    = decoded.daUsername;
          session.whmcsClientId = decoded.whmcsClientId;
          session.username      = decoded.daUsername;
          session.authenticated = true;
          session.ws.send(JSON.stringify({ type: 'auth_ok', username: decoded.daUsername, mode: 'full' }));
        } catch (jwtErr) {
          session.authenticated = false;
          session.ws.send(JSON.stringify({ type: 'auth_fail', message: 'Invalid or expired token — using chat-only mode' }));
        }
      } else {
        session.homeDir  = msg.homeDir;
        session.username = msg.username;
        session.authenticated = false;
        session.ws.send(JSON.stringify({ type: 'auth_ok', username: msg.username, mode: 'chat' }));
      }
      break;
    }

    // voice-relay + alfred-voice-live send type: 'chat'; IDE/relay.js uses 'text'
    case 'chat':
    case 'text': {
      if (!msg.text) return;
      if (session.isProcessing) {
        // ── STEERING: Queue text commands while Alfred is busy ─────────
        const lowerText = msg.text.toLowerCase().trim();
        const ABORT_PHRASES = ['stop', 'cancel', 'abort', 'never mind', 'nevermind', 'arrête', 'annule'];
        if (ABORT_PHRASES.some(p => lowerText.includes(p))) {
          session.pipelineAborted = true;
          session.steeringQueue = [];
          session.ws.send(JSON.stringify({
            type: 'steering_abort',
            text: msg.text,
            message: 'Got it — cancelling the current operation.',
          }));
          return;
        }
        session.steeringQueue.push({ text: msg.text, receivedAt: Date.now() });
        session.ws.send(JSON.stringify({
          type: 'steering_queued',
          text: msg.text,
          position: session.steeringQueue.length,
          message: `Noted! I'll handle "${msg.text.substring(0, 60)}" right after I finish this.`,
        }));
        return;
      }
      await processTextInput(session, msg.text, msg.ttsEnabled !== false);
      break;
    }

    case 'audio': {
      if (!msg.data) return;
      const audioData = Buffer.from(msg.data, 'base64');
      console.log(`[Voice] Received base64 audio: ${audioData.length} bytes, format: ${msg.format || 'webm'}`);
      await handleAudioChunk(session, audioData);
      break;
    }

    case 'audio_complete': {
      if (session.audioChunks.length > 0) {
        const fullAudio = Buffer.concat(session.audioChunks);
        session.audioChunks = [];
        await processVoicePipeline(session, fullAudio);
      }
      break;
    }

    case 'set_voice': {
      session.voice    = msg.voice || 'alloy';
      session.ttsModel = msg.engine || msg.model || 'kokoro';
      session.ws.send(JSON.stringify({ type: 'voice_set', voice: session.voice, model: session.ttsModel }));
      break;
    }

    case 'preview_voice': {
      const previewText   = msg.text || `Hi! I'm your Alfred assistant. How can I help you today?`;
      const previewVoice  = msg.voice  || session.voice  || 'alloy';
      const previewEngine = msg.engine || msg.model || session.ttsModel || 'kokoro';
      try {
        const ttsResult = await generateSpeech(previewText, previewEngine, previewVoice);
        session.ws.send(JSON.stringify({ type: 'audio_response', data: ttsResult.audioBuffer.toString('base64'), format: 'mp3' }));
        session.ws.send(JSON.stringify({ type: 'preview_complete', engine: previewEngine, voice: previewVoice }));
      } catch (previewErr) {
        session.ws.send(JSON.stringify({ type: 'tts_error', message: `Preview failed: ${previewErr.message}` }));
      }
      break;
    }

    // ── Telnyx: make an outbound call ─────────────────────────────────────
    case 'make_call': {
      const { to, from } = msg;
      if (!to) {
        session.ws.send(JSON.stringify({ type: 'call_error', message: 'Missing "to" phone number' }));
        break;
      }
      try {
        session.ws.send(JSON.stringify({ type: 'call_status', status: 'dialing', to }));
        const result = await makeCall(to, from || null, session.id);
        session.activeCalls.push(result.callControlId);
        session.ws.send(JSON.stringify({
          type:          'call_initiated',
          callControlId: result.callControlId,
          to:            result.to,
          from:          result.from,
          status:        result.status,
          message:       result.message,
        }));
        console.log(`[Voice+Telnyx] Session ${session.id} initiated call to ${result.to}`);
      } catch (err) {
        console.error('[Telnyx] makeCall error:', err.message);
        session.ws.send(JSON.stringify({ type: 'call_error', message: err.message }));
      }
      break;
    }

    // ── Telnyx: hang up a call ────────────────────────────────────────────
    case 'hangup_call': {
      const ccId = msg.callControlId || session.activeCalls[session.activeCalls.length - 1];
      if (!ccId) {
        session.ws.send(JSON.stringify({ type: 'call_error', message: 'No active call to hang up' }));
        break;
      }
      try {
        const result = await hangupCall(ccId);
        session.activeCalls = session.activeCalls.filter(id => id !== ccId);
        session.ws.send(JSON.stringify({ type: 'call_ended', callControlId: ccId, message: result.message }));
      } catch (err) {
        session.ws.send(JSON.stringify({ type: 'call_error', message: err.message }));
      }
      break;
    }

    // ── Telnyx: list active calls ─────────────────────────────────────────
    case 'list_calls': {
      session.ws.send(JSON.stringify({ type: 'calls_list', calls: listActiveCalls() }));
      break;
    }

    case 'set_context': {
      session.systemPrompt = msg.context;
      session.ws.send(JSON.stringify({ type: 'context_set' }));
      break;
    }

    case 'clear_history': {
      session.conversationHistory = [];
      session.ws.send(JSON.stringify({ type: 'history_cleared' }));
      break;
    }

    case 'ping': {
      session.ws.send(JSON.stringify({ type: 'pong', timestamp: Date.now() }));
      break;
    }

    default:
      console.warn(`[Voice] Unhandled message type: ${msg.type}`);
      break;
  }
}

async function processVoicePipeline(session, audioBuffer) {
  if (session.isProcessing) return;
  session.isProcessing = true;
  session.pipelineAborted = false;

  try {
    session.ws.send(JSON.stringify({ type: 'status', stage: 'transcribing' }));
    const sttResult = await transcribeAudio(audioBuffer, 'speech.webm');
    const userText  = sttResult.text.trim();

    if (!userText) {
      session.ws.send(JSON.stringify({ type: 'status', stage: 'silence' }));
      return;
    }

    session.ws.send(JSON.stringify({ type: 'transcription', text: userText, timing: sttResult.timing }));

    // Check if aborted before starting AI
    if (session.pipelineAborted) {
      session.ws.send(JSON.stringify({ type: 'status', stage: 'aborted', detail: 'Cancelled by user' }));
      return;
    }

    await processTextInput(session, userText, true);

  } catch (err) {
    session.ws.send(JSON.stringify({ type: 'error', message: `Voice pipeline error: ${err.message}`, stage: 'pipeline' }));
  } finally {
    session.isProcessing = false;

    // ── STEERING: Process queued commands ────────────────────────────
    if (session.steeringQueue.length > 0) {
      const queued = [...session.steeringQueue];
      session.steeringQueue = [];

      session.ws.send(JSON.stringify({
        type: 'steering_processing',
        count: queued.length,
        message: `Processing ${queued.length} queued command${queued.length > 1 ? 's' : ''}...`,
      }));

      // Process each queued command sequentially
      for (const item of queued) {
        if (session.pipelineAborted) break;
        session.isProcessing = true;
        try {
          session.ws.send(JSON.stringify({
            type: 'steering_executing',
            text: item.text,
            message: `Now handling: "${item.text.substring(0, 60)}"`,
          }));
          await processTextInput(session, item.text, true);
        } catch (qErr) {
          session.ws.send(JSON.stringify({ type: 'error', message: `Queued command error: ${qErr.message}` }));
        } finally {
          session.isProcessing = false;
        }
      }
    }

    // Also drain any raw audio chunks (legacy fallback)
    if (session.audioChunks.length > 0) {
      const queued = Buffer.concat(session.audioChunks);
      session.audioChunks = [];
      setTimeout(() => processVoicePipeline(session, queued), 100);
    }
  }
}

async function processTextInput(session, userText, ttsEnabled = true) {
  const aiStart = Date.now();

  session.conversationHistory.push({ role: 'user', content: userText });
  if (session.conversationHistory.length > 20) {
    session.conversationHistory = session.conversationHistory.slice(-20);
  }

  session.ws.send(JSON.stringify({ type: 'status', stage: 'thinking' }));

  let aiText, aiModel, aiTiming;

  if (session.authenticated && session.daUsername && isVoiceBridgeAvailable()) {
    try {
      const result = await processVoiceWithTools({
        userText,
        daUsername: session.daUsername,
        whmcsClientId: session.whmcsClientId,
        conversationHistory: session.conversationHistory.slice(0, -1),
        onStatus: (stage, detail) => {
          session.ws.send(JSON.stringify({ type: 'status', stage, detail }));
        },
        isAborted: () => session.pipelineAborted,
      });
      aiText   = result.text;
      aiModel  = result.model || 'claude';
      aiTiming = result.timing;
    } catch (bridgeErr) {
      console.error(`[Voice] Tool bridge error for ${session.id}:`, bridgeErr.message);
      const systemPrompt = 'You are Alfred, an expert AI assistant by GoSiteMe. Keep responses concise for voice.';
      const messages = [{ role: 'system', content: systemPrompt }, ...session.conversationHistory];
      const fallback = await chatCompletion(messages, 'llama-3.3-70b', 512, 0.7);
      aiText   = fallback.text;
      aiModel  = fallback.model;
      aiTiming = fallback.timing;
    }
  } else {
    const systemPrompt = session.systemPrompt ||
      'You are Alfred, an expert AI programming assistant built into GoCodeMe — the AI-powered cloud IDE by GoSiteMe. ' +
      'Keep responses concise and conversational since this is a voice interaction. ' +
      'Limit responses to 2-3 sentences unless the user asks for more detail. ' +
      'Never read out ".com" in URLs — just say the brand name naturally. ' +
      'You cannot access any user accounts, files, or server tools in this mode. ' +
      'If the user asks you to do something that requires tool access, let them know ' +
      'they need to sign in at GoSiteMe for full Alfred capabilities.';

    const messages = [{ role: 'system', content: systemPrompt }, ...session.conversationHistory];
    const aiResult = await chatCompletion(messages, 'llama-3.3-70b', 512, 0.7);
    aiText   = aiResult.text;
    aiModel  = aiResult.model;
    aiTiming = aiResult.timing;
  }

  session.conversationHistory.push({ role: 'assistant', content: aiText });

  session.ws.send(JSON.stringify({ type: 'response', text: aiText, timing: aiTiming, model: aiModel }));

  if (ttsEnabled && aiText) {
    session.ws.send(JSON.stringify({ type: 'status', stage: 'speaking' }));

    try {
      const voice    = session.voice    || 'alloy';
      const ttsModel = session.ttsModel || 'kokoro';
      const ttsResult = await generateSpeech(aiText, ttsModel, voice);

      session.ws.send(JSON.stringify({
        type:   'audio_response',
        data:   ttsResult.audioBuffer.toString('base64'),
        format: 'mp3',
      }));

      session.ws.send(JSON.stringify({
        type:      'tts_complete',
        timing:    ttsResult.timing,
        model:     ttsResult.model,
        audioSize: ttsResult.audioBuffer.length,
      }));
    } catch (ttsErr) {
      session.ws.send(JSON.stringify({
        type:    'tts_error',
        message: `TTS failed: ${ttsErr.message}. Text response was still delivered.`,
      }));
    }
  }

  const totalTime = Date.now() - aiStart;
  session.ws.send(JSON.stringify({
    type:        'pipeline_complete',
    totalTiming: totalTime,
    userText,
    aiText:      aiText.substring(0, 100) + (aiText.length > 100 ? '...' : ''),
  }));
}

export function getVoiceStatus() {
  const now = Date.now();
  return {
    activeSessions: sessions.size,
    sessions: Array.from(sessions.values()).map(s => ({
      id:            s.id,
      username:      s.username,
      authenticated: s.authenticated,
      daUsername:    s.daUsername,
      uptime:        `${((now - s.createdAt) / 1000).toFixed(0)}s`,
      lastActivity:  `${((now - s.lastActivity) / 1000).toFixed(0)}s ago`,
      messageCount:  s.conversationHistory.length,
      isProcessing:  s.isProcessing,
      activeCalls:   s.activeCalls.length,
    })),
  };
}

export function sendToSession(sessionId, message) {
  const session = sessions.get(sessionId);
  if (!session) return false;
  session.ws.send(typeof message === 'string' ? message : JSON.stringify(message));
  return true;
}
