<?php
/**
 * Individual Village Page
 */

require_once dirname(__DIR__, 2) . '/private_html/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$lang = $_GET['lang'] ?? (isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'en');
if (!in_array($lang, ['en', 'fr'])) $lang = 'en';
setcookie('lang', $lang, time() + (86400 * 365), '/');

$slug = $_GET['slug'] ?? '';
$currentUser = getCurrentUser();
$db = getDBConnection();

// Get village
$villageStmt = $db->prepare("
    SELECT v.*, 
           u.username as steward_username, 
           u.display_name as steward_name,
           u.avatar_url as steward_avatar,
           COUNT(DISTINCT vm.user_id) as member_count
    FROM villages v
    LEFT JOIN users u ON v.steward_id = u.id
    LEFT JOIN village_members vm ON v.id = vm.village_id
    WHERE v.slug = ? AND v.status IN ('active', 'forming')
    GROUP BY v.id
");
$villageStmt->execute([$slug]);
$village = $villageStmt->fetch();

if (!$village) {
    header('HTTP/1.0 404 Not Found');
    die('Village not found');
}

// Get village members
$membersStmt = $db->prepare("
    SELECT u.*, vm.role, vm.joined_at
    FROM village_members vm
    JOIN users u ON vm.user_id = u.id
    WHERE vm.village_id = ? AND u.status = 'active'
    ORDER BY vm.role DESC, vm.joined_at ASC
");
$membersStmt->execute([$village['id']]);
$members = $membersStmt->fetchAll();

// Get village resources
$resourcesStmt = $db->prepare("
    SELECT r.*, u.username, u.display_name
    FROM village_resources r
    JOIN users u ON r.user_id = u.id
    WHERE r.village_id = ?
    ORDER BY r.status ASC, r.created_at DESC
");
$resourcesStmt->execute([$village['id']]);
$resources = $resourcesStmt->fetchAll();

// Get village photos
$photosStmt = $db->prepare("
    SELECT vp.*, u.username, u.display_name
    FROM village_photos vp
    JOIN users u ON vp.user_id = u.id
    WHERE vp.village_id = ?
    ORDER BY vp.is_primary DESC, vp.display_order ASC, vp.created_at DESC
");
$photosStmt->execute([$village['id']]);
$photos = $photosStmt->fetchAll();

// Get village posts
$postsStmt = $db->prepare("
    SELECT p.*, u.username, u.display_name, u.avatar_url
    FROM posts p
    JOIN users u ON p.user_id = u.id
    WHERE p.village_id = ? AND p.deleted_at IS NULL AND p.visibility IN ('public', 'village')
    ORDER BY p.created_at DESC
    LIMIT 10
");
$postsStmt->execute([$village['id']]);
$posts = $postsStmt->fetchAll();

// Check if user is member
$is_member = false;
$user_role = null;
if (isLoggedIn()) {
    $memberCheckStmt = $db->prepare("SELECT role FROM village_members WHERE village_id = ? AND user_id = ?");
    $memberCheckStmt->execute([$village['id'], $currentUser['id']]);
    $memberCheck = $memberCheckStmt->fetch();
    if ($memberCheck) {
        $is_member = true;
        $user_role = $memberCheck['role'];
    }
}

$translations = [
    'en' => [
        'title' => 'Village',
        'members' => 'Members',
        'resources' => 'Resources & Projects',
        'recent_posts' => 'Recent Posts',
        'steward' => 'Steward',
        'member' => 'Member',
        'visitor' => 'Visitor',
        'join_village' => 'Join Village',
        'leave_village' => 'Leave Village',
        'location' => 'Location',
        'founded' => 'Founded',
        'status' => 'Status',
        'active' => 'Active',
        'forming' => 'Forming',
        'no_resources' => 'No resources yet',
        'no_posts' => 'No posts yet',
        'ecology' => 'Ecology',
        'art' => 'Art',
        'learning' => 'Learning',
        'infrastructure' => 'Infrastructure',
    ],
    'fr' => [
        'title' => 'Village',
        'members' => 'Membres',
        'resources' => 'Ressources & Projets',
        'recent_posts' => 'Publications Récentes',
        'steward' => 'Gérant',
        'member' => 'Membre',
        'visitor' => 'Visiteur',
        'join_village' => 'Rejoindre le Village',
        'leave_village' => 'Quitter le Village',
        'location' => 'Emplacement',
        'founded' => 'Fondé',
        'status' => 'Statut',
        'active' => 'Actif',
        'forming' => 'En Formation',
        'no_resources' => 'Aucune ressource pour le moment',
        'no_posts' => 'Aucune publication pour le moment',
        'ecology' => 'Écologie',
        'art' => 'Art',
        'learning' => 'Apprentissage',
        'infrastructure' => 'Infrastructure',
    ]
];

$t = $translations[$lang];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($lang === 'fr' && $village['name_fr'] ? $village['name_fr'] : $village['name']) ?> - Free Village Network</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/navbar-modern.css">
    <style>
        .village-header {
            background: var(--color-bg-card);
            border: 1px solid var(--color-border);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .village-title {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--color-accent);
        }
        .village-info-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }
        .village-section {
            background: var(--color-bg-card);
            border: 1px solid var(--color-border);
            border-radius: 12px;
            padding: 1.5rem;
        }
        .village-section h3 {
            margin-bottom: 1rem;
            color: var(--color-accent);
        }
        .members-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
        }
        .member-card {
            text-align: center;
            padding: 1rem;
            background: var(--color-bg);
            border-radius: 8px;
            border: 1px solid var(--color-border);
        }
        .resource-card {
            padding: 1rem;
            background: var(--color-bg);
            border-radius: 8px;
            border: 1px solid var(--color-border);
            margin-bottom: 1rem;
        }
        @media (max-width: 968px) {
            .village-info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/navbar.php'; ?>

    <section class="hero" style="min-height: 40vh; padding-top: 100px;">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title"><?= htmlspecialchars($lang === 'fr' && $village['name_fr'] ? $village['name_fr'] : $village['name']) ?></h1>
                <p class="hero-subtitle"><?= htmlspecialchars($lang === 'fr' && $village['description_fr'] ? substr($village['description_fr'], 0, 200) : substr($village['description'], 0, 200)) ?>...</p>
            </div>
        </div>
    </section>

    <!-- Photo Gallery -->
    <?php if (!empty($photos)): ?>
    <section class="village-photo-gallery">
        <div class="container">
            <div class="photo-gallery-grid">
                <?php 
                $primary_photo = null;
                foreach ($photos as $p) {
                    if ($p['is_primary']) {
                        $primary_photo = $p;
                        break;
                    }
                }
                if (!$primary_photo && !empty($photos)) {
                    $primary_photo = $photos[0];
                }
                $other_photos = [];
                if ($primary_photo) {
                    foreach ($photos as $p) {
                        if ($p['id'] != $primary_photo['id']) {
                            $other_photos[] = $p;
                        }
                    }
                }
                ?>
                <?php if ($primary_photo): ?>
                <div class="gallery-main-photo">
                    <img src="<?= htmlspecialchars($primary_photo['photo_url']) ?>" alt="<?= htmlspecialchars($village['name']) ?>" onclick="openPhotoModal(0)">
                </div>
                <?php endif; ?>
                <?php if (count($other_photos) > 0): ?>
                    <div class="gallery-thumbnails">
                        <?php foreach (array_slice($other_photos, 0, 4) as $idx => $photo): ?>
                            <div class="gallery-thumb" onclick="openPhotoModal(<?= $idx + 1 ?>)">
                                <img src="<?= htmlspecialchars($photo['photo_url']) ?>" alt="">
                                <?php if (count($other_photos) > 4 && $idx === 3): ?>
                                    <div class="gallery-more-overlay">
                                        <span>+<?= count($other_photos) - 4 ?> <?= $lang === 'fr' ? 'plus' : 'more' ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section class="activity">
        <div class="container">
            <div class="village-header">
                <h2 class="village-title"><?= htmlspecialchars($lang === 'fr' && $village['name_fr'] ? $village['name_fr'] : $village['name']) ?></h2>
                <p><?= htmlspecialchars($lang === 'fr' && $village['description_fr'] ? $village['description_fr'] : $village['description']) ?></p>
                
                <div style="display: flex; gap: 2rem; margin-top: 1.5rem; flex-wrap: wrap;">
                    <?php if ($village['region']): ?>
                        <div><strong><?= htmlspecialchars($t['location']) ?>:</strong> <?= htmlspecialchars($village['region']) ?>, <?= htmlspecialchars($village['country']) ?></div>
                    <?php endif; ?>
                    <?php if ($village['founded_date']): ?>
                        <div><strong><?= htmlspecialchars($t['founded']) ?>:</strong> <?= date('Y', strtotime($village['founded_date'])) ?></div>
                    <?php endif; ?>
                    <div><strong><?= htmlspecialchars($t['status']) ?>:</strong> <?= htmlspecialchars($t[$village['status']] ?? $village['status']) ?></div>
                    <div><strong><?= htmlspecialchars($t['members']) ?>:</strong> <?= $village['member_count'] ?></div>
                </div>

                <?php if ($village['steward_name']): ?>
                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--color-border);">
                        <strong><?= htmlspecialchars($t['steward']) ?>:</strong> <?= htmlspecialchars($village['steward_name']) ?>
                    </div>
                <?php endif; ?>

                <?php if (isLoggedIn()): ?>
                    <?php if (!$is_member): ?>
                        <div style="margin-top: 1.5rem;">
                            <a href="/land/join?slug=<?= htmlspecialchars($village['slug']) ?>" class="btn-primary" style="display: inline-block; padding: 1rem 2rem; background: var(--color-primary); color: white; text-decoration: none; border-radius: 25px; font-weight: 600; transition: all 0.3s;"><?= htmlspecialchars($t['join_village']) ?></a>
                        </div>
                    <?php else: ?>
                        <div style="margin-top: 1.5rem;">
                            <span class="village-badge" style="display: inline-block; padding: 0.75rem 1.5rem; background: rgba(212, 165, 116, 0.2); border: 2px solid var(--color-accent); border-radius: 25px; color: var(--color-accent); font-weight: 600;">✓ <?= htmlspecialchars($t[$user_role] ?? $user_role) ?></span>
                            <a href="/land/join?slug=<?= htmlspecialchars($village['slug']) ?>" style="margin-left: 1rem; color: var(--color-text-secondary); text-decoration: none; font-size: 0.9rem;"><?= $lang === 'fr' ? 'Gérer' : 'Manage' ?></a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div style="margin-top: 1.5rem;">
                        <a href="/login?redirect=<?= urlencode('/land/village/' . $village['slug']) ?>" class="btn-primary" style="display: inline-block; padding: 1rem 2rem; background: var(--color-primary); color: white; text-decoration: none; border-radius: 25px; font-weight: 600;"><?= $lang === 'fr' ? 'Se connecter pour rejoindre' : 'Login to join' ?></a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="village-info-grid">
                <div>
                    <div class="village-section">
                        <h3><?= htmlspecialchars($t['members']) ?></h3>
                        <?php if (empty($members)): ?>
                            <p class="empty-state">No members yet</p>
                        <?php else: ?>
                            <div class="members-grid">
                                <?php foreach ($members as $member): ?>
                                    <div class="member-card">
                                        <?php if ($member['avatar_url']): ?>
                                            <img src="<?= htmlspecialchars($member['avatar_url']) ?>" alt="" class="avatar" style="width: 60px; height: 60px;">
                                        <?php else: ?>
                                            <div class="avatar-placeholder" style="width: 60px; height: 60px; margin: 0 auto;"><?= strtoupper(substr($member['username'], 0, 1)) ?></div>
                                        <?php endif; ?>
                                        <strong style="display: block; margin-top: 0.5rem;"><?= htmlspecialchars($member['display_name'] ?: $member['username']) ?></strong>
                                        <span class="village-badge" style="font-size: 0.75rem;"><?= htmlspecialchars($t[$member['role']] ?? $member['role']) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="village-section" style="margin-top: 2rem;">
                        <h3><?= htmlspecialchars($t['resources']) ?></h3>
                        <?php if (empty($resources)): ?>
                            <p class="empty-state"><?= htmlspecialchars($t['no_resources']) ?></p>
                        <?php else: ?>
                            <?php foreach ($resources as $resource): ?>
                                <div class="resource-card">
                                    <h4><?= htmlspecialchars($lang === 'fr' && $resource['title_fr'] ? $resource['title_fr'] : $resource['title']) ?></h4>
                                    <p><?= htmlspecialchars($lang === 'fr' && $resource['description_fr'] ? substr($resource['description_fr'], 0, 150) : substr($resource['description'], 0, 150)) ?>...</p>
                                    <div style="display: flex; gap: 1rem; margin-top: 0.5rem; font-size: 0.875rem;">
                                        <span class="village-badge"><?= htmlspecialchars($t[$resource['resource_type']] ?? $resource['resource_type']) ?></span>
                                        <span style="color: var(--color-text-secondary);"><?= htmlspecialchars($resource['status']) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div>
                    <div class="village-section">
                        <h3><?= htmlspecialchars($t['recent_posts']) ?></h3>
                        <?php if (empty($posts)): ?>
                            <p class="empty-state"><?= htmlspecialchars($t['no_posts']) ?></p>
                        <?php else: ?>
                            <div class="posts-list">
                                <?php foreach ($posts as $post): ?>
                                    <div class="post-card">
                                        <div class="post-header">
                                            <strong><?= htmlspecialchars($post['display_name'] ?: $post['username']) ?></strong>
                                            <time><?= date('M j', strtotime($post['created_at'])) ?></time>
                                        </div>
                                        <p style="font-size: 0.9rem; color: var(--color-text-secondary);"><?= htmlspecialchars(substr($post['content'], 0, 100)) ?>...</p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <a href="/commons?village=<?= $village['id'] ?>" class="view-all">View All Posts →</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <p style="text-align: center; color: var(--color-text-secondary);">&copy; <?= date('Y') ?> The Free Village Network</p>
        </div>
    </footer>
    <?php if (!empty($photos)): ?>
    <!-- Photo Modal -->
    <div class="photo-modal" id="photoModal">
        <button class="photo-modal-close" onclick="closePhotoModal()">&times;</button>
        <button class="photo-modal-nav photo-modal-prev" onclick="changePhoto(-1)">‹</button>
        <div class="photo-modal-content">
            <img id="modalPhoto" src="" alt="">
        </div>
        <button class="photo-modal-nav photo-modal-next" onclick="changePhoto(1)">›</button>
    </div>
    <?php endif; ?>

    <?php if (!empty($photos)): ?>
    <script>
        // Photo gallery data
        const villagePhotos = <?= json_encode(array_map(function($p) { return $p['photo_url']; }, $photos)) ?>;
        let currentPhotoIndex = 0;

        function openPhotoModal(index) {
            if (villagePhotos.length === 0) return;
            currentPhotoIndex = index;
            if (currentPhotoIndex < 0) currentPhotoIndex = 0;
            if (currentPhotoIndex >= villagePhotos.length) currentPhotoIndex = villagePhotos.length - 1;
            document.getElementById('modalPhoto').src = villagePhotos[currentPhotoIndex];
            document.getElementById('photoModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closePhotoModal() {
            document.getElementById('photoModal').classList.remove('active');
            document.body.style.overflow = '';
        }

        function changePhoto(direction) {
            if (villagePhotos.length === 0) return;
            currentPhotoIndex += direction;
            if (currentPhotoIndex < 0) currentPhotoIndex = villagePhotos.length - 1;
            if (currentPhotoIndex >= villagePhotos.length) currentPhotoIndex = 0;
            document.getElementById('modalPhoto').src = villagePhotos[currentPhotoIndex];
        }

        // Close on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closePhotoModal();
            if (e.key === 'ArrowLeft') changePhoto(-1);
            if (e.key === 'ArrowRight') changePhoto(1);
        });

        // Close on background click
        const modal = document.getElementById('photoModal');
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === this) closePhotoModal();
            });
        }
    </script>
    <?php endif; ?>
    <script src="/assets/js/theme.js"></script>
    <script src="/assets/js/theme-randomizer.js"></script>
</body>
</html>

