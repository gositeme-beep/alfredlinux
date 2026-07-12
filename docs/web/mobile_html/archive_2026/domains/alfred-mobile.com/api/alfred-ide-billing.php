<?php
/**
 * Alfred IDE — Token Billing API
 * /api/alfred-ide-billing.php
 *
 * Called by the alfred-account VS Code extension after each AI operation.
 * Also used by the commander dashboard to view usage stats.
 *
 * Endpoints (all via ?action=):
 *   POST record   — log token usage for a completed AI operation
 *   GET  balance  — current month usage + plan info for the authenticated user
 *   GET  history  — paginated usage log
 *   GET  plans    — return all billing plan tiers
 *   GET  models   — return model cost table
 *   GET  summary  — commander-only: all-user aggregate stats
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type, X-IDE-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// ── Bootstrap ─────────────────────────────────────────────────────────────
define('ROOT', dirname(__DIR__));
$envFile = dirname(__DIR__) . '/../.env.php';
if (file_exists($envFile)) require_once $envFile;
require_once ROOT . '/includes/db-config.inc.php';

// DB connection
try {
    $pdo = getSharedDB();
} catch (Exception $e) {
    http_response_code(503);
    echo json_encode(['error' => 'DB unavailable']); exit;
}

// ── Auth ───────────────────────────────────────────────────────────────────
function getAuthUser(PDO $pdo): ?array {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['HTTP_X_IDE_TOKEN'] ?? '';
    if (!$header) return null;

    $token = preg_replace('/^Bearer\s+/i', '', trim($header));
    if (!$token) return null;

    // Check alfred_oauth_tokens
    $stmt = $pdo->prepare(
        'SELECT t.user_id, t.access_token, t.expires_at,
                c.email, c.firstname, c.lastname
         FROM alfred_oauth_tokens t
         JOIN clients c ON c.id = t.user_id
         WHERE t.access_token = ?
           AND t.revoked_at IS NULL
           AND (t.expires_at IS NULL OR t.expires_at > NOW())
         LIMIT 1'
    );
    $stmt->execute([hash('sha256', $token)]);
    $row = $stmt->fetch();
    if ($row) return $row;

    // Fallback: plain token match (for tokens stored unhashed)
    $stmt2 = $pdo->prepare(
        'SELECT t.user_id, t.access_token, t.expires_at,
                c.email, c.firstname, c.lastname
         FROM alfred_oauth_tokens t
         JOIN clients c ON c.id = t.user_id
         WHERE t.access_token = ?
           AND t.revoked_at IS NULL
           AND (t.expires_at IS NULL OR t.expires_at > NOW())
         LIMIT 1'
    );
    $stmt2->execute([$token]);
    return $stmt2->fetch() ?: null;
}

function getUserPlan(PDO $pdo, int $userId): string {
    // Check active subscriptions in WHMCS
    $stmt = $pdo->prepare(
        'SELECT b.plan
         FROM alfred_ide_balance b
         WHERE b.user_id = ? AND b.billing_period = DATE_FORMAT(NOW(), "%Y-%m")
         LIMIT 1'
    );
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if ($row && !empty($row['plan'])) return $row['plan'];

    // Check WHMCS subscription / product
    $stmt2 = $pdo->prepare(
        'SELECT p.ide_plan FROM tblclients c
         LEFT JOIN (SELECT userid, "starter" as ide_plan FROM tblhosting WHERE packageid IN (
           SELECT id FROM tblproducts WHERE name LIKE "%Alfred IDE%"
         ) AND domainstatus="Active" LIMIT 1) p ON p.userid = c.id
         WHERE c.id = ?'
    );
    $stmt2->execute([$userId]);
    $row2 = $stmt2->fetch();
    if ($row2 && $row2['ide_plan']) return $row2['ide_plan'];

    return 'free';
}

function requireAuth(PDO $pdo): array {
    $user = getAuthUser($pdo);
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized — include Authorization: Bearer <token>']); exit;
    }
    return $user;
}

function isCommander(array $user): bool {
    return (int)$user['user_id'] === 33;
}

// ── Helpers ────────────────────────────────────────────────────────────────
function currentPeriod(): string { return date('Y-m'); }

function getOrCreateBalance(PDO $pdo, int $userId, string $plan): array {
    $period = currentPeriod();

    // Get plan token allowance
    $planRow = $pdo->prepare('SELECT monthly_tokens FROM alfred_ide_plans WHERE plan_name = ? LIMIT 1');
    $planRow->execute([$plan]);
    $planData = $planRow->fetch();
    $included = $planData ? (int)$planData['monthly_tokens'] : 50000;

    $pdo->prepare(
        'INSERT INTO alfred_ide_balance (user_id, billing_period, plan, tokens_included)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
           plan = IF(plan != VALUES(plan), VALUES(plan), plan),
           tokens_included = IF(tokens_included != VALUES(tokens_included), VALUES(tokens_included), tokens_included)'
    )->execute([$userId, $period, $plan, $included]);

    $stmt = $pdo->prepare('SELECT * FROM alfred_ide_balance WHERE user_id = ? AND billing_period = ? LIMIT 1');
    $stmt->execute([$userId, $period]);
    return $stmt->fetch();
}

function getModelCost(PDO $pdo, string $model): array {
    $stmt = $pdo->prepare('SELECT input_per_1k, output_per_1k FROM alfred_ide_model_costs WHERE model_id = ? LIMIT 1');
    $stmt->execute([$model]);
    $row = $stmt->fetch();
    return $row ?: ['input_per_1k' => 0.000020, 'output_per_1k' => 0.000020]; // default: local Ollama
}

// ── Actions ────────────────────────────────────────────────────────────────
$action = $_GET['action'] ?? $_POST['action'] ?? 'balance';
$method = $_SERVER['REQUEST_METHOD'];

// ── GET /plans — public ────────────────────────────────────────────────────
if ($action === 'plans') {
    $rows = $pdo->query('SELECT * FROM alfred_ide_plans WHERE is_active=1 ORDER BY sort_order')->fetchAll();
    foreach ($rows as &$r) {
        if ($r['features']) $r['features'] = json_decode($r['features'], true);
    }
    echo json_encode(['ok' => true, 'plans' => $rows]); exit;
}

// ── GET /models — public ──────────────────────────────────────────────────
if ($action === 'models') {
    $rows = $pdo->query('SELECT * FROM alfred_ide_model_costs WHERE is_active=1 ORDER BY tier')->fetchAll();
    echo json_encode(['ok' => true, 'models' => $rows]); exit;
}

// ── POST /record — log a completed AI operation ───────────────────────────
if ($action === 'record' && $method === 'POST') {
    $user = requireAuth($pdo);
    $uid = (int)$user['user_id'];

    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $feature      = in_array($body['feature'] ?? '', ['completion','chat','explain','fix','generate','refactor','docs','test'])
                    ? $body['feature'] : 'completion';
    $model        = preg_replace('/[^a-zA-Z0-9\/_-]/', '', $body['model'] ?? 'ollama/llama3');
    $inputTokens  = max(0, (int)($body['input_tokens'] ?? 0));
    $outputTokens = max(0, (int)($body['output_tokens'] ?? 0));
    $language     = substr(preg_replace('/[^a-zA-Z0-9+#.-]/', '', $body['language'] ?? ''), 0, 50);
    $sessionToken = !empty($user['access_token']) ? substr((string)$user['access_token'], 0, 64) : null;
    $metadata     = $body['metadata'] ?? null;

    if ($inputTokens === 0 && $outputTokens === 0) {
        echo json_encode(['ok' => true, 'skipped' => 'zero tokens']); exit;
    }

    if (!is_array($metadata)) {
        $metadata = null;
    }

    $plan = getUserPlan($pdo, $uid);
    $balance = getOrCreateBalance($pdo, $uid, $plan);
    $modelCost = getModelCost($pdo, $model);

    $totalTokens = $inputTokens + $outputTokens;
    $costUsd = ($inputTokens / 1000 * $modelCost['input_per_1k'])
             + ($outputTokens / 1000 * $modelCost['output_per_1k']);

    $included = (int)$balance['tokens_included'];
    $used     = (int)$balance['tokens_used'];
    $previousOverage = $included > 0 ? max(0, $used - $included) : 0;
    $newOverageTotal = $included > 0 ? max(0, ($used + $totalTokens) - $included) : 0;
    $overageTokens = max(0, $newOverageTotal - $previousOverage);
    $isOverage = $overageTokens > 0 ? 1 : 0;

    // Overage cost from plan
    $planRow = $pdo->prepare('SELECT overage_per_1k FROM alfred_ide_plans WHERE plan_name = ? LIMIT 1');
    $planRow->execute([$plan]);
    $planData = $planRow->fetch();
    $overageRate = $planData ? (float)$planData['overage_per_1k'] : 0;
    $overageCost = $overageTokens / 1000 * $overageRate;

    $period = currentPeriod();

    try {
        $pdo->beginTransaction();

        // Insert usage record
        $pdo->prepare(
            'INSERT INTO alfred_ide_usage
             (user_id, session_token, feature, model, input_tokens, output_tokens, cost_usd, language, billing_period, is_overage, metadata)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $uid,
            $sessionToken,
            $feature,
            $model,
            $inputTokens,
            $outputTokens,
            $costUsd,
            $language ?: null,
            $period,
            $isOverage,
            $metadata ? json_encode($metadata, JSON_UNESCAPED_SLASHES) : null,
        ]);

        // Update balance
        $pdo->prepare(
            'UPDATE alfred_ide_balance
             SET tokens_used = tokens_used + ?,
                 tokens_overage = tokens_overage + ?,
                 cost_overage_usd = cost_overage_usd + ?
             WHERE user_id = ? AND billing_period = ?'
        )->execute([$totalTokens, $overageTokens, $overageCost, $uid, $period]);

        // Also log to alfred_usage for unified billing view
        $pdo->prepare(
            'INSERT INTO alfred_usage (user_id, resource, resource_type, quantity, unit_cost, is_overage, billing_period, metadata)
             VALUES (?, "api_call", ?, ?, ?, ?, ?, ?)'
        )->execute([
            $uid,
            "ide_{$feature}",
            $totalTokens,
            $costUsd / max(1, $totalTokens),
            $isOverage,
            $period,
            $metadata ? json_encode($metadata, JSON_UNESCAPED_SLASHES) : null,
        ]);

        // Treasury: record revenue if there's overage cost
        if ($overageCost > 0) {
            $pdo->prepare(
                'INSERT INTO alfred_treasury (entry_type, category, amount_cents, source, description, reference_id)
                 VALUES ("income", "ide_billing", ?, "alfred_ide", ?, ?)'
            )->execute([
                (int)round($overageCost * 100),
                "IDE token overage: {$overageTokens} tokens @ \${$overageRate}/1k",
                "user_{$uid}_{$period}"
            ]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('alfred-ide-billing record failed: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to record usage']);
        exit;
    }

    echo json_encode([
        'ok'              => true,
        'tokens_consumed' => $totalTokens,
        'tokens_used_mo'  => $used + $totalTokens,
        'tokens_included' => $included,
        'is_overage'      => (bool)$isOverage,
        'cost_usd'        => round($costUsd, 6),
        'overage_cost'    => round($overageCost, 6)
    ]);
    exit;
}

// ── GET /balance — current period stats for authed user ──────────────────
if ($action === 'balance') {
    $user = requireAuth($pdo);
    $uid = (int)$user['user_id'];
    $plan = getUserPlan($pdo, $uid);
    $balance = getOrCreateBalance($pdo, $uid, $plan);

    $planStmt = $pdo->prepare('SELECT * FROM alfred_ide_plans WHERE plan_name = ? LIMIT 1');
    $planStmt->execute([$plan]);
    $planRow = $planStmt->fetch();
    if ($planRow && $planRow['features']) $planRow['features'] = json_decode($planRow['features'], true);

    $used     = (int)$balance['tokens_used'];
    $included = (int)$balance['tokens_included'];
    $pct      = $included > 0 ? round($used / $included * 100, 1) : 0;

    $planName = $planRow['plan_name'] ?? $plan;

    echo json_encode([
        'ok'             => true,
        'user'           => ['id' => $uid, 'email' => $user['email'], 'name' => trim($user['firstname'] . ' ' . $user['lastname'])],
        'plan'           => (string)$planName,
        'plan_details'   => $planRow ?: ['plan_name' => $plan],
        'period'         => $balance['billing_period'],
        'tokens_included'=> $included,
        'tokens_used'    => $used,
        'tokens_remaining' => max(0, $included - $used),
        'tokens_overage' => (int)$balance['tokens_overage'],
        'overage_cost'   => (float)$balance['cost_overage_usd'],
        'percent_used'   => $pct,
        'unlimited'      => ($included === 0)
    ]);
    exit;
}

// ── GET /history — paginated usage log ────────────────────────────────────
if ($action === 'history') {
    $user = requireAuth($pdo);
    $uid = (int)$user['user_id'];
    $period  = preg_replace('/[^0-9-]/', '', $_GET['period'] ?? currentPeriod());
    $limit   = min(100, max(1, (int)($_GET['limit'] ?? 50)));
    $offset  = max(0, (int)($_GET['offset'] ?? 0));

    $stmt = $pdo->prepare(
        'SELECT id, feature, model, input_tokens, output_tokens, cost_usd,
                language, is_overage, created_at
         FROM alfred_ide_usage
         WHERE user_id = ? AND billing_period = ?
         ORDER BY created_at DESC
         LIMIT ? OFFSET ?'
    );
    $stmt->execute([$uid, $period, $limit, $offset]);
    $rows = $stmt->fetchAll();

    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM alfred_ide_usage WHERE user_id=? AND billing_period=?');
    $countStmt->execute([$uid, $period]);
    $total = (int)$countStmt->fetchColumn();

    echo json_encode(['ok' => true, 'period' => $period, 'total' => $total, 'rows' => $rows]);
    exit;
}

// ── GET /summary — Commander-only: all-user aggregate ───────────────────
if ($action === 'summary') {
    $user = requireAuth($pdo);
    if (!isCommander($user)) {
        http_response_code(403);
        echo json_encode(['error' => 'Commander only']); exit;
    }

    $period = preg_replace('/[^0-9-]/', '', $_GET['period'] ?? currentPeriod());

    // Per-feature breakdown
    $featureStmt = $pdo->prepare(
        'SELECT feature,
                COUNT(*) as requests,
                SUM(input_tokens) as input_tokens,
                SUM(output_tokens) as output_tokens,
                SUM(input_tokens + output_tokens) as total_tokens,
                SUM(cost_usd) as cost_usd,
                COUNT(DISTINCT user_id) as unique_users
         FROM alfred_ide_usage
         WHERE billing_period = ?
         GROUP BY feature ORDER BY total_tokens DESC'
    );
    $featureStmt->execute([$period]);

    // Per-model breakdown
    $modelStmt = $pdo->prepare(
        'SELECT model,
                COUNT(*) as requests,
                SUM(input_tokens + output_tokens) as total_tokens,
                SUM(cost_usd) as cost_usd
         FROM alfred_ide_usage
         WHERE billing_period = ?
         GROUP BY model ORDER BY total_tokens DESC'
    );
    $modelStmt->execute([$period]);

    // Plan distribution
    $planStmt = $pdo->prepare(
        'SELECT plan, COUNT(*) as users, SUM(tokens_used) as tokens_used, SUM(cost_overage_usd) as revenue
         FROM alfred_ide_balance WHERE billing_period = ?
         GROUP BY plan ORDER BY users DESC'
    );
    $planStmt->execute([$period]);

    // Daily usage trend (last 30 days)
    $dailyStmt = $pdo->prepare(
        'SELECT DATE(created_at) as day,
                COUNT(*) as requests,
                SUM(input_tokens + output_tokens) as tokens,
                COUNT(DISTINCT user_id) as users
         FROM alfred_ide_usage
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
         GROUP BY DATE(created_at) ORDER BY day ASC'
    );
    $dailyStmt->execute();

    // Totals
    $totalsStmt = $pdo->prepare(
        'SELECT COUNT(*) as total_requests,
                SUM(input_tokens + output_tokens) as total_tokens,
                SUM(cost_usd) as total_cost,
                COUNT(DISTINCT user_id) as active_users
         FROM alfred_ide_usage WHERE billing_period = ?'
    );
    $totalsStmt->execute([$period]);

    echo json_encode([
        'ok'       => true,
        'period'   => $period,
        'totals'   => $totalsStmt->fetch(),
        'features' => $featureStmt->fetchAll(),
        'models'   => $modelStmt->fetchAll(),
        'plans'    => $planStmt->fetchAll(),
        'daily'    => $dailyStmt->fetchAll()
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => "Unknown action: {$action}. Use: record|balance|history|plans|models|summary"]);
