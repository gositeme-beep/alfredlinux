<?php
/**
 * Quebec Regional Maps - Official Government Cartography
 */

require_once dirname(__DIR__) . '/private_html/config.php';
require_once __DIR__ . '/includes/auth.php';

$lang = $_GET['lang'] ?? (isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'en');
if (!in_array($lang, ['en', 'fr'])) $lang = 'en';
setcookie('lang', $lang, time() + (86400 * 365), '/');

$currentUser = getCurrentUser();

// Translations
$t = [
    'en' => [
        'title' => 'Quebec Regional Maps',
        'subtitle' => 'Official Government Cartography',
        'description' => 'Access official cartographic maps for all 17 administrative regions of Quebec',
        'region' => 'Region',
        'download' => 'Download Map',
        'size' => 'Size',
        'official_source' => 'Official Source',
        'source_desc' => 'Ministry of Municipal Affairs and Housing',
        'back_to_home' => 'Back to Home',
        'view_map' => 'View Map',
        'no_map' => 'No map available'
    ],
    'fr' => [
        'title' => 'Cartes Régionales du Québec',
        'subtitle' => 'Cartographie Officielle du Gouvernement',
        'description' => 'Accédez aux cartes cartographiques officielles des 17 régions administratives du Québec',
        'region' => 'Région',
        'download' => 'Télécharger la Carte',
        'size' => 'Taille',
        'official_source' => 'Source Officielle',
        'source_desc' => 'Ministère des Affaires municipales et de l\'Habitation',
        'back_to_home' => 'Retour à l\'Accueil',
        'view_map' => 'Voir la Carte',
        'no_map' => 'Aucune carte disponible'
    ]
];

$currentT = $t[$lang];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($currentT['title']) ?> - <?= htmlspecialchars($currentT['subtitle']) ?></title>
    <meta name="description" content="<?= htmlspecialchars($currentT['description']) ?>">
    
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="/assets/css/navbar-modern.css">
    <link rel="stylesheet" href="/assets/css/themes.css">
    <link rel="stylesheet" href="/assets/css/maps.css">
    
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

    <main class="maps-page">
        <section class="maps-hero">
            <div class="container">
                <h1 class="page-title"><?= htmlspecialchars($currentT['title']) ?></h1>
                <p class="page-subtitle"><?= htmlspecialchars($currentT['subtitle']) ?></p>
                <p class="page-description"><?= htmlspecialchars($currentT['description']) ?></p>
            </div>
        </section>

        <!-- Advanced Interactive Map -->
        <section class="interactive-map-section">
            <div class="container">
                <div class="map-header">
                    <h2><?= $lang === 'fr' ? 'Carte Interactive des Villes du Québec' : 'Interactive Quebec Cities Map' ?></h2>
                    <p><?= $lang === 'fr' ? 'Explorez toutes les villes des 17 régions administratives' : 'Explore all cities from the 17 administrative regions' ?></p>
                </div>
                
                <div class="map-controls">
                    <div class="map-search">
                        <input type="text" id="citySearch" placeholder="<?= $lang === 'fr' ? 'Rechercher une ville...' : 'Search for a city...' ?>" class="search-input">
                        <button class="search-btn">🔍</button>
                    </div>
                    <select id="regionFilter" class="region-filter">
                        <option value="all"><?= $lang === 'fr' ? 'Toutes les Régions' : 'All Regions' ?></option>
                    </select>
                    <button id="resetView" class="reset-btn"><?= $lang === 'fr' ? 'Réinitialiser' : 'Reset View' ?></button>
                </div>

                <div id="advancedInteractiveMap" class="advanced-map-container">
                    <canvas id="mapCanvas"></canvas>
                    <div class="map-zoom-controls">
                        <button id="zoomIn" class="zoom-btn">+</button>
                        <button id="zoomOut" class="zoom-btn">−</button>
                    </div>
                </div>

                <div id="cityDetailsPanel" class="city-details-panel">
                    <button class="close-panel" id="closePanel">×</button>
                    <div id="cityDetailsContent"></div>
                </div>
            </div>
        </section>

        <section class="maps-content">
            <div class="container">
                <div class="maps-source-info">
                    <p><strong><?= htmlspecialchars($currentT['official_source']) ?>:</strong> <?= htmlspecialchars($currentT['source_desc']) ?></p>
                </div>

                <div class="maps-grid" id="mapsGrid">
                    <!-- Maps will be loaded by JavaScript -->
                </div>
            </div>
        </section>
    </main>

    <footer style="padding: 3rem 0; background: var(--color-bg); border-top: 1px solid var(--color-border); margin-top: 4rem;">
        <div class="container" style="text-align: center; color: var(--color-text-secondary);">
            <p>&copy; <?= date('Y') ?> Free Village Network. <?= $lang === 'fr' ? 'Tous droits réservés.' : 'All rights reserved.' ?></p>
        </div>
    </footer>

    <script src="/assets/data/quebec-regional-maps.js"></script>
    <script src="/assets/data/quebec-municipalities.js"></script>
    <script src="/assets/js/theme.js"></script>
    <script src="/assets/js/advanced-interactive-map.js"></script>
    <script src="/assets/js/maps.js"></script>
</body>
</html>

