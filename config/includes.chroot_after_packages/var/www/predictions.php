<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$loggedIn = !empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Predictions Market — Bet on Outcomes with GSM</title>
    <meta name="description" content="Stake GSM on binary outcome prediction markets. Bet on events, see live odds shift, and win from the pool.">
    <link rel="stylesheet" href="/assets/css/fonts.css">
    <link rel="icon" href="/brand/logo.png" type="image/png">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        :root{--bg:#030308;--surface:#0c0c1a;--surface2:#111126;--border:rgba(100,140,255,0.08);--glow:rgba(100,160,255,0.12);--text:#d8dce8;--dim:#5a6488;--accent:#6c5ce7;--accent2:#a29bfe;--green:#00b894;--red:#ff6b6b;--gold:#ffd700;--cyan:#00cec9}
        body{font-family:'Inter',system-ui,sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
        a{color:var(--accent2);text-decoration:none}
        .container{max-width:1100px;margin:0 auto;padding:2rem 1.5rem}
        .page-header{text-align:center;margin-bottom:2rem}
        .page-header h1{font-size:2rem;font-weight:800;background:linear-gradient(135deg,var(--cyan),var(--accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
        .page-header p{color:var(--dim);margin-top:.5rem}
        .tabs{display:flex;gap:.5rem;justify-content:center;margin-bottom:2rem;flex-wrap:wrap}
        .tab{padding:.6rem 1.2rem;border-radius:10px;background:var(--surface);border:1px solid var(--border);color:var(--dim);cursor:pointer;font-size:.85rem;font-weight:600;transition:.2s}
        .tab.active,.tab:hover{background:var(--accent);color:#fff;border-color:var(--accent)}
        .market-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:1rem}
        .market-card{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:1.5rem;transition:.3s}
        .market-card:hover{border-color:var(--cyan)}
        .market-title{font-size:1rem;font-weight:700;margin-bottom:.5rem;line-height:1.4}
        .market-category{font-size:.7rem;font-weight:700;text-transform:uppercase;color:var(--cyan);margin-bottom:.5rem}
        .market-meta{font-size:.8rem;color:var(--dim);margin-bottom:.75rem;display:flex;gap:.75rem;flex-wrap:wrap}
        .odds-bar{display:flex;height:32px;border-radius:8px;overflow:hidden;margin-bottom:.5rem;font-size:.75rem;font-weight:700}
        .odds-yes{background:rgba(0,184,148,0.3);color:var(--green);display:flex;align-items:center;justify-content:center;min-width:30px;transition:.3s}
        .odds-no{background:rgba(255,107,107,0.3);color:var(--red);display:flex;align-items:center;justify-content:center;min-width:30px;transition:.3s}
        .odds-labels{display:flex;justify-content:space-between;font-size:.75rem;color:var(--dim);margin-bottom:.75rem}
        .bet-actions{display:flex;gap:.5rem}
        .btn{padding:.5rem 1rem;border-radius:8px;border:none;cursor:pointer;font-weight:700;font-size:.8rem;transition:.2s;flex:1}
        .btn-yes{background:rgba(0,184,148,0.15);color:var(--green);border:1px solid rgba(0,184,148,0.3)}
        .btn-yes:hover{background:rgba(0,184,148,0.3)}
        .btn-no{background:rgba(255,107,107,0.15);color:var(--red);border:1px solid rgba(255,107,107,0.3)}
        .btn-no:hover{background:rgba(255,107,107,0.3)}
        .btn-primary{background:var(--accent);color:#fff;border:1px solid var(--accent)}
        .pool-info{display:flex;gap:1rem;font-size:.8rem;color:var(--dim);margin-top:.5rem}
        .badge{display:inline-block;padding:.15rem .4rem;border-radius:4px;font-size:.65rem;font-weight:700;text-transform:uppercase}
        .badge-active{background:rgba(0,184,148,0.15);color:var(--green)}
        .badge-resolved{background:rgba(108,92,231,0.15);color:var(--accent2)}
        .badge-closed{background:rgba(255,107,107,0.15);color:var(--red)}
        .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:2rem}
        .stat-card{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:1rem;text-align:center}
        .stat-val{font-size:1.5rem;font-weight:800;color:var(--cyan)}
        .stat-label{font-size:.75rem;color:var(--dim);margin-top:.25rem}
        .bet-card{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:1rem;margin-bottom:.5rem}
        .empty{text-align:center;padding:3rem;color:var(--dim)}
        .nav-back{display:inline-flex;align-items:center;gap:.5rem;color:var(--dim);font-size:.85rem;margin-bottom:1rem}
        .nav-back:hover{color:var(--text)}
        .modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:100;justify-content:center;align-items:center}
        .modal-overlay.active{display:flex}
        .modal{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:2rem;max-width:500px;width:90%}
        @media(max-width:600px){.container{padding:1rem}.market-grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
<div class="container">
    <a href="/wallet.php" class="nav-back">← Back to Wallet</a>
    <div class="page-header">
        <h1>🔮 Predictions Market</h1>
        <p>Stake GSM on real outcomes. Odds shift in real time based on the pool.</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card"><div class="stat-val" id="statMarkets">—</div><div class="stat-label">Active Markets</div></div>
        <div class="stat-card"><div class="stat-val" id="statPool">—</div><div class="stat-label">Total Pool (GSM)</div></div>
        <div class="stat-card"><div class="stat-val" id="statBets">—</div><div class="stat-label">Total Bets</div></div>
        <div class="stat-card"><div class="stat-val" id="statWon">—</div><div class="stat-label">Your Winnings</div></div>
    </div>

    <div class="tabs">
        <div class="tab active" onclick="switchTab('active')">🔥 Active</div>
        <div class="tab" onclick="switchTab('resolved')">✅ Resolved</div>
        <div class="tab" onclick="switchTab('my_bets')">📊 My Bets</div>
        <div class="tab" onclick="switchTab('leaderboard')">🏆 Top Predictors</div>
    </div>

    <div class="market-grid" id="marketGrid"></div>
</div>

<div class="modal-overlay" id="betModal">
    <div class="modal" id="betContent"></div>
</div>

<script>
const API = '/api/predictions.php';
let currentTab = 'active';

function getCsrf() { const m = document.cookie.match(/XSRF-TOKEN=([^;]+)/); return m ? decodeURIComponent(m[1]) : ''; }

async function api(action, body) {
    const opts = body ? { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-Token':getCsrf()}, credentials:'same-origin', body:JSON.stringify(body) } : { credentials:'same-origin' };
    return (await fetch(API+'?action='+action, opts)).json();
}

function switchTab(tab) {
    currentTab = tab;
    document.querySelectorAll('.tab').forEach((t,i) => t.classList.toggle('active', ['active','resolved','my_bets','leaderboard'][i]===tab));
    loadTab();
}

async function loadTab() {
    const grid = document.getElementById('marketGrid');
    try {
        if (currentTab === 'my_bets') {
            const d = await api('my_bets');
            const bets = d.bets || [];
            if (!bets.length) { grid.innerHTML = '<div class="empty">No bets yet. Start predicting!</div>'; return; }
            grid.innerHTML = bets.map(b => `<div class="bet-card">
                <div class="market-title">${esc(b.title)}</div>
                <div class="market-meta">
                    <span class="badge ${b.status==='won'?'badge-active':b.status==='lost'?'badge-closed':'badge-resolved'}">${b.status}</span>
                    <span>Side: <strong>${b.side.toUpperCase()}</strong></span>
                    <span>Staked: ${parseFloat(b.amount_gsm).toFixed(2)} GSM</span>
                    <span>Odds: ${parseFloat(b.odds_at_time).toFixed(2)}x</span>
                    ${b.payout_gsm && parseFloat(b.payout_gsm)>0 ? '<span style="color:var(--green)">Won: '+parseFloat(b.payout_gsm).toFixed(2)+' GSM</span>' : ''}
                </div>
            </div>`).join('');
            return;
        }
        if (currentTab === 'leaderboard') {
            const d = await api('leaderboard&limit=30');
            const leaders = d.leaderboard || [];
            if (!leaders.length) { grid.innerHTML = '<div class="empty">No prediction data yet</div>'; return; }
            grid.innerHTML = '<div style="grid-column:1/-1"><table style="width:100%;border-collapse:collapse"><thead><tr><th style="text-align:left;padding:.5rem;color:var(--dim);font-size:.75rem">RANK</th><th style="text-align:left;padding:.5rem;color:var(--dim);font-size:.75rem">PREDICTOR</th><th style="text-align:left;padding:.5rem;color:var(--dim);font-size:.75rem">WINS</th><th style="text-align:left;padding:.5rem;color:var(--dim);font-size:.75rem">ACCURACY</th><th style="text-align:left;padding:.5rem;color:var(--dim);font-size:.75rem">GSM WON</th></tr></thead><tbody>' + leaders.map((l,i) => `<tr style="border-bottom:1px solid var(--border)"><td style="padding:.5rem;font-weight:800;color:${i<3?'var(--gold)':'var(--dim)'}">#${i+1}</td><td style="padding:.5rem;font-weight:600">${esc(l.name||'Player')}</td><td style="padding:.5rem">${l.wins||0}</td><td style="padding:.5rem">${l.accuracy||0}%</td><td style="padding:.5rem;color:var(--green);font-weight:700">${l.total_won ? parseFloat(l.total_won).toFixed(2) : '0'}</td></tr>`).join('') + '</tbody></table></div>';
            return;
        }

        const status = currentTab === 'resolved' ? 'resolved' : 'active';
        const d = await api('list_markets&status=' + status + '&limit=30');
        const markets = d.markets || [];
        if (!markets.length) { grid.innerHTML = '<div class="empty">No ' + status + ' markets</div>'; return; }

        grid.innerHTML = markets.map(m => {
            const odds = m.odds || {yes_pct:50,no_pct:50,yes:2,no:2};
            const badge = m.status === 'active' ? 'badge-active' : m.status === 'resolved' ? 'badge-resolved' : 'badge-closed';
            return `<div class="market-card">
                <div class="market-category">${esc(m.category)}</div>
                <div class="market-title">${esc(m.title)}</div>
                <div class="market-meta">
                    <span class="badge ${badge}">${m.status}</span>
                    <span>${m.total_bettors||0} bettors</span>
                    ${m.closes_at ? '<span>Closes: '+new Date(m.closes_at).toLocaleDateString()+'</span>' : ''}
                </div>
                <div class="odds-bar">
                    <div class="odds-yes" style="width:${odds.yes_pct}%">${m.outcome_yes_label||'Yes'} ${odds.yes_pct}%</div>
                    <div class="odds-no" style="width:${odds.no_pct}%">${m.outcome_no_label||'No'} ${odds.no_pct}%</div>
                </div>
                <div class="odds-labels">
                    <span>${odds.yes.toFixed(2)}x payout</span>
                    <span>${odds.no.toFixed(2)}x payout</span>
                </div>
                <div class="pool-info">
                    <span>Yes pool: ${parseFloat(m.pool_yes||0).toLocaleString()} GSM</span>
                    <span>No pool: ${parseFloat(m.pool_no||0).toLocaleString()} GSM</span>
                </div>
                ${m.status === 'active' ? `<div class="bet-actions" style="margin-top:.75rem">
                    <button class="btn btn-yes" onclick="openBet(${m.id},'yes','${esc(m.title)}',${odds.yes.toFixed(2)})">Bet ${m.outcome_yes_label||'Yes'}</button>
                    <button class="btn btn-no" onclick="openBet(${m.id},'no','${esc(m.title)}',${odds.no.toFixed(2)})">Bet ${m.outcome_no_label||'No'}</button>
                </div>` : ''}
                ${m.status === 'resolved' ? `<div style="margin-top:.75rem;padding:.5rem;border-radius:8px;background:rgba(0,184,148,0.1);text-align:center;font-weight:700;color:var(--green)">Resolved: ${m.resolved_outcome === 'yes' ? (m.outcome_yes_label||'Yes') : m.resolved_outcome === 'no' ? (m.outcome_no_label||'No') : 'Cancelled'}</div>` : ''}
            </div>`;
        }).join('');
    } catch(e) { grid.innerHTML = '<div class="empty">Error loading markets</div>'; }
}

function openBet(marketId, side, title, odds) {
    document.getElementById('betContent').innerHTML = `
        <h3 style="margin-bottom:.5rem">${esc(title)}</h3>
        <p style="color:var(--dim);margin-bottom:1rem">Betting: <strong style="color:${side==='yes'?'var(--green)':'var(--red)'}">${side.toUpperCase()}</strong> at ${odds}x</p>
        <div style="margin-bottom:1rem">
            <label style="font-size:.85rem;color:var(--dim);display:block;margin-bottom:.3rem">Amount (GSM)</label>
            <input id="betAmount" type="number" min="1" max="10000" value="10" style="width:100%;padding:.6rem;background:var(--surface2);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:1rem">
            <div style="display:flex;gap:.3rem;margin-top:.5rem">${[5,10,50,100,500].map(a => `<button onclick="document.getElementById('betAmount').value=${a}" style="flex:1;padding:.3rem;border-radius:6px;background:var(--surface2);border:1px solid var(--border);color:var(--dim);cursor:pointer;font-size:.75rem">${a}</button>`).join('')}</div>
            <p id="betPayout" style="color:var(--green);font-size:.85rem;margin-top:.5rem">Potential payout: ${(10*odds).toFixed(2)} GSM</p>
        </div>
        <div style="display:flex;gap:.5rem">
            <button class="btn btn-primary" style="flex:1" onclick="placeBet(${marketId},'${side}')">Confirm Bet</button>
            <button class="btn" style="flex:1;background:var(--surface2);color:var(--dim)" onclick="closeBet()">Cancel</button>
        </div>`;
    document.getElementById('betAmount').oninput = function() {
        document.getElementById('betPayout').textContent = 'Potential payout: ' + (parseFloat(this.value||0) * odds).toFixed(2) + ' GSM';
    };
    document.getElementById('betModal').classList.add('active');
}

function closeBet() { document.getElementById('betModal').classList.remove('active'); }
document.getElementById('betModal').addEventListener('click', e => { if (e.target===e.currentTarget) closeBet(); });

async function placeBet(marketId, side) {
    const amount = parseFloat(document.getElementById('betAmount').value);
    if (!amount || amount < 1) { alert('Enter a valid amount'); return; }
    const d = await api('place_bet', { market_id: marketId, side, amount });
    if (d.success) { alert('Bet placed! Odds locked at ' + (d.odds_locked||0).toFixed(2) + 'x'); closeBet(); loadTab(); loadStats(); }
    else { alert(d.error || 'Bet failed'); }
}

async function loadStats() {
    try {
        const [markets, bets] = await Promise.all([
            api('list_markets&status=active&limit=1'),
            api('my_bets')
        ]);
        const activeCount = (markets.markets || []).length;
        const totalPool = (markets.markets || []).reduce((s, m) => s + parseFloat(m.pool_yes||0) + parseFloat(m.pool_no||0), 0);
        const myBets = bets.bets || [];
        const totalWon = myBets.reduce((s, b) => s + (b.status === 'won' ? parseFloat(b.payout_gsm||0) : 0), 0);
        document.getElementById('statMarkets').textContent = activeCount;
        document.getElementById('statPool').textContent = totalPool.toLocaleString();
        document.getElementById('statBets').textContent = myBets.length;
        document.getElementById('statWon').textContent = totalWon > 0 ? totalWon.toFixed(2) : '—';
    } catch(e) {}
}

function esc(s) { const d = document.createElement('div'); d.textContent = s||''; return d.innerHTML; }

loadStats();
loadTab();
</script>
</body>
</html>
