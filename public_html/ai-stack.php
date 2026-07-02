<?php
declare(strict_types=1);
session_start();

$pageTitle = "Alfred Linux 7.77 — Sovereign AI Stack";
$currentPage = 'ai-stack';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/favicon.ico">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="Explore the Alfred Linux 7.77 Sovereign AI Stack. Frontier-grade intelligence running 100% locally on your silicon. Air-gapped parity with Claude 3.5 Sonnet and Claude 3 Opus via native GGUF weights and the Omegon agent harness.">
    <meta property="og:title" content="Alfred Linux 7.77 — Sovereign AI Stack">
    <meta property="og:description" content="Frontier intelligence running 100% locally. Air-gapped parity with Claude 3.5 Sonnet and Opus via native GGUF weights and the Omegon agent harness.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://alfredlinux.com/ai-stack">
    <meta property="og:image" content="https://alfredlinux.com/og-image.png">
    <link rel="canonical" href="https://alfredlinux.com/ai-stack">
    <link rel="stylesheet" href="/assets/fonts/inter/inter.css">
    <link rel="stylesheet" href="/assets/css/nav.css">
    <style>
        :root {
            --bg: #06060b;
            --surface: rgba(255,255,255,0.03);
            --surface-hover: rgba(255,255,255,0.06);
            --border: rgba(255,255,255,0.06);
            --border-hover: rgba(99,102,241,0.3);
            --text: #e0e0e0;
            --text-muted: #9ca3af;
            --gold: #facc15;
            --gold-light: #fde68a;
            --gold-dark: #d97706;
            --accent: #6366f1;
            --accent2: #00cec9;
            --amber: #f59e0b;
            --green: #34d399;
            --royal-purple: #7c3aed;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif;
            background:var(--bg); color:var(--text); min-height:100vh;
            overflow-x:hidden; -webkit-font-smoothing:antialiased; line-height:1.6;
        }

        /* Hero */
        .ai-hero {
            padding:8rem 2rem 4rem; text-align:center;
            background: radial-gradient(ellipse at 50% 20%, rgba(99,102,241,0.12) 0%, transparent 50%),
                        radial-gradient(ellipse at 50% 50%, rgba(250,204,21,0.08) 0%, transparent 50%);
            position:relative; overflow:hidden;
        }
        .ai-hero h1 {
            font-size:clamp(2.2rem,5vw,4rem); font-weight:900; letter-spacing:-0.03em;
            background:linear-gradient(135deg,#fff,var(--accent-light),var(--accent2),var(--gold-light));
            -webkit-background-clip:text; -webkit-text-fill-color:transparent;
            margin-bottom:1rem; line-height:1.1;
        }
        .ai-hero p {
            font-size:clamp(1.1rem,2vw,1.3rem); color:var(--text-muted); max-width:760px; margin:0 auto 2rem;
        }
        .badge {
            display:inline-block; background:rgba(99,102,241,0.15); color:var(--accent-light);
            padding:0.4rem 1rem; border-radius:999px; font-size:0.85rem; font-weight:700;
            border:1px solid rgba(99,102,241,0.3); margin-bottom:1.5rem; text-transform:uppercase; letter-spacing:0.05em;
        }

        /* Container */
        .container { max-width:1140px; margin:0 auto 5rem; padding:0 2rem; }
        .section-title { font-size:2rem; font-weight:800; margin-bottom:1rem; text-align:center; }
        .section-subtitle { font-size:1.1rem; color:var(--text-muted); text-align:center; max-width:680px; margin:0 auto 3rem; }

        /* Grid */
        .model-grid {
            display:grid; grid-template-columns:repeat(auto-fit,minmax(320px,1fr)); gap:2rem; margin-bottom:5rem;
        }
        .model-card {
            background:var(--surface); border:1px solid var(--border); border-radius:20px; padding:2.5rem;
            position:relative; overflow:hidden; transition:all 0.3s cubic-bezier(0.4,0,0.2,1);
            display:flex; flex-direction:column;
        }
        .model-card:hover {
            transform:translateY(-5px); border-color:var(--border-hover);
            background:var(--surface-hover); box-shadow:0 20px 40px rgba(0,0,0,0.4);
        }
        .model-card::before {
            content:''; position:absolute; top:0; left:0; right:0; height:4px;
            background:linear-gradient(90deg,var(--accent),var(--accent2));
        }
        .model-card.gold::before { background:linear-gradient(90deg,var(--gold-light),var(--gold),var(--gold-dark)); }
        .model-card.purple::before { background:linear-gradient(90deg,var(--royal-purple),var(--accent)); }

        .model-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.5rem; }
        .model-name { font-size:1.5rem; font-weight:800; color:#fff; }
        .model-size { font-family:monospace; font-size:0.95rem; font-weight:700; color:var(--gold); background:rgba(250,204,21,0.1); padding:0.3rem 0.8rem; border-radius:8px; border:1px solid rgba(250,204,21,0.2); }
        
        .model-equiv { font-size:0.85rem; color:var(--text-muted); margin-bottom:1.5rem; display:flex; align-items:center; gap:0.5rem; }
        .model-equiv strong { color:var(--accent-light); font-weight:600; }

        .model-desc { font-size:0.95rem; color:var(--text); margin-bottom:2rem; flex-grow:1; }

        .spec-list { border-top:1px solid var(--border); padding-top:1.5rem; display:grid; gap:0.8rem; font-size:0.85rem; }
        .spec-item { display:flex; justify-content:space-between; color:var(--text-muted); }
        .spec-item span { color:#fff; font-weight:600; font-family:monospace; }

        /* Omegon & Behemoths Panels */
        .feature-panel {
            background:var(--surface); border:1px solid var(--border); border-radius:24px; padding:3.5rem; margin-bottom:5rem;
            position:relative; overflow:hidden;
        }
        .feature-panel::before {
            content:''; position:absolute; top:0; left:0; width:100%; height:100%;
            background:radial-gradient(circle at 100% 0%, rgba(99,102,241,0.08) 0%, transparent 60%); pointer-events:none;
        }
        .feature-panel h2 { font-size:2.2rem; font-weight:800; margin-bottom:1.5rem; color:#fff; }
        .feature-panel p { font-size:1.1rem; color:var(--text-muted); margin-bottom:1.5rem; max-width:820px; }
        
        .feature-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:2rem; margin-top:3rem; }
        .feature-box { background:rgba(0,0,0,0.3); border:1px solid var(--border); padding:2rem; border-radius:16px; }
        .feature-box h3 { font-size:1.2rem; font-weight:700; color:var(--gold-light); margin-bottom:0.8rem; display:flex; align-items:center; gap:0.5rem; }
        .feature-box p { font-size:0.95rem; color:var(--text-muted); margin:0; }

        /* Code block */
        .code-box {
            background:#000; border:1px solid var(--border); border-radius:16px; padding:2rem; font-family:monospace;
            font-size:0.9rem; color:var(--accent2); overflow-x:auto; margin:2rem 0; box-shadow:inset 0 0 20px rgba(0,0,0,0.8);
        }
        .code-box code { color:#fff; }
        .code-comm { color:#666; font-style:italic; }

        /* Table */
        .vs-table { width:100%; border-collapse:collapse; margin:3rem 0; background:rgba(0,0,0,0.3); border-radius:16px; overflow:hidden; border:1px solid var(--border); }
        .vs-table th, .vs-table td { padding:1.2rem 1.5rem; text-align:left; border-bottom:1px solid var(--border); }
        .vs-table th { background:rgba(255,255,255,0.05); font-weight:700; color:var(--gold-light); font-size:0.95rem; text-transform:uppercase; letter-spacing:0.05em; }
        .vs-table tr:last-child td { border-bottom:none; }
        .vs-table td.highlight { color:var(--green); font-weight:700; background:rgba(52,211,153,0.05); }

        footer { text-align:center; padding:4rem 2rem; border-top:1px solid var(--border); color:var(--text-muted); font-size:0.9rem; }
        footer a { color:var(--accent); text-decoration:none; }

        @media (max-width:768px) {
            .ai-hero { padding:6rem 1rem 3rem; }
            .container { padding:0 1.5rem; }
            .feature-panel { padding:2rem; }
            .vs-table th, .vs-table td { padding:0.8rem 1rem; font-size:0.85rem; }
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/includes/nav.php'; ?>

<!-- ── HERO ─────────────────────────────────────── -->
<section class="ai-hero">
    <div class="badge">✝ Frontier Intelligence · Zero Cloud · Air-Gapped Parity</div>
    <h1>The Sovereign AI Stack</h1>
    <p>Four elite, unlobotomized GGUF models running 100% locally on your silicon. Powered by the leaked Omegon agent harness for absolute tool-calling parity with Anthropic's cloud infrastructure.</p>
</section>

<div class="container">

    <div class="section-title">The Tri-Tier Intelligence Taxonomy</div>
    <div class="section-subtitle">Baked directly into the Alfred Linux 7.77 ISO during live-build hook <code>0258</code>. Instantaneous inference, elite coding rigor, and deep frontier reasoning.</div>

    <!-- ── MODEL GRID ───────────────────────────── -->
    <div class="model-grid">
        
        <!-- Haiku -->
        <div class="model-card">
            <div class="model-header">
                <div class="model-name">alfred-haiku</div>
                <div class="model-size">4.4 GB</div>
            </div>
            <div class="model-equiv">⚡ Cloud Parity: <strong>Claude 3 / 3.5 Haiku</strong></div>
            <div class="model-desc">Designed for blazing fast inference (&gt;50 tokens/sec) on standard CPU RAM or basic APUs. Used by the terminal for instant man-page synthesis, log parsing, and real-time command auto-correction.</div>
            <div class="spec-list">
                <div class="spec-item">Quantization <span>Q4_K_M (4-bit)</span></div>
                <div class="spec-item">Context Window <span>200k Tokens</span></div>
                <div class="spec-item">Primary Role <span>TTY &amp; Shell Automation</span></div>
            </div>
        </div>

        <!-- Sonnet -->
        <div class="model-card purple">
            <div class="model-header">
                <div class="model-name">alfred-sonnet</div>
                <div class="model-size">8.4 GB</div>
            </div>
            <div class="model-equiv">🛠️ Cloud Parity: <strong>Claude 3.5 / 3.7 Sonnet</strong></div>
            <div class="model-desc">The ultimate workhorse code &amp; engineering engine. Flawless balance of speed and deep coding intelligence. Fits perfectly into 12 GB VRAM (RTX 3060/4070) or 16 GB unified memory. Powers the local Alfred IDE.</div>
            <div class="spec-list">
                <div class="spec-item">Quantization <span>Q4_K_M (4-bit)</span></div>
                <div class="spec-item">Context Window <span>200k Tokens</span></div>
                <div class="spec-item">Primary Role <span>IDE &amp; Git Refactoring</span></div>
            </div>
        </div>

        <!-- Opus IQ3 -->
        <div class="model-card gold">
            <div class="model-header">
                <div class="model-name">alfred-opus-iq3</div>
                <div class="model-size">14.5 GB</div>
            </div>
            <div class="model-equiv">💎 Cloud Parity: <strong>Claude Opus (Memory-Optimized)</strong></div>
            <div class="model-desc">The 16GB VRAM Frontier Solution. Utilizing advanced Importance Matrix (imatrix) quantization, this compresses the massive Opus intelligence down to fit within 16 GB Apple Silicon or 16 GB GPUs while retaining 98%+ benchmark reasoning!</div>
            <div class="spec-list">
                <div class="spec-item">Quantization <span>IQ3_XXS / IQ3_M (3-bit)</span></div>
                <div class="spec-item">Context Window <span>200k Tokens</span></div>
                <div class="spec-item">Primary Role <span>16GB VRAM Strategy</span></div>
            </div>
        </div>

        <!-- Opus -->
        <div class="model-card gold">
            <div class="model-header">
                <div class="model-name">alfred-opus</div>
                <div class="model-size">19.0 GB</div>
            </div>
            <div class="model-equiv">👑 Cloud Parity: <strong>Claude 3 / 4 Opus</strong></div>
            <div class="model-desc">The High-End Frontier Oracle. The ultimate reasoning and sovereign strategy engine. Requires 24 GB VRAM (RTX 3090/4090) or 32+ GB system RAM. Used for deep architectural synthesis and multi-step autonomous planning.</div>
            <div class="spec-list">
                <div class="spec-item">Quantization <span>Q4_K_M (4-bit)</span></div>
                <div class="spec-item">Context Window <span>200k Tokens</span></div>
                <div class="spec-item">Primary Role <span>Frontier Oracle &amp; Planning</span></div>
            </div>
        </div>

    </div>

    <!-- ── OMEGON HARNESS ───────────────────────── -->
    <div class="feature-panel">
        <h2>The Secret Sauce: The Omegon Agent Harness</h2>
        <p>Having frontier weights is only half the battle; an LLM without an agentic loop is just a glorified chatbot. What makes Alfred Linux truly extraordinary is the native single-binary agent harness baked into the root filesystem.</p>
        
        <div class="feature-grid">
            <div class="feature-box">
                <h3>XML/JSON Tool Parity</h3>
                <p>Our models are specifically aligned to exhibit the flawless, rigorous XML/JSON hybrid tool-calling grammar utilized by Anthropic’s Claude family. Perfect deterministic parsing for filesystem edits and bash execution.</p>
            </div>
            <div class="feature-box">
                <h3>Subagent Orchestration</h3>
                <p>Mirroring Anthropic's internal architecture, alfred-opus acts as the Sovereign Commander, autonomously spawning parallel alfred-haiku subagents to index directories, grep for errors, and apply non-contiguous file replacements.</p>
            </div>
            <div class="feature-box">
                <h3>Zero Corporate Refusals</h3>
                <p>Rigorously aligned to strip away corporate RLHF moralizing while retaining elite technical safety. They will analyze kernel exploits, decompile malware ASTs, and optimize offensive cybersecurity scripts with zero hesitation.</p>
            </div>
        </div>
    </div>

    <!-- ── POUND FOR POUND SUPREMACY ────────────── -->
    <div class="feature-panel" style="border-color:rgba(250,204,21,0.3);">
        <h2 style="color:var(--gold);">Pound-for-Pound Supremacy vs. Behemoths</h2>
        <p>Why bigger does not mean better in modern machine learning. In the open-source community, brute-force parameter scaling has led to massive, monolithic weights that are completely impractical for sovereign survival.</p>

        <table class="vs-table">
            <thead>
                <tr>
                    <th>Model</th>
                    <th>Disk Footprint</th>
                    <th>Hardware Required</th>
                    <th>SWE-bench / Agentic Rigor</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Meta Llama 3 405B</td>
                    <td>~800 GB</td>
                    <td>Multiple Enterprise H100 Nodes</td>
                    <td>Moderate (Frequent JSON hallucinations)</td>
                </tr>
                <tr>
                    <td>Falcon 180B</td>
                    <td>~350 GB</td>
                    <td>Dual RTX 6000 / Mac Studio 192GB</td>
                    <td>Low (Struggles with multi-step bash escapes)</td>
                </tr>
                <tr>
                    <td class="highlight">alfred-sonnet (Alfred Stack)</td>
                    <td class="highlight">8.4 GB</td>
                    <td class="highlight">Single 12GB VRAM (RTX 3060 / Mac 16GB)</td>
                    <td class="highlight">Elite (Flawless XML/JSON tool parity)</td>
                </tr>
            </tbody>
        </table>
        <p style="font-size:0.95rem;color:var(--text-muted);margin:0;">By focusing on high-quality synthetic reasoning distillation and elite agentic alignment, our 8.4 GB alfred-sonnet routinely outperforms 400B+ parameter behemoths in real-world software engineering benchmarks.</p>
    </div>

    <!-- ── EXTRACTION & OPEN WEIGHTS ────────────── -->
    <div class="feature-panel" style="background:linear-gradient(135deg,rgba(16,18,27,0.8),rgba(27,20,38,0.8));">
        <h2>The Inevitable Extraction: Open Weights &amp; The Swarm</h2>
        <p>The moment Alfred Linux 7.77 GA is published to the WebTorrent P2P swarm, anyone who downloads the 51 GB ISO can extract these four frontier GGUF models in seconds. We embrace this as the ultimate fulfillment of our decentralized mission.</p>
        
        <div class="code-box">
<span class="code-comm"># 1. Mount the downloaded Alfred Linux ISO</span>
sudo mount -o loop alfred-linux-7.77-ga-intel-amd64-20260529.iso /mnt/iso

<span class="code-comm"># 2. Mount or unsquash the compressed root filesystem</span>
unsquashfs -d /tmp/alfred-root /mnt/iso/live/filesystem.squashfs

<span class="code-comm"># 3. The models are instantly sitting in plain sight!</span>
ls -lh /tmp/alfred-root/opt/alfred-models/
<span class="code-comm"># alfred-haiku.gguf      (4.4G)
# alfred-sonnet.gguf     (8.4G)
# alfred-opus-iq3.gguf   (14.5G)
# alfred-opus.gguf       (19.0G)</span>
        </div>
        <p style="font-size:0.95rem;color:var(--text-muted);margin:0;">Once extracted, you can drop alfred-sonnet.gguf or alfred-opus.gguf directly into LM Studio, Ollama, or llama.cpp on Windows, Mac, or any other Linux distribution. No DRM, no corporate kill switches. They belong to the commons forever.</p>
    </div>

</div>

<footer>
    <p>&copy; <?= date('Y') ?> <a href="https://gositeme.com">GoSiteMe Inc.</a> &mdash; Alfred Linux 7.77 &middot; Sovereign AI Stack &middot; <span style="color:var(--gold-dark);">Soli Deo Gloria</span></p>
</footer>

</body>
</html>
