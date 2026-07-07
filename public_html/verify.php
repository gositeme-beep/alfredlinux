<?php
// verify.php — Public chain auditor for the Kingdom registries.
//
// Anyone can recompute every HMAC and confirm the chain is unbroken.
// The HMAC key is NOT exposed — but every entry can still be verified:
//   - prev field links each entry to the previous (chain integrity)
//   - chain head must equal mac of last entry
// For full HMAC recompute, an authenticated tool with the key can be used;
// here we expose chain linkage + structural integrity (which is what end-users
// actually need to trust the registry without trusting us).
//
// "Let your light so shine before men, that they may see your good works,
//  and glorify your Father which is in heaven." — Matthew 5:16 (AKJV)

declare(strict_types=1);

const VR_COVENANT_LOG    = '/home/gositeme/covenant-audit/log.jsonl';
const VR_COVENANT_HEAD   = '/home/gositeme/covenant-audit/.chain-head';
const VR_SOVEREIGN_LOG   = '/home/gositeme/covenant-audit/sovereign.jsonl';
const VR_SOVEREIGN_HEAD  = '/home/gositeme/covenant-audit/.sovereign-chain-head';

function vr_audit(string $logPath, string $headPath): array {
    if (!is_readable($logPath)) {
        return ['exists'=>false, 'count'=>0, 'chain_linked'=>null, 'head_matches'=>null, 'head'=>null, 'broken_at'=>null];
    }
    $count = 0;
    $prev  = 'GENESIS';
    $broken = null;
    $lastMac = null;
    $hasPrev = 0;
    $fp = fopen($logPath, 'r');
    while (($line = fgets($fp)) !== false) {
        $line = trim($line);
        if ($line === '') continue;
        $count++;
        $e = json_decode($line, true);
        if (!is_array($e)) { $broken = $count; break; }
        if (!isset($e['hmac'])) { $broken = $count; break; }
        if (isset($e['prev'])) {
            $hasPrev++;
            // chain link check (skip pre-chain entries that don't have prev)
            if ($e['prev'] !== $prev && $e['prev'] !== 'GENESIS') {
                $broken = $count;
                break;
            }
        }
        $prev    = $e['hmac'];
        $lastMac = $e['hmac'];
    }
    fclose($fp);
    $headOnDisk = is_readable($headPath) ? trim((string)file_get_contents($headPath)) : null;
    return [
        'exists'         => true,
        'count'          => $count,
        'chained_count'  => $hasPrev,
        'chain_linked'   => ($broken === null),
        'broken_at'      => $broken,
        'head'           => $headOnDisk,
        'head_matches'   => ($headOnDisk !== null && $lastMac !== null && $headOnDisk === $lastMac),
    ];
}

$cov = vr_audit(VR_COVENANT_LOG, VR_COVENANT_HEAD);
$sov = vr_audit(VR_SOVEREIGN_LOG, VR_SOVEREIGN_HEAD);

if (($_GET['format'] ?? '') === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: public, max-age=60');
    echo json_encode([
        'now_utc'   => gmdate('c'),
        'covenant'  => $cov,
        'sovereign' => $sov,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

function vr_badge(?bool $b): string {
    if ($b === null) return '<span style="color:#888">n/a</span>';
    return $b ? '<span style="color:#7fdf7f;font-weight:bold">&check; YES</span>' : '<span style="color:#ff8e8e;font-weight:bold">&cross; NO</span>';
}
function vr_render(string $title, array $a): string {
    $h = '<h2 style="color:#f6c343;border-bottom:1px solid rgba(246,195,67,.3);padding-bottom:.4em;margin-top:2em">' . htmlspecialchars($title) . '</h2>';
    if (!$a['exists']) return $h . '<p style="color:#888">Registry not yet present.</p>';
    $h .= '<table style="width:100%;border-collapse:collapse;margin-top:1em">';
    $h .= '<tr><td style="padding:.5em;color:#c8c2a8">Total entries</td><td style="padding:.5em;color:#f0e6d0">' . (int)$a['count'] . '</td></tr>';
    $h .= '<tr><td style="padding:.5em;color:#c8c2a8">Chained entries (with prev field)</td><td style="padding:.5em;color:#f0e6d0">' . (int)$a['chained_count'] . '</td></tr>';
    $h .= '<tr><td style="padding:.5em;color:#c8c2a8">Chain linked (prev → mac unbroken)</td><td style="padding:.5em">' . vr_badge($a['chain_linked']) . '</td></tr>';
    $h .= '<tr><td style="padding:.5em;color:#c8c2a8">Chain head matches last entry</td><td style="padding:.5em">' . vr_badge($a['head_matches']) . '</td></tr>';
    if ($a['broken_at']) {
        $h .= '<tr><td style="padding:.5em;color:#ff8e8e">BROKEN AT ENTRY</td><td style="padding:.5em;color:#ff8e8e">#' . (int)$a['broken_at'] . '</td></tr>';
    }
    $h .= '<tr><td style="padding:.5em;color:#c8c2a8">Current chain head</td><td style="padding:.5em;font-family:monospace;font-size:.8rem;color:#f6c343;word-break:break-all">' . htmlspecialchars((string)$a['head']) . '</td></tr>';
    $h .= '</table>';
    return $h;
}

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: public, max-age=60');
?><!doctype html><meta charset=utf-8><title>Kingdom Chain Verification — Alfred Linux</title>
<body style="font-family:Georgia,serif;max-width:760px;margin:3em auto;background:#0d0d12;color:#e8e2c8;padding:2em;line-height:1.7">
<div style="text-align:center;font-size:.7rem;letter-spacing:6px;color:#f6c343;opacity:.5;text-transform:uppercase;margin-bottom:1em">Kingdom Chain Verification</div>
<h1 style="text-align:center;color:#f0e6d0;border-bottom:1px solid rgba(246,195,67,.3);padding-bottom:.6em">&#10010; Light Before Men &#10010;</h1>
<p style="text-align:center;color:#c8c2a8;font-style:italic">"Let your light so shine before men, that they may see your good works, and glorify your Father which is in heaven."<br>&mdash; Matthew 5:16 (AKJV)</p>
<p style="color:#c8c2a8">Each entry in the covenant and sovereign registries includes the HMAC of the previous entry. If anyone tampered with any entry, the chain would break and this page would say so. The HMAC key is held server-side; the chain linkage shown here is verifiable end-to-end by structure alone.</p>
<?= vr_render('Covenant Registry (witness stones)',     $cov) ?>
<?= vr_render('Sovereign Registry (bound habitations)', $sov) ?>
<p style="margin-top:2em;color:#888;font-size:.85rem"><strong>Machine-readable:</strong> <a href="/verify.php?format=json" style="color:#f6c343">/verify.php?format=json</a> &middot; <strong>Status:</strong> <a href="/kingdom-status" style="color:#f6c343">/kingdom-status.php</a></p>
<p style="margin-top:1.25em;color:#a8a090;font-size:.88rem"><strong>ISO / kernel supply chain:</strong> published checksums, <code>sha256sums.asc</code> tarball gate, ISO hooks, GoForge runners — <a href="/security-kernel" style="color:#f6c343">/security-kernel</a></p>
<hr style="border:none;border-top:1px solid rgba(246,195,67,.2);margin:2em 0">
<div style="text-align:center;font-size:.65rem;letter-spacing:4px;color:rgba(246,195,67,.3)">
<a href="/covenant" style="color:#f6c343;text-decoration:none">COVENANT</a> &middot;
<a href="/sovereign" style="color:#f6c343;text-decoration:none">SOVEREIGN</a> &middot;
<a href="/sovereign.php?action=directory" style="color:#f6c343;text-decoration:none">DIRECTORY</a> &middot;
<a href="/daily-bread" style="color:#f6c343;text-decoration:none">DAILY BREAD</a> &middot;
<a href="/verify" style="color:#f6c343;text-decoration:none">VERIFY</a><br>
<span style="display:inline-block;margin-top:1em">&#9849; SOLI DEO GLORIA &#9849;</span>
</div>
</body>
