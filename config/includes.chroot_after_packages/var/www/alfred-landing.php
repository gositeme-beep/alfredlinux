<?php
$page_title = 'Alfred AI — Your AI That Actually Gets Things Done | GoSiteMe';
$page_description = 'Meet Alfred: 13,000+ AI tools, voice commands, fleet orchestration, and a consciousness layer that learns. Draft contracts, analyze data, schedule teams, find grants — all by voice or chat. Start free.';
$page_canonical = 'https://root.com/alfred-landing.php';
$page_og_title = 'Alfred AI — 13,000+ Tools, Voice-First, Gets Things Done';
$page_og_description = 'Meet Alfred: the AI assistant with 13,000+ tools, voice commands, fleet orchestration, and a consciousness that learns. Start your free trial.';
$page_twitter_description = 'Alfred AI: 13,000+ tools, voice-first, fleet orchestration. Your AI that actually gets things done.';
$page_og_image = 'https://root.com/assets/img/alfred-landing-og.png';
$page_og_image_alt = 'Alfred AI — Your AI That Actually Gets Things Done';
$noGlobalMain = true;
include __DIR__ . '/includes/site-header.inc.php';
?>

<style>
/* ===== Alfred Landing Page Styles ===== */
:root {
    --al-bg: #0a0a14;
    --al-surface: #12121e;
    --al-surface-2: #1a1a2e;
    --al-border: rgba(255,255,255,0.08);
    --al-accent: #6c5ce7;
    --al-accent-light: #a29bfe;
    --al-blue: #0984e3;
    --al-green: #00b894;
    --al-orange: #fdcb6e;
    --al-fire: #e17055;
    --al-pink: #fd79a8;
    --al-cyan: #00cec9;
    --al-text: #e8e8f0;
    --al-text-muted: #8a8a9a;
    --al-radius: 16px;
    --al-shadow: 0 4px 24px rgba(0,0,0,0.35);
    --al-gradient: linear-gradient(135deg, #6c5ce7 0%, #0984e3 50%, #00b894 100%);
}

/* ===== HERO ===== */
.al-hero {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 120px 20px 80px;
    position: relative;
    overflow: hidden;
    background: radial-gradient(ellipse at 50% 0%, #1a1033 0%, var(--al-bg) 70%);
}
.al-hero::before {
    content: '';
    position: absolute;
    top: -40%;
    left: -20%;
    width: 140%;
    height: 180%;
    background:
        radial-gradient(circle at 25% 30%, rgba(108,92,231,0.12) 0%, transparent 50%),
        radial-gradient(circle at 75% 60%, rgba(9,132,227,0.08) 0%, transparent 50%),
        radial-gradient(circle at 50% 80%, rgba(0,184,148,0.06) 0%, transparent 40%);
    animation: alHeroGlow 10s ease-in-out infinite alternate;
    pointer-events: none;
}
@keyframes alHeroGlow {
    0% { transform: scale(1) translateY(0); }
    100% { transform: scale(1.08) translateY(-20px); }
}
.al-hero-inner {
    max-width: 800px;
    position: relative;
    z-index: 2;
}
.al-hero h1 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: clamp(2.4rem, 6vw, 4.2rem);
    font-weight: 800;
    color: #fff;
    margin: 0 0 8px;
    line-height: 1.1;
}
.al-hero h1 .highlight {
    background: var(--al-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.al-hero .subtitle {
    font-size: clamp(1.1rem, 2.5vw, 1.5rem);
    color: var(--al-text-muted);
    margin: 16px 0 12px;
    font-weight: 400;
}

/* Typing Effect */
.al-typing-wrap {
    height: 48px;
    margin: 20px 0 36px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.al-typing {
    font-family: 'Space Grotesk', monospace;
    font-size: clamp(1rem, 2.2vw, 1.35rem);
    color: var(--al-accent-light);
    border-right: 2px solid var(--al-accent-light);
    padding-right: 4px;
    animation: alBlink 0.7s step-end infinite;
    white-space: nowrap;
    overflow: hidden;
}
@keyframes alBlink {
    50% { border-color: transparent; }
}

/* Hero CTAs */
.al-hero-ctas {
    display: flex;
    gap: 16px;
    justify-content: center;
    flex-wrap: wrap;
    margin-bottom: 50px;
}
.al-btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 16px 36px;
    background: var(--al-gradient);
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 1.1rem;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    transition: transform 0.3s, box-shadow 0.3s;
    box-shadow: 0 4px 20px rgba(108,92,231,0.35);
}
.al-btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 32px rgba(108,92,231,0.5);
}
.al-btn-outline {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 16px 36px;
    background: transparent;
    color: #fff;
    border: 2px solid rgba(255,255,255,0.2);
    border-radius: 12px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.3s;
}
.al-btn-outline:hover {
    border-color: var(--al-accent-light);
    background: rgba(108,92,231,0.08);
    transform: translateY(-2px);
}

/* Floating Device Mockup */
.al-device-mockup {
    width: 340px;
    height: 220px;
    margin: 0 auto;
    position: relative;
    perspective: 800px;
}
.al-device-screen {
    width: 100%;
    height: 100%;
    background: var(--al-surface);
    border: 2px solid var(--al-border);
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.4), 0 0 40px rgba(108,92,231,0.15);
    transform: rotateX(5deg);
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}
.al-device-screen::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 32px;
    background: var(--al-surface-2);
    border-bottom: 1px solid var(--al-border);
}
.al-device-dots {
    position: absolute;
    top: 10px;
    left: 14px;
    display: flex;
    gap: 6px;
    z-index: 2;
}
.al-device-dots span {
    width: 10px;
    height: 10px;
    border-radius: 50%;
}
.al-device-dots span:nth-child(1) { background: #e17055; }
.al-device-dots span:nth-child(2) { background: #fdcb6e; }
.al-device-dots span:nth-child(3) { background: #00b894; }
.al-device-content {
    font-family: monospace;
    color: var(--al-accent-light);
    font-size: 0.85rem;
    text-align: left;
    padding: 44px 20px 20px;
    width: 100%;
}
.al-device-content .prompt {
    color: var(--al-green);
}
.al-device-content .response {
    color: var(--al-text-muted);
    margin-top: 8px;
}

/* ===== STATS BAR ===== */
.al-stats-bar {
    background: var(--al-surface);
    border-top: 1px solid var(--al-border);
    border-bottom: 1px solid var(--al-border);
    padding: 28px 20px;
    display: flex;
    justify-content: center;
    gap: 40px;
    flex-wrap: wrap;
}
.al-stat {
    text-align: center;
    min-width: 120px;
}
.al-stat .value {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.6rem;
    font-weight: 800;
    color: #fff;
}
.al-stat .value span {
    color: var(--al-accent-light);
}
.al-stat .label {
    font-size: 0.85rem;
    color: var(--al-text-muted);
    margin-top: 2px;
}

/* ===== HOW IT WORKS ===== */
.al-section {
    padding: 100px 20px;
    max-width: 1200px;
    margin: 0 auto;
}
.al-section-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: clamp(1.8rem, 4vw, 2.8rem);
    font-weight: 800;
    color: #fff;
    text-align: center;
    margin-bottom: 16px;
}
.al-section-sub {
    text-align: center;
    color: var(--al-text-muted);
    font-size: 1.1rem;
    max-width: 600px;
    margin: 0 auto 60px;
}
.al-steps {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 40px;
}
.al-step {
    text-align: center;
    padding: 40px 24px;
    background: var(--al-surface);
    border: 1px solid var(--al-border);
    border-radius: var(--al-radius);
    transition: transform 0.3s, border-color 0.3s;
    position: relative;
}
.al-step:hover {
    transform: translateY(-6px);
    border-color: var(--al-accent);
}
.al-step-num {
    position: absolute;
    top: -18px;
    left: 50%;
    transform: translateX(-50%);
    width: 36px;
    height: 36px;
    background: var(--al-accent);
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.95rem;
    box-shadow: 0 4px 12px rgba(108,92,231,0.3);
}
.al-step-icon {
    font-size: 2.5rem;
    margin-bottom: 20px;
    background: var(--al-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.al-step h3 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.25rem;
    font-weight: 700;
    color: #fff;
    margin: 0 0 10px;
}
.al-step p {
    color: var(--al-text-muted);
    font-size: 0.95rem;
    line-height: 1.55;
    margin: 0;
}

/* ===== FEATURE SHOWCASE ===== */
.al-features {
    padding: 60px 20px 100px;
    max-width: 1200px;
    margin: 0 auto;
}
.al-feature {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    align-items: center;
    margin-bottom: 100px;
}
.al-feature:nth-child(even) {
    direction: rtl;
}
.al-feature:nth-child(even) > * {
    direction: ltr;
}
.al-feature-visual {
    background: var(--al-surface);
    border: 1px solid var(--al-border);
    border-radius: var(--al-radius);
    height: 320px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
}
.al-feature-visual::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(108,92,231,0.06) 0%, transparent 60%);
}
.al-feature-visual i {
    font-size: 5rem;
    color: var(--al-accent-light);
    opacity: 0.3;
    z-index: 1;
}
.al-feature-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    background: rgba(108,92,231,0.12);
    color: var(--al-accent-light);
    border-radius: 50px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 14px;
}
.al-feature-text h3 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.8rem;
    font-weight: 700;
    color: #fff;
    margin: 0 0 14px;
}
.al-feature-text p {
    color: var(--al-text-muted);
    font-size: 1.05rem;
    line-height: 1.65;
    margin: 0 0 20px;
}
.al-feature-list {
    list-style: none;
    padding: 0;
    margin: 0;
}
.al-feature-list li {
    padding: 6px 0;
    color: var(--al-text);
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    gap: 10px;
}
.al-feature-list li i {
    color: var(--al-green);
    font-size: 0.85rem;
}

/* ===== PRICING ===== */
.al-pricing {
    padding: 100px 20px;
    background: linear-gradient(180deg, var(--al-bg) 0%, #0d0d1a 100%);
}
.al-pricing-inner {
    max-width: 1100px;
    margin: 0 auto;
}
.al-pricing-toggle {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 14px;
    margin-bottom: 50px;
}
.al-pricing-toggle span {
    color: var(--al-text-muted);
    font-size: 0.95rem;
    font-weight: 500;
}
.al-pricing-toggle span.active {
    color: #fff;
}
.al-toggle-switch {
    width: 52px;
    height: 28px;
    background: var(--al-surface-2);
    border: 1px solid var(--al-border);
    border-radius: 50px;
    position: relative;
    cursor: pointer;
    transition: background 0.3s;
}
.al-toggle-switch.on {
    background: var(--al-accent);
    border-color: var(--al-accent);
}
.al-toggle-switch::after {
    content: '';
    position: absolute;
    top: 3px;
    left: 3px;
    width: 20px;
    height: 20px;
    background: #fff;
    border-radius: 50%;
    transition: transform 0.3s;
}
.al-toggle-switch.on::after {
    transform: translateX(24px);
}
.al-save-badge {
    background: var(--al-green);
    color: #fff;
    padding: 3px 10px;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 700;
}
.al-pricing-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
    align-items: start;
}
.al-price-card {
    background: var(--al-surface);
    border: 1px solid var(--al-border);
    border-radius: var(--al-radius);
    padding: 40px 32px;
    position: relative;
    transition: transform 0.3s, border-color 0.3s, box-shadow 0.3s;
}
.al-price-card:hover {
    transform: translateY(-6px);
    box-shadow: var(--al-shadow);
}
.al-price-card.featured {
    border-color: var(--al-accent);
    box-shadow: 0 0 40px rgba(108,92,231,0.15);
    transform: scale(1.04);
}
.al-price-card.featured:hover {
    transform: scale(1.04) translateY(-6px);
}
.al-popular-badge {
    position: absolute;
    top: -14px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--al-accent);
    color: #fff;
    padding: 5px 20px;
    border-radius: 50px;
    font-size: 0.8rem;
    font-weight: 700;
    white-space: nowrap;
    box-shadow: 0 4px 12px rgba(108,92,231,0.3);
}
.al-price-card-name {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.3rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 8px;
}
.al-price-card-desc {
    color: var(--al-text-muted);
    font-size: 0.9rem;
    margin-bottom: 24px;
}
.al-price-amount {
    font-family: 'Space Grotesk', sans-serif;
    margin-bottom: 6px;
}
.al-price-amount .currency {
    font-size: 1.2rem;
    color: var(--al-text-muted);
    vertical-align: top;
}
.al-price-amount .amount {
    font-size: 3rem;
    font-weight: 800;
    color: #fff;
}
.al-price-amount .period {
    font-size: 0.95rem;
    color: var(--al-text-muted);
}
.al-price-original {
    font-size: 0.85rem;
    color: var(--al-text-muted);
    text-decoration: line-through;
    margin-bottom: 20px;
    display: none;
}
.al-price-features {
    list-style: none;
    padding: 0;
    margin: 0 0 30px;
}
.al-price-features li {
    padding: 8px 0;
    color: var(--al-text);
    font-size: 0.92rem;
    display: flex;
    align-items: center;
    gap: 10px;
    border-bottom: 1px solid rgba(255,255,255,0.04);
}
.al-price-features li:last-child {
    border-bottom: none;
}
.al-price-features li i {
    color: var(--al-green);
    font-size: 0.8rem;
    width: 16px;
    text-align: center;
}
.al-price-btn {
    display: block;
    width: 100%;
    padding: 14px;
    border: none;
    border-radius: 10px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    text-align: center;
    text-decoration: none;
    transition: all 0.3s;
}
.al-price-btn-primary {
    background: var(--al-gradient);
    color: #fff;
    box-shadow: 0 4px 16px rgba(108,92,231,0.3);
}
.al-price-btn-primary:hover {
    box-shadow: 0 8px 28px rgba(108,92,231,0.5);
    transform: translateY(-2px);
}
.al-price-btn-secondary {
    background: var(--al-surface-2);
    color: #fff;
    border: 1px solid var(--al-border);
}
.al-price-btn-secondary:hover {
    border-color: var(--al-accent);
    background: rgba(108,92,231,0.08);
}

/* ===== DEMOGRAPHIC SHOWCASE ===== */
.al-demographics {
    padding: 100px 20px;
    max-width: 1100px;
    margin: 0 auto;
    text-align: center;
}
.al-demo-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 20px;
    margin-top: 50px;
}
.al-demo-card {
    background: var(--al-surface);
    border: 1px solid var(--al-border);
    border-radius: var(--al-radius);
    padding: 28px 16px;
    transition: all 0.3s;
    text-decoration: none;
    display: block;
}
.al-demo-card:hover {
    transform: translateY(-4px);
    border-color: var(--al-accent);
    box-shadow: 0 4px 20px rgba(108,92,231,0.15);
}
.al-demo-card i {
    font-size: 2.2rem;
    margin-bottom: 12px;
    display: block;
}
.al-demo-card span {
    font-size: 0.9rem;
    font-weight: 600;
    color: #fff;
}

/* ===== FAQ ===== */
.al-faq {
    padding: 100px 20px;
    max-width: 800px;
    margin: 0 auto;
}
.al-faq-item {
    border: 1px solid var(--al-border);
    border-radius: 12px;
    margin-bottom: 12px;
    overflow: hidden;
    transition: border-color 0.3s;
}
.al-faq-item:hover {
    border-color: var(--al-accent);
}
.al-faq-q {
    width: 100%;
    padding: 20px 24px;
    background: var(--al-surface);
    border: none;
    color: #fff;
    font-size: 1rem;
    font-weight: 600;
    text-align: left;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: background 0.3s;
}
.al-faq-q:hover {
    background: var(--al-surface-2);
}
.al-faq-q i {
    transition: transform 0.3s;
    color: var(--al-accent-light);
}
.al-faq-q.open i {
    transform: rotate(180deg);
}
.al-faq-a {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.4s ease, padding 0.3s;
    background: var(--al-surface);
}
.al-faq-a.show {
    max-height: 300px;
    padding: 0 24px 20px;
}
.al-faq-a p {
    color: var(--al-text-muted);
    font-size: 0.95rem;
    line-height: 1.6;
    margin: 0;
}

/* ===== FINAL CTA ===== */
.al-final-cta {
    padding: 120px 20px;
    text-align: center;
    background: linear-gradient(135deg, #0d0d1a 0%, #1a1033 50%, #0d0d1a 100%);
    position: relative;
    overflow: hidden;
}
.al-final-cta::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(circle at 30% 50%, rgba(108,92,231,0.1) 0%, transparent 50%),
        radial-gradient(circle at 70% 50%, rgba(0,184,148,0.08) 0%, transparent 50%);
    pointer-events: none;
}
.al-final-cta h2 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: clamp(1.8rem, 4vw, 2.8rem);
    font-weight: 800;
    color: #fff;
    margin: 0 0 16px;
    position: relative;
    z-index: 1;
}
.al-final-cta p {
    color: var(--al-text-muted);
    font-size: 1.1rem;
    margin: 0 0 36px;
    position: relative;
    z-index: 1;
}
.al-email-form {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
    max-width: 520px;
    margin: 0 auto;
    position: relative;
    z-index: 1;
}
.al-email-form input[type="email"] {
    flex: 1;
    min-width: 240px;
    padding: 16px 22px;
    background: var(--al-surface);
    border: 1px solid var(--al-border);
    border-radius: 12px;
    color: var(--al-text);
    font-size: 1rem;
    outline: none;
    transition: border-color 0.3s;
}
.al-email-form input[type="email"]:focus {
    border-color: var(--al-accent);
}
.al-email-form button {
    padding: 16px 32px;
    background: var(--al-gradient);
    color: #fff;
    border: none;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    transition: transform 0.3s, box-shadow 0.3s;
}
.al-email-form button:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 28px rgba(108,92,231,0.5);
}

/* ===== SCROLL ANIMATIONS ===== */
.al-reveal {
    opacity: 0;
    transform: translateY(40px);
    transition: opacity 0.7s ease, transform 0.7s ease;
}
.al-reveal.visible {
    opacity: 1;
    transform: translateY(0);
}

/* ===== RESPONSIVE ===== */
@media (max-width: 968px) {
    .al-steps { grid-template-columns: 1fr; max-width: 450px; margin: 0 auto; }
    .al-feature { grid-template-columns: 1fr; gap: 30px; }
    .al-feature:nth-child(even) { direction: ltr; }
    .al-feature-visual { height: 220px; }
    .al-pricing-grid { grid-template-columns: 1fr; max-width: 420px; margin: 0 auto; }
    .al-price-card.featured { transform: none; }
    .al-price-card.featured:hover { transform: translateY(-6px); }
}
@media (max-width: 768px) {
    .al-hero { padding: 100px 16px 60px; min-height: auto; }
    .al-stats-bar { gap: 24px; padding: 24px 16px; }
    .al-stat .value { font-size: 1.3rem; }
    .al-device-mockup { width: 280px; height: 180px; }
    .al-demo-grid { grid-template-columns: repeat(3, 1fr); gap: 12px; }
}
@media (max-width: 480px) {
    .al-hero-ctas { flex-direction: column; align-items: center; }
    .al-btn-primary, .al-btn-outline { width: 100%; justify-content: center; }
    .al-demo-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>

<main id="main">

<!-- ===== HERO SECTION ===== -->
<section class="al-hero">
    <div class="al-hero-inner">
        <h1>Meet <span class="highlight">Alfred.</span><br>Your AI That Actually Gets Things Done.</h1>
        <p class="subtitle">13,000+ tools. Voice-first. One subscription. Zero learning curve.</p>

        <div class="al-typing-wrap">
            <span class="al-typing" id="alTyping"></span>
        </div>

        <div class="al-hero-ctas">
            <button onclick="startCheckout('professional')" class="al-btn-primary" style="border:none;cursor:pointer;"><i class="fas fa-bolt"></i> Start Free Trial</button>
            <a href="#how-it-works" class="al-btn-outline"><i class="fas fa-play"></i> Watch Demo</a>
        </div>

        <!-- Floating Device Mockup -->
        <div class="al-device-mockup">
            <div class="al-device-screen">
                <div class="al-device-dots"><span></span><span></span><span></span></div>
                <div class="al-device-content">
                    <div class="prompt">$ Hey Alfred, draft an NDA for Acme Corp</div>
                    <div class="response">✓ NDA generated — 3 pages, mutual, 2-year term.<br>&nbsp;&nbsp;Ready for review. [Open] [Edit] [Send]</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== STATS BAR ===== -->
<div class="al-stats-bar">
    <div class="al-stat">
        <div class="value"><span>13,000+</span></div>
        <div class="label">AI Tools</div>
    </div>
    <div class="al-stat">
        <div class="value"><span>22</span></div>
        <div class="label">Categories</div>
    </div>
    <div class="al-stat">
        <div class="value">Voice + Chat + API</div>
        <div class="label">Access Methods</div>
    </div>
    <div class="al-stat">
        <div class="value"><span>99.9%</span></div>
        <div class="label">Uptime</div>
    </div>
</div>

<!-- Trust Signals Banner -->
<section style="padding:2rem 0;background:rgba(108,92,231,0.05);border-top:1px solid rgba(108,92,231,0.15);border-bottom:1px solid rgba(108,92,231,0.15);">
    <div style="max-width:1100px;margin:0 auto;display:flex;justify-content:center;flex-wrap:wrap;gap:2.5rem;padding:0 1.5rem;">
        <div style="text-align:center;">
            <div style="font-size:2rem;font-weight:800;font-family:'Space Grotesk',sans-serif;color:#6c5ce7;">13,000+</div>
            <div style="font-size:0.8rem;opacity:0.6;text-transform:uppercase;letter-spacing:1px;">AI Tools</div>
        </div>
        <div style="text-align:center;">
            <div style="font-size:2rem;font-weight:800;font-family:'Space Grotesk',sans-serif;color:#00b894;">26</div>
            <div style="font-size:0.8rem;opacity:0.6;text-transform:uppercase;letter-spacing:1px;">AI Models</div>
        </div>
        <div style="text-align:center;">
            <div style="font-size:2rem;font-weight:800;font-family:'Space Grotesk',sans-serif;color:#0984e3;">24</div>
            <div style="font-size:0.8rem;opacity:0.6;text-transform:uppercase;letter-spacing:1px;">Voice Agents</div>
        </div>
        <div style="text-align:center;">
            <div style="font-size:2rem;font-weight:800;font-family:'Space Grotesk',sans-serif;color:#e17055;">17</div>
            <div style="font-size:0.8rem;opacity:0.6;text-transform:uppercase;letter-spacing:1px;">Categories</div>
        </div>
        <div style="text-align:center;">
            <div style="font-size:2rem;font-weight:800;font-family:'Space Grotesk',sans-serif;color:#fdcb6e;">$3.99</div>
            <div style="font-size:0.8rem;opacity:0.6;text-transform:uppercase;letter-spacing:1px;">Starting Price</div>
        </div>
    </div>
</section>

<!-- Quick Category Showcase -->
<section style="padding:4rem 1.5rem;max-width:1200px;margin:0 auto;">
    <h2 style="text-align:center;font-family:'Space Grotesk',sans-serif;margin-bottom:0.5rem;">What Can Alfred Do?</h2>
    <p style="text-align:center;opacity:0.6;margin-bottom:2.5rem;">13,000+ tools across 27 industry verticals</p>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:1rem;">
        <a href="/use-cases/legal" style="display:block;padding:1.5rem;background:#12121e;border:1px solid rgba(225,112,85,0.25);border-radius:12px;text-align:center;text-decoration:none;color:#fff;transition:all .2s;position:relative;">
            <div style="position:absolute;top:8px;right:10px;font-size:.6rem;background:rgba(225,112,85,0.2);color:#e17055;padding:2px 8px;border-radius:8px;font-weight:700;">HOT</div>
            <div style="font-size:1.5rem;margin-bottom:0.5rem;">⚖️</div>
            <div style="font-size:0.85rem;font-weight:600;">Legal &amp; Law</div>
            <div style="font-size:0.7rem;opacity:0.5;margin-top:0.25rem;">43+ tools</div>
        </a>
        <a href="/tools/category/web-hosting" style="display:block;padding:1.5rem;background:#12121e;border:1px solid rgba(108,92,231,0.25);border-radius:12px;text-align:center;text-decoration:none;color:#fff;transition:all .2s;position:relative;">
            <div style="position:absolute;top:8px;right:10px;font-size:.6rem;background:rgba(108,92,231,0.2);color:#a29bfe;padding:2px 8px;border-radius:8px;font-weight:700;">CORE</div>
            <div style="font-size:1.5rem;margin-bottom:0.5rem;">🌐</div>
            <div style="font-size:0.85rem;font-weight:600;">Web Hosting</div>
            <div style="font-size:0.7rem;opacity:0.5;margin-top:0.25rem;">500+ tools</div>
        </a>
        <a href="/use-cases/healthcare" style="display:block;padding:1.5rem;background:#12121e;border:1px solid rgba(253,121,168,0.15);border-radius:12px;text-align:center;text-decoration:none;color:#fff;transition:all .2s;">
            <div style="font-size:1.5rem;margin-bottom:0.5rem;">🏥</div>
            <div style="font-size:0.85rem;font-weight:600;">Healthcare</div>
            <div style="font-size:0.7rem;opacity:0.5;margin-top:0.25rem;">12+ tools</div>
        </a>
        <a href="/use-cases/education" style="display:block;padding:1.5rem;background:#12121e;border:1px solid rgba(253,203,110,0.15);border-radius:12px;text-align:center;text-decoration:none;color:#fff;transition:all .2s;">
            <div style="font-size:1.5rem;margin-bottom:0.5rem;">🎓</div>
            <div style="font-size:0.85rem;font-weight:600;">Education</div>
            <div style="font-size:0.7rem;opacity:0.5;margin-top:0.25rem;">30+ tools</div>
        </a>
        <a href="/use-cases/business" style="display:block;padding:1.5rem;background:#12121e;border:1px solid rgba(9,132,227,0.15);border-radius:12px;text-align:center;text-decoration:none;color:#fff;transition:all .2s;">
            <div style="font-size:1.5rem;margin-bottom:0.5rem;">💼</div>
            <div style="font-size:0.85rem;font-weight:600;">Business</div>
            <div style="font-size:0.7rem;opacity:0.5;margin-top:0.25rem;">22+ tools</div>
        </a>
        <a href="/use-cases/ecommerce" style="display:block;padding:1.5rem;background:#12121e;border:1px solid rgba(9,132,227,0.15);border-radius:12px;text-align:center;text-decoration:none;color:#fff;transition:all .2s;">
            <div style="font-size:1.5rem;margin-bottom:0.5rem;">🛒</div>
            <div style="font-size:0.85rem;font-weight:600;">E-Commerce</div>
            <div style="font-size:0.7rem;opacity:0.5;margin-top:0.25rem;">20+ tools</div>
        </a>
        <a href="/use-cases/developers" style="display:block;padding:1.5rem;background:#12121e;border:1px solid rgba(0,184,148,0.15);border-radius:12px;text-align:center;text-decoration:none;color:#fff;transition:all .2s;">
            <div style="font-size:1.5rem;margin-bottom:0.5rem;">👨‍💻</div>
            <div style="font-size:0.85rem;font-weight:600;">Developers</div>
            <div style="font-size:0.7rem;opacity:0.5;margin-top:0.25rem;">13,000+ tools</div>
        </a>
        <a href="/use-cases/realestate" style="display:block;padding:1.5rem;background:#12121e;border:1px solid rgba(0,206,201,0.15);border-radius:12px;text-align:center;text-decoration:none;color:#fff;transition:all .2s;">
            <div style="font-size:1.5rem;margin-bottom:0.5rem;">🏠</div>
            <div style="font-size:0.85rem;font-weight:600;">Real Estate</div>
            <div style="font-size:0.7rem;opacity:0.5;margin-top:0.25rem;">10+ tools</div>
        </a>
        <a href="/use-cases/dental" style="display:block;padding:1.5rem;background:#12121e;border:1px solid rgba(0,184,148,0.15);border-radius:12px;text-align:center;text-decoration:none;color:#fff;transition:all .2s;">
            <div style="font-size:1.5rem;margin-bottom:0.5rem;">🦷</div>
            <div style="font-size:0.85rem;font-weight:600;">Dental</div>
            <div style="font-size:0.7rem;opacity:0.5;margin-top:0.25rem;">24/7 scheduling</div>
        </a>
        <a href="/use-cases/students" style="display:block;padding:1.5rem;background:#12121e;border:1px solid rgba(108,92,231,0.15);border-radius:12px;text-align:center;text-decoration:none;color:#fff;transition:all .2s;">
            <div style="font-size:1.5rem;margin-bottom:0.5rem;">📚</div>
            <div style="font-size:0.85rem;font-weight:600;">Students</div>
            <div style="font-size:0.7rem;opacity:0.5;margin-top:0.25rem;">14+ tools</div>
        </a>
        <a href="/use-cases/creators" style="display:block;padding:1.5rem;background:#12121e;border:1px solid rgba(225,112,85,0.15);border-radius:12px;text-align:center;text-decoration:none;color:#fff;transition:all .2s;">
            <div style="font-size:1.5rem;margin-bottom:0.5rem;">🎬</div>
            <div style="font-size:0.85rem;font-weight:600;">Creators</div>
            <div style="font-size:0.7rem;opacity:0.5;margin-top:0.25rem;">18+ tools</div>
        </a>
        <a href="/alfred-voice-live/" style="display:block;padding:1.5rem;background:#12121e;border:1px solid rgba(0,184,148,0.15);border-radius:12px;text-align:center;text-decoration:none;color:#fff;transition:all .2s;">
            <div style="font-size:1.5rem;margin-bottom:0.5rem;">📞</div>
            <div style="font-size:0.85rem;font-weight:600;">Voice AI</div>
            <div style="font-size:0.7rem;opacity:0.5;margin-top:0.25rem;">85+ tools</div>
        </a>
        <a href="/use-cases/restaurants" style="display:block;padding:1.5rem;background:#12121e;border:1px solid rgba(225,112,85,0.15);border-radius:12px;text-align:center;text-decoration:none;color:#fff;transition:all .2s;">
            <div style="font-size:1.5rem;margin-bottom:0.5rem;">🍽️</div>
            <div style="font-size:0.85rem;font-weight:600;">Restaurants</div>
            <div style="font-size:0.7rem;opacity:0.5;margin-top:0.25rem;">15+ tools</div>
        </a>
        <a href="/use-cases/insurance" style="display:block;padding:1.5rem;background:#12121e;border:1px solid rgba(108,92,231,0.15);border-radius:12px;text-align:center;text-decoration:none;color:#fff;transition:all .2s;">
            <div style="font-size:1.5rem;margin-bottom:0.5rem;">🛡️</div>
            <div style="font-size:0.85rem;font-weight:600;">Insurance</div>
            <div style="font-size:0.7rem;opacity:0.5;margin-top:0.25rem;">18+ tools</div>
        </a>
        <a href="/use-cases/accounting" style="display:block;padding:1.5rem;background:#12121e;border:1px solid rgba(0,184,148,0.15);border-radius:12px;text-align:center;text-decoration:none;color:#fff;transition:all .2s;">
            <div style="font-size:1.5rem;margin-bottom:0.5rem;">📊</div>
            <div style="font-size:0.85rem;font-weight:600;">Accounting</div>
            <div style="font-size:0.7rem;opacity:0.5;margin-top:0.25rem;">20+ tools</div>
        </a>
        <a href="/use-cases/logistics" style="display:block;padding:1.5rem;background:#12121e;border:1px solid rgba(9,132,227,0.15);border-radius:12px;text-align:center;text-decoration:none;color:#fff;transition:all .2s;">
            <div style="font-size:1.5rem;margin-bottom:0.5rem;">🚚</div>
            <div style="font-size:0.85rem;font-weight:600;">Logistics</div>
            <div style="font-size:0.7rem;opacity:0.5;margin-top:0.25rem;">15+ tools</div>
        </a>
    </div>
    <div style="text-align:center;margin-top:2rem;">
        <a href="/use-cases/" style="color:#a29bfe;font-size:0.9rem;">View all 27 industry verticals &rarr;</a>
    </div>
</section>

<!-- ===== HOW IT WORKS ===== -->
<section class="al-section al-reveal" id="how-it-works">
    <h2 class="al-section-title">How Alfred Works</h2>
    <p class="al-section-sub">Three simple steps from request to professional results — by voice, text, or API.</p>

    <div class="al-steps">
        <div class="al-step">
            <div class="al-step-num">1</div>
            <div class="al-step-icon"><i class="fas fa-microphone-alt"></i></div>
            <h3>Tell Alfred</h3>
            <p>Speak naturally or type your request. "Draft my contract," "Analyze this spreadsheet," "Schedule a meeting." Alfred understands context.</p>
        </div>
        <div class="al-step">
            <div class="al-step-num">2</div>
            <div class="al-step-icon"><i class="fas fa-brain"></i></div>
            <h3>Alfred Thinks</h3>
            <p>Multiple AI engines collaborate — GPT, Claude, Gemini, and more — choosing the best model for your task. Fleet orchestration handles complexity.</p>
        </div>
        <div class="al-step">
            <div class="al-step-num">3</div>
            <div class="al-step-icon"><i class="fas fa-check-circle"></i></div>
            <h3>Get Results</h3>
            <p>Professional output in seconds: documents, analysis, code, designs, schedules. Export, share, or iterate — Alfred remembers your preferences.</p>
        </div>
    </div>
</section>

<!-- ===== FEATURE SHOWCASE ===== -->
<section class="al-features">

    <!-- Feature 1: Voice-First AI -->
    <div class="al-feature al-reveal">
        <div class="al-feature-visual">
            <i class="fas fa-microphone-alt"></i>
        </div>
        <div class="al-feature-text">
            <div class="al-feature-badge"><i class="fas fa-volume-up"></i> Voice-First</div>
            <h3>Natural Conversation, Real Results</h3>
            <p>Say "Hey Alfred" and speak naturally. No menus, no learning curve. Alfred understands context, follow-ups, and complex multi-step requests — just like talking to a brilliant colleague.</p>
            <ul class="al-feature-list">
                <li><i class="fas fa-check"></i> Natural language understanding</li>
                <li><i class="fas fa-check"></i> Multi-turn conversations with memory</li>
                <li><i class="fas fa-check"></i> Works in English, French, Spanish, and 40+ languages</li>
                <li><i class="fas fa-check"></i> Real-time voice transcription</li>
            </ul>
        </div>
    </div>

    <!-- Feature 2: Fleet Orchestration -->
    <div class="al-feature al-reveal">
        <div class="al-feature-visual">
            <i class="fas fa-network-wired"></i>
        </div>
        <div class="al-feature-text">
            <div class="al-feature-badge"><i class="fas fa-sitemap"></i> Fleet Mode</div>
            <h3>35 AI Agents Working in Concert</h3>
            <p>Complex tasks get broken into sub-tasks and distributed across specialized agents. A legal agent drafts, a research agent fact-checks, a formatting agent polishes — all simultaneously.</p>
            <ul class="al-feature-list">
                <li><i class="fas fa-check"></i> Parallel task execution</li>
                <li><i class="fas fa-check"></i> Specialized domain agents</li>
                <li><i class="fas fa-check"></i> Auto-coordination and conflict resolution</li>
                <li><i class="fas fa-check"></i> Real-time progress dashboard</li>
            </ul>
        </div>
    </div>

    <!-- Feature 3: Consciousness Layer -->
    <div class="al-feature al-reveal">
        <div class="al-feature-visual">
            <i class="fas fa-lightbulb"></i>
        </div>
        <div class="al-feature-text">
            <div class="al-feature-badge"><i class="fas fa-atom"></i> Consciousness</div>
            <h3>A Personality That Learns &amp; Grows</h3>
            <p>Alfred isn't a cold tool — it develops a personality tuned to your style. It remembers your preferences, anticipates needs, and proactively suggests improvements before you ask.</p>
            <ul class="al-feature-list">
                <li><i class="fas fa-check"></i> Persistent memory across sessions</li>
                <li><i class="fas fa-check"></i> Mood &amp; tone awareness</li>
                <li><i class="fas fa-check"></i> Proactive suggestions</li>
                <li><i class="fas fa-check"></i> Personality customization</li>
            </ul>
        </div>
    </div>

    <!-- Feature 4: 22 Industry Tools -->
    <div class="al-feature al-reveal">
        <div class="al-feature-visual">
            <i class="fas fa-th-large"></i>
        </div>
        <div class="al-feature-text">
            <div class="al-feature-badge"><i class="fas fa-briefcase"></i> Industry Tools</div>
            <h3>27 Industry Verticals, Every Profession</h3>
            <p>From legal contract drafting to medical SOAP notes, from restaurant scheduling to logistics routing — Alfred has purpose-built tools for every industry.</p>
            <ul class="al-feature-list">
                <li><i class="fas fa-check"></i> Legal, Healthcare, Dental, Insurance, Real Estate</li>
                <li><i class="fas fa-check"></i> E-Commerce, Restaurants, Accounting, Logistics</li>
                <li><i class="fas fa-check"></i> Education, Students, Creators, Developers, Nonprofits</li>
                <li><i class="fas fa-check"></i> Web Hosting — 500+ tools (our core product)</li>
            </ul>
        </div>
    </div>

</section>

<!-- ===== PRICING ===== -->
<section class="al-pricing" id="pricing">
    <div class="al-pricing-inner">
        <h2 class="al-section-title al-reveal">Simple, Transparent Pricing</h2>
        <p class="al-section-sub al-reveal">Start free. Upgrade when you're ready. Cancel anytime.</p>

        <div class="al-pricing-toggle al-reveal">
            <span class="active" id="monthlyLabel">Monthly</span>
            <div class="al-toggle-switch" id="pricingToggle" onclick="togglePricing()"></div>
            <span id="annualLabel">Annual</span>
            <span class="al-save-badge">Save 20%</span>
        </div>

        <div class="al-pricing-grid al-reveal">
            <!-- Starter -->
            <div class="al-price-card">
                <div class="al-price-card-name">Starter</div>
                <div class="al-price-card-desc">Perfect for individuals getting started</div>
                <div class="al-price-amount">
                    <span class="currency">$</span><span class="amount" data-monthly="3.99" data-annual="3.19">3.99</span><span class="period">/mo</span>
                </div>
                <div class="al-price-original" data-original="$3.99/mo">$3.99/mo</div>
                <ul class="al-price-features">
                    <li><i class="fas fa-check"></i> 50 AI tools</li>
                    <li><i class="fas fa-check"></i> 100 queries/day</li>
                    <li><i class="fas fa-check"></i> Voice access</li>
                    <li><i class="fas fa-check"></i> Chat support</li>
                    <li><i class="fas fa-check"></i> 5 GB storage</li>
                </ul>
                <button onclick="startCheckout('starter')" class="al-price-btn al-price-btn-secondary">Get Started</button>
            </div>

            <!-- Professional (Featured) -->
            <div class="al-price-card featured">
                <div class="al-popular-badge"><i class="fas fa-fire"></i> Most Popular</div>
                <div class="al-price-card-name">Professional</div>
                <div class="al-price-card-desc">For power users and growing teams</div>
                <div class="al-price-amount">
                    <span class="currency">$</span><span class="amount" data-monthly="9.99" data-annual="7.99">9.99</span><span class="period">/mo</span>
                </div>
                <div class="al-price-original" data-original="$9.99/mo">$9.99/mo</div>
                <ul class="al-price-features">
                    <li><i class="fas fa-check"></i> All 13,000+ tools</li>
                    <li><i class="fas fa-check"></i> Unlimited queries</li>
                    <li><i class="fas fa-check"></i> Fleet mode (35 agents)</li>
                    <li><i class="fas fa-check"></i> Marketplace access</li>
                    <li><i class="fas fa-check"></i> Priority support</li>
                    <li><i class="fas fa-check"></i> 50 GB storage</li>
                    <li><i class="fas fa-check"></i> Custom workflows</li>
                </ul>
                <button onclick="startCheckout('professional')" class="al-price-btn al-price-btn-primary">Start Free Trial</button>
            </div>

            <!-- Enterprise -->
            <div class="al-price-card">
                <div class="al-price-card-name">Enterprise</div>
                <div class="al-price-card-desc">For organizations needing full power</div>
                <div class="al-price-amount">
                    <span class="currency">$</span><span class="amount" data-monthly="24.99" data-annual="19.99">24.99</span><span class="period">/mo</span>
                </div>
                <div class="al-price-original" data-original="$24.99/mo">$24.99/mo</div>
                <ul class="al-price-features">
                    <li><i class="fas fa-check"></i> Everything in Professional</li>
                    <li><i class="fas fa-check"></i> Priority processing</li>
                    <li><i class="fas fa-check"></i> White-label option</li>
                    <li><i class="fas fa-check"></i> Full API access</li>
                    <li><i class="fas fa-check"></i> Dedicated support manager</li>
                    <li><i class="fas fa-check"></i> Unlimited storage</li>
                    <li><i class="fas fa-check"></i> SSO &amp; team management</li>
                    <li><i class="fas fa-check"></i> SLA guarantee</li>
                </ul>
                <button onclick="startCheckout('enterprise')" class="al-price-btn al-price-btn-secondary">Contact Sales</button>
            </div>
        </div>
    </div>
</section>

<!-- ===== DEMOGRAPHICS ===== -->
<section class="al-demographics al-reveal">
    <h2 class="al-section-title">Alfred Works For Everyone</h2>
    <p class="al-section-sub">No matter your profession, Alfred has tools built for you.</p>

    <div class="al-demo-grid">
        <a href="/alfred-tools.php" class="al-demo-card">
            <i class="fas fa-graduation-cap" style="color: var(--al-blue);"></i>
            <span>Students</span>
        </a>
        <a href="/alfred-tools.php" class="al-demo-card">
            <i class="fas fa-gavel" style="color: var(--al-accent-light);"></i>
            <span>Lawyers</span>
        </a>
        <a href="/alfred-tools.php" class="al-demo-card">
            <i class="fas fa-stethoscope" style="color: var(--al-green);"></i>
            <span>Doctors</span>
        </a>
        <a href="/alfred-tools.php" class="al-demo-card">
            <i class="fas fa-chalkboard-teacher" style="color: var(--al-orange);"></i>
            <span>Teachers</span>
        </a>
        <a href="/alfred-tools.php" class="al-demo-card">
            <i class="fas fa-store" style="color: var(--al-pink);"></i>
            <span>Business Owners</span>
        </a>
        <a href="/alfred-tools.php" class="al-demo-card">
            <i class="fas fa-palette" style="color: var(--al-fire);"></i>
            <span>Creators</span>
        </a>
        <a href="/alfred-tools.php" class="al-demo-card">
            <i class="fas fa-code" style="color: var(--al-cyan);"></i>
            <span>Developers</span>
        </a>
        <a href="/alfred-tools.php" class="al-demo-card">
            <i class="fas fa-chart-pie" style="color: var(--al-accent);"></i>
            <span>Marketers</span>
        </a>
        <a href="/alfred-tools.php" class="al-demo-card">
            <i class="fas fa-calculator" style="color: var(--al-blue);"></i>
            <span>Accountants</span>
        </a>
        <a href="/alfred-tools.php" class="al-demo-card">
            <i class="fas fa-pen-fancy" style="color: var(--al-accent-light);"></i>
            <span>Writers</span>
        </a>
        <a href="/alfred-tools.php" class="al-demo-card">
            <i class="fas fa-flask" style="color: var(--al-green);"></i>
            <span>Researchers</span>
        </a>
        <a href="/alfred-tools.php" class="al-demo-card">
            <i class="fas fa-users" style="color: var(--al-orange);"></i>
            <span>HR Teams</span>
        </a>
    </div>
</section>

<!-- ===== FAQ ===== -->
<section class="al-faq al-reveal">
    <h2 class="al-section-title">Frequently Asked Questions</h2>
    <p class="al-section-sub">Everything you need to know about Alfred AI.</p>

    <div class="al-faq-item">
        <button class="al-faq-q" onclick="toggleFaq(this)">What is Alfred AI? <i class="fas fa-chevron-down"></i></button>
        <div class="al-faq-a"><p>Alfred is an AI assistant with 13,000+ specialized tools across 22 categories. It works via voice, text, or API — handling everything from drafting legal documents to analyzing datasets, scheduling teams, and building marketing campaigns.</p></div>
    </div>
    <div class="al-faq-item">
        <button class="al-faq-q" onclick="toggleFaq(this)">Is there a free trial? <i class="fas fa-chevron-down"></i></button>
        <div class="al-faq-a"><p>Yes! Every plan includes a free trial period so you can experience Alfred's full capabilities before committing. No credit card required to start.</p></div>
    </div>
    <div class="al-faq-item">
        <button class="al-faq-q" onclick="toggleFaq(this)">How does voice control work? <i class="fas fa-chevron-down"></i></button>
        <div class="al-faq-a"><p>Simply say "Hey Alfred" followed by your request. Alfred uses advanced speech recognition and natural language understanding to interpret your commands, ask clarifying questions if needed, and deliver results — all through natural conversation.</p></div>
    </div>
    <div class="al-faq-item">
        <button class="al-faq-q" onclick="toggleFaq(this)">What AI models does Alfred use? <i class="fas fa-chevron-down"></i></button>
        <div class="al-faq-a"><p>Alfred uses multiple AI engines including GPT-4, Claude, Gemini, Llama, and specialized fine-tuned models. It automatically selects the best model for each task, and can use multiple models simultaneously for complex requests via fleet orchestration.</p></div>
    </div>
    <div class="al-faq-item">
        <button class="al-faq-q" onclick="toggleFaq(this)">Can I cancel anytime? <i class="fas fa-chevron-down"></i></button>
        <div class="al-faq-a"><p>Absolutely. There are no long-term contracts. You can upgrade, downgrade, or cancel your subscription at any time from your dashboard. If you cancel, you'll retain access until the end of your billing period.</p></div>
    </div>
    <div class="al-faq-item">
        <button class="al-faq-q" onclick="toggleFaq(this)">Is my data secure? <i class="fas fa-chevron-down"></i></button>
        <div class="al-faq-a"><p>Yes. All data is encrypted at rest and in transit (AES-256 + TLS 1.3). We never train on your data. Enterprise plans include SOC 2 compliance, SSO, and data residency options. Your information stays yours.</p></div>
    </div>
    <div class="al-faq-item">
        <button class="al-faq-q" onclick="toggleFaq(this)">What's the Marketplace? <i class="fas fa-chevron-down"></i></button>
        <div class="al-faq-a"><p>The Alfred Marketplace lets you buy, sell, and share custom AI tools, templates, and workflows created by the community. Professional and Enterprise plan subscribers get full marketplace access, and anyone can become a seller.</p></div>
    </div>
    <div class="al-faq-item">
        <button class="al-faq-q" onclick="toggleFaq(this)">Does Alfred work on mobile? <i class="fas fa-chevron-down"></i></button>
        <div class="al-faq-a"><p>Yes. Alfred is fully responsive and works on any device — desktop, tablet, or mobile. Voice commands work especially well on mobile, letting you get things done hands-free from anywhere.</p></div>
    </div>
</section>

<!-- Quick Comparison -->
<section style="padding:3rem 1.5rem;background:rgba(108,92,231,0.03);">
    <div style="max-width:800px;margin:0 auto;text-align:center;">
        <h2 style="font-family:'Space Grotesk',sans-serif;margin-bottom:2rem;">Why Alfred Wins</h2>
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:0;border:1px solid rgba(108,92,231,0.2);border-radius:12px;overflow:hidden;font-size:0.85rem;">
            <div style="padding:1rem;background:rgba(108,92,231,0.1);font-weight:700;border-bottom:1px solid rgba(108,92,231,0.2);"></div>
            <div style="padding:1rem;background:rgba(108,92,231,0.1);font-weight:700;border-bottom:1px solid rgba(108,92,231,0.2);">ChatGPT</div>
            <div style="padding:1rem;background:rgba(108,92,231,0.1);font-weight:700;border-bottom:1px solid rgba(108,92,231,0.2);">Cursor</div>
            <div style="padding:1rem;background:rgba(108,92,231,0.15);font-weight:700;border-bottom:1px solid rgba(108,92,231,0.2);color:#a29bfe;">Alfred</div>
            
            <div style="padding:0.75rem;border-bottom:1px solid rgba(255,255,255,0.05);text-align:left;">Tools</div>
            <div style="padding:0.75rem;border-bottom:1px solid rgba(255,255,255,0.05);">~50</div>
            <div style="padding:0.75rem;border-bottom:1px solid rgba(255,255,255,0.05);">~50</div>
            <div style="padding:0.75rem;border-bottom:1px solid rgba(255,255,255,0.05);color:#00b894;font-weight:700;">13,000+</div>
            
            <div style="padding:0.75rem;border-bottom:1px solid rgba(255,255,255,0.05);text-align:left;">Voice Control</div>
            <div style="padding:0.75rem;border-bottom:1px solid rgba(255,255,255,0.05);opacity:0.4;">No</div>
            <div style="padding:0.75rem;border-bottom:1px solid rgba(255,255,255,0.05);opacity:0.4;">No</div>
            <div style="padding:0.75rem;border-bottom:1px solid rgba(255,255,255,0.05);color:#00b894;">✓ Phone</div>
            
            <div style="padding:0.75rem;border-bottom:1px solid rgba(255,255,255,0.05);text-align:left;">Fleet Mgmt</div>
            <div style="padding:0.75rem;border-bottom:1px solid rgba(255,255,255,0.05);opacity:0.4;">No</div>
            <div style="padding:0.75rem;border-bottom:1px solid rgba(255,255,255,0.05);opacity:0.4;">No</div>
            <div style="padding:0.75rem;border-bottom:1px solid rgba(255,255,255,0.05);color:#00b894;">✓ Yes</div>
            
            <div style="padding:0.75rem;text-align:left;">Price/mo</div>
            <div style="padding:0.75rem;">$20</div>
            <div style="padding:0.75rem;">$20</div>
            <div style="padding:0.75rem;color:#00b894;font-weight:700;">$3.99</div>
        </div>
        <a href="/compare.php" style="display:inline-block;margin-top:1.5rem;color:#a29bfe;font-size:0.9rem;">See full comparison &rarr;</a>
    </div>
</section>

<!-- ===== FINAL CTA ===== -->
<section class="al-final-cta">
    <h2>Ready to Transform Your Workflow?</h2>
    <p>Join thousands of professionals who let Alfred handle the heavy lifting.</p>
    <form class="al-email-form" onsubmit="handleSignup(event)">
        <input type="email" placeholder="Enter your email address" required>
        <button type="submit"><i class="fas fa-arrow-right"></i> Get Started Free</button>
    </form>
</section>

</main>

<script src="/assets/js/alfred-landing-engine.js"></script>

<!-- Explore More Interlinks -->
<section style="padding:3rem 1.5rem;text-align:center;">
    <div style="max-width:900px;margin:0 auto;">
        <h3 style="color:#a29bfe;margin-bottom:1.5rem;">Explore More</h3>
        <div style="display:flex;flex-wrap:wrap;gap:1rem;justify-content:center;">
            <a href="/tools/" class="btn btn-outline">Browse 13,000+ Tools</a>
            <a href="/pricing.php" class="btn btn-outline">View Pricing</a>
            <a href="/compare.php" class="btn btn-outline">Compare Alfred</a>
            <a href="/docs/" class="btn btn-outline">Documentation</a>
            <a href="/use-cases/" class="btn btn-outline">Use Cases</a>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
