<?php
/**
 * GoSiteMe — Changelog API
 * ─────────────────────────
 * Database-backed changelog management for programmatic access by agents and humans.
 *
 * Actions (GET):
 *   ?action=versions           — List all versions (with entry counts)
 *   ?action=entries             — List entries (optional: ?version=18.2&tag=tools&page=1&per_page=20)
 *   ?action=tags                — List all available tags
 *   ?action=stats               — Dashboard stats (total versions, entries, latest)
 *
 * Actions (POST, auth required):
 *   action=add_version          — Create a new version
 *   action=add_entry            — Add an entry to a version
 *   action=update_entry         — Update an existing entry
 *   action=delete_entry         — Soft-delete an entry (admin only)
 *
 * @since v19.0
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

session_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . (defined('SITE_URL') ? SITE_URL : 'https://gositeme.com'));
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Internal-Secret, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

// ─── Auth Helpers ──────────────────────────────────────────────────────────────

function requireAuth(): void {
    if (empty($_SESSION['logged_in']) || empty($_SESSION['client_id'])) {
        apiError('Authentication required', 401);
    }
}

function isAdmin(): bool {
    return !empty($_SESSION['is_admin']) || ($_SESSION['client_id'] ?? 0) === 33;
}

function isInternalAgent(): bool {
    $secret = $_SERVER['HTTP_X_INTERNAL_SECRET'] ?? '';
    $expected = defined('INTERNAL_SECRET') ? INTERNAL_SECRET : '';
    return $expected !== '' && hash_equals($expected, $secret);
}

// ─── Table Setup ───────────────────────────────────────────────────────────────

function ensureChangelogTables(PDO $db): void {
    static $checked = false;
    if ($checked) return;

    $db->exec("CREATE TABLE IF NOT EXISTS platform_changelog_versions (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        version         VARCHAR(20) NOT NULL UNIQUE,
        codename        VARCHAR(100) DEFAULT NULL,
        release_date    DATE NOT NULL,
        tags            VARCHAR(255) DEFAULT NULL,
        badge_class     ENUM('major','minor') DEFAULT 'major',
        sort_order      INT DEFAULT 0,
        created_by      INT DEFAULT NULL,
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_sort (sort_order DESC),
        INDEX idx_date (release_date DESC)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS platform_changelog_entries (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        version_id      INT NOT NULL,
        title_en        VARCHAR(500) NOT NULL,
        description_en  TEXT NOT NULL,
        title_fr        VARCHAR(500) DEFAULT NULL,
        description_fr  TEXT DEFAULT NULL,
        icon            VARCHAR(50) DEFAULT 'fas fa-circle',
        icon_color      VARCHAR(30) DEFAULT 'var(--cl-cyan)',
        tag             VARCHAR(30) DEFAULT 'tools',
        sort_order      INT DEFAULT 0,
        is_deleted      TINYINT(1) DEFAULT 0,
        created_by      INT DEFAULT NULL,
        agent_name      VARCHAR(100) DEFAULT NULL,
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_version (version_id),
        INDEX idx_tag (tag),
        INDEX idx_sort (sort_order),
        INDEX idx_deleted (is_deleted),
        FOREIGN KEY (version_id) REFERENCES platform_changelog_versions(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $checked = true;
}

// ─── Route ─────────────────────────────────────────────────────────────────────

$db = getDB();
if (!$db) apiError('Database unavailable', 503);

ensureChangelogTables($db);

$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    switch ($action) {
        case 'versions': handleListVersions($db); break;
        case 'entries':  handleListEntries($db);  break;
        case 'tags':     handleListTags($db);     break;
        case 'stats':    handleStats($db);        break;
        default:         apiError('Unknown action. Use: versions, entries, tags, stats', 400);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isInternalAgent()) {
        requireCSRF();
        requireAuth();
    }
    apiRateLimit(20, 60, 'changelog-write');

    $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $postAction = $body['action'] ?? $action;

    switch ($postAction) {
        case 'add_version':  handleAddVersion($db, $body);  break;
        case 'add_entry':    handleAddEntry($db, $body);    break;
        case 'update_entry': handleUpdateEntry($db, $body); break;
        case 'delete_entry': handleDeleteEntry($db, $body); break;
        default:             apiError('Unknown POST action. Use: add_version, add_entry, update_entry, delete_entry', 400);
    }
} else {
    apiError('Method not allowed', 405);
}

// ─── GET Handlers ──────────────────────────────────────────────────────────────

function handleListVersions(PDO $db): void {
    $stmt = $db->query("
        SELECT v.*, COUNT(e.id) AS entry_count
        FROM platform_changelog_versions v
        LEFT JOIN platform_changelog_entries e ON e.version_id = v.id AND e.is_deleted = 0
        GROUP BY v.id
        ORDER BY v.sort_order DESC, v.release_date DESC
    ");
    $versions = $stmt->fetchAll();
    apiSuccess(['versions' => $versions]);
}

function handleListEntries(PDO $db): void {
    $version = filter_input(INPUT_GET, 'version', FILTER_SANITIZE_SPECIAL_CHARS);
    $tag = filter_input(INPUT_GET, 'tag', FILTER_SANITIZE_SPECIAL_CHARS);
    $page = max(1, (int)(filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1));
    $perPage = min(100, max(1, (int)(filter_input(INPUT_GET, 'per_page', FILTER_VALIDATE_INT) ?: 50)));

    $where = ['e.is_deleted = 0'];
    $params = [];

    if ($version) {
        $where[] = 'v.version = ?';
        $params[] = $version;
    }
    if ($tag) {
        $where[] = 'e.tag = ?';
        $params[] = $tag;
    }

    $whereClause = implode(' AND ', $where);

    // Count
    $countStmt = $db->prepare("
        SELECT COUNT(*) FROM platform_changelog_entries e
        JOIN platform_changelog_versions v ON v.id = e.version_id
        WHERE $whereClause
    ");
    dbExecute($countStmt, $params);
    $total = (int)$countStmt->fetchColumn();

    // Fetch
    $offset = ($page - 1) * $perPage;
    $fetchParams = array_merge($params, [$perPage, $offset]);
    $fetchStmt = $db->prepare("
        SELECT e.*, v.version, v.codename, v.release_date, v.tags AS version_tags
        FROM platform_changelog_entries e
        JOIN platform_changelog_versions v ON v.id = e.version_id
        WHERE $whereClause
        ORDER BY v.sort_order DESC, v.release_date DESC, e.sort_order ASC
        LIMIT ? OFFSET ?
    ");
    dbExecute($fetchStmt, $fetchParams);
    $entries = $fetchStmt->fetchAll();

    apiPaginated($entries, $total, $page, $perPage);
}

function handleListTags(PDO $db): void {
    $stmt = $db->query("
        SELECT tag, COUNT(*) AS count
        FROM platform_changelog_entries
        WHERE is_deleted = 0
        GROUP BY tag
        ORDER BY count DESC
    ");
    apiSuccess(['tags' => $stmt->fetchAll()]);
}

function handleStats(PDO $db): void {
    $versions = (int)$db->query("SELECT COUNT(*) FROM platform_changelog_versions")->fetchColumn();
    $entries = (int)$db->query("SELECT COUNT(*) FROM platform_changelog_entries WHERE is_deleted = 0")->fetchColumn();
    $latest = $db->query("SELECT version, codename, release_date FROM platform_changelog_versions ORDER BY sort_order DESC LIMIT 1")->fetch();

    apiSuccess([
        'total_versions' => $versions,
        'total_entries' => $entries,
        'latest_version' => $latest ?: null,
    ]);
}

// ─── POST Handlers ─────────────────────────────────────────────────────────────

function handleAddVersion(PDO $db, array $body): void {
    $version = trim($body['version'] ?? '');
    $codename = trim($body['codename'] ?? '') ?: null;
    $releaseDate = trim($body['release_date'] ?? '');
    $tags = trim($body['tags'] ?? '');
    $badgeClass = ($body['badge_class'] ?? 'major') === 'minor' ? 'minor' : 'major';
    $sortOrder = (int)($body['sort_order'] ?? 0);

    if (!$version) apiError('version is required');
    if (!$releaseDate || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $releaseDate)) {
        apiError('release_date is required (YYYY-MM-DD format)');
    }

    // Check duplicate
    $check = $db->prepare("SELECT id FROM platform_changelog_versions WHERE version = ?");
    $check->execute([$version]);
    if ($check->fetch()) apiError("Version $version already exists", 409);

    // Auto sort_order if not specified
    if ($sortOrder === 0) {
        $maxSort = (int)$db->query("SELECT COALESCE(MAX(sort_order), 0) FROM platform_changelog_versions")->fetchColumn();
        $sortOrder = $maxSort + 100;
    }

    $stmt = $db->prepare("INSERT INTO platform_changelog_versions (version, codename, release_date, tags, badge_class, sort_order, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $version, $codename, $releaseDate, $tags, $badgeClass, $sortOrder,
        $_SESSION['client_id'] ?? null
    ]);

    apiSuccess(['id' => (int)$db->lastInsertId(), 'version' => $version, 'message' => "Version $version created"], 201);
}

function handleAddEntry(PDO $db, array $body): void {
    $version = trim($body['version'] ?? '');
    $titleEn = trim($body['title_en'] ?? '');
    $descEn = trim($body['description_en'] ?? '');
    $titleFr = trim($body['title_fr'] ?? '') ?: null;
    $descFr = trim($body['description_fr'] ?? '') ?: null;
    $icon = trim($body['icon'] ?? 'fas fa-circle');
    $iconColor = trim($body['icon_color'] ?? 'var(--cl-cyan)');
    $tag = trim($body['tag'] ?? 'tools');
    $agentName = trim($body['agent_name'] ?? '') ?: null;

    if (!$version) apiError('version is required');
    if (!$titleEn) apiError('title_en is required');
    if (!$descEn) apiError('description_en is required');

    // Validate tag
    $validTags = ['tools', 'infrastructure', 'billing', 'voice', 'security', 'vr', 'alfred-os', 'mobile', 'simulator', 'ide', 'social', 'health', 'crypto', 'docs'];
    if (!in_array($tag, $validTags, true)) {
        apiError("Invalid tag. Valid tags: " . implode(', ', $validTags));
    }

    // Find version
    $vStmt = $db->prepare("SELECT id FROM platform_changelog_versions WHERE version = ?");
    $vStmt->execute([$version]);
    $versionRow = $vStmt->fetch();
    if (!$versionRow) apiError("Version $version not found. Create it first with add_version.", 404);

    // Auto sort_order
    $maxSort = $db->prepare("SELECT COALESCE(MAX(sort_order), 0) FROM platform_changelog_entries WHERE version_id = ?");
    $maxSort->execute([$versionRow['id']]);
    $sortOrder = (int)$maxSort->fetchColumn() + 10;

    $stmt = $db->prepare("INSERT INTO platform_changelog_entries (version_id, title_en, description_en, title_fr, description_fr, icon, icon_color, tag, sort_order, created_by, agent_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $versionRow['id'], $titleEn, $descEn, $titleFr, $descFr,
        $icon, $iconColor, $tag, $sortOrder,
        $_SESSION['client_id'] ?? null, $agentName
    ]);

    apiSuccess(['id' => (int)$db->lastInsertId(), 'version' => $version, 'message' => "Entry added to $version"], 201);
}

function handleUpdateEntry(PDO $db, array $body): void {
    if (!isAdmin() && !isInternalAgent()) apiError('Admin access required', 403);

    $id = (int)($body['id'] ?? 0);
    if (!$id) apiError('id is required');

    $fields = [];
    $params = [];
    $updatable = ['title_en', 'description_en', 'title_fr', 'description_fr', 'icon', 'icon_color', 'tag'];

    foreach ($updatable as $field) {
        if (isset($body[$field])) {
            $fields[] = "$field = ?";
            $params[] = trim($body[$field]);
        }
    }

    if (empty($fields)) apiError('No fields to update');

    $params[] = $id;
    $stmt = $db->prepare("UPDATE platform_changelog_entries SET " . implode(', ', $fields) . " WHERE id = ?");
    $stmt->execute($params);

    apiSuccess(['updated' => $stmt->rowCount(), 'message' => 'Entry updated']);
}

function handleDeleteEntry(PDO $db, array $body): void {
    if (!isAdmin() && !isInternalAgent()) apiError('Admin access required', 403);

    $id = (int)($body['id'] ?? 0);
    if (!$id) apiError('id is required');

    $stmt = $db->prepare("UPDATE platform_changelog_entries SET is_deleted = 1 WHERE id = ?");
    $stmt->execute([$id]);

    apiSuccess(['deleted' => $stmt->rowCount(), 'message' => 'Entry soft-deleted']);
}
