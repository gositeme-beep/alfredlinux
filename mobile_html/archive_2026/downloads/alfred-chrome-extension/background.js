/**
 * Alfred AI Chrome Extension — Background Service Worker
 * Handles context menus, authentication, notifications, and side panel.
 * (c) GoSiteMe Inc. — https://gositeme.com
 */

const API_BASE = 'https://gositeme.com/api';

/* ─── Install / Startup ─── */
chrome.runtime.onInstalled.addListener(() => {
  // Context-menu entries
  const menus = [
    { id: 'alfred-ask-page',     title: 'Ask Alfred about this page' },
    { id: 'alfred-summarize',    title: 'Summarize this text',        contexts: ['selection'] },
    { id: 'alfred-translate',    title: 'Translate selection',        contexts: ['selection'] },
    { id: 'alfred-seo',         title: 'Check SEO for this page' },
    { id: 'alfred-security',    title: 'Find security issues' },
  ];

  menus.forEach((m) => {
    chrome.contextMenus.create({
      id: m.id,
      title: m.title,
      contexts: m.contexts || ['page'],
    });
  });

  // Set default storage values
  chrome.storage.local.get(['apiKey', 'user'], (data) => {
    if (!data.apiKey) {
      chrome.storage.local.set({ apiKey: '', user: null, history: [] });
    }
  });
});

/* ─── Context Menu Click Handler ─── */
chrome.contextMenus.onClicked.addListener(async (info, tab) => {
  const apiKey = await getApiKey();
  if (!apiKey) {
    notify('Alfred AI', 'Please set your API key first. Click the Alfred icon.');
    return;
  }

  let payload = {};

  switch (info.menuItemId) {
    case 'alfred-ask-page':
      payload = {
        tool: 'page_analyzer',
        message: `Analyze this page: ${tab.url}`,
        url: tab.url,
      };
      break;

    case 'alfred-summarize':
      payload = {
        tool: 'text_summarizer',
        message: `Summarize: ${info.selectionText}`,
        text: info.selectionText,
      };
      break;

    case 'alfred-translate':
      payload = {
        tool: 'translator',
        message: `Translate: ${info.selectionText}`,
        text: info.selectionText,
      };
      break;

    case 'alfred-seo':
      payload = {
        tool: 'seo_analyzer',
        message: `Check SEO for: ${tab.url}`,
        url: tab.url,
      };
      break;

    case 'alfred-security':
      payload = {
        tool: 'security_scanner',
        message: `Scan for security issues: ${tab.url}`,
        url: tab.url,
      };
      break;
  }

  try {
    notify('Alfred AI', 'Processing your request…');
    const result = await callAlfred(apiKey, payload);
    // Send result to side panel
    chrome.runtime.sendMessage({ type: 'alfred-response', data: result });
    // Open side panel to show result
    if (chrome.sidePanel) {
      chrome.sidePanel.open({ tabId: tab.id });
    }
  } catch (err) {
    notify('Alfred AI', `Error: ${err.message}`);
  }
});

/* ─── Action Click — open side panel ─── */
chrome.action.onClicked.addListener(async (tab) => {
  if (chrome.sidePanel) {
    await chrome.sidePanel.open({ tabId: tab.id });
  }
});

/* ─── Message Listener ─── */
chrome.runtime.onMessage.addListener((msg, sender, sendResponse) => {
  if (msg.type === 'alfred-chat') {
    getApiKey().then((apiKey) => {
      if (!apiKey) {
        sendResponse({ error: 'No API key set' });
        return;
      }
      callAlfred(apiKey, msg.payload)
        .then((result) => sendResponse({ data: result }))
        .catch((err) => sendResponse({ error: err.message }));
    });
    return true; // keep channel open for async
  }

  if (msg.type === 'get-auth') {
    chrome.storage.local.get(['apiKey', 'user'], (data) => {
      sendResponse(data);
    });
    return true;
  }

  if (msg.type === 'set-auth') {
    validateApiKey(msg.apiKey)
      .then((user) => {
        chrome.storage.local.set({ apiKey: msg.apiKey, user }, () => {
          sendResponse({ success: true, user });
        });
      })
      .catch((err) => sendResponse({ success: false, error: err.message }));
    return true;
  }

  if (msg.type === 'logout') {
    chrome.storage.local.set({ apiKey: '', user: null }, () => {
      sendResponse({ success: true });
    });
    return true;
  }
});

/* ─── API Helpers ─── */

async function getApiKey() {
  return new Promise((resolve) => {
    chrome.storage.local.get('apiKey', (data) => resolve(data.apiKey || ''));
  });
}

async function validateApiKey(apiKey) {
  const res = await fetch(`${API_BASE}/auth.php`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${apiKey}`,
    },
    body: JSON.stringify({ action: 'validate' }),
  });
  if (!res.ok) throw new Error('Invalid API key');
  const json = await res.json();
  if (!json.success) throw new Error(json.error || 'Invalid API key');
  return json.user;
}

async function callAlfred(apiKey, payload) {
  const res = await fetch(`${API_BASE}/alfred-chat.php`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${apiKey}`,
    },
    body: JSON.stringify(payload),
  });
  if (!res.ok) throw new Error(`API error ${res.status}`);
  return res.json();
}

function notify(title, message) {
  chrome.notifications.create({
    type: 'basic',
    iconUrl: 'icons/icon128.png',
    title,
    message,
  });
}
