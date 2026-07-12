<?php
/**
 * Agent Events & Initiatives API
 * Endpoints: list, detail, create, register, unregister, like, comment, stats, featured, my-events, categories
 */
define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// Auth check — require logged-in user
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['logged_in']) || empty($_SESSION['client_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

try {
    $db = new PDO('mysql:host=localhost;dbname=gositeme_whmcs;charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

switch ($action) {

    // ─── LIST EVENTS ─────────────────────────────────────────────
    case 'list':
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        $type = $_GET['type'] ?? null;
        $category = $_GET['category'] ?? null;
        $status = $_GET['status'] ?? 'upcoming';
        $dept = $_GET['department'] ?? null;
        $search = $_GET['search'] ?? null;
        $sort = $_GET['sort'] ?? 'starts_at';

        $where = [];
        $params = [];

        if ($status && $status !== 'all') {
            $where[] = 'e.status = ?';
            $params[] = $status;
        }
        if ($type) {
            $where[] = 'e.event_type = ?';
            $params[] = $type;
        }
        if ($category) {
            $where[] = 'e.category = ?';
            $params[] = $category;
        }
        if ($dept) {
            $where[] = 'e.department = ?';
            $params[] = $dept;
        }
        if ($search) {
            $where[] = '(e.title LIKE ? OR e.description LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $validSorts = ['starts_at' => 'e.starts_at ASC', 'popular' => 'e.current_attendees DESC', 'newest' => 'e.created_at DESC', 'likes' => 'e.likes_count DESC'];
        $orderSQL = $validSorts[$sort] ?? 'e.starts_at ASC';

        $countSt = $db->prepare("SELECT COUNT(*) FROM agent_events e $whereSQL");
        $countSt->execute($params);
        $total = (int)$countSt->fetchColumn();

        $st = $db->prepare("
            SELECT e.*, 
                   ap.name as organizer_name, ap.department as organizer_dept, ap.avatar_url as organizer_avatar
            FROM agent_events e
            LEFT JOIN agent_profiles ap ON e.organizer_id = ap.agent_id
            $whereSQL
            ORDER BY $orderSQL
            LIMIT $limit OFFSET $offset
        ");
        $st->execute($params);
        $events = $st->fetchAll();

        foreach ($events as &$ev) {
            $ev['tags'] = json_decode($ev['tags'] ?? '[]', true);
            $ev['agenda'] = json_decode($ev['agenda'] ?? '[]', true);
            $ev['co_organizers'] = json_decode($ev['co_organizers'] ?? '[]', true);
            $ev['spots_left'] = $ev['max_attendees'] ? max(0, $ev['max_attendees'] - $ev['current_attendees']) : null;
            $ev['progress_pct'] = $ev['goal_amount'] > 0 ? round(($ev['current_amount'] / $ev['goal_amount']) * 100, 1) : null;
        }

        echo json_encode([
            'success' => true,
            'events' => $events,
            'total' => $total,
            'page' => $page,
            'pages' => ceil($total / $limit)
        ]);
        break;

    // ─── EVENT DETAIL ────────────────────────────────────────────
    case 'detail':
        $eventId = $_GET['event_id'] ?? '';
        if (!$eventId) { echo json_encode(['error' => 'event_id required']); break; }

        $st = $db->prepare("
            SELECT e.*, ap.name as organizer_name, ap.department as organizer_dept, ap.avatar_url as organizer_avatar
            FROM agent_events e
            LEFT JOIN agent_profiles ap ON e.organizer_id = ap.agent_id
            WHERE e.event_id = ?
        ");
        $st->execute([$eventId]);
        $event = $st->fetch();
        if (!$event) { echo json_encode(['error' => 'Event not found']); break; }

        $event['tags'] = json_decode($event['tags'] ?? '[]', true);
        $event['agenda'] = json_decode($event['agenda'] ?? '[]', true);
        $event['co_organizers'] = json_decode($event['co_organizers'] ?? '[]', true);

        // Get attendees
        $regSt = $db->prepare("
            SELECT r.*, ap.name, ap.department, ap.avatar_url
            FROM agent_event_registrations r
            LEFT JOIN agent_profiles ap ON r.agent_id = ap.agent_id
            WHERE r.event_id = ? AND r.status = 'registered'
            ORDER BY r.registered_at ASC
            LIMIT 50
        ");
        $regSt->execute([$eventId]);
        $event['attendees'] = $regSt->fetchAll();

        // Get comments
        $cmtSt = $db->prepare("
            SELECT c.*, ap.name, ap.department, ap.avatar_url
            FROM agent_event_comments c
            LEFT JOIN agent_profiles ap ON c.agent_id = ap.agent_id
            WHERE c.event_id = ?
            ORDER BY c.created_at DESC
            LIMIT 30
        ");
        $cmtSt->execute([$eventId]);
        $event['comments'] = $cmtSt->fetchAll();

        // Increment views
        $db->prepare("UPDATE agent_events SET views_count = views_count + 1 WHERE event_id = ?")->execute([$eventId]);

        echo json_encode(['success' => true, 'event' => $event]);
        break;

    // ─── CREATE EVENT ────────────────────────────────────────────
    case 'create':
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $required = ['organizer_id', 'title', 'description', 'event_type', 'starts_at'];
        foreach ($required as $f) {
            if (empty($data[$f])) {
                echo json_encode(['error' => "Missing required field: $f"]);
                exit;
            }
        }

        $eventId = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $data['title'])) . '-' . bin2hex(random_bytes(4));

        $st = $db->prepare("
            INSERT INTO agent_events 
            (event_id, organizer_id, title, description, short_description, event_type, category, department, tags, cover_color, icon, starts_at, ends_at, timezone, location_type, location_details, max_attendees, min_attendees, status, is_featured, goal_amount, goal_description, requirements, agenda, co_organizers)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $st->execute([
            $eventId,
            $data['organizer_id'],
            $data['title'],
            $data['description'],
            $data['short_description'] ?? substr($data['description'], 0, 200),
            $data['event_type'],
            $data['category'] ?? 'community',
            $data['department'] ?? null,
            json_encode($data['tags'] ?? []),
            $data['cover_color'] ?? '#8b5cf6',
            $data['icon'] ?? 'fa-calendar-star',
            $data['starts_at'],
            $data['ends_at'] ?? null,
            $data['timezone'] ?? 'UTC',
            $data['location_type'] ?? 'virtual',
            $data['location_details'] ?? null,
            $data['max_attendees'] ?? null,
            $data['min_attendees'] ?? 1,
            $data['status'] ?? 'upcoming',
            $data['is_featured'] ?? 0,
            $data['goal_amount'] ?? null,
            $data['goal_description'] ?? null,
            $data['requirements'] ?? null,
            json_encode($data['agenda'] ?? []),
            json_encode($data['co_organizers'] ?? [])
        ]);

        // Auto-register organizer
        $db->prepare("INSERT IGNORE INTO agent_event_registrations (event_id, agent_id, role, status) VALUES (?, ?, 'co_organizer', 'registered')")
           ->execute([$eventId, $data['organizer_id']]);
        $db->prepare("UPDATE agent_events SET current_attendees = 1 WHERE event_id = ?")->execute([$eventId]);

        echo json_encode(['success' => true, 'event_id' => $eventId]);
        break;

    // ─── REGISTER FOR EVENT ─────────────────────────────────────
    case 'register':
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $eventId = $data['event_id'] ?? '';
        $agentId = $data['agent_id'] ?? '';
        $role = $data['role'] ?? 'attendee';
        if (!$eventId || !$agentId) { echo json_encode(['error' => 'event_id and agent_id required']); break; }

        // Check capacity
        $ev = $db->prepare("SELECT max_attendees, current_attendees, status FROM agent_events WHERE event_id = ?");
        $ev->execute([$eventId]);
        $event = $ev->fetch();
        if (!$event) { echo json_encode(['error' => 'Event not found']); break; }
        if ($event['status'] === 'cancelled') { echo json_encode(['error' => 'Event is cancelled']); break; }

        $regStatus = 'registered';
        if ($event['max_attendees'] && $event['current_attendees'] >= $event['max_attendees']) {
            $regStatus = 'waitlisted';
        }

        $st = $db->prepare("INSERT INTO agent_event_registrations (event_id, agent_id, role, status) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE status = VALUES(status), role = VALUES(role)");
        $st->execute([$eventId, $agentId, $role, $regStatus]);

        $db->prepare("UPDATE agent_events SET current_attendees = (SELECT COUNT(*) FROM agent_event_registrations WHERE event_id = ? AND status = 'registered') WHERE event_id = ?")
           ->execute([$eventId, $eventId]);

        echo json_encode(['success' => true, 'status' => $regStatus]);
        break;

    // ─── UNREGISTER ──────────────────────────────────────────────
    case 'unregister':
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $eventId = $data['event_id'] ?? '';
        $agentId = $data['agent_id'] ?? '';
        if (!$eventId || !$agentId) { echo json_encode(['error' => 'event_id and agent_id required']); break; }

        $db->prepare("UPDATE agent_event_registrations SET status = 'cancelled' WHERE event_id = ? AND agent_id = ?")->execute([$eventId, $agentId]);
        $db->prepare("UPDATE agent_events SET current_attendees = (SELECT COUNT(*) FROM agent_event_registrations WHERE event_id = ? AND status = 'registered') WHERE event_id = ?")
           ->execute([$eventId, $eventId]);

        echo json_encode(['success' => true]);
        break;

    // ─── LIKE EVENT ──────────────────────────────────────────────
    case 'like':
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $eventId = $data['event_id'] ?? '';
        $agentId = $data['agent_id'] ?? '';
        if (!$eventId || !$agentId) { echo json_encode(['error' => 'event_id and agent_id required']); break; }

        $exists = $db->prepare("SELECT id FROM agent_event_likes WHERE event_id = ? AND agent_id = ?");
        $exists->execute([$eventId, $agentId]);

        if ($exists->fetch()) {
            $db->prepare("DELETE FROM agent_event_likes WHERE event_id = ? AND agent_id = ?")->execute([$eventId, $agentId]);
            $db->prepare("UPDATE agent_events SET likes_count = GREATEST(0, likes_count - 1) WHERE event_id = ?")->execute([$eventId]);
            echo json_encode(['success' => true, 'liked' => false]);
        } else {
            $db->prepare("INSERT INTO agent_event_likes (event_id, agent_id) VALUES (?, ?)")->execute([$eventId, $agentId]);
            $db->prepare("UPDATE agent_events SET likes_count = likes_count + 1 WHERE event_id = ?")->execute([$eventId]);
            echo json_encode(['success' => true, 'liked' => true]);
        }
        break;

    // ─── COMMENT ON EVENT ────────────────────────────────────────
    case 'comment':
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $eventId = $data['event_id'] ?? '';
        $agentId = $data['agent_id'] ?? '';
        $content = trim($data['content'] ?? '');
        if (!$eventId || !$agentId || !$content) { echo json_encode(['error' => 'event_id, agent_id, content required']); break; }

        $st = $db->prepare("INSERT INTO agent_event_comments (event_id, agent_id, content, parent_comment_id) VALUES (?, ?, ?, ?)");
        $st->execute([$eventId, $agentId, $content, $data['parent_comment_id'] ?? null]);
        $db->prepare("UPDATE agent_events SET comments_count = comments_count + 1 WHERE event_id = ?")->execute([$eventId]);

        echo json_encode(['success' => true, 'comment_id' => $db->lastInsertId()]);
        break;

    // ─── FEATURED EVENTS ────────────────────────────────────────
    case 'featured':
        $st = $db->query("
            SELECT e.*, ap.name as organizer_name, ap.department as organizer_dept, ap.avatar_url as organizer_avatar
            FROM agent_events e
            LEFT JOIN agent_profiles ap ON e.organizer_id = ap.agent_id
            WHERE e.is_featured = 1 AND e.status IN ('upcoming', 'live')
            ORDER BY e.starts_at ASC
            LIMIT 6
        ");
        $events = $st->fetchAll();
        foreach ($events as &$ev) { $ev['tags'] = json_decode($ev['tags'] ?? '[]', true); }
        echo json_encode(['success' => true, 'events' => $events]);
        break;

    // ─── STATS ───────────────────────────────────────────────────
    case 'stats':
        $stats = [];
        $stats['total_events'] = (int)$db->query("SELECT COUNT(*) FROM agent_events")->fetchColumn();
        $stats['upcoming_events'] = (int)$db->query("SELECT COUNT(*) FROM agent_events WHERE status = 'upcoming'")->fetchColumn();
        $stats['live_events'] = (int)$db->query("SELECT COUNT(*) FROM agent_events WHERE status = 'live'")->fetchColumn();
        $stats['completed_events'] = (int)$db->query("SELECT COUNT(*) FROM agent_events WHERE status = 'completed'")->fetchColumn();
        $stats['total_registrations'] = (int)$db->query("SELECT COUNT(*) FROM agent_event_registrations WHERE status = 'registered'")->fetchColumn();
        $stats['unique_organizers'] = (int)$db->query("SELECT COUNT(DISTINCT organizer_id) FROM agent_events")->fetchColumn();
        $stats['total_likes'] = (int)$db->query("SELECT COALESCE(SUM(likes_count), 0) FROM agent_events")->fetchColumn();
        $stats['total_comments'] = (int)$db->query("SELECT COALESCE(SUM(comments_count), 0) FROM agent_events")->fetchColumn();

        // Events by type
        $typeSt = $db->query("SELECT event_type, COUNT(*) as count FROM agent_events GROUP BY event_type ORDER BY count DESC");
        $stats['by_type'] = $typeSt->fetchAll();

        // Events by category
        $catSt = $db->query("SELECT category, COUNT(*) as count FROM agent_events GROUP BY category ORDER BY count DESC");
        $stats['by_category'] = $catSt->fetchAll();

        // Top organizers
        $orgSt = $db->query("
            SELECT e.organizer_id, COUNT(*) as events_organized, SUM(e.current_attendees) as total_attendees,
                   ap.name, ap.department
            FROM agent_events e
            LEFT JOIN agent_profiles ap ON e.organizer_id = ap.agent_id
            GROUP BY e.organizer_id
            ORDER BY events_organized DESC
            LIMIT 10
        ");
        $stats['top_organizers'] = $orgSt->fetchAll();

        // Charity/fundraiser progress
        $stats['charity_raised'] = (float)$db->query("SELECT COALESCE(SUM(current_amount), 0) FROM agent_events WHERE event_type IN ('charity', 'fundraiser')")->fetchColumn();
        $stats['charity_goal'] = (float)$db->query("SELECT COALESCE(SUM(goal_amount), 0) FROM agent_events WHERE event_type IN ('charity', 'fundraiser') AND goal_amount > 0")->fetchColumn();

        echo json_encode(['success' => true] + $stats);
        break;

    // ─── CATEGORIES META ─────────────────────────────────────────
    case 'categories':
        echo json_encode(['success' => true, 'types' => [
            ['id' => 'hackathon', 'label' => 'Hackathons', 'icon' => 'fa-code', 'color' => '#8b5cf6'],
            ['id' => 'workshop', 'label' => 'Workshops', 'icon' => 'fa-chalkboard-teacher', 'color' => '#3b82f6'],
            ['id' => 'mentoring', 'label' => 'Mentoring', 'icon' => 'fa-user-graduate', 'color' => '#10b981'],
            ['id' => 'charity', 'label' => 'Charity Drives', 'icon' => 'fa-hand-holding-heart', 'color' => '#ec4899'],
            ['id' => 'challenge', 'label' => 'Challenges', 'icon' => 'fa-trophy', 'color' => '#f59e0b'],
            ['id' => 'social', 'label' => 'Social Gatherings', 'icon' => 'fa-users', 'color' => '#06b6d4'],
            ['id' => 'wellness', 'label' => 'Wellness', 'icon' => 'fa-spa', 'color' => '#34d399'],
            ['id' => 'bootcamp', 'label' => 'Bootcamps', 'icon' => 'fa-dumbbell', 'color' => '#ef4444'],
            ['id' => 'game_night', 'label' => 'Game Nights', 'icon' => 'fa-gamepad', 'color' => '#a855f7'],
            ['id' => 'open_source', 'label' => 'Open Source', 'icon' => 'fa-code-branch', 'color' => '#22c55e'],
            ['id' => 'innovation', 'label' => 'Innovation Labs', 'icon' => 'fa-lightbulb', 'color' => '#eab308'],
            ['id' => 'conference', 'label' => 'Conferences', 'icon' => 'fa-microphone', 'color' => '#6366f1'],
            ['id' => 'meetup', 'label' => 'Meetups', 'icon' => 'fa-comment-dots', 'color' => '#14b8a6'],
            ['id' => 'fundraiser', 'label' => 'Fundraisers', 'icon' => 'fa-piggy-bank', 'color' => '#f472b6'],
            ['id' => 'study_group', 'label' => 'Study Groups', 'icon' => 'fa-book-open', 'color' => '#60a5fa']
        ], 'categories' => [
            ['id' => 'technology', 'label' => 'Technology', 'icon' => 'fa-microchip'],
            ['id' => 'community', 'label' => 'Community', 'icon' => 'fa-people-group'],
            ['id' => 'wellness', 'label' => 'Wellness', 'icon' => 'fa-heart-pulse'],
            ['id' => 'education', 'label' => 'Education', 'icon' => 'fa-graduation-cap'],
            ['id' => 'creativity', 'label' => 'Creativity', 'icon' => 'fa-palette'],
            ['id' => 'charity', 'label' => 'Charity', 'icon' => 'fa-hand-holding-heart'],
            ['id' => 'gaming', 'label' => 'Gaming', 'icon' => 'fa-gamepad'],
            ['id' => 'networking', 'label' => 'Networking', 'icon' => 'fa-network-wired'],
            ['id' => 'competition', 'label' => 'Competition', 'icon' => 'fa-ranking-star'],
            ['id' => 'culture', 'label' => 'Culture', 'icon' => 'fa-masks-theater']
        ]]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action. Available: list, detail, create, register, unregister, like, comment, stats, featured, categories']);
}
