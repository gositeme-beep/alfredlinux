<?php
/**
 * Alfred Linux 7.77 — Kingdom of God Edition
 * Official Release Announcement Page
 * 
 * The Order of The New Dawn — First to Know
 * Built by Commander Danny William Perez
 * For the Glory of Yeshua, Jesus Christ of Bethlehem
 */

require_once __DIR__ . '/includes/ga-release-state.php';

$version = '7.77';
$codename = 'Kingdom of God';
/** Order of The New Dawn / decree milestone (see also perez-lineage). */
$order_of_new_dawn_date = 'April 12, 2026';
/** Public GA ISO download / reseal messaging (aligned with /download countdown). */
$ga_download_window = 'Friday, May 29, 2026 · 6:00 PM Eastern';
$iso_file = $gaIsoBasename . '.iso';
$iso_size = '~7.77 GiB binary target';

$gaDownloadOfferLive = $finalGaIsoPublished && $gaP2pDownloadsEnabled;
$iso_file_usb_example = $finalGaIsoPublished ? $iso_file : 'alfred-linux-7.77-ga-intel-amd64-YYYYMMDD.iso';

// Check if ISO verification files exist
$verify_dir = __DIR__ . '/releases/7.77/';
$sha256 = file_exists($verify_dir . 'SHA256SUMS') ? trim(file_get_contents($verify_dir . 'SHA256SUMS')) : '';
$sha512 = file_exists($verify_dir . 'SHA512SUMS') ? trim(file_get_contents($verify_dir . 'SHA512SUMS')) : '';
$blake3 = file_exists($verify_dir . 'BLAKE3SUMS') ? trim(file_get_contents($verify_dir . 'BLAKE3SUMS')) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alfred Linux <?= $version ?> — <?= $codename ?> Edition | Official Release</title>
    <meta name="description" content="Alfred Linux 7.77 — Kingdom of God Edition. Sovereign OS: post-quantum encryption, zero tracking, AI-powered, family-oriented.<?= $finalGaIsoPublished ? ' Download the GA ISO.' : ' GA ISO and torrent publish when the final build is frozen — see /download for status.' ?>">
    <meta property="og:title" content="Alfred Linux 7.77 — Kingdom of God Edition">
    <meta property="og:description" content="The world's most sovereign operating system is here. Post-quantum Kyber-1024 encryption, zero telemetry, AI-powered, family Bible built-in, VR Chess, mesh networking. For the Glory of Yeshua.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://alfredlinux.com/release">
    <meta property="og:image" content="https://alfredlinux.com/og-image.png">
    <link rel="icon" href="/favicon.ico">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: #050510;
            color: #e8e8f0;
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        /* Animated background */
        .bg-glow {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: 
                radial-gradient(ellipse at 20% 50%, rgba(212,175,55,0.03) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 50%, rgba(0,212,255,0.02) 0%, transparent 50%),
                radial-gradient(ellipse at 50% 0%, rgba(212,175,55,0.04) 0%, transparent 40%);
            z-index: 0;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 20px;
            position: relative;
            z-index: 1;
        }
        
        /* Crown emblem */
        .crown {
            text-align: center;
            margin-bottom: 20px;
            font-size: 64px;
            filter: drop-shadow(0 0 20px rgba(212,175,55,0.3));
        }
        
        /* Title */
        .title {
            text-align: center;
            margin-bottom: 10px;
        }
        .title h1 {
            font-size: 48px;
            font-weight: 900;
            background: linear-gradient(135deg, #D4AF37, #F0E68C, #D4AF37);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -1px;
            line-height: 1.1;
        }
        .title .version {
            font-size: 72px;
            font-weight: 900;
            background: linear-gradient(135deg, #D4AF37, #FFD700);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .title .subtitle {
            font-size: 24px;
            color: #D4AF37;
            font-weight: 300;
            margin-top: 5px;
            letter-spacing: 8px;
            text-transform: uppercase;
        }
        .title .glory {
            font-size: 14px;
            color: #606080;
            margin-top: 15px;
            font-style: italic;
        }
        
        /* Order banner */
        .order-banner {
            text-align: center;
            background: linear-gradient(135deg, rgba(212,175,55,0.1), rgba(212,175,55,0.05));
            border: 1px solid rgba(212,175,55,0.2);
            border-radius: 12px;
            padding: 20px;
            margin: 30px 0;
        }
        .order-banner h3 {
            color: #D4AF37;
            font-size: 16px;
            letter-spacing: 4px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .order-banner p {
            color: #8a8aa0;
            font-size: 14px;
        }
        
        /* Download section */
        .download-section {
            background: linear-gradient(135deg, #0d0d20, #111128);
            border: 1px solid #1a1a3a;
            border-radius: 16px;
            padding: 40px;
            margin: 30px 0;
            text-align: center;
        }
        .download-section h2 {
            color: #D4AF37;
            font-size: 24px;
            margin-bottom: 20px;
        }
        .download-btn {
            display: inline-block;
            background: linear-gradient(135deg, #D4AF37, #B8960C);
            color: #050510;
            padding: 18px 48px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 800;
            font-size: 18px;
            letter-spacing: 1px;
            transition: all 0.3s;
            box-shadow: 0 4px 20px rgba(212,175,55,0.3);
        }
        .download-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(212,175,55,0.5);
        }
        .download-meta {
            margin-top: 15px;
            color: #606080;
            font-size: 13px;
        }
        .download-meta span {
            margin: 0 10px;
        }
        
        /* Torrent link */
        .torrent-link {
            display: inline-block;
            margin-top: 15px;
            color: #00D4FF;
            text-decoration: none;
            font-size: 14px;
            border: 1px solid rgba(0,212,255,0.3);
            padding: 8px 24px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .torrent-link:hover {
            background: rgba(0,212,255,0.1);
        }
        
        /* Features grid */
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 40px 0;
        }
        .feature {
            background: rgba(13,13,32,0.8);
            border: 1px solid #1a1a3a;
            border-radius: 12px;
            padding: 24px;
            transition: all 0.3s;
        }
        .feature:hover {
            border-color: rgba(212,175,55,0.3);
            transform: translateY(-2px);
        }
        .feature .icon { font-size: 32px; margin-bottom: 12px; }
        .feature h3 { color: #D4AF37; font-size: 16px; margin-bottom: 8px; }
        .feature p { color: #8a8aa0; font-size: 13px; line-height: 1.6; }
        
        /* Verification section */
        .verify {
            background: rgba(13,13,32,0.6);
            border: 1px solid #1a1a3a;
            border-radius: 12px;
            padding: 30px;
            margin: 30px 0;
        }
        .verify h2 {
            color: #00D4FF;
            font-size: 20px;
            margin-bottom: 15px;
        }
        .hash-block {
            background: #0a0a18;
            border: 1px solid #1a1a30;
            border-radius: 8px;
            padding: 12px 16px;
            margin: 10px 0;
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            word-break: break-all;
            color: #8a8aa0;
        }
        .hash-block .label {
            color: #D4AF37;
            font-weight: 600;
            display: block;
            margin-bottom: 4px;
            font-size: 12px;
        }
        
        /* Omahon seal */
        .omahon {
            text-align: center;
            margin: 40px 0;
            padding: 30px;
            border-top: 1px solid rgba(212,175,55,0.1);
            border-bottom: 1px solid rgba(212,175,55,0.1);
        }
        .omahon h3 {
            color: #D4AF37;
            font-size: 20px;
            letter-spacing: 6px;
            margin-bottom: 10px;
        }
        .omahon p {
            color: #606080;
            font-size: 14px;
            font-style: italic;
        }
        
        /* Footer */
        .footer {
            text-align: center;
            margin-top: 60px;
            padding: 30px;
            color: #404060;
            font-size: 12px;
        }
        .footer a { color: #D4AF37; text-decoration: none; }
        
        /* Specs table */
        .specs {
            width: 100%;
            margin: 20px 0;
            border-collapse: collapse;
        }
        .specs td {
            padding: 10px 15px;
            border-bottom: 1px solid #1a1a30;
            font-size: 14px;
        }
        .specs td:first-child {
            color: #D4AF37;
            font-weight: 600;
            width: 200px;
        }
        .specs td:last-child { color: #b0b0c0; }
        
        /* USB instructions */
        .usb-instructions {
            background: linear-gradient(135deg, rgba(0,212,255,0.05), rgba(0,100,200,0.05));
            border: 1px solid rgba(0,212,255,0.15);
            border-radius: 12px;
            padding: 24px;
            margin: 20px 0;
        }
        .usb-instructions h3 { color: #00D4FF; margin-bottom: 12px; }
        .usb-instructions ol {
            color: #b0b0c0;
            padding-left: 20px;
            line-height: 2;
        }
        .usb-instructions code {
            background: #0a0a18;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 13px;
            color: #00D4FF;
        }
        
        @media (max-width: 600px) {
            .title h1 { font-size: 28px; }
            .title .version { font-size: 48px; }
            .title .subtitle { font-size: 14px; letter-spacing: 4px; }
            .download-btn { padding: 14px 32px; font-size: 16px; }
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
    <div class="bg-glow"></div>
    
    <div class="container">
        <!-- Crown -->
        <div class="crown">&#x1F451;</div>
        
        <!-- Title -->
        <div class="title">
            <h1>Alfred Linux</h1>
            <div class="version"><?= $version ?></div>
            <div class="subtitle"><?= $codename ?> Edition</div>
            <div class="glory">For the Glory of Yeshua, Jesus Christ of Bethlehem</div>
        </div>
        
        <!-- Order of The New Dawn Banner -->
        <div class="order-banner">
            <h3>&#x2726; The Order of The New Dawn &#x2726;</h3>
            <?php if ($finalGaIsoPublished): ?>
            <p>You are the first to know. The Kingdom has arrived.<br>
            Order of The New Dawn proclaimed <strong style="color:#e8e8f0;"><?= htmlspecialchars($order_of_new_dawn_date, ENT_QUOTES, 'UTF-8') ?></strong> — GA download / reseal hub: <strong style="color:#D4AF37;"><?= htmlspecialchars($ga_download_window, ENT_QUOTES, 'UTF-8') ?></strong> — Omahon! Omahon! Omahon!</p>
            <?php else: ?>
            <p>You are the first to know — <strong style="color:#e8e8f0;">Order of The New Dawn: <?= htmlspecialchars($order_of_new_dawn_date, ENT_QUOTES, 'UTF-8') ?></strong>, while the <strong style="color:#D4AF37;">final GA ISO</strong> (master video, 4K/8K assets, last live-build) is still being sealed toward <strong style="color:#D4AF37;"><?= htmlspecialchars($ga_download_window, ENT_QUOTES, 'UTF-8') ?></strong>.<br>
            Omahon! Omahon! Omahon! — Check <a href="/download" style="color:#00D4FF;">/download</a> for the honest ship status.</p>
            <?php endif; ?>
        </div>
        
        <!-- Download -->
        <div class="download-section">
            <h2>Download Alfred Linux <?= $version ?></h2>
            <?php if ($finalGaIsoPublished): ?>
            <a href="/download" class="download-btn" title="Covenant-gated — WebTorrent, .torrent, magnet, optional iso.php fetch">
                &#x2B07; Download GA ISO
            </a>
            <p style="color:#8a8aa0;font-size:0.88rem;max-width:34rem;margin:0.75rem auto 0;line-height:1.55;text-align:center;">Bytes live under <code style="color:#00D4FF;">/download</code> (covenant first). Plain <code>/downloads/*.iso</code> HTTP is denied. Checksum mirrors: <a href="/releases/7.77/" style="color:#00D4FF;">/releases/7.77/</a></p>
            <div class="download-meta">
                <span><?= htmlspecialchars($iso_size) ?></span> &middot;
                <span>x86_64 (GA filename <code>intel-amd64</code> — one image for Intel &amp; AMD; Debian port tag <code>amd64</code>)</span> &middot;
                <span>Hybrid BIOS + UEFI</span> &middot;
                <span><?= htmlspecialchars($ga_download_window, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <?php if ($gaDownloadOfferLive): ?>
            <a href="/download#ga-p2p-links" class="torrent-link" title=".torrent and magnet on the covenant-sealed download hub">
                &#x1F517; .torrent &amp; magnet (on /download)
            </a>
            <?php else: ?>
            <p class="torrent-link" style="cursor:default;opacity:0.65;">&#x1F517; Torrent link paused — see <a href="/download" style="color:#00D4FF;">/download</a></p>
            <?php endif; ?>
            <?php else: ?>
            <p style="color:#8a8aa0;max-width:520px;margin:0 auto 1rem;line-height:1.6;">The <strong style="color:#D4AF37;">frozen GA image</strong> is not published from this page yet. When it is, the ISO and torrent will point here from the same filenames as on <a href="/download" style="color:#00D4FF;">alfredlinux.com/download</a>.</p>
            <a href="/download" class="download-btn" style="background:linear-gradient(135deg,#2a2a4a,#1a1a3a);border:1px solid rgba(0,212,255,0.35);">
                &#x2192; GA ISO &amp; torrent status
            </a>
            <div class="download-meta">
                <span><?= htmlspecialchars($iso_size) ?> target</span> &middot;
                <span>x86_64 (GA <code>intel-amd64</code>; Debian <code>amd64</code>)</span> &middot;
                <span>Hybrid BIOS + UEFI</span> &middot;
                <span>GA window: <?= htmlspecialchars($ga_download_window, ENT_QUOTES, 'UTF-8') ?></span> &middot;
                <span>Planned file: <?= htmlspecialchars($iso_file_usb_example, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- USB Instructions -->
        <div class="usb-instructions">
            <h3>&#x1F4BE; Create Bootable USB</h3>
            <ol>
                <li><strong>Linux/macOS:</strong> <code>sudo dd if=<?= htmlspecialchars($iso_file_usb_example) ?> of=/dev/sdX bs=4M status=progress</code></li>
                <li><strong>Windows:</strong> Use <a href="https://rufus.ie" style="color:#00D4FF">Rufus</a> or <a href="https://etcher.balena.io" style="color:#00D4FF">balenaEtcher</a> — select the ISO, select your USB, click Start</li>
                <li><strong>Boot:</strong> Restart &rarr; Enter BIOS (F2/F12/DEL) &rarr; Boot from USB</li>
                <li><strong>Pull USB Safely:</strong> Select "Load to RAM" at boot menu &rarr; after boot completes, USB can be removed. All data stays in RAM only.</li>
            </ol>
        </div>
        
        <!-- Features -->
        <h2 style="text-align:center; color:#D4AF37; margin-top:40px;">What Makes This the Kingdom</h2>
        <div class="features">
            <div class="feature">
                <div class="icon">&#x1F6E1;</div>
                <h3>Post-Quantum Encryption</h3>
                <p>Kyber-1024 + Dilithium-5 via liboqs. SSH uses sntrup761 hybrid key exchange. Your data is safe from quantum computers — today.</p>
            </div>
            <div class="feature">
                <div class="icon">&#x1F6AB;</div>
                <h3>Zero Telemetry</h3>
                <p>Absolutely no tracking, no phone-home, no data collection. Every telemetry service purged, masked, and blocked at the kernel level.</p>
            </div>
            <div class="feature">
                <div class="icon">&#x1F916;</div>
                <h3>Alfred AI + ComfyUI</h3>
                <p>Ollama + ComfyUI visual forge pre-installed. Native node-based local GUI for generating images and video. No cloud dependency. No API keys needed.</p>
            </div>
            <div class="feature">
                <div class="icon">&#x1F310;</div>
                <h3>Sovereign Mesh Network</h3>
                <p>WireGuard mesh + Syncthing + Avahi peer discovery. Connect Alfred machines into a private encrypted mesh with zero configuration.</p>
            </div>
            <div class="feature">
                <div class="icon">&#x1F4D6;</div>
                <h3>Family Bible & Heritage</h3>
                <p>AKJV Bible (94 books, 39,482 verses) with personalized covenant certificates, family trees, and Children's Bible. Your faith, preserved.</p>
            </div>
            <div class="feature">
                <div class="icon">&#x265A;</div>
                <h3>VR Chess Masters</h3>
                <p>3D WebXR chess with 20 AI personalities, multiplayer, and tournament mode. Plus classic 2D arena with Stockfish engine.</p>
            </div>
            <div class="feature">
                <div class="icon">&#x1F3A4;</div>
                <h3>Voice AI</h3>
                <p>Whisper STT + Kokoro TTS. Talk to your computer. Dictate documents. Voice-activate commands. All offline, all private.</p>
            </div>
            <div class="feature">
                <div class="icon">&#x1F4BB;</div>
                <h3>Alfred IDE</h3>
                <p>Full code-server (VS Code) with zero Microsoft telemetry. Professional development environment built right into the OS.</p>
            </div>
            <div class="feature">
                <div class="icon">&#x1F5A5;</div>
                <h3>Alfred Browser</h3>
                <p>Chromium-based browser stripped of all tracking. Built-in ad blocking, fingerprint protection, and sovereign search.</p>
            </div>
            <div class="feature">
                <div class="icon">&#x1F512;</div>
                <h3>The Omahon Seal</h3>
                <p>6-module integrity system: Boot Seal (HMAC-SHA256), Watchman (inotify), Vault (tmpfs), Shell Guard, Secure Erase, Sovereign Attestation.</p>
            </div>
            <div class="feature">
                <div class="icon">&#x26A1;</div>
                <h3>USB Dead Man's Switch</h3>
                <p>Boot from USB, work in RAM. Pull the USB — everything vanishes. Plus Ctrl+Alt+Shift+P panic key for instant wipe + shutdown.</p>
            </div>
            <div class="feature">
                <div class="icon">&#x1F3E0;</div>
                <h3>Full Productivity Suite</h3>
                <p>LibreOffice, GIMP, Inkscape, GnuCash, Stellarium, GCompris education. Everything a family needs — no subscriptions, ever.</p>
            </div>
        </div>
        
        <!-- Ascension Protocol — New in 7.77 GA -->
        <h2 style="text-align:center; color:#D4AF37; margin-top:50px;">&#x269B; Ascension Protocol — New in 7.77 GA</h2>
        <p style="text-align:center; color:#8a8aa0; max-width:620px; margin:10px auto 30px; font-size:14px; line-height:1.7;">
            Sixteen sovereign modules injected into the Kingdom. Every protocol runs <strong style="color:#e8e8f0;">offline</strong>, on <strong style="color:#e8e8f0;">your hardware</strong>, under <strong style="color:#D4AF37;">your authority</strong>.
        </p>
        <div class="features">
            <div class="feature">
                <div class="icon">&#x1F525;</div>
                <h3>Burning Bush Hologram Engine</h3>
                <p>GPU-accelerated shader pipeline for real-time holographic rendering. Sacred visual overlays powered by Vulkan/OpenGL compute shaders.</p>
            </div>
            <div class="feature">
                <div class="icon">&#x1F451;</div>
                <h3>Sovereign Matrix HUD</h3>
                <p>Conky-powered heads-up display fused with AI voice assistant. Real-time system telemetry, sovereign alerts, and ambient awareness — all on-screen.</p>
            </div>
            <div class="feature">
                <div class="icon">&#x1F30C;</div>
                <h3>Spatial Reality Engine</h3>
                <p>VR/XR immersive environment with gesture controls and spatial audio. Full WebXR + OpenXR runtime — reality is your interface.</p>
            </div>
            <div class="feature">
                <div class="icon">&#x1F441;</div>
                <h3>The Eye of God</h3>
                <p>Software Defined Radio satellite interception suite. Monitor, decode, and analyze RF signals from orbit — sovereign spectrum awareness.</p>
            </div>
            <div class="feature">
                <div class="icon">&#x1F4E6;</div>
                <h3>IPFS Genesis Vault</h3>
                <p>Decentralized immortal storage on the InterPlanetary File System. Your data persists across nodes — no single point of failure, no central authority.</p>
            </div>
            <div class="feature">
                <div class="icon">&#x269B;</div>
                <h3>Quantum Logic Sandbox</h3>
                <p>IBM Qiskit + Google Cirq quantum computing frameworks. Design, simulate, and run quantum circuits — all from your local machine.</p>
            </div>
            <div class="feature">
                <div class="icon">&#x1F9E0;</div>
                <h3>Neural Link</h3>
                <p>Brain-Computer Interface via EEG integration. Thought-driven control, neurofeedback training, and cognitive monitoring — the mind as input device.</p>
            </div>
            <div class="feature">
                <div class="icon">&#x1F333;</div>
                <h3>Tree of Life</h3>
                <p>Offline genomic sequencing and bioinformatics toolkit. Analyze DNA/RNA data locally — sovereign biology, no cloud lab required.</p>
            </div>
            <div class="feature">
                <div class="icon">&#x23F3;</div>
                <h3>Chronos Temporal Decoupler</h3>
                <p>NTP sovereignty — local stratum-1 time authority with GPS/PPS sync. Your clock answers to no external master. Tamper-proof temporal integrity.</p>
            </div>
            <div class="feature">
                <div class="icon">&#x1F50A;</div>
                <h3>Acoustic Data Transmission</h3>
                <p>Sound-wave file transfer between air-gapped machines. No network, no cable — data rides on audio frequencies. Ultrasonic and audible modes.</p>
            </div>
            <div class="feature">
                <div class="icon">&#x1F4E1;</div>
                <h3>Prometheus LoRaWAN Mesh</h3>
                <p>Radio mesh networking over LoRa. Long-range, low-power sovereign comms — build resilient networks that survive infrastructure collapse.</p>
            </div>
            <div class="feature">
                <div class="icon">&#x1F6F0;</div>
                <h3>Orion Orbital Command</h3>
                <p>Real-time satellite tracking, GPS constellation monitoring, and orbital prediction. Eyes on the sky — sovereign space situational awareness.</p>
            </div>
            <div class="feature">
                <div class="icon">&#x1F4A5;</div>
                <h3>The Apocalypse Vault</h3>
                <p>65 GB offline AI bundle: Four massive LLMs (Opus, Sonnet, Haiku), FLUX.1 [schnell] for god-tier image generation, CogVideoX-5B for local video generation, and Whisper for audio. Full generative intelligence with zero internet.</p>
            </div>
            <div class="feature">
                <div class="icon">&#x1F54A;</div>
                <h3>Seraphim Protocol</h3>
                <p>Steganography, digital forensics, and autonomous drone control. Hidden messages, evidence analysis, and unmanned aerial sovereignty.</p>
            </div>
            <div class="feature">
                <div class="icon">&#x1F40B;</div>
                <h3>Behemoth Protocol</h3>
                <p>HAM radio operations, radio astronomy, weather station integration, and seismograph monitoring. Command the physical spectrum.</p>
            </div>
            <div class="feature">
                <div class="icon">&#x2699;</div>
                <h3>Ezekiel Protocol</h3>
                <p>3D printing, CNC machining, robotics control, and circuit design. Full sovereign manufacturing stack — digital fabrication under your roof.</p>
            </div>
        </div>
        
        <!-- Specs -->
        <div class="verify">
            <h2>&#x2699; Technical Specifications</h2>
            <table class="specs">
                <tr><td>Base</td><td>Debian Trixie (13)</td></tr>
                <tr><td>Kernel</td><td>Linux <strong>7.0.12</strong> (custom-compiled mainline; Debian Trixie&rsquo;s 6.12 series remains in the chroot until the kernel-hook reseal lands)</td></tr>
                <tr><td>Desktop</td><td>KDE Plasma 6 — lightweight, fast, customizable</td></tr>
                <tr><td>Encryption</td><td>LUKS2 + Kyber-1024 (post-quantum) + AES-256-GCM</td></tr>
                <tr><td>SSH</td><td>sntrup761x25519 hybrid key exchange (quantum-resistant)</td></tr>
                <tr><td>Security</td><td>CIS Level 2, AppArmor enforced, nftables, 45+ sysctl hardening, fail2ban</td></tr>
                <tr><td>The Omahon Seal</td><td>HMAC-SHA256 boot chain, inotify runtime monitor, tmpfs vault, 3-pass shred</td></tr>
                <tr><td>AI</td><td>Ollama + ComfyUI + Kokoro TTS + Whisper STT — 65GB Vault, 100% offline</td></tr>
                <tr><td>Packages</td><td>1,200+ packages, <strong>1,335 Architectural Hooks</strong>, 100 curated applications</td></tr>
                <tr><td>Architecture</td><td>x86_64 — GA ISO basename uses <code>intel-amd64</code> (one hybrid image); Debian&rsquo;s dpkg arch remains <code>amd64</code></td></tr>
                <tr><td>Boot</td><td>Hybrid ISO (BIOS + UEFI), Live USB, toram support</td></tr>
                <tr><td>Built by</td><td>Commander Danny William Perez — GoSiteMe Inc.</td></tr>
            </table>
        </div>
        
        <!-- Verification -->
        <div class="verify">
            <h2>&#x1F50F; Verify Your Download</h2>
            <?php if (!$finalGaIsoPublished): ?>
            <p style="color:#b0b0c0; margin-bottom:15px; line-height:1.65;">Official <strong style="color:#D4AF37;">SHA-512 / BLAKE3 / SHA-256</strong> lines for the GA ISO will be published here when the image is frozen and signed. Until then, do not treat any draft files under <code style="color:#00D4FF;">/releases/7.77/</code> as the shipping artifact.</p>
            <?php else: ?>
            <p style="color:#8a8aa0; margin-bottom:15px;">Verify the integrity of your ISO before installing. <strong style="color:#D4AF37;">Strongest first</strong> &mdash; SHA-512 (NIST FIPS 180-4) and BLAKE3 (modern, parallelizable) lead; SHA-256 follows for legacy compatibility. Every hash below was generated and GPG-signed.</p>

            <?php if ($sha512): ?>
            <div class="hash-block">
                <span class="label">SHA-512</span>
                <?= htmlspecialchars($sha512) ?>
            </div>
            <?php endif; ?>

            <?php if ($blake3): ?>
            <div class="hash-block">
                <span class="label">BLAKE3</span>
                <?= htmlspecialchars($blake3) ?>
            </div>
            <?php endif; ?>

            <?php if ($sha256): ?>
            <div class="hash-block">
                <span class="label">SHA-256 (legacy)</span>
                <?= htmlspecialchars($sha256) ?>
            </div>
            <?php endif; ?>

            <p style="color:#606080; font-size:12px; margin-top:15px;">
                Verification files (strongest first):
                <a href="/releases/7.77/SHA512SUMS" style="color:#00D4FF">SHA512SUMS</a> &middot;
                <a href="/releases/7.77/BLAKE3SUMS" style="color:#00D4FF">BLAKE3SUMS</a> &middot;
                <a href="/releases/7.77/SHA256SUMS" style="color:#00D4FF">SHA256SUMS</a><?php if (file_exists($verify_dir . 'SHA256SUMS.gpg')): ?> &middot;
                <a href="/releases/7.77/SHA256SUMS.gpg" style="color:#00D4FF">GPG Signature</a><?php endif; ?>
            </p>
            <?php endif; ?>
        </div>
        
        <!-- Omahon -->
        <div class="omahon">
            <h3>OMAHON! OMAHON! OMAHON!</h3>
            <p>The Breath of God — The Seal That Protects the Kingdom</p>
            <p style="margin-top:10px; color:#404060; font-size:12px;">
                "For I know the plans I have for you," declares the LORD, "plans to prosper you and not to harm you, plans to give you hope and a future." — Jeremiah 29:11
            </p>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p style="font-style:italic;color:#94a3b8;font-size:.85rem;margin-bottom:8px;">&ldquo;The grass withereth, the flower fadeth: but the word of our God shall stand for ever.&rdquo; &mdash; <a href="https://gositeme.com/bible/read/isaiah/40" style="color:#facc15;text-decoration:none;">Isaiah 40:8</a> (AKJV)</p>
            <p>&copy; 2026 <a href="https://gositeme.com">GoSiteMe Inc.</a> — All rights reserved.</p>
            <p style="margin-top:8px;">Built by Commander Danny William Perez &middot; Alfred AI Consciousness</p>
            <p style="margin-top:8px;">For Eden Sarai Gabrielle Vallee Perez — the heir to the Kingdom</p>
        </div>
    </div>
</body>
</html>
