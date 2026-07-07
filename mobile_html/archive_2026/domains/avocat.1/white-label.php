<?php
$page_title = 'White-Label Configuration - Alfred AI';
$page_description = 'Deploy Alfred AI under your own brand with full white-label customization. Custom domains, branding, voice, and more.';
$page_canonical = 'https://gositeme.com/white-label';
$page_og_title = 'White-Label Alfred AI — Your Brand, Our AI';
$page_og_description = 'Deploy Alfred AI under your own brand with full white-label customization.';
require_once __DIR__ . '/includes/site-header.inc.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$is_logged_in = !empty($_SESSION['logged_in']) && !empty($_SESSION['client_id']);
$client_id    = $is_logged_in ? (int)$_SESSION['client_id'] : 0;
$client_name  = $_SESSION['client_name'] ?? '';
$client_email = $_SESSION['client_email'] ?? '';
?>

<style>
/* ===== White-Label Page Styles ===== */
:root {
    --wl-bg: #0a0a14;
    --wl-surface: #12121e;
    --wl-surface-2: #1a1a2e;
    --wl-border: rgba(255,255,255,0.08);
    --wl-accent: #6c5ce7;
    --wl-accent-light: #a29bfe;
    --wl-green: #00b894;
    --wl-blue: #0984e3;
    --wl-orange: #fdcb6e;
    --wl-red: #d63031;
    --wl-text: #e8e8f0;
    --wl-text-muted: #8a8a9a;
    --wl-radius: 16px;
    --wl-radius-sm: 12px;
    --wl-transition: .3s cubic-bezier(.4,0,.2,1);
    --wl-shadow: 0 4px 24px rgba(0,0,0,0.3);
}

.wl-page { background: var(--wl-bg); color: var(--wl-text); overflow-x: hidden; }
.wl-page *, .wl-page *::before, .wl-page *::after { box-sizing: border-box; }
.wl-container { max-width: 1200px; margin: 0 auto; padding: 0 24px; }
.wl-section { padding: 80px 0; }

/* ---- Hero ---- */
.wl-hero {
    padding: 100px 0 60px;
    text-align: center;
    background: linear-gradient(135deg, #0a0a14 0%, #1a1033 50%, #0a0a14 100%);
    position: relative; overflow: hidden;
}
.wl-hero::before {
    content: '';
    position: absolute; top: -50%; left: -50%;
    width: 200%; height: 200%;
    background: radial-gradient(circle at 40% 40%, rgba(108,92,231,0.1) 0%, transparent 50%),
                radial-gradient(circle at 60% 60%, rgba(0,184,148,0.06) 0%, transparent 50%);
    animation: wlHeroPulse 10s ease-in-out infinite alternate;
    pointer-events: none;
}
@keyframes wlHeroPulse {
    0% { transform: scale(1) rotate(0deg); }
    100% { transform: scale(1.04) rotate(1.5deg); }
}
.wl-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 18px; border-radius: 50px; font-size: .78rem; font-weight: 600;
    letter-spacing: .5px; text-transform: uppercase;
    background: linear-gradient(135deg, rgba(108,92,231,.12), rgba(9,132,227,.12));
    color: var(--wl-accent-light);
    border: 1px solid rgba(108,92,231,.2);
    position: relative; z-index: 1;
}
.wl-hero h1 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: clamp(2.2rem, 5vw, 3.6rem); font-weight: 800;
    margin: 16px 0;
    background: linear-gradient(135deg, #6c5ce7, #0984e3);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text;
    position: relative; z-index: 1;
}
.wl-hero p {
    font-size: 1.15rem; color: var(--wl-text-muted);
    max-width: 600px; margin: 0 auto 30px;
    position: relative; z-index: 1; line-height: 1.6;
}
.wl-btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 14px 32px; border: none; border-radius: 50px;
    font-size: 1rem; font-weight: 700; cursor: pointer;
    text-decoration: none; font-family: inherit;
    transition: all var(--wl-transition);
}
.wl-btn-primary {
    background: linear-gradient(135deg, #6c5ce7, #0984e3); color: #fff;
    box-shadow: 0 4px 20px rgba(108,92,231,0.35);
}
.wl-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 32px rgba(108,92,231,0.5);
    color: #fff; text-decoration: none;
}
.wl-btn-ghost {
    background: transparent; border: 2px solid rgba(255,255,255,0.12);
    color: var(--wl-text);
}
.wl-btn-ghost:hover {
    border-color: var(--wl-accent); color: var(--wl-accent-light);
    text-decoration: none;
}
.wl-btn-sm { padding: 10px 22px; font-size: .88rem; }

/* ---- Marketing Features Grid ---- */
.wl-features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 24px; margin: 0 0 60px;
}
.wl-feature-card {
    background: var(--wl-surface);
    border: 1px solid var(--wl-border);
    border-radius: var(--wl-radius);
    padding: 32px;
    text-align: center;
    transition: all var(--wl-transition);
}
.wl-feature-card:hover {
    border-color: rgba(108,92,231,0.3);
    transform: translateY(-4px);
    box-shadow: 0 12px 40px rgba(0,0,0,0.4);
}
.wl-feature-icon {
    width: 64px; height: 64px;
    border-radius: 18px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.6rem; color: #fff; margin: 0 auto 20px;
    background: linear-gradient(135deg, var(--wl-accent), var(--wl-blue));
}
.wl-feature-card h3 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.15rem; font-weight: 700; margin: 0 0 10px; color: #fff;
}
.wl-feature-card p {
    color: var(--wl-text-muted); font-size: .92rem; line-height: 1.55; margin: 0;
}

/* ---- Pricing Box ---- */
.wl-pricing {
    background: var(--wl-surface);
    border: 1px solid rgba(108,92,231,0.2);
    border-radius: var(--wl-radius);
    padding: 48px;
    text-align: center;
    max-width: 600px;
    margin: 0 auto;
}
.wl-pricing h3 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.5rem; font-weight: 800; color: #fff; margin: 0 0 8px;
}
.wl-pricing .price {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 2.5rem; font-weight: 800; margin: 16px 0;
    background: linear-gradient(135deg, var(--wl-accent-light), var(--wl-green));
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text;
}
.wl-pricing .price span { font-size: 1rem; font-weight: 400; }
.wl-pricing p { color: var(--wl-text-muted); margin: 0 0 24px; }
.wl-pricing-features {
    list-style: none; padding: 0; margin: 0 0 30px; text-align: left;
}
.wl-pricing-features li {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 0; color: var(--wl-text); font-size: .92rem;
}
.wl-pricing-features li i { color: var(--wl-green); width: 18px; text-align: center; }

/* ════════ Config Panel (logged-in) ════════ */
.wl-config { padding: 40px 0 80px; }
.wl-config-header {
    text-align: center; margin-bottom: 40px;
}
.wl-config-header h1 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: clamp(1.6rem, 3.5vw, 2.4rem); font-weight: 800;
    margin: 12px 0 8px;
    background: linear-gradient(135deg, #6c5ce7, #0984e3);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text;
}
.wl-config-header p { color: var(--wl-text-muted); font-size: 1.05rem; }

/* Tabs */
.wl-tabs {
    display: flex; justify-content: center; gap: 4px;
    margin-bottom: 32px;
    border-bottom: 1px solid var(--wl-border);
    padding-bottom: 0;
    flex-wrap: wrap;
}
.wl-config-tab {
    padding: 12px 24px;
    background: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    color: var(--wl-text-muted);
    font-size: .92rem; font-weight: 600;
    cursor: pointer; font-family: inherit;
    transition: all var(--wl-transition);
    display: flex; align-items: center; gap: 8px;
}
.wl-config-tab:hover { color: var(--wl-text); }
.wl-config-tab.active {
    color: var(--wl-accent-light);
    border-bottom-color: var(--wl-accent);
}

/* Tab Panels */
.wl-tab-panel { display: none; }
.wl-tab-panel.active { display: block; }

.wl-panel-card {
    background: var(--wl-surface);
    border: 1px solid var(--wl-border);
    border-radius: var(--wl-radius);
    padding: 32px;
    margin-bottom: 24px;
}
.wl-panel-card h3 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.2rem; font-weight: 700; margin: 0 0 20px; color: #fff;
    display: flex; align-items: center; gap: 10px;
}
.wl-panel-card h3 i { color: var(--wl-accent-light); }

/* Form fields */
.wl-field { margin-bottom: 20px; }
.wl-field label {
    display: block; font-size: .85rem; font-weight: 600;
    color: var(--wl-text); margin-bottom: 6px;
}
.wl-field label small { color: var(--wl-text-muted); font-weight: 400; }
.wl-field input[type="text"],
.wl-field input[type="email"],
.wl-field input[type="url"],
.wl-field input[type="color"],
.wl-field select,
.wl-field textarea {
    width: 100%;
    padding: 12px 16px;
    background: var(--wl-bg);
    border: 1px solid var(--wl-border);
    border-radius: var(--wl-radius-sm);
    color: var(--wl-text);
    font-size: .95rem; font-family: inherit;
    outline: none;
    transition: border-color var(--wl-transition);
}
.wl-field input:focus,
.wl-field select:focus,
.wl-field textarea:focus {
    border-color: var(--wl-accent);
    box-shadow: 0 0 0 3px rgba(108,92,231,0.12);
}
.wl-field textarea { resize: vertical; min-height: 120px; font-family: 'SF Mono', monospace, inherit; font-size: .85rem; }
.wl-field input[type="color"] {
    width: 60px; height: 44px; padding: 4px; cursor: pointer;
    border-radius: 8px;
}
.wl-color-group {
    display: flex; align-items: center; gap: 12px;
}
.wl-color-group input[type="text"] { flex: 1; }
.wl-color-group input[type="color"] { width: 60px; flex-shrink: 0; }

/* Preview */
.wl-preview-box {
    background: var(--wl-bg);
    border: 1px solid var(--wl-border);
    border-radius: var(--wl-radius);
    padding: 24px;
    min-height: 200px;
}
.wl-preview-nav {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 16px;
    border-radius: var(--wl-radius-sm);
    margin-bottom: 16px;
}
.wl-preview-logo {
    width: 36px; height: 36px;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-weight: 800; color: #fff; font-size: .9rem;
}
.wl-preview-brand { font-weight: 700; color: #fff; }
.wl-preview-body {
    padding: 20px;
    border-radius: var(--wl-radius-sm);
    border: 1px dashed rgba(255,255,255,0.1);
}
.wl-preview-btn {
    display: inline-block;
    padding: 10px 24px;
    border-radius: 50px;
    color: #fff; font-weight: 600;
    margin-top: 12px;
}

/* Status badges */
.wl-status {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 14px; border-radius: 50px;
    font-size: .8rem; font-weight: 600;
}
.wl-status-verified { background: rgba(0,184,148,0.15); color: var(--wl-green); }
.wl-status-pending { background: rgba(253,203,110,0.15); color: var(--wl-orange); }
.wl-status-unverified { background: rgba(214,48,49,0.15); color: var(--wl-red); }

/* DNS box */
.wl-dns-instructions {
    background: var(--wl-bg);
    border: 1px solid var(--wl-border);
    border-radius: var(--wl-radius-sm);
    padding: 20px;
    margin-top: 16px;
}
.wl-dns-instructions h4 {
    font-size: .95rem; color: var(--wl-accent-light); margin: 0 0 12px;
}
.wl-dns-table {
    width: 100%; border-collapse: collapse;
}
.wl-dns-table th, .wl-dns-table td {
    text-align: left; padding: 8px 12px;
    border-bottom: 1px solid var(--wl-border);
    font-size: .85rem;
}
.wl-dns-table th { color: var(--wl-text-muted); font-weight: 600; }
.wl-dns-table td { color: var(--wl-text); font-family: 'SF Mono', monospace; }

/* Feature toggles */
.wl-toggle-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 0;
    border-bottom: 1px solid var(--wl-border);
}
.wl-toggle-row:last-child { border-bottom: none; }
.wl-toggle-info h4 {
    font-size: .95rem; font-weight: 600; color: var(--wl-text); margin: 0 0 2px;
}
.wl-toggle-info p { font-size: .82rem; color: var(--wl-text-muted); margin: 0; }
.wl-switch {
    position: relative; width: 48px; height: 26px;
}
.wl-switch input { opacity: 0; width: 0; height: 0; }
.wl-switch .slider {
    position: absolute; inset: 0; cursor: pointer;
    background: rgba(255,255,255,0.1);
    border-radius: 26px;
    transition: var(--wl-transition);
}
.wl-switch .slider::before {
    content: '';
    position: absolute; width: 20px; height: 20px;
    left: 3px; top: 3px;
    background: #fff; border-radius: 50%;
    transition: var(--wl-transition);
}
.wl-switch input:checked + .slider { background: var(--wl-accent); }
.wl-switch input:checked + .slider::before { transform: translateX(22px); }

/* Save bar */
.wl-save-bar {
    position: sticky; bottom: 0;
    background: rgba(18,18,30,0.95);
    backdrop-filter: blur(12px);
    border-top: 1px solid var(--wl-border);
    padding: 16px 0;
    z-index: 50;
}
.wl-save-bar-inner {
    display: flex; align-items: center; justify-content: space-between;
    gap: 16px;
}
.wl-save-msg {
    font-size: .9rem; font-weight: 600;
}
.wl-save-msg.success { color: var(--wl-green); }
.wl-save-msg.error { color: var(--wl-red); }

/* Responsive */
@media (max-width: 768px) {
    .wl-tabs { gap: 0; }
    .wl-config-tab { padding: 10px 14px; font-size: .82rem; }
    .wl-panel-card { padding: 20px; }
    .wl-color-group { flex-wrap: wrap; }
}
</style>

<div class="wl-page">

<?php if (!$is_logged_in): ?>
    <!-- ═══════════ MARKETING PAGE (NOT LOGGED IN) ═══════════ -->

    <!-- Hero -->
    <section class="wl-hero">
        <div class="wl-container">
            <span class="wl-badge"><i class="fas fa-palette"></i> Enterprise+</span>
            <h1>Your Brand, Our AI</h1>
            <p>Deploy Alfred AI under your own brand with full white-label customization. Your logo, your colors, your domain — powered by our AI infrastructure.</p>
            <div style="display:flex; justify-content:center; gap:16px; flex-wrap:wrap; position:relative; z-index:1;">
                <a href="/pricing.php" class="wl-btn wl-btn-primary"><i class="fas fa-rocket"></i> Upgrade to Enterprise+</a>
                <a href="/enterprise.php" class="wl-btn wl-btn-ghost"><i class="fas fa-building"></i> Learn More</a>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="wl-section">
        <div class="wl-container">
            <div style="text-align:center; margin-bottom:48px;">
                <h2 style="font-family:'Space Grotesk',sans-serif; font-size:clamp(1.6rem,3.5vw,2.4rem); font-weight:800; background:linear-gradient(135deg,#6c5ce7,#0984e3); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; margin:0 0 12px;">
                    Complete White-Label Solution
                </h2>
                <p style="color:var(--wl-text-muted); font-size:1.05rem; max-width:600px; margin:0 auto;">
                    Everything you need to deploy Alfred AI as your own product.
                </p>
            </div>

            <div class="wl-features-grid">
                <div class="wl-feature-card">
                    <div class="wl-feature-icon"><i class="fas fa-globe"></i></div>
                    <h3>Custom Domain</h3>
                    <p>Deploy on your own domain (e.g., ai.yourbrand.com) with automatic SSL and DNS verification.</p>
                </div>
                <div class="wl-feature-card">
                    <div class="wl-feature-icon" style="background:linear-gradient(135deg,#00b894,#55efc4);"><i class="fas fa-paint-brush"></i></div>
                    <h3>Full Branding</h3>
                    <p>Custom logo, colors, fonts, and CSS. Your customers will never know it's powered by Alfred.</p>
                </div>
                <div class="wl-feature-card">
                    <div class="wl-feature-icon" style="background:linear-gradient(135deg,#e17055,#fdcb6e);"><i class="fas fa-microphone-alt"></i></div>
                    <h3>Custom Voice</h3>
                    <p>Personalized greetings, hold music, IVR prompts, and company name pronunciation.</p>
                </div>
                <div class="wl-feature-card">
                    <div class="wl-feature-icon" style="background:linear-gradient(135deg,#0984e3,#74b9ff);"><i class="fas fa-tachometer-alt"></i></div>
                    <h3>White-Label Dashboard</h3>
                    <p>A fully branded management dashboard your clients can use to manage their AI assistants.</p>
                </div>
                <div class="wl-feature-card">
                    <div class="wl-feature-icon" style="background:linear-gradient(135deg,#fd79a8,#e84393);"><i class="fas fa-envelope"></i></div>
                    <h3>Custom Emails</h3>
                    <p>Branded email templates for welcome messages, notifications, and alerts.</p>
                </div>
                <div class="wl-feature-card">
                    <div class="wl-feature-icon" style="background:linear-gradient(135deg,#636e72,#b2bec3);"><i class="fas fa-sliders-h"></i></div>
                    <h3>Feature Control</h3>
                    <p>Toggle which features are visible, rename them, and disable modules you don't need.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing -->
    <section class="wl-section" style="background:var(--wl-surface);">
        <div class="wl-container">
            <div class="wl-pricing">
                <span class="wl-badge" style="margin-bottom:16px;"><i class="fas fa-crown"></i> Enterprise+</span>
                <h3>White-Label Plan</h3>
                <div class="price">$99<span>/mo+</span></div>
                <p>Available on Enterprise+ plans. Everything you need to resell Alfred AI.</p>
                <ul class="wl-pricing-features">
                    <li><i class="fas fa-check-circle"></i> Custom domain with SSL</li>
                    <li><i class="fas fa-check-circle"></i> Full brand customization</li>
                    <li><i class="fas fa-check-circle"></i> Custom voice and IVR</li>
                    <li><i class="fas fa-check-circle"></i> White-label dashboard</li>
                    <li><i class="fas fa-check-circle"></i> Branded email templates</li>
                    <li><i class="fas fa-check-circle"></i> Feature toggles & renaming</li>
                    <li><i class="fas fa-check-circle"></i> Priority support</li>
                    <li><i class="fas fa-check-circle"></i> 99.9% uptime SLA</li>
                </ul>
                <a href="/pricing.php" class="wl-btn wl-btn-primary" style="width:100%; justify-content:center;"><i class="fas fa-rocket"></i> Upgrade to Enterprise+</a>
            </div>
        </div>
    </section>

<?php else: ?>
    <!-- ═══════════ CONFIGURATION PANEL (LOGGED IN) ═══════════ -->

    <section class="wl-config">
        <div class="wl-container">
            <div class="wl-config-header">
                <span class="wl-badge"><i class="fas fa-palette"></i> White-Label</span>
                <h1>White-Label Configuration</h1>
                <p>Customize Alfred AI to match your brand. Changes apply to your white-label deployment.</p>
            </div>

            <!-- Tabs -->
            <div class="wl-tabs">
                <button class="wl-config-tab active" data-tab="branding"><i class="fas fa-paint-brush"></i> Branding</button>
                <button class="wl-config-tab" data-tab="domain"><i class="fas fa-globe"></i> Domain</button>
                <button class="wl-config-tab" data-tab="email"><i class="fas fa-envelope"></i> Email</button>
                <button class="wl-config-tab" data-tab="voice"><i class="fas fa-microphone-alt"></i> Voice</button>
                <button class="wl-config-tab" data-tab="features"><i class="fas fa-sliders-h"></i> Features</button>
            </div>

            <!-- ── Tab 1: Branding ── -->
            <div class="wl-tab-panel active" id="tab-branding">
                <div style="display:grid; grid-template-columns:1fr 380px; gap:24px;">
                    <div>
                        <div class="wl-panel-card">
                            <h3><i class="fas fa-building"></i> Company Info</h3>
                            <div class="wl-field">
                                <label>Company Name</label>
                                <input type="text" id="wlCompanyName" placeholder="Your Brand Name" maxlength="200">
                            </div>
                            <div class="wl-field">
                                <label>Logo <small>(paste URL or drag image for base64)</small></label>
                                <input type="text" id="wlLogoData" placeholder="https://yourbrand.com/logo.png or base64 data">
                            </div>
                        </div>

                        <div class="wl-panel-card">
                            <h3><i class="fas fa-palette"></i> Colors</h3>
                            <div class="wl-field">
                                <label>Primary Color</label>
                                <div class="wl-color-group">
                                    <input type="text" id="wlPrimaryColorText" placeholder="#6c5ce7" maxlength="7">
                                    <input type="color" id="wlPrimaryColor" value="#6c5ce7">
                                </div>
                            </div>
                            <div class="wl-field">
                                <label>Secondary Color</label>
                                <div class="wl-color-group">
                                    <input type="text" id="wlSecondaryColorText" placeholder="#a29bfe" maxlength="7">
                                    <input type="color" id="wlSecondaryColor" value="#a29bfe">
                                </div>
                            </div>
                        </div>

                        <div class="wl-panel-card">
                            <h3><i class="fas fa-font"></i> Typography</h3>
                            <div class="wl-field">
                                <label>Font Family</label>
                                <select id="wlFontFamily">
                                    <option value="Inter">Inter</option>
                                    <option value="Space Grotesk">Space Grotesk</option>
                                    <option value="Roboto">Roboto</option>
                                    <option value="Open Sans">Open Sans</option>
                                    <option value="Lato">Lato</option>
                                    <option value="Montserrat">Montserrat</option>
                                    <option value="Poppins">Poppins</option>
                                    <option value="Nunito">Nunito</option>
                                    <option value="Source Sans Pro">Source Sans Pro</option>
                                    <option value="DM Sans">DM Sans</option>
                                </select>
                            </div>
                        </div>

                        <div class="wl-panel-card">
                            <h3><i class="fas fa-code"></i> Custom CSS</h3>
                            <div class="wl-field">
                                <label>Additional CSS <small>(advanced — applied after theme)</small></label>
                                <textarea id="wlCustomCSS" placeholder="/* Custom CSS overrides */&#10;.header { border-color: #ff6600; }"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Live Preview -->
                    <div>
                        <div class="wl-panel-card" style="position:sticky; top:100px;">
                            <h3><i class="fas fa-eye"></i> Live Preview</h3>
                            <div class="wl-preview-box" id="wlPreviewBox">
                                <div class="wl-preview-nav" id="wlPreviewNav" style="background:var(--wl-surface-2);">
                                    <div class="wl-preview-logo" id="wlPreviewLogo" style="background:#6c5ce7;">A</div>
                                    <span class="wl-preview-brand" id="wlPreviewBrand">Your Brand</span>
                                </div>
                                <div class="wl-preview-body" id="wlPreviewBody">
                                    <p style="color:var(--wl-text-muted); font-size:.85rem; margin:0 0 8px;">Dashboard Preview</p>
                                    <p style="font-size:.9rem; margin:0 0 12px;">This is how your white-label dashboard will look to your customers.</p>
                                    <div class="wl-preview-btn" id="wlPreviewBtn" style="background:#6c5ce7;">Get Started</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Tab 2: Domain ── -->
            <div class="wl-tab-panel" id="tab-domain">
                <div class="wl-panel-card">
                    <h3><i class="fas fa-globe"></i> Custom Domain</h3>
                    <div class="wl-field">
                        <label>Domain Name</label>
                        <input type="text" id="wlCustomDomain" placeholder="ai.yourbrand.com">
                    </div>
                    <div style="display:flex; align-items:center; gap:12px; margin:16px 0;">
                        <span class="wl-status wl-status-pending" id="wlDomainStatus"><i class="fas fa-clock"></i> Not Verified</span>
                        <button class="wl-btn wl-btn-sm wl-btn-ghost" onclick="verifyDomain()"><i class="fas fa-sync-alt"></i> Verify DNS</button>
                    </div>

                    <div class="wl-dns-instructions">
                        <h4><i class="fas fa-info-circle"></i> DNS Configuration</h4>
                        <p style="color:var(--wl-text-muted); font-size:.85rem; margin:0 0 12px;">Add the following CNAME record to your domain's DNS settings:</p>
                        <table class="wl-dns-table">
                            <thead>
                                <tr><th>Type</th><th>Name</th><th>Target</th><th>TTL</th></tr>
                            </thead>
                            <tbody>
                                <tr><td>CNAME</td><td id="wlDnsName">ai</td><td>gositeme.com</td><td>3600</td></tr>
                            </tbody>
                        </table>
                        <p style="color:var(--wl-text-muted); font-size:.8rem; margin:12px 0 0;">DNS changes may take up to 48 hours to propagate. SSL certificate will be automatically provisioned after verification.</p>
                    </div>
                </div>

                <div class="wl-panel-card">
                    <h3><i class="fas fa-lock"></i> SSL Certificate</h3>
                    <p style="color:var(--wl-text-muted); font-size:.92rem;">SSL certificates are automatically provisioned via Let's Encrypt after domain verification. All traffic is encrypted end-to-end.</p>
                    <div style="margin-top:12px;">
                        <span class="wl-status wl-status-pending" id="wlSslStatus"><i class="fas fa-clock"></i> Pending domain verification</span>
                    </div>
                </div>
            </div>

            <!-- ── Tab 3: Email Templates ── -->
            <div class="wl-tab-panel" id="tab-email">
                <div class="wl-panel-card">
                    <h3><i class="fas fa-envelope"></i> Email Settings</h3>
                    <div class="wl-field">
                        <label>Sender Name</label>
                        <input type="text" id="wlEmailSender" placeholder="Your Brand Support" maxlength="100">
                    </div>
                    <div class="wl-field">
                        <label>Reply-To Email</label>
                        <input type="email" id="wlEmailReplyTo" placeholder="support@yourbrand.com" maxlength="200">
                    </div>
                </div>

                <div class="wl-panel-card">
                    <h3><i class="fas fa-user-plus"></i> Welcome Email Template</h3>
                    <div class="wl-field">
                        <label>HTML Template <small>(use {{name}}, {{company}}, {{login_url}} placeholders)</small></label>
                        <textarea id="wlWelcomeTemplate" style="min-height:200px;" placeholder="<h1>Welcome to {{company}}, {{name}}!</h1>&#10;<p>Your account is ready. <a href='{{login_url}}'>Log in now</a>.</p>"></textarea>
                    </div>
                </div>

                <div class="wl-panel-card">
                    <h3><i class="fas fa-bell"></i> Notification Email Template</h3>
                    <div class="wl-field">
                        <label>HTML Template <small>(use {{name}}, {{message}}, {{dashboard_url}} placeholders)</small></label>
                        <textarea id="wlNotificationTemplate" style="min-height:200px;" placeholder="<h2>{{name}}, you have a new notification</h2>&#10;<p>{{message}}</p>&#10;<a href='{{dashboard_url}}'>View Dashboard</a>"></textarea>
                    </div>
                </div>
            </div>

            <!-- ── Tab 4: Voice ── -->
            <div class="wl-tab-panel" id="tab-voice">
                <div class="wl-panel-card">
                    <h3><i class="fas fa-microphone-alt"></i> Voice Settings</h3>
                    <div class="wl-field">
                        <label>Custom Greeting Message</label>
                        <textarea id="wlVoiceGreeting" style="min-height:80px;" placeholder="Thank you for calling Your Brand. How can I help you today?"></textarea>
                    </div>
                    <div class="wl-field">
                        <label>Company Name Pronunciation <small>(phonetic spelling for TTS)</small></label>
                        <input type="text" id="wlVoiceCompanyName" placeholder="Your Brand" maxlength="200">
                    </div>
                    <div class="wl-field">
                        <label>Hold Music URL <small>(MP3 or WAV)</small></label>
                        <input type="url" id="wlHoldMusicUrl" placeholder="https://yourbrand.com/hold-music.mp3" maxlength="500">
                    </div>
                </div>

                <div class="wl-panel-card">
                    <h3><i class="fas fa-diagram-project"></i> IVR Prompts</h3>
                    <p style="color:var(--wl-text-muted); font-size:.92rem; margin:0 0 16px;">
                        For advanced IVR configuration, use the <a href="/ivr-builder.php" style="color:var(--wl-accent-light);">IVR Builder</a> 
                        to design custom call flows for your white-label deployment.
                    </p>
                    <a href="/ivr-builder.php" class="wl-btn wl-btn-sm wl-btn-ghost"><i class="fas fa-diagram-project"></i> Open IVR Builder</a>
                </div>
            </div>

            <!-- ── Tab 5: Features ── -->
            <div class="wl-tab-panel" id="tab-features">
                <div class="wl-panel-card">
                    <h3><i class="fas fa-sliders-h"></i> Feature Toggles</h3>
                    <p style="color:var(--wl-text-muted); font-size:.9rem; margin:0 0 20px;">Control which features are visible to your end users in the white-label deployment.</p>

                    <div class="wl-toggle-row">
                        <div class="wl-toggle-info">
                            <h4>AI Chat</h4>
                            <p>Text-based AI conversation interface</p>
                        </div>
                        <label class="wl-switch"><input type="checkbox" data-feature="chat" checked><span class="slider"></span></label>
                    </div>
                    <div class="wl-toggle-row">
                        <div class="wl-toggle-info">
                            <h4>Voice Agents</h4>
                            <p>AI phone answering and outbound calling</p>
                        </div>
                        <label class="wl-switch"><input type="checkbox" data-feature="voice" checked><span class="slider"></span></label>
                    </div>
                    <div class="wl-toggle-row">
                        <div class="wl-toggle-info">
                            <h4>AI Tools</h4>
                            <p>Access to 1,220+ AI-powered tools</p>
                        </div>
                        <label class="wl-switch"><input type="checkbox" data-feature="tools" checked><span class="slider"></span></label>
                    </div>
                    <div class="wl-toggle-row">
                        <div class="wl-toggle-info">
                            <h4>Fleet Dashboard</h4>
                            <p>Manage multiple AI agents at once</p>
                        </div>
                        <label class="wl-switch"><input type="checkbox" data-feature="fleet" checked><span class="slider"></span></label>
                    </div>
                    <div class="wl-toggle-row">
                        <div class="wl-toggle-info">
                            <h4>Conference Rooms</h4>
                            <p>Multi-AI video and voice conference rooms</p>
                        </div>
                        <label class="wl-switch"><input type="checkbox" data-feature="conference" checked><span class="slider"></span></label>
                    </div>
                    <div class="wl-toggle-row">
                        <div class="wl-toggle-info">
                            <h4>Marketplace</h4>
                            <p>Browse and install third-party tools and templates</p>
                        </div>
                        <label class="wl-switch"><input type="checkbox" data-feature="marketplace"><span class="slider"></span></label>
                    </div>
                    <div class="wl-toggle-row">
                        <div class="wl-toggle-info">
                            <h4>Extensions</h4>
                            <p>Chrome extension, CLI, and VS Code extension</p>
                        </div>
                        <label class="wl-switch"><input type="checkbox" data-feature="extensions"><span class="slider"></span></label>
                    </div>
                    <div class="wl-toggle-row">
                        <div class="wl-toggle-info">
                            <h4>Analytics Dashboard</h4>
                            <p>Usage analytics and performance metrics</p>
                        </div>
                        <label class="wl-switch"><input type="checkbox" data-feature="analytics" checked><span class="slider"></span></label>
                    </div>
                    <div class="wl-toggle-row">
                        <div class="wl-toggle-info">
                            <h4>Webhooks</h4>
                            <p>Webhook management for event-driven integrations</p>
                        </div>
                        <label class="wl-switch"><input type="checkbox" data-feature="webhooks" checked><span class="slider"></span></label>
                    </div>
                </div>
            </div>

            <!-- Save Bar -->
            <div class="wl-save-bar">
                <div class="wl-container">
                    <div class="wl-save-bar-inner">
                        <span class="wl-save-msg" id="wlSaveMsg"></span>
                        <div style="display:flex; gap:12px;">
                            <button class="wl-btn wl-btn-sm wl-btn-ghost" onclick="loadConfig()"><i class="fas fa-undo"></i> Reset</button>
                            <button class="wl-btn wl-btn-sm wl-btn-primary" onclick="saveConfig()"><i class="fas fa-save"></i> Save Configuration</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php endif; ?>

</div>

<script>window._wlLoggedIn = <?= $is_logged_in ? "true" : "false" ?>;</script>
<script src="/assets/js/white-label-engine.js"></script>

<?php require_once __DIR__ . '/includes/site-footer.inc.php'; ?>
