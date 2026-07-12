<?php
/**
 * Alfred Metaverse World State API — Phase 3: Persistent World
 * ─────────────────────────────────────────────────────────────
 * Server-side persistent world: player profiles, cross-game identity,
 * Kingdom Coins economy, social graph, portals, property.
 *
 * Endpoints:
 *   GET  ?action=profile           → Get/create player profile
 *   POST ?action=update-profile    → Update player profile
 *   POST ?action=portal            → Travel between worlds
 *   GET  ?action=world-state       → World state for a specific zone
 *   POST ?action=update-position   → Update player position in world
 *   GET  ?action=economy           → Kingdom Coins (KGD) balance + ledger
 *   POST ?action=transfer          → Transfer KGD between players
 *   POST ?action=earn              → Earn KGD (game rewards)
 *   POST ?action=spend             → Spend KGD (shops, etc)
 *   GET  ?action=friends           → Friend list
 *   POST ?action=friend-request    → Send friend request
 *   POST ?action=friend-respond    → Accept/decline friend request
 *   GET  ?action=presence          → Online players + locations
 *   GET  ?action=leaderboard       → Cross-game ELO leaderboard
 *   GET  ?action=achievements      → Player achievements
 *   POST ?action=award-achievement → Grant an achievement
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

function ensureMetaverseSchema() {
    $db = getDB();
    if (!$db) return false;

    $db->exec("CREATE TABLE IF NOT EXISTS kingdom_players (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        client_id       INT UNIQUE NOT NULL,
        display_name    VARCHAR(50) NOT NULL,
        avatar_url      VARCHAR(500) DEFAULT NULL,
        avatar_config   JSON DEFAULT NULL,
        title           VARCHAR(50) DEFAULT 'Peasant',
        elo_rating      INT DEFAULT 1000,
        kgd_balance     BIGINT DEFAULT 100,
        current_zone    VARCHAR(50) DEFAULT 'central_square',
        position_x      FLOAT DEFAULT 0,
        position_y      FLOAT DEFAULT 0,
        position_z      FLOAT DEFAULT 0,
        status          ENUM('online','away','offline') DEFAULT 'offline',
        games_played    INT DEFAULT 0,
        games_won       INT DEFAULT 0,
        total_earned    BIGINT DEFAULT 0,
        total_spent     BIGINT DEFAULT 0,
        joined_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_seen       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_client (client_id),
        INDEX idx_elo (elo_rating),
        INDEX idx_zone (current_zone),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS kingdom_economy (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        from_player_id  INT DEFAULT NULL,
        to_player_id    INT DEFAULT NULL,
        entry_type      ENUM('earn','spend','transfer','mint','wager_win','wager_loss') NOT NULL,
        amount          BIGINT NOT NULL,
        reason          VARCHAR(200) NOT NULL,
        zone            VARCHAR(50) DEFAULT NULL,
        game            VARCHAR(50) DEFAULT NULL,
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_from (from_player_id),
        INDEX idx_to (to_player_id),
        INDEX idx_type (entry_type),
        INDEX idx_date (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS kingdom_friends (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        player_id       INT NOT NULL,
        friend_id       INT NOT NULL,
        status          ENUM('pending','accepted','blocked') DEFAULT 'pending',
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_pair (player_id, friend_id),
        INDEX idx_player (player_id),
        INDEX idx_friend (friend_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS kingdom_achievements (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        achievement_id  VARCHAR(50) UNIQUE NOT NULL,
        name            VARCHAR(100) NOT NULL,
        description     VARCHAR(500) NOT NULL,
        icon            VARCHAR(50) DEFAULT '🏆',
        category        VARCHAR(50) DEFAULT 'general',
        kgd_reward      INT DEFAULT 0,
        rarity          ENUM('common','uncommon','rare','epic','legendary') DEFAULT 'common',
        INDEX idx_category (category)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS kingdom_player_achievements (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        player_id       INT NOT NULL,
        achievement_id  VARCHAR(50) NOT NULL,
        earned_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_earn (player_id, achievement_id),
        INDEX idx_player (player_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Seed achievements if empty
    $count = $db->query("SELECT COUNT(*) FROM kingdom_achievements")->fetchColumn();
    if ($count == 0) {
        $achievements = [
            ['first_steps', 'First Steps', 'Enter the Kingdom for the first time', '👣', 'general', 10, 'common'],
            ['chess_novice', 'Chess Novice', 'Play your first chess game', '♟️', 'chess', 25, 'common'],
            ['chess_master', 'Chess Master', 'Win 50 chess games', '♚', 'chess', 500, 'epic'],
            ['chess_grandmaster', 'Chess Grandmaster', 'Reach 2000 ELO in chess', '👑', 'chess', 1000, 'legendary'],
            ['pool_shark', 'Pool Shark', 'Win 10 pool games', '🎱', 'pool', 100, 'uncommon'],
            ['checkers_king', 'Checkers King', 'Win 25 checkers games', '🏁', 'checkers', 250, 'rare'],
            ['socialite', 'Socialite', 'Add 10 friends', '👥', 'social', 50, 'uncommon'],
            ['high_roller', 'High Roller', 'Wager and win 1000 KGD total', '💰', 'economy', 200, 'rare'],
            ['world_traveler', 'World Traveler', 'Visit all 7 zones', '🗺️', 'exploration', 100, 'uncommon'],
            ['dj_debut', 'DJ Debut', 'Create your first mix in DJ Studio', '🎧', 'music', 50, 'common'],
            ['zen_master', 'Zen Master', 'Spend 30 minutes in the Sanctuary', '🧘', 'wellness', 75, 'uncommon'],
            ['speed_dater', 'Speed Dater', 'Complete 5 speed dating rounds', '💕', 'social', 100, 'uncommon'],
            ['big_spender', 'Big Spender', 'Spend 5000 KGD in shops', '🛍️', 'economy', 300, 'rare'],
            ['veteran', 'Kingdom Veteran', 'Play 100 total games', '⭐', 'general', 500, 'epic'],
            ['philanthropist', 'Philanthropist', 'Transfer 1000 KGD to other players', '🤝', 'economy', 250, 'rare'],
        ];
        $stmt = $db->prepare("INSERT INTO kingdom_achievements (achievement_id, name, description, icon, category, kgd_reward, rarity) VALUES (?, ?, ?, ?, ?, ?, ?)");
        foreach ($achievements as $a) $stmt->execute($a);
    }

    return true;
}

// ─── ELO Rank Titles ───────────────────────────────────────────────
function getTitle($elo) {
    if ($elo >= 2200) return 'King';
    if ($elo >= 2000) return 'Duke';
    if ($elo >= 1800) return 'Earl';
    if ($elo >= 1500) return 'Baron';
    if ($elo >= 1200) return 'Knight';
    return 'Peasant';
}

// ─── Zones ─────────────────────────────────────────────────────────
$ZONES = [
    'central_square' => ['name' => 'Central Square', 'description' => 'The heart of the Kingdom', 'connections' => ['chess_arena','checkers_tavern','pool_hall','speed_cafe','dj_studio','sanctuary','guild_hall']],
    'chess_arena' => ['name' => 'Chess Arena', 'description' => 'PvP chess with wagers', 'connections' => ['central_square']],
    'checkers_tavern' => ['name' => 'Checkers Tavern', 'description' => 'Relaxed checkers matches', 'connections' => ['central_square']],
    'pool_hall' => ['name' => 'Pool Hall', 'description' => 'VR-ready billiards', 'connections' => ['central_square']],
    'speed_cafe' => ['name' => 'Speed Dating Café', 'description' => 'Meet new people', 'connections' => ['central_square']],
    'dj_studio' => ['name' => 'DJ Studio', 'description' => 'Create and share music', 'connections' => ['central_square']],
    'sanctuary' => ['name' => 'Sanctuary', 'description' => 'Meditation and wellness', 'connections' => ['central_square']],
    'guild_hall' => ['name' => 'Guild Hall', 'description' => 'Guilds, shops, and economy', 'connections' => ['central_square']],
];

$action = sanitize($_GET['action'] ?? '', 30);
$db = getDB();
if (!$db) jsonResponse(['error' => 'Database unavailable'], 503);
ensureMetaverseSchema();

switch ($action) {

    case 'profile':
        requireAuth();
        $clientId = $_SESSION['client_id'];
        $targetId = intval($_GET['player_id'] ?? $clientId);

        $stmt = $db->prepare("SELECT * FROM kingdom_players WHERE client_id = ?");
        $stmt->execute([$targetId]);
        $player = $stmt->fetch();

        if (!$player) {
            // Auto-create profile
            $displayName = sanitize($_SESSION['first_name'] ?? 'Adventurer', 50);
            $db->prepare("INSERT INTO kingdom_players (client_id, display_name, status) VALUES (?, ?, 'online')")->execute([$targetId, $displayName]);
            $stmt->execute([$targetId]);
            $player = $stmt->fetch();

            // Log first steps achievement
            $db->prepare("INSERT IGNORE INTO kingdom_player_achievements (player_id, achievement_id) VALUES (?, 'first_steps')")->execute([$player['id']]);
            $db->prepare("UPDATE kingdom_players SET kgd_balance = kgd_balance + 10 WHERE id = ?")->execute([$player['id']]);
        }

        $player['avatar_config'] = json_decode($player['avatar_config'], true);
        $player['title'] = getTitle($player['elo_rating']);

        // Achievement count
        $achCount = $db->prepare("SELECT COUNT(*) FROM kingdom_player_achievements WHERE player_id = ?");
        $achCount->execute([$player['id']]);
        $player['achievement_count'] = (int)$achCount->fetchColumn();

        jsonResponse(['success' => true, 'player' => $player]);
        break;

    case 'update-profile':
        requireAuth();
        $clientId = $_SESSION['client_id'];
        $input = json_decode(file_get_contents('php://input'), true);

        $updates = [];
        $params = [];

        if (isset($input['display_name'])) {
            $name = sanitize($input['display_name'], 50);
            if (strlen($name) >= 2) { $updates[] = "display_name = ?"; $params[] = $name; }
        }
        if (isset($input['avatar_url'])) {
            $url = filter_var($input['avatar_url'], FILTER_VALIDATE_URL);
            if ($url) { $updates[] = "avatar_url = ?"; $params[] = $url; }
        }
        if (isset($input['avatar_config']) && is_array($input['avatar_config'])) {
            $updates[] = "avatar_config = ?";
            $params[] = json_encode($input['avatar_config']);
        }
        if (isset($input['status']) && in_array($input['status'], ['online','away','offline'])) {
            $updates[] = "status = ?";
            $params[] = $input['status'];
        }

        if (empty($updates)) jsonResponse(['error' => 'No valid fields to update'], 400);

        $params[] = $clientId;
        $db->prepare("UPDATE kingdom_players SET " . implode(', ', $updates) . " WHERE client_id = ?")->execute($params);

        jsonResponse(['success' => true]);
        break;

    case 'portal':
        requireAuth();
        $clientId = $_SESSION['client_id'];
        $input = json_decode(file_get_contents('php://input'), true);
        $destination = sanitize($input['destination'] ?? '', 50);

        global $ZONES;
        if (!isset($ZONES[$destination])) jsonResponse(['error' => 'Invalid zone', 'available_zones' => array_keys($ZONES)], 400);

        // Get current zone
        $player = $db->prepare("SELECT id, current_zone FROM kingdom_players WHERE client_id = ?");
        $player->execute([$clientId]);
        $p = $player->fetch();
        if (!$p) jsonResponse(['error' => 'Player not found'], 404);

        // Check if destination is connected to current zone
        $currentZone = $p['current_zone'];
        if ($currentZone !== $destination && !in_array($destination, $ZONES[$currentZone]['connections'] ?? [])) {
            jsonResponse(['error' => 'Cannot travel directly — zone not connected', 'current' => $currentZone, 'connected_zones' => $ZONES[$currentZone]['connections']], 400);
        }

        $db->prepare("UPDATE kingdom_players SET current_zone = ?, position_x = 0, position_y = 0, position_z = 0, status = 'online' WHERE client_id = ?")->execute([$destination, $clientId]);

        jsonResponse(['success' => true, 'from' => $currentZone, 'to' => $destination, 'zone' => $ZONES[$destination]]);
        break;

    case 'world-state':
        if (!isInternalCall()) requireAuth();

        $zone = sanitize($_GET['zone'] ?? 'central_square', 50);
        global $ZONES;
        if (!isset($ZONES[$zone])) jsonResponse(['error' => 'Invalid zone'], 400);

        $players = $db->prepare("SELECT id, display_name, avatar_url, title, elo_rating, position_x, position_y, position_z, status FROM kingdom_players WHERE current_zone = ? AND status != 'offline'");
        $players->execute([$zone]);

        jsonResponse([
            'success' => true,
            'zone' => $zone,
            'zone_info' => $ZONES[$zone],
            'players' => $players->fetchAll(),
            'timestamp' => time(),
        ]);
        break;

    case 'update-position':
        requireAuth();
        $input = json_decode(file_get_contents('php://input'), true);
        $db->prepare("UPDATE kingdom_players SET position_x = ?, position_y = ?, position_z = ?, status = 'online' WHERE client_id = ?")->execute([
            floatval($input['x'] ?? 0), floatval($input['y'] ?? 0), floatval($input['z'] ?? 0), $_SESSION['client_id']
        ]);
        jsonResponse(['success' => true]);
        break;

    case 'economy':
        requireAuth();
        $clientId = $_SESSION['client_id'];

        $player = $db->prepare("SELECT id, kgd_balance, total_earned, total_spent FROM kingdom_players WHERE client_id = ?");
        $player->execute([$clientId]);
        $p = $player->fetch();
        if (!$p) jsonResponse(['error' => 'Player not found'], 404);

        $recent = $db->prepare("SELECT * FROM kingdom_economy WHERE from_player_id = ? OR to_player_id = ? ORDER BY created_at DESC LIMIT 20");
        $recent->execute([$p['id'], $p['id']]);

        jsonResponse([
            'success' => true,
            'balance' => (int)$p['kgd_balance'],
            'total_earned' => (int)$p['total_earned'],
            'total_spent' => (int)$p['total_spent'],
            'recent_transactions' => $recent->fetchAll(),
        ]);
        break;

    case 'transfer':
        requireAuth();
        $input = json_decode(file_get_contents('php://input'), true);
        $toPlayerId = intval($input['to_player_id'] ?? 0);
        $amount = intval($input['amount'] ?? 0);
        if (!$toPlayerId || $amount < 1) jsonResponse(['error' => 'to_player_id and positive amount required'], 400);

        $clientId = $_SESSION['client_id'];
        $from = $db->prepare("SELECT id, kgd_balance FROM kingdom_players WHERE client_id = ?");
        $from->execute([$clientId]);
        $sender = $from->fetch();

        if (!$sender || $sender['kgd_balance'] < $amount) jsonResponse(['error' => 'Insufficient KGD balance'], 400);

        $to = $db->prepare("SELECT id FROM kingdom_players WHERE id = ?");
        $to->execute([$toPlayerId]);
        if (!$to->fetch()) jsonResponse(['error' => 'Recipient not found'], 404);

        $db->beginTransaction();
        try {
            $db->prepare("UPDATE kingdom_players SET kgd_balance = kgd_balance - ?, total_spent = total_spent + ? WHERE id = ?")->execute([$amount, $amount, $sender['id']]);
            $db->prepare("UPDATE kingdom_players SET kgd_balance = kgd_balance + ?, total_earned = total_earned + ? WHERE id = ?")->execute([$amount, $amount, $toPlayerId]);
            $db->prepare("INSERT INTO kingdom_economy (from_player_id, to_player_id, entry_type, amount, reason) VALUES (?, ?, 'transfer', ?, ?)")->execute([
                $sender['id'], $toPlayerId, $amount, sanitize($input['reason'] ?? 'Transfer', 200)
            ]);
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            jsonResponse(['error' => 'Transfer failed'], 500);
        }

        jsonResponse(['success' => true, 'transferred' => $amount, 'new_balance' => $sender['kgd_balance'] - $amount]);
        break;

    case 'earn':
        if (!isInternalCall()) { requireAuth(); if (!isAdmin()) jsonResponse(['error' => 'Admin/internal required'], 403); }

        $input = json_decode(file_get_contents('php://input'), true);
        $playerId = intval($input['player_id'] ?? 0);
        $amount = intval($input['amount'] ?? 0);
        if (!$playerId || $amount < 1) jsonResponse(['error' => 'player_id and positive amount required'], 400);

        $db->prepare("UPDATE kingdom_players SET kgd_balance = kgd_balance + ?, total_earned = total_earned + ? WHERE id = ?")->execute([$amount, $amount, $playerId]);
        $db->prepare("INSERT INTO kingdom_economy (to_player_id, entry_type, amount, reason, game, zone) VALUES (?, 'earn', ?, ?, ?, ?)")->execute([
            $playerId, $amount, sanitize($input['reason'] ?? 'Reward', 200), sanitize($input['game'] ?? '', 50) ?: null, sanitize($input['zone'] ?? '', 50) ?: null
        ]);

        jsonResponse(['success' => true, 'earned' => $amount]);
        break;

    case 'spend':
        requireAuth();
        $input = json_decode(file_get_contents('php://input'), true);
        $amount = intval($input['amount'] ?? 0);
        if ($amount < 1) jsonResponse(['error' => 'Positive amount required'], 400);

        $clientId = $_SESSION['client_id'];
        $player = $db->prepare("SELECT id, kgd_balance FROM kingdom_players WHERE client_id = ?");
        $player->execute([$clientId]);
        $p = $player->fetch();

        if (!$p || $p['kgd_balance'] < $amount) jsonResponse(['error' => 'Insufficient KGD'], 400);

        $db->prepare("UPDATE kingdom_players SET kgd_balance = kgd_balance - ?, total_spent = total_spent + ? WHERE id = ?")->execute([$amount, $amount, $p['id']]);
        $db->prepare("INSERT INTO kingdom_economy (from_player_id, entry_type, amount, reason, zone) VALUES (?, 'spend', ?, ?, ?)")->execute([
            $p['id'], $amount, sanitize($input['reason'] ?? 'Purchase', 200), sanitize($input['zone'] ?? '', 50) ?: null
        ]);

        jsonResponse(['success' => true, 'spent' => $amount, 'new_balance' => $p['kgd_balance'] - $amount]);
        break;

    case 'friends':
        requireAuth();
        $clientId = $_SESSION['client_id'];
        $player = $db->prepare("SELECT id FROM kingdom_players WHERE client_id = ?");
        $player->execute([$clientId]);
        $p = $player->fetch();
        if (!$p) jsonResponse(['error' => 'Player not found'], 404);

        $friends = $db->prepare("SELECT f.*, p.display_name, p.avatar_url, p.title, p.elo_rating, p.current_zone, p.status FROM kingdom_friends f JOIN kingdom_players p ON (f.friend_id = p.id) WHERE f.player_id = ? AND f.status = 'accepted' UNION SELECT f.*, p.display_name, p.avatar_url, p.title, p.elo_rating, p.current_zone, p.status FROM kingdom_friends f JOIN kingdom_players p ON (f.player_id = p.id) WHERE f.friend_id = ? AND f.status = 'accepted'");
        $friends->execute([$p['id'], $p['id']]);

        $pending = $db->prepare("SELECT f.*, p.display_name FROM kingdom_friends f JOIN kingdom_players p ON f.player_id = p.id WHERE f.friend_id = ? AND f.status = 'pending'");
        $pending->execute([$p['id']]);

        jsonResponse(['success' => true, 'friends' => $friends->fetchAll(), 'pending_requests' => $pending->fetchAll()]);
        break;

    case 'friend-request':
        requireAuth();
        $input = json_decode(file_get_contents('php://input'), true);
        $friendPlayerId = intval($input['friend_player_id'] ?? 0);
        if (!$friendPlayerId) jsonResponse(['error' => 'friend_player_id required'], 400);

        $clientId = $_SESSION['client_id'];
        $player = $db->prepare("SELECT id FROM kingdom_players WHERE client_id = ?");
        $player->execute([$clientId]);
        $p = $player->fetch();
        if (!$p) jsonResponse(['error' => 'Player not found'], 404);
        if ($p['id'] === $friendPlayerId) jsonResponse(['error' => 'Cannot friend yourself'], 400);

        try {
            $db->prepare("INSERT INTO kingdom_friends (player_id, friend_id) VALUES (?, ?)")->execute([$p['id'], $friendPlayerId]);
            jsonResponse(['success' => true, 'status' => 'pending']);
        } catch (PDOException $e) {
            jsonResponse(['error' => 'Request already exists'], 400);
        }
        break;

    case 'friend-respond':
        requireAuth();
        $input = json_decode(file_get_contents('php://input'), true);
        $requestId = intval($input['request_id'] ?? 0);
        $response = sanitize($input['response'] ?? '', 20);
        if (!$requestId || !in_array($response, ['accepted','blocked'])) jsonResponse(['error' => 'request_id and response (accepted/blocked) required'], 400);

        $clientId = $_SESSION['client_id'];
        $player = $db->prepare("SELECT id FROM kingdom_players WHERE client_id = ?");
        $player->execute([$clientId]);
        $p = $player->fetch();

        $db->prepare("UPDATE kingdom_friends SET status = ? WHERE id = ? AND friend_id = ? AND status = 'pending'")->execute([$response, $requestId, $p['id']]);

        jsonResponse(['success' => true, 'status' => $response]);
        break;

    case 'presence':
        if (!isInternalCall()) requireAuth();

        $online = $db->query("SELECT id, display_name, avatar_url, title, current_zone, status, elo_rating FROM kingdom_players WHERE status != 'offline' ORDER BY last_seen DESC LIMIT 100")->fetchAll();

        $byZone = [];
        foreach ($online as $p) {
            $byZone[$p['current_zone']][] = $p;
        }

        jsonResponse(['success' => true, 'online_count' => count($online), 'players' => $online, 'by_zone' => $byZone]);
        break;

    case 'leaderboard':
        if (!isInternalCall()) requireAuth();

        $game = sanitize($_GET['game'] ?? '', 50);
        $limit = min(max(intval($_GET['limit'] ?? 25), 1), 100);

        $leaders = $db->prepare("SELECT id, display_name, avatar_url, title, elo_rating, games_played, games_won FROM kingdom_players ORDER BY elo_rating DESC LIMIT ?");
        dbExecute($leaders, [$limit]);

        $leaderboard = $leaders->fetchAll();
        foreach ($leaderboard as $i => &$l) {
            $l['rank'] = $i + 1;
            $l['win_rate'] = $l['games_played'] > 0 ? round(($l['games_won'] / $l['games_played']) * 100, 1) : 0;
        }

        jsonResponse(['success' => true, 'leaderboard' => $leaderboard]);
        break;

    case 'achievements':
        requireAuth();
        $clientId = intval($_GET['player_id'] ?? $_SESSION['client_id']);

        $player = $db->prepare("SELECT id FROM kingdom_players WHERE client_id = ?");
        $player->execute([$clientId]);
        $p = $player->fetch();
        if (!$p) jsonResponse(['error' => 'Player not found'], 404);

        $all = $db->query("SELECT * FROM kingdom_achievements ORDER BY category, rarity")->fetchAll();
        $earned = $db->prepare("SELECT achievement_id, earned_at FROM kingdom_player_achievements WHERE player_id = ?");
        $earned->execute([$p['id']]);
        $earnedMap = [];
        foreach ($earned->fetchAll() as $e) $earnedMap[$e['achievement_id']] = $e['earned_at'];

        foreach ($all as &$a) {
            $a['earned'] = isset($earnedMap[$a['achievement_id']]);
            $a['earned_at'] = $earnedMap[$a['achievement_id']] ?? null;
        }

        jsonResponse(['success' => true, 'achievements' => $all, 'earned_count' => count($earnedMap), 'total_count' => count($all)]);
        break;

    case 'award-achievement':
        if (!isInternalCall()) { requireAuth(); if (!isAdmin()) jsonResponse(['error' => 'Admin/internal required'], 403); }

        $input = json_decode(file_get_contents('php://input'), true);
        $playerId = intval($input['player_id'] ?? 0);
        $achievementId = sanitize($input['achievement_id'] ?? '', 50);
        if (!$playerId || !$achievementId) jsonResponse(['error' => 'player_id and achievement_id required'], 400);

        // Check achievement exists
        $ach = $db->prepare("SELECT kgd_reward FROM kingdom_achievements WHERE achievement_id = ?");
        $ach->execute([$achievementId]);
        $a = $ach->fetch();
        if (!$a) jsonResponse(['error' => 'Achievement not found'], 404);

        try {
            $db->prepare("INSERT INTO kingdom_player_achievements (player_id, achievement_id) VALUES (?, ?)")->execute([$playerId, $achievementId]);
            // Award KGD reward
            if ($a['kgd_reward'] > 0) {
                $db->prepare("UPDATE kingdom_players SET kgd_balance = kgd_balance + ? WHERE id = ?")->execute([$a['kgd_reward'], $playerId]);
            }
            jsonResponse(['success' => true, 'kgd_reward' => $a['kgd_reward']]);
        } catch (PDOException $e) {
            jsonResponse(['error' => 'Achievement already earned'], 400);
        }
        break;

    default:
        jsonResponse(['error' => 'Unknown action', 'available_actions' => ['profile','update-profile','portal','world-state','update-position','economy','transfer','earn','spend','friends','friend-request','friend-respond','presence','leaderboard','achievements','award-achievement']], 400);
}
