<?php
/**
 * UNIFIED APP STORE API — GoSiteMe Store
 * The single backend powering the App Store that rivals Google Play.
 * 
 * Aggregates: AI Agents, VR Games, Extensions, Services, Developer Apps
 * 
 * Actions:
 *   home       — Featured banners, trending, new, top-rated, collections
 *   browse     — Browse by content type + category with pagination
 *   search     — Unified full-text search across all content types
 *   detail     — Full detail page for any item (screenshots, reviews, versions)
 *   install    — Record install/hire/purchase of an item
 *   uninstall  — Remove an installed item
 *   rate       — Submit a rating (1-5 stars)
 *   review     — Submit a text review
 *   reviews    — Get reviews for an item with helpful votes
 *   vote       — Vote a review as helpful
 *   versions   — Get version history for an item
 *   my_apps    — User's installed/purchased apps
 *   collections — Curated collections (Editor's Choice, etc.)
 *   update_check — Check for available updates on installed items
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Auth (optional — some actions work for guests)
session_start();
$client_id = $_SESSION['client_id'] ?? null;

$action = $_REQUEST['action'] ?? 'home';
$db = getDB();
$db->exec("SET NAMES utf8mb4");

// ═══════════════════════════════════════════════════════════════
// SCHEMA — Auto-create unified store tables if they don't exist
// ═══════════════════════════════════════════════════════════════
$db->exec("CREATE TABLE IF NOT EXISTS store_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_type ENUM('game','agent','extension','service','app','template','tool') NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    title VARCHAR(200) NOT NULL,
    short_desc VARCHAR(300) DEFAULT '',
    description TEXT,
    developer_id INT DEFAULT NULL,
    developer_name VARCHAR(120) DEFAULT 'GoSiteMe',
    icon_url VARCHAR(500) DEFAULT '',
    banner_url VARCHAR(500) DEFAULT '',
    screenshots JSON DEFAULT NULL,
    video_url VARCHAR(500) DEFAULT '',
    category VARCHAR(60) DEFAULT 'general',
    tags JSON DEFAULT NULL,
    price DECIMAL(10,2) DEFAULT 0.00,
    currency VARCHAR(3) DEFAULT 'USD',
    billing_cycle ENUM('free','onetime','monthly','yearly') DEFAULT 'free',
    content_rating ENUM('everyone','teen','mature') DEFAULT 'everyone',
    min_age INT DEFAULT 0,
    version VARCHAR(20) DEFAULT '1.0.0',
    download_url VARCHAR(500) DEFAULT '',
    launch_url VARCHAR(500) DEFAULT '',
    platform JSON DEFAULT '[\"web\"]',
    size_bytes BIGINT DEFAULT 0,
    installs INT DEFAULT 0,
    rating_avg DECIMAL(3,2) DEFAULT 0.00,
    rating_count INT DEFAULT 0,
    featured TINYINT(1) DEFAULT 0,
    editors_choice TINYINT(1) DEFAULT 0,
    trending_score INT DEFAULT 0,
    status ENUM('draft','review','active','suspended','removed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type (item_type),
    INDEX idx_category (category),
    INDEX idx_status (status),
    INDEX idx_featured (featured),
    INDEX idx_rating (rating_avg),
    INDEX idx_installs (installs),
    FULLTEXT idx_search (title, short_desc, description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS store_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    client_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    title VARCHAR(200) DEFAULT '',
    body TEXT,
    helpful_count INT DEFAULT 0,
    developer_reply TEXT DEFAULT NULL,
    developer_reply_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_review (item_id, client_id),
    INDEX idx_item (item_id),
    INDEX idx_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS store_installs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    client_id INT NOT NULL,
    installed_version VARCHAR(20) DEFAULT '1.0.0',
    installed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_install (item_id, client_id),
    INDEX idx_client (client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS store_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    version VARCHAR(20) NOT NULL,
    changelog TEXT,
    download_url VARCHAR(500) DEFAULT '',
    size_bytes BIGINT DEFAULT 0,
    min_sdk INT DEFAULT 0,
    released_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_item (item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS store_collections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(80) NOT NULL UNIQUE,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    icon VARCHAR(60) DEFAULT '⭐',
    item_ids JSON DEFAULT '[]',
    sort_order INT DEFAULT 0,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$db->exec("CREATE TABLE IF NOT EXISTS store_helpful_votes (
    review_id INT NOT NULL,
    client_id INT NOT NULL,
    PRIMARY KEY (review_id, client_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ═══════════════════════════════════════════════════════════════
// SEED — Pre-populate store with existing games, agents, extensions
// ═══════════════════════════════════════════════════════════════
seedStoreIfEmpty($db);
populateCollections($db);

// ═══════════════════════════════════════════════════════════════
// ROUTING
// ═══════════════════════════════════════════════════════════════
try {
    switch ($action) {
        case 'home':        handleHome($db); break;
        case 'browse':      handleBrowse($db); break;
        case 'search':      handleSearch($db); break;
        case 'detail':      handleDetail($db); break;
        case 'install':     requireAuth(); handleInstall($db, $client_id); break;
        case 'uninstall':   requireAuth(); handleUninstall($db, $client_id); break;
        case 'rate':        requireAuth(); handleRate($db, $client_id); break;
        case 'review':      requireAuth(); handleReview($db, $client_id); break;
        case 'reviews':     handleReviews($db); break;
        case 'vote':        requireAuth(); handleVote($db, $client_id); break;
        case 'versions':    handleVersions($db); break;
        case 'my_apps':     requireAuth(); handleMyApps($db, $client_id); break;
        case 'collections': handleCollections($db); break;
        case 'update_check': requireAuth(); handleUpdateCheck($db, $client_id); break;
        case 'categories':  handleCategories($db); break;
        default:
            echo json_encode(['error' => 'Unknown action', 'actions' => [
                'home','browse','search','detail','install','uninstall',
                'rate','review','reviews','vote','versions','my_apps',
                'collections','update_check','categories'
            ]]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
    error_log("Store API error: " . $e->getMessage());
}

// ═══════════════════════════════════════════════════════════════
// HANDLERS
// ═══════════════════════════════════════════════════════════════

function handleHome(PDO $db): void {
    // Featured banners (up to 5)
    $featured = $db->query("SELECT id, item_type, slug, title, short_desc, icon_url, banner_url, 
        rating_avg, rating_count, installs, price, billing_cycle, content_rating
        FROM store_items WHERE featured = 1 AND status = 'active' 
        ORDER BY trending_score DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

    // Editor's Choice
    $editors = $db->query("SELECT id, item_type, slug, title, short_desc, icon_url,
        rating_avg, installs, price, billing_cycle
        FROM store_items WHERE editors_choice = 1 AND status = 'active' 
        ORDER BY rating_avg DESC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);

    // Trending (top installs in last 7 days — approximated by trending_score)
    $trending = $db->query("SELECT id, item_type, slug, title, short_desc, icon_url,
        rating_avg, installs, price, billing_cycle
        FROM store_items WHERE status = 'active' 
        ORDER BY trending_score DESC LIMIT 12")->fetchAll(PDO::FETCH_ASSOC);

    // New arrivals
    $newest = $db->query("SELECT id, item_type, slug, title, short_desc, icon_url,
        rating_avg, installs, price, billing_cycle
        FROM store_items WHERE status = 'active' 
        ORDER BY created_at DESC LIMIT 12")->fetchAll(PDO::FETCH_ASSOC);

    // Top rated
    $topRated = $db->query("SELECT id, item_type, slug, title, short_desc, icon_url,
        rating_avg, rating_count, installs, price, billing_cycle
        FROM store_items WHERE status = 'active' AND rating_count >= 3
        ORDER BY rating_avg DESC, rating_count DESC LIMIT 12")->fetchAll(PDO::FETCH_ASSOC);

    // By type counts
    $counts = $db->query("SELECT item_type, COUNT(*) as count 
        FROM store_items WHERE status = 'active' 
        GROUP BY item_type")->fetchAll(PDO::FETCH_ASSOC);

    // Active collections
    $collections = $db->query("SELECT slug, title, description, icon 
        FROM store_collections WHERE active = 1 
        ORDER BY sort_order LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'featured'    => $featured,
        'editors_choice' => $editors,
        'trending'    => $trending,
        'new'         => $newest,
        'top_rated'   => $topRated,
        'counts'      => $counts,
        'collections' => $collections,
    ]);
}

function handleBrowse(PDO $db): void {
    $type     = storeSanitize($_REQUEST['type'] ?? '', 20);
    $category = storeSanitize($_REQUEST['category'] ?? '', 60);
    $sort     = in_array($_REQUEST['sort'] ?? '', ['popular','newest','rating','name','price_low','price_high']) 
                ? $_REQUEST['sort'] : 'popular';
    $rating   = storeSanitize($_REQUEST['content_rating'] ?? '', 10);
    $priceMax = isset($_REQUEST['price_max']) ? (float)$_REQUEST['price_max'] : null;
    $free     = isset($_REQUEST['free']) ? (bool)$_REQUEST['free'] : null;
    $page     = max(1, (int)($_REQUEST['page'] ?? 1));
    $limit    = min(60, max(10, (int)($_REQUEST['limit'] ?? 24)));
    $offset   = ($page - 1) * $limit;

    $where = ["status = 'active'"];
    $params = [];

    if ($type && in_array($type, ['game','agent','extension','service','app','template','tool'])) {
        $where[] = "item_type = ?";
        $params[] = $type;
    }
    if ($category) {
        $where[] = "category = ?";
        $params[] = $category;
    }
    if ($rating && in_array($rating, ['everyone','teen','mature'])) {
        $where[] = "content_rating = ?";
        $params[] = $rating;
    }
    if ($priceMax !== null) {
        $where[] = "price <= ?";
        $params[] = $priceMax;
    }
    if ($free === true) {
        $where[] = "price = 0";
    }

    $orderMap = [
        'popular'    => 'installs DESC',
        'newest'     => 'created_at DESC',
        'rating'     => 'rating_avg DESC, rating_count DESC',
        'name'       => 'title ASC',
        'price_low'  => 'price ASC',
        'price_high' => 'price DESC',
    ];
    $order = $orderMap[$sort];

    $whereSQL = implode(' AND ', $where);

    $stmt = $db->prepare("SELECT COUNT(*) FROM store_items WHERE $whereSQL");
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();

    $stmt = $db->prepare("SELECT id, item_type, slug, title, short_desc, icon_url,
        category, rating_avg, rating_count, installs, price, billing_cycle, content_rating, version
        FROM store_items WHERE $whereSQL ORDER BY $order LIMIT $limit OFFSET $offset");
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'items'      => $items,
        'pagination' => ['page' => $page, 'limit' => $limit, 'total' => $total, 'pages' => ceil($total / $limit)],
        'filters'    => compact('type', 'category', 'sort', 'rating', 'free'),
    ]);
}

function handleSearch(PDO $db): void {
    $q = storeSanitize($_REQUEST['q'] ?? '', 200);
    if (strlen($q) < 2) {
        echo json_encode(['error' => 'Query must be at least 2 characters', 'results' => []]);
        return;
    }
    $type   = storeSanitize($_REQUEST['type'] ?? '', 20);
    $limit  = min(50, max(5, (int)($_REQUEST['limit'] ?? 20)));

    $where = ["status = 'active'"];
    $params = [];

    if ($type && in_array($type, ['game','agent','extension','service','app','template','tool'])) {
        $where[] = "item_type = ?";
        $params[] = $type;
    }

    $whereSQL = implode(' AND ', $where);

    // Full-text search with relevance scoring
    $stmt = $db->prepare("SELECT id, item_type, slug, title, short_desc, icon_url,
        category, rating_avg, rating_count, installs, price, billing_cycle,
        MATCH(title, short_desc, description) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance
        FROM store_items WHERE $whereSQL AND MATCH(title, short_desc, description) AGAINST(? IN NATURAL LANGUAGE MODE)
        ORDER BY relevance DESC, installs DESC LIMIT $limit");
    $params[] = $q;
    $params[] = $q;
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fallback to LIKE if fulltext returns nothing
    if (empty($results)) {
        $likeQ = '%' . $q . '%';
        $stmt = $db->prepare("SELECT id, item_type, slug, title, short_desc, icon_url,
            category, rating_avg, rating_count, installs, price, billing_cycle
            FROM store_items WHERE $whereSQL AND (title LIKE ? OR short_desc LIKE ?)
            ORDER BY installs DESC LIMIT $limit");
        $params2 = array_merge(array_slice($params, 0, -2), [$likeQ, $likeQ]);
        $stmt->execute($params2);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode(['query' => $q, 'count' => count($results), 'results' => $results]);
}

function handleDetail(PDO $db): void {
    $slug = storeSanitize($_REQUEST['slug'] ?? '', 120);
    $id   = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : null;

    if (!$slug && !$id) {
        echo json_encode(['error' => 'slug or id required']);
        return;
    }

    if ($slug) {
        $stmt = $db->prepare("SELECT * FROM store_items WHERE slug = ? AND status = 'active'");
        $stmt->execute([$slug]);
    } else {
        $stmt = $db->prepare("SELECT * FROM store_items WHERE id = ? AND status = 'active'");
        $stmt->execute([$id]);
    }

    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$item) {
        http_response_code(404);
        echo json_encode(['error' => 'App not found']);
        return;
    }

    // Decode JSON fields
    $item['screenshots'] = json_decode($item['screenshots'] ?? '[]', true);
    $item['tags']        = json_decode($item['tags'] ?? '[]', true);
    $item['platform']    = json_decode($item['platform'] ?? '["web"]', true);

    // Recent reviews (top 5)
    $reviews = $db->prepare("SELECT r.rating, r.title, r.body, r.helpful_count, r.created_at,
        r.developer_reply, r.developer_reply_at
        FROM store_reviews r WHERE r.item_id = ? ORDER BY r.helpful_count DESC, r.created_at DESC LIMIT 5");
    $reviews->execute([$item['id']]);
    $item['recent_reviews'] = $reviews->fetchAll(PDO::FETCH_ASSOC);

    // Rating breakdown
    $breakdown = $db->prepare("SELECT rating, COUNT(*) as count FROM store_reviews WHERE item_id = ? GROUP BY rating");
    $breakdown->execute([$item['id']]);
    $item['rating_breakdown'] = array_column($breakdown->fetchAll(PDO::FETCH_ASSOC), 'count', 'rating');

    // Latest version
    $ver = $db->prepare("SELECT version, changelog, released_at FROM store_versions WHERE item_id = ? ORDER BY released_at DESC LIMIT 1");
    $ver->execute([$item['id']]);
    $item['latest_version'] = $ver->fetch(PDO::FETCH_ASSOC) ?: null;

    // Similar items (same category, same type)
    $similar = $db->prepare("SELECT id, slug, title, icon_url, rating_avg, installs, price 
        FROM store_items WHERE category = ? AND item_type = ? AND id != ? AND status = 'active' 
        ORDER BY rating_avg DESC LIMIT 6");
    $similar->execute([$item['category'], $item['item_type'], $item['id']]);
    $item['similar'] = $similar->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($item);
}

function handleInstall(PDO $db, int $client_id): void {
    $item_id = (int)($_REQUEST['item_id'] ?? 0);
    if (!$item_id) { echo json_encode(['error' => 'item_id required']); return; }

    // Verify item exists
    $item = $db->prepare("SELECT id, version, price, billing_cycle, title FROM store_items WHERE id = ? AND status = 'active'");
    $item->execute([$item_id]);
    $itemData = $item->fetch(PDO::FETCH_ASSOC);
    if (!$itemData) { echo json_encode(['error' => 'Item not found']); return; }

    // GSM payment for paid items
    $price = (float)($itemData['price'] ?? 0);
    if ($price > 0) {
        $payMethod = $_REQUEST['pay_method'] ?? 'gsm';
        if ($payMethod === 'gsm') {
            // Check GSM balance
            $bal = $db->prepare("SELECT balance FROM gsm_balances WHERE user_id = ?");
            $bal->execute([$client_id]);
            $currentBalance = (float)($bal->fetchColumn() ?: 0);
            if ($currentBalance < $price) {
                echo json_encode(['error' => 'Insufficient GSM balance', 'required' => $price, 'balance' => $currentBalance]);
                return;
            }
            // Already has this item?
            $hasIt = $db->prepare("SELECT 1 FROM store_installs WHERE item_id = ? AND client_id = ?");
            $hasIt->execute([$item_id, $client_id]);
            if ($hasIt->fetchColumn()) {
                echo json_encode(['error' => 'Already installed']); return;
            }
            // Debit GSM atomically
            $debit = $db->prepare("UPDATE gsm_balances SET balance = balance - ? WHERE user_id = ? AND balance >= ?");
            $debit->execute([$price, $client_id, $price]);
            if ($debit->rowCount() === 0) {
                echo json_encode(['error' => 'Insufficient GSM balance (race)']); return;
            }
            // Record transaction
            $db->prepare("INSERT INTO gsm_transactions (user_id, type, amount, description, reference_type, reference_id) VALUES (?, 'debit', ?, ?, 'store_purchase', ?)")
               ->execute([$client_id, $price, 'Store purchase: ' . $itemData['title'], $item_id]);
            // Platform revenue (1% fee to treasury)
            $fee = round($price * 0.01, 8);
            if ($fee > 0) {
                $db->exec("INSERT INTO gsm_balances (user_id, balance) VALUES (0, $fee) ON DUPLICATE KEY UPDATE balance = balance + $fee");
            }
        }
        // else: other pay methods (Stripe/SOL) — handled by external flow
    }

    // Record install
    $stmt = $db->prepare("INSERT INTO store_installs (item_id, client_id, installed_version) 
        VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE installed_version = VALUES(installed_version), updated_at = NOW()");
    $stmt->execute([$item_id, $client_id, $itemData['version']]);

    // Increment install count
    $db->prepare("UPDATE store_items SET installs = installs + 1 WHERE id = ?")->execute([$item_id]);

    echo json_encode(['success' => true, 'installed_version' => $itemData['version'], 'paid' => $price > 0 ? $price : null]);
}

function handleUninstall(PDO $db, int $client_id): void {
    $item_id = (int)($_REQUEST['item_id'] ?? 0);
    $db->prepare("DELETE FROM store_installs WHERE item_id = ? AND client_id = ?")->execute([$item_id, $client_id]);
    echo json_encode(['success' => true]);
}

function handleRate(PDO $db, int $client_id): void {
    $item_id = (int)($_REQUEST['item_id'] ?? 0);
    $rating  = max(1, min(5, (int)($_REQUEST['rating'] ?? 0)));
    if (!$item_id || !$rating) { echo json_encode(['error' => 'item_id and rating (1-5) required']); return; }

    $stmt = $db->prepare("INSERT INTO store_reviews (item_id, client_id, rating) VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE rating = VALUES(rating), updated_at = NOW()");
    $stmt->execute([$item_id, $client_id, $rating]);

    // Recalculate average
    recalcRating($db, $item_id);
    echo json_encode(['success' => true]);
}

function handleReview(PDO $db, int $client_id): void {
    $item_id = (int)($_REQUEST['item_id'] ?? 0);
    $rating  = max(1, min(5, (int)($_REQUEST['rating'] ?? 0)));
    $title   = storeSanitize($_REQUEST['review_title'] ?? '', 200);
    $body    = storeSanitize($_REQUEST['body'] ?? '', 2000);
    if (!$item_id || !$rating) { echo json_encode(['error' => 'item_id and rating required']); return; }

    $stmt = $db->prepare("INSERT INTO store_reviews (item_id, client_id, rating, title, body) VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE rating = VALUES(rating), title = VALUES(title), body = VALUES(body), updated_at = NOW()");
    $stmt->execute([$item_id, $client_id, $rating, $title, $body]);

    recalcRating($db, $item_id);
    echo json_encode(['success' => true]);
}

function handleReviews(PDO $db): void {
    $item_id = (int)($_REQUEST['item_id'] ?? 0);
    $sort    = in_array($_REQUEST['sort'] ?? '', ['newest','helpful','rating_high','rating_low']) 
               ? $_REQUEST['sort'] : 'helpful';
    $page    = max(1, (int)($_REQUEST['page'] ?? 1));
    $limit   = min(50, max(5, (int)($_REQUEST['limit'] ?? 10)));
    $offset  = ($page - 1) * $limit;

    $orderMap = [
        'newest'      => 'r.created_at DESC',
        'helpful'     => 'r.helpful_count DESC, r.created_at DESC',
        'rating_high' => 'r.rating DESC',
        'rating_low'  => 'r.rating ASC',
    ];

    $stmt = $db->prepare("SELECT r.id, r.rating, r.title, r.body, r.helpful_count, r.created_at,
        r.developer_reply, r.developer_reply_at
        FROM store_reviews r WHERE r.item_id = ? 
        ORDER BY {$orderMap[$sort]} LIMIT $limit OFFSET $offset");
    $stmt->execute([$item_id]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total = $db->prepare("SELECT COUNT(*) FROM store_reviews WHERE item_id = ?");
    $total->execute([$item_id]);

    echo json_encode([
        'reviews'    => $reviews,
        'pagination' => ['page' => $page, 'limit' => $limit, 'total' => (int)$total->fetchColumn()],
    ]);
}

function handleVote(PDO $db, int $client_id): void {
    $review_id = (int)($_REQUEST['review_id'] ?? 0);
    if (!$review_id) { echo json_encode(['error' => 'review_id required']); return; }

    try {
        $db->prepare("INSERT INTO store_helpful_votes (review_id, client_id) VALUES (?, ?)")
           ->execute([$review_id, $client_id]);
        $db->prepare("UPDATE store_reviews SET helpful_count = helpful_count + 1 WHERE id = ?")
           ->execute([$review_id]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        // Already voted
        echo json_encode(['error' => 'Already voted']);
    }
}

function handleVersions(PDO $db): void {
    $item_id = (int)($_REQUEST['item_id'] ?? 0);
    $stmt = $db->prepare("SELECT version, changelog, size_bytes, released_at 
        FROM store_versions WHERE item_id = ? ORDER BY released_at DESC LIMIT 20");
    $stmt->execute([$item_id]);
    echo json_encode(['versions' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function handleMyApps(PDO $db, int $client_id): void {
    $stmt = $db->prepare("SELECT i.id, i.item_type, i.slug, i.title, i.icon_url, i.version as latest_version,
        inst.installed_version, inst.installed_at,
        CASE WHEN i.version != inst.installed_version THEN 1 ELSE 0 END as update_available
        FROM store_installs inst 
        JOIN store_items i ON inst.item_id = i.id
        WHERE inst.client_id = ? ORDER BY inst.updated_at DESC");
    $stmt->execute([$client_id]);
    echo json_encode(['apps' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

function handleCollections(PDO $db): void {
    $slug = storeSanitize($_REQUEST['slug'] ?? '', 80);

    if ($slug) {
        $stmt = $db->prepare("SELECT * FROM store_collections WHERE slug = ? AND active = 1");
        $stmt->execute([$slug]);
        $col = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$col) { echo json_encode(['error' => 'Collection not found']); return; }

        $ids = json_decode($col['item_ids'], true);
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $items = $db->prepare("SELECT id, item_type, slug, title, short_desc, icon_url,
                rating_avg, installs, price, billing_cycle
                FROM store_items WHERE id IN ($placeholders) AND status = 'active'");
            $items->execute($ids);
            $col['items'] = $items->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $col['items'] = [];
        }
        echo json_encode($col);
    } else {
        $all = $db->query("SELECT slug, title, description, icon FROM store_collections WHERE active = 1 ORDER BY sort_order")->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['collections' => $all]);
    }
}

function handleUpdateCheck(PDO $db, int $client_id): void {
    $stmt = $db->prepare("SELECT i.id, i.slug, i.title, i.version as latest, inst.installed_version as current
        FROM store_installs inst JOIN store_items i ON inst.item_id = i.id
        WHERE inst.client_id = ? AND i.version != inst.installed_version");
    $stmt->execute([$client_id]);
    $updates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['updates_available' => count($updates), 'items' => $updates]);
}

function handleCategories(PDO $db): void {
    $type = storeSanitize($_REQUEST['type'] ?? '', 20);
    $where = "status = 'active'";
    $params = [];
    if ($type) { $where .= " AND item_type = ?"; $params[] = $type; }

    $stmt = $db->prepare("SELECT category, COUNT(*) as count FROM store_items WHERE $where GROUP BY category ORDER BY count DESC");
    $stmt->execute($params);
    echo json_encode(['categories' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}

// ═══════════════════════════════════════════════════════════════
// HELPERS
// ═══════════════════════════════════════════════════════════════

function recalcRating(PDO $db, int $item_id): void {
    $stmt = $db->prepare("SELECT AVG(rating) as avg_r, COUNT(*) as cnt FROM store_reviews WHERE item_id = ?");
    $stmt->execute([$item_id]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    $db->prepare("UPDATE store_items SET rating_avg = ?, rating_count = ? WHERE id = ?")
       ->execute([round($r['avg_r'], 2), $r['cnt'], $item_id]);
}

function requireAuth(): void {
    global $client_id;
    if (!$client_id) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        exit;
    }
}

function storeSanitize(string $input, int $maxLen): string {
    return mb_substr(trim(strip_tags($input)), 0, $maxLen);
}

// ═══════════════════════════════════════════════════════════════
// SEED — Auto-populate store from existing content on first run
// ═══════════════════════════════════════════════════════════════
function seedStoreIfEmpty(PDO $db): void {
    $count = (int)$db->query("SELECT COUNT(*) FROM store_items")->fetchColumn();
    if ($count > 0) return; // Already seeded

    $items = [
        // ── VR GAMES & EXPERIENCES ──
        ['game','chess-masters','Chess Masters VR','The world\'s most immersive chess experience. 8K cinematic rendering, 200-player multiplayer, AI opponents from beginner to grandmaster, and real-money wagering.','GoSiteMe','🏆','strategy','5.00','yearly','everyone','1.0.0','/vr/chess-masters/',1,1,95],
        ['game','chess-ultimate','Chess Ultimate','Premium chess with realistic piece physics, voiced commentary, and tournament mode.','GoSiteMe','♟️','strategy','0.00','free','everyone','1.0.0','/vr/chess-ultimate/',0,1,80],
        ['game','chess-classic','Chess Classic','Clean, fast chess against Stockfish AI with opening book and puzzle trainer.','GoSiteMe','♔','strategy','0.00','free','everyone','1.0.0','/vr/chess/',0,0,70],
        ['game','vr-poker','Texas Hold\'em Poker','Full 3D Texas Hold\'em poker with 6 AI opponents, 4 game modes, and realistic table.','GoSiteMe','♠','strategy','0.00','free','everyone','1.0.0','/vr/poker/',0,1,85],
        ['game','vr-checkers','Checkers VR','Classic checkers in a beautiful 3D environment with online multiplayer.','GoSiteMe','🔴','strategy','0.00','free','everyone','1.0.0','/vr/checkers/',0,0,60],
        ['game','vr-pool','Pool & Billiards VR','Photorealistic billiards with accurate physics, trick shots, and tournaments.','GoSiteMe','🎱','sports','0.00','free','everyone','1.0.0','/vr/pool/',0,1,75],
        ['game','vr-racing','Racing VR','High-speed racing across exotic tracks with VR cockpit mode and multiplayer.','GoSiteMe','🏎️','racing','0.00','free','everyone','1.0.0','/vr/racing/',0,0,65],
        ['game','backgammon','Backgammon','Classic backgammon with AI difficulty levels and online play.','GoSiteMe','🎲','strategy','0.00','free','everyone','1.0.0','/backgammon/',0,0,50],
        ['game','vr-kingdom','Kingdom Builder','Build your empire in this VR strategy world. Resources, armies, and diplomacy.','GoSiteMe','👑','strategy','0.00','free','teen','1.0.0','/vr/kingdom/',0,0,55],

        // ── VR SOCIAL EXPERIENCES ──
        ['app','dj-studio','DJ Studio VR','Mix tracks, create beats, and perform live DJ sets in virtual reality.','GoSiteMe','🎧','entertainment','0.00','free','everyone','1.0.0','/vr/dj-studio/',1,0,70],
        ['app','concert-hall','Concert Hall VR','Experience live music performances in a stunning virtual concert venue.','GoSiteMe','🎵','entertainment','0.00','free','everyone','1.0.0','/vr/concert/',0,0,60],
        ['app','speed-dating','Speed Dating VR','Meet new people in fun, timed virtual dating rounds with voice chat.','GoSiteMe','💝','social','0.00','free','mature','1.0.0','/vr/speed-dating/',0,0,45],
        ['app','vr-gallery','Art Gallery VR','Explore curated art exhibitions in a beautiful virtual museum.','GoSiteMe','🎨','art','0.00','free','everyone','1.0.0','/vr/gallery/',0,0,40],
        ['app','circuit-lab','Circuit Lab VR','Build and simulate electronic circuits in virtual reality.','GoSiteMe','⚡','education','0.00','free','everyone','1.0.0','/vr/circuit-lab/',0,0,35],

        // ── EXTENSIONS & TOOLS ──
        ['extension','chrome-extension','Alfred Chrome Extension','Access 1,220+ AI tools from any webpage. Side panel, context menus, voice commands.','GoSiteMe','🌐','productivity','0.00','free','everyone','3.0.0','/downloads/alfred-chrome-extension/',1,1,90],
        ['extension','vscode-extension','Alfred VS Code Extension','AI pair programming with 1,220+ tools directly in VS Code.','GoSiteMe','💻','development','0.00','free','everyone','1.0.0','/extensions/vscode/',0,0,40],
        ['tool','alfred-cli','Alfred CLI','Command-line interface for Alfred AI. Automate tasks, run agents, manage tools.','GoSiteMe','⌨️','development','0.00','free','everyone','1.0.0','/downloads/alfred-cli/',0,0,50],

        // ── DESKTOP APPS ──
        ['app','desktop-windows','Veil Browser — Windows','Sovereign AI browser with auto-updates, domain management, mining, and Alfred AI.','GoSiteMe','🪟','productivity','0.00','free','everyone','3.0.0','/downloads/Veil-Browser-3.0.0-win-x64.zip',1,0,60],
        ['app','desktop-macos-intel','Veil Browser — macOS Intel','Sovereign AI browser for Mac Intel. Zip format, macOS 11+.','GoSiteMe','🍎','productivity','0.00','free','everyone','3.0.0','/downloads/Veil-Browser-3.0.0-mac-intel.zip',1,0,55],
        ['app','desktop-macos-arm64','Veil Browser — macOS Apple Silicon','Sovereign AI browser for M1/M2/M3/M4 Macs. Native ARM64 performance.','GoSiteMe','🍎','productivity','0.00','free','everyone','3.0.0','/downloads/Veil-Browser-3.0.0-mac-arm64.zip',1,0,55],
        ['app','desktop-linux','Veil Browser — Linux','Sovereign AI browser for Linux. AppImage format, works on Ubuntu, Fedora, Arch.','GoSiteMe','🐧','productivity','0.00','free','everyone','3.0.0','/downloads/Veil-Browser-3.0.0.AppImage',0,0,40],
        ['app','desktop-linux-deb','Veil Browser — Ubuntu/Debian','Sovereign AI browser as .deb package. Native apt install for Ubuntu 20.04+ and Debian 11+.','GoSiteMe','🐧','productivity','0.00','free','everyone','3.0.0','/downloads/veil-browser_3.0.0_amd64.deb',0,0,45],
        ['app','android-app','Veil Browser — Android','Full Veil Browser on Android. Built-in Alfred AI, update checks on launch.','GoSiteMe','📱','productivity','0.00','free','everyone','3.0.0','/downloads/GoSiteMe-Veil.apk',0,0,70],
        ['app','gocodeme-ide','Alfred IDE — Web','Cloud IDE with Monaco editor, AI chat, project management. No download needed.','GoSiteMe','💻','development','0.00','free','everyone','2.0.0','/editor/',1,1,80],

        // ── AI SERVICES ──
        ['service','alfred-starter','Alfred Starter Plan','100 AI tools, 3 agents, 30 voice minutes. Perfect to get started.','GoSiteMe','🚀','ai','3.99','monthly','everyone','1.0.0','',0,0,50],
        ['service','alfred-pro','Alfred Professional','All 1,220+ tools, unlimited agents, marketplace publishing, API access.','GoSiteMe','⚡','ai','9.99','monthly','everyone','1.0.0','',1,1,85],
        ['service','alfred-enterprise','Alfred Enterprise','20 agents, org accounts, SSO, priority support, dedicated infra.','GoSiteMe','🏢','ai','24.99','monthly','everyone','1.0.0','',0,0,60],
    ];

    $stmt = $db->prepare("INSERT INTO store_items 
        (item_type, slug, title, short_desc, developer_name, icon_url, category, price, billing_cycle, content_rating, version, launch_url, featured, editors_choice, trending_score, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");

    foreach ($items as $item) {
        $stmt->execute($item);
    }

    // Seed collections
    $colStmt = $db->prepare("INSERT INTO store_collections (slug, title, description, icon, item_ids, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
    $colStmt->execute(['editors-choice', 'Editor\'s Choice', 'Hand-picked by the GoSiteMe team for outstanding quality.', '⭐', '[]', 1]);
    $colStmt->execute(['vr-essentials', 'VR Essentials', 'The best virtual reality experiences to try first.', '🥽', '[]', 2]);
    $colStmt->execute(['productivity-powerhouse', 'Productivity Powerhouse', 'AI tools that supercharge your workflow.', '🔧', '[]', 3]);
    $colStmt->execute(['multiplayer-arena', 'Multiplayer Arena', 'Games best played with friends and rivals.', '⚔️', '[]', 4]);
    $colStmt->execute(['learn-and-build', 'Learn & Build', 'Educational tools and creative sandboxes.', '🎓', '[]', 5]);
    $colStmt->execute(['free-favorites', 'Free Favorites', 'Amazing apps and games that won\'t cost a dime.', '🆓', '[]', 6]);

    // Populate collection item_ids from actual seeded IDs
    populateCollections($db);
}

function populateCollections(PDO $db): void {
    // Check if already populated
    $check = $db->query("SELECT item_ids FROM store_collections WHERE slug = 'editors-choice'")->fetchColumn();
    if ($check && $check !== '[]') return;

    $getIds = function(array $slugs) use ($db) {
        $ph = implode(',', array_fill(0, count($slugs), '?'));
        $s = $db->prepare("SELECT id FROM store_items WHERE slug IN ($ph) ORDER BY id");
        $s->execute($slugs);
        return array_column($s->fetchAll(PDO::FETCH_ASSOC), 'id');
    };

    $collections = [
        'editors-choice' => $getIds(['chess-masters','vr-poker','chrome-extension','gocodeme-ide','alfred-pro']),
        'vr-essentials' => $getIds(['chess-masters','chess-ultimate','vr-poker','vr-pool','dj-studio','concert-hall','vr-gallery']),
        'productivity-powerhouse' => $getIds(['chrome-extension','vscode-extension','alfred-cli','gocodeme-ide','alfred-pro']),
        'multiplayer-arena' => $getIds(['chess-masters','vr-poker','vr-checkers','vr-pool','backgammon','speed-dating']),
        'learn-and-build' => $getIds(['circuit-lab','vscode-extension','alfred-cli','gocodeme-ide']),
        'free-favorites' => $getIds(['chess-ultimate','chess-classic','vr-checkers','vr-racing','backgammon','dj-studio','concert-hall','speed-dating','vr-gallery','circuit-lab','android-app']),
    ];

    $upd = $db->prepare("UPDATE store_collections SET item_ids = ? WHERE slug = ?");
    foreach ($collections as $slug => $ids) {
        $upd->execute([json_encode(array_map('intval', $ids)), $slug]);
    }
}
