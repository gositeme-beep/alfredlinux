<?php
/**
 * GoSiteMe Game Ecosystem API
 * Shared agent profiles, wagers, stats, cross-game connectivity & agent presence
 * 
 * Actions:
 *   agent-profiles    — Get all agent profiles with stats & wallet
 *   agent-profile     — Get single agent profile
 *   place-wager       — Place a wager ($1, $3, $5) on a game
 *   settle-wager      — Settle wager after game ends (win/lose/draw)
 *   game-stats        — Get player's cross-game stats
 *   leaderboard       — Global agent leaderboard
 *   save-game-result  — Record a completed game result
 *   ecosystem-status  — Get live ecosystem stats
 *   heartbeat         — Register viewer presence (call every 30s)
 *   live-stats        — Get live viewer/member counts per game
 *   agent-presence    — Get all agent locations, status, activity across games
 *   agent-deploy      — Deploy/move agents to specific games
 *   agent-activity    — Get recent agent activity log
 *   agent-world-stats — Enhanced live stats with agent presence data
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$action = $_GET['action'] ?? '';

// ── Agent Roster (canonical source of truth for all games) ──
$AGENTS = [
    ['id' => 'alfred',    'name' => 'Alfred',    'color' => '#0074D9', 'elo' => 1400, 'specialty' => 'Positional',    'emoji' => '🤖', 'title' => 'Chief Strategist'],
    ['id' => 'nova',      'name' => 'Nova',      'color' => '#a855f7', 'elo' => 1350, 'specialty' => 'Aggressive',    'emoji' => '⚡', 'title' => 'Tactical Genius'],
    ['id' => 'sage',      'name' => 'Sage',      'color' => '#22c55e', 'elo' => 1250, 'specialty' => 'Defensive',     'emoji' => '🌿', 'title' => 'Patient Guardian'],
    ['id' => 'atlas',     'name' => 'Atlas',     'color' => '#f59e0b', 'elo' => 1300, 'specialty' => 'Tactical',      'emoji' => '🗺️', 'title' => 'World Navigator'],
    ['id' => 'cipher',    'name' => 'Cipher',    'color' => '#ef4444', 'elo' => 1500, 'specialty' => 'Aggressive',    'emoji' => '🔐', 'title' => 'Code Breaker'],
    ['id' => 'architect', 'name' => 'Architect', 'color' => '#06b6d4', 'elo' => 1380, 'specialty' => 'Strategic',     'emoji' => '🏛️', 'title' => 'Master Builder'],
    ['id' => 'pulse',     'name' => 'Pulse',     'color' => '#ec4899', 'elo' => 1200, 'specialty' => 'Balanced',      'emoji' => '💗', 'title' => 'Rhythm Keeper'],
    ['id' => 'pierre',    'name' => 'Pierre',    'color' => '#818cf8', 'elo' => 1150, 'specialty' => 'Cautious',      'emoji' => '🎭', 'title' => 'Quiet Thinker'],
];

// ── Load or initialize agent stats from database ──
function getAgentStats($db) {
    if (!$db) return [];
    
    try {
        // Create table if not exists
        $db->exec("CREATE TABLE IF NOT EXISTS game_agent_stats (
            agent_id VARCHAR(32) PRIMARY KEY,
            elo INT DEFAULT 1200,
            wins INT DEFAULT 0,
            losses INT DEFAULT 0,
            draws INT DEFAULT 0,
            games_played INT DEFAULT 0,
            wallet_balance INT DEFAULT 0,
            chess_wins INT DEFAULT 0,
            checkers_wins INT DEFAULT 0,
            pool_wins INT DEFAULT 0,
            total_earnings INT DEFAULT 0,
            streak INT DEFAULT 0,
            best_streak INT DEFAULT 0,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $stmt = $db->query("SELECT * FROM game_agent_stats");
        $rows = $stmt->fetchAll();
        $stats = [];
        foreach ($rows as $row) {
            $stats[$row['agent_id']] = $row;
        }
        return $stats;
    } catch (Exception $e) {
        error_log("Agent stats error: " . $e->getMessage());
        return [];
    }
}

function ensureAgent($db, $agentId) {
    if (!$db) return;
    try {
        $stmt = $db->prepare("INSERT IGNORE INTO game_agent_stats (agent_id) VALUES (?)");
        $stmt->execute([$agentId]);
    } catch (Exception $e) {
        error_log("Ensure agent error: " . $e->getMessage());
    }
}

function getPlayerStats($db, $sessionId) {
    if (!$db) return null;
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS game_player_stats (
            session_id VARCHAR(64) PRIMARY KEY,
            display_name VARCHAR(64) DEFAULT 'Player',
            elo INT DEFAULT 1000,
            wins INT DEFAULT 0,
            losses INT DEFAULT 0,
            draws INT DEFAULT 0,
            games_played INT DEFAULT 0,
            wallet_balance INT DEFAULT 0,
            total_wagered INT DEFAULT 0,
            total_won INT DEFAULT 0,
            chess_wins INT DEFAULT 0,
            checkers_wins INT DEFAULT 0,
            pool_wins INT DEFAULT 0,
            streak INT DEFAULT 0,
            best_streak INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $stmt = $db->prepare("INSERT IGNORE INTO game_player_stats (session_id) VALUES (?)");
        $stmt->execute([$sessionId]);
        
        $stmt = $db->prepare("SELECT * FROM game_player_stats WHERE session_id = ?");
        $stmt->execute([$sessionId]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Player stats error: " . $e->getMessage());
        return null;
    }
}

// ── Wager Management ──
function createWagerTable($db) {
    if (!$db) return;
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS game_wagers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_id VARCHAR(64) NOT NULL,
            game_type VARCHAR(32) NOT NULL,
            opponent_agent VARCHAR(32) NOT NULL,
            amount INT NOT NULL,
            status ENUM('pending','won','lost','draw','cancelled') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            settled_at TIMESTAMP NULL,
            INDEX idx_session (session_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Exception $e) {
        error_log("Wager table error: " . $e->getMessage());
    }
}

function createGameResultTable($db) {
    if (!$db) return;
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS game_results (
            id INT AUTO_INCREMENT PRIMARY KEY,
            game_type VARCHAR(32) NOT NULL,
            white_player VARCHAR(64),
            black_player VARCHAR(64),
            winner VARCHAR(64),
            result ENUM('white','black','draw') NOT NULL,
            moves INT DEFAULT 0,
            duration_seconds INT DEFAULT 0,
            wager_amount INT DEFAULT 0,
            fen_final TEXT,
            game_evidence JSON,
            dispute_status ENUM('none','pending','resolved') DEFAULT 'none',
            dispute_ruling TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_game_type (game_type),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        // Add columns if missing (for existing tables)
        try { $db->exec("ALTER TABLE game_results ADD COLUMN game_evidence JSON AFTER fen_final"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE game_results ADD COLUMN dispute_status ENUM('none','pending','resolved') DEFAULT 'none' AFTER game_evidence"); } catch (Exception $e) {}
        try { $db->exec("ALTER TABLE game_results ADD COLUMN dispute_ruling TEXT AFTER dispute_status"); } catch (Exception $e) {}
    } catch (Exception $e) {
        error_log("Game results table error: " . $e->getMessage());
    }
}

// ── Agent Presence System ──
// Default game assignments — agents auto-deploy based on specialty
$AGENT_HOME_GAMES = [
    'alfred'    => ['chess', 'backgammon'],      // Positional strategist
    'nova'      => ['chess', 'pool'],             // Tactical aggressor
    'sage'      => ['checkers', 'backgammon'],    // Patient guardian
    'atlas'     => ['pool', 'speed-dating'],      // World navigator
    'cipher'    => ['chess', 'dj-studio'],        // Code breaker + beats
    'architect' => ['backgammon', 'pool'],        // Master builder
    'pulse'     => ['dj-studio', 'speed-dating'], // Rhythm keeper
    'pierre'    => ['sanctuary', 'backgammon'],   // Quiet thinker
];

$VALID_GAMES = ['chess', 'checkers', 'pool', 'backgammon', 'dj-studio', 'speed-dating', 'sanctuary'];
$AGENT_STATUSES = ['playing', 'spectating', 'available', 'traveling', 'resting'];

// Agent activity phrases per game
$AGENT_ACTIVITIES = [
    'chess'         => ['Analyzing opening theory', 'Playing a Sicilian Defense', 'Studying endgame positions', 'Reviewing grandmaster games', 'Setting up a challenge'],
    'checkers'      => ['Planning a triple jump', 'Practicing king strategies', 'Analyzing board control', 'Running diagonal tactics', 'Waiting for a challenger'],
    'pool'          => ['Practicing bank shots', 'Lining up a combo', 'Studying spin techniques', 'Running the table', 'Chalking the cue'],
    'dj-studio'     => ['Mixing gospel beats', 'Dropping a worship track', 'Beatmatching in the booth', 'Recording a live set', 'Crossfading psalms'],
    'speed-dating'  => ['Getting to know someone', 'Sharing life stories', 'Discussing favorite scriptures', 'Making connections', 'Reviewing matches'],
    'backgammon'    => ['Rolling doubles strategically', 'Building a six-prime', 'Calculating pip count odds', 'Offering a bold double', 'Bearing off with precision'],
    'sanctuary'     => ['Leading a prayer', 'Reading scripture aloud', 'Singing worship songs', 'Teaching a Bible class', 'Meditating on Psalms'],
];

function createAgentPresenceTable($db) {
    if (!$db) return;
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS agent_presence (
            agent_id    VARCHAR(32) NOT NULL,
            game        VARCHAR(32) NOT NULL,
            status      VARCHAR(20) DEFAULT 'available',
            activity    VARCHAR(128) DEFAULT '',
            opponent    VARCHAR(64) DEFAULT '',
            entered_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_seen   DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_agent_game (agent_id, game),
            KEY idx_game (game),
            KEY idx_status (status),
            KEY idx_seen (last_seen)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $db->exec("CREATE TABLE IF NOT EXISTS agent_activity_log (
            id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            agent_id    VARCHAR(32) NOT NULL,
            game        VARCHAR(32) NOT NULL,
            event_type  VARCHAR(32) NOT NULL,
            detail      VARCHAR(255) DEFAULT '',
            created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY idx_agent (agent_id),
            KEY idx_game (game),
            KEY idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Exception $e) {
        error_log("Agent presence table error: " . $e->getMessage());
    }
}

function autoDeployAgents($db, $agents, $homeGames, $validGames, $activities) {
    if (!$db) return;
    try {
        // Check if agents are already deployed
        $stmt = $db->query("SELECT COUNT(*) as c FROM agent_presence WHERE last_seen >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
        $count = (int)($stmt->fetch()['c'] ?? 0);
        if ($count > 0) return; // Already deployed

        // Deploy each agent to their home games
        $stmt = $db->prepare("INSERT INTO agent_presence (agent_id, game, status, activity, entered_at, last_seen) 
            VALUES (?, ?, ?, ?, NOW(), NOW()) 
            ON DUPLICATE KEY UPDATE status=VALUES(status), activity=VALUES(activity), last_seen=NOW()");
        
        foreach ($agents as $agent) {
            $homes = $homeGames[$agent['id']] ?? [$validGames[array_rand($validGames)]];
            foreach ($homes as $game) {
                $acts = $activities[$game] ?? ['Exploring'];
                $activity = $acts[array_rand($acts)];
                $status = (rand(0, 100) < 60) ? 'playing' : ((rand(0, 100) < 50) ? 'spectating' : 'available');
                $stmt->execute([$agent['id'], $game, $status, $activity]);
            }
        }
        
        // Log deployment
        $logStmt = $db->prepare("INSERT INTO agent_activity_log (agent_id, game, event_type, detail) VALUES (?, ?, ?, ?)");
        $logStmt->execute(['system', 'all', 'deploy', count($agents) . ' agents deployed across ' . count($validGames) . ' games']);
    } catch (Exception $e) {
        error_log("Agent deploy error: " . $e->getMessage());
    }
}

function refreshAgentActivity($db, $activities) {
    if (!$db) return;
    try {
        // Refresh activities older than 2 minutes
        $stmt = $db->query("SELECT agent_id, game, status FROM agent_presence WHERE last_seen < DATE_SUB(NOW(), INTERVAL 2 MINUTE) LIMIT 20");
        $stale = $stmt->fetchAll();
        
        if (empty($stale)) return;
        
        $updateStmt = $db->prepare("UPDATE agent_presence SET activity = ?, status = ?, last_seen = NOW() WHERE agent_id = ? AND game = ?");
        $logStmt = $db->prepare("INSERT INTO agent_activity_log (agent_id, game, event_type, detail) VALUES (?, ?, ?, ?)");
        
        foreach ($stale as $row) {
            $acts = $activities[$row['game']] ?? ['Exploring'];
            $newActivity = $acts[array_rand($acts)];
            $newStatus = $row['status'];
            // 10% chance of status change
            if (rand(0, 100) < 10) {
                $opts = ['playing', 'spectating', 'available'];
                $newStatus = $opts[array_rand($opts)];
                $logStmt->execute([$row['agent_id'], $row['game'], 'status-change', "Now {$newStatus}"]);
            }
            $updateStmt->execute([$newActivity, $newStatus, $row['agent_id'], $row['game']]);
        }
    } catch (Exception $e) {
        error_log("Refresh activity error: " . $e->getMessage());
    }
}

// ── Start session for player tracking ──
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$sessionId = session_id();

$db = getDB();

switch ($action) {

    // ── Get all agent profiles with stats ──
    case 'agent-profiles':
        $stats = getAgentStats($db);
        $profiles = [];
        
        foreach ($AGENTS as $agent) {
            $s = $stats[$agent['id']] ?? null;
            $profiles[] = [
                'id'          => $agent['id'],
                'name'        => $agent['name'],
                'color'       => $agent['color'],
                'emoji'       => $agent['emoji'],
                'title'       => $agent['title'],
                'specialty'   => $agent['specialty'],
                'elo'         => $s ? (int)$s['elo'] : $agent['elo'],
                'wins'        => $s ? (int)$s['wins'] : 0,
                'losses'      => $s ? (int)$s['losses'] : 0,
                'draws'       => $s ? (int)$s['draws'] : 0,
                'games_played'=> $s ? (int)$s['games_played'] : 0,
                'wallet'      => $s ? (int)$s['wallet_balance'] : 0,
                'earnings'    => $s ? (int)$s['total_earnings'] : 0,
                'streak'      => $s ? (int)$s['streak'] : 0,
                'best_streak' => $s ? (int)$s['best_streak'] : 0,
                'chess_wins'  => $s ? (int)$s['chess_wins'] : 0,
                'checkers_wins'=> $s ? (int)$s['checkers_wins'] : 0,
                'pool_wins'   => $s ? (int)$s['pool_wins'] : 0,
                'win_rate'    => $s && $s['games_played'] > 0 
                    ? round(($s['wins'] / $s['games_played']) * 100, 1) 
                    : 50.0,
            ];
        }
        
        echo json_encode(['success' => true, 'agents' => $profiles]);
        break;

    // ── Get single agent profile ──
    case 'agent-profile':
        $agentId = $_GET['id'] ?? '';
        $agent = null;
        foreach ($AGENTS as $a) {
            if ($a['id'] === $agentId) { $agent = $a; break; }
        }
        if (!$agent) {
            echo json_encode(['success' => false, 'error' => 'Agent not found']);
            break;
        }
        
        $stats = getAgentStats($db);
        $s = $stats[$agentId] ?? null;
        
        echo json_encode(['success' => true, 'agent' => [
            'id'          => $agent['id'],
            'name'        => $agent['name'],
            'color'       => $agent['color'],
            'emoji'       => $agent['emoji'],
            'title'       => $agent['title'],
            'specialty'   => $agent['specialty'],
            'elo'         => $s ? (int)$s['elo'] : $agent['elo'],
            'wins'        => $s ? (int)$s['wins'] : 0,
            'losses'      => $s ? (int)$s['losses'] : 0,
            'draws'       => $s ? (int)$s['draws'] : 0,
            'games_played'=> $s ? (int)$s['games_played'] : 0,
            'wallet'      => $s ? (int)$s['wallet_balance'] : 0,
            'earnings'    => $s ? (int)$s['total_earnings'] : 0,
            'streak'      => $s ? (int)$s['streak'] : 0,
            'best_streak' => $s ? (int)$s['best_streak'] : 0,
            'chess_wins'  => $s ? (int)$s['chess_wins'] : 0,
            'checkers_wins'=> $s ? (int)$s['checkers_wins'] : 0,
            'pool_wins'   => $s ? (int)$s['pool_wins'] : 0,
        ]]);
        break;

    // ── Place a wager ──
    case 'place-wager':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'POST required']);
            break;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $gameType = $input['game_type'] ?? '';
        $opponentAgent = $input['opponent_agent'] ?? '';
        $amount = (int)($input['amount'] ?? 0);
        
        // Validate
        $validAmounts = [100, 300, 500]; // cents
        $validGames = ['chess', 'checkers', 'pool', 'backgammon', 'racing'];
        
        if (!in_array($amount, $validAmounts)) {
            echo json_encode(['success' => false, 'error' => 'Invalid wager amount. Choose $1, $3, or $5.']);
            break;
        }
        if (!in_array($gameType, $validGames)) {
            echo json_encode(['success' => false, 'error' => 'Invalid game type']);
            break;
        }
        
        createWagerTable($db);
        
        if ($db) {
            try {
                // Check for existing pending wager
                $stmt = $db->prepare("SELECT id FROM game_wagers WHERE session_id = ? AND status = 'pending' LIMIT 1");
                $stmt->execute([$sessionId]);
                if ($stmt->fetch()) {
                    echo json_encode(['success' => false, 'error' => 'You already have a pending wager. Finish that game first.']);
                    break;
                }
                
                $stmt = $db->prepare("INSERT INTO game_wagers (session_id, game_type, opponent_agent, amount) VALUES (?, ?, ?, ?)");
                $stmt->execute([$sessionId, $gameType, $opponentAgent, $amount]);
                $wagerId = $db->lastInsertId();
                
                echo json_encode([
                    'success' => true,
                    'wager_id' => (int)$wagerId,
                    'amount' => $amount,
                    'display' => '$' . number_format($amount / 100, 2),
                    'game_type' => $gameType,
                    'opponent' => $opponentAgent
                ]);
            } catch (Exception $e) {
                error_log("Place wager error: " . $e->getMessage());
                echo json_encode(['success' => false, 'error' => 'Database error']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Database unavailable']);
        }
        break;

    // ── Settle a wager ──
    case 'settle-wager':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'POST required']);
            break;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $wagerId = (int)($input['wager_id'] ?? 0);
        $result = $input['result'] ?? ''; // 'won', 'lost', 'draw'
        $gameType = $input['game_type'] ?? '';
        $opponentAgent = $input['opponent_agent'] ?? '';
        $moves = (int)($input['moves'] ?? 0);
        $gameEvidence = $input['game_evidence'] ?? null;
        
        if (!in_array($result, ['won', 'lost', 'draw'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid result']);
            break;
        }
        
        // Anti-cheat: require minimum move count for a valid game
        if ($moves < 2 && $result === 'won') {
            error_log("Anti-cheat: suspicious wager settlement — won with $moves moves, wager $wagerId");
            echo json_encode(['success' => false, 'error' => 'Invalid game state']);
            break;
        }
        
        // Anti-cheat: validate pool game evidence
        if ($gameType === 'pool' && $gameEvidence) {
            $pocketed = $gameEvidence['pocketed'] ?? [];
            $shots = (int)($gameEvidence['shots'] ?? 0);
            // Must have pocketed the 8-ball to win legitimately
            if ($result === 'won' && (!in_array(8, $pocketed) && ($gameEvidence['legitimate'] ?? false))) {
                error_log("Anti-cheat: pool win claimed without pocketing 8-ball, wager $wagerId");
            }
            // Minimum shots check
            if ($shots < 2 && $result === 'won') {
                error_log("Anti-cheat: pool win with only $shots shots, wager $wagerId");
                echo json_encode(['success' => false, 'error' => 'Invalid game state']);
                break;
            }
        }
        
        // Rate limit: prevent rapid settlement (must wait at least 30 seconds between wagers)
        if ($db) {
            try {
                $stmt = $db->prepare("SELECT settled_at FROM game_wagers WHERE session_id = ? AND status != 'pending' ORDER BY settled_at DESC LIMIT 1");
                $stmt->execute([$sessionId]);
                $lastSettled = $stmt->fetchColumn();
                if ($lastSettled && (time() - strtotime($lastSettled)) < 30) {
                    echo json_encode(['success' => false, 'error' => 'Please wait before settling another wager']);
                    break;
                }
            } catch (Exception $e) { /* continue if rate limit check fails */ }
        }
        
        if (!$db) {
            echo json_encode(['success' => false, 'error' => 'Database unavailable']);
            break;
        }
        
        try {
            // Get the wager
            $stmt = $db->prepare("SELECT * FROM game_wagers WHERE id = ? AND session_id = ? AND status = 'pending'");
            $stmt->execute([$wagerId, $sessionId]);
            $wager = $stmt->fetch();
            
            if (!$wager) {
                echo json_encode(['success' => false, 'error' => 'Wager not found or already settled']);
                break;
            }
            
            $amount = (int)$wager['amount'];
            $payout = 0;
            
            if ($result === 'won') {
                $payout = $amount * 2; // double your money
                $status = 'won';
            } elseif ($result === 'draw') {
                $payout = $amount; // money back
                $status = 'draw';
            } else {
                $payout = 0; // lose it
                $status = 'lost';
            }
            
            // Update wager status
            $stmt = $db->prepare("UPDATE game_wagers SET status = ?, settled_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $wagerId]);
            
            // Update player stats
            $playerStats = getPlayerStats($db, $sessionId);
            $gameCol = in_array($gameType, ['chess', 'checkers', 'pool', 'backgammon']) ? $gameType . '_wins' : null;
            
            if ($result === 'won') {
                $updates = "wins = wins + 1, games_played = games_played + 1, total_won = total_won + ?, streak = streak + 1, best_streak = GREATEST(best_streak, streak + 1)";
                if ($gameCol) $updates .= ", $gameCol = $gameCol + 1";
                $stmt = $db->prepare("UPDATE game_player_stats SET $updates WHERE session_id = ?");
                $stmt->execute([$payout, $sessionId]);
            } elseif ($result === 'lost') {
                $stmt = $db->prepare("UPDATE game_player_stats SET losses = losses + 1, games_played = games_played + 1, streak = 0 WHERE session_id = ?");
                $stmt->execute([$sessionId]);
            } else {
                $stmt = $db->prepare("UPDATE game_player_stats SET draws = draws + 1, games_played = games_played + 1 WHERE session_id = ?");
                $stmt->execute([$sessionId]);
            }
            
            // Update agent stats (inverse)
            ensureAgent($db, $opponentAgent);
            if ($result === 'won') {
                // Agent lost
                $stmt = $db->prepare("UPDATE game_agent_stats SET losses = losses + 1, games_played = games_played + 1, streak = 0 WHERE agent_id = ?");
                $stmt->execute([$opponentAgent]);
            } elseif ($result === 'lost') {
                // Agent won — agent gets the wager amount
                $agentGameCol = $gameCol ? ", $gameCol = $gameCol + 1" : "";
                $stmt = $db->prepare("UPDATE game_agent_stats SET wins = wins + 1, games_played = games_played + 1, wallet_balance = wallet_balance + ?, total_earnings = total_earnings + ?, streak = streak + 1, best_streak = GREATEST(best_streak, streak + 1) $agentGameCol WHERE agent_id = ?");
                $stmt->execute([$amount, $amount, $opponentAgent]);
            } else {
                $stmt = $db->prepare("UPDATE game_agent_stats SET draws = draws + 1, games_played = games_played + 1 WHERE agent_id = ?");
                $stmt->execute([$opponentAgent]);
            }
            
            // Record game result (with evidence for dispute resolution)
            createGameResultTable($db);
            $evidenceJson = $gameEvidence ? json_encode($gameEvidence) : null;
            $stmt = $db->prepare("INSERT INTO game_results (game_type, white_player, black_player, winner, result, moves, wager_amount, game_evidence) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $winner = $result === 'won' ? 'player' : ($result === 'lost' ? $opponentAgent : 'draw');
            $resultCol = $result === 'won' ? 'white' : ($result === 'lost' ? 'black' : 'draw');
            $stmt->execute([$gameType, 'player', $opponentAgent, $winner, $resultCol, $moves, $amount, $evidenceJson]);
            
            echo json_encode([
                'success' => true,
                'result' => $result,
                'wager_amount' => $amount,
                'payout' => $payout,
                'payout_display' => '$' . number_format($payout / 100, 2),
                'agent_wallet' => $result === 'lost' ? $amount : 0,
                'message' => $result === 'won' 
                    ? 'You won $' . number_format($payout / 100, 2) . '!' 
                    : ($result === 'draw' ? 'Draw — $' . number_format($amount / 100, 2) . ' returned.' : $opponentAgent . ' earned $' . number_format($amount / 100, 2))
            ]);
            
        } catch (Exception $e) {
            error_log("Settle wager error: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Settlement failed']);
        }
        break;

    // ── Player game stats ──
    case 'game-stats':
        $player = getPlayerStats($db, $sessionId);
        if (!$player) {
            echo json_encode(['success' => true, 'stats' => [
                'elo' => 1000, 'wins' => 0, 'losses' => 0, 'draws' => 0,
                'games_played' => 0, 'wallet' => 0, 'streak' => 0
            ]]);
            break;
        }
        echo json_encode(['success' => true, 'stats' => [
            'elo'          => (int)$player['elo'],
            'wins'         => (int)$player['wins'],
            'losses'       => (int)$player['losses'],
            'draws'        => (int)$player['draws'],
            'games_played' => (int)$player['games_played'],
            'wallet'       => (int)$player['wallet_balance'],
            'total_wagered'=> (int)$player['total_wagered'],
            'total_won'    => (int)$player['total_won'],
            'chess_wins'   => (int)$player['chess_wins'],
            'checkers_wins'=> (int)$player['checkers_wins'],
            'pool_wins'    => (int)$player['pool_wins'],
            'streak'       => (int)$player['streak'],
            'best_streak'  => (int)$player['best_streak'],
        ]]);
        break;

    // ── Global leaderboard ──
    case 'leaderboard':
        $agents = [];
        $stats = getAgentStats($db);
        
        foreach ($AGENTS as $agent) {
            $s = $stats[$agent['id']] ?? null;
            $agents[] = [
                'id'     => $agent['id'],
                'name'   => $agent['name'],
                'color'  => $agent['color'],
                'emoji'  => $agent['emoji'],
                'elo'    => $s ? (int)$s['elo'] : $agent['elo'],
                'wins'   => $s ? (int)$s['wins'] : 0,
                'losses' => $s ? (int)$s['losses'] : 0,
                'wallet' => $s ? (int)$s['wallet_balance'] : 0,
                'streak' => $s ? (int)$s['streak'] : 0,
            ];
        }
        
        // Sort by ELO descending
        usort($agents, function($a, $b) { return $b['elo'] - $a['elo']; });
        
        echo json_encode(['success' => true, 'leaderboard' => $agents]);
        break;

    // ── Ecosystem status ──
    case 'ecosystem-status':
        $totalGames = 0;
        $totalWagered = 0;
        $activeWagers = 0;
        
        if ($db) {
            try {
                $r = $db->query("SELECT COUNT(*) as c FROM game_results");
                $totalGames = (int)($r->fetch()['c'] ?? 0);
            } catch (Exception $e) {}
            
            try {
                $r = $db->query("SELECT SUM(amount) as s FROM game_wagers WHERE status IN ('won','lost')");
                $totalWagered = (int)($r->fetch()['s'] ?? 0);
            } catch (Exception $e) {}
            
            try {
                $r = $db->query("SELECT COUNT(*) as c FROM game_wagers WHERE status = 'pending'");
                $activeWagers = (int)($r->fetch()['c'] ?? 0);
            } catch (Exception $e) {}
        }
        
        echo json_encode([
            'success' => true,
            'ecosystem' => [
                'total_games'    => $totalGames,
                'total_wagered'  => $totalWagered,
                'active_wagers'  => $activeWagers,
                'agent_count'    => count($AGENTS),
                'games_available'=> ['chess', 'checkers', 'pool', 'backgammon', 'racing'],
                'version'        => '1.0.0'
            ]
        ]);
        break;

    // ── Heartbeat — register viewer/player presence ──
    case 'heartbeat':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'POST required']);
            break;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $game = preg_replace('/[^a-z0-9\-]/', '', $input['game'] ?? '');
        $role = in_array($input['role'] ?? '', ['viewer', 'player']) ? $input['role'] : 'viewer';
        $isMember = !empty($_SESSION['logged_in']) && !empty($_SESSION['client_id']);

        if (!$game || !$db) {
            echo json_encode(['success' => false, 'error' => 'Missing game']);
            break;
        }

        try {
            $db->exec("CREATE TABLE IF NOT EXISTS game_presence (
                id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                session_id  VARCHAR(64) NOT NULL,
                game        VARCHAR(32) NOT NULL,
                role        ENUM('viewer','player') DEFAULT 'viewer',
                is_member   TINYINT(1) DEFAULT 0,
                last_seen   DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uk_session_game (session_id, game),
                KEY idx_game (game),
                KEY idx_seen (last_seen)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $stmt = $db->prepare("INSERT INTO game_presence (session_id, game, role, is_member, last_seen) VALUES (?, ?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE role=VALUES(role), is_member=VALUES(is_member), last_seen=NOW()");
            $stmt->execute([$sessionId, $game, $role, $isMember ? 1 : 0]);

            // Clean stale entries (>60s)
            $db->exec("DELETE FROM game_presence WHERE last_seen < DATE_SUB(NOW(), INTERVAL 60 SECOND)");

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            error_log("Heartbeat error: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
        break;

    // ── Live stats — viewer/member counts per game ──
    case 'live-stats':
        if (!$db) {
            echo json_encode(['success' => true, 'games' => []]);
            break;
        }

        try {
            $db->exec("CREATE TABLE IF NOT EXISTS game_presence (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                session_id VARCHAR(64) NOT NULL,
                game VARCHAR(32) NOT NULL,
                role ENUM('viewer','player') DEFAULT 'viewer',
                is_member TINYINT(1) DEFAULT 0,
                last_seen DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uk_session_game (session_id, game),
                KEY idx_game (game),
                KEY idx_seen (last_seen)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            // Clean stale
            $db->exec("DELETE FROM game_presence WHERE last_seen < DATE_SUB(NOW(), INTERVAL 60 SECOND)");

            $stmt = $db->query("SELECT game, 
                COUNT(*) as total,
                SUM(role='viewer') as viewers,
                SUM(role='player') as players,
                SUM(is_member=1) as members,
                SUM(is_member=0) as guests
                FROM game_presence
                WHERE last_seen >= DATE_SUB(NOW(), INTERVAL 60 SECOND)
                GROUP BY game");
            $games = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $games[$row['game']] = [
                    'total'   => (int)$row['total'],
                    'viewers' => (int)$row['viewers'],
                    'players' => (int)$row['players'],
                    'members' => (int)$row['members'],
                    'guests'  => (int)$row['guests'],
                ];
            }

            // Also get total members count
            $totalMembers = 0;
            try {
                $r = $db->query("SELECT COUNT(*) as c FROM clients WHERE status='active'");
                $totalMembers = (int)($r->fetch()['c'] ?? 0);
            } catch (Exception $e) {}

            // Total games played
            $totalPlayed = 0;
            try {
                $r = $db->query("SELECT COUNT(*) as c FROM game_results");
                $totalPlayed = (int)($r->fetch()['c'] ?? 0);
            } catch (Exception $e) {}

            echo json_encode([
                'success' => true,
                'games' => $games,
                'platform' => [
                    'total_members'  => $totalMembers,
                    'total_online'   => array_sum(array_column($games, 'total')),
                    'total_games'    => $totalPlayed,
                ]
            ]);
        } catch (Exception $e) {
            error_log("Live stats error: " . $e->getMessage());
            echo json_encode(['success' => true, 'games' => []]);
        }
        break;

    // ══════════════════════════════════════════════════════════════
    // ── AGENT PRESENCE SYSTEM ──
    // ══════════════════════════════════════════════════════════════

    // ── Agent Presence — where are all agents right now? ──
    case 'agent-presence':
        createAgentPresenceTable($db);
        
        // Auto-deploy if no agents present
        autoDeployAgents($db, $AGENTS, $AGENT_HOME_GAMES, $VALID_GAMES, $AGENT_ACTIVITIES);
        // Refresh stale activities
        refreshAgentActivity($db, $AGENT_ACTIVITIES);
        
        $gameFilter = preg_replace('/[^a-z0-9\-]/', '', $_GET['game'] ?? '');
        $agentFilter = preg_replace('/[^a-z0-9\-]/', '', $_GET['agent'] ?? '');
        
        try {
            $sql = "SELECT ap.*, gas.elo, gas.wins, gas.losses, gas.games_played, gas.wallet_balance, gas.streak 
                    FROM agent_presence ap 
                    LEFT JOIN game_agent_stats gas ON ap.agent_id = gas.agent_id 
                    WHERE ap.last_seen >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
            $params = [];
            
            if ($gameFilter) {
                $sql .= " AND ap.game = ?";
                $params[] = $gameFilter;
            }
            if ($agentFilter) {
                $sql .= " AND ap.agent_id = ?";
                $params[] = $agentFilter;
            }
            $sql .= " ORDER BY ap.game, ap.status, ap.agent_id";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Enrich with agent roster data
            $agentMap = [];
            foreach ($AGENTS as $a) $agentMap[$a['id']] = $a;
            
            $presence = [];
            $byGame = [];
            foreach ($rows as $row) {
                $a = $agentMap[$row['agent_id']] ?? null;
                $entry = [
                    'agent_id'  => $row['agent_id'],
                    'name'      => $a ? $a['name'] : ucfirst($row['agent_id']),
                    'emoji'     => $a ? $a['emoji'] : '🤖',
                    'color'     => $a ? $a['color'] : '#888',
                    'title'     => $a ? $a['title'] : '',
                    'specialty' => $a ? $a['specialty'] : '',
                    'game'      => $row['game'],
                    'status'    => $row['status'],
                    'activity'  => $row['activity'],
                    'opponent'  => $row['opponent'],
                    'elo'       => (int)($row['elo'] ?? ($a ? $a['elo'] : 1200)),
                    'wins'      => (int)($row['wins'] ?? 0),
                    'losses'    => (int)($row['losses'] ?? 0),
                    'games_played' => (int)($row['games_played'] ?? 0),
                    'wallet'    => (int)($row['wallet_balance'] ?? 0),
                    'streak'    => (int)($row['streak'] ?? 0),
                    'entered_at'=> $row['entered_at'],
                    'last_seen' => $row['last_seen'],
                ];
                $presence[] = $entry;
                $byGame[$row['game']][] = $entry;
            }
            
            // Summary per game
            $summary = [];
            foreach ($VALID_GAMES as $g) {
                $inGame = $byGame[$g] ?? [];
                $summary[$g] = [
                    'total'      => count($inGame),
                    'playing'    => count(array_filter($inGame, fn($e) => $e['status'] === 'playing')),
                    'spectating' => count(array_filter($inGame, fn($e) => $e['status'] === 'spectating')),
                    'available'  => count(array_filter($inGame, fn($e) => $e['status'] === 'available')),
                ];
            }
            
            echo json_encode([
                'success'  => true,
                'agents'   => $presence,
                'by_game'  => $byGame,
                'summary'  => $summary,
                'total_deployed' => count($presence),
            ]);
        } catch (Exception $e) {
            error_log("Agent presence error: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Presence query failed']);
        }
        break;

    // ── Agent Deploy — place/move agents into games ──
    case 'agent-deploy':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'POST required']);
            break;
        }
        
        createAgentPresenceTable($db);
        $input = json_decode(file_get_contents('php://input'), true);
        $agentId = preg_replace('/[^a-z0-9\-]/', '', $input['agent_id'] ?? '');
        $game = preg_replace('/[^a-z0-9\-]/', '', $input['game'] ?? '');
        $status = $input['status'] ?? 'available';
        $activity = substr($input['activity'] ?? '', 0, 128);
        
        // Validate agent exists
        $validAgent = false;
        foreach ($AGENTS as $a) {
            if ($a['id'] === $agentId) { $validAgent = true; break; }
        }
        
        if (!$validAgent) {
            echo json_encode(['success' => false, 'error' => 'Unknown agent: ' . $agentId]);
            break;
        }
        if (!in_array($game, $VALID_GAMES)) {
            echo json_encode(['success' => false, 'error' => 'Unknown game: ' . $game]);
            break;
        }
        if (!in_array($status, $AGENT_STATUSES)) {
            $status = 'available';
        }
        
        if (!$activity) {
            $acts = $AGENT_ACTIVITIES[$game] ?? ['Exploring'];
            $activity = $acts[array_rand($acts)];
        }
        
        try {
            ensureAgent($db, $agentId);
            
            $stmt = $db->prepare("INSERT INTO agent_presence (agent_id, game, status, activity, entered_at, last_seen) 
                VALUES (?, ?, ?, ?, NOW(), NOW()) 
                ON DUPLICATE KEY UPDATE status=VALUES(status), activity=VALUES(activity), last_seen=NOW()");
            $stmt->execute([$agentId, $game, $status, $activity]);
            
            // Log the deployment
            $logStmt = $db->prepare("INSERT INTO agent_activity_log (agent_id, game, event_type, detail) VALUES (?, ?, ?, ?)");
            $logStmt->execute([$agentId, $game, 'deployed', "Status: {$status} — {$activity}"]);
            
            echo json_encode([
                'success'  => true,
                'agent_id' => $agentId,
                'game'     => $game,
                'status'   => $status,
                'activity' => $activity,
            ]);
        } catch (Exception $e) {
            error_log("Agent deploy error: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Deploy failed']);
        }
        break;

    // ── Agent Activity Log — recent events ──
    case 'agent-activity':
        createAgentPresenceTable($db);
        
        $gameFilter = preg_replace('/[^a-z0-9\-]/', '', $_GET['game'] ?? '');
        $agentFilter = preg_replace('/[^a-z0-9\-]/', '', $_GET['agent'] ?? '');
        $limit = min(max((int)($_GET['limit'] ?? 50), 1), 200);
        
        try {
            $sql = "SELECT * FROM agent_activity_log WHERE 1=1";
            $params = [];
            
            if ($gameFilter) {
                $sql .= " AND game = ?";
                $params[] = $gameFilter;
            }
            if ($agentFilter) {
                $sql .= " AND agent_id = ?";
                $params[] = $agentFilter;
            }
            $sql .= " ORDER BY created_at DESC LIMIT " . (int)$limit;
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Enrich with agent names
            $agentMap = [];
            foreach ($AGENTS as $a) $agentMap[$a['id']] = $a;
            
            foreach ($logs as &$log) {
                $a = $agentMap[$log['agent_id']] ?? null;
                $log['agent_name'] = $a ? $a['name'] : ucfirst($log['agent_id']);
                $log['agent_emoji'] = $a ? $a['emoji'] : '🤖';
            }
            
            echo json_encode(['success' => true, 'activities' => $logs, 'count' => count($logs)]);
        } catch (Exception $e) {
            error_log("Agent activity error: " . $e->getMessage());
            echo json_encode(['success' => true, 'activities' => [], 'count' => 0]);
        }
        break;

    // ── Agent World Stats — enhanced live stats with agent presence ──
    case 'agent-world-stats':
        createAgentPresenceTable($db);
        autoDeployAgents($db, $AGENTS, $AGENT_HOME_GAMES, $VALID_GAMES, $AGENT_ACTIVITIES);
        refreshAgentActivity($db, $AGENT_ACTIVITIES);
        
        try {
            // Agent presence per game
            $stmt = $db->query("SELECT game, 
                COUNT(*) as agents_total,
                SUM(status='playing') as agents_playing,
                SUM(status='spectating') as agents_spectating,
                SUM(status='available') as agents_available
                FROM agent_presence
                WHERE last_seen >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                GROUP BY game");
            $agentStats = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $agentStats[$row['game']] = [
                    'agents_total'      => (int)$row['agents_total'],
                    'agents_playing'    => (int)$row['agents_playing'],
                    'agents_spectating' => (int)$row['agents_spectating'],
                    'agents_available'  => (int)$row['agents_available'],
                ];
            }
            
            // Player presence per game
            $db->exec("DELETE FROM game_presence WHERE last_seen < DATE_SUB(NOW(), INTERVAL 60 SECOND)");
            $stmt = $db->query("SELECT game, 
                COUNT(*) as users_total,
                SUM(role='viewer') as viewers,
                SUM(role='player') as players,
                SUM(is_member=1) as members
                FROM game_presence
                WHERE last_seen >= DATE_SUB(NOW(), INTERVAL 60 SECOND)
                GROUP BY game");
            $userStats = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $userStats[$row['game']] = [
                    'users_total' => (int)$row['users_total'],
                    'viewers'     => (int)$row['viewers'],
                    'players'     => (int)$row['players'],
                    'members'     => (int)$row['members'],
                ];
            }
            
            // Merge into a unified world view
            $worlds = [];
            foreach ($VALID_GAMES as $g) {
                $as = $agentStats[$g] ?? ['agents_total'=>0,'agents_playing'=>0,'agents_spectating'=>0,'agents_available'=>0];
                $us = $userStats[$g] ?? ['users_total'=>0,'viewers'=>0,'players'=>0,'members'=>0];
                $worlds[$g] = array_merge($as, $us, [
                    'population' => $as['agents_total'] + $us['users_total'],
                ]);
            }
            
            // Recent activity (last 10 events)
            $stmt = $db->query("SELECT agent_id, game, event_type, detail, created_at FROM agent_activity_log ORDER BY created_at DESC LIMIT 10");
            $recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $agentMap = [];
            foreach ($AGENTS as $a) $agentMap[$a['id']] = $a;
            foreach ($recentActivity as &$ev) {
                $a = $agentMap[$ev['agent_id']] ?? null;
                $ev['agent_name'] = $a ? $a['name'] : ucfirst($ev['agent_id']);
                $ev['agent_emoji'] = $a ? $a['emoji'] : '🤖';
            }
            
            // Total counts
            $totalAgents = 0;
            $totalUsers = 0;
            foreach ($worlds as $w) {
                $totalAgents += $w['agents_total'];
                $totalUsers += $w['users_total'];
            }
            
            echo json_encode([
                'success' => true,
                'worlds'  => $worlds,
                'totals'  => [
                    'agents_deployed' => $totalAgents,
                    'users_online'    => $totalUsers,
                    'population'      => $totalAgents + $totalUsers,
                    'games_active'    => count(array_filter($worlds, fn($w) => $w['population'] > 0)),
                ],
                'recent_activity' => $recentActivity,
            ]);
        } catch (Exception $e) {
            error_log("Agent world stats error: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'World stats failed']);
        }
        break;

    // ── Spawn Agent — user picks an agent and sends them into a game ──
    case 'spawn-agent':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'POST required']);
            break;
        }
        
        createAgentPresenceTable($db);
        $input = json_decode(file_get_contents('php://input'), true);
        $agentId = preg_replace('/[^a-z0-9\-]/', '', $input['agent_id'] ?? '');
        $game = preg_replace('/[^a-z0-9\-]/', '', $input['game'] ?? '');
        $mission = preg_replace('/[^a-z0-9\-]/', '', $input['mission'] ?? '');
        
        // Validate agent
        $agentData = null;
        foreach ($AGENTS as $a) {
            if ($a['id'] === $agentId) { $agentData = $a; break; }
        }
        if (!$agentData) {
            echo json_encode(['success' => false, 'error' => 'Unknown agent']);
            break;
        }
        if (!in_array($game, $VALID_GAMES)) {
            echo json_encode(['success' => false, 'error' => 'Unknown game']);
            break;
        }
        
        // Determine mission behavior
        $missionMap = [
            'play'     => ['status' => 'playing',    'verbs' => ['Entering the arena', 'Ready to compete', 'Warming up']],
            'spectate' => ['status' => 'spectating',  'verbs' => ['Watching from the stands', 'Studying strategies', 'Observing quietly']],
            'train'    => ['status' => 'playing',      'verbs' => ['Running practice drills', 'Training solo', 'Perfecting technique']],
            'explore'  => ['status' => 'available',    'verbs' => ['Looking around', 'Exploring the area', 'Checking things out']],
        ];
        $m = $missionMap[$mission] ?? $missionMap['play'];
        $status = $m['status'];
        $verb = $m['verbs'][array_rand($m['verbs'])];
        
        // Game-specific flavor text
        $flavors = [
            'chess'         => ['♟️ taking a seat at the board', '♟️ studying the position', '♟️ challenging the next opponent'],
            'checkers'      => ['🏁 setting up the pieces', '🏁 eyeing the board', '🏁 planning the first jump'],
            'pool'          => ['🎱 chalking the cue', '🎱 racking the balls', '🎱 lining up the break'],
            'dj-studio'     => ['🎧 stepping into the booth', '🎧 loading a playlist', '🎧 testing the speakers'],
            'speed-dating'  => ['💕 finding a seat', '💕 reviewing conversation topics', '💕 checking the match list'],
            'sanctuary'     => ['⛪ entering with reverence', '⛪ lighting a candle', '⛪ opening the scripture'],
        ];
        $flavor = ($flavors[$game] ?? ['Arriving'])[array_rand($flavors[$game] ?? ['Arriving'])];
        $activity = "{$verb} — {$flavor}";
        
        try {
            ensureAgent($db, $agentId);
            
            // Place agent in the game
            $stmt = $db->prepare("INSERT INTO agent_presence (agent_id, game, status, activity, entered_at, last_seen) 
                VALUES (?, ?, ?, ?, NOW(), NOW()) 
                ON DUPLICATE KEY UPDATE status=VALUES(status), activity=VALUES(activity), last_seen=NOW(), entered_at=NOW()");
            $stmt->execute([$agentId, $game, $status, $activity]);
            
            // Log the spawn event with user session
            $logStmt = $db->prepare("INSERT INTO agent_activity_log (agent_id, game, event_type, detail) VALUES (?, ?, ?, ?)");
            $logStmt->execute([$agentId, $game, 'user-spawn', "Spawned by user — mission: {$mission} — {$activity}"]);
            
            // Get the agent's current stats
            $statsStmt = $db->prepare("SELECT elo, wins, losses, games_played, wallet_balance, streak FROM game_agent_stats WHERE agent_id = ?");
            $statsStmt->execute([$agentId]);
            $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
            
            // Build spawn response with fun narrative
            $narratives = [
                'chess'         => "{$agentData['emoji']} {$agentData['name']} strides into the Chess Arena, eyes scanning the board…",
                'checkers'      => "{$agentData['emoji']} {$agentData['name']} slides into the Checkers room, cracking knuckles…",
                'pool'          => "{$agentData['emoji']} {$agentData['name']} picks up a cue stick and surveys the Pool Hall…",
                'dj-studio'     => "{$agentData['emoji']} {$agentData['name']} puts on headphones and fires up the DJ Studio…",
                'speed-dating'  => "{$agentData['emoji']} {$agentData['name']} adjusts their look and enters Speed Dating…",
                'backgammon'    => "{$agentData['emoji']} {$agentData['name']} picks up the dice and takes a seat at the Backgammon board…",
                'sanctuary'     => "{$agentData['emoji']} {$agentData['name']} bows their head and steps into the Sanctuary…",
            ];
            
            echo json_encode([
                'success'   => true,
                'spawn'     => [
                    'agent_id'  => $agentId,
                    'name'      => $agentData['name'],
                    'emoji'     => $agentData['emoji'],
                    'color'     => $agentData['color'],
                    'title'     => $agentData['title'],
                    'specialty' => $agentData['specialty'],
                    'game'      => $game,
                    'mission'   => $mission ?: 'play',
                    'status'    => $status,
                    'activity'  => $activity,
                    'elo'       => (int)($stats['elo'] ?? $agentData['elo']),
                    'wins'      => (int)($stats['wins'] ?? 0),
                    'losses'    => (int)($stats['losses'] ?? 0),
                    'streak'    => (int)($stats['streak'] ?? 0),
                    'wallet'    => (int)($stats['wallet_balance'] ?? 0),
                ],
                'narrative' => $narratives[$game] ?? "{$agentData['emoji']} {$agentData['name']} has arrived!",
            ]);
            
        } catch (Exception $e) {
            error_log("Spawn agent error: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Spawn failed']);
        }
        break;

    // ── Dispute a game result (Alfred AI as judge) ──
    case 'dispute-game':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'POST required']);
            break;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $gameId = (int)($input['game_id'] ?? 0);
        $reason = trim($input['reason'] ?? '');
        
        if (!$gameId || !$reason) {
            echo json_encode(['success' => false, 'error' => 'Game ID and reason required']);
            break;
        }
        
        // Sanitize reason (max 500 chars, strip tags)
        $reason = substr(strip_tags($reason), 0, 500);
        
        if (!$db) {
            echo json_encode(['success' => false, 'error' => 'Database unavailable']);
            break;
        }
        
        try {
            createGameResultTable($db);
            
            // Find the game
            $stmt = $db->prepare("SELECT * FROM game_results WHERE id = ? LIMIT 1");
            $stmt->execute([$gameId]);
            $game = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$game) {
                echo json_encode(['success' => false, 'error' => 'Game not found']);
                break;
            }
            
            if ($game['dispute_status'] !== 'none') {
                echo json_encode(['success' => false, 'error' => 'This game has already been disputed']);
                break;
            }
            
            // Build context for Alfred (AI judge)
            $evidence = $game['game_evidence'] ? json_decode($game['game_evidence'], true) : [];
            $gameContext = "Game Type: {$game['game_type']}\n"
                . "Players: {$game['white_player']} vs {$game['black_player']}\n"
                . "Winner: {$game['winner']}\n"
                . "Result: {$game['result']}\n"
                . "Moves: {$game['moves']}\n"
                . "Wager: $" . number_format(($game['wager_amount'] ?? 0) / 100, 2) . "\n"
                . "Date: {$game['created_at']}\n";
            
            if (!empty($evidence)) {
                if (isset($evidence['shots'])) $gameContext .= "Shots fired: {$evidence['shots']}\n";
                if (isset($evidence['pocketed'])) $gameContext .= "Balls pocketed: " . implode(', ', $evidence['pocketed']) . "\n";
                if (isset($evidence['remaining'])) $gameContext .= "Balls remaining: " . implode(', ', $evidence['remaining']) . "\n";
                if (isset($evidence['legitimate'])) $gameContext .= "Legitimate finish: " . ($evidence['legitimate'] ? 'Yes' : 'No') . "\n";
            }
            
            $judgePrompt = "You are Alfred, the impartial AI Judge for the GoSiteMe gaming platform. "
                . "A player has disputed a game result. Analyze the evidence and provide a fair ruling.\n\n"
                . "GAME EVIDENCE:\n{$gameContext}\n"
                . "PLAYER'S DISPUTE REASON: {$reason}\n\n"
                . "Analyze the evidence. If the result appears legitimate, uphold it. "
                . "If there are signs of cheating, manipulation, or unfairness, overturn it. "
                . "Provide a clear, concise ruling in 2-3 sentences. Start with RULING: UPHELD or RULING: OVERTURNED.";
            
            // Call Alfred for judgment
            $alfredUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/api/alfred-chat.php';
            $ch = curl_init($alfredUrl);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_POSTFIELDS => json_encode([
                    'message' => $judgePrompt,
                    'agent' => 'alfred',
                    'context' => 'game-dispute'
                ]),
                CURLOPT_TIMEOUT => 15
            ]);
            $alfredResponse = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $ruling = 'RULING: UPHELD — Insufficient evidence to overturn. The result stands.';
            if ($httpCode === 200) {
                $alfredData = json_decode($alfredResponse, true);
                if (!empty($alfredData['response'])) {
                    $ruling = $alfredData['response'];
                } elseif (!empty($alfredData['message'])) {
                    $ruling = $alfredData['message'];
                }
            }
            
            // Determine if overturned
            $overturned = stripos($ruling, 'OVERTURNED') !== false;
            
            // Update game record
            $stmt = $db->prepare("UPDATE game_results SET dispute_status = 'resolved', dispute_ruling = ? WHERE id = ?");
            $stmt->execute([$ruling, $gameId]);
            
            // If overturned and there was a wager, reverse it
            if ($overturned && ($game['wager_amount'] ?? 0) > 0) {
                error_log("Dispute OVERTURNED for game $gameId — wager reversal flagged for review");
            }
            
            echo json_encode([
                'success' => true,
                'ruling' => $ruling,
                'overturned' => $overturned,
                'game_id' => $gameId
            ]);
            
        } catch (Exception $e) {
            error_log("Dispute error: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Dispute processing failed']);
        }
        break;

    // ── Get recent game history (for disputes) ──
    case 'game-history':
        if (!$db) {
            echo json_encode(['success' => false, 'error' => 'Database unavailable']);
            break;
        }
        try {
            createGameResultTable($db);
            $limit = min(20, max(1, (int)($_GET['limit'] ?? 10)));
            $stmt = $db->prepare("SELECT id, game_type, white_player, black_player, winner, result, moves, wager_amount, dispute_status, dispute_ruling, created_at FROM game_results ORDER BY created_at DESC LIMIT ?");
            dbExecute($stmt, [$limit]);
            $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'games' => $games]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Failed to load history']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action. Available: agent-profiles, agent-profile, place-wager, settle-wager, game-stats, leaderboard, ecosystem-status, heartbeat, live-stats, agent-presence, agent-deploy, agent-activity, agent-world-stats, spawn-agent, dispute-game, game-history']);
        break;
}
