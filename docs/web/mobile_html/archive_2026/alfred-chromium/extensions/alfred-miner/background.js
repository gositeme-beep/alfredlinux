/**
 * Alfred Miner — Background Service Worker
 * SHA-256 proof-of-work mining for GSM tokens
 * Runs in background, respects CPU throttle settings
 */

const API_BASE = 'https://gositeme.com/api/mining.php';
const GSM_PER_HASH_BLOCK = 0.001; // per 1M hashes
const HASH_BLOCK_SIZE = 1_000_000;
const STATS_INTERVAL = 2000; // ms

let mining = false;
let throttle = 0.30; // 30% CPU default
let difficulty = 4;
let totalHashes = 0;
let blocksFound = 0;
let earnings = 0;
let hashrate = 0;
let startTime = 0;

// Offscreen document for mining (MV3 doesn't allow Web Workers directly)
let offscreenReady = false;

async function ensureOffscreen() {
  if (offscreenReady) return;
  try {
    await chrome.offscreen.createDocument({
      url: 'offscreen.html',
      reasons: ['WORKERS'],
      justification: 'SHA-256 mining computation in Web Worker'
    });
    offscreenReady = true;
  } catch (e) {
    // Already exists
    if (e.message.includes('already exists')) offscreenReady = true;
  }
}

// Listen for messages from popup and offscreen
chrome.runtime.onMessage.addListener((msg, sender, sendResponse) => {
  switch (msg.action) {
    case 'start':
      startMining();
      sendResponse({ ok: true });
      break;
    case 'stop':
      stopMining();
      sendResponse({ ok: true });
      break;
    case 'toggle':
      mining ? stopMining() : startMining();
      sendResponse({ ok: true, mining });
      break;
    case 'setThrottle':
      throttle = Math.max(0.05, Math.min(1.0, msg.value));
      chrome.runtime.sendMessage({ action: 'throttleUpdated', throttle });
      sendResponse({ ok: true });
      break;
    case 'getStatus':
      sendResponse({ mining, throttle, hashrate, totalHashes, blocksFound, earnings });
      break;
    case 'miningStats':
      // From offscreen worker
      hashrate = msg.hashrate || 0;
      totalHashes = msg.totalHashes || 0;
      blocksFound = msg.blocksFound || 0;
      earnings = msg.earnings || 0;
      updateBadge();
      break;
    case 'submitWork':
      submitWork(msg.work).then(r => sendResponse(r));
      return true; // async
  }
});

async function startMining() {
  if (mining) return;
  mining = true;
  startTime = Date.now();
  await ensureOffscreen();
  chrome.runtime.sendMessage({ action: 'workerStart', throttle, difficulty });
  updateBadge();
  chrome.notifications.create('mining-started', {
    type: 'basic',
    iconUrl: 'icons/icon128.png',
    title: 'Alfred Miner',
    message: 'Mining started — earning GSM tokens in background'
  });
}

function stopMining() {
  mining = false;
  chrome.runtime.sendMessage({ action: 'workerStop' });
  chrome.action.setBadgeText({ text: '' });
}

function updateBadge() {
  if (!mining) {
    chrome.action.setBadgeText({ text: '' });
    return;
  }
  const hrText = hashrate >= 1000 ? (hashrate / 1000).toFixed(1) + 'k' : Math.round(hashrate).toString();
  chrome.action.setBadgeText({ text: hrText });
  chrome.action.setBadgeBackgroundColor({ color: '#10B981' });
}

async function submitWork(work) {
  try {
    const res = await fetch(`${API_BASE}?action=submit_work`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({
        hashes: work.hashes,
        nonce: work.nonce,
        hash: work.hash,
        difficulty: work.difficulty,
        elapsed_ms: work.elapsed_ms
      })
    });
    const data = await res.json();
    if (data.success) {
      earnings = data.total_earned || earnings;
      blocksFound++;
    }
    return data;
  } catch (err) {
    console.error('[Alfred Miner] Submit work error:', err);
    return { success: false, error: err.message };
  }
}

// Auto-resume mining on browser start if it was active
chrome.storage.local.get(['miningEnabled', 'throttle'], (data) => {
  if (data.throttle) throttle = data.throttle;
  if (data.miningEnabled) startMining();
});

// Save state on suspend
chrome.runtime.onSuspend.addListener(() => {
  chrome.storage.local.set({ miningEnabled: mining, throttle });
});

// Alarm for periodic stats reporting
chrome.alarms.create('mining-stats', { periodInMinutes: 5 });
chrome.alarms.onAlarm.addListener((alarm) => {
  if (alarm.name === 'mining-stats' && mining) {
    // Periodic stats check
    fetch(`${API_BASE}?action=wallet`, { credentials: 'include' })
      .then(r => r.json())
      .then(data => {
        if (data.success) earnings = data.balance || earnings;
      })
      .catch(() => {});
  }
});
