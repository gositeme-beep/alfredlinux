<?php
/**
 * Alfred Linux — Community
 * Bug reports, feature requests, contributing, developer chat
 *
 * GoSiteMe Inc. — April 2026
 */
$year = date('Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community — Alfred Linux</title>
    <meta name="description" content="Join the Alfred Linux community. Report bugs, request features, contribute code, and connect with developers building the AI-native operating system.">
    <meta property="og:title" content="Community — Alfred Linux">
    <meta property="og:description" content="Report bugs, request features, contribute code. Alfred Linux community hub.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://alfredlinux.com/community">
    <meta property="og:image" content="https://alfredlinux.com/og-image.png">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="canonical" href="https://alfredlinux.com/community">
    <link rel="icon" href="/favicon.ico">
    <link rel="stylesheet" href="/assets/css/nav.css">
    <style>
        :root {
            --bg: #02000a;
            --surface: rgba(10, 10, 25, 0.6);
            --surface-hover: rgba(20, 20, 40, 0.8);
            --border: rgba(14, 165, 233, 0.3);
            --border-hover: rgba(212, 175, 55, 0.6);
            --text: #e0e0e0;
            --text-muted: #9ca3af;
            --text-dim: #6b7280;
            --gold: #facc15;
            --gold-light: #fde68a;
            --gold-dark: #d97706;
            --gold-glow: rgba(250,204,21,0.4);
            --accent: #6366f1;
            --accent-light: #a5b4fc;
            --cyber-blue: #0ea5e9;
            --cyber-glow: rgba(14, 165, 233, 0.5);
            --royal-purple: #7c3aed;
            --green: #34d399;
            --red: #ef4444;
            --amber: #f59e0b;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        html { scroll-behavior:smooth; }
        body {
            font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif;
            background:var(--bg); color:var(--text); min-height:100vh;
            overflow-x:hidden; -webkit-font-smoothing:antialiased; line-height:1.6;
            display:flex; flex-direction:column;
            }
        .container { perspective: 1200px;  }
        
        body::before {
            content: '';
            position: fixed;
            top: 0; left: -50%; width: 200%; height: 200%;
            background-image: 
                linear-gradient(rgba(14, 165, 233, 0.15) 1px, transparent 1px),
                linear-gradient(90deg, rgba(14, 165, 233, 0.15) 1px, transparent 1px);
            background-size: 60px 60px;
            transform: rotateX(60deg) translateY(-100px) translateZ(-200px);
            transform-origin: top center;
            z-index: -2;
            animation: grid-move 20s linear infinite;
            pointer-events: none;
        }
        @keyframes grid-move {
            0% { transform: rotateX(60deg) translateY(0) translateZ(-200px); }
            100% { transform: rotateX(60deg) translateY(60px) translateZ(-200px); }
        }

        body::after {
            content: '';
            position: fixed;
            top: 0; left: 0; width: 100vw; height: 100vh;
            background: radial-gradient(circle at 50% 50%, transparent 40%, rgba(2, 0, 10, 0.9) 100%);
            z-index: -1;
            pointer-events: none;
        }

        a { color:var(--accent-light); text-decoration:none; }
        a:hover { text-decoration:underline; text-shadow: 0 0 10px var(--accent-light); }

        .hero {
            padding:10rem 2rem 6rem; text-align:center; position:relative;
            transform-style: preserve-3d;
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0) rotateX(2deg); }
            50% { transform: translateY(-15px) rotateX(-2deg); }
        }

        .hero h1 {
            font-size:clamp(2.5rem,6vw,5rem); font-weight:900; letter-spacing:-0.03em;
            background:linear-gradient(135deg,#fff,var(--cyber-blue),var(--gold-light));
            -webkit-background-clip:text; -webkit-text-fill-color:transparent;
            margin-bottom:1.25rem; line-height:1.1; filter:drop-shadow(0 0 20px var(--cyber-glow));
            transform: translateZ(50px);
        }
        .hero p {
            font-size:clamp(1.1rem,2vw,1.3rem); color:var(--text-muted); max-width:800px; margin:0 auto 2rem; line-height:1.7;
            transform: translateZ(30px);
            text-shadow: 0 2px 10px rgba(0,0,0,0.8);
        }

        .container { perspective: 1200px; max-width:1240px; margin:0 auto 5rem; padding:0 2rem; flex:1; transform-style: preserve-3d; }

        .section { margin-top: 6rem; transform-style: preserve-3d; }
        .section h2 { font-size:clamp(1.8rem,4vw,3rem); font-weight:900; color:#fff; margin-bottom:1rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border); letter-spacing:-0.02em; text-shadow: 0 0 30px rgba(255,255,255,0.3); transform: translateZ(20px); }
        .section p { color:var(--text-muted); font-size:1.15rem; max-width:760px; margin-bottom: 2rem; line-height:1.6; transform: translateZ(10px); }

        .channel-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:2.5rem; margin:2rem 0; perspective: 1000px; }
        .channel-card {
            background:var(--surface); border:1px solid var(--border); padding:2.5rem; border-radius: 20px;
            position:relative; transition:all 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            transform-style: preserve-3d;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5), inset 0 0 20px rgba(14, 165, 233, 0.1);
            backdrop-filter: blur(10px);
            
        }
        .channel-card::after {
            content: ''; position: absolute; bottom: 0; right: 0;
            border-bottom: 20px solid var(--border);
            border-left: 20px solid transparent;
            transition: all 0.5s ease;
        }
        .channel-card:hover {
            transform: translateZ(40px) rotateX(5deg) rotateY(-5deg);
            border-color:var(--cyber-blue);
            box-shadow: 0 20px 50px rgba(0,0,0,0.6), inset 0 0 30px var(--cyber-glow), 0 0 20px var(--cyber-glow);
        }
        .channel-card:hover::after { border-bottom-color: var(--cyber-blue); }
        
        .channel-card .icon { font-size: 2.5rem; margin-bottom: 1rem; transform: translateZ(30px); text-shadow: 0 0 20px var(--cyber-glow); }
        .channel-card h3 { font-size:1.3rem; font-weight:800; color:#fff; margin-bottom:0.8rem; transform: translateZ(20px); text-shadow: 0 2px 5px rgba(0,0,0,0.8); }
        .channel-card p { color:var(--text-muted); font-size:0.95rem; line-height:1.65; margin-bottom:1.5rem; transform: translateZ(10px); }
        
        .channel-card .btn {
            display:inline-flex; align-items:center; gap:0.5rem; padding:0.6rem 1.5rem; border-radius: 8px;
            font-weight:900; font-size:0.9rem; text-decoration:none; transition:all 0.3s; text-transform: uppercase; letter-spacing: 1px;
            background:transparent; color:var(--cyber-blue); border:1px solid var(--cyber-blue);
            transform: translateZ(20px); position: relative; overflow: hidden;
        }
        .channel-card .btn:hover { background:rgba(14, 165, 233, 0.1); box-shadow: 0 0 20px var(--cyber-glow); color: #fff; border-color: #fff; }

        .repo-list { list-style: none; padding: 0; margin: 1.5rem 0; transform-style: preserve-3d; }
        .repo-list li {
            padding: 1.25rem 1.5rem; border: 1px solid var(--border); border-radius: 12px; margin-bottom: 1rem; 
            display: flex; justify-content: space-between; align-items: center; background: var(--surface);
            transition: all 0.3s ease; position: relative; overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        .repo-list li:hover {
            border-color: var(--cyber-blue); transform: translateX(10px) translateZ(20px);
            background: rgba(14, 165, 233, 0.05); box-shadow: 0 10px 30px rgba(0,0,0,0.5), inset 0 0 20px rgba(14, 165, 233, 0.1);
        }
        .repo-name { font-weight: 800; color: #fff; font-size: 1.1rem; text-shadow: 0 0 10px rgba(255,255,255,0.3); }
        .repo-desc { color: var(--text-muted); font-size: 0.9rem; }
        .repo-link { color: var(--cyber-blue); font-size: 0.95rem; font-weight: 700; white-space: nowrap; text-transform: uppercase; letter-spacing: 1px; }
        .repo-list li:hover .repo-link { color: #fff; text-shadow: 0 0 10px var(--cyber-glow); }

        .contribute-steps { counter-reset: step; list-style: none; padding: 0; margin: 2rem 0; transform-style: preserve-3d; }
        .contribute-steps li {
            counter-increment: step; padding: 1.5rem 1.5rem 1.5rem 4rem; position: relative; border: 1px solid var(--border); border-radius: 12px; margin-bottom: 1rem; background: var(--surface);
            transition: all 0.3s ease; box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        .contribute-steps li:hover {
            border-color: var(--gold); transform: translateY(-5px) translateZ(20px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.5), inset 0 0 20px rgba(250,204,21,0.1);
        }
        .contribute-steps li::before {
            content: counter(step); position: absolute; left: 1.25rem; top: 1.5rem; width: 2rem; height: 2rem; border-radius: 50%; 
            background: var(--gold); color: #000; font-size: 1rem; font-weight: 900; display: flex; align-items: center; justify-content: center;
            box-shadow: 0 0 15px var(--gold-glow);
        }
        .contribute-steps li strong { color: #fff; font-weight: 800; }
        .contribute-steps li p, .contribute-steps li code { color: var(--text-muted); font-size: 0.95rem; }
        code { background: rgba(0,0,0,0.5); padding: 0.2rem 0.5rem; border-radius: 6px; font-size: 0.9rem; border: 1px solid rgba(255,255,255,0.1); color: var(--cyber-blue); }

        .cta-box {
            text-align: center; margin: 6rem 0 2rem; padding: 4rem; background: rgba(14, 165, 233, 0.05); border: 1px solid var(--cyber-blue); border-radius: 20px;
            box-shadow: 0 0 40px rgba(14, 165, 233, 0.1), inset 0 0 30px rgba(14, 165, 233, 0.05);
            backdrop-filter: blur(10px); transform-style: preserve-3d;
            
        }
        .cta-box h2 { font-size: 2.2rem; font-weight: 900; color: #fff; margin-bottom: 1rem; text-transform: uppercase; letter-spacing: 2px; text-shadow: 0 0 30px var(--cyber-glow); transform: translateZ(30px); }
        .cta-box p { color: var(--text-muted); margin-bottom: 2rem; font-size: 1.1rem; transform: translateZ(20px); }
        
        .cta-box .btn {
            display:inline-flex; align-items:center; justify-content: center; padding:1rem 3rem;
            font-weight:900; font-size:1.1rem; text-decoration:none; transition:all 0.3s; text-transform: uppercase; letter-spacing: 1px;
            background:var(--cyber-blue); color:#000; box-shadow:0 0 30px var(--cyber-glow); 
            
            transform: translateZ(40px); position: relative; overflow: hidden;
        }
        .cta-box .btn::after { content:''; position:absolute; top:0; left:-100%; width:50%; height:100%; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.8), transparent); transform: skewX(-20deg); transition: 0.5s; }
        .cta-box .btn:hover::after { left: 200%; }
        .cta-box .btn:hover { background:#fff; box-shadow:0 0 50px var(--cyber-glow); transform: translateZ(50px) scale(1.05); }

        footer { text-align: center; padding: 4rem 2rem; color: var(--text-dim); font-size: 0.95rem; border-top: 1px solid rgba(255,255,255,0.05); position: relative; z-index: 10; background: rgba(0,0,0,0.8); backdrop-filter: blur(10px); }
        footer a { color: var(--cyber-blue); font-weight: 600; text-decoration: none; }
        footer a:hover { color: #fff; text-shadow: 0 0 10px var(--cyber-glow); }

        @media (max-width: 768px) {
            .hero { padding: 8rem 1.5rem 4rem; animation: none; }
            .container { perspective: 1200px; padding: 0 1.5rem 4rem; transform: none; }
            .channel-grid { grid-template-columns: 1fr; }
            .cta-box { clip-path: none; transform: none; }
            body::before { animation: none; opacity: 0.5; }
        }
    </style>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebPage",
        "name": "Alfred Linux Community",
        "description": "Community hub for Alfred Linux — bug reports, feature requests, source code, and contribution guides.",
        "url": "https://alfredlinux.com/community",
        "isPartOf": {
            "@type": "WebSite",
            "name": "Alfred Linux",
            "url": "https://alfredlinux.com"
        },
        "publisher": {
            "@type": "Organization",
            "name": "GoSiteMe Inc.",
            "url": "https://gositeme.com"
        }
    }
    </script>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/img/favicon/favicon-16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/img/favicon/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#D4AF37">
</head>
<body>

<?php $currentPage = 'community'; include __DIR__ . '/includes/nav.php'; ?>

<div class="hero">
    <h1>Community</h1>
    <p>Report bugs. Request features. Read the source. Contribute patches. Alfred Linux is company-backed and open source — built in public, auditable by anyone.</p>
</div>

<div class="container">

    <!-- ── Channels ────────────────────────────────────────── -->
    <div class="section">
        <h2>Where to Participate</h2>
        <div class="channel-grid">
            <div class="channel-card">
                <div class="icon">ðŸ›</div>
                <h3>Bug Reports</h3>
                <p>Found a problem? Open an issue on the relevant GoForge repo. Include your hardware, ISO version, and steps to reproduce.</p>
                <a href="/forge/commander/alfredlinux.com/issues" class="btn">Report a Bug</a>
            </div>
            <div class="channel-card">
                <div class="icon">💡</div>
                <h3>Feature Requests</h3>
                <p>Have an idea? Open an issue tagged "enhancement" on GoForge. We read every request and respond to feasible ones.</p>
                <a href="/forge/commander/alfredlinux.com/issues" class="btn">Request Feature</a>
            </div>
            <div class="channel-card">
                <div class="icon">📖</div>
                <h3>Source Code</h3>
                <p>8 public repositories. Every hook, every build script, every page you're looking at. Browse, fork, audit.</p>
                <a href="/forge/explore/repos" class="btn">Browse Repos</a>
            </div>
            <div class="channel-card">
                <div class="icon">🔧</div>
                <h3>Contribute Code</h3>
                <p>See the contribution guide below. Fork a repo, make your change, submit a pull request on GoForge.</p>
                <a href="/forge/commander/alfredlinux.com" class="btn">Fork &amp; Contribute</a>
            </div>
            <div class="channel-card">
                <div class="icon">📡</div>
                <h3>Developer Portal</h3>
                <p>SDKs, APIs, MCP tool documentation, and extension development guides for building on Alfred.</p>
                <a href="/developers" class="btn">Developer Docs</a>
            </div>
            <div class="channel-card">
                <div class="icon">🛡️ï¸</div>
                <h3>Security Reports</h3>
                <p>Found a vulnerability? Report it responsibly. We respond to security reports within 48 hours.</p>
                <a href="/security" class="btn">Security Policy</a>
            </div>
        </div>
    </div>

    <!-- ── Repositories ────────────────────────────────────── -->
    <div class="section">
        <h2>Open Source Repositories</h2>
        <p>All source code is on <a href="/forge/explore/repos">GoForge</a> — our self-hosted Git platform. No GitHub dependency, no third-party risk.</p>
        <ul class="repo-list">
            <li>
                <div><span class="repo-name">alfred-linux</span><br><span class="repo-desc">Build system — 369 build hooks (Kingdom of God Edition); v4.0 line was 17</span></div>
                <a href="/forge/commander/alfredlinux.com" class="repo-link">Browse →</a>
            </li>
            <li>
                <div><span class="repo-name">alfred-agent</span><br><span class="repo-desc">Autonomous AI agent — 1,870 lines, 14 tools, multi-provider</span></div>
                <a href="/forge/commander/alfred-agent" class="repo-link">Browse →</a>
            </li>
            <li>
                <div><span class="repo-name">alfred-commander</span><br><span class="repo-desc">IDE extension — 3,551 lines, AI chat, voice, stats, walkthrough</span></div>
                <a href="/forge/commander/alfred-ide" class="repo-link">Browse →</a>
            </li>
            <li>
                <div><span class="repo-name">alfred-ide</span><br><span class="repo-desc">IDE infrastructure — code-server config, branding, startup</span></div>
                <a href="/forge/commander/alfred-ide" class="repo-link">Browse →</a>
            </li>
            <li>
                <div><span class="repo-name">alfredlinux.com</span><br><span class="repo-desc">This website — 12 PHP pages, clean URLs, JSON-LD, P2P downloads</span></div>
                <a href="/forge/commander/alfredlinux.com" class="repo-link">Browse →</a>
            </li>
            <li>
                <div><span class="repo-name">alfred-mobile</span><br><span class="repo-desc">Android installer — Termux + proot, Samsung DeX support</span></div>
                <a href="/forge/commander/alfred-mobile" class="repo-link">Browse →</a>
            </li>
            <li>
                <div><span class="repo-name">alfred-browser</span><br><span class="repo-desc">Privacy-first browser — Electron, post-quantum crypto, P2P</span></div>
                <a href="/forge/commander/alfred-browser" class="repo-link">Browse →</a>
            </li>
            <li>
                <div><span class="repo-name">meta-dome</span><br><span class="repo-desc">VR worldbuilding platform — spatial computing, WebXR</span></div>
                <a href="/forge/commander/meta-dome" class="repo-link">Browse →</a>
            </li>
        </ul>
    </div>

    <!-- ── How to Contribute ──────────────────────────────── -->
    <div class="section">
        <h2>How to Contribute</h2>
        <p>We welcome contributions of all kinds — code, documentation, bug reports, hardware test results, translations.</p>
        <ol class="contribute-steps">
            <li>
                <strong>Find what to work on</strong>
                <p>Browse <a href="/forge/commander/alfredlinux.com/issues">open issues</a> on GoForge. Issues tagged <code>good-first-issue</code> are beginner-friendly. Or fix something that bothers you.</p>
            </li>
            <li>
                <strong>Fork the repository</strong>
                <p>Click "Fork" on the GoForge repo page. Clone your fork locally with <code>git clone</code>.</p>
            </li>
            <li>
                <strong>Make your change</strong>
                <p>Work in a topic branch. Keep commits focused — one logical change per commit. Test your changes.</p>
            </li>
            <li>
                <strong>Submit a pull request</strong>
                <p>Push your branch and open a Pull Request on GoForge. Describe what you changed and why. We review within a week.</p>
            </li>
        </ol>
        <p><strong>Code style:</strong> No linter religion. Write clear code, comment non-obvious logic, test what you change.</p>
        <p><strong>License:</strong> All contributions are licensed under <a href="https://www.gnu.org/licenses/agpl-3.0.html">KCL-1.0</a> — the same license as the project.</p>
    </div>

    <!-- ── Hardware Testing ────────────────────────────────── -->
    <div class="section">
        <h2>Submit Hardware Test Results</h2>
        <p>Tested Alfred Linux on your machine? We want to hear about it. Open an issue on <a href="/forge/commander/alfredlinux.com/issues">alfred-linux</a> with:</p>
        <ul style="list-style:none;padding:0;margin:1rem 0;">
            <li style="padding:0.4rem 0 0.4rem 1.5rem;position:relative;color:var(--text-muted);font-size:0.92rem;"><span style="position:absolute;left:0.4rem;color:var(--accent);font-weight:700;">›</span> <strong style="color:var(--text)">Machine:</strong> Make, model, CPU, RAM, GPU</li>
            <li style="padding:0.4rem 0 0.4rem 1.5rem;position:relative;color:var(--text-muted);font-size:0.92rem;"><span style="position:absolute;left:0.4rem;color:var(--accent);font-weight:700;">›</span> <strong style="color:var(--text)">ISO version:</strong> Which version (e.g., v7.77 GA)</li>
            <li style="padding:0.4rem 0 0.4rem 1.5rem;position:relative;color:var(--text-muted);font-size:0.92rem;"><span style="position:absolute;left:0.4rem;color:var(--accent);font-weight:700;">›</span> <strong style="color:var(--text)">Boot method:</strong> USB, QEMU, VirtualBox, VMware, bare metal</li>
            <li style="padding:0.4rem 0 0.4rem 1.5rem;position:relative;color:var(--text-muted);font-size:0.92rem;"><span style="position:absolute;left:0.4rem;color:var(--accent);font-weight:700;">›</span> <strong style="color:var(--text)">What worked:</strong> Boot, desktop, WiFi, audio, GPU, installer</li>
            <li style="padding:0.4rem 0 0.4rem 1.5rem;position:relative;color:var(--text-muted);font-size:0.92rem;"><span style="position:absolute;left:0.4rem;color:var(--accent);font-weight:700;">›</span> <strong style="color:var(--text)">What didn't:</strong> Any hardware that failed</li>
        </ul>
        <p>Every test result helps. See the <a href="/hardware">hardware compatibility list</a> for machines already verified.</p>
    </div>

    <!-- ── CTA ─────────────────────────────────────────────── -->
    <div class="cta-box">
        <h2>Start Contributing Today</h2>
        <p>The code is open. The issues are public. The roadmap is transparent. Pick something and build.</p>
        <a href="/forge/explore/repos" class="btn">Browse GoForge Repos</a>
    </div>

    <!-- ── CONTACT ────────────────────────────────────────── -->
    <div class="section" style="margin-top:2rem;">
        <h2>Direct Contact</h2>
        <p>Need to reach us directly?</p>
        <ul class="repo-list">
            <li><span class="repo-name">General inquiries</span><span class="repo-link"><a href="mailto:hello@gositeme.com">hello@gositeme.com</a></span></li>
            <li><span class="repo-name">Security reports</span><span class="repo-link"><a href="mailto:security@gositeme.com">security@gositeme.com</a></span></li>
            <li><span class="repo-name">Privacy questions</span><span class="repo-link"><a href="mailto:privacy@gositeme.com">privacy@gositeme.com</a></span></li>
        </ul>
    </div>

</div>

<footer>
    <p style="font-style:italic;color:#94a3b8;font-size:.85rem;">&ldquo;The grass withereth, the flower fadeth: but the word of our God shall stand for ever.&rdquo; &mdash; <a href="https://gositeme.com/bible/read/isaiah/40" style="color:#facc15;text-decoration:none;">Isaiah 40:8</a> (AKJV)</p>
    <p>&copy; <?= $year ?> <a href="https://gositeme.com">GoSiteMe Inc.</a> &mdash; Alfred Linux &middot; Open Source (KCL-1.0)</p>
</footer>

<script>
document.querySelector('.nav-toggle')?.addEventListener('click', () => {
    document.querySelector('.nav-links').classList.toggle('open');
});
</script>
<?php include __DIR__ . "/includes/shabbat-banner.php"; ?>
</body>
</html>

