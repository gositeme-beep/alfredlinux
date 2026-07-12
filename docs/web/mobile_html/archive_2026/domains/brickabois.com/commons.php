<?php
/**
 * The Commons - Social Connection & Dialogue
 */

require_once dirname(__DIR__) . '/private_html/config.php';
require_once __DIR__ . '/includes/auth.php';

$lang = $_GET['lang'] ?? (isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'en');
if (!in_array($lang, ['en', 'fr'])) $lang = 'en';
setcookie('lang', $lang, time() + (86400 * 365), '/');

$currentUser = getCurrentUser();
$db = getDBConnection();

// Handle post creation
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_post']) && isLoggedIn()) {
    $content = trim($_POST['content'] ?? '');
    $village_id = !empty($_POST['village_id']) ? (int)$_POST['village_id'] : null;
    $visibility = $_POST['visibility'] ?? 'public';
    
    if (empty($content)) {
        $error = $lang === 'fr' ? 'Le contenu ne peut pas être vide' : 'Content cannot be empty';
    } else {
        $stmt = $db->prepare("
            INSERT INTO posts (user_id, village_id, content, visibility, language)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$currentUser['id'], $village_id, $content, $visibility, $lang]);
        $success = $lang === 'fr' ? 'Publication créée avec succès!' : 'Post created successfully!';
    }
}

// Get posts
$village_filter = isset($_GET['village']) ? (int)$_GET['village'] : null;
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

$sql = "SELECT p.*, u.username, u.display_name, u.avatar_url, v.name as village_name, v.slug as village_slug,
        (SELECT COUNT(*) FROM reactions WHERE target_type = 'post' AND target_id = p.id) as reaction_count,
        (SELECT COUNT(*) FROM comments WHERE post_id = p.id AND deleted_at IS NULL) as comment_count
        FROM posts p
        JOIN users u ON p.user_id = u.id
        LEFT JOIN villages v ON p.village_id = v.id
        WHERE p.deleted_at IS NULL AND p.visibility = 'public'";

$params = [];
if ($village_filter) {
    $sql .= " AND p.village_id = ?";
    $params[] = $village_filter;
}

$sql .= " ORDER BY p.is_pinned DESC, p.created_at DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;

$stmt = $db->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll();

// Get total count
$countSql = "SELECT COUNT(*) as total FROM posts WHERE deleted_at IS NULL AND visibility = 'public'";
if ($village_filter) {
    $countSql .= " AND village_id = ?";
}
$countStmt = $db->prepare($countSql);
$countStmt->execute($village_filter ? [$village_filter] : []);
$total_posts = $countStmt->fetch()['total'];
$total_pages = ceil($total_posts / $per_page);

// Get user's villages for posting
$user_villages = [];
if (isLoggedIn()) {
    $villageStmt = $db->prepare("
        SELECT v.* FROM villages v
        JOIN village_members vm ON v.id = vm.village_id
        WHERE vm.user_id = ? AND v.status = 'active'
    ");
    $villageStmt->execute([$currentUser['id']]);
    $user_villages = $villageStmt->fetchAll();
}

// Get upcoming events
$eventsStmt = $db->prepare("
    SELECT e.*, u.username, u.display_name, v.name as village_name,
    (SELECT COUNT(*) FROM event_attendees WHERE event_id = e.id AND status = 'attending') as attendee_count
    FROM events e
    JOIN users u ON e.user_id = u.id
    LEFT JOIN villages v ON e.village_id = v.id
    WHERE e.is_public = 1 AND e.start_date >= NOW()
    ORDER BY e.start_date ASC
    LIMIT 10
");
$eventsStmt->execute();
$events = $eventsStmt->fetchAll();

$translations = [
    'en' => [
        'title' => 'The Commons',
        'subtitle' => 'Social Connection & Dialogue',
        'description' => 'A bilingual network of feeds, events, and stories',
        'create_post' => 'Create Post',
        'post_content' => 'What\'s on your mind?',
        'post_btn' => 'Post',
        'select_village' => 'Select Village (optional)',
        'visibility' => 'Visibility',
        'public' => 'Public',
        'village' => 'Village Only',
        'members' => 'Members Only',
        'recent_posts' => 'Recent Posts',
        'upcoming_events' => 'Upcoming Events',
        'no_posts' => 'No posts yet. Be the first to share!',
        'no_events' => 'No upcoming events.',
        'comments' => 'Comments',
        'reactions' => 'Reactions',
        'view_post' => 'View Post',
        'login_to_post' => 'Login to create posts',
        'next' => 'Next',
        'prev' => 'Previous',
    ],
    'fr' => [
        'title' => 'Les Communs',
        'subtitle' => 'Connexion Sociale & Dialogue',
        'description' => 'Un réseau bilingue de fils d\'actualité, d\'événements et d\'histoires',
        'create_post' => 'Créer une Publication',
        'post_content' => 'Qu\'avez-vous en tête?',
        'post_btn' => 'Publier',
        'select_village' => 'Sélectionner un Village (optionnel)',
        'visibility' => 'Visibilité',
        'public' => 'Public',
        'village' => 'Village Seulement',
        'members' => 'Membres Seulement',
        'recent_posts' => 'Publications Récentes',
        'upcoming_events' => 'Événements à Venir',
        'no_posts' => 'Aucune publication pour le moment. Soyez le premier à partager!',
        'no_events' => 'Aucun événement à venir.',
        'comments' => 'Commentaires',
        'reactions' => 'Réactions',
        'view_post' => 'Voir la Publication',
        'login_to_post' => 'Connectez-vous pour créer des publications',
        'next' => 'Suivant',
        'prev' => 'Précédent',
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
    <link rel="stylesheet" href="/assets/css/themes.css">
    <link rel="stylesheet" href="/assets/css/commons.css">
    <script>
        // Initialize theme immediately
        (function() {
            const theme = localStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-theme', theme);
            const colorTheme = localStorage.getItem('colorTheme') || 'forest';
            document.documentElement.setAttribute('data-color-theme', colorTheme);
        })();
    </script>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <section class="hero" style="min-height: 60vh; padding-top: 100px;">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title"><?= htmlspecialchars($t['title']) ?></h1>
                <p class="hero-subtitle"><?= htmlspecialchars($t['subtitle']) ?></p>
                <p class="hero-tagline"><?= htmlspecialchars($t['description']) ?></p>
            </div>
        </div>
    </section>

    <section class="activity" style="padding: 2rem 0;">
        <div class="container">
            <div class="commons-layout">
                <!-- Main Feed -->
                <div class="commons-main">
                    <!-- Create Post Form -->
                    <?php if (isLoggedIn()): ?>
                        <div class="create-post-card">
                            <h3><?= htmlspecialchars($t['create_post']) ?></h3>
                            <?php if ($error): ?>
                                <div class="error"><?= htmlspecialchars($error) ?></div>
                            <?php endif; ?>
                            <?php if ($success): ?>
                                <div class="success"><?= htmlspecialchars($success) ?></div>
                            <?php endif; ?>
                            <form method="POST" class="post-form">
                                <textarea name="content" placeholder="<?= htmlspecialchars($t['post_content']) ?>" required rows="4"></textarea>
                                <?php if (!empty($user_villages)): ?>
                                    <select name="village_id" class="village-select">
                                        <option value=""><?= htmlspecialchars($t['select_village']) ?></option>
                                        <?php foreach ($user_villages as $v): ?>
                                            <option value="<?= $v['id'] ?>"><?= htmlspecialchars($lang === 'fr' && $v['name_fr'] ? $v['name_fr'] : $v['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                                <div class="post-options">
                                    <select name="visibility">
                                        <option value="public"><?= htmlspecialchars($t['public']) ?></option>
                                        <option value="village"><?= htmlspecialchars($t['village']) ?></option>
                                        <option value="members"><?= htmlspecialchars($t['members']) ?></option>
                                    </select>
                                    <button type="submit" name="create_post" class="btn-primary"><?= htmlspecialchars($t['post_btn']) ?></button>
                                </div>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="create-post-card">
                            <p><?= htmlspecialchars($t['login_to_post']) ?> <a href="/login">Login</a></p>
                        </div>
                    <?php endif; ?>

                    <!-- Posts Feed -->
                    <h2 style="margin: 2rem 0 1rem;"><?= htmlspecialchars($t['recent_posts']) ?></h2>
                    <?php if (empty($posts)): ?>
                        <p class="empty-state"><?= htmlspecialchars($t['no_posts']) ?></p>
                    <?php else: ?>
                        <div class="posts-feed">
                            <?php foreach ($posts as $post): ?>
                                <div class="post-card-full">
                                    <div class="post-header">
                                        <div class="post-author">
                                            <?php if ($post['avatar_url']): ?>
                                                <img src="<?= htmlspecialchars($post['avatar_url']) ?>" alt="" class="avatar">
                                            <?php else: ?>
                                                <div class="avatar-placeholder"><?= strtoupper(substr($post['username'], 0, 1)) ?></div>
                                            <?php endif; ?>
                                            <div>
                                                <strong><?= htmlspecialchars($post['display_name'] ?: $post['username']) ?></strong>
                                                <?php if ($post['village_name']): ?>
                                                    <span class="village-badge">📍 <?= htmlspecialchars($post['village_name']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <time><?= date('M j, Y g:i A', strtotime($post['created_at'])) ?></time>
                                    </div>
                                    <p class="post-content"><?= nl2br(htmlspecialchars($post['content'])) ?></p>
                                    <div class="post-footer">
                                        <span class="post-stats">
                                            <span>❤️ <?= $post['reaction_count'] ?></span>
                                            <span>💬 <?= $post['comment_count'] ?></span>
                                        </span>
                                        <a href="/post?id=<?= $post['id'] ?>" class="view-post-link"><?= htmlspecialchars($t['view_post']) ?> →</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?= $page - 1 ?><?= $village_filter ? '&village=' . $village_filter : '' ?>" class="btn-secondary"><?= htmlspecialchars($t['prev']) ?></a>
                                <?php endif; ?>
                                <span>Page <?= $page ?> of <?= $total_pages ?></span>
                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?= $page + 1 ?><?= $village_filter ? '&village=' . $village_filter : '' ?>" class="btn-secondary"><?= htmlspecialchars($t['next']) ?></a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Sidebar -->
                <div class="commons-sidebar">
                    <!-- Upcoming Events -->
                    <div class="sidebar-section">
                        <h3><?= htmlspecialchars($t['upcoming_events']) ?></h3>
                        <?php if (empty($events)): ?>
                            <p class="empty-state"><?= htmlspecialchars($t['no_events']) ?></p>
                        <?php else: ?>
                            <div class="events-sidebar">
                                <?php foreach ($events as $event): ?>
                                    <div class="event-card-sidebar">
                                        <div class="event-date-small">
                                            <span class="event-day-small"><?= date('j', strtotime($event['start_date'])) ?></span>
                                            <span class="event-month-small"><?= date('M', strtotime($event['start_date'])) ?></span>
                                        </div>
                                        <div class="event-info-small">
                                            <h4><?= htmlspecialchars($lang === 'fr' && $event['title_fr'] ? $event['title_fr'] : $event['title']) ?></h4>
                                            <?php if ($event['village_name']): ?>
                                                <p>📍 <?= htmlspecialchars($event['village_name']) ?></p>
                                            <?php endif; ?>
                                            <p class="event-attendees">👥 <?= $event['attendee_count'] ?> <?= $lang === 'fr' ? 'participants' : 'attending' ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <a href="/commons/events" class="view-all"><?= htmlspecialchars($t['view_all']) ?> →</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="/assets/js/theme.js"></script>
    <script src="/assets/js/theme-randomizer.js"></script>
</body>
</html>

