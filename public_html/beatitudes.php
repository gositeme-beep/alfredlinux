<?php
/**
 * /beatitudes.php — The Beatitudes, Matthew 5:3-12
 */
declare(strict_types=1);
require __DIR__.'/includes/i18n.inc.php';

// Inscription stack: Greek · Latin (Vulgate) · English (AKJV) — John 19:20
$beats = [
  ['Poor in spirit',                       'theirs is the kingdom of heaven',         'Matthew 5:3',  'πτωχοὶ τῷ πνεύματι',                  'Beati pauperes spiritu'],
  ['They that mourn',                      'they shall be comforted',                  'Matthew 5:4',  'πενθοῦντες',                          'Beati qui lugent'],
  ['The meek',                             'they shall inherit the earth',             'Matthew 5:5',  'πραεῖς',                              'Beati mites'],
  ['Hunger and thirst after righteousness','they shall be filled',                     'Matthew 5:6',  'πεινῶντες καὶ διψῶντες',              'Beati qui esuriunt et sitiunt iustitiam'],
  ['The merciful',                         'they shall obtain mercy',                  'Matthew 5:7',  'ἐλεήμονες',                            'Beati misericordes'],
  ['Pure in heart',                        'they shall see God',                       'Matthew 5:8',  'καθαροὶ τῇ καρδίᾳ',                   'Beati mundo corde'],
  ['The peacemakers',                      'they shall be called the children of God', 'Matthew 5:9',  'εἰρηνοποιοί',                          'Beati pacifici'],
  ['Persecuted for righteousness',         'theirs is the kingdom of heaven',          'Matthew 5:10', 'δεδιωγμένοι',                          'Beati qui persecutionem patiuntur propter iustitiam'],
  ['Reviled for His sake',                 'great is your reward in heaven',           'Matthew 5:11-12', 'μακάριοί ἐστε ὅταν ὀνειδίσωσιν ὑμᾶς', 'Beati estis cum maledixerint vobis'],
];

if (($_GET['format'] ?? '') === 'json') {
  header('Content-Type: application/json; charset=utf-8');
  header('Access-Control-Allow-Origin: *');
  echo json_encode(['doctrine'=>'beatitudes','count'=>count($beats),'beatitudes'=>array_map(fn($b)=>['who'=>$b[0],'promise'=>$b[1],'reference'=>$b[2],'greek'=>$b[3],'latin'=>$b[4]],$beats),'inscription'=>'Greek · Latin · English — John 19:20','soli_deo_gloria'=>true],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
  exit;
}
?><!DOCTYPE html><html<?= alfred_html_attrs() ?>><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<?php require __DIR__.'/includes/seo.inc.php'; alfred_seo('/beatitudes', 'The Beatitudes', 'Nine blessings of the upside-down Kingdom — Matthew 5:3-12 — in Greek, Latin, and English (AKJV).'); ?>
<link rel="alternate" hreflang="en" href="https://alfredlinux.com/beatitudes?lang=en">
<link rel="alternate" hreflang="fr" href="https://alfredlinux.com/beatitudes?lang=fr">
<link rel="alternate" hreflang="he" href="https://alfredlinux.com/beatitudes?lang=he">
<?php alfred_lang_styles(); ?>
<title>The Beatitudes · Alfred Linux</title>
<meta name="description" content="The nine Beatitudes of the Sermon on the Mount — Matthew 5:3-12 — the upside-down Kingdom of Yeshua.">
<meta property="og:title" content="The Beatitudes"><meta property="og:url" content="https://alfredlinux.com/beatitudes">
<style>
:root{--gold:#ffd700;--gold-dim:#c8a02b;--ink:#0a0a14;--paper:#14141f;--line:#2a2a3e;--text:#ece8df;--text-dim:#a8a499;--cyan:#66c2ff;--rose:#d75a7a}
*{box-sizing:border-box}html,body{margin:0;padding:0;background:var(--ink);color:var(--text);font-family:"Crimson Pro",Georgia,serif;line-height:1.6}
header{background:radial-gradient(ellipse at top,#15172a 0%,#0a0a14 70%);border-bottom:1px solid var(--line);padding:clamp(3rem,8vw,6rem) 1.5rem 3rem;text-align:center;position:relative}
.cross{font-size:2.5rem;color:var(--gold);letter-spacing:.6em;margin:0 0 1rem}
h1{font-size:clamp(2rem,6vw,4rem);margin:0;font-weight:600}
h1 .word{color:var(--gold);font-style:italic;text-shadow:0 0 30px rgba(255,215,0,.25)}
.tag{margin:1rem auto 0;max-width:46rem;color:var(--text-dim);font-style:italic;font-size:1.1rem}
main{max-width:60rem;margin:0 auto;padding:3rem 1.5rem 5rem}
.intro{text-align:center;margin:0 auto 3rem;max-width:42rem;color:var(--text-dim);font-size:1.1rem}
.intro cite{color:var(--gold-dim);font-style:normal;display:block;margin-top:.5rem}
.list{display:grid;grid-template-columns:1fr;gap:1rem;list-style:none;padding:0;margin:0;counter-reset:b}
.list li{counter-increment:b;background:var(--paper);border:1px solid var(--line);border-radius:14px;padding:1.5rem 1.75rem;position:relative;transition:.25s}
.list li::before{content:"Blessed are " counter(b,decimal) "";position:absolute;top:1rem;right:1.5rem;color:var(--gold-dim);font-size:.8rem;font-family:ui-monospace,monospace;letter-spacing:.05em;opacity:.7;display:none}
.list li:hover{border-color:var(--gold);transform:translateY(-1px)}
.bhead{margin:0 0 .35rem;color:var(--gold);font-size:1.3rem;font-weight:600}
.bhead small{color:var(--text-dim);font-style:italic;font-weight:400;font-size:1rem;margin-left:.4rem}
.bgreek{color:var(--cyan);font-size:.95rem;font-style:italic;margin:0 0 .25rem;font-family:Georgia,serif}
.blatin{color:#e6c896;font-size:.95rem;font-style:italic;margin:0 0 .35rem;font-variant:small-caps;letter-spacing:.03em;font-family:"EB Garamond",Georgia,serif}
.bref{color:var(--text-dim);font-size:.8rem;font-family:ui-monospace,monospace}
.bpromise{margin:.45rem 0 0;color:var(--text);font-size:1.1rem;font-style:italic}
.bpromise::before{content:"→ ";color:var(--gold)}
.invite{margin:5rem auto 0;max-width:42rem;text-align:center;padding:2.5rem 1.5rem;background:radial-gradient(ellipse,#1f1832,#0a0a14);border:1px solid var(--gold-dim);border-radius:14px}
.invite p{font-size:1.15rem;color:var(--text);margin:0 0 1.25rem}
.invite a{display:inline-block;margin:.35rem;padding:.7rem 1.4rem;border:1px solid var(--gold);color:var(--gold);text-decoration:none;border-radius:8px}
.invite a:hover{background:var(--gold);color:var(--ink)}
footer{text-align:center;padding:3rem 1.5rem 4rem;color:var(--text-dim);font-size:.92rem;border-top:1px solid var(--line);margin-top:4rem}
footer a{color:var(--gold-dim)}
.toc-link{position:absolute;top:1rem;left:1.25rem;color:var(--text-dim);font-size:.78rem;text-decoration:none}
.toc-link:hover{color:var(--gold)}
.json-link{position:absolute;top:1rem;right:1.25rem;color:var(--text-dim);font-size:.78rem;text-decoration:none;font-family:ui-monospace,monospace}
.json-link:hover{color:var(--gold)}
</style>    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/img/favicon/favicon-16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/img/favicon/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#D4AF37">
</head><body>
<?php alfred_lang_switcher('/beatitudes'); ?>
<header>
  <a class="toc-link" href="/scriptures">← all teachings</a>
  <a class="json-link" href="?format=json">{ json }</a>
  <div class="cross">✠</div>
  <h1>The <span class="word">Beatitudes</span></h1>
  <p class="tag">"And he opened his mouth, and taught them, saying..." — Matthew 5:2</p>
</header>
<main>
<p class="intro">The upside-down Kingdom of Yeshua — where the poor are rich, the mourning are comforted, and the meek inherit the earth.<cite>— Sermon on the Mount, AKJESUSBible</cite></p>
<ol class="list">
<?php foreach($beats as $b): ?>
<li>
  <h2 class="bhead">Blessed are <?= htmlspecialchars($b[0]) ?></h2>
  <p class="bgreek"><?= htmlspecialchars($b[3]) ?></p>
  <p class="blatin"><?= htmlspecialchars($b[4]) ?></p>
  <span class="bref"><?= htmlspecialchars($b[2]) ?></span>
  <p class="bpromise"><?= htmlspecialchars($b[1]) ?></p>
</li>
<?php endforeach; ?>
</ol>
<section class="invite">
  <p>"Rejoice, and be exceeding glad: for great is your reward in heaven." — Matthew 5:12</p>
  <a href="/scriptures">All Teachings</a>
  <a href="/lords-prayer">The Lord's Prayer</a>
  <a href="/welcome">Enter the Welcome</a>
</section>
</main>
<footer><p>✠ <strong>Soli Deo Gloria</strong> ✠<br><em>"Ye are the salt of the earth... Ye are the light of the world."</em> — Matthew 5:13-14<br><a href="https://alfredlinux.com">alfredlinux.com</a> · Scriptures from the AKJESUSBible · <a href="?format=json">JSON</a></p></footer>
</body></html>
