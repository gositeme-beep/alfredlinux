<?php
/**
 * Alfred Linux — Intel GPU Compatibility Checker
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
    <title>Intel GPU Compatibility — Alfred Linux</title>
    <meta name="description" content="Check if your Intel GPU is supported by Alfred Linux's native open-source kernel modules.">
    <meta property="og:title" content="Intel GPU Compatibility — Alfred Linux">
    <meta property="og:description" content="Instant compatibility checker for Intel GPUs on Alfred Linux.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://alfredlinux.com/Intel-compatibility.php">
    <link rel="canonical" href="https://alfredlinux.com/Intel-compatibility.php">
    <link rel="icon" href="/favicon.ico">
    <link rel="stylesheet" href="/assets/css/nav.css">
    <style>
        :root {
            --bg: #06060b; --surface: rgba(255,255,255,0.03); --surface-hover: rgba(255,255,255,0.06);
            --border: rgba(255,255,255,0.06); --border-hover: rgba(99,102,241,0.3);
            --text: #e0e0e0; --text-muted: #9ca3af; --text-dim: #6b7280;
            --accent: #0071c5; /* Intel Red */ --accent-light: #ff4d54; --accent2: #a10f15;
            --green: #76b900; --amber: #f59e0b; --cyan: #22d3ee; --red: #0071c5;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter',-apple-system,BlinkMacSystemFont,sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; -webkit-font-smoothing: antialiased; line-height: 1.7; }
        a { color: var(--accent-light); text-decoration: none; }
        a:hover { text-decoration: underline; }

        .hero { text-align: center; padding: 6rem 2rem 3rem; background: radial-gradient(ellipse at 50% 20%, rgba(237,28,36,0.1) 0%, transparent 55%); }
        .hero h1 { font-size: clamp(2rem, 5vw, 3rem); font-weight: 900; margin-bottom: 1rem; background: linear-gradient(135deg, #fff, var(--red), #ff7b80); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .hero p { color: var(--text-muted); font-size: 1.1rem; max-width: 650px; margin: 0 auto; }

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
            box-shadow: 0 0 15px rgba(237,28,36,0.2);
        }

        .section { margin-top: 4rem; }
        .section h2 { font-size: 1.5rem; font-weight: 800; color: #fff; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border); }
        .section p { color: var(--text-muted); margin-bottom: 1rem; font-size: 0.95rem; }

        .status-legend { display: flex; gap: 1.5rem; margin: 1rem 0 2rem; flex-wrap: wrap; }
        .status-legend span { display: flex; align-items: center; gap: 0.4rem; font-size: 0.85rem; color: var(--text-muted); }
        .dot { width: 10px; height: 10px; border-radius: 50%; }
        .dot-full { background: var(--green); }
        .dot-amber { background: var(--amber); }

        .hw-table { width: 100%; border-collapse: collapse; margin: 1.5rem 0; }
        .hw-table th { text-align: left; padding: 0.75rem 1rem; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-dim); border-bottom: 1px solid var(--border); }
        .hw-table td { padding: 0.75rem 1rem; font-size: 0.88rem; border-bottom: 1px solid rgba(255,255,255,0.03); color: var(--text-muted); }
        .hw-table tr:hover td { background: var(--surface-hover); }
        .hw-table .machine { color: #fff; font-weight: 600; }
        .hw-table .badge { display: inline-block; padding: 0.15rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }
        .badge-full { background: rgba(118,185,0,0.15); color: var(--green); }
        .badge-legacy { background: rgba(245,158,11,0.15); color: var(--amber); }

        .submit-box { background: rgba(237,28,36,0.06); border: 1px solid rgba(237,28,36,0.2); border-radius: 16px; padding: 2rem; margin: 3rem 0; text-align: center; }
        .submit-box h3 { color: var(--accent-light); font-size: 1.1rem; margin-bottom: 0.75rem; }
        .submit-box p { color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1rem; }
        .submit-box .btn { display: inline-block; padding: 0.6rem 1.5rem; border-radius: 8px; background: var(--accent); color: #fff; font-weight: 600; text-decoration: none; }
        .submit-box .btn:hover { background: var(--accent2); text-decoration: none; }

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
        <h1>Intel GPU Compatibility</h1>
        <p>AlfredOS features flawless out-of-the-box support for Intel GPUs via the mainline Linux kernel's <b>Intelgpu</b> and <b>oneAPI</b> stack. Zero setup required.</p>
        
        <div class="search-box">
            <input type="text" id="gpuSearch" placeholder="Search your GPU (e.g., RX 7900, RDNA 3)..." onkeyup="filterGPUs()">
        </div>
    </div>

    <div class="container">
        <div class="status-legend">
            <span><div class="dot dot-full"></div> Native Open-Source (Intelgpu/oneAPI)</span>
            <span><div class="dot dot-amber"></div> Legacy Native (Arc module)</span>
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
                <!-- Intel Arc Alchemist -->
                <tr class="gpu-row">
                    <td class="machine">Xe HPG (Arc Alchemist)</td>
                    <td>Arc A770, Arc A750, Arc A580, Arc A380, Arc A310, Arc Pro A60, Arc Pro A50</td>
                    <td><span class="badge badge-full">Native (xe / i915)</span></td>
                </tr>
                <!-- Intel Iris Xe -->
                <tr class="gpu-row">
                    <td class="machine">Xe LP (Iris Xe / UHD)</td>
                    <td>Intel Iris Xe Graphics (Tiger Lake, Alder Lake, Raptor Lake, Meteor Lake)</td>
                    <td><span class="badge badge-full">Native (i915 / xe)</span></td>
                </tr>
                <!-- Intel Datacenter -->
                <tr class="gpu-row">
                    <td class="machine">Datacenter (Ponte Vecchio)</td>
                    <td>Intel Data Center GPU Max Series (Max 1550, Max 1350, Max 1100)</td>
                    <td><span class="badge badge-full">Native (xe + oneAPI)</span></td>
                </tr>
                <!-- Legacy Intel UHD / HD -->
                <tr class="gpu-row">
                    <td class="machine">Legacy Intel HD / UHD</td>
                    <td>Intel UHD 600 series, HD Graphics 500/400/300 series (Skylake to Comet Lake)</td>
                    <td><span class="badge badge-legacy">Native Legacy (i915)</span></td>
                </tr>
            </tbody>
        </table>

        <div class="submit-box">
            <h3>Powered by Mainline Innovation</h3>
            <p>Unlike proprietary alternatives, Intel's commitment to the open-source community means your GPU is ready the moment AlfredOS boots.</p>
            <a href="/release.php" class="btn">View Release Notes</a>
        </div>
    </div>

    <footer>
        <p>&copy; <?php echo $year; ?> GoSiteMe Inc. — AlfredOS is not affiliated with Advanced Micro Devices, Inc.</p>
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
