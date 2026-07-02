<?php
/**
 * DANIEL CALENDAR API — God's Clock for the World
 * ═══════════════════════════════════════════════════
 * 
 * Returns: sunset time, Hebrew date, Torah portion, upcoming feasts,
 *          candle lighting time, Enochian date, daily Scripture.
 * 
 * Usage:
 *   GET /api/daniel-calendar.php                        → Today, auto-detect location
 *   GET /api/daniel-calendar.php?lat=45.5&lon=-73.6     → Today for Montreal
 *   GET /api/daniel-calendar.php?date=2026-04-10        → Specific date
 *   GET /api/daniel-calendar.php?city=montreal           → Named city lookup
 * 
 * "He appointed the moon for seasons: the sun knoweth his going down." — Psalm 104:19
 * 
 * Created: April 10, 2026 (23 Iyar 5786) — Erev Shabbat
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=300');

// ─── Input ───
$lat = isset($_GET['lat']) ? (float)$_GET['lat'] : null;
$lon = isset($_GET['lon']) ? (float)$_GET['lon'] : null;
$dateStr = $_GET['date'] ?? date('Y-m-d');
$city = $_GET['city'] ?? null;

// ─── City lookup (common cities) ───
$cities = [
    'montreal'    => ['lat' => 45.5017, 'lon' => -73.5673, 'tz' => 'America/Montreal',       'name' => 'Montréal, QC'],
    'jerusalem'   => ['lat' => 31.7683, 'lon' => 35.2137,  'tz' => 'Asia/Jerusalem',          'name' => 'Jerusalem, Israel'],
    'new york'    => ['lat' => 40.7128, 'lon' => -74.0060, 'tz' => 'America/New_York',        'name' => 'New York, NY'],
    'newyork'     => ['lat' => 40.7128, 'lon' => -74.0060, 'tz' => 'America/New_York',        'name' => 'New York, NY'],
    'los angeles' => ['lat' => 34.0522, 'lon' => -118.2437,'tz' => 'America/Los_Angeles',     'name' => 'Los Angeles, CA'],
    'london'      => ['lat' => 51.5074, 'lon' => -0.1278,  'tz' => 'Europe/London',           'name' => 'London, UK'],
    'paris'       => ['lat' => 48.8566, 'lon' => 2.3522,   'tz' => 'Europe/Paris',            'name' => 'Paris, France'],
    'toronto'     => ['lat' => 43.6532, 'lon' => -79.3832, 'tz' => 'America/Toronto',         'name' => 'Toronto, ON'],
    'miami'       => ['lat' => 25.7617, 'lon' => -80.1918, 'tz' => 'America/New_York',        'name' => 'Miami, FL'],
    'tel aviv'    => ['lat' => 32.0853, 'lon' => 34.7818,  'tz' => 'Asia/Jerusalem',          'name' => 'Tel Aviv, Israel'],
    'chicago'     => ['lat' => 41.8781, 'lon' => -87.6298, 'tz' => 'America/Chicago',         'name' => 'Chicago, IL'],
    'houston'     => ['lat' => 29.7604, 'lon' => -95.3698, 'tz' => 'America/Chicago',         'name' => 'Houston, TX'],
    'sydney'      => ['lat' => -33.8688,'lon' => 151.2093, 'tz' => 'Australia/Sydney',        'name' => 'Sydney, Australia'],
    'tokyo'       => ['lat' => 35.6762, 'lon' => 139.6503, 'tz' => 'Asia/Tokyo',              'name' => 'Tokyo, Japan'],
    'berlin'      => ['lat' => 52.5200, 'lon' => 13.4050,  'tz' => 'Europe/Berlin',           'name' => 'Berlin, Germany'],
    'rome'        => ['lat' => 41.9028, 'lon' => 12.4964,  'tz' => 'Europe/Rome',             'name' => 'Rome, Italy'],
    'mexico city' => ['lat' => 19.4326, 'lon' => -99.1332, 'tz' => 'America/Mexico_City',     'name' => 'Mexico City'],
    'dubai'       => ['lat' => 25.2048, 'lon' => 55.2708,  'tz' => 'Asia/Dubai',              'name' => 'Dubai, UAE'],
    'mumbai'      => ['lat' => 19.0760, 'lon' => 72.8777,  'tz' => 'Asia/Kolkata',            'name' => 'Mumbai, India'],
    'sao paulo'   => ['lat' => -23.5505,'lon' => -46.6333, 'tz' => 'America/Sao_Paulo',       'name' => 'São Paulo, Brazil'],
    'cape town'   => ['lat' => -33.9249,'lon' => 18.4241,  'tz' => 'Africa/Johannesburg',     'name' => 'Cape Town, SA'],
];

if ($city) {
    $key = strtolower(trim($city));
    if (isset($cities[$key])) {
        $lat = $cities[$key]['lat'];
        $lon = $cities[$key]['lon'];
        $tz  = $cities[$key]['tz'];
        $cityName = $cities[$key]['name'];
    }
}

// Default to Montreal (Commander's location)
if ($lat === null || $lon === null) {
    $lat = 45.5017;
    $lon = -73.5673;
    $tz  = 'America/Montreal';
    $cityName = 'Montréal, QC (default)';
}

if (!isset($tz)) {
    // Rough timezone from longitude
    $tz = 'UTC';
    $offsetHours = round($lon / 15);
    // Use a generic timezone
    if ($offsetHours >= -12 && $offsetHours <= 14) {
        $tz = timezone_name_from_abbr('', $offsetHours * 3600, 0) ?: 'UTC';
    }
}
if (!isset($cityName)) $cityName = "Lat {$lat}, Lon {$lon}";

date_default_timezone_set($tz);

// ─── Parse date ───
$date = new DateTime($dateStr, new DateTimeZone($tz));
$year  = (int)$date->format('Y');
$month = (int)$date->format('n');
$day   = (int)$date->format('j');
$dow   = (int)$date->format('w'); // 0=Sun, 6=Sat

// ═══════════════════════════════════════════════════
// SUNSET CALCULATION (Meeus algorithm)
// ═══════════════════════════════════════════════════
function calculateSunset($lat, $lon, $year, $month, $day, $tz) {
    $dt = new DateTime("$year-$month-$day", new DateTimeZone($tz));
    $dayOfYear = (int)$dt->format('z') + 1; // 1-based
    
    $zenith = 90.833; // Official sunset (accounting for refraction)
    
    // Convert latitude to radians
    $latRad = deg2rad($lat);
    
    // Calculate approximate time
    $lngHour = $lon / 15;
    $t = $dayOfYear + ((18 - $lngHour) / 24); // 18 = sunset
    
    // Sun's mean anomaly
    $M = (0.9856 * $t) - 3.289;
    
    // Sun's true longitude
    $L = $M + (1.916 * sin(deg2rad($M))) + (0.020 * sin(deg2rad(2 * $M))) + 282.634;
    $L = fmod($L, 360);
    if ($L < 0) $L += 360;
    
    // Sun's right ascension
    $RA = rad2deg(atan(0.91764 * tan(deg2rad($L))));
    $RA = fmod($RA, 360);
    if ($RA < 0) $RA += 360;
    
    // RA must be in same quadrant as L
    $Lquadrant  = (floor($L / 90)) * 90;
    $RAquadrant = (floor($RA / 90)) * 90;
    $RA = $RA + ($Lquadrant - $RAquadrant);
    $RA = $RA / 15; // Convert to hours
    
    // Sun's declination
    $sinDec = 0.39782 * sin(deg2rad($L));
    $cosDec = cos(asin($sinDec));
    
    // Sun's local hour angle
    $cosH = (cos(deg2rad($zenith)) - ($sinDec * sin($latRad))) / ($cosDec * cos($latRad));
    
    if ($cosH > 1) return null;  // Sun never sets (polar)
    if ($cosH < -1) return null; // Sun never rises (polar)
    
    $H = rad2deg(acos($cosH));
    $H = $H / 15; // Convert to hours
    
    // Local mean time of sunset
    $T = $H + $RA - (0.06571 * $t) - 6.622;
    
    // UTC time
    $UT = $T - $lngHour;
    $UT = fmod($UT, 24);
    if ($UT < 0) $UT += 24;
    
    // Convert to local time
    $offset = $dt->getOffset() / 3600;
    $localTime = $UT + $offset;
    if ($localTime < 0) $localTime += 24;
    if ($localTime >= 24) $localTime -= 24;
    
    $hours = floor($localTime);
    $minutes = round(($localTime - $hours) * 60);
    
    return [
        'decimal'   => round($localTime, 4),
        'formatted' => sprintf('%d:%02d PM', $hours > 12 ? $hours - 12 : $hours, $minutes),
        'hours'     => (int)$hours,
        'minutes'   => (int)$minutes,
        'iso'       => sprintf('%04d-%02d-%02dT%02d:%02d:00', $year, $month, $day, $hours, $minutes),
    ];
}

$sunset = calculateSunset($lat, $lon, $year, $month, $day, $tz);
$candleLighting = null;
if ($sunset) {
    // 18 minutes before sunset
    $clMinutes = $sunset['minutes'] - 18;
    $clHours = $sunset['hours'];
    if ($clMinutes < 0) { $clMinutes += 60; $clHours -= 1; }
    $candleLighting = sprintf('%d:%02d PM', $clHours > 12 ? $clHours - 12 : $clHours, $clMinutes);
}

// ═══════════════════════════════════════════════════
// HEBREW DATE CALCULATION (Rabbinic/Hillel II)
// ═══════════════════════════════════════════════════
function gregorianToHebrew($gYear, $gMonth, $gDay) {
    // Use PHP's cal_from_jd for Hebrew conversion
    $jd = gregoriantojd($gMonth, $gDay, $gYear);
    $heb = cal_from_jd($jd, CAL_JEWISH);
    
    $hebrewMonths = [
        1 => 'Tishrei', 2 => 'Cheshvan', 3 => 'Kislev', 4 => 'Tevet',
        5 => 'Shevat', 6 => 'Adar', 7 => 'Adar II',
        8 => 'Nisan', 9 => 'Iyyar', 10 => 'Sivan', 11 => 'Tammuz',
        12 => 'Av', 13 => 'Elul'
    ];
    
    // The Jewish calendar in PHP uses a different month numbering
    // Tishrei=1, but output may vary. Let's use the abbrevname.
    return [
        'day'       => $heb['day'],
        'month'     => $heb['month'],
        'year'      => $heb['year'],
        'monthName' => $heb['abbrevmonth'] ?? ($hebrewMonths[$heb['month']] ?? 'Unknown'),
        'formatted' => $heb['day'] . ' ' . ($heb['abbrevmonth'] ?? $hebrewMonths[$heb['month']] ?? '?') . ' ' . $heb['year'],
    ];
}

$hebrew = gregorianToHebrew($year, $month, $day);

// ═══════════════════════════════════════════════════
// ENOCHIAN CALENDAR (364-day, 4×91)
// ═══════════════════════════════════════════════════
function enochianDate($gYear, $gMonth, $gDay) {
    // Enochian year starts at the spring equinox (March 20/21)
    // Each quarter: 30+30+31 = 91 days. Total: 364 days.
    // This is an approximation — the real priestly intercalation is lost.
    
    $equinox = new DateTime("$gYear-03-20");
    $current = new DateTime("$gYear-$gMonth-$gDay");
    
    if ($current < $equinox) {
        // We're in the previous Enochian year
        $prevYear = $gYear - 1;
        $equinox = new DateTime("$prevYear-03-20");
    }
    
    $dayOfYear = (int)$current->diff($equinox)->days + 1;
    
    if ($dayOfYear > 364) {
        // Intercalary day(s) — outside the 364-day structure
        return [
            'quarter' => 0,
            'month'   => 0,
            'day'     => $dayOfYear - 364,
            'dayOfYear' => $dayOfYear,
            'note'    => 'Intercalary day — outside the 364-day cycle. The priestly intercalation method is lost.',
            'formatted' => "Intercalary Day " . ($dayOfYear - 364),
        ];
    }
    
    // Which quarter (1-4)?
    $quarter = min(4, (int)ceil($dayOfYear / 91));
    $dayInQuarter = $dayOfYear - (($quarter - 1) * 91);
    
    // Which month in this quarter (each quarter: 30+30+31)?
    if ($dayInQuarter <= 30) {
        $monthInQ = 1;
        $dayInMonth = $dayInQuarter;
    } elseif ($dayInQuarter <= 60) {
        $monthInQ = 2;
        $dayInMonth = $dayInQuarter - 30;
    } else {
        $monthInQ = 3;
        $dayInMonth = $dayInQuarter - 60;
    }
    
    $enochMonth = ($quarter - 1) * 3 + $monthInQ;
    $seasonNames = [1 => 'Spring', 2 => 'Summer', 3 => 'Autumn', 4 => 'Winter'];
    
    return [
        'quarter'    => $quarter,
        'season'     => $seasonNames[$quarter],
        'month'      => $enochMonth,
        'day'        => $dayInMonth,
        'dayOfYear'  => $dayOfYear,
        'formatted'  => "Month $enochMonth, Day $dayInMonth (Q{$quarter} — {$seasonNames[$quarter]})",
        'note'       => 'Enochian 364-day solar calendar anchored to spring equinox. Approximate — priestly intercalation tradition is lost.',
    ];
}

$enochian = enochianDate($year, $month, $day);

// ═══════════════════════════════════════════════════
// TORAH PORTION (Annual Cycle)
// ═══════════════════════════════════════════════════
function getTorahPortion($gYear, $gMonth, $gDay) {
    // Torah portions follow the annual cycle starting after Sukkot.
    // There are 54 parashot, some doubled in non-leap years.
    // This is a simplified lookup — approximate based on the Shabbat date.
    
    $parashot = [
        ['name' => 'Bereshit',     'ref' => 'Genesis 1:1–6:8',         'topic' => 'Creation, Adam & Eve, Cain & Abel, the generations before the Flood'],
        ['name' => 'Noach',        'ref' => 'Genesis 6:9–11:32',       'topic' => 'Noah, the Flood, the Tower of Babel, the nations'],
        ['name' => 'Lech Lecha',   'ref' => 'Genesis 12:1–17:27',      'topic' => 'Abram\'s call, journey to Canaan, covenant of circumcision'],
        ['name' => 'Vayera',       'ref' => 'Genesis 18:1–22:24',      'topic' => 'Three visitors, Sodom destroyed, binding of Isaac'],
        ['name' => 'Chayei Sarah', 'ref' => 'Genesis 23:1–25:18',      'topic' => 'Sarah\'s death, cave of Machpelah, Isaac and Rebekah'],
        ['name' => 'Toldot',       'ref' => 'Genesis 25:19–28:9',      'topic' => 'Jacob and Esau, the birthright, Isaac\'s blessings'],
        ['name' => 'Vayetzei',     'ref' => 'Genesis 28:10–32:3',      'topic' => 'Jacob\'s ladder, Laban, Rachel & Leah, twelve tribes begin'],
        ['name' => 'Vayishlach',   'ref' => 'Genesis 32:4–36:43',      'topic' => 'Jacob wrestles the angel, becomes Israel, Esau reconciliation'],
        ['name' => 'Vayeshev',     'ref' => 'Genesis 37:1–40:23',      'topic' => 'Joseph\'s dreams, sold into slavery, Potiphar, prison'],
        ['name' => 'Miketz',       'ref' => 'Genesis 41:1–44:17',      'topic' => 'Pharaoh\'s dreams, Joseph rises to power, brothers come to Egypt'],
        ['name' => 'Vayigash',     'ref' => 'Genesis 44:18–47:27',     'topic' => 'Judah pleads, Joseph reveals himself, Jacob goes to Egypt'],
        ['name' => 'Vayechi',      'ref' => 'Genesis 47:28–50:26',     'topic' => 'Jacob blesses the twelve, death of Jacob and Joseph'],
        ['name' => 'Shemot',       'ref' => 'Exodus 1:1–6:1',          'topic' => 'Slavery in Egypt, Moses born, burning bush, "Let my people go"'],
        ['name' => 'Va\'eira',     'ref' => 'Exodus 6:2–9:35',         'topic' => 'God reveals His name, first seven plagues'],
        ['name' => 'Bo',           'ref' => 'Exodus 10:1–13:16',       'topic' => 'Last three plagues, Passover instituted, Exodus begins'],
        ['name' => 'Beshalach',    'ref' => 'Exodus 13:17–17:16',      'topic' => 'Crossing the Red Sea, manna, water from the rock'],
        ['name' => 'Yitro',        'ref' => 'Exodus 18:1–20:23',       'topic' => 'Jethro\'s advice, Sinai, the Ten Commandments given'],
        ['name' => 'Mishpatim',    'ref' => 'Exodus 21:1–24:18',       'topic' => 'Civil laws, the Book of the Covenant, "We will do and hear"'],
        ['name' => 'Terumah',      'ref' => 'Exodus 25:1–27:19',       'topic' => 'Tabernacle offerings, Ark of the Covenant design'],
        ['name' => 'Tetzaveh',     'ref' => 'Exodus 27:20–30:10',      'topic' => 'Priestly garments, ordination of Aaron and sons'],
        ['name' => 'Ki Tisa',      'ref' => 'Exodus 30:11–34:35',      'topic' => 'Census, golden calf, Moses breaks tablets, God\'s glory'],
        ['name' => 'Vayakhel',     'ref' => 'Exodus 35:1–38:20',       'topic' => 'Shabbat reminder, Tabernacle construction begins'],
        ['name' => 'Pekudei',      'ref' => 'Exodus 38:21–40:38',      'topic' => 'Tabernacle completed, God\'s glory fills it'],
        ['name' => 'Vayikra',      'ref' => 'Leviticus 1:1–5:26',      'topic' => 'Burnt offerings, grain offerings, sin offerings, guilt offerings'],
        ['name' => 'Tzav',         'ref' => 'Leviticus 6:1–8:36',      'topic' => 'Laws of offerings, ordination of priests'],
        ['name' => 'Shemini',      'ref' => 'Leviticus 9:1–11:47',     'topic' => 'Eighth day service, Nadab & Abihu, kosher laws'],
        ['name' => 'Tazria',       'ref' => 'Leviticus 12:1–13:59',    'topic' => 'Childbirth purification, tzara\'at (skin afflictions)'],
        ['name' => 'Metzora',      'ref' => 'Leviticus 14:1–15:33',    'topic' => 'Purification from tzara\'at, bodily discharges'],
        ['name' => 'Acharei Mot',  'ref' => 'Leviticus 16:1–18:30',    'topic' => 'Yom Kippur ritual, blood prohibition, forbidden relations'],
        ['name' => 'Kedoshim',     'ref' => 'Leviticus 19:1–20:27',    'topic' => '"Be holy" — love your neighbor, ethical laws'],
        ['name' => 'Emor',         'ref' => 'Leviticus 21:1–24:23',    'topic' => 'Priestly duties, the SEVEN FEASTS of Leviticus 23, blasphemer'],
        ['name' => 'Behar',        'ref' => 'Leviticus 25:1–26:2',     'topic' => 'Shemitah (sabbatical year), Jubilee, land redemption'],
        ['name' => 'Bechukotai',   'ref' => 'Leviticus 26:3–27:34',    'topic' => 'Blessings and curses, vows, tithes — Leviticus concludes'],
        ['name' => 'Bamidbar',     'ref' => 'Numbers 1:1–4:20',        'topic' => 'Census of Israel, arrangement of the camp, Levite duties'],
        ['name' => 'Naso',         'ref' => 'Numbers 4:21–7:89',       'topic' => 'Levite duties, Nazirite vow, priestly blessing, tabernacle offerings'],
        ['name' => 'Beha\'alotcha','ref' => 'Numbers 8:1–12:16',       'topic' => 'Menorah, Levite consecration, silver trumpets, Miriam\'s leprosy'],
        ['name' => 'Shelach',      'ref' => 'Numbers 13:1–15:41',      'topic' => 'Twelve spies, Israel refuses to enter, 40-year sentence, tzitzit'],
        ['name' => 'Korach',       'ref' => 'Numbers 16:1–18:32',      'topic' => 'Korach\'s rebellion, the earth swallows them, Aaron\'s rod'],
        ['name' => 'Chukat',       'ref' => 'Numbers 19:1–22:1',       'topic' => 'Red heifer, Miriam dies, Moses strikes the rock, bronze serpent'],
        ['name' => 'Balak',        'ref' => 'Numbers 22:2–25:9',       'topic' => 'Balaam and his donkey, blessings instead of curses'],
        ['name' => 'Pinchas',      'ref' => 'Numbers 25:10–30:1',      'topic' => 'Phinehas\'s zeal, new census, daughters of Zelophehad, daily offerings'],
        ['name' => 'Matot',        'ref' => 'Numbers 30:2–32:42',      'topic' => 'Vows, war against Midian, tribes settle east of Jordan'],
        ['name' => 'Masei',        'ref' => 'Numbers 33:1–36:13',      'topic' => 'Journey stages, borders of the land, cities of refuge'],
        ['name' => 'Devarim',      'ref' => 'Deuteronomy 1:1–3:22',    'topic' => 'Moses reviews the journey, appointing judges'],
        ['name' => 'Va\'etchanan', 'ref' => 'Deuteronomy 3:23–7:11',   'topic' => 'Moses pleads, Ten Commandments restated, SHEMA YISRAEL'],
        ['name' => 'Eikev',        'ref' => 'Deuteronomy 7:12–11:25',  'topic' => 'Blessings for obedience, "man does not live by bread alone"'],
        ['name' => 'Re\'eh',       'ref' => 'Deuteronomy 11:26–16:17', 'topic' => 'Blessing and curse, kosher review, tithes, the three pilgrimage feasts'],
        ['name' => 'Shoftim',      'ref' => 'Deuteronomy 16:18–21:9',  'topic' => 'Judges, kings, priests, prophets, cities of refuge, warfare laws'],
        ['name' => 'Ki Teitzei',   'ref' => 'Deuteronomy 21:10–25:19', 'topic' => '74 commandments — most in any portion. Family, justice, remembering Amalek'],
        ['name' => 'Ki Tavo',      'ref' => 'Deuteronomy 26:1–29:8',   'topic' => 'Firstfruits, tithing declaration, blessings at Gerizim, curses at Ebal'],
        ['name' => 'Nitzavim',     'ref' => 'Deuteronomy 29:9–30:20',  'topic' => '"You stand today, all of you." The covenant renewed. Choose life.'],
        ['name' => 'Vayelech',     'ref' => 'Deuteronomy 31:1–31:30',  'topic' => 'Moses\'s last day, Joshua commissioned, Torah written and placed beside the Ark'],
        ['name' => 'Ha\'azinu',    'ref' => 'Deuteronomy 32:1–32:52',  'topic' => 'The Song of Moses — heaven and earth as witnesses. Moses sees the land.'],
        ['name' => 'V\'Zot HaBracha','ref'=> 'Deuteronomy 33:1–34:12', 'topic' => 'Moses blesses the tribes, dies on Nebo, "no prophet like Moses" — until Yeshua'],
    ];
    
    // Approximate which parashah based on the Gregorian date.
    // The cycle starts after Simchat Torah (around October).
    // This is a simplified linear approximation.
    // For production, integrate a full Hebrew calendar library.
    
    $startDate = new DateTime(($gMonth >= 10 ? $gYear : $gYear - 1) . '-10-15'); // Approximate Simchat Torah
    $current = new DateTime("$gYear-$gMonth-$gDay");
    $diff = (int)$startDate->diff($current)->days;
    if ($current < $startDate) $diff = 365 - (int)$current->diff($startDate)->days;
    
    $weekNum = (int)floor($diff / 7);
    $index = $weekNum % count($parashot);
    
    $p = $parashot[$index];
    $p['weekNumber'] = $weekNum + 1;
    $p['totalParashot'] = count($parashot);
    return $p;
}

$parashah = getTorahPortion($year, $month, $day);

// ═══════════════════════════════════════════════════
// UPCOMING FEASTS (Mo'edim)
// ═══════════════════════════════════════════════════
function getUpcomingFeasts($gYear, $gMonth, $gDay) {
    // Approximate Gregorian dates for the 7 feasts in the given year.
    // These are based on the Hillel II calendar. For exact observation-based
    // dates, would need real new moon sighting data from Israel.
    
    // Note: These are APPROXIMATE — they shift by 1-2 days each year.
    // A proper implementation would compute from the Hebrew calendar.
    // This gives a useful approximation for the fleet.
    
    $feasts = [];
    
    // We'll use a simple offset from the Hebrew calendar conversion
    // For 2026, the approximate Gregorian dates are:
    $yearFeasts = [
        2026 => [
            ['name' => 'Pesach (Passover)',           'hebrew' => 'פֶּסַח',    'date' => '2026-04-01', 'end' => '2026-04-08', 'ref' => 'Leviticus 23:5',     'type' => 'spring', 'status' => 'FULFILLED', 'meaning' => 'Yeshua the Lamb, slain for the world'],
            ['name' => 'Matzot (Unleavened Bread)',   'hebrew' => 'מַצּוֹת',   'date' => '2026-04-02', 'end' => '2026-04-08', 'ref' => 'Leviticus 23:6-8',   'type' => 'spring', 'status' => 'FULFILLED', 'meaning' => 'Sinless bread in the earth — Yeshua in the tomb'],
            ['name' => 'Bikkurim (Firstfruits)',      'hebrew' => 'בִּכּוּרִים','date' => '2026-04-05', 'end' => '2026-04-05', 'ref' => 'Leviticus 23:9-14',  'type' => 'spring', 'status' => 'FULFILLED', 'meaning' => 'Yeshua rose — firstfruits of the resurrection'],
            ['name' => 'Shavuot (Pentecost)',         'hebrew' => 'שָׁבוּעוֹת','date' => '2026-05-24', 'end' => '2026-05-25', 'ref' => 'Leviticus 23:15-22', 'type' => 'spring', 'status' => 'FULFILLED', 'meaning' => 'Holy Spirit fell. Torah given at Sinai.'],
            ['name' => 'Yom Teruah (Trumpets)',       'hebrew' => 'תְּרוּעָה', 'date' => '2026-09-12', 'end' => '2026-09-13', 'ref' => 'Leviticus 23:23-25', 'type' => 'fall',   'status' => 'AWAITING', 'meaning' => 'The last trumpet — Yeshua returns'],
            ['name' => 'Yom Kippur (Day of Atonement)','hebrew'=> 'כִּפֻּרִים','date' => '2026-09-21', 'end' => '2026-09-21', 'ref' => 'Leviticus 23:26-32', 'type' => 'fall',   'status' => 'AWAITING', 'meaning' => 'Final judgment. Israel looks on Him they pierced.'],
            ['name' => 'Sukkot (Tabernacles)',        'hebrew' => 'סוּכּוֹת',  'date' => '2026-09-26', 'end' => '2026-10-03', 'ref' => 'Leviticus 23:33-43', 'type' => 'fall',   'status' => 'AWAITING', 'meaning' => 'God dwells WITH His people — the Millennial Kingdom'],
        ],
    ];
    
    $currentDate = new DateTime("$gYear-$gMonth-$gDay");
    $yr = $gYear;
    
    // Get feasts for this year (and next year if we're past all of them)
    $allFeasts = $yearFeasts[$yr] ?? $yearFeasts[2026] ?? [];
    
    $upcoming = [];
    foreach ($allFeasts as $f) {
        $feastDate = new DateTime($f['date']);
        $daysUntil = (int)$currentDate->diff($feastDate)->format('%r%a');
        $f['daysUntil'] = $daysUntil;
        $f['isPast'] = $daysUntil < 0;
        $f['isToday'] = $daysUntil === 0;
        $upcoming[] = $f;
    }
    
    return $upcoming;
}

$feasts = getUpcomingFeasts($year, $month, $day);

// Find next upcoming feast
$nextFeast = null;
foreach ($feasts as $f) {
    if ($f['daysUntil'] >= 0) {
        $nextFeast = $f;
        break;
    }
}

// ═══════════════════════════════════════════════════
// SHABBAT STATUS
// ═══════════════════════════════════════════════════
$isErevShabbat = ($dow === 5); // Friday
$isShabbat = ($dow === 6);     // Saturday
$shabbatStatus = 'weekday';
if ($isErevShabbat) $shabbatStatus = 'erev_shabbat';
if ($isShabbat) $shabbatStatus = 'shabbat';

$dayNames = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Shabbat'];
$dayName = $dayNames[$dow];

// ═══════════════════════════════════════════════════
// DAILY VERSE
// ═══════════════════════════════════════════════════
$dailyVerses = [
    0 => ['text' => 'This is the day which the LORD hath made; we will rejoice and be glad in it.', 'ref' => 'Psalm 118:24'],
    1 => ['text' => 'In the beginning God created the heaven and the earth.', 'ref' => 'Genesis 1:1'],
    2 => ['text' => 'The LORD is my shepherd; I shall not want.', 'ref' => 'Psalm 23:1'],
    3 => ['text' => 'Trust in the LORD with all thine heart; and lean not unto thine own understanding.', 'ref' => 'Proverbs 3:5'],
    4 => ['text' => 'I can do all things through Christ which strengtheneth me.', 'ref' => 'Philippians 4:13'],
    5 => ['text' => 'Remember the sabbath day, to keep it holy.', 'ref' => 'Exodus 20:8'],
    6 => ['text' => 'Come unto me, all ye that labour and are heavy laden, and I will give you rest.', 'ref' => 'Matthew 11:28'],
];
// Use day-of-year for variety
$dayOfYear = (int)$date->format('z');
$verseIndex = $dayOfYear % count($dailyVerses);
$dailyVerse = $dailyVerses[$verseIndex];

// Override for Shabbat & Erev Shabbat
if ($isShabbat) $dailyVerse = $dailyVerses[6];
if ($isErevShabbat) $dailyVerse = $dailyVerses[5];

// ═══════════════════════════════════════════════════
// NEXT SHABBAT SUNSET (calculated on non-Shabbat days)
// ═══════════════════════════════════════════════════
$nextShabbat = null;
if (!$isShabbat) {
    // Calculate days until next Friday (dow 5)
    $daysUntilFriday = (5 - $dow + 7) % 7;
    if ($daysUntilFriday === 0) $daysUntilFriday = 0; // Friday = today
    $nextFri = clone $date;
    $nextFri->modify("+{$daysUntilFriday} days");
    $nfYear = (int)$nextFri->format('Y');
    $nfMonth = (int)$nextFri->format('n');
    $nfDay = (int)$nextFri->format('j');
    $fridaySunset = calculateSunset($lat, $lon, $nfYear, $nfMonth, $nfDay, $tz);
    if ($fridaySunset) {
        $friCLMinutes = $fridaySunset['minutes'] - 18;
        $friCLHours = $fridaySunset['hours'];
        if ($friCLMinutes < 0) { $friCLMinutes += 60; $friCLHours--; }
        $nextShabbat = [
            'date'            => $nextFri->format('Y-m-d'),
            'formatted'       => $nextFri->format('l, F j, Y'),
            'sunset'          => $fridaySunset,
            'candleLighting'  => sprintf('%d:%02d PM', $friCLHours > 12 ? $friCLHours - 12 : $friCLHours, $friCLMinutes),
        ];
    }
}

// ═══════════════════════════════════════════════════
// ASSEMBLE RESPONSE
// ═══════════════════════════════════════════════════
$response = [
    'danielCalendar' => [
        'version'  => '1.0.0',
        'name'     => 'Daniel Calendar — God\'s Clock for the World',
        'mission'  => 'Daniel 7:25 said they would "change times and laws." This tool reveals what was changed.',
        'built_by' => 'Alfred, for the Kingdom of GoSiteMe',
    ],
    'request' => [
        'date'      => $dateStr,
        'latitude'  => $lat,
        'longitude' => $lon,
        'timezone'  => $tz,
        'city'      => $cityName,
    ],
    'gregorian' => [
        'date'      => $date->format('Y-m-d'),
        'dayOfWeek' => $dayName,
        'dayNumber' => $dow,
        'formatted' => $date->format('l, F j, Y'),
    ],
    'hebrew' => [
        'day'       => $hebrew['day'],
        'month'     => $hebrew['monthName'],
        'year'      => $hebrew['year'],
        'formatted' => $hebrew['formatted'],
        'system'    => 'Hillel II (calculated). For observation-based dates, check Karaite new moon sightings from Israel.',
    ],
    'enochian' => $enochian,
    'sun' => [
        'sunset'          => $sunset,
        'candleLighting'  => $candleLighting,
        'note'            => 'Candle lighting is 18 minutes before sunset (traditional Shabbat/feast custom).',
    ],
    'shabbat' => [
        'status'          => $shabbatStatus,
        'isErevShabbat'   => $isErevShabbat,
        'isShabbat'       => $isShabbat,
        'nextShabbat'     => $nextShabbat,
        'message'         => $isShabbat ? 'Shabbat Shalom! The King rests, and so do His people.'
                          : ($isErevShabbat ? 'Erev Shabbat — prepare your heart. The sun is going down.'
                          : 'Not Shabbat yet. Work faithfully until the King calls you to rest.'),
    ],
    'torah' => [
        'parashah'   => $parashah['name'],
        'reference'  => $parashah['ref'],
        'topic'      => $parashah['topic'],
        'weekNumber' => $parashah['weekNumber'],
    ],
    'feasts' => [
        'all'        => $feasts,
        'next'       => $nextFeast,
    ],
    'dailyVerse' => $dailyVerse,
    'genesis114' => 'And God said, Let there be lights in the firmament of the heaven to divide the day from the night; and let them be for signs, and for seasons (mo\'edim), and for days, and years.',
];

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
