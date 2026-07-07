<?php
/**
 * Article Template Renderer
 * 
 * Expects $article_meta array with:
 *   title, description, date, author, category, read_time,
 *   featured_image, tags[], slug
 * 
 * And $article_content string (HTML content of the article)
 */

if (!isset($article_meta) || !isset($article_content)) {
    http_response_code(404);
    include $_SERVER['DOCUMENT_ROOT'] . '/404.php';
    exit;
}

// Category config
$category_config = [
    'tutorials'     => ['label' => 'Tutorial',      'color' => '#6c5ce7', 'icon' => 'fa-graduation-cap'],
    'case-studies'  => ['label' => 'Case Study',     'color' => '#00cec9', 'icon' => 'fa-chart-line'],
    'announcements' => ['label' => 'Announcement',   'color' => '#fdcb6e', 'icon' => 'fa-bullhorn'],
    'ai-insights'   => ['label' => 'AI Insights',    'color' => '#e17055', 'icon' => 'fa-brain'],
    'legal-tech'    => ['label' => 'Legal Tech',     'color' => '#0984e3', 'icon' => 'fa-gavel'],
    'industry'      => ['label' => 'Industry',       'color' => '#00b894', 'icon' => 'fa-industry'],
];

$cat = $article_meta['category'] ?? 'tutorials';
$cat_info = $category_config[$cat] ?? $category_config['tutorials'];

// Get all articles for related/prev/next
function get_all_articles() {
    $articles = [];
    $dir = __DIR__;
    foreach (glob($dir . '/*.php') as $file) {
        $basename = basename($file, '.php');
        if (in_array($basename, ['index', 'article-template.inc'])) continue;
        
        // Extract metadata without executing
        $content = file_get_contents($file);
        if (preg_match('/\$article_meta\s*=\s*\[(.+?)\];/s', $content, $m)) {
            // Quick extraction of key fields
            $meta = [];
            if (preg_match("/'title'\s*=>\s*'(.+?)'/", $content, $t)) $meta['title'] = $t[1];
            if (preg_match("/'slug'\s*=>\s*'(.+?)'/", $content, $s)) $meta['slug'] = $s[1];
            if (preg_match("/'description'\s*=>\s*'(.+?)'/", $content, $d)) $meta['description'] = $d[1];
            if (preg_match("/'date'\s*=>\s*'(.+?)'/", $content, $dt)) $meta['date'] = $dt[1];
            if (preg_match("/'category'\s*=>\s*'(.+?)'/", $content, $c)) $meta['category'] = $c[1];
            if (preg_match("/'read_time'\s*=>\s*'(.+?)'/", $content, $r)) $meta['read_time'] = $r[1];
            if (preg_match("/'featured_image'\s*=>\s*'(.+?)'/", $content, $fi)) $meta['featured_image'] = $fi[1];
            if (preg_match("/'author'\s*=>\s*'(.+?)'/", $content, $a)) $meta['author'] = $a[1];
            if (!empty($meta['slug'])) {
                $articles[$meta['slug']] = $meta;
            }
        }
    }
    // Sort by date descending
    uasort($articles, function($a, $b) {
        return strtotime($b['date'] ?? '2026-01-01') - strtotime($a['date'] ?? '2026-01-01');
    });
    return $articles;
}

$all_articles = get_all_articles();
$current_slug = $article_meta['slug'];
$slugs = array_keys($all_articles);
$current_index = array_search($current_slug, $slugs);
$prev_article = ($current_index !== false && $current_index < count($slugs) - 1) ? $all_articles[$slugs[$current_index + 1]] : null;
$next_article = ($current_index !== false && $current_index > 0) ? $all_articles[$slugs[$current_index - 1]] : null;

// Related articles (same category, exclude current)
$related = [];
foreach ($all_articles as $slug => $a) {
    if ($slug === $current_slug) continue;
    if (($a['category'] ?? '') === $cat) {
        $related[] = $a;
    }
    if (count($related) >= 3) break;
}
// Fill with other articles if not enough
if (count($related) < 3) {
    foreach ($all_articles as $slug => $a) {
        if ($slug === $current_slug) continue;
        if (($a['category'] ?? '') === $cat) continue;
        $related[] = $a;
        if (count($related) >= 3) break;
    }
}

// Auto-generate table of contents from content
$toc = [];
$toc_content = $article_content;
$toc_content = preg_replace_callback('/<h([23])[^>]*>(.*?)<\/h[23]>/i', function($match) use (&$toc) {
    $level = $match[1];
    $text = strip_tags($match[2]);
    $id = preg_replace('/[^a-z0-9]+/', '-', strtolower($text));
    $id = trim($id, '-');
    $toc[] = ['level' => $level, 'text' => $text, 'id' => $id];
    return '<h' . $level . ' id="' . $id . '">' . $match[2] . '</h' . $level . '>';
}, $article_content);
$article_content = $toc_content;

// SEO / page setup
$page_title = $article_meta['title'] . ' | GoSiteMe Blog';
$page_description = $article_meta['description'];
$page_canonical = 'https://gositeme.com/articles/' . $article_meta['slug'];
$page_og_url = $page_canonical;
$page_og_image = 'https://gositeme.com' . $article_meta['featured_image'];
$page_og_title = $article_meta['title'];
$page_og_description = $article_meta['description'];

include $_SERVER['DOCUMENT_ROOT'] . '/includes/site-header.inc.php';

$share_url = urlencode($page_canonical);
$share_title = urlencode($article_meta['title']);
$pub_date = date('F j, Y', strtotime($article_meta['date']));
$iso_date = date('c', strtotime($article_meta['date']));
?>

<!-- Schema.org Article -->
<script type="application/ld+json">
<?php echo json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => $article_meta['title'],
    'description' => $article_meta['description'],
    'image' => 'https://gositeme.com' . $article_meta['featured_image'],
    'author' => ['@type' => 'Organization', 'name' => $article_meta['author']],
    'publisher' => [
        '@type' => 'Organization',
        'name' => 'GoSiteMe',
        'logo' => ['@type' => 'ImageObject', 'url' => 'https://gositeme.com/brand/logo.png']
    ],
    'datePublished' => $iso_date,
    'dateModified' => $iso_date,
    'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $page_canonical],
    'keywords' => implode(', ', $article_meta['tags'] ?? []),
    'articleSection' => $cat_info['label'],
    'wordCount' => str_word_count(strip_tags($article_content)),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
</script>

<!-- BreadcrumbList Schema -->
<script type="application/ld+json">
<?php echo json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => 'https://gositeme.com/'],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => 'https://gositeme.com/articles/'],
        ['@type' => 'ListItem', 'position' => 3, 'name' => $cat_info['label'], 'item' => 'https://gositeme.com/articles/category/' . $cat],
        ['@type' => 'ListItem', 'position' => 4, 'name' => $article_meta['title']],
    ]
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
</script>

<style>
/* Article page styles */
.article-hero {
    position: relative;
    padding: 120px 0 60px;
    background: linear-gradient(180deg, rgba(108,92,231,0.08) 0%, transparent 100%);
    overflow: hidden;
}
.article-hero::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: radial-gradient(ellipse at 50% 0%, rgba(108,92,231,0.12) 0%, transparent 60%);
    pointer-events: none;
}
.article-hero .container {
    max-width: 900px;
    margin: 0 auto;
    padding: 0 24px;
    position: relative;
    z-index: 1;
}
.article-breadcrumbs {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    color: var(--text-muted, #8888a8);
    margin-bottom: 24px;
    flex-wrap: wrap;
}
.article-breadcrumbs a {
    color: var(--al-accent, #6c5ce7);
    text-decoration: none;
    transition: color 0.2s;
}
.article-breadcrumbs a:hover { color: #a29bfe; }
.article-breadcrumbs .sep { opacity: 0.4; }
.article-category-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 16px;
}
.article-hero h1 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: clamp(1.8rem, 4vw, 2.8rem);
    font-weight: 700;
    line-height: 1.2;
    margin-bottom: 20px;
    color: #fff;
}
.article-meta-bar {
    display: flex;
    align-items: center;
    gap: 20px;
    font-size: 0.9rem;
    color: var(--text-muted, #8888a8);
    flex-wrap: wrap;
}
.article-meta-bar i {
    margin-right: 6px;
    opacity: 0.7;
}
.article-featured-img {
    width: 100%;
    max-height: 420px;
    object-fit: cover;
    border-radius: 16px;
    margin-top: 32px;
    border: 1px solid rgba(108,92,231,0.2);
}

/* Article body */
.article-layout {
    display: grid;
    grid-template-columns: 240px 1fr;
    gap: 48px;
    max-width: 1100px;
    margin: 0 auto;
    padding: 48px 24px 80px;
}
@media (max-width: 900px) {
    .article-layout {
        grid-template-columns: 1fr;
        gap: 24px;
    }
}

/* Table of Contents */
.article-toc {
    position: sticky;
    top: 100px;
    align-self: start;
}
.article-toc h4 {
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--text-muted, #8888a8);
    margin-bottom: 16px;
    font-weight: 600;
}
.article-toc ul {
    list-style: none;
    padding: 0;
    margin: 0;
    border-left: 2px solid rgba(108,92,231,0.2);
}
.article-toc li {
    padding: 6px 0 6px 16px;
    font-size: 0.85rem;
    line-height: 1.4;
}
.article-toc li.toc-h3 {
    padding-left: 28px;
    font-size: 0.8rem;
}
.article-toc a {
    color: var(--text-muted, #8888a8);
    text-decoration: none;
    transition: color 0.2s;
}
.article-toc a:hover,
.article-toc a.active {
    color: var(--al-accent, #6c5ce7);
}

/* Article content */
.article-content {
    min-width: 0;
}
.article-content h2 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.6rem;
    font-weight: 700;
    color: #fff;
    margin: 40px 0 16px;
    padding-top: 16px;
}
.article-content h3 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.25rem;
    font-weight: 600;
    color: #e0e0f0;
    margin: 32px 0 12px;
}
.article-content p {
    font-size: 1.05rem;
    line-height: 1.8;
    color: #c0c0d8;
    margin-bottom: 20px;
}
.article-content ul, .article-content ol {
    margin: 16px 0 24px 24px;
    color: #c0c0d8;
    line-height: 1.8;
}
.article-content li { margin-bottom: 8px; }
.article-content code {
    background: rgba(108,92,231,0.15);
    color: #a29bfe;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.92em;
}
.article-content pre {
    background: var(--al-surface, #12121e);
    border: 1px solid rgba(108,92,231,0.15);
    border-radius: 12px;
    padding: 20px;
    margin: 24px 0;
    overflow-x: auto;
}
.article-content pre code {
    background: none;
    padding: 0;
    color: #c0c0d8;
}
.article-content blockquote {
    border-left: 4px solid var(--al-accent, #6c5ce7);
    margin: 24px 0;
    padding: 16px 24px;
    background: rgba(108,92,231,0.06);
    border-radius: 0 12px 12px 0;
    color: #c0c0d8;
    font-style: italic;
}
.article-content a {
    color: var(--al-accent, #6c5ce7);
    text-decoration: underline;
    text-decoration-color: rgba(108,92,231,0.3);
    transition: text-decoration-color 0.2s;
}
.article-content a:hover {
    text-decoration-color: var(--al-accent, #6c5ce7);
}
.article-content img {
    max-width: 100%;
    border-radius: 12px;
    margin: 20px 0;
}

/* CTA box */
.article-cta {
    background: linear-gradient(135deg, rgba(108,92,231,0.15) 0%, rgba(0,206,201,0.1) 100%);
    border: 1px solid rgba(108,92,231,0.25);
    border-radius: 16px;
    padding: 32px;
    margin: 40px 0;
    text-align: center;
}
.article-cta h3 {
    color: #fff !important;
    margin-top: 0 !important;
    font-size: 1.4rem !important;
}
.article-cta p { text-align: center; }
.article-cta .btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 32px;
    background: var(--al-accent, #6c5ce7);
    color: #fff;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    transition: transform 0.2s, box-shadow 0.2s;
}
.article-cta .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(108,92,231,0.3);
}

/* Share buttons */
.article-share {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 40px 0;
    padding: 20px 0;
    border-top: 1px solid rgba(255,255,255,0.06);
    border-bottom: 1px solid rgba(255,255,255,0.06);
}
.article-share span {
    font-size: 0.9rem;
    color: var(--text-muted, #8888a8);
    font-weight: 600;
}
.article-share a {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: var(--al-surface, #12121e);
    color: #c0c0d8;
    text-decoration: none;
    transition: background 0.2s, color 0.2s, transform 0.2s;
    font-size: 1rem;
}
.article-share a:hover {
    background: var(--al-accent, #6c5ce7);
    color: #fff;
    transform: translateY(-2px);
}

/* Tags */
.article-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin: 24px 0;
}
.article-tags a {
    padding: 4px 12px;
    border-radius: 6px;
    background: rgba(108,92,231,0.1);
    color: #a29bfe;
    font-size: 0.82rem;
    text-decoration: none;
    transition: background 0.2s;
}
.article-tags a:hover {
    background: rgba(108,92,231,0.2);
}

/* Related articles */
.related-articles {
    max-width: 1100px;
    margin: 0 auto;
    padding: 0 24px 80px;
}
.related-articles h2 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.6rem;
    color: #fff;
    margin-bottom: 32px;
    text-align: center;
}
.related-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 24px;
}
.related-card {
    background: var(--al-surface, #12121e);
    border: 1px solid rgba(108,92,231,0.1);
    border-radius: 16px;
    padding: 24px;
    text-decoration: none;
    transition: transform 0.3s, border-color 0.3s, box-shadow 0.3s;
    display: block;
}
.related-card:hover {
    transform: translateY(-4px);
    border-color: rgba(108,92,231,0.3);
    box-shadow: 0 8px 32px rgba(108,92,231,0.1);
}
.related-card h3 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.1rem;
    color: #fff;
    margin-bottom: 8px;
    line-height: 1.3;
}
.related-card p {
    font-size: 0.9rem;
    color: var(--text-muted, #8888a8);
    line-height: 1.5;
    margin: 0;
}
.related-card .related-meta {
    font-size: 0.8rem;
    color: var(--text-muted, #8888a8);
    margin-top: 12px;
    display: flex;
    gap: 12px;
}

/* Prev/Next navigation */
.article-nav {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    max-width: 900px;
    margin: 0 auto;
    padding: 0 24px 60px;
}
@media (max-width: 600px) {
    .article-nav { grid-template-columns: 1fr; }
}
.article-nav a {
    display: block;
    padding: 20px 24px;
    background: var(--al-surface, #12121e);
    border: 1px solid rgba(108,92,231,0.1);
    border-radius: 12px;
    text-decoration: none;
    transition: border-color 0.3s, transform 0.2s;
}
.article-nav a:hover {
    border-color: rgba(108,92,231,0.3);
    transform: translateY(-2px);
}
.article-nav .nav-label {
    font-size: 0.8rem;
    color: var(--text-muted, #8888a8);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
}
.article-nav .nav-title {
    font-size: 1rem;
    color: #fff;
    font-weight: 600;
    line-height: 1.3;
}
.article-nav .nav-next { text-align: right; }
</style>

<main id="main">
    <!-- Hero -->
    <section class="article-hero">
        <div class="container">
            <!-- Breadcrumbs -->
            <nav class="article-breadcrumbs" aria-label="Breadcrumb">
                <a href="/">Home</a>
                <span class="sep">/</span>
                <a href="/articles/">Blog</a>
                <span class="sep">/</span>
                <a href="/articles/category/<?php echo $cat; ?>"><?php echo $cat_info['label']; ?></a>
                <span class="sep">/</span>
                <span><?php echo htmlspecialchars($article_meta['title']); ?></span>
            </nav>

            <!-- Category pill -->
            <span class="article-category-pill" style="background: <?php echo $cat_info['color']; ?>22; color: <?php echo $cat_info['color']; ?>; border: 1px solid <?php echo $cat_info['color']; ?>44;">
                <i class="fas <?php echo $cat_info['icon']; ?>"></i>
                <?php echo $cat_info['label']; ?>
            </span>

            <h1><?php echo htmlspecialchars($article_meta['title']); ?></h1>

            <div class="article-meta-bar">
                <span><i class="fas fa-calendar-alt"></i> <?php echo $pub_date; ?></span>
                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($article_meta['author']); ?></span>
                <span><i class="fas fa-clock"></i> <?php echo htmlspecialchars($article_meta['read_time']); ?></span>
                <span><i class="fas fa-folder"></i> <?php echo $cat_info['label']; ?></span>
            </div>

            <?php if (!empty($article_meta['featured_image'])): ?>
            <img class="article-featured-img" src="<?php echo htmlspecialchars($article_meta['featured_image']); ?>" alt="<?php echo htmlspecialchars($article_meta['title']); ?>" loading="eager">
            <?php endif; ?>
        </div>
    </section>

    <!-- Article body with TOC -->
    <div class="article-layout">
        <!-- Table of Contents (sidebar) -->
        <?php if (!empty($toc)): ?>
        <aside class="article-toc" aria-label="Table of contents">
            <h4><i class="fas fa-list"></i> Contents</h4>
            <ul>
                <?php foreach ($toc as $item): ?>
                <li class="toc-h<?php echo $item['level']; ?>">
                    <a href="#<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['text']); ?></a>
                </li>
                <?php endforeach; ?>
            </ul>

            <!-- Share (in sidebar on desktop) -->
            <div style="margin-top: 32px;">
                <h4><i class="fas fa-share-alt"></i> Share</h4>
                <div style="display: flex; gap: 8px; margin-top: 12px;">
                    <a href="https://twitter.com/intent/tweet?url=<?php echo $share_url; ?>&text=<?php echo $share_title; ?>" target="_blank" rel="noopener" style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:8px;background:var(--al-surface,#12121e);color:#c0c0d8;text-decoration:none;transition:background 0.2s;" aria-label="Share on X"><i class="fab fa-x-twitter"></i></a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $share_url; ?>" target="_blank" rel="noopener" style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:8px;background:var(--al-surface,#12121e);color:#c0c0d8;text-decoration:none;transition:background 0.2s;" aria-label="Share on Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo $share_url; ?>" target="_blank" rel="noopener" style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:8px;background:var(--al-surface,#12121e);color:#c0c0d8;text-decoration:none;transition:background 0.2s;" aria-label="Share on LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    <a href="mailto:?subject=<?php echo $share_title; ?>&body=Check%20this%20out:%20<?php echo $share_url; ?>" style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:8px;background:var(--al-surface,#12121e);color:#c0c0d8;text-decoration:none;transition:background 0.2s;" aria-label="Share via email"><i class="fas fa-envelope"></i></a>
                </div>
            </div>
        </aside>
        <?php endif; ?>

        <!-- Article content -->
        <article class="article-content">
            <?php echo $article_content; ?>

            <!-- Tags -->
            <?php if (!empty($article_meta['tags'])): ?>
            <div class="article-tags">
                <?php foreach ($article_meta['tags'] as $tag): ?>
                <a href="/articles/?search=<?php echo urlencode($tag); ?>">#<?php echo htmlspecialchars($tag); ?></a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Share bar (inline, mobile friendly) -->
            <div class="article-share">
                <span>Share this article:</span>
                <a href="https://twitter.com/intent/tweet?url=<?php echo $share_url; ?>&text=<?php echo $share_title; ?>" target="_blank" rel="noopener" aria-label="Share on X"><i class="fab fa-x-twitter"></i></a>
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $share_url; ?>" target="_blank" rel="noopener" aria-label="Share on Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo $share_url; ?>" target="_blank" rel="noopener" aria-label="Share on LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                <a href="mailto:?subject=<?php echo $share_title; ?>&body=Check%20this%20out:%20<?php echo $share_url; ?>" aria-label="Share via email"><i class="fas fa-envelope"></i></a>
            </div>
        </article>
    </div>

    <!-- Prev / Next Navigation -->
    <nav class="article-nav" aria-label="Article navigation">
        <?php if ($prev_article): ?>
        <a href="/articles/<?php echo $prev_article['slug']; ?>">
            <div class="nav-label"><i class="fas fa-arrow-left"></i> Previous</div>
            <div class="nav-title"><?php echo htmlspecialchars($prev_article['title']); ?></div>
        </a>
        <?php else: ?>
        <div></div>
        <?php endif; ?>

        <?php if ($next_article): ?>
        <a href="/articles/<?php echo $next_article['slug']; ?>" class="nav-next">
            <div class="nav-label">Next <i class="fas fa-arrow-right"></i></div>
            <div class="nav-title"><?php echo htmlspecialchars($next_article['title']); ?></div>
        </a>
        <?php else: ?>
        <div></div>
        <?php endif; ?>
    </nav>

    <!-- Related Articles -->
    <?php if (!empty($related)): ?>
    <section class="related-articles">
        <h2>Related Articles</h2>
        <div class="related-grid">
            <?php foreach ($related as $ra): 
                $ra_cat = $category_config[$ra['category'] ?? 'tutorials'] ?? $category_config['tutorials'];
            ?>
            <a href="/articles/<?php echo $ra['slug']; ?>" class="related-card">
                <span class="article-category-pill" style="background: <?php echo $ra_cat['color']; ?>22; color: <?php echo $ra_cat['color']; ?>; border: 1px solid <?php echo $ra_cat['color']; ?>44; font-size: 0.7rem; padding: 3px 10px; margin-bottom: 12px;">
                    <i class="fas <?php echo $ra_cat['icon']; ?>"></i> <?php echo $ra_cat['label']; ?>
                </span>
                <h3><?php echo htmlspecialchars($ra['title']); ?></h3>
                <p><?php echo htmlspecialchars($ra['description'] ?? ''); ?></p>
                <div class="related-meta">
                    <span><i class="fas fa-calendar-alt"></i> <?php echo date('M j, Y', strtotime($ra['date'] ?? 'now')); ?></span>
                    <span><i class="fas fa-clock"></i> <?php echo $ra['read_time'] ?? '5 min'; ?></span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</main>

<!-- TOC scroll spy -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tocLinks = document.querySelectorAll('.article-toc a');
    if (!tocLinks.length) return;
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                tocLinks.forEach(l => l.classList.remove('active'));
                const active = document.querySelector('.article-toc a[href="#' + entry.target.id + '"]');
                if (active) active.classList.add('active');
            }
        });
    }, { rootMargin: '-80px 0px -60% 0px' });
    document.querySelectorAll('.article-content h2[id], .article-content h3[id]').forEach(h => observer.observe(h));
});
</script>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/site-footer.inc.php'; ?>
