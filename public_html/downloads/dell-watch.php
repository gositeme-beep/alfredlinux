<?php
// Dell Watch - Commander dashboard
$validTokens = [
    'COMMANDER-INTERNAL-777' => ['name' => 'Commander', 'expires' => '2027-12-31'],
    'COMMANDER-OPS-ALPHA'    => ['name' => 'Commander Alpha', 'expires' => '2027-12-31'],
];

$token = (string)($_GET['token'] ?? $_COOKIE['cwtoken'] ?? '');
$tokenData = $validTokens[$token] ?? null;
if (!$tokenData) {
    http_response_code(403);
    echo '<!doctype html><html><head><meta charset="utf-8"><title>403</title></head><body style="background:#0b0f14;color:#ff5a7a;font-family:system-ui;padding:32px"><h1>403 - Commander Access Only</h1><p>Add ?token=COMMANDER-INTERNAL-777</p></body></html>';
    exit;
}
if (strtotime((string)$tokenData['expires']) < time()) {
    http_response_code(410);
    exit('Token expired');
}
setcookie('cwtoken', $token, time()+86400, '/', '', true, true);

$downloadsDir = __DIR__;
$logFile = $downloadsDir . '/dell-download-log.txt';
$buildLog = '/home/gositeme/law/alfredlinux-com-source-live/lb-docker-build.log';
$holdFlag = '/tmp/alfred-commander-hold';

require_once __DIR__ . '/../includes/ga-release-state.php';

function dw_parse_line(string $line): array {
    $out = [];
    foreach (explode(' | ', $line) as $part) {
        $eq = strpos($part, '=');
        if ($eq === false) continue;
        $k = substr($part, 0, $eq);
        $v = substr($part, $eq + 1);
        $out[$k] = $v;
    }
    return $out;
}

function dw_tail_lines(string $file, int $max = 300): array {
    if (!is_readable($file)) return [];
    $all = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($all)) return [];
    return array_slice($all, -$max);
}

function dw_latest_iso(string $dir): ?array {
    $isos = glob($dir . '/alfred-linux-*.iso');
    if (!$isos) return null;
    usort($isos, static fn($a, $b) => filemtime($b) <=> filemtime($a));
    $iso = $isos[0] ?? null;
    if (!$iso) return null;
    $asc = is_readable($iso . '.asc') && (int)@filesize($iso . '.asc') > 64;
    return [
        'name' => basename($iso),
        'path' => $iso,
        'size' => (int)@filesize($iso),
        'mtime' => (int)@filemtime($iso),
        'asc' => $asc,
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hold'])) {
    if ($_POST['hold'] === 'on') @touch($holdFlag);
    if ($_POST['hold'] === 'off') @unlink($holdFlag);
    header('Location: ?token=' . rawurlencode($token));
    exit;
}

if (($_GET['action'] ?? '') === 'feed') {
    header('Content-Type: application/json; charset=utf-8');

    $rows = [];
    foreach (array_reverse(dw_tail_lines($logFile, 500)) as $line) {
        $r = dw_parse_line($line);
        if (!$r) continue;
        $rows[] = [
            'ts' => (string)($r['ts'] ?? ''),
            'event' => (string)($r['event'] ?? ''),
            'partner' => (string)($r['partner'] ?? ''),
            'token' => (string)($r['token'] ?? ''),
            'ip' => (string)($r['ip'] ?? ''),
            'ua' => (string)($r['ua'] ?? ''),
            'ref' => (string)($r['ref'] ?? ''),
            'iso' => (string)($r['iso'] ?? ''),
            'bytes' => (int)($r['bytes'] ?? 0),
        ];
        if (count($rows) >= 120) break;
    }

    $tail = dw_tail_lines($buildLog, 1200);
    $lastTail = $tail ? end($tail) : '';
    $phase = 'BUILDING';
    if ($tail) {
        // p32: scope phase detection to the CURRENT build only — find the most
        // recent "[inner] lb build starting at" marker and ignore everything before it.
        $startIdx = -1;
        for ($i = count($tail) - 1; $i >= 0; $i--) {
            if (strpos($tail[$i], '[inner] lb build starting at') !== false) { $startIdx = $i; break; }
        }
        $current = $startIdx >= 0 ? array_slice($tail, $startIdx) : array_slice($tail, -120);
        $joined = implode("\n", $current);
        if (preg_match('/lb build finished.*exit=0/i', $joined)
            && preg_match('/published fresh ISO|live-image-amd64\.hybrid\.iso.*[1-9]/', $joined)) {
            $phase = 'DONE';
        } elseif (stripos($joined, 'unexpected failure occurred') !== false
               || (preg_match('/^E: /m', $joined) && stripos($joined, 'needrestart') === false)) {
            $phase = 'FAILED';
        }
    }

    $iso = dw_latest_iso($downloadsDir);

    echo json_encode([
        'ok' => true,
        'rows' => $rows,
        'ga' => [
            'gaFrozenIsoHookCount' => (int)($gaFrozenIsoHookCount ?? 0),
            'gaPlannedHookCount' => (int)($gaPlannedHookCount ?? 150),
            'finalGaIsoPublished' => (string)($finalGaIsoPublished ?? 'false'),
        ],
        'iso' => [
            'exists' => (bool)$iso,
            'name' => (string)($iso['name'] ?? ''),
            'size' => (int)($iso['size'] ?? 0),
            'mtime' => (int)($iso['mtime'] ?? 0),
            'asc' => (bool)($iso['asc'] ?? false),
        ],
        'build' => [
            'phase' => $phase,
            'log_tail' => (string)$lastTail,
        ],
        'hold' => is_file($holdFlag),
        'ts' => date('c'),
    ], JSON_UNESCAPED_SLASHES);
    exit;
}

$iso = dw_latest_iso($downloadsDir);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dell Watch ? Live Partner Download Feed</title>
<meta name="robots" content="noindex,nofollow">
<style>
:root{--bg:#0a0e14;--panel:#11161e;--b:#222b38;--a:#00ffaa;--d:#0076ce;--t:#d8e0ec;--m:#7a8699;--w:#ffb84d;--e:#ff5577}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--t);font:13px/1.45 ui-monospace,Consolas,monospace}
header{padding:16px 24px;border-bottom:1px solid var(--b);background:linear-gradient(90deg,#0a1422,#11203b);display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap}
main{max-width:1500px;margin:0 auto;padding:16px 24px}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px}
.card,.sec{background:var(--panel);border:1px solid var(--b);border-radius:8px;padding:12px}.sec{margin-top:12px}
.h{font-size:11px;text-transform:uppercase;color:var(--m)}.big{font-size:24px;color:var(--a);font-weight:700}.warn{color:var(--w)}.err{color:var(--e)}
button,a.btn{border:1px solid var(--b);background:#151d29;color:var(--t);padding:7px 10px;border-radius:5px;text-decoration:none;font:inherit;cursor:pointer}
a.btn.d{background:var(--d);color:#fff;border-color:transparent}
code{background:#0b1119;border:1px solid var(--b);padding:1px 6px;border-radius:4px;color:#ffd700}
table{width:100%;border-collapse:collapse}th,td{border-bottom:1px solid var(--b);padding:7px 8px;text-align:left;vertical-align:top}th{font-size:10px;color:var(--m);text-transform:uppercase}
.p{display:inline-block;font-size:10px;border-radius:999px;padding:2px 8px}.p1{background:#13291f;color:var(--a)}.p2{background:#122233;color:#8aa8ff}.p3{background:#341722;color:#ff7b95}
.row{display:flex;gap:8px;flex-wrap:wrap;align-items:center}.muted{color:var(--m)}
</style>
</head>
<body>
<header>
  <div><strong>Dell Watch</strong> <span class="muted">Live Partner Download Feed ? Commander Eyes Only</span></div>
  <div class="row">
    <span id="live" class="muted">Polling every 5s</span>
    <button id="refresh" type="button">Refresh now</button>
    <a class="btn" target="_blank" href="?action=feed&token=<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">feed JSON</a>
  </div>
</header>
<main>
  <div class="grid">
    <div class="card"><div class="h">Build</div><div id="buildPhase" class="big">BUILDING</div><div id="buildTail" class="muted">waiting for data...</div></div>
    <div class="card"><div class="h">Published ISO</div><div id="isoSize" class="big"><?= $iso ? round(($iso['size']/1073741824),2) . ' GB' : '?' ?></div><div id="isoMeta" class="muted"><?= $iso ? htmlspecialchars($iso['name'], ENT_QUOTES, 'UTF-8') : 'No ISO found' ?></div></div>
    <div class="card"><div class="h">GA Hook Stamp</div><div id="gaHooks" class="big"><?= (int)($gaFrozenIsoHookCount ?? 0) ?> / <?= (int)($gaPlannedHookCount ?? 150) ?></div><div class="muted">369-hook source tree</div></div>
    <div class="card"><div class="h">Hold Flag</div><div id="holdState" class="big <?= is_file($holdFlag) ? 'warn' : '' ?>"><?= is_file($holdFlag) ? 'ON' : 'OFF' ?></div>
      <form method="post" class="row" style="margin-top:8px">
        <button type="submit" name="hold" value="on">Hold ON</button>
        <button type="submit" name="hold" value="off">Hold OFF</button>
      </form>
    </div>
  </div>

  <div class="sec">
    <div class="row" style="justify-content:space-between"><div><strong>Live Hit Log</strong> <span class="muted">newest first</span></div><a class="btn d" target="_blank" href="dell-partner.php?token=COMMANDER-INTERNAL-777">Open partner page</a></div>
    <div style="max-height:60vh;overflow:auto;margin-top:10px">
      <table>
        <thead><tr><th>Time</th><th>Event</th><th>Partner/Token</th><th>IP</th><th>UA</th><th>Detail</th></tr></thead>
        <tbody id="feedBody"><tr><td colspan="6" class="muted">Loading...</td></tr></tbody>
      </table>
    </div>
  </div>
</main>
<script>
const token = <?= json_encode($token, JSON_UNESCAPED_SLASHES) ?>;
const el = (id) => document.getElementById(id);
function esc(s){return String(s??'').replace(/[&<>"']/g,m=>({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;"}[m]))}
function bytes(n){n=+n||0;if(n<1024)return n+' B';const u=['KB','MB','GB','TB'];for(let i=0;i<u.length;i++){const v=n/Math.pow(1024,i+1);if(v<1024)return (v<10?v.toFixed(2):v.toFixed(1))+' '+u[i]}return (n/Math.pow(1024,4)).toFixed(2)+' TB'}
function evPill(ev){if(ev==='DOWNLOAD_START'||ev==='DELL_DOWNLOAD') return '<span class="p p1">DOWNLOAD</span>'; if(ev==='PAGE_VIEW') return '<span class="p p2">PAGE VIEW</span>'; if(ev==='TOKEN_REJECTED') return '<span class="p p3">REJECTED</span>'; return '<span class="p">'+esc(ev)+'</span>'}
async function poll(){
  try{
    const r = await fetch('?action=feed&token='+encodeURIComponent(token), {cache:'no-store'});
    const d = await r.json();
    el('live').textContent = 'Last poll: ' + new Date().toLocaleTimeString();
    const phase = String((d.build||{}).phase||'BUILDING');
    const b = el('buildPhase'); b.textContent = phase; b.className = 'big ' + ((phase==='FAILED')?'err':(phase==='DONE'?'':'warn'));
    el('buildTail').textContent = (d.build||{}).log_tail || '(no log tail)';

    if (d.iso && d.iso.exists) {
      el('isoSize').textContent = bytes(d.iso.size);
      const dt = new Date((d.iso.mtime||0)*1000).toLocaleString();
      el('isoMeta').innerHTML = esc(d.iso.name) + ' ? ' + esc(dt) + (d.iso.asc ? ' ? GPG signed' : ' ? unsigned');
    }
    if (d.ga) el('gaHooks').textContent = (d.ga.gaFrozenIsoHookCount||0) + ' / ' + (d.ga.gaPlannedHookCount||150);
    if (typeof d.hold !== 'undefined') {
      const h = el('holdState'); h.textContent = d.hold ? 'ON' : 'OFF'; h.className = 'big ' + (d.hold ? 'warn' : '');
    }

    const rows = Array.isArray(d.rows) ? d.rows : [];
    const html = rows.length ? rows.map(r => `<tr>
      <td class="muted">${esc(r.ts||'')}</td>
      <td>${evPill(r.event||'')}</td>
      <td><strong>${esc(r.partner||'-')}</strong><br><code>${esc(r.token||'-')}</code></td>
      <td><code>${esc(r.ip||'-')}</code></td>
      <td class="muted" style="max-width:320px;word-break:break-all">${esc((r.ua||'-').slice(0,140))}</td>
      <td class="muted">${r.iso?('<span>iso '+esc(r.iso)+'</span><br>'):''}${r.bytes?('<span>bytes '+esc(bytes(r.bytes))+'</span><br>'):''}${r.ref&&r.ref!=='-'?('<span>ref '+esc(r.ref)+'</span>'):''}</td>
    </tr>`).join('') : '<tr><td colspan="6" class="muted">No rows yet.</td></tr>';
    el('feedBody').innerHTML = html;
  } catch(e) {
    el('live').textContent = 'Connection lost; retrying...';
  }
}
el('refresh').addEventListener('click',()=>{void poll()});
void poll();
setInterval(poll, 5000);
</script>
</body>
</html>
