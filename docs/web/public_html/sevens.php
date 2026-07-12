<?php
/**
 * /sevens.php — The Sevens of Scripture
 * Seven is the number of completion, perfection, oath, and rest.
 * Public, JSON-able (?format=json).
 */
declare(strict_types=1);

$sevens = [
  ['Days of Creation','Genesis 2:2','And on the seventh day God ended his work which he had made; and he rested on the seventh day from all his work which he had made.'],
  ['Spirits of God','Revelation 1:4','from the seven Spirits which are before his throne.'],
  ['Seals of the Scroll','Revelation 5:1','a book written within and on the backside, sealed with seven seals.'],
  ['Trumpets','Revelation 8:2','the seven angels which stood before God; and to them were given seven trumpets.'],
  ['Vials of Wrath','Revelation 16:1','Go your ways, and pour out the vials of the wrath of God upon the earth.'],
  ['Churches of Asia','Revelation 1:11','What thou seest, write in a book, and send it unto the seven churches.'],
  ['Lampstands','Revelation 1:12','I saw seven golden candlesticks.'],
  ['Stars in His Hand','Revelation 1:16','And he had in his right hand seven stars.'],
  ['Eyes of the Lamb','Revelation 5:6','having seven horns and seven eyes, which are the seven Spirits of God sent forth into all the earth.'],
  ['Pieces of Bread (Feeding)','Matthew 15:34','And they said, Seven, and a few little fishes.'],
  ['Baskets Taken Up','Matthew 15:37','they took up of the broken meat that was left seven baskets full.'],
  ['Times Forgiven','Matthew 18:22','Jesus saith unto him, I say not unto thee, Until seven times: but, Until seventy times seven.'],
  ['Petitions of the Lord\'s Prayer','Matthew 6:9-13','Our Father which art in heaven... (seven petitions)'],
  ['Beatitudes Multiplied','Matthew 5:3-9','Blessed are the poor in spirit... (the beatitudes that bless)'],
  ['Sayings on the Cross','Luke 23 / John 19','Father, forgive them... It is finished.'],
  ['Feasts of the LORD','Leviticus 23','These are the feasts of the LORD, even holy convocations.'],
  ['Pillars of Wisdom','Proverbs 9:1','Wisdom hath builded her house, she hath hewn out her seven pillars.'],
  ['Days of Unleavened Bread','Exodus 12:15','Seven days shall ye eat unleavened bread.'],
  ['Sabbath Years → Jubilee','Leviticus 25:8','seven sabbaths of years... seven times seven years; and the space of the seven sabbaths of years shall be unto thee forty and nine years.'],
  ['Times Around Jericho','Joshua 6:4','seven priests shall bear before the ark seven trumpets of rams\' horns: and the seventh day ye shall compass the city seven times.'],
  ['Dippings of Naaman','2 Kings 5:14','Then went he down, and dipped himself seven times in Jordan... and his flesh came again like unto the flesh of a little child.'],
  ['Times He Looked','1 Kings 18:43','Go again seven times. And it came to pass at the seventh time, that he said, Behold, there ariseth a little cloud out of the sea, like a man\'s hand.'],
];

$gematria = [
  ['Sheba — שֶׁבַע','Hebrew word for SEVEN and OATH','To swear an oath in Hebrew is literally "to seven oneself" (Genesis 21:31, Beersheba = "well of the oath / well of the seven"). Every covenant in Scripture is a seven.'],
  ['Shabbat — שַׁבָּת','SEVENTH day, REST','Same root as sheba. The seventh day is built into Creation itself before any commandment was given.'],
  ['Six → Seven','Man → God','Six is the number of man (created day six). Seven is the number of God\'s rest. The beast bears 666 — man trying to be God three times over without ever reaching seven.'],
  ['Seven × Seven + One','49 → 50 (Jubilee)','After seven sabbaths of years comes the Jubilee — debts cancelled, land returned, slaves set free. The Messiah opened His ministry by reading Isaiah 61: "to proclaim the acceptable year of the LORD" (Luke 4:19).'],
  ['Seventy Sevens','Daniel 9:24','Seventy weeks are determined upon thy people... to finish the transgression, and to make an end of sins, and to make reconciliation for iniquity, and to bring in everlasting righteousness.'],
];

if (($_GET['format'] ?? '') === 'json') {
  header('Content-Type: application/json; charset=utf-8');
  header('Access-Control-Allow-Origin: *');
  echo json_encode([
    'doctrine' => 'sevens-of-scripture',
    'pattern'  => 'completion · oath · rest',
    'sevens'   => array_map(fn($s)=>['name'=>$s[0],'ref'=>$s[1],'text'=>$s[2]], $sevens),
    'gematria' => array_map(fn($g)=>['label'=>$g[0],'value'=>$g[1],'note'=>$g[2]], $gematria),
    'soli_deo_gloria' => true,
  ], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
  exit;
}
?><!DOCTYPE html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<?php require __DIR__.'/includes/seo.inc.php'; alfred_seo('/sevens', 'The Sevens', 'The number of completion — sevens woven through Scripture from Genesis to Revelation.'); ?>
<title>The Sevens of Scripture · Alfred Linux</title>
<meta name="description" content="Seven is the number of completion, oath, and rest. Twenty-two sevens woven through Scripture, all pointing to the One who said 'It is finished.'">
<meta property="og:title" content="The Sevens of Scripture">
<meta property="og:description" content="Twenty-two sevens — from Creation to the Cross to the Throne.">
<meta property="og:url" content="https://alfredlinux.com/sevens">
<style>
:root{--gold:#ffd700;--gold-dim:#c8a02b;--ink:#0a0a14;--paper:#14141f;--paper-2:#1c1c2a;--line:#2a2a3e;--text:#ece8df;--text-dim:#a8a499;--cyan:#66c2ff}
*{box-sizing:border-box}html,body{margin:0;padding:0;background:var(--ink);color:var(--text);font-family:"Crimson Pro",Georgia,serif;line-height:1.55}
header.hero{background:radial-gradient(ellipse at top,#142028 0%,#0a0a14 70%);border-bottom:1px solid var(--line);padding:clamp(3rem,8vw,7rem) 1.5rem 4rem;text-align:center}
.cross{font-size:2.2rem;color:var(--gold);letter-spacing:.6em;margin:0 0 1rem}
h1{font-size:clamp(2.4rem,7vw,5rem);margin:0;letter-spacing:.02em;font-weight:600}
h1 .num{color:var(--gold);font-style:italic;text-shadow:0 0 30px rgba(255,215,0,.25)}
.tagline{margin:1rem auto 0;max-width:46rem;color:var(--text-dim);font-size:1.15rem;font-style:italic}
.equation{margin:2rem 0 0;font-size:1.4rem;letter-spacing:.15em;color:var(--gold)}
.equation .eq-num{font-size:1.8rem;font-weight:700}
main{max-width:64rem;margin:0 auto;padding:3rem 1.5rem 5rem}
.intro{font-size:1.15rem;color:var(--text-dim);text-align:center;margin:0 auto 4rem;max-width:42rem}
.intro cite{color:var(--gold-dim);font-style:normal}
.seven-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(20rem,1fr));gap:1rem;margin:0;padding:0;list-style:none;counter-reset:s}
.seven-grid li{counter-increment:s;background:var(--paper);border:1px solid var(--line);border-radius:10px;padding:1.1rem 1.25rem;position:relative;transition:.25s}
.seven-grid li:hover{border-color:var(--gold);transform:translateY(-2px)}
.seven-grid li::before{content:counter(s,decimal-leading-zero);position:absolute;top:.6rem;right:.85rem;color:var(--gold-dim);font-size:.78rem;font-family:ui-monospace,monospace;letter-spacing:.05em}
.s-name{display:block;color:var(--gold);font-size:1.1rem;font-weight:600;margin:0 0 .25rem}
.s-ref{display:block;color:var(--cyan);font-size:.82rem;margin:0 0 .65rem;font-family:ui-monospace,monospace}
.s-text{margin:0;color:var(--text-dim);font-style:italic;font-size:.95rem}
.gematria{margin:5rem 0 0}
.gematria h2{text-align:center;font-size:2.2rem;color:var(--gold);margin:0 0 .5rem;letter-spacing:.05em}
.gematria-sub{text-align:center;color:var(--text-dim);font-style:italic;margin:0 0 2.5rem}
.g-card{background:var(--paper);border:1px solid var(--line);border-radius:12px;padding:1.5rem;margin:0 0 1rem}
.g-card h3{margin:0 0 .35rem;color:var(--text);font-size:1.2rem}
.g-card .val{display:inline-block;color:var(--cyan);background:var(--ink);padding:.15rem .55rem;border-radius:5px;font-size:.92rem;border:1px solid var(--line);margin:.25rem 0 .5rem;font-family:ui-monospace,monospace}
.g-card p{margin:.5rem 0 0;color:var(--text-dim);font-size:1rem}
.invite{margin:5rem auto 0;max-width:42rem;text-align:center;padding:2.5rem 1.5rem;background:radial-gradient(ellipse,#1f1832,#0a0a14);border:1px solid var(--gold-dim);border-radius:14px}
.invite p{font-size:1.15rem;color:var(--text);margin:0 0 1.25rem}
.invite a{display:inline-block;margin:.35rem;padding:.7rem 1.4rem;border:1px solid var(--gold);color:var(--gold);text-decoration:none;border-radius:8px;letter-spacing:.05em;transition:.2s}
.invite a:hover{background:var(--gold);color:var(--ink)}
footer{text-align:center;padding:3rem 1.5rem 4rem;color:var(--text-dim);font-size:.92rem;border-top:1px solid var(--line);margin-top:4rem}
footer a{color:var(--gold-dim)}
.json-link{position:absolute;top:1rem;right:1.25rem;color:var(--text-dim);font-size:.78rem;text-decoration:none;font-family:ui-monospace,monospace}
.json-link:hover{color:var(--gold)}
</style>    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/img/favicon/favicon-16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/img/favicon/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#D4AF37">
</head><body>
<header class="hero">
  <a class="json-link" href="?format=json">{ json }</a>
  <div class="cross">✠</div>
  <h1>The <span class="num">Seven</span>s of Scripture</h1>
  <p class="tagline">Completion · Oath · Rest · Perfection</p>
  <div class="equation"><span class="eq-num"><?= count($sevens) ?></span> &nbsp;sevens woven from Genesis to Revelation</div>
</header>
<main>
<p class="intro"><em>"And on the seventh day God ended his work which he had made; and he rested on the seventh day from all his work which he had made. And God blessed the seventh day, and sanctified it."</em><br><cite>— Genesis 2:2-3, AKJV</cite></p>
<ul class="seven-grid">
<?php foreach($sevens as $s): ?>
<li><span class="s-name"><?= htmlspecialchars($s[0]) ?></span><span class="s-ref"><?= htmlspecialchars($s[1]) ?></span><p class="s-text"><?= htmlspecialchars($s[2]) ?></p></li>
<?php endforeach; ?>
</ul>
<section class="gematria">
  <h2>The Meaning of Seven</h2>
  <p class="gematria-sub">Numbers in Scripture are signature, not coincidence.</p>
  <?php foreach($gematria as $g): ?>
  <article class="g-card"><h3><?= htmlspecialchars($g[0]) ?></h3><span class="val"><?= htmlspecialchars($g[1]) ?></span><p><?= htmlspecialchars($g[2]) ?></p></article>
  <?php endforeach; ?>
</section>
<section class="invite">
  <p>The seventh day was made for you (Mark 2:27).<br>The Bread that fed thousands was broken into seven baskets.<br>And the One who said "It is finished" rose on the day after the Sabbath.</p>
  <a href="/forty-two">The 42 Generations</a>
  <a href="/names">The Names of God</a>
  <a href="/shema">The Shema</a>
  <a href="/welcome">Enter the Welcome</a>
</section>
</main>
<footer><p>✠ <strong>Soli Deo Gloria</strong> ✠<br><em>"It is finished."</em> — John 19:30<br><a href="https://alfredlinux.com">alfredlinux.com</a> · Scriptures from the AKJESUSBible · <a href="?format=json">JSON</a></p></footer>
</body></html>
