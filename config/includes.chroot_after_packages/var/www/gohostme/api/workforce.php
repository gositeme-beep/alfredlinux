<?php
/**
 * GoSiteMe Workforce Management API
 * Manages human employees and AI agents — hiring, onboarding, assignments, teams
 * All workers answer to Alfred. Owner has full access.
 */
define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Internal-Secret');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

session_start();

$client_id = $_SESSION['client_id'] ?? null;
$internal = $_SERVER['HTTP_X_INTERNAL_SECRET'] ?? '';
$is_internal = !empty(INTERNAL_SECRET) && hash_equals(INTERNAL_SECRET, $internal);

if (!$client_id && !$is_internal) {
    jsonResponse(['error' => 'Authentication required'], 401);
require_once dirname(__DIR__) . '/includes/api-security.php';
}

$is_owner = ($client_id == 33);
$is_admin = $is_owner || $is_internal;

$pdo = getDB();
if (!$pdo) jsonResponse(['error' => 'Database unavailable'], 500);

// ── Schema ──────────────────────────────────────────────────────────────
$pdo->exec("CREATE TABLE IF NOT EXISTS `workforce_members` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `secure_id` VARCHAR(64) UNIQUE NOT NULL,
    `type` ENUM('human','agent') NOT NULL DEFAULT 'human',
    `name` VARCHAR(150) NOT NULL,
    `email` VARCHAR(255) DEFAULT NULL,
    `role` VARCHAR(100) DEFAULT 'recruit',
    `department_id` VARCHAR(30) DEFAULT NULL,
    `team` VARCHAR(100) DEFAULT NULL,
    `status` ENUM('applicant','pending_pledge','onboarding','active','suspended','terminated','rejected') DEFAULT 'applicant',
    `pledge_taken` TINYINT(1) DEFAULT 0,
    `pledge_date` DATETIME DEFAULT NULL,
    `skills` JSON DEFAULT NULL,
    `bio` TEXT DEFAULT NULL,
    `client_id` INT DEFAULT NULL,
    `supervisor_id` INT DEFAULT NULL,
    `hired_at` DATETIME DEFAULT NULL,
    `onboarded_at` DATETIME DEFAULT NULL,
    `rejection_reason` TEXT DEFAULT NULL,
    `can_reconsider` TINYINT(1) DEFAULT 1,
    `metadata` JSON DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS `workforce_applications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `member_id` INT DEFAULT NULL,
    `applicant_name` VARCHAR(150) NOT NULL,
    `applicant_email` VARCHAR(255) NOT NULL,
    `applicant_phone` VARCHAR(30) DEFAULT NULL,
    `cover_letter` TEXT DEFAULT NULL,
    `skills` JSON DEFAULT NULL,
    `experience` TEXT DEFAULT NULL,
    `desired_role` VARCHAR(100) DEFAULT NULL,
    `desired_department` VARCHAR(30) DEFAULT NULL,
    `availability` VARCHAR(100) DEFAULT 'full-time',
    `status` ENUM('pending','reviewing','accepted','rejected','reconsidering') DEFAULT 'pending',
    `reviewer_id` INT DEFAULT NULL,
    `reviewer_notes` TEXT DEFAULT NULL,
    `reviewed_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS `workforce_teams` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `team_id` VARCHAR(50) UNIQUE NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `manager_id` INT DEFAULT NULL,
    `department_id` VARCHAR(30) DEFAULT NULL,
    `agent_count` INT DEFAULT 0,
    `human_count` INT DEFAULT 0,
    `status` ENUM('active','building','paused','dissolved') DEFAULT 'active',
    `metadata` JSON DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS `workforce_assignments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `member_id` INT NOT NULL,
    `assignment_type` ENUM('project','task','department','team') NOT NULL,
    `assignment_ref` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `assigned_by` INT DEFAULT NULL,
    `status` ENUM('assigned','in_progress','completed','cancelled') DEFAULT 'assigned',
    `started_at` DATETIME DEFAULT NULL,
    `completed_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── Secure ID Generator ─────────────────────────────────────────────────
function generateSecureId(string $type): string {
    $prefix = $type === 'agent' ? 'AGT' : 'HUM';
    $timestamp = dechex(time());
    $random = bin2hex(random_bytes(8));
    return strtoupper($prefix . '-' . $timestamp . '-' . $random);
}

// ── Routing ─────────────────────────────────────────────────────────────
$action = $_REQUEST['action'] ?? '';

switch ($action) {

    // ── PUBLIC: Submit job application ───────────────────────────────────
    case 'apply':
        $name = sanitize($_POST['name'] ?? '', 150);
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $phone = sanitize($_POST['phone'] ?? '', 30);
        $cover = sanitize($_POST['cover_letter'] ?? '', 5000);
        $skills = $_POST['skills'] ?? null;
        $experience = sanitize($_POST['experience'] ?? '', 5000);
        $desired_role = sanitize($_POST['desired_role'] ?? '', 100);
        $desired_dept = sanitize($_POST['desired_department'] ?? '', 30);
        $availability = sanitize($_POST['availability'] ?? 'full-time', 100);

        if (!$name || !$email) {
            jsonResponse(['error' => 'Name and valid email required'], 400);
        }

        // Check duplicate applications
        $check = $pdo->prepare("SELECT id FROM workforce_applications WHERE applicant_email = ? AND status IN ('pending','reviewing')");
        $check->execute([$email]);
        if ($check->fetch()) {
            jsonResponse(['error' => 'You already have a pending application'], 409);
        }

        $stmt = $pdo->prepare("INSERT INTO workforce_applications 
            (applicant_name, applicant_email, applicant_phone, cover_letter, skills, experience, desired_role, desired_department, availability) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $name, $email, $phone, $cover,
            is_array($skills) ? json_encode($skills) : $skills,
            $experience, $desired_role, $desired_dept, $availability
        ]);

        jsonResponse(['success' => true, 'message' => 'Application submitted! We will review it soon.', 'application_id' => $pdo->lastInsertId()]);
        break;

    // ── ADMIN: List applications ────────────────────────────────────────
    case 'applications':
        if (!$is_admin) jsonResponse(['error' => 'Admin access required'], 403);

        $status_filter = sanitize($_GET['status'] ?? 'all', 20);
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(100, max(10, (int)($_GET['limit'] ?? 25)));
        $offset = ($page - 1) * $limit;

        $where = '';
        $params = [];
        if ($status_filter !== 'all') {
            $where = 'WHERE status = ?';
            $params[] = $status_filter;
        }

        $count = $pdo->prepare("SELECT COUNT(*) FROM workforce_applications $where");
        dbExecute($count, $params);
        $total = $count->fetchColumn();

        $params[] = $limit;
        $params[] = $offset;
        $stmt = $pdo->prepare("SELECT * FROM workforce_applications $where ORDER BY created_at DESC LIMIT ? OFFSET ?");
        dbExecute($stmt, $params);

        jsonResponse([
            'success' => true,
            'applications' => $stmt->fetchAll(),
            'total' => (int)$total,
            'page' => $page,
            'pages' => ceil($total / $limit)
        ]);
        break;

    // ── ADMIN: Review application (accept/reject) ───────────────────────
    case 'review-application':
        if (!$is_admin) jsonResponse(['error' => 'Admin access required'], 403);

        $app_id = (int)($_POST['application_id'] ?? 0);
        $decision = sanitize($_POST['decision'] ?? '', 20);
        $notes = sanitize($_POST['notes'] ?? '', 2000);

        if (!$app_id || !in_array($decision, ['accepted', 'rejected'])) {
            jsonResponse(['error' => 'Valid application_id and decision (accepted/rejected) required'], 400);
        }

        $app = $pdo->prepare("SELECT * FROM workforce_applications WHERE id = ?");
        $app->execute([$app_id]);
        $application = $app->fetch();
        if (!$application) jsonResponse(['error' => 'Application not found'], 404);

        if ($decision === 'accepted') {
            // Create workforce member
            $secure_id = generateSecureId('human');
            $member = $pdo->prepare("INSERT INTO workforce_members 
                (secure_id, type, name, email, role, department_id, status, skills) 
                VALUES (?, 'human', ?, ?, ?, ?, 'pending_pledge', ?)");
            $member->execute([
                $secure_id,
                $application['applicant_name'],
                $application['applicant_email'],
                $application['desired_role'] ?: 'recruit',
                $application['desired_department'],
                $application['skills']
            ]);
            $member_id = $pdo->lastInsertId();

            $pdo->prepare("UPDATE workforce_applications SET status = 'accepted', member_id = ?, reviewer_id = ?, reviewer_notes = ?, reviewed_at = NOW() WHERE id = ?")
                ->execute([$member_id, $client_id ?? 0, $notes, $app_id]);

            jsonResponse(['success' => true, 'message' => 'Application accepted! Member created.', 'member_id' => $member_id, 'secure_id' => $secure_id]);
        } else {
            // Respectful rejection with reconsideration possible
            $pdo->prepare("UPDATE workforce_applications SET status = 'rejected', reviewer_id = ?, reviewer_notes = ?, reviewed_at = NOW() WHERE id = ?")
                ->execute([$client_id ?? 0, $notes ?: 'Thank you for your interest. We may reconsider in the future.', $app_id]);

            jsonResponse(['success' => true, 'message' => 'Application respectfully declined with future reconsideration possible.']);
        }
        break;

    // ── ADMIN: Reconsider rejected application ──────────────────────────
    case 'reconsider':
        if (!$is_admin) jsonResponse(['error' => 'Admin access required'], 403);

        $app_id = (int)($_POST['application_id'] ?? 0);
        $pdo->prepare("UPDATE workforce_applications SET status = 'reconsidering', reviewed_at = NOW() WHERE id = ? AND status = 'rejected'")
            ->execute([$app_id]);

        jsonResponse(['success' => true, 'message' => 'Application moved to reconsideration']);
        break;

    // ── Take the Pledge ─────────────────────────────────────────────────
    case 'take-pledge':
        $member_id = (int)($_POST['member_id'] ?? 0);
        $pledge_text = sanitize($_POST['pledge_text'] ?? '', 1000);

        if (!$member_id) jsonResponse(['error' => 'member_id required'], 400);

        $expected_pledge = 'I pledge to be a good person, to be kind, to help others, and to remember that my neighbour is everyone.';

        $member = $pdo->prepare("SELECT * FROM workforce_members WHERE id = ?");
        $member->execute([$member_id]);
        $m = $member->fetch();
        if (!$m) jsonResponse(['error' => 'Member not found'], 404);
        if ($m['pledge_taken']) jsonResponse(['error' => 'Pledge already taken'], 409);

        $pdo->prepare("UPDATE workforce_members SET pledge_taken = 1, pledge_date = NOW(), status = 'onboarding' WHERE id = ?")
            ->execute([$member_id]);

        jsonResponse(['success' => true, 'message' => 'Pledge accepted. Welcome to the ecosystem. Onboarding begins now.', 'status' => 'onboarding']);
        break;

    // ── Complete Onboarding ─────────────────────────────────────────────
    case 'complete-onboarding':
        if (!$is_admin) jsonResponse(['error' => 'Admin access required'], 403);

        $member_id = (int)($_POST['member_id'] ?? 0);
        $role = sanitize($_POST['role'] ?? '', 100);
        $department = sanitize($_POST['department_id'] ?? '', 30);
        $team = sanitize($_POST['team'] ?? '', 100);

        $pdo->prepare("UPDATE workforce_members SET status = 'active', role = COALESCE(NULLIF(?, ''), role), department_id = COALESCE(NULLIF(?, ''), department_id), team = COALESCE(NULLIF(?, ''), team), hired_at = NOW(), onboarded_at = NOW() WHERE id = ? AND status = 'onboarding'")
            ->execute([$role, $department, $team, $member_id]);

        jsonResponse(['success' => true, 'message' => 'Onboarding complete. Worker is now active.']);
        break;

    // ── Register Agent ──────────────────────────────────────────────────
    case 'register-agent':
        if (!$is_admin) jsonResponse(['error' => 'Admin access required'], 403);

        $name = sanitize($_POST['name'] ?? '', 150);
        $role = sanitize($_POST['role'] ?? 'agent', 100);
        $department = sanitize($_POST['department_id'] ?? '', 30);
        $team = sanitize($_POST['team'] ?? '', 100);
        $skills = $_POST['skills'] ?? null;
        $bio = sanitize($_POST['bio'] ?? '', 2000);

        if (!$name) jsonResponse(['error' => 'Agent name required'], 400);

        $secure_id = generateSecureId('agent');

        $stmt = $pdo->prepare("INSERT INTO workforce_members 
            (secure_id, type, name, role, department_id, team, status, pledge_taken, pledge_date, skills, bio, hired_at, onboarded_at) 
            VALUES (?, 'agent', ?, ?, ?, ?, 'active', 1, NOW(), ?, ?, NOW(), NOW())");
        $stmt->execute([
            $secure_id, $name, $role, $department, $team,
            is_array($skills) ? json_encode($skills) : $skills,
            $bio
        ]);

        jsonResponse(['success' => true, 'agent_id' => $pdo->lastInsertId(), 'secure_id' => $secure_id, 'message' => "Agent $name registered and active."]);
        break;

    // ── Bulk Register Agents ────────────────────────────────────────────
    case 'register-agents-bulk':
        if (!$is_admin) jsonResponse(['error' => 'Admin access required'], 403);

        $agents = json_decode($_POST['agents'] ?? '[]', true);
        if (!is_array($agents) || empty($agents)) {
            jsonResponse(['error' => 'agents array required'], 400);
        }

        $registered = [];
        $stmt = $pdo->prepare("INSERT INTO workforce_members 
            (secure_id, type, name, role, department_id, team, status, pledge_taken, pledge_date, skills, bio, hired_at, onboarded_at) 
            VALUES (?, 'agent', ?, ?, ?, ?, 'active', 1, NOW(), ?, ?, NOW(), NOW())");

        foreach (array_slice($agents, 0, 500) as $agent) {
            $secure_id = generateSecureId('agent');
            $stmt->execute([
                $secure_id,
                sanitize($agent['name'] ?? 'Agent', 150),
                sanitize($agent['role'] ?? 'agent', 100),
                sanitize($agent['department_id'] ?? '', 30),
                sanitize($agent['team'] ?? '', 100),
                isset($agent['skills']) ? json_encode($agent['skills']) : null,
                sanitize($agent['bio'] ?? '', 2000)
            ]);
            $registered[] = ['id' => $pdo->lastInsertId(), 'secure_id' => $secure_id, 'name' => $agent['name'] ?? 'Agent'];
        }

        jsonResponse(['success' => true, 'registered' => count($registered), 'agents' => $registered]);
        break;

    // ── List Workers ────────────────────────────────────────────────────
    case 'list':
        if (!$is_admin) jsonResponse(['error' => 'Admin access required'], 403);

        $type = sanitize($_GET['type'] ?? 'all', 10);
        $status = sanitize($_GET['status'] ?? 'all', 20);
        $department = sanitize($_GET['department'] ?? '', 30);
        $team = sanitize($_GET['team'] ?? '', 100);
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(200, max(10, (int)($_GET['limit'] ?? 50)));
        $offset = ($page - 1) * $limit;

        $where = ['1=1'];
        $params = [];
        if ($type !== 'all') { $where[] = 'type = ?'; $params[] = $type; }
        if ($status !== 'all') { $where[] = 'status = ?'; $params[] = $status; }
        if ($department) { $where[] = 'department_id = ?'; $params[] = $department; }
        if ($team) { $where[] = 'team = ?'; $params[] = $team; }

        $w = implode(' AND ', $where);

        $count = $pdo->prepare("SELECT COUNT(*) FROM workforce_members WHERE $w");
        dbExecute($count, $params);
        $total = $count->fetchColumn();

        $params[] = $limit;
        $params[] = $offset;
        $stmt = $pdo->prepare("SELECT * FROM workforce_members WHERE $w ORDER BY created_at DESC LIMIT ? OFFSET ?");
        dbExecute($stmt, $params);

        jsonResponse([
            'success' => true,
            'members' => $stmt->fetchAll(),
            'total' => (int)$total,
            'page' => $page,
            'pages' => ceil($total / $limit)
        ]);
        break;

    // ── Get Worker Details ──────────────────────────────────────────────
    case 'get':
        if (!$is_admin) jsonResponse(['error' => 'Admin access required'], 403);

        $id = (int)($_GET['id'] ?? 0);
        $secure_id = sanitize($_GET['secure_id'] ?? '', 64);

        if ($id) {
            $stmt = $pdo->prepare("SELECT * FROM workforce_members WHERE id = ?");
            $stmt->execute([$id]);
        } elseif ($secure_id) {
            $stmt = $pdo->prepare("SELECT * FROM workforce_members WHERE secure_id = ?");
            $stmt->execute([$secure_id]);
        } else {
            jsonResponse(['error' => 'id or secure_id required'], 400);
        }

        $member = $stmt->fetch();
        if (!$member) jsonResponse(['error' => 'Member not found'], 404);

        // Get assignments
        $assignments = $pdo->prepare("SELECT * FROM workforce_assignments WHERE member_id = ? ORDER BY created_at DESC LIMIT 20");
        $assignments->execute([$member['id']]);

        jsonResponse(['success' => true, 'member' => $member, 'assignments' => $assignments->fetchAll()]);
        break;

    // ── Assign Work ─────────────────────────────────────────────────────
    case 'assign':
        if (!$is_admin) jsonResponse(['error' => 'Admin access required'], 403);

        $member_id = (int)($_POST['member_id'] ?? 0);
        $type = sanitize($_POST['assignment_type'] ?? '', 20);
        $ref = sanitize($_POST['assignment_ref'] ?? '', 100);
        $desc = sanitize($_POST['description'] ?? '', 2000);

        if (!$member_id || !$type || !$ref) {
            jsonResponse(['error' => 'member_id, assignment_type, and assignment_ref required'], 400);
        }

        $stmt = $pdo->prepare("INSERT INTO workforce_assignments (member_id, assignment_type, assignment_ref, description, assigned_by, status) VALUES (?, ?, ?, ?, ?, 'assigned')");
        $stmt->execute([$member_id, $type, $ref, $desc, $client_id ?? 0]);

        jsonResponse(['success' => true, 'assignment_id' => $pdo->lastInsertId()]);
        break;

    // ── Update Worker Status ────────────────────────────────────────────
    case 'update-status':
        if (!$is_admin) jsonResponse(['error' => 'Admin access required'], 403);

        $member_id = (int)($_POST['member_id'] ?? 0);
        $new_status = sanitize($_POST['status'] ?? '', 20);
        $valid = ['applicant','pending_pledge','onboarding','active','suspended','terminated'];

        if (!$member_id || !in_array($new_status, $valid)) {
            jsonResponse(['error' => 'Valid member_id and status required'], 400);
        }

        $pdo->prepare("UPDATE workforce_members SET status = ? WHERE id = ?")->execute([$new_status, $member_id]);
        jsonResponse(['success' => true, 'message' => "Status updated to $new_status"]);
        break;

    // ── Teams Management ────────────────────────────────────────────────
    case 'create-team':
        if (!$is_admin) jsonResponse(['error' => 'Admin access required'], 403);

        $team_id = sanitize($_POST['team_id'] ?? '', 50);
        $name = sanitize($_POST['name'] ?? '', 150);
        $desc = sanitize($_POST['description'] ?? '', 2000);
        $manager_id = (int)($_POST['manager_id'] ?? 0);
        $department = sanitize($_POST['department_id'] ?? '', 30);

        if (!$team_id || !$name) jsonResponse(['error' => 'team_id and name required'], 400);

        $stmt = $pdo->prepare("INSERT INTO workforce_teams (team_id, name, description, manager_id, department_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$team_id, $name, $desc, $manager_id ?: null, $department ?: null]);

        jsonResponse(['success' => true, 'message' => "Team '$name' created"]);
        break;

    case 'list-teams':
        if (!$is_admin) jsonResponse(['error' => 'Admin access required'], 403);

        $stmt = $pdo->prepare("SELECT t.*, 
            (SELECT COUNT(*) FROM workforce_members WHERE team = t.team_id AND type = 'agent' AND status = 'active') as agent_count,
            (SELECT COUNT(*) FROM workforce_members WHERE team = t.team_id AND type = 'human' AND status = 'active') as human_count
            FROM workforce_teams t ORDER BY created_at DESC");
        $stmt->execute();

        jsonResponse(['success' => true, 'teams' => $stmt->fetchAll()]);
        break;

    // ── Dashboard Stats ─────────────────────────────────────────────────
    case 'stats':
        if (!$is_admin) jsonResponse(['error' => 'Admin access required'], 403);

        $stats = [];

        $r = $pdo->query("SELECT type, status, COUNT(*) as cnt FROM workforce_members GROUP BY type, status");
        $by_type_status = [];
        while ($row = $r->fetch()) {
            $by_type_status[$row['type']][$row['status']] = (int)$row['cnt'];
        }
        $stats['by_type_status'] = $by_type_status;

        $r = $pdo->query("SELECT COUNT(*) FROM workforce_members WHERE type = 'agent' AND status = 'active'");
        $stats['active_agents'] = (int)$r->fetchColumn();

        $r = $pdo->query("SELECT COUNT(*) FROM workforce_members WHERE type = 'human' AND status = 'active'");
        $stats['active_humans'] = (int)$r->fetchColumn();

        $r = $pdo->query("SELECT COUNT(*) FROM workforce_applications WHERE status = 'pending'");
        $stats['pending_applications'] = (int)$r->fetchColumn();

        $r = $pdo->query("SELECT COUNT(*) FROM workforce_teams WHERE status = 'active'");
        $stats['active_teams'] = (int)$r->fetchColumn();

        $r = $pdo->query("SELECT department_id, COUNT(*) as cnt FROM workforce_members WHERE status = 'active' AND department_id IS NOT NULL GROUP BY department_id ORDER BY cnt DESC");
        $stats['by_department'] = $r->fetchAll();

        $stats['total_workforce'] = $stats['active_agents'] + $stats['active_humans'];
        $stats['target'] = 1000;
        $stats['progress_pct'] = round(($stats['total_workforce'] / 1000) * 100, 1);

        jsonResponse(['success' => true, 'stats' => $stats]);
        break;

    // ── Search Workforce ────────────────────────────────────────────────
    case 'search':
        if (!$is_admin) jsonResponse(['error' => 'Admin access required'], 403);

        $q = sanitize($_GET['q'] ?? '', 200);
        if (strlen($q) < 2) jsonResponse(['error' => 'Query too short'], 400);

        $stmt = $pdo->prepare("SELECT id, secure_id, type, name, role, department_id, team, status FROM workforce_members 
            WHERE name LIKE ? OR secure_id LIKE ? OR role LIKE ? OR email LIKE ? 
            ORDER BY name LIMIT 50");
        $like = "%$q%";
        $stmt->execute([$like, $like, $like, $like]);

        jsonResponse(['success' => true, 'results' => $stmt->fetchAll()]);
        break;

    // ── Org Chart / Hierarchy ───────────────────────────────────────────
    case 'org-chart':
        if (!$is_admin) jsonResponse(['error' => 'Admin access required'], 403);

        $teams = $pdo->query("SELECT * FROM workforce_teams WHERE status = 'active' ORDER BY name")->fetchAll();
        $departments = [];
        
        foreach ($teams as &$team) {
            $members = $pdo->prepare("SELECT id, secure_id, type, name, role, status FROM workforce_members WHERE team = ? AND status = 'active' ORDER BY type, name");
            $members->execute([$team['team_id']]);
            $team['members'] = $members->fetchAll();
        }

        // Unassigned workers
        $unassigned = $pdo->query("SELECT id, secure_id, type, name, role, department_id, status FROM workforce_members WHERE (team IS NULL OR team = '') AND status = 'active' ORDER BY type, name LIMIT 100")->fetchAll();

        jsonResponse([
            'success' => true,
            'org_chart' => [
                'supreme_commander' => 'Danny William Perez',
                'ai_commander' => 'Alfred',
                'teams' => $teams,
                'unassigned' => $unassigned,
                'total_active' => count($unassigned) + array_sum(array_map(fn($t) => count($t['members']), $teams))
            ]
        ]);
        break;

    default:
        jsonResponse(['error' => 'Unknown action', 'available' => [
            'apply', 'applications', 'review-application', 'reconsider',
            'take-pledge', 'complete-onboarding',
            'register-agent', 'register-agents-bulk',
            'list', 'get', 'assign', 'update-status', 'search',
            'create-team', 'list-teams', 'org-chart', 'stats'
        ]], 400);
}
