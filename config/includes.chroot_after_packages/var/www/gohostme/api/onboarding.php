<?php
/**
 * Onboarding API
 * Handles saving user onboarding progress, preferences, and first agent creation
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// All onboarding endpoints require auth
if (empty($_SESSION['logged_in']) || empty($_SESSION['client_id'])) {
    jsonResponse(['error' => 'Authentication required'], 401);
}

require_once dirname(__DIR__) . '/includes/api-security.php';

$clientId = (int) $_SESSION['client_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Ensure table exists
ensureOnboardingTable();

switch ($action) {
    case 'status':
        getOnboardingStatus($clientId);
        break;
    case 'save-profile':
        saveProfile($clientId);
        break;
    case 'save-use-cases':
        saveUseCases($clientId);
        break;
    case 'create-first-agent':
        createFirstAgent($clientId);
        break;
    case 'save-channels':
        saveChannels($clientId);
        break;
    case 'complete':
        completeOnboarding($clientId);
        break;
    default:
        jsonResponse(['error' => 'Invalid action. Valid: status, save-profile, save-use-cases, create-first-agent, save-channels, complete'], 400);
}

/**
 * Ensure the onboarding table exists
 */
function ensureOnboardingTable() {
    $db = getDB();
    if (!$db) return;

    $db->exec("
        CREATE TABLE IF NOT EXISTS alfred_onboarding (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL UNIQUE,
            role VARCHAR(50),
            company_name VARCHAR(200),
            company_size VARCHAR(20),
            use_cases JSON,
            first_agent_id INT,
            channels_connected JSON,
            completed_at TIMESTAMP NULL,
            current_step INT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

/**
 * GET status — Check if user has completed onboarding
 */
function getOnboardingStatus($userId) {
    $db = getDB();
    if (!$db) jsonResponse(['error' => 'Database error'], 500);

    $stmt = $db->prepare("SELECT * FROM alfred_onboarding WHERE user_id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();

    if (!$row) {
        jsonResponse([
            'completed' => false,
            'current_step' => 1,
            'exists' => false
        ]);
    }

    jsonResponse([
        'completed' => !empty($row['completed_at']),
        'current_step' => (int) $row['current_step'],
        'exists' => true,
        'role' => $row['role'],
        'company_name' => $row['company_name'],
        'company_size' => $row['company_size'],
        'use_cases' => $row['use_cases'] ? json_decode($row['use_cases'], true) : [],
        'first_agent_id' => $row['first_agent_id'] ? (int) $row['first_agent_id'] : null,
        'channels_connected' => $row['channels_connected'] ? json_decode($row['channels_connected'], true) : [],
        'completed_at' => $row['completed_at']
    ]);
}

/**
 * POST save-profile — Save user profile/preferences from Step 1
 */
function saveProfile($userId) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'POST required'], 405);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) $input = $_POST;

    $role = sanitize($input['role'] ?? '', 50);
    $companyName = sanitize($input['company_name'] ?? '', 200);
    $companySize = sanitize($input['company_size'] ?? '', 20);

    $validRoles = ['developer', 'business_owner', 'marketing', 'customer_support', 'it_devops', 'other'];
    if ($role && !in_array($role, $validRoles)) {
        jsonResponse(['error' => 'Invalid role'], 400);
    }

    $validSizes = ['solo', '2-10', '11-50', '51-200', '200+'];
    if ($companySize && !in_array($companySize, $validSizes)) {
        jsonResponse(['error' => 'Invalid company size'], 400);
    }

    $db = getDB();
    if (!$db) jsonResponse(['error' => 'Database error'], 500);

    $stmt = $db->prepare("
        INSERT INTO alfred_onboarding (user_id, role, company_name, company_size, current_step)
        VALUES (?, ?, ?, ?, 2)
        ON DUPLICATE KEY UPDATE
            role = VALUES(role),
            company_name = VALUES(company_name),
            company_size = VALUES(company_size),
            current_step = GREATEST(current_step, 2)
    ");
    $stmt->execute([$userId, $role, $companyName, $companySize]);

    jsonResponse(['success' => true, 'next_step' => 2]);
}

/**
 * POST save-use-cases — Save selected use cases (Step 2)
 */
function saveUseCases($userId) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'POST required'], 405);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) $input = $_POST;

    $useCases = $input['use_cases'] ?? [];
    if (!is_array($useCases)) {
        jsonResponse(['error' => 'use_cases must be an array'], 400);
    }

    $validCases = [
        'customer_support', 'voice_agent', 'tool_automation',
        'content_generation', 'data_analysis', 'code_assistant',
        'lead_generation', 'appointment_scheduling'
    ];
    $useCases = array_intersect($useCases, $validCases);

    $db = getDB();
    if (!$db) jsonResponse(['error' => 'Database error'], 500);

    $stmt = $db->prepare("
        INSERT INTO alfred_onboarding (user_id, use_cases, current_step)
        VALUES (?, ?, 3)
        ON DUPLICATE KEY UPDATE
            use_cases = VALUES(use_cases),
            current_step = GREATEST(current_step, 3)
    ");
    $stmt->execute([$userId, json_encode($useCases)]);

    jsonResponse(['success' => true, 'next_step' => 3]);
}

/**
 * POST create-first-agent — Create agent from template (Step 3)
 */
function createFirstAgent($userId) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'POST required'], 405);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) $input = $_POST;

    $template = sanitize($input['template'] ?? '', 50);
    $agentName = sanitize($input['agent_name'] ?? '', 100);

    if (!$agentName) {
        jsonResponse(['error' => 'Agent name is required'], 400);
    }

    // Template configurations
    $templates = [
        'customer_support' => [
            'role' => 'specialist',
            'task' => 'Handle customer inquiries, resolve issues, and provide helpful responses based on company knowledge base.'
        ],
        'sales_agent' => [
            'role' => 'specialist',
            'task' => 'Qualify leads, answer product questions, and guide prospects through the sales funnel.'
        ],
        'knowledge_base' => [
            'role' => 'generalist',
            'task' => 'Answer questions using the company knowledge base and documentation.'
        ],
        'voice_receptionist' => [
            'role' => 'specialist',
            'task' => 'Answer phone calls, route callers, take messages, and schedule appointments.'
        ]
    ];

    if (!isset($templates[$template])) {
        jsonResponse(['error' => 'Invalid template'], 400);
    }

    $db = getDB();
    if (!$db) jsonResponse(['error' => 'Database error'], 500);

    try {
        $db->beginTransaction();

        // Create a fleet for the user if they don't have one
        $stmt = $db->prepare("SELECT id FROM alfred_fleets WHERE user_id = ? ORDER BY id ASC LIMIT 1");
        $stmt->execute([$userId]);
        $fleet = $stmt->fetch();

        if (!$fleet) {
            $stmt = $db->prepare("INSERT INTO alfred_fleets (user_id, fleet_name, objective, status, agent_count) VALUES (?, 'My First Fleet', 'Onboarding fleet', 'idle', 1)");
            $stmt->execute([$userId]);
            $fleetId = (int) $db->lastInsertId();
        } else {
            $fleetId = (int) $fleet['id'];
            $db->prepare("UPDATE alfred_fleets SET agent_count = agent_count + 1 WHERE id = ?")->execute([$fleetId]);
        }

        // Create the agent
        $tpl = $templates[$template];
        $stmt = $db->prepare("
            INSERT INTO alfred_fleet_agents (fleet_id, agent_name, agent_role, task, status)
            VALUES (?, ?, ?, ?, 'queued')
        ");
        $stmt->execute([$fleetId, $agentName, $tpl['role'], $tpl['task']]);
        $agentId = (int) $db->lastInsertId();

        // Update onboarding record
        $stmt = $db->prepare("
            INSERT INTO alfred_onboarding (user_id, first_agent_id, current_step)
            VALUES (?, ?, 4)
            ON DUPLICATE KEY UPDATE
                first_agent_id = VALUES(first_agent_id),
                current_step = GREATEST(current_step, 4)
        ");
        $stmt->execute([$userId, $agentId]);

        $db->commit();

        jsonResponse([
            'success' => true,
            'agent_id' => $agentId,
            'fleet_id' => $fleetId,
            'next_step' => 4
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Onboarding create-first-agent error: " . $e->getMessage());
        jsonResponse(['error' => 'Failed to create agent'], 500);
    }
}

/**
 * POST save-channels — Save connected channels (Step 4)
 */
function saveChannels($userId) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'POST required'], 405);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) $input = $_POST;

    $channels = $input['channels'] ?? [];
    if (!is_array($channels)) {
        jsonResponse(['error' => 'channels must be an array'], 400);
    }

    $validChannels = ['web_chat', 'voice', 'api', 'chrome_extension'];
    $channels = array_intersect($channels, $validChannels);

    $db = getDB();
    if (!$db) jsonResponse(['error' => 'Database error'], 500);

    $stmt = $db->prepare("
        INSERT INTO alfred_onboarding (user_id, channels_connected, current_step)
        VALUES (?, ?, 5)
        ON DUPLICATE KEY UPDATE
            channels_connected = VALUES(channels_connected),
            current_step = GREATEST(current_step, 5)
    ");
    $stmt->execute([$userId, json_encode($channels)]);

    jsonResponse(['success' => true, 'next_step' => 5]);
}

/**
 * POST complete — Mark onboarding as complete
 */
function completeOnboarding($userId) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'POST required'], 405);
    }

    $db = getDB();
    if (!$db) jsonResponse(['error' => 'Database error'], 500);

    $stmt = $db->prepare("
        INSERT INTO alfred_onboarding (user_id, completed_at, current_step)
        VALUES (?, NOW(), 5)
        ON DUPLICATE KEY UPDATE
            completed_at = NOW(),
            current_step = 5
    ");
    $stmt->execute([$userId]);

    jsonResponse(['success' => true, 'completed' => true]);
}
