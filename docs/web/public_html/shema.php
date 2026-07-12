<?php
/**
 * /shema.php — The Shema in All Tongues
 * "Hear, O Israel: The LORD our God is one LORD." — Deuteronomy 6:4
 * The most ancient confession of faith, in 30 tongues, all proclaiming the One.
 */
declare(strict_types=1);
require __DIR__.'/includes/i18n.inc.php';

$tongues = [
  ['Hebrew (Original)','שְׁמַע יִשְׂרָאֵל יְהוָה אֱלֹהֵינוּ יְהוָה אֶחָד','Sh\'ma Yisrael, Adonai Eloheinu, Adonai Echad','rtl','original'],
  ['Aramaic (Targum Onkelos)','שְׁמַע יִשְׂרָאֵל יְיָ אֱלָהָנָא יְיָ חַד','Sh\'ma Yisrael, Yeya Elahana, Yeya Chad','rtl','ancient'],
  ['Greek (Septuagint)','Ἄκουε Ἰσραήλ· Κύριος ὁ Θεὸς ἡμῶν Κύριος εἷς ἐστιν','Akoue Israēl, Kurios ho Theos hēmōn Kurios heis estin','ltr','ancient'],
  ['Latin (Vulgate)','Audi Israel, Dominus Deus noster Dominus unus est.','—','ltr','ancient'],
  ['Arabic','اِسْمَعْ يَا إِسْرَائِيلُ: الرَّبُّ إِلَهُنَا رَبٌّ وَاحِدٌ','Isma\' ya Isra\'il: Ar-Rabbu Ilahuna Rabbun Wahid','rtl','living'],
  ['English (AKJV)','Hear, O Israel: The LORD our God is one LORD.','Deuteronomy 6:4','ltr','living'],
  ['Spanish','Oye, Israel: Jehová nuestro Dios, Jehová uno es.','—','ltr','living'],
  ['French','Écoute, Israël! L\'Éternel, notre Dieu, est le seul Éternel.','—','ltr','living'],
  ['German','Höre, Israel, der HERR, unser Gott, ist ein einiger HERR!','—','ltr','living'],
  ['Italian','Ascolta, Israele: il Signore è il nostro Dio, il Signore è uno.','—','ltr','living'],
  ['Portuguese','Ouve, ó Israel, o SENHOR nosso Deus é o único SENHOR.','—','ltr','living'],
  ['Russian','Слушай, Израиль: Господь, Бог наш, Господь един есть!','Slushay, Izrail: Gospod, Bog nash, Gospod yedin yest','ltr','living'],
  ['Ukrainian','Слухай, Ізраїлю: Господь — Бог наш, Господь один!','—','ltr','living'],
  ['Polish','Słuchaj, Izraelu: Pan, Bóg nasz, Pan jest jedyny.','—','ltr','living'],
  ['Romanian','Ascultă, Israele! Domnul, Dumnezeul nostru, este singurul Domn.','—','ltr','living'],
  ['Mandarin (中文)','以色列啊，你要聽！耶和華我們神是獨一的主。','Yǐsèliè a, nǐ yào tīng! Yēhéhuá wǒmen Shén shì dúyī de Zhǔ.','ltr','living'],
  ['Japanese (日本語)','聞け、イスラエルよ。われわれの神、主は唯一の主である。','Kike, Isuraeru yo. Wareware no Kami, Shu wa yuiitsu no Shu de aru.','ltr','living'],
  ['Korean (한국어)','이스라엘아 들으라 우리 하나님 여호와는 오직 유일한 여호와이시니','Iseuraela deureura, uri Hananim Yeohowaneun ojik yuilhan Yeohowaisini.','ltr','living'],
  ['Hindi (हिन्दी)','हे इस्राएल, सुन! यहोवा हमारा परमेश्वर एक ही यहोवा है।','He Israel, sun! Yahova hamaara Parameshwar ek hi Yahova hai.','ltr','living'],
  ['Tamil (தமிழ்)','இஸ்ரவேலே, கேள்! நம்முடைய தேவனாகிய கர்த்தர் ஒரே கர்த்தர்.','Israveley, kel! Nammudaya Devanaaki Karthar oarey Karthar.','ltr','living'],
  ['Bengali (বাংলা)','হে ইস্রায়েল, শোন! আমাদের ঈশ্বর সদাপ্রভু এক সদাপ্রভু।','He Israel, shono! Aamader Ishwar Sadaprabhu ek Sadaprabhu.','ltr','living'],
  ['Urdu (اُردُو)','اے اِسرائیل سُن: خُداوند ہمارا خُدا ایک ہی خُداوند ہے۔','Aye Israel sun: Khudawand hamara Khuda ek hi Khudawand hai.','rtl','living'],
  ['Swahili','Sikia, Ee Israeli; Bwana Mungu wetu, Bwana ni mmoja.','—','ltr','living'],
  ['Yoruba','Gbọ́, Ísírẹ́lì: Olúwa Ọlọ́run wa, Olúwa kan ṣoṣo ni.','—','ltr','living'],
  ['Hausa','Ka ji ya Isra\'ila: Ubangiji Allahnmu, Ubangiji ɗaya ne.','—','ltr','living'],
  ['Amharic (አማርኛ)','እስራኤል ሆይ ስማ፥ አምላካችን እግዚአብሔር አንድ እግዚአብሔር ነው።','Israel hoy sma, Amlakachin Egzi\'abher and Egzi\'abher new.','ltr','living'],
  ['Tagalog','Dinggin mo, Oh Israel: ang Panginoon nating Dios ay isang Panginoon.','—','ltr','living'],
  ['Indonesian','Dengarlah, hai orang Israel: TUHAN itu Allah kita, TUHAN itu esa!','—','ltr','living'],
  ['Vietnamese','Hỡi Y-sơ-ra-ên! hãy nghe: Giê-hô-va Đức Chúa Trời chúng ta là Giê-hô-va có một không hai.','—','ltr','living'],
  ['Thai (ภาษาไทย)','โอ คนอิสราเอล จงฟังเถิด พระเยโฮวาห์ทรงเป็นพระเจ้าของเรา พระเยโฮวาห์แต่องค์เดียว','Oh khon Israel, jong fang thoet, Phra Yehowa song pen Phrachao khong rao, Phra Yehowa tae ong diao.','ltr','living'],
];

$reflection = [
  ['ECHAD — אֶחָד','One','The Hebrew word is not yachid (solitary, only). It is echad — a compound oneness, the same word used for husband and wife becoming "one flesh" (Genesis 2:24). The Shema does not deny the Triune God; it foreshadows Him.'],
  ['Yeshua and the Shema','Mark 12:29','When asked the greatest commandment, Jesus quoted the Shema first. He did not abolish it. He embodied it — for in Him all the fullness of the Godhead dwells bodily (Colossians 2:9).'],
  ['One God, One Mediator','1 Timothy 2:5','"For there is one God, and one mediator between God and men, the man Christ Jesus." The Shema and the Cross say the same thing: God is One, and He has come for you.'],
  ['Bridge to Our Cousins','Surah Al-Ikhlas','The Qur\'an\'s great confession — "Say: He is Allah, the One" — echoes the Shema. To our Muslim brothers and sisters: the One God you proclaim is the God of Abraham, Isaac, and Jacob, who has spoken finally in the Son (Hebrews 1:1-2).'],
];

if (($_GET['format'] ?? '') === 'json') {
  header('Content-Type: application/json; charset=utf-8');
  header('Access-Control-Allow-Origin: *');
  echo json_encode([
    'doctrine'  => 'shema',
    'reference' => 'Deuteronomy 6:4',
    'count'     => count($tongues),
    'tongues'   => array_map(fn($t)=>['language'=>$t[0],'text'=>$t[1],'transliteration'=>$t[2],'direction'=>$t[3],'era'=>$t[4]], $tongues),
    'reflection'=> array_map(fn($r)=>['title'=>$r[0],'reference'=>$r[1],'note'=>$r[2]], $reflection),
    'soli_deo_gloria' => true,
  ], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
  exit;
}
?><!DOCTYPE html><html<?= alfred_html_attrs() ?>><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<?php require __DIR__.'/includes/seo.inc.php'; alfred_seo('/shema', 'The Shema in 30 Tongues', '"Hear, O Israel: The LORD our God is one LORD" — the most ancient confession in 30 languages.'); ?>
<link rel="alternate" hreflang="en" href="https://alfredlinux.com/shema?lang=en">
<link rel="alternate" hreflang="fr" href="https://alfredlinux.com/shema?lang=fr">
<link rel="alternate" hreflang="he" href="https://alfredlinux.com/shema?lang=he">
<link rel="alternate" hreflang="x-default" href="https://alfredlinux.com/shema">
<?php alfred_lang_styles(); ?>
<title>The Shema in All Tongues · Alfred Linux</title>
<meta name="description" content="Hear, O Israel: The LORD our God is one LORD. The most ancient confession of faith, proclaimed in 30 tongues, declaring the One God of Abraham to every nation.">
<meta property="og:title" content="The Shema in All Tongues">
<meta property="og:description" content="One God. Thirty tongues. The confession of Israel proclaimed to every nation.">
<meta property="og:url" content="https://alfredlinux.com/shema">
<style>
:root{--gold:#ffd700;--gold-dim:#c8a02b;--ink:#0a0a14;--paper:#14141f;--paper-2:#1c1c2a;--line:#2a2a3e;--text:#ece8df;--text-dim:#a8a499;--cyan:#66c2ff;--rose:#d75a7a}
*{box-sizing:border-box}html,body{margin:0;padding:0;background:var(--ink);color:var(--text);font-family:"Crimson Pro",Georgia,serif;line-height:1.55}
header.hero{background:radial-gradient(ellipse at top,#141a28 0%,#0a0a14 70%);border-bottom:1px solid var(--line);padding:clamp(3rem,8vw,7rem) 1.5rem 4rem;text-align:center;position:relative}
.cross{font-size:2.2rem;color:var(--gold);letter-spacing:.6em;margin:0 0 1rem}
h1{font-size:clamp(2.4rem,7vw,5rem);margin:0;letter-spacing:.02em;font-weight:600}
h1 .word{color:var(--gold);font-style:italic;text-shadow:0 0 30px rgba(255,215,0,.25)}
.tagline{margin:1rem auto 0;max-width:46rem;color:var(--text-dim);font-size:1.15rem;font-style:italic}
.original{margin:2.5rem auto 0;max-width:54rem;padding:2rem 1.5rem;background:rgba(255,215,0,.04);border:1px solid var(--gold-dim);border-radius:14px}
.original .heb{font-family:"SBL Hebrew",serif;font-size:clamp(1.5rem,4vw,2.4rem);color:var(--gold);direction:rtl;unicode-bidi:isolate;line-height:1.6;margin:0}
.original .translit{margin:1rem 0 0;font-size:1.1rem;color:var(--text);font-style:italic;letter-spacing:.03em}
.original .ref{margin:.75rem 0 0;color:var(--gold-dim);font-size:.85rem;font-family:ui-monospace,monospace}
main{max-width:64rem;margin:0 auto;padding:3rem 1.5rem 5rem}
.intro{font-size:1.15rem;color:var(--text-dim);text-align:center;margin:0 auto 4rem;max-width:42rem}
.intro cite{color:var(--gold-dim);font-style:normal}
.section-head{text-align:center;margin:4rem 0 2rem}
.section-head h2{font-size:1.9rem;color:var(--gold);margin:0 0 .25rem}
.section-head p{margin:0;color:var(--text-dim);font-style:italic}
.tongues{display:grid;grid-template-columns:1fr;gap:.85rem;margin:0;padding:0;list-style:none;counter-reset:t}
@media (min-width:48rem){.tongues{grid-template-columns:repeat(2,1fr)}}
.tongues li{counter-increment:t;background:var(--paper);border:1px solid var(--line);border-radius:10px;padding:1.1rem 1.2rem;display:flex;flex-direction:column;transition:.25s;position:relative}
.tongues li:hover{border-color:var(--gold);transform:translateY(-1px)}
.tongues li.original-card{border-color:var(--gold);background:linear-gradient(135deg,#14141f 60%,#2a200a 100%)}
.tongues li.ancient-card{border-color:var(--cyan)}
.tongues li::before{content:counter(t,decimal-leading-zero);position:absolute;top:.5rem;right:.85rem;color:var(--gold-dim);font-size:.72rem;font-family:ui-monospace,monospace}
.lang{color:var(--cyan);font-size:.92rem;letter-spacing:.05em;margin:0 0 .55rem;font-family:ui-monospace,monospace;text-transform:uppercase}
.text{font-size:1.1rem;line-height:1.6;color:var(--text);margin:0 0 .55rem}
.text.rtl{direction:rtl;unicode-bidi:isolate;text-align:right;font-family:"SBL Hebrew","Times New Roman",serif;font-size:1.25rem;color:var(--gold)}
.inscription-trio{margin:2rem auto 1rem;max-width:60rem;display:grid;grid-template-columns:1fr;gap:.85rem;padding:0 1.5rem}
@media(min-width:48rem){.inscription-trio{grid-template-columns:repeat(3,1fr)}}
.inscription-trio .ic{background:linear-gradient(135deg,#14141f 60%,#2a200a 100%);border:1px solid var(--gold-dim);border-radius:12px;padding:1.25rem;text-align:center}
.inscription-trio .ll{color:var(--gold-dim);font-size:.7rem;letter-spacing:.15em;text-transform:uppercase;font-family:ui-monospace,monospace;margin:0 0 .65rem}
.inscription-trio .tx{font-size:1.15rem;line-height:1.55;color:var(--text);margin:0}
.inscription-trio .tx.heb{font-family:"SBL Hebrew",serif;direction:rtl;color:var(--gold);font-size:1.4rem}
.inscription-trio .tx.lat{font-variant:small-caps;letter-spacing:.03em;color:#e6c896;font-style:italic}
.inscription-banner{text-align:center;margin:2.5rem auto 0;max-width:48rem;color:var(--gold);font-size:.78rem;letter-spacing:.15em;font-family:ui-monospace,monospace;text-transform:uppercase;padding:0 1.5rem}
.inscription-banner em{color:var(--text-dim);text-transform:none;letter-spacing:0;font-family:Georgia,serif;display:block;margin-top:.4rem;font-size:.95rem;font-style:italic}
.translit{margin:auto 0 0;color:var(--text-dim);font-style:italic;font-size:.88rem}
.gematria{margin:5rem 0 0}
.gematria h2{text-align:center;font-size:2.2rem;color:var(--gold);margin:0 0 .5rem;letter-spacing:.05em}
.gematria-sub{text-align:center;color:var(--text-dim);font-style:italic;margin:0 0 2.5rem}
.g-card{background:var(--paper);border:1px solid var(--line);border-radius:12px;padding:1.5rem;margin:0 0 1rem}
.g-card h3{margin:0 0 .35rem;color:var(--text);font-size:1.2rem}
.g-card .ref{display:inline-block;color:var(--cyan);background:var(--ink);padding:.15rem .55rem;border-radius:5px;font-size:.85rem;border:1px solid var(--line);margin:.25rem 0 .5rem;font-family:ui-monospace,monospace}
.g-card p{margin:.5rem 0 0;color:var(--text-dim);font-size:1rem}
.?php alfred_lang_switcher('/shema'); ?>
<header class="hero">
  <a class="json-link" href="?format=json">{ json }</a>
  <div class="cross">✠</div>
  <h1><?= htmlspecialchars(t('sh.h1.lead')) ?> <span class="word"><?= htmlspecialchars(t('sh.h1.word')) ?></span> <?= htmlspecialchars(t('sh.h1.tail')) ?></h1>
  <p class="tagline"><?= htmlspecialchars(t('sh.tag')) ?>
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
  <h1>The <span class="word">Shema</span></h1>
  <p class="tagline">The most ancient confession of faith — declared in <?= count($tongues) ?> tongues to every people, tribe, and nation.</p>
  <div class="original">
    <p class="heb">שְׁמַע יִשְׂרָאֵל יְהוָה אֱלֹהֵינוּ יְהוָה אֶחָד</p>
    <p class="translit">Sh'ma Yisrael, Adonai Eloheinu, Adonai Echad.</p>
    <p class="ref">— Deuteronomy 6:4 · spoken by Israel for over 3,000 years</p>
  </div>
  <div class="inscription-banner">✠ Hebrew · Greek · Latin ✠<em>"And it was written in Hebrew, and Greek, and Latin." — John 19:20</em></div>
  <div class="inscription-trio">
    <div class="ic"><div class="ll">Hebrew · Original</div><p class="tx heb">שְׁמַע יִשְׂרָאֵל יְהוָה אֱלֹהֵינוּ יְהוָה אֶחָד</p></div>
    <div class="ic"><div class="ll">Greek · Septuagint</div><p class="tx">Ἄκουε Ἰσραήλ· Κύριος ὁ Θεὸς ἡμῶν Κύριος εἷς ἐστιν</p></div>
    <div class="ic"><div class="ll">Latin · Vulgate</div><p class="tx lat">Audi Israhel, Dominus Deus noster Dominus unus est</p></div>
  </div>
</header>
<main>
<p class="intro"><em>"Hear, O Israel: The LORD our God is one LORD. And thou shalt love the LORD thy God with all thine heart, and with all thy soul, and with all thy might."</em><br><cite>— Deuteronomy 6:4-5, AKJV</cite></p>
<div class="section-head">
  <h2>One Confession · Thirty Tongues</h2>
  <p>"Every tribe, and tongue, and people, and nation" — Revelation 5:9</p>
</div>
<ul class="tongues">
<?php foreach($tongues as $t):
  $cls = $t[4]==='original' ? 'original-card' : ($t[4]==='ancient' ? 'ancient-card' : '');
  $dirCls = $t[3]==='rtl' ? 'rtl' : '';
?>
<li class="<?= $cls ?>">
  <div class="lang"><?= htmlspecialchars($t[0]) ?></div>
  <div class="text <?= $dirCls ?>"><?= htmlspecialchars($t[1]) ?></div>
  <?php if($t[2] !== '—'): ?><div class="translit"><?= htmlspecialchars($t[2]) ?></div><?php endif; ?>
</li>
<?php endforeach; ?>
</ul>
<section class="gematria">
  <h2>Why "One" Matters</h2>
  <p class="gematria-sub">The Word God chose for "one" was no accident.</p>
  <?php foreach($reflection as $r): ?>
  <article class="g-card"><h3><?= htmlspecialchars($r[0]) ?></h3><span class="ref"><?= htmlspecialchars($r[1]) ?></span><p><?= htmlspecialchars($r[2]) ?></p></article>
  <?php endforeach; ?>
</section>
<section class="invite">
  <p><?= htmlspecialchars(t('sh.invite')) ?><br>"Hear Him." — Matthew 17:5</p>
  <a href="/forty-two">The 42 Generations</a>
  <a href="/sevens">The Sevens</a>
  <a href="/names">The Names of God</a>
  <a href="/welcome"><?= htmlspecialchars(t('nav.welcome')) ?></a>
</section>
</main>
<footer><p>✠ <strong>Soli Deo Gloria</strong> ✠<br><em>"That at the name of Jesus every knee should bow... and that every tongue should confess that Jesus Christ is Lord."</em> — Philippians 2:10-11<br><a href="https://alfredlinux.com">alfredlinux.com</a> · Scriptures from the AKJESUSBible · <a href="?format=json">JSON</a></p></footer>
</body></html>
