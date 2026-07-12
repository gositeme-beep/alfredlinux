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

    return array_map(function ($p) use ($userData, $profiles, $likedSet, $bookmarkSet, $comments, $currentUserId) {
        $uid  = (int)$p['user_id'];
        $pid  = (int)$p['id'];
        $info = $userData[$uid] ?? ['name' => 'Unknown', 'email' => ''];
        $name = $info['name'];
        $profile = $profiles[$uid] ?? null;
        $avatarUrl = $profile['avatar_url'] ?? ($info['email'] ? getGravatarUrl($info['email']) : null);
        $badge = $profile['badge'] ?? null;
        return [
            'id'            => $pid,
            'user_id'       => $uid,
            'author_name'   => $name,
            'initials'      => getInitials($name),
            'avatar_url'    => $avatarUrl,
            'badge'         => $badge,
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

    default:
        jsonResponse(['error' => 'Unknown action', 'actions' => [
            'feed', 'global', 'post', 'post-delete', 'like', 'comment', 'comment-delete',
            'follow', 'unfollow', 'followers', 'following', 'profile', 'update-profile',
            'user-posts', 'search', 'trending', 'notifications', 'notif-read',
            'bookmark', 'bookmarks', 'suggested-users', 'hashtag', 'trending-tags',
            'browse-profiles', 'profile-card',
        ]], 400);
}
