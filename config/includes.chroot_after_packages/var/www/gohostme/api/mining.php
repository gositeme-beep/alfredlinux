<?php
/**
 * Alfred Mining & Wallet API
 * ──────────────────────────
 * Handles browser mining rewards, wallet balances, search rewards,
 * and GSM token distribution.
 *
 * Endpoints:
 *   POST /api/mining.php?action=submit_work    → Submit mining proof-of-work
 *   POST /api/mining.php?action=toggle_mining   → Enable/disable mining
 *   GET  /api/mining.php?action=wallet          → Get wallet balance + stats
 *   GET  /api/mining.php?action=history         → Transaction history
 *   GET  /api/mining.php?action=leaderboard     → Top miners
 *   POST /api/mining.php?action=search_reward   → Award search reward
 *   GET  /api/mining.php?action=pool_stats      → Mining pool statistics
 *
 * Revenue Split: 80% User / 20% Platform
 * Token: GSM (GoSiteMe Token) on Solana
 */

define('GOSITEME_API', true);
require_once dirname(__DIR__) . '/api/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// ── Configuration ───────────────────────────────────────────────
define('MINING_USER_SHARE', 0.80);    // 80% to user
define('MINING_PLATFORM_SHARE', 0.20); // 20% to platform
define('GSM_PER_HASH_BLOCK', 0.001);  // GSM per valid hash block (1M hashes)
define('GSM_PER_SEARCH', 0.0001);     // GSM per search query
define('GSM_DAILY_SEARCH_CAP', 100);  // Max rewarded searches per day
define('GSM_STREAK_BONUS', 0.01);     // Bonus per day of streak
define('HASH_BLOCK_SIZE', 1000000);   // Hashes per reward block (1M)
define('MIN_DIFFICULTY', 4);          // Minimum leading zeros
define('MAX_HASHRATE', 50000);        // Max hashes/sec (abuse protection)
define('REWARD_POOL_TOTAL', 250000000); // 250M GSM allocated for mining (25%)

// ── Auth ────────────────────────────────────────────────────────
function getAuthUser(): ?array {
    if (session_status() === PHP_SESSION_NONE) session_start();

    if (!empty($_SESSION['logged_in']) && !empty($_SESSION['user_id'])) {
        return [
            'id' => (int)$_SESSION['user_id'],
            'name' => $_SESSION['client_name'] ?? '',
        ];
    }

    // API key auth
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/^Bearer\s+(.+)$/', $authHeader, $m)) {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, CONCAT(firstname, ' ', lastname) AS fullname FROM clients WHERE api_key = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$m[1]]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) return ['id' => (int)$user['id'], 'name' => $user['fullname']];
    }

    return null;
}

function requireAuth(): array {
    $user = getAuthUser();
    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        exit;
    }
    return $user;
}

// ── User Profile ────────────────────────────────────────────────
function ensureProfile(int $userId, PDO $db): void {
    $db->prepare("INSERT IGNORE INTO search_user_profiles (user_id) VALUES (?)")
       ->execute([$userId]);
}

function getProfile(int $userId, PDO $db): array {
    ensureProfile($userId, $db);
    $stmt = $db->prepare("SELECT * FROM search_user_profiles WHERE user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

// ── Mining Pool Stats ───────────────────────────────────────────
function getPoolStats(PDO $db): array {
    $distributed = $db->query("SELECT COALESCE(SUM(total_gsm_earned), 0) FROM search_user_profiles")
                      ->fetchColumn();
    $miners = $db->query("SELECT COUNT(*) FROM search_user_profiles WHERE mining_enabled = 1")
                 ->fetchColumn();
    $totalHashes = $db->query("SELECT COALESCE(SUM(mining_total_hashes), 0) FROM search_user_profiles")
                      ->fetchColumn();
    $searchRewards = $db->query("SELECT COALESCE(SUM(gsm_amount), 0) FROM search_mining_rewards WHERE reward_type = 'search'")
                        ->fetchColumn();
    $miningRewards = $db->query("SELECT COALESCE(SUM(gsm_amount), 0) FROM search_mining_rewards WHERE reward_type = 'crawl_contribute'")
                        ->fetchColumn();

    return [
        'pool_total' => REWARD_POOL_TOTAL,
        'distributed' => (float)$distributed,
        'remaining' => REWARD_POOL_TOTAL - (float)$distributed,
        'active_miners' => (int)$miners,
        'total_hashes' => (int)$totalHashes,
        'search_rewards_total' => (float)$searchRewards,
        'mining_rewards_total' => (float)$miningRewards,
        'user_share' => MINING_USER_SHARE,
        'platform_share' => MINING_PLATFORM_SHARE,
        'gsm_per_hash_block' => GSM_PER_HASH_BLOCK,
        'gsm_per_search' => GSM_PER_SEARCH,
    ];
}

// ── Actions ─────────────────────────────────────────────────────
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$db = getDB();

switch ($action) {

    // ── Get Wallet Balance + Profile ────────────────────────────
    case 'wallet':
        $user = requireAuth();
        $profile = getProfile($user['id'], $db);
        $pool = getPoolStats($db);

        // Get recent transactions
        $stmt = $db->prepare("SELECT reward_type, gsm_amount, description, created_at
                              FROM search_mining_rewards WHERE user_id = ?
                              ORDER BY created_at DESC LIMIT 20");
        $stmt->execute([$user['id']]);
        $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'ok' => true,
            'wallet' => [
                'balance' => (float)$profile['total_gsm_earned'],
                'wallet_address' => $profile['wallet_address'] ?: null,
                'mining_enabled' => (bool)$profile['mining_enabled'],
                'hashrate' => (float)$profile['mining_hashrate'],
                'total_hashes' => (int)$profile['mining_total_hashes'],
                'searches_today' => getDailySearchCount($user['id'], $db),
                'search_streak' => (int)$profile['search_streak_days'],
            ],
            'pool' => $pool,
            'recent_transactions' => $recent,
        ]);
        break;

    // ── Toggle Mining ───────────────────────────────────────────
    case 'toggle_mining':
        $user = requireAuth();
        ensureProfile($user['id'], $db);
        $profile = getProfile($user['id'], $db);
        $newState = $profile['mining_enabled'] ? 0 : 1;
        $db->prepare("UPDATE search_user_profiles SET mining_enabled = ? WHERE user_id = ?")
           ->execute([$newState, $user['id']]);
        echo json_encode([
            'ok' => true,
            'mining_enabled' => (bool)$newState,
            'message' => $newState ? 'Mining enabled — you earn GSM while browsing' : 'Mining paused',
        ]);
        break;

    // ── Submit Mining Work ──────────────────────────────────────
    case 'submit_work':
        $user = requireAuth();
        $input = json_decode(file_get_contents('php://input'), true);
        $hashes = (int)($input['hashes'] ?? 0);
        $nonce = $input['nonce'] ?? '';
        $difficulty = (int)($input['difficulty'] ?? 0);
        $jobId = $input['job_id'] ?? '';

        // Validate
        if ($hashes <= 0 || $hashes > HASH_BLOCK_SIZE * 2) {
            echo json_encode(['error' => 'Invalid hash count']);
            break;
        }
        if ($difficulty < MIN_DIFFICULTY) {
            echo json_encode(['error' => 'Difficulty too low']);
            break;
        }

        // Rate limit: Max hashrate check (prevent fake submissions)
        $profile = getProfile($user['id'], $db);
        $lastCheck = strtotime($profile['updated_at'] ?? 'now');
        $elapsed = max(1, time() - $lastCheck);
        $currentRate = $hashes / $elapsed;
        if ($currentRate > MAX_HASHRATE) {
            echo json_encode(['error' => 'Hashrate exceeds maximum']);
            break;
        }

        // Pool exhaustion check
        $pool = getPoolStats($db);
        if ($pool['remaining'] <= 0) {
            echo json_encode(['error' => 'Mining pool exhausted', 'remaining' => 0]);
            break;
        }

        // Calculate reward: 80% to user
        $hashBlocks = $hashes / HASH_BLOCK_SIZE;
        $totalReward = $hashBlocks * GSM_PER_HASH_BLOCK;
        $userReward = round($totalReward * MINING_USER_SHARE, 9);

        // Cap at remaining pool
        $userReward = min($userReward, $pool['remaining'] * MINING_USER_SHARE);

        if ($userReward > 0) {
            // Record reward
            $db->prepare("INSERT INTO search_mining_rewards (user_id, reward_type, gsm_amount, description)
                          VALUES (?, 'crawl_contribute', ?, ?)")
               ->execute([$user['id'], $userReward, "Mining: {$hashes} hashes at difficulty $difficulty"]);

            // Update profile
            $db->prepare("UPDATE search_user_profiles
                          SET total_gsm_earned = total_gsm_earned + ?,
                              mining_total_hashes = mining_total_hashes + ?,
                              mining_hashrate = ?
                          WHERE user_id = ?")
               ->execute([$userReward, $hashes, $currentRate, $user['id']]);
        }

        echo json_encode([
            'ok' => true,
            'reward' => $userReward,
            'total_earned' => (float)$profile['total_gsm_earned'] + $userReward,
            'hashes_accepted' => $hashes,
            'pool_remaining' => $pool['remaining'] - $totalReward,
        ]);
        break;

    // ── Search Reward ───────────────────────────────────────────
    case 'search_reward':
        $user = getAuthUser();
        if (!$user) {
            echo json_encode(['ok' => false, 'reason' => 'not_authenticated']);
            break;
        }

        ensureProfile($user['id'], $db);

        // Daily cap
        $dailyCount = getDailySearchCount($user['id'], $db);
        if ($dailyCount >= GSM_DAILY_SEARCH_CAP) {
            echo json_encode([
                'ok' => true,
                'reward' => 0,
                'reason' => 'daily_cap_reached',
                'searches_today' => $dailyCount,
            ]);
            break;
        }

        // Update streak
        $profile = getProfile($user['id'], $db);
        $lastSearch = $profile['last_search_at'] ? strtotime($profile['last_search_at']) : 0;
        $today = strtotime('today');
        $yesterday = strtotime('yesterday');
        $streak = (int)$profile['search_streak_days'];

        if ($lastSearch >= $yesterday && $lastSearch < $today) {
            $streak++; // Continuing streak
        } elseif ($lastSearch < $yesterday) {
            $streak = 1; // Reset streak
        }

        // Calculate reward with streak bonus
        $baseReward = GSM_PER_SEARCH;
        $streakBonus = min($streak * GSM_STREAK_BONUS * 0.01, GSM_PER_SEARCH); // Cap bonus at 100%
        $reward = round($baseReward + $streakBonus, 9);

        // Record
        $db->prepare("INSERT INTO search_mining_rewards (user_id, reward_type, gsm_amount, description)
                      VALUES (?, 'search', ?, ?)")
           ->execute([$user['id'], $reward, "Search reward (streak: {$streak}d)"]);

        $db->prepare("UPDATE search_user_profiles
                      SET total_gsm_earned = total_gsm_earned + ?,
                          total_searches = total_searches + 1,
                          search_streak_days = ?,
                          last_search_at = NOW()
                      WHERE user_id = ?")
           ->execute([$reward, $streak, $user['id']]);

        echo json_encode([
            'ok' => true,
            'reward' => $reward,
            'streak' => $streak,
            'searches_today' => $dailyCount + 1,
            'daily_cap' => GSM_DAILY_SEARCH_CAP,
        ]);
        break;

    // ── Transaction History ─────────────────────────────────────
    case 'history':
        $user = requireAuth();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(100, max(10, (int)($_GET['limit'] ?? 50)));
        $offset = ($page - 1) * $limit;

        $stmt = $db->prepare("SELECT reward_type, gsm_amount, description, created_at
                              FROM search_mining_rewards WHERE user_id = ?
                              ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->execute([$user['id'], $limit, $offset]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total = $db->prepare("SELECT COUNT(*) FROM search_mining_rewards WHERE user_id = ?");
        $total->execute([$user['id']]);
        $count = $total->fetchColumn();

        echo json_encode([
            'ok' => true,
            'transactions' => $rows,
            'page' => $page,
            'total' => (int)$count,
            'pages' => ceil($count / $limit),
        ]);
        break;

    // ── Leaderboard ─────────────────────────────────────────────
    case 'leaderboard':
        $stmt = $db->query("SELECT sp.user_id, CONCAT(c.firstname, ' ', c.lastname) AS name,
                                   sp.total_gsm_earned, sp.mining_total_hashes,
                                   sp.total_searches, sp.search_streak_days
                            FROM search_user_profiles sp
                            LEFT JOIN clients c ON c.id = sp.user_id
                            ORDER BY sp.total_gsm_earned DESC
                            LIMIT 50");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Anonymize names
        $leaderboard = array_map(function($r, $i) {
            $name = $r['name'] ?: 'Anon';
            return [
                'rank' => $i + 1,
                'name' => mb_substr($name, 0, 1) . str_repeat('*', max(0, mb_strlen($name) - 2)) . mb_substr($name, -1),
                'gsm_earned' => (float)$r['total_gsm_earned'],
                'hashes' => (int)$r['mining_total_hashes'],
                'searches' => (int)$r['total_searches'],
                'streak' => (int)$r['search_streak_days'],
            ];
        }, $rows, array_keys($rows));

        echo json_encode(['ok' => true, 'leaderboard' => $leaderboard]);
        break;

    // ── Pool Stats ──────────────────────────────────────────────
    case 'pool_stats':
        echo json_encode(['ok' => true, 'pool' => getPoolStats($db)]);
        break;

    // ── Set Wallet Address ──────────────────────────────────────
    case 'set_wallet':
        $user = requireAuth();
        $input = json_decode(file_get_contents('php://input'), true);
        $address = trim($input['wallet_address'] ?? '');

        // Validate Solana address (base58, 32-44 chars)
        if (!preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $address)) {
            echo json_encode(['error' => 'Invalid Solana wallet address']);
            break;
        }

        ensureProfile($user['id'], $db);
        $db->prepare("UPDATE search_user_profiles SET wallet_address = ? WHERE user_id = ?")
           ->execute([$address, $user['id']]);

        echo json_encode(['ok' => true, 'wallet_address' => $address]);
        break;

    default:
        echo json_encode(['error' => 'Invalid action', 'actions' => [
            'wallet', 'toggle_mining', 'submit_work', 'search_reward',
            'history', 'leaderboard', 'pool_stats', 'set_wallet'
        ]]);
}

// ── Helper ──────────────────────────────────────────────────────
function getDailySearchCount(int $userId, PDO $db): int {
    $stmt = $db->prepare("SELECT COUNT(*) FROM search_mining_rewards
                          WHERE user_id = ? AND reward_type = 'search'
                          AND DATE(created_at) = CURDATE()");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}
