<?php
/**
 * Alfred Linux — Hardware Compatibility List
 * Tested machines, VMs, and known issues
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
    <title>Hardware Compatibility — Alfred Linux</title>
    <meta name="description" content="Alfred Linux hardware compatibility list. Tested machines, virtual machines, and known hardware issues. Contribute your own test results.">
    <meta property="og:title" content="Hardware Compatibility — Alfred Linux">
    <meta property="og:description" content="Tested hardware for Alfred Linux. VMs, laptops, desktops — contribute your results.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://alfredlinux.com/hardware">
    <meta property="og:image" content="https://alfredlinux.com/og-image.png">
    <link rel="canonical" href="https://alfredlinux.com/hardware">
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


        .hero { text-align: center; padding: 6rem 2rem 3rem; background: radial-gradient(ellipse at 50% 20%, rgba(52,211,153,0.08) 0%, transparent 55%); }
        .hero h1 { font-size: clamp(2rem, 5vw, 3rem); font-weight: 900; margin-bottom: 1rem; background: linear-gradient(135deg, #fff, var(--green), var(--cyan)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .hero p { color: var(--text-muted); font-size: 1.1rem; max-width: 650px; margin: 0 auto; }

        .container { max-width: 1000px; margin: 0 auto; padding: 0 2rem 4rem; }

        .section { margin-top: 4rem; }
        .section h2 { font-size: 1.5rem; font-weight: 800; color: #fff; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border); }
        .section p { color: var(--text-muted); margin-bottom: 1rem; font-size: 0.95rem; }

        .status-legend { display: flex; gap: 1.5rem; margin: 1rem 0 2rem; flex-wrap: wrap; }
        .status-legend span { display: flex; align-items: center; gap: 0.4rem; font-size: 0.85rem; color: var(--text-muted); }
        .dot { width: 10px; height: 10px; border-radius: 50%; }
        .dot-full { background: var(--green); }
        .dot-partial { background: var(--amber); }
        .dot-na { background: var(--text-dim); }

        .hw-table { width: 100%; border-collapse: collapse; margin: 1.5rem 0; }
        .hw-table th { text-align: left; padding: 0.75rem 1rem; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-dim); border-bottom: 1px solid var(--border); }
        .hw-table td { padding: 0.75rem 1rem; font-size: 0.88rem; border-bottom: 1px solid rgba(255,255,255,0.03); color: var(--text-muted); }
        .hw-table tr:hover td { background: var(--surface-hover); }
        .hw-table .machine { color: #fff; font-weight: 600; }
        .hw-table .badge { display: inline-block; padding: 0.15rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }
        .badge-full { background: rgba(52,211,153,0.15); color: var(--green); }
        .badge-partial { background: rgba(245,158,11,0.15); color: var(--amber); }
        .badge-vm { background: rgba(99,102,241,0.15); color: var(--accent-light); }
        .badge-mobile { background: rgba(34,211,238,0.15); color: var(--cyan); }

        .notes { font-size: 0.8rem; color: var(--text-dim); font-style: italic; }

        .submit-box { background: rgba(99,102,241,0.06); border: 1px solid rgba(99,102,241,0.2); border-radius: 16px; padding: 2rem; margin: 3rem 0; text-align: center; }
        .submit-box h3 { color: var(--accent-light); font-size: 1.1rem; margin-bottom: 0.75rem; }
        .submit-box p { color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem; }
        .submit-box .btn { display: inline-block; padding: 0.6rem 1.5rem; border-radius: 8px; background: var(--accent); color: #fff; font-weight: 600; text-decoration: none; }
        .submit-box .btn:hover { background: var(--accent2); text-decoration: none; }

        footer { text-align: center; padding: 3rem 2rem; color: var(--text-dim); font-size: 0.85rem; border-top: 1px solid var(--border); }
        footer a { color: var(--accent-light); }

        @media (max-width: 768px) {
            .hero { padding: 5rem 1.5rem 2rem; }
            .container { padding: 0 1.25rem 3rem; }
            .hw-table { display: block; overflow-x: auto; }
        }
    </style>
    <script type="application/ld+json">
    {"@context":"https://schema.org","@type":"WebPage","name":"Hardware Compatibility — Alfred Linux","description":"Alfred Linux hardware compatibility list. Tested devices, system requirements, and hardware test reporting.","url":"https://alfredlinux.com/hardware","isPartOf":{"@type":"WebSite","name":"Alfred Linux","url":"https://alfredlinux.com"},"publisher":{"@type":"Organization","name":"GoSiteMe Inc.","url":"https://gositeme.com"}}
    </script>

<?php $currentPage = 'hardware'; include __DIR__ . '/includes/nav.php'; ?>

<div class="hero">
    <h1>Hardware Compatibility</h1>
    <p>Every machine we've tested, every VM we've booted. Honest results — what works, what doesn't, what's untested. Contribute your own below.</p>
</div>

<div class="container">

    <!-- ── Legend ───────────────────────────────────────────── -->
    <div class="status-legend">
        <span><span class="dot dot-full"></span> Full support</span>
        <span><span class="dot dot-partial"></span> Partial / workaround needed</span>
        <span><span class="dot dot-na"></span> Not tested</span>
    </div>

    <!-- ── Virtual Machines ────────────────────────────────── -->
    <div class="section">
        <h2>Virtual Machines</h2>
        <p>VMs are the primary development and testing target. Every RC is booted in QEMU before release.</p>
        <table class="hw-table">
            <thead>
                <tr>
                    <th>Platform</th>
                    <th>Boot</th>
                    <th>Desktop</th>
                    <th>Network</th>
                    <th>Audio</th>
                    <th>Install</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="machine">QEMU/KVM <span class="badge badge-vm">VM</span></td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td class="notes">Primary test environment. RC3→GA all verified.</td>
                </tr>
                <tr>
                    <td class="machine">VirtualBox 7.x <span class="badge badge-vm">VM</span></td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td class="notes">Guest Additions not preinstalled. VBoxSVGA recommended.</td>
                </tr>
                <tr>
                    <td class="machine">VMware Workstation <span class="badge badge-vm">VM</span></td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td class="notes">VMware Tools not preinstalled.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ── Bare Metal ──────────────────────────────────────── -->
    <div class="section">
        <h2>Bare Metal Hardware</h2>
        <p>Tested on real machines via USB boot. Debian Trixie base ensures broad hardware coverage — anything Debian 13 supports, Alfred Linux should too.</p>
        <table class="hw-table">
            <thead>
                <tr>
                    <th>Machine</th>
                    <th>CPU</th>
                    <th>Boot</th>
                    <th>WiFi</th>
                    <th>GPU</th>
                    <th>Audio</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="machine">EU Build Server</td>
                    <td>8-core x86_64, 32 GB</td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td class="notes">N/A (server)</td>
                    <td class="notes">N/A (headless)</td>
                    <td class="notes">N/A</td>
                    <td class="notes">Debian Bookworm host, build + chroot test</td>
                </tr>
                <tr>
                    <td class="machine">Generic x86_64 Laptop</td>
                    <td>Intel i5/i7</td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td class="notes">Intel iGPU. Standard hardware works out of box.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ── Mobile ──────────────────────────────────────────── -->
    <div class="section">
        <h2>Mobile (Android via Termux)</h2>
        <table class="hw-table">
            <thead>
                <tr>
                    <th>Device</th>
                    <th>Method</th>
                    <th>IDE</th>
                    <th>Voice</th>
                    <th>Search</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="machine">Samsung Galaxy S-series <span class="badge badge-mobile">Mobile</span></td>
                    <td>Termux + proot</td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td class="notes">DeX mode gives desktop IDE experience</td>
                </tr>
                <tr>
                    <td class="machine">Generic Android 7+ <span class="badge badge-mobile">Mobile</span></td>
                    <td>Termux + proot</td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td><span class="badge badge-partial">~</span></td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td class="notes">TTS may be slow on older devices</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ── Known Limitations ───────────────────────────────── -->
    <div class="section">
        <h2>Known Limitations</h2>
        <p>Honest accounting of what we haven't tested or know doesn't work yet:</p>
        <table class="hw-table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="machine">NVIDIA GPUs (proprietary driver)</td>
                    <td><span class="badge badge-partial">Partial</span></td>
                    <td>Nouveau open driver works. Proprietary driver not preinstalled — user must install post-boot.</td>
                </tr>
                <tr>
                    <td class="machine">ARM64 / Raspberry Pi</td>
                    <td class="notes">Not yet supported</td>
                    <td>Research phase — see <a href="/forge/commander/alfredlinux.com/src/branch/main/docs/ARM64_BUILD_INVESTIGATION.md">ARM64 investigation</a></td>
                </tr>
                <tr>
                    <td class="machine">Secure Boot</td>
                    <td><span class="badge badge-partial">Partial</span></td>
                    <td>Custom kernel 7.0 is unsigned. Disable Secure Boot in BIOS or use shim.</td>
                </tr>
                <tr>
                    <td class="machine">Broadcom WiFi chipsets</td>
                    <td class="notes">Untested</td>
                    <td>Debian base usually handles these, but we haven't verified specific Broadcom cards.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ── Submit ──────────────────────────────────────────── -->
    <div class="submit-box">
        <h3>Tested on Your Machine?</h3>
        <p>Open an issue on GoForge with your hardware details and test results. Every report expands this list.</p>
        <a href="/forge/commander/alfredlinux.com/issues" class="btn">Submit Test Report</a>
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
