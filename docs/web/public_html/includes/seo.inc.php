<?php
/**
 * /includes/seo.inc.php — shared SEO meta emitter for alfredlinux.com teachings.
 * Usage: <?php require __DIR__.'/includes/seo.inc.php'; alfred_seo('/path','Title','Description'); ?>
 */
function alfred_seo(string $path, string $title, string $desc, ?string $image = null): void {
    $base = 'https://alfredlinux.com';
    $canon = $base . $path;
    $img = $image ?: $base . '/og-default.svg';
    $h = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES|ENT_HTML5, 'UTF-8');

    echo "\n<!-- alfred-seo -->\n";
    echo '<link rel="canonical" href="' . $h($canon) . '">' . "\n";
    echo '<meta property="og:type" content="article">' . "\n";
    echo '<meta property="og:site_name" content="Alfred Linux">' . "\n";
    echo '<meta property="og:title" content="' . $h($title) . '">' . "\n";
    echo '<meta property="og:description" content="' . $h($desc) . '">' . "\n";
    echo '<meta property="og:url" content="' . $h($canon) . '">' . "\n";
    echo '<meta property="og:image" content="' . $h($img) . '">' . "\n";
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="twitter:title" content="' . $h($title) . '">' . "\n";
    echo '<meta name="twitter:description" content="' . $h($desc) . '">' . "\n";
    echo '<meta name="twitter:image" content="' . $h($img) . '">' . "\n";

    $ld = [
        '@context'      => 'https://schema.org',
        '@type'         => 'Article',
        'headline'      => $title,
        'description'   => $desc,
        'url'           => $canon,
        'image'         => $img,
        'inLanguage'    => 'en',
        'isPartOf'      => [
            '@type' => 'WebSite',
            'name'  => 'Alfred Linux',
            'url'   => $base,
        ],
        'publisher'     => [
            '@type' => 'Organization',
            'name'  => 'Alfred Linux',
            'url'   => $base,
        ],
        'about'         => 'Christian theology, Bible teaching, Yeshua / Jesus Christ',
    ];
    echo '<script type="application/ld+json">' . json_encode($ld, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
    echo "<!-- /alfred-seo -->\n";
}
