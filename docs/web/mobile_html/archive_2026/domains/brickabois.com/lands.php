<?php
/**
 * The Land - Physical Village Nodes
 * Airbnb-style discovery and browsing
 */

require_once dirname(__DIR__) . '/private_html/config.php';
require_once __DIR__ . '/includes/auth.php';

$lang = $_GET['lang'] ?? (isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'en');
if (!in_array($lang, ['en', 'fr'])) $lang = 'en';
setcookie('lang', $lang, time() + (86400 * 365), '/');

$currentUser = getCurrentUser();
$db = getDBConnection();

// Search and filter parameters
$search_query = trim($_GET['search'] ?? '');
$region_filter = $_GET['region'] ?? '';
$status_filter = $_GET['status'] ?? ''; // Empty means show all (active + forming)
$sort_by = $_GET['sort'] ?? 'newest';

// Build query - Show ALL villages regardless of status
$sql = "SELECT v.*, 
        COUNT(DISTINCT vm.user_id) as member_count,
        (SELECT COUNT(*) FROM posts WHERE village_id = v.id AND deleted_at IS NULL) as post_count,
        (SELECT COUNT(*) FROM events WHERE village_id = v.id AND start_date >= NOW()) as upcoming_events_count,
        (SELECT photo_url FROM village_photos WHERE village_id = v.id AND is_primary = 1 LIMIT 1) as primary_photo
        FROM villages v
        LEFT JOIN village_members vm ON v.id = vm.village_id
        WHERE v.status != 'archived' OR v.status IS NULL";

$params = [];

// Search filter
if (!empty($search_query)) {
    $sql .= " AND (v.name LIKE ? OR v.name_fr LIKE ? OR v.description LIKE ? OR v.description_fr LIKE ? OR v.region LIKE ? OR v.location_address LIKE ?)";
    $search_param = '%' . $search_query . '%';
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param, $search_param]);
}

// Region filter
if (!empty($region_filter)) {
    $sql .= " AND v.region = ?";
    $params[] = $region_filter;
}

// Status filter - only apply if explicitly set
if (!empty($status_filter) && in_array($status_filter, ['active', 'forming'])) {
    $sql .= " AND v.status = ?";
    $params[] = $status_filter;
}

$sql .= " GROUP BY v.id";

// Sorting
switch ($sort_by) {
    case 'members':
        $sql .= " ORDER BY member_count DESC";
        break;
    case 'name':
        $sql .= " ORDER BY v.name ASC";
        break;
    case 'oldest':
        $sql .= " ORDER BY v.created_at ASC";
        break;
    case 'newest':
    default:
        $sql .= " ORDER BY v.created_at DESC";
        break;
}

$villagesStmt = $db->prepare($sql);
$villagesStmt->execute($params);
$villages = $villagesStmt->fetchAll();

// Get unique regions for filter
$regionsStmt = $db->prepare("SELECT DISTINCT region FROM villages WHERE region IS NOT NULL AND region != '' ORDER BY region");
$regionsStmt->execute();
$regions = array_column($regionsStmt->fetchAll(), 'region');

$translations = [
    'en' => [
        'title' => 'The Land',
        'subtitle' => 'Physical Embodiment',
        'description' => 'Real-world village nodes stewarding ecology, art, and learning',
        'search_placeholder' => 'Search villages...',
        'filter_by_region' => 'Filter by Region',
        'filter_by_status' => 'Filter by Status',
        'sort_by' => 'Sort by',
        'sort_newest' => 'Newest First',
        'sort_oldest' => 'Oldest First',
        'sort_members' => 'Most Members',
        'sort_name' => 'Name (A-Z)',
        'all_regions' => 'All Regions',
        'all_statuses' => 'All Statuses',
        'active' => 'Active',
        'forming' => 'Forming',
        'members' => 'members',
        'posts' => 'posts',
        'events' => 'upcoming events',
        'view_village' => 'View Village',
        'join_village' => 'Join Village',
        'no_villages' => 'No villages found. Try adjusting your search or filters.',
        'clear_filters' => 'Clear Filters',
    ],
    'fr' => [
        'title' => 'La Terre',
        'subtitle' => 'Incarnation Physique',
        'description' => 'Nœuds de villages réels gérant l\'écologie, l\'art et l\'apprentissage',
        'search_placeholder' => 'Rechercher des villages...',
        'filter_by_region' => 'Filtrer par Région',
        'filter_by_status' => 'Filtrer par Statut',
        'sort_by' => 'Trier par',
        'sort_newest' => 'Plus Récent',
        'sort_oldest' => 'Plus Ancien',
        'sort_members' => 'Plus de Membres',
        'sort_name' => 'Nom (A-Z)',
        'all_regions' => 'Toutes les Régions',
        'all_statuses' => 'Tous les Statuts',
        'active' => 'Actif',
        'forming' => 'En Formation',
        'members' => 'membres',
        'posts' => 'publications',
        'events' => 'événements à venir',
        'view_village' => 'Voir le Village',
        'join_village' => 'Rejoindre le Village',
        'no_villages' => 'Aucun village trouvé. Essayez d\'ajuster votre recherche ou vos filtres.',
        'clear_filters' => 'Effacer les Filtres',
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
    <link rel="stylesheet" href="/assets/css/land.css">
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

    <!-- Hero Section -->
    <section class="hero" style="min-height: 60vh; padding-top: 100px;">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title"><?= htmlspecialchars($t['title']) ?></h1>
                <p class="hero-subtitle"><?= htmlspecialchars($t['subtitle']) ?></p>
                <p class="hero-tagline"><?= htmlspecialchars($t['description']) ?></p>
            </div>
        </div>
    </section>

    <!-- Search & Filters Bar -->
    <section class="search-filters-bar">
        <div class="container">
            <form method="GET" class="search-filters-form" id="searchForm">
                <!-- Search Input -->
                <div class="search-input-wrapper">
                    <input 
                        type="text" 
                        name="search" 
                        placeholder="<?= htmlspecialchars($t['search_placeholder']) ?>" 
                        value="<?= htmlspecialchars($search_query) ?>"
                        class="search-input"
                        autocomplete="off"
                    >
                    <button type="submit" class="search-button">🔍</button>
                </div>

                <!-- Filters -->
                <div class="filters-row">
                    <select name="region" class="filter-select">
                        <option value=""><?= htmlspecialchars($t['all_regions']) ?></option>
                        <?php foreach ($regions as $region): ?>
                            <option value="<?= htmlspecialchars($region) ?>" <?= $region_filter === $region ? 'selected' : '' ?>>
                                <?= htmlspecialchars($region) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="status" class="filter-select">
                        <option value="" <?= empty($status_filter) ? 'selected' : '' ?>><?= htmlspecialchars($t['all_statuses']) ?></option>
                        <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>><?= htmlspecialchars($t['active']) ?></option>
                        <option value="forming" <?= $status_filter === 'forming' ? 'selected' : '' ?>><?= htmlspecialchars($t['forming']) ?></option>
                    </select>

                    <select name="sort" class="filter-select">
                        <option value="newest" <?= $sort_by === 'newest' ? 'selected' : '' ?>><?= htmlspecialchars($t['sort_newest']) ?></option>
                        <option value="oldest" <?= $sort_by === 'oldest' ? 'selected' : '' ?>><?= htmlspecialchars($t['sort_oldest']) ?></option>
                        <option value="members" <?= $sort_by === 'members' ? 'selected' : '' ?>><?= htmlspecialchars($t['sort_members']) ?></option>
                        <option value="name" <?= $sort_by === 'name' ? 'selected' : '' ?>><?= htmlspecialchars($t['sort_name']) ?></option>
                    </select>

                    <?php if ($search_query || $region_filter || !empty($status_filter)): ?>
                        <a href="/land" class="clear-filters-btn"><?= htmlspecialchars($t['clear_filters']) ?></a>
                    <?php endif; ?>
                    
                    <script>
                    // Auto-submit form when filters change
                    document.querySelectorAll('.filter-select').forEach(select => {
                        select.addEventListener('change', function() {
                            document.getElementById('searchForm').submit();
                        });
                    });
                    </script>
                </div>
            </form>
        </div>
    </section>

    <!-- Villages Grid -->
    <section class="villages-section">
        <div class="container">
            <?php if (empty($villages)): ?>
                <div class="empty-state">
                    <p><?= htmlspecialchars($t['no_villages']) ?></p>
                </div>
            <?php else: ?>
                <div class="villages-grid-airbnb">
                    <?php foreach ($villages as $village): 
                        // Check if user is member
                        $is_member = false;
                        if (isLoggedIn() && $currentUser) {
                            $memberCheck = $db->prepare("SELECT id FROM village_members WHERE village_id = ? AND user_id = ?");
                            $memberCheck->execute([$village['id'], $currentUser['id']]);
                            $is_member = $memberCheck->fetch() !== false;
                        }
                    ?>
                        <div class="village-card-airbnb">
                            <!-- Village Image -->
                            <div class="village-image">
                                <?php if (!empty($village['primary_photo']) || !empty($village['photo_url'])): ?>
                                    <img src="<?= htmlspecialchars($village['primary_photo'] ?? $village['photo_url']) ?>" 
                                         alt="<?= htmlspecialchars($village['name']) ?>"
                                         class="village-photo"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="village-image-placeholder" style="display: none;">
                                        <span class="village-icon">🌿</span>
                                    </div>
                                <?php else: ?>
                                    <div class="village-image-placeholder">
                                        <span class="village-icon">🌿</span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($village['status'] === 'active'): ?>
                                    <div class="village-badge-top"><?= htmlspecialchars($t['active']) ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Village Info -->
                            <div class="village-card-content">
                                <div class="village-header">
                                    <h3 class="village-name"><?= htmlspecialchars($lang === 'fr' && !empty($village['name_fr']) ? $village['name_fr'] : $village['name']) ?></h3>
                                    <?php if (!empty($village['region'])): ?>
                                        <p class="village-location">📍 <?= htmlspecialchars($village['region']) ?>, <?= htmlspecialchars($village['country'] ?? 'Canada') ?></p>
                                    <?php endif; ?>
                                </div>

                                <?php 
                                $description = $lang === 'fr' && !empty($village['description_fr']) ? $village['description_fr'] : (!empty($village['description']) ? $village['description'] : '');
                                if (!empty($description)): ?>
                                    <p class="village-description"><?= htmlspecialchars(substr($description, 0, 120)) ?><?= strlen($description) > 120 ? '...' : '' ?></p>
                                <?php endif; ?>

                                <div class="village-stats">
                                    <span class="stat-item">👥 <?= $village['member_count'] ?> <?= htmlspecialchars($t['members']) ?></span>
                                    <?php if ($village['post_count'] > 0): ?>
                                        <span class="stat-item">💬 <?= $village['post_count'] ?> <?= htmlspecialchars($t['posts']) ?></span>
                                    <?php endif; ?>
                                    <?php if ($village['upcoming_events_count'] > 0): ?>
                                        <span class="stat-item">📅 <?= $village['upcoming_events_count'] ?> <?= htmlspecialchars($t['events']) ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="village-actions">
                                    <a href="/land/village/<?= htmlspecialchars($village['slug']) ?>" class="btn-view-village">
                                        <?= htmlspecialchars($t['view_village']) ?>
                                    </a>
                                    <?php if (isLoggedIn() && $currentUser && !$is_member): ?>
                                        <a href="/land/join?slug=<?= htmlspecialchars($village['slug']) ?>" class="btn-join-village">
                                            <?= htmlspecialchars($t['join_village']) ?>
                                        </a>
                                    <?php elseif (isLoggedIn() && $currentUser && $is_member): ?>
                                        <span class="btn-member-badge">✓ <?= $lang === 'fr' ? 'Membre' : 'Member' ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <script src="/assets/js/theme.js"></script>
    <script src="/assets/js/theme-randomizer.js"></script>
    <script src="/assets/js/land.js"></script>
</body>
</html>
