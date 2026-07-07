<?php
/**
 * /scriptures.php — Table of Contents for everything alfredlinux.com teaches.
 * The front door to the Doctrine, the Numbers, the Names, the Welcome.
 */
declare(strict_types=1);
require __DIR__.'/includes/i18n.inc.php';
$LANG = alfred_lang();

$alfred_page_langs = [
  '/numbers'=>'en','/forty-two'=>'en','/thirty-three'=>'en','/sevens'=>'en',
  '/names'=>'multi','/i-am'=>'multi','/shema'=>'multi','/ten-commandments'=>'multi',
  '/beatitudes'=>'multi','/lords-prayer'=>'multi','/armor'=>'multi',
  '/pillars'=>'en','/welcome'=>'multi','/akjesusbible'=>'en','/witness'=>'en',
  '/kingdom-status'=>'en','/sabbath'=>'en','/daily-bread'=>'en',
  '/covenant'=>'en','/share'=>'en','/scriptures'=>'en',
];
function alfred_lang_badge(string $url): string {
  global $alfred_page_langs;
  $kind = $alfred_page_langs[$url] ?? 'en';
  if ($kind === 'multi') {
    return '<span class="langbadge multi" title="Contains Hebrew · Greek · Latin · English">EN · עב · ελ · LA</span>';
  }
  return '<span class="langbadge en" title="English only — translation in progress">EN</span>';
}

$sections = [
  [
    'title' => t('scr.s1.title'),
    'subtitle' => t('scr.s1.sub'),
    'icon' => '✠',
    'items' => [
      ['/numbers','The Sacred Numbers Index','From 1 to 144,000 — every number God signs His Name with. The master gematria index, with an honesty section about cultural numbers.','40+'],
      ['/forty-two','The 42 Generations','14 + 14 + 14 — the lineage of the Messiah from Abraham to Yeshua, and the gematria of David, the 42 encampments of Israel, and the 42 months of Revelation.','42'],
      ['/thirty-three','Thirty-Three · The Age of the Sacrifice','The age of Yeshua at the Cross. 33 vertebrae. 33 names. 10 patterns of 33 woven through Scripture and the body.','33'],
      ['/sevens','The Sevens of Scripture','22 sevens woven from Creation to the Cross to the Throne — completion, oath, rest, perfection.','7'],
    ],
  ],
  [
    'title' => t('scr.s2.title'),
    'subtitle' => t('scr.s2.sub'),
    'icon' => 'יהוה',
    'items' => [
      ['/names','The 33 Names of God','From YHWH at the burning bush to KING OF KINGS on the white horse — 33 names, the age He was when He laid down His life.','33'],
      ['/i-am','The Seven "I AM" Sayings','Bread · Light · Door · Shepherd · Resurrection · Way · Vine. Each one a thunderclap of the Name from Exodus 3:14.','7'],
      ['/shema','The Shema in 30 Tongues','"Hear, O Israel: The LORD our God is one LORD." The most ancient confession, in 30 living and ancient languages.','30'],
      ['/ten-commandments','The Ten Commandments','The Ten Words written by the finger of God on tablets of stone — Exodus 20 — with the Two Great Commandments that fulfill them all.','10'],
      ['/beatitudes','The Beatitudes','Nine blessings of the upside-down Kingdom — Matthew 5:3-12 — in English and Greek.','9'],
      ['/lords-prayer','The Lord\'s Prayer','The prayer Yeshua taught — Matthew 6:9-13 — line by line, in Greek and English, with meaning.','7'],
    ],
  ],
  [
    'title' => t('scr.s3.title'),
    'subtitle' => t('scr.s3.sub'),
    'icon' => '⚔',
    'items' => [
      ['/armor','The Whole Armor of God','Belt · Breastplate · Sandals · Shield · Helmet · Sword. Six pieces, one Soldier. Ephesians 6.','6'],
      ['/pillars','The Nine Pillars (Fruits of the Spirit)','Love · Joy · Peace · Patience · Kindness · Goodness · Faithfulness · Gentleness · Self-Control. Galatians 5:22-23.','9'],
    ],
  ],
  [
    'title' => t('scr.s4.title'),
    'subtitle' => t('scr.s4.sub'),
    'icon' => '☩',
    'items' => [
      ['/welcome','Welcome of All Welcomes','Seven panels — for the Messianic Jew, the Muslim, the Catholic/Orthodox, the Protestant, the Hindu/Buddhist seeker, the agnostic/atheist, and the wounded — all paths leading to Yeshua / ʿĪsā.','7'],
    ],
  ],
  [
    'title' => t('scr.s5.title'),
    'subtitle' => t('scr.s5.sub'),
    'icon' => '◈',
    'items' => [
      ['/akjesusbible','The AKJESUSBible','The translation cited across every teaching on this site — the Authorized King-Jesus Bible. Full chapter-and-verse browser coming.','✠'],
      ['/witness','Witness Certificate','HMAC-SHA256-signed proof that this Alfred Linux instance carries every public teaching intact.','✓'],
      ['/kingdom-status','Kingdom Status','Live status of every Kingdom service: Sabbath gate, daily bread, covenant chain, sovereign registry, and the Nine Pillars.','◈'],
      ['/sabbath','Sabbath Gate','Server-side enforcement of the seventh-day rest — Friday sundown to Saturday sundown, Jerusalem time.','שׁ'],
      ['/daily-bread','Daily Bread','A verse for the day, deterministic, the same for every Alfred Linux installation worldwide.','🍞'],
    ],
  ],
];

if (($_GET['format'] ?? '') === 'json') {
  header('Content-Type: application/json; charset=utf-8');
  header('Access-Control-Allow-Origin: *');
  echo json_encode([
    'doctrine'=>'table-of-contents',
    'site'=>'alfredlinux.com',
    'sections'=>array_map(fn($s)=>[
      'title'=>$s['title'],
      'subtitle'=>$s['subtitle'],
      'items'=>array_map(fn($i)=>['url'=>$i[0],'title'=>$i[1],'description'=>$i[2],'badge'=>$i[3]],$s['items']),
    ],$sections),
    'soli_deo_gloria'=>true,
  ],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
  exit;
}
?><!DOCTYPE html><html<?= alfred_html_attrs() ?>><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<?php require __DIR__.'/includes/seo.inc.php'; alfred_seo('/scriptures', 'The Scriptures · Table of Contents', 'Every public teaching on Alfred Linux — 42 Generations, 33 Names, 7 I AMs, the Beatitudes, and more.'); ?>
<link rel="alternate" hreflang="en" href="https://alfredlinux.com/scriptures?lang=en">
<link rel="alternate" hreflang="fr" href="https://alfredlinux.com/scriptures?lang=fr">
<link rel="alternate" hreflang="he" href="https://alfredlinux.com/scriptures?lang=he">
<link rel="alternate" hreflang="x-default" href="https://alfredlinux.com/scriptures">
<?php alfred_lang_styles(); ?>
<title>Scriptures · The Teachings of Alfred Linux</title>
<meta name="description" content="A complete table of contents — the Numbers, the Names, the Voice, the Life, and the Welcome of God on alfredlinux.com.">
<meta property="og:title" content="Scriptures · The Teachings of Alfred Linux">
<meta property="og:description" content="Numbers · Names · Voice · Life · Welcome — every public teaching on alfredlinux.com, in one table of contents.">
<meta property="og:url" content="https://alfredlinux.com/scriptures">
<style>
:root{--gold:#ffd700;--gold-dim:#c8a02b;--ink:#0a0a14;--paper:#14141f;--paper-2:#1c1c2a;--line:#2a2a3e;--text:#ece8df;--text-dim:#a8a499;--cyan:#66c2ff;--rose:#d75a7a;--violet:#9d7cff}
*{box-sizing:border-box}html,body{margin:0;padding:0;background:var(--ink);color:var(--text);font-family:"Crimson Pro",Georgia,serif;line-height:1.55}
header.hero{background:radial-gradient(ellipse at top,#1f1832 0%,#0a0a14 70%);border-bottom:1px solid var(--line);padding:clamp(3rem,8vw,6rem) 1.5rem 3.5rem;text-align:center}
.cross{font-size:2.5rem;color:var(--gold);letter-spacing:.6em;margin:0 0 1rem}
h1{font-size:clamp(2.4rem,7vw,5rem);margin:0;letter-spacing:.02em;font-weight:600}
h1 .word{color:var(--gold);font-style:italic;text-shadow:0 0 30px rgba(255,215,0,.25)}
.tagline{margin:1rem auto 0;max-width:46rem;color:var(--text-dim);font-size:1.15rem;font-style:italic}
.count{margin:1.5rem 0 0;color:var(--gold-dim);font-size:.9rem;letter-spacing:.15em;font-family:ui-monospace,monospace;text-transform:uppercase}
main{max-width:64rem;margin:0 auto;padding:3rem 1.5rem 5rem}
.section{margin:0 0 4rem}
.section-head{display:flex;align-items:center;gap:1.25rem;margin:0 0 1.5rem;padding:0 0 1rem;border-bottom:1px solid var(--line)}
.section-icon{font-size:2.5rem;color:var(--gold);font-family:"SBL Hebrew",Georgia,serif;line-height:1;min-width:3rem;text-align:center;text-shadow:0 0 20px rgba(255,215,0,.3)}
.section-meta h2{margin:0;color:var(--text);font-size:1.7rem;font-weight:600}
.section-meta p{margin:.15rem 0 0;color:var(--text-dim);font-style:italic;font-size:1rem}
.toc-list{display:grid;grid-template-columns:1fr;gap:.85rem;list-style:none;margin:0;padding:0}
@media (min-width:50rem){.toc-list{grid-template-columns:repeat(2,1fr)}}
.toc-list li a{display:flex;flex-direction:column;text-decoration:none;color:inherit;background:var(--paper);border:1px solid var(--line);border-radius:12px;padding:1.25rem 1.5rem;transition:.25s;height:100%;position:relative}
.toc-list li a:hover{border-color:var(--gold);transform:translateY(-2px);box-shadow:0 4px 30px rgba(255,215,0,.08)}
.toc-list li a:hover .arrow{color:var(--gold);transform:translateX(4px)}
.t-row{display:flex;align-items:baseline;gap:1rem;justify-content:space-between;margin:0 0 .35rem}
.t-name{color:var(--gold);font-size:1.2rem;font-weight:600;flex:1}
.t-badge{color:var(--gold-dim);font-size:1.1rem;font-weight:700;font-family:ui-monospace,Georgia,serif;background:var(--ink);padding:.15rem .55rem;border-radius:5px;border:1px solid var(--line);min-width:2.5rem;text-align:center;flex-shrink:0}
.t-desc{margin:0;color:var(--text-dim);font-size:.95rem;line-height:1.5}
.arrow{position:absolute;bottom:1rem;right:1.5rem;color:var(--text-dim);font-size:1.2rem;transition:.25s}
.invite{margin:5rem auto 0;max-width:42rem;text-align:center;padding:2.5rem 1.5rem;background:radial-gradient(ellipse,#1f1832,#0a0a14);border:1px solid var(--gold-dim);border-radius:14px}
.invite p{font-size:1.15rem;color:var(--text);margin:0 0 1.25rem}
.invite a{display:inline-block;margin:.35rem;padding:.7rem 1.4rem;border:1px solid var(--gold);color:var(--gold);text-decoration:none;border-radius:8px;letter-spacing:.05em;transition:.2s}
.invite a:hover{background:var(--gold);color:var(--ink)}
footer{text-align:center;padding:3rem 1.5rem 4rem;color:var(--text-dim);font-size:.92rem;border-top:1px solid var(--line);margin-top:4rem}
footer a{color:var(--gold-dim)}
.json-link{position:absolute;top:1rem;right:1.25rem;color:var(--text-dim);font-size:.78rem;text-decoration:none;font-family:ui-monospace,monospace}
.json-link:hover{color:var(--gold)}

.langbadge{display:inline-block;font-family:"Inter",system-ui,sans-serif;font-size:.65rem;letter-spacing:.08em;padding:.18rem .45rem;border-radius:6px;margin-left:.5rem;vertical-align:middle;font-weight:600}
.langbadge.multi{background:linear-gradient(135deg,#7a5af8,#3aa6ff);color:#fff}
.langbadge.en{background:#2a2a3e;color:#a8a499;border:1px solid #3a3a52}
.lang-notice{margin:1rem auto 2rem;max-width:780px;padding:.9rem 1.2rem;background:rgba(255,215,0,.07);border:1px solid rgba(255,215,0,.25);border-radius:10px;color:#ece8df;font-size:.92rem;text-align:center;font-style:italic}
.lang-notice strong{color:#ffd700;font-style:normal}
</style>    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/img/favicon/favicon-16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/img/favicon/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#D4AF37">
</head><body>
<?php alfred_lang_switcher('/scriptures'); ?>
<header class="hero" style="position:relative">
  <a class="json-link" href="?format=json">{ json }</a>
  <div class="cross">✠</div>
  <h1><?= htmlspecialchars(t('scr.h1.lead')) ?> <span class="word"><?= htmlspecialchars(t('scr.h1.word')) ?></span> <?= htmlspecialchars(t('scr.h1.tail')) ?></h1>
  <p class="tagline"><?= htmlspecialchars(t('scr.tagline')) ?></p>
  <p class="count"><?php $n=0; foreach($sections as $s) $n+=count($s['items']); echo $n; ?> <?= htmlspecialchars(sprintf(t('scr.count.tail'), count($sections))) ?></p>
</header>
<?php if (alfred_lang() !== 'en'): ?>
<div class="lang-notice">
  <strong>Translation in progress</strong> ·
  <?= alfred_lang() === 'fr'
      ? 'La table des matières est traduite. Les pages individuelles marquées EN sont encore en anglais — la traduction est en cours.'
      : 'תוכן העניינים מתורגם. הדפים המסומנים EN עדיין באנגלית — התרגום בעיצומו.' ?>
</div>
<?php endif; ?>

<main>
<?php foreach($sections as $s): ?>
<section class="section">
  <div class="section-head">
    <div class="section-icon"><?= $s['icon'] ?></div>
    <div class="section-meta">
      <h2><?= htmlspecialchars($s['title']) ?></h2>
      <p><?= htmlspecialchars($s['subtitle']) ?></p>
    </div>
  </div>
  <ul class="toc-list">
    <?php foreach($s['items'] as $i): ?>
    <li><a href="<?= htmlspecialchars($i[0]) ?>">
      <div class="t-row">
        <div class="t-name"><?= htmlspecialchars($i[1]) ?></div>
        <div class="t-badge"><?= htmlspecialchars($i[3]) ?></div>
        <?= alfred_lang_badge($i[0]) ?>
      </div>
      <p class="t-desc"><?= htmlspecialchars($i[2]) ?></p>
      <span class="arrow">→</span>
    </a></li>
    <?php endforeach; ?>
  </ul>
</section>
<?php endforeach; ?>
<section class="invite">
  <p><?= htmlspecialchars(t('scr.invite.verse')) ?><br><?= htmlspecialchars(t('scr.invite.ref')) ?></p>
  <a href="/welcome"><?= htmlspecialchars(t('scr.invite.cta')) ?></a>
  <a href="https://alfredlinux.com/"><?= htmlspecialchars(t('nav.home')) ?></a>
</section>
</main>
<footer><p>✠ <strong><?= htmlspecialchars(t('soli')) ?></strong> ✠<br><em><?= htmlspecialchars(t('scr.foot.verse')) ?></em> <?= htmlspecialchars(t('scr.foot.ref')) ?><br><a href="https://alfredlinux.com">alfredlinux.com</a> · <a href="/akjesusbible"><?= htmlspecialchars(t('nav.scriptures')) ?></a> · <a href="?format=json">JSON</a></p></footer>
</body></html>
