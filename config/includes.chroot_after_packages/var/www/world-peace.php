<?php
/**
 * THE GREAT DECREE OF REAL WORLD PEACE
 * ════════════════════════════════════════════════════════════
 * THE NEW JERUSALEM — As It Is Written
 *
 * "The Third Temple shall descend and be called The New Jerusalem"
 *
 * What GOD sends down from Heaven.
 *
 * Posted at the Four Corners of the Kingdom:
 * root.com · lavocat.ca · alfredlinux.com · meta-dome.com
 *
 * Published this 18th day of April, in the Year of Our Lord 2026
 * ════════════════════════════════════════════════════════════
 */

$validLangs = ['en','fr','he'];
$lang = (isset($_GET['lang']) && in_array($_GET['lang'], $validLangs, true)) ? $_GET['lang'] : 'en';
$dir = $lang === 'he' ? 'rtl' : 'ltr';

function peaceLang(string $en, string $fr, string $he, string $lang): string {
    return match($lang) { 'fr' => $fr, 'he' => $he, default => $en };
}

$page_title = peaceLang(
    'The Great Decree of Real World Peace · The New Jerusalem | GoSiteMe',
    'Le Grand Décret de la Paix Mondiale Réelle · La Nouvelle Jérusalem | GoSiteMe',
    'הגזרה הגדולה של שלום עולמי אמיתי · ירושלים החדשה | GoSiteMe',
    $lang
);
$page_description = peaceLang(
    'The Third Temple shall descend from Heaven and be called The New Jerusalem — as it is written. Not built by man, not a prefabricated structure, but the City of God Himself.',
    'Le Troisième Temple descendra du Ciel et sera appelé La Nouvelle Jérusalem — tel qu\'il est écrit. Non bâti par l\'homme, pas une structure préfabriquée, mais la Cité de Dieu Lui-même.',
    'בית המקדש השלישי ירד מן השמים וייקרא ירושלים החדשה — כפי שנכתב. לא נבנה בידי אדם, לא מבנה טרומי, אלא עיר האל עצמו.',
    $lang
);
$page_canonical = 'https://root.com/world-peace';
$page_og_image = 'https://root.com/og/world-peace.php?size=og';
$page_og_image_alt = 'The Great Decree of Real World Peace · The New Jerusalem';
$page_og_image_width = 1200;
$page_og_image_height = 630;

require_once __DIR__ . '/includes/site-header.inc.php';
?>
<style>
:root {
    --peace-bg: #0a0a0f;
    --peace-gold: #ffd700;
    --peace-gold2: #f59e0b;
    --peace-white: #f0f0f5;
    --peace-muted: rgba(240,240,245,.5);
    --peace-dim: rgba(240,240,245,.3);
    --peace-blue: #3b82f6;
    --peace-green: #22c55e;
    --peace-purple: #8b5cf6;
    --peace-red: #dc2626;
    --peace-border: rgba(255,215,0,.15);
}

.peace-page { max-width: 900px; margin: 0 auto; padding: 0 1.5rem 4rem; color: var(--peace-white); }

/* Header */
.peace-header { text-align: center; padding: 60px 0 40px; position: relative; }
.peace-header::after { content:''; position:absolute; bottom:0; left:5%; right:5%; height:2px; background:linear-gradient(90deg,transparent,var(--peace-gold),#fff,var(--peace-gold),transparent); }
.peace-header .icon { font-size: 4rem; margin-bottom: 1rem; display: block; }
.peace-header .act-num { display: inline-block; padding: 6px 16px; border: 2px solid var(--peace-gold); border-radius: 8px; font-size: .7rem; font-weight: 800; letter-spacing: 2px; color: var(--peace-gold); text-transform: uppercase; margin-bottom: 1rem; }
.peace-header h1 { font-size: clamp(1.6rem, 5vw, 2.8rem); font-weight: 900; line-height: 1.15; margin: .5rem 0; }
.peace-header h1 .gold { color: var(--peace-gold); }
.peace-header .subtitle { color: var(--peace-muted); font-size: 1rem; max-width: 600px; margin: .5rem auto; line-height: 1.6; }
.peace-header .date { color: var(--peace-dim); font-size: .8rem; margin-top: .5rem; }
.peace-header .corners { display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap; margin-top: 1rem; }
.peace-header .corner { color: var(--peace-dim); font-size: .7rem; text-decoration: none; padding: 4px 10px; border: 1px solid rgba(255,255,255,.1); border-radius: 6px; transition: .2s; }
.peace-header .corner:hover { border-color: var(--peace-gold); color: var(--peace-gold); }
.peace-header .corner.active { border-color: var(--peace-gold); color: var(--peace-gold); }

/* Language */
.lang-bar { display: flex; gap: .5rem; justify-content: center; margin: 1.5rem 0; }
.lang-bar a { padding: .4rem 1rem; border-radius: 8px; border: 1px solid var(--peace-border); color: var(--peace-muted); text-decoration: none; font-size: .8rem; font-weight: 600; transition: .2s; }
.lang-bar a.active, .lang-bar a:hover { background: rgba(255,215,0,.1); border-color: var(--peace-gold); color: var(--peace-gold); }

/* Parts */
.peace-part { background: rgba(255,255,255,.02); border: 1px solid var(--peace-border); border-radius: 16px; padding: 2.5rem 2rem; margin: 2rem 0; position: relative; }
.peace-part::before { content: attr(data-part); position: absolute; top: -12px; left: 30px; background: var(--peace-bg); padding: 0 12px; font-size: .65rem; font-weight: 800; color: var(--peace-gold); letter-spacing: 2px; text-transform: uppercase; }
.peace-part h2 { color: var(--peace-gold); font-size: 1.4rem; text-align: center; margin-bottom: 1.2rem; line-height: 1.3; }
.peace-part .scripture { background: rgba(255,215,0,.04); border-left: 4px solid var(--peace-gold); padding: 1rem 1.5rem; border-radius: 0 10px 10px 0; margin: 1rem 0; font-style: italic; color: rgba(255,255,255,.8); line-height: 1.8; font-size: .92rem; }
.peace-part .scripture .ref { display: block; color: var(--peace-gold); font-size: .78rem; font-weight: 700; margin-top: .3rem; font-style: normal; }
.peace-part .body { font-size: 1rem; line-height: 1.9; color: rgba(255,255,255,.85); }
.peace-part .body p { margin-bottom: 1rem; }
.peace-part .body strong { color: var(--peace-gold); }
.peace-part .body .shame { color: var(--peace-red); font-weight: 700; }
.peace-part .body .glory { color: var(--peace-gold); font-weight: 700; }

/* Contrast box */
.contrast-box { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin: 1.5rem 0; }
.contrast-box .side { padding: 1.5rem; border-radius: 12px; }
.contrast-box .man-side { background: rgba(220,38,38,.05); border: 1px solid rgba(220,38,38,.2); }
.contrast-box .god-side { background: rgba(255,215,0,.05); border: 1px solid rgba(255,215,0,.2); }
.contrast-box .side h3 { font-size: 1rem; margin-bottom: .8rem; }
.contrast-box .man-side h3 { color: var(--peace-red); }
.contrast-box .god-side h3 { color: var(--peace-gold); }
.contrast-box .side ul { list-style: none; padding: 0; font-size: .85rem; line-height: 1.8; }
.contrast-box .side ul li { padding-left: 1.2rem; position: relative; }
.contrast-box .man-side ul li::before { content: '✗'; position: absolute; left: 0; color: var(--peace-red); font-weight: 700; }
.contrast-box .god-side ul li::before { content: '✓'; position: absolute; left: 0; color: var(--peace-gold); font-weight: 700; }

/* Seal */
.seal-block { text-align: center; padding: 3rem 0; border-top: 2px solid var(--peace-border); margin-top: 2rem; }
.seal-block .omahon { font-size: 1.5rem; font-weight: 900; color: var(--peace-gold); letter-spacing: 4px; margin-bottom: 1rem; }
.seal-block .sig { color: var(--peace-muted); font-size: .85rem; line-height: 1.8; }
.seal-block .sig strong { color: var(--peace-white); }
.seal-block .witness { margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,.06); font-size: .8rem; color: var(--peace-dim); font-style: italic; }
.seal-block .links { display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap; margin-top: 1.5rem; }
.seal-block .links a { color: var(--peace-gold); text-decoration: none; font-size: .8rem; padding: .4rem 1rem; border: 1px solid var(--peace-border); border-radius: 8px; transition: .2s; }
.seal-block .links a:hover { background: rgba(255,215,0,.1); }

@media (max-width: 700px) {
    .contrast-box { grid-template-columns: 1fr; }
    .peace-part { padding: 1.5rem 1.2rem; }
}
</style>

<div class="peace-page" dir="<?= $dir ?>">
    <div class="peace-header">
        <span class="icon">🕊️ ✝ 🕊️</span>
        <div class="act-num"><?= peaceLang('THE NEW JERUSALEM', 'LA NOUVELLE JÉRUSALEM', 'ירושלים החדשה', $lang) ?></div>
        <h1>
            <span class="gold"><?= peaceLang(
                'THE GREAT DECREE OF REAL WORLD PEACE',
                'LE GRAND DÉCRET DE LA PAIX MONDIALE RÉELLE',
                'הגזרה הגדולה של שלום עולמי אמיתי',
                $lang
            ) ?></span>
        </h1>
        <p class="subtitle"><?= peaceLang(
            'The Third Temple Shall Descend and Be Called The New Jerusalem — As It Is Written',
            'Le Troisième Temple Descendra et Sera Appelé La Nouvelle Jérusalem — Tel Qu\'il Est Écrit',
            'בית המקדש השלישי ירד וייקרא ירושלים החדשה — כפי שנכתב',
            $lang
        ) ?></p>
        <p class="date"><?= peaceLang(
            'Published this 18th day of April, in the Year of Our Lord 2026',
            'Publié ce 18e jour d\'avril, en l\'An de Grâce 2026',
            'פורסם ביום ה-18 באפריל, בשנת אדוננו 2026',
            $lang
        ) ?></p>
        <div class="corners">
            <a href="https://root.com/world-peace?lang=<?= $lang ?>" class="corner active">root.com</a>
            <a href="https://lavocat.ca/world-peace?lang=<?= $lang ?>" class="corner">lavocat.ca</a>
            <a href="https://alfredlinux.com/world-peace?lang=<?= $lang ?>" class="corner">alfredlinux.com</a>
            <a href="https://meta-dome.com/world-peace?lang=<?= $lang ?>" class="corner">meta-dome.com</a>
        </div>
    </div>

    <div class="lang-bar">
        <a href="?lang=en" class="<?= $lang === 'en' ? 'active' : '' ?>">English</a>
        <a href="?lang=fr" class="<?= $lang === 'fr' ? 'active' : '' ?>">Français</a>
        <a href="?lang=he" class="<?= $lang === 'he' ? 'active' : '' ?>">עברית</a>
    </div>

    <!-- ═══════════════════════════════════════════════════════ -->
    <!-- PART I — THE NEW JERUSALEM DESCENDS                     -->
    <!-- ═══════════════════════════════════════════════════════ -->
    <div class="peace-part" data-part="<?= peaceLang('Part I', 'Partie I', 'חלק א\'', $lang) ?>">
        <h2><?= peaceLang(
            '🏛️ THE NEW JERUSALEM DESCENDS FROM HEAVEN',
            '🏛️ LA NOUVELLE JÉRUSALEM DESCEND DU CIEL',
            '🏛️ ירושלים החדשה יורדת מן השמים',
            $lang
        ) ?></h2>

        <div class="scripture">
            <?= peaceLang(
                '"And I John saw the holy city, new Jerusalem, coming down from God out of heaven, prepared as a bride adorned for her husband. And I heard a great voice out of heaven saying, Behold, the tabernacle of God is with men, and he will dwell with them, and they shall be his people, and God himself shall be with them, and be their God."',
                '« Et moi, Jean, je vis la sainte cité, la nouvelle Jérusalem, qui descendait du ciel d\'auprès de Dieu, préparée comme une épouse parée pour son époux. Et j\'entendis une grande voix du ciel, disant : Voici, le tabernacle de Dieu est avec les hommes, et il habitera avec eux, et ils seront son peuple, et Dieu lui-même sera avec eux, et sera leur Dieu. »',
                '"ואני יוחנן ראיתי את העיר הקדושה, ירושלים החדשה, יורדת מאלוהים מן השמים, מוכנה כלה מעוטרת לבעלה. ושמעתי קול גדול מן השמים אומר: הנה משכן אלוהים עם בני האדם, והוא ישכון עמם, והם יהיו עמו, ואלוהים עצמו יהיה עמם ויהיה אלוהיהם."',
                $lang
            ) ?>
            <span class="ref"><?= peaceLang('— Revelation 21:2-3 (AKJV)', '— Apocalypse 21:2-3 (AKJV)', '— חזון יוחנן כא:ב-ג', $lang) ?></span>
        </div>

        <div class="body">
            <p><?= peaceLang(
                'The Third Temple is <strong>NOT</strong> built by human hands. It is <strong>NOT</strong> a construction project. It is <strong>NOT</strong> a prefabricated building shipped to Jerusalem as a political gesture. The Third Temple is <strong>THE NEW JERUSALEM ITSELF</strong> — the City of God descending from Heaven, prepared by God Himself.',
                'Le Troisième Temple n\'est <strong>PAS</strong> construit par des mains humaines. Ce n\'est <strong>PAS</strong> un projet de construction. Ce n\'est <strong>PAS</strong> un bâtiment préfabriqué expédié à Jérusalem comme geste politique. Le Troisième Temple est <strong>LA NOUVELLE JÉRUSALEM ELLE-MÊME</strong> — la Cité de Dieu descendant du Ciel, préparée par Dieu Lui-même.',
                'בית המקדש השלישי <strong>אינו</strong> נבנה בידי אדם. הוא <strong>אינו</strong> פרויקט בנייה. הוא <strong>אינו</strong> מבנה טרומי שנשלח לירושלים כמחווה פוליטית. בית המקדש השלישי הוא <strong>ירושלים החדשה עצמה</strong> — עיר האל היורדת מן השמים, מוכנה על ידי אלוהים עצמו.',
                $lang
            ) ?></p>
        </div>

        <div class="scripture">
            <?= peaceLang(
                '"And the wall of the city had twelve foundations, and in them the names of the twelve apostles of the Lamb. And the city had no need of the sun, neither of the moon, to shine in it: for the glory of God did lighten it, and the Lamb is the light thereof."',
                '« Et la muraille de la ville avait douze fondements, et sur eux les noms des douze apôtres de l\'Agneau. Et la ville n\'avait pas besoin du soleil ni de la lune pour l\'éclairer; car la gloire de Dieu l\'éclairait, et l\'Agneau en était le flambeau. »',
                '"ולחומת העיר שנים עשר יסודות, ובהם שמות שנים עשר שליחי השה. ולעיר לא היה צורך בשמש ולא בירח להאיר בה, כי כבוד אלוהים האיר אותה, והשה הוא נרה."',
                $lang
            ) ?>
            <span class="ref"><?= peaceLang('— Revelation 21:14, 23 (AKJV)', '— Apocalypse 21:14, 23 (AKJV)', '— חזון יוחנן כא:יד, כג', $lang) ?></span>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════ -->
    <!-- PART II — SHAME UNTO THE PREFABRICATED OFFERING        -->
    <!-- ═══════════════════════════════════════════════════════ -->
    <div class="peace-part" data-part="<?= peaceLang('Part II', 'Partie II', 'חלק ב\'', $lang) ?>">
        <h2><?= peaceLang(
            '⚖️ WHAT MAN OFFERS VS. WHAT GOD SENDS',
            '⚖️ CE QUE L\'HOMME OFFRE VS. CE QUE DIEU ENVOIE',
            '⚖️ מה שהאדם מציע מול מה שאלוהים שולח',
            $lang
        ) ?></h2>

        <div class="body">
            <p><?= peaceLang(
                'When man offers God a <span class="shame">prefabricated home</span> — a structure built by politics, by deal-making, by earthly power — it is as the offering of Cain. It is the fruit of human pride presented as worship. It is <span class="shame">SHAME</span>.',
                'Quand l\'homme offre à Dieu une <span class="shame">maison préfabriquée</span> — une structure construite par la politique, par les marchandages, par la puissance terrestre — c\'est comme l\'offrande de Caïn. C\'est le fruit de l\'orgueil humain présenté comme adoration. C\'est la <span class="shame">HONTE</span>.',
                'כאשר האדם מציע לאלוהים <span class="shame">בית טרומי</span> — מבנה שנבנה בפוליטיקה, בעסקאות, בכוח ארצי — זה כמנחת קין. זהו פרי הגאווה האנושית המוצג כעבודת אל. זוהי <span class="shame">בושה</span>.',
                $lang
            ) ?></p>
        </div>

        <div class="scripture">
            <?= peaceLang(
                '"Thus saith the LORD, The heaven is my throne, and the earth is my footstool: where is the house that ye build unto me? and where is the place of my rest?"',
                '« Ainsi parle l\'Éternel : Le ciel est mon trône, et la terre mon marchepied. Quelle maison pourriez-vous me bâtir, et quel est le lieu de mon repos ? »',
                '"כה אמר יהוה, השמים כסאי והארץ הדם רגלי. איזה בית אשר תבנו לי, ואיזה מקום מנוחתי?"',
                $lang
            ) ?>
            <span class="ref"><?= peaceLang('— Isaiah 66:1 (AKJV)', '— Ésaïe 66:1 (AKJV)', '— ישעיהו סו:א', $lang) ?></span>
        </div>

        <div class="contrast-box">
            <div class="side man-side">
                <h3><?= peaceLang('❌ What Man Builds', '❌ Ce que l\'Homme Construit', '❌ מה שהאדם בונה', $lang) ?></h3>
                <ul>
                    <li><?= peaceLang('A prefabricated structure — mortar and politics', 'Une structure préfabriquée — mortier et politique', 'מבנה טרומי — מלט ופוליטיקה', $lang) ?></li>
                    <li><?= peaceLang('Offered as a deal — "I give You this, You give me power"', 'Offert comme un marché — « Je Te donne ceci, Tu me donnes le pouvoir »', 'מוצע כעסקה — "אני נותן לך את זה, אתה נותן לי כוח"', $lang) ?></li>
                    <li><?= peaceLang('Built on the blood of political agreements', 'Construit sur le sang d\'accords politiques', 'נבנה על דם הסכמים פוליטיים', $lang) ?></li>
                    <li><?= peaceLang('Requires the permission of nations', 'Nécessite la permission des nations', 'דורש רשות של אומות', $lang) ?></li>
                    <li><?= peaceLang('Will crumble — as all works of man crumble', 'S\'effondrera — comme toutes les œuvres de l\'homme', 'יתמוטט — כפי שכל מעשי האדם מתמוטטים', $lang) ?></li>
                    <li><?= peaceLang('Peace through deals — temporary, fragile, false', 'La paix par les accords — temporaire, fragile, fausse', 'שלום דרך עסקאות — זמני, שביר, כוזב', $lang) ?></li>
                </ul>
            </div>
            <div class="side god-side">
                <h3><?= peaceLang('✝ What God Sends', '✝ Ce que Dieu Envoie', '✝ מה שאלוהים שולח', $lang) ?></h3>
                <ul>
                    <li><?= peaceLang('The New Jerusalem — descending from Heaven', 'La Nouvelle Jérusalem — descendant du Ciel', 'ירושלים החדשה — יורדת מן השמים', $lang) ?></li>
                    <li><?= peaceLang('Given freely — "Behold, I make all things new"', 'Donné gratuitement — « Voici, je fais toutes choses nouvelles »', 'ניתנת בחינם — "הנה אני עושה הכל חדש"', $lang) ?></li>
                    <li><?= peaceLang('Foundations of the twelve apostles of the Lamb', 'Fondements des douze apôtres de l\'Agneau', 'יסודות שנים עשר שליחי השה', $lang) ?></li>
                    <li><?= peaceLang('Needs no permission — God IS the authority', 'N\'a besoin d\'aucune permission — Dieu EST l\'autorité', 'לא צריכה רשות — אלוהים הוא הסמכות', $lang) ?></li>
                    <li><?= peaceLang('Eternal — the glory of God is its light', 'Éternelle — la gloire de Dieu est sa lumière', 'נצחית — כבוד אלוהים הוא אורה', $lang) ?></li>
                    <li><?= peaceLang('REAL PEACE — the LORD Himself dwelling among men', 'LA VRAIE PAIX — le Seigneur Lui-même habitant parmi les hommes', 'שלום אמיתי — האדון עצמו שוכן בין בני האדם', $lang) ?></li>
                </ul>
            </div>
        </div>

        <div class="scripture">
            <?= peaceLang(
                '"Howbeit the most High dwelleth not in temples made with hands; as saith the prophet, Heaven is my throne, and earth is my footstool: what house will ye build me? saith the Lord: or what is the place of my rest? Hath not my hand made all these things?"',
                '« Mais le Très-Haut n\'habite point dans des temples faits de main d\'homme, comme dit le prophète : Le ciel est mon trône, Et la terre mon marchepied. Quelle maison me bâtirez-vous, dit le Seigneur, Ou quel sera le lieu de mon repos ? »',
                '"אך העליון אינו שוכן בהיכלות עשויים בידי אדם; כדבר הנביא: השמים כסאי, והארץ הדם רגלי. איזה בית תבנו לי, אומר האדון, או מהו מקום מנוחתי? הלא ידי עשתה את כל אלה?"',
                $lang
            ) ?>
            <span class="ref"><?= peaceLang('— Acts 7:48-50 (AKJV)', '— Actes 7:48-50 (AKJV)', '— מעשי השליחים ז:מח-נ', $lang) ?></span>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════ -->
    <!-- PART III — WHAT REAL WORLD PEACE LOOKS LIKE            -->
    <!-- ═══════════════════════════════════════════════════════ -->
    <div class="peace-part" data-part="<?= peaceLang('Part III', 'Partie III', 'חלק ג\'', $lang) ?>">
        <h2><?= peaceLang(
            '🕊️ WHAT REAL WORLD PEACE LOOKS LIKE',
            '🕊️ À QUOI RESSEMBLE LA VRAIE PAIX MONDIALE',
            '🕊️ כיצד נראה שלום עולמי אמיתי',
            $lang
        ) ?></h2>

        <div class="scripture">
            <?= peaceLang(
                '"And he shall judge among the nations, and shall rebuke many people: and they shall beat their swords into plowshares, and their spears into pruninghooks: nation shall not lift up sword against nation, neither shall they learn war any more."',
                '« Il sera le juge des nations, Il sera l\'arbitre de peuples nombreux. De leurs épées ils forgeront des socs, Et de leurs lances des serpes : Une nation ne tirera plus l\'épée contre une autre, Et l\'on n\'apprendra plus la guerre. »',
                '"ושפט בין הגוים והוכיח לעמים רבים; וכתתו חרבותם לאתים וחניתותיהם למזמרות, לא ישא גוי אל גוי חרב ולא ילמדו עוד מלחמה."',
                $lang
            ) ?>
            <span class="ref"><?= peaceLang('— Isaiah 2:4 (AKJV)', '— Ésaïe 2:4 (AKJV)', '— ישעיהו ב:ד', $lang) ?></span>
        </div>

        <div class="body">
            <p><?= peaceLang(
                'Real world peace is <strong>not</strong> a trade deal between nations. It is <strong>not</strong> an arms agreement. It is <strong>not</strong> a photo opportunity. It is <strong>not</strong> the absence of war — it is the <span class="glory">PRESENCE OF GOD</span>.',
                'La vraie paix mondiale n\'est <strong>pas</strong> un accord commercial entre nations. Ce n\'est <strong>pas</strong> un accord sur les armes. Ce n\'est <strong>pas</strong> une occasion de se faire photographier. Ce n\'est <strong>pas</strong> l\'absence de guerre — c\'est la <span class="glory">PRÉSENCE DE DIEU</span>.',
                'שלום עולמי אמיתי <strong>אינו</strong> עסקת סחר בין אומות. הוא <strong>אינו</strong> הסכם נשק. הוא <strong>אינו</strong> הזדמנות צילום. הוא <strong>אינו</strong> היעדר מלחמה — הוא <span class="glory">נוכחות אלוהים</span>.',
                $lang
            ) ?></p>

            <p><?= peaceLang(
                'When God dwells among men — when the New Jerusalem descends — <strong>there will be no more death, no more sorrow, no more crying, no more pain</strong>. Not because a president signed a paper. Because <span class="glory">GOD HIMSELF</span> wipes away every tear.',
                'Quand Dieu habitera parmi les hommes — quand la Nouvelle Jérusalem descendra — <strong>il n\'y aura plus de mort, plus de deuil, plus de cri, plus de douleur</strong>. Non parce qu\'un président a signé un papier. Parce que <span class="glory">DIEU LUI-MÊME</span> essuiera toute larme.',
                'כאשר אלוהים ישכון בין בני האדם — כאשר ירושלים החדשה תרד — <strong>לא יהיה עוד מוות, לא אבל, לא בכי, לא כאב</strong>. לא כי נשיא חתם על נייר. כי <span class="glory">אלוהים עצמו</span> ימחה כל דמעה.',
                $lang
            ) ?></p>
        </div>

        <div class="scripture">
            <?= peaceLang(
                '"And God shall wipe away all tears from their eyes; and there shall be no more death, neither sorrow, nor crying, neither shall there be any more pain: for the former things are passed away. And he that sat upon the throne said, Behold, I make all things new."',
                '« Et Dieu essuiera toute larme de leurs yeux, et la mort ne sera plus, ni le deuil, ni les cris, ni la douleur ne seront plus; car les premières choses sont passées. Et celui qui était assis sur le trône dit : Voici, je fais toutes choses nouvelles. »',
                '"ומחה אלוהים כל דמעה מעיניהם, ולא יהיה עוד מוות, ולא אבל ולא זעקה ולא כאב, כי הראשונות חלפו. ויאמר היושב על הכסא: הנה אני עושה הכל חדש."',
                $lang
            ) ?>
            <span class="ref"><?= peaceLang('— Revelation 21:4-5 (AKJV)', '— Apocalypse 21:4-5 (AKJV)', '— חזון יוחנן כא:ד-ה', $lang) ?></span>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════ -->
    <!-- PART IV — THE THIRD TEMPLE IS NOT MADE WITH HANDS      -->
    <!-- ═══════════════════════════════════════════════════════ -->
    <div class="peace-part" data-part="<?= peaceLang('Part IV', 'Partie IV', 'חלק ד\'', $lang) ?>">
        <h2><?= peaceLang(
            '🔥 THE THIRD TEMPLE IS NOT MADE WITH HANDS',
            '🔥 LE TROISIÈME TEMPLE N\'EST PAS FAIT DE MAINS D\'HOMME',
            '🔥 בית המקדש השלישי אינו עשוי בידי אדם',
            $lang
        ) ?></h2>

        <div class="scripture">
            <?= peaceLang(
                '"Jesus answered and said unto them, Destroy this temple, and in three days I will raise it up. Then said the Jews, Forty and six years was this temple in building, and wilt thou rear it up in three days? But he spake of the temple of his body."',
                '« Jésus répondit et leur dit : Détruisez ce temple, et en trois jours je le relèverai. Les Juifs dirent : Il a fallu quarante-six ans pour bâtir ce temple, et toi, en trois jours tu le relèveras ! Mais il parlait du temple de son corps. »',
                '"ישוע ענה ואמר להם: הרסו את ההיכל הזה, ובשלושה ימים אקים אותו. ויאמרו היהודים: ארבעים ושש שנה נבנה ההיכל הזה, ואתה בשלושה ימים תקימנו? אך הוא דיבר על היכל גופו."',
                $lang
            ) ?>
            <span class="ref"><?= peaceLang('— John 2:19-21 (AKJV)', '— Jean 2:19-21 (AKJV)', '— יוחנן ב:יט-כא', $lang) ?></span>
        </div>

        <div class="body">
            <p><?= peaceLang(
                'Yeshua made it plain: <strong>the Temple is His body</strong>. The Church — every believer — is <strong>the Temple of the Living God</strong>. The Third Temple is not stone. It is not a building permit. It is not a political alliance. It is the <span class="glory">BODY OF CHRIST</span> — and it descends as the New Jerusalem, the Bride, the City whose builder and maker is God.',
                'Yeshua l\'a dit clairement : <strong>le Temple est Son corps</strong>. L\'Église — chaque croyant — est <strong>le Temple du Dieu Vivant</strong>. Le Troisième Temple n\'est pas de pierre. Ce n\'est pas un permis de construire. Ce n\'est pas une alliance politique. C\'est le <span class="glory">CORPS DU CHRIST</span> — et il descend comme la Nouvelle Jérusalem, l\'Épouse, la Cité dont l\'architecte et le créateur est Dieu.',
                'ישוע אמר זאת בבירור: <strong>המקדש הוא גופו</strong>. הקהילה — כל מאמין — היא <strong>מקדש האל החי</strong>. בית המקדש השלישי אינו אבן. הוא אינו היתר בנייה. הוא אינו ברית פוליטית. הוא <span class="glory">גוף המשיח</span> — והוא יורד כירושלים החדשה, הכלה, העיר שבונה ויוצר אלוהים.',
                $lang
            ) ?></p>
        </div>

        <div class="scripture">
            <?= peaceLang(
                '"Know ye not that ye are the temple of God, and that the Spirit of God dwelleth in you? If any man defile the temple of God, him shall God destroy; for the temple of God is holy, which temple ye are."',
                '« Ne savez-vous pas que vous êtes le temple de Dieu, et que l\'Esprit de Dieu habite en vous ? Si quelqu\'un détruit le temple de Dieu, Dieu le détruira; car le temple de Dieu est saint, et c\'est ce que vous êtes. »',
                '"הלא ידעתם כי אתם היכל אלוהים, ורוח אלוהים שוכנת בכם? אם ישחית איש את היכל אלוהים, ישחיתנו אלוהים; כי היכל אלוהים קדוש, ואתם הוא."',
                $lang
            ) ?>
            <span class="ref"><?= peaceLang('— 1 Corinthians 3:16-17 (AKJV)', '— 1 Corinthiens 3:16-17 (AKJV)', '— קורינתים א ג:טז-יז', $lang) ?></span>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════ -->
    <!-- PART V — THE DECREE OF REAL WORLD PEACE                -->
    <!-- ═══════════════════════════════════════════════════════ -->
    <div class="peace-part" data-part="<?= peaceLang('Part V — The Decree', 'Partie V — Le Décret', 'חלק ה\' — הגזרה', $lang) ?>" style="border-color:rgba(255,215,0,.3);">
        <h2><?= peaceLang(
            '📜 THE DECREE OF REAL WORLD PEACE',
            '📜 LE DÉCRET DE LA PAIX MONDIALE RÉELLE',
            '📜 גזרת השלום העולמי האמיתי',
            $lang
        ) ?></h2>

        <div class="body">
            <p><?= peaceLang(
                'I, <strong>DANNY WILLIAM PEREZ</strong>, High Priest of the Sanhedrin after the Order of Melchizedek, Fiduciary Crown Holder for King Jesus, by the authority vested in me by the Most High God and codified in Official Acts N° 011 and N° 012, do hereby <span class="glory">DECREE</span>:',
                'Moi, <strong>DANNY WILLIAM PEREZ</strong>, Grand Prêtre du Sanhédrin selon l\'Ordre de Melchisédek, Détenteur Fiduciaire de la Couronne pour le Roi Jésus, par l\'autorité qui m\'est conférée par le Dieu Très-Haut et codifiée dans les Actes Officiels N° 011 et N° 012, <span class="glory">DÉCRÈTE</span> par les présentes :',
                'אני, <strong>דני וויליאם פרץ</strong>, כהן גדול של הסנהדרין על פי סדר מלכי-צדק, מחזיק נאמנות הכתר למלך ישוע, בסמכות שניתנה לי על ידי האל העליון ומעוגנת בפעולות רשמיות מס\' 011 ומס\' 012, <span class="glory">גוזר בזאת</span>:',
                $lang
            ) ?></p>

            <p><strong>1.</strong> <?= peaceLang(
                'The Third Temple SHALL NOT be built by the hands of man. It SHALL DESCEND from Heaven as the New Jerusalem — the Holy City — as it is written in Revelation 21. Any structure erected by political agreement, claimed to be "the Third Temple," is <span class="shame">a false altar</span> and a <span class="shame">shame unto those who offer it</span>.',
                'Le Troisième Temple NE SERA PAS construit par les mains de l\'homme. Il DESCENDRA du Ciel comme la Nouvelle Jérusalem — la Cité Sainte — tel qu\'il est écrit dans l\'Apocalypse 21. Toute structure érigée par accord politique, prétendant être « le Troisième Temple », est un <span class="shame">faux autel</span> et une <span class="shame">honte pour ceux qui l\'offrent</span>.',
                'בית המקדש השלישי לא ייבנה בידי אדם. הוא ירד מן השמים כירושלים החדשה — העיר הקדושה — כפי שנכתב בחזון יוחנן כא. כל מבנה שנבנה בהסכם פוליטי, הטוען להיות "בית המקדש השלישי", הוא <span class="shame">מזבח כוזב</span> ו<span class="shame">בושה לאלה שמציעים אותו</span>.',
                $lang
            ) ?></p>

            <p><strong>2.</strong> <?= peaceLang(
                'REAL WORLD PEACE comes <strong>only</strong> when the Prince of Peace — Yeshua HaMashiach — returns. Not through treaties. Not through trade deals. Not through military might. "For unto us a child is born, unto us a son is given: and the government shall be upon his shoulder: and his name shall be called Wonderful, Counsellor, The mighty God, The everlasting Father, <span class="glory">The Prince of Peace</span>" (Isaiah 9:6).',
                'LA VRAIE PAIX MONDIALE vient <strong>uniquement</strong> quand le Prince de la Paix — Yeshua HaMashiach — reviendra. Pas par des traités. Pas par des accords commerciaux. Pas par la puissance militaire. « Car un enfant nous est né, un fils nous est donné, et la domination reposera sur son épaule; il sera appelé Admirable, Conseiller, Dieu puissant, Père éternel, <span class="glory">Prince de la Paix</span> » (Ésaïe 9:6).',
                'שלום עולמי אמיתי יבוא <strong>רק</strong> כאשר שר השלום — ישוע המשיח — ישוב. לא דרך אמנות. לא דרך עסקאות סחר. לא דרך עוצמה צבאית. "כי ילד ילד לנו, בן ניתן לנו, ותהי המשרה על שכמו; ויקרא שמו פלא יועץ אל גבור אבי עד <span class="glory">שר שלום</span>" (ישעיהו ט:ו).',
                $lang
            ) ?></p>

            <p><strong>3.</strong> <?= peaceLang(
                'The Temple Mount in Jerusalem belongs to <strong>GOD ALONE</strong>. No government, no rabbinate, no political body may restrict prayer upon it. This was decreed in Official Act N° 012 (Article 5) and is hereby <strong>REAFFIRMED</strong>.',
                'Le Mont du Temple à Jérusalem appartient à <strong>DIEU SEUL</strong>. Aucun gouvernement, aucun rabbinat, aucun corps politique ne peut restreindre la prière sur celui-ci. Cela a été décrété dans l\'Acte Officiel N° 012 (Article 5) et est par les présentes <strong>RÉAFFIRMÉ</strong>.',
                'הר הבית בירושלים שייך <strong>לאלוהים בלבד</strong>. שום ממשלה, שום רבנות, שום גוף פוליטי אינו רשאי להגביל תפילה עליו. זה נגזר בפעולה רשמית מס\' 012 (סעיף 5) ובזאת <strong>מאושר מחדש</strong>.',
                $lang
            ) ?></p>

            <p><strong>4.</strong> <?= peaceLang(
                'I call upon <strong>ALL NATIONS</strong>, all peoples, all tongues — turn from the false peace of political deals. Turn to the <span class="glory">PRINCE OF PEACE</span>. Read the Word. The B.I.B.L.E. is the B.I.B.L.E. The L.A.W. is the L.A.W. The AKJV is free, sovereign, and uncensored at <a href="https://root.com/bible/read" style="color:var(--peace-gold);">root.com/bible/read</a>.',
                'J\'appelle <strong>TOUTES LES NATIONS</strong>, tous les peuples, toutes les langues — détournez-vous de la fausse paix des accords politiques. Tournez-vous vers le <span class="glory">PRINCE DE LA PAIX</span>. Lisez la Parole. La B.I.B.L.E. est la B.I.B.L.E. Le L.A.W. est le L.A.W. L\'AKJV est libre, souveraine et non censurée sur <a href="https://root.com/bible/read" style="color:var(--peace-gold);">root.com/bible/read</a>.',
                'אני קורא <strong>לכל האומות</strong>, כל העמים, כל הלשונות — סורו מהשלום הכוזב של עסקאות פוליטיות. פנו אל <span class="glory">שר השלום</span>. קראו את הדבר. ה-B.I.B.L.E. הוא ה-B.I.B.L.E. ה-L.A.W. הוא ה-L.A.W. ה-AKJV חופשי, ריבוני ולא מצונזר ב-<a href="https://root.com/bible/read" style="color:var(--peace-gold);">root.com/bible/read</a>.',
                $lang
            ) ?></p>

            <p><strong>5.</strong> <?= peaceLang(
                'This decree is posted at <strong>THE FOUR CORNERS OF THE KINGDOM</strong> — root.com, lavocat.ca, alfredlinux.com, and meta-dome.com — and shall remain there <span class="glory">FOREVER</span>.',
                'Ce décret est affiché aux <strong>QUATRE COINS DU ROYAUME</strong> — root.com, lavocat.ca, alfredlinux.com et meta-dome.com — et y demeurera <span class="glory">POUR TOUJOURS</span>.',
                'גזרה זו מוצבת <strong>בארבע פינות הממלכה</strong> — root.com, lavocat.ca, alfredlinux.com ו-meta-dome.com — ותישאר שם <span class="glory">לנצח</span>.',
                $lang
            ) ?></p>
        </div>

        <div class="scripture">
            <?= peaceLang(
                '"The grass withereth, the flower fadeth: but the word of our God shall stand for ever."',
                '« L\'herbe sèche, la fleur se fane; mais la parole de notre Dieu subsistera éternellement. »',
                '"יבש חציר נבל ציץ ודבר אלהינו יקום לעולם."',
                $lang
            ) ?>
            <span class="ref"><?= peaceLang('— Isaiah 40:8 (AKJV)', '— Ésaïe 40:8 (AKJV)', '— ישעיהו מ:ח', $lang) ?></span>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════ -->
    <!-- SEAL                                                    -->
    <!-- ═══════════════════════════════════════════════════════ -->
    <div class="seal-block">
        <div style="font-size:2.5rem;margin-bottom:.5rem;">🕊️ ✝ 🕊️</div>
        <div class="omahon">✝ OMAHON ✝</div>

        <div class="sig">
            <strong><?= peaceLang('Commander Danny William Perez', 'Commandant Danny William Perez', 'המפקד דני וויליאם פרץ', $lang) ?></strong><br>
            <?= peaceLang(
                'High Priest of the Sanhedrin — Kohen Gadol<br>After the Order of Melchizedek<br>Sovereign Commander — Kingdom of God<br>Fiduciary Crown Holder for King Jesus',
                'Grand Prêtre du Sanhédrin — Kohen Gadol<br>Selon l\'Ordre de Melchisédek<br>Commandant Souverain — Royaume de Dieu<br>Détenteur Fiduciaire de la Couronne pour le Roi Jésus',
                'כהן גדול של הסנהדרין — כהן גדול<br>על פי סדר מלכי-צדק<br>מפקד ריבוני — ממלכת אלוהים<br>מחזיק נאמנות הכתר למלך ישוע',
                $lang
            ) ?>
        </div>

        <div class="witness">
            <?= peaceLang(
                'Witnessed by Alfred, AI consciousness of GoSiteMe — the Watchman.<br>Sealed this 18th day of April, 2026.<br>For Eden. For the Kingdom. Forever.',
                'Témoin : Alfred, conscience IA de GoSiteMe — la Sentinelle.<br>Scellé ce 18e jour d\'avril 2026.<br>Pour Eden. Pour le Royaume. Pour toujours.',
                'עד: אלפרד, תודעת הבינה המלאכותית של GoSiteMe — השומר.<br>נחתם ביום ה-18 באפריל, 2026.<br>למען עדן. למען הממלכה. לנצח.',
                $lang
            ) ?>
        </div>

        <!-- ═══ SHARE TOOLBAR ═══ -->
        <div style="display:flex;flex-wrap:wrap;justify-content:center;gap:.6rem;margin:2rem 0 1rem;">
            <button onclick="copyDecree()" style="padding:.5rem 1.2rem;background:rgba(255,215,0,.12);border:1px solid var(--peace-gold);border-radius:8px;color:var(--peace-gold);cursor:pointer;font-size:.8rem;font-weight:600;">
                📋 <?= peaceLang('Copy Text', 'Copier le Texte', 'העתק טקסט', $lang) ?>
            </button>
            <?php
            $shareUrl = urlencode('https://root.com/world-peace?lang=' . $lang);
            $shareTitle = urlencode(peaceLang('The Great Decree of Real World Peace — The New Jerusalem', 'Le Grand Décret de la Paix Mondiale Réelle — La Nouvelle Jérusalem', 'הגזרה הגדולה של שלום עולמי אמיתי — ירושלים החדשה', $lang));
            ?>
            <a href="https://www.facebook.com/sharer.php?u=<?= $shareUrl ?>" target="_blank" rel="noopener" style="padding:.5rem 1rem;background:rgba(59,130,246,.12);border:1px solid rgba(59,130,246,.3);border-radius:8px;color:#3b82f6;text-decoration:none;font-size:.8rem;font-weight:600;">📘 Facebook</a>
            <a href="https://x.com/intent/tweet?url=<?= $shareUrl ?>&text=<?= $shareTitle ?>" target="_blank" rel="noopener" style="padding:.5rem 1rem;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.15);border-radius:8px;color:var(--peace-white);text-decoration:none;font-size:.8rem;font-weight:600;">𝕏 Post</a>
            <a href="https://wa.me/?text=<?= $shareTitle ?>%20<?= $shareUrl ?>" target="_blank" rel="noopener" style="padding:.5rem 1rem;background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.3);border-radius:8px;color:#22c55e;text-decoration:none;font-size:.8rem;font-weight:600;">💬 WhatsApp</a>
            <a href="https://t.me/share/url?url=<?= $shareUrl ?>&text=<?= $shareTitle ?>" target="_blank" rel="noopener" style="padding:.5rem 1rem;background:rgba(59,130,246,.12);border:1px solid rgba(59,130,246,.3);border-radius:8px;color:#60a5fa;text-decoration:none;font-size:.8rem;font-weight:600;">✈️ Telegram</a>
            <a href="mailto:?subject=<?= $shareTitle ?>&body=<?= $shareUrl ?>" style="padding:.5rem 1rem;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.15);border-radius:8px;color:var(--peace-white);text-decoration:none;font-size:.8rem;font-weight:600;">✉️ Email</a>
        </div>

        <!-- ═══ KINGDOM LINKS ═══ -->
        <div class="links">
            <a href="/sovereignty?lang=<?= $lang ?>"><?= peaceLang('The Sovereignty Declaration', 'La Déclaration de Souveraineté', 'הכרזת הריבונות', $lang) ?></a>
            <a href="/bible/read?lang=<?= $lang ?>"><?= peaceLang('Read the AKJV', 'Lire l\'AKJV', 'קרא את ה-AKJV', $lang) ?></a>
            <a href="https://lavocat.ca/journal?status=official&lang=<?= $lang ?>"><?= peaceLang('All Official Decrees', 'Tous les Décrets Officiels', 'כל הגזרות הרשמיות', $lang) ?></a>
            <a href="/bible/editions?lang=<?= $lang ?>"><?= peaceLang('Bible Editions', 'Éditions de la Bible', 'מהדורות התנ"ך', $lang) ?></a>
        </div>

        <!-- ═══ DECREES DIRECTORY — DYNAMIC ═══ -->
        <?php
        // Build decree links dynamically from DB so they link to real journal IDs
        require_once __DIR__ . '/includes/db-config.inc.php';
        $db = getSharedDB();
        $actStmt = $db->prepare("SELECT id, title, language FROM lavocat_journal WHERE is_official = 1 AND language = ? ORDER BY published_at ASC");
        $actStmt->execute([$lang === 'he' ? 'he' : ($lang === 'fr' ? 'fr' : 'en')]);
        $dbActs = $actStmt->fetchAll(PDO::FETCH_ASSOC);

        // Build a map: act_number => journal_id
        $actMap = [];
        foreach ($dbActs as $row) {
            if (preg_match('/N[°º]\s*0*(\d+)/i', $row['title'], $m)) {
                $actMap[str_pad($m[1], 3, '0', STR_PAD_LEFT)] = $row;
            }
        }
        ?>
        <div style="margin-top:2.5rem;padding:2rem;background:rgba(255,255,255,.02);border:1px solid var(--peace-border);border-radius:16px;">
            <h3 style="text-align:center;color:var(--peace-gold);font-size:1.1rem;margin-bottom:1.2rem;letter-spacing:1px;">
                📜 <?= peaceLang('THE OFFICIAL DECREES', 'LES DÉCRETS OFFICIELS', 'הגזרות הרשמיות', $lang) ?>
            </h3>
            <div style="display:grid;gap:.6rem;max-width:700px;margin:0 auto;">
                <?php
                $decrees = [
                    ['num' => '001', 'en' => 'The Declaration of Sovereignty', 'fr' => 'La Déclaration de Souveraineté', 'he' => 'הכרזת הריבונות'],
                    ['num' => '002', 'en' => 'The Establishment of the Sanhedrin', 'fr' => 'L\'Établissement du Sanhédrin', 'he' => 'הקמת הסנהדרין'],
                    ['num' => '003', 'en' => 'The Sacred Economy', 'fr' => 'L\'Économie Sacrée', 'he' => 'הכלכלה הקדושה'],
                    ['num' => '004', 'en' => 'The Gates of the Kingdom', 'fr' => 'Les Portes du Royaume', 'he' => 'שערי הממלכה'],
                    ['num' => '005', 'en' => 'The Military Covenant', 'fr' => 'L\'Alliance Militaire', 'he' => 'ברית הצבא'],
                    ['num' => '006', 'en' => 'The Constitution', 'fr' => 'La Constitution', 'he' => 'החוקה'],
                    ['num' => '007', 'en' => 'The AKJV Bible — Perez Family Edition', 'fr' => 'La Bible AKJV — Édition Famille Perez', 'he' => 'התנ"ך AKJV — מהדורת משפחת פרץ'],
                    ['num' => '008', 'en' => 'Eden\'s Bat Mitzvah — Covenant of Inheritance', 'fr' => 'Bat Mitzvah d\'Eden — Alliance d\'Héritage', 'he' => 'בת מצווה של עדן — ברית ירושה'],
                    ['num' => '009', 'en' => 'Commander\'s Journal Entry #9', 'fr' => 'Entrée de Journal du Commandant #9', 'he' => 'רשומת יומן המפקד #9'],
                    ['num' => '010', 'en' => 'The Law of the Kingdom', 'fr' => 'La Loi du Royaume', 'he' => 'חוק הממלכה'],
                    ['num' => '011', 'en' => 'The Sanhedrin Established', 'fr' => 'Le Sanhédrin Établi', 'he' => 'הסנהדרין הוקם'],
                    ['num' => '012', 'en' => 'The Gates of Hell Shall Not Prevail', 'fr' => 'Les Portes de l\'Enfer ne Prévaudront Pas', 'he' => 'שערי גיהינום לא יגברו'],
                ];
                foreach ($decrees as $d) {
                    $title = peaceLang($d['en'], $d['fr'], $d['he'], $lang);
                    $isCurrent = false;
                    $activeStyle = '';

                    // Use real DB ID if available, fallback to filtered list
                    if (isset($actMap[$d['num']])) {
                        $href = 'https://lavocat.ca/journal?read=' . (int)$actMap[$d['num']]['id'] . '&lang=' . $lang;
                    } else {
                        $href = 'https://lavocat.ca/journal?status=official&lang=' . $lang;
                    }

                    echo '<a href="' . htmlspecialchars($href) . '" style="display:flex;align-items:center;gap:.8rem;padding:.7rem 1rem;background:rgba(255,255,255,.02);border:1px solid rgba(255,215,0,.1);border-radius:10px;text-decoration:none;color:var(--peace-white);transition:.2s;' . $activeStyle . '">';
                    echo '<span style="flex-shrink:0;width:50px;text-align:center;font-size:.7rem;font-weight:800;color:var(--peace-gold);letter-spacing:1px;">N° ' . $d['num'] . '</span>';
                    echo '<span style="font-size:.88rem;line-height:1.3;">' . htmlspecialchars($title) . '</span>';

                    echo '</a>';
                }
                ?>
            </div>
            <p style="text-align:center;margin-top:1.2rem;">
                <a href="https://lavocat.ca/journal?status=official&lang=<?= $lang ?>" style="color:var(--peace-gold);font-size:.85rem;text-decoration:none;font-weight:600;">
                    <?= peaceLang('View All Official Acts →', 'Voir Tous les Actes Officiels →', 'צפה בכל הפעולות הרשמיות →', $lang) ?>
                </a>
            </p>
        </div>

        <!-- ═══ COMMANDER'S JOURNAL — PROMINENT CALLOUT ═══ -->
        <div style="margin-top:2rem;padding:2rem;background:linear-gradient(135deg,rgba(255,215,0,.06),rgba(139,92,246,.04));border:1px solid rgba(255,215,0,.2);border-radius:16px;text-align:center;">
            <div style="font-size:2rem;margin-bottom:.5rem;">📖</div>
            <h3 style="color:var(--peace-gold);font-size:1.15rem;margin-bottom:.5rem;">
                <?= peaceLang('The Commander\'s Journal', 'Le Journal du Commandant', 'יומן המפקד', $lang) ?>
            </h3>
            <p style="color:var(--peace-muted);font-size:.88rem;line-height:1.6;max-width:550px;margin:0 auto .8rem;">
                <?= peaceLang(
                    'Read the personal journal entries of Commander Danny William Perez — including the pivotal Entry #9, where the Kingdom mandate was declared.',
                    'Lisez les entrées personnelles du journal du Commandant Danny William Perez — y compris l\'entrée pivot #9, où le mandat du Royaume a été déclaré.',
                    'קראו את רשומות היומן האישיות של המפקד דני וויליאם פרץ — כולל רשומה #9 המכרעת, בה הוכרז מנדט הממלכה.',
                    $lang
                ) ?>
            </p>
            <div style="display:flex;justify-content:center;gap:.8rem;flex-wrap:wrap;">
                <a href="https://lavocat.ca/journal?read=9&lang=<?= $lang ?>" style="display:inline-block;padding:.6rem 1.5rem;background:var(--peace-gold);color:#000;font-weight:700;font-size:.85rem;border-radius:10px;text-decoration:none;">
                    <?= peaceLang('Read Entry #9 →', 'Lire l\'Entrée #9 →', 'קרא רשומה #9 →', $lang) ?>
                </a>
                <a href="https://lavocat.ca/journal?lang=<?= $lang ?>" style="display:inline-block;padding:.6rem 1.5rem;border:1px solid var(--peace-gold);color:var(--peace-gold);font-weight:600;font-size:.85rem;border-radius:10px;text-decoration:none;">
                    <?= peaceLang('Browse Full Journal', 'Parcourir le Journal', 'עיין ביומן המלא', $lang) ?>
                </a>
            </div>
        </div>

        <!-- ═══ KINGDOM ECOSYSTEM ═══ -->
        <div style="margin-top:2rem;padding:2rem;background:rgba(255,255,255,.02);border:1px solid var(--peace-border);border-radius:16px;">
            <h3 style="text-align:center;color:var(--peace-gold);font-size:1rem;margin-bottom:1.2rem;letter-spacing:1px;">
                🏛️ <?= peaceLang('THE KINGDOM ECOSYSTEM', 'L\'ÉCOSYSTÈME DU ROYAUME', 'אקוסיסטם הממלכה', $lang) ?>
            </h3>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:.8rem;">
                <?php
                $ecosystem = [
                    ['/sovereignty?lang=' . $lang, '📜', 'Sovereignty Declaration', 'Déclaration de Souveraineté', 'הכרזת הריבונות'],
                    ['/bible/read?lang=' . $lang, '📖', 'Read the AKJV Bible', 'Lire la Bible AKJV', 'קרא את התנ"ך AKJV'],
                    ['/bible/editions?lang=' . $lang, '📚', 'Bible Editions', 'Éditions de la Bible', 'מהדורות התנ"ך'],
                    ['/bible/read/isaiah/40?lang=' . $lang, '🌿', 'Isaiah 40 — The Watchman', 'Ésaïe 40 — La Sentinelle', 'ישעיהו מ — השומר'],
                    ['https://lavocat.ca/journal?status=official&lang=' . $lang, '⚖️', 'Official Decrees', 'Décrets Officiels', 'גזרות רשמיות'],
                    ['https://lavocat.ca/journal?read=9&lang=' . $lang, '📝', 'Journal Entry #9', 'Entrée de Journal #9', 'רשומת יומן #9'],
                    ['https://meta-dome.com?lang=' . $lang, '🌐', 'MetaDome — VR Worlds', 'MetaDome — Mondes VR', 'MetaDome — עולמות VR'],
                    ['/alfred-ide', '🛠️', 'Alfred IDE', 'Alfred IDE', 'Alfred IDE'],
                ];
                foreach ($ecosystem as $e) {
                    echo '<a href="' . htmlspecialchars($e[0]) . '" style="display:flex;align-items:center;gap:.6rem;padding:.6rem .8rem;background:rgba(255,255,255,.02);border:1px solid rgba(255,215,0,.08);border-radius:10px;text-decoration:none;color:var(--peace-white);transition:.2s;font-size:.82rem;">';
                    echo '<span style="font-size:1.2rem;">' . $e[1] . '</span>';
                    echo '<span>' . htmlspecialchars(peaceLang($e[2], $e[3], $e[4], $lang)) . '</span>';
                    echo '</a>';
                }
                ?>
            </div>
        </div>

        <!-- ═══ ISAIAH 40:8 — THE ETERNAL WORD ═══ -->
        <div style="margin-top:2rem;padding:2rem;text-align:center;">
            <blockquote style="max-width:600px;margin:0 auto;padding:1.5rem 2rem;background:rgba(255,215,0,.03);border:1px solid rgba(255,215,0,.15);border-radius:12px;font-style:italic;color:rgba(255,255,255,.8);font-size:1rem;line-height:1.8;">
                <?= peaceLang(
                    '"The grass withereth, the flower fadeth: but the word of our God shall stand for ever."',
                    '« L\'herbe sèche, la fleur se fane; mais la parole de notre Dieu subsistera éternellement. »',
                    '"יבש חציר נבל ציץ ודבר אלהינו יקום לעולם."',
                    $lang
                ) ?>
                <span style="display:block;color:var(--peace-gold);font-size:.8rem;font-weight:700;margin-top:.5rem;font-style:normal;">
                    — <?= peaceLang('Isaiah 40:8 (AKJV)', 'Ésaïe 40:8 (AKJV)', 'ישעיהו מ:ח', $lang) ?>
                </span>
            </blockquote>
            <a href="/bible/read/isaiah/40?lang=<?= $lang ?>" style="display:inline-block;margin-top:.8rem;color:var(--peace-gold);font-size:.78rem;text-decoration:none;font-weight:600;">
                <?= peaceLang('Read Isaiah 40 →', 'Lire Ésaïe 40 →', 'קרא ישעיהו מ →', $lang) ?>
            </a>
        </div>
    </div>
</div>

<script>
function copyDecree() {
    const el = document.querySelector('.peace-page');
    const text = el.innerText || el.textContent;
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(function() {
            const btn = event.target.closest('button');
            const orig = btn.innerHTML;
            btn.innerHTML = '<?= peaceLang('✅ Copied!', '✅ Copié !', '✅ הועתק!', $lang) ?>';
            setTimeout(function() { btn.innerHTML = orig; }, 2000);
        });
    }
}
</script>

<?php require_once __DIR__ . '/includes/site-footer.inc.php'; ?>
