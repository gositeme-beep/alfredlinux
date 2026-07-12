<?php
/**
 * GoSiteMe Military Ranking API
 * 
 * Endpoints:
 *   GET  ?action=roster         - List all active personnel
 *   GET  ?action=rank&user_id=X - Get user's rank  
 *   GET  ?action=check&user_id=X&type=tool&name=search - Check access
 *   GET  ?action=eligible       - List auto-promotion eligible users
 *   POST ?action=promote        - Promote a user (requires auth)
 *   POST ?action=enlist         - Add new user to roster
 *   POST ?action=suspend        - Suspend a user
 */

header('Content-Type: application/json');

// Auth check — only Commander or authenticated sessions
session_start();
require_once __DIR__ . '/../scripts/rank-engine.php';

$engine = new RankEngine();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// For write operations, verify Commander auth
$isCommander = false;
if (isset($_SESSION['uid']) && (int)$_SESSION['uid'] === RankEngine::COMMANDER_CLIENT_ID) {
    $isCommander = true;
}

// API key auth fallback (for internal services)
$apiKey = $_SERVER['HTTP_X_ALFRED_KEY'] ?? '';
if ($apiKey && strlen($apiKey) === 64) {
    // Verify against vault
    $masterKey = @file_get_contents('/home/gositeme/.vault-master-key');
    if ($masterKey && hash_equals(trim($masterKey), $apiKey)) {
        $isCommander = true;
    }
}

try {
    switch ($action) {
        case 'roster':
            $status = $_GET['status'] ?? 'active';
            $allowedStatuses = ['active', 'suspended', 'discharged', 'awol'];
            if (!in_array($status, $allowedStatuses, true)) {
                $status = 'active';
            }
            echo json_encode(['success' => true, 'roster' => $engine->getRoster($status)]);
            break;
            
        case 'rank':
            $userId = (int)($_GET['user_id'] ?? 0);
            if (!$userId) { echo json_encode(['error' => 'user_id required']); break; }
            $rank = $engine->getUserRank($userId);
            echo json_encode(['success' => true, 'rank' => $rank]);
            break;
            
        case 'check':
            $userId = (int)($_GET['user_id'] ?? 0);
            $type = $_GET['type'] ?? '';
            $name = $_GET['name'] ?? '';
            if (!$userId || !$type || !$name) {
                echo json_encode(['error' => 'user_id, type, and name required']);
                break;
            }
            $allowed = $engine->hasAccess($userId, $type, $name);
            echo json_encode(['success' => true, 'user_id' => $userId, 'resource' => "$type:$name", 'allowed' => $allowed]);
            break;
            
        case 'eligible':
            if (!$isCommander) { echo json_encode(['error' => 'Commander access required']); break; }
            echo json_encode(['success' => true, 'eligible' => $engine->checkAutoPromotionEligible()]);
            break;
            
        case 'promote':
            if (!$isCommander) { echo json_encode(['error' => 'Commander access required']); break; }
            $userId = (int)($_POST['user_id'] ?? 0);
            $newRank = $_POST['rank_code'] ?? '';
            $reason = $_POST['reason'] ?? '';
            if (!$userId || !$newRank) {
                echo json_encode(['error' => 'user_id and rank_code required']);
                break;
            }
            $result = $engine->promote($userId, $newRank, RankEngine::COMMANDER_USER_ID, $reason);
            echo json_encode($result);
            break;
            
        case 'enlist':
            if (!$isCommander) { echo json_encode(['error' => 'Commander access required']); break; }
            $name = $_POST['display_name'] ?? '';
            $userId = (int)($_POST['user_id'] ?? 0);
            $clientId = isset($_POST['client_id']) ? (int)$_POST['client_id'] : null;
            $entryPoint = $_POST['entry_point'] ?? 'meta-dome';
            if (!$name || !$userId) {
                echo json_encode(['error' => 'display_name and user_id required']);
                break;
            }
            $result = $engine->enlist($name, $userId, $clientId, RankEngine::COMMANDER_USER_ID, $entryPoint);
            echo json_encode($result);
            break;
            
        case 'suspend':
            if (!$isCommander) { echo json_encode(['error' => 'Commander access required']); break; }
            $userId = (int)($_POST['user_id'] ?? 0);
            $reason = $_POST['reason'] ?? '';
            if (!$userId || !$reason) {
                echo json_encode(['error' => 'user_id and reason required']);
                break;
            }
            $result = $engine->suspend($userId, RankEngine::COMMANDER_USER_ID, $reason);
            echo json_encode($result);
            break;
            
        default:
            echo json_encode(['error' => 'Unknown action', 'available' => ['roster', 'rank', 'check', 'eligible', 'promote', 'enlist', 'suspend']]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal error', 'message' => $e->getMessage()]);
}
