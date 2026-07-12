<?php
/**
 * Conversations API — CRUD and export for Alfred conversation history
 *
 * Endpoints (via $_GET['action']):
 *   GET  ?action=list    — List user conversations (paginated, searchable)
 *   GET  ?action=get     — Get full conversation messages
 *   GET  ?action=stats   — User conversation statistics
 *   POST ?action=rename  — Rename a conversation
 *   POST ?action=delete  — Delete a conversation
 *   GET  ?action=export  — Export conversation in txt/json/md format
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

session_start();

// Auth — require logged-in user
$userId = $_SESSION['uid'] ?? $_SESSION['client_id'] ?? null;
if (!$userId) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
require_once dirname(__DIR__) . '/includes/api-security.php';
    exit;
}

// CSRF check for mutating requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $sessionToken = $_SESSION['alfred_csrf'] ?? '';
    if (!$sessionToken) {
        $_SESSION['alfred_csrf'] = bin2hex(random_bytes(32));
        $sessionToken = $_SESSION['alfred_csrf'];
    }
    if ($csrfToken !== '' && !hash_equals($sessionToken, $csrfToken)) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF token', 'csrf_token' => $sessionToken]);
        exit;
    }
}

// DB — shared config (no hardcoded credentials)
require_once dirname(__DIR__) . '/includes/db-config.inc.php';
try {
    $pdo = getSharedDB();
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':   handleList($pdo, $userId); break;
    case 'get':    handleGet($pdo, $userId); break;
    case 'stats':  handleStats($pdo, $userId); break;
    case 'rename': handleRename($pdo, $userId); break;
    case 'delete': handleDelete($pdo, $userId); break;
    case 'export': handleExport($pdo, $userId); break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action', 'valid' => ['list','get','stats','rename','delete','export']]);
}

/* ─────────────────────────────────────────────
   LIST — paginated conversation listing
   ───────────────────────────────────────────── */
function handleList($pdo, $userId) {
    $page    = max(1, intval($_GET['page'] ?? 1));
    $perPage = min(50, max(5, intval($_GET['per_page'] ?? 20)));
    $search  = trim($_GET['search'] ?? '');
    $filter  = $_GET['date_filter'] ?? 'all';
    $offset  = ($page - 1) * $perPage;

    $where = 'WHERE ac.user_id = ?';
    $params = [$userId];

    // Date filter
    switch ($filter) {
        case 'today':
            $where .= ' AND ac.created_at >= CURDATE()';
            break;
        case 'week':
            $where .= ' AND ac.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)';
            break;
        case 'month':
            $where .= ' AND ac.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)';
            break;
    }

    // Search filter — search inside messages
    if ($search !== '') {
        $where .= ' AND ac.conv_id IN (
            SELECT DISTINCT conv_id FROM alfred_conversations
            WHERE user_id = ? AND message LIKE ?
        )';
        $params[] = $userId;
        $params[] = '%' . $search . '%';
    }

    // Count total matching conversations
    $countSql = "SELECT COUNT(DISTINCT ac.conv_id) FROM alfred_conversations ac $where";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    // Fetch conversation list with preview
    $sql = "
        SELECT 
            ac.conv_id,
            ac.agent,
            MIN(ac.created_at) AS started,
            MAX(ac.created_at) AS updated,
            COUNT(*) AS msg_count,
            ct.title,
            (SELECT message FROM alfred_conversations ac2 
             WHERE ac2.conv_id = ac.conv_id AND ac2.role = 'user' 
             ORDER BY ac2.created_at ASC LIMIT 1) AS first_msg
        FROM alfred_conversations ac
        LEFT JOIN alfred_conversation_titles ct ON ct.conv_id = ac.conv_id AND ct.user_id = ac.user_id
        $where
        GROUP BY ac.conv_id, ac.agent
        ORDER BY MAX(ac.created_at) DESC
        LIMIT $perPage OFFSET $offset
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // Format results
    $conversations = [];
    foreach ($rows as $row) {
        $preview = mb_substr($row['first_msg'] ?? '', 0, 120);
        $conversations[] = [
            'conv_id'    => $row['conv_id'],
            'title'      => $row['title'] ?: generateTitle($preview),
            'preview'    => $preview,
            'agent'      => $row['agent'],
            'msg_count'  => (int)$row['msg_count'],
            'started'    => $row['started'],
            'updated'    => $row['updated'],
        ];
    }

    echo json_encode([
        'conversations' => $conversations,
        'total'         => $total,
        'page'          => $page,
        'per_page'      => $perPage,
        'total_pages'   => ceil($total / $perPage),
        'csrf_token'    => $_SESSION['alfred_csrf'] ?? '',
    ]);
}

/* ─────────────────────────────────────────────
   GET — full conversation messages
   ───────────────────────────────────────────── */
function handleGet($pdo, $userId) {
    $convId = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['id'] ?? '');
    if (!$convId) {
        http_response_code(400);
        echo json_encode(['error' => 'Conversation ID required']);
        return;
    }

    // Verify ownership
    $check = $pdo->prepare("SELECT COUNT(*) FROM alfred_conversations WHERE conv_id = ? AND user_id = ?");
    $check->execute([$convId, $userId]);
    if ($check->fetchColumn() == 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Conversation not found']);
        return;
    }

    $stmt = $pdo->prepare("
        SELECT id, role, message, agent, created_at
        FROM alfred_conversations
        WHERE conv_id = ? AND user_id = ?
        ORDER BY created_at ASC
        LIMIT 500
    ");
    $stmt->execute([$convId, $userId]);
    $messages = $stmt->fetchAll();

    // Get title
    $titleStmt = $pdo->prepare("SELECT title FROM alfred_conversation_titles WHERE conv_id = ? AND user_id = ?");
    $titleStmt->execute([$convId, $userId]);
    $titleRow = $titleStmt->fetch();
    $title = $titleRow['title'] ?? null;

    if (!$title && count($messages) > 0) {
        $firstUser = '';
        foreach ($messages as $m) {
            if ($m['role'] === 'user') { $firstUser = $m['message']; break; }
        }
        $title = generateTitle($firstUser);
    }

    echo json_encode([
        'conv_id'  => $convId,
        'title'    => $title,
        'messages' => $messages,
    ]);
}

/* ─────────────────────────────────────────────
   STATS — conversation statistics
   ───────────────────────────────────────────── */
function handleStats($pdo, $userId) {
    $stats = [];

    // Total conversations & messages
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT conv_id) AS total_conversations,
               COUNT(*) AS total_messages
        FROM alfred_conversations WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    $stats['total_conversations'] = (int)($row['total_conversations'] ?? 0);
    $stats['total_messages']      = (int)($row['total_messages'] ?? 0);

    // Average messages per conversation
    if ($stats['total_conversations'] > 0) {
        $stats['avg_messages'] = round($stats['total_messages'] / $stats['total_conversations'], 1);
    } else {
        $stats['avg_messages'] = 0;
    }

    // Most used agent
    $stmt = $pdo->prepare("
        SELECT agent, COUNT(*) AS cnt
        FROM alfred_conversations WHERE user_id = ?
        GROUP BY agent ORDER BY cnt DESC LIMIT 1
    ");
    $stmt->execute([$userId]);
    $agentRow = $stmt->fetch();
    $stats['most_used_agent'] = $agentRow['agent'] ?? 'alfred';

    // Busiest hour
    $stmt = $pdo->prepare("
        SELECT HOUR(created_at) AS hr, COUNT(*) AS cnt
        FROM alfred_conversations WHERE user_id = ?
        GROUP BY hr ORDER BY cnt DESC LIMIT 1
    ");
    $stmt->execute([$userId]);
    $hrRow = $stmt->fetch();
    $stats['busiest_hour'] = $hrRow ? sprintf('%02d:00', $hrRow['hr']) : '--';

    // Most active day of week
    $stmt = $pdo->prepare("
        SELECT DAYNAME(created_at) AS day_name, COUNT(*) AS cnt
        FROM alfred_conversations WHERE user_id = ?
        GROUP BY day_name ORDER BY cnt DESC LIMIT 1
    ");
    $stmt->execute([$userId]);
    $dayRow = $stmt->fetch();
    $stats['most_active_day'] = $dayRow['day_name'] ?? '--';

    echo json_encode(['stats' => $stats]);
}

/* ─────────────────────────────────────────────
   RENAME — set custom title for a conversation
   ───────────────────────────────────────────── */
function handleRename($pdo, $userId) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'POST required']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $convId = preg_replace('/[^a-zA-Z0-9_\-]/', '', $input['id'] ?? '');
    $title  = trim(mb_substr($input['title'] ?? '', 0, 200));

    if (!$convId || $title === '') {
        http_response_code(400);
        echo json_encode(['error' => 'id and title required']);
        return;
    }

    // Verify ownership
    $check = $pdo->prepare("SELECT COUNT(*) FROM alfred_conversations WHERE conv_id = ? AND user_id = ?");
    $check->execute([$convId, $userId]);
    if ($check->fetchColumn() == 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Conversation not found']);
        return;
    }

    // Upsert title (separate table so we don't alter the main schema)
    ensureTitlesTable($pdo);
    $stmt = $pdo->prepare("
        INSERT INTO alfred_conversation_titles (conv_id, user_id, title)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE title = VALUES(title)
    ");
    $stmt->execute([$convId, $userId, $title]);

    echo json_encode(['success' => true, 'title' => $title]);
}

/* ─────────────────────────────────────────────
   DELETE — remove a conversation
   ───────────────────────────────────────────── */
function handleDelete($pdo, $userId) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'POST required']);
        return;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $convId = preg_replace('/[^a-zA-Z0-9_\-]/', '', $input['id'] ?? '');

    if (!$convId) {
        http_response_code(400);
        echo json_encode(['error' => 'id required']);
        return;
    }

    // Verify ownership
    $check = $pdo->prepare("SELECT COUNT(*) FROM alfred_conversations WHERE conv_id = ? AND user_id = ?");
    $check->execute([$convId, $userId]);
    if ($check->fetchColumn() == 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Conversation not found']);
        return;
    }

    $stmt = $pdo->prepare("DELETE FROM alfred_conversations WHERE conv_id = ? AND user_id = ?");
    $stmt->execute([$convId, $userId]);

    // Also delete title if exists
    try {
        $pdo->prepare("DELETE FROM alfred_conversation_titles WHERE conv_id = ? AND user_id = ?")->execute([$convId, $userId]);
    } catch (PDOException $e) { /* table may not exist */ }

    echo json_encode(['success' => true, 'deleted' => $convId]);
}

/* ─────────────────────────────────────────────
   EXPORT — download conversation in txt/json/md
   ───────────────────────────────────────────── */
function handleExport($pdo, $userId) {
    $convId = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['id'] ?? '');
    $format = $_GET['format'] ?? 'txt';

    if (!$convId) {
        http_response_code(400);
        echo json_encode(['error' => 'id required']);
        return;
    }

    // Verify ownership  
    $check = $pdo->prepare("SELECT COUNT(*) FROM alfred_conversations WHERE conv_id = ? AND user_id = ?");
    $check->execute([$convId, $userId]);
    if ($check->fetchColumn() == 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Conversation not found']);
        return;
    }

    $stmt = $pdo->prepare("
        SELECT role, message, agent, created_at
        FROM alfred_conversations
        WHERE conv_id = ? AND user_id = ?
        ORDER BY created_at ASC
    ");
    $stmt->execute([$convId, $userId]);
    $messages = $stmt->fetchAll();

    $filename = 'alfred-conversation-' . $convId;

    switch ($format) {
        case 'json':
            header('Content-Type: application/json');
            header("Content-Disposition: attachment; filename=\"{$filename}.json\"");
            echo json_encode(['conv_id' => $convId, 'messages' => $messages, 'exported_at' => date('c')], JSON_PRETTY_PRINT);
            break;

        case 'md':
            header('Content-Type: text/markdown');
            header("Content-Disposition: attachment; filename=\"{$filename}.md\"");
            echo "# Alfred Conversation: {$convId}\n\n";
            foreach ($messages as $m) {
                $label = $m['role'] === 'user' ? '**You**' : '**Alfred**';
                echo "{$label} — _{$m['created_at']}_\n\n{$m['message']}\n\n---\n\n";
            }
            break;

        default: // txt
            header('Content-Type: text/plain');
            header("Content-Disposition: attachment; filename=\"{$filename}.txt\"");
            echo "Alfred Conversation: {$convId}\n";
            echo str_repeat('=', 50) . "\n\n";
            foreach ($messages as $m) {
                $label = $m['role'] === 'user' ? 'You' : 'Alfred';
                echo "[{$m['created_at']}] {$label}:\n{$m['message']}\n\n";
            }
    }
    exit;
}

/* ─────────────────────────────────────────────
   HELPERS
   ───────────────────────────────────────────── */
function generateTitle($preview) {
    if (!$preview) return 'Untitled Conversation';
    $title = mb_substr(strip_tags($preview), 0, 60);
    if (mb_strlen($preview) > 60) $title .= '…';
    return $title;
}

function ensureTitlesTable($pdo) {
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS alfred_conversation_titles (
                conv_id VARCHAR(64) NOT NULL,
                user_id INT UNSIGNED NOT NULL,
                title VARCHAR(200) NOT NULL DEFAULT '',
                PRIMARY KEY (conv_id, user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } catch (PDOException $e) {
        error_log('ensureTitlesTable error: ' . $e->getMessage());
    }
}
