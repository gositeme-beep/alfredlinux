<?php
/**
 * Slim live build-status banner for /download.
 * Reads /reseal-status.txt. Auto-refreshes every 30s while building.
 * Hides itself once GA is published AND artifact is on disk.
 */
if (defined('ALFRED_BUILD_STATUS_BANNER_RENDERED')) { return; }
define('ALFRED_BUILD_STATUS_BANNER_RENDERED', true);

if (!isset($gaIsoBasename)) {
    require_once dirname(__DIR__) . '/includes/ga-release-state.php';
}

$_bsRoot = dirname(__DIR__);
$_bsStatus = ['stage' => 'unknown', 'msg' => '', 'ts' => '', 'iso_sha256' => ''];
$_bsFile = $_bsRoot . '/reseal-status.txt';
if (is_file($_bsFile)) {
    foreach (file($_bsFile, FILE_IGNORE_NEW_LINES) as $_l) {
        if (strpos($_l, '=') !== false) {
            [$_k, $_v] = explode('=', $_l, 2);
            $_bsStatus[$_k] = $_v;
        }
    }
}

$_bsCandidates = [
  '/home/gositeme/alfred-linux-v2/build/binary.hybrid.iso',
  $_bsRoot . '/downloads/' . $gaIsoBasename . '.iso',
  $_bsRoot . '/downloads/alfred-linux-7.77-ga-amd64-omega-point.iso', // legacy basename before intel-amd64 rename
  $_bsRoot . '/downloads/alfred-linux-7.77-ga-amd64-20260412.iso',
];
$_bsBytes = 0; $_bsPath = '';
foreach ($_bsCandidates as $_p) {
    if (is_file($_p)) { $_bsBytes = (int) @filesize($_p); $_bsPath = $_p; break; }
}
$_bsTarget = (int) round(7.77 * 1073741824);
$_bsGiB = $_bsBytes / 1073741824;
$_bsSizePct = $_bsTarget > 0 ? min(100, ($_bsBytes / $_bsTarget) * 100) : 0;

$_bsStages = [
    'starting'      => ['Starting',          5],
    'kyber-scan'    => ['Kyber-1024 scan',  15],
    'kyber-rewrite' => ['Kyber-1024 rewrite',30],
    'kyber-verify'  => ['Kyber-1024 verify', 40],
    'level-777'     => ['Level-777 BIOS',    55],
    'kernel-7'      => ['Kernel 7 compile',  65],
    'reseal-iso'    => ['Reseal ISO',        75],
    'mksquashfs'    => ['mksquashfs',        85],
    'xorriso'       => ['xorriso pack',      95],
    'complete'      => ['Sealed',           100],
    'error'         => ['Error',              0],
    'unknown'       => ['Waiting on watcher', 0],
];
[$_bsLabel, $_bsPct] = $_bsStages[$_bsStatus['stage']] ?? $_bsStages['unknown'];
$_bsComplete = ($_bsStatus['stage'] === 'complete');
$_bsError    = ($_bsStatus['stage'] === 'error');

$_bsGaPublished = !empty($finalGaIsoPublished) && $_bsBytes > 0 && strpos($_bsPath, '/downloads/') !== false;
if ($_bsGaPublished && $_bsComplete) { return; }

if (!$_bsComplete && !$_bsError) {
    echo '<meta http-equiv="refresh" content="30">' . "\n";
}

$_bsAccent = $_bsError ? '#e17055' : ($_bsComplete ? '#10b981' : '#a78bfa');
?>
<style>
  .albs { max-width: 980px; margin: 1.25rem auto; padding: 0 1rem; font-family: inherit; }
  .albs-strip { background: rgba(18,18,26,.75); border: 1px solid <?= $_bsAccent ?>; border-left: 3px solid <?= $_bsAccent ?>; border-radius: 8px; padding: .65rem 1rem; color: #cbd5e1; font-size: .82rem; display: flex; align-items: center; gap: .9rem; flex-wrap: wrap; }
  .albs-label { color: <?= $_bsAccent ?>; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; font-size: .72rem; white-space: nowrap; }
  .albs-stage { color: #f1f5f9; font-weight: 600; }
  .albs-bar { flex: 1; min-width: 120px; height: 6px; background: rgba(255,255,255,.06); border-radius: 3px; overflow: hidden; }
  .albs-fill { height: 100%; background: <?= $_bsAccent ?>; width: <?= number_format($_bsPct, 1) ?>%; transition: width .5s; }
  .albs-meta { color: #94a3b8; font-size: .76rem; white-space: nowrap; }
  .albs-link { color: #67e8f9; text-decoration: none; font-weight: 600; }
  .albs-link:hover { text-decoration: underline; }
  .albs-dot { display: inline-block; width: 7px; height: 7px; background: <?= $_bsAccent ?>; border-radius: 50%; margin-right: 6px; vertical-align: middle; animation: albsPulse 1.6s ease-in-out infinite; }
  @keyframes albsPulse { 0%,100% { opacity: 1; } 50% { opacity: .25; } }
</style>
<div class="albs" role="status" aria-live="polite">
  <div class="albs-strip">
    <span class="albs-label"><?php if (!$_bsComplete && !$_bsError): ?><span class="albs-dot"></span><?php endif; ?><?= $_bsComplete ? 'Sealed' : ($_bsError ? 'Build error' : 'Building live') ?></span>
    <span class="albs-stage"><?= htmlspecialchars($_bsLabel) ?></span>
    <span class="albs-bar"><span class="albs-fill"></span></span>
    <span class="albs-meta"><?= number_format($_bsPct, 0) ?>%<?php if ($_bsBytes > 0): ?> · <?= number_format($_bsGiB, 2) ?> / 7.77 GiB<?php endif; ?></span>
    <a class="albs-link" href="/reseal">follow the build →</a>
  </div>
</div>
