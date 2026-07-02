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
    <style>
        :root { --bg:#06060b; --text:#e0e0e0; --muted:#9ca3af; --accent:#6366f1; --accent2:#a5b4fc; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:Inter,system-ui,sans-serif; background:var(--bg); color:var(--text); min-height:100vh; }
        .wrap { max-width:720px; margin:0 auto; padding:3rem 1.5rem 4rem; }
        h1 { font-size:clamp(1.5rem,4vw,2rem); margin-bottom:.75rem; }
        p { color:var(--muted); line-height:1.7; margin-bottom:1rem; }
        .meta { font-size:.85rem; color:var(--muted); padding:1rem; border:1px solid rgba(255,255,255,.08); border-radius:12px; margin:1.5rem 0; }
        .links { display:flex; flex-wrap:wrap; gap:.75rem; margin-top:1.5rem; }
        .links a { display:inline-flex; align-items:center; padding:.55rem 1.1rem; border-radius:8px; background:rgba(99,102,241,.2); color:var(--accent2); text-decoration:none; font-weight:600; font-size:.9rem; }
        .links a:hover { background:rgba(99,102,241,.35); }
    </style>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/img/favicon/favicon-16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/img/favicon/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#D4AF37">
</head>
<body>
<?php $currentPage = 'member_lounge'; include __DIR__ . '/includes/nav.php'; ?>
<div class="wrap">
    <h1>Member lounge</h1>
    <p>You are signed in with your GoSiteMe account. This space is for Alfred Linux members: contributor links, repos, and community tools.</p>
    <div class="meta">Account ID <strong><?= $cid ?></strong><?= $email !== '' ? ' · ' . $email : '' ?></div>
    <div class="links">
        <a href="/community">Community hub</a>
        <a href="/developers">Developers</a>
        <a href="/forge/explore/repos">GoForge repos</a>
        <a href="/download">Download</a>
    </div>
</div>

<footer style="text-align:center;padding:1.5rem;color:#94a3b8;font-size:.85rem;border-top:1px solid rgba(255,255,255,0.06);">
    <p style="font-style:italic;margin:0 0 0.5rem;">&ldquo;The grass withereth, the flower fadeth: but the word of our God shall stand for ever.&rdquo; &mdash; <a href="https://gositeme.com/bible/read/isaiah/40" style="color:#facc15;text-decoration:none;">Isaiah 40:8</a> (AKJV)</p>
    <p>&copy; <?= date('Y') ?> <a href="https://gositeme.com" style="color:#6366f1;text-decoration:none;">GoSiteMe Inc.</a> &mdash; Alfred Linux &middot; Open Source (AGPL-3.0)</p>
</footer>
<?php include __DIR__ . "/includes/shabbat-banner.php"; ?>
</body>
</html>
