<?php
/**
 * Alfred Marketplace Creator API
 * Endpoints for creators to manage products, earnings, reviews, and payouts.
 * 70% creator / 30% platform commission split.
 */
if (session_status() === PHP_SESSION_NONE) session_start();
define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json');

requireCSRF();
apiRateLimit(30, 60, 'marketplace');

// Auth check
if (empty($_SESSION['logged_in']) || empty($_SESSION['client_id'])) {
    jsonResponse(['success' => false, 'error' => 'Authentication required'], 401);
}
$client_id = (int) $_SESSION['client_id'];

$db = getDB();
if (!$db) {
    jsonResponse(['success' => false, 'error' => 'Database unavailable'], 500);
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

switch ($action) {

    // ─── Dashboard Stats ───
    case 'dashboard':
        if ($method !== 'GET') jsonResponse(['success' => false, 'error' => 'GET required'], 405);

        // Total products
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM alfred_marketplace_items WHERE seller_user_id = ? AND status != 'suspended'");
        $stmt->execute([$client_id]);
        $totalProducts = (int)$stmt->fetchColumn();

        // Total downloads
        $stmt = $db->prepare("SELECT COALESCE(SUM(downloads), 0) FROM alfred_marketplace_items WHERE seller_user_id = ? AND status != 'suspended'");
        $stmt->execute([$client_id]);
        $totalDownloads = (int)$stmt->fetchColumn();

        // Total revenue (seller earnings)
        $stmt = $db->prepare("SELECT COALESCE(SUM(seller_earnings), 0) FROM alfred_marketplace_purchases WHERE seller_id = ? AND status = 'completed'");
        $stmt->execute([$client_id]);
        $totalRevenue = $stmt->fetchColumn();

        // Average rating
        $stmt = $db->prepare("SELECT COALESCE(AVG(rating), 0) FROM alfred_marketplace_items WHERE seller_user_id = ? AND status = 'published' AND review_count > 0");
        $stmt->execute([$client_id]);
        $avgRating = round((float)$stmt->fetchColumn(), 1);

        // Recent downloads (last 10 purchases of this seller's items)
        $stmt = $db->prepare("
            SELECT p.id, p.price, p.seller_earnings, p.created_at, i.title, i.item_type
            FROM alfred_marketplace_purchases p
            JOIN alfred_marketplace_items i ON p.item_id = i.id
            WHERE p.seller_id = ? AND p.status = 'completed'
            ORDER BY p.created_at DESC LIMIT 10
        ");
        $stmt->execute([$client_id]);
        $recentDownloads = $stmt->fetchAll();

        // Recent reviews
        $stmt = $db->prepare("
            SELECT r.id, r.rating, r.title, r.review, r.created_at, r.seller_response, i.title as product_title
            FROM alfred_marketplace_reviews r
            JOIN alfred_marketplace_items i ON r.item_id = i.id
            WHERE i.seller_user_id = ?
            ORDER BY r.created_at DESC LIMIT 10
        ");
        $stmt->execute([$client_id]);
        $recentReviews = $stmt->fetchAll();

        // Revenue last 30 days (daily)
        $stmt = $db->prepare("
            SELECT DATE(created_at) as day, SUM(seller_earnings) as earnings, COUNT(*) as sales
            FROM alfred_marketplace_purchases
            WHERE seller_id = ? AND status = 'completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at) ORDER BY day
        ");
        $stmt->execute([$client_id]);
        $revenueDays = $stmt->fetchAll();

        jsonResponse([
            'success' => true,
            'stats' => [
                'total_products' => $totalProducts,
                'total_downloads' => $totalDownloads,
                'total_revenue' => $totalRevenue,
                'avg_rating' => $avgRating
            ],
            'recent_downloads' => $recentDownloads,
            'recent_reviews' => $recentReviews,
            'revenue_chart' => $revenueDays
        ]);
        break;

    // ─── List Creator's Products ───
    case 'products':
        if ($method !== 'GET') jsonResponse(['success' => false, 'error' => 'GET required'], 405);

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        $status = sanitize($_GET['status'] ?? '');
        $type = sanitize($_GET['type'] ?? '');

        $where = "seller_user_id = ?";
        $params = [$client_id];

        if ($status && in_array($status, ['draft', 'pending', 'published', 'suspended'])) {
            $where .= " AND status = ?";
            $params[] = $status;
        } else {
            $where .= " AND status != 'suspended'";
        }

        if ($type && in_array($type, ['agent', 'tool', 'fleet', 'template', 'integration'])) {
            $where .= " AND item_type = ?";
            $params[] = $type;
        }

        $stmt = $db->prepare("SELECT COUNT(*) FROM alfred_marketplace_items WHERE $where");
        dbExecute($stmt, $params);
        $total = (int)$stmt->fetchColumn();

        $params[] = $limit;
        $params[] = $offset;
        $stmt = $db->prepare("SELECT * FROM alfred_marketplace_items WHERE $where ORDER BY created_at DESC LIMIT ? OFFSET ?");
        dbExecute($stmt, $params);
        $products = $stmt->fetchAll();

        // Decode JSON fields
        foreach ($products as &$p) {
            $p['tags'] = json_decode($p['tags'] ?? '[]', true) ?: [];
            $p['screenshots'] = json_decode($p['screenshots'] ?? '[]', true) ?: [];
        }

        jsonResponse([
            'success' => true,
            'products' => $products,
            'total' => $total,
            'page' => $page,
            'pages' => ceil($total / $limit)
        ]);
        break;

    // ─── Single Product Detail ───
    case 'product':
        if ($method !== 'GET') jsonResponse(['success' => false, 'error' => 'GET required'], 405);

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) jsonResponse(['success' => false, 'error' => 'Product ID required'], 400);

        $stmt = $db->prepare("SELECT * FROM alfred_marketplace_items WHERE id = ? AND seller_user_id = ?");
        $stmt->execute([$id, $client_id]);
        $product = $stmt->fetch();

        if (!$product) jsonResponse(['success' => false, 'error' => 'Product not found'], 404);

        $product['tags'] = json_decode($product['tags'] ?? '[]', true) ?: [];
        $product['screenshots'] = json_decode($product['screenshots'] ?? '[]', true) ?: [];

        jsonResponse(['success' => true, 'product' => $product]);
        break;

    // ─── Create Product ───
    case 'product/create':
        if ($method !== 'POST') jsonResponse(['success' => false, 'error' => 'POST required'], 405);

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) jsonResponse(['success' => false, 'error' => 'Invalid JSON'], 400);

        // Validate fields
        $title = trim($input['title'] ?? '');
        $description = trim($input['description'] ?? '');
        $type = $input['item_type'] ?? '';
        $category = $input['category'] ?? '';
        $price = $input['price'] ?? 0;
        $tags = $input['tags'] ?? [];
        $icon_url = $input['icon_url'] ?? '';
        $screenshots = $input['screenshots'] ?? [];
        $config_json = $input['config_json'] ?? '';

        $errors = [];
        if (strlen($title) < 3 || strlen($title) > 100) $errors[] = 'Title must be 3-100 characters';
        if (strlen($description) < 50) $errors[] = 'Description must be at least 50 characters';
        if (!in_array($type, ['agent', 'tool', 'fleet', 'template', 'integration'])) $errors[] = 'Invalid item type';

        $validCats = ['Business', 'Communication', 'Data', 'Development', 'Finance', 'Health', 'Legal', 'Marketing', 'Productivity', 'Voice'];
        if (!in_array($category, $validCats)) $errors[] = 'Invalid category';

        $price = (float)$price;
        if ($price < 0 || ($price > 0 && $price < 0.99) || $price > 499.99) {
            $errors[] = 'Price must be 0 (free) or $0.99-$499.99';
        }

        if (!is_array($tags)) $tags = [];
        $tags = array_slice(array_map('trim', $tags), 0, 5);

        if (!is_array($screenshots)) $screenshots = [];
        $screenshots = array_slice($screenshots, 0, 5);

        if (!empty($errors)) {
            jsonResponse(['success' => false, 'errors' => $errors], 400);
        }

        $stmt = $db->prepare("
            INSERT INTO alfred_marketplace_items 
            (seller_user_id, item_type, title, description, price, icon_url, screenshots, category, tags, downloads, rating, review_count, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, 0, 'draft', NOW())
        ");
        $stmt->execute([
            $client_id, $type, sanitize($title, 100), $description, 
            number_format($price, 2, '.', ''), sanitize($icon_url, 500),
            json_encode($screenshots), sanitize($category, 50), json_encode($tags)
        ]);

        $productId = $db->lastInsertId();

        jsonResponse(['success' => true, 'product_id' => (int)$productId, 'message' => 'Product created as draft']);
        break;

    // ─── Update Product ───
    case 'product/update':
        if ($method !== 'POST' && $method !== 'PUT') jsonResponse(['success' => false, 'error' => 'POST/PUT required'], 405);

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) jsonResponse(['success' => false, 'error' => 'Invalid JSON'], 400);

        $id = (int)($input['id'] ?? 0);
        if (!$id) jsonResponse(['success' => false, 'error' => 'Product ID required'], 400);

        // Verify ownership
        $stmt = $db->prepare("SELECT * FROM alfred_marketplace_items WHERE id = ? AND seller_user_id = ?");
        $stmt->execute([$id, $client_id]);
        $existing = $stmt->fetch();
        if (!$existing) jsonResponse(['success' => false, 'error' => 'Product not found'], 404);

        // Build update fields
        $updates = [];
        $params = [];

        if (isset($input['title'])) {
            $t = trim($input['title']);
            if (strlen($t) < 3 || strlen($t) > 100) jsonResponse(['success' => false, 'error' => 'Title must be 3-100 chars'], 400);
            $updates[] = 'title = ?';
            $params[] = sanitize($t, 100);
        }
        if (isset($input['description'])) {
            $d = trim($input['description']);
            if (strlen($d) < 50) jsonResponse(['success' => false, 'error' => 'Description must be at least 50 chars'], 400);
            $updates[] = 'description = ?';
            $params[] = $d;
        }
        if (isset($input['item_type'])) {
            if (!in_array($input['item_type'], ['agent', 'tool', 'fleet', 'template', 'integration'])) {
                jsonResponse(['success' => false, 'error' => 'Invalid type'], 400);
            }
            $updates[] = 'item_type = ?';
            $params[] = $input['item_type'];
        }
        if (isset($input['category'])) {
            $validCats = ['Business', 'Communication', 'Data', 'Development', 'Finance', 'Health', 'Legal', 'Marketing', 'Productivity', 'Voice'];
            if (!in_array($input['category'], $validCats)) jsonResponse(['success' => false, 'error' => 'Invalid category'], 400);
            $updates[] = 'category = ?';
            $params[] = $input['category'];
        }
        if (isset($input['price'])) {
            $pr = (float)$input['price'];
            if ($pr < 0 || ($pr > 0 && $pr < 0.99) || $pr > 499.99) {
                jsonResponse(['success' => false, 'error' => 'Price must be 0 or $0.99-$499.99'], 400);
            }
            $updates[] = 'price = ?';
            $params[] = number_format($pr, 2, '.', '');
        }
        if (isset($input['tags'])) {
            $tags = array_slice(array_map('trim', (array)$input['tags']), 0, 5);
            $updates[] = 'tags = ?';
            $params[] = json_encode($tags);
        }
        if (isset($input['icon_url'])) {
            $updates[] = 'icon_url = ?';
            $params[] = sanitize($input['icon_url'], 500);
        }
        if (isset($input['screenshots'])) {
            $ss = array_slice((array)$input['screenshots'], 0, 5);
            $updates[] = 'screenshots = ?';
            $params[] = json_encode($ss);
        }

        if (empty($updates)) jsonResponse(['success' => false, 'error' => 'No fields to update'], 400);

        // If product is published, revert to pending on content change
        if ($existing['status'] === 'published' && (isset($input['title']) || isset($input['description']))) {
            $updates[] = "status = 'pending'";
        }

        $params[] = $id;
        $params[] = $client_id;
        $sql = "UPDATE alfred_marketplace_items SET " . implode(', ', $updates) . " WHERE id = ? AND seller_user_id = ?";
        $db->prepare($sql)->execute($params);

        jsonResponse(['success' => true, 'message' => 'Product updated']);
        break;

    // ─── Submit for Review ───
    case 'product/submit':
        if ($method !== 'POST') jsonResponse(['success' => false, 'error' => 'POST required'], 405);

        $input = json_decode(file_get_contents('php://input'), true);
        $id = (int)($input['id'] ?? 0);
        if (!$id) jsonResponse(['success' => false, 'error' => 'Product ID required'], 400);

        $stmt = $db->prepare("SELECT status FROM alfred_marketplace_items WHERE id = ? AND seller_user_id = ?");
        $stmt->execute([$id, $client_id]);
        $product = $stmt->fetch();

        if (!$product) jsonResponse(['success' => false, 'error' => 'Product not found'], 404);
        if ($product['status'] !== 'draft') jsonResponse(['success' => false, 'error' => 'Only draft products can be submitted'], 400);

        $stmt = $db->prepare("UPDATE alfred_marketplace_items SET status = 'pending' WHERE id = ? AND seller_user_id = ?");
        $stmt->execute([$id, $client_id]);

        jsonResponse(['success' => true, 'message' => 'Product submitted for review']);
        break;

    // ─── Delete (soft) Product ───
    case 'product/delete':
        if ($method !== 'POST' && $method !== 'DELETE') jsonResponse(['success' => false, 'error' => 'POST/DELETE required'], 405);

        $input = json_decode(file_get_contents('php://input'), true);
        $id = (int)($input['id'] ?? 0);
        if (!$id) jsonResponse(['success' => false, 'error' => 'Product ID required'], 400);

        $stmt = $db->prepare("UPDATE alfred_marketplace_items SET status = 'suspended' WHERE id = ? AND seller_user_id = ?");
        $stmt->execute([$id, $client_id]);

        if ($stmt->rowCount() === 0) jsonResponse(['success' => false, 'error' => 'Product not found'], 404);

        jsonResponse(['success' => true, 'message' => 'Product removed']);
        break;

    // ─── Earnings ───
    case 'earnings':
        if ($method !== 'GET') jsonResponse(['success' => false, 'error' => 'GET required'], 405);

        // Per-product earnings
        $stmt = $db->prepare("
            SELECT i.id, i.title, i.item_type, i.price, 
                   COUNT(p.id) as sales,
                   COALESCE(SUM(p.price), 0) as gross_revenue,
                   COALESCE(SUM(p.commission), 0) as platform_commission,
                   COALESCE(SUM(p.seller_earnings), 0) as net_earnings
            FROM alfred_marketplace_items i
            LEFT JOIN alfred_marketplace_purchases p ON p.item_id = i.id AND p.status = 'completed'
            WHERE i.seller_user_id = ? AND i.status != 'suspended'
            GROUP BY i.id
            ORDER BY net_earnings DESC
        ");
        $stmt->execute([$client_id]);
        $byProduct = $stmt->fetchAll();

        // Lifetime totals
        $stmt = $db->prepare("
            SELECT COALESCE(SUM(seller_earnings), 0) as total_earnings,
                   COALESCE(SUM(price), 0) as total_gross,
                   COUNT(*) as total_sales
            FROM alfred_marketplace_purchases WHERE seller_id = ? AND status = 'completed'
        ");
        $stmt->execute([$client_id]);
        $lifetime = $stmt->fetch();

        // Already paid out
        $stmt = $db->prepare("
            SELECT COALESCE(SUM(amount), 0) FROM alfred_marketplace_payouts 
            WHERE seller_id = ? AND status IN ('completed', 'processing', 'pending')
        ");
        $stmt->execute([$client_id]);
        $paidOut = $stmt->fetchColumn();

        $currentBalance = bcsub($lifetime['total_earnings'], $paidOut, 2);

        // Payout history
        $stmt = $db->prepare("
            SELECT * FROM alfred_marketplace_payouts WHERE seller_id = ? ORDER BY requested_at DESC LIMIT 20
        ");
        $stmt->execute([$client_id]);
        $payouts = $stmt->fetchAll();

        jsonResponse([
            'success' => true,
            'by_product' => $byProduct,
            'lifetime' => [
                'total_earnings' => $lifetime['total_earnings'],
                'total_gross' => $lifetime['total_gross'],
                'total_sales' => (int)$lifetime['total_sales'],
                'paid_out' => $paidOut,
                'current_balance' => $currentBalance
            ],
            'payout_history' => $payouts,
            'commission_rate' => '70/30',
            'min_payout' => '25.00'
        ]);
        break;

    // ─── Request Payout ───
    case 'payout/request':
        if ($method !== 'POST') jsonResponse(['success' => false, 'error' => 'POST required'], 405);

        // Calculate current balance
        $stmt = $db->prepare("SELECT COALESCE(SUM(seller_earnings), 0) FROM alfred_marketplace_purchases WHERE seller_id = ? AND status = 'completed'");
        $stmt->execute([$client_id]);
        $totalEarned = $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM alfred_marketplace_payouts WHERE seller_id = ? AND status IN ('completed', 'processing', 'pending')");
        $stmt->execute([$client_id]);
        $totalPaid = $stmt->fetchColumn();

        $balance = bcsub($totalEarned, $totalPaid, 2);

        if (bccomp($balance, '25.00', 2) < 0) {
            jsonResponse(['success' => false, 'error' => 'Minimum payout is $25.00. Current balance: $' . $balance], 400);
        }

        // Check for pending payouts
        $stmt = $db->prepare("SELECT COUNT(*) FROM alfred_marketplace_payouts WHERE seller_id = ? AND status = 'pending'");
        $stmt->execute([$client_id]);
        if ($stmt->fetchColumn() > 0) {
            jsonResponse(['success' => false, 'error' => 'You already have a pending payout request'], 400);
        }

        $stmt = $db->prepare("INSERT INTO alfred_marketplace_payouts (seller_id, amount, status) VALUES (?, ?, 'pending')");
        $stmt->execute([$client_id, $balance]);

        jsonResponse(['success' => true, 'message' => 'Payout of $' . $balance . ' requested', 'payout_id' => (int)$db->lastInsertId()]);
        break;

    // ─── Reviews ───
    case 'reviews':
        if ($method !== 'GET') jsonResponse(['success' => false, 'error' => 'GET required'], 405);

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        $ratingFilter = (int)($_GET['rating'] ?? 0);

        $where = "i.seller_user_id = ?";
        $params = [$client_id];

        if ($ratingFilter >= 1 && $ratingFilter <= 5) {
            $where .= " AND r.rating = ?";
            $params[] = $ratingFilter;
        }

        $stmt = $db->prepare("SELECT COUNT(*) FROM alfred_marketplace_reviews r JOIN alfred_marketplace_items i ON r.item_id = i.id WHERE $where");
        dbExecute($stmt, $params);
        $total = (int)$stmt->fetchColumn();

        $params[] = $limit;
        $params[] = $offset;
        $stmt = $db->prepare("
            SELECT r.*, i.title as product_title, i.item_type
            FROM alfred_marketplace_reviews r
            JOIN alfred_marketplace_items i ON r.item_id = i.id
            WHERE $where
            ORDER BY r.created_at DESC LIMIT ? OFFSET ?
        ");
        dbExecute($stmt, $params);
        $reviews = $stmt->fetchAll();

        jsonResponse([
            'success' => true,
            'reviews' => $reviews,
            'total' => $total,
            'page' => $page,
            'pages' => ceil($total / $limit)
        ]);
        break;

    // ─── Respond to Review ───
    case 'reviews/respond':
        if ($method !== 'POST') jsonResponse(['success' => false, 'error' => 'POST required'], 405);

        $input = json_decode(file_get_contents('php://input'), true);
        $reviewId = (int)($input['review_id'] ?? 0);
        $response = trim($input['response'] ?? '');

        if (!$reviewId) jsonResponse(['success' => false, 'error' => 'Review ID required'], 400);
        if (strlen($response) < 5 || strlen($response) > 2000) {
            jsonResponse(['success' => false, 'error' => 'Response must be 5-2000 characters'], 400);
        }

        // Verify the review belongs to one of the creator's products
        $stmt = $db->prepare("
            SELECT r.id FROM alfred_marketplace_reviews r
            JOIN alfred_marketplace_items i ON r.item_id = i.id
            WHERE r.id = ? AND i.seller_user_id = ?
        ");
        $stmt->execute([$reviewId, $client_id]);
        if (!$stmt->fetch()) {
            jsonResponse(['success' => false, 'error' => 'Review not found or not yours'], 404);
        }

        $stmt = $db->prepare("UPDATE alfred_marketplace_reviews SET seller_response = ?, seller_responded_at = NOW() WHERE id = ?");
        $stmt->execute([$response, $reviewId]);

        jsonResponse(['success' => true, 'message' => 'Response posted']);
        break;

    // ─── Analytics ───
    case 'analytics':
        if ($method !== 'GET') jsonResponse(['success' => false, 'error' => 'GET required'], 405);

        $productId = (int)($_GET['product_id'] ?? 0);

        if ($productId) {
            // Verify ownership
            $stmt = $db->prepare("SELECT id, title FROM alfred_marketplace_items WHERE id = ? AND seller_user_id = ?");
            $stmt->execute([$productId, $client_id]);
            $prod = $stmt->fetch();
            if (!$prod) jsonResponse(['success' => false, 'error' => 'Product not found'], 404);

            // Downloads over time (last 30 days)
            $stmt = $db->prepare("
                SELECT DATE(created_at) as day, COUNT(*) as downloads, SUM(seller_earnings) as earnings
                FROM alfred_marketplace_purchases
                WHERE item_id = ? AND seller_id = ? AND status = 'completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(created_at) ORDER BY day
            ");
            $stmt->execute([$productId, $client_id]);
            $dailyData = $stmt->fetchAll();

            // Total stats for this product
            $stmt = $db->prepare("
                SELECT COUNT(*) as total_sales, COALESCE(SUM(seller_earnings), 0) as total_earnings
                FROM alfred_marketplace_purchases
                WHERE item_id = ? AND seller_id = ? AND status = 'completed'
            ");
            $stmt->execute([$productId, $client_id]);
            $totals = $stmt->fetch();

            // Review breakdown
            $stmt = $db->prepare("
                SELECT rating, COUNT(*) as count FROM alfred_marketplace_reviews
                WHERE item_id = ? GROUP BY rating ORDER BY rating DESC
            ");
            $stmt->execute([$productId]);
            $ratingBreakdown = $stmt->fetchAll();

            jsonResponse([
                'success' => true,
                'product' => $prod,
                'daily' => $dailyData,
                'totals' => $totals,
                'rating_breakdown' => $ratingBreakdown
            ]);
        } else {
            // Global analytics for all products
            $stmt = $db->prepare("
                SELECT DATE(p.created_at) as day, COUNT(*) as sales, SUM(p.seller_earnings) as earnings
                FROM alfred_marketplace_purchases p
                WHERE p.seller_id = ? AND p.status = 'completed' AND p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(p.created_at) ORDER BY day
            ");
            $stmt->execute([$client_id]);
            $dailyData = $stmt->fetchAll();

            // Top products
            $stmt = $db->prepare("
                SELECT i.id, i.title, i.item_type, COUNT(p.id) as sales, COALESCE(SUM(p.seller_earnings), 0) as earnings
                FROM alfred_marketplace_items i
                LEFT JOIN alfred_marketplace_purchases p ON p.item_id = i.id AND p.status = 'completed'
                WHERE i.seller_user_id = ? AND i.status != 'suspended'
                GROUP BY i.id ORDER BY earnings DESC LIMIT 10
            ");
            $stmt->execute([$client_id]);
            $topProducts = $stmt->fetchAll();

            jsonResponse([
                'success' => true,
                'daily' => $dailyData,
                'top_products' => $topProducts
            ]);
        }
        break;

    default:
        jsonResponse(['success' => false, 'error' => 'Unknown action: ' . $action], 400);
}
