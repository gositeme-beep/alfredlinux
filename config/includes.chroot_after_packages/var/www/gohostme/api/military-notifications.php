<?php
/**
 * Military Notifications API — Level 3
 * GET  /api/military-notifications.php          — fetch unread notifications
 * POST /api/military-notifications.php?mark=ID  — mark one as read
 * POST /api/military-notifications.php?mark=all — mark all as read
 */
header('Content-Type: application/json');

require_once dirname(__DIR__) . '/includes/auth-gate.inc.php';

if (empty($clientId)) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Not authenticated']);
    exit;
}

require_once dirname(__DIR__) . '/includes/db-config.inc.php';
$db = getSharedDB();

// Mark as read
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mark = $_GET['mark'] ?? '';
    if ($mark === 'all') {
        $db->prepare("UPDATE military_notifications SET is_read = 1 WHERE client_id = ? AND is_read = 0")
           ->execute([$clientId]);
        echo json_encode(['ok' => true, 'action' => 'marked_all_read']);
    } elseif (ctype_digit($mark)) {
        $db->prepare("UPDATE military_notifications SET is_read = 1 WHERE id = ? AND client_id = ?")
           ->execute([(int)$mark, $clientId]);
        echo json_encode(['ok' => true, 'action' => 'marked_read', 'id' => (int)$mark]);
    } else {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid mark parameter']);
    }
    exit;
}

// GET — fetch notifications
$limit = min((int)($_GET['limit'] ?? 20), 100);
$unreadOnly = ($_GET['unread'] ?? '0') === '1';

$where = $unreadOnly ? 'AND is_read = 0' : '';
$stmt = $db->prepare("SELECT id, notification_type, title, message, data, is_read, created_at FROM military_notifications WHERE client_id = ? {$where} ORDER BY created_at DESC LIMIT ?");
$stmt->execute([$clientId, $limit]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Unread count
$unreadCount = (int)$db->prepare("SELECT COUNT(*) FROM military_notifications WHERE client_id = ? AND is_read = 0")->execute([$clientId]) ? $db->query("SELECT FOUND_ROWS()")->fetchColumn() : 0;
$ucStmt = $db->prepare("SELECT COUNT(*) FROM military_notifications WHERE client_id = ? AND is_read = 0");
$ucStmt->execute([$clientId]);
$unreadCount = (int)$ucStmt->fetchColumn();

echo json_encode([
    'ok' => true,
    'unread_count' => $unreadCount,
    'notifications' => $notifications,
]);
