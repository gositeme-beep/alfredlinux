/* ==========================================================
   Alfred AI Chrome Extension — Side Panel Script
   ========================================================== */

document.addEventListener('DOMContentLoaded', () => {
  const messagesEl = document.getElementById('messages');
  const msgInput   = document.getElementById('msgInput');
  const sendBtn    = document.getElementById('spSendBtn');
  const voiceBtn   = document.getElementById('voiceBtn');
  const clearBtn   = document.getElementById('clearBtn');
  const ctxTitle   = document.getElementById('ctxTitle');
  const ctxUrl     = document.getElementById('ctxUrl');

  let chatHistory = [];
  let isRecording = false;
  let recognition = null;

  // ---- Load page context ----
  loadPageContext();

  function loadPageContext() {
    chrome.runtime.sendMessage({ type: 'GET_PAGE_CONTEXT' }, ctx => {
      if (ctx && !ctx.error) {
        ctxTitle.textContent = ctx.title || 'Unknown page';
        ctxUrl.textContent   = ctx.url || '';
      }
    });
  }

  // ---- Quick tool actions ----
  document.querySelectorAll('.sp-tool-chip').forEach(chip => {
    chip.addEventListener('click', () => {
      const action = chip.dataset.action;
      const prompts = {
        summarize: 'Summarize the current page for me.',
        seo:       'Run an SEO analysis on the current page.',
        security:  'Check the current page for security issues.',
        code:      'Review the code on the current page.',
        translate: 'Translate the current page content to French.',
        rewrite:   'Rewrite the content of the current page to be more professional.'
      };
      const prompt = prompts[action] || `Run ${action} on this page.`;
      msgInput.value = prompt;
      sendMessage();
    });
  });

  // ---- Send message ----
  sendBtn.addEventListener('click', sendMessage);
  msgInput.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendMessage();
    }
  });

  // Auto-resize textarea
  msgInput.addEventListener('input', () => {
    msgInput.style.height = 'auto';
    msgInput.style.height = Math.min(msgInput.scrollHeight, 120) + 'px';
  });

  function sendMessage() {
    const text = msgInput.value.trim();
    if (!text) return;

    // Add user message
    appendMessage('user', text);
    chatHistory.push({ role: 'user', content: text });
    msgInput.value = '';
    msgInput.style.height = 'auto';

    // Show typing indicator
    const typingEl = showTyping();

    // Build context-enriched message
    const contextMsg = `[Page: ${ctxUrl.textContent || 'N/A'}] ${text}`;

    chrome.runtime.sendMessage({
      type: 'API_REQUEST',
      endpoint: 'chat',
      method: 'POST',
      body: {
        message: contextMsg,
        history: chatHistory.slice(-10) // Last 10 messages for context
      }
    }, resp => {
      removeTyping(typingEl);

      if (resp && resp.error) {
        appendMessage('assistant', `Error: ${resp.error}`);
      } else if (resp && resp.reply) {
        appendMessage('assistant', resp.reply);
        chatHistory.push({ role: 'assistant', content: resp.reply });
      } else {
        const raw = JSON.stringify(resp, null, 2);
        appendMessage('assistant', raw);
        chatHistory.push({ role: 'assistant', content: raw });
      }
    });
  }

  function appendMessage(role, text) {
    const div = document.createElement('div');
    div.className = `sp-msg sp-msg--${role}`;
    div.textContent = text;
    messagesEl.appendChild(div);
    messagesEl.scrollTop = messagesEl.scrollHeight;
  }

  function showTyping() {
    const div = document.createElement('div');
    div.className = 'sp-typing';
    div.innerHTML = '<span></span><span></span><span></span>';
    messagesEl.appendChild(div);
    messagesEl.scrollTop = messagesEl.scrollHeight;
    return div;
  }

  function removeTyping(el) {
    if (el && el.parentNode) el.parentNode.removeChild(el);
  }

  // ---- Clear chat ----
  clearBtn.addEventListener('click', () => {
    chatHistory = [];
    messagesEl.innerHTML = '<div class="sp-msg sp-msg--system">Chat cleared. Ask me anything!</div>';
  });

  // ---- Voice Input (Web Speech API) ----
  voiceBtn.addEventListener('click', toggleVoice);

  function toggleVoice() {
    if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
      appendMessage('system', 'Voice input is not supported in this browser.');
      return;
    }

    if (isRecording && recognition) {
      recognition.stop();
      return;
    }

    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    recognition = new SpeechRecognition();
    recognition.continuous = false;
    recognition.interimResults = true;
    recognition.lang = 'en-US';

    recognition.onstart = () => {
      isRecording = true;
      voiceBtn.classList.add('recording');
    };

    recognition.onresult = e => {
      let transcript = '';
      for (let i = e.resultIndex; i < e.results.length; i++) {
        transcript += e.results[i][0].transcript;
      }
      msgInput.value = transcript;
    };

    recognition.onend = () => {
      isRecording = false;
      voiceBtn.classList.remove('recording');
      if (msgInput.value.trim()) sendMessage();
    };

    recognition.onerror = e => {
      isRecording = false;
      voiceBtn.classList.remove('recording');
      if (e.error !== 'aborted') {
        appendMessage('system', `Voice error: ${e.error}`);
      }
    };

    recognition.start();
  }

  // ---- Settings ----
  document.getElementById('spSettingsBtn').addEventListener('click', () => {
    chrome.runtime.openOptionsPage && chrome.runtime.openOptionsPage();
  });

  // ---- Listen for context menu actions ----
  chrome.runtime.onMessage.addListener(msg => {
    if (msg.type === 'CONTEXT_ACTION') {
      const actionPrompts = {
        ask:       `Tell me about this page: ${msg.url}`,
        summarize: `Summarize the article at: ${msg.url}`,
        seo:       `Run SEO check on: ${msg.url}`,
        security:  `Find security issues on: ${msg.url}`
      };
      const prompt = actionPrompts[msg.action] || `Analyze: ${msg.url}`;
      if (msg.selectedText) {
        msgInput.value = `${prompt}\n\nSelected text: "${msg.selectedText}"`;
      } else {
        msgInput.value = prompt;
      }
      sendMessage();
    }
  });

  // Refresh context when panel is focused
  window.addEventListener('focus', loadPageContext);
});
