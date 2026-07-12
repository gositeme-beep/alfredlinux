<?php
/**
 * AI Employee Marketplace API
 * Sources from real agent templates (158 across 22 categories)
 * Hiring actually deploys agents to user's fleet
 *
 * GET  ?action=list      — Browse AI employees (with category, search, role filters)
 * GET  ?action=get&id=X  — Get employee detail by template ID
 * POST ?action=hire       — Hire an AI employee (deploys to fleet)
 * GET  ?action=my_team    — List user's hired employees
 * GET  ?action=stats      — Real deployment stats
 * GET  ?action=categories — List all categories
 */
define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../includes/agent-template-data.inc.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

session_start();
$clientId = (int) ($_SESSION['client_id'] ?? 0);
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':       listEmployees(); break;
    case 'get':        getEmployee(); break;
    case 'hire':       hireEmployee($clientId); break;
    case 'my_team':    myTeam($clientId); break;
    case 'stats':      getStats(); break;
    case 'categories': getCategories(); break;
    default:
        echo json_encode(['error' => 'Invalid action. Valid: list, get, hire, my_team, stats, categories']);
}

function listEmployees() {
    global $AGENT_TEMPLATES, $AGENT_CATEGORIES;

    $category = trim($_GET['category'] ?? '');
    $search   = strtolower(trim($_GET['q'] ?? ''));
    $role     = trim($_GET['role'] ?? '');
    $diff     = trim($_GET['difficulty'] ?? '');
    $sort     = trim($_GET['sort'] ?? 'name');

    $employees = $AGENT_TEMPLATES;

    // Category filter
    if ($category && $category !== 'all') {
        $employees = array_filter($employees, fn($t) => $t['category'] === $category);
    }

    // Search
    if ($search) {
        $employees = array_filter($employees, function($t) use ($search) {
            return str_contains(strtolower($t['name']), $search)
                || str_contains(strtolower($t['description']), $search)
                || in_array($search, array_map('strtolower', $t['tags']));
        });
    }

    // Role filter
    if ($role && in_array($role, ['specialist', 'coordinator', 'analyst', 'reviewer', 'leader'])) {
        $employees = array_filter($employees, fn($t) => $t['agent_role'] === $role);
    }

    // Difficulty filter
    if ($diff && in_array($diff, ['beginner', 'intermediate', 'advanced'])) {
        $employees = array_filter($employees, fn($t) => $t['difficulty'] === $diff);
    }

    // Sort
    $employees = array_values($employees);
    usort($employees, function($a, $b) use ($sort) {
        return match($sort) {
            'name'       => strcmp($a['name'], $b['name']),
            'role'       => strcmp($a['agent_role'], $b['agent_role']),
            'difficulty' => array_search($a['difficulty'], ['beginner','intermediate','advanced'])
                          - array_search($b['difficulty'], ['beginner','intermediate','advanced']),
            'category'   => strcmp($a['category'], $b['category']),
            'tools'      => count($b['tools']) - count($a['tools']),
            default      => strcmp($a['name'], $b['name']),
        };
    });

    // Enrich with category label and color
    foreach ($employees as &$e) {
        $cat = $AGENT_CATEGORIES[$e['category']] ?? null;
        $e['category_label'] = $cat['label'] ?? $e['category'];
        $e['category_color'] = $cat['color'] ?? '#6c5ce7';
        $e['tool_count'] = count($e['tools']);
    }

    echo json_encode([
        'success'   => true,
        'employees' => $employees,
        'total'     => count($employees),
    ]);
}

function getEmployee() {
    global $AGENT_TEMPLATES, $AGENT_CATEGORIES;
    $id = trim($_GET['id'] ?? '');
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Employee id required']);
        return;
    }

    $found = null;
    foreach ($AGENT_TEMPLATES as $t) {
        if ($t['id'] === $id) { $found = $t; break; }
    }
    if (!$found) {
        http_response_code(404);
        echo json_encode(['error' => 'Employee not found']);
        return;
    }

    $cat = $AGENT_CATEGORIES[$found['category']] ?? null;
    $found['category_label'] = $cat['label'] ?? $found['category'];
    $found['category_color'] = $cat['color'] ?? '#6c5ce7';
    $found['tool_count'] = count($found['tools']);

    echo json_encode(['success' => true, 'employee' => $found]);
}

function hireEmployee($clientId) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'POST required']);
        return;
    }
    if (!$clientId) {
        http_response_code(401);
        echo json_encode(['error' => 'Login required to hire AI employees']);
        return;
    }

    global $AGENT_TEMPLATES;
    $templateId = trim($_POST['template_id'] ?? '');
    if (!$templateId) {
        http_response_code(400);
        echo json_encode(['error' => 'template_id required']);
        return;
    }

    // Find template
    $template = null;
    foreach ($AGENT_TEMPLATES as $t) {
        if ($t['id'] === $templateId) { $template = $t; break; }
    }
    if (!$template) {
        http_response_code(404);
        echo json_encode(['error' => 'AI employee not found']);
        return;
    }

    $db = getDB();
    if (!$db) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed']);
        return;
    }

    try {
        $db->beginTransaction();

        // Get or create user's fleet
        $stmt = $db->prepare("SELECT id, fleet_name FROM alfred_fleets WHERE user_id = ? ORDER BY id ASC LIMIT 1");
        $stmt->execute([$clientId]);
        $fleet = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fleet) {
            $stmt = $db->prepare("INSERT INTO alfred_fleets (user_id, fleet_name, objective, status, strategy) VALUES (?, 'My Team', 'AI Employee team', 'idle', 'parallel')");
            $stmt->execute([$clientId]);
            $fleetId   = (int) $db->lastInsertId();
            $fleetName = 'My Team';
        } else {
            $fleetId   = (int) $fleet['id'];
            $fleetName = $fleet['fleet_name'];
        }

        // Check if already hired (same template in same fleet)
        $stmt = $db->prepare("SELECT id FROM alfred_fleet_agents WHERE fleet_id = ? AND agent_name = ?");
        $stmt->execute([$fleetId, $template['name']]);
        if ($stmt->fetch()) {
            $db->rollBack();
            echo json_encode(['success' => true, 'already_hired' => true, 'message' => $template['name'] . ' is already on your team!', 'fleet_name' => $fleetName]);
            return;
        }

        // Deploy agent to fleet
        $resultPayload = json_encode([
            'template_id' => $template['id'],
            'tools'       => $template['tools'],
            'config'      => $template['config'],
            'tags'        => $template['tags'],
        ]);

        $stmt = $db->prepare("INSERT INTO alfred_fleet_agents (fleet_id, agent_name, agent_role, task, result, status) VALUES (?, ?, ?, ?, ?, 'queued')");
        $stmt->execute([$fleetId, $template['name'], $template['agent_role'], $template['default_task'], $resultPayload]);
        $agentId = (int) $db->lastInsertId();

        // Update fleet agent count
        $stmt = $db->prepare("UPDATE alfred_fleets SET agent_count = (SELECT COUNT(*) FROM alfred_fleet_agents WHERE fleet_id = ?) WHERE id = ?");
        $stmt->execute([$fleetId, $fleetId]);

        // Track deployment
        ensureDeploymentTable($db);
        $stmt = $db->prepare("INSERT INTO alfred_template_deployments (user_id, template_id, agent_id, fleet_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$clientId, $template['id'], $agentId, $fleetId]);

        $db->commit();

        echo json_encode([
            'success'    => true,
            'hired'      => true,
            'message'    => $template['name'] . ' has been hired and deployed to "' . $fleetName . '"!',
            'agent_id'   => $agentId,
            'fleet_id'   => $fleetId,
            'fleet_name' => $fleetName,
            'template'   => $template['id'],
        ]);

    } catch (\Exception $e) {
        $db->rollBack();
        error_log("Marketplace hire failed: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Hiring failed. Please try again.']);
    }
}

function myTeam($clientId) {
    if (!$clientId) {
        echo json_encode(['success' => true, 'employees' => [], 'count' => 0]);
        return;
    }
    $db = getDB();
    if (!$db) {
        echo json_encode(['success' => true, 'employees' => [], 'count' => 0]);
        return;
    }

    try {
        ensureDeploymentTable($db);
        $stmt = $db->prepare("
            SELECT d.template_id, d.agent_id, d.fleet_id, d.created_at as hired_at,
                   a.agent_name, a.agent_role, a.status as agent_status
            FROM alfred_template_deployments d
            LEFT JOIN alfred_fleet_agents a ON a.id = d.agent_id
            WHERE d.user_id = ?
            ORDER BY d.created_at DESC
        ");
        $stmt->execute([$clientId]);
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'employees' => $employees, 'count' => count($employees)]);
    } catch (\Exception $e) {
        echo json_encode(['success' => true, 'employees' => [], 'count' => 0]);
    }
}

function getStats() {
    global $AGENT_TEMPLATES, $AGENT_CATEGORIES;
    $stats = [
        'total_employees'  => count($AGENT_TEMPLATES),
        'total_categories' => count($AGENT_CATEGORIES) - 1, // minus 'all'
        'total_hired'      => 0,
    ];

    $db = getDB();
    if ($db) {
        try {
            ensureDeploymentTable($db);
            $stats['total_hired'] = (int) $db->query("SELECT COUNT(*) FROM alfred_template_deployments")->fetchColumn();
        } catch (\Exception $e) {}
    }

    echo json_encode(['success' => true, 'stats' => $stats]);
}

function getCategories() {
    global $AGENT_CATEGORIES;
    $cats = $AGENT_CATEGORIES;
    unset($cats['all']);
    echo json_encode(['success' => true, 'categories' => $cats]);
}

function ensureDeploymentTable($db) {
    static $checked = false;
    if ($checked) return;
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS alfred_template_deployments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            template_id VARCHAR(50) NOT NULL,
            agent_id INT NOT NULL,
            fleet_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_atd_user (user_id),
            INDEX idx_atd_template (template_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $checked = true;
    } catch (\Exception $e) {
        error_log("Deployment table creation: " . $e->getMessage());
    }
}
