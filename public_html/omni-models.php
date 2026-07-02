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
    <title>Omni-Model Matrix | Alfred Linux</title>
    <meta name="description" content="Explore the 100GB Omni-Model Intelligence Matrix. The 8 God-Tier local AI weights running natively on Alfred Linux.">
    <link rel="stylesheet" href="/assets/fonts/inter/inter.css">
    <link rel="stylesheet" href="/assets/fonts/jetbrains-mono/jetbrains-mono.css">
    <link rel="stylesheet" href="/assets/css/nav.css">
    <style>
        :root {
            --bg: #06060b;
            --surface: rgba(255,255,255,0.03);
            --surface-hover: rgba(255,255,255,0.06);
            --border: rgba(255,255,255,0.06);
            --border-hover: rgba(139, 92, 246, 0.3);
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
            --purple: #8b5cf6;
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
            background: radial-gradient(ellipse at 50% 30%, rgba(139,92,246,0.08) 0%, transparent 60%);
        }
        .page-header h1 {
            font-size: clamp(2.2rem, 5vw, 3.5rem);
            font-weight: 900; margin-bottom: 1rem;
            background: linear-gradient(135deg, #fff 0%, var(--purple) 50%, var(--cyan) 100%);
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
        .component-card:hover { border-color: var(--purple); }
        .component-card h4 { font-size: 1.1rem; font-weight: 700; color: #fff; margin-bottom: 0.5rem; }
        .component-card .c-version { font-size: 0.8rem; color: var(--purple); font-family: 'JetBrains Mono', monospace; margin-bottom: 0.5rem; }
        .component-card p { font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0; }
    </style>
</head>
<body>

<?php $currentPage = 'docs'; include __DIR__ . '/includes/nav.php'; ?>

<!-- ═══ PAGE HEADER ═══ -->
<div class="page-header">
    <h1>100GB Omni-Model Matrix</h1>
    <p>Zero telemetry. Absolute privacy. Eight God-Tier local AI weights running entirely on your own silicon.</p>
</div>

<!-- ═══ DOC LAYOUT ═══ -->
<div class="doc-layout" style="grid-template-columns: 1fr;">
    <main class="doc-content">
        <h2 id="omni-models">The 100GB Intelligence Matrix</h2>
        <p>Unlike traditional operating systems that rely on cloud APIs to process thought, Alfred Linux v7.77 ships with a massive, localized AI brain. Housed within the <code>/opt/alfred-models</code> directory, the Omni-Model Matrix operates 100% offline, guaranteeing zero telemetry and absolute operational security.</p>

        <div class="component-grid">
            <div class="component-card">
                <h4>Llama 3 Instruct (70B)</h4>
                <div class="c-version">Strategic Planning</div>
                <p>The primary reasoning engine. Handles complex logistical queries, code generation, and multi-step deduction across the offline datasets.</p>
            </div>
            
            <div class="component-card" style="border-color: rgba(6,182,212,0.3); background: rgba(6,182,212,0.05);">
                <h4>Whisper V3 Large</h4>
                <div class="c-version">Real-time Transcription</div>
                <p>Provides flawless offline voice-to-text, directly integrated into the Wayland compositor for seamless voice orchestration of the OS.</p>
            </div>

            <div class="component-card" style="border-color: rgba(139,92,246,0.3); background: rgba(139,92,246,0.05);">
                <h4>Stable Diffusion XL (Base + Refiner)</h4>
                <div class="c-version">Spatial Rendering</div>
                <p>Leveraged by the Wayland 3D Cube to generate textures and spatial topographic maps dynamically.</p>
            </div>
            
            <div class="component-card" style="border-color: rgba(245,158,11,0.3); background: rgba(245,158,11,0.05);">
                <h4>CodeLlama (34B)</h4>
                <div class="c-version">On-Device Compilation</div>
                <p>Integrated natively into the Alfred Commander IDE. Analyzes the Linux kernel source tree and dynamically patches hooks without internet connectivity.</p>
            </div>
        </div>

        <!-- ═══ DETERMINISTIC COMPILATION ═══ -->
        <h2 id="deterministic-omega">Deterministic Omega Compilation</h2>
        <p>The Omni-Model matrix is bound directly to the compiler stack. The AI doesn't just suggest code; it mathematically verifies the safety and determinism of every binary payload before executing it against the root file system.</p>
    </main>
</div>
</body>
</html>
