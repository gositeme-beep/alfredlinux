<?php
$page_title = 'AI Employee Marketplace — Hire AI Agents for Your Team | GoSiteMe';
$page_description = 'Browse 158 AI employees across 22 specialties. Hire real AI agents that deploy to your fleet — customer support, sales, dev, legal, finance, and more.';
$page_canonical = 'https://root.com/marketplace.php';
$page_og_title = 'AI Employee Marketplace — Hire AI Agents Instantly';
$page_og_description = 'Browse 158 AI employees across 22 specialties. Hire real AI agents that deploy to your fleet instantly.';
$page_twitter_description = 'AI Employee Marketplace: Hire real AI agents — customer support, sales, dev, legal, finance & more.';
$page_og_image = 'https://root.com/assets/img/alfred-marketplace-og.png';
$page_og_image_alt = 'AI Employee Marketplace — Hire AI agents for your team';
$noGlobalMain = true;
include __DIR__ . '/includes/site-header.inc.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$is_logged_in = !empty($_SESSION['logged_in']) && !empty($_SESSION['client_id']);
$client_id    = $is_logged_in ? (int)$_SESSION['client_id'] : 0;
$client_name  = $_SESSION['client_name'] ?? '';
$client_email = $_SESSION['client_email'] ?? '';
?>

<style>
/* ===== Marketplace Page Styles ===== */
:root {
    --mp-bg: #0a0a14;
    --mp-surface: #12121e;
    --mp-surface-2: #1a1a2e;
    --mp-border: rgba(255,255,255,0.08);
    --mp-accent: #6c5ce7;
    --mp-accent-light: #a29bfe;
    --mp-green: #00b894;
    --mp-orange: #fdcb6e;
    --mp-fire: #e17055;
    --mp-blue: #0984e3;
    --mp-text: #e8e8f0;
    --mp-text-muted: #8a8a9a;
    --mp-radius: 14px;
    --mp-shadow: 0 4px 24px rgba(0,0,0,0.3);
}
.mp-hero {
    padding: 140px 0 50px;
    text-align: center;
    background: linear-gradient(135deg, #0a0a14 0%, #1a1033 50%, #0a0a14 100%);
    position: relative;
    overflow: hidden;
}
.mp-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle at 30% 50%, rgba(108,92,231,0.08) 0%, transparent 50%),
                radial-gradient(circle at 70% 50%, rgba(0,184,148,0.06) 0%, transparent 50%);
    animation: mpHeroPulse 8s ease-in-out infinite alternate;
}
@keyframes mpHeroPulse {
    0% { transform: scale(1) rotate(0deg); }
    100% { transform: scale(1.05) rotate(2deg); }
}
.mp-hero h1 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: clamp(2.2rem, 5vw, 3.8rem);
    font-weight: 800;
    color: #fff;
    margin: 0 0 12px;
    position: relative;
    z-index: 1;
}
.mp-hero h1 span {
    background: linear-gradient(135deg, var(--mp-accent-light), var(--mp-green));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.mp-hero .tagline {
    font-size: 1.2rem;
    color: var(--mp-text-muted);
    margin: 0 0 30px;
    position: relative;
    z-index: 1;
}
.mp-hero .cart-icon {
    position: fixed;
    top: 80px;
    right: 30px;
    z-index: 1000;
    background: var(--mp-accent);
    color: #fff;
    width: 52px;
    height: 52px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    cursor: pointer;
    box-shadow: 0 4px 20px rgba(108,92,231,0.4);
    transition: transform 0.3s, box-shadow 0.3s;
}
.mp-hero .cart-icon:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 28px rgba(108,92,231,0.6);
}
.mp-hero .cart-icon .cart-count {
    position: absolute;
    top: -4px;
    right: -4px;
    background: var(--mp-fire);
    color: #fff;
    font-size: 0.7rem;
    font-weight: 700;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Search & Filters */
.mp-controls {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px 30px;
}
.mp-search-bar {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}
.mp-search-bar input[type="text"] {
    flex: 1;
    min-width: 250px;
    padding: 14px 20px 14px 48px;
    background: var(--mp-surface);
    border: 1px solid var(--mp-border);
    border-radius: var(--mp-radius);
    color: var(--mp-text);
    font-size: 1rem;
    outline: none;
    transition: border-color 0.3s;
}
.mp-search-bar input[type="text"]:focus {
    border-color: var(--mp-accent);
}
.mp-search-wrap {
    position: relative;
    flex: 1;
    min-width: 250px;
}
.mp-search-wrap i {
    position: absolute;
    left: 18px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--mp-text-muted);
}
.mp-search-bar select {
    padding: 14px 18px;
    background: var(--mp-surface);
    border: 1px solid var(--mp-border);
    border-radius: var(--mp-radius);
    color: var(--mp-text);
    font-size: 0.95rem;
    cursor: pointer;
    outline: none;
}
.mp-search-bar select:focus {
    border-color: var(--mp-accent);
}

/* Category Tabs */
.mp-tabs {
    display: flex;
    gap: 8px;
    justify-content: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
}
.mp-tab {
    padding: 10px 24px;
    background: var(--mp-surface);
    border: 1px solid var(--mp-border);
    border-radius: 50px;
    color: var(--mp-text-muted);
    font-size: 0.95rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s;
}
.mp-tab:hover, .mp-tab.active {
    background: var(--mp-accent);
    color: #fff;
    border-color: var(--mp-accent);
    box-shadow: 0 2px 12px rgba(108,92,231,0.3);
}
.mp-tab i {
    margin-right: 6px;
}

/* Product Grid */
.mp-grid {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px 60px;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 24px;
}
.mp-card {
    background: var(--mp-surface);
    border: 1px solid var(--mp-border);
    border-radius: var(--mp-radius);
    overflow: hidden;
    transition: transform 0.3s, box-shadow 0.3s, border-color 0.3s;
    animation: mpFadeIn 0.5s ease forwards;
    opacity: 0;
}
.mp-card:hover {
    transform: translateY(-6px);
    box-shadow: var(--mp-shadow);
    border-color: var(--mp-accent);
}
@keyframes mpFadeIn {
    to { opacity: 1; }
}
.mp-card:nth-child(1) { animation-delay: 0.05s; }
.mp-card:nth-child(2) { animation-delay: 0.1s; }
.mp-card:nth-child(3) { animation-delay: 0.15s; }
.mp-card:nth-child(4) { animation-delay: 0.2s; }
.mp-card:nth-child(5) { animation-delay: 0.25s; }
.mp-card:nth-child(6) { animation-delay: 0.3s; }
.mp-card:nth-child(7) { animation-delay: 0.35s; }
.mp-card:nth-child(8) { animation-delay: 0.4s; }
.mp-card:nth-child(9) { animation-delay: 0.45s; }
.mp-card:nth-child(10) { animation-delay: 0.5s; }
.mp-card:nth-child(11) { animation-delay: 0.55s; }
.mp-card:nth-child(12) { animation-delay: 0.6s; }
.mp-card-thumb {
    height: 160px;
    background: var(--mp-surface-2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    position: relative;
}
.mp-card-thumb .badge-popular {
    position: absolute;
    top: 12px;
    left: 12px;
    background: linear-gradient(135deg, var(--mp-fire), #e74c3c);
    color: #fff;
    padding: 4px 12px;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 4px;
}
.mp-card-thumb .badge-free {
    position: absolute;
    top: 12px;
    right: 12px;
    background: var(--mp-green);
    color: #fff;
    padding: 4px 12px;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 600;
}
.mp-card-body {
    padding: 18px;
}
.mp-card-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.05rem;
    font-weight: 600;
    color: #fff;
    margin: 0 0 6px;
}
.mp-card-desc {
    font-size: 0.88rem;
    color: var(--mp-text-muted);
    margin: 0 0 12px;
    line-height: 1.45;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.mp-card-seller {
    font-size: 0.82rem;
    color: var(--mp-accent-light);
    margin-bottom: 10px;
}
.mp-card-seller i {
    margin-right: 4px;
}
.mp-card-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 14px;
}
.mp-card-rating {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.85rem;
}
.mp-card-rating .stars {
    color: var(--mp-orange);
}
.mp-card-rating .count {
    color: var(--mp-text-muted);
}
.mp-card-downloads {
    font-size: 0.82rem;
    color: var(--mp-text-muted);
}
.mp-card-downloads i {
    margin-right: 3px;
}
.mp-card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.mp-price {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.2rem;
    font-weight: 700;
    color: #fff;
}
.mp-price.free {
    color: var(--mp-green);
}
.mp-btn-cart {
    padding: 8px 18px;
    background: var(--mp-accent);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 0.88rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.3s, transform 0.2s;
    display: flex;
    align-items: center;
    gap: 6px;
}
.mp-btn-cart:hover {
    background: #5a4bd1;
    transform: scale(1.05);
}

/* Seller Dashboard */
.mp-seller-section {
    max-width: 1200px;
    margin: 0 auto 60px;
    padding: 0 20px;
}
.mp-seller-toggle {
    width: 100%;
    padding: 18px 24px;
    background: var(--mp-surface);
    border: 1px solid var(--mp-border);
    border-radius: var(--mp-radius);
    color: #fff;
    font-size: 1.05rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: border-color 0.3s;
}
.mp-seller-toggle:hover {
    border-color: var(--mp-accent);
}
.mp-seller-toggle i.chevron {
    transition: transform 0.3s;
}
.mp-seller-toggle.open i.chevron {
    transform: rotate(180deg);
}
.mp-seller-panel {
    display: none;
    background: var(--mp-surface);
    border: 1px solid var(--mp-border);
    border-top: none;
    border-radius: 0 0 var(--mp-radius) var(--mp-radius);
    padding: 30px;
}
.mp-seller-panel.show {
    display: block;
    animation: mpSlideDown 0.3s ease;
}
@keyframes mpSlideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
.mp-seller-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}
.mp-stat-card {
    background: var(--mp-surface-2);
    border-radius: 12px;
    padding: 20px;
    text-align: center;
}
.mp-stat-card .stat-value {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--mp-accent-light);
    display: block;
    margin-bottom: 4px;
}
.mp-stat-card .stat-label {
    font-size: 0.85rem;
    color: var(--mp-text-muted);
}
.mp-btn-list {
    padding: 14px 32px;
    background: linear-gradient(135deg, var(--mp-accent), #5a4bd1);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.3s, box-shadow 0.3s;
}
.mp-btn-list:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 24px rgba(108,92,231,0.4);
}

/* Reviews Section */
.mp-reviews {
    max-width: 1200px;
    margin: 0 auto 60px;
    padding: 0 20px;
}
.mp-reviews h2 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.8rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 30px;
    text-align: center;
}
.mp-reviews-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
}
.mp-review-card {
    background: var(--mp-surface);
    border: 1px solid var(--mp-border);
    border-radius: var(--mp-radius);
    padding: 24px;
    transition: border-color 0.3s;
}
.mp-review-card:hover {
    border-color: var(--mp-accent);
}
.mp-review-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}
.mp-review-avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: var(--mp-surface-2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    color: var(--mp-accent-light);
}
.mp-review-user {
    font-weight: 600;
    color: #fff;
    font-size: 0.95rem;
}
.mp-review-date {
    font-size: 0.8rem;
    color: var(--mp-text-muted);
}
.mp-review-stars {
    color: var(--mp-orange);
    margin-bottom: 8px;
}
.mp-review-text {
    font-size: 0.9rem;
    color: var(--mp-text-muted);
    line-height: 1.55;
}
.mp-review-product {
    font-size: 0.82rem;
    color: var(--mp-accent-light);
    margin-top: 10px;
    display: flex;
    align-items: center;
    gap: 4px;
}

/* Become a Seller CTA */
.mp-cta {
    text-align: center;
    padding: 80px 20px;
    background: linear-gradient(135deg, var(--mp-surface) 0%, #1a1033 100%);
}
.mp-cta h2 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: clamp(1.8rem, 4vw, 2.6rem);
    font-weight: 800;
    color: #fff;
    margin: 0 0 16px;
}
.mp-cta p {
    font-size: 1.1rem;
    color: var(--mp-text-muted);
    margin: 0 0 32px;
    max-width: 580px;
    margin-left: auto;
    margin-right: auto;
}
.mp-cta-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 16px 40px;
    background: linear-gradient(135deg, var(--mp-green), #00a381);
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 1.1rem;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    transition: transform 0.3s, box-shadow 0.3s;
}
.mp-cta-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 32px rgba(0,184,148,0.4);
}

/* Responsive */
@media (max-width: 768px) {
    .mp-hero { padding: 60px 0 30px; }
    .mp-grid { grid-template-columns: 1fr; padding: 0 16px 40px; }
    .mp-reviews-grid { grid-template-columns: 1fr; }
    .mp-seller-stats { grid-template-columns: repeat(2, 1fr); }
    .mp-hero .cart-icon { top: 70px; right: 16px; width: 46px; height: 46px; font-size: 1.1rem; }
}
@media (max-width: 480px) {
    .mp-tabs { gap: 6px; }
    .mp-tab { padding: 8px 16px; font-size: 0.85rem; }
    .mp-seller-stats { grid-template-columns: 1fr 1fr; }
}
</style>

<main id="main">

<!-- Shopping Cart Icon -->
<div class="mp-hero">
    <div class="cart-icon" id="cartIcon" title="My Team">
        <i class="fas fa-users"></i>
        <span class="cart-count" id="cartCount">0</span>
    </div>

    <h1>Hire AI <span>Employees</span></h1>
    <p class="tagline">158 real AI agents across 22 specialties — hire them and they deploy to your fleet instantly</p>
    <div style="display:flex;gap:12px;justify-content:center;position:relative;z-index:1;margin-top:12px;flex-wrap:wrap;">
        <a href="/fleet-dashboard.php" style="display:inline-flex;align-items:center;gap:8px;padding:10px 24px;background:linear-gradient(135deg,var(--mp-green),#00a381);color:#fff;border-radius:50px;font-size:0.9rem;font-weight:600;text-decoration:none;transition:transform 0.3s,box-shadow 0.3s;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 20px rgba(0,184,148,0.4)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
            <i class="fas fa-users"></i> View My Team
        </a>
        <a href="/agent-templates.php" style="display:inline-flex;align-items:center;gap:8px;padding:10px 24px;background:linear-gradient(135deg,var(--mp-accent),#5a4bd1);color:#fff;border-radius:50px;font-size:0.9rem;font-weight:600;text-decoration:none;transition:transform 0.3s,box-shadow 0.3s;" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 4px 20px rgba(108,92,231,0.4)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
            <i class="fas fa-th-large"></i> Template Gallery
        </a>
    </div>
</div>

<!-- Category Tabs -->
<div class="mp-controls">
    <div class="mp-tabs" id="mpTabs">
        <button class="mp-tab active" data-cat="all"><i class="fas fa-globe"></i> All (158)</button>
        <button class="mp-tab" data-cat="customer-support"><i class="fas fa-headset"></i> Support</button>
        <button class="mp-tab" data-cat="sales-marketing"><i class="fas fa-chart-line"></i> Sales</button>
        <button class="mp-tab" data-cat="voice-phone"><i class="fas fa-phone-volume"></i> Voice</button>
        <button class="mp-tab" data-cat="developer"><i class="fas fa-terminal"></i> Dev</button>
        <button class="mp-tab" data-cat="finance"><i class="fas fa-coins"></i> Finance</button>
        <button class="mp-tab" data-cat="education"><i class="fas fa-book-open"></i> Education</button>
        <button class="mp-tab" data-cat="health-wellness"><i class="fas fa-heart-pulse"></i> Health</button>
        <button class="mp-tab" data-cat="legal"><i class="fas fa-scale-balanced"></i> Legal</button>
        <button class="mp-tab" data-cat="industry"><i class="fas fa-industry"></i> Industry</button>
    </div>

    <!-- Search & Filters -->
    <div class="mp-search-bar">
        <div class="mp-search-wrap">
            <i class="fas fa-search"></i>
            <input type="text" id="mpSearch" placeholder="Search AI employees... (e.g. receptionist, code reviewer, tutor)">
        </div>
        <select id="mpRole">
            <option value="all">All Roles</option>
            <option value="specialist">Specialist</option>
            <option value="coordinator">Coordinator</option>
            <option value="analyst">Analyst</option>
            <option value="reviewer">Reviewer</option>
            <option value="leader">Leader</option>
        </select>
        <select id="mpDifficulty">
            <option value="all">All Levels</option>
            <option value="beginner">Beginner</option>
            <option value="intermediate">Intermediate</option>
            <option value="advanced">Advanced</option>
        </select>
        <select id="mpSort">
            <option value="name">Name A→Z</option>
            <option value="category">By Category</option>
            <option value="role">By Role</option>
            <option value="difficulty">By Difficulty</option>
            <option value="tools">Most Tools</option>
        </select>
    </div>
</div>

<!-- Product Grid — loaded from API -->
<div class="mp-grid" id="mpGrid">
    <div style="grid-column:1/-1;text-align:center;padding:3rem;color:var(--mp-text-muted);">
        <i class="fas fa-spinner fa-spin" style="font-size:2rem;color:var(--mp-accent);"></i>
        <p style="margin-top:1rem;">Loading marketplace products...</p>
    </div>
</div>

<!-- My Hired Team -->
<section class="mp-seller-section">
    <button class="mp-seller-toggle" id="sellerToggle" onclick="toggleSeller()">
        <span><i class="fas fa-users-gear"></i> &nbsp;My Hired Team</span>
        <i class="fas fa-chevron-down chevron"></i>
    </button>
    <div class="mp-seller-panel" id="sellerPanel">
        <div id="myTeamList" style="padding:1rem;color:var(--mp-text-muted);text-align:center;">
            <i class="fas fa-spinner fa-spin"></i> Loading your team...
        </div>
        <div style="text-align:center;margin-top:1rem;">
            <a href="/fleet-dashboard.php" class="mp-btn-list" style="text-decoration:none;display:inline-flex;align-items:center;gap:8px;"><i class="fas fa-rocket"></i> Open Fleet Dashboard</a>
        </div>
    </div>
</section>

<!-- How It Works -->
<section class="mp-reviews">
    <h2><i class="fas fa-bolt"></i> How It Works</h2>
    <div class="mp-reviews-grid">
        <div class="mp-review-card">
            <div class="mp-review-header">
                <div class="mp-review-avatar" style="background:rgba(108,92,231,0.2);color:var(--mp-accent);"><i class="fas fa-search"></i></div>
                <div>
                    <div class="mp-review-user" style="font-size:1.1rem;">1. Browse</div>
                </div>
            </div>
            <p class="mp-review-text">Search 158 AI employees across 22 specialties — from customer support agents to code reviewers, legal intake to fitness coaches.</p>
        </div>
        <div class="mp-review-card">
            <div class="mp-review-header">
                <div class="mp-review-avatar" style="background:rgba(0,184,148,0.2);color:var(--mp-green);"><i class="fas fa-user-plus"></i></div>
                <div>
                    <div class="mp-review-user" style="font-size:1.1rem;">2. Hire</div>
                </div>
            </div>
            <p class="mp-review-text">Click "Hire" and the AI employee is instantly deployed to your fleet. Each agent comes pre-configured with specialized tools and prompts.</p>
        </div>
        <div class="mp-review-card">
            <div class="mp-review-header">
                <div class="mp-review-avatar" style="background:rgba(253,203,110,0.2);color:var(--mp-orange);"><i class="fas fa-rocket"></i></div>
                <div>
                    <div class="mp-review-user" style="font-size:1.1rem;">3. Deploy</div>
                </div>
            </div>
            <p class="mp-review-text">Your new AI employee joins your fleet ready to work. Manage them from the Fleet Dashboard — assign tasks, monitor output, and scale your team.</p>
        </div>
    </div>
</section>

<!-- Scale Your Team CTA -->
<section class="mp-cta">
    <div style="max-width:700px;margin:0 auto;">
        <div style="display:inline-flex;align-items:center;gap:8px;background:rgba(0,184,148,0.15);color:var(--mp-green);padding:6px 16px;border-radius:50px;font-size:0.85rem;font-weight:600;margin-bottom:20px;">
            <i class="fas fa-users"></i> Real AI Agents
        </div>
        <h2>Scale Your Team with AI Employees</h2>
        <p>Every employee listed here is a real AI agent backed by GPT-4, pre-configured with specialized tools and ready to work. Hire them and they deploy to your fleet in seconds.</p>
        <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap;">
            <a href="/fleet-dashboard.php" class="mp-cta-btn"><i class="fas fa-rocket"></i> Fleet Dashboard</a>
            <a href="/agent-templates.php" class="mp-cta-btn" style="background:linear-gradient(135deg,var(--mp-accent),#5a4bd1);"><i class="fas fa-th-large"></i> All 158 Templates</a>
        </div>
        <div style="display:flex;gap:32px;justify-content:center;margin-top:32px;flex-wrap:wrap;">
            <div style="text-align:center;"><span class="mp-cta-stat" style="font-family:'Space Grotesk',sans-serif;font-size:1.4rem;font-weight:700;color:#fff;display:block;">158</span><span style="font-size:0.85rem;color:var(--mp-text-muted);">AI Employees</span></div>
            <div style="text-align:center;"><span class="mp-cta-stat" style="font-family:'Space Grotesk',sans-serif;font-size:1.4rem;font-weight:700;color:#fff;display:block;">22</span><span style="font-size:0.85rem;color:var(--mp-text-muted);">Specialties</span></div>
            <div style="text-align:center;"><span class="mp-cta-stat" style="font-family:'Space Grotesk',sans-serif;font-size:1.4rem;font-weight:700;color:#fff;display:block;" id="statHired">0</span><span style="font-size:0.85rem;color:var(--mp-text-muted);">Hired So Far</span></div>
        </div>
    </div>
</section>

</main>

<script src="/assets/js/marketplace-engine.js"></script>

<!-- Schema.org: WebPage + ItemList -->
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"WebPage","name":"AI Employee Marketplace — Hire AI Agents for Your Team","description":"Browse 158 AI employees across 22 specialties. Hire real AI agents that deploy to your fleet instantly.","url":"https://root.com/marketplace.php","isPartOf":{"@type":"WebSite","name":"GoSiteMe","url":"https://root.com"},"primaryImageOfPage":{"@type":"ImageObject","url":"https://root.com/assets/img/alfred-marketplace-og.png"}}
</script>
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"ItemList","name":"AI Employee Categories","description":"158 AI agents across 22 specialties available for hiring.","url":"https://root.com/marketplace.php","numberOfItems":"158","itemListElement":[{"@type":"ListItem","position":1,"name":"Customer Support","url":"https://root.com/marketplace.php#customer-support"},{"@type":"ListItem","position":2,"name":"Sales & Marketing","url":"https://root.com/marketplace.php#sales-marketing"},{"@type":"ListItem","position":3,"name":"Voice & Phone","url":"https://root.com/marketplace.php#voice-phone"},{"@type":"ListItem","position":4,"name":"Developer & Tech","url":"https://root.com/marketplace.php#developer"},{"@type":"ListItem","position":5,"name":"Finance & Legal","url":"https://root.com/marketplace.php#finance"}]}
</script>
</script>

<!-- Explore More Interlinks -->
<section style="padding:3rem 1.5rem;text-align:center;">
    <div style="max-width:900px;margin:0 auto;">
        <h3 style="color:#a29bfe;margin-bottom:1.5rem;">Explore More</h3>
        <div style="display:flex;flex-wrap:wrap;gap:1rem;justify-content:center;">
            <a href="/fleet-dashboard.php" class="btn btn-outline">Fleet Dashboard</a>
            <a href="/agent-templates.php" class="btn btn-outline">All 158 Templates</a>
            <a href="/enterprise.php" class="btn btn-outline">Enterprise</a>
            <a href="/pricing.php" class="btn btn-outline">View Pricing</a>
            <a href="/tools/" class="btn btn-outline">Tool Directory</a>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
