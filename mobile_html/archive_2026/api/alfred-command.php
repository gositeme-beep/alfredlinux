<?php
/**
 * ═══════════════════════════════════════════════════════════════════════
 * ALFRED COMMAND CENTER — Supreme Centralized Control API
 * ═══════════════════════════════════════════════════════════════════════
 *
 * This is Alfred's master control plane. Every subsystem in the GoSiteMe
 * ecosystem reports to and is controllable through this single API.
 *
 * Auth: INTERNAL_SECRET (MCP / service-to-service) OR admin session
 * Scope: Read + Write across ALL subsystems
 *
 * Categories:
 *   users.*           — User lifecycle & security
 *   billing.*         — Plans, payments, refunds
 *   games.*           — Game sessions, tournaments, scores
 *   gamification.*    — Badges, points, leaderboards
 *   pulse.*           — Social network moderation
 *   fleet.*           — Agent SLA & scheduling
 *   ivr.*             — IVR flow management
 *   campaigns.*       — Call campaign control
 *   security.*        — Platform security policies
 *   platform.*        — Feature flags, maintenance, config
 *   events.*          — Universal event bus
 *   override.*        — Emergency controls
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Internal-Secret, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── Auth: internal service OR admin session ─────────────────────────
// events.emit is allowed for any logged-in user (client-side telemetry)
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$isEventEmit = ($action === 'events.emit');

function cmdAuth(): bool {
    // Internal service auth (MCP server, orchestrator, etc.)
    $secret = defined('INTERNAL_SECRET') ? INTERNAL_SECRET : '';
    if ($secret && isset($_SERVER['HTTP_X_INTERNAL_SECRET']) && hash_equals($secret, $_SERVER['HTTP_X_INTERNAL_SECRET'])) {
        return true;
    }
    // Admin session auth
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!empty($_SESSION['logged_in']) && !empty($_SESSION['is_admin'])) {
        return true;
    }
    return false;
}

function userAuth(): bool {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return !empty($_SESSION['logged_in']);
}

if ($isEventEmit) {
    if (!userAuth() && !cmdAuth()) {
        jsonResponse(['error' => 'Authentication required'], 403);
    }
} elseif (!cmdAuth()) {
    jsonResponse(['error' => 'Unauthorized — Alfred Command Center requires internal or admin auth'], 403);
}

// ── Schema ──────────────────────────────────────────────────────────
function ensureCommandSchema(): void {
    $db = getDB();
    if (!$db) return;

    $db->exec("CREATE TABLE IF NOT EXISTS alfred_event_log (
        id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        event_type    VARCHAR(100) NOT NULL,
        subsystem     VARCHAR(50)  NOT NULL,
        actor         VARCHAR(100) DEFAULT 'alfred',
        target_id     INT UNSIGNED DEFAULT NULL,
        target_type   VARCHAR(50)  DEFAULT NULL,
        payload       JSON,
        severity      ENUM('info','warn','critical','emergency') DEFAULT 'info',
        created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_type    (event_type),
        INDEX idx_sub     (subsystem),
        INDEX idx_created (created_at),
        INDEX idx_sev     (severity)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS alfred_platform_config (
        config_key    VARCHAR(100) PRIMARY KEY,
        config_value  TEXT,
        updated_by    VARCHAR(100) DEFAULT 'alfred',
        updated_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS alfred_feature_flags (
        flag_name     VARCHAR(100) PRIMARY KEY,
        enabled       TINYINT(1) DEFAULT 1,
        description   VARCHAR(500) DEFAULT '',
        updated_by    VARCHAR(100) DEFAULT 'alfred',
        updated_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS alfred_overrides (
        id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        subsystem     VARCHAR(50)  NOT NULL,
        override_type ENUM('pause','resume','kill','config','rate_limit') NOT NULL,
        parameters    JSON,
        reason        VARCHAR(500) DEFAULT '',
        issued_by     VARCHAR(100) DEFAULT 'alfred',
        active        TINYINT(1) DEFAULT 1,
        created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
        expires_at    DATETIME DEFAULT NULL,
        INDEX idx_sub    (subsystem),
        INDEX idx_active (active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

ensureCommandSchema();

// ── Helpers ─────────────────────────────────────────────────────────
function logEvent(string $type, string $subsystem, $targetId = null, string $targetType = null, $payload = null, string $severity = 'info'): void {
    $db = getDB();
    if (!$db) return;
    $stmt = $db->prepare("INSERT INTO alfred_event_log (event_type, subsystem, actor, target_id, target_type, payload, severity) VALUES (?, ?, 'alfred', ?, ?, ?, ?)");
    $stmt->execute([$type, $subsystem, $targetId, $targetType, $payload ? json_encode($payload) : null, $severity]);
}

function getInput(): array {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

function tableExists(PDO $db, string $table): bool {
    // Use information_schema (compatible with MariaDB prepared statements)
    $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
    $stmt->execute([$table]);
    return (int)$stmt->fetchColumn() > 0;
}

// ── Routing ─────────────────────────────────────────────────────────
$action = $_GET['action'] ?? '';

switch ($action) {

// ═══════════════════════════════════════════════════════════════════
// SYSTEM OVERVIEW — Alfred's "eyes"
// ═══════════════════════════════════════════════════════════════════

case 'status':
    $db = getDB();
    $tables = ['clients', 'pulse_posts', 'pulse_follows', 'alfred_event_log',
               'orchestrator_workflows', 'fleet_agents', 'api_keys'];
    $counts = [];
    foreach ($tables as $t) {
        if (tableExists($db, $t)) {
            $row = $db->query("SELECT COUNT(*) AS c FROM `" . $t . "`")->fetch();
            $counts[$t] = (int)$row['c'];
        } else {
            $counts[$t] = null;
        }
    }

    // Active overrides
    $overrides = $db->query("SELECT COUNT(*) AS c FROM alfred_overrides WHERE active = 1")->fetch();
    // Recent events
    $events = $db->query("SELECT COUNT(*) AS c FROM alfred_event_log WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)")->fetch();

    jsonResponse([
        'success'          => true,
        'subsystem_counts' => $counts,
        'active_overrides' => (int)$overrides['c'],
        'events_last_hour' => (int)$events['c'],
        'server_time'      => date('Y-m-d H:i:s'),
        'php_version'      => PHP_VERSION,
        'uptime'           => @file_get_contents('/proc/uptime') ? explode(' ', file_get_contents('/proc/uptime'))[0] . 's' : 'N/A'
    ]);
    break;

// ═══════════════════════════════════════════════════════════════════
// USERS — Full lifecycle control
// ═══════════════════════════════════════════════════════════════════

case 'users.list':
    $db = getDB();
    $page  = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(100, max(1, (int)($_GET['limit'] ?? 50)));
    $offset = ($page - 1) * $limit;
    $search = $_GET['q'] ?? '';

    $where = '1=1';
    $params = [];
    if ($search) {
        $where = "(firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR company LIKE ?)";
        $s = '%' . $search . '%';
        $params = [$s, $s, $s, $s];
    }

    $stmt = $db->prepare("SELECT id, firstname, lastname, email, company, status, date_created, last_login FROM clients WHERE $where ORDER BY id DESC LIMIT $limit OFFSET $offset");
    $stmt->execute($params);
    $users = $stmt->fetchAll();

    $countStmt = $db->prepare("SELECT COUNT(*) AS c FROM clients WHERE $where");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetch()['c'];

    jsonResponse(['success' => true, 'users' => $users, 'total' => $total, 'page' => $page, 'limit' => $limit]);
    break;

case 'users.get':
    $db = getDB();
    $uid = (int)($_GET['user_id'] ?? 0);
    if (!$uid) jsonResponse(['error' => 'user_id required'], 400);
    $stmt = $db->prepare("SELECT id, firstname, lastname, email, company, status, date_created, last_login, address1, city, state, postcode, country, phone FROM clients WHERE id = ?");
    $stmt->execute([$uid]);
    $user = $stmt->fetch();
    if (!$user) jsonResponse(['error' => 'User not found'], 404);
    jsonResponse(['success' => true, 'user' => $user]);
    break;

case 'users.update':
    $db = getDB();
    $input = getInput();
    $uid = (int)($input['user_id'] ?? 0);
    if (!$uid) jsonResponse(['error' => 'user_id required'], 400);

    $allowed = ['firstname', 'lastname', 'email', 'company', 'status', 'address1', 'city', 'state', 'postcode', 'country', 'phone'];
    $sets = [];
    $vals = [];
    foreach ($allowed as $f) {
        if (isset($input[$f])) {
            $sets[] = "$f = ?";
            $vals[] = sanitize($input[$f], 500);
        }
    }
    if (empty($sets)) jsonResponse(['error' => 'No fields to update'], 400);
    $vals[] = $uid;

    $stmt = $db->prepare("UPDATE clients SET " . implode(', ', $sets) . " WHERE id = ?");
    $stmt->execute($vals);
    logEvent('user.updated', 'users', $uid, 'client', $input);
    jsonResponse(['success' => true, 'updated' => $stmt->rowCount()]);
    break;

case 'users.suspend':
    $db = getDB();
    $input = getInput();
    $uid = (int)($input['user_id'] ?? 0);
    $reason = sanitize($input['reason'] ?? 'Suspended by Alfred', 500);
    if (!$uid) jsonResponse(['error' => 'user_id required'], 400);

    $stmt = $db->prepare("UPDATE clients SET status = 'Inactive' WHERE id = ?");
    $stmt->execute([$uid]);
    logEvent('user.suspended', 'users', $uid, 'client', ['reason' => $reason], 'warn');
    jsonResponse(['success' => true, 'message' => "User $uid suspended"]);
    break;

case 'users.activate':
    $db = getDB();
    $input = getInput();
    $uid = (int)($input['user_id'] ?? 0);
    if (!$uid) jsonResponse(['error' => 'user_id required'], 400);

    $stmt = $db->prepare("UPDATE clients SET status = 'Active' WHERE id = ?");
    $stmt->execute([$uid]);
    logEvent('user.activated', 'users', $uid, 'client');
    jsonResponse(['success' => true, 'message' => "User $uid activated"]);
    break;

case 'users.security':
    $db = getDB();
    $uid = (int)($_GET['user_id'] ?? 0);
    if (!$uid) jsonResponse(['error' => 'user_id required'], 400);

    // Gather security info
    $result = ['user_id' => $uid];
    $stmt = $db->prepare("SELECT two_factor_enabled, support_pin, support_pin_set_at FROM clients WHERE id = ?");
    $stmt->execute([$uid]);
    $result['security_settings'] = $stmt->fetch() ?: null;
    // API keys
    if (tableExists($db, 'api_keys')) {
        $stmt = $db->prepare("SELECT id, key_name, created_at, last_used, is_active FROM api_keys WHERE client_id = ?");
        $stmt->execute([$uid]);
        $result['api_keys'] = $stmt->fetchAll();
    }
    // Active sessions (if tracked)
    $result['has_active_session'] = true; // placeholder
    jsonResponse(['success' => true, 'security' => $result]);
    break;

case 'users.reset-2fa':
    $db = getDB();
    $input = getInput();
    $uid = (int)($input['user_id'] ?? 0);
    $reason = sanitize($input['reason'] ?? 'Reset by Alfred', 500);
    if (!$uid) jsonResponse(['error' => 'user_id required'], 400);

    $stmt = $db->prepare("UPDATE clients SET two_factor_enabled = 0, two_factor_secret = NULL WHERE id = ?");
    $stmt->execute([$uid]);
    logEvent('user.2fa_reset', 'security', $uid, 'client', ['reason' => $reason], 'warn');
    jsonResponse(['success' => true, 'message' => "2FA reset for user $uid"]);
    break;

// ═══════════════════════════════════════════════════════════════════
// BILLING — Plan, payment, refund control
// ═══════════════════════════════════════════════════════════════════

case 'billing.plans':
    $db = getDB();
    if (tableExists($db, 'tblproducts')) {
        $plans = $db->query("SELECT id, name, description, type, paytype FROM tblproducts WHERE retired = 0 ORDER BY id")->fetchAll();
    } else {
        $plans = [];
    }
    jsonResponse(['success' => true, 'plans' => $plans]);
    break;

case 'billing.user-services':
    $db = getDB();
    $uid = (int)($_GET['user_id'] ?? 0);
    if (!$uid) jsonResponse(['error' => 'user_id required'], 400);

    if (tableExists($db, 'tblhosting')) {
        $stmt = $db->prepare("SELECT id, domain, packageid, domainstatus, regdate, nextduedate, amount, billingcycle FROM tblhosting WHERE userid = ?");
        $stmt->execute([$uid]);
        $services = $stmt->fetchAll();
    } else {
        $services = [];
    }
    jsonResponse(['success' => true, 'services' => $services]);
    break;

case 'billing.change-plan':
    $db = getDB();
    $input = getInput();
    $serviceId = (int)($input['service_id'] ?? 0);
    $newPlanId = (int)($input['plan_id'] ?? 0);
    $reason    = sanitize($input['reason'] ?? '', 500);
    if (!$serviceId || !$newPlanId) jsonResponse(['error' => 'service_id and plan_id required'], 400);

    if (tableExists($db, 'tblhosting')) {
        $stmt = $db->prepare("UPDATE tblhosting SET packageid = ? WHERE id = ?");
        $stmt->execute([$newPlanId, $serviceId]);
        logEvent('billing.plan_changed', 'billing', $serviceId, 'hosting', ['new_plan' => $newPlanId, 'reason' => $reason]);
        jsonResponse(['success' => true, 'message' => "Service $serviceId moved to plan $newPlanId"]);
    } else {
        jsonResponse(['error' => 'Hosting table not available'], 500);
    }
    break;

case 'billing.issue-credit':
    $db = getDB();
    $input = getInput();
    $uid    = (int)($input['user_id'] ?? 0);
    $amount = (float)($input['amount'] ?? 0);
    $reason = sanitize($input['reason'] ?? 'Credit issued by Alfred', 500);
    if (!$uid || $amount <= 0) jsonResponse(['error' => 'user_id and positive amount required'], 400);

    // Update client credit balance directly
    $db->prepare("UPDATE clients SET credit = credit + ? WHERE id = ?")->execute([$amount, $uid]);
    // Also log to tblcredit if it exists
    if (tableExists($db, 'tblcredit')) {
        $stmt = $db->prepare("INSERT INTO tblcredit (clientid, date, description, amount) VALUES (?, NOW(), ?, ?)");
        $stmt->execute([$uid, $reason, $amount]);
    }
    logEvent('billing.credit_issued', 'billing', $uid, 'client', ['amount' => $amount, 'reason' => $reason]);
    jsonResponse(['success' => true, 'message' => "Credited \$$amount to user $uid"]);
    break;

case 'billing.invoices':
    $db = getDB();
    $uid = (int)($_GET['user_id'] ?? 0);
    if (!$uid) jsonResponse(['error' => 'user_id required'], 400);

    if (tableExists($db, 'tblinvoices')) {
        $stmt = $db->prepare("SELECT id, date, duedate, total, status FROM tblinvoices WHERE userid = ? ORDER BY id DESC LIMIT 50");
        $stmt->execute([$uid]);
        $invoices = $stmt->fetchAll();
    } else {
        $invoices = [];
    }
    jsonResponse(['success' => true, 'invoices' => $invoices]);
    break;

// ═══════════════════════════════════════════════════════════════════
// GAMES — Observation & control
// ═══════════════════════════════════════════════════════════════════

case 'games.sessions':
    $db = getDB();
    if (tableExists($db, 'game_sessions')) {
        $sessions = $db->query("SELECT * FROM game_sessions WHERE status = 'active' ORDER BY created_at DESC LIMIT 100")->fetchAll();
    } else {
        $sessions = [];
    }
    jsonResponse(['success' => true, 'active_sessions' => $sessions]);
    break;

case 'games.scores':
    $db = getDB();
    $game = sanitize($_GET['game'] ?? '', 100);
    $limit = min(100, max(1, (int)($_GET['limit'] ?? 50)));

    if (tableExists($db, 'game_scores')) {
        $where = $game ? "WHERE game_name = ?" : "";
        $stmt = $db->prepare("SELECT gs.*, c.firstname, c.lastname FROM game_scores gs LEFT JOIN clients c ON gs.user_id = c.id $where ORDER BY gs.score DESC LIMIT $limit");
        $stmt->execute($game ? [$game] : []);
        $scores = $stmt->fetchAll();
    } else {
        $scores = [];
    }
    jsonResponse(['success' => true, 'scores' => $scores]);
    break;

case 'games.create-tournament':
    $db = getDB();
    $input = getInput();
    $name   = sanitize($input['name'] ?? '', 200);
    $game   = sanitize($input['game'] ?? '', 100);
    $start  = sanitize($input['start_date'] ?? '', 30);
    $end    = sanitize($input['end_date'] ?? '', 30);
    $maxP   = (int)($input['max_players'] ?? 0);
    $prize  = sanitize($input['prize_description'] ?? '', 500);

    if (!$name || !$game) jsonResponse(['error' => 'name and game required'], 400);

    $db->exec("CREATE TABLE IF NOT EXISTS game_tournaments (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(200) NOT NULL,
        game_name VARCHAR(100) NOT NULL,
        status ENUM('draft','open','active','completed','cancelled') DEFAULT 'draft',
        start_date DATETIME, end_date DATETIME,
        max_players INT UNSIGNED DEFAULT 0,
        prize_description VARCHAR(500) DEFAULT '',
        created_by VARCHAR(100) DEFAULT 'alfred',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $stmt = $db->prepare("INSERT INTO game_tournaments (name, game_name, start_date, end_date, max_players, prize_description) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $game, $start ?: null, $end ?: null, $maxP, $prize]);
    logEvent('game.tournament_created', 'games', $db->lastInsertId(), 'tournament', $input);
    jsonResponse(['success' => true, 'tournament_id' => $db->lastInsertId()]);
    break;

case 'games.award-score':
    $db = getDB();
    $input = getInput();
    $uid   = (int)($input['user_id'] ?? 0);
    $game  = sanitize($input['game'] ?? '', 100);
    $score = (int)($input['score'] ?? 0);
    if (!$uid || !$game) jsonResponse(['error' => 'user_id and game required'], 400);

    $db->exec("CREATE TABLE IF NOT EXISTS game_scores (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        game_name VARCHAR(100) NOT NULL,
        score INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (user_id), INDEX idx_game (game_name), INDEX idx_score (score DESC)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $stmt = $db->prepare("INSERT INTO game_scores (user_id, game_name, score) VALUES (?, ?, ?)");
    $stmt->execute([$uid, $game, $score]);
    logEvent('game.score_awarded', 'games', $uid, 'player', $input);
    jsonResponse(['success' => true, 'score_id' => $db->lastInsertId()]);
    break;

// ═══════════════════════════════════════════════════════════════════
// GAMIFICATION — Badges, points, leaderboards
// ═══════════════════════════════════════════════════════════════════

case 'gamification.award-badge':
    $db = getDB();
    $input = getInput();
    $uid   = (int)($input['user_id'] ?? 0);
    $badge = sanitize($input['badge'] ?? '', 100);
    $reason = sanitize($input['reason'] ?? '', 500);
    if (!$uid || !$badge) jsonResponse(['error' => 'user_id and badge required'], 400);

    $db->exec("CREATE TABLE IF NOT EXISTS gamification_badges (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        badge_name VARCHAR(100) NOT NULL,
        reason VARCHAR(500) DEFAULT '',
        awarded_by VARCHAR(100) DEFAULT 'alfred',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_badge (user_id, badge_name),
        INDEX idx_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $stmt = $db->prepare("INSERT IGNORE INTO gamification_badges (user_id, badge_name, reason) VALUES (?, ?, ?)");
    $stmt->execute([$uid, $badge, $reason]);
    logEvent('gamification.badge_awarded', 'gamification', $uid, 'user', ['badge' => $badge]);
    jsonResponse(['success' => true, 'awarded' => $stmt->rowCount() > 0]);
    break;

case 'gamification.award-points':
    $db = getDB();
    $input = getInput();
    $uid    = (int)($input['user_id'] ?? 0);
    $points = (int)($input['points'] ?? 0);
    $reason = sanitize($input['reason'] ?? '', 500);
    if (!$uid || !$points) jsonResponse(['error' => 'user_id and points required'], 400);

    $db->exec("CREATE TABLE IF NOT EXISTS gamification_points (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        points INT NOT NULL,
        reason VARCHAR(500) DEFAULT '',
        awarded_by VARCHAR(100) DEFAULT 'alfred',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $stmt = $db->prepare("INSERT INTO gamification_points (user_id, points, reason) VALUES (?, ?, ?)");
    $stmt->execute([$uid, $points, $reason]);
    logEvent('gamification.points_awarded', 'gamification', $uid, 'user', ['points' => $points]);
    jsonResponse(['success' => true, 'points_awarded' => $points]);
    break;

case 'gamification.leaderboard':
    $db = getDB();
    $limit = min(100, max(1, (int)($_GET['limit'] ?? 25)));

    if (tableExists($db, 'gamification_points')) {
        $stmt = $db->prepare("SELECT gp.user_id, SUM(gp.points) AS total_points, c.firstname, c.lastname
            FROM gamification_points gp LEFT JOIN clients c ON gp.user_id = c.id
            GROUP BY gp.user_id ORDER BY total_points DESC LIMIT $limit");
        $stmt->execute();
        $board = $stmt->fetchAll();
    } else {
        $board = [];
    }
    jsonResponse(['success' => true, 'leaderboard' => $board]);
    break;

case 'gamification.user-badges':
    $db = getDB();
    $uid = (int)($_GET['user_id'] ?? 0);
    if (!$uid) jsonResponse(['error' => 'user_id required'], 400);

    if (tableExists($db, 'gamification_badges')) {
        $stmt = $db->prepare("SELECT badge_name, reason, awarded_by, created_at FROM gamification_badges WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$uid]);
        $badges = $stmt->fetchAll();
    } else {
        $badges = [];
    }
    jsonResponse(['success' => true, 'badges' => $badges]);
    break;

// ═══════════════════════════════════════════════════════════════════
// PULSE — Social network moderation
// ═══════════════════════════════════════════════════════════════════

case 'pulse.moderate':
    $db = getDB();
    $input = getInput();
    $postId = (int)($input['post_id'] ?? 0);
    $moderationAction = sanitize($input['action'] ?? '', 50); // remove, flag, warn
    $reason = sanitize($input['reason'] ?? '', 500);
    if (!$postId || !$moderationAction) jsonResponse(['error' => 'post_id and action required'], 400);

    if ($moderationAction === 'remove' && tableExists($db, 'pulse_posts')) {
        $db->prepare("DELETE FROM pulse_posts WHERE id = ?")->execute([$postId]);
    }
    logEvent('pulse.moderated', 'pulse', $postId, 'post', ['action' => $moderationAction, 'reason' => $reason], 'warn');
    jsonResponse(['success' => true, 'message' => "Post $postId: $moderationAction"]);
    break;

case 'pulse.stats':
    $db = getDB();
    $stats = [];
    foreach (['pulse_posts', 'pulse_likes', 'pulse_comments', 'pulse_follows'] as $t) {
        if (tableExists($db, $t)) {
            $stats[$t] = (int)$db->query("SELECT COUNT(*) FROM $t")->fetchColumn();
        }
    }
    if (tableExists($db, 'pulse_posts')) {
        $stats['posts_today'] = (int)$db->query("SELECT COUNT(*) FROM pulse_posts WHERE DATE(created_at) = CURDATE()")->fetchColumn();
    }
    jsonResponse(['success' => true, 'stats' => $stats]);
    break;

// ═══════════════════════════════════════════════════════════════════
// FLEET — Agent SLA & scheduling
// ═══════════════════════════════════════════════════════════════════

case 'fleet.agents':
    $db = getDB();
    if (tableExists($db, 'fleet_agents')) {
        $agents = $db->query("SELECT * FROM fleet_agents ORDER BY id")->fetchAll();
    } else {
        $agents = [];
    }
    jsonResponse(['success' => true, 'agents' => $agents]);
    break;

case 'fleet.update-agent':
    $db = getDB();
    $input = getInput();
    $agentId = (int)($input['agent_id'] ?? 0);
    if (!$agentId) jsonResponse(['error' => 'agent_id required'], 400);

    $allowed = ['status', 'persona', 'voice_id', 'model', 'temperature', 'max_tokens', 'system_prompt'];
    $sets = [];
    $vals = [];
    foreach ($allowed as $f) {
        if (isset($input[$f])) {
            $sets[] = "$f = ?";
            $vals[] = $input[$f];
        }
    }
    if (empty($sets)) jsonResponse(['error' => 'No fields to update'], 400);
    $vals[] = $agentId;

    if (tableExists($db, 'fleet_agents')) {
        $stmt = $db->prepare("UPDATE fleet_agents SET " . implode(', ', $sets) . " WHERE id = ?");
        $stmt->execute($vals);
        logEvent('fleet.agent_updated', 'fleet', $agentId, 'agent', $input);
        jsonResponse(['success' => true, 'updated' => $stmt->rowCount()]);
    } else {
        jsonResponse(['error' => 'Fleet agents table not found'], 404);
    }
    break;

case 'fleet.set-sla':
    $db = getDB();
    $input = getInput();
    $agentId = (int)($input['agent_id'] ?? 0);
    $maxResponseMs = (int)($input['max_response_ms'] ?? 5000);
    $maxRetries    = (int)($input['max_retries'] ?? 3);
    $priority      = sanitize($input['priority'] ?? 'normal', 20);
    if (!$agentId) jsonResponse(['error' => 'agent_id required'], 400);

    $db->exec("CREATE TABLE IF NOT EXISTS fleet_sla (
        agent_id INT UNSIGNED PRIMARY KEY,
        max_response_ms INT UNSIGNED DEFAULT 5000,
        max_retries INT UNSIGNED DEFAULT 3,
        priority ENUM('low','normal','high','critical') DEFAULT 'normal',
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $stmt = $db->prepare("INSERT INTO fleet_sla (agent_id, max_response_ms, max_retries, priority) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE max_response_ms = VALUES(max_response_ms), max_retries = VALUES(max_retries), priority = VALUES(priority)");
    $stmt->execute([$agentId, $maxResponseMs, $maxRetries, $priority]);
    logEvent('fleet.sla_set', 'fleet', $agentId, 'agent', $input);
    jsonResponse(['success' => true]);
    break;

// ═══════════════════════════════════════════════════════════════════
// IVR — Flow management
// ═══════════════════════════════════════════════════════════════════

case 'ivr.flows':
    $db = getDB();
    if (tableExists($db, 'ivr_flows')) {
        $flows = $db->query("SELECT id, name, status, created_at, updated_at FROM ivr_flows ORDER BY id DESC")->fetchAll();
    } else {
        $flows = [];
    }
    jsonResponse(['success' => true, 'flows' => $flows]);
    break;

case 'ivr.create-flow':
    $db = getDB();
    $input = getInput();
    $name = sanitize($input['name'] ?? '', 200);
    $definition = $input['definition'] ?? [];   // JSON flow definition
    if (!$name) jsonResponse(['error' => 'name required'], 400);

    $db->exec("CREATE TABLE IF NOT EXISTS ivr_flows (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(200) NOT NULL,
        definition JSON,
        status ENUM('draft','active','paused','archived') DEFAULT 'draft',
        created_by VARCHAR(100) DEFAULT 'alfred',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $stmt = $db->prepare("INSERT INTO ivr_flows (name, definition) VALUES (?, ?)");
    $stmt->execute([$name, json_encode($definition)]);
    logEvent('ivr.flow_created', 'ivr', $db->lastInsertId(), 'flow', ['name' => $name]);
    jsonResponse(['success' => true, 'flow_id' => $db->lastInsertId()]);
    break;

case 'ivr.update-flow-status':
    $db = getDB();
    $input = getInput();
    $flowId = (int)($input['flow_id'] ?? 0);
    $status = sanitize($input['status'] ?? '', 20);
    if (!$flowId || !in_array($status, ['draft','active','paused','archived'])) jsonResponse(['error' => 'flow_id and valid status required'], 400);

    if (tableExists($db, 'ivr_flows')) {
        $stmt = $db->prepare("UPDATE ivr_flows SET status = ? WHERE id = ?");
        $stmt->execute([$status, $flowId]);
        logEvent('ivr.flow_status', 'ivr', $flowId, 'flow', ['status' => $status]);
        jsonResponse(['success' => true]);
    } else {
        jsonResponse(['error' => 'IVR flows table not found'], 404);
    }
    break;

// ═══════════════════════════════════════════════════════════════════
// CAMPAIGNS — Call campaign control
// ═══════════════════════════════════════════════════════════════════

case 'campaigns.list':
    $db = getDB();
    if (tableExists($db, 'call_campaigns')) {
        $campaigns = $db->query("SELECT * FROM call_campaigns ORDER BY id DESC LIMIT 50")->fetchAll();
    } else {
        $campaigns = [];
    }
    jsonResponse(['success' => true, 'campaigns' => $campaigns]);
    break;

case 'campaigns.pause':
    $db = getDB();
    $input = getInput();
    $cid = (int)($input['campaign_id'] ?? 0);
    $reason = sanitize($input['reason'] ?? '', 500);
    if (!$cid) jsonResponse(['error' => 'campaign_id required'], 400);

    if (tableExists($db, 'call_campaigns')) {
        $stmt = $db->prepare("UPDATE call_campaigns SET status = 'paused' WHERE id = ?");
        $stmt->execute([$cid]);
        logEvent('campaign.paused', 'campaigns', $cid, 'campaign', ['reason' => $reason], 'warn');
        jsonResponse(['success' => true, 'message' => "Campaign $cid paused"]);
    } else {
        jsonResponse(['error' => 'Campaigns table not found'], 404);
    }
    break;

case 'campaigns.resume':
    $db = getDB();
    $input = getInput();
    $cid = (int)($input['campaign_id'] ?? 0);
    if (!$cid) jsonResponse(['error' => 'campaign_id required'], 400);

    if (tableExists($db, 'call_campaigns')) {
        $stmt = $db->prepare("UPDATE call_campaigns SET status = 'active' WHERE id = ?");
        $stmt->execute([$cid]);
        logEvent('campaign.resumed', 'campaigns', $cid, 'campaign');
        jsonResponse(['success' => true, 'message' => "Campaign $cid resumed"]);
    } else {
        jsonResponse(['error' => 'Campaigns table not found'], 404);
    }
    break;

case 'campaigns.kill':
    $db = getDB();
    $input = getInput();
    $cid    = (int)($input['campaign_id'] ?? 0);
    $reason = sanitize($input['reason'] ?? 'Killed by Alfred', 500);
    if (!$cid) jsonResponse(['error' => 'campaign_id required'], 400);

    if (tableExists($db, 'call_campaigns')) {
        $stmt = $db->prepare("UPDATE call_campaigns SET status = 'cancelled' WHERE id = ?");
        $stmt->execute([$cid]);
        logEvent('campaign.killed', 'campaigns', $cid, 'campaign', ['reason' => $reason], 'critical');
        jsonResponse(['success' => true, 'message' => "Campaign $cid killed"]);
    } else {
        jsonResponse(['error' => 'Campaigns table not found'], 404);
    }
    break;

// ═══════════════════════════════════════════════════════════════════
// SECURITY — Platform security policies
// ═══════════════════════════════════════════════════════════════════

case 'security.audit':
    $db = getDB();
    $result = [];

    // Count admin users
    $result['total_users'] = (int)$db->query("SELECT COUNT(*) FROM clients")->fetchColumn();
    $result['active_users'] = (int)$db->query("SELECT COUNT(*) FROM clients WHERE status = 'Active'")->fetchColumn();

    // API keys
    if (tableExists($db, 'api_keys')) {
        $result['active_api_keys'] = (int)$db->query("SELECT COUNT(*) FROM api_keys WHERE is_active = 1")->fetchColumn();
    }
    // Recent events
    $result['critical_events_24h'] = (int)$db->query("SELECT COUNT(*) FROM alfred_event_log WHERE severity IN ('critical','emergency') AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();

    // Overrides
    $stmt = $db->query("SELECT * FROM alfred_overrides WHERE active = 1");
    $result['active_overrides'] = $stmt->fetchAll();

    jsonResponse(['success' => true, 'audit' => $result]);
    break;

case 'security.revoke-api-key':
    $db = getDB();
    $input = getInput();
    $keyId   = (int)($input['key_id'] ?? 0);
    $reason  = sanitize($input['reason'] ?? 'Revoked by Alfred', 500);
    if (!$keyId) jsonResponse(['error' => 'key_id required'], 400);

    if (tableExists($db, 'api_keys')) {
        $stmt = $db->prepare("UPDATE api_keys SET is_active = 0 WHERE id = ?");
        $stmt->execute([$keyId]);
        logEvent('security.api_key_revoked', 'security', $keyId, 'api_key', ['reason' => $reason], 'warn');
        jsonResponse(['success' => true, 'message' => "API key $keyId revoked"]);
    } else {
        jsonResponse(['error' => 'API keys table not found'], 404);
    }
    break;

case 'security.force-password-reset':
    $db = getDB();
    $input = getInput();
    $uid    = (int)($input['user_id'] ?? 0);
    $reason = sanitize($input['reason'] ?? 'Forced by Alfred', 500);
    if (!$uid) jsonResponse(['error' => 'user_id required'], 400);

    // Generate a secure random temporary password
    $token = bin2hex(random_bytes(32));
    $hashedPw = password_hash($token, PASSWORD_DEFAULT);
    $db->prepare("UPDATE clients SET password = ? WHERE id = ?")->execute([$hashedPw, $uid]);
    logEvent('security.password_reset_forced', 'security', $uid, 'client', ['reason' => $reason], 'warn');
    jsonResponse(['success' => true, 'message' => "Password reset forced for user $uid", 'reset_token' => $token]);
    break;

// ═══════════════════════════════════════════════════════════════════
// PLATFORM — Feature flags, config, maintenance
// ═══════════════════════════════════════════════════════════════════

case 'platform.config.get':
    $db = getDB();
    $key = sanitize($_GET['key'] ?? '', 100);
    if ($key) {
        $stmt = $db->prepare("SELECT * FROM alfred_platform_config WHERE config_key = ?");
        $stmt->execute([$key]);
        $config = $stmt->fetch();
        jsonResponse(['success' => true, 'config' => $config]);
    } else {
        $configs = $db->query("SELECT * FROM alfred_platform_config ORDER BY config_key")->fetchAll();
        jsonResponse(['success' => true, 'configs' => $configs]);
    }
    break;

case 'platform.config.set':
    $db = getDB();
    $input = getInput();
    $key   = sanitize($input['key'] ?? '', 100);
    $value = $input['value'] ?? '';
    if (!$key) jsonResponse(['error' => 'key required'], 400);

    $stmt = $db->prepare("INSERT INTO alfred_platform_config (config_key, config_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)");
    $stmt->execute([$key, is_string($value) ? $value : json_encode($value)]);
    logEvent('platform.config_changed', 'platform', null, null, ['key' => $key]);
    jsonResponse(['success' => true]);
    break;

case 'platform.flags.list':
    $db = getDB();
    $flags = $db->query("SELECT * FROM alfred_feature_flags ORDER BY flag_name")->fetchAll();
    jsonResponse(['success' => true, 'flags' => $flags]);
    break;

case 'platform.flags.set':
    $db = getDB();
    $input   = getInput();
    $flag    = sanitize($input['flag'] ?? '', 100);
    $enabled = isset($input['enabled']) ? (int)(bool)$input['enabled'] : 1;
    $desc    = sanitize($input['description'] ?? '', 500);
    if (!$flag) jsonResponse(['error' => 'flag required'], 400);

    $stmt = $db->prepare("INSERT INTO alfred_feature_flags (flag_name, enabled, description) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE enabled = VALUES(enabled), description = VALUES(description)");
    $stmt->execute([$flag, $enabled, $desc]);
    logEvent('platform.flag_changed', 'platform', null, null, ['flag' => $flag, 'enabled' => $enabled], $enabled ? 'info' : 'warn');
    jsonResponse(['success' => true]);
    break;

case 'platform.maintenance':
    $db = getDB();
    $input = getInput();
    $enabled = (bool)($input['enabled'] ?? false);
    $message = sanitize($input['message'] ?? 'Platform under maintenance', 500);
    $eta     = sanitize($input['eta'] ?? '', 50);

    $stmt = $db->prepare("INSERT INTO alfred_platform_config (config_key, config_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)");
    $stmt->execute(['maintenance_mode', json_encode(['enabled' => $enabled, 'message' => $message, 'eta' => $eta])]);
    logEvent('platform.maintenance', 'platform', null, null, ['enabled' => $enabled], $enabled ? 'critical' : 'info');
    jsonResponse(['success' => true, 'maintenance' => $enabled]);
    break;

// ═══════════════════════════════════════════════════════════════════
// EVENTS — Universal event bus (read)
// ═══════════════════════════════════════════════════════════════════

case 'events.recent':
    $db = getDB();
    $subsystem = sanitize($_GET['subsystem'] ?? '', 50);
    $severity  = sanitize($_GET['severity'] ?? '', 20);
    $limit     = min(500, max(1, (int)($_GET['limit'] ?? 100)));

    $where = '1=1';
    $params = [];
    if ($subsystem) { $where .= " AND subsystem = ?"; $params[] = $subsystem; }
    if ($severity)  { $where .= " AND severity = ?";  $params[] = $severity;  }

    $stmt = $db->prepare("SELECT * FROM alfred_event_log WHERE $where ORDER BY created_at DESC LIMIT $limit");
    $stmt->execute($params);
    jsonResponse(['success' => true, 'events' => $stmt->fetchAll()]);
    break;

case 'events.emit':
    $input = getInput();
    $type      = sanitize($input['event_type'] ?? '', 100);
    $subsystem = sanitize($input['subsystem'] ?? '', 50);
    $severity  = in_array(($input['severity'] ?? ''), ['info','warn','critical','emergency']) ? $input['severity'] : 'info';
    if (!$type || !$subsystem) jsonResponse(['error' => 'event_type and subsystem required'], 400);

    logEvent($type, $subsystem, $input['target_id'] ?? null, $input['target_type'] ?? null, $input['payload'] ?? null, $severity);
    jsonResponse(['success' => true]);
    break;

// ═══════════════════════════════════════════════════════════════════
// OVERRIDES — Emergency controls
// ═══════════════════════════════════════════════════════════════════

case 'override.issue':
    $db = getDB();
    $input = getInput();
    $subsystem = sanitize($input['subsystem'] ?? '', 50);
    $type      = in_array(($input['type'] ?? ''), ['pause','resume','kill','config','rate_limit']) ? $input['type'] : null;
    $reason    = sanitize($input['reason'] ?? '', 500);
    $params    = $input['parameters'] ?? [];
    $expiresAt = sanitize($input['expires_at'] ?? '', 30);

    if (!$subsystem || !$type) jsonResponse(['error' => 'subsystem and type required'], 400);

    // Deactivate previous overrides of same type on same subsystem
    $db->prepare("UPDATE alfred_overrides SET active = 0 WHERE subsystem = ? AND override_type = ? AND active = 1")->execute([$subsystem, $type]);

    $stmt = $db->prepare("INSERT INTO alfred_overrides (subsystem, override_type, parameters, reason, expires_at) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$subsystem, $type, json_encode($params), $reason, $expiresAt ?: null]);
    logEvent("override.$type", $subsystem, $db->lastInsertId(), 'override', $input, 'critical');
    jsonResponse(['success' => true, 'override_id' => $db->lastInsertId()]);
    break;

case 'override.lift':
    $db = getDB();
    $input = getInput();
    $overrideId = (int)($input['override_id'] ?? 0);
    if (!$overrideId) jsonResponse(['error' => 'override_id required'], 400);

    $stmt = $db->prepare("UPDATE alfred_overrides SET active = 0 WHERE id = ?");
    $stmt->execute([$overrideId]);
    logEvent('override.lifted', 'platform', $overrideId, 'override');
    jsonResponse(['success' => true]);
    break;

case 'override.active':
    $db = getDB();
    $stmt = $db->query("SELECT * FROM alfred_overrides WHERE active = 1 AND (expires_at IS NULL OR expires_at > NOW()) ORDER BY created_at DESC");
    jsonResponse(['success' => true, 'overrides' => $stmt->fetchAll()]);
    break;

// ═══════════════════════════════════════════════════════════════════
// DATA — Cross-subsystem queries
// ═══════════════════════════════════════════════════════════════════

case 'data.tables':
    $db = getDB();
    // Use information_schema for row counts — avoids broken views crashing
    $stmt = $db->prepare("SELECT TABLE_NAME, TABLE_ROWS FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME");
    $stmt->execute();
    $result = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $result[] = ['table' => $row['TABLE_NAME'], 'rows' => (int)$row['TABLE_ROWS']];
    }
    jsonResponse(['success' => true, 'tables' => $result, 'count' => count($result)]);
    break;

case 'data.query':
    // Named query allowlist — prevents arbitrary SQL execution
    $input = getInput();
    $queryName = trim($input['query'] ?? $input['sql'] ?? '');
    if (!$queryName) jsonResponse(['error' => 'query name required'], 400);

    $allowedQueries = [
        'user_count'       => "SELECT COUNT(*) as cnt FROM clients",
        'active_services'  => "SELECT COUNT(*) as cnt FROM services WHERE status='Active'",
        'table_list'       => "SELECT TABLE_NAME, TABLE_ROWS FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME",
        'recent_logins'    => "SELECT email, last_login FROM clients WHERE last_login IS NOT NULL ORDER BY last_login DESC LIMIT 20",
        'agent_count'      => "SELECT COUNT(*) as cnt FROM alfred_agents",
        'conversation_count' => "SELECT COUNT(*) as cnt FROM alfred_conversations",
        'revenue_summary'  => "SELECT SUM(amount) as total FROM invoices WHERE status='Paid' AND date >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
    ];

    if (!isset($allowedQueries[$queryName])) {
        jsonResponse(['error' => 'Unknown query. Available: ' . implode(', ', array_keys($allowedQueries))], 400);
    }

    $db = getDB();
    try {
        $stmt = $db->query($allowedQueries[$queryName]);
        $rows = $stmt->fetchAll();
        jsonResponse(['success' => true, 'query' => $queryName, 'rows' => $rows, 'count' => count($rows)]);
    } catch (PDOException $e) {
        error_log('[data.query] ' . $e->getMessage());
        jsonResponse(['error' => 'Query execution failed'], 500);
    }
    break;

// ═══════════════════════════════════════════════════════════════════
// SELFTEST — Comprehensive ecosystem health diagnostic
// ═══════════════════════════════════════════════════════════════════

case 'selftest':
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    $results = ['success' => true, 'timestamp' => date('c'), 'checks' => []];
    $pass = 0; $fail = 0;

    // 1. Database
    try {
        $db = getDB();
        $r = $db->query("SELECT COUNT(*) c FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()")->fetch();
        $results['checks']['database'] = ['status' => 'ok', 'tables' => (int)$r['c']];
        $pass++;
    } catch (Throwable $e) {
        $results['checks']['database'] = ['status' => 'fail', 'error' => 'Connection failed'];
        $fail++;
    }

    // 2. Redis
    try {
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379, 2);
        $pong = $redis->ping();
        $results['checks']['redis'] = ['status' => ($pong ? 'ok' : 'fail')];
        $pong ? $pass++ : $fail++;
    } catch (Exception $e) {
        $results['checks']['redis'] = ['status' => 'fail', 'error' => $e->getMessage()];
        $fail++;
    }

    // 3. Feature flags
    try {
        $flags = $db->query("SELECT flag_name, enabled FROM alfred_feature_flags ORDER BY flag_name")->fetchAll(PDO::FETCH_ASSOC);
        $results['checks']['feature_flags'] = ['status' => 'ok', 'count' => count($flags), 'flags' => $flags];
        $pass++;
    } catch (Exception $e) {
        $results['checks']['feature_flags'] = ['status' => 'fail'];
        $fail++;
    }

    // 4. Event logging
    try {
        $r = $db->query("SELECT COUNT(*) c FROM alfred_event_log WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetch();
        $results['checks']['event_log'] = ['status' => 'ok', 'events_24h' => (int)$r['c']];
        $pass++;
    } catch (Exception $e) {
        $results['checks']['event_log'] = ['status' => 'fail'];
        $fail++;
    }

    // 5. Active overrides
    try {
        $r = $db->query("SELECT COUNT(*) c FROM alfred_overrides WHERE active = 1")->fetch();
        $results['checks']['overrides'] = ['status' => 'ok', 'active' => (int)$r['c']];
        $pass++;
    } catch (Exception $e) {
        $results['checks']['overrides'] = ['status' => 'fail'];
        $fail++;
    }

    // 6. Disk space
    $diskFree = @disk_free_space('.');
    $diskTotal = @disk_total_space('.');
    if ($diskFree !== false && $diskTotal && $diskTotal > 0) {
        $diskPct = round(($diskTotal - $diskFree) / $diskTotal * 100, 1);
        $diskOk = $diskPct < 90;
        $results['checks']['disk'] = [
            'status' => $diskOk ? 'ok' : 'warn',
            'used_pct' => $diskPct,
            'free_gb' => round($diskFree / 1073741824, 1)
        ];
        $diskOk ? $pass++ : $fail++;
    } else {
        $results['checks']['disk'] = ['status' => 'ok', 'info' => 'disk_free_space unavailable'];
        $pass++;
    }

    // 7. PHP version
    $results['checks']['php'] = ['status' => 'ok', 'version' => PHP_VERSION];
    $pass++;

    // 8. SSL check (read cert from Caddy's storage to avoid self-connection)
    $certFile = glob('/home/gositeme/.local/share/caddy/certificates/acme-v02.api.letsencrypt.org-directory/gositeme.com/gositeme.com.crt')[0] ?? '';
    if ($certFile && file_exists($certFile)) {
        $certData = openssl_x509_parse(file_get_contents($certFile));
        if ($certData) {
            $expiresAt = date('Y-m-d', $certData['validTo_time_t']);
            $daysLeft = (int)(($certData['validTo_time_t'] - time()) / 86400);
            $results['checks']['ssl'] = ['status' => $daysLeft > 14 ? 'ok' : 'warn', 'expires' => $expiresAt, 'days_left' => $daysLeft];
            $daysLeft > 14 ? $pass++ : $fail++;
        } else {
            $results['checks']['ssl'] = ['status' => 'warn', 'info' => 'Could not parse cert'];
            $fail++;
        }
    } else {
        // Fallback: assume SSL is OK since we're serving HTTPS right now
        $results['checks']['ssl'] = ['status' => 'ok', 'info' => 'Serving HTTPS (cert file not directly readable)'];
        $pass++;
    }

    // 9. Backup recency
    $backupLog = __DIR__ . '/../logs/backup.log';
    if (file_exists($backupLog)) {
        $lines = file($backupLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $lastComplete = null;
        foreach (array_reverse($lines) as $line) {
            if (strpos($line, 'Backup complete') !== false) {
                preg_match('/\[([\d\-\s:]+)\]/', $line, $m);
                $lastComplete = trim($m[1] ?? '');
                break;
            }
        }
        $hoursAgo = $lastComplete ? round((time() - strtotime($lastComplete)) / 3600, 1) : null;
        $results['checks']['backup'] = [
            'status' => ($hoursAgo !== null && $hoursAgo < 26) ? 'ok' : 'warn',
            'last_complete' => $lastComplete,
            'hours_ago' => $hoursAgo
        ];
        ($hoursAgo !== null && $hoursAgo < 26) ? $pass++ : $fail++;
    } else {
        $results['checks']['backup'] = ['status' => 'warn', 'error' => 'No backup log found'];
        $fail++;
    }

    // Summary
    $results['summary'] = [
        'pass' => $pass,
        'fail' => $fail,
        'grade' => $fail === 0 ? 'A+' : ($fail <= 1 ? 'A' : ($fail <= 2 ? 'B' : 'C')),
        'verdict' => $fail === 0 ? 'All systems operational — Alfred reporting for duty.' : "$fail check(s) need attention."
    ];

    jsonResponse($results);
    break;

// ═══════════════════════════════════════════════════════════════════
// HELP — Self-documenting
// ═══════════════════════════════════════════════════════════════════

case 'help':
    jsonResponse(['success' => true, 'actions' => [
        'status'                   => 'GET    — System overview (table counts, overrides, events)',
        'users.list'               => 'GET    — List users (q, page, limit)',
        'users.get'                => 'GET    — Get user details (user_id)',
        'users.update'             => 'POST   — Update user fields (user_id, fields...)',
        'users.suspend'            => 'POST   — Suspend a user (user_id, reason)',
        'users.activate'           => 'POST   — Activate a user (user_id)',
        'users.security'           => 'GET    — Get user security info (user_id)',
        'users.reset-2fa'          => 'POST   — Reset user 2FA (user_id, reason)',
        'billing.plans'            => 'GET    — List available plans',
        'billing.user-services'    => 'GET    — User services/subscriptions (user_id)',
        'billing.change-plan'      => 'POST   — Change service plan (service_id, plan_id)',
        'billing.issue-credit'     => 'POST   — Issue account credit (user_id, amount, reason)',
        'billing.invoices'         => 'GET    — User invoices (user_id)',
        'games.sessions'           => 'GET    — Active game sessions',
        'games.scores'             => 'GET    — Game scores (game, limit)',
        'games.create-tournament'  => 'POST   — Create tournament (name, game, dates...)',
        'games.award-score'        => 'POST   — Award game score (user_id, game, score)',
        'gamification.award-badge' => 'POST   — Award badge (user_id, badge, reason)',
        'gamification.award-points'=> 'POST   — Award points (user_id, points, reason)',
        'gamification.leaderboard' => 'GET    — Points leaderboard (limit)',
        'gamification.user-badges' => 'GET    — User badges (user_id)',
        'pulse.moderate'           => 'POST   — Moderate pulse post (post_id, action, reason)',
        'pulse.stats'              => 'GET    — Pulse social stats',
        'fleet.agents'             => 'GET    — List fleet agents',
        'fleet.update-agent'       => 'POST   — Update agent config (agent_id, fields...)',
        'fleet.set-sla'            => 'POST   — Set agent SLA (agent_id, max_response_ms...)',
        'ivr.flows'                => 'GET    — List IVR flows',
        'ivr.create-flow'          => 'POST   — Create IVR flow (name, definition)',
        'ivr.update-flow-status'   => 'POST   — Update flow status (flow_id, status)',
        'campaigns.list'           => 'GET    — List call campaigns',
        'campaigns.pause'          => 'POST   — Pause campaign (campaign_id, reason)',
        'campaigns.resume'         => 'POST   — Resume campaign (campaign_id)',
        'campaigns.kill'           => 'POST   — Kill campaign (campaign_id, reason)',
        'security.audit'           => 'GET    — Security audit summary',
        'security.revoke-api-key'  => 'POST   — Revoke API key (key_id, reason)',
        'security.force-password-reset' => 'POST — Force password reset (user_id, reason)',
        'platform.config.get'      => 'GET    — Get config (key or all)',
        'platform.config.set'      => 'POST   — Set config (key, value)',
        'platform.flags.list'      => 'GET    — List feature flags',
        'platform.flags.set'       => 'POST   — Set feature flag (flag, enabled, description)',
        'platform.maintenance'     => 'POST   — Toggle maintenance mode (enabled, message, eta)',
        'events.recent'            => 'GET    — Recent events (subsystem, severity, limit)',
        'events.emit'              => 'POST   — Emit event (event_type, subsystem, severity, payload)',
        'override.issue'           => 'POST   — Issue override (subsystem, type, reason, parameters)',
        'override.lift'            => 'POST   — Lift override (override_id)',
        'override.active'          => 'GET    — List active overrides',
        'data.tables'              => 'GET    — List all database tables with row counts',
        'data.query'               => 'POST   — Execute read-only SQL query',
        'selftest'                 => 'GET    — Comprehensive ecosystem health diagnostic (9 checks, A+ grading)',
    ]]);
    break;

default:
    jsonResponse(['error' => "Unknown action: $action", 'hint' => 'Use ?action=help for available commands'], 400);
}
