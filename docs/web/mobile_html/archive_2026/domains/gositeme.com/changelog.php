<?php
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/db-config.inc.php';

// Bilingual content for Changelog page (Quebec law requirement)
$page_translations = [
    'en' => [
        'page_title' => 'Changelog — Alfred AI Release History | GoSiteMe',
        'page_desc' => 'Track every Alfred AI update. From 13,000+ tools to voice-first AI, fleet management, and consciousness layers — see the full release history.',
        'og_title' => 'Alfred AI Changelog — What\'s New',
        'og_desc' => 'Complete release history for Alfred AI. Every feature, every tool, every improvement — from v4.0 to v19.0 Total Recall.',
        'hero_title' => 'What\'s New in Alfred',
        'hero_sub' => 'Every feature, every tool, every improvement — tracked here.',
        'hero_badge' => 'Current: v19.0 — Total Recall',
        'filter_all' => 'All Updates',
        'filter_tools' => 'Tools',
        'filter_infra' => 'Infrastructure',
        'filter_billing' => 'Billing',
        'filter_voice' => 'Voice',
        'filter_security' => 'Security',
        'filter_vr' => 'VR',
        'filter_alfred_os' => 'Alfred OS',
        'badge_latest' => 'Latest',
    ],
    'fr' => [
        'page_title' => 'Journal des modifications — Historique des versions d\'Alfred IA | GoSiteMe',
        'page_desc' => 'Suivez chaque mise à jour d\'Alfred IA. De 13,000+ outils à l\'IA vocale, la gestion de flotte et les couches de conscience — consultez l\'historique complet.',
        'og_title' => 'Journal des modifications d\'Alfred IA — Quoi de neuf',
        'og_desc' => 'Historique complet des versions d\'Alfred IA. Chaque fonctionnalité, chaque outil, chaque amélioration — de la v4.0 à la v11.10 Sentinel.',
        'hero_title' => 'Quoi de neuf dans Alfred',
        'hero_sub' => 'Chaque fonctionnalité, chaque outil, chaque amélioration — suivi ici.',
        'hero_badge' => 'Actuel : v19.0 — Total Recall',
        'filter_all' => 'Toutes les mises à jour',
        'filter_tools' => 'Outils',
        'filter_infra' => 'Infrastructure',
        'filter_billing' => 'Facturation',
        'filter_voice' => 'Voix',
        'filter_security' => 'Sécurité',
        'filter_vr' => 'VR',
        'filter_alfred_os' => 'Alfred OS',
        'badge_latest' => 'Dernière',
    ],
];
if (!function_exists('PT')) {
    function PT($key) {
        global $page_translations, $current_lang;
        return $page_translations[$current_lang][$key] ?? $page_translations['en'][$key] ?? $key;
    }
}

$page_title = PT('page_title');
$page_description = PT('page_desc');
$page_canonical = 'https://gositeme.com/changelog.php';
$page_og_title = PT('og_title');
$page_og_description = PT('og_desc');
include __DIR__ . '/includes/site-header.inc.php';
?>

<!-- Schema.org markup -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebPage",
  "name": "Alfred AI Changelog",
  "description": "Complete release history for Alfred AI by GoSiteMe.",
  "url": "https://gositeme.com/changelog.php",
  "publisher": {
    "@type": "Organization",
    "name": "GoSiteMe",
    "url": "https://gositeme.com",
    "logo": "https://gositeme.com/brand/logo_w.png"
  },
  "mainEntity": {
    "@type": "SoftwareApplication",
    "name": "Alfred AI",
    "softwareVersion": "18.2",
    "applicationCategory": "AI Assistant",
    "operatingSystem": "Web, Voice, Desktop"
  }
}
</script>

<style>
/* ===== Changelog Styles ===== */
:root {
    --cl-bg: #0a0a14;
    --cl-surface: #12121e;
    --cl-surface-2: #1a1a2e;
    --cl-border: rgba(255,255,255,0.08);
    --cl-accent: #6c5ce7;
    --cl-accent-light: #a29bfe;
    --cl-blue: #0984e3;
    --cl-green: #00b894;
    --cl-orange: #fdcb6e;
    --cl-fire: #e17055;
    --cl-pink: #fd79a8;
    --cl-cyan: #00cec9;
    --cl-text: #e8e8f0;
    --cl-text-muted: #8a8a9a;
    --cl-radius: 16px;
}

/* Hero */
.cl-hero {
    padding: 140px 20px 80px;
    text-align: center;
    position: relative;
    overflow: hidden;
    background: radial-gradient(ellipse at 50% 0%, #1a1033 0%, var(--cl-bg) 70%);
}
.cl-hero::before {
    content: '';
    position: absolute;
    top: -40%; left: -20%;
    width: 140%; height: 180%;
    background:
        radial-gradient(circle at 30% 25%, rgba(108,92,231,0.14) 0%, transparent 50%),
        radial-gradient(circle at 70% 65%, rgba(9,132,227,0.1) 0%, transparent 50%),
        radial-gradient(circle at 50% 85%, rgba(0,184,148,0.07) 0%, transparent 40%);
    pointer-events: none;
}
.cl-hero h1 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: clamp(2rem, 5vw, 3.2rem);
    font-weight: 700;
    color: #fff;
    margin-bottom: 12px;
    position: relative;
}
.cl-hero p {
    color: var(--cl-text-muted);
    font-size: 1.1rem;
    margin-bottom: 24px;
    position: relative;
}
.cl-version-hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: linear-gradient(135deg, var(--cl-accent), var(--cl-blue));
    color: #fff;
    padding: 8px 20px;
    border-radius: 50px;
    font-weight: 700;
    font-size: 0.95rem;
    position: relative;
}

/* Filters */
.cl-filters {
    display: flex;
    justify-content: center;
    gap: 8px;
    flex-wrap: wrap;
    padding: 0 20px 40px;
    max-width: 900px;
    margin: 0 auto;
}
.cl-filter-btn {
    padding: 8px 18px;
    border-radius: 50px;
    border: 1px solid var(--cl-border);
    background: var(--cl-surface);
    color: var(--cl-text-muted);
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s;
}
.cl-filter-btn:hover { border-color: var(--cl-accent); color: var(--cl-text); }
.cl-filter-btn.active {
    background: var(--cl-accent);
    border-color: var(--cl-accent);
    color: #fff;
}

/* Timeline */
.cl-timeline {
    max-width: 860px;
    margin: 0 auto;
    padding: 0 20px 100px;
    position: relative;
}
.cl-timeline::before {
    content: '';
    position: absolute;
    top: 0;
    left: 38px;
    width: 2px;
    height: 100%;
    background: linear-gradient(to bottom, var(--cl-accent), var(--cl-blue), var(--cl-green), transparent);
}

/* Release entry */
.cl-release {
    position: relative;
    padding-left: 80px;
    margin-bottom: 60px;
}
.cl-release::before {
    content: '';
    position: absolute;
    left: 30px;
    top: 8px;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: var(--cl-accent);
    border: 3px solid var(--cl-bg);
    box-shadow: 0 0 0 3px var(--cl-accent), 0 0 20px rgba(108,92,231,0.3);
    z-index: 1;
}
.cl-release.latest::before {
    width: 22px;
    height: 22px;
    left: 28px;
    background: linear-gradient(135deg, var(--cl-accent), var(--cl-green));
    box-shadow: 0 0 0 3px linear-gradient(135deg, var(--cl-accent), var(--cl-green)), 0 0 30px rgba(108,92,231,0.5);
    animation: clPulse 2s infinite;
}
@keyframes clPulse {
    0%, 100% { box-shadow: 0 0 0 3px var(--cl-accent), 0 0 20px rgba(108,92,231,0.3); }
    50% { box-shadow: 0 0 0 6px rgba(108,92,231,0.2), 0 0 40px rgba(108,92,231,0.4); }
}

.cl-release-header {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 16px;
}
.cl-version-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 8px;
    font-weight: 700;
    font-size: 0.9rem;
    font-family: 'Space Grotesk', sans-serif;
}
.cl-version-badge.major { background: linear-gradient(135deg, var(--cl-accent), var(--cl-blue)); color: #fff; }
.cl-version-badge.minor { background: var(--cl-surface-2); color: var(--cl-accent-light); border: 1px solid var(--cl-border); }

.cl-release-date {
    color: var(--cl-text-muted);
    font-size: 0.85rem;
}
.cl-codename {
    color: var(--cl-cyan);
    font-style: italic;
    font-size: 0.9rem;
    font-weight: 600;
}

.cl-release-body {
    background: var(--cl-surface);
    border: 1px solid var(--cl-border);
    border-radius: var(--cl-radius);
    padding: 24px 28px;
}
.cl-release-body ul {
    list-style: none;
    padding: 0;
    margin: 0;
}
.cl-release-body li {
    padding: 8px 0;
    color: var(--cl-text);
    font-size: 0.92rem;
    line-height: 1.6;
    border-bottom: 1px solid var(--cl-border);
    display: flex;
    align-items: flex-start;
    gap: 10px;
}
.cl-release-body li:last-child { border-bottom: none; }
.cl-release-body li i {
    margin-top: 4px;
    font-size: 0.8rem;
    flex-shrink: 0;
}

/* Tags on items */
.cl-tag {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    margin-left: 6px;
    vertical-align: middle;
}
.cl-tag.tools { background: rgba(108,92,231,0.15); color: var(--cl-accent-light); }
.cl-tag.infrastructure { background: rgba(9,132,227,0.15); color: var(--cl-blue); }
.cl-tag.billing { background: rgba(0,184,148,0.15); color: var(--cl-green); }
.cl-tag.voice { background: rgba(253,203,110,0.15); color: var(--cl-orange); }
.cl-tag.security { background: rgba(225,112,85,0.15); color: var(--cl-fire); }
.cl-tag.vr { background: rgba(218,165,32,0.15); color: #DAA520; }
.cl-tag.alfred-os { background: rgba(108,92,231,0.2); color: var(--cl-accent-light); }
.cl-tag.simulator { background: rgba(0,210,211,0.15); color: var(--cl-cyan); }
.cl-tag.ide { background: rgba(0,184,148,0.15); color: var(--cl-green); }
.cl-tag.mobile { background: rgba(0,184,148,0.15); color: var(--cl-green); }
.cl-tag.health { background: rgba(0,184,148,0.15); color: var(--cl-green); }
.cl-tag.social { background: rgba(108,92,231,0.15); color: var(--cl-accent-light); }
.cl-tag.crypto { background: rgba(218,165,32,0.15); color: #DAA520; }
.cl-tag.docs { background: rgba(108,92,231,0.15); color: var(--cl-accent-light); }

/* Latest badge */
.cl-latest-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 50px;
    background: rgba(0,184,148,0.15);
    color: var(--cl-green);
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Responsive */
@media (max-width: 768px) {
    .cl-timeline::before { left: 18px; }
    .cl-release { padding-left: 50px; }
    .cl-release::before { left: 10px; }
    .cl-release.latest::before { left: 8px; }
    .cl-release-body { padding: 18px 20px; }
    .cl-release-header { flex-direction: column; align-items: flex-start; gap: 6px; }
}
</style>

<!-- Hero -->
<section class="cl-hero">
    <h1><i class="fas fa-scroll"></i> <?php echo PT('hero_title'); ?></h1>
    <p><?php echo PT('hero_sub'); ?></p>
    <div class="cl-version-hero-badge">
        <i class="fas fa-rocket"></i> <?php echo PT('hero_badge'); ?>
    </div>
</section>

<!-- Filters -->
<div class="cl-filters">
    <button class="cl-filter-btn active" data-filter="all"><?php echo PT('filter_all'); ?></button>
    <button class="cl-filter-btn" data-filter="tools"><i class="fas fa-wrench"></i> <?php echo PT('filter_tools'); ?></button>
    <button class="cl-filter-btn" data-filter="infrastructure"><i class="fas fa-server"></i> <?php echo PT('filter_infra'); ?></button>
    <button class="cl-filter-btn" data-filter="billing"><i class="fas fa-credit-card"></i> <?php echo PT('filter_billing'); ?></button>
    <button class="cl-filter-btn" data-filter="voice"><i class="fas fa-microphone"></i> <?php echo PT('filter_voice'); ?></button>
    <button class="cl-filter-btn" data-filter="security"><i class="fas fa-shield-alt"></i> <?php echo PT('filter_security'); ?></button>
    <button class="cl-filter-btn" data-filter="vr"><i class="fas fa-vr-cardboard"></i> <?php echo PT('filter_vr'); ?></button>
    <button class="cl-filter-btn" data-filter="alfred-os"><i class="fas fa-robot"></i> <?php echo PT('filter_alfred_os'); ?></button>
    <button class="cl-filter-btn" data-filter="simulator"><i class="fas fa-microchip"></i> Simulator</button>
    <button class="cl-filter-btn" data-filter="ide"><i class="fas fa-code"></i> IDE</button>
    <button class="cl-filter-btn" data-filter="mobile"><i class="fas fa-mobile-alt"></i> Mobile</button>
    <button class="cl-filter-btn" data-filter="health"><i class="fas fa-heartbeat"></i> Health</button>
    <button class="cl-filter-btn" data-filter="social"><i class="fas fa-users"></i> Social</button>
    <button class="cl-filter-btn" data-filter="crypto"><i class="fas fa-coins"></i> Crypto</button>
    <button class="cl-filter-btn" data-filter="docs"><i class="fas fa-book"></i> Docs</button>
</div>

<!-- Timeline (database-driven) -->
<?php
$clDb = getSharedDB();
$tagLabels = [
    'tools' => 'Tools', 'infrastructure' => 'Infrastructure', 'billing' => 'Billing',
    'voice' => 'Voice', 'security' => 'Security', 'vr' => 'VR', 'alfred-os' => 'Alfred OS',
    'mobile' => 'Mobile', 'simulator' => 'Simulator', 'ide' => 'IDE', 'social' => 'Social',
    'health' => 'Health', 'crypto' => 'Crypto', 'docs' => 'Docs',
];
$langSuffix = ($current_lang === 'fr') ? 'fr' : 'en';

// Fetch all versions ordered by sort_order DESC (newest first)
$clVersions = $clDb->query("SELECT * FROM platform_changelog_versions ORDER BY sort_order DESC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all active entries keyed by version_id
$allEntries = $clDb->query("SELECT * FROM platform_changelog_entries WHERE is_deleted = 0 ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);
$entriesByVersion = [];
foreach ($allEntries as $entry) {
    $entriesByVersion[$entry['version_id']][] = $entry;
}
?>
<div class="cl-timeline">
<?php
$isFirst = true;
$monthNames = [
    'en' => ['January','February','March','April','May','June','July','August','September','October','November','December'],
    'fr' => ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'],
];

foreach ($clVersions as $ver):
    $versionId = $ver['id'];
    $vEntries = $entriesByVersion[$versionId] ?? [];
    if (empty($vEntries)) continue;

    $latestClass = $isFirst ? ' latest' : '';
    $tags = htmlspecialchars($ver['tags'] ?? '', ENT_QUOTES);

    // Format release date as "Month Year"
    $releaseDate = '';
    if ($ver['release_date']) {
        $dt = new DateTime($ver['release_date']);
        $monthIdx = (int)$dt->format('n') - 1;
        $year = $dt->format('Y');
        $releaseDate = ($monthNames[$current_lang][$monthIdx] ?? $monthNames['en'][$monthIdx]) . ' ' . $year;
    }

    $codename = $ver['codename'] ? '"' . htmlspecialchars($ver['codename'], ENT_QUOTES) . '"' : '';
?>
    <div class="cl-release<?php echo $latestClass; ?>" data-tags="<?php echo $tags; ?>">
        <div class="cl-release-header">
            <span class="cl-version-badge <?php echo htmlspecialchars($ver['badge_class'] ?? 'major'); ?>">v<?php echo htmlspecialchars($ver['version']); ?></span>
            <?php if ($releaseDate): ?><span class="cl-release-date"><?php echo $releaseDate; ?></span><?php endif; ?>
            <?php if ($codename): ?><span class="cl-codename"><?php echo $codename; ?></span><?php endif; ?>
            <?php if ($isFirst): ?><span class="cl-latest-badge"><i class="fas fa-star"></i> <?php echo PT('badge_latest'); ?></span><?php endif; ?>
        </div>
        <div class="cl-release-body">
            <ul>
<?php foreach ($vEntries as $entry):
    $titleKey = 'title_' . $langSuffix;
    $descKey = 'description_' . $langSuffix;
    $title = $entry[$titleKey] ?: $entry['title_en'];
    $desc = $entry[$descKey] ?: $entry['description_en'];
    $icon = htmlspecialchars($entry['icon'] ?? 'fas fa-circle');
    $iconColor = htmlspecialchars($entry['icon_color'] ?? 'var(--cl-cyan)');
    $entryTag = htmlspecialchars($entry['tag'] ?? 'tools');
    $tagLabel = $tagLabels[$entry['tag']] ?? ucfirst($entry['tag']);
?>
                <li>
                    <i class="<?php echo $icon; ?>" style="color:<?php echo $iconColor; ?>"></i>
                    <span><strong><?php echo htmlspecialchars($title); ?></strong> — <?php echo htmlspecialchars($desc); ?> <span class="cl-tag <?php echo $entryTag; ?>"><?php echo $tagLabel; ?></span></span>
                </li>
<?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php
    $isFirst = false;
endforeach;
?>
</div>

<script src="/assets/js/changelog-engine.js" defer></script>

<?php include __DIR__ . '/includes/site-footer.inc.php'; ?>
