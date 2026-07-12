<?php
$currentPage = 'metadome-vr';
require_once __DIR__ . '/includes/ga-release-state.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/favicon.ico">
    <title>Metadome Spatial Matrix | Alfred Linux 7.77</title>
    <meta name="description" content="Turn Alfred Linux into a Spatial Matrix with Meta Quest 3, ALVR, and Monado OpenXR integration.">
    <link rel="stylesheet" href="/assets/fonts/inter/inter.css">
    <link rel="stylesheet" href="/assets/css/nav.css">
    <style>
        :root { --bg: #06060b; --text: #e0e0e0; --accent: #c084fc; --card-bg: rgba(255,255,255,0.03); }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); line-height: 1.6; margin: 0; }
        .hero { padding: 8rem 2rem 4rem; text-align: center; background: radial-gradient(ellipse at top, rgba(192,132,252,0.15) 0%, transparent 50%); }
        .hero h1 { font-size: 3.5rem; color: #fff; margin-bottom: 1rem; }
        .hero p { font-size: 1.2rem; color: #9ca3af; max-width: 800px; margin: 0 auto; }
        .container { max-width: 1000px; margin: 0 auto; padding: 2rem; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-top: 3rem; }
        .card { background: var(--card-bg); border: 1px solid rgba(192,132,252,0.3); padding: 2.5rem; border-radius: 16px; transition: transform 0.3s; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(192,132,252,0.15); }
        .card h3 { font-size: 1.5rem; color: #e9d5ff; margin-bottom: 1rem; }
        .card p { color: #d1d5db; }
        .code-block { background: #000; padding: 1.5rem; border-radius: 8px; font-family: monospace; color: #c084fc; margin-top: 2rem; border: 1px solid #333; }
    </style>
</head>
<body>
<?php include __DIR__ . '/includes/nav.php'; ?>

<section class="hero">
    <h1>Metadome Spatial Matrix</h1>
    <p>Your operating system is no longer confined to a flat screen. It lives in the room with you.</p>
</section>

<div class="container">
    <div class="grid">
        <div class="card">
            <h3>🥽 Monado OpenXR Integration</h3>
            <p>Alfred Linux natively compiles the Monado OpenXR runtime. No proprietary bridges. Your Wayland desktop environment composite is rendered flawlessly in 3D space.</p>
        </div>
        <div class="card">
            <h3>📡 ALVR Wireless Streaming</h3>
            <p>Put on your Meta Quest 3, and the ALVR daemon instantly connects to the OS. The rendering happens on your local GPU and beams to your headset with under 30ms of latency.</p>
        </div>
        <div class="card">
            <h3>🔊 Spatial Audio Mesh</h3>
            <p>When you ask Alfred a question, his voice originates from the exact 3D coordinates of his terminal. True positional audio using PipeWire and Haptic Feedback Engines.</p>
        </div>
    </div>

    <div class="code-block">
        $ alfred-metadome init<br>
        [OK] ALVR Daemon listening on port 9943...<br>
        [OK] Wayland compositor successfully attached to OpenXR session.
    </div>
</div>

</body>
</html>

<!-- VR Setup Guide - Added by Agent -->
<div class=container style=margin-top: 4rem;>
    <h2 style=font-size: 2.5rem; color: #e9d5ff; text-align: center; margin-bottom: 2rem;>🎮 Meta Quest Setup Guide</h2>

    <div class=card style=margin-bottom: 2rem;>
        <h3>Step 1: Enable Developer Mode on Quest</h3>
        <p>1. Open the <strong>Meta app</strong> on your phone<br>
        2. Go to <strong>Menu → Devices → Your Quest</strong><br>
        3. Tap <strong>Developer Mode → Enable</strong><br>
        4. Reboot the Quest</p>
    </div>

    <div class=card style=margin-bottom: 2rem;>
        <h3>Step 2: Connect Quest via USB</h3>
        <p>1. Plug Quest into your Alfred Linux PC with a USB-C cable<br>
        2. <strong>Put on the headset</strong> — a popup will ask Allow USB Debugging?<br>
        3. Check <strong>Always allow from this computer</strong> → tap <strong>OK</strong></p>
    </div>

    <div class=card style=margin-bottom: 2rem;>
        <h3>Step 3: Sideload ALVR Client</h3>
        <div class=code-block>
            $ adb devices<br>
            <span style=color:#6ee7b7;>1WMHH8XXXXXX    device</span><br><br>
            $ adb install /opt/alvr/client/alvr_client_android.apk<br>
            <span style=color:#6ee7b7;>Success</span>
        </div>
    </div>

    <div class=card style=margin-bottom: 2rem;>
        <h3>Step 4: Launch the Spatial Matrix</h3>
        <div class=code-block>
            <span style=color:#9ca3af;># Start the ALVR server on Alfred Linux</span><br>
            $ sudo systemctl start alvr-daemon<br><br>
            <span style=color:#9ca3af;># Or launch manually</span><br>
            $ alvr_launcher
        </div>
        <p style=margin-top: 1rem;>On Quest: <strong>App Library → Unknown Sources → ALVR</strong> → Connect to your Alfred PC. You're in! 🥽</p>
    </div>

    <div class=card style=margin-bottom: 2rem;>
        <h3>⚡ Quick One-Liner</h3>
        <div class=code-block>
            $ adb install /opt/alvr/client/alvr_client_android.apk && sudo systemctl start alvr-daemon
        </div>
    </div>

    <div class=card style=margin-bottom: 2rem;>
        <h3>📡 Wireless ADB (No Cable After First Setup)</h3>
        <div class=code-block>
            <span style=color:#9ca3af;># Enable wireless debugging on Quest first, then:</span><br>
            $ adb connect &lt;quest-ip&gt;:5555<br>
            $ adb install /opt/alvr/client/alvr_client_android.apk
        </div>
    </div>

    <div class=card style=border-color: rgba(110,231,183,0.4);>
        <h3 style=color: #6ee7b7;>🔮 What's Baked In</h3>
        <p>
            <strong>Monado OpenXR</strong> — Open-source XR runtime (systemd service)<br>
            <strong>ALVR v20.14.1</strong> — Wireless VR streaming server + Quest APK<br>
            <strong>Stardust XR</strong> — Spatial computing compositor (Flatpak)<br>
            <strong>Godot 4.3</strong> — Game engine with OpenXR support<br>
            <strong>ADB</strong> — Android Debug Bridge (pre-installed)<br>
            <strong>Vulkan</strong> — GPU-accelerated rendering pipeline
        </p>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<div class=container style=margin-top: 3rem; margin-bottom: 4rem;>
    <div class=card style=border-color: rgba(250,204,21,0.4); text-align: center;>
        <h3 style=color: #facc15;>📥 Download ALVR Client APK</h3>
        <p>Already running Alfred Linux? Grab the ALVR client APK directly and sideload it to your Meta Quest:</p>
        <a href=/downloads/alvr_client_android.apk style=display: inline-block; margin-top: 1rem; padding: 1rem 2.5rem; background: linear-gradient(135deg, #c084fc, #8b5cf6); color: #fff; border-radius: 12px; text-decoration: none; font-weight: bold; font-size: 1.1rem; transition: transform 0.2s;>⬇️ ALVR v20.14.1 Client APK (20MB)</a>
        <p style=margin-top: 1rem; color: #9ca3af; font-size: 0.85rem;>Or from terminal: <code style=color: #c084fc;>adb install /opt/alvr/client/alvr_client_android.apk</code></p>
    </div>
</div>
