<?php
$page_title = 'GSM Blockchain — Solana Integration — GoSiteMe';
$page_description = 'GSM token on Solana. Link your wallet, withdraw tokens, view smart contract status, and track the on-chain roadmap.';
$page_canonical = 'https://root.com/blockchain.php';
$page_og_title = 'GSM Token — Solana Blockchain Integration';
$page_og_description = 'GSM is an SPL token on Solana. Link Phantom/Solflare, withdraw to your wallet, mint real NFTs, and trade on DEX.';

include __DIR__ . '/includes/site-header.inc.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$is_logged_in = !empty($_SESSION['logged_in']) && !empty($_SESSION['client_id']);
?>

<style>
:root {
    --bc-bg: #0a0a14;
    --bc-surface: #12121e;
    --bc-surface-2: #1a1a2e;
    --bc-border: rgba(255,255,255,0.08);
    --bc-accent: #9945FF;
    --bc-cyan: #14F195;
    --bc-blue: #00D4FF;
    --bc-gold: #fbbf24;
    --bc-red: #f87171;
    --bc-text: #e8e8f0;
    --bc-text-muted: #8a8a9a;
    --bc-radius: 16px;
}

.bc-page {
    background: var(--bc-bg);
    color: var(--bc-text);
    min-height: 100vh;
    font-family: 'Inter', system-ui, sans-serif;
}

/* Hero */
.bc-hero {
    position: relative;
    padding: 130px 2rem 3rem;
    text-align: center;
    background:
        radial-gradient(ellipse at 30% 0%, rgba(153,69,255,0.2) 0%, transparent 60%),
        radial-gradient(ellipse at 70% 100%, rgba(20,241,149,0.1) 0%, transparent 60%),
        var(--bc-bg);
}
.bc-hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(153,69,255,0.15);
    border: 1px solid rgba(153,69,255,0.3);
    color: #c4b5fd;
    padding: 6px 18px;
    border-radius: 999px;
    font-size: 0.8rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
}
.bc-hero h1 {
    font-size: clamp(2rem, 5vw, 3rem);
    font-weight: 800;
    margin-bottom: 0.5rem;
}
.bc-hero h1 span {
    background: linear-gradient(135deg, var(--bc-accent), var(--bc-cyan));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.bc-hero .tagline {
    color: var(--bc-text-muted);
    font-size: 1.1rem;
    max-width: 600px;
    margin: 0 auto;
}

.bc-content { max-width: 1100px; margin: 0 auto; padding: 2rem; }

/* Quick Nav */
.bc-quick-nav { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 2rem; }
.bc-link {
    display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px;
    border-radius: 999px; background: var(--bc-surface); border: 1px solid var(--bc-border);
    color: var(--bc-text-muted); font-size: 0.8rem; font-weight: 600;
    text-decoration: none; transition: all 0.2s;
}
.bc-link:hover { border-color: var(--bc-accent); color: var(--bc-text); }

/* Token Info Card */
.bc-token-card {
    background: linear-gradient(135deg, rgba(153,69,255,0.1), rgba(20,241,149,0.05));
    border: 1px solid rgba(153,69,255,0.2);
    border-radius: var(--bc-radius);
    padding: 2rem;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 2rem;
    flex-wrap: wrap;
}
.bc-token-logo {
    width: 80px; height: 80px; border-radius: 50%;
    background: linear-gradient(135deg, var(--bc-accent), var(--bc-cyan));
    display: flex; align-items: center; justify-content: center;
    font-size: 2rem; font-weight: 900; color: #fff;
    flex-shrink: 0;
}
.bc-token-info { flex: 1; min-width: 250px; }
.bc-token-info h2 { font-size: 1.4rem; margin-bottom: 0.3rem; }
.bc-token-meta {
    display: flex; gap: 1.5rem; flex-wrap: wrap; margin-top: 0.8rem;
}
.bc-token-meta div { text-align: center; }
.bc-token-meta .val {
    font-size: 1.2rem; font-weight: 800; font-family: 'JetBrains Mono', monospace;
}
.bc-token-meta .lbl {
    font-size: 0.7rem; color: var(--bc-text-muted); text-transform: uppercase;
}

/* Status Grid */
.bc-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}
.bc-card {
    background: var(--bc-surface);
    border: 1px solid var(--bc-border);
    border-radius: var(--bc-radius);
    padding: 1.5rem;
}
.bc-card h3 {
    font-size: 1.1rem;
    font-weight: 700;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.bc-status-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
}
.bc-status-badge.deployed { background: rgba(20,241,149,0.15); color: var(--bc-cyan); }
.bc-status-badge.ready { background: rgba(251,191,36,0.15); color: var(--bc-gold); }
.bc-status-badge.planned { background: rgba(138,138,154,0.15); color: var(--bc-text-muted); }
.bc-status-badge.pending { background: rgba(0,212,255,0.15); color: var(--bc-blue); }

/* Wallet Section */
.bc-wallet-section {
    background: var(--bc-surface);
    border: 1px solid var(--bc-border);
    border-radius: var(--bc-radius);
    padding: 2rem;
    margin-bottom: 2rem;
}
.bc-wallet-section h2 {
    font-size: 1.3rem;
    margin-bottom: 1rem;
}
.bc-wallet-row {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}
.bc-input {
    flex: 1;
    min-width: 250px;
    padding: 12px 16px;
    border-radius: 10px;
    border: 1px solid var(--bc-border);
    background: var(--bc-surface-2);
    color: var(--bc-text);
    font-size: 0.9rem;
    font-family: 'JetBrains Mono', monospace;
}
.bc-input::placeholder { color: var(--bc-text-muted); }
.bc-select {
    padding: 12px 16px;
    border-radius: 10px;
    border: 1px solid var(--bc-border);
    background: var(--bc-surface-2);
    color: var(--bc-text);
    font-size: 0.9rem;
}
.bc-btn {
    padding: 12px 24px;
    border-radius: 10px;
    border: none;
    font-weight: 700;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s;
    background: linear-gradient(135deg, var(--bc-accent), var(--bc-cyan));
    color: #fff;
}
.bc-btn:hover { filter: brightness(1.1); }
.bc-btn.secondary {
    background: transparent;
    border: 1px solid var(--bc-border);
    color: var(--bc-text-muted);
}
.bc-btn:disabled { opacity: 0.5; cursor: not-allowed; }

/* Roadmap */
.bc-roadmap {
    background: var(--bc-surface);
    border: 1px solid var(--bc-border);
    border-radius: var(--bc-radius);
    padding: 2rem;
    margin-bottom: 2rem;
}
.bc-roadmap h2 { font-size: 1.3rem; margin-bottom: 1.5rem; }
.bc-phase {
    display: flex;
    gap: 1rem;
    padding: 1rem 0;
    border-bottom: 1px solid var(--bc-border);
    align-items: flex-start;
}
.bc-phase:last-child { border-bottom: none; }
.bc-phase-id {
    width: 44px; height: 44px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 0.8rem; flex-shrink: 0;
    font-family: 'JetBrains Mono', monospace;
}
.bc-phase-info { flex: 1; }
.bc-phase-info h4 { font-size: 0.95rem; margin-bottom: 0.2rem; }
.bc-phase-info p { font-size: 0.82rem; color: var(--bc-text-muted); line-height: 1.5; }
.bc-phase-reqs {
    display: flex; gap: 6px; flex-wrap: wrap; margin-top: 0.5rem;
}
.bc-phase-reqs span {
    font-size: 0.65rem; padding: 3px 8px; border-radius: 6px;
    background: rgba(153,69,255,0.1); color: #c4b5fd;
}

/* Withdrawal */
.bc-withdraw-section {
    background: var(--bc-surface);
    border: 1px solid var(--bc-border);
    border-radius: var(--bc-radius);
    padding: 2rem;
    margin-bottom: 2rem;
}
.bc-withdraw-section h2 { font-size: 1.3rem; margin-bottom: 1rem; }
.bc-balance-display {
    font-size: 2rem;
    font-weight: 800;
    font-family: 'JetBrains Mono', monospace;
    color: var(--bc-cyan);
    margin-bottom: 1rem;
}
.bc-balance-display .unit { font-size: 1rem; color: var(--bc-text-muted); }

/* History Table */
.bc-table-wrap { overflow-x: auto; }
.bc-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.82rem;
}
.bc-table th {
    text-align: left;
    padding: 10px;
    color: var(--bc-text-muted);
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.7rem;
    letter-spacing: 1px;
    border-bottom: 1px solid var(--bc-border);
}
.bc-table td {
    padding: 10px;
    border-bottom: 1px solid var(--bc-border);
}
.bc-table .addr {
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.75rem;
    max-width: 120px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

@media (max-width: 768px) {
    .bc-grid { grid-template-columns: 1fr; }
    .bc-token-card { flex-direction: column; text-align: center; }
}
</style>

<div class="bc-page">

<div class="bc-hero">
    <div class="bc-hero-badge">⛓️ Solana Mainnet</div>
    <h1>GSM <span>Blockchain</span></h1>
    <p class="tagline">GSM token on Solana. Link your wallet, withdraw tokens, mint real NFTs, and track smart contract deployment.</p>
</div>

<div class="bc-content">

<!-- Quick Nav -->
<div class="bc-quick-nav">
    <a href="/wallet.php" class="bc-link">💰 Wallet</a>
    <a href="/mine.php" class="bc-link">⛏️ Mine GSM</a>
    <a href="/nft-trophies.php" class="bc-link">🏅 NFT Trophies</a>
    <a href="/land-market.php" class="bc-link">🗺️ Land Market</a>
    <a href="/governance.php" class="bc-link">🏛️ Governance</a>
</div>

<!-- Token Card -->
<div class="bc-token-card">
    <div class="bc-token-logo">G</div>
    <div class="bc-token-info">
        <h2>GoSiteMe Token (GSM)</h2>
        <p style="color:var(--bc-text-muted);font-size:0.9rem;">SPL Token on Solana — 1 billion fixed supply, 8 decimals</p>
        <div class="bc-token-meta">
            <div><div class="val" style="color:var(--bc-cyan)">1B</div><div class="lbl">Total Supply</div></div>
            <div><div class="val" style="color:var(--bc-accent)">SPL</div><div class="lbl">Standard</div></div>
            <div><div class="val" style="color:var(--bc-gold)">8</div><div class="lbl">Decimals</div></div>
            <div><div class="val" style="color:var(--bc-blue)" id="bcExRate">10,000</div><div class="lbl">GSM/SOL</div></div>
        </div>
    </div>
</div>

<!-- Smart Contract Status Grid -->
<div class="bc-grid">
    <div class="bc-card" id="bcSplCard">
        <h3>🪙 SPL Token <span class="bc-status-badge" style="background:rgba(20,241,149,.15);color:#14F195;border-color:rgba(20,241,149,.3)" id="bcSplStatus">Live on Mainnet</span></h3>
        <p style="color:var(--bc-text-muted);font-size:0.85rem;">GSM is live on Solana mainnet — 1 billion tokens minted, 8 decimals, Metaplex metadata.</p>
        <div style="margin-top:1rem;font-size:0.8rem;">
            <div style="color:var(--bc-text-muted);margin-bottom:4px;">Mint Address:</div>
            <div style="font-family:monospace;color:var(--bc-cyan);word-break:break-all;" id="bcMintAddr"><a href="https://solscan.io/token/7Uix6nuVfPEPnqV9o9rffDvA6bX2YSLUjUJSQxU5Q7un" target="_blank" rel="noopener" style="color:var(--bc-cyan);text-decoration:underline;">7Uix6nuVfPEPnqV9o9rffDvA6bX2YSLUjUJSQxU5Q7un</a></div>
        </div>
    </div>
    <div class="bc-card">
        <h3>💱 DEX Listing <span class="bc-status-badge planned">Planned</span></h3>
        <p style="color:var(--bc-text-muted);font-size:0.85rem;">SOL/GSM trading pair on Raydium, routed via Jupiter aggregator.</p>
        <div style="margin-top:1rem;font-size:0.8rem;color:var(--bc-text-muted);">
            <div>Raydium CLMM pool → Jupiter auto-routing → real market price</div>
        </div>
    </div>
    <div class="bc-card">
        <h3>🎨 NFT Program <span class="bc-status-badge planned">Planned</span></h3>
        <p style="color:var(--bc-text-muted);font-size:0.85rem;">Metaplex Bubblegum compressed NFTs for trophies and achievements.</p>
        <div style="margin-top:1rem;font-size:0.8rem;color:var(--bc-text-muted);">
            <div id="bcNftsQueued">0 NFTs queued for minting</div>
        </div>
    </div>
    <div class="bc-card">
        <h3>🔐 Escrow Program <span class="bc-status-badge planned">Planned</span></h3>
        <p style="color:var(--bc-text-muted);font-size:0.85rem;">Anchor PDA-based trustless escrow for high-value wagers.</p>
        <div style="margin-top:1rem;font-size:0.8rem;color:var(--bc-text-muted);">
            <div id="bcEscrowCount">0 active escrows</div>
        </div>
    </div>
</div>

<?php if ($is_logged_in): ?>
<!-- Wallet Linking -->
<div class="bc-wallet-section">
    <h2>🔗 Link Solana Wallet</h2>
    <p style="color:var(--bc-text-muted);font-size:0.9rem;margin-bottom:1rem;">Connect your Phantom, Solflare, or Backpack wallet to withdraw GSM, receive NFTs, and participate in on-chain features.</p>
    <div id="bcWalletStatus">
        <div style="color:var(--bc-text-muted);">Loading wallet status...</div>
    </div>
    <div id="bcWalletForm" style="display:none;">
        <!-- One-Click Wallet Connect Buttons -->
        <div style="display:flex;gap:0.75rem;flex-wrap:wrap;margin-bottom:1.5rem;">
            <button class="bc-btn" onclick="connectWallet('phantom')" id="btnPhantom" style="background:linear-gradient(135deg,#AB9FF2,#7B61FF);gap:8px;display:inline-flex;align-items:center;">
                <svg width="20" height="20" viewBox="0 0 128 128" fill="white"><path d="M110.6 57.7c-4.1 0-7.8 1.7-10.3 4.6-4.6-8.4-13.5-14.1-23.7-14.1-15 0-27.2 12.2-27.2 27.2s12.2 27.2 27.2 27.2c10.2 0 19.1-5.7 23.7-14.1 2.5 2.9 6.2 4.6 10.3 4.6 7.6 0 13.7-6.1 13.7-13.7S118.2 57.7 110.6 57.7z"/></svg>
                Connect Phantom
            </button>
            <button class="bc-btn" onclick="connectWallet('solflare')" id="btnSolflare" style="background:linear-gradient(135deg,#FC7227,#FC9B27);gap:8px;display:inline-flex;align-items:center;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="white"><circle cx="12" cy="12" r="10"/></svg>
                Connect Solflare
            </button>
            <button class="bc-btn" onclick="connectWallet('backpack')" id="btnBackpack" style="background:linear-gradient(135deg,#E33E3F,#FF6B6B);gap:8px;display:inline-flex;align-items:center;">
                🎒 Connect Backpack
            </button>
        </div>
        <!-- Manual Address Entry (fallback) -->
        <details style="margin-top:0.5rem;">
            <summary style="color:var(--bc-text-muted);font-size:0.8rem;cursor:pointer;margin-bottom:0.75rem;">Or enter address manually</summary>
            <div class="bc-wallet-row">
                <input type="text" class="bc-input" id="bcWalletAddr" placeholder="Solana wallet address (e.g. 7xKX...)" maxlength="50">
                <select class="bc-select" id="bcWalletType">
                    <option value="phantom">Phantom</option>
                    <option value="solflare">Solflare</option>
                    <option value="backpack">Backpack</option>
                    <option value="other">Other</option>
                </select>
                <button class="bc-btn secondary" onclick="linkWallet()">Link Manually</button>
            </div>
        </details>
    </div>
</div>

<!-- Withdrawal -->
<div class="bc-withdraw-section">
    <h2>📤 Withdraw GSM</h2>
    <div class="bc-balance-display">
        <span id="bcBalance">0</span> <span class="unit">GSM available</span>
    </div>
    <div class="bc-wallet-row">
        <input type="number" class="bc-input" id="bcWithdrawAmt" placeholder="Amount (min 100 GSM)" min="100" step="1" style="max-width:250px;">
        <button class="bc-btn" id="bcWithdrawBtn" onclick="requestWithdrawal()" disabled>Request Withdrawal</button>
    </div>
    <p style="color:var(--bc-text-muted);font-size:0.8rem;margin-top:0.5rem;">0.5% withdrawal fee. Min 100 GSM. Max 1,000,000 GSM per request.</p>

    <!-- Withdrawal History -->
    <h3 style="margin-top:2rem;margin-bottom:1rem;font-size:1rem;">Withdrawal History</h3>
    <div class="bc-table-wrap">
        <table class="bc-table">
            <thead>
                <tr><th>ID</th><th>Amount</th><th>Fee</th><th>Destination</th><th>Status</th><th>Date</th><th>Tx</th></tr>
            </thead>
            <tbody id="bcWithdrawalHistory">
                <tr><td colspan="7" style="text-align:center;color:var(--bc-text-muted);">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
<div style="background:var(--bc-surface);border:1px solid var(--bc-border);border-radius:var(--bc-radius);padding:2rem;text-align:center;margin-bottom:2rem;">
    <h2 style="margin-bottom:0.5rem;">Log in to manage your blockchain features</h2>
    <p style="color:var(--bc-text-muted);margin-bottom:1rem;">Link your wallet, withdraw GSM, mint NFTs, and create escrows.</p>
    <a href="/login.php" class="bc-btn" style="display:inline-block;text-decoration:none;padding:12px 30px;">Log In →</a>
</div>
<?php endif; ?>

<!-- Roadmap -->
<div class="bc-roadmap">
    <h2>🗺️ Smart Contract Roadmap</h2>
    <div id="bcRoadmap">
        <!-- Populated by JS -->
    </div>
</div>

<!-- Stats -->
<div class="bc-card" style="margin-bottom:2rem;">
    <h3>📊 Blockchain Statistics</h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:1rem;margin-top:1rem;" id="bcStats">
        <div style="text-align:center;"><div class="val" style="font-size:1.3rem;font-weight:800;font-family:monospace;color:var(--bc-cyan);" id="bcStatWallets">—</div><div style="font-size:0.7rem;color:var(--bc-text-muted);text-transform:uppercase;">Wallets Linked</div></div>
        <div style="text-align:center;"><div class="val" style="font-size:1.3rem;font-weight:800;font-family:monospace;color:var(--bc-accent);" id="bcStatWithdrawn">—</div><div style="font-size:0.7rem;color:var(--bc-text-muted);text-transform:uppercase;">GSM Withdrawn</div></div>
        <div style="text-align:center;"><div class="val" style="font-size:1.3rem;font-weight:800;font-family:monospace;color:var(--bc-gold);" id="bcStatEscrows">—</div><div style="font-size:0.7rem;color:var(--bc-text-muted);text-transform:uppercase;">Escrows</div></div>
        <div style="text-align:center;"><div class="val" style="font-size:1.3rem;font-weight:800;font-family:monospace;color:var(--bc-blue);" id="bcStatNfts">—</div><div style="font-size:0.7rem;color:var(--bc-text-muted);text-transform:uppercase;">NFTs Minted</div></div>
    </div>
</div>

</div><!-- /bc-content -->
</div><!-- /bc-page -->

<script>
(function(){
    'use strict';
    const API = '/api/solana-blockchain.php';

    /* ── Wallet Providers ── */
    const PROVIDERS = {
        phantom:  { name: 'Phantom',  get: () => window.phantom?.solana,  installUrl: 'https://phantom.app/' },
        solflare: { name: 'Solflare', get: () => window.solflare,         installUrl: 'https://solflare.com/' },
        backpack: { name: 'Backpack', get: () => window.backpack,         installUrl: 'https://backpack.app/' }
    };

    async function apiFetch(action, opts) {
        const url = `${API}?action=${action}`;
        try {
            const res = await fetch(url, opts || {});
            return await res.json();
        } catch(e) { return {success:false, error:e.message}; }
    }

    /* ── Wallet Detection ── */
    function detectWallets() {
        const detected = {};
        for (const [key, prov] of Object.entries(PROVIDERS)) {
            const provider = prov.get();
            detected[key] = !!(provider && provider.isConnected !== undefined);
        }
        // Update button states
        for (const [key, available] of Object.entries(detected)) {
            const btn = document.getElementById('btn' + key.charAt(0).toUpperCase() + key.slice(1));
            if (!btn) continue;
            if (available) {
                btn.dataset.installed = '1';
            } else {
                btn.style.opacity = '0.6';
                btn.title = `${PROVIDERS[key].name} not detected — click to install`;
            }
        }
        return detected;
    }

    /* ── One-Click Wallet Connect ── */
    window.connectWallet = async function(providerKey) {
        const prov = PROVIDERS[providerKey];
        const provider = prov?.get();

        if (!provider) {
            if (confirm(`${prov.name} wallet not detected. Open ${prov.name} installation page?`)) {
                window.open(prov.installUrl, '_blank', 'noopener');
            }
            return;
        }

        try {
            // Disable buttons during connect
            document.querySelectorAll('#bcWalletForm .bc-btn').forEach(b => b.disabled = true);

            // Request connection
            const resp = await provider.connect();
            const pubkey = resp.publicKey?.toString() || provider.publicKey?.toString();
            if (!pubkey) throw new Error('No public key returned');

            // Sign verification message (proves wallet ownership)
            const nonce = Date.now().toString(36) + Math.random().toString(36).slice(2, 8);
            const message = `Link wallet to GoSiteMe GSM\\nAddress: ${pubkey}\\nNonce: ${nonce}`;
            const encoded = new TextEncoder().encode(message);
            const signature = await provider.signMessage(encoded, 'utf8');

            // Convert signature to base64 for transport
            const sigBytes = signature.signature || signature;
            const sigB64 = btoa(String.fromCharCode(...new Uint8Array(sigBytes)));

            // Submit to API with proof of ownership
            const d = await apiFetch('link-wallet', {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({
                    wallet_address: pubkey,
                    wallet_type: providerKey,
                    signature: sigB64,
                    message: message,
                    nonce: nonce
                })
            });

            if (d.success) {
                loadWalletStatus();
            } else {
                alert(d.error || 'Failed to link wallet');
            }
        } catch(e) {
            if (e.code === 4001 || e.message?.includes('User rejected')) {
                // User cancelled — do nothing
            } else {
                console.error('Wallet connect error:', e);
                alert('Wallet connection failed: ' + (e.message || 'Unknown error'));
            }
        } finally {
            document.querySelectorAll('#bcWalletForm .bc-btn').forEach(b => b.disabled = false);
        }
    };

    /* ── Token Info ── */
    async function loadTokenInfo() {
        const d = await apiFetch('token-info');
        if (!d.success) return;
        const t = d.data;
        if (t.token.mint_address) {
            document.getElementById('bcMintAddr').textContent = t.token.mint_address;
            document.getElementById('bcSplStatus').textContent = 'Deployed';
            document.getElementById('bcSplStatus').className = 'bc-status-badge deployed';
        }
    }

    /* ── Wallet Status ── */
    async function loadWalletStatus() {
        const d = await apiFetch('wallet-status');
        if (!d.success) return;
        const w = d.data;

        const statusDiv = document.getElementById('bcWalletStatus');
        const formDiv = document.getElementById('bcWalletForm');

        if (w.wallet_linked) {
            const shortAddr = w.wallet_address.slice(0,6) + '...' + w.wallet_address.slice(-4);
            statusDiv.innerHTML = `
                <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;">
                    <span style="color:var(--bc-cyan);font-weight:700;">✓ Wallet Connected</span>
                    <span style="font-family:monospace;font-size:0.85rem;background:var(--bc-surface-2);padding:6px 12px;border-radius:8px;cursor:pointer;" title="${w.wallet_address}" onclick="navigator.clipboard.writeText('${w.wallet_address}').then(()=>this.textContent='Copied!').then(()=>setTimeout(()=>this.textContent='${shortAddr}',1200))">${shortAddr}</span>
                    <span class="bc-status-badge" style="background:${w.wallet_type==='phantom'?'#AB9FF2':w.wallet_type==='solflare'?'#FC7227':'#E33E3F'}22;color:${w.wallet_type==='phantom'?'#AB9FF2':w.wallet_type==='solflare'?'#FC7227':'#E33E3F'};padding:4px 10px;border-radius:12px;font-size:0.75rem;font-weight:600;">${w.wallet_type}</span>
                    <button class="bc-btn secondary" onclick="unlinkWallet()" style="font-size:0.8rem;padding:6px 14px;">Disconnect</button>
                </div>`;
            formDiv.style.display = 'none';
            const wBtn = document.getElementById('bcWithdrawBtn');
            if (wBtn) wBtn.disabled = false;
        } else {
            statusDiv.innerHTML = '<div style="color:var(--bc-gold);margin-bottom:0.5rem;">⚠ No wallet connected — choose a provider above</div>';
            formDiv.style.display = '';
        }

        // Balance
        if (w.platform_balance) {
            document.getElementById('bcBalance').textContent = Number(w.platform_balance.gsm_available).toLocaleString();
        }
    }

    /* ── Withdrawal History ── */
    async function loadWithdrawals() {
        const d = await apiFetch('withdrawal-history');
        if (!d.success) return;
        const tbody = document.getElementById('bcWithdrawalHistory');
        if (!d.data.withdrawals.length) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:var(--bc-text-muted);">No withdrawals yet</td></tr>';
            return;
        }
        tbody.innerHTML = d.data.withdrawals.map(w => {
            const statusColor = w.status === 'completed' ? 'var(--bc-cyan)' : w.status === 'rejected' ? 'var(--bc-red)' : 'var(--bc-gold)';
            return `<tr>
                <td>#${w.id}</td>
                <td>${Number(w.amount_gsm).toLocaleString()} GSM</td>
                <td>${Number(w.fee_gsm).toLocaleString()}</td>
                <td class="addr">${w.destination}</td>
                <td><span style="color:${statusColor};font-weight:600;">${w.status}</span></td>
                <td style="font-size:0.75rem;">${new Date(w.requested_at).toLocaleDateString()}</td>
                <td>${w.tx_signature ? `<a href="https://solscan.io/tx/${w.tx_signature}" target="_blank" style="color:var(--bc-cyan);">View</a>` : '—'}</td>
            </tr>`;
        }).join('');
    }

    /* ── Blockchain Stats ── */
    async function loadStats() {
        const d = await apiFetch('blockchain-stats');
        if (!d.success) return;
        const s = d.data;
        document.getElementById('bcStatWallets').textContent = s.wallets_linked;
        document.getElementById('bcStatWithdrawn').textContent = Number(s.total_withdrawn_gsm).toLocaleString();
        document.getElementById('bcStatEscrows').textContent = s.total_escrows;
        document.getElementById('bcStatNfts').textContent = s.nfts_minted;
        document.getElementById('bcNftsQueued').textContent = `${s.nfts_queued} NFTs queued for minting`;
        document.getElementById('bcEscrowCount').textContent = `${s.active_escrows} active escrows`;
    }

    /* ── Roadmap ── */
    async function loadRoadmap() {
        const d = await apiFetch('roadmap');
        if (!d.success) return;
        const container = document.getElementById('bcRoadmap');
        const colors = {completed:'var(--bc-cyan)',ready:'var(--bc-gold)',planned:'var(--bc-text-muted)'};
        container.innerHTML = d.data.phases.map(p => `
            <div class="bc-phase">
                <div class="bc-phase-id" style="background:${colors[p.status]||colors.planned}22;color:${colors[p.status]||colors.planned};border:2px solid ${colors[p.status]||colors.planned}">${p.id}</div>
                <div class="bc-phase-info">
                    <h4>${p.name} <span class="bc-status-badge ${p.status}">${p.status}</span></h4>
                    <p>${p.description}</p>
                    <div class="bc-phase-reqs">
                        ${(p.requirements||[]).map(r => `<span>${r}</span>`).join('')}
                    </div>
                </div>
            </div>
        `).join('');
    }

    /* ── Actions ── */
    window.linkWallet = async function() {
        const addr = document.getElementById('bcWalletAddr').value.trim();
        const type = document.getElementById('bcWalletType').value;
        if (!addr) return alert('Enter a Solana wallet address');
        if (!/^[1-9A-HJ-NP-Za-km-z]{32,44}$/.test(addr)) return alert('Invalid Solana address format');
        const d = await apiFetch('link-wallet', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({wallet_address: addr, wallet_type: type})
        });
        if (d.success) { loadWalletStatus(); }
        else alert(d.error || 'Failed to link wallet');
    };

    window.unlinkWallet = async function() {
        if (!confirm('Disconnect your Solana wallet?')) return;
        const d = await apiFetch('unlink-wallet', {method:'POST'});
        if (d.success) loadWalletStatus();
        else alert(d.error || 'Failed');
    };

    window.requestWithdrawal = async function() {
        const amt = parseFloat(document.getElementById('bcWithdrawAmt').value);
        if (!amt || amt < 100) return alert('Minimum withdrawal is 100 GSM');
        if (!confirm(`Withdraw ${amt.toLocaleString()} GSM? (0.5% fee applies)`)) return;
        const d = await apiFetch('request-withdrawal', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({amount_gsm: amt})
        });
        if (d.success) {
            alert(`Withdrawal #${d.data.withdrawal_id} queued! ${d.data.note}`);
            loadWalletStatus();
            loadWithdrawals();
        } else alert(d.error || 'Failed');
    };

    /* ── Init ── */
    loadTokenInfo();
    loadRoadmap();
    loadStats();
    <?php if ($is_logged_in): ?>
    loadWalletStatus();
    loadWithdrawals();
    // Detect wallets after DOM + extensions load
    setTimeout(detectWallets, 500);
    <?php endif; ?>
})();
</script>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
