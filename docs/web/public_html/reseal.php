<?php
/**
 * /reseal — live status page for the Ninth-Hour ISO seal.
 * Target: King Jesus Version (KJV 1.0) · 91 GiB · sealed at 15:00 ET, 2026-06-20
 * Reads /reseal-status.txt (mirrored from /home/ubuntu by reseal-watcher.sh).
 * Auto-refreshes every 10s until stage=complete.
 */
require_once __DIR__ . '/includes/ga-release-state.php';

// ── Ninth-hour release anchor ────────────────────────────────────────
$releaseTs   = strtotime('2026-06-20 15:00:00 America/Toronto'); // the launch window
$now         = time();
$secsToSeal  = $releaseTs - $now;
$targetGiB   = 91;
$targetBytes = (int) round($targetGiB * 1073741824);
// Current ISO size (if a build artifact already exists)
$isoCandidates = [
  '/home/gositeme/alfred-linux-v2/build/binary.hybrid.iso',
  __DIR__ . '/downloads/' . $gaIsoBasename . '.iso',
  __DIR__ . '/downloads/alfred-linux-7.77-ga-amd64-omega-point.iso', // legacy basename before intel-amd64 rename
  __DIR__ . '/downloads/alfred-linux-7.77-ga-amd64-20260412.iso',
];
$curBytes = 0; $curPath = '';
foreach ($isoCandidates as $p) { if (is_file($p)) { $curBytes = filesize($p); $curPath = $p; break; } }
$curGiB     = $curBytes / 1073741824;
$sizePct    = $targetBytes > 0 ? min(100, ($curBytes / $targetBytes) * 100) : 0;
$statusFile = __DIR__ . '/reseal-status.txt';
$status = ['stage' => 'unknown', 'msg' => '', 'ts' => '', 'etime' => ''];
if (is_file($statusFile)) {
    foreach (file($statusFile, FILE_IGNORE_NEW_LINES) as $line) {
        if (strpos($line, '=') !== false) {
            [$k, $v] = explode('=', $line, 2);
            if ($k === 'timestamp') $status['ts'] = $v;
            elseif ($k === 'ets') {
                $elapsedSecs = time() - (int)$v;
                $status['etime'] = gmdate("H:i:s", max(0, $elapsedSecs));
            }
            else $status[$k] = $v;
        }
    }
}

$stages = [
    'starting'      => ['Starting',         '⏳', 5],
    'kernel-7012'   => ['Kernel 7.0.12',    '🧬', 20],
    'alfred-ai'     => ['GGUF Quantization','🧠', 40],
    'zstd-ultra'    => ['ZSTD-22 Ultra',    '🗜️', 60],
    'mksquashfs'    => ['Sovereign-Q Pack', '📦', 80],
    'xorriso'       => ['xorriso ISO',      '💿', 95],
    'complete'      => ['Sealed',           '✅', 100],
    'error'         => ['Error',            '⚠️', 0],
    'unknown'       => ['Waiting on watcher','…', 0],
];

[$label, $emoji, $pct] = $stages[$status['stage']] ?? $stages['unknown'];
$isComplete = ($status['stage'] === 'complete');
$isError    = ($status['stage'] === 'error');

// Operator gate: hidden panel of copy-paste commands. Token in chmod-600 file.
$opToken = '';
$opTokenFile = '/home/gositeme/.reseal-op-token';
if (is_readable($opTokenFile)) {
    $opToken = trim(@file_get_contents($opTokenFile));
}
$givenToken = (string)($_GET['op'] ?? '');
$isOperator = ($opToken !== '' && hash_equals($opToken, $givenToken));

require_once __DIR__ . '/includes/seo.inc.php';
alfred_seo('/reseal', 'Sovereign-Q Live Build Status',
  'Live progress of the King Jesus Version (KJV 1.0) Alfred Linux 91 GiB ISO. GA launch window Saturday 20 June 2026, 15:00 America/Toronto.');
?>
<?php if (!$isComplete && !$isError): ?>
<meta http-equiv="refresh" content="10">
<?php endif; ?>
<style>
  body { background:#0a0a0f; color:#e0e0e0; font-family:-apple-system,Segoe UI,system-ui,sans-serif; margin:0; padding:2rem; }
  .wrap { max-width:780px; margin:0 auto; }
  h1 { font-size:1.6rem; margin:0 0 1.5rem; color:#facc15; }
  .stage-card { background:#12121a; border:2px solid <?= $isError ? '#e17055' : ($isComplete ? '#00b894' : '#6c5ce7') ?>;
                border-radius:14px; padding:2rem; margin-bottom:1.5rem; }
  .emoji { font-size:3.5rem; line-height:1; }
  .stage-name { font-size:1.6rem; font-weight:700; margin:.5rem 0 .25rem;
                color:<?= $isError ? '#e17055' : ($isComplete ? '#00b894' : '#fde68a') ?>; }
  .stage-msg { color:#888; font-family:monospace; font-size:.95rem; word-break:break-all; }
  .bar { width:100%; height:14px; background:#1e1e2e; border-radius:7px; overflow:hidden; margin:1.25rem 0 .5rem; }
  .bar-fill { height:100%; background:linear-gradient(90deg,#6c5ce7,#00cec9); width:<?= $pct ?>%; transition:width .5s; }
  .meta { display:grid; grid-template-columns:repeat(2,1fr); gap:1rem; margin-top:1rem; font-size:.9rem; color:#888; }
  .meta b { color:#e0e0e0; display:block; }
  a { color:#00cec9; }
  .pipeline { display:flex; gap:.5rem; flex-wrap:wrap; margin-top:1rem; font-size:.85rem; }
  .pipeline span { padding:.3rem .7rem; border-radius:20px; background:#1e1e2e; color:#666; }
  .pipeline span.active { background:#6c5ce7; color:#fff; }
  .pipeline span.done   { background:#00b894; color:#fff; }
</style>
<div class="wrap">
  <h1>✠ Sovereign-Q Compression Protocol · Alfred-RAR · KJV 7.0.12</h1>

  <!-- Ninth-hour countdown banner -->
  <div class="stage-card" style="border-color:#facc15;background:linear-gradient(180deg,#1a1a08 0%, #12121a 100%);text-align:center">
    <div style="font-size:.78rem;letter-spacing:3px;color:#b8860b;font-weight:700">RELEASE WINDOW OPENS</div>
    <div style="font-size:1.45rem;font-weight:700;color:#facc15;margin:.4rem 0">Saturday, 20 June 2026 · 3:00 PM Eastern</div>
    <div id="countdown" style="font-family:'Courier New',monospace;font-size:2.4rem;color:#fde68a;letter-spacing:2px;margin:.4rem 0">— : — : —</div>
    <div style="font-size:.85rem;color:#888;font-style:italic">
      "The grass withereth, the flower fadeth: but the word of our God shall stand for ever." — Isaiah 40:8
    </div>
  </div>


<div class="stage-card">
    <div class="emoji"><?= $emoji ?></div>
    <div class="stage-name"><?= htmlspecialchars($label) ?></div>
    <div class="stage-msg"><?= htmlspecialchars($status['msg'] ?: '—') ?></div>
    <div class="bar"><div class="bar-fill"></div></div>
    <div class="meta">
      <div><b><?= $pct ?>%</b>progress (heuristic)</div>
      <div><b><?= htmlspecialchars($status['etime'] ?: '—') ?></b>elapsed</div>
      <div><b><?= htmlspecialchars($status['ts']    ?: '—') ?></b>last update (UTC)</div>
      <div><b><?= htmlspecialchars($status['stage'] ?: '—') ?></b>stage tag</div>
    </div>
    <div class="pipeline">
      <?php
        $order = ['starting','kernel-7012','alfred-ai','zstd-ultra','mksquashfs','xorriso','complete'];
        $cur   = array_search($status['stage'], $order, true);
        foreach ($order as $i => $key) {
          $cls = $cur === false ? '' : ($i < $cur ? 'done' : ($i === $cur ? 'active' : ''));
          echo '<span class="'.$cls.'">'.htmlspecialchars($stages[$key][0]).'</span>';
        }
      ?>
    </div>
  </div>

  <?php if ($isComplete): ?>
    <div class="stage-card" style="border-color:#facc15">
      <div class="emoji">✠</div>
      <div class="stage-name" style="color:#facc15">Ninth-Hour seal complete</div>
      <p style="color:#e0e0e0;margin:.75rem 0 0">
        King Jesus Version (KJV 1.0) is sealed.
        Verify on the <a href="/witness">Witness page</a>, download from <a href="/download">/download</a>,
        or broadcast via <a href="/share">/share</a>.
      </p>
    </div>
  <?php endif; ?>

  <?php if ($isOperator): ?>
    <div class="stage-card" style="border-color:#facc15;background:#1a1a08">
      <div class="stage-name" style="color:#facc15;font-size:1.2rem">⚙️ Operator next steps</div>
      <p style="color:#888;font-size:.85rem;margin:.5rem 0 1rem">Visible only with valid <code>?op=</code> token.</p>

      <?php if ($isComplete): ?>
        <p style="color:#e0e0e0">Run these on the build host as <code>ubuntu</code>:</p>
        <pre style="background:#0a0a0f;border:1px solid #2a2a3e;border-radius:8px;padding:1rem;color:#00cec9;font-size:.85rem;overflow-x:auto;line-height:1.6">
# 1. Operator gate — size (91 GiB) + optional site verify (copy script to ubuntu or call repo path):
# bash /home/gositeme/domains/alfredlinux.com/public_html/build/scripts/smoke-test-iso.sh   /path/to/frozen.iso
# VERIFY_SITE=1 ALFRED_SITE_ROOT=/home/gositeme/domains/alfredlinux.com/public_html \\
#   bash /home/gositeme/domains/alfredlinux.com/public_html/build/scripts/smoke-test-iso.sh   /path/to/frozen.iso
# bash /home/ubuntu/smoke-test-iso.sh   /path/to/frozen.iso

# 2. If green: regenerate SUMS for the public ISO (b3sum, sha512sum, sha256sum, md5sum)

# 2b. Site gate: SUMS + torrent info-hash must match includes/ga-release-state.php
bash /home/gositeme/domains/alfredlinux.com/public_html/build/scripts/verify-ga-publish-alignment.sh

# 2c. Kingdom size gate (before claiming ~91 GiB on /download)
bash /home/gositeme/domains/alfredlinux.com/public_html/build/scripts/check-iso-91gib.sh   <?= htmlspecialchars(__DIR__ . '/downloads/' . $gaIsoBasename . '.iso', ENT_QUOTES, 'UTF-8') ?>

# 2d. If ISO bytes changed: regenerate .torrent; then run 2b again. Example (adjust -a trackers as needed):
# cd /home/gositeme/domains/alfredlinux.com/public_html/downloads
# mktorrent -l 2097152 -a "wss://tracker.openwebtorrent.com" -o <?= htmlspecialchars($gaIsoBasename . '.iso.torrent', ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($gaIsoBasename . '.iso', ENT_QUOTES, 'UTF-8') ?>
# transmission-show <?= htmlspecialchars($gaIsoBasename . '.iso.torrent', ENT_QUOTES, 'UTF-8') ?>  # Hash must equal magnet btih:
#     <?= htmlspecialchars($gaTorrentBtihHex, ENT_QUOTES, 'UTF-8') ?>  (else bump \$gaTorrentBtihHex in ga-release-state.php)

# 3. Optional: GPG-sign the ISO for the .asc gate
gpg --armor --detach-sign   <?= htmlspecialchars(__DIR__ . '/downloads/' . $gaIsoBasename . '.iso', ENT_QUOTES, 'UTF-8') ?>

# 4. Flip the publish flag (the one command that makes /download go live)
sed -i 's/\$finalGaIsoPublished = false;.*/\$finalGaIsoPublished = true;/'   /home/gositeme/domains/alfredlinux.com/public_html/includes/ga-release-state.php

# 5. Read the broadcast payload
cat /home/gositeme/domains/alfredlinux.com/public_html/announce/latest.json
        </pre>
      <?php elseif ($isError): ?>
        <p style="color:#e17055">Chain in error state. Inspect:</p>
        <pre style="background:#0a0a0f;border:1px solid #2a2a3e;border-radius:8px;padding:1rem;color:#fde68a;font-size:.85rem">
ssh gositeme 'tail -60 /home/gositeme/law/alfredlinux-com-source-live/lb-docker-build.log'
ssh gositeme 'tail -60 /home/gositeme/law/alfredlinux-com-source-live/lb-docker-build.log'
        </pre>
      <?php else: ?>
        <p style="color:#888">Chain still running (<code><?= htmlspecialchars($status['stage']) ?></code>). No action needed — auto-announce watcher (PID checked at runtime) will fire when complete.</p>
        <pre style="background:#0a0a0f;border:1px solid #2a2a3e;border-radius:8px;padding:1rem;color:#fde68a;font-size:.85rem">
ssh gositeme 'tail -60 /home/gositeme/law/alfredlinux-com-source-live/lb-docker-build.log'
ssh gositeme 'tail -60 /home/gositeme/law/alfredlinux-com-source-live/lb-docker-build.log'
        </pre>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <p style="color:#666;font-size:.85rem;text-align:center;margin-top:2rem">
    Auto-refreshes every 10 seconds.
    Raw status: <a href="/reseal-status.txt">reseal-status.txt</a>.
  </p>
</div>

<script>
(function(){
  var target = <?= $releaseTs * 1000 ?>;
  var el = document.getElementById('countdown');
  if (!el) return;
  function pad(n){ return n < 10 ? '0'+n : ''+n; }
  function tick(){
    var diff = target - Date.now();
    if (diff <= 0) {
      el.textContent = 'THE NINTH HOUR';
      el.style.color = '#facc15';
      return;
    }
    var s = Math.floor(diff/1000);
    var h = Math.floor(s/3600); s -= h*3600;
    var m = Math.floor(s/60);   s -= m*60;
    el.textContent = pad(h) + ' : ' + pad(m) + ' : ' + pad(s);
  }
  tick(); setInterval(tick, 1000);
})();
</script>
