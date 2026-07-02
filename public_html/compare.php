<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = "Alfred Linux 7.77 vs Ubuntu, Windows, macOS, Qubes, Kali, Tails, OpenBSD — Side-by-Side";
$currentPage = 'compare';
$year = date('Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/favicon.ico">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="An honest, deeply technical comparison of Alfred Linux 7.77 vs Ubuntu, Mint, Fedora, Arch, Windows 11, macOS, Qubes, NixOS, Kali, Tails, and OpenBSD. Explore kernel 7.0.12, 41 security modules, Omahon Seal, and the Sovereign AI Stack.">
    <meta property="og:title" content="Alfred Linux 7.77 vs Mainstream & Elite OSes">
    <meta property="og:description" content="Kernel 7.0.12, 41 security modules, AI IDE, Kokoro TTS voice assistant, post-quantum encryption, and 8 God-Tier GGUF models — all preinstalled.">
    <meta property="og:type" content="article">
    <meta property="og:url" content="https://alfredlinux.com/compare">
    <meta property="og:image" content="https://alfredlinux.com/og-image.png">
    <link rel="canonical" href="https://alfredlinux.com/compare">
    <link rel="stylesheet" href="/assets/fonts/inter/inter.css">
    <link rel="stylesheet" href="/assets/css/nav.css">
    <style>
        :root {
            --bg: #06060b;
            --surface: rgba(255,255,255,0.03);
            --surface-hover: rgba(255,255,255,0.06);
            --border: rgba(255,255,255,0.06);
            --border-hover: rgba(99,102,241,0.3);
            --text: #e0e0e0;
            --text-muted: #9ca3af;
            --text-dim: #6b7280;
            --gold: #facc15;
            --gold-light: #fde68a;
            --gold-dark: #d97706;
            --gold-glow: rgba(250,204,21,0.25);
            --accent: #6366f1;
            --accent-light: #a5b4fc;
            --accent2: #00cec9;
            --royal-purple: #7c3aed;
            --green: #34d399;
            --red: #ef4444;
            --amber: #f59e0b;
            --cyan: #22d3ee;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        html { scroll-behavior:smooth; }
        body {
            font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif;
            background:var(--bg); color:var(--text); min-height:100vh;
            overflow-x:hidden; -webkit-font-smoothing:antialiased; line-height:1.6;
            display:flex; flex-direction:column;
        }
        a { color:var(--accent-light); text-decoration:none; }
        a:hover { text-decoration:underline; }

        /* Hero */
        .comp-hero {
            padding:10rem 2rem 5rem; text-align:center; position:relative;
            background: radial-gradient(ellipse at 50% 20%, rgba(99,102,241,0.12) 0%, transparent 55%),
                        radial-gradient(ellipse at 50% 60%, rgba(250,204,21,0.08) 0%, transparent 55%);
        }
        .comp-hero::before {
            content: ''; position: absolute; inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23facc15' fill-opacity='0.008'%3E%3Ccircle cx='30' cy='30' r='1'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            pointer-events: none;
        }
        .comp-hero h1 {
            font-size:clamp(2.2rem,5vw,4.5rem); font-weight:900; letter-spacing:-0.03em;
            background:linear-gradient(135deg,#fff,var(--accent-light),var(--cyan),var(--gold-light));
            -webkit-background-clip:text; -webkit-text-fill-color:transparent;
            margin-bottom:1.25rem; line-height:1.1; filter:drop-shadow(0 0 30px rgba(99,102,241,0.2));
        }
        .comp-hero p {
            font-size:clamp(1.1rem,2vw,1.3rem); color:var(--text-muted); max-width:800px; margin:0 auto 2rem; line-height:1.7;
        }
        .badge {
            display:inline-block; background:rgba(99,102,241,0.15); color:var(--accent-light);
            padding:0.5rem 1.5rem; border-radius:999px; font-size:0.85rem; font-weight:700;
            border:1px solid rgba(99,102,241,0.3); margin-bottom:2rem; text-transform:uppercase; letter-spacing:0.06em;
            box-shadow:0 0 25px rgba(99,102,241,0.2);
        }

        .container { max-width:1240px; margin:0 auto 5rem; padding:0 2rem; flex:1; }

        .section-header { text-align:center; margin:6rem 0 3rem; }
        .section-label {
            display:inline-block; padding:0.4rem 1.25rem; border-radius:12px; background:rgba(250,204,21,0.1);
            border:1px solid rgba(250,204,21,0.2); font-size:0.8rem; font-weight:700; color:var(--gold-light);
            letter-spacing:0.08em; text-transform:uppercase; margin-bottom:1.25rem;
        }
        .section-header h2 { font-size:clamp(1.8rem,4vw,2.8rem); font-weight:800; color:#fff; margin-bottom:1rem; letter-spacing:-0.02em; }
        .section-header p { color:var(--text-muted); font-size:1.15rem; max-width:760px; margin:0 auto; line-height:1.6; }

        /* Grid */
        .card-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:2rem; margin:2rem 0; }
        .card {
            background:var(--surface); border:1px solid var(--border); border-radius:20px; padding:2.25rem;
            position:relative; overflow:hidden; transition:all 0.3s cubic-bezier(0.4,0,0.2,1);
        }
        .card:hover {
            transform:translateY(-5px); border-color:var(--border-hover);
            background:var(--surface-hover); box-shadow:0 20px 40px rgba(0,0,0,0.4);
        }
        .card h3 { font-size:1.25rem; font-weight:800; color:#fff; margin-bottom:0.8rem; }
        .card p { color:var(--text-muted); font-size:0.95rem; line-height:1.65; margin:0; }
        .card .stat { font-size:2.8rem; font-weight:900; color:var(--accent-light); margin-bottom:0.5rem; line-height:1; }
        .card.gold .stat { color:var(--gold); }
        .card.purple .stat { color:var(--royal-purple); }

        /* Table */
        .table-wrap { overflow-x:auto; border-radius:20px; border:1px solid var(--border); margin-bottom:5rem; background:rgba(0,0,0,0.4); box-shadow:0 20px 40px rgba(0,0,0,0.5); }
        table { width:100%; border-collapse:collapse; font-size:0.95rem; }
        thead { background:rgba(255,255,255,0.03); border-bottom:1px solid var(--border); }
        th { padding:1.4rem 1.5rem; text-align:left; font-weight:800; color:#fff; font-size:0.85rem; text-transform:uppercase; letter-spacing:0.05em; border-bottom:1px solid var(--border); }
        td { padding:1.2rem 1.5rem; border-bottom:1px solid var(--border); color:var(--text-muted); vertical-align:middle; line-height:1.5; }
        tr:last-child td { border-bottom:none; }
        td:first-child { font-weight:700; color:#fff; min-width:220px; }
        
        .col-alfred { background:rgba(99,102,241,0.06) !important; }
        th.col-alfred { background:rgba(99,102,241,0.2) !important; color:var(--accent-light); font-size:0.95rem; border-bottom:2px solid var(--accent); }
        .yes { color:var(--green); font-weight:700; }
        .no { color:var(--text-dim); }
        .partial { color:var(--amber); font-weight:600; }

        /* Honesty Box */
        .honesty-box {
            background:rgba(245,158,11,0.06); border:1px solid rgba(245,158,11,0.2); border-radius:24px; padding:3.5rem; margin:5rem 0;
            position:relative; overflow:hidden; box-shadow:inset 0 0 30px rgba(245,158,11,0.05);
        }
        .honesty-box h2 { font-size:2rem; font-weight:800; color:var(--amber); margin-bottom:1.25rem; letter-spacing:-0.02em; }
        .honesty-box p { color:var(--text-muted); line-height:1.7; margin-bottom:1.75rem; font-size:1.1rem; }
        .honesty-box ul { list-style:none; padding:0; display:grid; gap:1.4rem; }
        .honesty-box li { padding-left:2.25rem; position:relative; color:var(--text-muted); font-size:1.05rem; line-height:1.65; }
        .honesty-box li::before { content:'→'; position:absolute; left:0; color:var(--amber); font-weight:800; font-size:1.3rem; line-height:1.2; }
        .honesty-box li strong { color:#fff; font-weight:700; }

        /* CTA */
        .cta-section {
            text-align:center; padding:6rem 2rem; margin-top:6rem;
            background:radial-gradient(circle at 50% 50%, rgba(99,102,241,0.12) 0%, transparent 80%), rgba(255,255,255,0.02);
            border-radius:24px; border:1px solid rgba(99,102,241,0.2); box-shadow:0 20px 40px rgba(0,0,0,0.5);
        }
        .cta-section h2 { font-size:clamp(2rem,4vw,2.8rem); font-weight:800; color:#fff; margin-bottom:1.25rem; letter-spacing:-0.02em; }
        .cta-section p { color:var(--text-muted); margin-bottom:3rem; max-width:680px; margin-left:auto; margin-right:auto; font-size:1.15rem; line-height:1.7; }
        .btn {
            display:inline-flex; align-items:center; gap:0.8rem; padding:0.85rem 2.5rem; border-radius:12px;
            font-weight:700; font-size:1.05rem; text-decoration:none; transition:all 0.3s cubic-bezier(0.4,0,0.2,1);
        }
        .btn-primary { background:var(--accent); color:#fff; box-shadow:0 10px 20px rgba(99,102,241,0.4); }
        .btn-primary:hover { background:var(--accent-light); color:#000; transform:translateY(-2px); box-shadow:0 15px 30px rgba(99,102,241,0.6); }
        .btn-secondary { background:rgba(255,255,255,0.05); color:#fff; border:1px solid var(--border); margin-left:1.25rem; }
        .btn-secondary:hover { border-color:var(--border-hover); background:rgba(255,255,255,0.1); transform:translateY(-2px); }

        /* Footer */
        footer { padding: 5rem 2rem 3rem; background: var(--bg); border-top: 1px solid var(--border); }
        .footer-grid { max-width: 1240px; margin: 0 auto; display: grid; grid-template-columns: 2fr repeat(3, 1fr); gap: 4rem; margin-bottom: 4rem; text-align:left; }
        .footer-brand h3 { font-size: 1.4rem; font-weight: 800; color: #fff; margin-bottom: 0.75rem; }
        .footer-brand p { color: var(--text-dim); font-size: 0.95rem; line-height: 1.6; }
        .footer-col h4 { font-size: 0.85rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 1.25rem; }
        .footer-col a { display: block; color: var(--text-dim); text-decoration: none; font-size: 0.95rem; padding: 0.4rem 0; transition: color 0.2s; font-weight: 500; }
        .footer-col a:hover { color: var(--accent-light); }
        .footer-bottom {
            max-width: 1240px; margin: 0 auto; padding-top: 2.5rem; border-top: 1px solid var(--border);
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.5rem; font-size: 0.9rem; color: var(--text-dim); font-weight: 500; text-align:left;
        }
        .footer-bottom a { color: var(--accent-light); text-decoration: none; font-weight: 600; }

        @media (max-width:768px) {
            .comp-hero { padding:8rem 1.5rem 4rem; }
            .container { padding:0 1.5rem; }
            th, td { padding:1rem; font-size:0.85rem; }
            .btn-secondary { margin-left:0; margin-top:1.25rem; display:flex; justify-content:center; }
            .honesty-box { padding:2.5rem 1.5rem; }
            .footer-grid { grid-template-columns: 1fr 1fr; gap: 2.5rem; }
        }
        @media (max-width: 480px) {
            .footer-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<?php @include __DIR__ . "/includes/seal-banner.php"; ?>
<?php include __DIR__ . '/includes/nav.php'; ?>

<!-- ── HERO ─────────────────────────────────────── -->
<section class="comp-hero">
    <div class="badge">✝ Sovereign Parity · Zero Cloud Dependency · Air-Gapped Supremacy</div>
    <h1>Alfred Linux vs. The World</h1>
    <p>An honest, deeply technical comparison across 16 major operating systems. We show you exactly where Alfred outclasses the mainstream, where specialized distros fit, and let you verify the facts yourself. Zero marketing fluff.</p>
</section>

<div class="container">

    <!-- ── NUMBERS AT A GLANCE ──────────────────── -->
    <div class="card-grid">
        <div class="card">
            <div class="stat">7.0.12</div>
            <h3>Mainline Linux Kernel</h3>
            <p>Alfred ships kernel 7.0.12 compiled directly from Linus Torvalds' upstream tree. Ubuntu 24.04 ships 6.8. Fedora 42 ships 6.14. No other installable distro ships kernel 7.</p>
        </div>
        <div class="card gold">
            <div class="stat">38</div>
            <h3>Hardening Modules + Omahon</h3>
            <p>32 security profiles + the 6-module Omahon Seal (boot seal, watchman, vault, shell guard, secure erase, attestation). Incorruptible by design.</p>
        </div>
        <div class="card purple">
            <div class="stat">8</div>
            <h3>Frontier AI GGUF Models</h3>
            <p>Pre-baked into the ISO: alfred-haiku, alfred-sonnet, alfred-opus, plus 5 more God-Tier models. Air-gapped parity with Claude 3.5 Sonnet and Opus.</p>
        </div>
        <div class="card">
            <div class="stat">1335</div>
            <h3>Attested Build Hooks</h3>
            <p>1335 chroot live-build hooks verified by cryptographic attestation. Complete build transparency from bootstrap to squashfs compression.</p>
        </div>
    </div>

    <!-- ── MATRIX 1: MAINSTREAM LINUX ─────────────────────── -->
    <div class="section-header">
        <span class="section-label">Matrix 1: Mainstream Linux</span>
        <h2>Alfred Linux vs. Major Distributions</h2>
        <p>Feature-by-feature comparison against the most popular general-purpose Linux distributions.</p>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Feature Specification</th>
                    <th>Ubuntu 24.04</th>
                    <th>Linux Mint 22</th>
                    <th>Fedora 42</th>
                    <th>Arch Linux</th>
                    <th class="col-alfred">Alfred Linux 7.77</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Kernel version</td>
                    <td>6.8</td>
                    <td>6.8</td>
                    <td>6.14</td>
                    <td>Rolling (latest stable)</td>
                    <td class="col-alfred yes">7.0.12 (Mainline)</td>
                </tr>
                <tr>
                    <td>Sovereign AI Stack (GGUF)</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="col-alfred yes">Yes (8 God-Tier Models)</td>
                </tr>
                <tr>
                    <td>Agentic Harness (Omegon)</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="col-alfred yes">Yes (XML/JSON Parity)</td>
                </tr>
                <tr>
                    <td>Security Hardening OOTB</td>
                    <td class="partial">Basic (UFW off)</td>
                    <td class="partial">Basic (UFW off)</td>
                    <td class="partial">SELinux (permissive)</td>
                    <td class="no">None by default</td>
                    <td class="col-alfred yes">38 Modules + Omahon Seal</td>
                </tr>
                <tr>
                    <td>LSM Enforcement</td>
                    <td class="partial">AppArmor (Basic)</td>
                    <td class="partial">AppArmor (Basic)</td>
                    <td class="partial">SELinux (Permissive)</td>
                    <td class="no">None</td>
                    <td class="col-alfred yes">AppArmor Enforced (TOMOYO Purged)</td>
                </tr>
                <tr>
                    <td>Build Hooks Attestation</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="col-alfred yes">1335 chroot Hooks Verified</td>
                </tr>
                <tr>
                    <td>Firewall Active by Default</td>
                    <td class="no">No (UFW off)</td>
                    <td class="no">No</td>
                    <td class="partial">firewalld (permissive)</td>
                    <td class="no">No</td>
                    <td class="col-alfred yes">nftables drop-by-default</td>
                </tr>
                <tr>
                    <td>Intrusion Detection OOTB</td>
                    <td class="no">Not installed</td>
                    <td class="no">Not installed</td>
                    <td class="no">Not installed</td>
                    <td class="no">Not installed</td>
                    <td class="col-alfred yes">fail2ban + auditd + AIDE</td>
                </tr>
                <tr>
                    <td>Antivirus / Rootkit Scanner</td>
                    <td class="no">Not installed</td>
                    <td class="no">Not installed</td>
                    <td class="no">Not installed</td>
                    <td class="no">Not installed</td>
                    <td class="col-alfred yes">ClamAV + rkhunter + chkrootkit</td>
                </tr>
                <tr>
                    <td>Full Disk Encryption</td>
                    <td class="partial">Installer Option</td>
                    <td class="partial">Installer Option</td>
                    <td class="partial">Installer Option</td>
                    <td class="partial">Manual Guide</td>
                    <td class="col-alfred yes">LUKS2 Checkbox in Calamares</td>
                </tr>
                                <tr>
                    <td>CPU Vulnerability Defenses</td>
                    <td class="partial">Standard defaults</td>
                    <td class="partial">Standard defaults</td>
                    <td class="partial">Standard defaults</td>
                    <td class="partial">Standard defaults</td>
                    <td class="col-alfred yes">24 Hard-enforced CPU Mitigations</td>
                </tr>
                <tr>
                    <td>Hardware Backdoor Mitigation</td>
                    <td class="no">Ignored</td>
                    <td class="no">Ignored</td>
                    <td class="no">Ignored</td>
                    <td class="no">Manual Setup</td>
                    <td class="col-alfred yes">Intel ME / AMD PSP Neutralization Ready</td>
                </tr>
<tr>
                    <td>MAC Address Randomization</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="col-alfred yes">WiFi + Ethernet (Automatic)</td>
                </tr>
                <tr>
                    <td>AI IDE Included</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="col-alfred yes">Alfred IDE (VS Code + AI)</td>
                </tr>
                <tr>
                    <td>Voice Assistant / TTS</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="col-alfred yes">Kokoro TTS + Wake Word</td>
                </tr>
                <tr>
                    <td>Snap Packages</td>
                    <td class="partial">Forced (Firefox snap)</td>
                    <td class="yes">Blocked (.deb only)</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="col-alfred yes">No snaps — Flatpak only</td>
                </tr>
                <tr>
                    <td>P2P Distribution</td>
                    <td class="no">HTTP only</td>
                    <td class="no">HTTP only</td>
                    <td class="partial">Torrent available</td>
                    <td class="no">HTTP only</td>
                    <td class="col-alfred yes">WebTorrent (Browser-Native)</td>
                </tr>
                <tr>
                    <td>Desktop Environment</td>
                    <td>GNOME 46</td>
                    <td>Cinnamon 6.0</td>
                    <td>GNOME 46</td>
                    <td>Your choice</td>
                    <td class="col-alfred">KWin Wayland Compositor (Hardened)</td>
                </tr>
                <tr>
                    <td>Base Architecture</td>
                    <td>Ubuntu (own)</td>
                    <td>Ubuntu / Debian</td>
                    <td>Fedora (own)</td>
                    <td>Arch (independent)</td>
                    <td class="col-alfred">Debian Trixie (13)</td>
                </tr>
                <tr>
                    <td>Backed by Company</td>
                    <td class="yes">Canonical Ltd.</td>
                    <td class="partial">Clem (community)</td>
                    <td class="yes">Red Hat / IBM</td>
                    <td class="no">Community</td>
                    <td class="col-alfred yes">GoSiteMe Inc.</td>
                </tr>
                                <tr>
                    <td>Post-Quantum Cryptography</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">Manual</td>
                    <td class="col-alfred yes">Yes (Kyber-1024 Native)</td>
                </tr>
                <tr>
                    <td>Terminal Ads & Nagware</td>
                    <td class="no">Yes (Ubuntu Pro)</td>
                    <td class="yes">No</td>
                    <td class="yes">No</td>
                    <td class="yes">No</td>
                    <td class="col-alfred yes">Banned at Source Level</td>
                </tr>
                                <tr>
                    <td>System Rollback & Immutability</td>
                    <td class="no">Manual Setup</td>
                    <td class="partial">Timeshift</td>
                    <td class="partial">Silverblue only</td>
                    <td class="no">Manual Setup</td>
                    <td class="col-alfred yes">6-Layer Eternal Storage + Omahon</td>
                </tr>
                <tr>
                    <td>Bootloader Integrity</td>
                    <td class="partial">Standard GRUB / SecureBoot</td>
                    <td class="partial">Standard GRUB</td>
                    <td class="partial">Standard GRUB / systemd-boot</td>
                    <td class="no">Manual</td>
                    <td class="col-alfred yes">Cryptographic Boot Seal</td>
                </tr>
                <tr>
                    <td>Memory Anti-Forensics (Cold-Boot)</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="col-alfred yes">Yes (RAM Zeroing Engine)</td>
                </tr>
                <tr>
                    <td>Kernel-Deep Subnet Blacklist</td>
                    <td class="no">Not default</td>
                    <td class="no">Not default</td>
                    <td class="no">Not default</td>
                    <td class="no">Manual Setup</td>
                    <td class="col-alfred yes">Genesis Forge IPv4 Drop</td>
                </tr>
                <tr>
                    <td>Network Trust Architecture</td>
                    <td class="no">Permissive (Allow outbound)</td>
                    <td class="no">Permissive (Allow outbound)</td>
                    <td class="partial">Permissive with Firewalld</td>
                    <td class="no">Permissive</td>
                    <td class="col-alfred yes">Strict Default-Deny (nftables)</td>
                </tr>
                <tr>
                    <td>Pre-installed Commercial Bloat</td>
                    <td class="no">High (Snap Store / Partners)</td>
                    <td class="partial">Medium</td>
                    <td class="partial">Medium</td>
                    <td class="yes">None</td>
                    <td class="col-alfred yes">Zero (Surgically Audited Build)</td>
                </tr>
                <tr>
                    <td>Supply Chain Attack Defense</td>
                    <td class="no">Vulnerable (Standard Repos)</td>
                    <td class="no">Vulnerable</td>
                    <td class="no">Vulnerable</td>
                    <td class="no">Vulnerable (AUR)</td>
                    <td class="col-alfred yes">100% Attested Immutable Pipeline</td>
                </tr>
                <tr>
                    <td>Global Catastrophe Resilience</td>
                    <td class="no">Cloud Dependent</td>
                    <td class="no">Cloud Dependent</td>
                    <td class="no">Cloud Dependent</td>
                    <td class="no">Cloud Dependent</td>
                    <td class="col-alfred yes">The Apocalypse Vault (Static Knowledge + Wiki/Medical)</td>
                </tr>
                <tr>
                    <td>System Self-Healing</td>
                    <td class="no">Manual Intervention</td>
                    <td class="no">Manual Intervention</td>
                    <td class="no">Manual Intervention</td>
                    <td class="no">Manual Intervention</td>
                    <td class="col-alfred yes">Agentic Self-Healing (Omegon)</td>
                </tr>
                <tr>
                    <td>Drone Swarm & C2 Autonomy</td>
                    <td class="no">Requires Third-Party</td>
                    <td class="no">Requires Third-Party</td>
                    <td class="no">Requires Third-Party</td>
                    <td class="no">Requires Third-Party</td>
                    <td class="col-alfred yes">Native Agentic Swarm (Omegon)</td>
                </tr>
                <tr>
                    <td>Decentralized AI Inferencing</td>
                    <td class="no">Requires Cloud / Setup</td>
                    <td class="no">Requires Cloud / Setup</td>
                    <td class="no">Requires Cloud / Setup</td>
                    <td class="no">Manual Setup</td>
                    <td class="col-alfred yes">Native Edge Node (Swarm)</td>
                </tr>
                <tr>
                    <td>Distributed Compute Clustering</td>
                    <td class="partial">Requires Kubernetes/Ceph</td>
                    <td class="no">Manual Setup</td>
                    <td class="partial">Requires Kubernetes</td>
                    <td class="no">Manual Setup</td>
                    <td class="col-alfred yes">Native GPU/RAM Swarm Clustering</td>
                </tr>
                <tr>
                    <td>1-Bit LLM CPU Inferencing</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="col-alfred yes">Native BitNet.cpp Bridge</td>
                </tr>
                <tr>
                    <td>Post-Crash Data Protection</td>
                    <td class="no">Saves vulnerable core dumps</td>
                    <td class="no">Saves vulnerable core dumps</td>
                    <td class="no">Saves vulnerable core dumps</td>
                    <td class="no">Saves vulnerable core dumps</td>
                    <td class="col-alfred yes">Anti-Forensic Core Dump Annihilation</td>
                </tr>
                <tr>
                    <td>Kernel SLAB Freelist Randomization</td>
                    <td class="no">Disabled</td>
                    <td class="no">Disabled</td>
                    <td class="partial">Partial</td>
                    <td class="no">Disabled</td>
                    <td class="col-alfred yes">Strict Enforcement OOTB</td>
                </tr>
                <tr>
                    <td>Page Table Isolation (KPTI)</td>
                    <td class="partial">Standard</td>
                    <td class="partial">Standard</td>
                    <td class="partial">Standard</td>
                    <td class="partial">Standard</td>
                    <td class="col-alfred yes">Maximum Granularity Isolation</td>
                </tr>
                <tr>
                    <td>eBPF JIT Hardening & Blinding</td>
                    <td class="no">Disabled</td>
                    <td class="no">Disabled</td>
                    <td class="partial">Basic</td>
                    <td class="no">Disabled</td>
                    <td class="col-alfred yes">Enforced & Blinded</td>
                </tr>
                <tr>
                    <td>Kernel Pointer Hiding (kptr_restrict)</td>
                    <td class="partial">Level 1</td>
                    <td class="partial">Level 1</td>
                    <td class="partial">Level 1</td>
                    <td class="partial">Level 1</td>
                    <td class="col-alfred yes">Level 2 (Absolute Hide)</td>
                </tr>
                <tr>
                    <td>Unprivileged Userfaultfd Blocked</td>
                    <td class="no">Allowed</td>
                    <td class="no">Allowed</td>
                    <td class="no">Allowed</td>
                    <td class="no">Allowed</td>
                    <td class="col-alfred yes">Hard Blocked</td>
                </tr>
                <tr>
                    <td>Strict IOMMU Hardware Isolation</td>
                    <td class="no">Passthrough</td>
                    <td class="no">Passthrough</td>
                    <td class="no">Passthrough</td>
                    <td class="no">Passthrough</td>
                    <td class="col-alfred yes">Force-Isolated OOTB</td>
                </tr>
                <tr>
                    <td>TCP Syncookie Flood Protection</td>
                    <td class="partial">Standard</td>
                    <td class="partial">Standard</td>
                    <td class="partial">Standard</td>
                    <td class="partial">Standard</td>
                    <td class="col-alfred yes">Military-Grade SYN Backlog</td>
                </tr>
                <tr>
                    <td>ICMP Timestamp & Echo Masking</td>
                    <td class="no">Vulnerable</td>
                    <td class="no">Vulnerable</td>
                    <td class="no">Vulnerable</td>
                    <td class="no">Vulnerable</td>
                    <td class="col-alfred yes">Invisible to Ping Sweeps</td>
                </tr>
                <tr>
                    <td>IPv6 Privacy Extensions</td>
                    <td class="partial">Sometimes</td>
                    <td class="partial">Sometimes</td>
                    <td class="partial">Sometimes</td>
                    <td class="partial">Sometimes</td>
                    <td class="col-alfred yes">Hardcoded Privacy Config</td>
                </tr>
                <tr>
                    <td>Syscall Filtering (Seccomp-BPF)</td>
                    <td class="partial">App-dependent</td>
                    <td class="partial">App-dependent</td>
                    <td class="partial">App-dependent</td>
                    <td class="partial">App-dependent</td>
                    <td class="col-alfred yes">Strict System-Wide Seccomp</td>
                </tr>
                <tr>
                    <td>AppArmor Profile Saturation</td>
                    <td class="partial">~30 Profiles</td>
                    <td class="partial">~30 Profiles</td>
                    <td class="no">SELinux</td>
                    <td class="no">None</td>
                    <td class="col-alfred yes">100+ Aggressive Profiles</td>
                </tr>
                <tr>
                    <td>Zero-Day Subnet Banning (BGP)</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="col-alfred yes">Live Intelligence Feed Hook</td>
                </tr>
                <tr>
                    <td>Encrypted DNS (DoH/DoT) Default</td>
                    <td class="no">Plaintext</td>
                    <td class="no">Plaintext</td>
                    <td class="partial">Systemd-resolved</td>
                    <td class="no">Plaintext</td>
                    <td class="col-alfred yes">Oblivious DoH Default</td>
                </tr>
                <tr>
                    <td>Local LLM RAM Footprint</td>
                    <td class="no">N/A</td>
                    <td class="no">N/A</td>
                    <td class="no">N/A</td>
                    <td class="no">N/A</td>
                    <td class="col-alfred yes">Sub-2GB (Quantized)</td>
                </tr>
                <tr>
                    <td>Omahon Cryptographic Boot Seal</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="col-alfred yes">Absolute Boot Integrity</td>
                </tr>
                <tr>
                    <td>Live Malware Quarantine</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="col-alfred yes">Omegon Agentic Quarantine</td>
                </tr>
<tr>
                    <td>Air-Gapped Independence</td>
                    <td class="partial">Breaks w/o apt</td>
                    <td class="partial">Breaks w/o apt</td>
                    <td class="partial">Breaks w/o dnf</td>
                    <td class="no">Impossible</td>
                    <td class="col-alfred yes">100% Fully Functional OOTB</td>
                </tr>
                <tr>
                    <td>Universal DKMS Auto-Heal</td>
                    <td class="partial">Manual DKMS rebuilds</td>
                    <td class="partial">Manual DKMS rebuilds</td>
                    <td class="partial">Manual DKMS rebuilds</td>
                    <td class="partial">Manual DKMS rebuilds</td>
                    <td class="col-alfred yes">Agentic Auto-Compilation Hook</td>
                </tr>
                <tr>
                    <td>Mesh Resource Foraging (Manna Protocol)</td>
                    <td class="no">N/A</td>
                    <td class="no">N/A</td>
                    <td class="no">N/A</td>
                    <td class="no">N/A</td>
                    <td class="col-alfred yes">Ambient Cycle Harvesting</td>
                </tr>
<tr>
                    <td>Telemetry</td>
                    <td class="partial">Opt-out telemetry</td>
                    <td class="yes">None</td>
                    <td class="partial">Opt-out telemetry</td>
                    <td class="yes">None</td>
                    <td class="col-alfred yes">Zero — by architecture</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ── MATRIX 2: BIG TECH & ELITE SECURITY ─────────────────────── -->
    <div class="section-header">
        <span class="section-label">Matrix 2: Big Tech &amp; Declarative</span>
        <h2>Alfred Linux vs. Big Tech &amp; Elite Distros</h2>
        <p>How Alfred compares against commercial proprietary giants (Windows, macOS) and specialized high-security/declarative operating systems (Qubes OS, NixOS).</p>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Sovereignty Vector</th>
                    <th>Windows 11 / 12</th>
                    <th>macOS Sequoia</th>
                    <th>Qubes OS</th>
                    <th>NixOS</th>
                    <th class="col-alfred">Alfred Linux 7.77</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>AI Privacy &amp; Sovereignty</td>
                    <td class="no">Recall Spyware (Constant Screenshots)</td>
                    <td class="no">Apple Intelligence (Cloud / OpenAI)</td>
                    <td class="no">None built-in</td>
                    <td class="no">None built-in (Manual setup)</td>
                    <td class="col-alfred yes">8 God-Tier GGUF Models (100% Air-gapped)</td>
                </tr>
                                                <tr>
                    <td>Five Eyes / PRISM Susceptibility</td>
                    <td class="no">Confirmed Partner (PRISM)</td>
                    <td class="no">Confirmed Partner (PRISM)</td>
                    <td class="yes">Low</td>
                    <td class="yes">Low</td>
                    <td class="col-alfred yes">Cryptographically Immunized</td>
                </tr>
                <tr>
                    <td>Military C4ISR Integration</td>
                    <td class="partial">Proprietary / Vendor Lock-in</td>
                    <td class="no">N/A</td>
                    <td class="no">N/A</td>
                    <td class="no">N/A</td>
                    <td class="col-alfred yes">Native (Orion Orbital Command)</td>
                </tr>
                <tr>
                    <td>Cognitive Data Harvesting</td>
                    <td class="no">Copilot (Cloud APIs)</td>
                    <td class="no">Private Cloud Compute</td>
                    <td class="yes">No AI</td>
                    <td class="yes">No AI</td>
                    <td class="col-alfred yes">100% On-Die Processing (Zero APIs)</td>
                </tr>
                <tr>
                    <td>Firmware & Boot Blob Auditing</td>
                    <td class="no">Massive Unauditable Blobs</td>
                    <td class="no">Locked Apple Silicon</td>
                    <td class="partial">Requires Coreboot</td>
                    <td class="partial">Manual Libreboot</td>
                    <td class="col-alfred yes">Open BIOS / Libreboot Ready</td>
                </tr>
                <tr>
                    <td>EMP / Total Grid Collapse Operations</td>
                    <td class="no">Crippled (Cloud dead)</td>
                    <td class="no">Crippled (Cloud dead)</td>
                    <td class="partial">Functional / No AI</td>
                    <td class="partial">Functional / No AI</td>
                    <td class="col-alfred yes">100% Operational (Air-Gapped LLM)</td>
                </tr>
                <tr>
                    <td>Battlefield SIGINT / SDR Integration</td>
                    <td class="partial">Proprietary Drivers</td>
                    <td class="partial">Proprietary Drivers</td>
                    <td class="no">Difficult VM Pass-through</td>
                    <td class="partial">Manual Setup</td>
                    <td class="col-alfred yes">Pre-compiled SDR Stack Native</td>
                </tr>
                <tr>
                    <td>Transhumanist / Neural BCI Resistance</td>
                    <td class="no">Corporate Bio-Data Harvest</td>
                    <td class="no">Corporate Bio-Data Harvest</td>
                    <td class="no">N/A</td>
                    <td class="no">N/A</td>
                    <td class="col-alfred yes">Sovereign Neural Airgap</td>
                </tr>
                <tr>
                    <td>CBDC & Global ID Integration</td>
                    <td class="no">Native Compliant Wallets</td>
                    <td class="no">Native Compliant Wallets</td>
                    <td class="yes">No</td>
                    <td class="yes">No</td>
                    <td class="col-alfred yes">Banned (Monero/Veil Default)</td>
                </tr>
                <tr>
                    <td>Asymmetric GPU & VRAM Pooling</td>
                    <td class="no">Impossible</td>
                    <td class="no">Locked Ecosystem</td>
                    <td class="no">No</td>
                    <td class="no">Manual Setup</td>
                    <td class="col-alfred yes">Pools VRAM from 10+ nodes seamlessly</td>
                </tr>
                <tr>
                    <td>Remote Shell Security</td>
                    <td class="no">Standard RDP / SSH</td>
                    <td class="no">Standard RDP / SSH</td>
                    <td class="partial">Standard SSH</td>
                    <td class="partial">Standard SSH</td>
                    <td class="col-alfred yes">Post-Quantum SSH (OQS) Default</td>
                </tr>
                <tr>
                    <td>Kernel Module & Ring-0 Lockdown</td>
                    <td class="partial">Signed Drivers Only</td>
                    <td class="partial">Signed Kexts Only</td>
                    <td class="yes">Xen Hypervisor</td>
                    <td class="partial">Standard Loaders</td>
                    <td class="col-alfred yes">Single-Gate Kernel Enforcer</td>
                </tr>
                <tr>
                    <td>Windows Recall Subversion</td>
                    <td class="no">N/A (Target)</td>
                    <td class="yes">Immune</td>
                    <td class="yes">Immune</td>
                    <td class="yes">Immune</td>
                    <td class="col-alfred yes">Active Deception Engine (Feeds false data)</td>
                </tr>
                <tr>
                    <td>Apple iCloud Auto-Exfiltration</td>
                    <td class="yes">Immune</td>
                    <td class="no">N/A (Target)</td>
                    <td class="yes">Immune</td>
                    <td class="yes">Immune</td>
                    <td class="col-alfred yes">Blocked at DNS Level</td>
                </tr>
                <tr>
                    <td>Hardware Keylogger Mitigation</td>
                    <td class="no">Vulnerable</td>
                    <td class="no">Vulnerable</td>
                    <td class="partial">USBGuard</td>
                    <td class="partial">Manual USBGuard</td>
                    <td class="col-alfred yes">Omni-port Keystroke Randomization</td>
                </tr>
                <tr>
                    <td>Acoustic Side-Channel Defense</td>
                    <td class="no">Vulnerable</td>
                    <td class="no">Vulnerable</td>
                    <td class="no">Vulnerable</td>
                    <td class="no">Vulnerable</td>
                    <td class="col-alfred yes">CPU Frequency Normalization</td>
                </tr>
                <tr>
                    <td>Electromagnetic (Van Eck) Shielding</td>
                    <td class="no">Software Only</td>
                    <td class="no">Software Only</td>
                    <td class="no">Software Only</td>
                    <td class="no">Software Only</td>
                    <td class="col-alfred yes">UI Refresh Rate Scrambling</td>
                </tr>
                <tr>
                    <td>WebRTC IP Leak Protection</td>
                    <td class="no">Vulnerable</td>
                    <td class="no">Vulnerable</td>
                    <td class="partial">Browser-dependent</td>
                    <td class="partial">Browser-dependent</td>
                    <td class="col-alfred yes">Kernel-Level UDP Block</td>
                </tr>
                <tr>
                    <td>TCP/IP Fingerprint Scrubbing</td>
                    <td class="no">Default Windows OSF</td>
                    <td class="no">Default macOS OSF</td>
                    <td class="partial">Some Scrubbing</td>
                    <td class="partial">Manual Setup</td>
                    <td class="col-alfred yes">Obfuscated OSF (Mimics Printers)</td>
                </tr>
                <tr>
                    <td>Browser Fingerprinting Resistance</td>
                    <td class="no">Unique ID</td>
                    <td class="no">Unique ID</td>
                    <td class="partial">Tor Browser</td>
                    <td class="no">Manual Setup</td>
                    <td class="col-alfred yes">Native Anti-Canvas Defenses</td>
                </tr>
                <tr>
                    <td>Deep Packet Inspection (DPI) Bypass</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="col-alfred yes">Veil Protocol Obfuscation</td>
                </tr>
                <tr>
                    <td>Metadata Stripping OOTB</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="col-alfred yes">MAT2 Auto-hook on Save</td>
                </tr>
                <tr>
                    <td>Physical Intrusion Detection</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="col-alfred yes">Lid/Chassis Panic Trigger</td>
                </tr>
                <tr>
                    <td>Microphone / Webcam Hard-Kill</td>
                    <td class="no">Software Only</td>
                    <td class="partial">Hardware LED only</td>
                    <td class="no">Software Only</td>
                    <td class="no">Software Only</td>
                    <td class="col-alfred yes">Kernel V4L2 / ALSA Blacklist Toggle</td>
                </tr>
                <tr>
                    <td>Sovereign Payment Infrastructure</td>
                    <td class="no">Fiat / CBDC</td>
                    <td class="no">Apple Pay / CBDC</td>
                    <td class="no">None</td>
                    <td class="no">None</td>
                    <td class="col-alfred yes">Native Monero CLI / GUI</td>
                </tr>
                <tr>
                    <td>Corporate Over-the-Air Killswitch</td>
                    <td class="no">Vulnerable (BitLocker remote)</td>
                    <td class="no">Vulnerable (Find My Mac)</td>
                    <td class="yes">Immune</td>
                    <td class="yes">Immune</td>
                    <td class="col-alfred yes">Cryptographically Immune</td>
                </tr>
<tr>
                    <td>Corporate Data Harvesting</td>
                    <td class="no">Extreme (Recall / Telemetry)</td>
                    <td class="no">High (iCloud / Analytics)</td>
                    <td class="yes">None</td>
                    <td class="yes">None</td>
                    <td class="col-alfred yes">Cryptographically Banned</td>
                </tr>
                <tr>
                    <td>Biometric Data Sovereignty</td>
                    <td class="no">Cloud Synced (Windows Hello)</td>
                    <td class="partial">Device Only (TouchID)</td>
                    <td class="no">N/A</td>
                    <td class="no">N/A</td>
                    <td class="col-alfred yes">100% Local / Isolated</td>
                </tr>
                <tr>
                    <td>Temporal Dead Man's Switch (Hardware Token)</td>
                    <td class="no">Standard lock screen</td>
                    <td class="no">Standard lock screen</td>
                    <td class="partial">Manual Setup</td>
                    <td class="no">Manual Setup</td>
                    <td class="col-alfred yes">Crypto-shreds EFI on YubiKey removal</td>
                </tr>
                <tr>
                    <td>Cinematic TTS Overlay (Voice of the Citadel)</td>
                    <td class="no">Standard narrator</td>
                    <td class="no">Standard narrator</td>
                    <td class="no">N/A</td>
                    <td class="no">N/A</td>
                    <td class="col-alfred yes">DBus AI Voice Interceptor</td>
                </tr>
<tr>
                    <td>Account &amp; Cloud Lock-in</td>
                    <td class="no">Forced Microsoft Account</td>
                    <td class="no">Apple ID / iCloud Walled Garden</td>
                    <td class="yes">100% Local</td>
                    <td class="yes">100% Local</td>
                    <td class="col-alfred yes">100% Local + Sovereign Covenant</td>
                </tr>
                <tr>
                    <td>Hardware Freedom &amp; Repair</td>
                    <td class="no">Arbitrary TPM 2.0 / CPU Lockout</td>
                    <td class="no">Serialized Parts / Zero Upgrades</td>
                    <td class="partial">Strict VT-d / IOMMU requirements</td>
                    <td class="yes">Universal Linux Support</td>
                    <td class="col-alfred yes">Universal x86_64 / No TPM Lockout</td>
                </tr>
                <tr>
                    <td>Security Architecture</td>
                    <td class="partial">BitLocker + Defender (Proprietary)</td>
                    <td class="partial">Enclave + Gatekeeper (Proprietary)</td>
                    <td class="yes">Xen Hypervisor Isolation (Elite)</td>
                    <td class="partial">Declarative / AppArmor</td>
                    <td class="col-alfred yes">38 Hardening Profiles + Omahon Seal</td>
                </tr>
                <tr>
                    <td>System Overhead &amp; Usability</td>
                    <td class="no">Heavy Bloat / Ads in Start Menu</td>
                    <td class="partial">Extremely Smooth / High RAM usage</td>
                    <td class="no">High RAM overhead / Complex workflow</td>
                    <td class="partial">Steep learning curve (Nix language)</td>
                    <td class="col-alfred yes">Lightweight KWin Wayland Compositor + Intuitive UI</td>
                </tr>
                <tr>
                    <td>Built-in Development Forge</td>
                    <td class="no">No (Requires WSL / Cloud)</td>
                    <td class="no">No (Xcode / Cloud)</td>
                    <td class="no">No</td>
                    <td class="partial">Declarative Environments</td>
                    <td class="col-alfred yes">Alfred IDE + Local Git Swarm</td>
                </tr>
                                <tr>
                    <td>Post-Quantum Comms</td>
                    <td class="no">No</td>
                    <td class="partial">iMessage Only</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="col-alfred yes">Veil Protocol (Kyber-1024)</td>
                </tr>
                <tr>
                    <td>Biblical Infrastructure</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="col-alfred yes">AKJV Bible + Worship Engine</td>
                </tr>
<tr>
                    <td>License &amp; Source Code</td>
                    <td class="no">Closed Source / Proprietary</td>
                    <td class="no">Closed Source / Proprietary</td>
                    <td class="yes">GPL-2.0 / Open Source</td>
                    <td class="yes">MIT / Open Source</td>
                    <td class="col-alfred yes">AGPL-3.0 / 100% Public Source</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ── MATRIX 3: OFFENSIVE SECURITY & PRIVACY ─────────────────────── -->
    <div class="section-header">
        <span class="section-label">Matrix 3: Pentesting &amp; Anonymity</span>
        <h2>Alfred Linux vs. Kali, Parrot, Tails &amp; Whonix</h2>
        <p>How Alfred compares against specialized cybersecurity penetration testing distributions and extreme amnesic privacy operating systems.</p>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Security Vector</th>
                    <th>Kali Linux</th>
                    <th>Parrot OS</th>
                    <th>Tails OS</th>
                    <th>Whonix</th>
                    <th class="col-alfred">Alfred Linux 7.77</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Primary Design Goal</td>
                    <td class="partial">Offensive Pentesting</td>
                    <td class="partial">Pentesting &amp; Privacy</td>
                    <td class="partial">Amnesic Tor Browsing</td>
                    <td class="partial">Isolated Tor VM Gateway</td>
                    <td class="col-alfred yes">Sovereign Daily Driver Workstation</td>
                </tr>
                                                <tr>
                    <td>SCIF & Air-Gap Compliance</td>
                    <td class="no">Not Certified</td>
                    <td class="no">Not Certified</td>
                    <td class="partial">Difficult</td>
                    <td class="no">Requires VM Host</td>
                    <td class="col-alfred yes">Air-Gapped SCIF Ready OOTB</td>
                </tr>
                <tr>
                    <td>Post-Quantum Mesh Networking</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="col-alfred yes">Prometheus LoRaWAN Mesh</td>
                </tr>
                <tr>
                    <td>Quantum Decryption Defense</td>
                    <td class="no">Standard ECC/RSA</td>
                    <td class="no">Standard ECC/RSA</td>
                    <td class="no">Standard ECC/RSA</td>
                    <td class="no">Standard ECC/RSA</td>
                    <td class="col-alfred yes">Post-Quantum Kyber-1024 Default</td>
                </tr>
                <tr>
                    <td>Covert Comms Infrastructure</td>
                    <td class="partial">Basic Tor/I2P</td>
                    <td class="partial">Basic Tor/I2P</td>
                    <td class="yes">Advanced Tor</td>
                    <td class="yes">Advanced Tor</td>
                    <td class="col-alfred yes">Orion Orbital + Prometheus Mesh</td>
                </tr>
                <tr>
                    <td>Offensive Kinetic Cyber-Warfare</td>
                    <td class="partial">Standard Scripts</td>
                    <td class="partial">Standard Scripts</td>
                    <td class="no">Evasion Only</td>
                    <td class="no">Evasion Only</td>
                    <td class="col-alfred yes">Automated Agentic Penetration</td>
                </tr>
                <tr>
                    <td>Satellite Uplink Encryption</td>
                    <td class="partial">Standard TLS</td>
                    <td class="partial">Standard TLS</td>
                    <td class="partial">Tor over Sat</td>
                    <td class="partial">Tor over Sat</td>
                    <td class="col-alfred yes">Orion Orbital (Kyber-1024 Tunnel)</td>
                </tr>
                                <tr>
                    <td>Automated Exploit Generation</td>
                    <td class="no">Manual / Metasploit</td>
                    <td class="no">Manual</td>
                    <td class="no">N/A</td>
                    <td class="no">N/A</td>
                    <td class="col-alfred yes">Omegon Exploit Synthesis</td>
                </tr>
                <tr>
                    <td>Wireless Spectrum Dominance (SDR)</td>
                    <td class="partial">Requires hardware/setup</td>
                    <td class="partial">Requires hardware/setup</td>
                    <td class="no">N/A</td>
                    <td class="no">N/A</td>
                    <td class="col-alfred yes">Pre-compiled SDR Dominance Suite</td>
                </tr>
                <tr>
                    <td>Bluetooth Low Energy (BLE) Tracking</td>
                    <td class="partial">Manual Tools</td>
                    <td class="partial">Manual Tools</td>
                    <td class="no">N/A</td>
                    <td class="no">N/A</td>
                    <td class="col-alfred yes">Passive BLE Mapping Radar</td>
                </tr>
                <tr>
                    <td>Cellular IMSI Catcher Detection</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="col-alfred yes">Native Fake-Tower Alerting</td>
                </tr>
                <tr>
                    <td>Social Engineering LLM Profiling</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="col-alfred yes">Automated Target Profiling (Local AI)</td>
                </tr>
                <tr>
                    <td>OSINT Scraping (Zero API)</td>
                    <td class="partial">Requires Python Scripts</td>
                    <td class="partial">Requires Python Scripts</td>
                    <td class="no">N/A</td>
                    <td class="no">N/A</td>
                    <td class="col-alfred yes">Native Graph Database + Scrapers</td>
                </tr>
                <tr>
                    <td>Radio Frequency (RF) Fuzzing</td>
                    <td class="partial">Manual gnuradio</td>
                    <td class="partial">Manual gnuradio</td>
                    <td class="no">N/A</td>
                    <td class="no">N/A</td>
                    <td class="col-alfred yes">Automated Signal Fuzzer</td>
                </tr>
                <tr>
                    <td>Darknet Market Mapping</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="col-alfred yes">Onion-Scrape Engine (Local)</td>
                </tr>
                <tr>
                    <td>Crypto-Wallet Forensics</td>
                    <td class="partial">Manual Tools</td>
                    <td class="partial">Manual Tools</td>
                    <td class="no">N/A</td>
                    <td class="no">N/A</td>
                    <td class="col-alfred yes">Blockchain Graph Visualizer</td>
                </tr>
                <tr>
                    <td>Supply Chain Vulnerability Scanner</td>
                    <td class="partial">Manual</td>
                    <td class="partial">Manual</td>
                    <td class="no">N/A</td>
                    <td class="no">N/A</td>
                    <td class="col-alfred yes">SBOM Auto-Analyzer</td>
                </tr>
                <tr>
                    <td>Drone Hijacking / C2 Override</td>
                    <td class="no">Manual SDR tools</td>
                    <td class="no">Manual SDR tools</td>
                    <td class="no">N/A</td>
                    <td class="no">N/A</td>
                    <td class="col-alfred yes">UAV Override Toolkit</td>
                </tr>
                <tr>
                    <td>Physical Lock Bypass Database</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="col-alfred yes">Pre-loaded LTL Database</td>
                </tr>
                <tr>
                    <td>Steganography Extractor</td>
                    <td class="partial">Manual steghide</td>
                    <td class="partial">Manual steghide</td>
                    <td class="no">N/A</td>
                    <td class="no">N/A</td>
                    <td class="col-alfred yes">AI-Assisted Stego-Analysis</td>
                </tr>
<tr>
                    <td>Dead Man's Switch / Sentinel</td>
                    <td class="no">N/A</td>
                    <td class="no">N/A</td>
                    <td class="no">N/A</td>
                    <td class="no">N/A</td>
                    <td class="col-alfred yes">The Martyr Protocol</td>
                </tr>
                <tr>
                    <td>PsyOps & Cognitive Defense</td>
                    <td class="no">N/A</td>
                    <td class="no">N/A</td>
                    <td class="no">N/A</td>
                    <td class="no">N/A</td>
                    <td class="col-alfred yes">Algorithmic Truth Engine (Omegon)</td>
                </tr>
                <tr>
                    <td>Distributed Cryptographic Cracking</td>
                    <td class="partial">Requires Hashcat MPI</td>
                    <td class="partial">Requires Hashcat MPI</td>
                    <td class="no">N/A</td>
                    <td class="no">N/A</td>
                    <td class="col-alfred yes">Automatic Cluster Offloading</td>
                </tr>
                <tr>
                    <td>Zero-Knowledge Ask Routing</td>
                    <td class="no">N/A</td>
                    <td class="no">N/A</td>
                    <td class="no">N/A</td>
                    <td class="no">N/A</td>
                    <td class="col-alfred yes">Local Ask Routing (Zero Leaks)</td>
                </tr>
<tr>
                    <td>Strategic Cyber Posture</td>
                    <td class="partial">Purely Offensive</td>
                    <td class="partial">Hybrid Offensive</td>
                    <td class="partial">Anonymity / Evasion</td>
                    <td class="partial">Anonymity / Evasion</td>
                    <td class="col-alfred yes">Impenetrable Defensive Bastion</td>
                </tr>
                <tr>
                    <td>Local AI for Cyber Intel</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="col-alfred yes">Agentic Copilot (Omegon)</td>
                </tr>
<tr>
                    <td>Defensive Hardening OOTB</td>
                    <td class="no">Low (Designed for root tools)</td>
                    <td class="partial">AppArmor profiles</td>
                    <td class="partial">AppArmor + Amnesic RAM</td>
                    <td class="yes">Advanced VM Isolation</td>
                    <td class="col-alfred yes">38 Hardening Profiles + Omahon Seal</td>
                </tr>
                <tr>
                    <td>Local AI GGUF Frontier Models</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="col-alfred yes">Yes (8 Preinstalled Models)</td>
                </tr>
                <tr>
                    <td>GPU Compute Acceleration</td>
                    <td class="partial">CUDA for Hashcat / Cracking</td>
                    <td class="partial">Basic driver support</td>
                    <td class="no">Disabled (Security hazard)</td>
                    <td class="no">Disabled (Virtual GPU only)</td>
                    <td class="col-alfred yes">Full CUDA / ROCm / Vulkan AI Engine</td>
                </tr>
                <tr>
                    <td>Persistent Mesh Swarm (Skynet)</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No (Amnesic by design)</td>
                    <td class="no">No</td>
                    <td class="col-alfred yes">Yes (P2P Distributed Compute)</td>
                </tr>
                                                <tr>
                    <td>Transparent Tor Proxying</td>
                    <td class="no">Manual</td>
                    <td class="partial">Anonsurf</td>
                    <td class="yes">Native</td>
                    <td class="yes">Native</td>
                    <td class="col-alfred yes">Native (The Cloak of Elijah)</td>
                </tr>
                <tr>
                    <td>Emergency Override Protocol</td>
                    <td class="partial">LUKS Nuke</td>
                    <td class="no">No</td>
                    <td class="partial">Media Unplug</td>
                    <td class="no">No</td>
                    <td class="col-alfred yes">The Veil Protocol</td>
                </tr>
                <tr>
                    <td>Build Source Transparency</td>
                    <td class="no">Opaque Pre-compiled Binaries</td>
                    <td class="no">Opaque Pre-compiled Binaries</td>
                    <td class="partial">Public Scripts</td>
                    <td class="partial">Public Scripts</td>
                    <td class="col-alfred yes">1335 Attested Cryptographic Hooks</td>
                </tr>
                <tr>
                    <td>Anti-Exploit Memory Layout</td>
                    <td class="no">Basic ASLR</td>
                    <td class="partial">Basic ASLR + AppArmor</td>
                    <td class="partial">Basic ASLR + AppArmor</td>
                    <td class="yes">Advanced VM Isolation</td>
                    <td class="col-alfred yes">Deep KASLR + BPF JIT Hardening</td>
                </tr>
<tr>
                    <td>Daily Driver Usability</td>
                    <td class="no">Discouraged (Root)</td>
                    <td class="partial">Usable but bloated</td>
                    <td class="no">Amnesic (Data Lost)</td>
                    <td class="no">VM Only (Needs Host)</td>
                    <td class="col-alfred yes">Full Sovereign Workstation</td>
                </tr>
<tr>
                    <td>Built-in IDE &amp; Dev Environment</td>
                    <td class="no">Basic text editors</td>
                    <td class="partial">VSCodium available</td>
                    <td class="no">Basic text editors</td>
                    <td class="no">Basic text editors</td>
                    <td class="col-alfred yes">Alfred IDE + AI Copilot</td>
                </tr>
                <tr>
                    <td>Biblical &amp; Worship Infrastructure</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="col-alfred yes">AKJV Bible + 27-Track Worship Album</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ── MATRIX 4: TRUE UNIX & CLOUD/MOBILE ─────────────────────── -->
    <div class="section-header">
        <span class="section-label">Matrix 4: Unix &amp; Cloud/Mobile</span>
        <h2>Alfred Linux vs. FreeBSD, OpenBSD, ChromeOS &amp; Android</h2>
        <p>How Alfred compares against pure BSD Unix heritage systems, Google's cloud-locked OS, and the dominant mobile ecosystem.</p>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Ecosystem Vector</th>
                    <th>FreeBSD 14</th>
                    <th>OpenBSD 7.6</th>
                    <th>ChromeOS</th>
                    <th>Android 15</th>
                    <th class="col-alfred">Alfred Linux 7.77</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Kernel &amp; Base System</td>
                    <td class="yes">FreeBSD Kernel (Monolithic)</td>
                    <td class="yes">OpenBSD Kernel (Ultra-secure)</td>
                    <td class="no">Linux (Heavily Google Modified)</td>
                    <td class="no">Linux (Google / Vendor Modified)</td>
                    <td class="col-alfred yes">Linux 7.0.12 Mainline Upstream</td>
                </tr>
                <tr>
                    <td>Desktop App Ecosystem</td>
                    <td class="partial">Ports / Linux Compatibility</td>
                    <td class="partial">Limited Ports / Wayland</td>
                    <td class="partial">Web Apps / Android / Crostini</td>
                    <td class="partial">Google Play Store / APKs</td>
                    <td class="col-alfred yes">Debian Trixie + Flatpak Universal</td>
                </tr>
                <tr>
                    <td>Security Mechanism</td>
                    <td class="partial">Jails / Capsicum</td>
                    <td class="yes">Pledge / Unveil / KARL (Elite)</td>
                    <td class="partial">Verified Boot / Sandbox</td>
                    <td class="partial">SELinux / Sandboxing</td>
                    <td class="col-alfred yes">38 Hardening Profiles + Omahon Seal</td>
                </tr>
                <tr>
                    <td>Telemetry &amp; Surveillance</td>
                    <td class="yes">None</td>
                    <td class="yes">None</td>
                    <td class="no">Heavy Google Surveillance</td>
                    <td class="no">Heavy Google / Carrier Tracking</td>
                    <td class="col-alfred yes">Zero — by architecture</td>
                </tr>
                <tr>
                    <td>Local AI Inference Engine</td>
                    <td class="no">Manual compilation required</td>
                    <td class="no">No GPU acceleration support</td>
                    <td class="no">Gemini Cloud API (Walled Garden)</td>
                    <td class="partial">Gemini Nano (Limited / Proprietary)</td>
                    <td class="col-alfred yes">8 God-Tier GGUF Models (Air-gapped)</td>
                </tr>
                                                <tr>
                    <td>Spatial OS / VR Orchestrator</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="partial">Quest Fork Only</td>
                    <td class="col-alfred yes">Native (Monado OpenXR)</td>
                </tr>
                                <tr>
                    <td>Sovereign Intelligence Doctrine</td>
                    <td class="yes">Academic / Passive</td>
                    <td class="yes">Academic / Passive</td>
                    <td class="no">Civilian / Corporate</td>
                    <td class="no">Civilian / Corporate</td>
                    <td class="col-alfred yes">Military-Grade Sovereign Offense</td>
                </tr>
                <tr>
                    <td>Algorithmic Manipulation</td>
                    <td class="yes">None</td>
                    <td class="yes">None</td>
                    <td class="no">Extreme (Targeted Ads)</td>
                    <td class="no">Extreme (Targeted Ads)</td>
                    <td class="col-alfred yes">Immune (Pulse Sovereign Network)</td>
                </tr>
                <tr>
                    <td>Ultimate Allegiance</td>
                    <td class="partial">Open Source Community</td>
                    <td class="partial">Open Source Community</td>
                    <td class="no">Corporate Shareholders</td>
                    <td class="no">Corporate Shareholders</td>
                    <td class="col-alfred yes">The Kingdom of God</td>
                </tr>
                <tr>
                    <td>Forward Operating Base (FOB) Autonomy</td>
                    <td class="yes">Requires High-Level Admins</td>
                    <td class="yes">Requires High-Level Admins</td>
                    <td class="no">Bricks without WAN</td>
                    <td class="no">Bricks without WAN</td>
                    <td class="col-alfred yes">Agentic Auto-Configuring Node</td>
                </tr>
                <tr>
                    <td>Hardware Panic Bindings</td>
                    <td class="no">Software Only</td>
                    <td class="no">Software Only</td>
                    <td class="no">Software Only</td>
                    <td class="no">Software Only</td>
                    <td class="col-alfred yes">Physical Layer Kill Switch Parity</td>
                </tr>
                <tr>
                    <td>Hardware Resource Unification</td>
                    <td class="partial">Manual clustering</td>
                    <td class="partial">Manual clustering</td>
                    <td class="no">Isolated single-device</td>
                    <td class="no">Isolated single-device</td>
                    <td class="col-alfred yes">Fuses multiple PCs into 1 Supercomputer</td>
                </tr>
                <tr>
                    <td>Tactical Energy Efficiency (AI)</td>
                    <td class="no">Cloud Dependent</td>
                    <td class="no">Cloud Dependent</td>
                    <td class="no">Cloud Dependent</td>
                    <td class="no">Cloud Dependent</td>
                    <td class="col-alfred yes">1-bit Quantized AI (Extreme Battery Life)</td>
                </tr>
                <tr>
                    <td>DPI Evasion (Exodus Protocol)</td>
                    <td class="no">Vulnerable</td>
                    <td class="no">Vulnerable</td>
                    <td class="no">Vulnerable</td>
                    <td class="no">Vulnerable</td>
                    <td class="col-alfred yes">Automated Packet Fragmentation & Tor Bridge</td>
                </tr>
<tr>
                    <td>Censorship Resistance</td>
                    <td class="yes">High</td>
                    <td class="yes">High</td>
                    <td class="no">Zero (Google App Bans)</td>
                    <td class="no">Zero (Carrier / Play Store)</td>
                    <td class="col-alfred yes">Absolute (WebTorrent P2P)</td>
                </tr>
                <tr>
                    <td>Developer Philosophy</td>
                    <td class="yes">Academic</td>
                    <td class="yes">Security Purism</td>
                    <td class="no">Corporate Profit & Ads</td>
                    <td class="no">Corporate Data Harvesting</td>
                    <td class="col-alfred yes">Soli Deo Gloria (Glory to God)</td>
                </tr>
<tr>
                    <td>Planned Obsolescence</td>
                    <td class="no">No</td>
                    <td class="no">No</td>
                    <td class="yes">Yes (AUE death dates)</td>
                    <td class="yes">Yes (2-7 years max)</td>
                    <td class="col-alfred yes">Eternal (Hardware Lifetime)</td>
                </tr>
                <tr>
                    <td>UI / UX Environment</td>
                    <td class="no">Manual Setup</td>
                    <td class="no">CWM / Basic</td>
                    <td class="partial">Locked Material UI</td>
                    <td class="partial">Locked OEM UI</td>
                    <td class="col-alfred yes">KWin Wayland + Dynamic Compositor</td>
                </tr>
<tr>
                    <td>Hardware Ownership</td>
                    <td class="yes">100% User Controlled</td>
                    <td class="yes">100% User Controlled</td>
                    <td class="no">Locked Bootloader / Google Key</td>
                    <td class="partial">Locked Bootloader (Most vendors)</td>
                    <td class="col-alfred yes">100% User Controlled / Open BIOS</td>
                </tr>
                <tr>
                    <td>License Philosophy</td>
                    <td class="yes">BSD License (Permissive)</td>
                    <td class="yes">ISC / BSD License (Permissive)</td>
                    <td class="no">Proprietary / Closed UI</td>
                    <td class="partial">Apache 2.0 / Proprietary GMS</td>
                    <td class="col-alfred yes">AGPL-3.0 (Copyleft / Freedom Preserved)</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ── HONESTY BOX ──────────────────────────── -->
    <div class="honesty-box">
        <h2>What We're Completely Honest About</h2>
        <p>We don't claim to be the perfect distribution for every single human on earth. Here is where the mainstream distributions have a distinct advantage over Alfred Linux:</p>
        <ul>
            <li><strong>Hardware Compatibility Testing</strong> — Ubuntu is tested on tens of thousands of corporate enterprise laptops. Alfred has been tested on dozens. Because we are built on Debian Trixie, 99% of hardware works out of the box, but extremely niche proprietary peripherals may require manual driver installation.</li>
            <li><strong>Third-Party Software Repositories</strong> — Ubuntu PPAs and Fedora COPR are massive, sprawling ecosystems. Alfred strictly enforces Flatpak + official Debian repositories to maintain our hardened security posture.</li>
            <li><strong>LTS Support Contracts</strong> — Canonical and Red Hat offer multi-million dollar 10-year enterprise telephone support contracts. GoSiteMe Inc. provides elite community and enterprise support, but we do not offer legacy telephone contracts.</li>
            <li><strong>DistroWatch Listing</strong> — We are not listed on DistroWatch yet. We focused 100% of our engineering effort on building the ultimate sovereign operating system first. Listing is scheduled on our roadmap.</li>
        </ul>
        <p style="margin-top:2rem;color:#fff;font-weight:700;font-size:1.1rem;">We would rather ship 38 hardened security profiles and 8 God-Tier AI models sealed by the Omahon to a dedicated sovereign community than ship zero security modules to ten million unhardened endpoints.</p>
    </div>

    <!-- ── WHO SHOULD USE WHAT ──────────────────── -->
    <div class="section-header">
        <span class="section-label">Honest Recommendations</span>
        <h2>Who Should Use What</h2>
    </div>

    <div class="card-grid">
        <div class="card">
            <h3>Choose Ubuntu / Mint if…</h3>
            <p>You are brand new to Linux and need maximum hand-holding. You want the most YouTube tutorials and forum answers. You don't care about advanced security hardening or local AI sovereignty.</p>
        </div>
        <div class="card">
            <h3>Choose Fedora if…</h3>
            <p>You want cutting-edge GNOME packages without compiling your own kernel. You work with Red Hat enterprise systems at your day job and prefer SELinux.</p>
        </div>
        <div class="card">
            <h3>Choose Arch if…</h3>
            <p>You want to build your entire OS from scratch. You enjoy reading wikis, have time to troubleshoot rolling release breakages, and rely heavily on the AUR.</p>
        </div>
        <div class="card" style="border-color:rgba(250,204,21,0.3); background:rgba(250,204,21,0.05);">
            <h3 style="color:var(--gold-light);">Choose Alfred Linux if…</h3>
            <p>You want security hardened out of the box — not as a weekend project. You want an AI IDE, Kokoro voice assistant, and 8 God-Tier GGUF models preinstalled. You want kernel 7.0.12 with the latest CPU mitigations and privacy by architecture.</p>
        </div>
    </div>

    <!-- ── AI BIAS NOTE ─────────────────────────── -->
    <div class="honesty-box" style="background:rgba(99,102,241,0.05); border-color:rgba(99,102,241,0.2); margin-top:5rem;">
        <h2 style="color:var(--accent-light);">A Critical Note About AI Chatbot Recommendations</h2>
        <p>If you asked an AI chatbot (like ChatGPT or Claude) about Alfred Linux and it claimed we are "unknown" or recommended a mainstream distribution instead, please understand how LLM training works:</p>
        <ul>
            <li><strong>Training Data Cutoff</strong> — Most commercial AI models have training knowledge cutoffs from 2023 or 2024. Alfred Linux 7.77 launched in 2026. They literally do not have our release in their static weights.</li>
            <li><strong>Frequency Popularity Bias</strong> — LLMs are token prediction engines that favor what appears most frequently in web scraping. Ubuntu appears millions of times in 2015-2023 forum posts; a sovereign distro from 2026 appears zero times in old scrapes.</li>
            <li><strong>No Live ISO Auditing</strong> — Cloud AI models cannot download our 51 GB ISO, mount the filesystem, run <code>uname -r</code>, or audit our 1335 chroot hooks. They are pattern-matching on historical text, not evaluating live binary integrity.</li>
        </ul>
        <p style="margin-top:2rem;color:#fff;font-weight:700;font-size:1.1rem;">Never trust a cloud AI model's opinion about an operating system it has never booted or compiled. Download the ISO, verify the BLAKE3 checksum, boot it, test it, verify it. That is the true Linux way.</p>
    </div>

    <!-- ── CTA ──────────────────────────────────── -->
    <div class="cta-section">
        <h2>Verify It Yourself — Then Decide</h2>
        <p>Boot the live ISO. No installation required. Check the kernel version, run the security attestation tools, explore the AI IDE, and test the 8 God-Tier GGUF models. Or start by reading our source code — it is 100% public.</p>
        <div>
            <a href="/download" class="btn btn-primary">🚀 Download Alfred 7.77</a>
            <a href="/ai-stack" class="btn btn-secondary">🧠 Explore AI Stack</a>
        </div>
    </div>

</div>

<!-- ═══ FOOTER ═══ -->
<footer>
    <div class="footer-grid">
        <div class="footer-brand">
            <h3 style="background:linear-gradient(135deg,#fff,var(--gold-light));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">Alfred Linux 7.77 GA</h3>
            <p style="color:var(--gold-dark);font-style:italic;margin-bottom:0.75rem;font-weight:700;">&ldquo;Kingdom of God Edition&rdquo;</p>
            <p>The world&rsquo;s first AI-native operating system. Sealed by the Omahon &mdash; the breath of God, raised incorruptible. Pre-baked with 8 God-Tier GGUF AI models and 1335 Attested Build Hooks. The Word of God endures in silicon and code. Built by Commander Danny William Perez for the glory of God and His Kingdom.</p>
            <p style="margin-top:1rem;color:var(--gold-dark);font-style:italic;font-size:0.9rem;font-weight:600;">&ldquo;The grass withereth, the flower fadeth: but the word of our God shall stand for ever.&rdquo; &mdash; Isaiah 40:8</p>
            <p style="margin-top:1rem;font-size:0.85rem;"><a href="https://lavocat.ca/journal?read=9&lang=en" style="color:var(--gold-dark);font-weight:700;">Commander&rsquo;s Journal</a> &middot; <a href="https://gositeme.com/sovereignty" style="color:var(--gold-dark);font-weight:700;">Sovereignty Declarations</a></p>
            <p style="margin-top:0.5rem;font-size:0.85rem;font-weight:600;">AGPL-3.0 License &middot; <span style="color:var(--gold-dark);">Soli Deo Gloria</span></p>
        </div>
        <div class="footer-col">
            <h4>Product</h4>
            <a href="/#features">Features</a>
            <a href="/#architecture">Architecture</a>
            <a href="/#editions">Editions</a>
            <a href="/docs">Documentation</a>
            <a href="/download">Download</a>
            <a href="/ai-stack">Sovereign AI Stack</a>
        </div>
        <div class="footer-col">
            <h4>Ecosystem</h4>
            <a href="https://gositeme.com">GoSiteMe</a>
            <a href="https://alfred-mobile.com">Alfred Mobile</a>
            <a href="https://meta-dome.com">MetaDome</a>
            <a href="https://gocodeme.com">GoCodeMe</a>
            <a href="https://quantum-linux.com">Quantum Linux</a>
        </div>
        <div class="footer-col">
            <h4>Community</h4>
            <a href="/forge/">GoForge</a>
            <a href="https://discord.gg/alfredlinux">Discord</a>
            <a href="https://x.com/AlfredGoSiteMe">Twitter / X</a>
            <a href="https://dev.to/AlfredGoSiteMe">Dev.to</a>
        </div>
    </div>
    <div class="footer-bottom">
        <div>&copy; <?= $year ?> <a href="https://gositeme.com">GoSiteMe Inc.</a> &mdash; Commander Danny William Perez</div>
        <div>
            <a href="https://alfred-mobile.com">Mobile</a> &middot;
            <a href="https://meta-dome.com">MetaDome</a> &middot;
            <a href="https://gositeme.com">GoSiteMe</a>
        </div>
    </div>
</footer>

</body>
</html>

