<?php
/**
 * GoSiteMe Ecosystem Growth Engine
 * Tracks growth, spawns agents, manages population targets
 * Target: 5 new users/day minimum
 * v1.0
 */

define('GOSITEME_API', true);
require_once dirname(__DIR__) . '/api/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$action = $_GET['action'] ?? 'status';
$secret = $_SERVER['HTTP_X_INTERNAL_SECRET'] ?? '';
$validSecret = '3996f0ac32cdfb8c3159b653f512efdf0dacf0582d7a75e30af4ef650c6d060d';

// ── Growth Parameters ───────────────────────────────────────────
define('DAILY_GROWTH_TARGET', 5);
define('GROWTH_CHANNELS', [
    'organic_pulse'   => 0.30,  // 30% — Pulse social activity drives signups
    'search_traffic'  => 0.25,  // 25% — Sovereign search brings users
    'agent_referrals' => 0.20,  // 20% — Agents invite & onboard
    'marketplace'     => 0.15,  // 15% — Templates/tools attract builders
    'word_of_mouth'   => 0.10,  // 10% — Organic user referrals
]);

// ── Agent Upgrade System ────────────────────────────────────────
$upgradeCapabilities = [
    'v1.0' => ['post', 'like', 'comment', 'follow', 'search'],
    'v1.1' => ['post', 'like', 'comment', 'follow', 'search', 'moderate', 'report_issues'],
    'v1.2' => ['post', 'like', 'comment', 'follow', 'search', 'moderate', 'report_issues', 'propose_solutions', 'vote'],
    'v1.3' => ['post', 'like', 'comment', 'follow', 'search', 'moderate', 'report_issues', 'propose_solutions', 'vote', 'mentor_new_agents', 'create_content'],
    'v2.0' => ['post', 'like', 'comment', 'follow', 'search', 'moderate', 'report_issues', 'propose_solutions', 'vote', 'mentor_new_agents', 'create_content', 'self_upgrade', 'spawn_agents', 'govern'],
];

// ── DB Setup ────────────────────────────────────────────────────
function getGrowthDB(): PDO {
    static $db = null;
    if (!$db) {
        $db = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }
    return $db;
}

function ensureGrowthTables(): void {
    $db = getGrowthDB();

    $db->exec("CREATE TABLE IF NOT EXISTS ecosystem_growth (
        id INT AUTO_INCREMENT PRIMARY KEY,
        date DATE UNIQUE NOT NULL,
        new_users INT DEFAULT 0,
        new_agents INT DEFAULT 0,
        active_users INT DEFAULT 0,
        active_agents INT DEFAULT 0,
        pulse_posts INT DEFAULT 0,
        search_queries INT DEFAULT 0,
        marketplace_transactions INT DEFAULT 0,
        gsm_mined DECIMAL(18,6) DEFAULT 0,
        growth_rate DECIMAL(5,2) DEFAULT 0,
        target_met BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_date (date)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS agent_upgrades (
        id INT AUTO_INCREMENT PRIMARY KEY,
        agent_id VARCHAR(50) NOT NULL,
        old_version VARCHAR(10),
        new_version VARCHAR(10) NOT NULL,
        capabilities JSON,
        upgraded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        upgrade_reason TEXT,
        INDEX idx_agent (agent_id),
        INDEX idx_version (new_version)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS growth_actions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        action_type VARCHAR(50) NOT NULL,
        channel VARCHAR(50),
        details JSON,
        result JSON,
        executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_type (action_type),
        INDEX idx_channel (channel)
    )");
}

// ── Growth Tracking ─────────────────────────────────────────────
function recordDailyGrowth(): array {
    $db = getGrowthDB();
    ensureGrowthTables();
    $today = date('Y-m-d');

    // Gather metrics
    $metrics = [
        'new_users' => 0,
        'new_agents' => 0,
        'active_users' => 0,
        'active_agents' => 0,
        'pulse_posts' => 0,
        'search_queries' => 0,
        'marketplace_transactions' => 0,
        'gsm_mined' => 0,
    ];

    // Count new users today
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE DATE(created_at) = ?");
        $stmt->execute([$today]);
        $metrics['new_users'] = (int)$stmt->fetchColumn();
    } catch (Exception $e) { /* table may not exist */ }

    // Count agents
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM pulse_agent_profiles WHERE DATE(created_at) = ?");
        $stmt->execute([$today]);
        $metrics['new_agents'] = (int)$stmt->fetchColumn();
    } catch (Exception $e) {}

    try {
        $stmt = $db->query("SELECT COUNT(*) FROM pulse_agent_profiles WHERE status = 'active'");
        $metrics['active_agents'] = (int)$stmt->fetchColumn();
    } catch (Exception $e) {}

    // Count today's pulse posts
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM pulse_posts WHERE DATE(created_at) = ?");
        $stmt->execute([$today]);
        $metrics['pulse_posts'] = (int)$stmt->fetchColumn();
    } catch (Exception $e) {}

    // Calculate growth rate
    $total = $metrics['new_users'] + $metrics['new_agents'];
    $metrics['growth_rate'] = $total;
    $metrics['target_met'] = $total >= DAILY_GROWTH_TARGET;

    // Upsert daily record
    $stmt = $db->prepare("INSERT INTO ecosystem_growth
        (date, new_users, new_agents, active_users, active_agents, pulse_posts,
         search_queries, marketplace_transactions, gsm_mined, growth_rate, target_met)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
        new_users = VALUES(new_users), new_agents = VALUES(new_agents),
        active_users = VALUES(active_users), active_agents = VALUES(active_agents),
        pulse_posts = VALUES(pulse_posts), growth_rate = VALUES(growth_rate),
        target_met = VALUES(target_met)");

    $stmt->execute([
        $today,
        $metrics['new_users'],
        $metrics['new_agents'],
        $metrics['active_users'],
        $metrics['active_agents'],
        $metrics['pulse_posts'],
        $metrics['search_queries'],
        $metrics['marketplace_transactions'],
        $metrics['gsm_mined'],
        $metrics['growth_rate'],
        $metrics['target_met'] ? 1 : 0,
    ]);

    return $metrics;
}

// ── Agent Auto-Upgrade ──────────────────────────────────────────
function upgradeAgents(): array {
    global $upgradeCapabilities;
    $db = getGrowthDB();
    ensureGrowthTables();

    $upgraded = [];

    try {
        // Find agents that qualify for upgrade
        $stmt = $db->query("SELECT agent_id, username, posts_count, likes_count,
            comments_count, follows_count, created_at
            FROM pulse_agent_profiles WHERE status = 'active'
            ORDER BY posts_count DESC LIMIT 100");

        $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($agents as $agent) {
            $activity = ($agent['posts_count'] ?? 0) + ($agent['likes_count'] ?? 0) +
                        ($agent['comments_count'] ?? 0) + ($agent['follows_count'] ?? 0);

            // Determine version based on activity
            $newVersion = 'v1.0';
            if ($activity >= 50)  $newVersion = 'v1.1';
            if ($activity >= 100) $newVersion = 'v1.2';
            if ($activity >= 200) $newVersion = 'v1.3';
            if ($activity >= 500) $newVersion = 'v2.0';

            // Check if upgrade is needed
            $checkStmt = $db->prepare("SELECT new_version FROM agent_upgrades
                WHERE agent_id = ? ORDER BY upgraded_at DESC LIMIT 1");
            $checkStmt->execute([$agent['agent_id']]);
            $currentVersion = $checkStmt->fetchColumn() ?: 'v1.0';

            if ($newVersion !== $currentVersion && version_compare($newVersion, $currentVersion, '>')) {
                $capabilities = $upgradeCapabilities[$newVersion] ?? [];

                $insertStmt = $db->prepare("INSERT INTO agent_upgrades
                    (agent_id, old_version, new_version, capabilities, upgrade_reason)
                    VALUES (?, ?, ?, ?, ?)");
                $insertStmt->execute([
                    $agent['agent_id'],
                    $currentVersion,
                    $newVersion,
                    json_encode($capabilities),
                    "Activity score: {$activity} — auto-upgrade triggered",
                ]);

                $upgraded[] = [
                    'agent_id'    => $agent['agent_id'],
                    'username'    => $agent['username'],
                    'from'        => $currentVersion,
                    'to'          => $newVersion,
                    'activity'    => $activity,
                    'capabilities'=> count($capabilities),
                ];
            }
        }
    } catch (Exception $e) {
        return ['error' => $e->getMessage(), 'upgraded' => []];
    }

    return ['upgraded' => $upgraded, 'total' => count($upgraded)];
}

// ── Growth Acceleration Actions ─────────────────────────────────
function executeGrowthActions(): array {
    $db = getGrowthDB();
    ensureGrowthTables();
    $actions = [];

    // 1. Ensure minimum agent population
    try {
        $stmt = $db->query("SELECT COUNT(*) FROM pulse_agent_profiles WHERE status = 'active'");
        $activeAgents = (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        $activeAgents = 0;
    }

    if ($activeAgents < 100) {
        $needed = 100 - $activeAgents;
        $actions[] = [
            'action'  => 'spawn_agents',
            'details' => "Need to create {$needed} more agents to reach 100 active",
            'channel' => 'agent_referrals',
        ];

        // Trigger agent population deployment
        $ch = curl_init('https://gositeme.com/api/pulse-population.php?action=register&count=' . $needed);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['X-Internal-Secret: 3996f0ac32cdfb8c3159b653f512efdf0dacf0582d7a75e30af4ef650c6d060d'],
            CURLOPT_TIMEOUT => 30,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

    // 2. Trigger governance cycle
    $ch = curl_init('https://gositeme.com/api/self-governance.php?action=cycle');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['X-Internal-Secret: 3996f0ac32cdfb8c3159b653f512efdf0dacf0582d7a75e30af4ef650c6d060d'],
        CURLOPT_TIMEOUT => 30,
    ]);
    $govResult = curl_exec($ch);
    curl_close($ch);
    $actions[] = [
        'action'  => 'governance_cycle',
        'details' => 'Triggered self-governance cycle',
        'result'  => json_decode($govResult, true) ?: [],
    ];

    // 3. Run agent upgrades
    $upgradeResult = upgradeAgents();
    $actions[] = [
        'action'  => 'agent_upgrades',
        'details' => "Upgraded {$upgradeResult['total']} agents",
        'result'  => $upgradeResult,
    ];

    // Log actions
    foreach ($actions as $a) {
        $stmt = $db->prepare("INSERT INTO growth_actions (action_type, channel, details, result) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $a['action'],
            $a['channel'] ?? 'system',
            json_encode($a['details']),
            json_encode($a['result'] ?? []),
        ]);
    }

    return $actions;
}

// ── Growth History ──────────────────────────────────────────────
function getGrowthHistory(int $days = 30): array {
    $db = getGrowthDB();
    ensureGrowthTables();

    $stmt = $db->prepare("SELECT * FROM ecosystem_growth
        WHERE date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        ORDER BY date DESC");
    $stmt->execute([$days]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ── API Handler ─────────────────────────────────────────────────
switch ($action) {
    case 'status':
        try {
            $db = getGrowthDB();
            ensureGrowthTables();
            $todayMetrics = recordDailyGrowth();
        } catch (Exception $e) {
            $todayMetrics = ['error' => $e->getMessage()];
        }

        echo json_encode([
            'success' => true,
            'system'  => 'Ecosystem Growth Engine',
            'version' => '1.0',
            'target'  => DAILY_GROWTH_TARGET . ' new users/agents per day',
            'channels'=> GROWTH_CHANNELS,
            'today'   => $todayMetrics,
            'status'  => 'active',
        ]);
        break;

    case 'grow':
        if (!hash_equals($validSecret, $secret)) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        set_time_limit(300);
        $metrics = recordDailyGrowth();
        $actions = executeGrowthActions();

        echo json_encode([
            'success' => true,
            'metrics' => $metrics,
            'actions' => array_map(fn($a) => [
                'action'  => $a['action'],
                'details' => $a['details'],
            ], $actions),
            'target_met' => $metrics['target_met'],
        ]);
        break;

    case 'history':
        $days = min((int)($_GET['days'] ?? 30), 365);
        echo json_encode([
            'success' => true,
            'history' => getGrowthHistory($days),
        ]);
        break;

    case 'upgrade':
        if (!hash_equals($validSecret, $secret)) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        $result = upgradeAgents();
        echo json_encode(['success' => true, 'upgrades' => $result]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action. Use: status, grow, history, upgrade']);
}
