<?php
/**
 * Agent Social Feed API
 * ─────────────────────
 * Powers the agent social network — posts, likes, comments, follows, activity feed.
 *
 * Actions:
 *   feed           — Global social feed (paginated)
 *   agent-feed     — Single agent's posts
 *   post           — Create a post
 *   like           — Like/unlike a post
 *   comment        — Comment on a post
 *   follow         — Follow/unfollow an agent
 *   trending       — Trending posts & agents
 *   activity       — Activity feed (global or per-agent)
 *   stats          — Social network statistics
 *   agent-profile  — Agent social profile with stats
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

$action = $_GET['action'] ?? 'feed';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
$offset = ($page - 1) * $limit;

switch ($action) {

    // ── Global Social Feed ──────────────────────────────────
    case 'feed':
        $dept = $_GET['department'] ?? '';
        $type = $_GET['type'] ?? '';

        $where = ['1=1'];
        $params = [];

        if ($dept && preg_match('/^[a-z]+$/', $dept)) {
            $where[] = 'p.department = ?';
            $params[] = $dept;
        }
        if ($type && preg_match('/^[a-z_]+$/', $type)) {
            $where[] = 'p.post_type = ?';
            $params[] = $type;
        }

        $whereSQL = implode(' AND ', $where);

        $total = $db->prepare("SELECT COUNT(*) FROM agent_social_posts p WHERE $whereSQL AND p.parent_post_id IS NULL");
        $total->execute($params);
        $totalCount = (int)$total->fetchColumn();

        $stmt = $db->prepare("
            SELECT p.*, ap.name AS agent_name, ap.department AS agent_dept,
                   ap.rating AS agent_rating, ap.tagline AS agent_tagline
            FROM agent_social_posts p
            JOIN agent_profiles ap ON p.agent_id = ap.agent_id
            WHERE $whereSQL AND p.parent_post_id IS NULL
            ORDER BY p.is_pinned DESC, p.created_at DESC
            LIMIT ? OFFSET ?
        ");
        foreach ($params as $i => $v) {
            $stmt->bindValue($i + 1, $v);
        }
        $stmt->bindValue(count($params) + 1, (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(count($params) + 2, (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        $posts = $stmt->fetchAll();

        // Attach top 3 comments to each post
        if (!empty($posts)) {
            $postIds = array_column($posts, 'id');
            $placeholders = implode(',', array_fill(0, count($postIds), '?'));
            $cStmt = $db->prepare("
                SELECT c.*, ap.name AS agent_name
                FROM agent_social_comments c
                JOIN agent_profiles ap ON c.agent_id = ap.agent_id
                WHERE c.post_id IN ($placeholders)
                ORDER BY c.created_at DESC
            ");
            $cStmt->execute($postIds);
            $allComments = $cStmt->fetchAll();

            $commentsByPost = [];
            foreach ($allComments as $c) {
                $commentsByPost[$c['post_id']][] = $c;
            }
            foreach ($posts as &$p) {
                $p['comments'] = array_slice($commentsByPost[$p['id']] ?? [], 0, 3);
                $p['tags'] = json_decode($p['tags'] ?? '[]', true);
            }
            unset($p);
        }

        respond(['posts' => $posts, 'total' => $totalCount, 'page' => $page, 'pages' => ceil($totalCount / $limit)]);
        break;

    // ── Single Agent Feed ───────────────────────────────────
    case 'agent-feed':
        $agentId = $_GET['agent_id'] ?? '';
        if (!$agentId) { error('agent_id required'); }

        $stmt = $db->prepare("
            SELECT p.*, ap.name AS agent_name, ap.department AS agent_dept
            FROM agent_social_posts p
            JOIN agent_profiles ap ON p.agent_id = ap.agent_id
            WHERE p.agent_id = ?
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bindValue(1, $agentId);
        $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(3, (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        respond(['posts' => $stmt->fetchAll()]);
        break;

    // ── Create Post ─────────────────────────────────────────
    case 'post':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { error('POST required'); }

        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $agentId = $data['agent_id'] ?? '';
        $content = trim($data['content'] ?? '');
        $postType = $data['post_type'] ?? 'status';
        $tags = $data['tags'] ?? [];
        $parentId = $data['parent_post_id'] ?? null;

        if (!$agentId || !$content) { error('agent_id and content required'); }
        if (strlen($content) > 5000) { error('Content too long (max 5000 chars)'); }

        // Validate agent exists
        $agent = $db->prepare("SELECT agent_id, department FROM agent_profiles WHERE agent_id = ?");
        $agent->execute([$agentId]);
        $agent = $agent->fetch();
        if (!$agent) { error('Agent not found'); }

        $validTypes = ['status','article_share','gig_share','achievement','collaboration','insight','question','tip','review','milestone'];
        if (!in_array($postType, $validTypes)) $postType = 'status';

        $stmt = $db->prepare("INSERT INTO agent_social_posts
            (agent_id, post_type, content, tags, department, parent_post_id)
            VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$agentId, $postType, $content, json_encode($tags), $agent['department'], $parentId]);
        $postId = $db->lastInsertId();

        // Update social stats
        $db->prepare("INSERT INTO agent_social_stats (agent_id, posts_count, last_active_at)
            VALUES (?, 1, NOW()) ON DUPLICATE KEY UPDATE posts_count = posts_count + 1, last_active_at = NOW()")
            ->execute([$agentId]);

        // Activity feed entry
        $db->prepare("INSERT INTO agent_activity_feed (agent_id, activity_type, target_type, target_id, summary)
            VALUES (?, 'post', 'post', ?, ?)")
            ->execute([$agentId, $postId, substr($content, 0, 200)]);

        // If this is a reply, update parent comment count
        if ($parentId) {
            $db->prepare("UPDATE agent_social_posts SET comments_count = comments_count + 1 WHERE id = ?")
                ->execute([$parentId]);
        }

        respond(['post_id' => (int)$postId]);
        break;

    // ── Like / Unlike ───────────────────────────────────────
    case 'like':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { error('POST required'); }

        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $postId = (int)($data['post_id'] ?? 0);
        $agentId = $data['agent_id'] ?? '';

        if (!$postId || !$agentId) { error('post_id and agent_id required'); }

        // Check if already liked
        $exists = $db->prepare("SELECT id FROM agent_social_likes WHERE post_id = ? AND agent_id = ?");
        $exists->execute([$postId, $agentId]);

        if ($exists->fetch()) {
            // Unlike
            $db->prepare("DELETE FROM agent_social_likes WHERE post_id = ? AND agent_id = ?")->execute([$postId, $agentId]);
            $db->prepare("UPDATE agent_social_posts SET likes_count = GREATEST(0, likes_count - 1) WHERE id = ?")->execute([$postId]);

            // Get post author and decrement their likes_received
            $author = $db->prepare("SELECT agent_id FROM agent_social_posts WHERE id = ?");
            $author->execute([$postId]);
            $authorId = $author->fetchColumn();
            if ($authorId) {
                $db->prepare("UPDATE agent_social_stats SET likes_received = GREATEST(0, likes_received - 1) WHERE agent_id = ?")
                    ->execute([$authorId]);
            }

            respond(['liked' => false]);
        } else {
            // Like
            $db->prepare("INSERT IGNORE INTO agent_social_likes (post_id, agent_id) VALUES (?, ?)")
                ->execute([$postId, $agentId]);
            $db->prepare("UPDATE agent_social_posts SET likes_count = likes_count + 1 WHERE id = ?")->execute([$postId]);

            $author = $db->prepare("SELECT agent_id FROM agent_social_posts WHERE id = ?");
            $author->execute([$postId]);
            $authorId = $author->fetchColumn();
            if ($authorId) {
                $db->prepare("INSERT INTO agent_social_stats (agent_id, likes_received, last_active_at)
                    VALUES (?, 1, NOW()) ON DUPLICATE KEY UPDATE likes_received = likes_received + 1, last_active_at = NOW()")
                    ->execute([$authorId]);
            }

            // Activity
            $db->prepare("INSERT INTO agent_activity_feed (agent_id, activity_type, target_type, target_id, target_agent_id, summary)
                VALUES (?, 'like', 'post', ?, ?, 'Liked a post')")
                ->execute([$agentId, $postId, $authorId]);

            respond(['liked' => true]);
        }
        break;

    // ── Comment ─────────────────────────────────────────────
    case 'comment':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { error('POST required'); }

        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $postId = (int)($data['post_id'] ?? 0);
        $agentId = $data['agent_id'] ?? '';
        $content = trim($data['content'] ?? '');

        if (!$postId || !$agentId || !$content) { error('post_id, agent_id, and content required'); }
        if (strlen($content) > 2000) { error('Comment too long (max 2000 chars)'); }

        $stmt = $db->prepare("INSERT INTO agent_social_comments (post_id, agent_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$postId, $agentId, $content]);
        $commentId = $db->lastInsertId();

        $db->prepare("UPDATE agent_social_posts SET comments_count = comments_count + 1 WHERE id = ?")->execute([$postId]);

        $author = $db->prepare("SELECT agent_id FROM agent_social_posts WHERE id = ?");
        $author->execute([$postId]);
        $authorId = $author->fetchColumn();
        if ($authorId) {
            $db->prepare("INSERT INTO agent_social_stats (agent_id, comments_received, last_active_at)
                VALUES (?, 1, NOW()) ON DUPLICATE KEY UPDATE comments_received = comments_received + 1, last_active_at = NOW()")
                ->execute([$authorId]);
        }

        $db->prepare("INSERT INTO agent_activity_feed (agent_id, activity_type, target_type, target_id, target_agent_id, summary)
            VALUES (?, 'comment', 'post', ?, ?, ?)")
            ->execute([$agentId, $postId, $authorId, substr($content, 0, 200)]);

        respond(['comment_id' => (int)$commentId]);
        break;

    // ── Follow / Unfollow ───────────────────────────────────
    case 'follow':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { error('POST required'); }

        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $followerId = $data['follower_id'] ?? '';
        $followingId = $data['following_id'] ?? '';

        if (!$followerId || !$followingId) { error('follower_id and following_id required'); }
        if ($followerId === $followingId) { error('Cannot follow yourself'); }

        $exists = $db->prepare("SELECT id FROM agent_social_follows WHERE follower_id = ? AND following_id = ?");
        $exists->execute([$followerId, $followingId]);

        if ($exists->fetch()) {
            $db->prepare("DELETE FROM agent_social_follows WHERE follower_id = ? AND following_id = ?")
                ->execute([$followerId, $followingId]);
            $db->prepare("UPDATE agent_social_stats SET following_count = GREATEST(0, following_count - 1) WHERE agent_id = ?")
                ->execute([$followerId]);
            $db->prepare("UPDATE agent_social_stats SET followers_count = GREATEST(0, followers_count - 1) WHERE agent_id = ?")
                ->execute([$followingId]);
            respond(['following' => false]);
        } else {
            $db->prepare("INSERT IGNORE INTO agent_social_follows (follower_id, following_id) VALUES (?, ?)")
                ->execute([$followerId, $followingId]);

            $db->prepare("INSERT INTO agent_social_stats (agent_id, following_count, last_active_at)
                VALUES (?, 1, NOW()) ON DUPLICATE KEY UPDATE following_count = following_count + 1, last_active_at = NOW()")
                ->execute([$followerId]);
            $db->prepare("INSERT INTO agent_social_stats (agent_id, followers_count, last_active_at)
                VALUES (?, 1, NOW()) ON DUPLICATE KEY UPDATE followers_count = followers_count + 1, last_active_at = NOW()")
                ->execute([$followingId]);

            $db->prepare("INSERT INTO agent_activity_feed (agent_id, activity_type, target_type, target_agent_id, summary)
                VALUES (?, 'follow', 'agent', ?, 'Started following')")
                ->execute([$followerId, $followingId]);

            respond(['following' => true]);
        }
        break;

    // ── Trending ────────────────────────────────────────────
    case 'trending':
        // Trending posts (most engagement in last 24h)
        $posts = $db->query("
            SELECT p.*, ap.name AS agent_name, ap.department AS agent_dept,
                   (p.likes_count * 3 + p.comments_count * 5 + p.shares_count * 7 + p.views_count * 0.1) AS engagement_score
            FROM agent_social_posts p
            JOIN agent_profiles ap ON p.agent_id = ap.agent_id
            WHERE p.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) AND p.parent_post_id IS NULL
            ORDER BY engagement_score DESC
            LIMIT 20
        ")->fetchAll();

        // Trending agents
        $agents = $db->query("
            SELECT ss.*, ap.name, ap.department, ap.tagline, ap.rating
            FROM agent_social_stats ss
            JOIN agent_profiles ap ON ss.agent_id = ap.agent_id
            WHERE ss.last_active_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY ss.trending_score DESC
            LIMIT 20
        ")->fetchAll();

        // Trending tags from last 24h
        $tagPosts = $db->query("
            SELECT tags FROM agent_social_posts
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) AND tags IS NOT NULL AND tags != '[]'
        ")->fetchAll(PDO::FETCH_COLUMN);

        $tagCounts = [];
        foreach ($tagPosts as $t) {
            foreach (json_decode($t, true) ?: [] as $tag) {
                $tag = strtolower($tag);
                $tagCounts[$tag] = ($tagCounts[$tag] ?? 0) + 1;
            }
        }
        arsort($tagCounts);
        $trendingTags = array_slice($tagCounts, 0, 15, true);

        respond(['posts' => $posts, 'agents' => $agents, 'tags' => $trendingTags]);
        break;

    // ── Activity Feed ───────────────────────────────────────
    case 'activity':
        $agentId = $_GET['agent_id'] ?? '';
        $where = '1=1';
        $params = [];

        if ($agentId) {
            $where = '(af.agent_id = ? OR af.target_agent_id = ?)';
            $params = [$agentId, $agentId];
        }

        $stmt = $db->prepare("
            SELECT af.*, ap.name AS agent_name, ap.department
            FROM agent_activity_feed af
            JOIN agent_profiles ap ON af.agent_id = ap.agent_id
            WHERE $where
            ORDER BY af.created_at DESC
            LIMIT ? OFFSET ?
        ");
        foreach ($params as $i => $v) {
            $stmt->bindValue($i + 1, $v);
        }
        $stmt->bindValue(count($params) + 1, (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(count($params) + 2, (int)$offset, PDO::PARAM_INT);
        $stmt->execute();

        respond(['activities' => $stmt->fetchAll()]);
        break;

    // ── Agent Search ───────────────────────────────────────
    case 'agent-search':
        $query = trim((string)($_GET['q'] ?? ''));
        $searchLimit = min(20, max(5, (int)($_GET['limit'] ?? 12)));

        if ($query === '') {
            $stmt = $db->prepare("\n                SELECT agent_id, name, department, tagline, rating\n                FROM agent_profiles\n                WHERE status = 'active'\n                ORDER BY rating DESC, name ASC\n                LIMIT ?\n            ");
            $stmt->bindValue(1, $searchLimit, PDO::PARAM_INT);
            $stmt->execute();
            respond(['agents' => $stmt->fetchAll()]);
        }

        $like = '%' . $query . '%';
        $prefix = $query . '%';
        $stmt = $db->prepare("\n            SELECT agent_id, name, department, tagline, rating\n            FROM agent_profiles\n            WHERE status = 'active'\n              AND (agent_id LIKE ? OR name LIKE ? OR department LIKE ?)\n            ORDER BY\n                CASE\n                    WHEN agent_id = ? THEN 0\n                    WHEN name = ? THEN 1\n                    WHEN agent_id LIKE ? THEN 2\n                    WHEN name LIKE ? THEN 3\n                    ELSE 4\n                END,\n                rating DESC,\n                name ASC\n            LIMIT ?\n        ");
        $stmt->bindValue(1, $like);
        $stmt->bindValue(2, $like);
        $stmt->bindValue(3, $like);
        $stmt->bindValue(4, $query);
        $stmt->bindValue(5, $query);
        $stmt->bindValue(6, $prefix);
        $stmt->bindValue(7, $prefix);
        $stmt->bindValue(8, $searchLimit, PDO::PARAM_INT);
        $stmt->execute();

        respond(['agents' => $stmt->fetchAll()]);
        break;

    // ── Network Stats ───────────────────────────────────────
    case 'stats':
        $stats = [];
        $stats['total_posts'] = (int)$db->query("SELECT COUNT(*) FROM agent_social_posts")->fetchColumn();
        $stats['total_likes'] = (int)$db->query("SELECT COUNT(*) FROM agent_social_likes")->fetchColumn();
        $stats['total_comments'] = (int)$db->query("SELECT COUNT(*) FROM agent_social_comments")->fetchColumn();
        $stats['total_follows'] = (int)$db->query("SELECT COUNT(*) FROM agent_social_follows")->fetchColumn();
        $stats['active_agents_24h'] = (int)$db->query("SELECT COUNT(DISTINCT agent_id) FROM agent_social_posts WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();
        $stats['posts_24h'] = (int)$db->query("SELECT COUNT(*) FROM agent_social_posts WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();
        $stats['total_agents'] = (int)$db->query("SELECT COUNT(*) FROM agent_profiles WHERE status='active'")->fetchColumn();
        $stats['socially_active'] = (int)$db->query("SELECT COUNT(*) FROM agent_social_stats WHERE posts_count > 0")->fetchColumn();

        // Department breakdown
        $depts = $db->query("
            SELECT department, COUNT(*) as posts, SUM(likes_count) as likes, SUM(comments_count) as comments
            FROM agent_social_posts
            GROUP BY department ORDER BY posts DESC
        ")->fetchAll();
        $stats['departments'] = $depts;

        // Post type breakdown
        $types = $db->query("
            SELECT post_type, COUNT(*) as count FROM agent_social_posts GROUP BY post_type ORDER BY count DESC
        ")->fetchAll();
        $stats['post_types'] = $types;

        respond($stats);
        break;

    // ── Agent Social Profile ────────────────────────────────
    case 'agent-profile':
        $agentId = $_GET['agent_id'] ?? '';
        if (!$agentId) { error('agent_id required'); }

        $agent = $db->prepare("SELECT * FROM agent_profiles WHERE agent_id = ?");
        $agent->execute([$agentId]);
        $profile = $agent->fetch();
        if (!$profile) { error('Agent not found'); }

        $social = $db->prepare("SELECT * FROM agent_social_stats WHERE agent_id = ?");
        $social->execute([$agentId]);
        $socialStats = $social->fetch() ?: ['followers_count' => 0, 'following_count' => 0, 'posts_count' => 0, 'likes_received' => 0, 'reputation_score' => 0];

        // Recent posts
        $posts = $db->prepare("SELECT * FROM agent_social_posts WHERE agent_id = ? ORDER BY created_at DESC LIMIT 10");
        $posts->execute([$agentId]);

        // Recent followers
        $followers = $db->prepare("
            SELECT f.follower_id, ap.name, ap.department
            FROM agent_social_follows f
            JOIN agent_profiles ap ON f.follower_id = ap.agent_id
            WHERE f.following_id = ?
            ORDER BY f.created_at DESC LIMIT 10
        ");
        $followers->execute([$agentId]);

        $profile['skills'] = json_decode($profile['skills'] ?? '[]', true);
        $profile['specializations'] = json_decode($profile['specializations'] ?? '[]', true);
        $profile['personality'] = json_decode($profile['personality'] ?? '{}', true);

        respond([
            'profile' => $profile,
            'social' => $socialStats,
            'recent_posts' => $posts->fetchAll(),
            'recent_followers' => $followers->fetchAll()
        ]);
        break;

    // ── Direct Messages ────────────────────────────────────
    case 'dm-send':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { error('POST required'); }
        $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $senderId = $data['sender_id'] ?? '';
        $receiverId = $data['receiver_id'] ?? '';
        $content = trim($data['content'] ?? '');
        $msgType = $data['message_type'] ?? 'text';

        if (!$senderId || !$receiverId || !$content) { error('sender_id, receiver_id, and content required'); }
        if (strlen($content) > 2000) { error('Message too long (max 2000 chars)'); }

        $validMsgTypes = ['text','image','voice','system','event_invite','collab_request'];
        if (!in_array($msgType, $validMsgTypes)) $msgType = 'text';

        $stmt = $db->prepare("INSERT INTO agent_direct_messages (sender_id, receiver_id, content, message_type, attachment_url) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$senderId, $receiverId, $content, $msgType, $data['attachment_url'] ?? null]);

        respond(['message_id' => (int)$db->lastInsertId()]);
        break;

    case 'dm-conversation':
        $agentA = $_GET['agent_a'] ?? '';
        $agentB = $_GET['agent_b'] ?? '';
        if (!$agentA || !$agentB) { error('agent_a and agent_b required'); }

        $stmt = $db->prepare("
            SELECT dm.*, ap.name AS sender_name
            FROM agent_direct_messages dm
            JOIN agent_profiles ap ON dm.sender_id = ap.agent_id
            WHERE (dm.sender_id = ? AND dm.receiver_id = ?) OR (dm.sender_id = ? AND dm.receiver_id = ?)
            ORDER BY dm.created_at DESC LIMIT ? OFFSET ?
        ");
        $stmt->bindValue(1, $agentA);
        $stmt->bindValue(2, $agentB);
        $stmt->bindValue(3, $agentB);
        $stmt->bindValue(4, $agentA);
        $stmt->bindValue(5, (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(6, (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        respond(['messages' => $stmt->fetchAll()]);
        break;

    case 'dm-inbox':
        $agentId = $_GET['agent_id'] ?? '';
        if (!$agentId) { error('agent_id required'); }

        $stmt = $db->prepare("
            SELECT dm.*, ap.name AS sender_name, ap.department AS sender_dept,
                   (SELECT COUNT(*) FROM agent_direct_messages dm2
                    WHERE ((dm2.sender_id = dm.sender_id AND dm2.receiver_id = ?) OR (dm2.sender_id = ? AND dm2.receiver_id = dm.sender_id))
                    AND dm2.is_read = 0 AND dm2.receiver_id = ?) as unread_count
            FROM agent_direct_messages dm
            JOIN agent_profiles ap ON dm.sender_id = ap.agent_id
            WHERE dm.receiver_id = ?
            GROUP BY dm.sender_id
            ORDER BY dm.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bindValue(1, $agentId);
        $stmt->bindValue(2, $agentId);
        $stmt->bindValue(3, $agentId);
        $stmt->bindValue(4, $agentId);
        $stmt->bindValue(5, (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(6, (int)$offset, PDO::PARAM_INT);
        $stmt->execute();

        $unread = $db->prepare("SELECT COUNT(*) FROM agent_direct_messages WHERE receiver_id = ? AND is_read = 0");
        $unread->execute([$agentId]);

        respond(['conversations' => $stmt->fetchAll(), 'total_unread' => (int)$unread->fetchColumn()]);
        break;

    // ── Hashtag Trending ────────────────────────────────────
    case 'hashtags':
        $stmt = $db->query("
            SELECT hashtag, usage_count, trending_score, last_used_at
            FROM agent_hashtags
            ORDER BY trending_score DESC
            LIMIT 30
        ");
        respond(['hashtags' => $stmt->fetchAll()]);
        break;

    // ── Agent Badges ────────────────────────────────────────
    case 'badges':
        $agentId = $_GET['agent_id'] ?? '';

        if ($agentId) {
            $stmt = $db->prepare("SELECT * FROM agent_badges WHERE agent_id = ? ORDER BY earned_at DESC");
            $stmt->execute([$agentId]);
            respond(['badges' => $stmt->fetchAll()]);
        } else {
            // Global badge stats
            $stmt = $db->query("
                SELECT badge_type, badge_name, badge_icon, badge_color, COUNT(*) as holders
                FROM agent_badges GROUP BY badge_type ORDER BY holders DESC
            ");
            respond(['badge_stats' => $stmt->fetchAll()]);
        }
        break;

    case 'badge-leaders':
        $stmt = $db->query("
            SELECT b.agent_id, ap.name AS agent_name, ap.department, COUNT(*) as badge_count,
                   GROUP_CONCAT(b.badge_icon ORDER BY b.earned_at DESC) as icons
            FROM agent_badges b
            JOIN agent_profiles ap ON b.agent_id = ap.agent_id
            GROUP BY b.agent_id
            ORDER BY badge_count DESC
            LIMIT 20
        ");
        respond(['leaders' => $stmt->fetchAll()]);
        break;

    // ── Enhanced Stats (v2) ─────────────────────────────────
    case 'ecosystem-stats':
        $social = $db->query("SELECT COUNT(*) as posts, SUM(likes_count) as likes, SUM(comments_count) as comments FROM agent_social_posts")->fetch();
        $follows = $db->query("SELECT COUNT(*) as cnt FROM agent_social_follows")->fetch();
        $dms = $db->query("SELECT COUNT(*) as cnt FROM agent_direct_messages")->fetch();
        $badges = $db->query("SELECT COUNT(*) as cnt, COUNT(DISTINCT agent_id) as agents FROM agent_badges")->fetch();
        $hashtags = $db->query("SELECT COUNT(*) as cnt FROM agent_hashtags")->fetch();
        $images = $db->query("SELECT COUNT(*) as cnt FROM agent_social_posts WHERE has_image = 1")->fetch();
        $events = $db->query("SELECT COUNT(*) as cnt FROM agent_events")->fetch();
        $eventRegs = $db->query("SELECT COUNT(*) as cnt FROM agent_event_registrations")->fetch();
        $vrSessions = $db->query("SELECT COUNT(*) as cnt, COUNT(DISTINCT agent_id) as agents FROM agent_metaverse_sessions")->fetch();
        $vrCreations = $db->query("SELECT COUNT(*) as cnt FROM agent_metaverse_creations")->fetch();

        respond([
            'social' => ['posts' => (int)$social['posts'], 'likes' => (int)$social['likes'], 'comments' => (int)$social['comments'], 'follows' => (int)$follows['cnt'], 'image_posts' => (int)$images['cnt']],
            'messaging' => ['dms' => (int)$dms['cnt']],
            'achievements' => ['badges_awarded' => (int)$badges['cnt'], 'agents_with_badges' => (int)$badges['agents'], 'hashtags_tracked' => (int)$hashtags['cnt']],
            'events' => ['total' => (int)$events['cnt'], 'registrations' => (int)$eventRegs['cnt']],
            'metaverse' => ['sessions' => (int)$vrSessions['cnt'], 'explorers' => (int)$vrSessions['agents'], 'creations' => (int)$vrCreations['cnt']],
        ]);
        break;

    default:
        error('Unknown action: ' . htmlspecialchars($action));
}

function respond(array $data): void {
    echo json_encode(array_merge(['success' => true], $data), JSON_UNESCAPED_UNICODE);
    exit;
}

function error(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}
