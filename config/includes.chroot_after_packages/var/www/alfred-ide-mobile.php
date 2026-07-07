<?php
/**
 * Alfred IDE Mobile — Touch-first AI chat for Pixel 8 & Samsung S26 Ultra
 * Connects to the same Alfred Chat API (/api/alfred-chat.php) as the desktop IDE extension.
 * Auth: requires alfred_ide_token cookie (set by /alfred-ide-auth.php)
 */
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', '1');
session_start();

// ── Auth check ──────────────────────────────────────────────────────────
$token = $_COOKIE['alfred_ide_token'] ?? '';
if (!$token) {
    header('Location: /alfred-ide-auth.php?redirect=/alfred-ide-mobile.php');
    exit;
}

// Validate token against DB
$dbh = new PDO('mysql:host=localhost;dbname=root_whmcs;charset=utf8mb4', 'root_whmcs', '!q@w#e$r5t');
$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$tokenHash = hash('sha256', $token);
$stmt = $dbh->prepare("SELECT id, email, display_name, google_name, client_id FROM alfred_ide_users WHERE session_token = ? AND token_expires > NOW() LIMIT 1");
$stmt->execute([$tokenHash]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    header('Location: /alfred-ide-auth.php?redirect=/alfred-ide-mobile.php');
    exit;
}

$displayName = htmlspecialchars($user['google_name'] ?: $user['display_name'] ?: explode('@', $user['email'])[0], ENT_QUOTES, 'UTF-8');
$safeToken   = preg_replace('/[^a-f0-9]/', '', $token);
$clientId    = (int) $user['client_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, interactive-widget=resizes-content">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="theme-color" content="#0d1117">
<meta name="mobile-web-app-capable" content="yes">
<title>Alfred IDE — Mobile</title>
<link rel="icon" href="/brand/icons/alfred-browser/favicon.ico">
<style>
  :root {
    --bg: #0d1117; --bg2: #161b22; --border: #30363d;
    --text: #c9d1d9; --muted: #8b949e; --dim: #484f58;
    --gold: #e2b340; --blue: #3b82f6; --green: #22c55e;
    --danger: #ef4444; --warn: #f59e0b;
    --safe-top: env(safe-area-inset-top, 0px);
    --safe-bottom: env(safe-area-inset-bottom, 0px);
  }
  * { margin: 0; padding: 0; box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
  html, body { height: 100%; overflow: hidden; }
  body {
    font-family: -apple-system, 'Segoe UI', system-ui, sans-serif;
    background: var(--bg); color: var(--text);
    display: flex; flex-direction: column;
    padding-top: var(--safe-top);
    padding-bottom: var(--safe-bottom);
  }

  /* ── Header ── */
  .m-header {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 14px; border-bottom: 1px solid var(--border);
    background: var(--bg); flex-shrink: 0; min-height: 50px;
  }
  .m-header .logo { color: var(--gold); font-size: 18px; font-weight: 800; letter-spacing: 0.5px; }
  .m-header .user { margin-left: auto; font-size: 12px; color: var(--muted); }
  .m-header .user .name { color: var(--green); font-weight: 600; }
  .m-header .menu-btn {
    width: 36px; height: 36px; border-radius: 8px; border: 1px solid var(--border);
    background: transparent; color: var(--muted); font-size: 18px; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
  }
  .m-header .menu-btn:active { background: rgba(255,255,255,0.05); }

  /* ── Model bar ── */
  .m-model-bar {
    display: flex; align-items: center; gap: 8px;
    padding: 6px 14px; border-bottom: 1px solid var(--border);
    background: var(--bg2); flex-shrink: 0; overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }
  .m-model-bar select {
    flex: 1; min-width: 0; height: 36px;
    background: var(--bg); border: 1px solid var(--border); border-radius: 8px;
    color: var(--text); font-size: 14px; padding: 0 8px;
    -webkit-appearance: none; appearance: none;
  }
  .m-model-bar select:focus { border-color: var(--gold); outline: none; }
  .m-model-bar .tok-sel { flex: 0 0 80px; color: var(--gold); }

  /* ── Chat area ── */
  .m-chat {
    flex: 1; overflow-y: auto; padding: 10px 14px;
    -webkit-overflow-scrolling: touch;
    scroll-behavior: smooth;
  }
  .m-msg {
    margin-bottom: 10px; padding: 10px 12px;
    border-radius: 12px; font-size: 15px; line-height: 1.6;
    word-wrap: break-word; overflow-wrap: break-word;
    animation: fadeIn 0.25s ease;
  }
  .m-msg.user { background: rgba(59,130,246,0.12); border: 1px solid rgba(59,130,246,0.2); }
  .m-msg.alfred { background: rgba(226,179,64,0.08); border: 1px solid rgba(226,179,64,0.15); }
  .m-msg.system { background: rgba(34,197,94,0.08); border: 1px solid rgba(34,197,94,0.15); font-size: 13px; color: var(--green); }
  .m-msg .sender { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; color: var(--muted); }
  .m-msg.user .sender { color: var(--blue); }
  .m-msg.alfred .sender { color: var(--gold); }
  .m-msg pre { background: #0d1117; border: 1px solid var(--border); border-radius: 8px; padding: 10px; margin: 6px 0; overflow-x: auto; -webkit-overflow-scrolling: touch; font-size: 13px; font-family: 'SF Mono', 'Fira Code', monospace; line-height: 1.5; }
  .m-msg code { background: rgba(255,255,255,0.06); padding: 2px 5px; border-radius: 4px; font-size: 13px; }
  .m-msg pre code { background: transparent; padding: 0; }
  .m-msg a { color: var(--blue); }
  .m-msg ul, .m-msg ol { padding-left: 20px; margin: 4px 0; }
  .m-msg li { margin: 2px 0; }
  .m-msg strong { color: #e6edf3; }
  @keyframes fadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }

  .thinking-dots::after { content: ''; animation: dots 1.5s infinite; }
  @keyframes dots { 0% { content: '.'; } 33% { content: '..'; } 66% { content: '...'; } }

  /* ── Input area ── */
  .m-input-area {
    flex-shrink: 0; padding: 8px 10px;
    border-top: 1px solid var(--border); background: var(--bg);
  }
  .m-input-row {
    display: flex; align-items: flex-end; gap: 8px;
  }
  .m-input-row textarea {
    flex: 1; min-height: 44px; max-height: 120px;
    background: var(--bg2); border: 1px solid var(--border); border-radius: 12px;
    color: var(--text); font-size: 16px; padding: 10px 14px;
    resize: none; outline: none; font-family: inherit; line-height: 1.4;
    -webkit-appearance: none;
  }
  .m-input-row textarea:focus { border-color: var(--gold); }
  .m-input-row textarea::placeholder { color: var(--dim); }
  .m-send-btn {
    width: 44px; height: 44px; border-radius: 12px; border: none;
    background: var(--gold); color: #0d1117; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; font-size: 20px; font-weight: 700;
    transition: opacity 0.15s;
  }
  .m-send-btn:active { opacity: 0.7; }
  .m-send-btn:disabled { opacity: 0.4; }
  .m-send-btn svg { width: 22px; height: 22px; }

  /* ── Status bar ── */
  .m-status {
    text-align: center; font-size: 11px; color: var(--dim);
    padding: 2px 10px 4px; flex-shrink: 0;
  }
  .m-status.active { color: var(--gold); }

  /* ── Settings drawer ── */
  .m-drawer {
    position: fixed; top: 0; right: -300px; width: 280px; height: 100%;
    background: var(--bg2); border-left: 1px solid var(--border);
    z-index: 100; transition: right 0.25s ease;
    padding: 20px 16px; overflow-y: auto;
  }
  .m-drawer.open { right: 0; }
  .m-drawer-overlay {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.5); z-index: 99; display: none;
  }
  .m-drawer-overlay.open { display: block; }
  .m-drawer h3 { color: var(--gold); font-size: 14px; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
  .m-drawer .drawer-item { padding: 10px 0; border-bottom: 1px solid var(--border); font-size: 14px; }
  .m-drawer .drawer-item label { color: var(--muted); font-size: 11px; display: block; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.3px; }
  .m-drawer .drawer-link { display: block; padding: 12px 0; color: var(--blue); font-size: 14px; text-decoration: none; border-bottom: 1px solid var(--border); }
  .m-drawer .drawer-link:active { opacity: 0.7; }
  .m-drawer .drawer-link.danger { color: var(--danger); }
  .m-drawer .close-drawer { position: absolute; top: 14px; right: 14px; width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--border); background: transparent; color: var(--muted); font-size: 18px; cursor: pointer; }

  /* ── Copy button ── */
  .copy-btn { display: inline-block; margin-top: 4px; padding: 4px 10px; border-radius: 6px; border: 1px solid var(--border); background: transparent; color: var(--blue); font-size: 12px; cursor: pointer; }
  .copy-btn:active { background: rgba(59,130,246,0.15); }

  /* ── Desktop redirect ── */
  @media (min-width: 768px) and (pointer: fine) {
    body::before {
      content: 'This page is for mobile devices. Redirecting to full IDE...';
      position: fixed; top: 50%; left: 50%; transform: translate(-50%,-50%);
      color: var(--muted); font-size: 16px; text-align: center;
    }
  }
</style>
</head>
<body>

<!-- Header -->
<div class="m-header">
  <span class="logo">Alfred</span>
  <span class="user"><span class="name"><?= $displayName ?></span></span>
  <button class="menu-btn" id="menuBtn" aria-label="Menu">☰</button>
</div>

<!-- Model selector bar -->
<div class="m-model-bar">
  <select id="modelSelect" aria-label="AI Model">
    <optgroup label="Anthropic">
      <option value="sonnet" selected>Claude Sonnet 4</option>
      <option value="opus">Claude Opus 4</option>
      <option value="haiku">Claude Haiku 3.5</option>
    </optgroup>
    <optgroup label="OpenAI">
      <option value="gpt4o">GPT-4o</option>
      <option value="gpt4o-mini">GPT-4o Mini</option>
      <option value="o3">o3</option>
      <option value="o4-mini">o4-mini</option>
    </optgroup>
    <optgroup label="Google">
      <option value="gemini-2.5-pro">Gemini 2.5 Pro</option>
      <option value="gemini-2.5-flash">Gemini 2.5 Flash</option>
    </optgroup>
    <optgroup label="Open Source">
      <option value="llama-4-maverick">Llama 4 Maverick</option>
      <option value="llama-4-scout">Llama 4 Scout</option>
      <option value="deepseek-r1">DeepSeek R1</option>
    </optgroup>
    <optgroup label="Free Tier">
      <option value="groq-llama-3.3">Llama 3.3 70B</option>
    </optgroup>
  </select>
  <select id="tokSelect" class="tok-sel" aria-label="Token multiplier">
    <option value="1">1x</option>
    <option value="30" selected>30x</option>
    <option value="60">60x</option>
    <option value="120">120x</option>
    <option value="300">300x</option>
  </select>
</div>

<!-- Chat messages -->
<div class="m-chat" id="chatArea">
  <div class="m-msg alfred">
    <div class="sender">Alfred</div>
    <div>Hey <?= $displayName ?>! I'm Alfred — your AI assistant. Ask me anything.</div>
  </div>
</div>

<!-- Input -->
<div class="m-input-area">
  <div class="m-input-row">
    <textarea id="chatInput" rows="1" placeholder="Ask Alfred…" inputmode="text" enterkeyhint="send" autocapitalize="sentences" autocorrect="on"></textarea>
    <button class="m-send-btn" id="sendBtn" aria-label="Send">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
    </button>
  </div>
</div>
<div class="m-status" id="statusBar">Ready</div>

<!-- Settings drawer -->
<div class="m-drawer-overlay" id="drawerOverlay"></div>
<div class="m-drawer" id="drawer">
  <button class="close-drawer" id="closeDrawer" aria-label="Close">✕</button>
  <h3>Settings</h3>
  <div class="drawer-item"><label>Signed in as</label> <?= $displayName ?></div>
  <div class="drawer-item"><label>Client ID</label> #<?= $clientId ?></div>
  <a href="/alfred-ide/" class="drawer-link">Open Full IDE (Desktop)</a>
  <a href="/alfred-ide-auth.php?action=logout" class="drawer-link danger">Sign Out</a>
</div>

<script>
(function() {
  'use strict';

  // ── Desktop redirect ──
  if (window.innerWidth >= 768 && !window.matchMedia('(pointer: coarse)').matches) {
    window.location.href = '/alfred-ide/';
    return;
  }

  // ── Auth ──
  const TOKEN = '<?= $safeToken ?>';
  let csrfToken = null;
  let convId = '';
  let sending = false;

  // ── DOM ──
  const chatArea = document.getElementById('chatArea');
  const chatInput = document.getElementById('chatInput');
  const sendBtn = document.getElementById('sendBtn');
  const statusBar = document.getElementById('statusBar');
  const modelSelect = document.getElementById('modelSelect');
  const tokSelect = document.getElementById('tokSelect');
  const drawer = document.getElementById('drawer');
  const drawerOverlay = document.getElementById('drawerOverlay');

  // ── Drawer ──
  document.getElementById('menuBtn').addEventListener('click', () => {
    drawer.classList.add('open');
    drawerOverlay.classList.add('open');
  });
  function closeDrawer() { drawer.classList.remove('open'); drawerOverlay.classList.remove('open'); }
  document.getElementById('closeDrawer').addEventListener('click', closeDrawer);
  drawerOverlay.addEventListener('click', closeDrawer);

  // ── Auto-resize textarea ──
  chatInput.addEventListener('input', () => {
    chatInput.style.height = 'auto';
    chatInput.style.height = Math.min(chatInput.scrollHeight, 120) + 'px';
  });

  // ── Send on Enter (no shift) ──
  chatInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      doSend();
    }
  });
  sendBtn.addEventListener('click', doSend);

  // ── Mobile keyboard: ensure input gets focus on touch ──
  chatInput.addEventListener('touchend', (e) => {
    e.stopPropagation();
    chatInput.focus();
  });

  // ── Handle visualViewport resize for virtual keyboard ──
  if (window.visualViewport) {
    window.visualViewport.addEventListener('resize', () => {
      document.body.style.height = window.visualViewport.height + 'px';
      chatArea.scrollTop = chatArea.scrollHeight;
    });
    window.visualViewport.addEventListener('scroll', () => {
      document.body.style.height = window.visualViewport.height + 'px';
    });
  }

  function setStatus(msg, active) {
    statusBar.textContent = msg;
    statusBar.className = 'm-status' + (active ? ' active' : '');
  }

  function addMessage(role, html, thinking) {
    const div = document.createElement('div');
    div.className = 'm-msg ' + role;
    const sender = document.createElement('div');
    sender.className = 'sender';
    sender.textContent = role === 'user' ? 'You' : role.charAt(0).toUpperCase() + role.slice(1);
    div.appendChild(sender);
    const content = document.createElement('div');
    content.className = 'msg-content';
    if (thinking) {
      content.innerHTML = '<span class="thinking-dots">Thinking</span>';
    } else {
      content.innerHTML = html;
    }
    div.appendChild(content);
    chatArea.appendChild(div);
    chatArea.scrollTop = chatArea.scrollHeight;
    return content;
  }

  function escapeHtml(s) {
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
  }

  function formatResponse(text) {
    // Basic markdown: code blocks, inline code, bold, links, lists
    let html = escapeHtml(text);
    // Code blocks
    html = html.replace(/```(\w*)\n([\s\S]*?)```/g, function(_, lang, code) {
      return '<pre><code>' + code + '</code></pre>';
    });
    // Inline code
    html = html.replace(/`([^`]+)`/g, '<code>$1</code>');
    // Bold
    html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    // Links
    html = html.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank" rel="noopener">$1</a>');
    // Line breaks
    html = html.replace(/\n/g, '<br>');
    return html;
  }

  async function doSend() {
    const text = chatInput.value.trim();
    if (!text || sending) return;
    sending = true;
    sendBtn.disabled = true;
    setStatus('Sending…', true);

    addMessage('user', escapeHtml(text));
    chatInput.value = '';
    chatInput.style.height = 'auto';

    const thinkingEl = addMessage('alfred', '', true);

    const payload = {
      message: text,
      agent: 'alfred',
      model: modelSelect.value,
      token_multiplier: parseInt(tokSelect.value) || 30,
      channel: 'ide-mobile',
      ide_session_token: TOKEN,
      conv_id: convId
    };

    const headers = {
      'Content-Type': 'application/json',
      'X-Alfred-Source': 'alfred-ide',
      'Authorization': 'Bearer ' + TOKEN
    };
    if (csrfToken) headers['X-CSRF-Token'] = csrfToken;

    try {
      let resp = await fetch('/api/alfred-chat.php', { method: 'POST', headers: headers, credentials: 'include', body: JSON.stringify(payload) });
      let data = await resp.json();

      // CSRF handshake
      if (data.csrf_token) csrfToken = data.csrf_token;
      let retries = 0;
      while (retries < 3 && (data.csrf_refresh || data.response === 'Session initialized. Please retry.' || data.error === 'CSRF validation failed')) {
        retries++;
        headers['X-CSRF-Token'] = csrfToken || '';
        resp = await fetch('/api/alfred-chat.php', { method: 'POST', headers: headers, credentials: 'include', body: JSON.stringify(payload) });
        data = await resp.json();
        if (data.csrf_token) csrfToken = data.csrf_token;
      }

      const responseText = data.response || data.message || data.error || 'No response from AI';
      if (data.conv_id) convId = data.conv_id;

      thinkingEl.innerHTML = formatResponse(responseText);

      // Add copy button for code blocks
      thinkingEl.querySelectorAll('pre').forEach(pre => {
        const btn = document.createElement('button');
        btn.className = 'copy-btn';
        btn.textContent = 'Copy';
        btn.onclick = () => {
          navigator.clipboard.writeText(pre.textContent).then(() => { btn.textContent = 'Copied!'; setTimeout(() => { btn.textContent = 'Copy'; }, 1500); });
        };
        pre.parentNode.insertBefore(btn, pre.nextSibling);
      });

      setStatus('Ready');
    } catch (err) {
      thinkingEl.innerHTML = '<span style="color:var(--danger)">Error: ' + escapeHtml(err.message || 'Request failed') + '</span>';
      setStatus('Error — try again');
    } finally {
      sending = false;
      sendBtn.disabled = false;
    }
  }
})();
</script>
</body>
</html>
