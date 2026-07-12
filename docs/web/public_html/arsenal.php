<?php
/**
 * Alfred Linux — The Arsenal (Software Center & Gaming)
 */
$currentPage = 'arsenal';
require_once __DIR__ . '/includes/ga-release-state.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Arsenal: Software & Gaming | Alfred Linux</title>
    <meta name="description" content="Discover the built-in Alfred Linux software ecosystem. KDE Discover, Flatpak, Vulkan, and Proton orchestrate a massive library of decentralized apps and high-performance gaming.">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="stylesheet" href="/assets/fonts/inter/inter.css">
    <link rel="stylesheet" href="/assets/css/nav.css">
    <style>
        :root {
            --bg: #06060b;
            --surface: rgba(255,255,255,0.03);
            --surface-hover: rgba(255,255,255,0.06);
            --border: rgba(255,255,255,0.06);
            --text: #e0e0e0;
            --text-muted: #9ca3af;
            --accent: #ef4444;
            --accent-light: #f87171;
            --gold: #facc15;
            --blue: #3b82f6;
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
            line-height: 1.6;
        }
        nav { position: relative; }

        .hero {
            padding: 8rem 2rem 4rem; text-align: center; position: relative;
            background: radial-gradient(ellipse at 50% 15%, rgba(239,68,68,0.1) 0%, transparent 50%);
        }
        .hero h1 {
            font-size: clamp(2.5rem, 5vw, 4.5rem); font-weight: 900; margin-bottom: 1.5rem;
            background: linear-gradient(135deg, #fff, var(--accent-light), var(--accent));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .hero p {
            font-size: 1.2rem; color: var(--text-muted); max-width: 900px; margin: 0 auto 2rem; line-height: 1.8;
        }
        .hero-badge {
            display: inline-flex; align-items: center; gap: 0.6rem;
            padding: 0.5rem 1.5rem; border-radius: 999px;
            background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3);
            font-size: 0.85rem; font-weight: 700; color: var(--accent-light);
            text-transform: uppercase; margin-bottom: 2rem;
        }

        .section { padding: 6rem 2rem; max-width: 1200px; margin: 0 auto; }
        .section-alt { background: rgba(255,255,255,0.01); }
        .section-header { text-align: center; margin-bottom: 4rem; }
        .section-header h2 { font-size: clamp(2rem, 4vw, 3rem); font-weight: 900; color: #fff; margin-bottom: 1rem; }
        .section-header p { font-size: 1.15rem; color: var(--text-muted); max-width: 800px; margin: 0 auto; }
        
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2rem; }
        .card {
            padding: 2.5rem; border-radius: 20px; background: var(--surface);
            border: 1px solid var(--border); transition: all 0.3s;
        }
        .card:hover { transform: translateY(-5px); border-color: rgba(239,68,68,0.3); background: var(--surface-hover); box-shadow: 0 15px 30px rgba(0,0,0,0.4); }
        .card-icon { font-size: 2.5rem; margin-bottom: 1.5rem; display: inline-block; padding: 1rem; border-radius: 16px; }
        .card h3 { font-size: 1.35rem; color: #fff; margin-bottom: 1rem; font-weight: 800; }
        .card p { color: var(--text-muted); font-size: 1rem; line-height: 1.7; }

        .split-row { display: grid; grid-template-columns: 1.2fr 1fr; gap: 4rem; align-items: center; margin-bottom: 5rem; }
        .split-row.reverse { grid-template-columns: 1fr 1.2fr; }
        .split-row.reverse .split-content { grid-column: 2; grid-row: 1; }
        .split-row.reverse .split-image { grid-column: 1; grid-row: 1; }
        @media (max-width: 900px) { .split-row, .split-row.reverse { grid-template-columns: 1fr; } .split-row.reverse .split-content, .split-row.reverse .split-image { grid-column: auto; grid-row: auto; } }
        
        .split-content h3 { font-size: 2.2rem; color: #fff; margin-bottom: 1rem; font-weight: 800; }
        .split-content h4 { font-size: 1.1rem; color: var(--accent-light); margin-bottom: 1.5rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; }
        .split-content p { color: var(--text-muted); font-size: 1.1rem; line-height: 1.8; margin-bottom: 1.5rem; }
        .split-image { background: rgba(239,68,68,0.05); border: 1px solid rgba(239,68,68,0.2); border-radius: 24px; padding: 4rem; text-align: center; box-shadow: 0 30px 60px rgba(239,68,68,0.1); position: relative; overflow: hidden; }
        .split-image i { font-size: 8rem; color: rgba(239,68,68,0.8); text-shadow: 0 0 40px rgba(239,68,68,0.5); }
        .split-image .badge { position: absolute; top: 1.5rem; right: 1.5rem; background: rgba(0,0,0,0.5); padding: 0.4rem 1rem; border-radius: 999px; border: 1px solid rgba(239,68,68,0.3); font-size: 0.8rem; color: #fff; font-family: monospace; }
        
        footer { padding: 4rem 2rem; background: var(--bg); text-align: center; border-top: 1px solid var(--border); margin-top: 4rem; color: var(--text-muted); }
    </style>
</head>
<body>
<?php @include __DIR__ . "/includes/seal-banner.php"; ?>
<?php include __DIR__ . '/includes/nav.php'; ?>

<section class="hero">
    <div class="hero-badge">⚔️ Software Center & Gaming Hub</div>
    <h1>The Arsenal</h1>
    <p>Alfred Linux is not just a secure vault—it is a sovereign powerhouse. Natively baked into the OS via live-build hooks are thousands of decentralized apps and a high-performance gaming ecosystem powered by Vulkan and Proton.</p>
</section>

<section class="section section-alt">
    <div class="split-row">
        <div class="split-content">
            <h3>KDE Discover & Flatpak</h3>
            <h4>The Decentralized Store</h4>
            <p>You don't need to use the terminal to install software. The <strong>KDE Discover</strong> software center is pre-installed and natively wired into the <strong>Flathub</strong> registry via OS build hooks.</p>
            <p>With a single click, you have access to thousands of sandboxed applications—from video editors to productivity suites—all cleanly separated from your core system files.</p>
        </div>
        <div class="split-image">
            <div class="badge">Discover_Store</div>
            <div style="font-size:8rem; display:flex; justify-content:center; align-items:center; height:100%; color: rgba(59,130,246,0.8); text-shadow: 0 0 50px rgba(59,130,246,0.5);">🛍️</div>
        </div>
    </div>

    <div class="split-row reverse">
        <div class="split-content">
            <h3>Elite Gaming Ecosystem</h3>
            <h4>Vulkan + Proton</h4>
            <p>Alfred Linux ships with a pre-configured gaming layer. The hooks install critical GPU drivers (CUDA/ROCm) alongside Vulkan and WINE integration.</p>
            <p>Launch <strong>Steam</strong>, <strong>Lutris</strong>, or the <strong>Heroic Games Launcher</strong> to play top-tier Windows games effortlessly with Proton compatibility, pushing your GPU to its absolute limits under the Wayland 3D Composer.</p>
        </div>
        <div class="split-image" style="background:rgba(250,204,21,0.05); border-color:rgba(250,204,21,0.2); box-shadow: 0 30px 60px rgba(250,204,21,0.1);">
            <div class="badge" style="border-color:rgba(250,204,21,0.3);">Gaming_Layer</div>
            <div style="font-size:8rem; display:flex; justify-content:center; align-items:center; height:100%; color: rgba(250,204,21,0.8); text-shadow: 0 0 50px rgba(250,204,21,0.5);">🎮</div>
        </div>
    </div>
</section>

<section class="section">
    <div class="section-header">
        <h2>The Power of the Hooks</h2>
        <p>How Alfred Linux configures the ecosystem before you even boot the ISO.</p>
    </div>
    <div class="grid">
        <div class="card">
            <div class="card-icon" style="color:#10b981;background:rgba(16,185,129,0.1);border-color:rgba(16,185,129,0.2);">📦</div>
            <h3>Sandboxed execution</h3>
            <p>Every Flatpak app runs in isolated containers. A malicious game or app cannot touch your `.ssh` keys or access your local GGUF models without explicit permission.</p>
        </div>
        <div class="card">
            <div class="card-icon" style="color:#ef4444;background:rgba(239,68,68,0.1);border-color:rgba(239,68,68,0.2);">⚡</div>
            <h3>Direct GPU Access</h3>
            <p>Unlike virtual machines, Steam and Lutris have raw, bare-metal access to your NVIDIA or AMD GPUs for tear-free gaming on the Wayland 3D Cube.</p>
        </div>
        <div class="card">
            <div class="card-icon" style="color:#8b5cf6;background:rgba(139,92,246,0.1);border-color:rgba(139,92,246,0.2);">🔄</div>
            <h3>Delta Updates</h3>
            <p>The Discover software center utilizes delta updates. When a game or app is patched, you only download the modified bytes, saving enormous amounts of bandwidth on the mesh network.</p>
        </div>
    </div>
</section>

<footer>
    <p>&copy; <?php echo date('Y'); ?> GoSiteMe Inc. / Alfred OS. All rights reserved.</p>
</footer>
</body>
</html>
