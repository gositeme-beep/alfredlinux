<?php
$pageTitle = "Handshake Domains — Domain Sovereignty";
$pageDescription = "Secure .root, .qgsm, and .gsm top-level domains on the Handshake decentralized DNS.";
$adminOnly = true;
include 'includes/site-header.inc.php';

// Supreme Admin check
if (($clientId ?? 0) !== 33) {
    header('Location: /dashboard');
    exit;
}
?>

<style>
.hns-page { max-width: 1200px; margin: 0 auto; padding: 30px 20px; }
.hns-hero { text-align: center; margin-bottom: 40px; }
.hns-title { font-size: 36px; font-weight: 800; margin-bottom: 8px; background: linear-gradient(135deg, #6366f1, #a855f7, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.hns-sub { color: #94a3b8; font-size: 16px; max-width: 600px; margin: 0 auto; }

/* Network banner */
.hns-network { display: flex; gap: 24px; padding: 16px 24px; background: rgba(99,102,241,0.06); border: 1px solid rgba(99,102,241,0.15); border-radius: 14px; margin-bottom: 30px; flex-wrap: wrap; align-items: center; justify-content: center; }
.hns-net-item { display: flex; align-items: center; gap: 8px; font-size: 14px; color: #cbd5e1; }
.hns-net-dot { width: 10px; height: 10px; border-radius: 50%; background: #22c55e; box-shadow: 0 0 8px rgba(34,197,94,0.5); }

/* Domain cards */
.hns-domains { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 40px; }
.hns-card { padding: 28px; background: rgba(15,23,42,0.6); border: 1px solid rgba(99,102,241,0.15); border-radius: 20px; position: relative; overflow: hidden; transition: all 0.3s; }
.hns-card:hover { border-color: rgba(99,102,241,0.4); transform: translateY(-2px); box-shadow: 0 8px 30px rgba(0,0,0,0.3); }
.hns-card.available { border-color: rgba(34,197,94,0.3); }
.hns-card.available::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, #22c55e, #10b981); }
.hns-card.taken { border-color: rgba(239,68,68,0.2); }
.hns-card.taken::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, #ef4444, #f97316); }
.hns-card.loading .hns-card-name span { animation: pulse-text 1.5s infinite; }
@keyframes pulse-text { 0%,100% { opacity: 1; } 50% { opacity: 0.4; } }

.hns-card-name { font-size: 32px; font-weight: 800; color: #e0e0ff; margin-bottom: 6px; }
.hns-card-name span { color: #6366f1; }
.hns-card-badge { display: inline-flex; align-items: center; gap: 6px; padding: 5px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 20px; }
.badge-available { background: rgba(34,197,94,0.15); color: #22c55e; }
.badge-taken { background: rgba(239,68,68,0.15); color: #ef4444; }
.badge-bidding { background: rgba(59,130,246,0.15); color: #3b82f6; }
.badge-reveal { background: rgba(168,85,247,0.15); color: #a855f7; }
.badge-opening { background: rgba(245,158,11,0.15); color: #f59e0b; }
.badge-unknown { background: rgba(100,116,139,0.15); color: #94a3b8; }

.hns-card-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px; }
.hns-card-stat { }
.hns-card-stat-label { font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
.hns-card-stat-value { font-size: 15px; font-weight: 600; color: #cbd5e1; }

.hns-card-actions { display: flex; gap: 10px; flex-wrap: wrap; }
.hns-btn { padding: 10px 18px; border-radius: 10px; font-size: 13px; font-weight: 700; cursor: pointer; border: none; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; }
.hns-btn-claim { background: linear-gradient(135deg, #22c55e, #10b981); color: white; }
.hns-btn-claim:hover { transform: translateY(-1px); box-shadow: 0 4px 16px rgba(34,197,94,0.4); }
.hns-btn-primary { background: linear-gradient(135deg, #6366f1, #a855f7); color: white; }
.hns-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 16px rgba(99,102,241,0.4); }
.hns-btn-secondary { background: rgba(99,102,241,0.1); color: #a5b4fc; border: 1px solid rgba(99,102,241,0.2); }
.hns-btn-secondary:hover { background: rgba(99,102,241,0.2); }
.hns-btn-danger { background: rgba(239,68,68,0.1); color: #fca5a5; border: 1px solid rgba(239,68,68,0.2); }

/* How to claim section */
.hns-guide { margin-bottom: 40px; }
.hns-guide-title { font-size: 22px; font-weight: 700; color: #e0e0ff; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
.hns-options { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.hns-option { padding: 24px; background: rgba(15,23,42,0.6); border: 1px solid rgba(99,102,241,0.12); border-radius: 16px; transition: border-color 0.3s; }
.hns-option:hover { border-color: rgba(99,102,241,0.3); }
.hns-option-badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; }
.hns-option-badge.recommended { background: rgba(34,197,94,0.15); color: #22c55e; }
.hns-option-badge.alternative { background: rgba(99,102,241,0.15); color: #a5b4fc; }
.hns-option h3 { font-size: 18px; font-weight: 700; color: #e0e0ff; margin-bottom: 8px; }
.hns-option p { color: #94a3b8; font-size: 14px; line-height: 1.6; margin-bottom: 16px; }
.hns-steps { padding-left: 0; list-style: none; counter-reset: step; }
.hns-steps li { position: relative; padding: 8px 0 8px 36px; font-size: 14px; color: #cbd5e1; line-height: 1.5; counter-increment: step; }
.hns-steps li::before { content: counter(step); position: absolute; left: 0; top: 8px; width: 24px; height: 24px; border-radius: 50%; background: rgba(99,102,241,0.2); color: #a5b4fc; font-size: 12px; font-weight: 700; display: flex; align-items: center; justify-content: center; }
.hns-steps a { color: #a855f7; text-decoration: none; font-weight: 600; }
.hns-steps a:hover { color: #c084fc; text-decoration: underline; }
.hns-option-links { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 16px; }

/* Lookup */
.hns-section-title { font-size: 18px; font-weight: 700; color: #e0e0ff; margin: 30px 0 16px; display: flex; align-items: center; gap: 10px; padding-bottom: 10px; border-bottom: 1px solid rgba(99,102,241,0.1); }
.hns-lookup { display: flex; gap: 12px; margin-bottom: 20px; }
.hns-lookup input { flex: 1; padding: 12px 16px; background: rgba(15,23,42,0.6); border: 1px solid rgba(99,102,241,0.2); border-radius: 12px; color: #e0e0ff; font-size: 15px; }
.hns-lookup input::placeholder { color: #475569; }
.hns-lookup input:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }

/* Cost estimate */
.hns-cost { background: rgba(34,197,94,0.06); border: 1px solid rgba(34,197,94,0.15); border-radius: 16px; padding: 20px 24px; margin-bottom: 30px; }
.hns-cost-title { font-size: 16px; font-weight: 700; color: #22c55e; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
.hns-cost-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
.hns-cost-item { text-align: center; }
.hns-cost-name { font-size: 13px; color: #94a3b8; margin-bottom: 4px; }
.hns-cost-value { font-size: 20px; font-weight: 700; color: #e0e0ff; }
.hns-cost-note { font-size: 11px; color: #64748b; margin-top: 2px; }

/* Log */
.hns-log { background: rgba(15,23,42,0.8); border: 1px solid rgba(99,102,241,0.08); border-radius: 12px; padding: 16px; font-family: 'JetBrains Mono', monospace; font-size: 12px; color: #94a3b8; max-height: 180px; overflow-y: auto; margin-top: 20px; }
.hns-log .success { color: #22c55e; }
.hns-log .error { color: #ef4444; }
.hns-log .info { color: #6366f1; }

@media (max-width: 900px) {
    .hns-domains { grid-template-columns: 1fr; }
    .hns-options { grid-template-columns: 1fr; }
    .hns-cost-grid { grid-template-columns: 1fr; gap: 12px; }
    .hns-lookup { flex-direction: column; }
    .hns-network { flex-direction: column; gap: 10px; }
}
@media (max-width: 600px) {
    .hns-title { font-size: 26px; }
    .hns-card-name { font-size: 24px; }
}
</style>

<div class="hns-page">
    <!-- Hero -->
    <div class="hns-hero">
        <h1 class="hns-title"><i class="fas fa-shield-alt"></i> Handshake Domain Sovereignty</h1>
        <p class="hns-sub">Own your namespace on the decentralized internet. No ICANN. No middlemen. True sovereignty.</p>
    </div>

    <!-- Network Status -->
    <div class="hns-network" id="networkBar">
        <div class="hns-net-item">
            <div class="hns-net-dot"></div>
            <span>Handshake Network <strong>Live</strong></span>
        </div>
        <div class="hns-net-item">
            <i class="fas fa-cubes" style="color:#6366f1"></i>
            <span>Block <strong id="netHeight">—</strong></span>
        </div>
        <div class="hns-net-item">
            <i class="fas fa-globe" style="color:#a855f7"></i>
            <span><strong id="netNames">—</strong> names registered</span>
        </div>
        <div class="hns-net-item">
            <i class="fas fa-server" style="color:#22c55e"></i>
            <span><strong id="netPeers">—</strong> peers</span>
        </div>
    </div>

    <!-- Target Domains -->
    <div class="hns-domains" id="targetDomains">
        <div class="hns-card loading" id="card-root">
            <div class="hns-card-name">.<span>root</span></div>
            <div class="hns-card-badge badge-unknown"><i class="fas fa-spinner fa-spin"></i> Checking...</div>
            <div class="hns-card-grid"></div>
            <div class="hns-card-actions"></div>
        </div>
        <div class="hns-card loading" id="card-qgsm">
            <div class="hns-card-name">.<span>qgsm</span></div>
            <div class="hns-card-badge badge-unknown"><i class="fas fa-spinner fa-spin"></i> Checking...</div>
            <div class="hns-card-grid"></div>
            <div class="hns-card-actions"></div>
        </div>
        <div class="hns-card loading" id="card-gsm">
            <div class="hns-card-name">.<span>gsm</span></div>
            <div class="hns-card-badge badge-unknown"><i class="fas fa-spinner fa-spin"></i> Checking...</div>
            <div class="hns-card-grid"></div>
            <div class="hns-card-actions"></div>
        </div>
    </div>

    <!-- Cost Estimate -->
    <div class="hns-cost" id="costEstimate" style="display:none">
        <div class="hns-cost-title"><i class="fas fa-calculator"></i> Estimated Cost to Claim</div>
        <div class="hns-cost-grid">
            <div class="hns-cost-item">
                <div class="hns-cost-name">.root</div>
                <div class="hns-cost-value" id="cost-root">—</div>
                <div class="hns-cost-note">never auctioned — minimum bid</div>
            </div>
            <div class="hns-cost-item">
                <div class="hns-cost-name">.qgsm</div>
                <div class="hns-cost-value" id="cost-qgsm">—</div>
                <div class="hns-cost-note">expired — minimum bid</div>
            </div>
            <div class="hns-cost-item">
                <div class="hns-cost-name">.gsm</div>
                <div class="hns-cost-value" id="cost-gsm">—</div>
                <div class="hns-cost-note">currently owned</div>
            </div>
        </div>
    </div>

    <!-- How to Claim -->
    <div class="hns-guide">
        <div class="hns-guide-title"><i class="fas fa-rocket" style="color:#a855f7"></i> How to Claim Your Domains</div>
        <div class="hns-options">
            <!-- Option 1: Bob Wallet -->
            <div class="hns-option">
                <div class="hns-option-badge recommended">Recommended</div>
                <h3><i class="fas fa-wallet" style="color:#f59e0b"></i> Option A: Bob Wallet</h3>
                <p>Bob Wallet is a free, open-source Handshake wallet with a built-in auction interface. It runs its own SPV node directly in your browser — no server needed.</p>
                <ul class="hns-steps">
                    <li>Install <a href="https://bobwallet.io/" target="_blank">Bob Wallet</a> (Windows / Mac / Linux desktop app)</li>
                    <li>Create a new wallet and let it sync (may take a few hours on first run)</li>
                    <li>Buy HNS on <a href="https://www.gate.io/trade/HNS_USDT" target="_blank">Gate.io</a> or <a href="https://www.coinex.com/en/exchange/hns-usdt" target="_blank">CoinEx</a> with credit card</li>
                    <li>Send HNS from the exchange to your Bob Wallet receive address</li>
                    <li>Search for <strong>root</strong> and <strong>qgsm</strong> in Bob Wallet</li>
                    <li>Click "Open Auction" → bid → reveal → register</li>
                </ul>
                <div class="hns-option-links">
                    <a href="https://bobwallet.io/" target="_blank" class="hns-btn hns-btn-claim"><i class="fas fa-download"></i> Get Bob Wallet</a>
                    <a href="https://www.gate.io/trade/HNS_USDT" target="_blank" class="hns-btn hns-btn-secondary"><i class="fas fa-coins"></i> Buy HNS</a>
                </div>
            </div>

            <!-- Option 2: Exchange + Remote -->
            <div class="hns-option">
                <div class="hns-option-badge alternative">Alternative</div>
                <h3><i class="fas fa-handshake" style="color:#6366f1"></i> Option B: EnCirca TLD Service</h3>
                <p>EnCirca is an ICANN-accredited registrar that offers a <strong>TLD acquisition service</strong> for Handshake names. They handle the on-chain auction process for you and accept credit card payments.</p>
                <ul class="hns-steps">
                    <li>Go to <a href="https://www.encirca.com/handshake-overview/" target="_blank">EnCirca Handshake Overview</a></li>
                    <li>Fill out their <strong>TLD Inquiry Form</strong> requesting .root and .qgsm</li>
                    <li>They will bid on the names using their infrastructure</li>
                    <li>Pay with credit card — no crypto wallets needed</li>
                    <li>They transfer the TLD ownership to you after winning</li>
                </ul>
                <div class="hns-option-links">
                    <a href="https://www.encirca.com/handshake-overview/" target="_blank" class="hns-btn hns-btn-primary"><i class="fas fa-external-link-alt"></i> Contact EnCirca</a>
                </div>
            </div>
        </div>
    </div>

    <!-- How the Auction Works -->
    <div class="hns-option" style="margin-bottom: 40px">
        <h3><i class="fas fa-gavel" style="color:#f59e0b"></i> How Handshake Auctions Work</h3>
        <p style="color:#94a3b8;font-size:14px;line-height:1.7;margin:0">
            Handshake uses <strong style="color:#e0e0ff">Vickrey auctions</strong> (sealed-bid, second-price).
            The process takes ~<strong style="color:#e0e0ff">15 days</strong> total:
            <strong style="color:#f59e0b">Open</strong> (sends tx to start auction) →
            <strong style="color:#3b82f6">Bidding</strong> (~5 days, all bids hidden) →
            <strong style="color:#a855f7">Reveal</strong> (~10 days, bids revealed) →
            <strong style="color:#22c55e">Register</strong> (winner sets DNS records).
            You pay the <em>second-highest</em> bid price. If nobody else bids, you get it for the minimum (~0.01 HNS).
            For names that have never been auctioned (.root) or expired (.qgsm), you'll likely be the only bidder.
        </p>
    </div>

    <!-- Lookup any domain -->
    <div class="hns-section-title"><i class="fas fa-search" style="color:#6366f1"></i> Lookup Any HNS Name</div>
    <div class="hns-lookup">
        <input type="text" id="lookupInput" placeholder="Enter a Handshake name (e.g. bitcoin, forever)" maxlength="63" autocomplete="off">
        <button class="hns-btn hns-btn-primary" onclick="lookupName()"><i class="fas fa-search"></i> Lookup</button>
    </div>
    <div id="lookupResult"></div>

    <!-- Activity Log -->
    <div class="hns-section-title"><i class="fas fa-terminal" style="color:#64748b"></i> Activity Log</div>
    <div class="hns-log" id="activityLog">
        <div class="info">[HNS] Querying Handshake explorer...</div>
    </div>
</div>

<script>
const API = '/api/handshake.php';
const EXPLORER = 'https://e.hnsfans.com';
const logEl = document.getElementById('activityLog');

function log(msg, type = 'info') {
    const ts = new Date().toLocaleTimeString();
    logEl.innerHTML += `<div class="${type}">[${ts}] ${msg}</div>`;
    logEl.scrollTop = logEl.scrollHeight;
}

async function api(action, params = {}) {
    const isGet = ['node-info','wallet-info','balance','name-status','lookup-all','names','receive-address','network-info'].includes(action);
    let url = `${API}?action=${action}`;
    let opts = { credentials: 'same-origin' };
    if (isGet) {
        Object.entries(params).forEach(([k,v]) => url += `&${k}=${encodeURIComponent(v)}`);
    } else {
        const form = new FormData();
        Object.entries(params).forEach(([k,v]) => form.append(k, v));
        opts.method = 'POST';
        opts.body = form;
    }
    try {
        const res = await fetch(url, opts);
        return res.json();
    } catch (e) {
        return { error: e.message };
    }
}

function badgeClass(state) {
    const map = { INACTIVE: 'badge-available', OPENING: 'badge-opening', BIDDING: 'badge-bidding', REVEAL: 'badge-reveal', CLOSED: 'badge-taken' };
    return map[(state||'').toUpperCase()] || 'badge-unknown';
}

function badgeLabel(state) {
    const map = { INACTIVE: 'Available', OPENING: 'Auction Opening', BIDDING: 'Bidding Active', REVEAL: 'Reveal Phase', CLOSED: 'Owned by Someone' };
    return map[(state||'').toUpperCase()] || state || 'Unknown';
}

function badgeIcon(state) {
    const map = { INACTIVE: 'fa-check-circle', OPENING: 'fa-clock', BIDDING: 'fa-gavel', REVEAL: 'fa-eye', CLOSED: 'fa-lock' };
    return map[(state||'').toUpperCase()] || 'fa-question-circle';
}

function formatHNS(val) {
    if (!val && val !== 0) return '0';
    return (val / 1e6).toFixed(2);
}

function renderCard(name, data) {
    const card = document.getElementById(`card-${name}`);
    if (!card) return;
    card.classList.remove('loading');

    const state = (data.state || 'UNKNOWN').toUpperCase();
    const badge = card.querySelector('.hns-card-badge');
    const grid = card.querySelector('.hns-card-grid');
    const actions = card.querySelector('.hns-card-actions');

    badge.className = `hns-card-badge ${badgeClass(state)}`;
    badge.innerHTML = `<i class="fas ${badgeIcon(state)}"></i> ${badgeLabel(state)}`;

    card.classList.remove('available', 'taken');
    if (state === 'INACTIVE') card.classList.add('available');
    else if (state === 'CLOSED') card.classList.add('taken');

    const valueHNS = formatHNS(data.value);
    const highestHNS = formatHNS(data.highest);
    const renewalBlock = data.renewal || data.stats?.renewalPeriodEnd || '—';
    const owner = data.owner?.hash ? data.owner.hash.substring(0, 12) + '...' : '—';

    grid.innerHTML = `
        <div class="hns-card-stat">
            <div class="hns-card-stat-label">Value</div>
            <div class="hns-card-stat-value">${valueHNS} HNS</div>
        </div>
        <div class="hns-card-stat">
            <div class="hns-card-stat-label">Highest Bid</div>
            <div class="hns-card-stat-value">${highestHNS} HNS</div>
        </div>
        ${state === 'CLOSED' ? `
        <div class="hns-card-stat">
            <div class="hns-card-stat-label">Owner</div>
            <div class="hns-card-stat-value" style="font-size:12px">${owner}</div>
        </div>
        <div class="hns-card-stat">
            <div class="hns-card-stat-label">Renewal</div>
            <div class="hns-card-stat-value">Block ${renewalBlock}</div>
        </div>
        ` : `
        <div class="hns-card-stat">
            <div class="hns-card-stat-label">Bids</div>
            <div class="hns-card-stat-value">${(data.bids || []).length}</div>
        </div>
        <div class="hns-card-stat">
            <div class="hns-card-stat-label">Status</div>
            <div class="hns-card-stat-value">${state === 'INACTIVE' ? 'Ready to claim' : state}</div>
        </div>
        `}
    `;

    actions.innerHTML = '';
    if (state === 'INACTIVE') {
        actions.innerHTML = `
            <a href="https://bobwallet.io/" target="_blank" class="hns-btn hns-btn-claim"><i class="fas fa-gavel"></i> Claim with Bob Wallet</a>
            <a href="${EXPLORER}/name/${name}" target="_blank" class="hns-btn hns-btn-secondary"><i class="fas fa-external-link-alt"></i> Explorer</a>
        `;
    } else if (state === 'CLOSED') {
        const reopenNote = data.stats?.blocksUntilExpire ? `Re-opens in ~${data.stats.blocksUntilExpire.toLocaleString()} blocks` : '';
        actions.innerHTML = `
            ${reopenNote ? `<span style="color:#f59e0b;font-size:13px"><i class="fas fa-clock"></i> ${reopenNote}</span>` : ''}
            <a href="${EXPLORER}/name/${name}" target="_blank" class="hns-btn hns-btn-secondary"><i class="fas fa-external-link-alt"></i> Explorer</a>
        `;
    } else {
        actions.innerHTML = `
            <a href="${EXPLORER}/name/${name}" target="_blank" class="hns-btn hns-btn-secondary"><i class="fas fa-external-link-alt"></i> Explorer</a>
        `;
    }

    // Update cost estimate
    const costEl = document.getElementById(`cost-${name}`);
    if (costEl) {
        if (state === 'INACTIVE') {
            costEl.textContent = '~$0.01';
            costEl.style.color = '#22c55e';
        } else if (state === 'CLOSED') {
            costEl.textContent = 'Not Available';
            costEl.style.color = '#ef4444';
        } else {
            costEl.textContent = 'In Auction';
            costEl.style.color = '#f59e0b';
        }
    }
}

async function lookupName() {
    const input = document.getElementById('lookupInput');
    const name = input.value.trim().toLowerCase().replace(/[^a-z0-9\-_]/g, '');
    if (!name) return;
    input.value = name;

    log(`Looking up .${name}...`, 'info');
    const data = await api('name-status', { name });
    const resultEl = document.getElementById('lookupResult');

    if (data.error) {
        resultEl.innerHTML = `<div style="color:#ef4444;padding:16px;background:rgba(239,68,68,0.06);border-radius:12px;margin-top:12px"><i class="fas fa-times-circle"></i> ${data.error}</div>`;
        log(`Lookup failed: ${data.error}`, 'error');
        return;
    }

    const state = (data.state || data.info?.state || 'UNKNOWN').toUpperCase();
    const valueHNS = formatHNS(data.value || data.info?.value || 0);

    resultEl.innerHTML = `
        <div class="hns-card ${state === 'INACTIVE' ? 'available' : state === 'CLOSED' ? 'taken' : ''}" style="margin-top:12px">
            <div class="hns-card-name">.<span>${name}</span></div>
            <div class="hns-card-badge ${badgeClass(state)}"><i class="fas ${badgeIcon(state)}"></i> ${badgeLabel(state)}</div>
            <div class="hns-card-grid">
                <div class="hns-card-stat"><div class="hns-card-stat-label">Value</div><div class="hns-card-stat-value">${valueHNS} HNS</div></div>
                <div class="hns-card-stat"><div class="hns-card-stat-label">Source</div><div class="hns-card-stat-value">${data._source || 'explorer'}</div></div>
            </div>
            <div class="hns-card-actions">
                ${state === 'INACTIVE' ? `<a href="https://bobwallet.io/" target="_blank" class="hns-btn hns-btn-claim"><i class="fas fa-gavel"></i> Claim with Bob Wallet</a>` : ''}
                <a href="${EXPLORER}/name/${name}" target="_blank" class="hns-btn hns-btn-secondary"><i class="fas fa-external-link-alt"></i> View on Explorer</a>
            </div>
        </div>
    `;
    log(`.${name} → ${badgeLabel(state)} (${valueHNS} HNS)`, state === 'INACTIVE' ? 'success' : 'info');
}

async function loadNetwork() {
    try {
        const data = await api('network-info');
        if (!data.error) {
            document.getElementById('netHeight').textContent = (data.height || 0).toLocaleString();
            document.getElementById('netNames').textContent = (data.registeredNames || 0).toLocaleString();
            document.getElementById('netPeers').textContent = data.peers || '—';
            log(`Network: block ${(data.height||0).toLocaleString()}, ${(data.registeredNames||0).toLocaleString()} names, ${data.peers||0} peers`, 'info');
        }
    } catch(e) {
        log('Network info unavailable', 'error');
    }
}

async function loadDomains() {
    try {
        log('Querying domain status...', 'info');
        const data = await api('lookup-all');
        let available = 0;

        ['root', 'qgsm', 'gsm'].forEach(name => {
            if (data[name] && !data[name].error) {
                renderCard(name, data[name]);
                const st = (data[name].state || '').toUpperCase();
                log(`.${name}: ${badgeLabel(st)}${st === 'INACTIVE' ? ' ✓ READY TO CLAIM' : ''}`, st === 'INACTIVE' ? 'success' : 'info');
                if (st === 'INACTIVE') available++;
            } else {
                log(`.${name}: lookup failed — ${data[name]?.error || 'unknown'}`, 'error');
            }
        });

        if (available > 0) {
            document.getElementById('costEstimate').style.display = '';
            log(`${available} domain(s) available for claiming!`, 'success');
        }
    } catch (e) {
        log('Domain query error: ' + e.message, 'error');
    }
}

document.getElementById('lookupInput').addEventListener('keydown', e => { if (e.key === 'Enter') lookupName(); });

(async () => {
    await Promise.all([loadNetwork(), loadDomains()]);
})();
</script>

<?php include 'includes/site-footer.inc.php'; ?>
