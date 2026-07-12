<?php
/**
 * GoSiteMe Crypto Command Center
 * Commander-only real-time crypto trading dashboard
 * Route: /docs/crypto-command or /docs/crypto-command.php
 */
session_start();

// Commander auth check (client_id 33)
$isCommander = false;
if (isset($_SESSION['uid']) && $_SESSION['uid'] == 33) {
    $isCommander = true;
} elseif (isset($_GET['client_id']) && $_GET['client_id'] == '33') {
    $isCommander = true;
}

if (!$isCommander) {
    http_response_code(403);
    echo '<h1>Commander Access Only</h1>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crypto Command Center — GoSiteMe</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;600;700&family=Inter:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0a0a14;
            --surface: #12121e;
            --surface2: #1a1a2e;
            --border: #2a2a3e;
            --text: #e0e0e8;
            --muted: #8888aa;
            --purple: #7D00FF;
            --cyan: #00D4FF;
            --green: #00ff88;
            --red: #ff4466;
            --orange: #ff8844;
            --yellow: #ffcc00;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background:var(--bg); color:var(--text); font-family:'Inter',sans-serif; }
        
        .header {
            background: linear-gradient(135deg, rgba(125,0,255,0.15), rgba(0,212,255,0.1));
            border-bottom: 1px solid var(--border);
            padding: 1.5rem 2rem;
            display: flex; justify-content: space-between; align-items: center;
        }
        .header h1 { font-family:'Space Grotesk',sans-serif; font-size:1.8rem; }
        .header h1 span { background: linear-gradient(135deg, var(--purple), var(--cyan)); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
        .header .status { display:flex; gap:1rem; align-items:center; }
        .status-dot { width:8px; height:8px; border-radius:50%; display:inline-block; }
        .status-dot.live { background:var(--green); box-shadow:0 0 8px var(--green); }
        .status-dot.off { background:var(--red); }
        
        .grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(300px,1fr)); gap:1.5rem; padding:2rem; }
        .card { background:var(--surface); border:1px solid var(--border); border-radius:12px; padding:1.5rem; }
        .card h3 { font-family:'Space Grotesk',sans-serif; font-size:1rem; color:var(--muted); margin-bottom:1rem; text-transform:uppercase; letter-spacing:0.05em; }
        .card.wide { grid-column: span 2; }
        .card.full { grid-column: 1/-1; }
        
        .big-number { font-family:'JetBrains Mono',monospace; font-size:2rem; font-weight:700; }
        .big-number.green { color:var(--green); }
        .big-number.red { color:var(--red); }
        .change { font-size:0.9rem; padding:2px 8px; border-radius:4px; font-family:'JetBrains Mono',monospace; }
        .change.up { background:rgba(0,255,136,0.15); color:var(--green); }
        .change.down { background:rgba(255,68,102,0.15); color:var(--red); }
        
        .pair-row { display:flex; justify-content:space-between; align-items:center; padding:0.8rem 0; border-bottom:1px solid var(--border); }
        .pair-row:last-child { border:none; }
        .pair-name { font-weight:600; }
        .pair-price { font-family:'JetBrains Mono',monospace; }
        
        .signal-badge { padding:3px 10px; border-radius:6px; font-size:0.8rem; font-weight:600; text-transform:uppercase; }
        .signal-badge.buy { background:rgba(0,255,136,0.2); color:var(--green); }
        .signal-badge.sell { background:rgba(255,68,102,0.2); color:var(--red); }
        .signal-badge.hold { background:rgba(255,204,0,0.2); color:var(--yellow); }
        
        .score-bar { height:6px; border-radius:3px; background:var(--surface2); margin-top:6px; }
        .score-fill { height:100%; border-radius:3px; transition:width 0.5s; }
        
        .btn { padding:10px 20px; border:none; border-radius:8px; cursor:pointer; font-weight:600; font-size:0.9rem; transition:all 0.2s; }
        .btn-primary { background:linear-gradient(135deg, var(--purple), var(--cyan)); color:#fff; }
        .btn-danger { background:var(--red); color:#fff; }
        .btn-success { background:var(--green); color:#000; }
        .btn:hover { transform:translateY(-1px); box-shadow:0 4px 12px rgba(0,0,0,0.3); }
        
        .agents { display:flex; gap:1rem; margin-top:1rem; }
        .agent-card { flex:1; background:var(--surface2); border:1px solid var(--border); border-radius:10px; padding:1.2rem; text-align:center; cursor:pointer; transition:all 0.2s; }
        .agent-card:hover { border-color:var(--purple); transform:translateY(-2px); }
        .agent-card .icon { font-size:2rem; margin-bottom:0.5rem; }
        .agent-card h4 { font-family:'Space Grotesk',sans-serif; }
        .agent-card p { font-size:0.8rem; color:var(--muted); margin-top:0.3rem; }
        
        .trade-table { width:100%; border-collapse:collapse; }
        .trade-table th { text-align:left; color:var(--muted); font-size:0.8rem; padding:8px; border-bottom:1px solid var(--border); }
        .trade-table td { padding:8px; font-family:'JetBrains Mono',monospace; font-size:0.85rem; border-bottom:1px solid rgba(42,42,62,0.5); }
        
        .loading { color:var(--muted); font-style:italic; }
        .refresh-btn { background:none; border:1px solid var(--border); color:var(--muted); padding:6px 12px; border-radius:6px; cursor:pointer; font-size:0.8rem; }
        .refresh-btn:hover { border-color:var(--cyan); color:var(--cyan); }
        
        @media(max-width:768px) { .grid { grid-template-columns:1fr; } .card.wide,.card.full { grid-column:auto; } }
    </style>
</head>
<body>
    <div class="header">
        <h1>🔮 <span>Crypto Command Center</span></h1>
        <div class="status">
            <span class="status-dot" id="apiStatus"></span>
            <span id="apiLabel" style="font-size:0.85rem;color:var(--muted)">Connecting...</span>
            <button class="refresh-btn" onclick="loadDashboard()">↻ Refresh</button>
            <span style="font-size:0.8rem;color:var(--muted)" id="lastUpdate"></span>
        </div>
    </div>

    <div class="grid">
        <!-- BTC Card -->
        <div class="card">
            <h3>₿ Bitcoin</h3>
            <div class="big-number" id="btcPrice">...</div>
            <span class="change" id="btcChange">...</span>
        </div>

        <!-- ETH Card -->
        <div class="card">
            <h3>Ξ Ethereum</h3>
            <div class="big-number" id="ethPrice">...</div>
            <span class="change" id="ethChange">...</span>
        </div>

        <!-- Bot Status -->
        <div class="card">
            <h3>🤖 Trading Bot</h3>
            <div id="botStatus" style="margin-bottom:1rem">
                <span class="big-number" id="botLabel">...</span>
            </div>
            <div style="display:flex;gap:0.5rem">
                <button class="btn btn-success" onclick="controlBot('start')" id="btnStart">Start Bot</button>
                <button class="btn btn-danger" onclick="controlBot('stop')" id="btnStop">Stop Bot</button>
            </div>
        </div>

        <!-- AI Agents -->
        <div class="card full">
            <h3>🧠 Crypto AI Agents — Call to Talk</h3>
            <div class="agents">
                <div class="agent-card" onclick="callAgent('quant')">
                    <div class="icon">📊</div>
                    <h4>Quant</h4>
                    <p>Market Analysis • Signal Scanner • Technical Charts</p>
                    <small style="color:var(--cyan)">Call: (833) 467-4836 → Say "Quant"</small>
                </div>
                <div class="agent-card" onclick="callAgent('mint')">
                    <div class="icon">💰</div>
                    <h4>Mint</h4>
                    <p>Portfolio Manager • Risk Control • P&L Tracking</p>
                    <small style="color:var(--green)">Call: (833) 467-4836 → Say "Mint"</small>
                </div>
                <div class="agent-card" onclick="callAgent('alfred')">
                    <div class="icon">🎩</div>
                    <h4>Alfred</h4>
                    <p>Your Brother AI • Everything Else • Full Control</p>
                    <small style="color:var(--purple)">Call: (833) 467-4836</small>
                </div>
            </div>
        </div>

        <!-- Signal Scanner -->
        <div class="card wide">
            <h3>📡 Live Signal Scanner</h3>
            <div id="signalList"><span class="loading">Scanning pairs...</span></div>
            <button class="btn btn-primary" onclick="runScan()" style="margin-top:1rem">🔍 Run Full Scan</button>
        </div>

        <!-- Quick Analysis -->
        <div class="card">
            <h3>🔬 Quick Analysis</h3>
            <div style="display:flex;gap:0.5rem;margin-bottom:1rem">
                <select id="analyzePair" style="flex:1;padding:8px;background:var(--surface2);border:1px solid var(--border);color:var(--text);border-radius:6px;">
                    <option>BTC_USDT</option>
                    <option>ETH_USDT</option>
                    <option>XRP_USDT</option>
                    <option>DOGE_USDT</option>
                    <option>SOL_USDT</option>
                    <option>ADA_USDT</option>
                    <option>AVAX_USDT</option>
                    <option>DOT_USDT</option>
                    <option>LINK_USDT</option>
                    <option>LTC_USDT</option>
                    <option>SHIB_USDT</option>
                    <option>MATIC_USDT</option>
                </select>
                <button class="btn btn-primary" onclick="analyzePair()">Analyze</button>
            </div>
            <div id="analysisResult"><span class="loading">Select a pair...</span></div>
        </div>

        <!-- Recent Signals -->
        <div class="card wide">
            <h3>📋 Active Signals</h3>
            <div id="activeSignals"><span class="loading">Loading...</span></div>
        </div>

        <!-- Trade History -->
        <div class="card full">
            <h3>📜 Recent Trades</h3>
            <div id="tradeHistory"><span class="loading">Loading...</span></div>
        </div>
    </div>

    <script>
        const API = '/api/crypto-trading.php';

        async function api(action, params = {}) {
            const url = new URL(API, location.origin);
            url.searchParams.set('action', action);
            url.searchParams.set('client_id', '33');
            for (const [k, v] of Object.entries(params)) url.searchParams.set(k, v);
            const r = await fetch(url);
            return r.json();
        }

        function fmt(n, decimals = 2) {
            return new Intl.NumberFormat('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals }).format(n);
        }

        async function loadDashboard() {
            try {
                const d = await api('dashboard');
                
                // API Status
                const dot = document.getElementById('apiStatus');
                const label = document.getElementById('apiLabel');
                dot.className = 'status-dot ' + (d.api_status === 'connected' ? 'live' : 'off');
                label.textContent = d.api_status === 'connected' ? 'API Connected' : 'No API Keys';

                // BTC
                if (d.btc) {
                    document.getElementById('btcPrice').textContent = '$' + fmt(d.btc.price);
                    const btcChange = document.getElementById('btcChange');
                    btcChange.textContent = (d.btc.change_pct >= 0 ? '+' : '') + d.btc.change_pct + '%';
                    btcChange.className = 'change ' + (d.btc.change_pct >= 0 ? 'up' : 'down');
                }

                // ETH
                if (d.eth) {
                    document.getElementById('ethPrice').textContent = '$' + fmt(d.eth.price);
                    const ethChange = document.getElementById('ethChange');
                    ethChange.textContent = (d.eth.change_pct >= 0 ? '+' : '') + d.eth.change_pct + '%';
                    ethChange.className = 'change ' + (d.eth.change_pct >= 0 ? 'up' : 'down');
                }

                // Bot
                document.getElementById('botLabel').textContent = d.bot_active ? 'ACTIVE' : 'STOPPED';
                document.getElementById('botLabel').className = 'big-number ' + (d.bot_active ? 'green' : 'red');

                // Signals
                if (d.active_signals && d.active_signals.length > 0) {
                    document.getElementById('activeSignals').innerHTML = d.active_signals.map(s => `
                        <div class="pair-row">
                            <span class="pair-name">${s.pair}</span>
                            <span class="signal-badge ${s.signal_type}">${s.signal_type}</span>
                            <span style="color:var(--muted);font-size:0.8rem">${s.reason}</span>
                            <span class="pair-price">$${fmt(s.price_at_signal)}</span>
                        </div>
                    `).join('');
                } else {
                    document.getElementById('activeSignals').innerHTML = '<span class="loading">No active signals. Run a scan.</span>';
                }

                // Trades
                if (d.recent_trades && d.recent_trades.length > 0) {
                    document.getElementById('tradeHistory').innerHTML = `
                        <table class="trade-table">
                            <tr><th>Pair</th><th>Side</th><th>Amount</th><th>Price</th><th>Total</th><th>Status</th><th>Time</th></tr>
                            ${d.recent_trades.map(t => `
                                <tr>
                                    <td>${t.pair}</td>
                                    <td style="color:${t.side === 'buy' ? 'var(--green)' : 'var(--red)'}">${t.side.toUpperCase()}</td>
                                    <td>${t.amount}</td>
                                    <td>$${fmt(t.price)}</td>
                                    <td>$${fmt(t.total)}</td>
                                    <td>${t.status}</td>
                                    <td style="color:var(--muted)">${t.created_at}</td>
                                </tr>
                            `).join('')}
                        </table>`;
                } else {
                    document.getElementById('tradeHistory').innerHTML = '<span class="loading">No trades yet. Use Quant for analysis first.</span>';
                }

                document.getElementById('lastUpdate').textContent = 'Updated: ' + new Date().toLocaleTimeString();
            } catch (e) {
                console.error('Dashboard error:', e);
            }
        }

        async function runScan() {
            document.getElementById('signalList').innerHTML = '<span class="loading">🔍 Scanning 12 pairs... (this takes ~30 seconds)</span>';
            try {
                const d = await api('signals');
                if (d.signals) {
                    document.getElementById('signalList').innerHTML = d.signals.map(s => {
                        const color = s.score > 0 ? 'var(--green)' : s.score < 0 ? 'var(--red)' : 'var(--yellow)';
                        const pct = Math.min(100, (Math.abs(s.score) / 100) * 100);
                        return `
                            <div class="pair-row">
                                <span class="pair-name" style="min-width:100px">${s.pair.replace('_USDT','')}</span>
                                <span class="pair-price">$${fmt(s.price)}</span>
                                <span class="change ${s.change_24h && parseFloat(s.change_24h) >= 0 ? 'up' : 'down'}">${s.change_24h}</span>
                                <span style="color:var(--muted);font-size:0.8rem">RSI: ${s.rsi}</span>
                                <span class="signal-badge ${s.recommendation.includes('BUY') ? 'buy' : s.recommendation.includes('SELL') ? 'sell' : 'hold'}">${s.recommendation}</span>
                                <div style="flex:1;margin-left:1rem">
                                    <div class="score-bar"><div class="score-fill" style="width:${pct}%;background:${color}"></div></div>
                                </div>
                                <span style="font-family:'JetBrains Mono';min-width:40px;text-align:right;color:${color}">${s.score > 0 ? '+' : ''}${s.score}</span>
                            </div>`;
                    }).join('');
                }
            } catch (e) {
                document.getElementById('signalList').innerHTML = '<span style="color:var(--red)">Scan failed: ' + e.message + '</span>';
            }
        }

        async function analyzePair() {
            const pair = document.getElementById('analyzePair').value;
            document.getElementById('analysisResult').innerHTML = '<span class="loading">Analyzing ' + pair + '...</span>';
            try {
                const d = await api('analyze', { pair });
                if (d.error) {
                    document.getElementById('analysisResult').innerHTML = '<span style="color:var(--red)">' + d.error + '</span>';
                    return;
                }
                const color = d.score > 0 ? 'var(--green)' : d.score < 0 ? 'var(--red)' : 'var(--yellow)';
                document.getElementById('analysisResult').innerHTML = `
                    <div style="text-align:center;margin-bottom:1rem">
                        <div class="big-number" style="color:${color}">${d.recommendation}</div>
                        <div style="font-family:'JetBrains Mono';font-size:1.5rem;color:${color}">${d.score > 0 ? '+' : ''}${d.score}</div>
                        <div style="color:var(--muted)">${d.change_24h} 24h | Risk: ${d.risk_level}</div>
                    </div>
                    <div style="font-size:0.8rem">
                        <div>RSI: ${d.indicators.rsi} | MACD: ${d.indicators.macd > 0 ? '+' : ''}${Number(d.indicators.macd).toFixed(4)}</div>
                        <div>SMA20: $${fmt(d.indicators.sma20)} | SMA50: $${fmt(d.indicators.sma50)}</div>
                        <div>Vol Ratio: ${d.indicators.volume_ratio}x</div>
                        <div style="margin-top:0.5rem;color:var(--muted)">${d.signals.join(' • ')}</div>
                    </div>`;
            } catch (e) {
                document.getElementById('analysisResult').innerHTML = '<span style="color:var(--red)">Error: ' + e.message + '</span>';
            }
        }

        async function controlBot(action) {
            if (action === 'start' && !confirm('Start the trading bot with conservative strategy?')) return;
            try {
                const d = await api('bot_' + action);
                alert(d.message || JSON.stringify(d));
                loadDashboard();
            } catch (e) {
                alert('Error: ' + e.message);
            }
        }

        function callAgent(name) {
            window.open('tel:+18334674836', '_blank');
        }

        // Auto-refresh every 30 seconds
        loadDashboard();
        setInterval(loadDashboard, 30000);
    </script>
</body>
</html>
