<?php
/**
 * NFT Trophies API — Phase 4d
 * Compressed Solana NFTs for tournament wins, achievements, and rare milestones
 * 
 * Actions: my-trophies, trophy-detail, available-trophies, mint-trophy,
 *          trophy-gallery, trophy-stats, admin-create-trophy, admin-award
 */
define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

apiRateLimit(30, 60, 'nft-trophies');
session_start();

// ── Constants ──
const TROPHY_CATEGORIES = ['tournament', 'achievement', 'milestone', 'seasonal', 'special', 'legendary'];
const TROPHY_RARITY = ['common', 'uncommon', 'rare', 'epic', 'legendary', 'mythic'];
const RARITY_GSM_COST = [
    'common'    => 5,
    'uncommon'  => 15,
    'rare'      => 50,
    'epic'      => 150,
    'legendary' => 500,
    'mythic'    => 2000,
];
// Games that can trigger trophies
const TROPHY_GAMES = ['chess', 'checkers', 'pool', 'backgammon', 'poker', 'cnc', 'metadome'];

// ── Auth ──
function trophyRequireAuth(): array {
    if (!empty($_SESSION['logged_in']) && !empty($_SESSION['client_id'])) {
        return [
            'client_id' => (int)$_SESSION['client_id'],
            'name'      => $_SESSION['client_name'] ?? '',
            'email'     => $_SESSION['client_email'] ?? '',
        ];
    }
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/^Bearer\s+(.+)$/', $auth, $m)) {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, CONCAT(firstname,' ',lastname) AS name, email 
                              FROM clients WHERE api_key = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$m[1]]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($u) return ['client_id' => (int)$u['id'], 'name' => $u['name'], 'email' => $u['email']];
    }
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

function trophyIsCommander(): bool {
    return ($_SESSION['client_id'] ?? 0) == 33;
}

// ── Tables ──
function ensureTrophyTables(PDO $db): void {
    // Trophy definitions (templates)
    $db->exec("CREATE TABLE IF NOT EXISTS nft_trophy_definitions (
        id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        slug            VARCHAR(100) NOT NULL UNIQUE,
        name            VARCHAR(200) NOT NULL,
        description     TEXT NOT NULL,
        category        ENUM('tournament','achievement','milestone','seasonal','special','legendary') DEFAULT 'achievement',
        rarity          ENUM('common','uncommon','rare','epic','legendary','mythic') DEFAULT 'common',
        game_type       VARCHAR(50) DEFAULT NULL,
        image_url       VARCHAR(500) DEFAULT NULL,
        metadata_json   JSON DEFAULT NULL,
        max_supply      INT UNSIGNED DEFAULT NULL,
        current_supply  INT UNSIGNED NOT NULL DEFAULT 0,
        mint_cost_gsm   DECIMAL(20,9) NOT NULL DEFAULT 0,
        auto_award      TINYINT(1) NOT NULL DEFAULT 0,
        auto_condition  JSON DEFAULT NULL,
        is_active       TINYINT(1) NOT NULL DEFAULT 1,
        created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY idx_category (category, is_active),
        KEY idx_rarity (rarity),
        KEY idx_game (game_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Awarded trophies (instances)
    $db->exec("CREATE TABLE IF NOT EXISTS nft_trophy_awards (
        id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        trophy_def_id   INT UNSIGNED NOT NULL,
        client_id       INT UNSIGNED NOT NULL,
        serial_number   INT UNSIGNED NOT NULL,
        earned_via      VARCHAR(100) DEFAULT NULL,
        earned_data     JSON DEFAULT NULL,
        nft_minted      TINYINT(1) NOT NULL DEFAULT 0,
        nft_mint_addr   VARCHAR(100) DEFAULT NULL,
        nft_mint_tx     VARCHAR(100) DEFAULT NULL,
        minted_at       DATETIME DEFAULT NULL,
        gsm_paid        DECIMAL(20,9) NOT NULL DEFAULT 0,
        is_displayed    TINYINT(1) NOT NULL DEFAULT 1,
        created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY idx_client (client_id),
        KEY idx_def (trophy_def_id),
        UNIQUE KEY uk_def_serial (trophy_def_id, serial_number),
        UNIQUE KEY uk_def_client (trophy_def_id, client_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Trophy showcase (user's display order)
    $db->exec("CREATE TABLE IF NOT EXISTS nft_trophy_showcase (
        id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        client_id       INT UNSIGNED NOT NULL,
        award_id        INT UNSIGNED NOT NULL,
        display_order   INT NOT NULL DEFAULT 0,
        UNIQUE KEY uk_showcase (client_id, award_id),
        KEY idx_client_order (client_id, display_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Seed default trophies if empty
    $count = $db->query("SELECT COUNT(*) FROM nft_trophy_definitions")->fetchColumn();
    if ($count == 0) {
        seedDefaultTrophies($db);
    }
}

function seedDefaultTrophies(PDO $db): void {
    $trophies = [
        // Achievement trophies (auto-awarded)
        ['first_win', 'First Victory', 'Won your first game against an AI opponent', 'achievement', 'common', null, 5, 1, '{"type":"wins","count":1}'],
        ['ten_wins', 'Seasoned Player', 'Won 10 games across any game type', 'achievement', 'uncommon', null, 15, 1, '{"type":"wins","count":10}'],
        ['fifty_wins', 'Veteran Champion', 'Won 50 games — a true competitor', 'achievement', 'rare', null, 50, 1, '{"type":"wins","count":50}'],
        ['hundred_wins', 'Century Legend', 'Won 100 games — legendary status', 'achievement', 'epic', null, 150, 1, '{"type":"wins","count":100}'],
        ['streak_five', 'Hot Streak', 'Won 5 games in a row', 'milestone', 'rare', null, 50, 1, '{"type":"streak","count":5}'],
        ['streak_ten', 'Unstoppable', 'Won 10 games in a row — unstoppable force', 'milestone', 'epic', null, 150, 1, '{"type":"streak","count":10}'],
        ['high_roller', 'High Roller', 'Wagered 1000+ GSM in total', 'milestone', 'rare', null, 50, 1, '{"type":"wagered_gsm","amount":1000}'],
        ['whale', 'Whale', 'Wagered 10,000+ GSM in total', 'milestone', 'legendary', null, 500, 1, '{"type":"wagered_gsm","amount":10000}'],

        // Game-specific trophies
        ['chess_master', 'Chess Master', 'Won 25 chess games', 'achievement', 'rare', 'chess', 50, 1, '{"type":"game_wins","game":"chess","count":25}'],
        ['pool_shark', 'Pool Shark', 'Won 25 pool games', 'achievement', 'rare', 'pool', 50, 1, '{"type":"game_wins","game":"pool","count":25}'],
        ['checkers_king', 'Checkers King', 'Won 25 checkers games', 'achievement', 'rare', 'checkers', 50, 1, '{"type":"game_wins","game":"checkers","count":25}'],
        ['backgammon_lord', 'Backgammon Lord', 'Won 25 backgammon games', 'achievement', 'rare', 'backgammon', 50, 1, '{"type":"game_wins","game":"backgammon","count":25}'],
        ['poker_ace', 'Poker Ace', 'Won 25 poker sessions profitably', 'achievement', 'rare', 'poker', 50, 1, '{"type":"game_wins","game":"poker","count":25}'],
        ['all_rounder', 'All-Rounder', 'Won at least 5 games in every game type', 'achievement', 'epic', null, 150, 1, '{"type":"all_games","min_wins":5}'],

        // Tournament trophies (manually awarded)
        ['tourney_gold', 'Tournament Champion', 'First place in an official tournament', 'tournament', 'legendary', null, 0, 0, null],
        ['tourney_silver', 'Tournament Runner-Up', 'Second place in an official tournament', 'tournament', 'epic', null, 0, 0, null],
        ['tourney_bronze', 'Tournament Top 3', 'Third place in an official tournament', 'tournament', 'rare', null, 0, 0, null],

        // Seasonal / Special (manually awarded, limited supply)
        ['beta_tester', 'Beta Pioneer', 'Participated in the GSM Economy beta test', 'special', 'epic', null, 0, 0, null],
        ['founding_member', 'Founding Member', 'One of the first 100 GSM token holders', 'special', 'legendary', null, 0, 0, null],
        ['land_baron', 'Land Baron', 'Owns 10+ VR land plots in the MetaDome', 'milestone', 'legendary', null, 500, 1, '{"type":"land_plots","count":10}'],
    ];

    $stmt = $db->prepare("INSERT INTO nft_trophy_definitions 
                          (slug, name, description, category, rarity, game_type, mint_cost_gsm, auto_award, auto_condition)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($trophies as $t) {
        $stmt->execute($t);
    }
}

// ── GSM Helpers ──
function debitGSMForTrophy(PDO $db, int $clientId, float $amount, string $desc): bool {
    $stmt = $db->prepare("UPDATE crypto_gsm_balances 
                          SET available_balance = available_balance - ?, total_spent = total_spent + ?, updated_at = NOW()
                          WHERE client_id = ? AND available_balance >= ?");
    $stmt->execute([$amount, $amount, $clientId, $amount]);
    if ($stmt->rowCount() === 0) return false;

    $db->prepare("INSERT INTO crypto_gsm_ledger (client_id, tx_type, amount, balance_after, description, created_at)
                  VALUES (?, 'nft_mint', ?, (SELECT available_balance FROM crypto_gsm_balances WHERE client_id = ?), ?, NOW())")
        ->execute([$clientId, -$amount, $clientId, $desc]);
    return true;
}

// ── Award a trophy to a user ──
function awardTrophy(PDO $db, int $clientId, string $slug, string $earnedVia = '', array $earnedData = []): array {
    // Get trophy definition
    $defStmt = $db->prepare("SELECT * FROM nft_trophy_definitions WHERE slug = ? AND is_active = 1");
    $defStmt->execute([$slug]);
    $def = $defStmt->fetch(PDO::FETCH_ASSOC);
    if (!$def) return ['success' => false, 'error' => 'Trophy not found'];

    // Check if already awarded
    $checkStmt = $db->prepare("SELECT id FROM nft_trophy_awards WHERE trophy_def_id = ? AND client_id = ?");
    $checkStmt->execute([$def['id'], $clientId]);
    if ($checkStmt->fetch()) return ['success' => false, 'error' => 'Already earned'];

    // Check max supply
    if ($def['max_supply'] !== null && (int)$def['current_supply'] >= (int)$def['max_supply']) {
        return ['success' => false, 'error' => 'Trophy supply exhausted'];
    }

    $serialNumber = (int)$def['current_supply'] + 1;

    $db->prepare("INSERT INTO nft_trophy_awards 
                  (trophy_def_id, client_id, serial_number, earned_via, earned_data, created_at)
                  VALUES (?, ?, ?, ?, ?, NOW())")
       ->execute([$def['id'], $clientId, $serialNumber, $earnedVia, json_encode($earnedData)]);

    $db->prepare("UPDATE nft_trophy_definitions SET current_supply = current_supply + 1 WHERE id = ?")
       ->execute([$def['id']]);

    return [
        'success' => true,
        'trophy' => $def['name'],
        'rarity' => $def['rarity'],
        'serial' => $serialNumber,
        'award_id' => (int)$db->lastInsertId(),
    ];
}

// ── Check and auto-award trophies based on stats ──
function checkAutoTrophies(PDO $db, int $clientId): array {
    $awarded = [];

    // Get all auto-award trophies
    $autoStmt = $db->query("SELECT * FROM nft_trophy_definitions WHERE auto_award = 1 AND is_active = 1");
    $autoDefs = $autoStmt->fetchAll(PDO::FETCH_ASSOC);

    // Gather player stats once
    $stats = getPlayerStats($db, $clientId);

    foreach ($autoDefs as $def) {
        // Skip if already awarded
        $checkStmt = $db->prepare("SELECT id FROM nft_trophy_awards WHERE trophy_def_id = ? AND client_id = ?");
        $checkStmt->execute([$def['id'], $clientId]);
        if ($checkStmt->fetch()) continue;

        $condition = json_decode($def['auto_condition'], true);
        if (!$condition) continue;

        $qualified = false;
        switch ($condition['type'] ?? '') {
            case 'wins':
                $qualified = ($stats['total_wins'] ?? 0) >= ($condition['count'] ?? PHP_INT_MAX);
                break;
            case 'streak':
                $qualified = ($stats['best_streak'] ?? 0) >= ($condition['count'] ?? PHP_INT_MAX);
                break;
            case 'wagered_gsm':
                $qualified = ($stats['total_wagered'] ?? 0) >= ($condition['amount'] ?? PHP_INT_MAX);
                break;
            case 'game_wins':
                $game = $condition['game'] ?? '';
                $qualified = ($stats['game_wins'][$game] ?? 0) >= ($condition['count'] ?? PHP_INT_MAX);
                break;
            case 'all_games':
                $minWins = $condition['min_wins'] ?? 5;
                $qualified = true;
                foreach (TROPHY_GAMES as $g) {
                    if ($g === 'cnc' || $g === 'metadome') continue; // Skip non-wager games
                    if (($stats['game_wins'][$g] ?? 0) < $minWins) { $qualified = false; break; }
                }
                break;
            case 'land_plots':
                $plotCount = $db->prepare("SELECT COUNT(*) FROM vr_land_plots WHERE owner_client_id = ?");
                $plotCount->execute([$clientId]);
                $qualified = (int)$plotCount->fetchColumn() >= ($condition['count'] ?? PHP_INT_MAX);
                break;
        }

        if ($qualified) {
            $result = awardTrophy($db, $clientId, $def['slug'], 'auto', ['condition' => $condition, 'stats' => $stats]);
            if ($result['success']) $awarded[] = $result;
        }
    }

    return $awarded;
}

function getPlayerStats(PDO $db, int $clientId): array {
    $stats = ['total_wins' => 0, 'best_streak' => 0, 'total_wagered' => 0, 'game_wins' => []];

    // From unified wagers
    try {
        $stmt = $db->prepare("SELECT game_type, 
                                     SUM(CASE WHEN result = 'won' THEN 1 ELSE 0 END) as wins,
                                     SUM(amount_gsm) as wagered
                              FROM game_unified_wagers WHERE client_id = ? AND status = 'settled'
                              GROUP BY game_type");
        $stmt->execute([$clientId]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats['game_wins'][$row['game_type']] = (int)$row['wins'];
            $stats['total_wins'] += (int)$row['wins'];
            $stats['total_wagered'] += (float)$row['wagered'];
        }
    } catch (\Exception $e) { /* table may not exist yet */ }

    // From unified stats
    try {
        $streakStmt = $db->prepare("SELECT MAX(best_streak) as ms FROM game_unified_stats WHERE client_id = ?");
        $streakStmt->execute([$clientId]);
        $streakRow = $streakStmt->fetch(PDO::FETCH_ASSOC);
        $stats['best_streak'] = (int)($streakRow['ms'] ?? 0);
    } catch (\Exception $e) {}

    // Also check legacy chess_wagers
    try {
        $legacyStmt = $db->prepare("SELECT SUM(CASE WHEN result = 'player_win' THEN 1 ELSE 0 END) as wins
                                    FROM chess_wagers WHERE user_id = ? AND status = 'settled'");
        $legacyStmt->execute([$clientId]);
        $legacyRow = $legacyStmt->fetch(PDO::FETCH_ASSOC);
        $stats['game_wins']['chess'] = ($stats['game_wins']['chess'] ?? 0) + (int)($legacyRow['wins'] ?? 0);
        $stats['total_wins'] += (int)($legacyRow['wins'] ?? 0);
    } catch (\Exception $e) {}

    return $stats;
}

// ── Main ──
$db = getDB();
if (!$db) { echo json_encode(['error' => 'Database unavailable']); exit; }
ensureTrophyTables($db);

$action = $_GET['action'] ?? '';

switch ($action) {

    // ── My trophies ──
    case 'my-trophies':
        $user = trophyRequireAuth();

        // Auto-check for new trophies
        $newAwards = checkAutoTrophies($db, $user['client_id']);

        $stmt = $db->prepare("SELECT a.id as award_id, a.serial_number, a.earned_via, a.nft_minted, 
                                     a.nft_mint_addr, a.is_displayed, a.created_at as earned_at,
                                     d.slug, d.name, d.description, d.category, d.rarity, d.game_type,
                                     d.image_url, d.current_supply, d.max_supply
                              FROM nft_trophy_awards a
                              JOIN nft_trophy_definitions d ON d.id = a.trophy_def_id
                              WHERE a.client_id = ?
                              ORDER BY FIELD(d.rarity,'mythic','legendary','epic','rare','uncommon','common'), a.created_at DESC");
        $stmt->execute([$user['client_id']]);
        $trophies = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Stats
        $rarityCounts = [];
        foreach ($trophies as $t) {
            $rarityCounts[$t['rarity']] = ($rarityCounts[$t['rarity']] ?? 0) + 1;
        }

        echo json_encode([
            'success' => true,
            'trophies' => $trophies,
            'total' => count($trophies),
            'by_rarity' => $rarityCounts,
            'new_awards' => $newAwards,
        ]);
        break;

    // ── Trophy detail ──
    case 'trophy-detail':
        $awardId = (int)($_GET['award_id'] ?? 0);
        $defId   = (int)($_GET['trophy_id'] ?? 0);

        if ($awardId > 0) {
            $stmt = $db->prepare("SELECT a.*, d.slug, d.name, d.description, d.category, d.rarity, 
                                         d.game_type, d.image_url, d.metadata_json, d.current_supply, d.max_supply,
                                         c.firstname as owner_name
                                  FROM nft_trophy_awards a
                                  JOIN nft_trophy_definitions d ON d.id = a.trophy_def_id
                                  LEFT JOIN clients c ON c.id = a.client_id
                                  WHERE a.id = ?");
            $stmt->execute([$awardId]);
        } elseif ($defId > 0) {
            $stmt = $db->prepare("SELECT d.*, NULL as award_id, NULL as serial_number, NULL as earned_via,
                                         NULL as nft_minted, NULL as nft_mint_addr
                                  FROM nft_trophy_definitions d WHERE d.id = ?");
            $stmt->execute([$defId]);
        } else {
            echo json_encode(['error' => 'Provide award_id or trophy_id']);
            break;
        }

        $trophy = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$trophy) { echo json_encode(['error' => 'Trophy not found']); break; }
        if (!empty($trophy['metadata_json'])) $trophy['metadata'] = json_decode($trophy['metadata_json'], true);

        // Recent holders
        $holdersStmt = $db->prepare("SELECT c.firstname as name, a.serial_number, a.created_at as earned_at
                                     FROM nft_trophy_awards a
                                     JOIN clients c ON c.id = a.client_id
                                     WHERE a.trophy_def_id = ?
                                     ORDER BY a.serial_number ASC LIMIT 20");
        $holdersStmt->execute([$trophy['trophy_def_id'] ?? $trophy['id']]);
        $holders = $holdersStmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'trophy' => $trophy, 'holders' => $holders]);
        break;

    // ── Available trophies (catalog) ──
    case 'available-trophies':
        $category = $_GET['category'] ?? '';
        $rarity   = $_GET['rarity'] ?? '';
        $game     = $_GET['game'] ?? '';

        $where = ['d.is_active = 1'];
        $params = [];
        if ($category && in_array($category, TROPHY_CATEGORIES, true)) { $where[] = 'd.category = ?'; $params[] = $category; }
        if ($rarity && in_array($rarity, TROPHY_RARITY, true)) { $where[] = 'd.rarity = ?'; $params[] = $rarity; }
        if ($game && in_array($game, TROPHY_GAMES, true)) { $where[] = 'd.game_type = ?'; $params[] = $game; }

        $wSQL = implode(' AND ', $where);
        $stmt = $db->prepare("SELECT d.id, d.slug, d.name, d.description, d.category, d.rarity, d.game_type,
                                     d.image_url, d.max_supply, d.current_supply, d.mint_cost_gsm, d.auto_award
                              FROM nft_trophy_definitions d WHERE $wSQL
                              ORDER BY FIELD(d.rarity,'mythic','legendary','epic','rare','uncommon','common'), d.name");
        $stmt->execute($params);
        $trophies = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // If user is logged in, mark which ones they already have
        $owned = [];
        if (!empty($_SESSION['client_id'])) {
            $ownedStmt = $db->prepare("SELECT trophy_def_id FROM nft_trophy_awards WHERE client_id = ?");
            $ownedStmt->execute([$_SESSION['client_id']]);
            while ($row = $ownedStmt->fetch(PDO::FETCH_ASSOC)) { $owned[] = (int)$row['trophy_def_id']; }
        }
        foreach ($trophies as &$t) { $t['owned'] = in_array((int)$t['id'], $owned, true); }

        echo json_encode([
            'success' => true,
            'trophies' => $trophies,
            'categories' => TROPHY_CATEGORIES,
            'rarities' => TROPHY_RARITY,
            'games' => TROPHY_GAMES,
        ]);
        break;

    // ── Mint trophy as on-chain NFT ──
    case 'mint-trophy':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        requireCSRF();
        $user = trophyRequireAuth();
        $data = json_decode(file_get_contents('php://input'), true) ?: [];

        $awardId = (int)($data['award_id'] ?? 0);
        if ($awardId <= 0) { echo json_encode(['error' => 'Invalid award_id']); break; }

        $stmt = $db->prepare("SELECT a.*, d.name, d.rarity, d.mint_cost_gsm, d.slug, d.description, d.metadata_json
                              FROM nft_trophy_awards a
                              JOIN nft_trophy_definitions d ON d.id = a.trophy_def_id
                              WHERE a.id = ? AND a.client_id = ?");
        $stmt->execute([$awardId, $user['client_id']]);
        $award = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$award) { echo json_encode(['error' => 'Trophy award not found']); break; }
        if ($award['nft_minted']) { echo json_encode(['error' => 'Already minted', 'mint_addr' => $award['nft_mint_addr']]); break; }

        $cost = (float)$award['mint_cost_gsm'];

        $db->beginTransaction();
        try {
            // Charge GSM for minting if cost > 0
            if ($cost > 0) {
                if (!debitGSMForTrophy($db, $user['client_id'], $cost, 'Minted NFT: ' . $award['name'])) {
                    $db->rollBack();
                    echo json_encode(['error' => 'Insufficient GSM for mint cost (' . $cost . ' GSM)']);
                    break;
                }
            }

            // Generate a placeholder mint address (real Solana minting requires Metaplex/Bubblegum integration)
            $mintAddr = 'gsm_nft_' . bin2hex(random_bytes(16));

            $db->prepare("UPDATE nft_trophy_awards SET nft_minted = 1, nft_mint_addr = ?, gsm_paid = ?, minted_at = NOW() WHERE id = ?")
               ->execute([$mintAddr, $cost, $awardId]);

            $db->commit();

            // Build NFT metadata
            $metadata = [
                'name' => $award['name'] . ' #' . $award['serial_number'],
                'symbol' => 'GSMTROPHY',
                'description' => $award['description'],
                'attributes' => [
                    ['trait_type' => 'Rarity', 'value' => $award['rarity']],
                    ['trait_type' => 'Serial', 'value' => $award['serial_number']],
                    ['trait_type' => 'Slug', 'value' => $award['slug']],
                    ['trait_type' => 'Earned Via', 'value' => $award['earned_via'] ?? 'manual'],
                ],
            ];

            echo json_encode([
                'success' => true,
                'message' => 'Trophy minted as NFT',
                'mint_addr' => $mintAddr,
                'gsm_cost' => $cost,
                'metadata' => $metadata,
                'note' => 'Placeholder mint — Solana compressed NFT minting will be activated when Metaplex integration is live',
            ]);
        } catch (\Exception $e) {
            $db->rollBack();
            echo json_encode(['error' => 'Mint failed: ' . $e->getMessage()]);
        }
        break;

    // ── Public trophy gallery ──
    case 'trophy-gallery':
        $clientId = (int)($_GET['client_id'] ?? 0);
        if ($clientId <= 0) { echo json_encode(['error' => 'client_id required']); break; }

        $stmt = $db->prepare("SELECT a.serial_number, a.nft_minted, a.nft_mint_addr, a.created_at as earned_at,
                                     d.slug, d.name, d.description, d.category, d.rarity, d.image_url,
                                     d.current_supply, d.max_supply
                              FROM nft_trophy_awards a
                              JOIN nft_trophy_definitions d ON d.id = a.trophy_def_id
                              WHERE a.client_id = ? AND a.is_displayed = 1
                              ORDER BY FIELD(d.rarity,'mythic','legendary','epic','rare','uncommon','common'), a.created_at DESC");
        $stmt->execute([$clientId]);
        $trophies = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Owner info
        $ownerStmt = $db->prepare("SELECT firstname as name FROM clients WHERE id = ?");
        $ownerStmt->execute([$clientId]);
        $owner = $ownerStmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'owner' => $owner ? $owner['name'] : 'Unknown',
            'trophies' => $trophies,
            'total' => count($trophies),
        ]);
        break;

    // ── Global trophy stats (public) ──
    case 'trophy-stats':
        $stats = [];

        // Total definitions and awards
        $stats['total_definitions'] = (int)$db->query("SELECT COUNT(*) FROM nft_trophy_definitions WHERE is_active = 1")->fetchColumn();
        $stats['total_awarded'] = (int)$db->query("SELECT COUNT(*) FROM nft_trophy_awards")->fetchColumn();
        $stats['total_minted'] = (int)$db->query("SELECT COUNT(*) FROM nft_trophy_awards WHERE nft_minted = 1")->fetchColumn();
        $stats['unique_holders'] = (int)$db->query("SELECT COUNT(DISTINCT client_id) FROM nft_trophy_awards")->fetchColumn();

        // By rarity
        $stmt = $db->query("SELECT d.rarity, COUNT(a.id) as awarded
                            FROM nft_trophy_definitions d
                            LEFT JOIN nft_trophy_awards a ON a.trophy_def_id = d.id
                            WHERE d.is_active = 1
                            GROUP BY d.rarity
                            ORDER BY FIELD(d.rarity,'mythic','legendary','epic','rare','uncommon','common')");
        $stats['by_rarity'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Most collected trophies
        $stmt = $db->query("SELECT d.name, d.rarity, d.slug, COUNT(a.id) as holders, d.max_supply
                            FROM nft_trophy_definitions d
                            JOIN nft_trophy_awards a ON a.trophy_def_id = d.id
                            GROUP BY d.id ORDER BY holders DESC LIMIT 10");
        $stats['most_collected'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Top collectors
        $stmt = $db->query("SELECT c.firstname as name, COUNT(a.id) as trophies,
                                   SUM(CASE WHEN d.rarity IN ('legendary','mythic') THEN 1 ELSE 0 END) as legendaries
                            FROM nft_trophy_awards a
                            JOIN clients c ON c.id = a.client_id
                            JOIN nft_trophy_definitions d ON d.id = a.trophy_def_id
                            GROUP BY a.client_id ORDER BY trophies DESC LIMIT 10");
        $stats['top_collectors'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'stats' => $stats]);
        break;

    // ── Admin: Create new trophy definition ──
    case 'admin-create-trophy':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        $user = trophyRequireAuth();
        if (!trophyIsCommander()) { echo json_encode(['error' => 'Commander access required']); break; }

        $data = json_decode(file_get_contents('php://input'), true) ?: [];

        $slug     = preg_replace('/[^a-z0-9_]/', '', strtolower($data['slug'] ?? ''));
        $name     = mb_substr(trim($data['name'] ?? ''), 0, 200);
        $desc     = mb_substr(trim($data['description'] ?? ''), 0, 2000);
        $category = $data['category'] ?? 'achievement';
        $rarity   = $data['rarity'] ?? 'common';
        $gameType = $data['game_type'] ?? null;
        $imageUrl = $data['image_url'] ?? null;
        $maxSup   = isset($data['max_supply']) ? (int)$data['max_supply'] : null;
        $autoAwd  = (int)($data['auto_award'] ?? 0);
        $autoCond = $data['auto_condition'] ?? null;

        if (!$slug || !$name || !$desc) { echo json_encode(['error' => 'slug, name, and description required']); break; }
        if (!in_array($category, TROPHY_CATEGORIES, true)) $category = 'other';
        if (!in_array($rarity, TROPHY_RARITY, true)) $rarity = 'common';

        $cost = RARITY_GSM_COST[$rarity] ?? 5;

        $db->prepare("INSERT INTO nft_trophy_definitions 
                      (slug, name, description, category, rarity, game_type, image_url, max_supply, 
                       mint_cost_gsm, auto_award, auto_condition, created_at)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())")
           ->execute([$slug, $name, $desc, $category, $rarity, $gameType, $imageUrl, $maxSup,
                      $cost, $autoAwd, $autoCond ? json_encode($autoCond) : null]);

        echo json_encode(['success' => true, 'trophy_id' => (int)$db->lastInsertId(), 'message' => 'Trophy created: ' . $name]);
        break;

    // ── Admin: Manually award trophy ──
    case 'admin-award':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        $user = trophyRequireAuth();
        if (!trophyIsCommander()) { echo json_encode(['error' => 'Commander access required']); break; }

        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $slug = $data['slug'] ?? '';
        $targetClientId = (int)($data['client_id'] ?? 0);
        $earnedVia = $data['earned_via'] ?? 'commander_award';

        if (!$slug || $targetClientId <= 0) { echo json_encode(['error' => 'slug and client_id required']); break; }

        $result = awardTrophy($db, $targetClientId, $slug, $earnedVia, ['awarded_by' => $user['client_id']]);
        echo json_encode($result);
        break;

    // ── Toggle trophy display ──
    case 'toggle-display':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        requireCSRF();
        $user = trophyRequireAuth();
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $awardId = (int)($data['award_id'] ?? 0);
        $display = (int)($data['display'] ?? 1);

        $db->prepare("UPDATE nft_trophy_awards SET is_displayed = ? WHERE id = ? AND client_id = ?")
           ->execute([$display ? 1 : 0, $awardId, $user['client_id']]);

        echo json_encode(['success' => true, 'displayed' => (bool)$display]);
        break;

    // ── SOL purchase stub ──
    case 'purchase-with-sol':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        requireCSRF();
        $user = trophyRequireAuth();
        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $awardId = (int)($data['award_id'] ?? 0);
        $solTx = $data['sol_tx_signature'] ?? '';
        if (!$awardId || !$solTx) { echo json_encode(['error' => 'award_id and sol_tx_signature required']); break; }
        // Stub: In production, verify the SOL transaction on-chain via Solana RPC
        // For now, record the claimed tx and flag for manual verification
        $db->prepare("INSERT INTO gsm_transactions (user_id, type, amount, description, reference_type, reference_id)
            VALUES (?, 'sol_purchase_pending', 0, ?, 'nft_sol_purchase', ?)")
           ->execute([$user['client_id'], 'SOL TX: ' . substr($solTx, 0, 88), $awardId]);
        echo json_encode([
            'success' => true,
            'status' => 'pending_verification',
            'message' => 'SOL payment recorded. Awaiting on-chain verification.',
            'note' => 'Solana RPC verification will be activated when the Helius/QuickNode integration is live.',
        ]);
        break;

    // ── GSM-to-SOL swap rate stub ──
    case 'sol-exchange-rate':
        // Stub: returns a fixed rate until a real oracle/DEX integration is added
        echo json_encode([
            'success' => true,
            'gsm_per_sol' => 10000,
            'sol_per_gsm' => 0.0001,
            'last_updated' => date('c'),
            'source' => 'fixed_stub',
            'note' => 'Live rates will come from Jupiter/Raydium DEX once the GSM SPL token is deployed.',
        ]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action', 'valid_actions' => [
            'my-trophies', 'trophy-detail', 'available-trophies', 'mint-trophy',
            'trophy-gallery', 'trophy-stats', 'admin-create-trophy', 'admin-award',
            'toggle-display', 'purchase-with-sol', 'sol-exchange-rate'
        ]]);
}
