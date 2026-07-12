<?php
/**
 * /akjesusbible.php — Landing for the AKJESUSBible
 * The translation of Scripture cited across alfredlinux.com.
 */
declare(strict_types=1);

if (($_GET['format'] ?? '') === 'json') {
  header('Content-Type: application/json; charset=utf-8');
  header('Access-Control-Allow-Origin: *');
  echo json_encode([
    'name'=>'AKJESUSBible',
    'description'=>'The Authorized King-Jesus Bible — the Authorized Version (1611) honoring the Holy Name of Yeshua throughout, used as the citation source for every teaching page on alfredlinux.com.',
    'home'=>'https://alfredlinux.com/akjesusbible',
    'status'=>'in-progress',
    'soli_deo_gloria'=>true,
  ],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
  exit;
}
?><!DOCTYPE html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<?php require __DIR__.'/includes/seo.inc.php'; alfred_seo('/akjesusbible', 'The AKJESUSBible', 'The Authorized King-Jesus Bible — the translation cited across every teaching at alfredlinux.com.'); ?>
<title>The AKJESUSBible · Alfred Linux</title>
<meta name="description" content="The translation of Scripture cited across every teaching page on alfredlinux.com — the Authorized King-Jesus Bible.">
<meta property="og:title" content="The AKJESUSBible">
<meta property="og:url" content="https://alfredlinux.com/akjesusbible">
<style>
:root{--gold:#ffd700;--gold-dim:#c8a02b;--ink:#0a0a14;--paper:#14141f;--line:#2a2a3e;--text:#ece8df;--text-dim:#a8a499}
*{box-sizing:border-box}html,body{margin:0;padding:0;background:var(--ink);color:var(--text);font-family:"Crimson Pro",Georgia,serif;line-height:1.6}
header{background:radial-gradient(ellipse at top,#15172a 0%,#0a0a14 70%);border-bottom:1px solid var(--line);padding:clamp(3rem,8vw,6rem) 1.5rem 3rem;text-align:center;position:relative}
.cross{font-size:2.5rem;color:var(--gold);letter-spacing:.6em;margin:0 0 1rem}
h1{font-size:clamp(2rem,6vw,3.8rem);margin:0;font-weight:600;color:var(--text)}
h1 .word{color:var(--gold);font-style:italic;text-shadow:0 0 30px rgba(255,215,0,.25)}
.tag{margin:1rem auto 0;max-width:46rem;color:var(--text-dim);font-style:italic;font-size:1.1rem}
main{max-width:46rem;margin:0 auto;padding:3rem 1.5rem 5rem}
section{background:var(--paper);border:1px solid var(--line);border-radius:14px;padding:2rem;margin:0 0 1.5rem}
section h2{margin:0 0 .65rem;color:var(--gold);font-size:1.35rem}
section p{margin:0 0 .85rem;color:var(--text);font-size:1.05rem}
section p:last-child{margin-bottom:0}
.quote{border-left:3px solid var(--gold);padding:1rem 1.25rem;background:var(--ink);border-radius:6px;font-style:italic;color:var(--text-dim);margin:.5rem 0 0}
.actions{text-align:center;margin:2.5rem 0 0}
.actions a{display:inline-block;margin:.35rem;padding:.7rem 1.4rem;border:1px solid var(--gold);color:var(--gold);text-decoration:none;border-radius:8px}
.actions a:hover{background:var(--gold);color:var(--ink)}
footer{text-align:center;padding:3rem 1.5rem 4rem;color:var(--text-dim);font-size:.92rem;border-top:1px solid var(--line);margin-top:4rem}
footer a{color:var(--gold-dim)}
.toc-link{position:absolute;top:1rem;left:1.25rem;color:var(--text-dim);font-size:.78rem;text-decoration:none}
.toc-link:hover{color:var(--gold)}
</style>    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/img/favicon/favicon-16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/img/favicon/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#D4AF37">
</head><body>
<header>
  <a class="toc-link" href="/scriptures">← all teachings</a>
  <div class="cross">✠</div>
  <h1>The <span class="word">AKJESUSBible</span></h1>
  <p class="tag">The Authorized King-Jesus Bible — the citation source for every teaching on alfredlinux.com.</p>
</header>
<main>
<section>
  <h2>Why this Bible</h2>
  <p>Every Scripture quoted across this site — from <a href="/forty-two" style="color:var(--gold)">forty-two.php</a> to <a href="/welcome" style="color:var(--gold)">welcome.php</a> — is drawn from the <strong>AKJESUSBible</strong>: the Authorized Version of 1611, lovingly preserved, with the Holy Name of <em>Yeshua / Jesus</em> honored throughout.</p>
  <p>We cite a single trusted text so every reader can verify what is written, weigh it against the Berean test (Acts 17:11), and know that nothing has been added or twisted to suit a man\'s argument.</p>
</section>
<section>
  <h2>The Test of Every Quotation</h2>
  <div class="quote">"These were more noble than those in Thessalonica, in that they received the word with all readiness of mind, and searched the scriptures daily, whether those things were so." — Acts 17:11</div>
  <p style="margin-top:1.25rem">If anything you read on a teaching page does not match what is written in the AKJESUSBible, the AKJESUSBible wins. Always. The Word is the rule of the words about it.</p>
</section>
<div class="actions">
  <a href="/scriptures">All Teachings</a>
  <a href="/welcome">Enter the Welcome</a>
</div>
</main>
<footer><p>✠ <strong>Soli Deo Gloria</strong> ✠<br><em>"The grass withereth, the flower fadeth: but the word of our God shall stand for ever."</em> — Isaiah 40:8<br><a href="https://alfredlinux.com">alfredlinux.com</a> · <a href="?format=json">JSON</a></p></footer>
</body></html>
