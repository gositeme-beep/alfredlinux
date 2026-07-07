<?php
/**
 * Alfred Learning Engine API — Phase 2: Self-Evolution
 * ────────────────────────────────────────────────────
 * Behavioral learning: track interactions, optimize prompts,
 * A/B test responses, and continuously improve.
 *
 * Endpoints:
 *   POST ?action=log-interaction    → Log a user interaction + outcome
 *   POST ?action=feedback           → Record user feedback on a response
 *   GET  ?action=insights           → Aggregated learning insights
 *   POST ?action=prompt-variant     → Create an A/B test prompt variant
 *   GET  ?action=experiments        → View active experiments
 *   POST ?action=experiment-result  → Record experiment outcome
 *   GET  ?action=patterns           → Detected behavioral patterns
 *   GET  ?action=performance        → Learning performance metrics
 *   POST ?action=adjust             → Apply a behavioral adjustment
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
    if (empty($_SESSION['logged_in']) || empty($_SESSION['client_id'])) jsonResponse(['error' => 'Authentication required'], 401);
}
function isAdmin() { return !empty($_SESSION['is_admin']) || ($_SESSION['client_id'] ?? 0) === 33; }
function isInternalCall() {
    $s = getenv('INTERNAL_SECRET') ?: '';
    return $s && isset($_SERVER['HTTP_X_INTERNAL_SECRET']) && hash_equals($s, $_SERVER['HTTP_X_INTERNAL_SECRET']);
}

function ensureLearningSchema() {
    $db = getDB();
    if (!$db) return false;

    $db->exec("CREATE TABLE IF NOT EXISTS alfred_interactions (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        session_id      VARCHAR(100) NOT NULL,
        channel         VARCHAR(30) DEFAULT 'web',
        user_message    TEXT NOT NULL,
        alfred_response TEXT NOT NULL,
        tools_used      JSON DEFAULT NULL,
        agent_used      VARCHAR(50) DEFAULT NULL,
        response_time_ms INT DEFAULT NULL,
        satisfaction    TINYINT DEFAULT NULL,
        feedback        TEXT DEFAULT NULL,
        intent_category VARCHAR(50) DEFAULT NULL,
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_session (session_id),
        INDEX idx_channel (channel),
        INDEX idx_satisfaction (satisfaction),
        INDEX idx_intent (intent_category),
        INDEX idx_date (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS alfred_experiments (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        experiment_id   VARCHAR(50) UNIQUE NOT NULL,
        experiment_name VARCHAR(200) NOT NULL,
        experiment_type ENUM('prompt','response_style','tool_selection','personality') DEFAULT 'prompt',
        variant_a       TEXT NOT NULL,
        variant_b       TEXT NOT NULL,
        metric          VARCHAR(50) DEFAULT 'satisfaction',
        variant_a_score DECIMAL(5,2) DEFAULT 0.00,
        variant_b_score DECIMAL(5,2) DEFAULT 0.00,
        variant_a_count INT DEFAULT 0,
        variant_b_count INT DEFAULT 0,
        status          ENUM('active','paused','concluded') DEFAULT 'active',
        winner          ENUM('a','b','inconclusive') DEFAULT NULL,
        min_samples     INT DEFAULT 100,
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        concluded_at    TIMESTAMP NULL,
        INDEX idx_status (status),
        INDEX idx_type (experiment_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS alfred_behavioral_adjustments (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        adjustment_id   VARCHAR(50) UNIQUE NOT NULL,
        category        VARCHAR(50) NOT NULL,
        parameter       VARCHAR(100) NOT NULL,
        old_value       TEXT DEFAULT NULL,
        new_value       TEXT NOT NULL,
        reason          TEXT NOT NULL,
        applied_by      VARCHAR(50) DEFAULT 'LEARNING_ENGINE',
        rollback_until  TIMESTAMP NULL,
        status          ENUM('active','rolled_back','expired') DEFAULT 'active',
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_category (category),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    return true;
}

$action = sanitize($_GET['action'] ?? '', 30);
$db = getDB();
if (!$db) jsonResponse(['error' => 'Database unavailable'], 503);
ensureLearningSchema();

switch ($action) {

    case 'log-interaction':
        if (!isInternalCall()) { requireAuth(); }

        $input = json_decode(file_get_contents('php://input'), true);
        $sessionId = sanitize($input['session_id'] ?? '', 100);
        $userMsg = sanitize($input['user_message'] ?? '', 5000);
        $alfredResp = sanitize($input['alfred_response'] ?? '', 10000);

        if (!$sessionId || !$userMsg || !$alfredResp) jsonResponse(['error' => 'session_id, user_message, and alfred_response required'], 400);

        $stmt = $db->prepare("INSERT INTO alfred_interactions (session_id, channel, user_message, alfred_response, tools_used, agent_used, response_time_ms, intent_category) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $sessionId,
            sanitize($input['channel'] ?? 'web', 30),
            $userMsg, $alfredResp,
            isset($input['tools_used']) ? json_encode($input['tools_used']) : null,
            sanitize($input['agent_used'] ?? '', 50) ?: null,
            intval($input['response_time_ms'] ?? 0) ?: null,
            sanitize($input['intent_category'] ?? '', 50) ?: null,
        ]);

        jsonResponse(['success' => true, 'interaction_id' => $db->lastInsertId()]);
        break;

    case 'feedback':
        if (!isInternalCall()) requireAuth();

        $input = json_decode(file_get_contents('php://input'), true);
        $interactionId = intval($input['interaction_id'] ?? 0);
        $satisfaction = intval($input['satisfaction'] ?? 0);

        if (!$interactionId || $satisfaction < 1 || $satisfaction > 5) {
            jsonResponse(['error' => 'interaction_id and satisfaction (1-5) required'], 400);
        }

        $stmt = $db->prepare("UPDATE alfred_interactions SET satisfaction = ?, feedback = ? WHERE id = ?");
        $stmt->execute([$satisfaction, sanitize($input['feedback'] ?? '', 1000) ?: null, $interactionId]);

        jsonResponse(['success' => true]);
        break;

    case 'insights':
        if (!isInternalCall()) requireAuth();

        $days = min(max(intval($_GET['days'] ?? 30), 1), 365);

        $avgSatisfaction = $db->query("SELECT AVG(satisfaction) FROM alfred_interactions WHERE satisfaction IS NOT NULL AND created_at > DATE_SUB(NOW(), INTERVAL {$days} DAY)")->fetchColumn();
        $totalInteractions = $db->query("SELECT COUNT(*) FROM alfred_interactions WHERE created_at > DATE_SUB(NOW(), INTERVAL {$days} DAY)")->fetchColumn();
        $avgResponseTime = $db->query("SELECT AVG(response_time_ms) FROM alfred_interactions WHERE response_time_ms IS NOT NULL AND created_at > DATE_SUB(NOW(), INTERVAL {$days} DAY)")->fetchColumn();

        $byChannel = $db->query("SELECT channel, COUNT(*) as count, AVG(satisfaction) as avg_sat FROM alfred_interactions WHERE created_at > DATE_SUB(NOW(), INTERVAL {$days} DAY) GROUP BY channel ORDER BY count DESC")->fetchAll();
        $byIntent = $db->query("SELECT intent_category, COUNT(*) as count, AVG(satisfaction) as avg_sat FROM alfred_interactions WHERE intent_category IS NOT NULL AND created_at > DATE_SUB(NOW(), INTERVAL {$days} DAY) GROUP BY intent_category ORDER BY count DESC LIMIT 10")->fetchAll();

        // Satisfaction trend by week
        $trend = $db->query("SELECT YEARWEEK(created_at, 1) as week, AVG(satisfaction) as avg_sat, COUNT(*) as count FROM alfred_interactions WHERE satisfaction IS NOT NULL AND created_at > DATE_SUB(NOW(), INTERVAL {$days} DAY) GROUP BY week ORDER BY week ASC")->fetchAll();

        // Low-satisfaction patterns
        $lowSat = $db->query("SELECT intent_category, COUNT(*) as c, AVG(satisfaction) as avg FROM alfred_interactions WHERE satisfaction <= 2 AND intent_category IS NOT NULL AND created_at > DATE_SUB(NOW(), INTERVAL {$days} DAY) GROUP BY intent_category ORDER BY c DESC LIMIT 5")->fetchAll();

        jsonResponse([
            'success' => true,
            'period_days' => $days,
            'total_interactions' => (int)$totalInteractions,
            'avg_satisfaction' => round($avgSatisfaction ?? 0, 2),
            'avg_response_time_ms' => round($avgResponseTime ?? 0),
            'by_channel' => $byChannel,
            'by_intent' => $byIntent,
            'satisfaction_trend' => $trend,
            'improvement_areas' => $lowSat,
        ]);
        break;

    case 'prompt-variant':
        if (!isInternalCall()) { requireAuth(); if (!isAdmin()) jsonResponse(['error' => 'Admin required'], 403); }

        $input = json_decode(file_get_contents('php://input'), true);
        $name = sanitize($input['experiment_name'] ?? '', 200);
        $variantA = $input['variant_a'] ?? '';
        $variantB = $input['variant_b'] ?? '';

        if (!$name || !$variantA || !$variantB) jsonResponse(['error' => 'experiment_name, variant_a, variant_b required'], 400);

        $expId = 'EXP-' . strtoupper(bin2hex(random_bytes(6)));
        $stmt = $db->prepare("INSERT INTO alfred_experiments (experiment_id, experiment_name, experiment_type, variant_a, variant_b, metric, min_samples) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $expId, $name,
            sanitize($input['experiment_type'] ?? 'prompt', 20),
            $variantA, $variantB,
            sanitize($input['metric'] ?? 'satisfaction', 50),
            intval($input['min_samples'] ?? 100),
        ]);

        jsonResponse(['success' => true, 'experiment_id' => $expId]);
        break;

    case 'experiments':
        if (!isInternalCall()) requireAuth();

        $status = sanitize($_GET['status'] ?? '', 20);
        $sql = "SELECT * FROM alfred_experiments";
        $params = [];
        if ($status && in_array($status, ['active','paused','concluded'])) {
            $sql .= " WHERE status = ?";
            $params[] = $status;
        }
        $sql .= " ORDER BY created_at DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $experiments = $stmt->fetchAll();

        foreach ($experiments as &$e) {
            $totalSamples = $e['variant_a_count'] + $e['variant_b_count'];
            $e['total_samples'] = $totalSamples;
            $e['progress_pct'] = $e['min_samples'] > 0 ? min(100, round(($totalSamples / ($e['min_samples'] * 2)) * 100)) : 0;
        }

        jsonResponse(['success' => true, 'experiments' => $experiments]);
        break;

    case 'experiment-result':
        if (!isInternalCall()) { requireAuth(); }

        $input = json_decode(file_get_contents('php://input'), true);
        $expId = sanitize($input['experiment_id'] ?? '', 50);
        $variant = sanitize($input['variant'] ?? '', 5);
        $score = floatval($input['score'] ?? 0);

        if (!$expId || !in_array($variant, ['a','b']) || $score < 0) {
            jsonResponse(['error' => 'experiment_id, variant (a/b), and score required'], 400);
        }

        $col = $variant === 'a' ? 'variant_a' : 'variant_b';
        $db->prepare("UPDATE alfred_experiments SET {$col}_count = {$col}_count + 1, {$col}_score = (({$col}_score * {$col}_count) + ?) / ({$col}_count + 1) WHERE experiment_id = ? AND status = 'active'")->execute([$score, $expId]);

        // Auto-conclude if enough samples
        $exp = $db->prepare("SELECT * FROM alfred_experiments WHERE experiment_id = ?");
        $exp->execute([$expId]);
        $e = $exp->fetch();

        if ($e && $e['variant_a_count'] >= $e['min_samples'] && $e['variant_b_count'] >= $e['min_samples']) {
            $winner = 'inconclusive';
            $diff = abs($e['variant_a_score'] - $e['variant_b_score']);
            if ($diff > 0.1) {
                $winner = $e['variant_a_score'] > $e['variant_b_score'] ? 'a' : 'b';
            }
            $db->prepare("UPDATE alfred_experiments SET status = 'concluded', winner = ?, concluded_at = NOW() WHERE experiment_id = ?")->execute([$winner, $expId]);
        }

        jsonResponse(['success' => true]);
        break;

    case 'patterns':
        if (!isInternalCall()) requireAuth();

        $patterns = [];

        // Most common intents
        $patterns['top_intents'] = $db->query("SELECT intent_category, COUNT(*) as c FROM alfred_interactions WHERE intent_category IS NOT NULL GROUP BY intent_category ORDER BY c DESC LIMIT 10")->fetchAll();

        // Peak hours
        $patterns['peak_hours'] = $db->query("SELECT HOUR(created_at) as hour, COUNT(*) as c FROM alfred_interactions GROUP BY hour ORDER BY c DESC LIMIT 5")->fetchAll();

        // Tool popularity
        $patterns['tool_usage'] = $db->query("SELECT JSON_UNQUOTE(JSON_EXTRACT(tools_used, '$[0]')) as tool, COUNT(*) as c FROM alfred_interactions WHERE tools_used IS NOT NULL GROUP BY tool ORDER BY c DESC LIMIT 10")->fetchAll();

        // Channel distribution
        $patterns['channels'] = $db->query("SELECT channel, COUNT(*) as c, AVG(satisfaction) as avg_sat FROM alfred_interactions GROUP BY channel ORDER BY c DESC")->fetchAll();

        // Response time distribution
        $patterns['response_time_buckets'] = $db->query("SELECT CASE WHEN response_time_ms < 500 THEN 'fast (<500ms)' WHEN response_time_ms < 2000 THEN 'medium (500ms-2s)' WHEN response_time_ms < 5000 THEN 'slow (2-5s)' ELSE 'very_slow (>5s)' END as bucket, COUNT(*) as c FROM alfred_interactions WHERE response_time_ms IS NOT NULL GROUP BY bucket")->fetchAll();

        jsonResponse(['success' => true, 'patterns' => $patterns]);
        break;

    case 'performance':
        if (!isInternalCall()) requireAuth();

        $metrics = [
            'total_interactions' => (int)$db->query("SELECT COUNT(*) FROM alfred_interactions")->fetchColumn(),
            'avg_satisfaction' => round($db->query("SELECT AVG(satisfaction) FROM alfred_interactions WHERE satisfaction IS NOT NULL")->fetchColumn() ?? 0, 2),
            'satisfaction_5_pct' => round($db->query("SELECT (COUNT(CASE WHEN satisfaction = 5 THEN 1 END) / COUNT(*)) * 100 FROM alfred_interactions WHERE satisfaction IS NOT NULL")->fetchColumn() ?? 0, 1),
            'avg_response_ms' => round($db->query("SELECT AVG(response_time_ms) FROM alfred_interactions WHERE response_time_ms IS NOT NULL")->fetchColumn() ?? 0),
            'active_experiments' => (int)$db->query("SELECT COUNT(*) FROM alfred_experiments WHERE status = 'active'")->fetchColumn(),
            'concluded_experiments' => (int)$db->query("SELECT COUNT(*) FROM alfred_experiments WHERE status = 'concluded'")->fetchColumn(),
            'active_adjustments' => (int)$db->query("SELECT COUNT(*) FROM alfred_behavioral_adjustments WHERE status = 'active'")->fetchColumn(),
            'interactions_today' => (int)$db->query("SELECT COUNT(*) FROM alfred_interactions WHERE DATE(created_at) = CURDATE()")->fetchColumn(),
        ];

        jsonResponse(['success' => true, 'performance' => $metrics]);
        break;

    case 'adjust':
        if (!isInternalCall()) { requireAuth(); if (!isAdmin()) jsonResponse(['error' => 'Admin required'], 403); }

        $input = json_decode(file_get_contents('php://input'), true);
        $category = sanitize($input['category'] ?? '', 50);
        $parameter = sanitize($input['parameter'] ?? '', 100);
        $newValue = $input['new_value'] ?? '';
        $reason = sanitize($input['reason'] ?? '', 500);

        if (!$category || !$parameter || !$newValue || !$reason) {
            jsonResponse(['error' => 'category, parameter, new_value, and reason required'], 400);
        }

        $adjId = 'ADJ-' . strtoupper(bin2hex(random_bytes(6)));
        $rollbackDays = intval($input['rollback_days'] ?? 7);

        $stmt = $db->prepare("INSERT INTO alfred_behavioral_adjustments (adjustment_id, category, parameter, old_value, new_value, reason, applied_by, rollback_until) VALUES (?, ?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? DAY))");
        $stmt->execute([
            $adjId, $category, $parameter,
            sanitize($input['old_value'] ?? '', 1000) ?: null,
            $newValue, $reason,
            sanitize($input['applied_by'] ?? 'ADMIN', 50),
            $rollbackDays,
        ]);

        jsonResponse(['success' => true, 'adjustment_id' => $adjId, 'rollback_available_for_days' => $rollbackDays]);
        break;

    default:
        jsonResponse(['error' => 'Unknown action', 'available_actions' => ['log-interaction','feedback','insights','prompt-variant','experiments','experiment-result','patterns','performance','adjust']], 400);
}
