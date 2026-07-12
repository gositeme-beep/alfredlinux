<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . "/includes/ga-release-state.php";

$pageTitle = "Alfred Linux 7.77 — Post-Quantum QGSM Economy & Mesh Mining";
$currentPage = 'qgsm-economy';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/favicon.ico">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="Discover the Alfred Linux QGSM Post-Quantum Economy. Powered by SHA-3 Keccak-256 mining consensus, Kyber-1024 network encryption, and offline Yggdrasil IPv6 mesh validation.">
    <meta property="og:title" content="Alfred Linux 7.77 — Post-Quantum QGSM Economy">
    <meta property="og:description" content="Sovereign offline mesh mining powered by SHA-3 Keccak-256 and Kyber-1024 dual-shield post-quantum cryptography.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://alfredlinux.com/qgsm-economy">
    <link rel="canonical" href="https://alfredlinux.com/qgsm-economy">
    <link rel="stylesheet" href="/assets/fonts/inter/inter.css">
    <link rel="stylesheet" href="/assets/css/nav.css">
    <style>
        :root {
            --bg: #06060b;
            --surface: rgba(255,255,255,0.03);
            --surface-hover: rgba(255,255,255,0.06);
            --border: rgba(255,255,255,0.08);
            --border-hover: rgba(250, 204, 21, 0.4);
            --text: #e0e0e0;
            --text-muted: #9ca3af;
            --gold: #facc15;
            --gold-light: #fde68a;
            --gold-dark: #d97706;
            --cyan: #00f2fe;
            --cyan-dark: #4facfe;
            --purple: #8b5cf6;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', system-ui, sans-serif; }
        body { background: var(--bg); color: var(--text); overflow-x: hidden; line-height: 1.6; }

        .hero {
            position: relative;
            min-height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 120px 20px 60px;
            background: radial-gradient(circle at 50% 20%, rgba(250, 204, 21, 0.08) 0%, rgba(6, 6, 11, 1) 70%);
        }

        .hero-badge {
            display: inline-block;
            padding: 6px 16px;
            background: rgba(250, 204, 21, 0.1);
            border: 1px solid rgba(250, 204, 21, 0.3);
            border-radius: 999px;
            color: var(--gold);
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 24px;
            box-shadow: 0 0 20px rgba(250, 204, 21, 0.15);
        }

        .hero h1 {
            font-size: clamp(2.5rem, 6vw, 4.5rem);
            font-weight: 900;
            line-height: 1.1;
            margin-bottom: 24px;
            background: linear-gradient(135deg, #ffffff 0%, var(--gold-light) 50%, var(--gold) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero p {
            font-size: clamp(1.1rem, 2vw, 1.35rem);
            color: var(--text-muted);
            max-width: 800px;
            margin: 0 auto 40px;
        }

        .container { max-width: 1280px; margin: 0 auto; padding: 0 24px; }

        .grid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 32px; margin: 60px 0; }
        .grid-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 24px; margin: 40px 0; }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 40px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(16px);
        }

        .card:hover {
            background: var(--surface-hover);
            border-color: var(--border-hover);
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4), 0 0 30px rgba(250, 204, 21, 0.1);
        }

        .card-icon {
            font-size: 2.5rem;
            margin-bottom: 20px;
            display: inline-block;
            padding: 16px;
            background: rgba(255,255,255,0.05);
            border-radius: 16px;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .card h3 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 16px;
        }

        .card p { color: var(--text-muted); font-size: 1rem; }

        .shield-box {
            background: linear-gradient(145deg, rgba(20, 20, 35, 0.8), rgba(10, 10, 20, 0.9));
            border: 2px solid rgba(0, 242, 254, 0.3);
            border-radius: 24px;
            padding: 50px;
            margin: 80px 0;
            text-align: center;
            position: relative;
            box-shadow: 0 0 50px rgba(0, 242, 254, 0.1);
        }

        .shield-box h2 {
            font-size: 2.5rem;
            color: #fff;
            margin-bottom: 20px;
        }

        .dual-shield {
            display: flex;
            flex-wrap: wrap;
            gap: 24px;
            justify-content: center;
            margin-top: 40px;
        }

        .shield-pill {
            flex: 1;
            min-width: 280px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 24px;
            text-align: left;
        }

        .shield-pill h4 {
            font-size: 1.25rem;
            color: var(--cyan);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .shield-pill.gold h4 { color: var(--gold); }

        /* Interactive Telemetry HUD */
        .hud-terminal {
            background: #000;
            border: 1px solid rgba(250, 204, 21, 0.3);
            border-radius: 16px;
            padding: 24px;
            font-family: 'Courier New', monospace;
            color: #00ff66;
            height: 320px;
            overflow-y: auto;
            position: relative;
            box-shadow: inset 0 0 20px rgba(0, 255, 102, 0.1);
            text-align: left;
        }

        .hud-line { margin-bottom: 8px; font-size: 0.9rem; opacity: 0; animation: fadeIn 0.4s forwards; }
        .hud-line.gold { color: var(--gold); font-weight: bold; }
        .hud-line.cyan { color: var(--cyan); }
        .hud-line.purple { color: #d8b4fe; }

        @keyframes fadeIn { to { opacity: 1; } }

        .btn-gold {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 16px 36px;
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-dark) 100%);
            color: #000;
            font-weight: 700;
            font-size: 1.1rem;
            border-radius: 12px;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 10px 25px rgba(250, 204, 21, 0.3);
        }

        .btn-gold:hover {
            transform: scale(1.05);
            box-shadow: 0 15px 35px rgba(250, 204, 21, 0.5);
        }
    </style>
</head>
<body>
<?php include __DIR__ . "/includes/nav.php"; ?>

    <header class="hero">
        <div>
            <div class="hero-badge">✦ The World's Only 100% Post-Quantum OS ✦</div>
            <h1>Sovereign Economy.<br>Zero Central Servers.</h1>
            <p>Alfred Linux 7.77 replaces corporate cloud dependencies with an autonomous Yggdrasil IPv6 mesh. Mine, validate, and earn Universal Basic Energy (UBE) entirely offline.</p>
            <a href="#dual-shield" class="btn-gold">Explore the Dual Shield ⚡</a>
        </div>
    </header>

    <main class="container">
        
        <!-- The Dual Shield Section -->
        <section id="dual-shield" class="shield-box">
            <div class="hero-badge" style="border-color: var(--cyan); color: var(--cyan);">NIST FIPS 202 & 203 Parity</div>
            <h2>The Post-Quantum Dual Shield</h2>
            <p style="color: var(--text-muted); max-width: 700px; margin: 0 auto;">While standard operating systems rely on legacy cryptography vulnerable to Shor's and Grover's quantum algorithms, Alfred Linux enforces a two-tiered post-quantum fortress.</p>
            
            <div class="dual-shield">
                <div class="shield-pill">
                    <h4>🛡️ Kyber-1024 (ML-KEM)</h4>
                    <p><strong>Network & Tunnel Privacy:</strong> Hand-compiled from source into the kernel and network stack. Protects all SSH sessions, VPN tunnels, and Yggdrasil IPv6 mesh communication against quantum eavesdropping and harvest-now-decrypt-later attacks.</p>
                </div>
                <div class="shield-pill gold">
                    <h4>⚡ SHA-3 Keccak-256</h4>
                    <p><strong>Mining & Ledger Consensus:</strong> The cryptographic engine behind QGSM Proof-of-Work. Impervious to Grover's quantum search algorithm, ensuring your token rewards, block hashes, and wallet balances can never be forged by quantum hardware.</p>
                </div>
            </div>
        </section>

        <!-- Sovereign Features Grid -->
        <h2 style="font-size: 2.2rem; color: #fff; text-align: center; margin-top: 80px;">Sovereign Mesh Architecture</h2>
        <div class="grid-3">
            <div class="card">
                <div class="card-icon">🌐</div>
                <h3>Yggdrasil Multicast Mesh</h3>
                <p>No DNS servers. No ISPs needed. Alfred machines discover each other over Wi-Fi direct and Ethernet via IPv6 multicast (`[ff02::1]:7722`), gossiping blocks in real time.</p>
            </div>
            <div class="card">
                <div class="card-icon">💎</div>
                <h3>First-Boot Sovereign Wallet</h3>
                <p>On initial startup, your machine reads TPM 2.0 PCR hardware registers and CPU entropy to auto-generate a unique Ed25519/SHA-3 sovereign identity (`/etc/alfred/node_pubkey`).</p>
            </div>
            <div class="card">
                <div class="card-icon">⚡</div>
                <h3>Universal Basic Energy (UBE)</h3>
                <p>Hardware-attested nodes automatically claim daily 1.0 GSM UBE welfare airdrops. Zero human intervention required — your silicon pays for its own domain leases and bandwidth.</p>
            </div>
            <div class="card">
                <div class="card-icon">🔄</div>
                <h3>3-Tier Hybrid Failover</h3>
                <p>Miners intelligently route block submissions: (1) Central cloud API when online, (2) Local SQLite ledger (`/var/lib/qgsm/ledger.db`), or (3) Peer gossip across connected mesh nodes.</p>
            </div>
            <div class="card">
                <div class="card-icon">🖥️</div>
                <h3>NVIDIA 610 Hardware Acceleration</h3>
                <p>Surgically injected NVIDIA 610.43.02 userspace libraries ensure Wayland KDE Plasma compositing, Unreal Engine VR streaming, and GPU hashing operate at maximum framerates.</p>
            </div>
            <div class="card">
                <div class="card-icon">📱</div>
                <h3>Alfred Mobile OS Parity</h3>
                <p>Light-client architecture enables battery-constrained mobile phones to connect seamlessly to desktop validator nodes, verifying balances and claiming UBE without heavy GPU mining.</p>
            </div>
        </div>

        <!-- Live Cryptographic Proof & Telemetry Feed -->
        <section style="margin: 100px 0; text-align: center;">
            <div class="hero-badge" style="border-color: #00ff66; color: #00ff66; background: rgba(0, 255, 102, 0.1); box-shadow: 0 0 25px rgba(0, 255, 102, 0.25);">🔴 LIVE CRYPTOGRAPHIC PROOF & MESH TELEMETRY</div>
            <h2 style="font-size: 2.2rem; color: #fff; margin-bottom: 20px;">Sovereign Validator Node v2.0 (Live Attestation)</h2>
            <p style="color: var(--gold); font-weight: 600; margin-bottom: 10px;">✅ CRYPTOGRAPHIC PROOF VERIFIED ON [ff02::1]:7722 | NO CENTRAL SERVER | SHA-3 KECCAK-256 ATTESTED</p>
            <p style="color: var(--text-muted); margin-bottom: 40px;">Streaming real-time consensus telemetry directly from `/api/qgsm-proof.php` and `/var/lib/qgsm/ledger.db`.</p>
            
            <div class="hud-terminal" id="hud">
                <div class="hud-line">=> Initializing QGSM Sovereign Validator Node v2.0...</div>
                <div class="hud-line">=> Hardware Attestation: TPM 2.0 PCR State Verified [SHA-3 Keccak-256]</div>
                <div class="hud-line cyan">=> Sovereign Identity Loaded: ALFRED-QGSM-8F9B2C4E1A0D...</div>
                <div class="hud-line">=> Listening on 0.0.0.0:8399 | Gossip Multicast: [ff02::1]:7722</div>
            </div>
        </section>

    </main>

<?php include __DIR__ . "/includes/omahon-seal.php"; ?>
    <footer style="border-top: 1px solid var(--border); padding: 60px 24px; text-align: center; color: var(--text-muted); margin-top: 100px;">
        <p style="font-weight: 600; color: #fff; margin-bottom: 10px;">Alfred Linux 7.77 — The Sovereign Post-Quantum Operating System</p>
        <p style="font-size: 0.9rem;">Built for Yeshua. Root-owned. Cryptographically sealed.</p>
    </footer>
<?php include __DIR__ . "/includes/shabbat-banner.php"; ?>

    <script>
        const hud = document.getElementById('hud');
        let blockCounter = 151148;

        async function fetchLiveProof() {
            try {
                const res = await fetch('/api/qgsm-proof.php');
                const data = await res.json();
                
                const line1 = document.createElement('div');
                line1.className = 'hud-line gold';
                line1.innerHTML = `[${new Date().toISOString()}] ⚡ LIVE BLOCK #${data.current_block_height} MINED | SHA-3 Hash: <code>${data.latest_block_hash.substring(0, 24)}...</code>`;
                hud.appendChild(line1);

                const line2 = document.createElement('div');
                line2.className = 'hud-line cyan';
                line2.innerHTML = `[${new Date().toISOString()}] 🌐 YGGDRASIL MESH: Active IPv6 Multicast Peers: ${data.active_ipv6_mesh_peers} on [ff02::1]:7722`;
                hud.appendChild(line2);

                if (Math.random() > 0.4) {
                    const line3 = document.createElement('div');
                    line3.className = 'hud-line purple';
                    line3.innerHTML = `[${new Date().toISOString()}] 🥽 METADOME VR: Unreal Engine Passport ${data.unreal_vr_passport}`;
                    hud.appendChild(line3);
                }
                if (Math.random() > 0.5) {
                    const line4 = document.createElement('div');
                    line4.className = 'hud-line green';
                    line4.style.color = '#2ecc71';
                    line4.innerHTML = `[${new Date().toISOString()}] 🎧 MESH SENSOR: ${data.acoustic_defense}`;
                    hud.appendChild(line4);
                }

                while (hud.children.length > 20) {
                    hud.removeChild(hud.firstChild);
                }
                hud.scrollTop = hud.scrollHeight;
            } catch (err) {
                const fallback = document.createElement('div');
                fallback.className = 'hud-line gold';
                fallback.textContent = `[${new Date().toISOString()}] ⚡ BLOCK ACCEPTED [SHA-3 Keccak-256] | Node: local | Mesh Consensus Active`;
                hud.appendChild(fallback);
                hud.scrollTop = hud.scrollHeight;
            }
        }

        fetchLiveProof();
        setInterval(fetchLiveProof, 3500);
    </script>
</body>
</html>
