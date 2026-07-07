<?php
/**
 * Alfred Linux — signed-in members only (GoSiteMe SSO).
 */
require_once __DIR__ . '/includes/al-session.inc.php';

if (!$al_user_logged_in) {
    $returnPath = '/member-lounge';
    $bridgePath = '/api/sso-bridge.php?target=alfred&redirect=' . rawurlencode($returnPath);
    header('Location: https://gositeme.com/login.php?return=' . rawurlencode($bridgePath), true, 302);
    exit;
}

$email = htmlspecialchars((string)($_SESSION['email'] ?? $_SESSION['client_email'] ?? ''), ENT_QUOTES, 'UTF-8');
$cid = (int)($_SESSION['client_id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Lounge — Alfred Linux</title>
    <meta name="robots" content="noindex,nofollow">
    <link rel="stylesheet" href="/assets/css/nav.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #030014;
            --text: #f3f4f6;
            --muted: #9ca3af;
            --accent: #D4AF37;
            --accent-glow: rgba(212, 175, 55, 0.4);
            --card-bg: rgba(255, 255, 255, 0.03);
            --card-border: rgba(255, 255, 255, 0.08);
            --cyber-blue: #0ea5e9;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
            position: relative;
        }

        /* Animated Background Mesh */
        .bg-mesh {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            z-index: -1;
            background: 
                radial-gradient(circle at 15% 50%, rgba(14, 165, 233, 0.15), transparent 25%),
                radial-gradient(circle at 85% 30%, rgba(212, 175, 55, 0.15), transparent 25%);
            animation: pulse-glow 15s ease-in-out infinite alternate;
        }

        @keyframes pulse-glow {
            0% { transform: scale(1); opacity: 0.8; }
            100% { transform: scale(1.1); opacity: 1; }
        }

        .lounge-container {
            flex: 1;
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
            padding: 4rem 1.5rem;
            animation: fade-in-up 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            opacity: 0;
            transform: translateY(20px);
        }

        @keyframes fade-in-up {
            to { opacity: 1; transform: translateY(0); }
        }

        .lounge-header {
            text-align: center;
            margin-bottom: 3.5rem;
            position: relative;
        }

        .lounge-header h1 {
            font-family: 'Outfit', sans-serif;
            font-size: clamp(2.5rem, 6vw, 4rem);
            font-weight: 800;
            background: linear-gradient(135deg, #fff 0%, #a1a1aa 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
            letter-spacing: -0.02em;
        }

        .lounge-header p {
            font-size: 1.15rem;
            color: var(--muted);
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .user-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            background: rgba(212, 175, 55, 0.1);
            border: 1px solid var(--accent-glow);
            padding: 0.5rem 1.25rem;
            border-radius: 99px;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--accent);
            margin-top: 1.5rem;
            box-shadow: 0 0 20px var(--accent-glow);
        }

        .user-badge .uid {
            background: var(--accent);
            color: #000;
            padding: 0.15rem 0.6rem;
            border-radius: 99px;
            font-size: 0.8rem;
            font-weight: 800;
        }

        /* Glassmorphism Grid */
        .portal-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .portal-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 20px;
            padding: 2rem;
            text-decoration: none;
            color: var(--text);
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            display: flex;
            flex-direction: column;
            gap: 1rem;
            z-index: 1;
        }

        .portal-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(180deg, rgba(255,255,255,0.05) 0%, transparent 100%);
            z-index: -1;
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .portal-card:hover {
            transform: translateY(-8px) scale(1.02);
            border-color: rgba(255,255,255,0.2);
            box-shadow: 0 20px 40px rgba(0,0,0,0.4), 0 0 30px rgba(14, 165, 233, 0.1);
        }

        .portal-card:hover::before {
            opacity: 1;
        }

        .card-icon {
            width: 54px;
            height: 54px;
            border-radius: 14px;
            background: rgba(255,255,255,0.05);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            border: 1px solid rgba(255,255,255,0.1);
            transition: transform 0.4s ease, background 0.4s ease;
        }

        .portal-card:hover .card-icon {
            transform: scale(1.1) rotate(-5deg);
            background: rgba(14, 165, 233, 0.2);
            border-color: var(--cyber-blue);
        }
        
        .card-icon.gold {
            background: rgba(212, 175, 55, 0.1);
        }
        .portal-card:hover .card-icon.gold {
            background: rgba(212, 175, 55, 0.25);
            border-color: var(--accent);
        }

        .card-content h3 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            letter-spacing: 0.01em;
        }

        .card-content p {
            color: var(--muted);
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .card-arrow {
            margin-top: auto;
            align-self: flex-end;
            color: var(--muted);
            transition: all 0.3s ease;
            opacity: 0.5;
        }

        .portal-card:hover .card-arrow {
            color: #fff;
            opacity: 1;
            transform: translateX(5px);
        }

        /* Footer override */
        footer {
            text-align: center;
            padding: 2rem 1.5rem;
            color: #64748b;
            font-size: 0.85rem;
            border-top: 1px solid rgba(255,255,255,0.05);
            background: rgba(0,0,0,0.2);
            backdrop-filter: blur(10px);
        }

        .footer-verse {
            font-style: italic;
            margin-bottom: 0.75rem;
        }

        .footer-verse a {
            color: var(--accent);
            text-decoration: none;
            transition: color 0.2s ease;
        }
        
        .footer-verse a:hover { color: #fff; }

        .footer-credits a {
            color: var(--cyber-blue);
            text-decoration: none;
        }

        @media (max-width: 768px) {
            .lounge-container { padding: 3rem 1.25rem; }
            .portal-card { padding: 1.5rem; }
        }
    </style>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/img/favicon/favicon-16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/img/favicon/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#D4AF37">
</head>
<body>
    <div class="bg-mesh"></div>
    
    <?php $currentPage = 'member_lounge'; include __DIR__ . '/includes/nav.php'; ?>
    
    <div class="lounge-container">
        <header class="lounge-header">
            <h1>Command Center</h1>
            <p>Welcome to the inner sanctum. Access exclusive Alfred Linux development tools, secure downloads, and the global contributor network.</p>
            <div class="user-badge">
                <span class="uid">UID <?= $cid ?></span>
                <?= $email !== '' ? $email : 'Authenticated' ?>
            </div>
        </header>

        <div class="portal-grid">
            <a href="/download" class="portal-card">
                <div class="card-icon gold">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                </div>
                <div class="card-content">
                    <h3>Secure Downloads</h3>
                    <p>Access the latest ISO builds, AppImages, and verified binaries via the WebSeed global torrent network.</p>
                </div>
                <div class="card-arrow">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                </div>
            </a>

            <a href="/forge/explore/repos" class="portal-card">
                <div class="card-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 14.66V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h5.34"></path><polygon points="18 2 22 6 12 16 8 16 8 12 18 2"></polygon></svg>
                </div>
                <div class="card-content">
                    <h3>GoForge Repositories</h3>
                    <p>Contribute to the core source code. Explore open repositories, submit pull requests, and audit the OS architecture.</p>
                </div>
                <div class="card-arrow">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                </div>
            </a>

            <a href="/community" class="portal-card">
                <div class="card-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                </div>
                <div class="card-content">
                    <h3>Community Hub</h3>
                    <p>Engage with developers, join specialized working groups, and participate in technical governance discussions.</p>
                </div>
                <div class="card-arrow">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                </div>
            </a>

            <a href="/developers" class="portal-card">
                <div class="card-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>
                </div>
                <div class="card-content">
                    <h3>Developer Documentation</h3>
                    <p>Read the SDK guides, explore API endpoints, and review the Alfred Linux architectural whitepapers.</p>
                </div>
                <div class="card-arrow">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
                </div>
            </a>
        </div>
    </div>

    <footer>
        <p class="footer-verse">&ldquo;The grass withereth, the flower fadeth: but the word of our God shall stand for ever.&rdquo; &mdash; <a href="https://gositeme.com/bible/read/isaiah/40">Isaiah 40:8</a> (AKJV)</p>
        <p class="footer-credits">&copy; <?= date('Y') ?> <a href="https://gositeme.com">GoSiteMe Inc.</a> &mdash; Alfred Linux &middot; Open Source (KCL-1.0)</p>
    </footer>
    
    <?php include __DIR__ . "/includes/shabbat-banner.php"; ?>
</body>
</html>
