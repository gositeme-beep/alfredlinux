<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$loggedIn = !empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$userName = $_SESSION['client_name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NFT Trophies — Achievement Gallery</title>
    <meta name="description" content="Earn, collect, and showcase NFT trophies from gaming achievements across the GoSiteMe platform.">
    <link rel="stylesheet" href="/assets/css/fonts.css">
    <link rel="icon" href="/brand/logo.png" type="image/png">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        :root{--bg:#030308;--surface:#0c0c1a;--surface2:#111126;--border:rgba(100,140,255,0.08);--glow:rgba(100,160,255,0.12);--text:#d8dce8;--dim:#5a6488;--accent:#6c5ce7;--accent2:#a29bfe;--green:#00b894;--red:#ff6b6b;--gold:#ffd700;--glass:rgba(12,12,26,0.85)}
        body{font-family:'Inter',system-ui,sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
        a{color:var(--accent2);text-decoration:none}
        .container{max-width:1200px;margin:0 auto;padding:2rem 1.5rem}
        .page-header{text-align:center;margin-bottom:2rem}
        .page-header h1{font-size:2rem;font-weight:800;background:linear-gradient(135deg,var(--gold),#ff8c00);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
        .page-header p{color:var(--dim);margin-top:.5rem}
        .tabs{display:flex;gap:.5rem;justify-content:center;margin-bottom:2rem;flex-wrap:wrap}
        .tab{padding:.6rem 1.2rem;border-radius:10px;background:var(--surface);border:1px solid var(--border);color:var(--dim);cursor:pointer;font-size:.85rem;font-weight:600;transition:.2s}
        .tab.active,.tab:hover{background:var(--accent);color:#fff;border-color:var(--accent)}
        .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:2rem}
        .stat-card{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:1rem;text-align:center}
        .stat-val{font-size:1.5rem;font-weight:800;color:var(--gold)}
        .stat-label{font-size:.75rem;color:var(--dim);margin-top:.25rem}
        .trophy-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:1rem}
        .trophy-card{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:1.5rem;text-align:center;transition:.3s;position:relative;overflow:hidden}
        .trophy-card:hover{border-color:var(--gold);transform:translateY(-2px);box-shadow:0 8px 30px rgba(255,215,0,0.1)}
        .trophy-icon{font-size:3rem;margin-bottom:.75rem}
        .trophy-name{font-size:1rem;font-weight:700;margin-bottom:.3rem}
        .trophy-desc{font-size:.8rem;color:var(--dim);margin-bottom:.75rem}
        .trophy-rarity{font-size:.7rem;font-weight:700;text-transform:uppercase;padding:.2rem .5rem;border-radius:4px;display:inline-block}
        .rarity-common{background:rgba(90,100,136,0.2);color:var(--dim)}
        .rarity-rare{background:rgba(108,92,231,0.2);color:var(--accent2)}
        .rarity-epic{background:rgba(255,107,107,0.2);color:var(--red)}
        .rarity-legendary{background:rgba(255,215,0,0.2);color:var(--gold)}
        .trophy-game{font-size:.7rem;color:var(--dim);margin-top:.5rem}
        .trophy-earned{position:absolute;top:.75rem;right:.75rem;font-size:.7rem;color:var(--green);font-weight:700}
        .trophy-locked{opacity:.5;filter:grayscale(0.5)}
        .btn{padding:.5rem 1rem;border-radius:8px;border:none;cursor:pointer;font-weight:700;font-size:.8rem;margin-top:.5rem}
        .btn-claim{background:var(--gold);color:#000}
        .btn-claim:hover{opacity:.9}
        .btn-mint{background:rgba(108,92,231,0.2);color:var(--accent2);border:1px solid rgba(108,92,231,0.3)}
        .empty{text-align:center;padding:3rem;color:var(--dim)}
        .nav-back{display:inline-flex;align-items:center;gap:.5rem;color:var(--dim);font-size:.85rem;margin-bottom:1rem}
        .nav-back:hover{color:var(--text)}
        .modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:100;justify-content:center;align-items:center}
        .modal-overlay.active{display:flex}
        .modal{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:2rem;max-width:500px;width:90%;max-height:80vh;overflow-y:auto}
        .modal h2{margin-bottom:1rem}
        @media(max-width:600px){.container{padding:1rem}.trophy-grid{grid-template-columns:1fr 1fr}}
    </style>
</head>
<body>
<div class="container">
    <a href="/wallet.php" class="nav-back">← Back to Wallet</a>
    <div class="page-header">
        <h1>🏆 NFT Trophies</h1>
        <p>Earn achievements. Collect trophies. Mint them on Solana.</p>
    </div>

    <div class="stats-grid" id="trophyStats">
        <div class="stat-card"><div class="stat-val" id="statEarned">—</div><div class="stat-label">Trophies Earned</div></div>
        <div class="stat-card"><div class="stat-val" id="statMinted">—</div><div class="stat-label">Minted NFTs</div></div>
        <div class="stat-card"><div class="stat-val" id="statGSM">—</div><div class="stat-label">GSM Rewards</div></div>
        <div class="stat-card"><div class="stat-val" id="statRank">—</div><div class="stat-label">Your Rank</div></div>
    </div>

    <div class="tabs">
        <div class="tab active" onclick="switchTab('available')">Available</div>
        <div class="tab" onclick="switchTab('my')">My Trophies</div>
        <div class="tab" onclick="switchTab('leaderboard')">Leaderboard</div>
    </div>

    <div class="trophy-grid" id="trophyGrid"></div>
</div>

<div class="modal-overlay" id="detailModal">
    <div class="modal" id="detailContent"></div>
</div>

<script>
const API = '/api/nft-trophies.php';
let currentTab = 'available';

function getCsrf() { const m = document.cookie.match(/XSRF-TOKEN=([^;]+)/); return m ? decodeURIComponent(m[1]) : ''; }

async function api(action, body) {
    const opts = body ? { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-Token':getCsrf()}, credentials:'same-origin', body:JSON.stringify(body) } : { credentials:'same-origin' };
    const r = await fetch(API+'?action='+action, opts);
    return r.json();
}

function switchTab(tab) {
    currentTab = tab;
    document.querySelectorAll('.tab').forEach((t,i) => t.classList.toggle('active', ['available','my','leaderboard'][i]===tab));
    loadTab();
}

async function loadTab() {
    const grid = document.getElementById('trophyGrid');
    try {
        if (currentTab === 'available') {
            const d = await api('available');
            const trophies = d.trophies || d.data || [];
            if (!trophies.length) { grid.innerHTML = '<div class="empty">No trophies available yet</div>'; return; }
            grid.innerHTML = trophies.map(t => trophyCard(t, false)).join('');
        } else if (currentTab === 'my') {
            const d = await api('my_trophies');
            const trophies = d.trophies || d.data || [];
            if (!trophies.length) { grid.innerHTML = '<div class="empty">No trophies earned yet. Keep playing!</div>'; return; }
            grid.innerHTML = trophies.map(t => trophyCard(t, true)).join('');
        } else if (currentTab === 'leaderboard') {
            const d = await api('leaderboard');
            const leaders = d.leaderboard || d.data || [];
            grid.innerHTML = leaders.length ? '<div style="grid-column:1/-1">' + leaders.map((l,i) => `<div class="trophy-card" style="text-align:left;display:flex;align-items:center;gap:1rem;padding:1rem;margin-bottom:.5rem"><div style="font-size:1.5rem;font-weight:800;color:${i<3?'var(--gold)':'var(--dim)'};">#${i+1}</div><div><div style="font-weight:700">${esc(l.name||l.firstname||'Player')}</div><div style="font-size:.8rem;color:var(--dim)">${l.total_trophies||l.count||0} trophies · ${l.total_gsm_earned||0} GSM earned</div></div></div>`).join('') + '</div>' : '<div class="empty">No data yet</div>';
        }
    } catch(e) { grid.innerHTML = '<div class="empty">Error loading trophies</div>'; }
}

function trophyCard(t, owned) {
    const rarityMap = {common:'rarity-common',rare:'rarity-rare',epic:'rarity-epic',legendary:'rarity-legendary'};
    const rc = rarityMap[t.rarity] || 'rarity-common';
    const icon = t.icon || t.icon_url || '🏆';
    return `<div class="trophy-card ${!owned && !t.earned ? 'trophy-locked' : ''}" onclick="showDetail(${t.id||0})">
        ${owned ? '<div class="trophy-earned">✓ Earned</div>' : ''}
        <div class="trophy-icon">${esc(icon)}</div>
        <div class="trophy-name">${esc(t.name||t.title)}</div>
        <div class="trophy-desc">${esc(t.description||'')}</div>
        <div class="trophy-rarity ${rc}">${t.rarity||'common'}</div>
        <div class="trophy-game">${t.game_type ? '🎮 ' + t.game_type : ''} ${t.gsm_reward ? '· 💰 ' + t.gsm_reward + ' GSM' : ''}</div>
        ${owned && !t.mint_address ? '<button class="btn btn-mint" onclick="event.stopPropagation();mintTrophy('+t.id+')">Mint NFT</button>' : ''}
        ${owned && t.mint_address ? '<div style="font-size:.7rem;color:var(--green);margin-top:.5rem">✓ Minted</div>' : ''}
    </div>`;
}

async function showDetail(id) {
    if (!id) return;
    const d = await api('trophy_detail&id=' + id);
    const t = d.trophy || d.data || {};
    document.getElementById('detailContent').innerHTML = `
        <div style="text-align:center">
            <div style="font-size:4rem">${esc(t.icon||t.icon_url||'🏆')}</div>
            <h2>${esc(t.name||t.title)}</h2>
            <p style="color:var(--dim)">${esc(t.description||'')}</p>
            <div style="margin:1rem 0"><span class="trophy-rarity ${t.rarity==='legendary'?'rarity-legendary':t.rarity==='epic'?'rarity-epic':t.rarity==='rare'?'rarity-rare':'rarity-common'}">${t.rarity||'common'}</span></div>
            <div style="font-size:.85rem;color:var(--dim)">
                ${t.game_type ? '<p>Game: '+t.game_type+'</p>' : ''}
                ${t.criteria ? '<p>Criteria: '+esc(t.criteria)+'</p>' : ''}
                ${t.gsm_reward ? '<p>Reward: '+t.gsm_reward+' GSM</p>' : ''}
                ${t.earned_count !== undefined ? '<p>Earned by: '+t.earned_count+' players</p>' : ''}
                ${t.mint_address ? '<p style="color:var(--green)">Minted: '+t.mint_address.substring(0,12)+'...</p>' : ''}
            </div>
            <button class="btn" style="background:var(--surface2);color:var(--dim);margin-top:1rem" onclick="closeDetail()">Close</button>
        </div>`;
    document.getElementById('detailModal').classList.add('active');
}

function closeDetail() { document.getElementById('detailModal').classList.remove('active'); }
document.getElementById('detailModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeDetail(); });

async function mintTrophy(id) {
    if (!confirm('Mint this trophy as an NFT on Solana?')) return;
    const d = await api('mint', { trophy_id: id });
    if (d.success) { alert('Trophy minted! ' + (d.mint_address || '')); loadTab(); }
    else { alert(d.error || 'Minting failed'); }
}

async function loadStats() {
    try {
        const d = await api('stats');
        if (d.success || d.data) {
            const s = d.stats || d.data || {};
            document.getElementById('statEarned').textContent = s.total_earned || s.earned || 0;
            document.getElementById('statMinted').textContent = s.total_minted || s.minted || 0;
            document.getElementById('statGSM').textContent = (s.total_gsm || s.gsm_earned || 0) + ' GSM';
            document.getElementById('statRank').textContent = s.rank ? '#' + s.rank : '—';
        }
    } catch(e) {}
}

function esc(s) { const d = document.createElement('div'); d.textContent = s||''; return d.innerHTML; }

loadStats();
loadTab();
</script>
</body>
</html>
