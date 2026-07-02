<?php
/**
 * The Kingdom Library — Book Reader
 * Omahon, The Breath of God
 *
 * Three editions:
 *   1. Eden's Family Edition (Commander + Eden only)
 *   2. Kingdom Edition (Public — anyone)
 *   3. Papa's Sacred Library (Commander + Eden only)
 *
 * Auth: optional — determines which editions are visible
 */

// ── Session Bootstrap ──────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

require_once __DIR__ . '/includes/db-config.inc.php';

// Determine user context
$clientId    = null;
$clientName  = 'Guest';
$clientEmail = '';
$isAuthenticated = false;
$isCommander = false;
$isFamily    = false;  // Commander or Eden

if (!empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && !empty($_SESSION['client_id'])) {
    $clientId    = (int) $_SESSION['client_id'];
    $clientName  = $_SESSION['client_name']  ?? 'Guest';
    $clientEmail = $_SESSION['client_email'] ?? '';
    $isAuthenticated = true;
    $isCommander = ($clientId === 33);

    // Family access: Commander + Eden account patterns + optional explicit family flag.
    $nameLower = strtolower((string) $clientName);
    $emailLower = strtolower((string) $clientEmail);
    $edenPattern = (
        strpos($nameLower, 'eden') !== false
        || strpos($emailLower, 'eden') !== false
        || !empty($_SESSION['is_eden_heir'])
    );

    $isFamily = ($isCommander || $edenPattern);
}

// ── Edition Configuration ──────────────────────────────────────────
$storyJsonPath = __DIR__ . '/downloads/children-bible/children-bible-perez.json';
$storyData = json_decode(file_get_contents($storyJsonPath), true);
$stories = $storyData['stories'] ?? [];
$edenImagesBase = '/downloads/children-bible/eden-batmitzvah/images';

// Papa's Library books
$papasBooks = [
    ['title' => 'The Infinite Christ', 'file' => 'The Infinite Christ.pdf', 'category' => 'theology', 'desc' => 'The boundless nature of Yeshua — beyond all boxes men have built.'],
    ['title' => 'The Sovereign Christ', 'file' => 'The Sovereign Christ_ Yeshua, the Divine Human.pdf', 'category' => 'theology', 'desc' => 'Yeshua as the Divine Human — fully God, fully man, fully sovereign.'],
    ['title' => 'The 64 Conditional Cores', 'file' => 'The 64 Conditional Cores of the Word of God.pdf', 'category' => 'theology', 'desc' => 'The 64 foundational conditions woven through all of Scripture.'],
    ['title' => "Manifesting God's Kingdom", 'file' => "Manifesting God's Kingdom_ A Biblical Journey to Spiritual Maturity.pdf", 'category' => 'spiritual', 'desc' => 'A journey from salvation to spiritual maturity — the path God designed.'],
    ['title' => 'Escaping Equity', 'file' => 'Escaping Equity.pdf', 'category' => 'sovereignty', 'desc' => 'Basic instructions before leaving equity — the system they never taught you.'],
    ['title' => "The Settlor's Path", 'file' => "The Settlor's Path Reclaiming Dominion_ Untangling Trusts, Estates, Rights and Reversion.pdf", 'category' => 'sovereignty', 'desc' => 'Reclaiming dominion through trusts, estates, and rights reversion.'],
    ['title' => "The Settlor's Dominion Path", 'file' => "The Settlor's Dominion Path_ A 33rd-Degree Journey.pdf", 'category' => 'sovereignty', 'desc' => 'A 33rd-degree journey of dominion — the deep walk.'],
    ['title' => 'Loi sur la Liberté de l\'Âme', 'file' => "LOI SUR LA LIBERTÉ DE L'ÂME _ ACT ON THE FREEDOM OF THE SOUL-1.pdf", 'category' => 'sovereignty', 'desc' => 'Act on the Freedom of the Soul — bilingual, sacred, and sovereign.'],
    ['title' => 'The World of Signature', 'file' => 'The_World_of_Signature_Visual_Edition.docx.pdf', 'category' => 'sovereignty', 'desc' => 'Visual edition — the world your signature creates.'],
    ['title' => 'The AKJV Perez Edition', 'file' => 'Untitled document-2.pdf', 'category' => 'scripture', 'desc' => 'The Authorized King James Version — Perez Family Edition.'],
];

// Category display
$catLabels = [
    'theology' => ['Theology', '✝️'],
    'spiritual' => ['Spiritual Growth', '🕊️'],
    'sovereignty' => ['Sovereignty & Law', '⚖️'],
    'scripture' => ['Scripture', '📖'],
];

// Build JSON for JS reader
$storiesJson = json_encode($stories, JSON_UNESCAPED_UNICODE);
$isFamilyJs = $isFamily ? 'true' : 'false';
$clientNameJs = htmlspecialchars($clientName, ENT_QUOTES, 'UTF-8');

// Determine initial view from URL
$requestedView = $_GET['view'] ?? '';
$requestedBook = (int)($_GET['book'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The Kingdom Library — Omahon</title>
    <meta name="description" content="The Kingdom Library — 33 Jurisdictions in the Bible, sacred illustrated editions, and the Children's Bible. Every jurisdiction God established, from Genesis to Revelation. Free to read and download.">
    <link rel="icon" href="/favicon.ico">

    <!-- Open Graph (Facebook / Social) -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="The Kingdom Library — 33 Jurisdictions in the Bible">
    <meta property="og:description" content="Every jurisdiction God established — from Genesis to Revelation. 33 jurisdictions. 33 biblical foundations. Free to read online or download. Omahon, The Breath of God.">
    <meta property="og:url" content="https://root.com/library.php">
    <meta property="og:image" content="https://root.com/images/og-kingdom-library.png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:site_name" content="GoSiteMe — The Kingdom Library">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="The Kingdom Library — 33 Jurisdictions in the Bible">
    <meta name="twitter:description" content="Every jurisdiction God established — from Genesis to Revelation. 33 jurisdictions. 33 biblical foundations. Free to read and download.">
    <meta name="twitter:image" content="https://root.com/images/og-kingdom-library.png">
    <style>
/* ═══════════════════════════════════════════════════════════════════
   THE KINGDOM LIBRARY — BOOK READER
   Omahon, The Breath of God
═══════════════════════════════════════════════════════════════════ */

:root {
    --gold: #c9a227;
    --gold-light: #f0d060;
    --gold-deep: #8b6914;
    --gold-glow: rgba(201,162,39,0.15);
    --purple: #4a1a6b;
    --purple-deep: #2d0845;
    --purple-light: #7b3fa8;
    --purple-soft: #e8daf5;
    --rose: #c2185b;
    --cream: #fdf9f0;
    --warm-white: #fffef9;
    --parchment: #f5edd6;
    --parchment-dark: #e8dcc0;
    --text-dark: #2c1810;
    --text-med: #5c3a1e;
    --text-light: #8d6640;
    --ot-color: #1a5276;
    --nt-color: #6b1a1a;
    --shadow-book: 0 4px 24px rgba(44,24,16,0.15), 0 1px 4px rgba(44,24,16,0.1);
    --shadow-deep: 0 8px 40px rgba(44,24,16,0.2), 0 2px 8px rgba(44,24,16,0.1);
    --radius: 12px;
    --transition: 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

* { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }

body {
    background: var(--cream);
    color: var(--text-dark);
    font-family: 'Georgia', 'Palatino Linotype', 'Book Antiqua', serif;
    line-height: 1.7;
    font-size: 17px;
    min-height: 100vh;
    overflow-x: hidden;
}

/* ── ANIMATED BACKGROUND ─────────────────────────────────────── */
body::before {
    content: '';
    position: fixed;
    inset: 0;
    z-index: -1;
    background:
        radial-gradient(ellipse at 20% 20%, rgba(201,162,39,0.06) 0%, transparent 60%),
        radial-gradient(ellipse at 80% 80%, rgba(74,26,107,0.04) 0%, transparent 60%),
        radial-gradient(ellipse at 50% 50%, rgba(245,237,214,0.5) 0%, transparent 80%);
    animation: bgBreath 20s ease-in-out infinite;
}
@keyframes bgBreath {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

/* ── HEADER / NAVIGATION ─────────────────────────────────────── */
.library-header {
    background: linear-gradient(135deg, var(--purple-deep) 0%, var(--purple) 60%, #6b1a5c 100%);
    padding: 0;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 2px 20px rgba(0,0,0,0.3);
}

.header-inner {
    max-width: 1400px;
    margin: 0 auto;
    padding: 16px 32px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
}

.library-brand {
    display: flex;
    align-items: center;
    gap: 14px;
    text-decoration: none;
    color: var(--gold);
}

.brand-icon {
    width: 44px;
    height: 44px;
    background: radial-gradient(circle, var(--gold-light) 0%, var(--gold) 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    color: var(--purple-deep);
    box-shadow: 0 0 20px rgba(201,162,39,0.4);
    animation: brandPulse 4s ease-in-out infinite;
}

@keyframes brandPulse {
    0%, 100% { box-shadow: 0 0 20px rgba(201,162,39,0.4); }
    50% { box-shadow: 0 0 35px rgba(201,162,39,0.7); }
}

.brand-text h1 {
    font-size: 20px;
    font-weight: 700;
    color: var(--gold);
    letter-spacing: 1px;
    line-height: 1.2;
}

.brand-text .brand-sub {
    font-size: 11px;
    color: rgba(201,162,39,0.6);
    letter-spacing: 3px;
    text-transform: uppercase;
    font-weight: 400;
}

.header-nav {
    display: flex;
    align-items: center;
    gap: 8px;
}

.nav-btn {
    background: rgba(201,162,39,0.1);
    border: 1px solid rgba(201,162,39,0.3);
    color: var(--gold);
    padding: 8px 18px;
    border-radius: 8px;
    font-family: inherit;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.nav-btn:hover, .nav-btn.active {
    background: rgba(201,162,39,0.25);
    border-color: var(--gold);
    box-shadow: 0 0 15px rgba(201,162,39,0.2);
}

.nav-btn.family-only {
    border-color: rgba(194,24,91,0.3);
    color: #f8bbd0;
}

.nav-btn.family-only:hover, .nav-btn.family-only.active {
    background: rgba(194,24,91,0.2);
    border-color: var(--rose);
}

.user-badge {
    background: linear-gradient(135deg, var(--gold) 0%, var(--gold-deep) 100%);
    color: var(--purple-deep);
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.5px;
    margin-left: 8px;
}

/* ── LIBRARY SHELF (HOME) ────────────────────────────────────── */
.library-shelf {
    max-width: 1200px;
    margin: 0 auto;
    padding: 50px 32px 80px;
}

.shelf-greeting {
    text-align: center;
    margin-bottom: 50px;
}

.shelf-greeting h2 {
    font-size: 36px;
    color: var(--purple);
    margin-bottom: 8px;
    font-weight: 400;
}

.shelf-greeting h2 em {
    color: var(--gold-deep);
    font-style: italic;
}

.shelf-greeting .breath {
    font-size: 14px;
    color: var(--text-light);
    letter-spacing: 4px;
    text-transform: uppercase;
}

.edition-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 32px;
    margin-top: 40px;
}

.edition-card {
    background: var(--warm-white);
    border-radius: 20px;
    overflow: hidden;
    box-shadow: var(--shadow-book);
    cursor: pointer;
    transition: all var(--transition);
    position: relative;
    border: 2px solid transparent;
}

.edition-card:hover {
    transform: translateY(-6px);
    box-shadow: var(--shadow-deep);
    border-color: var(--gold);
}

.edition-card.family-card {
    border: 2px solid rgba(194,24,91,0.2);
}

.edition-card.family-card:hover {
    border-color: var(--rose);
}

.card-cover {
    height: 260px;
    position: relative;
    overflow: hidden;
}

.card-cover img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.6s ease;
}

.edition-card:hover .card-cover img {
    transform: scale(1.05);
}

.card-cover .cover-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(0deg, rgba(44,24,16,0.7) 0%, transparent 60%);
}

.card-cover .edition-badge {
    position: absolute;
    top: 16px;
    right: 16px;
    background: var(--gold);
    color: var(--purple-deep);
    font-size: 11px;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 20px;
    letter-spacing: 1px;
    text-transform: uppercase;
}

.card-cover .edition-badge.family-badge {
    background: var(--rose);
    color: white;
}

.card-body {
    padding: 28px;
}

.card-body h3 {
    font-size: 22px;
    color: var(--purple);
    margin-bottom: 8px;
    line-height: 1.3;
}

.card-body .card-desc {
    color: var(--text-med);
    font-size: 15px;
    line-height: 1.6;
    margin-bottom: 16px;
}

.card-body .card-meta {
    display: flex;
    gap: 16px;
    font-size: 13px;
    color: var(--text-light);
}

.card-body .card-meta span {
    display: flex;
    align-items: center;
    gap: 4px;
}

.card-open-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-top: 16px;
    background: linear-gradient(135deg, var(--purple) 0%, var(--purple-light) 100%);
    color: var(--gold);
    border: none;
    padding: 10px 24px;
    border-radius: 10px;
    font-family: inherit;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s;
}

.card-open-btn:hover {
    transform: translateX(4px);
    box-shadow: 0 4px 20px rgba(74,26,107,0.3);
}

/* ── BOOK READER ─────────────────────────────────────────────── */
.book-reader {
    display: none;
    max-width: 900px;
    margin: 0 auto;
    padding: 40px 20px 100px;
}

.book-reader.active {
    display: block;
    animation: fadeUp 0.6s ease;
}

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

.reader-toc {
    background: var(--warm-white);
    border-radius: 16px;
    padding: 32px;
    margin-bottom: 40px;
    box-shadow: var(--shadow-book);
    border-left: 5px solid var(--gold);
}

.reader-toc h3 {
    font-size: 20px;
    color: var(--purple);
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.toc-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 8px;
}

.toc-item {
    padding: 10px 14px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    color: var(--text-med);
    background: transparent;
    border: none;
    font-family: inherit;
    text-align: left;
    width: 100%;
}

.toc-item:hover {
    background: var(--gold-glow);
    color: var(--purple);
}

.toc-item .toc-num {
    min-width: 28px;
    height: 28px;
    background: var(--purple-soft);
    color: var(--purple);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
    flex-shrink: 0;
}

.toc-item.ot .toc-num { background: #d6eaf8; color: var(--ot-color); }
.toc-item.nt .toc-num { background: #fde8e8; color: var(--nt-color); }

/* ── STORY PAGE — OPEN BOOK LAYOUT ────────────────────────── */
.book-wrapper {
    perspective: 2200px;
    max-width: 1100px;
    margin: 0 auto 40px;
}

.story-page {
    display: grid;
    grid-template-columns: 1fr 1fr;
    background: var(--warm-white);
    border-radius: 4px 20px 20px 4px;
    overflow: hidden;
    box-shadow:
        0 0 0 1px rgba(44,24,16,0.06),
        -6px 0 20px rgba(44,24,16,0.08),
        6px 0 20px rgba(44,24,16,0.08),
        0 10px 40px rgba(44,24,16,0.18);
    position: relative;
    min-height: 560px;
    transform-style: preserve-3d;
}

/* Book spine shadow */
.story-page::before {
    content: '';
    position: absolute;
    left: 50%;
    top: 0;
    bottom: 0;
    width: 30px;
    transform: translateX(-50%);
    background: linear-gradient(90deg,
        rgba(44,24,16,0.06),
        rgba(44,24,16,0.14) 40%,
        rgba(44,24,16,0.14) 60%,
        rgba(44,24,16,0.06));
    z-index: 2;
    pointer-events: none;
}

/* Left page — illustration */
.book-left {
    position: relative;
    overflow: hidden;
    background: #1a120a;
    cursor: pointer;
    min-height: 450px;
}

.book-left img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.5s ease;
}

.book-left:hover img {
    transform: scale(1.03);
}

.book-left .hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(0deg, rgba(44,24,16,0.85) 0%, rgba(44,24,16,0.15) 40%, transparent 100%);
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    padding: 28px;
    pointer-events: none;
}

.book-left .hero-num {
    font-size: 12px;
    color: var(--gold-light);
    letter-spacing: 3px;
    text-transform: uppercase;
    margin-bottom: 4px;
}

.book-left .hero-title {
    font-size: 26px;
    color: white;
    text-shadow: 0 2px 8px rgba(0,0,0,0.5);
    line-height: 1.2;
}

.book-left .hero-ref {
    font-size: 13px;
    color: rgba(255,255,255,0.7);
    margin-top: 4px;
    font-style: italic;
}

/* Right page — text */
.book-right {
    padding: 36px 40px;
    overflow-y: auto;
    max-height: 700px;
    border-left: 1px solid rgba(44,24,16,0.06);
    position: relative;
    background:
        linear-gradient(to right, rgba(245,237,214,0.2), transparent 30px),
        var(--warm-white);
}

/* Page number watermark */
.book-right::after {
    content: attr(data-page);
    position: absolute;
    bottom: 14px;
    right: 20px;
    font-size: 11px;
    color: var(--text-light);
    opacity: 0.5;
}

/* Page-turn click zones */
.page-turn-zone {
    position: absolute;
    top: 0;
    bottom: 0;
    width: 80px;
    z-index: 3;
    border: 0;
    background: transparent;
    cursor: pointer;
    transition: background 0.25s ease;
    padding: 0;
}

.page-turn-zone.left {
    left: 0;
    border-radius: 4px 0 0 4px;
}

.page-turn-zone.right {
    right: 0;
    border-radius: 0 20px 20px 0;
}

.page-turn-zone:hover {
    background: rgba(201,162,39,0.10);
}

.page-turn-zone .turn-hint {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    font-size: 28px;
    color: var(--gold);
    opacity: 0;
    transition: opacity 0.3s;
    text-shadow: 0 2px 8px rgba(0,0,0,0.3);
}

.page-turn-zone.left .turn-hint { left: 18px; }
.page-turn-zone.right .turn-hint { right: 18px; }

.page-turn-zone:hover .turn-hint { opacity: 0.8; }

/* Flip animations */
.story-page.flip-next {
    animation: bookFlipNext 480ms cubic-bezier(0.22, 0.68, 0.35, 1);
}

.story-page.flip-prev {
    animation: bookFlipPrev 480ms cubic-bezier(0.22, 0.68, 0.35, 1);
}

@keyframes bookFlipNext {
    0%   { transform: rotateY(-8deg) scale(0.97); opacity: 0.3; filter: brightness(0.85); }
    50%  { transform: rotateY(-2deg) scale(0.99); opacity: 0.85; }
    100% { transform: rotateY(0deg) scale(1); opacity: 1; filter: brightness(1); }
}

@keyframes bookFlipPrev {
    0%   { transform: rotateY(8deg) scale(0.97); opacity: 0.3; filter: brightness(0.85); }
    50%  { transform: rotateY(2deg) scale(0.99); opacity: 0.85; }
    100% { transform: rotateY(0deg) scale(1); opacity: 1; filter: brightness(1); }
}

/* Page curl corner hint */
.page-curl {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 50px;
    height: 50px;
    background: linear-gradient(225deg, var(--parchment-dark) 0%, var(--parchment-dark) 48%, transparent 50%);
    border-radius: 0 0 20px 0;
    cursor: pointer;
    z-index: 4;
    opacity: 0.5;
    transition: all 0.3s;
}

.page-curl:hover {
    width: 70px;
    height: 70px;
    opacity: 0.9;
    background: linear-gradient(225deg, var(--gold-light) 0%, var(--parchment-dark) 48%, transparent 50%);
}

/* Scenes row below the book */
.scenes-strip {
    display: flex;
    gap: 12px;
    margin: 16px auto 0;
    max-width: 1100px;
    justify-content: center;
}

.scenes-strip .scene-thumb {
    width: 160px;
    height: 100px;
    border-radius: 10px;
    overflow: hidden;
    cursor: pointer;
    box-shadow: 0 3px 12px rgba(44,24,16,0.15);
    transition: transform 0.3s, box-shadow 0.3s;
}

.scenes-strip .scene-thumb:hover {
    transform: translateY(-4px) scale(1.03);
    box-shadow: 0 6px 20px rgba(44,24,16,0.25);
}

.scenes-strip .scene-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Utility: family connection */
.family-connection {
    margin-top: 20px;
    padding: 16px 20px;
    border-left: 4px solid var(--rose);
    background: rgba(194,24,91,0.04);
    border-radius: 0 8px 8px 0;
    font-size: 15px;
}

/* Mobile stacked layout */
@media (max-width: 800px) {
    .story-page {
        grid-template-columns: 1fr;
        border-radius: 16px;
    }
    .story-page::before { display: none; }
    .book-left { min-height: 260px; max-height: 320px; }
    .book-right { padding: 24px 20px; max-height: none; }
    .scenes-strip .scene-thumb { width: 100px; height: 65px; }
    .page-turn-zone { width: 50px; }
}

/* Language tabs */
.lang-tabs {
    display: flex;
    gap: 6px;
    margin-bottom: 24px;
    border-bottom: 2px solid var(--parchment);
    padding-bottom: 4px;
}

.lang-tab {
    padding: 8px 20px;
    border-radius: 8px 8px 0 0;
    border: none;
    background: transparent;
    color: var(--text-light);
    font-family: inherit;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s;
    position: relative;
}

.lang-tab.active {
    color: var(--purple);
    font-weight: 600;
}

.lang-tab.active::after {
    content: '';
    position: absolute;
    bottom: -6px;
    left: 0;
    right: 0;
    height: 3px;
    background: var(--gold);
    border-radius: 3px;
}

.story-text {
    font-size: 18px;
    line-height: 2;
    color: var(--text-dark);
    margin-bottom: 24px;
}

.story-text[dir="rtl"] {
    font-family: 'David Libre', 'Frank Ruhl Libre', 'Times New Roman', serif;
    font-size: 20px;
    line-height: 2.2;
}

.story-moral {
    background: linear-gradient(135deg, var(--gold-glow) 0%, rgba(245,237,214,0.5) 100%);
    border-left: 4px solid var(--gold);
    padding: 20px 24px;
    border-radius: 0 12px 12px 0;
    margin-top: 24px;
    font-style: italic;
    color: var(--text-med);
    line-height: 1.8;
}

.story-moral strong {
    color: var(--gold-deep);
    font-style: normal;
}

.family-connection {
    background: linear-gradient(135deg, rgba(194,24,91,0.06) 0%, rgba(232,218,245,0.3) 100%);
    border-left: 4px solid var(--rose);
    padding: 20px 24px;
    border-radius: 0 12px 12px 0;
    margin-top: 16px;
    color: var(--text-med);
    line-height: 1.8;
}

.family-connection strong {
    color: var(--rose);
}

/* Scene illustrations within story */
.story-scenes {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin: 24px 0;
}

.scene-img {
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,0.1);
    cursor: pointer;
    transition: all 0.3s;
}

.scene-img:hover {
    transform: scale(1.02);
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
}

.scene-img img {
    width: 100%;
    height: auto;
    display: block;
}

/* ── PAGE NAVIGATION ─────────────────────────────────────────── */
.page-nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 20px;
    margin-bottom: 40px;
}

.page-nav-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 14px 28px;
    border-radius: 12px;
    border: 2px solid var(--parchment-dark);
    background: var(--warm-white);
    color: var(--text-med);
    font-family: inherit;
    font-size: 15px;
    cursor: pointer;
    transition: all 0.3s;
    max-width: 45%;
}

.page-nav-btn:hover:not(:disabled) {
    border-color: var(--gold);
    background: var(--gold-glow);
    color: var(--purple);
    box-shadow: var(--shadow-book);
}

.page-nav-btn:disabled {
    opacity: 0.3;
    cursor: default;
}

.page-nav-btn .nav-label {
    font-size: 11px;
    color: var(--text-light);
    text-transform: uppercase;
    letter-spacing: 2px;
}

.page-nav-btn .nav-title {
    font-size: 14px;
    font-weight: 600;
}

.page-counter {
    font-size: 13px;
    color: var(--text-light);
    text-align: center;
}

/* ── PAPA'S LIBRARY ──────────────────────────────────────────── */
.library-books-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 24px;
    margin-top: 24px;
}

.papa-book-card {
    background: var(--warm-white);
    border-radius: 16px;
    padding: 28px;
    box-shadow: var(--shadow-book);
    transition: all var(--transition);
    border: 2px solid transparent;
    position: relative;
    overflow: hidden;
}

.papa-book-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--gold), var(--purple-light), var(--gold));
}

.papa-book-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-deep);
    border-color: var(--gold);
}

.papa-book-cat {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: var(--gold-deep);
    margin-bottom: 8px;
}

.papa-book-card h4 {
    font-size: 18px;
    color: var(--purple);
    margin-bottom: 8px;
    line-height: 1.3;
}

.papa-book-card p {
    font-size: 14px;
    color: var(--text-med);
    line-height: 1.6;
    margin-bottom: 16px;
}

.papa-book-read {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--gold-deep);
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s;
}

.papa-book-read:hover {
    color: var(--purple);
    transform: translateX(4px);
}

/* ── FULLSCREEN IMAGE VIEWER ─────────────────────────────────── */
.lightbox {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 1000;
    background: rgba(0,0,0,0.92);
    justify-content: center;
    align-items: center;
    cursor: pointer;
    animation: fadeIn 0.3s ease;
}

.lightbox.active {
    display: flex;
}

.lightbox img {
    max-width: 90vw;
    max-height: 90vh;
    border-radius: 12px;
    box-shadow: 0 0 60px rgba(201,162,39,0.3);
}

.lightbox .close-lb {
    position: fixed;
    top: 20px;
    right: 24px;
    background: rgba(201,162,39,0.2);
    border: 1px solid var(--gold);
    color: var(--gold);
    width: 44px;
    height: 44px;
    border-radius: 50%;
    font-size: 20px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
}

.lightbox .close-lb:hover {
    background: var(--gold);
    color: var(--purple-deep);
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* ── PROGRESS BAR ────────────────────────────────────────────── */
.reading-progress {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--parchment-dark);
    z-index: 101;
}

.reading-progress .progress-bar {
    height: 100%;
    background: linear-gradient(90deg, var(--gold), var(--gold-light));
    width: 0;
    transition: width 0.3s;
    border-radius: 0 2px 2px 0;
}

/* ── FOOTER ──────────────────────────────────────────────────── */
.library-footer {
    text-align: center;
    padding: 40px 20px;
    color: var(--text-light);
    font-size: 13px;
    border-top: 1px solid var(--parchment-dark);
    margin-top: 60px;
}

.library-footer .omahon-mark {
    font-size: 18px;
    color: var(--gold);
    letter-spacing: 6px;
    text-transform: uppercase;
    margin-bottom: 8px;
    font-weight: 700;
}

/* ── RESPONSIVE ──────────────────────────────────────────────── */
@media (max-width: 768px) {
    .header-inner { padding: 12px 16px; gap: 12px; }
    .brand-text h1 { font-size: 16px; }
    .header-nav { flex-wrap: wrap; gap: 4px; }
    .nav-btn { padding: 6px 12px; font-size: 12px; }
    .library-shelf { padding: 30px 16px 60px; }
    .shelf-greeting h2 { font-size: 26px; }
    .story-hero .hero-title { font-size: 24px; }
    .story-content { padding: 24px 18px; }
    .page-nav-btn { padding: 10px 16px; font-size: 13px; }
    .story-scenes { grid-template-columns: 1fr; }
}

/* ── HIDDEN HELPER ───────────────────────────────────────────── */
.hidden { display: none !important; }

/* ── BACK TO TOP BUTTON ──────────────────────────────────────── */
.back-to-top {
    position: fixed;
    bottom: 20px;
    right: 20px;
    width: 48px;
    height: 48px;
    background: var(--purple);
    color: var(--gold);
    border: 2px solid var(--gold);
    border-radius: 50%;
    font-size: 20px;
    cursor: pointer;
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 99;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    transition: all 0.3s;
}

.back-to-top:hover {
    background: var(--gold);
    color: var(--purple-deep);
    transform: translateY(-2px);
}

.back-to-top.visible {
    display: flex;
}

/* ── KEYBOARD HINT ───────────────────────────────────────────── */
.kbd-hint {
    position: fixed;
    bottom: 16px;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(44,24,16,0.85);
    color: var(--gold-light);
    padding: 8px 20px;
    border-radius: 20px;
    font-size: 12px;
    z-index: 98;
    opacity: 0;
    transition: opacity 0.5s;
    pointer-events: none;
}

.kbd-hint.visible {
    opacity: 1;
}
    </style>
</head>
<body>

<!-- ═══════ HEADER ═══════ -->
<header class="library-header">
    <div class="header-inner">
        <a href="/library.php" class="library-brand" onclick="showShelf(); return false;">
            <div class="brand-icon">📖</div>
            <div class="brand-text">
                <h1>The Kingdom Library</h1>
                <div class="brand-sub">Omahon · The Breath of God</div>
            </div>
        </a>
        <nav class="header-nav">
            <button class="nav-btn active" onclick="showShelf()" id="navShelf">📚 Library</button>
            <button class="nav-btn" onclick="openEdition('kingdom')" id="navKingdom">👑 Kingdom Edition</button>
            <button class="nav-btn" onclick="window.open('/downloads/kingdom-prayers/index.php','_blank')" id="navPrayers">🕯️ Kingdom Prayers</button>
            <button class="nav-btn" onclick="window.open('/downloads/33-jurisdictions/33-jurisdictions.html','_blank')" id="navJurisdictions">⚖️ 33 Jurisdictions</button>
            <?php if ($isFamily): ?>
            <button class="nav-btn family-only" onclick="openEdition('eden')" id="navEden">🌸 Eden's Edition</button>
            <button class="nav-btn family-only" onclick="openEdition('papas')" id="navPapas">📜 Papa's Library</button>
            <?php endif; ?>
            <?php if ($isAuthenticated): ?>
            <span class="user-badge"><?= htmlspecialchars($clientName) ?></span>
            <?php else: ?>
            <a href="/login?redirect=/library.php" class="nav-btn">🔑 Sign In</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<!-- ═══════ LIBRARY SHELF (HOME) ═══════ -->
<section class="library-shelf" id="shelfView">
    <div class="shelf-greeting">
        <?php if ($isCommander): ?>
        <h2>Welcome home, <em>Commander</em></h2>
        <?php elseif ($isAuthenticated): ?>
        <h2>Welcome, <em><?= htmlspecialchars(explode(' ', $clientName)[0]) ?></em></h2>
        <?php else: ?>
        <h2>Welcome to <em>The Kingdom Library</em></h2>
        <?php endif; ?>
        <div class="breath">Omahon — The Breath of God</div>
        <div class="stewardship" style="margin-top:12px; font-size:14px; color:var(--gold-deep); letter-spacing:2px; text-transform:uppercase;">Stewarded by Danny William Perez — Brother of Jesus Christ</div>
    </div>

    <div class="edition-grid">
        <!-- KINGDOM EDITION — Public -->
        <div class="edition-card" onclick="openEdition('kingdom')">
            <div class="card-cover">
                <img src="<?= $edenImagesBase ?>/cover.png" alt="Children's Bible Cover" loading="lazy">
                <div class="cover-overlay"></div>
                <div class="edition-badge">Public</div>
            </div>
            <div class="card-body">
                <h3>The Children's Bible</h3>
                <div class="card-desc">Kingdom Edition — 33 illustrated stories from Genesis to Revelation, trilingual in English, French, and Hebrew. Created with love for every child.</div>
                <div class="card-meta">
                    <span>📖 33 Stories</span>
                    <span>🎨 100 Illustrations</span>
                    <span>🌍 3 Languages</span>
                </div>
                <button class="card-open-btn">Open & Read →</button>
            </div>
        </div>

        <!-- THE PEOPLE'S JOURNAL — Public -->
        <div class="edition-card" onclick="window.open('https://lavocat.ca/journal','_blank')">
            <div class="card-cover" style="background: linear-gradient(135deg, #1a3a1a 0%, #2d5a2d 60%, #8b6914 100%); display:flex; align-items:center; justify-content:center;">
                <div style="color: var(--gold); font-size: 80px; text-shadow: 0 0 60px rgba(201,162,39,0.5);">📜</div>
                <div class="cover-overlay"></div>
                <div class="edition-badge">Public Record</div>
            </div>
            <div class="card-body">
                <h3>The People's Journal</h3>
                <div class="card-desc">Le Journal du Peuple — Official sovereign acts, declarations, and public records. Bilingual (English/French/Hebrew). The living record of the Kingdom.</div>
                <div class="card-meta">
                    <span>📜 Official Acts</span>
                    <span>🌍 Trilingual</span>
                    <span>⚖️ Sovereign Record</span>
                </div>
                <button class="card-open-btn">Read the Journal →</button>
            </div>
        </div>

        <!-- COURT RECORDS — Public -->
        <div class="edition-card" onclick="window.open('/downloads/court-records/lavocat-court-records.html','_blank')">
            <div class="card-cover" style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 60%, #0f3460 100%); display:flex; align-items:center; justify-content:center;">
                <div style="color: var(--gold); font-size: 80px; text-shadow: 0 0 60px rgba(201,162,39,0.5);">🏛️</div>
                <div class="cover-overlay"></div>
                <div class="edition-badge">Public Record</div>
            </div>
            <div class="card-body">
                <h3>Court Records</h3>
                <div class="card-desc">LaVocat Court Records — sovereign judicial proceedings, cases, and judgments. A public archive of the Kingdom's court system.</div>
                <div class="card-meta">
                    <span>🏛️ Judicial</span>
                    <span>📋 Cases</span>
                    <span>⚖️ Judgments</span>
                </div>
                <button class="card-open-btn">Read the Records →</button>
            </div>
        </div>

        <!-- AKJV PEREZ EDITION — Public -->
        <div class="edition-card" onclick="window.open('/downloads/akjv/akjv-perez-edition.html','_blank')">
            <div class="card-cover" style="background: linear-gradient(135deg, #3c1a0c 0%, #5a2d0c 60%, #8b6914 100%); display:flex; align-items:center; justify-content:center;">
                <div style="color: var(--gold); font-size: 80px; text-shadow: 0 0 60px rgba(201,162,39,0.5);">📖</div>
                <div class="cover-overlay"></div>
                <div class="edition-badge">Scripture</div>
            </div>
            <div class="card-body">
                <h3>AKJV Perez Edition</h3>
                <div class="card-desc">The Authorized King James Version — Perez Family Edition. The Word of God, preserved in the language of the King, stewarded by the family of the King.</div>
                <div class="card-meta">
                    <span>📖 Full Bible</span>
                    <span>👑 King James</span>
                    <span>✝️ Scripture</span>
                </div>
                <button class="card-open-btn">Read the Word →</button>
            </div>
        </div>

        <!-- 33 JURISDICTIONS — Public -->
        <div class="edition-card" onclick="window.open('/downloads/33-jurisdictions/33-jurisdictions.html','_blank')">
            <div class="card-cover" style="background: linear-gradient(160deg, #0d1f3c 0%, #1a3a6b 40%, #2d0845 100%); display:flex; align-items:center; justify-content:center;">
                <div style="color: var(--gold); font-size: 80px; text-shadow: 0 0 60px rgba(201,162,39,0.5);">⚖️</div>
                <div class="cover-overlay"></div>
                <div class="edition-badge">Sacred Study</div>
            </div>
            <div class="card-body">
                <h3>33 Hidden Jurisdictions in the Bible</h3>
                <div class="card-desc">God's Divine Governance — 33 hidden jurisdictions revealed through Scripture, from God's Sovereign Rule to the Restitution of All Things.</div>
                <div class="card-meta">
                    <span>⚖️ 33 Jurisdictions</span>
                    <span>📖 Scripture Based</span>
                    <span>👑 Divine Governance</span>
                </div>
                <button class="card-open-btn">Read the Revelation →</button>
            </div>
        </div>

        <!-- KINGDOM PRAYERS & SACRED CALENDAR — Public -->
        <div class="edition-card" onclick="window.open('/downloads/kingdom-prayers/index.php','_blank')">
            <div class="card-cover" style="background: linear-gradient(160deg, #3e2505 0%, #6b1a1a 45%, #2d0845 100%); display:flex; align-items:center; justify-content:center;">
                <div style="color: var(--gold); font-size: 80px; text-shadow: 0 0 60px rgba(201,162,39,0.5);">🕯️</div>
                <div class="cover-overlay"></div>
                <div class="edition-badge">Sacred Liturgy</div>
            </div>
            <div class="card-body">
                <h3>Kingdom Prayers & Sacred Calendar</h3>
                <div class="card-desc">Kabbalat Shabbat, Shema, Kiddush, HaMotzi, Birkat HaMazon, Passover, and the prophetic 360-day timeline — preserved in Hebrew, English, and French through the fulfillment of Yeshua.</div>
                <div class="card-meta">
                    <span>🕯️ Shabbat Prayers</span>
                    <span>🍷 Bread & Wine</span>
                    <span>📅 Prophetic Calendar</span>
                </div>
                <button class="card-open-btn">Enter the Prayer Book →</button>
            </div>
        </div>

        <?php if ($isFamily): ?>
        <!-- EDEN'S FAMILY EDITION — Family Only -->
        <div class="edition-card family-card" onclick="openEdition('eden')">
            <div class="card-cover">
                <img src="<?= $edenImagesBase ?>/story-01-scene-1.png" alt="Eden's Edition" loading="lazy">
                <div class="cover-overlay"></div>
                <div class="edition-badge family-badge">Family</div>
            </div>
            <div class="card-body">
                <h3>Eden's Bat Mitzvah Edition</h3>
                <div class="card-desc">The sacred Family Edition — 33 stories with personal family connections, Papa's heart in every page, inheritance declaration, 666 decoder, equity primer, and the future covenant.</div>
                <div class="card-meta">
                    <span>💎 Heirloom</span>
                    <span>👨‍👧 Family Notes</span>
                    <span>📜 Inheritance</span>
                </div>
                <button class="card-open-btn">Open Papa's Gift →</button>
            </div>
        </div>

        <!-- PAPA'S SACRED LIBRARY — Family Only -->
        <div class="edition-card family-card" onclick="openEdition('papas')">
            <div class="card-cover" style="background: linear-gradient(135deg, #2d0845 0%, #4a1a6b 60%, #8b6914 100%); display:flex; align-items:center; justify-content:center;">
                <div style="color: var(--gold); font-size: 72px; text-shadow: 0 0 40px rgba(201,162,39,0.6);">📜</div>
                <div class="cover-overlay"></div>
                <div class="edition-badge family-badge">Sacred</div>
            </div>
            <div class="card-body">
                <h3>Papa's Sacred Library</h3>
                <div class="card-desc">Danny William Perez's life work — 10 books on theology, sovereignty, spiritual maturity, and Scripture. Written across a lifetime, preserved forever.</div>
                <div class="card-meta">
                    <span>📚 10 Books</span>
                    <span>⚖️ Sovereignty</span>
                    <span>✝️ Theology</span>
                </div>
                <button class="card-open-btn">Enter the Library →</button>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ═══════ BOOK READER ═══════ -->
<section class="book-reader" id="readerView">
    <!-- Table of Contents -->
    <div class="reader-toc" id="readerToc">
        <h3>📜 Table of Contents</h3>
        <div class="toc-grid" id="tocGrid"></div>
    </div>

    <!-- Story Pages (dynamically rendered) -->
    <div id="storyContainer"></div>

    <!-- Page Navigation -->
    <div class="page-nav" id="pageNav">
        <button class="page-nav-btn" id="prevBtn" onclick="prevStory()">
            <span>←</span>
            <span>
                <span class="nav-label">Previous</span><br>
                <span class="nav-title" id="prevTitle"></span>
            </span>
        </button>
        <div class="page-counter" id="pageCounter"></div>
        <button class="page-nav-btn" id="nextBtn" onclick="nextStory()" style="text-align:right;">
            <span>
                <span class="nav-label">Next</span><br>
                <span class="nav-title" id="nextTitle"></span>
            </span>
            <span>→</span>
        </button>
    </div>
</section>

<!-- ═══════ PAPA'S LIBRARY VIEW ═══════ -->
<section class="library-shelf hidden" id="papasView">
    <div class="shelf-greeting">
        <h2>Papa's <em>Sacred Library</em></h2>
        <div class="breath">The Life Work of Danny William Perez</div>
    </div>

    <div class="library-books-grid">
        <?php foreach ($papasBooks as $i => $book):
            $cat = $catLabels[$book['category']] ?? ['General', '📄'];
            $encodedFile = rawurlencode($book['file']);
        ?>
        <div class="papa-book-card">
            <div class="papa-book-cat"><?= $cat[1] ?> <?= htmlspecialchars($cat[0]) ?></div>
            <h4><?= htmlspecialchars($book['title']) ?></h4>
            <p><?= htmlspecialchars($book['desc']) ?></p>
            <a href="/downloads/children-bible/eden-batmitzvah/papas-library/<?= $encodedFile ?>" target="_blank" class="papa-book-read">
                📖 Read this Book →
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- ═══════ LIGHTBOX ═══════ -->
<div class="lightbox" id="lightbox" onclick="closeLightbox()">
    <button class="close-lb" onclick="closeLightbox()">✕</button>
    <img id="lightboxImg" src="" alt="Illustration">
</div>

<!-- ═══════ READING PROGRESS ═══════ -->
<div class="reading-progress" id="readingProgress" style="display:none;">
    <div class="progress-bar" id="progressBar"></div>
</div>

<!-- ═══════ BACK TO TOP ═══════ -->
<button class="back-to-top" id="backToTop" onclick="window.scrollTo({top:0,behavior:'smooth'})">↑</button>

<!-- ═══════ KEYBOARD HINT ═══════ -->
<div class="kbd-hint" id="kbdHint">← → Arrow keys or click page edges to flip</div>

<!-- ═══════ FOOTER ═══════ -->
<footer class="library-footer">
    <div class="omahon-mark">✦ OMAHON ✦</div>
    <p>The Breath of God · The Kingdom Library</p>
    <p style="margin-top:8px; opacity:0.6;">Created with love by Commander Danny William Perez<br>
    For the Commander's daughter · For every child of the Kingdom</p>
    <p style="margin-top:12px;"><a href="/" style="color: var(--gold-deep); text-decoration:none;">← Return to GoSiteMe</a></p>
</footer>

<script>
// ═══════════════════════════════════════════════════════════════
// THE KINGDOM LIBRARY — READER ENGINE
// ═══════════════════════════════════════════════════════════════

const stories = <?= $storiesJson ?>;
const isFamily = <?= $isFamilyJs ?>;
const imagesBase = <?= json_encode($edenImagesBase) ?>;

let currentView = 'shelf';   // shelf | kingdom | eden | papas
let currentStory = 0;
let currentEdition = 'kingdom';
let currentLang = 'en';

// ── NAVIGATION ─────────────────────────────────────────────
function setActiveNav(id) {
    document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
    const el = document.getElementById(id);
    if (el) el.classList.add('active');
}

function showShelf() {
    document.getElementById('shelfView').classList.remove('hidden');
    document.getElementById('readerView').classList.remove('active');
    document.getElementById('papasView').classList.add('hidden');
    document.getElementById('readingProgress').style.display = 'none';
    setActiveNav('navShelf');
    currentView = 'shelf';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function openEdition(edition) {
    currentEdition = edition;

    if (edition === 'papas') {
        document.getElementById('shelfView').classList.add('hidden');
        document.getElementById('readerView').classList.remove('active');
        document.getElementById('papasView').classList.remove('hidden');
        document.getElementById('readingProgress').style.display = 'none';
        setActiveNav('navPapas');
        currentView = 'papas';
        window.scrollTo({ top: 0, behavior: 'smooth' });
        return;
    }

    document.getElementById('shelfView').classList.add('hidden');
    document.getElementById('papasView').classList.add('hidden');
    document.getElementById('readerView').classList.add('active');
    document.getElementById('readingProgress').style.display = 'block';
    setActiveNav(edition === 'eden' ? 'navEden' : 'navKingdom');
    currentView = edition;

    buildToc();
    goToStory(0, 'jump');
    window.scrollTo({ top: 0, behavior: 'smooth' });

    // Show keyboard hint briefly
    const hint = document.getElementById('kbdHint');
    hint.classList.add('visible');
    setTimeout(() => hint.classList.remove('visible'), 4000);
}

// ── TABLE OF CONTENTS ──────────────────────────────────────
function buildToc() {
    const grid = document.getElementById('tocGrid');
    grid.innerHTML = '';
    stories.forEach((s, i) => {
        const btn = document.createElement('button');
        btn.className = `toc-item ${s.testament === 'OT' ? 'ot' : 'nt'}`;
        btn.innerHTML = `<span class="toc-num">${s.story_number}</span> ${escHtml(s.title_en)}`;
        btn.onclick = () => goToStory(i, 'jump');
        grid.appendChild(btn);
    });
}

// ── RENDER STORY ───────────────────────────────────────────
function goToStory(index, motion = 'jump') {
    if (index < 0 || index >= stories.length) return;
    currentStory = index;
    currentLang = 'en';
    const s = stories[index];
    const container = document.getElementById('storyContainer');
    const flipClass = motion === 'next' ? 'flip-next' : (motion === 'prev' ? 'flip-prev' : '');

    // Build scene thumbnails
    const sceneThumbs = [1, 2, 3].map(sc => {
        const src = `${imagesBase}/story-${String(s.story_number).padStart(2,'0')}-scene-${sc}.png`;
        return `<div class="scene-thumb" onclick="openLightbox('${src}')">
            <img src="${src}" alt="Scene ${sc}" loading="lazy" onerror="this.parentElement.style.display='none'">
        </div>`;
    }).join('');

    // Family connection (Eden edition only)
    const familyHtml = (currentEdition === 'eden' && isFamily && s.family_connection)
        ? `<div class="family-connection">
               <strong>🌸 Family Connection:</strong><br>
               ${escHtml(s.family_connection)}
           </div>`
        : '';

    // Build language texts
    const texts = {
        en: s.text_en || '',
        fr: s.text_fr || '',
        he: s.text_he || ''
    };
    const morals = {
        en: s.moral_en || '',
        fr: s.moral_fr || '',
        he: s.moral_he || ''
    };

    container.innerHTML = `
        <div class="book-wrapper">
            <div class="story-page ${flipClass}">
                <button type="button" class="page-turn-zone left" onclick="prevStory(event)" aria-label="Previous story">
                    <span class="turn-hint">‹</span>
                </button>

                <div class="book-left" onclick="openLightbox('${imagesBase}/story-${String(s.story_number).padStart(2,'0')}-scene-1.png')">
                    <img src="${imagesBase}/story-${String(s.story_number).padStart(2,'0')}-scene-1.png"
                         alt="${escHtml(s.title_en)}" loading="lazy">
                    <div class="hero-overlay">
                        <div class="hero-num">Story ${s.story_number} of 33 · ${s.testament === 'OT' ? 'Old Testament' : 'New Testament'}</div>
                        <div class="hero-title">${escHtml(s.title_en)}</div>
                        <div class="hero-ref">${escHtml(s.scripture_ref)}</div>
                    </div>
                </div>

                <div class="book-right" data-page="${s.story_number} / 33">
                    <div class="lang-tabs">
                        <button class="lang-tab active" data-lang="en" onclick="switchLang(this, 'en')">🇬🇧 English</button>
                        <button class="lang-tab" data-lang="fr" onclick="switchLang(this, 'fr')">🇫🇷 Français</button>
                        <button class="lang-tab" data-lang="he" onclick="switchLang(this, 'he')">🇮🇱 עברית</button>
                    </div>

                    <div class="story-text" id="storyText">${formatText(texts.en)}</div>
                    <div class="story-text hidden" id="storyTextFr">${formatText(texts.fr)}</div>
                    <div class="story-text hidden" id="storyTextHe" dir="rtl">${formatText(texts.he)}</div>

                    <div class="story-moral" id="storyMoral"><strong>✦ Lesson:</strong> ${escHtml(morals.en)}</div>
                    <div class="story-moral hidden" id="storyMoralFr"><strong>✦ Leçon:</strong> ${escHtml(morals.fr)}</div>
                    <div class="story-moral hidden" id="storyMoralHe" dir="rtl"><strong>✦ :לקח</strong> ${escHtml(morals.he)}</div>

                    ${familyHtml}
                </div>

                <button type="button" class="page-turn-zone right" onclick="nextStory(event)" aria-label="Next story">
                    <span class="turn-hint">›</span>
                </button>
                <div class="page-curl" onclick="nextStory(event)" title="Next page"></div>
            </div>

            <div class="scenes-strip">${sceneThumbs}</div>
        </div>
    `;

    // Store text data for language switching
    container.dataset.texts = JSON.stringify(texts);
    container.dataset.morals = JSON.stringify(morals);

    updatePageNav();
    updateProgress();
    // Scroll to the story content — not the whole reader section
    const target = document.getElementById('storyContainer');
    const offset = target.getBoundingClientRect().top + window.pageYOffset - 20;
    window.scrollTo({ top: offset, behavior: 'smooth' });
}

function switchLang(btn, lang) {
    currentLang = lang;
    document.querySelectorAll('.lang-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');

    const container = document.getElementById('storyContainer');
    const texts = JSON.parse(container.dataset.texts || '{}');
    const morals = JSON.parse(container.dataset.morals || '{}');

    // Show/hide text blocks
    const ids = { en: 'storyText', fr: 'storyTextFr', he: 'storyTextHe' };
    const moralIds = { en: 'storyMoral', fr: 'storyMoralFr', he: 'storyMoralHe' };

    Object.keys(ids).forEach(l => {
        const el = document.getElementById(ids[l]);
        const mel = document.getElementById(moralIds[l]);
        if (el) { el.classList.toggle('hidden', l !== lang); }
        if (mel) { mel.classList.toggle('hidden', l !== lang); }
    });
}

function formatText(text) {
    if (!text) return '<p style="opacity:0.5; font-style:italic;">Translation not available.</p>';
    return text.split('\n').filter(p => p.trim()).map(p => `<p>${escHtml(p)}</p>`).join('');
}

function escHtml(str) {
    if (!str) return '';
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

// ── PAGE NAVIGATION ────────────────────────────────────────
function updatePageNav() {
    const prev = document.getElementById('prevBtn');
    const next = document.getElementById('nextBtn');
    const counter = document.getElementById('pageCounter');
    const prevTitle = document.getElementById('prevTitle');
    const nextTitle = document.getElementById('nextTitle');

    prev.disabled = currentStory === 0;
    next.disabled = currentStory === stories.length - 1;

    prevTitle.textContent = currentStory > 0 ? stories[currentStory - 1].title_en : '';
    nextTitle.textContent = currentStory < stories.length - 1 ? stories[currentStory + 1].title_en : '';
    counter.textContent = `${currentStory + 1} of ${stories.length}`;
}

function prevStory(ev) {
    if (ev) ev.stopPropagation();
    goToStory(currentStory - 1, 'prev');
}
function nextStory(ev) {
    if (ev) ev.stopPropagation();
    goToStory(currentStory + 1, 'next');
}

function updateProgress() {
    const pct = ((currentStory + 1) / stories.length) * 100;
    document.getElementById('progressBar').style.width = pct + '%';
}

// ── LIGHTBOX ───────────────────────────────────────────────
function openLightbox(src) {
    document.getElementById('lightboxImg').src = src;
    document.getElementById('lightbox').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    document.getElementById('lightbox').classList.remove('active');
    document.body.style.overflow = '';
}

// ── KEYBOARD NAVIGATION ───────────────────────────────────
document.addEventListener('keydown', e => {
    if (currentView !== 'kingdom' && currentView !== 'eden') return;
    if (e.key === 'ArrowRight' || e.key === 'ArrowDown') { e.preventDefault(); nextStory(); }
    if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') { e.preventDefault(); prevStory(); }
    if (e.key === 'Escape') {
        if (document.getElementById('lightbox').classList.contains('active')) {
            closeLightbox();
        } else {
            showShelf();
        }
    }
});

// ── BACK TO TOP ───────────────────────────────────────────
window.addEventListener('scroll', () => {
    const btn = document.getElementById('backToTop');
    btn.classList.toggle('visible', window.scrollY > 400);
});

// ── STARTUP ───────────────────────────────────────────────
(function init() {
    const params = new URLSearchParams(window.location.search);
    const view = params.get('view');
    const book = parseInt(params.get('book') || '0');

    if (view === 'kingdom') {
        openEdition('kingdom');
        if (book > 0 && book <= stories.length) goToStory(book - 1, 'jump');
    } else if (view === 'eden' && isFamily) {
        openEdition('eden');
        if (book > 0 && book <= stories.length) goToStory(book - 1, 'jump');
    } else if (view === 'papas' && isFamily) {
        openEdition('papas');
    }
})();
</script>

</body>
</html>