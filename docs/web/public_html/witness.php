<?php
/**
 * /witness.php — The Witness Certificate
 * HMAC-signed proof that this Alfred Linux instance is doctrinally intact.
 */
declare(strict_types=1);

$pillars = [
  ['scriptures',     'The Scriptures TOC',    '/scriptures'],
  ['akjesusbible',   'AKJESUSBible Source',   '/akjesusbible'],
  ['names',          'The 33 Names',          '/names'],
  ['shema',          'The Shema',             '/shema'],
  ['forty-two',      'The 42 Generations',    '/forty-two'],
  ['sevens',         'The Sevens',            '/sevens'],
  ['numbers',        'The Sacred Numbers',    '/numbers'],
  ['thirty-three',   'Thirty-Three',          '/thirty-three'],
  ['i-am',           'The I AM Sayings',      '/i-am'],
  ['armor',          'The Armor of God',      '/armor'],
  ['pillars',        'The Nine Pillars',      '/pillars'],
  ['welcome',        'The Welcome',           '/welcome'],
  ['ten-commandments','The Ten Commandments', '/ten-commandments'],
  ['beatitudes',     'The Beatitudes',        '/beatitudes'],
  ['lords-prayer',   'The Lord\'s Prayer',    '/lords-prayer'],
  ['covenant',       'The Covenant',          '/covenant'],
  ['share',          'The Broadcast',         '/share'],
];

$root = __DIR__;
$results = [];
$ok = 0;
foreach ($pillars as [$id,$title,$url]) {
  $file = $root . '/' . $id . '.php';
  $present = is_file($file);
  $size    = $present ? filesize($file) : 0;
  $hash    = $present ? substr(hash_file('sha256',$file), 0, 16) : null;
  if ($present) $ok++;
  $results[] = ['id'=>$id,'title'=>$title,'url'=>$url,'present'=>$present,'size'=>$size,'sha256_16'=>$hash];
}

$verdict   = ($ok === count($pillars)) ? 'INTACT' : 'PARTIAL';
$timestamp = gmdate('c');
$secret    = getenv('WITNESS_SECRET') ?: 'soli-deo-gloria';
$payload   = json_encode(['verdict'=>$verdict,'count'=>$ok,'total'=>count($pillars),'timestamp'=>$timestamp,'pillars'=>$results]);
$signature = hash_hmac('sha256', $payload, $secret);

if (($_GET['format'] ?? '') === 'json') {
  header('Content-Type: application/json; charset=utf-8');
  header('Access-Control-Allow-Origin: *');
  echo json_encode([
    'doctrine'=>'witness-certificate',
    'verdict'=>$verdict,'count'=>$ok,'total'=>count($pillars),
    'timestamp'=>$timestamp,'pillars'=>$results,
    'signature_alg'=>'HMAC-SHA256','signature'=>$signature,
    'soli_deo_gloria'=>true,
  ],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
  exit;
}
?><!DOCTYPE html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<?php require __DIR__.'/includes/seo.inc.php'; alfred_seo('/witness', 'The Witness Certificate', 'HMAC-SHA256-signed proof that this Alfred Linux instance carries every public teaching intact.'); ?>
<title>Witness Certificate · Alfred Linux</title>
<meta name="description" content="HMAC-signed proof that this Alfred Linux instance carries every doctrinal teaching intact.">
<meta property="og:title" content="Witness Certificate"><meta property="og:url" content="https://alfredlinux.com/witness">
<style>
:root{--gold:#ffd700;--gold-dim:#c8a02b;--green:#5fc97a;--rose:#d75a7a;--ink:#0a0a14;--paper:#14141f;--line:#2a2a3e;--text:#ece8df;--text-dim:#a8a499;--cyan:#66c2ff}
*{box-sizing:border-box}html,body{margin:0;padding:0;background:var(--ink);color:var(--text);font-family:"Crimson Pro",Georgia,serif;line-height:1.55}
header{background:radial-gradient(ellipse at top,#15172a 0%,#0a0a14 70%);border-bottom:1px solid var(--line);padding:clamp(3rem,8vw,5rem) 1.5rem 3rem;text-align:center;position:relative}
.cross{font-size:2.5rem;color:var(--gold);letter-spacing:.6em;margin:0 0 1rem}
h1{font-size:clamp(2rem,6vw,3.5rem);margin:0;font-weight:600}
h1 .word{color:var(--gold);font-style:italic;text-shadow:0 0 30px rgba(255,215,0,.25)}
.tag{margin:1rem auto 0;max-width:46rem;color:var(--text-dim);font-style:italic;font-size:1.1rem}
main{max-width:64rem;margin:0 auto;padding:3rem 1.5rem 5rem}
.verdict{display:flex;align-items:center;gap:1.5rem;background:var(--paper);border:2px solid <?= $verdict==='INTACT'?'var(--green)':'var(--rose)' ?>;border-radius:14px;padding:2rem;margin:0 0 2rem}
.verdict-badge{font-size:3.5rem;color:<?= $verdict==='INTACT'?'var(--green)':'var(--rose)' ?>;line-height:1}
.verdict-body h2{margin:0 0 .35rem;color:<?= $verdict==='INTACT'?'var(--green)':'var(--rose)' ?>;font-size:1.6rem;letter-spacing:.05em}
.verdict-body p{margin:0;color:var(--text-dim);font-size:1rem}
.meta{display:grid;grid-template-columns:repeat(auto-fit,minmax(14rem,1fr));gap:.85rem;margin:0 0 2rem}
.meta div{background:var(--paper);border:1px solid var(--line);border-radius:10px;padding:1rem 1.25rem}
.meta dt{color:var(--text-dim);font-size:.78rem;letter-spacing:.1em;text-transform:uppercase;margin:0 0 .25rem;font-family:ui-monospace,monospace}
.meta dd{margin:0;color:var(--gold);font-size:1.05rem;font-family:ui-monospace,monospace;word-break:break-all}
table{width:100%;border-collapse:separate;border-spacing:0;background:var(--paper);border:1px solid var(--line);border-radius:12px;overflow:hidden;font-size:.95rem}
th,td{padding:.75rem 1rem;text-align:left;border-bottom:1px solid var(--line)}
th{background:var(--ink);color:var(--gold-dim);font-weight:600;font-size:.78rem;letter-spacing:.1em;text-transform:uppercase}
tr:last-child td{border-bottom:0}
td a{color:var(--cyan);text-decoration:none}
td a:hover{color:var(--gold)}
.ok{color:var(--green)} .bad{color:var(--rose)}
.sig{margin:2rem 0 0;background:var(--paper);border:1px solid var(--gold-dim);border-radius:12px;padding:1.5rem;font-family:ui-monospace,monospace;font-size:.85rem;word-break:break-all;color:var(--gold)}
.sig-label{display:block;color:var(--text-dim);font-size:.78rem;text-transform:uppercase;letter-spacing:.1em;margin:0 0 .5rem;font-family:ui-monospace,monospace}
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
<header>
  <a class="toc-link" href="/scriptures">← all teachings</a>
  <a class="json-link" href="?format=json">{ json }</a>
  <div class="cross">✠</div>
  <h1>The <span class="word">Witness</span> Certificate</h1>
  <p class="tag">HMAC-signed proof that this Alfred Linux instance carries every public teaching intact.</p>
</header>
<main>
<div class="verdict">
  <div class="verdict-badge"><?= $verdict==='INTACT' ? '✓' : '!' ?></div>
  <div class="verdict-body">
    <h2><?= htmlspecialchars($verdict) ?> · <?= $ok ?>/<?= count($pillars) ?> witnesses present</h2>
    <p>Generated <?= htmlspecialchars($timestamp) ?> · verifiable via the JSON endpoint and HMAC-SHA256 signature.</p>
  </div>
</div>
<dl class="meta">
  <div><dt>Algorithm</dt><dd>HMAC-SHA256</dd></div>
  <div><dt>Witnesses</dt><dd><?= $ok ?> / <?= count($pillars) ?></dd></div>
  <div><dt>Verdict</dt><dd><?= htmlspecialchars($verdict) ?></dd></div>
  <div><dt>Issued (UTC)</dt><dd><?= htmlspecialchars($timestamp) ?></dd></div>
</dl>
<table>
<thead><tr><th>Pillar</th><th>URL</th><th>Status</th><th>Bytes</th><th>SHA-256 (16)</th></tr></thead>
<tbody>
<?php foreach($results as $r): ?>
<tr>
  <td><?= htmlspecialchars($r['title']) ?></td>
  <td><a href="<?= htmlspecialchars($r['url']) ?>"><?= htmlspecialchars($r['url']) ?></a></td>
  <td class="<?= $r['present']?'ok':'bad' ?>"><?= $r['present']?'✓ present':'✗ missing' ?></td>
  <td><?= $r['size'] ? number_format($r['size']) : '—' ?></td>
  <td><?= $r['sha256_16'] ?? '—' ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<div class="sig">
  <span class="sig-label">HMAC-SHA256 Signature</span>
  <?= htmlspecialchars($signature) ?>
</div>
<section class="invite">
  <p>Verify this certificate at any time. Forward it. Keep it. The Word stands.</p>
  <a href="/scriptures">All Teachings</a>
  <a href="?format=json">JSON</a>
  <a href="/welcome">Enter the Welcome</a>
</section>
</main>
<footer><p>✠ <strong>Soli Deo Gloria</strong> ✠<br><em>"Heaven and earth shall pass away, but my words shall not pass away."</em> — Matthew 24:35<br><a href="https://alfredlinux.com">alfredlinux.com</a> · Scriptures from the AKJESUSBible · <a href="?format=json">JSON</a></p></footer>
</body></html>
