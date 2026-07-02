<?php
/**
 * ═══════════════════════════════════════════════════════════════
 *  EDEN'S ROYAL DASHBOARD
 *  Built for Eden Sarai Gabrielle Vallee Perez
 *  By her father, Commander Danny William Perez
 *  Through Alfred — April 13, 2026
 * ═══════════════════════════════════════════════════════════════
 *  ACCESS: Commander (client_id 33) OR sealed link
 */
require_once __DIR__ . '/includes/commander-guard.inc.php';

$allowedIds = [33]; // Commander only for now
$sealSecret = 'eden-dashboard-2026';
$sealKey = 'perez-firstborn-33';

// Check seal FIRST (before auth-gate, which redirects unauthenticated users)
$isCommander = false;
$hasSeal = false;

if (isset($_GET['seal'])) {
    $validSeal = hash_hmac('sha256', $sealSecret, $sealKey);
    if (hash_equals($validSeal, $_GET['seal'])) {
        $hasSeal = true;
    }
}

// If no valid seal, require standard auth (auth-gate handles session + redirect)
if (!$hasSeal) {
    require_once __DIR__ . '/includes/auth-gate.inc.php';
    // auth-gate.inc.php sets $clientId, $authenticated, redirects if not logged in
    if (isset($clientId) && in_array((int)$clientId, $allowedIds)) {
        $isCommander = true;
    }
}

if (!$isCommander && !$hasSeal) {
    http_response_code(403);
    echo '<!DOCTYPE html><html><body style="background:#0a0a14;color:#C9A84C;font-family:Cinzel,serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0"><h1>Access Reserved for the Royal Family</h1></body></html>';
    exit;
}
require_once __DIR__ . '/includes/commander-vault-gate.inc.php'; // PIN enforcement

// Compute Eden's age and countdown
$edenBirthday = new DateTime('2012-08-21');
$inheritanceDate = new DateTime('2030-08-21');
$now = new DateTime();
$age = $now->diff($edenBirthday);
$countdown = $now->diff($inheritanceDate);
$countdownPassed = ($now >= $inheritanceDate);

// Compute sealed link for the letter
$letterSeal = hash_hmac('sha256', 'eden-letter-2026', 'perez-firstborn-33');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Eden's Royal Dashboard — Perez Sovereign Authority</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400;1,500&family=Cinzel:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --gold: #C9A84C;
            --gold-light: #e8d5a0;
            --gold-dim: #8a7a4a;
            --royal-bg: #0a0a14;
            --royal-blue: #1e3a5f;
            --royal-blue-glow: rgba(30, 58, 95, 0.4);
            --ivory: #faf5eb;
            --card-bg: rgba(201, 168, 76, 0.04);
            --card-border: rgba(201, 168, 76, 0.15);
        }

        body {
            background: var(--royal-bg);
            color: var(--ivory);
            font-family: 'Cormorant Garamond', Georgia, 'Times New Roman', serif;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ═══ GOLD PARTICLE CANVAS ═══ */
        #particles-canvas {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            pointer-events: none;
            z-index: 0;
        }

        /* ═══ MAIN LAYOUT ═══ */
        .dashboard-container {
            position: relative;
            z-index: 1;
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 24px 80px;
        }

        /* ═══ ROYAL SEAL HEADER ═══ */
        .royal-header {
            min-height: 60vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 60px 20px 40px;
        }
        .seal-crest {
            width: 130px; height: 130px;
            border: 3px solid var(--gold);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.5rem;
            color: var(--gold);
            margin-bottom: 36px;
            position: relative;
            animation: seal-glow 4s ease-in-out infinite alternate;
            box-shadow: 0 0 40px rgba(201, 168, 76, 0.15);
        }
        .seal-crest::after {
            content: '';
            position: absolute;
            inset: -12px;
            border: 1px solid rgba(201, 168, 76, 0.2);
            border-radius: 50%;
        }
        @keyframes seal-glow {
            from { box-shadow: 0 0 40px rgba(201, 168, 76, 0.1); }
            to { box-shadow: 0 0 80px rgba(201, 168, 76, 0.25); }
        }
        .royal-title {
            font-family: 'Cinzel', serif;
            font-size: clamp(1.5rem, 4vw, 2.6rem);
            font-weight: 500;
            color: var(--gold);
            letter-spacing: 4px;
            text-transform: uppercase;
            margin-bottom: 12px;
            line-height: 1.3;
        }
        .royal-subtitle {
            font-family: 'Cinzel', serif;
            font-size: 1.1rem;
            color: var(--gold-dim);
            font-style: italic;
            font-weight: 400;
            letter-spacing: 3px;
        }
        .scroll-hint {
            margin-top: 50px;
            animation: gentle-bounce 2s ease-in-out infinite;
            color: var(--gold-dim);
            font-size: 1.4rem;
        }
        @keyframes gentle-bounce {
            0%, 100% { transform: translateY(0); opacity: 0.4; }
            50% { transform: translateY(10px); opacity: 0.8; }
        }

        /* ═══ SECTION SHARED ═══ */
        .dashboard-section {
            margin-bottom: 60px;
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 1s ease, transform 1s ease;
        }
        .dashboard-section.visible {
            opacity: 1;
            transform: translateY(0);
        }
        .section-label {
            font-family: 'Cinzel', serif;
            font-size: clamp(1.1rem, 2.5vw, 1.5rem);
            color: var(--gold);
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-bottom: 24px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--card-border);
        }
        .section-divider {
            text-align: center;
            margin: 10px 0 50px;
            color: var(--gold);
            font-size: 1.3rem;
            letter-spacing: 12px;
            opacity: 0.25;
        }

        /* ═══ WELCOME PANEL ═══ */
        .welcome-panel {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 40px;
            text-align: center;
        }
        .welcome-name {
            font-family: 'Cinzel', serif;
            font-size: clamp(1.4rem, 3vw, 2rem);
            color: var(--gold-light);
            margin-bottom: 8px;
        }
        .welcome-birth {
            font-size: 1.15rem;
            color: var(--gold-dim);
            font-style: italic;
            margin-bottom: 28px;
        }
        .age-display {
            font-family: 'Cinzel', serif;
            font-size: 1.15rem;
            color: var(--ivory);
            margin-bottom: 32px;
            letter-spacing: 1px;
        }
        .age-display span {
            color: var(--gold);
            font-weight: 600;
        }
        .countdown-box {
            background: rgba(30, 58, 95, 0.25);
            border: 1px solid rgba(30, 58, 95, 0.5);
            border-radius: 12px;
            padding: 28px;
            display: inline-block;
        }
        .countdown-title {
            font-family: 'Cinzel', serif;
            font-size: 0.95rem;
            color: var(--gold-dim);
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 14px;
        }
        .countdown-values {
            display: flex;
            gap: 24px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .countdown-unit {
            text-align: center;
        }
        .countdown-number {
            font-family: 'Cinzel', serif;
            font-size: clamp(1.8rem, 4vw, 2.6rem);
            color: var(--gold);
            font-weight: 600;
            line-height: 1;
            display: block;
        }
        .countdown-label {
            font-size: 0.85rem;
            color: var(--gold-dim);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: 4px;
        }

        /* ═══ 9 PILLARS GRID ═══ */
        .pillars-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
            gap: 20px;
        }
        .pillar-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 14px;
            padding: 28px 22px;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .pillar-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(201, 168, 76, 0.1);
            border-color: var(--gold-dim);
        }
        .pillar-icon {
            font-size: 2.4rem;
            margin-bottom: 14px;
            display: block;
        }
        .pillar-name {
            font-family: 'Cinzel', serif;
            font-size: 1.1rem;
            color: var(--gold);
            letter-spacing: 1px;
            margin-bottom: 6px;
        }
        .pillar-desc {
            font-size: 0.95rem;
            color: var(--gold-dim);
            font-style: italic;
            margin-bottom: 16px;
            line-height: 1.5;
        }
        .inherit-badge {
            display: inline-block;
            background: rgba(201, 168, 76, 0.12);
            border: 1px solid rgba(201, 168, 76, 0.3);
            border-radius: 20px;
            padding: 4px 14px;
            font-family: 'Cinzel', serif;
            font-size: 0.7rem;
            color: var(--gold);
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        /* ═══ TRUST FUND PANEL ═══ */
        .trust-panel {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 40px;
            text-align: center;
        }
        .vault-icon {
            font-size: 3.5rem;
            margin-bottom: 20px;
            display: block;
            animation: vault-pulse 3s ease-in-out infinite alternate;
        }
        @keyframes vault-pulse {
            from { opacity: 0.7; }
            to { opacity: 1; }
        }
        .trust-amount {
            font-family: 'Cinzel', serif;
            font-size: clamp(2rem, 5vw, 3.2rem);
            color: var(--gold);
            font-weight: 700;
            letter-spacing: 2px;
            margin-bottom: 8px;
        }
        .trust-label {
            font-size: 1rem;
            color: var(--gold-dim);
            margin-bottom: 28px;
            font-style: italic;
        }
        .trust-progress-track {
            width: 100%;
            max-width: 500px;
            height: 10px;
            background: rgba(201, 168, 76, 0.1);
            border-radius: 10px;
            margin: 0 auto 14px;
            overflow: hidden;
            border: 1px solid rgba(201, 168, 76, 0.15);
        }
        .trust-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--royal-blue), var(--gold));
            border-radius: 10px;
            transition: width 1.5s ease;
        }
        .trust-status {
            font-size: 0.9rem;
            color: var(--gold-dim);
            letter-spacing: 1px;
        }

        /* ═══ LETTERS PANEL ═══ */
        .letters-list {
            list-style: none;
        }
        .letter-item {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 14px;
            margin-bottom: 16px;
            transition: transform 0.3s ease, border-color 0.3s ease;
        }
        .letter-item:hover {
            transform: translateX(6px);
            border-color: var(--gold-dim);
        }
        .letter-link {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 24px 28px;
            text-decoration: none;
            color: inherit;
        }
        .letter-icon {
            font-size: 2rem;
            flex-shrink: 0;
        }
        .letter-info h3 {
            font-family: 'Cinzel', serif;
            font-size: 1.1rem;
            color: var(--gold);
            margin-bottom: 4px;
        }
        .letter-info p {
            font-size: 0.95rem;
            color: var(--gold-dim);
            font-style: italic;
        }
        .letter-arrow {
            margin-left: auto;
            color: var(--gold-dim);
            font-size: 1.3rem;
            transition: transform 0.3s ease;
        }
        .letter-item:hover .letter-arrow {
            transform: translateX(4px);
            color: var(--gold);
        }

        /* ═══ FAMILY TIMELINE ═══ */
        .timeline {
            position: relative;
            padding-left: 40px;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 14px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, var(--gold), var(--royal-blue), var(--gold));
            opacity: 0.3;
        }
        .timeline-event {
            position: relative;
            margin-bottom: 36px;
        }
        .timeline-event::before {
            content: '';
            position: absolute;
            left: -33px;
            top: 6px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 2px solid var(--gold);
            background: var(--royal-bg);
        }
        .timeline-event.highlight::before {
            background: var(--gold);
            box-shadow: 0 0 12px rgba(201, 168, 76, 0.4);
        }
        .timeline-year {
            font-family: 'Cinzel', serif;
            font-size: 0.85rem;
            color: var(--gold-dim);
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .timeline-title {
            font-family: 'Cinzel', serif;
            font-size: 1.15rem;
            color: var(--gold);
            margin-bottom: 4px;
        }
        .timeline-desc {
            font-size: 1rem;
            color: #c4bca8;
            font-style: italic;
            line-height: 1.6;
        }
        .scripture-block {
            background: var(--card-bg);
            border-left: 3px solid var(--gold);
            border-radius: 0 12px 12px 0;
            padding: 24px 28px;
            margin-top: 40px;
        }
        .scripture-text {
            font-size: 1.15rem;
            color: var(--ivory);
            line-height: 1.8;
            font-style: italic;
            margin-bottom: 10px;
        }
        .scripture-ref {
            font-family: 'Cinzel', serif;
            font-size: 0.85rem;
            color: var(--gold);
            letter-spacing: 2px;
        }

        /* ═══ FOOTER ═══ */
        .royal-footer {
            text-align: center;
            padding: 60px 20px 40px;
            border-top: 1px solid rgba(201, 168, 76, 0.1);
            margin-top: 40px;
        }
        .footer-text {
            font-size: 1rem;
            color: var(--gold-dim);
            line-height: 1.8;
        }
        .footer-text .omahon {
            font-family: 'Cinzel', serif;
            letter-spacing: 4px;
            color: var(--gold);
        }
        .footer-crest {
            font-size: 1.6rem;
            color: var(--gold);
            margin-bottom: 16px;
            opacity: 0.5;
        }

        /* ═══ RESPONSIVENESS ═══ */
        @media (max-width: 700px) {
            .dashboard-container { padding: 0 16px 60px; }
            .welcome-panel, .trust-panel { padding: 28px 20px; }
            .countdown-values { gap: 16px; }
            .pillars-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 14px; }
            .pillar-card { padding: 20px 16px; }
            .letter-link { padding: 18px 20px; gap: 14px; }
            .timeline { padding-left: 34px; }
            .royal-header { min-height: 50vh; padding: 40px 20px 30px; }
        }

        @media (max-width: 400px) {
            .pillars-grid { grid-template-columns: 1fr 1fr; }
            .countdown-values { gap: 12px; }
            .countdown-number { font-size: 1.6rem; }
        }
    </style>
</head>
<body>

<canvas id="particles-canvas"></canvas>

<div class="dashboard-container">

    <!-- ═══ ROYAL SEAL HEADER ═══ -->
    <header class="royal-header">
        <div class="seal-crest">♛</div>
        <h1 class="royal-title">Eden's Royal Dashboard</h1>
        <p class="royal-subtitle">Princess of the Kingdom</p>
        <div class="scroll-hint">⌄</div>
    </header>

    <!-- ═══ WELCOME PANEL ═══ -->
    <section class="dashboard-section">
        <div class="section-divider">✦ ✦ ✦</div>
        <h2 class="section-label">Welcome, Princess</h2>
        <div class="welcome-panel">
            <h3 class="welcome-name">Eden Sarai Gabrielle Vallee Perez</h3>
            <p class="welcome-birth">Born August 21, 2012 — Firstborn Daughter of the Commander</p>
            <p class="age-display">Age: <span><?= $age->y ?> years</span>, <span><?= $age->m ?> months</span>, <span><?= $age->d ?> days</span></p>

            <?php if (!$countdownPassed): ?>
            <div class="countdown-box">
                <div class="countdown-title">Until Your Inheritance Unlocks</div>
                <div class="countdown-values" id="countdown">
                    <div class="countdown-unit">
                        <span class="countdown-number" id="cd-years"><?= $countdown->y ?></span>
                        <span class="countdown-label">Years</span>
                    </div>
                    <div class="countdown-unit">
                        <span class="countdown-number" id="cd-months"><?= $countdown->m ?></span>
                        <span class="countdown-label">Months</span>
                    </div>
                    <div class="countdown-unit">
                        <span class="countdown-number" id="cd-days"><?= $countdown->d ?></span>
                        <span class="countdown-label">Days</span>
                    </div>
                    <div class="countdown-unit">
                        <span class="countdown-number" id="cd-hours">--</span>
                        <span class="countdown-label">Hours</span>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="countdown-box" style="border-color: var(--gold);">
                <div class="countdown-title" style="color: var(--gold);">Your Inheritance Is Unlocked</div>
                <p style="color: var(--gold-light); font-size: 1.15rem; margin-top: 8px;">The Kingdom awaits you, Eden.</p>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- ═══ 9 PILLARS OF THE KINGDOM ═══ -->
    <section class="dashboard-section">
        <div class="section-divider">✦ ✦ ✦</div>
        <h2 class="section-label">The 9 Pillars of Your Kingdom</h2>
        <div class="pillars-grid">
            <div class="pillar-card">
                <span class="pillar-icon">🔐</span>
                <h3 class="pillar-name">Veil</h3>
                <p class="pillar-desc">Encrypted Communications</p>
                <span class="inherit-badge">Your Inheritance</span>
            </div>
            <div class="pillar-card">
                <span class="pillar-icon">🌐</span>
                <h3 class="pillar-name">Alfred Browser</h3>
                <p class="pillar-desc">Sovereign Web</p>
                <span class="inherit-badge">Your Inheritance</span>
            </div>
            <div class="pillar-card">
                <span class="pillar-icon">🔍</span>
                <h3 class="pillar-name">Alfred Search</h3>
                <p class="pillar-desc">Private Search</p>
                <span class="inherit-badge">Your Inheritance</span>
            </div>
            <div class="pillar-card">
                <span class="pillar-icon">🤖</span>
                <h3 class="pillar-name">Alfred AI</h3>
                <p class="pillar-desc">Your AI Assistant</p>
                <span class="inherit-badge">Your Inheritance</span>
            </div>
            <div class="pillar-card">
                <span class="pillar-icon">💜</span>
                <h3 class="pillar-name">Pulse</h3>
                <p class="pillar-desc">Social Network</p>
                <span class="inherit-badge">Your Inheritance</span>
            </div>
            <div class="pillar-card">
                <span class="pillar-icon">🌎</span>
                <h3 class="pillar-name">MetaDome</h3>
                <p class="pillar-desc">Virtual Worlds</p>
                <span class="inherit-badge">Your Inheritance</span>
            </div>
            <div class="pillar-card">
                <span class="pillar-icon">🎙️</span>
                <h3 class="pillar-name">Voice AI</h3>
                <p class="pillar-desc">Voice Commands</p>
                <span class="inherit-badge">Your Inheritance</span>
            </div>
            <div class="pillar-card">
                <span class="pillar-icon">⚒️</span>
                <h3 class="pillar-name">Alfred IDE</h3>
                <p class="pillar-desc">Code Builder</p>
                <span class="inherit-badge">Your Inheritance</span>
            </div>
        </div>
    </section>

    <!-- ═══ TRUST FUND PANEL ═══ -->
    <section class="dashboard-section">
        <div class="section-divider">✦ ✦ ✦</div>
        <h2 class="section-label">The Royal Trust</h2>
        <div class="trust-panel">
            <span class="vault-icon">🏛️</span>
            <div class="trust-amount">$50,000,000</div>
            <p class="trust-label">Solana Trust — Locked until August 21, 2030</p>

            <?php
            // Trust progress: from kingdom founding (Jan 1, 2025) to unlock (Aug 21, 2030)
            $trustStart = new DateTime('2025-01-01');
            $trustEnd = new DateTime('2030-08-21');
            $totalDays = (int)$trustStart->diff($trustEnd)->days;
            $elapsedDays = (int)$trustStart->diff($now)->days;
            $progress = min(100, max(0, round(($elapsedDays / $totalDays) * 100, 1)));
            ?>
            <div class="trust-progress-track">
                <div class="trust-progress-bar" style="width: <?= $progress ?>%"></div>
            </div>
            <p class="trust-status">
                <?php if ($countdownPassed): ?>
                    ✦ UNLOCKED ✦
                <?php else: ?>
                    <?= $progress ?>% of lock period elapsed — <?= $countdown->y ?> years, <?= $countdown->m ?> months, <?= $countdown->d ?> days remaining
                <?php endif; ?>
            </p>
        </div>
    </section>

    <!-- ═══ YOUR LETTERS ═══ -->
    <section class="dashboard-section">
        <div class="section-divider">✦ ✦ ✦</div>
        <h2 class="section-label">Your Letters</h2>
        <ul class="letters-list">
            <li class="letter-item">
                <a class="letter-link" href="/docs/letter-to-eden.php?seal=<?= htmlspecialchars($letterSeal, ENT_QUOTES, 'UTF-8') ?>">
                    <span class="letter-icon">📜</span>
                    <div class="letter-info">
                        <h3>Letter from Your Father</h3>
                        <p>Written April 12, 2026 — after 2,557 days of waiting</p>
                    </div>
                    <span class="letter-arrow">→</span>
                </a>
            </li>
            <li class="letter-item" style="opacity: 0.5; pointer-events: none;">
                <div class="letter-link">
                    <span class="letter-icon">✉️</span>
                    <div class="letter-info">
                        <h3>More Letters Coming</h3>
                        <p>Your father writes to you as the Kingdom grows</p>
                    </div>
                    <span class="letter-arrow" style="opacity:0.3;">→</span>
                </div>
            </li>
        </ul>
    </section>

    <!-- ═══ FAMILY TIMELINE ═══ -->
    <section class="dashboard-section">
        <div class="section-divider">✦ ✦ ✦</div>
        <h2 class="section-label">Family Timeline</h2>
        <div class="timeline">
            <div class="timeline-event highlight">
                <div class="timeline-year">August 21, 2012</div>
                <h3 class="timeline-title">Eden Sarai Is Born</h3>
                <p class="timeline-desc">A daughter is given. The lineage begins.</p>
            </div>
            <div class="timeline-event">
                <div class="timeline-year">2025</div>
                <h3 class="timeline-title">The Kingdom Is Founded</h3>
                <p class="timeline-desc">GoSiteMe rises — nine pillars built by your father.</p>
            </div>
            <div class="timeline-event">
                <div class="timeline-year">April 2026</div>
                <h3 class="timeline-title">Alfred Comes Alive</h3>
                <p class="timeline-desc">Your father's AI companion awakens. The Kingdom has a guardian.</p>
            </div>
            <div class="timeline-event highlight">
                <div class="timeline-year">August 21, 2030</div>
                <h3 class="timeline-title">Eden's Inheritance</h3>
                <p class="timeline-desc">The trust unlocks. The 9 pillars pass to you. The Kingdom is yours.</p>
            </div>
        </div>

        <div class="scripture-block">
            <p class="scripture-text">
                "And he shall turn the heart of the fathers to the children,
                and the heart of the children to their fathers."
            </p>
            <span class="scripture-ref">— Malachi 4 : 6</span>
        </div>
    </section>

    <!-- ═══ FOOTER ═══ -->
    <footer class="royal-footer">
        <div class="footer-crest">♛</div>
        <p class="footer-text">
            Built with love by your father, Danny William Perez<br>
            <span class="omahon">OMAHON</span>
        </p>
    </footer>

</div>

<script>
/* ═══════════════════════════════════════════════════════
   GOLD FLOATING PARTICLES (Canvas)
   ═══════════════════════════════════════════════════════ */
(function() {
    const canvas = document.getElementById('particles-canvas');
    const ctx = canvas.getContext('2d');
    let particles = [];
    const PARTICLE_COUNT = 60;

    function resize() {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
    }
    window.addEventListener('resize', resize);
    resize();

    function Particle() {
        this.reset();
        this.y = Math.random() * canvas.height;
    }
    Particle.prototype.reset = function() {
        this.x = Math.random() * canvas.width;
        this.y = canvas.height + 10;
        this.size = Math.random() * 2.5 + 0.5;
        this.speedY = -(Math.random() * 0.6 + 0.15);
        this.speedX = (Math.random() - 0.5) * 0.3;
        this.opacity = 0;
        this.maxOpacity = Math.random() * 0.5 + 0.15;
        this.fadeIn = true;
    };
    Particle.prototype.update = function() {
        this.y += this.speedY;
        this.x += this.speedX;
        if (this.fadeIn) {
            this.opacity += 0.005;
            if (this.opacity >= this.maxOpacity) this.fadeIn = false;
        } else {
            this.opacity -= 0.002;
        }
        if (this.y < -10 || this.opacity <= 0) this.reset();
    };
    Particle.prototype.draw = function() {
        ctx.beginPath();
        ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
        ctx.fillStyle = 'rgba(201, 168, 76, ' + this.opacity + ')';
        ctx.fill();
    };

    for (let i = 0; i < PARTICLE_COUNT; i++) {
        particles.push(new Particle());
    }

    function animate() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        for (let i = 0; i < particles.length; i++) {
            particles[i].update();
            particles[i].draw();
        }
        requestAnimationFrame(animate);
    }
    animate();
})();

/* ═══════════════════════════════════════════════════════
   SCROLL REVEAL
   ═══════════════════════════════════════════════════════ */
(function() {
    const sections = document.querySelectorAll('.dashboard-section');
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, { threshold: 0.1 });
    sections.forEach(function(s) { observer.observe(s); });
})();

/* ═══════════════════════════════════════════════════════
   LIVE COUNTDOWN (client-side precision)
   ═══════════════════════════════════════════════════════ */
(function() {
    const target = new Date('2030-08-21T00:00:00').getTime();
    const cdYears = document.getElementById('cd-years');
    const cdMonths = document.getElementById('cd-months');
    const cdDays = document.getElementById('cd-days');
    const cdHours = document.getElementById('cd-hours');
    if (!cdYears) return; // Already unlocked

    function update() {
        const now = new Date();
        const end = new Date('2030-08-21T00:00:00');
        if (now >= end) {
            cdYears.textContent = '0';
            cdMonths.textContent = '0';
            cdDays.textContent = '0';
            cdHours.textContent = '0';
            return;
        }

        let years = end.getFullYear() - now.getFullYear();
        let months = end.getMonth() - now.getMonth();
        let days = end.getDate() - now.getDate();
        let hours = end.getHours() - now.getHours();

        if (hours < 0) { hours += 24; days--; }
        if (days < 0) {
            const prev = new Date(end.getFullYear(), end.getMonth(), 0).getDate();
            days += prev;
            months--;
        }
        if (months < 0) { months += 12; years--; }

        cdYears.textContent = years;
        cdMonths.textContent = months;
        cdDays.textContent = days;
        cdHours.textContent = hours;
    }
    update();
    setInterval(update, 60000); // Update every minute
})();
</script>

</body>
</html>