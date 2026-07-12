<?php
/**
 * Alfred Linux — New Jerusalem Spatial OS
 */
$currentPage = 'new-jerusalem';
require_once __DIR__ . '/includes/ga-release-state.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Jerusalem Spatial OS | Alfred Linux</title>
    <meta name="description" content="Revelation 21:16 in code. Experience the KWin Wayland compositor that turns your workspaces into an immersive 3D cube with true glassmorphism.">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="stylesheet" href="/assets/fonts/inter/inter.css">
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
            --accent: #8b5cf6;
            --accent-light: #a78bfa;
            --accent-glow: rgba(139,92,246,0.25);
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
            background: radial-gradient(ellipse at 50% 15%, rgba(139,92,246,0.12) 0%, transparent 50%);
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
            background: rgba(139,92,246,0.1); border: 1px solid rgba(139,92,246,0.3);
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
        .card:hover { transform: translateY(-5px); border-color: rgba(139,92,246,0.3); background: var(--surface-hover); box-shadow: 0 15px 30px rgba(0,0,0,0.4); }
        .card-icon { font-size: 2.5rem; margin-bottom: 1.5rem; display: inline-block; padding: 1rem; background: rgba(139,92,246,0.1); border-radius: 16px; border: 1px solid rgba(139,92,246,0.2); }
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
        .split-image { background: rgba(139,92,246,0.05); border: 1px solid rgba(139,92,246,0.2); border-radius: 24px; padding: 4rem; text-align: center; box-shadow: 0 30px 60px rgba(139,92,246,0.1); position: relative; overflow: hidden; }
        .split-image i { font-size: 8rem; color: rgba(139,92,246,0.8); text-shadow: 0 0 40px rgba(139,92,246,0.5); }
        .split-image .badge { position: absolute; top: 1.5rem; right: 1.5rem; background: rgba(0,0,0,0.5); padding: 0.4rem 1rem; border-radius: 999px; border: 1px solid rgba(139,92,246,0.3); font-size: 0.8rem; color: #fff; font-family: monospace; }
        
        .roadmap-grid { display: flex; flex-wrap: wrap; justify-content: center; gap: 2rem; }
        .roadmap-card { background: var(--surface); border: 1px solid var(--border); padding: 2.5rem; border-radius: 20px; min-width: 300px; flex: 1; text-align: left; }
        .roadmap-card h4 { font-size: 1.4rem; color: #fff; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.8rem; }
        .roadmap-card p { color: var(--text-muted); font-size: 1.05rem; line-height: 1.7; }
        
        .verse-quote {
            font-style: italic; color: #fff; text-align: center;
            padding: 3rem; margin: 4rem auto; max-width: 800px;
            font-size: 1.3rem; line-height: 1.7;
            background: linear-gradient(135deg, rgba(139,92,246,0.1), rgba(139,92,246,0.02)); border-radius: 24px; border: 1px solid rgba(139,92,246,0.2); box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        .verse-ref { display: block; margin-top: 1.5rem; font-style: normal; font-size: 1rem; color: var(--accent-light); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }

        footer { padding: 4rem 2rem; background: var(--bg); text-align: center; border-top: 1px solid var(--border); margin-top: 4rem; color: var(--text-muted); }
    </style>
</head>
<body>
<?php @include __DIR__ . "/includes/seal-banner.php"; ?>
<?php include __DIR__ . '/includes/nav.php'; ?>

<section class="hero">
    <div class="hero-badge">🧊 Wayland Compositor Expansion</div>
    <h1>New Jerusalem Spatial OS</h1>
    <p>While KDE Plasma is a massive upgrade over traditional Wayland 3D Cube, it remains a traditional 2D desktop. Alfred Linux introduces the Spatial OS—a custom Wayland/Hyprland environment where the desktop becomes a spiritual, immersive interface.</p>
</section>

<section class="section section-alt">
    <div class="split-row">
        <div class="split-content">
            <h3>The 3D Cube Foundation</h3>
            <h4>Hook: 0851-alfred-spatial</h4>
            <p>By forcefully enabling <code>cubeEnabled=true</code> and Wayland natively via the KWin compositor, your workspaces are transformed into a literal <strong>3D Spinning Cube</strong>.</p>
            <p><strong>The Implication:</strong> When you swipe between virtual desktops, the entire screen zooms out into a 3D spatial cube that you rotate manually to transition between environments. Your machine no longer feels like a flat ledger; it feels like a physical object in spatial reality.</p>
        </div>
        <div class="split-image">
            <div class="badge">cubeEnabled=true</div>
            <div style="font-size:8rem; display:flex; justify-content:center; align-items:center; height:100%; color: rgba(139,92,246,0.8); text-shadow: 0 0 50px rgba(139,92,246,0.5);">🧊</div>
        </div>
    </div>

    <div class="verse-quote">
        "And the city lieth foursquare, and the length is as large as the breadth: and he measured the city with the reed, twelve thousand furlongs. The length and the breadth and the height of it are equal."
        <span class="verse-ref">— Revelation 21:16</span>
    </div>

    <div class="split-row reverse">
        <div class="split-content">
            <h3>Mapping the Twelve Tribes</h3>
            <h4>Hierarchical Workspaces</h4>
            <p>The 3D spatial environments are explicitly mapped to the Twelve Tribes. Each face of the cube and its sub-workspaces correspond to a specific spiritual discipline or mission objective.</p>
            <p><strong>The Implication:</strong> Swiping between virtual desktops feels like moving between different sanctums. You shift seamlessly from the Judah environment (Leadership & Code Editing) to the Levi environment (Sanctuary Server Management & Auditing).</p>
        </div>
        <div class="split-image">
            <div class="badge">12_Tribes_Mapping</div>
            <div style="font-size:8rem; display:flex; justify-content:center; align-items:center; height:100%; color: rgba(16,185,129,0.8); text-shadow: 0 0 50px rgba(16,185,129,0.5);">✡️</div>
        </div>
    </div>
</section>

<section class="section">
    <div class="section-header">
        <h2>Why Wayland Changes Everything</h2>
        <p>For the last 30 years, Linux ran on "Wayland", a 2D display server that was flat and rigid. By upgrading the foundation to Wayland, Alfred Linux now renders the desktop using the GPU directly, unlocking massive potential.</p>
    </div>
    <div class="grid">
        <div class="card">
            <div class="card-icon" style="color:#3498db;background:rgba(52,152,219,0.1);border-color:rgba(52,152,219,0.2);">👆</div>
            <h3>1:1 Touchpad Gestures</h3>
            <p>Swiping 4 fingers doesn't just trigger a pre-baked animation; the screen moves fluidly exactly as your fingers move, peeling back the layers of the OS like you're pulling a curtain.</p>
        </div>
        <div class="card">
            <div class="card-icon" style="color:#10b981;background:rgba(16,185,129,0.1);border-color:rgba(16,185,129,0.2);">🪟</div>
            <h3>True Glassmorphism</h3>
            <p>Windows are no longer solid, opaque boxes. They are frosted glass elements that blur the dynamic environment behind them in real-time, calculating depth through the GPU.</p>
        </div>
        <div class="card">
            <div class="card-icon" style="color:#f43f5e;background:rgba(244,63,94,0.1);border-color:rgba(244,63,94,0.2);">⚡</div>
            <h3>Tear-Free Rendering</h3>
            <p>Every single frame is perfectly synchronized with your monitor's refresh rate. No screen tearing, no stuttering—just pure, uninterrupted visual fluidity.</p>
        </div>
    </div>
</section>

<section class="section section-alt">
    <div class="section-header">
        <h2>The Hyprland Expansions</h2>
        <p>The 3D Cube is only the beginning. Our ongoing integration with Hyprland enables custom OpenGL shaders directly in the compositor layer.</p>
    </div>
    <div class="roadmap-grid">
        <div class="roadmap-card" style="border-top:3px solid #ef4444;">
            <h4><span style="font-size:2rem;color:#ef4444;text-shadow:0 0 20px rgba(239,68,68,0.5);">🔥</span> Burning Bush Shaders</h4>
            <p>Advanced particle shaders attached directly to the Omahon AI terminal. As the LLM infers and generates tokens, the terminal window emits subtle, glowing embers that dynamically respond to system load.</p>
        </div>
        <div class="roadmap-card" style="border-top:3px solid #3b82f6;">
            <h4><span style="font-size:2rem;color:#3b82f6;text-shadow:0 0 20px rgba(59,130,246,0.5);">💧</span> Living Water Dock</h4>
            <p>Fluid-dynamic shaders integrated into the desktop dock. As your mouse glides over application icons, the dock physically ripples like water, creating a living, breathing interface.</p>
        </div>
    </div>
</section>

<footer>
    <p>&copy; <?php echo date('Y'); ?> GoSiteMe Inc. / Alfred OS. All rights reserved.</p>
</footer>
</body>
</html>

