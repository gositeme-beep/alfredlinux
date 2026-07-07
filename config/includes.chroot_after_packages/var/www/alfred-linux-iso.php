<?php
/**
 * alfred-linux-iso.php — Plain-English public status for Alfred Linux ISO.
 *
 * Designed so a non-technical reader sees one clear answer:
 *   "Building now" / "Last build failed" / "Build complete — ISO ready"
 * Technical details (run IDs, gates, raw log) are tucked behind <details>.
 */

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');

function safe_read_json(string $path): ?array {
    if (!is_readable($path)) return null;
    $raw = @file_get_contents($path);
    if ($raw === false) return null;
    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function ago(?int $ts): string {
    if (!$ts) return 'unknown';
    $d = time() - $ts;
    if ($d < 0) $d = 0;
    if ($d < 60)        return $d . ' second' . ($d === 1 ? '' : 's') . ' ago';
    if ($d < 3600)      return floor($d/60) . ' minute' . (floor($d/60) === 1.0 ? '' : 's') . ' ago';
    if ($d < 86400)     return floor($d/3600) . ' hour' . (floor($d/3600) === 1.0 ? '' : 's') . ' ago';
    return floor($d/86400) . ' day' . (floor($d/86400) === 1.0 ? '' : 's') . ' ago';
}
function fmt_size(int $bytes): string {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576)    return number_format($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024)       return number_format($bytes / 1024, 1) . ' KB';
    return $bytes . ' bytes';
}

$bridge   = safe_read_json('/home/root/law/alfred-bridge-status.json') ?? [];
$pipeline = safe_read_json('/home/root/law/alfred-build-control-plane/commander-pipeline-state.json') ?? [];

$runs    = $pipeline['runs'] ?? [];
$current = end($runs) ?: null;
reset($runs);

// Live signals (don't trust stale JSON fields alone)
$logPath = '/home/root/law/alfredlinux-com-source-live/lb-docker-build.log';
$logMtime = is_readable($logPath) ? @filemtime($logPath) : null;
$logFresh = $logMtime && (time() - $logMtime) < 180;  // within 3 minutes => active

$logTail = '';
if (is_readable($logPath)) {
    $fh = @fopen($logPath, 'r');
    if ($fh) {
        @fseek($fh, -16384, SEEK_END);
        $chunk = @fread($fh, 16384);
        @fclose($fh);
        if ($chunk !== false) {
            $lines = preg_split('/\r?\n/', trim($chunk));
            $logTail = implode("\n", array_slice($lines, -40));
        }
    }
}

// Detect last completed ISO (newest in iso-output)
$isoDir = '/home/root/law/alfredlinux-com-source-live/iso-output';
$lastIso = null;
if (is_dir($isoDir)) {
    $best = null; $bestM = 0;
    foreach (glob($isoDir . '/*.iso') ?: [] as $f) {
        $m = @filemtime($f);
        if ($m && $m > $bestM) { $bestM = $m; $best = $f; }
    }
    if (!$best) {
        foreach (glob($isoDir . '/archive/*.iso') ?: [] as $f) {
            $m = @filemtime($f);
            if ($m && $m > $bestM) { $bestM = $m; $best = $f; }
        }
    }
    if ($best) {
        $lastIso = [
            'name'  => basename($best),
            'size'  => @filesize($best) ?: 0,
            'mtime' => $bestM,
        ];
    }
}

// Determine the ONE headline status
$tail = $logTail;
$hasRecentError   = $tail && preg_match('/^E: |unexpected failure|exit=\d+|FATAL|ERROR/m', $tail);
$hasRecentSuccess = $tail && preg_match('/lb build finished.*exit=0|live-image-amd64\.hybrid\.iso (created|ok)/i', $tail);

if ($logFresh) {
    $headline = 'BUILDING NOW';
    $headlineColor = '#1c75d8';
    $headlineDetail = 'The Alfred Linux ISO is being assembled right now. This usually takes 30–90 minutes.';
} elseif ($hasRecentError) {
    $headline = 'LAST BUILD FAILED';
    $headlineColor = '#d04b4b';
    $headlineDetail = 'The most recent build did not finish. A commander needs to look at the log below and re-trigger.';
} elseif ($lastIso) {
    $headline = 'BUILD COMPLETE — ISO READY';
    $headlineColor = '#3bb273';
    $headlineDetail = 'The latest finished ISO image is listed below. You can download it or burn it to USB.';
} else {
    $headline = 'IDLE';
    $headlineColor = '#7f8da3';
    $headlineDetail = 'No build is currently running and no finished ISO is on file yet.';
}

// Friendly "what's happening right now" — last meaningful log line
$nowLine = '';
if ($tail) {
    $cands = preg_split('/\r?\n/', $tail);
    for ($i = count($cands) - 1; $i >= 0; $i--) {
        $l = trim($cands[$i]);
        if ($l === '' || strlen($l) < 4) continue;
        if (preg_match('/^(Selecting|Preparing|Unpacking|Setting up|Get:|Reading|Building dependency|Fetched|P: |I: |\[inner\])/', $l)) {
            $nowLine = $l; break;
        }
        if ($nowLine === '') $nowLine = $l;
    }
}

$kernelTarget = $current['kernelTarget'] ?? ($bridge['cp']['kernel_target'] ?? '7.0.12');
$isoVersion   = $current['isoVersion']   ?? ($bridge['cp']['iso_version']   ?? '7.77');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Alfred Linux ISO — Status</title>
<meta http-equiv="refresh" content="30">
<style>
:root { color-scheme: dark; }
* { box-sizing: border-box; }
body { margin:0; font-family: -apple-system, Segoe UI, Roboto, sans-serif; background:#0e1620; color:#e7eef8; line-height:1.5; }
.wrap { max-width: 880px; margin: 24px auto; padding: 0 16px; }
h1 { margin: 0 0 4px; font-size: 24px; }
.tagline { color:#9ab2c7; margin-bottom: 22px; font-size: 14px; }
.headline {
  border-radius: 14px; padding: 22px 22px; margin-bottom: 18px;
  background: linear-gradient(135deg, rgba(255,255,255,0.04), rgba(0,0,0,0.18));
  border: 2px solid var(--accent);
}
.headline .tag {
  display:inline-block; padding:6px 14px; border-radius:999px;
  background: var(--accent); color:#fff; font-weight:800; font-size:13px;
  letter-spacing:.5px; margin-bottom:10px;
}
.headline h2 { margin: 4px 0 6px; font-size: 22px; }
.headline p { margin: 0; color:#cfdcec; }
.card { background:#152233; border:1px solid #2a425a; border-radius:12px; padding:16px 18px; margin-bottom:14px; }
.card h3 { margin: 0 0 10px; font-size:15px; color:#bcd1e8; font-weight:600; }
.row { display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid #243a52; }
.row:last-child { border-bottom: none; }
.row .label { color:#8aa1b8; font-size:13px; }
.row .value { color:#e7eef8; font-weight:600; font-size:14px; text-align:right; }
.now {
  background:#0d1a29; border:1px solid #28415a; border-radius:8px;
  padding:10px 12px; font-family: ui-monospace, Menlo, Consolas, monospace;
  font-size:12px; color:#8fb4d8; word-break:break-all;
}
.iso-card { background: #0d2316; border:1px solid #2e6c46; }
.iso-card .name { font-family: ui-monospace, Menlo, Consolas, monospace; font-size:13px; color:#9be0b8; word-break:break-all; }
details { background:#101a26; border:1px solid #243a52; border-radius:10px; padding:10px 14px; margin-bottom:12px; }
details > summary { cursor:pointer; color:#9ab2c7; font-size:13px; padding:4px 0; }
details[open] > summary { color:#e7eef8; margin-bottom:8px; }
pre { background:#0a121b; border:1px solid #22364a; border-radius:8px; padding:10px;
  white-space:pre-wrap; word-break:break-word; max-height:340px; overflow:auto;
  font-size:11.5px; line-height:1.45; color:#a8c0d8; }
.foot { color:#7f8da3; font-size:12px; margin-top:18px; text-align:center; }
.foot a { color:#4aa3ff; }
@media (max-width: 520px) {
  .row { flex-direction: column; align-items: flex-start; gap:2px; }
  .row .value { text-align:left; }
}
</style>
</head>
<body>
<div class="wrap">

  <h1>Alfred Linux ISO</h1>
  <div class="tagline">Live status of the Alfred Linux operating system build. This page refreshes every 30 seconds.</div>

  <!-- ONE big plain-English status -->
  <div class="headline" style="--accent: <?= h($headlineColor) ?>">
    <span class="tag"><?= h($headline) ?></span>
    <h2>
      <?php if ($headline === 'BUILDING NOW'): ?>
        Alfred Linux is being built right now.
      <?php elseif ($headline === 'LAST BUILD FAILED'): ?>
        The last build attempt did not finish.
      <?php elseif ($headline === 'BUILD COMPLETE — ISO READY'): ?>
        Alfred Linux is built and ready to download.
      <?php else: ?>
        No build is currently running.
      <?php endif; ?>
    </h2>
    <p><?= h($headlineDetail) ?></p>
  </div>

  <!-- Live activity -->
  <div class="card">
    <h3>What's happening right now</h3>
    <div class="row">
      <div class="label">Build activity</div>
      <div class="value"><?= $logFresh ? 'Active (working)' : 'No activity' ?></div>
    </div>
    <div class="row">
      <div class="label">Last update from build log</div>
      <div class="value"><?= h(ago($logMtime)) ?></div>
    </div>
    <div class="row">
      <div class="label">Target kernel</div>
      <div class="value">Linux <?= h($kernelTarget) ?></div>
    </div>
    <div class="row">
      <div class="label">Target version</div>
      <div class="value">Alfred Linux <?= h($isoVersion) ?></div>
    </div>
    <?php if ($nowLine): ?>
    <div style="margin-top:12px;">
      <div class="label" style="color:#8aa1b8; font-size:13px; margin-bottom:6px;">Latest log line</div>
      <div class="now"><?= h($nowLine) ?></div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Last finished ISO -->
  <?php if ($lastIso): ?>
  <div class="card iso-card">
    <h3 style="color:#9be0b8;">Most recent completed ISO</h3>
    <div class="row">
      <div class="label">File name</div>
      <div class="value name"><?= h($lastIso['name']) ?></div>
    </div>
    <div class="row">
      <div class="label">Size</div>
      <div class="value"><?= h(fmt_size($lastIso['size'])) ?></div>
    </div>
    <div class="row">
      <div class="label">Finished</div>
      <div class="value"><?= h(ago($lastIso['mtime'])) ?> · <?= h(date('M j, Y g:i A', $lastIso['mtime'])) ?></div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Technical details collapsed -->
  <details>
    <summary>Show technical details (for builders)</summary>

    <div class="card" style="margin-top:10px;">
      <h3>Internal status</h3>
      <div class="row"><div class="label">Bridge build_phase</div><div class="value"><?= h($bridge['build_phase'] ?? '—') ?></div></div>
      <div class="row"><div class="label">Docker state</div><div class="value"><?= h($bridge['docker_state'] ?? '—') ?></div></div>
      <div class="row"><div class="label">Reported ISO size</div><div class="value"><?= isset($bridge['iso_size_mib']) ? h($bridge['iso_size_mib']) . ' MiB' : '—' ?></div></div>
      <div class="row"><div class="label">Reported ISO mtime</div><div class="value"><?= h($bridge['iso_mtime'] ?? '—') ?></div></div>
      <div class="row"><div class="label">Last bridge poll</div><div class="value"><?= h($bridge['polled_at'] ?? '—') ?></div></div>
      <?php if ($current): ?>
      <div class="row"><div class="label">Current run ID</div><div class="value" style="font-family:ui-monospace,Menlo,Consolas,monospace; font-size:12px;"><?= h($current['runId'] ?? '—') ?></div></div>
      <div class="row"><div class="label">Run status</div><div class="value"><?= h($current['status'] ?? '—') ?></div></div>
      <?php endif; ?>
    </div>

    <?php if ($current && !empty($current['gates'])): ?>
    <div class="card">
      <h3>Pipeline gates</h3>
      <?php foreach ($current['gates'] as $name => $g): ?>
        <div class="row">
          <div class="label"><?= h($name) ?></div>
          <div class="value"><?= h($g['status'] ?? '—') ?><?php if (!empty($g['code'])): ?> · <span style="color:#8aa1b8; font-weight:400;"><?= h($g['code']) ?></span><?php endif; ?></div>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($logTail): ?>
    <div class="card">
      <h3>Recent build log (last 40 lines)</h3>
      <pre><?= h($logTail) ?></pre>
    </div>
    <?php endif; ?>

    <?php if (count($runs) > 1): ?>
    <div class="card">
      <h3>Recent runs</h3>
      <?php foreach (array_slice(array_reverse($runs), 0, 8) as $r): ?>
        <div class="row">
          <div class="label" style="font-family:ui-monospace,Menlo,Consolas,monospace; font-size:11.5px;"><?= h($r['runId'] ?? '') ?></div>
          <div class="value"><?= h($r['status'] ?? '—') ?> · k<?= h($r['kernelTarget'] ?? '?') ?> · v<?= h($r['isoVersion'] ?? '?') ?></div>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </details>

  <div class="foot">
    Builders: use <a href="/commander-build-pipeline.php">/commander-build-pipeline.php</a> to trigger build / sync / publish.<br>
    Page auto-refreshes every 30 seconds.
  </div>
</div>
</body>
</html>
