<?php
/**
 * Fleet Management API
 * Handles fleet creation, agent management, deployment, and dashboard
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

requireCSRF();
apiRateLimit(20, 60, 'fleet');

// All fleet endpoints require auth
if (empty($_SESSION['logged_in']) || empty($_SESSION['client_id'])) {
    jsonResponse(['error' => 'Authentication required'], 401);
}

$clientId = (int) $_SESSION['client_id'];

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'create':
        createFleet($clientId);
        break;
    case 'list':
        listFleets($clientId);
        break;
    case 'status':
        getFleetStatus($clientId);
        break;
    case 'deploy':
        deployFleet($clientId);
        break;
    case 'pause':
        pauseFleet($clientId);
        break;
    case 'delete':
        deleteFleet($clientId);
        break;
    case 'add_agent':
        addAgent($clientId);
        break;
    case 'remove_agent':
        removeAgent($clientId);
        break;
    case 'dashboard':
        getDashboard($clientId);
        break;
    default:
        jsonResponse(['error' => 'Invalid action. Valid: create, list, status, deploy, pause, delete, add_agent, remove_agent, dashboard'], 400);
}

// ─── Helpers ────────────────────────────────────────────────────────────────

/**
 * Verify the current user owns the given fleet and return it
 */
function getOwnedFleet($clientId, $fleetId) {
    $db = getDB();
    if (!$db) {
        jsonResponse(['error' => 'Database connection failed'], 500);
    }

    $stmt = $db->prepare("SELECT * FROM alfred_fleets WHERE id = ? AND user_id = ?");
    $stmt->execute([$fleetId, $clientId]);
    $fleet = $stmt->fetch();

    if (!$fleet) {
        jsonResponse(['error' => 'Fleet not found or access denied'], 404);
    }

    return $fleet;
}

/**
 * Update the agent_count column on a fleet
 */
function refreshAgentCount($fleetId) {
    $db = getDB();
    if (!$db) return;

    try {
        $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM alfred_fleet_agents WHERE fleet_id = ?");
        $stmt->execute([$fleetId]);
        $count = $stmt->fetch()['cnt'];

        $stmt = $db->prepare("UPDATE alfred_fleets SET agent_count = ? WHERE id = ?");
        $stmt->execute([$count, $fleetId]);
    } catch (\Exception $e) {
        error_log("Fleet: refreshAgentCount failed: " . $e->getMessage());
    }
}

// ─── Actions ────────────────────────────────────────────────────────────────

/**
 * Create a new fleet
 */
function createFleet($clientId) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'POST required'], 405);
    }

    // Support both form-encoded and JSON body
    $input = $_POST;
    if (empty($input['name'])) {
        $json = json_decode(file_get_contents('php://input'), true);
        if ($json) $input = $json;
    }

    $name = sanitize($input['name'] ?? '', 100);
    $objective = sanitize($input['objective'] ?? $input['description'] ?? '', 2000);
    $strategy = sanitize($input['strategy'] ?? 'parallel', 30);
    $kpisRaw = $input['kpis'] ?? '{}';

    // Validate required fields
    if (empty($name)) {
        jsonResponse(['error' => 'Fleet name is required'], 400);
    }
    if (empty($objective)) {
        jsonResponse(['error' => 'Fleet objective is required'], 400);
    }

    // Validate strategy — map UI-friendly names to API values
    $strategyMap = ['sequential' => 'pipeline', 'adaptive' => 'consensus'];
    if (isset($strategyMap[$strategy])) $strategy = $strategyMap[$strategy];
    $validStrategies = ['parallel', 'pipeline', 'consensus', 'competition'];
    if (!in_array($strategy, $validStrategies)) {
        jsonResponse(['error' => 'Invalid strategy. Choose: ' . implode(', ', $validStrategies)], 400);
    }

    // Validate KPIs JSON
    $kpis = json_decode($kpisRaw, true);
    if ($kpisRaw !== '{}' && $kpis === null) {
        jsonResponse(['error' => 'Invalid KPIs JSON format'], 400);
    }

    $db = getDB();
    if (!$db) {
        jsonResponse(['error' => 'Database connection failed'], 500);
    }

    // Check fleet limits (from user preferences / plan)
    try {
        $stmt = $db->prepare("SELECT notification_settings FROM alfred_user_preferences WHERE user_id = ?");
        $stmt->execute([$clientId]);
        $prefs = $stmt->fetch();

        $fleetLimit = 1; // Default free tier
        if ($prefs && $prefs['notification_settings']) {
            $settings = json_decode($prefs['notification_settings'], true) ?: [];
            $fleetLimit = $settings['fleet_limit'] ?? 1;
        }

        if ($fleetLimit >= 0) {
            $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM alfred_fleets WHERE user_id = ? AND status != 'failed'");
            $stmt->execute([$clientId]);
            $currentCount = $stmt->fetch()['cnt'];

            if ($currentCount >= $fleetLimit) {
                jsonResponse([
                    'error' => "Fleet limit reached ($fleetLimit). Upgrade your plan for more fleets.",
                    'current_count' => $currentCount,
                    'limit' => $fleetLimit,
                ], 403);
            }
        }
    } catch (\Exception $e) {
        error_log("Fleet: plan check failed (non-blocking): " . $e->getMessage());
    }

    try {
        $stmt = $db->prepare("
            INSERT INTO alfred_fleets (user_id, fleet_name, objective, strategy, results, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$clientId, $name, $objective, $strategy, json_encode(['kpis' => $kpis])]);
        $fleetId = $db->lastInsertId();

        error_log("Fleet: Created fleet #$fleetId '$name' for client #$clientId (strategy: $strategy)");

        jsonResponse([
            'success' => true,
            'message' => 'Fleet created successfully',
            'fleet' => [
                'id' => (int) $fleetId,
                'name' => $name,
                'objective' => $objective,
                'strategy' => $strategy,
                'status' => 'idle',
                'agent_count' => 0,
                'kpis' => $kpis,
            ],
        ], 201);
    } catch (\Exception $e) {
        error_log("Fleet: creation failed: " . $e->getMessage());
        jsonResponse(['error' => 'Failed to create fleet'], 500);
    }
}

/**
 * List user's fleets with agent counts and status
 */
function listFleets($clientId) {
    $db = getDB();
    if (!$db) {
        jsonResponse(['error' => 'Database connection failed'], 500);
    }

    $status = sanitize($_GET['status'] ?? '', 20);

    try {
        $sql = "SELECT f.*, 
                       (SELECT COUNT(*) FROM alfred_fleet_agents WHERE fleet_id = f.id) as live_agent_count
                FROM alfred_fleets f
                WHERE f.user_id = ?";
        $params = [$clientId];

        if ($status && in_array($status, ['idle', 'running', 'paused', 'completed', 'failed'])) {
            $sql .= " AND f.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY f.created_at DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $fleets = $stmt->fetchAll();

        // Parse results JSON for each fleet
        foreach ($fleets as &$fleet) {
            $fleet['results'] = $fleet['results'] ? json_decode($fleet['results'], true) : null;
            $fleet['id'] = (int) $fleet['id'];
            $fleet['user_id'] = (int) $fleet['user_id'];
            $fleet['agent_count'] = (int) $fleet['live_agent_count'];
            unset($fleet['live_agent_count']);
        }

        jsonResponse([
            'success' => true,
            'fleets' => $fleets,
            'total' => count($fleets),
        ]);
    } catch (\Exception $e) {
        error_log("Fleet: list failed: " . $e->getMessage());
        jsonResponse(['error' => 'Failed to list fleets'], 500);
    }
}

/**
 * Get detailed fleet status with agents list
 */
function getFleetStatus($clientId) {
    $fleetId = intval($_GET['fleet_id'] ?? $_POST['fleet_id'] ?? 0);
    if ($fleetId <= 0) {
        jsonResponse(['error' => 'fleet_id is required'], 400);
    }

    $fleet = getOwnedFleet($clientId, $fleetId);

    $db = getDB();
    if (!$db) {
        jsonResponse(['error' => 'Database connection failed'], 500);
    }

    try {
        // Get agents
        $stmt = $db->prepare("SELECT * FROM alfred_fleet_agents WHERE fleet_id = ? ORDER BY id ASC");
        $stmt->execute([$fleetId]);
        $agents = $stmt->fetchAll();

        // Parse agent results
        foreach ($agents as &$agent) {
            $agent['result'] = $agent['result'] ? json_decode($agent['result'], true) : null;
            $agent['id'] = (int) $agent['id'];
            $agent['fleet_id'] = (int) $agent['fleet_id'];
        }

        // Parse fleet results
        $fleet['results'] = $fleet['results'] ? json_decode($fleet['results'], true) : null;
        $fleet['id'] = (int) $fleet['id'];

        // Agent status breakdown
        $statusBreakdown = ['queued' => 0, 'running' => 0, 'completed' => 0, 'failed' => 0, 'cancelled' => 0];
        foreach ($agents as $agent) {
            if (isset($statusBreakdown[$agent['status']])) {
                $statusBreakdown[$agent['status']]++;
            }
        }

        jsonResponse([
            'success' => true,
            'fleet' => $fleet,
            'agents' => $agents,
            'agent_count' => count($agents),
            'agent_status_breakdown' => $statusBreakdown,
        ]);
    } catch (\Exception $e) {
        error_log("Fleet: status failed: " . $e->getMessage());
        jsonResponse(['error' => 'Failed to get fleet status'], 500);
    }
}

/**
 * Deploy fleet (set status to running)
 */
function deployFleet($clientId) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'POST required'], 405);
    }

    $fleetId = intval($_POST['fleet_id'] ?? 0);
    if ($fleetId <= 0) {
        jsonResponse(['error' => 'fleet_id is required'], 400);
    }

    $fleet = getOwnedFleet($clientId, $fleetId);

    // Can only deploy idle or paused fleets
    if (!in_array($fleet['status'], ['idle', 'paused'])) {
        jsonResponse(['error' => "Cannot deploy fleet with status '{$fleet['status']}'. Must be idle or paused."], 400);
    }

    $db = getDB();
    if (!$db) {
        jsonResponse(['error' => 'Database connection failed'], 500);
    }

    try {
        // Check fleet has at least one agent
        $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM alfred_fleet_agents WHERE fleet_id = ?");
        $stmt->execute([$fleetId]);
        $agentCount = $stmt->fetch()['cnt'];

        if ($agentCount === 0) {
            jsonResponse(['error' => 'Cannot deploy fleet with no agents. Add agents first.'], 400);
        }

        // Set fleet to running
        $stmt = $db->prepare("UPDATE alfred_fleets SET status = 'running', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$fleetId]);

        // Set queued agents to running
        $stmt = $db->prepare("UPDATE alfred_fleet_agents SET status = 'running', started_at = NOW() WHERE fleet_id = ? AND status = 'queued'");
        $stmt->execute([$fleetId]);

        error_log("Fleet: Deployed fleet #$fleetId for client #$clientId ($agentCount agents)");

        jsonResponse([
            'success' => true,
            'message' => "Fleet '{$fleet['fleet_name']}' deployed with $agentCount agents",
            'fleet_id' => $fleetId,
            'status' => 'running',
            'agents_deployed' => (int) $agentCount,
        ]);
    } catch (\Exception $e) {
        error_log("Fleet: deploy failed: " . $e->getMessage());
        jsonResponse(['error' => 'Failed to deploy fleet'], 500);
    }
}

/**
 * Pause fleet
 */
function pauseFleet($clientId) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'POST required'], 405);
    }

    $fleetId = intval($_POST['fleet_id'] ?? 0);
    if ($fleetId <= 0) {
        jsonResponse(['error' => 'fleet_id is required'], 400);
    }

    $fleet = getOwnedFleet($clientId, $fleetId);

    if ($fleet['status'] !== 'running') {
        jsonResponse(['error' => "Cannot pause fleet with status '{$fleet['status']}'. Must be running."], 400);
    }

    $db = getDB();
    if (!$db) {
        jsonResponse(['error' => 'Database connection failed'], 500);
    }

    try {
        $stmt = $db->prepare("UPDATE alfred_fleets SET status = 'paused', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$fleetId]);

        // Pause running agents
        $stmt = $db->prepare("UPDATE alfred_fleet_agents SET status = 'queued' WHERE fleet_id = ? AND status = 'running'");
        $stmt->execute([$fleetId]);

        error_log("Fleet: Paused fleet #$fleetId for client #$clientId");

        jsonResponse([
            'success' => true,
            'message' => "Fleet '{$fleet['fleet_name']}' paused",
            'fleet_id' => $fleetId,
            'status' => 'paused',
        ]);
    } catch (\Exception $e) {
        error_log("Fleet: pause failed: " . $e->getMessage());
        jsonResponse(['error' => 'Failed to pause fleet'], 500);
    }
}

/**
 * Soft delete fleet (set status to retired via 'failed' + marker)
 */
function deleteFleet($clientId) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'POST required'], 405);
    }

    $fleetId = intval($_POST['fleet_id'] ?? 0);
    if ($fleetId <= 0) {
        jsonResponse(['error' => 'fleet_id is required'], 400);
    }

    $fleet = getOwnedFleet($clientId, $fleetId);

    // Cannot delete a running fleet — must pause first
    if ($fleet['status'] === 'running') {
        jsonResponse(['error' => 'Cannot delete a running fleet. Pause it first.'], 400);
    }

    $db = getDB();
    if (!$db) {
        jsonResponse(['error' => 'Database connection failed'], 500);
    }

    try {
        // Mark fleet results with retired flag
        $results = $fleet['results'] ? json_decode($fleet['results'], true) : [];
        $results['retired'] = true;
        $results['retired_at'] = date('c');
        $results['retired_by'] = $clientId;

        $stmt = $db->prepare("UPDATE alfred_fleets SET status = 'failed', results = ?, completed_at = NOW(), updated_at = NOW() WHERE id = ?");
        $stmt->execute([json_encode($results), $fleetId]);

        // Cancel any remaining agents
        $stmt = $db->prepare("UPDATE alfred_fleet_agents SET status = 'cancelled' WHERE fleet_id = ? AND status IN ('queued', 'running')");
        $stmt->execute([$fleetId]);

        error_log("Fleet: Retired (soft deleted) fleet #$fleetId for client #$clientId");

        jsonResponse([
            'success' => true,
            'message' => "Fleet '{$fleet['fleet_name']}' retired",
            'fleet_id' => $fleetId,
            'status' => 'retired',
        ]);
    } catch (\Exception $e) {
        error_log("Fleet: delete failed: " . $e->getMessage());
        jsonResponse(['error' => 'Failed to delete fleet'], 500);
    }
}

/**
 * Add an agent to a fleet
 */
function addAgent($clientId) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'POST required'], 405);
    }

    $fleetId = intval($_POST['fleet_id'] ?? 0);
    $agentName = sanitize($_POST['agent_name'] ?? '', 100);
    $agentRole = sanitize($_POST['role'] ?? 'generalist', 50);
    $task = sanitize($_POST['task'] ?? '', 2000);
    $skillsRaw = $_POST['skills'] ?? '[]';

    if ($fleetId <= 0) {
        jsonResponse(['error' => 'fleet_id is required'], 400);
    }
    if (empty($agentName)) {
        jsonResponse(['error' => 'agent_name is required'], 400);
    }

    // Validate role
    $validRoles = ['leader', 'specialist', 'generalist', 'reviewer'];
    if (!in_array($agentRole, $validRoles)) {
        jsonResponse(['error' => 'Invalid role. Choose: ' . implode(', ', $validRoles)], 400);
    }

    // Parse skills
    $skills = json_decode($skillsRaw, true);
    if ($skillsRaw !== '[]' && $skills === null) {
        jsonResponse(['error' => 'Invalid skills JSON format'], 400);
    }

    $fleet = getOwnedFleet($clientId, $fleetId);

    // Cannot add agents to a completed/failed fleet
    if (in_array($fleet['status'], ['completed', 'failed'])) {
        jsonResponse(['error' => "Cannot add agents to a fleet with status '{$fleet['status']}'"], 400);
    }

    $db = getDB();
    if (!$db) {
        jsonResponse(['error' => 'Database connection failed'], 500);
    }

    try {
        // Build the result JSON with skills
        $agentResult = null;
        if ($skills && count($skills) > 0) {
            $agentResult = json_encode(['skills' => $skills]);
        }

        $stmt = $db->prepare("
            INSERT INTO alfred_fleet_agents (fleet_id, agent_name, agent_role, task, result, status)
            VALUES (?, ?, ?, ?, ?, 'queued')
        ");
        $stmt->execute([$fleetId, $agentName, $agentRole, $task ?: null, $agentResult]);
        $agentId = $db->lastInsertId();

        // Refresh agent count
        refreshAgentCount($fleetId);

        error_log("Fleet: Added agent '$agentName' (role: $agentRole) to fleet #$fleetId");

        jsonResponse([
            'success' => true,
            'message' => "Agent '$agentName' added to fleet '{$fleet['fleet_name']}'",
            'agent' => [
                'id' => (int) $agentId,
                'fleet_id' => $fleetId,
                'agent_name' => $agentName,
                'agent_role' => $agentRole,
                'task' => $task ?: null,
                'skills' => $skills,
                'status' => 'queued',
            ],
        ], 201);
    } catch (\Exception $e) {
        error_log("Fleet: add agent failed: " . $e->getMessage());
        jsonResponse(['error' => 'Failed to add agent'], 500);
    }
}

/**
 * Remove an agent from a fleet
 */
function removeAgent($clientId) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'POST required'], 405);
    }

    $agentId = intval($_POST['agent_id'] ?? 0);
    if ($agentId <= 0) {
        jsonResponse(['error' => 'agent_id is required'], 400);
    }

    $db = getDB();
    if (!$db) {
        jsonResponse(['error' => 'Database connection failed'], 500);
    }

    try {
        // Get agent and verify fleet ownership
        $stmt = $db->prepare("
            SELECT a.*, f.user_id, f.fleet_name, f.status as fleet_status
            FROM alfred_fleet_agents a
            JOIN alfred_fleets f ON a.fleet_id = f.id
            WHERE a.id = ?
        ");
        $stmt->execute([$agentId]);
        $agent = $stmt->fetch();

        if (!$agent) {
            jsonResponse(['error' => 'Agent not found'], 404);
        }

        if ((int) $agent['user_id'] !== $clientId) {
            jsonResponse(['error' => 'Access denied'], 403);
        }

        // Cannot remove agents from running fleet
        if ($agent['fleet_status'] === 'running' && $agent['status'] === 'running') {
            jsonResponse(['error' => 'Cannot remove a running agent. Pause the fleet first.'], 400);
        }

        $fleetId = (int) $agent['fleet_id'];

        $stmt = $db->prepare("DELETE FROM alfred_fleet_agents WHERE id = ?");
        $stmt->execute([$agentId]);

        // Refresh agent count
        refreshAgentCount($fleetId);

        error_log("Fleet: Removed agent #{$agentId} '{$agent['agent_name']}' from fleet #$fleetId");

        jsonResponse([
            'success' => true,
            'message' => "Agent '{$agent['agent_name']}' removed from fleet '{$agent['fleet_name']}'",
            'agent_id' => $agentId,
            'fleet_id' => $fleetId,
        ]);
    } catch (\Exception $e) {
        error_log("Fleet: remove agent failed: " . $e->getMessage());
        jsonResponse(['error' => 'Failed to remove agent'], 500);
    }
}

/**
 * Dashboard — summary data for fleet management UI
 */
function getDashboard($clientId) {
    $db = getDB();
    if (!$db) {
        jsonResponse(['error' => 'Database connection failed'], 500);
    }

    try {
        // Total fleets by status
        $stmt = $db->prepare("
            SELECT status, COUNT(*) as count 
            FROM alfred_fleets 
            WHERE user_id = ? 
            GROUP BY status
        ");
        $stmt->execute([$clientId]);
        $fleetsByStatus = $stmt->fetchAll();

        $totalFleets = 0;
        $statusMap = [];
        foreach ($fleetsByStatus as $row) {
            $statusMap[$row['status']] = (int) $row['count'];
            $totalFleets += (int) $row['count'];
        }

        // Total agents across all fleets
        $stmt = $db->prepare("
            SELECT COUNT(*) as total_agents 
            FROM alfred_fleet_agents a
            JOIN alfred_fleets f ON a.fleet_id = f.id
            WHERE f.user_id = ?
        ");
        $stmt->execute([$clientId]);
        $totalAgents = (int) $stmt->fetch()['total_agents'];

        // Agents by status
        $stmt = $db->prepare("
            SELECT a.status, COUNT(*) as count 
            FROM alfred_fleet_agents a
            JOIN alfred_fleets f ON a.fleet_id = f.id
            WHERE f.user_id = ?
            GROUP BY a.status
        ");
        $stmt->execute([$clientId]);
        $agentsByStatus = $stmt->fetchAll();

        $agentStatusMap = [];
        foreach ($agentsByStatus as $row) {
            $agentStatusMap[$row['status']] = (int) $row['count'];
        }

        // Active fleets (running)
        $stmt = $db->prepare("
            SELECT f.id, f.fleet_name, f.strategy, f.status, f.progress_percent, f.agent_count, f.created_at
            FROM alfred_fleets f
            WHERE f.user_id = ? AND f.status IN ('running', 'paused')
            ORDER BY f.updated_at DESC
            LIMIT 5
        ");
        $stmt->execute([$clientId]);
        $activeFleets = $stmt->fetchAll();

        // Recent fleet activity (last 10 created or updated)
        $stmt = $db->prepare("
            SELECT f.id, f.fleet_name, f.status, f.strategy, f.agent_count, f.updated_at, f.created_at
            FROM alfred_fleets f
            WHERE f.user_id = ?
            ORDER BY f.updated_at DESC
            LIMIT 10
        ");
        $stmt->execute([$clientId]);
        $recentActivity = $stmt->fetchAll();

        // Mock active calls (voice calls in progress — placeholder)
        $activeCalls = [
            'count' => 0,
            'note' => 'Voice call integration coming soon',
        ];

        // Plan info
        $stmt = $db->prepare("SELECT notification_settings FROM alfred_user_preferences WHERE user_id = ?");
        $stmt->execute([$clientId]);
        $prefs = $stmt->fetch();
        $plan = 'free';
        $fleetLimit = 1;
        if ($prefs && $prefs['notification_settings']) {
            $settings = json_decode($prefs['notification_settings'], true) ?: [];
            $plan = $settings['plan'] ?? 'free';
            $fleetLimit = $settings['fleet_limit'] ?? 1;
        }

        jsonResponse([
            'success' => true,
            'dashboard' => [
                'total_fleets' => $totalFleets,
                'fleets_by_status' => $statusMap,
                'total_agents' => $totalAgents,
                'agents_by_status' => $agentStatusMap,
                'active_calls' => $activeCalls,
                'active_fleets' => $activeFleets,
                'recent_activity' => $recentActivity,
                'plan' => $plan,
                'fleet_limit' => $fleetLimit,
                'fleets_remaining' => $fleetLimit >= 0 ? max(0, $fleetLimit - $totalFleets) : -1,
            ],
        ]);
    } catch (\Exception $e) {
        error_log("Fleet: dashboard failed: " . $e->getMessage());
        jsonResponse(['error' => 'Failed to load dashboard'], 500);
    }
}
