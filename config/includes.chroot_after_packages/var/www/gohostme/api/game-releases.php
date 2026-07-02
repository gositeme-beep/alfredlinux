<?php
/**
 * GoSiteMe Developer Game Release System
 * Secure game publishing pipeline — developers submit games, 
 * sandboxed review, approval workflow, public release
 * Commander: Danny Perez
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';

session_start();
header('Content-Type: application/json');

$client_id = $_SESSION['client_id'] ?? null;
$internal = $_SERVER['HTTP_X_INTERNAL_SECRET'] ?? '';
$is_internal = !empty(INTERNAL_SECRET) && hash_equals(INTERNAL_SECRET, $internal);
if (!$client_id && !$is_internal) { echo json_encode(['error' => 'Auth required']); exit; }
require_once dirname(__DIR__) . '/includes/api-security.php';

$action = $_REQUEST['action'] ?? 'store';
$db = getDB();

// ── Content Security Scanner ──
function scanGameCode($code) {
    $threats = [];
    
    // Block external resource loading
    $external_patterns = [
        '/fetch\s*\(/i' => 'fetch() calls detected — no external requests allowed',
        '/XMLHttpRequest/i' => 'XMLHttpRequest detected — no external requests allowed',
        '/navigator\.sendBeacon/i' => 'sendBeacon detected — no data exfiltration allowed',
        '/WebSocket/i' => 'WebSocket detected — no external connections allowed',
        '/eval\s*\(/i' => 'eval() detected — dynamic code execution blocked',
        '/Function\s*\(/i' => 'Function constructor detected — dynamic code blocked',
        '/document\.cookie/i' => 'Cookie access detected — blocked for security',
        '/localStorage|sessionStorage/i' => 'Storage access detected — sandboxed games cannot access storage',
        '/window\.open/i' => 'window.open detected — popup blocked',
        '/window\.location/i' => 'Location change detected — navigation blocked',
        '/parent\.|top\.|frames\[/i' => 'Frame escape attempt detected — blocked',
        '/importScripts/i' => 'importScripts detected — external code loading blocked',
        '/<\s*script[^>]+src\s*=/i' => 'External script tag detected — no remote scripts',
        '/<\s*link[^>]+href\s*=\s*["\']https?:/i' => 'External stylesheet detected — no remote CSS',
        '/<\s*iframe/i' => 'Nested iframe detected — blocked',
        '/crypto\.subtle/i' => 'Crypto API access detected — blocked',
    ];
    
    foreach ($external_patterns as $pattern => $message) {
        if (preg_match($pattern, $code)) {
            $threats[] = $message;
        }
    }
    
    // Check code size (max 500KB)
    if (strlen($code) > 512000) {
        $threats[] = 'Game code exceeds 500KB limit';
    }
    
    return $threats;
}

// ── Rating Calculation ──
function calculateTrustScore($db, $developer_id) {
    $stmt = $db->prepare("SELECT 
        COUNT(*) as total_releases,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
        SUM(CASE WHEN status = 'flagged' THEN 1 ELSE 0 END) as flagged
        FROM game_releases WHERE developer_id = ?");
    $stmt->execute([$developer_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$stats || $stats['total_releases'] == 0) return 50;
    
    $score = 50;
    $score += min(25, $stats['approved'] * 5);
    $score -= $stats['rejected'] * 10;
    $score -= $stats['flagged'] * 20;
    
    return max(0, min(100, $score));
}

switch ($action) {

    // ── Developer Registration ──
    case 'register-developer':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(['error' => 'POST required'], 405); }
        
        $studio_name = trim($_POST['studio_name'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        
        if (empty($studio_name) || strlen($studio_name) < 3 || strlen($studio_name) > 60) {
            jsonResponse(['error' => 'Studio name must be 3-60 characters'], 400);
        }
        
        // Check if already registered
        $stmt = $db->prepare("SELECT id FROM game_developers WHERE client_id = ?");
        $stmt->execute([$client_id]);
        if ($stmt->fetch()) {
            jsonResponse(['error' => 'Already registered as developer'], 409);
        }
        
        $stmt = $db->prepare("INSERT INTO game_developers (client_id, studio_name, bio, trust_score, status, created_at) 
            VALUES (?, ?, ?, 50, 'active', NOW())");
        $stmt->execute([$client_id, $studio_name, $bio]);
        
        jsonResponse(['success' => true, 'developer_id' => $db->lastInsertId(), 'message' => 'Developer account created']);
        break;

    // ── Developer Profile ──
    case 'developer-profile':
        $dev_id = intval($_REQUEST['developer_id'] ?? 0);
        $query_id = $dev_id ?: $client_id;
        $field = $dev_id ? 'id' : 'client_id';
        
        $stmt = $db->prepare("SELECT * FROM game_developers WHERE $field = ?");
        $stmt->execute([$query_id]);
        $dev = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$dev) { jsonResponse(['error' => 'Developer not found'], 404); }
        
        // Get their releases
        $stmt = $db->prepare("SELECT id, title, genre, status, downloads, rating, created_at 
            FROM game_releases WHERE developer_id = ? ORDER BY created_at DESC LIMIT 20");
        $stmt->execute([$dev['id']]);
        $releases = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        jsonResponse(['developer' => $dev, 'releases' => $releases]);
        break;

    // ── Submit Game for Release ──
    case 'submit':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(['error' => 'POST required'], 405); }
        
        // Must be registered developer
        $stmt = $db->prepare("SELECT id, trust_score, status FROM game_developers WHERE client_id = ? AND status = 'active'");
        $stmt->execute([$client_id]);
        $dev = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$dev) { jsonResponse(['error' => 'Must register as developer first'], 403); }
        
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $genre = trim($_POST['genre'] ?? 'other');
        $game_code = $_POST['game_code'] ?? '';
        $price = floatval($_POST['price'] ?? 0);
        $age_rating = trim($_POST['age_rating'] ?? 'everyone');
        
        // Validation
        if (empty($title) || strlen($title) < 3 || strlen($title) > 100) {
            jsonResponse(['error' => 'Title must be 3-100 characters'], 400);
        }
        if (empty($game_code)) {
            jsonResponse(['error' => 'Game code is required'], 400);
        }
        if ($price < 0 || $price > 99.99) {
            jsonResponse(['error' => 'Price must be $0-$99.99'], 400);
        }
        
        $valid_genres = ['platformer','adventure','puzzle','racing','rpg','strategy','arcade','simulation','sports','educational','other'];
        if (!in_array($genre, $valid_genres)) $genre = 'other';
        
        $valid_ages = ['everyone','ages_8_plus','ages_13_plus','ages_16_plus'];
        if (!in_array($age_rating, $valid_ages)) $age_rating = 'everyone';
        
        // Security scan
        $threats = scanGameCode($game_code);
        if (!empty($threats)) {
            jsonResponse([
                'error' => 'Security scan failed',
                'threats' => $threats,
                'message' => 'Your game code contains blocked patterns. Remove them and resubmit.'
            ], 400);
        }
        
        // Auto-approve for high trust, otherwise pending review
        $status = ($dev['trust_score'] >= 80) ? 'approved' : 'pending_review';
        
        $stmt = $db->prepare("INSERT INTO game_releases 
            (developer_id, title, description, genre, game_code, price, age_rating, status, security_scan, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'passed', NOW())");
        $stmt->execute([$dev['id'], $title, $description, $genre, $game_code, $price, $age_rating, $status]);
        
        $release_id = $db->lastInsertId();
        
        // Log the submission
        $stmt = $db->prepare("INSERT INTO game_release_log (release_id, action, actor_id, details, created_at) 
            VALUES (?, 'submitted', ?, ?, NOW())");
        $stmt->execute([$release_id, $client_id, json_encode(['trust_score' => $dev['trust_score'], 'auto_approved' => $status === 'approved'])]);
        
        jsonResponse([
            'success' => true, 
            'release_id' => $release_id, 
            'status' => $status,
            'message' => $status === 'approved' 
                ? 'Game approved automatically (high trust developer)' 
                : 'Game submitted for review — Commander will be notified'
        ]);
        break;

    // ── Store (Browse Approved Games) ──
    case 'store':
        $genre = $_REQUEST['genre'] ?? null;
        $sort = $_REQUEST['sort'] ?? 'newest';
        $page = max(1, intval($_REQUEST['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $where = "gr.status = 'approved'";
        $params = [];
        
        if ($genre) {
            $where .= " AND gr.genre = ?";
            $params[] = $genre;
        }
        
        $order = match($sort) {
            'popular' => 'gr.downloads DESC',
            'rating' => 'gr.rating DESC',
            'price_low' => 'gr.price ASC',
            'price_high' => 'gr.price DESC',
            default => 'gr.created_at DESC'
        };
        
        $stmt = $db->prepare("SELECT gr.id, gr.title, gr.description, gr.genre, gr.price, gr.age_rating, 
            gr.downloads, gr.rating, gr.rating_count, gr.created_at,
            gd.studio_name, gd.trust_score
            FROM game_releases gr 
            JOIN game_developers gd ON gr.developer_id = gd.id
            WHERE $where ORDER BY $order LIMIT $limit OFFSET $offset");
        $stmt->execute($params);
        $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Total count
        $stmt = $db->prepare("SELECT COUNT(*) FROM game_releases gr WHERE $where");
        $stmt->execute($params);
        $total = $stmt->fetchColumn();
        
        $genres = ['platformer','adventure','puzzle','racing','rpg','strategy','arcade','simulation','sports','educational','other'];
        
        jsonResponse([
            'games' => $games,
            'total' => intval($total),
            'page' => $page,
            'pages' => ceil($total / $limit),
            'genres' => $genres
        ]);
        break;

    // ── Play a Released Game ──
    case 'play':
        $release_id = intval($_REQUEST['release_id'] ?? 0);
        if (!$release_id) { jsonResponse(['error' => 'release_id required'], 400); }
        
        $stmt = $db->prepare("SELECT gr.*, gd.studio_name FROM game_releases gr 
            JOIN game_developers gd ON gr.developer_id = gd.id 
            WHERE gr.id = ? AND gr.status = 'approved'");
        $stmt->execute([$release_id]);
        $game = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$game) { jsonResponse(['error' => 'Game not found or not approved'], 404); }
        
        // Increment downloads
        $db->prepare("UPDATE game_releases SET downloads = downloads + 1 WHERE id = ?")->execute([$release_id]);
        
        jsonResponse([
            'game' => [
                'id' => $game['id'],
                'title' => $game['title'],
                'description' => $game['description'],
                'genre' => $game['genre'],
                'studio' => $game['studio_name'],
                'age_rating' => $game['age_rating'],
                'game_code' => $game['game_code'],
                'downloads' => $game['downloads'] + 1,
                'rating' => $game['rating']
            ]
        ]);
        break;

    // ── Rate a Game ──
    case 'rate':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(['error' => 'POST required'], 405); }
        
        $release_id = intval($_POST['release_id'] ?? 0);
        $score = intval($_POST['score'] ?? 0);
        $review = trim($_POST['review'] ?? '');
        
        if (!$release_id || $score < 1 || $score > 5) {
            jsonResponse(['error' => 'release_id and score (1-5) required'], 400);
        }
        
        // Check game exists and is approved
        $stmt = $db->prepare("SELECT id, developer_id FROM game_releases WHERE id = ? AND status = 'approved'");
        $stmt->execute([$release_id]);
        $game = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$game) { jsonResponse(['error' => 'Game not found'], 404); }
        
        // Upsert rating
        $stmt = $db->prepare("INSERT INTO game_release_ratings (release_id, client_id, score, review, created_at) 
            VALUES (?, ?, ?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE score = VALUES(score), review = VALUES(review), created_at = NOW()");
        $stmt->execute([$release_id, $client_id, $score, $review]);
        
        // Update aggregate rating
        $stmt = $db->prepare("SELECT AVG(score) as avg_rating, COUNT(*) as count FROM game_release_ratings WHERE release_id = ?");
        $stmt->execute([$release_id]);
        $agg = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $db->prepare("UPDATE game_releases SET rating = ?, rating_count = ? WHERE id = ?")
            ->execute([round($agg['avg_rating'], 2), $agg['count'], $release_id]);
        
        jsonResponse(['success' => true, 'rating' => round($agg['avg_rating'], 2), 'count' => $agg['count']]);
        break;

    // ── Admin: Review Queue ──
    case 'review-queue':
        if ($client_id != 33 && !$is_internal) { jsonResponse(['error' => 'Commander access only'], 403); }
        
        $stmt = $db->query("SELECT gr.*, gd.studio_name, gd.trust_score 
            FROM game_releases gr 
            JOIN game_developers gd ON gr.developer_id = gd.id
            WHERE gr.status = 'pending_review' 
            ORDER BY gr.created_at ASC");
        $pending = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        jsonResponse(['pending' => $pending, 'count' => count($pending)]);
        break;

    // ── Admin: Approve / Reject / Flag ──
    case 'review':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { jsonResponse(['error' => 'POST required'], 405); }
        if ($client_id != 33 && !$is_internal) { jsonResponse(['error' => 'Commander access only'], 403); }
        
        $release_id = intval($_POST['release_id'] ?? 0);
        $decision = $_POST['decision'] ?? '';
        $reason = trim($_POST['reason'] ?? '');
        
        if (!in_array($decision, ['approved', 'rejected', 'flagged'])) {
            jsonResponse(['error' => 'Decision must be approved, rejected, or flagged'], 400);
        }
        
        $stmt = $db->prepare("SELECT id, developer_id FROM game_releases WHERE id = ?");
        $stmt->execute([$release_id]);
        $game = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$game) { jsonResponse(['error' => 'Release not found'], 404); }
        
        $db->prepare("UPDATE game_releases SET status = ?, review_notes = ?, reviewed_at = NOW() WHERE id = ?")
            ->execute([$decision, $reason, $release_id]);
        
        // Log the review
        $stmt = $db->prepare("INSERT INTO game_release_log (release_id, action, actor_id, details, created_at) 
            VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$release_id, 'review_' . $decision, $client_id, json_encode(['reason' => $reason])]);
        
        // Update developer trust score
        $new_trust = calculateTrustScore($db, $game['developer_id']);
        $db->prepare("UPDATE game_developers SET trust_score = ? WHERE id = ?")->execute([$new_trust, $game['developer_id']]);
        
        jsonResponse(['success' => true, 'decision' => $decision, 'new_trust_score' => $new_trust]);
        break;

    // ── My Releases (Developer Dashboard) ──
    case 'my-releases':
        $stmt = $db->prepare("SELECT id FROM game_developers WHERE client_id = ?");
        $stmt->execute([$client_id]);
        $dev = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$dev) { jsonResponse(['releases' => [], 'registered' => false]); }
        
        $stmt = $db->prepare("SELECT id, title, genre, status, downloads, rating, rating_count, price, created_at, reviewed_at, review_notes 
            FROM game_releases WHERE developer_id = ? ORDER BY created_at DESC");
        $stmt->execute([$dev['id']]);
        $releases = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Revenue summary
        $stmt = $db->prepare("SELECT COALESCE(SUM(downloads * price * 0.70), 0) as estimated_revenue FROM game_releases WHERE developer_id = ? AND status = 'approved'");
        $stmt->execute([$dev['id']]);
        $revenue = $stmt->fetch(PDO::FETCH_ASSOC);
        
        jsonResponse([
            'registered' => true,
            'releases' => $releases,
            'revenue' => [
                'estimated' => round($revenue['estimated_revenue'], 2),
                'split' => '70/30 — Developer keeps 70%'
            ]
        ]);
        break;

    // ── Platform Stats ──
    case 'stats':
        $stmt = $db->query("SELECT 
            (SELECT COUNT(*) FROM game_developers WHERE status = 'active') as developers,
            (SELECT COUNT(*) FROM game_releases) as total_releases,
            (SELECT COUNT(*) FROM game_releases WHERE status = 'approved') as approved_releases,
            (SELECT COUNT(*) FROM game_releases WHERE status = 'pending_review') as pending_review,
            (SELECT SUM(downloads) FROM game_releases WHERE status = 'approved') as total_downloads,
            (SELECT AVG(rating) FROM game_releases WHERE status = 'approved' AND rating > 0) as avg_rating");
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        jsonResponse(['stats' => $stats]);
        break;

    // ── Seed (Initialize tables + sample games) ──
    case 'seed':
        if ($client_id != 33 && !$is_internal) { jsonResponse(['error' => 'Commander access only'], 403); }
        
        // Create tables
        $db->exec("CREATE TABLE IF NOT EXISTS game_developers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            client_id INT NOT NULL,
            studio_name VARCHAR(60) NOT NULL,
            bio TEXT,
            trust_score INT DEFAULT 50,
            status ENUM('active','suspended','banned') DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY (client_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $db->exec("CREATE TABLE IF NOT EXISTS game_releases (
            id INT AUTO_INCREMENT PRIMARY KEY,
            developer_id INT NOT NULL,
            title VARCHAR(100) NOT NULL,
            description TEXT,
            genre VARCHAR(30) DEFAULT 'other',
            game_code LONGTEXT,
            price DECIMAL(5,2) DEFAULT 0.00,
            age_rating VARCHAR(20) DEFAULT 'everyone',
            status ENUM('draft','pending_review','approved','rejected','flagged','suspended') DEFAULT 'draft',
            security_scan ENUM('pending','passed','failed') DEFAULT 'pending',
            downloads INT DEFAULT 0,
            rating DECIMAL(3,2) DEFAULT 0.00,
            rating_count INT DEFAULT 0,
            review_notes TEXT,
            reviewed_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX (developer_id),
            INDEX (status),
            INDEX (genre)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $db->exec("CREATE TABLE IF NOT EXISTS game_release_ratings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            release_id INT NOT NULL,
            client_id INT NOT NULL,
            score TINYINT NOT NULL,
            review TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY (release_id, client_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $db->exec("CREATE TABLE IF NOT EXISTS game_release_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            release_id INT NOT NULL,
            action VARCHAR(50) NOT NULL,
            actor_id INT,
            details JSON,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX (release_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // Register Commander as first developer
        $stmt = $db->prepare("INSERT IGNORE INTO game_developers (client_id, studio_name, bio, trust_score, status, created_at) 
            VALUES (1, 'GoSiteMe Studios', 'Official GoSiteMe game development studio — Commander HQ', 100, 'active', NOW())");
        $stmt->execute();
        
        jsonResponse([
            'success' => true,
            'tables_created' => ['game_developers', 'game_releases', 'game_release_ratings', 'game_release_log'],
            'message' => 'Game Release System initialized — Commander registered as GoSiteMe Studios (Trust 100)'
        ]);
        break;

    default:
        jsonResponse(['error' => 'Unknown action', 'available' => [
            'store', 'play', 'rate', 'submit', 'my-releases', 'register-developer', 'developer-profile',
            'review-queue', 'review', 'stats', 'seed'
        ]], 400);
}
