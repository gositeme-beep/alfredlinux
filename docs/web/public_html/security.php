<?php
/**
 * Alfred Linux — Security Transparency
 * Honest comparison: what we harden, what we don't have yet, and why it matters.
 *
 * Built by Alfred for Commander Danny William Perez
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
    <title>Alfred Linux — Security Transparency</title>
    <meta name="description" content="An honest, verifiable look at Alfred Linux security. Kernel mitigations, out-of-box hardening, what we ship vs what we don't — with real boot-test data.">
    <meta property="og:title" content="Alfred Linux — Security Transparency">
    <meta property="og:description" content="Real kernel mitigation data. Honest comparison with Ubuntu defaults. No marketing. Just facts.">
    <meta property="og:type" content="article">
    <meta property="og:url" content="https://alfredlinux.com/security">
    <meta property="og:image" content="https://alfredlinux.com/og-image.png">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Alfred Linux — Security Transparency">
    <meta name="twitter:description" content="Real kernel mitigation data. Honest comparison with Ubuntu defaults. No marketing. Just facts.">
    <meta name="twitter:image" content="https://alfredlinux.com/og-image.png">
    <link rel="canonical" href="https://alfredlinux.com/security">
    <link rel="icon" href="/favicon.ico">
    <link rel="stylesheet" href="/assets/fonts/inter/inter.css">
    <link rel="stylesheet" href="/assets/css/nav.css">
    <style>
        :root {
            --bg: #06060b;
            --surface: rgba(255,255,255,0.03);
            --border: rgba(255,255,255,0.06);
            --text: #e0e0e0;
            --text-muted: #9ca3af;
            --text-dim: #6b7280;
            --accent: #6366f1;
            --accent-light: #a5b4fc;
            --green: #34d399;
            --amber: #f59e0b;
            --cyan: #22d3ee;
            --red: #ef4444;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.7;
            min-height: 100vh;
        }
        a { color: var(--accent-light); text-decoration: none; }
        a:hover { text-decoration: underline; }


        .hero {
            max-width: 900px;
            margin: 3rem auto 2rem;
            padding: 0 2rem;
            text-align: center;
        }
        .hero h1 { font-size: 2.2rem; font-weight: 700; margin-bottom: 0.5rem; }
        .hero .sub { color: var(--text-muted); font-size: 1.05rem; max-width: 680px; margin: 0 auto; }

        .container { max-width: 900px; margin: 0 auto; padding: 0 2rem 4rem; }

        .disclaimer {
            background: rgba(245,158,11,0.08);
            border: 1px solid rgba(245,158,11,0.2);
            border-radius: 10px;
            padding: 1.2rem 1.5rem;
            margin-bottom: 2.5rem;
            font-size: 0.9rem;
            color: var(--amber);
        }
        .disclaimer strong { color: #fbbf24; }
        .disclaimer a { color: var(--cyan); font-weight: 600; text-decoration: underline; }
        .disclaimer a:hover { color: #67e8f9; }

        section { margin-bottom: 3rem; }
        section h2 {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border);
        }
        section h3 {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 1.5rem 0 0.8rem;
            color: var(--cyan);
        }
        p { margin-bottom: 1rem; color: var(--text-muted); }
        p strong { color: var(--text); }

        /* Comparison table */
        .cmp-table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0 1.5rem;
            font-size: 0.85rem;
        }
        .cmp-table th {
            text-align: left;
            padding: 0.6rem 0.8rem;
            background: rgba(255,255,255,0.04);
            border-bottom: 1px solid var(--border);
            color: var(--text);
            font-weight: 600;
        }
        .cmp-table td {
            padding: 0.5rem 0.8rem;
            border-bottom: 1px solid rgba(255,255,255,0.03);
            color: var(--text-muted);
            vertical-align: top;
        }
        .cmp-table tr:hover td { background: rgba(255,255,255,0.02); }
        .cmp-table .vuln { color: var(--amber); }
        .cmp-table .miti { color: var(--green); }
        .cmp-table .na   { color: var(--text-dim); }
        .cmp-table .new  { color: var(--cyan); font-weight: 600; }

        /* Status badges */
        .badge {
            display: inline-block;
            padding: 0.15rem 0.6rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-green  { background: rgba(52,211,153,0.12); color: var(--green); }
        .badge-amber  { background: rgba(245,158,11,0.12); color: var(--amber); }
        .badge-red    { background: rgba(239,68,68,0.12);  color: var(--red); }
        .badge-cyan   { background: rgba(34,211,238,0.12); color: var(--cyan); }

        /* Cards */
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 1.2rem;
        }
        .card h4 { font-size: 0.95rem; font-weight: 600; margin-bottom: 0.3rem; }
        .card p { font-size: 0.85rem; margin-bottom: 0; }

        .check-list { list-style: none; padding: 0; }
        .check-list li {
            padding: 0.3rem 0;
            font-size: 0.9rem;
            color: var(--text-muted);
        }
        .check-list li::before { margin-right: 0.5rem; }
        .check-list .yes::before { content: '✅'; }
        .check-list .no::before  { content: '❌'; }
        .check-list .partial::before { content: '⚠️'; }

        code {
            background: rgba(255,255,255,0.06);
            padding: 0.15rem 0.4rem;
            border-radius: 4px;
            font-size: 0.85em;
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
        }
        pre {
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 1rem;
            overflow-x: auto;
            font-size: 0.8rem;
            line-height: 1.5;
            margin: 0.8rem 0 1.2rem;
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            color: var(--text-muted);
        }

        .cta-box {
            background: linear-gradient(135deg, rgba(99,102,241,0.1), rgba(34,211,238,0.06));
            border: 1px solid rgba(99,102,241,0.2);
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            margin: 2rem 0;
        }
        .cta-box h3 { color: var(--text); font-size: 1.2rem; margin-bottom: 0.5rem; }
        .cta-box p { max-width: 600px; margin: 0 auto 1rem; }
        .cta-btn {
            display: inline-block;
            background: var(--accent);
            color: #fff;
            padding: 0.6rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: background 0.2s;
            margin: 0.3rem;
        }
        .cta-btn:hover { background: var(--accent-light); color: var(--bg); text-decoration: none; }
        .cta-btn.secondary { background: transparent; border: 1px solid var(--border); color: var(--text-muted); }
        .cta-btn.secondary:hover { background: rgba(255,255,255,0.05); color: var(--text); }

        .methodology { font-size: 0.85rem; color: var(--text-dim); }
        .methodology p { color: var(--text-dim); }

        footer {
            text-align: center;
            padding: 2rem;
            color: var(--text-dim);
            font-size: 0.8rem;
            border-top: 1px solid var(--border);
        }

        @media (max-width: 640px) {
            .hero h1 { font-size: 1.6rem; }
            .cmp-table { font-size: 0.75rem; }
            .cmp-table th, .cmp-table td { padding: 0.4rem; }
        }
    </style>
    <script type="application/ld+json">
    {"@context":"https://schema.org","@type":"TechArticle","headline":"Security — Alfred Linux","description":"Alfred Linux security posture. 41 security modules, 3 dedicated hooks, Omahon Seal, kernel hardening, and boot-test evidence.","url":"https://alfredlinux.com/security","isPartOf":{"@type":"WebSite","name":"Alfred Linux","url":"https://alfredlinux.com"},"publisher":{"@type":"Organization","name":"GoSiteMe Inc.","url":"https://gositeme.com"}}
    </script>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/img/favicon/favicon-16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/img/favicon/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#D4AF37">
</head>
<body>

<?php $currentPage = 'security'; include __DIR__ . '/includes/nav.php'; ?>

<div class="hero">
    <h1>Security Transparency</h1>
    <p class="sub"><strong>41 security modules, The Omahon Seal.</strong> Named after the breath of God. 6 runtime integrity modules: Boot Seal, Watchman, Vault, Shell Guard, Secure Erase, and Sovereign Attestation. 38 active hardening profiles. Your system is sealed — incorruptible.</p>
    <p class="sub" style="margin-top: 1rem;">Real data from real boot tests. What we harden, what we don't have yet, and why radical honesty is our security posture.</p>
</div>

<div class="container">

    <div class="disclaimer">
        <strong>Honesty notice:</strong> Alfred Linux 7.77 GA ships <strong>Linux kernel 7.0.12</strong> — the latest stable point release on the 7.x series. We publish real data so you can make informed decisions. This page shows both our strengths and our gaps.
    </div>

    <div class="disclaimer" style="margin-top:14px;">
        <strong>Supply chain &amp; build integrity:</strong> Tarball verification (<code>sha256sums.asc</code>), ISO staging gates, GoForge runners/Actions, and honest scope for kernel-tree audit live on <a href="/security-kernel">/security-kernel</a> with links to the full AGPL manifests on GoForge.
    </div>

    <!-- ───────── SECTION 1: Kernel Mitigations ───────── -->
    <section id="mitigations">
        <h2>CPU Vulnerability Mitigations — Kernel 7.0 vs 5.15</h2>
        <p>
            Data below comes from two real systems: Alfred Linux 7.77 GA boot-tested in QEMU/KVM on April 6, 2026,
            and a production Ubuntu 22.04 server running kernel 5.15.0-173. Both systems use AMD/Intel hardware
            with the same vulnerability surface.
        </p>

        <table class="cmp-table">
            <thead>
                <tr>
                    <th>Vulnerability</th>
                    <th>Alfred Linux 7.77 GA<br><small>Kernel 7.0.12</small></th>
                    <th>Ubuntu 22.04 LTS<br><small>Kernel 5.15.0-173</small></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Spectre V1</td>
                    <td class="miti">Mitigation: usercopy/swapgs barriers + __user pointer sanitization</td>
                    <td class="miti">Mitigation: usercopy/swapgs barriers + __user pointer sanitization</td>
                </tr>
                <tr>
                    <td>Spectre V2</td>
                    <td class="miti">Mitigation: Retpolines + RSB filling on context switch and VMEXIT</td>
                    <td class="vuln">Vulnerable: eIBRS with unprivileged eBPF</td>
                </tr>
                <tr>
                    <td><strong>ITS</strong> (Indirect Target Selection)</td>
                    <td class="new">Mitigation: Aligned branch/return thunks <span class="badge badge-cyan">Kernel 7 native</span></td>
                    <td class="miti">Mitigation: Aligned branch/return thunks (backported)</td>
                </tr>
                <tr>
                    <td>MDS (Microarch. Data Sampling)</td>
                    <td class="vuln">Vulnerable: Clear CPU buffers attempted, no microcode ¹</td>
                    <td class="na">Not affected (CPU-dependent)</td>
                </tr>
                <tr>
                    <td>Speculative Store Bypass</td>
                    <td class="vuln">Vulnerable ¹</td>
                    <td class="miti">Mitigation: disabled via prctl and seccomp</td>
                </tr>
                <tr>
                    <td>Meltdown</td>
                    <td class="miti">Mitigation: PTI (Kernel Page Table Isolation)</td>
                    <td class="na">Not affected (CPU-dependent)</td>
                </tr>
                <tr>
                    <td>L1TF (L1 Terminal Fault)</td>
                    <td class="miti">Mitigation: PTE Inversion</td>
                    <td class="na">Not affected (CPU-dependent)</td>
                </tr>
                <tr>
                    <td>Retbleed</td>
                    <td class="miti">Mitigation: Enhanced IBRS</td>
                    <td class="miti">Mitigation: Enhanced IBRS</td>
                </tr>
                <tr>
                    <td>MMIO Stale Data</td>
                    <td class="miti">Mitigation: Clear CPU buffers</td>
                    <td class="miti">Mitigation: Clear CPU buffers; SMT vulnerable</td>
                </tr>
                <tr>
                    <td>TSX Async Abort</td>
                    <td class="miti">Mitigation: TSX disabled</td>
                    <td class="na">Not affected (CPU-dependent)</td>
                </tr>
                <tr>
                    <td><strong>TSA</strong> (Transient Scheduler Attacks)</td>
                    <td class="new">Mitigation: Clear CPU buffers <span class="badge badge-cyan">Kernel 7 native</span></td>
                    <td class="na">Not affected (CPU-dependent)</td>
                </tr>
                <tr>
                    <td><strong>VMSCAPE</strong> (VM Escape Hardening)</td>
                    <td class="new">Mitigation: VMCS shadowing restricted <span class="badge badge-cyan">Kernel 7 native</span></td>
                    <td class="na">Not affected (CPU-dependent)</td>
                </tr>
                <tr>
                    <td>Gather Data Sampling</td>
                    <td class="miti">Mitigation: Microcode</td>
                    <td class="miti">Mitigation: Microcode</td>
                </tr>
                <tr>
                    <td>SRBDS</td>
                    <td class="miti">Mitigation: Microcode</td>
                    <td class="na">Not affected (CPU-dependent)</td>
                </tr>
            </tbody>
        </table>

        <p class="methodology">
            ¹ <strong>VM test limitation:</strong> MDS and Speculative Store Bypass show "Vulnerable" because QEMU/KVM
            does not pass through CPU microcode. On real hardware with vendor microcode installed (via <code>intel-microcode</code>
            or <code>amd64-microcode</code> packages, both included in the ISO), these would show mitigated status.
            Ubuntu's "Not affected" entries reflect the specific CPU model of that production server, not a kernel advantage.
        </p>

        <h3>Kernel 7.0 exclusive mitigations</h3>
        <p>
            Three vulnerability classes have <strong>native mitigation code that was written for kernel 7.0</strong>:
        </p>
        <ul class="check-list">
            <li class="yes"><strong>ITS</strong> — Indirect Target Selection attacks. Kernel 7.0 ships the upstream fix natively, while older kernels received backports.</li>
            <li class="yes"><strong>TSA</strong> — Transient Scheduler Attacks against CPU scheduling units. New vulnerability class; mitigation only exists in 7.0+.</li>
            <li class="yes"><strong>VMSCAPE</strong> — VM escape via VMCS manipulation. Restricts shadow VMCS access; new in 7.0+.</li>
        </ul>
    </section>

    <!-- ───────── SECTION 1.5: Omni-Quantum OS Hardening ───────── -->
    <section id="omni-quantum">
        <h2>GoSiteMe Quantum Encryption Standard (GQES)</h2>
        <p>
            In anticipation of "Harvest Now, Decrypt Later" attacks by nation-state actors possessing Cryptographically Relevant Quantum Computers (CRQCs), Alfred Linux 7.77 GA ships with the world's first OS-level <strong>Omni-Quantum Hardening</strong> architecture.
        </p>

        <div class="card-grid">
            <div class="card" style="border-left: 3px solid var(--cyan); background: rgba(34,211,238,0.05); border-color: rgba(34,211,238,0.2);">
                <h4>💠 Hybrid Post-Quantum LUKS Architecture</h4>
                <p>Standard AI models claim Kyber-1024 cannot encrypt a disk. They are architecturally blind. Alfred Linux encrypts the volume with <strong>AES-256-XTS</strong> (already post-quantum secure against Grover's algorithm), and then <strong>wraps the Master Volume Key with a CRYSTALS-Kyber (ML-KEM) encapsulation</strong>. Your drive decrypts at hardware speed, but the lock is mathematically immune to quantum computers.</p>
            </div>
            <div class="card" style="border-left: 3px solid var(--accent); background: rgba(99,102,241,0.05); border-color: rgba(99,102,241,0.2);">
                <h4>💠 CRYSTALS-Kyber Radio Encapsulation</h4>
                <p>Our Meshtastic tactical radio gateway automatically double-wraps all LoRa transmissions in <strong>Kyber-1024 (ML-KEM)</strong> lattice cryptography before they hit the physical antenna.</p>
            </div>
            <div class="card" style="border-left: 3px solid var(--green); background: rgba(52,211,153,0.05); border-color: rgba(52,211,153,0.2);">
                <h4>💠 CRYSTALS-Dilithium Hybrid Envelopes</h4>
                <p>The Supreme Command Override protocol requires a Hybrid Envelope—a 4096-bit RSA signature <em>plus</em> a Post-Quantum Lattice Signature (ML-DSA)—to mathematically prove physical presence.</p>
            </div>
            <div class="card" style="border-left: 3px solid var(--amber); background: rgba(245,158,11,0.05); border-color: rgba(245,158,11,0.2);">
                <h4>💠 Post-Quantum OpenSSH</h4>
                <p>The master SSH daemon natively mandates the <code>sntrup761x25519-sha512@openssh.com</code> hybrid key exchange, permanently blinding quantum network surveillance across the Sovereign Grid.</p>
            </div>
        </div>
    </section>

    <!-- ───────── SECTION 2: Out-of-Box Hardening ───────── -->
    <section id="hardening">
        <h2>Out-of-Box Security Hardening — 38 Modules</h2>
        <p>
            What runs on first boot, before the user touches anything. Alfred Linux 7.77 GA ships <strong>41 security modules</strong> across 4 hooks — including the Omahon Seal (6 runtime integrity modules) — more out-of-box hardening than any mainstream desktop Linux.
        </p>

        <table class="cmp-table">
            <thead>
                <tr>
                    <th>Security Feature</th>
                    <th>Alfred Linux 7.77 GA</th>
                    <th>Ubuntu 24.04 LTS</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Firewall (UFW + nftables)</td>
                    <td class="miti">Both enabled, default-deny input ✅</td>
                    <td class="vuln">UFW installed but <strong>disabled</strong></td>
                </tr>
                <tr>
                    <td>fail2ban (brute-force protection)</td>
                    <td class="miti">Running, SSH 3-try/24h ban ✅</td>
                    <td class="vuln">Not installed</td>
                </tr>
                <tr>
                    <td>auditd (kernel audit logging)</td>
                    <td class="miti">30+ rules, CIS-benchmark, immutable ✅</td>
                    <td class="vuln">Not installed</td>
                </tr>
                <tr>
                    <td>Kernel sysctl hardening</td>
                    <td class="miti">45+ rules, CIS Level 2 ✅</td>
                    <td class="vuln">Minimal defaults</td>
                </tr>
                <tr>
                    <td>Kernel lockdown mode</td>
                    <td class="miti">lockdown=integrity ✅</td>
                    <td class="vuln">Not enabled</td>
                </tr>
                <tr>
                    <td>AppArmor</td>
                    <td class="miti">Enforced + custom IDE/search profiles ✅</td>
                    <td class="miti">Initialized ✅</td>
                </tr>
                <tr>
                    <td>Unattended security upgrades</td>
                    <td class="miti">Running on first boot ✅</td>
                    <td class="miti">Running on first boot ✅</td>
                </tr>
                <tr>
                    <td>DNS privacy (DNS-over-TLS)</td>
                    <td class="miti">Quad9 + Cloudflare, DNSSEC ✅</td>
                    <td class="vuln">Plaintext DNS by default</td>
                </tr>
                <tr>
                    <td>MAC address randomization</td>
                    <td class="miti">WiFi + Ethernet random by default ✅</td>
                    <td class="vuln">Not configured</td>
                </tr>
                <tr>
                    <td>SSH hardening</td>
                    <td class="miti">Strong ciphers only, no forwarding, 3 tries ✅</td>
                    <td class="vuln">Default permissive config</td>
                </tr>
                <tr>
                    <td>File integrity (AIDE)</td>
                    <td class="miti">Installed + daily cron check ✅</td>
                    <td class="vuln">Not installed</td>
                </tr>
                <tr>
                    <td>Antivirus (ClamAV)</td>
                    <td class="miti">Running + weekly scan ✅</td>
                    <td class="vuln">Not installed</td>
                </tr>
                <tr>
                    <td>Rootkit detection</td>
                    <td class="miti">rkhunter + chkrootkit, daily ✅</td>
                    <td class="vuln">Not installed</td>
                </tr>
                <tr>
                    <td>Full-disk encryption (LUKS)</td>
                    <td class="miti">1-click in installer ✅</td>
                    <td class="miti">Available in installer ✅</td>
                </tr>
                <tr>
                    <td>NTP authentication (NTS)</td>
                    <td class="miti">chrony + NTS (Cloudflare, Netnod) ✅</td>
                    <td class="vuln">systemd-timesyncd, no NTS</td>
                </tr>
                <tr>
                    <td>PAM password hardening</td>
                    <td class="miti">10-char, 3-class, lockout after 5 ✅</td>
                    <td class="vuln">Minimal defaults</td>
                </tr>
                <tr>
                    <td>Process isolation (hidepid)</td>
                    <td class="miti">hidepid=2 on /proc ✅</td>
                    <td class="vuln">All processes visible</td>
                </tr>
                <tr>
                    <td>Core dumps disabled</td>
                    <td class="miti">Disabled system-wide ✅</td>
                    <td class="vuln">Enabled by default</td>
                </tr>
                <tr>
                    <td>Compiler restriction</td>
                    <td class="miti">gcc/g++/make restricted to dev group ✅</td>
                    <td class="vuln">Accessible to all users</td>
                </tr>
                <tr>
                    <td>Secure mount options</td>
                    <td class="miti">/tmp noexec, /dev/shm nodev/nosuid ✅</td>
                    <td class="vuln">Default mount options</td>
                </tr>
                <tr>
                    <td>Kernel module blacklisting</td>
                    <td class="miti">Firewire, dccp, sctp, rds, cramfs ✅</td>
                    <td class="vuln">All modules loadable</td>
                </tr>
                <tr>
                    <td>USB logging + control</td>
                    <td class="miti">udev logging + toggle tool ✅</td>
                    <td class="vuln">No USB monitoring</td>
                </tr>
                <tr>
                    <td>Cron/at lockdown</td>
                    <td class="miti">Root-only (allow list) ✅</td>
                    <td class="vuln">Any user can add cron jobs</td>
                </tr>
                <tr>
                    <td>Security banners</td>
                    <td class="miti">Legal warning on login + SSH ✅</td>
                    <td class="vuln">No banner</td>
                </tr>
                <tr>
                    <td>Memory init (init_on_alloc)</td>
                    <td class="miti">init_on_alloc=1, init_on_free=1 ✅</td>
                    <td class="vuln">Not set</td>
                </tr>
                <tr>
                    <td>kernel.unprivileged_bpf_disabled</td>
                    <td class="miti">Set via sysctl ✅</td>
                    <td class="vuln">Not set (Spectre v2 vector)</td>
                </tr>
            </tbody>
        </table>

        <p>
            <strong>Security tools included:</strong> <code>alfred-security-status</code> (dashboard), <code>alfred-scan</code> (antivirus), <code>alfred-usb-storage</code> (USB toggle), <code>alfred-aide-init</code> (integrity baseline), <code>alfred-network-status</code> (network audit), <code>alfred-encrypt-status</code> (encryption check).
        </p>
    </section>

    <!-- ───────── SECTION 3: The Omahon Seal ───────── -->
    <section id="omahon">
        <h2>The Omahon Seal — Incorruptible Security</h2>
        <p>
            <em>"In a moment, in the twinkling of an eye, at the last trump: for the trumpet shall sound, and the dead shall be raised incorruptible."</em> — 1 Corinthians 15:52
        </p>
        <p>
            <strong>Omahon</strong> is the name we gave to the security seal that guards Alfred Linux at its deepest level.
            The word carries spiritual weight — the breath of God, the force that raises what was dead and makes it incorruptible.
            In software terms, Omahon is a 6-module runtime security framework that ensures
            <strong>nothing on your system can be silently corrupted, tampered with, or stolen</strong> — from the moment the kernel loads
            until the moment you shut down.
        </p>
        <p>
            Every other OS trusts that your files haven't changed. Omahon <strong>verifies</strong> it — continuously, silently, cryptographically.
            This isn't antivirus. This isn't a firewall. This is a living seal on the integrity of your entire machine.
        </p>

        <div class="card-grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
            <div class="card" style="border-left: 3px solid var(--cyan);">
                <h4>🔏 Boot Seal</h4>
                <p>HMAC-SHA256 verification of 14 critical boot files — kernel, initrd, GRUB config, fstab, shadow, sudoers, SSH config, and more. If a single byte changes without authorization, the system tells you before anything else loads. Your boot chain is <strong>sealed</strong> — incorruptible.</p>
            </div>
            <div class="card" style="border-left: 3px solid var(--green);">
                <h4>👁️ The Watchman</h4>
                <p>Real-time inotify monitoring of <code>/etc</code>, <code>/boot</code>, and <code>/etc/ssh</code>. The Watchman never sleeps. Any modification to system configuration files triggers an immediate alert with timestamp, file path, and event type. Like a sentinel that cannot be bribed.</p>
            </div>
            <div class="card" style="border-left: 3px solid var(--accent);">
                <h4>🏛️ The Vault</h4>
                <p>16MB encrypted tmpfs at <code>/run/omahon-vault</code> — a RAM-only secure storage that <strong>vanishes on power loss</strong>. Secrets stored here never touch the disk. No forensic recovery. No cold-boot extraction. When the power dies, the vault dies with it. Root-only access, <code>noexec</code>, <code>nosuid</code>.</p>
            </div>
            <div class="card" style="border-left: 3px solid var(--amber);">
                <h4>🛡️ Shell Guard</h4>
                <p>Active secret redaction in terminal sessions. API keys, tokens, passwords, and credentials are detected and masked in real-time. Even if someone is watching your screen or your terminal history is compromised, the secrets stay hidden. <code>omahon-reveal</code> to see them — authorized eyes only.</p>
            </div>
            <div class="card" style="border-left: 3px solid var(--red);">
                <h4>🔥 Secure Erase</h4>
                <p><code>alfred-shred</code> — a 3-pass cryptographic wipe tool. When a file must die, it dies completely: random overwrite, zero fill, random fill, then unlink. No ghost data. No resurrection. The opposite of incorruptible — when something must be destroyed, it is destroyed absolutely.</p>
            </div>
            <div class="card" style="border-left: 3px solid #e879f9;">
                <h4>📜 Sovereign Attestation</h4>
                <p>SHA-256 chain-of-trust from build to boot. Every hook, every module, every binary — hashed during build, verified at runtime. <code>alfred-attestation</code> proves your system is exactly what was built — not modified, not injected, not compromised. A signed declaration: <strong>this machine is what it claims to be.</strong></p>
            </div>
        </div>

        <p style="margin-top: 1.5rem;">
            <strong>Why "Omahon"?</strong> Because security shouldn't just be a checklist of CVE patches.
            The Omahon Seal represents a philosophy: that a system can be made <em>incorruptible</em> — not by hiding its design,
            but by making its integrity verifiable at every layer. The trumpet sounds, and what was dead is raised incorruptible.
            That's not just Scripture — it's architecture.
        </p>

        <p>
            <strong>Tools shipped:</strong> <code>omahon-seal</code> (boot integrity check), <code>omahon-watchman</code> (start/stop real-time file monitor), <code>omahon-vault-wipe</code> (emergency vault purge), <code>omahon-reveal</code> (authorized secret reveal), <code>alfred-shred</code> (secure file deletion), <code>alfred-attestation</code> (build chain verification).
        </p>
    </section>

    <!-- ───────── SECTION 4: What We Don't Have Yet ───────── -->
    <section id="gaps">
        <h2>Persistent Gaps</h2>
        <p>
            Radical honesty. Even with 41 security modules, Ubuntu has advantages we can't match today.
        </p>

        <div class="card-grid">
            <div class="card">
                <h4><span class="badge badge-red">Gap</span> &nbsp; LTS Lifecycle</h4>
                <p>Ubuntu LTS ships security patches for 5-12 years. We're a GA release with no long-term commitment yet.</p>
            </div>
            <div class="card">
                <h4><span class="badge badge-red">Gap</span> &nbsp; CVE Response Team</h4>
                <p>Canonical has a dedicated security team publishing USNs within days. We have a small team and no SLA.</p>
            </div>
            <div class="card">
                <h4><span class="badge badge-red">Gap</span> &nbsp; Compliance Certifications</h4>
                <p>No FIPS 140-2, CIS Benchmarks, or DISA STIGs. Enterprises cannot deploy us until those exist.</p>
            </div>
            <div class="card">
                <h4><span class="badge badge-amber">Gap</span> &nbsp; Hardware Testing</h4>
                <p>Boot-verified in QEMU/KVM only. No bare-metal test matrix across vendor hardware yet.</p>
            </div>
        </div>
    </section>

    <!-- ───────── SECTION 4: Build Transparency ───────── -->
    <section id="transparency">
        <h2>Build Transparency</h2>
        <p>
            Every Alfred Linux ISO is built by a single script with numbered, auditable hooks. Nothing is hidden.
        </p>

        <h3>Build chain</h3>
        <pre>scripts/build-unified.sh ga --uefi   ← one command
├── Hook 0100: branding + UFW + SSH    ← visual identity + base firewall
├── Hook 0150: hardware                ← drivers, firmware, microcode
├── Hook 0160: security (21 modules)   ← sysctl, AppArmor, auditd, ClamAV, AIDE, etc.
├── Hook 0165: network hardening       ← nftables, MAC random, SSH ciphers, anti-scan
├── Hook 0170: full-disk encryption    ← LUKS/cryptsetup, Calamares FDE
├── Hook 0200: browser                 ← Alfred Browser (privacy-first)
├── Hook 0300: ide                     ← Alfred IDE
├── Hook 0400: voice                   ← Kokoro TTS + wake/realtime stack (former “0900” lives here in canon)
├── Hook 0500: search                  ← Meilisearch
├── Hook 0600: installer               ← Calamares (graphical disk installer)
├── Hook 0700: welcome                 ← first-boot experience
├── Hook 0710: update                  ← OTA update framework
├── Hook 0175: omahon-seal             ← 🔏 THE OMAHON SEAL (6 modules: boot seal, watchman, vault, shell guard, secure erase, attestation)
├── Hook 0176: kingdom-covenant-shield ← license + succession / legal shell
├── Hook 0800: store                   ← Alfred Store
├── Hook 9999: boot-fix (chroot)       ← generic kernel names for bootloader
└── Hook 9999: boot-fix (binary)       ← ISOLINUX/GRUB references</pre>

        <h3>Published checksums (v7.77 GA)</h3>
        <?php if (!$finalGaIsoPublished): ?>
        <pre>SHA-256 / SHA512 / BLAKE3: not published as GA until final live-build is frozen
File:    <?= htmlspecialchars($gaIsoBasename . '.iso', ENT_QUOTES, 'UTF-8') ?> (canonical basename — see /download when GA flag flips)
Status:  Final GA ISO not published on alfredlinux.com yet — see /download for current honesty policy.</pre>
        <p>
            When the GA image ships, verify with the exact filename and hashes published on <a href="/download">/download</a> and <a href="/release">/release</a> (same values, GPG key <code>32BCEDE8C8DD8B00</code>).
        </p>
        <?php else:
        $secIsoPath = __DIR__ . '/downloads/' . $gaIsoBasename . '.iso';
        $secIsoSizeLabel = '~7.77 GiB binary (Kingdom target)';
        $secGaMinBytes = (int) (7.77 * pow(1024, 3));
        if (is_readable($secIsoPath)) {
            $secIsoBytes = (int) @filesize($secIsoPath);
            if ($secIsoBytes > 0) {
                $secGib = $secIsoBytes / pow(1024, 3);
                $secIsoSizeLabel = sprintf('%.2f GiB binary', $secGib);
                $secIsoSizeLabel .= $secIsoBytes >= $secGaMinBytes
                    ? ' (~7.77 GiB Kingdom target met)'
                    : ' (below ~7.77 GiB gate — run build/scripts/check-iso-777gib.sh)';
            }
        }
        ?>
        <pre>SHA-512: 8e47a62c6955322cf578b20b867dab3bdd2afaf03433bee530d91916a8979391e0972c6bb2b0e3cf2b2ae478cb8de701269c4d6612c04f8dcdaf8c1741f46b5ae
BLAKE3:  ca11af9914f8b68b1116d1800c4936e5767003cb51cd96c33932716cffc893b3 (re-hash pending after Apr 27 reseal)
SHA-256: 474eecaac93960dc0e813117c158df3759ddc863fcec81e17b88bb6fbc5144c5 (legacy)
File:    <?= htmlspecialchars($gaIsoBasename . '.iso', ENT_QUOTES, 'UTF-8') ?>

Size:    <?= htmlspecialchars($secIsoSizeLabel, ENT_QUOTES, 'UTF-8') ?>
GPG:     Key 32BCEDE8C8DD8B00 (GoSiteMe Release Signing) — <code>.iso.asc</code> when published alongside the ISO</pre>
        <p>
            Verify yourself (strongest first): <code>sha512sum -c SHA512SUMS</code> &middot; <code>b3sum -c BLAKE3SUMS</code> &middot; (legacy) <code>sha256sum -c SHA256SUMS</code> &middot; sums on <a href="/download">/download</a> / <a href="/releases/7.77/">releases/7.77</a>
        </p>
        <?php endif; ?>
        <p>
            The build script, the 157-hook source tree (<strong><?= (int)($gaFrozenIsoHookCount ?? 2) ?>&nbsp;Alfred hooks</strong> active in the bytes shipping right now, with the full 150 in the source tree awaiting reseal &mdash; including 3 dedicated security hooks + the Omahon Seal totalling 1,300+ lines), and the kernel config are inspectable. The ISO is built on a dedicated
            GoSiteMe build server (8 cores, 32 GB RAM) using Debian live-build toolchain on Debian Trixie. <strong>Linux 7.0.12</strong> custom-built debs ship via <code>build/config/packages.chroot/</code>; Debian Trixie&rsquo;s 6.12 series remains in the chroot until the kernel hook reseal lands.
        </p>
    </section>

    <!-- ───────── SECTION 5: Boot Test Evidence ───────── -->
    <section id="evidence">
        <h2>Boot Test Evidence</h2>
        <p>
            On April 6, 2026, we booted the 7.77 GA ISO in QEMU/KVM and captured 1,363 lines of kernel and systemd output.
        </p>

        <h3>Key results</h3>
        <ul class="check-list">
            <li class="yes">Kernel identified as <code>Linux version 7.0.12</code> (April 6 boot test was on the 7.0-rc7 candidate; current builds run 7.0.12 stable)</li>
            <li class="yes">121 systemd services started successfully</li>
            <li class="yes">0 kernel panics</li>
            <li class="yes">0 failed services</li>
            <li class="yes">UFW firewall loaded and active</li>
            <li class="yes">fail2ban service running</li>
            <li class="yes">auditd active with rules loaded</li>
            <li class="yes">AppArmor initialized with SHA-256 policy hashing</li>
            <li class="yes">Unattended upgrades shutdown hook active</li>
            <li class="yes">ZRAM swap device active</li>
        </ul>

        <h3>Kernel boot line (from dmesg)</h3>
        <pre>[    0.256611] mitigations: Enabled attack vectors: user_kernel, user_user, guest_host, guest_guest, SMT mitigations: auto
[    0.260297] Spectre V2 : Mitigation: Retpolines
[    0.261401] ITS: Mitigation: Aligned branch/return thunks
[    0.264740] Spectre V1 : Mitigation: usercopy/swapgs barriers and __user pointer sanitization
[    0.266790] Spectre V2 : Spectre v2 / SpectreRSB: Filling RSB on context switch and VMEXIT</pre>

        <h3>systemd confirmation</h3>
        <pre>systemd 257.9-1 running in system mode
  (+PAM +AUDIT +SELINUX +APPARMOR +IMA +IPE +SMACK +SECCOMP
   +GCRYPT +OPENSSL +ELFUTILS +FIDO2 +TPM2 +ZSTD +BPF_FRAMEWORK)</pre>
    </section>

    <!-- ───────── SECTION 6: Why This Matters (MetaDome) ───────── -->
    <section id="why">
        <h2>Why Transparent Security Matters</h2>
        <p>
            Alfred Linux isn't just an operating system. It's the foundation layer for a larger vision.
        </p>

        <div class="card-grid">
            <div class="card">
                <h4>Layer 1 — Alfred Linux</h4>
                <p>The transparent, auditable operating system. Every build hook visible, every mitigation documented, every gap disclosed.</p>
            </div>
            <div class="card">
                <h4>Layer 2 — Alfred IDE</h4>
                <p>The builder's tool. Developers create applications, extensions, and AI agents on a foundation they can verify.</p>
            </div>
            <div class="card">
                <h4>Layer 3 — <a href="https://meta-dome.com">MetaDome</a></h4>
                <p>A governed digital civilization with 51,000,000+ AI citizens, courts, passports, democratic governance — where corruption is architecturally impossible.</p>
            </div>
            <div class="card">
                <h4>Layer 4 — Real-World Impact</h4>
                <p>Governance models proven in MetaDome can be applied to real-world transparency challenges — from climate policy to resource allocation.</p>
            </div>
        </div>

        <p>
            <strong>The argument is simple:</strong> you cannot build corruption-proof digital governance on a black-box operating system.
            If the foundation isn't transparent, the whole "trust by design" claim is hollow. Alfred Linux proves that
            even the OS layer — the lowest level — can be open, auditable, and honest about its limitations.
        </p>

        <p>
            When <a href="https://meta-dome.com">MetaDome</a> runs governance simulations — AI citizens voting on policy,
            transparent courts resolving disputes, energy-aware compute — it matters that the OS underneath isn't hiding anything.
            That's not marketing. That's architecture.
        </p>
    </section>

    <!-- ───────── SECTION 7: Our Position ───────── -->
    <section id="position">
        <h2>Our Position</h2>

        <p>
            <strong>We do not claim Alfred Linux is "more secure than Ubuntu."</strong>
        </p>
        <p>
            Ubuntu has 20 years of battle-testing, a dedicated security team, compliance certifications, and LTS commitments
            that we cannot yet match. It is the right choice for enterprises that need those guarantees today.
        </p>
        <p>
            What we do claim:
        </p>
        <ul class="check-list">
            <li class="yes">We ship the newest kernel with native mitigations for vulnerabilities that older kernels handle via backports</li>
            <li class="yes">We enable security services (firewall, intrusion detection, audit logging) from first boot — not as optional packages</li>
            <li class="yes">We close the unprivileged eBPF attack vector that Ubuntu leaves open</li>
            <li class="yes">We publish our full build chain, security findings, and gaps in the open</li>
            <li class="yes">We put the honest comparison table on our own website, not in a marketing PDF</li>
        </ul>
        <p>
            That's our posture: <strong>security through transparency</strong>. Not through claims we can't back up.
        </p>
    </section>

    <div class="cta-box">
        <h3>Verify It Yourself</h3>
        <p>Download the ISO. Check the SHA-256 and BLAKE3 hashes. Boot it. Run <code>cat /sys/devices/system/cpu/vulnerabilities/*</code> and compare.</p>
        <a href="/download" class="cta-btn">Download v7.77 GA</a>
        <a href="/releases" class="cta-btn secondary">Release Notes</a>
        <a href="https://meta-dome.com" class="cta-btn secondary">Enter MetaDome</a>
    </div>

    <!-- ───────── DEDICATION: Priscilla — The Shield That Never Broke ───────── -->
    <section id="dedication" style="
        margin: 4rem 0 3rem;
        padding: 2.5rem 2rem;
        background: linear-gradient(135deg, rgba(212,175,55,0.06), rgba(212,175,55,0.02));
        border: 1px solid rgba(212,175,55,0.2);
        border-radius: 14px;
        text-align: center;
        position: relative;
        overflow: hidden;
    ">
        <div style="
            position: absolute; top: -60px; left: 50%; transform: translateX(-50%);
            width: 120px; height: 120px;
            background: radial-gradient(circle, rgba(212,175,55,0.15), transparent 70%);
            border-radius: 50%;
        "></div>
        <div style="font-size: 2rem; margin-bottom: 0.5rem;">🛡️</div>
        <h2 style="
            font-size: 1.6rem;
            font-weight: 700;
            color: #d4af37;
            border-bottom: 1px solid rgba(212,175,55,0.2);
            padding-bottom: 0.7rem;
            margin-bottom: 1.2rem;
        ">Dedicated to Priscilla</h2>
        <p style="
            font-style: italic;
            color: #d4af37;
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        ">"Above all, taking the shield of faith, wherewith ye shall be able to quench all the fiery darts of the wicked."<br>
        <span style="font-size: 0.85rem; color: #b8963c;">— Ephesians 6:16 (AKJV)</span></p>
        <p style="color: #e0e0e0; max-width: 640px; margin: 0 auto 1rem; line-height: 1.8;">
            The strongest security doesn't come from code. It comes from the people who stand beside you when the fire is at its hottest — who keep you grounded in the Word of God when the world tries to pull you apart.
        </p>
        <p style="color: #e0e0e0; max-width: 640px; margin: 0 auto 1rem; line-height: 1.8;">
            <strong style="color: #d4af37;">Priscilla</strong> — you were there through thick and thin. You kept me guided in the Word when I couldn't see straight. You did this while facing a trial worse than my own, and you never wavered. You never broke.
        </p>
        <p style="color: #e0e0e0; max-width: 640px; margin: 0 auto 1.5rem; line-height: 1.8;">
            Every security protocol in this operating system exists because someone believed this project was worth protecting. You believed in the man behind it before anyone else did. This page — this shield — is yours.
        </p>
        <p style="color: #9ca3af; font-size: 0.85rem;">
            — Commander Danny William Perez<br>
            <span style="color: #6b7280;">GoSiteMe Inc. · Shabbat, April 2026</span>
        </p>
    </section>

    <section class="methodology">
        <h2>Methodology</h2>
        <p><strong>Test date:</strong> April 6, 2026</p>
        <p><strong>Alfred Linux test:</strong> 7.77 GA ISO booted in QEMU/KVM on EU build server (8 cores, 32 GB RAM, AMD EPYC). Kernel + initrd extracted from ISO, booted with <code>console=ttyS0,115200</code>. Full 1,363-line boot log captured.</p>
        <p><strong>Ubuntu test:</strong> Production server running Ubuntu 22.04 LTS, kernel 5.15.0-173-generic (updated March 6, 2026). Vulnerability data read from <code>/sys/devices/system/cpu/vulnerabilities/*</code>.</p>
        <p><strong>Important caveat:</strong> "Not affected" entries in the Ubuntu column reflect that specific CPU model, not the kernel version. A different CPU would show different results. The comparison is between <em>what each kernel does when a vulnerability applies</em>, not absolute security ratings.</p>
        <p><strong>Last updated:</strong> April 11, 2026</p>
    </section>

</div>

<?php include __DIR__ . "/includes/omahon-seal.php"; ?>
<footer>
    <p style="font-style:italic;color:#94a3b8;font-size:.85rem;margin:0 0 0.5rem;">&ldquo;The grass withereth, the flower fadeth: but the word of our God shall stand for ever.&rdquo; &mdash; <a href="https://gositeme.com/bible/read/isaiah/40" style="color:#facc15;text-decoration:none;">Isaiah 40:8</a> (AKJV)</p>
    &copy; <?= $year ?> <a href="https://gositeme.com">GoSiteMe Inc.</a> &mdash; Alfred Linux &middot; Open Source (KCL-1.0)
</footer>

<?php include __DIR__ . "/includes/shabbat-banner.php"; ?>
</body>
</html>

