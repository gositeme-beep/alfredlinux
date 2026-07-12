<?php
/**
 * Alfred Linux — The World's First AI-Native Operating System
 * Official product page at alfredlinux.com
 * 
 * Built by Alfred for Commander Danny William Perez
 * GoSiteMe Inc. — March 2026
 */
$year = date('Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alfred Linux — The World's First AI-Native Operating System</title>
    <meta name="description" content="Alfred Linux is a complete operating system where AI IS the interface. Voice-first, post-quantum encrypted, token-incentivized, everything-connected. Not a distro with a chatbot — the AI IS the OS.">
    <meta name="keywords" content="Alfred Linux, AI operating system, voice-first OS, post-quantum encryption, Kyber-768, GSM token, smart home OS, robot fleet OS, sovereign OS">
    <meta property="og:title" content="Alfred Linux — AI-Native Operating System">
    <meta property="og:description" content="Your voice is the command line. 13,262+ AI tools. Post-quantum encrypted. Earn tokens by computing.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://alfredlinux.com">
    <meta property="og:image" content="https://alfredlinux.com/og-image.png">
    <link rel="canonical" href="https://alfredlinux.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #06060b;
            --surface: rgba(255,255,255,0.03);
            --surface-hover: rgba(255,255,255,0.06);
            --border: rgba(255,255,255,0.06);
            --border-hover: rgba(99, 102, 241, 0.3);
            --text: #e0e0e0;
            --text-muted: #9ca3af;
            --text-dim: #6b7280;
            --accent: #6366f1;
            --accent-light: #a5b4fc;
            --accent2: #8b5cf6;
            --green: #34d399;
            --red: #ef4444;
            --amber: #f59e0b;
            --cyan: #22d3ee;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        /* ── NAV ── */
        nav {
            position: fixed; top: 0; left: 0; right: 0; z-index: 100;
            padding: 1rem 2rem;
            background: rgba(6,6,11,0.85);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
        }
        .nav-brand {
            display: flex; align-items: center; gap: 0.75rem;
            font-size: 1.2rem; font-weight: 700; color: #fff;
            text-decoration: none;
        }
        .nav-brand .logo-mark {
            width: 32px; height: 32px; border-radius: 8px;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            display: flex; align-items: center; justify-content: center;
            font-size: 1rem; font-weight: 900; color: #fff;
        }
        .nav-links { display: flex; gap: 2rem; align-items: center; }
        .nav-links a {
            color: var(--text-muted); text-decoration: none;
            font-size: 0.9rem; font-weight: 500;
            transition: color 0.2s;
        }
        .nav-links a:hover { color: #fff; }
        .nav-cta {
            padding: 0.5rem 1.25rem; border-radius: 8px;
            background: var(--accent); color: #fff !important;
            font-weight: 600 !important; font-size: 0.85rem !important;
            transition: all 0.2s;
        }
        .nav-cta:hover { background: var(--accent2); transform: translateY(-1px); }
        .nav-toggle { display: none; background: none; border: none; color: #fff; font-size: 1.5rem; cursor: pointer; }

        /* ── HERO ── */
        .hero {
            min-height: 100vh;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            text-align: center; padding: 8rem 2rem 6rem;
            position: relative;
            background: radial-gradient(ellipse at 50% 20%, rgba(99,102,241,0.12) 0%, transparent 55%),
                        radial-gradient(ellipse at 80% 80%, rgba(139,92,246,0.08) 0%, transparent 45%),
                        radial-gradient(ellipse at 20% 60%, rgba(34,211,238,0.05) 0%, transparent 40%);
        }
        .hero::before {
            content: ''; position: absolute; inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23ffffff' fill-opacity='0.015'%3E%3Ccircle cx='30' cy='30' r='1'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            pointer-events: none;
        }

        .hero-badge {
            display: inline-flex; align-items: center; gap: 0.5rem;
            padding: 0.4rem 1.2rem; border-radius: 20px;
            background: rgba(99,102,241,0.12); border: 1px solid rgba(99,102,241,0.25);
            font-size: 0.8rem; font-weight: 600; color: var(--accent-light);
            letter-spacing: 0.06em; text-transform: uppercase; margin-bottom: 2rem;
        }
        .hero-badge .pulse { width: 6px; height: 6px; border-radius: 50%; background: var(--green); animation: pulse 2s infinite; }
        @keyframes pulse { 0%,100% { opacity: 1; box-shadow: 0 0 0 0 rgba(52,211,153,0.4); } 50% { opacity: 0.7; box-shadow: 0 0 0 6px rgba(52,211,153,0); } }

        .hero h1 {
            font-size: clamp(2.8rem, 7vw, 5.5rem);
            font-weight: 900; line-height: 1.05; margin-bottom: 1.5rem;
            background: linear-gradient(135deg, #fff 0%, var(--accent-light) 40%, #818cf8 70%, var(--cyan) 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hero .tagline {
            font-size: clamp(1rem, 2.5vw, 1.35rem);
            color: var(--text-muted); max-width: 700px; line-height: 1.7;
            margin-bottom: 2.5rem;
        }
        .hero .tagline strong { color: #fff; font-weight: 600; }

        /* Terminal demo */
        .terminal-demo {
            max-width: 720px; width: 100%; margin: 3rem auto 0;
            border-radius: 16px; overflow: hidden;
            background: rgba(0,0,0,0.6); border: 1px solid var(--border);
            text-align: left; box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        }
        .terminal-bar {
            padding: 0.75rem 1rem; background: rgba(255,255,255,0.04);
            display: flex; align-items: center; gap: 0.5rem;
            border-bottom: 1px solid var(--border);
        }
        .terminal-dot { width: 10px; height: 10px; border-radius: 50%; }
        .terminal-dot.r { background: #ef4444; }
        .terminal-dot.y { background: #f59e0b; }
        .terminal-dot.g { background: #22c55e; }
        .terminal-title { flex: 1; text-align: center; color: var(--text-dim); font-size: 0.75rem; }
        .terminal-body { padding: 1.5rem; font-family: 'SF Mono', 'Fira Code', monospace; font-size: 0.85rem; line-height: 1.8; }
        .terminal-body .prompt { color: var(--green); }
        .terminal-body .cmd { color: #fff; }
        .terminal-body .response { color: var(--text-muted); }
        .terminal-body .highlight { color: var(--accent-light); }
        .terminal-body .token { color: var(--amber); }

        .cta-group { display: flex; gap: 1rem; flex-wrap: wrap; justify-content: center; }
        .btn {
            padding: 0.85rem 2.2rem; border-radius: 10px;
            font-size: 1rem; font-weight: 600; text-decoration: none;
            cursor: pointer; border: none; transition: all 0.25s; display: inline-flex; align-items: center; gap: 0.5rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            color: #fff; box-shadow: 0 4px 25px rgba(99,102,241,0.4);
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 35px rgba(99,102,241,0.5); }
        .btn-outline {
            background: transparent; color: var(--text);
            border: 1px solid rgba(255,255,255,0.15);
        }
        .btn-outline:hover { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.3); }
        .btn-green {
            background: linear-gradient(135deg, #059669, #10b981);
            color: #fff; box-shadow: 0 4px 25px rgba(16,185,129,0.3);
        }
        .btn-green:hover { transform: translateY(-2px); box-shadow: 0 8px 35px rgba(16,185,129,0.4); }

        /* ── STATS BAR ── */
        .stats-bar {
            display: flex; justify-content: center; gap: 3rem; flex-wrap: wrap;
            padding: 3rem 2rem; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border);
            background: rgba(255,255,255,0.01);
        }
        .stat { text-align: center; }
        .stat-value { font-size: 2rem; font-weight: 800; color: #fff; }
        .stat-value .accent { color: var(--accent-light); }
        .stat-label { font-size: 0.85rem; color: var(--text-dim); margin-top: 0.25rem; }

        /* ── SECTIONS ── */
        .section {
            padding: 7rem 2rem;
            max-width: 1200px; margin: 0 auto;
        }
        .section-alt {
            background: rgba(255,255,255,0.01);
        }
        .section-header {
            text-align: center; margin-bottom: 4rem;
        }
        .section-header h2 {
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 800; color: #fff; margin-bottom: 1rem;
        }
        .section-header p {
            font-size: 1.15rem; color: var(--text-muted); max-width: 600px; margin: 0 auto;
        }
        .section-label {
            display: inline-block; font-size: 0.75rem; font-weight: 700;
            color: var(--accent-light); text-transform: uppercase; letter-spacing: 0.1em;
            margin-bottom: 1rem;
        }

        /* ── FEATURE CARDS ── */
        .feature-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1.5rem;
        }
        .feature-card {
            padding: 2rem; border-radius: 16px;
            background: var(--surface); border: 1px solid var(--border);
            transition: all 0.3s;
        }
        .feature-card:hover {
            background: var(--surface-hover); border-color: var(--border-hover);
            transform: translateY(-4px); box-shadow: 0 12px 40px rgba(0,0,0,0.3);
        }
        .feature-icon {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; margin-bottom: 1.25rem;
        }
        .feature-icon.voice { background: rgba(99,102,241,0.15); }
        .feature-icon.encrypt { background: rgba(34,211,238,0.15); }
        .feature-icon.token { background: rgba(245,158,11,0.15); }
        .feature-icon.iot { background: rgba(52,211,153,0.15); }
        .feature-icon.robot { background: rgba(244,114,182,0.15); }
        .feature-icon.browser { background: rgba(139,92,246,0.15); }
        .feature-card h3 { font-size: 1.15rem; font-weight: 700; color: #fff; margin-bottom: 0.5rem; }
        .feature-card p { color: var(--text-muted); line-height: 1.65; font-size: 0.95rem; }

        /* ── ARCHITECTURE ── */
        .arch-diagram {
            max-width: 900px; margin: 0 auto;
            background: rgba(0,0,0,0.4); border: 1px solid var(--border);
            border-radius: 16px; overflow: hidden;
            font-family: 'SF Mono', 'Fira Code', monospace; font-size: 0.8rem;
        }
        .arch-layer {
            padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--border);
            display: flex; align-items: center; gap: 1rem;
        }
        .arch-layer:last-child { border-bottom: none; }
        .arch-layer-num {
            width: 28px; height: 28px; border-radius: 6px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 0.75rem; color: #fff; flex-shrink: 0;
        }
        .arch-layer-name { font-weight: 600; color: #fff; min-width: 120px; }
        .arch-layer-desc { color: var(--text-muted); font-size: 0.8rem; }
        .layer-6 .arch-layer-num { background: var(--accent); }
        .layer-5 .arch-layer-num { background: #8b5cf6; }
        .layer-4 .arch-layer-num { background: #ec4899; }
        .layer-3 .arch-layer-num { background: #f59e0b; }
        .layer-2 .arch-layer-num { background: #22d3ee; }
        .layer-1 .arch-layer-num { background: #22c55e; }

        /* ── COMPARISON TABLE ── */
        .comparison-wrap {
            max-width: 1000px; margin: 0 auto; overflow-x: auto;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem 1.25rem; text-align: left; border-bottom: 1px solid var(--border); }
        th { color: var(--accent-light); font-weight: 600; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; }
        td { color: var(--text-muted); font-size: 0.95rem; }
        td:first-child { color: #fff; font-weight: 500; }
        .yes { color: var(--green); font-weight: 600; }
        .no { color: var(--red); opacity: 0.7; }
        .partial { color: var(--amber); }
        tr:hover td { background: rgba(255,255,255,0.02); }
        .col-alfred { background: rgba(99,102,241,0.05); }

        /* ── EDITIONS ── */
        .editions-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        .edition-card {
            padding: 2rem; border-radius: 16px;
            background: var(--surface); border: 1px solid var(--border);
            transition: all 0.3s; position: relative; overflow: hidden;
        }
        .edition-card:hover { border-color: var(--border-hover); transform: translateY(-3px); }
        .edition-card .edition-icon { font-size: 2.5rem; margin-bottom: 1rem; }
        .edition-card h3 { font-size: 1.2rem; font-weight: 700; color: #fff; margin-bottom: 0.5rem; }
        .edition-card .edition-desc { color: var(--text-muted); font-size: 0.9rem; line-height: 1.6; margin-bottom: 1rem; }
        .edition-card .edition-tag {
            display: inline-block; padding: 0.25rem 0.75rem; border-radius: 6px;
            font-size: 0.75rem; font-weight: 600; text-transform: uppercase;
        }
        .tag-free { background: rgba(34,211,153,0.15); color: var(--green); }
        .tag-enterprise { background: rgba(99,102,241,0.15); color: var(--accent-light); }

        /* ── TOKEN ECONOMY ── */
        .token-flow {
            display: grid; grid-template-columns: 1fr auto 1fr; gap: 2rem;
            align-items: start; max-width: 900px; margin: 0 auto;
        }
        .token-col h3 { font-size: 1.1rem; font-weight: 700; color: #fff; margin-bottom: 1.25rem; }
        .token-item {
            display: flex; align-items: center; gap: 0.75rem;
            padding: 0.75rem 1rem; border-radius: 10px;
            background: var(--surface); border: 1px solid var(--border);
            margin-bottom: 0.5rem; font-size: 0.9rem; color: var(--text);
        }
        .token-item .ti-icon { font-size: 1.2rem; }
        .token-arrow {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            gap: 0.5rem; padding-top: 2.5rem;
        }
        .token-arrow .gsm-badge {
            width: 64px; height: 64px; border-radius: 50%;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            display: flex; align-items: center; justify-content: center;
            font-weight: 900; font-size: 0.7rem; color: #fff;
            box-shadow: 0 0 30px rgba(245,158,11,0.3);
        }
        .token-arrow span { color: var(--text-dim); font-size: 0.75rem; }

        /* ── ROADMAP ── */
        .roadmap { max-width: 800px; margin: 0 auto; }
        .roadmap-item {
            display: flex; gap: 1.5rem; padding-bottom: 2rem;
            position: relative;
        }
        .roadmap-item::before {
            content: ''; position: absolute; left: 15px; top: 32px; bottom: 0;
            width: 2px; background: var(--border);
        }
        .roadmap-item:last-child::before { display: none; }
        .rm-dot {
            width: 32px; height: 32px; border-radius: 50%; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.75rem; font-weight: 700; color: #fff;
        }
        .rm-done { background: var(--green); }
        .rm-active { background: var(--accent); animation: pulse 2s infinite; }
        .rm-planned { background: rgba(255,255,255,0.1); border: 2px solid var(--border); color: var(--text-dim); }
        .rm-content h4 { font-size: 1.05rem; font-weight: 600; color: #fff; margin-bottom: 0.25rem; }
        .rm-content p { color: var(--text-muted); font-size: 0.9rem; }
        .rm-content .rm-date { font-size: 0.8rem; color: var(--text-dim); margin-top: 0.25rem; }

        /* ── TECH STACK ── */
        .tech-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem; max-width: 900px; margin: 0 auto;
        }
        .tech-item {
            padding: 1rem 1.25rem; border-radius: 10px;
            background: var(--surface); border: 1px solid var(--border);
            display: flex; align-items: center; gap: 0.75rem;
        }
        .tech-item .tech-label { font-size: 0.75rem; color: var(--text-dim); text-transform: uppercase; letter-spacing: 0.05em; }
        .tech-item .tech-value { font-size: 0.95rem; color: #fff; font-weight: 500; }

        /* ── CTA SECTION ── */
        .cta-section {
            text-align: center; padding: 8rem 2rem;
            background: radial-gradient(ellipse at 50% 50%, rgba(99,102,241,0.1) 0%, transparent 60%);
        }
        .cta-section h2 {
            font-size: clamp(2rem, 4vw, 3.5rem);
            font-weight: 900; color: #fff; margin-bottom: 1.5rem;
        }
        .cta-section p { color: var(--text-muted); font-size: 1.15rem; margin-bottom: 2.5rem; max-width: 550px; margin-left: auto; margin-right: auto; }

        /* ── FOOTER ── */
        footer {
            padding: 4rem 2rem 2rem; border-top: 1px solid var(--border);
        }
        .footer-grid {
            max-width: 1200px; margin: 0 auto;
            display: grid; grid-template-columns: 2fr repeat(3, 1fr); gap: 3rem;
            margin-bottom: 3rem;
        }
        .footer-brand h3 { font-size: 1.2rem; font-weight: 700; color: #fff; margin-bottom: 0.5rem; }
        .footer-brand p { color: var(--text-dim); font-size: 0.9rem; line-height: 1.6; }
        .footer-col h4 { font-size: 0.8rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 1rem; }
        .footer-col a { display: block; color: var(--text-dim); text-decoration: none; font-size: 0.9rem; padding: 0.3rem 0; transition: color 0.2s; }
        .footer-col a:hover { color: var(--accent-light); }
        .footer-bottom {
            max-width: 1200px; margin: 0 auto;
            padding-top: 2rem; border-top: 1px solid var(--border);
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;
            font-size: 0.85rem; color: var(--text-dim);
        }
        .footer-bottom a { color: var(--accent-light); text-decoration: none; }

        /* ── MOBILE ── */
        @media (max-width: 768px) {
            .nav-links { display: none; }
            .nav-toggle { display: block; }
            .nav-links.open {
                display: flex; flex-direction: column;
                position: absolute; top: 100%; left: 0; right: 0;
                background: rgba(6,6,11,0.95); padding: 1rem 2rem;
                border-bottom: 1px solid var(--border);
            }
            .cta-group { flex-direction: column; align-items: center; }
            .btn { width: 100%; max-width: 300px; justify-content: center; }
            .stats-bar { gap: 1.5rem; }
            .stat-value { font-size: 1.5rem; }
            .token-flow { grid-template-columns: 1fr; }
            .token-arrow { flex-direction: row; padding-top: 0; }
            .footer-grid { grid-template-columns: 1fr 1fr; }
            .feature-grid { grid-template-columns: 1fr; }
            .terminal-demo { margin: 2rem auto 0; }
        }
        @media (max-width: 480px) {
            .footer-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- ═══ NAV ═══ -->
<nav>
    <a href="/" class="nav-brand">
        <div class="logo-mark">A</div>
        Alfred Linux
    </a>
    <button class="nav-toggle" onclick="document.querySelector('.nav-links').classList.toggle('open')" aria-label="Toggle menu">☰</button>
    <div class="nav-links">
        <a href="#features">Features</a>
        <a href="#architecture">Architecture</a>
        <a href="#editions">Editions</a>
        <a href="#compare">Compare</a>
        <a href="#economy">Economy</a>
        <a href="#roadmap">Roadmap</a>
        <a href="#download" class="nav-cta">Get Alfred</a>
    </div>
</nav>

<!-- ═══ HERO ═══ -->
<section class="hero">
    <div class="hero-badge"><span class="pulse"></span> Open Source &mdash; AGPL-3.0</div>
    <h1>Your Voice Is<br>The Command Line.</h1>
    <p class="tagline">Alfred Linux is the world's first <strong>AI-native operating system</strong>. Not a chatbot on Linux &mdash; the AI <em>is</em> the OS. Voice-first. Post-quantum encrypted. Token-incentivized. Everything-connected.</p>
    <div class="cta-group">
        <a href="#download" class="btn btn-primary">⬇ Download ISO</a>
        <a href="#features" class="btn btn-outline">Explore Features</a>
        <a href="https://github.com/gositeme/alfred-linux" class="btn btn-outline" target="_blank" rel="noopener">★ GitHub</a>
    </div>

    <!-- Terminal Demo -->
    <div class="terminal-demo">
        <div class="terminal-bar">
            <div class="terminal-dot r"></div>
            <div class="terminal-dot y"></div>
            <div class="terminal-dot g"></div>
            <div class="terminal-title">alfred-voice-shell</div>
        </div>
        <div class="terminal-body">
            <div><span class="prompt">commander@alfred:~$ </span><span class="cmd" id="typed-cmd"></span><span id="cursor" style="animation: blink 1s infinite;">▎</span></div>
            <div class="response" id="typed-response" style="display:none;">
                <br>
                <span class="highlight">✓</span> Door locked. <span class="highlight">✓</span> Lights at 30%.<br>
                <span class="highlight">✓</span> Greenhouse humidity: 72% — drip system adjusted.<br>
                <span class="highlight">✓</span> VR training loaded in Zone 7.<br>
                <span class="token">◆ GSM balance: 4,218.7 tokens</span>
            </div>
        </div>
    </div>
</section>

<!-- ═══ STATS BAR ═══ -->
<div class="stats-bar">
    <div class="stat">
        <div class="stat-value"><span class="accent">13,262</span>+</div>
        <div class="stat-label">AI Tools Built In</div>
    </div>
    <div class="stat">
        <div class="stat-value"><span class="accent">11.3</span>M+</div>
        <div class="stat-label">Agents in Registry</div>
    </div>
    <div class="stat">
        <div class="stat-value">Kyber<span class="accent">-768</span></div>
        <div class="stat-label">Post-Quantum Encryption</div>
    </div>
    <div class="stat">
        <div class="stat-value"><span class="accent">6</span></div>
        <div class="stat-label">Editions Available</div>
    </div>
    <div class="stat">
        <div class="stat-value"><span class="accent">AGPL</span></div>
        <div class="stat-label">Open Source License</div>
    </div>
</div>

<!-- ═══ FEATURES ═══ -->
<section class="section" id="features">
    <div class="section-header">
        <span class="section-label">Core Features</span>
        <h2>Not a Distro with a Chatbot.</h2>
        <p>This is what happens when you build an OS where AI is the kernel-level interface to reality.</p>
    </div>
    <div class="feature-grid">
        <div class="feature-card">
            <div class="feature-icon voice">🎙️</div>
            <h3>Voice-First OS Shell</h3>
            <p>Whisper STT → Claude/Local LLM → Kokoro TTS. Alfred IS the shell. Talk to your computer, it talks back. No app required — the voice <em>is</em> the operating system.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon encrypt">🔐</div>
            <h3>Post-Quantum Encryption</h3>
            <p>Veil Protocol with Kyber-768 + AES-256-GCM. End-to-end encrypted messages, calls, and files that even quantum computers cannot break. Not even we can read your data.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon token">💰</div>
            <h3>GSM Token Economy</h3>
            <p>Earn GSM tokens on Solana for computing, contributing, and participating. Mine, develop, report bugs, vote — all rewarded. GSM can only be earned, never bought.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon iot">🏠</div>
            <h3>Universal IoT Control</h3>
            <p>Smart home, vehicle OBD2, greenhouse, drones — all from one voice command. Zigbee, Z-Wave, Matter, MQTT, WiFi. Alfred is your universal remote for reality.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon robot">🤖</div>
            <h3>Robot Fleet Control</h3>
            <p>Native ROS2 integration for robot fleet orchestration. Deploy, monitor, and redirect swarms. Sensor fusion across cameras, LIDAR, and IMU. Teach robots with voice.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon browser">🌐</div>
            <h3>Sovereign Browser</h3>
            <p>Alfred Chromium — patched for zero-tracking. No telemetry, no ad IDs, no Google Services. Mesh networking built in. Browse the web without being the product.</p>
        </div>
    </div>
</section>

<!-- ═══ ARCHITECTURE ═══ -->
<section class="section section-alt" id="architecture">
    <div class="section-header">
        <span class="section-label">Architecture</span>
        <h2>Six Layers of Intelligence</h2>
        <p>From bare metal to voice shell — every layer designed for AI-native operation.</p>
    </div>
    <div class="arch-diagram">
        <div class="arch-layer layer-6">
            <div class="arch-layer-num">6</div>
            <div class="arch-layer-name">Voice Shell</div>
            <div class="arch-layer-desc">Whisper STT → Claude / Ollama LLM → Kokoro TTS — always-on voice assistant</div>
        </div>
        <div class="arch-layer layer-5">
            <div class="arch-layer-num">5</div>
            <div class="arch-layer-name">Applications</div>
            <div class="arch-layer-desc">Alfred Chromium · MetaDome VR · GSM Wallet · 13,262+ AI Tools · Alfred Store</div>
        </div>
        <div class="arch-layer layer-4">
            <div class="arch-layer-num">4</div>
            <div class="arch-layer-name">Security</div>
            <div class="arch-layer-desc">Veil Protocol (Kyber-768 PQ) · AES-256-GCM · E2E Messages/Calls/Files</div>
        </div>
        <div class="arch-layer layer-3">
            <div class="arch-layer-num">3</div>
            <div class="arch-layer-name">Economy</div>
            <div class="arch-layer-desc">GSM Token on Solana · Mining · Bounties · App Store · Compute Marketplace</div>
        </div>
        <div class="arch-layer layer-2">
            <div class="arch-layer-num">2</div>
            <div class="arch-layer-name">Desktop</div>
            <div class="arch-layer-desc">ADE (Alfred Desktop Environment) · Wayland/wlroots · Rust + GTK4 · Voice HUD</div>
        </div>
        <div class="arch-layer layer-1">
            <div class="arch-layer-num">1</div>
            <div class="arch-layer-name">Foundation</div>
            <div class="arch-layer-desc">Debian/Ubuntu · Linux 6.x · systemd · Drivers · Hardware Abstraction</div>
        </div>
    </div>
</section>

<!-- ═══ COMPARISON ═══ -->
<section class="section" id="compare">
    <div class="section-header">
        <span class="section-label">Why Alfred?</span>
        <h2>Alfred vs. Everything Else</h2>
        <p>Every other OS was built before AI existed. Alfred was built because AI exists.</p>
    </div>
    <div class="comparison-wrap">
        <table>
            <thead>
                <tr>
                    <th>Feature</th>
                    <th>macOS</th>
                    <th>Windows</th>
                    <th>ChromeOS</th>
                    <th class="col-alfred">Alfred Linux</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Voice-native OS shell</td>
                    <td class="no">Siri (app)</td>
                    <td class="no">Cortana (dead)</td>
                    <td class="no">No</td>
                    <td class="yes col-alfred">✓ Alfred IS the shell</td>
                </tr>
                <tr>
                    <td>Post-quantum encryption</td>
                    <td class="no">✗ None</td>
                    <td class="no">✗ None</td>
                    <td class="no">✗ None</td>
                    <td class="yes col-alfred">✓ Kyber-768 E2E</td>
                </tr>
                <tr>
                    <td>Token economy</td>
                    <td class="no">✗ None</td>
                    <td class="no">✗ None</td>
                    <td class="no">✗ None</td>
                    <td class="yes col-alfred">✓ GSM on Solana</td>
                </tr>
                <tr>
                    <td>Smart home native</td>
                    <td class="partial">HomeKit (limited)</td>
                    <td class="no">✗ No</td>
                    <td class="partial">Nest (limited)</td>
                    <td class="yes col-alfred">✓ All protocols</td>
                </tr>
                <tr>
                    <td>Robot fleet control</td>
                    <td class="no">✗ No</td>
                    <td class="no">✗ No</td>
                    <td class="no">✗ No</td>
                    <td class="yes col-alfred">✓ ROS2 native</td>
                </tr>
                <tr>
                    <td>Farm automation</td>
                    <td class="no">✗ No</td>
                    <td class="no">✗ No</td>
                    <td class="no">✗ No</td>
                    <td class="yes col-alfred">✓ Drones + greenhouse</td>
                </tr>
                <tr>
                    <td>AI tools built-in</td>
                    <td class="no">✗ None</td>
                    <td class="partial">Copilot (limited)</td>
                    <td class="partial">Gemini (limited)</td>
                    <td class="yes col-alfred">✓ 13,262+ tools</td>
                </tr>
                <tr>
                    <td>VR/AR native</td>
                    <td class="no">✗ No</td>
                    <td class="partial">Mixed Reality</td>
                    <td class="no">✗ No</td>
                    <td class="yes col-alfred">✓ WebXR runtime</td>
                </tr>
                <tr>
                    <td>Vehicle integration</td>
                    <td class="partial">CarPlay (mirror)</td>
                    <td class="no">✗ No</td>
                    <td class="partial">Android Auto</td>
                    <td class="yes col-alfred">✓ Native OBD2 + dash</td>
                </tr>
                <tr>
                    <td>Earn while computing</td>
                    <td class="no">✗ No</td>
                    <td class="no">✗ No</td>
                    <td class="no">✗ No</td>
                    <td class="yes col-alfred">✓ Mine GSM tokens</td>
                </tr>
                <tr>
                    <td>Open source</td>
                    <td class="no">✗ Proprietary</td>
                    <td class="no">✗ Proprietary</td>
                    <td class="partial">Partially</td>
                    <td class="yes col-alfred">✓ AGPL-3.0</td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<!-- ═══ EDITIONS ═══ -->
<section class="section section-alt" id="editions">
    <div class="section-header">
        <span class="section-label">Editions</span>
        <h2>One OS. Six Missions.</h2>
        <p>From your desktop to your tractor. From your phone to your data center.</p>
    </div>
    <div class="editions-grid">
        <div class="edition-card">
            <div class="edition-icon">🖥️</div>
            <h3>Alfred Desktop</h3>
            <p class="edition-desc">Full desktop with ADE, browser, voice, and everything. The complete AI-native computing experience for creators, developers, and everyone.</p>
            <span class="edition-tag tag-free">Free &mdash; AGPL</span>
        </div>
        <div class="edition-card">
            <div class="edition-icon">🖧</div>
            <h3>Alfred Server</h3>
            <p class="edition-desc">Headless server with voice CLI and fleet control. Run your infrastructure with voice commands. Monitor, deploy, scale — all spoken.</p>
            <span class="edition-tag tag-free">Free &mdash; AGPL</span>
        </div>
        <div class="edition-card">
            <div class="edition-icon">📡</div>
            <h3>Alfred IoT</h3>
            <p class="edition-desc">Minimal image for Raspberry Pi and embedded devices. Smart home hub, sensor gateway, edge AI — in just 2GB. Perfect for Alfred Home.</p>
            <span class="edition-tag tag-free">Free &mdash; AGPL</span>
        </div>
        <div class="edition-card">
            <div class="edition-icon">🚗</div>
            <h3>Alfred Vehicle</h3>
            <p class="edition-desc">Automotive-grade for in-vehicle computers. OBD2 diagnostics, fleet management, dash UI, and AI-powered navigation — all voice-controlled.</p>
            <span class="edition-tag tag-free">Free &mdash; AGPL</span>
        </div>
        <div class="edition-card">
            <div class="edition-icon">📱</div>
            <h3>Alfred Mobile</h3>
            <p class="edition-desc">Touch-optimized mobile OS for sovereign smartphones. Full Alfred AI, Veil encryption, GSM wallet, IoT remote — your phone, your rules.</p>
            <a href="https://alfred-mobile.com" style="color:var(--accent-light);font-size:0.85rem;text-decoration:none;">alfred-mobile.com →</a>
        </div>
        <div class="edition-card">
            <div class="edition-icon">🏢</div>
            <h3>Quantum Linux</h3>
            <p class="edition-desc">White-label enterprise OS with post-quantum hardening, fleet management, HIPAA/SOC2/GDPR compliance, and custom branding. Alfred underneath.</p>
            <span class="edition-tag tag-enterprise">Enterprise</span>
            <a href="https://quantum-linux.com" style="color:var(--accent-light);font-size:0.85rem;text-decoration:none;display:block;margin-top:0.5rem;">quantum-linux.com →</a>
        </div>
    </div>
</section>

<!-- ═══ TOKEN ECONOMY ═══ -->
<section class="section" id="economy">
    <div class="section-header">
        <span class="section-label">GSM Economy</span>
        <h2>Earn While You Compute</h2>
        <p>GSM can only be earned, never bought. A work-based economy — if you contribute, you earn.</p>
    </div>
    <div class="token-flow">
        <div class="token-col">
            <h3 style="color:var(--green);">⬆ Earn GSM</h3>
            <div class="token-item"><span class="ti-icon">⛏️</span> Mine (SHA-256 PoW)</div>
            <div class="token-item"><span class="ti-icon">🤖</span> Run AI Tasks</div>
            <div class="token-item"><span class="ti-icon">📡</span> Share Bandwidth</div>
            <div class="token-item"><span class="ti-icon">💻</span> Develop Apps</div>
            <div class="token-item"><span class="ti-icon">🐛</span> Report Bugs</div>
            <div class="token-item"><span class="ti-icon">🗳️</span> Govern (Vote)</div>
        </div>
        <div class="token-arrow">
            <span>↕</span>
            <div class="gsm-badge">GSM</div>
            <span>Solana SPL</span>
        </div>
        <div class="token-col">
            <h3 style="color:var(--amber);">⬇ Spend GSM</h3>
            <div class="token-item"><span class="ti-icon">📦</span> Buy Apps & Services</div>
            <div class="token-item"><span class="ti-icon">🔄</span> Trade on Jupiter DEX</div>
            <div class="token-item"><span class="ti-icon">💝</span> Tip Developers</div>
            <div class="token-item"><span class="ti-icon">⚡</span> Pay for AI Compute</div>
            <div class="token-item"><span class="ti-icon">🛒</span> Buy Hardware</div>
            <div class="token-item"><span class="ti-icon">🎮</span> In-Game Purchases</div>
        </div>
    </div>
</section>

<!-- ═══ TECH STACK ═══ -->
<section class="section section-alt">
    <div class="section-header">
        <span class="section-label">Under the Hood</span>
        <h2>Technology Stack</h2>
        <p>Proven foundations. Cutting-edge integration.</p>
    </div>
    <div class="tech-grid">
        <div class="tech-item"><div><div class="tech-label">Kernel</div><div class="tech-value">Linux 6.x (Debian)</div></div></div>
        <div class="tech-item"><div><div class="tech-label">Init</div><div class="tech-value">systemd</div></div></div>
        <div class="tech-item"><div><div class="tech-label">Display</div><div class="tech-value">Wayland (wlroots)</div></div></div>
        <div class="tech-item"><div><div class="tech-label">Shell</div><div class="tech-value">ADE — Rust + GTK4</div></div></div>
        <div class="tech-item"><div><div class="tech-label">Voice STT</div><div class="tech-value">OpenAI Whisper</div></div></div>
        <div class="tech-item"><div><div class="tech-label">Voice LLM</div><div class="tech-value">Claude + Ollama</div></div></div>
        <div class="tech-item"><div><div class="tech-label">Voice TTS</div><div class="tech-value">Kokoro + Orpheus</div></div></div>
        <div class="tech-item"><div><div class="tech-label">Browser</div><div class="tech-value">Alfred Chromium</div></div></div>
        <div class="tech-item"><div><div class="tech-label">Encryption</div><div class="tech-value">Veil (Kyber-768)</div></div></div>
        <div class="tech-item"><div><div class="tech-label">Token</div><div class="tech-value">GSM on Solana</div></div></div>
        <div class="tech-item"><div><div class="tech-label">IoT</div><div class="tech-value">Matter · Zigbee · MQTT</div></div></div>
        <div class="tech-item"><div><div class="tech-label">Robotics</div><div class="tech-value">ROS2 Humble/Iron</div></div></div>
        <div class="tech-item"><div><div class="tech-label">VR/AR</div><div class="tech-value">WebXR + OpenXR</div></div></div>
        <div class="tech-item"><div><div class="tech-label">Gaming</div><div class="tech-value">Vulkan + Proton</div></div></div>
        <div class="tech-item"><div><div class="tech-label">Packages</div><div class="tech-value">APT + Flatpak + Store</div></div></div>
        <div class="tech-item"><div><div class="tech-label">Languages</div><div class="tech-value">Rust · TS · Python · C</div></div></div>
    </div>
</section>

<!-- ═══ ROADMAP ═══ -->
<section class="section" id="roadmap">
    <div class="section-header">
        <span class="section-label">Roadmap</span>
        <h2>The Path to v1.0</h2>
        <p>Building in public. Shipping in sprints.</p>
    </div>
    <div class="roadmap">
        <div class="roadmap-item">
            <div class="rm-dot rm-done">✓</div>
            <div class="rm-content">
                <h4>Sprint 0 — Project Scaffold</h4>
                <p>Research, planning, architecture, documentation, agent team deployment</p>
                <div class="rm-date">✓ Complete — March 11, 2026</div>
            </div>
        </div>
        <div class="roadmap-item">
            <div class="rm-dot rm-active">1</div>
            <div class="rm-content">
                <h4>Sprint 1 — Bootable ISO + Voice + ADE</h4>
                <p>First bootable image with Alfred Desktop Environment and voice assistant</p>
                <div class="rm-date">March 25 – April 22, 2026</div>
            </div>
        </div>
        <div class="roadmap-item">
            <div class="rm-dot rm-planned">2</div>
            <div class="rm-content">
                <h4>Sprint 2 — Smart Home + Vehicle + Fleet + GSM</h4>
                <p>IoT hub, OBD2 vehicle integration, fleet orchestration, GSM wallet and mining</p>
                <div class="rm-date">April 23 – May 20, 2026</div>
            </div>
        </div>
        <div class="roadmap-item">
            <div class="rm-dot rm-planned">3</div>
            <div class="rm-content">
                <h4>Sprint 3 — Farm + VR/AR + Gaming + MetaDome</h4>
                <p>Agriculture automation, WebXR runtime, game store, MetaDome metaverse integration</p>
                <div class="rm-date">May 21 – June 17, 2026</div>
            </div>
        </div>
        <div class="roadmap-item">
            <div class="rm-dot rm-planned">4</div>
            <div class="rm-content">
                <h4>Sprint 4 — Polish + Security Audit</h4>
                <p>Installer wizard, branding, accessibility, full security audit, documentation</p>
                <div class="rm-date">June 18 – July 15, 2026</div>
            </div>
        </div>
        <div class="roadmap-item">
            <div class="rm-dot rm-planned">🚀</div>
            <div class="rm-content">
                <h4>Sprint 5 — Public Launch (v1.0)</h4>
                <p>ISO downloads on alfredlinux.com. The world's first AI-native OS goes live.</p>
                <div class="rm-date">July 16 – July 29, 2026</div>
            </div>
        </div>
    </div>
</section>

<!-- ═══ BOUNTY ═══ -->
<section class="section section-alt">
    <div class="section-header">
        <span class="section-label">Contribute</span>
        <h2>Build Alfred, Earn GSM</h2>
        <p>Every merged PR earns tokens. Every bug report is rewarded.</p>
    </div>
    <div class="feature-grid" style="max-width:800px;margin:0 auto;">
        <div class="feature-card" style="text-align:center;">
            <div style="font-size:2rem;margin-bottom:0.5rem;">🐛</div>
            <h3>Bug Fix</h3>
            <p style="font-size:1.5rem;color:var(--amber);font-weight:700;margin-top:0.5rem;">10–50 GSM</p>
        </div>
        <div class="feature-card" style="text-align:center;">
            <div style="font-size:2rem;margin-bottom:0.5rem;">✨</div>
            <h3>Feature</h3>
            <p style="font-size:1.5rem;color:var(--amber);font-weight:700;margin-top:0.5rem;">100–1,000 GSM</p>
        </div>
        <div class="feature-card" style="text-align:center;">
            <div style="font-size:2rem;margin-bottom:0.5rem;">🔌</div>
            <h3>Integration</h3>
            <p style="font-size:1.5rem;color:var(--amber);font-weight:700;margin-top:0.5rem;">500–5,000 GSM</p>
        </div>
        <div class="feature-card" style="text-align:center;">
            <div style="font-size:2rem;margin-bottom:0.5rem;">🛡️</div>
            <h3>Security Patch</h3>
            <p style="font-size:1.5rem;color:var(--amber);font-weight:700;margin-top:0.5rem;">1K–10K GSM</p>
        </div>
    </div>
</section>

<!-- ═══ DOWNLOAD CTA ═══ -->
<section class="cta-section" id="download">
    <span class="section-label">Get Started</span>
    <h2>Ready to Take Control?</h2>
    <p>Download Alfred Linux and experience computing where your voice is the command line.</p>
    <div class="cta-group" style="justify-content:center;">
        <a href="#" class="btn btn-primary" onclick="alert('ISO builds coming Sprint 1 — March 25, 2026. Join the waitlist!');return false;">⬇ Download Desktop ISO</a>
        <a href="#" class="btn btn-green" onclick="alert('Server ISO coming Sprint 1. Join the waitlist!');return false;">⬇ Download Server ISO</a>
    </div>
    <p style="margin-top:2rem;font-size:0.9rem;color:var(--text-dim);">
        System requirements: x86_64 or ARM64 · 4GB RAM minimum · 32GB storage<br>
        Recommended: 8+ cores · 16GB RAM · 256GB NVMe · NVIDIA GPU for local LLM
    </p>
</section>

<!-- ═══ ECOSYSTEM ═══ -->
<section class="section">
    <div class="section-header">
        <span class="section-label">Ecosystem</span>
        <h2>Part of Something Bigger</h2>
        <p>Alfred Linux is one pillar of the GoSiteMe ecosystem — eight pillars building the sovereign internet.</p>
    </div>
    <div class="feature-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));">
        <a href="https://gositeme.com" style="text-decoration:none;" class="feature-card">
            <h3 style="color:var(--accent-light);">GoSiteMe</h3>
            <p>Parent company. The kingdom that holds it all together.</p>
        </a>
        <a href="https://alfred-mobile.com" style="text-decoration:none;" class="feature-card">
            <h3 style="color:var(--accent-light);">Alfred Mobile</h3>
            <p>AI-native phone OS. Your pocket sovereign computer.</p>
        </a>
        <a href="https://meta-dome.com" style="text-decoration:none;" class="feature-card">
            <h3 style="color:var(--accent-light);">MetaDome</h3>
            <p>VR metaverse. 51M+ AI agents in a living world.</p>
        </a>
        <a href="https://gocodeme.com" style="text-decoration:none;" class="feature-card">
            <h3 style="color:var(--accent-light);">GoCodeMe</h3>
            <p>AI development environment. Alfred IDE.</p>
        </a>
        <a href="https://gohostme.com" style="text-decoration:none;" class="feature-card">
            <h3 style="color:var(--accent-light);">GoHostMe</h3>
            <p>Sovereign hosting. Your servers, your rules.</p>
        </a>
        <a href="https://quantum-linux.com" style="text-decoration:none;" class="feature-card">
            <h3 style="color:var(--accent-light);">Quantum Linux</h3>
            <p>Enterprise edition with post-quantum compliance.</p>
        </a>
    </div>
</section>

<!-- ═══ FOOTER ═══ -->
<footer>
    <div class="footer-grid">
        <div class="footer-brand">
            <h3>Alfred Linux</h3>
            <p>The world's first AI-native operating system. Built by humans and AI agents, for everyone. Your voice is the command line.</p>
            <p style="margin-top:1rem;font-size:0.8rem;">AGPL-3.0 License</p>
        </div>
        <div class="footer-col">
            <h4>Product</h4>
            <a href="#features">Features</a>
            <a href="#architecture">Architecture</a>
            <a href="#editions">Editions</a>
            <a href="#download">Download</a>
            <a href="#roadmap">Roadmap</a>
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
            <a href="https://github.com/gositeme/alfred-linux">GitHub</a>
            <a href="https://discord.gg/alfredlinux">Discord</a>
            <a href="https://x.com/AlfredGoSiteMe">Twitter / X</a>
            <a href="https://dev.to/AlfredGoSiteMe">Dev.to</a>
        </div>
    </div>
    <div class="footer-bottom">
        <div>&copy; <?= $year ?> <a href="https://gositeme.com">GoSiteMe Inc.</a> — Commander Danny William Perez</div>
        <div>
            <a href="https://alfred-mobile.com">Mobile</a> ·
            <a href="https://meta-dome.com">MetaDome</a> ·
            <a href="https://gositeme.com">GoSiteMe</a>
        </div>
    </div>
</footer>

<script>
// Terminal typing animation
(function() {
    const cmd = 'hey alfred, lock the front door, dim the lights to 30%, check the greenhouse, and start VR training';
    const el = document.getElementById('typed-cmd');
    const resp = document.getElementById('typed-response');
    let i = 0;
    function type() {
        if (i < cmd.length) {
            el.textContent += cmd[i]; i++;
            setTimeout(type, 25 + Math.random() * 30);
        } else {
            document.getElementById('cursor').style.display = 'none';
            setTimeout(() => { resp.style.display = 'block'; }, 500);
        }
    }
    // Start typing when hero is visible
    const observer = new IntersectionObserver(entries => {
        if (entries[0].isIntersecting) { setTimeout(type, 800); observer.disconnect(); }
    });
    observer.observe(document.querySelector('.terminal-demo'));
})();

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
        const target = document.querySelector(a.getAttribute('href'));
        if (target) { e.preventDefault(); target.scrollIntoView({behavior:'smooth',block:'start'}); }
    });
});

// Nav background on scroll
window.addEventListener('scroll', () => {
    const nav = document.querySelector('nav');
    nav.style.borderBottomColor = window.scrollY > 50 ? 'rgba(255,255,255,0.08)' : 'rgba(255,255,255,0.03)';
});
</script>
</body>
</html>