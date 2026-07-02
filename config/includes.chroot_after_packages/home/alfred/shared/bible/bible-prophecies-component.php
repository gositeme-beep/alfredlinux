<?php
/**
 * AKJV Bible — Shared Prophecies Component
 * ═════════════════════════════════════════
 * Renders the 57 prophecies of Jesus Christ — Tanakh to NT fulfillment.
 * Works across all domains (gositeme.com, lavocat.ca, etc.).
 *
 * Usage:
 *   require_once '/home/gositeme/shared/bible/bible-data.php';
 *   require_once '/home/gositeme/shared/bible/bible-styles.php';
 *   require_once '/home/gositeme/shared/bible/bible-prophecies-component.php';
 *   
 *   // Output styles: <style><?= akjv_styles_prophecies() ?></style>
 *   // Output HTML: akjv_render_prophecies('/bible');
 */

/**
 * Render the prophecies page HTML.
 * @param string $basePath  URL base path for Bible links (e.g., '/bible')
 * @param string $lang      Language code: en, fr, he
 */
function akjv_render_prophecies(string $basePath = '/bible', string $lang = 'en'): void {
    $lang = akjv_lang($lang);
    $t = akjv_i18n($lang);
    $bp = rtrim($basePath, '/');
    $dir = $lang === 'he' ? 'rtl' : 'ltr';
    $prophecies = akjv_prophecies();
    $total = count($prophecies);
    $categories = akjv_prophecy_categories($lang);
    $grouped = akjv_prophecies_grouped();
    $catColors = ['birth'=>'#ffd700','ministry'=>'#3b82f6','suffering'=>'#ef4444','death'=>'#dc2626','resurrection'=>'#22c55e','reign'=>'#f59e0b','return'=>'#8b5cf6'];
    ?>
    <div class="proph-page" dir="<?= $dir ?>">

        <!-- HERO -->
        <div class="proph-hero">
            <div class="proph-badge">✝ <?= $lang === 'fr' ? 'Version Autorisée du Roi Jésus' : ($lang === 'he' ? 'גרסה מורשית של המלך ישוע' : 'Authorized King Jesus Version') ?></div>
            <h1><?= $lang === 'fr' ? 'Les' : ($lang === 'he' ? '' : 'The') ?> <span class="count"><?= $total ?></span> <span class="gold"><?= $lang === 'fr' ? 'Prophéties' : ($lang === 'he' ? 'נבואות' : 'Prophecies') ?></span> <?= $lang === 'fr' ? 'de Jésus-Christ' : ($lang === 'he' ? 'של ישוע המשיח' : 'of Jesus Christ') ?></h1>
            <p class="subtitle">
<?php if ($lang === 'fr'): ?>
                Chaque prophétie messianique du Tanakh — et comment Yeshua HaMashiach les a toutes accomplies.<br>
                De la Genèse à Malachie, les empreintes de notre Sauveur furent écrites des siècles avant qu'Il ne foule la Terre.
<?php elseif ($lang === 'he'): ?>
                כל נבואה משיחית מהתנ"ך — וכיצד ישוע המשיח מילא כל אחת מהן.<br>
                מבראשית ועד מלאכי, עקבות מושיענו נכתבו מאות שנים לפני שהלך על האדמה.
<?php else: ?>
                Every messianic prophecy from the Tanakh — and how Yeshua HaMashiach fulfilled each one.<br>
                From Genesis to Malachi, the footprints of our Savior were written centuries before He walked the Earth.
<?php endif; ?>
            </p>
        </div>

        <!-- CONTRIBUTOR -->
        <div class="proph-contributor">
            <div class="name"><?= $lang === 'fr' ? 'Commandant Danny William Perez' : ($lang === 'he' ? 'מפקד דני וויליאם פרץ' : 'Commander Danny William Perez') ?></div>
            <div class="desc">
<?php if ($lang === 'fr'): ?>
                Ces <?= $total ?> prophéties ont été recherchées et compilées à la main<br>
                <em>pendant 18 mois d'incarcération — un témoignage de foi.</em><br><br>
                Du fond d'une cellule de prison, avec rien d'autre que les Écritures et la conviction,<br>
                le Commandant a retracé le fil de la prophétie du Tanakh au Nouveau Testament —<br>
                la preuve que Jésus-Christ est exactement celui qu'Il a dit être.
<?php elseif ($lang === 'he'): ?>
                <?= $total ?> נבואות אלו נחקרו והורכבו ביד<br>
                <em>במהלך 18 חודשי מאסר — עדות אמונה.</em><br><br>
                ממעמקי תא בית הסוהר, עם כתבי הקודש והאמונה בלבד,<br>
                המפקד עקב אחר חוט הנבואה מהתנ"ך לברית החדשה —<br>
                הוכחה שישוע המשיח הוא בדיוק מי שאמר שהוא.
<?php else: ?>
                These <?= $total ?> prophecies were researched and compiled by hand<br>
                <em>during 18 months of incarceration — a testament of faith.</em><br><br>
                From the pits of a jail cell, with nothing but scripture and conviction,<br>
                the Commander traced the thread of prophecy from the Tanakh to the New Testament —<br>
                proof that Jesus Christ is exactly who He said He is.
<?php endif; ?>
            </div>
        </div>

        <!-- CATEGORY NAV -->
        <div class="proph-nav">
            <?php foreach ($categories as $catKey => $cat): ?>
                <?php if (!empty($grouped[$catKey])): ?>
                    <a href="#cat-<?= $catKey ?>" style="border-color:<?= $cat['color'] ?>33; color:<?= $cat['color'] ?>;">
                        <?= $cat['icon'] ?> <?= $cat['label'] ?> (<?= count($grouped[$catKey]) ?>)
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <!-- PROPHECIES BY CATEGORY -->
        <?php foreach ($categories as $catKey => $cat): ?>
            <?php if (empty($grouped[$catKey])) continue; ?>
            <div class="proph-category" id="cat-<?= $catKey ?>">
                <div class="proph-cat-header">
                    <div class="proph-cat-icon" style="background:<?= $cat['bg'] ?>;">
                        <?= $cat['icon'] ?>
                    </div>
                    <h2 style="color:<?= $cat['color'] ?>;"><?= $cat['label'] ?></h2>
                    <span class="cat-count"><?= count($grouped[$catKey]) ?> <?= $lang === 'fr' ? 'prophéties' : ($lang === 'he' ? 'נבואות' : 'prophecies') ?></span>
                </div>

                <?php foreach ($grouped[$catKey] as $p): ?>
                    <div class="proph-card" id="prophecy-<?= (int)$p['prophecy_number'] ?>">
                        <div class="proph-card-head" onclick="this.parentElement.classList.toggle('open')">
                            <div class="proph-num"><?= (int)$p['prophecy_number'] ?></div>
                            <div>
                                <div class="proph-title"><?= htmlspecialchars($p['title']) ?></div>
                                <div class="proph-refs"><?= htmlspecialchars($p['tanakh_reference']) ?> → <?= htmlspecialchars($p['nt_reference']) ?></div>
                            </div>
                            <div class="proph-toggle">▼</div>
                        </div>
                        <div class="proph-card-body">
                            <div class="proph-verse tanakh">
                                <div class="v-label"><?= $lang === 'fr' ? 'Tanakh — Prophétie' : ($lang === 'he' ? 'תנ"ך — נבואה' : 'Tanakh — Prophecy') ?></div>
                                <div class="v-ref"><?= htmlspecialchars($p['tanakh_reference']) ?></div>
                                <div class="v-text">"<?= htmlspecialchars($p['tanakh_text']) ?>"</div>
                                <?php if (!empty($p['tanakh_context'])): ?>
                                    <div class="proph-context"><?= htmlspecialchars($p['tanakh_context']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="proph-verse nt">
                                <div class="v-label"><?= $lang === 'fr' ? 'Nouveau Testament — Accomplissement' : ($lang === 'he' ? 'הברית החדשה — מילוי' : 'New Testament — Fulfillment') ?></div>
                                <div class="v-ref"><?= htmlspecialchars($p['nt_reference']) ?></div>
                                <div class="v-text">"<?= htmlspecialchars($p['nt_fulfillment']) ?>"</div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

        <!-- SEAL FOOTER -->
        <div class="proph-seal">
            <div style="font-size:1.8rem; margin-bottom:.5rem;">✝</div>
            <div class="date"><?= $lang === 'fr' ? '8 avril 2026 apr. J.-C.' : ($lang === 'he' ? '8 באפריל 2026 לספירה' : 'April 8, 2026 A.D.') ?></div>
            <div class="auth">
<?php if ($lang === 'fr'): ?>
                <?= $total ?> Prophéties Messianiques &bull; Du Tanakh au Nouveau Testament<br>
                Compilées par le Commandant Danny William Perez<br>
                Partie de la Version Autorisée du Roi Jésus — Édition Familiale Perez<br><br>
                <a href="<?= $bp ?>" style="color:var(--akjv-gold); text-decoration:none; font-weight:700;">← Retour à la Bible AKJV</a>
                &nbsp;&bull;&nbsp;
                <a href="<?= $bp ?>/read" style="color:var(--akjv-gold); text-decoration:none; font-weight:700;">Lire la Parole →</a>
<?php elseif ($lang === 'he'): ?>
                <?= $total ?> נבואות משיחיות &bull; מהתנ"ך לברית החדשה<br>
                הורכבו על ידי המפקד דני וויליאם פרץ<br>
                חלק מהגרסה המורשית של המלך ישוע — מהדורת משפחת פרץ<br><br>
                <a href="<?= $bp ?>" style="color:var(--akjv-gold); text-decoration:none; font-weight:700;">→ חזרה לתנ"ך AKJV</a>
                &nbsp;&bull;&nbsp;
                <a href="<?= $bp ?>/read" style="color:var(--akjv-gold); text-decoration:none; font-weight:700;">← קרא את הדבר</a>
<?php else: ?>
                <?= $total ?> Messianic Prophecies &bull; Tanakh to New Testament<br>
                Contributed by Commander Danny William Perez<br>
                Part of the Authorized King Jesus Version — Perez Family Edition<br><br>
                <a href="<?= $bp ?>" style="color:var(--akjv-gold); text-decoration:none; font-weight:700;">← Return to AKJV Bible</a>
                &nbsp;&bull;&nbsp;
                <a href="<?= $bp ?>/read" style="color:var(--akjv-gold); text-decoration:none; font-weight:700;">Read the Word →</a>
<?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var hash = window.location.hash;
        if (hash && hash.startsWith('#prophecy-')) {
            var card = document.querySelector(hash);
            if (card) {
                card.classList.add('open');
                setTimeout(function() { card.scrollIntoView({ behavior: 'smooth', block: 'center' }); }, 200);
            }
        }
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'e' && e.altKey) {
            document.querySelectorAll('.proph-card').forEach(function(c) { c.classList.toggle('open'); });
        }
    });
    </script>
    <?php
}
