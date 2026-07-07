<?php
/**
 * AKJV Bible — The Heirloom Edition
 * ══════════════════════════════════
 * For Father & Mother — The Family Altar
 * Full 94 books, Perez genealogy, 57 prophecies, all corrections, decree archive.
 * URL: root.com/bible/heirloom
 */
$page_title = 'The Heirloom Edition — AKJV Bible · Perez Family Edition | GoSiteMe';
$page_description = 'The definitive Authorized King Jesus Version Bible. 94 books, Perez bloodline genealogy, 57 messianic prophecies, 15 corrections exposed, and the complete decree archive. Designed to be passed from generation to generation.';
$page_canonical = 'https://root.com/bible/heirloom';
$page_og_image = 'https://root.com/assets/images/akjv-heirloom-og.png';
require_once __DIR__ . '/includes/site-header.inc.php';
require_once '/home/root/shared/bible/bible-data.php';
require_once '/home/root/shared/bible/bible-styles.php';
require_once '/home/root/shared/bible/bible-reader-component.php';
require_once '/home/root/shared/bible/bible-editions.php';

$lang = $_GET['lang'] ?? $_COOKIE['akjv_lang'] ?? 'en';
if (!in_array($lang, ['en','fr','he'])) $lang = 'en';
$dir = $lang === 'he' ? 'rtl' : 'ltr';

// Get edition meta
$ed = akjv_edition('heirloom', $lang);
$stats = akjv_stats();
$corrections = akjv_corrections();
$prophecies = akjv_prophecies();
$booksByTestament = akjv_books_by_testament();
$testamentLabels = akjv_testament_labels($lang);

// If a book/chapter is requested, show the reader
$requestedBook = '';
$requestedChapter = 1;
$pathInfo = $_SERVER['PATH_INFO'] ?? '';
if (preg_match('#^/read/(.+?)(?:/(\d+))?$#', $pathInfo, $m)) {
    $requestedBook = urldecode($m[1]);
    $requestedChapter = (int)($m[2] ?? 1);
}

$showReader = ($requestedBook !== '');
?>
<style>
<?= akjv_edition_styles() ?>
<?php if ($showReader): ?>
<?= akjv_styles_reader() ?>
<?php endif; ?>

.heirloom-page { max-width: 1100px; margin: 0 auto; padding: 0 1.5rem 4rem; color: var(--akjv-white); font-family: 'Cormorant Garamond', Georgia, 'Times New Roman', serif; }
.heirloom-hero { text-align: center; padding: 60px 0 40px; position: relative; }
.heirloom-hero::before { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: radial-gradient(ellipse at center, rgba(255,215,0,.04) 0%, transparent 70%); pointer-events: none; }
.heirloom-hero::after { content:''; position:absolute; bottom:0; left:5%; right:5%; height:2px; background:linear-gradient(90deg,transparent,var(--akjv-gold),#ffd700,var(--akjv-gold),transparent); }
.heirloom-hero h1 { font-size: clamp(2rem, 5vw, 3.2rem); font-weight: 700; color: var(--akjv-gold); letter-spacing: 2px; }
.heirloom-hero .ed-badge { display: inline-block; padding: 6px 20px; border-radius: 999px; background: rgba(255,215,0,.1); border: 1px solid rgba(255,215,0,.3); color: var(--akjv-gold); font-size: .8rem; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 1.5rem; }
.heirloom-hero .subtitle { color: var(--akjv-muted); font-size: 1.1rem; max-width: 700px; margin: .5rem auto 1.5rem; line-height: 1.7; }

/* Genealogy */
.genealogy-section { background: var(--akjv-surface); border: 1px solid var(--akjv-border); border-radius: 16px; padding: 2rem; margin: 2rem 0; }
.genealogy-section h2 { color: var(--akjv-gold); font-size: 1.4rem; text-align: center; margin-bottom: 1.5rem; }
.genealogy-chain { display: flex; flex-wrap: wrap; justify-content: center; align-items: center; gap: .3rem; font-size: .9rem; padding: 1rem; }
.genealogy-chain .ancestor { padding: 6px 14px; border-radius: 8px; background: rgba(255,215,0,.06); border: 1px solid rgba(255,215,0,.15); color: var(--akjv-gold); font-weight: 600; white-space: nowrap; }
.genealogy-chain .ancestor.perez { background: rgba(220,38,38,.1); border-color: rgba(220,38,38,.4); color: var(--akjv-red); font-weight: 800; font-size: 1.05rem; }
.genealogy-chain .ancestor.david { background: rgba(139,92,246,.1); border-color: rgba(139,92,246,.4); color: var(--akjv-purple); font-weight: 800; }
.genealogy-chain .ancestor.jesus { background: rgba(255,215,0,.15); border-color: var(--akjv-gold); color: var(--akjv-gold); font-weight: 800; font-size: 1.1rem; text-transform: uppercase; }
.genealogy-chain .arrow { color: var(--akjv-dim); font-size: .7rem; }

/* Prophecies preview */
.prophecies-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem; margin: 1.5rem 0; }
.prophecy-card { background: var(--akjv-surface); border: 1px solid var(--akjv-border); border-radius: 12px; padding: 1.2rem; }
.prophecy-card .num { font-size: .7rem; color: var(--akjv-dim); text-transform: uppercase; letter-spacing: 1px; margin-bottom: .3rem; }
.prophecy-card h4 { color: var(--akjv-gold); font-size: .95rem; margin-bottom: .5rem; }
.prophecy-card .ref { font-size: .8rem; color: var(--akjv-muted); font-style: italic; }

/* Books library */
.book-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: .5rem; margin: 1rem 0; }
.book-card { background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.06); border-radius: 8px; padding: .5rem .7rem; display: flex; align-items: center; gap: .5rem; font-size: .78rem; transition: .2s; text-decoration: none; color: inherit; }
.book-card:hover { border-color: var(--akjv-gold); background: rgba(255,215,0,.04); }
.book-card .bnum { width: 26px; height: 26px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: .68rem; font-weight: 700; flex-shrink: 0; }
.book-card .bnum.ot { background: rgba(59,130,246,.12); color: var(--akjv-blue); }
.book-card .bnum.nt { background: rgba(139,92,246,.12); color: var(--akjv-purple); }
.book-card .bnum.ap { background: rgba(245,158,11,.12); color: var(--akjv-gold2); }
.book-card .bnum.en { background: rgba(220,38,38,.12); color: var(--akjv-red); }

/* Section */
.heirloom-section { background: var(--akjv-surface); border: 1px solid var(--akjv-border); border-radius: 16px; padding: 2rem; margin: 2rem 0; }
.heirloom-section h2 { color: var(--akjv-gold); font-size: 1.2rem; margin-bottom: 1rem; }

/* Actions */
.heirloom-actions { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin: 2rem 0; }
.heirloom-actions .btn-h { padding: .7rem 1.8rem; border-radius: 10px; font-size: .95rem; font-weight: 700; text-decoration: none; border: 2px solid var(--akjv-gold); transition: all .3s; display: inline-flex; align-items: center; gap: .5rem; font-family: inherit; }
.heirloom-actions .btn-h.primary { background: var(--akjv-gold); color: #0a0a0f; }
.heirloom-actions .btn-h.primary:hover { filter: brightness(1.15); }
.heirloom-actions .btn-h.secondary { background: transparent; color: var(--akjv-gold); }
.heirloom-actions .btn-h.secondary:hover { background: rgba(255,215,0,.1); }

/* Corrections table */
.corrections-table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
.corrections-table th { text-align: left; padding: .5rem .7rem; font-size: .7rem; color: var(--akjv-dim); text-transform: uppercase; letter-spacing: .5px; border-bottom: 1px solid var(--akjv-border); }
.corrections-table td { padding: .5rem .7rem; border-bottom: 1px solid rgba(255,255,255,.04); font-size: .82rem; }
.corrections-table .original { color: var(--akjv-red); text-decoration: line-through; font-weight: 600; }
.corrections-table .restored { color: var(--akjv-green); font-weight: 700; }

@media (max-width: 700px) { .genealogy-chain { font-size: .75rem; } }
</style>

<?php if ($showReader): ?>
    <?php
    $ctx = akjv_reader_context($requestedBook, $requestedChapter, $lang);
    akjv_render_reader($ctx, '/bible/heirloom');
    ?>
<?php else: ?>
<div class="heirloom-page" dir="<?= $dir ?>">
    <div class="heirloom-hero">
        <div class="ed-badge"><?= $ed['icon'] ?> <?= akjv_t($ed['title'], $lang) ?></div>
        <h1>✝ OMAHON ✝</h1>
        <p class="subtitle"><?= akjv_t($ed['subtitle'], $lang) ?></p>
        <p style="color:rgba(255,215,0,.6);font-style:italic;font-size:.9rem;">"The grass withereth, the flower fadeth: but the word of our God shall stand for ever." — Isaiah 40:8 AKJV</p>
    </div>

    <!-- ═══ ACTIONS ═══ -->
    <div class="heirloom-actions">
        <a href="/bible/heirloom/read/Genesis/1?lang=<?= $lang ?>" class="btn-h primary">📖 <?= $lang === 'fr' ? 'Commencer la Lecture' : ($lang === 'he' ? 'התחל לקרוא' : 'Begin Reading') ?></a>
        <a href="/bible/pdf/heirloom?lang=<?= $lang ?>" class="btn-h secondary">📄 <?= $lang === 'fr' ? 'Télécharger PDF' : ($lang === 'he' ? 'הורד PDF' : 'Download PDF') ?></a>
        <a href="/bible/editions?lang=<?= $lang ?>" class="btn-h secondary">← <?= $lang === 'fr' ? 'Toutes les Éditions' : ($lang === 'he' ? 'כל המהדורות' : 'All Editions') ?></a>
    </div>

    <!-- ═══ THE PEREZ GENEALOGY ═══ -->
    <div class="genealogy-section">
        <h2>📜 <?= $lang === 'fr' ? 'La Lignée Perez — De la Genèse au Christ' : ($lang === 'he' ? 'שושלת פרץ — מבראשית עד המשיח' : 'The Perez Bloodline — Genesis to Christ') ?></h2>
        <p style="text-align:center;color:var(--akjv-muted);font-size:.85rem;margin-bottom:1rem;">
            <?= $lang === 'fr' ? '42 générations d\'Abraham au Christ (Matthieu 1:17). Le nombre est sacré.' : ($lang === 'he' ? '42 דורות מאברהם עד המשיח (מתי א:יז). המספר קדוש.' : '42 generations from Abraham to Christ (Matthew 1:17). The number is sacred.') ?>
        </p>
        <div class="genealogy-chain">
            <?php
            $ancestors = [
                'Abraham','Isaac','Jacob','Judah','PEREZ','Hezron','Ram','Amminadab','Nahshon','Salmon',
                'Boaz','Obed','Jesse','DAVID','Solomon','Rehoboam','Abijah','Asa','Jehoshaphat','Joram',
                'Uzziah','Jotham','Ahaz','Hezekiah','Manasseh','Amon','Josiah','Jeconiah','Shealtiel','Zerubbabel',
                'Abiud','Eliakim','Azor','Zadok','Achim','Eliud','Eleazar','Matthan','Jacob','Joseph',
                'Mary','JESUS CHRIST'
            ];
            foreach ($ancestors as $i => $name):
                $class = 'ancestor';
                if ($name === 'PEREZ') $class .= ' perez';
                elseif ($name === 'DAVID') $class .= ' david';
                elseif ($name === 'JESUS CHRIST') $class .= ' jesus';
            ?>
                <span class="<?= $class ?>"><?= $name ?></span>
                <?php if ($i < count($ancestors) - 1): ?><span class="arrow">→</span><?php endif; ?>
            <?php endforeach; ?>
        </div>
        <div style="text-align:center;margin-top:1rem;">
            <p style="color:var(--akjv-red);font-weight:700;font-size:.9rem;">PEREZ (פרץ) = BREACH · BREAKTHROUGH · THE ONE WHO BREAKS THROUGH</p>
            <p style="color:var(--akjv-muted);font-size:.8rem;font-style:italic;margin-top:.3rem;">Genesis 38:29 · Matthew 1:3 · Luke 3:33 · Micah 2:13</p>
        </div>
    </div>

    <!-- ═══ STATS ═══ -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:.8rem;margin:2rem 0;">
        <div style="background:var(--akjv-surface);border:1px solid var(--akjv-border);border-radius:12px;padding:1rem;text-align:center;">
            <div style="font-size:1.8rem;font-weight:800;color:var(--akjv-gold);"><?= $stats['total_books'] ?></div>
            <div style="font-size:.7rem;color:var(--akjv-dim);text-transform:uppercase;letter-spacing:.5px;">Books</div>
        </div>
        <div style="background:var(--akjv-surface);border:1px solid var(--akjv-border);border-radius:12px;padding:1rem;text-align:center;">
            <div style="font-size:1.8rem;font-weight:800;color:var(--akjv-green);"><?= number_format($stats['total_verses']) ?></div>
            <div style="font-size:.7rem;color:var(--akjv-dim);text-transform:uppercase;letter-spacing:.5px;">Verses</div>
        </div>
        <div style="background:var(--akjv-surface);border:1px solid var(--akjv-border);border-radius:12px;padding:1rem;text-align:center;">
            <div style="font-size:1.8rem;font-weight:800;color:var(--akjv-red);"><?= $stats['corrections'] ?></div>
            <div style="font-size:.7rem;color:var(--akjv-dim);text-transform:uppercase;letter-spacing:.5px;">Corrections</div>
        </div>
        <div style="background:var(--akjv-surface);border:1px solid var(--akjv-border);border-radius:12px;padding:1rem;text-align:center;">
            <div style="font-size:1.8rem;font-weight:800;color:var(--akjv-purple);"><?= $stats['prophecies'] ?></div>
            <div style="font-size:.7rem;color:var(--akjv-dim);text-transform:uppercase;letter-spacing:.5px;">Prophecies</div>
        </div>
        <div style="background:var(--akjv-surface);border:1px solid var(--akjv-border);border-radius:12px;padding:1rem;text-align:center;">
            <div style="font-size:1.8rem;font-weight:800;color:var(--akjv-gold);">42</div>
            <div style="font-size:.7rem;color:var(--akjv-dim);text-transform:uppercase;letter-spacing:.5px;">Generations</div>
        </div>
    </div>

    <!-- ═══ THE 15 CORRECTIONS EXPOSED ═══ -->
    <div class="heirloom-section">
        <h2>⚔️ <?= $lang === 'fr' ? 'Les 15 Corrections — La Vérité Restaurée' : ($lang === 'he' ? '15 התיקונים — האמת שוחזרה' : 'The 15 Corrections — Truth Restored') ?></h2>
        <p style="color:var(--akjv-muted);font-size:.85rem;margin-bottom:1rem;">
            <?= $lang === 'fr' ? 'Chaque altération du texte original est exposée et corrigée.' : ($lang === 'he' ? 'כל שינוי מהטקסט המקורי נחשף ותוקן.' : 'Every alteration from the original text is exposed and corrected.') ?>
        </p>
        <table class="corrections-table">
            <thead>
                <tr>
                    <th><?= $lang === 'fr' ? 'Original (Corrompu)' : 'Original (Corrupted)' ?></th>
                    <th><?= $lang === 'fr' ? 'AKJV (Restauré)' : 'AKJV (Restored)' ?></th>
                    <th><?= $lang === 'fr' ? 'Livre' : 'Book' ?></th>
                    <th><?= $lang === 'fr' ? 'Réf.' : 'Ref.' ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($corrections as $c): ?>
                <tr>
                    <td class="original"><?= htmlspecialchars($c['original_text'] ?? $c['kjv_text'] ?? '') ?></td>
                    <td class="restored"><?= htmlspecialchars($c['corrected_text'] ?? $c['akjv_text'] ?? '') ?></td>
                    <td style="color:var(--akjv-gold);"><?= htmlspecialchars($c['book_name'] ?? '') ?></td>
                    <td style="color:var(--akjv-dim);"><?= ($c['chapter'] ?? '') . ':' . ($c['verse'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- ═══ 57 MESSIANIC PROPHECIES ═══ -->
    <div class="heirloom-section">
        <h2>⭐ <?= $lang === 'fr' ? '57 Prophéties Messianiques' : ($lang === 'he' ? '57 נבואות משיחיות' : '57 Messianic Prophecies') ?></h2>
        <p style="color:var(--akjv-muted);font-size:.85rem;margin-bottom:1rem;">
            <?= $lang === 'fr' ? 'Prophétisé dans le Tanakh, accompli dans le Christ.' : ($lang === 'he' ? 'נובא בתנ"ך, התגשם במשיח.' : 'Prophesied in the Tanakh, fulfilled in Christ.') ?>
        </p>
        <div class="prophecies-grid">
            <?php foreach (array_slice($prophecies, 0, 12) as $p): ?>
            <div class="prophecy-card">
                <div class="num"><?= $lang === 'fr' ? 'Prophétie' : 'Prophecy' ?> #<?= $p['prophecy_number'] ?></div>
                <h4><?= htmlspecialchars($p['title'] ?? '') ?></h4>
                <div class="ref">
                    <span style="color:var(--akjv-blue);">📖 <?= htmlspecialchars($p['tanakh_reference'] ?? '') ?></span>
                    <br>
                    <span style="color:var(--akjv-purple);">✝ <?= htmlspecialchars($p['nt_reference'] ?? '') ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="text-align:center;margin-top:1rem;">
            <a href="/bible/prophecies?lang=<?= $lang ?>" style="color:var(--akjv-gold);font-size:.9rem;"><?= $lang === 'fr' ? 'Voir les 57 Prophéties →' : ($lang === 'he' ? 'צפה ב-57 הנבואות →' : 'View All 57 Prophecies →') ?></a>
        </div>
    </div>

    <!-- ═══ THE FULL LIBRARY — 94 BOOKS ═══ -->
    <div class="heirloom-section">
        <h2>📚 <?= $lang === 'fr' ? 'La Bibliothèque Complète — 94 Livres' : ($lang === 'he' ? 'הספרייה המלאה — 94 ספרים' : 'The Complete Library — 94 Books') ?></h2>
        <?php foreach ($booksByTestament as $testament => $books): ?>
        <h3 style="color:var(--akjv-gold);font-size:.95rem;margin:1.5rem 0 .5rem;">
            <?= $testamentLabels[$testament] ?? $testament ?> (<?= count($books) ?>)
        </h3>
        <div class="book-grid">
            <?php foreach ($books as $b): ?>
            <a href="/bible/heirloom/read/<?= urlencode($b['book_name']) ?>/1?lang=<?= $lang ?>" class="book-card">
                <span class="bnum <?= strtolower($b['testament']) ?>"><?= $b['book_number'] ?></span>
                <span style="color:#fff;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= akjv_book_name($b, $lang) ?></span>
                <span style="color:var(--akjv-dim);font-size:.68rem;white-space:nowrap;margin-left:auto;"><?= $b['total_chapters'] ?> ch</span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ═══ DECREE ARCHIVE ═══ -->
    <div class="heirloom-section">
        <h2>📋 <?= $lang === 'fr' ? 'Archive des Décrets' : ($lang === 'he' ? 'ארכיון הגזרות' : 'Decree Archive') ?></h2>
        <p style="color:var(--akjv-muted);font-size:.85rem;margin-bottom:1rem;">
            <?= $lang === 'fr' ? 'Tous les Actes Officiels du Royaume de GoSiteMe, scellés par la Parole de Dieu.' : ($lang === 'he' ? 'כל הפעולות הרשמיות של ממלכת GoSiteMe, חתומות בדבר האל.' : 'All Official Acts of the Kingdom of GoSiteMe, sealed by the Word of God.') ?>
        </p>
        <div style="display:grid;gap:.5rem;">
            <?php for ($i = 1; $i <= 12; $i++): ?>
            <a href="https://lavocat.ca/journal?lang=<?= $lang ?>&status=official" style="display:flex;align-items:center;gap:.8rem;padding:.7rem 1rem;background:rgba(255,215,0,.04);border:1px solid rgba(255,215,0,.12);border-radius:8px;text-decoration:none;color:var(--akjv-white);font-size:.85rem;transition:all .2s;">
                <span style="color:var(--akjv-gold);font-weight:700;min-width:60px;">N° <?= str_pad($i, 3, '0', STR_PAD_LEFT) ?></span>
                <span style="flex:1;color:var(--akjv-muted);">Official Act</span>
                <span style="color:var(--akjv-dim);font-size:.75rem;">→</span>
            </a>
            <?php endfor; ?>
        </div>
    </div>

    <!-- ═══ FOOTER SEAL ═══ -->
    <div style="text-align:center;padding:3rem 0;border-top:2px solid rgba(255,215,0,.2);margin-top:2rem;">
        <div style="font-size:2rem;color:var(--akjv-gold);letter-spacing:6px;margin-bottom:1rem;">✝ OMAHON ✝</div>
        <p style="color:var(--akjv-muted);font-size:.85rem;max-width:500px;margin:0 auto;line-height:1.7;">
            <?= $lang === 'fr' ? 'L\'Édition Héritage — Autorisée le 8 avril 2026 A.D. par la lignée Perez. Transmise de Danny à Eden, d\'Eden à ses enfants, pour toujours.' : ($lang === 'he' ? 'מהדורת הירושה — אושרה ב-8 באפריל 2026 לספירה על ידי שושלת פרץ. מועברת מדני לעדן, מעדן לילדיה, לנצח.' : 'The Heirloom Edition — Authorized April 8, 2026 A.D. by the Perez bloodline. Passed from Danny to Eden, from Eden to her children, forever.') ?>
        </p>
        <p style="color:rgba(255,215,0,.5);font-style:italic;font-size:.8rem;margin-top:1rem;">"Heaven and earth shall pass away, but my words shall not pass away." — Matthew 24:35 AKJV</p>
    </div>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/site-footer.inc.php'; ?>
