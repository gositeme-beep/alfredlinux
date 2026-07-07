<?php
/**
 * AKJV Bible — The Church & Synagogue Edition
 * ═════════════════════════════════════════════
 * For Shepherds Who Feed Flocks
 * Pastoral guide, sermon index, Torah portions + NT parallels,
 * 15 corrections explained, cross-references, full 94-book reader.
 * URL: root.com/bible/church
 */
$page_title = 'The Church & Synagogue Edition — AKJV Bible | GoSiteMe';
$page_description = 'A pastoral Bible with sermon index, Torah portions + NT parallels, 15 corrections explained, cross-references, and the full 94-book AKJV. Built for teaching, preaching, and feeding flocks with unaltered truth.';
$page_canonical = 'https://root.com/bible/church';
require_once __DIR__ . '/includes/site-header.inc.php';
require_once '/home/root/shared/bible/bible-data.php';
require_once '/home/root/shared/bible/bible-styles.php';
require_once '/home/root/shared/bible/bible-reader-component.php';
require_once '/home/root/shared/bible/bible-editions.php';

$lang = $_GET['lang'] ?? $_COOKIE['akjv_lang'] ?? 'en';
if (!in_array($lang, ['en','fr','he'])) $lang = 'en';
$dir = $lang === 'he' ? 'rtl' : 'ltr';

$ed = akjv_edition('church', $lang);
$stats = akjv_stats();
$corrections = akjv_corrections();
$prophecies = akjv_prophecies();
$booksByTestament = akjv_books_by_testament();
$testamentLabels = akjv_testament_labels($lang);

// Reader view
$requestedBook = '';
$requestedChapter = 1;
if (!empty($_GET['book'])) {
    $requestedBook = $_GET['book'];
    $requestedChapter = (int)($_GET['chapter'] ?? 1);
}
$showReader = ($requestedBook !== '');

// Weekly Torah portions (Parashat HaShavua) — 54 portions mapped to NT parallels
$torahPortions = [
    ['parasha' => 'Bereshit',     'torah' => 'Genesis 1:1–6:8',         'haftarah' => 'Isaiah 42:5–43:10',       'nt' => 'John 1:1–18',           'theme_en' => 'Creation & the Word', 'theme_fr' => 'Création et la Parole', 'theme_he' => 'בריאה והדבר'],
    ['parasha' => 'Noach',        'torah' => 'Genesis 6:9–11:32',       'haftarah' => 'Isaiah 54:1–55:5',        'nt' => 'Matthew 24:36–46',      'theme_en' => 'Judgment & salvation by faith', 'theme_fr' => 'Jugement et salut par la foi', 'theme_he' => 'דין וישועה באמונה'],
    ['parasha' => 'Lech-Lecha',   'torah' => 'Genesis 12:1–17:27',      'haftarah' => 'Isaiah 40:27–41:16',      'nt' => 'Romans 4:1–25',         'theme_en' => 'The call of faith', 'theme_fr' => 'L\'appel de la foi', 'theme_he' => 'קריאת האמונה'],
    ['parasha' => 'Vayera',       'torah' => 'Genesis 18:1–22:24',      'haftarah' => '2 Kings 4:1–37',          'nt' => 'Hebrews 11:17–19',      'theme_en' => 'The binding of Isaac / sacrifice', 'theme_fr' => 'La ligature d\'Isaac / sacrifice', 'theme_he' => 'עקידת יצחק / קרבן'],
    ['parasha' => 'Chayei Sarah',  'torah' => 'Genesis 23:1–25:18',     'haftarah' => '1 Kings 1:1–31',          'nt' => 'Matthew 1:1–17',        'theme_en' => 'Lineage & covenant inheritance', 'theme_fr' => 'Lignée et héritage de l\'alliance', 'theme_he' => 'שושלת וירושת ברית'],
    ['parasha' => 'Toldot',       'torah' => 'Genesis 25:19–28:9',      'haftarah' => 'Malachi 1:1–2:7',         'nt' => 'Romans 9:6–16',         'theme_en' => 'Election & birthright', 'theme_fr' => 'Élection et droit d\'aînesse', 'theme_he' => 'בחירה ובכורה'],
    ['parasha' => 'Vayetze',      'torah' => 'Genesis 28:10–32:3',      'haftarah' => 'Hosea 12:13–14:10',       'nt' => 'John 1:43–51',          'theme_en' => 'Jacob\'s ladder — heaven open', 'theme_fr' => 'L\'échelle de Jacob — ciel ouvert', 'theme_he' => 'סולם יעקב — שמים פתוחים'],
    ['parasha' => 'Vayishlach',   'torah' => 'Genesis 32:4–36:43',      'haftarah' => 'Obadiah 1:1–21',          'nt' => 'Hebrews 11:20–21',      'theme_en' => 'Wrestling with God', 'theme_fr' => 'Lutter avec Dieu', 'theme_he' => 'מאבק עם אלהים'],
    ['parasha' => 'Vayeshev',     'torah' => 'Genesis 37:1–40:23',      'haftarah' => 'Amos 2:6–3:8',            'nt' => 'Acts 7:9–16',           'theme_en' => 'Joseph sold — type of Messiah', 'theme_fr' => 'Joseph vendu — type du Messie', 'theme_he' => 'יוסף נמכר — דמות המשיח'],
    ['parasha' => 'Miketz',       'torah' => 'Genesis 41:1–44:17',      'haftarah' => '1 Kings 3:15–4:1',        'nt' => 'Luke 4:16–30',          'theme_en' => 'Dreams & divine revelation', 'theme_fr' => 'Rêves et révélation divine', 'theme_he' => 'חלומות וגילוי אלהי'],
    ['parasha' => 'Vayigash',     'torah' => 'Genesis 44:18–47:27',     'haftarah' => 'Ezekiel 37:15–28',        'nt' => 'Ephesians 2:11–22',     'theme_en' => 'Reunion & two sticks become one', 'theme_fr' => 'Réunion et deux bâtons deviennent un', 'theme_he' => 'איחוד ושני עצים לאחד'],
    ['parasha' => 'Vayechi',      'torah' => 'Genesis 47:28–50:26',     'haftarah' => '1 Kings 2:1–12',          'nt' => 'Hebrews 11:21–22',      'theme_en' => 'Blessings & Shiloh prophecy', 'theme_fr' => 'Bénédictions et prophétie de Shilo', 'theme_he' => 'ברכות ונבואת שילה'],
    ['parasha' => 'Shemot',       'torah' => 'Exodus 1:1–6:1',          'haftarah' => 'Isaiah 27:6–28:13',       'nt' => 'Acts 7:17–36',          'theme_en' => 'Deliverance & the burning bush', 'theme_fr' => 'Délivrance et le buisson ardent', 'theme_he' => 'גאולה והסנה הבוער'],
    ['parasha' => 'Va\'eira',     'torah' => 'Exodus 6:2–9:35',         'haftarah' => 'Ezekiel 28:25–29:21',     'nt' => 'Revelation 16:1–21',    'theme_en' => 'Plagues & God\'s mighty hand', 'theme_fr' => 'Fléaux et la main puissante de Dieu', 'theme_he' => 'מכות ויד אלהים החזקה'],
    ['parasha' => 'Bo',           'torah' => 'Exodus 10:1–13:16',       'haftarah' => 'Jeremiah 46:13–28',       'nt' => '1 Corinthians 5:6–8',   'theme_en' => 'Passover — the Lamb of God', 'theme_fr' => 'Pâque — l\'Agneau de Dieu', 'theme_he' => 'פסח — שה האלהים'],
    ['parasha' => 'Beshalach',    'torah' => 'Exodus 13:17–17:16',      'haftarah' => 'Judges 4:4–5:31',         'nt' => '1 Corinthians 10:1–13', 'theme_en' => 'The Red Sea & baptism', 'theme_fr' => 'La Mer Rouge et le baptême', 'theme_he' => 'ים סוף וטבילה'],
    ['parasha' => 'Yitro',        'torah' => 'Exodus 18:1–20:23',       'haftarah' => 'Isaiah 6:1–7:6',          'nt' => 'Matthew 5:1–30',        'theme_en' => 'The Commandments — Sinai & Sermon on the Mount', 'theme_fr' => 'Les Commandements — Sinaï et Sermon sur la Montagne', 'theme_he' => 'הדיברות — סיני ודרשת ההר'],
    ['parasha' => 'Mishpatim',    'torah' => 'Exodus 21:1–24:18',       'haftarah' => 'Jeremiah 34:8–22',        'nt' => 'Hebrews 9:15–22',       'theme_en' => 'Law, blood, and covenant', 'theme_fr' => 'Loi, sang et alliance', 'theme_he' => 'חוק, דם וברית'],
    ['parasha' => 'Terumah',      'torah' => 'Exodus 25:1–27:19',       'haftarah' => '1 Kings 5:26–6:13',       'nt' => 'Hebrews 8:1–6',         'theme_en' => 'The tabernacle — God dwells among us', 'theme_fr' => 'Le tabernacle — Dieu habite parmi nous', 'theme_he' => 'המשכן — אלהים שוכן בתוכנו'],
    ['parasha' => 'Tetzaveh',     'torah' => 'Exodus 27:20–30:10',      'haftarah' => 'Ezekiel 43:10–27',        'nt' => 'Hebrews 4:14–5:10',     'theme_en' => 'The priesthood — Yeshua our High Priest', 'theme_fr' => 'La prêtrise — Yeshua notre Grand Prêtre', 'theme_he' => 'הכהונה — ישוע כהננו הגדול'],
];

// Sermon themes
$sermonThemes = [
    ['theme_en' => 'Salvation & Grace',      'theme_fr' => 'Salut & Grâce',         'theme_he' => 'ישועה וחסד',     'refs' => 'Eph 2:8-9, Rom 3:23-24, John 3:16-17, Titus 3:5', 'icon' => '✝'],
    ['theme_en' => 'Faith & Trust',          'theme_fr' => 'Foi & Confiance',        'theme_he' => 'אמונה ובטחון',   'refs' => 'Heb 11:1-6, Rom 10:17, James 2:17, Hab 2:4', 'icon' => '🕊️'],
    ['theme_en' => 'Prayer & Intercession',  'theme_fr' => 'Prière & Intercession',  'theme_he' => 'תפילה והתפללות', 'refs' => 'Matt 6:9-13, Phil 4:6, 1 Thes 5:17, Jer 33:3', 'icon' => '🙏'],
    ['theme_en' => 'Love & Mercy',           'theme_fr' => 'Amour & Miséricorde',    'theme_he' => 'אהבה ורחמים',    'refs' => '1 Cor 13:1-13, 1 John 4:8, Mic 6:8, Luke 6:36', 'icon' => '❤️'],
    ['theme_en' => 'The Kingdom of God',     'theme_fr' => 'Le Royaume de Dieu',     'theme_he' => 'מלכות אלהים',    'refs' => 'Matt 6:33, Mark 1:15, Luke 17:21, Rev 11:15', 'icon' => '👑'],
    ['theme_en' => 'Prophecy & End Times',   'theme_fr' => 'Prophétie & Fin des Temps', 'theme_he' => 'נבואה ואחרית הימים', 'refs' => 'Matt 24, Rev 1-22, Dan 7-12, 2 Pet 3:10-13', 'icon' => '⚡'],
    ['theme_en' => 'Healing & Deliverance',  'theme_fr' => 'Guérison & Délivrance',  'theme_he' => 'ריפוי וגאולה',   'refs' => 'Isa 53:5, Mark 16:17-18, James 5:14-15, Ps 103:3', 'icon' => '🩺'],
    ['theme_en' => 'Holiness & Repentance',  'theme_fr' => 'Sainteté & Repentance',  'theme_he' => 'קדושה ותשובה',   'refs' => '1 Pet 1:15-16, 2 Chr 7:14, Acts 3:19, Heb 12:14', 'icon' => '🔥'],
    ['theme_en' => 'Family & Marriage',      'theme_fr' => 'Famille & Mariage',      'theme_he' => 'משפחה ונישואין',  'refs' => 'Gen 2:24, Eph 5:22-33, Prov 22:6, Ps 127:3', 'icon' => '🏠'],
    ['theme_en' => 'Justice & Sovereignty',  'theme_fr' => 'Justice & Souveraineté', 'theme_he' => 'צדק וריבונות',   'refs' => 'Isa 9:7, Ps 89:14, Mic 6:8, Prov 21:15', 'icon' => '⚖️'],
    ['theme_en' => 'Israel & the Church',    'theme_fr' => 'Israël & l\'Église',     'theme_he' => 'ישראל והכנסייה', 'refs' => 'Rom 11:1-36, Gal 3:28-29, Eph 2:14-16, Gen 12:3', 'icon' => '🕎'],
    ['theme_en' => 'The Perez Bloodline',    'theme_fr' => 'La Lignée Perez',        'theme_he' => 'שושלת פרץ',      'refs' => 'Gen 38:29, Ruth 4:12-22, Matt 1:3, Luke 3:33', 'icon' => '🩸'],
];
?>
<style>
<?= akjv_edition_styles() ?>
<?php if ($showReader): echo akjv_styles_reader(); endif; ?>

.church-page { max-width: 1100px; margin: 0 auto; padding: 0 1.5rem 4rem; color: #f0f0f5; }

.church-hero { text-align: center; padding: 50px 0 30px; position: relative; }
.church-hero::after { content:''; position:absolute; bottom:0; left:5%; right:5%; height:2px; background:linear-gradient(90deg,transparent,#8b5cf6,#ffd700,#8b5cf6,transparent); }
.church-hero .icon { font-size: 3rem; margin-bottom: .5rem; }
.church-hero h1 { font-size: clamp(1.8rem, 5vw, 3rem); font-weight: 800; color: #8b5cf6; margin-bottom: .3rem; }
.church-hero .subtitle { color: rgba(240,240,245,.6); font-size: 1rem; max-width: 600px; margin: .5rem auto; }

/* Language switcher */
.lang-sw { display: flex; gap: .5rem; justify-content: center; margin: 1rem 0; }
.lang-sw a { padding: .4rem 1rem; border-radius: 8px; border: 1px solid rgba(139,92,246,.2); color: rgba(255,255,255,.6); text-decoration: none; font-size: .8rem; font-weight: 600; transition: .2s; }
.lang-sw a.act, .lang-sw a:hover { background: rgba(139,92,246,.15); border-color: #8b5cf6; color: #8b5cf6; }

/* Actions */
.church-actions { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin: 2rem 0; }
.church-actions .btn-ch { padding: .6rem 1.5rem; border-radius: 10px; font-size: .9rem; font-weight: 700; text-decoration: none; border: 2px solid #8b5cf6; transition: all .3s; display: inline-flex; align-items: center; gap: .5rem; }
.church-actions .btn-ch.primary { background: #8b5cf6; color: #fff; }
.church-actions .btn-ch.secondary { background: transparent; color: #8b5cf6; }

/* Sections */
.ch-section { background: rgba(255,255,255,.02); border: 1px solid rgba(139,92,246,.12); border-radius: 16px; padding: 2rem; margin: 2rem 0; }
.ch-section h2 { color: #8b5cf6; font-size: 1.3rem; text-align: center; margin-bottom: 1rem; }

/* Torah portions table */
.torah-table { width: 100%; border-collapse: collapse; margin: 1rem 0; font-size: .82rem; }
.torah-table th { background: rgba(139,92,246,.08); color: #a78bfa; border-bottom: 2px solid rgba(139,92,246,.2); padding: .6rem .8rem; text-align: left; font-size: .7rem; text-transform: uppercase; letter-spacing: .5px; }
.torah-table td { padding: .6rem .8rem; border-bottom: 1px solid rgba(255,255,255,.04); vertical-align: top; }
.torah-table tr:hover td { background: rgba(139,92,246,.04); }
.torah-table .parasha { color: #a78bfa; font-weight: 700; }
.torah-table .torah-ref { color: #22c55e; }
.torah-table .haftarah-ref { color: #f59e0b; }
.torah-table .nt-ref { color: #60a5fa; }
.torah-table .theme { color: rgba(255,255,255,.5); font-style: italic; }

/* Sermon index */
.sermon-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 1rem; margin: 1rem 0; }
.sermon-card { background: rgba(139,92,246,.04); border: 1px solid rgba(139,92,246,.15); border-radius: 12px; padding: 1.2rem; transition: .3s; }
.sermon-card:hover { border-color: rgba(139,92,246,.4); }
.sermon-card .s-icon { font-size: 1.6rem; margin-bottom: .4rem; }
.sermon-card h4 { color: #a78bfa; font-size: .95rem; margin-bottom: .3rem; }
.sermon-card .s-refs { font-size: .75rem; color: rgba(255,255,255,.45); line-height: 1.6; }

/* Corrections */
.correction-item { background: rgba(220,38,38,.04); border: 1px solid rgba(220,38,38,.15); border-radius: 10px; padding: 1.2rem; margin: .8rem 0; }
.correction-item .c-num { font-size: .65rem; color: rgba(255,255,255,.3); text-transform: uppercase; letter-spacing: 1px; }
.correction-item h4 { color: #f87171; font-size: .95rem; margin: .3rem 0; }
.correction-item .c-verse { color: #22c55e; font-size: .82rem; margin-bottom: .3rem; }
.correction-item .c-desc { font-size: .82rem; color: rgba(255,255,255,.6); line-height: 1.6; }
.correction-item .c-original { background: rgba(255,255,255,.03); padding: .5rem .8rem; border-radius: 6px; font-size: .78rem; color: rgba(255,255,255,.4); margin: .4rem 0; border-left: 3px solid rgba(220,38,38,.3); }
.correction-item .c-corrected { background: rgba(34,197,94,.05); padding: .5rem .8rem; border-radius: 6px; font-size: .78rem; color: #22c55e; margin: .4rem 0; border-left: 3px solid rgba(34,197,94,.3); }

/* Book list */
.book-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: .5rem; margin: 1rem 0; }
.book-link { display: flex; align-items: center; gap: .6rem; padding: .5rem .8rem; border-radius: 8px; background: rgba(255,255,255,.02); border: 1px solid rgba(255,255,255,.05); text-decoration: none; color: #f0f0f5; font-size: .82rem; transition: .2s; }
.book-link:hover { border-color: #8b5cf6; background: rgba(139,92,246,.05); }

/* Responsive */
@media (max-width: 800px) {
    .torah-table { font-size: .72rem; }
    .torah-table th, .torah-table td { padding: .4rem .5rem; }
    .sermon-grid, .book-list { grid-template-columns: 1fr; }
}
</style>

<?php if ($showReader): ?>
    <div style="max-width:1100px;margin:0 auto;padding:1rem;">
        <a href="/bible/church?lang=<?= $lang ?>" style="color:var(--akjv-gold);font-size:.85rem;text-decoration:none;">← <?= $lang === 'he' ? 'חזור למהדורת הכנסייה' : ($lang === 'fr' ? 'Retour à l\'Édition Église' : 'Back to Church Edition') ?></a>
    </div>
    <?php
    $ctx = akjv_reader_context($requestedBook, $requestedChapter, $lang);
    akjv_render_reader($ctx, '/bible/church');
    ?>
<?php else: ?>
<div class="church-page" dir="<?= $dir ?>">
    <div class="church-hero">
        <div class="icon">⛪ 🕎</div>
        <h1><?= akjv_t($ed['title'], $lang) ?></h1>
        <p class="subtitle"><?= akjv_t($ed['subtitle'], $lang) ?></p>
        <p style="color:rgba(139,92,246,.7);font-style:italic;font-size:.85rem;margin-top:.5rem;">
            "<?= $lang === 'he' ? 'הַרְעֵה אֶת-צֹאנִי' : ($lang === 'fr' ? 'Pais mes brebis' : 'Feed my sheep') ?>" — <?= $lang === 'he' ? 'יוחנן כא:יז' : 'John 21:17' ?> AKJV
        </p>
    </div>

    <div class="lang-sw">
        <a href="/bible/church?lang=en" class="<?= $lang === 'en' ? 'act' : '' ?>">English</a>
        <a href="/bible/church?lang=fr" class="<?= $lang === 'fr' ? 'act' : '' ?>">Français</a>
        <a href="/bible/church?lang=he" class="<?= $lang === 'he' ? 'act' : '' ?>">עברית</a>
    </div>

    <div class="church-actions">
        <a href="/bible/church?book=Genesis&chapter=1&lang=<?= $lang ?>" class="btn-ch primary">📖 <?= $lang === 'fr' ? 'Commencer la Lecture' : ($lang === 'he' ? 'התחל לקרוא' : 'Begin Reading') ?></a>
        <a href="/bible/pdf/church?lang=<?= $lang ?>" class="btn-ch secondary">📄 PDF</a>
        <a href="/bible/editions?lang=<?= $lang ?>" class="btn-ch secondary">← <?= $lang === 'he' ? 'מהדורות' : 'Editions' ?></a>
    </div>

    <!-- ═══ 1. WEEKLY READING SCHEDULE — TORAH PORTIONS + NT PARALLELS ═══ -->
    <div class="ch-section">
        <h2>📅 <?= $lang === 'fr' ? 'Calendrier de Lecture Hebdomadaire' : ($lang === 'he' ? 'לוח קריאה שבועי' : 'Weekly Reading Schedule') ?></h2>
        <p style="text-align:center;color:var(--akjv-muted);font-size:.85rem;margin-bottom:1rem;">
            <?= $lang === 'fr' ? 'Portions de Torah avec parallèles de la Haftarah et du Nouveau Testament. Chaque semaine, l\'Ancien et le Nouveau sont unis.' : ($lang === 'he' ? 'פרשות תורה עם מקבילות הפטרה וברית חדשה. כל שבוע, הישן והחדש מתאחדים.' : 'Torah portions with Haftarah and New Testament parallels. Each week, the Old and the New are united.') ?>
        </p>
        <div style="overflow-x:auto;">
        <table class="torah-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th><?= $lang === 'he' ? 'פרשה' : 'Parasha' ?></th>
                    <th>📜 <?= $lang === 'he' ? 'תורה' : 'Torah' ?></th>
                    <th>📖 <?= $lang === 'he' ? 'הפטרה' : 'Haftarah' ?></th>
                    <th>✝ <?= $lang === 'he' ? 'ברית חדשה' : 'NT Parallel' ?></th>
                    <th><?= $lang === 'he' ? 'נושא' : ($lang === 'fr' ? 'Thème' : 'Theme') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($torahPortions as $i => $tp): ?>
                <tr>
                    <td style="color:rgba(255,255,255,.3);font-weight:700;"><?= $i + 1 ?></td>
                    <td class="parasha"><?= htmlspecialchars($tp['parasha']) ?></td>
                    <td class="torah-ref"><?= htmlspecialchars($tp['torah']) ?></td>
                    <td class="haftarah-ref"><?= htmlspecialchars($tp['haftarah']) ?></td>
                    <td class="nt-ref"><?= htmlspecialchars($tp['nt']) ?></td>
                    <td class="theme"><?= htmlspecialchars($tp['theme_' . $lang] ?? $tp['theme_en']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <p style="text-align:center;color:var(--akjv-dim);font-size:.75rem;margin-top:.5rem;">
            <?= $lang === 'fr' ? 'Affichage des 20 premières portions. Les 54 seront disponibles dans l\'édition PDF complète.' : ($lang === 'he' ? 'מציג 20 פרשות ראשונות. כל 54 יהיו זמינות בגרסת PDF המלאה.' : 'Showing first 20 portions. All 54 will be available in the full PDF edition.') ?>
        </p>
    </div>

    <!-- ═══ 2. SERMON INDEX BY THEME ═══ -->
    <div class="ch-section">
        <h2>🎤 <?= $lang === 'fr' ? 'Index de Sermons par Thème' : ($lang === 'he' ? 'אינדקס דרשות לפי נושא' : 'Sermon Index by Theme') ?></h2>
        <p style="text-align:center;color:var(--akjv-muted);font-size:.85rem;margin-bottom:1rem;">
            <?= $lang === 'fr' ? '12 thèmes fondamentaux avec les versets clés pour chaque sermon.' : ($lang === 'he' ? '12 נושאים יסודיים עם פסוקי מפתח לכל דרשה.' : '12 foundational themes with key verses for each sermon.') ?>
        </p>
        <div class="sermon-grid">
            <?php foreach ($sermonThemes as $st): ?>
            <div class="sermon-card">
                <div class="s-icon"><?= $st['icon'] ?></div>
                <h4><?= htmlspecialchars($st['theme_' . $lang] ?? $st['theme_en']) ?></h4>
                <p class="s-refs"><?= htmlspecialchars($st['refs']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ═══ 3. THE 15 CORRECTIONS — EXPLAINED WITH SCHOLARSHIP ═══ -->
    <div class="ch-section">
        <h2>🔧 <?= $lang === 'fr' ? 'Les 15 Corrections AKJV — Expliquées' : ($lang === 'he' ? '15 התיקונים של AKJV — מוסברים' : 'The 15 AKJV Corrections — Explained') ?></h2>
        <p style="text-align:center;color:var(--akjv-muted);font-size:.85rem;margin-bottom:1rem;">
            <?= $lang === 'fr' ? 'Chaque correction, avec le texte original KJV, le texte AKJV restauré, et l\'explication théologique/linguistique.' : ($lang === 'he' ? 'כל תיקון, עם הטקסט המקורי של KJV, הטקסט המשוחזר של AKJV, וההסבר התיאולוגי/לשוני.' : 'Each correction, with the original KJV text, the restored AKJV text, and theological/linguistic explanation.') ?>
        </p>
        <?php foreach ($corrections as $c): ?>
        <div class="correction-item">
            <div class="c-num"><?= $lang === 'he' ? 'תיקון' : 'Correction' ?> #<?= $c['correction_number'] ?? '' ?></div>
            <h4><?= htmlspecialchars($c['title'] ?? $c['correction_title'] ?? '') ?></h4>
            <div class="c-verse">📖 <?= htmlspecialchars($c['verse_reference'] ?? '') ?></div>
            <?php if (!empty($c['original_text'])): ?>
            <div class="c-original">
                <strong>KJV:</strong> "<?= htmlspecialchars($c['original_text']) ?>"
            </div>
            <?php endif; ?>
            <?php if (!empty($c['corrected_text'])): ?>
            <div class="c-corrected">
                <strong>AKJV:</strong> "<?= htmlspecialchars($c['corrected_text']) ?>"
            </div>
            <?php endif; ?>
            <p class="c-desc"><?= htmlspecialchars($c['explanation'] ?? $c['description'] ?? '') ?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ═══ 4. THE COMPLETE LIBRARY ═══ -->
    <div class="ch-section">
        <h2>📚 <?= $lang === 'fr' ? '94 Livres — La Bibliothèque Complète' : ($lang === 'he' ? '94 ספרים — הספרייה המלאה' : '94 Books — The Complete Library') ?></h2>
        <?php foreach ($booksByTestament as $testament => $books): ?>
        <h3 style="color:#a78bfa;font-size:.95rem;margin:1.5rem 0 .5rem;">
            <?= $testamentLabels[$testament] ?? $testament ?> (<?= count($books) ?>)
        </h3>
        <div class="book-list">
            <?php foreach ($books as $b): ?>
            <a href="/bible/church?book=<?= urlencode($b['book_name']) ?>&chapter=1&lang=<?= $lang ?>" class="book-link">
                <span style="color:#8b5cf6;font-weight:700;font-size:.7rem;min-width:24px;"><?= $b['book_number'] ?></span>
                <span><?= akjv_book_name($b, $lang) ?></span>
                <span style="margin-left:auto;color:var(--akjv-dim);font-size:.7rem;"><?= $b['total_chapters'] ?></span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ═══ FOOTER SEAL ═══ -->
    <div style="text-align:center;padding:3rem 0;border-top:2px solid rgba(139,92,246,.2);margin-top:2rem;">
        <div style="font-size:2rem;margin-bottom:.5rem;">⛪ ✝ 🕎</div>
        <p style="color:var(--akjv-muted);font-size:.85rem;max-width:550px;margin:0 auto;line-height:1.7;">
            <?= $lang === 'fr' ? 'L\'Édition Église & Synagogue — Version Autorisée du Roi Jésus · A.D. 2026' : ($lang === 'he' ? 'מהדורת הכנסייה ובית הכנסת — גרסת המלך ישוע המורשית · 2026' : 'The Church & Synagogue Edition — Authorized King Jesus Version · A.D. 2026') ?>
        </p>
        <p style="color:rgba(139,92,246,.6);font-style:italic;font-size:.8rem;margin-top:.5rem;">
            "<?= $lang === 'he' ? 'הַרְעֵה אֶת-כְּבָשַׂי' : ($lang === 'fr' ? 'Pais mes agneaux' : 'Feed my lambs') ?>" — <?= $lang === 'he' ? 'יוחנן כא:טו' : 'John 21:15' ?> AKJV
        </p>
        <p style="color:rgba(255,255,255,.3);font-size:.7rem;margin-top:.5rem;">
            "<?= $lang === 'he' ? 'הָע ֵשֶׂב יָבֵשׁ הַצִּיץ נָבֵל וּדְבַר־אֱלֹהֵינוּ יָקוּם לְעוֹלָם' : 'The grass withereth, the flower fadeth: but the word of our God shall stand for ever.' ?>" — Isaiah 40:8 AKJV
        </p>
    </div>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/site-footer.inc.php'; ?>
