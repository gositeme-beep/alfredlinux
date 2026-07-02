<?php
/**
 * The 57 Prophecies of Jesus Christ — Fulfilled
 * ═══════════════════════════════════════════════
 * Now uses the shared Bible library — One Bible, many altars.
 */
require_once '/home/root/shared/bible/bible-data.php';
require_once '/home/root/shared/bible/bible-styles.php';
require_once '/home/root/shared/bible/bible-prophecies-component.php';

$total = akjv_stats()['prophecies'];
$page_title = "The {$total} Prophecies of Jesus Christ — Fulfilled · AKJV Bible · Authorized April 8, 2026 A.D.";
$page_description = "All {$total} messianic prophecies from the Tanakh fulfilled by Jesus Christ (Yeshua HaMashiach), researched and compiled by Commander Danny William Perez during 18 months of incarceration. Part of the Authorized King Jesus Version.";
$page_canonical = 'https://root.com/bible/prophecies';
$page_og_image = 'https://root.com/assets/images/akjv-og.png';
require_once __DIR__ . '/includes/site-header.inc.php';
$lang = akjv_lang($current_lang ?? 'en');
?>
<style><?= akjv_styles_prophecies() ?></style>
<?php akjv_render_prophecies('/bible', $lang); ?>
<?php include 'includes/site-footer.inc.php'; ?>
