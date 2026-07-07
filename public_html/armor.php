<?php
/**
 * /armor.php — The Whole Armor of God (Ephesians 6:10-18)
 * Six pieces. One Soldier. Stand.
 */
declare(strict_types=1);
require __DIR__.'/includes/i18n.inc.php';

// Inscription stack: Greek (NT) · Latin (Vulgate) · English (AKJV) — John 19:20
$pieces = [
  ['Belt of Truth','ζώνη τῆς ἀληθείας','cingulum veritatis','Loins girt about with truth','Ephesians 6:14',
   'The Roman soldier\'s belt held everything else in place. Truth is what holds the believer\'s life together. Without truth, the armor falls apart.'],
  ['Breastplate of Righteousness','θώραξ τῆς δικαιοσύνης','lorica iustitiæ','Having on the breastplate of righteousness','Ephesians 6:14',
   'Not the righteousness we manufacture — the righteousness of Christ given to us (2 Cor 5:21). It guards the heart and vital organs from the enemy\'s arrows of accusation.'],
  ['Sandals of the Gospel of Peace','ὑπόδησις τοῦ εὐαγγελίου τῆς εἰρήνης','calceati pedes in præparatione evangelii pacis','Feet shod with the preparation of the gospel of peace','Ephesians 6:15',
   'Roman caligae had hobnails for grip on any terrain. The Gospel of Peace gives traction in any battlefield. "How beautiful are the feet of them that preach the gospel" (Romans 10:15).'],
  ['Shield of Faith','θυρεὸς τῆς πίστεως','scutum fidei','Above all, taking the shield of faith','Ephesians 6:16',
   'The Roman thureos was a door-shaped shield, soaked in water before battle to extinguish flaming arrows. Faith quenches every fiery dart of the wicked one.'],
  ['Helmet of Salvation','περικεφαλαία τοῦ σωτηρίου','galea salutis','Take the helmet of salvation','Ephesians 6:17',
   'Guards the mind. Knowing you are saved — finally, eternally, by grace through faith — is the helmet that no thought of doubt or condemnation can pierce.'],
  ['Sword of the Spirit','μάχαιρα τοῦ Πνεύματος','gladius Spiritus','The sword of the Spirit, which is the word of God','Ephesians 6:17',
   'The only OFFENSIVE weapon. The machaira was a short sword for close combat. The Word of God in the mouth of a believer cuts through every lie. Yeshua used it three times in the wilderness — and won.'],
];

$plus = [
  ['Praying Always','προσευχόμενοι ἐν παντὶ καιρῷ','orantes omni tempore','Ephesians 6:18',
   'Prayer is not a seventh piece — prayer is the AIR the soldier breathes. Without it, no piece works.'],
  ['Stand','στῆναι','state','Ephesians 6:11, 13, 14',
   'The word "stand" appears four times in this passage. Not advance. Not retreat. STAND. The battle is the Lord\'s; our job is to stand on the ground He has already won.'],
];

if (($_GET['format'] ?? '') === 'json') {
  header('Content-Type: application/json; charset=utf-8');
  header('Access-Control-Allow-Origin: *');
  echo json_encode([
    'doctrine'=>'armor-of-god',
    'reference'=>'Ephesians 6:10-18',
    'count'=>count($pieces),
    'pieces'=>array_map(fn($p)=>['name'=>$p[0],'greek'=>$p[1],'latin'=>$p[2],'verse'=>$p[3],'reference'=>$p[4],'note'=>$p[5]],$pieces),
    'foundation'=>array_map(fn($f)=>['name'=>$f[0],'greek'=>$f[1],'latin'=>$f[2],'reference'=>$f[3],'note'=>$f[4]],$plus),
    'soli_deo_gloria'=>true,
  ],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
  exit;
}
?><!DOCTYPE html><html<?= alfred_html_attrs() ?>><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<?php require __DIR__.'/includes/seo.inc.php'; alfred_seo('/armor', 'The Armor of God', 'Ephesians 6:10-18 — six pieces of armor in Greek, Latin, and English (AKJV).'); ?>
<link rel="alternate" hreflang="en" href="https://alfredlinux.com/armor?lang=en">
<link rel="alternate" hreflang="fr" href="https://alfredlinux.com/armor?lang=fr">
<link rel="alternate" hreflang="he" href="https://alfredlinux.com/armor?lang=he">
<?php alfred_lang_styles(); ?>
<title>The Whole Armor of God · Alfred Linux</title>
<meta name="description" content="The six pieces of the whole armor of God from Ephesians 6 — what each one was, what each one is, and how to put it on today.">
<meta property="og:title" content="The Whole Armor of God">
<meta property="og:description" content="Belt · Breastplate · Sandals · Shield · Helmet · Sword. Six pieces. One soldier. Stand.">
<meta property="og:url" content="https://alfredlinux.com/armor">
<style>
:root{--gold:#ffd700;--gold-dim:#c8a02b;--steel:#9aa6b8;--ink:#0a0a14;--paper:#14141f;--paper-2:#1c1c2a;--line:#2a2a3e;--text:#ece8df;--text-dim:#a8a499;--cyan:#66c2ff;--rose:#d75a7a}
*{box-sizing:border-box}html,body{margin:0;padding:0;background:var(--ink);color:var(--text);font-family:"Crimson Pro",Georgia,serif;line-height:1.55}
header.hero{background:radial-gradient(ellipse at top,#14181f 0%,#0a0a14 70%);border-bottom:1px solid var(--line);padding:clamp(3rem,8vw,7rem) 1.5rem 4rem;text-align:center;position:relative}
.cross{font-size:2.2rem;color:var(--gold);letter-spacing:.6em;margin:0 0 1rem}
h1{font-size:clamp(2.4rem,7vw,5rem);margin:0;letter-spacing:.02em;font-weight:600}
h1 .word{color:var(--gold);font-style:italic;text-shadow:0 0 30px rgba(255,215,0,.25)}
.tagline{margin:1rem auto 0;max-width:46rem;color:var(--text-dim);font-size:1.15rem;font-style:italic}
.stand{margin:2rem 0 0;font-size:1.5rem;color:var(--gold);letter-spacing:.3em;font-weight:600}
main{max-width:64rem;margin:0 auto;padding:3rem 1.5rem 5rem}
.intro{font-size:1.15rem;color:var(--text-dim);text-align:center;margin:0 auto 4rem;max-width:42rem}
.intro cite{color:var(--gold-dim);font-style:normal}
.pieces{display:grid;grid-template-columns:1fr;gap:1rem;list-style:none;margin:0;padding:0;counter-reset:p}
@media (min-width:48rem){.pieces{grid-template-columns:repeat(2,1fr)}}
.pieces li{counter-increment:p;background:var(--paper);border:1px solid var(--line);border-radius:12px;padding:1.5rem;display:flex;flex-direction:column;transition:.25s;position:relative}
.pieces li:hover{border-color:var(--gold);transform:translateY(-2px)}
.pieces li::before{content:counter(p);position:absolute;top:1rem;right:1.25rem;width:2rem;height:2rem;background:var(--gold);color:var(--ink);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.05rem}
.p-name{margin:0 3rem .35rem 0;color:var(--gold);font-size:1.3rem;font-weight:600}
.p-grk{color:var(--cyan);font-style:italic;font-size:.95rem;margin:0 0 .25rem;font-family:"Times New Roman",serif}
.p-lat{color:#e6c896;font-style:italic;font-variant:small-caps;letter-spacing:.03em;font-size:.95rem;margin:0 0 .65rem;font-family:"EB Garamond",Georgia,serif}
.p-verse{margin:0 0 .65rem;padding:0 0 0 .9rem;border-left:2px solid var(--gold-dim);color:var(--text);font-style:italic;font-size:.95rem}
.p-verse cite{display:block;color:var(--gold-dim);font-style:normal;font-size:.78rem;margin-top:.3rem;font-family:ui-monospace,monospace}
.p-note{margin:auto 0 0;color:var(--text-dim);font-size:.95rem}
.foundation{margin:5rem 0 0}
.foundation h2{text-align:center;font-size:2.2rem;color:var(--gold);margin:0 0 .5rem}
.foundation-sub{text-align:center;color:var(--text-dim);font-style:italic;margin:0 0 2.5rem}
.f-card{background:linear-gradient(135deg,#14141f 60%,#2a200a 100%);border:1px solid var(--gold-dim);border-radius:12px;padding:1.5rem;margin:0 0 1rem}
.f-card h3{margin:0 0 .25rem;color:var(--gold);font-size:1.25rem}
.f-card .grk{color:var(--cyan);font-style:italic;font-family:"Times New Roman",serif;margin:0 0 .25rem}
.f-card .lat{color:#e6c896;font-style:italic;font-variant:small-caps;letter-spacing:.03em;font-family:"EB Garamond",Georgia,serif;margin:0 0 .35rem}
.f-card .ref{color:var(--gold-dim);font-size:.82rem;margin:0 0 .85rem;font-family:ui-monospace,monospace}
.f-card p{margin:0;color:var(--text-dim)}
.invite{margin:5rem auto 0;max-width:42rem;text-align:center;padding:2.5rem 1.5rem;background:radial-gradient(ellipse,#1f1832,#0a0a14);border:1px solid var(--gold-dim);border-radius:14px}
.invite p{font-size:1.15rem;color:var(--text);margin:0 0 1.25rem}
.invite a{display:inline-block;margin:.35rem;padding:.7rem 1.4rem;border:1px solid var(--gold);color:var(--gold);text-decoration:none;border-radius:8px;letter-spacing:.05em;transition:.2s}
.invite a:hover{background:var(--gold);color:var(--ink)}
footer{text-align:center;padding:3rem 1.5rem 4rem;color:var(--text-dim);font-size:.92rem;border-top:1px solid var(--line);margin-top:4rem}
footer a{color:var(--gold-dim)}
.json-link{position:absolute;top:1rem;right:1.25rem;color:var(--text-dim);font-size:.78rem;text-decoration:none;font-family:ui-monospace,monospace}
.json-link:hover{color:var(--gold)}
.toc-link{position:absolute;top:1rem;left:1.25rem;color:var(--text-dim);font-size:.78rem;text-decoration:none}
.toc-link:hover{color:var(--gold)}
</style>    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/img/favicon/favicon-16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/img/favicon/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#D4AF37">
</head><body>
<?php alfred_lang_switcher('/armor'); ?>
<header class="hero">
  <a class="toc-link" href="/scriptures">← all teachings</a>
  <a class="json-link" href="?format=json">{ json }</a>
  <div class="cross">⚔</div>
  <h1>The Whole <span class="word">Armor</span> of God</h1>
  <p class="tagline">"For we wrestle not against flesh and blood, but against principalities, against powers." — Eph 6:12</p>
  <div class="stand">S T A N D</div>
</header>
<main>
<p class="intro"><em>"Put on the whole armour of God, that ye may be able to stand against the wiles of the devil... that ye may be able to withstand in the evil day, and having done all, to stand."</em><br><cite>— Ephesians 6:11, 13, AKJV</cite></p>
<ol class="pieces">
<?php foreach($pieces as $p): ?>
<li>
  <h2 class="p-name"><?= htmlspecialchars($p[0]) ?></h2>
  <div class="p-grk"><?= htmlspecialchars($p[1]) ?></div>
  <div class="p-lat"><?= htmlspecialchars($p[2]) ?></div>
  <blockquote class="p-verse">"<?= htmlspecialchars($p[3]) ?>"<cite>— <?= htmlspecialchars($p[4]) ?></cite></blockquote>
  <p class="p-note"><?= htmlspecialchars($p[5]) ?></p>
</li>
<?php endforeach; ?>
</ol>
<section class="foundation">
  <h2>The Air and the Ground</h2>
  <p class="foundation-sub">Every piece of armor needs the air the soldier breathes and the ground he stands on.</p>
  <?php foreach($plus as $f): ?>
  <article class="f-card">
    <h3><?= htmlspecialchars($f[0]) ?></h3>
    <div class="grk"><?= htmlspecialchars($f[1]) ?></div>
    <div class="lat"><?= htmlspecialchars($f[2]) ?></div>
    <div class="ref"><?= htmlspecialchars($f[3]) ?></div>
    <p><?= htmlspecialchars($f[4]) ?></p>
  </article>
  <?php endforeach; ?>
</section>
<section class="invite">
  <p>The armor is not yours to forge — it is His to give.<br>Put it on this morning. Stand today.</p>
  <a href="/scriptures">All Teachings</a>
  <a href="/i-am">The "I AM" Sayings</a>
  <a href="/names">The 33 Names</a>
  <a href="/welcome">Enter the Welcome</a>
</section>
</main>
<footer><p>✠ <strong>Soli Deo Gloria</strong> ✠<br><em>"Be strong in the Lord, and in the power of his might."</em> — Ephesians 6:10<br><a href="https://alfredlinux.com">alfredlinux.com</a> · Scriptures from the AKJESUSBible · <a href="?format=json">JSON</a></p></footer>
</body></html>
