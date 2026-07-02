<?php
declare(strict_types=1);
http_response_code(404);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = "404 — Not Found in the Book of Life";
$currentPage = '404';
$reqUri = $_SERVER['REQUEST_URI'] ?? '/unknown-path';
$year = date('Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/favicon.ico">
    <title><?= htmlspecialchars($pageTitle) ?> — Alfred Linux 7.77</title>
    <meta name="description" content="404 Page Not Found. The requested path could not be found across the Alfred Linux decentralized mesh swarm or the 369-hook attestation tree.">
    <link rel="stylesheet" href="/assets/fonts/inter/inter.css">
    <link rel="stylesheet" href="/assets/css/nav.css">
    <style>
        :root {
            --bg: #06060b;
            --surface: rgba(255,255,255,0.03);
            --surface-hover: rgba(255,255,255,0.06);
            --border: rgba(255,255,255,0.06);
            --border-hover: rgba(250,204,21,0.3);
            --text: #e0e0e0;
            --text-muted: #9ca3af;
            --gold: #facc15;
            --gold-light: #fde68a;
            --gold-dark: #d97706;
            --gold-glow: rgba(250,204,21,0.25);
            --accent: #6366f1;
            --accent-light: #a5b4fc;
            --accent2: #00cec9;
            --royal-purple: #7c3aed;
            --red: #ef4444;
            --text-dim: #6b7280;
            --green: #34d399;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif;
            background:var(--bg); color:var(--text); min-height:100vh;
            display:flex; flex-direction:column; overflow-x:hidden; -webkit-font-smoothing:antialiased;
            line-height:1.6;
        }

        /* Hero */
        .error-container {
            flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center;
            padding:10rem 2rem 6rem; text-align:center; position:relative;
            background: radial-gradient(ellipse at 50% 30%, rgba(250,204,21,0.12) 0%, transparent 60%),
                        radial-gradient(ellipse at 50% 70%, rgba(124,58,237,0.08) 0%, transparent 60%);
        }
        .error-container::before {
            content: ''; position: absolute; inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23facc15' fill-opacity='0.008'%3E%3Ccircle cx='30' cy='30' r='1'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            pointer-events: none;
        }

        .badge {
            display:inline-flex; align-items:center; gap:0.6rem;
            background:linear-gradient(135deg, rgba(250,204,21,0.15), rgba(99,102,241,0.15)); color:var(--gold-light);
            padding:0.5rem 1.5rem; border-radius:999px; font-size:0.85rem; font-weight:700;
            border:1px solid rgba(250,204,21,0.3); margin-bottom:2rem; text-transform:uppercase; letter-spacing:0.06em;
            box-shadow:0 0 25px rgba(250,204,21,0.2);
        }

        .error-code {
            font-size:clamp(6rem,18vw,12rem); font-weight:900; letter-spacing:-0.05em; line-height:0.9;
            background:linear-gradient(135deg,#fff 0%,var(--gold-light) 30%,var(--gold) 50%,#fff 70%,var(--gold-light) 100%);
            background-size:200% auto;
            -webkit-background-clip:text; -webkit-text-fill-color:transparent;
            background-clip:text;
            margin-bottom:1.5rem; filter:drop-shadow(0 0 30px rgba(250,204,21,0.2));
            animation:shimmer 6s linear infinite;
        }
        @keyframes shimmer { 0% { background-position: -200% center; } 100% { background-position: 200% center; } }

        h1 {
            font-size:clamp(2rem,5vw,3rem); font-weight:900; color:#fff; margin-bottom:1rem; letter-spacing:-0.02em;
        }
        p.desc {
            font-size:clamp(1.1rem,2.2vw,1.3rem); color:var(--text-muted); max-width:680px; margin:0 auto 3rem; line-height:1.7;
        }

        /* Terminal Simulation */
        .tty-box {
            background:#000; border:1px solid var(--border); border-radius:16px; padding:2rem;
            font-family:monospace; font-size:0.95rem; color:var(--text-muted); text-align:left;
            max-width:800px; width:100%; margin:0 auto 4rem; box-shadow:inset 0 0 20px rgba(0,0,0,0.8), 0 20px 40px rgba(0,0,0,0.5);
            position:relative; overflow:hidden;
        }
        .tty-box::before {
            content:''; position:absolute; top:0; left:0; right:0; height:4px;
            background:linear-gradient(90deg,var(--gold),var(--royal-purple),var(--accent2));
        }
        .tty-header { display:flex; gap:0.5rem; margin-bottom:1.5rem; align-items:center; border-bottom:1px solid var(--border); padding-bottom:1rem; }
        .tty-dot { width:12px; height:12px; border-radius:50%; }
        .tty-dot.red { background:#ff5f56; } .tty-dot.yellow { background:#ffbd2e; } .tty-dot.green { background:#27c93f; }
        .tty-title { margin-left:1rem; font-size:0.85rem; color:#888; font-weight:600; }

        .tty-line { margin-bottom:0.8rem; line-height:1.6; }
        .tty-prompt { color:var(--gold); font-weight:bold; }
        .tty-cmd { color:#fff; font-weight:600; }
        .tty-time { color:#666; font-size:0.85rem; }
        .tty-success { color:var(--accent2); font-weight:bold; }
        .tty-err { color:var(--red); font-weight:bold; }

        /* Navigation Grid */
        .nav-grid {
            display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:1.5rem;
            max-width:1000px; width:100%; margin:0 auto;
        }
        .nav-card {
            background:var(--surface); border:1px solid var(--border); border-radius:16px; padding:1.75rem;
            color:var(--text); text-decoration:none; font-weight:600; display:flex; align-items:center; gap:1.25rem; font-size:1.05rem;
            transition:all 0.3s cubic-bezier(0.4,0,0.2,1);
        }
        .nav-card:hover {
            transform:translateY(-4px); border-color:var(--border-hover);
            background:var(--surface-hover); color:#fff; box-shadow:0 12px 25px rgba(0,0,0,0.4);
        }
        .nav-card.gold { border-color:rgba(250,204,21,0.4); background:rgba(250,204,21,0.08); color:var(--gold-light); }
        .nav-card.gold:hover { background:rgba(250,204,21,0.12); color:var(--gold); box-shadow:0 12px 30px var(--gold-glow); }
        .nav-icon { font-size:1.8rem; }

        /* Footer */
        footer { padding: 5rem 2rem 3rem; background: var(--bg); border-top: 1px solid var(--border); }
        .footer-grid { max-width: 1240px; margin: 0 auto; display: grid; grid-template-columns: 2fr repeat(3, 1fr); gap: 4rem; margin-bottom: 4rem; text-align:left; }
        .footer-brand h3 { font-size: 1.4rem; font-weight: 800; color: #fff; margin-bottom: 0.75rem; }
        .footer-brand p { color: var(--text-dim); font-size: 0.95rem; line-height: 1.6; }
        .footer-col h4 { font-size: 0.85rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 1.25rem; }
        .footer-col a { display: block; color: var(--text-dim); text-decoration: none; font-size: 0.95rem; padding: 0.4rem 0; transition: color 0.2s; font-weight: 500; }
        .footer-col a:hover { color: var(--accent-light); }
        .footer-bottom {
            max-width: 1240px; margin: 0 auto; padding-top: 2.5rem; border-top: 1px solid var(--border);
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.5rem; font-size: 0.9rem; color: var(--text-dim); font-weight: 500; text-align:left;
        }
        .footer-bottom a { color: var(--accent-light); text-decoration: none; font-weight: 600; }

        @media (max-width:768px) {
            .error-container { padding:8rem 1.5rem 4rem; }
            .tty-box { padding:1.5rem; font-size:0.85rem; }
            .footer-grid { grid-template-columns: 1fr 1fr; gap: 2.5rem; }
        }
        @media (max-width: 480px) {
            .footer-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<?php @include __DIR__ . "/includes/seal-banner.php"; ?>
<?php include __DIR__ . '/includes/nav.php'; ?>

<main class="error-container">
    <div class="badge">📜 Revelation 20:15 — Not Found in the Book of Life</div>
    <div class="error-code">404</div>
    <h1>Path Not Found</h1>
    <p class="desc">The requested path could not be verified across the decentralized mesh swarm or the 369-hook Omahon attestation tree.</p>

    <!-- Simulated AI Terminal -->
    <div class="tty-box">
        <div class="tty-header">
            <div class="tty-dot red"></div><div class="tty-dot yellow"></div><div class="tty-dot green"></div>
            <div class="tty-title">alfred-haiku@sovereign-mesh:~#</div>
        </div>
        <div class="tty-line"><span class="tty-prompt">root@alfred-sovereign:~#</span> <span class="tty-cmd">alfred-haiku --search-mesh --query "<?= htmlspecialchars($reqUri) ?>"</span></div>
        <div class="tty-line"><span class="tty-time">[00:00:01]</span> Indexing local GGUF frontier weights... <span class="tty-success">OK (4 models active)</span></div>
        <div class="tty-line"><span class="tty-time">[00:00:02]</span> Querying BitTorrent P2P Swarm (f91c31...)... <span class="tty-time">0 peers holding chunk</span></div>
        <div class="tty-line"><span class="tty-time">[00:00:03]</span> Scanning 150 live-build chroot hooks... <span class="tty-time">Path not found in Omahon attestation.</span></div>
        <div class="tty-line"><span class="tty-time">[00:00:04]</span> RESULT: <span class="tty-err">404_NOT_FOUND</span>. The requested path is not written in the Book of Life.</div>
    </div>

    <!-- Quick Links -->
    <div class="nav-grid">
        <a href="/" class="nav-card"><span class="nav-icon">🏠</span> <span>Kingdom Home</span></a>
        <a href="/download" class="nav-card gold"><span class="nav-icon">🚀</span> <span>Download v7.77</span></a>
        <a href="/ai-stack" class="nav-card"><span class="nav-icon">🧠</span> <span>AI Sovereign Stack</span></a>
        <a href="/apps" class="nav-card"><span class="nav-icon">📦</span> <span>Kingdom Apps</span></a>
        <a href="/compare" class="nav-card"><span class="nav-icon">⚖️</span> <span>Compare OS</span></a>
        <a href="/pillars" class="nav-card"><span class="nav-icon">🏛️</span> <span>Nine Pillars</span></a>
    </div>
</main>

<!-- ═══ FOOTER ═══ -->
<footer>
    <div class="footer-grid">
        <div class="footer-brand">
            <h3 style="background:linear-gradient(135deg,#fff,var(--gold-light));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">Alfred Linux 7.77 GA</h3>
            <p style="color:var(--gold-dark);font-style:italic;margin-bottom:0.75rem;font-weight:700;">&ldquo;Kingdom of God Edition&rdquo;</p>
            <p>The world&rsquo;s first AI-native operating system. Sealed by the Omahon &mdash; the breath of God, raised incorruptible. Pre-baked with 4 Frontier GGUF AI models and 369 Attested Build Hooks. The Word of God endures in silicon and code. Built by Commander Danny William Perez for the glory of God and His Kingdom.</p>
            <p style="margin-top:1rem;color:var(--gold-dark);font-style:italic;font-size:0.9rem;font-weight:600;">&ldquo;The grass withereth, the flower fadeth: but the word of our God shall stand for ever.&rdquo; &mdash; Isaiah 40:8</p>
            <p style="margin-top:1rem;font-size:0.85rem;"><a href="https://lavocat.ca/journal?read=9&lang=en" style="color:var(--gold-dark);font-weight:700;">Commander&rsquo;s Journal</a> &middot; <a href="https://gositeme.com/sovereignty" style="color:var(--gold-dark);font-weight:700;">Sovereignty Declarations</a></p>
            <p style="margin-top:0.5rem;font-size:0.85rem;font-weight:600;">AGPL-3.0 License &middot; <span style="color:var(--gold-dark);">Soli Deo Gloria</span></p>
        </div>
        <div class="footer-col">
            <h4>Product</h4>
            <a href="/#features">Features</a>
            <a href="/#architecture">Architecture</a>
            <a href="/#editions">Editions</a>
            <a href="/docs">Documentation</a>
            <a href="/download">Download</a>
            <a href="/ai-stack">Sovereign AI Stack</a>
        </div>
        <div class="footer-col">
            <h4>Ecosystem</h4>
            <a href="https://gositeme.com">GoSiteMe</a>
            <a href="https://alfred-mobile.com">Alfred Mobile</a>
            <a href="https://meta-dome.com">MetaDome</a>
            <a href="https://gocodeme.com">GoCodeMe</a>
            <a href="https://quantum-linux.com">Quantum Linux</a>
        </div>
        <div class="footer-col">
            <h4>Community</h4>
            <a href="/forge/">GoForge</a>
            <a href="https://discord.gg/alfredlinux">Discord</a>
            <a href="https://x.com/AlfredGoSiteMe">Twitter / X</a>
            <a href="https://dev.to/AlfredGoSiteMe">Dev.to</a>
        </div>
    </div>
    <div class="footer-bottom">
        <div>&copy; <?= $year ?> <a href="https://gositeme.com">GoSiteMe Inc.</a> &mdash; Commander Danny William Perez</div>
        <div>
            <a href="https://alfred-mobile.com">Mobile</a> &middot;
            <a href="https://meta-dome.com">MetaDome</a> &middot;
            <a href="https://gositeme.com">GoSiteMe</a>
        </div>
    </div>
</footer>

</body>
</html>
