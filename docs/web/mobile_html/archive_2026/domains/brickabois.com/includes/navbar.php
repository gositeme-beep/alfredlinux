<?php
/**
 * Shared Navbar Component
 */

if (!function_exists('getCurrentUser')) {
    require_once __DIR__ . '/auth.php';
}
$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
if (!isset($lang)) {
    $lang = $_GET['lang'] ?? (isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'en');
}
?>
<nav class="navbar">
    <div class="container">
        <div class="nav-brand">
            <a href="/">
                <span style="font-size: 1.5rem; display: inline-block; margin-right: 0.5rem;">🌐</span>
                <span>Free Village Network</span>
            </a>
        </div>
        <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle menu">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <div class="nav-links" id="navLinks">
            <a href="/commons" class="<?= $currentPage === 'commons' ? 'active' : '' ?>"><?= $lang === 'fr' ? 'Les Communs' : 'The Commons' ?></a>
            <a href="/ledger" class="<?= $currentPage === 'ledger' ? 'active' : '' ?>"><?= $lang === 'fr' ? 'Le Registre' : 'The Ledger' ?></a>
            <a href="/land" class="<?= $currentPage === 'land' ? 'active' : '' ?>"><?= $lang === 'fr' ? 'La Terre' : 'The Land' ?></a>
            <a href="/maps" class="<?= $currentPage === 'maps' ? 'active' : '' ?>">🗺️ <?= $lang === 'fr' ? 'Cartes' : 'Maps' ?></a>
            <?php if (isLoggedIn()): ?>
                <a href="/dashboard"><?= $lang === 'fr' ? 'Tableau de bord' : 'Dashboard' ?></a>
                <?php if (isAdmin() || isImpersonating()): ?>
                    <a href="/admin"><?= $lang === 'fr' ? 'Admin' : 'Admin' ?></a>
                <?php endif; ?>
                <?php if (isImpersonating()): ?>
                    <a href="/admin?stop_impersonate=1" style="background: rgba(239, 68, 68, 0.2); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.5);">
                        <?= $lang === 'fr' ? 'Arrêter l\'impersonation' : 'Stop Impersonating' ?>
                    </a>
                <?php endif; ?>
                <a href="/profile"><?= htmlspecialchars($currentUser['display_name'] ?: $currentUser['username']) ?></a>
                <a href="/logout"><?= $lang === 'fr' ? 'Déconnexion' : 'Logout' ?></a>
            <?php else: ?>
                <a href="/login"><?= $lang === 'fr' ? 'Connexion' : 'Login' ?></a>
                <a href="/register" class="btn-outline"><?= $lang === 'fr' ? 'S\'inscrire' : 'Register' ?></a>
            <?php endif; ?>
            <div class="lang-switcher">
                <a href="?lang=en<?= isset($_GET['page']) ? '&page=' . $_GET['page'] : '' ?>" class="<?= $lang === 'en' ? 'active' : '' ?>">EN</a>
                <a href="?lang=fr<?= isset($_GET['page']) ? '&page=' . $_GET['page'] : '' ?>" class="<?= $lang === 'fr' ? 'active' : '' ?>">FR</a>
            </div>
        </div>
    </div>
</nav>
<script>
// Mobile menu toggle
(function() {
    const toggle = document.getElementById('mobileMenuToggle');
    const navLinks = document.getElementById('navLinks');
    if (toggle && navLinks) {
        toggle.addEventListener('click', function() {
            toggle.classList.toggle('active');
            navLinks.classList.toggle('active');
        });
        
        // Close menu when clicking a link
        navLinks.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                toggle.classList.remove('active');
                navLinks.classList.remove('active');
            });
        });
    }
})();
</script>

