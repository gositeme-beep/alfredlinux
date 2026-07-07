<?php
// daily-bread.php — Verse of the Day public endpoint (text/json/html/CLI).
// Logic lives in includes/daily-bread.inc.php so other endpoints can reuse it.

declare(strict_types=1);
require_once __DIR__ . '/includes/daily-bread.inc.php';

if (PHP_SAPI === 'cli') {
    $v = daily_bread_pick($argv[1] ?? null);
    echo "\"{$v['text']}\"\n  — {$v['reference']} ({$v['translation']})\n";
    exit(0);
}

$v = daily_bread_pick($_GET['date'] ?? null);
$fmt = strtolower((string)($_GET['format'] ?? ''));
if ($fmt === '') {
    $accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
    $ua     = strtolower((string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
    $fmt = (strpos($accept, 'application/json') !== false) ? 'json'
         : ((strpos($ua, 'curl') !== false || strpos($ua, 'wget') !== false) ? 'text' : 'html');
}

header('Cache-Control: public, max-age=600');
header('X-Verse-Date: ' . $v['date']);

if ($fmt === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($v, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
if ($fmt === 'text') {
    header('Content-Type: text/plain; charset=utf-8');
    echo "\"{$v['text']}\"\n  — {$v['reference']} ({$v['translation']})\n";
    exit;
}
header('Content-Type: text/html; charset=utf-8');
?><!doctype html><meta charset=utf-8><?php require __DIR__.'/includes/seo.inc.php'; alfred_seo('/daily-bread', 'Daily Bread', 'A verse for the day, deterministic and identical for every Alfred Linux installation worldwide.'); ?>
<title>Daily Bread — <?= htmlspecialchars($v['reference']) ?></title>
<body style="font-family:Georgia,serif;max-width:640px;margin:4em auto;background:#0d0d12;color:#e8e2c8;padding:2em;line-height:1.7;text-align:center">
<div style="font-size:.7rem;letter-spacing:5px;color:#f6c343;opacity:.55;text-transform:uppercase">Daily Bread &middot; <?= htmlspecialchars($v['date']) ?></div>
<blockquote style="font-style:italic;font-size:1.2rem;color:#f0e6d0;margin:1.5em 0;padding:1em 1.5em;border-left:3px solid #f6c343;background:rgba(246,195,67,.06);border-radius:0 6px 6px 0;text-align:left">
&ldquo;<?= htmlspecialchars($v['text']) ?>&rdquo;</blockquote>
<div style="color:#f6c343;font-size:.95rem">— <?= htmlspecialchars($v['reference']) ?> (AKJV)</div>
<div style="margin-top:2em;font-size:.65rem;letter-spacing:4px;color:rgba(246,195,67,.3)">&#9849; SOLI DEO GLORIA &#9849;</div>
</body>
