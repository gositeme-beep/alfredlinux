<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$loggedIn = !empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game Leaderboard — Cross-Game Rankings</title>
    <meta name="description" content="See who's dominating across all games. Rankings by wins, earnings, ELO, and prediction accuracy.">
    <link rel="stylesheet" href="/assets/css/fonts.css">
    <link rel="icon" href="/brand/logo.png" type="image/png">
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        :root{--bg:#030308;--surface:#0c0c1a;--surface2:#111126;--border:rgba(100,140,255,0.08);--glow:rgba(100,160,255,0.12);--text:#d8dce8;--dim:#5a6488;--accent:#6c5ce7;--accent2:#a29bfe;--green:#00b894;--red:#ff6b6b;--gold:#ffd700}
        body{font-family:'Inter',system-ui,sans-serif;background:var(--bg);color:var(--text);min-height:100vh}
        a{color:var(--accent2);text-decoration:none}
        .container{max-width:1000px;margin:0 auto;padding:2rem 1.5rem}
        .page-header{text-align:center;margin-bottom:2rem}
        .page-header h1{font-size:2rem;font-weight:800;background:linear-gradient(135deg,var(--gold),#ff8c00);-webkit-background-clip:text;-webkit-text-fill-color:transparent}
        .page-header p{color:var(--dim);margin-top:.5rem}
        .tabs{display:flex;gap:.5rem;justify-content:center;margin-bottom:1.5rem;flex-wrap:wrap}
        .tab{padding:.6rem 1.2rem;border-radius:10px;background:var(--surface);border:1px solid var(--border);color:var(--dim);cursor:pointer;font-size:.85rem;font-weight:600;transition:.2s}
        .tab.active,.tab:hover{background:var(--accent);color:#fff;border-color:var(--accent)}
        .filters{display:flex;gap:.5rem;justify-content:center;margin-bottom:2rem;flex-wrap:wrap}
        .filter-btn{padding:.4rem .8rem;border-radius:8px;background:var(--surface2);border:1px solid var(--border);color:var(--dim);cursor:pointer;font-size:.75rem;font-weight:600}
        .filter-btn.active{color:var(--accent2);border-color:var(--accent)}
        .lb-table{width:100%;border-collapse:collapse}
        .lb-table th{text-align:left;padding:.75rem 1rem;font-size:.75rem;text-transform:uppercase;color:var(--dim);border-bottom:1px solid var(--border)}
        .lb-table td{padding:.75rem 1rem;border-bottom:1px solid var(--border);font-size:.9rem}
        .lb-table tr:hover{background:var(--surface)}
        .rank-cell{font-weight:800;font-size:1.1rem;width:50px}
        .rank-1{color:var(--gold)}
        .rank-2{color:#c0c0c0}
        .rank-3{color:#cd7f32}
        .player-name{font-weight:600}
        .stat-highlight{color:var(--green);font-weight:700}
        .podium{display:flex;gap:1rem;justify-content:center;align-items:flex-end;margin-bottom:2rem}
        .podium-slot{text-align:center;padding:1rem;border-radius:12px;background:var(--surface);border:1px solid var(--border)}
        .podium-1{order:2;padding-bottom:2rem;border-color:var(--gold);box-shadow:0 0 20px rgba(255,215,0,0.1)}
        .podium-2{order:1;padding-bottom:1rem}
        .podium-3{order:3}
        .podium-rank{font-size:2rem;margin-bottom:.5rem}
        .podium-name{font-weight:700;font-size:.9rem}
        .podium-stat{color:var(--dim);font-size:.8rem}
        .empty{text-align:center;padding:3rem;color:var(--dim)}
        .nav-back{display:inline-flex;align-items:center;gap:.5rem;color:var(--dim);font-size:.85rem;margin-bottom:1rem}
        .nav-back:hover{color:var(--text)}
        @media(max-width:600px){.container{padding:1rem}.podium{flex-direction:column;align-items:center}.podium-1,.podium-2,.podium-3{order:unset}.lb-table{font-size:.8rem}.lb-table td,.lb-table th{padding:.5rem}}
    </style>
</head>
<body>
<div class="container">
    <a href="/wallet.php" class="nav-back">← Back to Wallet</a>
    <div class="page-header">
        <h1>🏆 Game Leaderboard</h1>
        <p>Cross-game rankings — who's on top?</p>
    </div>

    <div class="tabs">
        <div class="tab active" onclick="switchBoard('earnings')">💰 Earnings</div>
        <div class="tab" onclick="switchBoard('wins')">🎯 Wins</div>
        <div class="tab" onclick="switchBoard('streak')">🔥 Streaks</div>
        <div class="tab" onclick="switchBoard('predictions')">🔮 Predictions</div>
    </div>

    <div class="filters" id="gameFilters">
        <div class="filter-btn active" onclick="setGame(null)">All Games</div>
        <div class="filter-btn" onclick="setGame('chess')">♟️ Chess</div>
        <div class="filter-btn" onclick="setGame('checkers')">🔴 Checkers</div>
        <div class="filter-btn" onclick="setGame('pool')">🎱 Pool</div>
        <div class="filter-btn" onclick="setGame('backgammon')">🎲 Backgammon</div>
        <div class="filter-btn" onclick="setGame('poker')">♠️ Poker</div>
        <div class="filter-btn" onclick="setGame('racing')">🏎️ Racing</div>
    </div>

    <div id="podium"></div>
    <div id="leaderboard"></div>
</div>

<script>
const BETTING_API = '/api/universal-betting.php';
const PRED_API = '/api/predictions.php';
let currentBoard = 'earnings';
let currentGame = null;

async function fetchAPI(url) { return (await fetch(url, { credentials: 'same-origin' })).json(); }

function switchBoard(board) {
    currentBoard = board;
    document.querySelectorAll('.tab').forEach((t,i) => t.classList.toggle('active', ['earnings','wins','streak','predictions'][i]===board));
    document.getElementById('gameFilters').style.display = board === 'predictions' ? 'none' : 'flex';
    loadBoard();
}

function setGame(game) {
    currentGame = game;
    document.querySelectorAll('.filter-btn').forEach(b => {
        const txt = b.textContent.toLowerCase();
        b.classList.toggle('active', (game === null && txt.includes('all')) || (game && txt.includes(game)));
    });
    loadBoard();
}

async function loadBoard() {
    const el = document.getElementById('leaderboard');
    const podiumEl = document.getElementById('podium');

    if (currentBoard === 'predictions') {
        try {
            const d = await fetchAPI(PRED_API + '?action=leaderboard&limit=50');
            const leaders = d.leaderboard || [];
            if (!leaders.length) { podiumEl.innerHTML = ''; el.innerHTML = '<div class="empty">No prediction data yet</div>'; return; }
            renderPodium(podiumEl, leaders, l => l.total_won ? parseFloat(l.total_won).toFixed(1)+' GSM' : (l.wins||0)+' wins', l => (l.accuracy||0)+'% accuracy');
            el.innerHTML = renderTable(leaders, ['Rank','Predictor','Wins','Bets','Accuracy','GSM Won'], l => [
                l.name||'Player', l.wins||0, l.total_bets||0, (l.accuracy||0)+'%',
                l.total_won ? parseFloat(l.total_won).toFixed(2) : '0'
            ]);
        } catch(e) { el.innerHTML = '<div class="empty">Error loading data</div>'; }
        return;
    }

    try {
        const gameParam = currentGame ? '&game_type='+currentGame : '';
        const d = await fetchAPI(BETTING_API + '?action=leaderboard' + gameParam + '&limit=50');
        const leaders = d.leaderboard || [];
        if (!leaders.length) { podiumEl.innerHTML = ''; el.innerHTML = '<div class="empty">No data yet'+(currentGame?' for '+currentGame:'')+'</div>'; return; }

        if (currentBoard === 'earnings') {
            leaders.sort((a,b) => parseFloat(b.total_won||0)-parseFloat(a.total_won||0));
            renderPodium(podiumEl, leaders, l => parseFloat(l.total_won||0).toFixed(1)+' GSM', l => (l.win_rate||0)+'% win rate');
            el.innerHTML = renderTable(leaders, ['Rank','Player','Wins','Games','Win Rate','GSM Won','Streak'], l => [
                l.name||'Player', l.total_wins||0, l.total_games||0, (l.win_rate||0)+'%',
                parseFloat(l.total_won||0).toFixed(2), l.best_streak||0
            ]);
        } else if (currentBoard === 'wins') {
            leaders.sort((a,b) => (b.total_wins||0)-(a.total_wins||0));
            renderPodium(podiumEl, leaders, l => (l.total_wins||0)+' wins', l => (l.total_games||0)+' games');
            el.innerHTML = renderTable(leaders, ['Rank','Player','Wins','Losses','Games','Win Rate','GSM Won'], l => [
                l.name||'Player', l.total_wins||0, (l.total_games||0)-(l.total_wins||0),
                l.total_games||0, (l.win_rate||0)+'%', parseFloat(l.total_won||0).toFixed(2)
            ]);
        } else if (currentBoard === 'streak') {
            leaders.sort((a,b) => (b.best_streak||0)-(a.best_streak||0));
            renderPodium(podiumEl, leaders, l => '🔥 '+(l.best_streak||0), l => (l.total_wins||0)+' total wins');
            el.innerHTML = renderTable(leaders, ['Rank','Player','Best Streak','Wins','Games','Win Rate'], l => [
                l.name||'Player', l.best_streak||0, l.total_wins||0, l.total_games||0, (l.win_rate||0)+'%'
            ]);
        }
    } catch(e) { el.innerHTML = '<div class="empty">Error loading leaderboard</div>'; }
}

function renderPodium(el, leaders, mainStat, subStat) {
    if (leaders.length < 3) { el.innerHTML = ''; return; }
    const medals = ['🥇','🥈','🥉'];
    el.innerHTML = '<div class="podium">' + [0,1,2].map(i =>
        `<div class="podium-slot podium-${i+1}"><div class="podium-rank">${medals[i]}</div><div class="podium-name">${esc(leaders[i].name||'Player')}</div><div class="podium-stat">${mainStat(leaders[i])}</div><div class="podium-stat">${subStat(leaders[i])}</div></div>`
    ).join('') + '</div>';
}

function renderTable(leaders, headers, rowFn) {
    return `<table class="lb-table"><thead><tr>${headers.map(h=>'<th>'+h+'</th>').join('')}</tr></thead><tbody>${leaders.map((l,i) => {
        const cols = rowFn(l);
        const rc = i<3 ? ' rank-'+(i+1) : '';
        return `<tr><td class="rank-cell${rc}">#${i+1}</td>${cols.map((c,ci)=>`<td${ci===0?' class="player-name"':''}${ci===cols.length-1?' class="stat-highlight"':''}>${c}</td>`).join('')}</tr>`;
    }).join('')}</tbody></table>`;
}

function esc(s) { const d = document.createElement('div'); d.textContent = s||''; return d.innerHTML; }

loadBoard();
</script>
</body>
</html>
