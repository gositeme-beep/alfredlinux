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
            --bg: #06060b; --surface: rgba(255,255,255,0.03); --surface-hover: rgba(255,255,255,0.06);
            --border: rgba(255,255,255,0.06); --border-hover: rgba(99,102,241,0.3);
            --text: #e0e0e0; --text-muted: #9ca3af; --text-dim: #6b7280;
            --accent: #6366f1; --accent-light: #a5b4fc; --accent2: #8b5cf6;
            --green: #34d399; --amber: #f59e0b; --cyan: #22d3ee;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter',-apple-system,BlinkMacSystemFont,sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; -webkit-font-smoothing: antialiased; line-height: 1.7; }
        a { color: var(--accent-light); text-decoration: none; }
        a:hover { text-decoration: underline; }


        .hero { text-align: center; padding: 6rem 2rem 3rem; background: radial-gradient(ellipse at 50% 20%, rgba(99,102,241,0.12) 0%, transparent 55%); }
        .hero h1 { font-size: clamp(2rem, 5vw, 3rem); font-weight: 900; margin-bottom: 1rem; background: linear-gradient(135deg, #fff, var(--accent-light), var(--cyan)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .hero p { color: var(--text-muted); font-size: 1.1rem; max-width: 650px; margin: 0 auto; }

        .container { max-width: 900px; margin: 0 auto; padding: 0 2rem 4rem; }

        .section { margin-top: 4rem; }
        .section h2 { font-size: 1.5rem; font-weight: 800; color: #fff; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border); }
        .section p { color: var(--text-muted); margin-bottom: 1rem; font-size: 0.95rem; }

        .channel-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.25rem; margin: 2rem 0; }
        .channel-card { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 1.75rem; transition: border-color 0.2s, background 0.2s; }
        .channel-card:hover { border-color: var(--border-hover); background: var(--surface-hover); }
        .channel-card .icon { font-size: 1.8rem; margin-bottom: 0.75rem; }
        .channel-card h3 { font-size: 1.05rem; font-weight: 700; color: #fff; margin-bottom: 0.5rem; }
        .channel-card p { color: var(--text-muted); font-size: 0.85rem; line-height: 1.6; margin-bottom: 1rem; }
        .channel-card .btn { display: inline-block; padding: 0.5rem 1rem; border-radius: 8px; background: var(--accent); color: #fff; font-size: 0.85rem; font-weight: 600; text-decoration: none; }
        .channel-card .btn:hover { background: var(--accent2); text-decoration: none; }

        .repo-list { list-style: none; padding: 0; margin: 1.5rem 0; }
        .repo-list li { padding: 0.75rem 1rem; border: 1px solid var(--border); border-radius: 10px; margin-bottom: 0.5rem; display: flex; justify-content: space-between; align-items: center; background: var(--surface); }
        .repo-list li:hover { border-color: var(--border-hover); }
        .repo-name { font-weight: 600; color: #fff; font-size: 0.95rem; }
        .repo-desc { color: var(--text-muted); font-size: 0.8rem; }
        .repo-link { color: var(--accent-light); font-size: 0.85rem; white-space: nowrap; }

        .contribute-steps { counter-reset: step; list-style: none; padding: 0; margin: 1.5rem 0; }
        .contribute-steps li { counter-increment: step; padding: 1rem 1rem 1rem 3.5rem; position: relative; border: 1px solid var(--border); border-radius: 10px; margin-bottom: 0.75rem; background: var(--surface); }
        .contribute-steps li::before { content: counter(step); position: absolute; left: 1rem; top: 1rem; width: 1.75rem; height: 1.75rem; border-radius: 50%; background: var(--accent); color: #fff; font-size: 0.85rem; font-weight: 700; display: flex; align-items: center; justify-content: center; }
        .contribute-steps li strong { color: #fff; }
        .contribute-steps li p, .contribute-steps li code { color: var(--text-muted); font-size: 0.88rem; }
        code { background: rgba(0,0,0,0.3); padding: 0.15rem 0.4rem; border-radius: 4px; font-size: 0.85rem; }

        .cta-box { text-align: center; margin: 4rem 0 2rem; padding: 3rem; background: var(--surface); border: 1px solid var(--border); border-radius: 16px; }
        .cta-box h2 { font-size: 1.4rem; font-weight: 800; color: #fff; margin-bottom: 0.75rem; }
        .cta-box p { color: var(--text-muted); margin-bottom: 1.5rem; }
        .cta-box .btn { display: inline-block; padding: 0.75rem 2rem; border-radius: 10px; background: var(--accent); color: #fff; font-weight: 700; text-decoration: none; font-size: 1rem; }
        .cta-box .btn:hover { background: var(--accent2); text-decoration: none; }

        footer { text-align: center; padding: 3rem 2rem; color: var(--text-dim); font-size: 0.85rem; border-top: 1px solid var(--border); }
        footer a { color: var(--accent-light); }

        @media (max-width: 768px) {
            .hero { padding: 5rem 1.5rem 2rem; }
            .container { padding: 0 1.25rem 3rem; }
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
                <div class="icon">🐛</div>
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
                <div class="icon">🛡️</div>
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
        <p><strong>License:</strong> All contributions are licensed under <a href="https://www.gnu.org/licenses/agpl-3.0.html">AGPL-3.0</a> — the same license as the project.</p>
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
    <p>&copy; <?= $year ?> <a href="https://gositeme.com">GoSiteMe Inc.</a> &mdash; Alfred Linux &middot; Open Source (AGPL-3.0)</p>
</footer>

<script>
document.querySelector('.nav-toggle')?.addEventListener('click', () => {
    document.querySelector('.nav-links').classList.toggle('open');
});
</script>
<?php include __DIR__ . "/includes/shabbat-banner.php"; ?>
</body>
</html>
