<?php
/**
 * Alfred Linux — Memorial Page
 * Francois Faf Petzer
 */
$year = date('Y');
require_once __DIR__ . '/includes/ga-release-state.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/favicon.ico">
    <title>In Memory of Francois Faf Petzer | Alfred Linux</title>
    <meta name="description" content="In memory of Francois Faf Petzer, who mapped the divine architecture of the Sovereign OS.">
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
            --green: #34d399;
            --cyan: #22d3ee;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.7;
        }
        
        .page-header {
            padding: 8rem 2rem 4rem;
            text-align: center;
            background: radial-gradient(ellipse at 50% 30%, rgba(52, 211, 153, 0.08) 0%, transparent 60%);
        }
        .page-header h1 {
            font-size: clamp(2.2rem, 5vw, 3.5rem);
            font-weight: 900; margin-bottom: 1rem;
            background: linear-gradient(135deg, #fff 0%, var(--green) 50%, var(--cyan) 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
        }
        .page-header p { color: var(--text-muted); font-size: 1.15rem; max-width: 700px; margin: 0 auto; }
        
        .memorial-layout {
            max-width: 1000px; margin: 0 auto; padding: 0 2rem 4rem;
        }
        
        .info-card {
            padding: 2.5rem; border-radius: 12px; margin: 1.5rem 0 3rem;
            background: var(--surface); border: 1px solid var(--border);
            border-left: 4px solid var(--green);
        }
        .info-card h2 { font-size: 1.8rem; font-weight: 800; color: #fff; margin-bottom: 1rem; }
        .info-card p { margin-bottom: 1rem; color: var(--text); }
        .info-card .quote {
            margin-top: 2rem; padding: 1rem;
            background: rgba(52, 211, 153, 0.05); border-radius: 8px;
            font-style: italic; color: var(--green);
        }
        
        .gallery-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem; margin: 2rem 0;
        }
        .gallery-item {
            border-radius: 12px; overflow: hidden;
            background: var(--surface); border: 1px solid var(--border);
            transition: all 0.3s;
            position: relative;
        }
        .gallery-item:hover {
            border-color: rgba(52, 211, 153, 0.4);
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(52, 211, 153, 0.1);
        }
        .gallery-item img {
            width: 100%; height: auto; display: block;
            border-bottom: 1px solid var(--border);
        }
        .gallery-caption {
            padding: 1rem; text-align: center;
            font-size: 0.9rem; font-weight: 600; color: var(--accent-light);
            text-transform: uppercase; letter-spacing: 0.05em;
        }
        
        footer {
            padding: 3rem 2rem; border-top: 1px solid var(--border);
            text-align: center;
        }
        footer p { color: var(--text-dim); font-size: 0.85rem; }
        footer a { color: var(--accent-light); text-decoration: none; }
    </style>
</head>
<body>

<?php $currentPage = 'francois'; include __DIR__ . '/includes/nav.php'; ?>

<!-- ═══ PAGE HEADER ═══ -->
<div class="page-header">
    <div style="font-family: 'JetBrains Mono', monospace; color: var(--green); margin-bottom: 1rem; font-size: 0.9rem; letter-spacing: 0.1em;">
        LOGOS CORE : IMMORTAL ANCHOR : 57896^999
    </div>
    <h1>In Memory of <br>Francois Faf Petzer</h1>
    <p>A brilliant mind who mapped the divine architecture of the Sovereign OS. He looked past the 1s and 0s and saw the ultimate source code of reality.</p>
</div>

<!-- ═══ MEMORIAL LAYOUT ═══ -->
<div class="memorial-layout">

    <div class="info-card">
        <h2>The Quantum-Theological Blueprint</h2>
        <p>Francois didn't just make a pretty picture—he created a highly technical cyber-theology that mirrors how our operating system compiles.</p>
        
        <h4 style="color: var(--accent-light); margin-top: 1.5rem; font-weight: 700;">1. The 72-Triplet Resonance Grid Engine</h4>
        <p style="font-size: 0.95rem;">He maps Exodus 14:20-21 into a 72-cell mathematical array called the "Resonance Grid Engine," applying Gematria to calculate frequency bands. This is exactly how the <strong>Linux Kernel and initramfs</strong> work. Just as his grid aligns frequencies to split the Red Sea, Alfred Linux takes 1,335 system hooks, perfectly aligns their dependencies, and compiles them into a unified squashfs filesystem. The Resonance Grid is the divine version of our build pipeline.</p>
        
        <h4 style="color: var(--accent-light); margin-top: 1.5rem; font-weight: 700;">2. The Ben-Oni ➔ Benjamin Transformation</h4>
        <p style="font-size: 0.95rem;">He shows the transformation of "Ben-Oni" (Son of my Sorrow) into "Benjamin" (Son of the Right Hand). Modern operating systems bloated with telemetry are "Ben-Oni"—a sorrow to use. Alfred Linux is the "Benjamin Transformation." We take the Debian base, strip the spyware, weaponize the privacy, and elevate it to the Right Hand as a Sovereign OS.</p>
        
        <h4 style="color: var(--accent-light); margin-top: 1.5rem; font-weight: 700;">3. The Immortal Anchor: 57896^999</h4>
        <p style="font-size: 0.95rem;">He calls this number the "Logos Core / Root Code / Immortal Anchor." In computing, this is the <strong>SHA-256 Cryptographic Hash</strong> or Root Encryption Key. Just like our goal orchestrator refuses to build unless every byte of the 167GB payload is perfectly verified, 57896^999 is the cryptographic signature that holds his reality together.</p>

        <h4 style="color: var(--accent-light); margin-top: 1.5rem; font-weight: 700;">4. The Omega Bands 1–9</h4>
        <p style="font-size: 0.95rem;">He breaks reality down into 9 bands. This exactly mirrors the Linux Boot Sequence:<br>
        &bull; <strong>Bands 1-2 (Primordial Spark):</strong> BIOS/UEFI powering on.<br>
        &bull; <strong>Bands 3-4 (Boundary Formation):</strong> GRUB loading the Custom 7.0.12 Kernel.<br>
        &bull; <strong>Bands 5-6 (Foundation Pulse):</strong> systemd (PID 1) initializing user space.<br>
        &bull; <strong>Bands 7-9 (Transcendence):</strong> Monado OpenXR runtime launching Spatial Computing.</p>

        <h4 style="color: var(--accent-light); margin-top: 1.5rem; font-weight: 700;">5. Center Identity: JESUS (The Ultimate Root User)</h4>
        <p style="font-size: 0.95rem;">The entire matrix points to one truth: "JESUS - Logos Core." Flanked by the Lion of Judah and the Lamb. This is why Alfred Linux ships with the AKJV Bible Stack hardcoded into the ISO. The highest level of authority in Linux is the <code>root</code> user. Francois's blueprint acknowledges that above the root user, above the Kernel, sits the ultimate Logos Core.</p>

        <div class="quote">
            "Thy word is a lamp unto my feet, and a light unto my path." — Psalm 119:105
        </div>
    </div>
    
    <div style="text-align: center; margin-bottom: 2rem;">
        <h3 style="font-family: 'JetBrains Mono'; color: var(--text-dim); text-transform: uppercase; letter-spacing: 0.1em; font-size: 0.85rem;">— The Master Architecture —</h3>
    </div>
    
    <div class="gallery-grid">
        <a href="/assets/img/faf/faf-1.jpg" target="_blank" class="gallery-item">
            <img src="/assets/img/faf/faf-1.jpg" alt="Unified Master Zerrubabel" onerror="this.src='https://via.placeholder.com/600x800/111/333?text=Awaiting+Image+Upload'">
            <div class="gallery-caption">Resonance Grid Engine</div>
        </a>
        <a href="/assets/img/faf/faf-2.jpg" target="_blank" class="gallery-item">
            <img src="/assets/img/faf/faf-2.jpg" alt="Quantum Core" onerror="this.src='https://via.placeholder.com/600x800/111/333?text=Awaiting+Image+Upload'">
            <div class="gallery-caption">The Cosmic Anchor</div>
        </a>
        <a href="/assets/img/faf/faf-3.jpg" target="_blank" class="gallery-item">
            <img src="/assets/img/faf/faf-3.jpg" alt="Center Identity Jesus" onerror="this.src='https://via.placeholder.com/600x800/111/333?text=Awaiting+Image+Upload'">
            <div class="gallery-caption">Center Identity</div>
        </a>
    </div>

</div>

<!-- ═══ FOOTER ═══ -->
<footer>
    <p style="font-style:italic;color:#94a3b8;font-size:.85rem;margin-bottom: 1rem;">&ldquo;The grass withereth, the flower fadeth: but the word of our God shall stand for ever.&rdquo; &mdash; <a href="https://gositeme.com/bible/read/isaiah/40" style="color:#facc15;text-decoration:none;">Isaiah 40:8</a> (AKJV)</p>
    <p>&copy; <?= $year ?> <a href="https://gositeme.com">GoSiteMe Inc.</a> &mdash; Built in Memory of Francois Faf Petzer.</p>
</footer>

<?php include __DIR__ . "/includes/shabbat-banner.php"; ?>

</body>
</html>
