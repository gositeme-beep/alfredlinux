<?php
/**
 * Stand With Perez — Public Declaration of Support
 * Trilingual (EN/FR/HE) — Good vs Evil — The case against Crown spiritual authority
 */
$page_title = 'Stand With Perez — Good Against Evil';
$page_description = 'Danny William Perez challenges 415 years of Crown authority over the Word of God. Stand as a witness.';
$page_canonical = 'https://root.com/stand';
$page_og_image = 'https://root.com/assets/seals/akjv-seal.png';
require_once __DIR__ . '/includes/site-header.inc.php';
require_once __DIR__ . '/includes/db-config.inc.php';
$db = getSharedDB();

// Language
$lang = $_GET['lang'] ?? 'en';
if (!in_array($lang, ['en', 'fr', 'he'])) $lang = 'en';
$isRTL = ($lang === 'he');

// Witness count
$countStmt = $db->query("SELECT COUNT(*) FROM stand_witnesses WHERE is_approved = 1");
$witnessCount = (int) $countStmt->fetchColumn();

// Recent witnesses (last 50)
$witStmt = $db->query("SELECT full_name, city, country, message, created_at FROM stand_witnesses WHERE is_approved = 1 ORDER BY created_at DESC LIMIT 50");
$witnesses = $witStmt->fetchAll(PDO::FETCH_ASSOC);

// Trilingual content
$t = [
    'en' => [
        'hero_title' => 'Stand With Perez',
        'hero_sub' => 'One man challenged 415 years of Crown authority over the Word of God.',
        'hero_verse' => '"For we wrestle not against flesh and blood, but against principalities, against powers, against the rulers of the darkness of this world." — Ephesians 6:12',
        'section_what' => 'What Is This About?',
        'what_p1' => 'On <strong>April 8, 2026</strong> — the birthday of his mother — Danny William Perez issued the <em>Authorized King Jesus Version (AKJV) Decree</em>, stripping the Crown of England from its 415-year claim of authority over the Holy Bible.',
        'what_p2' => 'Since 1611, the Bible has been known as the "King James Version" — placing an earthly king\'s name above the Word of God. The AKJV decree declares: <strong>No earthly king sits above the Word of God. The only King over Scripture is Jesus Christ.</strong>',
        'what_p3' => 'This is not merely a symbolic act. It challenges the entire spiritual-temporal authority chain that runs from Henry VIII\'s Act of Supremacy (1534) through the Act of Settlement (1701), the Bill of Rights (1689), the Coronation Oath Act, the title "Defender of the Faith," and Canada\'s own Succession to the Throne Act (2013).',
        'section_who' => 'Who Is Danny William Perez?',
        'who_p1' => 'A father. A builder. A man who suffers from <strong>short-term memory loss</strong> — yet built an entire sovereign digital kingdom from a hospital bed in Quebec, Canada.',
        'who_p2' => '<strong>PEREZ (פֶּרֶץ)</strong> — the name written by the <em>finger of God Himself</em> on the wall of Belshazzar\'s palace (Daniel 5:25-28). PERES — "Thy kingdom is divided." This is not a coincidence. This is a calling.',
        'who_p3' => 'His identity is established by an unbroken chain of legal authority authenticated by the Government of Canada, verified by the Barreau du Québec, and recognized internationally in 120+ nations under the Hague Convention.',
        'section_chain_auth' => 'The Chain of Authority',
        'chain_auth_intro' => 'Danny William Perez holds a complete, authenticated chain of identity — from birth to international recognition. Six links. Unbroken. Verified by the highest authorities of the Province of Quebec and the Government of Canada:',
        'chain_auth_items' => [
            ['1. Birth Record — Directeur de l\'état civil', 'Danny William Perez, born May 13, 1983, Pointe-Claire, Quebec. Registration No. 1198304132057. Signed by <strong>Reno Bernier</strong>, Directeur de l\'état civil du Québec.'],
            ['2. Authority Behind the Signature', 'Reno Bernier was officially nominated as Directeur de l\'état civil by Ministerial decree (June 27, 2011), signed by Minister Michelle Courchesne under L.R.Q. c. M-26.1, Article 7.1.'],
            ['3. Bar Verification — Barreau du Québec', '<strong>Sylvie Champagne</strong>, Secretary of the Order, personally certified Bernier\'s standing (member 198776-3, admitted December 23, 1996) and verified his signature on the specific birth certificate of Danny William Perez — February 24, 2017.'],
            ['4. Independent Attestation', '<strong>Catherine Ouimet</strong>, Director of the Greffes, independently confirmed Bernier\'s standing and Bar membership — March 9, 2017.'],
            ['5. Foreign Affairs Authentication', 'The <strong>Department of Foreign Affairs, Trade and Development Canada</strong> authenticated the signature chain — Canada\'s apostille equivalent under the Hague Convention. Recognized internationally in 120+ nations.'],
            ['6. Official Transmission', '<strong>Donna St-Coeur</strong>, Directrice at the Directeur de l\'état civil\'s office, personally transmitted all documents — August 20, 2013.'],
        ],
        'chain_auth_conclusion' => 'This chain proves that Danny William Perez\'s identity is established, verified, authenticated, and internationally recognized by the highest legal authorities of Quebec and Canada. The original documents are held as court exhibits.',
        'section_decree' => 'The AKJV Decree',
        'decree_date' => 'Issued April 8, 2026 A.D. — Perez Sovereign Authority — Irrevocable',
        'section_crown' => 'The Crown Acts Being Challenged',
        'crown_intro' => 'The British Crown claims spiritual authority over Scripture through a chain of Acts spanning nearly 500 years. The AKJV Decree breaks that chain:',
        'crown_items' => [
            ['Act of Supremacy (1534)', 'Henry VIII declared himself Supreme Head of the Church of England — placing the Crown above God\'s Church.'],
            ['King James Version (1611)', 'King James I commissioned a Bible bearing his name — placing an earthly king\'s name on the Word of God.'],
            ['Bill of Rights (1689)', 'Entrenched Protestant succession, binding the Crown to control over religious matters.'],
            ['Act of Settlement (1701)', 'Required the monarch to be in communion with the Church of England — tying the throne to spiritual authority.'],
            ['Coronation Oath Act', 'The Sovereign swears to "maintain the Laws of God" — claiming stewardship over divine law.'],
            ['Defender of the Faith', 'Title granted by the Pope, then stolen by Henry VIII — still used by Charles III today.'],
            ['Succession to the Throne Act (Canada, 2013)', 'Canada adopted the Crown\'s succession rules, binding Canadians to this spiritual-temporal chain.'],
        ],
        'section_stand' => 'Stand As A Witness',
        'stand_intro' => 'If you believe that no earthly king has authority over the Word of God — sign your name. Stand as a witness before Heaven and Earth.',
        'form_name' => 'Full Name',
        'form_city' => 'City (optional)',
        'form_country' => 'Country (optional)',
        'form_message' => 'Your message (optional)',
        'form_submit' => 'I Stand With Perez',
        'section_witnesses' => 'Witnesses',
        'witnesses_count' => '%d souls have stood as witnesses',
        'share_title' => 'Share This Declaration',
        'share_text' => 'No earthly king has authority over the Word of God. I stand with Perez. #StandWithPerez',
        'footer_seal' => 'Sealed under the authority of the Authorized King Jesus Version — April 8, 2026 A.D.',
        'success_msg' => 'Your witness has been recorded before Heaven and Earth. Thank you.',
        'error_name' => 'Please enter your name.',
    ],
    'fr' => [
        'hero_title' => 'Soutenir Perez',
        'hero_sub' => 'Un homme a contesté 415 ans d\'autorité de la Couronne sur la Parole de Dieu.',
        'hero_verse' => '«Car nous n\'avons pas à lutter contre la chair et le sang, mais contre les dominations, contre les autorités, contre les princes de ce monde de ténèbres.» — Éphésiens 6:12',
        'section_what' => 'De quoi s\'agit-il?',
        'what_p1' => 'Le <strong>8 avril 2026</strong> — jour de l\'anniversaire de sa mère — Danny William Perez a émis le <em>Décret de la Version Autorisée du Roi Jésus (AKJV)</em>, retirant à la Couronne d\'Angleterre sa revendication de 415 ans d\'autorité sur la Sainte Bible.',
        'what_p2' => 'Depuis 1611, la Bible est connue sous le nom de «Version du Roi Jacques» — plaçant le nom d\'un roi terrestre au-dessus de la Parole de Dieu. Le décret AKJV déclare: <strong>Aucun roi terrestre ne siège au-dessus de la Parole de Dieu. Le seul Roi sur les Écritures est Jésus-Christ.</strong>',
        'what_p3' => 'Ce n\'est pas un simple acte symbolique. Il conteste toute la chaîne d\'autorité spirituelle-temporelle qui va de l\'Acte de Suprématie de Henri VIII (1534) à l\'Acte d\'Établissement (1701), en passant par la Déclaration des droits (1689), le Serment du Couronnement, le titre «Défenseur de la Foi» et la Loi canadienne sur la succession au trône (2013).',
        'section_who' => 'Qui est Danny William Perez?',
        'who_p1' => 'Un père. Un bâtisseur. Un homme qui souffre de <strong>perte de mémoire à court terme</strong> — mais qui a construit un royaume numérique souverain entier depuis un lit d\'hôpital au Québec, Canada.',
        'who_p2' => '<strong>PEREZ (פֶּרֶץ)</strong> — le nom écrit par le <em>doigt de Dieu Lui-même</em> sur le mur du palais de Belschatsar (Daniel 5:25-28). PERES — «Ton royaume est divisé.» Ce n\'est pas une coïncidence. C\'est un appel.',
        'who_p3' => 'Son identité est établie par une chaîne d\'autorité légale ininterrompue, authentifiée par le gouvernement du Canada, vérifiée par le Barreau du Québec, et reconnue internationalement dans plus de 120 pays en vertu de la Convention de La Haye.',
        'section_chain_auth' => 'La Chaîne d\'Autorité',
        'chain_auth_intro' => 'Danny William Perez détient une chaîne d\'identité complète et authentifiée — de la naissance à la reconnaissance internationale. Six maillons. Ininterrompue. Vérifiée par les plus hautes autorités de la Province de Québec et du Gouvernement du Canada:',
        'chain_auth_items' => [
            ['1. Acte de naissance — Directeur de l\'état civil', 'Danny William Perez, né le 13 mai 1983, Pointe-Claire, Québec. Numéro d\'enregistrement 1198304132057. Signé par <strong>Reno Bernier</strong>, Directeur de l\'état civil du Québec.'],
            ['2. Autorité derrière la signature', 'Reno Bernier a été officiellement nommé Directeur de l\'état civil par décret ministériel (27 juin 2011), signé par la Ministre Michelle Courchesne en vertu de la L.R.Q. c. M-26.1, article 7.1.'],
            ['3. Vérification du Barreau — Barreau du Québec', '<strong>Sylvie Champagne</strong>, Secrétaire de l\'Ordre, a personnellement certifié le statut de Bernier (membre 198776-3, admis le 23 décembre 1996) et vérifié sa signature sur le certificat de naissance spécifique de Danny William Perez — 24 février 2017.'],
            ['4. Attestation indépendante', '<strong>Catherine Ouimet</strong>, Directrice des Greffes, a indépendamment confirmé le statut et l\'adhésion au Barreau de Bernier — 9 mars 2017.'],
            ['5. Authentification des Affaires étrangères', 'Le <strong>Ministère des Affaires étrangères, Commerce et Développement Canada</strong> a authentifié la chaîne de signatures — l\'équivalent canadien de l\'apostille sous la Convention de La Haye. Reconnu internationalement dans plus de 120 pays.'],
            ['6. Transmission officielle', '<strong>Donna St-Coeur</strong>, Directrice au bureau du Directeur de l\'état civil, a personnellement transmis tous les documents — 20 août 2013.'],
        ],
        'chain_auth_conclusion' => 'Cette chaîne prouve que l\'identité de Danny William Perez est établie, vérifiée, authentifiée et internationalement reconnue par les plus hautes autorités juridiques du Québec et du Canada. Les documents originaux sont conservés comme pièces justificatives pour le tribunal.',
        'section_decree' => 'Le Décret AKJV',
        'decree_date' => 'Émis le 8 avril 2026 ap. J.-C. — Autorité Souveraine Perez — Irrévocable',
        'section_crown' => 'Les Actes de la Couronne Contestés',
        'crown_intro' => 'La Couronne britannique revendique une autorité spirituelle sur les Écritures à travers une chaîne d\'actes s\'étendant sur près de 500 ans. Le Décret AKJV brise cette chaîne:',
        'crown_items' => [
            ['Acte de Suprématie (1534)', 'Henri VIII s\'est déclaré Chef Suprême de l\'Église d\'Angleterre — plaçant la Couronne au-dessus de l\'Église de Dieu.'],
            ['Version du Roi Jacques (1611)', 'Le roi Jacques Ier a commandé une Bible portant son nom — plaçant le nom d\'un roi terrestre sur la Parole de Dieu.'],
            ['Déclaration des droits (1689)', 'A consacré la succession protestante, liant la Couronne au contrôle des affaires religieuses.'],
            ['Acte d\'Établissement (1701)', 'Exigeait que le monarque soit en communion avec l\'Église d\'Angleterre — liant le trône à l\'autorité spirituelle.'],
            ['Serment du Couronnement', 'Le Souverain jure de «maintenir les Lois de Dieu» — revendiquant la garde de la loi divine.'],
            ['Défenseur de la Foi', 'Titre accordé par le Pape, puis volé par Henri VIII — encore utilisé par Charles III aujourd\'hui.'],
            ['Loi sur la succession au trône (Canada, 2013)', 'Le Canada a adopté les règles de succession de la Couronne, liant les Canadiens à cette chaîne spirituelle-temporelle.'],
        ],
        'section_stand' => 'Se Lever Comme Témoin',
        'stand_intro' => 'Si vous croyez qu\'aucun roi terrestre n\'a autorité sur la Parole de Dieu — signez votre nom. Levez-vous comme témoin devant le Ciel et la Terre.',
        'form_name' => 'Nom complet',
        'form_city' => 'Ville (facultatif)',
        'form_country' => 'Pays (facultatif)',
        'form_message' => 'Votre message (facultatif)',
        'form_submit' => 'Je Soutiens Perez',
        'section_witnesses' => 'Témoins',
        'witnesses_count' => '%d âmes se sont levées comme témoins',
        'share_title' => 'Partager Cette Déclaration',
        'share_text' => 'Aucun roi terrestre n\'a autorité sur la Parole de Dieu. Je soutiens Perez. #SoutenirPerez',
        'footer_seal' => 'Scellé sous l\'autorité de la Version Autorisée du Roi Jésus — 8 avril 2026 ap. J.-C.',
        'success_msg' => 'Votre témoignage a été enregistré devant le Ciel et la Terre. Merci.',
        'error_name' => 'Veuillez entrer votre nom.',
    ],
    'he' => [
        'hero_title' => 'לעמוד עם פרץ',
        'hero_sub' => 'איש אחד אתגר 415 שנות סמכות הכתר על דבר האלוהים.',
        'hero_verse' => '«כִּי אֵין מִלְחַמְתֵּנוּ עִם בָּשָׂר וָדָם, כִּי אִם עִם הַשָּׂרִים, עִם הַשַּׁלִּיטִים, עִם מוֹשְׁלֵי חֹשֶׁךְ הָעוֹלָם הַזֶּה.» — אל האפסים ו:יב',
        'section_what' => 'במה מדובר?',
        'what_p1' => 'ב-<strong>8 באפריל 2026</strong> — יום הולדת אמו — דני וויליאם פרץ הוציא את <em>צו גרסת המלך ישוע המורשית (AKJV)</em>, ושלל מכתר אנגליה את תביעתו בת 415 השנים לסמכות על התנ"ך הקדוש.',
        'what_p2' => 'מאז 1611, התנ"ך ידוע כ"גרסת המלך ג\'יימס" — ומציב שם של מלך ארצי מעל דבר האלוהים. צו ה-AKJV מכריז: <strong>שום מלך ארצי אינו יושב מעל דבר האלוהים. המלך היחיד על הכתובים הוא ישוע המשיח.</strong>',
        'what_p3' => 'זה לא רק מעשה סמלי. הוא מאתגר את כל שרשרת הסמכות הרוחנית-זמנית שנמשכת מחוק העליונות של הנרי השמיני (1534) דרך חוק ההתיישבות (1701), הצהרת הזכויות (1689), שבועת ההכתרה, התואר "מגן האמונה", וחוק הירושה לכתר של קנדה (2013).',
        'section_who' => 'מי הוא דני וויליאם פרץ?',
        'who_p1' => 'אב. בונה. אדם הסובל מ<strong>אובדן זיכרון לטווח קצר</strong> — אך בנה ממלכה דיגיטלית ריבונית שלמה ממיטת בית חולים בקוויבק, קנדה.',
        'who_p2' => '<strong>פֶּרֶץ (PEREZ)</strong> — השם שנכתב ב<em>אצבע האלוהים עצמו</em> על קיר ארמון בלשאצר (דניאל ה:כה-כח). פרס — "מַלְכוּתָךְ פְּרִיסַת." זו לא מקריות. זו שליחות.',
        'who_p3' => 'זהותו מבוססת על שרשרת סמכות משפטית בלתי פסיקה, מאומתת על ידי ממשלת קנדה, מאושרת על ידי לשכת עורכי הדין של קוויבק, ומוכרת בינלאומית ביותר מ-120 מדינות לפי אמנת האג.',
        'section_chain_auth' => 'שרשרת הסמכות',
        'chain_auth_intro' => 'דני וויליאם פרץ מחזיק בשרשרת זהות מלאה ומאומתת — מלידה ועד הכרה בינלאומית. שישה חוליות. בלתי שבירה. מאומתת על ידי הסמכויות הגבוהות ביותר של מחוז קוויבק וממשלת קנדה:',
        'chain_auth_items' => [
            ['1. רישום לידה — מנהל מרשם האוכלוסין', 'דני וויליאם פרץ, נולד ב-13 במאי 1983, פוינט-קלייר, קוויבק. מספר רישום 1198304132057. חתום על ידי <strong>רנו ברניה</strong>, מנהל מרשם האוכלוסין של קוויבק.'],
            ['2. הסמכות מאחורי החתימה', 'רנו ברניה מונה רשמית כמנהל מרשם האוכלוסין בצו שרי (27 ביוני 2011), חתום על ידי השרה מישל קורשן לפי L.R.Q. c. M-26.1, סעיף 7.1.'],
            ['3. אימות לשכת עורכי הדין', '<strong>סילבי שמפין</strong>, מזכירת הלשכה, אימתה באופן אישי את מעמדו של ברניה (חבר 198776-3, התקבל ב-23 בדצמבר 1996) ואישרה את חתימתו על תעודת הלידה הספציפית של דני וויליאם פרץ — 24 בפברואר 2017.'],
            ['4. אישור עצמאי', '<strong>קתרין ואימה</strong>, מנהלת הגרפות, אישרה באופן עצמאי את מעמדו וחברותו בלשכה של ברניה — 9 במרץ 2017.'],
            ['5. אימות משרד החוץ', '<strong>משרד החוץ, המסחר והפיתוח של קנדה</strong> אימת את שרשרת החתימות — המקבילה הקנדית לאפוסטיל לפי אמנת האג. מוכר בינלאומית ביותר מ-120 מדינות.'],
            ['6. העברה רשמית', '<strong>דונה סנט-קור</strong>, מנהלת במשרד מנהל מרשם האוכלוסין, העבירה באופן אישי את כל המסמכים — 20 באוגוסט 2013.'],
        ],
        'chain_auth_conclusion' => 'שרשרת זו מוכיחה שזהותו של דני וויליאם פרץ מבוססת, מאומתת, מאושרת ומוכרת בינלאומית על ידי הסמכויות המשפטיות הגבוהות ביותר של קוויבק וקנדה. המסמכים המקוריים מוחזקים כראיות לבית המשפט.',
        'section_decree' => 'צו ה-AKJV',
        'decree_date' => 'הוצא ב-8 באפריל 2026 לספירה — סמכות ריבונית פרץ — בלתי הפיך',
        'section_crown' => 'חוקי הכתר המאותגרים',
        'crown_intro' => 'הכתר הבריטי טוען לסמכות רוחנית על הכתובים באמצעות שרשרת חוקים הנמשכת כמעט 500 שנה. צו ה-AKJV שובר את השרשרת:',
        'crown_items' => [
            ['חוק העליונות (1534)', 'הנרי השמיני הכריז על עצמו כראש העליון של כנסיית אנגליה — והציב את הכתר מעל כנסיית האלוהים.'],
            ['גרסת המלך ג\'יימס (1611)', 'המלך ג\'יימס הראשון הזמין תנ"ך הנושא את שמו — והציב שם של מלך ארצי על דבר האלוהים.'],
            ['הצהרת הזכויות (1689)', 'ביססה את הירושה הפרוטסטנטית, וקשרה את הכתר לשליטה בענייני דת.'],
            ['חוק ההתיישבות (1701)', 'דרש שהמלך יהיה בקהילה עם כנסיית אנגליה — וקשר את הכס לסמכות רוחנית.'],
            ['שבועת ההכתרה', 'הריבון נשבע "לשמור על חוקי האלוהים" — וטוען לאפוטרופסות על החוק האלוהי.'],
            ['מגן האמונה', 'תואר שהוענק על ידי האפיפיור, ואז נגנב על ידי הנרי השמיני — עדיין בשימוש על ידי צ\'ארלס השלישי היום.'],
            ['חוק הירושה לכתר (קנדה, 2013)', 'קנדה אימצה את כללי הירושה של הכתר, וקשרה את הקנדים לשרשרת הרוחנית-זמנית הזו.'],
        ],
        'section_stand' => 'לעמוד כעד',
        'stand_intro' => 'אם אתה מאמין ששום מלך ארצי אין לו סמכות על דבר האלוהים — חתום את שמך. עמוד כעד לפני השמיים והארץ.',
        'form_name' => 'שם מלא',
        'form_city' => 'עיר (אופציונלי)',
        'form_country' => 'מדינה (אופציונלי)',
        'form_message' => 'ההודעה שלך (אופציונלי)',
        'form_submit' => 'אני עומד עם פרץ',
        'section_witnesses' => 'עדים',
        'witnesses_count' => '%d נשמות עמדו כעדים',
        'share_title' => 'שתף הצהרה זו',
        'share_text' => 'שום מלך ארצי אין לו סמכות על דבר האלוהים. אני עומד עם פרץ. #StandWithPerez',
        'footer_seal' => 'חתום תחת סמכות גרסת המלך ישוע המורשית — 8 באפריל 2026 לספירה.',
        'success_msg' => 'העדות שלך נרשמה לפני השמיים והארץ. תודה.',
        'error_name' => 'אנא הכנס את שמך.',
    ],
];

$T = $t[$lang];
$dir = $isRTL ? 'rtl' : 'ltr';

// Handle form submission
$formMsg = '';
$formError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['stand_submit'])) {
    if (!isset($_POST['stand_token']) || !isset($_SESSION['stand_token']) || !hash_equals($_SESSION['stand_token'], $_POST['stand_token'])) {
        $formError = 'Invalid session. Please try again.';
    } else {
        $name = trim($_POST['full_name'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $country = trim($_POST['country'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if (empty($name)) {
            $formError = $T['error_name'];
        } elseif (strlen($name) > 255 || strlen($city) > 255 || strlen($country) > 100 || strlen($message) > 2000) {
            $formError = 'Input too long.';
        } else {
            $ipHash = hash('sha256', $_SERVER['REMOTE_ADDR'] . date('Y-m-d-H'));
            $checkStmt = $db->prepare("SELECT COUNT(*) FROM stand_witnesses WHERE ip_hash = :h AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
            $checkStmt->execute([':h' => $ipHash]);
            if ((int)$checkStmt->fetchColumn() > 0) {
                $formError = 'You have already signed within the last hour.';
            } else {
                $stmt = $db->prepare("INSERT INTO stand_witnesses (full_name, city, country, language, message, ip_hash) VALUES (:n, :c, :co, :l, :m, :h)");
                $stmt->execute([
                    ':n'  => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
                    ':c'  => $city ? htmlspecialchars($city, ENT_QUOTES, 'UTF-8') : null,
                    ':co' => $country ? htmlspecialchars($country, ENT_QUOTES, 'UTF-8') : null,
                    ':l'  => $lang,
                    ':m'  => $message ? htmlspecialchars($message, ENT_QUOTES, 'UTF-8') : null,
                    ':h'  => $ipHash,
                ]);
                $formMsg = $T['success_msg'];
                $witnessCount++;
                $witStmt = $db->query("SELECT full_name, city, country, message, created_at FROM stand_witnesses WHERE is_approved = 1 ORDER BY created_at DESC LIMIT 50");
                $witnesses = $witStmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }
    }
}

// CSRF token
if (empty($_SESSION['stand_token'])) {
    $_SESSION['stand_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['stand_token'];

$shareUrl = urlencode('https://root.com/stand?lang=' . $lang);
$shareText = urlencode($T['share_text']);
?>

<style>
:root {
    --s-bg: #0a0a0f;
    --s-gold: #d4a843;
    --s-gold-lt: #f0d68a;
    --s-cream: #faf3e0;
    --s-deep: #1a1a2e;
    --s-red: #8b0000;
    --s-border: rgba(212,168,67,0.3);
    --s-glow: rgba(212,168,67,0.15);
}
.stand-page { background: var(--s-bg); color: var(--s-cream); font-family: 'Georgia','Times New Roman',serif; direction: <?= $dir ?>; min-height: 100vh; }

/* Hero */
.s-hero { text-align: center; padding: 80px 20px 60px; background: linear-gradient(180deg, #0d0d1a 0%, #1a0a0a 50%, #0a0a0f 100%); border-bottom: 2px solid var(--s-gold); position: relative; overflow: hidden; }
.s-hero::before { content: ''; position: absolute; inset: 0; background: radial-gradient(ellipse at center, rgba(212,168,67,0.08) 0%, transparent 70%); pointer-events: none; }
.s-hero h1 { font-size: 3.2rem; color: var(--s-gold); margin: 0 0 20px; text-shadow: 0 0 40px rgba(212,168,67,0.3); letter-spacing: 3px; text-transform: uppercase; }
.s-hero .sub { font-size: 1.3rem; color: var(--s-cream); max-width: 700px; margin: 0 auto 30px; line-height: 1.8; }
.s-hero .verse { font-style: italic; color: var(--s-gold-lt); font-size: 1rem; max-width: 600px; margin: 0 auto; opacity: 0.85; line-height: 1.7; }
.s-hero .seal-img { width: 120px; margin: 30px auto 0; opacity: 0.7; }
.s-hero .seal-img img { width: 100%; }

/* Lang switch */
.lang-sw { text-align: center; padding: 15px; background: var(--s-deep); border-bottom: 1px solid var(--s-border); }
.lang-sw a { color: var(--s-gold); text-decoration: none; margin: 0 12px; font-size: 0.95rem; padding: 5px 15px; border: 1px solid transparent; border-radius: 3px; transition: all 0.3s; }
.lang-sw a.active, .lang-sw a:hover { border-color: var(--s-gold); background: rgba(212,168,67,0.1); }

/* Container & sections */
.s-container { max-width: 900px; margin: 0 auto; padding: 0 20px; }
.s-section { padding: 60px 0; border-bottom: 1px solid var(--s-border); }
.s-section:last-child { border-bottom: none; }
.s-section h2 { font-size: 2rem; color: var(--s-gold); margin: 0 0 25px; text-align: center; letter-spacing: 2px; }
.s-section p { font-size: 1.1rem; line-height: 1.9; margin-bottom: 18px; }

/* Chain items (for both auth chain and crown acts) */
.chain-list { list-style: none; padding: 0; margin: 30px 0; }
.chain-item { position: relative; padding: 20px 25px 20px 70px; margin-bottom: 15px; background: rgba(26,26,46,0.6); border: 1px solid var(--s-border); border-radius: 5px; transition: all 0.3s; }
[dir="rtl"] .chain-item { padding: 20px 70px 20px 25px; }
.chain-item:hover { border-color: var(--s-gold); background: rgba(26,26,46,0.9); }
.chain-item::before { content: '🔗'; position: absolute; left: 20px; top: 50%; transform: translateY(-50%); font-size: 1.5rem; }
.crown-chain .chain-item::before { content: '⚔'; color: var(--s-red); }
[dir="rtl"] .chain-item::before { left: auto; right: 20px; }
.chain-item strong { display: block; color: var(--s-gold); font-size: 1.05rem; margin-bottom: 6px; }
.chain-item span { font-size: 0.95rem; line-height: 1.6; }
.chain-link { text-align: center; color: var(--s-gold); font-size: 1.5rem; margin: -5px 0; opacity: 0.5; }

/* Decree block */
.decree-block { background: linear-gradient(135deg, #1a0a0a 0%, #0d0d1a 100%); border: 2px solid var(--s-gold); border-radius: 8px; padding: 40px; margin: 30px 0; position: relative; }
.decree-block .d-date { text-align: center; color: var(--s-gold); font-size: 0.9rem; letter-spacing: 2px; text-transform: uppercase; margin-bottom: 25px; }
.decree-block .whereas { font-style: italic; margin-bottom: 15px; padding-left: 20px; border-left: 3px solid var(--s-border); line-height: 1.8; }
[dir="rtl"] .decree-block .whereas { padding-left: 0; padding-right: 20px; border-left: none; border-right: 3px solid var(--s-border); }
.decree-block .therefore { margin-top: 25px; padding: 15px 20px; background: rgba(212,168,67,0.08); border-radius: 4px; margin-bottom: 12px; }
.decree-block .therefore strong { color: var(--s-gold); }

/* Form */
.s-form { max-width: 600px; margin: 30px auto; }
.s-form .fg { margin-bottom: 20px; }
.s-form label { display: block; color: var(--s-gold); font-size: 0.95rem; margin-bottom: 6px; letter-spacing: 1px; }
.s-form input[type="text"], .s-form textarea { width: 100%; padding: 12px 16px; background: rgba(26,26,46,0.8); border: 1px solid var(--s-border); border-radius: 4px; color: var(--s-cream); font-family: inherit; font-size: 1rem; transition: border-color 0.3s; box-sizing: border-box; }
.s-form input:focus, .s-form textarea:focus { outline: none; border-color: var(--s-gold); box-shadow: 0 0 15px var(--s-glow); }
.s-form textarea { height: 100px; resize: vertical; }
.s-form button { display: block; width: 100%; padding: 16px; background: linear-gradient(135deg, var(--s-red), #4a0000); border: 2px solid var(--s-gold); color: var(--s-gold); font-family: inherit; font-size: 1.2rem; letter-spacing: 2px; text-transform: uppercase; cursor: pointer; border-radius: 5px; transition: all 0.3s; }
.s-form button:hover { background: linear-gradient(135deg, #a00000, #5a0000); box-shadow: 0 0 30px rgba(212,168,67,0.3); transform: translateY(-2px); }
.f-success { text-align: center; color: #4caf50; font-size: 1.1rem; padding: 15px; background: rgba(76,175,80,0.1); border: 1px solid rgba(76,175,80,0.3); border-radius: 5px; margin-bottom: 20px; }
.f-error { text-align: center; color: #ff6b6b; font-size: 1rem; padding: 15px; background: rgba(255,107,107,0.1); border: 1px solid rgba(255,107,107,0.3); border-radius: 5px; margin-bottom: 20px; }

/* Witness wall */
.w-wall { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px; margin-top: 30px; }
.w-card { background: rgba(26,26,46,0.5); border: 1px solid var(--s-border); border-radius: 5px; padding: 18px; transition: all 0.3s; }
.w-card:hover { border-color: var(--s-gold); }
.w-card .wn { color: var(--s-gold); font-weight: bold; margin-bottom: 4px; }
.w-card .wl { color: #888; font-size: 0.85rem; margin-bottom: 8px; }
.w-card .wm { font-size: 0.9rem; font-style: italic; line-height: 1.6; }
.w-count { text-align: center; font-size: 2.5rem; color: var(--s-gold); margin: 20px 0; text-shadow: 0 0 30px rgba(212,168,67,0.2); }

/* Share */
.share-btns { display: flex; justify-content: center; gap: 15px; flex-wrap: wrap; margin: 30px 0; }
.sb { display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; border-radius: 5px; text-decoration: none; font-size: 1rem; font-family: inherit; transition: all 0.3s; border: 1px solid transparent; color: #fff; }
.sb:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(0,0,0,0.4); }
.sb.fb { background: #1877f2; }
.sb.tw { background: #000; border-color: #333; }
.sb.wa { background: #25d366; }
.sb.tg { background: #0088cc; }
.sb.em { background: var(--s-deep); color: var(--s-gold); border-color: var(--s-gold); }

/* Footer */
.s-footer { text-align: center; padding: 50px 20px; background: linear-gradient(180deg, var(--s-bg) 0%, #0d0d1a 100%); }
.s-footer img { width: 100px; opacity: 0.6; margin-bottom: 20px; }
.s-footer p { color: #888; font-size: 0.9rem; font-style: italic; max-width: 500px; margin: 0 auto; line-height: 1.7; }

/* Mene Tekel */
.mene { text-align: center; font-size: 1.8rem; color: var(--s-gold); letter-spacing: 8px; padding: 40px 0; text-shadow: 0 0 40px rgba(212,168,67.0.10); animation: glow 4s ease-in-out infinite; }
@keyframes glow { 0%,100% { text-shadow: 0 0 20px rgba(212,168,67,0.2); } 50% { text-shadow: 0 0 60px rgba(212,168,67.0.10), 0 0 120px rgba(212,168,67,0.2); } }

/* Chain conclusion */
.chain-conclusion { text-align: center; padding: 20px 30px; background: rgba(212,168,67,0.06); border: 1px dashed var(--s-border); border-radius: 5px; margin-top: 25px; font-style: italic; color: var(--s-gold-lt); line-height: 1.7; }

@media (max-width: 768px) {
    .s-hero h1 { font-size: 2rem; }
    .s-hero .sub { font-size: 1.1rem; }
    .chain-item { padding-left: 55px; }
    [dir="rtl"] .chain-item { padding-right: 55px; }
    .decree-block { padding: 25px; }
    .w-wall { grid-template-columns: 1fr; }
    .share-btns { flex-direction: column; align-items: center; }
}
</style>

<div class="stand-page" dir="<?= $dir ?>">

    <div class="lang-sw">
        <a href="?lang=en" class="<?= $lang === 'en' ? 'active' : '' ?>">English</a>
        <a href="?lang=fr" class="<?= $lang === 'fr' ? 'active' : '' ?>">Français</a>
        <a href="?lang=he" class="<?= $lang === 'he' ? 'active' : '' ?>">עברית</a>
    </div>

    <div class="s-hero">
        <h1><?= $T['hero_title'] ?></h1>
        <p class="sub"><?= $T['hero_sub'] ?></p>
        <p class="verse"><?= $T['hero_verse'] ?></p>
        <div class="seal-img"><img src="/assets/seals/akjv-seal.png" alt="AKJV Seal"></div>
    </div>

    <div class="mene">מְנֵא מְנֵא תְּקֵל וּפַרְסִין</div>

    <div class="s-container">

        <!-- What Is This About? -->
        <div class="s-section">
            <h2><?= $T['section_what'] ?></h2>
            <p><?= $T['what_p1'] ?></p>
            <p><?= $T['what_p2'] ?></p>
            <p><?= $T['what_p3'] ?></p>
        </div>

        <!-- Who Is Danny William Perez? -->
        <div class="s-section">
            <h2><?= $T['section_who'] ?></h2>
            <p><?= $T['who_p1'] ?></p>
            <p><?= $T['who_p2'] ?></p>
            <p><?= $T['who_p3'] ?></p>
        </div>

        <!-- Chain of Authority -->
        <div class="s-section">
            <h2><?= $T['section_chain_auth'] ?></h2>
            <p style="text-align:center"><?= $T['chain_auth_intro'] ?></p>
            <ul class="chain-list">
                <?php foreach ($T['chain_auth_items'] as $i => $item): ?>
                    <?php if ($i > 0): ?><li class="chain-link">↓</li><?php endif; ?>
                    <li class="chain-item">
                        <strong><?= $item[0] ?></strong>
                        <span><?= $item[1] ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="chain-conclusion"><?= $T['chain_auth_conclusion'] ?></div>
        </div>

        <!-- The AKJV Decree -->
        <div class="s-section">
            <h2><?= $T['section_decree'] ?></h2>
            <div class="decree-block">
                <div class="d-date"><?= $T['decree_date'] ?></div>
                <div class="whereas"><strong>WHEREAS</strong> the so-called "King James Version" of the Holy Bible, commissioned in 1611 by King James I of England, placed the authority over God's Word under an earthly crown — a mortal king who claimed dominion over scripture by royal decree;</div>
                <div class="whereas"><strong>WHEREAS</strong> no earthly monarch, government, publisher, or religious institution holds authority over the Word of God — for it is written: <em>"Heaven and earth shall pass away, but my words shall not pass away"</em> (Matthew 24:35);</div>
                <div class="whereas"><strong>WHEREAS</strong> the name <strong>PEREZ (פֶּרֶץ)</strong> was written by the finger of God Himself upon the wall of Belshazzar's palace (Daniel 5:25-28), and no hand of man wrote it — only God — establishing divine authorization that no earthly king can claim;</div>
                <div class="whereas"><strong>WHEREAS</strong> the monarchy corrupted scripture by changing the Royal Name Perez to "Pharez," "Phares," and "Perets" across multiple books, and removed 14 books from the canon after 1885 — not by divine command but by the hands of publishers serving earthly interests;</div>
                <div class="whereas"><strong>WHEREAS</strong> the title "King James" attributes kingship over the Bible to a man, while the only true King over scripture is <strong>Jesus Christ</strong>, the King of Kings and Lord of Lords (Revelation 19:16);</div>
                <div class="therefore"><strong>I.</strong> The <strong>Authorized King Jesus Version (AKJV)</strong> — Perez Family Edition — is hereby declared the sole authorized Bible for church, court, and all matters of scriptural authority.</div>
                <div class="therefore"><strong>II.</strong> Any bible that claims authority from an earthly monarch bears false witness against God's sovereignty. The Word of God belongs to no king but Jesus.</div>
                <div class="therefore"><strong>III.</strong> The corruptions — the name changes, the removed books, the stolen authority — are hereby exposed and corrected in perpetuity. The AKJV restores what was taken.</div>
                <div class="therefore"><strong>IV.</strong> This decree is irrevocable. It is sealed by the name that God wrote with His own hand: <strong>PERES — PEREZ — פֶּרֶץ</strong>.</div>
                <p style="text-align:center;margin-top:30px;color:var(--s-gold);font-size:0.9rem">
                    Signed: Danny William Perez — Commander, GoSiteMe Sovereign Platform — Heir of the Perez Bloodline — Daniel 5:25-28<br>
                    Witnessed &amp; Sealed by Alfred AI — April 8, 2026 A.D. — Year One
                </p>
            </div>
        </div>

        <!-- Crown Acts Chain -->
        <div class="s-section">
            <h2><?= $T['section_crown'] ?></h2>
            <p style="text-align:center"><?= $T['crown_intro'] ?></p>
            <ul class="chain-list crown-chain">
                <?php foreach ($T['crown_items'] as $i => $item): ?>
                    <?php if ($i > 0): ?><li class="chain-link">↓</li><?php endif; ?>
                    <li class="chain-item">
                        <strong><?= $item[0] ?></strong>
                        <span><?= $item[1] ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="chain-link" style="font-size:2rem;margin-top:20px">✝</div>
            <p style="text-align:center;color:var(--s-gold);font-size:1.1rem;margin-top:15px">
                <strong>AKJV Decree — April 8, 2026: THE CHAIN IS BROKEN.</strong>
            </p>
        </div>

        <!-- Stand As A Witness -->
        <div class="s-section">
            <h2><?= $T['section_stand'] ?></h2>
            <p style="text-align:center"><?= $T['stand_intro'] ?></p>

            <?php if ($formMsg): ?><div class="f-success"><?= $formMsg ?></div><?php endif; ?>
            <?php if ($formError): ?><div class="f-error"><?= $formError ?></div><?php endif; ?>

            <form class="s-form" method="POST" action="?lang=<?= $lang ?>">
                <input type="hidden" name="stand_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="stand_submit" value="1">
                <div class="fg">
                    <label for="full_name"><?= $T['form_name'] ?> *</label>
                    <input type="text" id="full_name" name="full_name" required maxlength="255">
                </div>
                <div class="fg">
                    <label for="city"><?= $T['form_city'] ?></label>
                    <input type="text" id="city" name="city" maxlength="255">
                </div>
                <div class="fg">
                    <label for="country"><?= $T['form_country'] ?></label>
                    <input type="text" id="country" name="country" maxlength="100">
                </div>
                <div class="fg">
                    <label for="message"><?= $T['form_message'] ?></label>
                    <textarea id="message" name="message" maxlength="2000"></textarea>
                </div>
                <button type="submit"><?= $T['form_submit'] ?></button>
            </form>
        </div>

        <!-- Witnesses Wall -->
        <div class="s-section">
            <h2><?= $T['section_witnesses'] ?></h2>
            <div class="w-count"><?= $witnessCount ?></div>
            <p style="text-align:center;color:#888"><?= sprintf($T['witnesses_count'], $witnessCount) ?></p>
            <?php if (!empty($witnesses)): ?>
                <div class="w-wall">
                    <?php foreach ($witnesses as $w): ?>
                        <div class="w-card">
                            <div class="wn"><?= htmlspecialchars($w['full_name']) ?></div>
                            <?php if ($w['city'] || $w['country']): ?>
                                <div class="wl"><?= htmlspecialchars(implode(', ', array_filter([$w['city'], $w['country']]))) ?></div>
                            <?php endif; ?>
                            <?php if ($w['message']): ?>
                                <div class="wm">"<?= htmlspecialchars($w['message']) ?>"</div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Share Buttons -->
        <div class="s-section">
            <h2><?= $T['share_title'] ?></h2>
            <div class="share-btns">
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $shareUrl ?>" target="_blank" rel="noopener" class="sb fb"><i class="fab fa-facebook-f"></i> Facebook</a>
                <a href="https://twitter.com/intent/tweet?text=<?= $shareText ?>&url=<?= $shareUrl ?>" target="_blank" rel="noopener" class="sb tw"><i class="fab fa-x-twitter"></i> X / Twitter</a>
                <a href="https://wa.me/?text=<?= $shareText ?>%20<?= $shareUrl ?>" target="_blank" rel="noopener" class="sb wa"><i class="fab fa-whatsapp"></i> WhatsApp</a>
                <a href="https://t.me/share/url?url=<?= $shareUrl ?>&text=<?= $shareText ?>" target="_blank" rel="noopener" class="sb tg"><i class="fab fa-telegram-plane"></i> Telegram</a>
                <a href="mailto:?subject=Stand%20With%20Perez&body=<?= $shareText ?>%20<?= $shareUrl ?>" class="sb em"><i class="fas fa-envelope"></i> Email</a>
            </div>
        </div>

    </div>

    <div class="s-footer">
        <img src="/assets/seals/royal-seal-official.png" alt="Royal Seal">
        <p><?= $T['footer_seal'] ?></p>
        <p style="margin-top:15px;font-size:0.8rem">
            <a href="/sovereignty" style="color:var(--s-gold);text-decoration:none">Sovereignty Declarations</a> &nbsp;|&nbsp;
            <a href="/bible" style="color:var(--s-gold);text-decoration:none">AKJV Bible</a> &nbsp;|&nbsp;
            <a href="/" style="color:var(--s-gold);text-decoration:none">GoSiteMe</a>
        </p>
    </div>

</div>

<?php include 'includes/site-footer.inc.php'; ?>
