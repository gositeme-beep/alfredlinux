    <?php if (empty($noGlobalMain)): ?></main><?php endif; ?>
    <?php if (!function_exists('L')) { require_once __DIR__ . '/lang.php'; } ?>

    <!-- Shabbat / God's Clock Banner — visible ecosystem-wide -->
    <div id="shabbat-banner" style="display:none; background:linear-gradient(135deg,#1a1a2e 0%,#16213e 50%,#0f3460 100%); border-top:2px solid #f6c343; padding:14px 20px; text-align:center; font-family:Georgia,'Times New Roman',serif; color:#e8d5b7; position:relative; z-index:100;">
        <div style="max-width:900px; margin:0 auto;">
            <div id="shabbat-icon" style="font-size:1.4rem; margin-bottom:4px;">&#x1F56F;</div>
            <div id="shabbat-msg" style="font-size:1.05rem; font-weight:600; letter-spacing:0.3px;"></div>
            <div id="shabbat-time" style="font-size:0.85rem; margin-top:4px; color:#f6c343; opacity:0.9;"></div>
            <div id="shabbat-date" style="font-size:0.78rem; margin-top:3px; color:#a8a8b3;"></div>
        </div>
    </div>
    <script>
    (function(){
        var b = document.getElementById('shabbat-banner');
        if (!b) return;
        fetch('/api/daniel-calendar.php?city=montreal')
            .then(function(r){ return r.json(); })
            .then(function(d){
                var s = d.shabbat || {};
                var sun = d.sun || {};
                var heb = d.hebrew || {};
                var en = d.enochian || {};
                var msg = '', time = '', icon = '\u{1F56F}';
                if (s.isShabbat) {
                    icon = '\u2728';
                    msg = 'Shabbat Shalom \u2014 The Sabbath is here. Rest in His presence.';
                    time = 'Havdalah (end): ' + (s.havdalah || sun.sunset && sun.sunset.formatted || '');
                } else if (s.isErevShabbat) {
                    icon = '\u{1F56F}';
                    var cl = (sun.candleLighting || '');
                    var ss = (sun.sunset && sun.sunset.formatted || '');
                    msg = 'Erev Shabbat \u2014 The sun is going down. Prepare your heart.';
                    time = 'Candle lighting: ' + cl + ' \u00B7 Sunset: ' + ss + ' (Montr\u00e9al)';
                } else {
                    var dow = (d.gregorian && d.gregorian.dayOfWeek || '');
                    var days = {Sunday:6,Monday:5,Tuesday:4,Wednesday:3,Thursday:2,Friday:1,Saturday:0};
                    var left = days[dow];
                    if (typeof left === 'number' && left > 0) {
                        msg = left + ' day' + (left>1?'s':'') + ' until Shabbat';
                        time = 'Next Friday sunset in Montr\u00e9al';
                        icon = '\u{1F54E}';
                    }
                }
                if (msg) {
                    document.getElementById('shabbat-icon').innerHTML = icon;
                    document.getElementById('shabbat-msg').textContent = msg;
                    document.getElementById('shabbat-time').textContent = time;
                    var dateStr = '';
                    if (heb.formatted) dateStr += heb.formatted;
                    if (en.formatted) dateStr += ' \u00B7 ' + en.formatted;
                    var feast = d.activeFeast && d.activeFeast.name;
                    if (feast) dateStr += ' \u00B7 ' + feast;
                    document.getElementById('shabbat-date').textContent = dateStr;
                    b.style.display = 'block';
                }
            })
            .catch(function(){});
    })();
    </script>

    <!-- Footer -->
    <footer class="footer" role="contentinfo" aria-label="<?php echo htmlspecialchars(L('a11y_site_footer'), ENT_QUOTES, 'UTF-8'); ?>">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <a href="/" class="logo">
                        <img src="/brand/logo_w.png" alt="GoSiteMe - Best Web Hosting &amp; AI Website Builder">
                    </a>
                    <p><?php echo L('footer_tagline'); ?></p>
                    
                    <!-- Toll-Free Phone Number -->
                    <a href="tel:+18334674836" class="footer-phone">
                        <i class="fas fa-phone-alt"></i>
                        <div class="footer-phone-info">
                            <span class="footer-phone-number">1-833-GOSITEME</span>
                            <span class="footer-phone-label"><?php echo L('footer_support_label'); ?></span>
                        </div>
                    </a>
                    
                    <div class="footer-social">
                        <a href="https://facebook.com/root" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://x.com/root" target="_blank" rel="noopener noreferrer" aria-label="X (Twitter)"><i class="fab fa-x-twitter"></i></a>
                        <a href="https://instagram.com/root" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="https://linkedin.com/company/root" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                    <p style="margin-top:1rem;font-style:italic;color:rgba(255,255,255,0.45);font-size:0.82rem;line-height:1.6;">&ldquo;The grass withereth, the flower fadeth: but the word of our God shall stand for ever.&rdquo; &mdash; Isaiah 40:8 AKJV</p>
                    <div style="margin-top:0.5rem;font-size:0.78rem;">
                        <a href="https://lavocat.ca/journal?read=9&amp;lang=en" style="color:#c084fc;">Commander&rsquo;s Journal</a> &middot;
                        <a href="/sovereignty" style="color:#fbbf24;">Sovereignty</a> &middot;
                        <a href="/bible/read/isaiah/40" style="color:#60a5fa;">Isaiah 40 AKJV</a>
                    </div>
                </div>
                
                <div class="footer-col">
                    <h4><?php echo L('footer_hosting'); ?></h4>
                    <ul>
                        <li><a href="<?php echo htmlspecialchars(billing_link('store/ai-domain-hosting-connected-with-ai-editor')); ?>"><?php echo L('nav_ai_hosting'); ?></a></li>
                        <li><a href="<?php echo htmlspecialchars(billing_link('store/token-packs')); ?>">Token Packs</a></li>
                        <li><a href="<?php echo htmlspecialchars(billing_link('store/ssl-certificates')); ?>">SSL Certificates</a></li>
                        <li><a href="/ai-servers/"><?php echo L('footer_ai_servers'); ?></a></li>
                        <li><a href="<?php echo htmlspecialchars(billing_link('store/ai-domain-hosting-connected-with-ai-editor')); ?>"><?php echo L('footer_domain_registration'); ?></a></li>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h4><?php echo L('footer_products'); ?></h4>
                    <ul>
                        <li><a href="/ai-servers/"><i class="fas fa-microchip" aria-hidden="true"></i> <?php echo L('footer_ai_servers'); ?></a></li>
                        <li><a href="/editor/"><i class="fas fa-globe" aria-hidden="true"></i> <?php echo L('nav_use_online'); ?></a></li>
                        <li><a href="/alfred-ide.php" class="footer-gocodeme"><i class="fas fa-download" aria-hidden="true"></i> Alfred IDE</a></li>
                        <li><a href="/alfred-browser" style="color:#3B82F6;"><i class="fas fa-shield-alt" aria-hidden="true"></i> Alfred Browser</a></li>
                        <li><a href="https://alfredlinux.com" style="color:#fbbf24;"><i class="fab fa-linux" aria-hidden="true"></i> Alfred Linux</a></li>
                        <li><a href="/library.php" style="color:#f59e0b;"><i class="fas fa-book-open" aria-hidden="true"></i> Kingdom Library</a></li>
                        <li><a href="/downloads/33-jurisdictions/" style="color:#60a5fa;"><i class="fas fa-scale-balanced" aria-hidden="true"></i> 33 Jurisdictions</a></li>
                        <li><a href="/apps" style="color:#34d399;"><i class="fas fa-th" aria-hidden="true"></i> All Apps & Downloads</a></li>
                        <li><a href="<?php echo htmlspecialchars(billing_link('store/ai-domain-hosting-connected-with-ai-editor')); ?>"><?php echo L('footer_ai_hosting_plans'); ?></a></li>
                        <li><a href="/alfred.php#addons">Power-Up Add-Ons</a></li>
                        <li><a href="/meet-alfred" style="color:#c084fc;"><i class="fas fa-fingerprint" aria-hidden="true"></i> Meet Alfred</a></li>
                        <li><a href="/voice-products.php"><i class="fas fa-phone-volume" aria-hidden="true"></i> Voice & AI Products</a></li>
                        <li><a href="/voice-cloning.php"><i class="fas fa-microphone-lines" aria-hidden="true"></i> Voice Cloning</a></li>
                        <li><a href="/call-campaigns.php"><i class="fas fa-bullhorn" aria-hidden="true"></i> Call Campaigns</a></li>
                        <li><a href="<?php echo htmlspecialchars(billing_link('contact.php')); ?>"><?php echo L('footer_site_migration'); ?></a></li>
                        <li><a href="/sdks"><i class="fas fa-cube" aria-hidden="true"></i> SDKs</a></li>
                        <li><a href="/webhooks.php"><i class="fas fa-plug" aria-hidden="true"></i> Webhooks</a></li>
                        <li><a href="/agent-templates.php"><i class="fas fa-clone" aria-hidden="true"></i> Agent Templates</a></li>
                        <li><a href="/help.php"><i class="fas fa-life-ring" aria-hidden="true"></i> Help Center</a></li>
                        <li><a href="/team-chat.php"><i class="fas fa-users-cog" aria-hidden="true"></i> Team Chat</a></li>
                        <li><a href="/pay/account/crypto"><i class="fas fa-coins" aria-hidden="true"></i> Crypto Trading</a></li>
                        <li><a href="/pay/account/crypto-reports"><i class="fas fa-chart-pie" aria-hidden="true"></i> Crypto Reports</a></li>
                        <li><a href="/open-source/"><i class="fas fa-code-branch" aria-hidden="true"></i> Open Source</a></li>
                        <li><a href="/templates/"><i class="fas fa-th-large" aria-hidden="true"></i> Templates</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Alfred AI</h4>
                    <ul>
                        <li><a href="/tools/"><i class="fas fa-toolbox" aria-hidden="true"></i> <?php echo L('footer_tools'); ?></a></li>
                        <li><a href="/marketplace.php"><i class="fas fa-store" aria-hidden="true"></i> <?php echo L('footer_marketplace'); ?></a></li>
                        <li><a href="/pricing.php"><i class="fas fa-tags" aria-hidden="true"></i> <?php echo L('nav_pricing'); ?></a></li>
                        <li><a href="/use-cases/"><i class="fas fa-users" aria-hidden="true"></i> <?php echo L('nav_use_cases'); ?></a></li>
                        <li><a href="/articles/"><i class="fas fa-newspaper" aria-hidden="true"></i> <?php echo L('footer_blog'); ?></a></li>
                        <li><a href="/about.php"><i class="fas fa-info-circle" aria-hidden="true"></i> <?php echo L('footer_about'); ?></a></li>
                        <li><a href="/founder.php"><i class="fas fa-user-astronaut" aria-hidden="true"></i> Meet the Founder</a></li>
                        <li><a href="/compare.php"><i class="fas fa-columns" aria-hidden="true"></i> <?php echo L('footer_compare'); ?></a></li>
                        <li><a href="/enterprise.php"><i class="fas fa-building" aria-hidden="true"></i> <?php echo L('footer_enterprise'); ?></a></li>
                        <li><a href="/docs/"><i class="fas fa-book" aria-hidden="true"></i> <?php echo L('footer_docs'); ?></a></li>
                        <li><a href="/changelog.php"><i class="fas fa-clipboard-list" aria-hidden="true"></i> <?php echo L('footer_changelog'); ?></a></li>
                        <li><a href="/fleet-dashboard.php"><i class="fas fa-satellite-dish" aria-hidden="true"></i> Fleet Dashboard</a></li>
                        <li><a href="/conference-room.php"><i class="fas fa-headset" aria-hidden="true"></i> Conference Rooms</a></li>
                        <li><a href="/extensions.php"><i class="fas fa-puzzle-piece" aria-hidden="true"></i> Extensions</a></li>
                        <li><a href="/ivr-builder.php"><i class="fas fa-diagram-project" aria-hidden="true"></i> IVR Builder</a></li>
                        <li><a href="/games.php"><i class="fas fa-gamepad" aria-hidden="true"></i> Games & Arcade</a></li>
                        <li><a href="/game-lobby.php"><i class="fas fa-satellite-dish" aria-hidden="true"></i> Live Lobby</a></li>
                        <li><a href="/vr-worlds.php"><i class="fas fa-vr-cardboard" aria-hidden="true"></i> VR Worlds Directory</a></li>
                        <li><a href="/blockchain.php"><i class="fas fa-link" aria-hidden="true"></i> GSM Blockchain</a></li>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h4>Developers</h4>
                    <ul>
                        <li><a href="/docs/api-reference"><i class="fas fa-code" aria-hidden="true"></i> API Reference</a></li>
                        <li><a href="/docs/getting-started"><i class="fas fa-play-circle" aria-hidden="true"></i> Getting Started</a></li>
                        <li><a href="/sdks"><i class="fas fa-cube" aria-hidden="true"></i> SDKs</a></li>
                        <li><a href="/developer-portal.php"><i class="fas fa-terminal" aria-hidden="true"></i> Developer Portal</a></li>
                        <li><a href="/docs/quickstart.php"><i class="fas fa-rocket" aria-hidden="true"></i> 60s Quickstart</a></li>
                        <li><a href="/webhooks.php"><i class="fas fa-plug" aria-hidden="true"></i> Webhooks</a></li>
                        <li><a href="/community.php"><i class="fas fa-users" aria-hidden="true"></i> Community</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Use Cases</h4>
                    <ul>
                        <li><a href="/use-cases/restaurants"><i class="fas fa-utensils" aria-hidden="true"></i> Restaurants</a></li>
                        <li><a href="/use-cases/ecommerce"><i class="fas fa-shopping-bag" aria-hidden="true"></i> E-Commerce</a></li>
                        <li><a href="/use-cases/dental"><i class="fas fa-tooth" aria-hidden="true"></i> Dental</a></li>
                        <li><a href="/use-cases/insurance"><i class="fas fa-shield-alt" aria-hidden="true"></i> Insurance</a></li>
                        <li><a href="/use-cases/logistics"><i class="fas fa-truck" aria-hidden="true"></i> Logistics</a></li>
                        <li><a href="/use-cases/accounting"><i class="fas fa-calculator" aria-hidden="true"></i> Accounting</a></li>
                        <li><a href="/use-cases/nonprofits"><i class="fas fa-hand-holding-heart" aria-hidden="true"></i> Nonprofits</a></li>
                        <li><a href="/use-cases/travel"><i class="fas fa-plane" aria-hidden="true"></i> Travel</a></li>
                        <li><a href="/use-cases/manufacturing"><i class="fas fa-industry" aria-hidden="true"></i> Manufacturing</a></li>
                        <li><a href="/use-cases/government"><i class="fas fa-landmark" aria-hidden="true"></i> Government</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4><?php echo L('footer_company'); ?></h4>
                    <ul>
                        <li><a href="/about.php"><?php echo L('footer_about'); ?></a></li>
                        <li><a href="<?php echo htmlspecialchars(billing_link('announcements')); ?>"><?php echo L('footer_news_updates'); ?></a></li>
                        <li><a href="<?php echo htmlspecialchars(billing_link('knowledgebase')); ?>"><?php echo L('footer_knowledge_base'); ?></a></li>
                        <li><a href="<?php echo htmlspecialchars(billing_link('contact.php')); ?>"><?php echo L('footer_contact_us'); ?></a></li>
                        <li><a href="/affiliate.php"><i class="fas fa-dollar-sign" aria-hidden="true"></i> Affiliate Program</a></li>
                        <li><a href="/invest" style="color:#55efc4;"><i class="fas fa-chart-line" aria-hidden="true"></i> Invest</a></li>
                        <li><a href="/security.php"><i class="fas fa-shield-halved" aria-hidden="true"></i> Security</a></li>
                        <li><a href="/press-kit.php"><i class="fas fa-newspaper" aria-hidden="true"></i> Press Kit</a></li>
                        <li><a href="/pulse.php" style="color:#60a5fa;"><i class="fas fa-bolt" aria-hidden="true"></i> Pulse</a></li>
                        <li><a href="/veil/" style="color:#a78bfa;"><i class="fas fa-comments" aria-hidden="true"></i> Veil</a></li>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h4><?php echo L('footer_support'); ?></h4>
                    <ul>
                        <li><a href="<?php echo htmlspecialchars(billing_link('clientarea.php')); ?>"><?php echo L('footer_client_area'); ?></a></li>
                        <li><a href="/my-account">My Account</a></li>
                        <li><a href="<?php echo htmlspecialchars(billing_link('submitticket.php')); ?>"><?php echo L('footer_submit_ticket'); ?></a></li>
                        <li><a href="<?php echo htmlspecialchars(billing_link('serverstatus.php')); ?>"><?php echo L('footer_server_status'); ?></a></li>
                        <li><a href="/privacy-policy/"><?php echo L('footer_privacy'); ?></a></li>
                        <li><a href="/terms-of-service.php"><?php echo L('footer_terms'); ?></a></li>
                        <li><a href="/humans.txt">Humans</a></li>
                        <li><a href="/donate.php" style="color:#e74c3c;"><i class="fas fa-heart" aria-hidden="true"></i> Donate</a></li>
                    </ul>
                </div>
            </div>
            
            <!-- SEO: keyword-rich internal links (visible, natural) -->
            <div class="footer-explore" style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid rgba(255,255,255,0.08); text-align: center;">
                <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 0.5rem;"><?php echo L('footer_explore'); ?>: <a href="<?php echo htmlspecialchars(billing_link('store/ai-domain-hosting-connected-with-ai-editor')); ?>">AI development platform</a> &middot; <a href="<?php echo htmlspecialchars(billing_link('store/token-packs')); ?>">token packs</a> &middot; <a href="<?php echo htmlspecialchars(billing_link('store/ai-domain-hosting-connected-with-ai-editor')); ?>">domain registration</a> &middot; <a href="<?php echo htmlspecialchars(billing_link('store/ssl-certificates')); ?>">SSL certificates</a> &middot; <a href="/alfred-ide.php">Alfred IDE</a> &middot; <a href="/editor/">build website online</a> &middot; <a href="<?php echo htmlspecialchars(billing_link('contact.php')); ?>">24/7 support</a></p>
                <p style="font-size: 0.85rem; color: var(--text-muted);">Best AI development platform <?php echo date('Y'); ?> &middot; <a href="/alfred.php">AI coding assistant</a> &middot; <a href="/alfred-ide.php">Alfred IDE</a> &middot; <a href="<?php echo htmlspecialchars(billing_link('store/ai-domain-hosting-connected-with-ai-editor')); ?>">AI hosting</a> &middot; <a href="<?php echo htmlspecialchars(billing_link('store/ai-domain-hosting-connected-with-ai-editor')); ?>">buy domain</a> &middot; <a href="/alfred.php#addons">power-up add-ons</a> &middot; <a href="/voice-products.php">AI voice agents</a></p>
            </div>
            
            <div class="footer-bottom">
                <p>© <?php echo date('Y'); ?> GoSiteMe.com. <?php echo L('footer_rights'); ?></p>
                <a href="#main" class="back-to-top" aria-label="<?php echo htmlspecialchars(L('a11y_back_to_top'), ENT_QUOTES, 'UTF-8'); ?>"><i class="fas fa-chevron-up" aria-hidden="true"></i></a>
                <div class="footer-payments">
                    <i class="fab fa-cc-visa" aria-hidden="true"></i>
                    <i class="fab fa-cc-mastercard" aria-hidden="true"></i>
                    <i class="fab fa-cc-amex" aria-hidden="true"></i>
                    <i class="fab fa-cc-paypal" aria-hidden="true"></i>
                    <i class="fab fa-bitcoin" aria-hidden="true"></i>
                </div>
            </div>
        </div>
    </footer>

    <?php
    // ── Kingdom Covenant Footer (AKJV verse + 4 corners + 9 pillars) ──
    if (!function_exists('akjv_random_verse')) {
        $__bd = '/home/root/shared/bible/bible-data.php';
        if (is_file($__bd)) { require_once $__bd; }
    }
    $__cf = '/home/root/shared/includes/covenant-footer.inc.php';
    if (is_file($__cf)) { include $__cf; }
    ?>

    <!-- Login Modal -->
    <div class="modal-overlay" id="loginModal">
        <div class="modal-box" style="position: relative;">
            <button type="button" class="modal-close" onclick="closeModal('loginModal')" aria-label="<?php echo L('modal_close'); ?>">&times;</button>
            <h2><?php echo L('modal_welcome_back'); ?></h2>
            <p><?php echo L('modal_sign_in_manage'); ?></p>
            
            <div class="modal-error" id="loginError"></div>
            
            <form id="loginForm" onsubmit="return handleLogin(event)">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['alfred_csrf'] ?? '' ?>">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required placeholder="you@example.com">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="Your password">
                </div>
                <button type="submit" class="btn btn-primary" id="loginBtn">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>

            <!-- Forgot Password Form (hidden by default) -->
            <div id="forgotPasswordForm" style="display:none;">
                <div class="modal-error" id="forgotError"></div>
                <div id="forgotSuccess" style="display:none; background:rgba(16,185,129,0.1); border:1px solid rgba(16,185,129,0.3); border-radius:8px; padding:14px; margin-bottom:16px; color:#10b981; font-size:0.9rem; text-align:center;"></div>
                <form id="forgotForm" onsubmit="return handleForgotPassword(event)">
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" required placeholder="you@example.com">
                    </div>
                    <button type="submit" class="btn btn-primary" id="forgotBtn">
                        <i class="fas fa-paper-plane"></i> Send Reset Link
                    </button>
                </form>
                <div style="text-align:center; margin-top:12px;">
                    <a href="#" onclick="showLoginForm()" style="color:var(--cyan); font-size:0.9rem;"><i class="fas fa-arrow-left" style="margin-right:4px;"></i> Back to Sign In</a>
                </div>
            </div>

            <div style="text-align:center; margin: 16px 0 8px;">
                <a href="https://root.com/middleware/dashboard" style="color: var(--cyan); font-size: 0.9rem; font-weight: 600; text-decoration: none;">
                    <i class="fas fa-external-link-alt" style="margin-right: 6px;"></i> <?php echo htmlspecialchars(L('a11y_open_editor'), ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </div>

            <!-- Social Login Divider -->
            <div class="login-divider">
                <span></span>
                <span>Or</span>
                <span></span>
            </div>

            <!-- Social Login Buttons -->
            <div class="social-login-buttons">
                <a href="/login?provider=google" class="btn-social btn-google">
                    <svg viewBox="0 0 24 24" width="20" height="20">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.27-4.74 3.27-8.1z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18A11.96 11.96 0 0 0 1 12c0 1.94.46 3.77 1.18 5.42l3.66-2.84z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    Continue with Google
                </a>
                <a href="/login?provider=facebook" class="btn-social btn-facebook">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="#fff">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                    Continue with Facebook
                </a>
                <a href="/login?provider=twitter" class="btn-social btn-x">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="#fff">
                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.0.10 4.126H5.117z"/>
                    </svg>
                    Continue with X
                </a>
            </div>

            <div class="modal-links" id="loginModalLinks">
                <a href="#" onclick="switchModal('registerModal')">Don't have an account? Sign up</a><br>
                <a href="#" onclick="showForgotPassword()" style="color: var(--text-muted);">Forgot password?</a>
            </div>
        </div>
    </div>
    
    <!-- Register Modal -->
    <div class="modal-overlay" id="registerModal">
        <div class="modal-box" style="position: relative;">
            <button type="button" class="modal-close" onclick="closeModal('registerModal')" aria-label="<?php echo L('modal_close'); ?>">&times;</button>
            <h2><?php echo L('modal_create_account'); ?></h2>
            <p><?php echo L('modal_get_started_today'); ?></p>
            
            <div class="modal-error" id="registerError"></div>
            
            <form id="registerForm" onsubmit="return handleRegister(event)">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['alfred_csrf'] ?? '' ?>">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label><?php echo L('modal_first_name'); ?></label>
                        <input type="text" name="firstname" required placeholder="John">
                    </div>
                    <div class="form-group">
                        <label><?php echo L('modal_last_name'); ?></label>
                        <input type="text" name="lastname" required placeholder="Doe">
                    </div>
                </div>
                <div class="form-group">
                    <label><?php echo L('modal_email'); ?></label>
                    <input type="email" name="email" required placeholder="you@example.com">
                </div>
                <div class="form-group">
                    <label><?php echo L('modal_password'); ?></label>
                    <input type="password" name="password" required placeholder="Min 8 characters" minlength="8">
                </div>
                <button type="submit" class="btn btn-purple" id="registerBtn">
                    <i class="fas fa-user-plus"></i> <?php echo L('modal_create_account_btn'); ?>
                </button>
            </form>

            <!-- Social Login Divider -->
            <div class="login-divider">
                <span></span>
                <span>Or</span>
                <span></span>
            </div>

            <!-- Social Sign-Up Buttons -->
            <div class="social-login-buttons">
                <a href="/register?provider=google" class="btn-social btn-google">
                    <svg viewBox="0 0 24 24" width="20" height="20">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.27-4.74 3.27-8.1z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18A11.96 11.96 0 0 0 1 12c0 1.94.46 3.77 1.18 5.42l3.66-2.84z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    Sign up with Google
                </a>
                <a href="/register?provider=facebook" class="btn-social btn-facebook">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="#fff">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                    Sign up with Facebook
                </a>
                <a href="/register?provider=twitter" class="btn-social btn-x">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="#fff">
                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.0.10 4.126H5.117z"/>
                    </svg>
                    Sign up with X
                </a>
            </div>
            
            <div class="modal-links">
                <a href="#" onclick="switchModal('loginModal')"><?php echo L('modal_have_account'); ?></a>
            </div>
        </div>
    </div>

    <!-- Reset Password Modal (shown when ?reset=TOKEN is in URL) -->
    <div class="modal-overlay" id="resetPasswordModal">
        <div class="modal-box" style="position: relative;">
            <button type="button" class="modal-close" onclick="closeModal('resetPasswordModal')" aria-label="<?php echo L('modal_close'); ?>">&times;</button>
            <h2><i class="fas fa-key" style="color:var(--cyan);margin-right:8px;"></i> Reset Password</h2>
            <p>Enter your new password below.</p>
            
            <div class="modal-error" id="resetError"></div>
            <div id="resetSuccess" style="display:none; background:rgba(16,185,129,0.1); border:1px solid rgba(16,185,129,0.3); border-radius:8px; padding:14px; margin-bottom:16px; color:#10b981; font-size:0.9rem; text-align:center;"></div>
            
            <form id="resetForm" onsubmit="return handleResetPassword(event)">
                <input type="hidden" name="token" id="resetToken" value="">
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="password" required placeholder="Min 8 characters, include a number" minlength="8">
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="password_confirm" required placeholder="Re-enter your new password" minlength="8">
                </div>
                <button type="submit" class="btn btn-primary" id="resetBtn">
                    <i class="fas fa-check"></i> Reset Password
                </button>
            </form>
            
            <div class="modal-links">
                <a href="#" onclick="closeModal('resetPasswordModal'); openModal('loginModal');">Back to Sign In</a>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        window.billingLangParam = '<?php echo htmlspecialchars(billing_lang_param(), ENT_QUOTES, 'UTF-8'); ?>';
        window.langDomainAvailable = <?php echo json_encode(L('domain_available')); ?>;
        window.langDomainTaken = <?php echo json_encode(L('domain_taken')); ?>;
        window.langDomainCheckAtCheckout = <?php echo json_encode(L('domain_check_at_checkout')); ?>;
        window.langDomainAdd = <?php echo json_encode(L('domain_add')); ?>;
        window.langDomainSearch = <?php echo json_encode(L('domain_search')); ?>;
        window.langDomainChecking = <?php echo json_encode(L('domain_checking')); ?>;
        window.langDomainSearchError = <?php echo json_encode(L('domain_search_error')); ?>;
        window.langDomainConnectError = <?php echo json_encode(L('domain_connect_error')); ?>;
    </script>

    <script src="/assets/js/vendor/aos.min.js" defer></script>
    <script>
        // AOS: aos.css sets [data-aos] to opacity:0 until .aos-animate. If init/IO misses
        // (common on wide monitors / slow paint), the whole hero looks empty. Homepage:
        // keep #aos-safety and skip AOS.init. Other pages: init + unstuck pass.
        window.addEventListener('load', function() {
            if (typeof AOS === 'undefined') {
                return;
            }
            var safety = document.getElementById('aos-safety');
            var isHome = document.body.classList.contains('page-home')
                || window.location.pathname === '/'
                || /\/index\.php$/i.test(window.location.pathname);
            if (isHome) {
                return;
            }
            if (safety) {
                safety.remove();
            }
            AOS.init({
                duration: 800,
                easing: 'ease-out-cubic',
                once: true
            });
            requestAnimationFrame(function() {
                if (typeof AOS.refresh === 'function') {
                    AOS.refresh();
                }
            });
            setTimeout(function() {
                if (typeof AOS.refresh === 'function') {
                    AOS.refresh();
                }
                document.querySelectorAll('[data-aos]:not(.aos-animate)').forEach(function(el) {
                    var r = el.getBoundingClientRect();
                    if (r.top < window.innerHeight + 150 && r.bottom > -150) {
                        el.classList.add('aos-animate');
                    }
                });
            }, 500);
        });
        
        // Navbar scroll effect & banner hide
        const navbar = document.getElementById('navbar');
        let lastScroll = 0;
        
        window.addEventListener('scroll', () => {
            const currentScroll = window.scrollY;
            
            if (currentScroll > 50) {
                navbar.classList.add('scrolled');
                document.body.classList.add('banner-hidden');
            } else {
                navbar.classList.remove('scrolled');
                document.body.classList.remove('banner-hidden');
            }
            
            lastScroll = currentScroll;
        });
        
        // Animated Counter
        const counters = document.querySelectorAll('.counter');
        const speed = 200;
        
        const animateCounter = (counter) => {
            const target = +counter.getAttribute('data-target');
            const increment = target / speed;
            let current = 0;
            
            const updateCounter = () => {
                current += increment;
                if (current < target) {
                    if (target >= 1000) {
                        counter.textContent = Math.ceil(current / 1000) + 'K+';
                    } else {
                        counter.textContent = Math.ceil(current);
                    }
                    requestAnimationFrame(updateCounter);
                } else {
                    if (target >= 1000) {
                        counter.textContent = (target / 1000) + 'K+';
                    } else {
                        counter.textContent = target;
                    }
                }
            };
            updateCounter();
        };
        
        // Intersection Observer for counters
        const counterObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounter(entry.target);
                    counterObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });
        
        counters.forEach(counter => counterObserver.observe(counter));
        
        // FAQ accordion
        document.querySelectorAll('.faq-question').forEach(button => {
            button.addEventListener('click', () => {
                const item = button.parentElement;
                const isActive = item.classList.contains('active');
                document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('active'));
                if (!isActive) {
                    item.classList.add('active');
                }
            });
        });
        
        // AI examples click
        document.querySelectorAll('.ai-example').forEach(example => {
            example.addEventListener('click', () => {
                const input = document.querySelector('.ai-input');
                const text = example.textContent;
                const prompts = {
                    'Restaurant website': 'A modern restaurant website with an elegant dark theme, menu showcase with photos, online reservation system, about section with chef bio, customer reviews, and contact information with map.',
                    'Online store': 'A clean e-commerce website for selling handmade jewelry with product categories, shopping cart, customer reviews, about the artisan section, and secure checkout.',
                    'Portfolio': 'A minimalist portfolio website for a graphic designer with project gallery, case studies, skills section, testimonials, and contact form.',
                    'Landing page': 'A high-converting SaaS landing page with hero section, feature highlights, pricing table, customer testimonials, FAQ, and email signup form.'
                };
                input.value = prompts[text] || text;
            });
        });
        
        // ========================================
        // DOMAIN SEARCH – inline results (same TLDs same TLDs & pricing from DB pricing from DB)
        // ========================================
        async function searchDomains(e) {
            e.preventDefault();
            const input = document.getElementById('domainInput');
            const btn = document.getElementById('domainSearchBtn');
            const results = document.getElementById('domainResults');
            const loading = results.querySelector('.results-loading');
            const list = results.querySelector('.results-list');
            const tlds = document.getElementById('domainTLDs');
            const domain = input.value.trim().toLowerCase();
            if (!domain) {
                input.focus();
                return false;
            }
            let sld = domain.replace(/^(https?:\/\/)?(www\.)?/, '').split('.')[0];
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + (typeof window.langDomainChecking !== 'undefined' ? window.langDomainChecking : 'Searching...');
            btn.disabled = true;
            results.style.display = 'block';
            loading.style.display = 'block';
            list.innerHTML = '';
            if (tlds) tlds.style.display = 'none';
            try {
                const response = await fetch('/api/domains.php?action=search&domain=' + encodeURIComponent(sld));
                const data = await response.json();
                loading.style.display = 'none';
                if (data.success && data.results && data.results.length) {
                    const avail = (typeof window.langDomainAvailable !== 'undefined') ? window.langDomainAvailable : 'Available';
                    const taken = (typeof window.langDomainTaken !== 'undefined') ? window.langDomainTaken : 'Taken';
                    const unknown = (typeof window.langDomainCheckAtCheckout !== 'undefined') ? window.langDomainCheckAtCheckout : 'Add to cart to verify';
                    const addBtn = (typeof window.langDomainAdd !== 'undefined') ? window.langDomainAdd : 'Add';
                    list.innerHTML = data.results.map(function(result) {
                        const isAv = result.available === true;
                        const isTaken = result.available === false;
                        const isUnknown = result.available === null || result.available === undefined;
                        const statusClass = isAv ? 'available' : (isTaken ? 'taken' : 'unknown');
                        const statusText = isAv ? avail : (isTaken ? taken : unknown);
                        const statusIcon = isAv ? 'fa-check-circle' : (isTaken ? 'fa-times-circle' : 'fa-question-circle');
                        const showAdd = isAv || isUnknown;
                        return '<div class="domain-result ' + statusClass + '">' +
                            '<div><div class="domain-name">' + result.domain + '</div>' +
                            '<div class="domain-status ' + statusClass + '">' +
                            '<i class="fas ' + statusIcon + '"></i> ' + statusText + '</div></div>' +
                            '<div style="display: flex; align-items: center; gap: 16px;">' +
                            '<span class="domain-price">$' + result.price + '/yr</span>' +
                            (showAdd ? '<button type="button" class="btn-add" onclick="addToCart(\'domain\', \'' + result.domain.replace(/'/g, "\\'") + '\', ' + (result.price_raw || result.price) + ')"><i class="fas fa-cart-plus"></i> ' + addBtn + '</button>' : '') + '</div></div>';
                    }).join('');
                } else {
                    const errMsg = (typeof window.langDomainSearchError !== 'undefined') ? window.langDomainSearchError : 'Error searching domains. Please try again.';
                    list.innerHTML = '<p style="text-align: center; color: var(--text-muted);">' + errMsg + '</p>';
                }
            } catch (err) {
                loading.style.display = 'none';
                const connErr = (typeof window.langDomainConnectError !== 'undefined') ? window.langDomainConnectError : 'Error connecting to server. Please try again.';
                list.innerHTML = '<p style="text-align: center; color: var(--text-muted);">' + connErr + '</p>';
            }
            const searchLabel = (typeof window.langDomainSearch !== 'undefined') ? window.langDomainSearch : 'Search';
            btn.innerHTML = '<i class="fas fa-search"></i> ' + searchLabel;
            btn.disabled = false;
            return false;
        }
        
        // Add to cart (AJAX — inline)
        async function addToCart(type, item, price) {
            if (type === 'domain') {
                const btn = event && event.target ? event.target.closest('.btn-add') : null;
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                }
                try {
                    const res = await fetch('/pay/api/billing-api.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        credentials: 'include',
                        body: JSON.stringify({ action: 'cart.addDomain', domain: item, price: price || 0 })
                    });
                    const data = await res.json();
                    if (data.success) {
                        if (btn) btn.innerHTML = '<i class="fas fa-check"></i> Added';
                        if (typeof showToast === 'function') showToast(item + ' added to cart!', 'success');
                        if (typeof updateCartCount === 'function') updateCartCount();
                    } else {
                        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-cart-plus"></i> Add'; }
                        if (typeof showToast === 'function') showToast(data.error || 'Could not add domain', 'error');
                    }
                } catch (err) {
                    console.error('addToCart domain error:', err);
                    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-cart-plus"></i> Add'; }
                    if (typeof showToast === 'function') showToast('Error adding to cart', 'error');
                }
            } else if (type === 'product') {
                const langParam = (typeof window.billingLangParam !== 'undefined') ? window.billingLangParam : 'language=english';
                window.location.href = `/cart?a=add&pid=${item}&${langParam}`;
            }
        }
        
        // ========================================
        // AUTHENTICATION
        // ========================================
        let currentUser = null;
        
        // Check auth on load
        async function checkAuth() {
            try {
                const response = await fetch('/api/auth.php?action=check', {
                    credentials: 'include'
                });
                const data = await response.json();
                
                if (data.authenticated) {
                    currentUser = data.client;
                    updateNavForUser();
                }
            } catch (err) {
                console.error('Auth check error:', err);
            }
        }
        
        // Update nav when logged in
        function updateNavForUser() {
            const navCta = document.querySelector('.nav-cta');
            if (navCta && currentUser) {
                const initials = currentUser.name.split(' ').map(n => n[0]).join('').toUpperCase();
                navCta.innerHTML = `
                    <div class="user-menu" style="position:relative;">
                        <button class="user-menu-btn" onclick="toggleUserMenu(event)">
                            <div class="user-avatar">${initials}</div>
                            <span>${currentUser.name.split(' ')[0]}</span>
                            <i class="fas fa-chevron-down" style="font-size: 0.7rem;"></i>
                        </button>
                        <div class="user-dropdown-menu" id="userDropdown" style="display:none;position:absolute;top:100%;right:0;min-width:220px;margin-top:8px;background:#1a1a2e;border:1px solid rgba(255,255,255,0.1);border-radius:12px;padding:8px;box-shadow:0 12px 36px rgba(0,0,0,0.5);z-index:9999;">
                            <a href="/dashboard" style="display:flex;align-items:center;gap:10px;padding:10px 16px;border-radius:8px;color:#b0b0c8;text-decoration:none;font-size:0.9rem;"><i class="fas fa-home" style="width:20px;color:#00D4FF;"></i> Dashboard</a>
                            <a href="/dashboard#services" style="display:flex;align-items:center;gap:10px;padding:10px 16px;border-radius:8px;color:#b0b0c8;text-decoration:none;font-size:0.9rem;"><i class="fas fa-server" style="width:20px;color:#00D4FF;"></i> My Services</a>
                            <a href="/dashboard#domains" style="display:flex;align-items:center;gap:10px;padding:10px 16px;border-radius:8px;color:#b0b0c8;text-decoration:none;font-size:0.9rem;"><i class="fas fa-globe" style="width:20px;color:#00D4FF;"></i> My Domains</a>
                            <a href="/dashboard#invoices" style="display:flex;align-items:center;gap:10px;padding:10px 16px;border-radius:8px;color:#b0b0c8;text-decoration:none;font-size:0.9rem;"><i class="fas fa-file-invoice" style="width:20px;color:#00D4FF;"></i> Invoices</a>
                            <a href="#" onclick="handleLogout()" style="display:flex;align-items:center;gap:10px;padding:10px 16px;border-radius:8px;color:#b0b0c8;text-decoration:none;font-size:0.9rem;border-top:1px solid rgba(255,255,255,0.1);margin-top:4px;"><i class="fas fa-sign-out-alt" style="width:20px;color:#ef4444;"></i> <?php echo L('sign_out'); ?></a>
                        </div>
                    </div>
                `;
            }
        }
        
        function toggleUserMenu(e) {
            if (e) e.stopPropagation();
            const dropdown = document.getElementById('userDropdown');
            if (!dropdown) return;
            const isOpen = dropdown.style.display === 'block';
            dropdown.style.display = isOpen ? 'none' : 'block';
        }
        
        // Modal functions
        function openModal(id) {
            document.getElementById(id).classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
            document.body.style.overflow = '';
        }
        
        function switchModal(toId) {
            document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('active'));
            openModal(toId);
        }
        
        // Close modal on outside click
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeModal(modal.id);
                }
            });
        });
        
        // Show/hide forgot password form within login modal
        function showForgotPassword() {
            document.getElementById('loginForm').style.display = 'none';
            document.getElementById('loginModalLinks').style.display = 'none';
            // Hide social login and editor link in login modal
            document.querySelectorAll('#loginModal .login-divider, #loginModal .social-login-buttons').forEach(el => { el.dataset.prevDisplay = el.style.display; el.style.display = 'none'; });
            const editorLink = document.querySelector('#loginModal div[style*="text-align:center"]');
            if (editorLink) { editorLink.dataset.prevDisplay = editorLink.style.display; editorLink.style.display = 'none'; }
            document.getElementById('forgotPasswordForm').style.display = 'block';
            document.getElementById('forgotError').style.display = 'none';
            document.getElementById('forgotSuccess').style.display = 'none';
        }

        function showLoginForm() {
            document.getElementById('forgotPasswordForm').style.display = 'none';
            document.getElementById('loginForm').style.display = '';
            document.getElementById('loginModalLinks').style.display = '';
            document.querySelectorAll('#loginModal .login-divider, #loginModal .social-login-buttons').forEach(el => el.style.display = el.dataset.prevDisplay || '');
            const editorLink = document.querySelector('#loginModal div[style*="text-align:center"]');
            if (editorLink) editorLink.style.display = editorLink.dataset.prevDisplay || '';
        }

        // Forgot password handler
        async function handleForgotPassword(e) {
            e.preventDefault();
            const form = e.target;
            const btn = document.getElementById('forgotBtn');
            const errorDiv = document.getElementById('forgotError');
            const successDiv = document.getElementById('forgotSuccess');

            const formData = new FormData(form);
            formData.append('action', 'forgot');

            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            btn.disabled = true;
            errorDiv.style.display = 'none';
            successDiv.style.display = 'none';

            try {
                const response = await fetch('/api/auth.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'include'
                });
                const data = await response.json();

                if (data.success) {
                    successDiv.textContent = 'If an account exists with that email, you\'ll receive a reset link.';
                    successDiv.style.display = 'block';
                    form.style.display = 'none';
                } else {
                    errorDiv.textContent = data.error || 'Something went wrong. Please try again.';
                    errorDiv.style.display = 'block';
                }
            } catch (err) {
                errorDiv.textContent = 'Connection error. Please try again.';
                errorDiv.style.display = 'block';
            }

            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Reset Link';
            btn.disabled = false;
            return false;
        }

        // Reset password handler
        async function handleResetPassword(e) {
            e.preventDefault();
            const form = e.target;
            const btn = document.getElementById('resetBtn');
            const errorDiv = document.getElementById('resetError');
            const successDiv = document.getElementById('resetSuccess');

            const formData = new FormData(form);
            formData.append('action', 'reset');

            // Client-side password match check
            const pw = form.querySelector('input[name="password"]').value;
            const pwc = form.querySelector('input[name="password_confirm"]').value;
            if (pw !== pwc) {
                errorDiv.textContent = 'Passwords do not match.';
                errorDiv.style.display = 'block';
                return false;
            }
            if (pw.length < 8) {
                errorDiv.textContent = 'Password must be at least 8 characters.';
                errorDiv.style.display = 'block';
                return false;
            }

            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Resetting...';
            btn.disabled = true;
            errorDiv.style.display = 'none';
            successDiv.style.display = 'none';

            try {
                const response = await fetch('/api/auth.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'include'
                });
                const data = await response.json();

                if (data.success) {
                    successDiv.textContent = 'Password reset successfully! You can now log in.';
                    successDiv.style.display = 'block';
                    form.style.display = 'none';
                    // After 2 seconds, switch to login modal
                    setTimeout(() => {
                        closeModal('resetPasswordModal');
                        openModal('loginModal');
                        // Clean URL param
                        const url = new URL(window.location);
                        url.searchParams.delete('reset');
                        window.history.replaceState({}, '', url);
                    }, 2500);
                } else {
                    errorDiv.textContent = data.error || 'Reset failed. Please try again.';
                    errorDiv.style.display = 'block';
                }
            } catch (err) {
                errorDiv.textContent = 'Connection error. Please try again.';
                errorDiv.style.display = 'block';
            }

            btn.innerHTML = '<i class="fas fa-check"></i> Reset Password';
            btn.disabled = false;
            return false;
        }

        // Login handler
        async function handleLogin(e) {
            e.preventDefault();
            
            const form = e.target;
            const btn = document.getElementById('loginBtn');
            const errorDiv = document.getElementById('loginError');
            
            const formData = new FormData(form);
            formData.append('action', 'login');
            
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing in...';
            btn.disabled = true;
            errorDiv.style.display = 'none';
            
            try {
                const response = await fetch('/api/auth.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'include'
                });
                const data = await response.json();
                
                if (data.success) {
                    currentUser = data.client;
                    closeModal('loginModal');
                    updateNavForUser();
                    // Redirect to dashboard or refresh
                    window.location.href = '/dashboard';
                } else {
                    errorDiv.textContent = data.error || 'Login failed';
                    errorDiv.style.display = 'block';
                    // If rate limited, show retry info
                    if (data.retry_after) {
                        errorDiv.textContent += ' (retry in ' + data.retry_after + 's)';
                    }
                }
            } catch (err) {
                errorDiv.textContent = 'Connection error. Please try again.';
                errorDiv.style.display = 'block';
            }
            
            btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Sign In';
            btn.disabled = false;
            
            return false;
        }
        
        // Register handler
        async function handleRegister(e) {
            e.preventDefault();
            
            const form = e.target;
            const btn = document.getElementById('registerBtn');
            const errorDiv = document.getElementById('registerError');
            
            const formData = new FormData(form);
            formData.append('action', 'register');
            
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating account...';
            btn.disabled = true;
            errorDiv.style.display = 'none';
            
            try {
                const response = await fetch('/api/auth.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'include'
                });
                const data = await response.json();
                
                if (data.success) {
                    currentUser = data.client;
                    closeModal('registerModal');
                    updateNavForUser();
                    window.location.href = '/dashboard';
                } else {
                    errorDiv.textContent = data.error || 'Registration failed';
                    errorDiv.style.display = 'block';
                }
            } catch (err) {
                errorDiv.textContent = 'Connection error. Please try again.';
                errorDiv.style.display = 'block';
            }
            
            btn.innerHTML = '<i class="fas fa-user-plus"></i> Create Account';
            btn.disabled = false;
            
            return false;
        }
        
        // Logout handler
        async function handleLogout() {
            try {
                await fetch('/api/auth.php?action=logout', { credentials: 'include' });
            } catch (err) {}
            
            currentUser = null;
            window.location.reload();
        }
        
        // Update login buttons to open modal
        document.addEventListener('DOMContentLoaded', function() {
            // Check if user is logged in
            checkAuth();
            
            // Auto-redirect to login page when ?login=1
            if (new URLSearchParams(window.location.search).get('login') === '1' && !currentUser) {
                window.location.href = '/login';
            }

            // Auto-open reset password modal when ?reset=TOKEN is in URL
            const resetToken = new URLSearchParams(window.location.search).get('reset');
            if (resetToken && resetToken.length === 64) {
                document.getElementById('resetToken').value = resetToken;
                openModal('resetPasswordModal');
            }
            
            // Redirect login links to dedicated login page (no modal)
            document.querySelectorAll('a[href*="clientarea.php"], a[href*="login"]').forEach(link => {
                if (!link.closest('.dropdown-menu') && !link.closest('.modal-box')) {
                    link.addEventListener('click', function(e) {
                        if (!currentUser) {
                            e.preventDefault();
                            window.location.href = '/login';
                        }
                    });
                }
            });
            
            // Redirect register links to dedicated register page (no modal)
            document.querySelectorAll('a[href*="register.php"]').forEach(link => {
                if (!link.closest('.modal-box')) {
                    link.addEventListener('click', function(e) {
                        if (!currentUser) {
                            e.preventDefault();
                            window.location.href = '/register';
                        }
                    });
                }
            });
        });
        
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                if (this.getAttribute('href') === '#') return;
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });
        
        // Social Proof Notifications
        const notifications = [
            { name: 'John D.', location: 'New York, USA', action: 'just launched', site: 'techstartup.io' },
            { name: 'Sarah M.', location: 'London, UK', action: 'just created', site: 'beautyshop.co' },
            { name: 'Mike R.', location: 'Toronto, CA', action: 'just built', site: 'portfolio.dev' },
            { name: 'Emma L.', location: 'Sydney, AU', action: 'just launched', site: 'fitnesshub.com' },
            { name: 'David K.', location: 'Berlin, DE', action: 'just created', site: 'agency.io' },
            { name: 'Lisa T.', location: 'Paris, FR', action: 'just built', site: 'boutique.shop' },
            { name: 'Alex P.', location: 'Miami, USA', action: 'just launched', site: 'realestate.pro' },
            { name: 'Nina S.', location: 'Dubai, UAE', action: 'just created', site: 'luxurytravel.com' }
        ];
        
        let notifIndex = 0;
        const notifPopup = document.getElementById('socialProof');
        
        function showNotification() {
            if (!notifPopup) return;
            const notif = notifications[notifIndex];
            const elName = document.getElementById('proofName');
            const elLoc  = document.getElementById('proofLocation');
            const elAct  = document.getElementById('proofAction');
            const elSite = document.getElementById('proofSite');
            if (elName) elName.textContent = notif.name;
            if (elLoc)  elLoc.textContent = notif.location;
            if (elAct)  elAct.textContent = notif.action;
            if (elSite) elSite.textContent = notif.site;
            
            notifPopup.classList.add('show');
            
            setTimeout(() => {
                notifPopup.classList.remove('show');
            }, 5000);
            
            notifIndex = (notifIndex + 1) % notifications.length;
        }
        
        // Show first notification after 8 seconds, then every 15 seconds
        if (notifPopup) {
            setTimeout(() => {
                showNotification();
                setInterval(showNotification, 15000);
            }, 8000);
        }
        
        // Close notification on click
        document.querySelector('.proof-close')?.addEventListener('click', () => {
            if (notifPopup) notifPopup.classList.remove('show');
        });
        
        // Mobile Menu Toggle
        const mobileToggle = document.getElementById('mobileToggle');
        const mobileClose = document.getElementById('mobileClose');
        const mobileMenu = document.getElementById('mobileMenu');
        const mobileOverlay = document.getElementById('mobileOverlay');
        
        function openMobileMenu() {
            mobileMenu.classList.add('active');
            mobileOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeMobileMenu() {
            mobileMenu.classList.remove('active');
            mobileOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }
        
        mobileToggle?.addEventListener('click', openMobileMenu);
        mobileClose?.addEventListener('click', closeMobileMenu);
        mobileOverlay?.addEventListener('click', closeMobileMenu);
        
        // Close mobile menu on link click
        mobileMenu?.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', closeMobileMenu);
        });

        // Mobile accordion
        mobileMenu?.querySelectorAll('.mob-accordion-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const panel = this.nextElementSibling;
                const chev = this.querySelector('.mob-chev');
                const isOpen = panel.style.maxHeight && panel.style.maxHeight !== '0px';
                // close others
                mobileMenu.querySelectorAll('.mob-accordion-panel').forEach(p => { p.style.maxHeight = '0px'; });
                mobileMenu.querySelectorAll('.mob-chev').forEach(c => { c.style.transform = 'rotate(0deg)'; });
                if (!isOpen) {
                    panel.style.maxHeight = panel.scrollHeight + 'px';
                    if (chev) chev.style.transform = 'rotate(180deg)';
                }
            });
        });
        
        // Close on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && mobileMenu?.classList.contains('active')) {
                closeMobileMenu();
            }
        });
    </script>
    
    <!-- Alfred Widget loaded via site-header.inc.php -->

    <div id="socialProof" class="social-proof-popup">
        <button type="button" class="proof-close" aria-label="<?php echo L('modal_close'); ?>">&times;</button>
        <div class="proof-icon">
            <i class="fas fa-rocket"></i>
        </div>
        <div class="proof-content">
            <p><strong id="proofName">Someone</strong> from <span id="proofLocation">somewhere</span></p>
            <p class="proof-action"><span id="proofAction">just launched</span> <strong id="proofSite">website.com</strong></p>
            <p class="proof-time">Just now</p>
        </div>
    </div>
    
    <style>
        /* Social Proof Popup */
        .social-proof-popup {
            position: fixed;
            bottom: 24px;
            left: 24px;
            background: rgba(26, 26, 46, 0.98);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 16px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4), 0 0 40px rgba(16, 185, 129, 0.1);
            z-index: 9999;
            transform: translateX(-120%);
            opacity: 0;
            transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            max-width: 320px;
        }
        
        .social-proof-popup.show {
            transform: translateX(0);
            opacity: 1;
        }
        
        .proof-close {
            position: absolute;
            top: 8px;
            right: 12px;
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 1.2rem;
            cursor: pointer;
            opacity: 0.6;
            transition: opacity 0.3s;
        }
        
        .proof-close:hover {
            opacity: 1;
        }
        
        .proof-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(0, 168, 255, 0.2));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: #10b981;
            flex-shrink: 0;
        }
        
        .proof-content p {
            margin: 0;
            font-size: 0.85rem;
            color: var(--text-muted);
            line-height: 1.4;
        }
        
        .proof-content p:first-child {
            color: var(--text);
        }
        
        .proof-content strong {
            color: #fff;
        }
        
        .proof-action {
            margin-top: 2px !important;
        }
        
        .proof-action strong {
            color: var(--cyan);
        }
        
        .proof-time {
            font-size: 0.75rem !important;
            color: var(--success) !important;
            margin-top: 4px !important;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .proof-time::before {
            content: '';
            width: 6px;
            height: 6px;
            background: var(--success);
            border-radius: 50%;
            animation: livePulse 2s infinite;
        }
        
        @media (max-width: 768px) {
            .social-proof-popup {
                left: 12px;
                right: 12px;
                bottom: 12px;
                max-width: none;
            }
        }
    </style>

    <script src="/assets/js/pwa-manager.js" defer></script>
    <script src="/assets/js/draft-guard.js" defer></script>
    <script src="/assets/js/alfred-ws.js" defer></script>
<?php if (!empty($_SESSION['client_id']) || !empty($_SESSION['uid'])): ?>
    <!-- Emergency stealth sign-out: subtle button + Ctrl+Shift+Q -->
    <a href="/logout.php?emergency=1" id="eBtn" title="Quick exit" style="position:fixed;bottom:8px;right:8px;width:28px;height:28px;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.04);border-radius:6px;color:rgba(255,255,255,.15);font-size:11px;text-decoration:none;z-index:99999;cursor:pointer;transition:opacity .2s;" onmouseenter="this.style.opacity='.6'" onmouseleave="this.style.opacity='1'"><i class="fas fa-power-off"></i></a>
    <script>
    (function() {
        var logoutKey = 'root:logout';
        var authCheckUrl = '/api/auth.php?action=check';
        var authCheckBusy = false;
        var lastAuthCheckAt = 0;
        var path = window.location.pathname || '/';
        var sensitivePath = /\/(commander|command|military-hq|supreme-admin|enterprise-admin|investor-admin|mission-control|conference-room|team-chat|finance-dashboard|fleet-dashboard|commander-organizer|commander-vault-unlock|agent-orchestrator|voice(?:\.php)?|dashboard|account)/i.test(path);
        var authCheckIntervalMs = sensitivePath ? 15000 : 60000;
        var authCheckMinGapMs = 5000;
        var heartbeatId = null;

        function handleRemoteLogout() {
            if (window.location.pathname === '/login.php' || window.location.pathname === '/logout.php') {
                return;
            }
            window.location.replace('/login.php?logged_out=1');
        }

        function triggerEmergencyExit() {
            window.location.replace('/logout.php?emergency=1');
        }

        function verifySession() {
            var now = Date.now();
            if (authCheckBusy || document.hidden || (now - lastAuthCheckAt) < authCheckMinGapMs) {
                return;
            }
            authCheckBusy = true;
            lastAuthCheckAt = now;
            fetch(authCheckUrl, {
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data || data.authenticated !== true) {
                    handleRemoteLogout();
                }
            })
            .catch(function() {
                // Ignore transient failures; the next focus/visibility event will re-check.
            })
            .finally(function() {
                authCheckBusy = false;
            });
        }

        function startHeartbeat() {
            if (heartbeatId) {
                clearInterval(heartbeatId);
            }
            heartbeatId = window.setInterval(function() {
                if (!document.hidden) {
                    verifySession();
                }
            }, authCheckIntervalMs);
        }

        var exitBtn = document.getElementById('eBtn');
        if (exitBtn) {
            exitBtn.addEventListener('click', function(e) {
                e.preventDefault();
                triggerEmergencyExit();
            });
        }

        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.shiftKey && e.key === 'Q') {
                e.preventDefault();
                triggerEmergencyExit();
            }
        });

        window.addEventListener('storage', function(e) {
            if (e.key === logoutKey && e.newValue) {
                handleRemoteLogout();
            }
        });

        window.addEventListener('focus', verifySession);
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                verifySession();
            }
        });

        startHeartbeat();
        window.setTimeout(verifySession, sensitivePath ? 1000 : 3000);
    })();
    </script>
<?php endif; ?>

<!-- Floating Call Alfred CTA -->
<div id="alfCallCta" style="position:fixed;bottom:24px;left:24px;z-index:9998;display:flex;align-items:center;gap:10px;padding:10px 18px 10px 12px;background:linear-gradient(135deg,#7D00FF,#00D4FF);border-radius:50px;box-shadow:0 6px 24px rgba(125,0,255,.4);cursor:pointer;transition:all .3s;text-decoration:none;color:#fff;font-family:'Space Grotesk',sans-serif;" onmouseenter="this.style.transform='translateY(-3px)';this.style.boxShadow='0 10px 32px rgba(125,0,255,.6)'" onmouseleave="this.style.transform='';this.style.boxShadow='0 6px 24px rgba(125,0,255,.4)'">
    <span style="width:36px;height:36px;background:rgba(255,255,255,.2);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.95rem;"><i class="fas fa-phone-alt"></i></span>
    <span style="font-weight:600;font-size:.85rem;white-space:nowrap;">Call Alfred</span>
</div>
<script>
(function(){
    var c=document.getElementById("alfCallCta");
    if(!c)return;
    c.addEventListener("click",function(){window.location.href="tel:+18334674836";});
    var last=0;
    window.addEventListener("scroll",function(){
        var st=window.pageYOffset||document.documentElement.scrollTop;
        if(st>last&&st>300){c.style.transform="translateY(100px)";c.style.opacity="0";}
        else{c.style.transform="";c.style.opacity="1";}
        last=st<=0?0:st;
    },{passive:true});
})();
</script>
<script>
if('serviceWorker' in navigator){navigator.serviceWorker.register('/sw.js').catch(function(){});}
</script>
</body>
</html>
