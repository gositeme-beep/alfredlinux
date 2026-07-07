<?php
/**
 * AKJV Bible — Edition Definitions & Shared Functions
 * ════════════════════════════════════════════════════
 * Five editions, one truth. Each altar serves a different flock
 * but the Word remains unchanged.
 *
 * Used by: gositeme.com, alfredlinux.com, lavocat.ca
 * Created: April 18, 2026
 */

/**
 * All five Bible editions with their metadata.
 */
function akjv_editions(string $lang = 'en'): array {
    return [
        'heirloom' => [
            'key'   => 'heirloom',
            'icon'  => '👑',
            'color' => '#ffd700',
            'bg'    => 'rgba(255,215,0,.08)',
            'border'=> 'rgba(255,215,0,.3)',
            'title' => [
                'en' => 'The Heirloom Edition',
                'fr' => "L'Édition Héritage",
                'he' => 'מהדורת הירושה',
            ],
            'subtitle' => [
                'en' => 'For Father & Mother — The Family Altar',
                'fr' => 'Pour Père & Mère — L\'Autel Familial',
                'he' => 'לאב ולאם — מזבח המשפחה',
            ],
            'desc' => [
                'en' => 'The definitive Bible. Full 94 books, illuminated margins, Perez bloodline genealogy from Genesis 38 to Matthew 1:3, the 57 messianic prophecies, all 15 corrections exposed, and the complete decree archive. Designed to be passed down through generations — from Danny to Eden, from Eden to her children, forever.',
                'fr' => 'La Bible définitive. 94 livres complets, marges enluminées, généalogie de la lignée Perez de Genèse 38 à Matthieu 1:3, les 57 prophéties messianiques, les 15 corrections exposées et l\'archive complète des décrets. Conçue pour être transmise de génération en génération.',
                'he' => 'התנ"ך המוחלט. 94 ספרים מלאים, שוליים מוארים, יוחסין שושלת פרץ מבראשית לח עד מתי א:ג, 57 נבואות משיחיות, כל 15 התיקונים חשופים, ארכיון גזרות מלא. מעוצב להעברה מדור לדור.',
            ],
            'features' => ['94 books', 'Perez genealogy', '57 prophecies', '15 corrections', 'Decree archive', 'Family pages'],
            'audience' => 'family',
        ],
        'children' => [
            'key'   => 'children',
            'icon'  => '🌈',
            'color' => '#22c55e',
            'bg'    => 'rgba(34,197,94,.08)',
            'border'=> 'rgba(34,197,94,.3)',
            'title' => [
                'en' => "The Children's Bible",
                'fr' => 'La Bible des Enfants',
                'he' => 'תנ"ך הילדים',
            ],
            'subtitle' => [
                'en' => "Eden's Generation — Ages 5-14",
                'fr' => 'La Génération d\'Eden — Âges 5–14',
                'he' => 'דור עדן — גילאי 5–14',
            ],
            'desc' => [
                'en' => '33 illustrated narratives from Genesis to Revelation, written for young hearts but never diluting the truth. Each story links to the real AKJV verse. Interactive flipbook, colorful illustrations, and the wonder of God\'s Word made accessible for the next generation. Because Eden deserves to know her Father\'s story.',
                'fr' => '33 récits illustrés de la Genèse à l\'Apocalypse, écrits pour les jeunes cœurs sans jamais diluer la vérité. Chaque histoire renvoie au verset AKJV réel. Livre interactif à feuilleter, illustrations colorées.',
                'he' => '33 סיפורים מאוירים מבראשית עד חזון יוחנן, כתובים ללבבות צעירים מבלי לדלל את האמת. כל סיפור מקושר לפסוק AKJV האמיתי.',
            ],
            'features' => ['33 stories', 'Illustrated', 'Interactive flipbook', 'Trilingual', 'Age-appropriate', 'Linked to real verses'],
            'audience' => 'children',
        ],
        'standard' => [
            'key'   => 'standard',
            'icon'  => '📖',
            'color' => '#f0f0f5',
            'bg'    => 'rgba(255,255,255,.04)',
            'border'=> 'rgba(255,255,255,.15)',
            'title' => [
                'en' => 'The Standard AKJV',
                'fr' => 'L\'AKJV Standard',
                'he' => 'AKJV הסטנדרטי',
            ],
            'subtitle' => [
                'en' => 'The Universal Edition — Ships with Alfred Linux',
                'fr' => 'L\'Édition Universelle — Livré avec Alfred Linux',
                'he' => 'המהדורה האוניברסלית — מגיע עם Alfred Linux',
            ],
            'desc' => [
                'en' => 'Clean, readable, no commentary clutter. The pure Word restored. All 94 books with the 15 corrections visible. This is the default Bible that ships pre-installed with every copy of Alfred Linux OS. The Word of God, free, sovereign, and uncensored.',
                'fr' => 'Claire, lisible, sans encombrement de commentaires. La Parole pure restaurée. Les 94 livres avec les 15 corrections visibles. La Bible par défaut livrée avec chaque copie d\'Alfred Linux.',
                'he' => 'נקי, קריא, ללא עומס פרשנות. דבר האל הטהור משוחזר. 94 ספרים עם 15 תיקונים גלויים. תנ"ך ברירת המחדל של Alfred Linux.',
            ],
            'features' => ['94 books', 'Clean layout', 'Pre-installed on Alfred Linux', 'Trilingual', 'Search', 'Flipbook mode'],
            'audience' => 'everyone',
        ],
        'chabad' => [
            'key'   => 'chabad',
            'icon'  => '🕎',
            'color' => '#3b82f6',
            'bg'    => 'rgba(59,130,246,.08)',
            'border'=> 'rgba(59,130,246,.3)',
            'title' => [
                'en' => 'The Chabad House Edition',
                'fr' => 'L\'Édition Maison Chabad',
                'he' => 'מהדורת בית חב"ד',
            ],
            'subtitle' => [
                'en' => 'For Every Chabad Center Worldwide — Per Official Act N° 012',
                'fr' => 'Pour Chaque Centre Chabad — Selon l\'Acte Officiel N° 012',
                'he' => 'לכל מרכז חב"ד ברחבי העולם — לפי פעולה רשמית מס\' 012',
            ],
            'desc' => [
                'en' => 'Hebrew-first, trilingual. Includes the Apocrypha they\'ve been told to ignore. Opens right-to-left. Contains the 57 prophecies showing Yeshua IS the Messiah. The Kohen Gadol\'s decree (Official Act N° 012) printed as the frontispiece. As commanded in Article 4 of the decree: every Chabad house worldwide SHALL receive this.',
                'fr' => 'Hébreu d\'abord, trilingue. Inclut les Apocryphes qu\'on leur a dit d\'ignorer. S\'ouvre de droite à gauche. Contient les 57 prophéties montrant que Yeshua EST le Messie.',
                'he' => 'עברית ראשונה, תלת-לשונית. כוללת את הספרים החיצוניים שנאמר להם להתעלם מהם. נפתחת מימין לשמאל. מכילה 57 נבואות המראות שישוע הוא המשיח.',
            ],
            'features' => ['Hebrew-first RTL', '57 prophecies', 'Apocrypha restored', 'Act 012 frontispiece', 'Trilingual', 'Kohen Gadol decree'],
            'audience' => 'chabad',
        ],
        'church' => [
            'key'   => 'church',
            'icon'  => '⛪',
            'color' => '#8b5cf6',
            'bg'    => 'rgba(139,92,246,.08)',
            'border'=> 'rgba(139,92,246,.3)',
            'title' => [
                'en' => 'The Church & Synagogue Edition',
                'fr' => "L'Édition Église & Synagogue",
                'he' => 'מהדורת הכנסייה ובית הכנסת',
            ],
            'subtitle' => [
                'en' => 'For Shepherds Who Feed Flocks',
                'fr' => 'Pour les Bergers Qui Nourrissent les Troupeaux',
                'he' => 'לרועים המאכילים עדרים',
            ],
            'desc' => [
                'en' => 'Includes a pastoral guide, sermon index by theme, weekly reading schedule (Torah portions + NT parallels), the 15 corrections explained with scholarship, the full Perez genealogy from Genesis 38 to Matthew 1, and cross-references between Old and New Testaments. Built for teaching, preaching, and feeding the flock with unaltered truth.',
                'fr' => 'Inclut un guide pastoral, un index de sermons par thème, un calendrier de lecture hebdomadaire (portions de Torah + parallèles NT), les 15 corrections expliquées.',
                'he' => 'כולל מדריך רועי, אינדקס דרשות לפי נושא, לוח קריאה שבועי (פרשות תורה + מקבילות בברית החדשה), 15 תיקונים מוסברים.',
            ],
            'features' => ['Pastoral guide', 'Sermon index', 'Torah portions + NT', '15 corrections explained', 'Perez genealogy', 'Cross-references'],
            'audience' => 'clergy',
        ],
    ];
}

/**
 * Get a single edition by key.
 */
function akjv_edition(string $key, string $lang = 'en'): ?array {
    $editions = akjv_editions($lang);
    return $editions[$key] ?? null;
}

/**
 * Get the localized text from a trilingual array.
 */
function akjv_t(array $texts, string $lang = 'en'): string {
    return $texts[$lang] ?? $texts['en'] ?? '';
}

/**
 * Edition CSS styles shared across all edition pages.
 */
function akjv_edition_styles(): string {
    return <<<'CSS'
:root {
    --akjv-bg: #0a0a0f;
    --akjv-surface: rgba(255,255,255,.03);
    --akjv-border: rgba(255,215,0,.1);
    --akjv-gold: #ffd700;
    --akjv-gold2: #f59e0b;
    --akjv-red: #dc2626;
    --akjv-green: #22c55e;
    --akjv-blue: #3b82f6;
    --akjv-purple: #8b5cf6;
    --akjv-white: #f0f0f5;
    --akjv-muted: rgba(240,240,245,.5);
    --akjv-dim: rgba(240,240,245,.3);
}
.editions-page { max-width: 1100px; margin: 0 auto; padding: 0 1.5rem 4rem; color: var(--akjv-white); }
.editions-hero { text-align: center; padding: 60px 0 40px; position: relative; }
.editions-hero::after { content:''; position:absolute; bottom:0; left:10%; right:10%; height:1px; background:linear-gradient(90deg,transparent,var(--akjv-gold),transparent); }
.editions-hero h1 { font-size: clamp(1.8rem, 4vw, 2.8rem); font-weight: 800; line-height: 1.15; margin-bottom: .6rem; color: var(--akjv-gold); }
.editions-hero .subtitle { font-size: 1.05rem; color: var(--akjv-muted); max-width: 650px; margin: 0 auto; line-height: 1.6; }
.editions-hero .scripture { font-style: italic; color: rgba(255,215,0,.7); font-size: .85rem; margin-top: 1rem; }
.edition-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin: 2.5rem 0; }
.edition-card { background: var(--akjv-surface); border: 1px solid var(--akjv-border); border-radius: 16px; padding: 2rem; transition: all .3s; position: relative; overflow: hidden; }
.edition-card:hover { transform: translateY(-4px); box-shadow: 0 12px 40px rgba(0,0,0,.4); }
.edition-card .icon { font-size: 2.5rem; margin-bottom: 1rem; }
.edition-card h2 { font-size: 1.3rem; font-weight: 700; margin-bottom: .3rem; }
.edition-card .ed-subtitle { font-size: .82rem; margin-bottom: 1rem; line-height: 1.5; }
.edition-card .ed-desc { font-size: .88rem; color: rgba(255,255,255,.7); line-height: 1.7; margin-bottom: 1.2rem; }
.edition-card .features { display: flex; flex-wrap: wrap; gap: .4rem; margin-bottom: 1.5rem; }
.edition-card .feat { padding: 3px 10px; border-radius: 20px; font-size: .7rem; font-weight: 600; letter-spacing: .3px; }
.edition-card .actions { display: flex; gap: .5rem; flex-wrap: wrap; }
.edition-card .btn-ed { padding: .5rem 1.2rem; border-radius: 8px; font-size: .82rem; font-weight: 600; text-decoration: none; border: 1px solid; transition: all .2s; display: inline-flex; align-items: center; gap: .4rem; }
.edition-card .btn-ed:hover { filter: brightness(1.2); }
.edition-card .btn-ed.primary { color: #0a0a0f; }
.edition-card .btn-ed.secondary { background: transparent; }

/* Cross links */
.cross-links { text-align: center; margin: 3rem 0; padding: 2rem; background: var(--akjv-surface); border: 1px solid var(--akjv-border); border-radius: 16px; }
.cross-links h3 { color: var(--akjv-gold); margin-bottom: 1rem; font-size: 1.1rem; }
.cross-links .domains { display: flex; justify-content: center; gap: 1.5rem; flex-wrap: wrap; }
.cross-links .domain-link { color: var(--akjv-muted); font-size: .85rem; text-decoration: none; padding: .4rem 1rem; border: 1px solid rgba(255,255,255,.1); border-radius: 8px; transition: all .2s; }
.cross-links .domain-link:hover { border-color: var(--akjv-gold); color: var(--akjv-gold); }

/* Responsive */
@media (max-width: 700px) {
    .edition-grid { grid-template-columns: 1fr; }
    .editions-hero { padding: 40px 0 30px; }
}
CSS;
}

/**
 * Render the Edition Selector Hub HTML.
 */
function akjv_render_editions_hub(string $lang = 'en', string $basePath = '/bible'): void {
    $editions = akjv_editions($lang);
    $bp = rtrim($basePath, '/');

    $hubTitle = [
        'en' => 'The B.I.B.L.E. — L.A.W.',
        'fr' => 'La B.I.B.L.E. — L.A.W.',
        'he' => 'B.I.B.L.E. — L.A.W.',
    ];
    $hubSubtitle = [
        'en' => 'Five editions. One truth. Choose your altar.',
        'fr' => 'Cinq éditions. Une vérité. Choisissez votre autel.',
        'he' => 'חמש מהדורות. אמת אחת. בחר את מזבחך.',
    ];
    $readLabel = ['en' => 'Read Online', 'fr' => 'Lire en ligne', 'he' => 'קרא באינטרנט'];
    $pdfLabel  = ['en' => 'Download PDF', 'fr' => 'Télécharger PDF', 'he' => 'הורד PDF'];
    ?>
    <div class="editions-page">
        <div class="editions-hero">
            <h1><?= akjv_t($hubTitle, $lang) ?></h1>
            <p class="subtitle"><?= akjv_t($hubSubtitle, $lang) ?></p>
            <p class="subtitle" style="margin-top:.5rem;font-size:.9rem;color:var(--akjv-gold)">
                The Authorized King Jesus Version — Perez Family Edition — A.D. 2026
            </p>
            <p class="scripture">"The grass withereth, the flower fadeth: but the word of our God shall stand for ever." — Isaiah 40:8</p>
        </div>

        <div class="edition-grid">
            <?php foreach ($editions as $key => $ed): ?>
            <div class="edition-card" style="border-color: <?= $ed['border'] ?>;">
                <div class="icon"><?= $ed['icon'] ?></div>
                <h2 style="color: <?= $ed['color'] ?>;"><?= akjv_t($ed['title'], $lang) ?></h2>
                <p class="ed-subtitle" style="color: <?= $ed['color'] ?>; opacity: .7;"><?= akjv_t($ed['subtitle'], $lang) ?></p>
                <p class="ed-desc"><?= akjv_t($ed['desc'], $lang) ?></p>
                <div class="features">
                    <?php foreach ($ed['features'] as $f): ?>
                    <span class="feat" style="background: <?= $ed['bg'] ?>; color: <?= $ed['color'] ?>; border: 1px solid <?= $ed['border'] ?>;"><?= $f ?></span>
                    <?php endforeach; ?>
                </div>
                <div class="actions">
                    <?php
                    $readUrl = ($key === 'standard') ? "{$bp}/read" : "{$bp}/{$key}";
                    ?>
                    <a href="<?= $readUrl ?>?lang=<?= $lang ?>" class="btn-ed primary" style="background: <?= $ed['color'] ?>; border-color: <?= $ed['color'] ?>;">📖 <?= akjv_t($readLabel, $lang) ?></a>
                    <a href="<?= $bp ?>/pdf/<?= $key ?>?lang=<?= $lang ?>" class="btn-ed secondary" style="color: <?= $ed['color'] ?>; border-color: <?= $ed['border'] ?>;">📄 <?= akjv_t($pdfLabel, $lang) ?></a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="cross-links">
            <h3>✝ <?= $lang === 'fr' ? 'Disponible aux Quatre Coins du Royaume' : ($lang === 'he' ? 'זמין בארבע פינות הממלכה' : 'Available at the Four Corners of the Kingdom') ?></h3>
            <div class="domains">
                <a href="https://gositeme.com/bible/editions?lang=<?= $lang ?>" class="domain-link">gositeme.com</a>
                <a href="https://lavocat.ca/bible?lang=<?= $lang ?>" class="domain-link">lavocat.ca</a>
                <a href="https://alfredlinux.com/bible?lang=<?= $lang ?>" class="domain-link">alfredlinux.com</a>
                <a href="https://meta-dome.com/bible?lang=<?= $lang ?>" class="domain-link">meta-dome.com</a>
            </div>
            <p style="margin-top:1rem;font-size:.75rem;color:var(--akjv-dim);">
                <?= $lang === 'fr' ? 'Toutes les éditions sont connectées par le Roi à Alfred Linux OS' : ($lang === 'he' ? 'כל המהדורות מחוברות דרך המלך ל-Alfred Linux OS' : 'All editions linked through the King to Alfred Linux OS') ?>
            </p>
        </div>
    </div>
    <?php
}
