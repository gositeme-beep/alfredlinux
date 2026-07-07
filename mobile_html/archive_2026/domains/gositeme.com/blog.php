<?php
$page_title = 'Blog — GoSiteMe | AI, Hosting & Voice Technology Insights';
$page_description = 'Read the latest from GoSiteMe — guides on AI hosting, Alfred AI tools, voice agents, web development, and building with AI. Tutorials, comparisons, and industry insights.';
$page_canonical = 'https://gositeme.com/blog';
$page_og_title = 'GoSiteMe Blog — AI, Hosting & Voice Technology';
$page_og_description = 'Guides, tutorials, and insights on AI hosting, Alfred AI, voice agents, and web development. Learn how to build smarter with GoSiteMe.';
$page_rss = 'https://gositeme.com/feed/rss';
include __DIR__ . '/includes/site-header.inc.php';

// Scan articles directory for posts
$articlesDir = __DIR__ . '/articles';
$articles = [];

// Map of known articles with metadata
$articleMeta = [
    'getting-started-with-alfred' => ['title' => 'Getting Started with Alfred AI', 'desc' => 'Your complete guide to setting up and using Alfred — from first login to 13,000+ tools.', 'category' => 'Getting Started', 'icon' => 'fa-rocket', 'date' => '2026-03-01'],
    '875-tools-complete-guide' => ['title' => '13,000+ Tools: The Complete Guide', 'desc' => 'Every tool category explained — file management, WordPress, databases, AI media, security, and more.', 'category' => 'Deep Dive', 'icon' => 'fa-tools', 'date' => '2026-03-01'],
    'alfred-for-students' => ['title' => 'Alfred AI for Students', 'desc' => 'How students use Alfred for essays, research, coding assignments, and building portfolios.', 'category' => 'Use Cases', 'icon' => 'fa-graduation-cap', 'date' => '2026-03-02'],
    'voice-first-ai-future' => ['title' => 'Voice-First AI: The Future is Now', 'desc' => 'Why voice interfaces are replacing dashboards. How Alfred uses voice commands, phone calls, and SMS.', 'category' => 'Industry', 'icon' => 'fa-microphone', 'date' => '2026-03-02'],
    'alfred-legal-aid-canada' => ['title' => 'Alfred Legal Aid for Canada', 'desc' => 'The Jailhouse Lawyer program — AI-powered legal assistance for Canadian inmates and underserved communities.', 'category' => 'Social Impact', 'icon' => 'fa-gavel', 'date' => '2026-03-02'],
    'fleet-management-guide' => ['title' => 'Fleet Management Guide', 'desc' => 'Deploy and manage multiple AI agents. Role-based access, monitoring, and enterprise-scale orchestration.', 'category' => 'Enterprise', 'icon' => 'fa-network-wired', 'date' => '2026-03-02'],
    'alfred-vs-chatgpt' => ['title' => 'Alfred vs ChatGPT: Full Comparison', 'desc' => 'What makes Alfred different from ChatGPT? Tools, hosting, voice, file access, and more — side by side.', 'category' => 'Comparison', 'icon' => 'fa-balance-scale', 'date' => '2026-03-03'],
    'small-business-ai-tools' => ['title' => 'AI Tools for Small Business', 'desc' => 'How small businesses save time and money with AI hosting, automated customer support, and voice agents.', 'category' => 'Business', 'icon' => 'fa-store', 'date' => '2026-03-03'],
    'building-ai-agents' => ['title' => 'Building AI Agents', 'desc' => 'Create custom AI voice agents for your business — receptionist, sales, support, appointment booking.', 'category' => 'Tutorial', 'icon' => 'fa-robot', 'date' => '2026-03-03'],
    'ai-conference-rooms' => ['title' => 'AI Conference Rooms', 'desc' => 'Multi-participant voice rooms where your team and Alfred collaborate in real time.', 'category' => 'Features', 'icon' => 'fa-users', 'date' => '2026-03-03'],
    'ai-voice-agent-setup-guide' => ['title' => 'AI Voice Agent Setup Guide', 'desc' => 'Step-by-step guide to setting up your first AI voice agent with GoSiteMe.', 'category' => 'Tutorial', 'icon' => 'fa-phone-volume', 'date' => '2026-03-04'],
    'ai-receptionist-cost' => ['title' => 'AI Receptionist: Cost Breakdown', 'desc' => 'How much does an AI receptionist cost vs. a human? Full ROI analysis for small businesses.', 'category' => 'Business', 'icon' => 'fa-calculator', 'date' => '2026-03-04'],
    'small-business-ai-tools-2025' => ['title' => 'Small Business AI Tools in 2025', 'desc' => 'The definitive guide to AI tools every small business should be using in 2025.', 'category' => 'Business', 'icon' => 'fa-briefcase', 'date' => '2026-03-04'],
    'ai-phone-answering-service-2025' => ['title' => 'AI Phone Answering Service 2025', 'desc' => 'AI phone answering vs. traditional services — cost, quality, and availability compared.', 'category' => 'Industry', 'icon' => 'fa-headset', 'date' => '2026-03-04'],
    'reduce-customer-support-costs' => ['title' => 'Reduce Customer Support Costs', 'desc' => 'How AI voice agents cut support costs by 60% while improving customer satisfaction.', 'category' => 'Business', 'icon' => 'fa-chart-line', 'date' => '2026-03-04'],
    'chatbot-vs-voice-ai' => ['title' => 'Chatbot vs Voice AI', 'desc' => 'Text chatbots vs. conversational voice AI — when to use each and why voice wins for support.', 'category' => 'Comparison', 'icon' => 'fa-comment-dots', 'date' => '2026-03-04'],
];

// Scan for .php articles
if (is_dir($articlesDir)) {
    foreach (glob("$articlesDir/*.php") as $file) {
        $slug = basename($file, '.php');
        if ($slug === 'index' || $slug === 'article-template.inc') continue;
        $meta = $articleMeta[$slug] ?? [
            'title' => ucwords(str_replace('-', ' ', $slug)),
            'desc' => '',
            'category' => 'Article',
            'icon' => 'fa-newspaper',
            'date' => date('Y-m-d', filemtime($file)),
        ];
        $articles[] = array_merge($meta, ['slug' => $slug]);
    }
}

// Also check for directory-based articles (clean URLs)
if (is_dir($articlesDir)) {
    foreach (glob("$articlesDir/*/index.php") as $file) {
        $slug = basename(dirname($file));
        if (isset($articleMeta[$slug]) && !in_array($slug, array_column($articles, 'slug'))) {
            $meta = $articleMeta[$slug];
            $articles[] = array_merge($meta, ['slug' => $slug]);
        }
    }
}

// Sort by date descending
usort($articles, fn($a, $b) => strcmp($b['date'], $a['date']));
$totalArticles = count($articles);

// Collect categories for filter
$categories = array_unique(array_column($articles, 'category'));
sort($categories);

// Search and category filter
$searchQuery = trim($_GET['q'] ?? '');
$categoryFilter = trim($_GET['category'] ?? '');

$filteredArticles = $articles;
if ($searchQuery !== '') {
    $q = strtolower($searchQuery);
    $filteredArticles = array_filter($filteredArticles, fn($a) =>
        stripos($a['title'], $q) !== false || stripos($a['desc'], $q) !== false || stripos($a['category'], $q) !== false
    );
}
if ($categoryFilter !== '') {
    $filteredArticles = array_filter($filteredArticles, fn($a) => $a['category'] === $categoryFilter);
}
$filteredArticles = array_values($filteredArticles);

// Pagination
$perPage = 12;
$page = max(1, (int)($_GET['page'] ?? 1));
$totalFiltered = count($filteredArticles);
$totalPages = max(1, (int)ceil($totalFiltered / $perPage));
$page = min($page, $totalPages);
$pagedArticles = array_slice($filteredArticles, ($page - 1) * $perPage, $perPage);
?>

<style>
    .blog-hero { padding: 140px 0 60px; text-align: center; background: linear-gradient(180deg, rgba(125,0,255,0.08), transparent); }
    .blog-hero h1 { font-family: 'Space Grotesk', sans-serif; font-size: clamp(2rem, 5vw, 3.2rem); font-weight: 800; margin-bottom: 16px; background: linear-gradient(135deg, #fff, #c084fc, #00D4FF); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
    .blog-hero p { color: #a8b2d1; font-size: 1.1rem; max-width: 650px; margin: 0 auto; }
    .blog-stats { display: flex; gap: 32px; justify-content: center; margin-top: 24px; }
    .blog-stats span { color: #fff; font-weight: 700; font-size: 0.9rem; }
    .blog-stats span em { color: #7D00FF; font-style: normal; font-size: 1.4rem; display: block; }
    .blog-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 24px; max-width: 1200px; margin: 0 auto; padding: 40px 24px 80px; }
    .blog-card { background: rgba(26,26,46,0.8); border: 1px solid rgba(255,255,255,0.06); border-radius: 14px; padding: 28px; transition: all 0.3s ease; display: flex; flex-direction: column; }
    .blog-card:hover { border-color: rgba(125,0,255,0.3); transform: translateY(-4px); box-shadow: 0 12px 40px rgba(125,0,255,0.15); }
    .blog-card-header { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; }
    .blog-card-icon { width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, rgba(125,0,255,0.2), rgba(0,168,255,0.2)); display: flex; align-items: center; justify-content: center; color: #c084fc; font-size: 1rem; }
    .blog-card-category { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #7D00FF; background: rgba(125,0,255,0.1); padding: 3px 10px; border-radius: 20px; }
    .blog-card h3 { font-family: 'Space Grotesk', sans-serif; font-size: 1.15rem; font-weight: 700; color: #fff; margin-bottom: 8px; line-height: 1.3; }
    .blog-card h3 a { color: inherit; text-decoration: none; }
    .blog-card h3 a:hover { color: #00D4FF; }
    .blog-card p { color: #a8b2d1; font-size: 0.88rem; line-height: 1.6; flex: 1; }
    .blog-card-footer { display: flex; align-items: center; justify-content: space-between; margin-top: 16px; padding-top: 14px; border-top: 1px solid rgba(255,255,255,0.05); }
    .blog-card-date { font-size: 0.78rem; color: rgba(255,255,255,0.35); }
    .blog-card-link { font-size: 0.85rem; font-weight: 600; color: #00D4FF; text-decoration: none; }
    .blog-card-link:hover { text-decoration: underline; }
    .blog-cta { text-align: center; padding: 60px 24px; background: linear-gradient(135deg, rgba(125,0,255,0.05), rgba(0,212,255,0.05)); border-top: 1px solid rgba(255,255,255,0.04); }
    .blog-cta h2 { font-family: 'Space Grotesk', sans-serif; font-size: 1.8rem; font-weight: 700; color: #fff; margin-bottom: 12px; }
    .blog-cta p { color: #a8b2d1; max-width: 500px; margin: 0 auto 24px; }
    @media (max-width: 768px) {
        .blog-grid { grid-template-columns: 1fr; }
        .blog-stats { gap: 20px; }
    }
    .blog-filters{max-width:1200px;margin:0 auto;padding:24px 24px 0;display:flex;gap:12px;flex-wrap:wrap;align-items:center}
    .blog-search{flex:1;min-width:200px;padding:10px 16px;border-radius:10px;border:1px solid rgba(255,255,255,0.1);background:rgba(26,26,46,0.8);color:#fff;font-size:.9rem;outline:none}
    .blog-search:focus{border-color:rgba(125,0,255,0.4)}
    .blog-cat-btn{padding:6px 14px;border-radius:20px;border:1px solid rgba(255,255,255,0.1);background:transparent;color:#a8b2d1;font-size:.78rem;cursor:pointer;transition:all .2s;text-decoration:none}
    .blog-cat-btn:hover,.blog-cat-btn.active{background:rgba(125,0,255,0.2);border-color:rgba(125,0,255,0.4);color:#fff}
    .blog-pagination{display:flex;justify-content:center;gap:8px;padding:32px 24px}
    .blog-pagination a,.blog-pagination span{padding:8px 14px;border-radius:8px;font-size:.85rem;text-decoration:none;border:1px solid rgba(255,255,255,0.1);color:#a8b2d1}
    .blog-pagination a:hover{background:rgba(125,0,255,0.15);color:#fff}
    .blog-pagination .current{background:rgba(125,0,255,0.3);color:#fff;border-color:rgba(125,0,255,0.5)}
</style>

<section class="blog-hero">
    <div class="container">
        <h1>GoSiteMe Blog</h1>
        <p>Guides, tutorials, and insights on AI hosting, voice agents, and building with Alfred — the most powerful AI assistant ever made.</p>
        <div class="blog-stats">
            <span><em><?php echo $totalArticles; ?></em>Articles</span>
            <span><em>13,000+</em>AI Tools</span>
            <span><em>16</em>AI Engines</span>
        </div>
    </div>
</section>

<div class="blog-filters">
    <form method="get" action="/blog" style="flex:1;min-width:200px;display:flex">
        <?php if ($categoryFilter): ?><input type="hidden" name="category" value="<?php echo htmlspecialchars($categoryFilter); ?>"><?php endif; ?>
        <input type="search" name="q" class="blog-search" placeholder="Search articles..." value="<?php echo htmlspecialchars($searchQuery); ?>" aria-label="Search articles">
    </form>
    <a href="/blog" class="blog-cat-btn <?php echo $categoryFilter === '' ? 'active' : ''; ?>">All</a>
    <?php foreach ($categories as $cat): ?>
        <a href="/blog?category=<?php echo urlencode($cat); ?><?php echo $searchQuery ? '&q=' . urlencode($searchQuery) : ''; ?>" class="blog-cat-btn <?php echo $categoryFilter === $cat ? 'active' : ''; ?>"><?php echo htmlspecialchars($cat); ?></a>
    <?php endforeach; ?>
</div>

<?php if ($searchQuery || $categoryFilter): ?>
<div style="max-width:1200px;margin:12px auto 0;padding:0 24px;font-size:.85rem;color:#a8b2d1">
    Showing <?php echo $totalFiltered; ?> result<?php echo $totalFiltered !== 1 ? 's' : ''; ?>
    <?php if ($searchQuery): ?> for "<strong style="color:#fff"><?php echo htmlspecialchars($searchQuery); ?></strong>"<?php endif; ?>
    <?php if ($categoryFilter): ?> in <strong style="color:#c084fc"><?php echo htmlspecialchars($categoryFilter); ?></strong><?php endif; ?>
</div>
<?php endif; ?>

<section>
    <div class="blog-grid">
        <?php if (empty($articles)): ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 60px 0;">
                <p style="color: #a8b2d1; font-size: 1.1rem;">Articles are coming soon! In the meantime, check out our <a href="/articles/" style="color: #00D4FF;">articles directory</a>.</p>
            </div>
        <?php else: ?>
            <?php foreach ($pagedArticles as $article): ?>
                <div class="blog-card">
                    <div class="blog-card-header">
                        <div class="blog-card-icon"><i class="fas <?php echo htmlspecialchars($article['icon']); ?>"></i></div>
                        <span class="blog-card-category"><?php echo htmlspecialchars($article['category']); ?></span>
                    </div>
                    <h3><a href="/articles/<?php echo htmlspecialchars($article['slug']); ?>"><?php echo htmlspecialchars($article['title']); ?></a></h3>
                    <p><?php echo htmlspecialchars($article['desc']); ?></p>
                    <div class="blog-card-footer">
                        <span class="blog-card-date"><?php echo date('M j, Y', strtotime($article['date'])); ?></span>
                        <a href="/articles/<?php echo htmlspecialchars($article['slug']); ?>" class="blog-card-link">Read Article <i class="fas fa-arrow-right" style="margin-left:4px; font-size:0.75rem;"></i></a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php if ($totalPages > 1): ?>
<nav class="blog-pagination" aria-label="Blog pagination">
    <?php
    $queryParams = [];
    if ($searchQuery) $queryParams['q'] = $searchQuery;
    if ($categoryFilter) $queryParams['category'] = $categoryFilter;
    
    if ($page > 1):
        $queryParams['page'] = $page - 1;
    ?>
        <a href="/blog?<?php echo http_build_query($queryParams); ?>">&laquo; Prev</a>
    <?php endif; ?>
    
    <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++):
        $queryParams['page'] = $p;
        if ($p === $page): ?>
            <span class="current"><?php echo $p; ?></span>
        <?php else: ?>
            <a href="/blog?<?php echo http_build_query($queryParams); ?>"><?php echo $p; ?></a>
        <?php endif;
    endfor; ?>
    
    <?php if ($page < $totalPages):
        $queryParams['page'] = $page + 1;
    ?>
        <a href="/blog?<?php echo http_build_query($queryParams); ?>">Next &raquo;</a>
    <?php endif; ?>
</nav>
<?php endif; ?>

<section class="blog-cta">
    <h2>Try Alfred Today</h2>
    <p>Everything you read about — 13,000+ tools, voice commands, AI media, code interpreter — try it all for $15/month.</p>
    <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
        <a href="/cart?a=add&pid=18" class="btn btn-primary btn-lg" style="display:inline-flex;align-items:center;gap:8px;padding:14px 28px;background:linear-gradient(135deg,#7D00FF,#00A8FF);color:#fff;border-radius:12px;font-weight:700;text-decoration:none;"><i class="fas fa-rocket"></i> Start AI Hosting</a>
        <a href="/alfred" class="btn btn-ghost" style="display:inline-flex;align-items:center;gap:8px;padding:14px 28px;border:1px solid rgba(255,255,255,0.15);color:rgba(255,255,255,0.7);border-radius:12px;font-weight:600;text-decoration:none;"><i class="fas fa-robot"></i> Meet Alfred</a>
    </div>
</section>

<!-- Schema.org -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "Blog",
    "name": "GoSiteMe Blog",
    "description": "Guides, tutorials, and insights on AI hosting, Alfred AI, voice agents, and web development.",
    "url": "https://gositeme.com/blog",
    "publisher": {
        "@type": "Organization",
        "name": "GoSiteMe",
        "url": "https://gositeme.com"
    },
    "blogPost": [
        <?php
        $schemaArticles = [];
        foreach (array_slice($articles, 0, 10) as $a) {
            $schemaArticles[] = json_encode([
                '@type' => 'BlogPosting',
                'headline' => $a['title'],
                'description' => $a['desc'],
                'url' => 'https://gositeme.com/articles/' . $a['slug'],
                'datePublished' => $a['date'],
                'author' => ['@type' => 'Organization', 'name' => 'GoSiteMe'],
            ], JSON_UNESCAPED_SLASHES);
        }
        echo implode(",\n        ", $schemaArticles);
        ?>
    ]
}
</script>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
