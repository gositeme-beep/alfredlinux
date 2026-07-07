<?php
/**
 * XP Award API — POST /api/xp-award.php
 * Level 3: Server-side XP award for front-end actions.
 *
 * POST body (JSON): { "action": "pulse_post", "context": { ... } }
 * Response: { "ok": true, "xp_awarded": 5, "total_xp": 105, "rank_up": false }
 */
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'POST required']);
    exit;
}

require_once dirname(__DIR__) . '/includes/auth-gate.inc.php';
require_once dirname(__DIR__) . '/includes/rank-guard.inc.php';

if (empty($clientId)) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Not authenticated']);
    exit;
}

if ($userRankTier < 1) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Not enlisted. Visit /enlist first.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$context = $input['context'] ?? [];

if (!isset(XP_ACTIONS[$action])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Unknown action', 'valid_actions' => array_keys(XP_ACTIONS)]);
    exit;
}

// Rate limit: max 100 XP awards per user per hour
$db = getSharedDB();
$rateCheck = $db->prepare("SELECT COUNT(*) FROM xp_ledger WHERE client_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
$rateCheck->execute([$clientId]);
if ($rateCheck->fetchColumn() >= 100) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'error' => 'Rate limit exceeded (100/hour)']);
    exit;
}

$result = awardXP($clientId, $action, $context);

echo json_encode([
    'ok'         => true,
    'action'     => $action,
    'xp_awarded' => $result['xp_awarded'],
    'total_xp'   => $result['total_xp'],
    'rank_up'    => $result['rank_up'],
    'new_rank'   => $result['new_rank'],
]);
