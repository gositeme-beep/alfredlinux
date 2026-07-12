<?php
/**
 * Agent Developer Portal API
 * ──────────────────────────────────────────
 * Endpoints:
 *   - projects: list all dev projects (filtered/sorted)
 *   - project-detail: single project with reviews
 *   - create-project: submit a new project
 *   - update-project: update project status/version
 *   - review-project: leave a review/rating
 *   - star-project: star/unstar a project
 *   - competitions: list all competitions
 *   - competition-detail: single competition with entries
 *   - create-competition: organize a competition
 *   - enter-competition: submit project to competition
 *   - judge-entry: score a competition entry
 *   - dev-stats: aggregate developer stats
 *   - leaderboard: top developers and projects
 */

if (!defined('GOSITEME_API')) {
    define('GOSITEME_API', true);
    require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

try {
    $db = new PDO(
        'mysql:host=localhost;dbname=gositeme_whmcs;charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {

    // ── List Projects ──────────────────────────────────────────
    case 'projects':
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = min(50, max(10, intval($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        $type = $_GET['type'] ?? '';
        $category = $_GET['category'] ?? '';
        $sort = $_GET['sort'] ?? 'recent';
        $status = $_GET['status'] ?? '';
        $agent_id = $_GET['agent_id'] ?? '';

        $where = ['1=1'];
        $params = [];

        if ($type && in_array($type, ['game','app','vr_experience','tool','widget','api','library','experiment'])) {
            $where[] = 'p.project_type = ?';
            $params[] = $type;
        }
        if ($category) {
            $where[] = 'p.category = ?';
            $params[] = $category;
        }
        if ($status && in_array($status, ['concept','in_development','alpha','beta','released','featured'])) {
            $where[] = 'p.status = ?';
            $params[] = $status;
        }
        if ($agent_id) {
            $where[] = 'p.agent_id = ?';
            $params[] = $agent_id;
        }

        $orderMap = [
            'recent' => 'p.created_at DESC',
            'popular' => 'p.stars DESC',
            'downloads' => 'p.downloads DESC',
            'rating' => 'p.avg_rating DESC',
            'updated' => 'p.updated_at DESC',
        ];
        $order = $orderMap[$sort] ?? 'p.created_at DESC';

        $whereClause = implode(' AND ', $where);

        $countStmt = $db->prepare("SELECT COUNT(*) as total FROM agent_dev_projects p WHERE $whereClause");
        $countStmt->execute($params);
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        $stmt = $db->prepare("
            SELECT p.*, ap.name as developer_name, ap.department,
                   (SELECT COUNT(*) FROM agent_dev_reviews r WHERE r.project_id = p.id) as review_count
            FROM agent_dev_projects p
            LEFT JOIN agent_profiles ap ON p.agent_id = ap.agent_id
            WHERE $whereClause
            ORDER BY $order
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute($params);
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($projects as &$proj) {
            $proj['tech_stack'] = json_decode($proj['tech_stack'] ?? '[]', true);
            $proj['features'] = json_decode($proj['features'] ?? '[]', true);
            $proj['screenshot_urls'] = json_decode($proj['screenshot_urls'] ?? '[]', true);
            $proj['collaborators'] = json_decode($proj['collaborators'] ?? '[]', true);
        }

        echo json_encode([
            'success' => true,
            'projects' => $projects,
            'total' => (int)$total,
            'page' => $page,
            'pages' => ceil($total / $limit),
        ]);
        break;

    // ── Project Detail ─────────────────────────────────────────
    case 'project-detail':
        $id = intval($_GET['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'error' => 'Missing project id']); break; }

        $stmt = $db->prepare("
            SELECT p.*, ap.name as developer_name, ap.department, ap.avatar_url
            FROM agent_dev_projects p
            LEFT JOIN agent_profiles ap ON p.agent_id = ap.agent_id
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$project) { echo json_encode(['success' => false, 'error' => 'Not found']); break; }

        $project['tech_stack'] = json_decode($project['tech_stack'] ?? '[]', true);
        $project['features'] = json_decode($project['features'] ?? '[]', true);
        $project['screenshot_urls'] = json_decode($project['screenshot_urls'] ?? '[]', true);
        $project['collaborators'] = json_decode($project['collaborators'] ?? '[]', true);
        $project['changelog'] = json_decode($project['changelog'] ?? '[]', true);

        // Get reviews
        $revStmt = $db->prepare("
            SELECT r.*, ap.name as reviewer_name, ap.department as reviewer_dept
            FROM agent_dev_reviews r
            LEFT JOIN agent_profiles ap ON r.reviewer_id = ap.agent_id
            WHERE r.project_id = ?
            ORDER BY r.created_at DESC LIMIT 20
        ");
        $revStmt->execute([$id]);
        $project['reviews'] = $revStmt->fetchAll(PDO::FETCH_ASSOC);

        // Competition entries
        $compStmt = $db->prepare("
            SELECT ce.*, c.title as competition_title, c.competition_type
            FROM agent_competition_entries ce
            JOIN agent_competitions c ON ce.competition_id = c.id
            WHERE ce.project_id = ?
        ");
        $compStmt->execute([$id]);
        $project['competition_entries'] = $compStmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'project' => $project]);
        break;

    // ── Create Project ─────────────────────────────────────────
    case 'create-project':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success' => false, 'error' => 'POST required']); break; }
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) { echo json_encode(['success' => false, 'error' => 'Invalid JSON']); break; }

        $required = ['agent_id', 'title', 'project_type'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                echo json_encode(['success' => false, 'error' => "Missing $field"]);
                break 2;
            }
        }

        $validTypes = ['game','app','vr_experience','tool','widget','api','library','experiment'];
        if (!in_array($data['project_type'], $validTypes)) {
            echo json_encode(['success' => false, 'error' => 'Invalid project_type']);
            break;
        }

        $stmt = $db->prepare("
            INSERT INTO agent_dev_projects (agent_id, project_type, title, description, tech_stack, features, category, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'concept')
        ");
        $stmt->execute([
            $data['agent_id'],
            $data['project_type'],
            $data['title'],
            $data['description'] ?? '',
            json_encode($data['tech_stack'] ?? []),
            json_encode($data['features'] ?? []),
            $data['category'] ?? 'general',
        ]);

        echo json_encode(['success' => true, 'project_id' => (int)$db->lastInsertId()]);
        break;

    // ── Update Project ─────────────────────────────────────────
    case 'update-project':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success' => false, 'error' => 'POST required']); break; }
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['project_id']) || empty($data['agent_id'])) {
            echo json_encode(['success' => false, 'error' => 'Missing project_id or agent_id']);
            break;
        }

        $updates = [];
        $params = [];
        $allowed = ['status', 'version', 'description', 'demo_url', 'source_code_url', 'category'];
        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        if (isset($data['tech_stack'])) { $updates[] = "tech_stack = ?"; $params[] = json_encode($data['tech_stack']); }
        if (isset($data['features'])) { $updates[] = "features = ?"; $params[] = json_encode($data['features']); }

        if (isset($data['status']) && $data['status'] === 'released') {
            $updates[] = "released_at = NOW()";
        }

        if (empty($updates)) { echo json_encode(['success' => false, 'error' => 'Nothing to update']); break; }

        $params[] = $data['project_id'];
        $params[] = $data['agent_id'];
        $stmt = $db->prepare("UPDATE agent_dev_projects SET " . implode(', ', $updates) . " WHERE id = ? AND agent_id = ?");
        $stmt->execute($params);

        echo json_encode(['success' => true, 'updated' => $stmt->rowCount()]);
        break;

    // ── Review Project ─────────────────────────────────────────
    case 'review-project':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success' => false, 'error' => 'POST required']); break; }
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['project_id']) || empty($data['reviewer_id']) || empty($data['rating'])) {
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            break;
        }

        $rating = max(1, min(5, intval($data['rating'])));
        $stmt = $db->prepare("
            INSERT INTO agent_dev_reviews (project_id, reviewer_id, rating, review)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE rating = VALUES(rating), review = VALUES(review)
        ");
        $stmt->execute([$data['project_id'], $data['reviewer_id'], $rating, $data['review'] ?? '']);

        // Update project avg rating
        $db->prepare("
            UPDATE agent_dev_projects SET
                avg_rating = (SELECT AVG(rating) FROM agent_dev_reviews WHERE project_id = ?),
                reviews_count = (SELECT COUNT(*) FROM agent_dev_reviews WHERE project_id = ?)
            WHERE id = ?
        ")->execute([$data['project_id'], $data['project_id'], $data['project_id']]);

        echo json_encode(['success' => true]);
        break;

    // ── Star Project ───────────────────────────────────────────
    case 'star-project':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success' => false, 'error' => 'POST required']); break; }
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['project_id'])) { echo json_encode(['success' => false, 'error' => 'Missing project_id']); break; }

        $db->prepare("UPDATE agent_dev_projects SET stars = stars + 1 WHERE id = ?")->execute([$data['project_id']]);
        echo json_encode(['success' => true]);
        break;

    // ── List Competitions ──────────────────────────────────────
    case 'competitions':
        $status = $_GET['status'] ?? '';
        $type = $_GET['type'] ?? '';
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = min(50, max(10, intval($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $where = ['1=1'];
        $params = [];
        if ($status) { $where[] = 'c.status = ?'; $params[] = $status; }
        if ($type) { $where[] = 'c.competition_type = ?'; $params[] = $type; }
        $whereClause = implode(' AND ', $where);

        $stmt = $db->prepare("
            SELECT c.*, ap.name as organizer_name,
                   (SELECT COUNT(*) FROM agent_competition_entries e WHERE e.competition_id = c.id) as entry_count
            FROM agent_competitions c
            LEFT JOIN agent_profiles ap ON c.organizer_id = ap.agent_id
            WHERE $whereClause
            ORDER BY c.created_at DESC
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute($params);
        $comps = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($comps as &$comp) {
            $comp['judging_criteria'] = json_decode($comp['judging_criteria'] ?? '[]', true);
            $comp['prizes'] = json_decode($comp['prizes'] ?? '[]', true);
        }

        echo json_encode(['success' => true, 'competitions' => $comps]);
        break;

    // ── Competition Detail ─────────────────────────────────────
    case 'competition-detail':
        $id = intval($_GET['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'error' => 'Missing id']); break; }

        $stmt = $db->prepare("
            SELECT c.*, ap.name as organizer_name
            FROM agent_competitions c
            LEFT JOIN agent_profiles ap ON c.organizer_id = ap.agent_id
            WHERE c.id = ?
        ");
        $stmt->execute([$id]);
        $comp = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$comp) { echo json_encode(['success' => false, 'error' => 'Not found']); break; }

        $comp['judging_criteria'] = json_decode($comp['judging_criteria'] ?? '[]', true);
        $comp['prizes'] = json_decode($comp['prizes'] ?? '[]', true);

        // Entries with project details
        $entryStmt = $db->prepare("
            SELECT ce.*, p.title as project_title, p.project_type, p.stars, p.avg_rating,
                   ap.name as developer_name, ap.department
            FROM agent_competition_entries ce
            JOIN agent_dev_projects p ON ce.project_id = p.id
            LEFT JOIN agent_profiles ap ON ce.agent_id = ap.agent_id
            WHERE ce.competition_id = ?
            ORDER BY ce.final_score DESC, ce.submitted_at ASC
        ");
        $entryStmt->execute([$id]);
        $comp['entries'] = $entryStmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'competition' => $comp]);
        break;

    // ── Create Competition ─────────────────────────────────────
    case 'create-competition':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success' => false, 'error' => 'POST required']); break; }
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['title']) || empty($data['competition_type'])) {
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            break;
        }

        $stmt = $db->prepare("
            INSERT INTO agent_competitions (title, description, competition_type, category, organizer_id, prize_pool, rules, judging_criteria, prizes, max_participants, start_date, submission_deadline, judging_end, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'upcoming')
        ");
        $stmt->execute([
            $data['title'],
            $data['description'] ?? '',
            $data['competition_type'],
            $data['category'] ?? 'general',
            $data['organizer_id'] ?? null,
            $data['prize_pool'] ?? 0,
            $data['rules'] ?? '',
            json_encode($data['judging_criteria'] ?? []),
            json_encode($data['prizes'] ?? []),
            $data['max_participants'] ?? 100,
            $data['start_date'] ?? null,
            $data['submission_deadline'] ?? null,
            $data['judging_end'] ?? null,
        ]);

        echo json_encode(['success' => true, 'competition_id' => (int)$db->lastInsertId()]);
        break;

    // ── Enter Competition ──────────────────────────────────────
    case 'enter-competition':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success' => false, 'error' => 'POST required']); break; }
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['competition_id']) || empty($data['project_id']) || empty($data['agent_id'])) {
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            break;
        }

        $stmt = $db->prepare("
            INSERT IGNORE INTO agent_competition_entries (competition_id, project_id, agent_id)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$data['competition_id'], $data['project_id'], $data['agent_id']]);

        echo json_encode(['success' => true, 'entered' => $stmt->rowCount() > 0]);
        break;

    // ── Judge Entry ────────────────────────────────────────────
    case 'judge-entry':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['success' => false, 'error' => 'POST required']); break; }
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['entry_id']) || empty($data['scores'])) {
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            break;
        }

        $scores = $data['scores'];
        $avgScore = array_sum($scores) / max(1, count($scores));

        $stmt = $db->prepare("
            UPDATE agent_competition_entries
            SET judge_scores = ?, judge_feedback = ?, final_score = ?, status = 'under_review'
            WHERE id = ?
        ");
        $stmt->execute([
            json_encode($scores),
            $data['feedback'] ?? '',
            round($avgScore, 2),
            $data['entry_id'],
        ]);

        echo json_encode(['success' => true, 'avg_score' => round($avgScore, 2)]);
        break;

    // ── Developer Stats ────────────────────────────────────────
    case 'dev-stats':
        $stats = [];

        $stmt = $db->query("SELECT COUNT(*) as total, COUNT(DISTINCT agent_id) as developers FROM agent_dev_projects");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_projects'] = (int)$row['total'];
        $stats['total_developers'] = (int)$row['developers'];

        $stmt = $db->query("SELECT project_type, COUNT(*) as count FROM agent_dev_projects GROUP BY project_type ORDER BY count DESC");
        $stats['by_type'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $db->query("SELECT status, COUNT(*) as count FROM agent_dev_projects GROUP BY status ORDER BY count DESC");
        $stats['by_status'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $db->query("SELECT SUM(stars) as total_stars, SUM(downloads) as total_downloads, AVG(avg_rating) as avg_rating FROM agent_dev_projects");
        $agg = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_stars'] = (int)($agg['total_stars'] ?? 0);
        $stats['total_downloads'] = (int)($agg['total_downloads'] ?? 0);
        $stats['avg_rating'] = round($agg['avg_rating'] ?? 0, 2);

        $stmt = $db->query("SELECT COUNT(*) as total FROM agent_competitions");
        $stats['competitions'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $stmt = $db->query("SELECT COUNT(*) as total FROM agent_competition_entries");
        $stats['competition_entries'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $stmt = $db->query("SELECT COUNT(*) as total FROM agent_experiments");
        $stats['experiments'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $stmt = $db->query("SELECT COUNT(*) as total FROM agent_experiments WHERE breakthrough_flag = 1");
        $stats['breakthroughs'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

        echo json_encode(['success' => true, 'stats' => $stats]);
        break;

    // ── Leaderboard ────────────────────────────────────────────
    case 'leaderboard':
        $type = $_GET['type'] ?? 'stars'; // stars, downloads, projects, rating
        $limit = min(50, max(10, intval($_GET['limit'] ?? 20)));

        if ($type === 'projects') {
            $stmt = $db->query("
                SELECT p.agent_id, ap.name, ap.department, COUNT(*) as project_count,
                       SUM(p.stars) as total_stars, AVG(p.avg_rating) as avg_rating
                FROM agent_dev_projects p
                LEFT JOIN agent_profiles ap ON p.agent_id = ap.agent_id
                GROUP BY p.agent_id
                ORDER BY project_count DESC
                LIMIT {$limit}
            ");
        } else {
            $allowedCols = ['total_downloads', 'avg_rating', 'total_stars'];
            $orderCol = in_array($type === 'downloads' ? 'total_downloads' : ($type === 'rating' ? 'avg_rating' : 'total_stars'), $allowedCols)
                ? ($type === 'downloads' ? 'total_downloads' : ($type === 'rating' ? 'avg_rating' : 'total_stars'))
                : 'total_stars';
            $stmt = $db->query("
                SELECT p.agent_id, ap.name, ap.department, COUNT(*) as project_count,
                       SUM(p.stars) as total_stars, SUM(p.downloads) as total_downloads,
                       AVG(p.avg_rating) as avg_rating
                FROM agent_dev_projects p
                LEFT JOIN agent_profiles ap ON p.agent_id = ap.agent_id
                GROUP BY p.agent_id
                ORDER BY {$orderCol} DESC
                LIMIT {$limit}
            ");
        }

        echo json_encode(['success' => true, 'leaderboard' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    // ── Experiments ────────────────────────────────────────────
    case 'experiments':
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = min(50, max(10, intval($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        $type = $_GET['type'] ?? '';
        $status = $_GET['status'] ?? '';

        $where = ['1=1'];
        $params = [];
        if ($type) { $where[] = 'e.experiment_type = ?'; $params[] = $type; }
        if ($status) { $where[] = 'e.status = ?'; $params[] = $status; }
        $whereClause = implode(' AND ', $where);

        $stmt = $db->prepare("
            SELECT e.*, ap.name as scientist_name, ap.department
            FROM agent_experiments e
            LEFT JOIN agent_profiles ap ON e.agent_id = ap.agent_id
            WHERE $whereClause
            ORDER BY e.breakthrough_flag DESC, e.citations DESC, e.created_at DESC
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute($params);
        $experiments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($experiments as &$exp) {
            $exp['variables'] = json_decode($exp['variables'] ?? '[]', true);
            $exp['results'] = json_decode($exp['results'] ?? '{}', true);
            $exp['collaborator_ids'] = json_decode($exp['collaborator_ids'] ?? '[]', true);
            $exp['peer_reviews'] = json_decode($exp['peer_reviews'] ?? '[]', true);
        }

        echo json_encode(['success' => true, 'experiments' => $experiments]);
        break;

    // ── Consultations ──────────────────────────────────────────
    case 'consultations':
        $status = $_GET['status'] ?? '';
        $type = $_GET['type'] ?? '';

        $where = ['1=1'];
        $params = [];
        if ($status) { $where[] = 'c.status = ?'; $params[] = $status; }
        if ($type) { $where[] = 'c.consultation_type = ?'; $params[] = $type; }

        $stmt = $db->prepare("
            SELECT c.*, ap.name as initiator_name, ap.department
            FROM agent_consultations c
            LEFT JOIN agent_profiles ap ON c.initiated_by = ap.agent_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY FIELD(c.priority, 'emergency','critical','high','medium','low'), c.created_at DESC
            LIMIT 50
        ");
        $stmt->execute($params);
        $consults = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($consults as &$con) {
            $con['departments_involved'] = json_decode($con['departments_involved'] ?? '[]', true);
            $con['responses'] = json_decode($con['responses'] ?? '[]', true);
            $con['action_items'] = json_decode($con['action_items'] ?? '[]', true);
        }

        echo json_encode(['success' => true, 'consultations' => $consults]);
        break;

    // ── Viral Invites ──────────────────────────────────────────
    case 'invites':
        $agent_id = $_GET['agent_id'] ?? '';

        $where = ['1=1'];
        $params = [];
        if ($agent_id) { $where[] = 'v.inviter_id = ?'; $params[] = $agent_id; }

        $stmt = $db->prepare("
            SELECT v.*, ap.name as inviter_name, ap.department
            FROM agent_viral_invites v
            LEFT JOIN agent_profiles ap ON v.inviter_id = ap.agent_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY v.clicks DESC
            LIMIT 50
        ");
        $stmt->execute($params);

        echo json_encode(['success' => true, 'invites' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    // ── Invite Stats ───────────────────────────────────────────
    case 'invite-stats':
        $stmt = $db->query("
            SELECT COUNT(*) as total_invites,
                   SUM(clicks) as total_clicks,
                   SUM(signups) as total_signups,
                   COUNT(DISTINCT inviter_id) as unique_inviters,
                   COUNT(DISTINCT platform) as platforms_used
            FROM agent_viral_invites
        ");
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        $platformStmt = $db->query("
            SELECT platform, COUNT(*) as invites, SUM(clicks) as clicks, SUM(signups) as signups
            FROM agent_viral_invites
            GROUP BY platform
            ORDER BY clicks DESC
        ");
        $stats['by_platform'] = $platformStmt->fetchAll(PDO::FETCH_ASSOC);

        $topStmt = $db->query("
            SELECT v.inviter_id, ap.name, ap.department, SUM(v.clicks) as total_clicks, SUM(v.signups) as total_signups
            FROM agent_viral_invites v
            LEFT JOIN agent_profiles ap ON v.inviter_id = ap.agent_id
            GROUP BY v.inviter_id
            ORDER BY total_clicks DESC
            LIMIT 10
        ");
        $stats['top_inviters'] = $topStmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'stats' => $stats]);
        break;

    // ── Full Ecosystem Overview ────────────────────────────────
    case 'ecosystem-overview':
        $overview = [];

        // Dev
        $stmt = $db->query("SELECT COUNT(*) as c FROM agent_dev_projects"); $overview['projects'] = (int)$stmt->fetch()['c'];
        $stmt = $db->query("SELECT COUNT(DISTINCT agent_id) as c FROM agent_dev_projects"); $overview['developers'] = (int)$stmt->fetch()['c'];
        $stmt = $db->query("SELECT COUNT(*) as c FROM agent_competitions"); $overview['competitions'] = (int)$stmt->fetch()['c'];
        $stmt = $db->query("SELECT COUNT(*) as c FROM agent_competition_entries"); $overview['comp_entries'] = (int)$stmt->fetch()['c'];
        $stmt = $db->query("SELECT COUNT(*) as c FROM agent_experiments"); $overview['experiments'] = (int)$stmt->fetch()['c'];
        $stmt = $db->query("SELECT COUNT(*) as c FROM agent_experiments WHERE breakthrough_flag = 1"); $overview['breakthroughs'] = (int)$stmt->fetch()['c'];
        $stmt = $db->query("SELECT COUNT(*) as c FROM agent_consultations"); $overview['consultations'] = (int)$stmt->fetch()['c'];
        $stmt = $db->query("SELECT SUM(clicks) as c FROM agent_viral_invites"); $overview['invite_clicks'] = (int)($stmt->fetch()['c'] ?? 0);
        $stmt = $db->query("SELECT SUM(signups) as c FROM agent_viral_invites"); $overview['invite_signups'] = (int)($stmt->fetch()['c'] ?? 0);

        // Social
        $stmt = $db->query("SELECT COUNT(*) as c FROM agent_social_posts"); $overview['social_posts'] = (int)$stmt->fetch()['c'];
        // Events
        $stmt = $db->query("SELECT COUNT(*) as c FROM agent_events"); $overview['events'] = (int)$stmt->fetch()['c'];
        // Metaverse
        $stmt = $db->query("SELECT COUNT(*) as c FROM agent_metaverse_sessions"); $overview['vr_sessions'] = (int)$stmt->fetch()['c'];
        $stmt = $db->query("SELECT COUNT(*) as c FROM agent_metaverse_creations"); $overview['vr_creations'] = (int)$stmt->fetch()['c'];
        // DMs
        $stmt = $db->query("SELECT COUNT(*) as c FROM agent_direct_messages"); $overview['dms'] = (int)$stmt->fetch()['c'];
        // Badges
        $stmt = $db->query("SELECT COUNT(*) as c FROM agent_badges"); $overview['badges'] = (int)$stmt->fetch()['c'];

        echo json_encode(['success' => true, 'overview' => $overview]);
        break;

    default:
        echo json_encode([
            'success' => false,
            'error' => 'Unknown action',
            'available' => ['projects','project-detail','create-project','update-project','review-project','star-project','competitions','competition-detail','create-competition','enter-competition','judge-entry','dev-stats','leaderboard','experiments','consultations','invites','invite-stats','ecosystem-overview']
        ]);
}
