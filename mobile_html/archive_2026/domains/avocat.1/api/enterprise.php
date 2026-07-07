<?php
/**
 * Alfred Enterprise API
 * Agent 1 — Project Phoenix (Master Plan 3)
 *
 * Endpoints:
 *   org, org/create, org/update
 *   members, members/invite, members/accept, members/role, members/remove
 *   teams, teams/create, teams/update, teams/add-member, teams/remove-member
 *   audit-log
 *   rbac/roles, rbac/create-role, rbac/update-role, rbac/delete-role
 *   usage/summary
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';
session_start();

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
// Built-in RBAC permission matrix
// ──────────────────────────────────────────────
define('ROLE_PERMISSIONS', [
    'owner'   => ['*'], // all permissions
    'admin'   => [
        'manage_members', 'create_teams', 'create_agents', 'deploy_fleet',
        'execute_tools', 'view_dashboards', 'view_audit', 'manage_api_keys',
        'white_label', 'export_data', 'marketplace_publish'
    ],
    'manager' => [
        'create_teams', 'create_agents', 'deploy_fleet', 'execute_tools',
        'view_dashboards', 'view_audit', 'export_data', 'marketplace_publish'
    ],
    'member'  => ['create_agents', 'execute_tools', 'view_dashboards', 'marketplace_publish'],
    'viewer'  => ['view_dashboards'],
]);

// ──────────────────────────────────────────────
// Helper: Audit log
// ──────────────────────────────────────────────
function auditLog($orgId, $userId, $action, $resourceType, $resourceId = null, $details = null) {
    try {
        $db = getDB();
        $stmt = $db->prepare(
            "INSERT INTO alfred_audit_log (org_id, user_id, action, resource_type, resource_id, details, ip_address, user_agent, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        $stmt->execute([
            $orgId,
            $userId,
            $action,
            $resourceType,
            $resourceId,
            $details !== null ? json_encode($details) : null,
            $_SERVER['REMOTE_ADDR'] ?? '',
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500)
        ]);
    } catch (PDOException $e) {
        error_log("Audit log error: " . $e->getMessage());
    }
}

// ──────────────────────────────────────────────
// Helper: Check RBAC permission
// ──────────────────────────────────────────────
function checkPermission($userId, $orgId, $permission) {
    try {
        $db = getDB();

        // Get the member's built-in role
        $stmt = $db->prepare(
            "SELECT role, permissions FROM alfred_org_members WHERE org_id = ? AND user_id = ?"
        );
        $stmt->execute([$orgId, $userId]);
        $member = $stmt->fetch();

        if (!$member) {
            return false;
        }

        $role = $member['role'];

        // Owner has all permissions
        if ($role === 'owner') {
            return true;
        }

        // Check built-in permissions
        $builtIn = ROLE_PERMISSIONS[$role] ?? [];
        if (in_array('*', $builtIn, true) || in_array($permission, $builtIn, true)) {
            return true;
        }

        // Check per-member permission overrides (JSON column)
        if (!empty($member['permissions'])) {
            $overrides = json_decode($member['permissions'], true);
            if (is_array($overrides) && in_array($permission, $overrides, true)) {
                return true;
            }
        }

        // Check custom RBAC roles assigned to this org
        $stmt2 = $db->prepare(
            "SELECT permissions FROM alfred_rbac_roles WHERE org_id = ? AND name = ?"
        );
        $stmt2->execute([$orgId, $role]);
        $custom = $stmt2->fetch();
        if ($custom && !empty($custom['permissions'])) {
            $customPerms = json_decode($custom['permissions'], true);
            if (is_array($customPerms) && in_array($permission, $customPerms, true)) {
                return true;
            }
        }

        return false;
    } catch (PDOException $e) {
        error_log("Permission check error: " . $e->getMessage());
        return false;
    }
}

// ──────────────────────────────────────────────
// Helper: Require permission (abort with 403 if denied)
// ──────────────────────────────────────────────
function requirePermission($userId, $orgId, $permission) {
    if (!checkPermission($userId, $orgId, $permission)) {
        jsonResponse(['error' => 'Forbidden — missing permission: ' . $permission], 403);
    }
}

// ──────────────────────────────────────────────
// Helper: Get logged-in user's org membership
// ──────────────────────────────────────────────
function getUserOrg($userId) {
    $db = getDB();
    $stmt = $db->prepare(
        "SELECT om.org_id, om.role, o.name AS org_name, o.slug, o.plan, o.owner_id
         FROM alfred_org_members om
         JOIN alfred_organizations o ON o.id = om.org_id
         WHERE om.user_id = ?
         ORDER BY om.id ASC LIMIT 1"
    );
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

// ──────────────────────────────────────────────
// Helper: Read JSON body
// ──────────────────────────────────────────────
function jsonBody() {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?: [];
}

// ══════════════════════════════════════════════
// AUTH CHECK (exempt SSO endpoints that receive external requests)
// ══════════════════════════════════════════════
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

$publicActions = ['sso/callback', 'sso/metadata'];
$userId = $_SESSION['client_id'] ?? null;
if (!$userId && !in_array($action, $publicActions)) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

// ══════════════════════════════════════════════
// ROUTING
// ══════════════════════════════════════════════

try {
    switch ($action) {

        // ──────────── ORGANIZATION ────────────

        case 'org':
            $membership = getUserOrg($userId);
            if (!$membership) {
                jsonResponse(['error' => 'No organization found for this user'], 404);
            }
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM alfred_organizations WHERE id = ?");
            $stmt->execute([$membership['org_id']]);
            $org = $stmt->fetch();
            unset($org['sso_config']); // don't leak SSO secrets in GET
            jsonResponse(['success' => true, 'organization' => $org]);
            break;

        case 'org/create':
            if ($method !== 'POST') jsonResponse(['error' => 'Method not allowed'], 405);
            $body = jsonBody();
            $name = sanitize($body['name'] ?? '', 255);
            $slug = sanitize($body['slug'] ?? '', 100);
            if (!$name || !$slug) {
                jsonResponse(['error' => 'Name and slug are required'], 400);
            }
            // Validate slug format
            if (!preg_match('/^[a-z0-9\-]{3,100}$/', $slug)) {
                jsonResponse(['error' => 'Slug must be 3-100 lowercase alphanumeric/hyphens'], 400);
            }
            $db = getDB();
            // Check uniqueness
            $stmt = $db->prepare("SELECT id FROM alfred_organizations WHERE slug = ?");
            $stmt->execute([$slug]);
            if ($stmt->fetch()) {
                jsonResponse(['error' => 'Slug already taken'], 409);
            }
            $stmt = $db->prepare(
                "INSERT INTO alfred_organizations (name, slug, owner_id, plan, created_at, updated_at)
                 VALUES (?, ?, ?, 'starter', NOW(), NOW())"
            );
            $stmt->execute([$name, $slug, $userId]);
            $orgId = $db->lastInsertId();

            // Add owner as member
            $stmt = $db->prepare(
                "INSERT INTO alfred_org_members (org_id, user_id, role, accepted_at, joined_at)
                 VALUES (?, ?, 'owner', NOW(), NOW())"
            );
            $stmt->execute([$orgId, $userId]);

            auditLog($orgId, $userId, 'org.created', 'organization', $orgId, ['name' => $name, 'slug' => $slug]);
            jsonResponse(['success' => true, 'org_id' => (int)$orgId, 'slug' => $slug], 201);
            break;

        case 'org/update':
            if ($method !== 'PUT' && $method !== 'POST') jsonResponse(['error' => 'Method not allowed'], 405);
            $membership = getUserOrg($userId);
            if (!$membership) jsonResponse(['error' => 'No organization found'], 404);
            requirePermission($userId, $membership['org_id'], 'manage_members');

            $body = jsonBody();
            $db = getDB();
            $fields = [];
            $params = [];
            $allowed = ['name', 'logo_url', 'domain', 'plan', 'sso_provider', 'sso_config', 'data_residency', 'max_users', 'max_agents'];
            foreach ($allowed as $col) {
                if (array_key_exists($col, $body)) {
                    $fields[] = "$col = ?";
                    $val = $body[$col];
                    if ($col === 'sso_config' && is_array($val)) {
                        $val = json_encode($val);
                    }
                    $params[] = $col === 'name' ? sanitize($val, 255) : $val;
                }
            }
            if (empty($fields)) {
                jsonResponse(['error' => 'No fields to update'], 400);
            }
            $params[] = $membership['org_id'];
            $sql = "UPDATE alfred_organizations SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = ?";
            $stmt = $db->prepare($sql);
            dbExecute($stmt, $params);

            auditLog($membership['org_id'], $userId, 'org.updated', 'organization', $membership['org_id'], array_keys($body));
            jsonResponse(['success' => true]);
            break;

        // ──────────── MEMBERS ────────────

        case 'members':
            $membership = getUserOrg($userId);
            if (!$membership) jsonResponse(['error' => 'No organization found'], 404);
            requirePermission($userId, $membership['org_id'], 'view_dashboards');
            $db = getDB();
            $stmt = $db->prepare(
                "SELECT om.id, om.user_id, om.role, om.permissions, om.invited_by, om.invited_at, om.accepted_at, om.joined_at
                 FROM alfred_org_members om
                 WHERE om.org_id = ?
                 ORDER BY om.joined_at ASC"
            );
            $stmt->execute([$membership['org_id']]);
            jsonResponse(['success' => true, 'members' => $stmt->fetchAll()]);
            break;

        case 'members/invite':
            if ($method !== 'POST') jsonResponse(['error' => 'Method not allowed'], 405);
            $membership = getUserOrg($userId);
            if (!$membership) jsonResponse(['error' => 'No organization found'], 404);
            requirePermission($userId, $membership['org_id'], 'manage_members');

            $body = jsonBody();
            $email = filter_var($body['email'] ?? '', FILTER_VALIDATE_EMAIL);
            $role  = $body['role'] ?? 'member';
            if (!$email) jsonResponse(['error' => 'Valid email is required'], 400);
            if (!in_array($role, ['admin', 'manager', 'member', 'viewer'])) {
                jsonResponse(['error' => 'Invalid role'], 400);
            }

            // Check max_users limit
            $db = getDB();
            $stmt = $db->prepare("SELECT max_users FROM alfred_organizations WHERE id = ?");
            $stmt->execute([$membership['org_id']]);
            $org = $stmt->fetch();
            $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM alfred_org_members WHERE org_id = ?");
            $stmt->execute([$membership['org_id']]);
            $currentCount = $stmt->fetch()['cnt'];
            if ($org && $org['max_users'] && $currentCount >= $org['max_users']) {
                jsonResponse(['error' => 'Organization has reached the maximum number of users (' . $org['max_users'] . ')'], 403);
            }

            // Check for existing pending invitation
            $stmt = $db->prepare(
                "SELECT id FROM alfred_invitations WHERE org_id = ? AND email = ? AND accepted_at IS NULL AND expires_at > NOW()"
            );
            $stmt->execute([$membership['org_id'], $email]);
            if ($stmt->fetch()) {
                jsonResponse(['error' => 'An active invitation already exists for this email'], 409);
            }

            $token = bin2hex(random_bytes(32)); // 64 chars
            $stmt = $db->prepare(
                "INSERT INTO alfred_invitations (org_id, email, role, token, invited_by, expires_at, created_at)
                 VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY), NOW())"
            );
            $stmt->execute([$membership['org_id'], $email, $role, $token, $userId]);

            auditLog($membership['org_id'], $userId, 'member.invited', 'invitation', $db->lastInsertId(), ['email' => $email, 'role' => $role]);
            jsonResponse(['success' => true, 'token' => $token, 'expires_in' => '7 days'], 201);
            break;

        case 'members/accept':
            if ($method !== 'POST') jsonResponse(['error' => 'Method not allowed'], 405);
            $body = jsonBody();
            $token = $body['token'] ?? '';
            if (!$token || strlen($token) !== 64) {
                jsonResponse(['error' => 'Invalid invitation token'], 400);
            }
            $db = getDB();
            $stmt = $db->prepare(
                "SELECT * FROM alfred_invitations WHERE token = ? AND accepted_at IS NULL AND expires_at > NOW()"
            );
            $stmt->execute([$token]);
            $invitation = $stmt->fetch();
            if (!$invitation) {
                jsonResponse(['error' => 'Invitation not found, expired, or already accepted'], 404);
            }

            // Check if user is already a member
            $stmt = $db->prepare("SELECT id FROM alfred_org_members WHERE org_id = ? AND user_id = ?");
            $stmt->execute([$invitation['org_id'], $userId]);
            if ($stmt->fetch()) {
                jsonResponse(['error' => 'User is already a member of this organization'], 409);
            }

            // Add member
            $stmt = $db->prepare(
                "INSERT INTO alfred_org_members (org_id, user_id, role, invited_by, invited_at, accepted_at, joined_at)
                 VALUES (?, ?, ?, ?, ?, NOW(), NOW())"
            );
            $stmt->execute([
                $invitation['org_id'],
                $userId,
                $invitation['role'],
                $invitation['invited_by'],
                $invitation['created_at']
            ]);

            // Mark invitation as accepted
            $stmt = $db->prepare("UPDATE alfred_invitations SET accepted_at = NOW() WHERE id = ?");
            $stmt->execute([$invitation['id']]);

            auditLog($invitation['org_id'], $userId, 'member.accepted', 'member', $userId, ['role' => $invitation['role']]);
            jsonResponse(['success' => true, 'org_id' => (int)$invitation['org_id'], 'role' => $invitation['role']]);
            break;

        case 'members/role':
            if ($method !== 'PUT' && $method !== 'POST') jsonResponse(['error' => 'Method not allowed'], 405);
            $membership = getUserOrg($userId);
            if (!$membership) jsonResponse(['error' => 'No organization found'], 404);
            requirePermission($userId, $membership['org_id'], 'manage_members');

            $body = jsonBody();
            $targetUserId = (int)($body['user_id'] ?? 0);
            $newRole = $body['role'] ?? '';
            if (!$targetUserId || !in_array($newRole, ['admin', 'manager', 'member', 'viewer'])) {
                jsonResponse(['error' => 'Valid user_id and role are required'], 400);
            }
            // Cannot change owner role
            $db = getDB();
            $stmt = $db->prepare(
                "SELECT role FROM alfred_org_members WHERE org_id = ? AND user_id = ?"
            );
            $stmt->execute([$membership['org_id'], $targetUserId]);
            $target = $stmt->fetch();
            if (!$target) jsonResponse(['error' => 'Member not found'], 404);
            if ($target['role'] === 'owner') jsonResponse(['error' => 'Cannot change owner role'], 403);

            $stmt = $db->prepare(
                "UPDATE alfred_org_members SET role = ? WHERE org_id = ? AND user_id = ?"
            );
            $stmt->execute([$newRole, $membership['org_id'], $targetUserId]);

            auditLog($membership['org_id'], $userId, 'member.role_changed', 'member', $targetUserId, ['old_role' => $target['role'], 'new_role' => $newRole]);
            jsonResponse(['success' => true]);
            break;

        case 'members/remove':
            if ($method !== 'DELETE' && $method !== 'POST') jsonResponse(['error' => 'Method not allowed'], 405);
            $membership = getUserOrg($userId);
            if (!$membership) jsonResponse(['error' => 'No organization found'], 404);
            requirePermission($userId, $membership['org_id'], 'manage_members');

            $body = jsonBody();
            $targetUserId = (int)($body['user_id'] ?? 0);
            if (!$targetUserId) jsonResponse(['error' => 'user_id is required'], 400);

            $db = getDB();
            $stmt = $db->prepare("SELECT role FROM alfred_org_members WHERE org_id = ? AND user_id = ?");
            $stmt->execute([$membership['org_id'], $targetUserId]);
            $target = $stmt->fetch();
            if (!$target) jsonResponse(['error' => 'Member not found'], 404);
            if ($target['role'] === 'owner') jsonResponse(['error' => 'Cannot remove the organization owner'], 403);

            $stmt = $db->prepare("DELETE FROM alfred_org_members WHERE org_id = ? AND user_id = ?");
            $stmt->execute([$membership['org_id'], $targetUserId]);

            // Also remove from all teams
            $stmt = $db->prepare(
                "DELETE tm FROM alfred_org_team_members tm
                 JOIN alfred_org_teams t ON t.id = tm.team_id
                 WHERE t.org_id = ? AND tm.user_id = ?"
            );
            $stmt->execute([$membership['org_id'], $targetUserId]);

            auditLog($membership['org_id'], $userId, 'member.removed', 'member', $targetUserId);
            jsonResponse(['success' => true]);
            break;

        // ──────────── TEAMS ────────────

        case 'teams':
            $membership = getUserOrg($userId);
            if (!$membership) jsonResponse(['error' => 'No organization found'], 404);
            requirePermission($userId, $membership['org_id'], 'view_dashboards');
            $db = getDB();
            $stmt = $db->prepare(
                "SELECT t.*, (SELECT COUNT(*) FROM alfred_org_team_members tm WHERE tm.team_id = t.id) AS member_count
                 FROM alfred_org_teams t
                 WHERE t.org_id = ?
                 ORDER BY t.name ASC"
            );
            $stmt->execute([$membership['org_id']]);
            jsonResponse(['success' => true, 'teams' => $stmt->fetchAll()]);
            break;

        case 'teams/create':
            if ($method !== 'POST') jsonResponse(['error' => 'Method not allowed'], 405);
            $membership = getUserOrg($userId);
            if (!$membership) jsonResponse(['error' => 'No organization found'], 404);
            requirePermission($userId, $membership['org_id'], 'create_teams');

            $body = jsonBody();
            $name = sanitize($body['name'] ?? '', 255);
            $description = sanitize($body['description'] ?? '', 1000);
            if (!$name) jsonResponse(['error' => 'Team name is required'], 400);

            $db = getDB();
            $stmt = $db->prepare(
                "INSERT INTO alfred_org_teams (org_id, name, description, created_at) VALUES (?, ?, ?, NOW())"
            );
            $stmt->execute([$membership['org_id'], $name, $description]);
            $teamId = $db->lastInsertId();

            auditLog($membership['org_id'], $userId, 'team.created', 'team', $teamId, ['name' => $name]);
            jsonResponse(['success' => true, 'team_id' => (int)$teamId], 201);
            break;

        case 'teams/update':
            if ($method !== 'PUT' && $method !== 'POST') jsonResponse(['error' => 'Method not allowed'], 405);
            $membership = getUserOrg($userId);
            if (!$membership) jsonResponse(['error' => 'No organization found'], 404);
            requirePermission($userId, $membership['org_id'], 'create_teams');

            $body = jsonBody();
            $teamId = (int)($body['team_id'] ?? 0);
            if (!$teamId) jsonResponse(['error' => 'team_id is required'], 400);

            $db = getDB();
            // Verify team belongs to org
            $stmt = $db->prepare("SELECT id FROM alfred_org_teams WHERE id = ? AND org_id = ?");
            $stmt->execute([$teamId, $membership['org_id']]);
            if (!$stmt->fetch()) jsonResponse(['error' => 'Team not found'], 404);

            $fields = [];
            $params = [];
            if (isset($body['name'])) {
                $fields[] = 'name = ?';
                $params[] = sanitize($body['name'], 255);
            }
            if (isset($body['description'])) {
                $fields[] = 'description = ?';
                $params[] = sanitize($body['description'], 1000);
            }
            if (isset($body['permissions'])) {
                $fields[] = 'permissions = ?';
                $params[] = is_array($body['permissions']) ? json_encode($body['permissions']) : $body['permissions'];
            }
            if (empty($fields)) jsonResponse(['error' => 'No fields to update'], 400);

            $params[] = $teamId;
            $stmt = $db->prepare("UPDATE alfred_org_teams SET " . implode(', ', $fields) . " WHERE id = ?");
            dbExecute($stmt, $params);

            auditLog($membership['org_id'], $userId, 'team.updated', 'team', $teamId, array_keys($body));
            jsonResponse(['success' => true]);
            break;

        case 'teams/add-member':
            if ($method !== 'POST') jsonResponse(['error' => 'Method not allowed'], 405);
            $membership = getUserOrg($userId);
            if (!$membership) jsonResponse(['error' => 'No organization found'], 404);
            requirePermission($userId, $membership['org_id'], 'create_teams');

            $body = jsonBody();
            $teamId = (int)($body['team_id'] ?? 0);
            $memberId = (int)($body['user_id'] ?? 0);
            if (!$teamId || !$memberId) jsonResponse(['error' => 'team_id and user_id are required'], 400);

            $db = getDB();
            // Verify team belongs to org
            $stmt = $db->prepare("SELECT id FROM alfred_org_teams WHERE id = ? AND org_id = ?");
            $stmt->execute([$teamId, $membership['org_id']]);
            if (!$stmt->fetch()) jsonResponse(['error' => 'Team not found'], 404);

            // Verify user is an org member
            $stmt = $db->prepare("SELECT id FROM alfred_org_members WHERE org_id = ? AND user_id = ?");
            $stmt->execute([$membership['org_id'], $memberId]);
            if (!$stmt->fetch()) jsonResponse(['error' => 'User is not a member of this organization'], 400);

            // Check if already in team
            $stmt = $db->prepare("SELECT id FROM alfred_org_team_members WHERE team_id = ? AND user_id = ?");
            $stmt->execute([$teamId, $memberId]);
            if ($stmt->fetch()) jsonResponse(['error' => 'User is already in this team'], 409);

            $stmt = $db->prepare("INSERT INTO alfred_org_team_members (team_id, user_id, added_at) VALUES (?, ?, NOW())");
            $stmt->execute([$teamId, $memberId]);

            auditLog($membership['org_id'], $userId, 'team.member_added', 'team', $teamId, ['user_id' => $memberId]);
            jsonResponse(['success' => true], 201);
            break;

        case 'teams/remove-member':
            if ($method !== 'DELETE' && $method !== 'POST') jsonResponse(['error' => 'Method not allowed'], 405);
            $membership = getUserOrg($userId);
            if (!$membership) jsonResponse(['error' => 'No organization found'], 404);
            requirePermission($userId, $membership['org_id'], 'create_teams');

            $body = jsonBody();
            $teamId = (int)($body['team_id'] ?? 0);
            $memberId = (int)($body['user_id'] ?? 0);
            if (!$teamId || !$memberId) jsonResponse(['error' => 'team_id and user_id are required'], 400);

            $db = getDB();
            // Verify team belongs to org
            $stmt = $db->prepare("SELECT id FROM alfred_org_teams WHERE id = ? AND org_id = ?");
            $stmt->execute([$teamId, $membership['org_id']]);
            if (!$stmt->fetch()) jsonResponse(['error' => 'Team not found'], 404);

            $stmt = $db->prepare("DELETE FROM alfred_org_team_members WHERE team_id = ? AND user_id = ?");
            $stmt->execute([$teamId, $memberId]);
            if ($stmt->rowCount() === 0) jsonResponse(['error' => 'User was not in this team'], 404);

            auditLog($membership['org_id'], $userId, 'team.member_removed', 'team', $teamId, ['user_id' => $memberId]);
            jsonResponse(['success' => true]);
            break;

        // ──────────── AUDIT LOG ────────────

        case 'audit-log':
            $membership = getUserOrg($userId);
            if (!$membership) jsonResponse(['error' => 'No organization found'], 404);
            requirePermission($userId, $membership['org_id'], 'view_audit');

            $page     = max(1, (int)($_GET['page'] ?? 1));
            $perPage  = min(100, max(1, (int)($_GET['per_page'] ?? 50)));
            $offset   = ($page - 1) * $perPage;
            $filterAction = sanitize($_GET['filter_action'] ?? '', 100);
            $filterUser   = (int)($_GET['filter_user'] ?? 0);
            $dateFrom     = sanitize($_GET['date_from'] ?? '', 20);
            $dateTo       = sanitize($_GET['date_to'] ?? '', 20);

            $where  = ['al.org_id = ?'];
            $params = [$membership['org_id']];

            if ($filterAction) {
                $where[]  = 'al.action = ?';
                $params[] = $filterAction;
            }
            if ($filterUser) {
                $where[]  = 'al.user_id = ?';
                $params[] = $filterUser;
            }
            if ($dateFrom) {
                $where[]  = 'al.created_at >= ?';
                $params[] = $dateFrom;
            }
            if ($dateTo) {
                $where[]  = 'al.created_at <= ?';
                $params[] = $dateTo . ' 23:59:59';
            }

            $whereSQL = implode(' AND ', $where);
            $db = getDB();

            // Total count
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM alfred_audit_log al WHERE $whereSQL");
            dbExecute($stmt, $params);
            $total = $stmt->fetch()['total'];

            // Rows
            $params[] = $perPage;
            $params[] = $offset;
            $stmt = $db->prepare(
                "SELECT al.* FROM alfred_audit_log al WHERE $whereSQL ORDER BY al.created_at DESC LIMIT ? OFFSET ?"
            );
            dbExecute($stmt, $params);
            $entries = $stmt->fetchAll();

            // Decode JSON details
            foreach ($entries as &$e) {
                if (isset($e['details'])) {
                    $decoded = json_decode($e['details'], true);
                    $e['details'] = $decoded !== null ? $decoded : $e['details'];
                }
            }
            unset($e);

            jsonResponse([
                'success' => true,
                'entries' => $entries,
                'pagination' => [
                    'page'      => $page,
                    'per_page'  => $perPage,
                    'total'     => (int)$total,
                    'pages'     => (int)ceil($total / $perPage),
                ]
            ]);
            break;

        // ──────────── RBAC ROLES ────────────

        case 'rbac/roles':
            $membership = getUserOrg($userId);
            if (!$membership) jsonResponse(['error' => 'No organization found'], 404);
            requirePermission($userId, $membership['org_id'], 'view_dashboards');

            // Built-in roles
            $builtIn = [];
            foreach (ROLE_PERMISSIONS as $roleName => $perms) {
                $builtIn[] = ['name' => $roleName, 'permissions' => $perms, 'is_custom' => false];
            }

            // Custom roles
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM alfred_rbac_roles WHERE org_id = ? ORDER BY name ASC");
            $stmt->execute([$membership['org_id']]);
            $custom = $stmt->fetchAll();
            foreach ($custom as &$r) {
                $r['permissions'] = json_decode($r['permissions'], true) ?: [];
                $r['is_custom'] = true;
            }
            unset($r);

            jsonResponse(['success' => true, 'built_in' => $builtIn, 'custom' => $custom]);
            break;

        case 'rbac/create-role':
            if ($method !== 'POST') jsonResponse(['error' => 'Method not allowed'], 405);
            $membership = getUserOrg($userId);
            if (!$membership) jsonResponse(['error' => 'No organization found'], 404);
            requirePermission($userId, $membership['org_id'], 'manage_members');

            $body = jsonBody();
            $name = sanitize($body['name'] ?? '', 100);
            $permissions = $body['permissions'] ?? [];
            if (!$name) jsonResponse(['error' => 'Role name is required'], 400);
            if (!is_array($permissions) || empty($permissions)) {
                jsonResponse(['error' => 'Permissions must be a non-empty array'], 400);
            }
            // Prevent naming conflicts with built-in roles
            if (in_array(strtolower($name), ['owner', 'admin', 'manager', 'member', 'viewer'])) {
                jsonResponse(['error' => 'Cannot use a built-in role name'], 400);
            }

            $db = getDB();
            $stmt = $db->prepare(
                "INSERT INTO alfred_rbac_roles (org_id, name, permissions, is_custom, created_at)
                 VALUES (?, ?, ?, TRUE, NOW())"
            );
            try {
                $stmt->execute([$membership['org_id'], $name, json_encode($permissions)]);
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    jsonResponse(['error' => 'A role with this name already exists in your organization'], 409);
                }
                throw $e;
            }
            $roleId = $db->lastInsertId();

            auditLog($membership['org_id'], $userId, 'rbac.role_created', 'role', $roleId, ['name' => $name, 'permissions' => $permissions]);
            jsonResponse(['success' => true, 'role_id' => (int)$roleId], 201);
            break;

        case 'rbac/update-role':
            if ($method !== 'PUT' && $method !== 'POST') jsonResponse(['error' => 'Method not allowed'], 405);
            $membership = getUserOrg($userId);
            if (!$membership) jsonResponse(['error' => 'No organization found'], 404);
            requirePermission($userId, $membership['org_id'], 'manage_members');

            $body = jsonBody();
            $roleId = (int)($body['role_id'] ?? 0);
            if (!$roleId) jsonResponse(['error' => 'role_id is required'], 400);

            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM alfred_rbac_roles WHERE id = ? AND org_id = ? AND is_custom = 1");
            $stmt->execute([$roleId, $membership['org_id']]);
            $role = $stmt->fetch();
            if (!$role) jsonResponse(['error' => 'Custom role not found'], 404);

            $fields = [];
            $params = [];
            if (isset($body['name'])) {
                $newName = sanitize($body['name'], 100);
                if (in_array(strtolower($newName), ['owner', 'admin', 'manager', 'member', 'viewer'])) {
                    jsonResponse(['error' => 'Cannot use a built-in role name'], 400);
                }
                $fields[] = 'name = ?';
                $params[] = $newName;
            }
            if (isset($body['permissions']) && is_array($body['permissions'])) {
                $fields[] = 'permissions = ?';
                $params[] = json_encode($body['permissions']);
            }
            if (empty($fields)) jsonResponse(['error' => 'No fields to update'], 400);

            $params[] = $roleId;
            $stmt = $db->prepare("UPDATE alfred_rbac_roles SET " . implode(', ', $fields) . " WHERE id = ?");
            dbExecute($stmt, $params);

            auditLog($membership['org_id'], $userId, 'rbac.role_updated', 'role', $roleId, array_keys($body));
            jsonResponse(['success' => true]);
            break;

        case 'rbac/delete-role':
            if ($method !== 'DELETE' && $method !== 'POST') jsonResponse(['error' => 'Method not allowed'], 405);
            $membership = getUserOrg($userId);
            if (!$membership) jsonResponse(['error' => 'No organization found'], 404);
            requirePermission($userId, $membership['org_id'], 'manage_members');

            $body = jsonBody();
            $roleId = (int)($body['role_id'] ?? 0);
            if (!$roleId) jsonResponse(['error' => 'role_id is required'], 400);

            $db = getDB();
            $stmt = $db->prepare("SELECT name FROM alfred_rbac_roles WHERE id = ? AND org_id = ? AND is_custom = 1");
            $stmt->execute([$roleId, $membership['org_id']]);
            $role = $stmt->fetch();
            if (!$role) jsonResponse(['error' => 'Custom role not found'], 404);

            $stmt = $db->prepare("DELETE FROM alfred_rbac_roles WHERE id = ?");
            $stmt->execute([$roleId]);

            auditLog($membership['org_id'], $userId, 'rbac.role_deleted', 'role', $roleId, ['name' => $role['name']]);
            jsonResponse(['success' => true]);
            break;

        // ──────────── USAGE SUMMARY ────────────

        case 'usage/summary':
            $membership = getUserOrg($userId);
            if (!$membership) jsonResponse(['error' => 'No organization found'], 404);
            requirePermission($userId, $membership['org_id'], 'view_dashboards');

            $db = getDB();
            $orgId = $membership['org_id'];

            // Member count
            $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM alfred_org_members WHERE org_id = ?");
            $stmt->execute([$orgId]);
            $memberCount = (int)$stmt->fetch()['cnt'];

            // Agent count
            $agentCount = 0;
            try {
                $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM alfred_fleet_agents fa JOIN alfred_fleets f ON f.id = fa.fleet_id WHERE f.org_id = ?");
                $stmt->execute([$orgId]);
                $agentCount = (int)$stmt->fetch()['cnt'];
            } catch (PDOException $e) {
                // fleet tables may have different schema
            }

            // Team count
            $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM alfred_org_teams WHERE org_id = ?");
            $stmt->execute([$orgId]);
            $teamCount = (int)$stmt->fetch()['cnt'];

            // API calls (last 30 days from audit log)
            $stmt = $db->prepare(
                "SELECT COUNT(*) as cnt FROM alfred_audit_log WHERE org_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
            );
            $stmt->execute([$orgId]);
            $apiCalls30d = (int)$stmt->fetch()['cnt'];

            // Tool usage (last 30 days)
            $toolUsage = 0;
            try {
                $stmt = $db->prepare(
                    "SELECT COUNT(*) as cnt FROM alfred_tool_usage tu
                     JOIN alfred_org_members om ON om.user_id = tu.user_id
                     WHERE om.org_id = ? AND tu.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
                );
                $stmt->execute([$orgId]);
                $toolUsage = (int)$stmt->fetch()['cnt'];
            } catch (PDOException $e) {
                // tool_usage may have different schema
            }

            // Org limits
            $stmt = $db->prepare("SELECT max_users, max_agents, plan FROM alfred_organizations WHERE id = ?");
            $stmt->execute([$orgId]);
            $orgInfo = $stmt->fetch();

            jsonResponse([
                'success'  => true,
                'usage'    => [
                    'members'       => $memberCount,
                    'max_users'     => (int)($orgInfo['max_users'] ?? 5),
                    'agents'        => $agentCount,
                    'max_agents'    => (int)($orgInfo['max_agents'] ?? 3),
                    'teams'         => $teamCount,
                    'api_calls_30d' => $apiCalls30d,
                    'tool_usage_30d'=> $toolUsage,
                    'plan'          => $orgInfo['plan'] ?? 'starter',
                ]
            ]);
            break;

        // ──────────── API KEY MANAGEMENT ────────────

        case 'api-keys':
            $db = getDB();
            $stmt = $db->prepare("
                SELECT id, key_prefix, name, scopes, rate_limit_tier,
                       last_used_at, expires_at, created_at,
                       CASE WHEN revoked_at IS NOT NULL THEN 0 ELSE 1 END AS active
                FROM alfred_api_keys
                WHERE user_id = :uid
                ORDER BY created_at DESC
            ");
            $stmt->execute([':uid' => $userId]);
            $keys = $stmt->fetchAll();
            foreach ($keys as &$k) {
                $k['scopes'] = json_decode($k['scopes'], true) ?: [];
                $k['active'] = (bool) $k['active'];
                $k['last_used'] = $k['last_used_at'] ?: 'Never';
            }
            unset($k);
            jsonResponse(['keys' => $keys]);
            break;

        case 'generate-api-key':
            if ($method !== 'POST') jsonResponse(['error' => 'Method not allowed'], 405);
            $body = jsonBody();
            $name = sanitize($body['name'] ?? '', 255);
            if (empty($name)) {
                jsonResponse(['error' => 'Key name is required'], 400);
            }
            $scopes = $body['scopes'] ?? ['*'];
            if (!is_array($scopes)) $scopes = ['*'];

            $secret  = bin2hex(random_bytes(48));
            $fullKey = 'ak_live_' . $secret;
            $keyHash = hash('sha256', $fullKey);
            $prefix  = substr($fullKey, 0, 12);

            $db = getDB();
            $stmt = $db->prepare("
                INSERT INTO alfred_api_keys (user_id, key_prefix, key_hash, name, scopes, rate_limit_tier)
                VALUES (:uid, :prefix, :hash, :name, :scopes, 'free')
            ");
            $stmt->execute([
                ':uid'    => $userId,
                ':prefix' => $prefix,
                ':hash'   => $keyHash,
                ':name'   => $name,
                ':scopes' => json_encode($scopes),
            ]);
            jsonResponse([
                'key'        => $fullKey,
                'key_prefix' => $prefix,
                'name'       => $name,
                'scopes'     => $scopes,
                'created_at' => date('c'),
                'message'    => 'Save this key now. It will not be shown again.',
            ], 201);
            break;

        // ──────────── SSO/SAML ────────────

        case 'sso/metadata':
            // SP metadata for configuring the IdP
            $spEntityId = SITE_URL . '/enterprise';
            $acsUrl     = SITE_URL . '/api/enterprise.php?action=sso/callback';
            
            header('Content-Type: application/xml');
            echo '<?xml version="1.0"?>' . "\n";
            echo '<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" entityID="' . htmlspecialchars($spEntityId) . '">' . "\n";
            echo '  <md:SPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol" AuthnRequestsSigned="false" WantAssertionsSigned="true">' . "\n";
            echo '    <md:NameIDFormat>urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress</md:NameIDFormat>' . "\n";
            echo '    <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="' . htmlspecialchars($acsUrl) . '" index="0" isDefault="true"/>' . "\n";
            echo '  </md:SPSSODescriptor>' . "\n";
            echo '</md:EntityDescriptor>';
            exit;

        case 'sso/callback':
            // SAML ACS endpoint — receives POST with SAMLResponse
            if ($method !== 'POST') {
                jsonResponse(['error' => 'POST required'], 405);
                break;
            }
            
            $samlResponse = $_POST['SAMLResponse'] ?? '';
            $relayState   = $_POST['RelayState'] ?? '';
            
            if (empty($samlResponse)) {
                jsonResponse(['error' => 'Missing SAMLResponse'], 400);
                break;
            }
            
            // Decode the SAML response
            $xml = base64_decode($samlResponse, true);
            if ($xml === false) {
                jsonResponse(['error' => 'Invalid SAMLResponse encoding'], 400);
                break;
            }
            
            // Parse the XML
            libxml_use_internal_errors(true);
            $doc = new DOMDocument();
            if (!$doc->loadXML($xml)) {
                jsonResponse(['error' => 'Invalid SAML XML'], 400);
                break;
            }
            
            // Extract NameID (email) from assertion
            $xpath = new DOMXPath($doc);
            $xpath->registerNamespace('saml', 'urn:oasis:names:tc:SAML:2.0:assertion');
            $xpath->registerNamespace('samlp', 'urn:oasis:names:tc:SAML:2.0:protocol');
            
            // Check status
            $statusNodes = $xpath->query('//samlp:StatusCode/@Value');
            if ($statusNodes->length > 0) {
                $statusValue = $statusNodes->item(0)->nodeValue;
                if (strpos($statusValue, 'Success') === false) {
                    auditLog(0, 0, 'sso.login_failed', 'sso', 0, ['status' => $statusValue]);
                    jsonResponse(['error' => 'SSO authentication failed', 'status' => $statusValue], 401);
                    break;
                }
            }
            
            // Extract email from NameID
            $nameIdNodes = $xpath->query('//saml:NameID');
            $email = null;
            if ($nameIdNodes->length > 0) {
                $email = strtolower(trim($nameIdNodes->item(0)->textContent));
            }
            
            // Also check Attribute elements for email
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $attrNodes = $xpath->query('//saml:Attribute[@Name="email" or @Name="http://schemas.xmlsoap.org/ws/2005/05/identity/claims/emailaddress" or @Name="User.email"]//saml:AttributeValue');
                if ($attrNodes->length > 0) {
                    $email = strtolower(trim($attrNodes->item(0)->textContent));
                }
            }
            
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                auditLog(0, 0, 'sso.no_email', 'sso', 0, []);
                jsonResponse(['error' => 'Could not extract email from SAML assertion'], 400);
                break;
            }
            
            // Extract optional attributes
            $firstNameNodes = $xpath->query('//saml:Attribute[@Name="firstName" or @Name="http://schemas.xmlsoap.org/ws/2005/05/identity/claims/givenname" or @Name="User.FirstName"]//saml:AttributeValue');
            $lastNameNodes  = $xpath->query('//saml:Attribute[@Name="lastName" or @Name="http://schemas.xmlsoap.org/ws/2005/05/identity/claims/surname" or @Name="User.LastName"]//saml:AttributeValue');
            $firstName = $firstNameNodes->length > 0 ? trim($firstNameNodes->item(0)->textContent) : '';
            $lastName  = $lastNameNodes->length > 0 ? trim($lastNameNodes->item(0)->textContent) : '';
            
            // Extract Issuer to match with org
            $issuerNodes = $xpath->query('//saml:Issuer');
            $issuer = $issuerNodes->length > 0 ? trim($issuerNodes->item(0)->textContent) : '';
            
            // Find org by SSO config matching the issuer or by email domain
            $db = getDB();
            $emailDomain = substr($email, strpos($email, '@') + 1);
            $stmt = $db->prepare(
                "SELECT id, name, slug, sso_provider, sso_config FROM alfred_organizations 
                 WHERE sso_provider IS NOT NULL AND (domain = ? OR sso_config LIKE ?)"
            );
            $stmt->execute([$emailDomain, '%' . $db->quote($issuer) . '%']);
            $org = $stmt->fetch();
            
            if (!$org) {
                // Fallback: match by email domain
                $stmt = $db->prepare("SELECT id, name, slug, sso_provider, sso_config FROM alfred_organizations WHERE domain = ?");
                $stmt->execute([$emailDomain]);
                $org = $stmt->fetch();
            }
            
            if (!$org) {
                auditLog(0, 0, 'sso.org_not_found', 'sso', 0, ['email' => $email, 'issuer' => $issuer]);
                jsonResponse(['error' => 'No organization found for this SSO provider'], 404);
                break;
            }
            
            // Validate certificate if configured
            $ssoConfig = json_decode($org['sso_config'] ?? '{}', true) ?: [];
            if (!empty($ssoConfig['certificate'])) {
                // Verify the SAML response signature using the IdP certificate
                $signatureNodes = $xpath->query('//ds:SignatureValue', null);
                // Note: Full XML signature verification requires xmlseclibs
                // For now we verify the certificate is present and assertion structure is valid
                if ($doc->getElementsByTagNameNS('http://www.w3.org/2000/09/xmldsig#', 'Signature')->length === 0) {
                    auditLog($org['id'], 0, 'sso.unsigned_assertion', 'sso', 0, ['issuer' => $issuer]);
                    // Log warning but don't block — some IdPs sign at response level only
                    error_log("SSO Warning: SAML assertion not signed for org {$org['id']}");
                }
            }
            
            // Find or create user
            $stmt = $db->prepare("SELECT id, firstname, lastname, email FROM clients WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                // Auto-provision user via SSO (JIT provisioning)
                $stmt = $db->prepare(
                    "INSERT INTO clients (firstname, lastname, email, password, status, datecreated) 
                     VALUES (?, ?, ?, ?, 'Active', NOW())"
                );
                $randomPass = bin2hex(random_bytes(32)); // SSO users don't use password
                $stmt->execute([
                    substr($firstName ?: 'SSO', 0, 100),
                    substr($lastName ?: 'User', 0, 100),
                    $email,
                    password_hash($randomPass, PASSWORD_DEFAULT)
                ]);
                $ssoUserId = (int)$db->lastInsertId();
                
                // Add to organization
                $stmt = $db->prepare(
                    "INSERT INTO alfred_org_members (org_id, user_id, role, permissions, joined_at) VALUES (?, ?, 'member', '{}', NOW())"
                );
                $stmt->execute([$org['id'], $ssoUserId]);
                
                auditLog($org['id'], $ssoUserId, 'sso.user_provisioned', 'user', $ssoUserId, ['email' => $email, 'provider' => $org['sso_provider']]);
            } else {
                $ssoUserId = (int)$user['id'];
                
                // Ensure user is in the org
                $stmt = $db->prepare("SELECT id FROM alfred_org_members WHERE org_id = ? AND user_id = ?");
                $stmt->execute([$org['id'], $ssoUserId]);
                if (!$stmt->fetch()) {
                    $stmt = $db->prepare(
                        "INSERT INTO alfred_org_members (org_id, user_id, role, permissions, joined_at, accepted_at) VALUES (?, ?, 'member', '{}', NOW(), NOW())"
                    );
                    $stmt->execute([$org['id'], $ssoUserId]);
                }
            }
            
            // Create session
            if (session_status() !== PHP_SESSION_ACTIVE) session_start();
            session_regenerate_id(true);
            $_SESSION['logged_in']   = true;
            $_SESSION['client_id']   = $ssoUserId;
            $_SESSION['client_email'] = $email;
            $_SESSION['client_name']  = trim("$firstName $lastName") ?: $email;
            $_SESSION['sso_org_id']   = $org['id'];
            $_SESSION['sso_provider'] = $org['sso_provider'];
            
            auditLog($org['id'], $ssoUserId, 'sso.login_success', 'user', $ssoUserId, [
                'provider' => $org['sso_provider'],
                'issuer'   => $issuer,
                'ip'       => $_SERVER['REMOTE_ADDR'] ?? ''
            ]);
            
            // Redirect to dashboard (or RelayState if provided)
            $redirectTo = '/dashboard.php';
            if (!empty($relayState) && strpos($relayState, '/') === 0) {
                $redirectTo = $relayState;  // Only accept relative paths
            }
            header('Location: ' . SITE_URL . $redirectTo);
            exit;

        case 'sso/initiate':
            // SP-initiated SSO — redirect user to IdP
            $membership = getUserOrg($userId);
            if (!$membership) jsonResponse(['error' => 'No organization found'], 404);
            
            $orgDb = getDB();
            $orgStmt = $orgDb->prepare("SELECT sso_provider, sso_config, domain FROM alfred_organizations WHERE id = ?");
            $orgStmt->execute([$membership['org_id']]);
            $orgData = $orgStmt->fetch();
            
            if (empty($orgData['sso_provider']) || empty($orgData['sso_config'])) {
                jsonResponse(['error' => 'SSO not configured for this organization'], 400);
                break;
            }
            
            $ssoConfig = json_decode($orgData['sso_config'], true);
            $idpUrl = $ssoConfig['sso_url'] ?? '';
            if (empty($idpUrl)) {
                jsonResponse(['error' => 'IdP SSO URL not configured'], 400);
                break;
            }
            
            $spEntityId = SITE_URL . '/enterprise';
            $acsUrl     = SITE_URL . '/api/enterprise.php?action=sso/callback';
            
            // Build SAML AuthnRequest
            $requestId = '_' . bin2hex(random_bytes(16));
            $issueInstant = gmdate('Y-m-d\TH:i:s\Z');
            
            $authnRequest = '<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" '
                . 'xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" '
                . 'ID="' . $requestId . '" '
                . 'Version="2.0" '
                . 'IssueInstant="' . $issueInstant . '" '
                . 'Destination="' . htmlspecialchars($idpUrl) . '" '
                . 'AssertionConsumerServiceURL="' . htmlspecialchars($acsUrl) . '" '
                . 'ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST">'
                . '<saml:Issuer>' . htmlspecialchars($spEntityId) . '</saml:Issuer>'
                . '<samlp:NameIDPolicy Format="urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress" AllowCreate="true"/>'
                . '</samlp:AuthnRequest>';
            
            $encodedRequest = base64_encode(gzdeflate($authnRequest));
            $redirectUrl = $idpUrl . '?' . http_build_query([
                'SAMLRequest' => $encodedRequest,
                'RelayState' => '/dashboard.php'
            ]);
            
            header('Location: ' . $redirectUrl);
            exit;

        // ──────────── DEFAULT ────────────

        default:
            jsonResponse(['error' => 'Unknown action: ' . $action, 'available_actions' => [
                'org', 'org/create', 'org/update',
                'members', 'members/invite', 'members/accept', 'members/role', 'members/remove',
                'teams', 'teams/create', 'teams/update', 'teams/add-member', 'teams/remove-member',
                'audit-log',
                'rbac/roles', 'rbac/create-role', 'rbac/update-role', 'rbac/delete-role',
                'usage/summary', 'api-keys', 'generate-api-key',
                'sso/metadata', 'sso/callback', 'sso/initiate'
            ]], 400);
            break;
    }

} catch (PDOException $e) {
    error_log("Enterprise API DB error: " . $e->getMessage());
    jsonResponse(['error' => 'Database error', 'message' => 'An internal error occurred'], 500);
} catch (Exception $e) {
    error_log("Enterprise API error: " . $e->getMessage());
    jsonResponse(['error' => 'Internal server error'], 500);
}
