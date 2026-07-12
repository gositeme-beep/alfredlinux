<?php
/**
 * 404 Error Page
 */

$lang = $_GET['lang'] ?? (isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'en');
if (!in_array($lang, ['en', 'fr'])) $lang = 'en';

$translations = [
    'en' => [
        'title' => 'Page Not Found',
        'message' => 'The page you are looking for does not exist.',
        'home' => 'Go Home'
    ],
    'fr' => [
        'title' => 'Page Non Trouvée',
        'message' => 'La page que vous recherchez n\'existe pas.',
        'home' => 'Retour à l\'Accueil'
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
</head>
<body>
    <?php 
    require_once __DIR__ . '/includes/auth.php';
    include __DIR__ . '/includes/navbar.php'; 
    ?>
    <div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; text-align: center; padding: 2rem; margin-top: 80px;">
        <div>
            <h1 style="font-size: 6rem; margin-bottom: 1rem; color: var(--color-accent);">404</h1>
            <h2 style="font-size: 2rem; margin-bottom: 1rem;"><?= htmlspecialchars($t['title']) ?></h2>
            <p style="color: var(--color-text-secondary); margin-bottom: 2rem;"><?= htmlspecialchars($t['message']) ?></p>
            <a href="/" class="btn btn-primary"><?= htmlspecialchars($t['home']) ?></a>
        </div>
    </div>
    <script src="/assets/js/theme.js"></script>
    <script src="/assets/js/theme-randomizer.js"></script>
</body>
</html>

