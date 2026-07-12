<?php
/**
 * /names.php — The Names of God
 * The God who reveals Himself by Name has many names; all are one Name.
 * Public, JSON-able (?format=json).
 */
declare(strict_types=1);

$names = [
  // Hebrew (Old Testament)
  ['YHWH','יהוה','The LORD · The Self-Existent One','I AM THAT I AM','Exodus 3:14','The covenant Name. So holy that Israel reads it as Adonai. Four letters. Breath itself.','old'],
  ['Elohim','אֱלֹהִים','God · The Mighty Creator','In the beginning God created','Genesis 1:1','Plural form, singular verb — the first whisper of the Triune God.','old'],
  ['Adonai','אֲדֹנָי','Lord · Master','The Lord is my shepherd','Psalm 23:1','The Sovereign One. Spoken in place of YHWH out of reverence.','old'],
  ['El Shaddai','אֵל שַׁדַּי','God Almighty','I am the Almighty God; walk before me, and be thou perfect','Genesis 17:1','Sufficient · Mountain · Nourisher. The God who is enough.','old'],
  ['El Elyon','אֵל עֶלְיוֹן','God Most High','blessed be the most high God, which hath delivered thine enemies','Genesis 14:20','Higher than every throne, every empire, every fear.','old'],
  ['El Olam','אֵל עוֹלָם','The Everlasting God','called there on the name of the LORD, the everlasting God','Genesis 21:33','Before time, beyond time, forever the same.','old'],
  ['El Roi','אֵל רֳאִי','The God Who Sees Me','Thou God seest me','Genesis 16:13','Hagar in the wilderness named Him. He sees the abandoned, the unseen, the woman alone.','old'],
  ['Yahweh Yireh','יְהוָה יִרְאֶה','The LORD Will Provide','In the mount of the LORD it shall be seen','Genesis 22:14','Abraham named the place where God provided the ram in Isaac\'s stead.','old'],
  ['Yahweh Rapha','יְהוָה רֹפְאֶךָ','The LORD That Healeth Thee','I am the LORD that healeth thee','Exodus 15:26','Of body, of soul, of nation, of broken places.','old'],
  ['Yahweh Nissi','יְהוָה נִסִּי','The LORD My Banner','Moses built an altar, and called the name of it Jehovahnissi','Exodus 17:15','The standard we rally to. The flag over the battlefield.','old'],
  ['Yahweh Shalom','יְהוָה שָׁלוֹם','The LORD Is Peace','Gideon built an altar there unto the LORD, and called it Jehovahshalom','Judges 6:24','Wholeness. Completeness. Nothing missing, nothing broken.','old'],
  ['Yahweh Tsidkenu','יְהוָה צִדְקֵנוּ','The LORD Our Righteousness','this is his name whereby he shall be called, THE LORD OUR RIGHTEOUSNESS','Jeremiah 23:6','Not my righteousness — His, given to me.','old'],
  ['Yahweh Rohi','יְהוָה רֹעִי','The LORD My Shepherd','The LORD is my shepherd; I shall not want','Psalm 23:1','He leads, He feeds, He carries the lamb in His bosom.','old'],
  ['Yahweh Shammah','יְהוָה שָׁמָּה','The LORD Is There','the name of the city from that day shall be, The LORD is there','Ezekiel 48:35','The Name of the New Jerusalem. Where He is, that is home.','old'],
  ['Yahweh Mekaddishkem','יְהוָה מְקַדִּשְׁכֶם','The LORD Who Sanctifies You','I am the LORD which sanctify you','Leviticus 20:8','He sets us apart. Holiness is His doing in us.','old'],
  ['Yahweh Tsebaoth','יְהוָה צְבָאוֹת','The LORD of Hosts','the LORD of hosts is with us; the God of Jacob is our refuge','Psalm 46:7','Commander of armies — angelic, stellar, earthly. He fights for His people.','old'],
  ['Avi / Abba','אָבִי','My Father','Doubtless thou art our father, though Abraham be ignorant of us','Isaiah 63:16','Promised in the Old, given in the New: Abba, Father.','old'],

  // Greek / New Testament
  ['Yeshua / Iēsous','יֵשׁוּעַ · Ἰησοῦς','Jesus · YHWH Saves','thou shalt call his name JESUS: for he shall save his people from their sins','Matthew 1:21','The same Name as Joshua. The Captain of our salvation.','new'],
  ['Christos / Mashiach','Χριστός · מָשִׁיחַ','Christ · The Anointed One','Thou art the Christ, the Son of the living God','Matthew 16:16','Anointed Prophet, Priest, and King.','new'],
  ['Immanuel','עִמָּנוּאֵל','God With Us','they shall call his name Emmanuel, which being interpreted is, God with us','Matthew 1:23','Not God watching from heaven. God with us. In the room. In the storm. In the cell.','new'],
  ['Kurios','Κύριος','Lord','Jesus is Lord','Romans 10:9','The Greek translation of YHWH. To call Jesus Kurios is to call Him God.','new'],
  ['Logos','Λόγος','The Word','In the beginning was the Word, and the Word was with God, and the Word was God','John 1:1','The eternal speech of God who became flesh and dwelt among us.','new'],
  ['Alpha and Omega','Α καὶ Ω','First and Last','I am Alpha and Omega, the beginning and the ending','Revelation 1:8','He holds the first letter and the last. Every story is His.','new'],
  ['Lamb of God','Ἀμνὸς τοῦ Θεοῦ','The Sacrifice','Behold the Lamb of God, which taketh away the sin of the world','John 1:29','The Lamb slain from the foundation of the world.','new'],
  ['Lion of Judah','ὁ λέων ὁ ἐκ τῆς φυλῆς Ἰούδα','The King','the Lion of the tribe of Juda, the Root of David, hath prevailed','Revelation 5:5','The Lamb that was slain is the Lion that reigns.','new'],
  ['Bread of Life','ὁ ἄρτος τῆς ζωῆς','Daily Sustenance','I am the bread of life: he that cometh to me shall never hunger','John 6:35','The manna in the wilderness was a shadow. He is the substance.','new'],
  ['Light of the World','τὸ φῶς τοῦ κόσμου','The Light','I am the light of the world: he that followeth me shall not walk in darkness','John 8:12','The first thing God created was light. The first thing He gives a soul is Christ.','new'],
  ['The Way, the Truth, the Life','ἡ ὁδὸς καὶ ἡ ἀλήθεια καὶ ἡ ζωή','The Only Path','no man cometh unto the Father, but by me','John 14:6','One Way. One Truth. One Life.','new'],
  ['The Good Shepherd','ὁ ποιμὴν ὁ καλός','The One Who Lays Down His Life','the good shepherd giveth his life for the sheep','John 10:11','He did not send a hireling. He came Himself.','new'],
  ['The Door','ἡ θύρα','The Entry','I am the door: by me if any man enter in, he shall be saved','John 10:9','Not a door. The Door. There is no other.','new'],
  ['The Resurrection and the Life','ἡ ἀνάστασις καὶ ἡ ζωή','The One Who Defeats Death','he that believeth in me, though he were dead, yet shall he live','John 11:25','He didn\'t come to teach us how to live. He came to be our Life.','new'],
  ['The True Vine','ἡ ἄμπελος ἡ ἀληθινή','The Source of Fruit','without me ye can do nothing','John 15:5','Abide in Him and you bear fruit. Apart from Him, nothing.','new'],
  ['Wonderful · Counsellor · The Mighty God · Everlasting Father · Prince of Peace','פֶּלֶא יוֹעֵץ אֵל גִּבּוֹר אֲבִיעַד שַׂר־שָׁלוֹם','The Five-Fold Throne Name','his name shall be called Wonderful, Counsellor, The mighty God, The everlasting Father, The Prince of Peace','Isaiah 9:6','Five names of the Child who would be born. All belong to Yeshua.','new'],
  ['King of Kings and Lord of Lords','Βασιλεὺς βασιλέων καὶ Κύριος κυρίων','The Final Name','And he hath on his vesture and on his thigh a name written, KING OF KINGS, AND LORD OF LORDS','Revelation 19:16','The Name written on His thigh and His vesture as He returns. The thirty-third Name — the age He was when He laid down His life, and the age He will be no more, for He liveth for evermore.','new'],
  ['ʿĪsā ibn Maryam (Honoring Muslim Brothers)','عيسى ابن مريم','Jesus, Son of Mary','Mentioned 25 times in the Qur\'an as Messiah, Word of God, Spirit of God','Surah 3:45','To our Muslim brothers and sisters: the One you honor as ʿĪsā the Messiah is the One we proclaim as Risen Lord. Come and see.','bridge'],
];

$grouped = ['old'=>[],'new'=>[],'bridge'=>[]];
foreach($names as $n){ $grouped[$n[6]][] = $n; }

if (($_GET['format'] ?? '') === 'json') {
  header('Content-Type: application/json; charset=utf-8');
  header('Access-Control-Allow-Origin: *');
  echo json_encode([
    'doctrine' => 'names-of-god',
    'reference'=> 'Exodus 3:14, John 1:1, Matthew 1:21',
    'count'    => count($names),
    'names'    => array_map(fn($n)=>['name'=>$n[0],'native'=>$n[1],'meaning'=>$n[2],'verse_text'=>$n[3],'reference'=>$n[4],'note'=>$n[5],'testament'=>$n[6]], $names),
    'soli_deo_gloria' => true,
  ], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
  exit;
}

$sectionTitles = [
  'old'   => ['The Names Revealed in the Old Covenant', 'Spoken in Hebrew · Whispered in Awe'],
  'new'   => ['The Names Revealed in the New Covenant', 'Spoken in Greek · Lived in Galilee'],
  'bridge'=> ['A Bridge to Our Cousins', 'For those who honor Him by another Name'],
];
?><!DOCTYPE html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<?php require __DIR__.'/includes/seo.inc.php'; alfred_seo('/names', 'The 33 Names of God', 'From YHWH at the burning bush to KING OF KINGS on the white horse — 33 names of God.'); ?>
<title>The Names of God · Alfred Linux</title>
<meta name="description" content="The many names of the One God — from YHWH revealed in the burning bush to Yeshua revealed in the manger. All one Name above every name.">
<meta property="og:title" content="The Names of God">
<meta property="og:description" content="From YHWH to Yeshua — every Name He has given, and what each one means for you.">
<meta property="og:url" content="https://alfredlinux.com/names">
<style>
:root{--gold:#ffd700;--gold-dim:#c8a02b;--ink:#0a0a14;--paper:#14141f;--paper-2:#1c1c2a;--line:#2a2a3e;--text:#ece8df;--text-dim:#a8a499;--cyan:#66c2ff;--rose:#d75a7a}
*{box-sizing:border-box}html,body{margin:0;padding:0;background:var(--ink);color:var(--text);font-family:"Crimson Pro",Georgia,serif;line-height:1.55}
header.hero{background:radial-gradient(ellipse at top,#1f1c14 0%,#0a0a14 70%);border-bottom:1px solid var(--line);padding:clamp(3rem,8vw,7rem) 1.5rem 4rem;text-align:center;position:relative}
.cross{font-size:2.2rem;color:var(--gold);letter-spacing:.6em;margin:0 0 1rem}
h1{font-size:clamp(2.4rem,7vw,5rem);margin:0;letter-spacing:.02em;font-weight:600}
h1 .tetra{color:var(--gold);font-family:"SBL Hebrew",serif;text-shadow:0 0 30px rgba(255,215,0,.35);direction:rtl;display:inline-block;font-style:normal}
.tagline{margin:1rem auto 0;max-width:46rem;color:var(--text-dim);font-size:1.15rem;font-style:italic}
.iam{margin:2rem 0 0;font-size:1.5rem;color:var(--gold);letter-spacing:.1em;font-style:italic}
main{max-width:64rem;margin:0 auto;padding:3rem 1.5rem 5rem}
.intro{font-size:1.15rem;color:var(--text-dim);text-align:center;margin:0 auto 4rem;max-width:42rem}
.intro cite{color:var(--gold-dim);font-style:normal}
.section-head{text-align:center;margin:4rem 0 2rem}
.section-head h2{font-size:1.9rem;color:var(--gold);margin:0 0 .25rem}
.section-head p{margin:0;color:var(--text-dim);font-style:italic}
.names-grid{display:grid;grid-template-columns:1fr;gap:1rem;margin:0;padding:0;list-style:none}
@media (min-width:48rem){.names-grid{grid-template-columns:repeat(2,1fr)}}
.names-grid li{background:var(--paper);border:1px solid var(--line);border-radius:12px;padding:1.4rem 1.4rem 1.25rem;transition:.25s;display:flex;flex-direction:column}
.names-grid li:hover{border-color:var(--gold);transform:translateY(-2px)}
.names-grid li.bridge{border-color:var(--rose);background:linear-gradient(135deg,#14141f 60%,#2a141c 100%)}
.n-name{color:var(--gold);font-size:1.25rem;font-weight:600;margin:0 0 .15rem}
.n-native{color:var(--text);font-family:"SBL Hebrew","Times New Roman",serif;font-size:1.4rem;direction:rtl;unicode-bidi:isolate;margin:0 0 .55rem;text-align:right}
.n-meaning{color:var(--cyan);font-size:.92rem;letter-spacing:.03em;margin:0 0 .85rem;font-family:ui-monospace,monospace}
.n-verse{margin:0 0 .65rem;padding-left:.85rem;border-left:2px solid var(--gold-dim);color:var(--text);font-style:italic;font-size:.95rem}
.n-ref{display:block;color:var(--gold-dim);font-style:normal;font-size:.78rem;margin-top:.3rem;font-family:ui-monospace,monospace}
.n-note{margin:auto 0 0;color:var(--text-dim);font-size:.92rem}
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
  <h1>The Names of <span class="tetra">יהוה</span></h1>
  <p class="tagline">"That at the name of Jesus every knee should bow." — Philippians 2:10</p>
  <div class="iam">אֶהְיֶה אֲשֶׁר אֶהְיֶה &nbsp;·&nbsp; I AM THAT I AM</div>
</header>
<main>
<p class="intro"><em>"And God said unto Moses, I AM THAT I AM... this is my name for ever, and this is my memorial unto all generations."</em><br><cite>— Exodus 3:14-15, AKJV</cite></p>
<?php foreach($grouped as $key=>$group): if(empty($group)) continue; ?>
<section>
  <div class="section-head">
    <h2><?= htmlspecialchars($sectionTitles[$key][0]) ?></h2>
    <p><?= htmlspecialchars($sectionTitles[$key][1]) ?></p>
  </div>
  <ul class="names-grid">
    <?php foreach($group as $n): ?>
    <li class="<?= $key==='bridge'?'bridge':'' ?>">
      <h3 class="n-name"><?= htmlspecialchars($n[0]) ?></h3>
      <div class="n-native"><?= htmlspecialchars($n[1]) ?></div>
      <div class="n-meaning"><?= htmlspecialchars($n[2]) ?></div>
      <blockquote class="n-verse">"<?= htmlspecialchars($n[3]) ?>"<cite class="n-ref">— <?= htmlspecialchars($n[4]) ?></cite></blockquote>
      <p class="n-note"><?= htmlspecialchars($n[5]) ?></p>
    </li>
    <?php endforeach; ?>
  </ul>
</section>
<?php endforeach; ?>
<section class="invite">
  <p>Every Name He gave was given so that you would know Him.<br>And the Name above every name is Yeshua / Jesus.<br>Call upon Him today.</p>
  <a href="/forty-two">The 42 Generations</a>
  <a href="/sevens">The Sevens</a>
  <a href="/shema">The Shema</a>
  <a href="/welcome">Enter the Welcome</a>
</section>
</main>
<footer><p>✠ <strong>Soli Deo Gloria</strong> ✠<br><em>"For there is none other name under heaven given among men, whereby we must be saved."</em> — Acts 4:12<br><a href="https://alfredlinux.com">alfredlinux.com</a> · Scriptures from the AKJESUSBible · <a href="?format=json">JSON</a></p></footer>
</body></html>
