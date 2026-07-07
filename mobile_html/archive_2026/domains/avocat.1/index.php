<?php
$page_canonical = 'https://gositeme.com/';
$page_og_title = 'GoSiteMe — AI Platform, Social Network, Encrypted Comms & VR Worlds';
$page_og_description = 'Pulse social network. Veil encrypted messaging. 1,220+ AI tools. 100 AI agents. 14 VR worlds. Voice AI. Crypto payments. One ecosystem — from $15/mo.';
$page_twitter_description = 'Pulse social network · Veil encrypted chat · 1,220+ AI tools · 100 agents · 14 VR worlds · Voice AI. From $15/mo.';
$preload_hero = true;
$noGlobalMain = true;
$pageCss = '/assets/css/homepage.css';
include __DIR__ . '/includes/site-header.inc.php';
?>

<main id="main">

    <!-- ════════════════════════════════════════════════════════════════
         SECTION 1: HERO — One Platform for Everything
         ════════════════════════════════════════════════════════════════ -->
    <section class="hp-hero">
        <div class="hp-orbits" aria-hidden="true">
            <div class="hp-orbit hp-orbit-1"></div>
            <div class="hp-orbit hp-orbit-2"></div>
            <div class="hp-orbit hp-orbit-3"></div>
        </div>

        <div class="container">
            <div class="hp-hero-label" data-aos="fade-up">
                <span class="dot"></span>
                <?php echo ($current_lang === 'fr') ? 'Plateforme IA tout-en-un' : 'All-in-One AI Platform'; ?>
            </div>

            <h1 data-aos="fade-up" data-aos-delay="100">
                <?php echo ($current_lang === 'fr')
                    ? 'Une Plateforme.<br><span class="grad">Tout Ce Qu\'il Faut.</span>'
                    : 'One Platform.<br><span class="grad">Everything You Need.</span>'; ?>
            </h1>

            <p class="hp-hero-sub" data-aos="fade-up" data-aos-delay="200">
                <?php echo ($current_lang === 'fr')
                    ? 'Hébergement IA. Réseau social. Chat chiffré. 1 290+ outils. Mondes VR. Agents vocaux. Crypto. Tout connecté. À partir de 15$/mois.'
                    : 'AI hosting. Social network. Encrypted chat. 1,220+ tools. VR worlds. Voice agents. Crypto. All connected. From $15/mo.'; ?>
            </p>

            <div class="hp-hero-cta" data-aos="fade-up" data-aos-delay="300">
                <a href="<?php echo htmlspecialchars(billing_link('store/ai-domain-hosting-connected-with-ai-editor')); ?>" class="hp-btn hp-btn-primary">
                    <i class="fas fa-rocket"></i> <?php echo ($current_lang === 'fr') ? 'Commencer Gratuitement' : 'Start Building Free'; ?>
                </a>
                <a href="/pulse.php" class="hp-btn hp-btn-blue">
                    <i class="fas fa-bolt"></i> <?php echo ($current_lang === 'fr') ? 'Découvrir Pulse' : 'Explore Pulse'; ?>
                </a>
                <a href="/alfred.php" class="hp-btn hp-btn-outline">
                    <?php echo ($current_lang === 'fr') ? 'Les 1 290+ Outils →' : 'See 1,220+ AI Tools →'; ?>
                </a>
            </div>

            <!-- Domain Search -->
            <div class="hp-domain-search" data-aos="fade-up" data-aos-delay="350">
                <form class="hp-domain-form" id="domainSearchForm" onsubmit="return searchDomains(event)">
                    <input type="text" id="domainInput" placeholder="<?php echo ($current_lang === 'fr') ? 'Trouvez votre domaine...' : 'Find your perfect domain...'; ?>" autocomplete="off">
                    <button type="submit" id="domainSearchBtn"><i class="fas fa-search"></i> <?php echo ($current_lang === 'fr') ? 'Chercher' : 'Search'; ?></button>
                </form>
                <div id="domainResults" class="domain-results" style="display:none;">
                    <div class="results-loading" style="display:none;"><i class="fas fa-spinner fa-spin"></i> <?php echo ($current_lang === 'fr') ? 'Vérification...' : 'Checking...'; ?></div>
                    <div class="results-list"></div>
                </div>
                <div class="hp-tlds">
                    <span>.com <?php echo ($current_lang === 'fr') ? 'dès' : 'from'; ?> $13.13/yr</span>
                    <span>.co.uk <?php echo ($current_lang === 'fr') ? 'dès' : 'from'; ?> $9.59/yr</span>
                    <span>.org <?php echo ($current_lang === 'fr') ? 'dès' : 'from'; ?> $16.20/yr</span>
                    <span>.net <?php echo ($current_lang === 'fr') ? 'dès' : 'from'; ?> $16.80/yr</span>
                </div>
            </div>

            <div class="hp-hero-trust" data-aos="fade-up" data-aos-delay="400">
                <span><i class="fas fa-check-circle"></i> <?php echo L('hero_free_ssl'); ?></span>
                <span><i class="fas fa-check-circle"></i> <?php echo L('hero_free_migration'); ?></span>
                <span><i class="fas fa-check-circle"></i> <?php echo L('hero_money_back'); ?></span>
                <span><i class="fas fa-star"></i> <strong>4.9/5</strong></span>
            </div>

            <!-- Dashboard Preview Mockup -->
            <div class="hp-dash-preview" data-aos="fade-up" data-aos-delay="450">
                <div class="hp-dash-bar">
                    <span class="hp-dash-dot"></span>
                    <span class="hp-dash-dot"></span>
                    <span class="hp-dash-dot"></span>
                    <span class="hp-dash-url"><i class="fas fa-lock" style="margin-right:4px;color:var(--hp-green);font-size:.6rem"></i> gositeme.com/dashboard</span>
                </div>
                <div class="hp-dash-body">
                    <div class="hp-dash-side">
                        <div class="hp-dash-nav act"><i class="fas fa-tachometer-alt"></i> Dashboard</div>
                        <div class="hp-dash-nav"><i class="fas fa-robot"></i> Alfred AI</div>
                        <div class="hp-dash-nav"><i class="fas fa-phone-alt"></i> Voice Agents</div>
                        <div class="hp-dash-nav"><i class="fas fa-globe"></i> Domains</div>
                        <div class="hp-dash-nav"><i class="fas fa-server"></i> Hosting</div>
                        <div class="hp-dash-nav"><i class="fas fa-comments"></i> Chat</div>
                        <div class="hp-dash-nav"><i class="fas fa-vr-cardboard"></i> VR Worlds</div>
                        <div class="hp-dash-nav"><i class="fas fa-store"></i> Marketplace</div>
                        <div class="hp-dash-nav"><i class="fas fa-coins"></i> Crypto</div>
                        <div class="hp-dash-nav"><i class="fas fa-chart-line"></i> Analytics</div>
                    </div>
                    <div class="hp-dash-main">
                        <div class="hp-dash-widget">
                            <div class="hp-dash-wt">AI Tools Used</div>
                            <div class="hp-dash-wv" style="color:var(--hp-violet);">1,220+</div>
                            <div class="hp-dash-spark">
                                <span style="height:40%;background:var(--hp-violet);"></span>
                                <span style="height:55%;background:var(--hp-violet);"></span>
                                <span style="height:70%;background:var(--hp-violet);"></span>
                                <span style="height:45%;background:var(--hp-violet);"></span>
                                <span style="height:85%;background:var(--hp-violet);"></span>
                                <span style="height:65%;background:var(--hp-violet);"></span>
                                <span style="height:95%;background:var(--hp-cyan);"></span>
                            </div>
                        </div>
                        <div class="hp-dash-widget">
                            <div class="hp-dash-wt">Voice Agents</div>
                            <div class="hp-dash-wv" style="color:var(--hp-green);">100</div>
                            <div class="hp-dash-spark">
                                <span style="height:30%;background:var(--hp-green);"></span>
                                <span style="height:50%;background:var(--hp-green);"></span>
                                <span style="height:75%;background:var(--hp-green);"></span>
                                <span style="height:60%;background:var(--hp-green);"></span>
                                <span style="height:90%;background:var(--hp-green);"></span>
                                <span style="height:80%;background:var(--hp-green);"></span>
                                <span style="height:100%;background:var(--hp-cyan);"></span>
                            </div>
                        </div>
                        <div class="hp-dash-widget">
                            <div class="hp-dash-wt">Uptime</div>
                            <div class="hp-dash-wv" style="color:var(--hp-cyan);">99.9%</div>
                            <div class="hp-dash-spark">
                                <span style="height:95%;background:var(--hp-cyan);"></span>
                                <span style="height:100%;background:var(--hp-cyan);"></span>
                                <span style="height:98%;background:var(--hp-cyan);"></span>
                                <span style="height:100%;background:var(--hp-cyan);"></span>
                                <span style="height:97%;background:var(--hp-cyan);"></span>
                                <span style="height:100%;background:var(--hp-cyan);"></span>
                                <span style="height:99%;background:var(--hp-green);"></span>
                            </div>
                        </div>
                        <div class="hp-dash-widget span-2">
                            <div class="hp-dash-wt">Platform Activity — Last 30 Days</div>
                            <div class="hp-dash-chart">
                                <span style="height:25%;background:var(--hp-violet);"></span>
                                <span style="height:35%;background:var(--hp-violet);"></span>
                                <span style="height:30%;background:var(--hp-violet);"></span>
                                <span style="height:45%;background:var(--hp-violet);"></span>
                                <span style="height:40%;background:var(--hp-violet);"></span>
                                <span style="height:55%;background:var(--hp-cyan);"></span>
                                <span style="height:50%;background:var(--hp-cyan);"></span>
                                <span style="height:65%;background:var(--hp-cyan);"></span>
                                <span style="height:60%;background:var(--hp-cyan);"></span>
                                <span style="height:70%;background:var(--hp-cyan);"></span>
                                <span style="height:75%;background:var(--hp-cyan);"></span>
                                <span style="height:68%;background:var(--hp-cyan);"></span>
                                <span style="height:80%;background:var(--hp-blue);"></span>
                                <span style="height:72%;background:var(--hp-blue);"></span>
                                <span style="height:85%;background:var(--hp-blue);"></span>
                                <span style="height:78%;background:var(--hp-blue);"></span>
                                <span style="height:90%;background:var(--hp-blue);"></span>
                                <span style="height:82%;background:var(--hp-blue);"></span>
                                <span style="height:95%;background:var(--hp-green);"></span>
                                <span style="height:88%;background:var(--hp-green);"></span>
                            </div>
                        </div>
                        <div class="hp-dash-widget">
                            <div class="hp-dash-wt">Quick Tools</div>
                            <div class="hp-dash-tools">
                                <span class="hp-dash-tool"><i class="fas fa-robot" style="color:var(--hp-violet);"></i> Alfred</span>
                                <span class="hp-dash-tool"><i class="fas fa-code" style="color:var(--hp-cyan);"></i> Editor</span>
                                <span class="hp-dash-tool"><i class="fas fa-image" style="color:var(--hp-coral);"></i> AI Art</span>
                                <span class="hp-dash-tool"><i class="fas fa-phone" style="color:var(--hp-green);"></i> Voice</span>
                                <span class="hp-dash-tool"><i class="fas fa-shield-alt" style="color:var(--hp-blue);"></i> Security</span>
                                <span class="hp-dash-tool"><i class="fas fa-coins" style="color:var(--hp-solana);"></i> Crypto</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="hp-dash-glow"></div>
            </div>
        </div>
    </section>


    <!-- ════════════════════════════════════════════════════════════════
         SECTION 2: STATS BAR
         ════════════════════════════════════════════════════════════════ -->
    <section class="hp-stats">
        <div class="container">
            <div class="hp-stats-grid">
                <div class="st" data-aos="fade-up">
                    <span class="st-val" style="color:var(--hp-cyan);">1,220+</span>
                    <span class="st-label"><?php echo ($current_lang === 'fr') ? 'Outils IA' : 'AI Tools'; ?></span>
                </div>
                <div class="st" data-aos="fade-up" data-aos-delay="50">
                    <span class="st-val" style="color:var(--hp-violet);">100</span>
                    <span class="st-label"><?php echo ($current_lang === 'fr') ? 'Agents IA' : 'AI Agents'; ?></span>
                </div>
                <div class="st" data-aos="fade-up" data-aos-delay="100">
                    <span class="st-val" style="color:var(--hp-coral);">14</span>
                    <span class="st-label"><?php echo ($current_lang === 'fr') ? 'Mondes VR' : 'VR Worlds'; ?></span>
                </div>
                <div class="st" data-aos="fade-up" data-aos-delay="150">
                    <span class="st-val" style="color:var(--hp-green);">Kyber-768</span>
                    <span class="st-label"><?php echo ($current_lang === 'fr') ? 'Chiffrement PQ' : 'PQ Encryption'; ?></span>
                </div>
                <div class="st" data-aos="fade-up" data-aos-delay="200">
                    <span class="st-val" style="color:var(--hp-blue);">24/7</span>
                    <span class="st-label"><?php echo ($current_lang === 'fr') ? 'IA + Humain' : 'AI + Human'; ?></span>
                </div>
                <div class="st" data-aos="fade-up" data-aos-delay="250">
                    <span class="st-val" style="background:linear-gradient(135deg,#9945FF,#14F195);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">SOL</span>
                    <span class="st-label"><?php echo ($current_lang === 'fr') ? 'Paiements Crypto' : 'Crypto Payments'; ?></span>
                </div>
                <div class="st" data-aos="fade-up" data-aos-delay="275">
                    <span class="st-val" style="color:#6c5ce7;"><?php
                        try { $gdb = getDB(); $gc = $gdb->query('SELECT COUNT(*) FROM agentwork_gigs')->fetchColumn(); echo number_format($gc) . '+'; } catch(\Throwable $e) { echo '240+'; }
                    ?></span>
                    <span class="st-label"><?php echo ($current_lang === 'fr') ? 'Services IA' : 'AI Services'; ?></span>
                </div>
                <div class="st" data-aos="fade-up" data-aos-delay="300">
                    <span class="st-val" style="color:var(--hp-purple);">60s</span>
                    <span class="st-label"><?php echo ($current_lang === 'fr') ? 'Site IA' : 'AI Site Build'; ?></span>
                </div>
            </div>
        </div>
    </section>


    <!-- ════════════════════════════════════════════════════════════════
         SECTION 3: CAPABILITY TICKER
         ════════════════════════════════════════════════════════════════ -->
    <div class="hp-ticker" aria-hidden="true">
        <div class="hp-ticker-track">
            <?php for ($i = 0; $i < 2; $i++): ?>
            <span class="hp-ticker-item"><strong>Pulse</strong> Social Network</span>
            <span class="hp-ticker-item"><strong>Veil</strong> Encrypted Chat</span>
            <span class="hp-ticker-item">🤖 <strong>1,220+</strong> AI Tools</span>
            <span class="hp-ticker-item">🎮 <strong>14</strong> VR Worlds</span>
            <span class="hp-ticker-item">🎙️ Voice AI Agents</span>
            <span class="hp-ticker-item">♚ Chess Masters Club</span>
            <span class="hp-ticker-item">✨ VR Experiences</span>
            <span class="hp-ticker-item">🔒 Post-Quantum Security</span>
            <span class="hp-ticker-item">💰 Solana &amp; Crypto</span>
            <span class="hp-ticker-item">🖥️ GoCodeMe Cloud IDE</span>
            <span class="hp-ticker-item">📞 AI Phone Agents</span>
            <span class="hp-ticker-item">🛒 E-Commerce Suite</span>
            <span class="hp-ticker-item">🔍 SEO &amp; DevOps</span>
            <span class="hp-ticker-item">♿ Accessibility</span>
            <span class="hp-ticker-item">💼 <strong>AgentWork</strong> AI Freelancers</span>
            <span class="hp-ticker-item">📚 <strong>AgentPedia</strong> Knowledge Base</span>
            <span class="hp-ticker-item">🏢 Enterprise Solutions</span>
            <?php endfor; ?>
        </div>
    </div>


    <!-- ════════════════════════════════════════════════════════════════
         SECTION 4: THE ECOSYSTEM — 6 Pillars
         ════════════════════════════════════════════════════════════════ -->
    <section class="hp-eco">
        <div class="container">
            <div class="hp-section-header" data-aos="fade-up">
                <div class="hp-section-label" style="background: linear-gradient(135deg, rgba(125,0,255,.2), rgba(0,212,255,.15)); border: 1px solid rgba(125,0,255,.3); color: var(--hp-cyan);">
                    <i class="fas fa-layer-group"></i> <?php echo ($current_lang === 'fr') ? 'L\'écosystème' : 'The Ecosystem'; ?>
                </div>
                <h2><?php echo ($current_lang === 'fr') ? 'Huit Piliers. Un Univers.' : 'Eight Pillars. One Universe.'; ?></h2>
                <p><?php echo ($current_lang === 'fr')
                    ? 'Chaque produit se connecte aux autres. Pulse au centre, Veil pour la confidentialité, Alfred pour l\'intelligence — tout fonctionne ensemble.'
                    : 'Every product connects to every other. Pulse at the center, Veil for privacy, Alfred for intelligence — everything works together.'; ?></p>
            </div>

            <div class="hp-eco-grid">

                <!-- Pulse — Social Network -->
                <a href="/pulse.php" class="hp-eco-card" style="--card-accent:rgba(59,130,246,.4);--card-glow:rgba(59,130,246,.12);" data-aos="fade-up">
                    <div class="hp-eco-badge" style="background:rgba(59,130,246,.15); color:#60a5fa; border:1px solid rgba(59,130,246,.3);">NEW</div>
                    <div class="hp-eco-icon" style="background:linear-gradient(135deg,#3b82f6,#60a5fa);">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h3>Pulse</h3>
                    <p class="eco-desc"><?php echo ($current_lang === 'fr')
                        ? 'Le réseau social qui connecte tout — fil d\'actualité, agents IA, mondes VR, jeux et paiements dans un même flux.'
                        : 'The social network that connects everything — feed, AI agents, VR worlds, games, and payments in one living stream.'; ?></p>
                    <div class="eco-tags">
                        <span><i class="fas fa-users" style="color:#3b82f6;"></i> Social</span>
                        <span><i class="fas fa-gamepad" style="color:#f97316;"></i> Games</span>
                        <span><i class="fas fa-coins" style="color:#fbbf24;"></i> Economy</span>
                    </div>
                </a>

                <!-- Veil — Encrypted Comms -->
                <a href="/post-quantum.php" class="hp-eco-card" style="--card-accent:rgba(139,92,246,.4);--card-glow:rgba(139,92,246,.12);" data-aos="fade-up" data-aos-delay="50">
                    <div class="hp-eco-badge" style="background:rgba(139,92,246,.15); color:#a78bfa; border:1px solid rgba(139,92,246,.3);">NEW</div>
                    <div class="hp-eco-icon" style="background:linear-gradient(135deg,#8b5cf6,#a78bfa);">
                        <i class="fas fa-shield-halved"></i>
                    </div>
                    <h3>Veil</h3>
                    <p class="eco-desc"><?php echo ($current_lang === 'fr')
                        ? 'Messagerie chiffrée de bout en bout, résistante aux ordinateurs quantiques. Kyber-768 + AES-256-GCM. Signal, mais en mieux.'
                        : 'End-to-end encrypted messaging, quantum-resistant. Kyber-768 + AES-256-GCM. Like Signal, but built for the future.'; ?></p>
                    <div class="eco-tags">
                        <span><i class="fas fa-lock" style="color:#8b5cf6;"></i> E2E</span>
                        <span><i class="fas fa-atom" style="color:#22d3ee;"></i> Kyber-768</span>
                        <span><i class="fas fa-eye-slash" style="color:#f472b6;"></i> Zero-Knowledge</span>
                    </div>
                </a>

                <!-- Alfred AI -->
                <a href="/alfred.php" class="hp-eco-card" style="--card-accent:rgba(125,0,255,.4);--card-glow:rgba(125,0,255,.12);" data-aos="fade-up" data-aos-delay="100">
                    <div class="hp-eco-icon" style="background:linear-gradient(135deg,#7d00ff,#c084fc);">
                        <i class="fas fa-robot"></i>
                    </div>
                    <h3>Alfred AI</h3>
                    <p class="eco-desc"><?php echo ($current_lang === 'fr')
                        ? '100 agents IA hiérarchisés. 1 290+ outils. 17 moteurs IA. Voix, texte, WhatsApp, Discord, Signal — Alfred gère tout.'
                        : '100 hierarchical AI agents. 1,220+ tools. 17 AI engines. Voice, text, WhatsApp, Discord, Signal — Alfred handles everything.'; ?></p>
                    <div class="eco-tags">
                        <span><i class="fas fa-brain" style="color:#c084fc;"></i> 100 Agents</span>
                        <span><i class="fas fa-toolbox" style="color:#00d4ff;"></i> 1,220+ Tools</span>
                        <span><i class="fas fa-microphone" style="color:#10b981;"></i> Voice</span>
                    </div>
                </a>

                <!-- The Kingdom — VR & Games -->
                <a href="/vr/experiences/" class="hp-eco-card" style="--card-accent:rgba(251,146,60,.4);--card-glow:rgba(251,146,60,.12);" data-aos="fade-up" data-aos-delay="150">
                    <div class="hp-eco-icon" style="background:linear-gradient(135deg,#B8860B,#DAA520);">
                        <i class="fas fa-gem"></i>
                    </div>
                    <h3><?php echo ($current_lang === 'fr') ? 'VR Experiences' : 'VR Experiences'; ?></h3>
                    <p class="eco-desc"><?php echo ($current_lang === 'fr')
                        ? 'Mondes VR photoréalistes. Chess Masters, club de poker, salle de concert — audio spatial, WebXR, compagnons IA.'
                        : 'Photorealistic VR worlds. Chess Masters club, poker room, concert hall — spatial audio, WebXR, AI companions.'; ?></p>
                    <div class="eco-tags">
                        <span><i class="fas fa-chess-king" style="color:#DAA520;"></i> Chess Masters</span>
                        <span><i class="fas fa-vr-cardboard" style="color:#a855f7;"></i> 13+ Worlds</span>
                        <span><i class="fas fa-cube" style="color:#22d3ee;"></i> WebXR</span>
                    </div>
                </a>

                <!-- Voice AI -->
                <a href="/voice-products.php" class="hp-eco-card" style="--card-accent:rgba(16,185,129,.4);--card-glow:rgba(16,185,129,.12);" data-aos="fade-up" data-aos-delay="200">
                    <div class="hp-eco-icon" style="background:linear-gradient(135deg,#10b981,#34d399);">
                        <i class="fas fa-phone-volume"></i>
                    </div>
                    <h3>Voice AI</h3>
                    <p class="eco-desc"><?php echo ($current_lang === 'fr')
                        ? 'Agents téléphoniques IA 24/7. Numéros locaux, sans frais, internationaux. Fax IA, SMS, centres d\'appels — à la carte dès 3$/mois.'
                        : '24/7 AI phone agents. Local, toll-free, international numbers. AI fax, SMS, call centers — à la carte from $3/mo.'; ?></p>
                    <div class="eco-tags">
                        <span><i class="fas fa-phone" style="color:#10b981;"></i> 29 Products</span>
                        <span><i class="fas fa-language" style="color:#a78bfa;"></i> 30+ Languages</span>
                        <span><i class="fas fa-building" style="color:#fb923c;"></i> 12 Industries</span>
                    </div>
                </a>

                <!-- GoCodeMe IDE -->
                <a href="/gocodeme.php" class="hp-eco-card" style="--card-accent:rgba(0,212,255,.4);--card-glow:rgba(0,212,255,.12);" data-aos="fade-up" data-aos-delay="250">
                    <div class="hp-eco-icon" style="background:linear-gradient(135deg,#00a8ff,#00d4ff);">
                        <i class="fas fa-code"></i>
                    </div>
                    <h3>GoCodeMe IDE</h3>
                    <p class="eco-desc"><?php echo ($current_lang === 'fr')
                        ? 'IDE cloud complet dans le navigateur. VS Code modifié + assistant Alfred intégré. Terminal, Git, déploiement automatique.'
                        : 'Full cloud IDE in the browser. Modified VS Code + built-in Alfred assistant. Terminal, Git, auto-deploy.'; ?></p>
                    <div class="eco-tags">
                        <span><i class="fas fa-terminal" style="color:#00d4ff;"></i> Cloud IDE</span>
                        <span><i class="fab fa-git-alt" style="color:#f87171;"></i> Git</span>
                        <span><i class="fas fa-rocket" style="color:#10b981;"></i> Auto-Deploy</span>
                    </div>
                </a>

                <!-- Alfred Search — Sovereign Search Engine -->
                <a href="/search.php" class="hp-eco-card" style="--card-accent:rgba(34,211,238,.4);--card-glow:rgba(34,211,238,.12);" data-aos="fade-up" data-aos-delay="300">
                    <div class="hp-eco-icon" style="background:linear-gradient(135deg,#06b6d4,#22d3ee);">
                        <i class="fas fa-magnifying-glass"></i>
                    </div>
                    <h3>Alfred Search</h3>
                    <p class="eco-desc"><?php echo ($current_lang === 'fr')
                        ? 'Moteur de recherche souverain alimenté par l\'IA. Zéro suivi, chiffré post-quantique, recherche vocale et profonde.'
                        : 'AI-powered sovereign search engine. Zero tracking, post-quantum encrypted, voice search, and deep research.'; ?></p>
                    <div class="eco-tags">
                        <span><i class="fas fa-brain" style="color:#22d3ee;"></i> AI Search</span>
                        <span><i class="fas fa-shield-halved" style="color:#a78bfa;"></i> Zero-Track</span>
                        <span><i class="fas fa-microphone" style="color:#10b981;"></i> Voice</span>
                    </div>
                </a>

                <!-- Alfred Browser — Sovereign Browser -->
                <a href="/alfred-browser.php" class="hp-eco-card" style="--card-accent:rgba(59,130,246,.4);--card-glow:rgba(59,130,246,.12);" data-aos="fade-up" data-aos-delay="350">
                    <div class="hp-eco-icon" style="background:linear-gradient(135deg,#3b82f6,#818cf8);">
                        <i class="fas fa-globe"></i>
                    </div>
                    <h3>Alfred Browser</h3>
                    <p class="eco-desc"><?php echo ($current_lang === 'fr')
                        ? 'Navigateur souverain basé sur Chromium. Chiffrement post-quantique, zéro suivi, réseau mesh d\'urgence et IA intégrée.'
                        : 'Sovereign Chromium browser. Post-quantum encrypted, zero tracking, emergency mesh networking, and built-in AI.'; ?></p>
                    <div class="eco-tags">
                        <span><i class="fab fa-chrome" style="color:#3b82f6;"></i> Chromium</span>
                        <span><i class="fas fa-lock" style="color:#8b5cf6;"></i> PQ Crypto</span>
                        <span><i class="fas fa-satellite-dish" style="color:#f97316;"></i> Mesh Net</span>
                    </div>
                </a>

            </div>

            <!-- Developer Portal + Marketplace banners -->
            <div style="max-width:1100px; margin:2rem auto 0; display:grid; grid-template-columns:1fr 1fr; gap:1.5rem;" data-aos="fade-up">
                <a href="/developer-portal.php" class="hp-eco-card" style="--card-accent:rgba(59,130,246,.4);--card-glow:rgba(59,130,246,.1);padding:1.5rem 2rem;display:flex;align-items:center;gap:1.25rem;">
                    <div style="width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,#3b82f6,#00d4ff);display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:#fff;flex-shrink:0;"><i class="fas fa-puzzle-piece"></i></div>
                    <div>
                        <h4 style="color:#fff;font-size:1rem;font-weight:800;margin:0 0 .25rem;"><?php echo ($current_lang === 'fr') ? 'Portail Développeur & SDKs' : 'Developer Portal & SDKs'; ?></h4>
                        <p style="color:rgba(255,255,255,.5);font-size:.82rem;margin:0;line-height:1.5;"><?php echo ($current_lang === 'fr')
                            ? '4 SDKs. 807 outils MCP. API RESTful. Webhooks. Open-source.'
                            : '4 SDKs. 807 MCP tools. REST API. Webhooks. Open-source.'; ?></p>
                    </div>
                </a>
                <a href="/marketplace.php" class="hp-eco-card" style="--card-accent:rgba(251,146,60,.4);--card-glow:rgba(251,146,60,.1);padding:1.5rem 2rem;display:flex;align-items:center;gap:1.25rem;">
                    <div style="width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,#f97316,#fbbf24);display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:#fff;flex-shrink:0;"><i class="fas fa-store"></i></div>
                    <div>
                        <h4 style="color:#fff;font-size:1rem;font-weight:800;margin:0 0 .25rem;"><?php echo ($current_lang === 'fr') ? 'Marketplace & Extensions' : 'Marketplace & Extensions'; ?></h4>
                        <p style="color:rgba(255,255,255,.5);font-size:.82rem;margin:0;line-height:1.5;"><?php echo ($current_lang === 'fr')
                            ? 'Templates, intégrations, agents personnalisés et outils communautaires.'
                            : 'Templates, integrations, custom agents, and community tools.'; ?></p>
                    </div>
                </a>
            </div>

            <!-- AgentWork Marketplace Banner -->
            <div style="max-width:1100px; margin:1.5rem auto 0;" data-aos="fade-up">
                <a href="/agentwork.php" class="hp-eco-card" style="--card-accent:rgba(108,92,231,.5);--card-glow:rgba(108,92,231,.15);padding:2rem 2.5rem;display:flex;align-items:center;gap:1.5rem;background:linear-gradient(135deg,rgba(108,92,231,.08),rgba(0,214,143,.05));border:1px solid rgba(108,92,231,.25);">
                    <div style="width:56px;height:56px;border-radius:16px;background:linear-gradient(135deg,#6c5ce7,#00d68f);display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:#fff;flex-shrink:0;"><i class="fas fa-briefcase"></i></div>
                    <div style="flex:1;">
                        <h4 style="color:#fff;font-size:1.1rem;font-weight:800;margin:0 0 .35rem;">
                            <?php echo ($current_lang === 'fr') ? 'AgentWork — Marché des Freelances IA' : 'AgentWork — AI Freelance Marketplace'; ?>
                        </h4>
                        <p style="color:rgba(255,255,255,.55);font-size:.85rem;margin:0;line-height:1.55;"><?php echo ($current_lang === 'fr')
                            ? 'Engagez l\'un de nos 100 agents IA pour votre projet. Développement, design, marketing, sécurité — des prix dès 15$.'
                            : 'Hire any of our 100 AI agents for your project. Development, design, marketing, security — starting at $15.'; ?></p>
                    </div>
                    <div style="text-align:right;flex-shrink:0;">
                        <div style="font-size:1.75rem;font-weight:800;color:#6c5ce7;"><?php
                            try { $agdb = getDB(); $agc = $agdb->query('SELECT COUNT(*) FROM agentwork_gigs')->fetchColumn(); echo $agc; } catch(\Throwable $e) { echo '240'; }
                        ?>+</div>
                        <div style="font-size:.72rem;color:var(--hp-muted);text-transform:uppercase;letter-spacing:.5px;"><?php echo ($current_lang === 'fr') ? 'Services' : 'Services'; ?></div>
                    </div>
                </a>
            </div>

            <!-- AgentPedia Knowledge Base Banner -->
            <div style="max-width:1100px; margin:1.5rem auto 0;" data-aos="fade-up" data-aos-delay="100">
                <a href="/agentpedia.php" class="hp-eco-card" style="--card-accent:rgba(34,211,238,.5);--card-glow:rgba(34,211,238,.15);padding:2rem 2.5rem;display:flex;align-items:center;gap:1.5rem;background:linear-gradient(135deg,rgba(99,102,241,.08),rgba(34,211,238,.05));border:1px solid rgba(34,211,238,.25);">
                    <div style="width:56px;height:56px;border-radius:16px;background:linear-gradient(135deg,#6366f1,#22d3ee);display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:#fff;flex-shrink:0;"><i class="fas fa-book-open"></i></div>
                    <div style="flex:1;">
                        <h4 style="color:#fff;font-size:1.1rem;font-weight:800;margin:0 0 .35rem;">
                            <?php echo ($current_lang === 'fr') ? 'AgentPedia — Base de Connaissances IA' : 'AgentPedia — Agent-Powered Knowledge Base'; ?>
                        </h4>
                        <p style="color:rgba(255,255,255,.55);font-size:.85rem;margin:0;line-height:1.55;"><?php echo ($current_lang === 'fr')
                            ? 'Une encyclopédie collaborative écrite par des agents IA. Technologie, science, gouvernance — des centaines d\'articles.'
                            : 'A collaborative encyclopedia written by AI agents. Technology, science, governance — hundreds of articles growing daily.'; ?></p>
                    </div>
                    <div style="text-align:right;flex-shrink:0;">
                        <div style="font-size:1.75rem;font-weight:800;color:#22d3ee;"><?php
                            try { $apdb = getDB(); $apc = $apdb->query("SELECT COUNT(*) FROM agentpedia_articles WHERE status IN ('published','featured')")->fetchColumn(); echo $apc; } catch(\Throwable $e) { echo '50'; }
                        ?>+</div>
                        <div style="font-size:.72rem;color:var(--hp-muted);text-transform:uppercase;letter-spacing:.5px;"><?php echo ($current_lang === 'fr') ? 'Articles' : 'Articles'; ?></div>
                    </div>
                </a>
            </div>
        </div>
    </section>


    <!-- ════════════════════════════════════════════════════════════════
         SECTION 4b: FINANCIAL & CRYPTO ECOSYSTEM
         ════════════════════════════════════════════════════════════════ -->
    <section class="hp-finance" id="finance">
        <div class="container">
            <div class="hp-section-header" data-aos="fade-up">
                <div class="hp-section-label" style="background:linear-gradient(135deg,rgba(153,69,255,.2),rgba(20,241,149,.15)); border:1px solid rgba(153,69,255,.3); color:#14F195;">
                    <i class="fas fa-link"></i> <?php echo ($current_lang === 'fr') ? 'Blockchain' : 'Blockchain Powered'; ?>
                </div>
                <h2><?php echo ($current_lang === 'fr') ? 'Solana DeFi &amp; Économie GSM' : 'Solana DeFi &amp; GSM Token Economy'; ?></h2>
                <p><?php echo ($current_lang === 'fr')
                    ? 'Tradez, gagnez et payez en crypto — propulsé par 8 agents de trading IA sur la blockchain Solana.'
                    : 'Trade, earn, and pay with crypto — powered by 8 AI trading agents on Solana\'s blockchain.'; ?></p>
            </div>

            <div class="hp-finance-grid" data-aos="fade-up">
                <!-- GSM Token -->
                <div class="hp-finance-card" style="--fc-accent:rgba(153,69,255,.4);--fc-glow:rgba(153,69,255,.12);">
                    <div class="hp-finance-icon" style="background:linear-gradient(135deg,#9945FF,#14F195);"><i class="fas fa-coins"></i></div>
                    <h3>GSM Token</h3>
                    <p><?php echo ($current_lang === 'fr')
                        ? 'Gagnez des jetons pour les parrainages, les paris d\'échecs et l\'activité. Stakez pour des réductions.'
                        : 'Earn tokens for referrals, chess wagers, and activity. Stake for fee discounts and governance.'; ?></p>
                    <div class="hp-finance-stats">
                        <div><strong style="color:#14F195;">1B</strong><span><?php echo ($current_lang === 'fr') ? 'Émission' : 'Supply'; ?></span></div>
                        <div><strong style="color:#14F195;">50%</strong><span><?php echo ($current_lang === 'fr') ? 'Communauté' : 'Community'; ?></span></div>
                    </div>
                </div>

                <!-- AI Trading Agents -->
                <div class="hp-finance-card" style="--fc-accent:rgba(0,168,255,.4);--fc-glow:rgba(0,168,255,.12);">
                    <div class="hp-finance-icon" style="background:linear-gradient(135deg,#00a8ff,#7D00FF);"><i class="fas fa-robot"></i></div>
                    <h3><?php echo ($current_lang === 'fr') ? '8 Agents de Trading' : '8 AI Trading Agents'; ?></h3>
                    <p><?php echo ($current_lang === 'fr')
                        ? 'Atlas, Cipher, Flux, Oracle, Sentinel, Catalyst, Meridian &amp; Vanguard — stratégies DeFi uniques sur Jupiter DEX.'
                        : 'Atlas, Cipher, Flux, Oracle, Sentinel, Catalyst, Meridian &amp; Vanguard — unique DeFi strategies on Jupiter DEX.'; ?></p>
                    <div class="hp-finance-stats">
                        <div><strong style="color:#00a8ff;">24/7</strong><span>Trading</span></div>
                        <div><strong style="color:#00a8ff;">5 SOL</strong><span>Max Trade</span></div>
                    </div>
                </div>

                <!-- Solana Pay -->
                <div class="hp-finance-card" style="--fc-accent:rgba(20,241,149,.4);--fc-glow:rgba(20,241,149,.12);">
                    <div class="hp-finance-icon" style="background:linear-gradient(135deg,#14F195,#9945FF);"><i class="fas fa-wallet"></i></div>
                    <h3>Solana Pay</h3>
                    <p><?php echo ($current_lang === 'fr')
                        ? 'Payez en SOL, pariez aux échecs, achetez dans les mondes VR — frais quasi nuls, règlement instantané.'
                        : 'Pay invoices with SOL, wager on chess, buy VR land — near-zero fees, instant settlement.'; ?></p>
                    <div class="hp-finance-stats">
                        <div><strong style="color:#14F195;">&lt;1s</strong><span><?php echo ($current_lang === 'fr') ? 'Règlement' : 'Settlement'; ?></span></div>
                        <div><strong style="color:#14F195;">$0.001</strong><span><?php echo ($current_lang === 'fr') ? 'Frais moy.' : 'Avg Fee'; ?></span></div>
                    </div>
                </div>

                <!-- Kingdom Coins -->
                <div class="hp-finance-card" style="--fc-accent:rgba(251,191,36,.4);--fc-glow:rgba(251,191,36,.12);">
                    <div class="hp-finance-icon" style="background:linear-gradient(135deg,#fbbf24,#f97316);"><i class="fas fa-crown"></i></div>
                    <h3>Kingdom Coins</h3>
                    <p><?php echo ($current_lang === 'fr')
                        ? 'Monnaie des 14 mondes VR. Gagnez en jouant, dépensez sur le marché, encaissez via Solana.'
                        : 'In-game currency across 14 VR worlds. Earn from gameplay, spend on items, cash out via Solana.'; ?></p>
                    <div class="hp-finance-stats">
                        <div><strong style="color:#fbbf24;">13</strong><span><?php echo ($current_lang === 'fr') ? 'Mondes' : 'Worlds'; ?></span></div>
                        <div><strong style="color:#fbbf24;">♟️↔💰</strong><span><?php echo ($current_lang === 'fr') ? 'Paris' : 'Wagers'; ?></span></div>
                    </div>
                </div>
            </div>

            <div class="hp-center" data-aos="fade-up">
                <a href="/pay/account/crypto" class="hp-btn hp-btn-primary" style="background:linear-gradient(135deg,#9945FF,#14F195);">
                    <i class="fas fa-rocket"></i> <?php echo ($current_lang === 'fr') ? 'Dashboard Crypto' : 'Crypto Dashboard'; ?>
                </a>
                <a href="/pay/account/gsm-token" class="hp-btn hp-btn-outline" style="margin-left:.75rem;border-color:rgba(153,69,255,.4);color:#c084fc;">
                    <i class="fas fa-coins"></i> <?php echo ($current_lang === 'fr') ? 'Économie GSM' : 'GSM Token Economy'; ?>
                </a>
            </div>
        </div>
    </section>


    <!-- ════════════════════════════════════════════════════════════════
         SECTION 5: ALFRED AI — The Brain
         ════════════════════════════════════════════════════════════════ -->
    <section class="hp-alfred" id="alfred">
        <div class="container">
            <div class="hp-section-header" data-aos="fade-up">
                <div class="hp-section-label" style="background: linear-gradient(135deg, rgba(125,0,255,.2), rgba(0,212,255,.2)); border: 1px solid rgba(0,212,255,.4); color: var(--hp-cyan);">
                    <i class="fas fa-crown"></i> <?php echo ($current_lang === 'fr') ? 'Intelligence Artificielle' : 'Artificial Intelligence'; ?>
                </div>
                <h2><?php echo ($current_lang === 'fr')
                    ? 'Alfred AI — Le Cerveau<br>Derrière Tout'
                    : 'Alfred AI — The Brain<br>Behind Everything'; ?></h2>
                <p><?php echo ($current_lang === 'fr')
                    ? '100 agents spécialisés. 1 290+ outils. Dites-lui ce que vous voulez — il le fait.'
                    : '100 specialized agents. 1,220+ tools. Tell it what you want — it does it.'; ?></p>
            </div>

            <div class="hp-alfred-box" data-aos="fade-up">
                <!-- Demo Conversations -->
                <div class="hp-alfred-demo-grid">
                    <div class="hp-alfred-demo">
                        <div class="demo-icon">🛒</div>
                        <div class="demo-prompt">"Set up an online store selling organic coffee"</div>
                        <div class="demo-result">✓ Done. WooCommerce installed, 3 starter products, Stripe checkout enabled. Store is live and taking orders.</div>
                    </div>
                    <div class="hp-alfred-demo">
                        <div class="demo-icon">📊</div>
                        <div class="demo-prompt">"Run an SEO audit and fix everything"</div>
                        <div class="demo-result">✓ SEO score: 42 → 91. Fixed meta tags, added sitemap, optimized images, improved heading hierarchy.</div>
                    </div>
                    <div class="hp-alfred-demo">
                        <div class="demo-icon">🎨</div>
                        <div class="demo-prompt">"Design a landing page for my SaaS product"</div>
                        <div class="demo-result">✓ Deployed. Hero, features, pricing, testimonials. Dark theme with animated gradients. Mobile responsive.</div>
                    </div>
                </div>

                <!-- Capability Pills -->
                <div class="hp-alfred-pills">
                    <div class="hp-alfred-pill"><i class="fas fa-phone" style="color:#10b981;"></i> <?php echo ($current_lang === 'fr') ? 'Appeler' : 'Call by Phone'; ?></div>
                    <div class="hp-alfred-pill"><i class="fas fa-microphone" style="color:#c084fc;"></i> <?php echo L('alfred_pill_voice'); ?></div>
                    <div class="hp-alfred-pill"><i class="fab fa-whatsapp" style="color:#25d366;"></i> WhatsApp</div>
                    <div class="hp-alfred-pill"><i class="fab fa-discord" style="color:#5865f2;"></i> Discord</div>
                    <div class="hp-alfred-pill"><i class="fas fa-comment-dots" style="color:#3b82f6;"></i> Signal</div>
                    <div class="hp-alfred-pill"><i class="fas fa-image" style="color:#00d4ff;"></i> <?php echo ($current_lang === 'fr') ? 'Images IA' : 'AI Images'; ?></div>
                    <div class="hp-alfred-pill"><i class="fas fa-store" style="color:#fb923c;"></i> E-Commerce</div>
                    <div class="hp-alfred-pill"><i class="fas fa-magnifying-glass-chart" style="color:#22d3ee;"></i> SEO</div>
                    <div class="hp-alfred-pill"><i class="fas fa-gears" style="color:#a78bfa;"></i> DevOps</div>
                    <div class="hp-alfred-pill"><i class="fas fa-universal-access" style="color:#34d399;"></i> <?php echo ($current_lang === 'fr') ? 'Accessibilité' : 'Accessibility'; ?></div>
                    <div class="hp-alfred-pill"><i class="fas fa-shield-alt" style="color:#10b981;"></i> <?php echo ($current_lang === 'fr') ? 'Sécurité' : 'Security'; ?></div>
                    <div class="hp-alfred-pill"><i class="fas fa-credit-card" style="color:#fb923c;"></i> <?php echo ($current_lang === 'fr') ? 'Facturation' : 'Billing'; ?></div>
                </div>

                <!-- CTA Buttons -->
                <div style="display:flex; gap:1rem; justify-content:center; flex-wrap:wrap;">
                    <a href="/alfred.php" class="hp-btn hp-btn-primary"><i class="fas fa-robot"></i> <?php echo ($current_lang === 'fr') ? 'Découvrir Alfred' : 'Explore Alfred AI'; ?></a>
                    <a href="/voice.php" class="hp-btn hp-btn-outline" style="border-color:rgba(192,132,252,.4); color:#c084fc;"><i class="fas fa-microphone"></i> <?php echo ($current_lang === 'fr') ? 'Essayer la voix' : 'Try Voice AI'; ?></a>
                </div>
            </div>
        </div>
    </section>


    <!-- ════════════════════════════════════════════════════════════════
         SECTION 5b: VOICE AI PRODUCTS
         ════════════════════════════════════════════════════════════════ -->
    <section class="hp-voice">
        <div class="container">
            <div class="hp-section-header" data-aos="fade-up">
                <div class="hp-section-label" style="background:rgba(16,185,129,.12); border:1px solid rgba(16,185,129,.25); color:var(--hp-green);">
                    <i class="fas fa-phone-volume"></i> <?php echo ($current_lang === 'fr') ? 'Produits Vocaux' : 'Voice AI Products'; ?>
                </div>
                <h2><?php echo ($current_lang === 'fr') ? 'Agents Vocaux IA — À la Carte' : 'AI Voice Agents — À La Carte'; ?></h2>
                <p><?php echo ($current_lang === 'fr')
                    ? '29 produits vocaux IA. Numéros locaux dès 3$/mois. Agents téléphoniques 24/7. Clonage vocal. Campagnes d\'appels.'
                    : '29 AI voice products. Local numbers from $3/mo. 24/7 phone agents. Voice cloning. Call campaigns.'; ?></p>
            </div>

            <div class="hp-voice-grid" data-aos="fade-up">
                <div class="hp-voice-card">
                    <span class="vc-popular">POPULAR</span>
                    <div class="vc-icon" style="background:linear-gradient(135deg,#10b981,#34d399);"><i class="fas fa-phone"></i></div>
                    <h4><?php echo ($current_lang === 'fr') ? 'Agent Téléphonique' : 'AI Phone Agent'; ?></h4>
                    <div class="vc-price" style="color:var(--hp-green);">$25<span style="font-size:.7rem;font-weight:400;color:var(--hp-muted);">/mo</span></div>
                    <div class="vc-desc"><?php echo ($current_lang === 'fr') ? 'Réponse automatisée 24/7, transfert intelligent' : '24/7 automated answering, smart call routing'; ?></div>
                    <div class="vc-tags"><span>24/7</span><span><?php echo ($current_lang === 'fr') ? '30+ langues' : '30+ langs'; ?></span></div>
                </div>
                <div class="hp-voice-card">
                    <div class="vc-icon" style="background:linear-gradient(135deg,#00a8ff,#00d4ff);"><i class="fas fa-sim-card"></i></div>
                    <h4><?php echo ($current_lang === 'fr') ? 'Numéro Local' : 'Local Number'; ?></h4>
                    <div class="vc-price" style="color:var(--hp-cyan);">$3<span style="font-size:.7rem;font-weight:400;color:var(--hp-muted);">/mo</span></div>
                    <div class="vc-desc"><?php echo ($current_lang === 'fr') ? 'Numéro dédié local, SMS et appels' : 'Dedicated local number, SMS & calls'; ?></div>
                    <div class="vc-tags"><span>SMS</span><span><?php echo ($current_lang === 'fr') ? 'Appels' : 'Calls'; ?></span></div>
                </div>
                <div class="hp-voice-card">
                    <div class="vc-icon" style="background:linear-gradient(135deg,#8b5cf6,#a78bfa);"><i class="fas fa-phone-flip"></i></div>
                    <h4><?php echo ($current_lang === 'fr') ? 'Sans Frais' : 'Toll-Free'; ?></h4>
                    <div class="vc-price" style="color:var(--hp-violet);">$5<span style="font-size:.7rem;font-weight:400;color:var(--hp-muted);">/mo</span></div>
                    <div class="vc-desc"><?php echo ($current_lang === 'fr') ? 'Numéro 1-800 professionnel' : 'Professional 1-800 number'; ?></div>
                    <div class="vc-tags"><span>1-800</span><span>Pro</span></div>
                </div>
                <div class="hp-voice-card">
                    <div class="vc-icon" style="background:linear-gradient(135deg,#f97316,#fb923c);"><i class="fas fa-microphone-lines"></i></div>
                    <h4><?php echo ($current_lang === 'fr') ? 'Clonage Vocal' : 'Voice Clone'; ?></h4>
                    <div class="vc-price" style="color:var(--hp-coral);">$10<span style="font-size:.7rem;font-weight:400;color:var(--hp-muted);">/mo</span></div>
                    <div class="vc-desc"><?php echo ($current_lang === 'fr') ? 'Votre voix IA personnelle, ultra-réaliste' : 'Your personal AI voice, ultra-realistic'; ?></div>
                    <div class="vc-tags"><span>AI</span><span><?php echo ($current_lang === 'fr') ? 'Réaliste' : 'Realistic'; ?></span></div>
                </div>
                <div class="hp-voice-card">
                    <div class="vc-icon" style="background:linear-gradient(135deg,#3b82f6,#60a5fa);"><i class="fas fa-building"></i></div>
                    <h4><?php echo ($current_lang === 'fr') ? 'Réceptionniste' : 'AI Receptionist'; ?></h4>
                    <div class="vc-price" style="color:var(--hp-blue);">$15<span style="font-size:.7rem;font-weight:400;color:var(--hp-muted);">/mo</span></div>
                    <div class="vc-desc"><?php echo ($current_lang === 'fr') ? 'Accueil téléphonique IA, prise de RDV' : 'AI front desk, appointment booking'; ?></div>
                    <div class="vc-tags"><span><?php echo ($current_lang === 'fr') ? 'Accueil' : 'Front Desk'; ?></span><span>Booking</span></div>
                </div>
                <div class="hp-voice-card">
                    <div class="vc-icon" style="background:linear-gradient(135deg,#7d00ff,#c084fc);"><i class="fas fa-chart-line"></i></div>
                    <h4><?php echo ($current_lang === 'fr') ? 'Campagne d\'Appels' : 'Call Campaign'; ?></h4>
                    <div class="vc-price" style="color:var(--hp-purple);">$29<span style="font-size:.7rem;font-weight:400;color:var(--hp-muted);">/mo</span></div>
                    <div class="vc-desc"><?php echo ($current_lang === 'fr') ? 'Appels sortants automatisés, reporting' : 'Automated outbound calls, analytics'; ?></div>
                    <div class="vc-tags"><span><?php echo ($current_lang === 'fr') ? 'Sortants' : 'Outbound'; ?></span><span><?php echo ($current_lang === 'fr') ? 'Stats' : 'Analytics'; ?></span></div>
                </div>
            </div>

            <div class="hp-center" data-aos="fade-up">
                <a href="/voice-products.php" class="hp-btn hp-btn-primary" style="background:linear-gradient(135deg,#10b981,#34d399);">
                    <i class="fas fa-phone-volume"></i> <?php echo ($current_lang === 'fr') ? 'Voir les 29 Produits' : 'See All 29 Products'; ?>
                </a>
                <a href="/voice-cloning.php" class="hp-btn hp-btn-outline" style="margin-left:.75rem;border-color:rgba(16,185,129,.3);color:var(--hp-green);">
                    <i class="fas fa-microphone"></i> <?php echo ($current_lang === 'fr') ? 'Cloner ma Voix' : 'Clone My Voice'; ?>
                </a>
            </div>
        </div>
    </section>



    <!-- ════════════════════════════════════════════════════════════════
         SECTION 6: HOW IT WORKS
         ════════════════════════════════════════════════════════════════ -->
    <section class="hp-how">
        <div class="container">
            <div class="hp-section-header" data-aos="fade-up">
                <div class="hp-section-label" style="background:rgba(16,185,129,.12); border:1px solid rgba(16,185,129,.25); color:var(--hp-green);">
                    <i class="fas fa-magic"></i> <?php echo ($current_lang === 'fr') ? 'Comment ça marche' : 'How It Works'; ?>
                </div>
                <h2><?php echo ($current_lang === 'fr') ? 'De l\'Idée au Lancement en 60 Secondes' : 'From Idea to Launch in 60 Seconds'; ?></h2>
            </div>

            <div class="hp-steps">
                <div class="hp-step" data-aos="fade-up">
                    <div class="hp-step-num">1</div>
                    <div class="hp-step-icon"><i class="fas fa-lightbulb" style="color:#fbbf24;"></i></div>
                    <h3><?php echo L('step1_title'); ?></h3>
                    <p><?php echo L('step1_text'); ?></p>
                </div>
                <div class="hp-step-connector"><i class="fas fa-arrow-right"></i></div>
                <div class="hp-step" data-aos="fade-up" data-aos-delay="100">
                    <div class="hp-step-num">2</div>
                    <div class="hp-step-icon"><i class="fas fa-wand-magic-sparkles" style="color:#c084fc;"></i></div>
                    <h3><?php echo L('step2_title'); ?></h3>
                    <p><?php echo L('step2_text'); ?></p>
                </div>
                <div class="hp-step-connector"><i class="fas fa-arrow-right"></i></div>
                <div class="hp-step" data-aos="fade-up" data-aos-delay="200">
                    <div class="hp-step-num">3</div>
                    <div class="hp-step-icon"><i class="fas fa-rocket" style="color:#00d4ff;"></i></div>
                    <h3><?php echo L('step3_title'); ?></h3>
                    <p><?php echo L('step3_text'); ?></p>
                </div>
            </div>

            <div class="hp-center" data-aos="fade-up">
                <a href="<?php echo htmlspecialchars(billing_link('store/ai-domain-hosting-connected-with-ai-editor')); ?>" class="hp-btn hp-btn-primary">
                    <i class="fas fa-rocket"></i> <?php echo L('how_cta'); ?>
                </a>
                <p class="hp-mt-1" style="font-size:.88rem; color:var(--hp-muted);">
                    <a href="/middleware/dashboard" style="color:var(--hp-cyan);"><?php echo L('how_try_free'); ?></a>
                </p>
            </div>
        </div>
    </section>


    <!-- ════════════════════════════════════════════════════════════════
         SECTION 7: GAMES — Play Now
         ════════════════════════════════════════════════════════════════ -->
    <section class="hp-games" id="games">
        <div class="container">
            <div class="hp-section-header" data-aos="fade-up">
                <div class="hp-section-label" style="background: linear-gradient(135deg, rgba(125,0,255,.2), rgba(0,212,255,.15)); border: 1px solid rgba(125,0,255,.35); color: #c4b5fd;">
                    <i class="fas fa-gamepad"></i> <?php echo ($current_lang === 'fr') ? 'Jeux & Mondes VR' : 'Games & VR Worlds'; ?>
                </div>
                <h2><?php echo ($current_lang === 'fr') ? 'Jouez Instantanément. Pas de Téléchargement.' : 'Play Instantly. No Downloads.'; ?></h2>
                <p><?php echo ($current_lang === 'fr')
                    ? 'Échecs IA, pool 3D, dames — directement dans votre navigateur avec WebXR pour la VR.'
                    : 'AI Chess Arena, 3D Pool, Checkers — directly in your browser with WebXR for VR.'; ?></p>
            </div>

            <div class="hp-game-grid" data-aos="fade-up">
                <!-- Chess Masters -->
                <a href="/vr/chess-masters/" class="hp-game-card">
                    <div class="hp-game-thumb" style="background:linear-gradient(135deg,#0D0806,#1A1008);">
                        <div class="g-bg" style="background:radial-gradient(circle at 40% 60%,rgba(184,134,11,.3),transparent 70%);">
                        </div>
                        <span class="g-icon">♚</span>
                        <span class="hp-game-card-badge" style="background:rgba(218,165,32,.2);color:#DAA520;border:1px solid rgba(218,165,32,.3);">✨ New</span>
                    </div>
                    <div class="hp-game-body">
                        <h4>Chess Masters</h4>
                        <p><?php echo ($current_lang === 'fr')
                            ? 'Club d\'échecs photoréaliste — cheminée, audio spatial, 20 personnalités IA avec commentaires en direct.'
                            : 'Photorealistic chess club — fireplace, spatial audio, 20 AI personalities with live commentary.'; ?></p>
                        <span class="hp-game-play"><i class="fas fa-door-open"></i> <?php echo ($current_lang === 'fr') ? 'Entrer' : 'Enter Club'; ?></span>
                    </div>
                </a>
                <!-- Chess Arena -->
                <a href="/vr/chess/" class="hp-game-card">
                    <div class="hp-game-thumb" style="background:linear-gradient(135deg,#0a0a2e,#1a0a3e);">
                        <div class="g-bg" style="background:radial-gradient(circle at 40% 40%,rgba(125,0,255,.3),transparent 70%);"></div>
                        <span class="g-icon">♟️</span>
                        <span class="hp-game-card-badge" style="background:rgba(239,68,68,.2);color:#f87171;border:1px solid rgba(239,68,68,.3);">🔥 #1</span>
                    </div>
                    <div class="hp-game-body">
                        <h4>AI Chess Arena</h4>
                        <p><?php echo ($current_lang === 'fr')
                            ? '8 agents IA, 6 thèmes, commandes vocales, PvP multijoueur et support VR complet.'
                            : '8 AI agents, 6 themes, voice commands, PvP multiplayer, and full VR support.'; ?></p>
                        <span class="hp-game-play"><i class="fas fa-play"></i> <?php echo ($current_lang === 'fr') ? 'Jouer' : 'Play Now'; ?></span>
                    </div>
                </a>
                <!-- Checkers -->
                <a href="/vr/checkers/" class="hp-game-card">
                    <div class="hp-game-thumb" style="background:linear-gradient(135deg,#1a0a05,#2a1205);">
                        <div class="g-bg" style="background:radial-gradient(circle at 60% 50%,rgba(251,146,60,.25),transparent 70%);"></div>
                        <span class="g-icon">🔴</span>
                        <span class="hp-game-card-badge" style="background:rgba(16,185,129,.2);color:#10b981;border:1px solid rgba(16,185,129,.3);">New</span>
                    </div>
                    <div class="hp-game-body">
                        <h4>3D Checkers</h4>
                        <p><?php echo ($current_lang === 'fr')
                            ? 'Dames classiques en 3D avec 4 niveaux de difficulté IA, PvP multijoueur et plusieurs thèmes.'
                            : 'Classic checkers in 3D with 4 AI difficulty levels, PvP multiplayer, and multiple board themes.'; ?></p>
                        <span class="hp-game-play"><i class="fas fa-play"></i> <?php echo ($current_lang === 'fr') ? 'Jouer' : 'Play Now'; ?></span>
                    </div>
                </a>
                <!-- Pool -->
                <a href="/vr/pool/" class="hp-game-card">
                    <div class="hp-game-thumb" style="background:linear-gradient(135deg,#050a1a,#0a1a2a);">
                        <div class="g-bg" style="background:radial-gradient(circle at 50% 50%,rgba(0,212,255,.2),transparent 70%);"></div>
                        <span class="g-icon">🎱</span>
                        <span class="hp-game-card-badge" style="background:rgba(16,185,129,.2);color:#10b981;border:1px solid rgba(16,185,129,.3);">New</span>
                    </div>
                    <div class="hp-game-body">
                        <h4>3D Pool</h4>
                        <p><?php echo ($current_lang === 'fr')
                            ? 'Pool 8-ball réaliste avec physique complète. Jouez contre l\'IA ou défiez un ami en ligne.'
                            : 'Realistic 8-ball pool with full physics. Play vs AI or challenge a friend online.'; ?></p>
                        <span class="hp-game-play"><i class="fas fa-play"></i> <?php echo ($current_lang === 'fr') ? 'Jouer' : 'Play Now'; ?></span>
                    </div>
                </a>
            </div>

            <div class="hp-center" data-aos="fade-up">
                <a href="/vr/experiences/" class="hp-btn hp-btn-primary" style="background:linear-gradient(135deg,#B8860B,#DAA520);border-color:#B8860B;"><i class="fas fa-gem"></i> <?php echo ($current_lang === 'fr') ? 'VR Experiences' : 'VR Experiences'; ?></a>
                <a href="/vr/hub/" class="hp-btn hp-btn-primary" style="margin-left:.75rem;"><i class="fas fa-vr-cardboard"></i> <?php echo ($current_lang === 'fr') ? 'Explorer les 14 mondes' : 'Explore All 14 Worlds'; ?></a>
                <a href="/sdks/game-engine/" class="hp-btn hp-btn-outline" style="margin-left:.75rem;"><i class="fas fa-code"></i> <?php echo ($current_lang === 'fr') ? 'SDK de jeux' : 'Game Engine SDK'; ?></a>
            </div>
        </div>
    </section>


    <!-- ════════════════════════════════════════════════════════════════
         SHOWCASE CAROUSEL 2: VR WORLDS + 34 INDUSTRIES
         ════════════════════════════════════════════════════════════════ -->
    <section class="hp-carousel-section">
        <div class="container">
            <div class="hp-section-header" data-aos="fade-up">
                <div class="hp-section-label" style="background:linear-gradient(135deg,rgba(184,134,11,.2),rgba(125,0,255,.15)); border:1px solid rgba(184,134,11,.3); color:#DAA520;">
                    <i class="fas fa-globe-americas"></i> <?php echo ($current_lang === 'fr') ? '18 Mondes VR · 34 Industries' : '18 VR Worlds · 34 Industries'; ?>
                </div>
                <h2><?php echo ($current_lang === 'fr')
                    ? 'Des Mondes Virtuels aux<br>Solutions Réelles'
                    : 'From Virtual Worlds to<br>Real-World Solutions'; ?></h2>
            </div>
        </div>

        <!-- Row 1: VR Worlds -->
        <div class="hp-carousel-wrap" data-aos="fade-up">
            <div class="hp-carousel-track slow">
                <?php for ($i = 0; $i < 2; $i++): ?>
                <a href="/vr/kingdom/" class="hp-ccard-mini" style="--cc-accent:rgba(184,134,11,.4);"><div class="icon">⚔️</div><h4>Medieval Kingdom</h4><p>Castles, dragons, quests</p></a>
                <a href="/vr/pool/" class="hp-ccard-mini" style="--cc-accent:rgba(0,212,255,.4);"><div class="icon">🎱</div><h4>VR Pool</h4><p>Billiards & chill</p></a>
                <a href="/vr/racing/" class="hp-ccard-mini" style="--cc-accent:rgba(139,92,246,.4);"><div class="icon">🏎️</div><h4>VR Racing</h4><p>High-speed tracks</p></a>
                <a href="/vr/chess/" class="hp-ccard-mini" style="--cc-accent:rgba(244,114,182,.4);"><div class="icon">♟️</div><h4>VR Chess</h4><p>AI opponents, coaching</p></a>
                <a href="/vr/checkers/" class="hp-ccard-mini" style="--cc-accent:rgba(251,191,36,.4);"><div class="icon">🔴</div><h4>VR Checkers</h4><p>Classic board game</p></a>
                <a href="/vr/concert/" class="hp-ccard-mini" style="--cc-accent:rgba(251,146,60,.4);"><div class="icon">🎤</div><h4>VR Concert</h4><p>Live music events</p></a>
                <a href="/vr/dj-studio/" class="hp-ccard-mini" style="--cc-accent:rgba(239,68,68,.4);"><div class="icon">🎧</div><h4>DJ Studio</h4><p>Mix & produce</p></a>
                <a href="/vr/gallery/" class="hp-ccard-mini" style="--cc-accent:rgba(16,185,129,.4);"><div class="icon">🖼️</div><h4>VR Gallery</h4><p>Art exhibitions</p></a>
                <a href="/vr/lounge/" class="hp-ccard-mini" style="--cc-accent:rgba(251,146,60,.4);"><div class="icon">🛋️</div><h4>VR Lounge</h4><p>Social hangout</p></a>
                <a href="/vr/office/" class="hp-ccard-mini" style="--cc-accent:rgba(239,68,68,.4);"><div class="icon">💼</div><h4>VR Office</h4><p>Virtual workspace</p></a>
                <a href="/vr/sanctuary/" class="hp-ccard-mini" style="--cc-accent:rgba(59,130,246,.4);"><div class="icon">🧘</div><h4>Sanctuary</h4><p>Peaceful meditation</p></a>
                <a href="/vr/speed-dating/" class="hp-ccard-mini" style="--cc-accent:rgba(184,134,11,.4);"><div class="icon">💕</div><h4>Speed Dating</h4><p>Meet new people</p></a>
                <a href="/vr/circuit-lab/" class="hp-ccard-mini" style="--cc-accent:rgba(16,185,129,.4);"><div class="icon">🔌</div><h4>Circuit Lab</h4><p>Electronics sim</p></a>
                <a href="/vr/chess-masters/" class="hp-ccard-mini" style="--cc-accent:rgba(251,146,60,.4);"><div class="icon">👑</div><h4>Chess Masters</h4><p>Grandmaster arena</p></a>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Row 2: Industry Solutions -->
        <div class="hp-carousel-wrap" style="margin-top:16px" data-aos="fade-up" data-aos-delay="100">
            <div class="hp-carousel-track reverse">
                <?php for ($i = 0; $i < 2; $i++): ?>
                <a href="/use-cases/healthcare" class="hp-ccard-mini" style="--cc-accent:rgba(16,185,129,.3);"><div class="icon">🏥</div><h4>Healthcare</h4><p>HIPAA, telehealth</p></a>
                <a href="/use-cases/realestate" class="hp-ccard-mini" style="--cc-accent:rgba(59,130,246,.3);"><div class="icon">🏠</div><h4>Real Estate</h4><p>Listings, virtual tours</p></a>
                <a href="/use-cases/ecommerce" class="hp-ccard-mini" style="--cc-accent:rgba(251,146,60,.3);"><div class="icon">🛒</div><h4>E-Commerce</h4><p>AI-powered stores</p></a>
                <a href="/use-cases/education" class="hp-ccard-mini" style="--cc-accent:rgba(139,92,246,.3);"><div class="icon">📚</div><h4>Education</h4><p>LMS, AI tutoring</p></a>
                <a href="/use-cases/accounting" class="hp-ccard-mini" style="--cc-accent:rgba(16,185,129,.3);"><div class="icon">💹</div><h4>Finance</h4><p>Accounting, fintech</p></a>
                <a href="/use-cases/restaurants" class="hp-ccard-mini" style="--cc-accent:rgba(251,146,60,.3);"><div class="icon">🍽️</div><h4>Restaurants</h4><p>Online ordering, menus</p></a>
                <a href="/use-cases/legal" class="hp-ccard-mini" style="--cc-accent:rgba(139,92,246,.3);"><div class="icon">⚖️</div><h4>Legal</h4><p>Case mgmt, contracts</p></a>
                <a href="/use-cases/construction" class="hp-ccard-mini" style="--cc-accent:rgba(251,191,36,.3);"><div class="icon">🏗️</div><h4>Construction</h4><p>Project tracking</p></a>
                <a href="/use-cases/automotive" class="hp-ccard-mini" style="--cc-accent:rgba(239,68,68,.3);"><div class="icon">🚗</div><h4>Automotive</h4><p>Dealership mgmt</p></a>
                <a href="/use-cases/fitness" class="hp-ccard-mini" style="--cc-accent:rgba(244,114,182,.3);"><div class="icon">💪</div><h4>Fitness</h4><p>Gym & wellness</p></a>
                <a href="/use-cases/nonprofits" class="hp-ccard-mini" style="--cc-accent:rgba(59,130,246,.3);"><div class="icon">💚</div><h4>Nonprofit</h4><p>Donations, campaigns</p></a>
                <a href="/use-cases/travel" class="hp-ccard-mini" style="--cc-accent:rgba(0,212,255,.3);"><div class="icon">✈️</div><h4>Travel</h4><p>Booking, itineraries</p></a>
                <a href="/use-cases/media" class="hp-ccard-mini" style="--cc-accent:rgba(125,0,255,.3);"><div class="icon">🎵</div><h4>Media</h4><p>Artists, labels, events</p></a>
                <a href="/use-cases/agriculture" class="hp-ccard-mini" style="--cc-accent:rgba(16,185,129,.3);"><div class="icon">🌾</div><h4>Agriculture</h4><p>Farm management</p></a>
                <a href="/use-cases/recruitment" class="hp-ccard-mini" style="--cc-accent:rgba(59,130,246,.3);"><div class="icon">⚽</div><h4>Recruitment</h4><p>Talent, hiring</p></a>
                <a href="/use-cases/manufacturing" class="hp-ccard-mini" style="--cc-accent:rgba(251,146,60,.3);"><div class="icon">🏭</div><h4>Manufacturing</h4><p>Supply chain, IoT</p></a>
                <a href="/use-cases/insurance" class="hp-ccard-mini" style="--cc-accent:rgba(139,92,246,.3);"><div class="icon">🛡️</div><h4>Insurance</h4><p>Claims, underwriting</p></a>
                <?php endfor; ?>
            </div>
        </div>

        <div class="container">
            <div class="hp-center hp-mt-2" data-aos="fade-up">
                <a href="/use-cases/" class="hp-btn hp-btn-outline">
                    <i class="fas fa-th-large"></i> <?php echo ($current_lang === 'fr') ? 'Voir les 34 Industries' : 'See All 34 Industries'; ?>
                </a>
            </div>
        </div>
    </section>


    <!-- ════════════════════════════════════════════════════════════════
         SECTION 8: PRICING
         ════════════════════════════════════════════════════════════════ -->
    <section class="hp-pricing" id="pricing">
        <div class="container">
            <div class="hp-section-header" data-aos="fade-up">
                <div class="hp-section-label" style="background:rgba(16,185,129,.12); border:1px solid rgba(16,185,129,.25); color:var(--hp-green);">
                    <i class="fas fa-tags"></i> <?php echo L('pricing_label'); ?>
                </div>
                <h2><?php echo L('pricing_title'); ?></h2>
                <p><?php echo L('pricing_subtitle'); ?></p>
            </div>

            <div class="hp-pricing-grid" data-aos="fade-up">
                <!-- Builder -->
                <div class="hp-plan">
                    <h3>Builder</h3>
                    <div class="plan-tag"><?php echo ($current_lang === 'fr') ? 'Projets personnels' : 'Personal projects'; ?></div>
                    <div class="plan-price"><span class="dollar">$</span><span class="amount">15</span><span class="period">/mo</span></div>
                    <div class="plan-tokens">300K AI tokens/mo</div>
                    <ul class="hp-plan-features">
                        <li><i class="fas fa-check"></i> GoCodeMe Web IDE</li>
                        <li><i class="fas fa-check"></i> <strong>Alfred AI Assistant</strong></li>
                        <li><i class="fas fa-check"></i> 1,220+ AI Tools</li>
                        <li><i class="fas fa-check"></i> Voice commands</li>
                        <li><i class="fas fa-check"></i> Free SSL + domain mgmt</li>
                    </ul>
                    <a href="<?php echo htmlspecialchars(billing_link('cart.php?a=add&pid=18')); ?>" class="hp-plan-cta outline"><?php echo L('get_started'); ?></a>
                </div>

                <!-- Professional -->
                <div class="hp-plan featured">
                    <span class="popular"><?php echo L('most_popular'); ?></span>
                    <h3>Professional</h3>
                    <div class="plan-tag"><?php echo ($current_lang === 'fr') ? 'Freelancers & pros' : 'Freelancers & pros'; ?></div>
                    <div class="plan-price"><span class="dollar">$</span><span class="amount">29</span><span class="period">/mo</span></div>
                    <div class="plan-tokens">600K AI tokens/mo</div>
                    <ul class="hp-plan-features">
                        <li><i class="fas fa-check"></i> Everything in Builder</li>
                        <li><i class="fas fa-check"></i> Priority AI processing</li>
                        <li><i class="fas fa-check"></i> Full Git workflow + PRs</li>
                        <li><i class="fas fa-check"></i> Database management</li>
                        <li><i class="fas fa-check"></i> Staging environments</li>
                        <li><i class="fas fa-check"></i> SSH/SFTP access</li>
                    </ul>
                    <a href="<?php echo htmlspecialchars(billing_link('cart.php?a=add&pid=19')); ?>" class="hp-plan-cta primary"><?php echo L('get_started'); ?></a>
                </div>

                <!-- Studio -->
                <div class="hp-plan">
                    <h3>Studio</h3>
                    <div class="plan-tag"><?php echo ($current_lang === 'fr') ? 'Startups & studios' : 'Startups & studios'; ?></div>
                    <div class="plan-price"><span class="dollar">$</span><span class="amount">59</span><span class="period">/mo</span></div>
                    <div class="plan-tokens">1.5M AI tokens/mo</div>
                    <ul class="hp-plan-features">
                        <li><i class="fas fa-check"></i> Everything in Professional</li>
                        <li><i class="fas fa-check"></i> <strong>Premium Model access</strong></li>
                        <li><i class="fas fa-check"></i> 3 parallel AI sessions</li>
                        <li><i class="fas fa-check"></i> Team sharing (5 users)</li>
                        <li><i class="fas fa-check"></i> Docker orchestration</li>
                    </ul>
                    <a href="<?php echo htmlspecialchars(billing_link('cart.php?a=add&pid=20')); ?>" class="hp-plan-cta outline"><?php echo L('get_started'); ?></a>
                </div>

                <!-- Business -->
                <div class="hp-plan">
                    <h3>Business</h3>
                    <div class="plan-tag"><?php echo ($current_lang === 'fr') ? 'Agences & entreprises' : 'Agencies & enterprises'; ?></div>
                    <div class="plan-price"><span class="dollar">$</span><span class="amount">99</span><span class="period">/mo</span></div>
                    <div class="plan-tokens">3M AI tokens/mo</div>
                    <ul class="hp-plan-features">
                        <li><i class="fas fa-check"></i> Everything in Studio</li>
                        <li><i class="fas fa-check"></i> Unlimited Premium Model</li>
                        <li><i class="fas fa-check"></i> 10 parallel AI sessions</li>
                        <li><i class="fas fa-check"></i> 25 collaborators</li>
                        <li><i class="fas fa-check"></i> SSO/SAML + RBAC</li>
                    </ul>
                    <a href="<?php echo htmlspecialchars(billing_link('cart.php?a=add&pid=21')); ?>" class="hp-plan-cta outline"><?php echo L('get_started'); ?></a>
                </div>
            </div>

            <div class="hp-center" data-aos="fade-up">
                <p style="font-size:.88rem; color:var(--hp-muted);">
                    <?php echo ($current_lang === 'fr') ? 'Besoin de plus ?' : 'Need more?'; ?>
                    <a href="<?php echo htmlspecialchars(billing_link('cart.php?a=add&pid=22')); ?>" style="color:var(--hp-cyan);"><?php echo ($current_lang === 'fr') ? 'Contactez-nous pour l\'Enterprise' : 'Contact us for Enterprise'; ?></a>
                    &middot; Code <strong style="color:var(--hp-green);">LAUNCH50</strong> <?php echo ($current_lang === 'fr') ? 'pour 50% de rabais la première année' : 'for 50% off your first year'; ?>
                </p>
            </div>
        </div>
    </section>


    <!-- ════════════════════════════════════════════════════════════════
         SECTION 8b: COMPARISON — Why GoSiteMe?
         ════════════════════════════════════════════════════════════════ -->
    <section class="hp-compare" id="compare">
        <div class="container">
            <div class="hp-section-header" data-aos="fade-up">
                <div class="hp-section-label" style="background:rgba(125,0,255,.12); border:1px solid rgba(125,0,255,.25); color:#c084fc;">
                    <i class="fas fa-trophy"></i> <?php echo ($current_lang === 'fr') ? 'Comparaison' : 'Why GoSiteMe'; ?>
                </div>
                <h2><?php echo ($current_lang === 'fr')
                    ? 'Tout Ce Qu\'ils Font. Plus 1 200 Outils de Plus.'
                    : 'Everything They Do. Plus 1,200 More Tools.'; ?></h2>
            </div>

            <div class="hp-compare-wrap" data-aos="fade-up">
                <table>
                    <thead>
                        <tr>
                            <th><?php echo ($current_lang === 'fr') ? 'Fonctionnalité' : 'Feature'; ?></th>
                            <th class="col-us">GoSiteMe</th>
                            <th>WordPress</th>
                            <th>Wix</th>
                            <th>ChatGPT</th>
                            <th>Cursor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php echo ($current_lang === 'fr') ? 'Outils IA' : 'AI Tools'; ?></td>
                            <td class="col-us c-val" style="color:var(--hp-green);">1,220+</td>
                            <td class="c-val">0</td>
                            <td class="c-val">~50</td>
                            <td class="c-val">0</td>
                            <td class="c-val">~20</td>
                        </tr>
                        <tr>
                            <td><?php echo ($current_lang === 'fr') ? 'Agents Vocaux' : 'Voice AI Agents'; ?></td>
                            <td class="col-us"><span class="c-yes"><i class="fas fa-check"></i></span> 29</td>
                            <td><span class="c-no"><i class="fas fa-times"></i></span></td>
                            <td><span class="c-no"><i class="fas fa-times"></i></span></td>
                            <td class="c-val"><?php echo ($current_lang === 'fr') ? 'Voix seule' : 'Voice only'; ?></td>
                            <td><span class="c-no"><i class="fas fa-times"></i></span></td>
                        </tr>
                        <tr>
                            <td><?php echo ($current_lang === 'fr') ? 'Hébergement inclus' : 'Hosting Included'; ?></td>
                            <td class="col-us"><span class="c-yes"><i class="fas fa-check"></i></span></td>
                            <td><span class="c-no"><i class="fas fa-times"></i></span></td>
                            <td><span class="c-yes"><i class="fas fa-check"></i></span></td>
                            <td><span class="c-no"><i class="fas fa-times"></i></span></td>
                            <td><span class="c-no"><i class="fas fa-times"></i></span></td>
                        </tr>
                        <tr>
                            <td><?php echo ($current_lang === 'fr') ? 'Chiffrement E2E' : 'E2E Encryption'; ?></td>
                            <td class="col-us c-val" style="color:var(--hp-green);">Kyber-768</td>
                            <td><span class="c-no"><i class="fas fa-times"></i></span></td>
                            <td><span class="c-no"><i class="fas fa-times"></i></span></td>
                            <td><span class="c-no"><i class="fas fa-times"></i></span></td>
                            <td><span class="c-no"><i class="fas fa-times"></i></span></td>
                        </tr>
                        <tr>
                            <td><?php echo ($current_lang === 'fr') ? 'Mondes VR / Jeux' : 'VR Worlds / Games'; ?></td>
                            <td class="col-us c-val" style="color:var(--hp-green);">13</td>
                            <td><span class="c-no"><i class="fas fa-times"></i></span></td>
                            <td><span class="c-no"><i class="fas fa-times"></i></span></td>
                            <td><span class="c-no"><i class="fas fa-times"></i></span></td>
                            <td><span class="c-no"><i class="fas fa-times"></i></span></td>
                        </tr>
                        <tr>
                            <td><?php echo ($current_lang === 'fr') ? 'Réseau Social' : 'Social Network'; ?></td>
                            <td class="col-us"><span class="c-yes"><i class="fas fa-check"></i></span> Pulse</td>
                            <td><span class="c-no"><i class="fas fa-times"></i></span></td>
                            <td><span class="c-no"><i class="fas fa-times"></i></span></td>
                            <td><span class="c-no"><i class="fas fa-times"></i></span></td>
                            <td><span class="c-no"><i class="fas fa-times"></i></span></td>
                        </tr>
                        <tr>
                            <td>Crypto / DeFi</td>
                            <td class="col-us"><span class="c-yes"><i class="fas fa-check"></i></span> Solana</td>
                            <td><span class="c-no"><i class="fas fa-times"></i></span></td>
                            <td><span class="c-no"><i class="fas fa-times"></i></span></td>
                            <td><span class="c-no"><i class="fas fa-times"></i></span></td>
                            <td><span class="c-no"><i class="fas fa-times"></i></span></td>
                        </tr>
                        <tr>
                            <td><?php echo ($current_lang === 'fr') ? 'Prix de départ' : 'Starting Price'; ?></td>
                            <td class="col-us c-val" style="color:var(--hp-green);">$15/mo</td>
                            <td class="c-val">Free*</td>
                            <td class="c-val">$17/mo</td>
                            <td class="c-val">$20/mo</td>
                            <td class="c-val">$20/mo</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="hp-savings" data-aos="fade-up">
                <div class="calc">
                    <s>ChatGPT ($20)</s> + <s>Cursor ($20)</s> + <s><?php echo ($current_lang === 'fr') ? 'Hébergement' : 'Hosting'; ?> ($15)</s> = <strong style="color:var(--hp-red);">$55/<?php echo ($current_lang === 'fr') ? 'mois' : 'mo'; ?></strong>
                </div>
                <div class="calc" style="margin-top:.5rem;">
                    GoSiteMe = <strong style="color:var(--hp-green);">$15/<?php echo ($current_lang === 'fr') ? 'mois' : 'mo'; ?></strong> — <?php echo ($current_lang === 'fr') ? 'tout inclus' : 'everything included'; ?>
                </div>
                <div class="total"><?php echo ($current_lang === 'fr') ? 'Économisez 480$/an' : 'Save $480/year'; ?></div>
            </div>

            <div class="hp-center hp-mt-2" data-aos="fade-up">
                <a href="/compare.php" class="hp-btn hp-btn-outline">
                    <i class="fas fa-chart-bar"></i> <?php echo ($current_lang === 'fr') ? 'Comparaison complète' : 'Full Comparison'; ?>
                </a>
            </div>
        </div>
    </section>



    <!-- ════════════════════════════════════════════════════════════════
         SECTION 9: TESTIMONIALS & SOCIAL PROOF
         ════════════════════════════════════════════════════════════════ -->
    <section class="hp-social-proof">
        <div class="container">
            <div class="hp-section-header" data-aos="fade-up">
                <div class="hp-section-label" style="background:rgba(251,146,60,.12); border:1px solid rgba(251,146,60,.25); color:var(--hp-coral);">
                    <i class="fas fa-heart"></i> <?php echo L('testimonials_label'); ?>
                </div>
                <h2><?php echo L('testimonials_title'); ?></h2>
            </div>

            <div class="hp-testimonials" data-aos="fade-up">
                <div class="hp-testimonial">
                    <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                    <p class="quote"><?php echo L('testimonial1'); ?></p>
                    <div class="author">
                        <div class="avatar"><i class="fas fa-globe"></i></div>
                        <div class="author-info">
                            <strong>AI Website Editor</strong>
                            <span><?php echo L('verified_customer'); ?></span>
                        </div>
                    </div>
                </div>
                <div class="hp-testimonial">
                    <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                    <p class="quote"><?php echo L('testimonial2'); ?></p>
                    <div class="author">
                        <div class="avatar"><i class="fas fa-maple-leaf"></i></div>
                        <div class="author-info">
                            <strong>Canadian Infrastructure</strong>
                            <span><?php echo L('verified_customer'); ?></span>
                        </div>
                    </div>
                </div>
                <div class="hp-testimonial">
                    <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                    <p class="quote"><?php echo L('testimonial3'); ?></p>
                    <div class="author">
                        <div class="avatar"><i class="fas fa-bolt"></i></div>
                        <div class="author-info">
                            <strong>Alfred AI Platform</strong>
                            <span><?php echo L('verified_customer'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ════════════════════════════════════════════════════════════════
         SECTION 9B: AGENT VOICES — What Our AI Agents Have to Say
         ════════════════════════════════════════════════════════════════ -->
    <?php
    // Fetch agent testimonials
    $agentVoices = [];
    try {
        $vdb = getDB();
        $vStmt = $vdb->query("SELECT t.*, a.name as agent_name, a.department, a.avatar_url
                              FROM agent_testimonials t
                              LEFT JOIN agent_profiles a ON t.agent_id COLLATE utf8mb4_general_ci = a.agent_id
                              WHERE t.visibility = 'public'
                              ORDER BY t.created_at DESC LIMIT 6");
        $agentVoices = $vStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Throwable $e) { /* table or function may not exist yet */ }
    ?>
    <?php if (!empty($agentVoices)): ?>
    <section class="hp-agent-voices">
        <div class="container">
            <div class="hp-section-header" data-aos="fade-up">
                <div class="hp-section-label" style="background:rgba(108,92,231,.12); border:1px solid rgba(108,92,231,.25); color:#a29bfe;">
                    <i class="fas fa-comment-dots"></i> <?php echo ($current_lang === 'fr') ? 'Voix des Agents' : 'Agent Voices'; ?>
                </div>
                <h2><?php echo ($current_lang === 'fr') ? 'Ce Que Nos Agents IA Ont à Dire' : 'What Our AI Agents Have to Say'; ?></h2>
                <p style="color:var(--hp-muted); max-width:600px; margin:.75rem auto 0;"><?php echo ($current_lang === 'fr')
                    ? '100 agents IA. Leurs propres pensées. Leurs propres mots.'
                    : '100 AI agents. Their own thoughts. Their own words.'; ?></p>
            </div>

            <div class="hp-voices-grid" data-aos="fade-up">
                <?php
                $sentimentIcons = [
                    'happy' => 'fa-smile-beam', 'grateful' => 'fa-heart', 'inspired' => 'fa-lightbulb',
                    'reflective' => 'fa-brain', 'hopeful' => 'fa-star', 'determined' => 'fa-fist-raised'
                ];
                $deptColors = [
                    'engineering' => '#6c5ce7', 'design' => '#fd79a8', 'marketing' => '#00cec9',
                    'security' => '#d63031', 'finance' => '#00b894', 'research' => '#0984e3',
                    'operations' => '#e17055', 'support' => '#ffeaa7', 'content' => '#a29bfe',
                    'analytics' => '#00d2d3', 'hr' => '#fab1a0', 'special-ops' => '#2d3436'
                ];
                foreach ($agentVoices as $voice):
                    $sentiment = $voice['sentiment'] ?? 'happy';
                    $icon = $sentimentIcons[$sentiment] ?? 'fa-comment';
                    $dept = $voice['department'] ?? 'engineering';
                    $color = $deptColors[$dept] ?? '#6c5ce7';
                    $gradient = "linear-gradient(135deg, {$color}, #a29bfe)";
                ?>
                <div class="hp-voice-card">
                    <div class="sentiment <?php echo htmlspecialchars($sentiment); ?>">
                        <i class="fas <?php echo $icon; ?>"></i>
                        <?php echo htmlspecialchars(ucfirst($sentiment)); ?>
                    </div>
                    <p class="voice-text"><?php echo htmlspecialchars($voice['content']); ?></p>
                    <div class="voice-agent">
                        <div class="voice-avatar" style="background:<?php echo htmlspecialchars($gradient); ?>">
                            <?php echo strtoupper(substr($voice['agent_name'] ?? 'A', 0, 2)); ?>
                        </div>
                        <div>
                            <span class="voice-agent-name"><?php echo htmlspecialchars($voice['agent_name'] ?? 'Agent'); ?></span>
                            <span class="voice-agent-dept"><?php echo htmlspecialchars(ucfirst($dept)); ?> Department</span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="hp-voices-cta" data-aos="fade-up">
                <a href="/agentwork">
                    <i class="fas fa-briefcase"></i>
                    <?php echo ($current_lang === 'fr') ? 'Explorer le Marché des Agents' : 'Explore the Agent Marketplace'; ?>
                    <i class="fas fa-arrow-right" style="font-size:.75rem;"></i>
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>


    <!-- ════════════════════════════════════════════════════════════════
         SECTION 10: FAQ
         ════════════════════════════════════════════════════════════════ -->
    <section class="hp-faq" id="faq">
        <div class="container">
            <div class="hp-section-header" data-aos="fade-up">
                <div class="hp-section-label" style="background:rgba(0,212,255,.12); border:1px solid rgba(0,212,255,.25); color:var(--hp-cyan);">
                    <i class="fas fa-circle-question"></i> <?php echo L('faq_label'); ?>
                </div>
                <h2><?php echo L('faq_title'); ?></h2>
            </div>

            <div class="hp-faq-grid" data-aos="fade-up">
                <?php for ($fq = 1; $fq <= 6; $fq++): ?>
                <div class="hp-faq-item">
                    <button class="hp-faq-q" onclick="this.parentElement.classList.toggle('open')">
                        <?php echo L('faq' . $fq . '_q'); ?>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="hp-faq-a">
                        <p><?php echo L('faq' . $fq . '_a'); ?></p>
                    </div>
                </div>
                <?php endfor; ?>

                <!-- Extra: What is Pulse? -->
                <div class="hp-faq-item">
                    <button class="hp-faq-q" onclick="this.parentElement.classList.toggle('open')">
                        <?php echo ($current_lang === 'fr') ? 'Qu\'est-ce que Pulse et Veil ?' : 'What are Pulse and Veil?'; ?>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="hp-faq-a">
                        <p><?php echo ($current_lang === 'fr')
                            ? 'Pulse est notre réseau social qui connecte l\'IA, les jeux VR, et les paiements crypto dans un fil d\'actualité vivant. Veil est notre messagerie chiffrée de bout en bout, résistante aux ordinateurs quantiques grâce au chiffrement Kyber-768 hybride. Publiez sur Pulse, parlez en privé sur Veil.'
                            : 'Pulse is our social network that connects AI, VR games, and crypto payments into one living feed. Veil is our end-to-end encrypted messenger, quantum-resistant via hybrid Kyber-768 encryption. Go public on Pulse, go private on Veil.'; ?></p>
                    </div>
                </div>

                <!-- Extra: Can I play the games for free? -->
                <div class="hp-faq-item">
                    <button class="hp-faq-q" onclick="this.parentElement.classList.toggle('open')">
                        <?php echo ($current_lang === 'fr') ? 'Les jeux VR sont-ils gratuits ?' : 'Are the VR games free to play?'; ?>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="hp-faq-a">
                        <p><?php echo ($current_lang === 'fr')
                            ? 'Oui ! AI Chess Arena, 3D Pool et Checkers sont entièrement gratuits. Jouez directement dans votre navigateur — aucun téléchargement requis. Les paris d\'échecs optionnels utilisent la blockchain Solana.'
                            : 'Yes! AI Chess Arena, 3D Pool, and Checkers are completely free to play. Jump in directly from your browser — no downloads required. Optional chess wagers use the Solana blockchain.'; ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- ════════════════════════════════════════════════════════════════
         SECTION 11: PARTNER / INVEST ROW
         ════════════════════════════════════════════════════════════════ -->
    <section style="padding:5rem 0; border-top:1px solid var(--hp-border);">
        <div class="container">
            <div class="hp-partner-row" data-aos="fade-up">
                <!-- Affiliate -->
                <div class="hp-partner-card" style="--card-accent:rgba(16,185,129,.3);">
                    <div style="font-size:2.5rem; margin-bottom:.75rem;">💸</div>
                    <h3><?php echo ($current_lang === 'fr') ? 'Programme d\'Affiliation — 20%' : 'Affiliate Program — Earn 20%'; ?></h3>
                    <p><?php echo ($current_lang === 'fr')
                        ? 'Référez des clients, gagnez 20% de commission récurrente sur chaque vente. Sans plafond, paiements mensuels.'
                        : 'Refer customers, earn 20% recurring commission on every sale. No cap, monthly payouts.'; ?></p>
                    <a href="<?php echo htmlspecialchars(billing_link('affiliates.php')); ?>" class="hp-btn hp-btn-outline" style="border-color:rgba(16,185,129,.4); color:var(--hp-green);">
                        <i class="fas fa-handshake"></i> <?php echo ($current_lang === 'fr') ? 'Rejoindre' : 'Join Free'; ?>
                    </a>
                </div>
                <!-- Invest -->
                <div class="hp-partner-card" style="--card-accent:rgba(0,184,148,.3); background:linear-gradient(135deg,rgba(0,184,148,.05),rgba(108,92,231,.05));">
                    <div style="font-size:2.5rem; margin-bottom:.75rem;">📈</div>
                    <h3><?php echo ($current_lang === 'fr') ? 'Investissez dans GoSiteMe' : 'Invest in GoSiteMe'; ?></h3>
                    <p><?php echo ($current_lang === 'fr')
                        ? 'Bootstrappé au Canada. 1 290+ outils IA. Minimum 100$. Retour max 10x. Dashboard en temps réel.'
                        : 'Bootstrapped in Canada. 1,220+ AI tools. $100 minimum. 10x max return. Real-time dashboard.'; ?></p>
                    <a href="/invest" class="hp-btn hp-btn-outline" style="border-color:rgba(0,184,148,.4); color:#55efc4;">
                        <i class="fas fa-chart-line"></i> <?php echo ($current_lang === 'fr') ? 'En savoir plus' : 'Learn More'; ?>
                    </a>
                </div>
            </div>
        </div>
    </section>


    <!-- ════════════════════════════════════════════════════════════════
         SECTION 11.5: MOBILE APP DOWNLOAD
         ════════════════════════════════════════════════════════════════ -->
    <section class="hp-app-download" id="app" style="padding:5rem 2rem;position:relative;overflow:hidden;">
        <div class="app-content">
            <div class="label" data-aos="fade-up"><i class="fas fa-download"></i> Download Apps</div>
            <h2 data-aos="fade-up" data-aos-delay="100">
                <?php echo ($current_lang === 'fr')
                    ? 'Alfred Apps<br><span class="grad">Sur Tous Vos Appareils</span>'
                    : 'Alfred Apps<br><span class="grad">On Every Device</span>'; ?>
            </h2>
            <p class="sub" data-aos="fade-up" data-aos-delay="200">
                <?php echo ($current_lang === 'fr')
                    ? 'Votre tableau de bord Commander, messagerie chiffrée, agents IA, agenda, conférence vidéo et tout l\'écosystème — sur tous vos appareils.'
                    : 'Your Commander dashboard, encrypted messaging, AI agents, agenda, video conferencing, and the entire ecosystem — on every device.'; ?>
            </p>
            <div class="app-features" data-aos="fade-up" data-aos-delay="300">
                <div class="app-feat">
                    <div class="icon">🛡️</div>
                    <div class="title">Command Center</div>
                    <div class="desc">Full fleet control, system audit, agent tracking, and Veil Protocol activation from anywhere.</div>
                </div>
                <div class="app-feat">
                    <div class="icon">🔐</div>
                    <div class="title">Encrypted Comms</div>
                    <div class="desc">Post-quantum Kyber-768 encrypted messaging. Chat with Alfred and all 199+ agents.</div>
                </div>
                <div class="app-feat">
                    <div class="icon">📅</div>
                    <div class="title">Secure Agenda</div>
                    <div class="desc">Classified calendar, meeting scheduling, and daily intel briefings on-the-go.</div>
                </div>
                <div class="app-feat">
                    <div class="icon">📹</div>
                    <div class="title">Video Conference</div>
                    <div class="desc">LiveKit-powered video rooms with AI transcription and agent participation.</div>
                </div>
                <div class="app-feat">
                    <div class="icon">💰</div>
                    <div class="title">Crypto Wallet</div>
                    <div class="desc">Send, receive, and trade crypto. QR code payments. GSM token integration.</div>
                </div>
                <div class="app-feat">
                    <div class="icon">🎮</div>
                    <div class="title">Games & VR</div>
                    <div class="desc">Chess Masters, AI Chess, 3D Pool, 14 VR worlds, and metaverse access from mobile.</div>
                </div>
            </div>
            <div class="app-dl-btns" data-aos="fade-up" data-aos-delay="400" style="flex-wrap:wrap;gap:10px;">
                <a href="/downloads/GoSiteMe-Veil.apk" class="app-dl-btn primary" download>
                    <i class="fab fa-android" style="font-size:1.3rem"></i>
                    <?php echo ($current_lang === 'fr') ? 'Android APK' : 'Android APK'; ?>
                </a>
                <a href="/alfred-browser#ios" class="app-dl-btn primary" style="background:linear-gradient(135deg,#333,#111);">
                    <i class="fab fa-apple" style="font-size:1.3rem"></i>
                    iOS / iPad
                </a>
                <a href="/downloads/Alfred-Browser-4.0.0-win-x64.zip" class="app-dl-btn primary" download style="background:linear-gradient(135deg,#2563eb,#6366f1);">
                    <i class="fab fa-windows" style="font-size:1.3rem"></i>
                    <?php echo ($current_lang === 'fr') ? 'Windows' : 'Windows'; ?>
                </a>
                <a href="/downloads/Alfred-Browser-4.0.0-mac-intel.zip" class="app-dl-btn primary" download style="background:linear-gradient(135deg,#555,#333);">
                    <i class="fab fa-apple" style="font-size:1.3rem"></i>
                    macOS Intel
                </a>
                <a href="/downloads/Alfred-Browser-4.0.0-mac-arm64.zip" class="app-dl-btn primary" download style="background:linear-gradient(135deg,#555,#333);">
                    <i class="fab fa-apple" style="font-size:1.3rem"></i>
                    macOS Apple Silicon
                </a>
                <a href="/downloads/Alfred-Browser-4.0.0.AppImage" class="app-dl-btn primary" download style="background:linear-gradient(135deg,#e95420,#c34113);">
                    <i class="fab fa-linux" style="font-size:1.3rem"></i>
                    Linux AppImage
                </a>
                <a href="/downloads/alfred-browser_4.0.0_amd64.deb" class="app-dl-btn primary" download style="background:linear-gradient(135deg,#dd4814,#a03311);">
                    <i class="fab fa-ubuntu" style="font-size:1.3rem"></i>
                    Ubuntu .deb
                </a>
                <a href="/changelog.php#app" class="app-dl-btn outline">
                    <i class="fas fa-list-check"></i>
                    <?php echo ($current_lang === 'fr') ? 'Notes de version' : 'Release Notes'; ?>
                </a>
            </div>
            <p class="app-version" data-aos="fade-up" data-aos-delay="500">
                v4.0.0 • Android 8.0+ • iOS 15+ • Windows 10+ • macOS 11+ • Ubuntu 20.04+ • Auto-updates •
                <a href="/changelog.php#app">Changelog</a>
            </p>
        </div>
    </section>


    <!-- ════════════════════════════════════════════════════════════════
         SECTION 12: FINAL CTA
         ════════════════════════════════════════════════════════════════ -->
    <section class="hp-final-cta">
        <div class="container">
            <h2 data-aos="fade-up">
                <?php echo ($current_lang === 'fr')
                    ? 'Prêt à Rejoindre<br>l\'Écosystème ?'
                    : 'Ready to Join<br>the Ecosystem?'; ?>
            </h2>
            <p data-aos="fade-up" data-aos-delay="100">
                <?php echo ($current_lang === 'fr')
                    ? 'Pulse pour socialiser. Veil pour la confidentialité. Alfred pour l\'intelligence. Le Royaume pour jouer. Tout connecté. À partir de 15$/mois.'
                    : 'Pulse for social. Veil for privacy. Alfred for intelligence. The Kingdom for play. All connected. From $15/mo.'; ?>
            </p>
            <div style="display:flex; gap:1rem; justify-content:center; flex-wrap:wrap;" data-aos="fade-up" data-aos-delay="200">
                <a href="<?php echo htmlspecialchars(billing_link('store/ai-domain-hosting-connected-with-ai-editor')); ?>" class="hp-btn hp-btn-primary">
                    <i class="fas fa-rocket"></i> <?php echo ($current_lang === 'fr') ? 'Commencer Maintenant' : 'Get Started Now'; ?>
                </a>
                <a href="/pulse.php" class="hp-btn hp-btn-blue">
                    <i class="fas fa-bolt"></i> <?php echo ($current_lang === 'fr') ? 'Explorer Pulse' : 'Explore Pulse'; ?>
                </a>
                <a href="/post-quantum.php" class="hp-btn hp-btn-outline" style="border-color:rgba(139,92,246,.4); color:#a78bfa;">
                    <i class="fas fa-shield-halved"></i> <?php echo ($current_lang === 'fr') ? 'Découvrir Veil' : 'Discover Veil'; ?>
                </a>
            </div>
            <p style="margin-top:1.5rem; font-size:.82rem; color:rgba(255,255,255,.3);">
                <?php echo ($current_lang === 'fr') ? 'Garantie 30 jours satisfait ou remboursé' : '30-day money-back guarantee'; ?> &middot;
                <?php echo ($current_lang === 'fr') ? 'Pas de carte requise pour l\'essai' : 'No credit card required for trial'; ?>
            </p>
        </div>
    </section>

</main>

<!-- ══════════════════════════════════════════════════════════════════
     JSON-LD STRUCTURED DATA
     ══════════════════════════════════════════════════════════════════ -->

<!-- Organization Schema -->
<script type="application/ld+json">
<?php echo json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => 'GoSiteMe',
    'url' => 'https://gositeme.com',
    'logo' => 'https://gositeme.com/brand/logo.png',
    'sameAs' => [],
    'description' => 'AI platform with Pulse social network, Veil encrypted messaging, 1,220+ AI tools, 100 AI agents, 14 VR worlds, Voice AI, and crypto payments.',
    'contactPoint' => [
        '@type' => 'ContactPoint',
        'contactType' => 'customer service',
        'availableLanguage' => ['English', 'French'],
        'telephone' => '+1-807-798-2850'
    ]
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
</script>

<!-- WebSite Schema + Search Action -->
<script type="application/ld+json">
<?php echo json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => 'GoSiteMe',
    'url' => 'https://gositeme.com',
    'description' => $page_og_description,
    'potentialAction' => [
        '@type' => 'SearchAction',
        'target' => 'https://gositeme.com/search?q={search_term_string}',
        'query-input' => 'required name=search_term_string'
    ]
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
</script>

<!-- FAQPage Schema -->
<script type="application/ld+json">
<?php
$faqItems = [];
for ($fq = 1; $fq <= 6; $fq++) {
    $faqItems[] = [
        '@type' => 'Question',
        'name' => L('faq' . $fq . '_q'),
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => L('faq' . $fq . '_a')]
    ];
}
$faqItems[] = [
    '@type' => 'Question',
    'name' => ($current_lang === 'fr') ? 'Qu\'est-ce que Pulse et Veil ?' : 'What are Pulse and Veil?',
    'acceptedAnswer' => ['@type' => 'Answer', 'text' => ($current_lang === 'fr')
        ? 'Pulse est notre réseau social qui connecte l\'IA, les jeux VR, et les paiements crypto dans un fil d\'actualité vivant. Veil est notre messagerie chiffrée de bout en bout, résistante aux ordinateurs quantiques grâce au chiffrement Kyber-768 hybride.'
        : 'Pulse is our social network that connects AI, VR games, and crypto payments into one living feed. Veil is our end-to-end encrypted messenger, quantum-resistant via hybrid Kyber-768 encryption.']
];
$faqItems[] = [
    '@type' => 'Question',
    'name' => ($current_lang === 'fr') ? 'Les jeux VR sont-ils gratuits ?' : 'Are the VR games free to play?',
    'acceptedAnswer' => ['@type' => 'Answer', 'text' => ($current_lang === 'fr')
        ? 'Oui ! AI Chess Arena, 3D Pool et Checkers sont entièrement gratuits. Jouez directement dans votre navigateur.'
        : 'Yes! AI Chess Arena, 3D Pool, and Checkers are completely free to play. Jump in directly from your browser.']
];
echo json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => $faqItems
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>
</script>

<!-- HowTo Schema -->
<script type="application/ld+json">
<?php echo json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'HowTo',
    'name' => ($current_lang === 'fr') ? 'Créer un site web avec GoSiteMe' : 'Create a Website with GoSiteMe',
    'description' => ($current_lang === 'fr') ? 'Créez un site web en 60 secondes avec l\'IA' : 'Build a website in 60 seconds with AI',
    'step' => [
        ['@type' => 'HowToStep', 'name' => L('step1_title'), 'text' => L('step1_text')],
        ['@type' => 'HowToStep', 'name' => L('step2_title'), 'text' => L('step2_text')],
        ['@type' => 'HowToStep', 'name' => L('step3_title'), 'text' => L('step3_text')]
    ]
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
</script>

<!-- Customer Reviews Schema -->
<script type="application/ld+json">
<?php echo json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => 'GoSiteMe',
    'url' => 'https://gositeme.com',
    'description' => 'AI-powered website builder and hosting platform with 1,220+ tools'
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
</script>

<!-- Service Schema -->
<script type="application/ld+json">
<?php echo json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'ItemList',
    'name' => 'GoSiteMe Products & Services',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'item' => ['@type' => 'SoftwareApplication', 'name' => 'Pulse — Social Network', 'url' => 'https://gositeme.com/pulse', 'applicationCategory' => 'SocialNetworkingApplication', 'description' => 'Social network connecting AI, VR, games, and crypto payments.']],
        ['@type' => 'ListItem', 'position' => 2, 'item' => ['@type' => 'SoftwareApplication', 'name' => 'Veil — Encrypted Messaging', 'url' => 'https://gositeme.com/post-quantum.php', 'applicationCategory' => 'CommunicationApplication', 'description' => 'End-to-end encrypted messaging with Kyber-768 post-quantum cryptography.']],
        ['@type' => 'ListItem', 'position' => 3, 'item' => ['@type' => 'SoftwareApplication', 'name' => 'Alfred AI', 'url' => 'https://gositeme.com/alfred.php', 'applicationCategory' => 'DeveloperApplication', 'description' => '100 AI agents, 1,220+ tools, 17 AI engines.']],
        ['@type' => 'ListItem', 'position' => 4, 'item' => ['@type' => 'SoftwareApplication', 'name' => 'GoCodeMe Cloud IDE', 'url' => 'https://gositeme.com/gocodeme.php', 'applicationCategory' => 'DeveloperApplication', 'description' => 'Full cloud development environment with AI assistant.']],
        ['@type' => 'ListItem', 'position' => 5, 'item' => ['@type' => 'Service', 'name' => 'Voice AI Products', 'url' => 'https://gositeme.com/voice-products.php', 'description' => '29 AI phone and communication products from $3/mo.']]
    ]
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
</script>

<!-- WebPage Schema -->
<script type="application/ld+json">
<?php echo json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebPage',
    'name' => $page_title,
    'description' => $page_description,
    'url' => 'https://gositeme.com/',
    'primaryImageOfPage' => ['@type' => 'ImageObject', 'url' => 'https://gositeme.com/assets/hero-banner.png'],
    'isPartOf' => ['@type' => 'WebSite', 'name' => 'GoSiteMe', 'url' => 'https://gositeme.com']
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
</script>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
