<?php
/**
 * Gamification System API — XP, Levels, Streaks, Achievements, Leaderboards, Daily Challenges
 * Reduces churn through engagement loops. Hooks into all Alfred activity.
 */
define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Internal-Secret');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

// ─── Auth helpers (internal calls from Alfred chat bypass session) ───
function gamifyIsInternal(): bool {
    $secret = defined('INTERNAL_SECRET') ? INTERNAL_SECRET : '';
    return $secret && isset($_SERVER['HTTP_X_INTERNAL_SECRET']) && hash_equals($secret, $_SERVER['HTTP_X_INTERNAL_SECRET']);
}
function gamifyRequireAuth(): void {
    if (gamifyIsInternal()) return;
    if (empty($_SESSION['logged_in']) || empty($_SESSION['client_id'])) {
        jsonResponse(['error' => 'Authentication required'], 401);
require_once dirname(__DIR__) . '/includes/api-security.php';
    }
}
function gamifyGetClientId(): int {
    if (gamifyIsInternal()) {
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        return (int) ($body['client_id'] ?? $_SESSION['client_id'] ?? 0);
    }
    return (int) ($_SESSION['client_id'] ?? 0);
}

// ─── Schema ───────────────────────────────────────────────────
function ensureGamifySchema(): void {
    $db = getDB();
    try {

    $db->exec("CREATE TABLE IF NOT EXISTS gamify_user_stats (
        client_id INT PRIMARY KEY,
        total_xp INT DEFAULT 0,
        level INT DEFAULT 1,
        current_streak INT DEFAULT 0,
        longest_streak INT DEFAULT 0,
        last_activity_date DATE,
        tools_used INT DEFAULT 0,
        conversations INT DEFAULT 0,
        agents_created INT DEFAULT 0,
        challenges_completed INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS gamify_xp_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        xp_amount INT NOT NULL,
        source VARCHAR(50) NOT NULL,
        description VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_client (client_id),
        INDEX idx_source (source),
        INDEX idx_created (created_at)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS gamify_achievements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        badge_key VARCHAR(50) UNIQUE NOT NULL,
        name VARCHAR(100) NOT NULL,
        description VARCHAR(255),
        icon VARCHAR(10) DEFAULT NULL,
        xp_reward INT DEFAULT 50,
        criteria_type ENUM('xp_total','streak','tools_used','conversations','agents_created','challenges','level','custom') NOT NULL,
        criteria_value INT DEFAULT 1,
        tier ENUM('bronze','silver','gold','platinum','diamond') DEFAULT 'bronze',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS gamify_user_achievements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        achievement_id INT NOT NULL,
        earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_client_achievement (client_id, achievement_id),
        INDEX idx_client (client_id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS gamify_daily_challenges (
        id INT AUTO_INCREMENT PRIMARY KEY,
        challenge_date DATE NOT NULL,
        challenge_type VARCHAR(50) NOT NULL,
        title VARCHAR(128) NOT NULL,
        description VARCHAR(255),
        xp_reward INT DEFAULT 25,
        criteria_action VARCHAR(50),
        criteria_count INT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_date_type (challenge_date, challenge_type)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS gamify_user_challenges (
        id INT AUTO_INCREMENT PRIMARY KEY,
        client_id INT NOT NULL,
        challenge_id INT NOT NULL,
        progress INT DEFAULT 0,
        completed TINYINT(1) DEFAULT 0,
        completed_at TIMESTAMP NULL,
        UNIQUE KEY uk_client_challenge (client_id, challenge_id),
        INDEX idx_client (client_id)
    )");

    // Seed achievements if empty
    $count = (int) $db->query("SELECT COUNT(*) FROM gamify_achievements")->fetchColumn();
    if ($count === 0) {
        $db->exec("INSERT INTO gamify_achievements (badge_key, name, description, icon, xp_reward, criteria_type, criteria_value, tier) VALUES
            ('first_chat', 'First Words', 'Had your first conversation with Alfred', '💬', 25, 'conversations', 1, 'bronze'),
            ('chat_10', 'Conversationalist', 'Had 10 conversations', '🗣️', 50, 'conversations', 10, 'bronze'),
            ('chat_100', 'Chat Champion', 'Had 100 conversations', '🏅', 200, 'conversations', 100, 'silver'),
            ('chat_1000', 'Chat Legend', '1,000 conversations — you and Alfred are besties', '👑', 500, 'conversations', 1000, 'gold'),
            ('tool_1', 'Tool Explorer', 'Used your first tool', '🔧', 25, 'tools_used', 1, 'bronze'),
            ('tool_25', 'Tool Master', 'Used 25 different tools', '⚡', 100, 'tools_used', 25, 'silver'),
            ('tool_100', 'Tool Wizard', 'Used 100 tools — you know the full arsenal', '🧙', 300, 'tools_used', 100, 'gold'),
            ('streak_3', 'Getting Started', '3-day activity streak', '🔥', 50, 'streak', 3, 'bronze'),
            ('streak_7', 'Week Warrior', '7-day activity streak', '📅', 100, 'streak', 7, 'silver'),
            ('streak_30', 'Monthly Machine', '30-day streak — unstoppable', '🗓️', 300, 'streak', 30, 'gold'),
            ('streak_100', 'Century Streak', '100-day streak — legendary dedication', '💎', 1000, 'streak', 100, 'platinum'),
            ('streak_365', 'Year of Alfred', '365-day streak — a full year', '🌟', 5000, 'streak', 365, 'diamond'),
            ('agent_1', 'Agent Creator', 'Created your first AI agent', '🤖', 50, 'agents_created', 1, 'bronze'),
            ('agent_5', 'Agent Commander', 'Created 5 AI agents', '🎖️', 150, 'agents_created', 5, 'silver'),
            ('agent_25', 'Fleet Admiral', '25 agents — you command an AI army', '⭐', 500, 'agents_created', 25, 'gold'),
            ('xp_100', 'Rising Star', 'Earned 100 XP', '⬆️', 25, 'xp_total', 100, 'bronze'),
            ('xp_1000', 'Power User', 'Earned 1,000 XP', '💪', 100, 'xp_total', 1000, 'silver'),
            ('xp_10000', 'XP Legend', 'Earned 10,000 XP', '🏆', 500, 'xp_total', 10000, 'gold'),
            ('xp_100000', 'Transcendent', '100,000 XP — you ARE Alfred', '🌌', 2500, 'xp_total', 100000, 'diamond'),
            ('level_5', 'Apprentice', 'Reached Level 5', '📗', 50, 'level', 5, 'bronze'),
            ('level_10', 'Journeyman', 'Reached Level 10', '📘', 100, 'level', 10, 'silver'),
            ('level_25', 'Expert', 'Reached Level 25', '📕', 300, 'level', 25, 'gold'),
            ('level_50', 'Grandmaster', 'Reached Level 50', '📓', 1000, 'level', 50, 'platinum'),
            ('challenge_1', 'Challenge Accepted', 'Completed your first daily challenge', '✅', 25, 'challenges', 1, 'bronze'),
            ('challenge_10', 'Challenger', 'Completed 10 daily challenges', '🎯', 100, 'challenges', 10, 'silver'),
            ('challenge_50', 'Challenge Crusher', '50 challenges conquered', '💥', 300, 'challenges', 50, 'gold')
        ");
    }

    } catch (PDOException $e) {
        error_log("Gamification schema error: " . $e->getMessage());
    }
}
ensureGamifySchema();

// ─── XP / Level Calculations ──────────────────────────────────
function xpForLevel(int $level): int {
    // XP needed to REACH this level: 100 * level^1.5
    return (int) (100 * pow($level, 1.5));
}

function levelFromXP(int $totalXP): int {
    $level = 1;
    while (xpForLevel($level + 1) <= $totalXP) {
        $level++;
        if ($level >= 100) break;
    }
    return $level;
}

// ─── Routing ──────────────────────────────────────────────────
$action = sanitize($_GET['action'] ?? $_POST['action'] ?? '', 50);

switch ($action) {
    case 'profile':          gamifyRequireAuth(); getProfile(); break;
    case 'award_xp':         gamifyRequireAuth(); awardXP(); break;
    case 'leaderboard':      getLeaderboard(); break;
    case 'achievements':     listAchievements(); break;
    case 'my_achievements':  gamifyRequireAuth(); myAchievements(); break;
    case 'check_streak':     gamifyRequireAuth(); checkStreak(); break;
    case 'daily_challenge':  gamifyRequireAuth(); dailyChallenge(); break;
    case 'complete_challenge': gamifyRequireAuth(); completeChallenge(); break;
    case 'xp_history':       gamifyRequireAuth(); xpHistory(); break;
    case 'stats':            platformStats(); break;
    default: jsonResponse(['error' => 'Unknown action', 'actions' => [
        'profile','award_xp','leaderboard','achievements','my_achievements',
        'check_streak','daily_challenge','complete_challenge','xp_history','stats'
    ]], 400);
}

// ─── Profile ──────────────────────────────────────────────────
function getProfile(): void {
    $db = getDB();
    $clientId = gamifyGetClientId();

    // Ensure user stats row exists
    $db->prepare("INSERT IGNORE INTO gamify_user_stats (client_id) VALUES (?)")->execute([$clientId]);

    $stmt = $db->prepare("SELECT * FROM gamify_user_stats WHERE client_id = ?");
    $stmt->execute([$clientId]);
    $stats = $stmt->fetch();

    $level = levelFromXP($stats['total_xp']);
    $nextLevelXP = xpForLevel($level + 1);

    // Count achievements
    $stmt = $db->prepare("SELECT COUNT(*) FROM gamify_user_achievements WHERE client_id = ?");
    $stmt->execute([$clientId]);
    $badgeCount = (int) $stmt->fetchColumn();
    $totalBadges = (int) $db->query("SELECT COUNT(*) FROM gamify_achievements")->fetchColumn();

    // Recent XP
    $stmt = $db->prepare("SELECT source, SUM(xp_amount) as total, COUNT(*) as count FROM gamify_xp_log WHERE client_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY source ORDER BY total DESC LIMIT 5");
    $stmt->execute([$clientId]);
    $recentSources = $stmt->fetchAll();

    jsonResponse([
        'success' => true,
        'profile' => [
            'total_xp' => (int) $stats['total_xp'],
            'level' => $level,
            'next_level_xp' => $nextLevelXP,
            'xp_progress' => $stats['total_xp'] - xpForLevel($level),
            'xp_needed' => $nextLevelXP - $stats['total_xp'],
            'current_streak' => (int) $stats['current_streak'],
            'longest_streak' => (int) $stats['longest_streak'],
            'tools_used' => (int) $stats['tools_used'],
            'conversations' => (int) $stats['conversations'],
            'agents_created' => (int) $stats['agents_created'],
            'challenges_completed' => (int) $stats['challenges_completed'],
            'badges' => "$badgeCount/$totalBadges",
            'last_activity' => $stats['last_activity_date'],
        ],
        'recent_xp_sources' => $recentSources,
    ]);
}

// ─── Award XP ─────────────────────────────────────────────────
function awardXP(): void {
    $db = getDB();
    $clientId = gamifyGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $amount = max(1, min(1000, (int) ($input['amount'] ?? 10)));
    $source = sanitize($input['source'] ?? 'general', 50);
    $description = sanitize($input['description'] ?? '', 255);

    // Ensure user row
    $db->prepare("INSERT IGNORE INTO gamify_user_stats (client_id) VALUES (?)")->execute([$clientId]);

    // Log XP
    $stmt = $db->prepare("INSERT INTO gamify_xp_log (client_id, xp_amount, source, description) VALUES (?, ?, ?, ?)");
    $stmt->execute([$clientId, $amount, $source, $description]);

    // Update total + counter
    $counterCol = match($source) {
        'conversation', 'chat' => 'conversations = conversations + 1',
        'tool_use', 'tool' => 'tools_used = tools_used + 1',
        'agent_create', 'agent' => 'agents_created = agents_created + 1',
        'challenge' => 'challenges_completed = challenges_completed + 1',
        default => null,
    };
    $sql = "UPDATE gamify_user_stats SET total_xp = total_xp + ?, last_activity_date = CURDATE()";
    if ($counterCol) $sql .= ", $counterCol";
    $sql .= " WHERE client_id = ?";
    $db->prepare($sql)->execute([$amount, $clientId]);

    // Recalculate level
    $totalXP = (int) $db->prepare("SELECT total_xp FROM gamify_user_stats WHERE client_id = ?")->execute([$clientId]) ? 0 : 0;
    $stmt = $db->prepare("SELECT total_xp FROM gamify_user_stats WHERE client_id = ?");
    $stmt->execute([$clientId]);
    $totalXP = (int) $stmt->fetchColumn();
    $newLevel = levelFromXP($totalXP);

    $db->prepare("UPDATE gamify_user_stats SET level = ? WHERE client_id = ?")->execute([$newLevel, $clientId]);

    // Check for new achievements
    $newBadges = checkAndAwardAchievements($db, $clientId);

    jsonResponse([
        'success' => true,
        'xp_awarded' => $amount,
        'total_xp' => $totalXP,
        'level' => $newLevel,
        'new_badges' => $newBadges,
    ]);
}

// ─── Achievement Checker ──────────────────────────────────────
function checkAndAwardAchievements(PDO $db, int $clientId): array {
    $stmt = $db->prepare("SELECT * FROM gamify_user_stats WHERE client_id = ?");
    $stmt->execute([$clientId]);
    $stats = $stmt->fetch();
    if (!$stats) return [];

    // Get unearned achievements
    $stmt = $db->prepare("SELECT a.* FROM gamify_achievements a LEFT JOIN gamify_user_achievements ua ON a.id = ua.achievement_id AND ua.client_id = ? WHERE ua.id IS NULL");
    $stmt->execute([$clientId]);
    $unearned = $stmt->fetchAll();

    $newBadges = [];
    foreach ($unearned as $ach) {
        $earned = false;
        $val = match($ach['criteria_type']) {
            'xp_total' => (int) $stats['total_xp'],
            'streak' => (int) $stats['current_streak'],
            'tools_used' => (int) $stats['tools_used'],
            'conversations' => (int) $stats['conversations'],
            'agents_created' => (int) $stats['agents_created'],
            'challenges' => (int) $stats['challenges_completed'],
            'level' => (int) $stats['level'],
            default => 0,
        };
        if ($val >= (int) $ach['criteria_value']) {
            $earned = true;
        }
        if ($earned) {
            $db->prepare("INSERT IGNORE INTO gamify_user_achievements (client_id, achievement_id) VALUES (?, ?)")
                ->execute([$clientId, $ach['id']]);
            // Award bonus XP for achievement
            $db->prepare("INSERT INTO gamify_xp_log (client_id, xp_amount, source, description) VALUES (?, ?, 'achievement', ?)")
                ->execute([$clientId, $ach['xp_reward'], "Badge: {$ach['name']}"]);
            $db->prepare("UPDATE gamify_user_stats SET total_xp = total_xp + ? WHERE client_id = ?")
                ->execute([$ach['xp_reward'], $clientId]);
            $newBadges[] = ['key' => $ach['badge_key'], 'name' => $ach['name'], 'icon' => $ach['icon'], 'xp' => $ach['xp_reward']];
        }
    }
    return $newBadges;
}

// ─── Leaderboard ──────────────────────────────────────────────
function getLeaderboard(): void {
    $db = getDB();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_GET;
    $period = sanitize($input['period'] ?? 'all', 20);
    $limit = min(50, max(5, (int) ($input['limit'] ?? 20)));

    if ($period === 'weekly') {
        $stmt = $db->prepare("SELECT client_id, SUM(xp_amount) as xp FROM gamify_xp_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY client_id ORDER BY xp DESC LIMIT ?");
        dbExecute($stmt, [$limit]);
    } elseif ($period === 'monthly') {
        $stmt = $db->prepare("SELECT client_id, SUM(xp_amount) as xp FROM gamify_xp_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY client_id ORDER BY xp DESC LIMIT ?");
        dbExecute($stmt, [$limit]);
    } else {
        $stmt = $db->prepare("SELECT client_id, total_xp as xp, level, current_streak FROM gamify_user_stats ORDER BY total_xp DESC LIMIT ?");
        dbExecute($stmt, [$limit]);
    }

    $entries = $stmt->fetchAll();
    $rank = 1;
    foreach ($entries as &$e) {
        $e['rank'] = $rank++;
        $e['level'] = $e['level'] ?? levelFromXP((int) $e['xp']);
    }

    jsonResponse(['success' => true, 'period' => $period, 'leaderboard' => $entries]);
}

// ─── Achievements ─────────────────────────────────────────────
function listAchievements(): void {
    $db = getDB();
    $achievements = $db->query("SELECT badge_key, name, description, icon, xp_reward, criteria_type, criteria_value, tier FROM gamify_achievements ORDER BY tier, criteria_value")->fetchAll();
    jsonResponse(['success' => true, 'achievements' => $achievements]);
}

function myAchievements(): void {
    $db = getDB();
    $clientId = gamifyGetClientId();

    $stmt = $db->prepare("SELECT a.badge_key, a.name, a.description, a.icon, a.xp_reward, a.tier, ua.earned_at FROM gamify_user_achievements ua JOIN gamify_achievements a ON a.id = ua.achievement_id WHERE ua.client_id = ? ORDER BY ua.earned_at DESC");
    $stmt->execute([$clientId]);
    $earned = $stmt->fetchAll();

    jsonResponse(['success' => true, 'earned' => $earned, 'count' => count($earned)]);
}

// ─── Streaks ──────────────────────────────────────────────────
function checkStreak(): void {
    $db = getDB();
    $clientId = gamifyGetClientId();

    $db->prepare("INSERT IGNORE INTO gamify_user_stats (client_id) VALUES (?)")->execute([$clientId]);

    $stmt = $db->prepare("SELECT current_streak, longest_streak, last_activity_date FROM gamify_user_stats WHERE client_id = ?");
    $stmt->execute([$clientId]);
    $stats = $stmt->fetch();

    $today = date('Y-m-d');
    $lastDate = $stats['last_activity_date'];
    $streak = (int) $stats['current_streak'];
    $longest = (int) $stats['longest_streak'];
    $streakBroken = false;
    $newDay = false;

    if ($lastDate === $today) {
        // Already logged today
    } elseif ($lastDate === date('Y-m-d', strtotime('-1 day'))) {
        // Consecutive day
        $streak++;
        $newDay = true;
    } else {
        // Streak broken (or first day)
        $streakBroken = $streak > 0;
        $streak = 1;
        $newDay = true;
    }

    if ($streak > $longest) $longest = $streak;

    if ($newDay) {
        $db->prepare("UPDATE gamify_user_stats SET current_streak = ?, longest_streak = ?, last_activity_date = ? WHERE client_id = ?")
            ->execute([$streak, $longest, $today, $clientId]);

        // Award streak XP (bonus for longer streaks)
        $streakXP = min(50, 5 + $streak);
        $db->prepare("INSERT INTO gamify_xp_log (client_id, xp_amount, source, description) VALUES (?, ?, 'streak', ?)")
            ->execute([$clientId, $streakXP, "Day $streak streak bonus"]);
        $db->prepare("UPDATE gamify_user_stats SET total_xp = total_xp + ? WHERE client_id = ?")->execute([$streakXP, $clientId]);

        checkAndAwardAchievements($db, $clientId);
    }

    jsonResponse([
        'success' => true,
        'current_streak' => $streak,
        'longest_streak' => $longest,
        'streak_broken' => $streakBroken,
        'new_day' => $newDay,
    ]);
}

// ─── Daily Challenges ─────────────────────────────────────────
function dailyChallenge(): void {
    $db = getDB();
    $clientId = gamifyGetClientId();
    $today = date('Y-m-d');

    // Auto-generate today's challenges if missing
    $existing = (int) $db->prepare("SELECT COUNT(*) FROM gamify_daily_challenges WHERE challenge_date = ?")->execute([$today]) ? 0 : 0;
    $stmt = $db->prepare("SELECT COUNT(*) FROM gamify_daily_challenges WHERE challenge_date = ?");
    $stmt->execute([$today]);
    $existing = (int) $stmt->fetchColumn();

    if ($existing === 0) {
        $templates = [
            ['chat_3', 'Triple Chat', 'Have 3 conversations with Alfred today', 25, 'conversation', 3],
            ['tool_2', 'Tool Time', 'Use 2 different Alfred tools today', 30, 'tool_use', 2],
            ['explore', 'Explorer', 'Try a tool category you haven\'t used before', 40, 'new_category', 1],
            ['share', 'Social Butterfly', 'Share something useful from Alfred with someone', 20, 'share', 1],
            ['voice_1', 'Voice First', 'Make or receive a voice call through Alfred', 35, 'voice_call', 1],
            ['create_agent', 'Agent Builder', 'Create or customize an AI agent', 50, 'agent_create', 1],
            ['finance_check', 'Money Moves', 'Check any financial metric or balance', 25, 'finance_tool', 1],
        ];
        // Pick 3 random challenges for today
        shuffle($templates);
        $picked = array_slice($templates, 0, 3);
        $insert = $db->prepare("INSERT IGNORE INTO gamify_daily_challenges (challenge_date, challenge_type, title, description, xp_reward, criteria_action, criteria_count) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($picked as $t) {
            $insert->execute([$today, $t[0], $t[1], $t[2], $t[3], $t[4], $t[5]]);
        }
    }

    // Get today's challenges with user progress
    $stmt = $db->prepare("SELECT c.id, c.challenge_type, c.title, c.description, c.xp_reward, c.criteria_action, c.criteria_count, COALESCE(uc.progress, 0) as progress, COALESCE(uc.completed, 0) as completed, uc.completed_at FROM gamify_daily_challenges c LEFT JOIN gamify_user_challenges uc ON c.id = uc.challenge_id AND uc.client_id = ? WHERE c.challenge_date = ? ORDER BY c.id");
    $stmt->execute([$clientId, $today]);
    $challenges = $stmt->fetchAll();

    jsonResponse(['success' => true, 'date' => $today, 'challenges' => $challenges]);
}

function completeChallenge(): void {
    $db = getDB();
    $clientId = gamifyGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $challengeId = (int) ($input['challenge_id'] ?? 0);

    if (!$challengeId) {
        jsonResponse(['error' => 'challenge_id required'], 400);
    }

    // Verify challenge exists and is today's
    $stmt = $db->prepare("SELECT * FROM gamify_daily_challenges WHERE id = ? AND challenge_date = CURDATE()");
    $stmt->execute([$challengeId]);
    $challenge = $stmt->fetch();
    if (!$challenge) {
        jsonResponse(['error' => 'Challenge not found or expired'], 404);
    }

    // Check not already completed
    $stmt = $db->prepare("SELECT completed FROM gamify_user_challenges WHERE client_id = ? AND challenge_id = ?");
    $stmt->execute([$clientId, $challengeId]);
    $existing = $stmt->fetch();
    if ($existing && $existing['completed']) {
        jsonResponse(['error' => 'Challenge already completed'], 400);
    }

    // Mark complete
    $db->prepare("INSERT INTO gamify_user_challenges (client_id, challenge_id, progress, completed, completed_at) VALUES (?, ?, ?, 1, NOW()) ON DUPLICATE KEY UPDATE progress = VALUES(progress), completed = 1, completed_at = NOW()")
        ->execute([$clientId, $challengeId, $challenge['criteria_count']]);

    // Award XP
    $xp = (int) $challenge['xp_reward'];
    $db->prepare("INSERT INTO gamify_xp_log (client_id, xp_amount, source, description) VALUES (?, ?, 'challenge', ?)")
        ->execute([$clientId, $xp, "Challenge: {$challenge['title']}"]);
    $db->prepare("UPDATE gamify_user_stats SET total_xp = total_xp + ?, challenges_completed = challenges_completed + 1 WHERE client_id = ?")
        ->execute([$xp, $clientId]);

    $newBadges = checkAndAwardAchievements($db, $clientId);

    jsonResponse([
        'success' => true,
        'challenge' => $challenge['title'],
        'xp_awarded' => $xp,
        'new_badges' => $newBadges,
    ]);
}

// ─── XP History ───────────────────────────────────────────────
function xpHistory(): void {
    $db = getDB();
    $clientId = gamifyGetClientId();
    $input = json_decode(file_get_contents('php://input'), true) ?: $_GET;
    $limit = min(100, max(10, (int) ($input['limit'] ?? 25)));

    $stmt = $db->prepare("SELECT xp_amount, source, description, created_at FROM gamify_xp_log WHERE client_id = ? ORDER BY created_at DESC LIMIT ?");
    dbExecute($stmt, [$clientId, $limit]);
    $history = $stmt->fetchAll();

    // Summary by source
    $stmt = $db->prepare("SELECT source, SUM(xp_amount) as total, COUNT(*) as events FROM gamify_xp_log WHERE client_id = ? GROUP BY source ORDER BY total DESC");
    $stmt->execute([$clientId]);
    $summary = $stmt->fetchAll();

    jsonResponse(['success' => true, 'history' => $history, 'summary' => $summary]);
}

// ─── Platform Stats ───────────────────────────────────────────
function platformStats(): void {
    $db = getDB();

    $totalUsers = (int) $db->query("SELECT COUNT(*) FROM gamify_user_stats")->fetchColumn();
    $totalXP = (int) $db->query("SELECT COALESCE(SUM(total_xp), 0) FROM gamify_user_stats")->fetchColumn();
    $avgLevel = round((float) $db->query("SELECT COALESCE(AVG(level), 1) FROM gamify_user_stats")->fetchColumn(), 1);
    $topStreak = (int) $db->query("SELECT COALESCE(MAX(longest_streak), 0) FROM gamify_user_stats")->fetchColumn();
    $activeToday = (int) $db->query("SELECT COUNT(*) FROM gamify_user_stats WHERE last_activity_date = CURDATE()")->fetchColumn();
    $totalBadges = (int) $db->query("SELECT COUNT(*) FROM gamify_user_achievements")->fetchColumn();

    jsonResponse([
        'success' => true,
        'stats' => [
            'total_users' => $totalUsers,
            'total_xp_awarded' => $totalXP,
            'average_level' => $avgLevel,
            'top_streak' => $topStreak,
            'active_today' => $activeToday,
            'total_badges_earned' => $totalBadges,
        ],
    ]);
}
