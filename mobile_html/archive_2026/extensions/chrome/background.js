/* ==========================================================
   Alfred AI Chrome Extension — Background Service Worker
   ========================================================== */

const API_BASE = 'https://gositeme.com/api/v1/';

/* ---------- Context Menu Setup ---------- */
chrome.runtime.onInstalled.addListener(() => {
  const menuItems = [
    { id: 'alfred-ask',       title: 'Ask Alfred about this page' },
    { id: 'alfred-summarize', title: 'Summarize this article' },
    { id: 'alfred-seo',       title: 'Check SEO' },
    { id: 'alfred-security',  title: 'Find security issues' }
  ];

  menuItems.forEach(item => {
    chrome.contextMenus.create({
      id: item.id,
      title: item.title,
      contexts: ['page', 'selection']
    });
  });
});

/* ---------- Context Menu Click Handler ---------- */
chrome.contextMenus.onClicked.addListener(async (info, tab) => {
  const actionMap = {
    'alfred-ask':       'ask',
    'alfred-summarize': 'summarize',
    'alfred-seo':       'seo',
    'alfred-security':  'security'
  };

  const action = actionMap[info.menuItemId];
  if (!action) return;

  // Try opening side panel first, fall back to popup message
  try {
    await chrome.sidePanel.open({ tabId: tab.id });
    setTimeout(() => {
      chrome.runtime.sendMessage({
        type: 'CONTEXT_ACTION',
        action,
        url: tab.url,
        title: tab.title,
        selectedText: info.selectionText || ''
      });
    }, 500);
  } catch {
    // Side panel may not be supported; send to popup
    chrome.runtime.sendMessage({
      type: 'CONTEXT_ACTION',
      action,
      url: tab.url,
      title: tab.title,
      selectedText: info.selectionText || ''
    });
  }
});

/* ---------- API Key Management ---------- */
async function getApiKey() {
  return new Promise(resolve => {
    chrome.storage.sync.get(['alfredApiKey'], result => {
      resolve(result.alfredApiKey || null);
    });
  });
}

async function setApiKey(key) {
  return new Promise(resolve => {
    chrome.storage.sync.set({ alfredApiKey: key }, resolve);
  });
}

/* ---------- API Request Helper ---------- */
async function apiRequest(endpoint, method = 'GET', body = null) {
  const apiKey = await getApiKey();
  if (!apiKey) {
    return { error: 'API key not configured. Open the extension popup to set your key.' };
  }

  const options = {
    method,
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${apiKey}`
    }
  };

  if (body) options.body = JSON.stringify(body);

  try {
    const resp = await fetch(`${API_BASE}${endpoint}`, options);
    if (resp.status === 429) {
      const retryAfter = resp.headers.get('Retry-After') || 5;
      return { error: `Rate limited. Retry after ${retryAfter}s.` };
    }
    return await resp.json();
  } catch (err) {
    return { error: `Network error: ${err.message}` };
  }
}

/* ---------- Message Passing ---------- */
chrome.runtime.onMessage.addListener((msg, sender, sendResponse) => {
  if (msg.type === 'API_REQUEST') {
    apiRequest(msg.endpoint, msg.method || 'GET', msg.body)
      .then(sendResponse)
      .catch(err => sendResponse({ error: err.message }));
    return true; // async response
  }

  if (msg.type === 'SET_API_KEY') {
    setApiKey(msg.key).then(() => sendResponse({ success: true }));
    return true;
  }

  if (msg.type === 'GET_API_KEY') {
    getApiKey().then(key => sendResponse({ key }));
    return true;
  }

  if (msg.type === 'GET_PAGE_CONTEXT') {
    chrome.tabs.query({ active: true, currentWindow: true }, tabs => {
      if (tabs[0]) {
        chrome.tabs.sendMessage(tabs[0].id, { type: 'EXTRACT_CONTEXT' }, sendResponse);
      } else {
        sendResponse({ error: 'No active tab' });
      }
    });
    return true;
  }
});
