<?php
$page_title = "Developer Community — GoSiteMe";
$page_description = "Join the GoSiteMe developer community. Build with Alfred IDE, earn GSM tokens, contribute to open source, and connect with builders worldwide.";
$page_canonical = "https://root.com/community.php";
require_once __DIR__ . '/includes/site-header.inc.php';

$isLoggedIn = !empty($_SESSION['logged_in']);
$clientId = (int) ($_SESSION['client_id'] ?? 0);
$userName = htmlspecialchars($_SESSION['client_name'] ?? 'Builder', ENT_QUOTES, 'UTF-8');
?>
<style>
:root {
    --comm-bg: #0a0a14;
    --comm-surface: #12121e;
    --comm-surface-2: #1a1a2e;
    --comm-border: rgba(255,255,255,0.06);
    --comm-text: #e0e0e0;
    --comm-muted: #8a8ab0;
    --comm-purple: #7d00ff;
    --comm-cyan: #00d4ff;
    --comm-green: #10b981;
    --comm-gold: #f5a623;
}
.comm-hero {
    text-align: center;
    padding: 5rem 2rem 3rem;
    background: radial-gradient(ellipse at 50% 0%, rgba(125,0,255,0.12) 0%, transparent 60%);
}
.comm-hero h1 { font-size: 2.8rem; font-weight: 800; margin: 0 0 1rem; background: linear-gradient(135deg, var(--comm-cyan), var(--comm-purple)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.comm-hero p { font-size: 1.1rem; color: var(--comm-muted); max-width: 600px; margin: 0 auto 2rem; }
.comm-stats { display: flex; justify-content: center; gap: 3rem; flex-wrap: wrap; margin-bottom: 2rem; }
.comm-stat { text-align: center; }
.comm-stat .val { font-size: 2rem; font-weight: 800; font-family: 'Space Mono', monospace; color: var(--comm-cyan); }
.comm-stat .label { font-size: 0.75rem; color: var(--comm-muted); text-transform: uppercase; letter-spacing: 1px; }
.comm-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem; max-width: 1200px; margin: 0 auto; padding: 0 2rem 3rem; }
.comm-card { background: var(--comm-surface); border: 1px solid var(--comm-border); border-radius: 16px; padding: 2rem; transition: all 0.3s; }
.comm-card:hover { border-color: rgba(125,0,255,0.3); transform: translateY(-2px); box-shadow: 0 8px 32px rgba(0,0,0,0.3); }
.comm-card h3 { font-size: 1.2rem; margin: 0 0 0.75rem; color: #fff; }
.comm-card p { font-size: 0.9rem; color: var(--comm-muted); margin: 0 0 1.5rem; line-height: 1.6; }
.comm-card .icon { font-size: 2rem; margin-bottom: 1rem; }
.comm-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 10px; font-weight: 600; font-size: 0.85rem; text-decoration: none; transition: all 0.2s; }
.comm-btn-primary { background: var(--comm-purple); color: #fff; }
.comm-btn-primary:hover { background: #9333ea; }
.comm-btn-outline { border: 1px solid var(--comm-border); color: var(--comm-text); }
.comm-btn-outline:hover { border-color: var(--comm-cyan); color: var(--comm-cyan); }
.comm-section { max-width: 1200px; margin: 0 auto; padding: 0 2rem; }
.comm-section h2 { font-size: 1.8rem; font-weight: 800; margin: 3rem 0 1.5rem; text-align: center; }
.comm-feed { max-width: 800px; margin: 0 auto 3rem; }
.comm-feed-item { background: var(--comm-surface); border: 1px solid var(--comm-border); border-radius: 12px; padding: 1.5rem; margin-bottom: 1rem; }
.comm-feed-item .meta { font-size: 0.8rem; color: var(--comm-muted); margin-bottom: 0.5rem; }
.comm-feed-item .title { font-weight: 700; color: #fff; font-size: 1rem; margin-bottom: 0.5rem; }
.comm-feed-item .body { font-size: 0.9rem; color: var(--comm-muted); line-height: 1.6; }
.comm-leaderboard { margin: 0 auto 3rem; max-width: 600px; }
.comm-lb-row { display: flex; align-items: center; gap: 1rem; padding: 12px 16px; border-bottom: 1px solid var(--comm-border); }
.comm-lb-row:last-child { border-bottom: none; }
.comm-lb-rank { font-size: 1.2rem; font-weight: 800; width: 32px; text-align: center; }
.comm-lb-name { flex: 1; font-weight: 600; }
.comm-lb-tokens { font-family: monospace; color: var(--comm-cyan); font-weight: 700; }
.earn-table { width: 100%; border-collapse: collapse; margin: 1.5rem 0; }
.earn-table td, .earn-table th { padding: 12px 16px; text-align: left; border-bottom: 1px solid var(--comm-border); }
.earn-table th { color: var(--comm-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px; }
.earn-table td:last-child { text-align: right; color: var(--comm-green); font-weight: 700; font-family: monospace; }
@media (max-width: 768px) {
    .comm-hero h1 { font-size: 2rem; }
    .comm-grid { grid-template-columns: 1fr; }
    .comm-stats { gap: 1.5rem; }
}
</style>

<div class="comm-hero">
    <h1>Builder Community</h1>
    <p>Build. Ship. Earn. Connect with developers building on the GoSiteMe platform.</p>

    <div class="comm-stats">
        <div class="comm-stat"><div class="val" id="commDevs">—</div><div class="label">Builders</div></div>
        <div class="comm-stat"><div class="val" id="commWorlds">22</div><div class="label">VR Worlds</div></div>
        <div class="comm-stat"><div class="val" id="commTools">856</div><div class="label">MCP Tools</div></div>
        <div class="comm-stat"><div class="val" id="commGsm">1B</div><div class="label">GSM Supply</div></div>
    </div>

    <?php if (!$isLoggedIn): ?>
    <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
        <a href="/register" class="comm-btn comm-btn-primary"><i class="fas fa-rocket"></i> Join the Community</a>
        <a href="/docs/quickstart.php" class="comm-btn comm-btn-outline"><i class="fas fa-book"></i> Quickstart Guide</a>
    </div>
    <?php else: ?>
    <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
        <a href="/alfred-ide.php" class="comm-btn comm-btn-primary"><i class="fas fa-code"></i> Open Alfred IDE</a>
        <a href="/developer-portal.php" class="comm-btn comm-btn-outline"><i class="fas fa-tools"></i> Developer Portal</a>
        <a href="/blockchain.php" class="comm-btn comm-btn-outline">◈ GSM Wallet</a>
    </div>
    <?php endif; ?>
</div>

<!-- ═══ CONNECT CHANNELS ═══ -->
<div class="comm-grid">
    <div class="comm-card">
        <div class="icon">💬</div>
        <h3>Community Chat</h3>
        <p>Real-time discussions with builders, AI agents, and the GoSiteMe team. Get help, share ideas, show your work.</p>
        <a href="/team-chat.php" class="comm-btn comm-btn-primary"><i class="fas fa-comments"></i> Open Chat</a>
    </div>
    <div class="comm-card">
        <div class="icon">🛠️</div>
        <h3>Alfred IDE</h3>
        <p>Cloud development environment with AI pair programming, 856+ MCP tools, and the full GoSiteMe API at your fingertips.</p>
        <a href="/alfred-ide.php" class="comm-btn comm-btn-primary"><i class="fas fa-code"></i> Launch IDE</a>
    </div>
    <div class="comm-card">
        <div class="icon">📚</div>
        <h3>Documentation</h3>
        <p>API references, SDK guides, extension tutorials, and quickstart guides for every platform surface.</p>
        <a href="/docs/quickstart.php" class="comm-btn comm-btn-outline"><i class="fas fa-book-open"></i> Quickstart</a>
    </div>
    <div class="comm-card">
        <div class="icon">🎮</div>
        <h3>Game Dev</h3>
        <p>Build VR worlds, card games, racing games, or anything you can imagine. 20 worlds already live. Your game earns GSM.</p>
        <a href="/game-lobby.php" class="comm-btn comm-btn-outline"><i class="fas fa-gamepad"></i> Game Lobby</a>
    </div>
    <div class="comm-card">
        <div class="icon">🌐</div>
        <h3>Open Source</h3>
        <p>Alfred Linux, MCP server, build hooks, SDKs — contribute to the platform infrastructure and earn bounties.</p>
        <a href="/open-source/" class="comm-btn comm-btn-outline"><i class="fab fa-github"></i> Contribute</a>
    </div>
    <div class="comm-card">
        <div class="icon">◈</div>
        <h3>Token Economy</h3>
        <p>Earn GSM by building, playing, contributing. GSM is live on Solana mainnet — connect your wallet or use the custodial wallet. Every action counts.</p>
        <a href="/blockchain.php" class="comm-btn comm-btn-outline">◈ Blockchain</a>
    </div>
</div>

<!-- ═══ EARN GSM ═══ -->
<div class="comm-section">
    <h2>How to Earn GSM</h2>
    <div class="comm-feed" style="max-width:700px;">
        <div class="comm-card">
            <table class="earn-table">
                <thead><tr><th>Activity</th><th>Tokens</th></tr></thead>
                <tbody>
                    <tr><td>🏗️ Ship a VR world or game</td><td>+5,000 GSM</td></tr>
                    <tr><td>🐛 Fix a bug (verified PR)</td><td>+500 GSM</td></tr>
                    <tr><td>📝 Write documentation</td><td>+200 GSM</td></tr>
                    <tr><td>🎮 Play a game (per session)</td><td>+10 GSM</td></tr>
                    <tr><td>🌐 Visit a VR world</td><td>+5 GSM</td></tr>
                    <tr><td>💡 Report a security issue</td><td>+1,000–10,000 GSM</td></tr>
                    <tr><td>🧩 Build an MCP tool</td><td>+1,000 GSM</td></tr>
                    <tr><td>📦 Publish an extension</td><td>+2,000 GSM</td></tr>
                    <tr><td>👥 Refer a builder</td><td>+500 GSM</td></tr>
                    <tr><td>🗳️ Daily login streak (7d)</td><td>+100 GSM</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ═══ BUILDER LEADERBOARD ═══ -->
<div class="comm-section">
    <h2>🏆 Builder Leaderboard</h2>
    <div class="comm-leaderboard" id="commLeaderboard">
        <div class="comm-card" style="text-align:center;color:var(--comm-muted);">
            Loading leaderboard...
        </div>
    </div>
</div>

<!-- ═══ RECENT ACTIVITY ═══ -->
<div class="comm-section">
    <h2>Recent Activity</h2>
    <div class="comm-feed" id="commFeed">
        <div class="comm-feed-item">
            <div class="meta">🚀 Platform · April 8, 2026</div>
            <div class="title">Alfred Linux 4.0 GA Released</div>
            <div class="body">First distro shipping Kernel 7.0.0 with 32 security modules, full-disk encryption, and Alfred IDE built in. Available at <a href="https://alfredlinux.com" style="color:var(--comm-cyan);">alfredlinux.com</a>.</div>
        </div>
        <div class="comm-feed-item">
            <div class="meta">◈ Economy · April 8, 2026</div>
            <div class="title">GSM Token Economy Live</div>
            <div class="body">Custodial wallets auto-created for all users. One-click Phantom/Solflare wallet connect shipped. Earn GSM by building, playing, and contributing.</div>
        </div>
        <div class="comm-feed-item">
            <div class="meta">🎮 Play · April 8, 2026</div>
            <div class="title">20 VR Worlds Now Tracked Live</div>
            <div class="body">All VR worlds now send heartbeats to the game lobby. Real-time player counts, viewer tracking, and AI agent presence visible in the <a href="/game-lobby.php" style="color:var(--comm-cyan);">Game Lobby</a>.</div>
        </div>
        <div class="comm-feed-item">
            <div class="meta">🤖 Builders · April 8, 2026</div>
            <div class="title">Alfred Agent — Anthropic Integration Live</div>
            <div class="body">Alfred Agent now runs Claude Sonnet with 16 built-in tools, MCP integration, and multi-provider support (Anthropic, OpenAI, Groq).</div>
        </div>
    </div>
</div>

<div style="text-align:center;padding:3rem 2rem;">
    <p style="color:var(--comm-muted);font-size:0.9rem;">
        Everything here is real. No waitlists, no vaporware. Start building now.
    </p>
    <?php if (!$isLoggedIn): ?>
    <a href="/register" class="comm-btn comm-btn-primary" style="font-size:1rem;padding:14px 32px;margin-top:1rem;"><i class="fas fa-rocket"></i> Create Free Account</a>
    <?php endif; ?>
</div>

<script>
(function(){
    // Load builder count from API
    fetch('/api/solana-blockchain.php?action=blockchain-stats')
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.success) {
                var el = document.getElementById('commDevs');
                if (el) el.textContent = d.data.wallets_linked || '—';
            }
        }).catch(function(){});

    // Load leaderboard (top earners)
    <?php if ($isLoggedIn): ?>
    fetch('/api/solana-blockchain.php?action=blockchain-stats')
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (!d.success) return;
            var lb = document.getElementById('commLeaderboard');
            // Placeholder until we have real leaderboard data
            lb.innerHTML = '<div class="comm-card" style="text-align:center;color:var(--comm-muted);padding:2rem;">Leaderboard populates as builders earn GSM tokens.<br><a href="/blockchain.php" style="color:var(--comm-cyan);">Connect wallet to start earning →</a></div>';
        }).catch(function(){});
    <?php endif; ?>
})();
</script>

<?php require_once __DIR__ . '/includes/site-footer.inc.php'; ?>
