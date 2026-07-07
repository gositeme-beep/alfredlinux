<?php
/**
 * /ten-commandments.php — The Ten Words from Sinai (Exodus 20 / Deut 5)
 */
declare(strict_types=1);
require __DIR__.'/includes/i18n.inc.php';

// Inscription stack: Hebrew · Greek (LXX) · Latin (Vulgate) · English (AKJV)
// John 19:20 — the title above His head was written in three tongues.
$ten = [
  ['I',  'No other gods',          'אָנֹכִי יְהוָה אֱלֹהֶיךָ',          'ἐγὼ εἰμι Κύριος ὁ Θεός σου',        'Non habebis deos alienos coram me',                'Exodus 20:2-3', 'I am the LORD thy God... Thou shalt have no other gods before me.'],
  ['II', 'No graven image',        'לֹא תַעֲשֶׂה לְךָ פֶסֶל',           'οὐ ποιήσεις σεαυτῷ εἴδωλον',         'Non facies tibi sculptile',                        'Exodus 20:4-6', 'Thou shalt not make unto thee any graven image... Thou shalt not bow down thyself to them, nor serve them.'],
  ['III','Honor the Name',         'לֹא תִשָּׂא אֶת־שֵׁם',              'οὐ λήψῃ τὸ ὄνομα Κυρίου τοῦ Θεοῦ σου ἐπὶ ματαίῳ', 'Non assumes nomen Domini Dei tui in vanum',        'Exodus 20:7',   'Thou shalt not take the name of the LORD thy God in vain; for the LORD will not hold him guiltless that taketh his name in vain.'],
  ['IV', 'Remember the Sabbath',   'זָכוֹר אֶת־יוֹם הַשַּׁבָּת',         'μνήσθητι τὴν ἡμέραν τῶν σαββάτων ἁγιάζειν αὐτήν', 'Memento ut diem sabbati sanctifices',              'Exodus 20:8-11','Remember the sabbath day, to keep it holy. Six days shalt thou labour... but the seventh day is the sabbath of the LORD thy God.'],
  ['V',  'Honor father and mother','כַּבֵּד אֶת־אָבִיךָ',                'τίμα τὸν πατέρα σου καὶ τὴν μητέρα σου',           'Honora patrem tuum et matrem tuam',                'Exodus 20:12',  'Honour thy father and thy mother: that thy days may be long upon the land which the LORD thy God giveth thee.'],
  ['VI', 'Thou shalt not kill',    'לֹא תִרְצָח',                       'οὐ φονεύσεις',                                     'Non occides',                                       'Exodus 20:13',  'Thou shalt not kill.'],
  ['VII','No adultery',            'לֹא תִנְאָף',                       'οὐ μοιχεύσεις',                                    'Non mœchaberis',                                   'Exodus 20:14',  'Thou shalt not commit adultery.'],
  ['VIII','No stealing',           'לֹא תִגְנֹב',                       'οὐ κλέψεις',                                       'Non furtum facies',                                'Exodus 20:15',  'Thou shalt not steal.'],
  ['IX', 'No false witness',       'לֹא־תַעֲנֶה בְרֵעֲךָ עֵד שָׁקֶר',    'οὐ ψευδομαρτυρήσεις κατὰ τοῦ πλησίον σου',         'Non loqueris contra proximum tuum falsum testimonium','Exodus 20:16','Thou shalt not bear false witness against thy neighbour.'],
  ['X',  'No coveting',            'לֹא תַחְמֹד',                       'οὐκ ἐπιθυμήσεις τὴν γυναῖκα τοῦ πλησίον σου',      'Non concupisces domum proximi tui',                'Exodus 20:17',  'Thou shalt not covet thy neighbour\'s house... wife... nor any thing that is thy neighbour\'s.'],
];

$two_great = [
  ['Mark 12:30', 'Thou shalt love the Lord thy God with all thy heart, and with all thy soul, and with all thy mind, and with all thy strength: this is the first commandment.'],
  ['Mark 12:31', 'Thou shalt love thy neighbour as thyself. There is none other commandment greater than these.'],
];

if (($_GET['format'] ?? '') === 'json') {
  header('Content-Type: application/json; charset=utf-8');
  header('Access-Control-Allow-Origin: *');
  echo json_encode(['doctrine'=>'ten-commandments','count'=>10,'commandments'=>array_map(fn($c)=>['number'=>$c[0],'title'=>$c[1],'hebrew'=>$c[2],'greek'=>$c[3],'latin'=>$c[4],'reference'=>$c[5],'text'=>$c[6]],$ten),'two_great_commandments'=>array_map(fn($t)=>['ref'=>$t[0],'text'=>$t[1]],$two_great),'inscription'=>'Hebrew · Greek · Latin · English — John 19:20','soli_deo_gloria'=>true],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
  exit;
}
?><!DOCTYPE html><html<?= alfred_html_attrs() ?>><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<?php require __DIR__.'/includes/seo.inc.php'; alfred_seo('/ten-commandments', 'The Ten Commandments', 'Exodus 20 — the Ten Words — in Hebrew, Greek, Latin, and English (AKJV).'); ?>
<link rel="alternate" hreflang="en" href="https://alfredlinux.com/ten-commandments?lang=en">
<link rel="alternate" hreflang="fr" href="https://alfredlinux.com/ten-commandments?lang=fr">
<link rel="alternate" hreflang="he" href="https://alfredlinux.com/ten-commandments?lang=he">
<link rel="alternate" hreflang="x-default" href="https://alfredlinux.com/ten-commandments">
<?php alfred_lang_styles(); ?>
<title>The Ten Commandments · Alfred Linux</title>
<meta name="description" content="The Ten Words from Sinai — Exodus 20 — and the Two Great Commandments that fulfill them all.">
<meta property="og:title" content="The Ten Commandments"><meta property="og:url" content="https://alfredlinux.com/ten-commandments">
<style>
:root{--gold:#ffd700;--gold-dim:#c8a02b;--ink:#0a0a14;--paper:#14141f;--line:#2a2a3e;--text:#ece8df;--text-dim:#a8a499;--cyan:#66c2ff}
*{box-sizing:border-box}html,body{margin:0;padding:0;background:var(--ink);color:var(--text);font-family:"Crimson Pro",Georgia,serif;line-height:1.6}
header{background:radial-gradient(ellipse at top,#1a1428 0%,#0a0a14 70%);border-bottom:1px solid var(--line);padding:clamp(3rem,8vw,6rem) 1.5rem 3rem;text-align:center;position:relative}
.cross{font-size:2.5rem;color:var(--gold);letter-spacing:.6em;margin:0 0 1rem}
h1{font-size:clamp(2rem,6vw,4rem);margin:0;font-weight:600}
h1 .word{color:var(--gold);font-style:italic;text-shadow:0 0 30px rgba(255,215,0,.25)}
.tag{margin:1rem auto 0;max-width:46rem;color:var(--text-dim);font-style:italic;font-size:1.1rem}
main{max-width:60rem;margin:0 auto;padding:3rem 1.5rem 5rem}
.intro{text-align:center;margin:0 auto 3rem;max-width:42rem;color:var(--text-dim);font-size:1.1rem}
.intro cite{color:var(--gold-dim);font-style:normal;display:block;margin-top:.5rem}
.tablets{display:grid;grid-template-columns:1fr;gap:1rem;list-style:none;margin:0;padding:0;counter-reset:cmd}
.tablets li{counter-increment:cmd;background:var(--paper);border:1px solid var(--line);border-radius:14px;padding:1.5rem 1.75rem;display:grid;grid-template-columns:auto 1fr;gap:1.5rem;transition:.25s}
.tablets li:hover{border-color:var(--gold);transform:translateY(-1px)}
.numeral{color:var(--gold);font-size:2.5rem;font-weight:700;line-height:1;font-family:Georgia,serif;text-align:center;min-width:3.5rem;align-self:center;text-shadow:0 0 20px rgba(255,215,0,.25)}
.cbody{min-width:0}
.crow{display:flex;align-items:baseline;gap:.85rem;flex-wrap:wrap;margin:0 0 .35rem}
.cname{color:var(--gold);font-size:1.25rem;font-weight:600;margin:0}
.cheb{color:var(--text);font-family:"SBL Hebrew","Times New Roman",serif;font-size:1.3rem;direction:rtl;unicode-bidi:isolate}
.cgreek{display:block;color:var(--cyan);font-family:Georgia,serif;font-size:1rem;font-style:italic;margin:.4rem 0 .15rem}
.clatin{display:block;color:#e6c896;font-family:"EB Garamond",Georgia,serif;font-size:1rem;font-style:italic;font-variant:small-caps;letter-spacing:.02em;margin:0 0 .55rem}
.inscription{display:flex;align-items:center;gap:.5rem;margin:0 0 .5rem;font-size:.65rem;color:var(--gold-dim);font-family:ui-monospace,monospace;letter-spacing:.1em;text-transform:uppercase}
.inscription::before{content:"✠";color:var(--gold)}
.cref{color:var(--cyan);font-size:.8rem;font-family:ui-monospace,monospace;background:var(--ink);padding:.15rem .55rem;border-radius:5px;border:1px solid var(--line);margin:0 0 .65rem;display:inline-block}
.ctext{margin:0;color:var(--text-dim);font-size:1rem;line-height:1.65;font-style:italic}
.great{margin:5rem 0 0}
.great h2{text-align:center;font-size:2rem;color:var(--gold);margin:0 0 .5rem}
.great-sub{text-align:center;color:var(--text-dim);font-style:italic;margin:0 0 2rem}
.gcard{background:linear-gradient(135deg,#14141f 60%,#1a2a14 100%);border:1px solid var(--gold-dim);border-radius:12px;padding:1.5rem;margin:0 0 1rem}
.gcard .gref{color:var(--cyan);font-family:ui-monospace,monospace;font-size:.85rem;margin:0 0 .5rem}
.gcard p{margin:0;font-size:1.1rem;color:var(--text);font-style:italic}
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
@media (max-width:34rem){.tablets li{grid-template-columns:1fr;gap:.75rem}.numeral{text-align:left;min-width:0}}
</style>    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/img/favicon/favicon-16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/img/favicon/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#D4AF37">
</head><body>
<?php alfred_lang_switcher('/ten-commandments'); ?>
<header>
  <a class="toc-link" href="/scriptures"><?= htmlspecialchars(t('chrome.toc')) ?></a>
  <a class="json-link" href="?format=json">{ json }</a>
  <div class="cross">✠</div>
  <h1><?= htmlspecialchars(t('tc.h1.lead')) ?> <span class="word"><?= htmlspecialchars(t('tc.h1.word')) ?></span></h1>
  <p class="tag"><?= htmlspecialchars(t('tc.tag')) ?></p>
</header>
<main>
<p class="intro"><em>"And God spake all these words, saying..."</em><cite>— Exodus 20:1, AKJESUSBible</cite></p>
<ol class="tablets">
<?php foreach($ten as $c): ?>
<li>
  <div class="numeral"><?= htmlspecialchars($c[0]) ?></div>
  <div class="cbody">
    <div class="inscription">Hebrew · Greek · Latin · English</div>
    <div class="crow"><h2 class="cname"><?= htmlspecialchars($c[1]) ?></h2><span class="cheb"><?= htmlspecialchars($c[2]) ?></span></div>
    <span class="cgreek"><?= htmlspecialchars($c[3]) ?></span>
    <span class="clatin"><?= htmlspecialchars($c[4]) ?></span>
    <span class="cref"><?= htmlspecialchars($c[5]) ?></span>
    <p class="ctext">"<?= htmlspecialchars($c[6]) ?>"</p>
  </div>
</li>
<?php endforeach; ?>
</ol>
<section class="great">
  <h2><?= htmlspecialchars(t('tc.great.h')) ?></h2>
  <p class="great-sub"><?= htmlspecialchars(t('tc.great.sub')) ?></p>
  <?php foreach($two_great as $g): ?>
  <article class="gcard"><p class="gref"><?= htmlspecialchars($g[0]) ?></p><p>"<?= htmlspecialchars($g[1]) ?>"</p></article>
  <?php endforeach; ?>
</section>
<section class="invite">
  <p><?= htmlspecialchars(t('tc.invite')) ?></p>
  <a href="/scriptures"><?= htmlspecialchars(t('chrome.all_teach')) ?></a>
  <a href="/welcome"><?= htmlspecialchars(t('nav.welcome')) ?></a>
</section>
</main>
<footer><p>✠ <strong>Soli Deo Gloria</strong> ✠<br><em>"For Christ is the end of the law for righteousness to every one that believeth."</em> — Romans 10:4<br><a href="https://alfredlinux.com">alfredlinux.com</a> · Scriptures from the AKJESUSBible · <a href="?format=json">JSON</a></p></footer>
</body></html>
