<?php
/**
 * Alfred Linux — Web USB Writer
 * 
 * A web-based tool that guides users through writing Alfred Linux to a USB drive.
 * Uses the Web USB API where supported, otherwise provides platform-specific instructions.
 * Downloads the ISO via WebTorrent for peer-to-peer speed, then writes to USB.
 */

require_once __DIR__ . '/includes/ga-release-state.php';
$gaDownloadOfferLive = $finalGaIsoPublished && $gaP2pDownloadsEnabled;

$downloadDir = __DIR__ . '/downloads';
$usbIsoFilename = $gaIsoBasename . '.iso';
$manifests = glob($downloadDir . '/*.verify.json');
$latest = null;
foreach ($manifests as $m) {
    $data = json_decode(file_get_contents($m), true);
    if ($data && (!$latest || strcmp($data['build_date'] ?? '', $latest['build_date'] ?? '') > 0)) {
        $latest = $data;
    }
}
if (is_array($latest) && !empty($latest['filename'])) {
    $usbIsoFilename = (string) $latest['filename'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Write USB — Alfred Linux</title>
    <meta name="description" content="Write Alfred Linux to a USB drive directly from your browser. No extra software needed.">
    <style>
        :root {
            --bg: #0a0a0f;
            --surface: #12121a;
            --surface2: #1a1a28;
            --gold: #d4af37;
            --green: #22c55e;
            --red: #ef4444;
            --blue: #3b82f6;
            --text: #e8e8f0;
            --text-dim: #888899;
            --border: #2a2a3a;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }
        .header {
            text-align: center;
            padding: 3rem 1rem 2rem;
            border-bottom: 1px solid var(--border);
        }
        .header h1 { color: var(--gold); font-size: 2rem; margin-bottom: 0.5rem; }
        .header .sub { color: var(--text-dim); font-size: 0.9rem; }
        .container { max-width: 800px; margin: 0 auto; padding: 2rem 1rem; }
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .card h2 { color: var(--gold); font-size: 1.1rem; margin-bottom: 1rem; }
        .step {
            display: flex;
            gap: 1.2rem;
            margin-bottom: 1.5rem;
            align-items: flex-start;
        }
        .step-num {
            background: var(--gold);
            color: #000;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            flex-shrink: 0;
        }
        .step-content h3 { font-size: 1rem; margin-bottom: 0.3rem; }
        .step-content p { color: var(--text-dim); font-size: 0.85rem; line-height: 1.5; }
        .btn {
            display: inline-block;
            background: var(--gold);
            color: #000;
            font-weight: 700;
            font-family: inherit;
            padding: 0.8rem 2rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.95rem;
            transition: all 0.3s;
            text-decoration: none;
        }
        .btn:hover { background: #e8c349; transform: translateY(-1px); }
        .btn-outline {
            background: transparent;
            border: 2px solid var(--gold);
            color: var(--gold);
        }
        .btn-outline:hover { background: rgba(212,175,55,0.1); }
        .progress-container {
            margin-top: 1.5rem;
            display: none;
        }
        .progress-bar {
            width: 100%;
            height: 8px;
            background: var(--bg);
            border-radius: 4px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--gold), var(--green));
            width: 0%;
            transition: width 0.5s;
        }
        .progress-text {
            color: var(--text-dim);
            font-size: 0.85rem;
            margin-top: 0.5rem;
        }
        .warning {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid var(--red);
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
            font-size: 0.85rem;
        }
        .warning strong { color: var(--red); }
        .platform-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        .tab {
            padding: 0.5rem 1rem;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 6px;
            color: var(--text-dim);
            cursor: pointer;
            font-family: inherit;
            font-size: 0.85rem;
        }
        .tab.active { background: var(--gold); color: #000; border-color: var(--gold); font-weight: 700; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .code-block {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 1rem;
            font-size: 0.85rem;
            overflow-x: auto;
            margin: 0.5rem 0;
        }
        .code-block code { color: var(--green); }
        .iso-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            margin: 1rem 0;
        }
        .iso-info .label { color: var(--text-dim); font-size: 0.8rem; }
        .iso-info .value { font-size: 0.9rem; }
        .footer {
            text-align: center;
            padding: 2rem;
            color: var(--text-dim);
            font-size: 0.75rem;
            border-top: 1px solid var(--border);
        }
        .seal { color: #a08620; letter-spacing: 4px; margin-top: 0.5rem; }
    </style>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/img/favicon/favicon-16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/img/favicon/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#D4AF37">
</head>
<body>

<div class="header">
    <h1>&#x1f4be; WRITE USB</h1>
    <div class="sub">Alfred Linux v7.77 — Kingdom of God Edition</div>
</div>

<div class="container">

    <!-- ISO Info -->
    <div class="card">
        <h2>&#x1f451; Release Information</h2>
        <?php if ($latest): ?>
        <div class="iso-info">
            <div><span class="label">File</span><br><span class="value"><?= htmlspecialchars($usbIsoFilename, ENT_QUOTES, 'UTF-8') ?></span></div>
            <div><span class="label">Version</span><br><span class="value"><?= htmlspecialchars($latest['version'] ?? '') ?> — <?= htmlspecialchars($latest['codename'] ?? '') ?></span></div>
            <div><span class="label">Size</span><br><span class="value"><?= htmlspecialchars($latest['size_human'] ?? '') ?></span></div>
            <div><span class="label">Architecture</span><br><span class="value"><?= htmlspecialchars($latest['architecture'] ?? 'amd64') ?></span><br><span class="value" style="font-size:0.82rem;color:var(--text-dim);line-height:1.45;">Debian <code>amd64</code> = x86_64 — typical Intel and AMD 64-bit PCs (name is historical, not AMD-only).</span></div>
        </div>
        <div style="margin-top: 1rem; display: flex; gap: 1rem; flex-wrap: wrap;">
            <?php if ($finalGaIsoPublished): ?>
            <a href="/covenant?next=%2Fdownload" class="btn">&#x2b07; DOWNLOAD ISO (covenant → /download)</a>
            <?php if ($gaDownloadOfferLive): ?>
            <a href="/download#ga-p2p-links" class="btn btn-outline" title="Covenant-sealed hub — .torrent on /download">&#x1f9f2; TORRENT</a>
            <?php else: ?>
            <span class="btn btn-outline" style="opacity:0.5;cursor:default;">&#x1f9f2; TORRENT (paused)</span>
            <?php endif; ?>
            <?php else: ?>
            <a href="/download" class="btn">&#x2192; GA ISO status (/download)</a>
            <span class="btn btn-outline" style="opacity:0.5;cursor:default;">&#x1f9f2; TORRENT (with GA)</span>
            <?php endif; ?>
            <a href="verify" class="btn btn-outline">&#x1f50d; VERIFY</a>
        </div>
        <?php if (!$finalGaIsoPublished): ?>
        <p style="margin-top:1rem;color:var(--text-dim);font-size:0.88rem;line-height:1.55;">Draft manifests may still list a filename; the <strong style="color:var(--gold);">official</strong> GA torrent and HTTP paths are announced on <a href="/download" style="color:var(--gold);">/download</a> when the image is frozen.</p>
        <?php endif; ?>
        <?php else: ?>
        <p style="color: var(--text-dim);">No releases available yet.</p>
        <?php endif; ?>
    </div>

    <!-- USB Writing Instructions -->
    <div class="card">
        <h2>&#x1f527; Write to USB Drive</h2>
        
        <div class="warning">
            <strong>&#x26a0; WARNING:</strong> Writing an ISO to a USB drive will ERASE all data on that drive. 
            Make sure you have backed up any important files and selected the correct device.
        </div>

        <div class="platform-tabs">
            <button class="tab active" onclick="showPlatform('windows')">Windows</button>
            <button class="tab" onclick="showPlatform('linux')">Linux</button>
            <button class="tab" onclick="showPlatform('macos')">macOS</button>
        </div>

        <!-- Windows -->
        <div class="tab-content active" id="platform-windows">
            <div class="step">
                <div class="step-num">1</div>
                <div class="step-content">
                    <h3>Download</h3>
                    <p>Download the ISO using the button above (covenant, then <code>/download</code> for P2P) or the <strong>.torrent</strong> link when P2P is live.</p>
                </div>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <div class="step-content">
                    <h3>Get Rufus</h3>
                    <p>Download <a href="https://rufus.ie" target="_blank" style="color: var(--gold);">Rufus</a> (free, open-source USB writer). No installation needed — just run the .exe.</p>
                </div>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <div class="step-content">
                    <h3>Insert USB Drive</h3>
                    <p>Insert a USB drive (8GB minimum, 16GB recommended). All data on it will be erased.</p>
                </div>
            </div>
            <div class="step">
                <div class="step-num">4</div>
                <div class="step-content">
                    <h3>Write</h3>
                    <p>In Rufus: select your USB drive, click SELECT to choose the Alfred Linux ISO, then click START. Wait for completion.</p>
                </div>
            </div>
            <div class="step">
                <div class="step-num">5</div>
                <div class="step-content">
                    <h3>Boot</h3>
                    <p>Restart your computer. Press F2, F12, DEL, or ESC (varies by manufacturer) to enter boot menu. Select your USB drive.</p>
                </div>
            </div>

            <div style="margin-top: 1rem;">
                <p style="color: var(--text-dim); font-size: 0.85rem;">
                    <strong style="color: var(--gold);">PowerShell alternative</strong> (advanced):
                </p>
                <div class="code-block">
                    <code>
# Verify download first<br>
(Get-FileHash alfred-linux-7.77-ga-*.iso -Algorithm SHA256).Hash<br>
# Compare with hash at https://alfredlinux.com/verify
                    </code>
                </div>
            </div>
        </div>

        <!-- Linux -->
        <div class="tab-content" id="platform-linux">
            <div class="step">
                <div class="step-num">1</div>
                <div class="step-content">
                    <h3>Download & Verify</h3>
                    <p style="color:var(--text-dim);font-size:0.88rem;line-height:1.55;margin-bottom:0.75rem;">Plain <code>https://alfredlinux.com/downloads/*.iso</code> is denied. Accept the <a href="/covenant?next=%2Fdownload" style="color:var(--gold);">covenant</a>, open <a href="/download" style="color:var(--gold);">/download</a> for P2P, or copy the time-limited <code>/downloads/iso.php?t=…</code> link shown there.</p>
                    <div class="code-block"><code>
wget -O <?= htmlspecialchars($usbIsoFilename, ENT_QUOTES, 'UTF-8') ?> "https://alfredlinux.com/downloads/iso.php?t=PASTE_TOKEN_FROM_DOWNLOAD"<br>
wget https://alfredlinux.com/downloads/<?= htmlspecialchars($usbIsoFilename, ENT_QUOTES, 'UTF-8') ?>.sha256<br>
sha256sum -c <?= htmlspecialchars($usbIsoFilename, ENT_QUOTES, 'UTF-8') ?>.sha256
                    </code></div>
                </div>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <div class="step-content">
                    <h3>Find Your USB Device</h3>
                    <div class="code-block"><code>lsblk</code></div>
                    <p>Look for your USB drive (e.g., /dev/sdb). Make sure you identify the correct device!</p>
                </div>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <div class="step-content">
                    <h3>Write to USB</h3>
                    <div class="code-block"><code>
sudo dd if=<?= htmlspecialchars($usbIsoFilename, ENT_QUOTES, 'UTF-8') ?> of=/dev/sdX bs=4M status=progress oflag=sync
                    </code></div>
                    <p>Replace <strong>/dev/sdX</strong> with your actual USB device. This will take a few minutes.</p>
                </div>
            </div>
            <div class="step">
                <div class="step-num">4</div>
                <div class="step-content">
                    <h3>Boot</h3>
                    <p>Restart and select USB from your boot menu (usually F2, F12, or hold Shift during GRUB).</p>
                </div>
            </div>
        </div>

        <!-- macOS -->
        <div class="tab-content" id="platform-macos">
            <div class="step">
                <div class="step-num">1</div>
                <div class="step-content">
                    <h3>Download & Verify</h3>
                    <p style="color:var(--text-dim);font-size:0.88rem;line-height:1.55;margin-bottom:0.75rem;">Plain <code>/downloads/*.iso</code> HTTP is denied — covenant + <a href="/download" style="color:var(--gold);">/download</a>, or <code>iso.php?t=…</code> from that page.</p>
                    <div class="code-block"><code>
curl -fL -o <?= htmlspecialchars($usbIsoFilename, ENT_QUOTES, 'UTF-8') ?> "https://alfredlinux.com/downloads/iso.php?t=PASTE_TOKEN_FROM_DOWNLOAD"<br>
curl -fLO https://alfredlinux.com/downloads/<?= htmlspecialchars($usbIsoFilename, ENT_QUOTES, 'UTF-8') ?>.sha256<br>
shasum -a 256 -c <?= htmlspecialchars($usbIsoFilename, ENT_QUOTES, 'UTF-8') ?>.sha256
                    </code></div>
                </div>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <div class="step-content">
                    <h3>Find & Unmount USB</h3>
                    <div class="code-block"><code>
diskutil list<br>
diskutil unmountDisk /dev/diskN
                    </code></div>
                    <p>Replace /dev/diskN with your USB device number.</p>
                </div>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <div class="step-content">
                    <h3>Write to USB</h3>
                    <div class="code-block"><code>
sudo dd if=<?= htmlspecialchars($usbIsoFilename, ENT_QUOTES, 'UTF-8') ?> of=/dev/rdiskN bs=4m
                    </code></div>
                    <p>Note: use <strong>/dev/rdiskN</strong> (with 'r') for faster writing.</p>
                </div>
            </div>
            <div class="step">
                <div class="step-num">4</div>
                <div class="step-content">
                    <h3>Boot</h3>
                    <p>Restart and hold the Option key to select the USB drive from the boot picker.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Requirements -->
    <div class="card">
        <h2>&#x2699; Requirements</h2>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
            <div>
                <h3 style="color: var(--text-dim); font-size: 0.85rem; margin-bottom: 0.5rem;">MINIMUM</h3>
                <p style="font-size: 0.85rem;">USB: 8 GB</p>
                <p style="font-size: 0.85rem;">CPU: 64-bit (x86_64)</p>
                <p style="font-size: 0.8rem; color: var(--text-dim); line-height: 1.45;">Same as Debian <code>amd64</code>: Intel, AMD, or compatible — not AMD-only.</p>
                <p style="font-size: 0.85rem;">RAM: 2 GB</p>
                <p style="font-size: 0.85rem;">Disk: 20 GB</p>
            </div>
            <div>
                <h3 style="color: var(--gold); font-size: 0.85rem; margin-bottom: 0.5rem;">RECOMMENDED</h3>
                <p style="font-size: 0.85rem;">USB: 16 GB</p>
                <p style="font-size: 0.85rem;">CPU: Intel i5+ / Ryzen 5+</p>
                <p style="font-size: 0.85rem;">RAM: 8 GB</p>
                <p style="font-size: 0.85rem;">Disk: 64 GB SSD</p>
            </div>
        </div>
    </div>

    <!-- Need Help -->
    <div class="card">
        <h2>&#x1f4ac; Need Help?</h2>
        <p style="color: var(--text-dim); font-size: 0.85rem; line-height: 1.6;">
            Visit <a href="https://alfredlinux.com/docs" style="color: var(--gold);">alfredlinux.com/docs</a> for detailed documentation.<br>
            Join the community at <a href="https://gositeme.com" style="color: var(--gold);">gositeme.com</a>.<br>
            Report issues at <a href="https://gocodeme.com" style="color: var(--gold);">gocodeme.com</a>.
        </p>
    </div>

</div>

<div class="footer">
    <div style="font-style:italic;color:#94a3b8;font-size:.85rem;margin-bottom:0.5rem;">&ldquo;The grass withereth, the flower fadeth: but the word of our God shall stand for ever.&rdquo; &mdash; <a href="https://gositeme.com/bible/read/isaiah/40" style="color:#facc15;text-decoration:none;">Isaiah 40:8</a> (AKJV)</div>
    <div>&copy; <?= date('Y') ?> GoSiteMe Inc. — Alfred Linux v7.77</div>
    <div class="seal">OMAHON &middot; OMAHON &middot; OMAHON</div>
</div>

<script>
function showPlatform(platform) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.getElementById('platform-' + platform).classList.add('active');
    event.target.classList.add('active');
}
</script>

</body>
</html>
