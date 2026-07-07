<?php
/**
 * Alfred Wallet & Mining Dashboard
 * ─────────────────────────────────
 * User wallet balance, mining controls, transaction history,
 * leaderboard, and pool statistics.
 */
if (session_status() === PHP_SESSION_NONE) session_start();

$loggedIn = !empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$userName = $_SESSION['client_name'] ?? 'User';
$userInitials = strtoupper(substr($userName, 0, 1)) . strtoupper(substr(strrchr($userName, ' ') ?: $userName, 1, 1));
$firstName = htmlspecialchars(explode(' ', $userName)[0]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alfred Wallet — GSM Token Mining & Rewards</title>
    <meta name="description" content="Earn GSM tokens by searching and mining. 80% of mining rewards go directly to you.">
    <link rel="stylesheet" href="/assets/css/fonts.css">
    <link rel="icon" href="/brand/logo.png" type="image/png">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --w-void: #030308; --w-deep: #070712; --w-surface: #0c0c1a;
            --w-surface2: #111126; --w-glass: rgba(12,12,26,0.85);
            --w-border: rgba(100,140,255,0.08); --w-glow: rgba(100,160,255,0.12);
            --w-text: #d8dce8; --w-dim: #5a6488; --w-mute: #363a50;
            --w-blue: #5b9cf5; --w-indigo: #7c5cfc; --w-cyan: #22d3ee;
            --w-green: #34d399; --w-amber: #fbbf24; --w-red: #ef4444;
            --w-gold: #fbbf24; --w-purple: #a855f7;
            --w-grad: linear-gradient(135deg, #5b9cf5, #7c5cfc, #a855f7);
            --w-grad2: linear-gradient(135deg, #22d3ee, #5b9cf5);
            --w-grad-gold: linear-gradient(135deg, #fbbf24, #f59e0b, #d97706);
            --w-radius: 16px;
        }
        html { scroll-behavior: smooth; }
        body { font-family: 'Inter', 'DM Sans', system-ui, sans-serif; background: var(--w-void); color: var(--w-text); min-height: 100vh; overflow-x: hidden; }
        a { text-decoration: none; color: inherit; }

        /* Ambient background */
        .w-ambient { position: fixed; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 0; }
        .w-orb { position: absolute; border-radius: 50%; filter: blur(120px); opacity: 0.15; animation: orbFloat 20s ease-in-out infinite; }
        .w-orb-1 { width: 600px; height: 600px; background: #5b9cf5; top: -200px; left: -100px; }
        .w-orb-2 { width: 500px; height: 500px; background: #7c5cfc; bottom: -200px; right: -100px; animation-delay: -7s; }
        .w-orb-3 { width: 300px; height: 300px; background: #fbbf24; top: 30%; right: 20%; animation-delay: -14s; opacity: 0.08; }
        @keyframes orbFloat { 0%, 100% { transform: translate(0, 0); } 33% { transform: translate(30px, -20px); } 66% { transform: translate(-20px, 15px); } }

        /* Nav */
        .w-nav { display: flex; align-items: center; justify-content: space-between; padding: 10px 24px; position: relative; z-index: 500; }
        .w-nav-left { display: flex; align-items: center; gap: 8px; }
        .w-nav-home { display: flex; align-items: center; gap: 8px; color: var(--w-text); font-weight: 700; font-size: 15px; opacity: 0.7; transition: opacity 0.2s; }
        .w-nav-home:hover { opacity: 1; }
        .w-nav-home img { height: 24px; }
        .w-nav-right { display: flex; align-items: center; gap: 16px; }
        .w-nav-link { color: var(--w-dim); font-size: 13px; font-weight: 500; transition: color 0.2s; }
        .w-nav-link:hover { color: var(--w-text); }
        .w-nav-link.active { color: var(--w-cyan); }
        .w-nav-user { display: flex; align-items: center; gap: 8px; padding: 5px 14px 5px 5px; background: rgba(255,255,255,0.05); border: 1px solid var(--w-border); border-radius: 50px; color: var(--w-text); font-size: 13px; font-weight: 500; transition: background 0.2s; }
        .w-nav-user:hover { background: rgba(255,255,255,0.08); }
        .w-nav-avatar { width: 26px; height: 26px; border-radius: 50%; background: var(--w-grad); display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; color: #fff; }

        /* Main container */
        .w-main { position: relative; z-index: 1; max-width: 1200px; margin: 0 auto; padding: 20px 24px 80px; }

        /* Hero / Balance card */
        .w-hero { text-align: center; padding: 40px 0 30px; }
        .w-hero-label { font-size: 13px; color: var(--w-dim); font-weight: 500; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 8px; }
        .w-balance { font-size: 56px; font-weight: 800; background: var(--w-grad-gold); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; line-height: 1.1; }
        .w-balance-symbol { font-size: 24px; opacity: 0.7; }
        .w-balance-usd { font-size: 16px; color: var(--w-dim); margin-top: 6px; font-weight: 400; }
        .w-hero-actions { display: flex; gap: 12px; justify-content: center; margin-top: 24px; }
        .w-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 24px; border-radius: 50px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; transition: all 0.2s; }
        .w-btn-primary { background: var(--w-grad); color: #fff; }
        .w-btn-primary:hover { opacity: 0.9; transform: translateY(-1px); box-shadow: 0 4px 20px rgba(91,156,245,0.3); }
        .w-btn-outline { background: transparent; color: var(--w-text); border: 1px solid var(--w-border); }
        .w-btn-outline:hover { border-color: var(--w-blue); background: rgba(91,156,245,0.05); }
        .w-btn-gold { background: var(--w-grad-gold); color: #000; font-weight: 700; }
        .w-btn-gold:hover { opacity: 0.9; box-shadow: 0 4px 20px rgba(251,191,36,0.3); }
        .w-btn-danger { background: rgba(239,68,68,0.1); color: var(--w-red); border: 1px solid rgba(239,68,68,0.2); }
        .w-btn-danger:hover { background: rgba(239,68,68,0.15); }

        /* Stats grid */
        .w-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin: 24px 0; }
        .w-stat { background: var(--w-surface); border: 1px solid var(--w-border); border-radius: var(--w-radius); padding: 20px; transition: border-color 0.2s; }
        .w-stat:hover { border-color: rgba(100,140,255,0.15); }
        .w-stat-label { font-size: 12px; color: var(--w-dim); font-weight: 500; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }
        .w-stat-value { font-size: 24px; font-weight: 700; color: var(--w-text); }
        .w-stat-sub { font-size: 12px; color: var(--w-dim); margin-top: 4px; }
        .w-stat-icon { float: right; font-size: 20px; opacity: 0.3; }

        /* Mining Panel */
        .w-section { margin: 32px 0; }
        .w-section-title { font-size: 18px; font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; }
        .w-section-badge { font-size: 11px; padding: 3px 10px; border-radius: 50px; font-weight: 600; }
        .w-badge-live { background: rgba(34,211,153,0.15); color: var(--w-green); }
        .w-badge-paused { background: rgba(90,100,136,0.15); color: var(--w-dim); }

        .w-mining-panel { background: var(--w-surface); border: 1px solid var(--w-border); border-radius: var(--w-radius); padding: 24px; }
        .w-mining-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .w-mining-toggle { position: relative; width: 56px; height: 30px; }
        .w-mining-toggle input { opacity: 0; width: 0; height: 0; }
        .w-mining-slider { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: var(--w-mute); border-radius: 30px; cursor: pointer; transition: background 0.3s; }
        .w-mining-slider::after { content: ''; position: absolute; left: 3px; top: 3px; width: 24px; height: 24px; border-radius: 50%; background: #fff; transition: transform 0.3s; }
        .w-mining-toggle input:checked + .w-mining-slider { background: var(--w-green); }
        .w-mining-toggle input:checked + .w-mining-slider::after { transform: translateX(26px); }

        .w-mining-info { font-size: 13px; color: var(--w-dim); line-height: 1.6; }
        .w-mining-info strong { color: var(--w-text); }

        .w-hashrate-display { display: flex; align-items: center; gap: 20px; margin: 20px 0; padding: 16px; background: rgba(34,211,153,0.04); border: 1px solid rgba(34,211,153,0.1); border-radius: 12px; }
        .w-hashrate-number { font-size: 32px; font-weight: 800; color: var(--w-cyan); }
        .w-hashrate-unit { font-size: 14px; color: var(--w-dim); }
        .w-hashrate-meta { font-size: 12px; color: var(--w-dim); }

        .w-throttle { margin-top: 16px; }
        .w-throttle-label { font-size: 13px; color: var(--w-dim); margin-bottom: 8px; }
        .w-throttle-slider { width: 100%; height: 6px; -webkit-appearance: none; border-radius: 3px; background: var(--w-mute); outline: none; }
        .w-throttle-slider::-webkit-slider-thumb { -webkit-appearance: none; width: 18px; height: 18px; border-radius: 50%; background: var(--w-cyan); cursor: pointer; }

        /* Revenue Split Visual */
        .w-split { display: flex; gap: 2px; margin: 16px 0; height: 8px; border-radius: 4px; overflow: hidden; }
        .w-split-user { flex: 80; background: var(--w-green); }
        .w-split-platform { flex: 20; background: var(--w-indigo); }
        .w-split-labels { display: flex; justify-content: space-between; font-size: 11px; color: var(--w-dim); margin-top: 4px; }

        /* Two-column layout */
        .w-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

        /* Transactions */
        .w-tx-list { list-style: none; }
        .w-tx-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid var(--w-border); }
        .w-tx-item:last-child { border-bottom: none; }
        .w-tx-type { display: flex; align-items: center; gap: 10px; }
        .w-tx-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 14px; }
        .w-tx-icon-search { background: rgba(91,156,245,0.1); color: var(--w-blue); }
        .w-tx-icon-mine { background: rgba(34,211,153,0.1); color: var(--w-green); }
        .w-tx-icon-streak { background: rgba(251,191,36,0.1); color: var(--w-gold); }
        .w-tx-icon-referral { background: rgba(168,85,247,0.1); color: var(--w-purple); }
        .w-tx-label { font-size: 13px; font-weight: 500; }
        .w-tx-date { font-size: 11px; color: var(--w-dim); }
        .w-tx-amount { font-size: 14px; font-weight: 700; color: var(--w-green); }

        /* Leaderboard */
        .w-lb-item { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--w-border); }
        .w-lb-item:last-child { border-bottom: none; }
        .w-lb-rank { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; background: var(--w-surface2); color: var(--w-dim); }
        .w-lb-rank.gold { background: rgba(251,191,36,0.15); color: var(--w-gold); }
        .w-lb-rank.silver { background: rgba(192,192,192,0.15); color: #c0c0c0; }
        .w-lb-rank.bronze { background: rgba(205,127,50,0.15); color: #cd7f32; }
        .w-lb-name { flex: 1; font-size: 13px; font-weight: 500; }
        .w-lb-earned { font-size: 13px; font-weight: 700; color: var(--w-gold); }

        /* Pool stats */
        .w-pool { background: var(--w-surface); border: 1px solid var(--w-border); border-radius: var(--w-radius); padding: 20px; }
        .w-pool-bar { height: 10px; border-radius: 5px; background: var(--w-mute); overflow: hidden; margin: 12px 0; }
        .w-pool-fill { height: 100%; background: var(--w-grad-gold); border-radius: 5px; transition: width 0.5s ease; }
        .w-pool-labels { display: flex; justify-content: space-between; font-size: 12px; color: var(--w-dim); }

        /* Wallet address */
        .w-wallet-input { display: flex; gap: 8px; margin-top: 12px; }
        .w-wallet-input input { flex: 1; padding: 10px 14px; background: var(--w-surface2); border: 1px solid var(--w-border); border-radius: 10px; color: var(--w-text); font-size: 13px; font-family: 'JetBrains Mono', monospace; outline: none; }
        .w-wallet-input input:focus { border-color: var(--w-blue); }
        .w-wallet-input button { padding: 10px 20px; border: none; border-radius: 10px; background: var(--w-grad); color: #fff; font-weight: 600; cursor: pointer; transition: opacity 0.2s; font-size: 13px; }
        .w-wallet-input button:hover { opacity: 0.9; }

        /* Login prompt */
        .w-login-prompt { text-align: center; padding: 80px 20px; }
        .w-login-prompt h2 { font-size: 28px; margin-bottom: 12px; }
        .w-login-prompt p { color: var(--w-dim); font-size: 16px; margin-bottom: 24px; max-width: 500px; margin-left: auto; margin-right: auto; }

        /* Toast notification */
        .w-toast { position: fixed; bottom: 24px; right: 24px; padding: 14px 20px; background: var(--w-surface2); border: 1px solid var(--w-border); border-radius: 12px; font-size: 14px; z-index: 9999; display: none; animation: toastIn 0.3s ease; max-width: 360px; }
        .w-toast.show { display: flex; align-items: center; gap: 10px; }
        .w-toast-icon { font-size: 18px; }
        @keyframes toastIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        /* Responsive */
        @media (max-width: 900px) {
            .w-stats { grid-template-columns: repeat(2, 1fr); }
            .w-grid-2 { grid-template-columns: 1fr; }
            .w-balance { font-size: 40px; }
        }
        @media (max-width: 600px) {
            .w-stats { grid-template-columns: 1fr; }
            .w-nav { padding: 10px 16px; }
            .w-main { padding: 16px; }
            .w-hero-actions { flex-direction: column; align-items: center; }
        }

        /* Hide global nav */
        .promo-bar, .phone-topbar, .navbar, .mega-backdrop, .cmd-overlay { display: none !important; }
    </style>
    <link rel="stylesheet" href="/assets/css/design-tokens.css?v=20260310">
    <link rel="stylesheet" href="/assets/css/components.css?v=20260310">
    <script src="/assets/js/gds-utils.js?v=20260310" defer></script>
    <script src="/assets/js/gds-toast.js?v=20260310" defer></script>
    <script src="/assets/js/gds-modal.js?v=20260310" defer></script>
</head>
<body>

<div class="w-ambient">
    <div class="w-orb w-orb-1"></div>
    <div class="w-orb w-orb-2"></div>
    <div class="w-orb w-orb-3"></div>
</div>

<!-- Nav -->
<div class="w-nav">
    <div class="w-nav-left">
        <a href="/" class="w-nav-home">
            <img src="/brand/logo_w.png" alt="GoSiteMe"> GoSiteMe
        </a>
    </div>
    <div class="w-nav-right">
        <a href="/search" class="w-nav-link">Search</a>
        <a href="/ecosystem" class="w-nav-link">Ecosystem</a>
        <a href="/wallet" class="w-nav-link active">Wallet</a>
        <a href="/pricing" class="w-nav-link">Pricing</a>
        <?php if ($loggedIn): ?>
            <a href="/dashboard" class="w-nav-user">
                <span class="w-nav-avatar"><?php echo htmlspecialchars($userInitials); ?></span>
                <?php echo $firstName; ?>
            </a>
        <?php else: ?>
            <a href="/login" class="w-nav-link">🔑 Login</a>
        <?php endif; ?>
    </div>
</div>

<div class="w-main">
<?php if (!$loggedIn): ?>
    <!-- Login Required -->
    <div class="w-login-prompt">
        <h2>🪙 Alfred Wallet</h2>
        <p>Earn GSM tokens by searching the web and contributing compute power. 80% of all mining rewards go directly to you.</p>
        <a href="/login?redirect=wallet" class="w-btn w-btn-primary">Sign In to Start Earning</a>
        <div style="margin-top: 32px;">
            <div class="w-stats" style="max-width: 600px; margin: 20px auto;">
                <div class="w-stat">
                    <div class="w-stat-label">User Share</div>
                    <div class="w-stat-value" style="color: var(--w-green);">80%</div>
                    <div class="w-stat-sub">Of all mining rewards</div>
                </div>
                <div class="w-stat">
                    <div class="w-stat-label">Earn Per Search</div>
                    <div class="w-stat-value" style="color: var(--w-gold);">0.0001</div>
                    <div class="w-stat-sub">GSM tokens</div>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- Wallet Hero -->
    <div class="w-hero">
        <div class="w-hero-label">Your GSM Balance</div>
        <div class="w-balance" id="walletBalance">
            <span class="w-balance-symbol">◎ </span>0.0000
        </div>
        <div class="w-balance-usd" id="walletUsd">Loading wallet...</div>
        <div class="w-hero-actions">
            <a href="/search" class="w-btn w-btn-primary">🔍 Search & Earn</a>
            <button class="w-btn w-btn-gold" id="toggleMiningBtn" onclick="toggleMining()">⛏️ Start Mining</button>
            <button class="w-btn w-btn-outline" onclick="document.getElementById('walletSection').scrollIntoView({behavior:'smooth'})">📋 Details</button>
        </div>
    </div>

    <!-- Stats -->
    <div class="w-stats">
        <div class="w-stat">
            <div class="w-stat-icon">⛏️</div>
            <div class="w-stat-label">Hashrate</div>
            <div class="w-stat-value" id="statHashrate">0 H/s</div>
            <div class="w-stat-sub" id="statTotalHashes">0 total hashes</div>
        </div>
        <div class="w-stat">
            <div class="w-stat-icon">🔍</div>
            <div class="w-stat-label">Searches Today</div>
            <div class="w-stat-value" id="statSearches">0</div>
            <div class="w-stat-sub" id="statStreak">0 day streak</div>
        </div>
        <div class="w-stat">
            <div class="w-stat-icon">🏆</div>
            <div class="w-stat-label">Mining Status</div>
            <div class="w-stat-value" id="statMiningStatus">Idle</div>
            <div class="w-stat-sub" id="statMiningTime">—</div>
        </div>
        <div class="w-stat">
            <div class="w-stat-icon">🌊</div>
            <div class="w-stat-label">Pool Remaining</div>
            <div class="w-stat-value" id="statPool">—</div>
            <div class="w-stat-sub">of 250M GSM</div>
        </div>
    </div>

    <!-- Mining Panel -->
    <div class="w-section">
        <div class="w-section-title">
            ⛏️ Browser Mining
            <span class="w-section-badge w-badge-paused" id="miningBadge">PAUSED</span>
        </div>
        <div class="w-mining-panel">
            <div class="w-mining-header">
                <div>
                    <div class="w-mining-info">
                        <strong>Earn GSM by lending idle CPU power.</strong><br>
                        Mining runs in a background thread — it won't slow down your browsing.
                        You receive <strong>80%</strong> of all rewards. Fully opt-in, pause anytime.
                    </div>
                </div>
                <label class="w-mining-toggle">
                    <input type="checkbox" id="miningSwitch" onchange="toggleMining()">
                    <span class="w-mining-slider"></span>
                </label>
            </div>

            <div class="w-split">
                <div class="w-split-user"></div>
                <div class="w-split-platform"></div>
            </div>
            <div class="w-split-labels">
                <span>👤 You: 80%</span>
                <span>🏢 Platform: 20%</span>
            </div>

            <div class="w-hashrate-display" id="hashrateDisplay" style="display:none;">
                <div>
                    <div class="w-hashrate-number" id="liveHashrate">0</div>
                    <div class="w-hashrate-unit">Hashes/second</div>
                </div>
                <div style="flex:1;">
                    <div class="w-hashrate-meta">Total: <strong id="liveTotalHashes">0</strong> hashes</div>
                    <div class="w-hashrate-meta">Blocks: <strong id="liveBlocks">0</strong></div>
                    <div class="w-hashrate-meta">Session earned: <strong id="liveEarned">0</strong> GSM</div>
                </div>
            </div>

            <div class="w-throttle">
                <div class="w-throttle-label">CPU Usage: <strong id="throttleValue">30%</strong></div>
                <input type="range" class="w-throttle-slider" id="throttleSlider" min="5" max="80" value="30" oninput="setThrottle(this.value)">
            </div>
        </div>
    </div>

    <!-- Two columns: Transactions + Leaderboard -->
    <div class="w-grid-2">
        <!-- Transactions -->
        <div class="w-section">
            <div class="w-section-title">📋 Recent Transactions</div>
            <div class="w-mining-panel">
                <ul class="w-tx-list" id="txList">
                    <li class="w-tx-item"><span class="w-tx-label" style="color:var(--w-dim);">Loading transactions...</span></li>
                </ul>
            </div>
        </div>

        <!-- Leaderboard -->
        <div class="w-section">
            <div class="w-section-title">🏆 Top Miners</div>
            <div class="w-mining-panel">
                <div id="leaderboard">
                    <div style="color:var(--w-dim); font-size: 13px;">Loading leaderboard...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Wallet Address + Pool -->
    <div class="w-grid-2" id="walletSection">
        <!-- Wallet Address -->
        <div class="w-section">
            <div class="w-section-title">🔐 Solana Wallet</div>
            <div class="w-mining-panel">
                <div class="w-mining-info">
                    Connect your Solana wallet to receive GSM token withdrawals on-chain.
                    The GSM token is built on Solana for fast, low-cost transfers.
                </div>
                <div class="w-wallet-input">
                    <input type="text" id="walletAddress" placeholder="Your Solana wallet address (e.g. 7xK...)" maxlength="44">
                    <button onclick="saveWallet()">Save</button>
                </div>
                <div id="walletStatus" style="font-size: 12px; color: var(--w-dim); margin-top: 8px;"></div>
            </div>
        </div>

        <!-- Pool Stats -->
        <div class="w-section">
            <div class="w-section-title">🌊 Mining Pool</div>
            <div class="w-pool">
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                    <span style="font-size: 14px; font-weight: 600;">GSM Distribution</span>
                    <span style="font-size: 13px; color: var(--w-dim);" id="poolPercent">0%</span>
                </div>
                <div class="w-pool-bar">
                    <div class="w-pool-fill" id="poolBar" style="width: 0%;"></div>
                </div>
                <div class="w-pool-labels">
                    <span id="poolDistributed">0 GSM distributed</span>
                    <span id="poolRemaining">250M GSM remaining</span>
                </div>
                <div style="margin-top: 16px; font-size: 12px; color: var(--w-dim); line-height: 1.8;">
                    <div>Active miners: <strong id="poolMiners">0</strong></div>
                    <div>Total hashes: <strong id="poolHashes">0</strong></div>
                    <div>Per hash block (1M): <strong>0.001 GSM</strong></div>
                    <div>Per search query: <strong>0.0001 GSM</strong></div>
                </div>
            </div>
        </div>
    </div>

<?php endif; ?>
</div>

<!-- Toast -->
<div class="w-toast" id="toast">
    <span class="w-toast-icon" id="toastIcon">🪙</span>
    <span id="toastMsg"></span>
</div>

<?php if ($loggedIn): ?>
<script src="/assets/js/alfred-miner.js"></script>
<script src="/assets/js/wallet-engine.js"></script>
<?php endif; ?>

</body>
</html>
