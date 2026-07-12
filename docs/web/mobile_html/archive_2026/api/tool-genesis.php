<?php
/**
 * Alfred Tool Genesis API — Phase 2: Self-Evolution
 * ──────────────────────────────────────────────────
 * Alfred creates its own tools autonomously.
 * Pipeline: Identify Need → Design → Generate → Test → Register → Deploy → Monitor
 *
 * Endpoints:
 *   POST ?action=identify        → Report a tool gap (manual or auto-detected)
 *   POST ?action=design          → Design a tool specification from a gap
 *   POST ?action=generate        → Generate tool code from spec
 *   POST ?action=test            → Run tests on generated tool
 *   POST ?action=approve         → Approve a tool for deployment
 *   POST ?action=deploy          → Deploy an approved tool
 *   GET  ?action=pipeline        → View genesis pipeline status
 *   GET  ?action=tools-created   → List all tools created by genesis
 *   POST ?action=rollback        → Rollback a deployed tool
 *   GET  ?action=stats           → Genesis statistics
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Internal-Secret');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

function requireAuth() {
    if (empty($_SESSION['logged_in']) || empty($_SESSION['client_id'])) {
        jsonResponse(['error' => 'Authentication required'], 401);
    }
}
function isAdmin() { return !empty($_SESSION['is_admin']) || ($_SESSION['client_id'] ?? 0) === 33; }
function isInternalCall() {
    $s = getenv('INTERNAL_SECRET') ?: '';
    return $s && isset($_SERVER['HTTP_X_INTERNAL_SECRET']) && hash_equals($s, $_SERVER['HTTP_X_INTERNAL_SECRET']);
}

function ensureGenesisSchema() {
    $db = getDB();
    if (!$db) return false;

    $db->exec("CREATE TABLE IF NOT EXISTS alfred_tool_genesis (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        genesis_id      VARCHAR(50) UNIQUE NOT NULL,
        tool_name       VARCHAR(100) NOT NULL,
        display_name    VARCHAR(200) DEFAULT NULL,
        stage           ENUM('identified','designed','generated','tested','approved','deployed','rolled_back') DEFAULT 'identified',
        gap_description TEXT NOT NULL,
        specification   JSON DEFAULT NULL,
        generated_code  LONGTEXT DEFAULT NULL,
        language        VARCHAR(20) DEFAULT 'php',
        test_results    JSON DEFAULT NULL,
        test_passed     BOOLEAN DEFAULT FALSE,
        security_review JSON DEFAULT NULL,
        security_passed BOOLEAN DEFAULT FALSE,
        deployed_at     TIMESTAMP NULL,
        deployed_path   VARCHAR(500) DEFAULT NULL,
        error_rate      DECIMAL(5,2) DEFAULT 0.00,
        usage_count     INT DEFAULT 0,
        created_by      VARCHAR(50) DEFAULT 'ALFRED',
        reviewed_by     VARCHAR(50) DEFAULT NULL,
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_stage (stage),
        INDEX idx_tool (tool_name),
        INDEX idx_created_by (created_by)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    return true;
}

$action = sanitize($_GET['action'] ?? '', 30);
$db = getDB();
if (!$db) jsonResponse(['error' => 'Database unavailable'], 503);
ensureGenesisSchema();

// Rate limit: max 5 new tools per day
function checkGenesisRateLimit() {
    $db = getDB();
    $today = $db->query("SELECT COUNT(*) FROM alfred_tool_genesis WHERE DATE(created_at) = CURDATE()")->fetchColumn();
    if ($today >= 5) {
        jsonResponse(['error' => 'Rate limit: maximum 5 new tools per day', 'today' => (int)$today], 429);
    }
}

switch ($action) {

    case 'identify':
        if (!isInternalCall()) { requireAuth(); if (!isAdmin()) jsonResponse(['error' => 'Admin required'], 403); }
        checkGenesisRateLimit();

        $input = json_decode(file_get_contents('php://input'), true);
        $gap = sanitize($input['gap_description'] ?? '', 1000);
        $toolName = sanitize($input['tool_name'] ?? '', 100);
        if (!$gap || !$toolName) jsonResponse(['error' => 'tool_name and gap_description required'], 400);

        // Validate tool name format
        if (!preg_match('/^[a-z][a-z0-9_]{2,50}$/', $toolName)) {
            jsonResponse(['error' => 'tool_name must be lowercase alphanumeric with underscores, 3-50 chars'], 400);
        }

        $genesisId = 'GEN-' . strtoupper(bin2hex(random_bytes(6)));
        $stmt = $db->prepare("INSERT INTO alfred_tool_genesis (genesis_id, tool_name, display_name, gap_description, created_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$genesisId, $toolName, sanitize($input['display_name'] ?? '', 200) ?: ucwords(str_replace('_', ' ', $toolName)), $gap, sanitize($input['created_by'] ?? 'ALFRED', 50)]);

        jsonResponse(['success' => true, 'genesis_id' => $genesisId, 'stage' => 'identified']);
        break;

    case 'design':
        if (!isInternalCall()) { requireAuth(); if (!isAdmin()) jsonResponse(['error' => 'Admin required'], 403); }

        $input = json_decode(file_get_contents('php://input'), true);
        $genesisId = sanitize($input['genesis_id'] ?? '', 50);
        if (!$genesisId) jsonResponse(['error' => 'genesis_id required'], 400);

        $spec = $input['specification'] ?? null;
        if (!$spec || !is_array($spec)) jsonResponse(['error' => 'specification object required'], 400);

        // Validate spec structure
        $requiredFields = ['parameters', 'returns', 'implementation_approach'];
        foreach ($requiredFields as $f) {
            if (!isset($spec[$f])) jsonResponse(['error' => "specification.{$f} required"], 400);
        }

        $spec['safety_constraints'] = $spec['safety_constraints'] ?? ['no_external_network', 'no_file_system_write', 'timeout_30s'];

        $stmt = $db->prepare("UPDATE alfred_tool_genesis SET specification = ?, language = ?, stage = 'designed' WHERE genesis_id = ? AND stage = 'identified'");
        $stmt->execute([json_encode($spec), sanitize($input['language'] ?? 'php', 20), $genesisId]);

        if ($stmt->rowCount() === 0) jsonResponse(['error' => 'Tool not found or not in identified stage'], 404);
        jsonResponse(['success' => true, 'genesis_id' => $genesisId, 'stage' => 'designed']);
        break;

    case 'generate':
        if (!isInternalCall()) { requireAuth(); if (!isAdmin()) jsonResponse(['error' => 'Admin required'], 403); }

        $input = json_decode(file_get_contents('php://input'), true);
        $genesisId = sanitize($input['genesis_id'] ?? '', 50);
        $code = $input['generated_code'] ?? '';
        if (!$genesisId || !$code) jsonResponse(['error' => 'genesis_id and generated_code required'], 400);

        // Basic security scan — block dangerous patterns
        $dangerous = ['eval(', 'exec(', 'system(', 'passthru(', 'shell_exec(', 'popen(', 'proc_open(', '`', 'unlink(', 'rmdir(', 'file_put_contents('];
        foreach ($dangerous as $d) {
            if (stripos($code, $d) !== false) {
                jsonResponse(['error' => "Security violation: code contains blocked function '{$d}'"], 400);
            }
        }

        $stmt = $db->prepare("UPDATE alfred_tool_genesis SET generated_code = ?, stage = 'generated' WHERE genesis_id = ? AND stage = 'designed'");
        $stmt->execute([$code, $genesisId]);

        if ($stmt->rowCount() === 0) jsonResponse(['error' => 'Tool not found or not in designed stage'], 404);
        jsonResponse(['success' => true, 'genesis_id' => $genesisId, 'stage' => 'generated']);
        break;

    case 'test':
        if (!isInternalCall()) { requireAuth(); if (!isAdmin()) jsonResponse(['error' => 'Admin required'], 403); }

        $input = json_decode(file_get_contents('php://input'), true);
        $genesisId = sanitize($input['genesis_id'] ?? '', 50);
        $testResults = $input['test_results'] ?? null;

        if (!$genesisId || !$testResults) jsonResponse(['error' => 'genesis_id and test_results required'], 400);

        $passed = !empty($input['test_passed']);
        $securityResults = $input['security_review'] ?? ['status' => 'pending'];
        $securityPassed = !empty($input['security_passed']);

        $stmt = $db->prepare("UPDATE alfred_tool_genesis SET test_results = ?, test_passed = ?, security_review = ?, security_passed = ?, stage = 'tested', reviewed_by = ? WHERE genesis_id = ? AND stage = 'generated'");
        $stmt->execute([json_encode($testResults), $passed ? 1 : 0, json_encode($securityResults), $securityPassed ? 1 : 0, sanitize($input['reviewed_by'] ?? 'CIPHER-INSPECTOR', 50), $genesisId]);

        if ($stmt->rowCount() === 0) jsonResponse(['error' => 'Tool not found or not in generated stage'], 404);
        jsonResponse(['success' => true, 'genesis_id' => $genesisId, 'stage' => 'tested', 'test_passed' => $passed, 'security_passed' => $securityPassed]);
        break;

    case 'approve':
        if (!isInternalCall()) { requireAuth(); if (!isAdmin()) jsonResponse(['error' => 'Admin required'], 403); }

        $input = json_decode(file_get_contents('php://input'), true);
        $genesisId = sanitize($input['genesis_id'] ?? '', 50);
        if (!$genesisId) jsonResponse(['error' => 'genesis_id required'], 400);

        // Verify tests and security passed
        $tool = $db->prepare("SELECT test_passed, security_passed FROM alfred_tool_genesis WHERE genesis_id = ? AND stage = 'tested'");
        $tool->execute([$genesisId]);
        $t = $tool->fetch();

        if (!$t) jsonResponse(['error' => 'Tool not found or not in tested stage'], 404);
        if (!$t['test_passed']) jsonResponse(['error' => 'Cannot approve: tests not passed'], 400);
        if (!$t['security_passed']) jsonResponse(['error' => 'Cannot approve: security review not passed'], 400);

        $db->prepare("UPDATE alfred_tool_genesis SET stage = 'approved', reviewed_by = ? WHERE genesis_id = ?")->execute([
            sanitize($input['approved_by'] ?? 'ADMIN', 50), $genesisId
        ]);

        jsonResponse(['success' => true, 'genesis_id' => $genesisId, 'stage' => 'approved']);
        break;

    case 'deploy':
        if (!isInternalCall()) { requireAuth(); if (!isAdmin()) jsonResponse(['error' => 'Admin required'], 403); }

        $input = json_decode(file_get_contents('php://input'), true);
        $genesisId = sanitize($input['genesis_id'] ?? '', 50);
        if (!$genesisId) jsonResponse(['error' => 'genesis_id required'], 400);

        $tool = $db->prepare("SELECT * FROM alfred_tool_genesis WHERE genesis_id = ? AND stage = 'approved'");
        $tool->execute([$genesisId]);
        $t = $tool->fetch();

        if (!$t) jsonResponse(['error' => 'Tool not found or not in approved stage'], 404);

        // Record deployment (actual file writing done by deploy agents)
        $deployedPath = sanitize($input['deployed_path'] ?? "tools/genesis/{$t['tool_name']}", 500);

        $db->prepare("UPDATE alfred_tool_genesis SET stage = 'deployed', deployed_at = NOW(), deployed_path = ? WHERE genesis_id = ?")->execute([$deployedPath, $genesisId]);

        jsonResponse(['success' => true, 'genesis_id' => $genesisId, 'stage' => 'deployed', 'deployed_path' => $deployedPath]);
        break;

    case 'rollback':
        if (!isInternalCall()) { requireAuth(); if (!isAdmin()) jsonResponse(['error' => 'Admin required'], 403); }

        $input = json_decode(file_get_contents('php://input'), true);
        $genesisId = sanitize($input['genesis_id'] ?? '', 50);
        if (!$genesisId) jsonResponse(['error' => 'genesis_id required'], 400);

        $stmt = $db->prepare("UPDATE alfred_tool_genesis SET stage = 'rolled_back' WHERE genesis_id = ? AND stage = 'deployed'");
        $stmt->execute([$genesisId]);

        if ($stmt->rowCount() === 0) jsonResponse(['error' => 'Tool not found or not deployed'], 404);
        jsonResponse(['success' => true, 'genesis_id' => $genesisId, 'stage' => 'rolled_back']);
        break;

    case 'pipeline':
        if (!isInternalCall()) requireAuth();

        $pipeline = $db->query("SELECT genesis_id, tool_name, display_name, stage, language, test_passed, security_passed, error_rate, usage_count, created_by, created_at, deployed_at FROM alfred_tool_genesis ORDER BY FIELD(stage, 'identified','designed','generated','tested','approved','deployed','rolled_back'), created_at DESC")->fetchAll();

        $byStage = [];
        foreach ($pipeline as $p) {
            $byStage[$p['stage']][] = $p;
        }

        jsonResponse(['success' => true, 'pipeline' => $pipeline, 'by_stage' => $byStage, 'total' => count($pipeline)]);
        break;

    case 'tools-created':
        if (!isInternalCall()) requireAuth();

        $deployed = $db->query("SELECT genesis_id, tool_name, display_name, language, usage_count, error_rate, deployed_at, deployed_path, created_by FROM alfred_tool_genesis WHERE stage = 'deployed' ORDER BY deployed_at DESC")->fetchAll();

        jsonResponse(['success' => true, 'tools' => $deployed, 'total' => count($deployed)]);
        break;

    case 'stats':
        if (!isInternalCall()) requireAuth();

        $total = $db->query("SELECT COUNT(*) FROM alfred_tool_genesis")->fetchColumn();
        $byStage = $db->query("SELECT stage, COUNT(*) as c FROM alfred_tool_genesis GROUP BY stage")->fetchAll(PDO::FETCH_KEY_PAIR);
        $byLanguage = $db->query("SELECT language, COUNT(*) as c FROM alfred_tool_genesis GROUP BY language")->fetchAll(PDO::FETCH_KEY_PAIR);
        $avgErrorRate = $db->query("SELECT AVG(error_rate) FROM alfred_tool_genesis WHERE stage = 'deployed'")->fetchColumn();
        $totalUsage = $db->query("SELECT SUM(usage_count) FROM alfred_tool_genesis WHERE stage = 'deployed'")->fetchColumn();

        jsonResponse([
            'success' => true,
            'total_tools' => (int)$total,
            'by_stage' => $byStage,
            'by_language' => $byLanguage,
            'deployed_avg_error_rate' => round($avgErrorRate ?? 0, 2),
            'total_usage' => (int)($totalUsage ?? 0),
        ]);
        break;

    default:
        jsonResponse(['error' => 'Unknown action', 'available_actions' => ['identify','design','generate','test','approve','deploy','rollback','pipeline','tools-created','stats']], 400);
}
