<?php
/**
 * Alfred Team Collaboration API
 * Agent 7 — Sprint 3
 *
 * Supplements /api/enterprise.php with team workspace features:
 *   overview, share-agent, unshare-agent, shared-agents,
 *   share-conversation, shared-conversations, activity,
 *   invite-code, join
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// ──────────────────────────────────────────────
// CORS pre-flight
// ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: ' . SITE_URL);
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    http_response_code(204);
    exit;
}

// ──────────────────────────────────────────────
// Auth check
// ──────────────────────────────────────────────
$userId = $_SESSION['client_id'] ?? null;
if (!$userId) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

// ──────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────

/**
 * Get user's org membership (first org)
 */
function getTeamUserOrg($uid) {
    $db = getDB();
    $stmt = $db->prepare(
        "SELECT om.org_id, om.role, o.name AS org_name, o.slug, o.plan, o.owner_id,
                o.logo_url, o.domain, o.max_users, o.max_agents, o.created_at AS org_created
         FROM alfred_org_members om
         JOIN alfred_organizations o ON o.id = om.org_id
         WHERE om.user_id = ?
         ORDER BY om.id ASC LIMIT 1"
    );
    $stmt->execute([$uid]);
    return $stmt->fetch();
}

/**
 * Check if user has admin-level permission (owner/admin)
 */
function isAdminRole($role) {
    return in_array($role, ['owner', 'admin'], true);
}

/**
 * Check if user has management permission
 */
function isManagerRole($role) {
    return in_array($role, ['owner', 'admin', 'manager'], true);
}

/**
 * Log team activity
 */
function logActivity($orgId, $uid, $action, $details = null) {
    try {
        $db = getDB();
        $stmt = $db->prepare(
            "INSERT INTO alfred_team_activity (org_id, user_id, action, details, created_at)
             VALUES (?, ?, ?, ?, NOW())"
        );
        $stmt->execute([
            $orgId,
            $uid,
            $action,
            $details !== null ? json_encode($details) : null
        ]);
    } catch (PDOException $e) {
        error_log("Team activity log error: " . $e->getMessage());
    }
}

/**
 * Read JSON body
 */
function teamJsonBody() {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?: [];
}

// ──────────────────────────────────────────────
// Routing
// ──────────────────────────────────────────────
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {

    switch ($action) {

        // ──────────── OVERVIEW ────────────
        case 'overview':
            $membership = getTeamUserOrg($userId);
            if (!$membership) {
                jsonResponse(['error' => 'No organization found', 'has_org' => false], 404);
            }
            $orgId = $membership['org_id'];
            $db = getDB();

            // Member count
            $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM alfred_org_members WHERE org_id = ?");
            $stmt->execute([$orgId]);
            $memberCount = (int)$stmt->fetch()['cnt'];

            // Team count
            $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM alfred_org_teams WHERE org_id = ?");
            $stmt->execute([$orgId]);
            $teamCount = (int)$stmt->fetch()['cnt'];

            // Shared agent count
            $sharedAgentCount = 0;
            try {
                $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM alfred_shared_agents WHERE org_id = ?");
                $stmt->execute([$orgId]);
                $sharedAgentCount = (int)$stmt->fetch()['cnt'];
            } catch (PDOException $e) { /* table may not exist yet */ }

            // Shared conversation count
            $sharedConvCount = 0;
            try {
                $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM alfred_shared_conversations WHERE org_id = ?");
                $stmt->execute([$orgId]);
                $sharedConvCount = (int)$stmt->fetch()['cnt'];
            } catch (PDOException $e) { /* table may not exist yet */ }

            // Recent activity (last 20)
            $recentActivity = [];
            try {
                $stmt = $db->prepare(
                    "SELECT ta.*, tc.firstname, tc.lastname, tc.email
                     FROM alfred_team_activity ta
                     LEFT JOIN clients tc ON tc.id = ta.user_id
                     WHERE ta.org_id = ?
                     ORDER BY ta.created_at DESC
                     LIMIT 20"
                );
                $stmt->execute([$orgId]);
                $recentActivity = $stmt->fetchAll();
                foreach ($recentActivity as &$a) {
                    if (isset($a['details'])) {
                        $decoded = json_decode($a['details'], true);
                        $a['details'] = $decoded !== null ? $decoded : $a['details'];
                    }
                }
                unset($a);
            } catch (PDOException $e) { /* table may not exist yet */ }

            // Online members (active in last 10 min via last_login)
            $onlineMembers = [];
            try {
                $stmt = $db->prepare(
                    "SELECT om.user_id, tc.firstname, tc.lastname
                     FROM alfred_org_members om
                     LEFT JOIN clients tc ON tc.id = om.user_id
                     WHERE om.org_id = ?
                     ORDER BY om.joined_at ASC"
                );
                $stmt->execute([$orgId]);
                $onlineMembers = $stmt->fetchAll();
            } catch (PDOException $e) { /* ignore */ }

            jsonResponse([
                'success' => true,
                'has_org' => true,
                'org' => [
                    'id'        => (int)$orgId,
                    'name'      => $membership['org_name'],
                    'slug'      => $membership['slug'],
                    'plan'      => $membership['plan'],
                    'logo_url'  => $membership['logo_url'] ?? null,
                    'role'      => $membership['role'],
                    'created'   => $membership['org_created'] ?? null,
                ],
                'stats' => [
                    'members'              => $memberCount,
                    'teams'                => $teamCount,
                    'shared_agents'        => $sharedAgentCount,
                    'shared_conversations' => $sharedConvCount,
                ],
                'recent_activity' => $recentActivity,
                'members'         => $onlineMembers,
            ]);
            break;

        // ──────────── SHARE AGENT ────────────
        case 'share-agent':
            if ($method !== 'POST') jsonResponse(['error' => 'Method not allowed'], 405);
            $membership = getTeamUserOrg($userId);
            if (!$membership) jsonResponse(['error' => 'No organization found'], 404);

            $body = teamJsonBody();
            $agentId    = (int)($body['agent_id'] ?? 0);
            $permissions = in_array($body['permissions'] ?? '', ['view','execute','manage']) ? $body['permissions'] : 'execute';
            if (!$agentId) jsonResponse(['error' => 'agent_id is required'], 400);

            $orgId = $membership['org_id'];
            $db = getDB();

            // Insert shared agent record
            try {
                $stmt = $db->prepare(
                    "INSERT INTO alfred_shared_agents (org_id, agent_id, shared_by, permissions, created_at)
                     VALUES (?, ?, ?, ?, NOW())"
                );
                $stmt->execute([$orgId, $agentId, $userId, $permissions]);
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    jsonResponse(['error' => 'This agent is already shared with the organization'], 409);
                }
                throw $e;
            }

            logActivity($orgId, $userId, 'agent.shared', ['agent_id' => $agentId, 'permissions' => $permissions]);
            jsonResponse(['success' => true, 'message' => 'Agent shared successfully'], 201);
            break;

        // ──────────── UNSHARE AGENT ────────────
        case 'unshare-agent':
            if ($method !== 'POST') jsonResponse(['error' => 'Method not allowed'], 405);
            $membership = getTeamUserOrg($userId);
            if (!$membership) jsonResponse(['error' => 'No organization found'], 404);

            $body = teamJsonBody();
            $agentId = (int)($body['agent_id'] ?? 0);
            if (!$agentId) jsonResponse(['error' => 'agent_id is required'], 400);

            $orgId = $membership['org_id'];
            $db = getDB();

            // Only the sharer or admin+ can unshare
            $stmt = $db->prepare(
                "SELECT shared_by FROM alfred_shared_agents WHERE org_id = ? AND agent_id = ?"
            );
            $stmt->execute([$orgId, $agentId]);
            $shared = $stmt->fetch();
            if (!$shared) jsonResponse(['error' => 'Shared agent not found'], 404);

            if ((int)$shared['shared_by'] !== (int)$userId && !isAdminRole($membership['role'])) {
                jsonResponse(['error' => 'Only the sharer or an admin can unshare'], 403);
            }

            $stmt = $db->prepare("DELETE FROM alfred_shared_agents WHERE org_id = ? AND agent_id = ?");
            $stmt->execute([$orgId, $agentId]);

            logActivity($orgId, $userId, 'agent.unshared', ['agent_id' => $agentId]);
            jsonResponse(['success' => true]);
            break;

        // ──────────── SHARED AGENTS ────────────
        case 'shared-agents':
            $membership = getTeamUserOrg($userId);
            if (!$membership) jsonResponse(['error' => 'No organization found'], 404);
            $orgId = $membership['org_id'];
            $db = getDB();

            $stmt = $db->prepare(
                "SELECT sa.*, tc.firstname AS sharer_firstname, tc.lastname AS sharer_lastname
                 FROM alfred_shared_agents sa
                 LEFT JOIN clients tc ON tc.id = sa.shared_by
                 WHERE sa.org_id = ?
                 ORDER BY sa.created_at DESC"
            );
            $stmt->execute([$orgId]);
            jsonResponse(['success' => true, 'agents' => $stmt->fetchAll()]);
            break;

        // ──────────── SHARE CONVERSATION ────────────
        case 'share-conversation':
            if ($method !== 'POST') jsonResponse(['error' => 'Method not allowed'], 405);
            $membership = getTeamUserOrg($userId);
            if (!$membership) jsonResponse(['error' => 'No organization found'], 404);

            $body = teamJsonBody();
            $convId = sanitize($body['conv_id'] ?? '', 50);
            $teamId = !empty($body['team_id']) ? (int)$body['team_id'] : null;
            if (!$convId) jsonResponse(['error' => 'conv_id is required'], 400);

            $orgId = $membership['org_id'];
            $db = getDB();

            // Check if already shared
            $stmt = $db->prepare(
                "SELECT id FROM alfred_shared_conversations WHERE org_id = ? AND conv_id = ?"
            );
            $stmt->execute([$orgId, $convId]);
            if ($stmt->fetch()) {
                jsonResponse(['error' => 'Conversation already shared'], 409);
            }

            // If team_id provided, verify team belongs to org
            if ($teamId) {
                $stmt = $db->prepare("SELECT id FROM alfred_org_teams WHERE id = ? AND org_id = ?");
                $stmt->execute([$teamId, $orgId]);
                if (!$stmt->fetch()) jsonResponse(['error' => 'Team not found in this organization'], 404);
            }

            $stmt = $db->prepare(
                "INSERT INTO alfred_shared_conversations (org_id, conv_id, shared_by, team_id, created_at)
                 VALUES (?, ?, ?, ?, NOW())"
            );
            $stmt->execute([$orgId, $convId, $userId, $teamId]);

            logActivity($orgId, $userId, 'conversation.shared', ['conv_id' => $convId, 'team_id' => $teamId]);
            jsonResponse(['success' => true, 'message' => 'Conversation shared'], 201);
            break;

        // ──────────── SHARED CONVERSATIONS ────────────
        case 'shared-conversations':
            $membership = getTeamUserOrg($userId);
            if (!$membership) jsonResponse(['error' => 'No organization found'], 404);
            $orgId = $membership['org_id'];
            $db = getDB();

            $where  = ['sc.org_id = ?'];
            $params = [$orgId];

            // Filter by team
            if (!empty($_GET['team_id'])) {
                $where[]  = 'sc.team_id = ?';
                $params[] = (int)$_GET['team_id'];
            }
            // Filter by member
            if (!empty($_GET['user_id'])) {
                $where[]  = 'sc.shared_by = ?';
                $params[] = (int)$_GET['user_id'];
            }
            // Filter by date
            if (!empty($_GET['date_from'])) {
                $where[]  = 'sc.created_at >= ?';
                $params[] = sanitize($_GET['date_from'], 20);
            }
            if (!empty($_GET['date_to'])) {
                $where[]  = 'sc.created_at <= ?';
                $params[] = sanitize($_GET['date_to'], 20) . ' 23:59:59';
            }

            $whereSQL = implode(' AND ', $where);
            $stmt = $db->prepare(
                "SELECT sc.*, tc.firstname AS sharer_firstname, tc.lastname AS sharer_lastname,
                        t.name AS team_name
                 FROM alfred_shared_conversations sc
                 LEFT JOIN clients tc ON tc.id = sc.shared_by
                 LEFT JOIN alfred_org_teams t ON t.id = sc.team_id
                 WHERE $whereSQL
                 ORDER BY sc.created_at DESC
                 LIMIT 100"
            );
            $stmt->execute($params);
            jsonResponse(['success' => true, 'conversations' => $stmt->fetchAll()]);
            break;

        // ──────────── ACTIVITY FEED ────────────
        case 'activity':
            $membership = getTeamUserOrg($userId);
            if (!$membership) jsonResponse(['error' => 'No organization found'], 404);
            $orgId = $membership['org_id'];
            $db = getDB();

            $limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));
            $offset = max(0, (int)($_GET['offset'] ?? 0));

            $stmt = $db->prepare(
                "SELECT ta.*, tc.firstname, tc.lastname
                 FROM alfred_team_activity ta
                 LEFT JOIN clients tc ON tc.id = ta.user_id
                 WHERE ta.org_id = ?
                 ORDER BY ta.created_at DESC
                 LIMIT ? OFFSET ?"
            );
            dbExecute($stmt, [$orgId, $limit, $offset]);
            $activities = $stmt->fetchAll();

            foreach ($activities as &$a) {
                if (isset($a['details'])) {
                    $decoded = json_decode($a['details'], true);
                    $a['details'] = $decoded !== null ? $decoded : $a['details'];
                }
            }
            unset($a);

            jsonResponse(['success' => true, 'activity' => $activities]);
            break;

        // ──────────── INVITE CODE ────────────
        case 'invite-code':
            if ($method !== 'POST') jsonResponse(['error' => 'Method not allowed'], 405);
            $membership = getTeamUserOrg($userId);
            if (!$membership) jsonResponse(['error' => 'No organization found'], 404);
            if (!isAdminRole($membership['role'])) {
                jsonResponse(['error' => 'Only admins and owners can generate invite codes'], 403);
            }

            $body = teamJsonBody();
            $roleId   = (int)($body['role_id'] ?? 4);
            $maxUses  = min(100, max(1, (int)($body['max_uses'] ?? 10)));
            $code     = strtoupper(substr(bin2hex(random_bytes(5)), 0, 8)) . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));

            $orgId = $membership['org_id'];
            $db = getDB();

            $stmt = $db->prepare(
                "INSERT INTO alfred_invite_codes (org_id, code, created_by, role_id, max_uses, uses, expires_at, created_at)
                 VALUES (?, ?, ?, ?, ?, 0, DATE_ADD(NOW(), INTERVAL 7 DAY), NOW())"
            );
            $stmt->execute([$orgId, $code, $userId, $roleId, $maxUses]);

            logActivity($orgId, $userId, 'invite_code.created', ['code' => $code, 'max_uses' => $maxUses]);
            jsonResponse([
                'success'    => true,
                'code'       => $code,
                'max_uses'   => $maxUses,
                'expires_in' => '7 days',
            ], 201);
            break;

        // ──────────── JOIN WITH CODE ────────────
        case 'join':
            if ($method !== 'POST') jsonResponse(['error' => 'Method not allowed'], 405);
            $body = teamJsonBody();
            $code = strtoupper(trim($body['code'] ?? ''));
            if (!$code) jsonResponse(['error' => 'Invite code is required'], 400);

            $db = getDB();
            $stmt = $db->prepare(
                "SELECT * FROM alfred_invite_codes WHERE code = ? AND expires_at > NOW() AND uses < max_uses"
            );
            $stmt->execute([$code]);
            $invite = $stmt->fetch();
            if (!$invite) {
                jsonResponse(['error' => 'Invalid, expired, or fully-used invite code'], 404);
            }

            $orgId = (int)$invite['org_id'];

            // Check if already a member
            $stmt = $db->prepare("SELECT id FROM alfred_org_members WHERE org_id = ? AND user_id = ?");
            $stmt->execute([$orgId, $userId]);
            if ($stmt->fetch()) {
                jsonResponse(['error' => 'You are already a member of this organization'], 409);
            }

            // Map role_id to role name
            $roleMap = [1 => 'owner', 2 => 'admin', 3 => 'manager', 4 => 'member', 5 => 'viewer'];
            $role = $roleMap[(int)$invite['role_id']] ?? 'member';

            // Add member
            $stmt = $db->prepare(
                "INSERT INTO alfred_org_members (org_id, user_id, role, accepted_at, joined_at)
                 VALUES (?, ?, ?, NOW(), NOW())"
            );
            $stmt->execute([$orgId, $userId, $role]);

            // Increment uses
            $stmt = $db->prepare("UPDATE alfred_invite_codes SET uses = uses + 1 WHERE id = ?");
            $stmt->execute([$invite['id']]);

            // Get org name
            $stmt = $db->prepare("SELECT name FROM alfred_organizations WHERE id = ?");
            $stmt->execute([$orgId]);
            $org = $stmt->fetch();

            logActivity($orgId, $userId, 'member.joined_via_code', ['code' => $code, 'role' => $role]);
            jsonResponse([
                'success'  => true,
                'org_id'   => $orgId,
                'org_name' => $org['name'] ?? 'Organization',
                'role'     => $role,
            ]);
            break;

        // ──────────── MEMBERS WITH DETAILS ────────────
        case 'members-detail':
            $membership = getTeamUserOrg($userId);
            if (!$membership) jsonResponse(['error' => 'No organization found'], 404);
            $orgId = $membership['org_id'];
            $db = getDB();

            $stmt = $db->prepare(
                "SELECT om.user_id, om.role, om.joined_at,
                        tc.firstname, tc.lastname, tc.email, tc.lastlogin
                 FROM alfred_org_members om
                 LEFT JOIN clients tc ON tc.id = om.user_id
                 WHERE om.org_id = ?
                 ORDER BY FIELD(om.role, 'owner','admin','manager','member','viewer'), om.joined_at ASC"
            );
            $stmt->execute([$orgId]);
            $members = $stmt->fetchAll();

            jsonResponse(['success' => true, 'members' => $members, 'my_role' => $membership['role']]);
            break;

        // ──────────── DEFAULT ────────────
        default:
            jsonResponse(['error' => 'Unknown action: ' . $action, 'available_actions' => [
                'overview', 'share-agent', 'unshare-agent', 'shared-agents',
                'share-conversation', 'shared-conversations', 'activity',
                'invite-code', 'join', 'members-detail'
            ]], 400);
            break;
    }

} catch (PDOException $e) {
    error_log("Team API DB error: " . $e->getMessage());
    jsonResponse(['error' => 'Database error', 'message' => 'An internal error occurred'], 500);
} catch (Exception $e) {
    error_log("Team API error: " . $e->getMessage());
    jsonResponse(['error' => 'Internal server error'], 500);
}
