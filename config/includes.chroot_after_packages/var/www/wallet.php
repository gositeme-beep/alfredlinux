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
    <title>Alfred Wallet — GSM Portfolio, Staking, Gaming & Mining</title>
    <meta name="description" content="Unified GSM wallet — portfolio, staking, P2P transfers, game wagers, and mining rewards.">
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

        /* Wallet Tabs */
        .w-tabs { display: flex; gap: 4px; padding: 4px; background: var(--w-surface); border: 1px solid var(--w-border); border-radius: 14px; margin: 0 0 24px; flex-wrap: wrap; }
        .w-tab { padding: 10px 20px; border-radius: 10px; font-size: 13px; font-weight: 600; color: var(--w-dim); cursor: pointer; border: none; background: transparent; transition: all 0.2s; }
        .w-tab:hover { color: var(--w-text); background: rgba(255,255,255,0.03); }
        .w-tab.active { background: rgba(91,156,245,0.12); color: var(--w-blue); }
        .w-tab-panel { display: none; }
        .w-tab-panel.active { display: block; }

        /* Staking */
        .w-stake-tiers { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin: 16px 0; }
        .w-stake-tier { background: var(--w-surface); border: 1px solid var(--w-border); border-radius: 14px; padding: 16px; cursor: pointer; transition: all 0.2s; text-align: center; }
        .w-stake-tier:hover { border-color: var(--w-blue); }
        .w-stake-tier.selected { border-color: var(--w-gold); background: rgba(251,191,36,0.05); }
        .w-stake-tier-name { font-size: 14px; font-weight: 700; margin-bottom: 4px; }
        .w-stake-tier-apy { font-size: 22px; font-weight: 800; background: var(--w-grad-gold); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .w-stake-tier-min { font-size: 12px; color: var(--w-dim); margin-top: 2px; }
        .w-stake-tier-lock { font-size: 11px; color: var(--w-dim); margin-top: 2px; }
        .w-stake-positions { margin-top: 20px; }
        .w-stake-position { display: flex; justify-content: space-between; align-items: center; padding: 14px; background: var(--w-surface); border: 1px solid var(--w-border); border-radius: 12px; margin-bottom: 8px; }
        .w-stake-amount-input { display: flex; gap: 8px; margin: 16px 0; }
        .w-stake-amount-input input { flex: 1; padding: 12px 14px; background: var(--w-surface2); border: 1px solid var(--w-border); border-radius: 10px; color: var(--w-text); font-size: 16px; outline: none; }
        .w-stake-amount-input input:focus { border-color: var(--w-gold); }

        /* Transfer */
        .w-transfer-form { max-width: 500px; }
        .w-transfer-form label { display: block; font-size: 13px; color: var(--w-dim); margin-bottom: 6px; font-weight: 500; }
        .w-transfer-form input { width: 100%; padding: 12px 14px; background: var(--w-surface2); border: 1px solid var(--w-border); border-radius: 10px; color: var(--w-text); font-size: 14px; outline: none; margin-bottom: 16px; }
        .w-transfer-form input:focus { border-color: var(--w-blue); }
        .w-transfer-fee { font-size: 12px; color: var(--w-dim); margin-bottom: 16px; }

        /* Game wagers */
        .w-game-tabs { display: flex; gap: 4px; margin-bottom: 16px; }
        .w-game-tab { padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 600; color: var(--w-dim); cursor: pointer; border: 1px solid transparent; background: transparent; transition: all 0.2s; }
        .w-game-tab:hover { color: var(--w-text); }
        .w-game-tab.active { background: rgba(34,211,153,0.1); color: var(--w-green); border-color: rgba(34,211,153,0.2); }
        .w-wager-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid var(--w-border); }
        .w-wager-item:last-child { border-bottom: none; }
        .w-wager-game { display: flex; align-items: center; gap: 10px; }
        .w-wager-game-icon { width: 28px; height: 28px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 14px; background: rgba(91,156,245,0.1); }
        .w-wager-result { font-size: 14px; font-weight: 700; }
        .w-wager-result.won { color: var(--w-green); }
        .w-wager-result.lost { color: var(--w-red); }
        .w-wager-result.draw { color: var(--w-amber); }

        @media (max-width: 900px) {
            .w-stake-tiers { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 600px) {
            .w-stake-tiers { grid-template-columns: 1fr; }
            .w-tabs { gap: 2px; }
            .w-tab { padding: 8px 12px; font-size: 12px; }
        }
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
            <div class="w-stat-icon">�</div>
            <div class="w-stat-label">Gaming</div>
            <div class="w-stat-value" id="statGaming">—</div>
            <div class="w-stat-sub" id="statGamingDetail">wagered / won</div>
        </div>
        <div class="w-stat">
            <div class="w-stat-icon">📈</div>
            <div class="w-stat-label">Staking</div>
            <div class="w-stat-value" id="statStaking">0 GSM</div>
            <div class="w-stat-sub" id="statYield">0 GSM yield</div>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="w-tabs">
        <button class="w-tab active" onclick="switchTab('portfolio')">📊 Portfolio</button>
        <button class="w-tab" onclick="switchTab('staking')">📈 Staking</button>
        <button class="w-tab" onclick="switchTab('transfer')">💸 Transfer</button>
        <button class="w-tab" onclick="switchTab('gaming')">🎮 Gaming</button>
        <button class="w-tab" onclick="switchTab('land')">🏠 Land</button>
        <button class="w-tab" onclick="switchTab('governance')">🗳️ Governance</button>
        <button class="w-tab" onclick="switchTab('trophies')">🏆 Trophies</button>
        <button class="w-tab" onclick="switchTab('mining')">⛏️ Mining</button>
        <button class="w-tab" onclick="window.location.href='/blockchain.php'">⛓️ Blockchain</button>
    </div>

    <!-- ═══ TAB: PORTFOLIO ═══ -->
    <div class="w-tab-panel active" id="tab-portfolio">
        <div class="w-grid-2">
            <div class="w-section" style="margin-top:0">
                <div class="w-section-title">💰 Balance Breakdown</div>
                <div class="w-mining-panel">
                    <div id="portfolioBreakdown">
                        <div class="w-tx-item"><span class="w-tx-label">Available</span><span class="w-tx-amount" id="pAvailable">—</span></div>
                        <div class="w-tx-item"><span class="w-tx-label">Staked</span><span class="w-tx-amount" id="pStaked" style="color:var(--w-indigo)">—</span></div>
                        <div class="w-tx-item"><span class="w-tx-label">Mining Earned</span><span class="w-tx-amount" id="pMining">—</span></div>
                        <div class="w-tx-item"><span class="w-tx-label">Gaming Earned</span><span class="w-tx-amount" id="pGamingEarned">—</span></div>
                        <div class="w-tx-item"><span class="w-tx-label">Gaming Spent</span><span class="w-tx-amount" id="pGamingSpent" style="color:var(--w-red)">—</span></div>
                        <div class="w-tx-item"><span class="w-tx-label">Pending Yield</span><span class="w-tx-amount" id="pPendingYield" style="color:var(--w-cyan)">—</span></div>
                    </div>
                </div>
            </div>
            <div class="w-section" style="margin-top:0">
                <div class="w-section-title">📋 Recent Activity</div>
                <div class="w-mining-panel">
                    <ul class="w-tx-list" id="txList">
                        <li class="w-tx-item"><span class="w-tx-label" style="color:var(--w-dim);">Loading transactions...</span></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Achievements -->
        <div class="w-section">
            <div class="w-section-title">🏅 Achievements</div>
            <div class="w-mining-panel" id="achievementsPanel">
                <div style="color:var(--w-dim); font-size: 13px;">Loading achievements...</div>
            </div>
        </div>
    </div>

    <!-- ═══ TAB: STAKING ═══ -->
    <div class="w-tab-panel" id="tab-staking">
        <div class="w-section" style="margin-top:0">
            <div class="w-section-title">📈 Stake GSM for Yield</div>
            <div class="w-mining-panel">
                <div class="w-mining-info" style="margin-bottom:16px">
                    Lock your GSM tokens to earn passive yield. Higher tiers = higher APY but longer lock periods.
                </div>
                <div class="w-stake-tiers">
                    <div class="w-stake-tier" onclick="selectStakingTier('bronze')">
                        <div class="w-stake-tier-name">🥉 Bronze</div>
                        <div class="w-stake-tier-apy">5% APY</div>
                        <div class="w-stake-tier-min">Min: 100 GSM</div>
                        <div class="w-stake-tier-lock">Lock: 7 days</div>
                    </div>
                    <div class="w-stake-tier" onclick="selectStakingTier('silver')">
                        <div class="w-stake-tier-name">🥈 Silver</div>
                        <div class="w-stake-tier-apy">10% APY</div>
                        <div class="w-stake-tier-min">Min: 1,000 GSM</div>
                        <div class="w-stake-tier-lock">Lock: 30 days</div>
                    </div>
                    <div class="w-stake-tier" onclick="selectStakingTier('gold')">
                        <div class="w-stake-tier-name">🥇 Gold</div>
                        <div class="w-stake-tier-apy">18% APY</div>
                        <div class="w-stake-tier-min">Min: 10,000 GSM</div>
                        <div class="w-stake-tier-lock">Lock: 90 days</div>
                    </div>
                    <div class="w-stake-tier" onclick="selectStakingTier('platinum')">
                        <div class="w-stake-tier-name">💎 Platinum</div>
                        <div class="w-stake-tier-apy">30% APY</div>
                        <div class="w-stake-tier-min">Min: 100,000 GSM</div>
                        <div class="w-stake-tier-lock">Lock: 180 days</div>
                    </div>
                </div>

                <div class="w-stake-amount-input" id="stakeInputArea" style="display:none">
                    <input type="number" id="stakeAmountInput" placeholder="Amount to stake" min="0" step="1">
                    <button class="w-btn w-btn-gold" onclick="submitStake()">Stake GSM</button>
                </div>
            </div>
        </div>

        <div class="w-section">
            <div class="w-section-title">📊 Active Staking Positions</div>
            <div class="w-mining-panel">
                <div class="w-stake-positions" id="stakingPositions">
                    <div style="color:var(--w-dim); font-size:13px;">Loading staking positions...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ TAB: TRANSFER ═══ -->
    <div class="w-tab-panel" id="tab-transfer">
        <div class="w-section" style="margin-top:0">
            <div class="w-section-title">💸 Send GSM</div>
            <div class="w-mining-panel">
                <div class="w-mining-info" style="margin-bottom:16px">
                    Transfer GSM to another user by email address. A 0.5% fee applies to all transfers.
                </div>
                <div class="w-transfer-form">
                    <label for="transferRecipient">Recipient Email</label>
                    <input type="email" id="transferRecipient" placeholder="user@example.com" maxlength="128">

                    <label for="transferAmount">Amount (GSM)</label>
                    <input type="number" id="transferAmount" placeholder="0.00" min="0.01" step="0.01">

                    <div class="w-transfer-fee" id="transferFeeDisplay">Fee: 0.5% · Total: 0.00 GSM</div>

                    <button class="w-btn w-btn-primary" onclick="submitTransfer()">Send GSM</button>
                </div>

                <div id="transferStatus" style="margin-top: 12px; font-size: 13px;"></div>
            </div>
        </div>

        <div class="w-grid-2">
            <!-- Wallet Address -->
            <div class="w-section">
                <div class="w-section-title">🔐 Solana Wallet</div>
                <div class="w-mining-panel">
                    <div class="w-mining-info">
                        Connect your Solana wallet to receive GSM token withdrawals on-chain.
                    </div>
                    <div class="w-wallet-input">
                        <input type="text" id="walletAddress" placeholder="Your Solana wallet address (e.g. 7xK...)" maxlength="44">
                        <button onclick="saveWallet()">Save</button>
                    </div>
                    <div id="walletStatus" style="font-size: 12px; color: var(--w-dim); margin-top: 8px;"></div>
                </div>
            </div>

            <!-- Transfer History -->
            <div class="w-section">
                <div class="w-section-title">📜 Transfer History</div>
                <div class="w-mining-panel">
                    <div id="transferHistory" style="color:var(--w-dim); font-size:13px;">Loading...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ TAB: GAMING ═══ -->
    <div class="w-tab-panel" id="tab-gaming">
        <div class="w-section" style="margin-top:0">
            <div class="w-section-title">🎮 Game Wagering</div>
            <div class="w-mining-panel">
                <div class="w-game-tabs">
                    <button class="w-game-tab active" onclick="filterGameWagers('all')">All</button>
                    <button class="w-game-tab" onclick="filterGameWagers('chess')">♟️ Chess</button>
                    <button class="w-game-tab" onclick="filterGameWagers('checkers')">🔴 Checkers</button>
                    <button class="w-game-tab" onclick="filterGameWagers('pool')">🎱 Pool</button>
                    <button class="w-game-tab" onclick="filterGameWagers('backgammon')">🎲 Backgammon</button>
                    <button class="w-game-tab" onclick="filterGameWagers('poker')">♠️ Poker</button>
                </div>
                <div id="wagerHistory">
                    <div style="color:var(--w-dim); font-size:13px;">Loading wager history...</div>
                </div>
            </div>
        </div>

        <div class="w-grid-2">
            <div class="w-section">
                <div class="w-section-title">📊 Gaming Stats</div>
                <div class="w-mining-panel" id="gamingStatsPanel">
                    <div style="color:var(--w-dim); font-size:13px;">Loading...</div>
                </div>
            </div>
            <div class="w-section">
                <div class="w-section-title">🏆 Betting Leaderboard</div>
                <div class="w-mining-panel" id="gamingLeaderboard">
                    <div style="color:var(--w-dim); font-size:13px;">Loading...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══ TAB: MINING ═══ -->
    <div class="w-tab-panel" id="tab-mining">
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

    <!-- Two columns: Leaderboard + Pool -->
    <div class="w-grid-2">
        <!-- Leaderboard -->
        <div class="w-section">
            <div class="w-section-title">🏆 Top Miners</div>
            <div class="w-mining-panel">
                <div id="leaderboard">
                    <div style="color:var(--w-dim); font-size: 13px;">Loading leaderboard...</div>
                </div>
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

    <!-- QGSM Migration -->
    <div class="w-section">
        <div class="w-mining-panel" style="border-color: rgba(59,130,246,0.28); background: linear-gradient(180deg, rgba(30,41,59,0.96), rgba(15,23,42,0.96));">
            <div class="w-section-title" style="margin-bottom:12px; font-size:16px;">🧭 GSM Token — Live on Solana Mainnet</div>
            <div class="w-mining-info">
                <strong>Now:</strong> GSM is live on Solana mainnet — <a href="https://solscan.io/token/7Uix6nuVfPEPnqV9o9rffDvA6bX2YSLUjUJSQxU5Q7un" target="_blank" rel="noopener" style="color:var(--w-accent)">view on Solscan</a>. Withdrawals to Phantom, Solflare, or any Solana wallet are active.<br>
                <strong>Next:</strong> QGSM — a post-quantum upgrade — will become the native ecosystem currency for agent payments, governance, mining rewards, and department treasuries. Your GSM balance will migrate 1:1.<br>
                <strong>What to do now:</strong> Keep your wallet connected, keep earning GSM, and read the QGSM white paper for what's ahead.
            </div>
            <div class="w-hero-actions" style="margin-top:16px; justify-content:flex-start;">
                <a href="/qgsm-whitepaper" class="w-btn w-btn-primary">Read QGSM White Paper</a>
                <a href="/qgsm-bridge.php" class="w-btn w-btn-outline">Open QGSM Bridge</a>
            </div>
        </div>
    </div>
    </div><!-- /tab-mining -->

    <!-- ═══ TAB: VR LAND ═══ -->
    <div class="w-tab-panel" id="tab-land">
        <div class="w-grid-2">
            <div class="w-card">
                <h3>🏠 My Land Plots</h3>
                <div id="myPlotsPanel"><div style="color:var(--w-dim);font-size:13px">Loading plots...</div></div>
            </div>
            <div class="w-card">
                <h3>📊 Land Portfolio</h3>
                <div id="landPortfolio"><div style="color:var(--w-dim);font-size:13px">Loading...</div></div>
            </div>
        </div>
        <div class="w-card" style="margin-top:16px">
            <h3>🏪 Marketplace Listings</h3>
            <div style="display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap">
                <select id="landZoneFilter" onchange="loadMarketplace()" style="background:var(--w-surface2);color:var(--w-text);border:1px solid var(--w-border);border-radius:8px;padding:6px 12px;font-size:12px">
                    <option value="">All Zones</option>
                    <option value="downtown">Downtown</option>
                    <option value="skyline">Skyline</option>
                    <option value="beachfront">Beachfront</option>
                    <option value="commercial">Commercial</option>
                    <option value="residential">Residential</option>
                    <option value="wilderness">Wilderness</option>
                </select>
                <select id="landSizeFilter" onchange="loadMarketplace()" style="background:var(--w-surface2);color:var(--w-text);border:1px solid var(--w-border);border-radius:8px;padding:6px 12px;font-size:12px">
                    <option value="">All Sizes</option>
                    <option value="small">Small</option>
                    <option value="medium">Medium</option>
                    <option value="large">Large</option>
                    <option value="estate">Estate</option>
                </select>
            </div>
            <div id="marketplaceListings"><div style="color:var(--w-dim);font-size:13px">Loading marketplace...</div></div>
        </div>
        <div class="w-card" style="margin-top:16px">
            <h3>📈 Market Stats</h3>
            <div id="landStats"><div style="color:var(--w-dim);font-size:13px">Loading...</div></div>
        </div>
    </div><!-- /tab-land -->

    <!-- ═══ TAB: GOVERNANCE ═══ -->
    <div class="w-tab-panel" id="tab-governance">
        <div class="w-card">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
                <h3 style="margin:0">🗳️ Active Proposals</h3>
                <button class="w-btn" onclick="showCreateProposal()">+ New Proposal</button>
            </div>
            <div style="display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap">
                <select id="govStatusFilter" onchange="loadProposals()" style="background:var(--w-surface2);color:var(--w-text);border:1px solid var(--w-border);border-radius:8px;padding:6px 12px;font-size:12px">
                    <option value="active">Active</option>
                    <option value="passed">Passed</option>
                    <option value="rejected">Rejected</option>
                    <option value="executed">Executed</option>
                    <option value="all">All</option>
                </select>
                <select id="govCategoryFilter" onchange="loadProposals()" style="background:var(--w-surface2);color:var(--w-text);border:1px solid var(--w-border);border-radius:8px;padding:6px 12px;font-size:12px">
                    <option value="">All Categories</option>
                    <option value="feature">Feature</option>
                    <option value="economy">Economy</option>
                    <option value="game">Game</option>
                    <option value="governance">Governance</option>
                    <option value="community">Community</option>
                    <option value="technical">Technical</option>
                </select>
            </div>
            <div id="proposalsList"><div style="color:var(--w-dim);font-size:13px">Loading proposals...</div></div>
        </div>
        <div id="createProposalForm" style="display:none" class="w-card" style="margin-top:16px">
            <h3>📝 Create Proposal</h3>
            <input type="text" id="propTitle" placeholder="Proposal title (min 10 chars)" style="width:100%;background:var(--w-surface2);color:var(--w-text);border:1px solid var(--w-border);border-radius:8px;padding:10px;margin-bottom:8px;box-sizing:border-box">
            <textarea id="propDescription" placeholder="Detailed description (min 30 chars)" rows="4" style="width:100%;background:var(--w-surface2);color:var(--w-text);border:1px solid var(--w-border);border-radius:8px;padding:10px;margin-bottom:8px;resize:vertical;box-sizing:border-box"></textarea>
            <div style="display:flex;gap:8px;margin-bottom:12px">
                <select id="propCategory" style="background:var(--w-surface2);color:var(--w-text);border:1px solid var(--w-border);border-radius:8px;padding:8px;flex:1">
                    <option value="feature">Feature</option>
                    <option value="economy">Economy</option>
                    <option value="game">Game</option>
                    <option value="community">Community</option>
                    <option value="technical">Technical</option>
                </select>
                <select id="propDuration" style="background:var(--w-surface2);color:var(--w-text);border:1px solid var(--w-border);border-radius:8px;padding:8px;flex:1">
                    <option value="3">3 days</option>
                    <option value="7" selected>7 days</option>
                    <option value="14">14 days</option>
                    <option value="30">30 days</option>
                </select>
            </div>
            <div style="display:flex;gap:8px">
                <button class="w-btn" onclick="submitProposal()">Submit Proposal</button>
                <button class="w-btn w-btn-outline" onclick="document.getElementById('createProposalForm').style.display='none'">Cancel</button>
            </div>
        </div>
        <div class="w-grid-2" style="margin-top:16px">
            <div class="w-card">
                <h3>🗳️ My Voting Power</h3>
                <div id="votingPowerPanel"><div style="color:var(--w-dim);font-size:13px">Loading...</div></div>
            </div>
            <div class="w-card">
                <h3>📊 Governance Stats</h3>
                <div id="govStatsPanel"><div style="color:var(--w-dim);font-size:13px">Loading...</div></div>
            </div>
        </div>
    </div><!-- /tab-governance -->

    <!-- ═══ TAB: TROPHIES ═══ -->
    <div class="w-tab-panel" id="tab-trophies">
        <div class="w-card">
            <h3>🏆 My Trophies</h3>
            <div id="myTrophiesPanel"><div style="color:var(--w-dim);font-size:13px">Loading trophies...</div></div>
        </div>
        <div class="w-card" style="margin-top:16px">
            <h3>📋 Trophy Catalog</h3>
            <div style="display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap">
                <select id="trophyCategoryFilter" onchange="loadTrophyCatalog()" style="background:var(--w-surface2);color:var(--w-text);border:1px solid var(--w-border);border-radius:8px;padding:6px 12px;font-size:12px">
                    <option value="">All Categories</option>
                    <option value="achievement">Achievement</option>
                    <option value="tournament">Tournament</option>
                    <option value="milestone">Milestone</option>
                    <option value="seasonal">Seasonal</option>
                    <option value="special">Special</option>
                    <option value="legendary">Legendary</option>
                </select>
                <select id="trophyRarityFilter" onchange="loadTrophyCatalog()" style="background:var(--w-surface2);color:var(--w-text);border:1px solid var(--w-border);border-radius:8px;padding:6px 12px;font-size:12px">
                    <option value="">All Rarities</option>
                    <option value="common">Common</option>
                    <option value="uncommon">Uncommon</option>
                    <option value="rare">Rare</option>
                    <option value="epic">Epic</option>
                    <option value="legendary">Legendary</option>
                    <option value="mythic">Mythic</option>
                </select>
            </div>
            <div id="trophyCatalog"><div style="color:var(--w-dim);font-size:13px">Loading catalog...</div></div>
        </div>
        <div class="w-card" style="margin-top:16px">
            <h3>📊 Trophy Stats</h3>
            <div id="trophyStatsPanel"><div style="color:var(--w-dim);font-size:13px">Loading...</div></div>
        </div>
    </div><!-- /tab-trophies -->

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
<script>
// ── Tab Switching ──
function switchTab(tab) {
    document.querySelectorAll('.w-tab-panel').forEach(function(p) { p.classList.remove('active'); });
    document.querySelectorAll('.w-tab').forEach(function(t) { t.classList.remove('active'); });
    var panel = document.getElementById('tab-' + tab);
    if (panel) panel.classList.add('active');
    // Highlight the clicked tab
    event.target.classList.add('active');
    // Load data for the tab
    if (tabLoaders[tab]) tabLoaders[tab]();
}

// ── GSM Economy API ──
function gsmApi(action, method, body) {
    var opts = { credentials: 'same-origin' };
    if (method === 'POST') {
        opts.method = 'POST';
        opts.headers = { 'Content-Type': 'application/json' };
        opts.body = JSON.stringify(body || {});
    }
    return fetch('/api/gsm-economy.php?action=' + encodeURIComponent(action), opts).then(function(r) { return r.json(); });
}

function bettingApi(action, params) {
    var qs = '?action=' + encodeURIComponent(action);
    if (params) for (var k in params) qs += '&' + k + '=' + encodeURIComponent(params[k]);
    return fetch('/api/universal-betting.php' + qs, { credentials: 'same-origin' }).then(function(r) { return r.json(); });
}

// ── Portfolio ──
function loadPortfolio() {
    gsmApi('balance', 'GET').then(function(data) {
        if (!data.success) return;
        var g = data.gsm;
        document.getElementById('walletBalance').innerHTML = '<span class="w-balance-symbol">◎ </span>' + g.gsm_balance.toFixed(4);
        document.getElementById('walletUsd').textContent = 'Available: ' + g.available.toFixed(4) + ' GSM · Staked: ' + g.staked_amount.toFixed(4) + ' GSM';
        document.getElementById('pAvailable').textContent = g.available.toFixed(4) + ' GSM';
        document.getElementById('pStaked').textContent = g.staked_amount.toFixed(4) + ' GSM';
        document.getElementById('pMining').textContent = g.mining_earned.toFixed(4) + ' GSM';
        document.getElementById('pGamingEarned').textContent = g.gaming_earned.toFixed(4) + ' GSM';
        document.getElementById('pGamingSpent').textContent = g.gaming_spent.toFixed(4) + ' GSM';
        document.getElementById('statStaking').textContent = g.staked_amount.toFixed(2) + ' GSM';

        // Pending yield
        if (data.staking) {
            var py = 0;
            data.staking.forEach(function(s) { py += parseFloat(s.pending_yield || 0); });
            document.getElementById('pPendingYield').textContent = py.toFixed(6) + ' GSM';
            document.getElementById('statYield').textContent = py.toFixed(4) + ' GSM yield';
        }

        // Achievements
        if (data.achievements) loadAchievementsUI(data.achievements);
    });

    // Load ledger for recent activity
    gsmApi('ledger', 'GET').then(function(data) {
        if (!data.success) return;
        var list = document.getElementById('txList');
        if (!data.transactions || data.transactions.length === 0) {
            list.innerHTML = '<li class="w-tx-item"><span class="w-tx-label" style="color:var(--w-dim)">No transactions yet</span></li>';
            return;
        }
        var html = '';
        data.transactions.slice(0, 15).forEach(function(tx) {
            var icon = tx.tx_type.includes('game') ? '🎮' : (tx.tx_type.includes('mining') ? '⛏️' : (tx.tx_type.includes('transfer') ? '💸' : (tx.tx_type.includes('stak') ? '📈' : '🪙')));
            var cls = parseFloat(tx.amount) >= 0 ? 'w-tx-amount' : 'w-tx-amount" style="color:var(--w-red)';
            var amt = parseFloat(tx.amount);
            html += '<li class="w-tx-item">';
            html += '<div class="w-tx-type"><div class="w-tx-icon w-tx-icon-mine">' + icon + '</div><div><div class="w-tx-label">' + (tx.description || tx.tx_type).substring(0, 50) + '</div><div class="w-tx-date">' + (tx.created_at || '') + '</div></div></div>';
            html += '<span class="' + cls + '">' + (amt >= 0 ? '+' : '') + amt.toFixed(4) + ' GSM</span>';
            html += '</li>';
        });
        list.innerHTML = html;
    });
}

function loadAchievementsUI(achievements) {
    var panel = document.getElementById('achievementsPanel');
    if (!achievements || achievements.length === 0) {
        panel.innerHTML = '<div style="color:var(--w-dim);font-size:13px;">No achievements yet. Play games and earn GSM to unlock them!</div>';
        return;
    }
    var html = '<div style="display:flex;flex-wrap:wrap;gap:10px">';
    achievements.forEach(function(a) {
        html += '<div style="background:var(--w-surface2);border:1px solid var(--w-border);border-radius:10px;padding:10px 14px;min-width:140px">';
        html += '<div style="font-weight:700;font-size:13px">' + a.title + '</div>';
        html += '<div style="font-size:11px;color:var(--w-dim)">' + a.description + '</div>';
        if (a.gsm_reward > 0) html += '<div style="font-size:11px;color:var(--w-gold);margin-top:2px">+' + parseFloat(a.gsm_reward).toFixed(2) + ' GSM</div>';
        html += '</div>';
    });
    html += '</div>';
    panel.innerHTML = html;
}

// ── Staking ──
var selectedTier = null;
function selectStakingTier(tier) {
    selectedTier = tier;
    document.querySelectorAll('.w-stake-tier').forEach(function(t) { t.classList.remove('selected'); });
    event.target.closest('.w-stake-tier').classList.add('selected');
    document.getElementById('stakeInputArea').style.display = 'flex';
    var mins = { bronze: 100, silver: 1000, gold: 10000, platinum: 100000 };
    document.getElementById('stakeAmountInput').min = mins[tier];
    document.getElementById('stakeAmountInput').placeholder = 'Min: ' + mins[tier].toLocaleString() + ' GSM';
}

function submitStake() {
    if (!selectedTier) return;
    var amount = parseFloat(document.getElementById('stakeAmountInput').value);
    if (!amount || amount <= 0) { showWalletToast('Enter a valid amount', '⚠️'); return; }
    gsmApi('stake', 'POST', { tier: selectedTier, amount: amount }).then(function(data) {
        if (data.success) {
            showWalletToast('Staked ' + amount + ' GSM at ' + selectedTier + ' tier!', '📈');
            loadStaking();
            loadPortfolio();
        } else {
            showWalletToast(data.error || 'Staking failed', '⚠️');
        }
    });
}

function loadStaking() {
    gsmApi('staking', 'GET').then(function(data) {
        var panel = document.getElementById('stakingPositions');
        if (!data.success || !data.stakes || data.stakes.length === 0) {
            panel.innerHTML = '<div style="color:var(--w-dim);font-size:13px">No active staking positions. Select a tier above to start earning yield.</div>';
            return;
        }
        var html = '';
        data.stakes.forEach(function(s) {
            var tierEmoji = { bronze:'🥉', silver:'🥈', gold:'🥇', platinum:'💎' };
            html += '<div class="w-stake-position">';
            html += '<div><strong>' + (tierEmoji[s.tier] || '') + ' ' + s.tier.charAt(0).toUpperCase() + s.tier.slice(1) + '</strong><br>';
            html += '<span style="font-size:12px;color:var(--w-dim)">' + parseFloat(s.amount).toFixed(2) + ' GSM · ' + s.apy + '% APY</span><br>';
            html += '<span style="font-size:11px;color:var(--w-dim)">Unlocks: ' + (s.unlock_date || 'N/A') + '</span></div>';
            html += '<div style="text-align:right"><div style="color:var(--w-green);font-weight:700">+' + parseFloat(s.pending_yield || s.total_yield || 0).toFixed(6) + ' GSM</div>';
            html += '<button class="w-btn w-btn-danger" style="font-size:11px;padding:4px 10px;margin-top:4px" onclick="unstake(' + s.id + ')">Unstake</button></div>';
            html += '</div>';
        });
        panel.innerHTML = html;
    });
}

function unstake(stakeId) {
    gsmApi('unstake', 'POST', { stake_id: stakeId }).then(function(data) {
        if (data.success) {
            showWalletToast('Unstaked successfully!', '✅');
            loadStaking();
            loadPortfolio();
        } else {
            showWalletToast(data.error || 'Unstake failed', '⚠️');
        }
    });
}

// ── Transfer ──
document.getElementById('transferAmount').addEventListener('input', function() {
    var amt = parseFloat(this.value) || 0;
    var fee = amt * 0.005;
    document.getElementById('transferFeeDisplay').textContent = 'Fee: 0.5% (' + fee.toFixed(4) + ' GSM) · Total: ' + (amt + fee).toFixed(4) + ' GSM';
});

function submitTransfer() {
    var recipient = document.getElementById('transferRecipient').value.trim();
    var amount = parseFloat(document.getElementById('transferAmount').value);
    if (!recipient || !amount || amount <= 0) { showWalletToast('Enter recipient and amount', '⚠️'); return; }
    gsmApi('transfer', 'POST', { to_email: recipient, amount: amount }).then(function(data) {
        var status = document.getElementById('transferStatus');
        if (data.success) {
            status.innerHTML = '<span style="color:var(--w-green)">✅ Sent ' + amount.toFixed(4) + ' GSM to ' + recipient + '</span>';
            document.getElementById('transferRecipient').value = '';
            document.getElementById('transferAmount').value = '';
            loadPortfolio();
            loadTransferHistory();
        } else {
            status.innerHTML = '<span style="color:var(--w-red)">❌ ' + (data.error || 'Transfer failed') + '</span>';
        }
    });
}

function loadTransferHistory() {
    gsmApi('ledger', 'GET').then(function(data) {
        var panel = document.getElementById('transferHistory');
        if (!data.success || !data.transactions) { panel.textContent = 'No transfers yet'; return; }
        var transfers = data.transactions.filter(function(t) { return t.tx_type === 'transfer_in' || t.tx_type === 'transfer_out'; });
        if (transfers.length === 0) { panel.textContent = 'No transfers yet'; return; }
        var html = '';
        transfers.slice(0, 10).forEach(function(tx) {
            var dir = tx.tx_type === 'transfer_in' ? '📥' : '📤';
            var color = tx.tx_type === 'transfer_in' ? 'var(--w-green)' : 'var(--w-red)';
            html += '<div class="w-tx-item"><span class="w-tx-label">' + dir + ' ' + (tx.description || tx.tx_type) + '</span>';
            html += '<span style="color:' + color + ';font-weight:700">' + parseFloat(tx.amount).toFixed(4) + ' GSM</span></div>';
        });
        panel.innerHTML = html;
    });
}

// ── Gaming ──
function loadGamingStats() {
    bettingApi('game-stats').then(function(data) {
        var panel = document.getElementById('gamingStatsPanel');
        if (!data.success) { panel.textContent = 'No gaming data'; return; }
        var t = data.totals;
        document.getElementById('statGaming').textContent = t.wins + 'W / ' + t.losses + 'L';
        document.getElementById('statGamingDetail').textContent = t.total_wagered_gsm.toFixed(2) + ' GSM wagered';

        var html = '<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">';
        html += '<div><div class="w-stat-label">Games</div><div class="w-stat-value" style="font-size:20px">' + t.games_played + '</div></div>';
        html += '<div><div class="w-stat-label">Win Rate</div><div class="w-stat-value" style="font-size:20px;color:var(--w-green)">' + t.win_rate + '</div></div>';
        html += '<div><div class="w-stat-label">Best Streak</div><div class="w-stat-value" style="font-size:20px;color:var(--w-gold)">' + t.best_streak + '</div></div>';
        html += '<div><div class="w-stat-label">Total Won</div><div class="w-stat-value" style="font-size:20px;color:var(--w-green)">' + t.total_won_gsm.toFixed(2) + ' GSM</div></div>';
        html += '</div>';

        if (data.per_game && data.per_game.length > 0) {
            html += '<div style="margin-top:16px;border-top:1px solid var(--w-border);padding-top:12px">';
            data.per_game.forEach(function(g) {
                var icons = { chess:'♟️', checkers:'🔴', pool:'🎱', backgammon:'🎲', poker:'♠️' };
                html += '<div class="w-tx-item">';
                html += '<span class="w-tx-label">' + (icons[g.game_type] || '🎮') + ' ' + g.game_type.charAt(0).toUpperCase() + g.game_type.slice(1) + '</span>';
                html += '<span style="font-size:13px;color:var(--w-dim)">' + g.wins + 'W/' + g.losses + 'L · ' + parseFloat(g.total_won).toFixed(2) + ' GSM</span>';
                html += '</div>';
            });
            html += '</div>';
        }
        panel.innerHTML = html;
    });
}

var currentGameFilter = 'all';
function filterGameWagers(game) {
    currentGameFilter = game;
    document.querySelectorAll('.w-game-tab').forEach(function(t) { t.classList.remove('active'); });
    event.target.classList.add('active');
    loadGamingHistory();
}

function loadGamingHistory() {
    var params = { limit: 20 };
    if (currentGameFilter !== 'all') params.game_type = currentGameFilter;
    bettingApi('wager-history', params).then(function(data) {
        var panel = document.getElementById('wagerHistory');
        if (!data.success || !data.wagers || data.wagers.length === 0) {
            panel.innerHTML = '<div style="color:var(--w-dim);font-size:13px">No wagers yet. Play a game and place a wager to see your history here.</div>';
            return;
        }
        var icons = { chess:'♟️', checkers:'🔴', pool:'🎱', backgammon:'🎲', poker:'♠️' };
        var html = '';
        data.wagers.forEach(function(w) {
            var amt = w.currency === 'gsm' ? parseFloat(w.amount_gsm) : (w.currency === 'usd' ? (parseInt(w.amount_usd)/100) : (parseInt(w.amount_sol)/1e9));
            var payout = w.currency === 'gsm' ? parseFloat(w.payout_gsm) : (w.currency === 'usd' ? (parseInt(w.payout_usd)/100) : (parseInt(w.payout_sol)/1e9));
            var sym = { gsm: ' GSM', usd: ' USD', sol: ' SOL' }[w.currency];
            html += '<div class="w-wager-item">';
            html += '<div class="w-wager-game"><div class="w-wager-game-icon">' + (icons[w.game_type] || '🎮') + '</div>';
            html += '<div><div style="font-size:13px;font-weight:600">' + w.game_type.charAt(0).toUpperCase() + w.game_type.slice(1) + ' vs ' + (w.opponent_agent || 'PvP') + '</div>';
            html += '<div style="font-size:11px;color:var(--w-dim)">' + (w.created_at || '') + '</div></div></div>';
            html += '<div style="text-align:right"><div class="w-wager-result ' + w.status + '">' + w.status.toUpperCase() + '</div>';
            html += '<div style="font-size:12px;color:var(--w-dim)">Bet: ' + amt.toFixed(2) + sym + (payout > 0 ? ' → ' + payout.toFixed(2) + sym : '') + '</div></div>';
            html += '</div>';
        });
        panel.innerHTML = html;
    });
}

function loadGamingLeaderboard() {
    bettingApi('leaderboard', { currency: 'gsm', limit: 10 }).then(function(data) {
        var panel = document.getElementById('gamingLeaderboard');
        if (!data.success || !data.leaderboard || data.leaderboard.length === 0) {
            panel.innerHTML = '<div style="color:var(--w-dim);font-size:13px">No leaderboard data yet.</div>';
            return;
        }
        var html = '';
        data.leaderboard.forEach(function(l, i) {
            var rankCls = i === 0 ? 'gold' : (i === 1 ? 'silver' : (i === 2 ? 'bronze' : ''));
            html += '<div class="w-lb-item">';
            html += '<div class="w-lb-rank ' + rankCls + '">' + (i + 1) + '</div>';
            html += '<div class="w-lb-name">' + l.name + '<br><span style="font-size:11px;color:var(--w-dim)">' + l.total_games + ' games · ' + l.win_rate + '% win</span></div>';
            html += '<div class="w-lb-earned">' + parseFloat(l.total_won).toFixed(2) + ' GSM</div>';
            html += '</div>';
        });
        panel.innerHTML = html;
    });
}

// ── Toast ──
function showWalletToast(msg, icon) {
    var toast = document.getElementById('toast');
    document.getElementById('toastIcon').textContent = icon || '🪙';
    document.getElementById('toastMsg').textContent = msg;
    toast.classList.add('show');
    setTimeout(function() { toast.classList.remove('show'); }, 4000);
}

// ── VR Land ──
function landApi(action, method, body) {
    var opts = { credentials: 'same-origin' };
    if (method === 'POST') {
        opts.method = 'POST';
        opts.headers = { 'Content-Type': 'application/json' };
        opts.body = JSON.stringify(body || {});
    }
    return fetch('/api/vr-land-market.php?action=' + encodeURIComponent(action), opts).then(function(r) { return r.json(); });
}

function loadMyPlots() {
    landApi('my-plots', 'GET').then(function(data) {
        var panel = document.getElementById('myPlotsPanel');
        if (!data.success || !data.plots || data.plots.length === 0) {
            panel.innerHTML = '<div style="color:var(--w-dim);font-size:13px">No land plots owned yet. Browse the marketplace to buy your first plot!</div>';
        } else {
            var html = '';
            data.plots.forEach(function(p) {
                var zoneEmoji = {downtown:'🏙️',skyline:'🌆',beachfront:'🏖️',commercial:'🏢',residential:'🏡',wilderness:'🌲'};
                html += '<div class="w-tx-item"><div class="w-tx-type">';
                html += '<div class="w-tx-icon w-tx-icon-mine">' + (zoneEmoji[p.zone]||'📍') + '</div>';
                html += '<div><div class="w-tx-label">' + (p.plot_name || 'Plot #' + p.id) + '</div>';
                html += '<div class="w-tx-date">' + p.zone + ' · ' + p.plot_size + ' · ' + p.rarity + '</div></div></div>';
                html += '<span style="font-size:12px">' + (p.is_listed ? '<span style="color:var(--w-green)">Listed: ' + parseFloat(p.listing_price).toFixed(2) + ' GSM</span>' : '<span style="color:var(--w-dim)">Not listed</span>') + '</span></div>';
            });
            panel.innerHTML = html;
        }
        // Portfolio
        var pPanel = document.getElementById('landPortfolio');
        if (data.portfolio) {
            pPanel.innerHTML = '<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">' +
                '<div><div class="w-stat-label">Total Plots</div><div class="w-stat-value" style="font-size:20px">' + data.portfolio.total_plots + '</div></div>' +
                '<div><div class="w-stat-label">Listed</div><div class="w-stat-value" style="font-size:20px">' + data.portfolio.listed_plots + '</div></div>' +
                '<div><div class="w-stat-label">Listed Value</div><div class="w-stat-value" style="font-size:16px;color:var(--w-gold)">' + parseFloat(data.portfolio.listed_value).toFixed(2) + ' GSM</div></div>' +
                '</div>';
        }
    });
}

function loadMarketplace() {
    var zone = document.getElementById('landZoneFilter').value;
    var size = document.getElementById('landSizeFilter').value;
    var params = {};
    if (zone) params.zone = zone;
    if (size) params.size = size;
    var qs = '?action=marketplace';
    for (var k in params) qs += '&' + k + '=' + encodeURIComponent(params[k]);
    fetch('/api/vr-land-market.php' + qs, { credentials: 'same-origin' }).then(function(r) { return r.json(); }).then(function(data) {
        var panel = document.getElementById('marketplaceListings');
        if (!data.success || !data.listings || data.listings.length === 0) {
            panel.innerHTML = '<div style="color:var(--w-dim);font-size:13px">No listings found. Check back later or change your filters.</div>';
            return;
        }
        var html = '';
        data.listings.forEach(function(p) {
            var zoneEmoji = {downtown:'🏙️',skyline:'🌆',beachfront:'🏖️',commercial:'🏢',residential:'🏡',wilderness:'🌲'};
            html += '<div class="w-tx-item"><div class="w-tx-type">';
            html += '<div class="w-tx-icon w-tx-icon-mine">' + (zoneEmoji[p.zone]||'📍') + '</div>';
            html += '<div><div class="w-tx-label">' + (p.plot_name || 'Plot #' + p.id) + '</div>';
            html += '<div class="w-tx-date">' + p.zone + ' · ' + p.plot_size + ' · ' + p.rarity + '</div></div></div>';
            html += '<span style="color:var(--w-gold);font-weight:700">' + parseFloat(p.listing_price).toFixed(2) + ' GSM</span></div>';
        });
        panel.innerHTML = html;
    });
}

function loadLandStats() {
    landApi('plot-stats', 'GET').then(function(data) {
        var panel = document.getElementById('landStats');
        if (!data.success) { panel.textContent = 'No stats available'; return; }
        var s = data.stats;
        var html = '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:10px">';
        html += '<div><div class="w-stat-label">Total Plots</div><div class="w-stat-value" style="font-size:18px">' + (s.totals.total||0) + '</div></div>';
        html += '<div><div class="w-stat-label">Owned</div><div class="w-stat-value" style="font-size:18px">' + (s.totals.owned||0) + '</div></div>';
        html += '<div><div class="w-stat-label">Listed</div><div class="w-stat-value" style="font-size:18px">' + (s.totals.listed||0) + '</div></div>';
        html += '<div><div class="w-stat-label">Total Volume</div><div class="w-stat-value" style="font-size:16px;color:var(--w-gold)">' + parseFloat(s.volume.total_volume).toFixed(2) + ' GSM</div></div>';
        html += '<div><div class="w-stat-label">Trades</div><div class="w-stat-value" style="font-size:18px">' + (s.volume.total_trades||0) + '</div></div>';
        html += '</div>';
        panel.innerHTML = html;
    });
}

// ── Governance ──
function govApi(action, method, body) {
    var opts = { credentials: 'same-origin' };
    if (method === 'POST') {
        opts.method = 'POST';
        opts.headers = { 'Content-Type': 'application/json' };
        opts.body = JSON.stringify(body || {});
    }
    return fetch('/api/governance.php?action=' + encodeURIComponent(action), opts).then(function(r) { return r.json(); });
}

function loadProposals() {
    var status = document.getElementById('govStatusFilter').value;
    var category = document.getElementById('govCategoryFilter').value;
    var qs = '?action=proposals&status=' + status;
    if (category) qs += '&category=' + category;
    fetch('/api/governance.php' + qs, { credentials: 'same-origin' }).then(function(r) { return r.json(); }).then(function(data) {
        var panel = document.getElementById('proposalsList');
        if (!data.success || !data.proposals || data.proposals.length === 0) {
            panel.innerHTML = '<div style="color:var(--w-dim);font-size:13px">No proposals found.</div>';
            return;
        }
        var html = '';
        data.proposals.forEach(function(p) {
            var statusColor = {active:'var(--w-green)',passed:'var(--w-blue)',rejected:'var(--w-red)',executed:'var(--w-gold)',cancelled:'var(--w-dim)'};
            var total = parseFloat(p.votes_for) + parseFloat(p.votes_against) + parseFloat(p.votes_abstain);
            var forPct = total > 0 ? Math.round((parseFloat(p.votes_for) / total) * 100) : 0;
            html += '<div style="background:var(--w-surface2);border:1px solid var(--w-border);border-radius:12px;padding:16px;margin-bottom:10px">';
            html += '<div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:8px">';
            html += '<div><div style="font-weight:700;font-size:14px">' + p.title + '</div>';
            html += '<div style="font-size:11px;color:var(--w-dim);margin-top:2px">' + p.category + ' · by ' + (p.proposer_name||'Anon') + (p.time_remaining_human ? ' · ' + p.time_remaining_human + ' left' : '') + '</div></div>';
            html += '<span style="font-size:11px;font-weight:700;color:' + (statusColor[p.status]||'var(--w-dim)') + ';text-transform:uppercase">' + p.status + '</span></div>';
            if (p.status === 'active') {
                html += '<div style="background:var(--w-surface);border-radius:6px;height:8px;overflow:hidden;margin-bottom:6px">';
                html += '<div style="height:100%;width:' + forPct + '%;background:var(--w-green);border-radius:6px"></div></div>';
                html += '<div style="display:flex;justify-content:space-between;font-size:11px;color:var(--w-dim)">';
                html += '<span>For: ' + parseFloat(p.votes_for).toFixed(0) + ' GSM (' + forPct + '%)</span>';
                html += '<span>Against: ' + parseFloat(p.votes_against).toFixed(0) + ' GSM</span>';
                html += '<span>' + p.voter_count + ' voters</span></div>';
                html += '<div style="margin-top:8px;display:flex;gap:6px">';
                html += '<button class="w-btn" style="font-size:11px;padding:4px 12px" onclick="castVote(' + p.id + ',\'for\')">👍 For</button>';
                html += '<button class="w-btn w-btn-danger" style="font-size:11px;padding:4px 12px" onclick="castVote(' + p.id + ',\'against\')">👎 Against</button>';
                html += '<button class="w-btn w-btn-outline" style="font-size:11px;padding:4px 12px" onclick="castVote(' + p.id + ',\'abstain\')">⏭️ Abstain</button></div>';
            }
            html += '</div>';
        });
        panel.innerHTML = html;
    });
}

function castVote(proposalId, vote) {
    govApi('vote', 'POST', { proposal_id: proposalId, vote: vote }).then(function(data) {
        if (data.success) {
            showWalletToast(data.message + ' (weight: ' + parseFloat(data.weight).toFixed(2) + ' GSM)', '🗳️');
            loadProposals();
        } else {
            showWalletToast(data.error || 'Vote failed', '⚠️');
        }
    });
}

function showCreateProposal() {
    document.getElementById('createProposalForm').style.display = 'block';
}

function submitProposal() {
    var title = document.getElementById('propTitle').value;
    var desc = document.getElementById('propDescription').value;
    var cat = document.getElementById('propCategory').value;
    var days = document.getElementById('propDuration').value;
    govApi('create-proposal', 'POST', { title: title, description: desc, category: cat, duration_days: parseInt(days) }).then(function(data) {
        if (data.success) {
            showWalletToast('Proposal created!', '📝');
            document.getElementById('createProposalForm').style.display = 'none';
            document.getElementById('propTitle').value = '';
            document.getElementById('propDescription').value = '';
            loadProposals();
        } else {
            showWalletToast(data.error || 'Creation failed', '⚠️');
        }
    });
}

function loadVotingPower() {
    govApi('my-votes', 'GET').then(function(data) {
        var panel = document.getElementById('votingPowerPanel');
        if (!data.success) { panel.textContent = 'Login to see voting power'; return; }
        var html = '<div class="w-stat-value" style="font-size:24px;color:var(--w-gold);margin-bottom:8px">' + parseFloat(data.voting_power).toFixed(2) + ' GSM</div>';
        html += '<div style="font-size:12px;color:var(--w-dim);margin-bottom:8px">Your votes: ' + data.votes.length + '</div>';
        if (data.my_delegate) {
            html += '<div style="font-size:12px;margin-bottom:8px">Delegated to: <strong>' + data.my_delegate.delegate_name + '</strong></div>';
        }
        if (data.delegated_to_me && data.delegated_to_me.length > 0) {
            html += '<div style="font-size:12px;color:var(--w-green)">+' + data.delegated_to_me.length + ' delegators</div>';
        }
        panel.innerHTML = html;
    });
}

function loadGovStats() {
    govApi('governance-stats', 'GET').then(function(data) {
        var panel = document.getElementById('govStatsPanel');
        if (!data.success) { panel.textContent = 'No stats'; return; }
        var s = data.stats;
        var html = '<div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">';
        html += '<div><div class="w-stat-label">Active</div><div class="w-stat-value" style="font-size:18px">' + (s.by_status.active||0) + '</div></div>';
        html += '<div><div class="w-stat-label">Passed</div><div class="w-stat-value" style="font-size:18px;color:var(--w-green)">' + (s.by_status.passed||0) + '</div></div>';
        html += '<div><div class="w-stat-label">Voters</div><div class="w-stat-value" style="font-size:18px">' + s.unique_voters + '</div></div>';
        html += '<div><div class="w-stat-label">GSM Voted</div><div class="w-stat-value" style="font-size:16px;color:var(--w-gold)">' + parseFloat(s.total_gsm_voted).toFixed(0) + '</div></div>';
        html += '</div>';
        panel.innerHTML = html;
    });
}

// ── Trophies ──
function trophyApi(action, method, body) {
    var opts = { credentials: 'same-origin' };
    if (method === 'POST') {
        opts.method = 'POST';
        opts.headers = { 'Content-Type': 'application/json' };
        opts.body = JSON.stringify(body || {});
    }
    return fetch('/api/nft-trophies.php?action=' + encodeURIComponent(action), opts).then(function(r) { return r.json(); });
}

function loadMyTrophies() {
    trophyApi('my-trophies', 'GET').then(function(data) {
        var panel = document.getElementById('myTrophiesPanel');
        if (!data.success || !data.trophies || data.trophies.length === 0) {
            panel.innerHTML = '<div style="color:var(--w-dim);font-size:13px">No trophies earned yet. Play games and hit milestones to earn trophies!</div>';
            return;
        }
        var rarityColor = {common:'#9e9e9e',uncommon:'#4caf50',rare:'#2196f3',epic:'#9c27b0',legendary:'#ff9800',mythic:'#f44336'};
        var html = '<div style="display:flex;flex-wrap:wrap;gap:12px">';
        data.trophies.forEach(function(t) {
            html += '<div style="background:var(--w-surface2);border:2px solid ' + (rarityColor[t.rarity]||'var(--w-border)') + ';border-radius:12px;padding:14px;min-width:160px;max-width:200px;text-align:center">';
            html += '<div style="font-size:28px;margin-bottom:6px">' + (t.category === 'tournament' ? '🏆' : (t.category === 'milestone' ? '🎯' : (t.category === 'special' ? '⭐' : '🏅'))) + '</div>';
            html += '<div style="font-weight:700;font-size:13px">' + t.name + '</div>';
            html += '<div style="font-size:11px;color:' + (rarityColor[t.rarity]||'var(--w-dim)') + ';text-transform:uppercase;margin:2px 0">' + t.rarity + '</div>';
            html += '<div style="font-size:11px;color:var(--w-dim)">#' + t.serial_number + (t.max_supply ? '/' + t.max_supply : '') + '</div>';
            if (!t.nft_minted) {
                html += '<button class="w-btn" style="font-size:10px;padding:3px 8px;margin-top:6px" onclick="mintTrophy(' + t.award_id + ')">Mint NFT</button>';
            } else {
                html += '<div style="font-size:10px;color:var(--w-green);margin-top:4px">✅ Minted</div>';
            }
            html += '</div>';
        });
        html += '</div>';
        if (data.new_awards && data.new_awards.length > 0) {
            data.new_awards.forEach(function(a) { showWalletToast('🏆 New trophy: ' + a.trophy + ' (' + a.rarity + ')', '🏆'); });
        }
        panel.innerHTML = html;
    });
}

function mintTrophy(awardId) {
    trophyApi('mint-trophy', 'POST', { award_id: awardId }).then(function(data) {
        if (data.success) {
            showWalletToast(data.message + (data.gsm_cost > 0 ? ' (-' + data.gsm_cost + ' GSM)' : ''), '🏆');
            loadMyTrophies();
        } else {
            showWalletToast(data.error || 'Mint failed', '⚠️');
        }
    });
}

function loadTrophyCatalog() {
    var cat = document.getElementById('trophyCategoryFilter').value;
    var rar = document.getElementById('trophyRarityFilter').value;
    var qs = '?action=available-trophies';
    if (cat) qs += '&category=' + cat;
    if (rar) qs += '&rarity=' + rar;
    fetch('/api/nft-trophies.php' + qs, { credentials: 'same-origin' }).then(function(r) { return r.json(); }).then(function(data) {
        var panel = document.getElementById('trophyCatalog');
        if (!data.success || !data.trophies || data.trophies.length === 0) {
            panel.innerHTML = '<div style="color:var(--w-dim);font-size:13px">No trophies match your filters.</div>';
            return;
        }
        var rarityColor = {common:'#9e9e9e',uncommon:'#4caf50',rare:'#2196f3',epic:'#9c27b0',legendary:'#ff9800',mythic:'#f44336'};
        var html = '';
        data.trophies.forEach(function(t) {
            html += '<div class="w-tx-item">';
            html += '<div class="w-tx-type"><div class="w-tx-icon w-tx-icon-mine">' + (t.owned ? '✅' : '🔒') + '</div>';
            html += '<div><div class="w-tx-label">' + t.name + '</div>';
            html += '<div class="w-tx-date" style="color:' + (rarityColor[t.rarity]||'var(--w-dim)') + '">' + t.rarity.toUpperCase() + ' · ' + t.category + (t.game_type ? ' · ' + t.game_type : '') + '</div></div></div>';
            html += '<span style="font-size:12px;color:var(--w-dim)">' + t.current_supply + (t.max_supply ? '/' + t.max_supply : '') + ' minted' + (t.auto_award ? '' : ' · Manual') + '</span></div>';
        });
        panel.innerHTML = html;
    });
}

function loadTrophyStats() {
    trophyApi('trophy-stats', 'GET').then(function(data) {
        var panel = document.getElementById('trophyStatsPanel');
        if (!data.success) { panel.textContent = 'No stats'; return; }
        var s = data.stats;
        var html = '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(100px,1fr));gap:10px">';
        html += '<div><div class="w-stat-label">Trophies</div><div class="w-stat-value" style="font-size:18px">' + s.total_definitions + '</div></div>';
        html += '<div><div class="w-stat-label">Awarded</div><div class="w-stat-value" style="font-size:18px">' + s.total_awarded + '</div></div>';
        html += '<div><div class="w-stat-label">Minted NFTs</div><div class="w-stat-value" style="font-size:18px;color:var(--w-gold)">' + s.total_minted + '</div></div>';
        html += '<div><div class="w-stat-label">Holders</div><div class="w-stat-value" style="font-size:18px">' + s.unique_holders + '</div></div>';
        html += '</div>';
        panel.innerHTML = html;
    });
}

// ── Init ──
document.addEventListener('DOMContentLoaded', function() {
    loadPortfolio();
});

// Tab-specific loading
var tabLoaders = {
    portfolio: loadPortfolio,
    staking: loadStaking,
    transfer: loadTransferHistory,
    gaming: function() { loadGamingStats(); loadGamingHistory(); loadGamingLeaderboard(); },
    land: function() { loadMyPlots(); loadMarketplace(); loadLandStats(); },
    governance: function() { loadProposals(); loadVotingPower(); loadGovStats(); },
    trophies: function() { loadMyTrophies(); loadTrophyCatalog(); loadTrophyStats(); }
};
</script>
<?php endif; ?>

</body>
</html>
