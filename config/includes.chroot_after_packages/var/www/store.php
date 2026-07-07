<?php
$page_title = 'GoSiteMe Store — Apps, Games, AI Agents & Extensions';
$page_description = 'Discover and install apps, VR games, AI agents, extensions, and services. The alternative app store for AI-powered tools and immersive experiences.';
$page_canonical = 'https://root.com/store.php';
$page_og_title = 'GoSiteMe Store';
$page_og_description = 'Apps, Games, AI Agents & Extensions — The AI-native app store.';
$page_og_image = 'https://root.com/assets/images/og-store.png';
$page_og_image_alt = 'GoSiteMe Store';
$page_twitter_description = 'Discover apps, VR games, AI agents & extensions — The AI-native app store.';

include __DIR__ . '/includes/site-header.inc.php';

if (session_status() === PHP_SESSION_NONE) session_start();
$is_logged_in = !empty($_SESSION['logged_in']) && !empty($_SESSION['client_id']);
$client_id = $is_logged_in ? (int)$_SESSION['client_id'] : 0;
?>

<style>
/* ═══════════════════════════════════════════════════════════════
   GoSiteMe Store — Google Play-class App Store
   ═══════════════════════════════════════════════════════════════ */
:root {
    --st-bg: #0a0a14;
    --st-surface: #12121e;
    --st-surface-2: #1a1a2e;
    --st-surface-3: #22223a;
    --st-border: rgba(255,255,255,0.08);
    --st-accent: #6c5ce7;
    --st-accent-light: #a29bfe;
    --st-green: #00b894;
    --st-blue: #0984e3;
    --st-orange: #fdcb6e;
    --st-fire: #e17055;
    --st-pink: #fd79a8;
    --st-text: #e8e8f0;
    --st-text-muted: #8a8a9a;
    --st-text-dim: #5a5a6a;
    --st-radius: 16px;
    --st-radius-sm: 10px;
    --st-shadow: 0 4px 24px rgba(0,0,0,0.4);
    --st-shadow-hover: 0 8px 32px rgba(108,92,231,0.2);
    --st-transition: 0.25s cubic-bezier(0.4,0,0.2,1);
}

* { box-sizing: border-box; }

.store-page {
    background: var(--st-bg);
    min-height: 100vh;
    font-family: 'Inter', 'Space Grotesk', -apple-system, sans-serif;
    color: var(--st-text);
}

/* ── TOP NAVIGATION BAR ── */
.store-topbar {
    position: sticky;
    top: 0;
    z-index: 100;
    background: rgba(10,10,20,0.85);
    backdrop-filter: blur(20px);
    border-bottom: 1px solid var(--st-border);
    padding: 0 24px;
}
.store-topbar-inner {
    max-width: 1400px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    height: 64px;
    gap: 16px;
}
.store-logo {
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    font-family: 'Space Grotesk', sans-serif;
    font-weight: 800;
    font-size: 1.3rem;
    color: #fff;
    flex-shrink: 0;
}
.store-logo-icon {
    width: 36px;
    height: 36px;
    background: linear-gradient(135deg, var(--st-accent), var(--st-green));
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
}

/* Tab nav (like Google Play: Games | Apps | AI Agents | Extensions) */
.store-tabs {
    display: flex;
    gap: 4px;
    margin-left: 32px;
}
.store-tab {
    padding: 8px 18px;
    border-radius: 24px;
    font-size: 0.92rem;
    font-weight: 600;
    color: var(--st-text-muted);
    cursor: pointer;
    transition: var(--st-transition);
    border: none;
    background: transparent;
    white-space: nowrap;
}
.store-tab:hover {
    color: #fff;
    background: var(--st-surface-2);
}
.store-tab.active {
    color: #fff;
    background: var(--st-accent);
}

/* Search */
.store-search {
    margin-left: auto;
    position: relative;
    flex: 0 1 380px;
}
.store-search input {
    width: 100%;
    padding: 10px 16px 10px 42px;
    border-radius: 24px;
    border: 1px solid var(--st-border);
    background: var(--st-surface);
    color: #fff;
    font-size: 0.95rem;
    outline: none;
    transition: var(--st-transition);
}
.store-search input:focus {
    border-color: var(--st-accent);
    box-shadow: 0 0 0 3px rgba(108,92,231,0.15);
}
.store-search input::placeholder { color: var(--st-text-dim); }
.store-search svg {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    width: 18px;
    height: 18px;
    stroke: var(--st-text-dim);
}

.store-user-actions {
    display: flex;
    gap: 8px;
    align-items: center;
    margin-left: 12px;
}
.store-user-actions .btn-myapps {
    padding: 8px 16px;
    border-radius: 24px;
    background: var(--st-surface-2);
    color: var(--st-text);
    font-size: 0.85rem;
    font-weight: 600;
    border: 1px solid var(--st-border);
    cursor: pointer;
    transition: var(--st-transition);
}
.store-user-actions .btn-myapps:hover {
    background: var(--st-accent);
    color: #fff;
    border-color: var(--st-accent);
}

/* ── MAIN CONTENT ── */
.store-content {
    max-width: 1400px;
    margin: 0 auto;
    padding: 24px 24px 80px;
}

/* ── FEATURED BANNER CAROUSEL ── */
.featured-carousel {
    position: relative;
    border-radius: var(--st-radius);
    overflow: hidden;
    margin-bottom: 40px;
    box-shadow: var(--st-shadow);
}
.featured-slides {
    display: flex;
    transition: transform 0.6s cubic-bezier(0.4,0,0.2,1);
    will-change: transform;
}
.featured-slide {
    min-width: 100%;
    position: relative;
    height: 320px;
    display: flex;
    align-items: center;
    padding: 40px 48px;
    background: linear-gradient(135deg, #1a1033, #0d1b3e);
    cursor: pointer;
}
.featured-slide:nth-child(2) { background: linear-gradient(135deg, #0d2818, #0d1b3e); }
.featured-slide:nth-child(3) { background: linear-gradient(135deg, #2d1515, #1a1a2e); }
.featured-slide:nth-child(4) { background: linear-gradient(135deg, #1a2833, #0a0a14); }
.featured-slide:nth-child(5) { background: linear-gradient(135deg, #1a1a0a, #0a0a14); }

.featured-info { flex: 1; z-index: 1; }
.featured-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    background: rgba(108,92,231,0.2);
    color: var(--st-accent-light);
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 12px;
}
.featured-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: clamp(1.6rem, 3vw, 2.4rem);
    font-weight: 800;
    margin: 0 0 10px;
    color: #fff;
}
.featured-desc {
    font-size: 1rem;
    color: var(--st-text-muted);
    margin: 0 0 20px;
    max-width: 500px;
    line-height: 1.6;
}
.featured-meta {
    display: flex;
    gap: 16px;
    align-items: center;
    font-size: 0.85rem;
}
.featured-stars { color: var(--st-orange); }
.featured-installs { color: var(--st-text-muted); }
.featured-price {
    padding: 4px 14px;
    border-radius: 20px;
    background: var(--st-green);
    color: #fff;
    font-weight: 700;
    font-size: 0.85rem;
}
.featured-icon {
    position: absolute;
    right: 48px;
    width: 160px;
    height: 160px;
    border-radius: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 5rem;
    background: rgba(255,255,255,0.05);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.08);
}

.carousel-dots {
    position: absolute;
    bottom: 16px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 8px;
    z-index: 2;
}
.carousel-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: rgba(255,255,255,0.3);
    cursor: pointer;
    transition: var(--st-transition);
}
.carousel-dot.active {
    background: var(--st-accent-light);
    width: 24px;
    border-radius: 4px;
}

/* Carousel arrows */
.carousel-arrow {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: rgba(0,0,0,0.5);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.1);
    color: #fff;
    font-size: 1.2rem;
    cursor: pointer;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--st-transition);
}
.carousel-arrow:hover { background: var(--st-accent); }
.carousel-arrow.prev { left: 16px; }
.carousel-arrow.next { right: 16px; }

/* ── SECTION HEADERS ── */
.store-section {
    margin-bottom: 40px;
}
.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}
.section-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.4rem;
    font-weight: 700;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 10px;
}
.section-icon { font-size: 1.3rem; }
.section-link {
    color: var(--st-accent-light);
    font-size: 0.9rem;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: var(--st-transition);
}
.section-link:hover { color: #fff; }

/* ── APP CARD GRID ── */
.app-row {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 16px;
}
.app-row.compact {
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
}

/* ── APP CARD ── */
.app-card {
    background: var(--st-surface);
    border: 1px solid var(--st-border);
    border-radius: var(--st-radius);
    padding: 16px;
    cursor: pointer;
    transition: var(--st-transition);
    text-decoration: none;
    color: inherit;
    display: flex;
    flex-direction: column;
}
.app-card:hover {
    border-color: var(--st-accent);
    transform: translateY(-3px);
    box-shadow: var(--st-shadow-hover);
}
.app-card-icon {
    width: 64px;
    height: 64px;
    border-radius: 16px;
    background: var(--st-surface-3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin-bottom: 12px;
    flex-shrink: 0;
}
.app-card-icon img {
    width: 100%;
    height: 100%;
    border-radius: 16px;
    object-fit: cover;
}
.app-card-title {
    font-weight: 700;
    font-size: 0.95rem;
    margin: 0 0 4px;
    color: #fff;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.app-card-dev {
    font-size: 0.8rem;
    color: var(--st-text-muted);
    margin: 0 0 8px;
}
.app-card-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.8rem;
    margin-top: auto;
}
.app-card-rating {
    display: flex;
    align-items: center;
    gap: 3px;
    color: var(--st-text-muted);
}
.app-card-rating .star { color: var(--st-orange); font-size: 0.75rem; }
.app-card-price {
    margin-left: auto;
    padding: 3px 10px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 0.78rem;
}
.app-card-price.free {
    background: rgba(0,184,148,0.15);
    color: var(--st-green);
}
.app-card-price.paid {
    background: rgba(108,92,231,0.15);
    color: var(--st-accent-light);
}
.app-card-type {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 2px 8px;
    border-radius: 6px;
    background: var(--st-surface-3);
    color: var(--st-text-dim);
    margin-bottom: 8px;
    display: inline-block;
    align-self: flex-start;
}

/* ── LIST-STYLE CARD (Google Play top charts style) ── */
.app-list-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 12px 16px;
    background: var(--st-surface);
    border: 1px solid var(--st-border);
    border-radius: var(--st-radius-sm);
    cursor: pointer;
    transition: var(--st-transition);
    text-decoration: none;
    color: inherit;
}
.app-list-card:hover {
    border-color: var(--st-accent);
    background: var(--st-surface-2);
}
.app-list-rank {
    font-size: 1.1rem;
    font-weight: 800;
    color: var(--st-text-dim);
    width: 28px;
    text-align: center;
    flex-shrink: 0;
}
.app-list-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background: var(--st-surface-3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.6rem;
    flex-shrink: 0;
}
.app-list-info { flex: 1; min-width: 0; }
.app-list-title {
    font-weight: 700;
    font-size: 0.9rem;
    color: #fff;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.app-list-subtitle {
    font-size: 0.8rem;
    color: var(--st-text-muted);
    display: flex;
    gap: 8px;
    align-items: center;
}

/* ── CATEGORY CHIPS ── */
.category-chips {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 24px;
}
.category-chip {
    padding: 8px 18px;
    border-radius: 24px;
    background: var(--st-surface);
    border: 1px solid var(--st-border);
    color: var(--st-text-muted);
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: var(--st-transition);
    white-space: nowrap;
}
.category-chip:hover, .category-chip.active {
    background: var(--st-accent);
    color: #fff;
    border-color: var(--st-accent);
}

/* ── COLLECTIONS ── */
.collections-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 16px;
}
.collection-card {
    background: var(--st-surface);
    border: 1px solid var(--st-border);
    border-radius: var(--st-radius);
    padding: 24px;
    cursor: pointer;
    transition: var(--st-transition);
    text-decoration: none;
    color: inherit;
}
.collection-card:hover {
    border-color: var(--st-accent);
    transform: translateY(-2px);
    box-shadow: var(--st-shadow-hover);
}
.collection-icon { font-size: 2.2rem; margin-bottom: 12px; }
.collection-title {
    font-weight: 700;
    font-size: 1.05rem;
    color: #fff;
    margin: 0 0 6px;
}
.collection-desc {
    font-size: 0.85rem;
    color: var(--st-text-muted);
    line-height: 1.5;
}

/* ── APP DETAIL MODAL ── */
.app-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.7);
    backdrop-filter: blur(8px);
    z-index: 1000;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 24px;
}
.app-modal-overlay.active { display: flex; }
.app-modal {
    background: var(--st-surface);
    border-radius: var(--st-radius);
    max-width: 800px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 16px 64px rgba(0,0,0,0.5);
    border: 1px solid var(--st-border);
}
.app-modal-header {
    display: flex;
    gap: 20px;
    padding: 32px 32px 0;
    align-items: flex-start;
}
.app-modal-icon {
    width: 96px;
    height: 96px;
    border-radius: 24px;
    background: var(--st-surface-3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3.2rem;
    flex-shrink: 0;
}
.app-modal-title-wrap { flex: 1; }
.app-modal-title {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.6rem;
    font-weight: 800;
    color: #fff;
    margin: 0 0 4px;
}
.app-modal-dev {
    color: var(--st-accent-light);
    font-size: 0.9rem;
    font-weight: 600;
    margin: 0 0 8px;
}
.app-modal-badges {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.app-modal-badge {
    padding: 3px 10px;
    border-radius: 8px;
    background: var(--st-surface-3);
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--st-text-muted);
    text-transform: uppercase;
}
.app-modal-close {
    position: absolute;
    top: 16px;
    right: 16px;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--st-surface-3);
    border: none;
    color: var(--st-text);
    font-size: 1.2rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}
.app-modal-close:hover { background: var(--st-fire); color: #fff; }

.app-modal-stats {
    display: flex;
    gap: 24px;
    padding: 20px 32px;
    border-bottom: 1px solid var(--st-border);
}
.app-modal-stat {
    text-align: center;
}
.app-modal-stat-value {
    font-size: 1.2rem;
    font-weight: 800;
    color: #fff;
}
.app-modal-stat-label {
    font-size: 0.75rem;
    color: var(--st-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.app-modal-actions {
    display: flex;
    gap: 12px;
    padding: 20px 32px;
    border-bottom: 1px solid var(--st-border);
}
.btn-install {
    padding: 12px 32px;
    border-radius: 24px;
    background: var(--st-accent);
    color: #fff;
    font-weight: 700;
    font-size: 1rem;
    border: none;
    cursor: pointer;
    transition: var(--st-transition);
    flex: 1;
    max-width: 300px;
}
.btn-install:hover { background: #5a4bd4; transform: scale(1.02); }
.btn-install.installed {
    background: var(--st-surface-3);
    color: var(--st-green);
    border: 1px solid var(--st-green);
}
.btn-wishlist {
    padding: 12px 20px;
    border-radius: 24px;
    background: transparent;
    border: 1px solid var(--st-border);
    color: var(--st-text);
    font-weight: 600;
    cursor: pointer;
    transition: var(--st-transition);
}
.btn-wishlist:hover { border-color: var(--st-pink); color: var(--st-pink); }

/* Screenshots carousel */
.app-modal-screenshots {
    padding: 20px 32px;
    overflow-x: auto;
    display: flex;
    gap: 12px;
    border-bottom: 1px solid var(--st-border);
    scrollbar-width: thin;
    scrollbar-color: var(--st-surface-3) transparent;
}
.screenshot-thumb {
    min-width: 200px;
    height: 130px;
    border-radius: var(--st-radius-sm);
    background: var(--st-surface-3);
    flex-shrink: 0;
    overflow: hidden;
}
.screenshot-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.app-modal-body {
    padding: 24px 32px;
}
.app-modal-body h3 {
    font-size: 1.1rem;
    margin: 24px 0 10px;
    color: #fff;
}
.app-modal-body h3:first-child { margin-top: 0; }
.app-modal-body p {
    font-size: 0.92rem;
    line-height: 1.7;
    color: var(--st-text-muted);
}

/* Rating breakdown */
.rating-breakdown {
    display: flex;
    gap: 20px;
    align-items: center;
}
.rating-big {
    text-align: center;
    min-width: 80px;
}
.rating-big-number {
    font-size: 3rem;
    font-weight: 800;
    color: #fff;
}
.rating-big-stars { color: var(--st-orange); font-size: 0.9rem; }
.rating-big-count {
    font-size: 0.8rem;
    color: var(--st-text-muted);
}
.rating-bars { flex: 1; }
.rating-bar-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 4px;
    font-size: 0.8rem;
    color: var(--st-text-muted);
}
.rating-bar-fill {
    flex: 1;
    height: 8px;
    background: var(--st-surface-3);
    border-radius: 4px;
    overflow: hidden;
}
.rating-bar-fill span {
    display: block;
    height: 100%;
    background: var(--st-orange);
    border-radius: 4px;
    transition: var(--st-transition);
}

/* Review card */
.review-card {
    background: var(--st-surface-2);
    border-radius: var(--st-radius-sm);
    padding: 16px;
    margin-bottom: 12px;
}
.review-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
}
.review-stars { color: var(--st-orange); font-size: 0.85rem; }
.review-date { color: var(--st-text-dim); font-size: 0.8rem; }
.review-body {
    font-size: 0.9rem;
    color: var(--st-text-muted);
    line-height: 1.6;
}
.review-helpful {
    margin-top: 8px;
    font-size: 0.8rem;
    color: var(--st-text-dim);
}

/* ── SEARCH RESULTS ── */
.search-results-panel {
    display: none;
    margin-bottom: 32px;
}
.search-results-panel.active { display: block; }
.search-results-info {
    font-size: 0.9rem;
    color: var(--st-text-muted);
    margin-bottom: 16px;
}

/* ── MY APPS PANEL ── */
.myapps-panel {
    display: none;
    margin-bottom: 32px;
}
.myapps-panel.active { display: block; }

/* ── LOADING SKELETON ── */
.skeleton {
    background: linear-gradient(90deg, var(--st-surface) 25%, var(--st-surface-2) 50%, var(--st-surface) 75%);
    background-size: 200% 100%;
    animation: skeletonPulse 1.5s ease-in-out infinite;
    border-radius: var(--st-radius-sm);
}
@keyframes skeletonPulse {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

/* ── RESPONSIVE ── */
@media (max-width: 900px) {
    .store-tabs { display: none; }
    .store-search { flex: 1; }
    .featured-slide { height: 260px; padding: 24px; }
    .featured-icon { display: none; }
    .app-modal-header { padding: 20px 20px 0; }
    .app-modal-body, .app-modal-actions, .app-modal-stats { padding-left: 20px; padding-right: 20px; }
}
@media (max-width: 600px) {
    .store-topbar-inner { padding: 0 8px; }
    .app-row { grid-template-columns: 1fr 1fr; gap: 10px; }
    .app-card { padding: 12px; }
    .app-card-icon { width: 48px; height: 48px; font-size: 1.5rem; }
    .featured-slide { height: 220px; }
    .featured-title { font-size: 1.2rem; }
    .app-modal { border-radius: 12px; }
}

/* Mobile tab drawer */
.mobile-tab-toggle {
    display: none;
    padding: 8px 14px;
    background: var(--st-surface-2);
    border: 1px solid var(--st-border);
    border-radius: 10px;
    color: var(--st-text);
    font-size: 0.85rem;
    cursor: pointer;
}
@media (max-width: 900px) {
    .mobile-tab-toggle { display: flex; align-items: center; gap: 6px; }
}
</style>

<div class="store-page">

<!-- TOP BAR -->
<div class="store-topbar">
    <div class="store-topbar-inner">
        <a href="/store.php" class="store-logo">
            <div class="store-logo-icon">🏪</div>
            Store
        </a>

        <div class="store-tabs" id="storeTabs">
            <button class="store-tab active" data-type="">For You</button>
            <button class="store-tab" data-type="game">Games</button>
            <button class="store-tab" data-type="app">Apps</button>
            <button class="store-tab" data-type="agent">AI Agents</button>
            <button class="store-tab" data-type="extension">Extensions</button>
            <button class="store-tab" data-type="service">Services</button>
            <button class="store-tab" data-type="tool">Tools</button>
        </div>

        <button class="mobile-tab-toggle" id="mobileTabToggle">☰ Browse</button>

        <div class="store-search">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
            <input type="text" id="storeSearchInput" placeholder="Search apps, games, agents..." autocomplete="off">
        </div>

        <div class="store-user-actions">
            <?php if ($is_logged_in): ?>
                <button class="btn-myapps" id="btnMyApps">📱 My Apps</button>
            <?php else: ?>
                <a href="/login.php" class="btn-myapps">Sign In</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="store-content">

    <!-- SEARCH RESULTS -->
    <div class="search-results-panel" id="searchResultsPanel">
        <div class="section-header">
            <h2 class="section-title"><span class="section-icon">🔍</span> Search Results</h2>
            <button class="section-link" id="clearSearch">✕ Clear</button>
        </div>
        <div class="search-results-info" id="searchInfo"></div>
        <div class="app-row" id="searchResults"></div>
    </div>

    <!-- MY APPS PANEL -->
    <div class="myapps-panel" id="myAppsPanel">
        <div class="section-header">
            <h2 class="section-title"><span class="section-icon">📱</span> My Apps</h2>
            <button class="section-link" id="closeMyApps">✕ Close</button>
        </div>
        <div class="app-row compact" id="myAppsList"></div>
    </div>

    <!-- HOME CONTENT (hidden during search) -->
    <div id="homeContent">

        <!-- FEATURED CAROUSEL -->
        <div class="featured-carousel" id="featuredCarousel">
            <div class="featured-slides" id="featuredSlides"></div>
            <button class="carousel-arrow prev" id="carouselPrev">‹</button>
            <button class="carousel-arrow next" id="carouselNext">›</button>
            <div class="carousel-dots" id="carouselDots"></div>
        </div>

        <!-- CATEGORY CHIPS (shown when browsing a type) -->
        <div class="category-chips" id="categoryChips" style="display:none"></div>

        <!-- EDITOR'S CHOICE -->
        <div class="store-section" id="sectionEditors">
            <div class="section-header">
                <h2 class="section-title"><span class="section-icon">⭐</span> Editor's Choice</h2>
                <span class="section-link" data-browse="editors_choice">See all →</span>
            </div>
            <div class="app-row" id="editorsRow"></div>
        </div>

        <!-- TRENDING -->
        <div class="store-section" id="sectionTrending">
            <div class="section-header">
                <h2 class="section-title"><span class="section-icon">🔥</span> Trending Now</h2>
                <span class="section-link" data-browse="trending">See all →</span>
            </div>
            <div class="app-row" id="trendingRow"></div>
        </div>

        <!-- NEW ARRIVALS -->
        <div class="store-section" id="sectionNew">
            <div class="section-header">
                <h2 class="section-title"><span class="section-icon">✨</span> New Arrivals</h2>
                <span class="section-link" data-browse="new">See all →</span>
            </div>
            <div class="app-row" id="newRow"></div>
        </div>

        <!-- TOP RATED -->
        <div class="store-section" id="sectionTopRated">
            <div class="section-header">
                <h2 class="section-title"><span class="section-icon">🏆</span> Top Rated</h2>
                <span class="section-link" data-browse="top_rated">See all →</span>
            </div>
            <div class="app-row" id="topRatedRow"></div>
        </div>

        <!-- COLLECTIONS -->
        <div class="store-section" id="sectionCollections">
            <div class="section-header">
                <h2 class="section-title"><span class="section-icon">📦</span> Collections</h2>
            </div>
            <div class="collections-grid" id="collectionsGrid"></div>
        </div>

        <!-- BROWSE RESULTS (when filtered by type) -->
        <div class="store-section" id="sectionBrowse" style="display:none">
            <div class="section-header">
                <h2 class="section-title" id="browseTitle"><span class="section-icon">📂</span> All</h2>
                <select id="browseSort" style="background:var(--st-surface-2);color:var(--st-text);border:1px solid var(--st-border);border-radius:8px;padding:6px 12px;font-size:0.85rem;">
                    <option value="popular">Most Popular</option>
                    <option value="rating">Top Rated</option>
                    <option value="newest">Newest</option>
                    <option value="name">Alphabetical</option>
                    <option value="price_low">Price: Low to High</option>
                    <option value="price_high">Price: High to Low</option>
                </select>
            </div>
            <div class="app-row" id="browseResults"></div>
            <div id="browsePagination" style="text-align:center;margin-top:20px;"></div>
        </div>

    </div>

</div>

<!-- APP DETAIL MODAL -->
<div class="app-modal-overlay" id="appModal">
    <div class="app-modal" style="position:relative;">
        <button class="app-modal-close" id="appModalClose">✕</button>
        <div id="appModalContent">
            <!-- Filled dynamically -->
        </div>
    </div>
</div>

</div>


<script>window._storeLoggedIn = <?= $is_logged_in ? 'true' : 'false' ?>;</script>
<script src="/assets/js/store-engine.js"></script>


<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
