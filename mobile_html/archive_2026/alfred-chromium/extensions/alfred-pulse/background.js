/**
 * Alfred Pulse — Background Service Worker
 * Polls for new social notifications
 */

const POLL_INTERVAL = 60000; // 1 minute

async function checkNotifications() {
  try {
    const res = await fetch('https://gositeme.com/api/pulse.php?action=notifications&unread=1&limit=5', {
      credentials: 'include'
    });
    const data = await res.json();
    if (data.success && data.unread_count > 0) {
      chrome.action.setBadgeText({ text: data.unread_count.toString() });
      chrome.action.setBadgeBackgroundColor({ color: '#10B981' });

      // Show notification for new items
      if (data.notifications && data.notifications.length) {
        const latest = data.notifications[0];
        chrome.notifications.create(`pulse-${latest.id}`, {
          type: 'basic',
          iconUrl: 'icons/icon128.png',
          title: 'Pulse Social',
          message: latest.message || 'New activity on your posts'
        });
      }
    } else {
      chrome.action.setBadgeText({ text: '' });
    }
  } catch (e) {}
}

// Poll on alarm
chrome.alarms.create('pulse-poll', { periodInMinutes: 1 });
chrome.alarms.onAlarm.addListener((alarm) => {
  if (alarm.name === 'pulse-poll') checkNotifications();
});

// Check on startup
chrome.runtime.onStartup.addListener(checkNotifications);
chrome.runtime.onInstalled.addListener(checkNotifications);

// Clear badge when popup opens
chrome.runtime.onMessage.addListener((msg) => {
  if (msg.action === 'popupOpened') {
    chrome.action.setBadgeText({ text: '' });
  }
});
