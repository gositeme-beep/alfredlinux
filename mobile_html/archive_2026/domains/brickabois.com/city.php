<?php
/**
 * Individual City Profile Page
 */

require_once dirname(__DIR__) . '/private_html/config.php';
require_once __DIR__ . '/includes/auth.php';

$lang = $_GET['lang'] ?? (isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'en');
if (!in_array($lang, ['en', 'fr'])) $lang = 'en';
setcookie('lang', $lang, time() + (86400 * 365), '/');

$cityName = $_GET['city'] ?? '';
$currentUser = getCurrentUser();
$db = getDBConnection();

// Get city data from municipalities
$cityData = null;
if ($cityName && isset($GLOBALS['quebecMunicipalities'])) {
    $cityData = $GLOBALS['quebecMunicipalities'][$cityName] ?? null;
}

// If not in globals, try to get from window (will be loaded via JS)
// For now, we'll use a fallback approach

$translations = [
    'en' => [
        'title' => 'City Profile',
        'location' => 'Location',
        'region' => 'Region',
        'population' => 'Population',
        'official_map' => 'Official Regional Map',
        'download_map' => 'Download Map',
        'view_map' => 'View Map',
        'back_to_map' => 'Back to Map',
        'city_info' => 'City Information',
        'no_city' => 'City not found'
    ],
    'fr' => [
        'title' => 'Profil de la Ville',
        'location' => 'Emplacement',
        'region' => 'Région',
        'population' => 'Population',
        'official_map' => 'Carte Régionale Officielle',
        'download_map' => 'Télécharger la Carte',
        'view_map' => 'Voir la Carte',
        'back_to_map' => 'Retour à la Carte',
        'city_info' => 'Informations sur la Ville',
        'no_city' => 'Ville non trouvée'
    ]
];

$t = $translations[$lang];

// Get villages in this city/region
$villagesInCity = [];
if ($cityName) {
    $villagesStmt = $db->prepare("
        SELECT v.*, COUNT(DISTINCT vm.user_id) as member_count
        FROM villages v
        LEFT JOIN village_members vm ON v.id = vm.village_id
        WHERE (v.region = ? OR v.name LIKE ? OR v.name_fr LIKE ?) AND v.status = 'active'
        GROUP BY v.id
        ORDER BY v.created_at DESC
    ");
    $searchTerm = "%{$cityName}%";
    $villagesStmt->execute([$cityName, $searchTerm, $searchTerm]);
    $villagesInCity = $villagesStmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($cityName ?: $t['no_city']) ?> - <?= htmlspecialchars($t['title']) ?></title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/navbar-modern.css">
    <link rel="stylesheet" href="/assets/css/themes.css">
    <link rel="stylesheet" href="/assets/css/land.css">
    <script>
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

    <div id="cityProfileContent">
        <div style="padding: 2rem; text-align: center; color: var(--color-text-secondary);">
            <p><?= htmlspecialchars($t['no_city']) ?></p>
            <a href="/" style="color: var(--color-accent); text-decoration: none;"><?= htmlspecialchars($t['back_to_map']) ?></a>
        </div>
    </div>

    <script src="/assets/data/quebec-municipalities.js"></script>
    <script src="/assets/data/quebec-regional-maps.js"></script>
    <script src="/assets/js/theme.js"></script>
    <script>
        // Load city data and render page
        (function() {
            const cityName = new URLSearchParams(window.location.search).get('city');
            if (!cityName) return;

            const municipalities = window.quebecMunicipalities || {};
            const city = municipalities[cityName];
            const regionalMaps = window.quebecRegionalMaps || {};
            
            if (!city) {
                return; // Already showing error
            }

            const lang = document.documentElement.lang || 'en';
            const t = {
                en: {
                    location: 'Location',
                    region: 'Region',
                    population: 'Population',
                    official_map: 'Official Regional Map',
                    download_map: 'Download Map',
                    view_map: 'View Map',
                    back_to_map: 'Back to Map',
                    city_info: 'City Information',
                    villages_here: 'Villages in this Area',
                    no_villages: 'No villages yet in this area'
                },
                fr: {
                    location: 'Emplacement',
                    region: 'Région',
                    population: 'Population',
                    official_map: 'Carte Régionale Officielle',
                    download_map: 'Télécharger la Carte',
                    view_map: 'Voir la Carte',
                    back_to_map: 'Retour à la Carte',
                    city_info: 'Informations sur la Ville',
                    villages_here: 'Villages dans cette Zone',
                    no_villages: 'Aucun village dans cette zone pour le moment'
                }
            }[lang];

            const regionalMap = window.getMapByRegion && window.getMapByRegion(city.region);
            const villages = <?= json_encode($villagesInCity) ?>;

            document.getElementById('cityProfileContent').innerHTML = `
                <section class="hero" style="min-height: 40vh; padding-top: 100px; background: linear-gradient(135deg, rgba(15, 15, 30, 1) 0%, rgba(26, 26, 46, 1) 100%);">
                    <div class="container">
                        <div class="hero-content" style="text-align: center;">
                            <h1 class="hero-title" style="font-size: clamp(2.5rem, 5vw, 4rem);">${cityName}</h1>
                            <p class="hero-subtitle" style="font-size: 1.5rem; color: var(--color-text-secondary);">
                                ${city.region}, ${city.country || 'Canada'}
                            </p>
                        </div>
                    </div>
                </section>

                <section class="activity" style="padding: 4rem 0;">
                    <div class="container">
                        <div class="village-header">
                            <h2 class="village-title">${t.city_info}</h2>
                            
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin-top: 2rem;">
                                <div style="padding: 1.5rem; background: var(--color-bg-card); border: 1px solid var(--color-border); border-radius: 15px;">
                                    <div style="font-size: 0.9rem; color: var(--color-text-secondary); margin-bottom: 0.5rem;">${t.region}</div>
                                    <div style="font-size: 1.5rem; font-weight: 700; color: var(--color-accent);">${city.region}</div>
                                </div>
                                ${city.population ? `
                                <div style="padding: 1.5rem; background: var(--color-bg-card); border: 1px solid var(--color-border); border-radius: 15px;">
                                    <div style="font-size: 0.9rem; color: var(--color-text-secondary); margin-bottom: 0.5rem;">${t.population}</div>
                                    <div style="font-size: 1.5rem; font-weight: 700; color: var(--color-accent);">${city.population.toLocaleString()}</div>
                                </div>
                                ` : ''}
                                ${city.lat && city.lng ? `
                                <div style="padding: 1.5rem; background: var(--color-bg-card); border: 1px solid var(--color-border); border-radius: 15px;">
                                    <div style="font-size: 0.9rem; color: var(--color-text-secondary); margin-bottom: 0.5rem;">${t.location}</div>
                                    <div style="font-size: 1rem; color: var(--color-text);">${city.lat.toFixed(4)}°, ${city.lng.toFixed(4)}°</div>
                                </div>
                                ` : ''}
                            </div>

                            ${regionalMap ? `
                            <div style="margin-top: 3rem; padding: 2rem; background: rgba(212, 165, 116, 0.1); border: 2px solid rgba(212, 165, 116, 0.3); border-radius: 20px;">
                                <h3 style="color: var(--color-accent); margin-bottom: 1rem;">${t.official_map}</h3>
                                <p style="color: var(--color-text-secondary); margin-bottom: 1.5rem;">${regionalMap.name} - ${regionalMap.size}</p>
                                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                                    <a href="${regionalMap.pdfUrl}" target="_blank" rel="noopener" style="padding: 0.75rem 1.5rem; background: var(--color-primary); color: var(--color-text); text-decoration: none; border-radius: 25px; font-weight: 600; display: inline-block;">
                                        📥 ${t.download_map}
                                    </a>
                                    <a href="${regionalMap.pdfUrl}" target="_blank" rel="noopener" style="padding: 0.75rem 1.5rem; background: transparent; border: 2px solid var(--color-border); color: var(--color-text); text-decoration: none; border-radius: 25px; font-weight: 600; display: inline-block;">
                                        👁️ ${t.view_map}
                                    </a>
                                </div>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                </section>

                ${villages && villages.length > 0 ? `
                <section class="activity" style="padding: 4rem 0; background: var(--color-bg-light);">
                    <div class="container">
                        <h2 style="font-size: 2rem; margin-bottom: 2rem; color: var(--color-accent);">${t.villages_here}</h2>
                        <div class="villages-grid-airbnb">
                            ${villages.map(village => {
                                const name = lang === 'fr' && village.name_fr ? village.name_fr : village.name;
                                return `
                                    <div class="village-card-airbnb">
                                        <div class="village-image">
                                            <div class="village-image-placeholder">
                                                <span class="village-icon">🌿</span>
                                            </div>
                                        </div>
                                        <div class="village-content">
                                            <h3><a href="/land/village/${village.slug}" style="color: var(--color-accent); text-decoration: none;">${name}</a></h3>
                                            <p style="color: var(--color-text-secondary); margin: 0.5rem 0;">${(lang === 'fr' && village.description_fr ? village.description_fr : village.description || '').substring(0, 100)}...</p>
                                            <div style="display: flex; gap: 1rem; margin-top: 1rem; font-size: 0.9rem; color: var(--color-text-secondary);">
                                                <span>👥 ${village.member_count || 0} members</span>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            }).join('')}
                        </div>
                    </div>
                </section>
                ` : `
                <section class="activity" style="padding: 4rem 0; background: var(--color-bg-light);">
                    <div class="container" style="text-align: center;">
                        <p style="color: var(--color-text-secondary); font-size: 1.2rem;">${t.no_villages}</p>
                    </div>
                </section>
                `}

                <footer style="padding: 3rem 0; background: var(--color-bg); border-top: 1px solid var(--color-border); margin-top: 4rem;">
                    <div class="container" style="text-align: center; color: var(--color-text-secondary);">
                        <p>&copy; ${new Date().getFullYear()} Free Village Network. ${lang === 'fr' ? 'Tous droits réservés.' : 'All rights reserved.'}</p>
                    </div>
                </footer>
            `;
        })();
    </script>
</body>
</html>

