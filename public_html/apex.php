<?php
/**
 * Apex Operations — Classified Intel
 */

require_once __DIR__ . '/includes/al-session.inc.php';
$currentPage = 'apex';
$al_lang = $_GET['lang'] ?? 'en';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    require_once __DIR__ . '/includes/seo.inc.php';
    alfred_seo('/apex', 'CLASSIFIED — Apex Operations', 'WARNING: CLEARANCE OMEGA REQUIRED. View extreme fringe God-Tier use cases for Alfred Linux.');
    ?>
    <link rel="stylesheet" href="/assets/vendor/fonts/space-grotesk/space-grotesk.css" />
    <link rel="stylesheet" href="/assets/vendor/fonts/jetbrains-mono/jetbrains-mono.css" />
    <link rel="stylesheet" href="/assets/vendor/fontawesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="/assets/css/nav.css">
    <style>
        /* APEX OPERATIONS TERMINAL THEME */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        @keyframes crt-flicker {
            0% { opacity: 0.95; }
            5% { opacity: 0.85; }
            10% { opacity: 0.95; }
            15% { opacity: 1; }
            100% { opacity: 1; }
        }

        @keyframes scanline {
            0% { transform: translateY(-100%); }
            100% { transform: translateY(100vh); }
        }

        @keyframes blink {
            0%, 49% { opacity: 1; }
            50%, 100% { opacity: 0; }
        }

        body { 
            background: #020202; 
            color: #00FF41; 
            font-family: 'JetBrains Mono', monospace; 
            line-height: 1.6; 
            overflow-x: hidden;
            animation: crt-flicker 0.15s infinite;
        }

        body::before {
            content: " ";
            display: block;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
            background: linear-gradient(rgba(18, 16, 16, 0) 50%, rgba(0, 0, 0, 0.25) 50%), linear-gradient(90deg, rgba(255, 0, 0, 0.06), rgba(0, 255, 0, 0.02), rgba(0, 0, 255, 0.06));
            z-index: 999;
            background-size: 100% 2px, 3px 100%;
            pointer-events: none;
        }

        .scanline {
            width: 100%;
            height: 10px;
            background: rgba(0, 255, 65, 0.1);
            position: fixed;
            z-index: 998;
            pointer-events: none;
            animation: scanline 8s linear infinite;
        }

        /* Nav Override for stealth */
        nav { border-bottom: 1px solid #00FF41 !important; background: #000 !important; }
        .nav-links a { color: #00FF41 !important; font-family: 'JetBrains Mono', monospace !important; text-transform: uppercase; }
        .nav-links a:hover { color: #fff !important; text-shadow: 0 0 10px #00FF41; }
        .logo { filter: sepia(1) hue-rotate(80deg) saturate(10) brightness(0.8); }

        .container { max-width: 1200px; margin: 0 auto; padding: 120px 24px 60px; position: relative; z-index: 10; }

        /* Header */
        .terminal-header { border: 2px solid #FF003C; padding: 20px; text-align: center; margin-bottom: 50px; background: rgba(255,0,60,0.05); }
        .terminal-header h1 { color: #FF003C; font-size: 2rem; text-transform: uppercase; letter-spacing: 4px; margin-bottom: 10px; }
        .terminal-header p { color: #FF003C; font-size: 1rem; }
        .cursor { display: inline-block; width: 10px; height: 1.2em; background: #00FF41; vertical-align: bottom; animation: blink 1s infinite; }

        /* Dossier Grid */
        .dossier-grid { display: flex; flex-direction: column; gap: 40px; }

        .dossier-block { 
            border: 1px solid #00FF41; 
            padding: 30px; 
            background: rgba(0,255,65,0.02);
            position: relative;
        }
        
        .dossier-block::before {
            content: '[DECRYPTED]';
            position: absolute;
            top: -12px;
            left: 20px;
            background: #020202;
            padding: 0 10px;
            font-size: 0.8rem;
            color: #00FF41;
            font-weight: bold;
        }

        .dossier-title { font-size: 1.5rem; color: #fff; margin-bottom: 15px; border-bottom: 1px dashed #00FF41; padding-bottom: 10px; text-transform: uppercase; }
        .dossier-persona { font-size: 0.9rem; color: #888; margin-bottom: 20px; }
        .dossier-content { margin-bottom: 25px; color: #b3ffb3; }
        
        /* Threat Assessment (Real World Stake) */
        .threat-assessment {
            border: 1px solid #FF003C;
            background: rgba(255,0,60,0.05);
            padding: 20px;
            margin-top: 20px;
        }
        .threat-title {
            color: #FF003C;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .threat-text { color: #ff9999; font-size: 0.95rem; }
        .threat-text strong { color: #FF003C; }

        .redacted {
            background: #00FF41;
            color: #00FF41;
            transition: all 0.2s;
            cursor: crosshair;
        }
        .redacted:hover {
            background: transparent;
            color: #fff;
        }

    </style>
</head>
<body>
    <div class="scanline"></div>

    <?php require_once __DIR__ . '/includes/nav.php'; ?>

    <div class="container">
        
        <div class="terminal-header">
            <h1><i class="fas fa-exclamation-triangle"></i> WARNING: CLEARANCE OMEGA REQUIRED</h1>
            <p>UNAUTHORIZED ACCESS WILL TRIGGER PROTOCOL ZERO. LOGGING IP ADDRESS... <span class="cursor"></span></p>
        </div>

        <div class="dossier-grid">

            <!-- SCENARIO 1 -->
            <div class="dossier-block">
                <div class="dossier-title">01. THE SILENT CONDUCTOR</div>
                <div class="dossier-persona">SUBJECT: Cognitive Synthesis / Neural Lace BCI Operator</div>
                <div class="dossier-content">
                    <p>For quadriplegic patients demanding total cognitive privacy, Alfred Linux acts as an air-gapped brain-computer interface hub. The ultra-low-latency kernel bypasses bloated OS bottlenecks to decode complex neural intentions with sub-millisecond precision, driving robotic exoskeletons without a single byte of telemetry ever touching the <span class="redacted">corporate cloud</span>.</p>
                </div>
                <div class="threat-assessment">
                    <div class="threat-title"><i class="fas fa-radiation"></i> CRITICAL THREAT ASSESSMENT</div>
                    <div class="threat-text">
                        <strong>The Crisis:</strong> A mega-corp mandates all BCI devices must connect to their telemetry cloud, selling patient thought-patterns to advertisers and introducing life-threatening lag.<br><br>
                        <strong>The Action:</strong> Operating purely on Alfred's localized bare-metal kernel, you seize absolute cognitive sovereignty, severing the cloud umbilical cord and moving freely in the real world with zero surveillance.
                    </div>
                </div>
            </div>

            <!-- SCENARIO 2 -->
            <div class="dossier-block">
                <div class="dossier-title">02. THE VOID AEGIS</div>
                <div class="dossier-persona">SUBJECT: Orbital Kinetic Defense Director</div>
                <div class="dossier-content">
                    <p>When tracking Near-Earth Objects (asteroids) from off-grid observatories, cloud-dependency is a death sentence. Alfred Linux allocates 99.9% of local hardware cycles to colossal N-body physics simulations, calculating exact kinetic impactor trajectories and deflection vectors in a mathematically perfect, zero-trust vacuum.</p>
                </div>
                <div class="threat-assessment">
                    <div class="threat-title"><i class="fas fa-radiation"></i> CRITICAL THREAT ASSESSMENT</div>
                    <div class="threat-text">
                        <strong>The Crisis:</strong> A massive cyber-attack paralyses the global defense grid just as an undocumented extinction-level asteroid breaches the lunar orbit.<br><br>
                        <strong>The Action:</strong> Operating from an isolated bunker, you use Alfred's absolute computational supremacy to calculate the deflection payload trajectory offline, manually triggering the launch and saving the planet while governments are locked out of their own systems.
                    </div>
                </div>
            </div>

            <!-- SCENARIO 3 -->
            <div class="dossier-block">
                <div class="dossier-title">03. THE GENESIS SEQUENCE</div>
                <div class="dossier-persona">SUBJECT: Unbound Genetic Cartographer</div>
                <div class="dossier-content">
                    <p>Operating entirely off-grid, rogue bio-engineers utilize Alfred's impenetrable Yggdrasil Sandbox to isolate hyper-complex CRISPR gene-editing models. By pulling from the 65GB local vault, massive high-fidelity protein-folding simulations are rendered entirely in the dark, turning isolated workstations into untethered biological genesis engines.</p>
                </div>
                <div class="threat-assessment">
                    <div class="threat-title"><i class="fas fa-radiation"></i> CRITICAL THREAT ASSESSMENT</div>
                    <div class="threat-text">
                        <strong>The Crisis:</strong> A highly engineered biological pathogen is released, but global pharmaceutical networks throttle the cure parameters to extort nations for access.<br><br>
                        <strong>The Action:</strong> You download the raw viral sequence into Alfred's Yggdrasil Sandbox, utilizing raw, unthrottled offline compute to fold the neutralizing protein in hours, releasing the open-source cure to global underground labs.
                    </div>
                </div>
            </div>

            <!-- SCENARIO 4 -->
            <div class="dossier-block">
                <div class="dossier-title">04. THE ICE WHISPERER</div>
                <div class="dossier-persona">SUBJECT: Sub-Glacial Seismologist</div>
                <div class="dossier-content">
                    <p>Stationed deep beneath the Antarctic ice sheet at -89°C, researchers run air-gapped tectonic monitoring. Alfred processes petabytes of micro-seismic telemetry from thousands of geophones. Utilizing its mathematically proven crash-proof kernel, it forecasts massive ice-shelf calving months in advance without a single reboot.</p>
                </div>
                <div class="threat-assessment">
                    <div class="threat-title"><i class="fas fa-radiation"></i> CRITICAL THREAT ASSESSMENT</div>
                    <div class="threat-text">
                        <strong>The Crisis:</strong> A massive thermal anomaly threatens to fracture a continent-sized ice shelf, but extreme solar radiation prevents any satellite uploads or cloud syncing.<br><br>
                        <strong>The Action:</strong> Relying on Alfred's unbreakable uptime, you process the tectonic telemetry purely on edge-compute, triggering a manual evacuation order to coastal megacities 72 hours before the resulting mega-tsunami hits.
                    </div>
                </div>
            </div>

            <!-- SCENARIO 5 -->
            <div class="dossier-block">
                <div class="dossier-title">05. THE PLASMA TAMER</div>
                <div class="dossier-persona">SUBJECT: Plasma Physics Confinement Commander</div>
                <div class="dossier-content">
                    <p>Inside localized Tokamak fusion reactors, a microsecond of lag equals a catastrophic plasma breach. Alfred’s preemptive kernel executes deterministic, real-time adjustments to magnetic coils under 0.8 microseconds, safely managing 150 million degrees Celsius without preemption interruptions.</p>
                </div>
                <div class="threat-assessment">
                    <div class="threat-title"><i class="fas fa-radiation"></i> CRITICAL THREAT ASSESSMENT</div>
                    <div class="threat-text">
                        <strong>The Crisis:</strong> The main grid fails, and the fusion reactor's commercial backup OS enters a "mandatory update loop," guaranteeing a catastrophic breach in 60 seconds.<br><br>
                        <strong>The Action:</strong> You hard-switch the containment array to the Alfred Linux terminal. Its zero-latency, bloat-free kernel instantly stabilizes the magnetic flux, quenching the plasma safely and preventing a localized nuclear disaster.
                    </div>
                </div>
            </div>

            <!-- SCENARIO 6 -->
            <div class="dossier-block">
                <div class="dossier-title">06. RESURRECTING LOST VOICES</div>
                <div class="dossier-persona">SUBJECT: Temporal Forensic Archaeologist</div>
                <div class="dossier-content">
                    <p>Deep in the desert, archaeologists use Alfred to run offline Lidar neural-renders of buried cities. Utilizing a locally hosted, offline LLM fine-tuned on extinct dialects, Alfred autonomously reconstructs ancient cuneiform from geometric anomalies, synthesizing phonetic soundscapes of dead languages in real-time.</p>
                </div>
                <div class="threat-assessment">
                    <div class="threat-title"><i class="fas fa-radiation"></i> CRITICAL THREAT ASSESSMENT</div>
                    <div class="threat-text">
                        <strong>The Crisis:</strong> A hostile militia is rapidly advancing on your dig site, intent on destroying a newly discovered ancient library containing the true origins of a major world religion.<br><br>
                        <strong>The Action:</strong> You use Alfred's highly optimized local AI to instantly Lidar-scan and cryptographically archive the tablets offline just minutes before the militia arrives, preserving the truth forever.
                    </div>
                </div>
            </div>

            <!-- SCENARIO 7 -->
            <div class="dossier-block">
                <div class="dossier-title">07. THE BACKYARD VOYAGER INTERCEPT</div>
                <div class="dossier-persona">SUBJECT: Deep-Space Telemetry Operator</div>
                <div class="dossier-content">
                    <p>Bypassing NASA’s Deep Space Network, amateur astronomers use Alfred’s peer-to-peer networking stack to pool and phase-align faint 8.4 GHz signals from Voyager 1. The native zero-latency DSP strips interstellar static, decoding deep-space hex data directly from a suburban garage.</p>
                </div>
                <div class="threat-assessment">
                    <div class="threat-title"><i class="fas fa-radiation"></i> CRITICAL THREAT ASSESSMENT</div>
                    <div class="threat-text">
                        <strong>The Crisis:</strong> A government agency classifies and blocks all incoming telemetry from a deep-space probe after it detects an anomalous, non-terrestrial signal.<br><br>
                        <strong>The Action:</strong> Utilizing Alfred's decentralized mesh, you and 500 amateur HAM operators phase-align your dishes, capturing and decoding the raw alien telemetry before the state can scrub the data.
                    </div>
                </div>
            </div>

            <!-- SCENARIO 8 -->
            <div class="dossier-block">
                <div class="dossier-title">08. THE LEVIATHAN CATCH</div>
                <div class="dossier-persona">SUBJECT: Dark-Net Threat Intelligence Hunter</div>
                <div class="dossier-content">
                    <p>Threat intelligence operatives deploy hyper-realistic corporate subnet clones within Alfred's hardware-level air-gapped Yggdrasil Sandbox. When state-sponsored zero-days infiltrate the honeypot, Yggdrasil’s stasis-lock freezes the payload, allowing hunters to reverse-engineer polymorphic malware line-by-line without risking the grid.</p>
                </div>
                <div class="threat-assessment">
                    <div class="threat-title"><i class="fas fa-radiation"></i> CRITICAL THREAT ASSESSMENT</div>
                    <div class="threat-text">
                        <strong>The Crisis:</strong> An apex-level, self-replicating zero-day worm is tearing through global hospital networks, shutting down critical life support systems.<br><br>
                        <strong>The Action:</strong> You lure the worm into Alfred\'s isolated sandbox. As it detonates, Yggdrasil freezes it in stasis. You extract the kill-switch code locally and broadcast the patch, stopping the worm dead in its tracks.
                    </div>
                </div>
            </div>

            <!-- SCENARIO 9 -->
            <div class="dossier-block">
                <div class="dossier-title">09. THE EDEN PROTOCOL</div>
                <div class="dossier-persona">SUBJECT: Atmospheric Terraforming Engineer</div>
                <div class="dossier-content">
                    <p>Inside sealed subterranean bio-domes, Alfred runs "AeroMind," an edge AI that balances mechanical CO2 scrubbers and biological algae-vats. The OS dynamically allocates resources to maximize photosynthetic oxygen yields and triggers real-time priority interrupts to sustain life in absolute off-grid isolation.</p>
                </div>
                <div class="threat-assessment">
                    <div class="threat-title"><i class="fas fa-radiation"></i> CRITICAL THREAT ASSESSMENT</div>
                    <div class="threat-text">
                        <strong>The Crisis:</strong> A surface nuclear event traps 500 people in a subterranean bunker. The primary life support AI, tethered to a destroyed cloud server, goes offline.<br><br>
                        <strong>The Action:</strong> You route all ventilation controls through Alfred Linux. Its edge-AI autonomously balances the algae-vat oxygen output against the rising CO2 levels, acting as the absolute mechanical lung for half a millennium.
                    </div>
                </div>
            </div>

            <!-- SCENARIO 10 -->
            <div class="dossier-block">
                <div class="dossier-title">10. THE ABSOLUTE ZERO IMPERATIVE</div>
                <div class="dossier-persona">SUBJECT: Cryogenic Stasis Technician</div>
                <div class="dossier-content">
                    <p>For long-term cryo-preservation, a system reboot means cellular death. Alfred orchestrates complex thermal algorithms and regulates nano-valve liquid nitrogen flows at -196°C. Its hot-swappable real-time redundancies guarantee zero latency and absolute survival for patients in vitrification.</p>
                </div>
                <div class="threat-assessment">
                    <div class="threat-title"><i class="fas fa-radiation"></i> CRITICAL THREAT ASSESSMENT</div>
                    <div class="threat-text">
                        <strong>The Crisis:</strong> A catastrophic power grid fluctuation hits a cryonics facility, causing the commercial OS managing the liquid nitrogen valves to trigger a fatal reboot sequence.<br><br>
                        <strong>The Action:</strong> Running on ultra-low-power battery backup, Alfred's crash-proof kernel seamlessly takes over the thermal array with zero dropped frames, sustaining vitrification and saving 100 lives from cellular crystallization.
                    </div>
                </div>
            </div>

        </div>
    </div>
    
    <?php require_once __DIR__ . '/includes/footer.inc.php'; ?>
    <script src="/assets/js/nav.js"></script>
</body>
</html>
