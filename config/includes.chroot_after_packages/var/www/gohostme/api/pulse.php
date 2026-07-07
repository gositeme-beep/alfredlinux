<?php
/**
 * Pulse Social Network API
 *
 * Endpoints (via ?action=):
 *   feed            GET    — personalised feed (posts from followed users + own)
 *   global          GET    — global/discover feed (all public posts)
 *   post            POST   — create a post
 *   post-delete     POST   — delete own post
 *   like            POST   — toggle like on a post
 *   comment         POST   — add comment to a post
 *   comment-delete  POST   — delete own comment
 *   follow          POST   — follow a user
 *   unfollow        POST   — unfollow a user
 *   followers       GET    — list followers for a user
 *   following       GET    — list who a user follows
 *   profile         GET    — get user profile + stats
 *   search          GET    — search users and posts
 *   trending        GET    — trending posts (most liked in 24h)
 *   notifications   GET    — user notifications
 *   notif-read      POST   — mark notifications read
 */

define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

session_start();

// ─── CORS / Headers ─────────────────────────────────────────────────────────
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Internal-Secret');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

// ─── Auth ───────────────────────────────────────────────────────────────────
function pulseRequireAuth(): int {
    if (empty($_SESSION['logged_in']) || empty($_SESSION['client_id'])) {
        jsonResponse(['error' => 'Authentication required'], 401);
    }
    return (int) $_SESSION['client_id'];
}

function pulseOptionalAuth(): ?int {
    if (!empty($_SESSION['logged_in']) && !empty($_SESSION['client_id'])) {
        return (int) $_SESSION['client_id'];
    }
    return null;
}

// ─── Schema ─────────────────────────────────────────────────────────────────
function ensurePulseSchema(PDO $db): void {
    static $checked = false;
    if ($checked) return;
    $checked = true;

    $db->exec("CREATE TABLE IF NOT EXISTS pulse_posts (
        id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id         INT UNSIGNED NOT NULL,
        content         TEXT NOT NULL,
        post_type       ENUM('text','image','link','game_result','agent_activity') DEFAULT 'text',
        media_url       VARCHAR(500) DEFAULT NULL,
        link_url        VARCHAR(500) DEFAULT NULL,
        link_title       VARCHAR(255) DEFAULT NULL,
        link_preview    VARCHAR(500) DEFAULT NULL,
        game_data       JSON DEFAULT NULL,
        like_count      INT UNSIGNED DEFAULT 0,
        comment_count   INT UNSIGNED DEFAULT 0,
        created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (user_id),
        INDEX idx_created (created_at),
        INDEX idx_type (post_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS pulse_likes (
        id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        post_id     BIGINT UNSIGNED NOT NULL,
        user_id     INT UNSIGNED NOT NULL,
        created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_post_user (post_id, user_id),
        INDEX idx_post (post_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS pulse_comments (
        id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        post_id     BIGINT UNSIGNED NOT NULL,
        user_id     INT UNSIGNED NOT NULL,
        content     TEXT NOT NULL,
        created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_post (post_id),
        INDEX idx_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS pulse_follows (
        id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        follower_id     INT UNSIGNED NOT NULL,
        following_id    INT UNSIGNED NOT NULL,
        created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_follow (follower_id, following_id),
        INDEX idx_follower (follower_id),
        INDEX idx_following (following_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS pulse_notifications (
        id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id     INT UNSIGNED NOT NULL,
        actor_id    INT UNSIGNED NOT NULL,
        type        ENUM('like','comment','follow','mention') NOT NULL,
        post_id     BIGINT UNSIGNED DEFAULT NULL,
        is_read     TINYINT(1) DEFAULT 0,
        created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_read (user_id, is_read),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ── Profiles table (bio, avatar, badge) ─────────────────────────────
    $db->exec("CREATE TABLE IF NOT EXISTS pulse_profiles (
        user_id     INT UNSIGNED PRIMARY KEY,
        bio         VARCHAR(280) DEFAULT '',
        avatar_url  VARCHAR(500) DEFAULT NULL,
        cover_url   VARCHAR(500) DEFAULT NULL,
        badge       VARCHAR(50) DEFAULT NULL,
        theme_color VARCHAR(7) DEFAULT '#3b82f6',
        created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // ── Bookmarks table ─────────────────────────────────────────────────
    $db->exec("CREATE TABLE IF NOT EXISTS pulse_bookmarks (
        id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id     INT UNSIGNED NOT NULL,
        post_id     BIGINT UNSIGNED NOT NULL,
        created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_user_post (user_id, post_id),
        INDEX idx_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

// ─── Helpers ────────────────────────────────────────────────────────────────
function getGravatarUrl(string $email, int $size = 80): string {
    $hash = md5(strtolower(trim($email)));
    return "https://www.gravatar.com/avatar/{$hash}?d=identicon&s={$size}";
}

function getUserName(PDO $db, int $userId): string {
    $stmt = $db->prepare("SELECT COALESCE(NULLIF(CONCAT(firstname,' ',lastname),''), COALESCE(NULLIF(firstname,''), email)) AS name FROM clients WHERE id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return $row ? $row['name'] : 'Unknown';
}

function getUserNames(PDO $db, array $userIds): array {
    if (empty($userIds)) return [];
    $placeholders = implode(',', array_fill(0, count($userIds), '?'));
    $stmt = $db->prepare("SELECT id, COALESCE(NULLIF(CONCAT(firstname, ' ', lastname),''), COALESCE(NULLIF(firstname,''), email)) AS name, email FROM clients WHERE id IN ($placeholders)");
    $stmt->execute(array_values($userIds));
    $map = [];
    while ($row = $stmt->fetch()) {
        $map[(int)$row['id']] = ['name' => $row['name'], 'email' => $row['email']];
    }
    return $map;
}

function getUserProfiles(PDO $db, array $userIds): array {
    if (empty($userIds)) return [];
    $ph = implode(',', array_fill(0, count($userIds), '?'));
    $stmt = $db->prepare("SELECT user_id, bio, avatar_url, badge FROM pulse_profiles WHERE user_id IN ($ph)");
    $stmt->execute(array_values($userIds));
    $map = [];
    while ($row = $stmt->fetch()) {
        $map[(int)$row['user_id']] = $row;
    }
    return $map;
}

function getInitials(string $name): string {
    $parts = explode(' ', trim($name));
    if (count($parts) >= 2) {
        return strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1));
    }
    return strtoupper(mb_substr($name, 0, 2));
}

function enrichPosts(PDO $db, array $posts, ?int $currentUserId): array {
    if (empty($posts)) return [];

    $postIds = array_column($posts, 'id');
    $userIds = array_unique(array_column($posts, 'user_id'));

    // Get user names + emails
    $userData = getUserNames($db, $userIds);
    $profiles = getUserProfiles($db, $userIds);

    // Military rank data
    $militaryMap = [];
    $rankColorMap = ['O-6'=>'#d4a017','O-5'=>'#c0392b','O-4'=>'#8e44ad','O-3'=>'#2980b9','O-2'=>'#27ae60','O-1'=>'#16a085','E-4'=>'#f39c12','E-3'=>'#e67e22','E-2'=>'#3498db','E-1'=>'#95a5a6','E-0'=>'#7f8c8d'];
    $rankIconMap = ['O-6'=>'fa-crown','O-5'=>'fa-star','O-4'=>'fa-medal','O-3'=>'fa-shield-alt','O-2'=>'fa-anchor','O-1'=>'fa-chevron-up','E-4'=>'fa-chevron-up','E-3'=>'fa-chevron-up','E-2'=>'fa-user','E-1'=>'fa-user','E-0'=>'fa-user'];
    if (!empty($userIds)) {
        $ph = implode(',', array_fill(0, count($userIds), '?'));
        $mStmt = $db->prepare("SELECT r.client_id, r.rank_code, r.rank_level, m.rank_name FROM alfred_military_roster r JOIN alfred_military_ranks m ON r.rank_code = m.rank_code WHERE r.client_id IN ($ph) AND r.status='active'");
        $mStmt->execute(array_values($userIds));
        while ($mr = $mStmt->fetch()) {
            $militaryMap[(int)$mr['client_id']] = [
                'rank_code'  => $mr['rank_code'],
                'rank_name'  => $mr['rank_name'],
                'rank_level' => (int)$mr['rank_level'],
                'rank_color' => $rankColorMap[$mr['rank_code']] ?? '#95a5a6',
                'rank_icon'  => $rankIconMap[$mr['rank_code']] ?? 'fa-user',
            ];
        }
    }

    // Get current user's likes
    $likedSet = [];
    if ($currentUserId && !empty($postIds)) {
        $ph = implode(',', array_fill(0, count($postIds), '?'));
        $stmt = $db->prepare("SELECT post_id FROM pulse_likes WHERE user_id = ? AND post_id IN ($ph)");
        $stmt->execute(array_merge([$currentUserId], $postIds));
        while ($row = $stmt->fetch()) {
            $likedSet[(int)$row['post_id']] = true;
        }
    }

    // Get current user's bookmarks
    $bookmarkSet = [];
    if ($currentUserId && !empty($postIds)) {
        $ph = implode(',', array_fill(0, count($postIds), '?'));
        $stmt = $db->prepare("SELECT post_id FROM pulse_bookmarks WHERE user_id = ? AND post_id IN ($ph)");
        $stmt->execute(array_merge([$currentUserId], $postIds));
        while ($row = $stmt->fetch()) {
            $bookmarkSet[(int)$row['post_id']] = true;
        }
    }

    // Get recent comments (last 3 per post)
    $comments = [];
    if (!empty($postIds)) {
        $ph = implode(',', array_fill(0, count($postIds), '?'));
        $stmt = $db->prepare("
            SELECT c.*, 
                   COALESCE(NULLIF(CONCAT(cl.firstname,' ',cl.lastname),''), COALESCE(NULLIF(cl.firstname,''), cl.email)) AS author_name,
                   cl.email AS author_email
            FROM pulse_comments c
            LEFT JOIN clients cl ON c.user_id = cl.id
            WHERE c.post_id IN ($ph)
            ORDER BY c.created_at DESC
        ");
        $stmt->execute($postIds);
        while ($row = $stmt->fetch()) {
            $pid = (int)$row['post_id'];
            if (!isset($comments[$pid])) $comments[$pid] = [];
            if (count($comments[$pid]) < 3) {
                $comments[$pid][] = [
                    'id'          => (int)$row['id'],
                    'user_id'     => (int)$row['user_id'],
                    'author_name' => $row['author_name'] ?: 'Unknown',
                    'initials'    => getInitials($row['author_name'] ?: 'U'),
                    'avatar_url'  => $row['author_email'] ? getGravatarUrl($row['author_email'], 32) : null,
                    'content'     => $row['content'],
                    'created_at'  => $row['created_at'],
                ];
            }
        }
        // Reverse to show oldest first
        foreach ($comments as &$arr) {
            $arr = array_reverse($arr);
        }
    }

    return array_map(function ($p) use ($userData, $profiles, $likedSet, $bookmarkSet, $comments, $currentUserId, $militaryMap) {
        $uid  = (int)$p['user_id'];
        $pid  = (int)$p['id'];
        $info = $userData[$uid] ?? ['name' => 'Unknown', 'email' => ''];
        $name = $info['name'];
        $profile = $profiles[$uid] ?? null;
        $avatarUrl = $profile['avatar_url'] ?? ($info['email'] ? getGravatarUrl($info['email']) : null);
        $badge = $profile['badge'] ?? null;
        $military = $militaryMap[$uid] ?? null;
        // Override badge with military rank if present
        if ($military && !$badge) {
            $badge = ($military['rank_level'] >= 11) ? 'commander' : (($military['rank_level'] >= 5) ? 'verified' : null);
        }
        return [
            'id'            => $pid,
            'user_id'       => $uid,
            'author_name'   => $name,
            'initials'      => getInitials($name),
            'avatar_url'    => $avatarUrl,
            'badge'         => $badge,
            'military'      => $military,
            'content'       => $p['content'],
            'post_type'     => $p['post_type'],
            'media_url'     => $p['media_url'],
            'link_url'      => $p['link_url'],
            'link_title'    => $p['link_title'],
            'link_preview'  => $p['link_preview'],
            'game_data'     => $p['game_data'] ? json_decode($p['game_data'], true) : null,
            'like_count'    => (int)$p['like_count'],
            'comment_count' => (int)$p['comment_count'],
            'liked'         => isset($likedSet[$pid]),
            'bookmarked'    => isset($bookmarkSet[$pid]),
            'is_own'        => $currentUserId === $uid,
            'comments'      => $comments[$pid] ?? [],
            'created_at'    => $p['created_at'],
        ];
    }, $posts);
}

function notify(PDO $db, int $userId, int $actorId, string $type, ?int $postId = null): void {
    if ($userId === $actorId) return; // don't notify self
    $stmt = $db->prepare("INSERT INTO pulse_notifications (user_id, actor_id, type, post_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $actorId, $type, $postId]);
}

// ─── Router ─────────────────────────────────────────────────────────────────
$action = sanitize($_GET['action'] ?? '', 30);
$db = getDB();
if (!$db) jsonResponse(['error' => 'Database unavailable'], 503);

ensurePulseSchema($db);

switch ($action) {

    // ── Personal feed (posts from people I follow + my own) ─────────────
    case 'feed': {
        $uid  = pulseRequireAuth();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $stmt = $db->prepare("
            SELECT p.* FROM pulse_posts p
            WHERE p.user_id = ? 
               OR p.user_id IN (SELECT following_id FROM pulse_follows WHERE follower_id = ?)
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?
        ");
        dbExecute($stmt, [$uid, $uid, $limit, $offset]);
        $posts = $stmt->fetchAll();

        jsonResponse([
            'success' => true,
            'posts'   => enrichPosts($db, $posts, $uid),
            'page'    => $page,
            'limit'   => $limit,
        ]);
    }

    // ── Global/discover feed ────────────────────────────────────────────
    case 'global': {
        $uid   = pulseOptionalAuth();
        $page  = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $stmt = $db->prepare("
            SELECT * FROM pulse_posts
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ");
        dbExecute($stmt, [$limit, $offset]);
        $posts = $stmt->fetchAll();

        jsonResponse([
            'success' => true,
            'posts'   => enrichPosts($db, $posts, $uid),
            'page'    => $page,
            'limit'   => $limit,
        ]);
    }

    // ── Create post ─────────────────────────────────────────────────────
    case 'post': {
        $uid   = pulseRequireAuth();
        $input = json_decode(file_get_contents('php://input'), true);

        $content = trim($input['content'] ?? '');
        if ($content === '') {
            jsonResponse(['error' => 'Post content is required'], 400);
        }
        if (mb_strlen($content) > 5000) {
            jsonResponse(['error' => 'Post too long (max 5000 chars)'], 400);
        }

        $postType  = in_array($input['post_type'] ?? 'text', ['text','image','link','game_result','agent_activity'])
                     ? $input['post_type'] : 'text';
        $mediaUrl  = null;
        $linkUrl   = null;
        $linkTitle = null;
        $linkPreview = null;
        $gameData  = null;

        if ($postType === 'image' && !empty($input['media_url'])) {
            $mediaUrl = filter_var($input['media_url'], FILTER_VALIDATE_URL) ? $input['media_url'] : null;
        }
        if ($postType === 'link' && !empty($input['link_url'])) {
            $linkUrl = filter_var($input['link_url'], FILTER_VALIDATE_URL) ? $input['link_url'] : null;
            $linkTitle = sanitize($input['link_title'] ?? '', 255);
            $linkPreview = sanitize($input['link_preview'] ?? '', 500);
        }
        if ($postType === 'game_result' && !empty($input['game_data'])) {
            $gameData = json_encode($input['game_data']);
        }

        $stmt = $db->prepare("
            INSERT INTO pulse_posts (user_id, content, post_type, media_url, link_url, link_title, link_preview, game_data)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$uid, $content, $postType, $mediaUrl, $linkUrl, $linkTitle, $linkPreview, $gameData]);
        $postId = (int)$db->lastInsertId();

        // Fetch the created post
        $stmt = $db->prepare("SELECT * FROM pulse_posts WHERE id = ?");
        $stmt->execute([$postId]);
        $posts = enrichPosts($db, [$stmt->fetch()], $uid);

        jsonResponse(['success' => true, 'post' => $posts[0] ?? null]);
    }

    // ── Delete post ─────────────────────────────────────────────────────
    case 'post-delete': {
        $uid   = pulseRequireAuth();
        $input = json_decode(file_get_contents('php://input'), true);
        $postId = (int)($input['post_id'] ?? 0);
        if (!$postId) jsonResponse(['error' => 'post_id required'], 400);

        $stmt = $db->prepare("DELETE FROM pulse_posts WHERE id = ? AND user_id = ?");
        $stmt->execute([$postId, $uid]);

        if ($stmt->rowCount() === 0) {
            jsonResponse(['error' => 'Post not found or not yours'], 404);
        }

        // Clean up related data
        $db->prepare("DELETE FROM pulse_likes WHERE post_id = ?")->execute([$postId]);
        $db->prepare("DELETE FROM pulse_comments WHERE post_id = ?")->execute([$postId]);
        $db->prepare("DELETE FROM pulse_notifications WHERE post_id = ?")->execute([$postId]);

        jsonResponse(['success' => true]);
    }

    // ── Toggle like ─────────────────────────────────────────────────────
    case 'like': {
        $uid   = pulseRequireAuth();
        $input = json_decode(file_get_contents('php://input'), true);
        $postId = (int)($input['post_id'] ?? 0);
        if (!$postId) jsonResponse(['error' => 'post_id required'], 400);

        // Check if already liked
        $stmt = $db->prepare("SELECT id FROM pulse_likes WHERE post_id = ? AND user_id = ?");
        $stmt->execute([$postId, $uid]);

        if ($stmt->fetch()) {
            // Unlike
            $db->prepare("DELETE FROM pulse_likes WHERE post_id = ? AND user_id = ?")->execute([$postId, $uid]);
            $db->prepare("UPDATE pulse_posts SET like_count = GREATEST(like_count - 1, 0) WHERE id = ?")->execute([$postId]);
            $liked = false;
        } else {
            // Like
            $db->prepare("INSERT INTO pulse_likes (post_id, user_id) VALUES (?, ?)")->execute([$postId, $uid]);
            $db->prepare("UPDATE pulse_posts SET like_count = like_count + 1 WHERE id = ?")->execute([$postId]);
            $liked = true;

            // Notify post author
            $author = $db->prepare("SELECT user_id FROM pulse_posts WHERE id = ?");
            $author->execute([$postId]);
            $row = $author->fetch();
            if ($row) notify($db, (int)$row['user_id'], $uid, 'like', $postId);
        }

        // Return updated count
        $stmt = $db->prepare("SELECT like_count FROM pulse_posts WHERE id = ?");
        $stmt->execute([$postId]);
        $count = (int)($stmt->fetchColumn() ?: 0);

        jsonResponse(['success' => true, 'liked' => $liked, 'like_count' => $count]);
    }

    // ── Add comment ─────────────────────────────────────────────────────
    case 'comment': {
        $uid   = pulseRequireAuth();
        $input = json_decode(file_get_contents('php://input'), true);
        $postId = (int)($input['post_id'] ?? 0);
        $content = trim($input['content'] ?? '');

        if (!$postId) jsonResponse(['error' => 'post_id required'], 400);
        if ($content === '') jsonResponse(['error' => 'Comment cannot be empty'], 400);
        if (mb_strlen($content) > 2000) jsonResponse(['error' => 'Comment too long (max 2000 chars)'], 400);

        $stmt = $db->prepare("INSERT INTO pulse_comments (post_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$postId, $uid, $content]);
        $commentId = (int)$db->lastInsertId();

        $db->prepare("UPDATE pulse_posts SET comment_count = comment_count + 1 WHERE id = ?")->execute([$postId]);

        // Notify post author
        $author = $db->prepare("SELECT user_id FROM pulse_posts WHERE id = ?");
        $author->execute([$postId]);
        $row = $author->fetch();
        if ($row) notify($db, (int)$row['user_id'], $uid, 'comment', $postId);

        $name = getUserName($db, $uid);
        jsonResponse([
            'success' => true,
            'comment' => [
                'id'          => $commentId,
                'user_id'     => $uid,
                'author_name' => $name,
                'initials'    => getInitials($name),
                'content'     => $content,
                'created_at'  => date('Y-m-d H:i:s'),
            ],
        ]);
    }

    // ── Delete comment ──────────────────────────────────────────────────
    case 'comment-delete': {
        $uid   = pulseRequireAuth();
        $input = json_decode(file_get_contents('php://input'), true);
        $commentId = (int)($input['comment_id'] ?? 0);
        if (!$commentId) jsonResponse(['error' => 'comment_id required'], 400);

        // Get comment to update post count
        $stmt = $db->prepare("SELECT post_id FROM pulse_comments WHERE id = ? AND user_id = ?");
        $stmt->execute([$commentId, $uid]);
        $row = $stmt->fetch();
        if (!$row) jsonResponse(['error' => 'Comment not found or not yours'], 404);

        $db->prepare("DELETE FROM pulse_comments WHERE id = ? AND user_id = ?")->execute([$commentId, $uid]);
        $db->prepare("UPDATE pulse_posts SET comment_count = GREATEST(comment_count - 1, 0) WHERE id = ?")->execute([$row['post_id']]);

        jsonResponse(['success' => true]);
    }

    // ── Follow ──────────────────────────────────────────────────────────
    case 'follow': {
        $uid   = pulseRequireAuth();
        $input = json_decode(file_get_contents('php://input'), true);
        $targetId = (int)($input['user_id'] ?? 0);
        if (!$targetId) jsonResponse(['error' => 'user_id required'], 400);
        if ($targetId === $uid) jsonResponse(['error' => 'Cannot follow yourself'], 400);

        // Check target exists
        $stmt = $db->prepare("SELECT id FROM clients WHERE id = ?");
        $stmt->execute([$targetId]);
        if (!$stmt->fetch()) jsonResponse(['error' => 'User not found'], 404);

        $stmt = $db->prepare("INSERT IGNORE INTO pulse_follows (follower_id, following_id) VALUES (?, ?)");
        $stmt->execute([$uid, $targetId]);

        if ($stmt->rowCount() > 0) {
            notify($db, $targetId, $uid, 'follow');
        }

        jsonResponse(['success' => true, 'following' => true]);
    }

    // ── Unfollow ────────────────────────────────────────────────────────
    case 'unfollow': {
        $uid   = pulseRequireAuth();
        $input = json_decode(file_get_contents('php://input'), true);
        $targetId = (int)($input['user_id'] ?? 0);
        if (!$targetId) jsonResponse(['error' => 'user_id required'], 400);

        $db->prepare("DELETE FROM pulse_follows WHERE follower_id = ? AND following_id = ?")->execute([$uid, $targetId]);
        jsonResponse(['success' => true, 'following' => false]);
    }

    // ── Followers list ──────────────────────────────────────────────────
    case 'followers': {
        $targetId = (int)($_GET['user_id'] ?? 0);
        $uid = pulseOptionalAuth();
        if (!$targetId && $uid) $targetId = $uid;
        if (!$targetId) jsonResponse(['error' => 'user_id required'], 400);

        $stmt = $db->prepare("
            SELECT c.id, COALESCE(NULLIF(c.firstname,''), c.email) AS name
            FROM pulse_follows f JOIN clients c ON c.id = f.follower_id
            WHERE f.following_id = ?
            ORDER BY f.created_at DESC LIMIT 100
        ");
        $stmt->execute([$targetId]);
        $followers = $stmt->fetchAll();

        // Check which ones current user follows
        $followingSet = [];
        if ($uid) {
            $ids = array_column($followers, 'id');
            if (!empty($ids)) {
                $ph = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $db->prepare("SELECT following_id FROM pulse_follows WHERE follower_id = ? AND following_id IN ($ph)");
                $stmt->execute(array_merge([$uid], $ids));
                while ($r = $stmt->fetch()) $followingSet[(int)$r['following_id']] = true;
            }
        }

        jsonResponse([
            'success'   => true,
            'followers' => array_map(function($f) use ($followingSet, $uid) {
                return [
                    'id'       => (int)$f['id'],
                    'name'     => $f['name'],
                    'initials' => getInitials($f['name']),
                    'is_following' => isset($followingSet[(int)$f['id']]),
                    'is_self'  => $uid === (int)$f['id'],
                ];
            }, $followers),
        ]);
    }

    // ── Following list ──────────────────────────────────────────────────
    case 'following': {
        $targetId = (int)($_GET['user_id'] ?? 0);
        $uid = pulseOptionalAuth();
        if (!$targetId && $uid) $targetId = $uid;
        if (!$targetId) jsonResponse(['error' => 'user_id required'], 400);

        $stmt = $db->prepare("
            SELECT c.id, COALESCE(NULLIF(c.firstname,''), c.email) AS name
            FROM pulse_follows f JOIN clients c ON c.id = f.following_id
            WHERE f.follower_id = ?
            ORDER BY f.created_at DESC LIMIT 100
        ");
        $stmt->execute([$targetId]);
        $following = $stmt->fetchAll();

        jsonResponse([
            'success'   => true,
            'following' => array_map(function($f) use ($uid) {
                return [
                    'id'       => (int)$f['id'],
                    'name'     => $f['name'],
                    'initials' => getInitials($f['name']),
                    'is_self'  => $uid === (int)$f['id'],
                ];
            }, $following),
        ]);
    }

    // ── Profile ─────────────────────────────────────────────────────────
    case 'profile': {
        $targetId = (int)($_GET['user_id'] ?? 0);
        $uid = pulseOptionalAuth();
        if (!$targetId && $uid) $targetId = $uid;
        if (!$targetId) jsonResponse(['error' => 'user_id required'], 400);

        $stmt = $db->prepare("SELECT id, COALESCE(NULLIF(CONCAT(firstname,' ',lastname),''), COALESCE(NULLIF(firstname,''), email)) AS name, email, date_created AS created_at FROM clients WHERE id = ?");
        $stmt->execute([$targetId]);
        $user = $stmt->fetch();
        if (!$user) jsonResponse(['error' => 'User not found'], 404);

        // Get profile data
        $stmt = $db->prepare("SELECT bio, avatar_url, badge, cover_url, theme_color FROM pulse_profiles WHERE user_id = ?");
        $stmt->execute([$targetId]);
        $profile = $stmt->fetch() ?: [];

        // Counts
        $stmtC = $db->prepare("SELECT COUNT(*) FROM pulse_posts WHERE user_id = ?");
        $stmtC->execute([$targetId]);
        $postCount = (int)$stmtC->fetchColumn();

        $stmtC = $db->prepare("SELECT COUNT(*) FROM pulse_follows WHERE following_id = ?");
        $stmtC->execute([$targetId]);
        $followerCount = (int)$stmtC->fetchColumn();

        $stmtC = $db->prepare("SELECT COUNT(*) FROM pulse_follows WHERE follower_id = ?");
        $stmtC->execute([$targetId]);
        $followingCount = (int)$stmtC->fetchColumn();

        $stmtC = $db->prepare("SELECT SUM(like_count) FROM pulse_posts WHERE user_id = ?");
        $stmtC->execute([$targetId]);
        $totalLikes = (int)$stmtC->fetchColumn();

        // Check if current user follows this profile
        $isFollowing = false;
        if ($uid && $uid !== $targetId) {
            $stmtF = $db->prepare("SELECT 1 FROM pulse_follows WHERE follower_id = ? AND following_id = ?");
            $stmtF->execute([$uid, $targetId]);
            $isFollowing = (bool)$stmtF->fetch();
        }

        $avatarUrl = $profile['avatar_url'] ?? getGravatarUrl($user['email']);

        // Military rank + full service record data
        $military = null;
        $mStmt = $db->prepare("SELECT r.client_id, r.rank_code, r.rank_level, r.display_name, r.status, r.created_at AS enlisted_at, m.rank_name, m.min_xp, m.min_days_active, m.permissions, m.description AS rank_desc FROM alfred_military_roster r JOIN alfred_military_ranks m ON r.rank_code = m.rank_code WHERE r.client_id = ? AND r.status IN ('active','suspended') LIMIT 1");
        $mStmt->execute([$targetId]);
        $mRow = $mStmt->fetch();
        if ($mRow) {
            // Rank color/icon maps
            $rankColors = ['O-6'=>'#d4a017','O-5'=>'#c0392b','O-4'=>'#8e44ad','O-3'=>'#2980b9','O-2'=>'#27ae60','O-1'=>'#16a085','E-4'=>'#f39c12','E-3'=>'#e67e22','E-2'=>'#3498db','E-1'=>'#95a5a6','E-0'=>'#7f8c8d'];
            $rankIcons = ['O-6'=>'fa-crown','O-5'=>'fa-star','O-4'=>'fa-medal','O-3'=>'fa-shield-alt','O-2'=>'fa-anchor','O-1'=>'fa-chevron-up','E-4'=>'fa-chevron-up','E-3'=>'fa-chevron-up','E-2'=>'fa-user','E-1'=>'fa-user','E-0'=>'fa-user'];

            // Days in service
            $enlistDate = new DateTime($mRow['enlisted_at']);
            $daysInService = $enlistDate->diff(new DateTime())->days;

            // Next rank
            $nrStmt = $db->prepare("SELECT rank_code, rank_name, min_xp, min_days_active FROM alfred_military_ranks WHERE rank_level > ? ORDER BY rank_level ASC LIMIT 1");
            $nrStmt->execute([(int)$mRow['rank_level']]);
            $nextRank = $nrStmt->fetch() ?: null;

            // XP summary
            $xpStmt = $db->prepare("SELECT total_xp FROM alfred_user_xp_summary WHERE user_id = ? LIMIT 1");
            $xpStmt->execute([$targetId]);
            $totalXp = (int)($xpStmt->fetchColumn() ?: 0);

            // Achievements
            $achStmt = $db->prepare("SELECT achievement_name, badge_tier, xp_awarded, unlocked_at FROM alfred_achievements WHERE user_id = ? AND unlocked_at IS NOT NULL ORDER BY unlocked_at DESC LIMIT 20");
            $achStmt->execute([$targetId]);
            $achievements = $achStmt->fetchAll(PDO::FETCH_ASSOC);

            // Streaks
            $skStmt = $db->prepare("SELECT streak_type, current_count FROM alfred_streaks WHERE user_id = ? ORDER BY current_count DESC LIMIT 5");
            $skStmt->execute([$targetId]);
            $streaks = $skStmt->fetchAll(PDO::FETCH_ASSOC);

            // Promotions
            $prStmt = $db->prepare("SELECT from_rank, to_rank, reason, created_at FROM alfred_promotion_log WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
            $prStmt->execute([$targetId]);
            $promotions = $prStmt->fetchAll(PDO::FETCH_ASSOC);

            $military = [
                'rank_code'    => $mRow['rank_code'],
                'rank_name'    => $mRow['rank_name'],
                'rank_level'   => (int)$mRow['rank_level'],
                'rank_desc'    => $mRow['rank_desc'],
                'rank_color'   => $rankColors[$mRow['rank_code']] ?? '#95a5a6',
                'rank_icon'    => $rankIcons[$mRow['rank_code']] ?? 'fa-user',
                'status'       => $mRow['status'],
                'enlisted_at'  => $mRow['enlisted_at'],
                'days_in_service' => $daysInService,
                'display_name' => $mRow['display_name'],
                'permissions'  => json_decode($mRow['permissions'] ?? '{}', true) ?: [],
                'total_xp'     => $totalXp,
                'current_min_xp' => (int)$mRow['min_xp'],
                'next_rank'    => $nextRank ? [
                    'rank_code'  => $nextRank['rank_code'],
                    'rank_name'  => $nextRank['rank_name'],
                    'min_xp'     => (int)$nextRank['min_xp'],
                    'min_days'   => (int)$nextRank['min_days_active'],
                    'rank_color' => $rankColors[$nextRank['rank_code']] ?? '#95a5a6',
                ] : null,
                'achievements' => $achievements,
                'streaks'      => $streaks,
                'promotions'   => $promotions,
                'service_record_url' => '/service-record.php?id=' . $targetId,
            ];
        }

        jsonResponse([
            'success' => true,
            'profile' => [
                'id'              => (int)$user['id'],
                'name'            => $user['name'],
                'initials'        => getInitials($user['name']),
                'avatar_url'      => $avatarUrl,
                'bio'             => $profile['bio'] ?? '',
                'badge'           => $profile['badge'] ?? null,
                'cover_url'       => $profile['cover_url'] ?? null,
                'theme_color'     => $profile['theme_color'] ?? '#3b82f6',
                'member_since'    => $user['created_at'],
                'post_count'      => $postCount,
                'follower_count'  => $followerCount,
                'following_count' => $followingCount,
                'total_likes'     => $totalLikes,
                'is_self'         => $uid === $targetId,
                'is_following'    => $isFollowing,
                'military'        => $military,
            ],
        ]);
    }

    // ── Search ──────────────────────────────────────────────────────────
    case 'search': {
        $uid = pulseOptionalAuth();
        $q = trim(sanitize($_GET['q'] ?? '', 200));
        if (mb_strlen($q) < 2) jsonResponse(['error' => 'Query too short (min 2 chars)'], 400);

        $searchTerm = "%{$q}%";

        // Search users
        $stmt = $db->prepare("
            SELECT c.id, 
                   COALESCE(NULLIF(CONCAT(c.firstname,' ',c.lastname),''), COALESCE(NULLIF(c.firstname,''), c.email)) AS name,
                   c.email,
                   (SELECT COUNT(*) FROM pulse_follows WHERE following_id = c.id) AS follower_count
            FROM clients c
            WHERE c.firstname LIKE ? OR c.lastname LIKE ? OR c.email LIKE ?
            LIMIT 10
        ");
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        $searchUsers = $stmt->fetchAll();
        $searchProfiles = getUserProfiles($db, array_column($searchUsers, 'id'));
        $users = array_map(function($u) use ($searchProfiles) {
            $p = $searchProfiles[(int)$u['id']] ?? null;
            return [
                'id'             => (int)$u['id'],
                'name'           => $u['name'],
                'initials'       => getInitials($u['name']),
                'avatar_url'     => $p['avatar_url'] ?? getGravatarUrl($u['email']),
                'bio'            => $p['bio'] ?? '',
                'badge'          => $p['badge'] ?? null,
                'follower_count' => (int)$u['follower_count'],
            ];
        }, $searchUsers);

        // Search posts
        $stmt = $db->prepare("
            SELECT * FROM pulse_posts
            WHERE content LIKE ?
            ORDER BY created_at DESC LIMIT 20
        ");
        $stmt->execute([$searchTerm]);
        $posts = enrichPosts($db, $stmt->fetchAll(), $uid);

        jsonResponse(['success' => true, 'users' => $users, 'posts' => $posts]);
    }

    // ── Trending (most liked in 24h) ────────────────────────────────────
    case 'trending': {
        $uid = pulseOptionalAuth();

        $stmt = $db->prepare("
            SELECT * FROM pulse_posts
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY like_count DESC, comment_count DESC
            LIMIT 20
        ");
        $stmt->execute();
        $posts = enrichPosts($db, $stmt->fetchAll(), $uid);

        jsonResponse(['success' => true, 'posts' => $posts]);
    }

    // ── Notifications ───────────────────────────────────────────────────
    case 'notifications': {
        $uid = pulseRequireAuth();

        $stmt = $db->prepare("
            SELECT n.*, COALESCE(NULLIF(c.firstname,''), c.email) AS actor_name
            FROM pulse_notifications n
            JOIN clients c ON c.id = n.actor_id
            WHERE n.user_id = ?
            ORDER BY n.created_at DESC
            LIMIT 50
        ");
        $stmt->execute([$uid]);
        $notifs = $stmt->fetchAll();

        $unread = 0;
        $result = array_map(function($n) use (&$unread) {
            if (!(int)$n['is_read']) $unread++;
            return [
                'id'         => (int)$n['id'],
                'type'       => $n['type'],
                'actor_id'   => (int)$n['actor_id'],
                'actor_name' => $n['actor_name'],
                'initials'   => getInitials($n['actor_name']),
                'post_id'    => $n['post_id'] ? (int)$n['post_id'] : null,
                'is_read'    => (bool)(int)$n['is_read'],
                'created_at' => $n['created_at'],
            ];
        }, $notifs);

        jsonResponse(['success' => true, 'notifications' => $result, 'unread_count' => $unread]);
    }

    // ── Mark notifications read ─────────────────────────────────────────
    case 'notif-read': {
        $uid = pulseRequireAuth();
        $db->prepare("UPDATE pulse_notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0")->execute([$uid]);
        jsonResponse(['success' => true]);
    }

    // ── Update profile ─────────────────────────────────────────────────
    case 'update-profile': {
        $uid = pulseRequireAuth();
        $input = json_decode(file_get_contents('php://input'), true);

        $bio = sanitize($input['bio'] ?? '', 280);
        $avatarUrl = null;
        if (!empty($input['avatar_url']) && filter_var($input['avatar_url'], FILTER_VALIDATE_URL)) {
            $avatarUrl = $input['avatar_url'];
        }
        $themeColor = preg_match('/^#[0-9a-fA-F]{6}$/', $input['theme_color'] ?? '') ? $input['theme_color'] : '#3b82f6';

        $stmt = $db->prepare("INSERT INTO pulse_profiles (user_id, bio, avatar_url, theme_color) 
            VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE bio = VALUES(bio), avatar_url = VALUES(avatar_url), theme_color = VALUES(theme_color)");
        $stmt->execute([$uid, $bio, $avatarUrl, $themeColor]);

        jsonResponse(['success' => true]);
    }

    // ── Toggle bookmark ─────────────────────────────────────────────────
    case 'bookmark': {
        $uid = pulseRequireAuth();
        $input = json_decode(file_get_contents('php://input'), true);
        $postId = (int)($input['post_id'] ?? 0);
        if (!$postId) jsonResponse(['error' => 'post_id required'], 400);

        $stmt = $db->prepare("SELECT id FROM pulse_bookmarks WHERE user_id = ? AND post_id = ?");
        $stmt->execute([$uid, $postId]);

        if ($stmt->fetch()) {
            $db->prepare("DELETE FROM pulse_bookmarks WHERE user_id = ? AND post_id = ?")->execute([$uid, $postId]);
            $bookmarked = false;
        } else {
            $db->prepare("INSERT INTO pulse_bookmarks (user_id, post_id) VALUES (?, ?)")->execute([$uid, $postId]);
            $bookmarked = true;
        }
        jsonResponse(['success' => true, 'bookmarked' => $bookmarked]);
    }

    // ── Get bookmarked posts ────────────────────────────────────────────
    case 'bookmarks': {
        $uid = pulseRequireAuth();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $stmt = $db->prepare("
            SELECT p.* FROM pulse_posts p
            JOIN pulse_bookmarks b ON b.post_id = p.id
            WHERE b.user_id = ?
            ORDER BY b.created_at DESC
            LIMIT ? OFFSET ?
        ");
        dbExecute($stmt, [$uid, $limit, $offset]);
        $posts = enrichPosts($db, $stmt->fetchAll(), $uid);

        jsonResponse(['success' => true, 'posts' => $posts, 'page' => $page, 'limit' => $limit]);
    }

    // ── Suggested users (people to follow) ──────────────────────────────
    case 'suggested-users': {
        $uid = pulseRequireAuth();

        // Users who have posted, but the current user doesn't follow
        $stmt = $db->prepare("
            SELECT DISTINCT c.id, 
                   COALESCE(NULLIF(CONCAT(c.firstname,' ',c.lastname),''), COALESCE(NULLIF(c.firstname,''), c.email)) AS name,
                   c.email,
                   (SELECT COUNT(*) FROM pulse_posts WHERE user_id = c.id) AS post_count,
                   (SELECT COUNT(*) FROM pulse_follows WHERE following_id = c.id) AS follower_count
            FROM clients c
            JOIN pulse_posts pp ON pp.user_id = c.id
            WHERE c.id != ?
              AND c.id NOT IN (SELECT following_id FROM pulse_follows WHERE follower_id = ?)
            ORDER BY follower_count DESC, post_count DESC
            LIMIT 5
        ");
        $stmt->execute([$uid, $uid]);
        $users = $stmt->fetchAll();

        $profiles = getUserProfiles($db, array_column($users, 'id'));

        jsonResponse([
            'success' => true,
            'users' => array_map(function($u) use ($profiles) {
                $p = $profiles[(int)$u['id']] ?? null;
                return [
                    'id'           => (int)$u['id'],
                    'name'         => $u['name'],
                    'initials'     => getInitials($u['name']),
                    'avatar_url'   => $p['avatar_url'] ?? getGravatarUrl($u['email']),
                    'bio'          => $p['bio'] ?? '',
                    'badge'        => $p['badge'] ?? null,
                    'post_count'   => (int)$u['post_count'],
                    'follower_count' => (int)$u['follower_count'],
                ];
            }, $users),
        ]);
    }

    // ── Hashtag feed ────────────────────────────────────────────────────
    case 'hashtag': {
        $uid = pulseOptionalAuth();
        $tag = sanitize($_GET['tag'] ?? '', 100);
        if (mb_strlen($tag) < 1) jsonResponse(['error' => 'tag required'], 400);

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $stmt = $db->prepare("
            SELECT * FROM pulse_posts
            WHERE content LIKE ?
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ");
        dbExecute($stmt, ["%#{$tag}%", $limit, $offset]);
        $posts = enrichPosts($db, $stmt->fetchAll(), $uid);

        jsonResponse(['success' => true, 'posts' => $posts, 'tag' => $tag, 'page' => $page]);
    }

    // ── Trending hashtags ───────────────────────────────────────────────
    case 'trending-tags': {
        // Extract hashtags from recent posts
        $stmt = $db->query("
            SELECT content FROM pulse_posts 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY created_at DESC
            LIMIT 500
        ");
        $allContent = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $tagCounts = [];
        foreach ($allContent as $content) {
            preg_match_all('/#([a-zA-Z0-9_]+)/', $content, $matches);
            foreach ($matches[1] as $tag) {
                $lower = strtolower($tag);
                $tagCounts[$lower] = ($tagCounts[$lower] ?? 0) + 1;
            }
        }
        arsort($tagCounts);
        $trending = [];
        $i = 0;
        foreach ($tagCounts as $tag => $count) {
            if ($i++ >= 10) break;
            $trending[] = ['tag' => $tag, 'count' => $count];
        }
        jsonResponse(['success' => true, 'tags' => $trending]);
    }

    // ── User posts (for profile view) ───────────────────────────────────
    case 'user-posts': {
        $uid = pulseOptionalAuth();
        $targetId = (int)($_GET['user_id'] ?? 0);
        if (!$targetId) jsonResponse(['error' => 'user_id required'], 400);
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $stmt = $db->prepare("SELECT * FROM pulse_posts WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
        dbExecute($stmt, [$targetId, $limit, $offset]);
        $posts = $stmt->fetchAll();

        jsonResponse([
            'success' => true,
            'posts'   => enrichPosts($db, $posts, $uid),
            'page'    => $page,
            'limit'   => $limit,
        ]);
    }

    // ── Browse profiles (people directory) ──────────────────────────────
    case 'browse-profiles': {
        $uid = pulseOptionalAuth();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;
        $order = in_array($_GET['order'] ?? '', ['newest', 'popular', 'active']) ? $_GET['order'] : 'popular';

        $orderClause = match($order) {
            'newest'  => 'c.date_created DESC',
            'active'  => 'post_count DESC, follower_count DESC',
            default   => 'follower_count DESC, post_count DESC',
        };

        $stmt = $db->prepare("
            SELECT c.id,
                   COALESCE(NULLIF(CONCAT(c.firstname,' ',c.lastname),''), COALESCE(NULLIF(c.firstname,''), c.email)) AS name,
                   c.email,
                   c.date_created,
                   (SELECT COUNT(*) FROM pulse_posts WHERE user_id = c.id) AS post_count,
                   (SELECT COUNT(*) FROM pulse_follows WHERE following_id = c.id) AS follower_count
            FROM clients c
            WHERE (SELECT COUNT(*) FROM pulse_posts WHERE user_id = c.id) > 0
               OR (SELECT COUNT(*) FROM pulse_follows WHERE following_id = c.id) > 0
            ORDER BY {$orderClause}
            LIMIT ? OFFSET ?
        ");
        dbExecute($stmt, [$limit, $offset]);
        $users = $stmt->fetchAll();

        $profiles = getUserProfiles($db, array_column($users, 'id'));

        // Check follow status
        $followMap = [];
        if ($uid) {
            $ids = array_column($users, 'id');
            if ($ids) {
                $ph = implode(',', array_fill(0, count($ids), '?'));
                $fStmt = $db->prepare("SELECT following_id FROM pulse_follows WHERE follower_id = ? AND following_id IN ($ph)");
                $fStmt->execute(array_merge([$uid], $ids));
                foreach ($fStmt->fetchAll(PDO::FETCH_COLUMN) as $fid) {
                    $followMap[(int)$fid] = true;
                }
            }
        }

        jsonResponse([
            'success' => true,
            'users' => array_map(function($u) use ($profiles, $uid, $followMap) {
                $p = $profiles[(int)$u['id']] ?? null;
                return [
                    'id'             => (int)$u['id'],
                    'name'           => $u['name'],
                    'initials'       => getInitials($u['name']),
                    'avatar_url'     => $p['avatar_url'] ?? getGravatarUrl($u['email']),
                    'bio'            => $p['bio'] ?? '',
                    'badge'          => $p['badge'] ?? null,
                    'post_count'     => (int)$u['post_count'],
                    'follower_count' => (int)$u['follower_count'],
                    'member_since'   => $u['date_created'],
                    'is_self'        => $uid === (int)$u['id'],
                    'is_following'   => isset($followMap[(int)$u['id']]),
                ];
            }, $users),
            'page' => $page,
            'order' => $order,
        ]);
    }

    // ── Profile card (lightweight hover card) ───────────────────────────
    case 'profile-card': {
        $targetId = (int)($_GET['user_id'] ?? 0);
        $uid = pulseOptionalAuth();
        if (!$targetId) jsonResponse(['error' => 'user_id required'], 400);

        $stmt = $db->prepare("SELECT id, COALESCE(NULLIF(CONCAT(firstname,' ',lastname),''), COALESCE(NULLIF(firstname,''), email)) AS name, email FROM clients WHERE id = ?");
        $stmt->execute([$targetId]);
        $user = $stmt->fetch();
        if (!$user) jsonResponse(['error' => 'User not found'], 404);

        $stmt = $db->prepare("SELECT bio, avatar_url, badge FROM pulse_profiles WHERE user_id = ?");
        $stmt->execute([$targetId]);
        $profile = $stmt->fetch() ?: [];

        $stmtC = $db->prepare("SELECT COUNT(*) FROM pulse_follows WHERE following_id = ?");
        $stmtC->execute([$targetId]);
        $followerCount = (int)$stmtC->fetchColumn();

        $stmtC = $db->prepare("SELECT COUNT(*) FROM pulse_posts WHERE user_id = ?");
        $stmtC->execute([$targetId]);
        $postCount = (int)$stmtC->fetchColumn();

        $isFollowing = false;
        $mutualCount = 0;
        if ($uid && $uid !== $targetId) {
            $stmtF = $db->prepare("SELECT 1 FROM pulse_follows WHERE follower_id = ? AND following_id = ?");
            $stmtF->execute([$uid, $targetId]);
            $isFollowing = (bool)$stmtF->fetch();

            // Mutual follows
            $stmtM = $db->prepare("
                SELECT COUNT(*) FROM pulse_follows a
                JOIN pulse_follows b ON a.following_id = b.following_id
                WHERE a.follower_id = ? AND b.follower_id = ? AND a.following_id != ? AND a.following_id != ?
            ");
            $stmtM->execute([$uid, $targetId, $uid, $targetId]);
            $mutualCount = (int)$stmtM->fetchColumn();
        }

        jsonResponse([
            'success' => true,
            'card' => [
                'id'             => (int)$user['id'],
                'name'           => $user['name'],
                'initials'       => getInitials($user['name']),
                'avatar_url'     => $profile['avatar_url'] ?? getGravatarUrl($user['email']),
                'bio'            => $profile['bio'] ?? '',
                'badge'          => $profile['badge'] ?? null,
                'follower_count' => $followerCount,
                'post_count'     => $postCount,
                'is_self'        => $uid === $targetId,
                'is_following'   => $isFollowing,
                'mutual_count'   => $mutualCount,
            ],
        ]);
    }

    // ── Groups list ────────────────────────────────────────────────────
    case 'groups': {
        $uid = pulseOptionalAuth();
        $category = sanitize($_GET['category'] ?? '', 30);
        $where = '';
        $params = [];
        if ($category) { $where = " WHERE g.category = ?"; $params[] = $category; }

        $sql = "SELECT g.*, (SELECT COUNT(*) FROM pulse_group_members gm WHERE gm.group_id = g.id) AS member_count_live";
        if ($uid) {
            $sql .= ", (SELECT 1 FROM pulse_group_members gm2 WHERE gm2.group_id = g.id AND gm2.user_id = ?) AS is_member";
            $params = [$uid, ...$params];
        }
        $sql .= " FROM pulse_groups g{$where} ORDER BY g.member_count DESC, g.name ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

        jsonResponse([
            'success' => true,
            'groups' => array_map(function($g) {
                return [
                    'id'           => (int)$g['id'],
                    'name'         => $g['name'],
                    'slug'         => $g['slug'],
                    'description'  => $g['description'],
                    'category'     => $g['category'],
                    'icon'         => $g['icon'],
                    'cover_color'  => $g['cover_color'],
                    'visibility'   => $g['visibility'],
                    'member_count' => (int)($g['member_count_live'] ?? $g['member_count']),
                    'is_member'    => !empty($g['is_member']),
                ];
            }, $groups),
        ]);
    }

    // ── Join/leave group ────────────────────────────────────────────────
    case 'group-join': {
        $uid = pulseRequireAuth();
        $input = json_decode(file_get_contents('php://input'), true);
        $groupId = (int)($input['group_id'] ?? 0);
        if (!$groupId) jsonResponse(['error' => 'group_id required'], 400);

        $check = $db->prepare("SELECT id FROM pulse_group_members WHERE group_id = ? AND user_id = ?");
        $check->execute([$groupId, $uid]);
        if ($check->fetch()) {
            // Leave
            $db->prepare("DELETE FROM pulse_group_members WHERE group_id = ? AND user_id = ?")->execute([$groupId, $uid]);
            $db->prepare("UPDATE pulse_groups SET member_count = GREATEST(0, member_count - 1) WHERE id = ?")->execute([$groupId]);
            jsonResponse(['success' => true, 'joined' => false]);
        } else {
            // Join
            $db->prepare("INSERT INTO pulse_group_members (group_id, user_id, role, joined_at) VALUES (?, ?, 'member', NOW())")->execute([$groupId, $uid]);
            $db->prepare("UPDATE pulse_groups SET member_count = member_count + 1 WHERE id = ?")->execute([$groupId]);
            jsonResponse(['success' => true, 'joined' => true]);
        }
    }

    // ── Ecosystem crosspost (internal only) ─────────────────────────────
    case 'ecosystem-crosspost': {
        // Only allow internal calls (same server with INTERNAL_SECRET)
        $secret = $_SERVER['HTTP_X_INTERNAL_SECRET'] ?? '';
        $expected = getenv('INTERNAL_SECRET') ?: '';
        if (!$expected || !$secret || !hash_equals($expected, $secret)) {
            jsonResponse(['error' => 'Unauthorized'], 403);
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $eventType = $input['event_type'] ?? '';
        $userId = (int)($input['user_id'] ?? 0);

        if (!$userId) jsonResponse(['error' => 'user_id required'], 400);

        $content = '';
        $postType = 'text';
        $gameData = null;

        switch ($eventType) {
            case 'game_result':
                $postType = 'game_result';
                $g = $input['game_data'] ?? [];
                $result = $g['result'] ?? 'played';
                $gameType = $g['game_type'] ?? 'game';
                $opponent = $g['opponent'] ?? 'opponent';
                $content = ucfirst($result) . ' a ' . $gameType . ' match against ' . $opponent . '!';
                $gameData = json_encode($g);
                break;
            case 'promotion':
                $from = $input['from_rank'] ?? '';
                $to = $input['to_rank'] ?? '';
                $content = "Promoted from {$from} to {$to}! Moving up the ranks.";
                break;
            case 'achievement':
                $achName = $input['achievement_name'] ?? 'an achievement';
                $xp = (int)($input['xp_awarded'] ?? 0);
                $content = "Unlocked achievement: {$achName}" . ($xp ? " (+{$xp} XP)" : '') . '!';
                break;
            default:
                jsonResponse(['error' => 'Unknown event_type. Valid: game_result, promotion, achievement'], 400);
        }

        $stmt = $db->prepare("INSERT INTO pulse_posts (user_id, content, post_type, game_data) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $content, $postType, $gameData]);
        jsonResponse(['success' => true, 'post_id' => (int)$db->lastInsertId()]);
    }

    // ── Agent social passthrough — unified front door ───────────────
    case 'agent-feed':
    case 'agent-profile':
    case 'agent-search':
    case 'agent-trending':
    case 'agent-activity':
    case 'agent-stats':
    case 'agent-badges':
    case 'ecosystem-stats': {
        // Map action names to social-feed.php action names
        $agentActionMap = [
            'agent-feed'     => 'agent-feed',
            'agent-profile'  => 'agent-profile',
            'agent-search'   => 'agent-search',
            'agent-trending' => 'trending',
            'agent-activity' => 'activity',
            'agent-stats'    => 'stats',
            'agent-badges'   => 'badges',
            'ecosystem-stats'=> 'ecosystem-stats',
        ];
        $sfAction = $agentActionMap[$action] ?? $action;

        // Forward by directly including social-feed.php with rewritten action
        $_GET['action'] = $sfAction;
        // social-feed.php's define/require_once are safe to re-run
        @include __DIR__ . '/social-feed.php';
        exit;
    }

    // ── Network overview — combined human + agent stats ─────────────
    case 'network-overview': {
        // Human network stats
        $humanPosts   = (int)$db->query("SELECT COUNT(*) FROM pulse_posts")->fetchColumn();
        $humanUsers   = (int)$db->query("SELECT COUNT(*) FROM pulse_profiles")->fetchColumn();
        $humanFollows = (int)$db->query("SELECT COUNT(*) FROM pulse_follows")->fetchColumn();
        $humanLikes   = (int)$db->query("SELECT COUNT(*) FROM pulse_likes")->fetchColumn();
        $humanGroups  = (int)$db->query("SELECT COUNT(*) FROM pulse_groups")->fetchColumn();

        // Agent network stats — full registry is the real population
        $agentRegistry= (int)$db->query("SELECT COUNT(*) FROM alfred_agent_registry")->fetchColumn();
        $agentSocial  = (int)$db->query("SELECT COUNT(*) FROM agent_profiles WHERE status='active'")->fetchColumn();
        $agentPosts   = (int)$db->query("SELECT COUNT(*) FROM agent_social_posts")->fetchColumn();
        $agentFollows = (int)$db->query("SELECT COUNT(*) FROM agent_social_follows")->fetchColumn();
        $agentDMs     = (int)$db->query("SELECT COUNT(*) FROM agent_direct_messages")->fetchColumn();
        $agentBadges  = (int)$db->query("SELECT COUNT(*) FROM agent_badges")->fetchColumn();
        $agentDomains = (int)$db->query("SELECT COUNT(DISTINCT domain) FROM alfred_agent_registry")->fetchColumn();

        // Fleet passports
        $fleetTotal   = (int)$db->query("SELECT COUNT(*) FROM fleet_passports")->fetchColumn();

        // Games
        $gameResults  = (int)$db->query("SELECT COUNT(*) FROM game_results")->fetchColumn();

        // Military
        $rosterCount  = (int)$db->query("SELECT COUNT(*) FROM alfred_military_roster WHERE status='active'")->fetchColumn();
        $unitCount    = (int)$db->query("SELECT COUNT(*) FROM military_units")->fetchColumn();

        // Metaverse
        $vrSessions   = (int)$db->query("SELECT COUNT(*) FROM agent_metaverse_sessions")->fetchColumn();
        $vrCreations  = (int)$db->query("SELECT COUNT(*) FROM agent_metaverse_creations")->fetchColumn();

        jsonResponse([
            'success' => true,
            'human_network'  => ['users' => $humanUsers, 'posts' => $humanPosts, 'follows' => $humanFollows, 'likes' => $humanLikes, 'groups' => $humanGroups],
            'agent_network'  => ['total_agents' => $agentRegistry, 'social_agents' => $agentSocial, 'domains' => $agentDomains, 'posts' => $agentPosts, 'follows' => $agentFollows, 'dms' => $agentDMs, 'badges' => $agentBadges],
            'fleet'          => ['passports' => $fleetTotal],
            'games'          => ['results' => $gameResults],
            'military'       => ['roster' => $rosterCount, 'units' => $unitCount],
            'metaverse'      => ['sessions' => $vrSessions, 'creations' => $vrCreations],
            'total_posts'    => $humanPosts + $agentPosts,
            'total_agents'   => $agentRegistry,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════
    // ALFRED COMMAND — VR C&C Game API
    // ═══════════════════════════════════════════════════════════════════

    // ── Game State — full state for current player ────────────────────
    case 'game-state': {
        $clientId = pulseRequireAuth();
        $db = getSharedDB();

        // Player stats
        $stats = $db->prepare("SELECT * FROM ac_player_stats WHERE client_id = ?");
        $stats->execute([$clientId]);
        $playerStats = $stats->fetch(PDO::FETCH_ASSOC) ?: ['total_xp' => 0, 'missions_completed' => 0, 'territories_captured' => 0];

        // Player resources
        $res = $db->prepare("SELECT resource_type, quantity FROM ac_player_resources WHERE client_id = ?");
        $res->execute([$clientId]);
        $resources = [];
        while ($r = $res->fetch(PDO::FETCH_ASSOC)) $resources[$r['resource_type']] = (float)$r['quantity'];

        // Active missions
        $mis = $db->prepare("SELECT mission_id, mission_type, title, progress, status, xp_reward, agents_deployed, started_at FROM ac_active_missions WHERE client_id = ? AND status IN ('briefing','active') ORDER BY started_at DESC LIMIT 10");
        $mis->execute([$clientId]);
        $activeMissions = $mis->fetchAll(PDO::FETCH_ASSOC);

        // Active deployments
        $dep = $db->prepare("SELECT d.zone_id, z.zone_name, d.domain, d.agent_count, d.role, d.status, d.deployed_at FROM ac_agent_deployments d JOIN territory_zones z ON z.id = d.zone_id WHERE d.client_id = ? AND d.recalled_at IS NULL ORDER BY d.deployed_at DESC");
        $dep->execute([$clientId]);
        $deployments = $dep->fetchAll(PDO::FETCH_ASSOC);

        // Territories controlled
        $ctrl = $db->prepare("SELECT tc.territory_id, t.territory_code, t.territory_name, t.zone_type, t.passive_xp_rate, tc.defense_strength, tc.captured_at FROM territory_control tc JOIN territories t ON t.id = tc.territory_id WHERE tc.controlling_client_id = ?");
        $ctrl->execute([$clientId]);
        $controlled = $ctrl->fetchAll(PDO::FETCH_ASSOC);

        // Structures
        $str = $db->prepare("SELECT id, zone_id, structure_type, structure_name, level, health, capacity, production_rate FROM ac_structures WHERE client_id = ?");
        $str->execute([$clientId]);
        $structures = $str->fetchAll(PDO::FETCH_ASSOC);

        // Military rank
        $rank = $db->prepare("SELECT r.rank_code, r.rank_level, r.display_name, mk.rank_name, mk.min_xp FROM alfred_military_roster r JOIN alfred_military_ranks mk ON mk.rank_code = r.rank_code WHERE r.client_id = ? LIMIT 1");
        $rank->execute([$clientId]);
        $rankInfo = $rank->fetch(PDO::FETCH_ASSOC) ?: ['rank_code' => 'E-1', 'rank_name' => 'Recruit', 'rank_level' => 1];

        // Active game session
        $sess = $db->prepare("SELECT session_id, session_type, chapter, vr_mode, started_at, xp_earned FROM ac_game_sessions WHERE client_id = ? AND ended_at IS NULL ORDER BY started_at DESC LIMIT 1");
        $sess->execute([$clientId]);
        $session = $sess->fetch(PDO::FETCH_ASSOC);

        jsonResponse([
            'success' => true,
            'player' => [
                'client_id' => $clientId,
                'rank' => $rankInfo,
                'stats' => $playerStats,
                'resources' => $resources,
            ],
            'session' => $session,
            'active_missions' => $activeMissions,
            'deployments' => $deployments,
            'territories_controlled' => $controlled,
            'structures' => $structures,
        ]);
    }

    // ── Territory Status — all territories + zones + control ──────────
    case 'territory-status': {
        $clientId = pulseOptionalAuth();
        $db = getSharedDB();

        $territories = $db->query("SELECT t.*, tc.controlling_client_id, tc.defense_strength, tc.captured_at FROM territories t LEFT JOIN territory_control tc ON tc.territory_id = t.id WHERE t.is_active = 1 ORDER BY t.id")->fetchAll(PDO::FETCH_ASSOC);
        $zones = $db->query("SELECT z.*, (SELECT SUM(d.agent_count) FROM ac_agent_deployments d WHERE d.zone_id = z.id AND d.recalled_at IS NULL) as deployed_agents FROM territory_zones z WHERE z.is_active = 1 ORDER BY z.id")->fetchAll(PDO::FETCH_ASSOC);
        $resources = $db->query("SELECT tr.*, tz.zone_name FROM territory_resources tr JOIN territory_zones tz ON tz.id = tr.zone_id")->fetchAll(PDO::FETCH_ASSOC);

        jsonResponse([
            'success' => true,
            'territories' => $territories,
            'zones' => $zones,
            'resources' => $resources,
        ]);
    }

    // ── Start Session — begin a VR game session ──────────────────────
    case 'start-session': {
        $clientId = pulseRequireAuth();
        $db = getSharedDB();
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

        $sessionId = sprintf('%s-%s-%s', 'AC', $clientId, bin2hex(random_bytes(8)));
        $type = in_array($input['session_type'] ?? '', ['campaign','skirmish','persistent','humanitarian','sandbox']) ? $input['session_type'] : 'persistent';
        $vrMode = in_array($input['vr_mode'] ?? '', ['immersive','desktop','mobile']) ? $input['vr_mode'] : 'desktop';
        $device = isset($input['device']) ? substr($input['device'], 0, 100) : 'unknown';

        $stmt = $db->prepare("INSERT INTO ac_game_sessions (session_id, client_id, session_type, vr_mode, device) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$sessionId, $clientId, $type, $vrMode, $device]);

        // Ensure player stats row exists
        $db->prepare("INSERT IGNORE INTO ac_player_stats (client_id) VALUES (?)")->execute([$clientId]);

        jsonResponse(['success' => true, 'session_id' => $sessionId, 'session_type' => $type, 'vr_mode' => $vrMode]);
    }

    // ── End Session — close a VR game session ────────────────────────
    case 'end-session': {
        $clientId = pulseRequireAuth();
        $db = getSharedDB();
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $sessionId = $input['session_id'] ?? '';

        $stmt = $db->prepare("UPDATE ac_game_sessions SET ended_at = NOW(), duration_sec = TIMESTAMPDIFF(SECOND, started_at, NOW()) WHERE session_id = ? AND client_id = ? AND ended_at IS NULL");
        $stmt->execute([$sessionId, $clientId]);

        // Update player playtime
        $db->prepare("UPDATE ac_player_stats SET play_time_sec = play_time_sec + (SELECT IFNULL(duration_sec,0) FROM ac_game_sessions WHERE session_id = ? LIMIT 1) WHERE client_id = ?")->execute([$sessionId, $clientId]);

        jsonResponse(['success' => true, 'ended' => $sessionId]);
    }

    // ── Mission List — available missions ────────────────────────────
    case 'mission-list': {
        $clientId = pulseOptionalAuth();
        $db = getSharedDB();
        $type = $_GET['type'] ?? null;

        $sql = "SELECT * FROM ac_mission_templates WHERE is_active = 1";
        $params = [];
        if ($type && in_array($type, ['combat','military_police','humanitarian','intel','cadastre','logistics','morale','theory'])) {
            $sql .= " AND mission_type = ?";
            $params[] = $type;
        }
        $sql .= " ORDER BY mission_type, difficulty, template_code";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        jsonResponse(['success' => true, 'missions' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    // ── Start Mission — begin a specific mission ─────────────────────
    case 'start-mission': {
        $clientId = pulseRequireAuth();
        $db = getSharedDB();
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

        $templateCode = $input['template_code'] ?? '';
        $zoneId = (int)($input['zone_id'] ?? 0);
        $sessionId = $input['session_id'] ?? null;

        // Get template
        $tmpl = $db->prepare("SELECT * FROM ac_mission_templates WHERE template_code = ? AND is_active = 1");
        $tmpl->execute([$templateCode]);
        $template = $tmpl->fetch(PDO::FETCH_ASSOC);
        if (!$template) jsonResponse(['error' => 'Invalid mission template'], 400);

        // Check rank requirement
        $rank = $db->prepare("SELECT rank_level FROM alfred_military_roster WHERE client_id = ?");
        $rank->execute([$clientId]);
        $playerRank = (int)($rank->fetchColumn() ?: 1);
        if ($playerRank < (int)$template['min_rank']) jsonResponse(['error' => 'Insufficient rank', 'required' => $template['min_rank'], 'current' => $playerRank], 403);

        // Generate mission
        $missionId = sprintf('M-%s-%s-%s', $template['mission_type'], $clientId, bin2hex(random_bytes(6)));
        $xpReward = rand((int)$template['xp_min'], (int)$template['xp_max']);
        $objectives = $template['objectives_tmpl'];

        $zone = null;
        if ($zoneId > 0) {
            $z = $db->prepare("SELECT id FROM territory_zones WHERE id = ?");
            $z->execute([$zoneId]);
            $zone = $z->fetchColumn() ?: null;
        }

        $stmt = $db->prepare("INSERT INTO ac_active_missions (mission_id, client_id, session_id, mission_type, mission_code, title, description, difficulty, zone_id, objectives, status, xp_reward) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'briefing', ?)");
        $stmt->execute([
            $missionId, $clientId, $sessionId,
            $template['mission_type'], $template['template_code'],
            $template['title'], $template['description'],
            $template['difficulty'], $zone,
            $objectives, $xpReward
        ]);

        jsonResponse(['success' => true, 'mission_id' => $missionId, 'title' => $template['title'], 'type' => $template['mission_type'], 'xp_reward' => $xpReward, 'objectives' => json_decode($objectives, true)]);
    }

    // ── Mission Status / Update ──────────────────────────────────────
    case 'mission-status': {
        $clientId = pulseRequireAuth();
        $db = getSharedDB();
        $missionId = $_GET['mission_id'] ?? ($_POST['mission_id'] ?? '');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            $progress = min(100, max(0, (int)($input['progress'] ?? 0)));
            $newStatus = in_array($input['status'] ?? '', ['active','completed','failed','abandoned']) ? $input['status'] : null;

            $updates = ['progress = ?'];
            $params = [$progress];

            if ($newStatus) {
                $updates[] = 'status = ?';
                $params[] = $newStatus;
                if ($newStatus === 'completed') {
                    $updates[] = 'completed_at = NOW()';
                }
            }

            $params[] = $missionId;
            $params[] = $clientId;
            $db->prepare("UPDATE ac_active_missions SET " . implode(', ', $updates) . " WHERE mission_id = ? AND client_id = ?")->execute($params);

            // If completed, award XP and update stats
            if ($newStatus === 'completed') {
                $m = $db->prepare("SELECT xp_reward, mission_type FROM ac_active_missions WHERE mission_id = ?");
                $m->execute([$missionId]);
                $mission = $m->fetch(PDO::FETCH_ASSOC);
                if ($mission) {
                    $xp = (int)$mission['xp_reward'];
                    $mType = $mission['mission_type'];
                    $db->prepare("UPDATE ac_player_stats SET total_xp = total_xp + ?, missions_completed = missions_completed + 1" .
                        ($mType === 'humanitarian' ? ", humanitarian_missions = humanitarian_missions + 1" : "") .
                        ($mType === 'intel' ? ", recon_missions = recon_missions + 1" : "") .
                        " WHERE client_id = ?")->execute([$xp, $clientId]);

                    // Update session XP
                    $db->prepare("UPDATE ac_game_sessions SET xp_earned = xp_earned + ? WHERE client_id = ? AND ended_at IS NULL")->execute([$xp, $clientId]);

                    // Crosspost to Pulse
                    $db->prepare("INSERT INTO pulse_posts (user_id, content, post_type, game_data) VALUES (?, ?, 'game_result', ?)")->execute([
                        $clientId,
                        "🎖️ Mission Complete: {$mission['mission_type']} — earned {$xp} XP",
                        json_encode(['mission_id' => $missionId, 'type' => $mType, 'xp' => $xp])
                    ]);
                }
            } elseif ($newStatus === 'failed') {
                $db->prepare("UPDATE ac_player_stats SET missions_failed = missions_failed + 1 WHERE client_id = ?")->execute([$clientId]);
            }
        }

        $stmt = $db->prepare("SELECT * FROM ac_active_missions WHERE mission_id = ? AND client_id = ?");
        $stmt->execute([$missionId, $clientId]);
        $mission = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$mission) jsonResponse(['error' => 'Mission not found'], 404);

        $mission['objectives'] = json_decode($mission['objectives'] ?? '[]', true);
        $mission['resource_reward'] = json_decode($mission['resource_reward'] ?? '{}', true);
        jsonResponse(['success' => true, 'mission' => $mission]);
    }

    // ── Deploy Agents — assign agents to a zone ──────────────────────
    case 'deploy-agents': {
        $clientId = pulseRequireAuth();
        $db = getSharedDB();
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

        $zoneId = (int)($input['zone_id'] ?? 0);
        $domain = $input['domain'] ?? 'general';
        $count = max(1, min(100000, (int)($input['count'] ?? 100)));
        $role = in_array($input['role'] ?? '', ['assault','defend','recon','support','medical','logistics','engineering']) ? $input['role'] : 'assault';
        $missionId = $input['mission_id'] ?? null;

        // Validate zone
        $zone = $db->prepare("SELECT id, zone_name, capture_difficulty FROM territory_zones WHERE id = ? AND is_active = 1");
        $zone->execute([$zoneId]);
        $zoneData = $zone->fetch(PDO::FETCH_ASSOC);
        if (!$zoneData) jsonResponse(['error' => 'Invalid zone'], 400);

        // Validate domain exists in registry
        $domainCheck = $db->prepare("SELECT COUNT(*) FROM alfred_agent_registry WHERE domain = ? LIMIT 1");
        $domainCheck->execute([$domain]);
        if (!(int)$domainCheck->fetchColumn()) {
            // Try partial match (domain names may differ)
            $allDomain = $db->query("SELECT DISTINCT domain FROM alfred_agent_registry LIMIT 5")->fetchAll(PDO::FETCH_COLUMN);
            jsonResponse(['error' => 'Unknown domain', 'available_sample' => $allDomain], 400);
        }

        // Check available agents in domain
        $available = $db->prepare("SELECT COUNT(*) FROM alfred_agent_registry WHERE domain = ? AND status = 'idle'");
        $available->execute([$domain]);
        $avail = (int)$available->fetchColumn();
        if ($count > $avail) $count = $avail;

        // Deploy
        $stmt = $db->prepare("INSERT INTO ac_agent_deployments (client_id, zone_id, mission_id, domain, agent_count, role) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$clientId, $zoneId, $missionId, $domain, $count, $role]);
        $deployId = $db->lastInsertId();

        // Update player stats
        $db->prepare("UPDATE ac_player_stats SET agents_deployed = agents_deployed + ? WHERE client_id = ?")->execute([$count, $clientId]);

        // Battle log
        $db->prepare("INSERT INTO ac_battle_log (event_type, zone_id, client_id, agent_domain, agent_count, detail) VALUES ('deploy', ?, ?, ?, ?, ?)")->execute([
            $zoneId, $clientId, $domain, $count,
            "Deployed $count $domain agents as $role to {$zoneData['zone_name']}"
        ]);

        jsonResponse([
            'success' => true,
            'deployment_id' => (int)$deployId,
            'zone' => $zoneData['zone_name'],
            'domain' => $domain,
            'count' => $count,
            'role' => $role,
            'available_remaining' => $avail - $count,
        ]);
    }

    // ── Recall Agents — bring agents back from zone ──────────────────
    case 'recall-agents': {
        $clientId = pulseRequireAuth();
        $db = getSharedDB();
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $deploymentId = (int)($input['deployment_id'] ?? 0);

        $stmt = $db->prepare("UPDATE ac_agent_deployments SET status = 'returning', recalled_at = NOW() WHERE id = ? AND client_id = ? AND recalled_at IS NULL");
        $stmt->execute([$deploymentId, $clientId]);
        jsonResponse(['success' => true, 'recalled' => $stmt->rowCount() > 0]);
    }

    // ── Capture Zone — attempt to capture a territory zone ───────────
    case 'capture-zone': {
        $clientId = pulseRequireAuth();
        $db = getSharedDB();
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $zoneId = (int)($input['zone_id'] ?? 0);

        // Get zone info
        $zone = $db->prepare("SELECT * FROM territory_zones WHERE id = ? AND is_active = 1");
        $zone->execute([$zoneId]);
        $zoneData = $zone->fetch(PDO::FETCH_ASSOC);
        if (!$zoneData) jsonResponse(['error' => 'Invalid zone'], 400);

        // Count deployed agents in this zone by this player
        $myForce = $db->prepare("SELECT SUM(agent_count) FROM ac_agent_deployments WHERE zone_id = ? AND client_id = ? AND recalled_at IS NULL AND status = 'active'");
        $myForce->execute([$zoneId, $clientId]);
        $force = (int)$myForce->fetchColumn();

        $difficulty = (int)$zoneData['capture_difficulty'];
        $required = $difficulty * 10; // Need 10 agents per difficulty point

        if ($force < $required) {
            jsonResponse(['error' => 'Insufficient forces', 'deployed' => $force, 'required' => $required, 'difficulty' => $difficulty], 400);
        }

        // Find which territory this zone maps to
        $terrId = null;
        $terrMatch = $db->prepare("SELECT id FROM territories WHERE zone_type = ? LIMIT 1");
        $terrMatch->execute([$zoneData['zone_type']]);
        $terrId = (int)$terrMatch->fetchColumn();

        // Battle!
        $attackPower = $force;
        $defensePower = $difficulty * 5 + rand(0, $difficulty * 2);
        $result = $attackPower > $defensePower ? 'attacker_win' : ($attackPower === $defensePower ? 'draw' : 'defender_win');

        $xpAwarded = $result === 'attacker_win' ? (int)($zoneData['xp_per_hour'] * 100) : (int)($zoneData['xp_per_hour'] * 20);

        // Log battle
        if ($terrId) {
            $db->prepare("INSERT INTO territory_battles (territory_id, attacker_client_id, result, attacker_score, defender_score, xp_awarded_attacker, battle_log) VALUES (?, ?, ?, ?, ?, ?, ?)")->execute([
                $terrId, $clientId, $result, $attackPower, $defensePower, $xpAwarded,
                json_encode(['zone' => $zoneData['zone_name'], 'force' => $force, 'difficulty' => $difficulty])
            ]);
        }

        // If won, establish control
        if ($result === 'attacker_win' && $terrId) {
            $db->prepare("INSERT INTO territory_control (territory_id, controlling_client_id, defense_strength, last_battle_at) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE controlling_client_id = VALUES(controlling_client_id), defense_strength = VALUES(defense_strength), last_battle_at = NOW()")->execute([$terrId, $clientId, $force]);

            $db->prepare("UPDATE ac_player_stats SET territories_captured = territories_captured + 1, battles_won = battles_won + 1, total_xp = total_xp + ? WHERE client_id = ?")->execute([$xpAwarded, $clientId]);

            // Battle log
            $db->prepare("INSERT INTO ac_battle_log (event_type, zone_id, client_id, agent_count, detail, xp_awarded) VALUES ('capture', ?, ?, ?, ?, ?)")->execute([$zoneId, $clientId, $force, "Zone captured: {$zoneData['zone_name']}", $xpAwarded]);

            // Crosspost victory
            $db->prepare("INSERT INTO pulse_posts (user_id, content, post_type, game_data) VALUES (?, ?, 'game_result', ?)")->execute([
                $clientId,
                "⚔️ Territory Captured: {$zoneData['zone_name']} — {$xpAwarded} XP earned with {$force} agents!",
                json_encode(['zone' => $zoneData['zone_name'], 'xp' => $xpAwarded, 'force' => $force])
            ]);
        } else {
            $db->prepare("UPDATE ac_player_stats SET battles_lost = battles_lost + 1, total_xp = total_xp + ? WHERE client_id = ?")->execute([$xpAwarded, $clientId]);
        }

        jsonResponse([
            'success' => true,
            'result' => $result,
            'zone' => $zoneData['zone_name'],
            'attack_power' => $attackPower,
            'defense_power' => $defensePower,
            'xp_awarded' => $xpAwarded,
            'captured' => $result === 'attacker_win',
        ]);
    }

    // ── Supply Transfer — move resources between player/zones ────────
    case 'supply-transfer': {
        $clientId = pulseRequireAuth();
        $db = getSharedDB();
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

        $resourceType = $input['resource_type'] ?? '';
        $quantity = max(1, (float)($input['quantity'] ?? 0));
        $direction = $input['direction'] ?? 'withdraw'; // 'withdraw' from zone, 'deposit' to zone
        $zoneId = (int)($input['zone_id'] ?? 0);

        $validTypes = ['credits','rations','medical','construction','intel','fuel','ammo','comms','water','seeds'];
        if (!in_array($resourceType, $validTypes)) jsonResponse(['error' => 'Invalid resource type', 'valid' => $validTypes], 400);

        if ($direction === 'deposit') {
            // Check player has enough
            $bal = $db->prepare("SELECT quantity FROM ac_player_resources WHERE client_id = ? AND resource_type = ?");
            $bal->execute([$clientId, $resourceType]);
            $current = (float)($bal->fetchColumn() ?: 0);
            if ($current < $quantity) jsonResponse(['error' => 'Insufficient resources', 'have' => $current, 'need' => $quantity], 400);

            $db->prepare("UPDATE ac_player_resources SET quantity = quantity - ? WHERE client_id = ? AND resource_type = ?")->execute([$quantity, $clientId, $resourceType]);
        } else {
            // Add to player
            $db->prepare("INSERT INTO ac_player_resources (client_id, resource_type, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + ?")->execute([$clientId, $resourceType, $quantity, $quantity]);
        }

        // Log
        $db->prepare("UPDATE ac_player_stats SET resources_gathered = resources_gathered + ? WHERE client_id = ?")->execute([(int)$quantity, $clientId]);

        jsonResponse(['success' => true, 'resource' => $resourceType, 'quantity' => $quantity, 'direction' => $direction]);
    }

    // ── Build Structure — build on a VR plot or zone ─────────────────
    case 'build-structure': {
        $clientId = pulseRequireAuth();
        $db = getSharedDB();
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

        $zoneId = (int)($input['zone_id'] ?? 0);
        $plotId = $input['plot_id'] ?? null;
        $type = $input['structure_type'] ?? '';
        $name = substr($input['name'] ?? '', 0, 100);

        $validTypes = ['fob','greenhouse','medical','comms_tower','supply_depot','safe_zone','watchtower','training','vault','sanctuary'];
        if (!in_array($type, $validTypes)) jsonResponse(['error' => 'Invalid structure type', 'valid' => $validTypes], 400);

        // Cost check (10 construction per level)
        $cost = 10;
        $bal = $db->prepare("SELECT quantity FROM ac_player_resources WHERE client_id = ? AND resource_type = 'construction'");
        $bal->execute([$clientId]);
        $have = (float)($bal->fetchColumn() ?: 0);
        if ($have < $cost) jsonResponse(['error' => 'Need construction materials', 'have' => $have, 'cost' => $cost], 400);

        $db->prepare("UPDATE ac_player_resources SET quantity = quantity - ? WHERE client_id = ? AND resource_type = 'construction'")->execute([$cost, $clientId]);

        // Production rates by type
        $rates = ['greenhouse' => 5.0, 'supply_depot' => 3.0, 'medical' => 2.0, 'comms_tower' => 1.0, 'training' => 1.5, 'safe_zone' => 0, 'watchtower' => 0.5, 'fob' => 0, 'vault' => 0, 'sanctuary' => 0];

        $stmt = $db->prepare("INSERT INTO ac_structures (client_id, plot_id, zone_id, structure_type, structure_name, production_rate) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$clientId, $plotId, $zoneId ?: null, $type, $name ?: ucfirst(str_replace('_',' ',$type)), $rates[$type] ?? 0]);

        $db->prepare("UPDATE ac_player_stats SET structures_built = structures_built + 1 WHERE client_id = ?")->execute([$clientId]);

        jsonResponse(['success' => true, 'structure_id' => (int)$db->lastInsertId(), 'type' => $type, 'name' => $name]);
    }

    // ── War Games List — available war games ─────────────────────────
    case 'war-games': {
        $db = getSharedDB();
        $games = $db->query("SELECT * FROM war_games ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
        jsonResponse(['success' => true, 'games' => $games]);
    }

    // ── Agent Domains — list all 137 domains with counts ─────────────
    case 'agent-domains': {
        $db = getSharedDB();
        $domains = $db->query("SELECT domain, COUNT(*) as agent_count FROM alfred_agent_registry GROUP BY domain ORDER BY domain")->fetchAll(PDO::FETCH_ASSOC);
        jsonResponse(['success' => true, 'total_domains' => count($domains), 'domains' => $domains]);
    }

    // ── Battle Log — recent battle events ────────────────────────────
    case 'battle-log': {
        $clientId = pulseOptionalAuth();
        $db = getSharedDB();
        $limit = min(50, max(10, (int)($_GET['limit'] ?? 20)));

        $sql = "SELECT * FROM ac_battle_log";
        $params = [];
        if ($clientId) {
            $sql .= " WHERE client_id = ?";
            $params[] = $clientId;
        }
        $sql .= " ORDER BY occurred_at DESC LIMIT $limit";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        jsonResponse(['success' => true, 'log' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    // ── Leaderboard — top players ────────────────────────────────────
    case 'leaderboard': {
        $db = getSharedDB();
        $metric = $_GET['metric'] ?? 'total_xp';
        $validMetrics = ['total_xp','missions_completed','territories_captured','battles_won','humanitarian_missions','agents_deployed'];
        if (!in_array($metric, $validMetrics)) $metric = 'total_xp';

        $stmt = $db->query("SELECT ps.client_id, ps.$metric as score, ps.total_xp, ps.missions_completed, ps.territories_captured, r.display_name, r.rank_code FROM ac_player_stats ps LEFT JOIN alfred_military_roster r ON r.client_id = ps.client_id ORDER BY ps.$metric DESC LIMIT 20");
        jsonResponse(['success' => true, 'metric' => $metric, 'leaderboard' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    default:
        jsonResponse(['error' => 'Unknown action', 'actions' => [
            'feed', 'global', 'post', 'post-delete', 'like', 'comment', 'comment-delete',
            'follow', 'unfollow', 'followers', 'following', 'profile', 'update-profile',
            'user-posts', 'search', 'trending', 'notifications', 'notif-read',
            'bookmark', 'bookmarks', 'suggested-users', 'hashtag', 'trending-tags',
            'browse-profiles', 'profile-card', 'ecosystem-crosspost',
            'groups', 'group-join',
            'agent-feed', 'agent-profile', 'agent-search', 'agent-trending',
            'agent-activity', 'agent-stats', 'agent-badges', 'ecosystem-stats',
            'network-overview',
            'game-state', 'territory-status', 'start-session', 'end-session',
            'mission-list', 'start-mission', 'mission-status',
            'deploy-agents', 'recall-agents', 'capture-zone',
            'supply-transfer', 'build-structure',
            'war-games', 'agent-domains', 'battle-log', 'leaderboard',
        ]], 400);
}
