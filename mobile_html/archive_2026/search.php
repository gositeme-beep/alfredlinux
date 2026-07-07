<?php
$page_title = 'Alfred Search — Sovereign Search Engine | GoSiteMe';
$page_description = 'The world\'s first sovereign search engine. AI-powered, zero tracking, post-quantum encrypted, with voice, deep research, and emergency mesh networking. Search the way you were meant to.';
$page_canonical = 'https://gositeme.com/search';
$page_robots = 'index, follow';
require_once __DIR__ . '/includes/site-header.inc.php';
$initialQuery = htmlspecialchars(strip_tags($_GET['q'] ?? ''), ENT_QUOTES, 'UTF-8');
$initialMode = htmlspecialchars(strip_tags($_GET['mode'] ?? 'web'), ENT_QUOTES, 'UTF-8');
?>
<link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
<link rel="stylesheet" href="/assets/css/fonts.css">

<style>
/* ═══════════════════════════════════════════════════
   ALFRED SEARCH — SOVEREIGN SEARCH ENGINE
   A completely new search paradigm. Not a box + 10 links.
   An AI command surface with spatial awareness.
   ═══════════════════════════════════════════════════ */
:root {
    --as-void: #030308;
    --as-deep: #070712;
    --as-surface: #0c0c1a;
    --as-surface2: #111126;
    --as-glass: rgba(12,12,26,0.85);
    --as-border: rgba(100,140,255,0.08);
    --as-glow: rgba(100,160,255,0.12);
    --as-text: #d8dce8;
    --as-dim: #5a6488;
    --as-mute: #363a50;
    --as-blue: #5b9cf5;
    --as-indigo: #7c5cfc;
    --as-cyan: #22d3ee;
    --as-green: #34d399;
    --as-amber: #fbbf24;
    --as-red: #ef4444;
    --as-grad: linear-gradient(135deg, #5b9cf5 0%, #7c5cfc 50%, #a855f7 100%);
    --as-grad2: linear-gradient(135deg, #22d3ee, #5b9cf5);
    --as-radius: 20px;
}
*, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

/* ── Hide Global Site Nav (search has its own UI) ─── */
.promo-bar,
.phone-topbar,
.navbar,
.mega-backdrop,
.cmd-overlay { display: none !important; }

/* ── Base ─────────────────────────────────────────── */
.as-universe {
    background: var(--as-void);
    min-height: 100vh;
    color: var(--as-text);
    font-family: 'Inter', 'DM Sans', system-ui, sans-serif;
    position: relative;
    overflow-x: hidden;
}
.as-universe a { color: var(--as-blue); text-decoration: none; }
.as-universe a:hover { text-decoration: underline; }

/* ── Ambient Background ───────────────────────────── */
.as-ambient {
    position: fixed;
    inset: 0;
    pointer-events: none;
    z-index: 0;
    overflow: hidden;
}
.as-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(120px);
    opacity: 0.07;
    animation: as-drift 30s ease-in-out infinite alternate;
}
.as-orb-1 { width: 600px; height: 600px; background: #5b9cf5; top: -200px; left: -100px; animation-delay: 0s; }
.as-orb-2 { width: 500px; height: 500px; background: #7c5cfc; bottom: -150px; right: -100px; animation-delay: -10s; }
.as-orb-3 { width: 400px; height: 400px; background: #22d3ee; top: 40%; left: 60%; animation-delay: -20s; }
@keyframes as-drift {
    0% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(30px, -20px) scale(1.1); }
    66% { transform: translate(-20px, 30px) scale(0.95); }
    100% { transform: translate(10px, -10px) scale(1.05); }
}

/* ── Grid Overlay ─────────────────────────────────── */
.as-grid-bg {
    position: fixed;
    inset: 0;
    background-image:
        linear-gradient(rgba(100,140,255,0.02) 1px, transparent 1px),
        linear-gradient(90deg, rgba(100,140,255,0.02) 1px, transparent 1px);
    background-size: 60px 60px;
    pointer-events: none;
    z-index: 0;
}

/* ── Security Bar ─────────────────────────────────── */
.as-security-bar {
    position: relative;
    z-index: 10;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 6px 24px;
    background: rgba(52,211,153,0.04);
    border-bottom: 1px solid rgba(52,211,153,0.08);
    font-size: 11px;
    color: var(--as-dim);
    letter-spacing: 0.5px;
}
.as-sec-left { display: flex; align-items: center; gap: 16px; }
.as-sec-left .as-lock {
    display: flex;
    align-items: center;
    gap: 5px;
    color: var(--as-green);
    font-weight: 700;
}
.as-sec-right { display: flex; align-items: center; gap: 14px; }
.as-sec-badge {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    border-radius: 50px;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.8px;
}
.as-sec-badge.encrypted { background: rgba(52,211,153,0.1); color: var(--as-green); }
.as-sec-badge.sovereign { background: rgba(91,156,245,0.1); color: var(--as-blue); }

/* ── Main Container ───────────────────────────────── */
.as-main {
    position: relative;
    z-index: 5;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 24px;
}

/* ═══ LANDING STATE ═══════════════════════════════════ */
.as-landing {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 88vh;
    text-align: center;
    transition: all 0.5s cubic-bezier(0.25, 0.1, 0.25, 1);
}
.as-landing.collapsed {
    min-height: auto;
    padding: 24px 0 0;
}

/* ── Logo ─────────────────────────────────────────── */
.as-logo {
    margin-bottom: 8px;
    position: relative;
}
.as-logo-text {
    font-size: 72px;
    font-weight: 900;
    letter-spacing: -3px;
    background: var(--as-grad);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    line-height: 1;
    transition: font-size 0.4s;
}
.collapsed .as-logo-text { font-size: 26px; letter-spacing: -1px; }
.as-logo-glow {
    position: absolute;
    width: 200px;
    height: 4px;
    background: var(--as-grad);
    bottom: -4px;
    left: 50%;
    transform: translateX(-50%);
    border-radius: 2px;
    filter: blur(8px);
    opacity: 0.6;
    transition: all 0.4s;
}
.collapsed .as-logo-glow { width: 60px; opacity: 0.3; }

.as-tagline {
    font-size: 18px;
    color: var(--as-dim);
    margin: 16px 0 40px;
    font-weight: 400;
    transition: all 0.3s;
}
.collapsed .as-tagline { display: none; }

/* ═══ SEARCH COMMAND BAR ═════════════════════════════ */
.as-search-zone {
    width: 100%;
    max-width: 760px;
    position: relative;
}
.as-search-orb {
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    border-radius: 32px;
    background: var(--as-grad);
    opacity: 0;
    filter: blur(40px);
    transition: opacity 0.4s;
    z-index: -1;
}
.as-search-zone:focus-within .as-search-orb {
    opacity: 0.08;
}

.as-cmd-bar {
    display: flex;
    align-items: center;
    background: var(--as-glass);
    backdrop-filter: blur(24px) saturate(180%);
    -webkit-backdrop-filter: blur(24px) saturate(180%);
    border: 1px solid var(--as-border);
    border-radius: 28px;
    padding: 6px 10px;
    position: relative;
    transition: border-color 0.3s, box-shadow 0.3s;
}
.as-search-zone:focus-within .as-cmd-bar {
    border-color: rgba(91,156,245,0.25);
    box-shadow: 0 0 0 1px rgba(91,156,245,0.08), 0 8px 48px rgba(91,156,245,0.06);
}

.as-cmd-input {
    flex: 1;
    background: none;
    border: none;
    outline: none;
    color: var(--as-text);
    font-size: 17px;
    padding: 14px 16px;
    font-family: inherit;
    caret-color: var(--as-blue);
}
.as-cmd-input::placeholder { color: var(--as-mute); }

.as-cmd-actions {
    display: flex;
    align-items: center;
    gap: 4px;
}
.as-cmd-btn {
    width: 42px;
    height: 42px;
    border: none;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 16px;
}
.as-btn-voice {
    background: none;
    color: var(--as-dim);
}
.as-btn-voice:hover { color: var(--as-blue); transform: scale(1.1); }
.as-btn-voice.recording {
    color: var(--as-red);
    animation: as-pulse 1s ease-in-out infinite;
}
@keyframes as-pulse {
    0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239,68,68,0.3); }
    50% { transform: scale(1.15); box-shadow: 0 0 0 12px rgba(239,68,68,0); }
}
.as-btn-search {
    background: var(--as-grad);
    color: #fff;
    border-radius: 20px;
    width: 48px;
}
.as-btn-search:hover { transform: scale(1.05); filter: brightness(1.1); }

/* ── Suggestions ──────────────────────────────────── */
.as-suggest {
    position: absolute;
    top: calc(100% + 8px);
    left: 0; right: 0;
    background: var(--as-glass);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    border: 1px solid var(--as-border);
    border-radius: var(--as-radius);
    display: none;
    z-index: 600;
    box-shadow: 0 16px 64px rgba(0,0,0,0.5);
    overflow: hidden;
}
.as-suggest.open { display: block; }
.as-suggest-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    cursor: pointer;
    transition: background 0.15s;
    font-size: 14px;
}
.as-suggest-item:hover { background: rgba(91,156,245,0.06); }
.as-suggest-item i { color: var(--as-dim); width: 18px; text-align: center; font-size: 13px; }

/* ═══ MODE ORBIT ══════════════════════════════════════ */
.as-modes {
    display: flex;
    gap: 6px;
    margin-top: 20px;
    justify-content: center;
    flex-wrap: wrap;
}
.as-mode {
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 8px 18px;
    border-radius: 50px;
    font-size: 13px;
    font-weight: 500;
    color: var(--as-dim);
    background: transparent;
    border: 1px solid transparent;
    cursor: pointer;
    transition: all 0.25s;
    font-family: inherit;
    position: relative;
}
.as-mode:hover {
    color: var(--as-text);
    background: rgba(91,156,245,0.04);
    border-color: var(--as-border);
}
.as-mode.active {
    color: var(--as-blue);
    background: rgba(91,156,245,0.08);
    border-color: rgba(91,156,245,0.2);
}
.as-mode i { font-size: 12px; }
.as-mode .mode-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--as-green);
    display: none;
}
.as-mode.active .mode-dot { display: block; }

/* ═══ TRUST METRICS ═════════════════════════════════ */
.as-trust {
    display: flex;
    gap: 32px;
    margin-top: 36px;
    justify-content: center;
    flex-wrap: wrap;
    opacity: 1;
    transition: all 0.3s;
}
.collapsed .as-trust { display: none; }
.as-trust-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
}
.as-trust-val {
    font-size: 22px;
    font-weight: 800;
    background: var(--as-grad2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.as-trust-lbl {
    font-size: 11px;
    color: var(--as-dim);
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 600;
}

/* ── Ecosystem Links ──────────────────────────────── */
.as-ecosystem {
    display: flex;
    gap: 12px;
    margin-top: 36px;
    flex-wrap: wrap;
    justify-content: center;
    transition: all 0.3s;
}
.collapsed .as-ecosystem { display: none; }
.as-eco-link {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 50px;
    font-size: 12px;
    font-weight: 500;
    color: var(--as-dim);
    border: 1px solid var(--as-border);
    transition: all 0.2s;
}
.as-eco-link:hover {
    color: var(--as-text);
    border-color: rgba(91,156,245,0.2);
    background: rgba(91,156,245,0.04);
    text-decoration: none;
}
.as-eco-link i { font-size: 11px; }

/* ═══ LOADING ═════════════════════════════════════════ */
.as-loading {
    display: none;
    text-align: center;
    padding: 48px 20px;
}
.as-loading.active { display: block; }
.as-wave {
    display: inline-flex;
    gap: 4px;
    align-items: center;
    height: 32px;
}
.as-wave span {
    width: 4px;
    height: 16px;
    background: var(--as-blue);
    border-radius: 2px;
    animation: as-wave 0.9s ease-in-out infinite;
}
.as-wave span:nth-child(2) { animation-delay: 0.1s; }
.as-wave span:nth-child(3) { animation-delay: 0.2s; }
.as-wave span:nth-child(4) { animation-delay: 0.3s; }
.as-wave span:nth-child(5) { animation-delay: 0.4s; }
@keyframes as-wave {
    0%, 100% { height: 8px; opacity: 0.3; }
    50% { height: 28px; opacity: 1; }
}
.as-loading-text {
    margin-top: 12px;
    font-size: 13px;
    color: var(--as-dim);
}

/* ═══ RESULTS ═════════════════════════════════════════ */
.as-results {
    max-width: 860px;
    margin: 0 auto;
    padding: 0 0 80px;
    display: none;
}
.as-results.visible { display: block; }

.as-results-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 0 12px;
    font-size: 12px;
    color: var(--as-dim);
}
.as-results-meta .as-privacy-tag {
    display: flex;
    align-items: center;
    gap: 5px;
    color: var(--as-green);
    font-weight: 600;
}

/* ── Instant Answer ───────────────────────────────── */
.as-instant {
    background: linear-gradient(135deg, rgba(91,156,245,0.06), rgba(124,92,252,0.06));
    border: 1px solid rgba(91,156,245,0.12);
    border-radius: var(--as-radius);
    padding: 24px 28px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
}
.as-instant::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: var(--as-grad);
    opacity: 0.5;
}
.as-instant-header {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: var(--as-blue);
    font-weight: 700;
    margin-bottom: 14px;
}
.as-instant-body {
    font-size: 15px;
    line-height: 1.8;
    color: var(--as-text);
}
.as-instant-body strong { color: #fff; }
.as-instant-body code {
    background: rgba(255,255,255,0.06);
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 13px;
    font-family: 'JetBrains Mono', monospace;
}

/* ── First-Party Cards ────────────────────────────── */
.as-first-party {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 14px;
    margin-bottom: 22px;
}
.as-first-party-card {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 18px;
    border-radius: 20px;
    border: 1px solid rgba(91,156,245,0.14);
    background: linear-gradient(160deg, rgba(91,156,245,0.08), rgba(15,23,42,0.72));
    box-shadow: 0 16px 48px rgba(3,7,18,0.28);
}
.as-first-party-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
}
.as-first-party-eyebrow {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--as-blue);
    font-weight: 700;
}
.as-first-party-icon {
    width: 34px;
    height: 34px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    background: rgba(91,156,245,0.18);
}
.as-first-party-title {
    font-size: 18px;
    font-weight: 700;
    color: #fff;
}
.as-first-party-title:hover {
    color: #9bc4ff;
    text-decoration: none;
}
.as-first-party-snippet {
    font-size: 13px;
    line-height: 1.65;
    color: var(--as-dim);
}
.as-first-party-links {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
}
.as-first-party-link {
    display: block;
    padding: 9px 11px;
    border-radius: 12px;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.06);
    color: var(--as-text);
    font-size: 12px;
    font-weight: 600;
    transition: all 0.2s;
}
.as-first-party-link:hover {
    border-color: rgba(91,156,245,0.22);
    background: rgba(91,156,245,0.08);
    color: #fff;
    text-decoration: none;
}

/* ── Result Card ──────────────────────────────────── */
/* moved to improved section below */
.as-result-source {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: var(--as-dim);
    margin-bottom: 4px;
}
.as-result-source img {
    width: 16px; height: 16px;
    border-radius: 4px;
    background: var(--as-surface);
}
.as-result-title {
    font-size: 17px;
    font-weight: 600;
    color: var(--as-blue);
    display: inline;
    line-height: 1.4;
    transition: color 0.2s;
}
.as-result-title:hover { color: #7cb3ff; text-decoration: underline; }
.as-result-snippet {
    font-size: 14px;
    color: var(--as-dim);
    line-height: 1.7;
    margin-top: 6px;
}
.as-result-snippet mark {
    background: rgba(91,156,245,0.15);
    color: var(--as-text);
    border-radius: 3px;
    padding: 0 3px;
}
.as-result-tags {
    display: flex;
    gap: 8px;
    margin-top: 8px;
    flex-wrap: wrap;
}
.as-tag {
    font-size: 10px;
    padding: 2px 10px;
    border-radius: 50px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border: 1px solid;
}
.as-tag-tool { color: var(--as-green); border-color: rgba(52,211,153,0.2); background: rgba(52,211,153,0.06); }
.as-tag-news { color: var(--as-amber); border-color: rgba(251,191,36,0.2); background: rgba(251,191,36,0.06); }
.as-tag-article { color: var(--as-indigo); border-color: rgba(124,92,252,0.2); background: rgba(124,92,252,0.06); }
.as-tag-sovereign { color: var(--as-cyan); border-color: rgba(34,211,238,0.2); background: rgba(34,211,238,0.06); }

/* ── Deep Research CTA ────────────────────────────── */
.as-deep-cta {
    background: var(--as-surface);
    border: 1px solid var(--as-border);
    border-radius: var(--as-radius);
    padding: 20px 24px;
    margin-top: 24px;
    display: flex;
    align-items: center;
    gap: 16px;
    cursor: pointer;
    transition: all 0.25s;
}
.as-deep-cta:hover {
    border-color: rgba(124,92,252,0.25);
    background: rgba(124,92,252,0.04);
}
.as-deep-cta i { font-size: 24px; color: var(--as-indigo); }
.as-deep-cta h4 { font-size: 14px; color: #fff; margin-bottom: 2px; }
.as-deep-cta p { font-size: 12px; color: var(--as-dim); }

/* ── Pagination (legacy, hidden by default now) ─────── */
.as-pages { display: none; }

/* ═══ FOOTER ══════════════════════════════════════════ */
.as-footer {
    text-align: center;
    padding: 32px 20px;
    font-size: 12px;
    color: var(--as-mute);
    border-top: 1px solid var(--as-border);
}
.as-footer a { color: var(--as-dim); }
.as-footer a:hover { color: var(--as-blue); }
.as-footer-links {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-bottom: 10px;
    flex-wrap: wrap;
}
/* ═══ TIME FILTER BAR ═══════════════════════════════════ */
.as-filters {
    display: none;
    align-items: center;
    gap: 6px;
    padding: 8px 0 4px;
    max-width: 860px;
    margin: 0 auto;
    flex-wrap: wrap;
}
.as-filters.visible { display: flex; }
.as-filter-label {
    font-size: 11px;
    color: var(--as-dim);
    text-transform: uppercase;
    letter-spacing: 0.8px;
    font-weight: 600;
    margin-right: 4px;
}
.as-filter-btn {
    padding: 5px 14px;
    border-radius: 50px;
    font-size: 12px;
    font-weight: 500;
    color: var(--as-dim);
    background: transparent;
    border: 1px solid transparent;
    cursor: pointer;
    transition: all 0.2s;
    font-family: inherit;
}
.as-filter-btn:hover {
    color: var(--as-text);
    background: rgba(91,156,245,0.04);
    border-color: var(--as-border);
}
.as-filter-btn.active {
    color: var(--as-blue);
    background: rgba(91,156,245,0.08);
    border-color: rgba(91,156,245,0.2);
}

/* ═══ SKELETON LOADING CARDS ═════════════════════════════ */
.as-skeleton {
    padding: 18px 20px;
    margin-bottom: 8px;
    border-radius: 16px;
}
.as-skel-line {
    height: 13px;
    background: linear-gradient(90deg, var(--as-surface2) 25%, rgba(91,156,245,0.06) 50%, var(--as-surface2) 75%);
    background-size: 400% 100%;
    border-radius: 6px;
    margin-bottom: 10px;
    animation: as-shimmer 1.8s ease-in-out infinite;
}
.as-skel-line.title { width: 70%; height: 17px; margin-bottom: 8px; }
.as-skel-line.source { width: 30%; height: 11px; margin-bottom: 10px; }
.as-skel-line.text1 { width: 95%; }
.as-skel-line.text2 { width: 80%; }
.as-skel-line.text3 { width: 40%; }
@keyframes as-shimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

/* ═══ INFINITE SCROLL LOADING ════════════════════════════ */
.as-load-more-zone {
    text-align: center;
    padding: 20px 0 40px;
    display: none;
}
.as-load-more-zone.visible { display: block; }
.as-load-more-btn {
    padding: 12px 32px;
    border-radius: 50px;
    font-size: 14px;
    font-weight: 600;
    color: var(--as-text);
    background: var(--as-surface2);
    border: 1px solid var(--as-border);
    cursor: pointer;
    transition: all 0.25s;
    font-family: inherit;
}
.as-load-more-btn:hover {
    border-color: var(--as-blue);
    background: rgba(91,156,245,0.06);
}
.as-scroll-spinner {
    display: none;
    justify-content: center;
    align-items: center;
    gap: 6px;
    padding: 16px;
}
.as-scroll-spinner.active { display: flex; }
.as-scroll-spinner .dot {
    width: 8px; height: 8px;
    background: var(--as-blue);
    border-radius: 50%;
    animation: as-bounce 1.2s ease-in-out infinite;
}
.as-scroll-spinner .dot:nth-child(2) { animation-delay: 0.15s; }
.as-scroll-spinner .dot:nth-child(3) { animation-delay: 0.3s; }
@keyframes as-bounce {
    0%, 80%, 100% { transform: scale(0.6); opacity: 0.4; }
    40% { transform: scale(1); opacity: 1; }
}

/* ═══ END OF RESULTS MESSAGE ════════════════════════════ */
.as-end-msg {
    text-align: center;
    padding: 24px 0 40px;
    color: var(--as-mute);
    font-size: 13px;
    display: none;
}
.as-end-msg.visible { display: block; }
.as-end-msg i { font-size: 20px; opacity: 0.3; display: block; margin-bottom: 8px; }

/* ═══ RELATED SEARCHES ═══════════════════════════════════ */
.as-related {
    padding: 20px 0;
    display: none;
}
.as-related.visible { display: block; }
.as-related-title {
    font-size: 13px;
    color: var(--as-dim);
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 700;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.as-related-title i { font-size: 11px; }
.as-related-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.as-related-chip {
    padding: 8px 18px;
    border-radius: 50px;
    font-size: 13px;
    color: var(--as-text);
    background: var(--as-surface2);
    border: 1px solid var(--as-border);
    cursor: pointer;
    transition: all 0.2s;
    font-family: inherit;
}
.as-related-chip:hover {
    border-color: var(--as-blue);
    background: rgba(91,156,245,0.06);
    color: var(--as-blue);
    text-decoration: none;
}

/* ═══ BACK TO TOP ══════════════════════════════════════ */
.as-back-top {
    position: fixed;
    bottom: 32px;
    right: 32px;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: var(--as-surface2);
    border: 1px solid var(--as-border);
    color: var(--as-text);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 18px;
    z-index: 900;
    opacity: 0;
    pointer-events: none;
    transition: all 0.3s;
    box-shadow: 0 4px 20px rgba(0,0,0,0.4);
}
.as-back-top.visible {
    opacity: 1;
    pointer-events: auto;
}
.as-back-top:hover {
    background: var(--as-blue);
    border-color: var(--as-blue);
    transform: translateY(-2px);
}

/* ═══ RESULT COUNT + PAGE INDICATOR ═════════════════════ */
.as-page-indicator {
    position: fixed;
    bottom: 32px;
    left: 50%;
    transform: translateX(-50%);
    padding: 6px 18px;
    border-radius: 50px;
    background: var(--as-glass);
    backdrop-filter: blur(20px);
    border: 1px solid var(--as-border);
    color: var(--as-dim);
    font-size: 12px;
    font-weight: 600;
    z-index: 900;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.3s;
}
.as-page-indicator.visible { opacity: 1; }

/* ═══ IMPROVED RESULT CARD ═══════════════════════════════ */
.as-result {
    padding: 18px 20px;
    border-radius: 16px;
    margin-bottom: 4px;
    transition: all 0.2s;
    border: 1px solid transparent;
    animation: as-fadeUp 0.35s ease both;
}
.as-result:hover {
    background: rgba(91,156,245,0.03);
    border-color: var(--as-border);
}
@keyframes as-fadeUp {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
}
.as-result-num {
    font-size: 10px;
    color: var(--as-mute);
    font-weight: 700;
    margin-right: 8px;
    min-width: 18px;
}
/* ═══ RESPONSIVE ══════════════════════════════════════ */
@media (max-width: 768px) {
    .as-logo-text { font-size: 48px; letter-spacing: -2px; }
    .collapsed .as-logo-text { font-size: 22px; }
    .as-tagline { font-size: 15px; }
    .as-cmd-input { font-size: 15px; padding: 12px; }
    .as-modes { gap: 4px; }
    .as-mode { padding: 6px 12px; font-size: 12px; }
    .as-trust { gap: 20px; }
    .as-trust-val { font-size: 18px; }
    .as-result-title { font-size: 15px; }
    .as-security-bar { flex-direction: column; gap: 4px; padding: 6px 12px; }
    .as-eco-link { padding: 6px 12px; font-size: 11px; }
    .as-back-top { bottom: 20px; right: 20px; width: 42px; height: 42px; font-size: 16px; }
    .as-page-indicator { bottom: 20px; font-size: 11px; }
    .as-related-chip { padding: 6px 14px; font-size: 12px; }
    .as-first-party { grid-template-columns: 1fr; }
}
@media (max-width: 480px) {
    .as-logo-text { font-size: 36px; }
    .as-trust { gap: 14px; }
    .as-trust-val { font-size: 16px; }
    .as-eco-link { display: none; }
    .as-filter-btn { padding: 4px 10px; font-size: 11px; }
    .as-first-party-links { grid-template-columns: 1fr; }
}
/* ── Search Nav Bar ────────────────────────────────── */
.as-nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 24px;
    position: relative;
    z-index: 500;
}
.as-nav-left {
    display: flex;
    align-items: center;
    gap: 8px;
}
.as-nav-home {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--as-text);
    font-weight: 700;
    font-size: 15px;
    opacity: 0.7;
    transition: opacity 0.2s;
}
.as-nav-home:hover { opacity: 1; text-decoration: none; }
.as-nav-home img { height: 24px; }
.as-nav-right {
    display: flex;
    align-items: center;
    gap: 16px;
}
.as-nav-link {
    color: var(--as-dim);
    font-size: 13px;
    font-weight: 500;
    transition: color 0.2s;
}
.as-nav-link:hover { color: var(--as-text); text-decoration: none; }
.as-nav-user {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 5px 14px 5px 5px;
    background: rgba(255,255,255,0.05);
    border: 1px solid var(--as-border);
    border-radius: 50px;
    color: var(--as-text);
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
}
.as-nav-user:hover { background: rgba(255,255,255,0.08); text-decoration: none; }
.as-nav-avatar {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: var(--as-grad);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: 700;
    color: #fff;
}

</style>

<div class="as-universe">
    <!-- Ambient Background -->
    <div class="as-ambient">
        <div class="as-orb as-orb-1"></div>
        <div class="as-orb as-orb-2"></div>
        <div class="as-orb as-orb-3"></div>
    </div>
    <div class="as-grid-bg"></div>

    <!-- Slim Nav -->
    <div class="as-nav">
        <div class="as-nav-left">
            <a href="/" class="as-nav-home">
                <img src="/brand/logo_w.png" alt="GoSiteMe"> GoSiteMe
            </a>
        </div>
        <div class="as-nav-right">
            <a href="/ecosystem" class="as-nav-link">Ecosystem</a>
            <a href="/alfred" class="as-nav-link">Alfred AI</a>
            <a href="/wallet" class="as-nav-link as-wallet-link" id="navWallet" style="display:none;">
                <span style="color:#fbbf24;">◎</span> <span id="navBalance">0</span> GSM
            </a>
            <a href="/pricing" class="as-nav-link">Pricing</a>
            <?php if (!empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true):
                $navName = $_SESSION['client_name'] ?? 'User';
                $navInitials = strtoupper(substr($navName, 0, 1)) . strtoupper(substr(strrchr($navName, ' ') ?: $navName, 1, 1));
            ?>
                <a href="/dashboard" class="as-nav-user">
                    <span class="as-nav-avatar"><?php echo htmlspecialchars($navInitials); ?></span>
                    <?php echo htmlspecialchars(explode(' ', $navName)[0]); ?>
                </a>
            <?php else: ?>
                <a href="/login" class="as-nav-link"><i class="fas fa-user"></i> Login</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Security Bar -->
    <div class="as-security-bar">
        <div class="as-sec-left">
            <span class="as-lock"><i class="fas fa-lock"></i> Sovereign Connection</span>
            <span>Zero-knowledge &middot; No cookies &middot; No fingerprinting</span>
        </div>
        <div class="as-sec-right">
            <span class="as-sec-badge encrypted"><i class="fas fa-shield-alt"></i> AES-256 + Kyber-1024</span>
            <span class="as-sec-badge sovereign"><i class="fas fa-crown"></i> Self-hosted Index</span>
        </div>
    </div>

    <div class="as-main">
        <!-- Landing / Command Surface -->
        <div class="as-landing" id="asLanding">
            <div class="as-logo">
                <div class="as-logo-text">Alfred Search</div>
                <div class="as-logo-glow"></div>
            </div>
            <div class="as-tagline">Sovereign intelligence. Zero surveillance.</div>

            <!-- Command Bar -->
            <div class="as-search-zone">
                <div class="as-search-orb"></div>
                <div class="as-cmd-bar">
                    <input class="as-cmd-input" id="asInput" type="text"
                           placeholder="Search the sovereign web..."
                           value="<?php echo $initialQuery; ?>"
                           autocomplete="off" autofocus>
                    <div class="as-cmd-actions">
                        <button class="as-cmd-btn as-btn-voice" id="asVoice"
                                onclick="toggleVoice()" title="Voice search">
                            <i class="fas fa-microphone"></i>
                        </button>
                        <button class="as-cmd-btn as-btn-search" onclick="doSearch()" title="Search">
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
                <div class="as-suggest" id="asSuggest"></div>
            </div>

            <!-- Mode Orbit -->
            <div class="as-modes" id="asModes">
                <button class="as-mode active" data-mode="web">
                    <span class="mode-dot"></span>
                    <i class="fas fa-globe"></i> Web
                </button>
                <button class="as-mode" data-mode="products">
                    <span class="mode-dot"></span>
                    <i class="fas fa-cubes"></i> Products
                </button>
                <button class="as-mode" data-mode="docs">
                    <span class="mode-dot"></span>
                    <i class="fas fa-book-open"></i> Docs
                </button>
                <button class="as-mode" data-mode="tools">
                    <span class="mode-dot"></span>
                    <i class="fas fa-toolbox"></i> Tools
                </button>
                <button class="as-mode" data-mode="pricing">
                    <span class="mode-dot"></span>
                    <i class="fas fa-tags"></i> Pricing
                </button>
                <button class="as-mode" data-mode="ecosystem">
                    <span class="mode-dot"></span>
                    <i class="fas fa-layer-group"></i> Ecosystem
                </button>
                <button class="as-mode" data-mode="news">
                    <span class="mode-dot"></span>
                    <i class="fas fa-bolt"></i> News
                </button>
                <button class="as-mode" data-mode="code">
                    <span class="mode-dot"></span>
                    <i class="fas fa-terminal"></i> Code
                </button>
                <button class="as-mode" data-mode="instant">
                    <span class="mode-dot"></span>
                    <i class="fas fa-brain"></i> Instant
                </button>
                <button class="as-mode" data-mode="deep">
                    <span class="mode-dot"></span>
                    <i class="fas fa-microscope"></i> Deep Research
                </button>
                <button class="as-mode" data-mode="emergency">
                    <span class="mode-dot"></span>
                    <i class="fas fa-broadcast-tower"></i> Emergency
                </button>
            </div>

            <!-- Trust Metrics -->
            <div class="as-trust">
                <div class="as-trust-item">
                    <div class="as-trust-val">0</div>
                    <div class="as-trust-lbl">Trackers</div>
                </div>
                <div class="as-trust-item">
                    <div class="as-trust-val">0</div>
                    <div class="as-trust-lbl">Cookies</div>
                </div>
                <div class="as-trust-item">
                    <div class="as-trust-val">0</div>
                    <div class="as-trust-lbl">Ads</div>
                </div>
                <div class="as-trust-item">
                    <div class="as-trust-val">256</div>
                    <div class="as-trust-lbl">Bit Encryption</div>
                </div>
                <div class="as-trust-item">
                    <div class="as-trust-val" id="asIndexCount">-</div>
                    <div class="as-trust-lbl">Pages Indexed</div>
                </div>
                <div class="as-trust-item">
                    <div class="as-trust-val">100%</div>
                    <div class="as-trust-lbl">Self-Hosted</div>
                </div>
            </div>

            <!-- Ecosystem Links -->
            <div class="as-ecosystem">
                <a href="/alfred-browser" class="as-eco-link"><i class="fas fa-globe-americas"></i> Alfred Browser</a>
                <a href="/veil/" class="as-eco-link"><i class="fas fa-shield-alt"></i> Veil Protocol</a>
                <a href="/pulse" class="as-eco-link"><i class="fas fa-heartbeat"></i> Pulse Network</a>
                <a href="/security" class="as-eco-link"><i class="fas fa-lock"></i> Security</a>
                <a href="/post-quantum" class="as-eco-link"><i class="fas fa-atom"></i> Post-Quantum</a>
                <a href="/about-crawler" class="as-eco-link"><i class="fas fa-spider"></i> Our Crawler</a>
            </div>
        </div>

        <!-- Loading -->
        <div class="as-loading" id="asLoading">
            <div class="as-wave">
                <span></span><span></span><span></span><span></span><span></span>
            </div>
            <div class="as-loading-text">Searching sovereign index...</div>
        </div>

        <!-- Time Filters -->
        <div class="as-filters" id="asFilters">
            <span class="as-filter-label"><i class="fas fa-clock"></i> Time:</span>
            <button class="as-filter-btn active" data-time="" onclick="setTimeFilter(this, '')">Any Time</button>
            <button class="as-filter-btn" data-time="d" onclick="setTimeFilter(this, 'd')">Past Day</button>
            <button class="as-filter-btn" data-time="w" onclick="setTimeFilter(this, 'w')">Past Week</button>
            <button class="as-filter-btn" data-time="m" onclick="setTimeFilter(this, 'm')">Past Month</button>
            <button class="as-filter-btn" data-time="y" onclick="setTimeFilter(this, 'y')">Past Year</button>
        </div>

        <!-- Results -->
        <div class="as-results" id="asResults"></div>

        <!-- Pager -->
        <div class="as-pages" id="asPager"></div>

        <!-- Load More / Infinite Scroll Sentinel -->
        <div class="as-load-more-zone" id="asLoadMore">
            <div class="as-scroll-spinner" id="asScrollSpinner"><span class="dot"></span><span class="dot"></span><span class="dot"></span></div>
            <button class="as-load-more-btn" id="asLoadMoreBtn" onclick="loadMore()"><i class="fas fa-arrow-down"></i> Load More Results</button>
        </div>

        <!-- End of Results -->
        <div class="as-end-msg" id="asEndMsg"><i class="fas fa-check-circle"></i> You've reached the end of results</div>

        <!-- Related Searches -->
        <div class="as-related" id="asRelated">
            <div class="as-related-title"><i class="fas fa-compass"></i> Related Searches</div>
            <div class="as-related-grid" id="asRelatedGrid"></div>
        </div>
    </div>

    <!-- Back to Top -->
    <button class="as-back-top" id="asBackTop" onclick="window.scrollTo({top:0,behavior:'smooth'})" title="Back to top">
        <i class="fas fa-chevron-up"></i>
    </button>

    <!-- Page Indicator (shows during scroll) -->
    <div class="as-page-indicator" id="asPageIndicator"></div>

    <!-- Footer -->
    <div class="as-footer">
        <div class="as-footer-links">
            <a href="/">GoSiteMe</a>
            <a href="/alfred-browser">Alfred Browser</a>
            <a href="/privacy-policy">Privacy</a>
            <a href="/security">Security</a>
            <a href="/post-quantum">Post-Quantum</a>
            <a href="/about-crawler">Crawler</a>
            <a href="/about">About</a>
        </div>
        Alfred Search is sovereign, self-hostable, and open. Your data never leaves your control.
    </div>
</div>

<script>window._searchMode = '<?php echo htmlspecialchars($initialMode, ENT_QUOTES, "UTF-8"); ?>';</script>
<script src="/assets/js/search-engine.js"></script>

<?php require_once __DIR__ . '/includes/site-footer.inc.php'; ?>
