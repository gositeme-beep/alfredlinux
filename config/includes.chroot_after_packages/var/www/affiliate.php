<?php
/**
 * Alfred AI Affiliate Program
 * Public page + authenticated dashboard
 */
$page_title       = 'Affiliate Program — Earn 20-30% Recurring Commission | Alfred AI';
$page_description = 'Join the Alfred AI affiliate program and earn up to 30% recurring commission on every referral. Get your unique link, marketing assets, and real-time tracking.';
$page_canonical   = 'https://root.com/affiliate.php';
$page_og_image    = 'https://root.com/assets/img/affiliate/og-affiliate.png';

require_once __DIR__ . '/includes/site-header.inc.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$is_logged_in = !empty($_SESSION['logged_in']) && !empty($_SESSION['client_id']);
$client_id    = $is_logged_in ? (int)$_SESSION['client_id'] : 0;
$client_name  = $_SESSION['client_name'] ?? '';
$client_email = $_SESSION['client_email'] ?? '';
// Legacy aliases for template compatibility
$isLoggedIn = $is_logged_in;
$clientId   = $client_id;
?>

<style>
/* ── Affiliate Page Styles ──────────────────────────────────────────────── */
:root {
    --al-bg: #0a0a14;
    --al-surface: #12121e;
    --al-surface2: #1a1a2e;
    --al-accent: #6c5ce7;
    --al-accent-glow: rgba(108,92,231,0.3);
    --al-text: #e0e0e0;
    --al-text-dim: #8888aa;
    --al-border: rgba(108,92,231,0.15);
    --al-success: #00cec9;
    --al-warning: #fdcb6e;
    --al-danger: #ff6b6b;
    --al-gold: #ffd700;
    --al-silver: #c0c0c0;
    --al-bronze: #cd7f32;
    --al-radius: 16px;
    --al-radius-sm: 10px;
}

.aff-page { background: var(--al-bg); color: var(--al-text); min-height: 100vh; }

/* Hero */
.aff-hero {
    text-align: center;
    padding: 140px 20px 80px;
    background: linear-gradient(180deg, rgba(108,92,231,0.08) 0%, transparent 60%);
    position: relative;
    overflow: hidden;
}
.aff-hero::before {
    content: '';
    position: absolute;
    width: 600px; height: 600px;
    background: radial-gradient(circle, rgba(108,92,231,0.12), transparent 70%);
    top: -200px; left: 50%; transform: translateX(-50%);
    border-radius: 50%;
    pointer-events: none;
}
.aff-hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(108,92,231,0.15);
    border: 1px solid var(--al-border);
    padding: 8px 20px;
    border-radius: 50px;
    font-size: 0.85rem;
    color: var(--al-accent);
    margin-bottom: 24px;
    font-weight: 600;
}
.aff-hero h1 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: clamp(2.2rem, 5vw, 3.8rem);
    font-weight: 800;
    line-height: 1.15;
    margin: 0 0 20px;
    color: #fff;
}
.aff-hero h1 span { color: var(--al-accent); }
.aff-hero p {
    font-size: 1.15rem;
    color: var(--al-text-dim);
    max-width: 640px;
    margin: 0 auto 36px;
    line-height: 1.7;
}
.aff-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 16px 36px;
    background: linear-gradient(135deg, var(--al-accent), #5a4bd1);
    color: #fff;
    border: none;
    border-radius: 50px;
    font-size: 1.05rem;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.3s;
    box-shadow: 0 4px 24px var(--al-accent-glow);
}
.aff-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 32px rgba(108,92,231,0.45);
}
.aff-btn-outline {
    background: transparent;
    border: 2px solid var(--al-accent);
    color: var(--al-accent);
    box-shadow: none;
}
.aff-btn-outline:hover { background: rgba(108,92,231,0.1); }

/* Sections */
.aff-section {
    padding: 80px 20px;
    max-width: 1200px;
    margin: 0 auto;
}
.aff-section-title {
    text-align: center;
    font-family: 'Space Grotesk', sans-serif;
    font-size: clamp(1.6rem, 3vw, 2.4rem);
    font-weight: 700;
    color: #fff;
    margin-bottom: 12px;
}
.aff-section-sub {
    text-align: center;
    color: var(--al-text-dim);
    font-size: 1.05rem;
    margin-bottom: 48px;
    max-width: 600px;
    margin-left: auto; margin-right: auto;
}

/* Commission Tiers */
.aff-tiers {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 28px;
    margin-top: 40px;
}
.aff-tier-card {
    background: var(--al-surface);
    border: 1px solid var(--al-border);
    border-radius: var(--al-radius);
    padding: 36px 28px;
    text-align: center;
    transition: all 0.3s;
    position: relative;
    overflow: hidden;
}
.aff-tier-card:hover {
    transform: translateY(-4px);
    border-color: var(--al-accent);
    box-shadow: 0 12px 40px rgba(0,0,0,0.3);
}
.aff-tier-card.gold { border-top: 3px solid var(--al-gold); }
.aff-tier-card.silver { border-top: 3px solid var(--al-silver); }
.aff-tier-card.bronze { border-top: 3px solid var(--al-bronze); }

.aff-tier-icon {
    width: 64px; height: 64px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    font-size: 1.6rem;
}
.aff-tier-card.bronze .aff-tier-icon { background: rgba(205,127,50,0.15); color: var(--al-bronze); }
.aff-tier-card.silver .aff-tier-icon { background: rgba(192,192,192,0.15); color: var(--al-silver); }
.aff-tier-card.gold .aff-tier-icon { background: rgba(255,215,0,0.15); color: var(--al-gold); }

.aff-tier-name { font-size: 1.3rem; font-weight: 700; color: #fff; margin-bottom: 6px; }
.aff-tier-range { font-size: 0.85rem; color: var(--al-text-dim); margin-bottom: 20px; }
.aff-tier-rate {
    font-size: 2.8rem;
    font-weight: 800;
    font-family: 'Space Grotesk', sans-serif;
    margin-bottom: 4px;
}
.aff-tier-card.bronze .aff-tier-rate { color: var(--al-bronze); }
.aff-tier-card.silver .aff-tier-rate { color: var(--al-silver); }
.aff-tier-card.gold .aff-tier-rate { color: var(--al-gold); }
.aff-tier-label { font-size: 0.9rem; color: var(--al-text-dim); margin-bottom: 24px; }

.aff-tier-features {
    list-style: none;
    padding: 0;
    margin: 0;
    text-align: left;
}
.aff-tier-features li {
    padding: 10px 0;
    border-top: 1px solid rgba(255,255,255,0.04);
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.95rem;
    color: var(--al-text);
}
.aff-tier-features li i { color: var(--al-success); font-size: 0.85rem; width: 18px; text-align: center; }

/* How It Works */
.aff-steps {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 32px;
    margin-top: 40px;
}
.aff-step {
    text-align: center;
    position: relative;
}
.aff-step-num {
    width: 56px; height: 56px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--al-accent), #5a4bd1);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 18px;
    font-size: 1.3rem;
    font-weight: 800;
    color: #fff;
    box-shadow: 0 4px 20px var(--al-accent-glow);
}
.aff-step h4 { font-size: 1.1rem; font-weight: 700; color: #fff; margin-bottom: 8px; }
.aff-step p { color: var(--al-text-dim); font-size: 0.92rem; line-height: 1.6; }
.aff-step-arrow {
    display: none;
    position: absolute;
    right: -20px; top: 28px;
    color: var(--al-accent);
    font-size: 1.2rem;
    opacity: 0.4;
}
@media (min-width: 900px) {
    .aff-step-arrow { display: block; }
    .aff-step:last-child .aff-step-arrow { display: none; }
}

/* Dashboard */
.aff-dashboard {
    background: var(--al-surface);
    border: 1px solid var(--al-border);
    border-radius: var(--al-radius);
    padding: 40px 32px;
    margin-top: 40px;
}
.aff-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 32px;
}
.aff-stat-card {
    background: var(--al-surface2);
    border-radius: var(--al-radius-sm);
    padding: 24px;
    text-align: center;
    border: 1px solid rgba(255,255,255,0.04);
}
.aff-stat-card i { font-size: 1.5rem; color: var(--al-accent); margin-bottom: 10px; display: block; }
.aff-stat-value {
    font-size: 2rem;
    font-weight: 800;
    font-family: 'Space Grotesk', sans-serif;
    color: #fff;
    margin-bottom: 4px;
}
.aff-stat-label { font-size: 0.82rem; color: var(--al-text-dim); text-transform: uppercase; letter-spacing: 0.5px; }

.aff-link-box {
    background: var(--al-surface2);
    border-radius: var(--al-radius-sm);
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 32px;
    border: 1px solid var(--al-border);
}
.aff-link-box input {
    flex: 1;
    background: transparent;
    border: none;
    color: var(--al-accent);
    font-size: 0.95rem;
    font-family: 'Space Grotesk', monospace;
    outline: none;
}
.aff-copy-btn {
    padding: 10px 20px;
    background: var(--al-accent);
    color: #fff;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 0.85rem;
    transition: all 0.2s;
    white-space: nowrap;
}
.aff-copy-btn:hover { background: #5a4bd1; }
.aff-copy-btn.copied { background: var(--al-success); }

/* Referrals table */
.aff-table-wrap { overflow-x: auto; }
.aff-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9rem;
}
.aff-table th {
    text-align: left;
    padding: 12px 16px;
    color: var(--al-text-dim);
    font-weight: 600;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid var(--al-border);
}
.aff-table td {
    padding: 14px 16px;
    border-bottom: 1px solid rgba(255,255,255,0.03);
    color: var(--al-text);
}
.aff-table tr:hover td { background: rgba(108,92,231,0.04); }
.aff-status {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}
.aff-status.converted { background: rgba(0,206,201,0.15); color: var(--al-success); }
.aff-status.signed_up { background: rgba(108,92,231,0.15); color: var(--al-accent); }
.aff-status.clicked   { background: rgba(255,255,255,0.06); color: var(--al-text-dim); }
.aff-status.churned   { background: rgba(255,107,107,0.15); color: var(--al-danger); }

/* Login prompt */
.aff-login-prompt {
    text-align: center;
    padding: 60px 20px;
    background: var(--al-surface);
    border-radius: var(--al-radius);
    border: 1px dashed var(--al-border);
}
.aff-login-prompt i { font-size: 2.5rem; color: var(--al-accent); margin-bottom: 16px; display: block; }
.aff-login-prompt h3 { color: #fff; margin-bottom: 10px; }
.aff-login-prompt p { color: var(--al-text-dim); margin-bottom: 24px; }

/* Marketing Assets */
.aff-assets-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 24px;
    margin-top: 32px;
}
.aff-asset-card {
    background: var(--al-surface);
    border: 1px solid var(--al-border);
    border-radius: var(--al-radius-sm);
    padding: 24px;
}
.aff-asset-card h4 { color: #fff; font-size: 1rem; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
.aff-asset-card h4 i { color: var(--al-accent); }
.aff-asset-preview {
    background: var(--al-surface2);
    border-radius: 8px;
    padding: 16px;
    font-size: 0.88rem;
    color: var(--al-text);
    line-height: 1.6;
    margin-bottom: 14px;
    white-space: pre-wrap;
    max-height: 160px;
    overflow-y: auto;
}
.aff-asset-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.aff-asset-btn {
    padding: 8px 16px;
    background: rgba(108,92,231,0.12);
    color: var(--al-accent);
    border: 1px solid var(--al-border);
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.82rem;
    font-weight: 600;
    transition: all 0.2s;
}
.aff-asset-btn:hover { background: rgba(108,92,231,0.25); }

/* FAQ */
.aff-faq { margin-top: 40px; }
.aff-faq-item {
    background: var(--al-surface);
    border: 1px solid var(--al-border);
    border-radius: var(--al-radius-sm);
    margin-bottom: 12px;
    overflow: hidden;
    transition: border-color 0.3s;
}
.aff-faq-item.active { border-color: var(--al-accent); }
.aff-faq-q {
    padding: 20px 24px;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 600;
    color: #fff;
    font-size: 1rem;
}
.aff-faq-q:hover { color: var(--al-accent); }
.aff-faq-q i { transition: transform 0.3s; color: var(--al-accent); font-size: 0.85rem; }
.aff-faq-item.active .aff-faq-q i { transform: rotate(180deg); }
.aff-faq-a {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.35s ease, padding 0.35s ease;
    padding: 0 24px;
    color: var(--al-text-dim);
    line-height: 1.7;
    font-size: 0.95rem;
}
.aff-faq-item.active .aff-faq-a { max-height: 300px; padding: 0 24px 20px; }

/* CTA Footer */
.aff-cta-footer {
    text-align: center;
    padding: 80px 20px;
    background: linear-gradient(180deg, transparent 0%, rgba(108,92,231,0.06) 100%);
}
.aff-cta-footer h2 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: clamp(1.8rem, 3.5vw, 2.8rem);
    font-weight: 800;
    color: #fff;
    margin-bottom: 16px;
}
.aff-cta-footer p { color: var(--al-text-dim); font-size: 1.1rem; margin-bottom: 32px; max-width: 500px; margin-left: auto; margin-right: auto; }

/* Loading & empty states */
.aff-loading { text-align: center; padding: 40px; color: var(--al-text-dim); }
.aff-empty { text-align: center; padding: 40px; color: var(--al-text-dim); }
.aff-spinner { display: inline-block; width: 32px; height: 32px; border: 3px solid var(--al-border); border-top-color: var(--al-accent); border-radius: 50%; animation: aff-spin 0.8s linear infinite; }
@keyframes aff-spin { to { transform: rotate(360deg); } }

/* Responsive */
@media (max-width: 768px) {
    .aff-hero { padding: 70px 16px 50px; }
    .aff-section { padding: 50px 16px; }
    .aff-dashboard { padding: 24px 16px; }
    .aff-link-box { flex-direction: column; }
    .aff-link-box input { width: 100%; text-align: center; }
    .aff-stats-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>

<div class="aff-page">

    <!-- ══ Hero ══════════════════════════════════════════════════════════ -->
    <section class="aff-hero">
        <div data-aos="fade-up">
            <div class="aff-hero-badge">
                <i class="fas fa-handshake"></i> Partner Program
            </div>
            <h1>Earn <span>20% Recurring Commission</span></h1>
            <p>Refer customers to Alfred AI and earn commission for life. Join our affiliate program and start earning with every referral.</p>
            <?php if ($isLoggedIn): ?>
                <a href="#dashboard" class="aff-btn"><i class="fas fa-chart-line"></i> Go to Dashboard</a>
            <?php else: ?>
                <a href="#signup" class="aff-btn"><i class="fas fa-rocket"></i> Join the Affiliate Program</a>
            <?php endif; ?>
        </div>
    </section>

    <!-- ══ Commission Tiers ═════════════════════════════════════════════ -->
    <section class="aff-section">
        <h2 class="aff-section-title" data-aos="fade-up">Commission Tiers</h2>
        <p class="aff-section-sub" data-aos="fade-up" data-aos-delay="100">The more you refer, the more you earn. Upgrade tiers automatically as your referrals grow.</p>

        <div class="aff-tiers">
            <!-- Bronze -->
            <div class="aff-tier-card bronze" data-aos="fade-up" data-aos-delay="100">
                <div class="aff-tier-icon"><i class="fas fa-medal"></i></div>
                <div class="aff-tier-name">Bronze</div>
                <div class="aff-tier-range">1 – 10 referrals</div>
                <div class="aff-tier-rate">20%</div>
                <div class="aff-tier-label">Recurring Commission</div>
                <ul class="aff-tier-features">
                    <li><i class="fas fa-check"></i> 30-day tracking cookie</li>
                    <li><i class="fas fa-check"></i> $50 minimum payout</li>
                    <li><i class="fas fa-check"></i> Real-time analytics</li>
                    <li><i class="fas fa-check"></i> Marketing materials</li>
                    <li><i class="fas fa-check"></i> Dedicated referral link</li>
                </ul>
            </div>

            <!-- Silver -->
            <div class="aff-tier-card silver" data-aos="fade-up" data-aos-delay="200">
                <div class="aff-tier-icon"><i class="fas fa-award"></i></div>
                <div class="aff-tier-name">Silver</div>
                <div class="aff-tier-range">11 – 50 referrals</div>
                <div class="aff-tier-rate">25%</div>
                <div class="aff-tier-label">Recurring Commission</div>
                <ul class="aff-tier-features">
                    <li><i class="fas fa-check"></i> 60-day tracking cookie</li>
                    <li><i class="fas fa-check"></i> $25 minimum payout</li>
                    <li><i class="fas fa-check"></i> Priority support</li>
                    <li><i class="fas fa-check"></i> Custom landing pages</li>
                    <li><i class="fas fa-check"></i> Monthly performance reports</li>
                </ul>
            </div>

            <!-- Gold -->
            <div class="aff-tier-card gold" data-aos="fade-up" data-aos-delay="300">
                <div class="aff-tier-icon"><i class="fas fa-crown"></i></div>
                <div class="aff-tier-name">Gold</div>
                <div class="aff-tier-range">51+ referrals</div>
                <div class="aff-tier-rate">30%</div>
                <div class="aff-tier-label">Recurring Commission</div>
                <ul class="aff-tier-features">
                    <li><i class="fas fa-check"></i> 90-day tracking cookie</li>
                    <li><i class="fas fa-check"></i> $10 minimum payout</li>
                    <li><i class="fas fa-check"></i> Dedicated account manager</li>
                    <li><i class="fas fa-check"></i> Custom commission deals</li>
                    <li><i class="fas fa-check"></i> Early access to features</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- ══ How It Works ═════════════════════════════════════════════════ -->
    <section class="aff-section" style="background: var(--al-surface); border-radius: 0; max-width: 100%; padding-left: 20px; padding-right: 20px;">
        <div style="max-width: 1200px; margin: 0 auto;">
            <h2 class="aff-section-title" data-aos="fade-up">How It Works</h2>
            <p class="aff-section-sub" data-aos="fade-up" data-aos-delay="100">Start earning in four simple steps</p>

            <div class="aff-steps">
                <div class="aff-step" data-aos="fade-up" data-aos-delay="100">
                    <div class="aff-step-num">1</div>
                    <h4>Sign Up</h4>
                    <p>Create your free affiliate account in seconds. No approval needed — start immediately.</p>
                    <i class="fas fa-chevron-right aff-step-arrow"></i>
                </div>
                <div class="aff-step" data-aos="fade-up" data-aos-delay="200">
                    <div class="aff-step-num">2</div>
                    <h4>Get Your Link</h4>
                    <p>Receive your unique referral link with tracking. Share it anywhere — it's that simple.</p>
                    <i class="fas fa-chevron-right aff-step-arrow"></i>
                </div>
                <div class="aff-step" data-aos="fade-up" data-aos-delay="300">
                    <div class="aff-step-num">3</div>
                    <h4>Share & Promote</h4>
                    <p>Share with your audience using our ready-made banners, social posts, and email templates.</p>
                    <i class="fas fa-chevron-right aff-step-arrow"></i>
                </div>
                <div class="aff-step" data-aos="fade-up" data-aos-delay="400">
                    <div class="aff-step-num">4</div>
                    <h4>Earn Commission</h4>
                    <p>Earn recurring commission on every referred customer for as long as they stay subscribed.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ══ Affiliate Dashboard ══════════════════════════════════════════ -->
    <section class="aff-section" id="dashboard">
        <h2 class="aff-section-title" data-aos="fade-up"><i class="fas fa-chart-pie" style="color:var(--al-accent);margin-right:8px;"></i> Affiliate Dashboard</h2>
        <p class="aff-section-sub" data-aos="fade-up" data-aos-delay="100">Track your referrals, commissions, and performance in real time.</p>

        <?php if ($isLoggedIn): ?>
        <div class="aff-dashboard" id="dashboardContent">
            <div class="aff-loading" id="dashLoading">
                <div class="aff-spinner"></div>
                <p style="margin-top:12px;">Loading your dashboard…</p>
            </div>

            <!-- Not registered prompt (hidden by default) -->
            <div id="dashRegister" style="display:none;">
                <div class="aff-login-prompt">
                    <i class="fas fa-user-plus"></i>
                    <h3>You're Not an Affiliate Yet</h3>
                    <p>Join the program to get your referral link and start earning.</p>
                    <button class="aff-btn" id="btnRegisterAffiliate"><i class="fas fa-rocket"></i> Join Now — It's Free</button>
                </div>
            </div>

            <!-- Active dashboard (hidden until loaded) -->
            <div id="dashActive" style="display:none;">
                <!-- Stats -->
                <div class="aff-stats-grid">
                    <div class="aff-stat-card">
                        <i class="fas fa-users"></i>
                        <div class="aff-stat-value" id="statTotalRefs">0</div>
                        <div class="aff-stat-label">Total Referrals</div>
                    </div>
                    <div class="aff-stat-card">
                        <i class="fas fa-user-check"></i>
                        <div class="aff-stat-value" id="statActiveRefs">0</div>
                        <div class="aff-stat-label">Active Referrals</div>
                    </div>
                    <div class="aff-stat-card">
                        <i class="fas fa-dollar-sign"></i>
                        <div class="aff-stat-value" id="statTotalEarnings">$0.00</div>
                        <div class="aff-stat-label">Total Earnings</div>
                    </div>
                    <div class="aff-stat-card">
                        <i class="fas fa-wallet"></i>
                        <div class="aff-stat-value" id="statPending">$0.00</div>
                        <div class="aff-stat-label">Pending Payout</div>
                    </div>
                </div>

                <!-- Referral Link -->
                <h4 style="color:#fff;margin-bottom:12px;font-size:0.95rem;"><i class="fas fa-link" style="color:var(--al-accent);margin-right:6px;"></i> Your Referral Link</h4>
                <div class="aff-link-box">
                    <input type="text" id="referralLink" value="" readonly>
                    <button class="aff-copy-btn" id="copyBtn"><i class="fas fa-copy"></i> Copy</button>
                </div>

                <!-- Tier badge -->
                <div style="margin-bottom:32px;display:flex;align-items:center;gap:12px;">
                    <span style="font-size:0.85rem;color:var(--al-text-dim);">Current Tier:</span>
                    <span id="tierBadge" class="aff-status converted" style="font-size:0.85rem;">Bronze</span>
                </div>

                <!-- Referrals Table -->
                <h4 style="color:#fff;margin-bottom:16px;font-size:0.95rem;"><i class="fas fa-list" style="color:var(--al-accent);margin-right:6px;"></i> Recent Referrals</h4>
                <div class="aff-table-wrap">
                    <table class="aff-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Referral ID</th>
                                <th>Status</th>
                                <th>Revenue</th>
                                <th>Commission</th>
                            </tr>
                        </thead>
                        <tbody id="referralsBody">
                            <tr><td colspan="5" class="aff-empty">No referrals yet. Share your link to get started!</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="aff-login-prompt" id="signup" data-aos="fade-up" data-aos-delay="200">
            <i class="fas fa-lock"></i>
            <h3>Log In to Access Your Dashboard</h3>
            <p>Sign in to your GoSiteMe account to view your affiliate stats, referral link, and earnings.</p>
            <a href="/api/auth.php?redirect=<?php echo urlencode('/affiliate.php#dashboard'); ?>" class="aff-btn"><i class="fas fa-sign-in-alt"></i> Log In / Sign Up</a>
        </div>
        <?php endif; ?>
    </section>

    <!-- ══ Marketing Assets ═════════════════════════════════════════════ -->
    <section class="aff-section">
        <h2 class="aff-section-title" data-aos="fade-up"><i class="fas fa-bullhorn" style="color:var(--al-accent);margin-right:8px;"></i> Marketing Assets</h2>
        <p class="aff-section-sub" data-aos="fade-up" data-aos-delay="100">Ready-made content to help you promote Alfred AI</p>

        <div class="aff-assets-grid">
            <!-- Banners -->
            <div class="aff-asset-card" data-aos="fade-up" data-aos-delay="100">
                <h4><i class="fas fa-image"></i> Banner Ads</h4>
                <div class="aff-asset-preview">
Available banner sizes:
• Leaderboard (728×90)
• Medium Rectangle (300×250)
• Skyscraper (160×600)
• Mobile Banner (320×50)

All banners include your tracking link automatically.</div>
                <div class="aff-asset-actions">
                    <button class="aff-asset-btn" id="btnLoadBanners"><i class="fas fa-download"></i> Download Pack</button>
                </div>
            </div>

            <!-- Social Posts -->
            <div class="aff-asset-card" data-aos="fade-up" data-aos-delay="200">
                <h4><i class="fab fa-twitter"></i> Social Media Posts</h4>
                <div class="aff-asset-preview" id="socialPreview">🚀 I've been using Alfred AI and it's incredible — 13,000+ AI tools in one platform. Try it free for 14 days!

#AI #AlfredAI #GoSiteMe</div>
                <div class="aff-asset-actions">
                    <button class="aff-asset-btn" data-social="twitter"><i class="fab fa-twitter"></i> Twitter</button>
                    <button class="aff-asset-btn" data-social="linkedin"><i class="fab fa-linkedin"></i> LinkedIn</button>
                    <button class="aff-asset-btn" data-social="facebook"><i class="fab fa-facebook"></i> Facebook</button>
                </div>
            </div>

            <!-- Email Templates -->
            <div class="aff-asset-card" data-aos="fade-up" data-aos-delay="300">
                <h4><i class="fas fa-envelope"></i> Email Templates</h4>
                <div class="aff-asset-preview">Subject: Try Alfred AI — 13,000+ AI Tools in One Platform

Hi {name},

I wanted to share Alfred AI with you — an all-in-one AI platform with 13,000+ tools for writing, coding, legal, marketing, and more.

Plans start at $3.99/mo with a 14-day free trial.</div>
                <div class="aff-asset-actions">
                    <button class="aff-asset-btn" data-email="intro"><i class="fas fa-copy"></i> Copy Intro Email</button>
                    <button class="aff-asset-btn" data-email="quick"><i class="fas fa-copy"></i> Copy Quick Email</button>
                </div>
            </div>
        </div>
    </section>

    <!-- ══ FAQ ══════════════════════════════════════════════════════════ -->
    <section class="aff-section">
        <h2 class="aff-section-title" data-aos="fade-up">Frequently Asked Questions</h2>
        <p class="aff-section-sub" data-aos="fade-up" data-aos-delay="100">Everything you need to know about the affiliate program</p>

        <div class="aff-faq" data-aos="fade-up" data-aos-delay="200">
            <div class="aff-faq-item">
                <div class="aff-faq-q">
                    How do I get paid?
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="aff-faq-a">
                    We offer payouts via PayPal, Stripe, or bank transfer. Simply set your preferred payout method in your dashboard and request a payout once you've reached the minimum threshold for your tier. Payouts are processed within 5-7 business days.
                </div>
            </div>

            <div class="aff-faq-item">
                <div class="aff-faq-q">
                    When are commissions calculated?
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="aff-faq-a">
                    Commissions are calculated in real time whenever a referred customer makes a payment. You'll see the commission credited to your account immediately in your dashboard. Commissions are recurring — you earn on every payment your referral makes, not just the first one.
                </div>
            </div>

            <div class="aff-faq-item">
                <div class="aff-faq-q">
                    Is there a minimum payout?
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="aff-faq-a">
                    Yes. The minimum depends on your tier: Bronze is $50, Silver is $25, and Gold is $10. As you grow your referrals and move up tiers, the minimum decreases — making it easier to cash out more frequently.
                </div>
            </div>

            <div class="aff-faq-item">
                <div class="aff-faq-q">
                    Can I refer myself?
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="aff-faq-a">
                    No. Self-referrals are not permitted and will be flagged automatically. The affiliate program is designed to reward you for bringing new customers to Alfred AI. Any detected self-referrals may result in account suspension.
                </div>
            </div>

            <div class="aff-faq-item">
                <div class="aff-faq-q">
                    How long does the cookie last?
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="aff-faq-a">
                    Cookie duration depends on your tier. Bronze affiliates get a 30-day cookie, Silver gets 60 days, and Gold gets 90 days. This means if someone clicks your link and signs up within that period, you'll receive credit for the referral.
                </div>
            </div>

            <div class="aff-faq-item">
                <div class="aff-faq-q">
                    What products earn commissions?
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="aff-faq-a">
                    You earn commissions on all Alfred AI subscription plans, including Starter, Professional, and Enterprise. This also includes any add-ons or upgrades your referral purchases. Token pack purchases and one-time services may also qualify.
                </div>
            </div>
        </div>
    </section>

    <!-- ══ CTA Footer ═══════════════════════════════════════════════════ -->
    <section class="aff-cta-footer">
        <div data-aos="fade-up">
            <h2>Start Earning Today</h2>
            <p>Join thousands of affiliates already earning recurring income with Alfred AI.</p>
            <?php if ($isLoggedIn): ?>
                <a href="#dashboard" class="aff-btn"><i class="fas fa-chart-line"></i> View Your Dashboard</a>
            <?php else: ?>
                <a href="/api/auth.php?redirect=<?php echo urlencode('/affiliate.php#dashboard'); ?>" class="aff-btn"><i class="fas fa-rocket"></i> Join the Affiliate Program</a>
            <?php endif; ?>
        </div>
    </section>

</div>

<!-- ══ JavaScript ═══════════════════════════════════════════════════════ -->
<script>window._affLoggedIn = <?= $isLoggedIn ? "true" : "false" ?>;</script>
<script src="/assets/js/affiliate-engine.js"></script>

<?php require_once __DIR__ . '/includes/site-footer.inc.php'; ?>
