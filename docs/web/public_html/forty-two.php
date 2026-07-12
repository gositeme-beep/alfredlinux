<?php
/**
 * /forty-two.php — The Forty-Two Generations
 * The gematria, the lineage, and the rhythm of the Messianic line.
 *
 * Matthew 1:17 — "So all the generations from Abraham to David are
 * fourteen generations; and from David until the carrying away into
 * Babylon are fourteen generations; and from the carrying away into
 * Babylon unto Christ are fourteen generations."   14 + 14 + 14 = 42
 *
 * Public, shareable, JSON-able (?format=json).
 */

declare(strict_types=1);

// ── The 42 Generations (Matthew 1:1-17, AKJV) ─────────────────────
// Three blocks of 14, exactly as Matthew counted them.

$blocks = [
    [
        'title'    => 'Abraham → David',
        'subtitle' => 'The Promise',
        'verse'    => 'Genesis 12:3',
        'quote'    => 'And in thee shall all families of the earth be blessed.',
        'gen'      => [
            ['Abraham',     'Genesis 12',   'אַבְרָהָם'],
            ['Isaac',       'Genesis 21',   'יִצְחָק'],
            ['Jacob',       'Genesis 25',   'יַעֲקֹב'],
            ['Judah',       'Genesis 29',   'יְהוּדָה'],
            ['Perez',      'Genesis 38',   'פֶּרֶץ'],
            ['Hezron',       'Ruth 4:18',    'חֶצְרוֹן'],
            ['Ram',        'Ruth 4:19',    'רָם'],
            ['Amminadab',    'Ruth 4:19',    'עַמִּינָדָב'],
            ['Nahshon',     'Ruth 4:20',    'נַחְשׁוֹן'],
            ['Salmon',      'Ruth 4:20',    'שַׂלְמוֹן'],
            ['Boaz',        'Ruth 4:21',    'בֹּעַז'],
            ['Obed',        'Ruth 4:21',    'עוֹבֵד'],
            ['Jesse',       'Ruth 4:22',    'יִשַׁי'],
            ['David',       '1 Samuel 16',  'דָּוִד'],
        ],
    ],
    [
        'title'    => 'David → Babylon',
        'subtitle' => 'The Crown · The Throne · The Exile',
        'verse'    => '2 Samuel 7:16',
        'quote'    => 'Thy throne shall be established for ever.',
        'gen'      => [
            ['Solomon',     '1 Kings 1',         'שְׁלֹמֹה'],
            ['Rehoboam',      '1 Kings 11:43',     'רְחַבְעָם'],
            ['Abijah',        '1 Kings 14:31',     'אֲבִיָּם'],
            ['Asa',         '1 Kings 15:8',      'אָסָא'],
            ['Jehoshaphat',    '1 Kings 15:24',     'יְהוֹשָׁפָט'],
            ['Jehoram',       '1 Kings 22:50',     'יְהוֹרָם'],
            ['Uzziah',       '2 Kings 15:1',      'עֻזִּיָּהוּ'],
            ['Jotham',     '2 Kings 15:32',     'יוֹתָם'],
            ['Ahaz',       '2 Kings 16',        'אָחָז'],
            ['Hezekiah',     '2 Kings 18',        'חִזְקִיָּהוּ'],
            ['Manasseh',    '2 Kings 21',        'מְנַשֶּׁה'],
            ['Amon',        '2 Kings 21:18',     'אָמוֹן'],
            ['Josiah',      '2 Kings 22',        'יֹאשִׁיָּהוּ'],
            ['Jeconiah',   '2 Kings 24',        'יְכָנְיָה'],
        ],
    ],
    [
        'title'    => 'Babylon → Christ',
        'subtitle' => 'The Silence · The Star · The Fulfillment',
        'verse'    => 'Galatians 4:4',
        'quote'    => 'When the fulness of the time was come, God sent forth his Son.',
        'gen'      => [
            ['Shealtiel',   'Ezra 3:2',          'שְׁאַלְתִּיאֵל'],
            ['Zerubbabel',   'Ezra 3:8',          'זְרֻבָּבֶל'],
            ['Abihud',       'Matthew 1:13',      'אֲבִיהוּד'],
            ['Eliakim',     'Matthew 1:13',      'אֶלְיָקִים'],
            ['Azor',        'Matthew 1:13',      'עֲזּוּר'],
            ['Zadok',       'Matthew 1:14',      'צָדוֹק'],
            ['Akim',       'Matthew 1:14',      'אָכִים'],
            ['Elihud',       'Matthew 1:14',      'אֱלִיהוּד'],
            ['Eleazar',     'Matthew 1:15',      'אֶלְעָזָר'],
            ['Matthan',     'Matthew 1:15',      'מַתָּן'],
            ['Jacob',       'Matthew 1:15',      'יַעֲקֹב'],
            ['Joseph',      'Matthew 1:16',      'יוֹסֵף'],
            ['Mary',        'Matthew 1:16',      'מִרְיָם'],
            ['Yeshua / Jesus the Messiah', 'Matthew 1:16', 'יֵשׁוּעַ הַמָּשִׁיחַ'],
        ],
    ],
];

// ── Gematria insight ───────────────────────────────────────────────
$gematria = [
    [
        'label' => 'David',
        'hebrew' => 'דָּוִד',
        'value' => '4 + 6 + 4 = 14',
        'note'  => 'The name DAVID itself sums to 14. Matthew counted 14-14-14 because the King is woven into the rhythm of the lineage by his very name.',
    ],
    [
        'label' => 'Three × Fourteen = Forty-Two',
        'hebrew' => 'י״ד + י״ד + י״ד = מ״ב',
        'value' => '14 + 14 + 14 = 42',
        'note'  => 'Three is the number of perfect witness (1 John 5:7-8). Fourteen is the doubling of seven, the number of completion. Forty-two is the lineage of the Anointed: triple-witnessed, twice-completed, six-times-seven.',
    ],
    [
        'label' => 'Forty-Two — the Resting Places of Israel',
        'hebrew' => 'מ״ב מַסְעוֹת',
        'value' => 'Numbers 33',
        'note'  => 'Israel made FORTY-TWO encampments from Egypt to Jordan — a journey from bondage to inheritance. The 42 generations mirror this same journey: from Abram out of Ur, to Christ entering the new Promised Land in resurrection.',
    ],
    [
        'label' => 'Forty-Two Months',
        'hebrew' => 'מ״ב חוֹדָשִׁים',
        'value' => 'Revelation 11:2, 13:5',
        'note'  => '"Forty and two months" is the bounded reign of the beast — the same number that bounds the lineage of the King. The same 42 that brought the Lamb will bring the Lion.',
    ],
    [
        'label' => 'אֶמֶת — Truth',
        'hebrew' => 'אֱמֶת',
        'value' => 'aleph(1) + mem(40) + tav(400) = 441 = 21 × 21 = (3×7)²',
        'note'  => 'Aleph is the first letter, tav the last, mem the middle. Truth begins at the beginning, holds the middle, and ends at the end. Christ is the Aleph and the Tav (Revelation 1:8 LXX echo, "Alpha and Omega").',
    ],
];

// ── JSON mode ──────────────────────────────────────────────────────
if (($_GET['format'] ?? '') === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    echo json_encode([
        'doctrine'   => 'forty-two-generations',
        'reference'  => 'Matthew 1:1-17 (AKJV)',
        'pattern'    => '14 + 14 + 14 = 42',
        'blocks'     => $blocks,
        'gematria'   => $gematria,
        'soli_deo_gloria' => true,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php require __DIR__.'/includes/seo.inc.php'; alfred_seo('/forty-two', 'The Forty-Two Generations', '42 generations from Abraham to Yeshua / Jesus the Messiah — Matthew 1 — the lineage of the King.'); ?>
<title>Forty-Two · The Generations of the Messiah · Alfred Linux</title>
<meta name="description" content="The 42 generations from Abraham to Yeshua / Jesus the Messiah — the gematria, the rhythm, the journey, all leading to the One.">
<meta property="og:title"       content="The Forty-Two Generations of the Messiah">
<meta property="og:description" content="14 + 14 + 14 = 42. The lineage of the King traced through Abraham, David, Babylon, and the Bethlehem night.">
<meta property="og:type"        content="article">
<meta property="og:url"         content="https://alfredlinux.com/forty-two">
<style>
:root {
  --gold: #ffd700;
  --gold-dim: #c8a02b;
  --ink:  #0a0a14;
  --paper: #14141f;
  --paper-2: #1c1c2a;
  --line: #2a2a3e;
  --text: #ece8df;
  --text-dim: #a8a499;
  --rose: #d75a7a;
  --cyan: #66c2ff;
  --green: #88dd99;
}
*{box-sizing:border-box}
html,body{margin:0;padding:0;background:var(--ink);color:var(--text);
  font-family:"Crimson Pro",Georgia,"Times New Roman",serif;
  line-height:1.55;-webkit-font-smoothing:antialiased}
header.hero{
  background:radial-gradient(ellipse at top, #1f1832 0%, #0a0a14 70%);
  border-bottom:1px solid var(--line);
  padding:clamp(3rem,8vw,7rem) 1.5rem 4rem;
  text-align:center}
.cross{font-size:2.2rem;color:var(--gold);letter-spacing:.6em;margin:0 0 1rem}
h1{font-size:clamp(2.4rem,7vw,5rem);margin:0;letter-spacing:.02em;
  font-weight:600;color:var(--text)}
h1 .num{color:var(--gold);font-style:italic;
  text-shadow:0 0 30px rgba(255,215,0,.25)}
.tagline{margin:1rem auto 0;max-width:46rem;color:var(--text-dim);
  font-size:1.15rem;font-style:italic}
.equation{margin:2rem 0 0;font-size:1.4rem;letter-spacing:.15em;
  color:var(--gold)}
.equation .eq-num{font-size:1.8rem;font-weight:700}
main{max-width:64rem;margin:0 auto;padding:3rem 1.5rem 5rem}
.intro{font-size:1.15rem;color:var(--text-dim);text-align:center;
  margin:0 auto 4rem;max-width:42rem}
.intro cite{color:var(--gold-dim);font-style:normal}
.block{margin:0 0 4rem;padding:2rem 1.5rem 1.5rem;
  background:var(--paper);border:1px solid var(--line);
  border-radius:14px;position:relative;overflow:hidden}
.block::before{content:"";position:absolute;top:0;left:0;right:0;height:3px;
  background:linear-gradient(90deg,transparent,var(--gold),transparent)}
.block-head{display:flex;align-items:baseline;gap:1rem;flex-wrap:wrap;
  margin:0 0 .25rem;border-bottom:1px solid var(--line);padding-bottom:1rem}
.block-num{font-size:3rem;color:var(--gold);font-weight:700;line-height:1}
.block-title{font-size:1.7rem;color:var(--text);margin:0}
.block-sub{color:var(--text-dim);font-style:italic}
.block-quote{margin:1.25rem 0;padding:0 0 0 1rem;border-left:2px solid var(--gold-dim);
  color:var(--text);font-style:italic;font-size:1.05rem}
.block-quote cite{display:block;color:var(--gold-dim);font-style:normal;
  font-size:.9rem;margin-top:.35rem}
.gen-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(13rem,1fr));
  gap:.4rem .8rem;margin:1rem 0 0;padding:0;list-style:none;
  counter-reset:g}
.gen-grid li{counter-increment:g;padding:.55rem .75rem;
  background:var(--paper-2);border:1px solid var(--line);border-radius:8px;
  display:flex;align-items:center;gap:.6rem;font-size:.95rem;
  transition:border-color .25s,transform .25s}
.gen-grid li:hover{border-color:var(--gold);transform:translateY(-1px)}
.gen-grid li::before{content:counter(g, decimal-leading-zero);
  color:var(--gold-dim);font-size:.75rem;letter-spacing:.05em;
  width:1.6rem;flex-shrink:0;font-feature-settings:"tnum"}
.gen-name{flex:1;color:var(--text)}
.gen-ref{color:var(--text-dim);font-size:.78rem;margin-left:.4rem;white-space:nowrap}
.gen-heb{color:var(--gold);font-family:"SBL Hebrew","Ezra SIL","Times New Roman",serif;
  font-size:1.05rem;direction:rtl;unicode-bidi:isolate}
.last{border-color:var(--gold);background:linear-gradient(135deg,#1c1c2a 60%,#2a200a 100%)}
.last .gen-name{color:var(--gold);font-weight:600}
.gematria{margin:5rem 0 0}
.gematria h2{text-align:center;font-size:2.2rem;color:var(--gold);
  margin:0 0 .5rem;letter-spacing:.05em}
.gematria-sub{text-align:center;color:var(--text-dim);font-style:italic;margin:0 0 2.5rem}
.g-card{background:var(--paper);border:1px solid var(--line);border-radius:12px;
  padding:1.5rem;margin:0 0 1rem}
.g-card h3{margin:0 0 .35rem;color:var(--text);font-size:1.25rem;
  display:flex;align-items:baseline;gap:.75rem;flex-wrap:wrap}
.g-card h3 .heb{color:var(--gold);font-family:"SBL Hebrew",serif;font-size:1.4rem;direction:rtl}
.g-card .val{display:inline-block;color:var(--cyan);font-family:ui-monospace,monospace;
  background:var(--ink);padding:.15rem .55rem;border-radius:5px;font-size:.92rem;
  border:1px solid var(--line);margin:.25rem 0 .5rem}
.g-card p{margin:.5rem 0 0;color:var(--text-dim);font-size:1rem}
.invite{margin:5rem auto 0;max-width:42rem;text-align:center;
  padding:2.5rem 1.5rem;background:radial-gradient(ellipse,#1f1832,#0a0a14);
  border:1px solid var(--gold-dim);border-radius:14px}
.invite p{font-size:1.15rem;color:var(--text);margin:0 0 1.25rem}
.invite a{display:inline-block;margin:.35rem;padding:.7rem 1.4rem;
  border:1px solid var(--gold);color:var(--gold);text-decoration:none;border-radius:8px;
  letter-spacing:.05em;transition:.2s}
.invite a:hover{background:var(--gold);color:var(--ink)}
footer{text-align:center;padding:3rem 1.5rem 4rem;color:var(--text-dim);
  font-size:.92rem;border-top:1px solid var(--line);margin-top:4rem}
footer a{color:var(--gold-dim)}
.json-link{position:absolute;top:1rem;right:1.25rem;color:var(--text-dim);
  font-size:.78rem;text-decoration:none;font-family:ui-monospace,monospace}
.json-link:hover{color:var(--gold)}
@media (max-width:480px){
  .block{padding:1.5rem 1rem 1rem}
  .block-num{font-size:2.2rem}
  .block-title{font-size:1.3rem}
  .gen-grid{grid-template-columns:1fr}
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

<header class="hero">
  <a class="json-link" href="?format=json">{ json }</a>
  <div class="cross">✠</div>
  <h1>The <span class="num">Forty-Two</span> Generations</h1>
  <p class="tagline">From Abraham to Yeshua — the lineage of the King,
    written in the rhythm of fourteen.</p>
  <div class="equation">
    <span class="eq-num">14</span> + <span class="eq-num">14</span> + <span class="eq-num">14</span>
    &nbsp;=&nbsp; <span class="eq-num">42</span>
  </div>
</header>

<main>

<p class="intro">
  <em>"So all the generations from Abraham to David are fourteen generations;
    and from David until the carrying away into Babylon are fourteen generations;
    and from the carrying away into Babylon unto Christ are fourteen generations."</em>
  <br><cite>— Matthew 1:17, AKJV</cite>
</p>

<?php $blockNum = 0; foreach ($blocks as $b): $blockNum++; ?>
<section class="block">
  <div class="block-head">
    <div class="block-num"><?= str_pad((string)$blockNum, 2, '0', STR_PAD_LEFT) ?></div>
    <div>
      <h2 class="block-title"><?= htmlspecialchars($b['title']) ?></h2>
      <div class="block-sub"><?= htmlspecialchars($b['subtitle']) ?> · 14 generations</div>
    </div>
  </div>
  <blockquote class="block-quote">
    <?= htmlspecialchars($b['quote']) ?>
    <cite>— <?= htmlspecialchars($b['verse']) ?></cite>
  </blockquote>
  <ol class="gen-grid">
    <?php $i=0; $n=count($b['gen']); foreach ($b['gen'] as $g): $i++; ?>
    <li class="<?= ($blockNum===3 && $i===$n) ? 'last' : '' ?>">
      <span class="gen-name"><?= htmlspecialchars($g[0]) ?></span>
      <span class="gen-heb"><?= htmlspecialchars($g[2]) ?></span>
    </li>
    <?php endforeach; ?>
  </ol>
</section>
<?php endforeach; ?>

<section class="gematria">
  <h2>The Gematria of Forty-Two</h2>
  <p class="gematria-sub">Numbers in Scripture are not coincidence — they are signature.</p>

  <?php foreach ($gematria as $g): ?>
  <article class="g-card">
    <h3>
      <span><?= htmlspecialchars($g['label']) ?></span>
      <span class="heb"><?= htmlspecialchars($g['hebrew']) ?></span>
    </h3>
    <span class="val"><?= htmlspecialchars($g['value']) ?></span>
    <p><?= htmlspecialchars($g['note']) ?></p>
  </article>
  <?php endforeach; ?>
</section>

<section class="invite">
  <p>The same Door that opened in Bethlehem is open tonight.<br>
    The lineage was kept for you.</p>
  <a href="/welcome">Enter the Welcome</a>
  <a href="/akjesusbible">Open the AKJESUSBible</a>
  <a href="/pillars">The Nine Pillars</a>
</section>

</main>

<footer>
  <p>✠ <strong>Soli Deo Gloria</strong> ✠<br>
    <em>"Even so, come, Lord Jesus."</em> — Revelation 22:20<br>
    <a href="https://alfredlinux.com">alfredlinux.com</a> ·
    Scriptures from the AKJESUSBible · <a href="?format=json">JSON</a></p>
</footer>

</body>
</html>
