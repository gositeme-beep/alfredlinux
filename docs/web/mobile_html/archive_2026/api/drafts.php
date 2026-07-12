<?php
/**
 * GoSiteMe — DraftGuard Server API
 * ─────────────────────────────────
 * Syncs user drafts to the database for cross-device recovery.
 * Client-side saves to IndexedDB first (instant), then optionally syncs here.
 *
 * Actions:
 *   GET  ?action=list         — list all user drafts
 *   GET  ?action=load&key=... — load a specific draft
 *   POST ?action=save         — save/update a draft
 *   POST ?action=delete       — delete a specific draft
 *   POST ?action=clear        — delete all user drafts
 */

include __DIR__ . '/../includes/api-security.php';
require_once __DIR__ . '/../includes/db-config.inc.php';

header('Content-Type: application/json');

// Require authentication
if (session_status() === PHP_SESSION_NONE) session_start();
$clientId = (int)($_SESSION['client_id'] ?? 0);
if ($clientId < 1) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS)
       ?? filter_input(INPUT_POST, 'action', FILTER_SANITIZE_SPECIAL_CHARS);

if (!$action) {
    echo json_encode(['error' => 'Missing action']);
    exit;
}

$pdo = getSharedDB();

// ── Ensure table exists (auto-migrate) ──────────────

$pdo->exec("CREATE TABLE IF NOT EXISTS `user_drafts` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `client_id` INT UNSIGNED NOT NULL,
    `page_key` VARCHAR(500) NOT NULL,
    `page_title` VARCHAR(255) DEFAULT '',
    `draft_data` JSON NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_client_page` (`client_id`, `page_key`(191)),
    KEY `idx_client_updated` (`client_id`, `updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// ── Action Router ────────────────────────────────────

switch ($action) {

    case 'save':
        requireCSRF();
        $body = json_decode(file_get_contents('php://input'), true);
        $pageKey   = trim($body['page_key'] ?? '');
        $data      = $body['data'] ?? null;
        $pageTitle = trim($body['page_title'] ?? '');

        if (!$pageKey || strlen($pageKey) > 500) {
            echo json_encode(['error' => 'Invalid page_key']);
            break;
        }
        if (!$data || !is_array($data)) {
            echo json_encode(['error' => 'Invalid data']);
            break;
        }

        $dataJson = json_encode($data, JSON_UNESCAPED_UNICODE);
        if (strlen($dataJson) > 1048576) { // 1MB limit
            echo json_encode(['error' => 'Draft too large (max 1MB)']);
            break;
        }

        $stmt = $pdo->prepare("INSERT INTO `user_drafts` (client_id, page_key, page_title, draft_data)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE draft_data = VALUES(draft_data), page_title = VALUES(page_title), updated_at = NOW()");
        $stmt->execute([$clientId, $pageKey, mb_substr($pageTitle, 0, 255), $dataJson]);

        echo json_encode(['ok' => true, 'saved' => true]);
        break;

    case 'load':
        $pageKey = filter_input(INPUT_GET, 'key', FILTER_SANITIZE_SPECIAL_CHARS);
        if (!$pageKey) {
            echo json_encode(['error' => 'Missing key']);
            break;
        }

        $stmt = $pdo->prepare("SELECT page_key, page_title, draft_data, updated_at FROM `user_drafts`
            WHERE client_id = ? AND page_key = ? LIMIT 1");
        $stmt->execute([$clientId, $pageKey]);
        $row = $stmt->fetch();

        if (!$row) {
            echo json_encode(['draft' => null]);
        } else {
            echo json_encode([
                'draft' => [
                    'pageKey'    => $row['page_key'],
                    'pageTitle'  => $row['page_title'],
                    'data'       => json_decode($row['draft_data'], true),
                    'updatedAt'  => strtotime($row['updated_at']) * 1000
                ]
            ]);
        }
        break;

    case 'list':
        $stmt = $pdo->prepare("SELECT page_key, page_title, updated_at,
            LENGTH(draft_data) as data_size
            FROM `user_drafts` WHERE client_id = ?
            ORDER BY updated_at DESC LIMIT 50");
        $stmt->execute([$clientId]);
        $rows = $stmt->fetchAll();

        $drafts = array_map(function($r) {
            return [
                'pageKey'   => $r['page_key'],
                'pageTitle' => $r['page_title'],
                'updatedAt' => strtotime($r['updated_at']) * 1000,
                'dataSize'  => (int)$r['data_size']
            ];
        }, $rows);

        echo json_encode(['drafts' => $drafts]);
        break;

    case 'delete':
        requireCSRF();
        $body = json_decode(file_get_contents('php://input'), true);
        $pageKey = trim($body['page_key'] ?? '');
        if (!$pageKey) {
            echo json_encode(['error' => 'Missing page_key']);
            break;
        }

        $stmt = $pdo->prepare("DELETE FROM `user_drafts` WHERE client_id = ? AND page_key = ?");
        $stmt->execute([$clientId, $pageKey]);
        echo json_encode(['ok' => true, 'deleted' => $stmt->rowCount()]);
        break;

    case 'clear':
        requireCSRF();
        $stmt = $pdo->prepare("DELETE FROM `user_drafts` WHERE client_id = ?");
        $stmt->execute([$clientId]);
        echo json_encode(['ok' => true, 'deleted' => $stmt->rowCount()]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action: ' . $action]);
}
