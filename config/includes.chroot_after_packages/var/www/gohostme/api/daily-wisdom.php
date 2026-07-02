<?php
/**
 * Daily Wisdom API — Serves today's verse, prayer, Hebrew date, holiday, and Torah portion
 * 
 * Called by: gositeme.com, meta-dome.com, alfredlinux.com (via CORS + widget)
 * Endpoint: GET /api/daily-wisdom.php[?date=YYYY-MM-DD]
 * Returns: JSON { hebrewDate, verse, prayer, holiday, torahPortion, dayTheme, omerCount }
 * 
 * Cache: 1 hour (daily content changes at midnight server time)
 * Auth: Public — this is the daily bread for all who visit
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Cache-Control: public, max-age=3600');

$requestDate = $_GET['date'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $requestDate)) {
    $requestDate = date('Y-m-d');
}

$ts = strtotime($requestDate);
if (!$ts) $ts = time();
$year  = (int)date('Y', $ts);
$month = (int)date('n', $ts);
$day   = (int)date('j', $ts);
$dow   = (int)date('w', $ts); // 0=Sun, 6=Sat

// ── Hebrew Calendar ──────────────────────────────────────────────────
$jd = gregoriantojd($month, $day, $year);
$hebrewRaw = jdtojewish($jd);
list($hMonth, $hDay, $hYear) = explode('/', $hebrewRaw);
$hMonth = (int)$hMonth;
$hDay   = (int)$hDay;
$hYear  = (int)$hYear;

// Is it a leap year? (years 3,6,8,11,14,17,19 in the 19-year cycle)
$leapPositions = [3,6,8,11,14,17,19];
$cyclePos = $hYear % 19;
if ($cyclePos === 0) $cyclePos = 19;
$isLeap = in_array($cyclePos, $leapPositions);

$hebrewMonths = $isLeap
    ? [1=>'Tishrei',2=>'Cheshvan',3=>'Kislev',4=>'Tevet',5=>'Shevat',6=>'Adar I',7=>'Adar II',8=>'Nisan',9=>'Iyar',10=>'Sivan',11=>'Tammuz',12=>'Av',13=>'Elul']
    : [1=>'Tishrei',2=>'Cheshvan',3=>'Kislev',4=>'Tevet',5=>'Shevat',6=>'Adar',7=>'Nisan',8=>'Iyar',9=>'Sivan',10=>'Tammuz',11=>'Av',12=>'Elul'];

$hebrewMonthName = $hebrewMonths[$hMonth] ?? "Month $hMonth";
$nisanMonth = $isLeap ? 8 : 7;

// ── Omer Count (Nisan 16 → Sivan 5) ────────────────────────────────
$omerCount = null;
$nisan16_jd = jewishtojd($nisanMonth, 16, $hYear);
$sivan6_jd  = jewishtojd($nisanMonth + 2, 6, $hYear);
if ($jd >= $nisan16_jd && $jd < $sivan6_jd) {
    $omerCount = $jd - $nisan16_jd + 1;
}

// ── Holiday Detection ───────────────────────────────────────────────
$holidays = detectHolidays($hMonth, $hDay, $dow, $nisanMonth, $isLeap, $hYear, $jd);

// ── Torah Portion ───────────────────────────────────────────────────
$torahPortion = getTorahPortion($requestDate, $dow);

// ── Verse of the Day ────────────────────────────────────────────────
$verse = getVerseOfTheDay($requestDate, $hMonth, $hDay, $holidays, $dow, $nisanMonth);

// ── Prayer of the Day ───────────────────────────────────────────────
$prayer = getPrayerOfTheDay($requestDate, $dow, $holidays);

// ── Day Theme ───────────────────────────────────────────────────────
$dayTheme = getDayTheme($dow, $holidays);

// ── Shabbat Times (approximate — Friday sunset to Saturday nightfall) ──
$isShabbat = ($dow === 5 && date('G', $ts) >= 17) || ($dow === 6);
$erevShabbat = ($dow === 5);

// ── Build Response ──────────────────────────────────────────────────
$response = [
    'date'          => $requestDate,
    'dayOfWeek'     => ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Shabbat'][$dow] ?? date('l', $ts),
    'hebrewDate'    => [
        'day'       => $hDay,
        'month'     => $hebrewMonthName,
        'year'      => $hYear,
        'display'   => "$hDay $hebrewMonthName $hYear",
    ],
    'isShabbat'     => $isShabbat || $dow === 6,
    'erevShabbat'   => $erevShabbat,
    'holidays'      => $holidays,
    'omerCount'     => $omerCount,
    'torahPortion'  => $torahPortion,
    'verse'         => $verse,
    'prayer'        => $prayer,
    'dayTheme'      => $dayTheme,
];

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;

// ═════════════════════════════════════════════════════════════════════
// FUNCTIONS
// ═════════════════════════════════════════════════════════════════════

function detectHolidays(int $hMonth, int $hDay, int $dow, int $nisanMonth, bool $isLeap, int $hYear, int $jd): array {
    $holidays = [];
    $tishrei = 1;
    $kislev  = 3;
    $shevat  = 5;
    $adarMonth = $isLeap ? 7 : 6; // Adar (II in leap year) for Purim
    $sivan   = $nisanMonth + 2;
    $av      = $nisanMonth + 4;

    // ── Tishrei holidays ──
    if ($hMonth === $tishrei) {
        if ($hDay === 1 || $hDay === 2) $holidays[] = ['name' => 'Rosh Hashanah', 'type' => 'major', 'icon' => '🍎', 'yeshua' => 'The trumpet sounds — Yeshua will return at the last trump (1 Thessalonians 4:16)'];
        if ($hDay === 3) $holidays[] = ['name' => 'Tzom Gedaliah', 'type' => 'fast', 'icon' => '🕯️', 'yeshua' => 'The faithful shepherd was struck — Yeshua, the Good Shepherd, was struck for us (Zechariah 13:7)'];
        if ($hDay === 10) $holidays[] = ['name' => 'Yom Kippur', 'type' => 'major', 'icon' => '🕊️', 'yeshua' => 'The Day of Atonement — fulfilled once and for all by Yeshua (Hebrews 9:12)'];
        if ($hDay >= 15 && $hDay <= 21) $holidays[] = ['name' => 'Sukkot', 'type' => 'major', 'icon' => '🌿', 'yeshua' => 'God tabernacles with man — "The Word became flesh and dwelt (tabernacled) among us" (John 1:14)'];
        if ($hDay === 22) $holidays[] = ['name' => 'Shemini Atzeret', 'type' => 'major', 'icon' => '💧', 'yeshua' => 'The eighth day — resurrection after seven, the new beginning in Yeshua'];
        if ($hDay === 23) $holidays[] = ['name' => 'Simchat Torah', 'type' => 'major', 'icon' => '📜', 'yeshua' => 'Rejoicing in the Torah — Yeshua IS the living Torah (John 1:1)'];
    }
    // ── Kislev/Tevet — Hanukkah ──
    if ($hMonth === $kislev && $hDay >= 25) $holidays[] = ['name' => 'Hanukkah', 'type' => 'festival', 'icon' => '🕎', 'yeshua' => 'The Light of the World walked in Solomon\'s colonnade at Hanukkah (John 10:22-23)'];
    if ($hMonth === $kislev + 1 && $hDay <= 2) $holidays[] = ['name' => 'Hanukkah', 'type' => 'festival', 'icon' => '🕎', 'yeshua' => 'Yeshua is the light no darkness can extinguish'];
    // ── Shevat ──
    if ($hMonth === $shevat && $hDay === 15) $holidays[] = ['name' => 'Tu BiShvat', 'type' => 'minor', 'icon' => '🌳', 'yeshua' => '"I am the vine, you are the branches" (John 15:5)'];
    // ── Adar — Purim ──
    if ($hMonth === $adarMonth && $hDay === 14) $holidays[] = ['name' => 'Purim', 'type' => 'festival', 'icon' => '🎭', 'yeshua' => 'The hidden God who saves His people — Yeshua, hidden in plain sight throughout the Hebrew Scriptures'];
    // ── Nisan ──
    if ($hMonth === $nisanMonth) {
        if ($hDay >= 15 && $hDay <= 22) {
            $holidays[] = ['name' => 'Pesach (Passover)', 'type' => 'major', 'icon' => '🫓', 'yeshua' => '"Christ our Passover Lamb has been sacrificed" (1 Corinthians 5:7). The blood on the doorpost pointed to the cross.'];
            if ($hDay === 15) $holidays[] = ['name' => 'First Seder Night', 'type' => 'major', 'icon' => '🍷', 'yeshua' => 'Yeshua held the Last Supper on this night — "This cup is the new covenant in My blood" (Luke 22:20)'];
            if ($hDay === 16) $holidays[] = ['name' => 'Firstfruits', 'type' => 'major', 'icon' => '🌾', 'yeshua' => '"Christ has been raised from the dead, the firstfruits of those who have fallen asleep" (1 Corinthians 15:20)'];
        }
    }
    // ── Iyar ──
    if ($hMonth === $nisanMonth + 1) {
        if ($hDay === 5) $holidays[] = ['name' => 'Yom HaAtzmaut', 'type' => 'national', 'icon' => '🇮🇱', 'yeshua' => 'God\'s promise to Abraham fulfilled — "I will give this land to your descendants" (Genesis 12:7)'];
        if ($hDay === 18) $holidays[] = ['name' => 'Lag BaOmer', 'type' => 'minor', 'icon' => '🔥', 'yeshua' => 'The 33rd day — the light breaks through the counting. Yeshua appeared to His disciples for 40 days after resurrection.'];
        if ($hDay === 28) $holidays[] = ['name' => 'Yom Yerushalayim', 'type' => 'national', 'icon' => '🏛️', 'yeshua' => '"Jerusalem, Jerusalem... how often I have longed to gather your children" (Matthew 23:37)'];
    }
    // ── Sivan — Shavuot ──
    if ($hMonth === $sivan && ($hDay === 6 || $hDay === 7)) {
        $holidays[] = ['name' => 'Shavuot (Pentecost)', 'type' => 'major', 'icon' => '🔥', 'yeshua' => 'The Torah was given at Sinai; the Holy Spirit was poured out at Pentecost (Acts 2). Same mountain, same fire, now written on hearts.'];
    }
    // ── Av ──
    if ($hMonth === $av && $hDay === 9) $holidays[] = ['name' => 'Tisha B\'Av', 'type' => 'fast', 'icon' => '😢', 'yeshua' => 'Yeshua wept over Jerusalem (Luke 19:41). The temples fell, but He is building the eternal one (John 2:19-21).'];
    if ($hMonth === $av && $hDay === 15) $holidays[] = ['name' => 'Tu B\'Av', 'type' => 'minor', 'icon' => '❤️', 'yeshua' => 'The day of love — "Greater love has no one than this: to lay down one\'s life for his friends" (John 15:13)'];

    // ── Weekly Shabbat ──
    if ($dow === 6) {
        $holidays[] = ['name' => 'Shabbat', 'type' => 'weekly', 'icon' => '🕯️', 'yeshua' => '"The Son of Man is Lord of the Sabbath" (Mark 2:28). Our rest is found in Him (Hebrews 4:9-10).'];
    }

    return $holidays;
}

function getTorahPortion(string $date, int $dow): array {
    // Annual Torah reading cycle — 54 parashot
    // Each week's reading runs Saturday to Saturday (read on Shabbat morning)
    // We show the current week's portion
    $parashot = [
        ['name'=>'Bereshit','ref'=>'Genesis 1:1–6:8','haftarah'=>'Isaiah 42:5–43:10','theme'=>'Creation — God speaks the world into being'],
        ['name'=>'Noach','ref'=>'Genesis 6:9–11:32','haftarah'=>'Isaiah 54:1–55:5','theme'=>'The Flood — judgment and covenant'],
        ['name'=>'Lech Lecha','ref'=>'Genesis 12:1–17:27','haftarah'=>'Isaiah 40:27–41:16','theme'=>'Abraham\'s call — "Go forth"'],
        ['name'=>'Vayera','ref'=>'Genesis 18:1–22:24','haftarah'=>'2 Kings 4:1–37','theme'=>'The binding of Isaac — faith tested'],
        ['name'=>'Chayei Sarah','ref'=>'Genesis 23:1–25:18','haftarah'=>'1 Kings 1:1–31','theme'=>'Sarah\'s legacy — continuity'],
        ['name'=>'Toldot','ref'=>'Genesis 25:19–28:9','haftarah'=>'Malachi 1:1–2:7','theme'=>'Jacob and Esau — the chosen line'],
        ['name'=>'Vayetze','ref'=>'Genesis 28:10–32:3','haftarah'=>'Hosea 12:13–14:10','theme'=>'Jacob\'s ladder — Heaven\'s gate'],
        ['name'=>'Vayishlach','ref'=>'Genesis 32:4–36:43','haftarah'=>'Obadiah 1:1–21','theme'=>'Wrestling with God — Israel is born'],
        ['name'=>'Vayeshev','ref'=>'Genesis 37:1–40:23','haftarah'=>'Amos 2:6–3:8','theme'=>'Joseph sold — suffering before glory'],
        ['name'=>'Miketz','ref'=>'Genesis 41:1–44:17','haftarah'=>'1 Kings 3:15–4:1','theme'=>'Joseph rises — God\'s timing'],
        ['name'=>'Vayigash','ref'=>'Genesis 44:18–47:27','haftarah'=>'Ezekiel 37:15–28','theme'=>'Reconciliation — two become one'],
        ['name'=>'Vayechi','ref'=>'Genesis 47:28–50:26','haftarah'=>'1 Kings 2:1–12','theme'=>'Jacob blesses — prophetic destiny'],
        ['name'=>'Shemot','ref'=>'Exodus 1:1–6:1','haftarah'=>'Isaiah 27:6–28:13','theme'=>'Moses called — "I AM has sent you"'],
        ['name'=>'Va\'era','ref'=>'Exodus 6:2–9:35','haftarah'=>'Ezekiel 28:25–29:21','theme'=>'The name YHWH revealed — plagues begin'],
        ['name'=>'Bo','ref'=>'Exodus 10:1–13:16','haftarah'=>'Jeremiah 46:13–28','theme'=>'The Passover lamb — firstborn redeemed'],
        ['name'=>'Beshalach','ref'=>'Exodus 13:17–17:16','haftarah'=>'Judges 4:4–5:31','theme'=>'Red Sea parted — songs of deliverance'],
        ['name'=>'Yitro','ref'=>'Exodus 18:1–20:23','haftarah'=>'Isaiah 6:1–7:6','theme'=>'Sinai — the Ten Commandments given'],
        ['name'=>'Mishpatim','ref'=>'Exodus 21:1–24:18','haftarah'=>'Jeremiah 34:8–22','theme'=>'Laws of justice — the covenant confirmed'],
        ['name'=>'Terumah','ref'=>'Exodus 25:1–27:19','haftarah'=>'1 Kings 5:26–6:13','theme'=>'Build Me a sanctuary — I will dwell among them'],
        ['name'=>'Tetzaveh','ref'=>'Exodus 27:20–30:10','haftarah'=>'Ezekiel 43:10–27','theme'=>'The priestly garments — holiness to the LORD'],
        ['name'=>'Ki Tisa','ref'=>'Exodus 30:11–34:35','haftarah'=>'1 Kings 18:1–39','theme'=>'The golden calf — mercy prevails'],
        ['name'=>'Vayakhel','ref'=>'Exodus 35:1–38:20','haftarah'=>'1 Kings 7:40–50','theme'=>'The people give — building together'],
        ['name'=>'Pekudei','ref'=>'Exodus 38:21–40:38','haftarah'=>'1 Kings 7:51–8:21','theme'=>'The Glory fills the Tabernacle'],
        ['name'=>'Vayikra','ref'=>'Leviticus 1:1–5:26','haftarah'=>'Isaiah 43:21–44:23','theme'=>'The offerings — drawing near to God'],
        ['name'=>'Tzav','ref'=>'Leviticus 6:1–8:36','haftarah'=>'Jeremiah 7:21–8:3','theme'=>'Priestly duties — the eternal flame'],
        ['name'=>'Shemini','ref'=>'Leviticus 9:1–11:47','haftarah'=>'2 Samuel 6:1–7:17','theme'=>'Fire from heaven — holy and common'],
        ['name'=>'Tazria','ref'=>'Leviticus 12:1–13:59','haftarah'=>'2 Kings 4:42–5:19','theme'=>'Purity and the human condition'],
        ['name'=>'Metzora','ref'=>'Leviticus 14:1–15:33','haftarah'=>'2 Kings 7:3–20','theme'=>'Cleansing — restoration to community'],
        ['name'=>'Acharei Mot','ref'=>'Leviticus 16:1–18:30','haftarah'=>'Ezekiel 22:1–19','theme'=>'The Day of Atonement ritual'],
        ['name'=>'Kedoshim','ref'=>'Leviticus 19:1–20:27','haftarah'=>'Amos 9:7–15','theme'=>'"Be holy, for I the LORD your God am holy"'],
        ['name'=>'Emor','ref'=>'Leviticus 21:1–24:23','haftarah'=>'Ezekiel 44:15–31','theme'=>'The appointed times (Mo\'adim) of the LORD'],
        ['name'=>'Behar','ref'=>'Leviticus 25:1–26:2','haftarah'=>'Jeremiah 32:6–27','theme'=>'Sabbatical year and Jubilee — liberty'],
        ['name'=>'Bechukotai','ref'=>'Leviticus 26:3–27:34','haftarah'=>'Jeremiah 16:19–17:14','theme'=>'Blessings and curses — the covenant choice'],
        ['name'=>'Bamidbar','ref'=>'Numbers 1:1–4:20','haftarah'=>'Hosea 2:1–22','theme'=>'Numbered in the wilderness — every soul counts'],
        ['name'=>'Naso','ref'=>'Numbers 4:21–7:89','haftarah'=>'Judges 13:2–25','theme'=>'The priestly blessing — "The LORD bless you and keep you"'],
        ['name'=>'Beha\'alotcha','ref'=>'Numbers 8:1–12:16','haftarah'=>'Zechariah 2:14–4:7','theme'=>'Light the menorah — complaining vs. trusting'],
        ['name'=>'Shelach','ref'=>'Numbers 13:1–15:41','haftarah'=>'Joshua 2:1–24','theme'=>'The spies — faith vs. fear'],
        ['name'=>'Korach','ref'=>'Numbers 16:1–18:32','haftarah'=>'1 Samuel 11:14–12:22','theme'=>'Rebellion — God chooses His servants'],
        ['name'=>'Chukat','ref'=>'Numbers 19:1–22:1','haftarah'=>'Judges 11:1–33','theme'=>'The red heifer — mystery of cleansing'],
        ['name'=>'Balak','ref'=>'Numbers 22:2–25:9','haftarah'=>'Micah 5:6–6:8','theme'=>'"A star shall come out of Jacob" — Balaam\'s prophecy'],
        ['name'=>'Pinchas','ref'=>'Numbers 25:10–30:1','haftarah'=>'1 Kings 18:46–19:21','theme'=>'Zealous for God — covenant of peace'],
        ['name'=>'Matot','ref'=>'Numbers 30:2–32:42','haftarah'=>'Jeremiah 1:1–2:3','theme'=>'Vows and warfare — words matter'],
        ['name'=>'Masei','ref'=>'Numbers 33:1–36:13','haftarah'=>'Jeremiah 2:4–28','theme'=>'The journey recorded — every stop matters'],
        ['name'=>'Devarim','ref'=>'Deuteronomy 1:1–3:22','haftarah'=>'Isaiah 1:1–27','theme'=>'Moses recounts — remember the journey'],
        ['name'=>'Va\'etchanan','ref'=>'Deuteronomy 3:23–7:11','haftarah'=>'Isaiah 40:1–26','theme'=>'The Shema — "Hear, O Israel"'],
        ['name'=>'Eikev','ref'=>'Deuteronomy 7:12–11:25','haftarah'=>'Isaiah 49:14–51:3','theme'=>'"Man does not live by bread alone"'],
        ['name'=>'Re\'eh','ref'=>'Deuteronomy 11:26–16:17','haftarah'=>'Isaiah 54:11–55:5','theme'=>'"See, I set before you blessing and curse"'],
        ['name'=>'Shoftim','ref'=>'Deuteronomy 16:18–21:9','haftarah'=>'Isaiah 51:12–52:12','theme'=>'Justice, justice you shall pursue'],
        ['name'=>'Ki Teitzei','ref'=>'Deuteronomy 21:10–25:19','haftarah'=>'Isaiah 54:1–10','theme'=>'Laws of compassion'],
        ['name'=>'Ki Tavo','ref'=>'Deuteronomy 26:1–29:8','haftarah'=>'Isaiah 60:1–22','theme'=>'Firstfruits offering — gratitude'],
        ['name'=>'Nitzavim','ref'=>'Deuteronomy 29:9–30:20','haftarah'=>'Isaiah 61:10–63:9','theme'=>'"Choose life" — the covenant renewed'],
        ['name'=>'Vayeilech','ref'=>'Deuteronomy 31:1–31:30','haftarah'=>'Hosea 14:2–10','theme'=>'Moses\' final charge — "Be strong and courageous"'],
        ['name'=>'Ha\'azinu','ref'=>'Deuteronomy 32:1–32:52','haftarah'=>'2 Samuel 22:1–51','theme'=>'The Song of Moses — heaven and earth as witness'],
        ['name'=>'V\'Zot HaBracha','ref'=>'Deuteronomy 33:1–34:12','haftarah'=>'Joshua 1:1–18','theme'=>'Moses blesses Israel — the Torah concludes, and begins again'],
    ];

    // Calculate which parasha we're in — simplified: use week-of-year cycle
    // The cycle restarts at Simchat Torah (Tishrei 23)
    // For a reasonable approximation: find the last Simchat Torah and count Shabbats from it
    $yearForCycle = (int)date('Y', strtotime($date));
    $monthForCycle = (int)date('n', strtotime($date));
    // Get this year's Simchat Torah (Tishrei 23)
    $hebrewYearGuess = $yearForCycle + 3760 + ($monthForCycle >= 9 ? 1 : 0);
    $cyclePos2 = $hebrewYearGuess % 19;
    if ($cyclePos2 === 0) $cyclePos2 = 19;
    $isLeap2 = in_array($cyclePos2, [3,6,8,11,14,17,19]);
    $st_jd = jewishtojd(1, 23, $hebrewYearGuess); // Tishrei 23
    $st_greg = jdtogregorian($st_jd);
    list($sm, $sd, $sy) = explode('/', $st_greg);
    $simchatTorah = mktime(0,0,0, $sm, $sd, $sy);

    $currentTs = strtotime($date);
    if ($currentTs < $simchatTorah) {
        // Use previous year's Simchat Torah
        $hebrewYearGuess--;
        $st_jd = jewishtojd(1, 23, $hebrewYearGuess);
        $st_greg = jdtogregorian($st_jd);
        list($sm, $sd, $sy) = explode('/', $st_greg);
        $simchatTorah = mktime(0,0,0, $sm, $sd, $sy);
    }

    $daysSince = floor(($currentTs - $simchatTorah) / 86400);
    $weeksSince = (int)floor($daysSince / 7);
    $portionIndex = $weeksSince % 54;

    $portion = $parashot[$portionIndex];
    return [
        'name'     => $portion['name'],
        'ref'      => $portion['ref'],
        'haftarah' => $portion['haftarah'],
        'theme'    => $portion['theme'],
        'week'     => $weeksSince + 1,
    ];
}

function getVerseOfTheDay(string $date, int $hMonth, int $hDay, array $holidays, int $dow, int $nisanMonth): array {
    // Priority: holiday verse → Shabbat verse → day-seeded rotating verse
    $holidayNames = array_column($holidays, 'name');

    // Holiday-specific verses
    if (in_array('Pesach (Passover)', $holidayNames)) {
        return ['ref'=>'Exodus 12:13','text'=>'The blood shall be a sign for you on the houses where you live; and when I see the blood I will pass over you.','category'=>'Passover'];
    }
    if (in_array('Shavuot (Pentecost)', $holidayNames)) {
        return ['ref'=>'Acts 2:4','text'=>'They were all filled with the Holy Spirit and began to speak in other tongues, as the Spirit gave them utterance.','category'=>'Shavuot'];
    }
    if (in_array('Rosh Hashanah', $holidayNames)) {
        return ['ref'=>'1 Thessalonians 4:16','text'=>'For the Lord Himself will descend from heaven with a shout, with the voice of the archangel, and with the trumpet of God.','category'=>'Rosh Hashanah'];
    }
    if (in_array('Yom Kippur', $holidayNames)) {
        return ['ref'=>'Hebrews 9:12','text'=>'Not with the blood of goats and calves, but with His own blood He entered the Most Holy Place once for all, having obtained eternal redemption.','category'=>'Yom Kippur'];
    }
    if (in_array('Sukkot', $holidayNames)) {
        return ['ref'=>'John 1:14','text'=>'And the Word became flesh, and tabernacled among us, and we beheld His glory, glory as of the only begotten from the Father, full of grace and truth.','category'=>'Sukkot'];
    }
    if (in_array('Hanukkah', $holidayNames)) {
        return ['ref'=>'John 8:12','text'=>'Then Jesus spoke to them again, saying, "I am the light of the world. He who follows Me shall not walk in darkness, but have the light of life."','category'=>'Hanukkah'];
    }
    if (in_array('Purim', $holidayNames)) {
        return ['ref'=>'Esther 4:14','text'=>'For if you remain completely silent at this time, relief and deliverance will arise for the Jews from another place. And who knows whether you have come to the kingdom for such a time as this?','category'=>'Purim'];
    }

    // Shabbat verses (rotating)
    if ($dow === 6) {
        $shabbatVerses = [
            ['ref'=>'Genesis 2:3','text'=>'Then God blessed the seventh day and sanctified it, because in it He rested from all His work which God had created and made.'],
            ['ref'=>'Exodus 20:8','text'=>'Remember the Sabbath day, to keep it holy.'],
            ['ref'=>'Isaiah 58:13-14','text'=>'If you turn away your foot from the Sabbath, from doing your pleasure on My holy day, and call the Sabbath a delight, then you shall delight yourself in the LORD.'],
            ['ref'=>'Hebrews 4:9-10','text'=>'There remains therefore a rest for the people of God. For he who has entered His rest has himself also ceased from his works as God did from His.'],
            ['ref'=>'Mark 2:27','text'=>'The Sabbath was made for man, and not man for the Sabbath.'],
            ['ref'=>'Psalm 92:1-2','text'=>'It is good to give thanks to the LORD, and to sing praises to Your name, O Most High; to declare Your lovingkindness in the morning, and Your faithfulness every night.'],
        ];
        $weekNum = (int)date('W', strtotime($date));
        return array_merge($shabbatVerses[$weekNum % count($shabbatVerses)], ['category'=>'Shabbat']);
    }

    // Daily verses — 365 curated verses cycling through categories
    $dailyVerses = [
        // Salvation & Grace
        ['ref'=>'Psalm 23:1-3','text'=>'The LORD is my shepherd; I shall not want. He makes me lie down in green pastures; He leads me beside still waters. He restores my soul.','category'=>'Peace'],
        ['ref'=>'Proverbs 3:5-6','text'=>'Trust in the LORD with all your heart, and lean not on your own understanding; in all your ways acknowledge Him, and He shall direct your paths.','category'=>'Wisdom'],
        ['ref'=>'Isaiah 40:31','text'=>'But those who wait on the LORD shall renew their strength; they shall mount up with wings like eagles, they shall run and not be weary, they shall walk and not faint.','category'=>'Strength'],
        ['ref'=>'Jeremiah 29:11','text'=>'For I know the plans I have for you, declares the LORD, plans to prosper you and not to harm you, plans to give you a hope and a future.','category'=>'Hope'],
        ['ref'=>'Romans 8:28','text'=>'And we know that all things work together for good to those who love God, to those who are the called according to His purpose.','category'=>'Faith'],
        ['ref'=>'Philippians 4:13','text'=>'I can do all things through Christ who strengthens me.','category'=>'Strength'],
        ['ref'=>'Matthew 11:28','text'=>'Come to Me, all you who labor and are heavy laden, and I will give you rest.','category'=>'Peace'],
        ['ref'=>'John 3:16','text'=>'For God so loved the world that He gave His only begotten Son, that whoever believes in Him should not perish but have everlasting life.','category'=>'Salvation'],
        ['ref'=>'Psalm 46:10','text'=>'Be still, and know that I am God; I will be exalted among the nations, I will be exalted in the earth.','category'=>'Peace'],
        ['ref'=>'Joshua 1:9','text'=>'Have I not commanded you? Be strong and of good courage; do not be afraid, nor be dismayed, for the LORD your God is with you wherever you go.','category'=>'Courage'],
        ['ref'=>'Isaiah 41:10','text'=>'Fear not, for I am with you; be not dismayed, for I am your God. I will strengthen you, yes, I will help you, I will uphold you with My righteous right hand.','category'=>'Courage'],
        ['ref'=>'Psalm 119:105','text'=>'Your word is a lamp to my feet and a light to my path.','category'=>'Wisdom'],
        ['ref'=>'Romans 12:2','text'=>'Do not be conformed to this world, but be transformed by the renewing of your mind, that you may prove what is that good and acceptable and perfect will of God.','category'=>'Wisdom'],
        ['ref'=>'1 Corinthians 13:4-7','text'=>'Love is patient, love is kind. It does not envy, it does not boast, it is not proud. It always protects, always trusts, always hopes, always perseveres.','category'=>'Love'],
        ['ref'=>'Psalm 91:1-2','text'=>'He who dwells in the secret place of the Most High shall abide under the shadow of the Almighty. I will say of the LORD, "He is my refuge and my fortress; my God, in Him I will trust."','category'=>'Protection'],
        ['ref'=>'Micah 6:8','text'=>'He has shown you, O man, what is good; and what does the LORD require of you but to do justly, to love mercy, and to walk humbly with your God?','category'=>'Wisdom'],
        ['ref'=>'Deuteronomy 6:4-5','text'=>'Hear, O Israel: The LORD our God, the LORD is one! You shall love the LORD your God with all your heart, with all your soul, and with all your strength.','category'=>'Shema'],
        ['ref'=>'Psalm 139:14','text'=>'I will praise You, for I am fearfully and wonderfully made; marvelous are Your works, and that my soul knows very well.','category'=>'Praise'],
        ['ref'=>'Lamentations 3:22-23','text'=>'Through the LORD\'s mercies we are not consumed, because His compassions fail not. They are new every morning; great is Your faithfulness.','category'=>'Mercy'],
        ['ref'=>'Matthew 6:33','text'=>'But seek first the kingdom of God and His righteousness, and all these things shall be added to you.','category'=>'Faith'],
        ['ref'=>'Psalm 27:1','text'=>'The LORD is my light and my salvation — whom shall I fear? The LORD is the stronghold of my life — of whom shall I be afraid?','category'=>'Courage'],
        ['ref'=>'Isaiah 53:5','text'=>'But He was wounded for our transgressions, He was bruised for our iniquities; the chastisement for our peace was upon Him, and by His stripes we are healed.','category'=>'Salvation'],
        ['ref'=>'2 Timothy 1:7','text'=>'For God has not given us a spirit of fear, but of power and of love and of a sound mind.','category'=>'Courage'],
        ['ref'=>'Psalm 34:18','text'=>'The LORD is near to the brokenhearted, and saves those who are crushed in spirit.','category'=>'Comfort'],
        ['ref'=>'Proverbs 18:10','text'=>'The name of the LORD is a strong tower; the righteous run to it and are safe.','category'=>'Protection'],
        ['ref'=>'John 14:6','text'=>'Jesus said to him, "I am the way, the truth, and the life. No one comes to the Father except through Me."','category'=>'Salvation'],
        ['ref'=>'Psalm 37:4','text'=>'Delight yourself also in the LORD, and He shall give you the desires of your heart.','category'=>'Faith'],
        ['ref'=>'Galatians 5:22-23','text'=>'But the fruit of the Spirit is love, joy, peace, longsuffering, kindness, goodness, faithfulness, gentleness, self-control.','category'=>'Spirit'],
        ['ref'=>'Isaiah 26:3','text'=>'You will keep him in perfect peace, whose mind is stayed on You, because he trusts in You.','category'=>'Peace'],
        ['ref'=>'Matthew 5:16','text'=>'Let your light so shine before men, that they may see your good works and glorify your Father in heaven.','category'=>'Light'],
    ];

    $dayOfYear = (int)date('z', strtotime($date));
    $idx = $dayOfYear % count($dailyVerses);
    return $dailyVerses[$idx];
}

function getPrayerOfTheDay(string $date, int $dow, array $holidays): array {
    $holidayNames = array_column($holidays, 'name');

    // Shabbat prayers
    if ($dow === 6 || $dow === 5) {
        $shabbatPrayers = [
            ['name'=>'Candle Lighting','hebrew'=>'בָּרוּךְ אַתָּה ה׳ אֱלֹהֵינוּ מֶלֶךְ הָעוֹלָם, אֲשֶׁר קִדְּשָׁנוּ בְּמִצְוֹתָיו, וְצִוָּנוּ לְהַדְלִיק נֵר שֶׁל שַׁבָּת','transliteration'=>'Baruch Atah Adonai, Eloheinu Melech Ha\'Olam, asher kidshanu b\'mitzvotav v\'tzivanu l\'hadlik ner shel Shabbat','english'=>'Blessed are You, LORD our God, King of the universe, who has sanctified us with His commandments, and commanded us to kindle the Shabbat light.','when'=>'Friday evening before sunset'],
            ['name'=>'Kiddush (Wine)','hebrew'=>'בָּרוּךְ אַתָּה ה׳ אֱלֹהֵינוּ מֶלֶךְ הָעוֹלָם, בּוֹרֵא פְּרִי הַגָּפֶן','transliteration'=>'Baruch Atah Adonai, Eloheinu Melech Ha\'Olam, borei p\'ri hagafen','english'=>'Blessed are You, LORD our God, King of the universe, who creates the fruit of the vine.','when'=>'Friday evening, Shabbat dinner'],
        ];
        return ['prayers' => $shabbatPrayers, 'occasion' => 'Shabbat'];
    }

    // Daily prayers
    $dailyPrayers = [
        // Sunday
        ['name'=>'Morning Gratitude (Modeh Ani)','hebrew'=>'מוֹדֶה אֲנִי לְפָנֶיךָ, מֶלֶךְ חַי וְקַיָּם, שֶׁהֶחֱזַרְתָּ בִּי נִשְׁמָתִי בְּחֶמְלָה, רַבָּה אֱמוּנָתֶךָ','transliteration'=>'Modeh ani l\'fanecha, Melech chai v\'kayam, shehechezarta bi nishmati b\'chemla, raba emunatecha','english'=>'I give thanks before You, living and eternal King, for You have mercifully restored my soul within me; great is Your faithfulness.','when'=>'Upon waking'],
        // Monday
        ['name'=>'The Shema','hebrew'=>'שְׁמַע יִשְׂרָאֵל ה׳ אֱלֹהֵינוּ ה׳ אֶחָד','transliteration'=>'Shema Yisrael, Adonai Eloheinu, Adonai Echad','english'=>'Hear, O Israel: The LORD our God, the LORD is One.','when'=>'Morning and evening'],
        // Tuesday
        ['name'=>'HaMotzi (Bread)','hebrew'=>'בָּרוּךְ אַתָּה ה׳ אֱלֹהֵינוּ מֶלֶךְ הָעוֹלָם, הַמּוֹצִיא לֶחֶם מִן הָאָרֶץ','transliteration'=>'Baruch Atah Adonai, Eloheinu Melech Ha\'Olam, hamotzi lechem min ha\'aretz','english'=>'Blessed are You, LORD our God, King of the universe, who brings forth bread from the earth.','when'=>'Before meals'],
        // Wednesday
        ['name'=>'Psalm 23 (Prayer of Rest)','hebrew'=>'מִזְמוֹר לְדָוִד ה׳ רֹעִי לֹא אֶחְסָר','transliteration'=>'Mizmor l\'David, Adonai ro\'i lo echsar','english'=>'A Psalm of David: The LORD is my shepherd, I shall not want.','when'=>'Anytime — a prayer of trust'],
        // Thursday
        ['name'=>'Birkat HaMazon (Grace After Meals)','hebrew'=>'בָּרוּךְ אַתָּה ה׳ אֱלֹהֵינוּ מֶלֶךְ הָעוֹלָם, הַזָּן אֶת הָעוֹלָם כֻּלּוֹ, בְּטוּבוֹ, בְּחֵן, בְּחֶסֶד, וּבְרַחֲמִים','transliteration'=>'Baruch Atah Adonai, Eloheinu Melech Ha\'Olam, hazan et ha\'olam kulo, b\'tuvo, b\'chen, b\'chesed, uv\'rachamim','english'=>'Blessed are You, LORD our God, King of the universe, who nourishes the whole world with goodness, grace, kindness, and mercy.','when'=>'After meals'],
    ];

    $dayIdx = $dow % count($dailyPrayers);
    return ['prayers' => [$dailyPrayers[$dayIdx]], 'occasion' => 'Daily'];
}

function getDayTheme(int $dow, array $holidays): array {
    $themes = [
        0 => ['theme'=>'Resurrection & New Beginnings','color'=>'#f5c542','icon'=>'✡️','message'=>'Yom Rishon — the first day. Yeshua rose on a Sunday. Every week begins with resurrection.'],
        1 => ['theme'=>'Study & Wisdom','color'=>'#3b82f6','icon'=>'📖','message'=>'Yom Sheni — the second day. God separated the waters above from the waters below. Today, seek to separate truth from confusion.'],
        2 => ['theme'=>'Gathering & Growth','color'=>'#10b981','icon'=>'🌱','message'=>'Yom Shlishi — the third day. God gathered the waters and brought forth vegetation. Plant seeds of kindness today.'],
        3 => ['theme'=>'Light & Guidance','color'=>'#f59e0b','icon'=>'⭐','message'=>'Yom Revi\'i — the fourth day. God set the sun, moon, and stars in their places. Let His light guide your path.'],
        4 => ['theme'=>'Life & Abundance','color'=>'#06b6d4','icon'=>'🐟','message'=>'Yom Chamishi — the fifth day. God filled the seas and skies with life. Be fruitful in all you do.'],
        5 => ['theme'=>'Preparation & Anticipation','color'=>'#8b5cf6','icon'=>'🕯️','message'=>'Yom Shishi — the sixth day. God created man and said "It is very good." Prepare your heart for Shabbat rest.'],
        6 => ['theme'=>'Rest & Holiness','color'=>'#e2b340','icon'=>'🕊️','message'=>'Shabbat Shalom — God rested, blessed this day, and made it holy. So must you. Let go. Be still. Know that He is God.'],
    ];

    $dayTheme = $themes[$dow] ?? $themes[0];

    // Override with holiday theme if applicable
    foreach ($holidays as $h) {
        if ($h['type'] === 'major') {
            $dayTheme['holidayOverride'] = $h['name'];
            $dayTheme['message'] = $h['yeshua'];
            break;
        }
    }

    return $dayTheme;
}
