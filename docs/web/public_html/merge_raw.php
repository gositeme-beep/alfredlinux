<?php
$release_html = file_get_contents('release.php');
$releases_html = file_get_contents('releases.php');

// 1. We want the final file to have the `nav.php` at the top, since the user liked it in the original `releases.php`.
// So we'll inject it into `release.php`'s <body>.
$release_html = str_replace('<body>', "<body>\n<?php \$currentPage = 'releases'; include __DIR__ . '/includes/nav.php'; ?>", $release_html);

// We need to bring over the CSS from releases.php so the nav (and the timeline) has styles.
preg_match('/<style>(.*?)<\/style>/s', $releases_html, $matches_style);
$historical_css = isset($matches_style[1]) ? $matches_style[1] : '';

// Inject that CSS into release.php's <head>
$release_html = str_replace('</head>', "<style>\n" . $historical_css . "\n</style>\n</head>", $release_html);

// 2. We want the actual historical content from `releases.php`.
preg_match('/<div class="hero">.*?<\/div>\s*<div class="container">(.*?)<\/div>\s*<footer>/s', $releases_html, $matches_html);
$historical_html = isset($matches_html[1]) ? $matches_html[1] : '';

// 3. Append the historical HTML before the Omahon Seal in release.php
$injection = "<div style=\"margin-top: 50px;\">\n<h1>Historical Releases</h1>\n<div class=\"container\">\n" . $historical_html . "\n</div>\n</div>";

if (strpos($release_html, '<!-- Footer -->') !== false) {
    $release_html = str_replace('<!-- Footer -->', $injection . "\n<!-- Footer -->", $release_html);
} elseif (strpos($release_html, '<div class="footer">') !== false) {
    $release_html = str_replace('<div class="footer">', $injection . "\n<div class=\"footer\">", $release_html);
} else {
    // Just append to body
    $release_html = str_replace('</body>', $injection . "\n</body>", $release_html);
}

file_put_contents('merged_temp.php', $release_html);
echo "Merged successfully.\n";
