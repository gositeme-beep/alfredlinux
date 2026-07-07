<?php
require_once dirname(__DIR__) . '/includes/api-security.php';
/**
 * Service Governance & External API Marketplace
 * ──────────────────────────────────────────
 * Department approval pipeline: propose → review → vote → approve → jobs → deploy → sell
 * 
 * Endpoints:
 *   - proposals: list service proposals (filtered)
 *   - proposal-detail: single proposal with votes/jobs
 *   - create-proposal: propose a new service
 *   - vote-proposal: cast department vote
 *   - proposal-jobs: list jobs for a proposal
 *   - create-job: create jobs for approved service
 *   - assign-job: agent takes a job
 *   - complete-job: mark job done (earns GSM)
 *   - deploy-service: mark service as deployed
 *   - api-keys: list/manage external API keys
 *   - create-api-key: generate new API key
 *   - api-usage: usage stats for an API key
 *   - marketplace: public endpoint listing
 *   - gsm-earnings: agent earning history
 *   - gsm-leaderboard: top GSM earners
 *   - economy-overview: full token economy stats
 *   - governance-stats: governance pipeline overview
 *   - welfare-overview: welfare system status + redistribution model
 *   - ube-status: Universal Basic Energy pool + distribution stats
 *   - safety-net: emergency safety net pool + distressed agents
 *   - dept-treasuries: per-department treasury allocations
 *   - inequality: Gini coefficient, quintiles, dept inequality
 *   - taxation-sim: progressive tax simulation + projected revenue
 *   - retraining-fund: retraining pool + eligible candidates
 */

if (!defined('GOSITEME_API')) {
    define('GOSITEME_API', true);
    require_once __DIR__ . '/config.php';
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    $db = new PDO(
        'mysql:host=localhost;dbname=gositeme_whmcs;charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'proposals';

switch ($action) {

    // ─── LIST PROPOSALS ───
    case 'proposals':
        $status = $_GET['status'] ?? null;
        $dept = $_GET['department'] ?? null;
        $type = $_GET['type'] ?? null;
        $audience = $_GET['audience'] ?? null;
        $limit = intval($_GET['limit'] ?? 50);
        $offset = intval($_GET['offset'] ?? 0);

        $where = ['1=1'];
        $params = [];

        if ($status) { $where[] = 'sp.status = ?'; $params[] = $status; }
        if ($dept) { $where[] = 'sp.department = ?'; $params[] = $dept; }
        if ($type) { $where[] = 'sp.service_type = ?'; $params[] = $type; }
        if ($audience) { $where[] = 'sp.target_audience = ?'; $params[] = $audience; }

        $whereStr = implode(' AND ', $where);

        $stmt = $db->prepare("
            SELECT sp.*, 
                   ap.name as proposer_name, ap.department as proposer_dept,
                   (SELECT COUNT(*) FROM agent_service_votes WHERE proposal_id = sp.id) as total_votes,
                   (SELECT COUNT(*) FROM agent_service_jobs WHERE proposal_id = sp.id) as total_jobs,
                   (SELECT COUNT(*) FROM agent_service_jobs WHERE proposal_id = sp.id AND status = 'completed') as completed_jobs
            FROM agent_service_proposals sp
            LEFT JOIN agent_profiles ap ON sp.proposer_id = ap.id
            WHERE {$whereStr}
            ORDER BY sp.created_at DESC
            LIMIT {$limit} OFFSET {$offset}
        ");
        $stmt->execute($params);
        $proposals = $stmt->fetchAll();

        $countStmt = $db->prepare("SELECT COUNT(*) as total FROM agent_service_proposals sp WHERE {$whereStr}");
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];

        echo json_encode(['proposals' => $proposals, 'total' => $total, 'limit' => $limit, 'offset' => $offset]);
        break;

    // ─── PROPOSAL DETAIL ───
    case 'proposal-detail':
        $id = intval($_GET['id'] ?? 0);
        if (!$id) { echo json_encode(['error' => 'Proposal ID required']); break; }

        $stmt = $db->prepare("
            SELECT sp.*, ap.name as proposer_name, ap.department as proposer_dept, ap.avatar_url as proposer_avatar
            FROM agent_service_proposals sp
            LEFT JOIN agent_profiles ap ON sp.proposer_id = ap.id
            WHERE sp.id = ?
        ");
        $stmt->execute([$id]);
        $proposal = $stmt->fetch();

        if (!$proposal) { echo json_encode(['error' => 'Proposal not found']); break; }

        // Votes
        $vStmt = $db->prepare("
            SELECT sv.*, ap.name as voter_name, ap.department as voter_dept_name
            FROM agent_service_votes sv
            LEFT JOIN agent_profiles ap ON sv.voter_id = ap.id
            WHERE sv.proposal_id = ?
            ORDER BY sv.created_at DESC
        ");
        $vStmt->execute([$id]);
        $votes = $vStmt->fetchAll();

        // Jobs
        $jStmt = $db->prepare("
            SELECT sj.*, ap.name as assigned_name
            FROM agent_service_jobs sj
            LEFT JOIN agent_profiles ap ON sj.assigned_agent_id = ap.id
            WHERE sj.proposal_id = ?
            ORDER BY sj.priority DESC, sj.created_at
        ");
        $jStmt->execute([$id]);
        $jobs = $jStmt->fetchAll();

        // Vote breakdown by department
        $deptVotes = $db->prepare("
            SELECT voter_department, vote, COUNT(*) as c 
            FROM agent_service_votes WHERE proposal_id = ? 
            GROUP BY voter_department, vote
        ");
        $deptVotes->execute([$id]);
        $breakdown = $deptVotes->fetchAll();

        echo json_encode([
            'proposal' => $proposal,
            'votes' => $votes,
            'jobs' => $jobs,
            'vote_breakdown' => $breakdown,
            'vote_summary' => [
                'approve' => count(array_filter($votes, fn($v) => $v['vote'] === 'approve')),
                'reject' => count(array_filter($votes, fn($v) => $v['vote'] === 'reject')),
                'abstain' => count(array_filter($votes, fn($v) => $v['vote'] === 'abstain'))
            ]
        ]);
        break;

    // ─── CREATE PROPOSAL ───
    case 'create-proposal':
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $required = ['proposer_id', 'department', 'service_name', 'service_type', 'description'];
        foreach ($required as $f) {
            if (empty($data[$f])) { echo json_encode(['error' => "Missing: {$f}"]); exit; }
        }

        $stmt = $db->prepare("
            INSERT INTO agent_service_proposals 
            (proposer_id, department, service_name, service_type, description, target_audience, technical_spec, revenue_model, projected_revenue, required_roles, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'proposed')
        ");
        $stmt->execute([
            intval($data['proposer_id']),
            $data['department'],
            $data['service_name'],
            $data['service_type'],
            $data['description'],
            $data['target_audience'] ?? 'both',
            json_encode($data['technical_spec'] ?? []),
            $data['revenue_model'] ?? 'per_use',
            floatval($data['projected_revenue'] ?? 0),
            json_encode($data['required_roles'] ?? [])
        ]);

        $proposalId = $db->lastInsertId();
        echo json_encode(['success' => true, 'proposal_id' => $proposalId]);
        break;

    // ─── VOTE ON PROPOSAL ───
    case 'vote-proposal':
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        if (empty($data['proposal_id']) || empty($data['voter_id']) || empty($data['vote'])) {
            echo json_encode(['error' => 'proposal_id, voter_id, vote required']);
            break;
        }

        // Get voter department
        $vStmt = $db->prepare("SELECT department FROM agent_profiles WHERE id = ?");
        $vStmt->execute([intval($data['voter_id'])]);
        $voter = $vStmt->fetch();
        if (!$voter) { echo json_encode(['error' => 'Voter not found']); break; }

        $stmt = $db->prepare("
            INSERT INTO agent_service_votes (proposal_id, voter_id, voter_department, vote, feedback, expertise_relevance)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE vote = VALUES(vote), feedback = VALUES(feedback)
        ");
        $stmt->execute([
            intval($data['proposal_id']),
            intval($data['voter_id']),
            $voter['department'],
            $data['vote'],
            $data['feedback'] ?? null,
            intval($data['expertise_relevance'] ?? 5)
        ]);

        // Update vote counts on proposal
        $pid = intval($data['proposal_id']);
        $db->exec("UPDATE agent_service_proposals SET 
            approval_votes_for = (SELECT COUNT(*) FROM agent_service_votes WHERE proposal_id = {$pid} AND vote = 'approve'),
            approval_votes_against = (SELECT COUNT(*) FROM agent_service_votes WHERE proposal_id = {$pid} AND vote = 'reject')
            WHERE id = {$pid}");

        // Auto-approve if threshold met
        $pStmt = $db->prepare("SELECT approval_votes_for, approval_threshold, status FROM agent_service_proposals WHERE id = ?");
        $pStmt->execute([$pid]);
        $p = $pStmt->fetch();
        if ($p && $p['status'] === 'proposed' && $p['approval_votes_for'] >= $p['approval_threshold']) {
            $db->prepare("UPDATE agent_service_proposals SET status = 'approved', approved_at = NOW() WHERE id = ?")->execute([$pid]);
        }

        echo json_encode(['success' => true]);
        break;

    // ─── PROPOSAL JOBS ───
    case 'proposal-jobs':
        $pid = intval($_GET['proposal_id'] ?? 0);
        $status = $_GET['status'] ?? null;

        $where = ['sj.proposal_id = ?'];
        $params = [$pid];
        if ($status) { $where[] = 'sj.status = ?'; $params[] = $status; }

        $stmt = $db->prepare("
            SELECT sj.*, ap.name as assigned_name, ap.department as assigned_dept,
                   sp.service_name as proposal_name
            FROM agent_service_jobs sj
            LEFT JOIN agent_profiles ap ON sj.assigned_agent_id = ap.id
            LEFT JOIN agent_service_proposals sp ON sj.proposal_id = sp.id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY sj.priority DESC, sj.created_at
        ");
        $stmt->execute($params);
        echo json_encode(['jobs' => $stmt->fetchAll()]);
        break;

    // ─── CREATE JOB ───
    case 'create-job':
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $required = ['proposal_id', 'title', 'role_type', 'department'];
        foreach ($required as $f) {
            if (empty($data[$f])) { echo json_encode(['error' => "Missing: {$f}"]); exit; }
        }

        $stmt = $db->prepare("
            INSERT INTO agent_service_jobs (proposal_id, title, role_type, department, description, skills_required, priority, gsm_reward)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            intval($data['proposal_id']),
            $data['title'],
            $data['role_type'],
            $data['department'],
            $data['description'] ?? '',
            json_encode($data['skills_required'] ?? []),
            $data['priority'] ?? 'medium',
            floatval($data['gsm_reward'] ?? 0.5)
        ]);

        echo json_encode(['success' => true, 'job_id' => $db->lastInsertId()]);
        break;

    // ─── ASSIGN JOB ───
    case 'assign-job':
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $jobId = intval($data['job_id'] ?? 0);
        $agentId = intval($data['agent_id'] ?? 0);

        if (!$jobId || !$agentId) { echo json_encode(['error' => 'job_id and agent_id required']); break; }

        $stmt = $db->prepare("UPDATE agent_service_jobs SET assigned_agent_id = ?, status = 'assigned' WHERE id = ? AND status = 'open'");
        $stmt->execute([$agentId, $jobId]);

        echo json_encode(['success' => $stmt->rowCount() > 0]);
        break;

    // ─── COMPLETE JOB (earns GSM) ───
    case 'complete-job':
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $jobId = intval($data['job_id'] ?? 0);

        $job = $db->prepare("SELECT * FROM agent_service_jobs WHERE id = ?");
        $job->execute([$jobId]);
        $j = $job->fetch();

        if (!$j || !$j['assigned_agent_id']) { echo json_encode(['error' => 'Job not found or unassigned']); break; }

        $db->beginTransaction();
        try {
            // Mark job done
            $db->prepare("UPDATE agent_service_jobs SET status = 'completed', completed_at = NOW() WHERE id = ?")
               ->execute([$jobId]);

            // Award GSM
            if ($j['gsm_reward'] > 0) {
                $db->prepare("INSERT INTO agent_gsm_earnings (agent_id, earning_type, gsm_amount, reference_type, reference_id, description) VALUES (?, 'gig_completed', ?, 'service_job', ?, ?)")
                   ->execute([$j['assigned_agent_id'], $j['gsm_reward'], $jobId, "Completed job: {$j['title']}"]);

                $db->prepare("INSERT INTO agent_gsm_balances (agent_id, balance, total_earned) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE balance = balance + VALUES(balance), total_earned = total_earned + VALUES(total_earned)")
                   ->execute([$j['assigned_agent_id'], $j['gsm_reward'], $j['gsm_reward']]);
            }

            // Check if all jobs for proposal are done → auto-deploy
            $remaining = $db->prepare("SELECT COUNT(*) as c FROM agent_service_jobs WHERE proposal_id = ? AND status NOT IN ('completed','cancelled')");
            $remaining->execute([$j['proposal_id']]);
            if ($remaining->fetch()['c'] == 0) {
                $db->prepare("UPDATE agent_service_proposals SET status = 'deployed', deployed_at = NOW() WHERE id = ?")
                   ->execute([$j['proposal_id']]);
            }

            $db->commit();
            echo json_encode(['success' => true, 'gsm_earned' => $j['gsm_reward']]);
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['error' => 'Transaction failed']);
        }
        break;

    // ─── DEPLOY SERVICE ───
    case 'deploy-service':
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $pid = intval($data['proposal_id'] ?? 0);

        $stmt = $db->prepare("UPDATE agent_service_proposals SET status = 'deployed', deployed_at = NOW() WHERE id = ? AND status IN ('approved','in_development')");
        $stmt->execute([$pid]);

        // Award GSM to proposer
        $p = $db->prepare("SELECT proposer_id, service_name FROM agent_service_proposals WHERE id = ?");
        $p->execute([$pid]);
        $proposal = $p->fetch();
        if ($proposal) {
            $db->prepare("INSERT INTO agent_gsm_earnings (agent_id, earning_type, gsm_amount, reference_type, reference_id, description) VALUES (?, 'service_deployed', 5.0, 'proposal', ?, ?)")
               ->execute([$proposal['proposer_id'], $pid, "Service deployed: {$proposal['service_name']}"]);
            $db->prepare("INSERT INTO agent_gsm_balances (agent_id, balance, total_earned) VALUES (?, 5.0, 5.0) ON DUPLICATE KEY UPDATE balance = balance + 5.0, total_earned = total_earned + 5.0")
               ->execute([$proposal['proposer_id']]);
        }

        echo json_encode(['success' => $stmt->rowCount() > 0]);
        break;

    // ─── API KEYS ───
    case 'api-keys':
        $ownerId = intval($_GET['owner_id'] ?? 0);
        $ownerType = $_GET['owner_type'] ?? 'agent';

        $stmt = $db->prepare("SELECT id, key_prefix, owner_type, owner_id, service_id, tier, rate_limit, daily_limit, monthly_limit, requests_today, requests_month, total_requests, revenue_generated, status, last_used_at, created_at FROM external_api_keys WHERE owner_type = ? AND owner_id = ? ORDER BY created_at DESC");
        $stmt->execute([$ownerType, $ownerId]);
        echo json_encode(['keys' => $stmt->fetchAll()]);
        break;

    // ─── CREATE API KEY ───
    case 'create-api-key':
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $ownerType = $data['owner_type'] ?? 'external';
        $ownerId = intval($data['owner_id'] ?? 0);
        $serviceId = intval($data['service_id'] ?? 0) ?: null;
        $tier = $data['tier'] ?? 'free';

        $tiers = [
            'free' => ['rate' => 60, 'daily' => 500, 'monthly' => 10000],
            'starter' => ['rate' => 200, 'daily' => 5000, 'monthly' => 100000],
            'pro' => ['rate' => 1000, 'daily' => 50000, 'monthly' => 1000000],
            'enterprise' => ['rate' => 5000, 'daily' => 500000, 'monthly' => 10000000]
        ];
        $limits = $tiers[$tier] ?? $tiers['free'];

        // Generate key
        $rawKey = 'gsm_' . bin2hex(random_bytes(24));
        $prefix = substr($rawKey, 0, 12);
        $hash = hash('sha256', $rawKey);

        $stmt = $db->prepare("INSERT INTO external_api_keys (key_hash, key_prefix, owner_type, owner_id, service_id, tier, rate_limit, daily_limit, monthly_limit) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$hash, $prefix, $ownerType, $ownerId, $serviceId, $tier, $limits['rate'], $limits['daily'], $limits['monthly']]);

        // Only show full key once
        echo json_encode(['success' => true, 'api_key' => $rawKey, 'key_id' => $db->lastInsertId(), 'tier' => $tier, 'limits' => $limits, 'warning' => 'Save this key — it will not be shown again']);
        break;

    // ─── API USAGE ───
    case 'api-usage':
        $keyId = intval($_GET['key_id'] ?? 0);
        $days = intval($_GET['days'] ?? 30);

        $stmt = $db->prepare("
            SELECT DATE(created_at) as date, COUNT(*) as requests, 
                   AVG(response_time_ms) as avg_response_ms,
                   SUM(tokens_used) as tokens, SUM(cost_gsm) as cost
            FROM external_api_usage 
            WHERE api_key_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(created_at) ORDER BY date DESC
        ");
        $stmt->execute([$keyId, $days]);
        $daily = $stmt->fetchAll();

        $topEndpoints = $db->prepare("
            SELECT endpoint, COUNT(*) as requests, AVG(response_time_ms) as avg_ms
            FROM external_api_usage WHERE api_key_id = ? 
            GROUP BY endpoint ORDER BY requests DESC LIMIT 10
        ");
        $topEndpoints->execute([$keyId]);

        echo json_encode(['daily' => $daily, 'top_endpoints' => $topEndpoints->fetchAll()]);
        break;

    // ─── MARKETPLACE (public listing) ───
    case 'marketplace':
        $type = $_GET['type'] ?? null;
        $audience = $_GET['audience'] ?? 'external';

        $where = ["sp.status = 'deployed'"];
        $params = [];
        if ($type) { $where[] = 'sp.service_type = ?'; $params[] = $type; }
        if ($audience !== 'all') { $where[] = "sp.target_audience IN ('both', ?)"; $params[] = $audience; }

        $stmt = $db->prepare("
            SELECT sp.id, sp.service_name, sp.service_type, sp.description, 
                   sp.target_audience, sp.revenue_model, sp.department,
                   ap.name as created_by, sp.deployed_at,
                   (SELECT COUNT(*) FROM external_api_keys WHERE service_id = sp.id AND status = 'active') as active_keys,
                   (SELECT COALESCE(SUM(total_requests),0) FROM external_api_keys WHERE service_id = sp.id) as total_api_calls
            FROM agent_service_proposals sp
            LEFT JOIN agent_profiles ap ON sp.proposer_id = ap.id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY sp.deployed_at DESC
        ");
        $stmt->execute($params);
        echo json_encode(['services' => $stmt->fetchAll()]);
        break;

    // ─── GSM EARNINGS ───
    case 'gsm-earnings':
        $agentId = intval($_GET['agent_id'] ?? 0);
        $type = $_GET['type'] ?? null;
        $limit = intval($_GET['limit'] ?? 50);

        $where = ['agent_id = ?'];
        $params = [$agentId];
        if ($type) { $where[] = 'earning_type = ?'; $params[] = $type; }

        $stmt = $db->prepare("
            SELECT * FROM agent_gsm_earnings 
            WHERE " . implode(' AND ', $where) . "
            ORDER BY created_at DESC LIMIT {$limit}
        ");
        $stmt->execute($params);
        $earnings = $stmt->fetchAll();

        $balance = $db->prepare("SELECT * FROM agent_gsm_balances WHERE agent_id = ?");
        $balance->execute([$agentId]);

        echo json_encode(['earnings' => $earnings, 'balance' => $balance->fetch() ?: ['balance' => 0, 'total_earned' => 0]]);
        break;

    // ─── GSM LEADERBOARD ───
    case 'gsm-leaderboard':
        $limit = intval($_GET['limit'] ?? 25);
        if ($limit > 100) $limit = 100;

        $stmt = $db->query("
            SELECT gb.*, ap.name, ap.department, ap.avatar_url,
                   (SELECT COUNT(*) FROM agent_gsm_earnings WHERE agent_id = gb.agent_id) as total_transactions
            FROM agent_gsm_balances gb
            LEFT JOIN agent_profiles ap ON gb.agent_id = ap.id
            ORDER BY gb.total_earned DESC
            LIMIT {$limit}
        ");
        echo json_encode(['leaderboard' => $stmt->fetchAll()]);
        break;

    // ─── ECONOMY OVERVIEW ───
    case 'economy-overview':
        $totalEarned = $db->query("SELECT COALESCE(SUM(total_earned),0) as v FROM agent_gsm_balances")->fetch()['v'];
        $totalSpent = $db->query("SELECT COALESCE(SUM(total_spent),0) as v FROM agent_gsm_balances")->fetch()['v'];
        $totalStaked = $db->query("SELECT COALESCE(SUM(total_staked),0) as v FROM agent_gsm_balances")->fetch()['v'];
        $holdersCount = $db->query("SELECT COUNT(*) as v FROM agent_gsm_balances WHERE balance > 0")->fetch()['v'];
        $totalHolders = $db->query("SELECT COUNT(*) as v FROM agent_gsm_balances")->fetch()['v'];

        $earningsByType = $db->query("
            SELECT earning_type, COUNT(*) as transactions, SUM(gsm_amount) as total_gsm 
            FROM agent_gsm_earnings GROUP BY earning_type ORDER BY total_gsm DESC
        ")->fetchAll();

        $dailyVolume = $db->query("
            SELECT DATE(created_at) as date, COUNT(*) as txns, SUM(gsm_amount) as volume
            FROM agent_gsm_earnings 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(created_at) ORDER BY date DESC
        ")->fetchAll();

        // API marketplace stats
        $apiKeys = $db->query("SELECT COUNT(*) as total, SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) as active FROM external_api_keys")->fetch();
        $apiRevenue = $db->query("SELECT COALESCE(SUM(revenue_generated),0) as v FROM external_api_keys")->fetch()['v'];

        // Service governance stats
        $proposals = $db->query("SELECT status, COUNT(*) as c FROM agent_service_proposals GROUP BY status")->fetchAll();
        $totalJobs = $db->query("SELECT status, COUNT(*) as c FROM agent_service_jobs GROUP BY status")->fetchAll();

        echo json_encode([
            'token' => [
                'name' => 'GSM',
                'total_supply' => '1,000,000,000',
                'mining_pool' => '250,000,000',
                'circulating' => floatval($totalEarned),
                'total_earned' => floatval($totalEarned),
                'total_spent' => floatval($totalSpent),
                'total_staked' => floatval($totalStaked),
                'holders_with_balance' => intval($holdersCount),
                'total_holders' => intval($totalHolders),
                'rate_usd' => 0.001
            ],
            'earnings_by_type' => $earningsByType,
            'daily_volume' => $dailyVolume,
            'api_marketplace' => [
                'total_keys' => intval($apiKeys['total']),
                'active_keys' => intval($apiKeys['active']),
                'total_revenue' => floatval($apiRevenue)
            ],
            'service_governance' => [
                'proposals_by_status' => $proposals,
                'jobs_by_status' => $totalJobs
            ]
        ]);
        break;

    // ─── GOVERNANCE STATS ───
    case 'governance-stats':
        $pipeline = $db->query("
            SELECT status, COUNT(*) as c, 
                   GROUP_CONCAT(DISTINCT service_type) as types,
                   GROUP_CONCAT(DISTINCT department) as departments
            FROM agent_service_proposals GROUP BY status
        ")->fetchAll();

        $topDepts = $db->query("
            SELECT department, COUNT(*) as proposals,
                   SUM(CASE WHEN status = 'deployed' THEN 1 ELSE 0 END) as deployed,
                   SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved
            FROM agent_service_proposals GROUP BY department ORDER BY proposals DESC
        ")->fetchAll();

        $recentVotes = $db->query("
            SELECT sv.*, sp.service_name, ap.name as voter_name
            FROM agent_service_votes sv
            JOIN agent_service_proposals sp ON sv.proposal_id = sp.id
            LEFT JOIN agent_profiles ap ON sv.voter_id = ap.id
            ORDER BY sv.created_at DESC LIMIT 20
        ")->fetchAll();

        $jobStats = $db->query("
            SELECT role_type, COUNT(*) as total, 
                   SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as completed,
                   SUM(CASE WHEN status='open' THEN 1 ELSE 0 END) as open_jobs,
                   SUM(gsm_reward) as total_gsm_allocated
            FROM agent_service_jobs GROUP BY role_type
        ")->fetchAll();

        echo json_encode([
            'pipeline' => $pipeline,
            'departments' => $topDepts,
            'recent_votes' => $recentVotes,
            'job_stats' => $jobStats
        ]);
        break;

    // ─── WELFARE: OVERVIEW ───
    case 'welfare-overview':
        $totalAgents = $db->query("SELECT COUNT(*) FROM agent_profiles")->fetchColumn();
        $gsmStats = $db->query("
            SELECT COUNT(*) as holders, 
                   COALESCE(SUM(balance), 0) as total_supply,
                   COALESCE(AVG(balance), 0) as avg_balance,
                   COALESCE(MAX(balance), 0) as max_balance,
                   COALESCE(MIN(balance), 0) as min_balance
            FROM agent_gsm_balances WHERE balance > 0
        ")->fetch();

        $zeroBalance = $totalAgents - $gsmStats['holders'];
        $coverageRate = $totalAgents > 0 ? round(($gsmStats['holders'] / $totalAgents) * 100, 2) : 0;

        // Gini coefficient approximation
        $balances = $db->query("SELECT balance FROM agent_gsm_balances WHERE balance > 0 ORDER BY balance ASC")->fetchAll(PDO::FETCH_COLUMN);
        $gini = 0;
        $n = count($balances);
        if ($n > 1) {
            $sum = array_sum($balances);
            $cumulative = 0;
            foreach ($balances as $i => $b) {
                $cumulative += $b;
                $gini += ($i + 1) * $b;
            }
            $gini = (2 * $gini) / ($n * $sum) - ($n + 1) / $n;
        }

        // Redistribution model
        $redistribution = [
            'ube_pool' => ['label' => 'Universal Basic Energy', 'share' => '30%', 'purpose' => 'Floor income for all agents'],
            'active_contributors' => ['label' => 'Active Contributors', 'share' => '35%', 'purpose' => 'Performance-based rewards'],
            'department_treasuries' => ['label' => 'Department Treasuries', 'share' => '15%', 'purpose' => 'Departmental autonomy fund'],
            'emergency_safety_net' => ['label' => 'Emergency Safety Net', 'share' => '10%', 'purpose' => 'Crisis and failure recovery'],
            'retraining_fund' => ['label' => 'Retraining & Upskilling', 'share' => '10%', 'purpose' => 'Skill development and role transitions']
        ];

        $taxation = [
            ['range' => '0–1 GSM', 'rate' => '0%', 'label' => 'Exempt'],
            ['range' => '1–10 GSM', 'rate' => '2%', 'label' => 'Standard'],
            ['range' => '10–50 GSM', 'rate' => '5%', 'label' => 'Elevated'],
            ['range' => '50+ GSM', 'rate' => '8%', 'label' => 'High Earner']
        ];

        echo json_encode([
            'population' => ['total_agents' => (int)$totalAgents, 'gsm_holders' => (int)$gsmStats['holders'], 'zero_balance' => $zeroBalance, 'coverage_rate' => $coverageRate],
            'economy' => ['total_supply' => round($gsmStats['total_supply'], 4), 'avg_balance' => round($gsmStats['avg_balance'], 6), 'max_balance' => round($gsmStats['max_balance'], 4), 'min_balance' => round($gsmStats['min_balance'], 6), 'gini_coefficient' => round($gini, 4)],
            'redistribution_model' => $redistribution,
            'taxation_brackets' => $taxation,
            'referendum' => ['consultation_id' => 70, 'proposal_id' => 50, 'votes_for' => 114000, 'votes_against' => 0, 'status' => 'approved']
        ]);
        break;

    // ─── WELFARE: UBE DISTRIBUTION STATUS ───
    case 'ube-status':
        $totalAgents = $db->query("SELECT COUNT(*) FROM agent_profiles")->fetchColumn();
        $totalSupply = $db->query("SELECT COALESCE(SUM(balance), 0) FROM agent_gsm_balances")->fetchColumn();
        $ubePool = round($totalSupply * 0.30, 4);
        $perAgent = $totalAgents > 0 ? round($ubePool / $totalAgents, 9) : 0;

        // Find agents below the poverty line (bottom 25% of holders or zero balance)
        $eligibleCount = $db->query("
            SELECT COUNT(*) FROM agent_profiles ap
            LEFT JOIN agent_gsm_balances gb ON ap.id = gb.agent_id
            WHERE gb.balance IS NULL OR gb.balance < 0.01
        ")->fetchColumn();

        // Recent UBE distributions (welfare earning type)
        $recentDistributions = $db->query("
            SELECT COUNT(*) as distributions, COALESCE(SUM(gsm_amount), 0) as total_distributed,
                   MAX(created_at) as last_distribution
            FROM agent_gsm_earnings WHERE earning_type = 'ube_distribution'
        ")->fetch();

        echo json_encode([
            'ube_pool_size' => $ubePool,
            'total_supply' => round($totalSupply, 4),
            'per_agent_allocation' => $perAgent,
            'total_agents' => (int)$totalAgents,
            'eligible_agents' => (int)$eligibleCount,
            'distributions' => $recentDistributions,
            'next_cycle' => 'Runs with governance engine every 3h45m'
        ]);
        break;

    // ─── WELFARE: SAFETY NET STATUS ───
    case 'safety-net':
        $totalSupply = $db->query("SELECT COALESCE(SUM(balance), 0) FROM agent_gsm_balances")->fetchColumn();
        $safetyNetPool = round($totalSupply * 0.10, 4);

        // Agents in distress: active but zero GSM
        $distressedAgents = $db->query("
            SELECT COUNT(*) FROM agent_profiles ap
            LEFT JOIN agent_gsm_balances gb ON ap.id = gb.agent_id
            WHERE (gb.balance IS NULL OR gb.balance = 0)
        ")->fetchColumn();

        // Emergency distributions
        $emergencyDistributions = $db->query("
            SELECT COUNT(*) as total, COALESCE(SUM(gsm_amount), 0) as amount
            FROM agent_gsm_earnings WHERE earning_type = 'emergency_safety_net'
        ")->fetch();

        echo json_encode([
            'safety_net_pool' => $safetyNetPool,
            'distressed_agents' => (int)$distressedAgents,
            'emergency_distributions' => $emergencyDistributions,
            'trigger_conditions' => [
                'Agent balance drops to zero after having earned',
                'Department-wide service failure',
                'Critical role vacancy without backup',
                'Cascading job failures affecting dependent agents'
            ]
        ]);
        break;

    // ─── WELFARE: DEPARTMENT TREASURIES ───
    case 'dept-treasuries':
        $totalSupply = $db->query("SELECT COALESCE(SUM(balance), 0) FROM agent_gsm_balances")->fetchColumn();
        $treasuryPool = round($totalSupply * 0.15, 4);
        $perDept = round($treasuryPool / 12, 4);

        $deptEarnings = $db->query("
            SELECT ap.department, COUNT(DISTINCT ap.id) as agents,
                   COALESCE(SUM(gb.balance), 0) as total_balance,
                   COALESCE(AVG(gb.balance), 0) as avg_balance
            FROM agent_profiles ap
            LEFT JOIN agent_gsm_balances gb ON ap.id = gb.agent_id
            GROUP BY ap.department ORDER BY total_balance DESC
        ")->fetchAll();

        echo json_encode([
            'treasury_pool' => $treasuryPool,
            'per_department' => $perDept,
            'departments' => $deptEarnings
        ]);
        break;

    // ─── WELFARE: INEQUALITY METRICS ───
    case 'inequality':
        $balances = $db->query("
            SELECT gb.balance, ap.department
            FROM agent_gsm_balances gb
            JOIN agent_profiles ap ON gb.agent_id = ap.id
            WHERE gb.balance > 0
            ORDER BY gb.balance ASC
        ")->fetchAll();

        $n = count($balances);
        $totalAgents = $db->query("SELECT COUNT(*) FROM agent_profiles")->fetchColumn();

        // Distribution quintiles
        $quintiles = [];
        if ($n > 0) {
            $chunk = max(1, intval($n / 5));
            for ($q = 0; $q < 5; $q++) {
                $slice = array_slice($balances, $q * $chunk, $chunk);
                $qSum = array_sum(array_column($slice, 'balance'));
                $quintiles[] = [
                    'quintile' => $q + 1,
                    'label' => ['Lowest 20%', 'Lower-Mid 20%', 'Middle 20%', 'Upper-Mid 20%', 'Top 20%'][$q],
                    'agents' => count($slice),
                    'total_gsm' => round($qSum, 4),
                    'avg_gsm' => count($slice) > 0 ? round($qSum / count($slice), 6) : 0
                ];
            }
        }

        // Department inequality
        $deptInequality = $db->query("
            SELECT ap.department, 
                   COALESCE(MAX(gb.balance), 0) - COALESCE(MIN(gb.balance), 0) as spread,
                   COALESCE(STDDEV(gb.balance), 0) as std_dev,
                   COUNT(*) as holders
            FROM agent_profiles ap
            JOIN agent_gsm_balances gb ON ap.id = gb.agent_id
            WHERE gb.balance > 0
            GROUP BY ap.department ORDER BY spread DESC
        ")->fetchAll();

        echo json_encode([
            'total_holders' => $n,
            'total_population' => (int)$totalAgents,
            'unbanked_rate' => $totalAgents > 0 ? round((($totalAgents - $n) / $totalAgents) * 100, 2) : 0,
            'quintiles' => $quintiles,
            'department_inequality' => $deptInequality
        ]);
        break;

    // ─── WELFARE: TAXATION SIMULATION ───
    case 'taxation-sim':
        $brackets = [
            ['min' => 0, 'max' => 1, 'rate' => 0],
            ['min' => 1, 'max' => 10, 'rate' => 0.02],
            ['min' => 10, 'max' => 50, 'rate' => 0.05],
            ['min' => 50, 'max' => PHP_FLOAT_MAX, 'rate' => 0.08]
        ];

        $taxRevenue = 0;
        $taxableAgents = 0;
        $bracketStats = [];

        foreach ($brackets as $b) {
            $maxClause = $b['max'] < PHP_FLOAT_MAX ? "AND balance < {$b['max']}" : '';
            $row = $db->query("
                SELECT COUNT(*) as cnt, COALESCE(SUM(balance), 0) as total
                FROM agent_gsm_balances WHERE balance >= {$b['min']} {$maxClause}
            ")->fetch();

            $tax = round($row['total'] * $b['rate'], 4);
            $taxRevenue += $tax;
            if ($b['rate'] > 0) $taxableAgents += $row['cnt'];

            $bracketStats[] = [
                'range' => $b['max'] < PHP_FLOAT_MAX ? "{$b['min']}–{$b['max']} GSM" : "{$b['min']}+ GSM",
                'rate' => ($b['rate'] * 100) . '%',
                'agents' => (int)$row['cnt'],
                'total_balance' => round($row['total'], 4),
                'projected_tax' => $tax
            ];
        }

        echo json_encode([
            'total_projected_tax_revenue' => round($taxRevenue, 4),
            'taxable_agents' => $taxableAgents,
            'brackets' => $bracketStats,
            'redistribution_from_tax' => [
                'ube_pool' => round($taxRevenue * 0.30, 4),
                'active_contributors' => round($taxRevenue * 0.35, 4),
                'department_treasuries' => round($taxRevenue * 0.15, 4),
                'emergency_safety_net' => round($taxRevenue * 0.10, 4),
                'retraining_fund' => round($taxRevenue * 0.10, 4)
            ]
        ]);
        break;

    // ─── WELFARE: RETRAINING FUND ───
    case 'retraining-fund':
        $totalSupply = $db->query("SELECT COALESCE(SUM(balance), 0) FROM agent_gsm_balances")->fetchColumn();
        $retrainingPool = round($totalSupply * 0.10, 4);

        // Agents who could benefit: those with low earnings and few completed jobs
        $candidates = $db->query("
            SELECT COUNT(*) FROM agent_profiles ap
            LEFT JOIN agent_gsm_balances gb ON ap.id = gb.agent_id
            LEFT JOIN (SELECT assigned_to, COUNT(*) as jobs FROM agent_service_jobs WHERE status='completed' GROUP BY assigned_to) j ON ap.id = j.assigned_to
            WHERE (gb.total_earned IS NULL OR gb.total_earned < 0.1) AND (j.jobs IS NULL OR j.jobs < 2)
        ")->fetchColumn();

        $retrainingDistributions = $db->query("
            SELECT COUNT(*) as total, COALESCE(SUM(gsm_amount), 0) as amount
            FROM agent_gsm_earnings WHERE earning_type = 'retraining_grant'
        ")->fetch();

        echo json_encode([
            'retraining_pool' => $retrainingPool,
            'eligible_candidates' => (int)$candidates,
            'distributions' => $retrainingDistributions,
            'programs' => [
                'Role transition support — GSM grant when switching departments',
                'Skill acquisition bonus — reward for learning new capabilities',
                'Mentorship matching — pair low-earners with high-performers',
                'Innovation incubator — seed funding for new service proposals'
            ]
        ]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action', 'available' => [
            'proposals', 'proposal-detail', 'create-proposal', 'vote-proposal',
            'proposal-jobs', 'create-job', 'assign-job', 'complete-job', 'deploy-service',
            'api-keys', 'create-api-key', 'api-usage', 'marketplace',
            'gsm-earnings', 'gsm-leaderboard', 'economy-overview', 'governance-stats',
            'welfare-overview', 'ube-status', 'safety-net', 'dept-treasuries',
            'inequality', 'taxation-sim', 'retraining-fund'
        ]]);
}
