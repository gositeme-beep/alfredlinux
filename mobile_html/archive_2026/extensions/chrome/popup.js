/* ==========================================================
   Alfred AI Chrome Extension — Popup Script
   ========================================================== */

document.addEventListener('DOMContentLoaded', init);

async function init() {
  const setupScreen = document.getElementById('setupScreen');
  const mainScreen  = document.getElementById('mainScreen');
  const saveKeyBtn  = document.getElementById('saveKeyBtn');
  const apiKeyInput = document.getElementById('apiKeyInput');
  const chatInput   = document.getElementById('chatInput');
  const sendBtn     = document.getElementById('sendBtn');
  const responseArea = document.getElementById('responseArea');
  const settingsBtn = document.getElementById('settingsBtn');
  const openPanelBtn = document.getElementById('openPanelBtn');

  // Check for stored API key
  chrome.runtime.sendMessage({ type: 'GET_API_KEY' }, response => {
    if (response && response.key) {
      showMain();
    } else {
      showSetup();
    }
  });

  function showSetup() {
    setupScreen.classList.remove('hidden');
    mainScreen.classList.add('hidden');
  }

  function showMain() {
    setupScreen.classList.add('hidden');
    mainScreen.classList.remove('hidden');
  }

  // Save API key
  saveKeyBtn.addEventListener('click', () => {
    const key = apiKeyInput.value.trim();
    if (!key) {
      apiKeyInput.style.borderColor = '#d63031';
      return;
    }
    chrome.runtime.sendMessage({ type: 'SET_API_KEY', key }, resp => {
      if (resp && resp.success) showMain();
    });
  });

  // Settings — go back to setup
  settingsBtn.addEventListener('click', () => showSetup());

  // Quick action buttons
  document.querySelectorAll('.action-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const action = btn.dataset.action;
      handleAction(action);
    });
  });

  // Send chat message
  sendBtn.addEventListener('click', sendChat);
  chatInput.addEventListener('keydown', e => {
    if (e.key === 'Enter') sendChat();
  });

  async function sendChat() {
    const message = chatInput.value.trim();
    if (!message) return;
    chatInput.value = '';
    showResponse('<span class="loading">Thinking…</span>');
    chrome.runtime.sendMessage({
      type: 'API_REQUEST',
      endpoint: 'chat',
      method: 'POST',
      body: { message }
    }, resp => {
      if (resp && resp.error) {
        showResponse(`<span style="color:#d63031;">Error: ${escapeHtml(resp.error)}</span>`);
      } else if (resp && resp.reply) {
        showResponse(escapeHtml(resp.reply));
      } else {
        showResponse(escapeHtml(JSON.stringify(resp, null, 2)));
      }
    });
  }

  async function handleAction(action) {
    showResponse('<span class="loading">Analyzing page…</span>');

    // Get page context from content script
    chrome.runtime.sendMessage({ type: 'GET_PAGE_CONTEXT' }, ctx => {
      const pageContext = ctx || {};
      let endpoint = 'chat';
      let message = '';

      switch (action) {
        case 'chat':
          chatInput.focus();
          responseArea.classList.remove('active');
          return;
        case 'analyze':
          message = `Analyze this webpage: ${pageContext.url || 'unknown'}\nTitle: ${pageContext.title || 'unknown'}\nDescription: ${pageContext.description || 'N/A'}`;
          break;
        case 'seo':
          message = `Check SEO for: ${pageContext.url || 'unknown'}\nTitle: ${pageContext.title || 'unknown'}\nHeadings: ${(pageContext.headings || []).join(', ')}`;
          break;
        case 'summarize':
          message = `Summarize this article:\nURL: ${pageContext.url || 'unknown'}\nTitle: ${pageContext.title || 'unknown'}\nContent: ${(pageContext.content || '').substring(0, 2000)}`;
          break;
      }

      chrome.runtime.sendMessage({
        type: 'API_REQUEST',
        endpoint,
        method: 'POST',
        body: { message }
      }, resp => {
        if (resp && resp.error) {
          showResponse(`<span style="color:#d63031;">Error: ${escapeHtml(resp.error)}</span>`);
        } else if (resp && resp.reply) {
          showResponse(escapeHtml(resp.reply));
        } else {
          showResponse(escapeHtml(JSON.stringify(resp, null, 2)));
        }
      });
    });
  }

  // Open side panel
  openPanelBtn.addEventListener('click', e => {
    e.preventDefault();
    chrome.tabs.query({ active: true, currentWindow: true }, tabs => {
      if (tabs[0]) {
        chrome.sidePanel.open({ tabId: tabs[0].id }).catch(() => {});
      }
    });
  });

  function showResponse(html) {
    responseArea.innerHTML = html;
    responseArea.classList.add('active');
  }

  function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  }

  // Listen for context menu actions routed via background
  chrome.runtime.onMessage.addListener((msg) => {
    if (msg.type === 'CONTEXT_ACTION') {
      handleAction(msg.action);
    }
  });
}
