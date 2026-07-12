<?php
/**
 * Alfred News Feeds API — Phase 1: Autonomy Foundation
 * ─────────────────────────────────────────────────────
 * Information sovereignty: RSS, API, and scrape-based feed aggregation.
 * Alfred stays informed on tech, finance, security, and more.
 *
 * Endpoints:
 *   GET  ?action=feeds            → List all feeds
 *   POST ?action=add-feed         → Register a new feed source
 *   POST ?action=update-feed      → Update feed settings
 *   GET  ?action=items            → Get feed items (paginated, filtered)
 *   GET  ?action=unprocessed      → Items awaiting agent processing
 *   POST ?action=process          → Mark item as processed + record action
 *   GET  ?action=poll             → Poll a feed (or all due feeds) for new items
 *   GET  ?action=stats            → Feed statistics
 *   POST ?action=seed             → Seed default feeds
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Internal-Secret');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

function requireAuth() {
    if (empty($_SESSION['logged_in']) || empty($_SESSION['client_id'])) {
        jsonResponse(['error' => 'Authentication required'], 401);
    }
}

function isAdmin() {
    return !empty($_SESSION['is_admin']) || ($_SESSION['client_id'] ?? 0) === 33;
}

function isInternalCall() {
    $secret = getenv('INTERNAL_SECRET') ?: '';
    return $secret && isset($_SERVER['HTTP_X_INTERNAL_SECRET']) && hash_equals($secret, $_SERVER['HTTP_X_INTERNAL_SECRET']);
}

// ─── DB Schema ─────────────────────────────────────────────────────
function ensureFeedsSchema() {
    $db = getDB();
    if (!$db) return false;

    $db->exec("CREATE TABLE IF NOT EXISTS alfred_feeds (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        feed_name       VARCHAR(100) NOT NULL,
        feed_url        VARCHAR(500) NOT NULL,
        feed_type       ENUM('rss','api','websocket','scrape') NOT NULL,
        category        VARCHAR(50) NOT NULL,
        poll_interval   INT DEFAULT 1800,
        last_polled     TIMESTAMP NULL,
        last_item_hash  VARCHAR(64) DEFAULT NULL,
        assigned_agent  VARCHAR(50) DEFAULT NULL,
        status          ENUM('active','paused','error') DEFAULT 'active',
        error_message   TEXT DEFAULT NULL,
        items_total     INT DEFAULT 0,
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_category (category),
        INDEX idx_poll (last_polled),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS alfred_feed_items (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        feed_id         INT NOT NULL,
        item_hash       VARCHAR(64) UNIQUE NOT NULL,
        title           VARCHAR(500) NOT NULL,
        summary         TEXT DEFAULT NULL,
        url             VARCHAR(1000) DEFAULT NULL,
        relevance_score DECIMAL(3,2) DEFAULT 0.50,
        processed       BOOLEAN DEFAULT FALSE,
        action_taken    TEXT DEFAULT NULL,
        published_at    TIMESTAMP NULL,
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (feed_id) REFERENCES alfred_feeds(id) ON DELETE CASCADE,
        INDEX idx_relevance (relevance_score),
        INDEX idx_processed (processed),
        INDEX idx_feed (feed_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    return true;
}

// ─── RSS Parser ────────────────────────────────────────────────────
function parseRSSFeed($url) {
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 15,
            'user_agent' => 'AlfredBot/1.0 (+https://gositeme.com/alfred)',
        ],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);

    $xml = @file_get_contents($url, false, $ctx);
    if (!$xml) return [];

    libxml_use_internal_errors(true);
    $feed = @simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
    if (!$feed) return [];

    $items = [];

    // RSS 2.0
    if (isset($feed->channel->item)) {
        foreach ($feed->channel->item as $item) {
            $items[] = [
                'title' => (string) ($item->title ?? 'Untitled'),
                'summary' => strip_tags((string) ($item->description ?? '')),
                'url' => (string) ($item->link ?? ''),
                'published_at' => !empty((string) $item->pubDate) ? date('Y-m-d H:i:s', strtotime((string) $item->pubDate)) : null,
            ];
            if (count($items) >= 50) break;
        }
    }
    // Atom
    elseif (isset($feed->entry)) {
        foreach ($feed->entry as $entry) {
            $link = '';
            foreach ($entry->link as $l) {
                if ((string) $l['rel'] === 'alternate' || empty($link)) {
                    $link = (string) $l['href'];
                }
            }
            $items[] = [
                'title' => (string) ($entry->title ?? 'Untitled'),
                'summary' => strip_tags((string) ($entry->summary ?? $entry->content ?? '')),
                'url' => $link,
                'published_at' => !empty((string) $entry->published) ? date('Y-m-d H:i:s', strtotime((string) $entry->published)) : (!empty((string) $entry->updated) ? date('Y-m-d H:i:s', strtotime((string) $entry->updated)) : null),
            ];
            if (count($items) >= 50) break;
        }
    }

    return $items;
}

// ─── Hacker News API Parser ───────────────────────────────────────
function parseHNFeed() {
    $ctx = stream_context_create(['http' => ['timeout' => 10]]);
    $topStories = @file_get_contents('https://hacker-news.firebaseio.com/v0/topstories.json', false, $ctx);
    if (!$topStories) return [];

    $ids = json_decode($topStories, true);
    if (!$ids) return [];

    $items = [];
    foreach (array_slice($ids, 0, 30) as $id) {
        $story = @file_get_contents("https://hacker-news.firebaseio.com/v0/item/{$id}.json", false, $ctx);
        if (!$story) continue;
        $s = json_decode($story, true);
        if (!$s || ($s['type'] ?? '') !== 'story') continue;

        $items[] = [
            'title' => $s['title'] ?? 'Untitled',
            'summary' => 'Score: ' . ($s['score'] ?? 0) . ' | Comments: ' . ($s['descendants'] ?? 0),
            'url' => $s['url'] ?? "https://news.ycombinator.com/item?id={$id}",
            'published_at' => date('Y-m-d H:i:s', $s['time'] ?? time()),
        ];
    }
    return $items;
}

// ─── Generic API Parser ───────────────────────────────────────────
function parseAPIFeed($url) {
    $ctx = stream_context_create([
        'http' => ['timeout' => 15, 'user_agent' => 'AlfredBot/1.0'],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);
    $data = @file_get_contents($url, false, $ctx);
    if (!$data) return [];

    // CoinGecko market data
    $json = json_decode($data, true);
    if (!$json) return [];

    $items = [];
    // Handle array of objects with common fields
    foreach (array_slice($json, 0, 30) as $obj) {
        if (!is_array($obj)) continue;
        $items[] = [
            'title' => $obj['name'] ?? $obj['title'] ?? $obj['id'] ?? 'Item',
            'summary' => json_encode(array_slice($obj, 0, 5)),
            'url' => $obj['url'] ?? $obj['link'] ?? '',
            'published_at' => isset($obj['published_at']) ? date('Y-m-d H:i:s', strtotime($obj['published_at'])) : (isset($obj['last_updated']) ? date('Y-m-d H:i:s', strtotime($obj['last_updated'])) : null),
        ];
    }
    return $items;
}

// ─── Router ────────────────────────────────────────────────────────
$action = sanitize($_GET['action'] ?? '', 30);
$db = getDB();

if (!$db) jsonResponse(['error' => 'Database unavailable'], 503);

ensureFeedsSchema();

switch ($action) {

    // ── List Feeds ──────────────────────────────────────────────────
    case 'feeds':
        if (!isInternalCall()) requireAuth();

        $category = sanitize($_GET['category'] ?? '', 50);
        $sql = "SELECT * FROM alfred_feeds";
        $params = [];

        if ($category) {
            $sql .= " WHERE category = ?";
            $params[] = $category;
        }
        $sql .= " ORDER BY category, feed_name";

        $stmt = $db->prepare($sql);
        dbExecute($stmt, $params);
        $feeds = $stmt->fetchAll();

        // Add "due for poll" flag
        foreach ($feeds as &$f) {
            $f['due_for_poll'] = !$f['last_polled'] || (time() - strtotime($f['last_polled'])) > $f['poll_interval'];
        }

        jsonResponse(['success' => true, 'feeds' => $feeds, 'total' => count($feeds)]);
        break;

    // ── Add Feed ────────────────────────────────────────────────────
    case 'add-feed':
        if (!isInternalCall()) {
            requireAuth();
            if (!isAdmin()) jsonResponse(['error' => 'Admin access required'], 403);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) jsonResponse(['error' => 'JSON body required'], 400);

        $name = sanitize($input['feed_name'] ?? '', 100);
        $url = filter_var($input['feed_url'] ?? '', FILTER_VALIDATE_URL);
        $type = sanitize($input['feed_type'] ?? 'rss', 20);
        $category = sanitize($input['category'] ?? '', 50);

        if (!$name || !$url || !$category) jsonResponse(['error' => 'feed_name, feed_url, and category required'], 400);
        if (!in_array($type, ['rss', 'api', 'websocket', 'scrape'])) jsonResponse(['error' => 'Invalid feed_type'], 400);

        $stmt = $db->prepare("INSERT INTO alfred_feeds (feed_name, feed_url, feed_type, category, poll_interval, assigned_agent) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $name, $url, $type, $category,
            intval($input['poll_interval'] ?? 1800),
            sanitize($input['assigned_agent'] ?? '', 50) ?: null,
        ]);

        jsonResponse(['success' => true, 'feed_id' => $db->lastInsertId()]);
        break;

    // ── Update Feed ─────────────────────────────────────────────────
    case 'update-feed':
        if (!isInternalCall()) {
            requireAuth();
            if (!isAdmin()) jsonResponse(['error' => 'Admin access required'], 403);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $feedId = intval($input['feed_id'] ?? 0);
        if (!$feedId) jsonResponse(['error' => 'feed_id required'], 400);

        $updates = [];
        $params = [];

        if (isset($input['status']) && in_array($input['status'], ['active', 'paused', 'error'])) {
            $updates[] = "status = ?";
            $params[] = $input['status'];
        }
        if (isset($input['poll_interval'])) {
            $updates[] = "poll_interval = ?";
            $params[] = max(60, intval($input['poll_interval']));
        }
        if (isset($input['assigned_agent'])) {
            $updates[] = "assigned_agent = ?";
            $params[] = sanitize($input['assigned_agent'], 50);
        }

        if (empty($updates)) jsonResponse(['error' => 'No valid fields to update'], 400);

        $params[] = $feedId;
        $db->prepare("UPDATE alfred_feeds SET " . implode(', ', $updates) . " WHERE id = ?")->execute($params);

        jsonResponse(['success' => true]);
        break;

    // ── Items ───────────────────────────────────────────────────────
    case 'items':
        if (!isInternalCall()) requireAuth();

        $page = max(intval($_GET['page'] ?? 1), 1);
        $limit = min(max(intval($_GET['limit'] ?? 25), 1), 100);
        $offset = ($page - 1) * $limit;

        $sql = "SELECT fi.*, f.feed_name, f.category FROM alfred_feed_items fi JOIN alfred_feeds f ON fi.feed_id = f.id WHERE 1=1";
        $countSql = "SELECT COUNT(*) FROM alfred_feed_items fi JOIN alfred_feeds f ON fi.feed_id = f.id WHERE 1=1";
        $params = [];

        if (!empty($_GET['feed_id'])) {
            $sql .= " AND fi.feed_id = ?";
            $countSql .= " AND fi.feed_id = ?";
            $params[] = intval($_GET['feed_id']);
        }
        if (!empty($_GET['category'])) {
            $sql .= " AND f.category = ?";
            $countSql .= " AND f.category = ?";
            $params[] = sanitize($_GET['category'], 50);
        }
        if (isset($_GET['processed'])) {
            $sql .= " AND fi.processed = ?";
            $countSql .= " AND fi.processed = ?";
            $params[] = intval($_GET['processed']);
        }
        if (!empty($_GET['min_relevance'])) {
            $sql .= " AND fi.relevance_score >= ?";
            $countSql .= " AND fi.relevance_score >= ?";
            $params[] = floatval($_GET['min_relevance']);
        }

        $countStmt = $db->prepare($countSql);
        dbExecute($countStmt, $params);
        $total = $countStmt->fetchColumn();

        $sql .= " ORDER BY fi.relevance_score DESC, fi.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $db->prepare($sql);
        dbExecute($stmt, $params);

        jsonResponse([
            'success' => true,
            'items' => $stmt->fetchAll(),
            'pagination' => [
                'page' => $page, 'limit' => $limit,
                'total' => (int) $total, 'pages' => ceil($total / $limit),
            ],
        ]);
        break;

    // ── Unprocessed ─────────────────────────────────────────────────
    case 'unprocessed':
        if (!isInternalCall()) requireAuth();

        $limit = min(max(intval($_GET['limit'] ?? 20), 1), 50);
        $stmt = $db->prepare("SELECT fi.*, f.feed_name, f.category, f.assigned_agent FROM alfred_feed_items fi JOIN alfred_feeds f ON fi.feed_id = f.id WHERE fi.processed = 0 ORDER BY fi.relevance_score DESC, fi.created_at ASC LIMIT ?");
        dbExecute($stmt, [$limit]);

        jsonResponse(['success' => true, 'items' => $stmt->fetchAll()]);
        break;

    // ── Process Item ────────────────────────────────────────────────
    case 'process':
        if (!isInternalCall()) {
            requireAuth();
            if (!isAdmin()) jsonResponse(['error' => 'Admin access required'], 403);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $itemId = intval($input['item_id'] ?? 0);
        if (!$itemId) jsonResponse(['error' => 'item_id required'], 400);

        $stmt = $db->prepare("UPDATE alfred_feed_items SET processed = 1, action_taken = ? WHERE id = ?");
        $stmt->execute([
            sanitize($input['action_taken'] ?? 'acknowledged', 500),
            $itemId,
        ]);

        jsonResponse(['success' => true]);
        break;

    // ── Poll Feeds ──────────────────────────────────────────────────
    case 'poll':
        if (!isInternalCall()) {
            requireAuth();
            if (!isAdmin()) jsonResponse(['error' => 'Admin access required'], 403);
        }

        $feedId = intval($_GET['feed_id'] ?? 0);

        if ($feedId) {
            $feeds = $db->prepare("SELECT * FROM alfred_feeds WHERE id = ? AND status = 'active'");
            $feeds->execute([$feedId]);
            $feeds = $feeds->fetchAll();
        } else {
            // Poll all feeds that are due
            $feeds = $db->query("SELECT * FROM alfred_feeds WHERE status = 'active' AND (last_polled IS NULL OR TIMESTAMPDIFF(SECOND, last_polled, NOW()) >= poll_interval)")->fetchAll();
        }

        $results = [];

        foreach ($feeds as $feed) {
            $items = [];

            switch ($feed['feed_type']) {
                case 'rss':
                    $items = parseRSSFeed($feed['feed_url']);
                    break;
                case 'api':
                    if (strpos($feed['feed_url'], 'hacker-news') !== false) {
                        $items = parseHNFeed();
                    } else {
                        $items = parseAPIFeed($feed['feed_url']);
                    }
                    break;
                // websocket and scrape handled by dedicated services
            }

            $newCount = 0;
            foreach ($items as $item) {
                $hash = hash('sha256', $item['url'] . '|' . $item['title']);
                $title = mb_substr($item['title'], 0, 500);
                $summary = mb_substr($item['summary'] ?? '', 0, 2000);
                $url = mb_substr($item['url'] ?? '', 0, 1000);

                try {
                    $stmt = $db->prepare("INSERT IGNORE INTO alfred_feed_items (feed_id, item_hash, title, summary, url, published_at) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$feed['id'], $hash, $title, $summary, $url, $item['published_at']]);
                    if ($stmt->rowCount() > 0) $newCount++;
                } catch (PDOException $e) {
                    // Duplicate hash, skip
                }
            }

            // Update feed metadata
            $lastHash = count($items) > 0 ? hash('sha256', $items[0]['url'] . '|' . $items[0]['title']) : $feed['last_item_hash'];
            $db->prepare("UPDATE alfred_feeds SET last_polled = NOW(), last_item_hash = ?, items_total = items_total + ?, error_message = NULL WHERE id = ?")->execute([$lastHash, $newCount, $feed['id']]);

            $results[] = [
                'feed_id' => $feed['id'],
                'feed_name' => $feed['feed_name'],
                'fetched' => count($items),
                'new' => $newCount,
            ];
        }

        jsonResponse(['success' => true, 'polled' => count($results), 'results' => $results]);
        break;

    // ── Stats ───────────────────────────────────────────────────────
    case 'stats':
        if (!isInternalCall()) requireAuth();

        $totalFeeds = $db->query("SELECT COUNT(*) FROM alfred_feeds")->fetchColumn();
        $activeFeeds = $db->query("SELECT COUNT(*) FROM alfred_feeds WHERE status = 'active'")->fetchColumn();
        $totalItems = $db->query("SELECT COUNT(*) FROM alfred_feed_items")->fetchColumn();
        $unprocessed = $db->query("SELECT COUNT(*) FROM alfred_feed_items WHERE processed = 0")->fetchColumn();
        $todayItems = $db->query("SELECT COUNT(*) FROM alfred_feed_items WHERE DATE(created_at) = CURDATE()")->fetchColumn();

        $byCategory = $db->query("SELECT f.category, COUNT(DISTINCT f.id) as feeds, COUNT(fi.id) as items FROM alfred_feeds f LEFT JOIN alfred_feed_items fi ON f.id = fi.feed_id GROUP BY f.category ORDER BY items DESC")->fetchAll();

        jsonResponse([
            'success' => true,
            'total_feeds' => (int) $totalFeeds,
            'active_feeds' => (int) $activeFeeds,
            'total_items' => (int) $totalItems,
            'unprocessed' => (int) $unprocessed,
            'today' => (int) $todayItems,
            'by_category' => $byCategory,
        ]);
        break;

    // ── Seed Default Feeds ──────────────────────────────────────────
    case 'seed':
        if (!isInternalCall()) {
            requireAuth();
            if (!isAdmin()) jsonResponse(['error' => 'Admin access required'], 403);
        }

        $defaultFeeds = [
            ['Hacker News', 'https://hacker-news.firebaseio.com/v0/topstories.json', 'api', 'tech', 1800, 'SAGE-CRAWLER'],
            ['TechCrunch', 'https://techcrunch.com/feed/', 'rss', 'tech', 1800, 'SAGE-CRAWLER'],
            ['The Verge', 'https://www.theverge.com/rss/index.xml', 'rss', 'tech', 3600, 'SAGE-CRAWLER'],
            ['Ars Technica', 'https://feeds.arstechnica.com/arstechnica/index', 'rss', 'tech', 3600, 'SAGE-CRAWLER'],
            ['CoinGecko Top Coins', 'https://api.coingecko.com/api/v3/coins/markets?vs_currency=usd&order=market_cap_desc&per_page=20', 'api', 'finance', 300, 'ATLAS-TRADER'],
            ['CVE Recent', 'https://cve.circl.lu/api/last/30', 'api', 'security', 3600, 'CIPHER-SENTINEL'],
            ['GitHub Advisory', 'https://github.com/advisories.atom', 'rss', 'security', 3600, 'CIPHER-SENTINEL'],
            ['Product Hunt', 'https://www.producthunt.com/feed', 'rss', 'competitors', 7200, 'HERALD-SEO'],
            ['PHP Releases', 'https://www.php.net/releases/feed.php', 'rss', 'development', 86400, 'NOVA-DEBUGGER'],
            ['Node.js Blog', 'https://nodejs.org/en/feed/blog.xml', 'rss', 'development', 86400, 'NOVA-DEBUGGER'],
        ];

        $inserted = 0;
        $skipped = 0;
        foreach ($defaultFeeds as $f) {
            $exists = $db->prepare("SELECT COUNT(*) FROM alfred_feeds WHERE feed_url = ?");
            $exists->execute([$f[1]]);
            if ($exists->fetchColumn() > 0) { $skipped++; continue; }

            $stmt = $db->prepare("INSERT INTO alfred_feeds (feed_name, feed_url, feed_type, category, poll_interval, assigned_agent) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute($f);
            $inserted++;
        }

        jsonResponse(['success' => true, 'inserted' => $inserted, 'skipped' => $skipped, 'total_seeded' => count($defaultFeeds)]);
        break;

    default:
        jsonResponse([
            'error' => 'Unknown action',
            'available_actions' => ['feeds', 'add-feed', 'update-feed', 'items', 'unprocessed', 'process', 'poll', 'stats', 'seed'],
        ], 400);
}
