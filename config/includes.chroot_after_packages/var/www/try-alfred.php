<?php
/**
 * Try Alfred — Free public AI chat (no login required)
 * Top-of-funnel user acquisition page.
 * 5 free messages per day → signup wall → conversion.
 */
$pageTitle       = "Try Alfred AI — Free Chat | GoSiteMe";
$pageDescription = "Chat with Alfred AI for free. No sign-up required. Ask anything — coding, business, creative writing, research. 5 free messages per day.";

// Minimal — load header for nav but this page works anonymously
if (session_status() === PHP_SESSION_NONE) session_start();
$isLoggedIn = !empty($_SESSION['uid']);

// Logged-in users should use the full Alfred experience (no trial wall)
if ($isLoggedIn) {
  header('Location: /alfred.php');
  exit;
}

include __DIR__ . '/includes/site-header.inc.php';
?>

<style>
  /* ── Try Alfred Page ──────────────────────────────────────────────── */
  .ta-page {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    height: calc(100vh - 80px);
    display: flex;
    flex-direction: column;
    font-family: var(--alfred-font, system-ui, -apple-system, sans-serif);
  }

  /* Hero (shown only when no messages) */
  .ta-hero {
    text-align: center;
    padding: 60px 20px 40px;
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
  }
  .ta-hero.hidden { display: none; }
  .ta-hero h1 {
    font-size: 2.5rem;
    font-weight: 800;
    background: linear-gradient(135deg, #6366f1, #a78bfa, #22d3ee);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 12px;
    line-height: 1.2;
  }
  .ta-hero p {
    color: var(--alfred-text-secondary, #999);
    font-size: 1.1rem;
    max-width: 500px;
    margin-bottom: 32px;
    line-height: 1.6;
  }
  .ta-suggestions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: center;
    max-width: 600px;
  }
  .ta-suggestion {
    padding: 10px 18px;
    background: var(--alfred-card-bg, #1a1a2e);
    border: 1px solid var(--alfred-border, #2a2a4a);
    border-radius: 12px;
    color: var(--alfred-text, #e0e0e0);
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s;
  }
  .ta-suggestion:hover {
    border-color: var(--alfred-primary, #6366f1);
    background: rgba(99, 102, 241, 0.1);
    transform: translateY(-1px);
  }

  /* Chat area */
  .ta-chat {
    flex: 1;
    overflow-y: auto;
    padding: 20px 0;
    scroll-behavior: smooth;
    display: none;
  }
  .ta-chat.active {
    display: block;
  }
  .ta-message {
    display: flex;
    gap: 12px;
    padding: 16px 0;
    animation: ta-fadeIn 0.3s ease;
  }
  @keyframes ta-fadeIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
  }
  .ta-avatar {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
    margin-top: 2px;
  }
  .ta-avatar.user {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
  }
  .ta-avatar.alfred {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
  }
  .ta-bubble {
    flex: 1;
    color: var(--alfred-text, #e0e0e0);
    font-size: 0.95rem;
    line-height: 1.7;
    max-width: calc(100% - 50px);
  }
  .ta-bubble p { margin: 0 0 8px; }
  .ta-bubble p:last-child { margin-bottom: 0; }
  .ta-bubble code {
    background: rgba(255,255,255,0.08);
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.85em;
    font-family: 'JetBrains Mono', monospace;
  }
  .ta-bubble pre {
    background: #0d1117;
    border: 1px solid var(--alfred-border, #2a2a4a);
    border-radius: 8px;
    padding: 12px 16px;
    overflow-x: auto;
    margin: 12px 0;
    font-size: 0.85rem;
    line-height: 1.5;
  }
  .ta-bubble pre code {
    background: none;
    padding: 0;
  }
  .ta-typing {
    display: flex;
    gap: 4px;
    padding: 8px 0;
  }
  .ta-typing span {
    width: 8px;
    height: 8px;
    background: var(--alfred-primary, #6366f1);
    border-radius: 50%;
    animation: ta-bounce 1.4s ease-in-out infinite;
  }
  .ta-typing span:nth-child(2) { animation-delay: 0.2s; }
  .ta-typing span:nth-child(3) { animation-delay: 0.4s; }
  @keyframes ta-bounce {
    0%, 80%, 100% { transform: scale(0.6); opacity: 0.4; }
    40% { transform: scale(1); opacity: 1; }
  }

  /* Input area */
  .ta-input-area {
    padding: 16px 0 8px;
    border-top: 1px solid var(--alfred-border, #2a2a4a);
    position: relative;
  }
  .ta-input-wrapper {
    display: flex;
    gap: 8px;
    align-items: end;
  }
  .ta-input {
    flex: 1;
    padding: 12px 16px;
    background: var(--alfred-card-bg, #1a1a2e);
    border: 1px solid var(--alfred-border, #2a2a4a);
    border-radius: 12px;
    color: #fff;
    font-size: 0.95rem;
    resize: none;
    outline: none;
    font-family: inherit;
    min-height: 44px;
    max-height: 120px;
    line-height: 1.4;
    transition: border-color 0.2s;
  }
  .ta-input:focus {
    border-color: var(--alfred-primary, #6366f1);
  }
  .ta-input::placeholder {
    color: var(--alfred-text-secondary, #666);
  }
  .ta-send {
    width: 44px;
    height: 44px;
    background: var(--alfred-primary, #6366f1);
    border: none;
    border-radius: 12px;
    color: #fff;
    font-size: 18px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: opacity 0.2s;
    flex-shrink: 0;
  }
  .ta-send:hover { opacity: 0.85; }
  .ta-send:disabled { opacity: 0.4; cursor: not-allowed; }

  .ta-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 8px;
    font-size: 0.75rem;
    color: var(--alfred-text-secondary, #666);
  }
  .ta-remaining {
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .ta-dots {
    display: flex;
    gap: 4px;
  }
  .ta-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--alfred-primary, #6366f1);
    transition: background 0.2s;
  }
  .ta-dot.used { background: rgba(99, 102, 241, 0.2); }
  .ta-powered a {
    color: var(--alfred-text-secondary, #666);
    text-decoration: none;
  }
  .ta-powered a:hover { color: #fff; }

  /* Signup wall overlay */
  .ta-wall {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.8);
    backdrop-filter: blur(8px);
    z-index: 10000;
    display: none;
    align-items: center;
    justify-content: center;
    animation: ta-fadeIn 0.3s;
  }
  .ta-wall.show { display: flex; }
  .ta-wall-card {
    background: var(--alfred-card-bg, #1a1a2e);
    border: 1px solid var(--alfred-border, #2a2a4a);
    border-radius: 20px;
    padding: 48px 40px;
    text-align: center;
    max-width: 440px;
    width: 90%;
  }
  .ta-wall-card h2 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 12px;
  }
  .ta-wall-card p {
    color: var(--alfred-text-secondary, #999);
    margin-bottom: 24px;
    line-height: 1.6;
  }
  .ta-wall-perks {
    text-align: left;
    margin-bottom: 24px;
  }
  .ta-wall-perks div {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 6px 0;
    color: var(--alfred-text, #e0e0e0);
    font-size: 0.9rem;
  }
  .ta-wall-perks .perk-icon {
    color: #22d3ee;
    font-size: 14px;
    width: 20px;
    text-align: center;
  }
  .ta-wall-btn {
    display: block;
    width: 100%;
    padding: 14px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    border: none;
    border-radius: 12px;
    color: #fff;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    margin-bottom: 12px;
    transition: transform 0.1s;
  }
  .ta-wall-btn:hover { transform: scale(1.02); }
  .ta-wall-login {
    color: var(--alfred-text-secondary, #666);
    font-size: 0.85rem;
  }
  .ta-wall-login a {
    color: var(--alfred-primary, #6366f1);
    text-decoration: none;
  }

  @media (max-width: 640px) {
    .ta-hero h1 { font-size: 1.8rem; }
    .ta-hero p { font-size: 0.95rem; }
    .ta-wall-card { padding: 32px 24px; }
  }
</style>

<div class="ta-page">
  <!-- Hero (before first message) -->
  <div class="ta-hero" id="ta-hero">
    <h1>Chat with Alfred AI</h1>
    <p>Ask anything — coding, business ideas, creative writing, research, math. No sign-up required.</p>
    <div class="ta-suggestions">
      <div class="ta-suggestion" onclick="useSuggestion(this)">Help me build a website</div>
      <div class="ta-suggestion" onclick="useSuggestion(this)">Write a business plan</div>
      <div class="ta-suggestion" onclick="useSuggestion(this)">Explain quantum computing</div>
      <div class="ta-suggestion" onclick="useSuggestion(this)">Debug my Python code</div>
      <div class="ta-suggestion" onclick="useSuggestion(this)">Generate a marketing strategy</div>
      <div class="ta-suggestion" onclick="useSuggestion(this)">What can you do?</div>
    </div>
  </div>

  <!-- Chat messages -->
  <div class="ta-chat" id="ta-chat"></div>

  <!-- Input -->
  <div class="ta-input-area">
    <div class="ta-input-wrapper">
      <textarea class="ta-input" id="ta-input" placeholder="Ask Alfred anything…" rows="1"
        maxlength="2000" autofocus></textarea>
      <button class="ta-send" id="ta-send" onclick="sendMessage()" title="Send">
        <i class="fas fa-paper-plane"></i>
      </button>
    </div>
    <div class="ta-meta">
      <div class="ta-remaining">
        <div class="ta-dots" id="ta-dots"></div>
        <span id="ta-remaining-text"></span>
      </div>
      <div class="ta-powered">
        <a href="/">Powered by GoSiteMe</a>
      </div>
    </div>
  </div>
</div>

<!-- Signup Wall -->
<div class="ta-wall" id="ta-wall">
  <div class="ta-wall-card">
    <h2>You're loving Alfred! 🎉</h2>
    <p>You've used all your free messages today. Sign up in 30 seconds to unlock more.</p>
    <div class="ta-wall-perks">
      <div><span class="perk-icon">✓</span> 50 free AI messages per month</div>
      <div><span class="perk-icon">✓</span> Access to 13,000+ AI tools</div>
      <div><span class="perk-icon">✓</span> Voice commands & phone access</div>
      <div><span class="perk-icon">✓</span> Browser-based crypto mining</div>
      <div><span class="perk-icon">✓</span> Full AI coding IDE (Alfred IDE)</div>
    </div>
    <a href="/register" class="ta-wall-btn">Create Free Account</a>
    <div class="ta-wall-login">
      Already have an account? <a href="/login.php">Log in</a>
    </div>
  </div>
</div>

<script>
(function() {
  'use strict';

  const chatEl    = document.getElementById('ta-chat');
  const heroEl    = document.getElementById('ta-hero');
  const inputEl   = document.getElementById('ta-input');
  const sendBtn   = document.getElementById('ta-send');
  const wallEl    = document.getElementById('ta-wall');
  const dotsEl    = document.getElementById('ta-dots');
  const remText   = document.getElementById('ta-remaining-text');

  let history   = [];
  let remaining = 5;
  let total     = 5;
  let sending   = false;

  // Auto-resize textarea
  inputEl.addEventListener('input', () => {
    inputEl.style.height = 'auto';
    inputEl.style.height = Math.min(inputEl.scrollHeight, 120) + 'px';
  });

  // Enter to send, Shift+Enter for newline
  inputEl.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendMessage();
    }
  });

  // Load status on page load
  (async function init() {
    try {
      const r = await fetch('/middleware/api/trial/status');
      const d = await r.json();
      if (d.ok) {
        remaining = d.remaining;
        total = d.total;
      }
    } catch {}
    updateDots();
  })();

  function updateDots() {
    const used = total - remaining;
    dotsEl.innerHTML = '';
    for (let i = 0; i < total; i++) {
      const dot = document.createElement('span');
      dot.className = 'ta-dot' + (i < used ? ' used' : '');
      dotsEl.appendChild(dot);
    }
    if (remaining <= 0) {
      remText.textContent = 'No free messages left';
    } else if (remaining === 1) {
      remText.textContent = '1 message remaining';
    } else {
      remText.textContent = remaining + ' messages remaining';
    }
  }

  function addMessage(role, content) {
    const div = document.createElement('div');
    div.className = 'ta-message';
    const isUser = role === 'user';
    div.innerHTML = `
      <div class="ta-avatar ${isUser ? 'user' : 'alfred'}">${isUser ? '👤' : '🤖'}</div>
      <div class="ta-bubble">${isUser ? escapeHtml(content) : formatMarkdown(content)}</div>
    `;
    chatEl.appendChild(div);
    chatEl.scrollTop = chatEl.scrollHeight;
    return div;
  }

  function addTyping() {
    const div = document.createElement('div');
    div.className = 'ta-message';
    div.id = 'ta-typing';
    div.innerHTML = `
      <div class="ta-avatar alfred">🤖</div>
      <div class="ta-bubble">
        <div class="ta-typing"><span></span><span></span><span></span></div>
      </div>
    `;
    chatEl.appendChild(div);
    chatEl.scrollTop = chatEl.scrollHeight;
  }

  function removeTyping() {
    const el = document.getElementById('ta-typing');
    if (el) el.remove();
  }

  window.sendMessage = async function() {
    if (sending) return;
    const msg = inputEl.value.trim();
    if (!msg) return;

    if (remaining <= 0) {
      wallEl.classList.add('show');
      return;
    }

    // Show message
    heroEl.classList.add('hidden');
    chatEl.classList.add('active');
    addMessage('user', msg);
    inputEl.value = '';
    inputEl.style.height = 'auto';
    sending = true;
    sendBtn.disabled = true;
    addTyping();

    try {
      const r = await fetch('/middleware/api/trial/chat', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          message: msg,
          history: history.slice(-4),
        }),
      });

      removeTyping();
      const d = await r.json();

      if (d.limitReached) {
        remaining = 0;
        updateDots();
        wallEl.classList.add('show');
        return;
      }

      if (d.ok) {
        addMessage('assistant', d.reply);
        history.push({ role: 'user', content: msg });
        history.push({ role: 'assistant', content: d.reply });
        remaining = d.remaining;
        updateDots();

        if (remaining <= 0) {
          // Show wall after a brief delay so they can read the response
          setTimeout(() => wallEl.classList.add('show'), 3000);
        }
      } else {
        addMessage('assistant', d.error || 'Something went wrong. Please try again.');
      }
    } catch {
      removeTyping();
      addMessage('assistant', 'Connection error. Please check your internet and try again.');
    } finally {
      sending = false;
      sendBtn.disabled = false;
      inputEl.focus();
    }
  };

  window.useSuggestion = function(el) {
    inputEl.value = el.textContent;
    sendMessage();
  };

  function escapeHtml(s) {
    const div = document.createElement('div');
    div.textContent = s;
    return div.innerHTML.replace(/\n/g, '<br>');
  }

  function formatMarkdown(text) {
    // Basic markdown rendering
    let html = escapeHtmlLight(text);

    // Code blocks
    html = html.replace(/```(\w*)\n?([\s\S]*?)```/g, (_, lang, code) => {
      return '<pre><code>' + code.trim() + '</code></pre>';
    });

    // Inline code
    html = html.replace(/`([^`]+)`/g, '<code>$1</code>');

    // Bold
    html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');

    // Italic
    html = html.replace(/\*(.+?)\*/g, '<em>$1</em>');

    // Headers
    html = html.replace(/^### (.+)$/gm, '<h4>$1</h4>');
    html = html.replace(/^## (.+)$/gm, '<h3>$1</h3>');
    html = html.replace(/^# (.+)$/gm, '<h2>$1</h2>');

    // Lists
    html = html.replace(/^- (.+)$/gm, '<li>$1</li>');
    html = html.replace(/^(\d+)\. (.+)$/gm, '<li>$2</li>');

    // Line breaks (but not inside pre tags)
    html = html.replace(/(?<!<\/pre>)\n/g, '<br>');

    return html;
  }

  function escapeHtmlLight(s) {
    return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }
})();
</script>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
