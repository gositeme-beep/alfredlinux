<?php
/**
 * Alfred API v1 — Usage & Billing Resource Handler
 *
 * Endpoints:
 *   GET /usage              — Get usage stats for authenticated user
 *   GET /usage/tools        — Tool-specific usage breakdown
 *   GET /usage/daily        — Daily usage over time
 *   GET /usage/billing      — Get billing / plan info
 *
 * Note: /billing at the top-level routes here too.
 *
 * @version 1.0.0
 * @since   2026-03-04
 */

declare(strict_types=1);

require_once __DIR__ . '/../helpers.php';

/**
 * Handle usage requests
 */
function handleUsageRequest(array $ctx): void
{
    $method = $ctx['method'];
    $route  = $ctx['route'];
    $id     = $route['id'] ?? null;

    if ($method !== 'GET') {
        respondError('Only GET is allowed on /usage', 405, 'method_not_allowed');
    }

    if ($id === null) {
        getUsageOverview($ctx);
    } elseif ($id === 'tools') {
        getToolUsage($ctx);
    } elseif ($id === 'daily') {
        getDailyUsage($ctx);
    } elseif ($id === 'billing') {
        getBillingInfo($ctx);
    } else {
        respondError("Unknown usage sub-resource '{$id}'", 404, 'resource_not_found');
    }
}

/**
 * GET /usage — Overview of usage stats
 */
function getUsageOverview(array $ctx): void
{
    requireScopes($ctx['auth'], 'usage:read');

    $db = getDB();
    if (!$db) {
        respondError('Database unavailable', 503, 'service_unavailable');
    }

    $userId = $ctx['auth']['user_id'];

    try {
        // Tool usage totals
        $stmt = $db->prepare("SELECT COUNT(*) as total, SUM(success) as successes FROM alfred_tool_usage WHERE user_id = ?");
        $stmt->execute([$userId]);
        $toolStats = $stmt->fetch();

        $totalExecutions = (int) ($toolStats['total'] ?? 0);
        $successCount    = (int) ($toolStats['successes'] ?? 0);

        // Unique tools used
        $stmt = $db->prepare("SELECT COUNT(DISTINCT tool_name) FROM alfred_tool_usage WHERE user_id = ?");
        $stmt->execute([$userId]);
        $uniqueTools = (int) $stmt->fetchColumn();

        // Fleet counts
        $stmt = $db->prepare("SELECT COUNT(*) FROM alfred_fleets WHERE user_id = ?");
        $stmt->execute([$userId]);
        $fleetCount = (int) $stmt->fetchColumn();

        // Agent counts
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM alfred_fleet_agents a 
            JOIN alfred_fleets f ON a.fleet_id = f.id 
            WHERE f.user_id = ?
        ");
        $stmt->execute([$userId]);
        $agentCount = (int) $stmt->fetchColumn();

        // Conference/call counts
        $stmt = $db->prepare("SELECT COUNT(*) FROM alfred_conferences WHERE host_user_id = ?");
        $stmt->execute([$userId]);
        $conferenceCount = (int) $stmt->fetchColumn();

        // API request count (from alfred_usage, last 30 days)
        $apiRequests = 0;
        try {
            $stmt = $db->prepare("SELECT SUM(quantity) FROM alfred_usage WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
            $stmt->execute([$userId]);
            $apiRequests = (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            // alfred_usage table might not exist yet
        }

        // XP
        $totalXp = 0;
        try {
            $stmt = $db->prepare("SELECT total_xp, level, streak_days FROM alfred_user_xp_summary WHERE client_id = ?");
            $stmt->execute([$userId]);
            $xpRow = $stmt->fetch();
        } catch (\PDOException $e) {
            $xpRow = null;
        }

        logUsage($userId, 'usage', 1, 'GET /usage');

        respond([
            'data' => [
                'tool_executions'     => $totalExecutions,
                'successful_executions' => $successCount,
                'success_rate'        => $totalExecutions > 0 ? round(($successCount / $totalExecutions) * 100, 1) : 0,
                'unique_tools_used'   => $uniqueTools,
                'fleets'              => $fleetCount,
                'agents'              => $agentCount,
                'conferences'         => $conferenceCount,
                'api_requests_30d'    => $apiRequests,
                'xp'                  => $xpRow ? [
                    'total'       => (int) $xpRow['total_xp'],
                    'level'       => (int) $xpRow['level'],
                    'streak_days' => (int) $xpRow['streak_days'],
                ] : null,
                'tier'                => $ctx['auth']['tier'],
            ],
        ]);
    } catch (\PDOException $e) {
        error_log('API v1 usage: overview failed: ' . $e->getMessage());
        respondError('Failed to get usage overview', 500, 'internal_error');
    }
}

/**
 * GET /usage/tools — Tool usage breakdown
 */
function getToolUsage(array $ctx): void
{
    requireScopes($ctx['auth'], 'usage:read');

    $db = getDB();
    if (!$db) {
        respondError('Database unavailable', 503, 'service_unavailable');
    }

    $userId = $ctx['auth']['user_id'];
    $pg     = getPagination();

    try {
        // Total distinct tools
        $stmt = $db->prepare("SELECT COUNT(DISTINCT tool_name) FROM alfred_tool_usage WHERE user_id = ?");
        $stmt->execute([$userId]);
        $total = (int) $stmt->fetchColumn();

        // Top tools
        $stmt = $db->prepare("
            SELECT tool_name, category, 
                   COUNT(*) as usage_count, 
                   SUM(success) as success_count,
                   AVG(execution_time_ms) as avg_time_ms,
                   MAX(used_at) as last_used
            FROM alfred_tool_usage 
            WHERE user_id = :uid 
            GROUP BY tool_name, category 
            ORDER BY usage_count DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':uid', $userId);
        $stmt->bindValue(':limit', $pg['per_page'], \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $pg['offset'], \PDO::PARAM_INT);
        $stmt->execute();
        $tools = $stmt->fetchAll();

        // Cast
        foreach ($tools as &$t) {
            $t['usage_count']   = (int) $t['usage_count'];
            $t['success_count'] = (int) $t['success_count'];
            $t['avg_time_ms']   = (int) round((float) $t['avg_time_ms']);
        }

        logUsage($userId, 'usage', 1, 'GET /usage/tools');

        respond(paginatedResponse($tools, $total, $pg['page'], $pg['per_page']));
    } catch (\PDOException $e) {
        error_log('API v1 usage: tool usage failed: ' . $e->getMessage());
        respondError('Failed to get tool usage', 500, 'internal_error');
    }
}

/**
 * GET /usage/daily — Daily usage over the last 30 days
 */
function getDailyUsage(array $ctx): void
{
    requireScopes($ctx['auth'], 'usage:read');

    $db = getDB();
    if (!$db) {
        respondError('Database unavailable', 503, 'service_unavailable');
    }

    $userId = $ctx['auth']['user_id'];
    $days   = min(90, max(7, (int) ($_GET['days'] ?? 30)));

    try {
        $stmt = $db->prepare("
            SELECT DATE(used_at) as date, COUNT(*) as count, SUM(success) as successes
            FROM alfred_tool_usage 
            WHERE user_id = :uid AND used_at >= DATE_SUB(NOW(), INTERVAL :days DAY) 
            GROUP BY DATE(used_at) 
            ORDER BY date ASC
        ");
        $stmt->bindValue(':uid', $userId);
        $stmt->bindValue(':days', $days, \PDO::PARAM_INT);
        $stmt->execute();
        $daily = $stmt->fetchAll();

        foreach ($daily as &$d) {
            $d['count']     = (int) $d['count'];
            $d['successes'] = (int) $d['successes'];
        }

        logUsage($userId, 'usage', 1, 'GET /usage/daily');

        respond([
            'data' => [
                'period_days' => $days,
                'daily'       => $daily,
                'total'       => array_sum(array_column($daily, 'count')),
            ],
        ]);
    } catch (\PDOException $e) {
        error_log('API v1 usage: daily failed: ' . $e->getMessage());
        respondError('Failed to get daily usage', 500, 'internal_error');
    }
}

/**
 * GET /usage/billing — Billing / plan info
 */
function getBillingInfo(array $ctx): void
{
    requireScopes($ctx['auth'], 'billing:read');

    $db = getDB();
    if (!$db) {
        respondError('Database unavailable', 503, 'service_unavailable');
    }

    $userId = $ctx['auth']['user_id'];

    try {
        // Get plan from user prefs
        $stmt = $db->prepare("SELECT notification_settings FROM alfred_user_preferences WHERE user_id = ?");
        $stmt->execute([$userId]);
        $prefs = $stmt->fetch();

        $plan       = 'free';
        $fleetLimit = 1;
        if ($prefs && !empty($prefs['notification_settings'])) {
            $settings   = json_decode($prefs['notification_settings'], true) ?: [];
            $plan       = $settings['plan'] ?? 'free';
            $fleetLimit = $settings['fleet_limit'] ?? 1;
        }

        // Rate limit info
        $tiers = RATE_TIERS;
        $rateLimits = $tiers[$ctx['auth']['tier']] ?? $tiers['free'];

        // Get current fleet usage
        $stmt = $db->prepare("SELECT COUNT(*) FROM alfred_fleets WHERE user_id = ? AND status != 'failed'");
        $stmt->execute([$userId]);
        $currentFleets = (int) $stmt->fetchColumn();

        logUsage($userId, 'usage', 1, 'GET /usage/billing');

        respond([
            'data' => [
                'plan'          => $plan,
                'api_tier'      => $ctx['auth']['tier'],
                'rate_limits'   => $rateLimits,
                'fleet_limit'   => $fleetLimit,
                'fleets_used'   => $currentFleets,
                'fleets_remaining' => $fleetLimit >= 0 ? max(0, $fleetLimit - $currentFleets) : -1,
            ],
        ]);
    } catch (\PDOException $e) {
        error_log('API v1 usage: billing failed: ' . $e->getMessage());
        respondError('Failed to get billing info', 500, 'internal_error');
    }
}
