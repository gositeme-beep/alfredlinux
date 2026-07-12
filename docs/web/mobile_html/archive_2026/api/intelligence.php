<?php
/**
 * Intelligence API — Regional Intelligence Dashboard Data
 * ────────────────────────────────────────────────────────
 * Serves intelligence data for the admin dashboard.
 *
 * Endpoints:
 *   GET ?action=dashboard    → Full intelligence overview
 *   GET ?action=alerts       → Active alerts
 *   GET ?action=brief        → Latest daily brief
 *   GET ?action=articles     → Recent articles (filterable)
 *   GET ?action=sources      → Intelligence sources status
 */

define('GOSITEME_API', true);
require_once dirname(__DIR__) . '/api/config.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

if (session_status() === PHP_SESSION_NONE) session_start();
$clientId = (int)($_SESSION['client_id'] ?? $_SESSION['user_id'] ?? 0);

if ($clientId !== 33) {
    http_response_code(403);
    echo json_encode(['error' => 'Admin access required']);
require_once dirname(__DIR__) . '/includes/api-security.php';
    exit;
}

$db = getDB();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'dashboard':
        $sources = $db->query("SELECT COUNT(*) as total,
            SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) as active,
            SUM(crawl_count) as total_crawls
            FROM intel_sources")->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'active' => 0, 'total_crawls' => 0];

        $articles = $db->query("SELECT COUNT(*) as total,
            SUM(CASE WHEN DATE(crawled_at) = CURDATE() THEN 1 ELSE 0 END) as today,
            SUM(CASE WHEN analyzed_at IS NOT NULL THEN 1 ELSE 0 END) as analyzed
            FROM intel_articles")->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'today' => 0, 'analyzed' => 0];

        $alerts = $db->query("SELECT * FROM intel_alerts WHERE acknowledged_at IS NULL ORDER BY
            FIELD(severity,'critical','high','medium','low'), detected_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

        $brief = $db->query("SELECT * FROM intel_daily_briefs ORDER BY brief_date DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

        $byRegion = $db->query("SELECT region, COUNT(*) as cnt FROM intel_articles
            WHERE crawled_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY region ORDER BY cnt DESC")->fetchAll(PDO::FETCH_ASSOC);

        $urgentArticles = $db->query("SELECT id, title, region, urgency, category, sentiment, crawled_at
            FROM intel_articles WHERE urgency IN ('important','urgent','critical')
            ORDER BY FIELD(urgency,'critical','urgent','important'), crawled_at DESC LIMIT 15")->fetchAll(PDO::FETCH_ASSOC);

        $sourcesByRegion = $db->query("SELECT region, COUNT(*) as cnt FROM intel_sources WHERE status='active' GROUP BY region ORDER BY cnt DESC")->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'ok',
            'sources' => $sources,
            'sources_by_region' => $sourcesByRegion,
            'articles' => $articles,
            'articles_by_region' => $byRegion,
            'active_alerts' => $alerts,
            'alert_count' => count($alerts),
            'latest_brief' => $brief,
            'urgent_articles' => $urgentArticles,
            'generated_at' => date('c'),
        ]);
        break;

    case 'alerts':
        $limit = min((int)($_GET['limit'] ?? 50), 100);
        $alerts = $db->prepare("SELECT * FROM intel_alerts ORDER BY detected_at DESC LIMIT ?");
        dbExecute($alerts, [$limit]);
        echo json_encode(['alerts' => $alerts->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'acknowledge':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        $input = json_decode(file_get_contents('php://input'), true);
        $alertId = (int)($input['alert_id'] ?? 0);
        if (!$alertId) { echo json_encode(['error' => 'alert_id required']); break; }
        $db->prepare("UPDATE intel_alerts SET acknowledged_at = NOW(), acknowledged_by = ? WHERE id = ?")
            ->execute([$userId, $alertId]);
        echo json_encode(['status' => 'acknowledged', 'alert_id' => $alertId]);
        break;

    case 'brief':
        $brief = $db->query("SELECT * FROM intel_daily_briefs ORDER BY brief_date DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['brief' => $brief ?: null]);
        break;

    case 'articles':
        $region = $_GET['region'] ?? '';
        $urgency = $_GET['urgency'] ?? '';
        $limit = min((int)($_GET['limit'] ?? 30), 100);

        $where = '1=1';
        $params = [];
        if ($region) { $where .= ' AND region = ?'; $params[] = $region; }
        if ($urgency) { $where .= ' AND urgency = ?'; $params[] = $urgency; }
        $params[] = $limit;

        $stmt = $db->prepare("SELECT id, url, title, region, category, urgency, sentiment, language, crawled_at
            FROM intel_articles WHERE $where ORDER BY crawled_at DESC LIMIT ?");
        dbExecute($stmt, $params);
        echo json_encode(['articles' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'sources':
        $sources = $db->query("SELECT id, url, region, source_type, language, priority, last_crawled, crawl_count, error_count, status
            FROM intel_sources ORDER BY priority ASC, region ASC")->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['sources' => $sources]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action', 'actions' => ['dashboard','alerts','acknowledge','brief','articles','sources']]);
}
