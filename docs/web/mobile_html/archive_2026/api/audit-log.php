<?php
/**
 * GoSiteMe Audit Log API
 * ───────────────────────
 * Records and retrieves security-relevant events across the platform.
 *
 * POST /api/audit-log.php   — Log an event (internal/authenticated)
 * GET  /api/audit-log.php   — Query audit logs (admin-only)
 *
 * Auto-creates table on first use.
 * @since v14.0
 */
define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';
require_once __DIR__ . '/helpers/response.php';

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) session_start();
$clientId = (int)($_SESSION['client_id'] ?? 0);
$isOwner  = ($clientId === 33);

try {
    $db = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    apiError('Service unavailable', 503);
}

// Auto-create table
$db->exec("CREATE TABLE IF NOT EXISTS `alfred_audit_log` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `timestamp` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `client_id` INT UNSIGNED NULL,
    `action` VARCHAR(100) NOT NULL,
    `resource` VARCHAR(200) NULL,
    `resource_id` VARCHAR(100) NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(500) NULL,
    `details` JSON NULL,
    `severity` ENUM('info','warning','critical') NOT NULL DEFAULT 'info',
    INDEX `idx_client` (`client_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_timestamp` (`timestamp`),
    INDEX `idx_severity` (`severity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// ── POST: Record an audit event ──────────────────────────────
if ($method === 'POST') {
    requireCSRF();
    if (!$clientId) apiError('Authentication required', 401);

    $body = getJsonBody();
    $action     = trim($body['action'] ?? '');
    $resource   = trim($body['resource'] ?? '');
    $resourceId = trim($body['resource_id'] ?? '');
    $details    = $body['details'] ?? null;
    $severity   = in_array($body['severity'] ?? '', ['info','warning','critical']) ? $body['severity'] : 'info';

    if (!$action) apiError('action is required');

    $stmt = $db->prepare("INSERT INTO alfred_audit_log
        (client_id, action, resource, resource_id, ip_address, user_agent, details, severity)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $clientId,
        substr($action, 0, 100),
        substr($resource, 0, 200) ?: null,
        substr($resourceId, 0, 100) ?: null,
        $_SERVER['REMOTE_ADDR'] ?? null,
        substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500) ?: null,
        $details ? json_encode($details) : null,
        $severity,
    ]);

    apiSuccess(['id' => (int)$db->lastInsertId()], 201, 'Event logged');
}

// ── GET: Query audit logs (admin-only) ───────────────────────
if ($method === 'GET') {
    if (!$isOwner) apiError('Admin access required', 403);

    $page    = max(1, (int)($_GET['page'] ?? 1));
    $perPage = min(100, max(1, (int)($_GET['per_page'] ?? 50)));
    $offset  = ($page - 1) * $perPage;

    // Filters
    $where  = [];
    $params = [];

    if (!empty($_GET['action'])) {
        $where[]  = 'action = ?';
        $params[] = $_GET['action'];
    }
    if (!empty($_GET['client_id'])) {
        $where[]  = 'client_id = ?';
        $params[] = (int)$_GET['client_id'];
    }
    if (!empty($_GET['severity'])) {
        $where[]  = 'severity = ?';
        $params[] = $_GET['severity'];
    }
    if (!empty($_GET['since'])) {
        $where[]  = 'timestamp >= ?';
        $params[] = $_GET['since'];
    }
    if (!empty($_GET['resource'])) {
        $where[]  = 'resource = ?';
        $params[] = $_GET['resource'];
    }

    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $countStmt = $db->prepare("SELECT COUNT(*) FROM alfred_audit_log $whereSQL");
    dbExecute($countStmt, $params);
    $total = (int)$countStmt->fetchColumn();

    $dataStmt = $db->prepare("SELECT * FROM alfred_audit_log $whereSQL ORDER BY timestamp DESC LIMIT ? OFFSET ?");
    $params[] = $perPage;
    $params[] = $offset;
    dbExecute($dataStmt, $params);
    $rows = $dataStmt->fetchAll();

    // Decode JSON details
    foreach ($rows as &$row) {
        if (isset($row['details'])) $row['details'] = json_decode($row['details'], true);
    }
    unset($row);

    apiPaginated($rows, $total, $page, $perPage);
}

requireMethod(['GET', 'POST', 'HEAD']);
