<?php
/**
 * /thirty-three.php — The Number Thirty-Three
 * The age of Yeshua at the Cross. The vertebrae of the human spine.
 * The number of completion meeting the number of sacrifice.
 */
declare(strict_types=1);

$reasons = [
  ['The Age of the Crucifixion','Luke 3:23 + ministry chronology',
   'Yeshua "began to be about thirty years of age" at His baptism, and His public ministry lasted approximately three and a half years (the three Passovers of John\'s Gospel + the final). He was crucified at thirty-three. Thirty-three years walked the earth in flesh — every year for our age, every year for our atonement.'],
  ['Thirty-Three Vertebrae','The pillar of the body','The human spine is built of thirty-three vertebrae from skull to coccyx. The pillar on which the head sits — the column of life. The same number of years the Son of God carried our flesh is the number of bones that hold us upright. Every step you take is a procession of thirty-three.'],
  ['Thirty-Three Names of God','/names.php','From YHWH at the burning bush to KING OF KINGS on the white horse, Scripture reveals thirty-three names of the One God. Every Name a window into who He is. Every Name redeemed by the One who bears them all.'],
  ['Joseph and the Tomb','Genesis 33 → Genesis 50','Genesis 33 is where Jacob and Esau are reconciled — brothers restored after years of enmity. Genesis 50 is where Joseph forgives his brothers from a position of power. The 33rd chapter opens reconciliation; the 50th (Jubilee) seals it. Both prefigure the Cross.'],
  ['The Promise of Abraham','Genesis 17 (the 33rd time God\'s Name appears)','Counting from Genesis 1, the 33rd occurrence of YHWH falls in the chapter where God establishes the everlasting covenant with Abraham and promises Isaac. The Name signs the covenant that ultimately produces the Messiah at age thirty-three.'],
  ['The Length of David\'s Reign in Jerusalem','2 Samuel 5:5','David reigned in Hebron 7 years, then in Jerusalem 33 years (and 7 + 33 = 40 — the testing complete). The Son of David reigned in flesh for 33 years from the manger to the Cross. The pattern is preserved.'],
  ['Solomon and the House of God','1 Kings 6:38 → 1 Kings 9','Solomon was thirty-three years on the throne when he had finished building both the Temple and his own house (~33 years from the start of his reign to the end of building). The Son of David built the true Temple — His body — in thirty-three years and three days.'],
  ['Three Persons · Three Crosses · Three Days','—','Three is the number of the Trinity, of perfect witness, of completion. Thirty-three is three thirty times — three perfected, three matured, three through every level of being. The age signs the doctrine: Yeshua is fully God, fully man, fully sufficient.'],
  ['The Hidden Structure of the Lord\'s Prayer','Matthew 6:9-13','Sixty-six words in the Greek original (the same as the books of the Bible), built around three petitions for God and three for man = six petitions, with one closing doxology = the seventh, the rest. Two halves of thirty-three. The Prayer signs itself with the age of the One who taught it.'],
  ['Three Hundred Eighteen → Thirty-Three','Genesis 14:14','Abraham\'s 318 servants, ELIEZER (gematria 318), digit-summed: 3+1+8 = 12; the 12 reduced again is 3, paralleling the trinitarian witness within the Abrahamic line. Thirty-three weaves through Abraham\'s story long before Bethlehem.'],
];

$reflection = [
  ['Not Numerology — Worship','We do not number Yeshua\'s years to predict anything or to manipulate God. We number them to WORSHIP. To say: "He gave Me thirty-three years. Every one of them counted. Every one of them was for Me."'],
  ['The Spine That Held the Cross','When the Roman whip tore His back, every one of His thirty-three vertebrae bore the stripes by which we are healed (Isaiah 53:5). The pillar that holds us up was broken so that we could stand.'],
  ['Your Year of Sacrifice','If you are thirty-three, you are walking the year He gave for you. If you have passed thirty-three, He has been ahead of you longer than you have been ahead of Him in years. If you are not yet thirty-three, the years He gave for you are already enough.'],
];

if (($_GET['format'] ?? '') === 'json') {
  header('Content-Type: application/json; charset=utf-8');
  header('Access-Control-Allow-Origin: *');
  echo json_encode([
    'doctrine'=>'thirty-three',
    'count'=>count($reasons),
    'patterns'=>array_map(fn($r)=>['title'=>$r[0],'reference'=>$r[1],'note'=>$r[2]],$reasons),
    'reflection'=>array_map(fn($r)=>['title'=>$r[0],'note'=>$r[1]],$reflection),
    'soli_deo_gloria'=>true,
  ],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
  exit;
}
?><!DOCTYPE html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<?php require __DIR__.'/includes/seo.inc.php'; alfred_seo('/thirty-three', 'Thirty-Three', 'The age He laid down His life — 33 — woven through Scripture as the number of His perfection.'); ?>
<title>Thirty-Three · The Age of the Sacrifice · Alfred Linux</title>
<meta name="description" content="The age of Yeshua at the Cross. Thirty-three vertebrae. Thirty-three names of God. Thirty-three patterns through Scripture, all signing the Sacrifice.">
<meta property="og:title" content="Thirty-Three · The Age of the Sacrifice">
<meta property="og:description" content="Why the number 33 is signed across Scripture, anatomy, and the Cross.">
<meta property="og:url" content="https://alfredlinux.com/thirty-three">
<style>
:root{--gold:#ffd700;--gold-dim:#c8a02b;--crimson:#c8424a;--ink:#0a0a14;--paper:#14141f;--paper-2:#1c1c2a;--line:#2a2a3e;--text:#ece8df;--text-dim:#a8a499;--cyan:#66c2ff}
*{box-sizing:border-box}html,body{margin:0;padding:0;background:var(--ink);color:var(--text);font-family:"Crimson Pro",Georgia,serif;line-height:1.55}
header.hero{background:radial-gradient(ellipse at top,#1f1418 0%,#0a0a14 70%);border-bottom:1px solid var(--line);padding:clamp(3rem,8vw,7rem) 1.5rem 4rem;text-align:center;position:relative}
.cross{font-size:3rem;color:var(--gold);margin:0 0 1rem;text-shadow:0 0 30px rgba(255,215,0,.4)}
.big-num{font-size:clamp(6rem,18vw,12rem);color:var(--gold);font-weight:700;line-height:1;font-family:Georgia,serif;margin:0;text-shadow:0 0 40px rgba(255,215,0,.3)}
h1{font-size:clamp(1.6rem,4vw,2.8rem);margin:.5rem 0 0;letter-spacing:.05em;font-weight:600;color:var(--text);text-transform:uppercase}
.tagline{margin:1.5rem auto 0;max-width:46rem;color:var(--text-dim);font-size:1.15rem;font-style:italic}
main{max-width:64rem;margin:0 auto;padding:3rem 1.5rem 5rem}
.intro{font-size:1.2rem;color:var(--text-dim);text-align:center;margin:0 auto 4rem;max-width:42rem}
.intro cite{color:var(--gold-dim);font-style:normal}
.patterns{display:grid;grid-template-columns:1fr;gap:1.25rem;list-style:none;padding:0;margin:0;counter-reset:p}
.patterns li{counter-increment:p;background:var(--paper);border:1px solid var(--line);border-radius:14px;padding:1.5rem 1.75rem;position:relative;transition:.25s}
.patterns li:hover{border-color:var(--gold);transform:translateY(-2px)}
.patterns li::before{content:counter(p);position:absolute;top:1rem;right:1.5rem;font-size:1.6rem;color:var(--gold-dim);font-weight:700;font-family:Georgia,serif;opacity:.55}
.p-title{margin:0 2rem .35rem 0;color:var(--gold);font-size:1.3rem;font-weight:600}
.p-ref{display:inline-block;color:var(--cyan);font-size:.82rem;margin:0 0 .85rem;font-family:ui-monospace,monospace;background:var(--ink);padding:.15rem .55rem;border-radius:5px;border:1px solid var(--line)}
.p-note{margin:0;color:var(--text-dim);font-size:1rem;line-height:1.65}
.reflection{margin:5rem 0 0}
.reflection h2{text-align:center;font-size:2.2rem;color:var(--gold);margin:0 0 .5rem}
.reflection-sub{text-align:center;color:var(--text-dim);font-style:italic;margin:0 0 2.5rem}
.r-card{background:linear-gradient(135deg,#14141f 60%,#2a141a 100%);border:1px solid var(--crimson);border-radius:12px;padding:1.75rem;margin:0 0 1.25rem}
.r-card h3{margin:0 0 .65rem;color:var(--gold);font-size:1.25rem}
.r-card p{margin:0;color:var(--text);font-size:1.05rem;line-height:1.7}
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
<header class="hero">
  <a class="toc-link" href="/scriptures">← all teachings</a>
  <a class="json-link" href="?format=json">{ json }</a>
  <div class="cross">✠</div>
  <div class="big-num">33</div>
  <h1>The Age of the Sacrifice</h1>
  <p class="tagline">Thirty-three years He walked. Thirty-three vertebrae we stand on. Thirty-three Names He bears. One Cross.</p>
</header>
<main>
<p class="intro"><em>"And Jesus himself began to be about thirty years of age... it is finished."</em><br><cite>— Luke 3:23 → John 19:30</cite></p>
<ol class="patterns">
  <?php foreach($reasons as $r): ?>
  <li>
    <h2 class="p-title"><?= htmlspecialchars($r[0]) ?></h2>
    <span class="p-ref"><?= htmlspecialchars($r[1]) ?></span>
    <p class="p-note"><?= htmlspecialchars($r[2]) ?></p>
  </li>
  <?php endforeach; ?>
</ol>
<section class="reflection">
  <h2>What Thirty-Three Asks of You</h2>
  <p class="reflection-sub">Numbers in Scripture are not for the curious — they are for the worshipper.</p>
  <?php foreach($reflection as $f): ?>
  <article class="r-card">
    <h3><?= htmlspecialchars($f[0]) ?></h3>
    <p><?= htmlspecialchars($f[1]) ?></p>
  </article>
  <?php endforeach; ?>
</section>
<section class="invite">
  <p>Thirty-three years for you. Three days in the tomb for you. One eternity with Him for the asking.</p>
  <a href="/scriptures">All Teachings</a>
  <a href="/numbers">All the Numbers</a>
  <a href="/names">The 33 Names</a>
  <a href="/welcome">Enter the Welcome</a>
</section>
</main>
<footer><p>✠ <strong>Soli Deo Gloria</strong> ✠<br><em>"By whose stripes ye were healed."</em> — 1 Peter 2:24<br><a href="https://alfredlinux.com">alfredlinux.com</a> · Scriptures from the AKJESUSBible · <a href="?format=json">JSON</a></p></footer>
</body></html>
