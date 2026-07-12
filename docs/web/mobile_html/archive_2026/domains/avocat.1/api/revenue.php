<?php
/**
 * Revenue & Treasury API — Platform 20% Share Visibility
 * ───────────────────────────────────────────────────────
 * Provides Danny (Admin) and Alfred (Commander) full visibility
 * into the platform's 20% mining revenue share, allocation tracking,
 * and fund distribution across ecosystem components.
 *
 * Endpoints:
 *   GET  ?action=dashboard       → Full revenue dashboard (admin only)
 *   GET  ?action=platform_share  → Platform 20% earnings breakdown
 *   GET  ?action=allocations     → How funds are allocated
 *   GET  ?action=daily_report    → Daily revenue summary
 *   POST ?action=allocate        → Allocate funds to a program (admin only)
 *   GET  ?action=treasury        → Full treasury state
 */

define('GOSITEME_API', true);
require_once dirname(__DIR__) . '/api/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Admin IDs who can see revenue
define('REVENUE_ADMIN_IDS', [33]); // Danny
define('PLATFORM_SHARE', 0.20);

// ── Auth ────────────────────────────────────────────────────────
function getAuthUser(): ?array {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!empty($_SESSION['logged_in']) && !empty($_SESSION['user_id'])) {
        return ['id' => (int)$_SESSION['user_id'], 'name' => $_SESSION['client_name'] ?? ''];
    }
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/^Bearer\s+(.+)$/', $authHeader, $m)) {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, name FROM clients WHERE api_key = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$m[1]]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) return ['id' => (int)$user['id'], 'name' => $user['name']];
    }
    return null;
}

function requireAdmin(): array {
    $user = getAuthUser();
    if (!$user || !in_array($user['id'], REVENUE_ADMIN_IDS)) {
        http_response_code(403);
        echo json_encode(['error' => 'Admin access required']);
        exit;
    }
    return $user;
}

// ── Database Setup ──────────────────────────────────────────────
function ensureRevenueTables(): void {
    $db = getDB();

    $db->exec("CREATE TABLE IF NOT EXISTS platform_revenue (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        source ENUM('mining','search','marketplace','subscriptions','trading','chess','vr','api_fees') NOT NULL,
        gross_amount DECIMAL(20,8) NOT NULL DEFAULT 0,
        platform_share DECIMAL(20,8) NOT NULL DEFAULT 0,
        user_share DECIMAL(20,8) NOT NULL DEFAULT 0,
        currency VARCHAR(10) DEFAULT 'GSM',
        period_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_source (source),
        INDEX idx_date (period_date),
        INDEX idx_source_date (source, period_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS platform_allocations (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        program VARCHAR(100) NOT NULL,
        amount DECIMAL(20,8) NOT NULL,
        currency VARCHAR(10) DEFAULT 'GSM',
        status ENUM('planned','allocated','spent','returned') DEFAULT 'planned',
        authorized_by INT NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        executed_at TIMESTAMP NULL,
        INDEX idx_program (program),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS platform_treasury (
        id INT PRIMARY KEY DEFAULT 1,
        total_gsm_earned DECIMAL(20,8) DEFAULT 0,
        total_platform_share DECIMAL(20,8) DEFAULT 0,
        total_allocated DECIMAL(20,8) DEFAULT 0,
        total_spent DECIMAL(20,8) DEFAULT 0,
        available_balance DECIMAL(20,8) DEFAULT 0,
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("INSERT IGNORE INTO platform_treasury (id) VALUES (1)");
}

// ── Revenue Aggregation ─────────────────────────────────────────
function aggregateMiningRevenue(PDO $db): array {
    // Get total mining rewards distributed
    $stmt = $db->query("SELECT
        COUNT(*) as total_rewards,
        COALESCE(SUM(amount), 0) as total_gsm_distributed,
        COALESCE(SUM(amount) * " . PLATFORM_SHARE . " / (1 - " . PLATFORM_SHARE . "), 0) as platform_share_earned,
        COUNT(DISTINCT user_id) as unique_miners
    FROM search_mining_rewards WHERE type IN ('mining', 'hash_block')");
    $mining = $stmt->fetch(PDO::FETCH_ASSOC);

    // Search rewards
    $stmt = $db->query("SELECT
        COUNT(*) as total_searches,
        COALESCE(SUM(amount), 0) as total_gsm_search,
        COALESCE(SUM(amount) * " . PLATFORM_SHARE . " / (1 - " . PLATFORM_SHARE . "), 0) as platform_search_share
    FROM search_mining_rewards WHERE type = 'search'");
    $search = $stmt->fetch(PDO::FETCH_ASSOC);

    // Today's mining
    $stmt = $db->query("SELECT
        COALESCE(SUM(amount), 0) as today_user_gsm,
        COUNT(*) as today_rewards
    FROM search_mining_rewards WHERE DATE(created_at) = CURDATE()");
    $today = $stmt->fetch(PDO::FETCH_ASSOC);

    // 7-day trend
    $stmt = $db->query("SELECT
        DATE(created_at) as day,
        SUM(amount) as user_gsm,
        COUNT(*) as reward_count,
        COUNT(DISTINCT user_id) as miners
    FROM search_mining_rewards
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY day ASC");
    $trend = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 30-day trend
    $stmt = $db->query("SELECT
        DATE(created_at) as day,
        SUM(amount) as user_gsm,
        COUNT(*) as reward_count
    FROM search_mining_rewards
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY day ASC");
    $trend30 = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Top miners
    $stmt = $db->query("SELECT user_id, SUM(amount) as total_earned, COUNT(*) as rewards
        FROM search_mining_rewards
        GROUP BY user_id ORDER BY total_earned DESC LIMIT 10");
    $topMiners = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalUserGSM = (float)($mining['total_gsm_distributed'] ?? 0) + (float)($search['total_gsm_search'] ?? 0);
    $totalPlatformGSM = $totalUserGSM * PLATFORM_SHARE / (1 - PLATFORM_SHARE);
    $totalGross = $totalUserGSM + $totalPlatformGSM;

    return [
        'gross_gsm_generated' => round($totalGross, 8),
        'user_share_gsm' => round($totalUserGSM, 8),
        'platform_share_gsm' => round($totalPlatformGSM, 8),
        'platform_share_pct' => PLATFORM_SHARE * 100 . '%',
        'mining' => [
            'total_rewards' => (int)$mining['total_rewards'],
            'user_gsm' => round((float)$mining['total_gsm_distributed'], 8),
            'platform_gsm' => round((float)$mining['platform_share_earned'], 8),
            'unique_miners' => (int)$mining['unique_miners'],
        ],
        'search' => [
            'total_searches' => (int)$search['total_searches'],
            'user_gsm' => round((float)$search['total_gsm_search'], 8),
            'platform_gsm' => round((float)$search['platform_search_share'], 8),
        ],
        'today' => [
            'user_gsm' => round((float)$today['today_user_gsm'], 8),
            'platform_gsm' => round((float)$today['today_user_gsm'] * PLATFORM_SHARE / (1 - PLATFORM_SHARE), 8),
            'rewards' => (int)$today['today_rewards'],
        ],
        'trend_7d' => array_map(function($d) {
            $userGSM = (float)$d['user_gsm'];
            return [
                'date' => $d['day'],
                'user_gsm' => round($userGSM, 8),
                'platform_gsm' => round($userGSM * PLATFORM_SHARE / (1 - PLATFORM_SHARE), 8),
                'rewards' => (int)$d['reward_count'],
                'miners' => (int)$d['miners'],
            ];
        }, $trend),
        'trend_30d' => array_map(function($d) {
            $userGSM = (float)$d['user_gsm'];
            return [
                'date' => $d['day'],
                'total_gsm' => round($userGSM / (1 - PLATFORM_SHARE), 8),
                'rewards' => (int)$d['reward_count'],
            ];
        }, $trend30),
        'top_miners' => $topMiners,
    ];
}

// ── Handlers ────────────────────────────────────────────────────
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'dashboard':
        $user = requireAdmin();
        $db = getDB();
        ensureRevenueTables();

        $revenue = aggregateMiningRevenue($db);

        // Treasury state
        $treasury = $db->query("SELECT * FROM platform_treasury LIMIT 1")->fetch(PDO::FETCH_ASSOC);

        // Update treasury with calculated values
        $db->prepare("UPDATE platform_treasury SET
            total_gsm_earned = ?,
            total_platform_share = ?,
            available_balance = total_platform_share - total_allocated,
            last_updated = NOW()
        WHERE id = 1")->execute([
            $revenue['gross_gsm_generated'],
            $revenue['platform_share_gsm']
        ]);

        $treasury = $db->query("SELECT * FROM platform_treasury LIMIT 1")->fetch(PDO::FETCH_ASSOC);

        // Recent allocations
        $alloc = $db->query("SELECT * FROM platform_allocations ORDER BY created_at DESC LIMIT 10")
            ->fetchAll(PDO::FETCH_ASSOC);

        // User count
        $userCount = $db->query("SELECT COUNT(*) FROM search_user_profiles")->fetchColumn();

        // Pool stats
        $poolTotal = 250000000;
        $poolDistributed = $revenue['gross_gsm_generated'];
        $poolRemaining = $poolTotal - $poolDistributed;

        echo json_encode([
            'status' => 'ok',
            'revenue' => $revenue,
            'treasury' => [
                'total_earned' => round((float)($treasury['total_gsm_earned'] ?? 0), 8),
                'platform_share' => round((float)($treasury['total_platform_share'] ?? 0), 8),
                'allocated' => round((float)($treasury['total_allocated'] ?? 0), 8),
                'spent' => round((float)($treasury['total_spent'] ?? 0), 8),
                'available' => round((float)($treasury['available_balance'] ?? 0), 8),
            ],
            'pool' => [
                'total' => $poolTotal,
                'distributed' => round($poolDistributed, 8),
                'remaining' => round($poolRemaining, 8),
                'pct_distributed' => round(($poolDistributed / $poolTotal) * 100, 6),
            ],
            'allocations' => $alloc,
            'total_users' => (int)$userCount,
            'generated_at' => date('c'),
        ]);
        break;

    case 'platform_share':
        $user = requireAdmin();
        $db = getDB();
        $revenue = aggregateMiningRevenue($db);
        echo json_encode([
            'platform_share_gsm' => $revenue['platform_share_gsm'],
            'platform_share_pct' => PLATFORM_SHARE * 100,
            'mining_share' => $revenue['mining']['platform_gsm'],
            'search_share' => $revenue['search']['platform_gsm'],
            'today' => $revenue['today'],
            'trend_7d' => $revenue['trend_7d'],
        ]);
        break;

    case 'allocations':
        $user = requireAdmin();
        $db = getDB();
        ensureRevenueTables();
        $alloc = $db->query("SELECT * FROM platform_allocations ORDER BY created_at DESC LIMIT 50")
            ->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['allocations' => $alloc]);
        break;

    case 'allocate':
        $user = requireAdmin();
        $db = getDB();
        ensureRevenueTables();

        $input = json_decode(file_get_contents('php://input'), true);
        $program = trim($input['program'] ?? '');
        $amount = (float)($input['amount'] ?? 0);
        $desc = trim($input['description'] ?? '');

        if (!$program || $amount <= 0) {
            echo json_encode(['error' => 'Program name and positive amount required']);
            break;
        }

        // Check available balance
        $treasury = $db->query("SELECT available_balance FROM platform_treasury LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($amount > (float)($treasury['available_balance'] ?? 0)) {
            echo json_encode(['error' => 'Insufficient treasury balance', 'available' => $treasury['available_balance']]);
            break;
        }

        $stmt = $db->prepare("INSERT INTO platform_allocations (program, amount, authorized_by, description, status)
            VALUES (?, ?, ?, ?, 'allocated')");
        $stmt->execute([$program, $amount, $user['id'], $desc]);

        $db->exec("UPDATE platform_treasury SET total_allocated = total_allocated + $amount, available_balance = total_platform_share - total_allocated - $amount WHERE id = 1");

        echo json_encode(['status' => 'allocated', 'id' => $db->lastInsertId(), 'program' => $program, 'amount' => $amount]);
        break;

    case 'treasury':
        $user = requireAdmin();
        $db = getDB();
        ensureRevenueTables();

        // Refresh treasury
        $revenue = aggregateMiningRevenue($db);
        $db->prepare("UPDATE platform_treasury SET total_gsm_earned = ?, total_platform_share = ?, available_balance = ? - total_allocated WHERE id = 1")
            ->execute([$revenue['gross_gsm_generated'], $revenue['platform_share_gsm'], $revenue['platform_share_gsm']]);

        $treasury = $db->query("SELECT * FROM platform_treasury LIMIT 1")->fetch(PDO::FETCH_ASSOC);

        // Allocation breakdown by program
        $stmt = $db->query("SELECT program, SUM(amount) as total, COUNT(*) as count, status
            FROM platform_allocations GROUP BY program, status ORDER BY total DESC");
        $byProgram = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'treasury' => $treasury,
            'allocations_by_program' => $byProgram,
            'revenue_summary' => [
                'gross' => $revenue['gross_gsm_generated'],
                'platform_20pct' => $revenue['platform_share_gsm'],
                'user_80pct' => $revenue['user_share_gsm'],
            ],
        ]);
        break;

    case 'daily_report':
        $user = requireAdmin();
        $db = getDB();
        $days = min((int)($_GET['days'] ?? 30), 90);

        $stmt = $db->prepare("SELECT
            DATE(created_at) as day,
            type,
            SUM(amount) as total_user_gsm,
            COUNT(*) as reward_count,
            COUNT(DISTINCT user_id) as unique_users
        FROM search_mining_rewards
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        GROUP BY DATE(created_at), type
        ORDER BY day DESC, type");
        $stmt->execute([$days]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Group by day
        $report = [];
        foreach ($rows as $r) {
            $day = $r['day'];
            if (!isset($report[$day])) {
                $report[$day] = ['date' => $day, 'mining' => 0, 'search' => 0, 'total_user' => 0, 'total_platform' => 0, 'rewards' => 0, 'users' => 0];
            }
            $userGSM = (float)$r['total_user_gsm'];
            $platGSM = $userGSM * PLATFORM_SHARE / (1 - PLATFORM_SHARE);
            if ($r['type'] === 'search') $report[$day]['search'] += $userGSM;
            else $report[$day]['mining'] += $userGSM;
            $report[$day]['total_user'] += $userGSM;
            $report[$day]['total_platform'] += $platGSM;
            $report[$day]['rewards'] += (int)$r['reward_count'];
            $report[$day]['users'] = max($report[$day]['users'], (int)$r['unique_users']);
        }

        echo json_encode(['days' => $days, 'report' => array_values($report)]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action', 'actions' => ['dashboard','platform_share','allocations','allocate','treasury','daily_report']]);
}
