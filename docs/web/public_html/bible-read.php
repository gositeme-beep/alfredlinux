<?php
/**
 * Authorized King Jesus Version (Perez Family Edition) — Bible reader on alfredlinux.com
 * URL: /bible/read or /bible/read/Genesis/1
 * Uses the shared Bible library — One Bible, many altars.
 */

require_once '/home/gositeme/shared/bible/bible-data.php';
require_once '/home/gositeme/shared/bible/bible-styles.php';
require_once '/home/gositeme/shared/bible/bible-reader-component.php';

// Language support
$lang = 'en';
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'fr', 'he'])) {
    $lang = $_GET['lang'];
} elseif (isset($_COOKIE['akjv_lang'])) {
    $lang = $_COOKIE['akjv_lang'];
}
$lang = akjv_lang($lang);
if (isset($_GET['lang'])) {
    setcookie('akjv_lang', $lang, time() + 86400 * 365, '/', '.alfredlinux.com', true, true);
}

// Parse URL: /bible/read/BookName/Chapter
$path = $_SERVER['REQUEST_URI'] ?? '';
$path = strtok($path, '?');
$parts = explode('/', trim($path, '/'));
$requestedBook = urldecode($parts[2] ?? 'Genesis');
$requestedChapter = max(1, (int)($parts[3] ?? 1));
$requestedBook = preg_replace('/[^a-zA-Z0-9 ()\-]/', '', $requestedBook);

$ctx = akjv_reader_context($requestedBook, $requestedChapter, $lang);
$bookDisplay = akjv_book_name($ctx['currentBook'], $lang);
$pageTitle = "{$bookDisplay} {$ctx['chapter']} — King Jesus Version · Perez Family Edition";
?>
<!DOCTYPE html>
<html lang="<?= $lang === 'he' ? 'he' : ($lang === 'fr' ? 'fr' : 'en') ?>"<?= $lang === 'he' ? ' dir="rtl"' : '' ?>>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?></title>
<meta name="description" content="Read <?= htmlspecialchars($bookDisplay) ?> Chapter <?= $ctx['chapter'] ?> in the Authorized King Jesus Version (AKJV) — Perez Family Edition. 94 books, 39,482 verses. The Word of God stands forever.">
<meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
<meta property="og:url" content="https://alfredlinux.com/bible/read/<?= urlencode($ctx['currentBook']['book_name']) ?>/<?= $ctx['chapter'] ?>">
<meta property="og:image" content="https://alfredlinux.com/og-image.png">
<meta property="og:type" content="website">
<link rel="canonical" href="https://alfredlinux.com/bible/read/<?= urlencode($ctx['currentBook']['book_name']) ?>/<?= $ctx['chapter'] ?>">
<link rel="icon" href="/favicon.ico">
<link rel="stylesheet" href="/assets/css/nav.css">
<style><?= akjv_styles_reader() ?></style>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/img/favicon/favicon-16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/img/favicon/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#D4AF37">
</head>
<body style="background:#0a0a0f;color:#e0e0e0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',system-ui,sans-serif;min-height:100vh;">

<?php $currentPage = 'bible'; include __DIR__ . '/includes/nav.php'; ?>

<div style="text-align:right;max-width:900px;margin:1rem auto .5rem;padding:0 1rem;display:flex;justify-content:flex-end;gap:6px;">
    <a href="?lang=en" style="padding:4px 10px;border-radius:6px;font-size:.78rem;font-weight:700;text-decoration:none;<?= $lang==='en' ? 'background:rgba(255,215,0,.15);border:1px solid var(--akjv-gold,#ffd700);color:#ffd700' : 'background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);color:#888' ?>">EN</a>
    <a href="?lang=fr" style="padding:4px 10px;border-radius:6px;font-size:.78rem;font-weight:700;text-decoration:none;<?= $lang==='fr' ? 'background:rgba(255,215,0,.15);border:1px solid var(--akjv-gold,#ffd700);color:#ffd700' : 'background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);color:#888' ?>">FR</a>
    <a href="?lang=he" style="padding:4px 10px;border-radius:6px;font-size:.78rem;font-weight:700;text-decoration:none;<?= $lang==='he' ? 'background:rgba(255,215,0,.15);border:1px solid var(--akjv-gold,#ffd700);color:#ffd700' : 'background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);color:#888' ?>">עב</a>
</div>

<?php akjv_render_reader($ctx, '/bible'); ?>

<footer style="text-align:center;padding:2rem 1.5rem;color:#94a3b8;font-size:.85rem;border-top:1px solid rgba(250,204,21,0.08);">
    <p style="font-size:1rem;color:#facc15;font-weight:700;margin-bottom:0.75rem;">&ldquo;The grass withereth, the flower fadeth: but the word of our God shall stand for ever.&rdquo; &mdash; Isaiah 40:8</p>
    <p>&copy; <?= date('Y') ?> <a href="https://gositeme.com" style="color:#facc15;text-decoration:none;">GoSiteMe Inc.</a> &mdash; AKJV Bible &middot; Perez Family Edition &middot; <span style="color:#d97706;">Soli Deo Gloria</span></p>
    <p style="margin-top:0.5rem;font-size:0.78rem;"><a href="https://lavocat.ca/journal?read=9&lang=en" style="color:#fde68a;text-decoration:none;">Commander&rsquo;s Journal</a> &middot; <a href="https://gositeme.com/sovereignty" style="color:#fde68a;text-decoration:none;">Sovereignty Declarations</a> &middot; <a href="https://gositeme.com/bible/read/isaiah/40" style="color:#fde68a;text-decoration:none;">Isaiah 40 &mdash; AKJV</a></p>
</footer>

<?php include __DIR__ . "/includes/shabbat-banner.php"; ?>
</body>
</html>
