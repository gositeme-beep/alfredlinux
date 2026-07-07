<?php
$page_title = 'Integration Roadmap - Alfred AI';
$page_description = 'Planned integrations for Alfred AI. See our roadmap for upcoming CRM, helpdesk, e-commerce, and platform connections.';
$page_canonical = 'https://root.com/integrations';
$page_og_title = 'Alfred AI Integration Roadmap — Planned Connections';
$page_og_description = 'See what integrations we\'re building for Alfred AI. CRMs, helpdesks, e-commerce platforms, and more on our roadmap.';
require_once __DIR__ . '/includes/site-header.inc.php';
?>

<style>
/* ===== Integrations Page Styles ===== */
:root {
    --int-bg: #0a0a14;
    --int-surface: #12121e;
    --int-surface-2: #1a1a2e;
    --int-border: rgba(255,255,255,0.08);
    --int-accent: #6c5ce7;
    --int-accent-light: #a29bfe;
    --int-green: #00b894;
    --int-blue: #0984e3;
    --int-orange: #fdcb6e;
    --int-red: #e17055;
    --int-pink: #fd79a8;
    --int-text: #e8e8f0;
    --int-text-muted: #8a8a9a;
    --int-radius: 16px;
    --int-radius-sm: 12px;
    --int-shadow: 0 4px 24px rgba(0,0,0,0.3);
    --int-transition: .3s cubic-bezier(.4,0,.2,1);
}

.int-page { background: var(--int-bg); color: var(--int-text); overflow-x: hidden; }
.int-page *, .int-page *::before, .int-page *::after { box-sizing: border-box; }
.int-container { max-width: 1200px; margin: 0 auto; padding: 0 24px; }
.int-section { padding: 80px 0; }
.int-section--alt { background: var(--int-surface); }

/* ---- Hero ---- */
.int-hero {
    padding: 140px 0 60px;
    text-align: center;
    background: linear-gradient(135deg, #0a0a14 0%, #1a1033 50%, #0a0a14 100%);
    position: relative;
    overflow: hidden;
}
.int-hero::before {
    content: '';
    position: absolute;
    top: -50%; left: -50%;
    width: 200%; height: 200%;
    background: radial-gradient(circle at 30% 40%, rgba(108,92,231,0.1) 0%, transparent 50%),
                radial-gradient(circle at 70% 60%, rgba(0,184,148,0.07) 0%, transparent 50%);
    animation: intHeroPulse 10s ease-in-out infinite alternate;
    pointer-events: none;
}
@keyframes intHeroPulse {
    0% { transform: scale(1) rotate(0deg); }
    100% { transform: scale(1.04) rotate(1.5deg); }
}
.int-hero .int-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 18px; border-radius: 50px; font-size: .78rem; font-weight: 600;
    letter-spacing: .5px; text-transform: uppercase;
    background: linear-gradient(135deg, rgba(108,92,231,.12), rgba(9,132,227,.12));
    color: var(--int-accent-light);
    border: 1px solid rgba(108,92,231,.2);
    margin-bottom: 20px;
    position: relative; z-index: 1;
}
.int-hero h1 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: clamp(2.2rem, 5vw, 3.8rem);
    font-weight: 800;
    margin: 0 0 16px;
    position: relative; z-index: 1;
    background: linear-gradient(135deg, #6c5ce7, #0984e3);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text;
}
.int-hero p {
    font-size: 1.2rem;
    color: var(--int-text-muted);
    max-width: 600px;
    margin: 0 auto 30px;
    position: relative; z-index: 1;
    line-height: 1.6;
}
.int-hero-stats {
    display: flex; justify-content: center; gap: 40px;
    position: relative; z-index: 1;
    flex-wrap: wrap;
}
.int-hero-stat {
    text-align: center;
}
.int-hero-stat .num {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 2rem; font-weight: 800;
    background: linear-gradient(135deg, var(--int-accent-light), var(--int-green));
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text;
}
.int-hero-stat .label {
    display: block; font-size: .85rem; color: var(--int-text-muted); margin-top: 4px;
}

/* ---- Search + Filter ---- */
.int-filter-bar {
    padding: 30px 0;
    position: sticky;
    top: 68px;
    z-index: 50;
    background: rgba(10,10,20,0.95);
    backdrop-filter: blur(12px);
    border-bottom: 1px solid var(--int-border);
}
.int-search-wrap {
    position: relative;
    max-width: 500px;
    margin: 0 auto 20px;
}
.int-search-wrap i {
    position: absolute;
    left: 16px; top: 50%; transform: translateY(-50%);
    color: var(--int-text-muted);
    pointer-events: none;
}
.int-search {
    width: 100%;
    padding: 14px 20px 14px 44px;
    background: var(--int-surface);
    border: 1px solid var(--int-border);
    border-radius: 50px;
    color: var(--int-text);
    font-size: 1rem;
    font-family: inherit;
    outline: none;
    transition: border-color var(--int-transition);
}
.int-search:focus {
    border-color: var(--int-accent);
    box-shadow: 0 0 0 3px rgba(108,92,231,0.15);
}
.int-search::placeholder { color: var(--int-text-muted); }
.int-tabs {
    display: flex;
    justify-content: center;
    gap: 8px;
    flex-wrap: wrap;
}
.int-tab {
    padding: 8px 20px;
    border-radius: 50px;
    border: 1px solid var(--int-border);
    background: transparent;
    color: var(--int-text-muted);
    font-size: .88rem;
    font-weight: 600;
    cursor: pointer;
    transition: all var(--int-transition);
    font-family: inherit;
}
.int-tab:hover, .int-tab.active {
    background: var(--int-accent);
    color: #fff;
    border-color: var(--int-accent);
}

/* ---- Integration Grid ---- */
.int-grid-section { padding: 60px 0 80px; }
.int-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 24px;
}
.int-card {
    background: var(--int-surface);
    border: 1px solid var(--int-border);
    border-radius: var(--int-radius);
    padding: 28px;
    transition: all var(--int-transition);
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}
.int-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--int-accent), var(--int-blue));
    opacity: 0;
    transition: opacity var(--int-transition);
}
.int-card:hover {
    border-color: rgba(108,92,231,0.3);
    transform: translateY(-4px);
    box-shadow: 0 12px 40px rgba(0,0,0,0.4);
}
.int-card:hover::before { opacity: 1; }
.int-card-top {
    display: flex; align-items: center; gap: 16px; margin-bottom: 16px;
}
.int-card-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem; font-weight: 800; color: #fff;
    flex-shrink: 0;
    letter-spacing: -0.5px;
}
.int-card-info h3 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.1rem; font-weight: 700;
    margin: 0 0 4px; color: #fff;
}
.int-card-cat {
    display: inline-block;
    padding: 2px 10px;
    border-radius: 50px;
    font-size: .7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .5px;
    background: rgba(108,92,231,0.15);
    color: var(--int-accent-light);
}
.int-card p {
    color: var(--int-text-muted);
    font-size: .92rem;
    line-height: 1.55;
    margin: 0 0 20px;
    flex: 1;
}
.int-card-actions {
    display: flex; gap: 10px; margin-top: auto;
}
.int-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 10px 20px; border: none; border-radius: 50px;
    font-size: .85rem; font-weight: 600; cursor: pointer;
    transition: all var(--int-transition); font-family: inherit;
    text-decoration: none;
}
.int-btn-primary {
    background: linear-gradient(135deg, #6c5ce7, #0984e3);
    color: #fff;
    box-shadow: 0 4px 16px rgba(108,92,231,0.3);
}
.int-btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 24px rgba(108,92,231,0.45);
    color: #fff; text-decoration: none;
}
.int-btn-ghost {
    background: transparent;
    border: 1px solid var(--int-border);
    color: var(--int-text-muted);
}
.int-btn-ghost:hover {
    border-color: var(--int-accent);
    color: var(--int-accent-light);
    text-decoration: none;
}
.int-no-results {
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px 20px;
    display: none;
}
.int-no-results i { font-size: 3rem; color: var(--int-text-muted); margin-bottom: 16px; }
.int-no-results h3 { font-size: 1.3rem; color: var(--int-text); margin: 0 0 8px; }
.int-no-results p { color: var(--int-text-muted); }

/* Category colors */
.int-cat-crm { background: linear-gradient(135deg, #6c5ce7, #a29bfe); }
.int-cat-ecommerce { background: linear-gradient(135deg, #00b894, #55efc4); }
.int-cat-helpdesk { background: linear-gradient(135deg, #0984e3, #74b9ff); }
.int-cat-communication { background: linear-gradient(135deg, #e17055, #fab1a0); }
.int-cat-payment { background: linear-gradient(135deg, #fdcb6e, #ffeaa7); color: #333 !important; }
.int-cat-analytics { background: linear-gradient(135deg, #fd79a8, #e84393); }
.int-cat-cloud { background: linear-gradient(135deg, #0984e3, #00cec9); }
.int-cat-voice { background: linear-gradient(135deg, #e17055, #d63031); }
.int-cat-productivity { background: linear-gradient(135deg, #fdcb6e, #e17055); color: #333 !important; }
.int-cat-devtools { background: linear-gradient(135deg, #636e72, #b2bec3); }

/* ---- API Section ---- */
.int-api-section {
    padding: 80px 0;
    background: var(--int-surface);
}
.int-api-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 48px;
    align-items: center;
}
.int-api-content h2 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: clamp(1.6rem, 3.5vw, 2.4rem);
    font-weight: 800;
    margin: 0 0 16px;
    background: linear-gradient(135deg, #6c5ce7, #0984e3);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text;
}
.int-api-content p {
    color: var(--int-text-muted);
    font-size: 1.05rem;
    line-height: 1.7;
    margin: 0 0 24px;
}
.int-api-features {
    list-style: none; padding: 0; margin: 0 0 30px;
}
.int-api-features li {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 0;
    color: var(--int-text);
    font-size: .95rem;
}
.int-api-features li i {
    color: var(--int-green);
    font-size: 1rem;
    width: 20px; text-align: center;
}
.int-api-code {
    background: #0d0d1a;
    border: 1px solid var(--int-border);
    border-radius: var(--int-radius);
    padding: 24px;
    overflow-x: auto;
}
.int-api-code pre {
    margin: 0;
    font-family: 'SF Mono', 'Fira Code', monospace;
    font-size: .85rem;
    line-height: 1.7;
    color: var(--int-text);
}
.int-api-code .code-comment { color: #636e72; }
.int-api-code .code-keyword { color: var(--int-accent-light); }
.int-api-code .code-string { color: var(--int-green); }
.int-api-code .code-func { color: var(--int-blue); }
.int-api-btns { display: flex; gap: 12px; flex-wrap: wrap; }

/* ---- Request Section ---- */
.int-request-section { padding: 80px 0; }
.int-request-card {
    background: var(--int-surface);
    border: 1px solid var(--int-border);
    border-radius: var(--int-radius);
    padding: 48px;
    max-width: 700px;
    margin: 0 auto;
    text-align: center;
}
.int-request-card h2 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.8rem; font-weight: 800;
    margin: 0 0 12px;
    background: linear-gradient(135deg, #6c5ce7, #0984e3);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text;
}
.int-request-card > p {
    color: var(--int-text-muted);
    margin: 0 0 30px;
    font-size: 1.05rem;
}
.int-request-form {
    display: flex; flex-direction: column; gap: 16px;
    text-align: left;
}
.int-form-group { display: flex; flex-direction: column; gap: 6px; }
.int-form-group label {
    font-size: .85rem; font-weight: 600; color: var(--int-text);
}
.int-form-group input,
.int-form-group textarea {
    padding: 12px 16px;
    background: var(--int-bg);
    border: 1px solid var(--int-border);
    border-radius: var(--int-radius-sm);
    color: var(--int-text);
    font-size: .95rem;
    font-family: inherit;
    outline: none;
    transition: border-color var(--int-transition);
}
.int-form-group input:focus,
.int-form-group textarea:focus {
    border-color: var(--int-accent);
    box-shadow: 0 0 0 3px rgba(108,92,231,0.12);
}
.int-form-group textarea { resize: vertical; min-height: 100px; }
.int-request-submit {
    display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    padding: 14px 32px; border: none; border-radius: 50px;
    background: linear-gradient(135deg, #6c5ce7, #0984e3);
    color: #fff; font-size: 1rem; font-weight: 700;
    cursor: pointer; font-family: inherit;
    box-shadow: 0 4px 20px rgba(108,92,231,0.35);
    transition: all var(--int-transition);
    margin-top: 8px;
}
.int-request-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 32px rgba(108,92,231,0.5);
}
.int-request-msg {
    padding: 12px 16px; border-radius: var(--int-radius-sm);
    font-size: .9rem; font-weight: 600; display: none; margin-top: 12px;
}
.int-request-msg.success { display: block; background: rgba(0,184,148,0.12); color: var(--int-green); }
.int-request-msg.error { display: block; background: rgba(214,48,49,0.12); color: var(--int-red); }

/* ---- CTA ---- */
.int-cta {
    padding: 100px 0;
    text-align: center;
    background: linear-gradient(135deg, #0a0a14 0%, #1a1033 50%, #0a0a14 100%);
    position: relative;
}
.int-cta::before {
    content: '';
    position: absolute; inset: 0; pointer-events: none;
    background: radial-gradient(ellipse at 50% 50%, rgba(108,92,231,0.1) 0%, transparent 60%);
}
.int-cta h2 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: clamp(1.8rem, 4vw, 2.8rem); font-weight: 800;
    margin: 0 0 16px;
    background: linear-gradient(135deg, #6c5ce7, #a29bfe);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text;
    position: relative; z-index: 1;
}
.int-cta p {
    color: var(--int-text-muted);
    font-size: 1.1rem;
    margin: 0 0 32px;
    position: relative; z-index: 1;
}
.int-cta-btns {
    display: flex; justify-content: center; gap: 16px; flex-wrap: wrap;
    position: relative; z-index: 1;
}

/* ---- Responsive ---- */
@media (max-width: 768px) {
    .int-api-grid { grid-template-columns: 1fr; }
    .int-hero-stats { gap: 24px; }
    .int-request-card { padding: 32px 24px; }
    .int-grid { grid-template-columns: 1fr; }
    .int-filter-bar { position: static; }
}
@media (max-width: 480px) {
    .int-tabs { gap: 6px; }
    .int-tab { padding: 6px 14px; font-size: .8rem; }
}
</style>

<div class="int-page">

    <!-- ===== HERO ===== -->
    <section class="int-hero">
        <div class="int-container">
            <span class="int-badge"><i class="fas fa-road"></i> Roadmap</span>
            <h1>Integration Roadmap</h1>
            <p>We're building integrations to connect Alfred AI with your favorite platforms and tools. Here's what's planned — vote for the ones you need most.</p>
            <div class="int-hero-stats">
                <div class="int-hero-stat">
                    <div class="num">45</div>
                    <span class="label">Planned</span>
                </div>
                <div class="int-hero-stat">
                    <div class="num">10</div>
                    <span class="label">Categories</span>
                </div>
                <div class="int-hero-stat">
                    <div class="num">REST</div>
                    <span class="label">API Standard</span>
                </div>
                <div class="int-hero-stat">
                    <div class="num">2026</div>
                    <span class="label">Target Year</span>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== SEARCH + FILTER ===== -->
    <div class="int-filter-bar">
        <div class="int-container">
            <div class="int-search-wrap">
                <i class="fas fa-search"></i>
                <input type="text" class="int-search" id="intSearch" placeholder="Search integrations..." autocomplete="off">
            </div>
            <div class="int-tabs" id="intTabs">
                <button class="int-tab active" data-cat="all">All</button>
                <button class="int-tab" data-cat="crm">CRM</button>
                <button class="int-tab" data-cat="ecommerce">E-Commerce</button>
                <button class="int-tab" data-cat="helpdesk">Helpdesk</button>
                <button class="int-tab" data-cat="communication">Communication</button>
                <button class="int-tab" data-cat="payment">Payment</button>
                <button class="int-tab" data-cat="analytics">Analytics</button>
                <button class="int-tab" data-cat="cloud">Cloud</button>
                <button class="int-tab" data-cat="voice">Voice</button>
                <button class="int-tab" data-cat="productivity">Productivity</button>
                <button class="int-tab" data-cat="devtools">Dev Tools</button>
            </div>
        </div>
    </div>

    <!-- ===== INTEGRATION GRID ===== -->
    <section class="int-grid-section">
        <div class="int-container">
            <div class="int-grid" id="intGrid">

                <?php
                $integrations = [
                    // CRM
                    ['name' => 'Salesforce', 'cat' => 'crm', 'letter' => 'S', 'desc' => 'Sync contacts, leads, and opportunities. Auto-log calls and update deal stages with AI insights.'],
                    ['name' => 'HubSpot', 'cat' => 'crm', 'letter' => 'H', 'desc' => 'Connect marketing, sales, and service hubs. Enrich contacts and automate follow-ups with Alfred.'],
                    ['name' => 'Zoho CRM', 'cat' => 'crm', 'letter' => 'Z', 'desc' => 'Two-way sync with Zoho CRM. AI-powered lead scoring and automated task creation.'],
                    ['name' => 'Pipedrive', 'cat' => 'crm', 'letter' => 'P', 'desc' => 'Manage your sales pipeline with AI. Auto-create deals from conversations and track activities.'],
                    ['name' => 'Freshsales', 'cat' => 'crm', 'letter' => 'F', 'desc' => 'Integrate with Freshsales for AI-driven lead management and conversation intelligence.'],

                    // E-Commerce
                    ['name' => 'Shopify', 'cat' => 'ecommerce', 'letter' => 'S', 'desc' => 'AI customer support for your Shopify store. Order tracking, product recommendations, and returns.'],
                    ['name' => 'WooCommerce', 'cat' => 'ecommerce', 'letter' => 'W', 'desc' => 'WordPress e-commerce integration. Manage orders, answer product questions, and process refunds.'],
                    ['name' => 'BigCommerce', 'cat' => 'ecommerce', 'letter' => 'B', 'desc' => 'Enterprise e-commerce integration with AI-powered product search and order management.'],
                    ['name' => 'Magento', 'cat' => 'ecommerce', 'letter' => 'M', 'desc' => 'Adobe Commerce integration for large catalogs. AI product discovery and customer support.'],
                    ['name' => 'Square', 'cat' => 'ecommerce', 'letter' => 'Sq', 'desc' => 'Connect Square POS for in-store and online AI assistance. Inventory and order sync.'],

                    // Helpdesk
                    ['name' => 'Zendesk', 'cat' => 'helpdesk', 'letter' => 'Z', 'desc' => 'AI-powered ticket routing, auto-responses, and knowledge base integration with Zendesk.'],
                    ['name' => 'Freshdesk', 'cat' => 'helpdesk', 'letter' => 'F', 'desc' => 'Automate Level-1 support with AI. Ticket creation, prioritization, and smart escalation.'],
                    ['name' => 'Intercom', 'cat' => 'helpdesk', 'letter' => 'I', 'desc' => 'Enhance Intercom conversations with AI. Smart handoff from bot to human agents.'],
                    ['name' => 'Help Scout', 'cat' => 'helpdesk', 'letter' => 'HS', 'desc' => 'AI-assisted email support. Draft responses, categorize tickets, and surface knowledge articles.'],
                    ['name' => 'Front', 'cat' => 'helpdesk', 'letter' => 'F', 'desc' => 'Collaborative inbox meets AI. Automate tagging, routing, and response drafts in Front.'],

                    // Communication
                    ['name' => 'Slack', 'cat' => 'communication', 'letter' => 'S', 'desc' => 'Alfred in your Slack workspace. Ask questions, run tools, and get notifications in any channel.'],
                    ['name' => 'Microsoft Teams', 'cat' => 'communication', 'letter' => 'T', 'desc' => 'Deploy Alfred as a Teams bot. Meeting summaries, task creation, and enterprise search.'],
                    ['name' => 'Discord', 'cat' => 'communication', 'letter' => 'D', 'desc' => 'Add Alfred to your Discord server. Community support, moderation assist, and AI commands.'],
                    ['name' => 'WhatsApp', 'cat' => 'communication', 'letter' => 'W', 'desc' => 'WhatsApp Business API integration. AI conversations with customers via their favorite app.'],
                    ['name' => 'Telegram', 'cat' => 'communication', 'letter' => 'T', 'desc' => 'Telegram bot integration. Serve customers and automate workflows via Telegram messages.'],

                    // Payment
                    ['name' => 'Stripe', 'cat' => 'payment', 'letter' => 'S', 'desc' => 'Payment processing, subscription management, and invoice automation powered by AI.'],
                    ['name' => 'PayPal', 'cat' => 'payment', 'letter' => 'PP', 'desc' => 'Accept PayPal payments and automate refund processing with AI-assisted dispute management.'],
                    ['name' => 'Square Payments', 'cat' => 'payment', 'letter' => 'Sq', 'desc' => 'In-person and online payment processing with Square. AI-powered transaction insights.'],
                    ['name' => 'Braintree', 'cat' => 'payment', 'letter' => 'B', 'desc' => 'PayPal\'s Braintree for advanced payment processing. Fraud detection and subscription billing.'],

                    // Analytics
                    ['name' => 'Google Analytics', 'cat' => 'analytics', 'letter' => 'GA', 'desc' => 'AI-powered insights from your GA4 data. Natural language queries for traffic and conversion analysis.'],
                    ['name' => 'Mixpanel', 'cat' => 'analytics', 'letter' => 'M', 'desc' => 'Product analytics integration. Track user behavior and funnel performance with AI insights.'],
                    ['name' => 'Segment', 'cat' => 'analytics', 'letter' => 'S', 'desc' => 'Customer data platform integration. Route events and build unified customer profiles.'],
                    ['name' => 'Amplitude', 'cat' => 'analytics', 'letter' => 'A', 'desc' => 'Behavioral analytics with AI. Understand user journeys and predict churn with Alfred.'],

                    // Cloud
                    ['name' => 'AWS', 'cat' => 'cloud', 'letter' => 'A', 'desc' => 'Deploy Alfred on AWS infrastructure. Lambda, S3, and SageMaker integrations for scalable AI.'],
                    ['name' => 'Google Cloud', 'cat' => 'cloud', 'letter' => 'G', 'desc' => 'GCP integration with Vertex AI, Cloud Functions, and BigQuery for enterprise deployments.'],
                    ['name' => 'Azure', 'cat' => 'cloud', 'letter' => 'Az', 'desc' => 'Microsoft Azure deployment. Cognitive Services, Functions, and Active Directory integration.'],
                    ['name' => 'DigitalOcean', 'cat' => 'cloud', 'letter' => 'DO', 'desc' => 'Simple cloud deployment on DigitalOcean. Droplets, Spaces, and managed databases.'],

                    // Voice
                    ['name' => 'Twilio', 'cat' => 'voice', 'letter' => 'T', 'desc' => 'Programmable voice and SMS. Build AI phone agents, IVR systems, and outbound campaigns.'],
                    ['name' => 'Vonage', 'cat' => 'voice', 'letter' => 'V', 'desc' => 'Communications API for voice, video, and messaging. AI-powered call handling and routing.'],
                    ['name' => 'Plivo', 'cat' => 'voice', 'letter' => 'P', 'desc' => 'Cloud communication platform for voice and SMS with AI-enhanced call flows.'],
                    ['name' => 'Bandwidth', 'cat' => 'voice', 'letter' => 'B', 'desc' => 'Enterprise-grade voice and messaging APIs. PSTN access with AI voice agent capabilities.'],

                    // Productivity
                    ['name' => 'Zapier', 'cat' => 'productivity', 'letter' => 'Z', 'desc' => 'Connect Alfred to 5,000+ apps via Zapier. Automate any workflow without code.'],
                    ['name' => 'Make', 'cat' => 'productivity', 'letter' => 'M', 'desc' => 'Visual automation with Make (Integromat). Complex workflows with AI-powered decision nodes.'],
                    ['name' => 'IFTTT', 'cat' => 'productivity', 'letter' => 'IF', 'desc' => 'Simple automation triggers. If this happens in Alfred, then do that in any connected app.'],
                    ['name' => 'n8n', 'cat' => 'productivity', 'letter' => 'n8', 'desc' => 'Self-hosted workflow automation. Build complex AI pipelines with the n8n visual editor.'],

                    // Dev Tools
                    ['name' => 'GitHub', 'cat' => 'devtools', 'letter' => 'GH', 'desc' => 'AI code reviews, issue management, and PR automation. Alfred as your AI dev teammate.'],
                    ['name' => 'GitLab', 'cat' => 'devtools', 'letter' => 'GL', 'desc' => 'CI/CD pipeline integration. AI-assisted merge requests and issue triage.'],
                    ['name' => 'Jira', 'cat' => 'devtools', 'letter' => 'J', 'desc' => 'Project management with AI. Auto-create stories, estimate points, and track sprint progress.'],
                    ['name' => 'Linear', 'cat' => 'devtools', 'letter' => 'L', 'desc' => 'Modern issue tracking integration. AI-powered task prioritization and cycle planning.'],
                    ['name' => 'Notion', 'cat' => 'devtools', 'letter' => 'N', 'desc' => 'Knowledge base sync with Notion. AI search across docs, wikis, and project boards.'],
                ];

                $cat_labels = [
                    'crm' => 'CRM',
                    'ecommerce' => 'E-Commerce',
                    'helpdesk' => 'Helpdesk',
                    'communication' => 'Communication',
                    'payment' => 'Payment',
                    'analytics' => 'Analytics',
                    'cloud' => 'Cloud',
                    'voice' => 'Voice',
                    'productivity' => 'Productivity',
                    'devtools' => 'Dev Tools',
                ];

                foreach ($integrations as $int):
                    $cat = $int['cat'];
                    $label = $cat_labels[$cat] ?? ucfirst($cat);
                ?>
                <div class="int-card" data-cat="<?php echo $cat; ?>" data-name="<?php echo htmlspecialchars(strtolower($int['name'])); ?>">
                    <div class="int-card-top">
                        <div class="int-card-icon int-cat-<?php echo $cat; ?>"><?php echo htmlspecialchars($int['letter']); ?></div>
                        <div class="int-card-info">
                            <h3><?php echo htmlspecialchars($int['name']); ?></h3>
                            <span class="int-card-cat"><?php echo htmlspecialchars($label); ?></span>
                        </div>
                    </div>
                    <p><?php echo htmlspecialchars($int['desc']); ?></p>
                    <div class="int-card-actions">
                        <a href="/developer-portal.php" class="int-btn int-btn-ghost"><i class="fas fa-book"></i> Developer Portal</a>
                        <span class="int-btn int-btn-primary" style="opacity:.7;cursor:default;"><i class="fas fa-clock"></i> Coming Soon</span>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- No results -->
                <div class="int-no-results" id="intNoResults">
                    <i class="fas fa-search"></i>
                    <h3>No integrations found</h3>
                    <p>Try a different search term or category, or request a new integration below.</p>
                </div>

            </div>
        </div>
    </section>

    <!-- ===== BUILD YOUR OWN ===== -->
    <section class="int-api-section">
        <div class="int-container">
            <div class="int-api-grid">
                <div class="int-api-content">
                    <span class="int-badge" style="margin-bottom:16px"><i class="fas fa-code"></i> Developer API</span>
                    <h2>Build Your Own Integration</h2>
                    <p>Our developer API is available now. Use it to build custom integrations while we work on native connectors for the platforms listed above.</p>
                    <ul class="int-api-features">
                        <li><i class="fas fa-check-circle"></i> RESTful API available now</li>
                        <li><i class="fas fa-wrench" style="color:var(--int-orange)"></i> SDKs in development</li>
                        <li><i class="fas fa-check-circle"></i> Webhook events for real-time sync</li>
                        <li><i class="fas fa-check-circle"></i> API key authentication</li>
                        <li><i class="fas fa-wrench" style="color:var(--int-orange)"></i> OAuth2 coming soon</li>
                        <li><i class="fas fa-check-circle"></i> Developer portal &amp; docs</li>
                    </ul>
                    <div class="int-api-btns">
                        <a href="/developer-portal.php" class="int-btn int-btn-primary"><i class="fas fa-terminal"></i> Developer Portal</a>
                        <a href="/sdks.php" class="int-btn int-btn-ghost"><i class="fas fa-cube"></i> View SDKs</a>
                        <a href="/docs/api-reference" class="int-btn int-btn-ghost"><i class="fas fa-book-open"></i> API Docs</a>
                    </div>
                </div>
                <div class="int-api-code">
<pre><span class="code-comment">// Use the Alfred API (available now)</span>
<span class="code-keyword">const</span> response = <span class="code-keyword">await</span> <span class="code-func">fetch</span>(<span class="code-string">'https://root.com/api/alfred-command.php'</span>, {
  method: <span class="code-string">'POST'</span>,
  headers: {
    <span class="code-string">'Content-Type'</span>: <span class="code-string">'application/json'</span>,
    <span class="code-string">'Authorization'</span>: <span class="code-string">'Bearer YOUR_API_KEY'</span>
  },
  body: JSON.<span class="code-func">stringify</span>({
    command: <span class="code-string">'summarize'</span>,
    text: documentContent
  })
});

<span class="code-keyword">const</span> result = <span class="code-keyword">await</span> response.<span class="code-func">json</span>();
console.<span class="code-func">log</span>(result.data);</pre>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== REQUEST INTEGRATION ===== -->
    <section class="int-request-section">
        <div class="int-container">
            <div class="int-request-card">
                <i class="fas fa-lightbulb" style="font-size:2.5rem; color:var(--int-accent-light); margin-bottom:16px;"></i>
                <h2>Request an Integration</h2>
                <p>Don't see the integration you need? Let us know and we'll prioritize it on our roadmap.</p>
                <form class="int-request-form" id="intRequestForm">
                    <div class="int-form-group">
                        <label for="intReqPlatform">Platform / Service Name *</label>
                        <input type="text" id="intReqPlatform" name="platform" required placeholder="e.g. Monday.com">
                    </div>
                    <div class="int-form-group">
                        <label for="intReqEmail">Your Email *</label>
                        <input type="email" id="intReqEmail" name="email" required placeholder="you@company.com">
                    </div>
                    <div class="int-form-group">
                        <label for="intReqUseCase">Use Case</label>
                        <textarea id="intReqUseCase" name="use_case" placeholder="How would you use this integration with Alfred?"></textarea>
                    </div>
                    <button type="submit" class="int-request-submit"><i class="fas fa-paper-plane"></i> Submit Request</button>
                    <div class="int-request-msg" id="intReqMsg"></div>
                </form>
            </div>
        </div>
    </section>

    <!-- ===== CTA ===== -->
    <section class="int-cta">
        <div class="int-container">
            <h2>Stay Updated</h2>
            <p>We're actively building these integrations. Request ones you need above, or start building with our API today.</p>
            <div class="int-cta-btns">
                <a href="/pricing.php" class="int-btn int-btn-primary" style="padding:16px 36px; font-size:1.05rem;"><i class="fas fa-rocket"></i> Get Started Free</a>
                <a href="/developer-portal.php" class="int-btn int-btn-ghost" style="padding:16px 36px; font-size:1.05rem;"><i class="fas fa-code"></i> Developer Portal</a>
            </div>
        </div>
    </section>

</div>

<script src="/assets/js/integrations-engine.js"></script>

<?php require_once __DIR__ . '/includes/site-footer.inc.php'; ?>
