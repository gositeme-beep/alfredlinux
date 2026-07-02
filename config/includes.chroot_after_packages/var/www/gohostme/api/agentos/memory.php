<?php
/**
 * GSM Alfred OS — 5-Type Memory Store API v1.0
 * Episodic · Semantic · Procedural · Spatial · Relational
 *
 * Endpoints:
 *   POST   ?action=store&type=X     — Store a memory
 *   GET    ?action=recall&type=X    — Retrieve memories
 *   POST   ?action=forget           — Remove a memory
 *   POST   ?action=consolidate      — Merge/strengthen memories
 *   GET    ?action=search           — Search across all memory types
 *   GET    ?action=stats            — Memory statistics
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
$action = $_GET['action'] ?? 'recall';

switch ($action) {
    case 'store':       handleStore($auth); break;
    case 'recall':      handleRecall($auth); break;
    case 'forget':      handleForget($auth); break;
    case 'consolidate': handleConsolidate($auth); break;
    case 'search':      handleSearch($auth); break;
    case 'stats':       handleStats($auth); break;
    default:            agentos_error('Unknown action');
}

// ═══════════════════════════════════════════════════════════════
// STORE — Persist a new memory
// ═══════════════════════════════════════════════════════════════
function handleStore(array $auth): void {
    $type = $_GET['type'] ?? '';
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) agentos_error('Request body required');

    $agentId = mb_substr($input['agent_id'] ?? 'alfred', 0, 50);
    $userId = $auth['user_id'];

    $pdo = agentos_pdo();

    switch ($type) {
        case 'episodic':
            $id = storeEpisodic($pdo, $agentId, $userId, $input);
            break;
        case 'semantic':
            $id = storeSemantic($pdo, $agentId, $userId, $input);
            break;
        case 'procedural':
            $id = storeProcedural($pdo, $agentId, $input);
            break;
        case 'spatial':
            $id = storeSpatial($pdo, $agentId, $input);
            break;
        case 'relational':
            $id = storeRelational($pdo, $input);
            break;
        default:
            agentos_error('type must be: episodic, semantic, procedural, spatial, or relational');
    }

    agentos_audit([
        'agent_id' => $agentId, 'user_id' => $userId,
        'action_type' => 'memory_stored', 'status' => 'completed',
        'input' => ['type' => $type, 'id' => $id],
    ]);

    agentos_respond(['ok' => true, 'type' => $type, 'id' => $id], 201);
}

function storeEpisodic(PDO $pdo, string $agentId, ?int $userId, array $input): int {
    $validTypes = ['conversation', 'task_execution', 'observation', 'interaction', 'error', 'milestone'];
    $episodeType = in_array($input['episode_type'] ?? '', $validTypes) ? $input['episode_type'] : 'observation';

    $stmt = $pdo->prepare("INSERT INTO agentos_memory_episodic 
        (user_id, agent_id, episode_type, summary, details, outcome, 
         importance, task_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $userId, $agentId, $episodeType,
        mb_substr($input['summary'] ?? '', 0, 1000),
        json_encode($input['details'] ?? null),
        $input['outcome'] ?? 'unknown',
        min(max((int)($input['importance'] ?? 5), 1), 10),
        $input['task_id'] ?? null,
    ]);
    return (int)$pdo->lastInsertId();
}

function storeSemantic(PDO $pdo, string $agentId, ?int $userId, array $input): int {
    if (empty($input['fact_key'])) agentos_error('fact_key required for semantic memory');

    // Upsert: update confidence if fact already exists
    $stmt = $pdo->prepare("INSERT INTO agentos_memory_semantic 
        (user_id, agent_id, fact_key, fact_value, domain, confidence, source)
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            fact_value = VALUES(fact_value),
            confidence = GREATEST(confidence, VALUES(confidence)),
            source = VALUES(source),
            verified = 1");
    $stmt->execute([
        $userId, $agentId,
        mb_substr($input['fact_key'], 0, 500),
        mb_substr($input['fact_value'] ?? '', 0, 5000),
        mb_substr($input['domain'] ?? 'general', 0, 100),
        max(0.0, min(1.0, (float)($input['confidence'] ?? 0.8))),
        mb_substr($input['source'] ?? 'observation', 0, 200),
    ]);
    return (int)$pdo->lastInsertId();
}

function storeProcedural(PDO $pdo, string $agentId, array $input): int {
    if (empty($input['procedure_name'])) agentos_error('procedure_name required');

    $stmt = $pdo->prepare("INSERT INTO agentos_memory_procedural 
        (agent_id, procedure_name, trigger_pattern, steps, 
         success_rate, times_used, learned_from)
        VALUES (?, ?, ?, ?, ?, 0, ?)
        ON DUPLICATE KEY UPDATE
            steps = VALUES(steps),
            learned_from = VALUES(learned_from)");
    $stmt->execute([
        $agentId,
        mb_substr($input['procedure_name'], 0, 200),
        $input['trigger_pattern'] ?? null,
        json_encode($input['steps'] ?? []),
        max(0.0, min(100.0, (float)($input['success_rate'] ?? 100.0))),
        mb_substr($input['learned_from'] ?? 'manual', 0, 200),
    ]);
    return (int)$pdo->lastInsertId();
}

function storeSpatial(PDO $pdo, string $agentId, array $input): int {
    if (empty($input['entity_id'])) agentos_error('entity_id required for spatial memory');

    $stmt = $pdo->prepare("INSERT INTO agentos_memory_spatial 
        (world_id, entity_id, entity_type, position_x, position_y, position_z, 
         orientation, properties, observed_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            position_x = VALUES(position_x), position_y = VALUES(position_y), position_z = VALUES(position_z),
            orientation = VALUES(orientation),
            properties = VALUES(properties)");
    $stmt->execute([
        mb_substr($input['world_id'] ?? 'default', 0, 100),
        mb_substr($input['entity_id'], 0, 128),
        mb_substr($input['entity_type'] ?? 'object', 0, 50),
        (float)($input['x'] ?? 0),
        (float)($input['y'] ?? 0),
        (float)($input['z'] ?? 0),
        json_encode($input['orientation'] ?? null),
        json_encode($input['properties'] ?? null),
        $agentId,
    ]);
    return (int)$pdo->lastInsertId();
}

function storeRelational(PDO $pdo, array $input): int {
    if (empty($input['subject_id']) || empty($input['relation']) || empty($input['object_id'])) {
        agentos_error('subject_id, relation, and object_id required');
    }

    $stmt = $pdo->prepare("INSERT INTO agentos_memory_relational 
        (subject_type, subject_id, relation, object_type, object_id,
         weight, metadata, valid_until)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            weight = VALUES(weight),
            metadata = VALUES(metadata)");
    $stmt->execute([
        mb_substr($input['subject_type'] ?? 'entity', 0, 50),
        mb_substr($input['subject_id'], 0, 100),
        mb_substr($input['relation'], 0, 100),
        mb_substr($input['object_type'] ?? 'entity', 0, 50),
        mb_substr($input['object_id'], 0, 100),
        max(0.0, min(1.0, (float)($input['weight'] ?? 1.0))),
        json_encode($input['metadata'] ?? null),
        $input['valid_until'] ?? null,
    ]);
    return (int)$pdo->lastInsertId();
}

// ═══════════════════════════════════════════════════════════════
// RECALL — Retrieve memories
// ═══════════════════════════════════════════════════════════════
function handleRecall(array $auth): void {
    $type = $_GET['type'] ?? '';
    $agentId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['agent_id'] ?? 'alfred');
    $limit = min((int)($_GET['limit'] ?? 20), 100);
    $pdo = agentos_pdo();

    switch ($type) {
        case 'episodic':
            $memories = recallEpisodic($pdo, $agentId, $auth['user_id'], $limit);
            break;
        case 'semantic':
            $memories = recallSemantic($pdo, $agentId, $auth['user_id'], $limit);
            break;
        case 'procedural':
            $memories = recallProcedural($pdo, $agentId, $limit);
            break;
        case 'spatial':
            $worldId = mb_substr($_GET['world_id'] ?? 'default', 0, 100);
            $memories = recallSpatial($pdo, $agentId, $worldId, $limit);
            break;
        case 'relational':
            $subjectId = mb_substr($_GET['subject_id'] ?? '', 0, 100);
            $memories = recallRelational($pdo, $subjectId, $limit);
            break;
        default:
            agentos_error('type must be: episodic, semantic, procedural, spatial, or relational');
    }

    agentos_respond(['ok' => true, 'type' => $type, 'count' => count($memories), 'memories' => $memories]);
}

function recallEpisodic(PDO $pdo, string $agentId, ?int $userId, int $limit): array {
    $where = ['agent_id=?'];
    $params = [$agentId];

    if ($userId) {
        $where[] = '(user_id=? OR user_id IS NULL)';
        $params[] = $userId;
    }
    if (isset($_GET['episode_type'])) {
        $where[] = 'episode_type=?';
        $params[] = $_GET['episode_type'];
    }
    if (isset($_GET['min_importance'])) {
        $where[] = 'importance >= ?';
        $params[] = (int)$_GET['min_importance'];
    }

    $safeLimit = (int)$limit;
    $stmt = $pdo->prepare("SELECT id, episode_type, summary, details, outcome, 
        importance, task_id, recalled_count, created_at
        FROM agentos_memory_episodic WHERE " . implode(' AND ', $where) . "
        ORDER BY importance DESC, created_at DESC LIMIT {$safeLimit}");
    $stmt->execute($params);
    $mems = $stmt->fetchAll();

    // Bump recall count
    if (!empty($mems)) {
        $ids = array_column($mems, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $pdo->prepare("UPDATE agentos_memory_episodic SET recalled_count=recalled_count+1, last_recalled=NOW() WHERE id IN ({$placeholders})")->execute($ids);
    }

    foreach ($mems as &$m) {
        $m['details'] = json_decode($m['details'] ?? 'null', true);
    }
    return $mems;
}

function recallSemantic(PDO $pdo, string $agentId, ?int $userId, int $limit): array {
    $where = ['agent_id=?'];
    $params = [$agentId];

    if ($userId) {
        $where[] = '(user_id=? OR user_id IS NULL)';
        $params[] = $userId;
    }
    if (isset($_GET['domain'])) {
        $where[] = 'domain=?';
        $params[] = $_GET['domain'];
    }
    if (isset($_GET['min_confidence'])) {
        $where[] = 'confidence >= ?';
        $params[] = (float)$_GET['min_confidence'];
    }
    if (isset($_GET['search'])) {
        $where[] = '(fact_key LIKE ? OR fact_value LIKE ?)';
        $term = '%' . mb_substr($_GET['search'], 0, 100) . '%';
        $params[] = $term;
        $params[] = $term;
    }

    $safeLimit = (int)$limit;
    $stmt = $pdo->prepare("SELECT id, fact_key, fact_value, domain, confidence, source, verified, created_at
        FROM agentos_memory_semantic WHERE " . implode(' AND ', $where) . "
        ORDER BY confidence DESC LIMIT {$safeLimit}");
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function recallProcedural(PDO $pdo, string $agentId, int $limit): array {
    $where = ['agent_id=?', 'enabled=1'];
    $params = [$agentId];

    if (isset($_GET['search'])) {
        $where[] = '(procedure_name LIKE ? OR trigger_pattern LIKE ?)';
        $term = '%' . mb_substr($_GET['search'], 0, 100) . '%';
        $params[] = $term;
        $params[] = $term;
    }

    $safeLimit = (int)$limit;
    $stmt = $pdo->prepare("SELECT id, procedure_name, trigger_pattern, steps, 
        success_rate, times_used, last_used, learned_from
        FROM agentos_memory_procedural WHERE " . implode(' AND ', $where) . "
        ORDER BY success_rate DESC, times_used DESC LIMIT {$safeLimit}");
    $stmt->execute($params);
    $procs = $stmt->fetchAll();

    foreach ($procs as &$p) {
        $p['steps'] = json_decode($p['steps'] ?? '[]', true);
    }
    return $procs;
}

function recallSpatial(PDO $pdo, string $agentId, string $worldId, int $limit): array {
    $where = ['world_id=?'];
    $params = [$worldId];

    if (isset($_GET['entity_type'])) {
        $where[] = 'entity_type=?';
        $params[] = $_GET['entity_type'];
    }

    $safeLimit = (int)$limit;
    if (isset($_GET['near_x']) && isset($_GET['near_y'])) {
        $x = (float)$_GET['near_x'];
        $y = (float)$_GET['near_y'];
        $z = (float)($_GET['near_z'] ?? 0);
        $stmt = $pdo->prepare("SELECT *, 
            SQRT(POW(position_x-{$x},2) + POW(position_y-{$y},2) + POW(position_z-{$z},2)) as distance
            FROM agentos_memory_spatial WHERE " . implode(' AND ', $where) . "
            ORDER BY distance ASC LIMIT {$safeLimit}");
        $stmt->execute($params);
        $mems = $stmt->fetchAll();
    } else {
        $stmt = $pdo->prepare("SELECT * FROM agentos_memory_spatial 
            WHERE " . implode(' AND ', $where) . " ORDER BY last_observed DESC LIMIT {$safeLimit}");
        $stmt->execute($params);
        $mems = $stmt->fetchAll();
    }

    foreach ($mems as &$m) {
        $m['orientation'] = json_decode($m['orientation'] ?? 'null', true);
        $m['properties'] = json_decode($m['properties'] ?? 'null', true);
    }
    return $mems;
}

function recallRelational(PDO $pdo, string $subjectId, int $limit): array {
    if (!$subjectId) agentos_error('subject_id required for relational recall');

    $safeLimit = (int)$limit;
    $stmt = $pdo->prepare("SELECT * FROM agentos_memory_relational 
        WHERE subject_id=? AND (valid_until IS NULL OR valid_until > NOW())
        ORDER BY weight DESC LIMIT {$safeLimit}");
    $stmt->execute([$subjectId]);
    return $stmt->fetchAll();
}

// ═══════════════════════════════════════════════════════════════
// FORGET — Remove memories
// ═══════════════════════════════════════════════════════════════
function handleForget(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $type = $input['type'] ?? '';
    $id = (int)($input['id'] ?? 0);

    $tableMap = [
        'episodic' => 'agentos_memory_episodic',
        'semantic' => 'agentos_memory_semantic',
        'procedural' => 'agentos_memory_procedural',
        'spatial' => 'agentos_memory_spatial',
        'relational' => 'agentos_memory_relational',
    ];

    if (!isset($tableMap[$type])) agentos_error('Invalid memory type');
    if (!$id) agentos_error('id required');

    $pdo = agentos_pdo();
    $table = $tableMap[$type];
    $stmt = $pdo->prepare("DELETE FROM {$table} WHERE id=?");
    $stmt->execute([$id]);

    agentos_respond(['ok' => true, 'type' => $type, 'deleted' => $stmt->rowCount()]);
}

// ═══════════════════════════════════════════════════════════════
// CONSOLIDATE — Merge and strengthen memories
// ═══════════════════════════════════════════════════════════════
function handleConsolidate(array $auth): void {
    $input = json_decode(file_get_contents('php://input'), true);
    $agentId = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['agent_id'] ?? 'alfred');
    $pdo = agentos_pdo();
    $actions = [];

    // 1. Expire old low-importance episodic memories (> 30 days, importance < 3)
    $stmt = $pdo->prepare("DELETE FROM agentos_memory_episodic 
        WHERE agent_id=? AND importance < 3 AND recalled_count = 0 
        AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stmt->execute([$agentId]);
    $actions['expired_episodic'] = $stmt->rowCount();

    // 2. Remove low-confidence unverified semantic facts older than 30 days
    $stmt = $pdo->prepare("DELETE FROM agentos_memory_semantic 
        WHERE agent_id=? AND verified=0 AND confidence < 0.3 
        AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stmt->execute([$agentId]);
    $actions['expired_semantic'] = $stmt->rowCount();

    // 3. Disable poorly performing procedures
    $stmt = $pdo->prepare("UPDATE agentos_memory_procedural 
        SET enabled=0 WHERE agent_id=? AND success_rate < 20 AND times_used > 5");
    $stmt->execute([$agentId]);
    $actions['disabled_procedures'] = $stmt->rowCount();

    // 4. Boost importance of frequently recalled episodic memories
    $stmt = $pdo->prepare("UPDATE agentos_memory_episodic 
        SET importance = LEAST(importance + 1, 10) 
        WHERE agent_id=? AND recalled_count > 5 AND importance < 10");
    $stmt->execute([$agentId]);
    $actions['boosted_episodic'] = $stmt->rowCount();

    // 5. Expire stale relational memories
    $stmt = $pdo->prepare("DELETE FROM agentos_memory_relational 
        WHERE valid_until IS NOT NULL AND valid_until < NOW()");
    $stmt->execute();
    $actions['expired_relational'] = $stmt->rowCount();

    agentos_respond(['ok' => true, 'agent_id' => $agentId, 'actions' => $actions]);
}

// ═══════════════════════════════════════════════════════════════
// SEARCH — Cross-type memory search
// ═══════════════════════════════════════════════════════════════
function handleSearch(array $auth): void {
    $query = mb_substr(trim($_GET['q'] ?? ''), 0, 200);
    if (!$query) agentos_error('q (query) required');

    $agentId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['agent_id'] ?? 'alfred');
    $pdo = agentos_pdo();
    $term = '%' . $query . '%';
    $results = [];

    // Search episodic
    $stmt = $pdo->prepare("SELECT 'episodic' as type, id, summary as label, importance as score, created_at 
        FROM agentos_memory_episodic WHERE agent_id=? AND summary LIKE ? 
        ORDER BY importance DESC LIMIT 10");
    $stmt->execute([$agentId, $term]);
    $results = array_merge($results, $stmt->fetchAll());

    // Search semantic
    $stmt = $pdo->prepare("SELECT 'semantic' as type, id, fact_key as label, confidence as score, created_at 
        FROM agentos_memory_semantic WHERE agent_id=? AND (fact_key LIKE ? OR fact_value LIKE ?)
        ORDER BY confidence DESC LIMIT 10");
    $stmt->execute([$agentId, $term, $term]);
    $results = array_merge($results, $stmt->fetchAll());

    // Search procedural
    $stmt = $pdo->prepare("SELECT 'procedural' as type, id, procedure_name as label, success_rate/100 as score, created_at 
        FROM agentos_memory_procedural WHERE agent_id=? AND (procedure_name LIKE ? OR trigger_pattern LIKE ?)
        ORDER BY success_rate DESC LIMIT 10");
    $stmt->execute([$agentId, $term, $term]);
    $results = array_merge($results, $stmt->fetchAll());

    // Search spatial
    $worldId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['world_id'] ?? 'default');
    $stmt = $pdo->prepare("SELECT 'spatial' as type, id, entity_id as label, 0.5 as score, last_observed as created_at 
        FROM agentos_memory_spatial WHERE world_id=? AND (entity_id LIKE ? OR entity_type LIKE ?)
        ORDER BY last_observed DESC LIMIT 10");
    $stmt->execute([$worldId, $term, $term]);
    $results = array_merge($results, $stmt->fetchAll());

    agentos_respond(['ok' => true, 'query' => $query, 'results' => array_slice($results, 0, 30)]);
}

// ═══════════════════════════════════════════════════════════════
// STATS — Memory statistics
// ═══════════════════════════════════════════════════════════════
function handleStats(array $auth): void {
    $agentId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['agent_id'] ?? 'alfred');
    $pdo = agentos_pdo();

    $stats = [];
    $tables = [
        'episodic' => 'agentos_memory_episodic',
        'semantic' => 'agentos_memory_semantic',
        'procedural' => 'agentos_memory_procedural',
        'spatial' => 'agentos_memory_spatial',
    ];

    foreach ($tables as $type => $table) {
        if ($type === 'spatial') continue; // spatial has no agent_id
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM {$table} WHERE agent_id=?");
        $stmt->execute([$agentId]);
        $stats[$type] = ['count' => (int)$stmt->fetchColumn()];
    }

    // Spatial counts by world_id (no agent_id column)
    $stmt = $pdo->query("SELECT COUNT(*) FROM agentos_memory_spatial");
    $stats['spatial'] = ['count' => (int)$stmt->fetchColumn()];
    // Total
    $stats['total'] = array_sum(array_column($stats, 'count'));

    // Most recalled episodic memory
    $stmt = $pdo->prepare("SELECT summary, recalled_count FROM agentos_memory_episodic 
        WHERE agent_id=? ORDER BY recalled_count DESC LIMIT 1");
    $stmt->execute([$agentId]);
    $stats['most_recalled'] = $stmt->fetch() ?: null;

    // Highest success rate procedure
    $stmt = $pdo->prepare("SELECT procedure_name, success_rate, times_used FROM agentos_memory_procedural 
        WHERE agent_id=? AND times_used > 0 ORDER BY success_rate DESC LIMIT 1");
    $stmt->execute([$agentId]);
    $stats['best_procedure'] = $stmt->fetch() ?: null;

    agentos_respond(['ok' => true, 'agent_id' => $agentId, 'stats' => $stats]);
}
