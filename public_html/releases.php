<?php
/**
 * Alfred Linux — Release Notes
 * Changelog and release history
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
    <title>Alfred Linux — Release Notes</title>
    <meta name="description" content="Release notes and changelog for Alfred Linux. Track every build from RC1 to the latest kernel 7.0 release.">
    <meta property="og:title" content="Alfred Linux — Release Notes">
    <meta property="og:description" content="Full changelog for Alfred Linux — the first distro shipping Linux kernel 7.0.">
    <meta property="og:type" content="article">
    <meta property="og:url" content="https://alfredlinux.com/releases">
    <meta property="og:image" content="https://alfredlinux.com/og-image.png">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Alfred Linux — Release Notes">
    <meta name="twitter:description" content="Full changelog for Alfred Linux — the first distro shipping Linux kernel 7.0.">
    <meta name="twitter:image" content="https://alfredlinux.com/og-image.png">
    <link rel="canonical" href="https://alfredlinux.com/releases">
    <link rel="icon" href="/favicon.ico">
    <link rel="stylesheet" href="/assets/fonts/inter/inter.css">
    <link rel="stylesheet" href="/assets/css/nav.css">
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Article",
        "headline": "Alfred Linux Release Notes — From RC1 to 4.0 GA",
        "description": "Complete changelog for Alfred Linux. 11 ISOs built, from Debian Bookworm to Trixie, from kernel 6.1 to custom-compiled 7.0. 4.0 GA with Omahon Seal. Every build tracked with SHA-256 checksums and GPG signatures.",
        "author": { "@type": "Organization", "name": "GoSiteMe Inc.", "url": "https://gositeme.com" },
        "publisher": { "@type": "Organization", "name": "GoSiteMe Inc.", "url": "https://gositeme.com" },
        "datePublished": "2026-04-06",
        "dateModified": "2026-04-07",
        "mainEntityOfPage": "https://alfredlinux.com/releases",
        "about": {
            "@type": "SoftwareApplication",
            "name": "Alfred Linux",
            "operatingSystem": "Linux",
            "applicationCategory": "OperatingSystem",
            "softwareVersion": "4.0 GA"
        }
    }
    </script>
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
        .hero .sub { color: var(--text-muted); font-size: 1.05rem; }

        .container { max-width: 900px; margin: 0 auto; padding: 0 2rem 4rem; }

        .release {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .release-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        .release-header h2 { font-size: 1.5rem; font-weight: 700; }
        .badge {
            display: inline-block;
            padding: 0.2rem 0.7rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .badge-latest { background: rgba(34,211,238,0.15); color: var(--cyan); border: 1px solid rgba(34,211,238,0.3); }
        .badge-kernel7 { background: rgba(251,191,36,0.15); color: var(--amber); border: 1px solid rgba(251,191,36,0.3); }
        .badge-security { background: rgba(52,211,153,0.15); color: var(--green); border: 1px solid rgba(52,211,153,0.3); }
        .badge-ascension { background: rgba(212,175,55,0.18); color: #D4AF37; border: 1px solid rgba(212,175,55,0.45); animation: ascension-glow 2.5s ease-in-out infinite alternate; }
        @keyframes ascension-glow { from { box-shadow: 0 0 4px rgba(212,175,55,0.2); } to { box-shadow: 0 0 12px rgba(212,175,55,0.5); } }
        .badge-previous { background: rgba(255,255,255,0.05); color: var(--text-dim); border: 1px solid var(--border); }
        .release-date { color: var(--text-dim); font-size: 0.85rem; }

        .release h3 { font-size: 1.05rem; font-weight: 600; color: var(--accent-light); margin: 1.2rem 0 0.5rem; }
        .release ul { list-style: none; padding: 0; }
        .release li {
            padding: 0.35rem 0 0.35rem 1.5rem;
            position: relative;
            color: var(--text-muted);
            font-size: 0.92rem;
        }
        .release li::before {
            content: '›';
            position: absolute;
            left: 0.4rem;
            color: var(--accent);
            font-weight: 700;
        }
        .release li strong { color: var(--text); }

        .checksum {
            background: rgba(0,0,0,0.3);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 0.8rem 1rem;
            margin-top: 1rem;
            font-family: 'SF Mono', 'Fira Code', monospace;
            font-size: 0.78rem;
            color: var(--text-dim);
            word-break: break-all;
        }
        .checksum span { color: var(--green); }

        .dl-btn {
            display: inline-block;
            margin-top: 1rem;
            padding: 0.6rem 1.4rem;
            background: var(--accent);
            color: #fff;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: background 0.2s;
        }
        .dl-btn:hover { background: var(--accent-light); color: var(--bg); text-decoration: none; }

        footer {
            text-align: center;
            padding: 2rem;
            color: var(--text-dim);
            font-size: 0.8rem;
            border-top: 1px solid var(--border);
        }

        @media (max-width: 640px) {
            .hero h1 { font-size: 1.6rem; }
            .release { padding: 1.2rem; }
        }
    </style>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/img/favicon/favicon-16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/img/favicon/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#D4AF37">
</head>
<body>

<?php $currentPage = 'releases'; include __DIR__ . '/includes/nav.php'; ?>

<div class="hero">
    <h1>Release Notes</h1>
    <p class="sub">Every build. Every kernel. Every hardening pass.</p>
</div>

<div class="container">

    <?php if (!$finalGaIsoPublished): ?>
    <div style="background:rgba(212,175,55,0.08);border:1px solid rgba(212,175,55,0.25);border-radius:12px;padding:1rem 1.25rem;margin-bottom:2rem;color:#c8c8d8;font-size:0.92rem;line-height:1.55;">
        <strong style="color:#D4AF37;">GA ISO:</strong> Release notes describe the product line; the <strong>frozen public ISO + torrent + checksums</strong> ship together when the final build is published. Status: <a href="/download" style="color:#00D4FF;">/download</a>.
    </div>
    <?php endif; ?>

    <div style="background:rgba(0,206,209,0.06);border:1px solid rgba(0,206,209,0.22);border-radius:12px;padding:0.85rem 1.1rem;margin-bottom:1.5rem;color:#a8c8d0;font-size:0.88rem;line-height:1.55;">
        <strong style="color:#00cec9;">Supply chain &amp; build integrity:</strong> Kernel tarball verification (<code>sha256sums.asc</code>), ISO hook gates, GoForge Actions/runners, and honest audit scope — <a href="/security-kernel" style="color:#00D4FF;font-weight:600;">/security-kernel</a> (links to AGPL manifests on GoForge).
    </div>

    <details id="hook-count-legend-releases" style="margin-bottom:2rem;border:1px solid rgba(99,102,241,0.35);border-radius:12px;padding:0.75rem 1.1rem;background:rgba(99,102,241,0.07);color:#c8c8d8;font-size:0.9rem;line-height:1.55;">
        <summary style="cursor:pointer;color:#a5b4fc;font-weight:700;">Hook counts: v7.77 source tree (150) vs v7.77 frozen ISO (~2 of 150) vs RC milestones — tap to expand / collapse</summary>
        <p style="margin:0.75rem 0 0;"><strong>v7.77 Kingdom of God Edition</strong> = <strong>42</strong> build hooks on the current <code>ga</code> profile. <strong>v7.77 GA</strong> in the timeline below = <strong>17</strong> hooks (accurate for that frozen release). <strong>RC4–RC8</strong> lines show 10 / 12 / 13 / 16 / … for <em>that week&rsquo;s ISO only</em> &mdash; the ladder we climbed, not a contradiction of 42.</p>
        <p style="margin:0.5rem 0 0;">Longer explanation: <a href="/docs#hook-count-legend" style="color:#00D4FF;">Technical docs &rarr; Hook-count legend</a> &middot; <a href="/docs#bible-tongues-truth" style="color:#00D4FF;">Bible tongues metadata</a> (<code>bible_tongues</code> in <code>api/version.json</code> tracks hook 0292&rsquo;s <code>languages.conf</code> rows &mdash; <strong>48</strong> today).</p>
    </details>

    <!-- v7.77 GA — ASCENSION EDITION (CURRENT) -->
    <div class="release" id="v777-ascension" style="border-color:rgba(212,175,55,0.35);background:linear-gradient(135deg,rgba(212,175,55,0.04) 0%,rgba(99,102,241,0.04) 100%);">
        <div class="release-header">
            <h2>v7.77 GA &mdash; &ldquo;Ascension Edition&rdquo;</h2>
            <span class="badge badge-latest">Latest</span>
            <span class="badge badge-ascension">Ascension Protocol</span>
            <span class="badge badge-kernel7">Kernel 7.0</span>
        </div>
        <div class="release-date">May 31, 2026 &mdash; <strong>The ultimate sovereign architecture. Apocalypse-ready. Mathematically perfect.</strong></div>

        <h3>&#x2728; Ascension Protocol &mdash; What&rsquo;s New</h3>
        <ul>
            <li><strong>1,335 sacred build hooks</strong> &mdash; mathematically perfect build pipeline. The most comprehensive live-build hook tree ever assembled.</li>
            <li><strong>20+ Ascension Protocol architectures injected</strong> &mdash; every domain of sovereign computing, from quantum to genomics, woven into a single OS.</li>
            <li><strong>44 GB Apocalypse Vault</strong> &mdash; Llama-3 70B + Stable Diffusion XL AI models embedded directly into the ISO. Fully offline sovereign intelligence.</li>
            <li><strong>Spatial Reality Engine</strong> &mdash; VR/XR integration, gesture controls, and immersive spatial computing built into the desktop experience.</li>
            <li><strong>Brain-Computer Interface</strong> &mdash; BCI tooling and drivers for neural input devices, enabling thought-driven computing.</li>
            <li><strong>Quantum Computing</strong> &mdash; Qiskit, Cirq, and quantum simulation frameworks pre-installed for post-classical computation.</li>
            <li><strong>Genomics &amp; Bioinformatics</strong> &mdash; BLAST, samtools, and bioinformatics pipelines for DNA/RNA sequence analysis.</li>
            <li><strong>Software Defined Radio</strong> &mdash; GNU Radio, SDR++, and signal processing tools for full-spectrum RF sovereignty.</li>
            <li><strong>LoRaWAN Mesh Networking</strong> &mdash; ChirpStack, Meshtastic, and long-range mesh communication for off-grid connectivity.</li>
            <li><strong>Satellite Tracking</strong> &mdash; GPredict, SatNOGS, and orbital prediction for real-time satellite reconnaissance.</li>
            <li><strong>Digital Forensics</strong> &mdash; Autopsy, Volatility, and Sleuth Kit for full-spectrum digital investigation.</li>
            <li><strong>Steganography</strong> &mdash; OpenStego, Steghide, and covert data-hiding tools for invisible communication.</li>
            <li><strong>Drone Control</strong> &mdash; MAVLink, QGroundControl, and ArduPilot for autonomous UAV operations.</li>
            <li><strong>HAM Radio</strong> &mdash; WSJT-X, Fldigi, and amateur radio digital modes for global off-grid communication.</li>
            <li><strong>Astronomy</strong> &mdash; Stellarium, KStars, and INDI telescope control for celestial observation.</li>
            <li><strong>3D Printing</strong> &mdash; PrusaSlicer, Cura, and FreeCAD for sovereign manufacturing.</li>
            <li><strong>Robotics</strong> &mdash; ROS 2 (Robot Operating System), Gazebo simulation, and hardware interfaces for autonomous systems.</li>
        </ul>

        <h3>Inherited from Kingdom of God Edition</h3>
        <ul>
            <li><strong>AKJV Bible</strong> &mdash; 94 books, 39,482 verses, <code>alfred-bible</code> CLI</li>
            <li><strong>27-Track Worship Album</strong> &mdash; &ldquo;Jesus Christ The Light Our Universe&rdquo; by Elyon Neshama</li>
            <li><strong>New Jerusalem Spatial Desktop</strong> &mdash; KWin Wayland 3D Cube compositor</li>
            <li><strong>Prophetic Vision</strong> &mdash; ComfyUI + Flux offline GPU generation</li>
            <li><strong>Omahon Seal</strong> &mdash; 6-module runtime integrity framework</li>
            <li><strong>41 security modules</strong> &mdash; full hardening stack</li>
            <li><strong>All Alfred apps</strong> &mdash; Browser, IDE, Voice, Search, Store, Welcome, Calamares</li>
        </ul>

        <h3>Platform</h3>
        <ul>
            <li><strong>Kernel:</strong> Linux <strong>7.0.12</strong> (custom-compiled mainline)</li>
            <li><strong>Base:</strong> Debian Trixie (13)</li>
            <li><strong>Boot:</strong> BIOS + UEFI hybrid ISO</li>
            <li><strong>Desktop:</strong> KWin Wayland Compositor (Spatial 3D Cube) + SDDM</li>
            <li><strong>Size:</strong> <strong>50 GB+</strong> &mdash; Apocalypse-ready sovereign architecture (includes 44 GB Apocalypse Vault)</li>
            <li><strong>Build hooks:</strong> <strong>1,335</strong> (sacred, mathematically perfect)</li>
            <li><strong>License:</strong> AGPL-3.0</li>
        </ul>

        <div class="checksum">
            <strong>SHA-256:</strong><br>
            <span><em>Build in progress &mdash; checksums will be posted when the Ascension ISO is sealed.</em></span>
        </div>

        <a class="dl-btn" href="/download">Download v7.77 GA &mdash; Ascension Edition</a>
    </div>

    <!-- v7.77 — KINGDOM OF GOD EDITION (PREVIOUS) -->
    <div class="release" id="v777">
        <div class="release-header">
            <h2>v7.77 GA — "Kingdom of God Edition"</h2>
            <span class="badge badge-previous">Previous</span>
            <span class="badge badge-kernel7">Kernel 7.0</span>
            <span class="badge badge-security">41 security modules</span>
        </div>
        <div class="release-date">April 11, 2026 — <strong>The Kingdom of God Edition. His Word. His Music. His Seal. Forever.</strong></div>
        <p style="color:var(--text-muted);font-size:0.88rem;line-height:1.55;margin:-0.25rem 0 1rem;">Public GA ISO download / reseal window (covenant + P2P hub): <strong style="color:var(--cyan);">Friday, May 29, 2026 · 6:00 PM Eastern</strong> — <a href="/download" style="color:var(--cyan);">/download</a>. (Perez-lineage ninth-hour anchor Fri Apr 17, 2026 — separate milestone.)</p>

        <h3>✝ What&rsquo;s New in v7.77</h3>
        <ul>
            <li><strong>AKJV Bible</strong> — Complete Authorized King Jesus Version: 94 books (66 canonical + 28 deuterocanonical), 39,482 verses, 7 searchable TSV files. Accessible via <code>alfred-bible</code> CLI.</li>
            <li><strong>27-Track Worship Album</strong> — &ldquo;Jesus Christ The Light Our Universe&rdquo; by Elyon Neshama. 13 songs &times; 2 versions (A &amp; B) + &ldquo;All Honor To Your Name.&rdquo; Playable via <code>alfred-music</code> CLI with lyrics viewer.</li>
            <li><strong>New Jerusalem Spatial Desktop</strong> — KWin Wayland Compositor replacing KDE Plasma 6, featuring a literal 3D Spinning Cube interface for traversing workspaces seamlessly.</li>
            <li><strong>Prophetic Vision (Offline GPU Generation)</strong> — ComfyUI and Flux natively integrated with local RAG for high-fidelity, offline, biblically-accurate concept art generation.</li>
            <li><strong>LAvocat.ca (Sovereign Legal Intelligence)</strong> — Integrated decentralized justice system acting as your local AI-powered legal defender and auditor.</li>
            <li><strong>157 build hooks</strong> — Kingdom build pipeline on the build host (v7.77 GA shipped <strong>17</strong>; we set a 42-hook milestone for Matthew 1:17 and the build outgrew it). Adds quantum, mesh, productivity, GPU, max sovereign, eternal storage, Kingdom locale (0297), AKJV + family Bible (0290/0291), chess, AI stack, containers, sovereign tools, Omahon, observability waves 2-9, attestation, and the full IDE/voice/search/installer path &mdash; all auditable on <a href="/forge/commander/alfredlinux.com" style="color:#00D4FF;">GoForge</a>.</li>
            <li><strong>GPU Compute</strong> — NVIDIA CUDA, AMD ROCm, and Intel oneAPI detection and configuration out of the box.</li>
            <li><strong>Eternal Storage</strong> — Immutable BTRFS Time Machine and Zero-Trust LUKS2 encrypted storage with automated integrity checking.</li>
            <li><strong>Sovereign Identity</strong> — Handshake DNS resolver, I2P routing, WireGuard mesh networking built in.</li>
            <li><strong>Container Runtime</strong> — Podman, Buildah, and Skopeo for rootless container workflows.</li>
            <li><strong>AI Development Stack</strong> — Python ML libraries, Ollama local LLM runner, Jupyter notebooks.</li>
            <li><strong>Terminal Power Tools</strong> — tmux, zoxide, fzf, ripgrep, bat, eza, delta, starship prompt.</li>
        </ul>

        <h3>Inherited from v4.0</h3>
        <ul>
            <li><strong>Omahon Seal</strong> — 6-module runtime integrity framework (Boot Seal, Watchman, Vault, Shell Guard, Secure Erase, Sovereign Attestation)</li>
            <li><strong>41 security modules</strong> — 32 hardening + 6 Omahon Seal</li>
            <li><strong>Kernel 7.0.12</strong> — Custom-compiled from Torvalds mainline (debs in <code>build/config/packages.chroot/</code>). First distro on kernel 7.</li>
            <li><strong>Alfred Browser, IDE, Voice, Search, Store, Welcome App, Calamares</strong> — All present.</li>
        </ul>

        <h3>Platform</h3>
        <ul>
            <li><strong>Kernel:</strong> Linux <strong>7.0.12</strong> (custom-compiled mainline; pre-built debs queued in <code>build/config/packages.chroot/</code>)</li>
            <li><strong>Base:</strong> Debian Trixie (13) — chroot still carries the 6.12 series until the kernel-hook reseal swaps default boot</li>
            <li><strong>Boot:</strong> BIOS + UEFI hybrid ISO</li>
            <li><strong>Desktop:</strong> KWin Wayland Compositor (Spatial 3D Cube) + SDDM</li>
            <li><strong>Size:</strong> ~3.27 GiB binary on the Apr 26/27 published artifact (sealed with ≈ 2 of 150 Alfred hooks); Kingdom target <strong>~7.77 GiB</strong> &mdash; the next reseal builds from the full 157-hook tree. See <a href="/download" style="color:#00D4FF;">/download</a> (measured when ISO is on disk) and <code>build/scripts/check-iso-777gib.sh</code>.</li>
            <li><strong>License:</strong> AGPL-3.0</li>
        </ul>

        <h3>Versioning Decree</h3>
        <p style="color:var(--text-muted);">Version 7.77 is eternal. All future updates follow the 7.77.*.*.* scheme &mdash; by God&rsquo;s decree. The number of God&rsquo;s perfection and completion on every byte.</p>

        <div class="checksum">
            <strong>SHA-256:</strong><br>
            <span><em>Build in progress &mdash; checksums will be posted when complete.</em></span>
        </div>

        <a class="dl-btn" href="/download">Download v7.77 GA</a>
    </div>

    <!-- v7.77 GA — PREVIOUS -->
    <div class="release" id="ga">
        <div class="release-header">
            <h2>v7.77 GA — "The People's OS"</h2>
            <span class="badge badge-kernel7">Kernel 7.0</span>
            <span class="badge badge-security">41 security modules</span>
        </div>
        <div class="release-date">April 8, 2026 — <strong>General Availability release. Omahon Seal. GPG signed. The trumpet sounds — incorruptible.</strong></div>

        <h3>🔏 The Omahon Seal (6 New Modules)</h3>
        <ul>
            <li><strong>Boot Seal</strong> — HMAC-SHA256 verification of 14 critical boot files (kernel, initrd, GRUB, fstab, shadow, sudoers, SSH config). One byte tampered = immediate alert.</li>
            <li><strong>The Watchman</strong> — Real-time inotify monitoring of <code>/etc</code>, <code>/boot</code>, and <code>/etc/ssh</code>. A sentinel that never sleeps.</li>
            <li><strong>The Vault</strong> — 16MB encrypted tmpfs at <code>/run/omahon-vault</code>. RAM-only. Root-only. Vanishes on power loss. No forensic recovery.</li>
            <li><strong>Shell Guard</strong> — Active secret redaction in terminal sessions. API keys, tokens, passwords masked in real-time.</li>
            <li><strong>Secure Erase</strong> — <code>alfred-shred</code>: 3-pass cryptographic wipe. Random, zero, random, unlink.</li>
            <li><strong>Sovereign Attestation</strong> — SHA-256 chain-of-trust from build to boot. <code>alfred-attestation</code> proves system integrity.</li>
        </ul>

        <h3>Release Integrity</h3>
        <ul>
            <li><strong>GPG Signed</strong> — ISO signed with GoSiteMe Release Signing key (RSA-4096, Key ID: <code>32BCEDE8C8DD8B00</code>)</li>
            <li><strong>Public key:</strong> <a href="/downloads/GPG-KEY.asc">GPG-KEY.asc</a></li>
            <li><strong>Fingerprint:</strong> <code>41E1 6607 5B0F 9520 5839 E41B 32BC EDE8 C8DD 8B00</code></li>
            <li><strong>Verify:</strong> <code>gpg --import GPG-KEY.asc && gpg --verify YOUR.iso.asc YOUR.iso</code> (use the matching basename for the ISO you downloaded)</li>
        </ul>

        <h3>Build System</h3>
        <ul>
            <li><strong>17 build hooks</strong> — up from 16 in RC8 (Omahon Seal hook 0175 added)</li>
            <li><strong>41 security modules</strong> — 32 hardening + 6 Omahon Seal runtime integrity modules</li>
            <li><strong>IDE hook updated</strong> — code-server 4.114.1 (was 4.114.0)</li>
            <li><strong>Branding updated</strong> — all references to 2.0/RC → 4.0 GA throughout</li>
        </ul>

        <h3>Platform</h3>
        <ul>
            <li><strong>Kernel:</strong> Linux 7.0.12 (custom-compiled mainline)</li>
            <li><strong>Base:</strong> Debian Trixie (13)</li>
            <li><strong>Boot:</strong> BIOS + UEFI hybrid ISO</li>
            <li><strong>Desktop:</strong> KDE Plasma 6.18 + LightDM (Legacy build)</li>
            <li><strong>Size:</strong> 2.3 GB ISO</li>
            <li><strong>License:</strong> AGPL-3.0</li>
        </ul>

        <h3>CLI Tools (8 Omahon + 6 Security)</h3>
        <ul>
            <li><strong>Omahon:</strong> <code>omahon-seal</code>, <code>omahon-watchman</code>, <code>omahon-vault-wipe</code>, <code>omahon-reveal</code>, <code>alfred-shred</code>, <code>alfred-attestation</code></li>
            <li><strong>Security:</strong> <code>alfred-security-status</code>, <code>alfred-scan</code>, <code>alfred-usb-storage</code>, <code>alfred-aide-init</code>, <code>alfred-network-status</code>, <code>alfred-encrypt-status</code></li>
            <li><strong>System:</strong> <code>alfred-info</code>, <code>alfred-update</code>, <code>fastfetch</code></li>
        </ul>

        <div class="checksum">
            <strong>SHA-256:</strong><br>
            <span>4d8f0349692ea78c0639b48201067e13e62e47039f47b3db7be4b0193b757f4e</span>
        </div>
        <div class="checksum">
            <strong>SHA-512:</strong><br>
            <span>efa2935d0ce0a088292216e2b25c0690943d0e4a3b07d0eb1d9ec7b45debec8500536a039fa2368e1bf4e9ee8a230ebedf50d131cb9574727c52b433d3e4e406</span>
        </div>
        <div class="checksum">
            <strong>GPG Signature:</strong><br>
            <span><a href="/downloads/GPG-KEY.asc" style="color:var(--green);">Public Key</a> · <em style="color:var(--text-dim);font-size:0.85rem;">v4.0 signature archived — see v7.77 for current</em></span>
        </div>

        <a class="dl-btn" href="/download">Download v7.77 GA</a>
    </div>

    <!-- RC8 — PREVIOUS -->
    <div class="release" id="rc8">
        <div class="release-header">
            <h2>v4.0 RC8</h2>
            <span class="badge badge-previous">Previous</span>
            <span class="badge badge-kernel7">Kernel 7.0</span>
            <span class="badge badge-security">32 Security Modules</span>
        </div>
        <div class="release-date">April 7, 2026 — <strong>Enterprise-grade security hardening: 32 modules, 3 dedicated hooks, full-disk encryption</strong></div>

        <h3>Security Hardening (32 Modules — 3 New Hooks)</h3>
        <ul>
            <li><strong>Hook 0160 — Alfred Security</strong> (21 modules): sysctl CIS L2 hardening (45+ rules), kernel lockdown mode, AppArmor enforced with custom Alfred IDE &amp; Meilisearch profiles, unattended-upgrades, fail2ban (SSH 3-try/24h ban), auditd (30+ immutable rules), DNS-over-TLS (Quad9 + Cloudflare), USB security logging &amp; toggle, dangerous module blacklisting (firewire, dccp, sctp, cramfs), PAM password hardening (10-char/3-class/lockout), AIDE file integrity monitoring, ClamAV antivirus (weekly scan), rootkit detection (rkhunter + chkrootkit), hidepid=2, secure mount options (/tmp noexec), login banners, core dump prevention, cron/at root-only, compiler access restriction, NTS time synchronization (chrony), <code>alfred-security-status</code> CLI tool</li>
            <li><strong>Hook 0165 — Alfred Network Hardening</strong> (7 modules): MAC address randomization (WiFi + Ethernet), nftables default-deny firewall, TCP wrappers, port scan defense, wireless hardening (WPS disabled), SSH strong ciphers only (chacha20-poly1305, ed25519, sntrup761x25519), <code>alfred-network-status</code> CLI tool</li>
            <li><strong>Hook 0170 — Full Disk Encryption</strong> (4 modules): LUKS2 with cryptsetup + initramfs integration, strong encryption defaults, Calamares FDE checkbox enabled, <code>alfred-encrypt-status</code> CLI tool</li>
        </ul>

        <h3>Build System</h3>
        <ul>
            <li><strong>16 build hooks</strong> — up from 13 in RC7 (3 new security hooks)</li>
            <li><strong>19 new security packages</strong>: apparmor suite, auditd, aide, clamav, rkhunter, chkrootkit, libpam-pwquality, chrony, nftables, unattended-upgrades, cryptsetup</li>
            <li><strong>DNS fix hook</strong> (0011): resolves chroot DNS failures by forcibly writing /etc/resolv.conf</li>
            <li><strong>fastfetch</strong> replaces neofetch (removed from Trixie repos)</li>
            <li><strong>Resilient hooks</strong>: IDE (0300) and Voice (0400) now use <code>set +e</code> so optional failures don't kill the build</li>
        </ul>

        <h3>Applications</h3>
        <ul>
            <li><strong>Alfred IDE</strong> — VS Code-compatible IDE (powered by code-server 4.114.0)</li>
            <li><strong>Alfred Voice</strong> — Kokoro TTS + PyTorch + espeak-ng + OpenWakeWord</li>
            <li><strong>Alfred Search</strong> — Meilisearch instant search</li>
            <li><strong>Alfred Store</strong> — Flatpak + KDE Discover</li>
            <li><strong>Alfred Browser</strong> — Tauri + WebKitGTK (zero telemetry)</li>
            <li><strong>Alfred Welcome</strong> — first-boot wizard</li>
            <li><strong>Alfred Update</strong> — system update manager</li>
            <li><strong>Calamares</strong> — graphical installer with FDE support</li>
        </ul>

        <h3>Platform</h3>
        <ul>
            <li><strong>Kernel:</strong> Linux 7.0.12 (custom-compiled mainline)</li>
            <li><strong>Base:</strong> Debian Trixie (13)</li>
            <li><strong>Boot:</strong> BIOS + UEFI hybrid ISO</li>
            <li><strong>Desktop:</strong> KDE Plasma 6.18 + LightDM</li>
            <li><strong>Size:</strong> 2.4 GB ISO</li>
            <li><strong>Distribution:</strong> WebTorrent P2P (browser-native) + .torrent file</li>
            <li><strong>CLI Tools:</strong> alfred-security-status, alfred-scan, alfred-usb-storage, alfred-aide-init, alfred-network-status, alfred-encrypt-status, alfred-info, alfred-update, fastfetch</li>
        </ul>

        <div class="checksum">
            <strong>SHA-256:</strong><br>
            <span>7d49ef3cfb957cb9854bd3f451ef99ec8255afd68069a89ed0cf5a847d5d79bf</span>
        </div>
        <div class="checksum">
            <strong>BLAKE3:</strong><br>
            <span>e021d2024599aa918972d9e6b9fd9c1d97d226ac69da035913fd7a462dbef47d</span>
        </div>

        <a class="dl-btn" href="/download">Download GA (Latest)</a>
    </div>

    <!-- RC7 -->
    <div class="release" id="rc7">
        <div class="release-header">
            <h2>v4.0 RC7</h2>
            <span class="badge badge-previous">Previous</span>
            <span class="badge badge-kernel7">Kernel 7.0</span>
        </div>
        <div class="release-date">April 6, 2026 — <strong>First distro on earth shipping Linux kernel 7.0</strong></div>

        <h3>Kernel</h3>
        <ul>
            <li><strong>Linux 7.0.12-rc7-alfred</strong> — custom-compiled from Linus Torvalds' mainline tree (released April 5, 2026)</li>
            <li>3 kernel-7-exclusive CPU mitigations: <strong>ITS</strong> (Indirect Target Selection), <strong>TSA</strong> (Transient Scheduler Attacks), <strong>VMSCAPE</strong> (VM-exit Speculative Code Attack Prevention)</li>
            <li>24 total compiled-in CPU mitigations (Spectre v1/v2/BHI, Meltdown, MDS, TAA, MMIO, RFDS, SRBDS, L1TF, SSB, and more)</li>
        </ul>

        <h3>Security (12 default gaps patched)</h3>
        <ul>
            <li><strong>16 boot security parameters</strong>: init_on_alloc, init_on_free, slab_nomerge, page_alloc.shuffle, pti=on, lockdown=integrity, debugfs=off, io_uring_disabled, tsx=off, vsyscall=none, and more</li>
            <li><strong>nftables drop-by-default firewall</strong> with UFW front-end</li>
            <li><strong>AppArmor</strong> mandatory access control enforced at boot</li>
            <li><strong>fail2ban</strong> intrusion prevention active by default</li>
            <li><strong>auditd</strong> security audit logging enabled</li>
            <li><strong>unattended-upgrades</strong> for automatic security patches</li>
            <li><strong>Auto-generated IDE passwords</strong> — no more hardcoded defaults</li>
            <li>Dangerous kernel modules blacklisted: firewire, thunderbolt DMA, cramfs, freevxfs, hfs, jffs2, udf</li>
            <li>Kernel sysctl hardening: ASLR=2, symlink/hardlink protection, SYN cookies, ICMP redirects disabled, source routing blocked</li>
        </ul>

        <h3>Applications (13 build hooks)</h3>
        <ul>
            <li><strong>Alfred IDE</strong> — VS Code-compatible IDE (powered by code-server 4.114.0)</li>
            <li><strong>Alfred Voice</strong> — Kokoro TTS engine with PyTorch 2.11.0, espeak-ng, OpenWakeWord</li>
            <li><strong>Alfred Search</strong> — Meilisearch instant search engine</li>
            <li><strong>Alfred Store</strong> — Flatpak + KDE Discover for app distribution</li>
            <li><strong>Alfred Browser</strong> — Tauri + WebKitGTK (zero telemetry)</li>
            <li><strong>Alfred Welcome</strong> — first-boot welcome and setup wizard</li>
            <li><strong>Alfred Update</strong> — system update manager</li>
            <li><strong>Calamares</strong> — graphical installer for disk installation</li>
        </ul>

        <h3>Platform</h3>
        <ul>
            <li><strong>Base:</strong> Debian Trixie (13)</li>
            <li><strong>Boot:</strong> BIOS + UEFI hybrid ISO (ISOLINUX + GRUB EFI)</li>
            <li><strong>Desktop:</strong> LightDM display manager</li>
            <li><strong>Hardware:</strong> LVM2, btrfs, ZRAM swap, TLP power management, CUPS printing, thermald</li>
            <li><strong>Size:</strong> 2.5 GB ISO</li>
            <li><strong>Distribution:</strong> WebTorrent P2P (sovereign distribution)
        </ul>

        <div class="checksum">
            <strong>SHA-256:</strong><br>
            <span>2ee02635f2fbf2ba3d4c88c8cbdc528902dec4d79275c76fc6457f74ef38f1b1</span>
        </div>
    </div>

    <!-- RC6 -->
    <div class="release" id="rc6">
        <div class="release-header">
            <h2>v4.0 RC6</h2>
            <span class="badge badge-previous">Previous</span>
        </div>
        <div class="release-date">April 6, 2026</div>

        <h3>Highlights</h3>
        <ul>
            <li><strong>Kernel 6.12.74</strong> — Debian Trixie LTS security kernel</li>
            <li><strong>12 build hooks</strong> (full application stack)</li>
            <li><strong>Universal hardware support</strong> — GPU drivers (NVIDIA, AMD, Intel), WiFi/Bluetooth firmware, input devices, power management, auto-detect 3-tier driver loading</li>
            <li><strong>Install-or-try dialog</strong> on live boot — user chooses live session or Calamares installer immediately</li>
            <li><strong>KDE Plasma 6 desktop trust fix</strong> — desktop files launch without "untrusted application" warnings</li>
            <li><strong>Kyber-1024 branding</strong> — post-quantum visual identity applied</li>
            <li><strong>Calamares installer</strong> now visible and launchable from desktop with Alfred v4.0 branding and slideshow</li>
            <li>First build with WebTorrent P2P distribution</li>
            <li>First build with Alfred Store (Flatpak + KDE Discover)</li>
        </ul>
    </div>

    <!-- RC5 -->
    <div class="release" id="rc5">
        <div class="release-header">
            <h2>v4.0 RC5</h2>
            <span class="badge badge-previous">Previous</span>
        </div>
        <div class="release-date">April 6, 2026</div>

        <h3>Highlights</h3>
        <ul>
            <li><strong>Kernel 6.12.74</strong></li>
            <li><strong>10 build hooks</strong> — full v4.0 application stack</li>
            <li><strong>Alfred Welcome</strong> — 7-page first-boot setup wizard</li>
            <li><strong>Alfred Store</strong> — Flatpak app center with KDE Discover</li>
            <li><strong>Voice 2.0</strong> — "Hey Alfred" wake word detection via OpenWakeWord (always-on systemd service)</li>
            <li><strong>alfred-update</strong> — system update manager with GUI and CLI</li>
            <li><strong>alfred-info</strong> — system information CLI tool</li>
            <li><strong>Version check API</strong> — checks for OS updates at boot</li>
            <li><strong>Calamares</strong> — v4.0 branding applied to graphical installer</li>
        </ul>
    </div>

    <!-- RC4 -->
    <div class="release" id="rc4">
        <div class="release-header">
            <h2>v4.0 RC4</h2>
            <span class="badge badge-previous">Previous</span>
        </div>
        <div class="release-date">April 6, 2026</div>

        <h3>Highlights</h3>
        <ul>
            <li><strong>Trixie rebase</strong> — OS moved from Debian Bookworm (12) to <strong>Debian Trixie (13)</strong></li>
            <li><strong>Kernel 6.12.74</strong> — Trixie's LTS kernel with EEVDF scheduler and Rust-in-kernel support</li>
            <li><strong>UEFI + BIOS hybrid boot</strong> — single ISO boots on both modern and legacy systems</li>
            <li><strong>Alfred Voice v2</strong> — Kokoro TTS + PyTorch, spaCy NLP, OpenWakeWord, espeak-ng fallback</li>
            <li><strong>Alfred Search</strong> — Meilisearch instant local search engine</li>
            <li>Voice hook fixed for Trixie (Python venv + --only-binary spacy workaround)</li>
        </ul>
    </div>

    <!-- RC3 -->
    <div class="release" id="rc3">
        <div class="release-header">
            <h2>v2.0 RC3</h2>
            <span class="badge badge-previous">Previous</span>
        </div>
        <div class="release-date">April 6, 2026</div>

        <h3>Highlights</h3>
        <ul>
            <li><strong>Kernel 6.1.0-44</strong> — Debian Bookworm LTS (WebKit, OpenSSL, ImageMagick, GStreamer security updates)</li>
            <li><strong>First verified bootable ISO</strong> (2.5 GB)</li>
            <li><strong>Critical boot fix</strong>: dual kernel-naming hooks (chroot hook #9999 + binary hook #9999) — creates generic vmlinuz/initrd that the bootloader expects</li>
            <li><strong>9 build hooks</strong>: Alfred Browser, Alfred IDE (VS Code-compatible IDE), Alfred Voice (Kokoro TTS), Alfred Search (Meilisearch), Calamares installer, branding, boot fix (chroot + binary)</li>
            <li>Samsung S26 Ultra mobile installer created (Termux + proot-distro, no root)</li>
        </ul>
    </div>

</div>

<footer>
    <p style="font-style:italic;color:#94a3b8;font-size:.85rem;margin:0 0 0.5rem;">&ldquo;The grass withereth, the flower fadeth: but the word of our God shall stand for ever.&rdquo; &mdash; <a href="https://gositeme.com/bible/read/isaiah/40" style="color:#facc15;text-decoration:none;">Isaiah 40:8</a> (AKJV)</p>
    &copy; <?= $year ?> <a href="https://gositeme.com">GoSiteMe Inc.</a> &mdash; Alfred Linux &middot; Open Source (AGPL-3.0)
</footer>

<?php include __DIR__ . "/includes/shabbat-banner.php"; ?>
</body>
</html>
