<?php
/**
 * Veil Agenda API — Encrypted Calendar & Task Management
 * ═══════════════════════════════════════════════════════
 * Secure agenda/calendar stored within the Veil-protected ecosystem.
 * Only accessible to authenticated users. Owner sees all, others see their own.
 */
if (!defined('GOSITEME_API')) {
    define('GOSITEME_API', true);
}
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/config.php';
session_start();

// Auth check
$clientId = $_SESSION['client_id'] ?? null;
if (!$clientId) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
require_once dirname(__DIR__) . '/includes/api-security.php';
    exit;
}

$isOwner = (int)$clientId === 33;

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database unavailable']);
    exit;
}

// Auto-create table if needed
$pdo->exec("CREATE TABLE IF NOT EXISTS veil_agenda (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    title VARCHAR(500) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    event_time TIME DEFAULT NULL,
    end_time TIME DEFAULT NULL,
    category ENUM('meeting','task','reminder','training','security','personal','ops') DEFAULT 'task',
    priority TINYINT DEFAULT 5,
    status ENUM('pending','in_progress','completed','cancelled') DEFAULT 'pending',
    recurring ENUM('none','daily','weekly','monthly') DEFAULT 'none',
    tags VARCHAR(500) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_client_date (client_id, event_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($method) {
    case 'GET':
        handleGet($pdo, $clientId, $isOwner, $action);
        break;
    case 'POST':
        handlePost($pdo, $clientId, $isOwner);
        break;
    case 'PUT':
        handlePut($pdo, $clientId, $isOwner);
        break;
    case 'DELETE':
        handleDelete($pdo, $clientId, $isOwner);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}

function handleGet(PDO $pdo, int $clientId, bool $isOwner, string $action): void {
    if ($action === 'upcoming') {
        $sql = "SELECT * FROM veil_agenda WHERE event_date >= CURDATE() AND status != 'cancelled'";
        if (!$isOwner) $sql .= " AND client_id = ?";
        $sql .= " ORDER BY event_date ASC, event_time ASC LIMIT 50";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($isOwner ? [] : [$clientId]);
        echo json_encode(['events' => $stmt->fetchAll()]);
        return;
    }

    $month = $_GET['month'] ?? date('Y-m');
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
        $month = date('Y-m');
    }

    $sql = "SELECT * FROM veil_agenda WHERE event_date LIKE ? AND status != 'cancelled'";
    $params = [$month . '%'];
    if (!$isOwner) {
        $sql .= " AND client_id = ?";
        $params[] = $clientId;
    }
    $sql .= " ORDER BY event_date ASC, event_time ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode(['events' => $stmt->fetchAll(), 'month' => $month]);
}

function handlePost(PDO $pdo, int $clientId, bool $isOwner): void {
    $input = json_decode(file_get_contents('php://input'), true);
    if (empty($input['title']) || empty($input['event_date'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Title and event_date are required']);
        return;
    }

    // Validate date format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $input['event_date'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid date format (YYYY-MM-DD)']);
        return;
    }

    $stmt = $pdo->prepare("INSERT INTO veil_agenda (client_id, title, description, event_date, event_time, end_time, category, priority, recurring, tags) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $clientId,
        substr($input['title'], 0, 500),
        substr($input['description'] ?? '', 0, 5000),
        $input['event_date'],
        $input['event_time'] ?? null,
        $input['end_time'] ?? null,
        in_array($input['category'] ?? '', ['meeting','task','reminder','training','security','personal','ops']) ? $input['category'] : 'task',
        min(9, max(0, (int)($input['priority'] ?? 5))),
        in_array($input['recurring'] ?? '', ['none','daily','weekly','monthly']) ? $input['recurring'] : 'none',
        substr($input['tags'] ?? '', 0, 500)
    ]);
    echo json_encode(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
}

function handlePut(PDO $pdo, int $clientId, bool $isOwner): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = (int)($input['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Event ID required']);
        return;
    }

    // Verify ownership
    $check = $pdo->prepare("SELECT client_id FROM veil_agenda WHERE id = ?");
    $check->execute([$id]);
    $row = $check->fetch();
    if (!$row || (!$isOwner && (int)$row['client_id'] !== $clientId)) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        return;
    }

    $fields = [];
    $values = [];
    foreach (['title','description','event_date','event_time','end_time','category','priority','status','recurring','tags'] as $f) {
        if (isset($input[$f])) {
            $fields[] = "$f = ?";
            $values[] = $input[$f];
        }
    }
    if (empty($fields)) {
        echo json_encode(['error' => 'No fields to update']);
        return;
    }
    $values[] = $id;
    $pdo->prepare("UPDATE veil_agenda SET " . implode(', ', $fields) . " WHERE id = ?")->execute($values);
    echo json_encode(['success' => true]);
}

function handleDelete(PDO $pdo, int $clientId, bool $isOwner): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = (int)($input['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Event ID required']);
        return;
    }

    $sql = "UPDATE veil_agenda SET status = 'cancelled' WHERE id = ?";
    $params = [$id];
    if (!$isOwner) {
        $sql .= " AND client_id = ?";
        $params[] = $clientId;
    }
    $pdo->prepare($sql)->execute($params);
    echo json_encode(['success' => true]);
}
