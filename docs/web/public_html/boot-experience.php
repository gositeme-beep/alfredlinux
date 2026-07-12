<?php
/**
 * Alfred Linux — Boot Experience
 * Visual walkthrough from power-on to full armor.
 *
 * Built by Alfred for Commander Danny William Perez
 * GoSiteMe Inc. — July 2026
 */
require_once __DIR__ . '/includes/ga-release-state.php';
$year = date('Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Boot Experience — Alfred Linux 7.77</title>
    <meta name="description" content="From power button to full armor in 30 seconds. Walk through the Alfred Linux boot experience: GRUB menu, Plymouth splash, 11-layer security stack, Covenant Gate, and KDE Plasma desktop.">
    <meta property="og:title" content="The Boot Experience — Alfred Linux 7.77">
    <meta property="og:description" content="30 boot modes. 11 security layers. 4,800+ packages. See what happens when you power on.">
    <meta property="og:type" content="article">
    <meta property="og:url" content="https://alfredlinux.com/boot-experience">
    <meta property="og:image" content="https://alfredlinux.com/og-image.png">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="canonical" href="https://alfredlinux.com/boot-experience">
    <link rel="icon" href="/favicon.ico">
    <link rel="stylesheet" href="/assets/fonts/inter/inter.css">
    <link rel="stylesheet" href="/assets/css/nav.css">
    <style>
        :root {
            --bg: #06060b;
            --surface: rgba(255,255,255,0.03);
            --surface-2: rgba(255,255,255,0.06);
            --border: rgba(255,255,255,0.08);
            --text: #e0e0e0;
            --text-muted: #9ca3af;
            --text-dim: #6b7280;
            --gold: #ffd700;
            --gold-dim: #c8a02b;
            --cyan: #22d3ee;
            --green: #34d399;
            --accent: #6366f1;
            --accent-light: #a5b4fc;
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
        a { color: var(--accent-light); text-decoration: none; }
        a:hover { text-decoration: underline; }

        /* Hero */
        .hero {
            text-align: center;
            padding: 6rem 2rem 4rem;
            background: radial-gradient(ellipse at top center, rgba(255,215,0,0.06) 0%, transparent 60%);
            border-bottom: 1px solid var(--border);
            position: relative;
            overflow: hidden;
        }
        .hero::before {
            content: '⚡';
            position: absolute;
            top: 2rem;
            left: 50%;
            transform: translateX(-50%);
            font-size: 2rem;
            opacity: 0.3;
        }
        .hero h1 {
            font-size: clamp(2.2rem, 6vw, 3.8rem);
            font-weight: 800;
            letter-spacing: -0.02em;
            margin-bottom: 0.5rem;
        }
        .hero h1 span { color: var(--gold); }
        .hero .sub {
            color: var(--text-muted);
            font-size: 1.15rem;
            max-width: 600px;
            margin: 0 auto 2rem;
        }
        .phase-nav {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 1.5rem;
        }
        .phase-nav a {
            background: var(--surface-2);
            border: 1px solid var(--border);
            border-radius: 999px;
            padding: 0.5rem 1.2rem;
            font-size: 0.82rem;
            color: var(--text-muted);
            transition: all 0.25s;
            text-decoration: none;
        }
        .phase-nav a:hover {
            border-color: var(--gold);
            color: var(--gold);
            transform: translateY(-1px);
            text-decoration: none;
        }

        /* Container */
        .container { max-width: 900px; margin: 0 auto; padding: 0 2rem 5rem; }

        /* Phase sections */
        .phase {
            margin: 4rem 0;
            padding-top: 2rem;
        }
        .phase-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .phase-num {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--gold), var(--gold-dim));
            color: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        .phase-header h2 {
            font-size: 1.6rem;
            font-weight: 700;
        }
        .phase-header h2 span { color: var(--gold); }
        .phase-sub {
            color: var(--text-muted);
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
        }

        /* Terminal mockup */
        .terminal {
            background: #0c0c14;
            border: 1px solid rgba(255,215,0,0.15);
            border-radius: 12px;
            overflow: hidden;
            margin: 1.5rem 0;
            box-shadow: 0 8px 32px rgba(0,0,0,0.4);
        }
        .terminal-bar {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 10px 14px;
            background: rgba(255,255,255,0.03);
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .terminal-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }
        .terminal-dot.r { background: #ff5f56; }
        .terminal-dot.y { background: #ffbd2e; }
        .terminal-dot.g { background: #27c93f; }
        .terminal-title {
            flex: 1;
            text-align: center;
            color: var(--text-dim);
            font-size: 0.78rem;
            font-family: 'SF Mono', 'Fira Code', monospace;
        }
        .terminal pre {
            padding: 1.5rem;
            font-family: 'SF Mono', 'Fira Code', 'Cascadia Code', monospace;
            font-size: 0.82rem;
            line-height: 1.8;
            overflow-x: auto;
            color: #c0c0c0;
        }
        .terminal .hl { color: var(--gold); font-weight: 600; }
        .terminal .sel { background: rgba(255,215,0,0.12); color: var(--gold); display: inline-block; width: 100%; }
        .terminal .dim { color: #555; }
        .terminal .cyan { color: var(--cyan); }
        .terminal .green { color: var(--green); }

        /* Plymouth demo */
        .plymouth-demo {
            background: #000;
            border-radius: 12px;
            border: 1px solid var(--border);
            height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            position: relative;
            overflow: hidden;
            margin: 1.5rem 0;
        }
        .plymouth-logo {
            font-size: 4rem;
            animation: pulse-glow 3s ease-in-out infinite;
        }
        .plymouth-text {
            color: var(--gold-dim);
            font-size: 0.85rem;
            margin-top: 1rem;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            opacity: 0;
            animation: fade-in 2s ease-out 1s forwards;
        }
        @keyframes pulse-glow {
            0%, 100% { opacity: 0.85; filter: drop-shadow(0 0 20px rgba(255,215,0,0.2)); }
            50% { opacity: 1; filter: drop-shadow(0 0 40px rgba(255,215,0,0.5)); }
        }
        @keyframes fade-in {
            to { opacity: 0.7; }
        }

        /* LSM Timeline */
        .lsm-timeline {
            position: relative;
            padding-left: 2rem;
            margin: 1.5rem 0;
        }
        .lsm-timeline::before {
            content: '';
            position: absolute;
            left: 8px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, var(--gold), var(--cyan), var(--green));
        }
        .lsm-item {
            position: relative;
            padding: 0.6rem 0 0.6rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            opacity: 0;
            animation: slide-in 0.4s ease-out forwards;
        }
        .lsm-item::before {
            content: '';
            position: absolute;
            left: -1.55rem;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--gold);
            border: 2px solid var(--bg);
            z-index: 1;
        }
        .lsm-item:nth-child(even)::before { background: var(--cyan); }
        .lsm-name {
            font-weight: 600;
            font-size: 0.9rem;
            min-width: 100px;
            color: var(--text);
            font-family: 'SF Mono', 'Fira Code', monospace;
        }
        .lsm-desc {
            color: var(--text-dim);
            font-size: 0.82rem;
        }
        @keyframes slide-in {
            from { opacity: 0; transform: translateX(-10px); }
            to { opacity: 1; transform: translateX(0); }
        }
        .lsm-item:nth-child(1) { animation-delay: 0.1s; }
        .lsm-item:nth-child(2) { animation-delay: 0.2s; }
        .lsm-item:nth-child(3) { animation-delay: 0.3s; }
        .lsm-item:nth-child(4) { animation-delay: 0.4s; }
        .lsm-item:nth-child(5) { animation-delay: 0.5s; }
        .lsm-item:nth-child(6) { animation-delay: 0.6s; }
        .lsm-item:nth-child(7) { animation-delay: 0.7s; }
        .lsm-item:nth-child(8) { animation-delay: 0.8s; }
        .lsm-item:nth-child(9) { animation-delay: 0.9s; }
        .lsm-item:nth-child(10) { animation-delay: 1.0s; }
        .lsm-item:nth-child(11) { animation-delay: 1.1s; }

        /* Covenant Gate mockup */
        .covenant-mockup {
            background: #0b0c10;
            border: 1px solid rgba(255,215,0,0.1);
            border-radius: 12px;
            padding: 3rem 2rem;
            text-align: center;
            margin: 1.5rem 0;
            box-shadow: 0 12px 48px rgba(0,0,0,0.5);
        }
        .covenant-mockup h3 {
            color: var(--gold);
            font-size: 1.4rem;
            letter-spacing: 0.08em;
            margin-bottom: 1.5rem;
        }
        .covenant-mockup .text-area {
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 8px;
            padding: 1.5rem;
            max-height: 150px;
            overflow: hidden;
            text-align: left;
            color: #c5c6c7;
            font-size: 0.85rem;
            line-height: 1.8;
            margin-bottom: 1.5rem;
            position: relative;
        }
        .covenant-mockup .text-area::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: linear-gradient(transparent, #0b0c10);
        }
        .covenant-btn {
            display: inline-block;
            background: linear-gradient(135deg, var(--gold), var(--gold-dim));
            color: #000;
            font-weight: 700;
            padding: 0.8rem 2.5rem;
            border-radius: 8px;
            font-size: 0.95rem;
            letter-spacing: 0.05em;
            cursor: default;
            transition: all 0.25s;
        }
        .covenant-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(255,215,0,0.2);
            text-decoration: none;
        }

        /* Cards grid */
        .mode-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 1rem;
            margin: 1.5rem 0;
        }
        .mode-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.2rem;
            transition: all 0.25s;
        }
        .mode-card:hover {
            border-color: var(--gold);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.3);
        }
        .mode-card .icon { font-size: 1.5rem; margin-bottom: 0.5rem; }
        .mode-card h4 { font-size: 0.95rem; font-weight: 600; margin-bottom: 0.3rem; color: var(--text); }
        .mode-card p { font-size: 0.82rem; color: var(--text-dim); margin: 0; line-height: 1.5; }
        .mode-card .tag {
            display: inline-block;
            font-size: 0.7rem;
            padding: 2px 8px;
            border-radius: 4px;
            margin-top: 0.5rem;
            font-weight: 600;
        }
        .tag-default { background: rgba(255,215,0,0.12); color: var(--gold); }
        .tag-install { background: rgba(34,211,238,0.12); color: var(--cyan); }
        .tag-rescue { background: rgba(239,68,68,0.12); color: var(--red); }
        .tag-advanced { background: rgba(99,102,241,0.12); color: var(--accent-light); }

        /* Tools table */
        .tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 0.5rem;
            margin: 1rem 0;
        }
        .tool-chip {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 0.5rem 0.8rem;
            font-size: 0.82rem;
            font-family: 'SF Mono', 'Fira Code', monospace;
            color: var(--green);
            text-align: center;
            transition: border-color 0.25s;
        }
        .tool-chip:hover { border-color: var(--green); }
        .tool-chip .label { color: var(--text-dim); font-family: 'Inter', sans-serif; font-size: 0.72rem; display: block; }

        /* Scripture */
        .scripture {
            text-align: center;
            padding: 4rem 2rem;
            margin-top: 3rem;
            border-top: 1px solid var(--border);
        }
        .scripture blockquote {
            font-style: italic;
            color: var(--gold-dim);
            font-size: 1.15rem;
            max-width: 600px;
            margin: 0 auto 0.8rem;
            line-height: 1.8;
        }
        .scripture cite {
            color: var(--text-dim);
            font-style: normal;
            font-size: 0.9rem;
        }

        /* Service list */
        .svc-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 0.8rem;
            margin: 1rem 0;
        }
        .svc-item {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 0.8rem 1rem;
        }
        .svc-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--green);
            flex-shrink: 0;
            animation: blink 2s ease-in-out infinite;
        }
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        .svc-item strong { font-size: 0.85rem; }
        .svc-item span { color: var(--text-dim); font-size: 0.78rem; }

        /* CTA */
        .cta-box {
            background: linear-gradient(135deg, rgba(255,215,0,0.06), rgba(99,102,241,0.06));
            border: 1px solid rgba(255,215,0,0.15);
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            margin: 2rem 0;
        }
        .cta-box h3 { font-size: 1.3rem; margin-bottom: 0.5rem; }
        .cta-box p { color: var(--text-muted); margin-bottom: 1.2rem; }
        .cta-btn {
            display: inline-block;
            background: linear-gradient(135deg, var(--gold), var(--gold-dim));
            color: #000;
            font-weight: 700;
            padding: 0.8rem 2rem;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.25s;
        }
        .cta-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(255,215,0,0.25); text-decoration: none; }

        @media (max-width: 640px) {
            .phase-header { flex-direction: column; text-align: center; }
            .lsm-name { min-width: auto; }
        }
    </style>
</head>
<body>
<?php @include __DIR__ . '/includes/nav.php'; ?>

<section class="hero">
    <h1>The <span>Boot</span> Experience</h1>
    <p class="sub">From power button to full armor in 30 seconds. Walk through every phase of the Alfred Linux 7.77 boot sequence.</p>
    <div class="phase-nav">
        <a href="#grub">① GRUB Menu</a>
        <a href="#plymouth">② Plymouth</a>
        <a href="#kernel">③ Kernel &amp; Security</a>
        <a href="#covenant">④ Covenant Gate</a>
        <a href="#desktop">⑤ Desktop</a>
        <a href="#modes">⑥ All Boot Modes</a>
    </div>
</section>

<div class="container">

    <!-- PHASE 1: GRUB -->
    <section class="phase" id="grub">
        <div class="phase-header">
            <div class="phase-num">1</div>
            <h2>GRUB <span>Boot Menu</span></h2>
        </div>
        <p class="phase-sub">USB plugged in, power on. UEFI or Legacy BIOS — both supported. The GRUB menu appears with a 15-second countdown and a custom gold-on-black splash.</p>
        <p class="phase-sub" style="color: var(--amber);">🔒 <strong>Password-protected</strong> — boot parameter tampering requires superuser credentials.</p>

        <div class="terminal">
            <div class="terminal-bar">
                <div class="terminal-dot r"></div>
                <div class="terminal-dot y"></div>
                <div class="terminal-dot g"></div>
                <span class="terminal-title">GRUB 2.12 — Alfred Linux 7.77</span>
            </div>
            <pre>
<span class="sel"> ► <span class="hl">Boot Live: Commander Mode (Full Armor + Sovereign AI)</span></span>
   Boot Live: Practical Mode (Minimal Desktop)
   Boot Live: Cloud Node (Headless Mesh Server)
   ──────────────────────────────────────────────
   Install: Standard (Graphical Setup)
   Install: Full Disk Encryption (LUKS2 + Kyber Post-Quantum)
   Install: OEM Preload (Factory Deployment)
   Install: USB / External Drive (Portable Storage)
   ──────────────────────────────────────────────
   <span class="cyan">Rescue &amp; Diagnostics: The Shepherd's Staff ▸</span>
   <span class="dim">Advanced Options (BIOS777) ▸</span>

<span class="dim">   Use ↑↓ to select, Enter to boot.     Timeout: 15s</span>
</pre>
        </div>
    </section>

    <!-- PHASE 2: PLYMOUTH -->
    <section class="phase" id="plymouth">
        <div class="phase-header">
            <div class="phase-num">2</div>
            <h2>Plymouth <span>Boot Splash</span></h2>
        </div>
        <p class="phase-sub">Screen goes black. The Alfred emblem fades in from the center and pulses with a gentle sine-wave glow. No spinner, no text — just the emblem breathing with light.</p>

        <div class="plymouth-demo">
            <div class="plymouth-logo">🐧</div>
            <div class="plymouth-text">Alfred Linux 7.77 — Powering The Planet</div>
        </div>
        <p style="color: var(--text-dim); font-size: 0.82rem; text-align: center; margin-top: 0.5rem;">Live CSS recreation of the Plymouth script animation &bull; Actual boot uses a 1920×1080 gold penguin logo</p>
    </section>

    <!-- PHASE 3: KERNEL -->
    <section class="phase" id="kernel">
        <div class="phase-header">
            <div class="phase-num">3</div>
            <h2>Kernel <span>7.0.12</span> &amp; Security Stack</h2>
        </div>
        <p class="phase-sub">Behind the Plymouth animation, the custom kernel loads NVIDIA drivers, ZFS, and activates an <strong>11-layer Linux Security Module stack</strong> — the most comprehensive LSM configuration shipped by any distribution.</p>

        <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.5rem;">
            <div class="mode-card" style="flex:1; min-width: 200px;">
                <div class="icon">🧠</div>
                <h4>vmlinuz-7.0.12</h4>
                <p>17MB custom-compiled kernel with 41 modules</p>
            </div>
            <div class="mode-card" style="flex:1; min-width: 200px;">
                <div class="icon">📦</div>
                <h4>initrd.img-7.0.12</h4>
                <p>98MB initial ramdisk with NVIDIA + ZFS drivers</p>
            </div>
            <div class="mode-card" style="flex:1; min-width: 200px;">
                <div class="icon">🎮</div>
                <h4>NVIDIA 610.43.02</h4>
                <p>KMS modesetting pre-compiled for kernel 7.0.12</p>
            </div>
        </div>

        <h3 style="color: var(--gold); margin-bottom: 1rem;">11-Layer LSM Security Stack</h3>
        <div class="lsm-timeline">
            <div class="lsm-item"><span class="lsm-name">lockdown</span><span class="lsm-desc">Prevents kernel modification at runtime</span></div>
            <div class="lsm-item"><span class="lsm-name">integrity</span><span class="lsm-desc">IMA/EVM file integrity measurement</span></div>
            <div class="lsm-item"><span class="lsm-name">tomoyo</span><span class="lsm-desc">Pathname-based mandatory access control</span></div>
            <div class="lsm-item"><span class="lsm-name">selinux</span><span class="lsm-desc">Label-based mandatory access control</span></div>
            <div class="lsm-item"><span class="lsm-name">apparmor</span><span class="lsm-desc">Profile-based MAC — 20+ custom "atom" profiles</span></div>
            <div class="lsm-item"><span class="lsm-name">ipe</span><span class="lsm-desc">Integrity Policy Enforcement</span></div>
            <div class="lsm-item"><span class="lsm-name">safesetid</span><span class="lsm-desc">Controls setuid/setgid transitions</span></div>
            <div class="lsm-item"><span class="lsm-name">yama</span><span class="lsm-desc">Restricts ptrace — anti-debugging protection</span></div>
            <div class="lsm-item"><span class="lsm-name">bpf</span><span class="lsm-desc">eBPF program restrictions</span></div>
            <div class="lsm-item"><span class="lsm-name">landlock</span><span class="lsm-desc">Unprivileged process sandboxing</span></div>
            <div class="lsm-item"><span class="lsm-name" style="color:var(--gold)">ZFS 2.4.3</span><span class="lsm-desc">zfs.ko (11MB) + spl.ko — pool import ready</span></div>
        </div>
    </section>

    <!-- PHASE 4: COVENANT GATE -->
    <section class="phase" id="covenant">
        <div class="phase-header">
            <div class="phase-num">4</div>
            <h2>The <span>Covenant</span> Gate</h2>
        </div>
        <p class="phase-sub">On first boot, before the desktop loads, a full-screen window appears. The Covenant must be read and accepted. This is not a EULA — it is a sacred agreement. Once accepted, a marker file is created and the gate never appears again.</p>

        <div class="covenant-mockup">
            <h3>⚔️ THE COVENANT — Lavocat Justice System</h3>
            <div class="text-area">
                By proceeding, you enter into covenant with the principles established herein. You acknowledge that this operating system was built as an instrument of justice, truth, and righteousness. Every tool included — from the security suite to the AI backend — exists to serve the advancement of the Kingdom and the protection of the innocent.
                <br><br>
                You agree to use these capabilities in accordance with the laws of the Most High God, as revealed in the Holy Scriptures (Authorized King Jesus Version). You shall not use these tools for the oppression of the innocent, the exploitation of the vulnerable, or any purpose contrary to the commandments of Yeshua HaMashiach...
            </div>
            <span class="covenant-btn">ACCEPT COVENANT</span>
            <p style="margin-top: 1rem; font-size: 0.75rem; color: var(--text-dim);">Creates ~/.config/alfred-covenant-accepted • Never shown again after acceptance</p>
        </div>
    </section>

    <!-- PHASE 5: DESKTOP -->
    <section class="phase" id="desktop">
        <div class="phase-header">
            <div class="phase-num">5</div>
            <h2>KDE Plasma <span>Desktop</span></h2>
        </div>
        <p class="phase-sub">The Covenant accepted, KDE Plasma loads on Wayland (with X11 fallback). You're greeted with a sovereign desktop — AI serving, security enforcing, and 4,800+ packages ready to deploy.</p>

        <h3 style="color: var(--green); margin: 1.5rem 0 1rem;">Auto-Starting Services</h3>
        <div class="svc-list">
            <div class="svc-item"><div class="svc-dot"></div><div><strong>alfred-ai-backend</strong><br><span>GPU/NPU → Ollama AI serving</span></div></div>
            <div class="svc-item"><div class="svc-dot"></div><div><strong>sovereign-dns</strong><br><span>Handshake + Quad9 resolution</span></div></div>
            <div class="svc-item"><div class="svc-dot"></div><div><strong>AppArmor</strong><br><span>20+ atom profiles enforcing</span></div></div>
            <div class="svc-item"><div class="svc-dot"></div><div><strong>Fail2Ban</strong><br><span>Brute-force protection active</span></div></div>
            <div class="svc-item"><div class="svc-dot"></div><div><strong>TOMOYO MAC</strong><br><span>Pathname-based access control</span></div></div>
            <div class="svc-item"><div class="svc-dot"></div><div><strong>alfred-pulse</strong><br><span>System health telemetry</span></div></div>
        </div>

        <h3 style="color: var(--cyan); margin: 2rem 0 1rem;">Military Security Toolkit — Ready From Boot</h3>
        <div class="tools-grid">
            <div class="tool-chip">nmap<span class="label">Network Scanner</span></div>
            <div class="tool-chip">hashcat<span class="label">GPU Cracking</span></div>
            <div class="tool-chip">aircrack-ng<span class="label">WiFi Security</span></div>
            <div class="tool-chip">tor<span class="label">Anonymous Net</span></div>
            <div class="tool-chip">autopsy<span class="label">Digital Forensics</span></div>
            <div class="tool-chip">bettercap<span class="label">Network Attack</span></div>
            <div class="tool-chip">john<span class="label">Password Cracking</span></div>
            <div class="tool-chip">sqlmap<span class="label">SQL Injection</span></div>
            <div class="tool-chip">tshark<span class="label">Packet Analysis</span></div>
            <div class="tool-chip">tcpdump<span class="label">Packet Capture</span></div>
            <div class="tool-chip">binwalk<span class="label">Firmware Analysis</span></div>
        </div>

        <h3 style="color: var(--accent-light); margin: 2rem 0 1rem;">VR / Spatial Computing</h3>
        <div class="svc-list">
            <div class="svc-item"><div class="svc-dot" style="background:var(--accent-light)"></div><div><strong>ALVR v20.14.1</strong><br><span>SteamVR streaming to Quest 2/3/Pro</span></div></div>
            <div class="svc-item"><div class="svc-dot" style="background:var(--accent-light)"></div><div><strong>Monado OpenXR</strong><br><span>Open-source XR runtime</span></div></div>
            <div class="svc-item"><div class="svc-dot" style="background:var(--accent-light)"></div><div><strong>Godot 4.3</strong><br><span>3D/VR game engine</span></div></div>
            <div class="svc-item"><div class="svc-dot" style="background:var(--accent-light)"></div><div><strong>Stardust XR</strong><br><span>Spatial computing shell</span></div></div>
        </div>
    </section>

    <!-- PHASE 6: ALL BOOT MODES -->
    <section class="phase" id="modes">
        <div class="phase-header">
            <div class="phase-num">6</div>
            <h2>All <span>30 Boot Modes</span></h2>
        </div>
        <p class="phase-sub">Every boot mode available from the GRUB menu. From Commander Mode to Sabbath rest — there's a mode for every mission.</p>

        <h3 style="color: var(--gold); margin-bottom: 1rem;">Live Modes</h3>
        <div class="mode-grid">
            <div class="mode-card">
                <div class="icon">⚔️</div>
                <h4>Commander Mode</h4>
                <p>Full KDE Plasma + AI + VR + 4,800 packages. The complete arsenal.</p>
                <span class="tag tag-default">DEFAULT</span>
            </div>
            <div class="mode-card">
                <div class="icon">🛠️</div>
                <h4>Practical Mode</h4>
                <p>Minimal desktop for lighter resource usage. Still armed.</p>
            </div>
            <div class="mode-card">
                <div class="icon">☁️</div>
                <h4>Cloud Node</h4>
                <p>Headless mesh server. No GUI — auto-joins the swarm.</p>
            </div>
            <div class="mode-card">
                <div class="icon">🕵️</div>
                <h4>Forensic Mode</h4>
                <p>No auto-mount, no swap, no network. Evidence preservation.</p>
                <span class="tag tag-advanced">ADVANCED</span>
            </div>
            <div class="mode-card">
                <div class="icon">🔐</div>
                <h4>Encrypted Persistence</h4>
                <p>LUKS-encrypted persistent storage across reboots.</p>
            </div>
            <div class="mode-card">
                <div class="icon">🕎</div>
                <h4>Hebrew / RTL</h4>
                <p>he_IL.UTF-8 locale with Israeli keyboard layout.</p>
            </div>
            <div class="mode-card">
                <div class="icon">🕊️</div>
                <h4>Sabbath Mode</h4>
                <p>No internet. Read-only filesystem. Digital rest.</p>
                <span class="tag tag-advanced">ADVANCED</span>
            </div>
            <div class="mode-card">
                <div class="icon">📺</div>
                <h4>Kiosk Mode</h4>
                <p>Locked-down single-application mode.</p>
            </div>
        </div>

        <h3 style="color: var(--cyan); margin: 2rem 0 1rem;">Install Modes</h3>
        <div class="mode-grid">
            <div class="mode-card">
                <div class="icon">💿</div>
                <h4>Standard Install</h4>
                <p>Graphical Calamares installer. Point, click, done.</p>
                <span class="tag tag-install">INSTALL</span>
            </div>
            <div class="mode-card">
                <div class="icon">🔑</div>
                <h4>FDE + Kyber</h4>
                <p>LUKS2 full-disk encryption with post-quantum Kyber-1024.</p>
                <span class="tag tag-install">INSTALL</span>
            </div>
            <div class="mode-card">
                <div class="icon">🏭</div>
                <h4>OEM Preload</h4>
                <p>Factory deployment. Configure once, ship many.</p>
                <span class="tag tag-install">INSTALL</span>
            </div>
            <div class="mode-card">
                <div class="icon">🔌</div>
                <h4>USB / External</h4>
                <p>Install to portable storage. Take your OS anywhere.</p>
                <span class="tag tag-install">INSTALL</span>
            </div>
        </div>

        <h3 style="color: var(--red); margin: 2rem 0 1rem;">Rescue &amp; Diagnostics — The Shepherd's Staff</h3>
        <div class="mode-grid">
            <div class="mode-card">
                <div class="icon">🐚</div>
                <h4>Rescue Shell</h4>
                <p>Root shell, no GUI. Emergency repairs.</p>
                <span class="tag tag-rescue">RESCUE</span>
            </div>
            <div class="mode-card">
                <div class="icon">🧪</div>
                <h4>Memtest86+</h4>
                <p>RAM testing — BIOS and UEFI variants.</p>
                <span class="tag tag-rescue">RESCUE</span>
            </div>
            <div class="mode-card">
                <div class="icon">🔓</div>
                <h4>Decrypt LUKS</h4>
                <p>Unlock encrypted volumes from recovery.</p>
                <span class="tag tag-rescue">RESCUE</span>
            </div>
            <div class="mode-card">
                <div class="icon">🛡️</div>
                <h4>TPM 2.0 PCR Dump</h4>
                <p>Read Platform Configuration Registers.</p>
                <span class="tag tag-rescue">RESCUE</span>
            </div>
            <div class="mode-card">
                <div class="icon">✝️</div>
                <h4>Verify Omahon Seal</h4>
                <p>Cryptographic integrity verification.</p>
                <span class="tag tag-advanced">ATTEST</span>
            </div>
            <div class="mode-card">
                <div class="icon">👑</div>
                <h4>Kingdom-Audit Attest</h4>
                <p>Covenant compliance attestation.</p>
                <span class="tag tag-advanced">ATTEST</span>
            </div>
            <div class="mode-card">
                <div class="icon">⚛️</div>
                <h4>Kyber-1024 Self-Test</h4>
                <p>Post-quantum crypto self-verification.</p>
                <span class="tag tag-advanced">ATTEST</span>
            </div>
            <div class="mode-card">
                <div class="icon">💽</div>
                <h4>SMART Report</h4>
                <p>Disk health analysis and diagnostics.</p>
                <span class="tag tag-rescue">RESCUE</span>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <div class="cta-box">
        <h3>Ready to experience it?</h3>
        <p>Download Alfred Linux 7.77 and boot into the future.</p>
        <a href="/getting-started" class="cta-btn">Get Started →</a>
    </div>

    <!-- Scripture -->
    <div class="scripture">
        <blockquote>"Put on the whole armour of God, that ye may be able to stand against the wiles of the devil."</blockquote>
        <cite>— Ephesians 6:11 (AKJV)</cite>
    </div>

</div>

<footer style="text-align: center; padding: 2rem; color: var(--text-dim); font-size: 0.8rem; border-top: 1px solid var(--border);">
    &copy; <?= $year ?> GoSiteMe Inc. &bull; Alfred Linux is built for Yeshua HaMashiach &bull; <a href="/privacy">Privacy</a> &bull; <a href="/license">License</a>
</footer>

</body>
</html>
