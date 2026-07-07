/**
 * Alfred Veil — Background Service Worker
 * Maintains WebSocket connection for encrypted message notifications
 */

let ws = null;
let reconnectTimer = null;

function connect() {
  if (ws && ws.readyState === WebSocket.OPEN) return;

  ws = new WebSocket('wss://gositeme.com/ws/veil');

  ws.onopen = () => {
    console.log('[Veil] Connected to messaging server');
    if (reconnectTimer) { clearTimeout(reconnectTimer); reconnectTimer = null; }
  };

  ws.onmessage = (event) => {
    try {
      const msg = JSON.parse(event.data);
      if (msg.type === 'new_message') {
        chrome.notifications.create(`veil-msg-${msg.id}`, {
          type: 'basic',
          iconUrl: 'icons/icon128.png',
          title: `${msg.sender_name || 'New Message'}`,
          message: msg.preview || 'Encrypted message received'
        });
        chrome.action.setBadgeText({ text: (msg.unread_count || '').toString() });
        chrome.action.setBadgeBackgroundColor({ color: '#8B5CF6' });
      }
    } catch (e) {}
  };

  ws.onclose = () => {
    ws = null;
    reconnectTimer = setTimeout(connect, 5000);
  };

  ws.onerror = () => {
    ws?.close();
  };
}

// Connect on install/startup
chrome.runtime.onInstalled.addListener(connect);
chrome.runtime.onStartup.addListener(connect);

// Clear badge when popup opens
chrome.runtime.onMessage.addListener((msg, sender, sendResponse) => {
  if (msg.action === 'popupOpened') {
    chrome.action.setBadgeText({ text: '' });
  }
});
