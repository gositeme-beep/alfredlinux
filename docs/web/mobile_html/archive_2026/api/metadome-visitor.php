<?php
/**
 * MetaDome Visitor Tracking API
 * 
 * Lightweight, privacy-first visitor counter.
 * - Hashes IPs (never stores raw)
 * - Tracks page views + unique visitors per day
 * - Returns live counter data for the visitor widget
 * 
 * Endpoints:
 *   POST /api/metadome-visitor.php?action=track   — Record a visit
 *   GET  /api/metadome-visitor.php?action=stats    — Get live counter
 */

require_once __DIR__ . '/../includes/db-config.inc.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://meta-dome.com');
header('Access-Control-Allow-Methods: GET, POST');

$db = getSharedDB();
$action = $_GET['action'] ?? 'stats';

// ── Track a visit ──
if ($action === 'track' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $ipHash = hash('sha256', $ip . date('Y-m-d')); // Day-scoped hash for uniqueness

    $domain = 'meta-dome.com';
    $page = substr($_POST['page'] ?? '/', 0, 255);
    $referer = isset($_SERVER['HTTP_REFERER']) ? substr($_SERVER['HTTP_REFERER'], 0, 500) : null;
    $ua = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 500) : null;

    // Check if this IP already visited today
    $check = $db->prepare("SELECT COUNT(*) FROM metadome_visitors WHERE ip_hash = ? AND domain = ? AND DATE(visited_at) = CURDATE()");
    $check->execute([$ipHash, $domain]);
    $isUnique = $check->fetchColumn() == 0 ? 1 : 0;

    // Insert visit record
    $stmt = $db->prepare("INSERT INTO metadome_visitors (ip_hash, domain, page, referer, user_agent, is_unique_today) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$ipHash, $domain, $page, $referer, $ua, $isUnique]);

    // Upsert daily stats
    $db->prepare("INSERT INTO metadome_visitor_stats (stat_date, domain, total_hits, unique_visitors)
        VALUES (CURDATE(), ?, 1, ?)
        ON DUPLICATE KEY UPDATE total_hits = total_hits + 1, unique_visitors = unique_visitors + ?"
    )->execute([$domain, $isUnique, $isUnique]);

    // Track specific page clicks
    if (strpos($page, 'passport') !== false) {
        $db->prepare("UPDATE metadome_visitor_stats SET passport_clicks = passport_clicks + 1 WHERE stat_date = CURDATE() AND domain = ?")->execute([$domain]);
    } elseif (strpos($page, 'map') !== false) {
        $db->prepare("UPDATE metadome_visitor_stats SET map_clicks = map_clicks + 1 WHERE stat_date = CURDATE() AND domain = ?")->execute([$domain]);
    }

    echo json_encode(['ok' => true, 'unique' => (bool)$isUnique]);
    exit;
}

// ── Get live stats ──
if ($action === 'stats') {
    // Today's stats
    $today = $db->prepare("SELECT total_hits, unique_visitors, passport_clicks, map_clicks FROM metadome_visitor_stats WHERE stat_date = CURDATE() AND domain = 'meta-dome.com'");
    $today->execute();
    $todayStats = $today->fetch(PDO::FETCH_ASSOC) ?: ['total_hits' => 0, 'unique_visitors' => 0, 'passport_clicks' => 0, 'map_clicks' => 0];

    // All-time totals
    $allTime = $db->query("SELECT COALESCE(SUM(total_hits),0) as total_hits, COALESCE(SUM(unique_visitors),0) as unique_visitors FROM metadome_visitor_stats WHERE domain = 'meta-dome.com'")->fetch(PDO::FETCH_ASSOC);

    // Currently online (visits in last 5 minutes)
    $online = $db->query("SELECT COUNT(DISTINCT ip_hash) FROM metadome_visitors WHERE domain = 'meta-dome.com' AND visited_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)")->fetchColumn();

    // Historical chart (last 30 days)
    $chart = $db->query("SELECT stat_date, total_hits, unique_visitors FROM metadome_visitor_stats WHERE domain = 'meta-dome.com' AND stat_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) ORDER BY stat_date ASC")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'today'    => $todayStats,
        'allTime'  => $allTime,
        'online'   => (int)$online,
        'chart'    => $chart,
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid action']);
