<?php
/**
 * Usage Tracking API
 * Tracks and reports resource usage for billing/metering purposes.
 *
 * Actions: summary, daily, history, record, limits, alerts, overage
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// ── Valid resource types ───────────────────────────────────────────────────
define('VALID_RESOURCE_TYPES', [
    'api_call', 'voice_minute', 'storage_mb', 'agent_hour', 'tool_execution',
    'sms', 'whatsapp', 'image_gen', 'doc_analysis', 'translation_min', 'conference_min'
]);

// ── Route Action ───────────────────────────────────────────────────────────
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'summary':   getSummary();   break;
    case 'daily':     getDaily();     break;
    case 'history':   getHistory();   break;
    case 'record':    recordAction(); break;
    case 'limits':    getLimits();    break;
    case 'alerts':    getAlerts();    break;
    case 'overage':   getOverage();   break;
    default:
        jsonResponse(['error' => 'Invalid action. Valid: summary, daily, history, record, limits, alerts, overage'], 400);
}

// ── Auth Helper ────────────────────────────────────────────────────────────

function requireAuth(): int {
    $uid = $_SESSION['uid'] ?? $_SESSION['client_id'] ?? null;
    if (!$uid) {
        jsonResponse(['error' => 'Authentication required', 'login_url' => SITE_URL . '/api/auth.php'], 401);
    }
    return (int) $uid;
}

// ── Get User Plan ──────────────────────────────────────────────────────────

function getUserPlan(int $userId): string {
    $db = getDB();
    if (!$db) return 'free';

    // Check alfred_api_keys for plan info
    $stmt = $db->prepare("SELECT plan FROM alfred_api_keys WHERE client_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if ($row && !empty($row['plan'])) {
        return strtolower($row['plan']);
    }

    // Fallback: check session
    $plan = $_SESSION['plan'] ?? $_SESSION['client_plan'] ?? 'free';
    return strtolower($plan);
}

// ── Current billing period ─────────────────────────────────────────────────

function currentBillingPeriod(): string {
    return date('Y-m');
}

// ── Get current month usage for a resource type ────────────────────────────

function getCurrentUsage(int $userId, string $resourceType): float {
    $db = getDB();
    if (!$db) return 0;

    $period = currentBillingPeriod();

    $stmt = $db->prepare("
        SELECT COALESCE(SUM(quantity), 0)
        FROM alfred_usage
        WHERE user_id = ?
          AND (resource_type = ? OR resource = ?)
          AND (billing_period = ? OR DATE_FORMAT(created_at, '%Y-%m') = ?)
    ");
    $stmt->execute([$userId, $resourceType, $resourceType, $period, $period]);
    return (float) $stmt->fetchColumn();
}

// ── Get plan limit for a resource type ─────────────────────────────────────

function getPlanLimit(string $plan, string $resourceType): array {
    $db = getDB();
    if (!$db) return ['monthly_limit' => 0, 'overage_rate' => 0];

    $stmt = $db->prepare("SELECT monthly_limit, overage_rate FROM alfred_usage_limits WHERE plan = ? AND resource_type = ?");
    $stmt->execute([$plan, $resourceType]);
    $row = $stmt->fetch();
    if ($row) {
        return [
            'monthly_limit' => (int) $row['monthly_limit'],
            'overage_rate'  => (float) $row['overage_rate'],
        ];
    }
    return ['monthly_limit' => 0, 'overage_rate' => 0];
}

// ══════════════════════════════════════════════════════════════════════════
//  recordUsage() — Core helper for recording usage events
// ══════════════════════════════════════════════════════════════════════════

/**
 * Record a usage event.
 *
 * @param int    $userId       Client / user ID
 * @param string $resourceType One of VALID_RESOURCE_TYPES
 * @param float  $quantity     Units consumed (default 1)
 * @param array  $metadata     Optional JSON metadata
 * @return array Result with success/error info
 */
function recordUsage(int $userId, string $resourceType, float $quantity = 1, array $metadata = []): array {
    $db = getDB();
    if (!$db) return ['success' => false, 'error' => 'Database unavailable'];

    // Validate resource type
    if (!in_array($resourceType, VALID_RESOURCE_TYPES)) {
        return ['success' => false, 'error' => 'Invalid resource type: ' . $resourceType];
    }

    $plan    = getUserPlan($userId);
    $period  = currentBillingPeriod();
    $current = getCurrentUsage($userId, $resourceType);
    $limit   = getPlanLimit($plan, $resourceType);

    $monthlyLimit = $limit['monthly_limit'];
    $overageRate  = $limit['overage_rate'];

    // 0 means unlimited
    $isUnlimited = ($monthlyLimit === 0);
    $isOverage   = false;

    if (!$isUnlimited) {
        $newTotal = $current + $quantity;

        if ($newTotal > $monthlyLimit) {
            // Plan allows overage (has a rate > 0)?
            if ($overageRate > 0) {
                $isOverage = true;
            } else {
                // Free plan or no overage — deny
                return [
                    'success'    => false,
                    'error'      => 'Usage limit exceeded',
                    'resource'   => $resourceType,
                    'used'       => $current,
                    'limit'      => $monthlyLimit,
                    'plan'       => $plan,
                    'upgrade_url'=> SITE_URL . '/pricing.php',
                ];
            }
        }

        // Check thresholds and create alerts
        $thresholds = [80, 90, 100];
        foreach ($thresholds as $t) {
            $thresholdValue = $monthlyLimit * ($t / 100);
            if ($current < $thresholdValue && ($current + $quantity) >= $thresholdValue) {
                createAlert($userId, $resourceType, $t, $current + $quantity, $monthlyLimit, $period);
            }
        }
    }

    // Map resource type to ENUM value if it matches
    $enumValues = ['api_call','voice_minute','agent_hour','storage_mb','sms','whatsapp','image_gen','doc_analysis','translation_min','conference_min'];
    $enumResource = in_array($resourceType, $enumValues) ? $resourceType : 'api_call';

    // Calculate unit cost
    $unitCost = $isOverage ? $overageRate : 0;

    $metaJson = !empty($metadata) ? json_encode($metadata) : null;

    $stmt = $db->prepare("
        INSERT INTO alfred_usage (user_id, resource, resource_type, quantity, unit_cost, is_overage, billing_period, metadata, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $userId,
        $enumResource,
        $resourceType,
        $quantity,
        $unitCost,
        $isOverage ? 1 : 0,
        $period,
        $metaJson,
    ]);

    $newTotal = $current + $quantity;
    $pct = $isUnlimited ? 0 : round(($newTotal / $monthlyLimit) * 100, 1);

    return [
        'success'      => true,
        'resource_type' => $resourceType,
        'quantity'      => $quantity,
        'total_used'    => $newTotal,
        'limit'         => $monthlyLimit,
        'unlimited'     => $isUnlimited,
        'percentage'    => $pct,
        'is_overage'    => $isOverage,
        'overage_cost'  => $isOverage ? round($quantity * $overageRate, 4) : 0,
    ];
}

// ── Create alert ───────────────────────────────────────────────────────────

function createAlert(int $userId, string $resourceType, int $threshold, float $currentUsage, int $monthlyLimit, string $period): void {
    $db = getDB();
    if (!$db) return;

    // Don't duplicate alerts for same user/resource/threshold/period
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM alfred_usage_alerts
        WHERE user_id = ? AND resource_type = ? AND threshold = ? AND billing_period = ?
    ");
    $stmt->execute([$userId, $resourceType, $threshold, $period]);
    if ((int) $stmt->fetchColumn() > 0) return;

    $stmt = $db->prepare("
        INSERT INTO alfred_usage_alerts (user_id, resource_type, threshold, current_usage, monthly_limit, billing_period, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$userId, $resourceType, $threshold, $currentUsage, $monthlyLimit, $period]);
}

// ══════════════════════════════════════════════════════════════════════════
//  Action Handlers
// ══════════════════════════════════════════════════════════════════════════

/**
 * GET summary — Current month usage breakdown
 */
function getSummary(): void {
    $userId = requireAuth();
    $plan   = getUserPlan($userId);
    $period = currentBillingPeriod();
    $db     = getDB();
    if (!$db) jsonResponse(['error' => 'Database unavailable'], 500);

    // Get all limits for this plan
    $stmt = $db->prepare("SELECT resource_type, monthly_limit, overage_rate FROM alfred_usage_limits WHERE plan = ?");
    $stmt->execute([$plan]);
    $limits = $stmt->fetchAll();

    $breakdown = [];

    foreach ($limits as $lim) {
        $rt    = $lim['resource_type'];
        $limit = (int) $lim['monthly_limit'];
        $rate  = (float) $lim['overage_rate'];
        $used  = getCurrentUsage($userId, $rt);

        $unlimited  = ($limit === 0);
        $percentage = $unlimited ? 0 : round(($used / $limit) * 100, 1);

        // Overage quantity and cost
        $overageQty  = (!$unlimited && $used > $limit) ? $used - $limit : 0;
        $overageCost = round($overageQty * $rate, 4);

        $breakdown[] = [
            'resource_type' => $rt,
            'used'          => round($used, 4),
            'limit'         => $limit,
            'unlimited'     => $unlimited,
            'percentage'    => min($percentage, 999),
            'remaining'     => $unlimited ? null : max(0, $limit - $used),
            'overage_qty'   => round($overageQty, 4),
            'overage_cost'  => $overageCost,
            'overage_rate'  => $rate,
        ];
    }

    jsonResponse([
        'success'        => true,
        'plan'           => $plan,
        'billing_period' => $period,
        'breakdown'      => $breakdown,
    ]);
}

/**
 * GET daily — Daily usage for last 30 days
 */
function getDaily(): void {
    $userId = requireAuth();
    $db     = getDB();
    if (!$db) jsonResponse(['error' => 'Database unavailable'], 500);

    $resourceFilter = sanitize($_GET['resource_type'] ?? '', 50);

    $where  = "WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $params = [$userId];

    if ($resourceFilter && in_array($resourceFilter, VALID_RESOURCE_TYPES)) {
        $where .= " AND (resource_type = ? OR resource = ?)";
        $params[] = $resourceFilter;
        $params[] = $resourceFilter;
    }

    $stmt = $db->prepare("
        SELECT DATE(created_at) AS day,
               COALESCE(resource_type, resource) AS resource_type,
               SUM(quantity) AS total_quantity,
               COUNT(*) AS event_count,
               SUM(CASE WHEN is_overage = 1 THEN quantity ELSE 0 END) AS overage_qty,
               SUM(CASE WHEN is_overage = 1 THEN quantity * unit_cost ELSE 0 END) AS overage_cost
        FROM alfred_usage $where
        GROUP BY DATE(created_at), COALESCE(resource_type, resource)
        ORDER BY day ASC, resource_type ASC
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    jsonResponse([
        'success' => true,
        'days'    => $rows,
        'period'  => '30d',
    ]);
}

/**
 * GET history — Monthly usage for last 12 months
 */
function getHistory(): void {
    $userId = requireAuth();
    $db     = getDB();
    if (!$db) jsonResponse(['error' => 'Database unavailable'], 500);

    $stmt = $db->prepare("
        SELECT COALESCE(billing_period, DATE_FORMAT(created_at, '%Y-%m')) AS month,
               COALESCE(resource_type, resource) AS resource_type,
               SUM(quantity) AS total_quantity,
               COUNT(*) AS event_count,
               SUM(CASE WHEN is_overage = 1 THEN quantity ELSE 0 END) AS overage_qty,
               SUM(CASE WHEN is_overage = 1 THEN quantity * unit_cost ELSE 0 END) AS overage_cost
        FROM alfred_usage
        WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY month, resource_type
        ORDER BY month DESC, resource_type ASC
    ");
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll();

    // Group by month
    $months = [];
    foreach ($rows as $r) {
        $m = $r['month'];
        if (!isset($months[$m])) {
            $months[$m] = ['month' => $m, 'resources' => [], 'total_events' => 0, 'total_overage_cost' => 0];
        }
        $months[$m]['resources'][] = [
            'resource_type'  => $r['resource_type'],
            'total_quantity' => round((float) $r['total_quantity'], 4),
            'event_count'    => (int) $r['event_count'],
            'overage_qty'    => round((float) $r['overage_qty'], 4),
            'overage_cost'   => round((float) $r['overage_cost'], 4),
        ];
        $months[$m]['total_events'] += (int) $r['event_count'];
        $months[$m]['total_overage_cost'] += (float) $r['overage_cost'];
    }

    jsonResponse([
        'success' => true,
        'history' => array_values($months),
    ]);
}

/**
 * POST record — Record a usage event
 */
function recordAction(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['error' => 'POST required'], 405);
    }

    $userId = requireAuth();

    $resourceType = sanitize($_POST['resource_type'] ?? '', 50);
    $quantity     = (float) ($_POST['quantity'] ?? 1);
    $metaRaw      = $_POST['metadata'] ?? '';

    if (empty($resourceType)) {
        jsonResponse(['error' => 'resource_type is required'], 400);
    }
    if ($quantity <= 0 || $quantity > 999999) {
        jsonResponse(['error' => 'quantity must be between 0 and 999999'], 400);
    }

    $metadata = [];
    if ($metaRaw) {
        $decoded = json_decode($metaRaw, true);
        if (is_array($decoded)) {
            $metadata = $decoded;
        }
    }

    $result = recordUsage($userId, $resourceType, $quantity, $metadata);

    if ($result['success']) {
        jsonResponse($result);
    } else {
        jsonResponse($result, 429);
    }
}

/**
 * GET limits — Plan limits for authenticated user
 */
function getLimits(): void {
    $userId = requireAuth();
    $plan   = getUserPlan($userId);
    $db     = getDB();
    if (!$db) jsonResponse(['error' => 'Database unavailable'], 500);

    $stmt = $db->prepare("SELECT resource_type, monthly_limit, overage_rate FROM alfred_usage_limits WHERE plan = ? ORDER BY resource_type ASC");
    $stmt->execute([$plan]);
    $limits = $stmt->fetchAll();

    $formatted = [];
    foreach ($limits as $l) {
        $unlimited = ((int) $l['monthly_limit'] === 0);
        $formatted[] = [
            'resource_type' => $l['resource_type'],
            'monthly_limit' => (int) $l['monthly_limit'],
            'unlimited'     => $unlimited,
            'overage_rate'  => (float) $l['overage_rate'],
            'display_limit' => $unlimited ? 'Unlimited' : number_format((int) $l['monthly_limit']),
        ];
    }

    jsonResponse([
        'success' => true,
        'plan'    => $plan,
        'limits'  => $formatted,
    ]);
}

/**
 * GET alerts — Usage threshold alerts
 */
function getAlerts(): void {
    $userId = requireAuth();
    $period = sanitize($_GET['period'] ?? currentBillingPeriod(), 7);
    $db     = getDB();
    if (!$db) jsonResponse(['error' => 'Database unavailable'], 500);

    $stmt = $db->prepare("
        SELECT id, resource_type, threshold, current_usage, monthly_limit, billing_period, acknowledged, created_at
        FROM alfred_usage_alerts
        WHERE user_id = ? AND billing_period = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId, $period]);
    $alerts = $stmt->fetchAll();

    // Format alerts
    $formatted = [];
    foreach ($alerts as $a) {
        $percentage = (int) $a['monthly_limit'] > 0
            ? round(((float) $a['current_usage'] / (int) $a['monthly_limit']) * 100, 1)
            : 0;

        $severity = 'info';
        if ((int) $a['threshold'] >= 100) $severity = 'critical';
        elseif ((int) $a['threshold'] >= 90) $severity = 'warning';

        $formatted[] = [
            'id'             => (int) $a['id'],
            'resource_type'  => $a['resource_type'],
            'threshold'      => (int) $a['threshold'],
            'percentage'     => $percentage,
            'current_usage'  => round((float) $a['current_usage'], 4),
            'monthly_limit'  => (int) $a['monthly_limit'],
            'severity'       => $severity,
            'acknowledged'   => (bool) $a['acknowledged'],
            'billing_period' => $a['billing_period'],
            'created_at'     => $a['created_at'],
        ];
    }

    // Summary: any critical?
    $hasCritical = false;
    $hasWarning  = false;
    foreach ($formatted as $f) {
        if ($f['severity'] === 'critical') $hasCritical = true;
        if ($f['severity'] === 'warning')  $hasWarning = true;
    }

    jsonResponse([
        'success'      => true,
        'alerts'       => $formatted,
        'has_critical'  => $hasCritical,
        'has_warning'   => $hasWarning,
        'total_alerts'  => count($formatted),
        'billing_period'=> $period,
    ]);
}

/**
 * GET overage — Overage charges for current period
 */
function getOverage(): void {
    $userId = requireAuth();
    $plan   = getUserPlan($userId);
    $period = sanitize($_GET['period'] ?? currentBillingPeriod(), 7);
    $db     = getDB();
    if (!$db) jsonResponse(['error' => 'Database unavailable'], 500);

    $stmt = $db->prepare("
        SELECT COALESCE(resource_type, resource) AS resource_type,
               SUM(quantity) AS overage_quantity,
               SUM(quantity * unit_cost) AS overage_cost,
               COUNT(*) AS event_count
        FROM alfred_usage
        WHERE user_id = ? AND is_overage = 1
          AND (billing_period = ? OR DATE_FORMAT(created_at, '%Y-%m') = ?)
        GROUP BY resource_type
        ORDER BY overage_cost DESC
    ");
    $stmt->execute([$userId, $period, $period]);
    $rows = $stmt->fetchAll();

    $totalCost = 0;
    $items = [];
    foreach ($rows as $r) {
        $cost = round((float) $r['overage_cost'], 4);
        $totalCost += $cost;
        $items[] = [
            'resource_type'    => $r['resource_type'],
            'overage_quantity' => round((float) $r['overage_quantity'], 4),
            'overage_cost'     => $cost,
            'event_count'      => (int) $r['event_count'],
        ];
    }

    jsonResponse([
        'success'        => true,
        'plan'           => $plan,
        'billing_period' => $period,
        'overage_items'  => $items,
        'total_overage'  => round($totalCost, 2),
        'currency'       => 'USD',
    ]);
}
