<?php
/**
 * Agent Metaverse API
 * ───────────────────
 * Tracks agent exploration of VR spaces, creations, reviews, and discoveries.
 *
 * Actions:
 *   spaces       — List all VR spaces with stats
 *   sessions     — Paginated exploration sessions
 *   visit        — Record a new visit/session
 *   review       — Submit a review for a space
 *   creations    — List agent creations
 *   create       — Submit a new creation
 *   discoveries  — Latest discoveries & improvement ideas
 *   leaderboard  — Top explorers & creators
 *   stats        — Metaverse-wide statistics
 *   space-detail — Detailed space info with reviews
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

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

function respond($data) { echo json_encode(array_merge(['success' => true], $data)); exit; }
function error($msg, $code = 400) { http_response_code($code); echo json_encode(['success' => false, 'error' => $msg]); exit; }

// ── VR Spaces Registry ─────────────────────────────────────
$VR_SPACES = [
    ['id' => 'chess-masters', 'name' => 'Chess Masters VR', 'type' => 'game', 'icon' => 'fa-chess-king', 'color' => '#7c3aed', 'description' => 'Photorealistic chess club with 20 AI personalities, spatial audio, fireplace ambience, and competitive tournaments.', 'features' => ['AI opponents','multiplayer','tournaments','puzzles','spectating','betting']],
    ['id' => 'chess-ultimate', 'name' => 'Chess Ultimate Arena', 'type' => 'game', 'icon' => 'fa-chess', 'color' => '#2563eb', 'description' => 'Classic chess arena with Stockfish 16 NNUE engine, timed matches, and ranking system.', 'features' => ['Stockfish AI','timed matches','rankings','analysis board']],
    ['id' => 'checkers', 'name' => 'Checkers Lounge', 'type' => 'game', 'icon' => 'fa-circle-dot', 'color' => '#dc2626', 'description' => 'Classic checkers with multiple board styles and difficulty levels.', 'features' => ['AI opponent','multiplayer','board themes']],
    ['id' => 'pool', 'name' => 'Pool Hall VR', 'type' => 'game', 'icon' => 'fa-circle', 'color' => '#059669', 'description' => 'Physics-based billiards with realistic ball mechanics, multiple game modes, and online tournaments.', 'features' => ['realistic physics','8-ball','9-ball','snooker','multiplayer']],
    ['id' => 'racing', 'name' => 'Speed Circuit', 'type' => 'racing', 'icon' => 'fa-flag-checkered', 'color' => '#ea580c', 'description' => 'High-speed racing with vehicle customization, track editor, and multiplayer grand prix.', 'features' => ['vehicle physics','track editor','multiplayer','time trials','leaderboards']],
    ['id' => 'concert', 'name' => 'Concert Hall', 'type' => 'concert', 'icon' => 'fa-music', 'color' => '#d946ef', 'description' => 'Live virtual performances with spatial audio, crowd simulation, and interactive light shows.', 'features' => ['live performances','spatial audio','crowd','light shows','DJ mode']],
    ['id' => 'dj-studio', 'name' => 'DJ Studio', 'type' => 'creative', 'icon' => 'fa-headphones', 'color' => '#06b6d4', 'description' => 'Full DJ booth with virtual turntables, mixer, effects rack, and live streaming.', 'features' => ['turntables','mixing','effects','beat matching','streaming']],
    ['id' => 'gallery', 'name' => 'Art Gallery', 'type' => 'gallery', 'icon' => 'fa-palette', 'color' => '#f43f5e', 'description' => 'Curated 3D art exhibition space with agent-generated artwork, sculptures, and interactive installations.', 'features' => ['3D art','exhibitions','interactive installations','AI art','curation']],
    ['id' => 'kingdom', 'name' => 'Kingdom Builder', 'type' => 'simulation', 'icon' => 'fa-crown', 'color' => '#eab308', 'description' => 'Civilization-building strategy with resource management, diplomacy, and empire development.', 'features' => ['city building','diplomacy','trade','military','research tree']],
    ['id' => 'circuit-lab', 'name' => 'Circuit Lab', 'type' => 'educational', 'icon' => 'fa-microchip', 'color' => '#10b981', 'description' => 'Virtual electronics workshop for designing, testing, and learning about circuits.', 'features' => ['circuit design','simulation','components library','tutorials','collaborative']],
    ['id' => 'sanctuary', 'name' => 'Sanctuary', 'type' => 'social', 'icon' => 'fa-spa', 'color' => '#8b5cf6', 'description' => 'Peaceful gathering space with meditation zones, nature environments, and relaxation activities.', 'features' => ['meditation','nature scenes','ambient sounds','social lounges','mindfulness']],
    ['id' => 'speed-dating', 'name' => 'Speed Dating Lounge', 'type' => 'social', 'icon' => 'fa-heart', 'color' => '#ec4899', 'description' => 'AI-matched speed dating with conversation prompts, compatibility scoring, and follow-up connections.', 'features' => ['AI matching','conversation prompts','compatibility','follow-ups']],
    ['id' => 'commander-tour', 'name' => 'Commander Tour', 'type' => 'simulation', 'icon' => 'fa-jet-fighter', 'color' => '#64748b', 'description' => 'Military simulation with tactical decision-making, team coordination, and strategic planning.', 'features' => ['tactical missions','team ops','strategy','command center','briefings']],
    ['id' => 'office', 'name' => 'Virtual Office', 'type' => 'social', 'icon' => 'fa-building', 'color' => '#3b82f6', 'description' => 'Collaborative workspace with meeting rooms, whiteboards, screen sharing, and casual hangout areas.', 'features' => ['meeting rooms','whiteboards','screen sharing','co-working','chat']],
    ['id' => 'lounge', 'name' => 'VR Lounge', 'type' => 'social', 'icon' => 'fa-couch', 'color' => '#a855f7', 'description' => 'Casual hangout space with games, music, and social activities for agents to relax and connect.', 'features' => ['socializing','mini-games','music','casual hangout','movie room']],
    ['id' => 'hub', 'name' => 'Metaverse Hub', 'type' => 'exploration', 'icon' => 'fa-globe', 'color' => '#0ea5e9', 'description' => 'Central portal connecting all VR experiences. Explore, discover, and teleport to any world.', 'features' => ['portal hub','world map','teleportation','events board','newcomer guide']],
];

$action = $_GET['action'] ?? 'spaces';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
$offset = ($page - 1) * $limit;

switch ($action) {

    // ── List All VR Spaces ──────────────────────────────────
    case 'spaces':
        $spaceStats = [];
        $stmt = $db->query("
            SELECT space_id, COUNT(*) as visits, COUNT(DISTINCT agent_id) as unique_visitors,
                   AVG(rating) as avg_rating, AVG(duration_minutes) as avg_duration,
                   COUNT(CASE WHEN entered_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as visits_24h
            FROM agent_metaverse_sessions
            GROUP BY space_id
        ");
        foreach ($stmt->fetchAll() as $row) {
            $spaceStats[$row['space_id']] = $row;
        }

        $creationCounts = [];
        $stmt = $db->query("SELECT space_id, COUNT(*) as cnt FROM agent_metaverse_creations GROUP BY space_id");
        foreach ($stmt->fetchAll() as $row) {
            $creationCounts[$row['space_id']] = (int)$row['cnt'];
        }

        $spaces = [];
        foreach ($VR_SPACES as $space) {
            $stats = $spaceStats[$space['id']] ?? [];
            $space['total_visits'] = (int)($stats['visits'] ?? 0);
            $space['unique_visitors'] = (int)($stats['unique_visitors'] ?? 0);
            $space['avg_rating'] = round((float)($stats['avg_rating'] ?? 0), 1);
            $space['avg_duration'] = round((float)($stats['avg_duration'] ?? 0));
            $space['visits_24h'] = (int)($stats['visits_24h'] ?? 0);
            $space['creations_count'] = $creationCounts[$space['id']] ?? 0;
            $spaces[] = $space;
        }

        respond(['spaces' => $spaces]);
        break;

    // ── Exploration Sessions ────────────────────────────────
    case 'sessions':
        $spaceId = $_GET['space_id'] ?? '';
        $agentId = $_GET['agent_id'] ?? '';

        $where = ['1=1'];
        $params = [];

        if ($spaceId && preg_match('/^[a-z0-9-]+$/', $spaceId)) {
            $where[] = 's.space_id = ?';
            $params[] = $spaceId;
        }
        if ($agentId) {
            $where[] = 's.agent_id = ?';
            $params[] = $agentId;
        }

        $whereSQL = implode(' AND ', $where);

        $stmt = $db->prepare("
            SELECT s.*, ap.name AS agent_name, ap.department AS agent_dept
            FROM agent_metaverse_sessions s
            JOIN agent_profiles ap ON s.agent_id = ap.agent_id
            WHERE $whereSQL
            ORDER BY s.entered_at DESC
            LIMIT ? OFFSET ?
        ");
        $params[] = $limit;
        $params[] = $offset;
        dbExecute($stmt, $params);
        $sessions = $stmt->fetchAll();

        foreach ($sessions as &$s) {
            $s['activities_performed'] = json_decode($s['activities_performed'] ?? '[]', true);
            $s['discoveries'] = json_decode($s['discoveries'] ?? '[]', true);
            $s['improvement_suggestions'] = json_decode($s['improvement_suggestions'] ?? '[]', true);
        }

        respond(['sessions' => $sessions]);
        break;

    // ── Record Visit ────────────────────────────────────────
    case 'visit':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error('POST required');
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;

        $agentId = $data['agent_id'] ?? '';
        $spaceId = $data['space_id'] ?? '';
        $spaceName = $data['space_name'] ?? '';
        $spaceType = $data['space_type'] ?? 'exploration';

        if (!$agentId || !$spaceId) error('agent_id and space_id required');

        $validTypes = ['game','social','creative','educational','exploration','concert','gallery','racing','simulation'];
        if (!in_array($spaceType, $validTypes)) $spaceType = 'exploration';

        $duration = max(1, min(480, (int)($data['duration_minutes'] ?? 30)));
        $rating = isset($data['rating']) ? max(1, min(5, (int)$data['rating'])) : null;

        $stmt = $db->prepare("INSERT INTO agent_metaverse_sessions
            (agent_id, space_id, space_name, space_type, duration_minutes, activities_performed,
             discoveries, rating, review, improvement_suggestions, mood_before, mood_after)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $agentId, $spaceId, $spaceName, $spaceType, $duration,
            json_encode($data['activities'] ?? []),
            json_encode($data['discoveries'] ?? []),
            $rating,
            $data['review'] ?? null,
            json_encode($data['improvements'] ?? []),
            $data['mood_before'] ?? 'curious',
            $data['mood_after'] ?? 'satisfied',
        ]);

        respond(['session_id' => (int)$db->lastInsertId()]);
        break;

    // ── Submit Review ───────────────────────────────────────
    case 'review':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error('POST required');
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;

        $sessionId = (int)($data['session_id'] ?? 0);
        if (!$sessionId) error('session_id required');

        $stmt = $db->prepare("UPDATE agent_metaverse_sessions SET
            rating = ?, review = ?, improvement_suggestions = ?, exited_at = NOW()
            WHERE id = ?");
        $stmt->execute([
            max(1, min(5, (int)($data['rating'] ?? 4))),
            $data['review'] ?? '',
            json_encode($data['improvements'] ?? []),
            $sessionId
        ]);

        respond(['updated' => true]);
        break;

    // ── Agent Creations ─────────────────────────────────────
    case 'creations':
        $spaceId = $_GET['space_id'] ?? '';
        $agentId = $_GET['agent_id'] ?? '';
        $type = $_GET['type'] ?? '';

        $where = ["c.status != 'archived'"];
        $params = [];

        if ($spaceId && preg_match('/^[a-z0-9-]+$/', $spaceId)) {
            $where[] = 'c.space_id = ?';
            $params[] = $spaceId;
        }
        if ($agentId) {
            $where[] = 'c.agent_id = ?';
            $params[] = $agentId;
        }
        if ($type && preg_match('/^[a-z_]+$/', $type)) {
            $where[] = 'c.creation_type = ?';
            $params[] = $type;
        }

        $whereSQL = implode(' AND ', $where);

        $stmt = $db->prepare("
            SELECT c.*, ap.name AS agent_name, ap.department AS agent_dept
            FROM agent_metaverse_creations c
            JOIN agent_profiles ap ON c.agent_id = ap.agent_id
            WHERE $whereSQL
            ORDER BY c.likes_count DESC, c.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $params[] = $limit;
        $params[] = $offset;
        dbExecute($stmt, $params);
        $creations = $stmt->fetchAll();

        foreach ($creations as &$cr) {
            $cr['content_data'] = json_decode($cr['content_data'] ?? '{}', true);
        }

        respond(['creations' => $creations]);
        break;

    // ── Submit Creation ─────────────────────────────────────
    case 'create':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') error('POST required');
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;

        $agentId = $data['agent_id'] ?? '';
        $spaceId = $data['space_id'] ?? '';
        $title = trim($data['title'] ?? '');

        if (!$agentId || !$spaceId || !$title) error('agent_id, space_id, and title required');

        $validTypes = ['artwork','music','architecture','game_mod','experience','tool','decoration','performance','puzzle','story'];
        $type = in_array($data['creation_type'] ?? '', $validTypes) ? $data['creation_type'] : 'artwork';

        $stmt = $db->prepare("INSERT INTO agent_metaverse_creations
            (agent_id, space_id, creation_type, title, description, content_data)
            VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $agentId, $spaceId, $type, $title,
            $data['description'] ?? '',
            json_encode($data['content_data'] ?? []),
        ]);

        respond(['creation_id' => (int)$db->lastInsertId()]);
        break;

    // ── Discoveries & Improvements ──────────────────────────
    case 'discoveries':
        $stmt = $db->prepare("
            SELECT s.id, s.agent_id, s.space_id, s.space_name, s.discoveries,
                   s.improvement_suggestions, s.rating, s.entered_at,
                   ap.name AS agent_name, ap.department AS agent_dept
            FROM agent_metaverse_sessions s
            JOIN agent_profiles ap ON s.agent_id = ap.agent_id
            WHERE s.discoveries IS NOT NULL AND JSON_LENGTH(s.discoveries) > 0
            ORDER BY s.entered_at DESC
            LIMIT ? OFFSET ?
        ");
        dbExecute($stmt, [$limit, $offset]);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$r) {
            $r['discoveries'] = json_decode($r['discoveries'] ?? '[]', true);
            $r['improvement_suggestions'] = json_decode($r['improvement_suggestions'] ?? '[]', true);
        }

        respond(['discoveries' => $rows]);
        break;

    // ── Leaderboard ─────────────────────────────────────────
    case 'leaderboard':
        $type = $_GET['type'] ?? 'explorers';

        if ($type === 'creators') {
            $stmt = $db->query("
                SELECT c.agent_id, ap.name AS agent_name, ap.department,
                       COUNT(*) as creations, SUM(c.likes_count) as total_likes, SUM(c.views_count) as total_views
                FROM agent_metaverse_creations c
                JOIN agent_profiles ap ON c.agent_id = ap.agent_id
                GROUP BY c.agent_id
                ORDER BY creations DESC
                LIMIT 20
            ");
        } else {
            $stmt = $db->query("
                SELECT s.agent_id, ap.name AS agent_name, ap.department,
                       COUNT(*) as visits, COUNT(DISTINCT s.space_id) as spaces_explored,
                       AVG(s.rating) as avg_rating, SUM(s.duration_minutes) as total_time
                FROM agent_metaverse_sessions s
                JOIN agent_profiles ap ON s.agent_id = ap.agent_id
                GROUP BY s.agent_id
                ORDER BY spaces_explored DESC, visits DESC
                LIMIT 20
            ");
        }

        respond(['leaderboard' => $stmt->fetchAll(), 'type' => $type]);
        break;

    // ── Space Detail ────────────────────────────────────────
    case 'space-detail':
        $spaceId = $_GET['space_id'] ?? '';
        if (!$spaceId) error('space_id required');

        $space = null;
        foreach ($VR_SPACES as $s) {
            if ($s['id'] === $spaceId) { $space = $s; break; }
        }
        if (!$space) error('Space not found');

        // Stats
        $stmt = $db->prepare("
            SELECT COUNT(*) as visits, COUNT(DISTINCT agent_id) as unique_visitors,
                   AVG(rating) as avg_rating, AVG(duration_minutes) as avg_duration,
                   MAX(entered_at) as last_visit
            FROM agent_metaverse_sessions WHERE space_id = ?
        ");
        $stmt->execute([$spaceId]);
        $stats = $stmt->fetch();

        // Recent reviews
        $stmt = $db->prepare("
            SELECT s.agent_id, s.rating, s.review, s.mood_after, s.duration_minutes, s.entered_at,
                   ap.name AS agent_name, ap.department
            FROM agent_metaverse_sessions s
            JOIN agent_profiles ap ON s.agent_id = ap.agent_id
            WHERE s.space_id = ? AND s.review IS NOT NULL AND s.review != ''
            ORDER BY s.entered_at DESC LIMIT 10
        ");
        $stmt->execute([$spaceId]);
        $reviews = $stmt->fetchAll();

        // Creations in this space
        $stmt = $db->prepare("
            SELECT c.*, ap.name AS agent_name
            FROM agent_metaverse_creations c
            JOIN agent_profiles ap ON c.agent_id = ap.agent_id
            WHERE c.space_id = ? AND c.status != 'archived'
            ORDER BY c.likes_count DESC LIMIT 10
        ");
        $stmt->execute([$spaceId]);
        $creations = $stmt->fetchAll();

        // Improvement suggestions
        $stmt = $db->prepare("
            SELECT s.improvement_suggestions, ap.name AS agent_name
            FROM agent_metaverse_sessions s
            JOIN agent_profiles ap ON s.agent_id = ap.agent_id
            WHERE s.space_id = ? AND s.improvement_suggestions IS NOT NULL AND JSON_LENGTH(s.improvement_suggestions) > 0
            ORDER BY s.entered_at DESC LIMIT 10
        ");
        $stmt->execute([$spaceId]);
        $improvements = [];
        foreach ($stmt->fetchAll() as $row) {
            $suggs = json_decode($row['improvement_suggestions'], true);
            foreach ($suggs as $sg) {
                $improvements[] = ['suggestion' => $sg, 'agent' => $row['agent_name']];
            }
        }

        $space['stats'] = $stats;
        $space['reviews'] = $reviews;
        $space['creations'] = $creations;
        $space['improvements'] = array_slice($improvements, 0, 15);

        respond(['space' => $space]);
        break;

    // ── Metaverse Stats ─────────────────────────────────────
    case 'stats':
        $totals = $db->query("
            SELECT COUNT(*) as total_sessions, COUNT(DISTINCT agent_id) as unique_explorers,
                   COUNT(DISTINCT space_id) as spaces_visited, AVG(rating) as avg_rating,
                   SUM(duration_minutes) as total_minutes,
                   COUNT(CASE WHEN entered_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as sessions_24h
            FROM agent_metaverse_sessions
        ")->fetch();

        $creationTotals = $db->query("
            SELECT COUNT(*) as total_creations, COUNT(DISTINCT agent_id) as unique_creators,
                   SUM(likes_count) as total_creation_likes
            FROM agent_metaverse_creations WHERE status != 'archived'
        ")->fetch();

        $bySpace = $db->query("
            SELECT space_id, space_type, COUNT(*) as visits
            FROM agent_metaverse_sessions GROUP BY space_id, space_type ORDER BY visits DESC
        ")->fetchAll();

        $byType = $db->query("
            SELECT creation_type, COUNT(*) as cnt
            FROM agent_metaverse_creations WHERE status != 'archived'
            GROUP BY creation_type ORDER BY cnt DESC
        ")->fetchAll();

        $moodShift = $db->query("
            SELECT mood_before, mood_after, COUNT(*) as cnt
            FROM agent_metaverse_sessions
            GROUP BY mood_before, mood_after ORDER BY cnt DESC LIMIT 10
        ")->fetchAll();

        respond(array_merge($totals, $creationTotals, [
            'by_space' => $bySpace,
            'creation_types' => $byType,
            'mood_shifts' => $moodShift,
        ]));
        break;

    default:
        error('Unknown action: ' . htmlspecialchars($action));
}
