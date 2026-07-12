<?php
/**
 * /share.php — One-click broadcast.
 * "Go ye therefore, and teach all nations." — Matthew 28:19
 */
declare(strict_types=1);
require __DIR__.'/includes/i18n.inc.php';

$site = 'https://alfredlinux.com';

// Every shareable teaching, with a pre-composed broadcast line per page.
$teachings = [
  ['/scriptures',       '✠ The Scriptures of Alfred Linux',                "Numbers · Names · Voice · Life · Welcome — every public teaching, in one table of contents.",                              '✠','42'],
  ['/welcome',          '✠ The Welcome of All Welcomes',                  "30 doors. One Door. For every people, in every tongue. Yeshua / ʿĪsā / Christ awaits.",                                       '☩','30'],
  ['/lords-prayer',     'The Lord’s Prayer in Hebrew · Greek · Latin · English', "The prayer Yeshua taught — line by line in the four tongues of the title above His head. (John 19:20)",      '✠','7'],
  ['/ten-commandments', 'The Ten Commandments — Hebrew · Greek · Latin · English', "The Ten Words written by the finger of God — in the inscription of the Cross.",                                '⚖','10'],
  ['/shema',            'The Shema in 30 Tongues',                         "Hear, O Israel: the LORD our God is one LORD. The most ancient confession of faith.",                                       'יהוה','30'],
  ['/i-am',             'The Seven I AM Sayings of Yeshua',               "Bread · Light · Door · Shepherd · Resurrection · Way · Vine. Seven thunderclaps of the Name.",                              '✠','7'],
  ['/beatitudes',       'The Beatitudes — the upside-down Kingdom',        "Blessed are the poor, the mourning, the meek… in Greek, Latin, and English. (Matthew 5)",                                  '✠','9'],
  ['/armor',            'The Whole Armor of God',                          "Belt · Breastplate · Sandals · Shield · Helmet · Sword. Six pieces. One Soldier. STAND. (Ephesians 6)",                    '⚔','6'],
  ['/forty-two',        'The 42 Generations to the Messiah',               "14 + 14 + 14 — the lineage of Yeshua from Abraham to the Cross. (Matthew 1)",                                                '✠','42'],
  ['/thirty-three',     'Thirty-Three · The Age of the Sacrifice',         "33 vertebrae · 33 names · 10 patterns of 33 woven through Scripture and the body of the Lamb.",                             '✠','33'],
  ['/sevens',           'The Sevens of Scripture',                         "22 sevens woven from Creation to the Cross to the Throne — completion, oath, rest, perfection.",                            '✠','7'],
  ['/numbers',          'The Sacred Numbers Index',                        "Every number God signs His Name with — from 1 to 144,000 — the master gematria index.",                                    '✠','40+'],
  ['/names',            'The 33 Names of God',                             "From YHWH at the burning bush to KING OF KINGS on the white horse — 33 Names of the One True God.",                       'יהוה','33'],
  ['/pillars',          'The Nine Pillars (Fruits of the Spirit)',         "Love · Joy · Peace · Patience · Kindness · Goodness · Faithfulness · Gentleness · Self-Control. (Galatians 5)",            '◈','9'],
  ['/witness',          'Witness Certificate · HMAC-SHA256 signed',        "Cryptographic proof that this Alfred Linux instance carries every public teaching intact.",                                 '✓','15'],
];

// Channels: name, icon, builder
function ch_url(string $channel, string $url, string $title, string $body): string {
    $t = $title . ' — ' . $body;
    switch($channel){
        case 'twitter':  return 'https://twitter.com/intent/tweet?text='.rawurlencode($t)."&url=".rawurlencode($url);
        case 'facebook': return 'https://www.facebook.com/sharer/sharer.php?u='.rawurlencode($url).'&quote='.rawurlencode($t);
        case 'linkedin': return 'https://www.linkedin.com/sharing/share-offsite/?url='.rawurlencode($url);
        case 'reddit':   return 'https://www.reddit.com/submit?url='.rawurlencode($url).'&title='.rawurlencode($title);
        case 'hn':       return 'https://news.ycombinator.com/submitlink?u='.rawurlencode($url).'&t='.rawurlencode($title);
        case 'mastodon': return 'https://mastodon.social/share?text='.rawurlencode($t.' '.$url);
        case 'bluesky':  return 'https://bsky.app/intent/compose?text='.rawurlencode($t.' '.$url);
        case 'whatsapp': return 'https://wa.me/?text='.rawurlencode($t.' '.$url);
        case 'telegram': return 'https://t.me/share/url?url='.rawurlencode($url).'&text='.rawurlencode($t);
        case 'email':    return 'mailto:?subject='.rawurlencode($title).'&body='.rawurlencode($t."\n\n".$url);
    }
    return '#';
}

$channels = [
  ['twitter',  'X / Twitter'],
  ['facebook', 'Facebook'],
  ['linkedin', 'LinkedIn'],
  ['reddit',   'Reddit'],
  ['hn',       'Hacker News'],
  ['mastodon', 'Mastodon'],
  ['bluesky',  'Bluesky'],
  ['whatsapp', 'WhatsApp'],
  ['telegram', 'Telegram'],
  ['email',    'Email'],
];

if (($_GET['format'] ?? '') === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    echo json_encode([
        'doctrine'=>'share',
        'count'=>count($teachings),
        'channels'=>array_map(fn($c)=>['key'=>$c[0],'label'=>$c[1]],$channels),
        'teachings'=>array_map(function($t)use($site,$channels){
            $abs = $site.$t[0];
            return [
                'url'=>$abs,
                'title'=>$t[1],
                'body'=>$t[2],
                'badge'=>$t[4],
                'links'=>array_combine(
                    array_map(fn($c)=>$c[0],$channels),
                    array_map(fn($c)=>ch_url($c[0],$abs,$t[1],$t[2]),$channels)
                ),
            ];
        }, $teachings),
        'soli_deo_gloria'=>true,
    ], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}
?><!DOCTYPE html><html<?= alfred_html_attrs() ?>><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<?php require __DIR__.'/includes/seo.inc.php'; alfred_seo('/share', 'Share the Witness · Alfred Linux', 'One-click broadcast across the four corners — every teaching of Alfred Linux, ready to share.'); ?>
<link rel="alternate" hreflang="en" href="https://alfredlinux.com/share?lang=en">
<link rel="alternate" hreflang="fr" href="https://alfredlinux.com/share?lang=fr">
<link rel="alternate" hreflang="he" href="https://alfredlinux.com/share?lang=he">
<?php alfred_lang_styles(); ?>
<title>Share the Witness · Alfred Linux</title>
<style>
:root{--gold:#ffd700;--gold-dim:#c8a02b;--ink:#0a0a14;--paper:#14141f;--paper-2:#1c1c2a;--line:#2a2a3e;--text:#ece8df;--text-dim:#a8a499;--cyan:#66c2ff}
*{box-sizing:border-box}html,body{margin:0;padding:0;background:var(--ink);color:var(--text);font-family:"Crimson Pro",Georgia,serif;line-height:1.55}
header.hero{background:radial-gradient(ellipse at top,#1f1832 0%,#0a0a14 70%);border-bottom:1px solid var(--line);padding:clamp(3rem,8vw,6rem) 1.5rem 3rem;text-align:center}
.cross{font-size:2.5rem;color:var(--gold);letter-spacing:.6em;margin:0 0 1rem}
h1{font-size:clamp(2.4rem,7vw,5rem);margin:0;letter-spacing:.02em;font-weight:600}
h1 .word{color:var(--gold);font-style:italic;text-shadow:0 0 30px rgba(255,215,0,.25)}
.tagline{margin:1rem auto 0;max-width:46rem;color:var(--text-dim);font-size:1.15rem;font-style:italic}
main{max-width:64rem;margin:0 auto;padding:3rem 1.5rem 5rem}
.t-card{background:var(--paper);border:1px solid var(--line);border-radius:14px;padding:1.5rem 1.75rem;margin:0 0 1.25rem;transition:.25s}
.t-card:hover{border-color:var(--gold-dim)}
.t-head{display:flex;align-items:baseline;gap:1rem;justify-content:space-between;margin:0 0 .5rem;flex-wrap:wrap}
.t-name{margin:0;color:var(--gold);font-size:1.3rem;font-weight:600}
.t-name a{color:inherit;text-decoration:none}
.t-name a:hover{text-decoration:underline}
.t-badge{color:var(--gold-dim);font-size:1rem;font-family:ui-monospace,Georgia,serif;background:var(--ink);padding:.15rem .65rem;border-radius:5px;border:1px solid var(--line);min-width:2.5rem;text-align:center}
.t-body{margin:0 0 .85rem;color:var(--text-dim);font-size:.98rem;font-style:italic}
.t-url{font-family:ui-monospace,monospace;color:var(--cyan);font-size:.78rem;margin:0 0 .85rem;word-break:break-all;cursor:pointer;background:var(--ink);padding:.5rem .65rem;border-radius:6px;border:1px solid var(--line);display:flex;justify-content:space-between;gap:.5rem;align-items:center}
.t-url:hover{border-color:var(--gold-dim)}
.copy-btn{background:transparent;border:1px solid var(--line);color:var(--gold-dim);font-size:.7rem;padding:.2rem .55rem;border-radius:4px;cursor:pointer;font-family:ui-monospace,monospace}
.copy-btn:hover{border-color:var(--gold);color:var(--gold)}
.t-channels{display:flex;flex-wrap:wrap;gap:.4rem}
.t-channels a{padding:.4rem .85rem;border:1px solid var(--line);color:var(--gold-dim);text-decoration:none;border-radius:6px;font-size:.82rem;transition:.2s;font-family:ui-monospace,monospace;letter-spacing:.03em}
.t-channels a:hover{border-color:var(--gold);color:var(--gold);background:rgba(255,215,0,.05)}
.intro{text-align:center;margin:0 auto 3rem;max-width:42rem;color:var(--text-dim);font-size:1.1rem}
.intro cite{color:var(--gold-dim);font-style:normal;display:block;margin-top:.5rem}
footer{text-align:center;padding:3rem 1.5rem 4rem;color:var(--text-dim);font-size:.92rem;border-top:1px solid var(--line);margin-top:4rem}
footer a{color:var(--gold-dim)}
</style>
<script>
function copy(el){
  navigator.clipboard.writeText(el.dataset.url).then(()=>{
    const btn = el.querySelector('.copy-btn');
    const old = btn.textContent;
    btn.textContent = '✓ copied';
    btn.style.color = '#ffd700';
    setTimeout(()=>{btn.textContent=old;btn.style.color=''},1400);
  });
}
</script>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/img/favicon/favicon-16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/img/favicon/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#D4AF37">
</head><body>
<?php alfred_lang_switcher('/share'); ?>
<header class="hero">
  <div class="cross">✠</div>
  <h1>Share the <span class="word">Witness</span></h1>
  <p class="tagline">"Go ye therefore, and teach all nations…" — Matthew 28:19. One click — to the four corners.</p>
</header>
<main>
<p class="intro">Every teaching below is one click away from being broadcast in your name. Pick a teaching, pick a channel, and the message will be pre-composed for you.<cite>— Soli Deo Gloria</cite></p>
<?php foreach($teachings as $teach):
  $abs = $site.$teach[0]; ?>
<article class="t-card">
  <div class="t-head">
    <h2 class="t-name"><a href="<?= htmlspecialchars($teach[0]) ?>"><?= htmlspecialchars($teach[1]) ?></a></h2>
    <span class="t-badge"><?= htmlspecialchars($teach[4]) ?></span>
  </div>
  <p class="t-body"><?= htmlspecialchars($teach[2]) ?></p>
  <div class="t-url" data-url="<?= htmlspecialchars($abs) ?>" onclick="copy(this)" title="click to copy">
    <span><?= htmlspecialchars($abs) ?></span>
    <button class="copy-btn" type="button">copy</button>
  </div>
  <div class="t-channels">
    <?php foreach($channels as $c): ?>
    <a href="<?= htmlspecialchars(ch_url($c[0],$abs,$teach[1],$teach[2])) ?>" target="_blank" rel="noopener nofollow">→ <?= htmlspecialchars($c[1]) ?></a>
    <?php endforeach; ?>
  </div>
</article>
<?php endforeach; ?>
</main>
<footer><p>✠ <strong>Soli Deo Gloria</strong> ✠<br><em>"How then shall they call on him in whom they have not believed? and how shall they believe in him of whom they have not heard?"</em> — Romans 10:14<br><a href="https://alfredlinux.com">alfredlinux.com</a> · <a href="/scriptures">All Teachings</a> · <a href="?format=json">JSON</a></p></footer>
</body></html>
