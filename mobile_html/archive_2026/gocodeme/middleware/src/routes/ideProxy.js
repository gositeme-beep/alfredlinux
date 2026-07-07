'use strict';

/**
 * IDE Reverse Proxy
 *
 * Proxies HTTP and WebSocket connections to per-customer Theia/Agent instances.
 *
 * Routes:
 *   /ide/:port/*     → http://127.0.0.1:<port>/*
 *   /agent/:port/*   → http://127.0.0.1:<port>/*
 *
 * Security:
 *   - Only ports in the allowed range (4000–4040) are proxied
 *   - Port must have an active session in Redis (launched by /api/launch)
 *   - WebSocket upgrade is handled automatically by http-proxy
 */

const httpProxy = require('http-proxy');
const zlib = require('zlib');
const { getRedis } = require('../redis');
const logger = require('../logger');
const jwt = require('jsonwebtoken');
const scanKeys = require('../utils/scanKeys');

const PORT_MIN = 4000;

// ── IDE Auth Token helpers ─────────────────────────────────────────────────
// Each IDE session gets a random auth token stored in Redis.
// Users must present this token (via cookie or query param) to access the proxy.
// This prevents cross-tenant access via port-guessing (VULN-01/02).

/** Parse cookies from a raw Cookie header string */
function parseCookies(cookieHeader) {
  const cookies = {};
  if (!cookieHeader) return cookies;
  cookieHeader.split(';').forEach(pair => {
    const idx = pair.indexOf('=');
    if (idx > 0) {
      const name = pair.slice(0, idx).trim();
      const val = pair.slice(idx + 1).trim();
      try { cookies[name] = decodeURIComponent(val); } catch { cookies[name] = val; }
    }
  });
  return cookies;
}

/** Validate IDE auth token from cookie or query param.
 *  Returns { ok, daUsername, token, fromQuery } or { ok: false, reason }.
 *  Token must be a UUID stored in Redis as ide_auth_token:<token> → daUsername. */
async function validateIdeAuth(req, port) {
  // Extract token from query param or cookie
  const cookies = parseCookies(req.headers ? (req.headers.cookie || '') : '');
  const token = (req.query && req.query.gcm_auth) || cookies.gcm_ide_auth;
  if (!token) return { ok: false, reason: 'missing' };

  // Validate token format — must be a UUID
  if (!/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i.test(token)) {
    return { ok: false, reason: 'invalid_format' };
  }

  // Lookup token in Redis
  const redis = getRedis();
  const tokenOwner = await redis.get(`ide_auth_token:${token}`);
  if (!tokenOwner) return { ok: false, reason: 'expired' };

  // Verify the token owner matches the port owner
  const portOwner = await validatePort(port);
  if (!portOwner) return { ok: false, reason: 'no_session' };
  if (portOwner !== tokenOwner) return { ok: false, reason: 'not_owner' };

  return { ok: true, daUsername: tokenOwner, token, fromQuery: !!(req.query && req.query.gcm_auth) };
}
const PORT_MAX = 4500;  // supports 250 concurrent users (2 ports each: Theia + Agent)

// WebSocket-only proxy (simple pass-through, no response rewriting)
const wsProxy = httpProxy.createProxyServer({
  ws: true,
  changeOrigin: true,
  xfwd: true,
  selfHandleResponse: false,
});

wsProxy.on('error', (err, req, res) => {
  logger.error(`WS proxy error: ${err.message}`);
});

// ── Model Multiplier Defaults (shared with model selector script) ──────────
const { MODELS } = require('../billing/modelRouter');
const GCM_PICKER_IDS = [
  'claude-opus-4-6', 'claude-sonnet-4-6', 'claude-haiku-4-5', 'gpt-4.1', 'gpt-4.1-mini', 'gpt-4.1-nano', 'gpt-4o', 'o3', 'o3-mini', 'o4-mini',
  'gemini-3.1-pro', 'gemini-3-flash', 'gemini-3.1-flash-lite', 'gemini-image', 'gemini-2.5-pro', 'gemini-2.5-flash',
  'grok-3', 'grok-3-mini', 'qwen3-coder', 'qwen3-coder-480b', 'qwen3.5', 'deepseek-v3', 'deepseek-r1', 'glm-5', 'kimi-k2.5', 'kimi-k2-thinking',
  'llama-4-maverick', 'llama-4-scout', 'mistral-small', 'groq-llama-3.3-70b', 'groq-llama-3.1-8b', 'veo-2-video',
];
const gcmTokenMultDefaults = {};
for (const id of GCM_PICKER_IDS) {
  if (MODELS[id]) gcmTokenMultDefaults[id] = MODELS[id].tokenMultiplier || 1;
}

// ── Alfred IDE Widget — full chat + voice assistant ─────────────────────────
function getAlfredWidget(authInfo) {
  const daUsername = authInfo.daUsername || '';
  const voiceToken = authInfo.voiceToken || '';
  const sessionToken = authInfo.sessionToken || voiceToken;
  const isAuth = authInfo.isAuth ? 'true' : 'false';
  return `
<!-- GoCodeMe Session Token for API auth (model selector, usage, etc.) -->
<script>window.__gcmSessionToken=${JSON.stringify(sessionToken)};window.__gcmTokenMultDefaults=${JSON.stringify(gcmTokenMultDefaults)};</script>
<!-- Alfred AI Voice + Chat Widget for GoCodeMe IDE -->
<style>
#gcmA{position:fixed;bottom:20px;right:20px;z-index:99999;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;font-size:14px;line-height:1.4}
#gcmA *{box-sizing:border-box}
#gcmA button{font-family:inherit}
.gcm-orb{width:54px;height:54px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:radial-gradient(circle at 35% 35%,rgba(192,132,252,0.95),rgba(125,0,255,0.7) 50%,rgba(0,212,255,0.35));box-shadow:0 6px 28px rgba(125,0,255,0.45),0 0 40px rgba(125,0,255,0.12);cursor:pointer;border:none;outline:none;position:relative;transition:all .3s ease;animation:gcmFloat 3s ease-in-out infinite}
.gcm-orb:hover{transform:scale(1.1);box-shadow:0 10px 40px rgba(125,0,255,0.6)}
.gcm-orb::before{content:'';position:absolute;top:12%;left:18%;width:30%;height:25%;background:rgba(255,255,255,0.18);border-radius:50%;filter:blur(5px);transform:rotate(-30deg);pointer-events:none}
.gcm-orb-icon{font-size:1.3rem;filter:drop-shadow(0 2px 4px rgba(0,0,0,0.4));z-index:1;line-height:1}
.gcm-orb-pulse{position:absolute;width:100%;height:100%;border-radius:50%;background:radial-gradient(circle,rgba(125,0,255,0.3),transparent 70%);animation:gcmPulse 2.5s ease-out infinite;z-index:-1;pointer-events:none}
.gcm-orb-ring{position:absolute;width:100%;height:100%;border-radius:50%;border:2px solid rgba(0,212,255,0.3);animation:gcmRingAnim 3s ease-out infinite;z-index:-1;pointer-events:none}
.gcm-orb-badge{position:absolute;top:-3px;right:-3px;background:linear-gradient(135deg,#10b981,#06b6d4);color:#fff;font-size:.5rem;font-weight:900;padding:2px 5px;border-radius:6px;letter-spacing:.5px;border:2px solid #1e1e1e;z-index:2}
.gcm-orb.listening{animation:gcmListenPulse 1s ease-in-out infinite;box-shadow:0 0 30px rgba(239,68,68,0.6),0 0 60px rgba(239,68,68,0.2)}
.gcm-orb.processing{animation:gcmThink 1.5s linear infinite}
.gcm-orb.speaking{animation:gcmSpeak .8s ease-in-out infinite alternate}
@keyframes gcmFloat{0%,100%{box-shadow:0 6px 28px rgba(125,0,255,.45),0 0 40px rgba(125,0,255,.12)}50%{box-shadow:0 10px 40px rgba(125,0,255,.6),0 0 60px rgba(125,0,255,.2)}}
@keyframes gcmPulse{0%{transform:scale(1);opacity:.5}100%{transform:scale(1.8);opacity:0}}
@keyframes gcmRingAnim{0%{transform:scale(1);opacity:.6}100%{transform:scale(2);opacity:0}}
@keyframes gcmListenPulse{0%,100%{transform:scale(1)}50%{transform:scale(1.08)}}
@keyframes gcmThink{0%{box-shadow:0 0 20px rgba(125,0,255,.5)}50%{box-shadow:0 0 40px rgba(0,212,255,.5)}100%{box-shadow:0 0 20px rgba(125,0,255,.5)}}
@keyframes gcmSpeak{from{box-shadow:0 0 20px rgba(16,185,129,.4)}to{box-shadow:0 0 40px rgba(16,185,129,.6)}}
.gcm-panel{display:none;position:fixed;bottom:84px;right:20px;width:380px;height:520px;background:rgba(13,13,24,0.97);backdrop-filter:blur(20px);border:1px solid rgba(125,0,255,0.25);border-radius:16px;box-shadow:0 16px 48px rgba(0,0,0,0.55),0 0 20px rgba(125,0,255,0.08);z-index:99999;overflow:hidden;animation:gcmSlide .25s ease;flex-direction:column}
.gcm-panel.open{display:flex}
@keyframes gcmSlide{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
.gcm-head{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-bottom:1px solid rgba(255,255,255,0.08);flex-shrink:0}
.gcm-head-left{display:flex;align-items:center;gap:8px}
.gcm-head-title{font-size:.88rem;font-weight:700;color:#fff}
.gcm-head-status{font-size:.6rem;padding:2px 7px;border-radius:10px;font-weight:600}
.gcm-head-status.offline{background:rgba(255,255,255,0.08);color:rgba(255,255,255,0.4)}
.gcm-head-status.online{background:rgba(16,185,129,0.2);color:#10b981}
.gcm-head-status.connecting{background:rgba(251,191,36,0.2);color:#fbbf24}
.gcm-head-btns{display:flex;gap:4px}
.gcm-head-btns button{background:none;border:none;color:rgba(255,255,255,0.4);font-size:1rem;cursor:pointer;padding:4px;line-height:1;border-radius:6px;transition:all .2s}
.gcm-head-btns button:hover{color:#fff;background:rgba(255,255,255,0.08)}
.gcm-head-btns button.active{color:#a78bfa}
.gcm-tabs{display:flex;border-bottom:1px solid rgba(255,255,255,0.06);flex-shrink:0}
.gcm-tab{flex:1;padding:8px;text-align:center;font-size:.72rem;font-weight:600;color:rgba(255,255,255,0.35);cursor:pointer;border-bottom:2px solid transparent;transition:all .2s;background:none;border-top:none;border-left:none;border-right:none}
.gcm-tab:hover{color:rgba(255,255,255,0.6)}
.gcm-tab.active{color:#a78bfa;border-bottom-color:#7c3aed}
.gcm-chat{flex:1;overflow-y:auto;padding:12px 14px;display:flex;flex-direction:column;gap:8px;scrollbar-width:thin;scrollbar-color:rgba(255,255,255,0.08) transparent}
.gcm-chat::-webkit-scrollbar{width:4px}
.gcm-chat::-webkit-scrollbar-thumb{background:rgba(255,255,255,0.1);border-radius:2px}
.gcm-msg{max-width:88%;padding:8px 12px;border-radius:12px;font-size:.82rem;line-height:1.45;word-break:break-word;animation:gcmFadeIn .2s ease}
@keyframes gcmFadeIn{from{opacity:0;transform:translateY(4px)}to{opacity:1;transform:translateY(0)}}
.gcm-msg.user{align-self:flex-end;background:linear-gradient(135deg,rgba(125,0,255,0.3),rgba(0,212,255,0.15));color:#e2e8f0;border-bottom-right-radius:4px}
.gcm-msg.alfred{align-self:flex-start;background:rgba(255,255,255,0.06);color:#c9d1d9;border-bottom-left-radius:4px}
.gcm-msg.system{align-self:center;background:none;color:rgba(255,255,255,0.3);font-size:.72rem;font-style:italic;padding:4px 8px}
.gcm-msg.alfred pre{background:rgba(0,0,0,0.3);border-radius:6px;padding:8px 10px;overflow-x:auto;margin:6px 0 2px;font-size:.76rem;font-family:'Fira Code',monospace}
.gcm-msg.alfred code{background:rgba(0,0,0,0.25);padding:1px 4px;border-radius:3px;font-size:.78rem;font-family:'Fira Code',monospace}
.gcm-empty{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;color:rgba(255,255,255,0.25);padding:20px}
.gcm-empty-icon{font-size:2.5rem;opacity:.6}
.gcm-empty-text{font-size:.82rem;text-align:center;max-width:240px;line-height:1.5}
.gcm-empty-hint{font-size:.7rem;color:rgba(255,255,255,0.15)}
.gcm-typing{align-self:flex-start;display:flex;gap:4px;padding:10px 14px;background:rgba(255,255,255,0.06);border-radius:12px;border-bottom-left-radius:4px}
.gcm-typing span{width:6px;height:6px;border-radius:50%;background:rgba(255,255,255,0.3);animation:gcmDot 1.4s ease-in-out infinite}
.gcm-typing span:nth-child(2){animation-delay:.2s}
.gcm-typing span:nth-child(3){animation-delay:.4s}
@keyframes gcmDot{0%,80%,100%{transform:scale(.6);opacity:.4}40%{transform:scale(1);opacity:1}}
.gcm-input-area{display:flex;align-items:center;gap:6px;padding:10px 12px;border-top:1px solid rgba(255,255,255,0.08);flex-shrink:0;background:rgba(0,0,0,0.15)}
.gcm-compact-banner{display:flex;align-items:center;gap:8px;padding:8px 12px;background:linear-gradient(135deg,rgba(245,158,11,0.12),rgba(239,68,68,0.08));border-bottom:1px solid rgba(245,158,11,0.2);flex-shrink:0;font-size:.78rem;animation:gcmFadeIn .3s ease}
.gcm-compact-banner.critical{background:linear-gradient(135deg,rgba(239,68,68,0.15),rgba(220,38,38,0.1));border-color:rgba(239,68,68,0.3)}
.gcm-compact-icon{font-size:1rem;flex-shrink:0}
.gcm-compact-text{color:rgba(255,255,255,0.7);flex:1}
.gcm-compact-btn{padding:4px 12px;background:linear-gradient(135deg,rgba(125,0,255,0.8),rgba(0,212,255,0.6));border:none;border-radius:8px;color:#fff;font-size:.72rem;font-weight:600;cursor:pointer;letter-spacing:.3px;transition:all .2s;flex-shrink:0}
.gcm-compact-btn:hover{transform:scale(1.05);box-shadow:0 2px 12px rgba(125,0,255,0.4)}
.gcm-compact-btn:disabled{opacity:.5;cursor:not-allowed;transform:none}
/* v9.0: Context usage bar */
.gcm-context-bar{display:flex;align-items:center;gap:6px;padding:4px 12px;border-bottom:1px solid rgba(255,255,255,0.05);flex-shrink:0;font-size:.65rem;color:rgba(255,255,255,0.4)}
.gcm-ctx-track{flex:1;height:4px;background:rgba(255,255,255,0.06);border-radius:2px;overflow:hidden;min-width:60px}
.gcm-ctx-fill{height:100%;border-radius:2px;transition:width .5s ease,background .5s;background:#7d00ff;width:0%}
.gcm-ctx-fill.warn{background:linear-gradient(90deg,#f59e0b,#ef4444)}
.gcm-ctx-fill.critical{background:#ef4444;animation:gcmPulseBar 1s ease-in-out infinite}
@keyframes gcmPulseBar{0%,100%{opacity:1}50%{opacity:.6}}
.gcm-ctx-label{white-space:nowrap;min-width:32px;text-align:right}
/* v9.0: Model switcher pills in input area */
.gcm-model-badge{display:inline-flex;align-items:center;gap:3px;padding:2px 8px;background:rgba(125,0,255,0.12);border:1px solid rgba(125,0,255,0.2);border-radius:8px;font-size:.6rem;color:#a78bfa;cursor:pointer;flex-shrink:0;transition:all .2s;white-space:nowrap}
.gcm-model-badge:hover{background:rgba(125,0,255,0.2);border-color:rgba(125,0,255,0.4)}
.gcm-model-menu{position:absolute;bottom:100%;right:0;background:rgba(20,20,30,0.97);border:1px solid rgba(125,0,255,0.25);border-radius:10px;padding:6px;min-width:180px;display:none;z-index:100001;box-shadow:0 8px 24px rgba(0,0,0,0.5);max-height:200px;overflow-y:auto}
.gcm-model-menu.open{display:block}
.gcm-model-opt{display:flex;align-items:center;gap:8px;padding:6px 10px;border-radius:6px;cursor:pointer;font-size:.72rem;color:rgba(255,255,255,0.7);transition:all .15s}
.gcm-model-opt:hover{background:rgba(125,0,255,0.15);color:#fff}
.gcm-model-opt.active{background:rgba(125,0,255,0.2);color:#a78bfa;font-weight:600}
/* Full model picker (grouped + credits + multipliers) */
.gcm-model-popup{position:fixed;display:none;flex-direction:column;min-width:286px;max-width:310px;max-height:min(70vh,540px);overflow-y:auto;background:rgba(16,16,24,0.98);backdrop-filter:blur(12px);border:1px solid rgba(125,0,255,0.35);border-radius:14px;padding:6px 0;z-index:100003;box-shadow:0 16px 48px rgba(0,0,0,0.55),0 0 0 1px rgba(125,0,255,0.08);font-family:system-ui,-apple-system,BlinkMacSystemFont,sans-serif}
.gcm-model-popup.open{display:flex}
.gcm-popup-auto{display:flex;align-items:center;gap:10px;padding:11px 14px;margin:2px 8px 8px;border-radius:11px;cursor:pointer;background:rgba(125,0,255,0.14);border:1px solid rgba(125,0,255,0.28);font-size:.8rem;font-weight:600;color:#fff;transition:all .15s}
.gcm-popup-auto:hover,.gcm-popup-auto.active{background:rgba(125,0,255,0.26);border-color:rgba(167,139,250,0.45);box-shadow:0 0 16px rgba(125,0,255,0.12)}
.gcm-credit-balance{display:flex;align-items:center;gap:8px;padding:6px 14px 10px;margin:0 8px;font-size:.72rem;color:rgba(255,255,255,0.55);border-bottom:1px solid rgba(255,255,255,0.06)}
.gcm-credit-amount{margin-left:auto;font-weight:800;color:#34d399;font-size:.78rem}
.gcm-popup-group{padding:8px 14px 4px;font-size:.62rem;font-weight:800;color:rgba(255,255,255,0.32);text-transform:uppercase;letter-spacing:.55px}
.gcm-popup-item{display:flex;align-items:center;flex-wrap:wrap;gap:4px 8px;padding:9px 12px;margin:1px 6px;border-radius:10px;cursor:pointer;transition:background .12s;font-size:.74rem}
.gcm-popup-item:hover{background:rgba(125,0,255,0.1)}
.gcm-popup-item.active{background:rgba(125,0,255,0.22);outline:1px solid rgba(125,0,255,0.25)}
.gcm-item-emoji{font-size:1.05rem;line-height:1;flex-shrink:0}
.gcm-item-name{flex:1;min-width:100px;color:#f4f4f5;font-weight:550}
.gcm-item-tier{font-size:.56rem;padding:3px 7px;border-radius:7px;white-space:nowrap;font-weight:700}
.gcm-item-tier.premium{color:#fbbf24;background:rgba(251,191,36,0.14)}
.gcm-item-tier.standard{color:#93c5fd;background:rgba(147,197,253,0.1)}
.gcm-item-tier.economy{color:#6ee7b7;background:rgba(110,231,183,0.09)}
.gcm-item-tier.free{color:#fb923c;background:rgba(251,146,60,0.12)}
.gcm-multiplier-badge{font-size:.54rem;padding:3px 7px;border-radius:7px;background:rgba(255,255,255,0.07);color:rgba(255,255,255,0.45);cursor:pointer;font-weight:700;transition:all .12s;border:1px solid rgba(255,255,255,0.06)}
.gcm-multiplier-badge:hover{background:rgba(245,158,11,0.15);color:#fbbf24;border-color:rgba(245,158,11,0.25)}
.gcm-multiplier-editor{display:none;flex-wrap:wrap;gap:5px;padding:10px 12px;background:rgba(0,0,0,0.35);margin:4px 8px 8px;border-radius:10px;align-items:center;border:1px solid rgba(245,158,11,0.15)}
.gcm-multiplier-editor.open{display:flex}
.gcm-multiplier-editor input{width:52px;padding:5px 6px;border-radius:8px;border:1px solid rgba(255,255,255,0.12);background:rgba(255,255,255,0.05);color:#fff;font-size:.72rem}
.gcm-mult-preset{padding:5px 9px;font-size:.58rem;border-radius:8px;border:1px solid rgba(245,158,11,0.35);background:rgba(245,158,11,0.1);color:#fcd34d;cursor:pointer;font-weight:700}
.gcm-mult-preset:hover{background:rgba(245,158,11,0.22)}
/* v9.0: Snippet save button */
.gcm-snippet-btn{display:none;padding:2px 6px;background:rgba(16,185,129,0.15);border:1px solid rgba(16,185,129,0.2);border-radius:6px;font-size:.55rem;color:#10b981;cursor:pointer;transition:all .2s;margin-left:auto}
.gcm-snippet-btn:hover{background:rgba(16,185,129,0.3)}
.gcm-msg:hover .gcm-snippet-btn{display:inline-block}
/* v9.0: Reconnect banner */
.gcm-reconnect-banner{display:none;align-items:center;justify-content:center;gap:8px;padding:6px 12px;background:linear-gradient(135deg,rgba(239,68,68,0.12),rgba(245,158,11,0.08));border-bottom:1px solid rgba(239,68,68,0.2);font-size:.72rem;color:rgba(255,255,255,0.7);flex-shrink:0}
.gcm-reconnect-banner.active{display:flex}
.gcm-reconnect-spinner{width:12px;height:12px;border:2px solid rgba(239,68,68,0.3);border-top-color:#ef4444;border-radius:50%;animation:gcmSpin .8s linear infinite}
@keyframes gcmSpin{to{transform:rotate(360deg)}}
.gcm-input-area input{flex:1;padding:10px 14px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:12px;color:#fff;font-size:.84rem;font-family:inherit;outline:none;transition:border-color .2s;min-width:0}
.gcm-input-area input::placeholder{color:rgba(255,255,255,0.2)}
.gcm-input-area input:focus{border-color:rgba(125,0,255,0.5)}
.gcm-mic-btn{width:38px;height:38px;border-radius:50%;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);color:rgba(255,255,255,0.5);font-size:1rem;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .2s;flex-shrink:0}
.gcm-mic-btn:hover{background:rgba(125,0,255,0.2);color:#fff;border-color:rgba(125,0,255,0.4)}
.gcm-mic-btn.recording{background:rgba(239,68,68,0.25);border-color:rgba(239,68,68,0.5);color:#ef4444;animation:gcmMicPulse 1s ease-in-out infinite}
@keyframes gcmMicPulse{0%,100%{box-shadow:0 0 0 0 rgba(239,68,68,0.3)}50%{box-shadow:0 0 0 8px rgba(239,68,68,0)}}
.gcm-send-btn{position:relative;z-index:3;width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#7D00FF,#00D4FF);border:none;color:#fff;font-size:1rem;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .2s;flex-shrink:0;font-weight:700;-webkit-tap-highlight-color:transparent;touch-action:manipulation}
.gcm-send-btn:not(.gcm-send-dim):hover{transform:scale(1.05);box-shadow:0 4px 16px rgba(125,0,255,0.4)}
.gcm-send-btn.gcm-send-dim{opacity:.32;cursor:not-allowed;transform:none;box-shadow:none;filter:grayscale(.35)}
.gcm-stop-btn{position:relative;z-index:3;touch-action:manipulation;width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#ef4444,#dc2626);border:none;color:#fff;font-size:.9rem;cursor:pointer;display:none;align-items:center;justify-content:center;transition:all .2s;flex-shrink:0;font-weight:700;animation:gcmPulse 1.5s ease-in-out infinite}
.gcm-stop-btn:hover{transform:scale(1.05);box-shadow:0 4px 16px rgba(239,68,68,0.4)}
.gcm-settings{display:none;flex:1;overflow-y:auto;padding:14px 16px}
.gcm-settings.open{display:block}
.gcm-settings h4{font-size:.7rem;text-transform:uppercase;letter-spacing:.8px;color:rgba(255,255,255,0.3);margin:0 0 8px;font-weight:600}
.gcm-settings-section{margin-bottom:16px}
.gcm-model-pills{display:flex;flex-wrap:wrap;gap:6px}
.gcm-pill{background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:16px;padding:5px 12px;font-size:.72rem;color:rgba(255,255,255,0.5);cursor:pointer;transition:all .2s;font-weight:500}
.gcm-pill:hover{background:rgba(125,0,255,0.15);color:#fff}
.gcm-pill.active{background:linear-gradient(135deg,rgba(125,0,255,0.3),rgba(0,212,255,0.2));color:#fff;border-color:rgba(125,0,255,0.5);font-weight:700}
.gcm-model-grid{display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-top:8px}
.gcm-model-card{background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:8px;padding:8px 10px;cursor:pointer;transition:all .2s;text-align:left}
.gcm-model-card:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(0,0,0,0.3);border-color:var(--card-glow,rgba(99,102,241,0.4))!important}
.gcm-model-card.active{border-width:2px;border-color:var(--card-glow,rgba(99,102,241,0.5))!important;box-shadow:0 0 12px var(--card-glow,rgba(99,102,241,0.2))}
.gcm-model-card .gcm-mn{font-size:.76rem;font-weight:600;color:#fff}
.gcm-model-card .gcm-mt{font-size:.6rem;margin-top:2px}
.gcm-model-card .gcm-mt.gcm-free{color:#10b981}
.gcm-model-card .gcm-free-badge{display:inline-block;background:#10b981;color:#000;font-size:.5rem;font-weight:700;padding:1px 5px;border-radius:3px;margin-left:4px;vertical-align:middle;letter-spacing:.5px}
.gcm-token-bar{background:rgba(255,255,255,0.06);border-radius:6px;height:6px;overflow:hidden;margin-top:6px}
.gcm-token-fill{height:100%;border-radius:6px;background:linear-gradient(90deg,#10b981,#06b6d4);transition:width .5s ease}
.gcm-token-info{display:flex;justify-content:space-between;font-size:.65rem;color:rgba(255,255,255,0.3);margin-top:4px}
.gcm-tts-toggle{display:flex;align-items:center;justify-content:space-between;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:10px;padding:10px 14px;margin-top:8px}
.gcm-tts-label{font-size:.78rem;color:rgba(255,255,255,0.6)}
.gcm-tts-switch{position:relative;width:36px;height:20px;background:rgba(255,255,255,0.1);border-radius:10px;cursor:pointer;transition:background .2s}
.gcm-tts-switch.on{background:rgba(125,0,255,0.5)}
.gcm-tts-switch::after{content:'';position:absolute;top:2px;left:2px;width:16px;height:16px;border-radius:50%;background:#fff;transition:transform .2s}
.gcm-tts-switch.on::after{transform:translateX(16px)}
.gcm-voice-link{display:flex;align-items:center;gap:8px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:10px;padding:10px 14px;text-decoration:none;color:#fff;transition:all .2s;margin-top:8px}
.gcm-voice-link:hover{background:rgba(125,0,255,0.12);border-color:rgba(125,0,255,0.3)}
.gcm-voice-link .gcm-vl-icon{font-size:1.2rem}
.gcm-voice-link .gcm-vl-text{font-size:.76rem;font-weight:600}
.gcm-voice-link .gcm-vl-sub{font-size:.62rem;color:rgba(255,255,255,0.3);margin-top:1px}
@media(max-width:600px){
#gcmA{bottom:10px;right:10px}
.gcm-orb{width:48px;height:48px}
.gcm-orb-icon{font-size:1.1rem}
.gcm-panel{bottom:0;right:0;left:0;width:100%;height:85vh;max-height:100vh;border-radius:16px 16px 0 0;border-bottom:none}
.gcm-input-area input{font-size:16px}
}
@media(max-width:400px){
.gcm-panel{height:90vh}
.gcm-model-grid{grid-template-columns:1fr}
}
.gcm-bridge-indicator{display:inline-flex;align-items:center;gap:4px;font-size:.55rem;padding:2px 6px;border-radius:8px;font-weight:600;margin-left:6px}
.gcm-bridge-indicator.active{background:rgba(125,0,255,0.2);color:#a78bfa}
.gcm-bridge-indicator.inactive{background:rgba(255,255,255,0.06);color:rgba(255,255,255,0.3)}
.gcm-bridge-dot{width:6px;height:6px;border-radius:50%;display:inline-block}
.gcm-bridge-dot.active{background:#10b981;box-shadow:0 0 6px rgba(16,185,129,0.5)}
.gcm-bridge-dot.inactive{background:rgba(255,255,255,0.2)}
.gcm-relay-btn{display:inline-flex;align-items:center;gap:3px;font-size:.55rem;padding:2px 8px;border-radius:10px;font-weight:700;cursor:pointer;transition:all .2s;border:1px solid rgba(125,0,255,0.2);background:rgba(125,0,255,0.08);color:rgba(255,255,255,0.4);margin-left:4px;white-space:nowrap}
.gcm-relay-btn:hover{background:rgba(125,0,255,0.2);color:#a78bfa}
.gcm-relay-btn.active{background:rgba(16,185,129,0.2);border-color:rgba(16,185,129,0.3);color:#10b981;animation:gcmRelayPulse 2s ease-in-out infinite}
.gcm-relay-btn.active:hover{background:rgba(16,185,129,0.3)}
@keyframes gcmRelayPulse{0%,100%{box-shadow:none}50%{box-shadow:0 0 8px rgba(16,185,129,0.3)}}
.gcm-msg.ide-alfred{align-self:flex-start;background:linear-gradient(135deg,rgba(16,185,129,0.1),rgba(6,182,212,0.08));color:#a7f3d0;border:1px solid rgba(16,185,129,0.15);border-bottom-left-radius:4px;border-radius:12px}
.gcm-msg.ide-alfred .gcm-ide-label{display:inline-block;font-size:.6rem;font-weight:700;color:#10b981;margin-bottom:4px;letter-spacing:.3px}
.gcm-msg.relayed{position:relative}
.gcm-msg.relayed::after{content:'🔄';position:absolute;top:4px;right:6px;font-size:.55rem;opacity:.5}
.gcm-relay-status{text-align:center;font-size:.65rem;color:rgba(125,0,255,0.6);padding:4px 0;animation:gcmFadeIn .3s ease}
.gcm-relay-panel{display:none;flex:1;overflow-y:auto;padding:12px 14px;flex-direction:column;gap:10px}.gcm-relay-panel.open{display:flex}
.gcm-files-panel{display:none;flex:1;overflow-y:auto;padding:8px 10px;flex-direction:column;gap:2px}.gcm-files-panel.open{display:flex}
.gcm-files-header{display:flex;align-items:center;justify-content:space-between;padding:4px 6px;font-size:.7rem;color:rgba(255,255,255,.5)}
.gcm-files-header button{background:none;border:none;color:rgba(255,255,255,.4);font-size:.65rem;cursor:pointer;padding:2px 6px;border-radius:4px}
.gcm-files-header button:hover{color:#fff;background:rgba(255,255,255,.08)}
.gcm-file-item{display:flex;align-items:center;gap:6px;padding:4px 8px;border-radius:6px;font-size:.72rem;cursor:pointer;transition:background .15s;color:rgba(255,255,255,.8);white-space:nowrap;overflow:hidden}
.gcm-file-item:hover{background:rgba(167,139,250,.12)}
.gcm-file-icon{font-size:.65rem;flex-shrink:0;width:16px;text-align:center}
.gcm-file-name{flex:1;overflow:hidden;text-overflow:ellipsis}
.gcm-file-name .gcm-fdir{color:rgba(255,255,255,.35);font-size:.62rem}
.gcm-file-badge{font-size:.55rem;background:rgba(167,139,250,.2);color:#a78bfa;padding:1px 5px;border-radius:8px;flex-shrink:0}
.gcm-files-empty{text-align:center;padding:30px 10px;color:rgba(255,255,255,.3);font-size:.76rem}
.gcm-relay-section{margin-bottom:6px}.gcm-relay-section h4{font-size:.65rem;text-transform:uppercase;letter-spacing:.6px;color:rgba(255,255,255,0.25);margin:0 0 6px;font-weight:600}
.gcm-relay-modes{display:flex;gap:5px}.gcm-mode-pill{flex:1;padding:8px 4px;text-align:center;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);border-radius:10px;color:rgba(255,255,255,0.35);font-size:.65rem;cursor:pointer;transition:all .2s}
.gcm-mode-pill:hover{background:rgba(125,0,255,0.1);color:#a78bfa;border-color:rgba(125,0,255,0.25)}
.gcm-mode-pill.active{background:linear-gradient(135deg,rgba(125,0,255,0.2),rgba(0,212,255,0.12));color:#fff;border-color:rgba(125,0,255,0.4)}
.gcm-mode-pill .mp-i{font-size:1rem;display:block;margin-bottom:1px}.gcm-mode-pill .mp-n{font-size:.62rem;font-weight:700}.gcm-mode-pill .mp-d{font-size:.5rem;color:rgba(255,255,255,0.25);margin-top:1px}
.gcm-turns-row{display:flex;align-items:center;justify-content:space-between;background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);border-radius:10px;padding:8px 12px}.gcm-turns-row span{font-size:.75rem;color:rgba(255,255,255,0.5)}
.gcm-turns-ctrl{display:flex;align-items:center;gap:6px}.gcm-turns-ctrl button{width:22px;height:22px;border-radius:50%;border:1px solid rgba(255,255,255,0.12);background:rgba(255,255,255,0.05);color:#fff;font-size:.75rem;cursor:pointer;display:flex;align-items:center;justify-content:center}.gcm-turns-ctrl button:hover{background:rgba(125,0,255,0.2);border-color:#7c3aed}
.gcm-turns-val{font-size:.85rem;font-weight:700;color:#a78bfa;min-width:14px;text-align:center}
.gcm-thread-box{flex:1;overflow-y:auto;border:1px solid rgba(255,255,255,0.05);border-radius:10px;background:rgba(0,0,0,0.12);padding:6px;min-height:80px;max-height:200px}
.gcm-thread-empty{text-align:center;color:rgba(255,255,255,0.15);font-size:.68rem;padding:16px 8px;font-style:italic}
.gcm-thread-item{padding:5px 8px;border-radius:6px;margin-bottom:3px;font-size:.68rem;line-height:1.35;word-break:break-word}
.gcm-thread-item.t-user{background:rgba(125,0,255,0.08);border-left:2px solid #7c3aed}
.gcm-thread-item.t-widget{background:rgba(167,139,250,0.06);border-left:2px solid #a78bfa}
.gcm-thread-item.t-ide{background:rgba(16,185,129,0.06);border-left:2px solid #10b981}
.gcm-thread-item .t-role{font-size:.52rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;margin-bottom:1px}
.gcm-thread-item.t-user .t-role{color:#a78bfa}
.gcm-thread-item.t-widget .t-role{color:#c084fc}
.gcm-thread-item.t-ide .t-role{color:#10b981}
.gcm-thread-item .t-text{color:rgba(255,255,255,0.6)}.gcm-thread-item .t-meta{font-size:.48rem;color:rgba(255,255,255,0.15);float:right}
.gcm-relay-stats{display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:4px}.gcm-rstat{background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.05);border-radius:6px;padding:6px;text-align:center}.gcm-rstat .rs-v{font-size:.85rem;font-weight:700;color:#a78bfa}
.gcm-rstat .rs-l{font-size:.48rem;color:rgba(255,255,255,0.25);text-transform:uppercase;letter-spacing:.3px}
.gcm-turn-badge{display:inline-block;font-size:.48rem;font-weight:700;padding:1px 4px;border-radius:4px;margin-left:3px;vertical-align:middle}.gcm-turn-badge.p1{background:rgba(125,0,255,0.18);color:#a78bfa}.gcm-turn-badge.p2{background:rgba(0,212,255,0.18);color:#67e8f9}.gcm-turn-badge.p3{background:rgba(16,185,129,0.18);color:#6ee7b7}
.gcm-consensus-bar{display:flex;align-items:center;gap:5px;padding:5px 10px;border-radius:8px;font-size:.68rem;margin:4px 0;animation:gcmFadeIn .3s ease}
.gcm-consensus-bar.thinking{background:rgba(251,191,36,0.06);border:1px solid rgba(251,191,36,0.12);color:#fbbf24}
.gcm-consensus-bar.agreed{background:rgba(16,185,129,0.06);border:1px solid rgba(16,185,129,0.12);color:#10b981}.gcm-consensus-bar.diverged{background:rgba(239,68,68,0.06);border:1px solid rgba(239,68,68,0.12);color:#f87171}
.gcm-relay-active-bar{display:flex;align-items:center;gap:6px;padding:4px 10px;background:linear-gradient(90deg,rgba(125,0,255,0.08),rgba(0,212,255,0.05));border:1px solid rgba(125,0,255,0.15);border-radius:8px;font-size:.65rem;color:#c4b5fd;margin:3px 0;animation:gcmFadeIn .3s ease}
.gcm-relay-active-bar .rab-dot{width:6px;height:6px;border-radius:50%;background:#10b981;animation:gcmPulse 1.5s ease-in-out infinite}
.gcm-clear-thread{background:none;border:1px solid rgba(255,255,255,0.08);color:rgba(255,255,255,0.3);font-size:.6rem;padding:4px 10px;border-radius:6px;cursor:pointer;transition:all .2s;margin-top:4px;width:100%}.gcm-clear-thread:hover{background:rgba(239,68,68,0.1);border-color:rgba(239,68,68,0.2);color:#f87171}
/* ═══ Autopilot Panel ═══ */
.gcm-autopilot-overlay{display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.85);z-index:100001;flex-direction:column;animation:gcmFadeIn .3s ease}
.gcm-autopilot-overlay.open{display:flex}
.gcm-ap-header{display:flex;align-items:center;justify-content:space-between;padding:10px 16px;background:rgba(13,13,24,0.95);border-bottom:1px solid rgba(125,0,255,0.25)}
.gcm-ap-header-left{display:flex;align-items:center;gap:10px}
.gcm-ap-title{font-size:.9rem;font-weight:700;color:#fff}
.gcm-ap-status{font-size:.65rem;padding:3px 8px;border-radius:10px;font-weight:600;background:rgba(16,185,129,0.2);color:#10b981}
.gcm-ap-status.idle{background:rgba(255,255,255,0.08);color:rgba(255,255,255,0.4)}
.gcm-ap-status.acting{background:rgba(251,191,36,0.2);color:#fbbf24;animation:gcmThink 1.5s linear infinite}
.gcm-ap-status.paused{background:rgba(239,68,68,0.2);color:#f87171;animation:gcmPulse 1.5s ease-in-out infinite}
.gcm-ap-url{font-size:.7rem;color:rgba(255,255,255,0.4);max-width:400px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.gcm-ap-header-right{display:flex;align-items:center;gap:8px}
.gcm-ap-close{background:none;border:1px solid rgba(255,255,255,0.15);color:rgba(255,255,255,0.6);padding:5px 12px;border-radius:8px;cursor:pointer;font-size:.75rem;font-weight:600;transition:all .2s}
.gcm-ap-close:hover{background:rgba(239,68,68,0.15);border-color:rgba(239,68,68,0.3);color:#f87171}
.gcm-ap-guardrail{font-size:.6rem;color:rgba(255,255,255,0.3);padding:2px 8px;border-radius:6px;background:rgba(255,255,255,0.04)}
.gcm-ap-tabs-bar{display:flex;gap:2px;padding:4px 16px;background:rgba(13,13,24,0.9);border-bottom:1px solid rgba(255,255,255,0.06);overflow-x:auto;scrollbar-width:thin}
.gcm-ap-tab{padding:4px 10px;font-size:.65rem;color:rgba(255,255,255,0.4);border-radius:6px 6px 0 0;cursor:pointer;white-space:nowrap;max-width:180px;overflow:hidden;text-overflow:ellipsis;background:rgba(255,255,255,0.03);border:1px solid transparent;border-bottom:none;transition:all .2s}
.gcm-ap-tab.active{background:rgba(125,0,255,0.12);color:#c4b5fd;border-color:rgba(125,0,255,0.2)}
.gcm-ap-tab:hover{background:rgba(255,255,255,0.06)}
.gcm-ap-body{flex:1;display:flex;overflow:hidden}
.gcm-ap-viewport{flex:1;display:flex;align-items:center;justify-content:center;background:#111;position:relative;overflow:hidden}
.gcm-ap-viewport img{max-width:100%;max-height:100%;object-fit:contain;border-radius:2px}
.gcm-ap-viewport .gcm-ap-placeholder{color:rgba(255,255,255,0.2);font-size:1.2rem;text-align:center}
.gcm-ap-cursor{position:absolute;width:16px;height:16px;border-radius:50%;background:rgba(239,68,68,0.7);border:2px solid #fff;transform:translate(-50%,-50%);pointer-events:none;z-index:5;transition:left .15s ease,top .15s ease;box-shadow:0 0 12px rgba(239,68,68,0.5)}
.gcm-ap-cursor.hidden{display:none}
.gcm-ap-viewport-controls{position:absolute;bottom:10px;left:50%;transform:translateX(-50%);display:flex;gap:4px;z-index:6}
.gcm-ap-vp-pill{padding:3px 10px;font-size:.6rem;border-radius:10px;border:1px solid rgba(255,255,255,0.1);color:rgba(255,255,255,0.4);background:rgba(0,0,0,0.6);cursor:pointer;transition:all .2s}
.gcm-ap-vp-pill.active{background:rgba(125,0,255,0.3);color:#c4b5fd;border-color:rgba(125,0,255,0.3)}
.gcm-ap-vp-pill:hover{background:rgba(125,0,255,0.15);color:#e9d5ff}
.gcm-ap-approval{position:absolute;bottom:20px;left:50%;transform:translateX(-50%);display:none;gap:8px;padding:12px 20px;background:rgba(13,13,24,0.95);border:1px solid rgba(251,191,36,0.3);border-radius:12px;z-index:7;max-width:500px;align-items:center}
.gcm-ap-approval.visible{display:flex}
.gcm-ap-approval-text{font-size:.75rem;color:#fbbf24;flex:1}
.gcm-ap-approval-btn{padding:6px 16px;border-radius:8px;font-size:.7rem;font-weight:600;cursor:pointer;border:none;transition:all .2s}
.gcm-ap-approve-btn{background:rgba(16,185,129,0.3);color:#10b981}.gcm-ap-approve-btn:hover{background:rgba(16,185,129,0.5)}
.gcm-ap-reject-btn{background:rgba(239,68,68,0.3);color:#f87171}.gcm-ap-reject-btn:hover{background:rgba(239,68,68,0.5)}
.gcm-ap-sidebar{width:280px;background:rgba(13,13,24,0.95);border-left:1px solid rgba(125,0,255,0.15);display:flex;flex-direction:column;overflow:hidden}
.gcm-ap-sidebar h4{font-size:.65rem;text-transform:uppercase;letter-spacing:.8px;color:rgba(255,255,255,0.3);margin:0;padding:10px 12px 6px;font-weight:600;cursor:pointer;user-select:none}
.gcm-ap-sidebar h4:hover{color:rgba(255,255,255,0.5)}
.gcm-ap-actions{flex:1;overflow-y:auto;padding:0 12px 12px;scrollbar-width:thin;scrollbar-color:rgba(255,255,255,0.08) transparent}
.gcm-ap-action-item{display:flex;align-items:flex-start;gap:8px;padding:8px 10px;border-radius:8px;margin-bottom:4px;font-size:.72rem;line-height:1.4;animation:gcmFadeIn .2s ease}
.gcm-ap-action-item.current{background:rgba(125,0,255,0.12);border:1px solid rgba(125,0,255,0.2);color:#c4b5fd}
.gcm-ap-action-item.done{background:rgba(16,185,129,0.06);color:rgba(255,255,255,0.5)}
.gcm-ap-action-item .ap-step{font-weight:700;font-size:.6rem;min-width:20px;text-align:center;padding:2px 4px;border-radius:4px;background:rgba(255,255,255,0.06)}
.gcm-ap-action-item.current .ap-step{background:rgba(125,0,255,0.3);color:#e9d5ff}
.gcm-ap-action-item.done .ap-step{background:rgba(16,185,129,0.15);color:#10b981}
.gcm-ap-footer{padding:8px 12px;border-top:1px solid rgba(255,255,255,0.06);display:flex;align-items:center;gap:6px}
.gcm-ap-step-count{font-size:.65rem;color:rgba(255,255,255,0.3)}
/* v8.0: Confidence bar */
.gcm-ap-confidence-bar{position:absolute;top:0;left:0;right:0;height:3px;z-index:8;transition:background .4s}
.gcm-ap-confidence-bar.high{background:linear-gradient(90deg,#10b981,#34d399)}
.gcm-ap-confidence-bar.medium{background:linear-gradient(90deg,#fbbf24,#f59e0b)}
.gcm-ap-confidence-bar.low{background:linear-gradient(90deg,#ef4444,#f87171)}
/* v8.0: Sentiment indicator */
.gcm-ap-sentiment{font-size:.6rem;padding:2px 8px;border-radius:8px;font-weight:600;transition:all .3s}
.gcm-ap-sentiment.progressing{background:rgba(16,185,129,0.2);color:#10b981}
.gcm-ap-sentiment.neutral{background:rgba(255,255,255,0.06);color:rgba(255,255,255,0.4)}
.gcm-ap-sentiment.stuck{background:rgba(251,191,36,0.2);color:#fbbf24}
.gcm-ap-sentiment.failing{background:rgba(239,68,68,0.2);color:#f87171}
/* v8.0: Undo button */
.gcm-ap-undo-btn{background:rgba(125,0,255,0.2);border:1px solid rgba(125,0,255,0.3);color:#c4b5fd;padding:4px 12px;border-radius:8px;font-size:.65rem;font-weight:600;cursor:pointer;transition:all .2s;display:none}
.gcm-ap-undo-btn.available{display:inline-block}
.gcm-ap-undo-btn:hover{background:rgba(125,0,255,0.35)}
/* v8.0: Celebration overlay */
.gcm-ap-celebration{position:absolute;top:0;left:0;right:0;bottom:0;pointer-events:none;z-index:10;display:none}
.gcm-ap-celebration.active{display:block;animation:gcmCelebrate 2s ease-out forwards}
@keyframes gcmCelebrate{0%{opacity:1}100%{opacity:0}}
.gcm-ap-confetti{position:absolute;width:8px;height:8px;border-radius:2px;animation:gcmConfettiFall 2s ease-out forwards}
@keyframes gcmConfettiFall{0%{transform:translateY(-20px) rotate(0deg);opacity:1}100%{transform:translateY(100vh) rotate(720deg);opacity:0}}
/* v8.0: Frustration alert */
.gcm-ap-frustration{display:none;position:absolute;top:10px;right:10px;padding:8px 14px;background:rgba(239,68,68,0.9);color:#fff;border-radius:10px;font-size:.7rem;font-weight:600;z-index:9;animation:gcmPulse 1s ease-in-out 3}
.gcm-ap-frustration.visible{display:block}
/* v8.0: Annotation dots */
.gcm-ap-annotation{position:absolute;width:20px;height:20px;border-radius:50%;border:2px solid;pointer-events:auto;z-index:6;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.5rem;font-weight:700;color:#fff;transform:translate(-50%,-50%);transition:transform .15s}
.gcm-ap-annotation:hover{transform:translate(-50%,-50%) scale(1.4)}
.gcm-ap-annotation-tip{position:absolute;bottom:100%;left:50%;transform:translateX(-50%);background:rgba(0,0,0,0.9);color:#fff;padding:2px 6px;border-radius:4px;font-size:.55rem;white-space:nowrap;display:none;margin-bottom:4px}
.gcm-ap-annotation:hover .gcm-ap-annotation-tip{display:block}
/* v8.0: Narration (ARIA live) */
.gcm-ap-narration{position:absolute;top:-9999px;left:-9999px}
/* v8.0: Batch status bar */
.gcm-ap-batch-bar{display:none;padding:4px 16px;background:rgba(125,0,255,0.1);border-bottom:1px solid rgba(125,0,255,0.15);font-size:.6rem;color:#c4b5fd;align-items:center;gap:8px}
.gcm-ap-batch-bar.active{display:flex}
.gcm-ap-batch-progress{flex:1;height:3px;background:rgba(255,255,255,0.08);border-radius:2px;overflow:hidden}
.gcm-ap-batch-fill{height:100%;background:#7d00ff;transition:width .3s}
/* v8.0: Spectator badge */
.gcm-ap-spectators{font-size:.55rem;padding:2px 6px;border-radius:8px;background:rgba(125,0,255,0.15);color:#c4b5fd;display:none}
.gcm-ap-spectators.active{display:inline-block}
/* v8.0: High-contrast support */
@media(prefers-contrast:more){.gcm-autopilot-overlay{background:#000}.gcm-ap-header{background:#000;border-color:#fff}.gcm-ap-action-item{border:1px solid rgba(255,255,255,0.3)}.gcm-ap-cursor{background:#ff0;border-color:#000}.gcm-ap-vp-pill{border-color:#fff;color:#fff}}
@media(max-width:768px){.gcm-ap-sidebar{width:200px}}
@media(max-width:500px){.gcm-ap-sidebar{display:none}}
</style>
<div id="gcmA">
  <div class="gcm-panel" id="gcmPanel">
    <div class="gcm-head">
      <div class="gcm-head-left">
        <span class="gcm-head-title">🎩 Alfred AI</span>
        <span id="gcmBuildStamp" style="font-size:10px;color:rgba(255,255,255,.55);border:1px solid rgba(255,255,255,.2);border-radius:999px;padding:1px 6px;">BUILD 2026-03-23-2</span>
        <span class="gcm-head-status offline" id="gcmConnStatus">Offline</span>
        <span class="gcm-bridge-indicator inactive" id="gcmBridgeStatus" title="Alfred-to-Alfred Bridge"><span class="gcm-bridge-dot inactive" id="gcmBridgeDot"></span> Bridge</span>
        <button class="gcm-relay-btn" id="gcmRelayToggle" title="Auto-Relay: Both Alfreds collaborate on every message">🔄 Relay</button>
      </div>
      <div class="gcm-head-btns">
        <button id="gcmSettingsToggle" title="Settings">⚙</button>
        <button id="gcmPanelClose" title="Close">✕</button>
      </div>
    </div>
    <div class="gcm-tabs" id="gcmTabBar">
      <button class="gcm-tab active" data-tab="chat">💬 Chat</button>
      <button class="gcm-tab" data-tab="relay">🔄 Relay</button>
      <button class="gcm-tab" data-tab="files">📂 Files <span class="gcm-file-badge" id="gcmFileCount" style="display:none">0</span></button>
      <button class="gcm-tab" data-tab="settings">⚙ Settings</button>
    </div>
    <div class="gcm-chat" id="gcmChat">
      <div class="gcm-compact-banner" id="gcmCompactBanner" style="display:none">
        <span class="gcm-compact-icon">📦</span>
        <span class="gcm-compact-text">Context <span id="gcmCompactPct">75</span>% full</span>
        <button class="gcm-compact-btn" id="gcmCompactBtn" title="Compact conversation to free up context space">Compact</button>
      </div>
      <div class="gcm-context-bar" id="gcmContextBar">
        <span>📊</span>
        <div class="gcm-ctx-track"><div class="gcm-ctx-fill" id="gcmCtxFill"></div></div>
        <span class="gcm-ctx-label" id="gcmCtxLabel">0%</span>
      </div>
      <div class="gcm-reconnect-banner" id="gcmReconnectBanner">
        <div class="gcm-reconnect-spinner"></div>
        <span id="gcmReconnectText">Reconnecting...</span>
      </div>
      <div class="gcm-empty" id="gcmEmpty">
        <div class="gcm-empty-icon">🎩</div>
        <div class="gcm-empty-text">Hi! I'm Alfred Coder, your AI coding assistant.<br>Ask me anything or use the mic to talk.</div>
        <div class="gcm-empty-hint">400+ tools · 16+ engines · voice + text</div>
      </div>
    </div>
    <div class="gcm-settings" id="gcmSettingsPanel">
      <div class="gcm-settings-section">
        <h4>Quick Mode</h4>
        <div class="gcm-model-pills" id="gcmModePills">
          <button class="gcm-pill active" data-mode="auto">⚡ Auto</button>
          <button class="gcm-pill" data-mode="sonnet">🟣 Sonnet</button>
          <button class="gcm-pill" data-mode="opus">💎 Opus</button>
          <button class="gcm-pill" data-mode="haiku">🍃 Haiku</button>
          <button class="gcm-pill" data-mode="turbo">🚀 Turbo</button>
        </div>
      </div>
      <div class="gcm-settings-section">
        <h4>Token Usage</h4>
        <div class="gcm-token-bar"><div class="gcm-token-fill" id="gcmTokenFill" style="width:0%"></div></div>
        <div class="gcm-token-info"><span id="gcmTokenUsed">-</span><span id="gcmTokenTotal">-</span></div>
      </div>
      <div class="gcm-settings-section">
        <h4>Voice Playback</h4>
        <div class="gcm-tts-toggle">
          <span class="gcm-tts-label">Play Alfred's voice responses</span>
          <div class="gcm-tts-switch" id="gcmTtsToggle"></div>
        </div>
      </div>
      <div class="gcm-settings-section">
        <h4>AI Models</h4>
        <div class="gcm-model-grid" id="gcmModelGrid"></div>
      </div>
      <div class="gcm-settings-section">
        <h4>Full Voice Experience</h4>
        <a href="https://gositeme.com/alfred-voice-live/" target="_blank" class="gcm-voice-link">
          <span class="gcm-vl-icon">🎙️</span>
          <div><div class="gcm-vl-text">Open Alfred Voice Studio</div><div class="gcm-vl-sub">26 agents · 3 TTS engines · live mode · full UI</div></div>
        </a>
      </div>
    </div>
    <div class="gcm-files-panel" id="gcmFilesPanel">
      <div class="gcm-files-header">
        <span id="gcmFilesTitle">Files Touched (0)</span>
        <button id="gcmFilesClear" title="Clear list">🗑️ Clear</button>
      </div>
      <div id="gcmFilesList">
        <div class="gcm-files-empty" id="gcmFilesEmpty">📂 Files read by Alfred will appear here.<br><span style="font-size:.62rem">Click any file to open it in the editor.</span></div>
      </div>
    </div>
    <div class="gcm-relay-panel" id="gcmRelayPanel">
      <div class="gcm-relay-section">
        <h4>Relay Mode</h4>
        <div class="gcm-relay-modes">
          <button class="gcm-mode-pill active" data-mode="collab"><span class="mp-i">🤝</span><span class="mp-n">Collab</span><span class="mp-d">Both contribute</span></button>
          <button class="gcm-mode-pill" data-mode="consensus"><span class="mp-i">✅</span><span class="mp-n">Consensus</span><span class="mp-d">Must agree</span></button>
          <button class="gcm-mode-pill" data-mode="delegate"><span class="mp-i">🎯</span><span class="mp-n">Delegate</span><span class="mp-d">Auto-route</span></button>
        </div>
      </div>
      <div class="gcm-relay-section">
        <h4>Max Passes</h4>
        <div class="gcm-turns-row">
          <span>Relay depth</span>
          <div class="gcm-turns-ctrl">
            <button id="gcmTurnsMinus">−</button>
            <span class="gcm-turns-val" id="gcmTurnsVal">2</span>
            <button id="gcmTurnsPlus">+</button>
          </div>
        </div>
      </div>
      <div class="gcm-relay-section">
        <h4>Stats</h4>
        <div class="gcm-relay-stats">
          <div class="gcm-rstat"><div class="rs-v" id="gcmStatRelays">0</div><div class="rs-l">Relays</div></div>
          <div class="gcm-rstat"><div class="rs-v" id="gcmStatTurns">0</div><div class="rs-l">Passes</div></div>
          <div class="gcm-rstat"><div class="rs-v" id="gcmStatConsensus">0</div><div class="rs-l">Agreed</div></div>
          <div class="gcm-rstat"><div class="rs-v" id="gcmStatTime">0s</div><div class="rs-l">Total Time</div></div>
        </div>
      </div>
      <div class="gcm-relay-section" style="flex:1;display:flex;flex-direction:column">
        <h4>Conversation Thread</h4>
        <div class="gcm-thread-box" id="gcmThreadBox">
          <div class="gcm-thread-empty">No relay conversations yet.<br>Toggle Relay ON and ask a question.</div>
        </div>
        <button class="gcm-clear-thread" id="gcmClearThread">🗑️ Clear Thread</button>
      </div>
    </div>
    <div class="gcm-input-area" id="gcmInputArea">
      <button class="gcm-mic-btn" id="gcmMicBtn" title="Tap to record voice">🎙️</button>
      <input type="text" id="gcmInput" inputmode="text" enterkeyhint="send" placeholder="Ask Alfred anything…" autocomplete="off">
      <div style="position:relative">
        <span class="gcm-model-badge" id="gcmModelBadge" title="Models & smart routing">🤖 Auto</span>
        <div class="gcm-model-popup" id="gcmModelPopup"></div>
      </div>
      <button type="button" class="gcm-send-btn gcm-send-dim" id="gcmSendBtn" title="Send">↑</button>
      <button class="gcm-stop-btn" id="gcmStopBtn" title="Cancel response">■</button>
    </div>
  </div>
  <button class="gcm-orb" id="gcmOrb" title="Alfred AI — Chat, Voice & 400+ tools">
    <span class="gcm-orb-pulse"></span>
    <span class="gcm-orb-ring"></span>
    <span class="gcm-orb-icon">🎩</span>
    <span class="gcm-orb-badge">AI</span>
  </button>
</div>
<!-- Autopilot Live Browser Panel -->
<div class="gcm-autopilot-overlay" id="gcmAutopilotOverlay">
  <div class="gcm-ap-header">
    <div class="gcm-ap-header-left">
      <span class="gcm-ap-title">🚀 Alfred Autopilot</span>
      <span class="gcm-ap-status idle" id="gcmApStatus">Idle</span>
      <span class="gcm-ap-url" id="gcmApUrl"></span>
    </div>
    <div class="gcm-ap-header-right">
      <span class="gcm-ap-sentiment neutral" id="gcmApSentiment">Neutral</span>
      <span class="gcm-ap-spectators" id="gcmApSpectators">👁 1</span>
      <button class="gcm-ap-undo-btn" id="gcmApUndoBtn" title="Undo last action (Ctrl+Z)">↩ Undo</button>
      <span class="gcm-ap-guardrail" id="gcmApGuardrail"></span>
      <span class="gcm-ap-step-count" id="gcmApStepCount">0 steps</span>
      <button class="gcm-ap-close" id="gcmApClose">✕ Close</button>
    </div>
  </div>
  <div class="gcm-ap-tabs-bar" id="gcmApTabsBar" style="display:none"></div>
  <div class="gcm-ap-batch-bar" id="gcmApBatchBar"><span id="gcmApBatchText">Batch: 0/0</span><div class="gcm-ap-batch-progress"><div class="gcm-ap-batch-fill" id="gcmApBatchFill" style="width:0%"></div></div></div>
  <div class="gcm-ap-body">
    <div class="gcm-ap-viewport" id="gcmApViewport">
      <div class="gcm-ap-confidence-bar high" id="gcmApConfidence"></div>
      <div class="gcm-ap-placeholder" id="gcmApPlaceholder">🖥️ Alfred will open a live browser here…<br><span style="font-size:.75rem;opacity:.5">Ask Alfred to browse the web and this panel opens automatically</span></div>
      <img id="gcmApScreen" style="display:none" alt="Live browser view">
      <div class="gcm-ap-cursor hidden" id="gcmApCursor"></div>
      <div class="gcm-ap-viewport-controls" id="gcmApVpControls">
        <span class="gcm-ap-vp-pill active" data-vp="desktop">Desktop</span>
        <span class="gcm-ap-vp-pill" data-vp="tablet">Tablet</span>
        <span class="gcm-ap-vp-pill" data-vp="mobile">Mobile</span>
      </div>
      <div class="gcm-ap-approval" id="gcmApApproval">
        <span class="gcm-ap-approval-text" id="gcmApApprovalText"></span>
        <button class="gcm-ap-approval-btn gcm-ap-approve-btn" id="gcmApApproveBtn">✓ Approve</button>
        <button class="gcm-ap-approval-btn gcm-ap-reject-btn" id="gcmApRejectBtn">✕ Reject</button>
      </div>
      <div class="gcm-ap-celebration" id="gcmApCelebration"></div>
      <div class="gcm-ap-frustration" id="gcmApFrustration"></div>
      <div id="gcmApAnnotations" style="position:absolute;top:0;left:0;right:0;bottom:0;pointer-events:none;z-index:6"></div>
    </div>
    <div class="gcm-ap-sidebar">
      <h4>Live Actions</h4>
      <div class="gcm-ap-actions" id="gcmApActions"></div>
      <h4 id="gcmApNetworkToggle" style="cursor:pointer">▶ Network <span style="font-weight:400;opacity:.5" id="gcmApNetCount">0</span></h4>
      <div id="gcmApNetworkPanel" style="display:none;max-height:200px;overflow-y:auto;padding:0 12px 8px;font-size:.62rem;color:rgba(255,255,255,0.4)"></div>
      <div class="gcm-ap-footer">
        <span class="gcm-ap-step-count" id="gcmApFooterSteps">Waiting for session…</span>
      </div>
    </div>
  </div>
  <div class="gcm-ap-narration" id="gcmApNarration" aria-live="polite" aria-atomic="true" role="status"></div>
</div>
<script>
(function(){
console.info('[gcm-widget] BUILD 2026-03-23-2 loaded');
var RELAY_URL='/middleware/api/voice-relay';
var AUTH_TOKEN=${JSON.stringify(sessionToken)};
var AUTH_USER=${JSON.stringify(daUsername)};
var IS_AUTH=${isAuth};
var AUTH_HEADERS=AUTH_TOKEN?{'Content-Type':'application/json','Authorization':'Bearer '+AUTH_TOKEN}:{'Content-Type':'application/json'};
var sessionId=null,isConnected=false,pollActive=false,isRecording=false,ttsEnabled=false,ttsCtx=null;
var mediaRecorder=null,audioChunks=[],micStream=null;
var panel=document.getElementById('gcmPanel');
var orb=document.getElementById('gcmOrb');
var chat=document.getElementById('gcmChat');
var emptyEl=document.getElementById('gcmEmpty');
var input=document.getElementById('gcmInput');
var sendBtn=document.getElementById('gcmSendBtn');
var stopBtn=document.getElementById('gcmStopBtn');
var micBtn=document.getElementById('gcmMicBtn');
var connStatus=document.getElementById('gcmConnStatus');
var settingsPanel=document.getElementById('gcmSettingsPanel');
var typingEl=null;
var reconnectCount=0,MAX_RECONNECT=5,reconnectTimer=null,userClosed=false;
var autoRelayEnabled=false,lastUserMsg='',relayPending=false,relaySeqId=0,relayMsgIds=new Set();
var relayMode='collab',maxRelayTurns=2,relayThread=[],relayStats={relays:0,turns:0,consensusHits:0,totalTime:0};
var MAX_THREAD_SIZE=50,RELAY_COOLDOWN=3000,lastRelayEnd=0;
var LS_KEY='gcm_relay_v4';
try{var saved=JSON.parse(localStorage.getItem(LS_KEY));if(saved){relayThread=saved.thread||[];relayStats=saved.stats||relayStats;relayMode=saved.mode||relayMode;maxRelayTurns=saved.turns||maxRelayTurns;}}catch(e){}

// ── Panel toggle ──
orb.addEventListener('click',function(){
  if(panel.classList.contains('open')){panel.classList.remove('open');return;}
  panel.classList.add('open');
  if(!isConnected&&!sessionId)connectRelay();
  setTimeout(function(){if(input){input.focus();syncSendDim();}},100);
  loadSettings();
});
document.getElementById('gcmPanelClose').addEventListener('click',function(){panel.classList.remove('open');});

// ── Auto-Relay toggle ──
document.getElementById('gcmRelayToggle').addEventListener('click',function(){
  autoRelayEnabled=!autoRelayEnabled;
  this.classList.toggle('active',autoRelayEnabled);
  this.textContent=autoRelayEnabled?'\\ud83d\\udd04 ON':'\\ud83d\\udd04 Relay';
  addMsg('system',autoRelayEnabled?'\\ud83d\\udd04 Relay ON ('+relayMode+' \\u00b7 '+maxRelayTurns+' passes) \\u2014 both Alfreds collaborate':'\\ud83d\\udd04 Relay OFF');
  // When relay is turned ON, try to open AI Chat panel if not already open
  if(autoRelayEnabled&&!findChatEditor()){
    var chatTab=document.querySelector('[id*="chat-view"]')||document.querySelector('[id*="chat-input"]')||document.querySelector('[id*="ai-chat"]')||document.querySelector('.p-TabBar-tab[title*="Chat"]')||document.querySelector('.theia-TabBar-tab[title*="Chat"]')||document.querySelector('.p-TabBar-tab[title*="AI"]')||document.querySelector('.theia-TabBar-tab[title*="AI"]');
    if(chatTab){chatTab.click();addMsg('system','\\ud83d\\udce1 Opening AI Chat panel for Alfred Coder...');}
    else{addMsg('system','\\u26a0\\ufe0f AI Chat not found \\u2014 press Ctrl+Alt+I to open it, then relay will auto-connect');}
  }
});

// ── Tabs ──
var tabs=document.querySelectorAll('.gcm-tab');
tabs.forEach(function(t){t.addEventListener('click',function(){
  tabs.forEach(function(x){x.classList.remove('active');});t.classList.add('active');
  var which=t.dataset.tab;
  chat.style.display=which==='chat'?'flex':'none';
  document.getElementById('gcmInputArea').style.display=which==='chat'?'flex':'none';
  settingsPanel.classList.toggle('open',which==='settings');
  document.getElementById('gcmRelayPanel').classList.toggle('open',which==='relay');
  document.getElementById('gcmFilesPanel').classList.toggle('open',which==='files');
  if(which==='settings')loadSettings();
  if(which==='chat')setTimeout(syncSendDim,0);
});});
document.getElementById('gcmSettingsToggle').addEventListener('click',function(){
  var st=document.querySelector('[data-tab="settings"]');
  st.click();
});

// ── Input / Send (never use disabled on Send — some browsers/IME skip input events → button stays dead)
function syncSendDim(){
  if(!sendBtn||!input)return;
  sendBtn.classList.toggle('gcm-send-dim',!String(input.value||'').trim());
}
if(input&&sendBtn){
  ['input','keyup','paste','cut','compositionend','change'].forEach(function(ev){input.addEventListener(ev,syncSendDim);});
  input.addEventListener('keydown',function(e){if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();sendText();}});
  sendBtn.addEventListener('click',function(e){
    e.preventDefault();
    e.stopPropagation();
    // Hard debug: make it obvious whether the click handler fires.
    try {
      addMsg('system','🧪 DEBUG: send click fired (txtLen=' + String(input && input.value ? input.value.length : 0) + ')');
      sendText();
    } catch (err) {
      addMsg('system','⚠️ DEBUG: send handler error: ' + (err && err.message ? err.message : String(err)));
    }
  });
  sendBtn.addEventListener('pointerdown',function(e){
    e.stopPropagation();
    try {
      addMsg('system','🧷 DEBUG: send pointerdown fired (txtLen=' + String(input && input.value ? input.value.length : 0) + ')');
    } catch (_) {}
  });
}
stopBtn.addEventListener('click',function(){
  // Cancel in-flight response — send steering abort to voice relay
  if(isConnected&&sessionId){
    relaySend({type:'steering',action:'cancel',text:'User cancelled response'});
  }
  hideTyping();setOrb('idle');
  addMsg('system','🛑 Cancelled');
  stopBtn.style.display='none';sendBtn.style.display='flex';
});

function sendText(){
  if(!input||!sendBtn)return;
  var txt=String(input.value||'').trim();
  if(!txt){
    syncSendDim();
    // Avoid silent no-op when user types in the editor/pager instead of the widget input.
    addMsg('system','⚠️ Type in the Alfred input box (bottom-right), then press Enter or Send.');
    return;
  }
  input.value='';syncSendDim();
  lastUserMsg=txt;
  cancelAllTTS(); // Interrupt any speaking when user sends a new message
  if(window.__alfredBridge&&window.__alfredBridge.cancelRelay)window.__alfredBridge.cancelRelay();
  addMsg('user',txt);hideEmpty();
  function unstickSend(){syncSendDim();hideTyping();setOrb('idle');stopBtn.style.display='none';sendBtn.style.display='flex';}
  function doSend(){
    relaySend({type:'text',text:txt,ttsEnabled:ttsEnabled}).then(function(ok){if(!ok)unstickSend();});
    setOrb('processing');showTyping();
  }
  if(!isConnected||!sessionId){
    addMsg('system','Connecting to Alfred…');
    connectRelay(function(ok){
      if(!ok){unstickSend();return;}
      doSend();
    });
    return;
  }
  doSend();
}
function relaySend(msg){
  if(!sessionId||!isConnected){
    addMsg('system','⚠️ Alfred not connected yet — wait for “Connected” or press Ctrl+Alt+I to reopen the chat panel.');
    return Promise.resolve(false);
  }
  return fetch(RELAY_URL+'/send',{method:'POST',headers:AUTH_HEADERS,body:JSON.stringify({session_id:sessionId,message:msg}),credentials:'include'})
    .then(function(r){
      return r.json().then(function(d){return{ok:r.ok,status:r.status,data:d};}).catch(function(){return{ok:r.ok,status:r.status,data:null};});
    })
    .then(function(res){
      if(!res.ok||!res.data||res.data.ok===false){
        var err=(res.data&&res.data.error)||('HTTP '+(res.status||'?'));
        addMsg('system','⚠️ '+err);
        return false;
      }
      return true;
    })
    .catch(function(e){addMsg('system','⚠️ Send error: '+(e&&e.message?e.message:'network'));return false;});
}

// ── Voice recording ──
micBtn.addEventListener('click',function(){
  if(isRecording){stopRecording(true);return;}
  startRecording();
});

function startRecording(){
  if(isRecording)return;
  if(!isConnected&&!sessionId){connectRelay(function(ok){if(ok)startRecording();});return;}
  if(!isConnected){addMsg('system','Connecting…');return;}
  navigator.mediaDevices.getUserMedia({audio:{echoCancellation:true,noiseSuppression:true,sampleRate:16000}}).then(function(stream){
    micStream=stream;
    var mime=['audio/webm;codecs=opus','audio/webm','audio/ogg'].find(function(t){return MediaRecorder.isTypeSupported(t);})||'';
    mediaRecorder=new MediaRecorder(stream,mime?{mimeType:mime}:{});
    audioChunks=[];
    mediaRecorder.ondataavailable=function(e){if(e.data.size>0)audioChunks.push(e.data);};
    mediaRecorder.onstop=function(){sendAudio();};
    mediaRecorder.start(100);
    isRecording=true;
    micBtn.classList.add('recording');micBtn.title='Tap to stop recording';
    setOrb('listening');
    addMsg('system','🎙️ Recording… tap mic to send');
    hideEmpty();
    setTimeout(function(){if(isRecording)stopRecording(true);},15000);
  }).catch(function(err){
    addMsg('system','⚠️ '+(err.name==='NotAllowedError'?'Microphone permission denied':'Mic error: '+err.message));
  });
}
function stopRecording(send){
  if(!isRecording)return;isRecording=false;
  micBtn.classList.remove('recording');micBtn.title='Tap to record voice';
  if(micStream){micStream.getTracks().forEach(function(t){t.stop();});micStream=null;}
  if(mediaRecorder&&mediaRecorder.state!=='inactive'){
    if(!send){audioChunks=[];mediaRecorder.stop();setOrb('idle');return;}
    mediaRecorder.stop();
  } else if(send&&audioChunks.length){sendAudio();}
  else{setOrb('idle');}
}
function sendAudio(){
  if(!audioChunks.length||!sessionId||!isConnected){setOrb('idle');return;}
  var blob=new Blob(audioChunks,{type:'audio/webm'});audioChunks=[];
  var r=new FileReader();
  r.onloadend=function(){
    relaySend({type:'audio',data:r.result.split(',')[1],format:'webm'});
    setOrb('processing');showTyping();
  };
  r.readAsDataURL(blob);
}

// ── Voice Relay ──
function connectRelay(cb){
  if(isConnected&&sessionId){
    if(typeof cb==='function')cb(true);
    return;
  }
  userClosed=false;
  setConn('connecting');
  fetch(RELAY_URL+'/connect',{method:'POST',headers:AUTH_HEADERS,body:JSON.stringify({voice_server:'wss://127.0.0.1:3006/',text_only:true,ide_chat:true}),credentials:'include'})
  .then(function(r){return r.json().then(function(d){return{httpOk:r.ok,data:d};}).catch(function(){return{httpOk:false,data:{ok:false,error:'Bad JSON from relay'}};});})
  .then(function(pack){
    var d=pack.data;
    if(!pack.httpOk||!d||!d.ok){
      addMsg('system','⚠️ '+(d&&d.error?d.error:'Connection failed'));
      setConn('offline');
      if(typeof cb==='function')cb(false);
      return;
    }
    sessionId=d.session_id;isConnected=true;reconnectCount=0;setConn('online');
    setOrb('idle');
    pollActive=true;pollLoop();
    function finishConnect(){
      if(typeof cb==='function')cb(true);
    }
    if(IS_AUTH&&AUTH_TOKEN){
      relaySend({type:'auth',token:AUTH_TOKEN,username:AUTH_USER}).then(function(ok){
        addMsg('system',ok?'Connected — authenticated as '+AUTH_USER+' (tools + billing as available)':'⚠️ Auth ping failed — retry send or refresh');
        finishConnect();
      });
    }else{
      addMsg('system','Connected to Alfred (text chat — sign in on GoSiteMe for full tools)');
      finishConnect();
    }
  })
  .catch(function(e){
    setConn('offline');
    addMsg('system','⚠️ Connection failed — will retry');
    if(typeof cb==='function')cb(false);
    if(!userClosed&&reconnectCount<MAX_RECONNECT){
      reconnectCount++;
      var delay=Math.min(2000*reconnectCount,8000);
      setConn('connecting');
      reconnectTimer=setTimeout(function(){if(!isConnected&&!userClosed)connectRelay(cb);},delay);
    }
  });
}
var pollFailCount=0;
function pollLoop(){
  if(!pollActive||!sessionId)return;
  fetch(RELAY_URL+'/poll?session_id='+encodeURIComponent(sessionId),{headers:AUTH_TOKEN?{'Authorization':'Bearer '+AUTH_TOKEN}:{},credentials:'include'})
  .then(function(r){return r.json();})
  .then(function(d){
    if(!d||!d.ok){
      pollFailCount++;
      if(pollFailCount>=3){
        isConnected=false;pollActive=false;sessionId=null;setConn('offline');setOrb('idle');
        // Auto-reconnect after session expiry (e.g. server restart)
        if(!userClosed&&reconnectCount<MAX_RECONNECT){
          reconnectCount++;
          var rDelay=Math.min(2000*reconnectCount,8000);
          addMsg('system','\\u26a0\\ufe0f Session expired \\u2014 reconnecting in '+(rDelay/1000)+'s...');
          setConn('connecting');
          setTimeout(function(){if(!isConnected&&!userClosed)connectRelay();},rDelay);
        }
        return;
      }
      setTimeout(function(){if(pollActive)pollLoop();},1000*pollFailCount);
      return;
    }
    pollFailCount=0;
    var gotClose=false;
    if(d.messages&&d.messages.length){
      d.messages.forEach(function(m){
        try{
          var msg=typeof m==='string'?JSON.parse(m):m;
          if(msg.type==='ws_closed'||msg.type==='connection_closed'){gotClose=true;return;}
          if(msg.type==='audio_response'&&msg.data&&ttsEnabled){
            var bytes=atob(msg.data);var arr=new Uint8Array(bytes.length);
            for(var i=0;i<bytes.length;i++)arr[i]=bytes.charCodeAt(i);
            playTTS(arr.buffer);return;
          }
          if(msg.type==='audio_binary'&&msg.data&&ttsEnabled){
            var b=atob(msg.data);var a=new Uint8Array(b.length);
            for(var j=0;j<b.length;j++)a[j]=b.charCodeAt(j);
            playTTS(a.buffer);return;
          }
          handleMsg(msg);
        }catch(e){}
      });
    }
    if(gotClose){pollActive=false;isConnected=false;sessionId=null;setConn('connecting');
      setTimeout(function(){if(!userClosed)connectRelay();},1500);return;}
    if(pollActive)pollLoop();
  })
  .catch(function(e){
    pollFailCount++;
    if(pollFailCount>=3){
      isConnected=false;pollActive=false;sessionId=null;setConn('offline');setOrb('idle');
      // Auto-reconnect on network errors
      if(!userClosed&&reconnectCount<MAX_RECONNECT){
        reconnectCount++;
        var rDelay=Math.min(2000*reconnectCount,8000);
        setConn('connecting');
        setTimeout(function(){if(!isConnected&&!userClosed)connectRelay();},rDelay);
      }
      return;
    }
    if(pollActive){setTimeout(function(){if(pollActive)pollLoop();},2000);}
  });
}

function handleMsg(msg){
  hideTyping();
  switch(msg.type){
    case 'transcript':case 'transcription':
      addMsg('user',msg.text);setOrb('processing');showTyping();break;
    case 'response_text':case 'response':case 'text_response':
      addMsg('alfred',msg.text);setOrb(ttsEnabled?'speaking':'idle');
      if(ttsEnabled&&msg.text)speakText(msg.text,'widget');
      // Handle relay if active
      if(autoRelayEnabled&&relay.active&&relay.waitingFor==='widget'){continueRelayFromWidget(msg.text);}
      else if(autoRelayEnabled&&!relay.active&&lastUserMsg){startRelayChain(msg.text);}
      break;
    case 'response_complete':case 'pipeline_complete':
      hideTyping();setOrb('idle');break;
    case 'session_start':
      addMsg('system',msg.message||'Alfred is ready');break;
    case 'status':
      if(msg.message){addMsg('system',msg.message);break;}
      if(msg.stage==='thinking'||msg.stage==='transcribing'){setOrb('processing');showTyping();}
      else if(msg.stage==='speaking'){setOrb('speaking');}
      else if(msg.stage==='using_tools'){setOrb('processing');showTyping('Using tools…');}
      else if(msg.stage==='tool_result'){showTyping('Processing results…');}
      else if(msg.stage==='silence'){hideTyping();setOrb('idle');}
      break;
    case 'auth_ok':addMsg('system','🔑 Authenticated — full access enabled');break;
    case 'auth_fail':addMsg('system','⚠️ Auth failed — chat mode only');break;
    case 'tts_error':addMsg('system','🔇 '+(msg.message||'TTS unavailable'));break;
    case 'error':hideTyping();setOrb('idle');addMsg('system','⚠️ '+(msg.message||'Error'));break;
    case 'steering_queued':addMsg('system','📋 Queued: '+(msg.text||''));break;
    case 'steering_abort':addMsg('system','🛑 Cancelled');setOrb('idle');break;
    case 'steering_processing':addMsg('system','⚡ Running queued commands…');break;
    case 'steering_executing':addMsg('system','▶ '+(msg.text||msg.message||''));setOrb('processing');break;
  }
}

// ── Chat UI ──
function addMsg(role,text,opts){
  hideEmpty();
  opts=opts||{};
  var el=document.createElement('div');el.className='gcm-msg '+role;
  if(opts.relayed)el.classList.add('relayed');
  if(opts.msgId)el.dataset.relayId=opts.msgId;
  if(role==='alfred'){el.innerHTML=formatMsg(text);}
  else if(role==='ide-alfred'){el.innerHTML='<div class=\"gcm-ide-label\">\ud83d\udda5\ufe0f Alfred Coder</div>'+formatMsg(text);}
  else{el.textContent=text;}
  chat.appendChild(el);chat.scrollTop=chat.scrollHeight;
  // Notify autopilot panel hook
  if(window.__alfredAutopilot&&role==='alfred'&&text){
    if(text.indexOf('autopilot')!==-1||text.indexOf('Autopilot')!==-1||text.indexOf('browser session')!==-1){
      if(!window.__alfredAutopilot.isOpen())window.__alfredAutopilot.open('Web task');
    }
  }
}
function formatMsg(t){
  t=t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  t=t.replace(/\`\`\`([\\s\\S]*?)\`\`\`/g,'<pre>$1</pre>');
  t=t.replace(/\`([^\`]+)\`/g,'<code>$1</code>');
  t=t.replace(/\\*\\*([^*]+)\\*\\*/g,'<strong>$1</strong>');
  t=t.replace(/\\n/g,'<br>');
  return t;
}
function hideEmpty(){if(emptyEl)emptyEl.style.display='none';}
function showTyping(label){
  hideTyping();
  typingEl=document.createElement('div');typingEl.className='gcm-typing';typingEl.id='gcmTyping';
  if(label){typingEl.innerHTML='<span style="animation:none;width:auto;height:auto;background:none;font-size:.72rem;color:rgba(255,255,255,.4)">'+label+'</span>';}
  else{typingEl.innerHTML='<span></span><span></span><span></span>';}
  chat.appendChild(typingEl);chat.scrollTop=chat.scrollHeight;
  // Show stop button during processing
  sendBtn.style.display='none';stopBtn.style.display='flex';
}
function hideTyping(){if(typingEl){typingEl.remove();typingEl=null;}
  stopBtn.style.display='none';sendBtn.style.display='flex';
  syncSendDim();
}

// ── Orb states ──
function setOrb(state){
  orb.classList.remove('listening','processing','speaking');
  if(state!=='idle')orb.classList.add(state);
}
function setConn(state){
  connStatus.className='gcm-head-status '+state;
  connStatus.textContent=state==='online'?'Connected':state==='connecting'?'Connecting…':'Offline';
}

// ── TTS ──
document.getElementById('gcmTtsToggle').addEventListener('click',function(){
  ttsEnabled=!ttsEnabled;this.classList.toggle('on',ttsEnabled);
});
function getTTSCtx(){
  if(!ttsCtx||ttsCtx.state==='closed')ttsCtx=new(window.AudioContext||window.webkitAudioContext)();
  return ttsCtx;
}
var ttsCurrentSource=null;
function playTTS(buf){
  try{
    var ctx=getTTSCtx();
    if(ctx.state==='suspended')ctx.resume();
    var src=ctx.createBufferSource();
    ctx.decodeAudioData(buf.slice(0),function(decoded){
      src.buffer=decoded;src.connect(ctx.destination);src.start();
      ttsCurrentSource=src;
      setOrb('speaking');
      src.onended=function(){ttsCurrentSource=null;setOrb('idle');};
    });
  }catch(e){setOrb('idle');}
}

// ── Browser TTS for Alfred Coder (distinct voice) ──
function speakText(text,agent){
  if(!ttsEnabled)return;
  if(agent==='widget')return; // Widget uses server TTS via voice-relay audio
  if(!window.speechSynthesis)return;
  var bt=String.fromCharCode(96);
  var clean=text.replace(new RegExp(bt+bt+bt+'[\\\\s\\\\S]*?'+bt+bt+bt,'g'),' code block ').replace(new RegExp('[#*_~'+bt+'>|\\\\-]','g'),'').substring(0,1500);
  var utter=new SpeechSynthesisUtterance(clean);
  utter.rate=1.05;
  utter.pitch=0.8; // Lower pitch to distinguish from main Alfred voice
  utter.volume=0.9;
  var voices=speechSynthesis.getVoices();
  var ideVoice=voices.find(function(v){return /Daniel|Male|David|Mark|Google UK English Male/i.test(v.name);});
  if(!ideVoice)ideVoice=voices.find(function(v){return v.lang.indexOf('en')===0&&v!==speechSynthesis.getVoices()[0];});
  if(ideVoice)utter.voice=ideVoice;
  utter.onstart=function(){setOrb('speaking');};
  utter.onend=function(){setOrb('idle');};
  utter.onerror=function(){setOrb('idle');};
  speechSynthesis.speak(utter);
}
function cancelAllTTS(){
  if(window.speechSynthesis)speechSynthesis.cancel();
  if(ttsCurrentSource){try{ttsCurrentSource.stop();}catch(e){}}ttsCurrentSource=null;
}

// ── Settings ──
var _gcmH={'Content-Type':'application/json'};
if(window.__gcmSessionToken)_gcmH['Authorization']='Bearer '+window.__gcmSessionToken;
var modePills=document.querySelectorAll('.gcm-pill');
modePills.forEach(function(m){m.addEventListener('click',function(){
  modePills.forEach(function(x){x.classList.remove('active');});m.classList.add('active');
  fetch('/middleware/api/usage/set-model',{method:'POST',headers:_gcmH,body:JSON.stringify({mode:m.dataset.mode}),credentials:'include'}).catch(function(){});
});});
var providerColors={
  'Anthropic':{c:'#e87b35',bg:'rgba(232,123,53,.12)'},
  'OpenAI':{c:'#10b981',bg:'rgba(16,185,129,.12)'},
  'Google':{c:'#4285f4',bg:'rgba(66,133,244,.12)'},
  'Open Source':{c:'#06b6d4',bg:'rgba(6,182,212,.12)'},
  'xAI':{c:'#ef4444',bg:'rgba(239,68,68,.12)'},
  'Groq':{c:'#f97316',bg:'rgba(249,115,22,.12)'},
  'Video Gen':{c:'#ec4899',bg:'rgba(236,72,153,.12)'}
};
var modelDefs=[
  {id:'claude-opus-4-6',n:'Claude Opus 4.6',t:'Premium',p:'Anthropic'},
  {id:'claude-sonnet-4-6',n:'Claude Sonnet 4.6',t:'Standard',p:'Anthropic'},
  {id:'claude-haiku-4-5',n:'Claude Haiku 4.5',t:'Economy',p:'Anthropic'},
  {id:'gpt-4.1',n:'GPT-4.1',t:'Standard',p:'OpenAI'},
  {id:'gpt-4.1-mini',n:'GPT-4.1 Mini',t:'Economy',p:'OpenAI'},
  {id:'gpt-4.1-nano',n:'GPT-4.1 Nano',t:'Economy',p:'OpenAI'},
  {id:'gpt-4o',n:'GPT-4o',t:'Standard',p:'OpenAI'},
  {id:'o3',n:'o3 (Reasoning)',t:'Premium',p:'OpenAI'},
  {id:'o3-mini',n:'o3 Mini',t:'Standard',p:'OpenAI'},
  {id:'o4-mini',n:'o4 Mini',t:'Standard',p:'OpenAI'},
  {id:'gemini-3.1-pro',n:'Gemini 3.1 Pro',t:'Premium',p:'Google'},
  {id:'gemini-3-flash',n:'Gemini 3 Flash',t:'Economy',p:'Google'},
  {id:'gemini-3.1-flash-lite',n:'Gemini 3.1 Flash Lite',t:'Economy',p:'Google'},
  {id:'gemini-image',n:'Gemini Image Gen',t:'Economy',p:'Google'},
  {id:'gemini-2.5-pro',n:'Gemini 2.5 Pro',t:'Premium',p:'Google'},
  {id:'gemini-2.5-flash',n:'Gemini 2.5 Flash',t:'Economy',p:'Google'},
  {id:'grok-3',n:'Grok 3',t:'Standard',p:'xAI'},
  {id:'grok-3-mini',n:'Grok 3 Mini',t:'Economy',p:'xAI'},
  {id:'qwen3-coder',n:'Qwen3 Coder',t:'Economy',p:'Open Source'},
  {id:'qwen3-coder-480b',n:'Qwen3 Coder 480B',t:'Standard',p:'Open Source'},
  {id:'qwen3.5',n:'Qwen 3.5',t:'Economy',p:'Open Source'},
  {id:'deepseek-v3',n:'DeepSeek V3.1',t:'Economy',p:'Open Source'},
  {id:'deepseek-r1',n:'DeepSeek R1',t:'Reasoning',p:'Open Source'},
  {id:'glm-5',n:'GLM-5',t:'Economy',p:'Open Source'},
  {id:'kimi-k2.5',n:'Kimi K2.5',t:'Economy',p:'Open Source'},
  {id:'kimi-k2-thinking',n:'Kimi K2 Thinking',t:'Standard',p:'Open Source'},
  {id:'llama-4-maverick',n:'Llama 4 Maverick',t:'Economy',p:'Open Source'},
  {id:'llama-4-scout',n:'Llama 4 Scout',t:'Economy',p:'Open Source'},
  {id:'mistral-small',n:'Mistral Small',t:'Economy',p:'Open Source'},
  {id:'groq-llama-3.3-70b',n:'Llama 3.3 70B',t:'Free',p:'Groq'},
  {id:'groq-llama-3.1-8b',n:'Llama 3.1 8B',t:'Free',p:'Groq'},
  {id:'veo-2-video',n:'Veo 2 Video',t:'Standard',p:'Video Gen'}
];
var grid=document.getElementById('gcmModelGrid');
modelDefs.forEach(function(m){
  var b=document.createElement('button');b.className='gcm-model-card';b.dataset.model=m.id;
  var pc=providerColors[m.p]||{c:'#a78bfa',bg:'rgba(167,139,250,.12)'};
  var tierClass=m.t==='Free'?' gcm-free':'';
  var badge=m.t==='Free'?'<span class="gcm-free-badge">FREE</span>':'';
  var tierColor=m.t==='Premium'?'#f59e0b':m.t==='Reasoning'?'#a855f7':m.t==='Economy'?'#10b981':m.t==='Free'?'#f97316':pc.c;
  b.style.cssText='background:'+pc.bg+';border-color:'+pc.c+'33;--card-glow:'+pc.c;
  b.innerHTML='<div class="gcm-mn">'+m.n+badge+'</div><div class="gcm-mt'+tierClass+'" style="color:'+tierColor+'">'+m.t+' · <span style="color:'+pc.c+'">'+m.p+'</span></div>';
  b.addEventListener('click',function(){
    grid.querySelectorAll('.gcm-model-card').forEach(function(x){x.classList.remove('active');});b.classList.add('active');
    fetch('/middleware/api/usage/set-model',{method:'POST',headers:_gcmH,body:JSON.stringify({model:m.id}),credentials:'include'}).catch(function(){});
  });
  grid.appendChild(b);
});
function loadSettings(){
  fetch('/middleware/api/usage/current-model',{headers:_gcmH,credentials:'include'}).then(function(r){return r.json();}).then(function(d){
    if(d.mode)modePills.forEach(function(m){m.classList.toggle('active',m.dataset.mode===d.mode);});
    if(d.model)grid.querySelectorAll('.gcm-model-card').forEach(function(b){b.classList.toggle('active',b.dataset.model===d.model);});
  }).catch(function(){});
  fetch('/middleware/api/tokens/report',{headers:_gcmH,credentials:'include'}).then(function(r){return r.json();}).then(function(d){
    if(d.ok&&d.tokens){
      var u=d.tokens.used||0,t=d.tokens.total||1,pct=Math.min(100,Math.round(u/t*100));
      document.getElementById('gcmTokenFill').style.width=pct+'%';
      document.getElementById('gcmTokenUsed').textContent=fmtT(u)+' used';
      document.getElementById('gcmTokenTotal').textContent=fmtT(t)+' total';
    }
  }).catch(function(){});
}
function fmtT(n){return n>=1e6?(n/1e6).toFixed(1)+'M':n>=1e3?(n/1e3).toFixed(0)+'K':n.toString();}

// ── Alfred Dual-Agent Orchestration Engine v4.0 ─────────────────────────────
// Multi-turn relay · Shared memory · Task routing · Consensus · Bi-directional
(function initOrchestrator(){
  // ── Relay State Machine ──
  var relay={active:false,pass:0,seqId:0,waitingFor:null,sentByUs:false};

  // ── Task Router ──
  function routeTask(text){
    var t=(text||'').toLowerCase();
    var cKW=['code','function','debug','error','syntax','file','class','import','module','refactor','test','compile','build','lint','typescript','javascript','python','react','css','html','git','commit','branch','merge','package','npm','component','api','endpoint','controller','docker','webpack','vite'];
    var hKW=['dns','domain','hosting','server','ssl','email','cpanel','directadmin','whmcs','billing','invoice','nginx','apache','port','certificate','deploy','ftp','ssh','backup','nameserver','mx','cname','renewal','transfer','bandwidth','quota','plan','vps','dedicated','whois','registrar'];
    var cS=0,hS=0;
    cKW.forEach(function(w){if(t.indexOf(w)!==-1)cS++;});
    hKW.forEach(function(w){if(t.indexOf(w)!==-1)hS++;});
    if(cS>hS+1)return 'ide';if(hS>cS+1)return 'widget';return 'both';
  }

  // ── Thread Management (Shared Memory with localStorage persistence) ──
  function addToThread(role,text,pass){
    relayThread.push({role:role,text:text.substring(0,2000),pass:pass||relay.pass,ts:Date.now()});
    // Cap thread size to prevent memory leak
    if(relayThread.length>MAX_THREAD_SIZE)relayThread=relayThread.slice(-MAX_THREAD_SIZE);
    updateThreadUI();
    saveRelayState();
  }
  function saveRelayState(){
    try{localStorage.setItem(LS_KEY,JSON.stringify({thread:relayThread.slice(-30),stats:relayStats,mode:relayMode,turns:maxRelayTurns}));}catch(e){}
  }
  function buildContextPrompt(forAgent,latestText){
    var ctx='[DUAL-ALFRED ORCHESTRATION — '+relayMode.toUpperCase()+' MODE]\\n';
    ctx+='You are '+(forAgent==='ide'?'Alfred Coder (code/workspace/filesystem expert with full editor access)':'Alfred (hosting/MCP tools/DNS/billing expert with 400+ tools)')+'.\\n';
    ctx+='This is relay pass '+relay.pass+' of '+maxRelayTurns+'.\\n\\n';
    if(relayThread.length>0){
      ctx+='=== CONVERSATION THREAD ===\\n';
      var recent=relayThread.slice(-8);
      recent.forEach(function(t){
        var label=t.role==='user'?'USER':t.role==='widget'?'WIDGET ALFRED':'IDE ALFRED';
        ctx+=label+' (pass '+t.pass+'): '+t.text.substring(0,600)+'\\n\\n';
      });
      ctx+='=== END THREAD ===\\n\\n';
    }
    if(relayMode==='consensus'){
      ctx+='CONSENSUS MODE: Compare your analysis with the other Alfred. State whether you AGREE or DISAGREE, and why. Propose a unified recommendation.\\n';
    } else if(relayMode==='delegate'){
      ctx+='DELEGATE MODE: Only respond if this falls within your expertise. Otherwise say [PASS].\\n';
    } else {
      ctx+='COLLAB MODE: Build on the other Alfred\\'s response. Add your unique perspective. Be concise — only add what the other Alfred cannot.\\n';
    }
    ctx+='If you have absolutely nothing meaningful to add, respond with only [PASS].';
    return ctx;
  }

  // ── Find AI Chat Editor (Theia) — cached, multi-strategy ──
  var _cachedEditor=null,_cacheTs=0;
  function findChatEditor(){
    // Cache hit: reuse for 2s unless stale
    if(_cachedEditor&&(Date.now()-_cacheTs)<2000){
      try{if(_cachedEditor.getDomNode()&&document.body.contains(_cachedEditor.getDomNode()))return _cachedEditor;}catch(e){}
      _cachedEditor=null;
    }
    if(!window.monaco)return null;
    var editors=window.monaco.editor.getEditors();
    // Strategy 1: Theia AI Chat specific selectors
    var selectors=[
      '.theia-ChatInput-Editor .monaco-editor',
      '.theia-ChatInput-Editor-Box .monaco-editor',
      '.theia-ChatInput .monaco-editor',
      '[class*="ChatInput"] .monaco-editor',
      '#chat-view-widget .monaco-editor',
      '#chat-input-widget .monaco-editor',
      '#chat-tree-widget-treeContainer .monaco-editor',
      '[id*="chat"] .monaco-editor'
    ];
    var container=null;
    for(var s=0;s<selectors.length;s++){
      container=document.querySelector(selectors[s]);
      if(container)break;
    }
    if(container&&editors.length){
      for(var i=0;i<editors.length;i++){
        var domNode=editors[i].getDomNode();
        if(domNode&&(container.contains(domNode)||container===domNode)){_cachedEditor=editors[i];_cacheTs=Date.now();return editors[i];}
      }
    }
    // Strategy 2: Walk up from editor DOM node to find chat parent
    for(var j=0;j<editors.length;j++){
      var dom=editors[j].getDomNode();
      if(dom){
        var parent=dom.closest('.theia-ChatInput-Editor')||dom.closest('.theia-ChatInput-Editor-Box')||dom.closest('.theia-ChatInput')||dom.closest('[class*="ChatInput"]')||dom.closest('#chat-view-widget')||dom.closest('#chat-input-widget');
        if(parent){_cachedEditor=editors[j];_cacheTs=Date.now();return editors[j];}
      }
    }
    // Strategy 3: Theia uses ai-chat:/ URI scheme for the chat input editor
    for(var k=0;k<editors.length;k++){
      try{var model=editors[k].getModel();if(model&&model.uri&&(model.uri.scheme==='ai-chat'||model.uri.path.indexOf('chat')!==-1)){_cachedEditor=editors[k];_cacheTs=Date.now();return editors[k];}}catch(e){}
    }
    // Strategy 4: Check models for ai-chat scheme, then match to editor
    try{
      var models=window.monaco.editor.getModels();
      for(var m=0;m<models.length;m++){
        if(models[m].uri&&models[m].uri.scheme==='ai-chat'){
          for(var e2=0;e2<editors.length;e2++){
            try{if(editors[e2].getModel()===models[m]){_cachedEditor=editors[e2];_cacheTs=Date.now();return editors[e2];}}catch(e){}
          }
        }
      }
    }catch(e){}
    // Strategy 5: Monaco editor exists in DOM but maybe not in getEditors() — find via widget container
    if(!editors.length||!container){
      var chatEditorDiv=document.querySelector('.theia-ChatInput-Editor .monaco-editor')||document.querySelector('#chat-input-widget .monaco-editor');
      if(chatEditorDiv){
        // Try to get editor from Monaco internals via the DOM node
        try{
          var allEditors=window.monaco.editor.getEditors();
          for(var a=0;a<allEditors.length;a++){
            var dn=allEditors[a].getDomNode();
            if(dn&&(chatEditorDiv.contains(dn)||chatEditorDiv===dn)){_cachedEditor=allEditors[a];_cacheTs=Date.now();return allEditors[a];}
          }
        }catch(e){}
        // Last resort: return a proxy that uses the textarea directly
        var ta=chatEditorDiv.querySelector('textarea.inputarea')||chatEditorDiv.querySelector('textarea');
        if(ta){
          return {_isFallback:true,_textarea:ta,_container:chatEditorDiv,
            getDomNode:function(){return this._container;},
            getModel:function(){return {setValue:function(v){this._textarea.focus();this._textarea.value=v;this._textarea.dispatchEvent(new Event('input',{bubbles:true}));}.bind(this)};}.bind(this),
            setValue:function(v){this.getModel().setValue(v);},
            focus:function(){this._textarea.focus();}
          };
        }
      }
    }
    return null;
  }
  function isChatPanelOpen(){return !!document.querySelector('.theia-ChatNode')||!!document.querySelector('.theia-ChatInput')||!!document.querySelector('#chat-tree-widget-treeContainer')||!!document.querySelector('#chat-input-widget')||!!document.querySelector('#chat-view-widget');}

  // ── Send to Alfred Coder (with retry + auto-open) ──
  function sendToAIChat(text,isRelay){
    var ed=findChatEditor();
    if(!ed){
      // Try to open the AI Chat panel by clicking its tab/widget
      var chatTab=document.querySelector('[id*="chat-view"]')||document.querySelector('[id*="chat-input"]')||document.querySelector('[id*="ai-chat"]')||document.querySelector('.p-TabBar-tab[title*="Chat"]')||document.querySelector('.theia-TabBar-tab[title*="Chat"]')||document.querySelector('.p-TabBar-tab[title*="AI"]')||document.querySelector('.theia-TabBar-tab[title*="AI"]');
      if(chatTab){
        chatTab.click();
        addMsg('system','\\ud83d\\udce1 Opening AI Chat for Alfred Coder...');
        // Retry with increasing delays — Monaco editor is created async in React.useEffect
        var retries=[1500,3000,5000,7500,10000];
        (function tryRetry(idx){
          setTimeout(function(){
            var ed2=findChatEditor();
            if(ed2){doSendToEditor(ed2,text,isRelay);}
            else if(idx<retries.length-1){tryRetry(idx+1);}
            else{
              // Only show error if the chat panel itself isn't visible
              if(!isChatPanelOpen()){
                addMsg('system','\\u26a0\\ufe0f AI Chat panel failed to open \\u2014 try pressing Ctrl+Alt+I to open it manually');
              } else {
                addMsg('system','\\u26a0\\ufe0f AI Chat is open but the editor input could not be detected. Try typing directly in the AI Chat panel.');
              }
              if(isRelay&&relay.active){endRelay('\\u26a0\\ufe0f AI Chat editor not reachable');}
            }
          },retries[idx]);
        })(0);
        return 'pending';
      }
      // No chat tab found but panel might already be open
      if(isChatPanelOpen()){
        addMsg('system','\\ud83d\\udce1 AI Chat panel detected, waiting for editor...');
        var retries2=[1000,2500,5000,8000];
        (function tryRetry2(idx){
          setTimeout(function(){
            var ed3=findChatEditor();
            if(ed3){doSendToEditor(ed3,text,isRelay);}
            else if(idx<retries2.length-1){tryRetry2(idx+1);}
            else{
              addMsg('system','\\u26a0\\ufe0f AI Chat editor input not detected \\u2014 try typing directly in the AI Chat panel');
              if(isRelay&&relay.active){endRelay('\\u26a0\\ufe0f AI Chat editor not reachable');}
            }
          },retries2[idx]);
        })(0);
        return 'pending';
      }
      addMsg('system','\\u26a0\\ufe0f AI Chat panel not found \\u2014 press Ctrl+Alt+I to open it, then try again');
      return false;
    }
    return doSendToEditor(ed,text,isRelay);
  }
  function doSendToEditor(ed,text,isRelay){
    if(isRelay)relay.sentByUs=true;
    // Handle fallback textarea proxy
    if(ed._isFallback){
      ed.setValue(text);
      ed.focus();
      setTimeout(function(){
        var sendBtn=document.querySelector('.theia-ChatInputOptions-right [role="button"][title*="Send"]')||document.querySelector('.theia-ChatInput [role="button"][title*="Send"]')||document.querySelector('[class*="codicon-send"]');
        if(sendBtn){(sendBtn.closest('[role="button"]')||sendBtn.closest('button')||sendBtn).click();return;}
        // Enter key on textarea
        var enterOpts={key:'Enter',code:'Enter',keyCode:13,which:13,bubbles:true,cancelable:true,composed:true};
        ed._textarea.dispatchEvent(new KeyboardEvent('keydown',enterOpts));
        ed._textarea.dispatchEvent(new KeyboardEvent('keyup',enterOpts));
      },200);
      return true;
    }
    // Set the value via the Monaco model so React picks it up
    try{
      var model=ed.getModel();
      if(model){model.setValue(text);}else{ed.setValue(text);}
    }catch(e){ed.setValue(text);}
    ed.focus();
    setTimeout(function(){
      // Strategy 1: Click the Send button directly (most reliable \\u2014 triggers React submit callback)
      var sendSelectors=[
        '.theia-ChatInput-Editor-Box [role="button"][title*="Send"]',
        '.theia-ChatInputOptions-right [role="button"][title*="Send"]',
        '.theia-ChatInput [role="button"][title*="Send"]',
        '[class*="ChatInput"] [role="button"][title*="Send"]',
        '#chat-view-widget [role="button"][title*="Send"]',
        '.theia-ChatInput-Editor-Box button[title*="Send"]',
        '.theia-ChatInput button[title*="Send"]',
        '.theia-ChatInputOptions-right .option',
        '[class*="ChatInput"] button[title*="Send"]'
      ];
      var sendBtn=null;
      for(var si=0;si<sendSelectors.length;si++){sendBtn=document.querySelector(sendSelectors[si]);if(sendBtn)break;}
      // Also try codicon-send icon
      if(!sendBtn){var icon=document.querySelector('[class*="codicon-send"]');if(icon)sendBtn=icon.closest('[role="button"]')||icon.closest('button');}
      if(sendBtn&&!sendBtn.disabled){sendBtn.click();return;}
      // Strategy 2: Dispatch Enter key on the Monaco editor\\u2019s input element
      var domNode=ed.getDomNode();
      if(domNode){
        var target=domNode.querySelector('textarea.inputarea')||domNode.querySelector('textarea');
        if(target){
          target.focus();
          try{target.dispatchEvent(new InputEvent('beforeinput',{inputType:'insertLineBreak',bubbles:true,cancelable:true,composed:true}));}catch(e){}
          var enterOpts={key:'Enter',code:'Enter',keyCode:13,which:13,bubbles:true,cancelable:true,composed:true};
          target.dispatchEvent(new KeyboardEvent('keydown',enterOpts));
          target.dispatchEvent(new KeyboardEvent('keyup',enterOpts));
          return;
        }
      }
      // Strategy 3: Try Monaco action as last resort
      try{
        var action=ed.getAction('chat.accept')||ed.getAction('acceptInput');
        if(action){action.run();return;}
      }catch(e){}
      // Strategy 4: If all else fails, try triggering Enter via the editor command
      try{ed.trigger('relay','type',{text:'\\n'});}catch(e){}
    },250);
    return true;
  }

  // ── Send to Alfred (Widget) ──
  function sendToWidget(text){
    function fire(){
      relaySend({type:'text',text:text,ttsEnabled:ttsEnabled});
    }
    if(!sessionId||!isConnected){
      connectRelay(function(ok){if(ok)fire();});
      return;
    }
    fire();
  }

  // ── Start Relay Chain (with rate limiting) ──
  function startRelayChain(widgetResponse){
    var now=Date.now();
    if(now-lastRelayEnd<RELAY_COOLDOWN){addMsg('system','\u23f3 Cooldown \u2014 wait '+(Math.ceil((RELAY_COOLDOWN-(now-lastRelayEnd))/1000))+'s');return;}
    relay.active=true;
    relay.pass=1;
    relay.seqId++;
    relay.startTs=Date.now();
    var seqNow=relay.seqId;
    relay.waitingFor='ide';
    relayStats.relays++;
    updateRelayStats();
    addToThread('user',lastUserMsg,0);
    addToThread('widget',widgetResponse,0);
    showRelayBar('\\ud83d\\udd04 Pass 1/'+maxRelayTurns+' \\u2014 Relaying to Alfred Coder\\u2026');
    var prompt=buildContextPrompt('ide',widgetResponse);
    var result=sendToAIChat(prompt,true);
    if(result===false){endRelay('\\u26a0\\ufe0f AI Chat not available \\u2014 open with Ctrl+Alt+I');}
    else if(result==='pending'){addMsg('system','\\u23f3 Opening AI Chat panel\\u2026');}
    setTimeout(function(){
      if(relay.active&&relay.seqId===seqNow&&relay.waitingFor==='ide'){endRelay('\\u23f0 Alfred Coder timed out');}
    },90000);
  }

  // ── Handle Alfred Coder Response (from observer) ──
  function handleIDEResponse(text){
    if(!relay.active||relay.waitingFor!=='ide')return false;
    if(!autoRelayEnabled){endRelay('Relay toggled off');return true;}
    if(text.indexOf('[PASS]')!==-1){endRelay('Alfred Coder passed \\u2014 nothing to add');return true;}
    var display=text.length>3000?text.substring(0,3000)+'\\u2026':text;
    addMsg('ide-alfred',display,{relayed:true});
    speakText(display,'ide');
    addTurnBadge('ide-alfred',relay.pass);
    addToThread('ide',text,relay.pass);
    relayStats.turns++;
    updateRelayStats();
    removeRelayBar();
    if(relayMode==='consensus'){checkConsensus();}
    if(relay.pass<maxRelayTurns){
      relay.pass++;
      relay.waitingFor='widget';
      relay.sentByUs=false;
      showRelayBar('\\ud83d\\udd04 Pass '+relay.pass+'/'+maxRelayTurns+' \\u2014 Relaying to Alfred\\u2026');
      var prompt=buildContextPrompt('widget',text);
      sendToWidget(prompt);
      var seqNow=relay.seqId;
      setTimeout(function(){
        if(relay.active&&relay.seqId===seqNow&&relay.waitingFor==='widget'){endRelay('\\u23f0 Alfred timed out on pass '+relay.pass);}
      },90000);
    } else {endRelay(null);}
    return true;
  }

  // ── Handle Alfred Widget Response during Relay ──
  function continueRelayFromWidget(text){
    if(!relay.active||relay.waitingFor!=='widget')return false;
    if(!autoRelayEnabled){endRelay('Relay toggled off');return true;}
    if(text.indexOf('[PASS]')!==-1){endRelay('Alfred passed \\u2014 nothing to add');return true;}
    addTurnBadge('alfred',relay.pass);
    addToThread('widget',text,relay.pass);
    relayStats.turns++;
    updateRelayStats();
    removeRelayBar();
    if(relay.pass<maxRelayTurns){
      relay.pass++;
      relay.waitingFor='ide';
      showRelayBar('\\ud83d\\udd04 Pass '+relay.pass+'/'+maxRelayTurns+' \\u2014 Relaying to Alfred Coder\\u2026');
      var prompt=buildContextPrompt('ide',text);
      var result=sendToAIChat(prompt,true);
      if(result===false){endRelay('\\u26a0\\ufe0f AI Chat not available');}
      var seqNow=relay.seqId;
      setTimeout(function(){
        if(relay.active&&relay.seqId===seqNow&&relay.waitingFor==='ide'){endRelay('\\u23f0 Alfred Coder timed out on pass '+relay.pass);}
      },90000);
    } else {endRelay(null);}
    return true;
  }

  // ── End Relay ──
  function endRelay(msg){
    var totalPasses=relay.pass;
    var elapsed=relay.startTs?Math.round((Date.now()-relay.startTs)/1000):0;
    relay.active=false;
    relay.waitingFor=null;
    relay.sentByUs=false;
    relay.pass=0;
    lastRelayEnd=Date.now();
    if(elapsed>0)relayStats.totalTime+=elapsed;
    removeRelayBar();
    if(msg){addMsg('system',msg);}
    else{addMsg('system','\\u2705 Relay complete \\u2014 '+totalPasses+' pass'+(totalPasses>1?'es':'')+' in '+elapsed+'s');}
    saveRelayState();
  }

  // ── Relay Status UI ──
  function showRelayBar(text){
    removeRelayBar();
    var el=document.createElement('div');
    el.className='gcm-relay-active-bar';
    el.innerHTML='<span class=\\'rab-dot\\'></span> '+text;
    el.id='gcmRelayBar';
    chat.appendChild(el);chat.scrollTop=chat.scrollHeight;
  }
  function removeRelayBar(){
    var els=chat.querySelectorAll('.gcm-relay-active-bar,.gcm-relay-status');
    els.forEach(function(e){e.remove();});
  }
  function addTurnBadge(msgClass,pass){
    var msgs=chat.querySelectorAll('.gcm-msg.'+msgClass);
    var last=msgs[msgs.length-1];
    if(last){
      last.classList.add('relayed');
      var badge=document.createElement('span');
      badge.className='gcm-turn-badge p'+Math.min(pass,3);
      badge.textContent='P'+pass;
      last.insertBefore(badge,last.firstChild);
    }
  }

  // ── Consensus Check (improved: stop-word filter + Jaccard similarity) ──
  var STOP_WORDS='the,a,an,is,are,was,were,be,been,being,have,has,had,do,does,did,will,would,could,should,may,might,shall,can,this,that,these,those,it,its,i,you,he,she,we,they,me,him,her,us,them,my,your,his,our,their,what,which,who,whom,when,where,how,not,no,nor,but,and,or,so,if,then,than,too,very,just,also,as,of,in,on,at,to,for,with,by,from,about,into,through,during,before,after,above,below,between,under,again,further,once,all,each,every,both,few,more,most,other,some,such,only,same,just,because,until,while'.split(',');
  function checkConsensus(){
    var wTexts=relayThread.filter(function(t){return t.role==='widget';}).map(function(t){return t.text.toLowerCase();});
    var iTexts=relayThread.filter(function(t){return t.role==='ide';}).map(function(t){return t.text.toLowerCase();});
    if(!wTexts.length||!iTexts.length)return;
    var wt=wTexts[wTexts.length-1];
    var it=iTexts[iTexts.length-1];
    // Extract meaningful words (no stop words, min 3 chars)
    function extractWords(s){return s.replace(/[^a-z0-9\\s]/g,' ').split(/\\s+/).filter(function(w){return w.length>2&&STOP_WORDS.indexOf(w)===-1;});}
    var wWords=extractWords(wt);
    var iWords=extractWords(it);
    // Jaccard similarity: intersection / union
    var wSet={},iSet={},intersection=0,unionSize=0;
    wWords.forEach(function(w){wSet[w]=true;});
    iWords.forEach(function(w){iSet[w]=true;});
    var allKeys={};
    Object.keys(wSet).forEach(function(k){allKeys[k]=true;});
    Object.keys(iSet).forEach(function(k){allKeys[k]=true;});
    unionSize=Object.keys(allKeys).length;
    Object.keys(wSet).forEach(function(k){if(iSet[k])intersection++;});
    var sim=unionSize>0?intersection/unionSize:0;
    var bar=document.createElement('div');
    bar.className='gcm-consensus-bar';
    if(sim>0.20){
      bar.className+=' agreed';
      bar.innerHTML='\\u2705 Both Alfreds broadly agree ('+Math.round(sim*100)+'% Jaccard similarity, '+intersection+' shared concepts)';
      relayStats.consensusHits++;
    } else {
      bar.className+=' diverged';
      bar.innerHTML='\\u26a1 Different perspectives ('+Math.round(sim*100)+'% overlap) \\u2014 review both responses';
    }
    chat.appendChild(bar);chat.scrollTop=chat.scrollHeight;
    updateRelayStats();
  }

  // ── Observer: IDE Chat Response Detection (self-healing, Theia-native) ──
  var chatObserver=null;
  var lastObservedText='';
  var observerDebounce=null;
  var lastMutationTs=0;
  var observedTarget=null;
  function startChatObserver(){
    // Disconnect stale observer if target was removed from DOM
    if(chatObserver&&observedTarget&&!document.body.contains(observedTarget)){
      chatObserver.disconnect();chatObserver=null;observedTarget=null;
    }
    if(chatObserver)return;
    // Theia AI Chat uses #chat-tree-widget or #chat-tree-widget-treeContainer
    var containerSelectors=['#chat-tree-widget-treeContainer','#chat-tree-widget','#chat-view-widget','[id*="chat-tree"]','[id*="chat-view"]'];
    var target=null;
    for(var ci=0;ci<containerSelectors.length;ci++){target=document.querySelector(containerSelectors[ci]);if(target)break;}
    if(!target){
      var chatNode=document.querySelector('.theia-ChatNode')||document.querySelector('.theia-ResponseNode');
      if(chatNode){target=chatNode.closest('[class*="TreeContainer"]')||chatNode.parentElement;}
    }
    if(!target){setTimeout(startChatObserver,3000);return;}
    observedTarget=target;
    chatObserver=new MutationObserver(function(){
      lastMutationTs=Date.now();
      clearTimeout(observerDebounce);
      // Smart debounce: short wait, then check if mutations stopped (stream ended)
      observerDebounce=setTimeout(function checkStable(){
        var elapsed=Date.now()-lastMutationTs;
        if(elapsed<1800){setTimeout(checkStable,1000);return;}
        // Mutations stopped for 1.8s — response likely complete
        // Check if there's still a "Generating" indicator (stream not done)
        if(observedTarget.querySelector('.theia-ChatContentInProgress')){setTimeout(checkStable,1500);return;}
        // Find all response nodes (Theia uses .theia-ResponseNode for AI responses)
        var responseNodes=observedTarget.querySelectorAll('.theia-ResponseNode');
        if(!responseNodes.length){
          // Fallback: try .theia-ChatNode elements
          responseNodes=observedTarget.querySelectorAll('.theia-ChatNode');
        }
        if(!responseNodes.length)return;
        var lastNode=responseNodes[responseNodes.length-1];
        // Extract text from the response content (Theia uses .theia-ResponseNode-Content)
        var contentEl=lastNode.querySelector('.theia-ResponseNode-Content')||lastNode;
        var text=(contentEl.textContent||'').trim();
        if(!text||text.length<10||text===lastObservedText)return;
        lastObservedText=text;
        masterObserverCallback(text);
      },2000);
    });
    chatObserver.observe(target,{childList:true,subtree:true,characterData:true});
  }
  // Self-healing: periodically check observer health
  setInterval(function(){
    if(!chatObserver||!observedTarget||!document.body.contains(observedTarget)){
      chatObserver=null;observedTarget=null;startChatObserver();
    }
  },10000);
  setTimeout(startChatObserver,5000);

  // ── Master Observer Callback ──
  function masterObserverCallback(responseText){
    // Case 1: We sent this via relay AND relay is actively waiting — handle as relay response
    if(relay.sentByUs&&relay.active&&relay.waitingFor==='ide'){
      relay.sentByUs=false;
      handleIDEResponse(responseText);
      return;
    }
    // Reset stale sentByUs flag (e.g. from Forward button or ended relay) — don't swallow the response
    if(relay.sentByUs)relay.sentByUs=false;
    // Case 2: User typed directly in IDE Chat with auto-relay on — bi-directional relay
    if(autoRelayEnabled&&!relay.active){
      var display=responseText.length>2000?responseText.substring(0,2000)+'\\u2026':responseText;
      addMsg('ide-alfred',display);
      relay.active=true;
      relay.pass=1;
      relay.seqId++;
      relay.waitingFor='widget';
      addToThread('ide',responseText,0);
      showRelayBar('\\ud83d\\udd04 Bi-directional \\u2014 asking Alfred for perspective\\u2026');
      var prompt=buildContextPrompt('widget',responseText);
      sendToWidget(prompt);
      var seqNow=relay.seqId;
      setTimeout(function(){
        if(relay.active&&relay.seqId===seqNow&&relay.waitingFor==='widget'){endRelay('\\u23f0 Alfred timed out');}
      },90000);
      return;
    }
    // Case 3: Not in relay mode — no action
    relay.sentByUs=false;
  }

  // ── Thread UI ──
  function updateThreadUI(){
    var container=document.getElementById('gcmThreadBox');
    if(!container)return;
    if(!relayThread.length){
      container.innerHTML='<div class=\\'gcm-thread-empty\\'>No relay conversations yet.<br>Toggle Relay ON and ask a question.</div>';
      return;
    }
    container.innerHTML='';
    relayThread.slice(-20).forEach(function(t){
      var cls=t.role==='user'?'t-user':t.role==='widget'?'t-widget':'t-ide';
      var label=t.role==='user'?'\\ud83d\\udc64 User':t.role==='widget'?'\\ud83c\\udfa9 Alfred':'\\ud83d\\udda5\\ufe0f Alfred Coder';
      var div=document.createElement('div');
      div.className='gcm-thread-item '+cls;
      var preview=t.text.length>150?t.text.substring(0,150)+'\\u2026':t.text;
      div.innerHTML='<div class=\\'t-role\\'>'+label+' <span class=\\'t-meta\\'>P'+t.pass+'</span></div><div class=\\'t-text\\'>'+preview.replace(/</g,'&lt;')+'</div>';
      container.appendChild(div);
    });
    container.scrollTop=container.scrollHeight;
  }

  // ── Stats UI ──
  function updateRelayStats(){
    var e1=document.getElementById('gcmStatRelays');
    var e2=document.getElementById('gcmStatTurns');
    var e3=document.getElementById('gcmStatConsensus');
    var e4=document.getElementById('gcmStatTime');
    if(e1)e1.textContent=relayStats.relays;
    if(e2)e2.textContent=relayStats.turns;
    if(e3)e3.textContent=relayStats.consensusHits;
    if(e4)e4.textContent=relayStats.totalTime>=60?Math.round(relayStats.totalTime/60)+'m':relayStats.totalTime+'s';
  }

  // ── Bridge Status ──
  function getBridgeStatus(){
    return {
      widgetConnected:isConnected,widgetSessionId:sessionId,
      aiChatAvailable:!!findChatEditor(),chatPanelOpen:isChatPanelOpen(),
      relayActive:relay.active,relayMode:relayMode,relayPass:relay.pass,
      maxTurns:maxRelayTurns,threadLength:relayThread.length,
      stats:relayStats,bridgeVersion:'4.2'
    };
  }

  // ── Global Bridge API ──
  window.__alfredBridge={
    sendToAIChat:sendToAIChat,
    sendToWidget:sendToWidget,
    getStatus:getBridgeStatus,
    toggleAutoRelay:function(on){
      autoRelayEnabled=typeof on==='boolean'?on:!autoRelayEnabled;
      var btn=document.getElementById('gcmRelayToggle');
      if(btn){btn.classList.toggle('active',autoRelayEnabled);btn.textContent=autoRelayEnabled?'\\ud83d\\udd04 ON':'\\ud83d\\udd04 Relay';}
      return autoRelayEnabled;
    },
    setMode:function(m){if(['collab','consensus','delegate'].indexOf(m)!==-1){relayMode=m;}},
    setMaxTurns:function(n){maxRelayTurns=Math.max(1,Math.min(5,n));var v=document.getElementById('gcmTurnsVal');if(v)v.textContent=maxRelayTurns;},
    cancelRelay:function(){if(relay.active)endRelay('\\u21a9\\ufe0f Cancelled \\u2014 new message');},
    clearThread:function(){relayThread=[];relayStats={relays:0,turns:0,consensusHits:0,totalTime:0};updateThreadUI();updateRelayStats();saveRelayState();},
    version:'4.2'
  };

  // ── Override addMsg for relay orchestration ──
  var _origAddMsg=addMsg;
  addMsg=function(role,text,opts){
    opts=opts||{};
    // Widget relay-chain response (don't add fwd button, just continue chain)
    if(role==='alfred'&&text&&text.length>5&&relay.active&&relay.waitingFor==='widget'&&!opts.relayed){
      _origAddMsg(role,text,opts);
      continueRelayFromWidget(text);
      return;
    }
    _origAddMsg(role,text,opts);
    if(role!=='alfred'||!text||text.length<=5)return;
    // Add forward button
    var msgs=chat.querySelectorAll('.gcm-msg.alfred');
    var lastMsg=msgs[msgs.length-1];
    if(lastMsg&&!lastMsg.querySelector('.gcm-fwd-btn')){
      var btn=document.createElement('button');
      btn.className='gcm-fwd-btn';
      btn.title='Forward to IDE AI Chat';
      btn.textContent='\\u27a1\\ufe0f IDE Chat';
      btn.style.cssText='display:inline-block;margin-top:6px;padding:3px 8px;font-size:.65rem;background:rgba(125,0,255,0.2);border:1px solid rgba(125,0,255,0.3);border-radius:8px;color:#a78bfa;cursor:pointer;transition:all .2s;float:right;';
      btn.onmouseover=function(){btn.style.background='rgba(125,0,255,0.4)';};
      btn.onmouseout=function(){btn.style.background='rgba(125,0,255,0.2)';};
      btn.onclick=function(){
        var r=sendToAIChat(text);
        if(r===true){btn.textContent='\\u2713 Sent';btn.style.color='#10b981';btn.disabled=true;}
        else if(r==='pending'){btn.textContent='\\u23f3\\u2026';btn.style.color='#f59e0b';}
      };
      lastMsg.appendChild(btn);
    }
    // Auto-relay: start new relay chain
    if(autoRelayEnabled&&!opts.relayed&&!relay.active){
      if(relayMode==='delegate'){
        var route=routeTask(lastUserMsg);
        if(route==='widget'){addMsg('system','\\ud83c\\udfaf Delegate: Alfred handles this alone');return;}
        if(route==='ide'){startRelayChain(text);return;}
      }
      startRelayChain(text);
    }
  };

  console.log('[Alfred Orchestrator] v4.3 \\u2014 Robust relay: no silent auto-disable, always-clickable relay button, enhanced bridge fallbacks');

  // ── Bridge Indicator ──
  var bridgeEl=document.getElementById('gcmBridgeStatus');
  var bridgeDot=document.getElementById('gcmBridgeDot');
  var relayBtn=document.getElementById('gcmRelayToggle');
  var _bridgeWarnShown=false;
  function updateBridgeIndicator(){
    var ok=!!findChatEditor();
    if(bridgeEl){bridgeEl.className='gcm-bridge-indicator '+(ok?'active':'inactive');bridgeEl.title=ok?'Bridge Active \\u2014 Relay ready':'Bridge Standby \\u2014 open AI Chat (Ctrl+Alt+I) to activate';}
    if(bridgeDot){bridgeDot.className='gcm-bridge-dot '+(ok?'active':'inactive');}
    // Always keep relay button clickable \\u2014 never auto-disable relay
    if(relayBtn){
      relayBtn.style.opacity=ok?'1':'0.6';
      relayBtn.style.pointerEvents='auto';
    }
    // If bridge reconnects while relay is on, clear the warning
    if(ok&&_bridgeWarnShown){_bridgeWarnShown=false;}
    // If bridge is down and relay is on, show a one-time hint (don\\u2019t disable relay)
    if(!ok&&autoRelayEnabled&&!_bridgeWarnShown){
      _bridgeWarnShown=true;
      addMsg('system','\\u26a0\\ufe0f Bridge standby \\u2014 open AI Chat panel (Ctrl+Alt+I) so Alfred Coder can join the relay');
    }
  }
  setInterval(updateBridgeIndicator,5000);
  setTimeout(updateBridgeIndicator,4000);

  // ── Relay Tab: Mode pills ──
  document.querySelectorAll('.gcm-mode-pill').forEach(function(p){
    p.addEventListener('click',function(){
      document.querySelectorAll('.gcm-mode-pill').forEach(function(x){x.classList.remove('active');});
      p.classList.add('active');
      relayMode=p.dataset.mode;
      addMsg('system','\\ud83d\\udd04 Mode: '+relayMode.charAt(0).toUpperCase()+relayMode.slice(1));
      saveRelayState();
    });
  });

  // ── Relay Tab: Turns control ──
  var tvEl=document.getElementById('gcmTurnsVal');
  var tmBtn=document.getElementById('gcmTurnsMinus');
  var tpBtn=document.getElementById('gcmTurnsPlus');
  if(tmBtn)tmBtn.addEventListener('click',function(){maxRelayTurns=Math.max(1,maxRelayTurns-1);if(tvEl)tvEl.textContent=maxRelayTurns;saveRelayState();});
  if(tpBtn)tpBtn.addEventListener('click',function(){maxRelayTurns=Math.min(5,maxRelayTurns+1);if(tvEl)tvEl.textContent=maxRelayTurns;saveRelayState();});

  // ── Relay Tab: Clear thread ──
  var clrBtn=document.getElementById('gcmClearThread');
  if(clrBtn)clrBtn.addEventListener('click',function(){
    relayThread=[];relayStats={relays:0,turns:0,consensusHits:0,totalTime:0};
    updateThreadUI();updateRelayStats();saveRelayState();
    addMsg('system','\\ud83d\\uddd1\\ufe0f Thread cleared');
  });

  // Init: restore persisted state into UI
  updateThreadUI();
  updateRelayStats();
  // Restore mode pill selection
  document.querySelectorAll('.gcm-mode-pill').forEach(function(p){
    p.classList.toggle('active',p.dataset.mode===relayMode);
  });
  // Restore turns value
  if(tvEl)tvEl.textContent=maxRelayTurns;
})();

// ═══════════════════════════════════════════════════════════════════════════
// Alfred Autopilot — Live Browser Stream Panel v8.0
// ═══════════════════════════════════════════════════════════════════════════
(function(){
  var overlay=document.getElementById('gcmAutopilotOverlay');
  var viewport=document.getElementById('gcmApViewport');
  var screenImg=document.getElementById('gcmApScreen');
  var placeholder=document.getElementById('gcmApPlaceholder');
  var statusEl=document.getElementById('gcmApStatus');
  var urlEl=document.getElementById('gcmApUrl');
  var stepCountEl=document.getElementById('gcmApStepCount');
  var footerSteps=document.getElementById('gcmApFooterSteps');
  var actionsEl=document.getElementById('gcmApActions');
  var closeBtn=document.getElementById('gcmApClose');
  var cursorEl=document.getElementById('gcmApCursor');
  var guardrailEl=document.getElementById('gcmApGuardrail');
  var tabsBar=document.getElementById('gcmApTabsBar');
  var approvalEl=document.getElementById('gcmApApproval');
  var approvalText=document.getElementById('gcmApApprovalText');
  var approveBtn=document.getElementById('gcmApApproveBtn');
  var rejectBtn=document.getElementById('gcmApRejectBtn');
  var vpControls=document.getElementById('gcmApVpControls');
  var netToggle=document.getElementById('gcmApNetworkToggle');
  var netPanel=document.getElementById('gcmApNetworkPanel');
  var netCount=document.getElementById('gcmApNetCount');
  // v8.0 elements
  var sentimentEl=document.getElementById('gcmApSentiment');
  var undoBtn=document.getElementById('gcmApUndoBtn');
  var confidenceBar=document.getElementById('gcmApConfidence');
  var celebrationEl=document.getElementById('gcmApCelebration');
  var frustrationEl=document.getElementById('gcmApFrustration');
  var narrationEl=document.getElementById('gcmApNarration');
  var annotationsEl=document.getElementById('gcmApAnnotations');
  var spectatorsEl=document.getElementById('gcmApSpectators');
  var batchBar=document.getElementById('gcmApBatchBar');
  var batchText=document.getElementById('gcmApBatchText');
  var batchFill=document.getElementById('gcmApBatchFill');
  var apWs=null;
  var apActive=false;
  var stepNum=0;
  var currentVp='desktop';
  var netShown=false;

  // Viewport pill clicks
  vpControls.addEventListener('click',function(e){
    var pill=e.target.closest('.gcm-ap-vp-pill');
    if(!pill)return;
    var preset=pill.dataset.vp;
    vpControls.querySelectorAll('.gcm-ap-vp-pill').forEach(function(p){p.classList.remove('active');});
    pill.classList.add('active');
    currentVp=preset;
    if(apWs&&apWs.readyState===1){
      apWs.send(JSON.stringify({type:'viewport',preset:preset}));
    }
  });

  // Network panel toggle
  netToggle.addEventListener('click',function(){
    netShown=!netShown;
    netPanel.style.display=netShown?'block':'none';
    netToggle.textContent=(netShown?'▼':'▶')+' Network ';
    netToggle.appendChild(netCount);
  });

  // Approval buttons
  approveBtn.addEventListener('click',function(){
    if(apWs&&apWs.readyState===1){
      apWs.send(JSON.stringify({type:'approve'}));
    }
    approvalEl.classList.remove('visible');
    statusEl.textContent='Acting…';statusEl.className='gcm-ap-status acting';
  });
  rejectBtn.addEventListener('click',function(){
    if(apWs&&apWs.readyState===1){
      apWs.send(JSON.stringify({type:'reject'}));
    }
    approvalEl.classList.remove('visible');
    statusEl.textContent='Live';statusEl.className='gcm-ap-status';
  });

  // v8.0: Undo button
  undoBtn.addEventListener('click',function(){
    if(apWs&&apWs.readyState===1){
      apWs.send(JSON.stringify({type:'undo'}));
    }
  });

  // Keyboard shortcuts — v8.0 enhanced
  document.addEventListener('keydown',function(e){
    if(!overlay.classList.contains('open'))return;
    if(e.key==='Escape'){overlay.classList.remove('open');if(apWs)try{apWs.close();}catch(x){}apWs=null;}
    // Ctrl+Z = undo
    if((e.ctrlKey||e.metaKey)&&e.key==='z'&&!e.shiftKey){
      e.preventDefault();
      if(apWs&&apWs.readyState===1)apWs.send(JSON.stringify({type:'undo'}));
    }
    // Enter = approve, Backspace = reject (when approval visible)
    if(approvalEl.classList.contains('visible')){
      if(e.key==='Enter'){e.preventDefault();approveBtn.click();}
      if(e.key==='Backspace'||e.key==='Delete'){e.preventDefault();rejectBtn.click();}
    }
  });

  function updateCursor(x,y){
    if(!x&&!y){cursorEl.classList.add('hidden');return;}
    cursorEl.classList.remove('hidden');
    var imgRect=screenImg.getBoundingClientRect();
    var vpRect=viewport.getBoundingClientRect();
    if(imgRect.width>0){
      var scaleX=imgRect.width/(screenImg.naturalWidth||1280);
      var scaleY=imgRect.height/(screenImg.naturalHeight||800);
      var left=imgRect.left-vpRect.left+x*scaleX;
      var top=imgRect.top-vpRect.top+y*scaleY;
      cursorEl.style.left=left+'px';
      cursorEl.style.top=top+'px';
    }
  }

  function updateTabBar(tabs){
    if(!tabs||tabs.length<=1){tabsBar.style.display='none';return;}
    tabsBar.style.display='flex';
    tabsBar.innerHTML='';
    tabs.forEach(function(t){
      var el=document.createElement('span');
      el.className='gcm-ap-tab'+(t.active?' active':'');
      var label=t.url;
      try{label=new URL(t.url).hostname;}catch(e){}
      el.textContent='Tab '+(t.index+1)+': '+label;
      el.title=t.url;
      el.addEventListener('click',function(){
        if(apWs&&apWs.readyState===1){
          apWs.send(JSON.stringify({type:'switch_tab',tabIndex:t.index}));
        }
      });
      tabsBar.appendChild(el);
    });
  }

  // v8.0: Update sentiment indicator
  function updateSentiment(sentiment){
    if(!sentimentEl)return;
    sentimentEl.className='gcm-ap-sentiment '+(sentiment||'neutral');
    var labels={progressing:'Progressing',neutral:'Neutral',stuck:'Stuck',failing:'Failing'};
    sentimentEl.textContent=labels[sentiment]||sentiment||'Neutral';
  }

  // v8.0: Update confidence bar
  function updateConfidence(score){
    if(!confidenceBar)return;
    var level=score>=0.7?'high':score>=0.4?'medium':'low';
    confidenceBar.className='gcm-ap-confidence-bar '+level;
  }

  // v8.0: Show celebration animation
  function showCelebration(msg){
    if(!celebrationEl)return;
    celebrationEl.innerHTML='';
    var colors=['#10b981','#fbbf24','#8b5cf6','#3b82f6','#ef4444','#ec4899'];
    for(var i=0;i<30;i++){
      var c=document.createElement('div');
      c.className='gcm-ap-confetti';
      c.style.background=colors[i%colors.length];
      c.style.left=Math.random()*100+'%';
      c.style.animationDelay=Math.random()*0.5+'s';
      c.style.animationDuration=(1.5+Math.random())+'s';
      celebrationEl.appendChild(c);
    }
    celebrationEl.classList.add('active');
    setTimeout(function(){celebrationEl.classList.remove('active');celebrationEl.innerHTML='';},2500);
    if(narrationEl)narrationEl.textContent=msg||'Task completed successfully!';
  }

  // v8.0: Show frustration alert
  function showFrustration(msg){
    if(!frustrationEl)return;
    frustrationEl.textContent=msg||'Detected repeated difficulties';
    frustrationEl.classList.add('visible');
    setTimeout(function(){frustrationEl.classList.remove('visible');},5000);
  }

  // v8.0: Render annotations
  function renderAnnotations(annotations){
    if(!annotationsEl)return;
    annotationsEl.innerHTML='';
    if(!annotations||!annotations.length)return;
    var imgRect=screenImg.getBoundingClientRect();
    var vpRect=viewport.getBoundingClientRect();
    if(imgRect.width<=0)return;
    var scaleX=imgRect.width/(screenImg.naturalWidth||1280);
    var scaleY=imgRect.height/(screenImg.naturalHeight||800);
    annotations.forEach(function(a){
      var dot=document.createElement('div');
      dot.className='gcm-ap-annotation';
      dot.style.borderColor=a.color||'#fbbf24';
      dot.style.background=(a.color||'#fbbf24')+'80';
      dot.style.left=(imgRect.left-vpRect.left+a.x*scaleX)+'px';
      dot.style.top=(imgRect.top-vpRect.top+a.y*scaleY)+'px';
      dot.style.pointerEvents='auto';
      dot.textContent=a.id||'';
      if(a.text){
        var tip=document.createElement('span');
        tip.className='gcm-ap-annotation-tip';
        tip.textContent=a.text;
        dot.appendChild(tip);
      }
      annotationsEl.appendChild(dot);
    });
  }

  // v8.0: Update batch bar
  function updateBatch(status){
    if(!batchBar||!status)return;
    if(!status.active){batchBar.classList.remove('active');return;}
    batchBar.classList.add('active');
    batchText.textContent='Batch: '+status.completed+'/'+status.total;
    var pct=status.total>0?Math.round(status.completed/status.total*100):0;
    batchFill.style.width=pct+'%';
  }

  // v8.0: Update spectator count
  function updateSpectators(count){
    if(!spectatorsEl)return;
    if(count>1){
      spectatorsEl.textContent='👁 '+count;
      spectatorsEl.classList.add('active');
    }else{spectatorsEl.classList.remove('active');}
  }

  // Expose global API for autopilot tools to trigger the panel
  window.__alfredAutopilot={
    open:function(task){
      overlay.classList.add('open');
      apActive=true;
      stepNum=0;
      actionsEl.innerHTML='';
      placeholder.style.display='flex';
      screenImg.style.display='none';
      cursorEl.classList.add('hidden');
      statusEl.textContent='Starting…';
      statusEl.className='gcm-ap-status acting';
      urlEl.textContent='';
      stepCountEl.textContent='0 steps';
      guardrailEl.textContent='';
      footerSteps.textContent='Task: '+task;
      approvalEl.classList.remove('visible');
      tabsBar.style.display='none';
      updateSentiment('neutral');
      updateConfidence(1.0);
      undoBtn.classList.remove('available');
      if(batchBar)batchBar.classList.remove('active');
      if(annotationsEl)annotationsEl.innerHTML='';
      connectAutopilotStream();
    },
    close:function(reason){
      apActive=false;
      statusEl.textContent='Stopped';
      statusEl.className='gcm-ap-status idle';
      footerSteps.textContent=reason||'Session ended';
      cursorEl.classList.add('hidden');
      approvalEl.classList.remove('visible');
      if(apWs){try{apWs.close();}catch(e){}}
      apWs=null;
    },
    addAction:function(step,action,desc,isCurrent){
      stepNum=step||stepNum+1;
      var item=document.createElement('div');
      item.className='gcm-ap-action-item '+(isCurrent?'current':'done');
      item.innerHTML='<span class="ap-step">'+stepNum+'</span><span>'+(desc||action)+'</span>';
      var prev=actionsEl.querySelectorAll('.gcm-ap-action-item.current');
      for(var i=0;i<prev.length;i++)prev[i].className='gcm-ap-action-item done';
      actionsEl.appendChild(item);
      item.scrollIntoView({behavior:'smooth',block:'end'});
      stepCountEl.textContent=stepNum+' step'+(stepNum!==1?'s':'');
    },
    updateUrl:function(url,title){
      urlEl.textContent=url||'';
      if(title)urlEl.title=title;
    },
    setStatus:function(s){
      if(s==='acting'){statusEl.textContent='Acting…';statusEl.className='gcm-ap-status acting';}
      else if(s==='observing'){statusEl.textContent='Observing';statusEl.className='gcm-ap-status';}
      else if(s==='paused'){statusEl.textContent='⏸ Paused';statusEl.className='gcm-ap-status paused';}
      else{statusEl.textContent='Live';statusEl.className='gcm-ap-status';}
    },
    isOpen:function(){return overlay.classList.contains('open');},
  };

  closeBtn.addEventListener('click',function(){
    overlay.classList.remove('open');
    if(apWs){try{apWs.close();}catch(e){}}
    apWs=null;
  });

  function connectAutopilotStream(){
    if(apWs)try{apWs.close();}catch(e){}
    var proto=location.protocol==='https:'?'wss:':'ws:';
    var wsUrl=proto+'//'+location.host+'/middleware/api/autopilot/stream?user='+encodeURIComponent(AUTH_USER);
    apWs=new WebSocket(wsUrl);
    apWs.binaryType='arraybuffer';
    apWs.onopen=function(){console.log('[Autopilot] Stream connected');};
    apWs.onmessage=function(ev){
      if(ev.data instanceof ArrayBuffer){
        var data=new Uint8Array(ev.data);
        if(data.length>9&&data[0]===0x01){
          var cx=(data[1]<<8)|data[2];
          var cy=(data[3]<<8)|data[4];
          var step=(data[5]<<24)|(data[6]<<16)|(data[7]<<8)|data[8];
          var jpegData=data.slice(9);
          var blob=new Blob([jpegData],{type:'image/jpeg'});
          var url=URL.createObjectURL(blob);
          var oldUrl=screenImg.src;
          screenImg.src=url;
          screenImg.style.display='block';
          placeholder.style.display='none';
          if(oldUrl&&oldUrl.startsWith('blob:'))URL.revokeObjectURL(oldUrl);
          if(cx||cy){updateCursor(cx,cy);}
          stepNum=step;
        }else{
          var blob2=new Blob([ev.data],{type:'image/jpeg'});
          var url2=URL.createObjectURL(blob2);
          var oldUrl2=screenImg.src;
          screenImg.src=url2;
          screenImg.style.display='block';
          placeholder.style.display='none';
          if(oldUrl2&&oldUrl2.startsWith('blob:'))URL.revokeObjectURL(oldUrl2);
        }
      }else{
        try{
          var msg=JSON.parse(ev.data);
          if(msg.type==='action'){
            window.__alfredAutopilot.addAction(msg.step,msg.action,msg.description,true);
            if(msg.url)window.__alfredAutopilot.updateUrl(msg.url);
            window.__alfredAutopilot.setStatus('acting');
            if(msg.coords)updateCursor(msg.coords.x,msg.coords.y);
            // v8.0: Narrate action for screen readers
            if(narrationEl)narrationEl.textContent='Step '+msg.step+': '+(msg.description||msg.action);
          }else if(msg.type==='observe'){
            window.__alfredAutopilot.updateUrl(msg.url,msg.title);
            window.__alfredAutopilot.setStatus('observing');
          }else if(msg.type==='stopped'){
            window.__alfredAutopilot.close(msg.reason||'Session ended');
          }else if(msg.type==='started'||msg.type==='session_info'){
            if(!overlay.classList.contains('open')){
              overlay.classList.add('open');
              apActive=true;
            }
            statusEl.textContent='Starting…';
            statusEl.className='gcm-ap-status acting';
            footerSteps.textContent='Task: '+(msg.task||'Web task');
            if(msg.guardrails){
              var remain=msg.guardrails.stepsRemaining||'?';
              guardrailEl.textContent=remain+' steps left';
            }
            if(msg.tabs)updateTabBar(msg.tabs);
            // v8.0: Initialize human-centric state from session_info
            if(msg.sentiment)updateSentiment(msg.sentiment);
            if(msg.confidence!==undefined)updateConfidence(msg.confidence);
            if(msg.spectators)updateSpectators(msg.spectators);
            if(msg.undoAvailable)undoBtn.classList.add('available');else undoBtn.classList.remove('available');
            if(msg.annotations)renderAnnotations(msg.annotations);
            if(msg.batchStatus)updateBatch(msg.batchStatus);
          }else if(msg.type==='approval_required'){
            var approvalMsg=msg.message||'Alfred wants to perform an action';
            if(msg.confidence!==undefined){
              updateConfidence(msg.confidence);
              approvalMsg+=' (confidence: '+Math.round(msg.confidence*100)+'%)';
            }
            approvalText.textContent=approvalMsg;
            approvalEl.classList.add('visible');
            window.__alfredAutopilot.setStatus('paused');
            if(narrationEl)narrationEl.textContent='Action requires approval: '+approvalMsg;
          }else if(msg.type==='sentiment_changed'){
            // v8.0: Sentiment change
            updateSentiment(msg.sentiment);
            if(narrationEl&&msg.message)narrationEl.textContent=msg.message;
          }else if(msg.type==='frustration_detected'){
            // v8.0: Frustration alert
            showFrustration(msg.message);
            if(narrationEl)narrationEl.textContent='Warning: '+msg.message;
          }else if(msg.type==='celebration'){
            // v8.0: Celebration animation
            showCelebration(msg.message);
          }else if(msg.type==='narration'){
            // v8.0: ARIA live narration update
            if(narrationEl)narrationEl.textContent=msg.text||'';
          }else if(msg.type==='annotation_added'||msg.type==='annotation_removed'||msg.type==='annotations_cleared'){
            // v8.0: Re-render annotations (fetch current list)
            fetch('/middleware/api/autopilot/annotations?user='+encodeURIComponent(AUTH_USER))
              .then(function(r){return r.json();})
              .then(function(d){if(d.annotations)renderAnnotations(d.annotations);})
              .catch(function(){});
          }else if(msg.type==='tab_opened'||msg.type==='tab_switched'){
            if(msg.tabs)updateTabBar(msg.tabs);
            else if(msg.totalTabs>1){
              fetch('/middleware/api/autopilot/tabs?user='+encodeURIComponent(AUTH_USER))
                .then(function(r){return r.json();})
                .then(function(d){if(d.tabs)updateTabBar(d.tabs);})
                .catch(function(){});
            }
          }else if(msg.type==='viewport_changed'){
            vpControls.querySelectorAll('.gcm-ap-vp-pill').forEach(function(p){
              p.classList.toggle('active',p.dataset.vp===msg.preset);
            });
          }else if(msg.type==='download'){
            window.__alfredAutopilot.addAction(stepNum,'download','📥 Downloaded: '+msg.filename,false);
          }
        }catch(e){console.warn('[Autopilot] Bad message',e);}
      }
    };
    apWs.onclose=function(){
      console.log('[Autopilot] Stream closed');
      if(apActive){setTimeout(connectAutopilotStream,2000);}
    };
    apWs.onerror=function(e){console.error('[Autopilot] Stream error',e);};
  }
})();

// ── Conversation Compacting ─────────────────────────────────────────────────
// Shows a banner when context is filling up, lets user compact to continue
// chatting in the same session without losing important context.
(function(){
  var compactBanner=document.getElementById('gcmCompactBanner');
  var compactBtn=document.getElementById('gcmCompactBtn');
  var compactPct=document.getElementById('gcmCompactPct');
  if(!compactBanner||!compactBtn)return;

  var lastContextPct=0;
  var compacting=false;

  // Monitor XHR/fetch responses for x-context-usage header
  var origFetch=window.fetch;
  window.fetch=function(){
    return origFetch.apply(this,arguments).then(function(resp){
      try{
        var ctx=resp.headers.get('x-context-usage');
        if(ctx){updateContextBanner(JSON.parse(ctx));}
      }catch(e){}
      return resp;
    });
  };

  // Also intercept SSE responses — check for context-usage in initial headers
  var origXHR=XMLHttpRequest.prototype.open;
  XMLHttpRequest.prototype.open=function(){
    var xhr=this;
    xhr.addEventListener('readystatechange',function(){
      if(xhr.readyState>=2){
        try{
          var ctx=xhr.getResponseHeader('x-context-usage');
          if(ctx){updateContextBanner(JSON.parse(ctx));}
        }catch(e){}
      }
    });
    return origXHR.apply(this,arguments);
  };

  function updateContextBanner(ctx){
    if(!ctx||typeof ctx.percent!=='number')return;
    lastContextPct=ctx.percent;
    if(ctx.level==='critical'){
      compactBanner.style.display='flex';
      compactBanner.classList.add('critical');
      compactPct.textContent=ctx.percent;
    }else if(ctx.level==='warning'){
      compactBanner.style.display='flex';
      compactBanner.classList.remove('critical');
      compactPct.textContent=ctx.percent;
    }else{
      compactBanner.style.display='none';
    }
  }

  compactBtn.addEventListener('click',function(){
    if(compacting)return;
    compacting=true;
    compactBtn.disabled=true;
    compactBtn.textContent='Compacting…';

    // Get conversation messages from Theia's internal state
    // The widget stores messages — we need to tell the IDE to compact
    // by calling the compact endpoint with the session token
    var token=window.__gcmSessionToken||'';
    var msgs=collectChatMessages();

    origFetch('/middleware/api/anthropic-proxy/compact',{
      method:'POST',
      headers:{'Content-Type':'application/json','Authorization':'Bearer '+token},
      body:JSON.stringify({messages:msgs})
    }).then(function(r){return r.json();}).then(function(data){
      compacting=false;
      compactBtn.disabled=false;
      compactBtn.textContent='Compact';
      if(data.ok&&data.compacted){
        addMsg('system','📦 Conversation compacted: '+data.originalCount+' → '+data.newCount+' messages ('+data.summarized+' summarized). Context now at '+data.context.percent+'%.');
        if(data.context.percent<75){compactBanner.style.display='none';}
        else{compactPct.textContent=data.context.percent;}
        // Notify Theia IDE to replace its message history
        if(window.__theiaCompactHandler){
          window.__theiaCompactHandler(data.messages);
        }
      }else{
        addMsg('system','📦 '+(data.reason||'Nothing to compact.'));
      }
    }).catch(function(e){
      compacting=false;
      compactBtn.disabled=false;
      compactBtn.textContent='Compact';
      addMsg('system','⚠️ Compact failed: '+e.message);
    });
  });

  // Collect messages from widget chat history for compact API
  function collectChatMessages(){
    var msgs=[];
    var chatEl=document.getElementById('gcmChat');
    if(!chatEl)return msgs;
    var nodes=chatEl.querySelectorAll('.gcm-msg');
    for(var i=0;i<nodes.length;i++){
      var el=nodes[i];
      var role='user';
      if(el.classList.contains('alfred')||el.classList.contains('ide-alfred'))role='assistant';
      else if(el.classList.contains('system'))continue; // skip system messages
      msgs.push({role:role,content:el.textContent||''});
    }
    return msgs;
  }
})();

// ── v9.0: Context Usage Bar ─────────────────────────────────────────────────
(function(){
  var ctxFill=document.getElementById('gcmCtxFill');
  var ctxLabel=document.getElementById('gcmCtxLabel');
  if(!ctxFill||!ctxLabel)return;

  window.__gcmUpdateContextBar=function(ctx){
    if(!ctx||typeof ctx.percent!=='number')return;
    var pct=Math.min(ctx.percent,100);
    ctxFill.style.width=pct+'%';
    ctxLabel.textContent=pct+'%';
    ctxFill.classList.remove('warn','critical');
    if(pct>=90)ctxFill.classList.add('critical');
    else if(pct>=70)ctxFill.classList.add('warn');
  };

  // Hook into the existing fetch/XHR interceptors
  var origFetch2=window.fetch;
  window.fetch=function(){
    return origFetch2.apply(this,arguments).then(function(resp){
      try{var c=resp.headers.get('x-context-usage');if(c)window.__gcmUpdateContextBar(JSON.parse(c));}catch(e){}
      return resp;
    });
  };
})();

// ── v9.1: Full model popup (grouped providers, credits, multipliers) ───────
(function(){
  var badge=document.getElementById('gcmModelBadge');
  var popup=document.getElementById('gcmModelPopup');
  if(!badge||!popup||typeof modelDefs==='undefined')return;
  var _h={'Content-Type':'application/json'};
  if(window.__gcmSessionToken)_h['Authorization']='Bearer '+window.__gcmSessionToken;
  var provEmoji={Anthropic:'🟣',OpenAI:'🟢',Google:'🔵',xAI:'🚀','Open Source':'🔶',Groq:'🆓','Video Gen':'🎬'};
  var provOrder=['Anthropic','OpenAI','Google','xAI','Open Source','Groq','Video Gen'];
  var multMap={},km;
  if(window.__gcmTokenMultDefaults)for(km in window.__gcmTokenMultDefaults)if(Object.prototype.hasOwnProperty.call(window.__gcmTokenMultDefaults,km))multMap[km]=window.__gcmTokenMultDefaults[km];
  var emojiMap={'claude-opus-4-6':'🧠','claude-sonnet-4-6':'⚡','claude-haiku-4-5':'💨','gpt-4.1':'🟢','gpt-4.1-mini':'🟡','gpt-4.1-nano':'⚡','gpt-4o':'🌈','o3':'🔮','o3-mini':'💎','o4-mini':'✨','gemini-3.1-pro':'🌐','gemini-3-flash':'⚡','gemini-3.1-flash-lite':'🪶','gemini-image':'🍌','gemini-2.5-pro':'💎','gemini-2.5-flash':'💡','grok-3':'🚀','grok-3-mini':'⚡','qwen3-coder':'🔧','qwen3-coder-480b':'🏗️','qwen3.5':'🌟','deepseek-v3':'🌊','deepseek-r1':'🧪','glm-5':'🔶','kimi-k2.5':'🌙','kimi-k2-thinking':'🧠','llama-4-maverick':'🦙','llama-4-scout':'🔍','mistral-small':'🇫🇷','groq-llama-3.3-70b':'🆓','groq-llama-3.1-8b':'🆓','veo-2-video':'🎬'};
  var curModel='auto';
  var editMid=null;

  function findM(id){for(var i=0;i<modelDefs.length;i++)if(modelDefs[i].id===id)return modelDefs[i];return null;}
  function tierCls(t){if(t==='Free')return'free';if(t==='Premium'||t==='Reasoning')return'premium';if(t==='Economy')return'economy';return'standard';}
  function tierLbl(t){if(t==='Free')return'🆓 Free';if(t==='Premium'||t==='Reasoning')return'★ Premium';if(t==='Economy')return'○ Economy';return'● Standard';}
  function fmtMult(v){return(v===Math.floor(v)?String(v):String(v))+'×';}

  function render(){
    var byP={},i,p,list,j,m,em,mult,h;
    for(i=0;i<modelDefs.length;i++){m=modelDefs[i];if(!byP[m.p])byP[m.p]=[];byP[m.p].push(m);}
    h='<div class="gcm-popup-auto'+(curModel==='auto'?' active':'')+'" data-pick="auto"><span style="font-size:15px">🤖</span><span>Auto (Smart Routing)</span></div>';
    h+='<div class="gcm-credit-balance"><span>💳</span><span>Credit Balance</span><span class="gcm-credit-amount" id="gcmCredAmt">—</span></div>';
    for(p=0;p<provOrder.length;p++){
      list=byP[provOrder[p]];if(!list||!list.length)continue;
      h+='<div class="gcm-popup-group">'+provEmoji[provOrder[p]]+' '+(provOrder[p]==='Groq'?'Groq (Free)':provOrder[p])+'</div>';
      for(j=0;j<list.length;j++){
        m=list[j];em=emojiMap[m.id]||'⚡';mult=multMap.hasOwnProperty(m.id)?multMap[m.id]:1;
        h+='<div class="gcm-popup-item'+(curModel===m.id?' active':'')+'" data-pick="'+m.id+'">';
        h+='<span class="gcm-item-emoji">'+em+'</span><span class="gcm-item-name">'+m.n+'</span>';
        h+='<span class="gcm-item-tier '+tierCls(m.t)+'">'+tierLbl(m.t)+'</span>';
        h+='<span class="gcm-multiplier-badge" data-mid="'+m.id+'" title="Multiplier (owner can edit)">'+fmtMult(mult)+'</span></div>';
      }
    }
    h+='<div class="gcm-multiplier-editor" id="gcmMultEdit"><span style="font-size:.62rem;color:rgba(255,255,255,0.4);width:100%;margin-bottom:4px">Owner: set token multiplier</span>';
    h+='<input type="number" id="gcmMultIn" min="0.1" max="999" step="0.1" value="1">';
    h+='<button type="button" class="gcm-mult-preset" data-v="1">1×</button><button type="button" class="gcm-mult-preset" data-v="12">12×</button><button type="button" class="gcm-mult-preset" data-v="30">30×</button>';
    h+='<button type="button" class="gcm-mult-preset" data-v="60">60×</button><button type="button" class="gcm-mult-preset" data-v="100">100×</button><button type="button" class="gcm-mult-preset" data-v="600">600×</button></div>';
    popup.innerHTML=h;
    fetch('/middleware/api/usage/credits',{headers:_h,credentials:'include'}).then(function(r){return r.json();}).then(function(d){
      var el=document.getElementById('gcmCredAmt');if(el&&d.ok&&typeof d.balance==='number')el.textContent='$'+d.balance.toFixed(2);
    }).catch(function(){});
    fetch('/middleware/api/usage/multipliers',{headers:_h,credentials:'include'}).then(function(r){return r.json();}).then(function(d){
      if(!d.ok||!d.multipliers)return;
      Object.keys(d.multipliers).forEach(function(k){
        var node=popup.querySelector('.gcm-multiplier-badge[data-mid="'+k+'"]');
        if(node&&d.multipliers[k]&&typeof d.multipliers[k].multiplier==='number'){multMap[k]=d.multipliers[k].multiplier;node.textContent=fmtMult(d.multipliers[k].multiplier);}
      });
    }).catch(function(){});
    popup.querySelector('[data-pick="auto"]').addEventListener('click',function(e){e.stopPropagation();pick('auto');});
    popup.querySelectorAll('.gcm-popup-item[data-pick]').forEach(function(el){
      el.addEventListener('click',function(e){
        if(e.target.closest('.gcm-multiplier-badge'))return;
        e.stopPropagation();pick(el.getAttribute('data-pick'));
      });
    });
    var med=document.getElementById('gcmMultEdit');
    popup.querySelectorAll('.gcm-multiplier-badge').forEach(function(b){
      b.addEventListener('click',function(e){
        e.stopPropagation();editMid=b.getAttribute('data-mid');
        med.classList.add('open');document.getElementById('gcmMultIn').value=multMap[editMid]||1;
      });
    });
    popup.querySelectorAll('.gcm-mult-preset').forEach(function(btn){
      btn.addEventListener('click',function(e){
        e.stopPropagation();var v=parseFloat(btn.getAttribute('data-v'));if(!editMid)return;
        fetch('/middleware/api/usage/multiplier/'+encodeURIComponent(editMid),{method:'PUT',headers:_h,credentials:'include',body:JSON.stringify({multiplier:v})}).then(function(r){return r.json();}).then(function(d){
          if(d.ok){multMap[editMid]=v;var nb=popup.querySelector('.gcm-multiplier-badge[data-mid="'+editMid+'"]');if(nb)nb.textContent=fmtMult(v);med.classList.remove('open');}
        }).catch(function(){});
      });
    });
    document.getElementById('gcmMultIn').addEventListener('change',function(e){
      e.stopPropagation();if(!editMid)return;var v=parseFloat(e.target.value);
      if(!Number.isFinite(v)||v<=0)return;
      fetch('/middleware/api/usage/multiplier/'+encodeURIComponent(editMid),{method:'PUT',headers:_h,credentials:'include',body:JSON.stringify({multiplier:v})}).then(function(r){return r.json();}).then(function(d){
        if(d.ok){multMap[editMid]=v;var nb=popup.querySelector('.gcm-multiplier-badge[data-mid="'+editMid+'"]');if(nb)nb.textContent=fmtMult(v);med.classList.remove('open');}
      }).catch(function(){});
    });
  }

  function pick(id){
    curModel=id;
    fetch('/middleware/api/usage/set-model',{method:'POST',headers:_h,body:JSON.stringify({model:id}),credentials:'include'}).catch(function(){});
    if(id==='auto'){badge.textContent='🤖 Auto';window.__gcmSelectedModel='auto';}
    else{var mm=findM(id);badge.innerHTML=(emojiMap[id]||'⚡')+' <span style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:inline-block;vertical-align:bottom">'+(mm?mm.n:'')+'</span>';window.__gcmSelectedModel=id;}
    popup.classList.remove('open');
    var pills=document.querySelectorAll('#gcmModePills .gcm-pill');
    if(pills.length)pills.forEach(function(p){p.classList.toggle('active',p.dataset.mode===id||(id!=='auto'&&findM(id)&&(p.dataset.mode==='opus'&&id.indexOf('opus')>=0||p.dataset.mode==='sonnet'&&id.indexOf('sonnet')>=0||p.dataset.mode==='haiku'&&id.indexOf('haiku')>=0)));});
  }

  badge.addEventListener('click',function(e){
    e.stopPropagation();
    if(popup.classList.contains('open')){popup.classList.remove('open');return;}
    render();
    var r=badge.getBoundingClientRect(),ph=Math.min(520,window.innerHeight*0.68);
    popup.style.right=(document.documentElement.clientWidth-r.right)+'px';
    popup.style.bottom=(window.innerHeight-r.top+10)+'px';
    popup.style.top='auto';popup.style.left='auto';popup.style.maxHeight=ph+'px';
    popup.classList.add('open');
  });
  popup.addEventListener('click',function(e){e.stopPropagation();});
  document.addEventListener('click',function(){popup.classList.remove('open');});
  fetch('/middleware/api/usage/current-model',{headers:_h,credentials:'include'}).then(function(r){return r.json();}).then(function(d){
    if(d.ok&&d.model){curModel=d.model;if(d.model==='auto')badge.textContent='🤖 Auto';
    else{var mm=findM(d.model);if(mm)badge.innerHTML=(emojiMap[d.model]||'⚡')+' <span style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:inline-block;vertical-align:bottom">'+mm.n+'</span>';}
    window.__gcmSelectedModel=d.model;}
  }).catch(function(){});
})();

// ── v9.0: Snippet Save ─────────────────────────────────────────────────────
(function(){
  // Add snippet save buttons to AI response messages
  var chatEl=document.getElementById('gcmChat');
  if(!chatEl)return;
  var observer=new MutationObserver(function(muts){
    muts.forEach(function(m){
      m.addedNodes.forEach(function(node){
        if(node.nodeType===1&&node.classList&&node.classList.contains('gcm-msg')&&(node.classList.contains('alfred')||node.classList.contains('ide-alfred'))){
          var btn=document.createElement('button');
          btn.className='gcm-snippet-btn';
          btn.textContent='💾 Save';
          btn.title='Save this response as a code snippet';
          btn.addEventListener('click',function(){
            var content=node.textContent||'';
            var name=prompt('Snippet name:','snippet-'+(Date.now()%10000));
            if(!name)return;
            try{
              var key='gcm_snippets';
              var snippets=JSON.parse(localStorage.getItem(key)||'[]');
              snippets.unshift({name:name,content:content.replace('💾 Save','').trim(),created:new Date().toISOString()});
              if(snippets.length>100)snippets=snippets.slice(0,100);
              localStorage.setItem(key,JSON.stringify(snippets));
              btn.textContent='✅ Saved!';
              setTimeout(function(){btn.textContent='💾 Save';},2000);
            }catch(e){alert('Save failed: '+e.message);}
          });
          node.appendChild(btn);
        }
      });
    });
  });
  observer.observe(chatEl,{childList:true});
})();

// ── v9.0: Auto-Reconnect ───────────────────────────────────────────────────
(function(){
  var banner=document.getElementById('gcmReconnectBanner');
  var bannerText=document.getElementById('gcmReconnectText');
  var connStatus=document.getElementById('gcmConnStatus');
  if(!banner)return;

  var reconnectAttempts=0;
  var maxAttempts=5;
  var lastConnected=true;

  function checkConnection(){
    fetch('/middleware/api/health',{method:'GET',cache:'no-store'}).then(function(r){
      if(r.ok){
        if(!lastConnected){
          banner.classList.remove('active');
          if(connStatus)connStatus.textContent='Online';
          if(connStatus)connStatus.className='gcm-head-status online';
          reconnectAttempts=0;
          addMsg('system','✅ Reconnected to Alfred');
        }
        lastConnected=true;
      }else{throw new Error('not ok');}
    }).catch(function(){
      lastConnected=false;
      reconnectAttempts++;
      if(reconnectAttempts<=maxAttempts){
        banner.classList.add('active');
        bannerText.textContent='Reconnecting... ('+reconnectAttempts+'/'+maxAttempts+')';
        if(connStatus)connStatus.textContent='Reconnecting';
        if(connStatus)connStatus.className='gcm-head-status offline';
      }else{
        bannerText.textContent='Connection lost. Refresh the page.';
      }
    });
  }

  // Check every 10s
  setInterval(checkConnection,10000);
})();

// ── v9.0: Voice Input (Web Speech API) ──────────────────────────────────────
(function(){
  if(!('webkitSpeechRecognition' in window)&&!('SpeechRecognition' in window))return;
  var micBtn=document.getElementById('gcmMicBtn');
  var inputEl=document.getElementById('gcmInput');
  if(!micBtn||!inputEl)return;

  var SpeechRecognition=window.SpeechRecognition||window.webkitSpeechRecognition;
  var recognition=new SpeechRecognition();
  recognition.continuous=false;
  recognition.interimResults=true;
  recognition.lang='en-US';
  var isListening=false;

  micBtn.addEventListener('click',function(){
    if(isListening){
      recognition.stop();
      isListening=false;
      micBtn.style.background='';
      micBtn.textContent='🎙️';
      return;
    }
    recognition.start();
    isListening=true;
    micBtn.style.background='rgba(239,68,68,0.3)';
    micBtn.textContent='🔴';
  });

  recognition.onresult=function(e){
    var transcript='';
    for(var i=e.resultIndex;i<e.results.length;i++){
      transcript+=e.results[i][0].transcript;
    }
    inputEl.value=transcript;
    inputEl.dispatchEvent(new Event('input'));
    if(e.results[e.results.length-1].isFinal){
      recognition.stop();
      isListening=false;
      micBtn.style.background='';
      micBtn.textContent='🎙️';
    }
  };

  recognition.onerror=function(){
    isListening=false;
    micBtn.style.background='';
    micBtn.textContent='🎙️';
  };

  recognition.onend=function(){
    isListening=false;
    micBtn.style.background='';
    micBtn.textContent='🎙️';
  };
})();

// ── v9.1 Files Touched Tracker ──────────────────────────────────────────
(function(){
  var trackedFiles=[];
  var filesList=document.getElementById('gcmFilesList');
  var filesEmpty=document.getElementById('gcmFilesEmpty');
  var filesTitle=document.getElementById('gcmFilesTitle');
  var fileBadge=document.getElementById('gcmFileCount');
  if(!filesList)return;

  // File type → icon mapping
  var iconMap={
    js:'📜',ts:'📘',jsx:'⚛️',tsx:'⚛️',
    php:'🐘',py:'🐍',rb:'💎',go:'🐹',rs:'🦀',
    html:'🌐',css:'🎨',scss:'🎨',less:'🎨',
    json:'📋',xml:'📋',yaml:'📋',yml:'📋',toml:'📋',
    md:'📝',txt:'📝',log:'📝',
    sh:'🖥️',bash:'🖥️',zsh:'🖥️',
    sql:'🗃️',env:'🔐',lock:'🔒',
    png:'🖼️',jpg:'🖼️',svg:'🖼️',gif:'🖼️',
    dockerfile:'🐳',makefile:'⚙️'
  };

  function getIcon(name){
    var lower=name.toLowerCase();
    if(lower==='dockerfile')return '🐳';
    if(lower==='makefile')return '⚙️';
    var ext=lower.split('.').pop();
    return iconMap[ext]||'📄';
  }

  function addFile(fullPath,action){
    action=action||'read';
    // Dedupe — increment count if already tracked
    for(var i=0;i<trackedFiles.length;i++){
      if(trackedFiles[i].path===fullPath){
        trackedFiles[i].count++;
        trackedFiles[i].lastAction=action;
        trackedFiles[i].ts=Date.now();
        renderFiles();
        return;
      }
    }
    trackedFiles.push({path:fullPath,count:1,lastAction:action,ts:Date.now()});
    renderFiles();
  }

  function renderFiles(){
    if(!trackedFiles.length){
      filesEmpty.style.display='';
      fileBadge.style.display='none';
      filesTitle.textContent='Files Touched (0)';
      return;
    }
    filesEmpty.style.display='none';
    fileBadge.textContent=trackedFiles.length;
    fileBadge.style.display='';
    filesTitle.textContent='Files Touched ('+trackedFiles.length+')';

    // Remove old items (keep the empty placeholder)
    var items=filesList.querySelectorAll('.gcm-file-item');
    items.forEach(function(el){el.remove();});

    // Sort by most recent first
    var sorted=trackedFiles.slice().sort(function(a,b){return b.ts-a.ts;});
    sorted.forEach(function(f){
      var parts=f.path.replace(/\\\\/g,'/').split('/');
      var name=parts.pop()||'';
      var dir=parts.length>2?'…/'+parts.slice(-2).join('/'):parts.join('/');
      var el=document.createElement('div');
      el.className='gcm-file-item';
      el.title=f.path+' ('+f.lastAction+', '+f.count+'x)';
      el.innerHTML=
        '<span class="gcm-file-icon">'+getIcon(name)+'</span>'+
        '<span class="gcm-file-name">'+name+(dir?' <span class="gcm-fdir">'+dir+'</span>':'')+'</span>'+
        (f.count>1?'<span class="gcm-file-badge">'+f.count+'x</span>':'');
      el.addEventListener('click',function(){openFileInEditor(f.path);});
      filesList.insertBefore(el,filesEmpty);
    });
  }

  function openFileInEditor(filePath){
    // Strategy 1: Use Monaco editor's openUri
    try{
      if(window.monaco&&window.monaco.editor){
        var uri=window.monaco.Uri.file(filePath);
        // Try Theia's opener by dispatching a command via the input
        var ed=window.monaco.editor.getEditors()[0];
        if(ed&&ed._theiaDiContainer){
          var openerService=ed._theiaDiContainer.get&&ed._theiaDiContainer.get('OpenerService');
          if(openerService){openerService.getOpener(uri).then(function(opener){opener.open(uri);});return;}
        }
      }
    }catch(e){}

    // Strategy 2: Simulate Ctrl+P quick-open and type the filename
    try{
      var name=filePath.split('/').pop();
      document.dispatchEvent(new KeyboardEvent('keydown',{key:'p',code:'KeyP',ctrlKey:true,bubbles:true}));
      setTimeout(function(){
        // Find the quick-open input
        var qOpen=document.querySelector('.monaco-quick-input-widget input')||
                  document.querySelector('.quick-input-widget input')||
                  document.querySelector('[class*="quick-open"] input')||
                  document.querySelector('.quick-input-box input');
        if(qOpen){
          qOpen.value=name;
          qOpen.dispatchEvent(new Event('input',{bubbles:true}));
        }
      },300);
      return;
    }catch(e){}
  }

  // Clear button
  document.getElementById('gcmFilesClear').addEventListener('click',function(){
    trackedFiles=[];
    renderFiles();
  });

  // ── Detection: Watch Theia AI Chat DOM for file paths ──
  // Method 1: Intercept fetch responses from the AI API that contain tool_use blocks
  var origFetch=window.fetch;
  window.fetch=function(){
    var result=origFetch.apply(this,arguments);
    result.then(function(resp){
      try{
        var url=typeof arguments[0]==='string'?arguments[0]:(arguments[0]&&arguments[0].url)||'';
        if(url.indexOf('/v1/messages')!==-1||url.indexOf('/api/claude')!==-1||url.indexOf('/api/ai-terminal')!==-1){
          // Clone to read body without consuming
          resp.clone().text().then(function(body){
            extractFilePaths(body);
          }).catch(function(){});
        }
      }catch(e){}
    }).catch(function(){});
    return result;
  }.bind(window);

  // Method 2: Watch the Theia Chat DOM for rendered file references
  var chatContainer=null;
  function watchChatDOM(){
    var selectors=['#chat-tree-widget-treeContainer','#chat-tree-widget','.theia-ChatNode'];
    for(var i=0;i<selectors.length;i++){chatContainer=document.querySelector(selectors[i]);if(chatContainer)break;}
    if(!chatContainer){setTimeout(watchChatDOM,5000);return;}

    var chatFileObs=new MutationObserver(function(){
      // Look for file paths in rendered content
      var nodes=chatContainer.querySelectorAll('.theia-ResponseNode-Content, .theia-ChatNode');
      nodes.forEach(function(node){
        if(node._gcmFileScanned)return;
        node._gcmFileScanned=true;
        var text=node.textContent||'';
        extractFilePaths(text);
      });
    });
    chatFileObs.observe(chatContainer,{childList:true,subtree:true});
  }
  setTimeout(watchChatDOM,6000);

  // Extract file paths from text
  var filePathRegex=new RegExp('(?:^|[\\\\s"\\'\\x60(,:])(\/(?:home|tmp|var|etc|usr|opt|root|mnt|srv)[\/][^\\\\s"\\'\\x60),;:><|*?\\\\n]{3,})','gm');
  var readFileRegex=new RegExp('(?:read_file|list_dir|file_search|create_file|edit_file|replace_string_in_file|read_directory|write_file|open_file)\\\\s*[\\\\({]["\\'\\x60]?([^"\\'\\\\s)},]{5,})','gi');

  var cleanEndRegex=new RegExp('[\"\\\\s\\'\\x60),;]+$','');

  function extractFilePaths(text){
    if(!text||text.length<10)return;
    var match;
    // Pattern 1: Absolute paths
    filePathRegex.lastIndex=0;
    while((match=filePathRegex.exec(text))!==null){
      var p=match[1].replace(cleanEndRegex,'');
      if(p.length>4&&p.indexOf('.')!==-1)addFile(p,'read');
    }
    // Pattern 2: Tool use patterns
    readFileRegex.lastIndex=0;
    while((match=readFileRegex.exec(text))!==null){
      var fp=match[1].replace(cleanEndRegex,'');
      if(fp.length>4&&fp.startsWith('/'))addFile(fp,match[0].startsWith('create')?'create':match[0].startsWith('edit')||match[0].startsWith('replace')||match[0].startsWith('write')?'edit':'read');
    }
  }

  // Expose globally for other modules to call
  window.__gcmTrackFile=addFile;
})();

})();
</script>
`;
}


// Create reusable proxy server with HTML injection (for IDE pages)
const proxy = httpProxy.createProxyServer({
  ws: true,
  changeOrigin: true,
  xfwd: true,
  selfHandleResponse: true,
});

proxy.on('proxyRes', (proxyRes, req, res) => {
  const contentType = proxyRes.headers['content-type'] || '';
  const isHtml = contentType.includes('text/html');

  if (!isHtml) {
    // Non-HTML: pass through as-is
    res.writeHead(proxyRes.statusCode, proxyRes.headers);
    proxyRes.pipe(res);
    return;
  }

  // HTML response — collect body, inject Alfred widget before </body>
  const chunks = [];
  proxyRes.on('data', chunk => chunks.push(chunk));
  proxyRes.on('end', () => {
    let body = Buffer.concat(chunks);

    // Decompress if needed
    const encoding = proxyRes.headers['content-encoding'];
    const decompress = encoding === 'gzip' ? zlib.gunzipSync
                     : encoding === 'br'   ? zlib.brotliDecompressSync
                     : encoding === 'deflate' ? zlib.inflateSync
                     : null;
    if (decompress) {
      try { body = decompress(body); } catch (e) {
        logger.warn(`IDE proxy: failed to decompress ${encoding}: ${e.message}`);
      }
    }

    let html = body.toString('utf-8');

    // ── Inject localStorage isolation FIRST (before any widget code) ──────
    // All IDE instances share the same origin (gositeme.com), so localStorage
    // is shared. Without isolation, Theia's workspace path leaks between users
    // opening different IDE ports in the same browser.
    // We scope all storage keys by the IDE port number extracted from the URL.
    const portMatch = (req.originalUrl || req.url || '').match(/\/ide\/(\d+)/);
    const idePort = portMatch ? portMatch[1] : '0';
    // ── Inject IDE Custom Scripts (Model Selector, Context Meter, etc.) ──────
    // These scripts inject UI elements into the Theia AI Chat panel.
    // They require the session token and model multiplier defaults.
    const auth = req._ideAuth || {};
    const sessionToken = auth.sessionToken || auth.voiceToken || '';
    const gcmInjections = `
<script>
(function(){
  window.__gcmSessionToken = ${JSON.stringify(sessionToken)};
  window.__gcmTokenMultDefaults = ${JSON.stringify(gcmTokenMultDefaults)};
})();
</script>
<script src="gcm-context-meter.js"></script>
<script src="gcm-model-selector.js"></script>
`;

    const storageIsolation = `<script>
(function(){
  var PORT = '${idePort}';
  var PREFIX = '__ide_' + PORT + '_';
  var _orig = window.localStorage;
  var _getItem = _orig.getItem.bind(_orig);
  var _setItem = _orig.setItem.bind(_orig);
  var _removeItem = _orig.removeItem.bind(_orig);
  // Clear any workspace keys from OTHER ports to prevent stale cross-user leaks
  try {
    for (var i = _orig.length - 1; i >= 0; i--) {
      var k = _orig.key(i);
      if (k && k.startsWith('__ide_') && !k.startsWith(PREFIX)) {
        _orig.removeItem(k);
      }
    }
  } catch(e) {}
  Object.defineProperty(window, 'localStorage', {
    get: function() {
      return new Proxy(_orig, {
        get: function(target, prop) {
          if (prop === 'getItem') return function(k) { return _getItem(PREFIX + k); };
          if (prop === 'setItem') return function(k, v) { return _setItem(PREFIX + k, v); };
          if (prop === 'removeItem') return function(k) { return _removeItem(PREFIX + k); };
          if (prop === 'key') return function(i) { var k = target.key(i); return k && k.startsWith(PREFIX) ? k.slice(PREFIX.length) : k; };
          if (prop === 'clear') return function() {
            for (var i = target.length - 1; i >= 0; i--) {
              var k = target.key(i);
              if (k && k.startsWith(PREFIX)) target.removeItem(k);
            }
          };
          if (prop === 'length') {
            var c = 0;
            for (var i = 0; i < target.length; i++) { if ((target.key(i)||'').startsWith(PREFIX)) c++; }
            return c;
          }
          var val = target[prop];
          return typeof val === 'function' ? val.bind(target) : val;
        }
      });
    },
    configurable: true
  });
})();
</script>\n`;

    // Inject Alfred widget only when explicitly requested.
    // Default OFF to avoid duplicate chat surfaces (extension panel + floating widget).
    // Enable with ?alfred_widget=1 if needed for debugging.
    var widgetHtml = '';
    if (req && req.query && String(req.query.alfred_widget || '') === '1') {
      widgetHtml = getAlfredWidget(req._ideAuth || {});
    }
    const lastBody = html.lastIndexOf('</body>');
    if (lastBody !== -1) {
      html = html.substring(0, lastBody) + widgetHtml + html.substring(lastBody);
    } else if (html.includes('</html>')) {
      html = html.replace('</html>', widgetHtml + '</html>');
    }

    // Inject localStorage isolation as early as possible (after <head>)
    const headClose = html.indexOf('</head>');
    if (headClose !== -1) {
      // Inject GCM scripts and isolation into <head>
      html = html.substring(0, headClose) + gcmInjections + storageIsolation + html.substring(headClose);
    } else {
      html = gcmInjections + storageIsolation + html;
    }

    const rewritten = Buffer.from(html, 'utf-8');
    const headers = { ...proxyRes.headers };
    delete headers['content-encoding'];
    delete headers['transfer-encoding'];
    headers['content-length'] = rewritten.length;

    res.writeHead(proxyRes.statusCode, headers);
    res.end(rewritten);
  });
});

proxy.on('error', (err, req, res) => {
  logger.error(`IDE proxy error: ${err.message}`);
  if (res && res.writeHead && !res.headersSent) {
    // Return a friendly loading page that auto-retries instead of raw JSON
    res.writeHead(502, { 'Content-Type': 'text/html; charset=utf-8' });
    res.end(`<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>GoCodeMe – Starting IDE…</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{background:#0d1117;color:#c9d1d9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh}
.card{text-align:center;max-width:420px;padding:2rem}
.spinner{width:48px;height:48px;border:4px solid #30363d;border-top-color:#58a6ff;border-radius:50%;animation:spin .8s linear infinite;margin:0 auto 1.5rem}
@keyframes spin{to{transform:rotate(360deg)}}
h1{font-size:1.25rem;margin-bottom:.5rem;color:#f0f6fc}
p{font-size:.9rem;color:#8b949e;line-height:1.5}
.dots::after{content:'';animation:dots 1.5s steps(4,end) infinite}
@keyframes dots{0%{content:''}25%{content:'.'}50%{content:'..'}75%{content:'...'}}
.retry-info{margin-top:1rem;font-size:.8rem;color:#484f58}
</style>
</head>
<body>
<div class="card">
  <div class="spinner"></div>
  <h1>Your IDE is starting up<span class="dots"></span></h1>
  <p>Hang tight — this usually takes just a few seconds.</p>
  <p class="retry-info">Auto-retrying every 2 seconds…</p>
</div>
<script>
(function(){
  var retries = 0, maxRetries = 60;
  function check(){
    retries++;
    if(retries > maxRetries){
      document.querySelector('h1').textContent = 'IDE failed to start';
      document.querySelector('p').textContent = 'Please go back and try launching again.';
      document.querySelector('.spinner').style.display = 'none';
      document.querySelector('.retry-info').textContent = '';
      return;
    }
    fetch(location.href, {method:'HEAD', cache:'no-store'})
      .then(function(r){
        if(r.ok) location.reload();
        else setTimeout(check, 2000);
      })
      .catch(function(){ setTimeout(check, 2000); });
  }
  setTimeout(check, 2000);
})();
</script>
</body>
</html>`);
  }
});

// Separate proxy for the agent — intercepts HTML to inject <base> tag
const agentProxy = httpProxy.createProxyServer({
  changeOrigin: true,
  xfwd: true,
  selfHandleResponse: true,
});

agentProxy.on('error', (err, req, res) => {
  logger.error(`Agent proxy error: ${err.message}`);
  if (res && res.writeHead && !res.headersSent) {
    res.writeHead(502, { 'Content-Type': 'text/html; charset=utf-8' });
    res.end(`<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>GoCodeMe – Starting Agent…</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{background:#0d1117;color:#c9d1d9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh}
.card{text-align:center;max-width:420px;padding:2rem}
.spinner{width:48px;height:48px;border:4px solid #30363d;border-top-color:#58a6ff;border-radius:50%;animation:spin .8s linear infinite;margin:0 auto 1.5rem}
@keyframes spin{to{transform:rotate(360deg)}}
h1{font-size:1.25rem;margin-bottom:.5rem;color:#f0f6fc}
p{font-size:.9rem;color:#8b949e;line-height:1.5}
.dots::after{content:'';animation:dots 1.5s steps(4,end) infinite}
@keyframes dots{0%{content:''}25%{content:'.'}50%{content:'..'}75%{content:'...'}}
.retry-info{margin-top:1rem;font-size:.8rem;color:#484f58}
</style>
</head>
<body>
<div class="card">
  <div class="spinner"></div>
  <h1>Agent is starting up<span class="dots"></span></h1>
  <p>Hang tight — this usually takes just a few seconds.</p>
  <p class="retry-info">Auto-retrying every 2 seconds…</p>
</div>
<script>
(function(){
  var retries = 0, maxRetries = 60;
  function check(){
    retries++;
    if(retries > maxRetries){
      document.querySelector('h1').textContent = 'Agent failed to start';
      document.querySelector('p').textContent = 'Please go back and try launching again.';
      document.querySelector('.spinner').style.display = 'none';
      document.querySelector('.retry-info').textContent = '';
      return;
    }
    fetch(location.href, {method:'HEAD', cache:'no-store'})
      .then(function(r){
        if(r.ok) location.reload();
        else setTimeout(check, 2000);
      })
      .catch(function(){ setTimeout(check, 2000); });
  }
  setTimeout(check, 2000);
})();
</script>
</body>
</html>`);
  }
});

agentProxy.on('proxyRes', (proxyRes, req, res) => {
  const contentType = proxyRes.headers['content-type'] || '';
  const isHtml = contentType.includes('text/html');

  if (!isHtml) {
    // Non-HTML: pass through as-is
    res.writeHead(proxyRes.statusCode, proxyRes.headers);
    proxyRes.pipe(res);
    return;
  }

  // HTML response — collect body, inject <base> tag, send
  const chunks = [];
  proxyRes.on('data', chunk => chunks.push(chunk));
  proxyRes.on('end', () => {
    let body = Buffer.concat(chunks);

    // Decompress if gzipped
    const encoding = proxyRes.headers['content-encoding'];
    const decompress = encoding === 'gzip' ? zlib.gunzipSync
                     : encoding === 'br'   ? zlib.brotliDecompressSync
                     : encoding === 'deflate' ? zlib.inflateSync
                     : null;
    if (decompress) {
      try { body = decompress(body); } catch (e) {
        logger.warn(`Agent proxy: failed to decompress ${encoding}: ${e.message}`);
      }
    }

    let html = body.toString('utf-8');

    // Build the base path: /middleware/agent/<port>/
    const port = req._agentPort || 'unknown';
    const basePath = `/middleware/agent/${port}/`;

    // Inject <base href="..."> right after <head> (or after <head ...>)
    if (html.includes('<head') && !html.includes('<base ')) {
      html = html.replace(/(<head[^>]*>)/, `$1<base href="${basePath}">`);
    }

    // Rewrite absolute /assets/ references to go through the proxy base path.
    // HTML attributes (href, src) respect <base>, so relative is fine there:
    html = html.replace(/href="\/assets\//g, `href="${basePath}assets/`);
    html = html.replace(/href='\/assets\//g, `href='${basePath}assets/`);
    html = html.replace(/src="\/assets\//g, `src="${basePath}assets/`);
    html = html.replace(/src='\/assets\//g, `src='${basePath}assets/`);

    // ES module imports do NOT respect <base href> — they must use absolute paths.
    html = html.replace(/import "\/assets\//g, `import "${basePath}assets/`);
    html = html.replace(/import '\/assets\//g, `import '${basePath}assets/`);
    html = html.replace(/from "\/assets\//g, `from "${basePath}assets/`);
    html = html.replace(/from '\/assets\//g, `from '${basePath}assets/`);
    html = html.replace(/import\("\/assets\//g, `import("${basePath}assets/`);

    // Also fix bare "assets/..." that are already relative (from previous rewrites or SPA builds)
    // These need ./ prefix for ES modules
    html = html.replace(/import "assets\//g, `import "${basePath}assets/`);
    html = html.replace(/import 'assets\//g, `import '${basePath}assets/`);
    html = html.replace(/from "assets\//g, `from "${basePath}assets/`);
    html = html.replace(/from 'assets\//g, `from '${basePath}assets/`);
    html = html.replace(/import\("assets\//g, `import("${basePath}assets/`);

    const rewritten = Buffer.from(html, 'utf-8');

    // Send with updated headers (remove content-encoding since we decompressed,
    // update content-length)
    const headers = { ...proxyRes.headers };
    delete headers['content-encoding'];
    delete headers['transfer-encoding'];
    headers['content-length'] = rewritten.length;

    res.writeHead(proxyRes.statusCode, headers);
    res.end(rewritten);
  });
});

/** Validate port is in allowed range and has an active session.
 *  Returns the daUsername that owns the session, or false. */
async function validatePort(port) {
  if (port < PORT_MIN || port > PORT_MAX) return false;

  // SECURITY (R3-06): Use SCAN instead of KEYS to avoid O(N) blocking on Redis
  const redis = getRedis();
  let cursor = '0';
  do {
    const [nextCursor, keys] = await redis.scan(cursor, 'MATCH', 'launch:sessions:*', 'COUNT', 100);
    cursor = nextCursor;
    for (const key of keys) {
      try {
        const sessions = JSON.parse(await redis.get(key));
        if (sessions.some(s => s.port === port)) {
          return key.replace('launch:sessions:', '');
        }
      } catch { /* skip corrupt entries */ }
    }
  } while (cursor !== '0');
  return false;
}

/**
 * Validate JWT from Authorization header (for proxy routes).
 * Returns { ok, daUsername } or { ok: false }.
 */
function validateProxyJwt(req) {
  const authHeader = req.headers.authorization || '';
  const token = authHeader.startsWith('Bearer ') ? authHeader.slice(7) : null;
  if (!token) return { ok: false };
  try {
    const config = require('../config');
    const decoded = jwt.verify(token, config.jwt.secret);
    if (decoded && decoded.daUsername) return { ok: true, daUsername: decoded.daUsername };
  } catch (_) {}
  return { ok: false };
}

/**
 * Express middleware for IDE proxy
 * Handles: /ide/:port/* and /agent/:port/*
 */
function createProxyMiddleware(pathPrefix) {
  return async function ideProxyHandler(req, res) {
    const port = parseInt(req.params.port, 10);
    if (isNaN(port)) {
      return res.status(400).json({ ok: false, error: 'Invalid port' });
    }

    // ── SECURITY: Require IDE auth token (cookie or query param) ──────────
    const auth = await validateIdeAuth(req, port);
    if (!auth.ok) {
      if (auth.reason === 'no_session') {
        return res.status(403).json({ ok: false, error: 'No active session on this port' });
      }
      return res.status(401).json({ ok: false, error: 'IDE authentication required' });
    }

    // ── SECURITY: Cross-check JWT user matches port owner (sandbox isolation) ──
    const jwtAuth = validateProxyJwt(req);
    if (jwtAuth.ok && jwtAuth.daUsername !== auth.daUsername) {
      logger.warn(
        `SANDBOX VIOLATION: JWT user "${jwtAuth.daUsername}" attempted to access IDE owned by "${auth.daUsername}" on port ${port}`
      );
      return res.status(403).json({ ok: false, error: 'Access denied: this workspace belongs to another user' });
    }

    // Set httpOnly cookie if auth came from query param (first access)
    if (auth.fromQuery) {
      res.cookie('gcm_ide_auth', auth.token, {
        httpOnly: true,
        secure: true,
        sameSite: 'strict',
        maxAge: 8 * 60 * 60 * 1000, // 8h — matches token TTL
        path: '/',
      });
      // Strip gcm_auth from the proxied URL to avoid leaking token to Theia
      if (req.url && req.url.includes('gcm_auth')) {
        try {
          const u = new URL(req.url, 'http://localhost');
          u.searchParams.delete('gcm_auth');
          req.url = u.pathname + (u.search || '');
        } catch {}
      }
    }

    // ── Track activity for idle session reaper (throttled, non-blocking) ──
    // Only update Redis once per minute per port to avoid excess Redis load
    const now = Date.now();
    if (!ideProxyHandler._lastActivity) ideProxyHandler._lastActivity = {};
    if (!ideProxyHandler._lastActivity[port] || now - ideProxyHandler._lastActivity[port] > 60000) {
      ideProxyHandler._lastActivity[port] = now;
      try {
        const rds = getRedis();
        // SECURITY (R3 M-03): Use SCAN instead of KEYS
        const keys = await scanKeys(rds, 'launch:sessions:*');
        for (const key of keys) {
          try {
            const sessions = JSON.parse(await rds.get(key));
            if (sessions.some(s => s.port === port)) {
              const daUsername = key.replace('launch:sessions:', '');
              rds.set(`activity:${daUsername}`, now.toString(), 'EX', 7200).catch(() => {});
              break;
            }
          } catch {}
        }
      } catch (_) {}
    }

    // Strip the /ide/:port or /agent/:port prefix from the URL
    const originalUrl = req.url;
    // req.url at this point is everything after the mounted path
    // e.g. for /ide/4000/foo/bar → req.url is /foo/bar (Express strips the mount)

    const target = `http://127.0.0.1:${port}`;

    // ── Inject voice auth for the Alfred IDE widget ─────────────────────────
    if (pathPrefix !== 'agent') {
      try {
        const rds = getRedis();
        // SECURITY (R3 M-03): Use SCAN instead of KEYS
        const keys2 = await scanKeys(rds, 'launch:sessions:*');
        for (const k of keys2) {
          try {
            const sessions = JSON.parse(await rds.get(k));
            if (sessions.some(s => s.port === port)) {
              const daUser = k.replace('launch:sessions:', '');
              const secret = require('../config').jwt.secret;
              if (secret && daUser) {
                const voiceJwt = jwt.sign({ daUsername: daUser, plan: 'active' }, secret, { expiresIn: '8h' });
                // Also resolve whmcsClientId for model selector API auth
                let resolvedClientId = null;
                try {
                  resolvedClientId = await rds.get(`client_id_by_da:${daUser}`);
                } catch {}
                const sessionJwt = resolvedClientId
                  ? jwt.sign({ daUsername: daUser, whmcsClientId: resolvedClientId, plan: 'active' }, secret, { expiresIn: '8h' })
                  : voiceJwt;
                req._ideAuth = { daUsername: daUser, voiceToken: voiceJwt, sessionToken: sessionJwt, isAuth: true };
              }
              break;
            }
          } catch {}
        }
      } catch (err) {
        logger.warn(`IDE auth lookup failed: ${err.message}`);
      }
    }

    // For agent routes, use the HTML-rewriting proxy so <base> tag is injected
    if (pathPrefix === 'agent') {
      req._agentPort = port;
      agentProxy.web(req, res, { target }, (err) => {
        logger.error(`Agent proxy web error for port ${port}: ${err.message}`);
        if (!res.headersSent) {
          res.status(502).json({ ok: false, error: 'Agent unreachable' });
        }
      });
    } else {
      proxy.web(req, res, { target }, (err) => {
        logger.error(`IDE proxy web error for port ${port}: ${err.message}`);
        if (!res.headersSent) {
          res.status(502).json({ ok: false, error: 'IDE unreachable' });
        }
      });
    }
  };
}

/**
 * Attach WebSocket upgrade handler to the HTTP server.
 * Call this once after server.listen() — handles ws:// upgrade for /ide/:port and /agent/:port paths.
 */
function attachUpgradeHandler(server) {
  server.on('upgrade', async (req, socket, head) => {
    // Strip /middleware prefix if Apache didn't strip it for WebSocket upgrades
    if (req.url.startsWith('/middleware/')) {
      req.url = req.url.slice('/middleware'.length);
    }

    // ── Voice WebSocket proxy — /voice-ws → wss://127.0.0.1:3006 ────────
    if (req.url === '/voice-ws' || req.url.startsWith('/voice-ws?')) {
      req.url = '/';
      const voicePort = process.env.VOICE_PORT || 3006;
      const voiceTarget = `wss://127.0.0.1:${voicePort}`;
      logger.info(`Voice WS upgrade → ${voiceTarget}`);
      wsProxy.ws(req, socket, head, { target: voiceTarget, secure: false }, (err) => {
        logger.error(`Voice proxy ws error: ${err.message}`);
        socket.destroy();
      });
      return;
    }

    // ── Autopilot Live Browser Stream — /api/autopilot/stream ───────────
    if (req.url.startsWith('/api/autopilot/stream')) {
      try {
        const { handleAutopilotWebSocket } = require('./autopilotProxy.js');
        // Manual WebSocket upgrade using 'ws' library
        const WebSocket = require('ws');
        const wss = new WebSocket.Server({ noServer: true });
        wss.handleUpgrade(req, socket, head, (ws) => {
          handleAutopilotWebSocket(ws, req);
        });
      } catch (err) {
        logger.error(`Autopilot WS upgrade error: ${err.message}`);
        socket.write('HTTP/1.1 500 Internal Server Error\r\n\r\n');
        socket.destroy();
      }
      return;
    }
    // Match /ide/:port/... or /agent/:port/...
    const match = req.url.match(/^\/(ide|agent)\/(\d+)(\/.*)?$/);
    if (!match) return; // not for us — let default handler deal with it

    const port = parseInt(match[2], 10);
    const remainingPath = match[3] || '/';

    // ── SECURITY: Require IDE auth token from cookie for WebSocket ────────
    const wsCookies = parseCookies(req.headers.cookie || '');
    const wsToken = wsCookies.gcm_ide_auth;
    if (!wsToken || !/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i.test(wsToken)) {
      logger.error(`WS Upgrade rejected! Path: ${req.url}, Cookies: ${req.headers.cookie}, Extracted wsToken: ${wsToken}`);
      socket.write('HTTP/1.1 401 Unauthorized\r\nContent-Length: 0\r\n\r\n');
      socket.destroy();
      return;
    }
    let wsTokenOwner;
    try {
      const redis = getRedis();
      wsTokenOwner = await redis.get(`ide_auth_token:${wsToken}`);
    } catch {}
    if (!wsTokenOwner) {
      socket.write('HTTP/1.1 401 Unauthorized\r\nContent-Length: 0\r\n\r\n');
      socket.destroy();
      return;
    }
    const portOwner = await validatePort(port);
    if (!portOwner || portOwner !== wsTokenOwner) {
      socket.write('HTTP/1.1 403 Forbidden\r\nContent-Length: 0\r\n\r\n');
      socket.destroy();
      return;
    }

    // ── SECURITY: Cross-check JWT user on WebSocket upgrade (sandbox isolation) ──
    const wsAuthHeader = req.headers.authorization || '';
    const wsJwtToken = wsAuthHeader.startsWith('Bearer ') ? wsAuthHeader.slice(7) : null;
    if (wsJwtToken) {
      try {
        const config = require('../config');
        const decoded = jwt.verify(wsJwtToken, config.jwt.secret);
        if (decoded && decoded.daUsername && decoded.daUsername !== wsTokenOwner) {
          logger.warn(`SANDBOX VIOLATION (WS): JWT user "${decoded.daUsername}" tried WS to IDE owned by "${wsTokenOwner}" port ${port}`);
          socket.write('HTTP/1.1 403 Forbidden\r\nContent-Length: 0\r\n\r\n');
          socket.destroy();
          return;
        }
      } catch (_) { /* invalid JWT — IDE auth token was already validated, allow */ }
    }

    // Rewrite the URL to strip /ide/:port prefix
    req.url = remainingPath;

    const target = `http://127.0.0.1:${port}`;
    wsProxy.ws(req, socket, head, { target }, (err) => {
      logger.error(`IDE proxy ws error for port ${port}: ${err.message}`);
      socket.destroy();
    });
  });

  logger.info('IDE WebSocket upgrade handler attached');
}

module.exports = { createProxyMiddleware, attachUpgradeHandler };
