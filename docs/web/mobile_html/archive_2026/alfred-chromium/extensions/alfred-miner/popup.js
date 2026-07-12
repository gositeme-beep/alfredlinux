/**
 * Alfred Miner — Popup UI Controller
 */

const toggle = document.getElementById('miningToggle');
const badge = document.getElementById('statusBadge');
const throttleSlider = document.getElementById('throttleSlider');
const throttleValue = document.getElementById('throttleValue');
const hashrateEl = document.getElementById('hashrate');
const blocksEl = document.getElementById('blocks');
const hashesEl = document.getElementById('hashes');
const earningsEl = document.getElementById('totalEarnings');

// Get current status on popup open
chrome.runtime.sendMessage({ action: 'getStatus' }, (status) => {
  if (!status) return;
  toggle.checked = status.mining;
  updateBadge(status.mining);
  throttleSlider.value = Math.round(status.throttle * 100);
  throttleValue.textContent = Math.round(status.throttle * 100) + '%';
  updateStats(status);
});

// Toggle mining
toggle.addEventListener('change', () => {
  chrome.runtime.sendMessage({ action: toggle.checked ? 'start' : 'stop' });
  updateBadge(toggle.checked);
  chrome.storage.local.set({ miningEnabled: toggle.checked });
});

// Throttle slider
throttleSlider.addEventListener('input', () => {
  const pct = parseInt(throttleSlider.value);
  throttleValue.textContent = pct + '%';
  chrome.runtime.sendMessage({ action: 'setThrottle', value: pct / 100 });
  chrome.storage.local.set({ throttle: pct / 100 });
});

// Listen for live stats
chrome.runtime.onMessage.addListener((msg) => {
  if (msg.action === 'miningStats') {
    updateStats(msg);
  }
});

function updateBadge(active) {
  badge.textContent = active ? 'MINING' : 'OFF';
  badge.className = 'badge' + (active ? '' : ' off');
}

function updateStats(data) {
  if (data.hashrate !== undefined) {
    hashrateEl.textContent = data.hashrate >= 1000
      ? (data.hashrate / 1000).toFixed(1) + ' kH/s'
      : data.hashrate + ' H/s';
  }
  if (data.blocksFound !== undefined) blocksEl.textContent = data.blocksFound;
  if (data.totalHashes !== undefined) {
    hashesEl.textContent = data.totalHashes >= 1000000
      ? (data.totalHashes / 1000000).toFixed(1) + 'M'
      : data.totalHashes >= 1000
        ? (data.totalHashes / 1000).toFixed(0) + 'k'
        : data.totalHashes;
  }
  if (data.earnings !== undefined) earningsEl.textContent = data.earnings.toFixed(3);
}

// Refresh stats every 2 seconds while popup is open
setInterval(() => {
  chrome.runtime.sendMessage({ action: 'getStatus' }, (status) => {
    if (status) updateStats(status);
  });
}, 2000);
