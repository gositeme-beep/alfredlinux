<?php
$gold_html = file_get_contents('release.php'); // The pristine 7.77 page
$timeline_html = file_get_contents('releases.php'); // The pristine historic timeline

// 1. Extract timeline CSS and HTML
preg_match('/<style>(.*?)<\/style>/s', $timeline_html, $matches_style);
$timeline_css = isset($matches_style[1]) ? $matches_style[1] : '';

preg_match('/<div class="hero">.*?<\/div>\s*<div class="container">(.*?)<\/div>\s*<footer>/s', $timeline_html, $matches_html);
$timeline_blocks = isset($matches_html[1]) ? $matches_html[1] : '';

// 2. Setup Gold Page
$final_html = str_replace('</head>', "    <link rel=\"stylesheet\" href=\"/assets/css/nav.css\">\n</head>", $gold_html);

// Inject nav into body WITHOUT adding a </style> tag that str_replace would catch!
$final_html = str_replace('<body>', "<body>\n<?php \$currentPage = 'releases'; include __DIR__ . '/includes/nav.php'; ?>\n<style>nav { position: relative !important; margin-bottom: 20px; z-index: 10; }</style>", $final_html);

// 3. Clean Timeline CSS
$clean_css = preg_replace('/@import\s+url\(.*?\);\s*/', '', $timeline_css); // STRIP OUT THE IMPORT TO AVOID INVALIDATING EVERYTHING
$clean_css = preg_replace('/:root\s*\{.*?\}/s', '', $clean_css);
$clean_css = preg_replace('/body\s*\{.*?\}/s', '', $clean_css);
$clean_css = preg_replace('/html\s*\{.*?\}/s', '', $clean_css);
$clean_css = preg_replace('/\*\s*\{.*?\}/s', '', $clean_css);
$clean_css = preg_replace('/a\s*\{.*?\}/s', '', $clean_css);
$clean_css = preg_replace('/a:hover\s*\{.*?\}/s', '', $clean_css);
$clean_css = preg_replace('/\.hero\s*\{.*?\}/s', '', $clean_css);
$clean_css = preg_replace('/\.container\s*\{.*?\}/s', '', $clean_css);

$clean_css = str_replace('var(--surface)', 'rgba(13,13,32,0.8)', $clean_css); 
$clean_css = str_replace('var(--border)', '#1a1a3a', $clean_css);
$clean_css = str_replace('var(--text-muted)', '#8a8aa0', $clean_css);
$clean_css = str_replace('var(--text-dim)', '#606080', $clean_css);
$clean_css = str_replace('var(--text)', '#e8e8f0', $clean_css);
$clean_css = str_replace('var(--accent)', '#D4AF37', $clean_css); 
$clean_css = str_replace('var(--accent-light)', '#FFD700', $clean_css);
$clean_css = str_replace('var(--green)', '#34d399', $clean_css);
$clean_css = str_replace('var(--amber)', '#f59e0b', $clean_css);
$clean_css = str_replace('var(--cyan)', '#00D4FF', $clean_css);
$clean_css = str_replace('var(--red)', '#ef4444', $clean_css);
$clean_css = str_replace('var(--bg)', '#050510', $clean_css);
$clean_css .= "\n.release { transition: all 0.3s; }\n.release:hover { border-color: rgba(212,175,55,0.3); transform: translateY(-2px); }\n";

// 4. Inject CSS INTO THE HEAD ONLY!
// We will replace the FIRST </style> tag we find (which is in the <head>).
$final_html = preg_replace('/<\/style>/', "\n/* Unified Timeline CSS */\n" . $clean_css . "\n</style>", $final_html, 1);

// 5. Inject HTML
$injection = <<<HTML
        <!-- Historical Timeline Section -->
        <div style="margin-top: 80px; text-align: center; border-top: 1px solid rgba(212,175,55,0.1); padding-top: 50px;">
            <h2 style="color: #D4AF37; font-size: 32px; letter-spacing: 4px; text-transform: uppercase;">Previous Releases</h2>
            <p style="color: #606080; font-size: 14px; margin-top: 10px; margin-bottom: 40px;">The archive of the Kingdom's ascension.</p>
        </div>
        <div class="timeline-wrapper" style="max-width: 900px; margin: 0 auto; text-align: left;">
            $timeline_blocks
        </div>
HTML;

if (strpos($final_html, '<!-- Omahon seal -->') !== false) {
    $final_html = str_replace('<!-- Omahon seal -->', $injection . "\n        <!-- Omahon seal -->", $final_html);
} else {
    $final_html = str_replace('<div class="omahon">', $injection . "\n        <div class=\"omahon\">", $final_html);
}

file_put_contents('releases_unified.php', $final_html);
echo "Unified successfully.\n";
