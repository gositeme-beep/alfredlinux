<?php
/**
 * Alfred Linux — Cyber-Warfare Capabilities
 */
$currentPage = 'cyber-capabilities';
require_once __DIR__ . '/includes/ga-release-state.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cyber-Warfare Capabilities | Alfred Linux</title>
    <meta name="description" content="The 5 Layers of Military-Grade Cyber-Warfare capabilities natively integrated into Alfred Linux.">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="stylesheet" href="/assets/fonts/inter/inter.css">
    <link rel="stylesheet" href="/assets/css/nav.css">
    <style>
        :root {
            --bg: #06060b;
            --surface: rgba(255,255,255,0.03);
            --surface-hover: rgba(255,255,255,0.06);
            --border: rgba(255,255,255,0.06);
            --text: #e0e0e0;
            --text-muted: #9ca3af;
            --accent: #ef4444;
            --accent-light: #f87171;
            --gold: #facc15;
            --blue: #3b82f6;
            --green: #10b981;
            --purple: #8b5cf6;
            --cyan: #06b6d4;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            line-height: 1.6;
        }
        nav { position: relative; z-index: 100; }

        .hero {
            padding: 8rem 2rem 4rem; text-align: center; position: relative;
            background: radial-gradient(ellipse at 50% 15%, rgba(239,68,68,0.1) 0%, transparent 50%);
        }
        .hero h1 {
            font-size: clamp(2.5rem, 5vw, 4.5rem); font-weight: 900; margin-bottom: 1.5rem;
            background: linear-gradient(135deg, #fff, var(--accent-light), var(--accent));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .hero p {
            font-size: 1.2rem; color: var(--text-muted); max-width: 900px; margin: 0 auto 2rem; line-height: 1.8;
        }
        .hero-badge {
            display: inline-flex; align-items: center; gap: 0.6rem;
            padding: 0.5rem 1.5rem; border-radius: 999px;
            background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3);
            font-size: 0.85rem; font-weight: 700; color: var(--accent-light);
            text-transform: uppercase; margin-bottom: 2rem;
            box-shadow: 0 0 20px rgba(239,68,68,0.2);
        }

        .section { padding: 6rem 2rem; max-width: 1400px; margin: 0 auto; perspective: 2000px; }
        
        .phase-container { display: flex; flex-direction: column; gap: 5rem; }
        
        .phase-card {
            display: grid;
            grid-template-columns: 1fr 1.2fr 1.5fr;
            gap: 3rem;
            padding: 3rem;
            border-radius: 24px;
            background: var(--surface);
            border: 1px solid var(--border);
            position: relative;
            transform-style: preserve-3d;
            transition: box-shadow 0.3s, border-color 0.3s;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        .phase-card:hover {
            box-shadow: 0 30px 60px rgba(0,0,0,0.6);
            border-color: rgba(255,255,255,0.2);
        }
        
        @media (max-width: 1200px) {
            .phase-card { grid-template-columns: 1fr 1fr; }
            .phase-image { display: none !important; }
        }
        @media (max-width: 900px) {
            .phase-card { grid-template-columns: 1fr; gap: 2rem; }
        }

        .phase-header {
            display: flex;
            flex-direction: column;
            justify-content: center;
            transform: translateZ(40px);
        }
        
        .phase-number {
            font-size: 6rem;
            font-weight: 900;
            line-height: 1;
            margin-bottom: 0.5rem;
            opacity: 0.15;
            background: linear-gradient(135deg, #fff, transparent);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            transform: translateZ(20px);
        }

        .phase-title { font-size: 2.2rem; color: #fff; font-weight: 800; margin-bottom: 1rem; }
        .phase-desc { color: var(--text-muted); font-size: 1.1rem; line-height: 1.7; }

        .phase-image {
            display: flex;
            justify-content: center;
            align-items: center;
            transform: translateZ(80px);
        }
        .phase-image img {
            max-width: 100%;
            height: auto;
            border-radius: 16px;
            box-shadow: 0 30px 60px rgba(0,0,0,0.6);
            border: 1px solid rgba(255,255,255,0.1);
        }

        .phase-tools {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.5rem;
            align-content: center;
            transform: translateZ(50px);
        }

        .tool-pill {
            background: rgba(0,0,0,0.5);
            border: 1px solid var(--border);
            padding: 1.5rem;
            border-radius: 16px;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            transition: all 0.2s;
            position: relative;
            overflow: hidden;
        }
        .tool-pill::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 4px; height: 100%;
            background: var(--accent-light);
            opacity: 0.5;
            transition: 0.3s;
        }
        .tool-pill:hover::before { opacity: 1; }
        .tool-pill:hover {
            background: rgba(255,255,255,0.05);
            border-color: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }
        .tool-name { color: #fff; font-weight: 700; font-size: 1.1rem; }
        .tool-cat { color: var(--accent-light); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; }

        .watchmen-section {
            margin-top: 8rem;
            padding: 6rem 3rem;
            background: linear-gradient(180deg, rgba(6,6,11,1) 0%, rgba(20,6,6,0.8) 100%);
            border-radius: 32px;
            border: 1px solid rgba(239,68,68,0.2);
            box-shadow: 0 0 100px rgba(239,68,68,0.05) inset;
            position: relative;
            overflow: hidden;
        }
        .watchmen-header { text-align: center; margin-bottom: 5rem; }
        .watchmen-header h2 { font-size: clamp(2.5rem, 4vw, 3.5rem); font-weight: 900; color: #fff; margin-bottom: 1rem; }
        .watchmen-header p { font-size: 1.2rem; color: var(--accent-light); max-width: 800px; margin: 0 auto; line-height: 1.8; }
        
        .scenario-grid { display: grid; grid-template-columns: 1fr; gap: 4rem; }
        
        .scenario-block {
            display: grid;
            grid-template-columns: 1fr 3fr;
            gap: 3rem;
            align-items: center;
        }
        @media (max-width: 900px) {
            .scenario-block { grid-template-columns: 1fr; gap: 1.5rem; }
        }

        .scenario-verse {
            font-family: serif;
            font-size: 1.4rem;
            font-style: italic;
            color: var(--gold);
            line-height: 1.6;
            padding-right: 2rem;
            border-right: 2px solid rgba(250,204,21,0.3);
        }
        .scenario-verse cite { display: block; font-size: 0.9rem; font-style: normal; font-family: 'Inter', sans-serif; margin-top: 1rem; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 0.1em; }
        
        .scenario-content h4 { font-size: 1.8rem; color: #fff; margin-bottom: 1rem; font-weight: 800; display: flex; align-items: center; gap: 1rem; }
        .scenario-content h4 span { font-size: 0.9rem; padding: 0.3rem 0.8rem; background: rgba(255,255,255,0.1); border-radius: 999px; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); }
        .scenario-content .context { font-size: 1.1rem; color: var(--text-muted); margin-bottom: 1.5rem; line-height: 1.7; }
        .scenario-content .action {
            background: rgba(0,0,0,0.4);
            border-left: 3px solid var(--accent);
            padding: 1.5rem 2rem;
            font-size: 1.05rem;
            color: #fff;
            line-height: 1.7;
            border-radius: 0 16px 16px 0;
        }
        .scenario-content .action strong { color: var(--accent-light); }

        footer { padding: 4rem 2rem; background: var(--bg); text-align: center; border-top: 1px solid var(--border); margin-top: 4rem; color: var(--text-muted); }
    </style>
</head>
<body>
<?php @include __DIR__ . "/includes/seal-banner.php"; ?>
<?php include __DIR__ . '/includes/nav.php'; ?>

<section class="hero">
    <div class="hero-badge">🛡️ Military-Grade Arsenal</div>
    <h1>Cyber-Warfare Capabilities</h1>
    <p>Alfred Linux is engineered as a weaponized operating system. Discover the five strategic layers of intelligence, exploitation, and deception integrated natively into the core.</p>
</section>

<section class="section">
    <div class="phase-container">
        
        <!-- PHASE 1 -->
        <div class="phase-card" data-tilt data-tilt-max="3" data-tilt-speed="400" data-tilt-perspective="1500" style="--accent-light: var(--green);">
            <div class="phase-header">
                <div class="phase-number">01</div>
                <h3 class="phase-title">Advanced Persistent Threat Defense</h3>
                <p class="phase-desc">The foundation of the operating system is fortified with extreme network monitoring, intrusion prevention, and decentralized global routing. The host protects itself before striking outwards.</p>
            </div>
            <div class="phase-image">
                <img src="/assets/img/3d/cyber_defense_3d_1782193418580.png" alt="Defense 3D" style="box-shadow: 0 30px 60px rgba(16,185,129,0.3);">
            </div>
            <div class="phase-tools">
                <div class="tool-pill"><span class="tool-cat">NDR</span><span class="tool-name">Zeek & Suricata</span></div>
                <div class="tool-pill"><span class="tool-cat">EDR</span><span class="tool-name">Wazuh Agent</span></div>
                <div class="tool-pill"><span class="tool-cat">Mesh Network</span><span class="tool-name">Yggdrasil</span></div>
                <div class="tool-pill"><span class="tool-cat">Firewalling</span><span class="tool-name">OpenSnitch & Fail2Ban</span></div>
            </div>
        </div>

        <!-- PHASE 2 -->
        <div class="phase-card" data-tilt data-tilt-max="3" data-tilt-speed="400" data-tilt-perspective="1500" style="--accent-light: var(--blue);">
            <div class="phase-header">
                <div class="phase-number">02</div>
                <h3 class="phase-title">Top Secret Intelligence & C2</h3>
                <p class="phase-desc">With the perimeter secured, Alfred Linux deploys elite Command & Control infrastructure, full packet capture arrays, and malware detonation sandboxes for threat hunting.</p>
            </div>
            <div class="phase-image">
                <img src="/assets/img/3d/cyber_intel_3d_1782193427307.png" alt="Intel 3D" style="box-shadow: 0 30px 60px rgba(59,130,246,0.3);">
            </div>
            <div class="phase-tools">
                <div class="tool-pill"><span class="tool-cat">Command & Control</span><span class="tool-name">Sliver</span></div>
                <div class="tool-pill"><span class="tool-cat">Packet Capture</span><span class="tool-name">Arkime</span></div>
                <div class="tool-pill"><span class="tool-cat">Intel Analysis</span><span class="tool-name">Cortex & MISP</span></div>
                <div class="tool-pill"><span class="tool-cat">Malware Detonation</span><span class="tool-name">CAPE Sandbox</span></div>
                <div class="tool-pill"><span class="tool-cat">Digital Forensics</span><span class="tool-name">Autopsy</span></div>
            </div>
        </div>

        <!-- PHASE 3 -->
        <div class="phase-card" data-tilt data-tilt-max="3" data-tilt-speed="400" data-tilt-perspective="1500" style="--accent-light: var(--gold);">
            <div class="phase-header">
                <div class="phase-number">03</div>
                <h3 class="phase-title">Deep Observability & SIGINT</h3>
                <p class="phase-desc">Total battlespace awareness. Monitor radio frequencies in the physical domain, and utilize eBPF hooks to gain perfect visibility into the deepest layers of the OS kernel.</p>
            </div>
            <div class="phase-image">
                <img src="/assets/img/3d/cyber_sigint_3d_1782193435876.png" alt="SIGINT 3D" style="box-shadow: 0 30px 60px rgba(250,204,21,0.3);">
            </div>
            <div class="phase-tools">
                <div class="tool-pill"><span class="tool-cat">Signals Intelligence</span><span class="tool-name">GNU Radio & SDRangel</span></div>
                <div class="tool-pill"><span class="tool-cat">Fleet Visibility</span><span class="tool-name">OSQuery & FleetDM</span></div>
                <div class="tool-pill"><span class="tool-cat">Threat Hunting</span><span class="tool-name">Velociraptor</span></div>
                <div class="tool-pill"><span class="tool-cat">eBPF Security</span><span class="tool-name">Tetragon</span></div>
                <div class="tool-pill"><span class="tool-cat">Multiplayer C2</span><span class="tool-name">Mythic</span></div>
            </div>
        </div>

        <!-- PHASE 4 -->
        <div class="phase-card" data-tilt data-tilt-max="3" data-tilt-speed="400" data-tilt-perspective="1500" style="--accent-light: var(--accent);">
            <div class="phase-header">
                <div class="phase-number">04</div>
                <h3 class="phase-title">Offensive Exploitation</h3>
                <p class="phase-desc">The arsenal shifts from intelligence to active engagement. Discover vulnerabilities across Enemy infrastructure and execute tactical network interception and exploitation.</p>
            </div>
            <div class="phase-image">
                <img src="/assets/img/3d/cyber_offensive_3d_1782193444942.png" alt="Offensive 3D" style="box-shadow: 0 30px 60px rgba(239,68,68,0.3);">
            </div>
            <div class="phase-tools">
                <div class="tool-pill"><span class="tool-cat">Exploitation Framework</span><span class="tool-name">Metasploit</span></div>
                <div class="tool-pill"><span class="tool-cat">Active Directory</span><span class="tool-name">Impacket</span></div>
                <div class="tool-pill"><span class="tool-cat">Tactical MITM</span><span class="tool-name">Responder & Bettercap</span></div>
                <div class="tool-pill"><span class="tool-cat">Vulnerability Scanner</span><span class="tool-name">OpenVAS</span></div>
                <div class="tool-pill"><span class="tool-cat">SecOps Orchestration</span><span class="tool-name">DefectDojo</span></div>
            </div>
        </div>

        <!-- PHASE 5 -->
        <div class="phase-card" data-tilt data-tilt-max="3" data-tilt-speed="400" data-tilt-perspective="1500" style="--accent-light: var(--purple);">
            <div class="phase-header">
                <div class="phase-number">05</div>
                <h3 class="phase-title">Stealth & Deception</h3>
                <p class="phase-desc">The final layer ensures complete operational invisibility. Route traffic through darknets, scrub metadata, and trap adversaries in elaborate, monitored honeypots.</p>
            </div>
            <div class="phase-image">
                <img src="/assets/img/3d/cyber_stealth_3d_1782193455357.png" alt="Stealth 3D" style="box-shadow: 0 30px 60px rgba(139,92,246,0.3);">
            </div>
            <div class="phase-tools">
                <div class="tool-pill"><span class="tool-cat">Extreme Anonymity</span><span class="tool-name">Tor, I2P & Proxychains</span></div>
                <div class="tool-pill"><span class="tool-cat">Dark Enclaves</span><span class="tool-name">Nebula</span></div>
                <div class="tool-pill"><span class="tool-cat">Plausible Deniability</span><span class="tool-name">VeraCrypt</span></div>
                <div class="tool-pill"><span class="tool-cat">Deception Honeypots</span><span class="tool-name">T-Pot & Cowrie</span></div>
                <div class="tool-pill"><span class="tool-cat">Anti-Forensics</span><span class="tool-name">Macchanger & MAT2</span></div>
            </div>
        </div>

    </div>
</section>

<section class="watchmen-section">
    <div class="watchmen-header">
        <h2>The Watchmen Directives</h2>
        <p>Practical deployment scenarios for the Kingdom of God. How the Watchmen utilize Alfred Linux to defend the flock, discern the spirits, and engage the adversary.</p>
    </div>
    
    <div class="scenario-grid">
        
        <!-- Scenario 1 -->
        <div class="scenario-block">
            <div class="scenario-verse">
                "But if the watchman see the sword come, and blow not the trumpet, and the people be not warned; if the sword come, and take any person from among them... his blood will I require at the watchman's hand."
                <cite>Ezekiel 33:6</cite>
            </div>
            <div class="scenario-content">
                <h4>The Watchman on the Wall <span>Phase 1: Defense</span></h4>
                <p class="context">A state-sponsored botnet begins a massive DDoS and brute-force campaign against the Kingdom's decentralized communication nodes.</p>
                <p class="action"><strong>The Action:</strong> The Watchman utilizes <strong>Zeek</strong> and <strong>Suricata</strong> to instantly detect the anomalous traffic patterns. <strong>Fail2Ban</strong> automatically begins dropping the hostile IPs, while the <strong>Yggdrasil</strong> mesh network dynamically reroutes the Kingdom's traffic around the attack surface, ensuring zero downtime for the brethren.</p>
            </div>
        </div>

        <!-- Scenario 2 -->
        <div class="scenario-block">
            <div class="scenario-verse">
                "Beloved, believe not every spirit, but try the spirits whether they are of God: because many false prophets are gone out into the world."
                <cite>1 John 4:1</cite>
            </div>
            <div class="scenario-content">
                <h4>The Discernment of Spirits <span>Phase 2: Intelligence</span></h4>
                <p class="context">An adversary attempts to infiltrate the network by sending a highly sophisticated, weaponized payload hidden within an innocent-looking document.</p>
                <p class="action"><strong>The Action:</strong> Before it can execute, the Watchman isolates the file and detonates it within the <strong>CAPE Sandbox</strong>. They use <strong>Autopsy</strong> to perform digital forensics, reverse-engineering the malware to uncover the adversary's true intent and feeding that intelligence into <strong>MISP</strong> to warn all other Kingdom nodes globally.</p>
            </div>
        </div>

        <!-- Scenario 3 -->
        <div class="scenario-block">
            <div class="scenario-verse">
                "For nothing is secret, that shall not be made manifest; neither any thing hid, that shall not be known and come abroad."
                <cite>Luke 8:17</cite>
            </div>
            <div class="scenario-content">
                <h4>Eyes in the Sky <span>Phase 3: SIGINT</span></h4>
                <p class="context">Hostile forces are coordinating physical and digital strikes, utilizing encrypted radio frequencies and attempting to inject rootkits into the host OS.</p>
                <p class="action"><strong>The Action:</strong> The Watchman deploys <strong>GNU Radio</strong> and <strong>SDRangel</strong> to intercept and decode the adversary's physical radio transmissions. Simultaneously, <strong>Tetragon's</strong> eBPF hooks monitor the deepest levels of the Linux kernel, ensuring the Watchman's own system remains absolutely pure and uncompromised.</p>
            </div>
        </div>

        <!-- Scenario 4 -->
        <div class="scenario-block">
            <div class="scenario-verse">
                "For the word of God is quick, and powerful, and sharper than any twoedged sword, piercing even to the dividing asunder of soul and spirit..."
                <cite>Hebrews 4:12</cite>
            </div>
            <div class="scenario-content">
                <h4>The Sword of the Spirit <span>Phase 4: Exploitation</span></h4>
                <p class="context">A critical vulnerability is discovered in the infrastructure of a known adversary who is actively preparing an attack against the Kingdom.</p>
                <p class="action"><strong>The Action:</strong> Operating under strict tactical directives, the Watchman uses <strong>OpenVAS</strong> to map the enemy's attack surface. Utilizing the <strong>Metasploit</strong> framework, they execute a precise, surgical strike to neutralize the threat infrastructure before the adversary can launch their attack.</p>
            </div>
        </div>

        <!-- Scenario 5 -->
        <div class="scenario-block">
            <div class="scenario-verse">
                "Behold, I send you forth as sheep in the midst of wolves: be ye therefore wise as serpents, and harmless as doves."
                <cite>Matthew 10:16</cite>
            </div>
            <div class="scenario-content">
                <h4>The Cloak of Invisibility <span>Phase 5: Stealth</span></h4>
                <p class="context">Watchmen operating in hostile, highly monitored territories (where the Kingdom is persecuted) need to communicate critical intelligence back to the command center.</p>
                <p class="action"><strong>The Action:</strong> The Watchman connects through the <strong>Nebula</strong> dark enclave, routing their traffic over <strong>Tor</strong> and <strong>I2P</strong> via <strong>Proxychains</strong>. Before transmitting any data, they use <strong>MAT2</strong> to scrub all metadata from their files, ensuring their physical location and identity remain entirely invisible to the enemy.</p>
            </div>
        </div>

    </div>
</section>

<!-- Vanilla Tilt JS for 3D interactions -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-tilt/1.8.0/vanilla-tilt.min.js"></script>

<footer>
    <p>&copy; <?php echo date('Y'); ?> GoSiteMe Inc. / Alfred OS. All rights reserved.</p>
</footer>
</body>
</html>
