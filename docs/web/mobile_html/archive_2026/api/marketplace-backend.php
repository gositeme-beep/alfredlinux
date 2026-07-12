<?php
/**
 * Marketplace Backend API — Tool install/uninstall, ratings, reviews, search, categories
 * Works alongside marketplace-creator.php (seller-side). This is the buyer/consumer side.
 * Uses existing marketplace_agents, marketplace_installs, marketplace_ratings tables + alfred_marketplace_* tables.
 */
define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Internal-Secret');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

function mktIsInternal(): bool {
    $secret = defined('INTERNAL_SECRET') ? INTERNAL_SECRET : '';
    return $secret && isset($_SERVER['HTTP_X_INTERNAL_SECRET']) && hash_equals($secret, $_SERVER['HTTP_X_INTERNAL_SECRET']);
}
function mktRequireAuth(): void {
    if (mktIsInternal()) return;
    if (empty($_SESSION['logged_in']) || empty($_SESSION['client_id'])) {
        jsonResponse(['error' => 'Authentication required'], 401);
require_once dirname(__DIR__) . '/includes/api-security.php';
    }
}
function mktGetClientId(): int {
    if (mktIsInternal()) {
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        return (int) ($body['client_id'] ?? $_SESSION['client_id'] ?? 0);
    }
    return (int) ($_SESSION['client_id'] ?? 0);
}

// ─── Schema Extensions ───────────────────────────────────────
function ensureMarketplaceSchema(): void {
    $db = getDB();
    try {

    // Reviews table (extends basic marketplace_ratings)
    $db->exec("CREATE TABLE IF NOT EXISTS marketplace_reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        agent_id VARCHAR(64) NOT NULL,
        rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
        title VARCHAR(128),
        review TEXT,
        helpful_count INT DEFAULT 0,
        seller_response TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_client_agent (client_id, agent_id),
        INDEX idx_agent (agent_id)
    )");

    // Wishlists
    $db->exec("CREATE TABLE IF NOT EXISTS marketplace_wishlist (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        agent_id VARCHAR(64) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_client_agent (client_id, agent_id),
        INDEX idx_client (client_id)
    )");

    // Categories
    $db->exec("CREATE TABLE IF NOT EXISTS marketplace_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(50) UNIQUE NOT NULL,
        name VARCHAR(100) NOT NULL,
        description VARCHAR(255),
        icon VARCHAR(10),
        sort_order INT DEFAULT 0,
        agent_count INT DEFAULT 0
    )");

    // Seed categories
    $count = (int) $db->query("SELECT COUNT(*) FROM marketplace_categories")->fetchColumn();
    if ($count === 0) {
        $db->exec("INSERT INTO marketplace_categories (slug, name, description, icon, sort_order) VALUES
            ('productivity', 'Productivity', 'Boost your workflow and automate tasks', '⚡', 1),
            ('finance', 'Finance & Accounting', 'Financial tools, invoicing, and bookkeeping', '💰', 2),
            ('marketing', 'Marketing & SEO', 'Content creation, SEO, and campaign tools', '📊', 3),
            ('development', 'Development', 'Code generation, debugging, and DevOps', '💻', 4),
            ('writing', 'Writing & Content', 'Blog posts, copywriting, and editing', '✍️', 5),
            ('customer-service', 'Customer Service', 'Support bots, ticket handling, and chat', '🎧', 6),
            ('data', 'Data & Analytics', 'Data processing, visualization, and insights', '📈', 7),
            ('voice', 'Voice & Communication', 'Voice AI, calls, and conferencing', '🎙️', 8),
            ('healthcare', 'Healthcare', 'Medical documentation and patient tools', '🏥', 9),
            ('legal', 'Legal & Compliance', 'Contract review, compliance, and legal docs', '⚖️', 10),
            ('education', 'Education', 'Tutoring, course creation, and learning', '📚', 11),
            ('creative', 'Creative & Design', 'Image generation, design, and creative tools', '🎨', 12)
        ");
    }
    } catch (PDOException $e) {
        error_log('Marketplace schema error: ' . $e->getMessage());
    }
}
ensureMarketplaceSchema();

$action = sanitize($_GET['action'] ?? $_POST['action'] ?? '', 50);

switch ($action) {
    case 'browse':           browse(); break;
    case 'search':           search(); break;
    case 'categories':       listCategories(); break;
    case 'detail':           agentDetail(); break;
    case 'featured':         featured(); break;
    case 'trending':         trending(); break;
    case 'install':          mktRequireAuth(); installAgent(); break;
    case 'uninstall':        mktRequireAuth(); uninstallAgent(); break;
    case 'my_installs':      mktRequireAuth(); myInstalls(); break;
    case 'rate':             mktRequireAuth(); rateAgent(); break;
    case 'review':           mktRequireAuth(); reviewAgent(); break;
    case 'reviews':          agentReviews(); break;
    case 'wishlist_add':     mktRequireAuth(); wishlistAdd(); break;
    case 'wishlist_remove':  mktRequireAuth(); wishlistRemove(); break;
    case 'my_wishlist':      mktRequireAuth(); myWishlist(); break;
    case 'stats':            marketplaceStats(); break;
    default: jsonResponse(['error' => 'Unknown action', 'actions' => [
        'browse','search','categories','detail','featured','trending',
        'install','uninstall','my_installs','rate','review','reviews',
        'wishlist_add','wishlist_remove','my_wishlist','stats'
    ]], 400);
}

// ─── Helper: choose best agents table ─────────────────────────
function agentTable(): string {
    $db = getDB();
    // Prefer alfred_marketplace_items if it exists (richer schema)
    $stmt = $db->query("SHOW TABLES LIKE 'alfred_marketplace_items'");
    if ($stmt->rowCount() > 0) return 'alfred_marketplace_items';
    return 'marketplace_agents';
}

// ─── Browse ───────────────────────────────────────────────────
function browse(): void {
    $db = getDB();
    $input = $_GET;
    $category = sanitize($input['category'] ?? '', 50);
    $page = max(1, (int) ($input['page'] ?? 1));
    $limit = min(50, max(10, (int) ($input['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    $sort = in_array($input['sort'] ?? '', ['popular', 'newest', 'rating', 'name']) ? $input['sort'] : 'popular';

    $table = agentTable();
    $where = $table === 'alfred_marketplace_items' ? "status = 'published'" : "status = 'published'";
    $params = [];

    if ($category) {
        $where .= " AND category = ?";
        $params[] = $category;
    }

    $orderBy = match($sort) {
        'popular' => 'installs DESC',
        'newest' => 'created_at DESC',
        'rating' => 'stars DESC',
        'name' => 'name ASC',
        default => 'installs DESC',
    };

    // Map columns based on table
    $nameCol = $table === 'alfred_marketplace_items' ? 'title as name' : 'name';
    $selectCols = $table === 'alfred_marketplace_items'
        ? "id, title as name, description, category, price, seller_user_id as author_id, icon_emoji as icon, stars, downloads as installs, featured, status, created_at"
        : "id, agent_id, name, description, category, price, author_id, icon, color, stars, installs, featured, status, created_at";
    $installsCol = $table === 'alfred_marketplace_items' ? 'downloads' : 'installs';

    $orderBy = str_replace('installs', $installsCol, $orderBy);

    $stmt = $db->prepare("SELECT $selectCols FROM $table WHERE $where ORDER BY $orderBy LIMIT ? OFFSET ?");
    $params[] = $limit;
    $params[] = $offset;
    dbExecute($stmt, $params);
    $agents = $stmt->fetchAll();

    // Total count
    $countStmt = $db->prepare("SELECT COUNT(*) FROM $table WHERE " . ($category ? "status = 'published' AND category = ?" : "status = 'published'"));
    $countStmt->execute($category ? [$category] : []);
    $total = (int) $countStmt->fetchColumn();

    jsonResponse([
        'success' => true,
        'agents' => $agents,
        'pagination' => ['page' => $page, 'limit' => $limit, 'total' => $total, 'pages' => ceil($total / $limit)],
    ]);
}

// ─── Search ───────────────────────────────────────────────────
function search(): void {
    $db = getDB();
    $q = sanitize($_GET['q'] ?? '', 100);
    if (strlen($q) < 2) jsonResponse(['error' => 'Query too short (min 2 chars)'], 400);

    $table = agentTable();
    $nameCol = $table === 'alfred_marketplace_items' ? 'title' : 'name';
    $selectCols = $table === 'alfred_marketplace_items'
        ? "id, title as name, description, category, price, stars, downloads as installs, icon_emoji as icon"
        : "id, agent_id, name, description, category, price, stars, installs, icon";

    $stmt = $db->prepare("SELECT $selectCols FROM $table WHERE status = 'published' AND ($nameCol LIKE ? OR description LIKE ?) ORDER BY stars DESC LIMIT 30");
    $like = "%$q%";
    $stmt->execute([$like, $like]);

    jsonResponse(['success' => true, 'query' => $q, 'results' => $stmt->fetchAll()]);
}

// ─── Categories ───────────────────────────────────────────────
function listCategories(): void {
    $db = getDB();
    $categories = $db->query("SELECT slug, name, description, icon, agent_count FROM marketplace_categories ORDER BY sort_order")->fetchAll();
    jsonResponse(['success' => true, 'categories' => $categories]);
}

// ─── Detail ───────────────────────────────────────────────────
function agentDetail(): void {
    $db = getDB();
    $id = sanitize($_GET['id'] ?? '', 64);
    if (!$id) jsonResponse(['error' => 'id required'], 400);

    $table = agentTable();
    $selectCols = $table === 'alfred_marketplace_items'
        ? "id, title as name, description, category, price, seller_user_id as author_id, stars, downloads as installs, featured, status, created_at"
        : "id, agent_id, name, description, category, price, author_id, icon, color, stars, installs, featured, status, created_at";

    $stmt = $db->prepare("SELECT $selectCols FROM $table WHERE id = ? OR " . ($table === 'alfred_marketplace_items' ? 'id' : 'agent_id') . " = ?");
    $stmt->execute([$id, $id]);
    $agent = $stmt->fetch();

    if (!$agent) jsonResponse(['error' => 'Agent not found'], 404);

    // Get reviews
    $stmt = $db->prepare("SELECT rating, title, review, created_at FROM marketplace_reviews WHERE agent_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$id]);
    $reviews = $stmt->fetchAll();

    // Install count
    $stmt = $db->prepare("SELECT COUNT(*) FROM marketplace_installs WHERE agent_id = ?");
    $stmt->execute([$id]);
    $installCount = (int) $stmt->fetchColumn();

    $agent['reviews'] = $reviews;
    $agent['install_count'] = $installCount;

    jsonResponse(['success' => true, 'agent' => $agent]);
}

// ─── Featured ─────────────────────────────────────────────────
function featured(): void {
    $db = getDB();
    $table = agentTable();
    $selectCols = $table === 'alfred_marketplace_items'
        ? "id, title as name, description, category, price, stars, downloads as installs, icon_emoji as icon"
        : "id, agent_id, name, description, category, price, stars, installs, icon";

    $stmt = $db->query("SELECT $selectCols FROM $table WHERE featured = 1 AND status = 'published' ORDER BY stars DESC LIMIT 12");
    jsonResponse(['success' => true, 'featured' => $stmt->fetchAll()]);
}

// ─── Trending ─────────────────────────────────────────────────
function trending(): void {
    $db = getDB();
    // Trending = most installed in last 7 days
    $stmt = $db->query("SELECT agent_id, COUNT(*) as recent_installs FROM marketplace_installs WHERE installed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY agent_id ORDER BY recent_installs DESC LIMIT 12");
    $trending = $stmt->fetchAll();

    jsonResponse(['success' => true, 'trending' => $trending]);
}

// ─── Install ──────────────────────────────────────────────────
function installAgent(): void {
    $db = getDB();
    $clientId = mktGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $agentId = sanitize($input['agent_id'] ?? '', 64);

    if (!$agentId) jsonResponse(['error' => 'agent_id required'], 400);

    // Check not already installed
    $stmt = $db->prepare("SELECT id FROM marketplace_installs WHERE client_id = ? AND agent_id = ?");
    $stmt->execute([$clientId, $agentId]);
    if ($stmt->fetch()) jsonResponse(['error' => 'Already installed'], 400);

    // Install
    $db->prepare("INSERT INTO marketplace_installs (client_id, agent_id) VALUES (?, ?)")->execute([$clientId, $agentId]);

    // Increment install count
    $table = agentTable();
    $installCol = $table === 'alfred_marketplace_items' ? 'downloads' : 'installs';
    $idCol = $table === 'alfred_marketplace_items' ? 'id' : 'agent_id';
    $db->prepare("UPDATE $table SET $installCol = $installCol + 1 WHERE $idCol = ?")->execute([$agentId]);

    jsonResponse(['success' => true, 'message' => 'Agent installed']);
}

// ─── Uninstall ────────────────────────────────────────────────
function uninstallAgent(): void {
    $db = getDB();
    $clientId = mktGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $agentId = sanitize($input['agent_id'] ?? '', 64);

    if (!$agentId) jsonResponse(['error' => 'agent_id required'], 400);

    $stmt = $db->prepare("DELETE FROM marketplace_installs WHERE client_id = ? AND agent_id = ?");
    $stmt->execute([$clientId, $agentId]);

    if ($stmt->rowCount() > 0) {
        $table = agentTable();
        $installCol = $table === 'alfred_marketplace_items' ? 'downloads' : 'installs';
        $idCol = $table === 'alfred_marketplace_items' ? 'id' : 'agent_id';
        $db->prepare("UPDATE $table SET $installCol = GREATEST($installCol - 1, 0) WHERE $idCol = ?")->execute([$agentId]);
    }

    jsonResponse(['success' => true, 'message' => 'Agent uninstalled']);
}

// ─── My Installs ──────────────────────────────────────────────
function myInstalls(): void {
    $db = getDB();
    $clientId = mktGetClientId();

    $table = agentTable();
    $nameCol = $table === 'alfred_marketplace_items' ? 'a.title as name' : 'a.name';

    if ($table === 'alfred_marketplace_items') {
        $stmt = $db->prepare("SELECT i.agent_id, a.title as name, a.description, a.category, i.installed_at FROM marketplace_installs i LEFT JOIN $table a ON i.agent_id = a.id WHERE i.client_id = ? ORDER BY i.installed_at DESC");
    } else {
        $stmt = $db->prepare("SELECT i.agent_id, a.name, a.description, a.category, a.icon, i.installed_at FROM marketplace_installs i LEFT JOIN $table a ON i.agent_id = a.agent_id WHERE i.client_id = ? ORDER BY i.installed_at DESC");
    }
    $stmt->execute([$clientId]);

    jsonResponse(['success' => true, 'installs' => $stmt->fetchAll()]);
}

// ─── Rate ─────────────────────────────────────────────────────
function rateAgent(): void {
    $db = getDB();
    $clientId = mktGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $agentId = sanitize($input['agent_id'] ?? '', 64);
    $rating = max(1, min(5, (int) ($input['rating'] ?? 0)));

    if (!$agentId || !$rating) jsonResponse(['error' => 'agent_id and rating (1-5) required'], 400);

    $db->prepare("INSERT INTO marketplace_ratings (client_id, agent_id, rating) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE rating = VALUES(rating)")
        ->execute([$clientId, $agentId, $rating]);

    // Recalculate average stars
    $stmt = $db->prepare("SELECT AVG(rating) as avg_rating FROM marketplace_ratings WHERE agent_id = ?");
    $stmt->execute([$agentId]);
    $avg = round((float) $stmt->fetchColumn(), 1);

    $table = agentTable();
    $idCol = $table === 'alfred_marketplace_items' ? 'id' : 'agent_id';
    $db->prepare("UPDATE $table SET stars = ? WHERE $idCol = ?")->execute([$avg, $agentId]);

    jsonResponse(['success' => true, 'new_average' => $avg]);
}

// ─── Review ───────────────────────────────────────────────────
function reviewAgent(): void {
    $db = getDB();
    $clientId = mktGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $agentId = sanitize($input['agent_id'] ?? '', 64);
    $rating = max(1, min(5, (int) ($input['rating'] ?? 0)));
    $title = sanitize($input['title'] ?? '', 128);
    $review = sanitize($input['review'] ?? '', 2000);

    if (!$agentId || !$rating) jsonResponse(['error' => 'agent_id and rating required'], 400);

    $db->prepare("INSERT INTO marketplace_reviews (client_id, agent_id, rating, title, review) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE rating = VALUES(rating), title = VALUES(title), review = VALUES(review)")
        ->execute([$clientId, $agentId, $rating, $title, $review]);

    // Also update marketplace_ratings
    $db->prepare("INSERT INTO marketplace_ratings (client_id, agent_id, rating) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE rating = VALUES(rating)")
        ->execute([$clientId, $agentId, $rating]);

    jsonResponse(['success' => true, 'message' => 'Review submitted']);
}

// ─── Reviews for an agent ─────────────────────────────────────
function agentReviews(): void {
    $db = getDB();
    $agentId = sanitize($_GET['agent_id'] ?? '', 64);
    if (!$agentId) jsonResponse(['error' => 'agent_id required'], 400);

    $stmt = $db->prepare("SELECT rating, title, review, helpful_count, seller_response, created_at FROM marketplace_reviews WHERE agent_id = ? ORDER BY created_at DESC LIMIT 50");
    $stmt->execute([$agentId]);
    $reviews = $stmt->fetchAll();

    $stmt = $db->prepare("SELECT AVG(rating) as avg, COUNT(*) as total FROM marketplace_reviews WHERE agent_id = ?");
    $stmt->execute([$agentId]);
    $summary = $stmt->fetch();

    jsonResponse(['success' => true, 'reviews' => $reviews, 'summary' => $summary]);
}

// ─── Wishlist ─────────────────────────────────────────────────
function wishlistAdd(): void {
    $db = getDB();
    $clientId = mktGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $agentId = sanitize($input['agent_id'] ?? '', 64);
    if (!$agentId) jsonResponse(['error' => 'agent_id required'], 400);

    $db->prepare("INSERT IGNORE INTO marketplace_wishlist (client_id, agent_id) VALUES (?, ?)")->execute([$clientId, $agentId]);
    jsonResponse(['success' => true, 'message' => 'Added to wishlist']);
}

function wishlistRemove(): void {
    $db = getDB();
    $clientId = mktGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $agentId = sanitize($input['agent_id'] ?? '', 64);
    if (!$agentId) jsonResponse(['error' => 'agent_id required'], 400);

    $db->prepare("DELETE FROM marketplace_wishlist WHERE client_id = ? AND agent_id = ?")->execute([$clientId, $agentId]);
    jsonResponse(['success' => true, 'message' => 'Removed from wishlist']);
}

function myWishlist(): void {
    $db = getDB();
    $clientId = mktGetClientId();

    $table = agentTable();
    if ($table === 'alfred_marketplace_items') {
        $stmt = $db->prepare("SELECT w.agent_id, a.title as name, a.description, a.category, a.stars, w.created_at FROM marketplace_wishlist w LEFT JOIN $table a ON w.agent_id = a.id WHERE w.client_id = ? ORDER BY w.created_at DESC");
    } else {
        $stmt = $db->prepare("SELECT w.agent_id, a.name, a.description, a.category, a.stars, a.icon, w.created_at FROM marketplace_wishlist w LEFT JOIN $table a ON w.agent_id = a.agent_id WHERE w.client_id = ? ORDER BY w.created_at DESC");
    }
    $stmt->execute([$clientId]);

    jsonResponse(['success' => true, 'wishlist' => $stmt->fetchAll()]);
}

// ─── Stats ────────────────────────────────────────────────────
function marketplaceStats(): void {
    $db = getDB();
    $table = agentTable();

    $totalAgents = (int) $db->query("SELECT COUNT(*) FROM $table WHERE status = 'published'")->fetchColumn();
    $totalInstalls = (int) $db->query("SELECT COUNT(*) FROM marketplace_installs")->fetchColumn();
    $totalReviews = (int) $db->query("SELECT COUNT(*) FROM marketplace_reviews")->fetchColumn();
    $avgRating = round((float) $db->query("SELECT COALESCE(AVG(rating), 0) FROM marketplace_ratings")->fetchColumn(), 1);

    $topCategory = $db->query("SELECT category, COUNT(*) as count FROM $table WHERE status = 'published' GROUP BY category ORDER BY count DESC LIMIT 1")->fetch();

    jsonResponse([
        'success' => true,
        'stats' => [
            'total_agents' => $totalAgents,
            'total_installs' => $totalInstalls,
            'total_reviews' => $totalReviews,
            'average_rating' => $avgRating,
            'top_category' => $topCategory ? $topCategory['category'] : 'none',
        ],
    ]);
}
