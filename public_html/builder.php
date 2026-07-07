<?php
$release_html = file_get_contents('release.php');
$releases_html = file_get_contents('releases.php');

// 1. Add nav.css to the head of release.php
$release_html = str_replace('</head>', "    <link rel=\"stylesheet\" href=\"/assets/css/nav.css\">\n</head>", $release_html);

// 2. Add the nav.php include right after <body>
$release_html = str_replace('<body>', "<body>\n<?php \$currentPage = 'releases'; include __DIR__ . '/includes/nav.php'; ?>\n<style>nav { position: relative !important; margin-bottom: 20px; }</style>", $release_html);

// 3. Extract the historical releases CSS from releases.php
preg_match('/<style>(.*?)<\/style>/s', $releases_html, $matches_style);
$historical_css = isset($matches_style[1]) ? $matches_style[1] : '';

// Remove conflicting background variables from the historical CSS so they don't break the parent page!
// release.php has a black background `#050510`. releases.php has `--bg: #06060b`. 
// If we inject `:root { --bg: #06060b; ... body { background: var(--bg); ... }` it will override release.php's body!
// So let's strip out the `body { ... }` and `* { ... }` and `:root { ... }` and `html { ... }` from $historical_css
$historical_css = preg_replace('/:root\s*\{.*?\}/s', '', $historical_css);
$historical_css = preg_replace('/body\s*\{.*?\}/s', '', $historical_css);
$historical_css = preg_replace('/html\s*\{.*?\}/s', '', $historical_css);
$historical_css = preg_replace('/\*\s*\{.*?\}/s', '', $historical_css);
// Also remove `a { ... }` and `a:hover { ... }` which are global
$historical_css = preg_replace('/a\s*\{.*?\}/s', '', $historical_css);
$historical_css = preg_replace('/a:hover\s*\{.*?\}/s', '', $historical_css);

// 4. Inject the safe historical CSS into the <style> block of release.php
$release_html = str_replace('</style>', "\n/* --- HISTORICAL RELEASES CSS --- */\n" . $historical_css . "\n</style>", $release_html);

// 5. Extract the historical releases HTML
preg_match('/(<!-- RC8 — PREVIOUS -->.*?<footer>)/s', $releases_html, $matches_html);
$historical_html = isset($matches_html[1]) ? $matches_html[1] : '';

// Remove footer from historical html
$historical_html = str_replace('<footer>', '', $historical_html);

// 6. Inject the historical HTML before the Omahon Seal in release.php
$injection = "<h2 style=\"text-align:center; color:#D4AF37; margin-top:80px; margin-bottom:20px;\">Historical Releases</h2>\n<div style=\"max-width: 900px; margin: 0 auto; padding: 0 20px;\">\n" . $historical_html . "</div>\n\n<!-- Omahon seal -->";
// Wait, release.php doesn't have <!-- Omahon seal -->. It has <!-- Omahon --> or similar.
// Let's check release.php. Ah, it has `<!-- Omahon seal -->` maybe? Let's use `<!-- Footer -->`
$release_html = str_replace('<!-- Footer -->', $injection . "\n<!-- Footer -->", $release_html);

// Let's fallback if `<!-- Footer -->` isn't there, we'll replace `<div class="footer">`
if (strpos($release_html, '<!-- Footer -->') === false) {
    $release_html = str_replace('<div class="footer">', $injection . "\n<div class=\"footer\">", $release_html);
}

file_put_contents('releases_fixed.php', $release_html);
echo "Built successfully.\n";
