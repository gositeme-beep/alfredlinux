<?php
/**
 * DEVELOPER CONSOLE API — GoSiteMe Store
 * Allows developers to publish, update, and manage their apps/games/agents.
 * 
 * Actions:
 *   my_listings   — Get all developer's published items
 *   submit        — Submit a new item for review
 *   update_item   — Update an existing listing (title, desc, screenshots, etc.)
 *   publish_version — Push a new version with changelog
 *   analytics     — Download/install analytics for developer's items
 *   reply_review  — Reply to a user review
 *   withdraw      — Remove a listing from the store
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

session_start();
$client_id = $_SESSION['client_id'] ?? null;
if (!$client_id) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
require_once dirname(__DIR__) . '/includes/api-security.php';
    exit;
}

$action = $_REQUEST['action'] ?? 'my_listings';
$db = getDB();
$db->exec("SET NAMES utf8mb4");

try {
    switch ($action) {
        case 'my_listings':   handleMyListings($db, $client_id); break;
        case 'submit':        handleSubmit($db, $client_id); break;
        case 'update_item':   handleUpdateItem($db, $client_id); break;
        case 'publish_version': handlePublishVersion($db, $client_id); break;
        case 'analytics':     handleAnalytics($db, $client_id); break;
        case 'reply_review':  handleReplyReview($db, $client_id); break;
        case 'withdraw':      handleWithdraw($db, $client_id); break;
        default:
            echo json_encode(['error' => 'Unknown action', 'actions' => [
                'my_listings','submit','update_item','publish_version',
                'analytics','reply_review','withdraw'
            ]]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
    error_log("Developer Console error: " . $e->getMessage());
}

// ═══════════════════════════════════════════════════════════════
// HANDLERS
// ═══════════════════════════════════════════════════════════════

function handleMyListings(PDO $db, int $client_id): void {
    $stmt = $db->prepare("SELECT id, item_type, slug, title, short_desc, icon_url, 
        category, price, billing_cycle, version, status, rating_avg, rating_count, installs,
        featured, editors_choice, created_at, updated_at
        FROM store_items WHERE developer_id = ? ORDER BY updated_at DESC");
    $stmt->execute([$client_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Summary stats
    $totalInstalls = array_sum(array_column($items, 'installs'));
    $avgRating = count($items) > 0 ? array_sum(array_column($items, 'rating_avg')) / count($items) : 0;

    echo json_encode([
        'items' => $items,
        'summary' => [
            'total_listings' => count($items),
            'total_installs' => $totalInstalls,
            'avg_rating' => round($avgRating, 2),
            'active' => count(array_filter($items, fn($i) => $i['status'] === 'active')),
            'in_review' => count(array_filter($items, fn($i) => $i['status'] === 'review')),
        ],
    ]);
}

function handleSubmit(PDO $db, int $client_id): void {
    $required = ['item_type', 'title', 'short_desc'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['error' => "Missing required field: $field"]);
            return;
        }
    }

    $type = sanitize($_POST['item_type'], 20);
    if (!in_array($type, ['game','agent','extension','service','app','template','tool'])) {
        echo json_encode(['error' => 'Invalid item_type']);
        return;
    }

    $title = sanitize($_POST['title'], 200);
    $slug = preg_replace('/[^a-z0-9\-]/', '', str_replace(' ', '-', strtolower($title)));
    $slug = substr($slug, 0, 120);

    // Check slug uniqueness
    $check = $db->prepare("SELECT id FROM store_items WHERE slug = ?");
    $check->execute([$slug]);
    if ($check->fetch()) {
        $slug .= '-' . substr(md5(uniqid('', true)), 0, 6);
    }

    $shortDesc = sanitize($_POST['short_desc'], 300);
    $description = sanitize($_POST['description'] ?? '', 10000);
    $category = sanitize($_POST['category'] ?? 'general', 60);
    $price = max(0, (float)($_POST['price'] ?? 0));
    $billingCycle = in_array($_POST['billing_cycle'] ?? 'free', ['free','onetime','monthly','yearly']) 
                    ? $_POST['billing_cycle'] : 'free';
    $contentRating = in_array($_POST['content_rating'] ?? 'everyone', ['everyone','teen','mature'])
                     ? $_POST['content_rating'] : 'everyone';
    $launchUrl = filter_var($_POST['launch_url'] ?? '', FILTER_VALIDATE_URL) ? $_POST['launch_url'] : '';
    $iconUrl = sanitize($_POST['icon_url'] ?? '', 500);
    $tags = isset($_POST['tags']) ? json_encode(array_slice(array_map('trim', explode(',', $_POST['tags'])), 0, 10)) : '[]';
    $screenshots = isset($_POST['screenshots']) ? json_encode(array_slice(array_filter(array_map('trim', explode(',', $_POST['screenshots']))), 0, 8)) : '[]';
    $platform = isset($_POST['platform']) ? json_encode(array_map('trim', explode(',', $_POST['platform']))) : '["web"]';

    // Get developer name
    $devName = sanitize($_POST['developer_name'] ?? '', 120);
    if (!$devName) {
        $devName = $_SESSION['client_name'] ?? 'Developer';
    }

    $stmt = $db->prepare("INSERT INTO store_items 
        (item_type, slug, title, short_desc, description, developer_id, developer_name, 
         icon_url, screenshots, category, tags, price, billing_cycle, content_rating,
         launch_url, platform, version, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, '1.0.0', 'review')");
    $stmt->execute([
        $type, $slug, $title, $shortDesc, $description, $client_id, $devName,
        $iconUrl, $screenshots, $category, $tags, $price, $billingCycle, $contentRating,
        $launchUrl, $platform
    ]);

    $newId = $db->lastInsertId();

    // Create initial version entry
    $db->prepare("INSERT INTO store_versions (item_id, version, changelog) VALUES (?, '1.0.0', 'Initial release')")
       ->execute([$newId]);

    echo json_encode([
        'success' => true,
        'id' => $newId,
        'slug' => $slug,
        'status' => 'review',
        'message' => 'Your app has been submitted for review. We\'ll notify you when it\'s approved.',
    ]);
}

function handleUpdateItem(PDO $db, int $client_id): void {
    $itemId = (int)($_POST['item_id'] ?? 0);
    if (!$itemId) { echo json_encode(['error' => 'item_id required']); return; }

    // Verify ownership
    $check = $db->prepare("SELECT id FROM store_items WHERE id = ? AND developer_id = ?");
    $check->execute([$itemId, $client_id]);
    if (!$check->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Not your listing']);
        return;
    }

    $updates = [];
    $params = [];
    $allowed = [
        'title' => 200, 'short_desc' => 300, 'description' => 10000,
        'icon_url' => 500, 'banner_url' => 500, 'video_url' => 500,
        'category' => 60, 'content_rating' => 10, 'launch_url' => 500,
    ];

    foreach ($allowed as $field => $maxLen) {
        if (isset($_POST[$field])) {
            $updates[] = "$field = ?";
            $params[] = sanitize($_POST[$field], $maxLen);
        }
    }

    if (isset($_POST['price'])) {
        $updates[] = "price = ?";
        $params[] = max(0, (float)$_POST['price']);
    }
    if (isset($_POST['billing_cycle']) && in_array($_POST['billing_cycle'], ['free','onetime','monthly','yearly'])) {
        $updates[] = "billing_cycle = ?";
        $params[] = $_POST['billing_cycle'];
    }
    if (isset($_POST['tags'])) {
        $updates[] = "tags = ?";
        $params[] = json_encode(array_slice(array_map('trim', explode(',', $_POST['tags'])), 0, 10));
    }
    if (isset($_POST['screenshots'])) {
        $updates[] = "screenshots = ?";
        $params[] = json_encode(array_slice(array_filter(array_map('trim', explode(',', $_POST['screenshots']))), 0, 8));
    }

    if (empty($updates)) {
        echo json_encode(['error' => 'No fields to update']);
        return;
    }

    $params[] = $itemId;
    $db->prepare("UPDATE store_items SET " . implode(', ', $updates) . " WHERE id = ?")->execute($params);

    echo json_encode(['success' => true, 'updated_fields' => count($updates)]);
}

function handlePublishVersion(PDO $db, int $client_id): void {
    $itemId = (int)($_POST['item_id'] ?? 0);
    $version = sanitize($_POST['version'] ?? '', 20);
    $changelog = sanitize($_POST['changelog'] ?? 'Bug fixes and improvements.', 5000);

    if (!$itemId || !$version) {
        echo json_encode(['error' => 'item_id and version required']);
        return;
    }

    // Verify ownership
    $check = $db->prepare("SELECT id, version FROM store_items WHERE id = ? AND developer_id = ?");
    $check->execute([$itemId, $client_id]);
    $item = $check->fetch(PDO::FETCH_ASSOC);
    if (!$item) {
        http_response_code(403);
        echo json_encode(['error' => 'Not your listing']);
        return;
    }

    // Version must be newer (simple string comparison)
    if (version_compare($version, $item['version'], '<=')) {
        echo json_encode(['error' => "Version must be newer than current ({$item['version']})"]);
        return;
    }

    $downloadUrl = sanitize($_POST['download_url'] ?? '', 500);
    $sizeBytes = max(0, (int)($_POST['size_bytes'] ?? 0));

    // Insert version record
    $db->prepare("INSERT INTO store_versions (item_id, version, changelog, download_url, size_bytes) VALUES (?, ?, ?, ?, ?)")
       ->execute([$itemId, $version, $changelog, $downloadUrl, $sizeBytes]);

    // Update item's current version
    $db->prepare("UPDATE store_items SET version = ?, updated_at = NOW() WHERE id = ?")->execute([$version, $itemId]);

    echo json_encode([
        'success' => true,
        'version' => $version,
        'previous' => $item['version'],
    ]);
}

function handleAnalytics(PDO $db, int $client_id): void {
    $itemId = isset($_REQUEST['item_id']) ? (int)$_REQUEST['item_id'] : null;

    // Get all developer's items or specific one
    if ($itemId) {
        $check = $db->prepare("SELECT id FROM store_items WHERE id = ? AND developer_id = ?");
        $check->execute([$itemId, $client_id]);
        if (!$check->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'Not your listing']);
            return;
        }
        $ids = [$itemId];
    } else {
        $stmt = $db->prepare("SELECT id FROM store_items WHERE developer_id = ?");
        $stmt->execute([$client_id]);
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    if (empty($ids)) {
        echo json_encode(['analytics' => [], 'summary' => []]);
        return;
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    // Per-item stats
    $items = $db->prepare("SELECT id, title, slug, installs, rating_avg, rating_count, trending_score
        FROM store_items WHERE id IN ($placeholders)");
    $items->execute($ids);
    $analytics = $items->fetchAll(PDO::FETCH_ASSOC);

    // Recent installs (last 30 days) per item
    foreach ($analytics as &$a) {
        $recent = $db->prepare("SELECT COUNT(*) FROM store_installs WHERE item_id = ? AND installed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $recent->execute([$a['id']]);
        $a['installs_30d'] = (int)$recent->fetchColumn();

        $reviews = $db->prepare("SELECT COUNT(*) FROM store_reviews WHERE item_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $reviews->execute([$a['id']]);
        $a['reviews_30d'] = (int)$reviews->fetchColumn();
    }

    // Overall summary
    $totalInstalls = array_sum(array_column($analytics, 'installs'));
    $totalInstalls30d = array_sum(array_column($analytics, 'installs_30d'));

    echo json_encode([
        'analytics' => $analytics,
        'summary' => [
            'total_items' => count($analytics),
            'total_installs' => $totalInstalls,
            'installs_30d' => $totalInstalls30d,
            'avg_rating' => count($analytics) > 0 ? round(array_sum(array_column($analytics, 'rating_avg')) / count($analytics), 2) : 0,
        ],
    ]);
}

function handleReplyReview(PDO $db, int $client_id): void {
    $reviewId = (int)($_POST['review_id'] ?? 0);
    $reply = sanitize($_POST['reply'] ?? '', 2000);

    if (!$reviewId || !$reply) {
        echo json_encode(['error' => 'review_id and reply required']);
        return;
    }

    // Verify the review belongs to one of developer's items
    $check = $db->prepare("SELECT r.id FROM store_reviews r 
        JOIN store_items i ON r.item_id = i.id 
        WHERE r.id = ? AND i.developer_id = ?");
    $check->execute([$reviewId, $client_id]);
    if (!$check->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Not your listing\'s review']);
        return;
    }

    $db->prepare("UPDATE store_reviews SET developer_reply = ?, developer_reply_at = NOW() WHERE id = ?")
       ->execute([$reply, $reviewId]);

    echo json_encode(['success' => true]);
}

function handleWithdraw(PDO $db, int $client_id): void {
    $itemId = (int)($_POST['item_id'] ?? 0);
    if (!$itemId) { echo json_encode(['error' => 'item_id required']); return; }

    $check = $db->prepare("SELECT id FROM store_items WHERE id = ? AND developer_id = ?");
    $check->execute([$itemId, $client_id]);
    if (!$check->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Not your listing']);
        return;
    }

    $db->prepare("UPDATE store_items SET status = 'removed' WHERE id = ?")->execute([$itemId]);
    echo json_encode(['success' => true, 'message' => 'Listing withdrawn from the store.']);
}

function sanitize(string $input, int $maxLen): string {
    return mb_substr(trim(strip_tags($input)), 0, $maxLen);
}
