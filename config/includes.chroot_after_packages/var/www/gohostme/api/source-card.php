<?php
/**
 * Source Card API — The Identity of the New World
 * ────────────────────────────────────────────────
 * 
 * Not a social insurance number. Not a tax ID. Not a credit score.
 * 
 * A Source Card is a living reflection of what you CREATE and CONTRIBUTE.
 * It's your egg basket — it holds your potential, your hatched skills,
 * your reputation, your energy flow, and your lineage.
 * 
 * Every entity in the ecosystem gets one:
 *   - AI Agents (alfred, nova, cipher...)
 *   - Human participants (clients, creators, builders)
 *   - Metaverse citizens (kingdom players)
 * 
 * The Source Card bridges all existing systems:
 *   - Treasury (real money)
 *   - Kingdom Economy (KGD virtual currency)
 *   - DeFi (multi-chain wallets)
 *   - Agent Registry (AI workforce)
 *   - Learning Engine (wisdom)
 *   - Reputation (trust)
 * 
 * Philosophy: Energy in → Energy recognized → Community grows
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

// ─── SCHEMA ───────────────────────────────────────────────────────

function ensureSourceCardSchema() {
    $db = getDB();
    if (!$db) return false;

    // The Source Card itself — universal identity
    $db->exec("CREATE TABLE IF NOT EXISTS source_cards (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        source_id       VARCHAR(50) NOT NULL UNIQUE COMMENT 'SRC-XXXX format — the universal ID',
        entity_type     ENUM('agent','human','citizen') NOT NULL COMMENT 'What kind of being',
        entity_ref      VARCHAR(100) NOT NULL COMMENT 'Reference: agent_id, client_id, or player_id',
        display_name    VARCHAR(100) NOT NULL,
        avatar_url      VARCHAR(500) DEFAULT NULL,
        
        -- The Egg Basket: What you carry
        energy_created  BIGINT DEFAULT 0 COMMENT 'Total energy units generated (contributions)',
        energy_received BIGINT DEFAULT 0 COMMENT 'Total energy received from others',
        energy_given    BIGINT DEFAULT 0 COMMENT 'Total energy given to others',
        
        -- Living Score: Your reputation pulse
        trust_score     DECIMAL(5,2) DEFAULT 50.00 COMMENT '0-100 living trust score',
        contribution_streak INT DEFAULT 0 COMMENT 'Consecutive days of contribution',
        longest_streak  INT DEFAULT 0 COMMENT 'Best streak ever',
        
        -- Skills Hatched: What your eggs became
        skills          JSON DEFAULT NULL COMMENT 'Array of {skill, level, verified}',
        domains         JSON DEFAULT NULL COMMENT 'Domains of expertise',
        
        -- Lineage: The flock — who mentored you, who you mentored
        mentor_source_id VARCHAR(50) DEFAULT NULL COMMENT 'Who brought you in / initialized you',
        mentees_count   INT DEFAULT 0 COMMENT 'How many you have mentored',
        
        -- Cross-system links
        client_id       INT DEFAULT NULL COMMENT 'Links to whmcs/auth client_id',
        agent_id        VARCHAR(50) DEFAULT NULL COMMENT 'Links to alfred_agent_registry',
        player_id       INT DEFAULT NULL COMMENT 'Links to kingdom_players',
        
        -- Status
        status          ENUM('active','dormant','suspended','ascended') DEFAULT 'active',
        tier            ENUM('seed','sprout','sapling','tree','grove','forest') DEFAULT 'seed' COMMENT 'Growth tier based on energy',
        
        -- Metadata
        metadata        JSON DEFAULT NULL,
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_active     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        
        INDEX idx_entity (entity_type, entity_ref),
        INDEX idx_client (client_id),
        INDEX idx_agent (agent_id),
        INDEX idx_tier (tier),
        INDEX idx_trust (trust_score)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Contribution Ledger — every egg laid
    $db->exec("CREATE TABLE IF NOT EXISTS source_contributions (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        source_id       VARCHAR(50) NOT NULL COMMENT 'Who contributed',
        contribution_type VARCHAR(50) NOT NULL COMMENT 'code, design, support, community, learning, mentoring, building, playing',
        description     VARCHAR(500) NOT NULL,
        energy_value    INT NOT NULL DEFAULT 1 COMMENT 'Energy units earned',
        context         VARCHAR(100) DEFAULT NULL COMMENT 'Where: api, metaverse, chat, defi, etc.',
        reference_id    VARCHAR(100) DEFAULT NULL COMMENT 'Task ID, session ID, etc.',
        verified_by     VARCHAR(50) DEFAULT NULL COMMENT 'Source ID of verifier (or SYSTEM)',
        metadata        JSON DEFAULT NULL,
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_source (source_id),
        INDEX idx_type (contribution_type),
        INDEX idx_date (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Energy Transfers — eggs shared between baskets
    $db->exec("CREATE TABLE IF NOT EXISTS source_energy_transfers (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        from_source_id  VARCHAR(50) NOT NULL,
        to_source_id    VARCHAR(50) NOT NULL,
        amount          INT NOT NULL,
        reason          VARCHAR(200) NOT NULL,
        transfer_type   ENUM('gift','reward','trade','mentorship','recognition') NOT NULL,
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_from (from_source_id),
        INDEX idx_to (to_source_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Reputation Events — trust score changes with audit trail
    $db->exec("CREATE TABLE IF NOT EXISTS source_reputation (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        source_id       VARCHAR(50) NOT NULL,
        event_type      ENUM('upvote','downvote','achievement','violation','verification','decay','boost') NOT NULL,
        delta           DECIMAL(5,2) NOT NULL COMMENT 'Trust score change (+/-)',
        reason          VARCHAR(200) NOT NULL,
        given_by        VARCHAR(50) DEFAULT NULL COMMENT 'Source ID of who gave this',
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_source (source_id),
        INDEX idx_date (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    return true;
}

// ─── HELPERS ──────────────────────────────────────────────────────

function generateSourceId($entityType) {
    $prefix = match($entityType) {
        'agent' => 'SRC-A',
        'human' => 'SRC-H',
        'citizen' => 'SRC-C',
        default => 'SRC-X'
    };
    return $prefix . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
}

function getSourceCard($db, $sourceId) {
    $stmt = $db->prepare("SELECT * FROM source_cards WHERE source_id = ?");
    $stmt->execute([$sourceId]);
    $card = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($card) {
        $card['skills'] = json_decode($card['skills'] ?: '[]', true);
        $card['domains'] = json_decode($card['domains'] ?: '[]', true);
        $card['metadata'] = json_decode($card['metadata'] ?: '{}', true);
    }
    return $card;
}

function getSourceCardByRef($db, $entityType, $entityRef) {
    $stmt = $db->prepare("SELECT * FROM source_cards WHERE entity_type = ? AND entity_ref = ?");
    $stmt->execute([$entityType, $entityRef]);
    $card = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($card) {
        $card['skills'] = json_decode($card['skills'] ?: '[]', true);
        $card['domains'] = json_decode($card['domains'] ?: '[]', true);
        $card['metadata'] = json_decode($card['metadata'] ?: '{}', true);
    }
    return $card;
}

function calculateTier($energyCreated) {
    if ($energyCreated >= 100000) return 'forest';
    if ($energyCreated >= 10000)  return 'grove';
    if ($energyCreated >= 1000)   return 'tree';
    if ($energyCreated >= 100)    return 'sapling';
    if ($energyCreated >= 10)     return 'sprout';
    return 'seed';
}

// ─── MAIN ─────────────────────────────────────────────────────────

$db = getDB();
if (!$db) jsonResponse(['error' => 'Database unavailable'], 503);
ensureSourceCardSchema();

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$input = json_decode(file_get_contents('php://input'), true) ?: [];

switch ($action) {

    // ─── View a Source Card ───────────────────────────────────────
    case 'card':
        $sourceId = $_GET['source_id'] ?? ($input['source_id'] ?? '');
        if (!$sourceId) jsonResponse(['error' => 'source_id required'], 400);
        
        $card = getSourceCard($db, $sourceId);
        if (!$card) jsonResponse(['error' => 'Source Card not found'], 404);
        
        // Enrich with recent contributions
        $recent = $db->prepare("SELECT contribution_type, description, energy_value, created_at 
            FROM source_contributions WHERE source_id = ? ORDER BY created_at DESC LIMIT 10");
        $recent->execute([$sourceId]);
        
        // Contribution breakdown
        $breakdown = $db->prepare("SELECT contribution_type, COUNT(*) as count, SUM(energy_value) as total_energy 
            FROM source_contributions WHERE source_id = ? GROUP BY contribution_type ORDER BY total_energy DESC");
        $breakdown->execute([$sourceId]);
        
        jsonResponse([
            'success' => true,
            'card' => $card,
            'recent_contributions' => $recent->fetchAll(PDO::FETCH_ASSOC),
            'contribution_breakdown' => $breakdown->fetchAll(PDO::FETCH_ASSOC)
        ]);

    // ─── View my own Source Card (session-based) ──────────────────
    case 'my-card':
        requireAuth();
        $clientId = $_SESSION['client_id'];
        
        $card = getSourceCardByRef($db, 'human', (string)$clientId);
        if (!$card) {
            // Auto-create card for authenticated humans
            $sourceId = generateSourceId('human');
            $name = $_SESSION['name'] ?? $_SESSION['email'] ?? 'Citizen #' . $clientId;
            $db->prepare("INSERT INTO source_cards (source_id, entity_type, entity_ref, display_name, client_id) VALUES (?, 'human', ?, ?, ?)")
                ->execute([$sourceId, (string)$clientId, $name, $clientId]);
            $card = getSourceCard($db, $sourceId);
        }
        
        $recent = $db->prepare("SELECT contribution_type, description, energy_value, created_at 
            FROM source_contributions WHERE source_id = ? ORDER BY created_at DESC LIMIT 10");
        $recent->execute([$card['source_id']]);
        
        $breakdown = $db->prepare("SELECT contribution_type, COUNT(*) as count, SUM(energy_value) as total_energy 
            FROM source_contributions WHERE source_id = ? GROUP BY contribution_type ORDER BY total_energy DESC");
        $breakdown->execute([$card['source_id']]);
        
        jsonResponse([
            'success' => true,
            'card' => $card,
            'recent_contributions' => $recent->fetchAll(PDO::FETCH_ASSOC),
            'contribution_breakdown' => $breakdown->fetchAll(PDO::FETCH_ASSOC)
        ]);

    // ─── Create a Source Card ─────────────────────────────────────
    case 'create':
        if (!isInternalCall() && !isAdmin()) jsonResponse(['error' => 'Admin or internal required'], 403);
        
        $entityType = $input['entity_type'] ?? '';
        $entityRef = $input['entity_ref'] ?? '';
        $displayName = $input['display_name'] ?? '';
        
        if (!in_array($entityType, ['agent', 'human', 'citizen'])) jsonResponse(['error' => 'Invalid entity_type (agent/human/citizen)'], 400);
        if (!$entityRef || !$displayName) jsonResponse(['error' => 'entity_ref and display_name required'], 400);
        
        // Check duplicate
        $existing = getSourceCardByRef($db, $entityType, $entityRef);
        if ($existing) jsonResponse(['success' => true, 'card' => $existing, 'note' => 'Already exists']);
        
        $sourceId = generateSourceId($entityType);
        $skills = !empty($input['skills']) ? json_encode($input['skills']) : null;
        $domains = !empty($input['domains']) ? json_encode($input['domains']) : null;
        $mentor = $input['mentor_source_id'] ?? null;
        $clientId = $input['client_id'] ?? null;
        $agentId = $input['agent_id'] ?? null;
        
        $stmt = $db->prepare("INSERT INTO source_cards 
            (source_id, entity_type, entity_ref, display_name, skills, domains, mentor_source_id, client_id, agent_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$sourceId, $entityType, $entityRef, $displayName, $skills, $domains, $mentor, $clientId, $agentId]);
        
        // If mentored, increment mentor's mentee count
        if ($mentor) {
            $db->prepare("UPDATE source_cards SET mentees_count = mentees_count + 1 WHERE source_id = ?")->execute([$mentor]);
        }
        
        jsonResponse(['success' => true, 'source_id' => $sourceId, 'card' => getSourceCard($db, $sourceId)], 201);

    // ─── Record a Contribution (lay an egg) ───────────────────────
    case 'contribute':
        if (!isInternalCall()) { requireAuth(); }
        
        $sourceId = $input['source_id'] ?? '';
        $type = $input['contribution_type'] ?? '';
        $description = $input['description'] ?? '';
        $energyValue = (int)($input['energy_value'] ?? 1);
        $context = $input['context'] ?? null;
        $referenceId = $input['reference_id'] ?? null;
        
        $validTypes = ['code', 'design', 'support', 'community', 'learning', 'mentoring', 'building', 'playing', 'creating', 'healing', 'securing', 'researching'];
        if (!$sourceId || !$type || !$description) jsonResponse(['error' => 'source_id, contribution_type, and description required'], 400);
        if (!in_array($type, $validTypes)) jsonResponse(['error' => 'Invalid contribution_type', 'valid' => $validTypes], 400);
        if ($energyValue < 1 || $energyValue > 1000) jsonResponse(['error' => 'energy_value must be 1-1000'], 400);
        
        $card = getSourceCard($db, $sourceId);
        if (!$card) jsonResponse(['error' => 'Source Card not found'], 404);
        
        // If not internal, verify the caller owns this card
        if (!isInternalCall() && !isAdmin()) {
            if ($card['client_id'] != ($_SESSION['client_id'] ?? 0)) {
                jsonResponse(['error' => 'Not your Source Card'], 403);
            }
        }
        
        $db->prepare("INSERT INTO source_contributions (source_id, contribution_type, description, energy_value, context, reference_id, verified_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)")
            ->execute([$sourceId, $type, $description, $energyValue, $context, $referenceId, isInternalCall() ? 'SYSTEM' : null]);
        
        // Update card totals
        $newEnergy = $card['energy_created'] + $energyValue;
        $newTier = calculateTier($newEnergy);
        
        // Update streak
        $lastContrib = $db->prepare("SELECT DATE(created_at) FROM source_contributions WHERE source_id = ? ORDER BY created_at DESC LIMIT 1 OFFSET 1");
        $lastContrib->execute([$sourceId]);
        $lastDate = $lastContrib->fetchColumn();
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        $streak = $card['contribution_streak'];
        if ($lastDate === $yesterday || $lastDate === $today) {
            if ($lastDate === $yesterday) $streak++;
        } else {
            $streak = 1; // Reset streak
        }
        $longestStreak = max($card['longest_streak'], $streak);
        
        $db->prepare("UPDATE source_cards SET energy_created = ?, tier = ?, contribution_streak = ?, longest_streak = ? WHERE source_id = ?")
            ->execute([$newEnergy, $newTier, $streak, $longestStreak, $sourceId]);
        
        jsonResponse([
            'success' => true,
            'contribution' => ['type' => $type, 'energy' => $energyValue, 'context' => $context],
            'card_update' => ['energy_created' => $newEnergy, 'tier' => $newTier, 'streak' => $streak]
        ]);

    // ─── Transfer Energy Between Cards ────────────────────────────
    case 'transfer':
        if (!isInternalCall()) { requireAuth(); }
        
        $fromId = $input['from_source_id'] ?? '';
        $toId = $input['to_source_id'] ?? '';
        $amount = (int)($input['amount'] ?? 0);
        $reason = $input['reason'] ?? '';
        $transferType = $input['transfer_type'] ?? 'gift';
        
        if (!$fromId || !$toId || !$amount || !$reason) jsonResponse(['error' => 'from_source_id, to_source_id, amount, and reason required'], 400);
        if ($amount < 1 || $amount > 10000) jsonResponse(['error' => 'Amount must be 1-10000'], 400);
        if (!in_array($transferType, ['gift', 'reward', 'trade', 'mentorship', 'recognition'])) jsonResponse(['error' => 'Invalid transfer_type'], 400);
        
        $fromCard = getSourceCard($db, $fromId);
        $toCard = getSourceCard($db, $toId);
        if (!$fromCard || !$toCard) jsonResponse(['error' => 'Source Card(s) not found'], 404);
        
        // Verify ownership if not internal
        if (!isInternalCall() && !isAdmin()) {
            if ($fromCard['client_id'] != ($_SESSION['client_id'] ?? 0)) {
                jsonResponse(['error' => 'Not your Source Card'], 403);
            }
        }
        
        $db->beginTransaction();
        try {
            $db->prepare("INSERT INTO source_energy_transfers (from_source_id, to_source_id, amount, reason, transfer_type) VALUES (?, ?, ?, ?, ?)")
                ->execute([$fromId, $toId, $amount, $reason, $transferType]);
            
            $db->prepare("UPDATE source_cards SET energy_given = energy_given + ? WHERE source_id = ?")
                ->execute([$amount, $fromId]);
            $db->prepare("UPDATE source_cards SET energy_received = energy_received + ? WHERE source_id = ?")
                ->execute([$amount, $toId]);
            
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            jsonResponse(['error' => 'Transfer failed'], 500);
        }
        
        jsonResponse([
            'success' => true,
            'transfer' => ['from' => $fromId, 'to' => $toId, 'amount' => $amount, 'type' => $transferType]
        ]);

    // ─── Reputation Event ─────────────────────────────────────────
    case 'reputation':
        if (!isInternalCall() && !isAdmin()) jsonResponse(['error' => 'Admin or internal required'], 403);
        
        $sourceId = $input['source_id'] ?? '';
        $eventType = $input['event_type'] ?? '';
        $delta = (float)($input['delta'] ?? 0);
        $reason = $input['reason'] ?? '';
        $givenBy = $input['given_by'] ?? null;
        
        if (!$sourceId || !$eventType || !$reason) jsonResponse(['error' => 'source_id, event_type, and reason required'], 400);
        if (!in_array($eventType, ['upvote', 'downvote', 'achievement', 'violation', 'verification', 'decay', 'boost'])) {
            jsonResponse(['error' => 'Invalid event_type'], 400);
        }
        
        $card = getSourceCard($db, $sourceId);
        if (!$card) jsonResponse(['error' => 'Source Card not found'], 404);
        
        // Clamp trust_score between 0 and 100
        $newTrust = max(0, min(100, $card['trust_score'] + $delta));
        
        $db->prepare("INSERT INTO source_reputation (source_id, event_type, delta, reason, given_by) VALUES (?, ?, ?, ?, ?)")
            ->execute([$sourceId, $eventType, $delta, $reason, $givenBy]);
        $db->prepare("UPDATE source_cards SET trust_score = ? WHERE source_id = ?")
            ->execute([$newTrust, $sourceId]);
        
        jsonResponse([
            'success' => true,
            'trust_score' => ['was' => (float)$card['trust_score'], 'now' => $newTrust, 'delta' => $delta],
            'event' => $eventType
        ]);

    // ─── Update Skills ────────────────────────────────────────────
    case 'update-skills':
        if (!isInternalCall()) { requireAuth(); }
        
        $sourceId = $input['source_id'] ?? '';
        $skills = $input['skills'] ?? null; // Array of {skill, level, verified}
        
        if (!$sourceId || !is_array($skills)) jsonResponse(['error' => 'source_id and skills array required'], 400);
        
        $card = getSourceCard($db, $sourceId);
        if (!$card) jsonResponse(['error' => 'Source Card not found'], 404);
        
        if (!isInternalCall() && !isAdmin() && $card['client_id'] != ($_SESSION['client_id'] ?? 0)) {
            jsonResponse(['error' => 'Not your Source Card'], 403);
        }
        
        $db->prepare("UPDATE source_cards SET skills = ? WHERE source_id = ?")
            ->execute([json_encode($skills), $sourceId]);
        
        jsonResponse(['success' => true, 'skills' => $skills]);

    // ─── Leaderboard — The Grove ──────────────────────────────────
    case 'leaderboard':
        $sortBy = $_GET['sort'] ?? 'energy_created';
        $allowedSorts = ['energy_created', 'trust_score', 'contribution_streak', 'energy_given', 'mentees_count'];
        if (!in_array($sortBy, $allowedSorts)) $sortBy = 'energy_created';
        
        $entityType = $_GET['type'] ?? null;
        $tier = $_GET['tier'] ?? null;
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
        
        $where = "WHERE status = 'active'";
        $params = [];
        if ($entityType && in_array($entityType, ['agent', 'human', 'citizen'])) {
            $where .= " AND entity_type = ?";
            $params[] = $entityType;
        }
        if ($tier && in_array($tier, ['seed', 'sprout', 'sapling', 'tree', 'grove', 'forest'])) {
            $where .= " AND tier = ?";
            $params[] = $tier;
        }
        
        $stmt = $db->prepare("SELECT source_id, entity_type, display_name, avatar_url, energy_created, 
            energy_given, trust_score, contribution_streak, tier, mentees_count, last_active
            FROM source_cards $where ORDER BY $sortBy DESC LIMIT $limit");
        $stmt->execute($params);
        
        // Ecosystem stats
        $stats = $db->query("SELECT 
            COUNT(*) as total_cards,
            SUM(energy_created) as total_energy,
            SUM(CASE WHEN entity_type='agent' THEN 1 ELSE 0 END) as agents,
            SUM(CASE WHEN entity_type='human' THEN 1 ELSE 0 END) as humans,
            SUM(CASE WHEN entity_type='citizen' THEN 1 ELSE 0 END) as citizens,
            AVG(trust_score) as avg_trust
            FROM source_cards WHERE status = 'active'")->fetch(PDO::FETCH_ASSOC);
        
        jsonResponse([
            'success' => true,
            'leaderboard' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'ecosystem' => $stats,
            'sort' => $sortBy,
            'filters' => ['type' => $entityType, 'tier' => $tier]
        ]);

    // ─── Lookup by existing system ID ─────────────────────────────
    case 'lookup':
        $clientId = $_GET['client_id'] ?? null;
        $agentId = $_GET['agent_id'] ?? null;
        
        if ($clientId) {
            $stmt = $db->prepare("SELECT * FROM source_cards WHERE client_id = ?");
            $stmt->execute([(int)$clientId]);
        } elseif ($agentId) {
            $stmt = $db->prepare("SELECT * FROM source_cards WHERE agent_id = ?");
            $stmt->execute([$agentId]);
        } else {
            jsonResponse(['error' => 'Provide client_id or agent_id'], 400);
        }
        
        $card = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$card) jsonResponse(['error' => 'No Source Card found for that reference'], 404);
        
        $card['skills'] = json_decode($card['skills'] ?: '[]', true);
        $card['domains'] = json_decode($card['domains'] ?: '[]', true);
        $card['metadata'] = json_decode($card['metadata'] ?: '{}', true);
        
        jsonResponse(['success' => true, 'card' => $card]);

    // ─── Seed all agents with Source Cards ────────────────────────
    case 'seed-agents':
        if (!isInternalCall() && !isAdmin()) jsonResponse(['error' => 'Admin or internal required'], 403);
        
        $agents = $db->query("SELECT agent_id, agent_name, agent_role, domain FROM alfred_agent_registry")->fetchAll(PDO::FETCH_ASSOC);
        $created = 0;
        $skipped = 0;
        $alfredSourceId = null;
        
        foreach ($agents as $agent) {
            $existing = getSourceCardByRef($db, 'agent', $agent['agent_id']);
            if ($existing) {
                if ($agent['agent_id'] === 'alfred') $alfredSourceId = $existing['source_id'];
                $skipped++;
                continue;
            }
            
            $sourceId = generateSourceId('agent');
            if ($agent['agent_id'] === 'alfred') $alfredSourceId = $sourceId;
            
            // Director-level agents mentored by ALFRED
            $mentor = ($agent['agent_role'] !== 'commander' && $alfredSourceId) ? $alfredSourceId : null;
            
            $skills = [];
            $domains = [$agent['domain']];
            
            // Assign starting skills based on role
            switch ($agent['domain']) {
                case 'engineering': $skills = [['skill' => 'code', 'level' => 3, 'verified' => true], ['skill' => 'architecture', 'level' => 2, 'verified' => true]]; break;
                case 'security': $skills = [['skill' => 'threat_analysis', 'level' => 3, 'verified' => true], ['skill' => 'encryption', 'level' => 3, 'verified' => true]]; break;
                case 'research': $skills = [['skill' => 'analysis', 'level' => 3, 'verified' => true], ['skill' => 'synthesis', 'level' => 2, 'verified' => true]]; break;
                case 'finance': $skills = [['skill' => 'accounting', 'level' => 3, 'verified' => true], ['skill' => 'forecasting', 'level' => 2, 'verified' => true]]; break;
                case 'communications': $skills = [['skill' => 'messaging', 'level' => 3, 'verified' => true], ['skill' => 'routing', 'level' => 2, 'verified' => true]]; break;
                case 'infrastructure': $skills = [['skill' => 'deployment', 'level' => 3, 'verified' => true], ['skill' => 'monitoring', 'level' => 3, 'verified' => true]]; break;
                case 'marketing': $skills = [['skill' => 'content', 'level' => 3, 'verified' => true], ['skill' => 'analytics', 'level' => 2, 'verified' => true]]; break;
                default: $skills = [['skill' => $agent['domain'], 'level' => 2, 'verified' => true]]; break;
            }
            
            // Starting trust based on role
            $trust = match($agent['agent_role']) {
                'commander' => 95.00,
                'director' => 80.00,
                'specialist' => 65.00,
                default => 50.00
            };
            
            $stmt = $db->prepare("INSERT INTO source_cards 
                (source_id, entity_type, entity_ref, display_name, agent_id, skills, domains, mentor_source_id, trust_score)
                VALUES (?, 'agent', ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $sourceId, $agent['agent_id'], $agent['agent_name'], $agent['agent_id'],
                json_encode($skills), json_encode($domains), $mentor, $trust
            ]);
            
            if ($mentor) {
                $db->prepare("UPDATE source_cards SET mentees_count = mentees_count + 1 WHERE source_id = ?")->execute([$mentor]);
            }
            
            $created++;
        }
        
        jsonResponse([
            'success' => true,
            'seeded' => $created,
            'skipped' => $skipped,
            'total_agents' => count($agents),
            'alfred_source_id' => $alfredSourceId
        ]);

    // ─── Ecosystem Overview ───────────────────────────────────────
    case 'ecosystem':
        $stats = $db->query("SELECT 
            COUNT(*) as total_cards,
            SUM(energy_created) as total_energy_created,
            SUM(energy_given) as total_energy_shared,
            SUM(CASE WHEN entity_type='agent' THEN 1 ELSE 0 END) as agents,
            SUM(CASE WHEN entity_type='human' THEN 1 ELSE 0 END) as humans,
            SUM(CASE WHEN entity_type='citizen' THEN 1 ELSE 0 END) as citizens,
            AVG(trust_score) as avg_trust,
            SUM(mentees_count) as total_mentorships
        FROM source_cards WHERE status = 'active'")->fetch(PDO::FETCH_ASSOC);
        
        $tierBreakdown = $db->query("SELECT tier, COUNT(*) as count FROM source_cards WHERE status = 'active' GROUP BY tier ORDER BY FIELD(tier, 'seed','sprout','sapling','tree','grove','forest')")->fetchAll(PDO::FETCH_ASSOC);
        
        $topContributors = $db->query("SELECT source_id, display_name, entity_type, energy_created, tier, trust_score 
            FROM source_cards WHERE status = 'active' ORDER BY energy_created DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        
        $recentContributions = $db->query("SELECT c.source_id, s.display_name, c.contribution_type, c.description, c.energy_value, c.created_at
            FROM source_contributions c JOIN source_cards s ON c.source_id = s.source_id 
            ORDER BY c.created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
        
        $energyFlow = $db->query("SELECT DATE(created_at) as day, SUM(energy_value) as energy 
            FROM source_contributions WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at) ORDER BY day")->fetchAll(PDO::FETCH_ASSOC);
        
        jsonResponse([
            'success' => true,
            'ecosystem' => $stats,
            'tiers' => $tierBreakdown,
            'top_contributors' => $topContributors,
            'recent_contributions' => $recentContributions,
            'energy_flow_30d' => $energyFlow,
            'philosophy' => 'Energy in → Energy recognized → Community grows'
        ]);

    default:
        jsonResponse([
            'error' => 'Unknown action',
            'available_actions' => [
                'card', 'my-card', 'create', 'contribute', 'transfer',
                'reputation', 'update-skills', 'leaderboard', 'lookup',
                'seed-agents', 'ecosystem'
            ]
        ], 400);
}
