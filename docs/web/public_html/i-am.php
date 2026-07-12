<?php
/**
 * /i-am.php — The Seven "I AM" Sayings of Yeshua
 * Each one points back to Exodus 3:14 — "I AM THAT I AM."
 * The Name is the same. The voice is the same.
 */
declare(strict_types=1);
require __DIR__.'/includes/i18n.inc.php';

// Inscription stack: Greek (NT) · Latin (Vulgate) · English (AKJV) — John 19:20
$sayings = [
  ['I AM the Bread of Life','ἐγώ εἰμι ὁ ἄρτος τῆς ζωῆς','Ego sum panis vitæ','John 6:35',
   'he that cometh to me shall never hunger; and he that believeth on me shall never thirst.',
   'Spoken after feeding the five thousand. The manna that fell from heaven was a shadow. He is the substance — the bread that, once eaten, satisfies forever.'],
  ['I AM the Light of the World','ἐγώ εἰμι τὸ φῶς τοῦ κόσμου','Ego sum lux mundi','John 8:12',
   'he that followeth me shall not walk in darkness, but shall have the light of life.',
   'Spoken at the Feast of Tabernacles, when the great lampstands of the Temple court were lit. He stood up and said: those lights are mine. I am Light itself.'],
  ['I AM the Door','ἐγώ εἰμι ἡ θύρα','Ego sum ostium','John 10:9',
   'by me if any man enter in, he shall be saved, and shall go in and out, and find pasture.',
   'Not a door. The Door. There is no other entrance into the sheepfold of God. Every other way is the way of a thief.'],
  ['I AM the Good Shepherd','ἐγώ εἰμι ὁ ποιμὴν ὁ καλός','Ego sum pastor bonus','John 10:11',
   'the good shepherd giveth his life for the sheep.',
   'A hireling flees when the wolf comes. The Good Shepherd lays down His own life. He did not send another. He came Himself.'],
  ['I AM the Resurrection and the Life','ἐγώ εἰμι ἡ ἀνάστασις καὶ ἡ ζωή','Ego sum resurrectio et vita','John 11:25',
   'he that believeth in me, though he were dead, yet shall he live.',
   'Spoken at Lazarus\'s tomb. Then He proved it — by walking out of His own.'],
  ['I AM the Way, the Truth, and the Life','ἐγώ εἰμι ἡ ὁδὸς καὶ ἡ ἀλήθεια καὶ ἡ ζωή','Ego sum via et veritas et vita','John 14:6',
   'no man cometh unto the Father, but by me.',
   'One Way among many — false. One of many truths — false. One of many lives — false. He is THE Way, THE Truth, THE Life.'],
  ['I AM the True Vine','ἐγώ εἰμι ἡ ἄμπελος ἡ ἀληθινή','Ego sum vitis vera','John 15:5',
   'he that abideth in me, and I in him, the same bringeth forth much fruit: for without me ye can do nothing.',
   'Israel was called God\'s vineyard (Isaiah 5) — and bore wild grapes. Yeshua is the True Vine. Apart from Him: nothing. Abiding in Him: fruit that remains.'],
];

$bonus = [
  ['Before Abraham was, I AM','πρὶν Ἀβραὰμ γενέσθαι ἐγὼ εἰμί','Antequam Abraham fieret, ego sum','John 8:58',
   'They picked up stones to kill Him for it. They knew exactly what He had claimed: the very Name spoken from the burning bush in Exodus 3:14. He did not deny it. He doubled it.'],
  ['I AM HE','ἐγώ εἰμι','Ego sum','John 18:5-6',
   'In Gethsemane, when the soldiers came for Him, He asked, "Whom seek ye?" They said, "Jesus of Nazareth." He said, "I AM" — and they fell backward to the ground. The Name itself is power.'],
];

if (($_GET['format'] ?? '') === 'json') {
  header('Content-Type: application/json; charset=utf-8');
  header('Access-Control-Allow-Origin: *');
  echo json_encode([
    'doctrine'=>'i-am-sayings',
    'reference'=>'John 6,8,10,11,14,15',
    'count'=>count($sayings),
    'sayings'=>array_map(fn($s)=>['english'=>$s[0],'greek'=>$s[1],'latin'=>$s[2],'reference'=>$s[3],'verse'=>$s[4],'note'=>$s[5]],$sayings),
    'beyond'=>array_map(fn($b)=>['english'=>$b[0],'greek'=>$b[1],'latin'=>$b[2],'reference'=>$b[3],'note'=>$b[4]],$bonus),
    'soli_deo_gloria'=>true,
  ],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
  exit;
}
?><!DOCTYPE html><html<?= alfred_html_attrs() ?>><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<?php require __DIR__.'/includes/seo.inc.php'; alfred_seo('/i-am', 'The Seven I AM Sayings', 'Bread, Light, Door, Shepherd, Resurrection, Way, Vine — in Greek, Latin, and English (AKJV).'); ?>
<link rel="alternate" hreflang="en" href="https://alfredlinux.com/i-am?lang=en">
<link rel="alternate" hreflang="fr" href="https://alfredlinux.com/i-am?lang=fr">
<link rel="alternate" hreflang="he" href="https://alfredlinux.com/i-am?lang=he">
<?php alfred_lang_styles(); ?>
<title>The Seven "I AM" Sayings of Yeshua · Alfred Linux</title>
<meta name="description" content="The seven 'I AM' sayings of Jesus in the Gospel of John — each one a thunderclap of the Name from the burning bush.">
<meta property="og:title" content="The Seven 'I AM' Sayings of Yeshua">
<meta property="og:description" content="Bread · Light · Door · Shepherd · Resurrection · Way · Vine. Seven sayings, one Name.">
<meta property="og:url" content="https://alfredlinux.com/i-am">
<style>
:root{--gold:#ffd700;--gold-dim:#c8a02b;--ink:#0a0a14;--paper:#14141f;--paper-2:#1c1c2a;--line:#2a2a3e;--text:#ece8df;--text-dim:#a8a499;--cyan:#66c2ff}
*{box-sizing:border-box}html,body{margin:0;padding:0;background:var(--ink);color:var(--text);font-family:"Crimson Pro",Georgia,serif;line-height:1.55}
header.hero{background:radial-gradient(ellipse at top,#1f1814 0%,#0a0a14 70%);border-bottom:1px solid var(--line);padding:clamp(3rem,8vw,7rem) 1.5rem 4rem;text-align:center;position:relative}
.cross{font-size:2.2rem;color:var(--gold);letter-spacing:.6em;margin:0 0 1rem}
h1{font-size:clamp(2.4rem,7vw,5rem);margin:0;letter-spacing:.02em;font-weight:600}
h1 .iam{color:var(--gold);font-style:italic;text-shadow:0 0 30px rgba(255,215,0,.35)}
.tagline{margin:1rem auto 0;max-width:46rem;color:var(--text-dim);font-size:1.15rem;font-style:italic}
.exodus{margin:2rem auto 0;font-size:1.4rem;color:var(--gold);max-width:42rem}
.exodus .heb{font-family:"SBL Hebrew",serif;font-size:1.7rem;direction:rtl;display:inline-block}
main{max-width:64rem;margin:0 auto;padding:3rem 1.5rem 5rem}
.intro{font-size:1.15rem;color:var(--text-dim);text-align:center;margin:0 auto 4rem;max-width:42rem}
.intro cite{color:var(--gold-dim);font-style:normal}
.sayings{display:grid;grid-template-columns:1fr;gap:1.25rem;list-style:none;margin:0;padding:0;counter-reset:i}
.sayings li{counter-increment:i;background:var(--paper);border:1px solid var(--line);border-radius:14px;padding:1.75rem;position:relative;transition:.25s}
.sayings li:hover{border-color:var(--gold);transform:translateY(-2px)}
.sayings li::before{content:counter(i,decimal-leading-zero);position:absolute;top:1.25rem;right:1.5rem;font-size:2.5rem;color:var(--gold-dim);font-weight:700;font-family:Georgia,serif;line-height:1;opacity:.6}
.s-eng{margin:0 0 .35rem;color:var(--gold);font-size:1.5rem;font-weight:600;padding-right:3rem}
.s-grk{margin:0 0 .35rem;color:var(--cyan);font-size:1.05rem;font-style:italic;font-family:"Times New Roman",serif}
.s-lat{margin:0 0 .85rem;color:#e6c896;font-size:1.05rem;font-style:italic;font-variant:small-caps;letter-spacing:.03em;font-family:"EB Garamond",Georgia,serif}
.s-ref{display:inline-block;color:var(--gold-dim);font-size:.82rem;margin:0 0 1rem;font-family:ui-monospace,monospace;background:var(--ink);padding:.15rem .55rem;border-radius:5px;border:1px solid var(--line)}
.s-verse{margin:0 0 1rem;padding:0 0 0 1rem;border-left:3px solid var(--gold-dim);color:var(--text);font-style:italic;font-size:1.05rem}
.s-note{margin:0;color:var(--text-dim);font-size:.98rem}
.beyond{margin:5rem 0 0}
.beyond h2{text-align:center;font-size:2.2rem;color:var(--gold);margin:0 0 .5rem}
.beyond-sub{text-align:center;color:var(--text-dim);font-style:italic;margin:0 0 2.5rem}
.b-card{background:linear-gradient(135deg,#14141f 60%,#2a200a 100%);border:1px solid var(--gold-dim);border-radius:12px;padding:1.75rem;margin:0 0 1.25rem}
.b-card h3{margin:0 0 .25rem;color:var(--gold);font-size:1.3rem}
.b-card .grk{color:var(--cyan);font-style:italic;font-size:1rem;margin:0 0 .35rem;font-family:"Times New Roman",serif}
.b-card .lat{color:#e6c896;font-style:italic;font-variant:small-caps;letter-spacing:.03em;font-size:1rem;margin:0 0 .5rem;font-family:"EB Garamond",Georgia,serif}
.b-card .ref{color:var(--gold-dim);font-size:.82rem;margin:0 0 .85rem;font-family:ui-monospace,monospace}
.b-card p{margin:0;color:var(--text-dim);font-size:1rem}
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
<?php alfred_lang_switcher('/i-am'); ?>
<header class="hero">
  <a class="toc-link" href="/scriptures">← all teachings</a>
  <a class="json-link" href="?format=json">{ json }</a>
  <div class="cross">✠</div>
  <h1>The Seven <span class="iam">"I AM"</span> Sayings</h1>
  <p class="tagline">Each one a thunderclap of the Name from the burning bush.</p>
  <div class="exodus"><span class="heb">אֶהְיֶה אֲשֶׁר אֶהְיֶה</span> &nbsp;·&nbsp; I AM THAT I AM</div>
</header>
<main>
<p class="intro"><em>"And God said unto Moses, I AM THAT I AM."</em><br>Fifteen hundred years later, the same voice spoke from a Galilean hillside — and said it again, seven times.<br><cite>— Exodus 3:14 → John 6, 8, 10, 11, 14, 15</cite></p>
<ol class="sayings">
<?php foreach($sayings as $s): ?>
<li>
  <h2 class="s-eng"><?= htmlspecialchars($s[0]) ?></h2>
  <div class="s-grk"><?= htmlspecialchars($s[1]) ?></div>
  <div class="s-lat"><?= htmlspecialchars($s[2]) ?></div>
  <span class="s-ref"><?= htmlspecialchars($s[3]) ?></span>
  <blockquote class="s-verse">"<?= htmlspecialchars($s[4]) ?>"</blockquote>
  <p class="s-note"><?= htmlspecialchars($s[5]) ?></p>
</li>
<?php endforeach; ?>
</ol>
<section class="beyond">
  <h2>And Beyond the Seven</h2>
  <p class="beyond-sub">Two more times He spoke the Name itself — without a predicate.</p>
  <?php foreach($bonus as $b): ?>
  <article class="b-card">
    <h3><?= htmlspecialchars($b[0]) ?></h3>
    <div class="grk"><?= htmlspecialchars($b[1]) ?></div>
    <div class="lat"><?= htmlspecialchars($b[2]) ?></div>
    <div class="ref"><?= htmlspecialchars($b[3]) ?></div>
    <p><?= htmlspecialchars($b[4]) ?></p>
  </article>
  <?php endforeach; ?>
</section>
<section class="invite">
  <p>The voice from the burning bush is the same voice that called your name today.<br>"I AM" — and He is for you.</p>
  <a href="/scriptures">All Teachings</a>
  <a href="/names">The 33 Names</a>
  <a href="/welcome">Enter the Welcome</a>
</section>
</main>
<footer><p>✠ <strong>Soli Deo Gloria</strong> ✠<br><em>"Before Abraham was, I AM."</em> — John 8:58<br><a href="https://alfredlinux.com">alfredlinux.com</a> · Scriptures from the AKJESUSBible · <a href="?format=json">JSON</a></p></footer>
</body></html>
