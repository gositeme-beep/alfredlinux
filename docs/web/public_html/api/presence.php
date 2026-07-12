<?php
/**
 * Live visitor presence tracker — Redis-backed
 * Each browser pings on an interval with a stable per-browser ID (localStorage).
 * TTL — if no ping before expiry, that browser is dropped from the count.
 * Count = distinct live keys (one browser ≈ one key; not “unique humans” behind NAT).
 * Returns: { count: int|null, you: string } — count null only if Redis is unavailable.
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://alfredlinux.com');
header('Cache-Control: no-store');

$page = preg_replace('/[^a-z0-9_-]/', '', $_GET['page'] ?? 'download');
$sid  = preg_replace('/[^a-zA-Z0-9]/', '', $_GET['sid'] ?? '');

// Connect to Redis (local, no auth)
try {
    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379, 1.0);
} catch (Exception $e) {
    echo json_encode(['count' => null, 'you' => $sid, 'error' => 'redis']);
    exit;
}

$prefix = "alfredlinux:presence:{$page}:";
$ttl    = 120; // seconds — keep > client ping interval

if ($sid) {
    // Refresh this visitor's TTL
    $redis->setex($prefix . $sid, $ttl, '1');
}

// Count live keys (SCAN avoids blocking Redis on KEYS during traffic spikes)
$match = $prefix . '*';
$count = 0;
$it    = null;
do {
    $batch = $redis->scan($it, $match, 256);
    if (is_array($batch) && $batch !== []) {
        $count += count($batch);
    }
} while ($it != 0);

echo json_encode(['count' => $count, 'you' => $sid]);
