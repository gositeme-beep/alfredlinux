<?php
/**
 * Alfred Linux — Prophetic Vision
 */
$currentPage = 'prophetic-vision';
require_once __DIR__ . '/includes/ga-release-state.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prophetic Vision GPU RAG | Alfred Linux</title>
    <meta name="description" content="Natively integrating ComfyUI and Flux into the desktop. Generate high-fidelity, biblically-accurate 8K visual concept art entirely offline via local GPU.">
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
            --gold: #facc15;
            --gold-light: #fde68a;
            --accent: #f59e0b;
            --accent-light: #fbbf24;
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
            background: radial-gradient(ellipse at 50% 15%, rgba(245,158,11,0.1) 0%, transparent 50%);
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
            background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.3);
            font-size: 0.85rem; font-weight: 700; color: var(--accent-light);
            text-transform: uppercase; margin-bottom: 2rem;
        }

        .section { padding: 6rem 2rem; max-width: 1200px; margin: 0 auto; }
        .section-alt { background: rgba(255,255,255,0.01); }
        .section-header { text-align: center; margin-bottom: 4rem; }
        .section-header h2 { font-size: clamp(2rem, 4vw, 3rem); font-weight: 900; color: #fff; margin-bottom: 1rem; }
        .section-header p { font-size: 1.15rem; color: var(--text-muted); max-width: 800px; margin: 0 auto; }
        
        .split-row { display: grid; grid-template-columns: 1.2fr 1fr; gap: 4rem; align-items: center; margin-bottom: 5rem; }
        .split-row.reverse { grid-template-columns: 1fr 1.2fr; }
        .split-row.reverse .split-content { grid-column: 2; grid-row: 1; }
        .split-row.reverse .split-image { grid-column: 1; grid-row: 1; }
        @media (max-width: 900px) { .split-row, .split-row.reverse { grid-template-columns: 1fr; } .split-row.reverse .split-content, .split-row.reverse .split-image { grid-column: auto; grid-row: auto; } }
        
        .split-content h3 { font-size: 2.2rem; color: #fff; margin-bottom: 1rem; font-weight: 800; }
        .split-content h4 { font-size: 1.1rem; color: var(--accent-light); margin-bottom: 1.5rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; }
        .split-content p { color: var(--text-muted); font-size: 1.1rem; line-height: 1.8; margin-bottom: 1.5rem; }
        .split-image { background: rgba(245,158,11,0.05); border: 1px solid rgba(245,158,11,0.2); border-radius: 24px; padding: 4rem; text-align: center; box-shadow: 0 30px 60px rgba(245,158,11,0.1); position: relative; overflow: hidden; }
        .split-image i { font-size: 8rem; color: rgba(245,158,11,0.8); text-shadow: 0 0 40px rgba(245,158,11,0.5); }
        .split-image .badge { position: absolute; top: 1.5rem; right: 1.5rem; background: rgba(0,0,0,0.5); padding: 0.4rem 1rem; border-radius: 999px; border: 1px solid rgba(245,158,11,0.3); font-size: 0.8rem; color: #fff; font-family: monospace; }
        
        .verse-quote {
            font-style: italic; color: #fff; text-align: center;
            padding: 3rem; margin: 4rem auto; max-width: 800px;
            font-size: 1.3rem; line-height: 1.7;
            background: linear-gradient(135deg, rgba(245,158,11,0.1), rgba(245,158,11,0.02)); border-radius: 24px; border: 1px solid rgba(245,158,11,0.2); box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        .verse-ref { display: block; margin-top: 1.5rem; font-style: normal; font-size: 1rem; color: var(--accent-light); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }

        footer { padding: 4rem 2rem; background: var(--bg); text-align: center; border-top: 1px solid var(--border); margin-top: 4rem; color: var(--text-muted); }
    </style>
</head>
<body>
<?php @include __DIR__ . "/includes/seal-banner.php"; ?>
<?php include __DIR__ . '/includes/nav.php'; ?>

<section class="hero">
    <div class="hero-badge">👁️ ComfyUI / Flux RAG Pipeline</div>
    <h1>Prophetic Vision</h1>
    <p>We push AI beyond text and language into full multimedia generation. By integrating ComfyUI and Flux natively into the desktop, your local GPU instantly generates high-fidelity, biblically-accurate 8K visual concept art entirely offline.</p>
</section>

<section class="section section-alt">
    <div class="split-row">
        <div class="split-content">
            <h3>Visualizing the Word</h3>
            <h4>Retrieval-Augmented Generation</h4>
            <p>Our specialized GPU pipeline is wired directly into a RAG system connected to your offline AKJV Bible. A pastor, student, or sovereign user can highlight a passage—such as Ezekiel's wheel or Solomon's temple.</p>
            <p><strong>The Implication:</strong> Instantly, the native OS parses the theological context and instructs the local GPU to render photorealistic or concept art that adheres strictly to the biblical descriptions. No internet connection. No censorship.</p>
        </div>
        <div class="split-image">
            <div class="badge">GPU_RAG_Active</div>
            <div style="font-size:8rem; display:flex; justify-content:center; align-items:center; height:100%; color: rgba(245,158,11,0.8); text-shadow: 0 0 50px rgba(245,158,11,0.5);">👁️</div>
        </div>
    </div>

    <div class="verse-quote">
        "Now as I beheld the living creatures, behold one wheel upon the earth by the living creatures, with his four faces... The appearance of the wheels and their work was like unto the colour of a beryl: and they four had one likeness."
        <span class="verse-ref">— Ezekiel 1:15-16</span>
    </div>

    <div class="split-row reverse">
        <div class="split-content">
            <h3>Natively Integrated</h3>
            <h4>ComfyUI & Flux</h4>
            <p>Unlike cloud-based image generators that filter or alter biblical imagery, Alfred Linux ships the models directly to your hardware. With node-based control through ComfyUI, you retain absolute authority over the rendering pipeline.</p>
        </div>
        <div class="split-image">
            <div class="badge">ComfyUI_Node_Tree</div>
            <div style="font-size:8rem; display:flex; justify-content:center; align-items:center; height:100%; color: rgba(99,102,241,0.8); text-shadow: 0 0 50px rgba(99,102,241,0.5);">🎨</div>
        </div>
    </div>
</section>

<footer>
    <p>&copy; <?php echo date('Y'); ?> GoSiteMe Inc. / Alfred OS. All rights reserved.</p>
</footer>
</body>
</html>
