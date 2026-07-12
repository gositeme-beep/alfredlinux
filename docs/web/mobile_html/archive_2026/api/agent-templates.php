<?php
/**
 * Agent Templates API
 * Handles template listing, detail retrieval, deployment, and tracking
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// ── Template Data (shared source of truth) ──────────────────────────────────
require_once __DIR__ . '/../includes/agent-template-data.inc.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

function getTemplates() {
    global $AGENT_TEMPLATES;
    return $AGENT_TEMPLATES;
}

function getCategoryMeta() {
    global $AGENT_CATEGORIES;
    // Remove the 'all' pseudo-category for API responses
    $cats = $AGENT_CATEGORIES;
    unset($cats['all']);
    return $cats;
}

// ─── Routing ────────────────────────────────────────────────────────────────

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

switch ($action) {
    case 'list':
        handleList();
        break;
    case 'get':
        handleGet();
        break;
    case 'deploy':
        handleDeploy();
        break;
    case 'popular':
        handlePopular();
        break;
    case 'user-agents':
        handleUserAgents();
        break;
    default:
        jsonResponse(['error' => 'Invalid action. Valid: list, get, deploy, popular, user-agents'], 400);
}

// ─── Handlers ───────────────────────────────────────────────────────────────

function handleList() {
    $templates  = getTemplates();
    $categories = getCategoryMeta();

    // Optional category filter
    $cat = $_GET['category'] ?? '';
    if ($cat) {
        $templates = array_values(array_filter($templates, fn($t) => $t['category'] === $cat));
    }

    // Optional search
    $q = strtolower(trim($_GET['q'] ?? ''));
    if ($q) {
        $templates = array_values(array_filter($templates, function ($t) use ($q) {
            return str_contains(strtolower($t['name']), $q)
                || str_contains(strtolower($t['description']), $q)
                || in_array($q, $t['tags']);
        }));
    }

    jsonResponse([
        'success'    => true,
        'count'      => count($templates),
        'categories' => $categories,
        'templates'  => $templates,
    ]);
}

function handleGet() {
    $id = $_GET['id'] ?? '';
    if (!$id) {
        jsonResponse(['error' => 'Template id is required'], 400);
    }

    $templates = getTemplates();
    $found = null;
    foreach ($templates as $t) {
        if ($t['id'] === $id) { $found = $t; break; }
    }

    if (!$found) {
        jsonResponse(['error' => 'Template not found'], 404);
    }

    $categories = getCategoryMeta();
    $found['category_label'] = $categories[$found['category']]['label'] ?? $found['category'];

    jsonResponse(['success' => true, 'template' => $found]);
}

function handleDeploy() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'POST required'], 405);
    }

    if (empty($_SESSION['logged_in']) || empty($_SESSION['client_id'])) {
        jsonResponse(['error' => 'Authentication required'], 401);
    }

    $clientId   = (int) $_SESSION['client_id'];
    $templateId = trim($_POST['template_id'] ?? '');

    if (!$templateId) {
        jsonResponse(['error' => 'template_id is required'], 400);
    }

    // Find template
    $templates = getTemplates();
    $template  = null;
    foreach ($templates as $t) {
        if ($t['id'] === $templateId) { $template = $t; break; }
    }
    if (!$template) {
        jsonResponse(['error' => 'Template not found'], 404);
    }

    $db = getDB();
    if (!$db) {
        jsonResponse(['error' => 'Database connection failed'], 500);
    }

    try {
        $db->beginTransaction();

        // Check if user has any fleet — create "Default Fleet" if not
        $stmt = $db->prepare("SELECT id, fleet_name FROM alfred_fleets WHERE user_id = ? ORDER BY id ASC LIMIT 1");
        $stmt->execute([$clientId]);
        $fleet = $stmt->fetch();

        if (!$fleet) {
            $stmt = $db->prepare("INSERT INTO alfred_fleets (user_id, fleet_name, objective, status, strategy) VALUES (?, 'Default Fleet', 'Auto-created fleet for template agents', 'idle', 'parallel')");
            $stmt->execute([$clientId]);
            $fleetId   = (int) $db->lastInsertId();
            $fleetName = 'Default Fleet';
        } else {
            $fleetId   = (int) $fleet['id'];
            $fleetName = $fleet['fleet_name'];
        }

        // Build result JSON with template metadata
        $resultPayload = json_encode([
            'template_id' => $template['id'],
            'tools'       => $template['tools'],
            'config'      => $template['config'],
            'tags'        => $template['tags'],
        ]);

        // Insert agent
        $stmt = $db->prepare("
            INSERT INTO alfred_fleet_agents (fleet_id, agent_name, agent_role, task, result, status)
            VALUES (?, ?, ?, ?, ?, 'queued')
        ");
        $stmt->execute([
            $fleetId,
            $template['name'],
            $template['agent_role'],
            $template['default_task'],
            $resultPayload,
        ]);
        $agentId = (int) $db->lastInsertId();

        // Refresh agent count
        $stmt = $db->prepare("UPDATE alfred_fleets SET agent_count = (SELECT COUNT(*) FROM alfred_fleet_agents WHERE fleet_id = ?) WHERE id = ?");
        $stmt->execute([$fleetId, $fleetId]);

        // Track deployment
        ensureDeploymentTable($db);
        $stmt = $db->prepare("INSERT INTO alfred_template_deployments (user_id, template_id, agent_id, fleet_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$clientId, $template['id'], $agentId, $fleetId]);

        $db->commit();

        jsonResponse([
            'success'    => true,
            'message'    => "'{$template['name']}' deployed to fleet '{$fleetName}'",
            'agent_id'   => $agentId,
            'fleet_id'   => $fleetId,
            'fleet_name' => $fleetName,
            'template'   => $template['id'],
        ], 201);

    } catch (\Exception $e) {
        $db->rollBack();
        error_log("Template deploy failed: " . $e->getMessage());
        jsonResponse(['error' => 'Deployment failed. Please try again.'], 500);
    }
}

function handlePopular() {
    $db = getDB();
    if (!$db) {
        // Fallback: return first 10 templates
        jsonResponse(['success' => true, 'templates' => array_slice(getTemplates(), 0, 10)]);
    }

    try {
        ensureDeploymentTable($db);
        $stmt = $db->query("SELECT template_id, COUNT(*) as deploy_count FROM alfred_template_deployments GROUP BY template_id ORDER BY deploy_count DESC LIMIT 10");
        $rows = $stmt->fetchAll();

        $templates = getTemplates();
        $indexed   = [];
        foreach ($templates as $t) {
            $indexed[$t['id']] = $t;
        }

        $result = [];
        foreach ($rows as $r) {
            if (isset($indexed[$r['template_id']])) {
                $t = $indexed[$r['template_id']];
                $t['deploy_count'] = (int) $r['deploy_count'];
                $result[] = $t;
            }
        }

        // Fill up to 10 with remaining templates if not enough deployments
        if (count($result) < 10) {
            $usedIds = array_column($result, 'id');
            foreach ($templates as $t) {
                if (!in_array($t['id'], $usedIds)) {
                    $t['deploy_count'] = 0;
                    $result[] = $t;
                    if (count($result) >= 10) break;
                }
            }
        }

        jsonResponse(['success' => true, 'templates' => $result]);
    } catch (\Exception $e) {
        jsonResponse(['success' => true, 'templates' => array_slice(getTemplates(), 0, 10)]);
    }
}

function handleUserAgents() {
    if (empty($_SESSION['logged_in']) || empty($_SESSION['client_id'])) {
        jsonResponse(['error' => 'Authentication required'], 401);
    }

    $clientId = (int) $_SESSION['client_id'];
    $db = getDB();
    if (!$db) {
        jsonResponse(['error' => 'Database connection failed'], 500);
    }

    try {
        ensureDeploymentTable($db);
        $stmt = $db->prepare("
            SELECT d.template_id, d.agent_id, d.fleet_id, d.created_at,
                   a.agent_name, a.agent_role, a.status as agent_status
            FROM alfred_template_deployments d
            LEFT JOIN alfred_fleet_agents a ON a.id = d.agent_id
            WHERE d.user_id = ?
            ORDER BY d.created_at DESC
        ");
        $stmt->execute([$clientId]);
        $agents = $stmt->fetchAll();

        jsonResponse(['success' => true, 'count' => count($agents), 'agents' => $agents]);
    } catch (\Exception $e) {
        error_log("Template user-agents error: " . $e->getMessage());
        jsonResponse(['error' => 'Failed to fetch agents'], 500);
    }
}

// ─── Ensure deployment tracking table exists ────────────────────────────────

function ensureDeploymentTable($db) {
    static $checked = false;
    if ($checked) return;

    try {
        $db->exec("
            CREATE TABLE IF NOT EXISTS alfred_template_deployments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                template_id VARCHAR(50) NOT NULL,
                agent_id INT NOT NULL,
                fleet_id INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_atd_user (user_id),
                INDEX idx_atd_template (template_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $checked = true;
    } catch (\Exception $e) {
        error_log("Template table creation: " . $e->getMessage());
    }
}
