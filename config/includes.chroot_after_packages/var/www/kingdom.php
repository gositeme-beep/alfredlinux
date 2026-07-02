<?php
/**
 * Alfred Linux — Kingdom Architecture Deep Dive
 */
$currentPage = 'kingdom';
require_once __DIR__ . '/includes/ga-release-state.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Kingdom Architecture | GoSiteMe</title>
    <meta name="description" content="Explore the profound engineering behind Alfred OS: 150+ live hooks, 777 security hardenings, IPFS mesh networking, and Prophetic Vision GPU generation.">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="stylesheet" href="/assets/fonts/inter/inter.css">
    <link rel="stylesheet" href="/assets/css/nav.css">
    <style>
        :root {
            --bg: #06060b;
            --surface: rgba(255,255,255,0.03);
            --surface-hover: rgba(255,255,255,0.06);
            --border: rgba(255,255,255,0.06);
            --border-hover: rgba(250, 204, 21, 0.3);
            --text: #e0e0e0;
            --text-muted: #9ca3af;
            --gold: #facc15;
            --gold-light: #fde68a;
            --gold-dark: #d97706;
            --accent: #6366f1;
            --accent-light: #a5b4fc;
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

        /* ── HERO ── */
        .hero {
            padding: 8rem 2rem 4rem; text-align: center; position: relative;
            background: radial-gradient(ellipse at 50% 15%, rgba(250,204,21,0.12) 0%, transparent 50%);
        }
        .hero h1 {
            font-size: clamp(2.5rem, 5vw, 4.5rem); font-weight: 900; margin-bottom: 1.5rem;
            background: linear-gradient(135deg, #fff, var(--gold-light), var(--gold));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .hero p {
            font-size: 1.2rem; color: var(--text-muted); max-width: 900px; margin: 0 auto 2rem; line-height: 1.8;
        }
        .hero-badge {
            display: inline-flex; align-items: center; gap: 0.6rem;
            padding: 0.5rem 1.5rem; border-radius: 999px;
            background: rgba(250,204,21,0.1); border: 1px solid rgba(250,204,21,0.3);
            font-size: 0.85rem; font-weight: 700; color: var(--gold-light);
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
        .card:hover { transform: translateY(-5px); border-color: rgba(250,204,21,0.3); background: var(--surface-hover); box-shadow: 0 15px 30px rgba(0,0,0,0.4); }
        .card-icon { font-size: 2.5rem; margin-bottom: 1.5rem; display: inline-block; padding: 1rem; background: rgba(250,204,21,0.1); border-radius: 16px; border: 1px solid rgba(250,204,21,0.2); }
        .card h3 { font-size: 1.35rem; color: #fff; margin-bottom: 1rem; font-weight: 800; }
        .card p { color: var(--text-muted); font-size: 1rem; line-height: 1.7; }

        .feature-row { display: flex; flex-wrap: wrap; gap: 3rem; align-items: center; margin-bottom: 5rem; padding: 3rem; background: var(--surface); border: 1px solid var(--border); border-radius: 24px; }
        .feature-row:nth-child(even) { flex-direction: row-reverse; }
        .feature-content { flex: 1; min-width: 300px; }
        .feature-content h3 { font-size: 2rem; color: #fff; margin-bottom: 1rem; font-weight: 800; }
        .feature-content h4 { font-size: 1.2rem; color: var(--gold-light); margin-bottom: 1.5rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }
        .feature-content p { color: var(--text-muted); font-size: 1.1rem; line-height: 1.8; margin-bottom: 1.5rem; }
        .feature-badge { display: inline-block; padding: 0.4rem 1.2rem; background: rgba(99, 102, 241, 0.15); border: 1px solid rgba(99, 102, 241, 0.3); color: var(--accent-light); border-radius: 999px; font-weight: 700; font-size: 0.85rem; }

        .quote-box {
            margin: 4rem auto; max-width: 900px; padding: 3rem; text-align: center;
            background: linear-gradient(135deg, rgba(250,204,21,0.05), rgba(99,102,241,0.05));
            border: 1px solid rgba(250,204,21,0.2); border-radius: 20px; position: relative;
        }
        .quote-box p { font-size: 1.4rem; font-style: italic; color: #fff; line-height: 1.7; }
        .quote-box .author { margin-top: 1.5rem; color: var(--gold-dark); font-weight: 700; font-size: 1.1rem; }
    </style>
</head>
<body>
<?php @include __DIR__ . "/includes/seal-banner.php"; ?>
<?php include __DIR__ . '/includes/site-header.inc.php'; ?>
<main id="main">
<section class="hero">
    <div class="hero-badge">👑 The Spiritual Engine</div>
    <h1>The Kingdom Architecture</h1>
    <p>A sovereign digital ecosystem built on 150+ custom Debian Live hooks. From absolute cryptographic isolation to offline theological intelligence, Alfred OS isn't just an operating system—it's a spiritual ecosystem.</p>
</section>

<section class="section">
    <div class="section-header">
        <h2>The 150+ Hooks Foundation</h2>
        <p>The core of Alfred OS is entirely declarative and reproducible. It is built via 150+ live-build hooks that weave together the Twelve Tribes framework, sovereign security, and decentralized protocols directly into the ISO.</p>
    </div>
    <div class="grid">
        <div class="card">
            <div class="card-icon" style="color:#60a5fa;">🧠</div>
            <h3>The Offline AI Tier</h3>
            <p>Built-in LLMs (Llama.cpp, BitNet) and Voice Agents running 100% offline. We guarantee that your sovereign intelligence cannot be censored, throttled, or shut down by any corporation.</p>
        </div>
        <div class="card">
            <div class="card-icon" style="color:#ef4444;">🛡️</div>
            <h3>Sovereign Security</h3>
            <p>The Kingdom Covenant Shield (0177) enforces absolute 1777 permissions and prevents systematic deadlocks. Calamares LUKS2 encryption protects the data at rest.</p>
        </div>
        <div class="card">
            <div class="card-icon" style="color:#10b981;">🏛️</div>
            <h3>12 Tribes Framework</h3>
            <p>A deeply mapped hierarchical metadata structure organizing files, workloads, and encrypted communications mathematically across the peer-to-peer mesh network.</p>
        </div>
    </div>
</section>

<section class="section section-alt">
    <div class="section-header">
        <h2>Bleeding-Edge Expansions</h2>
        <p>The future of Alfred OS brings features that transcend traditional computing. These are the active and upcoming architectural proposals pushing the OS to the next level.</p>
    </div>
    
    <div class="feature-row">
        <div class="feature-content">
            <h3 style="color:var(--gold-light);">Sanctuary Hypervisor</h3>
            <h4>Cryptographic Isolation Layer</h4>
            <p>A Type-1 Bare-Metal KVM/QEMU isolation layer. In the Sanctuary, apps don't just "run"; they exist in cryptographically isolated domains. If a workload gets compromised, it happens inside a sterile void. A breach in one zone cannot contaminate the sanctuary.</p>
            <p><strong>The Implication:</strong> High-risk nodes (financial Ledgers, LAvocat.ca justice portals, GSM token wallets) run in pure isolation, guaranteeing the integrity of your personal kingdom.</p>
            <div class="feature-badge">Hook: 0155-alfred-sanctuary</div>
        </div>
    </div>

    <div class="feature-row">
        <div class="feature-content">
            <h3 style="color:var(--accent-light);">Prophetic Vision GPU Pipeline</h3>
            <h4>Local RAG + ComfyUI/Flux</h4>
            <p>Current AI hooks focus on language. The Prophetic Vision expansion pushes this into full multimedia generation by integrating Stable Diffusion XL or Flux natively into the OS.</p>
            <p><strong>The Implication:</strong> By wiring the GPU into a Retrieval-Augmented Generation (RAG) system connected to your offline AKJV Bible, you can highlight a passage (e.g., Ezekiel's wheel), and your local GPU will instantly generate high-fidelity, biblically-accurate 8K visual concept art—entirely offline, without filters or subscriptions.</p>
            <div class="feature-badge">Hook: 0259-alfred-vision</div>
        </div>
    </div>

    <div class="feature-row">
        <div class="feature-content">
            <h3 style="color:#34d399;">The Ark (IPFS Mesh)</h3>
            <h4>Uncensorable Eternal Storage</h4>
            <p>Data is no longer stored on fragile, centralized hard drives. The Ark utilizes Kubo IPFS and the Eternal Storage layer to distribute your files across the global Alfred peer-to-peer mesh network.</p>
            <p><strong>The Implication:</strong> If the global internet fractures, your data—and the Kingdom's records—survive. The network dynamically heals itself, mirroring critical state across the world.</p>
            <div class="feature-badge">Hook: 0850-alfred-ark</div>
        </div>
    </div>

    <div class="feature-row">
        <div class="feature-content">
            <h3 style="color:#f472b6;">Global Settlement Matrix (GSM/QGSM)</h3>
            <h4>Post-Financial-System Protocol</h4>
            <p>Integrated natively into the OS and connected to <em>meta-dome.com</em>. When traditional financial systems falter, the GSM/QGSM protocol ensures all users remain a party to the matrix, whether contributing or receiving welfare.</p>
            <p><strong>The Implication:</strong> The OS acts as a decentralized reserve. Tied to the Kingdom Covenant and the Bi-Family authorized Jesus version, the GSM provides an unbreakable ledger of economic sovereignty for the people.</p>
        </div>
    </div>

    <div class="feature-row">
        <div class="feature-content">
            <h3 style="color:#22d3ee;">LAvocat.ca Justice Portal</h3>
            <h4>Sovereign Legal Intelligence</h4>
            <p>The OS directly integrates with the decentralized justice system at <em>LAvocat.ca</em>. Operating as a distributed legal engine on the server network, it empowers individuals to navigate complex jurisdictional environments autonomously.</p>
            <p><strong>The Implication:</strong> In Jeremiah 1:14, it is written: <em>"Out of the north an evil shall break forth upon all the inhabitants of the land."</em> As extreme jurisdictional and authoritarian overreach often tests its ground in the North (Canada), LAvocat.ca acts as the prophetic shield against this calamity. You hold an AI-powered legal defender and auditor locally on your machine, fully integrated into the greater Kingdom ecosystem to defend your sovereign rights.</p>
        </div>
    </div>

</section>

<div class="quote-box">
    <p>"You aren't just building a Linux distro; you've built an entire spiritual ecosystem."</p>
    <div class="author">— The Architects</div>
</div>
</main>
</body>
</html>
