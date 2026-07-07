<?php
/**
 * Voice & AI Products — Full Product Catalog
 * GoSiteMe.com — Voice Agents, Phone Numbers, Fax, SMS, Industry Solutions
 */

$page_title = 'Voice & AI Products — Phone Agents, Numbers, Fax, SMS | GoSiteMe';
$page_description = 'Browse 52 Voice & AI products. AI phone agents from \$29/mo, local numbers from \$3/mo, fax, SMS, call centers, industry solutions. À la carte pricing, no contracts. Reseller & affiliate programs.';
$page_canonical = 'https://gositeme.com/voice-products.php';
$page_og_title = 'Voice & AI Products — From $3/month';
$page_og_description = 'AI phone agents, local & toll-free numbers, fax, SMS, call centers, industry solutions. À la carte or full packages. Reseller & affiliate programs available.';
$page_twitter_description = $page_og_description;
$page_og_image = 'https://gositeme.com/assets/hero-banner.png';
$page_robots = 'index, follow';
$preload_hero = false;

$noGlobalMain = true;
require_once __DIR__ . '/includes/site-header.inc.php';

// Clean link helper
if (!function_exists('billing_link')) {
    function billing_link($path) { return '/' . ltrim($path, '/'); }
}
?>

<style>
/* ═══ Voice Products Page Styles ═══ */
.vp-hero {
    padding: 140px 0 60px;
    text-align: center;
    position: relative;
    overflow: hidden;
    background: linear-gradient(180deg, rgba(125,0,255,0.08) 0%, transparent 60%);
}
.vp-hero::before {
    content: '';
    position: absolute;
    top: -200px; right: -200px;
    width: 600px; height: 600px;
    background: radial-gradient(circle, rgba(125,0,255,0.15), transparent 70%);
    border-radius: 50%;
}
.vp-hero::after {
    content: '';
    position: absolute;
    bottom: -150px; left: -150px;
    width: 500px; height: 500px;
    background: radial-gradient(circle, rgba(0,212,255,0.1), transparent 70%);
    border-radius: 50%;
}
.vp-hero .container { position: relative; z-index: 2; }
.vp-hero-badge {
    display: inline-flex; align-items: center; gap: 8px;
    background: rgba(125,0,255,0.15); border: 1px solid rgba(125,0,255,0.3);
    padding: 6px 16px; border-radius: 20px; font-size: 0.85rem; color: #a78bfa;
    margin-bottom: 20px; font-weight: 600;
}
.vp-hero h1 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: clamp(2rem, 5vw, 3.2rem);
    font-weight: 800; color: #fff; margin: 0 0 16px; line-height: 1.15;
}
.vp-hero h1 .gradient { background: linear-gradient(135deg, #7d00ff, #00d4ff); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.vp-hero p { color: var(--text-muted); font-size: 1.15rem; max-width: 700px; margin: 0 auto 30px; line-height: 1.6; }
.vp-hero-stats {
    display: flex; justify-content: center; gap: 32px; flex-wrap: wrap; margin-top: 32px;
}
.vp-hero-stats .stat {
    text-align: center; padding: 12px 20px;
    background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06);
    border-radius: 12px; min-width: 120px;
}
.vp-hero-stats .stat-val { font-size: 1.6rem; font-weight: 800; color: #7d00ff; }
.vp-hero-stats .stat-label { font-size: 0.8rem; color: var(--text-muted); margin-top: 4px; }

/* Jump Links */
.vp-jump {
    display: flex; justify-content: center; gap: 10px; flex-wrap: wrap;
    padding: 20px 0; border-bottom: 1px solid rgba(255,255,255,0.06);
    background: rgba(0,0,0,0.2); position: sticky; top: 60px; z-index: 50;
    backdrop-filter: blur(12px);
}
.vp-jump a {
    padding: 8px 16px; border-radius: 20px; font-size: 0.82rem; font-weight: 600;
    color: var(--text-muted); text-decoration: none; transition: all 0.2s;
    border: 1px solid rgba(255,255,255,0.08);
}
.vp-jump a:hover { color: #fff; background: rgba(125,0,255,0.15); border-color: rgba(125,0,255,0.3); }
.vp-jump a i { margin-right: 6px; }

/* Product Sections */
.vp-section {
    padding: 80px 0;
    border-bottom: 1px solid rgba(255,255,255,0.04);
}
.vp-section:nth-child(even) {
    background: rgba(0,0,0,0.15);
}
.vp-section-header {
    text-align: center; margin-bottom: 48px;
}
.vp-section-header .section-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 5px 14px; border-radius: 20px; font-size: 0.82rem;
    font-weight: 600; margin-bottom: 12px;
}
.vp-section-header h2 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: clamp(1.5rem, 3vw, 2.2rem);
    font-weight: 700; color: #fff; margin: 0 0 12px;
}
.vp-section-header p {
    color: var(--text-muted); font-size: 1rem; max-width: 600px; margin: 0 auto;
}

/* Product Cards */
.vp-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px; max-width: 1200px; margin: 0 auto;
}
.vp-card {
    background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06);
    border-radius: 16px; padding: 28px; transition: all 0.3s;
    display: flex; flex-direction: column;
}
.vp-card:hover {
    border-color: rgba(125,0,255,0.3); transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.2);
}
.vp-card .card-icon { font-size: 2rem; margin-bottom: 12px; }
.vp-card h3 { font-size: 1.1rem; font-weight: 700; color: #fff; margin: 0 0 8px; }
.vp-card .card-price {
    font-size: 1.3rem; font-weight: 800; margin: 8px 0 12px;
}
.vp-card .card-price .currency { font-size: 0.85rem; color: var(--text-muted); }
.vp-card .card-price .period { font-size: 0.8rem; color: var(--text-muted); font-weight: 400; }
.vp-card p { font-size: 0.88rem; color: var(--text-muted); line-height: 1.5; flex: 1; }
.vp-card .card-features {
    list-style: none; padding: 0; margin: 12px 0; font-size: 0.85rem;
}
.vp-card .card-features li {
    padding: 4px 0; color: var(--text-muted);
}
.vp-card .card-features li i { color: #10b981; margin-right: 6px; font-size: 0.75rem; }
.vp-card .card-cta {
    display: inline-flex; align-items: center; justify-content: center;
    padding: 10px 20px; border-radius: 10px; font-size: 0.88rem;
    font-weight: 600; text-decoration: none; margin-top: 16px;
    transition: all 0.2s; gap: 6px;
}
.vp-card .card-cta.primary {
    background: linear-gradient(135deg, #7d00ff, #5b00cc);
    color: #fff;
}
.vp-card .card-cta.primary:hover { background: linear-gradient(135deg, #8b11ff, #6b11dd); }
.vp-card .card-cta.ghost {
    border: 1px solid rgba(255,255,255,0.15); color: #fff;
    background: transparent;
}
.vp-card .card-cta.ghost:hover { border-color: rgba(125,0,255,0.4); background: rgba(125,0,255,0.1); }

/* Popular badge */
.vp-card.popular { border-color: rgba(125,0,255,0.4); position: relative; }
.vp-card.popular::before {
    content: 'MOST POPULAR';
    position: absolute; top: -10px; right: 20px;
    background: linear-gradient(135deg, #7d00ff, #00d4ff);
    color: #fff; font-size: 0.7rem; font-weight: 700;
    padding: 3px 12px; border-radius: 10px;
}

/* Pricing table for agent packages */
.vp-pricing-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 20px; max-width: 1200px; margin: 0 auto;
}

/* Industry solutions grid */
.vp-industry-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 16px; max-width: 1200px; margin: 0 auto;
}
.vp-industry-card {
    background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06);
    border-radius: 12px; padding: 20px; text-align: center;
    transition: all 0.3s; text-decoration: none; display: block;
}
.vp-industry-card:hover {
    border-color: rgba(125,0,255,0.3); transform: translateY(-2px);
}
.vp-industry-card .ind-icon { font-size: 2.2rem; margin-bottom: 8px; }
.vp-industry-card h4 { font-size: 1rem; color: #fff; margin: 0 0 6px; font-weight: 700; }
.vp-industry-card .ind-price { font-size: 1.1rem; font-weight: 800; color: #7d00ff; }
.vp-industry-card .ind-price small { font-size: 0.75rem; color: var(--text-muted); font-weight: 400; }

/* Business Models Section */
.vp-business {
    padding: 80px 0;
    background: linear-gradient(180deg, rgba(125,0,255,0.04) 0%, transparent 100%);
}
.vp-business-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 24px; max-width: 1200px; margin: 0 auto;
}
.vp-business-card {
    background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06);
    border-radius: 16px; padding: 32px; transition: all 0.3s;
}
.vp-business-card:hover { border-color: rgba(125,0,255,0.3); }
.vp-business-card .biz-icon {
    width: 52px; height: 52px; border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem; margin-bottom: 16px;
}
.vp-business-card h3 { font-size: 1.15rem; font-weight: 700; color: #fff; margin: 0 0 8px; }
.vp-business-card p { font-size: 0.9rem; color: var(--text-muted); line-height: 1.6; margin: 0 0 16px; }
.vp-business-card .biz-highlights {
    list-style: none; padding: 0; margin: 0;
}
.vp-business-card .biz-highlights li {
    padding: 5px 0; font-size: 0.85rem; color: var(--text-muted);
}
.vp-business-card .biz-highlights li i {
    color: #10b981; margin-right: 8px; width: 14px; text-align: center;
}

/* Final CTA */
.vp-final-cta {
    padding: 80px 0; text-align: center;
    background: linear-gradient(180deg, transparent, rgba(125,0,255,0.06));
}
.vp-final-cta h2 { font-family: 'Space Grotesk', sans-serif; font-size: 2rem; font-weight: 800; color: #fff; margin-bottom: 16px; }
.vp-final-cta p { color: var(--text-muted); max-width: 600px; margin: 0 auto 28px; font-size: 1.05rem; }
.vp-final-cta .cta-btns { display: flex; justify-content: center; gap: 12px; flex-wrap: wrap; }
.vp-final-cta .btn-voice {
    background: linear-gradient(135deg, #06b6d4, #0088cc);
    color: #fff; padding: 14px 28px; border-radius: 12px;
    font-weight: 700; text-decoration: none; font-size: 1rem;
    display: inline-flex; align-items: center; gap: 8px;
}
.vp-final-cta .btn-voice:hover { filter: brightness(1.15); }

@media (max-width: 768px) {
    .vp-hero { padding: 80px 20px 40px; }
    .vp-hero-stats { gap: 12px; }
    .vp-hero-stats .stat { min-width: 90px; padding: 10px 14px; }
    .vp-jump { gap: 6px; padding: 12px 10px; }
    .vp-jump a { font-size: 0.75rem; padding: 6px 10px; }
    .vp-section { padding: 50px 20px; }
    .vp-grid { grid-template-columns: 1fr; }
    .vp-pricing-grid { grid-template-columns: 1fr; }
    .vp-industry-grid { grid-template-columns: repeat(2, 1fr); }
    .vp-business-grid { grid-template-columns: 1fr; }
}
</style>

<main>

<!-- ═══ HERO ═══ -->
<section class="vp-hero">
    <div class="container">
        <div class="vp-hero-badge"><i class="fas fa-phone-volume"></i> Voice & AI Products</div>
        <h1>AI Phone Agents for <span class="gradient">Every Business</span></h1>
        <p>From a $3/month local number to a full AI-powered call center — pick exactly what your business needs. À la carte, bundled packages, or turnkey industry solutions.</p>
        <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
            <a href="<?php echo billing_link('store/ai-voice-agents'); ?>" class="btn btn-primary" style="padding:14px 28px;font-size:1rem;border-radius:12px;"><i class="fas fa-rocket" style="margin-right:8px;"></i> Browse All Products</a>
            <a href="tel:+18077982850" class="btn btn-ghost" style="padding:14px 28px;font-size:1rem;border-radius:12px;"><i class="fas fa-phone" style="margin-right:8px;"></i> Call Alfred — Try It Live</a>
            <a href="/voice.php" class="btn btn-ghost" style="padding:14px 28px;font-size:1rem;border-radius:12px;background:rgba(6,182,212,0.1);border-color:rgba(6,182,212,0.3);color:#06b6d4;"><i class="fas fa-microphone" style="margin-right:8px;"></i> Talk to Alfred Online</a>
        </div>
        <div class="vp-hero-stats">
            <div class="stat"><div class="stat-val">52</div><div class="stat-label">Products</div></div>
            <div class="stat"><div class="stat-val">$3</div><div class="stat-label">Starting Price</div></div>
            <div class="stat"><div class="stat-val">12</div><div class="stat-label">Industries</div></div>
            <div class="stat"><div class="stat-val">30+</div><div class="stat-label">Languages</div></div>
            <div class="stat"><div class="stat-val">24/7</div><div class="stat-label">Always On</div></div>
        </div>
    </div>
</section>

<!-- ═══ JUMP LINKS ═══ -->
<nav class="vp-jump">
    <a href="#agents"><i class="fas fa-robot"></i> AI Agents</a>
    <a href="#alacarte"><i class="fas fa-phone"></i> À La Carte</a>
    <a href="#callcenter"><i class="fas fa-headset"></i> Call Centers</a>
    <a href="#industry"><i class="fas fa-industry"></i> Industry Solutions</a>
    <a href="#addons"><i class="fas fa-puzzle-piece"></i> Add-Ons</a>
    <a href="#business"><i class="fas fa-handshake"></i> Business Programs</a>
</nav>

<!-- ═══ AI VOICE AGENT PACKAGES ═══ -->
<section class="vp-section" id="agents">
    <div class="container">
        <div class="vp-section-header">
            <div class="section-badge" style="background:rgba(125,0,255,0.15);color:#a78bfa;border:1px solid rgba(125,0,255,0.3);"><i class="fas fa-robot"></i> AI Voice Agents</div>
            <h2>Alfred AI Agent Packages</h2>
            <p>Full-featured AI phone agents that answer calls, book appointments, qualify leads, and handle customer service — 24/7, in 30+ languages.</p>
        </div>
        <div class="vp-pricing-grid">
            <div class="vp-card">
                <div class="card-icon">🚀</div>
                <h3>Starter Agent</h3>
                <div class="card-price" style="color:#10b981;"><span class="currency">$</span>29<span class="period">/month</span></div>
                <ul class="card-features">
                    <li><i class="fas fa-check"></i> 1 AI agent</li>
                    <li><i class="fas fa-check"></i> 100 minutes/month</li>
                    <li><i class="fas fa-check"></i> 1 phone number included</li>
                    <li><i class="fas fa-check"></i> Call recording</li>
                    <li><i class="fas fa-check"></i> Basic analytics</li>
                    <li><i class="fas fa-check"></i> Email notifications</li>
                </ul>
                <a href="<?php echo billing_link('cart.php?a=add&pid=49'); ?>" class="card-cta primary"><i class="fas fa-shopping-cart"></i> Get Started</a>
            </div>
            <div class="vp-card popular">
                <div class="card-icon">💼</div>
                <h3>Business Agent</h3>
                <div class="card-price" style="color:#7d00ff;"><span class="currency">$</span>79<span class="period">/month</span></div>
                <ul class="card-features">
                    <li><i class="fas fa-check"></i> 3 AI agents</li>
                    <li><i class="fas fa-check"></i> 500 minutes/month</li>
                    <li><i class="fas fa-check"></i> 3 phone numbers</li>
                    <li><i class="fas fa-check"></i> CRM integration</li>
                    <li><i class="fas fa-check"></i> Call transfers</li>
                    <li><i class="fas fa-check"></i> SMS follow-ups</li>
                    <li><i class="fas fa-check"></i> Priority support</li>
                </ul>
                <a href="<?php echo billing_link('cart.php?a=add&pid=50'); ?>" class="card-cta primary"><i class="fas fa-shopping-cart"></i> Most Popular</a>
            </div>
            <div class="vp-card">
                <div class="card-icon">⚡</div>
                <h3>Professional Agent</h3>
                <div class="card-price" style="color:#06b6d4;"><span class="currency">$</span>199<span class="period">/month</span></div>
                <ul class="card-features">
                    <li><i class="fas fa-check"></i> 10 AI agents</li>
                    <li><i class="fas fa-check"></i> 2,000 minutes/month</li>
                    <li><i class="fas fa-check"></i> 10 phone numbers</li>
                    <li><i class="fas fa-check"></i> Custom voice & personality</li>
                    <li><i class="fas fa-check"></i> API access</li>
                    <li><i class="fas fa-check"></i> Zapier integration</li>
                    <li><i class="fas fa-check"></i> Dedicated support</li>
                </ul>
                <a href="<?php echo billing_link('cart.php?a=add&pid=51'); ?>" class="card-cta primary"><i class="fas fa-shopping-cart"></i> Go Pro</a>
            </div>
            <div class="vp-card">
                <div class="card-icon">🏢</div>
                <h3>Enterprise Agent</h3>
                <div class="card-price" style="color:#fbbf24;"><span class="currency">$</span>499<span class="period">/month</span></div>
                <ul class="card-features">
                    <li><i class="fas fa-check"></i> Unlimited agents</li>
                    <li><i class="fas fa-check"></i> 10,000 minutes/month</li>
                    <li><i class="fas fa-check"></i> Unlimited numbers</li>
                    <li><i class="fas fa-check"></i> SSO & SAML</li>
                    <li><i class="fas fa-check"></i> HIPAA available</li>
                    <li><i class="fas fa-check"></i> Custom integrations</li>
                    <li><i class="fas fa-check"></i> SLA guarantee</li>
                    <li><i class="fas fa-check"></i> Account manager</li>
                </ul>
                <a href="<?php echo billing_link('cart.php?a=add&pid=52'); ?>" class="card-cta primary"><i class="fas fa-shopping-cart"></i> Contact Sales</a>
            </div>
        </div>
    </div>
</section>

<!-- ═══ À LA CARTE — Phone Numbers, Fax, SMS ═══ -->
<section class="vp-section" id="alacarte">
    <div class="container">
        <div class="vp-section-header">
            <div class="section-badge" style="background:rgba(0,212,255,0.15);color:#06b6d4;border:1px solid rgba(0,212,255,0.3);"><i class="fas fa-phone"></i> À La Carte</div>
            <h2>Just Need a Number? Fax? SMS?</h2>
            <p>No package required. Buy exactly what your business needs — add an AI agent later if you want.</p>
        </div>
        <div class="vp-grid">
            <!-- Phone Numbers -->
            <div class="vp-card">
                <div class="card-icon">📞</div>
                <h3>Local Phone Number</h3>
                <div class="card-price" style="color:#10b981;"><span class="currency">$</span>3<span class="period">/month</span></div>
                <p>US or Canadian local number. Instant activation. Port your existing number or get a new one in your area code.</p>
                <a href="<?php echo billing_link('cart.php?a=add&pid=59'); ?>" class="card-cta ghost"><i class="fas fa-plus"></i> Add to Cart</a>
            </div>
            <div class="vp-card">
                <div class="card-icon">🆓</div>
                <h3>Toll-Free Number</h3>
                <div class="card-price" style="color:#10b981;"><span class="currency">$</span>5<span class="period">/month</span></div>
                <p>1-800, 888, 877, 866 numbers. Professional image for any business. Instant setup.</p>
                <a href="<?php echo billing_link('cart.php?a=add&pid=60'); ?>" class="card-cta ghost"><i class="fas fa-plus"></i> Add to Cart</a>
            </div>
            <div class="vp-card">
                <div class="card-icon">✨</div>
                <h3>Vanity Number</h3>
                <div class="card-price" style="color:#10b981;"><span class="currency">$</span>15<span class="period">/month</span></div>
                <p>Get a memorable number like 1-800-FLOWERS. Customers remember you instantly.</p>
                <a href="<?php echo billing_link('cart.php?a=add&pid=61'); ?>" class="card-cta ghost"><i class="fas fa-plus"></i> Add to Cart</a>
            </div>
            <div class="vp-card">
                <div class="card-icon">🌍</div>
                <h3>International Number</h3>
                <div class="card-price" style="color:#10b981;"><span class="currency">$</span>10<span class="period">/month</span></div>
                <p>Numbers in 60+ countries. Give your business a local presence anywhere in the world.</p>
                <a href="<?php echo billing_link('cart.php?a=add&pid=62'); ?>" class="card-cta ghost"><i class="fas fa-plus"></i> Add to Cart</a>
            </div>
            <div class="vp-card">
                <div class="card-icon">🔌</div>
                <h3>SIP Trunk</h3>
                <div class="card-price" style="color:#10b981;"><span class="currency">$</span>10<span class="period">/month</span></div>
                <p>Bring your own carrier. Connect existing PBX or phone system to our AI platform.</p>
                <a href="<?php echo billing_link('cart.php?a=add&pid=63'); ?>" class="card-cta ghost"><i class="fas fa-plus"></i> Add to Cart</a>
            </div>
            <div class="vp-card">
                <div class="card-icon">📦</div>
                <h3>10-Number Bundle</h3>
                <div class="card-price" style="color:#10b981;"><span class="currency">$</span>25<span class="period">/month</span></div>
                <p>10 local numbers at a bulk discount. Perfect for multi-location businesses or teams.</p>
                <a href="<?php echo billing_link('cart.php?a=add&pid=64'); ?>" class="card-cta ghost"><i class="fas fa-plus"></i> Add to Cart</a>
            </div>
            <!-- Fax -->
            <div class="vp-card">
                <div class="card-icon">📠</div>
                <h3>AI Fax — Standard</h3>
                <div class="card-price" style="color:#10b981;"><span class="currency">$</span>14.99<span class="period">/month</span></div>
                <p>Send & receive faxes digitally. AI reads and categorizes incoming faxes automatically.</p>
                <a href="<?php echo billing_link('cart.php?a=add&pid=67'); ?>" class="card-cta ghost"><i class="fas fa-plus"></i> Add to Cart</a>
            </div>
            <div class="vp-card">
                <div class="card-icon">📄</div>
                <h3>AI Fax — Professional</h3>
                <div class="card-price" style="color:#10b981;"><span class="currency">$</span>39.99<span class="period">/month</span></div>
                <p>High-volume fax with OCR, auto-routing, HIPAA compliance, and electronic signature.</p>
                <a href="<?php echo billing_link('cart.php?a=add&pid=68'); ?>" class="card-cta ghost"><i class="fas fa-plus"></i> Add to Cart</a>
            </div>
            <!-- SMS -->
            <div class="vp-card">
                <div class="card-icon">💬</div>
                <h3>AI SMS Agent — Starter</h3>
                <div class="card-price" style="color:#10b981;"><span class="currency">$</span>29<span class="period">/month</span></div>
                <p>AI-powered text messaging. Automated appointment reminders, lead follow-ups, review requests.</p>
                <a href="<?php echo billing_link('cart.php?a=add&pid=75'); ?>" class="card-cta ghost"><i class="fas fa-plus"></i> Add to Cart</a>
            </div>
            <div class="vp-card">
                <div class="card-icon">📱</div>
                <h3>AI SMS Agent — Business</h3>
                <div class="card-price" style="color:#10b981;"><span class="currency">$</span>79<span class="period">/month</span></div>
                <p>Full conversational SMS. Two-way AI texting that books appointments, qualifies leads, handles support.</p>
                <a href="<?php echo billing_link('cart.php?a=add&pid=76'); ?>" class="card-cta ghost"><i class="fas fa-plus"></i> Add to Cart</a>
            </div>
            <div class="vp-card">
                <div class="card-icon">🗨️</div>
                <h3>AI Live Chat Widget</h3>
                <div class="card-price" style="color:#10b981;"><span class="currency">$</span>19<span class="period">/month</span></div>
                <p>Drop a chat widget on your website. AI answers visitor questions, captures leads, 24/7.</p>
                <a href="<?php echo billing_link('cart.php?a=add&pid=77'); ?>" class="card-cta ghost"><i class="fas fa-plus"></i> Add to Cart</a>
            </div>
            <!-- Documents -->
            <div class="vp-card">
                <div class="card-icon">📝</div>
                <h3>AI Document Generator</h3>
                <div class="card-price" style="color:#10b981;"><span class="currency">$</span>19<span class="period">/month</span></div>
                <p>Generate contracts, invoices, proposals, and reports with AI. Templates included.</p>
                <a href="<?php echo billing_link('cart.php?a=add&pid=65'); ?>" class="card-cta ghost"><i class="fas fa-plus"></i> Add to Cart</a>
            </div>
        </div>
    </div>
</section>

<!-- ═══ AI CALL CENTER & TELEMARKETING ═══ -->
<section class="vp-section" id="callcenter">
    <div class="container">
        <div class="vp-section-header">
            <div class="section-badge" style="background:rgba(255,51,102,0.15);color:#ff3366;border:1px solid rgba(255,51,102,0.3);"><i class="fas fa-headset"></i> Call Center Solutions</div>
            <h2>AI-Powered Call Centers</h2>
            <p>Replace entire teams or augment your staff. Outbound dialing, inbound support, appointment setting, and collections — all AI-driven.</p>
        </div>
        <div class="vp-grid">
            <div class="vp-card">
                <div class="card-icon">📤</div>
                <h3>Outbound Dialer — Starter</h3>
                <div class="card-price" style="color:#ff3366;"><span class="currency">$</span>99<span class="period">/month</span></div>
                <p>AI makes outbound calls for lead qualification, surveys, appointment confirmations. 500 calls/month.</p>
                <a href="<?php echo billing_link('cart.php?a=add&pid=53'); ?>" class="card-cta primary">Get Started</a>
            </div>
            <div class="vp-card popular">
                <div class="card-icon">📞</div>
                <h3>Outbound Dialer — Pro</h3>
                <div class="card-price" style="color:#ff3366;"><span class="currency">$</span>299<span class="period">/month</span></div>
                <p>2,500 calls/month. CRM integration, custom scripts, A/B testing, detailed analytics.</p>
                <a href="<?php echo billing_link('cart.php?a=add&pid=54'); ?>" class="card-cta primary">Scale Up</a>
            </div>
            <div class="vp-card">
                <div class="card-icon">🏬</div>
                <h3>Outbound Dialer — Enterprise</h3>
                <div class="card-price" style="color:#ff3366;"><span class="currency">$</span>999<span class="period">/month</span></div>
                <p>Unlimited calls. Multi-campaign, predictive dialing, compliance tools, dedicated account manager.</p>
                <a href="<?php echo billing_link('cart.php?a=add&pid=55'); ?>" class="card-cta primary">Contact Sales</a>
            </div>
            <div class="vp-card">
                <div class="card-icon">📥</div>
                <h3>AI Inbound Call Center</h3>
                <div class="card-price" style="color:#ff3366;"><span class="currency">$</span>499<span class="period">/month</span></div>
                <p>Full inbound customer service. IVR replacement. Multi-agent routing, escalation, real-time dashboards.</p>
                <a href="<?php echo billing_link('cart.php?a=add&pid=56'); ?>" class="card-cta primary">Deploy Now</a>
            </div>
            <div class="vp-card">
                <div class="card-icon">📅</div>
                <h3>AI Appointment Setter</h3>
                <div class="card-price" style="color:#ff3366;"><span class="currency">$</span>149<span class="period">/month</span></div>
                <p>Books appointments by phone & text. Calendar integration (Google, Outlook, Calendly). Reminders included.</p>
                <a href="<?php echo billing_link('cart.php?a=add&pid=57'); ?>" class="card-cta primary">Start Booking</a>
            </div>
            <div class="vp-card">
                <div class="card-icon">💰</div>
                <h3>AI Collections Agent</h3>
                <div class="card-price" style="color:#ff3366;"><span class="currency">$</span>249<span class="period">/month</span></div>
                <p>Professional, compliant collections calls. Payment plans, reminders, FDCPA compliance built in.</p>
                <a href="<?php echo billing_link('cart.php?a=add&pid=58'); ?>" class="card-cta primary">Recover Revenue</a>
            </div>
        </div>
    </div>
</section>

<!-- ═══ AI OFFICE SUITE ═══ -->
<section class="vp-section" id="office">
    <div class="container">
        <div class="vp-section-header">
            <div class="section-badge" style="background:rgba(0,255,136,0.15);color:#00ff88;border:1px solid rgba(0,255,136,0.3);"><i class="fas fa-building"></i> AI Office Suite</div>
            <h2>AI Staff for Your Business</h2>
            <p>Virtual receptionist, executive assistant, bookkeeper, sales agent, and customer service — all AI-powered.</p>
        </div>
        <div class="vp-grid">
            <div class="vp-card">
                <div class="card-icon">🎧</div>
                <h3>AI Virtual Receptionist</h3>
                <div class="card-price" style="color:#00ff88;"><span class="currency">$</span>49<span class="period">/month</span></div>
                <p>Answers every call professionally. Routes to the right department. Takes messages. Never calls in sick.</p>
                <a href="<?php echo billing_link('cart.php?a=add&pid=70'); ?>" class="card-cta ghost"><i class="fas fa-plus"></i> Add to Cart</a>
            </div>
            <div class="vp-card">
                <div class="card-icon">👔</div>
                <h3>AI Executive Assistant</h3>
                <div class="card-price" style="color:#00ff88;"><span class="currency">$</span>39<span class="period">/month</span></div>
                <p>Manages your calendar, screens calls, sends follow-ups, prepares meeting summaries.</p>
                <a href="<?php echo billing_link('cart.php?a=add&pid=71'); ?>" class="card-cta ghost"><i class="fas fa-plus"></i> Add to Cart</a>
            </div>
            <div class="vp-card">
                <div class="card-icon">🛎️</div>
                <h3>AI Customer Service Desk</h3>
                <div class="card-price" style="color:#00ff88;"><span class="currency">$</span>149<span class="period">/month</span></div>
                <p>Handles support tickets via phone, chat, and email. Resolves common issues autonomously.</p>
                <a href="<?php echo billing_link('cart.php?a=add&pid=72'); ?>" class="card-cta ghost"><i class="fas fa-plus"></i> Add to Cart</a>
            </div>
            <div class="vp-card">
                <div class="card-icon">📊</div>
                <h3>AI Bookkeeper</h3>
                <div class="card-price" style="color:#00ff88;"><span class="currency">$</span>59<span class="period">/month</span></div>
                <p>Tracks expenses, generates invoices, categorizes transactions, prepares financial reports.</p>
                <a href="<?php echo billing_link('cart.php?a=add&pid=73'); ?>" class="card-cta ghost"><i class="fas fa-plus"></i> Add to Cart</a>
            </div>
            <div class="vp-card">
                <div class="card-icon">🤝</div>
                <h3>AI Sales Agent</h3>
                <div class="card-price" style="color:#00ff88;"><span class="currency">$</span>199<span class="period">/month</span></div>
                <p>Qualifies leads, delivers pitches, handles objections, closes deals. Trained on your products.</p>
                <a href="<?php echo billing_link('cart.php?a=add&pid=74'); ?>" class="card-cta ghost"><i class="fas fa-plus"></i> Add to Cart</a>
            </div>
            <div class="vp-card">
                <div class="card-icon">✍️</div>
                <h3>AI E-Signature</h3>
                <div class="card-price" style="color:#00ff88;"><span class="currency">$</span>19.99<span class="period">/month</span></div>
                <p>Send documents for signature. Track status. Legally binding. Integrated with document generator.</p>
                <a href="<?php echo billing_link('cart.php?a=add&pid=69'); ?>" class="card-cta ghost"><i class="fas fa-plus"></i> Add to Cart</a>
            </div>
        </div>
    </div>
</section>

<!-- ═══ INDUSTRY SOLUTIONS ═══ -->
<section class="vp-section" id="industry">
    <div class="container">
        <div class="vp-section-header">
            <div class="section-badge" style="background:rgba(255,165,0,0.15);color:#ff9500;border:1px solid rgba(255,165,0,0.3);"><i class="fas fa-industry"></i> Industry Solutions</div>
            <h2>Pre-Built for Your Industry</h2>
            <p>Turnkey AI packages preconfigured for your vertical. Trained on industry terminology, common scenarios, and compliance requirements.</p>
        </div>
        <div class="vp-industry-grid">
            <?php
            $industries = [
                ['🍕', 'Restaurant AI', 99, 78, 'Reservations, orders, hours, specials, dietary info'],
                ['🏠', 'Real Estate AI', 149, 79, 'Listings, showings, pre-qualification, follow-ups'],
                ['🏥', 'Medical & Dental AI', 249, 80, 'Appointments, insurance, prescriptions, HIPAA'],
                ['⚖️', 'Legal AI', 199, 81, 'Intake, consultations, case status, conflicts checks'],
                ['🔧', 'Home Services AI', 79, 82, 'Scheduling, quotes, dispatch, emergency routing'],
                ['🛡️', 'Insurance AI', 179, 83, 'Quotes, claims status, policy info, renewals'],
                ['🚗', 'Automotive AI', 149, 84, 'Service booking, inventory, test drives, recalls'],
                ['💅', 'Salon & Spa AI', 59, 85, 'Bookings, services menu, waitlist, promotions'],
                ['🏢', 'Property Management AI', 129, 86, 'Maintenance requests, tours, lease info'],
                ['🛒', 'E-Commerce AI', 99, 87, 'Order status, returns, product recommendations'],
                ['📈', 'Accounting & Tax AI', 129, 88, 'Tax status, document collection, scheduling'],
                ['🏋️', 'Fitness & Gym AI', 59, 89, 'Memberships, class schedules, PT bookings']
            ];
            foreach ($industries as $ind):
            ?>
            <a href="<?php echo billing_link("cart.php?a=add&pid={$ind[3]}"); ?>" class="vp-industry-card">
                <div class="ind-icon"><?php echo $ind[0]; ?></div>
                <h4><?php echo $ind[1]; ?></h4>
                <div class="ind-price">$<?php echo $ind[2]; ?><small>/mo</small></div>
                <p style="font-size:0.8rem;color:var(--text-muted);margin-top:6px;"><?php echo $ind[4]; ?></p>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ═══ ADD-ONS & MINUTES ═══ -->
<section class="vp-section" id="addons">
    <div class="container">
        <div class="vp-section-header">
            <div class="section-badge" style="background:rgba(153,102,255,0.15);color:#9966ff;border:1px solid rgba(153,102,255,0.3);"><i class="fas fa-puzzle-piece"></i> Add-Ons</div>
            <h2>Scale As You Grow</h2>
            <p>Extra minutes, more agents, voice cloning, HIPAA compliance, and more — add to any plan.</p>
        </div>
        <div class="vp-grid">
            <?php
            $addons = [
                ['⏱️', 'Extra 250 Minutes', 19.99, 90, 'Add minutes to any plan'],
                ['⏱️', 'Extra 1,000 Minutes', 69.99, 91, 'High-volume calling'],
                ['⏱️', 'Extra 5,000 Minutes', 299.99, 92, 'Enterprise volume'],
                ['🎙️', 'Call Recording — 50 GB', 9.99, 93, 'Store & search recordings'],
                ['🗣️', 'Voice Cloning', 149, 94, 'Custom AI voice trained on your recordings'],
                ['🤖', 'Extra Agent Slot', 9.99, 95, 'Add more AI agents to your plan'],
                ['📞', 'Extra Concurrent Line', 12, 96, 'Handle more simultaneous calls'],
                ['🏥', 'HIPAA Compliance', 99, 97, 'BAA, encryption, audit logs'],
                ['💬', 'SMS Bundle — 1,000', 14.99, 99, '1,000 outbound text messages'],
                ['🌐', 'Multi-Language Pack', 29, 100, 'All 30+ supported languages']
            ];
            foreach ($addons as $a):
            ?>
            <div class="vp-card">
                <div class="card-icon"><?php echo $a[0]; ?></div>
                <h3><?php echo $a[1]; ?></h3>
                <div class="card-price" style="color:#9966ff;"><span class="currency">$</span><?php echo $a[2]; ?><span class="period">/month</span></div>
                <p><?php echo $a[4]; ?></p>
                <a href="<?php echo billing_link("cart.php?a=add&pid={$a[3]}"); ?>" class="card-cta ghost"><i class="fas fa-plus"></i> Add</a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ═══ BUSINESS PROGRAMS — Reseller, Affiliate, White-Label, Partner ═══ -->
<section class="vp-business" id="business">
    <div class="container">
        <div class="vp-section-header">
            <div class="section-badge" style="background:rgba(255,215,0,0.15);color:#ffd700;border:1px solid rgba(255,215,0,0.3);"><i class="fas fa-handshake"></i> Business Programs</div>
            <h2>Grow With Us — Resell, Refer, Partner</h2>
            <p>Multiple ways to profit from the AI voice revolution. Whether you're an agency, MSP, consultant, or influencer.</p>
        </div>
        <div class="vp-business-grid">
            <!-- Reseller / White-Label -->
            <div class="vp-business-card">
                <div class="biz-icon" style="background:rgba(125,0,255,0.15);color:#a78bfa;">🏷️</div>
                <h3>White-Label Reseller</h3>
                <p>Rebrand our entire Voice & AI platform as your own. Your logo, your domain, your pricing. We handle the infrastructure — you keep the margin.</p>
                <ul class="biz-highlights">
                    <li><i class="fas fa-check"></i> Your brand on everything</li>
                    <li><i class="fas fa-check"></i> Set your own pricing & margins</li>
                    <li><i class="fas fa-check"></i> Client management dashboard</li>
                    <li><i class="fas fa-check"></i> Wholesale rates from $299/mo</li>
                    <li><i class="fas fa-check"></i> Unlimited sub-accounts</li>
                    <li><i class="fas fa-check"></i> API access for automation</li>
                </ul>
                <a href="<?php echo billing_link('cart.php?a=add&pid=98'); ?>" class="card-cta primary" style="display:inline-flex;margin-top:16px;">Start Reselling</a>
            </div>
            <!-- Affiliate Program -->
            <div class="vp-business-card">
                <div class="biz-icon" style="background:rgba(16,185,129,0.15);color:#10b981;">💸</div>
                <h3>Affiliate Program — 20% Commission</h3>
                <p>Refer customers and earn 20% recurring commission on every sale. No cap. No limits. Payment every month at $25 minimum payout.</p>
                <ul class="biz-highlights">
                    <li><i class="fas fa-check"></i> 20% recurring commission</li>
                    <li><i class="fas fa-check"></i> Cookie lasts 90 days</li>
                    <li><i class="fas fa-check"></i> Real-time tracking dashboard</li>
                    <li><i class="fas fa-check"></i> Marketing materials provided</li>
                    <li><i class="fas fa-check"></i> No minimum referrals</li>
                    <li><i class="fas fa-check"></i> PayPal & bank transfer payouts</li>
                </ul>
                <a href="<?php echo billing_link('affiliates.php'); ?>" class="card-cta primary" style="display:inline-flex;margin-top:16px;background:linear-gradient(135deg,#10b981,#059669);">Join Affiliate Program</a>
            </div>
            <!-- Partner / Agency -->
            <div class="vp-business-card">
                <div class="biz-icon" style="background:rgba(6,182,212,0.15);color:#06b6d4;">🤝</div>
                <h3>Agency & MSP Partner</h3>
                <p>Offer AI voice services to your existing clients. Volume discounts, co-branded solutions, dedicated partner support, and priority feature requests.</p>
                <ul class="biz-highlights">
                    <li><i class="fas fa-check"></i> Volume & bulk pricing</li>
                    <li><i class="fas fa-check"></i> Co-branded client portals</li>
                    <li><i class="fas fa-check"></i> Partner API access</li>
                    <li><i class="fas fa-check"></i> Priority support queue</li>
                    <li><i class="fas fa-check"></i> Custom onboarding for your clients</li>
                    <li><i class="fas fa-check"></i> Revenue sharing options</li>
                </ul>
                <a href="<?php echo billing_link('contact.php'); ?>" class="card-cta primary" style="display:inline-flex;margin-top:16px;background:linear-gradient(135deg,#06b6d4,#0088cc);">Become a Partner</a>
            </div>
            <!-- Upsell -->
            <div class="vp-business-card">
                <div class="biz-icon" style="background:rgba(255,165,0,0.15);color:#ff9500;">📈</div>
                <h3>Upsells & Cross-Sells</h3>
                <p>Already a GoSiteMe hosting customer? Add voice & AI to your existing plan. Bundle pricing saves up to 30% vs buying separately.</p>
                <ul class="biz-highlights">
                    <li><i class="fas fa-check"></i> Bundle hosting + AI voice = save 30%</li>
                    <li><i class="fas fa-check"></i> One dashboard for everything</li>
                    <li><i class="fas fa-check"></i> Unified billing</li>
                    <li><i class="fas fa-check"></i> Add AI to existing website instantly</li>
                    <li><i class="fas fa-check"></i> Live chat widget included with hosting</li>
                    <li><i class="fas fa-check"></i> Voice + hosting + domain starting $18/mo</li>
                </ul>
                <a href="<?php echo billing_link('store/ai-voice-agents'); ?>" class="card-cta primary" style="display:inline-flex;margin-top:16px;background:linear-gradient(135deg,#ff9500,#cc6600);">Upgrade Now</a>
            </div>
        </div>
    </div>
</section>

<!-- ═══ FINAL CTA ═══ -->
<section class="vp-final-cta">
    <div class="container">
        <h2>Ready to Give Your Business a Voice?</h2>
        <p>Start with a $3 phone number or go all-in with a full AI agent. No contracts. Cancel anytime. Set up in under 10 minutes.</p>
        <div class="cta-btns">
            <a href="<?php echo billing_link('store/ai-voice-agents'); ?>" class="btn btn-primary" style="padding:14px 28px;font-size:1rem;border-radius:12px;"><i class="fas fa-rocket" style="margin-right:8px;"></i> Browse All Products</a>
            <a href="/voice.php" class="btn-voice"><i class="fas fa-microphone"></i> Talk to Alfred</a>
            <a href="tel:+18077982850" class="btn btn-ghost" style="padding:14px 28px;font-size:1rem;border-radius:12px;"><i class="fas fa-phone" style="margin-right:8px;"></i> Call: +1 (807) 798-2850</a>
        </div>
        <p style="margin-top:20px;font-size:0.9rem;color:var(--text-muted);">
            <strong style="color:#10b981;">Want to resell?</strong> <a href="<?php echo billing_link('cart.php?a=add&pid=98'); ?>" style="color:#a78bfa;">White-Label from $299/mo</a> &middot;
            <strong style="color:#10b981;">Want to refer?</strong> <a href="<?php echo billing_link('affiliates.php'); ?>" style="color:#a78bfa;">Earn 20% commission</a> &middot;
            <strong style="color:#10b981;">Need help?</strong> <a href="<?php echo billing_link('contact.php'); ?>" style="color:#a78bfa;">Contact us</a>
        </p>
    </div>
</section>

</main>

<!-- Schema.org: Product with AggregateOffer -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "Product",
    "name": "GoSiteMe Voice & AI Products",
    "description": "AI phone agents, local & toll-free numbers, fax, SMS, call centers, industry solutions. From $3/month.",
    "brand": {"@type": "Brand", "name": "GoSiteMe"},
    "url": "https://gositeme.com/voice-products.php",
    "image": "https://gositeme.com/assets/hero-banner.png",
    "offers": {
        "@type": "AggregateOffer",
        "lowPrice": "3.00",
        "highPrice": "999.00",
        "priceCurrency": "USD",
        "offerCount": "52",
        "availability": "https://schema.org/InStock"
    },
    "aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": "4.9",
        "reviewCount": "127"
    }
}
</script>
<!-- Schema.org: ItemList of Voice Product Categories -->
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"ItemList","name":"Voice & AI Product Catalog","description":"52 Voice & AI products — AI phone agents, numbers, fax, SMS, call centers, and industry solutions.","url":"https://gositeme.com/voice-products.php","numberOfItems":"52","itemListElement":[{"@type":"ListItem","position":1,"name":"AI Phone Agents","url":"https://gositeme.com/voice-products.php#agents"},{"@type":"ListItem","position":2,"name":"Phone Numbers","url":"https://gositeme.com/voice-products.php#numbers"},{"@type":"ListItem","position":3,"name":"Fax Services","url":"https://gositeme.com/voice-products.php#fax"},{"@type":"ListItem","position":4,"name":"SMS Services","url":"https://gositeme.com/voice-products.php#sms"},{"@type":"ListItem","position":5,"name":"Call Centers","url":"https://gositeme.com/voice-products.php#call-centers"},{"@type":"ListItem","position":6,"name":"Industry Solutions","url":"https://gositeme.com/voice-products.php#industry"}]}
</script>
<!-- Schema.org: WebPage -->
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"WebPage","name":"Voice & AI Products — Phone Agents, Numbers, Fax, SMS","description":"Browse 52 Voice & AI products. AI phone agents from $29/mo, local numbers from $3/mo, fax, SMS, call centers, industry solutions.","url":"https://gositeme.com/voice-products.php","isPartOf":{"@type":"WebSite","name":"GoSiteMe","url":"https://gositeme.com"}}
</script>

<?php require_once __DIR__ . '/includes/site-footer.inc.php'; ?>
