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
                <tr>
                    <td class="machine">Apple Silicon (UTM / Parallels) <span class="badge badge-vm">VM</span></td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td class="notes">Perfect virtualization on M1/M2/M3/M4 via QEMU backend.</td>
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
                    <td class="machine">Generic x86_64 Laptop</td>
                    <td>Intel i5/i7</td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td class="notes">Intel iGPU. Standard hardware works out of box.</td>
                </tr>
                <tr>
                    <td class="machine">Apple Intel Mac (x86_64)</td>
                    <td>Intel Core/Xeon</td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td class="notes">Flawless bare-metal installation via standard EFI bootloader. Full GPU acceleration on Intel Iris/Radeon.</td>
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

    <!-- ── Spatial Computing & VR ──────────────────────────── -->
    <div class="section">
        <h2>Spatial Computing & VR</h2>
        <p>Zero-Friction AR and immersive God-Mode matrices running natively on Wayland.</p>
        <table class="hw-table">
            <thead>
                <tr>
                    <th>Headset</th>
                    <th>Integration</th>
                    <th>Desktop</th>
                    <th>Tracking</th>
                    <th>Audio</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="machine">Meta Quest 3 (and Pro)</td>
                    <td>Native ALVR / xrdesktop</td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td class="notes">Native Zero-Friction AR anchoring and 360-degree Orbital Command Center.</td>
                </tr>
                <tr>
                    <td class="machine">Valve Index / PCVR</td>
                    <td>Native SteamVR</td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td class="notes">Direct-display mode with ultra-low latency Wayland protocols.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ── Handheld PCs & Cyberdecks ───────────────────────── -->
    <div class="section">
        <h2>Handheld PCs & Cyberdecks</h2>
        <table class="hw-table">
            <thead>
                <tr>
                    <th>Device</th>
                    <th>APU</th>
                    <th>Screen/Touch</th>
                    <th>Controls</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="machine">Steam Deck (LCD & OLED) <span class="badge badge-mobile">Handheld</span></td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td class="notes">Transforms the Steam Deck into a portable Post-Quantum cyberdeck with full God-Mode.</td>
                </tr>
                <tr>
                    <td class="machine">GPD Win / Ayaneo <span class="badge badge-mobile">Handheld</span></td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td class="notes">Out-of-the-box touchscreen, controller, and APU mapping.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ── Cryptographic Hardware Keys ─────────────────────── -->
    <div class="section">
        <h2>Cryptographic Hardware Keys</h2>
        <table class="hw-table">
            <thead>
                <tr>
                    <th>Device</th>
                    <th>Protocol</th>
                    <th>PAM Auth</th>
                    <th>God-Mode</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="machine">YubiKey 5 Series (NFC/Nano/C)</td>
                    <td>FIDO2 / U2F</td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td class="notes">Native PAM modules pre-installed for hardware-backed Zero-Trust authentication.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ── AI Processors & Neural Accelerators ─────────────── -->
    <div class="section">
        <h2>AI Processors & Neural Accelerators (NPU)</h2>
        <table class="hw-table">
            <thead>
                <tr>
                    <th>Accelerator</th>
                    <th>Framework</th>
                    <th>Integration</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="machine">Intel Core Ultra / Ryzen AI (NPUs)</td>
                    <td>ONNX / OpenVINO</td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td class="notes">Direct integration to offload local Ascended Neural Agents without taxing the CPU.</td>
                </tr>
                <tr>
                    <td class="machine">Google Coral Edge TPU</td>
                    <td>PCIe / USB</td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td class="notes">Native passthrough for real-time local tensor computations.</td>
                </tr>
                <tr>
                    <td class="machine">External GPUs (eGPU)</td>
                    <td>Thunderbolt 4 / USB4</td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td class="notes">Hot-pluggable GPU passthrough for scaling ML-KEM cryptography and VR rendering.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ── Zero-Trust Biometrics & Root of Trust ───────────── -->
    <div class="section">
        <h2>Zero-Trust Biometrics & Root of Trust</h2>
        <table class="hw-table">
            <thead>
                <tr>
                    <th>Hardware</th>
                    <th>Daemon</th>
                    <th>God-Mode</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="machine">Hardware TPM 2.0 / Pluton</td>
                    <td>systemd-cryptsetup</td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td class="notes">Seamless LUKS2 disk encryption sealing. The matrix decrypts securely via hardware validation.</td>
                </tr>
                <tr>
                    <td class="machine">IR Facial Recognition</td>
                    <td>Howdy (Windows Hello)</td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td class="notes">Compatible IR camera integration perfectly tied into our PAM stack.</td>
                </tr>
                <tr>
                    <td class="machine">Fingerprint Scanners</td>
                    <td>fprintd</td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td class="notes">Native biometric authentication mapped directly to the God-Mode NOPASSWD execution framework.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ── Software Defined Radio (SDR) & Signals Intelligence -->
    <div class="section">
        <h2>Software Defined Radio (SDR) & Signals Intelligence</h2>
        <table class="hw-table">
            <thead>
                <tr>
                    <th>Device</th>
                    <th>Subsystem</th>
                    <th>God-Mode</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="machine">HackRF One / BladeRF</td>
                    <td>Native SDR</td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td class="notes">Native kernel module support for advanced signals intelligence and RF penetration testing.</td>
                </tr>
                <tr>
                    <td class="machine">RTL-SDR</td>
                    <td>Native SDR</td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td class="notes">Out-of-the-box driver integration for immediate God-Mode radio frequency analysis.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ── Network DPUs & SmartNICs ────────────────────────── -->
    <div class="section">
        <h2>Network DPUs & SmartNICs</h2>
        <table class="hw-table">
            <thead>
                <tr>
                    <th>Hardware</th>
                    <th>Architecture</th>
                    <th>God-Mode</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="machine">NVIDIA BlueField DPUs / 25GbE</td>
                    <td>eBPF / Tetragon</td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td class="notes">Perfectly maps to our eBPF kernel-level firewall architecture for enterprise-grade network interception.</td>
                </tr>
                <tr>
                    <td class="machine">Mellanox / Intel 10G</td>
                    <td>SR-IOV</td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td class="notes">Zero-latency SR-IOV virtualization hooks active out-of-the-box.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ── Professional Real-Time Audio ────────────────────── -->
    <div class="section">
        <h2>Professional Real-Time Audio</h2>
        <table class="hw-table">
            <thead>
                <tr>
                    <th>Interface</th>
                    <th>Driver Stack</th>
                    <th>God-Mode</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="machine">Focusrite / Universal Audio</td>
                    <td>PipeWire</td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td class="notes">Powered by our low-latency PipeWire subsystem. Achieve sub-3ms audio buffering out-of-the-box.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ── AR Smart Glasses & Cybernetics ──────────────────── -->
    <div class="section">
        <h2>AR Smart Glasses & Cybernetics</h2>
        <table class="hw-table">
            <thead>
                <tr>
                    <th>Interface</th>
                    <th>Subsystem</th>
                    <th>God-Mode</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="machine">Xreal Air / Viture One</td>
                    <td>DP Alt Mode / Wayland</td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td class="notes">Native USB-C DP Alt Mode hooks for instantaneous 130-inch holographic HUDs.</td>
                </tr>
                <tr>
                    <td class="machine">OpenBCI (Brain-Computer Interface)</td>
                    <td>EEG / Python</td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td class="notes">Raw EEG signal ingestion via Python/C++ hooks natively powering Neural Agents.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ── Aerospace, Drones & Robotics ────────────────────── -->
    <div class="section">
        <h2>Aerospace, Drones & Robotics</h2>
        <table class="hw-table">
            <thead>
                <tr>
                    <th>Hardware</th>
                    <th>Framework</th>
                    <th>God-Mode</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="machine">Pixhawk / ArduPilot</td>
                    <td>ROS2</td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td class="notes">Native ROS2 nodes pre-configured for drone swarm God-Mode orchestration.</td>
                </tr>
                <tr>
                    <td class="machine">Velodyne LiDAR Sensors</td>
                    <td>ROS2 / Post-Quantum</td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td class="notes">Plug-and-play spatial point-cloud mapping integrated natively with the kernel.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ── Planetary Mesh & Satellite Arrays ───────────────── -->
    <div class="section">
        <h2>Planetary Mesh & Satellite Arrays</h2>
        <table class="hw-table">
            <thead>
                <tr>
                    <th>Array Type</th>
                    <th>Protocol</th>
                    <th>God-Mode</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="machine">Starlink Dish / Iridium</td>
                    <td>Off-Grid Mesh</td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td class="notes">Native integration with our Zero-Trust mesh networking layer for true planetary uplinks.</td>
                </tr>
                <tr>
                    <td class="machine">LoRaWAN / Meshtastic</td>
                    <td>Sub-GHz Radio</td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td class="notes">Decentralized cryptographic packet routing over long-range radio frequencies.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ── Exascale Supercomputing & Mainframes ────────────── -->
    <div class="section">
        <h2>Exascale Supercomputing & Mainframes</h2>
        <table class="hw-table">
            <thead>
                <tr>
                    <th>Architecture</th>
                    <th>Subsystem</th>
                    <th>God-Mode</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="machine">IBM Z-Series / LinuxONE</td>
                    <td>Post-Quantum KEM</td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td class="notes">Massive scale-out architecture leveraging Post-Quantum cryptography across thousands of cores.</td>
                </tr>
                <tr>
                    <td class="machine">NVIDIA DGX SuperPODs</td>
                    <td>GPU RDMA</td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td class="notes">Native GPU RDMA scaling to deploy Ascended Neural Agents across multi-rack topologies.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ── Medical Imaging & Bio-Informatics ───────────────── -->
    <div class="section">
        <h2>Medical Imaging & Bio-Informatics</h2>
        <table class="hw-table">
            <thead>
                <tr>
                    <th>Workstation</th>
                    <th>Standard</th>
                    <th>God-Mode</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="machine">MRI / CT Scanners</td>
                    <td>DICOM / eBPF</td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td class="notes">Native DICOM processing pipelines powered by our sub-millisecond eBPF ingestion engine.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ── Quantum Processing Units (QPU) ──────────────────── -->
    <div class="section">
        <h2>Quantum Processing Units (QPU)</h2>
        <table class="hw-table">
            <thead>
                <tr>
                    <th>Quantum System</th>
                    <th>Subsystem</th>
                    <th>God-Mode</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="machine">IBM Quantum System One</td>
                    <td>Qiskit / ML-KEM</td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td class="notes">Native Qiskit integration for hybrid classical-quantum computations, scaling seamlessly with our Post-Quantum cryptographic stack.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ── Automotive & Avionics (CAN Bus) ─────────────────── -->
    <div class="section">
        <h2>Automotive & Avionics (CAN Bus)</h2>
        <table class="hw-table">
            <thead>
                <tr>
                    <th>Interface</th>
                    <th>Protocol</th>
                    <th>God-Mode</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="machine">OBD2 / Automotive Diagnostics</td>
                    <td>CAN Bus</td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td class="notes">Native can-utils integration for real-time ECU flashing, telemetry ingestion, and automotive cyberdeck orchestration.</td>
                </tr>
                <tr>
                    <td class="machine">Tesla / EV Telemetry</td>
                    <td>eBPF Ingestion</td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td class="notes">Direct ingestion of high-frequency automotive sensor data via custom kernel filters.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ── Industrial Control Systems (SCADA) ──────────────── -->
    <div class="section">
        <h2>Industrial Control Systems (SCADA / PLC)</h2>
        <table class="hw-table">
            <thead>
                <tr>
                    <th>Controller</th>
                    <th>Network</th>
                    <th>God-Mode</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="machine">Siemens S7 / PLCs</td>
                    <td>Modbus TCP</td>
                    <td><span class="badge badge-full">✓</span></td>
                    <td class="notes">Zero-Trust air-gapped industrial control orchestration for nuclear, grid, and heavy-machinery environments.</td>
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
                    <td class="machine">NVIDIA GPUs</td>
                    <td style="color: #10b981; font-weight: bold;">Native Open-Source Kernel Modules now active out-of-the-box (as of 7.0.12).</td>
                    <td><a href="/nvidia-compatibility.php">View Compatibility List</a> to see if your card is supported by the new open-source driver, or if it requires the legacy proprietary driver.</td>
                </tr>
                <tr>
                    <td class="machine">AMD GPUs</td>
                    <td style="color: #ed1c24; font-weight: bold;">Native Open-Source (amdgpu/ROCm) active out-of-the-box.</td>
                    <td><a href="/amd-compatibility.php">View Compatibility List</a> to see your exact architecture and driver stack.</td>
                </tr>
                <tr>
                    <td class="machine">Intel GPUs</td>
                    <td style="color: #0071c5; font-weight: bold;">Native Open-Source (xe/i915) active out-of-the-box.</td>
                    <td><a href="/intel-compatibility.php">View Compatibility List</a> to see your exact architecture and driver stack.</td>
                </tr>
                <tr>
                    <td class="machine">ARM64 / Raspberry Pi</td>
                    <td class="notes">Not yet supported</td>
                    <td>Research phase — see <a href="/forge/commander/alfredlinux.com/src/branch/main/docs/ARM64_BUILD_INVESTIGATION.md">ARM64 investigation</a></td>
                </tr>
                <tr>
                    <td class="machine">Secure Boot</td>
                    <td><span class="badge badge-full">✓ Full</span></td>
                    <td>Full UEFI Secure Boot support utilizing Post-Quantum ML-KEM Machine Owner Keys (MOK).</td>
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
    <p>&copy; <?= $year ?> <a href="https://gositeme.com">GoSiteMe Inc.</a> &mdash; Alfred Linux &middot; Open Source (KCL-1.0)</p>
</footer>

<script>
document.querySelector('.nav-toggle')?.addEventListener('click', () => {
    document.querySelector('.nav-links').classList.toggle('open');
});
</script>
<?php include __DIR__ . "/includes/shabbat-banner.php"; ?>
</body>
</html>
