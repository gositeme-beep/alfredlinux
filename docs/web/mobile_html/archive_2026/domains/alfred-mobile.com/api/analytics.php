<?php
/**
 * Alfred AI — Analytics API
 * Provides usage analytics, time-series data, agent performance, and cost analysis
 * 
 * Endpoints (via ?action=):
 *   GET overview         — Overview card data (aggregated counts for period)
 *   GET timeseries       — Time-series data for charts
 *   GET top-tools        — Most-used tools
 *   GET agent-performance — Agent stats table
 *   GET cost-breakdown   — Current period cost analysis
 *   GET activity         — Recent activity feed
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Auth check
if (empty($_SESSION['logged_in']) || empty($_SESSION['client_id'])) {
    jsonResponse(['error' => 'Authentication required'], 401);
require_once dirname(__DIR__) . '/includes/api-security.php';
}

$clientId = (int) $_SESSION['client_id'];
$action   = sanitize($_GET['action'] ?? '');
$db       = getDB();

if (!$db) {
    jsonResponse(['error' => 'Database unavailable'], 500);
}

// Ensure tables exist
ensureAnalyticsTables($db);

switch ($action) {
    case 'overview':
        handleOverview($db, $clientId);
        break;
    case 'timeseries':
        handleTimeseries($db, $clientId);
        break;
    case 'top-tools':
        handleTopTools($db, $clientId);
        break;
    case 'agent-performance':
        handleAgentPerformance($db, $clientId);
        break;
    case 'cost-breakdown':
        handleCostBreakdown($db, $clientId);
        break;
    case 'activity':
        handleActivity($db, $clientId);
        break;
    default:
        jsonResponse(['error' => 'Invalid action', 'valid_actions' => [
            'overview','timeseries','top-tools','agent-performance','cost-breakdown','activity'
        ]], 400);
}

/**
 * GET overview — Returns overview card data with comparison
 */
function handleOverview($db, $clientId) {
    $period = sanitize($_GET['period'] ?? '30d');
    $range  = getPeriodRange($period);
    $prev   = getPreviousPeriodRange($period);

    // Current period stats
    $current = getUsageStats($db, $clientId, $range['start'], $range['end']);
    // Previous period stats for comparison
    $previous = getUsageStats($db, $clientId, $prev['start'], $prev['end']);

    // Calculate percentage changes
    $calcChange = function($cur, $pre) {
        if ($pre == 0) return $cur > 0 ? 100 : 0;
        return round((($cur - $pre) / $pre) * 100, 1);
    };

    jsonResponse([
        'success' => true,
        'period'  => $period,
        'data'    => [
            'total_api_calls' => [
                'value'  => (int)$current['api_calls'],
                'change' => $calcChange($current['api_calls'], $previous['api_calls'])
            ],
            'total_voice_minutes' => [
                'value'  => round((float)$current['voice_minutes'], 1),
                'change' => $calcChange($current['voice_minutes'], $previous['voice_minutes'])
            ],
            'active_agents' => [
                'value'  => (int)$current['active_agents'],
                'change' => $calcChange($current['active_agents'], $previous['active_agents'])
            ],
            'tools_executed' => [
                'value'  => (int)$current['tools_executed'],
                'change' => $calcChange($current['tools_executed'], $previous['tools_executed'])
            ],
            'fleet_uptime' => [
                'value'  => round((float)($current['fleet_uptime'] ?: 99.9), 1),
                'change' => 0
            ],
            'storage_used' => [
                'value'  => round((float)$current['storage_used'], 2),
                'change' => $calcChange($current['storage_used'], $previous['storage_used'])
            ]
        ]
    ]);
}

/**
 * GET timeseries — Time-series data for charts
 */
function handleTimeseries($db, $clientId) {
    $metric      = sanitize($_GET['metric'] ?? 'api_calls');
    $period      = sanitize($_GET['period'] ?? '30d');
    $granularity = sanitize($_GET['granularity'] ?? 'day');
    $range       = getPeriodRange($period);

    $validMetrics = ['api_calls', 'voice_minutes', 'tools', 'costs'];
    if (!in_array($metric, $validMetrics)) {
        jsonResponse(['error' => 'Invalid metric', 'valid' => $validMetrics], 400);
    }

    $validGranularity = ['hour', 'day', 'week'];
    if (!in_array($granularity, $validGranularity)) {
        $granularity = 'day';
    }

    // Build resource type filter
    $resourceFilter = '';
    $params = [$clientId, $range['start'], $range['end']];
    switch ($metric) {
        case 'api_calls':
            $resourceFilter = "AND resource_type IN ('api_call','chat','conversation')";
            $valueExpr = 'COUNT(*)';
            break;
        case 'voice_minutes':
            $resourceFilter = "AND resource_type = 'voice_call'";
            $valueExpr = 'COALESCE(SUM(quantity), 0)';
            break;
        case 'tools':
            $resourceFilter = "AND resource_type = 'tool_call'";
            $valueExpr = 'COUNT(*)';
            break;
        case 'costs':
            $resourceFilter = '';
            $valueExpr = 'COALESCE(SUM(cost), 0)';
            break;
    }

    // Date grouping format
    switch ($granularity) {
        case 'hour':
            $dateFormat = '%Y-%m-%d %H:00';
            break;
        case 'week':
            $dateFormat = '%x-W%v';
            break;
        default:
            $dateFormat = '%Y-%m-%d';
    }

    $sql = "SELECT DATE_FORMAT(created_at, ?) AS label, {$valueExpr} AS value
            FROM alfred_usage
            WHERE user_id = ? AND created_at BETWEEN ? AND ?
            {$resourceFilter}
            GROUP BY label
            ORDER BY label ASC";

    try {
        $stmt = $db->prepare($sql);
        $stmt->execute([$dateFormat, $clientId, $range['start'], $range['end']]);
        $rows = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Analytics timeseries error: " . $e->getMessage());
        $rows = [];
    }

    $labels = array_column($rows, 'label');
    $data   = array_map(function($r) { return round((float)$r['value'], 2); }, $rows);

    // Fill gaps for daily granularity
    if ($granularity === 'day' && empty($rows)) {
        $start = new DateTime($range['start']);
        $end   = new DateTime($range['end']);
        while ($start <= $end) {
            $labels[] = $start->format('Y-m-d');
            $data[]   = 0;
            $start->modify('+1 day');
        }
    }

    jsonResponse([
        'success'     => true,
        'metric'      => $metric,
        'period'      => $period,
        'granularity' => $granularity,
        'labels'      => $labels,
        'data'        => $data
    ]);
}

/**
 * GET top-tools — Most-used tools
 */
function handleTopTools($db, $clientId) {
    $period = sanitize($_GET['period'] ?? '30d');
    $range  = getPeriodRange($period);
    $limit  = min((int)($_GET['limit'] ?? 10), 50);

    try {
        $stmt = $db->prepare("
            SELECT resource_id AS tool_id, 
                   COALESCE(resource_name, resource_id) AS name, 
                   COUNT(*) AS count
            FROM alfred_usage
            WHERE user_id = ? AND resource_type = 'tool_call'
              AND created_at BETWEEN ? AND ?
            GROUP BY resource_id, resource_name
            ORDER BY count DESC
            LIMIT ?
        ");
        $stmt->execute([$clientId, $range['start'], $range['end'], $limit]);
        $tools = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Analytics top-tools error: " . $e->getMessage());
        $tools = [];
    }

    jsonResponse([
        'success' => true,
        'period'  => $period,
        'tools'   => $tools
    ]);
}

/**
 * GET agent-performance — Agent stats table
 */
function handleAgentPerformance($db, $clientId) {
    try {
        // Get agents with usage stats
        $stmt = $db->prepare("
            SELECT 
                a.id AS agent_id,
                a.name,
                a.status,
                COALESCE(conv.conversation_count, 0) AS conversation_count,
                COALESCE(conv.avg_response_ms, 0) AS avg_response_ms,
                COALESCE(err.error_count, 0) AS error_count
            FROM alfred_agents a
            LEFT JOIN (
                SELECT resource_id, 
                       COUNT(*) AS conversation_count,
                       AVG(CASE WHEN JSON_VALID(metadata) THEN JSON_EXTRACT(metadata, '$.response_ms') ELSE NULL END) AS avg_response_ms
                FROM alfred_usage
                WHERE user_id = ? AND resource_type IN ('conversation','chat')
                GROUP BY resource_id
            ) conv ON conv.resource_id = CAST(a.id AS CHAR)
            LEFT JOIN (
                SELECT resource_id, COUNT(*) AS error_count
                FROM alfred_usage
                WHERE user_id = ? AND resource_type = 'error'
                GROUP BY resource_id
            ) err ON err.resource_id = CAST(a.id AS CHAR)
            WHERE a.user_id = ?
            ORDER BY conversation_count DESC
        ");
        $stmt->execute([$clientId, $clientId, $clientId]);
        $agents = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Analytics agent-performance error: " . $e->getMessage());
        $agents = [];
    }

    // Format response
    foreach ($agents as &$agent) {
        $agent['agent_id']           = (int)$agent['agent_id'];
        $agent['conversation_count'] = (int)$agent['conversation_count'];
        $agent['avg_response_ms']    = round((float)$agent['avg_response_ms']);
        $agent['error_count']        = (int)$agent['error_count'];
        $agent['satisfaction']       = $agent['error_count'] > 0 
            ? max(0, round(100 - ($agent['error_count'] / max($agent['conversation_count'], 1) * 100), 1)) 
            : 100;
    }

    jsonResponse([
        'success' => true,
        'agents'  => $agents
    ]);
}

/**
 * GET cost-breakdown — Current period cost analysis
 */
function handleCostBreakdown($db, $clientId) {
    $period = sanitize($_GET['period'] ?? '30d');
    $range  = getPeriodRange($period);

    // Get costs by category
    try {
        $stmt = $db->prepare("
            SELECT resource_type AS category,
                   COUNT(*) AS usage_count,
                   COALESCE(SUM(cost), 0) AS total_cost
            FROM alfred_usage
            WHERE user_id = ? AND created_at BETWEEN ? AND ?
            GROUP BY resource_type
            ORDER BY total_cost DESC
        ");
        $stmt->execute([$clientId, $range['start'], $range['end']]);
        $categories = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Analytics cost-breakdown error: " . $e->getMessage());
        $categories = [];
    }

    $totalCost = array_sum(array_column($categories, 'total_cost'));

    // Get plan limits
    $planLimits = getPlanLimits($db, $clientId);

    // Calculate days in period for projection
    $start = new DateTime($range['start']);
    $end   = new DateTime($range['end']);
    $daysInPeriod  = max($start->diff($end)->days, 1);
    $daysElapsed   = max((new DateTime())->diff($start)->days, 1);
    $projectedCost = ($totalCost / $daysElapsed) * 30;

    jsonResponse([
        'success'          => true,
        'period'           => $period,
        'by_category'      => $categories,
        'total_cost'       => round($totalCost, 2),
        'projected_monthly'=> round($projectedCost, 2),
        'plan_limits'      => $planLimits,
        'days_elapsed'     => $daysElapsed,
        'days_in_period'   => $daysInPeriod
    ]);
}

/**
 * GET activity — Recent activity feed
 */
function handleActivity($db, $clientId) {
    $page    = max((int)($_GET['page'] ?? 1), 1);
    $perPage = min(max((int)($_GET['per_page'] ?? 50), 10), 100);
    $type    = sanitize($_GET['type'] ?? '');
    $offset  = ($page - 1) * $perPage;

    $where = "WHERE user_id = ?";
    $params = [$clientId];

    if ($type) {
        $where .= " AND resource_type = ?";
        $params[] = $type;
    }

    try {
        // Count total
        $countStmt = $db->prepare("SELECT COUNT(*) FROM alfred_usage {$where}");
        dbExecute($countStmt, $params);
        $total = (int) $countStmt->fetchColumn();

        // Fetch activities
        $params[] = $perPage;
        $params[] = $offset;
        $stmt = $db->prepare("
            SELECT id, resource_type AS type, resource_id, resource_name AS name,
                   quantity, cost, created_at,
                   CASE WHEN JSON_VALID(metadata) THEN metadata ELSE NULL END AS metadata
            FROM alfred_usage
            {$where}
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ");
        dbExecute($stmt, $params);
        $activities = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Analytics activity error: " . $e->getMessage());
        $activities = [];
        $total = 0;
    }

    // Format activities
    foreach ($activities as &$act) {
        $act['id']   = (int)$act['id'];
        $act['cost'] = round((float)($act['cost'] ?? 0), 4);
        if ($act['metadata']) {
            $act['metadata'] = json_decode($act['metadata'], true);
        }
    }

    jsonResponse([
        'success'    => true,
        'activities' => $activities,
        'pagination' => [
            'page'     => $page,
            'per_page' => $perPage,
            'total'    => $total,
            'pages'    => ceil($total / $perPage)
        ]
    ]);
}

// ────────────────────────────────────────────────────
// Helper functions
// ────────────────────────────────────────────────────

function getUsageStats($db, $clientId, $start, $end) {
    $defaults = [
        'api_calls'      => 0,
        'voice_minutes'  => 0,
        'active_agents'  => 0,
        'tools_executed' => 0,
        'fleet_uptime'   => 99.9,
        'storage_used'   => 0
    ];

    try {
        $stmt = $db->prepare("
            SELECT
                SUM(CASE WHEN resource_type IN ('api_call','chat','conversation') THEN 1 ELSE 0 END) AS api_calls,
                SUM(CASE WHEN resource_type = 'voice_call' THEN COALESCE(quantity, 0) ELSE 0 END) AS voice_minutes,
                SUM(CASE WHEN resource_type = 'tool_call' THEN 1 ELSE 0 END) AS tools_executed,
                SUM(CASE WHEN resource_type = 'storage' THEN COALESCE(quantity, 0) ELSE 0 END) AS storage_used
            FROM alfred_usage
            WHERE user_id = ? AND created_at BETWEEN ? AND ?
        ");
        $stmt->execute([$clientId, $start, $end]);
        $row = $stmt->fetch();

        if ($row) {
            $defaults['api_calls']     = (int)($row['api_calls'] ?? 0);
            $defaults['voice_minutes'] = (float)($row['voice_minutes'] ?? 0);
            $defaults['tools_executed']= (int)($row['tools_executed'] ?? 0);
            $defaults['storage_used']  = (float)($row['storage_used'] ?? 0);
        }

        // Active agents count
        $stmt2 = $db->prepare("
            SELECT COUNT(DISTINCT id) FROM alfred_agents
            WHERE user_id = ? AND status = 'active'
        ");
        $stmt2->execute([$clientId]);
        $defaults['active_agents'] = (int) $stmt2->fetchColumn();

    } catch (PDOException $e) {
        error_log("getUsageStats error: " . $e->getMessage());
    }

    return $defaults;
}

function getPeriodRange($period) {
    $end = date('Y-m-d H:i:s');
    switch ($period) {
        case 'today':
            $start = date('Y-m-d 00:00:00');
            break;
        case '7d':
            $start = date('Y-m-d H:i:s', strtotime('-7 days'));
            break;
        case '90d':
            $start = date('Y-m-d H:i:s', strtotime('-90 days'));
            break;
        case '30d':
        default:
            $start = date('Y-m-d H:i:s', strtotime('-30 days'));
            break;
    }
    return ['start' => $start, 'end' => $end];
}

function getPreviousPeriodRange($period) {
    switch ($period) {
        case 'today':
            $days = 1;
            break;
        case '7d':
            $days = 7;
            break;
        case '90d':
            $days = 90;
            break;
        case '30d':
        default:
            $days = 30;
            break;
    }
    $end   = date('Y-m-d H:i:s', strtotime("-{$days} days"));
    $start = date('Y-m-d H:i:s', strtotime("-" . ($days * 2) . " days"));
    return ['start' => $start, 'end' => $end];
}

function getPlanLimits($db, $clientId) {
    $limits = [
        'api_calls'      => ['limit' => 10000, 'used' => 0, 'label' => 'API Calls'],
        'voice_minutes'  => ['limit' => 500,   'used' => 0, 'label' => 'Voice Minutes'],
        'agents'         => ['limit' => 10,    'used' => 0, 'label' => 'Agents'],
        'tools'          => ['limit' => 1220,   'used' => 0, 'label' => 'Tools'],
        'storage_mb'     => ['limit' => 5000,  'used' => 0, 'label' => 'Storage (MB)']
    ];

    try {
        // Get limits from subscription if available
        $stmt = $db->prepare("
            SELECT plan_type, api_limit, voice_limit, agent_limit, storage_limit
            FROM alfred_usage_limits
            WHERE user_id = ?
            LIMIT 1
        ");
        $stmt->execute([$clientId]);
        $sub = $stmt->fetch();

        if ($sub) {
            if (!empty($sub['api_limit']))     $limits['api_calls']['limit']     = (int)$sub['api_limit'];
            if (!empty($sub['voice_limit']))   $limits['voice_minutes']['limit'] = (int)$sub['voice_limit'];
            if (!empty($sub['agent_limit']))   $limits['agents']['limit']        = (int)$sub['agent_limit'];
            if (!empty($sub['storage_limit'])) $limits['storage_mb']['limit']    = (int)$sub['storage_limit'];
        }

        // Get current month usage
        $monthStart = date('Y-m-01 00:00:00');
        $stats = getUsageStats($db, $clientId, $monthStart, date('Y-m-d H:i:s'));
        $limits['api_calls']['used']     = $stats['api_calls'];
        $limits['voice_minutes']['used'] = $stats['voice_minutes'];
        $limits['agents']['used']        = $stats['active_agents'];
        $limits['storage_mb']['used']    = $stats['storage_used'];

    } catch (PDOException $e) {
        error_log("getPlanLimits error: " . $e->getMessage());
    }

    return $limits;
}

function ensureAnalyticsTables($db) {
    try {
        // Check if alfred_usage has needed columns
        $stmt = $db->query("SHOW TABLES LIKE 'alfred_usage'");
        if (!$stmt->fetch()) {
            return; // Table doesn't exist yet, main schema handles creation
        }

        // Check for resource_name column
        $cols = $db->query("SHOW COLUMNS FROM alfred_usage LIKE 'resource_name'")->fetch();
        if (!$cols) {
            $db->exec("ALTER TABLE alfred_usage ADD COLUMN resource_name VARCHAR(200) DEFAULT NULL AFTER resource_id");
        }

        // Check for cost column
        $cols = $db->query("SHOW COLUMNS FROM alfred_usage LIKE 'cost'")->fetch();
        if (!$cols) {
            $db->exec("ALTER TABLE alfred_usage ADD COLUMN cost DECIMAL(10,4) DEFAULT 0 AFTER quantity");
        }

        // Check for metadata column
        $cols = $db->query("SHOW COLUMNS FROM alfred_usage LIKE 'metadata'")->fetch();
        if (!$cols) {
            $db->exec("ALTER TABLE alfred_usage ADD COLUMN metadata JSON DEFAULT NULL");
        }
    } catch (PDOException $e) {
        error_log("ensureAnalyticsTables error: " . $e->getMessage());
    }
}
