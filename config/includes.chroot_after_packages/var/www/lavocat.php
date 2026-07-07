<?php
/**
 * Alfred Linux — LAvocat Sovereign Legal Intelligence
 */
$currentPage = 'lavocat';
require_once __DIR__ . '/includes/ga-release-state.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LAvocat Sovereign Legal Intelligence | Alfred Linux</title>
    <meta name="description" content="LAvocat.ca decentralized justice integration. Operating as a distributed legal engine on the server network to shield your sovereign rights.">
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
            --accent: #22c55e;
            --accent-light: #4ade80;
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
            background: radial-gradient(ellipse at 50% 15%, rgba(34,197,94,0.1) 0%, transparent 50%);
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
            background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.3);
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
        .split-image { background: rgba(34,197,94,0.05); border: 1px solid rgba(34,197,94,0.2); border-radius: 24px; padding: 4rem; text-align: center; box-shadow: 0 30px 60px rgba(34,197,94,0.1); position: relative; overflow: hidden; }
        .split-image i { font-size: 8rem; color: rgba(34,197,94,0.8); text-shadow: 0 0 40px rgba(34,197,94,0.5); }
        .split-image .badge { position: absolute; top: 1.5rem; right: 1.5rem; background: rgba(0,0,0,0.5); padding: 0.4rem 1rem; border-radius: 999px; border: 1px solid rgba(34,197,94,0.3); font-size: 0.8rem; color: #fff; font-family: monospace; }
        
        .verse-quote {
            font-style: italic; color: #fff; text-align: center;
            padding: 3rem; margin: 4rem auto; max-width: 800px;
            font-size: 1.3rem; line-height: 1.7;
            background: linear-gradient(135deg, rgba(34,197,94,0.1), rgba(34,197,94,0.02)); border-radius: 24px; border: 1px solid rgba(34,197,94,0.2); box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        .verse-ref { display: block; margin-top: 1.5rem; font-style: normal; font-size: 1rem; color: var(--accent-light); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }

        footer { padding: 4rem 2rem; background: var(--bg); text-align: center; border-top: 1px solid var(--border); margin-top: 4rem; color: var(--text-muted); }
    </style>
</head>
<body>
<?php @include __DIR__ . "/includes/seal-banner.php"; ?>
<?php include __DIR__ . '/includes/nav.php'; ?>

<section class="hero">
    <div class="hero-badge">⚖️ Decentralized Justice Integration</div>
    <h1>Sovereign Legal Intelligence</h1>
    <p>The OS directly integrates with the decentralized justice system at LAvocat.ca. Operating as a distributed legal engine on the server network, it empowers individuals to navigate complex jurisdictional environments autonomously.</p>
</section>

<section class="section section-alt">
    <div class="split-row">
        <div class="split-content">
            <h3>The Calamity From The North</h3>
            <h4>Prophetic Shielding</h4>
            <p><strong>"Out of the north an evil shall break forth upon all the inhabitants of the land."</strong> The north with its authority requires a shield of truth and jurisdiction. LAvocat is designed as a prophetic and biblical defense mechanism.</p>
            <p><strong>The Implication:</strong> You hold an AI-powered legal defender and auditor locally on your machine, fully integrated into the greater Kingdom ecosystem to shield your sovereign rights.</p>
        </div>
        <div class="split-image">
            <div class="badge">LAvocat_Defender</div>
            <div style="font-size:8rem; display:flex; justify-content:center; align-items:center; height:100%; color: rgba(34,197,94,0.8); text-shadow: 0 0 50px rgba(34,197,94,0.5);">🛡️</div>
        </div>
    </div>

    <div class="verse-quote">
        "Then the Lord said unto me, Out of the north an evil shall break forth upon all the inhabitants of the land."
        <span class="verse-ref">— Jeremiah 1:14</span>
    </div>

    <div class="split-row reverse">
        <div class="split-content">
            <h3>Local Auditor & Defender</h3>
            <h4>Autonomy in Jurisdiction</h4>
            <p>The intelligence stack evaluates contracts, legal notices, and jurisdictional claims utilizing frontier models (like `alfred-opus`). The AI acts as your personal counsel, completely isolated from corporate surveillance or external influence.</p>
            <p>It generates responses, defensive strategies, and sovereignty declarations using the immutable principles of the Kingdom of God.</p>
        </div>
        <div class="split-image">
            <div class="badge">AI_Auditor_Local</div>
            <div style="font-size:8rem; display:flex; justify-content:center; align-items:center; height:100%; color: rgba(250,204,21,0.8); text-shadow: 0 0 50px rgba(250,204,21,0.5);">⚖️</div>
        </div>
    </div>
</section>

<footer>
    <p>&copy; <?php echo date('Y'); ?> GoSiteMe Inc. / Alfred OS. All rights reserved.</p>
</footer>
</body>
</html>
