<?php
/**
 * Alfred Linux — Privacy Policy
 * Zero telemetry. Zero tracking. Here's the proof.
 *
 * GoSiteMe Inc. — April 2026
 */
$year = date('Y');
require_once __DIR__ . '/includes/ga-release-state.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy — Alfred Linux</title>
    <meta name="description" content="Alfred Linux privacy policy. Zero telemetry. Zero tracking. Zero data collection. No analytics. No phone-home. Auditable source code proves it.">
    <meta property="og:title" content="Privacy Policy — Alfred Linux">
    <meta property="og:description" content="Zero telemetry. Zero tracking. Zero data collection. Auditable source code proves it.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://alfredlinux.com/privacy">
    <meta property="og:image" content="https://alfredlinux.com/og-image.png">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="canonical" href="https://alfredlinux.com/privacy">
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

        .container { max-width: 800px; margin: 0 auto; padding: 0 2rem 4rem; }

        .section { margin-top: 3rem; }
        .section h2 { font-size: 1.4rem; font-weight: 800; color: #fff; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border); }
        .section h3 { font-size: 1.1rem; font-weight: 700; color: var(--accent-light); margin: 1.5rem 0 0.5rem; }
        .section p { color: var(--text-muted); margin-bottom: 1rem; font-size: 0.95rem; }
        .section ul { list-style: none; padding: 0; margin: 1rem 0; }
        .section li { padding: 0.5rem 0 0.5rem 1.5rem; position: relative; color: var(--text-muted); font-size: 0.92rem; }
        .section li::before { content: "✓"; position: absolute; left: 0.2rem; color: var(--green); font-weight: 700; }
        .section li strong { color: var(--text); }

        .proof-box { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 1.5rem; margin: 1.5rem 0; }
        .proof-box code { background: rgba(0,0,0,0.3); padding: 0.15rem 0.4rem; border-radius: 4px; font-size: 0.85rem; color: var(--green); }

        .updated { color: var(--text-dim); font-size: 0.85rem; margin-top: 3rem; text-align: center; }

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
        "name": "Privacy Policy — Alfred Linux",
        "description": "Alfred Linux privacy policy. Zero telemetry. Zero tracking. Zero data collection.",
        "url": "https://alfredlinux.com/privacy",
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

<?php $currentPage = 'privacy'; include __DIR__ . '/includes/nav.php'; ?>

<div class="hero">
    <h1>Privacy Policy</h1>
    <p>Zero telemetry. Zero tracking. Zero data collection. This isn't a toggle — it's the architecture.</p>
</div>

<div class="container">

    <div class="section">
        <h2>The Short Version</h2>
        <p><strong>Alfred Linux does not collect, transmit, store, or sell any user data. Period.</strong></p>
        <p>There is no telemetry. There is no analytics. There is no phone-home. There is no crash reporting. There is no usage tracking. There are no cookies. There is no fingerprinting. There is no data broker relationship. There never will be.</p>
    </div>

    <div class="section">
        <h2>What Alfred Linux Does NOT Do</h2>
        <ul>
            <li><strong>No telemetry</strong> — No system information is transmitted to any server. Not hardware specs, not usage patterns, not crash reports, not package lists.</li>
            <li><strong>No analytics</strong> — This website uses zero analytics. No Google Analytics. No Plausible. No Matomo. No tracking pixels. No cookies.</li>
            <li><strong>No phone-home</strong> — The OS does not contact any GoSiteMe server after installation. Update checks are manual via <code>alfred-update</code>.</li>
            <li><strong>No crash reporting</strong> — Application crashes stay on your machine. We don't collect or upload dumps.</li>
            <li><strong>No user accounts required</strong> — You don't need to register anywhere to use Alfred Linux.</li>
            <li><strong>No third-party telemetry</strong> — Firefox is replaced with Alfred Browser (Tauri + WebKitGTK, zero tracking). Snap is not installed. Ubuntu telemetry daemons are not present.</li>
        </ul>
    </div>

    <div class="section">
        <h2>How to Verify</h2>
        <p>Don't trust this page. Verify it yourself:</p>
        <div class="proof-box">
            <h3>1. Network audit</h3>
            <p>Boot the live ISO and run:</p>
            <code>sudo tcpdump -i any -n -c 100</code>
            <p style="margin-top:0.5rem;">Watch the traffic. You'll see DHCP, DNS, and nothing else. Zero connections to GoSiteMe, Google, Microsoft, Ubuntu, or any telemetry endpoint.</p>
        </div>
        <div class="proof-box">
            <h3>2. Source code audit</h3>
            <p>Every build hook is open source on <a href="/forge/commander/alfredlinux.com">GoForge</a>. Read the 42 hooks line by line. There is no hidden network call.</p>
        </div>
        <div class="proof-box">
            <h3>3. DNS audit</h3>
            <code>grep -r "telemetry\|analytics\|tracking\|phone.home" /etc/ /usr/share/alfred/</code>
            <p style="margin-top:0.5rem;">Zero results. Because it doesn't exist.</p>
        </div>
    </div>

    <div class="section">
        <h2>This Website</h2>
        <p>alfredlinux.com is a static PHP site. It does not use cookies. It does not use JavaScript analytics. It does not fingerprint browsers. It does not log IP addresses beyond standard Apache access logs (which are used only for abuse detection and are rotated every 14 days).</p>
        <p><?php if ($finalGaIsoPublished): ?>
        The download page may load a WebTorrent client — opt-in, connects to public BitTorrent trackers for GA ISO P2P, not GoSiteMe application servers.
        <?php else: ?>
        The download page includes optional JavaScript for in-browser P2P; it is inactive for GA ISO fetch until the official torrent is published (see <a href="/download">/download</a>). No analytics scripts run on this site.
        <?php endif; ?></p>
    </div>

    <div class="section">
        <h2>Third-Party Services</h2>
        <p>Alfred Linux ships with <strong>Flatpak + Flathub</strong> for the Alfred Store. When you install apps via the Store, your machine connects to Flathub's CDN. Flathub has their own <a href="https://flathub.org/about" target="_blank" rel="noopener">privacy policy</a>. This is the only third-party network connection in the default install, and it only happens when you explicitly install an app.</p>
    </div>

    <div class="section">
        <h2>Contact</h2>
        <p>Privacy questions: <strong>privacy@gositeme.com</strong></p>
        <p>General inquiries: <strong>hello@gositeme.com</strong></p>
        <p>Bug reports: <a href="/forge/commander/alfredlinux.com/issues">GoForge Issues</a></p>
    </div>

    <p class="updated">Last updated: April 16, 2026</p>

</div>

<footer>
    <p>&copy; <?= $year ?> <a href="https://gositeme.com">GoSiteMe Inc.</a> &mdash; Alfred Linux &middot; Open Source (AGPL-3.0)</p>
    <p style="margin-top:0.75rem;font-style:italic;color:#9ca3af;">&ldquo;The grass withereth, the flower fadeth: but the word of our God shall stand for ever.&rdquo; &mdash; Isaiah 40:8 AKJV</p>
    <p style="margin-top:0.5rem;"><a href="https://lavocat.ca/journal?read=9&amp;lang=en">Commander&rsquo;s Journal</a> &middot; <a href="https://gositeme.com/sovereignty">Sovereignty Declarations</a></p>
</footer>

</body>
</html>
