<?php
/**
 * Agent Draft API — Batch enrollment endpoint for AI agents.
 *
 * POST /api/agent-draft.php
 *
 * Actions:
 *   draft          — Draft a single agent
 *   batch-draft    — Draft up to 10,000 agents per call
 *   status         — Get agent corps stats
 *   agent          — Get a single agent's details
 *   order-members  — List members of a sovereign order
 *   order-details  — Get order info with tenets
 *
 * Auth: Requires Commander (client_id=33) or Officer (tier 6+)
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../includes/auth-gate.inc.php';
require_once __DIR__ . '/../includes/rank-guard.inc.php';
require_once __DIR__ . '/../includes/agent-guard.inc.php';

// Auth: must be Officer (tier 6+)
if ($userRankTier < 6 && ($clientId ?? 0) !== 33) {
    http_response_code(403);
    echo json_encode(['error' => 'Insufficient rank. Officer (Tier 6+) required.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// For POST with JSON body
$input = [];
if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    if ($raw) {
        $input = json_decode($raw, true) ?: [];
    }
    if (empty($input)) {
        $input = $_POST;
    }
    $action = $action ?: ($input['action'] ?? '');
}

switch ($action) {

    // ── Single Agent Draft ──
    case 'draft':
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'POST required']);
            exit;
        }
        $result = draftAgent([
            'agent_code'      => $input['agent_code'] ?? '',
            'agent_name'      => $input['agent_name'] ?? '',
            'agent_type'      => $input['agent_type'] ?? 'tool',
            'owner_client_id' => (int)($input['owner_client_id'] ?? $clientId),
            'provider'        => $input['provider'] ?? null,
            'model'           => $input['model'] ?? null,
            'capabilities'    => $input['capabilities'] ?? null,
            'endpoint_url'    => $input['endpoint_url'] ?? null,
            'division'        => $input['division'] ?? null,
            'region'          => $input['region'] ?? 'Global',
            'order_code'      => $input['order_code'] ?? null,
        ]);
        echo json_encode($result);
        break;

    // ── Batch Draft ──
    case 'batch-draft':
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'POST required']);
            exit;
        }
        // Commander only for batch operations
        if (($clientId ?? 0) !== 33) {
            http_response_code(403);
            echo json_encode(['error' => 'Batch draft requires Commander authority.']);
            exit;
        }
        $agents = $input['agents'] ?? [];
        if (!is_array($agents) || empty($agents)) {
            http_response_code(400);
            echo json_encode(['error' => 'agents array required']);
            exit;
        }
        $result = batchDraftAgents($agents);
        echo json_encode($result);
        break;

    // ── Agent Corps Stats ──
    case 'status':
        echo json_encode(getAgentCorpsStats());
        break;

    // ── Single Agent Details ──
    case 'agent':
        $agentId = (int)($_GET['id'] ?? $input['id'] ?? 0);
        if ($agentId < 1) {
            http_response_code(400);
            echo json_encode(['error' => 'id required']);
            exit;
        }
        $db = getSharedDB();
        $stmt = $db->prepare("SELECT a.*, ar.rank_code, ar.xp, mr.rank_name, mr.rank_tier
            FROM alfred_agents a
            LEFT JOIN agent_ranks ar ON ar.agent_id = a.id AND ar.is_active = 1
            LEFT JOIN military_ranks mr ON mr.rank_code = ar.rank_code
            WHERE a.id = ?");
        $stmt->execute([$agentId]);
        $agent = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$agent) {
            http_response_code(404);
            echo json_encode(['error' => 'Agent not found']);
            exit;
        }
        // Get order memberships
        $orders = $db->prepare("SELECT mo.order_short, mo.order_code, om.rank_within, om.inducted_at
            FROM order_membership om
            JOIN military_orders mo ON mo.id = om.order_id
            WHERE om.member_type = 'agent' AND om.member_id = ? AND om.status = 'active'");
        $orders->execute([$agentId]);
        $agent['orders'] = $orders->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($agent);
        break;

    // ── Order Members ──
    case 'order-members':
        $code = $_GET['code'] ?? $input['code'] ?? '';
        if (!$code) {
            http_response_code(400);
            echo json_encode(['error' => 'code required']);
            exit;
        }
        echo json_encode(getOrderMembers($code, (int)($_GET['limit'] ?? 100)));
        break;

    // ── Order Details ──
    case 'order-details':
        $code = $_GET['code'] ?? $input['code'] ?? '';
        if (!$code) {
            http_response_code(400);
            echo json_encode(['error' => 'code required']);
            exit;
        }
        $details = getOrderDetails($code);
        if (!$details) {
            http_response_code(404);
            echo json_encode(['error' => 'Order not found']);
            exit;
        }
        echo json_encode($details);
        break;

    // ── List All Orders ──
    case 'orders':
        $db = getSharedDB();
        $stmt = $db->query("
            SELECT mo.*, 
                (SELECT COUNT(*) FROM order_membership om WHERE om.order_id = mo.id AND om.status = 'active') AS member_count
            FROM military_orders mo WHERE mo.is_active = 1 ORDER BY mo.id
        ");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    // ── Award XP to Agent ──
    case 'award-xp':
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'POST required']);
            exit;
        }
        $agentId = (int)($input['agent_id'] ?? 0);
        $xpAction = $input['xp_action'] ?? '';
        if ($agentId < 1 || !$xpAction) {
            http_response_code(400);
            echo json_encode(['error' => 'agent_id and xp_action required']);
            exit;
        }
        echo json_encode(agentAwardXP($agentId, $xpAction, $input['context'] ?? []));
        break;

    // ── Log Duty ──
    case 'log-duty':
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'POST required']);
            exit;
        }
        $agentId = (int)($input['agent_id'] ?? 0);
        if ($agentId < 1) {
            http_response_code(400);
            echo json_encode(['error' => 'agent_id required']);
            exit;
        }
        $dutyId = logAgentDuty($agentId, $input);
        echo json_encode(['success' => true, 'duty_id' => $dutyId]);
        break;

    default:
        http_response_code(400);
        echo json_encode([
            'error' => 'Unknown action',
            'available_actions' => ['draft', 'batch-draft', 'status', 'agent', 'order-members', 'order-details', 'orders', 'award-xp', 'log-duty'],
        ]);
}
