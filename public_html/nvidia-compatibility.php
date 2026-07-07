<?php
/**
 * Alfred Linux — Nvidia Compatibility Checker
 * Features the open-source kernel driver support matrix
 *
 * GoSiteMe Inc.
 */
$year = date('Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nvidia Compatibility — Alfred Linux</title>
    <meta name="description" content="Check if your Nvidia GPU is supported by Alfred Linux's native open-source kernel modules.">
    <meta property="og:title" content="Nvidia Compatibility — Alfred Linux">
    <meta property="og:description" content="Instant compatibility checker for Nvidia GPUs on Alfred Linux.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://alfredlinux.com/nvidia-compatibility.php">
    <link rel="canonical" href="https://alfredlinux.com/nvidia-compatibility.php">
    <link rel="icon" href="/favicon.ico">
    <link rel="stylesheet" href="/assets/css/nav.css">
    <style>
        :root {
            --bg: #06060b; --surface: rgba(255,255,255,0.03); --surface-hover: rgba(255,255,255,0.06);
            --border: rgba(255,255,255,0.06); --border-hover: rgba(99,102,241,0.3);
            --text: #e0e0e0; --text-muted: #9ca3af; --text-dim: #6b7280;
            --accent: #76b900; /* Nvidia Green */ --accent-light: #9deb24; --accent2: #4f8200;
            --green: #76b900; --amber: #f59e0b; --cyan: #22d3ee; --red: #ef4444;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter',-apple-system,BlinkMacSystemFont,sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; -webkit-font-smoothing: antialiased; line-height: 1.7; }
        a { color: var(--accent-light); text-decoration: none; }
        a:hover { text-decoration: underline; }

        .hero { text-align: center; padding: 6rem 2rem 3rem; background: radial-gradient(ellipse at 50% 20%, rgba(118,185,0,0.1) 0%, transparent 55%); }
        .hero h1 { font-size: clamp(2rem, 5vw, 3rem); font-weight: 900; margin-bottom: 1rem; background: linear-gradient(135deg, #fff, var(--green), #a3ffa3); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .hero p { color: var(--text-muted); font-size: 1.1rem; max-width: 750px; margin: 0 auto; }
        .hero .world-first-badge { display: inline-block; background: rgba(118,185,0,0.15); border: 1px solid var(--green); color: #9deb24; padding: 4px 12px; border-radius: 8px; font-size: 0.85rem; font-weight: 700; letter-spacing: 1px; margin-bottom: 1rem; box-shadow: 0 0 20px rgba(118,185,0,0.2); }

        .container { max-width: 1000px; margin: 0 auto; padding: 0 2rem 4rem; }

        .search-box {
            position: relative;
            max-width: 600px;
            margin: 2rem auto;
        }
        .search-box input {
            width: 100%;
            padding: 1rem 1.5rem;
            font-size: 1.1rem;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            color: #fff;
            outline: none;
            transition: all 0.2s;
        }
        .search-box input:focus {
            border-color: var(--accent);
            background: rgba(255,255,255,0.05);
            box-shadow: 0 0 15px rgba(118,185,0,0.2);
        }

        .section { margin-top: 4rem; }
        .section h2 { font-size: 1.5rem; font-weight: 800; color: #fff; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border); }
        .section p { color: var(--text-muted); margin-bottom: 1rem; font-size: 0.95rem; }

        .status-legend { display: flex; gap: 1.5rem; margin: 1rem 0 2rem; flex-wrap: wrap; }
        .status-legend span { display: flex; align-items: center; gap: 0.4rem; font-size: 0.85rem; color: var(--text-muted); }
        .dot { width: 10px; height: 10px; border-radius: 50%; }
        .dot-full { background: var(--green); }
        .dot-na { background: var(--red); }

        .hw-table { width: 100%; border-collapse: collapse; margin: 1.5rem 0; }
        .hw-table th { text-align: left; padding: 0.75rem 1rem; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-dim); border-bottom: 1px solid var(--border); }
        .hw-table td { padding: 0.75rem 1rem; font-size: 0.88rem; border-bottom: 1px solid rgba(255,255,255,0.03); color: var(--text-muted); }
        .hw-table tr:hover td { background: var(--surface-hover); }
        .hw-table .machine { color: #fff; font-weight: 600; }
        .hw-table .badge { display: inline-block; padding: 0.15rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }
        .badge-full { background: rgba(118,185,0,0.15); color: var(--green); }
        .badge-na { background: rgba(239,68,68,0.15); color: var(--red); }

        .submit-box { background: rgba(118,185,0,0.06); border: 1px solid rgba(118,185,0,0.2); border-radius: 16px; padding: 2rem; margin: 3rem 0; text-align: center; }
        .submit-box h3 { color: var(--accent-light); font-size: 1.1rem; margin-bottom: 0.75rem; }
        .submit-box p { color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem; }
        .submit-box .btn { display: inline-block; padding: 0.6rem 1.5rem; border-radius: 8px; background: var(--accent); color: #fff; font-weight: 600; text-decoration: none; }
        .submit-box .btn:hover { background: var(--accent2); text-decoration: none; }

        .video-container {
            margin: 3rem 0;
            padding-bottom: 56.25%; /* 16:9 aspect ratio */
            position: relative;
            height: 0;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--border);
            background: #000;
        }
        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: 0;
        }

        footer { text-align: center; padding: 3rem 2rem; color: var(--text-dim); font-size: 0.85rem; border-top: 1px solid var(--border); }
        footer a { color: var(--accent-light); }

        @media (max-width: 768px) {
            .hero { padding: 5rem 1.5rem 2rem; }
            .container { padding: 0 1.25rem 3rem; }
        }
        .hidden { display: none; }
    </style>
</head>
<body>

    <?php include __DIR__ . '/includes/nav.php'; ?>

    <div class="hero">
        <div class="world-first-badge"><i class="fas fa-globe"></i> WORLD FIRST: NEXT-GEN NVIDIA NATIVE ARCHITECTURE</div>
        <h1>Nvidia GPU Compatibility</h1>
        <p>Welcome to the world's first operating system shipping Nvidia's revolutionary <b>Next-Generation Open GPU Architecture</b> baked directly into the default live ISO. No post-install configuration required.<br><br>Search your GPU below to verify Native Support.</p>
        <div class="search-box">
            <input type="text" id="gpuSearch" placeholder="Search your GPU (e.g., RTX 4090, Turing)..." onkeyup="filterGPUs()">
        </div>
    </div>

    <div class="container">
        <div class="status-legend">
            <span><div class="dot dot-full"></div> Native Open-Source Supported</span>
            <span><div class="dot dot-na"></div> Legacy (Requires Proprietary Driver)</span>
        </div>

        <table class="hw-table" id="gpuTable">
            <thead>
                <tr>
                    <th>Architecture</th>
                    <th>Supported Cards</th>
                    <th>Support Status</th>
                </tr>
            </thead>
            <tbody>
                <!-- Blackwell -->
                <tr class="gpu-row">
                    <td class="machine">Blackwell (B100, B200, GB200)</td>
                    <td>B100, B200, GB200, B-series</td>
                    <td><span class="badge badge-full">Native (Mandatory)</span></td>
                </tr>
                <!-- Hopper -->
                <tr class="gpu-row">
                    <td class="machine">Hopper (H100, H200)</td>
                    <td>H100, H200, Grace Hopper Superchips</td>
                    <td><span class="badge badge-full">Native (Mandatory)</span></td>
                </tr>
                <!-- Ada Lovelace -->
                <tr class="gpu-row">
                    <td class="machine">Ada Lovelace (RTX 40-Series)</td>
                    <td>RTX 4090, 4080, 4070 Ti, 4070, 4060 Ti, 4060, L4, L40, RTX 6000 Ada</td>
                    <td><span class="badge badge-full">Native Supported</span></td>
                </tr>
                <!-- Ampere -->
                <tr class="gpu-row">
                    <td class="machine">Ampere (RTX 30-Series)</td>
                    <td>RTX 3090 Ti, 3090, 3080 Ti, 3080, 3070 Ti, 3070, 3060 Ti, 3060, 3050, A100, A-series</td>
                    <td><span class="badge badge-full">Native Supported</span></td>
                </tr>
                <!-- Turing -->
                <tr class="gpu-row">
                    <td class="machine">Turing (RTX 20-Series)</td>
                    <td>RTX 2080 Ti, 2080, 2070, 2060, GTX 1660 Ti, 1660, 1650, T4, Quadro RTX series</td>
                    <td><span class="badge badge-full">Native Supported</span></td>
                </tr>
                <!-- Volta -->
                <tr class="gpu-row">
                    <td class="machine">Volta (V100, Titan V)</td>
                    <td>Tesla V100, Titan V</td>
                    <td><span class="badge badge-na">Legacy (Proprietary Only)</span></td>
                </tr>
                <!-- Pascal -->
                <tr class="gpu-row">
                    <td class="machine">Pascal (GTX 10-Series)</td>
                    <td>GTX 1080 Ti, 1080, 1070, 1060, 1050, Titan Xp, P100</td>
                    <td><span class="badge badge-na">Legacy (Proprietary Only)</span></td>
                </tr>
                <!-- Maxwell -->
                <tr class="gpu-row">
                    <td class="machine">Maxwell (GTX 900-Series)</td>
                    <td>GTX 980 Ti, 980, 970, 960, 950, M40</td>
                    <td><span class="badge badge-na">Legacy (Proprietary Only)</span></td>
                </tr>
            </tbody>
        </table>

        <div class="section" id="video-section">
            <h2>See AlfredOS & Nvidia in Action</h2>
            <p>Watch how perfectly AlfredOS 7.0.12 interfaces with modern Nvidia hardware using the revolutionary open-source kernel modules.</p>
            <div class="video-container">
                <!-- Replace with actual YouTube embed when ready -->
                <iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" title="AlfredOS Nvidia Compatibility" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </div>
        </div>

        <div class="submit-box">
            <h3>Ready to Experience Native Nvidia Power?</h3>
            <p>AlfredOS 7.0.12 Gold Master is compiling now. Prepare your systems.</p>
            <a href="/release.php" class="cta-btn" style="background:#76b900;color:#000;">View 7.0.12 Release Notes</a>
        </div>
    </div>

    <footer>
        <p>&copy; <?php echo $year; ?> GoSiteMe Inc. — AlfredOS is not affiliated with NVIDIA Corporation.</p>
        <p><a href="/">Back to Home</a></p>
    </footer>

    <script>
        function filterGPUs() {
            let input = document.getElementById('gpuSearch');
            let filter = input.value.toLowerCase();
            let table = document.getElementById('gpuTable');
            let rows = table.getElementsByClassName('gpu-row');

            for (let i = 0; i < rows.length; i++) {
                let text = rows[i].innerText.toLowerCase();
                if (text.indexOf(filter) > -1) {
                    rows[i].style.display = "";
                } else {
                    rows[i].style.display = "none";
                }
            }
        }
    </script>
</body>
</html>
