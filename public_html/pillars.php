<?php
// pillars.php — Public Nine Pillars dashboard with live health badges.
// JSON: /pillars.php?format=json
//
// "And the very God of peace sanctify you wholly..." — 1 Thess 5:23 (AKJV)

declare(strict_types=1);
require_once __DIR__ . '/includes/nine-pillars.inc.php';

$check = nine_pillars_check();
$up    = count(array_filter($check, fn($p) => $p['up']));

if (($_GET['format'] ?? '') === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: public, max-age=60');
    header('Access-Control-Allow-Origin: *');
    echo json_encode([
        'now_utc' => gmdate('c'),
        'total'   => 9,
        'up'      => $up,
        'pillars' => $check,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=60');
?><!doctype html><meta charset=utf-8>
<?php require __DIR__.'/includes/seo.inc.php'; alfred_seo('/pillars', 'The Nine Pillars', 'The nine pillars of Alfred Linux — Sabbath, Daily Bread, Witness, Covenant, and more.'); ?>
<title>The Nine Pillars — Alfred Linux</title>
<body style="font-family:Georgia,serif;max-width:880px;margin:3em auto;background:#0d0d12;color:#e8e2c8;padding:2em;line-height:1.6">
<div style="text-align:center;font-size:.7rem;letter-spacing:6px;color:#f6c343;opacity:.5;text-transform:uppercase">The Nine Pillars</div>
<h1 style="text-align:center;color:#f0e6d0;border-bottom:1px solid rgba(246,195,67,.3);padding-bottom:.5em">&#10010; Fruits of the Spirit &#10010;</h1>
<p style="text-align:center;color:#c8c2a8;font-style:italic">"But the fruit of the Spirit is love, joy, peace, longsuffering, gentleness, goodness, faith, Meekness, temperance: against such there is no law."<br>&mdash; Galatians 5:22-23 (AKJV)</p>
<p style="text-align:center;color:#888"><strong style="color:<?= $up===9?'#7fdf7f':'#f6c343' ?>"><?= $up ?>/9 pillars healthy</strong> &middot; <a href="?format=json" style="color:#f6c343">JSON</a></p>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1em;margin-top:2em">
<?php foreach ($check as $p): $color = $p['up'] ? '#7fdf7f' : '#ff8e8e'; ?>
  <a href="<?= htmlspecialchars($p['url']) ?>" style="display:block;padding:1.2em;background:rgba(246,195,67,.04);border:1px solid rgba(246,195,67,.2);border-left:3px solid <?= $color ?>;border-radius:6px;text-decoration:none;color:#e8e2c8;transition:all .15s">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.4em">
      <span style="font-size:1.6rem"><?= $p['icon'] ?></span>
      <span style="font-size:.7rem;color:<?= $color ?>;font-weight:bold">● <?= $p['up']?'UP':'DOWN' ?> · <?= (int)$p['ms'] ?>ms</span>
    </div>
    <div style="color:#f6c343;font-weight:bold;font-size:1.05rem">Pillar <?= $p['n'] ?> &middot; <?= htmlspecialchars($p['name']) ?></div>
    <div style="color:#c8c2a8;font-size:.8rem;margin-top:.2em">Fruit: <?= htmlspecialchars($p['fruit']) ?> &middot; <?= htmlspecialchars($p['verse']) ?></div>
  </a>
<?php endforeach; ?>
</div>
<hr style="border:none;border-top:1px solid rgba(246,195,67,.2);margin:3em 0">
<div style="text-align:center;font-size:.65rem;letter-spacing:4px;color:rgba(246,195,67,.3)">
<a href="/covenant" style="color:#f6c343;text-decoration:none">COVENANT</a> &middot;
<a href="/sovereign" style="color:#f6c343;text-decoration:none">SOVEREIGN</a> &middot;
<a href="/daily-bread" style="color:#f6c343;text-decoration:none">DAILY BREAD</a> &middot;
<a href="/verify" style="color:#f6c343;text-decoration:none">VERIFY</a> &middot;
<a href="/kingdom-status" style="color:#f6c343;text-decoration:none">STATUS</a><br>
<span style="display:inline-block;margin-top:1em">&#9849; SOLI DEO GLORIA &#9849;</span>
</div>
</body>
