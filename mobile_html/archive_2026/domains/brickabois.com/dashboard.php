<?php
/**
 * User Dashboard
 */

require_once dirname(__DIR__) . '/private_html/config.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$lang = $_GET['lang'] ?? (isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'en');
if (!in_array($lang, ['en', 'fr'])) $lang = 'en';
setcookie('lang', $lang, time() + (86400 * 365), '/');

$currentUser = getCurrentUser();
$db = getDBConnection();

// Get user stats
$postsStmt = $db->prepare("SELECT COUNT(*) as count FROM posts WHERE user_id = ? AND deleted_at IS NULL");
$postsStmt->execute([$currentUser['id']]);
$user_posts = $postsStmt->fetch()['count'];

$eventsStmt = $db->prepare("SELECT COUNT(*) as count FROM events WHERE user_id = ?");
$eventsStmt->execute([$currentUser['id']]);
$user_events = $eventsStmt->fetch()['count'];

$villagesStmt = $db->prepare("
    SELECT COUNT(*) as count FROM village_members 
    WHERE user_id = ? AND role IN ('member', 'steward')
");
$villagesStmt->execute([$currentUser['id']]);
$user_villages = $villagesStmt->fetch()['count'];

// Get recent activity
$recentPostsStmt = $db->prepare("
    SELECT p.*, v.name as village_name
    FROM posts p
    LEFT JOIN villages v ON p.village_id = v.id
    WHERE p.user_id = ? AND p.deleted_at IS NULL
    ORDER BY p.created_at DESC
    LIMIT 5
");
$recentPostsStmt->execute([$currentUser['id']]);
$recent_posts = $recentPostsStmt->fetchAll();

// Get user's villages
$myVillagesStmt = $db->prepare("
    SELECT v.*, vm.role
    FROM villages v
    JOIN village_members vm ON v.id = vm.village_id
    WHERE vm.user_id = ? AND v.status = 'active'
    ORDER BY vm.role DESC, v.name ASC
");
$myVillagesStmt->execute([$currentUser['id']]);
$my_villages = $myVillagesStmt->fetchAll();

$translations = [
    'en' => [
        'title' => 'Dashboard',
        'welcome' => 'Welcome back',
        'stats' => 'Your Activity',
        'posts' => 'Posts',
        'events' => 'Events',
        'villages' => 'Villages',
        'recent_posts' => 'Your Recent Posts',
        'my_villages' => 'My Villages',
        'view_all' => 'View All',
        'no_posts' => 'No posts yet',
        'no_villages' => 'Not a member of any villages yet',
        'steward' => 'Steward',
        'member' => 'Member',
    ],
    'fr' => [
        'title' => 'Tableau de Bord',
        'welcome' => 'Bon retour',
        'stats' => 'Votre Activité',
        'posts' => 'Publications',
        'events' => 'Événements',
        'villages' => 'Villages',
        'recent_posts' => 'Vos Publications Récentes',
        'my_villages' => 'Mes Villages',
        'view_all' => 'Voir Tout',
        'no_posts' => 'Aucune publication pour le moment',
        'no_villages' => 'Pas encore membre d\'un village',
        'steward' => 'Gérant',
        'member' => 'Membre',
    ]
];

$t = $translations[$lang];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($t['title']) ?> - Free Village Network</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/navbar-modern.css">
    <style>
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        .stat-card {
            background: var(--color-bg-card);
            border: 1px solid var(--color-border);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--color-accent);
            margin-bottom: 0.5rem;
        }
        .stat-label {
            color: var(--color-text-secondary);
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }
        .dashboard-section {
            background: var(--color-bg-card);
            border: 1px solid var(--color-border);
            border-radius: 12px;
            padding: 1.5rem;
        }
        .dashboard-section h3 {
            margin-bottom: 1rem;
            color: var(--color-accent);
        }
        @media (max-width: 968px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <section class="hero" style="min-height: 40vh; padding-top: 100px;">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title"><?= htmlspecialchars($t['welcome']) ?>, <?= htmlspecialchars($currentUser['display_name'] ?: $currentUser['username']) ?>!</h1>
            </div>
        </div>
    </section>

    <section class="activity">
        <div class="container">
            <h2><?= htmlspecialchars($t['stats']) ?></h2>
            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-number"><?= $user_posts ?></div>
                    <div class="stat-label"><?= htmlspecialchars($t['posts']) ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $user_events ?></div>
                    <div class="stat-label"><?= htmlspecialchars($t['events']) ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $user_villages ?></div>
                    <div class="stat-label"><?= htmlspecialchars($t['villages']) ?></div>
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="dashboard-section">
                    <h3><?= htmlspecialchars($t['recent_posts']) ?></h3>
                    <?php if (empty($recent_posts)): ?>
                        <p class="empty-state"><?= htmlspecialchars($t['no_posts']) ?></p>
                    <?php else: ?>
                        <div class="posts-list">
                            <?php foreach ($recent_posts as $post): ?>
                                <div class="post-card">
                                    <div class="post-header">
                                        <strong><?= htmlspecialchars(substr($post['content'], 0, 100)) ?>...</strong>
                                        <?php if ($post['village_name']): ?>
                                            <span class="village-badge"><?= htmlspecialchars($post['village_name']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <time><?= date('M j, Y', strtotime($post['created_at'])) ?></time>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <a href="/commons" class="view-all"><?= htmlspecialchars($t['view_all']) ?> →</a>
                    <?php endif; ?>
                </div>

                <div class="dashboard-section">
                    <h3><?= htmlspecialchars($t['my_villages']) ?></h3>
                    <?php if (empty($my_villages)): ?>
                        <p class="empty-state"><?= htmlspecialchars($t['no_villages']) ?></p>
                    <?php else: ?>
                        <div class="villages-list">
                            <?php foreach ($my_villages as $village): ?>
                                <div class="village-card" style="margin-bottom: 1rem;">
                                    <h4><?= htmlspecialchars($lang === 'fr' && $village['name_fr'] ? $village['name_fr'] : $village['name']) ?></h4>
                                    <span class="village-badge"><?= htmlspecialchars($village['role'] === 'steward' ? $t['steward'] : $t['member']) ?></span>
                                    <a href="/land/village/<?= htmlspecialchars($village['slug']) ?>" class="village-link">Visit →</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <p style="text-align: center; color: var(--color-text-secondary);">&copy; <?= date('Y') ?> The Free Village Network</p>
        </div>
    </footer>
</body>
</html>

