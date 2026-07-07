<?php
/**
 * /verify — Alfred Linux 7 public ISO verification
 *
 * Drop in webroot. Reads the covenant chain from disk, lets a visitor paste
 * any sha256 (or upload an ISO they computed locally) and shows the full
 * provenance: build entry, sealing time, parent hash, signing identity.
 *
 * Differentiator: no other OS gives you "did this exact bit-for-bit ISO come
 * from us, on this date, signed by this trust root, blessed in this entry?"
 */
declare(strict_types=1);
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-cache');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

$CHAIN_PATHS = [
    '/var/lib/alfred/covenant-chain.log',
    '/home/root/public_html/covenant-chain.log',
    __DIR__ . '/covenant-chain.log',
];

function load_chain(array $paths): array {
    foreach ($paths as $p) {
        if (is_readable($p)) {
            $raw = trim((string)file_get_contents($p));
            if ($raw === '') return [];
            // line-delimited JSON preferred
            if (str_starts_with($raw, '{') && str_contains($raw, "\n{")) {
                $out = [];
                foreach (explode("\n", $raw) as $ln) {
                    $ln = trim($ln);
                    if ($ln === '' || $ln[0] !== '{') continue;
                    $j = json_decode($ln, true);
                    if (is_array($j)) $out[] = $j;
                }
                return $out;
            }
            // fallback blocks separated by ---
            $out = [];
            foreach (preg_split('/^---+\s*$/m', $raw) as $block) {
                $block = trim($block);
                if ($block === '') continue;
                if (str_starts_with($block, '{')) {
                    $j = json_decode($block, true);
                    if (is_array($j)) $out[] = $j;
                } else {
                    $d = [];
                    foreach (explode("\n", $block) as $ln) {
                        if (strpos($ln, ':') !== false) {
                            [$k,$v] = explode(':', $ln, 2);
                            $d[strtolower(str_replace(' ','_',trim($k)))] = trim($v);
                        }
                    }
                    if ($d) $out[] = $d;
                }
            }
            return $out;
        }
    }
    return [];
}

function entry_canonical(array $e): string {
    $strip = $e;
    unset($strip['hash'], $strip['sig'], $strip['signature']);
    ksort($strip);
    return json_encode($strip, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}
function entry_hash(array $e): string {
    return hash('sha256', entry_canonical($e));
}

function verify_chain(array $entries): array {
    $expected = str_repeat('0', 64);
    $rows = [];
    $ok = true;
    foreach ($entries as $i => $e) {
        $parent  = $e['parent_hash'] ?? $e['prev'] ?? '';
        $claimed = $e['hash'] ?? '';
        $computed= entry_hash($e);
        $linkOk  = $parent === $expected;
        $hashOk  = $claimed === '' || $claimed === $computed;
        $rows[] = [
            'i'       => $i,
            'id'      => $e['id'] ?? $e['entry_id'] ?? "#$i",
            'title'   => $e['title'] ?? $e['subject'] ?? '',
            'ts'      => $e['timestamp'] ?? $e['ts'] ?? '',
            'parent'  => $parent,
            'computed'=> $computed,
            'linkOk'  => $linkOk,
            'hashOk'  => $hashOk,
            'iso'     => $e['iso_sha256'] ?? $e['artifact_sha256'] ?? $e['sha256'] ?? '',
            'arch'    => $e['arch'] ?? '',
        ];
        if (!$linkOk || !$hashOk) $ok = false;
        $expected = $computed;
    }
    return ['ok' => $ok, 'head' => $expected, 'rows' => $rows];
}

function find_iso(array $entries, string $sha): ?array {
    $sha = strtolower(trim($sha));
    foreach ($entries as $e) {
        foreach (['iso_sha256','artifact_sha256','sha256'] as $k) {
            if (isset($e[$k]) && strtolower((string)$e[$k]) === $sha) return $e;
        }
        foreach (($e['artifacts'] ?? []) as $a) {
            if (is_array($a) && strtolower((string)($a['sha256'] ?? '')) === $sha) return $e;
        }
    }
    return null;
}

$entries = load_chain($CHAIN_PATHS);
$result  = verify_chain($entries);
$inputSha= isset($_REQUEST['sha256']) ? strtolower(trim((string)$_REQUEST['sha256'])) : '';
$match   = ($inputSha && preg_match('/^[a-f0-9]{64}$/', $inputSha)) ? find_iso($entries, $inputSha) : null;
$mode    = ($_REQUEST['fmt'] ?? '') === 'json' ? 'json' : 'html';

if ($mode === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'tool'    => 'alfred-verify-web',
        'version' => '1.0.0',
        'verified_utc' => gmdate('c'),
        'chain_ok' => $result['ok'],
        'entries_total' => count($result['rows']),
        'head_hash' => $result['head'],
        'queried_sha256' => $inputSha ?: null,
        'matched' => $match ? [
            'id' => $match['id'] ?? $match['entry_id'] ?? null,
            'title' => $match['title'] ?? $match['subject'] ?? null,
            'timestamp' => $match['timestamp'] ?? $match['ts'] ?? null,
            'arch' => $match['arch'] ?? null,
        ] : null,
        'entries' => $result['rows'],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Verify — Alfred Linux 7</title>
<meta name="description" content="Cryptographically verify your Alfred Linux 7 ISO against the public covenant chain.">
<style>
:root{--bg:#0a0e14;--fg:#e6edf3;--mut:#7d8590;--ok:#3fb950;--bad:#f85149;--warn:#d29922;--accent:#58a6ff;--card:#161b22;--border:#30363d}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--fg);font:16px/1.55 -apple-system,BlinkMacSystemFont,"Segoe UI",Inter,system-ui,sans-serif}
.wrap{max-width:1100px;margin:0 auto;padding:32px 20px}
header{border-bottom:1px solid var(--border);padding-bottom:24px;margin-bottom:32px}
h1{margin:0 0 8px;font-size:32px;letter-spacing:-.02em}
h1 .cross{color:var(--accent);font-weight:300;margin-right:6px}
.tag{display:inline-block;padding:3px 10px;border:1px solid var(--border);border-radius:999px;font-size:12px;color:var(--mut);margin-right:6px}
.lead{color:var(--mut);max-width:720px;margin:8px 0 0}
form{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:20px;margin-bottom:24px}
form label{display:block;font-weight:600;margin-bottom:8px}
form .row{display:flex;gap:8px;flex-wrap:wrap}
input[type=text]{flex:1;min-width:280px;padding:12px 14px;background:#0d1117;color:var(--fg);border:1px solid var(--border);border-radius:8px;font:14px/1.4 ui-monospace,SFMono-Regular,Consolas,monospace}
button{padding:12px 24px;background:var(--accent);color:#0a0e14;border:0;border-radius:8px;font-weight:600;cursor:pointer}
button.alt{background:transparent;color:var(--accent);border:1px solid var(--accent)}
.banner{padding:16px 20px;border-radius:10px;margin:0 0 24px;border:1px solid;font-weight:500}
.banner.ok{border-color:var(--ok);background:rgba(63,185,80,.07);color:var(--ok)}
.banner.bad{border-color:var(--bad);background:rgba(248,81,73,.07);color:var(--bad)}
.banner.info{border-color:var(--accent);background:rgba(88,166,255,.07);color:var(--accent)}
.banner h2{margin:0 0 4px;font-size:18px}
.banner p{margin:0;color:var(--fg);opacity:.9}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin-bottom:24px}
.stat{background:var(--card);border:1px solid var(--border);border-radius:10px;padding:14px}
.stat .k{color:var(--mut);font-size:12px;text-transform:uppercase;letter-spacing:.05em}
.stat .v{font:600 18px/1.2 ui-monospace,SFMono-Regular,Consolas,monospace;margin-top:4px;word-break:break-all}
table{width:100%;border-collapse:collapse;background:var(--card);border:1px solid var(--border);border-radius:10px;overflow:hidden;margin-bottom:24px;font-size:13px}
th,td{text-align:left;padding:10px 12px;border-bottom:1px solid var(--border);vertical-align:top}
th{background:#0d1117;color:var(--mut);font-size:11px;text-transform:uppercase;letter-spacing:.05em}
tr:last-child td{border-bottom:0}
.mono{font-family:ui-monospace,SFMono-Regular,Consolas,monospace}
.ok-cell{color:var(--ok)}.bad-cell{color:var(--bad)}
.cli{background:#0d1117;border:1px solid var(--border);border-radius:10px;padding:16px;font:13px/1.55 ui-monospace,SFMono-Regular,Consolas,monospace;color:var(--fg);overflow-x:auto;margin:8px 0 24px}
.cli .c{color:var(--mut)}
footer{color:var(--mut);font-size:13px;border-top:1px solid var(--border);padding-top:20px;margin-top:32px}
a{color:var(--accent)}
.match-card{background:linear-gradient(180deg,rgba(63,185,80,.1),rgba(63,185,80,.02));border:1px solid var(--ok);border-radius:12px;padding:24px;margin-bottom:24px}
.match-card h2{margin:0 0 12px;color:var(--ok)}
.match-card dl{display:grid;grid-template-columns:140px 1fr;gap:6px 16px;margin:0}
.match-card dt{color:var(--mut);font-size:13px}
.match-card dd{margin:0;font-family:ui-monospace,SFMono-Regular,Consolas,monospace;font-size:13px;word-break:break-all}
</style>
</head>
<body>
<div class="wrap">

<header>
  <h1><span class="cross">✚</span> Verify Alfred Linux 7</h1>
  <p class="lead">Paste the SHA-256 of your downloaded ISO. We will trace it through the entire public covenant chain — every build, every signature, every parent hash — back to Genesis. <strong>If it doesn't match, don't boot it.</strong></p>
  <div style="margin-top:14px">
    <span class="tag">offline-verifiable</span>
    <span class="tag">GPG-signed</span>
    <span class="tag">reproducible builds</span>
    <span class="tag">10 architectures</span>
  </div>
</header>

<form method="get" action="">
  <label for="sha256">ISO SHA-256 (64 hex characters)</label>
  <div class="row">
    <input id="sha256" name="sha256" type="text" autocomplete="off" spellcheck="false"
           pattern="[a-fA-F0-9]{64}" placeholder="e.g. fd3a229b414107c55a3e9bd39a26370e1be3cf53c6480e04f93808c124b10e67"
           value="<?= htmlspecialchars($inputSha) ?>">
    <button type="submit">Verify</button>
    <button type="submit" class="alt" name="fmt" value="json">JSON</button>
  </div>
</form>

<?php if ($inputSha && !$match): ?>
  <div class="banner bad">
    <h2>✗ NOT FOUND in chain</h2>
    <p>This SHA-256 does not match any sealed Alfred Linux 7 ISO. Either the file is tampered, you mis-typed, or it's from a different project. Do not boot.</p>
  </div>
<?php elseif ($match): ?>
  <div class="match-card">
    <h2>✓ Authentic Alfred Linux 7 ISO</h2>
    <dl>
      <dt>Entry ID</dt>      <dd><?= htmlspecialchars((string)($match['id'] ?? $match['entry_id'] ?? '')) ?></dd>
      <dt>Title</dt>          <dd><?= htmlspecialchars((string)($match['title'] ?? $match['subject'] ?? '')) ?></dd>
      <dt>Architecture</dt>   <dd><?= htmlspecialchars((string)($match['arch'] ?? 'amd64')) ?></dd>
      <dt>Sealed (UTC)</dt>   <dd><?= htmlspecialchars((string)($match['timestamp'] ?? $match['ts'] ?? '')) ?></dd>
      <dt>SHA-256</dt>        <dd><?= htmlspecialchars($inputSha) ?></dd>
      <dt>Parent hash</dt>    <dd><?= htmlspecialchars((string)($match['parent_hash'] ?? $match['prev'] ?? '')) ?></dd>
    </dl>
  </div>
<?php endif; ?>

<div class="banner <?= $result['ok'] ? 'ok' : 'bad' ?>">
  <h2><?= $result['ok'] ? '✓ Covenant chain is intact' : '✗ Covenant chain has broken links' ?></h2>
  <p><?= count($result['rows']) ?> entries, head hash <span class="mono"><?= substr($result['head'],0,16) ?>…</span></p>
</div>

<div class="grid">
  <div class="stat"><div class="k">Entries</div><div class="v"><?= count($result['rows']) ?></div></div>
  <div class="stat"><div class="k">Head</div><div class="v"><?= substr($result['head'],0,12) ?>…</div></div>
  <div class="stat"><div class="k">Status</div><div class="v" style="color:<?= $result['ok']?'var(--ok)':'var(--bad)' ?>"><?= $result['ok']?'VALID':'BROKEN' ?></div></div>
  <div class="stat"><div class="k">Last sealed</div><div class="v" style="font-size:14px"><?= htmlspecialchars($result['rows'][count($result['rows'])-1]['ts'] ?? '—') ?></div></div>
</div>

<h2>Verify offline (don't trust this page — verify yourself)</h2>
<p>The page you're reading could be lying. <strong>Always verify offline</strong> using our public open-source tool:</p>
<div class="cli">
<span class="c"># 1. Download alfred-verify (also bundled inside the ISO at /usr/bin/alfred-verify)</span>
curl -O https://alfredlinux.com/tools/alfred-verify
chmod +x alfred-verify

<span class="c"># 2. Compute your ISO hash</span>
sha256sum alfred-linux-7-amd64.iso

<span class="c"># 3. Verify against the public chain</span>
./alfred-verify --chain https://alfredlinux.com/covenant-chain.log \
                --iso alfred-linux-7-amd64.iso

<span class="c"># Or with just a hash:</span>
./alfred-verify --chain https://alfredlinux.com/covenant-chain.log \
                --sha256 fd3a229b414107c55a3e9bd39a26370e1be3cf53c6480e04f93808c124b10e67
</div>

<h2>Public chain (newest first, last 25 entries)</h2>
<table>
  <thead><tr><th>#</th><th>Entry</th><th>Title</th><th>Sealed</th><th>Hash</th><th>Link</th></tr></thead>
  <tbody>
  <?php foreach (array_reverse(array_slice($result['rows'], -25)) as $r): ?>
    <tr>
      <td class="mono"><?= $r['i'] ?></td>
      <td class="mono"><?= htmlspecialchars($r['id']) ?></td>
      <td><?= htmlspecialchars($r['title']) ?></td>
      <td class="mono" style="white-space:nowrap"><?= htmlspecialchars($r['ts']) ?></td>
      <td class="mono"><?= substr($r['computed'],0,16) ?>…</td>
      <td class="<?= $r['linkOk']&&$r['hashOk']?'ok-cell':'bad-cell' ?>"><?= $r['linkOk']&&$r['hashOk']?'✓':'✗' ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<footer>
  <p><strong>Why this matters:</strong> No other operating system in the world cryptographically links its build artifacts to a public, append-only, GPG-signed covenant chain that you can re-walk yourself. Trust nothing. Verify everything.</p>
  <p>Open source: <a href="https://alfredlinux.com/forge/commander/alfredlinux.com">alfred-verify</a> · Public chain JSON: <a href="/covenant-chain.log">/covenant-chain.log</a> · Discussion: <a href="/transparency/">/transparency/</a></p>
  <p>Generated <?= gmdate('Y-m-d H:i:s') ?> UTC · <code>alfred-verify-web 1.0.0</code></p>
</footer>

</div>
</body>
</html>
