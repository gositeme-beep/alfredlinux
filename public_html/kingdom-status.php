<?php
// kingdom-status.json — single aggregator endpoint for OS clients, MOTDs,
// dashboards, federation peers. One source of truth.
//
// Public read-only. Cached 5 min.
//
// "And he said unto them, Go ye into all the world, and preach the gospel
//  to every creature." — Mark 16:15 (AKJV)

declare(strict_types=1);
require_once __DIR__ . '/includes/sabbath.php';
require_once __DIR__ . '/includes/daily-bread.inc.php';

const KS_COVENANT_LOG    = '/home/gositeme/covenant-audit/log.jsonl';
const KS_COVENANT_HEAD   = '/home/gositeme/covenant-audit/.chain-head';
const KS_SOVEREIGN_LOG   = '/home/gositeme/covenant-audit/sovereign.jsonl';
const KS_SOVEREIGN_HEAD  = '/home/gositeme/covenant-audit/.sovereign-chain-head';

function ks_count_lines(string $f): int {
    if (!is_readable($f)) return 0;
    $n = 0;
    $fp = fopen($f, 'r');
    while (!feof($fp)) { if (fgets($fp) !== false) $n++; }
    fclose($fp);
    return $n;
}

function ks_head(string $f): ?string {
    return is_readable($f) ? trim((string)file_get_contents($f)) : null;
}

$verse   = daily_bread_pick();
$sabbath = sabbath_state();

$status = [
    'service'    => 'alfredlinux.com',
    'edition'    => 'Kingdom of God Edition',
    'version'    => '7.77',
    'now_utc'    => gmdate('c'),
    'sabbath'    => [
        'active' => (bool)($sabbath['active'] ?? false),
        'erev'   => (bool)($sabbath['erev'] ?? false),
        'end_ts' => $sabbath['end_ts'] ?? null,
        'source' => $sabbath['source'] ?? 'unknown',
    ],
    'daily_bread' => $verse,
    'covenant'    => [
        'count'      => ks_count_lines(KS_COVENANT_LOG),
        'chain_head' => ks_head(KS_COVENANT_HEAD),
    ],
    'sovereign'   => [
        'count'      => ks_count_lines(KS_SOVEREIGN_LOG),
        'chain_head' => ks_head(KS_SOVEREIGN_HEAD),
    ],
    'pillars'     => [
        'covenant'    => 'https://alfredlinux.com/covenant.php',
        'sovereign'   => 'https://alfredlinux.com/sovereign.php',
        'daily_bread' => 'https://alfredlinux.com/daily-bread.php',
        'directory'   => 'https://alfredlinux.com/sovereign.php?action=directory',
        'verify'      => 'https://alfredlinux.com/verify.php',
        'status'      => 'https://alfredlinux.com/kingdom-status.php',
    ],
    'verse_seal'  => 'Mark 16:15 — "Go ye into all the world, and preach the gospel to every creature."',
];

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');
header('Access-Control-Allow-Origin: *');
echo json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
