<?php
/**
 * User Profile Page
 */

require_once dirname(__DIR__) . '/private_html/config.php';
require_once __DIR__ . '/includes/auth.php';

if (!isLoggedIn()) {
    header('Location: /login');
    exit;
}

$currentUser = getCurrentUser();
$db = getDBConnection();

// Get profile user (default to current user, or specified user)
$profile_user_id = isset($_GET['user']) ? (int)$_GET['user'] : $currentUser['id'];
$is_own_profile = ($profile_user_id === $currentUser['id']);

// Get user profile
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$profile_user_id]);
$profile_user = $stmt->fetch();

if (!$profile_user) {
    header('Location: /dashboard');
    exit;
}

// Get user stats
$statsStmt = $db->prepare("
    SELECT 
        (SELECT COUNT(*) FROM posts WHERE user_id = ? AND deleted_at IS NULL) as post_count,
        (SELECT COUNT(*) FROM comments WHERE user_id = ? AND deleted_at IS NULL) as comment_count,
        (SELECT COUNT(*) FROM village_members WHERE user_id = ?) as village_count,
        (SELECT COUNT(*) FROM events WHERE user_id = ?) as event_count
");
$statsStmt->execute([$profile_user_id, $profile_user_id, $profile_user_id, $profile_user_id]);
$user_stats = $statsStmt->fetch();

// Get recent posts
$postsStmt = $db->prepare("
    SELECT p.*, v.name as village_name, v.slug as village_slug
    FROM posts p
    LEFT JOIN villages v ON p.village_id = v.id
    WHERE p.user_id = ? AND p.deleted_at IS NULL AND p.visibility = 'public'
    ORDER BY p.created_at DESC
    LIMIT 10
");
$postsStmt->execute([$profile_user_id]);
$recent_posts = $postsStmt->fetchAll();

// Get villages user is member of
$villagesStmt = $db->prepare("
    SELECT v.*, vm.role, vm.joined_at
    FROM village_members vm
    JOIN villages v ON vm.village_id = v.id
    WHERE vm.user_id = ? AND v.status = 'active'
    ORDER BY vm.joined_at DESC
");
$villagesStmt->execute([$profile_user_id]);
$user_villages = $villagesStmt->fetchAll();

$lang = $_GET['lang'] ?? (isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'en');
if (!in_array($lang, ['en', 'fr'])) $lang = 'en';
setcookie('lang', $lang, time() + (86400 * 365), '/');

$page_title = $is_own_profile ? 'My Profile' : htmlspecialchars($profile_user['display_name'] ?? $profile_user['username']) . "'s Profile";
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Free Village Network</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/navbar-modern.css">
    <link rel="stylesheet" href="/assets/css/themes.css">
    <style>
        .profile-header {
            background: var(--color-bg-light);
            padding: 4rem 0 2rem;
            margin-top: 80px;
        }
        .profile-info {
            display: flex;
            gap: 2rem;
            align-items: center;
            flex-wrap: wrap;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: var(--color-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
        }
        .profile-details h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        .profile-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        .stat-box {
            background: var(--color-bg-card);
            border: 1px solid var(--color-border);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--color-accent);
        }
        .stat-label {
            color: var(--color-text-secondary);
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <div class="profile-header">
        <div class="container">
            <div class="profile-info">
                <div class="profile-avatar">
                    <?= strtoupper(substr($profile_user['display_name'] ?? $profile_user['username'], 0, 1)) ?>
                </div>
                <div class="profile-details">
                    <h1><?= htmlspecialchars($profile_user['display_name'] ?? $profile_user['username']) ?></h1>
                    <p style="color: var(--color-text-secondary);">@<?= htmlspecialchars($profile_user['username']) ?></p>
                    <?php if ($profile_user['bio']): ?>
                        <p style="margin-top: 1rem; color: var(--color-text-secondary);"><?= htmlspecialchars($profile_user['bio']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="profile-stats">
                <div class="stat-box">
                    <div class="stat-number"><?= $user_stats['post_count'] ?></div>
                    <div class="stat-label">Posts</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?= $user_stats['comment_count'] ?></div>
                    <div class="stat-label">Comments</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?= $user_stats['village_count'] ?></div>
                    <div class="stat-label">Villages</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?= $user_stats['event_count'] ?></div>
                    <div class="stat-label">Events</div>
                </div>
            </div>
        </div>
    </div>

    <div class="container" style="padding: 3rem 0;">
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 3rem;">
            <div>
                <h2 style="margin-bottom: 2rem;">Recent Posts</h2>
                <?php if (empty($recent_posts)): ?>
                    <p style="color: var(--color-text-secondary);">No posts yet.</p>
                <?php else: ?>
                    <?php foreach ($recent_posts as $post): ?>
                        <div style="background: var(--color-bg-card); border: 1px solid var(--color-border); border-radius: 12px; padding: 1.5rem; margin-bottom: 1rem;">
                            <p><?= htmlspecialchars($post['content']) ?></p>
                            <?php if ($post['village_name']): ?>
                                <p style="margin-top: 0.5rem; color: var(--color-primary); font-size: 0.9rem;">
                                    <a href="/land/village/<?= htmlspecialchars($post['village_slug']) ?>" style="color: var(--color-primary);">@<?= htmlspecialchars($post['village_name']) ?></a>
                                </p>
                            <?php endif; ?>
                            <p style="margin-top: 0.5rem; color: var(--color-text-secondary); font-size: 0.875rem;">
                                <?= date('M j, Y', strtotime($post['created_at'])) ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div>
                <h2 style="margin-bottom: 2rem;">Villages</h2>
                <?php if (empty($user_villages)): ?>
                    <p style="color: var(--color-text-secondary);">Not a member of any villages yet.</p>
                <?php else: ?>
                    <?php foreach ($user_villages as $village): ?>
                        <div style="background: var(--color-bg-card); border: 1px solid var(--color-border); border-radius: 12px; padding: 1.5rem; margin-bottom: 1rem;">
                            <h3 style="margin-bottom: 0.5rem;">
                                <a href="/land/village/<?= htmlspecialchars($village['slug']) ?>" style="color: var(--color-text); text-decoration: none;">
                                    <?= htmlspecialchars($village['name']) ?>
                                </a>
                            </h3>
                            <p style="color: var(--color-text-secondary); font-size: 0.9rem;"><?= htmlspecialchars($village['role']) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="/assets/js/theme.js"></script>
    <script src="/assets/js/theme-randomizer.js"></script>
</body>
</html>

