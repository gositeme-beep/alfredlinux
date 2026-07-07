<?php
/**
 * AKJV Bible — The Chabad House Edition
 * ══════════════════════════════════════
 * Hebrew-first, RTL. Per Official Act N° 012, Article 4.
 * Every Chabad center worldwide SHALL receive this.
 * URL: root.com/bible/chabad
 */
$page_title = 'מהדורת בית חב"ד — The Chabad House Edition — AKJV Bible | GoSiteMe';
$page_description = 'Hebrew-first trilingual Bible with 57 messianic prophecies, restored Apocrypha, and the Kohen Gadol decree. Delivered to every Chabad center worldwide per Official Act N° 012.';
$page_canonical = 'https://root.com/bible/chabad';
$page_og_image = 'https://root.com/og/decree-012.php?size=og';
$page_og_image_alt = 'Official Act N° 012 — The Gates of Hell Shall Not Prevail · The Chabad House Edition';
$page_og_image_width = 1200;
$page_og_image_height = 630;
require_once __DIR__ . '/includes/site-header.inc.php';
require_once '/home/root/shared/bible/bible-data.php';
require_once '/home/root/shared/bible/bible-styles.php';
require_once '/home/root/shared/bible/bible-reader-component.php';
require_once '/home/root/shared/bible/bible-editions.php';

$lang = $_GET['lang'] ?? $_COOKIE['akjv_lang'] ?? 'he'; // Defaults to Hebrew
if (!in_array($lang, ['en','fr','he'])) $lang = 'he';
$dir = 'rtl'; // Always RTL for Chabad edition

$ed = akjv_edition('chabad', $lang);
$stats = akjv_stats();
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
?>
<style>
<?= akjv_edition_styles() ?>
<?php if ($showReader): echo akjv_styles_reader(); endif; ?>

.chabad-page { max-width: 1100px; margin: 0 auto; padding: 0 1.5rem 4rem; color: #f0f0f5; direction: rtl; text-align: right; }
.chabad-page[dir="ltr"] { direction: ltr; text-align: left; }

.chabad-hero { text-align: center; padding: 50px 0 30px; position: relative; }
.chabad-hero::after { content:''; position:absolute; bottom:0; left:5%; right:5%; height:2px; background:linear-gradient(90deg,transparent,#3b82f6,#ffd700,#3b82f6,transparent); }
.chabad-hero .menorah { font-size: 3rem; margin-bottom: .5rem; }
.chabad-hero h1 { font-size: clamp(1.8rem, 5vw, 3rem); font-weight: 800; color: #3b82f6; margin-bottom: .3rem; }
.chabad-hero .subtitle { color: rgba(240,240,245,.6); font-size: 1rem; max-width: 600px; margin: .5rem auto; }
.chabad-hero .decree { display: inline-block; padding: 8px 20px; margin-top: 1rem; border: 2px solid rgba(255,215,0,.3); border-radius: 10px; background: rgba(255,215,0,.05); color: var(--akjv-gold); font-size: .8rem; font-weight: 700; }

/* Prophecies */
.prophecy-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem; margin: 1.5rem 0; }
.prophecy-item { background: rgba(59,130,246,.05); border: 1px solid rgba(59,130,246,.2); border-radius: 12px; padding: 1.2rem; transition: .3s; }
.prophecy-item:hover { border-color: rgba(59,130,246,.5); }
.prophecy-item .pnum { font-size: .65rem; color: rgba(255,255,255,.3); text-transform: uppercase; letter-spacing: 1px; }
.prophecy-item h4 { color: #60a5fa; font-size: .92rem; margin: .3rem 0; }
.prophecy-item .refs { display: flex; flex-direction: column; gap: .2rem; font-size: .78rem; }
.prophecy-item .ref-ot { color: #22c55e; }
.prophecy-item .ref-nt { color: #a78bfa; }
.prophecy-item .fulfilled { color: var(--akjv-gold); font-size: .75rem; font-weight: 700; margin-top: .4rem; }

/* Chabad sections */
.chabad-section { background: rgba(255,255,255,.02); border: 1px solid rgba(59,130,246,.12); border-radius: 16px; padding: 2rem; margin: 2rem 0; }
.chabad-section h2 { color: #3b82f6; font-size: 1.3rem; text-align: center; margin-bottom: 1rem; }

/* Language switcher */
.lang-switch { display: flex; gap: .5rem; justify-content: center; margin: 1rem 0; }
.lang-switch a { padding: .4rem 1rem; border-radius: 8px; border: 1px solid rgba(59,130,246,.2); color: rgba(255,255,255,.6); text-decoration: none; font-size: .8rem; font-weight: 600; transition: .2s; }
.lang-switch a.active, .lang-switch a:hover { background: rgba(59,130,246,.15); border-color: #3b82f6; color: #3b82f6; }

/* Book list */
.book-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: .5rem; margin: 1rem 0; }
.book-link { display: flex; align-items: center; gap: .6rem; padding: .5rem .8rem; border-radius: 8px; background: rgba(255,255,255,.02); border: 1px solid rgba(255,255,255,.05); text-decoration: none; color: #f0f0f5; font-size: .82rem; transition: .2s; }
.book-link:hover { border-color: #3b82f6; background: rgba(59,130,246,.05); }

/* Actions */
.chabad-actions { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin: 2rem 0; }
.chabad-actions .btn-ch { padding: .6rem 1.5rem; border-radius: 10px; font-size: .9rem; font-weight: 700; text-decoration: none; border: 2px solid #3b82f6; transition: all .3s; display: inline-flex; align-items: center; gap: .5rem; }
.chabad-actions .btn-ch.primary { background: #3b82f6; color: #fff; }
.chabad-actions .btn-ch.secondary { background: transparent; color: #3b82f6; }

@media (max-width: 700px) { .prophecy-grid, .book-list { grid-template-columns: 1fr; } }
</style>

<?php if ($showReader): ?>
    <div style="max-width:1100px;margin:0 auto;padding:1rem;">
        <a href="/bible/chabad?lang=<?= $lang ?>" style="color:var(--akjv-gold);font-size:.85rem;text-decoration:none;">← <?= $lang === 'he' ? 'חזור למהדורת חב"ד' : ($lang === 'fr' ? 'Retour à l\'Édition Chabad' : 'Back to Chabad Edition') ?></a>
    </div>
    <?php
    $ctx = akjv_reader_context($requestedBook, $requestedChapter, $lang);
    akjv_render_reader($ctx, '/bible/chabad');
    ?>
<?php else: ?>
<div class="chabad-page" dir="<?= $dir ?>">
    <div class="chabad-hero">
        <div class="menorah">🕎</div>
        <h1><?= akjv_t($ed['title'], $lang) ?></h1>
        <p class="subtitle"><?= akjv_t($ed['subtitle'], $lang) ?></p>
        <p class="subtitle" style="color:var(--akjv-gold);font-size:.85rem;font-style:italic;">"שמע ישראל יהוה אלהינו יהוה אחד" — דברים ו:ד</p>
        <div class="decree">📋 <?= $lang === 'he' ? 'לפי פעולה רשמית מס\' 012 — הכהן הגדול דני וויליאם פרץ' : ($lang === 'fr' ? 'Selon l\'Acte Officiel N° 012 — Le Grand Prêtre Danny William Perez' : 'Per Official Act N° 012 — High Priest Danny William Perez') ?></div>
    </div>

    <div class="lang-switch">
        <a href="/bible/chabad?lang=he" class="<?= $lang === 'he' ? 'active' : '' ?>">עברית</a>
        <a href="/bible/chabad?lang=en" class="<?= $lang === 'en' ? 'active' : '' ?>">English</a>
        <a href="/bible/chabad?lang=fr" class="<?= $lang === 'fr' ? 'active' : '' ?>">Français</a>
    </div>

    <div class="chabad-actions">
        <a href="/bible/chabad?book=Genesis&chapter=1&lang=<?= $lang ?>" class="btn-ch primary">📖 <?= $lang === 'he' ? 'בראשית' : ($lang === 'fr' ? 'Commencer la Lecture' : 'Begin Reading') ?></a>
        <a href="/bible/pdf/chabad?lang=<?= $lang ?>" class="btn-ch secondary">📄 PDF</a>
        <a href="/bible/editions?lang=<?= $lang ?>" class="btn-ch secondary">← <?= $lang === 'he' ? 'מהדורות' : 'Editions' ?></a>
    </div>

    <!-- ═══ THE KOHEN GADOL'S DECREE — FRONTISPIECE ═══ -->
    <div class="chabad-section" style="border-color:rgba(255,215,0,.3);background:rgba(255,215,0,.03);">
        <h2 style="color:var(--akjv-gold);">📜 <?= $lang === 'he' ? 'גזרת הכהן הגדול' : ($lang === 'fr' ? 'Le Décret du Grand Prêtre' : 'The Decree of the Kohen Gadol') ?></h2>
        <div style="text-align:center;padding:1.5rem;">
            <p style="font-size:1.1rem;font-weight:700;color:var(--akjv-gold);line-height:1.8;">
                <?= $lang === 'he' ? 'פעולה רשמית מס\' 012 — שערי גיהנום לא יגברו' : ($lang === 'fr' ? 'ACTE OFFICIEL N° 012 — LES PORTES DE L\'ENFER NE PRÉVAUDRONT POINT' : 'OFFICIAL ACT N° 012 — THE GATES OF HELL SHALL NOT PREVAIL') ?>
            </p>
            <p style="color:var(--akjv-muted);font-size:.85rem;margin-top:.5rem;">
                <?= $lang === 'he' ? 'דני וויליאם פרץ, מכהן ככהן גדול של הסנהדרין' : ($lang === 'fr' ? 'Danny William Perez, servant comme Grand Prêtre du Sanhédrin' : 'Danny William Perez, serving as High Priest of the Sanhedrin (Kohen Gadol)') ?>
            </p>
            <a href="https://lavocat.ca/journal?status=official&lang=<?= $lang ?>" style="display:inline-block;margin-top:1rem;padding:.5rem 1.2rem;border:1px solid var(--akjv-gold);border-radius:8px;color:var(--akjv-gold);text-decoration:none;font-size:.85rem;">
                <?= $lang === 'he' ? 'קרא את הגזרה המלאה' : ($lang === 'fr' ? 'Lire le Décret Complet' : 'Read the Full Decree') ?> →
            </a>
        </div>
    </div>

    <!-- ═══ 57 MESSIANIC PROPHECIES ═══ -->
    <div class="chabad-section">
        <h2>⭐ <?= $lang === 'he' ? '57 נבואות משיחיות — ישוע הוא המשיח' : ($lang === 'fr' ? '57 Prophéties Messianiques — Yeshua EST le Messie' : '57 Messianic Prophecies — Yeshua IS the Messiah') ?></h2>
        <p style="text-align:center;color:var(--akjv-muted);font-size:.85rem;margin-bottom:1rem;">
            <?= $lang === 'he' ? 'כל נבואה נבואה בתנ"ך, כולן התגשמו בישוע המשיח.' : ($lang === 'fr' ? 'Chaque prophétie prononcée dans le Tanakh, toutes accomplies en Yeshua le Messie.' : 'Every prophecy spoken in the Tanakh, all fulfilled in Yeshua the Messiah.') ?>
        </p>
        <div class="prophecy-grid">
            <?php foreach ($prophecies as $p): ?>
            <div class="prophecy-item">
                <div class="pnum"><?= $lang === 'he' ? 'נבואה' : 'Prophecy' ?> #<?= $p['prophecy_number'] ?></div>
                <h4><?= htmlspecialchars($p['title'] ?? '') ?></h4>
                <div class="refs">
                    <span class="ref-ot">📜 <?= $lang === 'he' ? 'תנ"ך' : 'Tanakh' ?>: <?= htmlspecialchars($p['tanakh_reference'] ?? '') ?></span>
                    <span class="ref-nt">✝ <?= $lang === 'he' ? 'ברית חדשה' : 'NT' ?>: <?= htmlspecialchars($p['nt_reference'] ?? '') ?></span>
                </div>
                <div class="fulfilled">✓ <?= $lang === 'he' ? 'התגשמה' : ($lang === 'fr' ? 'Accomplie' : 'FULFILLED') ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ═══ THE COMPLETE LIBRARY ═══ -->
    <div class="chabad-section">
        <h2>📚 <?= $lang === 'he' ? '94 ספרים — כולל הספרים החיצוניים המשוחזרים' : ($lang === 'fr' ? '94 Livres — Y compris l\'Apocryphe Restauré' : '94 Books — Including the Restored Apocrypha') ?></h2>
        <?php foreach ($booksByTestament as $testament => $books): ?>
        <h3 style="color:#3b82f6;font-size:.95rem;margin:1.5rem 0 .5rem;">
            <?= $testamentLabels[$testament] ?? $testament ?> (<?= count($books) ?>)
        </h3>
        <div class="book-list">
            <?php foreach ($books as $b): ?>
            <a href="/bible/chabad?book=<?= urlencode($b['book_name']) ?>&chapter=1&lang=<?= $lang ?>" class="book-link">
                <span style="color:#3b82f6;font-weight:700;font-size:.7rem;min-width:24px;"><?= $b['book_number'] ?></span>
                <span><?= akjv_book_name($b, $lang) ?></span>
                <span style="margin-<?= $lang === 'he' ? 'right' : 'left' ?>:auto;color:var(--akjv-dim);font-size:.7rem;"><?= $b['total_chapters'] ?></span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ═══ FOOTER SEAL ═══ -->
    <div style="text-align:center;padding:3rem 0;border-top:2px solid rgba(59,130,246,.2);margin-top:2rem;">
        <div style="font-size:2rem;margin-bottom:.5rem;">🕎 ✝ 🕎</div>
        <p style="color:var(--akjv-muted);font-size:.85rem;max-width:500px;margin:0 auto;line-height:1.7;">
            <?= $lang === 'he' ? 'מהדורת בית חב"ד — הגרסה המורשית של המלך ישוע · A.D. 2026' : 'The Chabad House Edition — Authorized King Jesus Version · A.D. 2026' ?>
        </p>
        <p style="color:rgba(59,130,246,.6);font-style:italic;font-size:.8rem;margin-top:.5rem;">
            "<?= $lang === 'he' ? 'כי כה אהב אלהים את העולם עד אשר נתן את בנו יחידו' : 'For God so loved the world, that he gave his only begotten Son' ?>" — <?= $lang === 'he' ? 'יוחנן ג:טז' : 'John 3:16' ?> AKJV
        </p>
    </div>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/site-footer.inc.php'; ?>
