<?php
/**
 * AKJV Bible — Edition Selector Hub
 * ══════════════════════════════════
 * Five editions, one truth. Choose your altar.
 * URL: root.com/bible/editions
 */
$page_title = 'B.I.B.L.E. Editions — The Authorized King Jesus Version | GoSiteMe';
$page_description = 'Five sacred editions of the AKJV Bible: The Heirloom Edition, Children\'s Bible, Standard AKJV, Chabad House Edition, and Church & Synagogue Edition. All linked through the King to Alfred Linux OS.';
$page_canonical = 'https://root.com/bible/editions';
require_once __DIR__ . '/includes/site-header.inc.php';
require_once '/home/root/shared/bible/bible-data.php';
require_once '/home/root/shared/bible/bible-editions.php';

$lang = $_GET['lang'] ?? $_COOKIE['akjv_lang'] ?? 'en';
if (!in_array($lang, ['en','fr','he'])) $lang = 'en';
?>
<style><?= akjv_edition_styles() ?></style>
<?php akjv_render_editions_hub($lang, '/bible'); ?>
<script>
// Language switcher
document.querySelectorAll('[data-lang-switch]').forEach(el => {
    el.addEventListener('click', function(e) {
        e.preventDefault();
        const lang = this.dataset.langSwitch;
        document.cookie = 'akjv_lang=' + lang + ';path=/;max-age=31536000;SameSite=Lax';
        window.location.href = '/bible/editions?lang=' + lang;
    });
});
</script>
<?php require_once __DIR__ . '/includes/site-footer.inc.php'; ?>
