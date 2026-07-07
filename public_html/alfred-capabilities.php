<?php
/**
 * Alfred Linux — Capabilities
 * Details the Dynamic Layer Tiering and GPU capabilities.
 */
$year = date('Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dynamic AI Capabilities — Alfred Linux</title>
    <meta name="description" content="Discover how Alfred Linux dynamically tiers neural networks across GPU VRAM and System RAM to prevent out-of-memory crashes on massive 70B models.">
    <link rel="stylesheet" href="/assets/css/nav.css">
    <style>
        :root {
            --bg: #06060b; --surface: rgba(255,255,255,0.03);
            --border: rgba(255,255,255,0.06);
            --text: #e0e0e0; --text-muted: #9ca3af; --text-dim: #6b7280;
            --accent: #6366f1; --accent-light: #a5b4fc;
            --green: #34d399; --amber: #f59e0b; --cyan: #22d3ee; --red: #ef4444;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter',-apple-system,BlinkMacSystemFont,sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; line-height: 1.7; }
        a { color: var(--accent-light); text-decoration: none; }
        a:hover { text-decoration: underline; }

        .hero { text-align: center; padding: 6rem 2rem 3rem; background: radial-gradient(ellipse at 50% 20%, rgba(99,102,241,0.12) 0%, transparent 55%); }
        .hero h1 { font-size: clamp(2rem, 5vw, 3rem); font-weight: 900; margin-bottom: 1rem; background: linear-gradient(135deg, #fff, var(--green), var(--cyan)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .hero p.subtitle { color: var(--text-muted); font-size: 1.15rem; max-width: 700px; margin: 0 auto; }

        .container { max-width: 780px; margin: 0 auto; padding: 0 2rem 4rem; }

        .section { margin-top: 5rem; background: rgba(255,255,255,0.01); padding: 3rem; border-radius: 16px; border: 1px solid rgba(255,255,255,0.03); backdrop-filter: blur(10px); }
        .section h2 { font-size: 1.8rem; font-weight: 900; color: #fff; margin-bottom: 1.5rem; text-shadow: 0 0 20px rgba(52,211,153,0.4); letter-spacing: -0.02em; }
        .section p { color: #d1d5db; margin-bottom: 1.5rem; font-size: 1.15rem; line-height: 1.9; }

        .evidence { margin: 1.5rem 0; padding: 1.5rem 2rem; background: rgba(52,211,153,0.04); border: 1px solid rgba(52,211,153,0.2); border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .evidence .label { font-size: 0.85rem; font-weight: 800; color: var(--green); text-transform: uppercase; letter-spacing: 0.15em; margin-bottom: 0.75rem; }
        .evidence ul { list-style: none; padding: 0; }
        .evidence li { padding: 0.5rem 0; color: #cbd5e1; font-size: 1.05rem; }
        .evidence li::before { content: "→ "; color: var(--green); font-weight: 700; text-shadow: 0 0 10px var(--green); }

        .divider { margin: 4rem auto; width: 100px; height: 3px; background: linear-gradient(90deg, transparent, var(--green), var(--cyan), transparent); border-radius: 2px; }

        footer { text-align: center; padding: 3rem 2rem; color: var(--text-dim); font-size: 0.85rem; border-top: 1px solid var(--border); }
    </style>
</head>
<body>

<?php $currentPage = 'cyber-capabilities'; include __DIR__ . '/includes/nav.php'; ?>

<div class="hero">
    <h1>Dynamic Layer Tiering</h1>
    <p class="subtitle">How Alfred Linux seamlessly bridges GPU VRAM and CPU RAM to run massive, 70B+ parameter AI models locally without out-of-memory crashes.</p>
    <img src="/dynamic_layer_tiering.png" alt="Dynamic Layer Tiering Visualization" style="width: 100%; max-width: 900px; border-radius: 16px; margin-top: 2rem; border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 20px 40px rgba(0,0,0,0.5);">
</div>

<div class="container">
    <div class="section">
        <h2>The Hardware Handshake</h2>
        <p>Because Alfred Linux meticulously bakes the raw <strong>NVIDIA open-gpu-kernel-modules</strong> and AMD's <strong>ROCm</strong> stacks into the offline master vault, the OS possesses bare-metal access to the GPU's CUDA/HIP cores the literal millisecond it boots.</p>
        <p>When you pull a massive model offline, the backend engine instantly interrogates the PCI bus to determine exactly how much VRAM is available. There is zero manual configuration.</p>
    </div>

    <div class="divider"></div>

    <div class="section">
        <h2>Dynamic Layer Splitting</h2>
        <p>Neural networks are built in mathematical "layers." If you load a 40GB model, but your RTX 4090 only has 24GB of VRAM, a standard operating system will immediately crash with a fatal "Out of Memory" error.</p>
        <p>Alfred doesn't crash. Instead, the AI engine physically slices the neural network based on the exact hardware detected on the PCIe bus.</p>

        <div class="evidence">
            <div class="label">The Splitting Architecture</div>
            <ul>
                <li><strong>VRAM Packing:</strong> It crams the maximum number of layers it possibly can (e.g., 30 out of 40 layers) into the hyper-fast GPU VRAM.</li>
                <li><strong>RAM Overflow:</strong> It gracefully dumps the remaining layers (e.g., the last 10 layers) into standard System RAM.</li>
            </ul>
        </div>
    </div>

    <div class="divider"></div>

    <div class="section">
        <h2>The Overflow Execution</h2>
        <p>When you interrogate the AI, the data shoots through the GPU at lightspeed for the first layers, and then seamlessly overflows to the CPU to finish processing the remainder.</p>
        <p><strong>The Result:</strong> The model runs perfectly. It guarantees that the AI never crashes and the user can run models vastly larger than their hardware technically supports. You just type <code>ollama run llama3:70b</code>, and Alfred negotiates the entire VRAM/RAM split automatically behind the scenes.</p>
    </div>
</div>

<footer>
    <p>&copy; <?php echo $year; ?> GoSiteMe Inc. — Alfred Linux is a registered trademark.</p>
</footer>

</body>
</html>
