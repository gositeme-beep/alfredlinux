<?php
$page_title = 'Agent Templates - Alfred AI';
$page_description = 'Deploy pre-built AI agents in seconds. Browse 150+ templates for customer support, sales, voice, outdoor, culinary, finance, and more.';
$page_canonical = 'https://root.com/agent-templates';
$page_og_title = 'Agent Templates — Deploy AI Agents Instantly | Alfred AI';
$page_og_description = $page_description;
$page_twitter_description = 'Browse 150+ pre-built AI agent templates. Deploy in seconds — support, sales, voice, outdoor, health, finance, and more.';
if (session_status() === PHP_SESSION_NONE) session_start();
$is_logged_in = !empty($_SESSION['logged_in']) && !empty($_SESSION['client_id']);
$client_id = $is_logged_in ? (int)$_SESSION['client_id'] : 0;
require_once __DIR__ . '/includes/site-header.inc.php';

/* ── Template Data (shared source of truth) ── */
require_once __DIR__ . '/includes/agent-template-data.inc.php';
$TEMPLATES = $AGENT_TEMPLATES;
$CATEGORIES = $AGENT_CATEGORIES;
$difficulty_colors = ['beginner' => '#00b894', 'intermediate' => '#fdcb6e', 'advanced' => '#e17055'];
?>

<!-- Schema.org SoftwareApplication markup -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "CollectionPage",
    "name": "Alfred AI Agent Templates",
    "description": "<?php echo htmlspecialchars($page_description); ?>",
    "url": "<?php echo htmlspecialchars($page_canonical); ?>",
    "mainEntity": {
        "@type": "ItemList",
        "numberOfItems": <?php echo count($TEMPLATES); ?>,
        "itemListElement": [
<?php foreach ($TEMPLATES as $i => $t): ?>
            {
                "@type": "ListItem",
                "position": <?php echo $i + 1; ?>,
                "item": {
                    "@type": "SoftwareApplication",
                    "name": "<?php echo htmlspecialchars($t['name']); ?>",
                    "description": "<?php echo htmlspecialchars($t['description']); ?>",
                    "applicationCategory": "AI Agent Template",
                    "operatingSystem": "Web",
                    "offers": { "@type": "Offer", "price": "0", "priceCurrency": "USD" }
                }
            }<?php echo $i < count($TEMPLATES) - 1 ? ',' : ''; ?>

<?php endforeach; ?>
        ]
    }
}
</script>

<style>
/* ===== Agent Templates Page ===== */
:root {
    --at-bg: #0a0a14;
    --at-surface: #12121e;
    --at-surface-2: #1a1a2e;
    --at-surface-hover: #22223a;
    --at-border: rgba(255,255,255,0.08);
    --at-accent: #6c5ce7;
    --at-accent-light: #a29bfe;
    --at-accent-glow: rgba(108,92,231,0.35);
    --at-green: #00b894;
    --at-yellow: #fdcb6e;
    --at-red: #e17055;
    --at-blue: #0984e3;
    --at-cyan: #00cec9;
    --at-text: #e8e8f0;
    --at-text-muted: #8a8a9a;
    --at-radius: 14px;
    --at-radius-sm: 8px;
    --at-shadow: 0 4px 24px rgba(0,0,0,0.3);
    --at-font: 'Segoe UI', system-ui, -apple-system, sans-serif;
    --at-mono: 'JetBrains Mono', 'Fira Code', monospace;
}

/* Hero */
.at-hero {
    padding: 80px 0 40px;
    text-align: center;
    background: linear-gradient(135deg, #0a0a14 0%, #1a1033 50%, #0a0a14 100%);
    position: relative;
    overflow: hidden;
}
.at-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    left: 50%;
    transform: translateX(-50%);
    width: 800px;
    height: 800px;
    background: radial-gradient(circle, var(--at-accent-glow) 0%, transparent 70%);
    pointer-events: none;
    animation: atHeroPulse 6s ease-in-out infinite;
}
@keyframes atHeroPulse {
    0%, 100% { opacity: .4; transform: translateX(-50%) scale(1); }
    50%      { opacity: .7; transform: translateX(-50%) scale(1.1); }
}
.at-hero h1 {
    font-size: clamp(2rem, 5vw, 3.2rem);
    font-weight: 800;
    letter-spacing: -.02em;
    position: relative;
    color: var(--at-text);
}
.at-hero h1 .highlight {
    background: linear-gradient(135deg, var(--at-accent), var(--at-cyan));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
.at-hero .subtitle {
    color: var(--at-text-muted);
    font-size: 1.15rem;
    margin-top: .8rem;
    position: relative;
}
.at-hero-count {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    background: var(--at-surface-2);
    border: 1px solid var(--at-border);
    padding: .5rem 1.2rem;
    border-radius: 50px;
    margin-top: 1.2rem;
    font-size: .95rem;
    position: relative;
    color: var(--at-text);
}
.at-hero-count strong {
    color: var(--at-green);
    font-family: var(--at-mono);
    font-size: 1.2rem;
}

/* Search */
.at-search-wrap {
    max-width: 540px;
    margin: 1.8rem auto 0;
    position: relative;
}
.at-search-wrap i {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--at-text-muted);
    font-size: 1rem;
}
.at-search {
    width: 100%;
    padding: .9rem 1rem .9rem 44px;
    background: var(--at-surface-2);
    border: 1px solid var(--at-border);
    border-radius: 50px;
    color: var(--at-text);
    font-size: 1rem;
    outline: none;
    transition: border-color .2s, box-shadow .2s;
}
.at-search::placeholder { color: var(--at-text-muted); }
.at-search:focus {
    border-color: var(--at-accent);
    box-shadow: 0 0 0 3px var(--at-accent-glow);
}

/* Category tabs */
.at-tabs {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: .6rem;
    max-width: 1200px;
    margin: 2rem auto 0;
    padding: 0 1rem;
}
.at-tab {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .5rem 1rem;
    background: var(--at-surface-2);
    border: 1px solid var(--at-border);
    border-radius: 50px;
    color: var(--at-text-muted);
    font-size: .85rem;
    cursor: pointer;
    transition: all .2s;
    white-space: nowrap;
}
.at-tab:hover, .at-tab.active {
    background: var(--at-accent);
    color: #fff;
    border-color: var(--at-accent);
}
.at-tab .tab-count {
    background: rgba(255,255,255,0.15);
    padding: 1px 7px;
    border-radius: 20px;
    font-size: .75rem;
    font-weight: 700;
}

/* Main container */
.at-container {
    max-width: 1240px;
    margin: 0 auto;
    padding: 2rem 1.5rem 4rem;
}

/* Category sections */
.at-category-section {
    margin-bottom: 3rem;
}
.at-category-header {
    display: flex;
    align-items: center;
    gap: .7rem;
    margin-bottom: 1.3rem;
    padding-bottom: .8rem;
    border-bottom: 1px solid var(--at-border);
}
.at-category-header .cat-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    color: #fff;
}
.at-category-header h2 {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--at-text);
}
.at-category-header .cat-count {
    color: var(--at-text-muted);
    font-size: .85rem;
    margin-left: auto;
}

/* Template grid */
.at-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.2rem;
}
@media (max-width: 960px) {
    .at-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 768px) {
    .at-hero { padding: 60px 0 30px; }
    .at-hero h1 { font-size: 1.8rem; }
    .at-hero .subtitle { font-size: 1rem; }
    .at-hero-count { font-size: 0.85rem; padding: 0.4rem 1rem; }
    .at-search-wrap { margin: 1.2rem auto 0; }
    .at-tabs { gap: 0.4rem; margin: 1.5rem auto 0; }
    .at-tab { padding: 0.4rem 0.8rem; font-size: 0.8rem; }
    .at-category-header h2 { font-size: 1.1rem; }
}
@media (max-width: 600px) {
    .at-grid { grid-template-columns: 1fr; }
    .at-hero { padding: 50px 0 24px; }
    .at-hero h1 { font-size: 1.5rem; }
    .at-search-wrap { max-width: 100%; margin: 1rem 1rem 0; }
    .at-search { width: 100%; font-size: 0.9rem; }
    .at-tabs { overflow-x: auto; flex-wrap: nowrap; justify-content: flex-start; -webkit-overflow-scrolling: touch; scrollbar-width: none; padding: 0 1rem; }
    .at-tabs::-webkit-scrollbar { display: none; }
    .at-modal-info-grid { grid-template-columns: 1fr; }
    .at-modal-config-row { flex-direction: column; gap: 0.2rem; }
    .at-card { padding: 1rem; }
    .at-card-icon { width: 38px; height: 38px; font-size: 1rem; }
}
@media (pointer: coarse) {
    .at-tab { min-height: 44px; }
    .at-card { min-height: 44px; }
    .at-search { min-height: 44px; }
}

/* Template card */
.at-card {
    background: var(--at-surface);
    border: 1px solid var(--at-border);
    border-radius: var(--at-radius);
    padding: 1.4rem;
    display: flex;
    flex-direction: column;
    gap: .8rem;
    transition: transform .2s, border-color .2s, box-shadow .2s;
    position: relative;
    overflow: hidden;
}
.at-card:hover {
    transform: translateY(-3px);
    border-color: var(--at-accent);
    box-shadow: 0 8px 32px rgba(108,92,231,0.15);
}
.at-card-top {
    display: flex;
    align-items: flex-start;
    gap: .8rem;
}
.at-card-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: #fff;
    flex-shrink: 0;
}
.at-card-meta {
    flex: 1;
    min-width: 0;
}
.at-card-meta h3 {
    font-size: 1rem;
    font-weight: 700;
    color: var(--at-text);
    margin: 0;
    line-height: 1.3;
}
.at-card-badges {
    display: flex;
    gap: .4rem;
    margin-top: .3rem;
    flex-wrap: wrap;
}
.at-badge {
    display: inline-flex;
    align-items: center;
    gap: .25rem;
    font-size: .7rem;
    padding: 2px 8px;
    border-radius: 20px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .03em;
}
.at-badge-role {
    background: rgba(108,92,231,0.15);
    color: var(--at-accent-light);
}
.at-badge-diff {
    font-weight: 700;
}
.at-card-desc {
    color: var(--at-text-muted);
    font-size: .875rem;
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.at-card-tools {
    display: flex;
    align-items: center;
    gap: .4rem;
    color: var(--at-text-muted);
    font-size: .78rem;
}
.at-card-tools i { color: var(--at-accent-light); font-size: .85rem; }
.at-card-actions {
    display: flex;
    gap: .6rem;
    margin-top: auto;
}
.at-btn {
    flex: 1;
    padding: .6rem .8rem;
    border: none;
    border-radius: var(--at-radius-sm);
    font-size: .85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all .2s;
    text-align: center;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .4rem;
}
.at-btn-deploy {
    background: var(--at-accent);
    color: #fff;
}
.at-btn-deploy:hover {
    background: #7c6cf7;
    box-shadow: 0 4px 16px var(--at-accent-glow);
}
.at-btn-preview {
    background: var(--at-surface-2);
    color: var(--at-text);
    border: 1px solid var(--at-border);
}
.at-btn-preview:hover {
    border-color: var(--at-accent);
    color: var(--at-accent-light);
}

/* No results */
.at-no-results {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--at-text-muted);
    display: none;
}
.at-no-results i { font-size: 3rem; margin-bottom: 1rem; color: var(--at-accent); }
.at-no-results h3 { color: var(--at-text); margin-bottom: .5rem; }

/* ===== Modal ===== */
.at-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.7);
    backdrop-filter: blur(6px);
    z-index: 9999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}
.at-modal-overlay.visible {
    display: flex;
}
.at-modal {
    background: var(--at-surface);
    border: 1px solid var(--at-border);
    border-radius: var(--at-radius);
    width: 100%;
    max-width: 620px;
    max-height: 90vh;
    overflow-y: auto;
    padding: 2rem;
    position: relative;
    box-shadow: 0 24px 80px rgba(0,0,0,0.5);
    animation: atModalIn .25s ease-out;
}
@keyframes atModalIn {
    from { opacity: 0; transform: translateY(20px) scale(.97); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}
.at-modal-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: var(--at-surface-2);
    border: 1px solid var(--at-border);
    border-radius: 50%;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--at-text-muted);
    cursor: pointer;
    font-size: 1rem;
    transition: all .2s;
}
.at-modal-close:hover {
    color: #fff;
    border-color: var(--at-accent);
}
.at-modal-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.at-modal-icon {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: #fff;
    flex-shrink: 0;
}
.at-modal-title h2 {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--at-text);
    margin: 0;
}
.at-modal-title .modal-badges {
    display: flex;
    gap: .4rem;
    margin-top: .3rem;
}
.at-modal-body section {
    margin-bottom: 1.3rem;
}
.at-modal-body section h4 {
    font-size: .85rem;
    font-weight: 700;
    color: var(--at-text-muted);
    text-transform: uppercase;
    letter-spacing: .05em;
    margin-bottom: .5rem;
}
.at-modal-body section p {
    color: var(--at-text);
    font-size: .95rem;
    line-height: 1.6;
}
.at-modal-tools-list {
    display: flex;
    flex-wrap: wrap;
    gap: .4rem;
}
.at-modal-tool {
    background: var(--at-surface-2);
    border: 1px solid var(--at-border);
    padding: 4px 10px;
    border-radius: 6px;
    font-size: .8rem;
    color: var(--at-accent-light);
    font-family: var(--at-mono);
}
.at-modal-config {
    background: var(--at-surface-2);
    border: 1px solid var(--at-border);
    border-radius: 10px;
    padding: 1rem;
    font-size: .85rem;
    font-family: var(--at-mono);
    color: var(--at-text);
}
.at-modal-config-row {
    display: flex;
    justify-content: space-between;
    padding: .3rem 0;
    border-bottom: 1px solid var(--at-border);
}
.at-modal-config-row:last-child { border-bottom: none; }
.at-modal-config-row .key { color: var(--at-text-muted); }
.at-modal-config-row .val { color: var(--at-accent-light); font-weight: 600; }
.at-modal-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: .8rem;
}
.at-modal-info-item {
    background: var(--at-surface-2);
    border: 1px solid var(--at-border);
    border-radius: 10px;
    padding: .8rem;
    text-align: center;
}
.at-modal-info-item .label {
    font-size: .7rem;
    color: var(--at-text-muted);
    text-transform: uppercase;
    letter-spacing: .05em;
}
.at-modal-info-item .value {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--at-text);
    margin-top: .2rem;
}
.at-modal-footer {
    margin-top: 1.5rem;
    display: flex;
    gap: .8rem;
}
.at-modal-footer .at-btn {
    padding: .8rem 1.2rem;
    font-size: .95rem;
}

/* Deploy toast */
.at-toast {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    background: var(--at-green);
    color: #000;
    padding: 1rem 1.5rem;
    border-radius: var(--at-radius-sm);
    font-weight: 600;
    font-size: .95rem;
    z-index: 10000;
    display: none;
    box-shadow: 0 8px 32px rgba(0,0,0,0.3);
    animation: atToastIn .3s ease-out;
}
@keyframes atToastIn {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}
.at-toast.error { background: var(--at-red); color: #fff; }

/* Scrollbar */
.at-modal::-webkit-scrollbar { width: 5px; }
.at-modal::-webkit-scrollbar-track { background: transparent; }
.at-modal::-webkit-scrollbar-thumb { background: var(--at-accent); border-radius: 3px; }

/* ===== CTA Banner ===== */
.at-cta {
    background: linear-gradient(135deg, var(--at-surface) 0%, rgba(108,92,231,0.12) 100%);
    border: 1px solid var(--at-border);
    border-radius: var(--at-radius);
    padding: 3rem 2rem;
    text-align: center;
    margin-top: 2rem;
}
.at-cta h2 {
    font-size: 1.6rem;
    font-weight: 800;
    color: var(--at-text);
}
.at-cta p {
    color: var(--at-text-muted);
    margin: .5rem 0 1.5rem;
    font-size: 1rem;
}
.at-cta-btn {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    padding: .8rem 2rem;
    background: var(--at-accent);
    color: #fff;
    border-radius: var(--at-radius-sm);
    font-weight: 700;
    font-size: 1rem;
    text-decoration: none;
    transition: all .2s;
}
.at-cta-btn:hover {
    background: #7c6cf7;
    box-shadow: 0 4px 20px var(--at-accent-glow);
    color: #fff;
}
</style>

<!-- ===== HERO ===== -->
<section class="at-hero">
    <h1>Agent <span class="highlight">Templates</span></h1>
    <p class="subtitle">Deploy AI agents in seconds. Choose from <?php echo count($TEMPLATES); ?>+ pre-built templates.</p>
    <div class="at-hero-count"><i class="fa-solid fa-robot"></i> <strong><?php echo count($TEMPLATES); ?></strong> templates ready to deploy</div>
    <div class="at-search-wrap">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" class="at-search" id="atSearch" placeholder="Search templates… e.g. support, sales, voice" autocomplete="off">
    </div>
</section>

<!-- ===== CATEGORY TABS ===== -->
<nav class="at-tabs" id="atTabs">
<?php
$cat_counts = [];
foreach ($TEMPLATES as $t) {
    $cat_counts[$t['category']] = ($cat_counts[$t['category']] ?? 0) + 1;
}
foreach ($CATEGORIES as $key => $cat):
    $count = $key === 'all' ? count($TEMPLATES) : ($cat_counts[$key] ?? 0);
?>
    <button class="at-tab<?php echo $key === 'all' ? ' active' : ''; ?>" data-category="<?php echo $key; ?>">
        <i class="fa-solid <?php echo htmlspecialchars($cat['icon']); ?>"></i>
        <?php echo htmlspecialchars($cat['label']); ?>
        <span class="tab-count"><?php echo $count; ?></span>
    </button>
<?php endforeach; ?>
</nav>

<!-- ===== TEMPLATE GRID ===== -->
<div class="at-container">

<!-- No results message -->
<div class="at-no-results" id="atNoResults">
    <i class="fa-solid fa-search"></i>
    <h3>No templates found</h3>
    <p>Try a different search term or category.</p>
</div>

<?php
// Group templates by category
$grouped = [];
foreach ($TEMPLATES as $t) {
    $grouped[$t['category']][] = $t;
}

foreach ($grouped as $catKey => $catTemplates):
    $catMeta = $CATEGORIES[$catKey] ?? ['label' => $catKey, 'icon' => 'fa-folder', 'color' => '#6c5ce7'];
?>
<section class="at-category-section" id="cat-<?php echo $catKey; ?>" data-category="<?php echo $catKey; ?>">
    <div class="at-category-header">
        <div class="cat-icon" style="background:<?php echo $catMeta['color']; ?>"><i class="fa-solid <?php echo $catMeta['icon']; ?>"></i></div>
        <h2><?php echo htmlspecialchars($catMeta['label']); ?></h2>
        <span class="cat-count"><?php echo count($catTemplates); ?> templates</span>
    </div>
    <div class="at-grid">
    <?php foreach ($catTemplates as $t):
        $diffColor = $difficulty_colors[$t['difficulty']] ?? '#a29bfe';
        $catColor  = $catMeta['color'];
    ?>
        <div class="at-card" data-id="<?php echo htmlspecialchars($t['id']); ?>" data-category="<?php echo htmlspecialchars($t['category']); ?>" data-name="<?php echo htmlspecialchars(strtolower($t['name'])); ?>" data-tags="<?php echo htmlspecialchars(implode(',', $t['tags'])); ?>" data-desc="<?php echo htmlspecialchars(strtolower($t['description'])); ?>">
            <div class="at-card-top">
                <div class="at-card-icon" style="background:<?php echo $catColor; ?>"><i class="fa-solid <?php echo htmlspecialchars($t['icon']); ?>"></i></div>
                <div class="at-card-meta">
                    <h3><?php echo htmlspecialchars($t['name']); ?></h3>
                    <div class="at-card-badges">
                        <span class="at-badge at-badge-role"><i class="fa-solid fa-user-tag"></i> <?php echo htmlspecialchars($t['agent_role']); ?></span>
                        <span class="at-badge at-badge-diff" style="background:<?php echo $diffColor; ?>20;color:<?php echo $diffColor; ?>"><?php echo htmlspecialchars($t['difficulty']); ?></span>
                    </div>
                </div>
            </div>
            <p class="at-card-desc"><?php echo htmlspecialchars($t['description']); ?></p>
            <div class="at-card-tools">
                <i class="fa-solid fa-wrench"></i>
                <?php echo count($t['tools']); ?> tools included &middot; <?php echo htmlspecialchars($t['estimated_setup']); ?> setup
            </div>
            <div class="at-card-actions">
                <button class="at-btn at-btn-deploy" onclick="deployTemplate('<?php echo htmlspecialchars($t['id']); ?>')">
                    <?php if ($is_logged_in): ?>
                        <i class="fa-solid fa-rocket"></i> Deploy
                    <?php else: ?>
                        <i class="fa-solid fa-arrow-right-to-bracket"></i> Sign Up to Deploy
                    <?php endif; ?>
                </button>
                <button class="at-btn at-btn-preview" onclick="previewTemplate('<?php echo htmlspecialchars($t['id']); ?>')">
                    <i class="fa-solid fa-eye"></i> Preview
                </button>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
</section>
<?php endforeach; ?>

<!-- CTA -->
<div class="at-cta">
    <h2>Can't find what you need?</h2>
    <p>Build a custom agent from scratch with Alfred's Fleet Command dashboard.</p>
    <a href="/fleet-dashboard.php" class="at-cta-btn"><i class="fa-solid fa-plus"></i> Build Custom Agent</a>
</div>

</div><!-- /.at-container -->

<!-- ===== PREVIEW MODAL ===== -->
<div class="at-modal-overlay" id="atModal">
    <div class="at-modal">
        <button class="at-modal-close" onclick="closeModal()"><i class="fa-solid fa-xmark"></i></button>
        <div class="at-modal-header">
            <div class="at-modal-icon" id="modalIcon"></div>
            <div class="at-modal-title">
                <h2 id="modalName"></h2>
                <div class="modal-badges" id="modalBadges"></div>
            </div>
        </div>
        <div class="at-modal-body">
            <section>
                <h4>Description</h4>
                <p id="modalDesc"></p>
            </section>
            <section>
                <h4>Default Task</h4>
                <p id="modalTask" style="font-style:italic;color:var(--at-accent-light)"></p>
            </section>
            <section>
                <h4>Tools &amp; Skills</h4>
                <div class="at-modal-tools-list" id="modalTools"></div>
            </section>
            <section>
                <h4>Agent Configuration</h4>
                <div class="at-modal-config" id="modalConfig"></div>
            </section>
            <section>
                <div class="at-modal-info-grid" id="modalInfo"></div>
            </section>
        </div>
        <div class="at-modal-footer">
            <button class="at-btn at-btn-deploy" id="modalDeployBtn" style="flex:2">
                <i class="fa-solid fa-rocket"></i> Deploy This Agent
            </button>
            <button class="at-btn at-btn-preview" onclick="closeModal()" style="flex:1">Close</button>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="at-toast" id="atToast"></div>

<script>
window._atTemplates = <?php echo json_encode($TEMPLATES, JSON_UNESCAPED_UNICODE); ?>;
window._atCategories = <?php echo json_encode($CATEGORIES, JSON_UNESCAPED_UNICODE); ?>;
window._atLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
window._atDiffColors = <?php echo json_encode($difficulty_colors); ?>;
</script>
<script src="/assets/js/agent-templates-engine.js"></script>

<?php require_once __DIR__ . '/includes/site-footer.inc.php'; ?>
