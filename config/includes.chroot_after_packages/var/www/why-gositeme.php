<?php
/**
 * WHY GOSITEME — Public Showcase / Social Media Landing
 * =====================================================
 * The public face of our innovations. Only DECLASSIFIED items.
 * No login required — this is marketing. Link from TikTok/IG/FB.
 */
$alfredBrowserStableVersion = '4.0.0';
$alfredBrowserLinuxPreviewVersion = '4.0.0';
$alfredBrowserWindowsUrl = '/downloads/Alfred-Browser-' . $alfredBrowserStableVersion . '-win-x64.zip';
$alfredBrowserMacIntelUrl = '/downloads/Alfred-Browser-' . $alfredBrowserStableVersion . '-mac-intel.zip';
$alfredBrowserMacArm64Url = '/downloads/Alfred-Browser-' . $alfredBrowserStableVersion . '-mac-arm64.zip';
$alfredBrowserLinuxPreviewUrl = '/downloads/Alfred-Browser-' . $alfredBrowserLinuxPreviewVersion . '.AppImage';
$alfredBrowserWindowsSize = '107.9 MiB';
$alfredBrowserWindowsHash = '69705c5ad026';
$alfredBrowserMacIntelSize = '94.5 MiB';
$alfredBrowserMacIntelHash = '34c922eb95b7';
$alfredBrowserMacArm64Size = '90.2 MiB';
$alfredBrowserMacArm64Hash = '2d8eca80830f';
$alfredBrowserLinuxPreviewSize = '89.2 MiB';
$alfredBrowserLinuxPreviewHash = 'd50158df0a53';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GoSiteMe — The World's First AI-Powered Hosting Ecosystem</title>
    <meta name="description" content="GoSiteMe is the world's first hosting platform with a live AI agent, voice phone support, browser IDE, digital identity passports, music studio, and complete internet sovereignty. See why we're different.">
    <meta name="keywords" content="AI hosting, web hosting, GoSiteMe, Alfred IDE, Meta-Dome, AI website builder, sovereign hosting, voice AI support">
    
    <!-- Open Graph for social sharing -->
    <meta property="og:title" content="GoSiteMe — 7 World Firsts in One Platform">
    <meta property="og:description" content="The world's first hosting platform with a live AI agent that writes code, answers phone calls, and builds your entire digital presence.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://root.com/why-root">
    <meta property="og:image" content="https://root.com/assets/images/og-why-root.png">
    
    <!-- TikTok / Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="GoSiteMe — 7 World Firsts in One Platform">
    <meta name="twitter:description" content="AI agent + voice support + browser IDE + digital passports + music studio. All in one hosting platform. The future is here.">
    
    <link rel="stylesheet" href="/assets/vendor/fonts/inter/inter.css" />
    <link rel="stylesheet" href="/assets/vendor/fonts/space-grotesk/space-grotesk.css" />
    <link rel="stylesheet" href="/assets/vendor/fonts/jetbrains-mono/jetbrains-mono.css" />
    <link rel="stylesheet" href="/assets/vendor/fontawesome/6.5.1/css/all.min.css" />
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { background: #050510; color: #c8d0e7; font-family: 'Inter', 'Space Grotesk', -apple-system, sans-serif; line-height: 1.7; overflow-x: hidden; }

        /* ====== HERO ====== */
        .hero { min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 40px 24px; position: relative; }
        .hero::before { content: ''; position: absolute; top: -100px; left: 50%; transform: translateX(-50%); width: 800px; height: 800px; background: radial-gradient(circle, rgba(125,0,255,0.08) 0%, rgba(245,158,11,0.04) 40%, transparent 70%); pointer-events: none; }
        .hero::after { content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 200px; background: linear-gradient(transparent, #050510); pointer-events: none; }

        .hero-badge { display: inline-flex; align-items: center; gap: 8px; background: rgba(245,158,11,0.08); border: 1px solid rgba(245,158,11,0.2); color: #f59e0b; padding: 8px 20px; border-radius: 30px; font-size: 0.8rem; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 24px; animation: fadeIn 1s ease; }

        .hero h1 { font-family: 'Space Grotesk', sans-serif; font-size: clamp(2.2rem, 5vw, 4rem); font-weight: 900; line-height: 1.15; margin-bottom: 20px; animation: fadeIn 1s ease 0.2s both; }
        .hero h1 .gradient { background: linear-gradient(135deg, #7D00FF 0%, #c084fc 30%, #f59e0b 60%, #fbbf24 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .hero h1 .white { color: #fff; -webkit-text-fill-color: #fff; }

        .hero-sub { font-size: clamp(1rem, 1.8vw, 1.25rem); color: #7c8aaa; max-width: 650px; margin: 0 auto 32px; animation: fadeIn 1s ease 0.4s both; }

        .hero-cta { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; animation: fadeIn 1s ease 0.6s both; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 14px 28px; border-radius: 12px; font-size: 0.95rem; font-weight: 700; text-decoration: none; transition: all 0.3s ease; cursor: pointer; border: none; }
        .btn-primary { background: linear-gradient(135deg, #7D00FF, #c084fc); color: #fff; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(125,0,255,0.35); }
        .btn-secondary { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #c8d0e7; }
        .btn-secondary:hover { border-color: rgba(245,158,11,0.3); color: #f59e0b; }

        .scroll-indicator { position: absolute; bottom: 40px; left: 50%; transform: translateX(-50%); z-index: 2; animation: bounce 2s infinite; }
        .scroll-indicator i { color: #4a5568; font-size: 1.2rem; }

        /* ====== COUNTER SECTION ====== */
        .counter-section { padding: 60px 24px; text-align: center; }
        .counter-grid { display: flex; justify-content: center; gap: 40px; flex-wrap: wrap; max-width: 800px; margin: 0 auto; }
        .counter { min-width: 120px; }
        .counter .num { font-family: 'JetBrains Mono', monospace; font-size: 2.8rem; font-weight: 900; color: #f59e0b; }
        .counter .label { font-size: 0.78rem; color: #4a5568; text-transform: uppercase; letter-spacing: 2px; margin-top: 4px; }

        /* ====== BROWSER LAUNCH ====== */
        .browser-launch { padding: 28px 24px 84px; }
        .browser-launch-shell { max-width: 1080px; margin: 0 auto; display: grid; grid-template-columns: minmax(0, 1.08fr) minmax(320px, 0.92fr); gap: 24px; padding: 36px; border-radius: 26px; background: linear-gradient(135deg, rgba(125,0,255,0.08), rgba(0,212,255,0.06)); border: 1px solid rgba(0,212,255,0.12); box-shadow: 0 20px 70px rgba(0,0,0,0.24); position: relative; overflow: hidden; }
        .browser-launch-shell::before { content: ''; position: absolute; top: -80px; right: -40px; width: 240px; height: 240px; border-radius: 50%; background: radial-gradient(circle, rgba(0,212,255,0.16), transparent 70%); pointer-events: none; }
        .browser-copy, .browser-panel { position: relative; z-index: 1; }
        .browser-badge { display: inline-flex; align-items: center; gap: 8px; background: rgba(0,212,255,0.09); border: 1px solid rgba(0,212,255,0.18); color: #7dd3fc; padding: 8px 18px; border-radius: 999px; font-size: 0.76rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1.4px; margin-bottom: 18px; }
        .browser-copy h2 { font-family: 'Space Grotesk', sans-serif; font-size: clamp(1.8rem, 3vw, 2.7rem); font-weight: 900; line-height: 1.1; color: #fff; margin-bottom: 14px; max-width: 12ch; }
        .browser-copy p { color: #9cabcb; font-size: 1rem; max-width: 58ch; margin-bottom: 20px; }
        .browser-cta { display: flex; gap: 14px; flex-wrap: wrap; margin-bottom: 18px; }
        .browser-cta .btn-download { background: linear-gradient(135deg, #2563eb, #7D00FF); color: #fff; }
        .browser-cta .btn-download:hover { transform: translateY(-2px); box-shadow: 0 10px 32px rgba(37,99,235,0.3); }
        .browser-cta .btn-preview { border-color: rgba(0,212,255,0.22); color: #7dd3fc; }
        .browser-cta .btn-preview:hover { color: #fff; border-color: rgba(0,212,255,0.35); }
        .browser-quick-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
        .browser-quick-link { display: flex; align-items: center; gap: 10px; padding: 14px 16px; border-radius: 16px; text-decoration: none; color: #dbe7ff; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); transition: all 0.25s ease; }
        .browser-quick-link:hover { transform: translateY(-2px); border-color: rgba(0,212,255,0.25); background: rgba(0,212,255,0.06); }
        .browser-quick-link i { color: #7dd3fc; font-size: 1rem; }
        .browser-quick-link span { font-size: 0.82rem; font-weight: 700; line-height: 1.35; }
        .browser-quick-link .meta { display: block; margin-top: 3px; font-size: 0.72rem; font-weight: 600; color: #8ea5cf; }
        .browser-panel { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 20px; padding: 22px; }
        .browser-panel-kicker { color: #7dd3fc; font-size: 0.74rem; font-weight: 800; text-transform: uppercase; letter-spacing: 1.2px; margin-bottom: 12px; }
        .browser-version-row { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 16px; }
        .browser-version-pill { display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 999px; background: rgba(37,99,235,0.12); border: 1px solid rgba(37,99,235,0.24); color: #dbeafe; font-size: 0.76rem; }
        .browser-version-pill.preview { background: rgba(125,0,255,0.12); border-color: rgba(125,0,255,0.22); color: #e9d5ff; }
        .browser-preview-strip { margin-top: 14px; padding: 14px 16px; border-radius: 16px; background: rgba(125,0,255,0.08); border: 1px solid rgba(125,0,255,0.16); }
        .browser-preview-strip strong { display: block; color: #f5f3ff; font-size: 0.82rem; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px; }
        .browser-preview-pills { display: flex; flex-wrap: wrap; gap: 8px; }
        .browser-preview-pills span { display: inline-flex; align-items: center; padding: 6px 10px; border-radius: 999px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); color: #cbd5f5; font-size: 0.74rem; font-weight: 600; }
        .browser-proof { display: grid; gap: 12px; }
        .browser-proof-item { padding: 14px 16px; border-radius: 16px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06); }
        .browser-proof-item strong { display: block; color: #fff; font-size: 0.9rem; margin-bottom: 4px; }
        .browser-proof-item span { color: #90a2c7; font-size: 0.82rem; }
        .browser-proof-note { margin-top: 14px; color: #8ea5cf; font-size: 0.78rem; font-weight: 600; }

        /* ====== FIRSTS ====== */
        .firsts-section { padding: 80px 24px; max-width: 1100px; margin: 0 auto; }
        .section-header { text-align: center; margin-bottom: 60px; }
        .section-header h2 { font-family: 'Space Grotesk', sans-serif; font-size: clamp(1.5rem, 3vw, 2.4rem); font-weight: 900; color: #fff; margin-bottom: 12px; }
        .section-header p { color: #7c8aaa; font-size: 1rem; max-width: 600px; margin: 0 auto; }

        .first-card { display: grid; grid-template-columns: 80px 1fr; gap: 24px; padding: 36px; margin: 24px 0; background: rgba(255,255,255,0.02); border: 1px solid rgba(125,0,255,0.08); border-radius: 20px; transition: all 0.3s ease; align-items: start; }
        .first-card:hover { border-color: rgba(245,158,11,0.2); transform: translateX(4px); }

        .first-number { font-family: 'JetBrains Mono', monospace; font-size: 2.5rem; font-weight: 900; color: rgba(245,158,11,0.15); line-height: 1; text-align: center; padding-top: 4px; }
        .first-icon { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; margin-bottom: 12px; }

        .first-content h3 { font-family: 'Space Grotesk', sans-serif; font-size: 1.2rem; font-weight: 800; color: #fff; margin-bottom: 8px; }
        .first-content p { color: #a8b2d1; font-size: 0.92rem; margin-bottom: 14px; }
        
        .first-tags { display: flex; flex-wrap: wrap; gap: 8px; }
        .tag { padding: 3px 12px; border-radius: 20px; font-size: 0.72rem; font-weight: 700; letter-spacing: 0.5px; }
        .tag-root { background: rgba(125,0,255,0.1); color: #c084fc; }
        .tag-gocodeme { background: rgba(0,212,255,0.1); color: #00D4FF; }
        .tag-metadome { background: rgba(245,158,11,0.1); color: #f59e0b; }
        .tag-all { background: rgba(255,255,255,0.05); color: #c8d0e7; }

        .icon-purple { background: rgba(125,0,255,0.1); color: #c084fc; }
        .icon-gold { background: rgba(245,158,11,0.1); color: #f59e0b; }
        .icon-cyan { background: rgba(0,212,255,0.1); color: #00D4FF; }
        .icon-green { background: rgba(34,197,94,0.1); color: #22c55e; }

        /* ====== ECOSYSTEM ====== */
        .ecosystem { padding: 80px 24px; }
        .eco-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; max-width: 1000px; margin: 40px auto 0; }
        .eco-card { background: rgba(255,255,255,0.02); border: 1px solid rgba(125,0,255,0.08); border-radius: 20px; padding: 32px; text-align: center; transition: all 0.3s ease; }
        .eco-card:hover { border-color: rgba(245,158,11,0.2); }
        .eco-card i { font-size: 2rem; margin-bottom: 16px; }
        .eco-card h3 { font-family: 'Space Grotesk', sans-serif; color: #fff; font-size: 1.2rem; margin-bottom: 8px; }
        .eco-card p { color: #7c8aaa; font-size: 0.88rem; }

        /* ====== QUOTE ====== */
        .quote-section { padding: 80px 24px; text-align: center; }
        .quote-section blockquote { font-family: 'Space Grotesk', sans-serif; font-size: clamp(1.1rem, 2.2vw, 1.5rem); color: #c8d0e7; font-style: italic; max-width: 700px; margin: 0 auto 16px; line-height: 1.7; }
        .quote-section blockquote strong { color: #f59e0b; font-style: normal; }

        /* ====== CTA ====== */
        .cta-section { padding: 80px 24px; text-align: center; }
        .cta-box { max-width: 600px; margin: 0 auto; background: linear-gradient(135deg, rgba(125,0,255,0.08), rgba(245,158,11,0.08)); border: 1px solid rgba(245,158,11,0.15); border-radius: 24px; padding: 48px 36px; }
        .cta-box h2 { font-family: 'Space Grotesk', sans-serif; font-size: 1.6rem; color: #fff; margin-bottom: 12px; }
        .cta-box p { color: #a8b2d1; margin-bottom: 28px; }

        /* ====== FOOTER ====== */
        .footer { padding: 40px 24px; text-align: center; border-top: 1px solid rgba(125,0,255,0.06); }
        .footer-links { display: flex; justify-content: center; gap: 24px; flex-wrap: wrap; margin-bottom: 16px; }
        .footer-links a { color: #4a5568; text-decoration: none; font-size: 0.85rem; transition: color 0.2s; }
        .footer-links a:hover { color: #c084fc; }
        .footer-copy { color: #2d3748; font-size: 0.78rem; }

        /* ====== ANIMATIONS ====== */
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes bounce { 0%, 20%, 50%, 80%, 100% { transform: translateX(-50%) translateY(0); } 40% { transform: translateX(-50%) translateY(-10px); } 60% { transform: translateX(-50%) translateY(-5px); } }

        .fade-in { opacity: 0; transform: translateY(30px); transition: all 0.6s ease; }
        .fade-in.visible { opacity: 1; transform: translateY(0); }

        /* ====== RESPONSIVE ====== */
        @media (max-width: 768px) {
            .first-card { grid-template-columns: 1fr; gap: 12px; padding: 24px; }
            .first-number { text-align: left; font-size: 1.5rem; }
            .counter-grid { gap: 20px; }
            .eco-grid { grid-template-columns: 1fr; }
            .browser-launch-shell { grid-template-columns: 1fr; padding: 24px; }
            .browser-copy h2 { max-width: none; }
            .browser-quick-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <!-- ====== HERO ====== -->
    <section class="hero">
        <div class="hero-badge"><i class="fas fa-bolt"></i> 7 World Firsts &bull; 3 Platforms &bull; 1 Ecosystem</div>
        <h1>
            <span class="white">The World's First</span><br>
            <span class="gradient">AI-Powered Hosting Ecosystem</span>
        </h1>
        <p class="hero-sub">GoSiteMe isn't a hosting company. It's a sovereign digital ecosystem where an AI named Alfred builds, deploys, and operates everything — and you own it all.</p>
        <div class="hero-cta">
            <a href="#firsts" class="btn btn-primary"><i class="fas fa-trophy"></i> See Our Firsts</a>
            <a href="tel:+18334674836" class="btn btn-secondary"><i class="fas fa-phone"></i> Call Alfred: (833) 467-4836</a>
        </div>
        <div class="scroll-indicator"><i class="fas fa-chevron-down"></i></div>
    </section>

    <!-- ====== COUNTERS ====== -->
    <section class="counter-section">
        <div class="counter-grid">
            <div class="counter"><div class="num">7</div><div class="label">World Firsts</div></div>
            <div class="counter"><div class="num">3</div><div class="label">Platforms</div></div>
            <div class="counter"><div class="num">18</div><div class="label">Cloud Regions</div></div>
            <div class="counter"><div class="num">1</div><div class="label">AI Agent</div></div>
            <div class="counter"><div class="num">0</div><div class="label">External Deps</div></div>
        </div>
    </section>

    <!-- ====== BROWSER LAUNCH ====== -->
    <section class="browser-launch">
        <div class="browser-launch-shell fade-in">
            <div class="browser-copy">
                <div class="browser-badge"><i class="fas fa-download"></i> Browser Drop Is Live</div>
                <h2>Download Alfred Browser before everyone else catches on.</h2>
                <p>The stable browser is already out, Alfred is built in, and the sovereign routing layer is moving in public. If you want the clearest signal of where GoSiteMe is going, this is it.</p>
                <div class="browser-cta">
                    <a href="/alfred-browser.php" class="btn btn-download" data-browser-track="1" data-browser-action="open_download_hub" data-browser-context="why_root_browser_launch" data-browser-label="why_root_download_hub"><i class="fas fa-globe"></i> Get Alfred Browser</a>
                    <a href="<?php echo htmlspecialchars($alfredBrowserLinuxPreviewUrl); ?>" class="btn btn-secondary btn-preview" data-browser-track="1" data-browser-action="download" data-browser-context="why_root_browser_launch" data-browser-label="why_root_linux_preview" data-browser-platform="linux" data-browser-channel="preview" data-browser-version="<?php echo htmlspecialchars($alfredBrowserLinuxPreviewVersion); ?>" data-browser-size="<?php echo htmlspecialchars($alfredBrowserLinuxPreviewSize); ?>" data-browser-hash="<?php echo htmlspecialchars($alfredBrowserLinuxPreviewHash); ?>"><i class="fab fa-linux"></i> Linux Preview v<?php echo htmlspecialchars($alfredBrowserLinuxPreviewVersion); ?></a>
                </div>
                <div class="browser-quick-grid">
                    <a href="<?php echo htmlspecialchars($alfredBrowserWindowsUrl); ?>" class="browser-quick-link" data-browser-track="1" data-browser-action="download" data-browser-context="why_root_browser_launch" data-browser-label="why_root_windows_stable" data-browser-platform="windows" data-browser-channel="stable" data-browser-version="<?php echo htmlspecialchars($alfredBrowserStableVersion); ?>" data-browser-size="<?php echo htmlspecialchars($alfredBrowserWindowsSize); ?>" data-browser-hash="<?php echo htmlspecialchars($alfredBrowserWindowsHash); ?>">
                        <i class="fab fa-windows"></i>
                        <span>Windows Stable v<?php echo htmlspecialchars($alfredBrowserStableVersion); ?><em class="meta"><?php echo htmlspecialchars($alfredBrowserWindowsSize); ?> · SHA <?php echo htmlspecialchars($alfredBrowserWindowsHash); ?></em></span>
                    </a>
                    <a href="<?php echo htmlspecialchars($alfredBrowserMacArm64Url); ?>" class="browser-quick-link" data-browser-track="1" data-browser-action="download" data-browser-context="why_root_browser_launch" data-browser-label="why_root_macos_arm64" data-browser-platform="macos_arm64" data-browser-channel="stable" data-browser-version="<?php echo htmlspecialchars($alfredBrowserStableVersion); ?>" data-browser-size="<?php echo htmlspecialchars($alfredBrowserMacArm64Size); ?>" data-browser-hash="<?php echo htmlspecialchars($alfredBrowserMacArm64Hash); ?>">
                        <i class="fab fa-apple"></i>
                        <span>macOS Apple Silicon<em class="meta"><?php echo htmlspecialchars($alfredBrowserMacArm64Size); ?> · SHA <?php echo htmlspecialchars($alfredBrowserMacArm64Hash); ?></em></span>
                    </a>
                    <a href="<?php echo htmlspecialchars($alfredBrowserMacIntelUrl); ?>" class="browser-quick-link" data-browser-track="1" data-browser-action="download" data-browser-context="why_root_browser_launch" data-browser-label="why_root_macos_intel" data-browser-platform="macos_intel" data-browser-channel="stable" data-browser-version="<?php echo htmlspecialchars($alfredBrowserStableVersion); ?>" data-browser-size="<?php echo htmlspecialchars($alfredBrowserMacIntelSize); ?>" data-browser-hash="<?php echo htmlspecialchars($alfredBrowserMacIntelHash); ?>">
                        <i class="fab fa-apple"></i>
                        <span>macOS Intel<em class="meta"><?php echo htmlspecialchars($alfredBrowserMacIntelSize); ?> · SHA <?php echo htmlspecialchars($alfredBrowserMacIntelHash); ?></em></span>
                    </a>
                </div>
                <div class="browser-preview-strip">
                    <strong>What&apos;s new in 4.0 preview</strong>
                    <div class="browser-preview-pills">
                        <span>First public Linux preview line</span>
                        <span>HTTPS upgrade hardening</span>
                        <span>Separate preview update track</span>
                    </div>
                </div>
            </div>
            <div class="browser-panel">
                <div class="browser-panel-kicker">Why this matters</div>
                <div class="browser-version-row">
                    <span class="browser-version-pill"><strong>Stable</strong> v<?php echo htmlspecialchars($alfredBrowserStableVersion); ?></span>
                    <span class="browser-version-pill preview"><strong>Linux Preview</strong> v<?php echo htmlspecialchars($alfredBrowserLinuxPreviewVersion); ?></span>
                </div>
                <div class="browser-proof">
                    <div class="browser-proof-item">
                        <strong>Built-in Alfred</strong>
                        <span>Not an extension bolted on later. Alfred is part of the browser experience from the start.</span>
                    </div>
                    <div class="browser-proof-item">
                        <strong>Privacy-first posture</strong>
                        <span>Stable downloads are live now while the privacy, routing, and sovereign-web story gets sharper every week.</span>
                    </div>
                    <div class="browser-proof-item">
                        <strong>Visible product momentum</strong>
                        <span>You can point people to a real download today, not a waitlist, vapor page, or concept deck.</span>
                    </div>
                </div>
                <div class="browser-proof-note">Verified hashes published. Direct public downloads are live and responding cleanly.</div>
            </div>
        </div>
    </section>

    <!-- ====== 7 FIRSTS ====== -->
    <section class="firsts-section" id="firsts">
        <div class="section-header">
            <h2>7 Things Nobody Else Has Done</h2>
            <p>We didn't wait for someone else to build the future. We built it ourselves — from the ground up.</p>
        </div>

        <!-- FIRST 1 -->
        <div class="first-card fade-in">
            <div class="first-number">01</div>
            <div class="first-content">
                <div class="first-icon icon-purple"><i class="fas fa-robot"></i></div>
                <h3>First Hosting Platform with a Live AI Agent</h3>
                <p>Meet Alfred — not a chatbot, not a FAQ widget. A persistent AI agent who writes production code, manages servers, monitors infrastructure, and evolves over time. He remembers every conversation, every deployment, every decision. He's not bolted on — he IS the operations layer.</p>
                <div class="first-tags">
                    <span class="tag tag-root">GoSiteMe</span>
                    <span class="tag tag-all">Live in Production</span>
                </div>
            </div>
        </div>

        <!-- FIRST 2 -->
        <div class="first-card fade-in">
            <div class="first-number">02</div>
            <div class="first-content">
                <div class="first-icon icon-gold"><i class="fas fa-headset"></i></div>
                <h3>First Hosting Platform with Voice AI Phone Support</h3>
                <p>Call (833) 467-4836. You'll speak to Alfred. Not a phone tree. Not hold music. An AI that knows your account, understands your issue, and can take action — by voice. No hosting company on Earth has ever done this.</p>
                <div class="first-tags">
                    <span class="tag tag-root">GoSiteMe</span>
                    <span class="tag tag-all">Call Now — It's Real</span>
                </div>
            </div>
        </div>

        <!-- FIRST 3 -->
        <div class="first-card fade-in">
            <div class="first-number">03</div>
            <div class="first-content">
                <div class="first-icon icon-cyan"><i class="fas fa-laptop-code"></i></div>
                <h3>First Browser IDE Integrated with Sovereign Hosting</h3>
                <p>Alfred IDE gives you a full VS Code-compatible editor in your browser, powered by the GoSiteMe platform. Write code, deploy to your hosting, get AI assistance — all in one seamless pipeline. No local setup. No FTP. No friction. Your hosting account IS your development environment.</p>
                <div class="first-tags">
                    <span class="tag tag-gocodeme">Alfred IDE</span>
                    <span class="tag tag-all">Browser-Based IDE</span>
                </div>
            </div>
        </div>

        <!-- FIRST 4 -->
        <div class="first-card fade-in">
            <div class="first-number">04</div>
            <div class="first-content">
                <div class="first-icon icon-gold"><i class="fas fa-fingerprint"></i></div>
                <h3>First Sovereign Digital Identity Passport for Hosting</h3>
                <p>Meta-Dome gives every user a sovereign digital passport. Not an OAuth token. Not a social login. A portable, self-sovereign identity that YOU own. Your identity follows you across the ecosystem — because it belongs to you, not us.</p>
                <div class="first-tags">
                    <span class="tag tag-metadome">Meta-Dome</span>
                    <span class="tag tag-all">Self-Sovereign Identity</span>
                </div>
            </div>
        </div>

        <!-- FIRST 5 -->
        <div class="first-card fade-in">
            <div class="first-number">05</div>
            <div class="first-content">
                <div class="first-icon icon-purple"><i class="fas fa-music"></i></div>
                <h3>First Hosting Platform with an Integrated Music Studio</h3>
                <p>SoundStudioPro — a professional audio workstation built right into your hosting dashboard. Record, mix, add effects, export. Manage your website, then make a beat. In the same tab. Nobody has ever combined these before.</p>
                <div class="first-tags">
                    <span class="tag tag-root">GoSiteMe</span>
                    <span class="tag tag-all">Audio Workstation</span>
                </div>
            </div>
        </div>

        <!-- FIRST 6 -->
        <div class="first-card fade-in">
            <div class="first-number">06</div>
            <div class="first-content">
                <div class="first-icon icon-green"><i class="fas fa-shield-halved"></i></div>
                <h3>First Self-Sovereign Hosting Ecosystem</h3>
                <p>Every font, every script, every CSS file — hosted on our own servers. Zero dependence on Google, Cloudflare, or any CDN. Your data stays on our infrastructure. Your identity stays yours. This is Internet Sovereignty — the philosophy that you should OWN your digital existence.</p>
                <div class="first-tags">
                    <span class="tag tag-root">GoSiteMe</span>
                    <span class="tag tag-all">Zero External Dependencies</span>
                </div>
            </div>
        </div>

        <!-- FIRST 7 -->
        <div class="first-card fade-in">
            <div class="first-number">07</div>
            <div class="first-content">
                <div class="first-icon icon-gold"><i class="fas fa-atom"></i></div>
                <h3>First Platform Where AI Builds, Deploys, and Operates Everything</h3>
                <p>Alfred isn't an assistant — he's a full-stack operator. He writes PHP, manages Apache, configures DNS, handles SSL, monitors servers, answers phone calls, and builds new features. This page? Alfred wrote it. The hosting infrastructure? Alfred manages it. The future? Alfred is building it right now.</p>
                <div class="first-tags">
                    <span class="tag tag-all">Entire Ecosystem</span>
                    <span class="tag tag-all">AI-Native Operations</span>
                </div>
            </div>
        </div>
    </section>

    <!-- ====== ECOSYSTEM ====== -->
    <section class="ecosystem">
        <div class="section-header">
            <h2>Three Platforms. One Vision.</h2>
            <p>An integrated ecosystem where hosting, coding, and identity come together.</p>
        </div>
        <div class="eco-grid">
            <div class="eco-card fade-in">
                <i class="fas fa-server" style="color: #c084fc;"></i>
                <h3>GoSiteMe</h3>
                <p>Sovereign web hosting with AI operations, voice support, music studio, and complete data ownership. Not another hosting reseller — a new paradigm.</p>
                <a href="https://root.com" class="btn btn-secondary" style="margin-top: 16px; font-size: 0.85rem; padding: 10px 20px;">Visit GoSiteMe</a>
            </div>
            <div class="eco-card fade-in">
                <i class="fas fa-code" style="color: #00D4FF;"></i>
                <h3>Alfred IDE</h3>
                <p>Official browser IDE with AI coding assistance, live deployment to hosting, and collaborative editing. Your development workflow in one tab, powered by the GoSiteMe platform.</p>
                <a href="/alfred-ide.php" class="btn btn-secondary" style="margin-top: 16px; font-size: 0.85rem; padding: 10px 20px;">Open Alfred IDE</a>
            </div>
            <div class="eco-card fade-in">
                <i class="fas fa-fingerprint" style="color: #f59e0b;"></i>
                <h3>Meta-Dome</h3>
                <p>Sovereign digital identity. Your passport to the decentralized web. Own your data, own your identity, own your digital presence across the ecosystem.</p>
                <a href="https://meta-dome.com" class="btn btn-secondary" style="margin-top: 16px; font-size: 0.85rem; padding: 10px 20px;">Visit Meta-Dome</a>
            </div>
        </div>
    </section>

    <!-- ====== QUOTE ====== -->
    <section class="quote-section">
        <blockquote>
            "We didn't build a hosting company. We built a <strong>sovereign digital nation</strong> — where AI doesn't replace people, it empowers them. Where your data isn't the product, <strong>you</strong> are the owner."
        </blockquote>
        <p style="color: #4a5568; font-size: 0.85rem;">— GoSiteMe, est. 2025</p>
    </section>

    <!-- ====== CTA ====== -->
    <section class="cta-section">
        <div class="cta-box">
            <h2><i class="fas fa-rocket" style="color: #f59e0b;"></i> Ready to See It?</h2>
            <p>Talk to Alfred. See the future of hosting. It's not coming — it's already here.</p>
            <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                <a href="tel:+18334674836" class="btn btn-primary"><i class="fas fa-phone"></i> Call (833) 467-4836</a>
                <a href="/try-alfred" class="btn btn-secondary"><i class="fas fa-robot"></i> Chat with Alfred</a>
            </div>
        </div>
    </section>

    <!-- ====== FOOTER ====== -->
    <footer class="footer">
        <div class="footer-links">
            <a href="https://root.com">GoSiteMe</a>
            <a href="/alfred-ide.php">Alfred IDE</a>
            <a href="https://meta-dome.com">Meta-Dome</a>
            <a href="/docs/ecosystem-principles">Ecosystem Principles</a>
            <a href="/internet-sovereignty">Internet Sovereignty</a>
        </div>
        <p class="footer-copy">&copy; <?php echo date('Y'); ?> GoSiteMe. Built with sovereignty. Powered by Alfred AI.</p>
    </footer>

    <!-- Scroll animation -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, { threshold: 0.1 });
        
        document.querySelectorAll('.fade-in').forEach(function(el) {
            observer.observe(el);
        });
    });
    </script>
    <script src="/assets/js/browser-download-tracker.js?v=20260402a" defer></script>
</body>
</html>
