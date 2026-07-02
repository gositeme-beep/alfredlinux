<?php
/**
 * Alfred Linux — Developer Foundation Portal
 * The page that makes developers start here, not at kernel.org or Debian.
 */
$year = date('Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/favicon.ico">
    <title>Build on Alfred Linux — Developer Foundation</title>
    <meta name="description" content="Don't start from kernel.org. Don't fork Ubuntu. Build your Linux project on Alfred — the AI-native foundation with kernel 7.0, 500+ MCP tools, voice engine, and a 6-layer build system ready to fork.">
    <meta name="keywords" content="Alfred Linux developer, build Linux distro, fork Alfred, custom Linux, AI Linux SDK, MCP tools, live-build, Debian Trixie">
    <meta property="og:title" content="Build on Alfred Linux — Developer Foundation">
    <meta property="og:description" content="The foundation other distros build on. Kernel 7.0. 500+ AI tools. Voice engine. Fork it. Ship it.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://alfredlinux.com/developers">
    <meta property="og:image" content="https://alfredlinux.com/og-image.png">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Build on Alfred Linux — Developer Foundation">
    <meta name="twitter:description" content="The foundation other distros build on. Kernel 7.0. 500+ AI tools. Voice engine. Fork it. Ship it.">
    <meta name="twitter:image" content="https://alfredlinux.com/og-image.png">
    <link rel="canonical" href="https://alfredlinux.com/developers">
    <link rel="stylesheet" href="/assets/fonts/inter/inter.css">
    <link rel="stylesheet" href="/assets/fonts/jetbrains-mono/jetbrains-mono.css">
    <link rel="stylesheet" href="/assets/css/nav.css">
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
        body { font-family: 'Inter', -apple-system, sans-serif; background: var(--bg); color: var(--text); line-height: 1.7; }
        a { color: var(--accent-light); }


        .container { max-width: 1100px; margin: 0 auto; padding: 0 2rem; }

        /* Hero */
        .dev-hero { padding: 8rem 2rem 4rem; text-align: center; max-width: 900px; margin: 0 auto; }
        .dev-hero h1 { font-size: clamp(2rem, 5vw, 3.5rem); font-weight: 900; line-height: 1.1; margin-bottom: 1.5rem; }
        .dev-hero h1 em { color: var(--accent-light); font-style: normal; }
        .dev-hero .lead { font-size: 1.25rem; color: var(--text-muted); max-width: 700px; margin: 0 auto 2rem; }

        /* Why not section */
        .why-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin: 2rem 0 3rem; }
        .why-card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; }
        .why-card h3 { font-size: 1.1rem; font-weight: 700; margin-bottom: 0.5rem; color: var(--red); }
        .why-card.good h3 { color: var(--green); }
        .why-card p { font-size: 0.9rem; color: var(--text-muted); }

        /* Sections */
        .section { padding: 4rem 2rem; max-width: 1100px; margin: 0 auto; }
        .section-alt { background: var(--surface); }
        .section h2 { font-size: clamp(1.5rem, 3vw, 2.2rem); font-weight: 800; margin-bottom: 0.5rem; }
        .section .subtitle { color: var(--text-muted); font-size: 1.1rem; margin-bottom: 2rem; }
        .section-label { display: inline-block; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.15em; color: var(--accent-light); background: rgba(99,102,241,0.1); padding: 0.3rem 0.8rem; border-radius: 20px; margin-bottom: 0.75rem; }

        /* Code blocks */
        .code-block { background: #0d1117; border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 1.5rem; margin: 1.5rem 0; overflow-x: auto; font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; line-height: 1.8; color: #c9d1d9; }
        .code-block .comment { color: #8b949e; }
        .code-block .cmd { color: var(--green); }
        .code-block .flag { color: var(--cyan); }
        .code-block .string { color: var(--amber); }

        /* Layer stack */
        .layer-stack { display: flex; flex-direction: column; gap: 0; margin: 2rem 0; }
        .layer { padding: 1.2rem 1.5rem; border: 1px solid var(--border); display: flex; align-items: center; gap: 1rem; }
        .layer:first-child { border-radius: 12px 12px 0 0; }
        .layer:last-child { border-radius: 0 0 12px 12px; }
        .layer-num { font-family: 'JetBrains Mono', monospace; font-weight: 700; font-size: 0.8rem; color: var(--accent-light); min-width: 30px; }
        .layer-name { font-weight: 700; min-width: 160px; }
        .layer-desc { color: var(--text-muted); font-size: 0.9rem; }
        .layer-yours { background: rgba(99,102,241,0.08); border-color: var(--accent); }

        /* Feature grid */
        .feature-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.5rem; margin: 2rem 0; }
        .feature-card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; transition: border-color 0.2s; }
        .feature-card:hover { border-color: var(--border-hover); }
        .feature-card h3 { font-size: 1rem; font-weight: 700; margin-bottom: 0.4rem; }
        .feature-card p { font-size: 0.85rem; color: var(--text-muted); }
        .feature-card code { font-family: 'JetBrains Mono', monospace; font-size: 0.8rem; color: var(--cyan); background: rgba(34,211,238,0.08); padding: 0.15rem 0.4rem; border-radius: 4px; }

        /* CTA */
        .btn { display: inline-block; padding: 0.8rem 1.8rem; border-radius: 10px; font-weight: 600; text-decoration: none; font-size: 1rem; transition: all 0.2s; }
        .btn-primary { background: var(--accent); color: #fff; }
        .btn-primary:hover { background: var(--accent2); transform: translateY(-1px); }
        .btn-outline { border: 1px solid var(--border); color: var(--text); background: transparent; }
        .btn-outline:hover { border-color: var(--accent); color: var(--accent-light); }
        .cta-group { display: flex; gap: 1rem; flex-wrap: wrap; justify-content: center; margin: 2rem 0; }

        /* Steps */
        .steps { counter-reset: step; margin: 2rem 0; }
        .step { display: flex; gap: 1.5rem; margin-bottom: 2rem; }
        .step::before { counter-increment: step; content: counter(step); font-family: 'JetBrains Mono', monospace; font-size: 1.5rem; font-weight: 800; color: var(--accent); min-width: 40px; text-align: center; }
        .step-content h3 { font-size: 1.1rem; font-weight: 700; margin-bottom: 0.3rem; }
        .step-content p { font-size: 0.9rem; color: var(--text-muted); }

        /* Compare table */
        .compare-table { width: 100%; border-collapse: collapse; margin: 2rem 0; font-size: 0.9rem; }
        .compare-table th { text-align: left; padding: 0.8rem; border-bottom: 2px solid var(--accent); color: var(--accent-light); font-weight: 700; }
        .compare-table td { padding: 0.8rem; border-bottom: 1px solid var(--border); }
        .compare-table tr:hover td { background: var(--surface-hover); }
        .compare-table .check { color: var(--green); font-weight: 700; }
        .compare-table .cross { color: var(--text-dim); }

        /* Footer */
        footer { padding: 3rem 2rem; text-align: center; border-top: 1px solid var(--border); color: var(--text-dim); font-size: 0.85rem; }

        @media (max-width: 768px) {
            .why-grid, .feature-grid { grid-template-columns: 1fr; }
            .layer { flex-direction: column; align-items: flex-start; gap: 0.3rem; }
            .compare-table { font-size: 0.75rem; }
        }
    </style>
    <script type="application/ld+json">
    {"@context":"https://schema.org","@type":"WebPage","name":"Developers — Alfred Linux","description":"Build on Alfred Linux. Clone the source, write hooks, create custom ISOs, contribute to the AI-native operating system.","url":"https://alfredlinux.com/developers","isPartOf":{"@type":"WebSite","name":"Alfred Linux","url":"https://alfredlinux.com"},"publisher":{"@type":"Organization","name":"GoSiteMe Inc.","url":"https://gositeme.com"}}
    </script>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/img/favicon/favicon-16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/img/favicon/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#D4AF37">
</head>
<body>

<?php $currentPage = 'developers'; include __DIR__ . '/includes/nav.php'; ?>

<!-- HERO -->
<div class="dev-hero">
    <span class="section-label">Developer Foundation</span>
    <h1>Don't Start from Scratch.<br>Start from <em>Alfred</em>.</h1>
    <p class="lead">Every great Linux distro started by forking something else. Ubuntu forked Debian. Mint forked Ubuntu. Pop!_OS forked Ubuntu. <strong>Your project starts here.</strong></p>
    <div class="cta-group">
        <a href="#fork" class="btn btn-primary">Fork Alfred Linux</a>
        <a href="#build" class="btn btn-outline">Build Guide</a>
        <a href="/forge/" class="btn btn-outline" target="_blank" rel="noopener">★ Source Code</a>
    </div>
</div>

<!-- WHY NOT START FROM SCRATCH -->
<div class="section">
    <span class="section-label">The Problem</span>
    <h2>Starting from Debian is 10,000 hours of wheel-reinvention.</h2>
    <p class="subtitle">Here's what you'd have to build yourself — or what you get for free by forking Alfred.</p>

    <div class="why-grid">
        <div class="why-card">
            <h3>✗ Start from Debian</h3>
            <p>Bare system. No AI. No voice. No agent. No build pipeline. No branding hooks. You're months from a bootable ISO with anything useful.</p>
        </div>
        <div class="why-card">
            <h3>✗ Start from Ubuntu</h3>
            <p>Snap packages you can't remove. Telemetry. Canonical lock-in. Your "custom distro" is actually Ubuntu with a wallpaper change.</p>
        </div>
        <div class="why-card">
            <h3>✗ Start from kernel.org</h3>
            <p>You'll spend 6 months getting networking to work before you can even think about your actual project.</p>
        </div>
        <div class="why-card good">
            <h3>✓ Start from Alfred Linux</h3>
            <p>Kernel 7.0.12. UEFI + BIOS. AI agent built in. Voice engine. 500+ MCP tools. 6-layer build system. 41 security modules + Omahon Seal. One command to build your ISO. Fork and ship.</p>
        </div>
    </div>
</div>

<!-- WHAT YOU GET -->
<div class="section" id="layers" style="background: var(--surface); padding: 4rem 2rem; max-width: 100%;">
    <div style="max-width: 1100px; margin: 0 auto;">
    <span class="section-label">What You Inherit</span>
    <h2>Six layers. All yours to extend.</h2>
    <p class="subtitle">Alfred Linux is a layered architecture. Fork any layer, replace any layer, extend any layer.</p>

    <div class="layer-stack">
        <div class="layer layer-yours">
            <span class="layer-num">L7</span>
            <span class="layer-name" style="color:var(--accent-light);">Your Project</span>
            <span class="layer-desc">← This is where you start. Everything below is already done.</span>
        </div>
        <div class="layer">
            <span class="layer-num">L6</span>
            <span class="layer-name">Calamares</span>
            <span class="layer-desc">Graphical installer — let your users install to disk in 3 clicks</span>
        </div>
        <div class="layer">
            <span class="layer-num">L5</span>
            <span class="layer-name">Alfred Search</span>
            <span class="layer-desc">Meilisearch — local-first search engine, no cloud dependency</span>
        </div>
        <div class="layer">
            <span class="layer-num">L4</span>
            <span class="layer-name">Alfred Voice</span>
            <span class="layer-desc">Kokoro TTS engine — your OS speaks. Offline. No API keys.</span>
        </div>
        <div class="layer">
            <span class="layer-num">L3</span>
            <span class="layer-name">Alfred IDE</span>
            <span class="layer-desc">Alfred IDE — full VS Code-compatible dev environment in browser</span>
        </div>
        <div class="layer">
            <span class="layer-num">L2</span>
            <span class="layer-name">Alfred Browser</span>
            <span class="layer-desc">Tauri + WebKitGTK sovereign browser — no Google, no telemetry</span>
        </div>
        <div class="layer">
            <span class="layer-num">L1.5</span>
            <span class="layer-name">🔏 Omahon Seal</span>
            <span class="layer-desc">6-module runtime integrity: Boot Seal, Watchman, Vault, Shell Guard, Secure Erase, Attestation — the incorruptible foundation</span>
        </div>
        <div class="layer">
            <span class="layer-num">L1</span>
            <span class="layer-name">Base System</span>
            <span class="layer-desc">Debian Trixie · Kernel 7.0.12 · Wayland 3D Cube4 · Plymouth · LightDM · Firmware</span>
        </div>
    </div>

    <p style="text-align:center;margin-top:1rem;color:var(--text-dim);font-size:0.9rem;">Each layer is a single hook script. Remove ones you don't need. Add your own. Build your ISO in one command.</p>
    </div>
</div>

<!-- FORK GUIDE -->
<div class="section" id="fork">
    <span class="section-label">Fork in 5 Minutes</span>
    <h2>Your custom Linux distro. Today.</h2>
    <p class="subtitle">Not weeks. Not months. Today.</p>

    <div class="steps">
        <div class="step">
            <div class="step-content">
                <h3>Clone the repository</h3>
                <p>Get the entire build system — scripts, hooks, package lists, branding assets.</p>
                <div class="code-block">
                    <span class="cmd">git clone</span> https://alfredlinux.com/forge/commander/alfredlinux.com.git my-distro<br>
                    <span class="cmd">cd</span> my-distro
                </div>
            </div>
        </div>
        <div class="step">
            <div class="step-content">
                <h3>Customize your layers</h3>
                <p>Edit hooks in <code>config/hooks/live/</code>. Each is a self-contained bash script. Remove what you don't need, add what you do.</p>
                <div class="code-block">
                    <span class="comment"># Want voice but not the browser? Remove the browser hook:</span><br>
                    <span class="cmd">rm</span> config/hooks/live/0200-alfred-browser.hook.chroot<br><br>
                    <span class="comment"># Add your own layer:</span><br>
                    <span class="cmd">cat</span> > config/hooks/live/0700-my-app.hook.chroot &lt;&lt; <span class="string">'EOF'</span><br>
                    #!/bin/bash<br>
                    <span class="cmd">apt-get install -y</span> my-custom-package<br>
                    <span class="comment"># Your setup logic here</span><br>
                    <span class="string">EOF</span>
                </div>
            </div>
        </div>
        <div class="step">
            <div class="step-content">
                <h3>Brand it</h3>
                <p>Hook <code>0100-alfred-customize.hook.chroot</code> handles all branding — plymouth boot screen, wallpapers, fastfetch ASCII art, LightDM greeter. Change the names and images. Done.</p>
            </div>
        </div>
        <div class="step">
            <div class="step-content">
                <h3>Build your ISO</h3>
                <p>One command. Outputs a bootable hybrid ISO (BIOS + UEFI).</p>
                <div class="code-block">
                    <span class="cmd">sudo</span> ./scripts/build-unified.sh ga <span class="flag">--uefi</span><br><br>
                    <span class="comment"># Output: iso-output/my-distro-3.0-rc-amd64-20260406.iso</span><br>
                    <span class="comment"># Size: ~2.5 GB · Boots on any PC · BIOS + UEFI</span>
                </div>
            </div>
        </div>
        <div class="step">
            <div class="step-content">
                <h3>Ship it</h3>
                <p>Your ISO. Your distro. Your name. Built on a proven, AI-native foundation that already works.</p>
            </div>
        </div>
    </div>
</div>

<!-- TOOL ECOSYSTEM -->
<div class="section" id="tools" style="background: var(--surface); padding: 4rem 2rem; max-width: 100%;">
    <div style="max-width: 1100px; margin: 0 auto;">
    <span class="section-label">Built-in SDK</span>
    <h2>500+ MCP tools. AI agent harness. Voice engine.</h2>
    <p class="subtitle">Every Alfred Linux ISO ships with infrastructure that would take you years to build.</p>

    <div class="feature-grid">
        <div class="feature-card">
            <h3>AI Agent Harness</h3>
            <p>Multi-provider (Anthropic, OpenAI, Groq). Tool-calling loop. Session management. HTTP + CLI interface.</p>
            <code>curl localhost:3102/chat</code>
        </div>
        <div class="feature-card">
            <h3>500+ MCP Tools</h3>
            <p>File operations, git, database, web scraping, code analysis, system administration — all callable by the AI agent.</p>
            <code>gocodeme-mcp:3006</code>
        </div>
        <div class="feature-card">
            <h3>Kokoro TTS Voice</h3>
            <p>Offline text-to-speech. No cloud. No API keys. Your OS speaks. Hook into it from any application.</p>
            <code>/usr/local/bin/kokoro</code>
        </div>
        <div class="feature-card">
            <h3>Meilisearch</h3>
            <p>Instant local search engine. Index anything. Sub-50ms results. Typo-tolerant. Runs on the device.</p>
            <code>localhost:7700</code>
        </div>
        <div class="feature-card">
            <h3>Alfred IDE</h3>
            <p>Full VS Code in the browser. Extensions. Terminal. Git. Debug. Accessible from any device on the network.</p>
            <code>localhost:8443</code>
        </div>
        <div class="feature-card">
            <h3>Alfred Remote — SSH</h3>
            <p>Extension pack in the site tree: your branding, Microsoft&rsquo;s battle-tested Remote - SSH under the hood. Build a VSIX from <code>extensions/alfred-remote-ssh/</code> with <code>bash pack-vsix.sh</code> (needs Node), then <em>Install from VSIX</em> in Cursor or VS Code.</p>
            <code>extensions/alfred-remote-ssh/pack-vsix.sh</code>
        </div>
        <div class="feature-card">
            <h3>Live-Build Pipeline</h3>
            <p>Cumulative 6-stage build system. Each stage adds a layer. Skip what you don't need. Build from <code>b1</code> to <code>rc</code>.</p>
            <code>build/scripts/build-unified.sh</code>
        </div>
    </div>
    </div>
</div>

<!-- BUILD REQUIREMENTS -->
<div class="section" id="build">
    <span class="section-label">Build Requirements</span>
    <h2>What you need to build Alfred Linux (or your fork).</h2>

    <div class="feature-grid" style="grid-template-columns: 1fr 1fr; margin: 2rem 0;">
        <div class="feature-card">
            <h3>Build Host</h3>
            <p>Debian or Ubuntu server with root access<br>
            8+ GB RAM recommended<br>
            30+ GB free disk space<br>
            Fast internet for package downloads</p>
        </div>
        <div class="feature-card">
            <h3>Packages</h3>
            <p>
                <code>live-build</code> <code>debootstrap</code><br>
                <code>squashfs-tools</code> <code>xorriso</code><br>
                <code>syslinux-utils</code><br>
                UEFI: <code>grub-efi-amd64-bin</code> <code>mtools</code>
            </p>
        </div>
    </div>

    <div class="code-block">
        <span class="comment"># Install build dependencies on Debian/Ubuntu:</span><br>
        <span class="cmd">sudo apt install</span> live-build debootstrap squashfs-tools xorriso syslinux-utils \<br>
        &nbsp;&nbsp;&nbsp;&nbsp;grub-efi-amd64-bin grub-common mtools dosfstools<br><br>
        <span class="comment"># Clone and build:</span><br>
        <span class="cmd">git clone</span> https://alfredlinux.com/forge/commander/alfredlinux.com.git<br>
        <span class="cmd">cd</span> alfred-linux<br>
        <span class="cmd">sudo</span> ./scripts/build-unified.sh ga <span class="flag">--uefi</span>
    </div>

    <p style="margin-top:2.5rem;color:var(--text-dim);max-width:760px;line-height:1.65;font-size:0.95rem;">
        <strong>CI on GoForge:</strong> the repo ships <code>.gitea/workflows/security-audit.yml</code>
        (and <code>.forgejo/workflows/security-audit.yml</code>) mirroring GitHub Actions — on-disk paths the engine expects; product name is <strong>GoForge</strong> (the Forge).
        On the Forge: enable Actions, register <code>act_runner</code>, then use the operator runbook
        <a href="https://alfredlinux.com/forge/commander/alfredlinux.com/raw/branch/main/docs/GOFORGE-INFRASTRUCTURE-UPGRADE.txt" target="_blank" rel="noopener" style="color:var(--accent-light);">GoForge infrastructure upgrade</a>.
        Public kernel / supply-chain page: <a href="/security-kernel" style="color:var(--accent-light);">/security-kernel</a>.
    </p>
</div>

<!-- COMPARISON -->
<div class="section" style="background: var(--surface); padding: 4rem 2rem; max-width: 100%;">
    <div style="max-width: 1100px; margin: 0 auto;">
    <span class="section-label">Why Alfred as a Foundation</span>
    <h2>Compare starting points</h2>

    <table class="compare-table">
        <thead>
            <tr>
                <th>Feature</th>
                <th>kernel.org</th>
                <th>Debian</th>
                <th>Ubuntu</th>
                <th style="color:var(--accent-light);">Alfred Linux</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Bootable ISO in one command</td>
                <td class="cross">✗</td>
                <td class="cross">✗</td>
                <td class="cross">✗</td>
                <td class="check">✓</td>
            </tr>
            <tr>
                <td>Kernel 7.0.12</td>
                <td class="check">✓</td>
                <td class="cross">✗ (6.1)</td>
                <td class="cross">✗ (6.8)</td>
                <td class="check">✓</td>
            </tr>
            <tr>
                <td>AI agent built in</td>
                <td class="cross">✗</td>
                <td class="cross">✗</td>
                <td class="cross">✗</td>
                <td class="check">✓</td>
            </tr>
            <tr>
                <td>Voice engine (offline TTS)</td>
                <td class="cross">✗</td>
                <td class="cross">✗</td>
                <td class="cross">✗</td>
                <td class="check">✓</td>
            </tr>
            <tr>
                <td>500+ MCP tools</td>
                <td class="cross">✗</td>
                <td class="cross">✗</td>
                <td class="cross">✗</td>
                <td class="check">✓</td>
            </tr>
            <tr>
                <td>Browser IDE included</td>
                <td class="cross">✗</td>
                <td class="cross">✗</td>
                <td class="cross">✗</td>
                <td class="check">✓</td>
            </tr>
            <tr>
                <td>BIOS + UEFI hybrid boot</td>
                <td class="cross">✗</td>
                <td class="check">✓</td>
                <td class="check">✓</td>
                <td class="check">✓</td>
            </tr>
            <tr>
                <td>Modular hook-based build</td>
                <td class="cross">✗</td>
                <td class="cross">✗</td>
                <td class="cross">✗</td>
                <td class="check">✓</td>
            </tr>
            <tr>
                <td>No telemetry / no Snap</td>
                <td class="check">✓</td>
                <td class="check">✓</td>
                <td class="cross">✗</td>
                <td class="check">✓</td>
            </tr>
            <tr>
                <td>Mobile installer (Android)</td>
                <td class="cross">✗</td>
                <td class="cross">✗</td>
                <td class="cross">✗</td>
                <td class="check">✓</td>
            </tr>
            <tr>
                <td>Time to first custom ISO</td>
                <td>Months</td>
                <td>Weeks</td>
                <td>Days</td>
                <td style="color:var(--green);font-weight:700;">Minutes</td>
            </tr>
        </tbody>
    </table>
    </div>
</div>

<!-- CTA -->
<div class="section" style="text-align: center; padding: 5rem 2rem;">
    <h2 style="font-size: clamp(1.5rem, 4vw, 2.5rem); margin-bottom: 1rem;">The question isn't <em style="color:var(--accent-light);">"why Alfred?"</em></h2>
    <p style="font-size: 1.2rem; color: var(--text-muted); margin-bottom: 2rem;">The question is <strong>"why would you start anywhere else?"</strong></p>
    <div class="cta-group">
        <a href="/forge/" class="btn btn-primary" target="_blank" rel="noopener">★ Fork on GoForge</a>
        <a href="/#download" class="btn btn-outline">Download v7.77 GA ISO</a>
        <a href="/docs" class="btn btn-outline">Read the Docs</a>
    </div>
</div>

<!-- FOOTER -->
<footer>
    <p style="font-style:italic;color:#94a3b8;font-size:.85rem;">&ldquo;The grass withereth, the flower fadeth: but the word of our God shall stand for ever.&rdquo; &mdash; <a href="https://gositeme.com/bible/read/isaiah/40" style="color:#facc15;text-decoration:none;">Isaiah 40:8</a> (AKJV)</p>
    <p>&copy; <?= $year ?> <a href="https://gositeme.com" style="color:var(--accent-light);">GoSiteMe Inc.</a> &mdash; Alfred Linux &middot; Open Source (AGPL-3.0)</p>
</footer>

<?php include __DIR__ . "/includes/shabbat-banner.php"; ?>
</body>
</html>

