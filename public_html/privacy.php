<?php
/**
 * Alfred Linux — Privacy Manifesto
 * Zero telemetry. Zero tracking. Enforced by Kernel-level mathematics.
 *
 * GoSiteMe Inc. — June 2026
 */
$year = date('Y');
require_once __DIR__ . '/includes/ga-release-state.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Manifesto — Alfred Linux</title>
    <meta name="description" content="Alfred Linux privacy manifesto. Zero telemetry. Zero tracking. Enforced by eBPF and Post-Quantum Cryptography.">
    <meta property="og:title" content="Privacy Manifesto — Alfred Linux">
    <meta property="og:description" content="Zero telemetry. Zero tracking. Enforced by eBPF and Post-Quantum Cryptography.">
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
        .proof-box .badge { margin-left: 0.5rem; }

        .badge { display: inline-block; padding: 0.15rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }
        .badge-full { background: rgba(52,211,153,0.15); color: var(--green); }

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
        "name": "Privacy Manifesto — Alfred Linux",
        "description": "Alfred Linux privacy manifesto. Zero telemetry. Zero tracking. Enforced by eBPF and Post-Quantum Cryptography.",
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
    <h1>Privacy Manifesto</h1>
    <p>Zero telemetry. Zero tracking. Zero data collection. This isn't just a policy — it is mathematically and physically enforced at the kernel level.</p>
</div>

<div class="container">

    <div class="section">
        <h2>The God-Mode Standard</h2>
        <p><strong>Alfred Linux does not collect, transmit, store, or sell any user data. Period.</strong></p>
        <p>There is no telemetry. There is no analytics. There is no phone-home. There is no crash reporting. There is no usage tracking. There are no cookies. There is no fingerprinting. There is no data broker relationship. There never will be.</p>
    </div>

    <div class="section">
        <h2>God-Mode Kernel Enforcement (Ring-0)</h2>
        <p>We do not rely on "promises" to protect your data. We rely on mathematics.</p>
        <ul>
            <li><strong>Cilium Tetragon eBPF Enforcement</strong> — Our custom Linux kernel integrates Zero-Trust eBPF hooks directly at Ring-0. If a proprietary driver or rogue application attempts to phone home, the kernel intercepts and blocks the packet before it even reaches the network stack.</li>
            <li><strong>Air-Gapped Telemetry Matrix</strong> — AlfredLinux thrives completely offline. Update checks are explicitly manual. There are no background daemons pinging remote servers to check for internet connectivity.</li>
            <li><strong>No proprietary telemetry escapes</strong> — Firefox is replaced with Alfred Browser (Tauri + WebKitGTK, zero tracking). Snap is purged. Ubuntu telemetry daemons are physically erased from the ISO.</li>
        </ul>
    </div>

    <div class="section">
        <h2>Post-Quantum Data Sovereignty</h2>
        <p>Your data at rest is as secure as your data in transit.</p>
        <ul>
            <li><strong>LUKS2 &amp; Hardware TPM Validation</strong> — User data is mathematically locked at rest using LUKS2, sealed by the physical Hardware TPM chip. </li>
            <li><strong>ML-KEM Post-Quantum Cryptography</strong> — Our bootloader and secure enclaves are hardened with NIST-standardized ML-KEM algorithms. Even if state actors steal your physical drive, the data is unreadable, not just today, but against future quantum decryption arrays.</li>
        </ul>
    </div>

    <div class="section">
        <h2>How to Verify (Zero-Trust Audit)</h2>
        <p>Don't trust this page. Verify the mathematics yourself:</p>
        <div class="proof-box">
            <h3>1. eBPF Packet Tracing <span class="badge badge-full">✓ God-Mode</span></h3>
            <p>Boot the live ISO and monitor the kernel directly:</p>
            <code>sudo tetra getevents --network</code>
            <p style="margin-top:0.5rem;">Watch the raw kernel events. You will see DHCP, DNS, and absolute silence. Zero connections to GoSiteMe, Google, Microsoft, or any telemetry endpoint.</p>
        </div>
        <div class="proof-box">
            <h3>2. Source Code Compilation Audit</h3>
            <p>Every single line of the 100-GiB build process is transparent. Read the 1340 Live-Build hooks on <a href="/forge/commander/alfredlinux.com">GoForge</a>. There is no hidden payload.</p>
        </div>
        <div class="proof-box">
            <h3>3. Subsystem DNS Audit</h3>
            <code>grep -r "telemetry\|analytics\|tracking\|phone.home" /etc/ /usr/share/alfred/</code>
            <p style="margin-top:0.5rem;">Zero results. Because it doesn't exist.</p>
        </div>
    </div>

    <div class="section">
        <h2>This Website</h2>
        <p>alfredlinux.com is a static PHP site. It does not use cookies. It does not use JavaScript analytics. It does not fingerprint browsers. It does not log IP addresses beyond standard Apache access logs (which are used only for abuse detection and are rotated every 14 days).</p>
        <p><?php if (isset($finalGaIsoPublished) && $finalGaIsoPublished): ?>
        The download page may load a WebTorrent client — opt-in, connects to public BitTorrent trackers for GA ISO P2P, not GoSiteMe application servers.
        <?php else: ?>
        The download page includes optional JavaScript for in-browser P2P; it is inactive for GA ISO fetch until the official torrent is published (see <a href="/download">/download</a>). No analytics scripts run on this site.
        <?php endif; ?></p>
    </div>

    <div class="section">
        <h2>Third-Party Services</h2>
        <p>Alfred Linux ships with <strong>Flatpak + Flathub</strong> for the Alfred Store. When you install apps via the Store, your machine connects to Flathub's CDN. Flathub has their own <a href="https://flathub.org/about" target="_blank" rel="noopener">privacy policy</a>. This is the only third-party network connection in the default install, and it only happens when you explicitly trigger an installation.</p>
    </div>

    <div class="section">
        <h2>Contact</h2>
        <p>Privacy questions: <strong>privacy@gositeme.com</strong></p>
        <p>General inquiries: <strong>hello@gositeme.com</strong></p>
        <p>Bug reports: <a href="/forge/commander/alfredlinux.com/issues">GoForge Issues</a></p>
    </div>

    <p class="updated">Last updated: June 2026</p>

</div>

<footer>
    <p>&copy; <?= $year ?> <a href="https://gositeme.com">GoSiteMe Inc.</a> &mdash; Alfred Linux &middot; Open Source (KCL-1.0)</p>
    <p style="margin-top:0.75rem;font-style:italic;color:#9ca3af;">&ldquo;The grass withereth, the flower fadeth: but the word of our God shall stand for ever.&rdquo; &mdash; Isaiah 40:8 AKJV</p>
    <p style="margin-top:0.5rem;"><a href="https://lavocat.ca/journal?read=9&amp;lang=en">Commander&rsquo;s Journal</a> &middot; <a href="https://gositeme.com/sovereignty">Sovereignty Declarations</a></p>
</footer>

</body>
</html>
