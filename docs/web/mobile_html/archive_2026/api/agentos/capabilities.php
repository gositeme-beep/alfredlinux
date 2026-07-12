<?php
/**
 * GSM Alfred OS — Capability Graph API v1.0
 * Typed capability registry with risk metadata
 *
 * Endpoints:
 *   GET    ?action=list            — List all capabilities (filterable)
 *   GET    ?action=get&id=X        — Get single capability
 *   POST   ?action=create          — Register a new capability
 *   POST   ?action=update          — Update capability metadata
 *   POST   ?action=toggle&id=X     — Enable/disable capability
 *   GET    ?action=graph           — Get dependency graph
 *   POST   ?action=seed            — Seed default capabilities from tools.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Internal-Secret');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

define('GOSITEME_API', true);
require_once __DIR__ . '/bootstrap.php';
agentos_ensure_schema();

$auth = agentos_auth();
$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':    handleList($auth); break;
    case 'get':     handleGet($auth); break;
    case 'create':  handleCreate($auth); break;
    case 'update':  handleUpdate($auth); break;
    case 'toggle':  handleToggle($auth); break;
    case 'graph':   handleGraph($auth); break;
    case 'seed':    handleSeed($auth); break;
    default:        agentos_error('Unknown action');
}

function handleList(array $auth): void {
    $pdo = agentos_pdo();
    $where = ['1=1'];
    $params = [];

    if (isset($_GET['category'])) {
        $where[] = 'category=?';
        $params[] = $_GET['category'];
    }
    if (isset($_GET['type'])) {
        $where[] = 'capability_type=?';
        $params[] = $_GET['type'];
    }
    if (isset($_GET['risk'])) {
        $where[] = 'risk_level=?';
        $params[] = $_GET['risk'];
    }
    if (isset($_GET['provider'])) {
        $where[] = 'provider=?';
        $params[] = $_GET['provider'];
    }
    if (!isset($_GET['include_disabled'])) {
        $where[] = 'enabled=1';
    }
    if (isset($_GET['search'])) {
        $where[] = '(capability_id LIKE ? OR display_name LIKE ? OR description LIKE ?)';
        $term = '%' . mb_substr($_GET['search'], 0, 100) . '%';
        $params = array_merge($params, [$term, $term, $term]);
    }

    $sql = "SELECT capability_id, display_name, description, capability_type, category, 
            provider, risk_level, requires_simulation, requires_approval, enabled,
            input_schema, output_schema, endpoint, timeout_ms
            FROM agentos_capabilities WHERE " . implode(' AND ', $where) . " ORDER BY category, capability_id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $caps = $stmt->fetchAll();

    // Parse JSON fields
    foreach ($caps as &$cap) {
        $cap['input_schema'] = json_decode($cap['input_schema'] ?? 'null', true);
        $cap['output_schema'] = json_decode($cap['output_schema'] ?? 'null', true);
    }

    agentos_respond([
        'ok' => true,
        'count' => count($caps),
        'capabilities' => $caps,
    ]);
}

function handleGet(array $auth): void {
    $id = preg_replace('/[^a-zA-Z0-9_.-]/', '', $_GET['id'] ?? '');
    if (!$id) agentos_error('id required');

    $pdo = agentos_pdo();
    $stmt = $pdo->prepare("SELECT * FROM agentos_capabilities WHERE capability_id=?");
    $stmt->execute([$id]);
    $cap = $stmt->fetch();
    if (!$cap) agentos_error('Capability not found', 404);

    $cap['input_schema'] = json_decode($cap['input_schema'] ?? 'null', true);
    $cap['output_schema'] = json_decode($cap['output_schema'] ?? 'null', true);
    $cap['dependencies'] = json_decode($cap['dependencies'] ?? '[]', true);

    // Get skills that use this capability
    $stmt = $pdo->prepare("SELECT s.skill_id, s.display_name FROM agentos_skill_steps ss 
        JOIN agentos_skills s ON s.skill_id = ss.skill_id 
        WHERE ss.capability_id=? GROUP BY s.skill_id");
    $stmt->execute([$id]);
    $cap['used_by_skills'] = $stmt->fetchAll();

    // Get recent usage from audit log
    $stmt = $pdo->prepare("SELECT COUNT(*) as uses, 
        SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as successes,
        AVG(duration_ms) as avg_ms
        FROM agentos_audit_log WHERE capability_id=? AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stmt->execute([$id]);
    $cap['usage_stats'] = $stmt->fetch();

    agentos_respond(['ok' => true, 'capability' => $cap]);
}

function handleCreate(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || empty($input['capability_id'])) {
        agentos_error('capability_id is required');
    }

    $pdo = agentos_pdo();
    $capId = preg_replace('/[^a-zA-Z0-9_.-]/', '', mb_substr($input['capability_id'], 0, 100));

    // Validate risk level
    $validRisks = ['low', 'medium', 'high', 'critical'];
    $riskLevel = in_array($input['risk_level'] ?? 'low', $validRisks) ? $input['risk_level'] : 'low';

    $validTypes = ['action', 'query', 'transform', 'control', 'perception', 'communication'];
    $capType = in_array($input['capability_type'] ?? 'action', $validTypes) ? $input['capability_type'] : 'action';

    $stmt = $pdo->prepare("INSERT INTO agentos_capabilities 
        (capability_id, display_name, description, capability_type, category, 
         provider, risk_level, requires_simulation, requires_approval,
         input_schema, output_schema, dependencies, endpoint, timeout_ms, cooldown_ms)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->execute([
        $capId,
        mb_substr($input['display_name'] ?? $capId, 0, 200),
        mb_substr($input['description'] ?? '', 0, 1000),
        $capType,
        mb_substr($input['category'] ?? 'general', 0, 100),
        mb_substr($input['provider'] ?? 'native', 0, 50),
        $riskLevel,
        !empty($input['requires_simulation']) ? 1 : 0,
        !empty($input['requires_approval']) ? 1 : 0,
        json_encode($input['input_schema'] ?? null),
        json_encode($input['output_schema'] ?? null),
        json_encode($input['dependencies'] ?? []),
        $input['endpoint'] ?? null,
        (int)($input['timeout_ms'] ?? 30000),
        (int)($input['cooldown_ms'] ?? 0),
    ]);

    agentos_audit([
        'agent_id' => 'system', 'user_id' => $auth['user_id'],
        'action_type' => 'capability_created', 'capability_id' => $capId,
        'status' => 'completed', 'input' => ['capability_id' => $capId],
    ]);

    agentos_respond(['ok' => true, 'capability_id' => $capId], 201);
}

function handleUpdate(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $capId = preg_replace('/[^a-zA-Z0-9_.-]/', '', $input['capability_id'] ?? '');
    if (!$capId) agentos_error('capability_id required');

    $pdo = agentos_pdo();

    // Build dynamic SET clause from allowed fields
    $allowed = ['display_name', 'description', 'capability_type', 'category', 'provider',
                'risk_level', 'requires_simulation', 'requires_approval', 'endpoint', 'timeout_ms', 'cooldown_ms'];
    $sets = [];
    $params = [];

    foreach ($allowed as $field) {
        if (isset($input[$field])) {
            $sets[] = "{$field}=?";
            $params[] = $input[$field];
        }
    }

    // JSON fields
    foreach (['input_schema', 'output_schema', 'dependencies'] as $jsonField) {
        if (isset($input[$jsonField])) {
            $sets[] = "{$jsonField}=?";
            $params[] = json_encode($input[$jsonField]);
        }
    }

    if (empty($sets)) agentos_error('No fields to update');

    $params[] = $capId;
    $sql = "UPDATE agentos_capabilities SET " . implode(', ', $sets) . " WHERE capability_id=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    agentos_cache_set('capabilities_list', null, 0); // Invalidate cache

    agentos_respond(['ok' => true, 'capability_id' => $capId, 'updated' => count($sets)]);
}

function handleToggle(array $auth): void {
    $id = preg_replace('/[^a-zA-Z0-9_.-]/', '', $_GET['id'] ?? '');
    $pdo = agentos_pdo();
    $pdo->prepare("UPDATE agentos_capabilities SET enabled = NOT enabled WHERE capability_id=?")->execute([$id]);

    $stmt = $pdo->prepare("SELECT enabled FROM agentos_capabilities WHERE capability_id=?");
    $stmt->execute([$id]);
    $enabled = (bool)$stmt->fetchColumn();

    agentos_cache_set('capabilities_list', null, 0);
    agentos_respond(['ok' => true, 'capability_id' => $id, 'enabled' => $enabled]);
}

function handleGraph(array $auth): void {
    $pdo = agentos_pdo();
    $stmt = $pdo->query("SELECT capability_id, display_name, category, dependencies, 
        capability_type, risk_level FROM agentos_capabilities WHERE enabled=1");
    $caps = $stmt->fetchAll();

    $nodes = [];
    $edges = [];

    foreach ($caps as $cap) {
        $nodes[] = [
            'id' => $cap['capability_id'],
            'label' => $cap['display_name'],
            'group' => $cap['category'],
            'type' => $cap['capability_type'],
            'risk' => $cap['risk_level'],
        ];

        $deps = json_decode($cap['dependencies'] ?? '[]', true);
        foreach ($deps as $dep) {
            $edges[] = ['from' => $dep, 'to' => $cap['capability_id']];
        }
    }

    agentos_respond(['ok' => true, 'nodes' => $nodes, 'edges' => $edges]);
}

function handleSeed(array $auth): void {
    $pdo = agentos_pdo();

    // Seed core Alfred OS capabilities from existing platform
    $capabilities = [
        // ── Code ──
        ['code.read', 'Read Code', 'Read files from workspace', 'query', 'code', 'native', 'low', '/api/tools.php', false, false],
        ['code.write', 'Write Code', 'Create or modify source files', 'action', 'code', 'native', 'medium', '/api/tools.php', false, false],
        ['code.analyze', 'Analyze Code', 'Static analysis, linting, patterns', 'query', 'code', 'native', 'low', '/api/tools.php', false, false],
        ['code.deploy', 'Deploy Code', 'Deploy to staging or production', 'action', 'code', 'native', 'critical', '/api/tools.php', true, true],
        ['code.test', 'Run Tests', 'Execute test suites', 'action', 'code', 'native', 'low', '/api/tools.php', false, false],
        ['code.refactor', 'Refactor Code', 'Restructure/optimize code', 'action', 'code', 'native', 'medium', '/api/tools.php', false, false],

        // ── Communication ──
        ['comm.chat', 'Chat', 'Send/receive chat messages', 'communication', 'communication', 'native', 'low', '/api/alfred-chat.php', false, false],
        ['comm.voice', 'Voice Call', 'Initiate or manage voice calls', 'communication', 'communication', 'native', 'medium', '/api/voice.php', false, false],
        ['comm.email', 'Send Email', 'Send transactional emails', 'communication', 'communication', 'native', 'medium', '/api/email.php', false, false],
        ['comm.sms', 'Send SMS', 'Send SMS messages', 'communication', 'communication', 'native', 'medium', '/api/sms.php', false, false],
        ['comm.webhook', 'Fire Webhook', 'Send webhook to external URL', 'communication', 'communication', 'native', 'medium', '/api/webhooks.php', false, false],

        // ── Data ──
        ['data.query', 'Query Database', 'Read data from database', 'query', 'data', 'native', 'low', '/api/tools.php', false, false],
        ['data.write', 'Write Data', 'Insert/update database records', 'action', 'data', 'native', 'medium', '/api/tools.php', false, false],
        ['data.analytics', 'Run Analytics', 'Generate analytics reports', 'query', 'data', 'native', 'low', '/api/analytics.php', false, false],
        ['data.export', 'Export Data', 'Export data to files', 'action', 'data', 'native', 'medium', '/api/tools.php', false, false],

        // ── Commerce ──
        ['commerce.invoice', 'Create Invoice', 'Generate invoices', 'action', 'commerce', 'native', 'high', '/api/billing.php', true, false],
        ['commerce.payment', 'Process Payment', 'Process a payment transaction', 'action', 'commerce', 'native', 'critical', '/api/billing.php', true, true],
        ['commerce.marketplace', 'Marketplace', 'Create/manage marketplace listings', 'action', 'commerce', 'native', 'medium', '/api/marketplace.php', false, false],

        // ── Infrastructure ──
        ['infra.dns', 'Manage DNS', 'Create/update DNS records', 'action', 'infrastructure', 'native', 'high', '/api/dns.php', true, false],
        ['infra.ssl', 'Manage SSL', 'Issue/renew SSL certificates', 'action', 'infrastructure', 'native', 'high', '/api/ssl.php', true, false],
        ['infra.server', 'Server Operations', 'Server provisioning and management', 'action', 'infrastructure', 'native', 'critical', '/api/servers.php', true, true],

        // ── World ──
        ['world.observe', 'Observe World', 'Get current world state', 'perception', 'world', 'native', 'low', '/api/agentos/world-state.php', false, false],
        ['world.modify', 'Modify World', 'Change world entities or state', 'action', 'world', 'native', 'medium', '/api/agentos/world-state.php', false, false],
        ['world.spawn', 'Spawn Entity', 'Create new entity in world', 'action', 'world', 'native', 'medium', '/api/agentos/world-state.php', false, false],

        // ── Robotics ──
        ['robot.command', 'Robot Command', 'Send command to physical robot', 'control', 'robotics', 'native', 'critical', '/api/agentos/bridge.php', true, true],
        ['robot.telemetry', 'Robot Telemetry', 'Read robot sensor data', 'perception', 'robotics', 'native', 'low', '/api/agentos/bridge.php', false, false],
        ['robot.navigate', 'Robot Navigate', 'Plan and execute robot movement', 'control', 'robotics', 'native', 'high', '/api/agentos/bridge.php', true, false],

        // ── AI ──
        ['ai.reason', 'AI Reasoning', 'General purpose AI reasoning', 'query', 'ai', 'native', 'low', '/api/alfred-chat.php', false, false],
        ['ai.generate', 'AI Generate', 'Generate text/code/images', 'action', 'ai', 'native', 'low', '/api/alfred-chat.php', false, false],
        ['ai.classify', 'AI Classify', 'Classify or categorize input', 'query', 'ai', 'native', 'low', '/api/alfred-chat.php', false, false],
        ['ai.vision', 'AI Vision', 'Analyze images and visual data', 'perception', 'ai', 'native', 'low', '/api/tools.php', false, false],

        // ── Identity ──
        ['identity.verify', 'Verify Identity', 'Verify user identity', 'query', 'identity', 'native', 'high', '/api/auth.php', false, false],
        ['identity.create', 'Create Account', 'Create new user account', 'action', 'identity', 'native', 'high', '/api/auth.php', true, false],

        // ── Memory ──
        ['memory.store', 'Store Memory', 'Persist information to memory', 'action', 'memory', 'native', 'low', '/api/agentos/memory.php', false, false],
        ['memory.recall', 'Recall Memory', 'Retrieve information from memory', 'query', 'memory', 'native', 'low', '/api/agentos/memory.php', false, false],
        ['memory.forget', 'Forget', 'Remove specific memories', 'action', 'memory', 'native', 'medium', '/api/agentos/memory.php', false, false],
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO agentos_capabilities 
        (capability_id, display_name, description, capability_type, category, 
         provider, risk_level, endpoint, requires_simulation, requires_approval)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $count = 0;
    foreach ($capabilities as $cap) {
        $stmt->execute($cap);
        if ($stmt->rowCount() > 0) $count++;
    }

    agentos_respond(['ok' => true, 'seeded' => $count, 'total' => count($capabilities)]);
}
