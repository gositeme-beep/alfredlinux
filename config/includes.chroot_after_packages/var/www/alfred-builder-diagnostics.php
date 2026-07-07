<?php
/**
 * Alfred Builder Diagnostics
 * Single source of truth for ALL build operations:
 *   - Live status of kernel + ISO docker builds
 *   - Every hook (chroot+binary, normal+live), with description, size, perms
 *   - Every package list, every shipped .deb
 *   - Live-build config (auto/config)
 *   - Auto-detected failure signatures from recent logs
 *   - Recent log tails for both pipelines
 *
 * Read-only. Public-safe (no secrets, no shell exec from client).
 */
declare(strict_types=1);

const REPO         = '/home/root/law/alfredlinux-com-source-live';
const ISO_LOG      = REPO . '/lb-docker-build.log';
const ISO_NAME_F   = REPO . '/lb-docker.containername';
const KERN_WORK    = '/home/root/law/kernel-7.0.12-work';
const KERN_LOG     = KERN_WORK . '/build.log';
const KERN_NAME_F  = KERN_WORK . '/docker-bindeb.containername';
const PIPE_STATE   = '/home/root/law/alfred-build-control-plane/commander-pipeline-state.json';
const WATCHER_LOG  = '/home/root/law/auto-iso-restart.log';
const WATCHER_SH   = '/home/root/law/auto-iso-restart.sh';
const CHECKLIST    = '/home/root/law/POST-KERNEL-7.0.12-CHECKLIST.txt';

// ---------- helpers ----------
function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function rfile(string $p): ?string { return is_readable($p) ? @file_get_contents($p) : null; }
function rtail(string $p, int $n = 80): string {
    if (!is_readable($p)) return '(not readable)';
    $lines = @file($p, FILE_IGNORE_NEW_LINES) ?: [];
    return implode("\n", array_slice($lines, -$n));
}
function fmtbytes(int $b): string {
    if ($b < 1024) return $b . ' B';
    if ($b < 1048576) return number_format($b / 1024, 1) . ' KB';
    if ($b < 1073741824) return number_format($b / 1048576, 1) . ' MB';
    return number_format($b / 1073741824, 2) . ' GB';
}
function ago(int $ts): string {
    $d = time() - $ts;
    if ($d < 60) return $d . 's ago';
    if ($d < 3600) return intdiv($d, 60) . 'm ago';
    if ($d < 86400) return intdiv($d, 3600) . 'h ago';
    return intdiv($d, 86400) . 'd ago';
}
/** Run a docker command (read-only), return trimmed stdout or '' */
function dockerExec(string $args): string {
    $cmd = '/usr/bin/docker ' . $args . ' 2>/dev/null';
    $out = @shell_exec($cmd);
    return is_string($out) ? trim($out) : '';
}
function containerStatus(?string $name): array {
    if (!$name) return ['exists' => false];
    $out = dockerExec('ps -a --filter ' . escapeshellarg('name=' . $name) . ' --format "{{.Status}}|{{.RunningFor}}|{{.State}}"');
    if ($out === '') return ['exists' => false];
    [$status, $age, $state] = explode('|', $out . '||') + ['','',''];
    return ['exists' => true, 'status' => $status, 'age' => $age, 'state' => $state, 'running' => str_starts_with($status, 'Up')];
}
/** Pull the first comment block (header) of a hook script for description */
function hookDescription(string $file): string {
    $fh = @fopen($file, 'r'); if (!$fh) return '';
    // PASS 1: look for explicit "# Description:" line in first 30 lines (highest priority)
    $explicit = '';
    $firstEcho = '';
    $desc = '';
    $linesRead = 0;
    $allLines = [];
    while (!feof($fh) && $linesRead < 30) {
        $L = fgets($fh); if ($L === false) break; $linesRead++;
        $allLines[] = rtrim($L);
    }
    fclose($fh);

    foreach ($allLines as $L) {
        if (preg_match('/^#\s*Description:\s*(.+)$/i', $L, $m)) {
            $explicit = trim($m[1]);
            break;
        }
    }
    if ($explicit !== '') {
        if (strlen($explicit) > 180) $explicit = substr($explicit, 0, 177) . '…';
        return $explicit;
    }

    // PASS 2: original comment-block walk
    foreach ($allLines as $i => $L) {
        if ($i >= 12) break;
        // skip shebang
        if (str_starts_with($L, '#!')) continue;
        // STOP at any non-comment, non-blank line (set -e, code, etc.)
        if ($L !== '' && !str_starts_with($L, '#')) break;
        if ($L === '') { if ($desc !== '') break; else continue; }
        $clean = ltrim($L, '# ');
        if ($clean === '') continue;
        // skip noise
        if (str_contains($clean, 'SPDX')) continue;
        if (preg_match('#^/(home|usr|etc)/#', $clean)) continue;
        // STOP at any divider line (── ══ ▓ ▌ etc.)
        if (preg_match('/^[─═━▀▄▌▐▒▓│┃┌┐└┘├┤┬┴┼╔╗╚╝╠╣╦╩╬║]/u', $clean)) break;
        if (preg_match('/[─═━]{3,}/u', $clean)) break;
        $desc .= $clean . ' ';
        if (strlen($desc) > 160) break;
    }
    $out = trim(preg_replace('/\s+/', ' ', $desc) ?: '');

    // PASS 3: fallback to first "echo ..." line if no comment description
    if ($out === '') {
        foreach ($allLines as $L) {
            if (preg_match('/^\s*echo\s+["\']?(\[?[^"\']{5,140})/i', $L, $m)) {
                $firstEcho = trim($m[1], " \t\"'");
                break;
            }
        }
        if ($firstEcho !== '') $out = '⚙ ' . $firstEcho;
    }

    if (strlen($out) > 180) $out = substr($out, 0, 177) . '…';
    return $out;
}

// ---------- failure-signature detector ----------
const FAIL_SIGS = [
    "/cannot stat 'chroot\/boot\/initrd/i" => "Custom kernel installed but no initrd generated. Hook 0990-kernel7-initrd must run.",
    "/binary\/live\/initrd\.img-\*': No such file/i" => "Same as above — initrd missing in binary stage.",
    "/zstd: not found/i"           => "zstd binary missing in build container apt list.",
    "/lz4: not found/i"            => "lz4 binary missing in build container apt list.",
    "/exit status 141/i"           => "SIGPIPE in pipefail. Usually 'yes |' feeding a non-interactive consumer.",
    "/E: An unexpected failure occurred, exiting/i" => "live-build aborted. Check immediately preceding lines.",
    "/initrd link\/copy failure signature/i" => "Postflight gate caught missing initrd in binary stage.",
    "/Killed signal/i"             => "Process killed (likely OOM). Check `dmesg | grep -i oom`.",
    "/No space left on device/i"   => "Disk full. Run `df -h`.",
    "/Cannot allocate memory/i"    => "OOM. Reduce NJOBS or add swap.",
    "/dpkg-buildpackage: error/i"  => "dpkg-buildpackage error — see immediately preceding compile error.",
    "/recipe for target.*failed/i" => "make rule failed.",
    "/error:.*undefined reference/i" => "Linker undefined reference — likely missing CONFIG dependency.",
    "/No such file or directory/i" => "File missing — see preceding line for which.",
];
function detectFailures(string $logTail): array {
    $hits = [];
    foreach (FAIL_SIGS as $rx => $explain) {
        if (preg_match($rx, $logTail, $m)) {
            $hits[] = ['match' => trim($m[0]), 'explain' => $explain];
        }
    }
    return $hits;
}

// ---------- gather data ----------
$kernName  = trim((string)rfile(KERN_NAME_F));
$isoName   = trim((string)rfile(ISO_NAME_F));
$kernCt    = containerStatus($kernName);
$isoCt     = containerStatus($isoName);

// auto-restart watcher
$watcherPid = trim((string)@shell_exec("pgrep -f 'auto-iso-restart\\.sh' | head -1 2>/dev/null"));
$watcherRunning = $watcherPid !== '' && ctype_digit($watcherPid);
$watcherTail = rtail(WATCHER_LOG, 40);
$watcherMtime = is_readable(WATCHER_LOG) ? @filemtime(WATCHER_LOG) : 0;
$kernTail  = rtail(KERN_LOG, 80);
$isoTail   = rtail(ISO_LOG, 80);
$kernLogBig= rtail(KERN_LOG, 2000);
$isoLogBig = rtail(ISO_LOG, 2000);
$kernFails = detectFailures($kernLogBig);
$isoFails  = detectFailures($isoLogBig);

$kernMtime = is_file(KERN_LOG) ? filemtime(KERN_LOG) : 0;
$isoMtime  = is_file(ISO_LOG)  ? filemtime(ISO_LOG)  : 0;

// hooks
$hookGroups = [
    'config/hooks (root)'        => REPO . '/config/hooks',
    'config/hooks/normal'        => REPO . '/config/hooks/normal',
    'config/hooks/live'          => REPO . '/config/hooks/live',
];
$hooks = [];
foreach ($hookGroups as $label => $dir) {
    $hooks[$label] = [];
    if (!is_dir($dir)) continue;
    foreach (scandir($dir) ?: [] as $f) {
        if ($f === '.' || $f === '..' || str_contains($f, '.bak')) continue;
        $full = $dir . '/' . $f;
        if (!is_file($full)) continue;
        $hooks[$label][] = [
            'name'  => $f,
            'path'  => $full,
            'size'  => filesize($full) ?: 0,
            'exec'  => is_executable($full),
            'mtime' => filemtime($full) ?: 0,
            'desc'  => hookDescription($full),
            'stage' => str_contains($f, '.binary') ? 'binary' : (str_contains($f, '.chroot') ? 'chroot' : '?'),
            'enabled' => !str_contains($f, '.disabled'),
        ];
    }
    usort($hooks[$label], fn($a,$b) => strcmp($a['name'], $b['name']));
}

// package lists
$pkgListsDir = REPO . '/config/package-lists';
$pkgLists = [];
foreach (scandir($pkgListsDir) ?: [] as $f) {
    if ($f === '.' || $f === '..' || str_contains($f, '.bak')) continue;
    $full = $pkgListsDir . '/' . $f;
    if (!is_file($full)) continue;
    $body = (string)rfile($full);
    $pkgs = preg_grep('/^[a-z0-9]/', array_map('trim', explode("\n", $body))) ?: [];
    $pkgLists[$f] = ['count' => count($pkgs), 'sample' => array_slice($pkgs, 0, 8)];
}

// shipped debs
$debs = [];
foreach (glob(REPO . '/config/packages.chroot/*.deb') ?: [] as $f) {
    $debs[] = ['name' => basename($f), 'size' => filesize($f) ?: 0, 'mtime' => filemtime($f) ?: 0];
}

// auto-detected hook hygiene issues
$hookIssues = [];
foreach ($hookGroups as $label => $dir) {
    if (!is_dir($dir)) continue;
    foreach (scandir($dir) ?: [] as $f) {
        if ($f === '.' || $f === '..') continue;
        $full = $dir . '/' . $f;
        if (!is_file($full)) continue;
        if (preg_match('/\.bak|~$|\.old$/', $f)) {
            $hookIssues[] = ['sev' => 'warn', 'msg' => "$label/$f — backup file in active hook directory (live-build will ignore but it's clutter)"];
        }
        if (preg_match('/\.hook\.(chroot|binary)$/', $f) && !is_executable($full)) {
            $hookIssues[] = ['sev' => 'err',  'msg' => "$label/$f — NOT executable (live-build will SKIP it silently)"];
        }
        if (preg_match('/\.hook\.(chroot|binary)$/', $f) && filesize($full) < 30) {
            $hookIssues[] = ['sev' => 'warn', 'msg' => "$label/$f — file size <30 bytes (likely empty/truncated)"];
        }
    }
}

// live-build auto/config
$autoConfig = (string)rfile(REPO . '/auto/config');

// kernel version consistency
$autoCfgFlavour = '';
if (preg_match('/--linux-flavours\s+(\S+)/', $autoConfig, $m)) $autoCfgFlavour = $m[1];
$debKvers = [];
foreach ($debs as $d) if (preg_match('/^linux-image-([0-9.]+)_/', $d['name'], $mm)) $debKvers[] = $mm[1];
$debKvers = array_unique($debKvers);
$kernelMismatch = ($autoCfgFlavour && $debKvers && !in_array($autoCfgFlavour, $debKvers, true));

// pipeline state json
$pipeState = json_decode((string)rfile(PIPE_STATE), true) ?: [];
$lastRun = $pipeState['runs'][0] ?? null;
?>
<!doctype html>
<html lang="en"><head>
<meta charset="utf-8">
<title>Alfred Builder Diagnostics</title>
<meta http-equiv="refresh" content="60">
<style>
:root { color-scheme: dark; }
* { box-sizing: border-box; }
body { background:#0b1220; color:#e6edf3; font:14px/1.5 -apple-system,Segoe UI,Roboto,sans-serif; margin:0; padding:24px; }
h1 { margin:0 0 6px; font-size:24px; }
h2 { margin:32px 0 10px; padding-bottom:6px; border-bottom:1px solid #1f2a3d; font-size:18px; color:#7ed4ff; }
h3 { margin:20px 0 8px; font-size:15px; color:#9ec5ff; }
.sub { color:#8a9bb4; font-size:13px; }
.cards { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin:14px 0; }
.card { background:#101a2e; border:1px solid #1f2a3d; border-radius:10px; padding:14px 18px; }
.card.ok { border-color:#23643a; background:#0f1d16; }
.card.run { border-color:#1d4d8c; background:#0d1828; }
.card.bad { border-color:#7a1f1f; background:#1d0f10; }
.card.idle { border-color:#4a4a4a; background:#161616; }
.lbl { color:#8a9bb4; font-size:12px; text-transform:uppercase; letter-spacing:.04em; }
.val { font-size:15px; font-weight:600; margin-top:2px; }
.tag { display:inline-block; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:700; }
.tag.green { background:#1f5d35; color:#a6f3c2; }
.tag.blue  { background:#1d3a6b; color:#a6c8ff; }
.tag.red   { background:#6e1f1f; color:#ffb3b3; }
.tag.gray  { background:#27344b; color:#9eb1cc; }
table { width:100%; border-collapse:collapse; font-size:13px; }
th, td { text-align:left; padding:6px 8px; border-bottom:1px solid #1a2233; vertical-align:top; }
th { color:#8a9bb4; font-weight:600; font-size:11px; text-transform:uppercase; letter-spacing:.04em; background:#0d1626; position:sticky; top:0; }
tr:hover td { background:#101a2e; }
code, pre { font-family:ui-monospace,Menlo,Consolas,monospace; }
pre { background:#0a1224; border:1px solid #1a2233; border-radius:8px; padding:12px; overflow-x:auto; max-height:520px; overflow-y:auto; font-size:12px; line-height:1.45; }
.hookname { font-family:ui-monospace,Menlo,Consolas,monospace; font-size:12px; color:#cbe1ff; }
.fail-row { background:#2a0f10; }
.fail-row td { color:#ffb3b3; }
.muted { color:#8a9bb4; }
details { margin:6px 0; }
summary { cursor:pointer; color:#9ec5ff; padding:4px 0; }
.grid3 { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; }
.kv { display:grid; grid-template-columns:160px 1fr; gap:6px 14px; font-size:13px; }
.kv .k { color:#8a9bb4; }
.searchbox { width:100%; padding:8px 12px; background:#0a1224; border:1px solid #1f2a3d; color:#e6edf3; border-radius:6px; margin:6px 0 10px; font:13px ui-monospace,Menlo,Consolas,monospace; }
</style>
</head><body>

<h1>Alfred Builder — Full Diagnostics</h1>
<div class="sub">Auto-refresh 60s · Generated <?= date('Y-m-d H:i:s T') ?></div>

<!-- ============ LIVE STATUS ============ -->
<h2>Live Build Status</h2>
<div class="cards">
  <?php
  $kCls = !$kernCt['exists'] ? 'bad' : ($kernCt['running'] ? 'run' : (str_contains($kernCt['status']??'','Exited (0)') ? 'ok' : 'bad'));
  ?>
  <div class="card <?= $kCls ?>">
    <div class="lbl">Kernel 7.0.12 build (bindeb-pkg)</div>
    <div class="val">
      <?= $kernCt['exists'] ? h($kernCt['status']) : 'no container' ?>
    </div>
    <div class="kv" style="margin-top:8px">
      <div class="k">Container</div><div><code><?= h($kernName ?: '—') ?></code></div>
      <div class="k">Last log update</div><div><?= $kernMtime ? ago($kernMtime) : '—' ?></div>
      <div class="k">Log path</div><div><code><?= h(KERN_LOG) ?></code></div>
      <div class="k">Work dir</div><div><code><?= h(KERN_WORK) ?></code></div>
    </div>
  </div>
  <?php
  $iCls = !$isoCt['exists'] ? 'idle' : ($isoCt['running'] ? 'run' : (str_contains($isoCt['status']??'','Exited (0)') ? 'ok' : 'bad'));
  $isoVal = $isoCt['exists']
      ? h($isoCt['status'])
      : 'IDLE — waiting for kernel 7.0.12 deb (auto-restart watcher will trigger)';
  ?>
  <div class="card <?= $iCls ?>">
    <div class="lbl">ISO build (lb build, live-build/Docker)</div>
    <div class="val">
      <?= $isoVal ?>
    </div>
    <div class="kv" style="margin-top:8px">
      <?php if ($isoCt['exists']): ?>
      <div class="k">Container</div><div><code><?= h($isoName ?: '—') ?></code></div>
      <?php endif; ?>
      <div class="k">Last log update</div><div><?= $isoMtime ? ago($isoMtime) : '—' ?></div>
      <div class="k">Log path</div><div><code><?= h(ISO_LOG) ?></code></div>
      <div class="k">Repo</div><div><code><?= h(REPO) ?></code></div>
      <?php
      // Only show pipeline run info if recent (<6h) OR there's an active container
      $showRun = $lastRun && ($isoCt['exists'] || (!empty($lastRun['startedAt']) && (time() - strtotime($lastRun['startedAt'])) < 6*3600));
      if ($showRun): ?>
      <div class="k">Pipeline runId</div><div><?= h($lastRun['runId'] ?? '—') ?> · <?= h($lastRun['status'] ?? '—') ?></div>
      <div class="k">Kernel target</div><div><?= h($lastRun['kernelTarget'] ?? '—') ?></div>
      <div class="k">ISO version</div><div><?= h($lastRun['isoVersion'] ?? '—') ?></div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- ============ AUTO-RESTART WATCHER ============ -->
<h2>Auto-Restart Watcher</h2>
<div class="sub">Polls <code><?= h(KERN_WORK) ?></code> every 60s. When <code>linux-image-7.0.12_*.deb</code> appears, automatically: stages debs → regenerates apt index → rewires <code>auto/config</code> → kicks off ISO build. <strong>You don't have to do anything.</strong></div>
<div class="cards">
  <div class="card <?= $watcherRunning ? 'run' : 'bad' ?>">
    <div class="lbl">Watcher daemon</div>
    <div class="val"><?= $watcherRunning ? "RUNNING (PID $watcherPid)" : 'NOT RUNNING' ?></div>
    <div class="kv" style="margin-top:8px">
      <div class="k">Script</div><div><code><?= h(WATCHER_SH) ?></code></div>
      <div class="k">Log path</div><div><code><?= h(WATCHER_LOG) ?></code></div>
      <div class="k">Last log update</div><div><?= $watcherMtime ? ago($watcherMtime) : '—' ?></div>
      <div class="k">Manual checklist</div><div><code><?= h(CHECKLIST) ?></code></div>
    </div>
  </div>
  <div class="card">
    <div class="lbl">Watcher activity (last 40 lines)</div>
    <pre style="max-height:220px;overflow:auto;font-size:11px;background:#0a0e14;color:#9bc;padding:8px;margin-top:6px"><?= h($watcherTail ?: '(no activity yet — waiting for kernel deb)') ?></pre>
  </div>
</div>

<!-- ============ AUTO FAILURE DETECTION ============ -->
<h2>Auto-Detected Failure Signatures</h2>
<div class="grid3" style="grid-template-columns:1fr 1fr">
  <div class="card <?= empty($kernFails) ? 'ok' : 'bad' ?>">
    <div class="lbl">Kernel build log</div>
    <?php if (empty($kernFails)): ?>
      <div class="val">No known failure signatures detected.</div>
    <?php else: ?>
      <table>
      <?php foreach ($kernFails as $f): ?>
        <tr class="fail-row">
          <td><code><?= h($f['match']) ?></code><br><span class="muted"><?= h($f['explain']) ?></span></td>
        </tr>
      <?php endforeach; ?>
      </table>
    <?php endif; ?>
  </div>
  <div class="card <?= empty($isoFails) ? 'ok' : 'bad' ?>">
    <div class="lbl">ISO build log</div>
    <?php if (empty($isoFails)): ?>
      <div class="val">No known failure signatures detected.</div>
    <?php else: ?>
      <table>
      <?php foreach ($isoFails as $f): ?>
        <tr class="fail-row">
          <td><code><?= h($f['match']) ?></code><br><span class="muted"><?= h($f['explain']) ?></span></td>
        </tr>
      <?php endforeach; ?>
      </table>
    <?php endif; ?>
  </div>
</div>

<!-- ============ HOOKS ============ -->
<h2>Hooks (live-build)</h2>
<div class="sub">live-build only executes hooks in <code>config/hooks/normal/</code> and <code>config/hooks/live/</code>. Files at <code>config/hooks/</code> root are <strong>ignored</strong>.</div>

<!-- pipeline hygiene checks -->
<h3>Pipeline Hygiene Checks</h3>
<?php
$cleanCards = [
    ['Hook hygiene', empty($hookIssues), $hookIssues, 'No hook hygiene issues.'],
    ['Kernel version consistency', !$kernelMismatch,
        $kernelMismatch ? [['sev'=>'err','msg'=>"auto/config wants --linux-flavours '{$autoCfgFlavour}' but shipped debs are for ".implode(',',$debKvers)]] : [],
        "auto/config flavour '{$autoCfgFlavour}' matches shipped deb(s) ".implode(',',$debKvers).'.'],
];
?>
<div class="grid3" style="grid-template-columns:1fr 1fr">
<?php foreach ($cleanCards as [$ttl, $ok, $issues, $okMsg]): ?>
  <div class="card <?= $ok ? 'ok' : 'bad' ?>">
    <div class="lbl"><?= h($ttl) ?></div>
    <?php if ($ok): ?>
      <div class="val">✓ <?= h($okMsg) ?></div>
    <?php else: ?>
      <table>
      <?php foreach ($issues as $i): ?>
        <tr class="fail-row"><td><span class="tag <?= $i['sev']==='err'?'red':'gray' ?>"><?= h($i['sev']) ?></span> <?= h($i['msg']) ?></td></tr>
      <?php endforeach; ?>
      </table>
    <?php endif; ?>
  </div>
<?php endforeach; ?>
</div>

<input class="searchbox" id="hookFilter" placeholder="filter hooks (name or description)..." oninput="filterHooks(this.value)">

<?php foreach ($hooks as $label => $list): ?>
  <h3><?= h($label) ?> · <span class="muted"><?= count($list) ?> file(s)</span></h3>
  <?php if (str_contains($label,'(root)') && count($list) > 0): ?>
    <div class="card bad" style="margin-bottom:10px">
      <strong>WARNING:</strong> <?= count($list) ?> hook(s) sit at <code>config/hooks/</code> root and will <strong>NOT be run by live-build</strong>. Move them to <code>normal/</code> or <code>live/</code>.
    </div>
  <?php endif; ?>
  <?php if (empty($list)): ?>
    <div class="muted">— empty —</div>
  <?php else: ?>
    <table class="hooks">
      <thead><tr>
        <th>Name</th><th>Stage</th><th>Exec</th><th>Size</th><th>Modified</th><th>Description</th>
      </tr></thead>
      <tbody>
      <?php foreach ($list as $hk): ?>
        <tr class="hookrow" data-search="<?= h(strtolower($hk['name'].' '.$hk['desc'])) ?>">
          <td class="hookname"><?= h($hk['name']) ?>
            <?php if (!$hk['enabled']): ?> <span class="tag gray">DISABLED</span><?php endif; ?>
          </td>
          <td><span class="tag <?= $hk['stage']==='chroot'?'blue':($hk['stage']==='binary'?'green':'gray') ?>"><?= h($hk['stage']) ?></span></td>
          <td><?= $hk['exec'] ? '<span class="tag green">+x</span>' : '<span class="tag red">no</span>' ?></td>
          <td class="muted"><?= fmtbytes($hk['size']) ?></td>
          <td class="muted"><?= $hk['mtime'] ? date('Y-m-d', $hk['mtime']) : '—' ?></td>
          <td><?= h($hk['desc'] ?: '—') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
<?php endforeach; ?>

<!-- ============ PACKAGE LISTS ============ -->
<h2>Package Lists (config/package-lists)</h2>
<table>
  <thead><tr><th>File</th><th>Pkg count</th><th>Sample (first 8)</th></tr></thead>
  <tbody>
  <?php foreach ($pkgLists as $name => $info): ?>
    <tr>
      <td class="hookname"><?= h($name) ?></td>
      <td><?= (int)$info['count'] ?></td>
      <td class="muted"><?= h(implode(', ', $info['sample'])) ?><?= count($info['sample']) >= 8 ? '…' : '' ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<!-- ============ SHIPPED DEBS ============ -->
<h2>Pre-built .deb Packages (config/packages.chroot)</h2>
<table>
  <thead><tr><th>File</th><th>Size</th><th>Modified</th></tr></thead>
  <tbody>
  <?php foreach ($debs as $d): ?>
    <tr>
      <td class="hookname"><?= h($d['name']) ?></td>
      <td><?= fmtbytes($d['size']) ?></td>
      <td class="muted"><?= date('Y-m-d H:i', $d['mtime']) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<!-- ============ AUTO/CONFIG ============ -->
<h2>live-build Configuration (auto/config)</h2>
<details><summary>Show full file</summary>
<pre><?= h($autoConfig) ?></pre>
</details>

<!-- ============ LOG TAILS ============ -->
<h2>Recent Logs (last 80 lines)</h2>
<h3>Kernel build log</h3>
<pre><?= h($kernTail) ?></pre>
<h3>ISO build log</h3>
<pre><?= h($isoTail) ?></pre>

<script>
function filterHooks(q) {
  q = (q||'').trim().toLowerCase();
  document.querySelectorAll('tr.hookrow').forEach(r => {
    r.style.display = !q || r.dataset.search.includes(q) ? '' : 'none';
  });
}
</script>

</body></html>
