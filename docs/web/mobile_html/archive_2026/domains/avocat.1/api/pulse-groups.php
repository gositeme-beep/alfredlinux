<?php
/**
 * Pulse Groups & Communities API
 * Endpoints for creating, joining, managing community groups on Pulse Network
 */
define('GOSITEME_API', true);
require_once dirname(__DIR__) . '/api/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

session_start();

function groupRequireAuth() {
    if (empty($_SESSION['logged_in']) || empty($_SESSION['client_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
require_once dirname(__DIR__) . '/includes/api-security.php';
        exit;
    }
    return (int) $_SESSION['client_id'];
}

function groupOptionalAuth() {
    return (!empty($_SESSION['logged_in']) && !empty($_SESSION['client_id'])) ? (int) $_SESSION['client_id'] : null;
}

// Ensure schema
function ensureGroupSchema() {
    global $pdo;

    $pdo->exec("CREATE TABLE IF NOT EXISTS pulse_groups (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        slug VARCHAR(120) NOT NULL UNIQUE,
        description TEXT,
        category VARCHAR(50) DEFAULT 'general',
        icon VARCHAR(50) DEFAULT 'fas fa-users',
        cover_color VARCHAR(20) DEFAULT '#6c5ce7',
        visibility ENUM('public','private','hidden') DEFAULT 'public',
        owner_id INT UNSIGNED NOT NULL,
        member_count INT UNSIGNED DEFAULT 1,
        post_count INT UNSIGNED DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_category (category),
        INDEX idx_visibility (visibility),
        INDEX idx_owner (owner_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS pulse_group_members (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        group_id BIGINT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        role ENUM('member','moderator','admin','owner') DEFAULT 'member',
        joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_group_user (group_id, user_id),
        INDEX idx_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS pulse_group_posts (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        group_id BIGINT UNSIGNED NOT NULL,
        post_id BIGINT UNSIGNED NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_group_post (group_id, post_id),
        INDEX idx_group (group_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

try {
    $pdo = new PDO(
        'mysql:host=' . GOSITEME_DB_HOST . ';dbname=' . GOSITEME_DB_NAME . ';charset=utf8mb4',
        GOSITEME_DB_USER, GOSITEME_DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    ensureGroupSchema();
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($action) {

    // Create a new group
    case 'create':
        $uid = groupRequireAuth();
        $name = trim($input['name'] ?? '');
        $description = trim($input['description'] ?? '');
        $category = trim($input['category'] ?? 'general');
        $icon = trim($input['icon'] ?? 'fas fa-users');
        $visibility = in_array($input['visibility'] ?? '', ['public', 'private', 'hidden']) ? $input['visibility'] : 'public';

        if (strlen($name) < 2 || strlen($name) > 100) {
            http_response_code(400);
            echo json_encode(['error' => 'Group name must be 2-100 characters']);
            exit;
        }

        $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($name));
        $slug = trim($slug, '-');

        // Check uniqueness
        $check = $pdo->prepare("SELECT id FROM pulse_groups WHERE slug = ?");
        $check->execute([$slug]);
        if ($check->fetch()) {
            $slug .= '-' . substr(uniqid(), -4);
        }

        $stmt = $pdo->prepare("INSERT INTO pulse_groups (name, slug, description, category, icon, cover_color, visibility, owner_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $coverColor = $input['cover_color'] ?? '#6c5ce7';
        $stmt->execute([$name, $slug, $description, $category, $icon, $coverColor, $visibility, $uid]);
        $groupId = $pdo->lastInsertId();

        // Owner auto-joins
        $pdo->prepare("INSERT INTO pulse_group_members (group_id, user_id, role) VALUES (?, ?, 'owner')")->execute([$groupId, $uid]);

        echo json_encode(['success' => true, 'group' => ['id' => $groupId, 'name' => $name, 'slug' => $slug]]);
        break;

    // List groups (browse/discover)
    case 'list':
        $uid = groupOptionalAuth();
        $category = $_GET['category'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $where = "WHERE g.visibility = 'public'";
        $params = [];
        if ($category) {
            $where .= " AND g.category = ?";
            $params[] = $category;
        }

        $params[] = $limit;
        $params[] = $offset;

        $sql = "SELECT g.*, c.firstname AS owner_name" .
            ($uid ? ", (SELECT 1 FROM pulse_group_members m WHERE m.group_id = g.id AND m.user_id = $uid LIMIT 1) AS is_member" : ", 0 AS is_member") .
            " FROM pulse_groups g LEFT JOIN clients c ON g.owner_id = c.id $where ORDER BY g.member_count DESC, g.created_at DESC LIMIT ? OFFSET ?";
        $stmt = $pdo->prepare($sql);
        dbExecute($stmt, $params);
        echo json_encode(['groups' => $stmt->fetchAll()]);
        break;

    // Get group details
    case 'get':
        $uid = groupOptionalAuth();
        $groupId = (int)($_GET['id'] ?? 0);
        $slug = $_GET['slug'] ?? '';

        if ($groupId) {
            $stmt = $pdo->prepare("SELECT g.*, c.firstname AS owner_name FROM pulse_groups g LEFT JOIN clients c ON g.owner_id = c.id WHERE g.id = ?");
            $stmt->execute([$groupId]);
        } else {
            $stmt = $pdo->prepare("SELECT g.*, c.firstname AS owner_name FROM pulse_groups g LEFT JOIN clients c ON g.owner_id = c.id WHERE g.slug = ?");
            $stmt->execute([$slug]);
        }
        $group = $stmt->fetch();
        if (!$group) { http_response_code(404); echo json_encode(['error' => 'Group not found']); exit; }

        // Check if hidden
        if ($group['visibility'] === 'hidden' && (!$uid || $group['owner_id'] != $uid)) {
            $memCheck = $pdo->prepare("SELECT 1 FROM pulse_group_members WHERE group_id = ? AND user_id = ?");
            $memCheck->execute([$group['id'], $uid ?? 0]);
            if (!$memCheck->fetch()) { http_response_code(404); echo json_encode(['error' => 'Group not found']); exit; }
        }

        if ($uid) {
            $memStmt = $pdo->prepare("SELECT role FROM pulse_group_members WHERE group_id = ? AND user_id = ?");
            $memStmt->execute([$group['id'], $uid]);
            $mem = $memStmt->fetch();
            $group['is_member'] = $mem ? 1 : 0;
            $group['role'] = $mem ? $mem['role'] : null;
        }

        echo json_encode(['group' => $group]);
        break;

    // Join a group
    case 'join':
        $uid = groupRequireAuth();
        $groupId = (int)($input['group_id'] ?? 0);

        $grp = $pdo->prepare("SELECT id, visibility FROM pulse_groups WHERE id = ?");
        $grp->execute([$groupId]);
        $group = $grp->fetch();
        if (!$group) { http_response_code(404); echo json_encode(['error' => 'Group not found']); exit; }
        if ($group['visibility'] === 'hidden') { http_response_code(403); echo json_encode(['error' => 'This group is invite-only']); exit; }

        $stmt = $pdo->prepare("INSERT IGNORE INTO pulse_group_members (group_id, user_id, role) VALUES (?, ?, 'member')");
        $stmt->execute([$groupId, $uid]);
        if ($stmt->rowCount() > 0) {
            $pdo->prepare("UPDATE pulse_groups SET member_count = member_count + 1 WHERE id = ?")->execute([$groupId]);
        }
        echo json_encode(['success' => true]);
        break;

    // Leave a group
    case 'leave':
        $uid = groupRequireAuth();
        $groupId = (int)($input['group_id'] ?? 0);

        // Can't leave if owner
        $check = $pdo->prepare("SELECT role FROM pulse_group_members WHERE group_id = ? AND user_id = ?");
        $check->execute([$groupId, $uid]);
        $mem = $check->fetch();
        if ($mem && $mem['role'] === 'owner') {
            http_response_code(400);
            echo json_encode(['error' => 'Owners cannot leave. Transfer ownership first.']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM pulse_group_members WHERE group_id = ? AND user_id = ?");
        $stmt->execute([$groupId, $uid]);
        if ($stmt->rowCount() > 0) {
            $pdo->prepare("UPDATE pulse_groups SET member_count = GREATEST(member_count - 1, 0) WHERE id = ?")->execute([$groupId]);
        }
        echo json_encode(['success' => true]);
        break;

    // Group feed (posts in this group)
    case 'feed':
        $uid = groupOptionalAuth();
        $groupId = (int)($_GET['group_id'] ?? 0);
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $stmt = $pdo->prepare("SELECT p.*, c.firstname AS author_name
            FROM pulse_group_posts gp
            JOIN pulse_posts p ON gp.post_id = p.id
            LEFT JOIN clients c ON p.user_id = c.id
            WHERE gp.group_id = ?
            ORDER BY p.created_at DESC LIMIT ? OFFSET ?");
        dbExecute($stmt, [$groupId, $limit, $offset]);
        echo json_encode(['posts' => $stmt->fetchAll()]);
        break;

    // Post to a group
    case 'post':
        $uid = groupRequireAuth();
        $groupId = (int)($input['group_id'] ?? 0);
        $content = trim($input['content'] ?? '');

        if (!$content || strlen($content) > 5000) {
            http_response_code(400);
            echo json_encode(['error' => 'Content required (max 5000 chars)']);
            exit;
        }

        // Must be member
        $memCheck = $pdo->prepare("SELECT 1 FROM pulse_group_members WHERE group_id = ? AND user_id = ?");
        $memCheck->execute([$groupId, $uid]);
        if (!$memCheck->fetch()) { http_response_code(403); echo json_encode(['error' => 'Must be a member to post']); exit; }

        // Create as regular pulse post, then link to group
        $postStmt = $pdo->prepare("INSERT INTO pulse_posts (user_id, content, post_type) VALUES (?, ?, 'text')");
        $postStmt->execute([$uid, $content]);
        $postId = $pdo->lastInsertId();

        $pdo->prepare("INSERT INTO pulse_group_posts (group_id, post_id) VALUES (?, ?)")->execute([$groupId, $postId]);
        $pdo->prepare("UPDATE pulse_groups SET post_count = post_count + 1 WHERE id = ?")->execute([$groupId]);

        echo json_encode(['success' => true, 'post_id' => $postId]);
        break;

    // List group members
    case 'members':
        $groupId = (int)($_GET['group_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT m.user_id, m.role, m.joined_at, c.firstname AS name
            FROM pulse_group_members m
            LEFT JOIN clients c ON m.user_id = c.id
            WHERE m.group_id = ? ORDER BY FIELD(m.role, 'owner', 'admin', 'moderator', 'member'), m.joined_at ASC LIMIT 100");
        $stmt->execute([$groupId]);
        echo json_encode(['members' => $stmt->fetchAll()]);
        break;

    // My groups
    case 'my-groups':
        $uid = groupRequireAuth();
        $stmt = $pdo->prepare("SELECT g.*, m.role FROM pulse_groups g
            JOIN pulse_group_members m ON g.id = m.group_id
            WHERE m.user_id = ? ORDER BY m.joined_at DESC");
        $stmt->execute([$uid]);
        echo json_encode(['groups' => $stmt->fetchAll()]);
        break;

    // Search groups
    case 'search':
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 2) { echo json_encode(['groups' => []]); exit; }
        $stmt = $pdo->prepare("SELECT * FROM pulse_groups WHERE visibility = 'public' AND (name LIKE ? OR description LIKE ?) ORDER BY member_count DESC LIMIT 20");
        $like = '%' . $q . '%';
        $stmt->execute([$like, $like]);
        echo json_encode(['groups' => $stmt->fetchAll()]);
        break;

    // Categories
    case 'categories':
        echo json_encode(['categories' => [
            ['id' => 'outdoors', 'name' => 'Outdoors & Nature', 'icon' => 'fas fa-mountain'],
            ['id' => 'technology', 'name' => 'Technology & AI', 'icon' => 'fas fa-microchip'],
            ['id' => 'travel', 'name' => 'Travel & Adventure', 'icon' => 'fas fa-plane'],
            ['id' => 'energy', 'name' => 'Clean Energy & Future', 'icon' => 'fas fa-solar-panel'],
            ['id' => 'gaming', 'name' => 'Gaming & VR', 'icon' => 'fas fa-gamepad'],
            ['id' => 'creativity', 'name' => 'Art & Creativity', 'icon' => 'fas fa-palette'],
            ['id' => 'science', 'name' => 'Science & Discovery', 'icon' => 'fas fa-flask'],
            ['id' => 'wellness', 'name' => 'Health & Wellness', 'icon' => 'fas fa-heart'],
            ['id' => 'faith', 'name' => 'Faith & Spirituality', 'icon' => 'fas fa-dove'],
            ['id' => 'business', 'name' => 'Business & Finance', 'icon' => 'fas fa-chart-line'],
            ['id' => 'general', 'name' => 'General', 'icon' => 'fas fa-comments'],
        ]]);
        break;

    default:
        echo json_encode(['actions' => ['create','list','get','join','leave','feed','post','members','my-groups','search','categories']]);
}
