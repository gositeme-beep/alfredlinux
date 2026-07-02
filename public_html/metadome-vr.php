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
