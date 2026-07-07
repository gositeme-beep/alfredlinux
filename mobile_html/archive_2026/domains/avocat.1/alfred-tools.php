<?php
$page_title       = "Alfred's Arsenal: 1,220+ AI Tools | GoSiteMe AI Platform";
$page_description = "Explore 1,220+ AI-powered tools organized across 22 categories. Legal, education, healthcare, DevOps, security, and more — all managed by Alfred AI.";
$page_canonical   = 'https://gositeme.com/alfred-tools.php';
$page_og_title    = "Alfred's Arsenal — 1,220+ AI Tools at Your Command";
$page_og_description = '1,220+ AI tools across 22 categories. Legal, education, healthcare, DevOps, security & more. Voice-activated. Try Alfred free.';
$page_twitter_description = '1,220+ AI tools. 22 categories. Voice-activated. Try Alfred free.';
include __DIR__ . '/includes/site-header.inc.php';
?>

<style>
/* ===== ALFRED TOOLS PAGE — SCOPED STYLES ===== */

/* ---------- CSS Variables ---------- */
.at-page {
    --at-bg: #0a0a14;
    --at-surface: #12121e;
    --at-surface-hover: #1a1a2e;
    --at-border: rgba(255,255,255,.06);
    --at-text: #e4e4ec;
    --at-text-muted: #8888a4;
    --at-accent: #6c5ce7;
    --at-accent2: #00cec9;
    --at-gradient: linear-gradient(135deg, #6c5ce7, #00cec9);
    --at-gradient-soft: linear-gradient(135deg, rgba(108,92,231,.15), rgba(0,206,201,.15));
    --at-radius: 16px;
    --at-radius-sm: 10px;
    --at-transition: .3s cubic-bezier(.4,0,.2,1);
    --at-shadow: 0 8px 32px rgba(0,0,0,.35);
    --at-glow: 0 0 40px rgba(108,92,231,.2);
}

.at-page { background: var(--at-bg); color: var(--at-text); overflow-x: hidden; }

/* ---------- Utility ---------- */
.at-container { max-width: 1280px; margin: 0 auto; padding: 0 24px; }
.at-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 14px; border-radius: 50px; font-size: .78rem; font-weight: 600; letter-spacing: .5px; text-transform: uppercase;
    background: var(--at-gradient-soft); color: var(--at-accent2); border: 1px solid rgba(0,206,201,.2);
}
.at-btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 14px 32px; border: none; border-radius: 50px; cursor: pointer;
    font-size: 1rem; font-weight: 700; text-decoration: none;
    background: var(--at-gradient); color: #fff;
    box-shadow: 0 4px 24px rgba(108,92,231,.35);
    transition: transform var(--at-transition), box-shadow var(--at-transition);
}
.at-btn:hover { transform: translateY(-2px) scale(1.03); box-shadow: 0 8px 32px rgba(108,92,231,.5); }
.at-btn--outline {
    background: transparent; border: 2px solid var(--at-accent);
    color: var(--at-accent); box-shadow: none;
}
.at-btn--outline:hover { background: var(--at-accent); color: #fff; }
.at-btn--sm { padding: 10px 22px; font-size: .88rem; }

/* ---------- Hero ---------- */
.at-hero {
    position: relative; padding: 140px 0 80px; text-align: center;
    background: radial-gradient(ellipse at 50% 0%, rgba(108,92,231,.18) 0%, transparent 70%);
    overflow: hidden;
}
.at-hero::before {
    content: ''; position: absolute; inset: 0; pointer-events: none;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' xmlns='http://www.w3.org/2000/svg'%3E%3Cdefs%3E%3Cpattern id='g' width='60' height='60' patternUnits='userSpaceOnUse'%3E%3Cpath d='M60 0H0v60' fill='none' stroke='rgba(255,255,255,.03)' stroke-width='.5'/%3E%3C/pattern%3E%3C/defs%3E%3Crect fill='url(%23g)' width='60' height='60'/%3E%3C/svg%3E");
    opacity: .6;
}
.at-hero__badge { margin-bottom: 20px; }
.at-hero h1 {
    font-size: clamp(2.2rem, 5vw, 3.8rem); font-weight: 800; line-height: 1.15;
    background: var(--at-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text; margin: 0 0 16px;
}
.at-hero__sub { font-size: clamp(1rem, 2vw, 1.25rem); color: var(--at-text-muted); max-width: 640px; margin: 0 auto 32px; line-height: 1.6; }
.at-hero__counter {
    display: inline-flex; align-items: baseline; gap: 8px;
    font-size: clamp(3rem, 8vw, 5.5rem); font-weight: 900;
    background: var(--at-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
    margin-bottom: 12px;
}
.at-hero__counter-label { font-size: 1.1rem; color: var(--at-text-muted); font-weight: 400; -webkit-text-fill-color: var(--at-text-muted); }
.at-hero__ctas { display: flex; justify-content: center; gap: 16px; flex-wrap: wrap; margin-top: 10px; }
/* Floating orbs */
.at-orb {
    position: absolute; border-radius: 50%; filter: blur(80px); opacity: .25; pointer-events: none;
    animation: at-float 12s ease-in-out infinite;
}
.at-orb--1 { width: 400px; height: 400px; background: #6c5ce7; top: -120px; left: -100px; }
.at-orb--2 { width: 300px; height: 300px; background: #00cec9; bottom: -80px; right: -60px; animation-delay: -4s; }
.at-orb--3 { width: 200px; height: 200px; background: #fd79a8; top: 40%; right: 10%; animation-delay: -8s; }
@keyframes at-float {
    0%, 100% { transform: translateY(0) scale(1); }
    50% { transform: translateY(-30px) scale(1.08); }
}

/* ---------- Search ---------- */
.at-search { padding: 0 0 60px; margin-top: -20px; position: relative; z-index: 2; }
.at-search__wrap {
    max-width: 640px; margin: 0 auto; position: relative;
}
.at-search__input {
    width: 100%; padding: 18px 24px 18px 54px; border: 2px solid var(--at-border);
    border-radius: 50px; background: var(--at-surface); color: var(--at-text);
    font-size: 1.05rem; outline: none; transition: border-color var(--at-transition), box-shadow var(--at-transition);
    box-sizing: border-box;
}
.at-search__input::placeholder { color: var(--at-text-muted); }
.at-search__input:focus { border-color: var(--at-accent); box-shadow: var(--at-glow); }
.at-search__icon {
    position: absolute; left: 20px; top: 50%; transform: translateY(-50%);
    color: var(--at-text-muted); font-size: 1.1rem; pointer-events: none;
}
.at-search__count { text-align: center; margin-top: 12px; color: var(--at-text-muted); font-size: .9rem; }

/* ---------- Categories Grid ---------- */
.at-cats { padding: 0 0 80px; }
.at-cats__heading {
    text-align: center; font-size: 2rem; font-weight: 700; margin: 0 0 12px;
}
.at-cats__sub { text-align: center; color: var(--at-text-muted); margin: 0 0 48px; font-size: 1.05rem; }
.at-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 24px;
}

/* ---------- Category Card ---------- */
.at-card {
    background: var(--at-surface); border: 1px solid var(--at-border);
    border-radius: var(--at-radius); padding: 28px 24px; cursor: pointer;
    transition: transform var(--at-transition), border-color var(--at-transition), box-shadow var(--at-transition);
    position: relative; overflow: hidden;
}
.at-card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
    background: var(--at-gradient); opacity: 0; transition: opacity var(--at-transition);
}
.at-card:hover { transform: translateY(-4px); border-color: rgba(108,92,231,.3); box-shadow: var(--at-glow); }
.at-card:hover::before { opacity: 1; }
.at-card.at-card--active { border-color: var(--at-accent); box-shadow: var(--at-glow); }
.at-card.at-card--active::before { opacity: 1; }
.at-card__icon {
    width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center;
    background: var(--at-gradient-soft); font-size: 1.4rem; margin-bottom: 16px;
    color: var(--at-accent2);
}
.at-card__head { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; }
.at-card__name { font-size: 1.15rem; font-weight: 700; margin: 0; }
.at-card__count {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 32px; padding: 2px 10px; border-radius: 50px;
    background: var(--at-gradient); color: #fff; font-size: .78rem; font-weight: 700;
}
.at-card__desc { color: var(--at-text-muted); font-size: .92rem; line-height: 1.55; margin: 0 0 18px; }
.at-card__explore {
    display: inline-flex; align-items: center; gap: 6px;
    color: var(--at-accent); font-weight: 600; font-size: .9rem; background: none; border: none; cursor: pointer;
    padding: 0; transition: gap var(--at-transition);
}
.at-card:hover .at-card__explore { gap: 10px; }
.at-card__explore i { transition: transform var(--at-transition); }
.at-card:hover .at-card__explore i { transform: translateX(3px); }

/* ---------- Tool List (expanded) ---------- */
.at-tools-panel {
    display: none; grid-column: 1 / -1;
    background: var(--at-surface); border: 1px solid rgba(108,92,231,.25);
    border-radius: var(--at-radius); padding: 32px; margin-top: 8px;
    animation: at-slideDown .35s ease;
}
.at-tools-panel.at-tools-panel--open { display: block; }
@keyframes at-slideDown {
    from { opacity: 0; transform: translateY(-12px); }
    to { opacity: 1; transform: translateY(0); }
}
.at-tools-panel__head {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--at-border);
}
.at-tools-panel__title { font-size: 1.3rem; font-weight: 700; margin: 0; }
.at-tools-panel__close {
    width: 36px; height: 36px; border-radius: 50%; border: 1px solid var(--at-border); background: transparent;
    color: var(--at-text-muted); cursor: pointer; display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; transition: background var(--at-transition);
}
.at-tools-panel__close:hover { background: rgba(255,255,255,.06); }
.at-tools-list { list-style: none; padding: 0; margin: 0; display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 12px; }
.at-tools-list li {
    padding: 14px 16px; border-radius: var(--at-radius-sm);
    background: rgba(255,255,255,.03); border: 1px solid var(--at-border);
    transition: background var(--at-transition), border-color var(--at-transition);
}
.at-tools-list li:hover { background: rgba(108,92,231,.08); border-color: rgba(108,92,231,.2); }
.at-tools-list li strong { display: block; font-size: .95rem; margin-bottom: 3px; }
.at-tools-list li span { display: block; font-size: .82rem; color: var(--at-text-muted); line-height: 1.45; }
.at-card--hidden { display: none !important; }

/* ---------- Voice Badge ---------- */
.at-voice {
    padding: 60px 0; text-align: center;
    background: var(--at-gradient-soft); border-top: 1px solid var(--at-border); border-bottom: 1px solid var(--at-border);
}
.at-voice__box {
    display: inline-flex; flex-direction: column; align-items: center; gap: 16px;
    padding: 40px 56px; border-radius: var(--at-radius); background: rgba(0,0,0,.25);
    border: 1px solid rgba(108,92,231,.2);
}
.at-voice__icon {
    width: 72px; height: 72px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
    background: var(--at-gradient); font-size: 1.6rem; color: #fff;
    animation: at-pulse 2.5s ease-in-out infinite;
}
@keyframes at-pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(108,92,231,.5); }
    50% { box-shadow: 0 0 0 20px rgba(108,92,231,0); }
}
.at-voice__text { font-size: 1.2rem; font-weight: 300; color: var(--at-text-muted); }
.at-voice__cmd {
    font-family: 'Courier New', monospace; font-size: 1.15rem; font-weight: 700;
    color: var(--at-accent2); background: rgba(0,206,201,.08);
    padding: 10px 24px; border-radius: 50px; border: 1px solid rgba(0,206,201,.2);
}

/* ---------- Testimonials ---------- */
.at-testimonials { padding: 80px 0; }
.at-testimonials__heading { text-align: center; font-size: 2rem; font-weight: 700; margin: 0 0 48px; }
.at-testimonials__grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px; }
.at-testimonial {
    background: var(--at-surface); border: 1px solid var(--at-border); border-radius: var(--at-radius);
    padding: 32px; position: relative;
}
.at-testimonial__stars { color: #f1c40f; font-size: .95rem; margin-bottom: 14px; letter-spacing: 2px; }
.at-testimonial__quote { font-size: 1rem; line-height: 1.65; color: var(--at-text); margin: 0 0 20px; font-style: italic; }
.at-testimonial__author { display: flex; align-items: center; gap: 12px; }
.at-testimonial__avatar {
    width: 44px; height: 44px; border-radius: 50%;
    background: var(--at-gradient); display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 1rem; color: #fff;
}
.at-testimonial__meta strong { display: block; font-size: .95rem; }
.at-testimonial__meta span { font-size: .82rem; color: var(--at-text-muted); }

/* ---------- Pricing ---------- */
.at-pricing { padding: 80px 0; background: radial-gradient(ellipse at 50% 100%, rgba(108,92,231,.1) 0%, transparent 70%); }
.at-pricing__heading { text-align: center; font-size: 2rem; font-weight: 700; margin: 0 0 12px; }
.at-pricing__sub { text-align: center; color: var(--at-text-muted); margin: 0 0 48px; font-size: 1.05rem; }
.at-pricing__grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px; max-width: 1000px; margin: 0 auto; }
.at-plan {
    background: var(--at-surface); border: 1px solid var(--at-border); border-radius: var(--at-radius);
    padding: 36px 28px; text-align: center; transition: transform var(--at-transition), box-shadow var(--at-transition);
    position: relative;
}
.at-plan:hover { transform: translateY(-4px); }
.at-plan--featured { border-color: var(--at-accent); box-shadow: var(--at-glow); }
.at-plan--featured .at-plan__tag {
    position: absolute; top: -13px; left: 50%; transform: translateX(-50%);
    background: var(--at-gradient); color: #fff; font-size: .75rem; font-weight: 700;
    padding: 5px 18px; border-radius: 50px; text-transform: uppercase; letter-spacing: .5px;
}
.at-plan__name { font-size: 1.1rem; font-weight: 700; margin: 0 0 8px; color: var(--at-accent2); }
.at-plan__price { font-size: 2.8rem; font-weight: 900; margin: 0 0 4px; }
.at-plan__price span { font-size: 1rem; font-weight: 400; color: var(--at-text-muted); }
.at-plan__desc { color: var(--at-text-muted); font-size: .92rem; margin: 0 0 24px; }
.at-plan__features { list-style: none; padding: 0; margin: 0 0 28px; text-align: left; }
.at-plan__features li { padding: 8px 0; font-size: .92rem; color: var(--at-text); border-bottom: 1px solid var(--at-border); display: flex; align-items: center; gap: 8px; }
.at-plan__features li i { color: var(--at-accent2); font-size: .85rem; }

/* ---------- CTA ---------- */
.at-cta { padding: 80px 0; text-align: center; position: relative; }
.at-cta::before {
    content: ''; position: absolute; inset: 0; background: var(--at-gradient); opacity: .06; pointer-events: none;
}
.at-cta h2 { font-size: clamp(1.8rem, 4vw, 2.6rem); font-weight: 800; margin: 0 0 16px; }
.at-cta p { color: var(--at-text-muted); font-size: 1.1rem; max-width: 560px; margin: 0 auto 32px; }
.at-cta__buttons { display: flex; justify-content: center; gap: 16px; flex-wrap: wrap; }

/* ---------- Stats Bar ---------- */
.at-stats { padding: 60px 0; border-top: 1px solid var(--at-border); }
.at-stats__grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; text-align: center; }
.at-stat__num { font-size: 2.4rem; font-weight: 900; background: var(--at-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.at-stat__label { color: var(--at-text-muted); font-size: .9rem; margin-top: 4px; }

/* ---------- Responsive ---------- */
@media (max-width: 768px) {
    .at-hero { padding: 72px 0 56px; }
    .at-grid { grid-template-columns: 1fr; }
    .at-testimonials__grid { grid-template-columns: 1fr; }
    .at-pricing__grid { grid-template-columns: 1fr; }
    .at-stats__grid { grid-template-columns: repeat(2, 1fr); }
    .at-voice__box { padding: 28px 24px; }
    .at-tools-list { grid-template-columns: 1fr; }
}
@media (max-width: 480px) {
    .at-container { padding: 0 16px; }
    .at-hero h1 { font-size: 1.8rem; }
    .at-stats__grid { grid-template-columns: 1fr 1fr; gap: 16px; }
}
</style>

<div class="at-page">

<!-- ==================== HERO ==================== -->
<section class="at-hero">
    <div class="at-orb at-orb--1"></div>
    <div class="at-orb at-orb--2"></div>
    <div class="at-orb at-orb--3"></div>
    <div class="at-container" style="position:relative; z-index:1;">
        <div class="at-hero__badge">
            <span class="at-badge"><i class="fas fa-robot"></i> Powered by Alfred AI</span>
        </div>
        <div class="at-hero__counter">
            <span id="at-counter">0</span><span class="at-hero__counter-label">+ AI Tools</span>
        </div>
        <h1>Alfred's Arsenal</h1>
        <p class="at-hero__sub">The most comprehensive AI toolkit on any platform. 6 providers. 13,000+ tools. Voice-activated. One subscription.</p>
        <div class="at-hero__ctas">
            <button onclick="startCheckout('starter')" class="at-btn" style="border:none;cursor:pointer;"><i class="fas fa-rocket"></i> Try Alfred Free</button>
            <a href="#categories" class="at-btn at-btn--outline"><i class="fas fa-th-large"></i> Browse Tools</a>
        </div>
    </div>
</section>

<!-- ==================== STATS BAR ==================== -->
<section class="at-stats">
    <div class="at-container">
        <div class="at-stats__grid">
            <div>
                <div class="at-stat__num">13,000+</div>
                <div class="at-stat__label">AI Tools</div>
            </div>
            <div>
                <div class="at-stat__num">6</div>
                <div class="at-stat__label">Providers</div>
            </div>
            <div>
                <div class="at-stat__num">16</div>
                <div class="at-stat__label">AI Engines</div>
            </div>
            <div>
                <div class="at-stat__num">24/7</div>
                <div class="at-stat__label">Voice Access</div>
            </div>
        </div>
    </div>
</section>

<!-- ==================== SEARCH ==================== -->
<section class="at-search" id="categories">
    <div class="at-container">
        <div class="at-search__wrap">
            <i class="fas fa-search at-search__icon"></i>
            <input type="text" class="at-search__input" id="at-search" placeholder="Search tools, categories, or keywords…" autocomplete="off">
        </div>
        <div class="at-search__count" id="at-search-count">Showing all 22 categories</div>
    </div>
</section>

<!-- ==================== CATEGORY GRID ==================== -->
<section class="at-cats">
    <div class="at-container">
        <h2 class="at-cats__heading">Tool Categories</h2>
        <p class="at-cats__sub">Click any category to explore every tool inside it.</p>
        <div class="at-grid" id="at-grid">
            <!-- Cards are rendered by JS below -->
        </div>
    </div>
</section>

<!-- ==================== VOICE ACTIVATION ==================== -->
<section class="at-voice">
    <div class="at-container">
        <div class="at-voice__box">
            <div class="at-voice__icon"><i class="fas fa-microphone-alt"></i></div>
            <p class="at-voice__text">Voice-activated tools — just say:</p>
            <div class="at-voice__cmd">"Hey Alfred, show me Legal tools"</div>
            <p class="at-voice__text" style="font-size:.92rem; max-width:460px;">
                Works with any category. Alfred understands natural language and can launch any tool by voice — on desktop, mobile, or phone call.
            </p>
        </div>
    </div>
</section>

<!-- ==================== TESTIMONIALS ==================== -->
<section class="at-testimonials">
    <div class="at-container">
        <h2 class="at-testimonials__heading">What Our Users Say</h2>
        <div class="at-testimonials__grid">
            <div class="at-testimonial">
                <div class="at-testimonial__stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                <p class="at-testimonial__quote">"Alfred literally replaced five SaaS subscriptions for my law firm. The Legal &amp; Compliance tools draft motions, summarize case law, and track deadlines — all voice-activated. I saved $400/month and gained hours back every week."</p>
                <div class="at-testimonial__author">
                    <div class="at-testimonial__avatar">MR</div>
                    <div class="at-testimonial__meta">
                        <strong>Michelle R.</strong>
                        <span>Immigration Attorney, Toronto</span>
                    </div>
                </div>
            </div>
            <div class="at-testimonial">
                <div class="at-testimonial__stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                <p class="at-testimonial__quote">"I manage 30+ client sites. Alfred's Server Management and DevOps tools handle deployments, SSL renewals, uptime monitoring, and backups without me lifting a finger. It's like having a sysadmin team on call 24/7."</p>
                <div class="at-testimonial__author">
                    <div class="at-testimonial__avatar">JK</div>
                    <div class="at-testimonial__meta">
                        <strong>James K.</strong>
                        <span>Freelance Web Developer, Vancouver</span>
                    </div>
                </div>
            </div>
            <div class="at-testimonial">
                <div class="at-testimonial__stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i></div>
                <p class="at-testimonial__quote">"My students use the K-12 tools for tutoring in math and science. Parents love the safety controls. The AI explains concepts patiently, generates practice problems, and even gamifies learning. Game-changer for my classroom."</p>
                <div class="at-testimonial__author">
                    <div class="at-testimonial__avatar">DP</div>
                    <div class="at-testimonial__meta">
                        <strong>Diane P.</strong>
                        <span>Grade 8 Teacher, Montréal</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==================== PRICING ==================== -->
<section class="at-pricing">
    <div class="at-container">
        <h2 class="at-pricing__heading">Simple, Powerful Pricing</h2>
        <p class="at-pricing__sub">Every plan includes Alfred AI and all 1,220+ tools.</p>
        <div class="at-pricing__grid">
            <div class="at-plan">
                <div class="at-plan__name">Starter</div>
                <div class="at-plan__price">$3.99<span>/mo</span></div>
                <p class="at-plan__desc">Perfect for personal projects</p>
                <ul class="at-plan__features">
                    <li><i class="fas fa-check"></i> All 1,220+ AI tools</li>
                    <li><i class="fas fa-check"></i> 1 website</li>
                    <li><i class="fas fa-check"></i> 10 GB NVMe storage</li>
                    <li><i class="fas fa-check"></i> Free SSL certificate</li>
                    <li><i class="fas fa-check"></i> Alfred voice commands</li>
                </ul>
                <button onclick="startCheckout('starter')" class="at-btn at-btn--outline at-btn--sm" style="width:100%; justify-content:center;border:none;cursor:pointer;">Get Started</button>
            </div>
            <div class="at-plan at-plan--featured">
                <div class="at-plan__tag">Most Popular</div>
                <div class="at-plan__name">Professional</div>
                <div class="at-plan__price">$9.99<span>/mo</span></div>
                <p class="at-plan__desc">For growing businesses</p>
                <ul class="at-plan__features">
                    <li><i class="fas fa-check"></i> All 1,220+ AI tools</li>
                    <li><i class="fas fa-check"></i> Unlimited websites</li>
                    <li><i class="fas fa-check"></i> 50 GB NVMe storage</li>
                    <li><i class="fas fa-check"></i> Free domain + SSL</li>
                    <li><i class="fas fa-check"></i> Priority Alfred voice</li>
                    <li><i class="fas fa-check"></i> Fleet orchestration</li>
                </ul>
                <button onclick="startCheckout('professional')" class="at-btn at-btn--sm" style="width:100%; justify-content:center;border:none;cursor:pointer;">Get Started</button>
            </div>
            <div class="at-plan">
                <div class="at-plan__name">Enterprise</div>
                <div class="at-plan__price">$24.99<span>/mo</span></div>
                <p class="at-plan__desc">Full power, unlimited scale</p>
                <ul class="at-plan__features">
                    <li><i class="fas fa-check"></i> All 1,220+ AI tools</li>
                    <li><i class="fas fa-check"></i> Unlimited everything</li>
                    <li><i class="fas fa-check"></i> 200 GB NVMe storage</li>
                    <li><i class="fas fa-check"></i> Dedicated resources</li>
                    <li><i class="fas fa-check"></i> White-label Alfred</li>
                    <li><i class="fas fa-check"></i> Phone support + SLA</li>
                </ul>
                <button onclick="startCheckout('enterprise')" class="at-btn at-btn--outline at-btn--sm" style="width:100%; justify-content:center;border:none;cursor:pointer;">Contact Sales</button>
            </div>
        </div>
    </div>
</section>

<!-- ==================== FINAL CTA ==================== -->
<section class="at-cta">
    <div class="at-container" style="position:relative; z-index:1;">
        <h2>Ready to Unleash Alfred?</h2>
        <p>1,220+ AI tools. 22 categories. One platform. Start building smarter today.</p>
        <div class="at-cta__buttons">
            <button onclick="startCheckout('professional')" class="at-btn" style="border:none;cursor:pointer;"><i class="fas fa-bolt"></i> Start Free Trial</button>
            <a href="/alfred.php" class="at-btn at-btn--outline"><i class="fas fa-info-circle"></i> Learn About Alfred</a>
        </div>
    </div>
</section>

</div><!-- /.at-page -->


<script src="/assets/js/alfred-tools-engine.js"></script>


<!-- Explore More Interlinks -->
<section style="padding:3rem 1.5rem;text-align:center;">
    <div style="max-width:900px;margin:0 auto;">
        <h3 style="color:#a29bfe;margin-bottom:1.5rem;">Explore More</h3>
        <div style="display:flex;flex-wrap:wrap;gap:1rem;justify-content:center;">
            <a href="/marketplace.php" class="btn btn-outline">Marketplace</a>
            <a href="/pricing.php" class="btn btn-outline">View Pricing</a>
            <a href="/fleet-dashboard.php" class="btn btn-outline">Fleet Dashboard</a>
            <a href="/docs/" class="btn btn-outline">Documentation</a>
            <a href="/articles/" class="btn btn-outline">Articles</a>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
