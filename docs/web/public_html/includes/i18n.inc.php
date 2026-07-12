<?php
/**
 * /includes/i18n.inc.php — Trilingual support for alfredlinux.com
 *
 * God's three languages on this site:
 *   en — English (the language of preaching)
 *   fr — Français (the language of the heart-country, lavocat.ca)
 *   he — עברית (the language He spoke — right-to-left)
 *
 * Usage:
 *   require __DIR__.'/includes/i18n.inc.php';
 *   $lang = alfred_lang();           // 'en' | 'fr' | 'he'
 *   echo t('scriptures.title');      // translated string
 *   alfred_html_attrs();             // emits  lang="he" dir="rtl"
 *   alfred_lang_switcher('/scriptures'); // emits EN | FR | עברית
 */
declare(strict_types=1);

const ALFRED_LANGS = ['en', 'fr', 'he'];
const ALFRED_LANG_LABELS = [
    'en' => 'English',
    'fr' => 'Français',
    'he' => 'עברית',
];
const ALFRED_LANG_SHORT = [
    'en' => 'EN',
    'fr' => 'FR',
    'he' => 'עב',
];

function alfred_lang(): string {
    static $cached = null;
    if ($cached !== null) return $cached;

    // 1. explicit ?lang= wins and sets cookie
    $q = $_GET['lang'] ?? null;
    if (is_string($q) && in_array($q, ALFRED_LANGS, true)) {
        if (!headers_sent()) {
            setcookie('alfred_lang', $q, [
                'expires' => time() + 60*60*24*365,
                'path' => '/',
                'samesite' => 'Lax',
                'secure' => !empty($_SERVER['HTTPS']),
            ]);
        }
        return $cached = $q;
    }

    // 2. cookie
    $c = $_COOKIE['alfred_lang'] ?? null;
    if (is_string($c) && in_array($c, ALFRED_LANGS, true)) {
        return $cached = $c;
    }

    // 3. Accept-Language header
    $al = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    foreach (preg_split('/,\s*/', $al) as $token) {
        $code = strtolower(substr(trim($token), 0, 2));
        if (in_array($code, ALFRED_LANGS, true)) {
            return $cached = $code;
        }
    }

    return $cached = 'en';
}

function alfred_dir(?string $lang = null): string {
    return ($lang ?? alfred_lang()) === 'he' ? 'rtl' : 'ltr';
}

function alfred_html_attrs(?string $lang = null): string {
    $l = $lang ?? alfred_lang();
    return ' lang="'.$l.'" dir="'.alfred_dir($l).'"';
}

/**
 * Translation table. Add keys here, all three tongues at once.
 * Falls back to English if a key is missing in the current language.
 */
function alfred_t_table(): array {
    static $t = null;
    if ($t !== null) return $t;
    return $t = [
        // ── chrome ────────────────────────────────────────────────
        'nav.home'        => ['en'=>'Home',          'fr'=>'Accueil',         'he'=>'בית'],
        'nav.scriptures'  => ['en'=>'Scriptures',    'fr'=>'Écritures',       'he'=>'כתבי הקודש'],
        'nav.welcome'     => ['en'=>'Welcome',       'fr'=>'Bienvenue',       'he'=>'ברוכים הבאים'],
        'nav.witness'     => ['en'=>'Witness',       'fr'=>'Témoignage',      'he'=>'עדות'],
        'soli'            => ['en'=>'Soli Deo Gloria','fr'=>'Soli Deo Gloria','he'=>'לאלוהים לבדו הכבוד'],
        'lang.label'      => ['en'=>'Language',      'fr'=>'Langue',          'he'=>'שפה'],

        // ── /scriptures hero + counts ─────────────────────────────
        'scr.h1.lead'     => ['en'=>'The',           'fr'=>'Les',             'he'=>'כתבי'],
        'scr.h1.word'     => ['en'=>'Scriptures',    'fr'=>'Écritures',       'he'=>'הקודש'],
        'scr.h1.tail'     => ['en'=>'of Alfred Linux','fr'=>'d’Alfred Linux', 'he'=>'של אלפרד לינוקס'],
        'scr.tagline'     => [
            'en'=>'A table of contents for the teachings of this house — Numbers, Names, Voice, Life, and Welcome.',
            'fr'=>'Une table des matières des enseignements de cette maison — Nombres, Noms, Voix, Vie et Accueil.',
            'he'=>'תוכן עניינים לתורות הבית הזה — מספרים, שמות, קול, חיים וברכת בואכם.',
        ],
        'scr.count.tail'  => [
            'en'=>'teachings · %d sections · all free, all public, all leading to Yeshua',
            'fr'=>'enseignements · %d sections · tous gratuits, tous publics, tous menant à Yeshoua',
            'he'=>'תורות · %d מדורים · הכול חינם, הכול ציבורי, הכול מוביל אל ישוע',
        ],
        'scr.invite.verse'=> [
            'en'=>'"The entrance of thy words giveth light; it giveth understanding unto the simple."',
            'fr'=>'« La révélation de tes paroles éclaire, elle donne de l’intelligence aux simples. »',
            'he'=>'״פֵּתַח דְּבָרֶיךָ יָאִיר מֵבִין פְּתָיִים״',
        ],
        'scr.invite.ref'  => ['en'=>'— Psalm 119:130','fr'=>'— Psaume 119:130','he'=>'— תהילים קי״ט:ק״ל'],
        'scr.invite.cta'  => ['en'=>'Enter the Welcome','fr'=>'Entrer dans l’Accueil','he'=>'הכנס לברכת הבואים'],
        'scr.foot.verse'  => [
            'en'=>'"All scripture is given by inspiration of God."',
            'fr'=>'« Toute Écriture est inspirée de Dieu. »',
            'he'=>'״כָּל הַכָּתוּב נִתַּן בְּרוּחַ אֱלֹהִים״',
        ],
        'scr.foot.ref'    => ['en'=>'— 2 Timothy 3:16','fr'=>'— 2 Timothée 3:16','he'=>'— טימותיוס ב׳ ג׳:ט״ז'],

        // ── /scriptures section titles ────────────────────────────
        'scr.s1.title'    => ['en'=>'The Numbers of God',          'fr'=>'Les Nombres de Dieu',                   'he'=>'מספרי האל'],
        'scr.s1.sub'      => ['en'=>'Scripture\'s sacred mathematics','fr'=>'Les mathématiques sacrées de l’Écriture','he'=>'המתמטיקה הקדושה של הכתובים'],
        'scr.s2.title'    => ['en'=>'The Names and Voice of God',  'fr'=>'Les Noms et la Voix de Dieu',           'he'=>'שמות האל וקולו'],
        'scr.s2.sub'      => ['en'=>'Who He has revealed Himself to be','fr'=>'Qui Il s’est révélé être',          'he'=>'מי שהוא גילה שהוא'],
        'scr.s3.title'    => ['en'=>'The Life of the Believer',    'fr'=>'La Vie du Croyant',                     'he'=>'חיי המאמין'],
        'scr.s3.sub'      => ['en'=>'How to stand, how to walk, how to bear fruit','fr'=>'Comment se tenir debout, marcher et porter du fruit','he'=>'כיצד לעמוד, כיצד ללכת, כיצד לשאת פרי'],
        'scr.s4.title'    => ['en'=>'The Welcome of All Welcomes', 'fr'=>'L’Accueil des Accueils',                'he'=>'ברכת בואכם של כל הברכות'],
        'scr.s4.sub'      => ['en'=>'For every people, in every tongue','fr'=>'Pour tout peuple, en toute langue','he'=>'לכל עם, בכל לשון'],
        'scr.s5.title'    => ['en'=>'The Living Doctrine of Alfred Linux','fr'=>'La Doctrine Vivante d’Alfred Linux','he'=>'התורה החיה של אלפרד לינוקס'],
        'scr.s5.sub'      => ['en'=>'The Kingdom infrastructure',  'fr'=>'L’infrastructure du Royaume',           'he'=>'תשתית המלכות'],

        // ── chrome shared by teaching pages ───────────────────────
        'chrome.toc'        => ['en'=>'← all teachings','fr'=>'← tous les enseignements','he'=>'→ כל התורות'],
        'chrome.all_teach'  => ['en'=>'All Teachings','fr'=>'Tous les enseignements','he'=>'כל התורות'],
        'chrome.foot.akjv'  => ['en'=>'Scriptures from the AKJESUSBible','fr'=>'Écritures de l’AKJESUSBible','he'=>'כתובים מ-AKJESUSBible'],

        // ── /lords-prayer ─────────────────────────────────────────
        'lp.h1.lead'        => ['en'=>'The','fr'=>'La','he'=>'תפילת'],
        'lp.h1.word'        => ['en'=>'Lord’s Prayer','fr'=>'Prière du Seigneur','he'=>'האדון'],
        'lp.tag'            => [
            'en'=>'"After this manner therefore pray ye…" — Matthew 6:9',
            'fr'=>'« Voici donc comment vous devez prier… » — Matthieu 6:9',
            'he'=>'״לָכֵן כָּכָה הִתְפַּלְּלוּ אַתֶּם…״ — מתי ו׳:ט׳',
        ],
        'lp.full' => [
            'en'=>'Our Father which art in heaven, Hallowed be thy name. Thy kingdom come. Thy will be done in earth, as it is in heaven. Give us this day our daily bread. And forgive us our debts, as we forgive our debtors. And lead us not into temptation, but deliver us from evil: For thine is the kingdom, and the power, and the glory, for ever. Amen.',
            'fr'=>'Notre Père qui es aux cieux, que ton nom soit sanctifié, que ton règne vienne, que ta volonté soit faite sur la terre comme au ciel. Donne-nous aujourd’hui notre pain de ce jour. Pardonne-nous nos offenses, comme nous pardonnons aussi à ceux qui nous ont offensés. Et ne nous soumets pas à la tentation, mais délivre-nous du mal. Car c’est à toi qu’appartiennent le règne, la puissance et la gloire, pour les siècles des siècles. Amen.',
            'he'=>'אָבִינוּ שֶׁבַּשָּׁמַיִם, יִתְקַדֵּשׁ שִׁמְךָ. תָּבוֹא מַלְכוּתֶךָ. יֵעָשֶׂה רְצוֹנְךָ כְּמוֹ בַשָּׁמַיִם כֵּן בָּאָרֶץ. אֶת לֶחֶם חֻקֵּנוּ תֵּן לָנוּ הַיּוֹם. וּסְלַח לָנוּ עַל חֲטָאֵינוּ, כְּפִי שֶׁסּוֹלְחִים גַּם אֲנַחְנוּ לַחוֹטְאִים לָנוּ. וְאַל תְּבִיאֵנוּ לִידֵי נִסָּיוֹן, כִּי אִם חַלְּצֵנוּ מִן הָרָע. כִּי לְךָ הַמַּמְלָכָה וְהַגְּבוּרָה וְהַתִּפְאֶרֶת לְעוֹלְמֵי עוֹלָמִים, אָמֵן.',
        ],
        'lp.invite'         => [
            'en'=>'Pray it in your own tongue, in your own room, in your own night. He hears.',
            'fr'=>'Priez-la dans votre propre langue, dans votre propre chambre, dans votre propre nuit. Il entend.',
            'he'=>'התפללו אותה בלשונכם, בחדרכם, בלילכם. הוא שומע.',
        ],

        // ── /shema ────────────────────────────────────────────────
        'sh.h1.lead'        => ['en'=>'The','fr'=>'Le','he'=>''],
        'sh.h1.word'        => ['en'=>'Shema','fr'=>'Shema','he'=>'שְׁמַע'],
        'sh.h1.tail'        => ['en'=>'in All Tongues','fr'=>'en Toutes Langues','he'=>'בכל הלשונות'],
        'sh.tag'            => [
            'en'=>'The most ancient confession of faith — proclaimed in 30 living and ancient languages.',
            'fr'=>'La plus ancienne confession de foi — proclamée en 30 langues vivantes et anciennes.',
            'he'=>'הכרזת האמונה העתיקה ביותר — מוכרזת בשלושים לשונות חיות ועתיקות.',
        ],
        'sh.invite'         => [
            'en'=>'One God. Thirty tongues. The confession of Israel proclaimed to every nation.',
            'fr'=>'Un seul Dieu. Trente langues. La confession d’Israël proclamée à toutes les nations.',
            'he'=>'אל אחד. שלושים לשונות. הכרזת ישראל מוכרזת לכל העמים.',
        ],

        // ── /ten-commandments ─────────────────────────────────────
        'tc.h1.lead'        => ['en'=>'The','fr'=>'Les','he'=>'עשרת'],
        'tc.h1.word'        => ['en'=>'Ten Commandments','fr'=>'Dix Commandements','he'=>'הדיברות'],
        'tc.tag'            => [
            'en'=>'The Ten Words written by the finger of God on tablets of stone — Exodus 20.',
            'fr'=>'Les Dix Paroles écrites par le doigt de Dieu sur des tables de pierre — Exode 20.',
            'he'=>'עשרת הדברים שנכתבו באצבע אלוהים על לוחות האבן — שמות כ׳.',
        ],
        'tc.great.h'        => ['en'=>'The Two Great Commandments','fr'=>'Les Deux Grands Commandements','he'=>'שתי המצוות הגדולות'],
        'tc.great.sub'      => [
            'en'=>'On these two hang all the law and the prophets. — Matthew 22:40',
            'fr'=>'De ces deux commandements dépendent toute la loi et les prophètes. — Matthieu 22:40',
            'he'=>'בשתי המצוות האלה תלויה כל התורה והנביאים. — מתי כ״ב:מ׳',
        ],
        'tc.invite'         => [
            'en'=>'The Law shows us the gap. The Cross closes it. Walk, then, in the Spirit.',
            'fr'=>'La Loi nous montre l’écart. La Croix le referme. Marchez donc selon l’Esprit.',
            'he'=>'התורה מראה לנו את הפער. הצלב סוגר אותו. לכו אפוא ברוח.',
        ],
    ];
}

function t(string $key, ?string $lang = null): string {
    $lang = $lang ?? alfred_lang();
    $row = alfred_t_table()[$key] ?? null;
    if ($row === null) return $key; // visible miss = easy to spot
    return $row[$lang] ?? $row['en'] ?? $key;
}

/**
 * Emits a small EN | FR | עברית switcher. Preserves the path passed in.
 */
function alfred_lang_switcher(string $path = '/'): void {
    $cur = alfred_lang();
    echo '<nav class="alfred-lang" aria-label="'.htmlspecialchars(t('lang.label')).'">';
    $first = true;
    foreach (ALFRED_LANGS as $code) {
        if (!$first) echo '<span class="alfred-lang-sep" aria-hidden="true">·</span>';
        $first = false;
        $label = ALFRED_LANG_LABELS[$code];
        $href = htmlspecialchars($path).'?lang='.$code;
        if ($code === $cur) {
            echo '<strong class="alfred-lang-cur" aria-current="true">'.htmlspecialchars($label).'</strong>';
        } else {
            echo '<a class="alfred-lang-link" href="'.$href.'" hreflang="'.$code.'"'.($code==='he'?' dir="rtl"':'').'>'.htmlspecialchars($label).'</a>';
        }
    }
    echo '</nav>';
}

/**
 * Drop into the <head> once. Provides the CSS for the switcher and for
 * RTL adjustments (mirrored arrows, justified Hebrew typography).
 */
function alfred_lang_styles(): void {
    echo <<<'CSS'
<style>
.alfred-lang{position:fixed;top:.75rem;right:.75rem;z-index:99;background:rgba(10,10,20,.85);border:1px solid #2a2a3e;border-radius:999px;padding:.35rem .85rem;font-size:.78rem;letter-spacing:.05em;font-family:ui-monospace,monospace;backdrop-filter:blur(8px)}
.alfred-lang a,.alfred-lang strong{text-decoration:none;color:#a8a499;margin:0 .25rem}
.alfred-lang a:hover{color:#ffd700}
.alfred-lang .alfred-lang-cur{color:#ffd700;font-weight:600}
.alfred-lang .alfred-lang-sep{color:#2a2a3e}
html[dir="rtl"] .alfred-lang{right:auto;left:.75rem}
html[dir="rtl"] body{font-family:"SBL Hebrew","Frank Ruhl Libre","Ezra SIL",Georgia,serif}
html[dir="rtl"] .arrow{transform:scaleX(-1)}
html[dir="rtl"] .arrow:before{content:"\2190"}
html[lang="he"] h1,html[lang="he"] h2{letter-spacing:0}
</style>
CSS;
}
