<?php
require_once __DIR__ . '/includes/auth-gate.inc.php';

$pageTitle       = "Credits — GoCodeMe";
$pageDescription = "Manage your GoCodeMe AI credit balance, view spending history, and configure auto-replenish.";
include __DIR__ . '/includes/site-header.inc.php';
?>

<style>
  .credits-page {
    max-width: 900px;
    margin: 40px auto;
    padding: 0 20px;
    font-family: var(--alfred-font, system-ui, -apple-system, sans-serif);
    color: var(--alfred-text, #e0e0e0);
  }
  .credits-hero {
    text-align: center;
    margin-bottom: 40px;
  }
  .credits-hero h1 {
    font-size: 2rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 8px;
  }
  .credits-hero p {
    color: var(--alfred-text-secondary, #999);
    font-size: 1rem;
  }

  .credits-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 32px;
  }
  @media (max-width: 640px) {
    .credits-grid { grid-template-columns: 1fr; }
  }

  .credits-card {
    background: var(--alfred-card-bg, #1a1a2e);
    border: 1px solid var(--alfred-border, #2a2a4a);
    border-radius: 12px;
    padding: 24px;
  }
  .credits-card h2 {
    font-size: 1rem;
    font-weight: 600;
    color: var(--alfred-text-secondary, #999);
    margin: 0 0 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 0.75rem;
  }

  .balance-amount {
    font-size: 2.5rem;
    font-weight: 800;
    color: #81c784;
    font-family: monospace;
    line-height: 1.2;
  }
  .balance-amount.low { color: #ef5350; }
  .balance-amount.zero { color: #666; }
  .balance-status {
    font-size: 0.85rem;
    color: var(--alfred-text-secondary, #999);
    margin-top: 8px;
  }

  .credits-packs {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 32px;
  }
  @media (max-width: 640px) {
    .credits-packs { grid-template-columns: 1fr; }
  }
  .credit-pack {
    background: var(--alfred-card-bg, #1a1a2e);
    border: 1px solid var(--alfred-border, #2a2a4a);
    border-radius: 12px;
    padding: 24px;
    text-align: center;
    cursor: pointer;
    transition: border-color 0.2s, transform 0.1s;
    position: relative;
  }
  .credit-pack:hover {
    border-color: var(--alfred-primary, #6366f1);
    transform: translateY(-2px);
  }
  .credit-pack.popular {
    border-color: var(--alfred-primary, #6366f1);
  }
  .credit-pack.popular::after {
    content: 'BEST VALUE';
    position: absolute;
    top: -10px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--alfred-primary, #6366f1);
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 10px;
    border-radius: 10px;
    letter-spacing: 1px;
  }
  .credit-pack .pack-amount {
    font-size: 2rem;
    font-weight: 800;
    color: #fff;
    margin-bottom: 4px;
  }
  .credit-pack .pack-label {
    font-size: 0.85rem;
    color: var(--alfred-text-secondary, #999);
    margin-bottom: 16px;
  }
  .credit-pack .pack-estimate {
    font-size: 0.75rem;
    color: var(--alfred-text-secondary, #777);
    margin-bottom: 16px;
  }
  .credit-pack .pack-btn {
    display: inline-block;
    padding: 8px 24px;
    background: var(--alfred-primary, #6366f1);
    color: #fff;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.85rem;
    font-weight: 600;
    transition: opacity 0.2s;
  }
  .credit-pack .pack-btn:hover { opacity: 0.85; }

  .replenish-card {
    grid-column: 1 / -1;
  }
  .replenish-form {
    display: flex;
    gap: 16px;
    align-items: end;
    flex-wrap: wrap;
  }
  .replenish-field {
    display: flex;
    flex-direction: column;
    gap: 4px;
  }
  .replenish-field label {
    font-size: 0.75rem;
    color: var(--alfred-text-secondary, #999);
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  .replenish-field input, .replenish-field select {
    padding: 8px 12px;
    background: var(--alfred-input-bg, #0f0f23);
    border: 1px solid var(--alfred-border, #2a2a4a);
    border-radius: 8px;
    color: #fff;
    font-size: 0.9rem;
    width: 120px;
  }
  .replenish-btn {
    padding: 8px 20px;
    background: var(--alfred-primary, #6366f1);
    color: #fff;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.85rem;
    font-weight: 600;
  }
  .toast {
    position: fixed;
    bottom: 24px;
    right: 24px;
    padding: 12px 24px;
    border-radius: 8px;
    color: #fff;
    font-size: 0.9rem;
    z-index: 10000;
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.3s;
  }
  .toast.show {
    opacity: 1;
    transform: translateY(0);
  }
  .toast.success { background: #2e7d32; }
  .toast.error { background: #c62828; }

  .estimate-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 12px;
    font-size: 0.85rem;
  }
  .estimate-table th {
    text-align: left;
    padding: 6px 8px;
    color: var(--alfred-text-secondary, #999);
    font-weight: 600;
    border-bottom: 1px solid var(--alfred-border, #2a2a4a);
  }
  .estimate-table td {
    padding: 6px 8px;
    color: var(--alfred-text, #e0e0e0);
    border-bottom: 1px solid rgba(255,255,255,0.04);
  }
</style>

<div class="credits-page">
  <div class="credits-hero">
    <h1>💳 GoCodeMe Credits</h1>
    <p>Prepaid credits for AI usage — pay for what you use, top up anytime.</p>
  </div>

  <div class="credits-grid">
    <!-- Balance Card -->
    <div class="credits-card">
      <h2>Current Balance</h2>
      <div class="balance-amount" id="cr-balance">$—</div>
      <div class="balance-status" id="cr-status">Loading…</div>
    </div>

    <!-- Estimates Card -->
    <div class="credits-card">
      <h2>What Credits Buy You</h2>
      <table class="estimate-table">
        <thead><tr><th>Model</th><th>≈ Messages</th></tr></thead>
        <tbody id="cr-estimates">
          <tr><td>Haiku 4.5</td><td id="est-haiku">—</td></tr>
          <tr><td>Sonnet 4.6</td><td id="est-sonnet">—</td></tr>
          <tr><td>Opus 4.6</td><td id="est-opus">—</td></tr>
          <tr><td>GPT-4.1 Mini</td><td id="est-mini">—</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Credit Packs -->
  <h2 style="color:#fff;margin-bottom:16px;">Top Up Credits</h2>
  <div class="credits-packs">
    <div class="credit-pack" data-amount="10">
      <div class="pack-amount">$10</div>
      <div class="pack-label">Starter Pack</div>
      <div class="pack-estimate">~280 Sonnet messages</div>
      <button class="pack-btn" onclick="requestTopUp(10)">Add $10</button>
    </div>
    <div class="credit-pack popular" data-amount="50">
      <div class="pack-amount">$50</div>
      <div class="pack-label">Power Pack</div>
      <div class="pack-estimate">~1,400 Sonnet messages</div>
      <button class="pack-btn" onclick="requestTopUp(50)">Add $50</button>
    </div>
    <div class="credit-pack" data-amount="100">
      <div class="pack-amount">$100</div>
      <div class="pack-label">Pro Pack</div>
      <div class="pack-estimate">~2,800 Sonnet messages</div>
      <button class="pack-btn" onclick="requestTopUp(100)">Add $100</button>
    </div>
  </div>

  <!-- Auto-Replenish Settings -->
  <div class="credits-grid">
    <div class="credits-card replenish-card">
      <h2>⚡ Auto-Replenish</h2>
      <p style="color:#999;font-size:0.85rem;margin-bottom:16px;">Automatically add credits when your balance drops below a threshold.</p>
      <div class="replenish-form">
        <div class="replenish-field">
          <label>When balance drops below</label>
          <input type="number" id="ar-threshold" min="0" max="1000" step="1" value="5" placeholder="$5">
        </div>
        <div class="replenish-field">
          <label>Add this amount</label>
          <input type="number" id="ar-amount" min="1" max="10000" step="1" value="25" placeholder="$25">
        </div>
        <div class="replenish-field">
          <label>Status</label>
          <select id="ar-enabled">
            <option value="0">Disabled</option>
            <option value="1">Enabled</option>
          </select>
        </div>
        <button class="replenish-btn" onclick="saveAutoReplenish()">Save Settings</button>
      </div>
    </div>
  </div>
</div>

<div class="toast" id="toast-msg"></div>

<script>
(function() {
  'use strict';

  // Cost per average message (~2000 input, ~800 output tokens)
  const MSG_COSTS = {
    haiku:  (2000 * 0.80 + 800 * 4.0) / 1e6,   // ~$0.0048
    sonnet: (2000 * 3.0 + 800 * 15.0) / 1e6,    // ~$0.018
    opus:   (2000 * 15.0 + 800 * 75.0) / 1e6,   // ~$0.09
    mini:   (2000 * 0.40 + 800 * 1.60) / 1e6,   // ~$0.00208
  };

  let balance = 0;

  function getJwt() {
    try { return localStorage.getItem('gcm_jwt') || ''; } catch { return ''; }
  }
  function headers() {
    const h = { 'Content-Type': 'application/json' };
    const jwt = getJwt();
    if (jwt) h['Authorization'] = 'Bearer ' + jwt;
    return h;
  }

  async function loadBalance() {
    try {
      const r = await fetch('/middleware/api/usage/credits', { headers: headers(), credentials: 'include' });
      const d = await r.json();
      if (d.ok) {
        balance = d.balance;
        const el = document.getElementById('cr-balance');
        el.textContent = '$' + balance.toFixed(2);
        el.className = 'balance-amount' + (balance <= 0 ? ' zero' : balance < 5 ? ' low' : '');
        document.getElementById('cr-status').textContent = balance <= 0 ? 'No credits remaining' : 'Credits available';
        updateEstimates();
      }
    } catch (e) {
      document.getElementById('cr-status').textContent = 'Failed to load balance';
    }
  }

  function updateEstimates() {
    document.getElementById('est-haiku').textContent  = '~' + Math.floor(balance / MSG_COSTS.haiku).toLocaleString();
    document.getElementById('est-sonnet').textContent = '~' + Math.floor(balance / MSG_COSTS.sonnet).toLocaleString();
    document.getElementById('est-opus').textContent   = '~' + Math.floor(balance / MSG_COSTS.opus).toLocaleString();
    document.getElementById('est-mini').textContent   = '~' + Math.floor(balance / MSG_COSTS.mini).toLocaleString();
  }

  async function loadAutoReplenish() {
    try {
      const r = await fetch('/middleware/api/usage/autoreplenish', { headers: headers(), credentials: 'include' });
      const d = await r.json();
      if (d.ok) {
        document.getElementById('ar-threshold').value = d.threshold || 5;
        document.getElementById('ar-amount').value    = d.amount || 25;
        document.getElementById('ar-enabled').value   = d.enabled ? '1' : '0';
      }
    } catch { /* defaults remain */ }
  }

  window.saveAutoReplenish = async function() {
    const threshold = parseFloat(document.getElementById('ar-threshold').value) || 5;
    const amount    = parseFloat(document.getElementById('ar-amount').value) || 25;
    const enabled   = document.getElementById('ar-enabled').value === '1';
    try {
      const r = await fetch('/middleware/api/usage/autoreplenish', {
        method: 'POST',
        headers: headers(),
        credentials: 'include',
        body: JSON.stringify({ threshold, amount, enabled })
      });
      const d = await r.json();
      if (d.ok) {
        showToast('Auto-replenish settings saved!', 'success');
      } else {
        showToast(d.error || 'Failed to save', 'error');
      }
    } catch {
      showToast('Network error', 'error');
    }
  };

  window.requestTopUp = async function(amount) {
    // For now, show a confirmation — Stripe integration will replace this
    showToast('Credit top-up request for $' + amount + ' submitted. Contact support to complete purchase.', 'success');
  };

  function showToast(msg, type) {
    const el = document.getElementById('toast-msg');
    el.textContent = msg;
    el.className = 'toast ' + type + ' show';
    setTimeout(() => { el.className = 'toast'; }, 4000);
  }

  // Init
  loadBalance();
  loadAutoReplenish();
  setInterval(loadBalance, 60000); // refresh every 60s
})();
</script>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
