<?php
/**
 * Custom 404 Page – same look as main site, noindex for SEO, helpful links.
 */
require_once __DIR__ . '/includes/lang.php';

http_response_code(404);

$requestPath = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$requestPath = rawurldecode($requestPath ?: '/');
$missingPath = trim($requestPath, '/');
$missingSlug = $missingPath !== '' ? basename($missingPath) : '';
$searchSeed = trim((string) preg_replace('/[-_+]+/', ' ', $missingSlug));
$searchSeed = preg_replace('/\s+/', ' ', $searchSeed);
$searchSeed = mb_substr($searchSeed, 0, 120);
$requestUrl = 'https://gositeme.com' . ($requestPath !== '' ? $requestPath : '/');

$isFr = ($current_lang === 'fr');
$heading = $isFr ? 'Cette page n\'existe pas.' : 'That page does not exist.';
$subheading = $isFr
    ? 'Nous avons gardé le statut 404, mais nous pouvons vous aider à retrouver ce que vous cherchiez.'
    : 'We kept this as a real 404, but we can still help you recover what you were trying to find.';
$searchLabel = $isFr ? 'Rechercher sur GoSiteMe' : 'Search GoSiteMe';
$searchPlaceholder = $isFr ? 'Essayez un produit, une page, un outil ou un service...' : 'Try a product, page, tool, or service...';
$searchButton = $isFr ? 'Chercher' : 'Search';
$recoveryTitle = $isFr ? 'Chemins rapides' : 'Fast recovery paths';
$queryHintTitle = $isFr ? 'Suggestion basée sur l’URL' : 'URL-based suggestion';
$missingLabel = $isFr ? 'URL manquante détectée' : 'Missing URL detected';
$popularTitle = $isFr ? 'Destinations populaires' : 'Popular destinations';
$statusLabel = $isFr ? 'Erreur 404 confirmée' : 'Confirmed 404 error';
$sideTitle = $isFr ? 'Retrouvez votre chemin' : 'Find your way back';
$sideLead = $isFr
    ? 'Cette page est introuvable, mais vous pouvez lancer une recherche ou choisir un raccourci fiable ci-dessous.'
    : 'This page is missing, but you can search the site or jump to a reliable destination below.';

$quickLinks = [
    ['href' => '/', 'label' => $isFr ? 'Accueil' : 'Home'],
    ['href' => '/search', 'label' => $isFr ? 'Moteur de recherche' : 'Search engine'],
    ['href' => '/pricing.php', 'label' => $isFr ? 'Tarifs' : 'Pricing'],
    ['href' => '/login.php', 'label' => $isFr ? 'Connexion' : 'Login'],
    ['href' => '/tools/', 'label' => $isFr ? 'Outils IA' : 'AI tools'],
    ['href' => '/docs/', 'label' => 'Docs'],
];

$suggestions = [];
if ($searchSeed !== '') {
    $suggestions[] = [
        'href' => '/search?q=' . rawurlencode($searchSeed),
        'label' => $searchSeed,
        'type' => $isFr ? 'Recherche directe' : 'Direct search',
    ];
}

$pathLower = strtolower($missingPath);
$keywordMap = [
    'search' => ['/search', $isFr ? 'Moteur de recherche' : 'Search engine'],
    'host' => ['/pricing.php', $isFr ? 'Hébergement' : 'Hosting'],
    'domain' => ['/domains/', $isFr ? 'Domaines' : 'Domains'],
    'tool' => ['/tools/', $isFr ? 'Outils IA' : 'AI tools'],
    'doc' => ['/docs/', 'Docs'],
    'price' => ['/pricing.php', $isFr ? 'Tarifs' : 'Pricing'],
    'plan' => ['/pricing.php', $isFr ? 'Tarifs' : 'Pricing'],
    'login' => ['/login.php', $isFr ? 'Connexion' : 'Login'],
    'code' => ['/gocodeme.php', 'GoCodeMe'],
    'voice' => ['/voice-products.php', $isFr ? 'Agents vocaux IA' : 'AI voice agents'],
];

foreach ($keywordMap as $needle => [$href, $label]) {
    if ($pathLower !== '' && strpos($pathLower, $needle) !== false) {
        $suggestions[] = [
            'href' => $href,
            'label' => $label,
            'type' => $isFr ? 'Correspondance probable' : 'Likely match',
        ];
    }
}

$suggestions = array_values(array_reduce($suggestions, static function ($carry, $item) {
    $key = $item['href'] . '|' . $item['label'];
    $carry[$key] = $item;
    return $carry;
}, []));

$page_title       = L('page_404_title');
$page_description = $current_lang === 'fr' ? 'Page introuvable. GoSiteMe – hébergement web et créateur de sites IA.' : 'Page not found. GoSiteMe – web hosting and AI website builder.';
$page_canonical   = $requestUrl;
$page_robots      = 'noindex, follow';
$page_og_url      = $requestUrl;
$page_og_image    = 'https://gositeme.com/assets/hero-banner.png';
$page_og_image_alt = 'GoSiteMe – Best web hosting & AI website builder';

$noGlobalMain = true;
require_once __DIR__ . '/includes/site-header.inc.php';
$page_404_desc = $current_lang === 'fr' ? 'Page introuvable. Retour a l\'accueil, recherchez sur GoSiteMe ou utilisez les raccourcis de recuperation.' : 'Page not found. Return home, search GoSiteMe, or use quick recovery links.';
?>
<script type="application/ld+json"><?php echo json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebPage',
    'name' => L('page_404_heading'),
    'description' => $page_404_desc,
    'url' => 'https://gositeme.com/',
    'isPartOf' => ['@type' => 'WebSite', 'name' => 'GoSiteMe', 'url' => 'https://gositeme.com']
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
<style>
    .nf-shell {
        position: relative;
        overflow: hidden;
        padding: 5rem 1.5rem 6rem;
        background:
            radial-gradient(circle at top left, rgba(91,156,245,0.18), transparent 30%),
            radial-gradient(circle at bottom right, rgba(124,92,252,0.16), transparent 28%),
            linear-gradient(180deg, #060912 0%, #0a1020 48%, #060912 100%);
    }
    .nf-shell::before {
        content: '';
        position: absolute;
        inset: 0;
        background-image:
            linear-gradient(rgba(91,156,245,0.05) 1px, transparent 1px),
            linear-gradient(90deg, rgba(91,156,245,0.05) 1px, transparent 1px);
        background-size: 48px 48px;
        opacity: 0.22;
        pointer-events: none;
    }
    .nf-wrap {
        position: relative;
        z-index: 1;
        max-width: 1120px;
        margin: 0 auto;
    }
    .nf-hero {
        display: grid;
        grid-template-columns: minmax(0, 1.2fr) minmax(280px, 0.8fr);
        gap: 1.5rem;
        align-items: stretch;
    }
    .nf-panel,
    .nf-side {
        border: 1px solid rgba(116, 147, 255, 0.16);
        background: rgba(7, 12, 24, 0.76);
        backdrop-filter: blur(18px);
        border-radius: 28px;
        box-shadow: 0 24px 80px rgba(0, 0, 0, 0.28);
    }
    .nf-panel {
        padding: 2rem;
    }
    .nf-status {
        display: inline-flex;
        align-items: center;
        gap: 0.55rem;
        padding: 0.45rem 0.85rem;
        border-radius: 999px;
        background: rgba(239, 68, 68, 0.12);
        border: 1px solid rgba(239, 68, 68, 0.24);
        color: #ffb5b5;
        font-size: 0.78rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        font-weight: 700;
    }
    .nf-title {
        margin: 1rem 0 0.75rem;
        font-size: clamp(2.5rem, 4vw, 4.4rem);
        line-height: 0.94;
        letter-spacing: -0.04em;
        color: #f5f8ff;
    }
    .nf-text {
        max-width: 42rem;
        font-size: 1.06rem;
        line-height: 1.7;
        color: rgba(232, 239, 255, 0.78);
        margin-bottom: 1.4rem;
    }
    .nf-search-shell {
        margin-top: 1.35rem;
        padding: 0.7rem;
        border-radius: 24px;
        border: 1px solid rgba(91,156,245,0.18);
        background: linear-gradient(135deg, rgba(10,17,35,0.96), rgba(11,14,26,0.84));
        box-shadow: inset 0 0 0 1px rgba(255,255,255,0.03);
    }
    .nf-search-label {
        display: block;
        margin: 0 0 0.75rem 0.35rem;
        color: rgba(177, 194, 229, 0.74);
        font-size: 0.82rem;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        font-weight: 700;
    }
    .nf-search-bar {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        border-radius: 20px;
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(91,156,245,0.1);
        padding: 0.45rem;
    }
    .nf-search-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 3rem;
        height: 3rem;
        border-radius: 16px;
        background: linear-gradient(135deg, rgba(34,211,238,0.18), rgba(91,156,245,0.22));
        color: #a8dcff;
        font-size: 1.05rem;
        flex-shrink: 0;
    }
    .nf-search-input {
        flex: 1;
        min-width: 0;
        border: none;
        outline: none;
        background: transparent;
        color: #eef4ff;
        font-size: 1.02rem;
        padding: 0.95rem 0.35rem;
    }
    .nf-search-input::placeholder {
        color: rgba(198, 211, 241, 0.42);
    }
    .nf-search-btn {
        border: none;
        cursor: pointer;
        border-radius: 18px;
        padding: 0.95rem 1.2rem;
        color: #fff;
        font-weight: 800;
        letter-spacing: 0.02em;
        background: linear-gradient(135deg, #5b9cf5 0%, #7c5cfc 55%, #22d3ee 100%);
        box-shadow: 0 10px 28px rgba(91,156,245,0.3);
    }
    .nf-search-btn:hover {
        transform: translateY(-1px);
    }
    .nf-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.8rem;
        margin-top: 1rem;
    }
    .nf-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.55rem 0.85rem;
        border-radius: 999px;
        border: 1px solid rgba(148, 163, 184, 0.16);
        background: rgba(255,255,255,0.03);
        color: rgba(219, 230, 252, 0.74);
        font-size: 0.86rem;
    }
    .nf-side {
        padding: 1.5rem;
    }
    .nf-side h2,
    .nf-grid h2 {
        margin: 0 0 1rem;
        font-size: 0.9rem;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: rgba(168, 187, 227, 0.76);
    }
    .nf-side strong {
        display: block;
        margin-bottom: 0.35rem;
        color: #f4f7ff;
        font-size: 1rem;
    }
    .nf-path {
        margin-top: 0.8rem;
        padding: 0.95rem 1rem;
        border-radius: 18px;
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(148,163,184,0.1);
        color: rgba(214, 227, 252, 0.72);
        word-break: break-word;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, Liberation Mono, monospace;
        font-size: 0.9rem;
    }
    .nf-grid {
        margin-top: 1.5rem;
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1.25rem;
    }
    .nf-card {
        border-radius: 24px;
        padding: 1.4rem;
        border: 1px solid rgba(116, 147, 255, 0.12);
        background: rgba(9, 14, 26, 0.76);
        box-shadow: 0 18px 40px rgba(0,0,0,0.18);
    }
    .nf-links,
    .nf-suggestion-list {
        display: grid;
        gap: 0.7rem;
    }
    .nf-link,
    .nf-suggestion {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.95rem 1rem;
        border-radius: 18px;
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(148,163,184,0.08);
        color: #eef4ff;
        text-decoration: none;
        transition: transform 0.18s ease, border-color 0.18s ease, background 0.18s ease;
    }
    .nf-link:hover,
    .nf-suggestion:hover {
        transform: translateY(-1px);
        border-color: rgba(91,156,245,0.24);
        background: rgba(91,156,245,0.08);
        text-decoration: none;
    }
    .nf-link small,
    .nf-suggestion small {
        display: block;
        margin-top: 0.18rem;
        color: rgba(182, 197, 231, 0.62);
    }
    .nf-arrow {
        flex-shrink: 0;
        color: #8ab7ff;
        font-size: 1.1rem;
    }
    .nf-footer-links {
        margin-top: 1.4rem;
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
    }
    .nf-footer-links a {
        padding: 0.65rem 0.9rem;
        border-radius: 999px;
        border: 1px solid rgba(148,163,184,0.12);
        background: rgba(255,255,255,0.03);
        color: rgba(233, 239, 255, 0.8);
        text-decoration: none;
        font-size: 0.9rem;
    }
    .nf-footer-links a:hover {
        border-color: rgba(91,156,245,0.24);
        color: #fff;
        text-decoration: none;
    }
    @media (max-width: 900px) {
        .nf-hero,
        .nf-grid {
            grid-template-columns: 1fr;
        }
        .nf-panel,
        .nf-side,
        .nf-card {
            padding: 1.25rem;
        }
    }
    @media (max-width: 640px) {
        .nf-shell {
            padding: 4rem 1rem 5rem;
        }
        .nf-search-bar {
            flex-wrap: wrap;
        }
        .nf-search-icon {
            width: 2.75rem;
            height: 2.75rem;
        }
        .nf-search-btn {
            width: 100%;
        }
    }
</style>
<main class="main-content" id="main">
    <section class="nf-shell">
        <div class="nf-wrap">
            <div class="nf-hero">
                <div class="nf-panel">
                    <div class="nf-status"><?php echo htmlspecialchars($statusLabel); ?></div>
                    <h1 class="nf-title"><?php echo htmlspecialchars($heading); ?></h1>
                    <p class="nf-text"><?php echo htmlspecialchars($subheading); ?></p>

                    <form action="/search" method="get" class="nf-search-shell" role="search">
                        <label class="nf-search-label" for="nfSearchInput"><?php echo htmlspecialchars($searchLabel); ?></label>
                        <div class="nf-search-bar">
                            <span class="nf-search-icon" aria-hidden="true">⌘</span>
                            <input
                                id="nfSearchInput"
                                class="nf-search-input"
                                type="search"
                                name="q"
                                value="<?php echo htmlspecialchars($searchSeed, ENT_QUOTES, 'UTF-8'); ?>"
                                placeholder="<?php echo htmlspecialchars($searchPlaceholder); ?>"
                                autocomplete="off"
                            >
                            <input type="hidden" name="mode" value="web">
                            <button type="submit" class="nf-search-btn"><?php echo htmlspecialchars($searchButton); ?></button>
                        </div>
                    </form>

                    <div class="nf-meta">
                        <div class="nf-chip"><?php echo htmlspecialchars($missingLabel); ?>: <strong><?php echo htmlspecialchars($requestPath); ?></strong></div>
                        <?php if ($searchSeed !== ''): ?>
                            <div class="nf-chip"><?php echo htmlspecialchars($queryHintTitle); ?>: <strong><?php echo htmlspecialchars($searchSeed); ?></strong></div>
                        <?php endif; ?>
                    </div>
                </div>

                <aside class="nf-side">
                    <h2><?php echo htmlspecialchars($recoveryTitle); ?></h2>
                    <strong><?php echo htmlspecialchars($sideTitle); ?></strong>
                    <p style="color:rgba(220,230,252,0.72);line-height:1.7;margin:0;">
                        <?php echo htmlspecialchars($sideLead); ?>
                    </p>
                    <div class="nf-path"><?php echo htmlspecialchars($requestPath); ?></div>
                    <div class="nf-footer-links">
                        <a href="/"><?php echo htmlspecialchars(L('page_404_home')); ?></a>
                        <a href="<?php echo htmlspecialchars(billing_link('contact.php')); ?>"><?php echo htmlspecialchars(L('page_404_contact')); ?></a>
                        <a href="/gocodeme.php">GoCodeMe</a>
                        <a href="/middleware/dashboard"><?php echo htmlspecialchars(L('nav_use_online')); ?></a>
                    </div>
                </aside>
            </div>

            <div class="nf-grid">
                <section class="nf-card">
                    <h2><?php echo htmlspecialchars($queryHintTitle); ?></h2>
                    <div class="nf-suggestion-list">
                        <?php if (!empty($suggestions)): ?>
                            <?php foreach ($suggestions as $suggestion): ?>
                                <a class="nf-suggestion" href="<?php echo htmlspecialchars($suggestion['href']); ?>">
                                    <span>
                                        <strong><?php echo htmlspecialchars($suggestion['label']); ?></strong>
                                        <small><?php echo htmlspecialchars($suggestion['type']); ?></small>
                                    </span>
                                    <span class="nf-arrow" aria-hidden="true">→</span>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="nf-suggestion">
                                <span>
                                    <strong><?php echo $isFr ? 'Rechercher tout le site' : 'Search the full site'; ?></strong>
                                    <small><?php echo $isFr ? 'Utilisez le moteur Alfred pour retrouver la bonne destination.' : 'Use Alfred Search to recover the right destination.'; ?></small>
                                </span>
                                <span class="nf-arrow" aria-hidden="true">→</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="nf-card">
                    <h2><?php echo htmlspecialchars($popularTitle); ?></h2>
                    <div class="nf-links">
                        <?php foreach ($quickLinks as $link): ?>
                            <a class="nf-link" href="<?php echo htmlspecialchars($link['href']); ?>">
                                <span>
                                    <strong><?php echo htmlspecialchars($link['label']); ?></strong>
                                    <small><?php echo $isFr ? 'Destination fiable' : 'Reliable destination'; ?></small>
                                </span>
                                <span class="nf-arrow" aria-hidden="true">→</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>
        </div>
    </section>
</main>
<?php require_once __DIR__ . '/includes/site-footer.inc.php'; ?>
