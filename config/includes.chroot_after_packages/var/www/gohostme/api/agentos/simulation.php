<?php
/**
 * GSM Alfred OS — Simulation Engine API v1.0
 * Dry-run / sandbox mode for actions before real execution
 *
 * Endpoints:
 *   POST   ?action=run               — Run simulation of an action
 *   POST   ?action=scenario          — Run multi-step scenario
 *   GET    ?action=get&id=X          — Get simulation result
 *   GET    ?action=list              — List recent simulations
 *   POST   ?action=compare           — Compare two sim results
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
$action = $_GET['action'] ?? 'run';

switch ($action) {
    case 'run':      handleRun($auth); break;
    case 'scenario': handleScenario($auth); break;
    case 'get':      handleGet($auth); break;
    case 'list':     handleList($auth); break;
    case 'compare':  handleCompare($auth); break;
    default:         agentos_error('Unknown action');
}

// ═══════════════════════════════════════════════════════════════
// RUN — Simulate a single action
// ═══════════════════════════════════════════════════════════════
function handleRun(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || empty($input['capability_id'])) {
        agentos_error('capability_id required');
    }

    $capId = mb_substr($input['capability_id'], 0, 100);
    $inputData = $input['input'] ?? [];
    $taskId = $input['task_id'] ?? null;

    $pdo = agentos_pdo();
    $simId = agentos_id('sim');
    $startTime = hrtime(true);

    // Get capability info
    $stmt = $pdo->prepare("SELECT * FROM agentos_capabilities WHERE capability_id=?");
    $stmt->execute([$capId]);
    $cap = $stmt->fetch() ?: null;

    // Risk assessment
    $riskMap = ['low' => 0.1, 'medium' => 0.3, 'high' => 0.6, 'critical' => 0.9];
    $riskScore = $riskMap[$cap['risk_level'] ?? 'low'] ?? 0.1;

    // Simulate effects
    $effects = simulateEffects($pdo, $capId, $inputData, $cap);

    // Detect anomalies
    $anomalies = detectAnomalies($effects, $riskScore, $cap);

    // Determine outcome
    $outcome = 'safe';
    if (!empty($anomalies)) {
        $maxSeverity = max(array_column($anomalies, 'severity'));
        if ($maxSeverity >= 0.8) $outcome = 'unsafe';
        elseif ($maxSeverity >= 0.5) $outcome = 'warning';
    }

    // Predicted outputs
    $predictions = predictOutputs($pdo, $capId, $inputData, $cap);

    $durationMs = (int)((hrtime(true) - $startTime) / 1_000_000);

    // Store simulation
    $stmt = $pdo->prepare("INSERT INTO agentos_simulations 
        (sim_id, task_id, sim_type, input_state, expected_state, actual_state, actions_taken, outcome,
         risk_score, anomalies, duration_ms)
        VALUES (?, ?, 'dry_run', ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $simId, $taskId,
        json_encode(['capability_id' => $capId, 'input' => $inputData]),
        json_encode($predictions),
        json_encode(['effects' => $effects]),
        json_encode(['capability_id' => $capId]),
        $outcome, $riskScore,
        json_encode($anomalies),
        $durationMs,
    ]);

    agentos_audit([
        'agent_id' => 'simulator', 'user_id' => $auth['user_id'],
        'action_type' => 'simulation_run', 'capability_id' => $capId,
        'status' => 'completed', 'risk_level' => $cap['risk_level'] ?? 'low',
        'output' => ['sim_id' => $simId, 'outcome' => $outcome],
        'duration_ms' => $durationMs,
    ]);

    agentos_respond([
        'ok' => true,
        'sim_id' => $simId,
        'outcome' => $outcome,
        'risk_score' => $riskScore,
        'effects' => $effects,
        'predictions' => $predictions,
        'anomalies' => $anomalies,
        'duration_ms' => $durationMs,
    ]);
}

// ═══════════════════════════════════════════════════════════════
// SCENARIO — Simulate a multi-step plan
// ═══════════════════════════════════════════════════════════════
function handleScenario(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || empty($input['steps'])) agentos_error('steps array required');

    $pdo = agentos_pdo();
    $simId = agentos_id('sim');
    $startTime = hrtime(true);
    
    $cumulativeRisk = 0;
    $stepResults = [];
    $worldSnapshot = getWorldSnapshot($pdo);
    $allAnomalies = [];

    foreach ($input['steps'] as $i => $step) {
        $capId = $step['capability_id'] ?? '';
        $stepInput = $step['input'] ?? [];

        // Get capability
        $stmt = $pdo->prepare("SELECT * FROM agentos_capabilities WHERE capability_id=?");
        $stmt->execute([$capId]);
        $cap = $stmt->fetch();

        $riskMap = ['low' => 0.1, 'medium' => 0.3, 'high' => 0.6, 'critical' => 0.9];
        $stepRisk = $riskMap[$cap['risk_level'] ?? 'low'] ?? 0.1;
        $cumulativeRisk = 1 - (1 - $cumulativeRisk) * (1 - $stepRisk);

        $effects = simulateEffects($pdo, $capId, $stepInput, $cap);
        $anomalies = detectAnomalies($effects, $stepRisk, $cap);
        $predictions = predictOutputs($pdo, $capId, $stepInput, $cap);

        // Apply predicted effects to snapshot
        $worldSnapshot = applyEffects($worldSnapshot, $effects);

        $allAnomalies = array_merge($allAnomalies, $anomalies);

        $stepResults[] = [
            'step' => $i + 1,
            'capability_id' => $capId,
            'risk_score' => round($stepRisk, 3),
            'effects' => $effects,
            'predictions' => $predictions,
            'anomalies' => $anomalies,
        ];
    }

    $outcome = 'safe';
    if (!empty($allAnomalies)) {
        $maxSev = max(array_column($allAnomalies, 'severity'));
        if ($maxSev >= 0.8 || $cumulativeRisk > 0.9) $outcome = 'unsafe';
        elseif ($maxSev >= 0.5 || $cumulativeRisk > 0.6) $outcome = 'warning';
    }

    $durationMs = (int)((hrtime(true) - $startTime) / 1_000_000);

    // Store scenario simulation
    $stmt = $pdo->prepare("INSERT INTO agentos_simulations 
        (sim_id, task_id, sim_type, input_state, expected_state, actual_state, actions_taken, outcome,
         risk_score, anomalies, duration_ms)
        VALUES (?, ?, 'scenario', ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $simId, $input['task_id'] ?? null,
        json_encode(['steps' => $input['steps']]),
        json_encode($stepResults),
        json_encode(['final_world_state' => $worldSnapshot]),
        json_encode(array_column($input['steps'], 'capability_id')),
        $outcome, round($cumulativeRisk, 3),
        json_encode($allAnomalies),
        $durationMs,
    ]);

    agentos_respond([
        'ok' => true,
        'sim_id' => $simId,
        'outcome' => $outcome,
        'cumulative_risk' => round($cumulativeRisk, 3),
        'steps' => $stepResults,
        'total_anomalies' => count($allAnomalies),
        'duration_ms' => $durationMs,
    ]);
}

function handleGet(array $auth): void {
    $id = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['id'] ?? '');
    if (!$id) agentos_error('id required');

    $pdo = agentos_pdo();
    $stmt = $pdo->prepare("SELECT * FROM agentos_simulations WHERE sim_id=?");
    $stmt->execute([$id]);
    $sim = $stmt->fetch();
    if (!$sim) agentos_error('Simulation not found', 404);

    foreach (['input_state', 'predicted_output', 'anomalies', 'metadata'] as $f) {
        $sim[$f] = json_decode($sim[$f] ?? 'null', true);
    }

    agentos_respond(['ok' => true, 'simulation' => $sim]);
}

function handleList(array $auth): void {
    $pdo = agentos_pdo();
    $limit = min((int)($_GET['limit'] ?? 25), 100);

    $where = ['1=1'];
    $params = [];
    if (isset($_GET['outcome'])) {
        $where[] = 'outcome=?';
        $params[] = $_GET['outcome'];
    }
    if (isset($_GET['task_id'])) {
        $where[] = 'task_id=?';
        $params[] = $_GET['task_id'];
    }
    $stmt = $pdo->prepare("SELECT sim_id, task_id, sim_type, outcome, risk_score, duration_ms, created_at
        FROM agentos_simulations WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC LIMIT " . (int)$limit);
    $stmt->execute($params);

    agentos_respond(['ok' => true, 'simulations' => $stmt->fetchAll()]);
}

function handleCompare(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $simA = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['sim_a'] ?? '');
    $simB = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['sim_b'] ?? '');

    if (!$simA || !$simB) agentos_error('sim_a and sim_b required');

    $pdo = agentos_pdo();
    $stmtA = $pdo->prepare("SELECT * FROM agentos_simulations WHERE sim_id=?");
    $stmtA->execute([$simA]);
    $a = $stmtA->fetch();
    $stmtA->execute([$simB]);
    $b = $stmtA->fetch();

    if (!$a || !$b) agentos_error('One or both simulations not found');

    agentos_respond([
        'ok' => true,
        'comparison' => [
            'risk_delta' => round(($b['risk_score'] ?? 0) - ($a['risk_score'] ?? 0), 3),
            'outcome_a' => $a['outcome'],
            'outcome_b' => $b['outcome'],
            'safer' => ($a['risk_score'] ?? 0) <= ($b['risk_score'] ?? 0) ? $simA : $simB,
            'anomalies_a' => count(json_decode($a['anomalies'] ?? '[]', true)),
            'anomalies_b' => count(json_decode($b['anomalies'] ?? '[]', true)),
        ],
    ]);
}

// ── Simulation Helpers ─────────────────────────────────────────

function simulateEffects(PDO $pdo, string $capId, array $input, ?array $cap): array {
    $effects = [];
    $category = $cap['category'] ?? '';
    $type = $cap['capability_type'] ?? 'action';

    // Model effects based on capability category
    switch ($category) {
        case 'code':
            $effects[] = ['type' => 'file_system', 'scope' => 'workspace', 'reversible' => ($capId !== 'code.deploy')];
            if ($capId === 'code.deploy') {
                $effects[] = ['type' => 'production_change', 'scope' => 'infrastructure', 'reversible' => false];
            }
            break;
        case 'data':
            $effects[] = ['type' => 'database', 'scope' => 'data', 'reversible' => ($type === 'query')];
            break;
        case 'commerce':
            $effects[] = ['type' => 'financial', 'scope' => 'billing', 'reversible' => false];
            break;
        case 'infrastructure':
            $effects[] = ['type' => 'infrastructure', 'scope' => 'server', 'reversible' => false];
            break;
        case 'robotics':
            $effects[] = ['type' => 'physical', 'scope' => 'robot', 'reversible' => false];
            $effects[] = ['type' => 'real_world', 'scope' => 'environment', 'reversible' => false];
            break;
        case 'communication':
            $effects[] = ['type' => 'message', 'scope' => 'external', 'reversible' => false];
            break;
        default:
            if ($type === 'action') {
                $effects[] = ['type' => 'state_change', 'scope' => 'internal', 'reversible' => true];
            }
    }

    return $effects;
}

function detectAnomalies(array $effects, float $riskScore, ?array $cap): array {
    $anomalies = [];

    foreach ($effects as $effect) {
        // Irreversible real-world effects are high severity
        if (!$effect['reversible'] && in_array($effect['type'], ['physical', 'real_world', 'financial'])) {
            $anomalies[] = [
                'type' => 'irreversible_effect',
                'description' => "Irreversible {$effect['type']} effect on {$effect['scope']}",
                'severity' => 0.8,
            ];
        }

        // Infrastructure changes
        if ($effect['type'] === 'production_change') {
            $anomalies[] = [
                'type' => 'production_impact',
                'description' => 'Action affects production infrastructure',
                'severity' => 0.7,
            ];
        }
    }

    // High cumulative risk
    if ($riskScore > 0.7) {
        $anomalies[] = [
            'type' => 'high_risk',
            'description' => "High aggregate risk score: {$riskScore}",
            'severity' => $riskScore,
        ];
    }

    return $anomalies;
}

function predictOutputs(PDO $pdo, string $capId, array $input, ?array $cap): array {
    // Check procedural memory for this capability's typical outputs
    $stmt = $pdo->prepare("SELECT success_rate, times_used FROM agentos_memory_procedural 
        WHERE procedure_name=? AND enabled=1 LIMIT 1");
    $stmt->execute([$capId]);
    $proc = $stmt->fetch();

    return [
        'expected_success_rate' => $proc ? (float)$proc['success_rate'] : 75.0,
        'historical_uses' => $proc ? (int)$proc['times_used'] : 0,
        'estimated_duration_ms' => (int)($cap['timeout_ms'] ?? 30000) / 3,
        'predicted_outcome' => ($proc && $proc['success_rate'] > 50) ? 'success' : 'uncertain',
    ];
}

function getWorldSnapshot(PDO $pdo): array {
    $stmt = $pdo->query("SELECT state_key, state_value FROM agentos_world_state WHERE world_id='default'");
    return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

function applyEffects(array $snapshot, array $effects): array {
    foreach ($effects as $effect) {
        $snapshot["sim_effect_{$effect['type']}"] = json_encode([
            'applied' => true, 'scope' => $effect['scope'], 'reversible' => $effect['reversible'],
        ]);
    }
    return $snapshot;
}
