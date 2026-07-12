<?php
/**
 * Alfred AI — Notification Center API
 * Manages user notifications, preferences, and read state.
 *
 * Endpoints (via ?action=):
 *   GET  list         — Get user's notifications (paginated, unread first)
 *   GET  unread-count — Count of unread notifications
 *   POST mark-read    — Mark notification(s) as read (id or "all")
 *   POST dismiss      — Delete a notification
 *   GET  preferences  — Get notification preferences
 *   POST preferences  — Update notification preferences
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Auth check
if (empty($_SESSION['logged_in']) || empty($_SESSION['client_id'])) {
    jsonResponse(['error' => 'Authentication required'], 401);
require_once dirname(__DIR__) . '/includes/api-security.php';
}

$clientId = (int) $_SESSION['client_id'];
$action   = sanitize($_GET['action'] ?? '');
$method   = $_SERVER['REQUEST_METHOD'];
$db       = getDB();

if (!$db) {
    jsonResponse(['error' => 'Database unavailable'], 500);
}

// Ensure tables exist
ensureNotificationTables($db);

switch ($action) {
    case 'list':
        handleList($db, $clientId);
        break;
    case 'unread-count':
        handleUnreadCount($db, $clientId);
        break;
    case 'mark-read':
        if ($method !== 'POST') jsonResponse(['error' => 'POST required'], 405);
        handleMarkRead($db, $clientId);
        break;
    case 'dismiss':
        if ($method !== 'POST') jsonResponse(['error' => 'POST required'], 405);
        handleDismiss($db, $clientId);
        break;
    case 'preferences':
        if ($method === 'POST') {
            handleUpdatePreferences($db, $clientId);
        } else {
            handleGetPreferences($db, $clientId);
        }
        break;
    default:
        jsonResponse(['error' => 'Invalid action', 'valid_actions' => [
            'list','unread-count','mark-read','dismiss','preferences'
        ]], 400);
}

/**
 * GET list — Get user's notifications (paginated, unread first)
 */
function handleList($db, $clientId) {
    $page    = max((int)($_GET['page'] ?? 1), 1);
    $perPage = min(max((int)($_GET['per_page'] ?? 20), 5), 100);
    $type    = sanitize($_GET['type'] ?? '');
    $offset  = ($page - 1) * $perPage;

    $where  = "WHERE user_id = ?";
    $params = [$clientId];

    $validTypes = ['info','success','warning','error','billing','security','system'];
    if ($type && in_array($type, $validTypes)) {
        $where .= " AND type = ?";
        $params[] = $type;
    }

    try {
        // Total count
        $countStmt = $db->prepare("SELECT COUNT(*) FROM alfred_notifications {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Fetch notifications (unread first, then by date)
        $fetchParams = array_merge($params, [$perPage, $offset]);
        $stmt = $db->prepare("
            SELECT id, type, title, message, action_url, action_label, is_read, created_at
            FROM alfred_notifications
            {$where}
            ORDER BY is_read ASC, created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute($fetchParams);
        $notifications = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Notifications list error: " . $e->getMessage());
        $notifications = [];
        $total = 0;
    }

    // Format
    foreach ($notifications as &$n) {
        $n['id']      = (int)$n['id'];
        $n['is_read'] = (bool)$n['is_read'];
    }

    jsonResponse([
        'success'       => true,
        'notifications' => $notifications,
        'pagination'    => [
            'page'     => $page,
            'per_page' => $perPage,
            'total'    => $total,
            'pages'    => max(ceil($total / $perPage), 1)
        ]
    ]);
}

/**
 * GET unread-count — Count of unread notifications
 */
function handleUnreadCount($db, $clientId) {
    try {
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM alfred_notifications
            WHERE user_id = ? AND is_read = 0
        ");
        $stmt->execute([$clientId]);
        $count = (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Notifications unread-count error: " . $e->getMessage());
        $count = 0;
    }

    jsonResponse([
        'success' => true,
        'count'   => $count
    ]);
}

/**
 * POST mark-read — Mark notification(s) as read
 * Body: { "id": 123 } or { "id": "all" }
 */
function handleMarkRead($db, $clientId) {
    $input = json_decode(file_get_contents('php://input'), true);
    $id    = $input['id'] ?? null;

    if ($id === null) {
        jsonResponse(['error' => 'Missing id parameter. Use numeric id or "all"'], 400);
    }

    try {
        if ($id === 'all') {
            $stmt = $db->prepare("
                UPDATE alfred_notifications SET is_read = 1
                WHERE user_id = ? AND is_read = 0
            ");
            $stmt->execute([$clientId]);
            $affected = $stmt->rowCount();
        } else {
            $id = (int) $id;
            $stmt = $db->prepare("
                UPDATE alfred_notifications SET is_read = 1
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$id, $clientId]);
            $affected = $stmt->rowCount();
        }
    } catch (PDOException $e) {
        error_log("Notifications mark-read error: " . $e->getMessage());
        jsonResponse(['error' => 'Failed to mark as read'], 500);
    }

    jsonResponse([
        'success'  => true,
        'affected' => $affected
    ]);
}

/**
 * POST dismiss — Delete a notification
 * Body: { "id": 123 }
 */
function handleDismiss($db, $clientId) {
    $input = json_decode(file_get_contents('php://input'), true);
    $id    = (int)($input['id'] ?? 0);

    if (!$id) {
        jsonResponse(['error' => 'Missing or invalid id'], 400);
    }

    try {
        $stmt = $db->prepare("
            DELETE FROM alfred_notifications
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$id, $clientId]);
        $deleted = $stmt->rowCount();
    } catch (PDOException $e) {
        error_log("Notifications dismiss error: " . $e->getMessage());
        jsonResponse(['error' => 'Failed to dismiss notification'], 500);
    }

    jsonResponse([
        'success' => true,
        'deleted' => (bool)$deleted
    ]);
}

/**
 * GET preferences — Get notification preferences
 */
function handleGetPreferences($db, $clientId) {
    try {
        $stmt = $db->prepare("
            SELECT email_billing, email_security, email_system, email_marketing,
                   inapp_all, webhook_all
            FROM alfred_notification_prefs
            WHERE user_id = ?
        ");
        $stmt->execute([$clientId]);
        $prefs = $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Notification preferences error: " . $e->getMessage());
        $prefs = null;
    }

    if (!$prefs) {
        // Return defaults
        $prefs = [
            'email_billing'   => true,
            'email_security'  => true,
            'email_system'    => true,
            'email_marketing' => false,
            'inapp_all'       => true,
            'webhook_all'     => false
        ];
    } else {
        foreach ($prefs as $k => &$v) {
            $v = (bool)$v;
        }
    }

    jsonResponse([
        'success'     => true,
        'preferences' => $prefs
    ]);
}

/**
 * POST preferences — Update notification preferences
 * Body: { "email_billing": true, "email_security": true, ... }
 */
function handleUpdatePreferences($db, $clientId) {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        jsonResponse(['error' => 'Invalid JSON body'], 400);
    }

    $validFields = ['email_billing','email_security','email_system','email_marketing','inapp_all','webhook_all'];
    $updates = [];
    $values  = [];

    foreach ($validFields as $field) {
        if (isset($input[$field])) {
            $updates[] = "{$field} = ?";
            $values[]  = $input[$field] ? 1 : 0;
        }
    }

    if (empty($updates)) {
        jsonResponse(['error' => 'No valid preference fields provided', 'valid_fields' => $validFields], 400);
    }

    try {
        // Upsert
        $insertFields = implode(', ', array_map(function($f) use ($input) {
            return $f;
        }, array_filter($validFields, function($f) use ($input) {
            return isset($input[$f]);
        })));

        $insertPlaceholders = implode(', ', array_fill(0, count($values), '?'));
        $updateClause = implode(', ', $updates);

        $stmt = $db->prepare("
            INSERT INTO alfred_notification_prefs (user_id, {$insertFields})
            VALUES (?, {$insertPlaceholders})
            ON DUPLICATE KEY UPDATE {$updateClause}
        ");
        $allValues = array_merge([$clientId], $values, $values);
        $stmt->execute($allValues);
    } catch (PDOException $e) {
        error_log("Update preferences error: " . $e->getMessage());
        jsonResponse(['error' => 'Failed to update preferences'], 500);
    }

    jsonResponse([
        'success' => true,
        'message' => 'Preferences updated'
    ]);
}

/**
 * Ensure notification tables exist
 */
function ensureNotificationTables($db) {
    try {
        $db->exec("
            CREATE TABLE IF NOT EXISTS alfred_notifications (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                org_id INT DEFAULT NULL,
                type ENUM('info','success','warning','error','billing','security','system') DEFAULT 'info',
                title VARCHAR(200) NOT NULL,
                message TEXT,
                action_url VARCHAR(500) DEFAULT NULL,
                action_label VARCHAR(100) DEFAULT NULL,
                is_read TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_read (user_id, is_read),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS alfred_notification_prefs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL UNIQUE,
                email_billing TINYINT(1) DEFAULT 1,
                email_security TINYINT(1) DEFAULT 1,
                email_system TINYINT(1) DEFAULT 1,
                email_marketing TINYINT(1) DEFAULT 0,
                inapp_all TINYINT(1) DEFAULT 1,
                webhook_all TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } catch (PDOException $e) {
        error_log("ensureNotificationTables error: " . $e->getMessage());
    }
}
