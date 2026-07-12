<?php
/**
 * Alfred Linux — Getting Started
 * Practical quickstart guide: Download → Flash → Boot → You're in.
 *
 * Built by Alfred for Commander Danny William Perez
 * GoSiteMe Inc. — 2026
 */
$year = date('Y');
$currentPage = 'getting-started';
require_once __DIR__ . '/includes/ga-release-state.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Getting Started — Alfred Linux</title>
    <meta name="description" content="Get up and running with Alfred Linux in five steps. Download the ISO, write it to USB, boot, and enter the Kingdom. A complete quickstart guide.">
    <meta property="og:title" content="Getting Started — Alfred Linux">
    <meta property="og:description" content="Download. Flash. Boot. You're in. The complete quickstart guide for Alfred Linux 7.77 Alpha Matrix.">
    <meta property="og:type" content="article">
    <meta property="og:url" content="https://alfredlinux.com/getting-started">
    <meta property="og:image" content="https://alfredlinux.com/og-default.svg">
    <meta property="og:site_name" content="Alfred Linux">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Getting Started — Alfred Linux">
    <meta name="twitter:description" content="Download. Flash. Boot. You're in. The complete quickstart guide for Alfred Linux 7.77.">
    <meta name="twitter:image" content="https://alfredlinux.com/og-default.svg">
    <link rel="canonical" href="https://alfredlinux.com/getting-started">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/img/favicon/favicon-16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/img/favicon/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#D4AF37">
    <link rel="stylesheet" href="/assets/fonts/inter/inter.css">
    <link rel="stylesheet" href="/assets/css/nav.css">
    <style>
        :root {
            --bg: #06060b;
            --surface: rgba(255,255,255,0.03);
            --surface-hover: rgba(255,255,255,0.06);
            --border: rgba(255,255,255,0.06);
            --border-hover: rgba(255,255,255,0.12);
            --text: #e0e0e0;
            --text-muted: #9ca3af;
            --text-dim: #6b7280;
            --gold: #ffd700;
            --gold-dim: #c8a02b;
            --cyan: #22d3ee;
            --accent: #6366f1;
            --accent-light: #a5b4fc;
            --green: #34d399;
            --red: #ef4444;
            --amber: #f59e0b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.7;
            min-height: 100vh;
        }

        a { color: var(--accent-light); text-decoration: none; transition: color 0.2s; }
        a:hover { color: #fff; text-decoration: underline; }

        /* ═══════════════════ HERO ═══════════════════ */
        .hero {
            position: relative;
            padding: clamp(5rem, 12vw, 9rem) 2rem 4rem;
            text-align: center;
            overflow: hidden;
            border-bottom: 1px solid var(--border);
        }
        .hero::before {
            content: '';
            position: absolute;
            top: -40%;
            left: 50%;
            transform: translateX(-50%);
            width: 800px;
            height: 800px;
            background: radial-gradient(circle, rgba(255,215,0,0.06) 0%, rgba(99,102,241,0.03) 40%, transparent 70%);
            pointer-events: none;
        }
        .hero-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: block;
            animation: pulse-glow 3s ease-in-out infinite;
        }
        @keyframes pulse-glow {
            0%, 100% { opacity: 0.7; filter: drop-shadow(0 0 8px rgba(255,215,0,0.3)); }
            50% { opacity: 1; filter: drop-shadow(0 0 20px rgba(255,215,0,0.6)); }
        }
        .hero h1 {
            font-size: clamp(2.4rem, 6vw, 4rem);
            font-weight: 800;
            letter-spacing: -0.02em;
            margin-bottom: 0.75rem;
            background: linear-gradient(135deg, #fff 30%, var(--gold) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hero .subtitle {
            font-size: clamp(1.1rem, 2.5vw, 1.35rem);
            color: var(--text-muted);
            font-weight: 400;
            letter-spacing: 0.05em;
            margin-bottom: 2rem;
        }
        .hero .subtitle strong {
            color: var(--gold);
            font-weight: 600;
        }

        /* ═══════════════════ PROGRESS BAR ═══════════════════ */
        .progress-bar {
            display: flex;
            justify-content: center;
            gap: 0;
            max-width: 700px;
            margin: 0 auto;
            position: relative;
        }
        .progress-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            position: relative;
            cursor: pointer;
            text-decoration: none !important;
        }
        .progress-step:hover .step-dot { transform: scale(1.3); box-shadow: 0 0 16px rgba(255,215,0,0.4); }
        .step-dot {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--surface);
            border: 2px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--text-muted);
            transition: all 0.3s ease;
            position: relative;
            z-index: 2;
        }
        .step-dot.active {
            background: var(--gold);
            color: #000;
            border-color: var(--gold);
            box-shadow: 0 0 20px rgba(255,215,0,0.3);
        }
        .step-label {
            font-size: 0.7rem;
            color: var(--text-dim);
            margin-top: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            white-space: nowrap;
        }
        .progress-step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 17px;
            left: calc(50% + 18px);
            width: calc(100% - 36px);
            height: 2px;
            background: rgba(255,255,255,0.08);
            z-index: 1;
        }

        /* ═══════════════════ CONTAINER ═══════════════════ */
        .container {
            max-width: 880px;
            margin: 0 auto;
            padding: 0 2rem 5rem;
        }

        /* ═══════════════════ SECTION HEADERS ═══════════════════ */
        .section-header {
            text-align: center;
            margin: 4rem 0 2rem;
        }
        .section-header h2 {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .section-header p {
            color: var(--text-muted);
            max-width: 600px;
            margin: 0 auto;
        }

        /* ═══════════════════ REQUIREMENTS GRID ═══════════════════ */
        .req-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1rem;
            margin: 2rem 0 3rem;
        }
        .req-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.25rem;
            transition: all 0.3s ease;
        }
        .req-card:hover {
            border-color: var(--border-hover);
            background: var(--surface-hover);
            transform: translateY(-2px);
        }
        .req-card .req-icon {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            display: block;
        }
        .req-card h3 {
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: #fff;
        }
        .req-card p {
            font-size: 0.85rem;
            color: var(--text-muted);
            line-height: 1.5;
        }
        .req-card .req-tag {
            display: inline-block;
            margin-top: 0.5rem;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-weight: 600;
        }
        .tag-required { background: rgba(239,68,68,0.15); color: var(--red); }
        .tag-recommended { background: rgba(255,215,0,0.12); color: var(--gold); }
        .tag-optional { background: rgba(34,211,238,0.12); color: var(--cyan); }

        /* ═══════════════════ STEP CARDS ═══════════════════ */
        .step-card {
            position: relative;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2.5rem 2rem 2rem 2rem;
            margin: 2.5rem 0;
            transition: border-color 0.3s ease;
        }
        .step-card:hover {
            border-color: rgba(255,215,0,0.15);
        }
        .step-number {
            position: absolute;
            top: -20px;
            left: 2rem;
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--gold), #b8860b);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            font-weight: 800;
            color: #000;
            box-shadow: 0 4px 16px rgba(255,215,0,0.25);
        }
        .step-card h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            padding-left: 3rem;
        }
        .step-card .step-desc {
            color: var(--text-muted);
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
        }

        /* ═══════════════════ CODE BLOCKS ═══════════════════ */
        .code-block {
            position: relative;
            background: #0d0d14;
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 10px;
            margin: 1rem 0;
            overflow: hidden;
        }
        .code-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.6rem 1rem;
            background: rgba(255,255,255,0.03);
            border-bottom: 1px solid rgba(255,255,255,0.04);
        }
        .code-label {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .copy-btn {
            background: none;
            border: 1px solid rgba(255,255,255,0.1);
            color: var(--text-muted);
            font-size: 0.75rem;
            padding: 0.3rem 0.7rem;
            border-radius: 6px;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        .copy-btn:hover { color: var(--gold); border-color: var(--gold); }
        .copy-btn.copied { color: var(--green); border-color: var(--green); }
        .code-body {
            padding: 1rem 1.25rem;
            overflow-x: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(255,255,255,0.1) transparent;
        }
        .code-body code {
            font-family: 'JetBrains Mono', 'Fira Code', ui-monospace, monospace;
            font-size: 0.85rem;
            line-height: 1.6;
            color: var(--green);
            white-space: pre;
            display: block;
        }
        .code-body .comment { color: var(--text-dim); }
        .code-body .flag { color: var(--cyan); }
        .code-body .path { color: var(--gold); }

        /* ═══════════════════ TAB SYSTEM ═══════════════════ */
        .tabs {
            display: flex;
            gap: 0;
            border-bottom: 1px solid var(--border);
            margin-bottom: 1rem;
        }
        .tab-btn {
            background: none;
            border: none;
            border-bottom: 2px solid transparent;
            color: var(--text-muted);
            font-size: 0.85rem;
            font-weight: 600;
            padding: 0.6rem 1.2rem;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.2s;
        }
        .tab-btn:hover { color: var(--text); }
        .tab-btn.active { color: var(--gold); border-bottom-color: var(--gold); }
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }

        /* ═══════════════════ INFO BOXES ═══════════════════ */
        .info-box {
            display: flex;
            gap: 0.75rem;
            padding: 1rem 1.25rem;
            border-radius: 10px;
            margin: 1rem 0;
            font-size: 0.9rem;
            line-height: 1.5;
        }
        .info-box .info-icon { font-size: 1.2rem; flex-shrink: 0; margin-top: 0.1rem; }
        .info-tip { background: rgba(34,211,238,0.06); border: 1px solid rgba(34,211,238,0.15); color: var(--cyan); }
        .info-warn { background: rgba(245,158,11,0.06); border: 1px solid rgba(245,158,11,0.15); color: var(--amber); }
        .info-note { background: rgba(99,102,241,0.06); border: 1px solid rgba(99,102,241,0.15); color: var(--accent-light); }

        /* ═══════════════════ CHECKLIST ═══════════════════ */
        .checklist {
            list-style: none;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 0.75rem;
            margin: 1rem 0;
        }
        .checklist li {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            font-size: 0.9rem;
            color: var(--text);
            padding: 0.75rem 1rem;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            transition: all 0.2s;
        }
        .checklist li:hover { border-color: var(--border-hover); }
        .checklist li .check { color: var(--green); font-weight: 700; flex-shrink: 0; }

        /* ═══════════════════ BOOT FLOW ═══════════════════ */
        .boot-flow {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0;
            flex-wrap: wrap;
            margin: 1.5rem 0;
            padding: 1.5rem;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
        }
        .boot-stage {
            text-align: center;
            padding: 0.75rem 1rem;
        }
        .boot-stage .stage-name {
            font-size: 0.85rem;
            font-weight: 600;
            color: #fff;
            margin-bottom: 0.25rem;
        }
        .boot-stage .stage-desc {
            font-size: 0.72rem;
            color: var(--text-dim);
        }
        .boot-arrow {
            color: var(--gold-dim);
            font-size: 1.2rem;
            padding: 0 0.25rem;
        }

        /* ═══════════════════ BOOT TABLE ═══════════════════ */
        .boot-table-wrap {
            overflow-x: auto;
            margin: 1.5rem 0;
            border-radius: 12px;
            border: 1px solid var(--border);
        }
        .boot-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }
        .boot-table th {
            text-align: left;
            padding: 0.85rem 1rem;
            background: rgba(255,215,0,0.06);
            color: var(--gold);
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            border-bottom: 1px solid var(--border);
        }
        .boot-table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--border);
            vertical-align: top;
        }
        .boot-table tr:last-child td { border-bottom: none; }
        .boot-table tr:hover td { background: var(--surface-hover); }
        .boot-table .mode-name {
            color: var(--cyan);
            font-weight: 600;
            white-space: nowrap;
        }
        .boot-table .mode-desc { color: var(--text-muted); }

        /* ═══════════════════ LINKS SECTION ═══════════════════ */
        .links-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
        }
        .link-card {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.25rem;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            text-decoration: none !important;
            transition: all 0.3s ease;
        }
        .link-card:hover {
            border-color: var(--gold-dim);
            background: var(--surface-hover);
            transform: translateY(-2px);
        }
        .link-card .link-icon {
            font-size: 2rem;
            flex-shrink: 0;
        }
        .link-card h3 {
            font-size: 0.95rem;
            font-weight: 600;
            color: #fff;
            margin-bottom: 0.15rem;
        }
        .link-card p {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        /* ═══════════════════ CTA BOX ═══════════════════ */
        .cta-box {
            text-align: center;
            padding: 3rem 2rem;
            margin: 3rem 0 1rem;
            background: radial-gradient(ellipse at center, rgba(255,215,0,0.04), transparent 70%);
            border: 1px solid rgba(255,215,0,0.1);
            border-radius: 16px;
        }
        .cta-box h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .cta-box p {
            color: var(--text-muted);
            margin-bottom: 1.5rem;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }
        .cta-btn {
            display: inline-block;
            padding: 0.85rem 2rem;
            border: 2px solid var(--gold);
            color: var(--gold);
            font-weight: 700;
            font-size: 0.95rem;
            border-radius: 10px;
            text-decoration: none !important;
            letter-spacing: 0.03em;
            transition: all 0.3s ease;
            margin: 0.35rem;
        }
        .cta-btn:hover {
            background: var(--gold);
            color: #000;
            box-shadow: 0 4px 24px rgba(255,215,0,0.25);
            transform: translateY(-2px);
        }
        .cta-btn.secondary {
            border-color: rgba(255,255,255,0.15);
            color: var(--text-muted);
        }
        .cta-btn.secondary:hover {
            border-color: var(--accent-light);
            color: var(--accent-light);
            background: transparent;
            box-shadow: none;
        }

        /* ═══════════════════ FOOTER ═══════════════════ */
        footer {
            text-align: center;
            padding: 3rem 2rem 4rem;
            color: var(--text-dim);
            font-size: 0.85rem;
            border-top: 1px solid var(--border);
        }
        footer a { color: var(--gold-dim); }

        /* ═══════════════════ RESPONSIVE ═══════════════════ */
        @media (max-width: 640px) {
            .hero { padding: 4rem 1.5rem 3rem; }
            .container { padding: 0 1.25rem 4rem; }
            .step-card { padding: 2rem 1.25rem 1.5rem; }
            .step-card h2 { padding-left: 2.5rem; font-size: 1.25rem; }
            .step-number { left: 1.25rem; }
            .progress-bar { gap: 0; }
            .step-label { font-size: 0.6rem; }
            .step-dot { width: 28px; height: 28px; font-size: 0.7rem; }
            .progress-step:not(:last-child)::after { top: 13px; left: calc(50% + 14px); width: calc(100% - 28px); }
            .boot-flow { flex-direction: column; gap: 0; }
            .boot-arrow { transform: rotate(90deg); }
            .req-grid { grid-template-columns: 1fr; }
            .checklist { grid-template-columns: 1fr; }
        }

        /* ═══════════════════ ANIMATIONS ═══════════════════ */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-in {
            opacity: 0;
            animation: fadeUp 0.6s ease forwards;
        }
        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }
    </style>
</head>
<body>
<?php include __DIR__ . '/includes/nav.php'; ?>

<!-- ═══════════════════ HERO ═══════════════════ -->
<header class="hero">
    <span class="hero-icon">🚀</span>
    <h1>Getting Started</h1>
    <p class="subtitle"><strong>Download.</strong> Flash. Boot. <strong>You're in.</strong></p>

    <div class="progress-bar">
        <a href="#step-1" class="progress-step">
            <div class="step-dot active">1</div>
            <span class="step-label">Download</span>
        </a>
        <a href="#step-2" class="progress-step">
            <div class="step-dot">2</div>
            <span class="step-label">Write USB</span>
        </a>
        <a href="#step-3" class="progress-step">
            <div class="step-dot">3</div>
            <span class="step-label">Boot</span>
        </a>
        <a href="#step-4" class="progress-step">
            <div class="step-dot">4</div>
            <span class="step-label">First Boot</span>
        </a>
        <a href="#step-5" class="progress-step">
            <div class="step-dot">5</div>
            <span class="step-label">Install</span>
        </a>
    </div>
</header>

<div class="container">

    <!-- ═══════════════════ REQUIREMENTS ═══════════════════ -->
    <div class="section-header animate-in">
        <h2>⚙️ System Requirements</h2>
        <p>What you need before you begin</p>
    </div>

    <div class="req-grid">
        <div class="req-card">
            <span class="req-icon">🖥️</span>
            <h3>x86_64 CPU</h3>
            <p>Intel or AMD 64-bit processor</p>
            <span class="req-tag tag-required">Required</span>
        </div>
        <div class="req-card">
            <span class="req-icon">🧠</span>
            <h3>8 GB RAM Minimum</h3>
            <p>16 GB recommended for AI workloads</p>
            <span class="req-tag tag-required">Required</span>
        </div>
        <div class="req-card">
            <span class="req-icon">💾</span>
            <h3>128 GB+ USB 3.0 Drive</h3>
            <p>For the ~80 GB Alpha Matrix ISO</p>
            <span class="req-tag tag-required">Required</span>
        </div>
        <div class="req-card">
            <span class="req-icon">🔧</span>
            <h3>UEFI or Legacy BIOS</h3>
            <p>Both boot modes supported</p>
            <span class="req-tag tag-required">Required</span>
        </div>
        <div class="req-card">
            <span class="req-icon">🎮</span>
            <h3>NVIDIA GPU</h3>
            <p>610.43.02 drivers pre-installed</p>
            <span class="req-tag tag-recommended">Recommended</span>
        </div>
        <div class="req-card">
            <span class="req-icon">🥽</span>
            <h3>VR Headset</h3>
            <p>Quest 2/3/Pro supported via ALVR</p>
            <span class="req-tag tag-optional">Optional</span>
        </div>
    </div>

    <!-- ═══════════════════ STEP 1: DOWNLOAD ═══════════════════ -->
    <div class="step-card" id="step-1">
        <div class="step-number">1</div>
        <h2>Download the ISO</h2>
        <p class="step-desc">Get the Alfred Linux Alpha Matrix image — the largest, most complete Linux distribution ever built.</p>

        <div style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: center; margin-bottom: 1.5rem;">
            <a href="/download" class="cta-btn" style="margin: 0;">⬇ Download Page</a>
            <div style="font-size: 0.85rem; color: var(--text-muted);">
                <strong style="color: #fff;"><?= htmlspecialchars($gaIsoBasename) ?>.iso</strong><br>
                ~80 GB &middot; Torrent available for faster download
            </div>
        </div>

        <div class="info-box info-tip">
            <span class="info-icon">💡</span>
            <div>
                <strong>Use the torrent.</strong> At ~80 GB, peer-to-peer is significantly faster than direct download. The download page includes a WebTorrent client — no extra software needed.
            </div>
        </div>

        <div class="info-box info-warn">
            <span class="info-icon">⚠️</span>
            <div>
                <strong>Always verify your download.</strong> Check the SHA-256 and BLAKE3 checksums on the download page before writing to USB. This ensures the ISO wasn't corrupted or tampered with.
            </div>
        </div>

        <div class="code-block">
            <div class="code-header">
                <span class="code-label">Verify Checksum</span>
                <button class="copy-btn" onclick="copyCode(this)">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                    Copy
                </button>
            </div>
            <div class="code-body">
                <code><span class="comment"># Verify SHA-256 checksum</span>
sha256sum <?= htmlspecialchars($gaIsoBasename) ?>.iso

<span class="comment"># Or verify with BLAKE3 (faster)</span>
b3sum <?= htmlspecialchars($gaIsoBasename) ?>.iso</code>
            </div>
        </div>
    </div>

    <!-- ═══════════════════ STEP 2: WRITE TO USB ═══════════════════ -->
    <div class="step-card" id="step-2">
        <div class="step-number">2</div>
        <h2>Write to USB</h2>
        <p class="step-desc">Flash the ISO to a USB drive. This will erase all data on the target drive.</p>

        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab(event, 'tab-linux')">🐧 Linux</button>
            <button class="tab-btn" onclick="switchTab(event, 'tab-windows')">🪟 Windows</button>
            <button class="tab-btn" onclick="switchTab(event, 'tab-macos')">🍎 macOS</button>
        </div>

        <div id="tab-linux" class="tab-panel active">
            <div class="code-block">
                <div class="code-header">
                    <span class="code-label">Linux — dd</span>
                    <button class="copy-btn" onclick="copyCode(this)">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                        Copy
                    </button>
                </div>
                <div class="code-body">
                    <code><span class="comment"># Find your USB device (e.g. /dev/sdb)</span>
lsblk

<span class="comment"># Write the ISO (replace /dev/sdX with your device)</span>
sudo dd <span class="flag">if=</span><span class="path"><?= htmlspecialchars($gaIsoBasename) ?>.iso</span> \
       <span class="flag">of=</span><span class="path">/dev/sdX</span> \
       <span class="flag">bs=</span>4M <span class="flag">status=</span>progress <span class="flag">oflag=</span>sync</code>
                </div>
            </div>
            <div class="info-box info-warn">
                <span class="info-icon">⚠️</span>
                <div><strong>Double-check the target device.</strong> <code style="color: var(--amber);">dd</code> writes raw — the wrong device means data loss. Use <code style="color: var(--amber);">lsblk</code> to verify.</div>
            </div>
        </div>

        <div id="tab-windows" class="tab-panel">
            <p style="margin-bottom: 1rem; color: var(--text-muted);">Use <strong style="color: #fff;">Rufus</strong> — the recommended USB writer for Windows:</p>
            <ol style="padding-left: 1.5rem; margin-bottom: 1rem; color: var(--text-muted); font-size: 0.9rem;">
                <li style="margin-bottom: 0.5rem;">Download <a href="https://rufus.ie" target="_blank" rel="noopener">Rufus</a> (free, open-source)</li>
                <li style="margin-bottom: 0.5rem;">Select your USB drive under <strong style="color: #fff;">Device</strong></li>
                <li style="margin-bottom: 0.5rem;">Click <strong style="color: #fff;">SELECT</strong> → choose the Alfred Linux ISO</li>
                <li style="margin-bottom: 0.5rem;">Partition scheme: <strong style="color: #fff;">GPT</strong> (for UEFI) or <strong style="color: #fff;">MBR</strong> (for Legacy BIOS)</li>
                <li style="margin-bottom: 0.5rem;">Click <strong style="color: #fff;">START</strong> → select <strong style="color: #fff;">Write in DD Image mode</strong></li>
            </ol>
            <div class="info-box info-tip">
                <span class="info-icon">💡</span>
                <div>Also see: <a href="/write-usb">Write USB</a> — our dedicated USB writing guide with step-by-step screenshots.</div>
            </div>
        </div>

        <div id="tab-macos" class="tab-panel">
            <div class="code-block">
                <div class="code-header">
                    <span class="code-label">macOS — dd</span>
                    <button class="copy-btn" onclick="copyCode(this)">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                        Copy
                    </button>
                </div>
                <div class="code-body">
                    <code><span class="comment"># List disks to find your USB (e.g. /dev/disk2)</span>
diskutil list

<span class="comment"># Unmount the USB drive</span>
diskutil unmountDisk <span class="path">/dev/diskN</span>

<span class="comment"># Write the ISO (note: rdiskN for raw speed)</span>
sudo dd <span class="flag">if=</span><span class="path"><?= htmlspecialchars($gaIsoBasename) ?>.iso</span> \
       <span class="flag">of=</span><span class="path">/dev/rdiskN</span> \
       <span class="flag">bs=</span>4m</code>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════ STEP 3: BOOT ═══════════════════ -->
    <div class="step-card" id="step-3">
        <div class="step-number">3</div>
        <h2>Boot from USB</h2>
        <p class="step-desc">Plug in the USB, enter your BIOS, and watch Alfred Linux come alive.</p>

        <div class="boot-flow">
            <div class="boot-stage">
                <div class="stage-name">BIOS / UEFI</div>
                <div class="stage-desc">Boot from USB</div>
            </div>
            <span class="boot-arrow">→</span>
            <div class="boot-stage">
                <div class="stage-name">GRUB Menu</div>
                <div class="stage-desc">Select boot mode</div>
            </div>
            <span class="boot-arrow">→</span>
            <div class="boot-stage">
                <div class="stage-name">Plymouth</div>
                <div class="stage-desc">Splash screen</div>
            </div>
            <span class="boot-arrow">→</span>
            <div class="boot-stage">
                <div class="stage-name" style="color: var(--gold);">Covenant Gate</div>
                <div class="stage-desc">Accept the covenant</div>
            </div>
            <span class="boot-arrow">→</span>
            <div class="boot-stage">
                <div class="stage-name" style="color: var(--green);">Desktop</div>
                <div class="stage-desc">KDE Plasma</div>
            </div>
        </div>

        <ol style="padding-left: 1.5rem; margin: 1.5rem 0; font-size: 0.9rem;">
            <li style="margin-bottom: 0.75rem; color: var(--text-muted);"><strong style="color: #fff;">Enter BIOS/UEFI</strong> — Usually <code style="background: rgba(255,255,255,0.06); padding: 0.15rem 0.5rem; border-radius: 4px; font-size: 0.8rem;">F2</code>, <code style="background: rgba(255,255,255,0.06); padding: 0.15rem 0.5rem; border-radius: 4px; font-size: 0.8rem;">F12</code>, <code style="background: rgba(255,255,255,0.06); padding: 0.15rem 0.5rem; border-radius: 4px; font-size: 0.8rem;">DEL</code>, or <code style="background: rgba(255,255,255,0.06); padding: 0.15rem 0.5rem; border-radius: 4px; font-size: 0.8rem;">ESC</code> during POST</li>
            <li style="margin-bottom: 0.75rem; color: var(--text-muted);"><strong style="color: #fff;">Set boot order</strong> — Move USB drive to the top of the boot priority list</li>
            <li style="margin-bottom: 0.75rem; color: var(--text-muted);"><strong style="color: #fff;">GRUB appears</strong> — Select <span style="color: var(--cyan); font-weight: 600;">'Commander Mode'</span> (default live session)</li>
            <li style="margin-bottom: 0.75rem; color: var(--text-muted);"><strong style="color: #fff;">Covenant Gate</strong> — Accept the covenant to enter the Kingdom</li>
            <li style="color: var(--text-muted);"><strong style="color: #fff;">Desktop loads</strong> — KDE Plasma is ready. Everything works out of the box.</li>
        </ol>

        <div class="info-box info-note">
            <span class="info-icon">📖</span>
            <div>Want the full visual walkthrough? See the <a href="/boot-experience">Boot Experience</a> page for detailed screenshots and explanations of every boot stage.</div>
        </div>
    </div>

    <!-- ═══════════════════ STEP 4: FIRST BOOT ═══════════════════ -->
    <div class="step-card" id="step-4">
        <div class="step-number">4</div>
        <h2>First Boot — What's Ready</h2>
        <p class="step-desc">Alfred Linux ships as a complete system. No setup wizards, no package installs. Everything is live from the first moment.</p>

        <ul class="checklist">
            <li>
                <span class="check">✓</span>
                <div><strong>4,800+ Packages</strong><br><span style="color: var(--text-muted); font-size: 0.8rem;">Pre-installed, pre-configured, ready to use</span></div>
            </li>
            <li>
                <span class="check">✓</span>
                <div><strong>AI Backend Running</strong><br><span style="color: var(--text-muted); font-size: 0.8rem;">Ollama with models loaded and ready</span></div>
            </li>
            <li>
                <span class="check">✓</span>
                <div><strong>VR Runtime Ready</strong><br><span style="color: var(--text-muted); font-size: 0.8rem;">Plug in your headset — ALVR is configured</span></div>
            </li>
            <li>
                <span class="check">✓</span>
                <div><strong>Military Security Tools</strong><br><span style="color: var(--text-muted); font-size: 0.8rem;">AppArmor, firewall, IDS — active from boot</span></div>
            </li>
            <li>
                <span class="check">✓</span>
                <div><strong>Bible Data Accessible</strong><br><span style="color: var(--text-muted); font-size: 0.8rem;">AKJV Bible forged into the immutable core</span></div>
            </li>
            <li>
                <span class="check">✓</span>
                <div><strong>KDE Plasma Desktop</strong><br><span style="color: var(--text-muted); font-size: 0.8rem;">Premium theme, panels, and widgets pre-configured</span></div>
            </li>
        </ul>

        <div class="info-box info-tip">
            <span class="info-icon">⚡</span>
            <div>
                <strong>You're running live from USB.</strong> Any changes you make won't persist across reboots unless you install to disk (Step 5). This is perfect for testing.
            </div>
        </div>
    </div>

    <!-- ═══════════════════ STEP 5: INSTALL ═══════════════════ -->
    <div class="step-card" id="step-5">
        <div class="step-number">5</div>
        <h2>Install to Disk <span style="color: var(--text-dim); font-weight: 400; font-size: 0.9rem;">(Optional)</span></h2>
        <p class="step-desc">Ready to commit? Install Alfred Linux to your hard drive for a permanent, persistent system.</p>

        <ol style="padding-left: 1.5rem; margin: 1rem 0 1.5rem; font-size: 0.9rem;">
            <li style="margin-bottom: 0.75rem; color: var(--text-muted);"><strong style="color: #fff;">Reboot</strong> and return to the GRUB menu</li>
            <li style="margin-bottom: 0.75rem; color: var(--text-muted);">Select <span style="color: var(--cyan); font-weight: 600;">'Install: Standard'</span> or <span style="color: var(--cyan); font-weight: 600;">'Install: FDE + Kyber'</span></li>
            <li style="margin-bottom: 0.75rem; color: var(--text-muted);"><strong style="color: #fff;">Calamares</strong> graphical installer launches — follow the wizard</li>
            <li style="color: var(--text-muted);">Reboot into your installed system — done.</li>
        </ol>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin: 1.5rem 0;">
            <div style="padding: 1rem; background: rgba(34,211,238,0.04); border: 1px solid rgba(34,211,238,0.12); border-radius: 10px;">
                <h4 style="color: var(--cyan); font-size: 0.9rem; margin-bottom: 0.25rem;">🔓 Standard Install</h4>
                <p style="font-size: 0.8rem; color: var(--text-muted);">Traditional partition layout. Simple and fast. Best for single-user systems.</p>
            </div>
            <div style="padding: 1rem; background: rgba(255,215,0,0.04); border: 1px solid rgba(255,215,0,0.12); border-radius: 10px;">
                <h4 style="color: var(--gold); font-size: 0.9rem; margin-bottom: 0.25rem;">🔐 FDE + Kyber-1024</h4>
                <p style="font-size: 0.8rem; color: var(--text-muted);">Full Disk Encryption with post-quantum Kyber-1024. Future-proof against quantum attacks.</p>
            </div>
            <div style="padding: 1rem; background: rgba(99,102,241,0.04); border: 1px solid rgba(99,102,241,0.12); border-radius: 10px;">
                <h4 style="color: var(--accent-light); font-size: 0.9rem; margin-bottom: 0.25rem;">🗄️ ZFS Root</h4>
                <p style="font-size: 0.8rem; color: var(--text-muted);">Enterprise-grade filesystem with snapshots, compression, and self-healing.</p>
            </div>
        </div>
    </div>

    <!-- ═══════════════════ BOOT MODES TABLE ═══════════════════ -->
    <div class="section-header" style="margin-top: 4rem;">
        <h2>🎯 Boot Modes Quick Reference</h2>
        <p>All available GRUB menu entries at a glance</p>
    </div>

    <div class="boot-table-wrap">
        <table class="boot-table">
            <thead>
                <tr>
                    <th>Boot Mode</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="mode-name">Commander Mode</td>
                    <td class="mode-desc">Default live session — full desktop, all systems active</td>
                </tr>
                <tr>
                    <td class="mode-name">Commander Mode (Safe Graphics)</td>
                    <td class="mode-desc">Software rendering — use if GPU isn't detected</td>
                </tr>
                <tr>
                    <td class="mode-name">Forensic Mode</td>
                    <td class="mode-desc">Read-only boot — no swap, no automount, for forensic analysis</td>
                </tr>
                <tr>
                    <td class="mode-name">Install: Standard</td>
                    <td class="mode-desc">Calamares graphical installer — standard partition layout</td>
                </tr>
                <tr>
                    <td class="mode-name">Install: FDE + Kyber</td>
                    <td class="mode-desc">Full disk encryption with post-quantum Kyber-1024 KEM</td>
                </tr>
                <tr>
                    <td class="mode-name">Memtest86+</td>
                    <td class="mode-desc">Memory diagnostics — test RAM integrity before install</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="info-box info-note" style="margin-top: 1rem;">
        <span class="info-icon">📖</span>
        <div>See the <a href="/boot-experience">Boot Experience</a> page for full details on every boot mode, Plymouth themes, and the Covenant Gate.</div>
    </div>

    <!-- ═══════════════════ HELP & COMMUNITY ═══════════════════ -->
    <div class="section-header" style="margin-top: 4rem;">
        <h2>🤝 Help &amp; Community</h2>
        <p>You're not alone. Resources, documentation, and community await.</p>
    </div>

    <div class="links-grid">
        <a href="/docs" class="link-card">
            <span class="link-icon">📚</span>
            <div>
                <h3>Documentation</h3>
                <p>Full docs, guides, and reference</p>
            </div>
        </a>
        <a href="https://github.com/GoSiteMe-com/alfredlinux" target="_blank" rel="noopener" class="link-card">
            <span class="link-icon">🔗</span>
            <div>
                <h3>GitHub</h3>
                <p>Source code, issues, and contributions</p>
            </div>
        </a>
        <a href="/community" class="link-card">
            <span class="link-icon">👥</span>
            <div>
                <h3>Community</h3>
                <p>Connect with other Alfred users</p>
            </div>
        </a>
    </div>

    <!-- ═══════════════════ CTA ═══════════════════ -->
    <div class="cta-box">
        <h3>Ready to Enter the Kingdom?</h3>
        <p>Download Alfred Linux 7.77 Alpha Matrix and experience computing the way it was meant to be.</p>
        <a href="/download" class="cta-btn">⬇ Download Now</a>
        <a href="/security" class="cta-btn secondary">Security Transparency</a>
        <a href="/releases" class="cta-btn secondary">Release Notes</a>
    </div>

</div>

<?php include __DIR__ . "/includes/omahon-seal.php"; ?>
<footer>
    <p style="font-style: italic; color: #94a3b8; font-size: .85rem; margin: 0 0 0.5rem;">
        &ldquo;The grass withereth, the flower fadeth: but the word of our God shall stand for ever.&rdquo;
        &mdash; <a href="https://gositeme.com/bible/read/isaiah/40" style="color: #facc15; text-decoration: none;">Isaiah 40:8</a> (AKJV)
    </p>
    &copy; <?= $year ?> <a href="https://gositeme.com">GoSiteMe Inc.</a> &mdash; Alfred Linux &middot; Open Source (KCL-1.0)
</footer>

<?php include __DIR__ . "/includes/shabbat-banner.php"; ?>

<script>
/* ═══════ TAB SWITCHING ═══════ */
function switchTab(e, panelId) {
    const card = e.target.closest('.step-card');
    card.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    card.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    e.target.classList.add('active');
    document.getElementById(panelId).classList.add('active');
}

/* ═══════ COPY TO CLIPBOARD ═══════ */
function copyCode(btn) {
    const block = btn.closest('.code-block');
    const code = block.querySelector('code').innerText;
    navigator.clipboard.writeText(code).then(() => {
        btn.classList.add('copied');
        btn.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> Copied!`;
        setTimeout(() => {
            btn.classList.remove('copied');
            btn.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg> Copy`;
        }, 2000);
    });
}

/* ═══════ PROGRESS BAR — SCROLL TRACKING ═══════ */
(function() {
    const steps = ['step-1', 'step-2', 'step-3', 'step-4', 'step-5'];
    const dots = document.querySelectorAll('.progress-bar .step-dot');
    const progressLinks = document.querySelectorAll('.progress-bar .progress-step');

    function updateProgress() {
        const scrollPos = window.scrollY + window.innerHeight * 0.35;
        let activeIndex = 0;

        steps.forEach((id, i) => {
            const el = document.getElementById(id);
            if (el && el.offsetTop <= scrollPos) {
                activeIndex = i;
            }
        });

        dots.forEach((dot, i) => {
            dot.classList.toggle('active', i <= activeIndex);
        });

        // Fill connecting lines for completed steps
        progressLinks.forEach((link, i) => {
            const after = link.querySelector('::after');
            if (i < activeIndex) {
                link.style.setProperty('--line-color', 'var(--gold)');
            }
        });
    }

    window.addEventListener('scroll', updateProgress, { passive: true });
    updateProgress();
})();

/* ═══════ FADE-IN ANIMATIONS ═══════ */
(function() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.step-card, .req-card, .link-card').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });
})();
</script>
</body>
</html>
