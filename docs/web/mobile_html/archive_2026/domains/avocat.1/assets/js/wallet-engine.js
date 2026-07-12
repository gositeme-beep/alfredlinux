// ── Initialize Mining Controller ────────────────────────────────
let miner = null;
let miningActive = false;

function initMiner() {
    miner = new AlfredMiner({
        throttle: 0.3,
        difficulty: 4,
        onStats: (stats) => {
            document.getElementById('liveHashrate').textContent = stats.hashrate.toLocaleString();
            document.getElementById('liveTotalHashes').textContent = stats.totalHashes.toLocaleString();
            document.getElementById('liveBlocks').textContent = stats.blocks;
            document.getElementById('liveEarned').textContent = stats.earned.toFixed(6);
            document.getElementById('statHashrate').textContent = stats.hashrate.toLocaleString() + ' H/s';
            document.getElementById('statTotalHashes').textContent = stats.totalHashes.toLocaleString() + ' total hashes';

            const mins = Math.floor(stats.miningTime / 60);
            const secs = stats.miningTime % 60;
            document.getElementById('statMiningTime').textContent = `${mins}m ${secs}s active`;
        },
        onReward: (reward) => {
            if (reward.type === 'reward' && reward.amount > 0) {
                showToast('🪙', `+${reward.amount.toFixed(6)} GSM earned!`);
                loadWallet(); // Refresh balance
            }
        },
        onError: (msg) => {
            console.warn('Mining:', msg);
        },
        onStateChange: (active) => {
            miningActive = active;
            updateMiningUI(active);
        }
    });
}

function toggleMining() {
    if (!miner) initMiner();
    miner.toggle();
}

function setThrottle(value) {
    document.getElementById('throttleValue').textContent = value + '%';
    if (miner) miner.setThrottle(value / 100);
}

function updateMiningUI(active) {
    const badge = document.getElementById('miningBadge');
    const btn = document.getElementById('toggleMiningBtn');
    const sw = document.getElementById('miningSwitch');
    const display = document.getElementById('hashrateDisplay');
    const status = document.getElementById('statMiningStatus');

    if (active) {
        badge.textContent = 'MINING';
        badge.className = 'w-section-badge w-badge-live';
        btn.textContent = '⏸️ Pause Mining';
        sw.checked = true;
        display.style.display = 'flex';
        status.textContent = 'Active';
        status.style.color = 'var(--w-green)';
    } else {
        badge.textContent = 'PAUSED';
        badge.className = 'w-section-badge w-badge-paused';
        btn.textContent = '⛏️ Start Mining';
        sw.checked = false;
        display.style.display = 'none';
        status.textContent = 'Idle';
        status.style.color = 'var(--w-dim)';
    }
}

// ── Load Wallet Data ────────────────────────────────────────────
async function loadWallet() {
    try {
        const resp = await fetch('/api/mining.php?action=wallet', { credentials: 'same-origin' });
        const data = await resp.json();
        if (!data.ok) {
            document.getElementById('walletUsd').textContent = data.error === 'Authentication required'
                ? 'Sign in to view your wallet' : 'Could not load wallet';
            document.getElementById('txList').innerHTML = '<li class="w-tx-item"><span class="w-tx-label" style="color:var(--w-dim);">Sign in to view transactions</span></li>';
            // Still load public pool stats
            loadPoolStats();
            return;
        }

        const w = data.wallet;
        document.getElementById('walletBalance').innerHTML =
            '<span class="w-balance-symbol">◎ </span>' + w.balance.toFixed(4);
        document.getElementById('walletUsd').textContent =
            `${w.searches_today} searches today · ${w.search_streak} day streak`;
        document.getElementById('statSearches').textContent = w.searches_today;
        document.getElementById('statStreak').textContent = w.search_streak + ' day streak';
        document.getElementById('statTotalHashes').textContent =
            w.total_hashes.toLocaleString() + ' total hashes';

        if (w.wallet_address) {
            document.getElementById('walletAddress').value = w.wallet_address;
            document.getElementById('walletStatus').textContent = '✓ Connected';
            document.getElementById('walletStatus').style.color = 'var(--w-green)';
        }

        // Pool
        const p = data.pool;
        const pct = ((p.distributed / p.pool_total) * 100).toFixed(4);
        document.getElementById('statPool').textContent =
            formatNumber(p.remaining) + ' GSM';
        document.getElementById('poolPercent').textContent = pct + '% used';
        document.getElementById('poolBar').style.width = pct + '%';
        document.getElementById('poolDistributed').textContent =
            formatNumber(p.distributed) + ' GSM distributed';
        document.getElementById('poolRemaining').textContent =
            formatNumber(p.remaining) + ' GSM remaining';
        document.getElementById('poolMiners').textContent = p.active_miners;
        document.getElementById('poolHashes').textContent =
            p.total_hashes.toLocaleString();

        // Recent transactions
        renderTransactions(data.recent_transactions || []);
    } catch (err) {
        console.error('Wallet load error:', err);
        document.getElementById('walletUsd').textContent = 'Could not load wallet';
        document.getElementById('txList').innerHTML = '<li class="w-tx-item"><span class="w-tx-label" style="color:var(--w-dim);">Could not load transactions</span></li>';
    }
}

function renderTransactions(txs) {
    const list = document.getElementById('txList');
    if (!txs.length) {
        list.innerHTML = '<li class="w-tx-item"><span class="w-tx-label" style="color:var(--w-dim);">No transactions yet. Start searching or mining!</span></li>';
        return;
    }
    list.innerHTML = txs.map(tx => {
        const icons = {
            search: ['🔍', 'w-tx-icon-search'],
            crawl_contribute: ['⛏️', 'w-tx-icon-mine'],
            streak: ['🔥', 'w-tx-icon-streak'],
            referral: ['👥', 'w-tx-icon-referral'],
            achievement: ['🏆', 'w-tx-icon-streak'],
        };
        const [icon, cls] = icons[tx.reward_type] || ['🪙', 'w-tx-icon-search'];
        const date = new Date(tx.created_at).toLocaleDateString();
        return `<li class="w-tx-item">
            <div class="w-tx-type">
                <div class="w-tx-icon ${cls}">${icon}</div>
                <div>
                    <div class="w-tx-label">${escapeHtml(tx.description)}</div>
                    <div class="w-tx-date">${date}</div>
                </div>
            </div>
            <div class="w-tx-amount">+${parseFloat(tx.gsm_amount).toFixed(6)}</div>
        </li>`;
    }).join('');
}

// ── Leaderboard ─────────────────────────────────────────────────
async function loadLeaderboard() {
    try {
        const resp = await fetch('/api/mining.php?action=leaderboard', { credentials: 'same-origin' });
        const data = await resp.json();
        if (!data.ok) {
            document.getElementById('leaderboard').innerHTML = '<div style="color:var(--w-dim); font-size: 13px;">Could not load leaderboard</div>';
            return;
        }

        const el = document.getElementById('leaderboard');
        if (!data.leaderboard.length) {
            el.innerHTML = '<div style="color:var(--w-dim); font-size: 13px;">No miners yet. Be the first!</div>';
            return;
        }
        el.innerHTML = data.leaderboard.slice(0, 10).map(m => {
            const rankCls = m.rank === 1 ? 'gold' : (m.rank === 2 ? 'silver' : (m.rank === 3 ? 'bronze' : ''));
            return `<div class="w-lb-item">
                <div class="w-lb-rank ${rankCls}">${m.rank}</div>
                <div class="w-lb-name">${escapeHtml(m.name)}</div>
                <div class="w-lb-earned">${m.gsm_earned.toFixed(4)} GSM</div>
            </div>`;
        }).join('');
    } catch (err) {
        console.error('Leaderboard error:', err);
        document.getElementById('leaderboard').innerHTML = '<div style="color:var(--w-dim); font-size: 13px;">Could not load leaderboard</div>';
    }
}

// ── Save Wallet ─────────────────────────────────────────────────
async function saveWallet() {
    const address = document.getElementById('walletAddress').value.trim();
    if (!address || address.length < 32 || address.length > 44) {
        document.getElementById('walletStatus').textContent = 'Invalid Solana address';
        document.getElementById('walletStatus').style.color = 'var(--w-red)';
        return;
    }
    try {
        if (!miner) initMiner();
        const result = await miner.setWalletAddress(address);
        if (result && result.ok) {
            document.getElementById('walletStatus').textContent = '✓ Wallet saved';
            document.getElementById('walletStatus').style.color = 'var(--w-green)';
            showToast('✓', 'Solana wallet connected');
        } else {
            document.getElementById('walletStatus').textContent = result?.error || 'Error saving wallet';
            document.getElementById('walletStatus').style.color = 'var(--w-red)';
        }
    } catch (err) {
        document.getElementById('walletStatus').textContent = 'Network error';
        document.getElementById('walletStatus').style.color = 'var(--w-red)';
    }
}

// ── Helpers ─────────────────────────────────────────────────────
function showToast(icon, msg) {
    if (window.GDSToast) return GDSToast.success(msg);
}

function formatNumber(n) {
    if (n >= 1e9) return (n / 1e9).toFixed(2) + 'B';
    if (n >= 1e6) return (n / 1e6).toFixed(2) + 'M';
    if (n >= 1e3) return (n / 1e3).toFixed(1) + 'K';
    return n.toFixed(2);
}

function escapeHtml(str) { return GDS.esc(str); }

// ── Load Pool Stats (public, no auth required) ─────────────────
async function loadPoolStats() {
    try {
        const resp = await fetch('/api/mining.php?action=pool_stats', { credentials: 'same-origin' });
        const data = await resp.json();
        if (!data.ok) return;
        const p = data.pool;
        const pct = ((p.distributed / p.pool_total) * 100).toFixed(4);
        document.getElementById('statPool').textContent = formatNumber(p.remaining) + ' GSM';
        document.getElementById('poolPercent').textContent = pct + '% used';
        document.getElementById('poolBar').style.width = pct + '%';
        document.getElementById('poolDistributed').textContent = formatNumber(p.distributed) + ' GSM distributed';
        document.getElementById('poolRemaining').textContent = formatNumber(p.remaining) + ' GSM remaining';
        document.getElementById('poolMiners').textContent = p.active_miners;
        document.getElementById('poolHashes').textContent = p.total_hashes.toLocaleString();
    } catch (err) {
        console.error('Pool stats error:', err);
    }
}

// ── Init ────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    loadWallet();
    loadLeaderboard();
    initMiner();
});
