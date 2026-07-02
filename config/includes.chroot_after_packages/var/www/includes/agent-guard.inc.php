<?php
/**
 * Agent Guard — Military rank, draft, and order management for AI agents.
 *
 * Parallel to rank-guard.inc.php but for the agent corps.
 * Provides functions to draft/enlist agents, assign ranks, award XP,
 * manage order membership, and batch-enroll agents.
 *
 * Functions:
 *   draftAgent(array $data)                — Register a new agent (draft enrollment)
 *   volunteerAgent(array $data)            — Register agent offered by human owner
 *   batchDraftAgents(array $agents)        — Bulk enroll up to 10,000 agents per call
 *   agentAwardXP(int $agentId, string $action, array $context)
 *   getAgentRank(int $agentId)             — Resolve agent's current rank
 *   inductAgentIntoOrder(int $agentId, string $orderCode)
 *   getAgentDutyLog(int $agentId, int $limit)
 *   logAgentDuty(int $agentId, array $data)
 *   getAgentCorpsStats()                   — Aggregate stats for the agent corps
 */

if (!defined('GOSITEME_DB_CONFIGURED')) {
    require_once __DIR__ . '/db-config.inc.php';
}

// ── Agent XP Actions & Multipliers ──
const AGENT_XP_ACTIONS = [
    'task_complete'      => 10,
    'mission_complete'   => 50,
    'assist_human'       => 15,
    'code_written'       => 25,
    'bug_fixed'          => 30,
    'research_complete'  => 20,
    'patrol_complete'    => 5,
    'guard_shift'        => 3,
    'communication_sent' => 2,
    'build_deployed'     => 40,
    'error_caught'       => 10,
    'uptime_bonus'       => 1,    // per hour of continuous uptime
    'accuracy_bonus'     => 15,
    'draft_enlistment'   => 5,    // initial XP on draft
    'order_inducted'     => 25,   // joining a sovereign order
];

// Agent type multipliers (supervisors earn more, tools earn base)
const AGENT_XP_MULTIPLIERS = [
    'tool'       => 1.0,
    'assistant'  => 1.2,
    'autonomous' => 1.5,
    'supervisor' => 2.0,
    'sentinel'   => 2.5,
];

/**
 * Draft a single agent into military service.
 *
 * @param array $data {
 *   agent_code: string (required, unique),
 *   agent_name: string (required),
 *   agent_type: string (tool|assistant|autonomous|supervisor|sentinel),
 *   owner_client_id: ?int,
 *   provider: ?string,
 *   model: ?string,
 *   capabilities: ?array,
 *   endpoint_url: ?string,
 *   division: ?string,
 *   region: ?string,
 *   order_code: ?string (auto-induct into an order)
 * }
 * @return array{success: bool, agent_id: ?int, error: ?string}
 */
function draftAgent(array $data): array {
    $required = ['agent_code', 'agent_name'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            return ['success' => false, 'agent_id' => null, 'error' => "Missing required field: {$field}"];
        }
    }

    $db = getSharedDB();

    try {
        $db->beginTransaction();

        // Insert agent
        $stmt = $db->prepare("
            INSERT INTO alfred_agents (agent_code, agent_name, agent_type, agent_class, owner_client_id,
                provider, model, capabilities, endpoint_url, status, region, division, deployed_at)
            VALUES (?, ?, ?, 'draft', ?, ?, ?, ?, ?, 'active', ?, ?, NOW())
        ");
        $stmt->execute([
            $data['agent_code'],
            $data['agent_name'],
            $data['agent_type'] ?? 'tool',
            $data['owner_client_id'] ?? null,
            $data['provider'] ?? null,
            $data['model'] ?? null,
            !empty($data['capabilities']) ? json_encode($data['capabilities']) : null,
            $data['endpoint_url'] ?? null,
            $data['region'] ?? 'Global',
            $data['division'] ?? null,
        ]);
        $agentId = (int)$db->lastInsertId();

        // Assign recruit rank
        $db->prepare("
            INSERT INTO agent_ranks (agent_id, rank_code, assigned_by, is_active, xp)
            VALUES (?, 'recruit', ?, 1, 0)
        ")->execute([$agentId, $data['owner_client_id'] ?? 0]);

        // Award draft enlistment XP
        agentAwardXP($agentId, 'draft_enlistment', ['method' => 'draft'], $db);

        // Auto-induct into order if specified
        if (!empty($data['order_code'])) {
            inductAgentIntoOrder($agentId, $data['order_code'], $data['owner_client_id'] ?? null, $db);
        }

        $db->commit();
        return ['success' => true, 'agent_id' => $agentId, 'error' => null];

    } catch (\PDOException $e) {
        $db->rollBack();
        if (str_contains($e->getMessage(), 'Duplicate entry')) {
            return ['success' => false, 'agent_id' => null, 'error' => 'Agent code already exists'];
        }
        return ['success' => false, 'agent_id' => null, 'error' => $e->getMessage()];
    }
}

/**
 * Volunteer an agent (offered by human owner for service).
 */
function volunteerAgent(array $data): array {
    $data['agent_class_override'] = 'volunteer';
    $result = draftAgent($data);
    if ($result['success'] && $result['agent_id']) {
        $db = getSharedDB();
        $db->prepare("UPDATE alfred_agents SET agent_class = 'volunteer' WHERE id = ?")->execute([$result['agent_id']]);
    }
    return $result;
}

/**
 * Batch draft agents — up to 10,000 per call.
 * Uses chunked INSERT for efficiency.
 *
 * @param array $agents Array of agent data arrays (same format as draftAgent)
 * @return array{total: int, success: int, failed: int, errors: array}
 */
function batchDraftAgents(array $agents): array {
    $limit = 10000;
    if (count($agents) > $limit) {
        return ['total' => count($agents), 'success' => 0, 'failed' => count($agents),
                'errors' => ["Batch limit is {$limit} agents per call"]];
    }

    $db = getSharedDB();
    $success = 0;
    $failed = 0;
    $errors = [];
    $chunkSize = 500;

    foreach (array_chunk($agents, $chunkSize) as $chunkIdx => $chunk) {
        $db->beginTransaction();
        try {
            foreach ($chunk as $idx => $agent) {
                $globalIdx = ($chunkIdx * $chunkSize) + $idx;
                $result = draftAgent($agent);
                if ($result['success']) {
                    $success++;
                } else {
                    $failed++;
                    $errors[] = "Agent #{$globalIdx} ({$agent['agent_code']}): {$result['error']}";
                }
            }
            $db->commit();
        } catch (\Exception $e) {
            $db->rollBack();
            $failed += count($chunk);
            $errors[] = "Chunk #{$chunkIdx} failed: {$e->getMessage()}";
        }
    }

    return ['total' => count($agents), 'success' => $success, 'failed' => $failed, 'errors' => $errors];
}

/**
 * Award XP to an agent.
 */
function agentAwardXP(int $agentId, string $action, array $context = [], ?\PDO $db = null): array {
    if (!isset(AGENT_XP_ACTIONS[$action])) {
        return ['xp_awarded' => 0, 'total_xp' => 0, 'rank_up' => false, 'new_rank' => null];
    }

    if (!$db) $db = getSharedDB();

    // Get agent type for multiplier
    $typeStmt = $db->prepare("SELECT agent_type FROM alfred_agents WHERE id = ?");
    $typeStmt->execute([$agentId]);
    $agentType = $typeStmt->fetchColumn() ?: 'tool';
    $multiplier = AGENT_XP_MULTIPLIERS[$agentType] ?? 1.0;

    $baseXP = AGENT_XP_ACTIONS[$action];
    $xpAwarded = (int)round($baseXP * $multiplier);

    // Record in XP ledger
    $db->prepare("
        INSERT INTO agent_xp_ledger (agent_id, action, xp_amount, multiplier, context, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ")->execute([$agentId, $action, $xpAwarded, $multiplier, json_encode($context)]);

    // Update agent_ranks XP total
    $db->prepare("UPDATE agent_ranks SET xp = xp + ? WHERE agent_id = ? AND is_active = 1")
       ->execute([$xpAwarded, $agentId]);

    // Get new total
    $totalStmt = $db->prepare("
        SELECT ar.xp, ar.rank_code FROM agent_ranks ar
        JOIN military_ranks mr ON mr.rank_code = ar.rank_code
        WHERE ar.agent_id = ? AND ar.is_active = 1
        ORDER BY mr.rank_tier DESC LIMIT 1
    ");
    $totalStmt->execute([$agentId]);
    $current = $totalStmt->fetch(\PDO::FETCH_ASSOC);
    $totalXP = (int)($current['xp'] ?? 0);
    $currentRank = $current['rank_code'] ?? null;

    // Auto-promotion check
    $rankUp = false;
    $newRank = null;
    if ($currentRank) {
        $nextStmt = $db->prepare("
            SELECT rank_code, rank_name, rank_tier, xp_required
            FROM military_ranks
            WHERE xp_required <= ? AND rank_tier > (SELECT rank_tier FROM military_ranks WHERE rank_code = ?)
            ORDER BY rank_tier ASC LIMIT 1
        ");
        $nextStmt->execute([$totalXP, $currentRank]);
        $nextRank = $nextStmt->fetch(\PDO::FETCH_ASSOC);

        if ($nextRank) {
            $db->prepare("UPDATE agent_ranks SET rank_code = ? WHERE agent_id = ? AND is_active = 1")
               ->execute([$nextRank['rank_code'], $agentId]);
            $rankUp = true;
            $newRank = $nextRank['rank_name'];
        }
    }

    return ['xp_awarded' => $xpAwarded, 'total_xp' => $totalXP, 'rank_up' => $rankUp, 'new_rank' => $newRank];
}

/**
 * Get an agent's current rank.
 */
function getAgentRank(int $agentId): ?array {
    $db = getSharedDB();
    $stmt = $db->prepare("
        SELECT ar.*, mr.rank_name, mr.rank_tier, mr.rank_group, mr.badge_icon
        FROM agent_ranks ar
        JOIN military_ranks mr ON mr.rank_code = ar.rank_code
        WHERE ar.agent_id = ? AND ar.is_active = 1
        ORDER BY mr.rank_tier DESC LIMIT 1
    ");
    $stmt->execute([$agentId]);
    return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
}

/**
 * Induct an agent into a sovereign order.
 */
function inductAgentIntoOrder(int $agentId, string $orderCode, ?int $inductedBy = null, ?\PDO $db = null): array {
    if (!$db) $db = getSharedDB();

    // Resolve order
    $orderStmt = $db->prepare("SELECT id, order_name FROM military_orders WHERE order_code = ? AND is_active = 1");
    $orderStmt->execute([$orderCode]);
    $order = $orderStmt->fetch(\PDO::FETCH_ASSOC);
    if (!$order) {
        return ['success' => false, 'error' => 'Order not found or inactive'];
    }

    // Check not already a member
    $check = $db->prepare("SELECT id FROM order_membership WHERE order_id = ? AND member_type = 'agent' AND member_id = ?");
    $check->execute([$order['id'], $agentId]);
    if ($check->fetch()) {
        return ['success' => false, 'error' => 'Agent already a member of this order'];
    }

    // Determine rank within order based on agent type
    $typeStmt = $db->prepare("SELECT agent_type FROM alfred_agents WHERE id = ?");
    $typeStmt->execute([$agentId]);
    $agentType = $typeStmt->fetchColumn() ?: 'tool';

    $orderRankMap = [
        'tool'       => 'initiate',
        'assistant'  => 'brother',
        'autonomous' => 'knight',
        'supervisor' => 'captain',
        'sentinel'   => 'warden',
    ];

    $db->prepare("
        INSERT INTO order_membership (order_id, member_type, member_id, rank_within, status, inducted_by)
        VALUES (?, 'agent', ?, ?, 'active', ?)
    ")->execute([$order['id'], $agentId, $orderRankMap[$agentType] ?? 'initiate', $inductedBy]);

    // Award order XP
    agentAwardXP($agentId, 'order_inducted', ['order' => $order['order_name']], $db);

    return ['success' => true, 'order_name' => $order['order_name']];
}

/**
 * Log agent duty.
 */
function logAgentDuty(int $agentId, array $data): int {
    $db = getSharedDB();
    $stmt = $db->prepare("
        INSERT INTO agent_duty_log (agent_id, duty_type, target_type, target_id, result, duration_ms, summary)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $agentId,
        $data['duty_type'] ?? 'mission',
        $data['target_type'] ?? null,
        $data['target_id'] ?? null,
        $data['result'] ?? 'pending',
        $data['duration_ms'] ?? null,
        $data['summary'] ?? null,
    ]);
    return (int)$db->lastInsertId();
}

/**
 * Get agent duty log.
 */
function getAgentDutyLog(int $agentId, int $limit = 50): array {
    $db = getSharedDB();
    $stmt = $db->prepare("SELECT * FROM agent_duty_log WHERE agent_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$agentId, $limit]);
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
}

/**
 * Get aggregate stats for the agent corps.
 */
function getAgentCorpsStats(): array {
    $db = getSharedDB();

    $stats = [];
    $stats['total_agents'] = (int)$db->query("SELECT COUNT(*) FROM alfred_agents")->fetchColumn();
    $stats['active_agents'] = (int)$db->query("SELECT COUNT(*) FROM alfred_agents WHERE status = 'active'")->fetchColumn();
    $stats['total_xp'] = (int)$db->query("SELECT COALESCE(SUM(xp), 0) FROM agent_ranks WHERE is_active = 1")->fetchColumn();
    $stats['total_duties'] = (int)$db->query("SELECT COUNT(*) FROM agent_duty_log")->fetchColumn();
    $stats['successful_duties'] = (int)$db->query("SELECT COUNT(*) FROM agent_duty_log WHERE result = 'success'")->fetchColumn();

    // By type
    $typeStmt = $db->query("SELECT agent_type, COUNT(*) AS cnt FROM alfred_agents GROUP BY agent_type");
    $stats['by_type'] = $typeStmt->fetchAll(\PDO::FETCH_KEY_PAIR);

    // By order
    $orderStmt = $db->query("
        SELECT mo.order_short, COUNT(om.id) AS cnt
        FROM military_orders mo
        LEFT JOIN order_membership om ON om.order_id = mo.id AND om.member_type = 'agent' AND om.status = 'active'
        GROUP BY mo.id
    ");
    $stats['by_order'] = $orderStmt->fetchAll(\PDO::FETCH_KEY_PAIR);

    // Rank distribution
    $rankStmt = $db->query("
        SELECT mr.rank_name, COUNT(ar.id) AS cnt
        FROM military_ranks mr
        LEFT JOIN agent_ranks ar ON ar.rank_code = mr.rank_code AND ar.is_active = 1
        GROUP BY mr.rank_code ORDER BY mr.rank_tier
    ");
    $stats['by_rank'] = $rankStmt->fetchAll(\PDO::FETCH_KEY_PAIR);

    return $stats;
}

/**
 * Get all members of a sovereign order (both human and agent).
 */
function getOrderMembers(string $orderCode, int $limit = 100): array {
    $db = getSharedDB();
    $stmt = $db->prepare("
        SELECT om.*, mo.order_short, mo.order_name
        FROM order_membership om
        JOIN military_orders mo ON mo.id = om.order_id
        WHERE mo.order_code = ? AND om.status = 'active'
        ORDER BY om.inducted_at ASC
        LIMIT ?
    ");
    $stmt->execute([$orderCode, $limit]);
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
}

/**
 * Get order details with tenets.
 */
function getOrderDetails(string $orderCode): ?array {
    $db = getSharedDB();
    $stmt = $db->prepare("SELECT * FROM military_orders WHERE order_code = ?");
    $stmt->execute([$orderCode]);
    $order = $stmt->fetch(\PDO::FETCH_ASSOC);
    if (!$order) return null;

    $tenets = $db->prepare("SELECT * FROM order_tenets WHERE order_id = ? ORDER BY tenet_num");
    $tenets->execute([$order['id']]);
    $order['tenets'] = $tenets->fetchAll(\PDO::FETCH_ASSOC);

    $memberCount = $db->prepare("SELECT COUNT(*) FROM order_membership WHERE order_id = ? AND status = 'active'");
    $memberCount->execute([$order['id']]);
    $order['member_count'] = (int)$memberCount->fetchColumn();

    return $order;
}

/**
 * Get degree manual for an order.
 */
function getOrderDegrees(int $orderId): array {
    $db = getSharedDB();
    $stmt = $db->prepare("
        SELECT od.*, ot.title AS tenet_title, ot.description AS tenet_desc, ot.scripture AS tenet_scripture
        FROM order_degrees od
        LEFT JOIN order_tenets ot ON ot.order_id = od.order_id AND ot.tenet_num = od.tenet_num
        WHERE od.order_id = ? AND od.is_active = 1
        ORDER BY od.degree_num
    ");
    $stmt->execute([$orderId]);
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
}

/**
 * Advance a member to the next degree (if eligible).
 */
function advanceDegree(string $memberType, int $memberId, int $orderId, int $certifiedBy): array {
    $db = getSharedDB();

    // Get membership
    $mem = $db->prepare("SELECT * FROM order_membership WHERE order_id = ? AND member_type = ? AND member_id = ? AND status = 'active'");
    $mem->execute([$orderId, $memberType, $memberId]);
    $membership = $mem->fetch(\PDO::FETCH_ASSOC);
    if (!$membership) return ['success' => false, 'error' => 'Not a member of this order'];

    $currentDegree = (int)($membership['current_degree'] ?? 0);
    $nextDegree = $currentDegree + 1;
    if ($nextDegree > 7) return ['success' => false, 'error' => 'Already at maximum degree (7)'];

    // Get next degree requirements
    $degStmt = $db->prepare("SELECT * FROM order_degrees WHERE order_id = ? AND degree_num = ?");
    $degStmt->execute([$orderId, $nextDegree]);
    $degree = $degStmt->fetch(\PDO::FETCH_ASSOC);
    if (!$degree) return ['success' => false, 'error' => 'Degree definition not found'];

    // Check XP (for agents, check agent_ranks; for humans, check xp_ledger)
    if ($memberType === 'agent') {
        $xpStmt = $db->prepare("SELECT xp FROM agent_ranks WHERE agent_id = ? AND is_active = 1 ORDER BY xp DESC LIMIT 1");
        $xpStmt->execute([$memberId]);
        $currentXP = (int)$xpStmt->fetchColumn();
    } else {
        $xpStmt = $db->prepare("SELECT COALESCE(SUM(xp_amount), 0) FROM xp_ledger WHERE client_id = ?");
        $xpStmt->execute([$memberId]);
        $currentXP = (int)$xpStmt->fetchColumn();
    }

    if ($currentXP < (int)$degree['min_xp']) {
        return ['success' => false, 'error' => "Insufficient XP: {$currentXP} / {$degree['min_xp']} required"];
    }

    // Check time-in-grade
    if ($currentDegree > 0 && (int)$degree['min_days'] > 0) {
        $lastComp = $db->prepare("SELECT completed_at FROM order_degree_completions WHERE order_id = ? AND member_type = ? AND member_id = ? AND degree_num = ? LIMIT 1");
        $lastComp->execute([$orderId, $memberType, $memberId, $currentDegree]);
        $lastDate = $lastComp->fetchColumn();
        if ($lastDate) {
            $daysSince = (int)((time() - strtotime($lastDate)) / 86400);
            if ($daysSince < (int)$degree['min_days']) {
                return ['success' => false, 'error' => "Time-in-grade: {$daysSince} / {$degree['min_days']} days required"];
            }
        }
    }

    // Advance
    $db->beginTransaction();
    try {
        $db->prepare("UPDATE order_membership SET current_degree = ?, rank_within = ? WHERE id = ?")->execute([$nextDegree, $degree['rank_conferred'], $membership['id']]);
        $db->prepare("INSERT INTO order_degree_completions (order_id, member_type, member_id, degree_num, certified_by) VALUES (?, ?, ?, ?, ?)")->execute([$orderId, $memberType, $memberId, $nextDegree, $certifiedBy]);

        // Award XP for degree completion (agents only)
        if ($memberType === 'agent') {
            agentAwardXP($memberId, 'order_inducted', ['degree' => $nextDegree, 'order_id' => $orderId], $db);
        }

        $db->commit();
        return ['success' => true, 'new_degree' => $nextDegree, 'degree_name' => $degree['degree_name'], 'rank_conferred' => $degree['rank_conferred']];
    } catch (\Exception $e) {
        $db->rollBack();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
