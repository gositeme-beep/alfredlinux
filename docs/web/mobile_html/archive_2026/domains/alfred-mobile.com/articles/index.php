<?php
/**
 * GoSiteMe Blog — Article Listing Page
 * Scans /articles/ for PHP files with $article_meta arrays
 */

$page_title = 'Blog — AI Insights, Tutorials & News | GoSiteMe';
$page_description = 'Explore tutorials, case studies, AI insights, and product announcements from GoSiteMe. Learn how Alfred AI and 1,220+ tools can transform your workflow.';
$page_canonical = 'https://gositeme.com/articles/';

// Category config
$category_config = [
    'tutorials'     => ['label' => 'Tutorials',     'color' => '#6c5ce7', 'icon' => 'fa-graduation-cap'],
    'case-studies'  => ['label' => 'Case Studies',   'color' => '#00cec9', 'icon' => 'fa-chart-line'],
    'announcements' => ['label' => 'Announcements',  'color' => '#fdcb6e', 'icon' => 'fa-bullhorn'],
    'ai-insights'   => ['label' => 'AI Insights',    'color' => '#e17055', 'icon' => 'fa-brain'],
    'legal-tech'    => ['label' => 'Legal Tech',     'color' => '#0984e3', 'icon' => 'fa-gavel'],
    'industry'      => ['label' => 'Industry',       'color' => '#00b894', 'icon' => 'fa-industry'],
];

// Get all articles
function blog_get_all_articles() {
    $articles = [];
    $dir = __DIR__;
    foreach (glob($dir . '/*.php') as $file) {
        $basename = basename($file, '.php');
        if (in_array($basename, ['index', 'article-template.inc'])) continue;
        
        $content = file_get_contents($file);
        if (preg_match('/\$article_meta\s*=\s*\[/', $content)) {
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
                $articles[] = $meta;
            }
        }
    }
    usort($articles, function($a, $b) {
        return strtotime($b['date'] ?? '2026-01-01') - strtotime($a['date'] ?? '2026-01-01');
    });
    return $articles;
}

$all_articles = blog_get_all_articles();

// Filtering
$filter_category = isset($_GET['category']) ? preg_replace('/[^a-z0-9-]/', '', $_GET['category']) : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : (isset($_GET['q']) ? trim($_GET['q']) : '');

if ($filter_category && isset($category_config[$filter_category])) {
    $page_title = $category_config[$filter_category]['label'] . ' — GoSiteMe Blog';
    $page_canonical = 'https://gositeme.com/articles/category/' . $filter_category;
    $all_articles = array_filter($all_articles, function($a) use ($filter_category) {
        return ($a['category'] ?? '') === $filter_category;
    });
    $all_articles = array_values($all_articles);
}

if ($search_query !== '') {
    $q = strtolower($search_query);
    $all_articles = array_filter($all_articles, function($a) use ($q) {
        return stripos($a['title'] ?? '', $q) !== false
            || stripos($a['description'] ?? '', $q) !== false
            || stripos($a['category'] ?? '', $q) !== false;
    });
    $all_articles = array_values($all_articles);
}

// Pagination
$per_page = 10;
$total = count($all_articles);
$total_pages = max(1, ceil($total / $per_page));
$page = max(1, min($total_pages, intval($_GET['page'] ?? 1)));
$offset = ($page - 1) * $per_page;
$articles = array_slice($all_articles, $offset, $per_page);

include $_SERVER['DOCUMENT_ROOT'] . '/includes/site-header.inc.php';
?>

<!-- Schema.org Blog -->
<script type="application/ld+json">
<?php echo json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Blog',
    'name' => 'GoSiteMe Blog',
    'description' => $page_description,
    'url' => 'https://gositeme.com/articles/',
    'publisher' => [
        '@type' => 'Organization',
        'name' => 'GoSiteMe',
        'logo' => ['@type' => 'ImageObject', 'url' => 'https://gositeme.com/brand/logo.png']
    ],
    'blogPost' => array_map(function($a) {
        return [
            '@type' => 'BlogPosting',
            'headline' => $a['title'] ?? '',
            'url' => 'https://gositeme.com/articles/' . ($a['slug'] ?? ''),
            'datePublished' => $a['date'] ?? '',
            'description' => $a['description'] ?? '',
        ];
    }, array_slice($all_articles, 0, 20))
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
</script>

<style>
/* Blog listing styles */
.blog-hero {
    position: relative;
    padding: 120px 0 48px;
    text-align: center;
    background: linear-gradient(180deg, rgba(108,92,231,0.08) 0%, transparent 100%);
    overflow: hidden;
}
.blog-hero::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: radial-gradient(ellipse at 50% 0%, rgba(108,92,231,0.15) 0%, transparent 60%);
    pointer-events: none;
}
.blog-hero .container {
    position: relative;
    z-index: 1;
    max-width: 800px;
    margin: 0 auto;
    padding: 0 24px;
}
.blog-hero h1 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: clamp(2rem, 4vw, 3rem);
    font-weight: 700;
    color: #fff;
    margin-bottom: 16px;
}
.blog-hero h1 span { color: var(--al-accent, #6c5ce7); }
.blog-hero p {
    font-size: 1.1rem;
    color: var(--text-muted, #8888a8);
    max-width: 600px;
    margin: 0 auto 32px;
    line-height: 1.6;
}

/* Search bar */
.blog-search {
    max-width: 520px;
    margin: 0 auto;
    position: relative;
}
.blog-search input {
    width: 100%;
    padding: 14px 20px 14px 48px;
    background: var(--al-surface, #12121e);
    border: 1px solid rgba(108,92,231,0.2);
    border-radius: 12px;
    color: #fff;
    font-size: 1rem;
    outline: none;
    transition: border-color 0.3s, box-shadow 0.3s;
    font-family: 'Inter', sans-serif;
}
.blog-search input:focus {
    border-color: var(--al-accent, #6c5ce7);
    box-shadow: 0 0 0 3px rgba(108,92,231,0.15);
}
.blog-search input::placeholder { color: #666; }
.blog-search i {
    position: absolute;
    left: 18px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted, #8888a8);
}

/* Category filters */
.blog-categories {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 10px;
    max-width: 1200px;
    margin: -12px auto 40px;
    padding: 0 24px;
}
.cat-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 18px;
    border-radius: 24px;
    font-size: 0.85rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s;
    border: 1px solid transparent;
}
.cat-pill:hover {
    transform: translateY(-2px);
}
.cat-pill.active {
    box-shadow: 0 4px 16px rgba(108,92,231,0.2);
}

/* Article grid */
.blog-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 28px;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 24px 60px;
}
@media (max-width: 420px) {
    .blog-grid { grid-template-columns: 1fr; }
}

/* Article card */
.blog-card {
    background: var(--al-surface, #12121e);
    border: 1px solid rgba(108,92,231,0.1);
    border-radius: 16px;
    overflow: hidden;
    transition: transform 0.3s, border-color 0.3s, box-shadow 0.3s;
    display: flex;
    flex-direction: column;
    text-decoration: none;
}
.blog-card:hover {
    transform: translateY(-6px);
    border-color: rgba(108,92,231,0.3);
    box-shadow: 0 12px 40px rgba(108,92,231,0.12), 0 0 0 1px rgba(108,92,231,0.1);
}
.blog-card-img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-bottom: 1px solid rgba(108,92,231,0.08);
}
.blog-card-body {
    padding: 24px;
    flex: 1;
    display: flex;
    flex-direction: column;
}
.blog-card-cat {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
    padding: 4px 12px;
    border-radius: 16px;
    width: fit-content;
}
.blog-card h2 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 1.2rem;
    font-weight: 700;
    color: #fff;
    line-height: 1.3;
    margin-bottom: 10px;
    transition: color 0.2s;
}
.blog-card:hover h2 { color: var(--al-accent, #6c5ce7); }
.blog-card-excerpt {
    font-size: 0.92rem;
    color: var(--text-muted, #8888a8);
    line-height: 1.6;
    margin-bottom: 16px;
    flex: 1;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.blog-card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.82rem;
    color: var(--text-muted, #8888a8);
    padding-top: 14px;
    border-top: 1px solid rgba(255,255,255,0.06);
}
.blog-card-footer i { margin-right: 5px; opacity: 0.7; }

/* Pagination */
.blog-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    padding: 0 24px 80px;
}
.blog-pagination a,
.blog-pagination span {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    height: 40px;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}
.blog-pagination a {
    background: var(--al-surface, #12121e);
    border: 1px solid rgba(108,92,231,0.15);
    color: #c0c0d8;
}
.blog-pagination a:hover {
    background: var(--al-accent, #6c5ce7);
    color: #fff;
    border-color: var(--al-accent, #6c5ce7);
}
.blog-pagination .current {
    background: var(--al-accent, #6c5ce7);
    color: #fff;
    border: 1px solid var(--al-accent, #6c5ce7);
}

/* Empty state */
.blog-empty {
    text-align: center;
    padding: 60px 24px;
    color: var(--text-muted, #8888a8);
}
.blog-empty i {
    font-size: 3rem;
    opacity: 0.3;
    margin-bottom: 16px;
    display: block;
}
.blog-empty h3 { color: #fff; margin-bottom: 8px; }
</style>

<main id="main">
    <!-- Hero -->
    <section class="blog-hero">
        <div class="container">
            <h1>The GoSiteMe <span>Blog</span></h1>
            <p>Tutorials, AI insights, case studies, and product news. Learn how to build smarter with Alfred AI and 1,220+ tools.</p>
            
            <form class="blog-search" action="/articles/" method="get">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Search articles..." value="<?php echo htmlspecialchars($search_query); ?>" aria-label="Search articles">
            </form>
        </div>
    </section>

    <!-- Category filter pills -->
    <nav class="blog-categories" aria-label="Article categories">
        <a href="/articles/" class="cat-pill <?php echo !$filter_category ? 'active' : ''; ?>" style="background: <?php echo !$filter_category ? 'rgba(108,92,231,0.2)' : 'rgba(255,255,255,0.05)'; ?>; color: <?php echo !$filter_category ? '#a29bfe' : '#8888a8'; ?>; border-color: <?php echo !$filter_category ? 'rgba(108,92,231,0.3)' : 'transparent'; ?>;">
            <i class="fas fa-th-large"></i> All
        </a>
        <?php foreach ($category_config as $slug => $cfg): 
            $is_active = ($filter_category === $slug);
        ?>
        <a href="/articles/category/<?php echo $slug; ?>" class="cat-pill <?php echo $is_active ? 'active' : ''; ?>" style="background: <?php echo $is_active ? $cfg['color'] . '33' : 'rgba(255,255,255,0.05)'; ?>; color: <?php echo $is_active ? $cfg['color'] : '#8888a8'; ?>; border-color: <?php echo $is_active ? $cfg['color'] . '55' : 'transparent'; ?>;">
            <i class="fas <?php echo $cfg['icon']; ?>"></i> <?php echo $cfg['label']; ?>
        </a>
        <?php endforeach; ?>
    </nav>

    <?php if ($search_query !== ''): ?>
    <div style="max-width: 1200px; margin: 0 auto 24px; padding: 0 24px;">
        <p style="color: var(--text-muted, #8888a8); font-size: 0.95rem;">
            <i class="fas fa-search" style="margin-right: 8px;"></i>
            Showing results for "<strong style="color: #fff;"><?php echo htmlspecialchars($search_query); ?></strong>" 
            (<?php echo $total; ?> article<?php echo $total !== 1 ? 's' : ''; ?> found)
            <a href="/articles/" style="color: var(--al-accent, #6c5ce7); margin-left: 12px; text-decoration: none;">Clear search</a>
        </p>
    </div>
    <?php endif; ?>

    <!-- Article grid -->
    <?php if (empty($articles)): ?>
    <div class="blog-empty">
        <i class="fas fa-newspaper"></i>
        <h3>No articles found</h3>
        <p>Try a different search term or <a href="/articles/" style="color: var(--al-accent, #6c5ce7);">browse all articles</a>.</p>
    </div>
    <?php else: ?>
    <div class="blog-grid">
        <?php foreach ($articles as $article): 
            $a_cat = $category_config[$article['category'] ?? 'tutorials'] ?? $category_config['tutorials'];
            $a_date = date('M j, Y', strtotime($article['date'] ?? 'now'));
        ?>
        <a href="/articles/<?php echo htmlspecialchars($article['slug']); ?>" class="blog-card">
            <img class="blog-card-img" src="<?php echo htmlspecialchars($article['featured_image'] ?? '/assets/img/blog/placeholder.png'); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>" loading="lazy">
            <div class="blog-card-body">
                <span class="blog-card-cat" style="background: <?php echo $a_cat['color']; ?>22; color: <?php echo $a_cat['color']; ?>;">
                    <i class="fas <?php echo $a_cat['icon']; ?>"></i> <?php echo $a_cat['label']; ?>
                </span>
                <h2><?php echo htmlspecialchars($article['title']); ?></h2>
                <p class="blog-card-excerpt"><?php echo htmlspecialchars($article['description'] ?? ''); ?></p>
                <div class="blog-card-footer">
                    <span><i class="fas fa-calendar-alt"></i> <?php echo $a_date; ?></span>
                    <span><i class="fas fa-clock"></i> <?php echo htmlspecialchars($article['read_time'] ?? '5 min'); ?></span>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <nav class="blog-pagination" aria-label="Blog pagination">
        <?php 
        $base_url = '/articles/';
        $params = [];
        if ($filter_category) $params['category'] = $filter_category;
        if ($search_query) $params['search'] = $search_query;
        
        if ($page > 1): 
            $params['page'] = $page - 1;
        ?>
        <a href="<?php echo $base_url . '?' . http_build_query($params); ?>" aria-label="Previous page"><i class="fas fa-chevron-left"></i></a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_pages; $i++): 
            $params['page'] = $i;
            if ($i === $page): ?>
            <span class="current"><?php echo $i; ?></span>
            <?php else: ?>
            <a href="<?php echo $base_url . '?' . http_build_query($params); ?>"><?php echo $i; ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($page < $total_pages): 
            $params['page'] = $page + 1;
        ?>
        <a href="<?php echo $base_url . '?' . http_build_query($params); ?>" aria-label="Next page"><i class="fas fa-chevron-right"></i></a>
        <?php endif; ?>
    </nav>
    <?php endif; ?>
    <?php endif; ?>
</main>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/site-footer.inc.php'; ?>
