<?php
/**
 * Alfred Linux — Apps & Downloads
 * Central hub for all Alfred ecosystem downloads.
 */
$isoVersion = "4.0 GA";
$isoFile    = "alfred-linux-4.0-ga-amd64-20260408.iso";
$isoSize    = "2.3 GB";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Apps & Downloads — Alfred Linux + GoSiteMe Ecosystem</title>
<meta name="description" content="Download Alfred Linux, Alfred Browser, Veil Messenger, Pulse Social, and Alfred IDE. Available for Linux, Windows, Android, and as web apps.">
<meta property="og:title" content="Apps & Downloads — Alfred Ecosystem">
<meta property="og:description" content="Every app, every platform. Alfred Linux ISO, Alfred Browser, Veil Messenger, Pulse Social, and Alfred IDE.">
<meta property="og:url" content="https://alfredlinux.com/apps">
<meta property="og:type" content="website">
<meta property="og:image" content="https://alfredlinux.com/og-image.png">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Apps & Downloads — Alfred Ecosystem">
<meta name="twitter:description" content="Every app, every platform. Alfred Linux ISO, Alfred Browser, Veil Messenger, Pulse Social, and Alfred IDE.">
<meta name="twitter:image" content="https://alfredlinux.com/og-image.png">
<link rel="canonical" href="https://alfredlinux.com/apps">
<link rel="icon" href="/favicon.ico">
<link rel="stylesheet" href="/assets/css/nav.css">
<style>
:root {
    --bg: #0a0a0f;
    --surface: #12121a;
    --border: #1e1e2e;
    --accent: #6c5ce7;
    --accent2: #00cec9;
    --gold: #fdcb6e;
    --text: #e0e0e0;
    --dim: #888;
    --success: #00b894;
}
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',system-ui,sans-serif; background:var(--bg); color:var(--text); min-height:100vh; }


.hero { text-align:center; padding:80px 24px 48px; }
.hero h1 { font-size:clamp(2rem,5vw,3rem); font-weight:900; letter-spacing:-1.5px; margin-bottom:12px; }
.hero h1 span { background:linear-gradient(135deg,var(--accent),var(--accent2)); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
.hero p { color:var(--dim); font-size:1.05rem; max-width:600px; margin:0 auto; line-height:1.7; }

.container { max-width:960px; margin:0 auto; padding:0 24px 80px; }

/* App sections */
.app-section { margin-bottom:48px; background:var(--surface); border:1px solid var(--border); border-radius:16px; padding:32px; }
.app-section.featured { border-color:rgba(108,92,231,0.3); box-shadow:0 0 40px rgba(108,92,231,0.08); }
.app-header { display:flex; align-items:center; gap:16px; margin-bottom:20px; flex-wrap:wrap; }
.app-icon { width:56px; height:56px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:24px; flex-shrink:0; }
.app-name { font-size:1.4rem; font-weight:800; }
.app-tag { font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:1px; padding:3px 10px; border-radius:100px; display:inline-block; margin-left:8px; }
.app-desc { color:var(--dim); font-size:.95rem; line-height:1.6; margin-bottom:20px; }

.dl-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:10px; }
.dl-card { display:flex; align-items:center; gap:12px; padding:14px 16px; border-radius:12px; background:rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.06); text-decoration:none; color:var(--text); transition:all .2s; }
.dl-card:hover { border-color:rgba(108,92,231,0.3); transform:translateY(-1px); box-shadow:0 4px 16px rgba(0,0,0,0.3); }
.dl-card .icon { width:36px; height:36px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0; }
.dl-card .info h4 { font-size:.85rem; font-weight:700; margin:0 0 2px; }
.dl-card .info span { font-size:.7rem; color:var(--dim); }
.dl-card .arrow { margin-left:auto; font-size:12px; opacity:.4; }
.dl-card:hover .arrow { opacity:1; }
.dl-card.disabled { pointer-events:none; opacity:.3; }

.cross-link { display:inline-flex; align-items:center; gap:8px; color:var(--accent2); font-size:.85rem; font-weight:600; text-decoration:none; margin-top:12px; }
.cross-link:hover { text-decoration:underline; }

.notice { margin-top:48px; padding:20px 24px; background:rgba(0,206,201,0.06); border:1px solid rgba(0,206,201,0.15); border-radius:14px; font-size:.85rem; color:var(--dim); line-height:1.7; }
.notice strong { color:var(--accent2); }
.notice a { color:var(--accent2); text-decoration:none; font-weight:600; }
.notice a:hover { text-decoration:underline; }

@media(max-width:768px) {
    .app-header { flex-direction:column; text-align:center; }
    .dl-grid { grid-template-columns:1fr; }
    .hero { padding:60px 24px 36px; }
}
</style>
</head>
<body>

<?php $currentPage = 'apps'; include __DIR__ . '/includes/nav.php'; ?>

<div class="hero">
    <h1>Apps & <span>Downloads</span></h1>
    <p>Every app in the Alfred ecosystem. Desktop, mobile, and web — zero tracking, zero telemetry, zero compromise.</p>
</div>

<div class="container">

    <!-- ═══ ALFRED LINUX ═══ -->
    <div class="app-section featured">
        <div class="app-header">
            <div class="app-icon" style="background:rgba(108,92,231,0.15);color:var(--accent);">🐧</div>
            <div>
                <span class="app-name">Alfred Linux</span>
                <span class="app-tag" style="background:rgba(108,92,231,0.15);color:var(--accent);"><?= htmlspecialchars($isoVersion) ?></span>
            </div>
        </div>
        <p class="app-desc">The sovereign Linux distribution. Post-quantum encryption, Alfred AI built in, privacy-first design. BIOS + UEFI bootable, Debian-based, kernel 7.0.</p>
        <div class="dl-grid">
            <a href="/download" class="dl-card">
                <div class="icon" style="background:rgba(0,206,201,0.1);color:var(--accent2);">⚡</div>
                <div class="info">
                    <h4>P2P Download</h4>
                    <span>WebTorrent · In-browser · <?= htmlspecialchars($isoSize) ?></span>
                </div>
                <span class="arrow">→</span>
            </a>
            <a href="/downloads/<?= htmlspecialchars($isoFile) ?>.torrent" class="dl-card">
                <div class="icon" style="background:rgba(253,203,110,0.1);color:var(--gold);">🧲</div>
                <div class="info">
                    <h4>.torrent File</h4>
                    <span>Use any torrent client</span>
                </div>
                <span class="arrow">↓</span>
            </a>
            <a href="/downloads/install-alfred-mobile.sh" class="dl-card">
                <div class="icon" style="background:rgba(0,184,148,0.1);color:var(--success);">📱</div>
                <div class="info">
                    <h4>Mobile Installer</h4>
                    <span>Android/Termux · No root</span>
                </div>
                <span class="arrow">↓</span>
            </a>
        </div>
        <div style="margin-top:12px;display:flex;gap:16px;flex-wrap:wrap;">
            <a href="/downloads/<?= htmlspecialchars($isoFile) ?>.sha256" class="cross-link" style="color:var(--dim);font-size:.75rem;">🔒 SHA-256</a>
            <a href="/downloads/<?= htmlspecialchars($isoFile) ?>.blake3" class="cross-link" style="color:var(--dim);font-size:.75rem;">🔒 BLAKE3</a>
            <a href="/releases" class="cross-link" style="color:var(--dim);font-size:.75rem;">📋 Release Notes</a>
        </div>
    </div>

    <!-- ═══ ALFRED BROWSER ═══ -->
    <div class="app-section">
        <div class="app-header">
            <div class="app-icon" style="background:rgba(59,130,246,0.15);color:#3b82f6;">🌐</div>
            <div>
                <span class="app-name">Alfred Browser</span>
                <span class="app-tag" style="background:rgba(59,130,246,0.12);color:#3b82f6;">v3.0.0</span>
            </div>
        </div>
        <p class="app-desc">The sovereign browser. Post-quantum encryption, AI-powered with 13,000+ tools, built-in mining, games, VR worlds. Zero telemetry.</p>
        <div class="dl-grid">
            <a href="https://gositeme.com/downloads/Alfred-Browser-3.0.0-win-x64.zip.torrent" class="dl-card">
                <div class="icon" style="background:rgba(59,130,246,0.1);color:#3b82f6;">🪟</div>
                <div class="info"><h4>Windows</h4><span>.torrent · x64 Portable</span></div>
                <span class="arrow">🧲</span>
            </a>
            <a href="https://gositeme.com/downloads/Alfred-Browser-3.0.0-mac-intel.zip.torrent" class="dl-card">
                <div class="icon" style="background:rgba(255,255,255,0.04);color:#fff;">🍎</div>
                <div class="info"><h4>macOS Intel</h4><span>.torrent · x64 · macOS 11+</span></div>
                <span class="arrow">🧲</span>
            </a>
            <a href="https://gositeme.com/downloads/Alfred-Browser-3.0.0-mac-arm64.zip.torrent" class="dl-card">
                <div class="icon" style="background:rgba(255,255,255,0.04);color:#fff;">🍎</div>
                <div class="info"><h4>macOS Apple Silicon</h4><span>.torrent · ARM64 · M-series</span></div>
                <span class="arrow">🧲</span>
            </a>
            <a href="https://gositeme.com/downloads/Alfred-Browser-3.0.0.AppImage.torrent" class="dl-card">
                <div class="icon" style="background:rgba(251,191,36,0.1);color:#fbbf24;">🐧</div>
                <div class="info"><h4>Linux AppImage</h4><span>.torrent · x64 · Universal</span></div>
                <span class="arrow">🧲</span>
            </a>
            <a href="https://gositeme.com/downloads/alfred-browser_3.0.0_amd64.deb.torrent" class="dl-card">
                <div class="icon" style="background:rgba(221,72,20,0.1);color:#dd4814;">🐧</div>
                <div class="info"><h4>Ubuntu / Debian</h4><span>.torrent · .deb x64</span></div>
                <span class="arrow">🧲</span>
            </a>
            <a href="https://gositeme.com/downloads/Alfred-Browser.apk.torrent" class="dl-card">
                <div class="icon" style="background:rgba(52,211,153,0.1);color:#34d399;">📱</div>
                <div class="info"><h4>Android</h4><span>.torrent · APK · 8.0+</span></div>
                <span class="arrow">🧲</span>
            </a>
        </div>
        <a href="https://gositeme.com/alfred-browser" class="cross-link">→ Full feature showcase & hashes</a>
    </div>

    <!-- ═══ VEIL MESSENGER ═══ -->
    <div class="app-section">
        <div class="app-header">
            <div class="app-icon" style="background:rgba(139,92,246,0.15);color:#8b5cf6;">🔐</div>
            <div>
                <span class="app-name">Veil Messenger</span>
                <span class="app-tag" style="background:rgba(139,92,246,0.12);color:#8b5cf6;">v1.0.0</span>
            </div>
        </div>
        <p class="app-desc">End-to-end encrypted communications. AES-256-GCM, X25519 key exchange, zero-knowledge architecture. Text, voice, video, and encrypted file sharing.</p>
        <div class="dl-grid">
            <a href="https://gositeme.com/downloads/Veil-Messenger-1.0.0.AppImage.torrent" class="dl-card">
                <div class="icon" style="background:rgba(251,191,36,0.1);color:#fbbf24;">🐧</div>
                <div class="info"><h4>Linux AppImage</h4><span>.torrent · x64 · 88 MB</span></div>
                <span class="arrow">🧲</span>
            </a>
            <a href="https://gositeme.com/downloads/veil-messenger_1.0.0_amd64.deb.torrent" class="dl-card">
                <div class="icon" style="background:rgba(221,72,20,0.1);color:#dd4814;">🐧</div>
                <div class="info"><h4>Ubuntu / Debian</h4><span>.torrent · .deb x64</span></div>
                <span class="arrow">🧲</span>
            </a>
            <a href="https://gositeme.com/downloads/veil-messenger-1.0.0-x86_64.rpm.torrent" class="dl-card">
                <div class="icon" style="background:rgba(239,68,68,0.1);color:#ef4444;">🐧</div>
                <div class="info"><h4>Fedora / RHEL</h4><span>.torrent · .rpm x64</span></div>
                <span class="arrow">🧲</span>
            </a>
            <a href="https://gositeme.com/downloads/GoSiteMe-Veil.apk.torrent" class="dl-card">
                <div class="icon" style="background:rgba(52,211,153,0.1);color:#34d399;">📱</div>
                <div class="info"><h4>Android</h4><span>.torrent · APK · 8.0+</span></div>
                <span class="arrow">🧲</span>
            </a>
            <a href="https://gositeme.com/veil/" class="dl-card">
                <div class="icon" style="background:rgba(139,92,246,0.1);color:#8b5cf6;">🌐</div>
                <div class="info"><h4>Web App</h4><span>Open in browser</span></div>
                <span class="arrow">→</span>
            </a>
        </div>
    </div>

    <!-- ═══ PULSE SOCIAL ═══ -->
    <div class="app-section">
        <div class="app-header">
            <div class="app-icon" style="background:rgba(13,148,136,0.15);color:#0d9488;">💬</div>
            <div>
                <span class="app-name">Pulse Social</span>
                <span class="app-tag" style="background:rgba(13,148,136,0.12);color:#0d9488;">v1.0.0</span>
            </div>
        </div>
        <p class="app-desc">The sovereign social network. Chronological feed, no algorithms, no data harvesting. Share, discover, and connect without surveillance.</p>
        <div class="dl-grid">
            <a href="https://gositeme.com/downloads/Pulse-Social-1.0.0.AppImage.torrent" class="dl-card">
                <div class="icon" style="background:rgba(251,191,36,0.1);color:#fbbf24;">🐧</div>
                <div class="info"><h4>Linux AppImage</h4><span>.torrent · x64 · 88 MB</span></div>
                <span class="arrow">🧲</span>
            </a>
            <a href="https://gositeme.com/downloads/pulse-social_1.0.0_amd64.deb.torrent" class="dl-card">
                <div class="icon" style="background:rgba(221,72,20,0.1);color:#dd4814;">🐧</div>
                <div class="info"><h4>Ubuntu / Debian</h4><span>.torrent · .deb x64</span></div>
                <span class="arrow">🧲</span>
            </a>
            <a href="https://gositeme.com/downloads/pulse-social-1.0.0-x86_64.rpm.torrent" class="dl-card">
                <div class="icon" style="background:rgba(239,68,68,0.1);color:#ef4444;">🐧</div>
                <div class="info"><h4>Fedora / RHEL</h4><span>.torrent · .rpm x64</span></div>
                <span class="arrow">🧲</span>
            </a>
            <a href="https://gositeme.com/downloads/Pulse-Social.apk.torrent" class="dl-card">
                <div class="icon" style="background:rgba(52,211,153,0.1);color:#34d399;">📱</div>
                <div class="info"><h4>Android</h4><span>.torrent · APK · 8.0+</span></div>
                <span class="arrow">🧲</span>
            </a>
            <a href="https://gositeme.com/pulse" class="dl-card">
                <div class="icon" style="background:rgba(13,148,136,0.1);color:#0d9488;">🌐</div>
                <div class="info"><h4>Web App</h4><span>Open in browser</span></div>
                <span class="arrow">→</span>
            </a>
        </div>
    </div>

    <!-- ═══ ALFRED IDE ═══ -->
    <div class="app-section">
        <div class="app-header">
            <div class="app-icon" style="background:rgba(226,179,64,0.15);color:#e2b340;">⚡</div>
            <div>
                <span class="app-name">Alfred IDE</span>
                <span class="app-tag" style="background:rgba(226,179,64,0.12);color:#e2b340;">v2.1.0</span>
            </div>
        </div>
        <p class="app-desc">Cloud-first code editor. VS Code core, Commander extension, AI copilot, 500+ tools. Web-first, with desktop builds for Windows and Linux.</p>
        <div class="dl-grid">
            <a href="https://gositeme.com/alfred-ide/" class="dl-card">
                <div class="icon" style="background:rgba(226,179,64,0.1);color:#e2b340;">🌐</div>
                <div class="info"><h4>Web IDE (Flagship)</h4><span>Open in browser</span></div>
                <span class="arrow">→</span>
            </a>
            <a href="https://gositeme.com/downloads/alfred-ide/Alfred-IDE-Windows-x64.zip.torrent" class="dl-card">
                <div class="icon" style="background:rgba(59,130,246,0.1);color:#3b82f6;">🪟</div>
                <div class="info"><h4>Windows</h4><span>.torrent · x64 Portable · 194 MB</span></div>
                <span class="arrow">🧲</span>
            </a>
            <a href="https://gositeme.com/downloads/alfred-ide/alfred-ide-2.1.0-linux-amd64.deb.torrent" class="dl-card">
                <div class="icon" style="background:rgba(221,72,20,0.1);color:#dd4814;">🐧</div>
                <div class="info"><h4>Linux .deb</h4><span>.torrent · x64 · 80 MB</span></div>
                <span class="arrow">🧲</span>
            </a>
        </div>
        <a href="https://gositeme.com/alfred-ide.php" class="cross-link">→ Alfred IDE launch page</a>
    </div>

    <!-- ═══ CHECKSUMS ═══ -->
    <div class="notice">
        <strong>🔒 Integrity Verification</strong><br>
        Alfred Linux ISO checksums (SHA-256 + BLAKE3) are alongside the ISO.
        GoSiteMe app checksums are available at
        <a href="https://gositeme.com/downloads/SHA256SUMS.txt">SHA256SUMS.txt</a>.<br><br>
        <strong>🧲 P2P / WebTorrent Only</strong><br>
        All downloads on this page are served via WebTorrent and .torrent files — our bandwidth serves our ecosystem, not file hosting.
        Use any torrent client (qBittorrent, Transmission, Deluge) or the <a href="/download">in-browser P2P downloader</a> for the Alfred Linux ISO.
    </div>

</div>

<footer style="text-align:center;padding:1.5rem;color:#94a3b8;font-size:.85rem;border-top:1px solid rgba(255,255,255,0.06);">
    &copy; <?= date('Y') ?> <a href="https://gositeme.com" style="color:#6366f1;text-decoration:none;">GoSiteMe Inc.</a> &mdash; Alfred Linux &middot; Open Source (AGPL-3.0)
</footer>

<?php include __DIR__ . "/includes/shabbat-banner.php"; ?>
</body>
</html>
