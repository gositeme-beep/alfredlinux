<?php
/**
 * Governance & Voting API — Phase 4b
 * Stake-weighted governance: create proposals, vote with GSM weight, delegate votes
 * 
 * Actions: proposals, proposal-detail, create-proposal, vote, my-votes,
 *          delegate, undelegate, governance-stats, admin-resolve
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

apiRateLimit(30, 60, 'governance');
session_start();

// ── Constants ──
const MIN_GSM_TO_PROPOSE = 500;     // Minimum GSM balance to create a proposal
const MIN_GSM_TO_VOTE    = 1;       // Minimum GSM to cast a vote
const PROPOSAL_DURATION_DAYS = 7;   // Default voting period
const MAX_ACTIVE_PROPOSALS   = 20;  // Limit concurrent proposals
const CATEGORIES = ['feature', 'economy', 'game', 'governance', 'community', 'technical', 'other'];
const QUORUM_PERCENT = 10;          // % of total circulating GSM needed for quorum

// ── Auth ──
function govRequireAuth(): array {
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

function govIsCommander(): bool {
    return ($_SESSION['client_id'] ?? 0) == 33;
}

// ── Tables ──
function ensureGovernanceTables(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS gsm_governance_proposals (
        id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        proposer_id     INT UNSIGNED NOT NULL,
        title           VARCHAR(200) NOT NULL,
        description     TEXT NOT NULL,
        category        ENUM('feature','economy','game','governance','community','technical','other') DEFAULT 'feature',
        status          ENUM('active','passed','rejected','cancelled','executed') DEFAULT 'active',
        votes_for       DECIMAL(20,9) NOT NULL DEFAULT 0,
        votes_against   DECIMAL(20,9) NOT NULL DEFAULT 0,
        votes_abstain   DECIMAL(20,9) NOT NULL DEFAULT 0,
        voter_count     INT UNSIGNED NOT NULL DEFAULT 0,
        quorum_gsm      DECIMAL(20,9) NOT NULL DEFAULT 0,
        snapshot_supply DECIMAL(20,9) NOT NULL DEFAULT 0,
        starts_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        ends_at         DATETIME NOT NULL,
        resolved_at     DATETIME DEFAULT NULL,
        execution_notes TEXT DEFAULT NULL,
        created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY idx_status (status, ends_at),
        KEY idx_proposer (proposer_id),
        KEY idx_category (category, status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS gsm_governance_votes (
        id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        proposal_id     INT UNSIGNED NOT NULL,
        voter_id        INT UNSIGNED NOT NULL,
        vote            ENUM('for','against','abstain') NOT NULL,
        weight_gsm      DECIMAL(20,9) NOT NULL,
        delegated_from  INT UNSIGNED DEFAULT NULL,
        comment         VARCHAR(500) DEFAULT NULL,
        created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_voter_proposal (proposal_id, voter_id),
        KEY idx_voter (voter_id),
        KEY idx_proposal_vote (proposal_id, vote)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS gsm_governance_delegates (
        id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        delegator_id    INT UNSIGNED NOT NULL,
        delegate_id     INT UNSIGNED NOT NULL,
        created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_delegator (delegator_id),
        KEY idx_delegate (delegate_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

// ── Helpers ──
function getVotingPower(PDO $db, int $clientId): float {
    // Voting power = available_balance + staked_amount (staked GSM still counts for voting)
    $stmt = $db->prepare("SELECT COALESCE(available_balance, 0) + COALESCE(staked_amount, 0) as power
                          FROM crypto_gsm_balances WHERE client_id = ?");
    $stmt->execute([$clientId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $ownPower = $row ? (float)$row['power'] : 0.0;

    // Add delegated power
    $delStmt = $db->prepare("SELECT SUM(COALESCE(b.available_balance, 0) + COALESCE(b.staked_amount, 0)) as delegated
                             FROM gsm_governance_delegates d
                             JOIN crypto_gsm_balances b ON b.client_id = d.delegator_id
                             WHERE d.delegate_id = ?");
    $delStmt->execute([$clientId]);
    $delRow = $delStmt->fetch(PDO::FETCH_ASSOC);
    $delegated = $delRow ? (float)$delRow['delegated'] : 0.0;

    return $ownPower + $delegated;
}

function getCirculatingSupply(PDO $db): float {
    $stmt = $db->query("SELECT COALESCE(SUM(available_balance + staked_amount), 0) FROM crypto_gsm_balances");
    return (float)$stmt->fetchColumn();
}

function autoResolveExpired(PDO $db): void {
    $expired = $db->query("SELECT id, votes_for, votes_against, quorum_gsm, voter_count
                           FROM gsm_governance_proposals 
                           WHERE status = 'active' AND ends_at <= NOW()")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($expired as $p) {
        $totalVotes = (float)$p['votes_for'] + (float)$p['votes_against'] + 0; // abstain doesn't count toward decision
        $quorumMet = $totalVotes >= (float)$p['quorum_gsm'];
        $passed = $quorumMet && (float)$p['votes_for'] > (float)$p['votes_against'];
        $status = $passed ? 'passed' : 'rejected';
        $db->prepare("UPDATE gsm_governance_proposals SET status = ?, resolved_at = NOW() WHERE id = ? AND status = 'active'")
           ->execute([$status, $p['id']]);
    }
}

// ── Main ──
$db = getDB();
if (!$db) { echo json_encode(['error' => 'Database unavailable']); exit; }
ensureGovernanceTables($db);
autoResolveExpired($db);

$action = $_GET['action'] ?? '';

switch ($action) {

    // ── List proposals (public) ──
    case 'proposals':
        $status = $_GET['status'] ?? 'active';
        $category = $_GET['category'] ?? '';
        $page   = max(1, (int)($_GET['page'] ?? 1));
        $limit  = min(50, max(10, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];
        if (in_array($status, ['active','passed','rejected','cancelled','executed','all'], true)) {
            if ($status !== 'all') { $where[] = 'p.status = ?'; $params[] = $status; }
        } else {
            $where[] = 'p.status = ?'; $params[] = 'active';
        }
        if ($category && in_array($category, CATEGORIES, true)) { $where[] = 'p.category = ?'; $params[] = $category; }

        $wSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $countStmt = $db->prepare("SELECT COUNT(*) FROM gsm_governance_proposals p $wSQL");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $db->prepare("SELECT p.*, c.firstname AS proposer_name
                              FROM gsm_governance_proposals p
                              LEFT JOIN clients c ON c.id = p.proposer_id
                              $wSQL
                              ORDER BY p.status = 'active' DESC, p.created_at DESC
                              LIMIT $limit OFFSET $offset");
        $stmt->execute($params);
        $proposals = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Add time remaining for active proposals
        foreach ($proposals as &$prop) {
            if ($prop['status'] === 'active') {
                $endsAt = strtotime($prop['ends_at']);
                $remaining = $endsAt - time();
                $prop['time_remaining'] = $remaining > 0 ? $remaining : 0;
                $prop['time_remaining_human'] = $remaining > 86400
                    ? floor($remaining / 86400) . 'd ' . floor(($remaining % 86400) / 3600) . 'h'
                    : ($remaining > 3600 ? floor($remaining / 3600) . 'h ' . floor(($remaining % 3600) / 60) . 'm' : floor($remaining / 60) . 'm');
                $totalVotes = (float)$prop['votes_for'] + (float)$prop['votes_against'] + (float)$prop['votes_abstain'];
                $prop['quorum_percent'] = $prop['quorum_gsm'] > 0 ? round(($totalVotes / (float)$prop['quorum_gsm']) * 100, 1) : 0;
            }
        }

        echo json_encode([
            'success' => true,
            'proposals' => $proposals,
            'total' => $total,
            'page' => $page,
            'pages' => ceil($total / $limit),
            'categories' => CATEGORIES,
        ]);
        break;

    // ── Proposal detail (public) ──
    case 'proposal-detail':
        $propId = (int)($_GET['proposal_id'] ?? 0);
        if ($propId <= 0) { echo json_encode(['error' => 'Invalid proposal_id']); break; }

        $stmt = $db->prepare("SELECT p.*, c.firstname AS proposer_name
                              FROM gsm_governance_proposals p
                              LEFT JOIN clients c ON c.id = p.proposer_id
                              WHERE p.id = ?");
        $stmt->execute([$propId]);
        $proposal = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$proposal) { echo json_encode(['error' => 'Proposal not found']); break; }

        // Vote breakdown
        $voteStmt = $db->prepare("SELECT v.vote, v.weight_gsm, v.comment, v.created_at, c.firstname as voter_name
                                  FROM gsm_governance_votes v
                                  LEFT JOIN clients c ON c.id = v.voter_id
                                  WHERE v.proposal_id = ? ORDER BY v.weight_gsm DESC");
        $voteStmt->execute([$propId]);
        $votes = $voteStmt->fetchAll(PDO::FETCH_ASSOC);

        $totalVotes = (float)$proposal['votes_for'] + (float)$proposal['votes_against'] + (float)$proposal['votes_abstain'];
        $proposal['for_percent']     = $totalVotes > 0 ? round(((float)$proposal['votes_for'] / $totalVotes) * 100, 1) : 0;
        $proposal['against_percent'] = $totalVotes > 0 ? round(((float)$proposal['votes_against'] / $totalVotes) * 100, 1) : 0;
        $proposal['abstain_percent'] = $totalVotes > 0 ? round(((float)$proposal['votes_abstain'] / $totalVotes) * 100, 1) : 0;
        $proposal['quorum_percent']  = $proposal['quorum_gsm'] > 0 ? round(($totalVotes / (float)$proposal['quorum_gsm']) * 100, 1) : 0;

        // Check if current user voted
        $myVote = null;
        if (!empty($_SESSION['client_id'])) {
            $myStmt = $db->prepare("SELECT vote, weight_gsm, comment FROM gsm_governance_votes WHERE proposal_id = ? AND voter_id = ?");
            $myStmt->execute([$propId, $_SESSION['client_id']]);
            $myVote = $myStmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        echo json_encode([
            'success'  => true,
            'proposal' => $proposal,
            'votes'    => $votes,
            'my_vote'  => $myVote,
        ]);
        break;

    // ── Create proposal ──
    case 'create-proposal':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        requireCSRF();
        $user = govRequireAuth();
        $data = json_decode(file_get_contents('php://input'), true) ?: [];

        $title    = mb_substr(trim($data['title'] ?? ''), 0, 200);
        $desc     = mb_substr(trim($data['description'] ?? ''), 0, 5000);
        $category = $data['category'] ?? 'feature';
        $days     = min(30, max(1, (int)($data['duration_days'] ?? PROPOSAL_DURATION_DAYS)));

        if (strlen($title) < 10) { echo json_encode(['error' => 'Title must be at least 10 characters']); break; }
        if (strlen($desc) < 30) { echo json_encode(['error' => 'Description must be at least 30 characters']); break; }
        if (!in_array($category, CATEGORIES, true)) { $category = 'other'; }

        // Check proposer has enough GSM
        $power = getVotingPower($db, $user['client_id']);
        if ($power < MIN_GSM_TO_PROPOSE && !govIsCommander()) {
            echo json_encode(['error' => 'Need at least ' . MIN_GSM_TO_PROPOSE . ' GSM to create proposals. You have ' . number_format($power, 2)]);
            break;
        }

        // Check active proposal limit
        $activeCount = $db->query("SELECT COUNT(*) FROM gsm_governance_proposals WHERE status = 'active'")->fetchColumn();
        if ($activeCount >= MAX_ACTIVE_PROPOSALS) {
            echo json_encode(['error' => 'Maximum active proposals reached (' . MAX_ACTIVE_PROPOSALS . '). Wait for some to close.']);
            break;
        }

        // Check user's active proposals (max 3 per user)
        $userActive = $db->prepare("SELECT COUNT(*) FROM gsm_governance_proposals WHERE proposer_id = ? AND status = 'active'");
        $userActive->execute([$user['client_id']]);
        if ((int)$userActive->fetchColumn() >= 3) {
            echo json_encode(['error' => 'You already have 3 active proposals']);
            break;
        }

        $supply = getCirculatingSupply($db);
        $quorum = $supply * (QUORUM_PERCENT / 100);

        $db->prepare("INSERT INTO gsm_governance_proposals 
                      (proposer_id, title, description, category, quorum_gsm, snapshot_supply, ends_at, created_at)
                      VALUES (?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? DAY), NOW())")
           ->execute([$user['client_id'], $title, $desc, $category, $quorum, $supply, $days]);

        $newId = (int)$db->lastInsertId();
        echo json_encode(['success' => true, 'proposal_id' => $newId, 'message' => 'Proposal created', 'voting_ends' => $days . ' days']);
        break;

    // ── Vote ──
    case 'vote':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        requireCSRF();
        $user = govRequireAuth();
        $data = json_decode(file_get_contents('php://input'), true) ?: [];

        $propId  = (int)($data['proposal_id'] ?? 0);
        $vote    = $data['vote'] ?? '';
        $comment = mb_substr(trim($data['comment'] ?? ''), 0, 500);

        if (!in_array($vote, ['for', 'against', 'abstain'], true)) { echo json_encode(['error' => 'Vote must be: for, against, or abstain']); break; }

        // Get proposal
        $stmt = $db->prepare("SELECT id, status, ends_at FROM gsm_governance_proposals WHERE id = ?");
        $stmt->execute([$propId]);
        $proposal = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$proposal) { echo json_encode(['error' => 'Proposal not found']); break; }
        if ($proposal['status'] !== 'active') { echo json_encode(['error' => 'Voting is closed']); break; }
        if (strtotime($proposal['ends_at']) <= time()) { echo json_encode(['error' => 'Voting period has ended']); break; }

        // Check voting power
        $power = getVotingPower($db, $user['client_id']);
        if ($power < MIN_GSM_TO_VOTE) {
            echo json_encode(['error' => 'Need at least ' . MIN_GSM_TO_VOTE . ' GSM to vote']);
            break;
        }

        // Check if already voted
        $existStmt = $db->prepare("SELECT id, vote, weight_gsm FROM gsm_governance_votes WHERE proposal_id = ? AND voter_id = ?");
        $existStmt->execute([$propId, $user['client_id']]);
        $existing = $existStmt->fetch(PDO::FETCH_ASSOC);

        $db->beginTransaction();
        try {
            if ($existing) {
                // Change vote: remove old weight, add new
                $oldVote = $existing['vote'];
                $oldWeight = (float)$existing['weight_gsm'];
                $voteCol = 'votes_' . $oldVote;
                $db->prepare("UPDATE gsm_governance_proposals SET $voteCol = $voteCol - ? WHERE id = ?")
                   ->execute([$oldWeight, $propId]);

                // Update vote
                $db->prepare("UPDATE gsm_governance_votes SET vote = ?, weight_gsm = ?, comment = ? WHERE id = ?")
                   ->execute([$vote, $power, $comment, $existing['id']]);
            } else {
                // New vote
                $db->prepare("INSERT INTO gsm_governance_votes (proposal_id, voter_id, vote, weight_gsm, comment, created_at)
                              VALUES (?, ?, ?, ?, ?, NOW())")
                   ->execute([$propId, $user['client_id'], $vote, $power, $comment]);

                $db->prepare("UPDATE gsm_governance_proposals SET voter_count = voter_count + 1 WHERE id = ?")
                   ->execute([$propId]);
            }

            // Add new weight
            $newCol = 'votes_' . $vote;
            $db->prepare("UPDATE gsm_governance_proposals SET $newCol = $newCol + ? WHERE id = ?")
               ->execute([$power, $propId]);

            $db->commit();

            echo json_encode([
                'success' => true,
                'message' => ($existing ? 'Vote changed' : 'Vote cast') . ': ' . $vote,
                'weight' => $power,
            ]);
        } catch (\Exception $e) {
            $db->rollBack();
            echo json_encode(['error' => 'Vote failed: ' . $e->getMessage()]);
        }
        break;

    // ── My votes ──
    case 'my-votes':
        $user = govRequireAuth();
        $stmt = $db->prepare("SELECT v.*, p.title, p.status AS proposal_status, p.ends_at
                              FROM gsm_governance_votes v
                              JOIN gsm_governance_proposals p ON p.id = v.proposal_id
                              WHERE v.voter_id = ?
                              ORDER BY v.created_at DESC LIMIT 50");
        $stmt->execute([$user['client_id']]);
        $votes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Voting power
        $power = getVotingPower($db, $user['client_id']);

        // Delegation info
        $delStmt = $db->prepare("SELECT d.delegate_id, c.firstname as delegate_name
                                 FROM gsm_governance_delegates d
                                 JOIN clients c ON c.id = d.delegate_id
                                 WHERE d.delegator_id = ?");
        $delStmt->execute([$user['client_id']]);
        $myDelegate = $delStmt->fetch(PDO::FETCH_ASSOC) ?: null;

        // People who delegated to me
        $fromStmt = $db->prepare("SELECT d.delegator_id, c.firstname as delegator_name,
                                         COALESCE(b.available_balance, 0) + COALESCE(b.staked_amount, 0) as their_power
                                  FROM gsm_governance_delegates d
                                  JOIN clients c ON c.id = d.delegator_id
                                  LEFT JOIN crypto_gsm_balances b ON b.client_id = d.delegator_id
                                  WHERE d.delegate_id = ?");
        $fromStmt->execute([$user['client_id']]);
        $delegators = $fromStmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'votes' => $votes,
            'voting_power' => $power,
            'my_delegate' => $myDelegate,
            'delegated_to_me' => $delegators,
        ]);
        break;

    // ── Delegate voting power ──
    case 'delegate':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        requireCSRF();
        $user = govRequireAuth();
        $data = json_decode(file_get_contents('php://input'), true) ?: [];

        $delegateEmail = trim($data['delegate_email'] ?? '');
        if (!$delegateEmail) { echo json_encode(['error' => 'Delegate email required']); break; }

        // Find delegate
        $delStmt = $db->prepare("SELECT id, firstname FROM clients WHERE email = ? AND status = 'active' LIMIT 1");
        $delStmt->execute([$delegateEmail]);
        $delegate = $delStmt->fetch(PDO::FETCH_ASSOC);
        if (!$delegate) { echo json_encode(['error' => 'Delegate not found']); break; }
        if ((int)$delegate['id'] === $user['client_id']) { echo json_encode(['error' => 'Cannot delegate to yourself']); break; }

        // Prevent circular delegation
        $circStmt = $db->prepare("SELECT delegate_id FROM gsm_governance_delegates WHERE delegator_id = ?");
        $circStmt->execute([$delegate['id']]);
        $theirDelegate = $circStmt->fetch(PDO::FETCH_ASSOC);
        if ($theirDelegate && (int)$theirDelegate['delegate_id'] === $user['client_id']) {
            echo json_encode(['error' => 'Circular delegation not allowed']);
            break;
        }

        $db->prepare("INSERT INTO gsm_governance_delegates (delegator_id, delegate_id, created_at)
                      VALUES (?, ?, NOW())
                      ON DUPLICATE KEY UPDATE delegate_id = ?, created_at = NOW()")
           ->execute([$user['client_id'], $delegate['id'], $delegate['id']]);

        echo json_encode(['success' => true, 'message' => 'Delegated voting power to ' . $delegate['firstname']]);
        break;

    // ── Undelegate ──
    case 'undelegate':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        requireCSRF();
        $user = govRequireAuth();
        $db->prepare("DELETE FROM gsm_governance_delegates WHERE delegator_id = ?")
           ->execute([$user['client_id']]);
        echo json_encode(['success' => true, 'message' => 'Delegation removed']);
        break;

    // ── Governance stats (public) ──
    case 'governance-stats':
        $stats = [];

        // Proposal counts
        $stmt = $db->query("SELECT status, COUNT(*) as cnt FROM gsm_governance_proposals GROUP BY status");
        $stats['by_status'] = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { $stats['by_status'][$row['status']] = (int)$row['cnt']; }

        // Category breakdown
        $stmt = $db->query("SELECT category, COUNT(*) as cnt FROM gsm_governance_proposals GROUP BY category ORDER BY cnt DESC");
        $stats['by_category'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Total unique voters
        $stats['unique_voters'] = (int)$db->query("SELECT COUNT(DISTINCT voter_id) FROM gsm_governance_votes")->fetchColumn();

        // Total GSM voted
        $stats['total_gsm_voted'] = (float)$db->query("SELECT COALESCE(SUM(weight_gsm), 0) FROM gsm_governance_votes")->fetchColumn();

        // Active delegations
        $stats['active_delegations'] = (int)$db->query("SELECT COUNT(*) FROM gsm_governance_delegates")->fetchColumn();

        // Circulating supply
        $stats['circulating_supply'] = getCirculatingSupply($db);

        // Most active voters
        $stmt = $db->query("SELECT c.firstname as name, COUNT(v.id) as votes, SUM(v.weight_gsm) as total_weight
                            FROM gsm_governance_votes v
                            JOIN clients c ON c.id = v.voter_id
                            GROUP BY v.voter_id ORDER BY votes DESC LIMIT 10");
        $stats['top_voters'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Recently resolved
        $stmt = $db->query("SELECT id, title, status, votes_for, votes_against, voter_count, resolved_at
                            FROM gsm_governance_proposals
                            WHERE status IN ('passed','rejected')
                            ORDER BY resolved_at DESC LIMIT 5");
        $stats['recent_resolved'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'stats' => $stats]);
        break;

    // ── Admin: Manually resolve/execute a proposal ──
    case 'admin-resolve':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['error' => 'POST required']); break; }
        $user = govRequireAuth();
        if (!govIsCommander()) { echo json_encode(['error' => 'Commander access required']); break; }

        $data = json_decode(file_get_contents('php://input'), true) ?: [];
        $propId = (int)($data['proposal_id'] ?? 0);
        $newStatus = $data['status'] ?? '';
        $notes = mb_substr(trim($data['notes'] ?? ''), 0, 2000);

        if (!in_array($newStatus, ['passed', 'rejected', 'cancelled', 'executed'], true)) {
            echo json_encode(['error' => 'Status must be: passed, rejected, cancelled, or executed']);
            break;
        }

        $db->prepare("UPDATE gsm_governance_proposals SET status = ?, resolved_at = NOW(), execution_notes = ? WHERE id = ?")
           ->execute([$newStatus, $notes, $propId]);

        echo json_encode(['success' => true, 'message' => 'Proposal #' . $propId . ' set to ' . $newStatus]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action', 'valid_actions' => [
            'proposals', 'proposal-detail', 'create-proposal', 'vote', 'my-votes',
            'delegate', 'undelegate', 'governance-stats', 'admin-resolve'
        ]]);
}
