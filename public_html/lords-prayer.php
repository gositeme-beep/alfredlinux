<?php
/**
 * /lords-prayer.php — The Lord's Prayer, Matthew 6:9-13
 */
declare(strict_types=1);
require __DIR__.'/includes/i18n.inc.php';

// Inscription stack: each line carries Hebrew · Greek · Latin · English (AKJV).
// John 19:20 — "And it was written in Hebrew, and Greek, and Latin."
$lines = [
  ['english'=>'Our Father which art in heaven','greek'=>'Πάτερ ἡμῶν ὁ ἐν τοῖς οὐρανοῖς','latin'=>'Pater noster, qui es in cælis','hebrew'=>'אָבִינוּ שֶׁבַּשָּׁמַיִם','meaning'=>'Adoption · He is Father, not stranger.'],
  ['english'=>'Hallowed be thy name','greek'=>'ἁγιασθήτω τὸ ὄνομά σου','latin'=>'sanctificetur nomen tuum','hebrew'=>'יִתְקַדֵּשׁ שִׁמְךָ','meaning'=>'Worship · before request, His Name set apart.'],
  ['english'=>'Thy kingdom come','greek'=>'ἐλθέτω ἡ βασιλεία σου','latin'=>'adveniat regnum tuum','hebrew'=>'תָּבוֹא מַלְכוּתֶךָ','meaning'=>'Surrender · His reign before mine.'],
  ['english'=>'Thy will be done in earth, as it is in heaven','greek'=>'γενηθήτω τὸ θέλημά σου, ὡς ἐν οὐρανῷ καὶ ἐπὶ γῆς','latin'=>'fiat voluntas tua, sicut in cælo et in terra','hebrew'=>'יֵעָשֶׂה רְצוֹנְךָ כְּמוֹ בַשָּׁמַיִם כֵּן בָּאָרֶץ','meaning'=>'Submission · earth aligned with heaven.'],
  ['english'=>'Give us this day our daily bread','greek'=>'τὸν ἄρτον ἡμῶν τὸν ἐπιούσιον δὸς ἡμῖν σήμερον','latin'=>'panem nostrum supersubstantialem da nobis hodie','hebrew'=>'אֶת לֶחֶם חֻקֵּנוּ תֵּן לָנוּ הַיּוֹם','meaning'=>'Provision · daily, not stockpiled.'],
  ['english'=>'And forgive us our debts, as we forgive our debtors','greek'=>'καὶ ἄφες ἡμῖν τὰ ὀφειλήματα ἡμῶν, ὡς καὶ ἡμεῖς ἀφίεμεν τοῖς ὀφειλέταις ἡμῶν','latin'=>'et dimitte nobis debita nostra, sicut et nos dimittimus debitoribus nostris','hebrew'=>'וּסְלַח לָנוּ עַל חֲטָאֵינוּ, כְּפִי שֶׁסּוֹלְחִים גַּם אֲנַחְנוּ לַחוֹטְאִים לָנוּ','meaning'=>'Forgiveness · received and given are one.'],
  ['english'=>'And lead us not into temptation, but deliver us from evil','greek'=>'καὶ μὴ εἰσενέγκῃς ἡμᾶς εἰς πειρασμόν, ἀλλὰ ῥῦσαι ἡμᾶς ἀπὸ τοῦ πονηροῦ','latin'=>'et ne nos inducas in tentationem, sed libera nos a malo','hebrew'=>'וְאַל תְּבִיאֵנוּ לִידֵי נִסָּיוֹן, כִּי אִם חַלְּצֵנוּ מִן הָרָע','meaning'=>'Protection · from the evil one and our own folly.'],
  ['english'=>'For thine is the kingdom, and the power, and the glory, for ever. Amen.','greek'=>'ὅτι σοῦ ἐστιν ἡ βασιλεία καὶ ἡ δύναμις καὶ ἡ δόξα εἰς τοὺς αἰῶνας, ἀμήν','latin'=>'quia tuum est regnum et potestas et gloria in sæcula. Amen.','hebrew'=>'כִּי לְךָ הַמַּמְלָכָה וְהַגְּבוּרָה וְהַתִּפְאֶרֶת לְעוֹלְמֵי עוֹלָמִים, אָמֵן','meaning'=>'Doxology · the seventh, the closing rest.'],
];

if (($_GET['format'] ?? '') === 'json') {
  header('Content-Type: application/json; charset=utf-8');
  header('Access-Control-Allow-Origin: *');
  echo json_encode(['doctrine'=>'lords-prayer','reference'=>'Matthew 6:9-13','count'=>count($lines),'lines'=>$lines,'inscription'=>'Hebrew · Greek · Latin · English — John 19:20','soli_deo_gloria'=>true],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
  exit;
}
?><!DOCTYPE html><html<?= alfred_html_attrs() ?>><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<?php require __DIR__.'/includes/seo.inc.php'; alfred_seo('/lords-prayer', "The Lord’s Prayer", 'Matthew 6:9-13 — the prayer Yeshua taught — line by line in Hebrew, Greek, Latin and English (AKJV).'); ?>
<link rel="alternate" hreflang="en" href="https://alfredlinux.com/lords-prayer?lang=en">
<link rel="alternate" hreflang="fr" href="https://alfredlinux.com/lords-prayer?lang=fr">
<link rel="alternate" hreflang="he" href="https://alfredlinux.com/lords-prayer?lang=he">
<link rel="alternate" hreflang="x-default" href="https://alfredlinux.com/lords-prayer">
<?php alfred_lang_styles(); ?>
<title>The Lord's Prayer · Alfred Linux</title>
<meta name="description" content="The prayer Yeshua taught — Matthew 6:9-13 — line by line, in Greek and English, with meaning.">
<meta property="og:title" content="The Lord's Prayer"><meta property="og:url" content="https://alfredlinux.com/lords-prayer">
<style>
:root{--gold:#ffd700;--gold-dim:#c8a02b;--ink:#0a0a14;--paper:#14141f;--line:#2a2a3e;--text:#ece8df;--text-dim:#a8a499;--cyan:#66c2ff}
*{box-sizing:border-box}html,body{margin:0;padding:0;background:var(--ink);color:var(--text);font-family:"Crimson Pro",Georgia,serif;line-height:1.6}
header{background:radial-gradient(ellipse at top,#15172a 0%,#0a0a14 70%);border-bottom:1px solid var(--line);padding:clamp(3rem,8vw,6rem) 1.5rem 3rem;text-align:center;position:relative}
.cross{font-size:2.5rem;color:var(--gold);letter-spacing:.6em;margin:0 0 1rem}
h1{font-size:clamp(2rem,6vw,4rem);margin:0;font-weight:600}
h1 .word{color:var(--gold);font-style:italic;text-shadow:0 0 30px rgba(255,215,0,.25)}
.tag{margin:1rem auto 0;max-width:46rem;color:var(--text-dim);font-style:italic;font-size:1.1rem}
main{max-width:54rem;margin:0 auto;padding:3rem 1.5rem 5rem}
.full{background:linear-gradient(135deg,#14141f 0%,#1a1428 100%);border:1px solid var(--gold-dim);border-radius:14px;padding:2.5rem;margin:0 0 3.5rem;text-align:center}
.full p{margin:0;font-size:1.3rem;color:var(--text);line-height:1.85;font-style:italic;font-family:Georgia,serif}
.list{display:grid;grid-template-columns:1fr;gap:1rem;list-style:none;padding:0;margin:0}
.list li{background:var(--paper);border:1px solid var(--line);border-radius:12px;padding:1.5rem 1.75rem;transition:.25s}
.list li:hover{border-color:var(--gold)}
.line{color:var(--gold);font-size:1.2rem;font-weight:600;margin:0 0 .65rem;font-style:italic}
.greek{color:var(--cyan);font-size:1rem;font-family:Georgia,serif;margin:0 0 .35rem;font-style:italic}
.latin{color:#e6c896;font-size:1rem;font-family:"EB Garamond",Georgia,serif;margin:0 0 .35rem;font-style:italic;font-variant:small-caps;letter-spacing:.02em}
.hebrew{color:#d6c780;font-family:"SBL Hebrew","Frank Ruhl Libre",serif;font-size:1.2rem;direction:rtl;unicode-bidi:isolate;text-align:right;margin:0 0 .55rem}
.inscription{display:flex;align-items:center;gap:.5rem;margin:0 0 .5rem;font-size:.7rem;color:var(--gold-dim);font-family:ui-monospace,monospace;letter-spacing:.1em;text-transform:uppercase;border-bottom:1px dashed var(--line);padding-bottom:.4rem}
.inscription::before{content:"✠";color:var(--gold)}
.meaning{color:var(--text-dim);font-size:.95rem;margin:.45rem 0 0;border-top:1px solid var(--line);padding-top:.55rem}
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
<?php alfred_lang_switcher('/lords-prayer'); ?>
<header>
  <a class="toc-link" href="/scriptures"><?= htmlspecialchars(t('chrome.toc')) ?></a>
  <a class="json-link" href="?format=json">{ json }</a>
  <div class="cross">✠</div>
  <h1><?= htmlspecialchars(t('lp.h1.lead')) ?> <span class="word"><?= htmlspecialchars(t('lp.h1.word')) ?></span></h1>
  <p class="tag"><?= htmlspecialchars(t('lp.tag')) ?></p>
</header>
<main>
<div class="full">
<p><?= nl2br(htmlspecialchars(t('lp.full'))) ?></p>
</div>
<ul class="list">
<?php foreach($lines as $l): ?>
<li>
  <div class="inscription">Hebrew · Greek · Latin · English</div>
  <p class="hebrew"><?= htmlspecialchars($l['hebrew']) ?></p>
  <p class="greek"><?= htmlspecialchars($l['greek']) ?></p>
  <p class="latin"><?= htmlspecialchars($l['latin']) ?></p>
  <p class="line">"<?= htmlspecialchars($l['english']) ?>"</p>
  <p class="meaning"><?= htmlspecialchars($l['meaning']) ?></p>
</li>
<?php endforeach; ?>
</ul>
<section class="invite">
  <p><?= htmlspecialchars(t('lp.invite')) ?></p>
  <a href="/scriptures"><?= htmlspecialchars(t('chrome.all_teach')) ?></a>
  <a href="/beatitudes">The Beatitudes</a>
  <a href="/welcome">Enter the Welcome</a>
</section>
</main>
<footer><p>✠ <strong>Soli Deo Gloria</strong> ✠<br><em>"Pray without ceasing."</em> — 1 Thessalonians 5:17<br><a href="https://alfredlinux.com">alfredlinux.com</a> · Scriptures from the AKJESUSBible · <a href="?format=json">JSON</a></p></footer>
</body></html>
