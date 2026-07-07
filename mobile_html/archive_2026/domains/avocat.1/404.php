<?php
/**
 * Custom 404 Page – same look as main site, noindex for SEO, helpful links.
 */
require_once __DIR__ . '/includes/lang.php';

http_response_code(404);

$page_title       = L('page_404_title');
$page_description = $current_lang === 'fr' ? 'Page introuvable. GoSiteMe – hébergement web et créateur de sites IA.' : 'Page not found. GoSiteMe – web hosting and AI website builder.';
$page_canonical   = 'https://gositeme.com/'; // Point to homepage; 404 itself is noindex
$page_robots      = 'noindex, follow';
$page_og_url      = 'https://gositeme.com/';
$page_og_image    = 'https://gositeme.com/assets/hero-banner.png';
$page_og_image_alt = 'GoSiteMe – Best web hosting & AI website builder';

$noGlobalMain = true;
require_once __DIR__ . '/includes/site-header.inc.php';
$page_404_desc = $current_lang === 'fr' ? 'Page introuvable. Retour à l\'accueil, support ou sitemap.' : 'Page not found. Return home, contact support, or view sitemap.';
?>
<script type="application/ld+json"><?php echo json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'WebPage',
    'name' => L('page_404_heading'),
    'description' => $page_404_desc,
    'url' => 'https://gositeme.com/',
    'isPartOf' => ['@type' => 'WebSite', 'name' => 'GoSiteMe', 'url' => 'https://gositeme.com']
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
<main class="main-content" id="main">
    <section class="section section-404" style="padding: 6rem 1.5rem; text-align: center;">
        <div class="container" style="max-width: 780px;">
            <h1 class="section-title" style="margin-bottom: 0.5rem;"><?php echo htmlspecialchars(L('page_404_heading')); ?></h1>
            <p class="section-subtitle" style="margin-bottom: 2rem; font-size: 1.1rem;"><?php echo htmlspecialchars(L('page_404_text')); ?></p>
            <div class="btn-group" style="flex-wrap: wrap; gap: 1rem; justify-content: center;">
                <a href="/" class="btn btn-primary"><?php echo htmlspecialchars(L('page_404_home')); ?></a>
                <a href="<?php echo htmlspecialchars(billing_link('contact.php')); ?>" class="btn btn-outline"><?php echo htmlspecialchars(L('page_404_contact')); ?></a>
                <a href="/gocodeme.php" class="btn btn-outline"><?php echo L('hero_download_gocodeme'); ?></a>
                <a href="/middleware/dashboard" class="btn btn-outline"><?php echo L('nav_use_online'); ?></a>
            </div>

            <!-- Upsell cards -->
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1.25rem;margin-top:3rem;text-align:left;">
                <div style="background:rgba(125,0,255,0.08);border:1px solid rgba(125,0,255,0.25);border-radius:14px;padding:1.5rem;">
                    <div style="font-size:1.5rem;margin-bottom:.5rem;">🌐</div>
                    <h3 style="font-size:1rem;margin:0 0 .4rem;color:#fff;">AI Web Hosting</h3>
                    <p style="font-size:.85rem;opacity:.75;margin:0 0 1rem;">Lightning-fast hosting with built-in AI tools, starting at $15/mo.</p>
                    <a href="<?php echo htmlspecialchars(billing_link('store/ai-domain-hosting-connected-with-ai-editor')); ?>" class="btn btn-primary" style="font-size:.85rem;padding:.5rem 1.2rem;">Browse Plans</a>
                </div>
                <div style="background:rgba(0,212,255,0.08);border:1px solid rgba(0,212,255,0.25);border-radius:14px;padding:1.5rem;">
                    <div style="font-size:1.5rem;margin-bottom:.5rem;">💻</div>
                    <h3 style="font-size:1rem;margin:0 0 .4rem;color:#fff;">GoCodeMe AI IDE</h3>
                    <p style="font-size:.85rem;opacity:.75;margin:0 0 1rem;">Code with 1,220+ AI tools, voice control &amp; Alfred assistant built in.</p>
                    <a href="/gocodeme.php" class="btn btn-primary" style="font-size:.85rem;padding:.5rem 1.2rem;background:linear-gradient(135deg,#00D4FF,#7D00FF);">Get GoCodeMe</a>
                </div>
                <div style="background:rgba(192,132,252,0.08);border:1px solid rgba(192,132,252,0.25);border-radius:14px;padding:1.5rem;">
                    <div style="font-size:1.5rem;margin-bottom:.5rem;">📞</div>
                    <h3 style="font-size:1rem;margin:0 0 .4rem;color:#fff;">AI Phone Agents</h3>
                    <p style="font-size:.85rem;opacity:.75;margin:0 0 1rem;">24/7 AI agents that answer calls, book appointments &amp; qualify leads.</p>
                    <a href="/voice-products.php" class="btn btn-primary" style="font-size:.85rem;padding:.5rem 1.2rem;background:linear-gradient(135deg,#C084FC,#7D00FF);">See Packages</a>
                </div>
            </div>

            <!-- Alfred AI Quick Links -->
            <div style="margin-top:2.5rem;padding:2rem;background:rgba(108,92,231,0.08);border:1px solid rgba(108,92,231,0.25);border-radius:14px;">
                <h3 style="font-size:1.1rem;margin:0 0 1rem;color:#a29bfe;text-align:center;">
                    <?php echo $current_lang === 'fr' ? 'Découvrez Alfred AI' : 'Discover Alfred AI'; ?>
                </h3>
                <div style="display:flex;flex-wrap:wrap;gap:0.75rem;justify-content:center;">
                    <a href="/tools/" style="padding:0.5rem 1rem;background:rgba(108,92,231,0.15);border:1px solid rgba(108,92,231,0.3);border-radius:8px;color:#a29bfe;text-decoration:none;font-size:0.85rem;">
                        🧰 <?php echo $current_lang === 'fr' ? '1,220+ Outils IA' : '1,220+ AI Tools'; ?>
                    </a>
                    <a href="/marketplace.php" style="padding:0.5rem 1rem;background:rgba(108,92,231,0.15);border:1px solid rgba(108,92,231,0.3);border-radius:8px;color:#a29bfe;text-decoration:none;font-size:0.85rem;">
                        🏪 Marketplace
                    </a>
                    <a href="/pricing.php" style="padding:0.5rem 1rem;background:rgba(108,92,231,0.15);border:1px solid rgba(108,92,231,0.3);border-radius:8px;color:#a29bfe;text-decoration:none;font-size:0.85rem;">
                        💎 <?php echo $current_lang === 'fr' ? 'Tarifs' : 'Pricing'; ?>
                    </a>
                    <a href="/use-cases/" style="padding:0.5rem 1rem;background:rgba(108,92,231,0.15);border:1px solid rgba(108,92,231,0.3);border-radius:8px;color:#a29bfe;text-decoration:none;font-size:0.85rem;">
                        🎯 <?php echo $current_lang === 'fr' ? 'Cas d\'utilisation' : 'Use Cases'; ?>
                    </a>
                    <a href="/docs/" style="padding:0.5rem 1rem;background:rgba(108,92,231,0.15);border:1px solid rgba(108,92,231,0.3);border-radius:8px;color:#a29bfe;text-decoration:none;font-size:0.85rem;">
                        📚 Documentation
                    </a>
                    <a href="/articles/" style="padding:0.5rem 1rem;background:rgba(108,92,231,0.15);border:1px solid rgba(108,92,231,0.3);border-radius:8px;color:#a29bfe;text-decoration:none;font-size:0.85rem;">
                        📰 Blog
                    </a>
                    <a href="/about.php" style="padding:0.5rem 1rem;background:rgba(108,92,231,0.15);border:1px solid rgba(108,92,231,0.3);border-radius:8px;color:#a29bfe;text-decoration:none;font-size:0.85rem;">
                        ℹ️ <?php echo $current_lang === 'fr' ? 'À propos' : 'About'; ?>
                    </a>
                    <a href="/compare.php" style="padding:0.5rem 1rem;background:rgba(108,92,231,0.15);border:1px solid rgba(108,92,231,0.3);border-radius:8px;color:#a29bfe;text-decoration:none;font-size:0.85rem;">
                        ⚖️ <?php echo $current_lang === 'fr' ? 'Comparer' : 'Compare'; ?>
                    </a>
                </div>
            </div>

            <p style="margin-top:2.5rem;font-size:.85rem;opacity:.6;">
                <a href="/"><?php echo L('nav_hosting'); ?></a> ·
                <a href="<?php echo htmlspecialchars(billing_link('store/ai-domain-hosting-connected-with-ai-editor')); ?>"><?php echo L('nav_ai_hosting'); ?></a> ·
                <a href="<?php echo htmlspecialchars(billing_link('store/token-packs')); ?>">Token Packs</a> ·
                <a href="<?php echo htmlspecialchars(billing_link('store/domains-register')); ?>"><?php echo L('nav_domains'); ?></a> ·
                <a href="/voice-products.php">AI Phone Agents</a> ·
                <a href="/tools/">AI Tools</a> ·
                <a href="/articles/">Blog</a> ·
                <a href="/about.php">About</a> ·
                <a href="/docs/">Docs</a>
            </p>
            <p style="margin-top: 1rem; font-size: 0.85rem; opacity:.5;"><a href="/sitemap.xml">Sitemap</a> &middot; <a href="/humans.txt">Humans</a></p>
        </div>
    </section>
</main>
<?php require_once __DIR__ . '/includes/site-footer.inc.php'; ?>
