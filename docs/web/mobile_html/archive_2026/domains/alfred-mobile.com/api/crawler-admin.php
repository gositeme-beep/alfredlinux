<?php
/**
 * Alfred Crawler Management API
 * ──────────────────────────────
 * Admin-only API for managing the web crawler.
 * Provides status, stats, ability to add URLs, and trigger crawls.
 *
 * Endpoints:
 *   GET  ?action=stats              → Crawler statistics
 *   GET  ?action=queue&status=...   → View queue entries
 *   GET  ?action=domains            → Domain stats
 *   POST ?action=add_url&url=...    → Add URL to crawl queue
 *   POST ?action=add_urls           → Bulk add URLs (JSON body)
 *   GET  ?action=recent             → Recently crawled pages
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

// Auth check — admin only
session_start();
if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Admin access required']);
require_once dirname(__DIR__) . '/includes/api-security.php';
    exit;
}

function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$db = getDB();
if (!$db) respond(['error' => 'Database unavailable'], 500);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'stats':
        $stats = [];
        $stats['queue_total']   = (int) $db->query("SELECT COUNT(*) FROM crawler_queue")->fetchColumn();
        $stats['queue_pending'] = (int) $db->query("SELECT COUNT(*) FROM crawler_queue WHERE status='pending'")->fetchColumn();
        $stats['queue_done']    = (int) $db->query("SELECT COUNT(*) FROM crawler_queue WHERE status='done'")->fetchColumn();
        $stats['queue_failed']  = (int) $db->query("SELECT COUNT(*) FROM crawler_queue WHERE status='failed'")->fetchColumn();
        $stats['queue_blocked'] = (int) $db->query("SELECT COUNT(*) FROM crawler_queue WHERE status='blocked'")->fetchColumn();
        $stats['pages_indexed'] = (int) $db->query("SELECT COUNT(*) FROM crawler_pages")->fetchColumn();
        $stats['domains_known'] = (int) $db->query("SELECT COUNT(*) FROM crawler_domains")->fetchColumn();
        $stats['avg_quality']   = round((float) $db->query("SELECT AVG(quality_score) FROM crawler_pages")->fetchColumn(), 3);
        $stats['total_content_mb'] = round((float) $db->query("SELECT SUM(content_length) / 1048576 FROM crawler_pages")->fetchColumn(), 2);

        // Last crawl time
        $lastCrawl = $db->query("SELECT MAX(crawled_at) FROM crawler_pages")->fetchColumn();
        $stats['last_crawl'] = $lastCrawl;

        respond($stats);

    case 'queue':
        $status = $_GET['status'] ?? 'pending';
        $limit = min(100, max(1, (int) ($_GET['limit'] ?? 20)));
        $offset = max(0, (int) ($_GET['offset'] ?? 0));

        $allowed = ['pending', 'crawling', 'done', 'failed', 'blocked'];
        if (!in_array($status, $allowed)) respond(['error' => 'Invalid status'], 400);

        $stmt = $db->prepare("SELECT id, url, domain, depth, priority, status, attempts, last_crawled
            FROM crawler_queue WHERE status = ? ORDER BY priority DESC, id ASC LIMIT ? OFFSET ?");
        dbExecute($stmt, [$status, $limit, $offset]);
        respond(['queue' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'status' => $status]);

    case 'domains':
        $limit = min(100, max(1, (int) ($_GET['limit'] ?? 20)));
        $stmt = $db->prepare("SELECT domain, pages_crawled, reputation, crawl_delay, last_crawled, blocked
            FROM crawler_domains ORDER BY pages_crawled DESC LIMIT ?");
        dbExecute($stmt, [$limit]);
        respond(['domains' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

    case 'recent':
        $limit = min(50, max(1, (int) ($_GET['limit'] ?? 20)));
        $stmt = $db->prepare("SELECT url, domain, title, quality_score, http_status, crawled_at
            FROM crawler_pages ORDER BY crawled_at DESC LIMIT ?");
        dbExecute($stmt, [$limit]);
        respond(['pages' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);

    case 'add_url':
        $url = trim($_GET['url'] ?? $_POST['url'] ?? '');
        if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
            respond(['error' => 'Valid URL required'], 400);
        }
        if (!preg_match('#^https?://#i', $url)) {
            respond(['error' => 'Only HTTP/HTTPS URLs allowed'], 400);
        }
        $domain = strtolower(preg_replace('/^www\./', '', parse_url($url, PHP_URL_HOST) ?? ''));
        $hash = hash('sha256', strtolower($url));
        $priority = (int) ($_POST['priority'] ?? 70);

        $stmt = $db->prepare("INSERT IGNORE INTO crawler_queue (url, url_hash, domain, depth, priority) VALUES (?, ?, ?, 0, ?)");
        $stmt->execute([$url, $hash, $domain, $priority]);
        respond(['added' => $stmt->rowCount() > 0, 'url' => $url]);

    case 'add_urls':
        $input = json_decode(file_get_contents('php://input'), true);
        $urls = $input['urls'] ?? [];
        if (!is_array($urls) || empty($urls)) respond(['error' => 'URLs array required'], 400);

        $added = 0;
        $stmt = $db->prepare("INSERT IGNORE INTO crawler_queue (url, url_hash, domain, depth, priority) VALUES (?, ?, ?, 0, 70)");
        foreach (array_slice($urls, 0, 500) as $url) {
            $url = trim($url);
            if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) continue;
            if (!preg_match('#^https?://#i', $url)) continue;
            $domain = strtolower(preg_replace('/^www\./', '', parse_url($url, PHP_URL_HOST) ?? ''));
            $hash = hash('sha256', strtolower($url));
            $stmt->execute([$url, $hash, $domain]);
            if ($stmt->rowCount() > 0) $added++;
        }
        respond(['added' => $added, 'total_submitted' => count($urls)]);

    default:
        respond(['error' => 'Unknown action. Use: stats, queue, domains, recent, add_url, add_urls'], 400);
}
