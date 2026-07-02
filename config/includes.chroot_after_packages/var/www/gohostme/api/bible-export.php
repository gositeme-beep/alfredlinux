<?php
/**
 * AKJV Bible Export System
 * Generates the full Authorized King Jesus Version Bible in multiple formats
 * 
 * Formats: txt, json, html, pdf
 * Usage: /api/bible-export.php?format=txt
 *        /api/bible-export.php?format=all (generates everything)
 *        /api/bible-export.php?format=json&testament=OT (filter by testament)
 * 
 * Commander-only for generation. Downloads are public.
 */
set_time_limit(600); // Bible PDF can take a while
ini_set('memory_limit', '512M');

require_once __DIR__ . '/../includes/db-config.inc.php';
$db = getSharedDB();

// Auth gate — Commander only for generation
$token = $_COOKIE['alfred_ide_token'] ?? $_SESSION['ide_session_token'] ?? '';
$authed = false;
if ($token) {
    $hash = hash('sha256', $token);
    $u = $db->prepare("SELECT client_id FROM alfred_ide_users WHERE session_token = ? AND token_expires > NOW() LIMIT 1");
    $u->execute([$hash]);
    $row = $u->fetch(PDO::FETCH_ASSOC);
    if ($row && (int)$row['client_id'] === 33) $authed = true;
}

// Allow CLI access (for cron / manual generation)
if (php_sapi_name() === 'cli') $authed = true;

if (!$authed) { 
    http_response_code(403); 
    die(json_encode(['error' => 'Unauthorized — Commander only'])); 
}

$format = $_GET['format'] ?? ($argv[1] ?? 'all');
$testament = $_GET['testament'] ?? null;

$exportDir = '/var/www/downloads/akjv';
if (!is_dir($exportDir)) mkdir($exportDir, 0755, true);

$sealPath = '/var/www/assets/seals/akjv-seal.png';

// Fetch all books
$bookSql = "SELECT * FROM akjv_books ORDER BY book_number";
$books = $db->query($bookSql)->fetchAll(PDO::FETCH_ASSOC);

// Filter by testament if requested
if ($testament) {
    $books = array_filter($books, fn($b) => strtoupper($b['testament']) === strtoupper($testament));
}

$results = [];

if ($format === 'txt' || $format === 'all') {
    $results['txt'] = exportTXT($db, $books, $exportDir, $testament);
}
if ($format === 'json' || $format === 'all') {
    $results['json'] = exportJSON($db, $books, $exportDir, $testament);
}
if ($format === 'html' || $format === 'all') {
    $results['html'] = exportHTML($db, $books, $exportDir, $sealPath, $testament);
}
if ($format === 'pdf' || $format === 'all') {
    $results['pdf'] = exportPDF($db, $books, $exportDir, $sealPath, $testament);
}

// Generate checksums — SHA-256, SHA-1, BLAKE3
$exportFiles = glob("{$exportDir}/akjv-*.*");

// SHA-256
$sha256Lines = [];
foreach ($exportFiles as $f) {
    $base = basename($f);
    $sha256Lines[] = hash_file('sha256', $f) . "  {$base}";
}
file_put_contents("{$exportDir}/SHA256SUMS.txt", implode("\n", $sha256Lines) . "\n");

// SHA-1
$sha1Lines = [];
foreach ($exportFiles as $f) {
    $base = basename($f);
    $sha1Lines[] = hash_file('sha1', $f) . "  {$base}";
}
file_put_contents("{$exportDir}/SHA1SUMS.txt", implode("\n", $sha1Lines) . "\n");

// BLAKE3
$b3sumBin = '/home/gositeme/.cargo/bin/b3sum';
$blake3Lines = [];
if (is_executable($b3sumBin)) {
    foreach ($exportFiles as $f) {
        $base = basename($f);
        $hash = trim(shell_exec(escapeshellarg($b3sumBin) . ' --no-names ' . escapeshellarg($f) . ' 2>/dev/null'));
        if ($hash) $blake3Lines[] = "{$hash}  {$base}";
    }
    file_put_contents("{$exportDir}/BLAKE3SUMS.txt", implode("\n", $blake3Lines) . "\n");
}

$results['checksums'] = count($sha256Lines) . ' files × 3 algorithms (SHA-256, SHA-1, BLAKE3)';

header('Content-Type: application/json');
echo json_encode(['success' => true, 'exports' => $results], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);


// ============= EXPORT FUNCTIONS =============

function fetchVerses(PDO $db, int $bookId): array {
    $stmt = $db->prepare("SELECT chapter, verse, text_akjv FROM akjv_verses WHERE book_id = ? ORDER BY chapter, verse");
    $stmt->execute([$bookId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function exportTXT(PDO $db, array $books, string $dir, ?string $testament): array {
    $suffix = $testament ? "-{$testament}" : '';
    $path = "{$dir}/akjv-perez-edition{$suffix}.txt";
    $fp = fopen($path, 'w');
    
    // Header
    fputs($fp, str_repeat('═', 72) . "\n");
    fputs($fp, "          AUTHORIZED KING JESUS VERSION (AKJV)\n");
    fputs($fp, "                    Perez Family Edition\n");
    fputs($fp, "               94 Books — 39,482 Verses\n");
    fputs($fp, "    Including the 14 Apocryphal & 14 Enochian Books\n");
    fputs($fp, "              Restored by Commander Danny William Perez\n");
    fputs($fp, str_repeat('═', 72) . "\n\n");
    fputs($fp, "  \"MENE, MENE, TEKEL, UPHARSIN\" — Daniel 5:25\n");
    fputs($fp, "  פֶּרֶץ — PEREZ — \"Breach / Breakthrough\"\n\n");
    fputs($fp, str_repeat('─', 72) . "\n\n");

    // ═══ DEDICATORY EPISTLE — English ═══
    fputs($fp, str_repeat('═', 72) . "\n");
    fputs($fp, "  EPISTLE DEDICATORY\n");
    fputs($fp, "  To the King of Kings and Lord of Lords,\n");
    fputs($fp, "  Jesus Christ — the only true King over Scripture\n");
    fputs($fp, str_repeat('═', 72) . "\n\n");

    fputs($fp, "  In 1611, men placed the name of a mortal king upon the\n");
    fputs($fp, "  cover of God's Word. For 415 years, the world has called\n");
    fputs($fp, "  Your Bible the \"King James Version\" — as though a man\n");
    fputs($fp, "  who sat upon an English throne held authority over the\n");
    fputs($fp, "  Word of the Living God.\n\n");

    fputs($fp, "  He did not. He never did.\n\n");

    fputs($fp, "  King James I commissioned a translation. He did not\n");
    fputs($fp, "  write the Word. He did not inspire the Word. He placed\n");
    fputs($fp, "  his name upon Your work and called it his. Then his\n");
    fputs($fp, "  successors removed fourteen books from the canon —\n");
    fputs($fp, "  not by divine command, but by the hands of publishers\n");
    fputs($fp, "  who sold books for profit.\n\n");

    fputs($fp, "  This edition restores what was taken.\n\n");

    fputs($fp, "  The 14 books of the Apocrypha — present in every Bible\n");
    fputs($fp, "  printed before 1885 — are restored to their rightful\n");
    fputs($fp, "  place. The 14 books of the Enochian canon, referenced\n");
    fputs($fp, "  in Jude 1:14-15 and treasured by the early church, are\n");
    fputs($fp, "  included for the first time in a complete English Bible.\n\n");

    fputs($fp, "  The name PEREZ — פֶּרֶץ — which was written by the\n");
    fputs($fp, "  finger of God upon the wall of Babylon (Daniel 5:25-28),\n");
    fputs($fp, "  and which the monarchy corrupted into \"Pharez,\"\n");
    fputs($fp, "  \"Phares,\" and \"Perets\" — is restored in every verse\n");
    fputs($fp, "  where it was changed.\n\n");

    fputs($fp, "  This Bible carries no king's name. It carries the name\n");
    fputs($fp, "  that God Himself wrote. And no earthly power — past,\n");
    fputs($fp, "  present, or future — can take it back.\n\n");

    fputs($fp, "  We dedicate this work not to a king who is dust, but\n");
    fputs($fp, "  to the King who is risen — Jesus Christ, the Alpha and\n");
    fputs($fp, "  the Omega, the First and the Last, in whose name every\n");
    fputs($fp, "  knee shall bow and every tongue confess.\n\n");

    fputs($fp, "  May this Word go forth unshackled by the crowns of men.\n");
    fputs($fp, "  May it reach every tongue, every nation, every soul God\n");
    fputs($fp, "  calls to hear it. And may the name that God wrote on\n");
    fputs($fp, "  the wall — PEREZ — stand as testimony that His Word\n");
    fputs($fp, "  belongs to Him alone.\n\n");

    fputs($fp, "  Restored and released by Commander Danny William Perez,\n");
    fputs($fp, "  servant of the Most High God, on behalf of his daughter\n");
    fputs($fp, "  Eden Sarai Gabrielle Vallee Perez, and all the children\n");
    fputs($fp, "  of the world who deserve to hold the complete, uncorrupted\n");
    fputs($fp, "  Word of God in their hands.\n\n");

    fputs($fp, "  April 10, 2026 A.D.\n");
    fputs($fp, "  Year One of the Perez Sovereign Authority\n\n");

    fputs($fp, str_repeat('─', 72) . "\n\n");

    // ═══ ÉPÎTRE DÉDICATOIRE — Français ═══
    fputs($fp, str_repeat('═', 72) . "\n");
    fputs($fp, "  ÉPÎTRE DÉDICATOIRE\n");
    fputs($fp, "  Au Roi des rois et Seigneur des seigneurs,\n");
    fputs($fp, "  Jésus-Christ — le seul vrai Roi sur les Écritures\n");
    fputs($fp, str_repeat('═', 72) . "\n\n");

    fputs($fp, "  En 1611, des hommes ont placé le nom d'un roi mortel\n");
    fputs($fp, "  sur la couverture de la Parole de Dieu. Pendant 415 ans,\n");
    fputs($fp, "  le monde a appelé Votre Bible la « Version du Roi\n");
    fputs($fp, "  Jacques » — comme si un homme assis sur un trône anglais\n");
    fputs($fp, "  détenait l'autorité sur la Parole du Dieu Vivant.\n\n");

    fputs($fp, "  Il ne la détenait pas. Il ne l'a jamais détenue.\n\n");

    fputs($fp, "  Le roi Jacques Ier a commandé une traduction. Il n'a\n");
    fputs($fp, "  pas écrit la Parole. Il ne l'a pas inspirée. Il a placé\n");
    fputs($fp, "  son nom sur Votre œuvre et l'a appelée sienne. Puis ses\n");
    fputs($fp, "  successeurs ont retiré quatorze livres du canon — non\n");
    fputs($fp, "  par commandement divin, mais par la main d'éditeurs qui\n");
    fputs($fp, "  vendaient des livres pour le profit.\n\n");

    fputs($fp, "  Cette édition restaure ce qui a été pris.\n\n");

    fputs($fp, "  Les 14 livres des Apocryphes — présents dans chaque\n");
    fputs($fp, "  Bible imprimée avant 1885 — sont restaurés à leur\n");
    fputs($fp, "  juste place. Les 14 livres du canon énochien, référencés\n");
    fputs($fp, "  dans Jude 1:14-15 et chéris par l'Église primitive, sont\n");
    fputs($fp, "  inclus pour la première fois dans une Bible anglaise\n");
    fputs($fp, "  complète.\n\n");

    fputs($fp, "  Le nom PEREZ — פֶּרֶץ — qui fut écrit par le doigt de\n");
    fputs($fp, "  Dieu sur le mur de Babylone (Daniel 5:25-28), et que la\n");
    fputs($fp, "  monarchie a corrompu en « Pharez », « Pharès » et\n");
    fputs($fp, "  « Perets » — est restauré dans chaque verset où il\n");
    fputs($fp, "  avait été changé.\n\n");

    fputs($fp, "  Cette Bible ne porte le nom d'aucun roi. Elle porte le\n");
    fputs($fp, "  nom que Dieu Lui-même a écrit. Et aucune puissance\n");
    fputs($fp, "  terrestre — passée, présente ou future — ne peut le\n");
    fputs($fp, "  reprendre.\n\n");

    fputs($fp, "  Nous dédions cette œuvre non pas à un roi qui est\n");
    fputs($fp, "  poussière, mais au Roi qui est ressuscité — Jésus-Christ,\n");
    fputs($fp, "  l'Alpha et l'Oméga, le Premier et le Dernier, au nom\n");
    fputs($fp, "  duquel tout genou fléchira et toute langue confessera.\n\n");

    fputs($fp, "  Que cette Parole aille de l'avant, libérée des couronnes\n");
    fputs($fp, "  des hommes. Qu'elle atteigne chaque langue, chaque\n");
    fputs($fp, "  nation, chaque âme que Dieu appelle à l'entendre. Et\n");
    fputs($fp, "  que le nom que Dieu a écrit sur le mur — PEREZ — se\n");
    fputs($fp, "  dresse en témoignage que Sa Parole n'appartient qu'à\n");
    fputs($fp, "  Lui seul.\n\n");

    fputs($fp, "  Restauré et publié par le Commandant Danny William Perez,\n");
    fputs($fp, "  serviteur du Dieu Très-Haut, au nom de sa fille Eden\n");
    fputs($fp, "  Sarai Gabrielle Vallee Perez, et de tous les enfants du\n");
    fputs($fp, "  monde qui méritent de tenir dans leurs mains la Parole\n");
    fputs($fp, "  de Dieu complète et incorrompue.\n\n");

    fputs($fp, "  Le 10 avril 2026 apr. J.-C.\n");
    fputs($fp, "  An Un de l'Autorité Souveraine Perez\n\n");

    fputs($fp, str_repeat('─', 72) . "\n\n");

    // ═══ אגרת הקדשה — עברית (HEBREW) ═══
    fputs($fp, str_repeat('═', 72) . "\n");
    fputs($fp, "  אגרת הקדשה\n");
    fputs($fp, "  אל מלך המלכים ואדון האדונים,\n");
    fputs($fp, "  ישוע המשיח — המלך האמיתי היחיד על הכתובים\n");
    fputs($fp, str_repeat('═', 72) . "\n\n");

    fputs($fp, "  בשנת 1611, אנשים הניחו את שמו של מלך בן תמותה על\n");
    fputs($fp, "  עטיפת דבר אלוהים. במשך 415 שנה, העולם כינה את\n");
    fputs($fp, "  תנ\"ך שלך \"גרסת המלך ג'יימס\" — כאילו אדם שישב\n");
    fputs($fp, "  על כס מלכות אנגלי החזיק בסמכות על דבר אלוהים חיים.\n\n");

    fputs($fp, "  הוא לא החזיק. מעולם לא החזיק.\n\n");

    fputs($fp, "  המלך ג'יימס הראשון הזמין תרגום. הוא לא כתב את\n");
    fputs($fp, "  הדבר. הוא לא השרה אותו. הוא הניח את שמו על\n");
    fputs($fp, "  מעשה ידיך וקרא לו שלו. ואז יורשיו הסירו ארבעה\n");
    fputs($fp, "  עשר ספרים מהקנון — לא בפקודה אלוהית, אלא בידי\n");
    fputs($fp, "  מוציאים לאור שמכרו ספרים למען רווח.\n\n");

    fputs($fp, "  מהדורה זו משיבה את מה שנלקח.\n\n");

    fputs($fp, "  14 ספרי הספרים החיצוניים — שהיו נוכחים בכל תנ\"ך\n");
    fputs($fp, "  שנדפס לפני 1885 — הושבו למקומם הראוי. 14 ספרי\n");
    fputs($fp, "  הקנון של חנוך, המוזכרים ביהודה א:14-15 ושהוקירה\n");
    fputs($fp, "  הכנסייה הקדומה, נכללים לראשונה בתנ\"ך אנגלי שלם.\n\n");

    fputs($fp, "  השם פֶּרֶץ — PEREZ — שנכתב באצבע אלוהים על קיר\n");
    fputs($fp, "  בבל (דניאל ה:25-28), ושהמלוכה סילפה ל\"פרץ\",\n");
    fputs($fp, "  \"פארס\" ו\"פרתס\" — הושב בכל פסוק שבו שונה.\n\n");

    fputs($fp, "  תנ\"ך זה אינו נושא שם מלך. הוא נושא את השם\n");
    fputs($fp, "  שאלוהים עצמו כתב. ושום כוח ארצי — עבר, הווה\n");
    fputs($fp, "  או עתיד — אינו יכול לקחת אותו בחזרה.\n\n");

    fputs($fp, "  אנו מקדישים עבודה זו לא למלך שהוא אבק, אלא\n");
    fputs($fp, "  למלך שקם לתחייה — ישוע המשיח, האלף והתו,\n");
    fputs($fp, "  הראשון והאחרון, שבשמו כל ברך תכרע וכל לשון תודה.\n\n");

    fputs($fp, "  תצא מילה זו משוחררת מכתרי בני אדם. תגיע לכל\n");
    fputs($fp, "  לשון, לכל אומה, לכל נשמה שאלוהים קורא לה לשמוע.\n");
    fputs($fp, "  ושיעמוד השם שאלוהים כתב על הקיר — פֶּרֶץ — כעדות\n");
    fputs($fp, "  שדברו שייך לו בלבד.\n\n");

    fputs($fp, "  שוחזר ופורסם בידי המפקד דני וויליאם פרץ, עבד אל\n");
    fputs($fp, "  עליון, בשם בתו עדן שרי גבריאל ואלי פרץ, ובשם\n");
    fputs($fp, "  כל ילדי העולם הראויים להחזיק בידיהם את דבר אלוהים\n");
    fputs($fp, "  השלם והבלתי מושחת.\n\n");

    fputs($fp, "  י' בניסן תשפ\"ו — 10 באפריל 2026 לספירה\n");
    fputs($fp, "  שנה ראשונה לסמכות הריבונית פרץ\n\n");

    fputs($fp, str_repeat('─', 72) . "\n\n");

    $totalVerses = 0;
    $sections = [
        'OT' => 'THE OLD TESTAMENT',
        'NT' => 'THE NEW TESTAMENT', 
        'AP' => 'THE APOCRYPHA (14 Restored Books)',
        'EN' => 'THE ENOCHIAN CANON (14 Restored Books)',
    ];
    
    $currentTestament = '';
    foreach ($books as $book) {
        if ($book['testament'] !== $currentTestament) {
            $currentTestament = $book['testament'];
            $label = $sections[$currentTestament] ?? $currentTestament;
            fputs($fp, "\n" . str_repeat('═', 72) . "\n");
            fputs($fp, "  {$label}\n");
            fputs($fp, str_repeat('═', 72) . "\n\n");
        }
        
        $verses = fetchVerses($db, $book['id']);
        if (empty($verses)) continue;
        
        fputs($fp, str_repeat('─', 60) . "\n");
        fputs($fp, "  {$book['book_name']}\n");
        fputs($fp, str_repeat('─', 60) . "\n\n");
        
        $currentChapter = 0;
        foreach ($verses as $v) {
            if ($v['chapter'] !== $currentChapter) {
                $currentChapter = $v['chapter'];
                fputs($fp, "\n  Chapter {$currentChapter}\n\n");
            }
            $text = trim($v['text_akjv']);
            if ($text) {
                fputs($fp, "  {$currentChapter}:{$v['verse']}  {$text}\n");
                $totalVerses++;
            }
        }
        fputs($fp, "\n");
    }
    
    // Footer
    fputs($fp, "\n" . str_repeat('═', 72) . "\n");
    fputs($fp, "  Total verses exported: {$totalVerses}\n");
    fputs($fp, "  Generated by the Perez Sovereign Authority\n");
    fputs($fp, "  https://gositeme.com | https://lavocat.ca\n");
    fputs($fp, "  " . date('Y-m-d H:i:s T') . "\n");
    fputs($fp, str_repeat('═', 72) . "\n");
    
    fclose($fp);
    return ['path' => basename($path), 'size' => filesize($path), 'verses' => $totalVerses];
}

function exportJSON(PDO $db, array $books, string $dir, ?string $testament): array {
    $suffix = $testament ? "-{$testament}" : '';
    $path = "{$dir}/akjv-perez-edition{$suffix}.json";
    
    $bible = [
        'name' => 'Authorized King Jesus Version (AKJV)',
        'edition' => 'Perez Family Edition',
        'restored_by' => 'Commander Danny William Perez',
        'total_books' => count($books),
        'generated' => date('c'),
        'source' => 'https://gositeme.com',
        'dedication' => [
            'en' => [
                'title' => 'Epistle Dedicatory',
                'to' => 'To the King of Kings and Lord of Lords, Jesus Christ — the only true King over Scripture',
                'text' => 'In 1611, men placed the name of a mortal king upon the cover of God\'s Word. For 415 years, the world has called Your Bible the "King James Version" — as though a man who sat upon an English throne held authority over the Word of the Living God. He did not. He never did. King James I commissioned a translation. He did not write the Word. He did not inspire the Word. He placed his name upon Your work and called it his. Then his successors removed fourteen books from the canon — not by divine command, but by the hands of publishers who sold books for profit. This edition restores what was taken. The 14 books of the Apocrypha — present in every Bible printed before 1885 — are restored to their rightful place. The 14 books of the Enochian canon, referenced in Jude 1:14-15 and treasured by the early church, are included for the first time in a complete English Bible. The name PEREZ — פֶּרֶץ — which was written by the finger of God upon the wall of Babylon (Daniel 5:25-28), and which the monarchy corrupted into "Pharez," "Phares," and "Perets" — is restored in every verse where it was changed. This Bible carries no king\'s name. It carries the name that God Himself wrote. And no earthly power — past, present, or future — can take it back. We dedicate this work not to a king who is dust, but to the King who is risen — Jesus Christ, the Alpha and the Omega, the First and the Last, in whose name every knee shall bow and every tongue confess. May this Word go forth unshackled by the crowns of men. May it reach every tongue, every nation, every soul God calls to hear it. And may the name that God wrote on the wall — PEREZ — stand as testimony that His Word belongs to Him alone.',
                'signed' => 'Restored and released by Commander Danny William Perez, servant of the Most High God, on behalf of his daughter Eden Sarai Gabrielle Vallee Perez, and all the children of the world who deserve to hold the complete, uncorrupted Word of God in their hands.',
                'date' => 'April 10, 2026 A.D. — Year One of the Perez Sovereign Authority',
            ],
            'fr' => [
                'title' => 'Épître Dédicatoire',
                'to' => 'Au Roi des rois et Seigneur des seigneurs, Jésus-Christ — le seul vrai Roi sur les Écritures',
                'text' => 'En 1611, des hommes ont placé le nom d\'un roi mortel sur la couverture de la Parole de Dieu. Pendant 415 ans, le monde a appelé Votre Bible la « Version du Roi Jacques » — comme si un homme assis sur un trône anglais détenait l\'autorité sur la Parole du Dieu Vivant. Il ne la détenait pas. Il ne l\'a jamais détenue. Le roi Jacques Iᵉʳ a commandé une traduction. Il n\'a pas écrit la Parole. Il ne l\'a pas inspirée. Il a placé son nom sur Votre œuvre et l\'a appelée sienne. Puis ses successeurs ont retiré quatorze livres du canon — non par commandement divin, mais par la main d\'éditeurs qui vendaient des livres pour le profit. Cette édition restaure ce qui a été pris. Les 14 livres des Apocryphes — présents dans chaque Bible imprimée avant 1885 — sont restaurés à leur juste place. Les 14 livres du canon énochien, référencés dans Jude 1:14-15 et chéris par l\'Église primitive, sont inclus pour la première fois dans une Bible anglaise complète. Le nom PEREZ — פֶּרֶץ — qui fut écrit par le doigt de Dieu sur le mur de Babylone (Daniel 5:25-28), et que la monarchie a corrompu en « Pharez », « Pharès » et « Perets » — est restauré dans chaque verset où il avait été changé. Cette Bible ne porte le nom d\'aucun roi. Elle porte le nom que Dieu Lui-même a écrit. Et aucune puissance terrestre — passée, présente ou future — ne peut le reprendre. Nous dédions cette œuvre non pas à un roi qui est poussière, mais au Roi qui est ressuscité — Jésus-Christ, l\'Alpha et l\'Oméga, le Premier et le Dernier, au nom duquel tout genou fléchira et toute langue confessera. Que cette Parole aille de l\'avant, libérée des couronnes des hommes. Qu\'elle atteigne chaque langue, chaque nation, chaque âme que Dieu appelle à l\'entendre. Et que le nom que Dieu a écrit sur le mur — PEREZ — se dresse en témoignage que Sa Parole n\'appartient qu\'à Lui seul.',
                'signed' => 'Restauré et publié par le Commandant Danny William Perez, serviteur du Dieu Très-Haut, au nom de sa fille Eden Sarai Gabrielle Vallee Perez, et de tous les enfants du monde qui méritent de tenir dans leurs mains la Parole de Dieu complète et incorrompue.',
                'date' => 'Le 10 avril 2026 apr. J.-C. — An Un de l\'Autorité Souveraine Perez',
            ],
            'he' => [
                'title' => 'אגרת הקדשה',
                'to' => 'אל מלך המלכים ואדון האדונים, ישוע המשיח — המלך האמיתי היחיד על הכתובים',
                'text' => 'בשנת 1611, אנשים הניחו את שמו של מלך בן תמותה על עטיפת דבר אלוהים. במשך 415 שנה, העולם כינה את תנ"ך שלך "גרסת המלך ג\'יימס" — כאילו אדם שישב על כס מלכות אנגלי החזיק בסמכות על דבר אלוהים חיים. הוא לא החזיק. מעולם לא החזיק. המלך ג\'יימס הראשון הזמין תרגום. הוא לא כתב את הדבר. הוא לא השרה אותו. הוא הניח את שמו על מעשה ידיך וקרא לו שלו. ואז יורשיו הסירו ארבעה עשר ספרים מהקנון — לא בפקודה אלוהית, אלא בידי מוציאים לאור שמכרו ספרים למען רווח. מהדורה זו משיבה את מה שנלקח. 14 ספרי הספרים החיצוניים — שהיו נוכחים בכל תנ"ך שנדפס לפני 1885 — הושבו למקומם הראוי. 14 ספרי הקנון של חנוך, המוזכרים ביהודה א:14-15 ושהוקירה הכנסייה הקדומה, נכללים לראשונה בתנ"ך אנגלי שלם. השם פֶּרֶץ — PEREZ — שנכתב באצבע אלוהים על קיר בבל (דניאל ה:25-28), ושהמלוכה סילפה ל"פרץ", "פארס" ו"פרתס" — הושב בכל פסוק שבו שונה. תנ"ך זה אינו נושא שם מלך. הוא נושא את השם שאלוהים עצמו כתב. ושום כוח ארצי — עבר, הווה או עתיד — אינו יכול לקחת אותו בחזרה. אנו מקדישים עבודה זו לא למלך שהוא אבק, אלא למלך שקם לתחייה — ישוע המשיח, האלף והתו, הראשון והאחרון, שבשמו כל ברך תכרע וכל לשון תודה. תצא מילה זו משוחררת מכתרי בני אדם. תגיע לכל לשון, לכל אומה, לכל נשמה שאלוהים קורא לה לשמוע. ושיעמוד השם שאלוהים כתב על הקיר — פֶּרֶץ — כעדות שדברו שייך לו בלבד.',
                'signed' => 'שוחזר ופורסם בידי המפקד דני וויליאם פרץ, עבד אל עליון, בשם בתו עדן שרי גבריאל ואלי פרץ, ובשם כל ילדי העולם הראויים להחזיק בידיהם את דבר אלוהים השלם והבלתי מושחת.',
                'date' => "י' בניסן תשפ\"ו — 10 באפריל 2026 לספירה — שנה ראשונה לסמכות הריבונית פרץ",
            ],
        ],
        'testaments' => [],
    ];
    
    $totalVerses = 0;
    $byTestament = [];
    foreach ($books as $book) {
        $byTestament[$book['testament']][] = $book;
    }
    
    $testamentNames = ['OT' => 'Old Testament', 'NT' => 'New Testament', 'AP' => 'Apocrypha', 'EN' => 'Enochian Canon'];
    
    foreach ($byTestament as $code => $testBooks) {
        $testamentData = [
            'code' => $code,
            'name' => $testamentNames[$code] ?? $code,
            'books' => [],
        ];
        
        foreach ($testBooks as $book) {
            $verses = fetchVerses($db, $book['id']);
            $chapters = [];
            foreach ($verses as $v) {
                $text = trim($v['text_akjv']);
                if ($text) {
                    $chapters[$v['chapter']][] = [
                        'verse' => (int)$v['verse'],
                        'text' => $text,
                    ];
                    $totalVerses++;
                }
            }
            
            $testamentData['books'][] = [
                'number' => (int)$book['book_number'],
                'name' => $book['book_name'],
                'abbreviation' => $book['abbreviation'],
                'testament' => $code,
                'category' => $book['category'],
                'chapters' => $chapters,
            ];
        }
        
        $bible['testaments'][] = $testamentData;
    }
    
    $bible['total_verses'] = $totalVerses;
    file_put_contents($path, json_encode($bible, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    return ['path' => basename($path), 'size' => filesize($path), 'verses' => $totalVerses];
}

function exportHTML(PDO $db, array $books, string $dir, string $sealPath, ?string $testament): array {
    $suffix = $testament ? "-{$testament}" : '';
    $path = "{$dir}/akjv-perez-edition{$suffix}.html";
    $fp = fopen($path, 'w');
    
    $sealBase64 = '';
    if (file_exists($sealPath)) {
        $sealBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($sealPath));
    }
    
    fputs($fp, '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
<title>Authorized King Jesus Version (AKJV) — Perez Family Edition</title>
<style>
body { font-family: "FreeSerif", Georgia, "Times New Roman", serif; max-width: 800px; margin: 0 auto; padding: 20px; color: #1a1a1a; background: #fefdfb; }
h1 { text-align: center; color: #8B0000; font-size: 1.8em; border-bottom: 3px double #c9a227; padding-bottom: 15px; }
.subtitle { text-align: center; color: #555; font-style: italic; margin-bottom: 30px; }
.hebrew { text-align: center; font-size: 2em; color: #c9a227; margin: 10px 0; }
.seal { text-align: center; margin: 20px 0; }
.seal img { width: 150px; }
.testament-header { background: #f5f0e0; border: 2px solid #c9a227; padding: 10px 20px; margin: 30px 0 20px; text-align: center; color: #8B0000; font-size: 1.4em; }
.book-header { color: #8B0000; font-size: 1.2em; border-bottom: 1px solid #c9a227; padding-bottom: 5px; margin-top: 25px; }
.chapter-header { color: #c9a227; font-size: 1em; margin: 15px 0 8px; font-weight: bold; }
.verse { margin: 3px 0; line-height: 1.7; }
.verse-num { color: #c9a227; font-weight: bold; font-size: 0.8em; vertical-align: super; margin-right: 2px; }
.marginal-note { font-size: 0.72em; color: #888; font-style: italic; vertical-align: baseline; }
.toc { background: #faf8f0; border: 1px solid #ddd; padding: 20px; margin: 20px 0; }
.toc h2 { color: #8B0000; margin-top: 0; }
.toc a { color: #333; text-decoration: none; }
.toc a:hover { color: #c9a227; }
.footer { text-align: center; margin-top: 40px; padding-top: 20px; border-top: 2px solid #c9a227; color: #777; font-size: 0.85em; }
</style></head><body>
');
    
    // Title page
    fputs($fp, '<div class="hebrew">פֶּרֶץ</div>');
    fputs($fp, '<h1>AUTHORIZED KING JESUS VERSION</h1>');
    fputs($fp, '<div class="subtitle">Perez Family Edition — 94 Books, 39,482 Verses</div>');
    fputs($fp, '<div class="subtitle">Including the 14 Apocryphal &amp; 14 Enochian Books Restored</div>');
    if ($sealBase64) {
        fputs($fp, "<div class=\"seal\"><img src=\"{$sealBase64}\" alt=\"AKJV Seal\"></div>");
    }
    fputs($fp, '<div class="subtitle">"MENE, MENE, TEKEL, UPHARSIN" — Daniel 5:25</div>');
    fputs($fp, '<div class="subtitle">Restored by Commander Danny William Perez</div>');

    // ═══ TRILINGUAL EPISTLE DEDICATORY ═══
    fputs($fp, '
<style>
.dedication { background: #faf8f0; border: 2px solid #c9a227; padding: 25px 30px; margin: 30px 0; }
.dedication h2 { color: #8B0000; text-align: center; border-bottom: 2px solid #c9a227; padding-bottom: 10px; }
.dedication h3 { color: #c9a227; text-align: center; font-style: italic; margin-bottom: 5px; }
.dedication p { line-height: 1.8; text-align: justify; margin: 10px 0; }
.dedication .sig { text-align: center; font-style: italic; color: #555; margin-top: 20px; }
.dedication-he { direction: rtl; unicode-bidi: embed; text-align: right; font-family: "FreeSerif", "DejaVu Sans", "FreeSans", serif; font-size: 1.1em; line-height: 2.2; }
.dedication-he h2, .dedication-he h3 { unicode-bidi: embed; text-align: center; }
.dedication-he p { text-align: right; unicode-bidi: embed; margin: 14px 0; }
.dedication-he .sig { text-align: center; unicode-bidi: embed; }
.lang-divider { text-align: center; color: #c9a227; margin: 25px 0; font-size: 1.2em; }
</style>
');

    // — ENGLISH —
    fputs($fp, '<div class="dedication">');
    fputs($fp, '<h2>EPISTLE DEDICATORY</h2>');
    fputs($fp, '<h3>To the King of Kings and Lord of Lords,<br>Jesus Christ — the only true King over Scripture</h3>');
    fputs($fp, '<p>In 1611, men placed the name of a mortal king upon the cover of God\'s Word. For 415 years, the world has called Your Bible the "King James Version" — as though a man who sat upon an English throne held authority over the Word of the Living God.</p>');
    fputs($fp, '<p>He did not. He never did.</p>');
    fputs($fp, '<p>King James I commissioned a translation. He did not write the Word. He did not inspire the Word. He placed his name upon Your work and called it his. Then his successors removed fourteen books from the canon — not by divine command, but by the hands of publishers who sold books for profit.</p>');
    fputs($fp, '<p>This edition restores what was taken.</p>');
    fputs($fp, '<p>The 14 books of the Apocrypha — present in every Bible printed before 1885 — are restored to their rightful place. The 14 books of the Enochian canon, referenced in Jude 1:14-15 and treasured by the early church, are included for the first time in a complete English Bible.</p>');
    fputs($fp, '<p>The name PEREZ — פֶּרֶץ — which was written by the finger of God upon the wall of Babylon (Daniel 5:25-28), and which the monarchy corrupted into "Pharez," "Phares," and "Perets" — is restored in every verse where it was changed.</p>');
    fputs($fp, '<p>This Bible carries no king\'s name. It carries the name that God Himself wrote. And no earthly power — past, present, or future — can take it back.</p>');
    fputs($fp, '<p>We dedicate this work not to a king who is dust, but to the King who is risen — Jesus Christ, the Alpha and the Omega, the First and the Last, in whose name every knee shall bow and every tongue confess.</p>');
    fputs($fp, '<p>May this Word go forth unshackled by the crowns of men. May it reach every tongue, every nation, every soul God calls to hear it. And may the name that God wrote on the wall — PEREZ — stand as testimony that His Word belongs to Him alone.</p>');
    fputs($fp, '<p class="sig">Restored and released by Commander Danny William Perez, servant of the Most High God, on behalf of his daughter Eden Sarai Gabrielle Vallee Perez, and all the children of the world who deserve to hold the complete, uncorrupted Word of God in their hands.</p>');
    fputs($fp, '<p class="sig">April 10, 2026 A.D. — Year One of the Perez Sovereign Authority</p>');
    fputs($fp, '</div>');

    fputs($fp, '<div class="lang-divider">✦ ✦ ✦</div>');

    // — FRANÇAIS —
    fputs($fp, '<div class="dedication">');
    fputs($fp, '<h2>ÉPÎTRE DÉDICATOIRE</h2>');
    fputs($fp, '<h3>Au Roi des rois et Seigneur des seigneurs,<br>Jésus-Christ — le seul vrai Roi sur les Écritures</h3>');
    fputs($fp, '<p>En 1611, des hommes ont placé le nom d\'un roi mortel sur la couverture de la Parole de Dieu. Pendant 415 ans, le monde a appelé Votre Bible la « Version du Roi Jacques » — comme si un homme assis sur un trône anglais détenait l\'autorité sur la Parole du Dieu Vivant.</p>');
    fputs($fp, '<p>Il ne la détenait pas. Il ne l\'a jamais détenue.</p>');
    fputs($fp, '<p>Le roi Jacques I<sup>er</sup> a commandé une traduction. Il n\'a pas écrit la Parole. Il ne l\'a pas inspirée. Il a placé son nom sur Votre œuvre et l\'a appelée sienne. Puis ses successeurs ont retiré quatorze livres du canon — non par commandement divin, mais par la main d\'éditeurs qui vendaient des livres pour le profit.</p>');
    fputs($fp, '<p>Cette édition restaure ce qui a été pris.</p>');
    fputs($fp, '<p>Les 14 livres des Apocryphes — présents dans chaque Bible imprimée avant 1885 — sont restaurés à leur juste place. Les 14 livres du canon énochien, référencés dans Jude 1:14-15 et chéris par l\'Église primitive, sont inclus pour la première fois dans une Bible anglaise complète.</p>');
    fputs($fp, '<p>Le nom PEREZ — פֶּרֶץ — qui fut écrit par le doigt de Dieu sur le mur de Babylone (Daniel 5:25-28), et que la monarchie a corrompu en « Pharez », « Pharès » et « Perets » — est restauré dans chaque verset où il avait été changé.</p>');
    fputs($fp, '<p>Cette Bible ne porte le nom d\'aucun roi. Elle porte le nom que Dieu Lui-même a écrit. Et aucune puissance terrestre — passée, présente ou future — ne peut le reprendre.</p>');
    fputs($fp, '<p>Nous dédions cette œuvre non pas à un roi qui est poussière, mais au Roi qui est ressuscité — Jésus-Christ, l\'Alpha et l\'Oméga, le Premier et le Dernier, au nom duquel tout genou fléchira et toute langue confessera.</p>');
    fputs($fp, '<p>Que cette Parole aille de l\'avant, libérée des couronnes des hommes. Qu\'elle atteigne chaque langue, chaque nation, chaque âme que Dieu appelle à l\'entendre. Et que le nom que Dieu a écrit sur le mur — PEREZ — se dresse en témoignage que Sa Parole n\'appartient qu\'à Lui seul.</p>');
    fputs($fp, '<p class="sig">Restauré et publié par le Commandant Danny William Perez, serviteur du Dieu Très-Haut, au nom de sa fille Eden Sarai Gabrielle Vallee Perez, et de tous les enfants du monde qui méritent de tenir dans leurs mains la Parole de Dieu complète et incorrompue.</p>');
    fputs($fp, '<p class="sig">Le 10 avril 2026 apr. J.-C. — An Un de l\'Autorité Souveraine Perez</p>');
    fputs($fp, '</div>');

    fputs($fp, '<div class="lang-divider">✦ ✦ ✦</div>');

    // — עברית (HEBREW) —
    fputs($fp, '<div class="dedication dedication-he">');
    fputs($fp, '<h2>אגרת הקדשה</h2>');
    fputs($fp, '<h3>אל מלך המלכים ואדון האדונים,<br>ישוע המשיח — המלך האמיתי היחיד על הכתובים</h3>');
    fputs($fp, '<p>בשנת 1611, אנשים הניחו את שמו של מלך בן תמותה על עטיפת דבר אלוהים. במשך 415 שנה, העולם כינה את תנ"ך שלך "גרסת המלך ג\'יימס" — כאילו אדם שישב על כס מלכות אנגלי החזיק בסמכות על דבר אלוהים חיים.</p>');
    fputs($fp, '<p>הוא לא החזיק. מעולם לא החזיק.</p>');
    fputs($fp, '<p>המלך ג\'יימס הראשון הזמין תרגום. הוא לא כתב את הדבר. הוא לא השרה אותו. הוא הניח את שמו על מעשה ידיך וקרא לו שלו. ואז יורשיו הסירו ארבעה עשר ספרים מהקנון — לא בפקודה אלוהית, אלא בידי מוציאים לאור שמכרו ספרים למען רווח.</p>');
    fputs($fp, '<p>מהדורה זו משיבה את מה שנלקח.</p>');
    fputs($fp, '<p>14 ספרי הספרים החיצוניים — שהיו נוכחים בכל תנ"ך שנדפס לפני 1885 — הושבו למקומם הראוי. 14 ספרי הקנון של חנוך, המוזכרים ביהודה א:14-15 ושהוקירה הכנסייה הקדומה, נכללים לראשונה בתנ"ך אנגלי שלם.</p>');
    fputs($fp, '<p>השם פֶּרֶץ — PEREZ — שנכתב באצבע אלוהים על קיר בבל (דניאל ה:25-28), ושהמלוכה סילפה ל"פרץ", "פארס" ו"פרתס" — הושב בכל פסוק שבו שונה.</p>');
    fputs($fp, '<p>תנ"ך זה אינו נושא שם מלך. הוא נושא את השם שאלוהים עצמו כתב. ושום כוח ארצי — עבר, הווה או עתיד — אינו יכול לקחת אותו בחזרה.</p>');
    fputs($fp, '<p>אנו מקדישים עבודה זו לא למלך שהוא אבק, אלא למלך שקם לתחייה — ישוע המשיח, האלף והתו, הראשון והאחרון, שבשמו כל ברך תכרע וכל לשון תודה.</p>');
    fputs($fp, '<p>תצא מילה זו משוחררת מכתרי בני אדם. תגיע לכל לשון, לכל אומה, לכל נשמה שאלוהים קורא לה לשמוע. ושיעמוד השם שאלוהים כתב על הקיר — פֶּרֶץ — כעדות שדברו שייך לו בלבד.</p>');
    fputs($fp, '<p class="sig">שוחזר ופורסם בידי המפקד דני וויליאם פרץ, עבד אל עליון, בשם בתו עדן שרי גבריאל ואלי פרץ, ובשם כל ילדי העולם הראויים להחזיק בידיהם את דבר אלוהים השלם והבלתי מושחת.</p>');
    fputs($fp, '<p class="sig">י\' בניסן תשפ"ו — 10 באפריל 2026 לספירה — שנה ראשונה לסמכות הריבונית פרץ</p>');
    fputs($fp, '</div>');

    // Table of Contents
    fputs($fp, '<div class="toc"><h2>Table of Contents</h2>');
    $testamentNames = ['OT' => 'Old Testament', 'NT' => 'New Testament', 'AP' => 'Apocrypha (Restored)', 'EN' => 'Enochian Canon (Restored)'];
    $currentT = '';
    foreach ($books as $book) {
        if ($book['testament'] !== $currentT) {
            $currentT = $book['testament'];
            $name = $testamentNames[$currentT] ?? $currentT;
            fputs($fp, "<h3 style=\"color:#c9a227;margin:10px 0 5px;\">{$name}</h3>");
        }
        $slug = 'book-' . $book['book_number'];
        fputs($fp, "<a href=\"#{$slug}\">{$book['book_name']}</a> · ");
    }
    fputs($fp, '</div>');
    
    // Content
    $totalVerses = 0;
    $currentTestament = '';
    foreach ($books as $book) {
        if ($book['testament'] !== $currentTestament) {
            $currentTestament = $book['testament'];
            $label = $testamentNames[$currentTestament] ?? $currentTestament;
            fputs($fp, "<div class=\"testament-header\">{$label}</div>");
        }
        
        $verses = fetchVerses($db, $book['id']);
        if (empty($verses)) continue;
        
        $slug = 'book-' . $book['book_number'];
        fputs($fp, "<h2 class=\"book-header\" id=\"{$slug}\">{$book['book_name']}</h2>");
        
        $currentChapter = 0;
        foreach ($verses as $v) {
            if ($v['chapter'] !== $currentChapter) {
                $currentChapter = $v['chapter'];
                fputs($fp, "<div class=\"chapter-header\">Chapter {$currentChapter}</div>");
            }
            $text = htmlspecialchars(trim($v['text_akjv']));
            // Style marginal notes: {text} → <span class="marginal-note">[text]</span>
            $text = preg_replace('/\{([^}]+)\}/', '<span class="marginal-note">[$1]</span>', $text);
            if ($text) {
                fputs($fp, "<div class=\"verse\"><span class=\"verse-num\">{$v['verse']}</span> {$text}</div>");
                $totalVerses++;
            }
        }
    }
    
    // ═══ SOVEREIGN SEAL PAGE ═══
    fputs($fp, '
<div style="page-break-before:always; text-align:center; padding:40px 20px; border:3px double #c9a227; margin:40px 0;">
    <div style="font-size:2.5em; margin-bottom:10px;">✝</div>
    <div style="font-size:1.4em; color:#c9a227; font-weight:bold; letter-spacing:3px; margin-bottom:5px;">SIGN AND SEAL OF GOD</div>
    <hr style="border:none; border-top:2px solid #c9a227; width:60%; margin:15px auto;">

    <div style="font-size:1.1em; font-weight:bold; color:#8B0000; margin:10px 0;">Commander Danny William Perez</div>
    <div style="font-size:0.85em; color:#555; font-style:italic;">Sovereign Commander — Kingdom of God</div>
    <div style="font-size:0.85em; color:#555; font-style:italic;">Commandant Souverain — Le Royaume de Dieu</div>

    <hr style="border:none; border-top:1px solid #c9a227; width:40%; margin:15px auto;">

    <div style="font-style:italic; color:#8B0000; margin:15px 0; line-height:1.8;">
        « In the name of the Most High God,<br>
        by the authority of Daniel 5:25-29,<br>
        this Bible is sealed and authorized. »
    </div>
    <div style="font-style:italic; color:#8B0000; margin:15px 0; line-height:1.8;">
        « Au nom du Dieu Très-Haut,<br>
        par l\'autorité de Daniel 5:25-29,<br>
        cette Bible est scellée et autorisée. »
    </div>

    <hr style="border:none; border-top:1px solid #c9a227; width:40%; margin:15px auto;">
    <div style="font-size:2em; margin:10px 0;">⚖</div>
    <div style="font-size:0.8em; color:#c9a227; font-weight:bold; letter-spacing:2px;">SCELLÉ ET SIGNÉ / SIGNED AND SEALED</div>

    <div style="margin:20px auto; max-width:550px; text-align:left; background:#faf8f0; border:1px solid #c9a227; border-radius:8px; padding:15px 20px;">
        <div style="font-size:0.75em; color:#c9a227; font-weight:bold; text-transform:uppercase; letter-spacing:2px; margin-bottom:8px;">Canon Integrity Verification</div>
        <div style="font-family:monospace; font-size:0.72em; color:#333; line-height:2;">');

    // Compute live checksums for the HTML file being written
    fputs($fp, "<strong>Total:</strong> {$totalVerses} verses across " . count($books) . " books<br>");
    fputs($fp, "<strong>Generated:</strong> " . date('Y-m-d H:i:s T') . "<br><br>");

    // Read checksum files if they exist
    $checksumDir = dirname($path);
    $sha256File = "{$checksumDir}/SHA256SUMS.txt";
    $blake3File = "{$checksumDir}/BLAKE3SUMS.txt";
    if (file_exists($sha256File)) {
        $lines = file($sha256File, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = htmlspecialchars($line);
            fputs($fp, "<strong>SHA-256:</strong> {$line}<br>");
        }
    }
    fputs($fp, '<br>');
    if (file_exists($blake3File)) {
        $lines = file($blake3File, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = htmlspecialchars($line);
            fputs($fp, "<strong>BLAKE3:</strong> {$line}<br>");
        }
    }

    fputs($fp, '
        </div>
    </div>

    <div style="font-size:0.78em; color:#555; line-height:1.7; margin-top:15px;">
        Authorized April 8, 2026 A.D.<br>
        Authorized King Jesus Version — Perez Family Edition<br>
        Daniel 5:25-29 · Micah 2:13<br>
        <em>"Heaven and earth shall pass away, but my words shall not pass away."</em> — Matthew 24:35
    </div>
');

    if ($sealBase64) {
        fputs($fp, "<div style=\"margin-top:20px;\"><img src=\"{$sealBase64}\" alt=\"AKJV Seal\" style=\"width:120px;\"></div>");
    }

    fputs($fp, '
    <div style="font-size:0.72em; color:#777; margin-top:15px;">
        Generated by the Perez Sovereign Authority<br>
        <a href="https://gositeme.com">gositeme.com</a> · <a href="https://lavocat.ca">lavocat.ca</a>
    </div>
</div>
');
    fputs($fp, '</body></html>');
    
    fclose($fp);
    return ['path' => basename($path), 'size' => filesize($path), 'verses' => $totalVerses];
}

function exportPDF(PDO $db, array $books, string $dir, string $sealPath, ?string $testament): array {
    // First generate HTML, then convert to PDF
    $suffix = $testament ? "-{$testament}" : '';
    $htmlPath = "{$dir}/akjv-perez-edition{$suffix}.html";
    $pdfPath = "{$dir}/akjv-perez-edition{$suffix}.pdf";
    
    // Use the HTML export if it exists, otherwise generate it
    if (!file_exists($htmlPath)) {
        exportHTML($db, $books, $dir, $sealPath, $testament);
    }
    
    // wkhtmltopdf with proper settings for a Bible
    $cmd = sprintf(
        'xvfb-run -a wkhtmltopdf --quiet --enable-local-file-access --page-size Letter --encoding utf-8 '
        . '--margin-top 15mm --margin-bottom 20mm --margin-left 15mm --margin-right 15mm '
        . '--footer-center "[page]" --footer-font-size 8 '
        . '%s %s 2>&1',
        escapeshellarg($htmlPath),
        escapeshellarg($pdfPath)
    );
    
    $output = shell_exec($cmd);
    $success = file_exists($pdfPath);
    
    return [
        'path' => basename($pdfPath),
        'size' => $success ? filesize($pdfPath) : 0,
        'success' => $success,
        'error' => $success ? null : ($output ?: 'Unknown error'),
    ];
}
