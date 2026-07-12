<?php
require_once __DIR__ . '/lang.php';

// Commander-only pages: return a plain 404 unless the session is client_id/uid 33.
// Important: if there's no session cookie, do NOT start a session (avoid Set-Cookie leaks).
$needsCommander = false;
if (PHP_SAPI !== 'cli') {
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
    $protectedExact = [
        '/supreme-admin.php',
        '/eden-tracker.php',
        '/docs/letter-to-future-me.php',
    ];
    $protectedPrefixes = [
        '/commander-',
        '/commander-archive/',
        '/docs/commander-',
    ];

    $needsCommander = in_array($requestPath, $protectedExact, true);
    if (!$needsCommander) {
        foreach ($protectedPrefixes as $prefix) {
            if ($requestPath !== '' && strpos($requestPath, $prefix) === 0) {
                $needsCommander = true;
                break;
            }
        }
    }

    if ($needsCommander) {
        $cookieName = session_name();
        $hasSessionCookie = $cookieName && isset($_COOKIE[$cookieName]);
        if (!$hasSessionCookie) {
            http_response_code(404);
            header('Content-Type: text/html; charset=utf-8');
            header('Cache-Control: no-store');
            header('X-Robots-Tag: noindex, nofollow');
            echo '<!DOCTYPE html><html lang="en"><head>';
            echo '<meta charset="UTF-8">';
            echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
            echo '<meta name="robots" content="noindex, nofollow">';
            echo '<title>404 — Not Found</title>';
            echo '<link rel="preload" href="/assets/css/site.min.css" as="style" type="text/css">';
            echo '<link rel="stylesheet" href="/assets/css/site.min.css">';
            echo '</head><body>';
            echo '<main class="main-content" id="main">';
            echo '<section class="section section-404" style="padding:6rem 1.5rem; text-align:center;">';
            echo '<div class="container" style="max-width:780px;">';
            echo '<h1 class="section-title" style="margin-bottom:.5rem;">Page not found</h1>';
            echo '<p class="section-subtitle" style="margin-bottom:2rem; font-size:1.1rem;">This page doesn\'t exist.</p>';
            echo '<div class="btn-group" style="flex-wrap:wrap; gap:1rem; justify-content:center;">';
            echo '<a href="/" class="btn btn-primary">Home</a>';
            echo '<a href="/sitemap.xml" class="btn btn-outline">Sitemap</a>';
            echo '</div></div></section></main>';
            echo '</body></html>';
            exit;
        }
    }
}

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

if ($needsCommander) {
    $clientId = (int)($_SESSION['client_id'] ?? $_SESSION['uid'] ?? 0);
    $isLoggedIn = !empty($_SESSION['logged_in']) || !empty($_SESSION['client_id']) || !empty($_SESSION['uid']);
    $isCommander = ($clientId === 33) && $isLoggedIn;

    if (!$isCommander) {
        http_response_code(404);
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-store');
        header('X-Robots-Tag: noindex, nofollow');
        echo '<!DOCTYPE html><html lang="en"><head>';
        echo '<meta charset="UTF-8">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        echo '<meta name="robots" content="noindex, nofollow">';
        echo '<title>404 — Not Found</title>';
        echo '<link rel="preload" href="/assets/css/site.min.css" as="style" type="text/css">';
        echo '<link rel="stylesheet" href="/assets/css/site.min.css">';
        echo '</head><body>';
        echo '<main class="main-content" id="main">';
        echo '<section class="section section-404" style="padding:6rem 1.5rem; text-align:center;">';
        echo '<div class="container" style="max-width:780px;">';
        echo '<h1 class="section-title" style="margin-bottom:.5rem;">Page not found</h1>';
        echo '<p class="section-subtitle" style="margin-bottom:2rem; font-size:1.1rem;">This page doesn\'t exist.</p>';
        echo '<div class="btn-group" style="flex-wrap:wrap; gap:1rem; justify-content:center;">';
        echo '<a href="/" class="btn btn-primary">Home</a>';
        echo '<a href="/sitemap.xml" class="btn btn-outline">Sitemap</a>';
        echo '</div></div></section></main>';
        echo '</body></html>';
        exit;
    }
}
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
if (!isset($page_title)) $page_title = L('meta_title');
if (!isset($page_description)) $page_description = L('meta_description');
if (!isset($page_canonical)) $page_canonical = 'https://gositeme.com/';
if (!isset($page_og_url)) $page_og_url = $page_canonical;
if (!isset($page_og_image)) $page_og_image = 'https://gositeme.com/assets/hero-banner.png';
if (!isset($page_og_image_alt)) $page_og_image_alt = 'Best web hosting & AI website builder - GoSiteMe. Build sites in 60 seconds. WordPress, domains, free SSL.';
if (!isset($page_og_title)) $page_og_title = $page_title;
if (!isset($page_og_description)) $page_og_description = $page_description;
if (!isset($page_twitter_description)) $page_twitter_description = $page_og_description;
$html_lang = ($current_lang === 'fr') ? 'fr' : 'en';
$og_locale = L('og_locale');
$canonical_no_query = rtrim(preg_replace('#\?.*$#', '', $page_canonical), '/') ?: 'https://gositeme.com';
?>
<!DOCTYPE html>
<html lang="<?php echo $html_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="tiktok-developers-site-verification" content="lfgjhAaP9i6qvfNhuiQ03cL0VJ0yX6Pp" />
    
    <!-- Primary Meta Tags / SEO -->
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="title" content="<?php echo htmlspecialchars($page_title); ?>">
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars(L('meta_keywords')); ?>">
    <meta name="author" content="GoSiteMe">
    <meta name="robots" content="<?php echo isset($page_robots) ? htmlspecialchars($page_robots) : 'index, follow'; ?>">
    <link rel="canonical" href="<?php echo htmlspecialchars($page_canonical); ?>">
    <link rel="sitemap" type="application/xml" title="Sitemap" href="https://gositeme.com/sitemap.xml">
    <link rel="alternate" type="application/rss+xml" title="GoSiteMe News" href="https://gositeme.com/announcements/rss">
    
    <!-- Language alternates for SEO (EN/FR) -->
    <link rel="alternate" hreflang="en" href="<?php echo htmlspecialchars($canonical_no_query); ?>?language=english">
    <link rel="alternate" hreflang="fr" href="<?php echo htmlspecialchars($canonical_no_query); ?>?language=french">
    <link rel="alternate" hreflang="x-default" href="<?php echo htmlspecialchars($canonical_no_query); ?>">
    
    <!-- Open Graph / Facebook (uses hero image, not logo) -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo htmlspecialchars($page_og_url); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($page_og_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($page_og_description); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($page_og_image); ?>">
    <meta property="og:image:alt" content="<?php echo htmlspecialchars($page_og_image_alt); ?>">
    <meta property="og:site_name" content="GoSiteMe">
    <meta property="og:locale" content="<?php echo htmlspecialchars($og_locale); ?>">
    
    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo htmlspecialchars($page_og_url); ?>">
    <meta property="twitter:title" content="<?php echo htmlspecialchars($page_og_title); ?>">
    <meta property="twitter:description" content="<?php echo htmlspecialchars($page_twitter_description); ?>">
    <meta property="twitter:image" content="<?php echo htmlspecialchars($page_og_image); ?>">
    <meta name="twitter:image:alt" content="<?php echo htmlspecialchars($page_og_image_alt); ?>">
    
    <!-- PWA / App meta -->
    <meta name="theme-color" content="#0a0a14">
    <meta name="apple-mobile-web-app-title" content="GoSiteMe">
    <meta name="application-name" content="GoSiteMe">
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/brand/favicon.png" sizes="32x32">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    
    <link rel="preload" href="/assets/css/site.min.css?v=20260309b" as="style" type="text/css">
    <?php if (!empty($preload_hero)): ?>
    <link rel="preload" href="/assets/hero-banner.png" as="image">
    <?php endif; ?>
    <!-- Preconnect to Critical Resources -->
    <!-- preconnect removed: self-hosted -->
    <!-- preconnect removed: self-hosted -->
    
    <!-- Fonts -->
    <link href="/assets/vendor/fonts/inter/inter.css" />
    <link rel="stylesheet" href="/assets/vendor/fonts/space-grotesk/space-grotesk.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="/assets/fontawesome/css/all.min.css" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="/assets/fontawesome/css/all.min.css"></noscript>
    
    <!-- AOS Animation - Deferred -->
    <link href="/assets/js/vendor/aos.css" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link href="/assets/js/vendor/aos.css" rel="stylesheet"></noscript>
    
    <!-- AOS Fallback: if AOS fails to init within 3s, show everything -->
    <style id="aos-safety">[data-aos]{opacity:1!important;transform:none!important;transition:none!important}</style>
    
    <!-- Site CSS -->
    <link rel="stylesheet" href="/assets/css/design-tokens.css?v=20260310">
    <link rel="stylesheet" href="/assets/css/site.min.css?v=20260309b">
    <link rel="stylesheet" href="/assets/css/components.css?v=20260310">
    <link rel="stylesheet" href="/assets/css/mobile-universal.css?v=20260311">
    <style>
    /* ═══ LOGO FLIP ANIMATION ═══ */
    .logo-flip{display:inline-block;perspective:600px;height:38px;overflow:visible;}
    .logo-flip-inner{display:flex;align-items:center;transition:transform .8s cubic-bezier(.4,.2,.2,1);transform-style:preserve-3d;position:relative;}
    .logo-flip-front,.logo-flip-back{display:flex;align-items:center;backface-visibility:hidden;}
    .logo-flip-back{position:absolute;top:50%;left:0;transform:rotateY(180deg) translateY(-50%);transform-origin:center center;}
    .logo-flip.flipped .logo-flip-inner{transform:rotateY(180deg);}
    .logo-main-img{display:none;}
    @media(max-width:768px){.logo-flip-back span:last-child{display:none;}}

    .site-cart-badge{position:absolute;top:-6px;right:-10px;background:#00a8ff;color:#fff;font-size:.65rem;font-weight:700;min-width:16px;height:16px;border-radius:50%;text-align:center;line-height:16px;padding:0 4px;}
    .mob-accordion{border-bottom:1px solid rgba(255,255,255,.05);}
    .mob-accordion-btn{display:flex;align-items:center;gap:12px;width:100%;padding:16px 0;background:none;border:none;color:#fff;font-size:1.05rem;font-weight:600;cursor:pointer;font-family:inherit;}
    .mob-accordion-btn i:first-child{width:24px;text-align:center;color:var(--purple);}
    .mob-chev{margin-left:auto!important;font-size:.7rem;transition:transform .25s ease;color:var(--text-muted);}
    .mob-accordion-panel{max-height:0;overflow:hidden;transition:max-height .3s ease;padding-left:12px;}
    .mob-accordion-panel a{font-size:.95rem!important;padding:12px 0!important;}
    @media(max-width:768px){
        .pwa-split{grid-template-columns:1fr!important;}
    }
    /* ═══ MEGA MENU ═══ */
    .mega-trigger>a{display:flex!important;align-items:center;gap:6px;}
    .mega-chev{font-size:.55rem;opacity:.4;transition:transform .25s ease;margin-left:2px;}
    .mega-trigger.open .mega-chev{transform:rotate(180deg);opacity:.7;}
    .mega-panel{position:fixed;left:50%;transform:translateX(-50%);width:min(900px,92vw);background:rgba(12,12,22,.98);backdrop-filter:blur(24px);-webkit-backdrop-filter:blur(24px);border:1px solid rgba(255,255,255,.08);border-radius:16px;padding:28px 32px;opacity:0;visibility:hidden;pointer-events:none;transition:opacity .22s ease,visibility .22s ease;z-index:10000;box-shadow:0 20px 60px rgba(0,0,0,.6),inset 0 0 0 1px rgba(255,255,255,.03);}
    .mega-panel.active{opacity:1;visibility:visible;pointer-events:auto;}
    .mega-backdrop{position:fixed;inset:0;z-index:9998;pointer-events:none;display:none;}.mega-backdrop.active{display:block;}
    .mega-cols{display:grid;gap:28px;}.mega-cols-2{grid-template-columns:1fr 1fr;}.mega-cols-3{grid-template-columns:1fr 1fr 1fr;}
    .mega-heading{font-size:.68rem;text-transform:uppercase;letter-spacing:.1em;color:var(--text-muted);margin-bottom:10px;font-weight:700;padding-left:12px;}
    .mega-item{display:flex;align-items:flex-start;gap:10px;padding:8px 12px;border-radius:8px;text-decoration:none!important;color:#b0b0c8;transition:all .18s ease;}
    .mega-item:hover{background:rgba(125,0,255,.1);color:#fff;transform:translateX(3px);}
    .mega-item i{width:18px;text-align:center;margin-top:3px;font-size:.85rem;flex-shrink:0;}
    .mega-item .mi-t{font-size:.88rem;font-weight:500;line-height:1.3;}
    .mega-item .mi-d{font-size:.72rem;color:var(--text-muted);margin-top:1px;line-height:1.3;}
    /* ═══ COMMAND PALETTE ═══ */
    .cmd-overlay{position:fixed;inset:0;background:rgba(0,0,0,.55);backdrop-filter:blur(4px);-webkit-backdrop-filter:blur(4px);z-index:99999;display:none;justify-content:center;align-items:flex-start;padding-top:min(120px,15vh);}
    .cmd-overlay.active{display:flex;}
    .cmd-modal{width:min(640px,92vw);background:#0e0e1a;border:1px solid rgba(255,255,255,.1);border-radius:16px;box-shadow:0 30px 80px rgba(0,0,0,.7);overflow:hidden;animation:cmdIn .18s ease;}
    @keyframes cmdIn{from{opacity:0;transform:scale(.97) translateY(-8px);}to{opacity:1;transform:none;}}
    .cmd-input-wrap{display:flex;align-items:center;gap:10px;padding:14px 18px;border-bottom:1px solid rgba(255,255,255,.07);}
    .cmd-input-wrap i{color:var(--text-muted);font-size:1.05rem;}
    .cmd-input{flex:1;background:none;border:none;outline:none;color:#fff;font-size:1rem;font-family:inherit;}
    .cmd-input::placeholder{color:rgba(255,255,255,.25);}
    .cmd-kbd{display:inline-flex;align-items:center;padding:2px 6px;border-radius:4px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.08);color:var(--text-muted);font-size:.68rem;font-family:inherit;line-height:1.4;}
    .cmd-results{max-height:min(400px,50vh);overflow-y:auto;padding:6px;}
    .cmd-results::-webkit-scrollbar{width:4px;}.cmd-results::-webkit-scrollbar-thumb{background:rgba(255,255,255,.08);border-radius:4px;}
    .cmd-group-title{font-size:.67rem;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);padding:10px 14px 4px;font-weight:700;}
    .cmd-item{display:flex;align-items:center;gap:10px;padding:9px 14px;border-radius:8px;cursor:pointer;color:#b0b0c8;text-decoration:none;transition:background .12s;}
    .cmd-item:hover,.cmd-item.active{background:rgba(125,0,255,.12);color:#fff;}
    .cmd-item i{width:18px;text-align:center;font-size:.85rem;color:var(--purple);flex-shrink:0;}
    .cmd-item-title{flex:1;font-size:.88rem;font-weight:500;}.cmd-item-cat{font-size:.68rem;color:var(--text-muted);}
    .cmd-empty{padding:40px;text-align:center;color:var(--text-muted);}
    .cmd-footer{display:flex;gap:16px;padding:10px 18px;border-top:1px solid rgba(255,255,255,.05);font-size:.7rem;color:var(--text-muted);}
    .cmd-footer span{display:inline-flex;align-items:center;gap:4px;}
    /* ═══ NAV SEARCH BUTTON ═══ */
    .nav-search-btn{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);border-radius:8px;color:var(--text-muted);cursor:pointer;display:inline-flex;align-items:center;gap:8px;padding:7px 14px;font-size:.82rem;transition:all .2s;font-family:inherit;}
    .nav-search-btn:hover{background:rgba(255,255,255,.08);color:#fff;border-color:rgba(255,255,255,.15);}
    .mob-search-wrap{padding:12px 24px 4px;position:relative;}
    .mob-search-input{width:100%;padding:10px 14px 10px 36px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);border-radius:8px;color:#fff;font-size:.9rem;font-family:inherit;outline:none;}
    .mob-search-input:focus{border-color:var(--purple);background:rgba(255,255,255,.08);}
    .mob-search-wrap i{position:absolute;left:38px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:.85rem;pointer-events:none;}
    @media(max-width:768px){
        .mega-panel,.mega-backdrop{display:none!important;}
        .nav-search-btn .cmd-kbd{display:none;}
        .nav-search-btn{padding:7px 10px;}
    }
    </style>
    
    <!-- Schema.org Structured Data (SEO) - max visibility -->
    <?php
    $schema_org = [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => 'GoSiteMe',
        'url' => 'https://gositeme.com',
        'logo' => 'https://gositeme.com/brand/logo.png',
        'image' => $page_og_image,
        'description' => $page_description,
        'knowsAbout' => ['Web Hosting', 'WordPress Hosting', 'AI Website Builder', 'Domain Registration', 'Website Builder', 'No-Code Development', 'Cloud Hosting', 'VPS Hosting', 'Dedicated Servers', 'SSL Certificates', 'Website Migration'],
        'slogan' => 'Build stunning websites in 60 seconds',
        'aggregateRating' => ['@type' => 'AggregateRating', 'ratingValue' => '4.9', 'bestRating' => '5', 'worstRating' => '1', 'ratingCount' => '2847', 'reviewCount' => '2847'],
        'hasOfferCatalog' => [
            '@type' => 'OfferCatalog',
            'name' => 'GoSiteMe Web Hosting & AI Builder',
            'itemListElement' => [
                ['@type' => 'Offer', 'itemOffered' => ['@type' => 'Service', 'name' => 'WordPress Hosting']],
                ['@type' => 'Offer', 'itemOffered' => ['@type' => 'Service', 'name' => 'AI Website Builder']],
                ['@type' => 'Offer', 'itemOffered' => ['@type' => 'Service', 'name' => 'Domain Registration']],
                ['@type' => 'Offer', 'itemOffered' => ['@type' => 'Service', 'name' => 'Dedicated Servers']]
            ]
        ],
        'contactPoint' => [
            '@type' => 'ContactPoint',
            'telephone' => '+1-833-467-4836',
            'contactType' => 'customer service',
            'areaServed' => ['US', 'CA'],
            'availableLanguage' => ['English', 'French'],
            'contactOption' => 'TollFree',
            'hoursAvailable' => ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'], 'opens' => '00:00', 'closes' => '23:59']
        ],
        'foundingDate' => '2025',
        'sameAs' => ['https://gocodeme.com']
    ];
    $schema_website = [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => 'GoSiteMe',
        'alternateName' => 'GoSiteMe Web Hosting',
        'url' => 'https://gositeme.com',
        'description' => $page_description,
        'publisher' => ['@type' => 'Organization', 'name' => 'GoSiteMe', 'logo' => ['@type' => 'ImageObject', 'url' => 'https://gositeme.com/brand/logo.png']],
        'inLanguage' => ['en', 'fr'],
        'potentialAction' => ['@type' => 'SearchAction', 'target' => ['@type' => 'EntryPoint', 'urlTemplate' => 'https://gositeme.com/?s={search_term_string}'], 'query-input' => 'required name=search_term_string']
    ];
    $schema_software = [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'GoCodeMe',
        'applicationCategory' => 'DeveloperApplication',
        'operatingSystem' => 'Web, Windows, macOS, Linux',
        'url' => 'https://gositeme.com/gocodeme.php',
        'description' => 'AI-powered development platform. Full IDE with AI assistant, 13,000+ tools, 17 AI engines. Voice-controlled coding, AI image generation, e-commerce, SEO, DevOps, design, accessibility, customer intelligence and more.',
        'offers' => ['@type' => 'AggregateOffer', 'lowPrice' => '5', 'highPrice' => '199', 'priceCurrency' => 'USD', 'url' => 'https://gositeme.com/cart?a=add&pid=18', 'offerCount' => '12'],
        'author' => ['@type' => 'Organization', 'name' => 'GoSiteMe']
    ];
    ?>
    <?php
    $schema_nav = [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => 'Main navigation',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'item' => ['@type' => 'SiteNavigationElement', 'name' => 'AI Development Platform', 'url' => 'https://gositeme.com/gocodeme.php']],
            ['@type' => 'ListItem', 'position' => 2, 'item' => ['@type' => 'SiteNavigationElement', 'name' => 'Alfred AI Assistant', 'url' => 'https://gositeme.com/alfred.php']],
            ['@type' => 'ListItem', 'position' => 3, 'item' => ['@type' => 'SiteNavigationElement', 'name' => 'Domain Registration', 'url' => 'https://gositeme.com/store/hosting']],
            ['@type' => 'ListItem', 'position' => 4, 'item' => ['@type' => 'SiteNavigationElement', 'name' => 'Pricing', 'url' => 'https://gositeme.com/pricing.php']],
            ['@type' => 'ListItem', 'position' => 5, 'item' => ['@type' => 'SiteNavigationElement', 'name' => 'Contact & Support', 'url' => 'https://gositeme.com/contact']]
        ]
    ];
    ?>
    <script type="application/ld+json"><?php echo json_encode($schema_org, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
    <script type="application/ld+json"><?php echo json_encode($schema_website, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
    <script type="application/ld+json"><?php echo json_encode($schema_software, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
    <script type="application/ld+json"><?php echo json_encode($schema_nav, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
    <!-- Alfred Widget v9 -->
    <?php $awVer = '9.6.0'; ?>
    <link rel="stylesheet" href="/assets/css/alfred-widget.min.css?v=<?php echo $awVer; ?>">
    <style>@media (display-mode: standalone){.aw-trigger{display:flex !important;}}</style>
    <script>
    // Alfred Voice Widget config (injected by PHP)
    // window.AW_WS_URL removed — JS uses dynamic location-based URL
    window.AW_AUTH_TOKEN = "<?php
        $aw_uid = $_SESSION['uid'] ?? $_SESSION['client_id'] ?? null;
        $aw_hmac_secret = getenv('ALFRED_HMAC_SECRET')
            ?: (defined('ALFRED_HMAC_SECRET') ? ALFRED_HMAC_SECRET : '')
            ?: 'gositeme-alfred-hmac-2026';
            echo $aw_uid ? hash_hmac('sha256', session_id() . '|' . $aw_uid, $aw_hmac_secret) : ''; 
    ?>";
    window.AW_USERNAME = "<?php echo htmlspecialchars($_SESSION['username'] ?? $_SESSION['client_name'] ?? 'Guest', ENT_QUOTES); ?>";
    window.AW_CSRF_TOKEN = "<?php if (!isset($_SESSION['alfred_csrf'])) $_SESSION['alfred_csrf'] = bin2hex(random_bytes(32)); $_SESSION['csrf_token'] = $_SESSION['alfred_csrf']; echo $_SESSION['alfred_csrf']; ?>";
    window.AW_USER_ID = "<?php echo $_SESSION['uid'] ?? $_SESSION['client_id'] ?? ''; ?>";
    window.AW_PAGE_CONTEXT = "<?php echo basename($_SERVER['SCRIPT_NAME'], '.php'); ?>";
    </script>
    <script src="/assets/js/alfred-widget.min.js?v=<?php echo $awVer; ?>" defer></script>
    <script src="/assets/js/alfred-events.js?v=<?php echo $awVer; ?>" defer></script>
    <script src="/assets/js/gds-utils.js?v=20260310" defer></script>
    <script src="/assets/js/gds-toast.js?v=20260310" defer></script>
    <script src="/assets/js/gds-modal.js?v=20260310" defer></script>
<?php if (!empty($pageCss)): foreach ((array)$pageCss as $pcss): ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($pcss); ?>">
<?php endforeach; endif; ?>
</head>
<body>
    <!-- Urgency Banner -->
    <div class="urgency-banner" id="urgencyBanner">
        <p>
            <i class="fas fa-fire"></i>
            <strong><?php echo L('urgency_limited'); ?></strong> <?php echo L('urgency_offer'); ?> <span class="countdown">LAUNCH50</span>
            <a href="/cart?promo=LAUNCH50" style="color: #fff; margin-left: 12px; text-decoration: underline;"><?php echo L('urgency_claim'); ?></a>
        </p>
        <button onclick="document.body.classList.add('banner-hidden');localStorage.setItem('bannerDismissed','1');" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:rgba(255,255,255,.7);cursor:pointer;font-size:1.1rem;padding:4px 8px;" aria-label="Dismiss">&times;</button>
    </div>
    <script>if(localStorage.getItem('bannerDismissed')==='1')document.body.classList.add('banner-hidden');</script>

    <!-- Skip to main content (WCAG 2.4.1) -->
    <a href="#main" class="skip-link">Skip to main content</a>
    <style>.skip-link{position:absolute;top:-100%;left:1rem;z-index:10000;padding:.75rem 1.5rem;background:var(--gds-color-cyan,#06b6d4);color:#000;font-weight:700;border-radius:0 0 .5rem .5rem;text-decoration:none;transition:top .2s}.skip-link:focus{top:0}</style>

    <!-- Background Elements -->
    <div class="bg-grid"></div>
    <div class="bg-glow bg-glow-1"></div>
    <div class="bg-glow bg-glow-2"></div>

    <!-- Navigation -->
    <header>
    <nav class="navbar" id="navbar" role="navigation" aria-label="<?php echo $html_lang === 'fr' ? 'Navigation principale' : 'Main navigation'; ?>">
        <div class="container">
            <a href="/" class="logo">
                <span class="logo-flip" aria-hidden="true">
                    <span class="logo-flip-inner">
                        <span class="logo-flip-front"><img src="/brand/logo_w.png" alt="GoSiteMe" style="height:38px;width:auto;"></span>
                        <span class="logo-flip-back"><img src="/brand/gocodeme-icon.svg" alt="GoCodeMe" style="height:32px;width:32px;border-radius:8px;"><span style="font-size:.85rem;font-weight:700;background:linear-gradient(135deg,#10b981,#34d399);-webkit-background-clip:text;-webkit-text-fill-color:transparent;margin-left:6px;">GoCodeMe</span></span>
                    </span>
                </span>
                <img src="/brand/logo_w.png" alt="GoSiteMe - Best Web Hosting &amp; AI Website Builder" class="logo-main-img">
            </a>
            
            <ul class="nav-links">
                <li class="mega-trigger" data-mega="products">
                    <a href="/gocodeme.php" class="nav-gocodeme"><i class="fas fa-wand-magic-sparkles" style="margin-right:6px"></i><?php echo L('nav_gocodeme_editor'); ?> <i class="fas fa-chevron-down mega-chev"></i></a>
                </li>
                <li class="mega-trigger" data-mega="ai">
                    <a href="/alfred.php">Alfred AI <i class="fas fa-chevron-down mega-chev"></i></a>
                </li>
                <li class="mega-trigger" data-mega="hosting">
                    <a href="#"><?php echo L('nav_hosting'); ?> <i class="fas fa-chevron-down mega-chev"></i></a>
                </li>
                <li class="mega-trigger" data-mega="devs">
                    <a href="/developer-portal.php">Developers <i class="fas fa-chevron-down mega-chev"></i></a>
                </li>
                <li>
                    <a href="/pricing.php" style="color:#00b894;font-weight:700;"><i class="fas fa-tags" style="margin-right:4px;"></i><?php echo L('nav_pricing'); ?></a>
                </li>
                <li>
                    <a href="/universe.php" style="background:linear-gradient(135deg,#7c5ce7,#22d3ee);-webkit-background-clip:text;-webkit-text-fill-color:transparent;font-weight:700;"><i class="fas fa-atom" style="margin-right:4px;-webkit-text-fill-color:#7c5ce7;"></i>Universe</a>
                </li>
                <li class="mega-trigger" data-mega="resources">
                    <a href="/about.php">More <i class="fas fa-chevron-down mega-chev"></i></a>
                </li>
                <li><button class="nav-search-btn" id="cmdOpenBtn" aria-label="Search pages"><i class="fas fa-search"></i> <span class="cmd-kbd">⌘K</span></button></li>
                <li><a href="<?php echo htmlspecialchars(billing_link('cart.php')); ?>" class="nav-cart" style="position:relative;"><i class="fas fa-shopping-cart"></i> Cart<?php $__cartCount = count($_SESSION['billing_cart'] ?? []); ?><span class="site-cart-badge" <?= $__cartCount <= 0 ? 'style="display:none;"' : '' ?>><?= $__cartCount ?></span></a></li>
            </ul>
            
            <div class="nav-cta">
                <?php if (!empty($_SESSION['logged_in']) && !empty($_SESSION['client_id'])): ?>
                <!-- Notification Bell -->
                <div class="notif-bell-wrap" style="position:relative;margin-right:14px;display:inline-block;">
                    <button id="notifBellBtn" aria-label="Notifications" style="background:none;border:none;color:inherit;cursor:pointer;font-size:1.15rem;position:relative;padding:4px;">
                        <i class="fas fa-bell"></i>
                        <span id="notifBadge" style="display:none;position:absolute;top:-4px;right:-6px;background:#e17055;color:#fff;font-size:0.65rem;font-weight:700;min-width:16px;height:16px;border-radius:50%;text-align:center;line-height:16px;padding:0 4px;">0</span>
                    </button>
                    <div id="notifDropdown" style="display:none;position:absolute;right:0;top:calc(100% + 8px);width:340px;max-height:420px;background:#12121e;border:1px solid rgba(255,255,255,0.08);border-radius:12px;box-shadow:0 12px 36px rgba(0,0,0,0.5);z-index:9999;overflow:hidden;">
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:14px 16px;border-bottom:1px solid rgba(255,255,255,0.06);">
                            <strong style="font-size:0.9rem;">Notifications</strong>
                            <button id="notifMarkAllRead" style="background:none;border:none;color:#a29bfe;cursor:pointer;font-size:0.78rem;">Mark all read</button>
                        </div>
                        <div id="notifList" style="max-height:320px;overflow-y:auto;padding:6px 0;"></div>
                        <div style="padding:10px 16px;border-top:1px solid rgba(255,255,255,0.06);text-align:center;">
                            <a href="/dashboard.php#notifications" style="color:#a29bfe;text-decoration:none;font-size:0.82rem;">View All Notifications</a>
                        </div>
                    </div>
                </div>
                <script>
                (function(){
                    const bell = document.getElementById('notifBellBtn');
                    const dropdown = document.getElementById('notifDropdown');
                    const badge = document.getElementById('notifBadge');
                    const list = document.getElementById('notifList');
                    let open = false;

                    function toggleDropdown(e) {
                        e.stopPropagation();
                        open = !open;
                        dropdown.style.display = open ? 'block' : 'none';
                        if (open) loadNotifications();
                    }

                    bell.addEventListener('click', toggleDropdown);
                    document.addEventListener('click', function(e) {
                        if (open && !dropdown.contains(e.target) && e.target !== bell) {
                            open = false;
                            dropdown.style.display = 'none';
                        }
                    });

                    async function fetchNotifCount() {
                        try {
                            const r = await fetch('/api/notifications.php?action=unread-count');
                            const d = await r.json();
                            if (d.success && d.count > 0) {
                                badge.textContent = d.count > 99 ? '99+' : d.count;
                                badge.style.display = 'block';
                            } else {
                                badge.style.display = 'none';
                            }
                        } catch(e) {}
                    }

                    async function loadNotifications() {
                        try {
                            const r = await fetch('/api/notifications.php?action=list&per_page=10');
                            const d = await r.json();
                            if (!d.success || !d.notifications.length) {
                                list.innerHTML = '<div style="padding:24px;text-align:center;color:#8a8ab0;font-size:0.85rem;"><i class="fas fa-bell-slash" style="display:block;font-size:1.5rem;margin-bottom:8px;opacity:0.4;"></i>No notifications</div>';
                                return;
                            }
                            list.innerHTML = d.notifications.map(n => {
                                const icons = {info:'fa-info-circle',success:'fa-check-circle',warning:'fa-exclamation-triangle',error:'fa-times-circle',billing:'fa-credit-card',security:'fa-shield-alt',system:'fa-cog'};
                                const colors = {info:'#0984e3',success:'#00b894',warning:'#fdcb6e',error:'#e17055',billing:'#a29bfe',security:'#fd79a8',system:'#636e72'};
                                const ico = icons[n.type] || 'fa-bell';
                                const col = colors[n.type] || '#8a8ab0';
                                const unread = !n.is_read ? 'border-left:3px solid '+col+';' : '';
                                return '<div style="padding:10px 16px;'+unread+'cursor:pointer;transition:background 0.15s;" onmouseenter="this.style.background=\'rgba(255,255,255,0.03)\'" onmouseleave="this.style.background=\'none\'" data-id="'+n.id+'"><div style="display:flex;gap:10px;align-items:flex-start;"><i class="fas '+ico+'" style="color:'+col+';margin-top:3px;font-size:0.85rem;"></i><div style="flex:1;min-width:0;"><div style="font-size:0.82rem;font-weight:'+(n.is_read?'400':'600')+';color:#e0e0e0;">'+escH(n.title)+'</div><div style="font-size:0.75rem;color:#8a8ab0;margin-top:2px;">'+escH(n.message||'').substring(0,80)+'</div></div></div></div>';
                            }).join('');
                        } catch(e) { list.innerHTML = '<div style="padding:16px;text-align:center;color:#8a8ab0;">Error loading</div>'; }
                    }

                    document.getElementById('notifMarkAllRead').addEventListener('click', async function(e) {
                        e.stopPropagation();
                        try {
                            await fetch('/api/notifications.php?action=mark-read', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:'all'})});
                            badge.style.display = 'none';
                            loadNotifications();
                        } catch(e) {}
                    });

                    function escH(s) { const d=document.createElement('div'); d.textContent=s||''; return d.innerHTML; }

                    fetchNotifCount();
                    setInterval(fetchNotifCount, 60000);
                })();
                </script>
                <?php endif; ?>

                <span class="lang-switcher" style="margin-right:12px;font-size:0.9rem;" aria-label="<?php echo $html_lang === 'fr' ? 'Changer la langue' : 'Change language'; ?>">
                    <a href="?language=<?php echo $current_lang === 'fr' ? 'english' : 'french'; ?>" rel="nofollow" style="color:inherit;text-decoration:none;"><?php echo $current_lang === 'fr' ? 'EN' : 'FR'; ?></a>
                </span>
                <?php if (empty($_SESSION['logged_in'])): ?>
                <a href="/login" class="btn btn-ghost nav-login-btn">
                    <i class="fas fa-user"></i> <?php echo L('nav_login'); ?>
                </a>
                <a href="/register" class="btn btn-primary nav-register-btn"><?php echo L('nav_get_started'); ?></a>
                <?php else: ?>
                <!-- Logged-in user profile dropdown -->
                <?php
                    $navUserName = htmlspecialchars($_SESSION['client_name'] ?? $_SESSION['username'] ?? 'Account', ENT_QUOTES, 'UTF-8');
                    $navUserInitials = '';
                    $nameParts = explode(' ', trim($_SESSION['client_name'] ?? $_SESSION['username'] ?? 'U'));
                    $navUserInitials = strtoupper(substr($nameParts[0], 0, 1));
                    if (count($nameParts) > 1) $navUserInitials .= strtoupper(substr(end($nameParts), 0, 1));
                ?>
                <div class="nav-user-wrap" style="position:relative;display:inline-block;">
                    <button id="navUserBtn" style="display:flex;align-items:center;gap:8px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);color:#fff;cursor:pointer;padding:5px 12px 5px 5px;border-radius:999px;font-size:0.85rem;font-weight:600;transition:all 0.2s;" onmouseenter="this.style.borderColor='rgba(125,0,255,0.4)'" onmouseleave="this.style.borderColor='rgba(255,255,255,0.1)'">
                        <span style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#7d00ff,#00d4ff);display:flex;align-items:center;justify-content:center;font-size:0.7rem;font-weight:700;color:#fff;flex-shrink:0;"><?php echo $navUserInitials; ?></span>
                        <?php echo $navUserName; ?>
                        <i class="fas fa-chevron-down" style="font-size:0.6rem;opacity:0.6;"></i>
                    </button>
                    <div id="navUserDropdown" style="display:none;position:absolute;right:0;top:calc(100% + 8px);width:220px;background:#12121e;border:1px solid rgba(255,255,255,0.08);border-radius:12px;box-shadow:0 12px 36px rgba(0,0,0,0.5);z-index:9999;overflow:hidden;padding:6px 0;">
                        <a href="/dashboard.php" style="display:flex;align-items:center;gap:10px;padding:10px 16px;color:#e0e0e0;text-decoration:none;font-size:0.85rem;transition:background 0.15s;" onmouseenter="this.style.background='rgba(255,255,255,0.04)'" onmouseleave="this.style.background='none'"><i class="fas fa-tachometer-alt" style="width:16px;text-align:center;color:#7d00ff;"></i> Dashboard</a>
                        <a href="/conversations" style="display:flex;align-items:center;gap:10px;padding:10px 16px;color:#e0e0e0;text-decoration:none;font-size:0.85rem;transition:background 0.15s;" onmouseenter="this.style.background='rgba(255,255,255,0.04)'" onmouseleave="this.style.background='none'"><i class="fas fa-robot" style="width:16px;text-align:center;color:#00d4ff;"></i> Alfred AI</a>
                        <a href="/middleware/dashboard" style="display:flex;align-items:center;gap:10px;padding:10px 16px;color:#e0e0e0;text-decoration:none;font-size:0.85rem;transition:background 0.15s;" onmouseenter="this.style.background='rgba(255,255,255,0.04)'" onmouseleave="this.style.background='none'"><i class="fas fa-code" style="width:16px;text-align:center;color:#10b981;"></i> GoCodeMe</a>
                        <a href="/profile" style="display:flex;align-items:center;gap:10px;padding:10px 16px;color:#e0e0e0;text-decoration:none;font-size:0.85rem;transition:background 0.15s;" onmouseenter="this.style.background='rgba(255,255,255,0.04)'" onmouseleave="this.style.background='none'"><i class="fas fa-user-cog" style="width:16px;text-align:center;color:#a29bfe;"></i> Profile</a>
                        <div style="border-top:1px solid rgba(255,255,255,0.06);margin:4px 0;"></div>
                        <a href="/login.php?logout=1" style="display:flex;align-items:center;gap:10px;padding:10px 16px;color:#e17055;text-decoration:none;font-size:0.85rem;transition:background 0.15s;" onmouseenter="this.style.background='rgba(255,255,255,0.04)'" onmouseleave="this.style.background='none'"><i class="fas fa-sign-out-alt" style="width:16px;text-align:center;"></i> Sign Out</a>
                    </div>
                </div>
                <script>
                (function(){
                    const btn = document.getElementById('navUserBtn');
                    const dd = document.getElementById('navUserDropdown');
                    let open = false;
                    btn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        open = !open;
                        dd.style.display = open ? 'block' : 'none';
                    });
                    document.addEventListener('click', function(e) {
                        if (open && !dd.contains(e.target)) {
                            open = false;
                            dd.style.display = 'none';
                        }
                    });
                })();
                </script>
                <?php endif; ?>
            </div>
            
            <button class="mobile-toggle" id="mobileToggle" aria-label="Open menu">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <!-- Mega Menu Backdrop -->
    <div class="mega-backdrop" id="megaBackdrop"></div>

    <!-- Mega Panel: Products -->
    <div class="mega-panel" id="mega-products">
        <div class="mega-cols mega-cols-3">
            <div>
                <div class="mega-heading">Platform</div>
                <a href="/middleware/dashboard" class="mega-item"><i class="fas fa-globe" style="color:#06b6d4"></i><div><div class="mi-t"><?php echo L('nav_use_online'); ?></div><div class="mi-d">Launch the IDE in your browser</div></div></a>
                <a href="/gocodeme.php" class="mega-item"><i class="fas fa-download" style="color:#a78bfa"></i><div><div class="mi-t"><?php echo L('nav_download_app'); ?></div><div class="mi-d">Desktop app for Windows, Mac, Linux</div></div></a>
                <a href="/alfred.php" class="mega-item"><i class="fas fa-robot" style="color:#06b6d4"></i><div><div class="mi-t">Alfred AI</div><div class="mi-d">13,000+ tools · 50M+ agents · 17 engines</div></div></a>
                <a href="/dashboard.php" class="mega-item"><i class="fas fa-gauge-high" style="color:#34d399"></i><div><div class="mi-t">Dashboard</div><div class="mi-d">Your account overview</div></div></a>
                <a href="/live-demo.php" class="mega-item"><i class="fas fa-play" style="color:#f59e0b"></i><div><div class="mi-t">Live Demo</div><div class="mi-d">See the ecosystem in action</div></div></a>
            </div>
            <div>
                <div class="mega-heading">Voice & AI</div>
                <a href="/voice-products.php" class="mega-item"><i class="fas fa-phone-volume" style="color:#a78bfa"></i><div><div class="mi-t">Voice Products</div><div class="mi-d">Voice AI suite overview</div></div></a>
                <a href="/voice-cloning.php" class="mega-item"><i class="fas fa-microphone-lines" style="color:#f472b6"></i><div><div class="mi-t">Voice Cloning</div><div class="mi-d">Clone voices with AI</div></div></a>
                <a href="/alfred-voice-live/" class="mega-item"><i class="fas fa-terminal" style="color:#34d399"></i><div><div class="mi-t">Command Center</div><div class="mi-d">Voice-activated operations hub</div></div></a>
            </div>
            <div>
                <div class="mega-heading">Experiences</div>
                <a href="/pulse.php" class="mega-item"><i class="fas fa-bolt" style="color:#3b82f6"></i><div><div class="mi-t">Pulse</div><div class="mi-d">Social network &amp; ecosystem hub</div></div></a>
                <a href="/vr/hub/" class="mega-item"><i class="fas fa-vr-cardboard" style="color:#a855f7"></i><div><div class="mi-t">VR World</div><div class="mi-d">Immersive 3D environments</div></div></a>
                <a href="/vr/chess/" class="mega-item"><i class="fas fa-chess" style="color:#a855f7"></i><div><div class="mi-t">Chess Arena</div><div class="mi-d">Play AI agents in VR</div></div></a>
                <a href="/games.php" class="mega-item"><i class="fas fa-gamepad" style="color:#f59e0b"></i><div><div class="mi-t">Games & Arcade</div><div class="mi-d">Browser games collection</div></div></a>
                <a href="/templates/" class="mega-item"><i class="fas fa-layer-group"></i><div><div class="mi-t">Website Templates</div></div></a>
                <a href="/open-source/" class="mega-item"><i class="fas fa-lock-open"></i><div><div class="mi-t">Open-Source Tools</div></div></a>
            </div>
        </div>
    </div>

    <!-- Mega Panel: AI & Tools -->
    <div class="mega-panel" id="mega-ai">
        <div class="mega-cols mega-cols-3">
            <div>
                <div class="mega-heading">Core</div>
                <a href="/tools/" class="mega-item"><i class="fas fa-toolbox" style="color:#06b6d4"></i><div><div class="mi-t">Tool Directory</div><div class="mi-d">Browse 13,000+ AI tools</div></div></a>
                <a href="/marketplace.php" class="mega-item"><i class="fas fa-store" style="color:#a78bfa"></i><div><div class="mi-t"><?php echo L('footer_marketplace'); ?></div><div class="mi-d">Extensions, templates & more</div></div></a>
                <a href="/search.php" class="mega-item"><i class="fas fa-magnifying-glass" style="color:#f472b6"></i><div><div class="mi-t">Alfred Search</div><div class="mi-d">Sovereign search engine</div></div></a>
                <a href="/agentpedia.php" class="mega-item"><i class="fas fa-book-open" style="color:#f59e0b"></i><div><div class="mi-t">AgentPedia</div><div class="mi-d">AI-written knowledge base</div></div></a>
                <a href="/pricing.php" class="mega-item"><i class="fas fa-tags" style="color:#34d399"></i><div><div class="mi-t"><?php echo L('nav_pricing'); ?></div><div class="mi-d">Plans & token packs</div></div></a>
                <a href="/use-cases/" class="mega-item"><i class="fas fa-users"></i><div><div class="mi-t"><?php echo L('nav_use_cases'); ?></div><div class="mi-d">33 industries</div></div></a>
                <a href="/compare.php" class="mega-item"><i class="fas fa-columns"></i><div><div class="mi-t"><?php echo L('footer_compare'); ?></div></div></a>
            </div>
            <div>
                <div class="mega-heading">Features</div>
                <a href="/fleet-dashboard.php" class="mega-item"><i class="fas fa-satellite-dish" style="color:#06b6d4"></i><div><div class="mi-t">Fleet Dashboard</div><div class="mi-d">Manage AI agent fleet</div></div></a>
                <a href="/agent-orchestrator.php" class="mega-item"><i class="fas fa-gears" style="color:#ff6b00"></i><div><div class="mi-t">Agent Orchestrator</div><div class="mi-d">Deploy coding agents</div></div></a>
                <a href="/conference-room.php" class="mega-item"><i class="fas fa-headset" style="color:#a78bfa"></i><div><div class="mi-t">Conference Rooms</div><div class="mi-d">AI-powered meetings</div></div></a>
                <a href="/team-chat.php" class="mega-item"><i class="fas fa-users-rectangle" style="color:#34d399"></i><div><div class="mi-t">Team Chat</div><div class="mi-d">Collaborate & negotiate</div></div></a>
                <a href="/voice-cloning.php" class="mega-item"><i class="fas fa-microphone-lines"></i><div><div class="mi-t">Voice Cloning</div></div></a>
            </div>
            <div>
                <div class="mega-heading">Workflow</div>
                <a href="/agent-templates.php" class="mega-item"><i class="fas fa-clone" style="color:#f472b6"></i><div><div class="mi-t">Agent Templates</div><div class="mi-d">Pre-built AI agents</div></div></a>
                <a href="/ivr-builder.php" class="mega-item"><i class="fas fa-diagram-project" style="color:#f59e0b"></i><div><div class="mi-t">IVR Builder</div><div class="mi-d">Visual call flow designer</div></div></a>
                <a href="/conversations.php" class="mega-item"><i class="fas fa-comments"></i><div><div class="mi-t">Conversations</div></div></a>
                <a href="/analytics.php" class="mega-item"><i class="fas fa-chart-line"></i><div><div class="mi-t">Analytics</div></div></a>
                <a href="/team.php" class="mega-item"><i class="fas fa-users-gear"></i><div><div class="mi-t">Team Workspace</div></div></a>
                <a href="/marketplace-creator" class="mega-item"><i class="fas fa-paint-brush"></i><div><div class="mi-t">Creator Dashboard</div></div></a>
                <a href="/call-campaigns.php" class="mega-item"><i class="fas fa-bullhorn"></i><div><div class="mi-t">Call Campaigns</div></div></a>
            </div>
        </div>
    </div>

    <!-- Mega Panel: Hosting -->
    <div class="mega-panel" id="mega-hosting">
        <div class="mega-cols mega-cols-2">
            <div>
                <div class="mega-heading">Hosting & Domains</div>
                <a href="<?php echo htmlspecialchars(billing_link('store/ai-domain-hosting-connected-with-ai-editor')); ?>" class="mega-item"><i class="fas fa-robot" style="color:#06b6d4"></i><div><div class="mi-t"><?php echo L('nav_ai_hosting'); ?></div><div class="mi-d">AI-powered web hosting with editor</div></div></a>
                <a href="<?php echo htmlspecialchars(billing_link('store/ai-domain-hosting-connected-with-ai-editor')); ?>" class="mega-item"><i class="fas fa-globe-americas" style="color:#a78bfa"></i><div><div class="mi-t"><?php echo L('nav_domains'); ?></div><div class="mi-d">Register & transfer domains</div></div></a>
                <a href="<?php echo htmlspecialchars(billing_link('store/token-packs')); ?>" class="mega-item"><i class="fas fa-coins" style="color:#f59e0b"></i><div><div class="mi-t">Token Packs</div><div class="mi-d">AI usage credits</div></div></a>
                <a href="<?php echo htmlspecialchars(billing_link('store/ssl-certificates')); ?>" class="mega-item"><i class="fas fa-lock" style="color:#34d399"></i><div><div class="mi-t">SSL Certificates</div><div class="mi-d">Secure your site</div></div></a>
            </div>
            <div>
                <div class="mega-heading">Infrastructure</div>
                <a href="/ai-servers/" class="mega-item"><i class="fas fa-microchip" style="color:#06b6d4"></i><div><div class="mi-t"><?php echo L('nav_ai_servers'); ?></div><div class="mi-d">GPU-powered dedicated servers</div></div></a>
                <a href="<?php echo htmlspecialchars(billing_link('store/training')); ?>" class="mega-item"><i class="fas fa-graduation-cap" style="color:#a78bfa"></i><div><div class="mi-t">Training</div><div class="mi-d">Learn to build & manage</div></div></a>
                <a href="/white-label.php" class="mega-item"><i class="fas fa-tag" style="color:#f472b6"></i><div><div class="mi-t">White Label</div><div class="mi-d">Resell under your brand</div></div></a>
                <a href="/status.php" class="mega-item"><i class="fas fa-signal" style="color:#34d399"></i><div><div class="mi-t">System Status</div><div class="mi-d">Uptime & incidents</div></div></a>
            </div>
        </div>
    </div>

    <!-- Mega Panel: Developers -->
    <div class="mega-panel" id="mega-devs">
        <div class="mega-cols mega-cols-3">
            <div>
                <div class="mega-heading">Get Started</div>
                <a href="/developer-portal.php" class="mega-item"><i class="fas fa-terminal" style="color:#06b6d4"></i><div><div class="mi-t">Developer Portal</div><div class="mi-d">Build on the platform</div></div></a>
                <a href="/docs/getting-started" class="mega-item"><i class="fas fa-play-circle" style="color:#34d399"></i><div><div class="mi-t">Getting Started</div><div class="mi-d">Quick start guide</div></div></a>
                <a href="/docs/" class="mega-item"><i class="fas fa-book" style="color:#a78bfa"></i><div><div class="mi-t"><?php echo L('footer_docs'); ?></div><div class="mi-d">Full reference docs</div></div></a>
                <a href="/changelog.php" class="mega-item"><i class="fas fa-clipboard-list"></i><div><div class="mi-t"><?php echo L('footer_changelog'); ?></div></div></a>
            </div>
            <div>
                <div class="mega-heading">APIs & SDKs</div>
                <a href="/docs/api-reference" class="mega-item"><i class="fas fa-code" style="color:#06b6d4"></i><div><div class="mi-t">API Reference</div><div class="mi-d">RESTful API docs</div></div></a>
                <a href="/sdks" class="mega-item"><i class="fas fa-cube" style="color:#a78bfa"></i><div><div class="mi-t">SDKs</div><div class="mi-d">Python, Node, PHP, Go & more</div></div></a>
                <a href="/webhooks.php" class="mega-item"><i class="fas fa-plug" style="color:#f59e0b"></i><div><div class="mi-t">Webhooks</div><div class="mi-d">Event-driven integrations</div></div></a>
                <?php if (!empty($_SESSION['logged_in']) && !empty($_SESSION['client_id'])): ?>
                <a href="/api/oauth.php?action=apps" class="mega-item"><i class="fas fa-key" style="color:#34d399"></i><div><div class="mi-t">OAuth Apps</div><div class="mi-d">Manage your OAuth clients</div></div></a>
                <?php endif; ?>
            </div>
            <div>
                <div class="mega-heading">Extend</div>
                <a href="/extensions.php" class="mega-item"><i class="fas fa-puzzle-piece" style="color:#f472b6"></i><div><div class="mi-t">Extensions</div><div class="mi-d">Browse & install plugins</div></div></a>
                <a href="/integrations.php" class="mega-item"><i class="fas fa-link" style="color:#06b6d4"></i><div><div class="mi-t">Integrations</div><div class="mi-d">Connect third-party services</div></div></a>
                <a href="/languages.php" class="mega-item"><i class="fas fa-language" style="color:#a78bfa"></i><div><div class="mi-t">Languages</div><div class="mi-d">300+ language support</div></div></a>
            </div>
        </div>
    </div>

    <!-- Mega Panel: Resources -->
    <div class="mega-panel" id="mega-resources">
        <div class="mega-cols mega-cols-3">
            <div>
                <div class="mega-heading">Company</div>
                <a href="/about.php" class="mega-item"><i class="fas fa-info-circle" style="color:#06b6d4"></i><div><div class="mi-t"><?php echo L('footer_about'); ?></div></div></a>
                <a href="/enterprise.php" class="mega-item"><i class="fas fa-building" style="color:#a78bfa"></i><div><div class="mi-t"><?php echo L('footer_enterprise'); ?></div><div class="mi-d">Custom plans for organizations</div></div></a>
                <a href="/careers.php" class="mega-item"><i class="fas fa-briefcase" style="color:#f59e0b"></i><div><div class="mi-t">Careers</div><div class="mi-d">Join the ecosystem</div></div></a>
                <a href="/ecosystem.php" class="mega-item"><i class="fas fa-globe" style="color:#06b6d4"></i><div><div class="mi-t">Ecosystem</div><div class="mi-d">The sovereign internet platform</div></div></a>
                <a href="/security.php" class="mega-item"><i class="fas fa-shield-halved" style="color:#34d399"></i><div><div class="mi-t">Security</div></div></a>
                <a href="/pulse.php" class="mega-item"><i class="fas fa-bolt" style="color:#3b82f6"></i><div><div class="mi-t">Pulse</div><div class="mi-d">Social network &amp; ecosystem hub</div></div></a>
                <a href="/veil/" class="mega-item"><i class="fas fa-comments" style="color:#8b5cf6"></i><div><div class="mi-t">Veil</div><div class="mi-d">Encrypted messaging &amp; payments</div></div></a>
                <a href="/post-quantum.php" class="mega-item"><i class="fas fa-atom" style="color:#f472b6"></i><div><div class="mi-t">Post-Quantum</div></div></a>
            </div>
            <div>
                <div class="mega-heading">Learn & Support</div>
                <a href="/chronicles.php" class="mega-item"><i class="fas fa-flask-vial" style="color:#d4a017"></i><div><div class="mi-t">Research Chronicles</div><div class="mi-d">Innovation lab & breakthroughs</div></div></a>
                <a href="/health-research.php" class="mega-item"><i class="fas fa-dna" style="color:#10b981"></i><div><div class="mi-t">Health Research</div><div class="mi-d">50K agents — ask any health question</div></div></a>
                <a href="/help.php" class="mega-item"><i class="fas fa-life-ring" style="color:#06b6d4"></i><div><div class="mi-t">Help Center</div><div class="mi-d">FAQs & guides</div></div></a>
                <a href="/articles/" class="mega-item"><i class="fas fa-newspaper" style="color:#a78bfa"></i><div><div class="mi-t"><?php echo L('footer_blog'); ?></div><div class="mi-d">Blog & tutorials</div></div></a>
                <a href="<?php echo htmlspecialchars(billing_link('contact.php')); ?>" class="mega-item"><i class="fas fa-envelope" style="color:#34d399"></i><div><div class="mi-t"><?php echo L('nav_support'); ?></div></div></a>
                <a href="<?php echo htmlspecialchars(billing_link('announcements')); ?>" class="mega-item"><i class="fas fa-bullhorn" style="color:#f59e0b"></i><div><div class="mi-t"><?php echo L('nav_news'); ?></div></div></a>
            </div>
            <div>
                <div class="mega-heading">Finance & Investment</div>
                <a href="/invest" class="mega-item" style="color:#55efc4"><i class="fas fa-chart-line" style="color:#55efc4"></i><div><div class="mi-t">Invest in GoSiteMe</div><div class="mi-d">Join our growth story</div></div></a>
                <a href="/affiliate.php" class="mega-item" style="color:#10b981"><i class="fas fa-dollar-sign" style="color:#10b981"></i><div><div class="mi-t">Affiliate Program</div><div class="mi-d">Earn 20% commission</div></div></a>
                <a href="/pay/account/crypto" class="mega-item"><i class="fas fa-chart-candlestick" style="color:#14F195"></i><div><div class="mi-t">Crypto Trading</div></div></a>
                <a href="/pay/account/gsm-token" class="mega-item"><i class="fas fa-coins" style="color:#14F195"></i><div><div class="mi-t">GSM Token</div></div></a>
                <a href="/mine.php" class="mega-item"><i class="fas fa-hammer" style="color:#f59e0b"></i><div><div class="mi-t">Mine GSM</div><div class="mi-d">Earn tokens while you browse</div></div></a>
                <a href="/wallet.php" class="mega-item"><i class="fas fa-wallet" style="color:#a78bfa"></i><div><div class="mi-t">Wallet</div><div class="mi-d">Balance, mining & transactions</div></div></a>
                <a href="/qgsm-whitepaper.php" class="mega-item"><i class="fas fa-scroll" style="color:#ec4899"></i><div><div class="mi-t">QGSM Whitepaper</div><div class="mi-d">Post-quantum cryptocurrency</div></div></a>
                <a href="/qgsm-bridge.php" class="mega-item"><i class="fas fa-bridge" style="color:#6366f1"></i><div><div class="mi-t">QGSM Bridge</div><div class="mi-d">Passport & earning gateway</div></div></a>
            </div>
        </div>
    </div>

    <!-- Command Palette (Ctrl+K / ⌘K) -->
    <div class="cmd-overlay" id="cmdOverlay">
        <div class="cmd-modal">
            <div class="cmd-input-wrap">
                <i class="fas fa-search"></i>
                <input type="text" class="cmd-input" id="cmdInput" placeholder="Search pages, tools, features..." autocomplete="off" spellcheck="false">
                <span class="cmd-kbd">ESC</span>
            </div>
            <div class="cmd-results" id="cmdResults"></div>
            <div class="cmd-footer">
                <span><span class="cmd-kbd">↑↓</span> Navigate</span>
                <span><span class="cmd-kbd">↵</span> Open</span>
                <span><span class="cmd-kbd">ESC</span> Close</span>
            </div>
        </div>
    </div>

    <!-- Mobile Menu Overlay -->
    <div class="mobile-menu-overlay" id="mobileOverlay"></div>
    
    <!-- Mobile Menu -->
    <div class="mobile-menu" id="mobileMenu">
        <button class="mobile-menu-close" id="mobileClose" aria-label="<?php echo $html_lang === 'fr' ? 'Fermer le menu' : 'Close menu'; ?>">
            <i class="fas fa-times"></i>
        </button>
        
        <!-- Mobile Search -->
        <div class="mob-search-wrap">
            <i class="fas fa-search"></i>
            <input type="text" class="mob-search-input" id="mobSearchInput" placeholder="Search all pages..." readonly>
        </div>
        <div class="mob-accordion">
            <button class="mob-accordion-btn"><i class="fas fa-server"></i> <?php echo L('nav_hosting'); ?> <i class="fas fa-chevron-down mob-chev"></i></button>
            <div class="mob-accordion-panel">
                <a href="<?php echo htmlspecialchars(billing_link('store/ai-domain-hosting-connected-with-ai-editor')); ?>"><i class="fas fa-robot"></i> <?php echo L('nav_ai_hosting'); ?></a>
                <a href="<?php echo htmlspecialchars(billing_link('store/token-packs')); ?>"><i class="fas fa-coins"></i> Token Packs</a>
                <a href="<?php echo htmlspecialchars(billing_link('store/ssl-certificates')); ?>"><i class="fas fa-lock"></i> SSL Certificates</a>
                <a href="<?php echo htmlspecialchars(billing_link('store/training')); ?>"><i class="fas fa-graduation-cap"></i> Training</a>
                <a href="<?php echo htmlspecialchars(billing_link('store/ai-server-support')); ?>"><i class="fas fa-headset"></i> Server Support</a>
                <a href="/ai-servers/"><i class="fas fa-microchip"></i> <?php echo L('nav_ai_servers'); ?></a>
            </div>
        </div>

        <!-- Mobile Accordion: GoCodeMe -->
        <div class="mob-accordion">
            <button class="mob-accordion-btn"><i class="fas fa-wand-magic-sparkles"></i> <?php echo L('nav_gocodeme_editor'); ?> <i class="fas fa-chevron-down mob-chev"></i></button>
            <div class="mob-accordion-panel">
                <a href="/middleware/dashboard"><i class="fas fa-globe"></i> <?php echo L('nav_use_online'); ?></a>
                <a href="/gocodeme.php"><i class="fas fa-download"></i> <?php echo L('nav_download_app'); ?></a>
                <a href="/alfred.php" style="color:#06b6d4;"><i class="fas fa-robot"></i> Alfred AI — 13,000+ Tools</a>
                <a href="/voice-products.php" style="color:#a78bfa;"><i class="fas fa-phone-volume"></i> Voice & AI Products</a>
                <a href="/vr/hub/" style="color:#a855f7;"><i class="fas fa-vr-cardboard"></i> VR World & Chess Arena</a>
                <a href="/games.php" style="color:#f59e0b;"><i class="fas fa-gamepad"></i> Games & Arcade</a>
                <a href="/templates/"><i class="fas fa-layer-group"></i> Website Templates</a>
                <a href="/open-source/"><i class="fas fa-lock-open"></i> Open-Source Tools</a>
                <a href="/projects.php" style="color:#ffd700;"><i class="fas fa-compass"></i> Project Directory</a>
            </div>
        </div>

        <!-- Mobile Accordion: Alfred -->
        <div class="mob-accordion">
            <button class="mob-accordion-btn"><i class="fas fa-robot"></i> <?php echo L('nav_alfred'); ?> <i class="fas fa-chevron-down mob-chev"></i></button>
            <div class="mob-accordion-panel">
                <a href="/tools/"><i class="fas fa-toolbox"></i> <?php echo L('footer_tools'); ?></a>
                <a href="/marketplace.php"><i class="fas fa-store"></i> <?php echo L('footer_marketplace'); ?></a>
                <a href="/pricing.php"><i class="fas fa-tags"></i> <?php echo L('nav_pricing'); ?></a>
                <a href="/use-cases/"><i class="fas fa-users"></i> <?php echo L('nav_use_cases'); ?></a>
                <a href="/fleet-dashboard.php"><i class="fas fa-satellite-dish"></i> Fleet Dashboard</a>
                <a href="/agent-orchestrator.php"><i class="fas fa-gears"></i> Agent Orchestrator</a>
                <a href="/conference-room.php"><i class="fas fa-headset"></i> Conference Rooms</a>
                <a href="/team-chat.php"><i class="fas fa-users-rectangle"></i> Team Chat</a>
                <a href="/voice-cloning.php"><i class="fas fa-microphone-lines"></i> Voice Cloning</a>
                <a href="/agent-templates.php"><i class="fas fa-clone"></i> Agent Templates</a>
            </div>
        </div>

        <!-- Mobile Accordion: Crypto & Finance -->
        <div class="mob-accordion">
            <button class="mob-accordion-btn" style="color:#14F195;"><i class="fas fa-link"></i> Crypto & Finance <i class="fas fa-chevron-down mob-chev"></i></button>
            <div class="mob-accordion-panel">
                <a href="/pay/account/crypto"><i class="fas fa-chart-candlestick"></i> Crypto Trading</a>
                <a href="/pay/account/gsm-token"><i class="fas fa-coins"></i> GSM Token</a>
                <a href="/mine.php"><i class="fas fa-hammer"></i> Mine GSM</a>
                <a href="/wallet.php"><i class="fas fa-wallet"></i> Wallet</a>
                <a href="/pay/account/crypto-reports"><i class="fas fa-file-invoice-dollar"></i> Crypto Reports</a>
                <a href="/invest" style="color:#55efc4;"><i class="fas fa-chart-line"></i> Invest in GoSiteMe</a>
                <a href="/qgsm-whitepaper.php"><i class="fas fa-scroll"></i> QGSM Whitepaper</a>
                <a href="/qgsm-bridge.php"><i class="fas fa-bridge"></i> QGSM Bridge</a>
            </div>
        </div>

        <!-- Mobile Accordion: Developer -->
        <div class="mob-accordion">
            <button class="mob-accordion-btn"><i class="fas fa-terminal"></i> Developer <i class="fas fa-chevron-down mob-chev"></i></button>
            <div class="mob-accordion-panel">
                <a href="/developer-portal.php"><i class="fas fa-terminal"></i> Developer Portal</a>
                <a href="/extensions.php"><i class="fas fa-puzzle-piece"></i> Extensions</a>
                <a href="/sdks"><i class="fas fa-cube"></i> SDKs</a>
                <a href="/webhooks.php"><i class="fas fa-plug"></i> Webhooks</a>
                <a href="/docs/api-reference"><i class="fas fa-code"></i> API Reference</a>
                <a href="/docs/getting-started"><i class="fas fa-play-circle"></i> Getting Started</a>
                <a href="/docs/"><i class="fas fa-book"></i> <?php echo L('footer_docs'); ?></a>
                <?php if (!empty($_SESSION['logged_in']) && !empty($_SESSION['client_id'])): ?>
                <a href="/api/oauth.php?action=apps"><i class="fas fa-key"></i> OAuth Apps</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="divider"></div>

        <a href="/pricing.php" style="color:#00b894;font-weight:700;"><i class="fas fa-tags"></i> <?php echo L('nav_pricing'); ?> — Alfred AI Plans</a>
        <a href="<?php echo htmlspecialchars(billing_link('store/ai-domain-hosting-connected-with-ai-editor')); ?>"><i class="fas fa-globe-americas"></i> <?php echo L('mobile_domains'); ?></a>
        <a href="<?php echo htmlspecialchars(billing_link('cart.php')); ?>" style="position:relative;"><i class="fas fa-shopping-cart"></i> Cart<?php $__mc = count($_SESSION['billing_cart'] ?? []); ?><span class="site-cart-badge" <?= $__mc <= 0 ? 'style="display:none;"' : '' ?>><?= $__mc ?></span></a>
        <a href="<?php echo htmlspecialchars(billing_link('announcements')); ?>"><i class="fas fa-newspaper"></i> <?php echo L('mobile_news'); ?></a>
        <a href="<?php echo htmlspecialchars(billing_link('contact.php')); ?>"><i class="fas fa-envelope"></i> <?php echo L('mobile_contact'); ?></a>
        <a href="/help.php"><i class="fas fa-life-ring"></i> Help Center</a>
        <a href="/affiliates" style="color:#10b981;"><i class="fas fa-dollar-sign"></i> Affiliate Program — Earn 20%</a>

        <div class="divider"></div>
        
        <span class="lang-switcher-mobile" style="display:block;padding:12px 24px;font-size:0.9rem;">
            <a href="?language=<?php echo $current_lang === 'fr' ? 'english' : 'french'; ?>" rel="nofollow"><?php echo $current_lang === 'fr' ? 'English' : 'Français'; ?></a>
        </span>
        
        <a href="/login" class="btn btn-outline"><i class="fas fa-user"></i> <?php echo L('mobile_login'); ?></a>
        <a href="/register" class="btn btn-primary"><i class="fas fa-rocket"></i> <?php echo L('mobile_get_started'); ?></a>
    </div>

    <!-- Mega Menu + Command Palette JS -->
    <script>
    (function(){
        /* ═══ MEGA MENU HOVER BRIDGING ═══ */
        var backdrop=document.getElementById('megaBackdrop'),
            triggers=document.querySelectorAll('.mega-trigger'),
            panels=document.querySelectorAll('.mega-panel'),
            NAV=document.getElementById('navbar'),
            hideTimer=null;
        function positionPanels(){
            if(!NAV)return;
            var r=NAV.getBoundingClientRect();
            var top=(r.bottom+8)+'px';
            panels.forEach(function(p){p.style.top=top;});
        }
        positionPanels();
        window.addEventListener('scroll',positionPanels,{passive:true});
        window.addEventListener('resize',positionPanels,{passive:true});
        function showPanel(id){
            clearTimeout(hideTimer);
            panels.forEach(function(p){p.classList.remove('active');});
            triggers.forEach(function(t){t.classList.remove('open');});
            var panel=document.getElementById('mega-'+id);
            var trigger=document.querySelector('[data-mega="'+id+'"]');
            if(panel){panel.classList.add('active');positionPanels();}
            if(trigger)trigger.classList.add('open');
            if(backdrop)backdrop.classList.add('active');
        }
        function hideAll(){
            panels.forEach(function(p){p.classList.remove('active');});
            triggers.forEach(function(t){t.classList.remove('open');});
            if(backdrop)backdrop.classList.remove('active');
        }
        function startHide(){hideTimer=setTimeout(hideAll,350);}
        function cancelHide(){clearTimeout(hideTimer);}
        triggers.forEach(function(trigger){
            trigger.addEventListener('mouseenter',function(){showPanel(trigger.dataset.mega);});
            trigger.addEventListener('mouseleave',startHide);
        });
        panels.forEach(function(panel){
            panel.addEventListener('mouseenter',cancelHide);
            panel.addEventListener('mouseleave',startHide);
        });
        document.addEventListener('click',function(e){if(!e.target.closest('.mega-trigger')&&!e.target.closest('.mega-panel')){clearTimeout(hideTimer);hideAll();}});

        /* ═══ COMMAND PALETTE ═══ */
        var cmdOverlay=document.getElementById('cmdOverlay'),
            cmdInput=document.getElementById('cmdInput'),
            cmdResults=document.getElementById('cmdResults'),
            cmdOpenBtn=document.getElementById('cmdOpenBtn'),
            cmdActive=-1;
        var PAGES=[
            {t:'GoCodeMe Editor',u:'/gocodeme.php',i:'fa-wand-magic-sparkles',c:'Platform',k:'ide editor code'},
            {t:'Use Online (IDE)',u:'/middleware/dashboard',i:'fa-globe',c:'Platform',k:'launch browser ide'},
            {t:'Alfred AI Assistant',u:'/alfred.php',i:'fa-robot',c:'Platform',k:'ai assistant chatbot 1220 tools'},
            {t:'Voice Command Center',u:'/alfred-voice-live/',i:'fa-terminal',c:'Platform',k:'voice command hub'},
            {t:'Dashboard',u:'/dashboard.php',i:'fa-tachometer-alt',c:'Platform',k:'user dashboard home'},
            {t:'Voice Products',u:'/voice-products.php',i:'fa-phone-volume',c:'Voice & AI',k:'voice ai products'},
            {t:'Voice Cloning',u:'/voice-cloning.php',i:'fa-microphone-lines',c:'Voice & AI',k:'clone voice ai'},
            {t:'Voice Portal',u:'/voice-portal.php',i:'fa-headset',c:'Voice & AI',k:'voice portal'},
            {t:'Fleet Dashboard',u:'/fleet-dashboard.php',i:'fa-satellite-dish',c:'AI Features',k:'fleet agents manage orchestrate'},
            {t:'Agent Orchestrator',u:'/agent-orchestrator.php',i:'fa-gears',c:'AI Features',k:'agent orchestrator deploy coding upgrade backlog'},
            {t:'Conference Rooms',u:'/conference-room.php',i:'fa-headset',c:'AI Features',k:'meeting conference call'},
            {t:'Team Chat & Negotiation',u:'/team-chat.php',i:'fa-users-rectangle',c:'AI Features',k:'team chat negotiate'},
            {t:'Call Campaigns',u:'/call-campaigns.php',i:'fa-bullhorn',c:'AI Features',k:'outbound call campaign'},
            {t:'Agent Templates',u:'/agent-templates.php',i:'fa-clone',c:'AI Features',k:'agent template prebuilt'},
            {t:'IVR Builder',u:'/ivr-builder.php',i:'fa-diagram-project',c:'AI Features',k:'ivr phone tree builder'},
            {t:'Conversations',u:'/conversations.php',i:'fa-comments',c:'AI Features',k:'chat history conversations'},
            {t:'Analytics',u:'/analytics.php',i:'fa-chart-line',c:'AI Features',k:'analytics stats reports'},
            {t:'Team Workspace',u:'/team.php',i:'fa-users-gear',c:'AI Features',k:'team members workspace'},
            {t:'Creator Dashboard',u:'/marketplace-creator',i:'fa-paint-brush',c:'AI Features',k:'creator publish marketplace'},
            {t:'Tool Directory \u2014 13,000+ Tools',u:'/tools/',i:'fa-toolbox',c:'AI Core',k:'tools search browse'},
            {t:'Project Directory',u:'/projects.php',i:'fa-compass',c:'AI Core',k:'projects find navigate directory all pages'},
            {t:'Marketplace',u:'/marketplace.php',i:'fa-store',c:'AI Core',k:'marketplace extensions plugins'},
            {t:'Pricing',u:'/pricing.php',i:'fa-tags',c:'AI Core',k:'pricing plans cost token'},
            {t:'Use Cases',u:'/use-cases/',i:'fa-users',c:'AI Core',k:'use cases industry'},
            {t:'Compare',u:'/compare.php',i:'fa-columns',c:'AI Core',k:'compare alternatives vs'},
            {t:'VR World Hub',u:'/vr/hub/',i:'fa-vr-cardboard',c:'Experiences',k:'vr virtual reality 3d'},
            {t:'Chess Arena (VR)',u:'/vr/chess/',i:'fa-chess',c:'Experiences',k:'chess vr ai play'},
            {t:'Games & Arcade',u:'/games.php',i:'fa-gamepad',c:'Experiences',k:'games arcade play'},
            {t:'Website Templates',u:'/templates/',i:'fa-layer-group',c:'Experiences',k:'templates website design'},
            {t:'Open-Source Tools',u:'/open-source/',i:'fa-lock-open',c:'Experiences',k:'open source free'},
            {t:'AI Hosting',u:'/store/hosting',i:'fa-robot',c:'Hosting',k:'hosting web ai'},
            {t:'Domains',u:'/store/domains',i:'fa-globe-americas',c:'Hosting',k:'domain register transfer'},
            {t:'Token Packs',u:'/store/token-packs',i:'fa-coins',c:'Hosting',k:'tokens credits buy'},
            {t:'SSL Certificates',u:'/store/ssl',i:'fa-lock',c:'Hosting',k:'ssl certificate https'},
            {t:'AI Servers',u:'/ai-servers/',i:'fa-microchip',c:'Hosting',k:'servers gpu dedicated'},
            {t:'Training',u:'/store/training',i:'fa-graduation-cap',c:'Hosting',k:'training learn course'},
            {t:'White Label',u:'/white-label.php',i:'fa-tag',c:'Hosting',k:'white label resell brand'},
            {t:'System Status',u:'/status.php',i:'fa-signal',c:'Hosting',k:'status uptime incidents'},
            {t:'Developer Portal',u:'/developer-portal.php',i:'fa-terminal',c:'Developers',k:'developer api build'},
            {t:'Getting Started',u:'/docs/getting-started',i:'fa-play-circle',c:'Developers',k:'getting started quick start'},
            {t:'Documentation',u:'/docs/',i:'fa-book',c:'Developers',k:'docs documentation reference'},
            {t:'API Reference',u:'/docs/api-reference',i:'fa-code',c:'Developers',k:'api reference rest endpoints'},
            {t:'SDKs',u:'/sdks',i:'fa-cube',c:'Developers',k:'sdk python node php go'},
            {t:'Extensions',u:'/extensions.php',i:'fa-puzzle-piece',c:'Developers',k:'extensions plugins chrome cli'},
            {t:'Webhooks',u:'/webhooks.php',i:'fa-plug',c:'Developers',k:'webhooks events integrations'},
            {t:'Integrations',u:'/integrations.php',i:'fa-link',c:'Developers',k:'integrations connect third party'},
            {t:'Changelog',u:'/changelog.php',i:'fa-clipboard-list',c:'Developers',k:'changelog updates releases'},
            {t:'Languages',u:'/languages.php',i:'fa-language',c:'Developers',k:'languages 300 multilingual'},
            {t:'About GoSiteMe',u:'/about.php',i:'fa-info-circle',c:'Company',k:'about company story'},
            {t:'Enterprise',u:'/enterprise.php',i:'fa-building',c:'Company',k:'enterprise business custom'},
            {t:'Security',u:'/security.php',i:'fa-shield-halved',c:'Company',k:'security privacy protection'},
            {t:'Pulse',u:'/pulse.php',i:'fa-bolt',c:'Company',k:'pulse social network feed community ecosystem'},
            {t:'Veil',u:'/veil/',i:'fa-comments',c:'Company',k:'veil messaging encrypted chat payments'},
            {t:'Veil Command Center',u:'/veil/command-center.php',i:'fa-gauge-high',c:'Company',k:'veil command center hq mission control'},
            {t:'World Events Intel',u:'/veil/world-events.php',i:'fa-globe',c:'Company',k:'world events news intelligence briefing'},
            {t:'Fleet Tracker',u:'/veil/fleet-tracker.php',i:'fa-satellite',c:'Company',k:'fleet tracker agents status'},
            {t:'Post-Quantum',u:'/post-quantum.php',i:'fa-atom',c:'Company',k:'post quantum encryption'},
            {t:'Help Center',u:'/help.php',i:'fa-life-ring',c:'Support',k:'help faq support'},
            {t:'Articles & Blog',u:'/articles/',i:'fa-newspaper',c:'Support',k:'blog articles tutorials'},
            {t:'Contact',u:'/contact',i:'fa-envelope',c:'Support',k:'contact email support ticket'},
            {t:'News & Announcements',u:'/announcements',i:'fa-bullhorn',c:'Support',k:'news announcements updates'},
            {t:'Invest in GoSiteMe',u:'/invest',i:'fa-chart-line',c:'Finance',k:'invest equity shares'},
            {t:'Affiliate Program \u2014 Earn 20%',u:'/affiliate.php',i:'fa-dollar-sign',c:'Finance',k:'affiliate earn commission referral'},
            {t:'Crypto Trading',u:'/pay/account/crypto',i:'fa-chart-candlestick',c:'Finance',k:'crypto trading bitcoin solana'},
            {t:'GSM Token',u:'/pay/account/gsm-token',i:'fa-coins',c:'Finance',k:'gsm token cryptocurrency'},
            {t:'Crypto Reports',u:'/pay/account/crypto-reports',i:'fa-file-invoice-dollar',c:'Finance',k:'crypto reports pnl'},
            {t:'VR Checkers',u:'/vr/checkers/',i:'fa-chess-board',c:'VR Spaces',k:'checkers vr'},
            {t:'VR Concert Hall',u:'/vr/concert/',i:'fa-music',c:'VR Spaces',k:'concert music vr'},
            {t:'VR DJ Studio',u:'/vr/dj-studio/',i:'fa-headphones',c:'VR Spaces',k:'dj studio music vr'},
            {t:'VR Art Gallery',u:'/vr/gallery/',i:'fa-palette',c:'VR Spaces',k:'gallery art vr'},
            {t:'VR Kingdom',u:'/vr/kingdom/',i:'fa-chess-rook',c:'VR Spaces',k:'kingdom castle vr'},
            {t:'VR Lounge',u:'/vr/lounge/',i:'fa-couch',c:'VR Spaces',k:'lounge chill vr'},
            {t:'VR Office',u:'/vr/office/',i:'fa-briefcase',c:'VR Spaces',k:'office work vr'},
            {t:'VR Pool Hall',u:'/vr/pool/',i:'fa-circle',c:'VR Spaces',k:'pool billiards vr'},
            {t:'VR Racing',u:'/vr/racing/',i:'fa-flag-checkered',c:'VR Spaces',k:'racing cars vr'},
            {t:'VR Sanctuary',u:'/vr/sanctuary/',i:'fa-spa',c:'VR Spaces',k:'sanctuary meditation vr'},
            {t:'VR Speed Dating',u:'/vr/speed-dating/',i:'fa-heart',c:'VR Spaces',k:'speed dating social vr'},
            {t:'Privacy Policy',u:'/privacy-policy.php',i:'fa-user-shield',c:'Legal',k:'privacy policy data'},
            {t:'Terms of Service',u:'/terms-of-service.php',i:'fa-file-contract',c:'Legal',k:'terms service agreement'},
            {t:'Health Research Portal',u:'/health-research.php',i:'fa-dna',c:'Health & Science',k:'health research genetics longevity aging cannabis nutrition agents'},
            {t:'Healthcare Dashboard',u:'/healthcare-dashboard.php',i:'fa-stethoscope',c:'Health & Science',k:'healthcare medical dashboard ehr vitals'},
            {t:'Use Case: Accounting',u:'/use-cases/accounting',i:'fa-calculator',c:'Use Cases',k:'accounting finance'},
            {t:'Use Case: Healthcare',u:'/use-cases/healthcare',i:'fa-hospital',c:'Use Cases',k:'healthcare medical'},
            {t:'Use Case: Real Estate',u:'/use-cases/realestate',i:'fa-house',c:'Use Cases',k:'real estate property'},
            {t:'Use Case: Legal',u:'/use-cases/legal',i:'fa-gavel',c:'Use Cases',k:'legal law attorney'},
            {t:'Use Case: Education',u:'/use-cases/education',i:'fa-graduation-cap',c:'Use Cases',k:'education school university'},
            {t:'Use Case: E-Commerce',u:'/use-cases/ecommerce',i:'fa-shopping-bag',c:'Use Cases',k:'ecommerce online store shop'},
            {t:'Use Case: Restaurants',u:'/use-cases/restaurants',i:'fa-utensils',c:'Use Cases',k:'restaurants food'},
            {t:'Use Case: Construction',u:'/use-cases/construction',i:'fa-hard-hat',c:'Use Cases',k:'construction building'},
            {t:'Use Case: Fitness',u:'/use-cases/fitness',i:'fa-dumbbell',c:'Use Cases',k:'fitness gym health'},
            {t:'Use Case: Dental',u:'/use-cases/dental',i:'fa-tooth',c:'Use Cases',k:'dental dentist'},
            {t:'Use Case: Insurance',u:'/use-cases/insurance',i:'fa-umbrella',c:'Use Cases',k:'insurance coverage'},
            {t:'Use Case: Travel',u:'/use-cases/travel',i:'fa-plane',c:'Use Cases',k:'travel tourism'},
            {t:'Use Case: Automotive',u:'/use-cases/automotive',i:'fa-car',c:'Use Cases',k:'automotive cars dealership'},
            {t:'Use Case: Government',u:'/use-cases/government',i:'fa-landmark',c:'Use Cases',k:'government public sector'},
            {t:'Use Case: Nonprofits',u:'/use-cases/nonprofits',i:'fa-hand-holding-heart',c:'Use Cases',k:'nonprofit charity'},
            {t:'Use Case: Creators',u:'/use-cases/creators',i:'fa-video',c:'Use Cases',k:'content creators youtube'},
            {t:'Use Case: Agriculture',u:'/use-cases/agriculture',i:'fa-tractor',c:'Use Cases',k:'agriculture farming'},
            {t:'Use Case: Manufacturing',u:'/use-cases/manufacturing',i:'fa-industry',c:'Use Cases',k:'manufacturing factory'},
            {t:'Use Case: Logistics',u:'/use-cases/logistics',i:'fa-truck',c:'Use Cases',k:'logistics shipping supply chain'},
            {t:'Use Case: Recruitment',u:'/use-cases/recruitment',i:'fa-user-tie',c:'Use Cases',k:'recruitment hiring hr'},
            {t:'Use Case: Developers',u:'/use-cases/developers',i:'fa-laptop-code',c:'Use Cases',k:'developers programming'},
            {t:'Use Case: Students',u:'/use-cases/students',i:'fa-user-graduate',c:'Use Cases',k:'students college school'},
            {t:'Use Case: Business',u:'/use-cases/business',i:'fa-briefcase',c:'Use Cases',k:'business corporate'},
            {t:'Use Case: Salon & Beauty',u:'/use-cases/salon',i:'fa-scissors',c:'Use Cases',k:'salon beauty spa'},
            {t:'Use Case: Property Management',u:'/use-cases/property-management',i:'fa-building',c:'Use Cases',k:'property management landlord'},
            {t:'Use Case: Solo Practice',u:'/use-cases/solo-practice',i:'fa-user',c:'Use Cases',k:'solo practice freelance'}
        ];
        function openCmd(){
            cmdOverlay.classList.add('active');
            cmdInput.value='';cmdActive=-1;
            renderResults('');
            setTimeout(function(){cmdInput.focus();},50);
        }
        function closeCmd(){cmdOverlay.classList.remove('active');}
        if(cmdOpenBtn)cmdOpenBtn.addEventListener('click',openCmd);
        var mobSearch=document.getElementById('mobSearchInput');
        if(mobSearch)mobSearch.addEventListener('focus',function(){this.blur();openCmd();});
        if(cmdOverlay)cmdOverlay.addEventListener('click',function(e){if(e.target===cmdOverlay)closeCmd();});
        document.addEventListener('keydown',function(e){
            if((e.metaKey||e.ctrlKey)&&e.key==='k'){e.preventDefault();if(cmdOverlay.classList.contains('active'))closeCmd();else openCmd();return;}
            if(e.key==='Escape'&&cmdOverlay.classList.contains('active')){e.preventDefault();closeCmd();return;}
            if(!cmdOverlay.classList.contains('active'))return;
            var items=cmdResults.querySelectorAll('.cmd-item');
            if(e.key==='ArrowDown'){e.preventDefault();cmdActive=Math.min(cmdActive+1,items.length-1);hlItem(items);}
            if(e.key==='ArrowUp'){e.preventDefault();cmdActive=Math.max(cmdActive-1,0);hlItem(items);}
            if(e.key==='Enter'&&items[cmdActive]){e.preventDefault();window.location.href=items[cmdActive].href;}
        });
        function hlItem(items){items.forEach(function(it,i){it.classList.toggle('active',i===cmdActive);if(i===cmdActive)it.scrollIntoView({block:'nearest'});});}
        if(cmdInput)cmdInput.addEventListener('input',function(){cmdActive=-1;renderResults(cmdInput.value.trim().toLowerCase());});
        function renderResults(q){
            if(!q){
                var popular=PAGES.filter(function(p){return['Platform','AI Core','AI Features','Experiences'].indexOf(p.c)!==-1;}).slice(0,12);
                var html='<div class="cmd-group-title">Popular</div>';
                popular.forEach(function(p){html+='<a href="'+escA(p.u)+'" class="cmd-item"><i class="fas '+p.i+'"></i><span class="cmd-item-title">'+escH(p.t)+'</span><span class="cmd-item-cat">'+escH(p.c)+'</span></a>';});
                cmdResults.innerHTML=html;return;
            }
            var words=q.split(/\s+/);
            var scored=PAGES.map(function(p){
                var hay=(p.t+' '+p.c+' '+(p.k||'')).toLowerCase();
                var score=0;
                for(var w=0;w<words.length;w++){
                    if(hay.indexOf(words[w])!==-1)score+=10;
                    if(p.t.toLowerCase().indexOf(words[w])!==-1)score+=5;
                    if(p.t.toLowerCase().indexOf(words[w])===0)score+=3;
                }
                return{t:p.t,u:p.u,i:p.i,c:p.c,score:score};
            }).filter(function(p){return p.score>0;}).sort(function(a,b){return b.score-a.score;}).slice(0,20);
            if(!scored.length){cmdResults.innerHTML='<div class="cmd-empty"><i class="fas fa-search" style="font-size:1.5rem;display:block;margin-bottom:8px;opacity:.3"></i>No results for \u201c'+escH(q)+'\u201d</div>';return;}
            var groups={},order=[];
            scored.forEach(function(p){if(!groups[p.c]){groups[p.c]=[];order.push(p.c);}groups[p.c].push(p);});
            var html='';
            order.forEach(function(cat){
                html+='<div class="cmd-group-title">'+escH(cat)+'</div>';
                groups[cat].forEach(function(p){html+='<a href="'+escA(p.u)+'" class="cmd-item"><i class="fas '+p.i+'"></i><span class="cmd-item-title">'+escH(p.t)+'</span><span class="cmd-item-cat">'+escH(p.c)+'</span></a>';});
            });
            cmdResults.innerHTML=html;
        }
        function escH(s){var d=document.createElement('div');d.textContent=s;return d.innerHTML;}
        function escA(s){return s.replace(/"/g,'&quot;').replace(/</g,'&lt;');}
    })();
    </script>
    <script>
    /* Logo Flip: GoSiteMe ↔ GoCodeMe */
    (function(){
        var el=document.querySelector('.logo-flip');
        if(!el)return;
        var on=false;
        setInterval(function(){
            on=!on;
            if(on)el.classList.add('flipped');
            else el.classList.remove('flipped');
        },6000);
    })();
    </script>
    </header>
    <?php if (empty($noGlobalMain)): ?><main id="main"><?php endif; ?>
