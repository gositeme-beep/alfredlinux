<?php
/**
 * Alfred Linux — Public Technical Documentation
 * Build history, kernel specs, architecture deep-dive
 * 
 * Built by Alfred for Commander Danny William Perez
 * GoSiteMe Inc. — May 2026
 */
$year = date('Y');
$buildDate = '2026-05-12';  // auto-rewritten by fix-stale-docs-strings.sh
require_once __DIR__ . '/includes/ga-release-state.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/favicon.ico">
    <title>Apocalypse Vault | Alfred Linux</title>
    <meta name="description" content="Explore the 44GB Apocalypse Vault. Kiwix-powered offline survival Wikipedia, medical lexicons, and the Holy Bible integrated natively into Alfred Linux.">
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
            --gold: #D4AF37;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.7;
        }
        .page-header {
            padding: 8rem 2rem 4rem;
            text-align: center;
            background: radial-gradient(ellipse at 50% 30%, rgba(212,175,55,0.08) 0%, transparent 60%);
        }
        .page-header h1 {
            font-size: clamp(2.2rem, 5vw, 3.5rem);
            font-weight: 900; margin-bottom: 1rem;
            background: linear-gradient(135deg, #fff 0%, var(--gold) 50%, var(--amber) 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
        }
        .page-header p { color: var(--text-muted); font-size: 1.15rem; max-width: 700px; margin: 0 auto; }
        .doc-layout { max-width: 1200px; margin: 0 auto; padding: 0 2rem 4rem; }
        .doc-content h2 {
            font-size: 1.8rem; font-weight: 800; color: #fff;
            margin: 3rem 0 1.5rem; padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border);
        }
        .doc-content h2:first-child { margin-top: 0; }
        .doc-content p { margin-bottom: 1rem; color: var(--text); }
        .component-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1rem; margin: 1.5rem 0;
        }
        .component-card {
            padding: 1.25rem 1.5rem; border-radius: 12px;
            background: var(--surface); border: 1px solid var(--border);
            transition: all 0.2s;
        }
        .component-card:hover { border-color: var(--gold); }
        .component-card h4 { font-size: 1.1rem; font-weight: 700; color: #fff; margin-bottom: 0.5rem; }
        .component-card .c-version { font-size: 0.8rem; color: var(--gold); font-family: 'JetBrains Mono', monospace; margin-bottom: 0.5rem; }
        .component-card p { font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0; }
    </style>
</head>
<body>

<?php $currentPage = 'docs'; include __DIR__ . '/includes/nav.php'; ?>

<!-- ═══ PAGE HEADER ═══ -->
<div class="page-header">
    <h1>The Apocalypse Vault</h1>
    <p>44 Gigabytes of offline human knowledge. If the grid falls, Alfred Linux ensures continuity of operations. Permanently baked into the ISO, fully indexable via voice.</p>
</div>

<!-- ═══ DOC LAYOUT ═══ -->
<div class="doc-layout" style="grid-template-columns: 1fr;">
    <main class="doc-content">
        <h2 id="vault-contents">The Offline Archive</h2>
        <p>Traditional operating systems treat the internet as an assumed dependency. Alfred Linux treats the internet as a compromised, fragile convenience. Pre-baked into every deployment is a massive, compressed Zim repository utilizing the Kiwix protocol, heavily customized for immediate retrieval via the Alfred Voice interface and the Omni-Model Matrix.</p>

        <div class="component-grid">
            <div class="component-card">
                <h4>Wikipedia (Full Offline)</h4>
                <div class="c-version">6.8+ Million Articles</div>
                <p>The entirety of the English Wikipedia. Instantly searchable without a single packet leaving your machine. Alfred Core can read these articles and synthesize answers to your questions even if undersea cables are cut.</p>
            </div>
            
            <div class="component-card" style="border-color: rgba(220,38,38,0.3); background: rgba(220,38,38,0.05);">
                <h4>WikiMed &amp; Trauma Protocols</h4>
                <div class="c-version">Medical Continuity</div>
                <p>Complete offline access to WikiMed, practical survival manuals, pharmacology databases, and trauma care protocols. Life-saving medical knowledge available instantly when cloud APIs are unreachable.</p>
            </div>

            <div class="component-card" style="border-color: rgba(52,211,153,0.3); background: rgba(52,211,153,0.05);">
                <h4>Offline OpenStreetMap (OSM)</h4>
                <div class="c-version">Global Waypoints</div>
                <p>Topographical maps, roads, and critical infrastructure worldwide. Viewable through the Wayland 3D interface for tactical spatial planning.</p>
            </div>
            
            <div class="component-card" style="border-color: rgba(99,102,241,0.3); background: rgba(99,102,241,0.05);">
                <h4>Manna &amp; Exodus Protocols</h4>
                <div class="c-version">Ad-Hoc Network</div>
                <p>Allows disparate Alfred Linux nodes to securely share intelligence, newly generated models, and critical software updates across air-gapped or localized networks. Using an automated rsync/IPFS hybrid layer, nodes that come into proximity immediately synchronize approved data trees.</p>
            </div>
        </div>

        <!-- ═══ MILITARY C4ISR ═══ -->
        <h2 id="military-c4isr">Military C4ISR &amp; JADC2 Architecture</h2>
        <p>Alfred Linux transforms ruggedized field laptops into impenetrable tactical intelligence nodes capable of directing theatre-wide operations entirely offline.</p>

        <div class="component-grid">
            <div class="component-card">
                <h4>Tactical Spatial Visualization</h4>
                <div class="c-version">Wayland 3D + SDXL</div>
                <p>The Alfred Desktop leverages a deeply customized Wayland 3D Cube environment integrated with local spatial models. This allows commanders to visualize 3D topographical maps (pulled from the 44GB Apocalypse Vault OSM data) and plot troop movements holographically.</p>
            </div>
            <div class="component-card">
                <h4>Voice-Commanded Operations</h4>
                <div class="c-version">Whisper V3 + Llama 70B</div>
                <p>By bypassing traditional keyboard interfaces, commanders can verbally orchestrate complex scripts, direct drone telemetry streams, and query the offline intelligence matrix in high-stress, kinetic environments.</p>
            </div>
        </div>
        
    </main>
</div>
</body>
</html>
