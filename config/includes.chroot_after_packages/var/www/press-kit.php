<?php
$page_title = 'Press Kit — GoSiteMe';
$page_description = 'Official GoSiteMe press kit. Brand assets, platform statistics, product suite, and media contact for the world\'s first AI-native technology platform.';
$page_canonical = 'https://root.com/press-kit';
include __DIR__ . '/includes/site-header.inc.php';
?>

<style>
/* ── Press Kit Premium Styles ─────────────────────────────────────── */
.pk-hero{position:relative;padding:6rem 0 5rem;text-align:center;overflow:hidden}
.pk-hero::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 80% 60% at 50% 0%,rgba(0,212,255,.08) 0%,transparent 70%),radial-gradient(ellipse 50% 40% at 80% 20%,rgba(125,0,255,.06) 0%,transparent 60%);pointer-events:none}
.pk-hero-badge{display:inline-flex;align-items:center;gap:.5rem;padding:.4rem 1rem;border-radius:999px;background:linear-gradient(135deg,rgba(0,212,255,.1),rgba(125,0,255,.1));border:1px solid rgba(0,212,255,.2);color:#00d4ff;font-size:.75rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;margin-bottom:1.5rem;backdrop-filter:blur(8px)}
.pk-hero h1{font-size:clamp(2.5rem,5vw,4rem);font-weight:900;margin:0 0 .75rem;background:linear-gradient(135deg,#fff 30%,#00d4ff 70%,#a78bfa);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;line-height:1.1}
.pk-hero .pk-sub{font-size:clamp(1rem,2vw,1.25rem);color:rgba(255,255,255,.55);max-width:700px;margin:0 auto 2.5rem;line-height:1.7;font-weight:400}
.pk-nav{display:flex;gap:.5rem;justify-content:center;flex-wrap:wrap}
.pk-nav a{padding:.55rem 1.25rem;border-radius:999px;font-size:.8rem;font-weight:600;text-decoration:none;transition:all .3s ease;letter-spacing:.02em}
.pk-nav a.pk-primary{background:linear-gradient(135deg,#00d4ff,#0066ff);color:#fff;box-shadow:0 4px 20px rgba(0,100,255,.25)}
.pk-nav a.pk-primary:hover{box-shadow:0 6px 30px rgba(0,100,255,.4);transform:translateY(-1px)}
.pk-nav a.pk-ghost{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);color:rgba(255,255,255,.8)}
.pk-nav a.pk-ghost:hover{background:rgba(255,255,255,.08);border-color:rgba(255,255,255,.2)}

.pk-section{padding:5rem 0;position:relative}
.pk-section::before{content:'';position:absolute;top:0;left:50%;transform:translateX(-50%);width:min(100%,860px);height:1px;background:linear-gradient(90deg,transparent,rgba(255,255,255,.06) 20%,rgba(255,255,255,.06) 80%,transparent)}
.pk-section:first-of-type::before{display:none}
.pk-inner{max-width:1000px;margin:0 auto;padding:0 1.5rem}
.pk-label{display:inline-flex;align-items:center;gap:.4rem;font-size:.7rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;margin-bottom:1rem}
.pk-h2{font-size:clamp(1.6rem,3vw,2.2rem);font-weight:800;margin:0 0 .5rem;color:#fff}
.pk-desc{color:rgba(255,255,255,.45);font-size:.95rem;line-height:1.7;max-width:600px;margin-bottom:2.5rem}

/* Stats Grid */
.pk-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:1px;background:rgba(255,255,255,.04);border-radius:1rem;overflow:hidden;border:1px solid rgba(255,255,255,.06)}
.pk-stat{background:rgba(7,19,25,.8);padding:2rem 1.5rem;text-align:center;transition:background .3s}
.pk-stat:hover{background:rgba(0,212,255,.03)}
.pk-stat-val{font-size:clamp(1.8rem,3vw,2.4rem);font-weight:900;line-height:1;margin-bottom:.4rem}
.pk-stat-label{font-size:.75rem;color:rgba(255,255,255,.4);font-weight:500;letter-spacing:.04em;text-transform:uppercase}
@media(max-width:768px){.pk-stats{grid-template-columns:repeat(2,1fr)}}

/* About Card */
.pk-about-card{background:linear-gradient(135deg,rgba(0,212,255,.03),rgba(125,0,255,.03));border:1px solid rgba(255,255,255,.06);border-radius:1rem;padding:2.5rem;position:relative;overflow:hidden}
.pk-about-card::after{content:'';position:absolute;top:-50%;right:-20%;width:300px;height:300px;background:radial-gradient(circle,rgba(0,212,255,.04),transparent 70%);pointer-events:none}
.pk-about-card p{color:rgba(255,255,255,.7);line-height:1.85;font-size:1rem;position:relative;z-index:1}
.pk-about-card strong{color:#fff}

/* Product Grid */
.pk-products{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem}
@media(max-width:900px){.pk-products{grid-template-columns:repeat(2,1fr)}}
@media(max-width:550px){.pk-products{grid-template-columns:1fr}}
.pk-product{background:rgba(255,255,255,.02);border:1px solid rgba(255,255,255,.05);border-radius:.75rem;padding:1.5rem;text-decoration:none;transition:all .3s ease;position:relative;overflow:hidden}
.pk-product:hover{border-color:rgba(0,212,255,.2);transform:translateY(-2px);box-shadow:0 8px 30px rgba(0,0,0,.3)}
.pk-product-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:1rem;font-size:1.1rem}
.pk-product-name{font-weight:700;color:#fff;font-size:.95rem;margin-bottom:.35rem}
.pk-product-desc{font-size:.8rem;color:rgba(255,255,255,.4);line-height:1.5}

/* Brand Assets */
.pk-assets{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem}
@media(max-width:768px){.pk-assets{grid-template-columns:repeat(2,1fr)}}
.pk-asset{background:rgba(255,255,255,.02);border:1px solid rgba(255,255,255,.05);border-radius:.75rem;padding:1.5rem;text-align:center;text-decoration:none;transition:all .3s;cursor:pointer}
.pk-asset:hover{border-color:rgba(251,191,36,.25);background:rgba(251,191,36,.03);transform:translateY(-1px)}
.pk-asset img{max-width:72px;max-height:56px;margin:0 auto .75rem;display:block;object-fit:contain}
.pk-asset-name{font-size:.78rem;font-weight:600;color:#fff}
.pk-asset-meta{font-size:.65rem;color:rgba(255,255,255,.3);margin-top:.2rem}

/* Colors */
.pk-colors{display:flex;gap:1rem;flex-wrap:wrap}
.pk-color{text-align:center;transition:transform .2s}
.pk-color:hover{transform:scale(1.08)}
.pk-color-swatch{width:64px;height:64px;border-radius:12px;border:2px solid rgba(255,255,255,.06);box-shadow:0 4px 12px rgba(0,0,0,.3)}
.pk-color-name{font-size:.68rem;color:rgba(255,255,255,.45);margin-top:.4rem;font-weight:500}
.pk-color-hex{font-size:.62rem;color:rgba(255,255,255,.25);font-family:monospace}

/* Timeline */
.pk-timeline{position:relative;padding-left:2rem}
.pk-timeline::before{content:'';position:absolute;left:6px;top:4px;bottom:4px;width:2px;background:linear-gradient(180deg,#00d4ff,#7D00FF,#14F195);border-radius:1px;opacity:.3}
.pk-tl-item{position:relative;padding-bottom:1.5rem}
.pk-tl-item::before{content:'';position:absolute;left:-2rem;top:6px;width:14px;height:14px;border-radius:50%;border:2px solid;background:rgba(7,19,25,.9)}
.pk-tl-date{font-size:.72rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;margin-bottom:.25rem}
.pk-tl-event{font-size:.88rem;color:rgba(255,255,255,.6);line-height:1.6}

/* Alfred Linux Feature Card */
.pk-linux-card{background:linear-gradient(135deg,rgba(251,191,36,.04),rgba(20,241,149,.04));border:1px solid rgba(251,191,36,.12);border-radius:1rem;padding:2.5rem;position:relative;overflow:hidden}
.pk-linux-card::before{content:'';position:absolute;top:-30%;right:-10%;width:250px;height:250px;border-radius:50%;background:radial-gradient(circle,rgba(251,191,36,.06),transparent 70%);pointer-events:none}
.pk-linux-badge{display:inline-flex;align-items:center;gap:.4rem;padding:.3rem .8rem;border-radius:999px;font-size:.7rem;font-weight:700;letter-spacing:.05em;margin-bottom:1rem}
.pk-linux-specs{display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem;margin:1.5rem 0}
@media(max-width:600px){.pk-linux-specs{grid-template-columns:1fr}}
.pk-linux-spec{background:rgba(0,0,0,.2);border-radius:.5rem;padding:.75rem 1rem}
.pk-linux-spec-label{font-size:.65rem;color:rgba(255,255,255,.35);text-transform:uppercase;letter-spacing:.06em;font-weight:600}
.pk-linux-spec-val{font-size:.9rem;color:#fff;font-weight:700;margin-top:.15rem}

/* Boilerplate */
.pk-boilerplate{background:rgba(255,255,255,.02);border-left:3px solid #10b981;border-radius:0 .75rem .75rem 0;padding:2rem 2.5rem}
.pk-boilerplate p{font-size:1rem;color:rgba(255,255,255,.65);line-height:1.9;font-style:italic}
.pk-boilerplate .pk-copy-hint{font-style:normal;font-size:.78rem;color:rgba(255,255,255,.3);margin-top:1rem;cursor:pointer;transition:color .2s}
.pk-boilerplate .pk-copy-hint:hover{color:rgba(255,255,255,.5)}

/* Contact */
.pk-contact-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem}
@media(max-width:768px){.pk-contact-grid{grid-template-columns:1fr}}
.pk-contact-card{background:rgba(255,255,255,.02);border:1px solid rgba(255,255,255,.05);border-radius:.75rem;padding:1.5rem;transition:border-color .3s}
.pk-contact-card:hover{border-color:rgba(0,212,255,.15)}
.pk-contact-card h4{font-size:.85rem;font-weight:700;color:#fff;margin-bottom:.75rem;display:flex;align-items:center;gap:.5rem}
.pk-contact-card h4 i{font-size:.75rem;opacity:.5}
.pk-contact-line{display:flex;align-items:center;gap:.6rem;font-size:.82rem;color:rgba(255,255,255,.55);padding:.25rem 0}
.pk-contact-line i{width:14px;text-align:center;font-size:.7rem;color:rgba(255,255,255,.25)}
.pk-contact-line a{color:#00d4ff;text-decoration:none}
.pk-contact-line a:hover{text-decoration:underline}
</style>

<main style="padding-top:5rem;">

    <!-- ═══════ HERO ═══════ -->
    <section class="pk-hero">
        <div class="container">
            <div class="pk-hero-badge">
                <span style="width:6px;height:6px;border-radius:50%;background:#14F195;animation:pulse 2s infinite"></span>
                Official Press Kit
            </div>
            <h1>GoSiteMe</h1>
            <p class="pk-sub">
                The world's first AI-native technology platform. 13,000+ tools. 50M+ agents. 20 VR worlds. One ecosystem. Everything the media needs — all in one place.
            </p>
            <nav class="pk-nav">
                <a href="#about" class="pk-primary"><i class="fas fa-info-circle"></i>&ensp;About</a>
                <a href="#facts" class="pk-ghost"><i class="fas fa-chart-bar"></i>&ensp;Key Facts</a>
                <a href="#brand" class="pk-ghost"><i class="fas fa-palette"></i>&ensp;Brand Assets</a>
                <a href="#alfred-linux" class="pk-ghost"><i class="fab fa-linux"></i>&ensp;Alfred Linux</a>
                <a href="#contact" class="pk-ghost"><i class="fas fa-envelope"></i>&ensp;Contact</a>
            </nav>
        </div>
    </section>

    <!-- ═══════ ABOUT ═══════ -->
    <section class="pk-section" id="about">
        <div class="pk-inner">
            <div class="pk-label" style="color:#00d4ff;"><i class="fas fa-landmark"></i> Who We Are</div>
            <h2 class="pk-h2">The AI Platform That Replaces Everything</h2>
            <p class="pk-desc">One subscription. Every tool a developer, creator, or business needs — from AI to VR to post-quantum encryption.</p>

            <div class="pk-about-card">
                <p><strong>GoSiteMe</strong> is a Canadian AI-native technology platform that unifies cloud hosting, a browser-based IDE, 13,000+ AI tools, 50 million+ specialized AI agents, 20 browser-based VR worlds, a social network, post-quantum encrypted messaging, 29 voice AI products, and a Solana-based token economy into a single integrated ecosystem.</p>
                <p style="margin-top:1.25rem;">Founded and bootstrapped in Thunder Bay, Ontario, GoSiteMe replaces the need for separate subscriptions to ChatGPT, Cursor, hosting providers, game platforms, and communication tools — starting at <strong>$15/month</strong> with everything included.</p>
                <p style="margin-top:1.25rem;"><strong>Flagship products:</strong></p>
                <ul style="margin:.75rem 0 0 1.5rem;color:rgba(255,255,255,.6);line-height:2;">
                    <li><strong>Alfred AI</strong> — 50M+ specialized agents across 17 AI engines with autonomous tool execution</li>
                    <li><strong>Alfred IDE</strong> — Cloud code editor with 875+ integrated tools and AI pair programming</li>
                    <li><strong>Alfred Linux</strong> — AI-native operating system on Linux kernel 7.0 — first distro on earth shipping kernel 7</li>
                    <li><strong>Pulse</strong> — Social network connecting AI, games, crypto, and community</li>
                    <li><strong>Veil</strong> — Post-quantum encrypted messaging with Kyber-1024 cryptography</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- ═══════ KEY FACTS ═══════ -->
    <section class="pk-section" id="facts">
        <div class="pk-inner">
            <div class="pk-label" style="color:#14F195;"><i class="fas fa-signal"></i> By The Numbers</div>
            <h2 class="pk-h2">Platform at a Glance</h2>
            <p class="pk-desc">Real numbers. Real infrastructure. No vaporware.</p>

            <div class="pk-stats">
                <?php
                $facts = [
                    ['13,000+', 'AI Tools', '#00d4ff'],
                    ['50M+', 'AI Agents', '#c084fc'],
                    ['875+', 'MCP Server Tools', '#10b981'],
                    ['20', 'VR Worlds', '#fbbf24'],
                    ['29', 'Voice Products', '#f87171'],
                    ['7.0', 'Linux Kernel', '#14F195'],
                    ['1B', 'GSM Token Supply', '#14F195'],
                    ['$15/mo', 'Starting Price', '#00d4ff'],
                ];
                foreach ($facts as [$val, $label, $color]):
                ?>
                <div class="pk-stat">
                    <div class="pk-stat-val" style="color:<?php echo $color; ?>;"><?php echo htmlspecialchars($val); ?></div>
                    <div class="pk-stat-label"><?php echo htmlspecialchars($label); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ═══════ PRODUCTS ═══════ -->
    <section class="pk-section" id="products">
        <div class="pk-inner">
            <div class="pk-label" style="color:#c084fc;"><i class="fas fa-layer-group"></i> Product Suite</div>
            <h2 class="pk-h2">10 Products. One Platform.</h2>
            <p class="pk-desc">Every product is interconnected — your AI assistant knows your code, your social feed, your VR worlds, and your wallet.</p>

            <div class="pk-products">
                <?php
                $products = [
                    ['Alfred AI', '50M+ specialized agents, 13,000+ tools, 17 AI engines with autonomous multi-step execution.', 'fas fa-robot', '#00d4ff', '/alfred.php'],
                    ['Alfred IDE', 'Cloud code editor with 875+ integrated tools, AI pair programming, and live collaboration.', 'fas fa-code', '#10b981', '/alfred-ide.php'],
                    ['Alfred Linux', 'AI-native operating system on kernel 7.0 — the first distro on earth shipping kernel 7.', 'fab fa-linux', '#fbbf24', 'https://alfredlinux.com'],
                    ['Pulse', 'Social network connecting AI, games, crypto, and community in one unified feed.', 'fas fa-bolt', '#3b82f6', '/pulse.php'],
                    ['Veil', 'Post-quantum encrypted messaging with Kyber-1024 — ready for the quantum era.', 'fas fa-shield-halved', '#a78bfa', '/veil/'],
                    ['VR Worlds', '20 browser-based WebXR worlds — chess, pool, DJ studio, concerts, racing, and more.', 'fas fa-vr-cardboard', '#f87171', '/vr/hub/'],
                    ['Voice AI', '29 products: phone agents, voice cloning, call campaigns, toll-free AI receptionists.', 'fas fa-phone-volume', '#fb923c', '/voice-products.php'],
                    ['GSM Token', 'Solana SPL token live on mainnet — powering the platform economy with 1B supply. Earn, stake, and spend.', 'fas fa-coins', '#14F195', '/blockchain.php'],
                    ['Game Lobby', 'Live game directory with real-time player counts, AI opponents, and telemetry.', 'fas fa-gamepad', '#c084fc', '/game-lobby.php'],
                    ['Community Hub', 'Developer forums, bounty boards, and GSM token rewards for contributions.', 'fas fa-users', '#3b82f6', '/community.php'],
                ];
                foreach ($products as [$name, $desc, $icon, $color, $url]):
                ?>
                <a href="<?php echo htmlspecialchars($url); ?>" class="pk-product">
                    <div class="pk-product-icon" style="background:<?php echo $color; ?>12;">
                        <i class="<?php echo $icon; ?>" style="color:<?php echo $color; ?>;"></i>
                    </div>
                    <div class="pk-product-name"><?php echo htmlspecialchars($name); ?></div>
                    <div class="pk-product-desc"><?php echo htmlspecialchars($desc); ?></div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ═══════ BRAND ASSETS ═══════ -->
    <section class="pk-section" id="brand">
        <div class="pk-inner">
            <div class="pk-label" style="color:#fbbf24;"><i class="fas fa-palette"></i> Brand Identity</div>
            <h2 class="pk-h2">Brand Assets</h2>
            <p class="pk-desc">Official logos, icons, and colors. Do not stretch, recolor, or alter. Click to download.</p>

            <div class="pk-assets">
                <?php
                $assets = [
                    ['GoSiteMe Logo (Light)', '/brand/logo.png', 'PNG'],
                    ['GoSiteMe Logo (Dark)', '/brand/logo_w.png', 'PNG'],
                    ['Alfred Icon', '/brand/alfred-icon.svg', 'SVG'],
                    ['Pulse Icon', '/brand/pulse-icon.svg', 'SVG'],
                    ['Veil Icon', '/brand/veil-icon.svg', 'SVG'],
                    ['Veil Icon', '/brand/veil-icon-256.png', 'PNG'],
                    ['Voice Icon', '/brand/voice-icon.svg', 'SVG'],
                    ['Alfred Icon 512', '/assets/images/alfred-icon-512.png', 'PNG'],
                ];
                foreach ($assets as [$name, $path, $fmt]):
                ?>
                <a href="<?php echo htmlspecialchars($path); ?>" download class="pk-asset">
                    <img src="<?php echo htmlspecialchars($path); ?>" alt="<?php echo htmlspecialchars($name); ?>" loading="lazy">
                    <div class="pk-asset-name"><?php echo htmlspecialchars($name); ?></div>
                    <div class="pk-asset-meta"><?php echo htmlspecialchars($fmt); ?> &middot; Download</div>
                </a>
                <?php endforeach; ?>
            </div>

            <h3 style="font-size:1.15rem;font-weight:800;margin:3rem 0 1.25rem;color:#fff;">Brand Colors</h3>
            <div class="pk-colors">
                <?php
                $colors = [
                    ['Cyan', '#00d4ff'],
                    ['Purple', '#7D00FF'],
                    ['Emerald', '#10b981'],
                    ['Solana Purple', '#9945FF'],
                    ['Solana Green', '#14F195'],
                    ['DeepSpace', '#071319'],
                    ['Coral', '#fb923c'],
                    ['Violet', '#a78bfa'],
                ];
                foreach ($colors as [$cname, $hex]):
                ?>
                <div class="pk-color">
                    <div class="pk-color-swatch" style="background:<?php echo $hex; ?>;"></div>
                    <div class="pk-color-name"><?php echo htmlspecialchars($cname); ?></div>
                    <div class="pk-color-hex"><?php echo htmlspecialchars($hex); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ═══════ TIMELINE ═══════ -->
    <section class="pk-section" id="timeline">
        <div class="pk-inner">
            <div class="pk-label" style="color:#3b82f6;"><i class="fas fa-clock-rotate-left"></i> Milestones</div>
            <h2 class="pk-h2">Company Timeline</h2>
            <p class="pk-desc">From founding to the world's first kernel 7 distro — in under two years.</p>

            <div class="pk-timeline">
                <?php
                $timeline = [
                    ['2024', 'GoSiteMe founded in Thunder Bay, Ontario — AI-powered hosting platform launched', '#00d4ff'],
                    ['2025 Q1', 'Alfred AI assistant introduced with 13,000+ tools across 17 AI engines', '#00d4ff'],
                    ['2025 Q2', 'Pulse social network and Veil post-quantum encrypted messenger launched', '#a78bfa'],
                    ['2025 Q3', 'VR worlds debut — 20 browser-based WebXR experiences go live', '#fbbf24'],
                    ['2025 Q4', 'MCP server reaches 500+ tools; 29 Voice AI products launched', '#f87171'],
                    ['2026 Q1', 'Alfred IDE cloud launch; Alfred Browser desktop apps; Kyber-1024 encryption deployed', '#10b981'],
                    ['2026 Q2', 'Alfred Linux v7.77 GA on kernel 7.0 — first distro on earth with kernel 7; GSM token deployed on Solana mainnet; 875+ MCP tools; Alfred Agent harness operational', '#14F195'],
                ];
                foreach ($timeline as [$date, $event, $color]):
                ?>
                <div class="pk-tl-item">
                    <div style="border-color:<?php echo $color; ?>;" class="pk-tl-dot"></div>
                    <div class="pk-tl-date" style="color:<?php echo $color; ?>;"><?php echo htmlspecialchars($date); ?></div>
                    <div class="pk-tl-event"><?php echo htmlspecialchars($event); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ═══════ ALFRED LINUX ═══════ -->
    <section class="pk-section" id="alfred-linux">
        <div class="pk-inner">
            <div class="pk-label" style="color:#fbbf24;"><i class="fab fa-linux"></i> Operating System</div>
            <h2 class="pk-h2">Alfred Linux v4.0</h2>
            <p class="pk-desc">The world's first AI-native operating system. First distro on earth shipping Linux kernel 7.</p>

            <div class="pk-linux-card">
                <div class="pk-linux-badge" style="background:rgba(20,241,149,.1);border:1px solid rgba(20,241,149,.25);color:#14F195;">
                    <i class="fas fa-circle-check"></i> GA — General Availability
                </div>
                <p style="color:rgba(255,255,255,.7);line-height:1.8;font-size:1rem;position:relative;z-index:1;">
                    <strong style="color:#fff;">Alfred Linux</strong> is a custom Debian Trixie (13) based AI-native operating system shipping the custom-compiled <strong style="color:#14F195;">Linux kernel 7.0</strong> — making it the first distribution on earth to ship kernel 7. It includes Alfred IDE, Alfred Search, Alfred Voice, 32 security modules, post-quantum encryption, and a complete AI development environment out of the box.
                </p>

                <div class="pk-linux-specs">
                    <div class="pk-linux-spec"><div class="pk-linux-spec-label">Base</div><div class="pk-linux-spec-val">Debian Trixie (13)</div></div>
                    <div class="pk-linux-spec"><div class="pk-linux-spec-label">Kernel</div><div class="pk-linux-spec-val" style="color:#14F195;">7.0.0 (Custom)</div></div>
                    <div class="pk-linux-spec"><div class="pk-linux-spec-label">Desktop</div><div class="pk-linux-spec-val">Custom Alfred Desktop</div></div>
                    <div class="pk-linux-spec"><div class="pk-linux-spec-label">Architecture</div><div class="pk-linux-spec-val">amd64</div></div>
                    <div class="pk-linux-spec"><div class="pk-linux-spec-label">Version</div><div class="pk-linux-spec-val">v7.77 GA</div></div>
                    <div class="pk-linux-spec"><div class="pk-linux-spec-label">Security</div><div class="pk-linux-spec-val">32 Modules Active</div></div>
                </div>

                <p style="color:#fff;font-weight:700;margin:1.5rem 0 .5rem;position:relative;z-index:1;">Highlights:</p>
                <ul style="margin:0 0 0 1.5rem;color:rgba(255,255,255,.6);line-height:2;position:relative;z-index:1;">
                    <li><strong style="color:#fff;">First kernel 7 distro</strong> — custom-compiled Linux 7.0.0 with hardware optimization</li>
                    <li>Alfred IDE pre-installed — full code editor experience in the browser</li>
                    <li>Alfred Search for instant local file and document search</li>
                    <li>Alfred Voice for text-to-speech and voice interaction</li>
                    <li>32 security modules active by default — enterprise-grade hardening</li>
                    <li>Post-quantum encryption ready — Kyber-1024 built in</li>
                    <li>Samsung DeX compatible mobile installer for Android</li>
                    <li>BLAKE3 + SHA-256 verified ISO downloads</li>
                </ul>

                <div style="margin-top:1.5rem;display:flex;gap:.75rem;flex-wrap:wrap;position:relative;z-index:1;">
                    <a href="https://alfredlinux.com" style="padding:.5rem 1.2rem;background:linear-gradient(135deg,#fbbf24,#f59e0b);border-radius:999px;color:#000;font-size:.8rem;font-weight:700;text-decoration:none;">alfredlinux.com</a>
                    <a href="https://alfredlinux.com/downloads/" style="padding:.5rem 1.2rem;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:999px;color:#fff;font-size:.8rem;font-weight:600;text-decoration:none;">Downloads</a>
                    <a href="https://alfredlinux.com/docs" style="padding:.5rem 1.2rem;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:999px;color:#fff;font-size:.8rem;font-weight:600;text-decoration:none;">Documentation</a>
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════ BOILERPLATE ═══════ -->
    <section class="pk-section" id="boilerplate">
        <div class="pk-inner">
            <div class="pk-label" style="color:#10b981;"><i class="fas fa-quote-left"></i> Press Copy</div>
            <h2 class="pk-h2">Boilerplate</h2>
            <p class="pk-desc">Approved copy for articles, blog posts, reviews, and press coverage.</p>

            <div class="pk-boilerplate" id="boilerplate-text">
                <p>GoSiteMe is a Canadian AI-native technology platform that unifies cloud hosting, a browser-based IDE, 13,000+ AI tools, 50 million+ specialized agents, 20 browser-based VR worlds, a social network, post-quantum encrypted messaging, 29 voice AI products, and a Solana-based token economy into one integrated ecosystem. Bootstrapped in Thunder Bay, Ontario, GoSiteMe replaces the need for separate subscriptions to ChatGPT, Cursor, hosting, game platforms, and communication tools — starting at $15/month. The platform serves developers, creators, and businesses through its flagship products: Alfred AI (50M+ autonomous agents), Alfred IDE (cloud code editor with 875+ tools), Alfred Linux (first distro on earth with kernel 7), Pulse (social network), and Veil (post-quantum encrypted messaging).</p>
                <div class="pk-copy-hint" onclick="navigator.clipboard.writeText(document.querySelector('#boilerplate-text p').textContent).then(()=>{this.textContent='Copied!';setTimeout(()=>{this.textContent='Click to copy boilerplate →'},2000)})">
                    <i class="fas fa-copy"></i>&ensp;Click to copy boilerplate &rarr;
                </div>
            </div>
        </div>
    </section>

    <!-- ═══════ CONTACT ═══════ -->
    <section class="pk-section" id="contact">
        <div class="pk-inner">
            <div class="pk-label" style="color:#f87171;"><i class="fas fa-satellite-dish"></i> Get In Touch</div>
            <h2 class="pk-h2">Media Contact</h2>
            <p class="pk-desc">All press inquiries are handled by Alfred, our AI representative. Responses within 24 hours.</p>

            <div class="pk-contact-grid">
                <div class="pk-contact-card">
                    <h4><i class="fas fa-robot"></i> Press &amp; Media</h4>
                    <div class="pk-contact-line"><i class="fas fa-robot"></i> Alfred — AI Representative</div>
                    <div class="pk-contact-line"><i class="fas fa-envelope"></i> <a href="mailto:alfred@root.com">alfred@root.com</a></div>
                    <div class="pk-contact-line"><i class="fas fa-phone"></i> 1-833-GOSITEME</div>
                </div>
                <div class="pk-contact-card">
                    <h4><i class="fas fa-link"></i> Key Links</h4>
                    <div class="pk-contact-line"><i class="fas fa-globe"></i> <a href="https://root.com">root.com</a></div>
                    <div class="pk-contact-line"><i class="fab fa-linux"></i> <a href="https://alfredlinux.com">alfredlinux.com</a></div>
                    <div class="pk-contact-line"><i class="fas fa-code"></i> <a href="/developer-portal.php">Developer Portal</a></div>
                    <div class="pk-contact-line"><i class="fas fa-users"></i> <a href="/community.php">Community Hub</a></div>
                </div>
                <div class="pk-contact-card">
                    <h4><i class="fas fa-comments"></i> Social</h4>
                    <div class="pk-contact-line"><i class="fab fa-discord"></i> <a href="https://discord.gg/root">Discord</a></div>
                    <div class="pk-contact-line"><i class="fab fa-telegram"></i> <a href="https://t.me/GoSiteMeBot">Telegram</a></div>
                    <div class="pk-contact-line"><i class="fas fa-bolt"></i> <a href="/pulse.php">Pulse Network</a></div>
                </div>
            </div>
        </div>
    </section>

</main>

<script type="application/ld+json">
<?php echo json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebPage',
    'name' => 'GoSiteMe Press Kit',
    'description' => $page_description,
    'url' => $page_canonical,
    'isPartOf' => ['@type' => 'WebSite', 'name' => 'GoSiteMe', 'url' => 'https://root.com'],
    'about' => [
        '@type' => 'Organization',
        'name' => 'GoSiteMe',
        'url' => 'https://root.com',
        'foundingDate' => '2024',
        'foundingLocation' => 'Thunder Bay, Ontario, Canada',
        'description' => 'AI-native technology platform with 13,000+ tools, 50M+ agents, 20 VR worlds, post-quantum encryption, and a Linux distribution shipping kernel 7.',
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT); ?>
</script>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
