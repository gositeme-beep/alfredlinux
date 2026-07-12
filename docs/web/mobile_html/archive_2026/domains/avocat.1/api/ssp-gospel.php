<?php
/**
 * GoSiteMe SSP Gospel Music API v1.0
 * "Make a joyful noise unto the LORD" — Psalm 100:1
 *
 * Gospel music creation, worship tracks, Psalms of David,
 * SoundStudioPro token integration, multi-faith unity through Jesus/Yeshua/Isa,
 * nature worship environments, and gospel automix.
 */
define('GOSITEME_API', true);
require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$action = isset($_GET['action']) ? trim($_GET['action']) : 'health';

// ══════════════════════════════════════════════════════════════
//  NAMES OF JESUS — He unites every nation, tongue, and people
// ══════════════════════════════════════════════════════════════
$NAMES_OF_JESUS = [
    ['name' => 'Jesus Christ',        'language' => 'English',            'script' => 'Jesus Christ',             'tradition' => 'Christianity',           'source' => 'New Testament (KJV)',                'verse' => 'Matthew 1:21',     'meaning' => 'God saves; the Anointed One'],
    ['name' => 'Yeshua HaMashiach',   'language' => 'Hebrew',             'script' => 'ישוע המשיח',               'tradition' => 'Messianic Judaism',      'source' => 'Complete Jewish Bible (CJB)',        'verse' => 'Isaiah 49:6',      'meaning' => 'Salvation; the Messiah'],
    ['name' => 'Isa al-Masih',        'language' => 'Arabic',             'script' => 'عيسى المسيح',              'tradition' => 'Islam',                  'source' => 'Quran (Surah 3:45, 19:19-21)',       'verse' => 'Surah Maryam 19:21', 'meaning' => 'Jesus the Messiah; prophet of God, born of virgin Maryam'],
    ['name' => 'Iēsous Christos',     'language' => 'Greek',              'script' => 'Ἰησοῦς Χριστός',           'tradition' => 'Eastern Orthodox',       'source' => 'New Testament (Original Greek)',     'verse' => 'John 1:1',         'meaning' => 'The Logos; the Word made flesh'],
    ['name' => 'Jésus-Christ',        'language' => 'French',             'script' => 'Jésus-Christ',             'tradition' => 'Christianity',           'source' => 'Louis Segond Bible',                 'verse' => 'Jean 3:16',        'meaning' => 'God so loved the world'],
    ['name' => 'Jesucristo',          'language' => 'Spanish',            'script' => 'Jesucristo',               'tradition' => 'Christianity',           'source' => 'Reina-Valera Bible',                 'verse' => 'Juan 3:16',        'meaning' => 'The Saviour of the world'],
    ['name' => 'Yesu Kristo',         'language' => 'Swahili',            'script' => 'Yesu Kristo',              'tradition' => 'African Christianity',   'source' => 'Swahili Union Version',              'verse' => 'Yohana 3:16',      'meaning' => 'Jesus Christ in East Africa'],
    ['name' => 'Isus Hristos',        'language' => 'Russian/Slavic',     'script' => 'Иисус Христос',            'tradition' => 'Russian Orthodox',       'source' => 'Synodal Bible',                      'verse' => 'Иоанна 3:16',     'meaning' => 'The Christ, the Living God'],
    ['name' => 'Yesu Khristu',        'language' => 'Hindi',              'script' => 'यीशु मसीह',                'tradition' => 'Indian Christianity',    'source' => 'Hindi Bible (OV)',                   'verse' => 'Yuhanna 3:16',    'meaning' => 'Lord and Saviour in South Asia'],
    ['name' => 'Yesu Kristu',         'language' => 'Amharic',            'script' => 'ኢየሱስ ክርስቶስ',              'tradition' => 'Ethiopian Orthodox',     'source' => 'Amharic Bible',                      'verse' => 'Yohannes 3:16',   'meaning' => 'Christ in the oldest African church'],
    ['name' => 'Iesu Keriso',         'language' => 'Samoan',             'script' => 'Iesu Keriso',              'tradition' => 'Pacific Christianity',   'source' => 'Samoan Bible',                       'verse' => 'Ioane 3:16',      'meaning' => 'Jesus Christ across the Pacific'],
    ['name' => 'Jezi Kris',           'language' => 'Haitian Creole',     'script' => 'Jezi Kris',                'tradition' => 'Caribbean Christianity', 'source' => 'Haitian Creole Bible',               'verse' => 'Jan 3:16',        'meaning' => 'The Living Hope in the Caribbean'],
    ['name' => 'Immanuel',            'language' => 'Hebrew',             'script' => 'עִמָּנוּאֵל',              'tradition' => 'Messianic Prophecy',     'source' => 'Isaiah 7:14 / Matthew 1:23',         'verse' => 'Isaiah 7:14',     'meaning' => 'God with us'],
    ['name' => 'The Lamb of God',     'language' => 'English',            'script' => 'The Lamb of God',          'tradition' => 'Universal Christianity', 'source' => 'John 1:29',                          'verse' => 'John 1:29',       'meaning' => 'Behold the Lamb who takes away the sin of the world'],
    ['name' => 'The Word',            'language' => 'English/Greek',      'script' => 'ὁ Λόγος / The Word',       'tradition' => 'Universal',              'source' => 'John 1:1-14',                        'verse' => 'John 1:1',        'meaning' => 'In the beginning was the Word, and the Word was God'],
    ['name' => 'Prince of Peace',     'language' => 'English/Hebrew',     'script' => 'שַׂר שָׁלוֹם',             'tradition' => 'Messianic Prophecy',     'source' => 'Isaiah 9:6',                         'verse' => 'Isaiah 9:6',      'meaning' => 'Wonderful Counselor, Mighty God, Everlasting Father'],
    ['name' => 'Alpha and Omega',     'language' => 'Greek/English',      'script' => 'Α Ω',                      'tradition' => 'Universal',              'source' => 'Revelation 1:8, 22:13',              'verse' => 'Revelation 1:8',  'meaning' => 'The Beginning and the End'],
    ['name' => 'Ruh Allah',           'language' => 'Arabic',             'script' => 'روح الله',                  'tradition' => 'Islamic Theology',       'source' => 'Quran (Surah 4:171)',                'verse' => 'Surah 4:171',     'meaning' => 'Spirit of God — a title given to Isa in the Quran'],
    ['name' => 'Kalimatu Allah',      'language' => 'Arabic',             'script' => 'كلمة الله',                 'tradition' => 'Islamic Theology',       'source' => 'Quran (Surah 3:45)',                 'verse' => 'Surah 3:45',      'meaning' => 'Word of God — the Quran calls Isa the Word from Allah'],
];

// ══════════════════════════════════════════════════════════════
//  GOSPEL MUSIC GENRES
// ══════════════════════════════════════════════════════════════
$GOSPEL_GENRES = [
    ['id' => 'traditional-hymns',      'name' => 'Traditional Hymns',          'description' => 'Timeless hymns of the faith — Amazing Grace, How Great Thou Art, Be Thou My Vision.',        'bpm_range' => [60, 100],  'mood' => 'reverent',    'icon' => '⛪'],
    ['id' => 'contemporary-worship',   'name' => 'Contemporary Worship',       'description' => 'Modern worship anthems for the church — uplifting, powerful, studio-quality praise.',          'bpm_range' => [70, 130],  'mood' => 'uplifting',   'icon' => '🎤'],
    ['id' => 'gospel-choir',           'name' => 'Gospel Choir',               'description' => 'Full choir arrangements with that powerful, spirit-filled gospel sound.',                     'bpm_range' => [80, 140],  'mood' => 'joyful',      'icon' => '🎶'],
    ['id' => 'psalms-of-david',        'name' => 'Psalms of David',            'description' => 'Musical settings of the Psalms — harp, strings, and the voice of David the worshipper.',      'bpm_range' => [50, 90],   'mood' => 'devotional',  'icon' => '🎵'],
    ['id' => 'spiritual',              'name' => 'Spirituals',                 'description' => 'Deep spiritual songs of faith, freedom, and hope — rooted in history and the heart.',         'bpm_range' => [60, 110],  'mood' => 'spiritual',   'icon' => '🕊️'],
    ['id' => 'gospel-jazz',            'name' => 'Gospel Jazz',                'description' => 'Smooth jazz infused with praise — saxophone, keys, and spirit-led improvisation.',             'bpm_range' => [70, 120],  'mood' => 'smooth',      'icon' => '🎷'],
    ['id' => 'worship-ambient',        'name' => 'Worship Ambient',            'description' => 'Atmospheric worship soundscapes for prayer, meditation, and soaking in His presence.',         'bpm_range' => [40, 80],   'mood' => 'peaceful',    'icon' => '🌊'],
    ['id' => 'gospel-hip-hop',         'name' => 'Gospel Hip-Hop',             'description' => 'Faith-based hip-hop — spoken word, beats, and rhymes that glorify the Most High.',             'bpm_range' => [80, 110],  'mood' => 'energetic',   'icon' => '🎙️'],
    ['id' => 'praise-dance',           'name' => 'Praise & Dance',             'description' => 'Upbeat praise music for joyful dance — let everything that has breath praise the Lord!',      'bpm_range' => [110, 150], 'mood' => 'euphoric',    'icon' => '💃'],
    ['id' => 'world-worship',          'name' => 'World Worship',              'description' => 'Worship from every nation — African drums, Middle Eastern melodies, Asian strings.',           'bpm_range' => [60, 130],  'mood' => 'global',      'icon' => '🌍'],
    ['id' => 'acoustic-worship',       'name' => 'Acoustic Worship',           'description' => 'Stripped-back acoustic worship — just a guitar, a voice, and the Holy Spirit.',                'bpm_range' => [60, 100],  'mood' => 'intimate',    'icon' => '🎸'],
    ['id' => 'orchestral-sacred',      'name' => 'Orchestral Sacred',          'description' => 'Grand orchestral arrangements — Handel\'s Messiah, sacred symphonies, heavenly compositions.', 'bpm_range' => [50, 120],  'mood' => 'majestic',    'icon' => '🎻'],
];

// ══════════════════════════════════════════════════════════════
//  GOSPEL INSTRUMENTS — for SoundStudioPro gospel creation
// ══════════════════════════════════════════════════════════════
$GOSPEL_INSTRUMENTS = [
    ['id' => 'harp-of-david',   'name' => 'Harp of David',           'type' => 'strings',     'icon' => '🎵', 'description' => 'The instrument of King David — sweet psalms and melodies that soothe the soul.',                        'ssp_preset' => 'harp-ethereal'],
    ['id' => 'pipe-organ',      'name' => 'Cathedral Pipe Organ',    'type' => 'keys',        'icon' => '⛪', 'description' => 'The mighty pipe organ — filling cathedrals with the glory of God for centuries.',                        'ssp_preset' => 'organ-cathedral'],
    ['id' => 'gospel-piano',    'name' => 'Gospel Piano',            'type' => 'keys',        'icon' => '🎹', 'description' => 'Rich, warm gospel piano with runs, chords, and that signature worship sound.',                          'ssp_preset' => 'piano-gospel'],
    ['id' => 'choir-voices',    'name' => 'Heavenly Choir',          'type' => 'vocals',      'icon' => '🎶', 'description' => 'A full gospel choir — soprano, alto, tenor, bass — singing praises to the King.',                       'ssp_preset' => 'choir-full'],
    ['id' => 'acoustic-guitar', 'name' => 'Worship Guitar',          'type' => 'strings',     'icon' => '🎸', 'description' => 'Warm acoustic guitar for intimate worship — fingerpicked or strummed.',                                  'ssp_preset' => 'guitar-acoustic-worship'],
    ['id' => 'strings-section', 'name' => 'String Orchestra',        'type' => 'strings',     'icon' => '🎻', 'description' => 'Lush string arrangements — violins, violas, cellos — creating heavenly atmosphere.',                     'ssp_preset' => 'strings-orchestral'],
    ['id' => 'trumpet-herald',  'name' => 'Herald Trumpet',          'type' => 'brass',       'icon' => '🎺', 'description' => 'The trumpet shall sound — heralding the King of Kings with majesty and power.',                         'ssp_preset' => 'trumpet-herald'],
    ['id' => 'gospel-drums',    'name' => 'Gospel Drums',            'type' => 'percussion',  'icon' => '🥁', 'description' => 'Powerful gospel drumming — from soft brushes to full praise breaks.',                                    'ssp_preset' => 'drums-gospel'],
    ['id' => 'african-drums',   'name' => 'African Praise Drums',    'type' => 'percussion',  'icon' => '🪘', 'description' => 'Djembe, talking drums, and African rhythms — the heartbeat of world worship.',                          'ssp_preset' => 'drums-african'],
    ['id' => 'gospel-bass',     'name' => 'Gospel Bass',             'type' => 'bass',        'icon' => '🎸', 'description' => 'Deep, groovy gospel bass lines that anchor the praise.',                                                 'ssp_preset' => 'bass-gospel'],
    ['id' => 'saxophone',       'name' => 'Worship Saxophone',       'type' => 'woodwind',    'icon' => '🎷', 'description' => 'Smooth, spirit-led saxophone for worship jazz and prophetic flow.',                                      'ssp_preset' => 'sax-worship'],
    ['id' => 'flute-spirit',    'name' => 'Spirit Flute',            'type' => 'woodwind',    'icon' => '🪈', 'description' => 'Gentle, ethereal flute melodies — like a still small voice in the wilderness.',                          'ssp_preset' => 'flute-ethereal'],
    ['id' => 'tambourine',      'name' => 'Praise Tambourine',       'type' => 'percussion',  'icon' => '🎊', 'description' => 'The sound of celebration — Miriam danced with tambourines after crossing the Red Sea.',                  'ssp_preset' => 'tambourine-praise'],
    ['id' => 'synth-pads',      'name' => 'Worship Pads',            'type' => 'synth',       'icon' => '🌊', 'description' => 'Ambient worship pads — sustained, warm tones creating an atmosphere of His presence.',                   'ssp_preset' => 'pads-worship'],
    ['id' => 'shofar',          'name' => 'Shofar',                  'type' => 'brass',       'icon' => '📯', 'description' => 'The ancient ram\'s horn — blown at Rosh Hashanah, Yom Kippur, and to herald the coming King.',            'ssp_preset' => 'shofar-ceremonial'],
    ['id' => 'oud',             'name' => 'Middle Eastern Oud',      'type' => 'strings',     'icon' => '🪕', 'description' => 'An ancient stringed instrument from the Holy Land — deeply expressive melodies of worship.',              'ssp_preset' => 'oud-worship'],
];

// ══════════════════════════════════════════════════════════════
//  PSALMS OF DAVID — Musical foundations from the Psalmist King
// ══════════════════════════════════════════════════════════════
$PSALMS_OF_DAVID = [
    ['psalm' => 'Psalm 23',   'title' => 'The Lord is My Shepherd',       'key' => 'D major',  'tempo' => 'Adagio',      'bpm' => 60,  'mood' => 'peaceful',    'instruments' => ['harp-of-david', 'strings-section', 'flute-spirit'],                           'verse' => 'The LORD is my shepherd; I shall not want.'],
    ['psalm' => 'Psalm 100',  'title' => 'Make a Joyful Noise',           'key' => 'G major',  'tempo' => 'Allegro',     'bpm' => 120, 'mood' => 'joyful',      'instruments' => ['gospel-drums', 'trumpet-herald', 'choir-voices', 'tambourine'],              'verse' => 'Make a joyful noise unto the LORD, all ye lands.'],
    ['psalm' => 'Psalm 150',  'title' => 'Praise Him with Everything',    'key' => 'Bb major', 'tempo' => 'Vivace',      'bpm' => 140, 'mood' => 'euphoric',    'instruments' => ['trumpet-herald', 'gospel-drums', 'tambourine', 'harp-of-david', 'choir-voices'], 'verse' => 'Let every thing that hath breath praise the LORD.'],
    ['psalm' => 'Psalm 51',   'title' => 'Create in Me a Clean Heart',    'key' => 'A minor',  'tempo' => 'Lento',       'bpm' => 50,  'mood' => 'repentant',   'instruments' => ['gospel-piano', 'strings-section'],                                            'verse' => 'Create in me a clean heart, O God; and renew a right spirit within me.'],
    ['psalm' => 'Psalm 91',   'title' => 'Under His Wings',               'key' => 'Eb major', 'tempo' => 'Andante',     'bpm' => 72,  'mood' => 'protective',  'instruments' => ['harp-of-david', 'flute-spirit', 'synth-pads'],                                'verse' => 'He that dwelleth in the secret place of the most High shall abide under the shadow of the Almighty.'],
    ['psalm' => 'Psalm 27',   'title' => 'The Lord is My Light',          'key' => 'C major',  'tempo' => 'Moderato',    'bpm' => 88,  'mood' => 'confident',   'instruments' => ['acoustic-guitar', 'gospel-piano', 'strings-section'],                         'verse' => 'The LORD is my light and my salvation; whom shall I fear?'],
    ['psalm' => 'Psalm 46',   'title' => 'Be Still and Know',             'key' => 'F major',  'tempo' => 'Largo',       'bpm' => 48,  'mood' => 'still',       'instruments' => ['synth-pads', 'harp-of-david', 'flute-spirit'],                                'verse' => 'Be still, and know that I am God.'],
    ['psalm' => 'Psalm 95',   'title' => 'Come Let Us Sing',              'key' => 'A major',  'tempo' => 'Allegretto',  'bpm' => 108, 'mood' => 'celebratory', 'instruments' => ['choir-voices', 'gospel-piano', 'gospel-drums', 'tambourine'],                 'verse' => 'O come, let us sing unto the LORD: let us make a joyful noise to the rock of our salvation.'],
    ['psalm' => 'Psalm 34',   'title' => 'Taste and See',                 'key' => 'E major',  'tempo' => 'Andante',     'bpm' => 76,  'mood' => 'grateful',    'instruments' => ['acoustic-guitar', 'flute-spirit', 'strings-section'],                         'verse' => 'O taste and see that the LORD is good: blessed is the man that trusteth in him.'],
    ['psalm' => 'Psalm 42',   'title' => 'As the Deer Pants',             'key' => 'D minor',  'tempo' => 'Adagio',      'bpm' => 56,  'mood' => 'longing',     'instruments' => ['harp-of-david', 'synth-pads', 'choir-voices'],                                'verse' => 'As the hart panteth after the water brooks, so panteth my soul after thee, O God.'],
    ['psalm' => 'Psalm 8',    'title' => 'How Majestic is Your Name',     'key' => 'F# major', 'tempo' => 'Moderato',    'bpm' => 92,  'mood' => 'awe',         'instruments' => ['strings-section', 'trumpet-herald', 'pipe-organ'],                            'verse' => 'O LORD our Lord, how excellent is thy name in all the earth!'],
    ['psalm' => 'Psalm 1',    'title' => 'Blessed is the Man',            'key' => 'C major',  'tempo' => 'Andante',     'bpm' => 80,  'mood' => 'wise',        'instruments' => ['acoustic-guitar', 'harp-of-david'],                                           'verse' => 'Blessed is the man that walketh not in the counsel of the ungodly.'],
    ['psalm' => 'Psalm 139',  'title' => 'You Have Searched Me',          'key' => 'Ab major', 'tempo' => 'Adagio',      'bpm' => 64,  'mood' => 'intimate',    'instruments' => ['gospel-piano', 'strings-section', 'synth-pads'],                              'verse' => 'O LORD, thou hast searched me, and known me.'],
    ['psalm' => 'Psalm 19',   'title' => 'The Heavens Declare',           'key' => 'Bb major', 'tempo' => 'Allegro',     'bpm' => 116, 'mood' => 'majestic',    'instruments' => ['pipe-organ', 'strings-section', 'trumpet-herald', 'choir-voices'],            'verse' => 'The heavens declare the glory of God; and the firmament sheweth his handywork.'],
    ['psalm' => 'Psalm 103',  'title' => 'Bless the Lord O My Soul',      'key' => 'G major',  'tempo' => 'Moderato',    'bpm' => 96,  'mood' => 'thankful',    'instruments' => ['choir-voices', 'gospel-piano', 'strings-section', 'gospel-drums'],            'verse' => 'Bless the LORD, O my soul: and all that is within me, bless his holy name.'],
    ['psalm' => 'Psalm 121',  'title' => 'I Lift My Eyes to the Hills',   'key' => 'E major',  'tempo' => 'Andante',     'bpm' => 70,  'mood' => 'trusting',    'instruments' => ['acoustic-guitar', 'flute-spirit', 'harp-of-david'],                           'verse' => 'I will lift up mine eyes unto the hills, from whence cometh my help.'],
];

// ══════════════════════════════════════════════════════════════
//  GOSPEL TRACK CATALOG — Pre-made worship tracks
// ══════════════════════════════════════════════════════════════
$GOSPEL_TRACKS = [
    // Traditional Hymns
    ['id' => 'gt-001', 'title' => 'Amazing Grace (Sanctuary Mix)',        'artist' => 'Will Chambers',           'genre' => 'traditional-hymns',    'bpm' => 72,  'key' => 'G major',  'duration' => 312, 'energy' => 4, 'mood' => 'reverent',     'psalm_ref' => null,         'scripture' => 'Ephesians 2:8-9',      'ssp_id' => 'ssp-gs-001'],
    ['id' => 'gt-002', 'title' => 'How Great Thou Art',                   'artist' => 'Sanctuary Choir',         'genre' => 'traditional-hymns',    'bpm' => 68,  'key' => 'Bb major', 'duration' => 285, 'energy' => 5, 'mood' => 'majestic',     'psalm_ref' => 'Psalm 8',    'scripture' => 'Psalm 8:1',            'ssp_id' => 'ssp-gs-002'],
    ['id' => 'gt-003', 'title' => 'Be Thou My Vision',                    'artist' => 'Creeker Chambers',        'genre' => 'traditional-hymns',    'bpm' => 76,  'key' => 'Eb major', 'duration' => 298, 'energy' => 3, 'mood' => 'devotional',   'psalm_ref' => 'Psalm 119',  'scripture' => 'Psalm 119:105',        'ssp_id' => 'ssp-gs-003'],
    // Contemporary Worship
    ['id' => 'gt-004', 'title' => 'Oceans of His Grace',                  'artist' => 'SoundStudioPro Worship',  'genre' => 'contemporary-worship', 'bpm' => 66,  'key' => 'D major',  'duration' => 420, 'energy' => 6, 'mood' => 'uplifting',    'psalm_ref' => 'Psalm 42',   'scripture' => 'Matthew 14:29',        'ssp_id' => 'ssp-gs-004'],
    ['id' => 'gt-005', 'title' => 'Cornerstone',                          'artist' => 'SoundStudioPro Worship',  'genre' => 'contemporary-worship', 'bpm' => 74,  'key' => 'C major',  'duration' => 354, 'energy' => 7, 'mood' => 'powerful',     'psalm_ref' => 'Psalm 118',  'scripture' => '1 Peter 2:6',          'ssp_id' => 'ssp-gs-005'],
    ['id' => 'gt-006', 'title' => 'What a Beautiful Name',                'artist' => 'Sanctuary Worship Band',  'genre' => 'contemporary-worship', 'bpm' => 68,  'key' => 'D major',  'duration' => 378, 'energy' => 8, 'mood' => 'powerful',     'psalm_ref' => null,         'scripture' => 'Philippians 2:9-11',   'ssp_id' => 'ssp-gs-006'],
    // Gospel Choir
    ['id' => 'gt-007', 'title' => 'Oh Happy Day',                         'artist' => 'Sanctuary Choir',         'genre' => 'gospel-choir',         'bpm' => 108, 'key' => 'F major',  'duration' => 290, 'energy' => 9, 'mood' => 'joyful',       'psalm_ref' => null,         'scripture' => 'Acts 2:41',            'ssp_id' => 'ssp-gs-007'],
    ['id' => 'gt-008', 'title' => 'Total Praise',                         'artist' => 'Sanctuary Choir',         'genre' => 'gospel-choir',         'bpm' => 72,  'key' => 'Ab major', 'duration' => 340, 'energy' => 7, 'mood' => 'reverent',     'psalm_ref' => 'Psalm 121',  'scripture' => 'Psalm 121:1-2',        'ssp_id' => 'ssp-gs-008'],
    ['id' => 'gt-009', 'title' => 'Hallelujah Chorus',                    'artist' => 'Grand Orchestra',         'genre' => 'gospel-choir',         'bpm' => 96,  'key' => 'D major',  'duration' => 260, 'energy' => 10,'mood' => 'triumphant',   'psalm_ref' => 'Psalm 150',  'scripture' => 'Revelation 19:6',      'ssp_id' => 'ssp-gs-009'],
    // Psalms of David
    ['id' => 'gt-010', 'title' => 'Psalm 23 (Shepherd\'s Song)',          'artist' => 'Creeker Chambers',        'genre' => 'psalms-of-david',      'bpm' => 60,  'key' => 'D major',  'duration' => 480, 'energy' => 2, 'mood' => 'peaceful',     'psalm_ref' => 'Psalm 23',   'scripture' => 'Psalm 23:1-4',         'ssp_id' => 'ssp-gs-010'],
    ['id' => 'gt-011', 'title' => 'Psalm 91 (Under His Wings)',           'artist' => 'SoundStudioPro Worship',  'genre' => 'psalms-of-david',      'bpm' => 72,  'key' => 'Eb major', 'duration' => 396, 'energy' => 3, 'mood' => 'protective',   'psalm_ref' => 'Psalm 91',   'scripture' => 'Psalm 91:1-2',         'ssp_id' => 'ssp-gs-011'],
    ['id' => 'gt-012', 'title' => 'Psalm 150 (Praise Him!)',              'artist' => 'Sanctuary Praise Band',   'genre' => 'psalms-of-david',      'bpm' => 140, 'key' => 'Bb major', 'duration' => 240, 'energy' => 10,'mood' => 'euphoric',     'psalm_ref' => 'Psalm 150',  'scripture' => 'Psalm 150:6',          'ssp_id' => 'ssp-gs-012'],
    ['id' => 'gt-013', 'title' => 'Be Still (Psalm 46)',                  'artist' => 'Creeker Chambers',        'genre' => 'psalms-of-david',      'bpm' => 48,  'key' => 'F major',  'duration' => 540, 'energy' => 1, 'mood' => 'still',        'psalm_ref' => 'Psalm 46',   'scripture' => 'Psalm 46:10',          'ssp_id' => 'ssp-gs-013'],
    // Worship Ambient
    ['id' => 'gt-014', 'title' => 'Rivers of Living Water',               'artist' => 'SoundStudioPro Worship',  'genre' => 'worship-ambient',      'bpm' => 55,  'key' => 'E minor',  'duration' => 600, 'energy' => 2, 'mood' => 'peaceful',     'psalm_ref' => 'Psalm 42',   'scripture' => 'John 7:38',            'ssp_id' => 'ssp-gs-014'],
    ['id' => 'gt-015', 'title' => 'In the Secret Place',                  'artist' => 'Creeker Chambers',        'genre' => 'worship-ambient',      'bpm' => 45,  'key' => 'Ab major', 'duration' => 720, 'energy' => 1, 'mood' => 'intimate',     'psalm_ref' => 'Psalm 91',   'scripture' => 'Psalm 91:1',           'ssp_id' => 'ssp-gs-015'],
    // Gospel Jazz
    ['id' => 'gt-016', 'title' => 'Grace & Groove',                       'artist' => 'DRUMAHON',                'genre' => 'gospel-jazz',          'bpm' => 92,  'key' => 'Db major', 'duration' => 330, 'energy' => 5, 'mood' => 'smooth',       'psalm_ref' => null,         'scripture' => 'Titus 2:11',           'ssp_id' => 'ssp-gs-016'],
    ['id' => 'gt-017', 'title' => 'Midnight Prayer (Jazz)',               'artist' => 'DRUMAHON',                'genre' => 'gospel-jazz',          'bpm' => 85,  'key' => 'Bb minor', 'duration' => 420, 'energy' => 4, 'mood' => 'reflective',   'psalm_ref' => 'Psalm 63',   'scripture' => 'Psalm 63:6',           'ssp_id' => 'ssp-gs-017'],
    // Gospel Hip-Hop
    ['id' => 'gt-018', 'title' => 'Armor of God',                         'artist' => 'Taz\'',                   'genre' => 'gospel-hip-hop',       'bpm' => 95,  'key' => 'C minor',  'duration' => 264, 'energy' => 8, 'mood' => 'bold',         'psalm_ref' => null,         'scripture' => 'Ephesians 6:10-18',    'ssp_id' => 'ssp-gs-018'],
    ['id' => 'gt-019', 'title' => 'Faith Walk',                           'artist' => 'Taz\'',                   'genre' => 'gospel-hip-hop',       'bpm' => 88,  'key' => 'E minor',  'duration' => 240, 'energy' => 7, 'mood' => 'determined',   'psalm_ref' => null,         'scripture' => '2 Corinthians 5:7',    'ssp_id' => 'ssp-gs-019'],
    // Praise & Dance
    ['id' => 'gt-020', 'title' => 'Shout to the Lord (EDM Praise)',       'artist' => 'SoundStudioPro',          'genre' => 'praise-dance',         'bpm' => 128, 'key' => 'A major',  'duration' => 300, 'energy' => 9, 'mood' => 'euphoric',     'psalm_ref' => 'Psalm 100',  'scripture' => 'Psalm 100:1',          'ssp_id' => 'ssp-gs-020'],
    ['id' => 'gt-021', 'title' => 'Dance Before the Lord',                'artist' => 'MANNJAI514',              'genre' => 'praise-dance',         'bpm' => 118, 'key' => 'G major',  'duration' => 276, 'energy' => 8, 'mood' => 'celebratory',  'psalm_ref' => 'Psalm 149',  'scripture' => '2 Samuel 6:14',        'ssp_id' => 'ssp-gs-021'],
    // World Worship
    ['id' => 'gt-022', 'title' => 'Yesu Ni Bwana (Jesus is Lord)',        'artist' => 'African Praise Collective','genre' => 'world-worship',       'bpm' => 106, 'key' => 'F major',  'duration' => 288, 'energy' => 7, 'mood' => 'joyful',       'psalm_ref' => null,         'scripture' => 'Philippians 2:11',     'ssp_id' => 'ssp-gs-022'],
    ['id' => 'gt-023', 'title' => 'Ya Rabb (O Lord)',                     'artist' => 'Middle Eastern Worship',  'genre' => 'world-worship',        'bpm' => 78,  'key' => 'D minor',  'duration' => 360, 'energy' => 5, 'mood' => 'devotional',   'psalm_ref' => 'Psalm 42',   'scripture' => 'Psalm 42:1',           'ssp_id' => 'ssp-gs-023'],
    ['id' => 'gt-024', 'title' => 'Shalom Jerusalem',                     'artist' => 'Messianic Worship Band',  'genre' => 'world-worship',        'bpm' => 82,  'key' => 'E minor',  'duration' => 324, 'energy' => 6, 'mood' => 'prayerful',    'psalm_ref' => 'Psalm 122',  'scripture' => 'Psalm 122:6',          'ssp_id' => 'ssp-gs-024'],
    // Acoustic Worship
    ['id' => 'gt-025', 'title' => '10,000 Reasons (Acoustic)',            'artist' => 'Sanctuary Acoustic',      'genre' => 'acoustic-worship',     'bpm' => 74,  'key' => 'G major',  'duration' => 380, 'energy' => 5, 'mood' => 'thankful',     'psalm_ref' => 'Psalm 103',  'scripture' => 'Psalm 103:1',          'ssp_id' => 'ssp-gs-025'],
    ['id' => 'gt-026', 'title' => 'Here I Am to Worship',                 'artist' => 'Sanctuary Acoustic',      'genre' => 'acoustic-worship',     'bpm' => 68,  'key' => 'E major',  'duration' => 336, 'energy' => 4, 'mood' => 'intimate',     'psalm_ref' => null,         'scripture' => 'Romans 12:1',          'ssp_id' => 'ssp-gs-026'],
    // Orchestral Sacred
    ['id' => 'gt-027', 'title' => 'Messiah: Worthy is the Lamb',         'artist' => 'Grand Orchestra',         'genre' => 'orchestral-sacred',    'bpm' => 88,  'key' => 'D major',  'duration' => 450, 'energy' => 9, 'mood' => 'majestic',     'psalm_ref' => null,         'scripture' => 'Revelation 5:12',      'ssp_id' => 'ssp-gs-027'],
    ['id' => 'gt-028', 'title' => 'Ave Verum Corpus',                     'artist' => 'Grand Orchestra',         'genre' => 'orchestral-sacred',    'bpm' => 56,  'key' => 'D major',  'duration' => 210, 'energy' => 3, 'mood' => 'sacred',       'psalm_ref' => null,         'scripture' => 'John 6:51',            'ssp_id' => 'ssp-gs-028'],
    // Spirituals
    ['id' => 'gt-029', 'title' => 'Swing Low Sweet Chariot',              'artist' => 'Sanctuary Choir',         'genre' => 'spiritual',            'bpm' => 72,  'key' => 'F major',  'duration' => 270, 'energy' => 4, 'mood' => 'spiritual',    'psalm_ref' => null,         'scripture' => '2 Kings 2:11',         'ssp_id' => 'ssp-gs-029'],
    ['id' => 'gt-030', 'title' => 'Were You There',                       'artist' => 'Sanctuary Choir',         'genre' => 'spiritual',            'bpm' => 58,  'key' => 'C minor',  'duration' => 300, 'energy' => 3, 'mood' => 'sorrowful',    'psalm_ref' => null,         'scripture' => 'Isaiah 53:5',          'ssp_id' => 'ssp-gs-030'],
];

// ══════════════════════════════════════════════════════════════
//  WORSHIP ENVIRONMENTS — Nature & Outdoor stages
// ══════════════════════════════════════════════════════════════
$WORSHIP_ENVIRONMENTS = [
    ['id' => 'env-garden-eden',       'name' => 'Garden of Eden',              'type' => 'garden',      'atmosphere' => 'paradise',   'skyColor' => '#87CEEB', 'groundColor' => '#228B22', 'ambientSound' => 'birds-stream',        'description' => 'A lush paradise garden with fruit trees, crystal rivers, and the presence of God walking in the cool of the day.', 'scripture' => 'Genesis 2:8-9'],
    ['id' => 'env-jordan-river',      'name' => 'Jordan River',                'type' => 'riverside',   'atmosphere' => 'baptismal',  'skyColor' => '#4682B4', 'groundColor' => '#D2B48C', 'ambientSound' => 'flowing-river',       'description' => 'The banks of the Jordan River — where Jesus was baptized and the heavens were opened.', 'scripture' => 'Matthew 3:16'],
    ['id' => 'env-mount-sinai',       'name' => 'Mount Sinai',                 'type' => 'mountain',    'atmosphere' => 'glory',      'skyColor' => '#FF6347', 'groundColor' => '#8B4513', 'ambientSound' => 'wind-thunder',        'description' => 'The mountain of God — where Moses received the commandments and the glory of the Lord appeared like consuming fire.', 'scripture' => 'Exodus 24:17'],
    ['id' => 'env-sea-galilee',       'name' => 'Sea of Galilee',              'type' => 'lakeside',    'atmosphere' => 'serene',     'skyColor' => '#B0C4DE', 'groundColor' => '#C2B280', 'ambientSound' => 'gentle-waves',        'description' => 'The peaceful shores of the Sea of Galilee — where Jesus called His disciples, walked on water, and calmed the storm.', 'scripture' => 'Matthew 4:18-19'],
    ['id' => 'env-olive-grove',       'name' => 'Olive Grove (Gethsemane)',    'type' => 'grove',       'atmosphere' => 'prayer',     'skyColor' => '#2F4F4F', 'groundColor' => '#556B2F', 'ambientSound' => 'night-crickets',      'description' => 'The ancient olive grove where Jesus prayed before the cross — a place of deep, intimate prayer.', 'scripture' => 'Matthew 26:36'],
    ['id' => 'env-wilderness',        'name' => 'Wilderness of Judea',         'type' => 'desert',      'atmosphere' => 'solitude',   'skyColor' => '#F0E68C', 'groundColor' => '#DEB887', 'ambientSound' => 'desert-wind',         'description' => 'The vast wilderness where Jesus fasted 40 days and overcame temptation with the Word of God.', 'scripture' => 'Matthew 4:1-4'],
    ['id' => 'env-cedar-forest',      'name' => 'Cedars of Lebanon',           'type' => 'forest',      'atmosphere' => 'majestic',   'skyColor' => '#87CEEB', 'groundColor' => '#2E8B57', 'ambientSound' => 'forest-birds',        'description' => 'Towering cedar trees that Solomon used to build the Temple — a cathedral of nature where worship rises like incense.', 'scripture' => '1 Kings 5:6'],
    ['id' => 'env-starfield',         'name' => 'Abraham\'s Starfield',        'type' => 'night-sky',   'atmosphere' => 'promise',    'skyColor' => '#0B0B2B', 'groundColor' => '#333333', 'ambientSound' => 'night-silence',       'description' => 'The infinite night sky God showed Abraham — "Look toward heaven and count the stars, if you can number them."', 'scripture' => 'Genesis 15:5'],
    ['id' => 'env-sunrise',           'name' => 'Easter Sunrise',              'type' => 'hilltop',     'atmosphere' => 'resurrection','skyColor' => '#FF7F50', 'groundColor' => '#8FBC8F', 'ambientSound' => 'dawn-chorus',         'description' => 'A golden sunrise over rolling hills — the dawn of the resurrection, when Mary found the empty tomb.', 'scripture' => 'Matthew 28:1-6'],
    ['id' => 'env-waterfall',         'name' => 'Living Water Falls',          'type' => 'waterfall',   'atmosphere' => 'renewal',    'skyColor' => '#ADD8E6', 'groundColor' => '#3CB371', 'ambientSound' => 'waterfall-birds',     'description' => 'A cascading waterfall surrounded by tropical greenery — "He that believeth in me, out of his belly shall flow rivers of living water."', 'scripture' => 'John 7:38'],
    ['id' => 'env-vineyard',          'name' => 'The True Vine Vineyard',      'type' => 'vineyard',    'atmosphere' => 'abiding',    'skyColor' => '#E6E6FA', 'groundColor' => '#6B8E23', 'ambientSound' => 'vineyard-breeze',     'description' => 'Golden-hour vineyard rows stretching to the horizon — "I am the vine, ye are the branches."', 'scripture' => 'John 15:5'],
    ['id' => 'env-ocean-shore',       'name' => 'Shores of Eternity',          'type' => 'beach',       'atmosphere' => 'eternal',    'skyColor' => '#00CED1', 'groundColor' => '#F5DEB3', 'ambientSound' => 'ocean-waves',         'description' => 'A vast, untouched shore with crystal-clear waters — a glimpse of the sea of glass before the throne (Rev 4:6).', 'scripture' => 'Revelation 4:6'],
];

// ══════════════════════════════════════════════════════════════
//  SOUNDSTUDIOPRO TOKEN SYSTEM — GSM → SSP Gospel Credits
// ══════════════════════════════════════════════════════════════
$SSP_TOKEN_CONFIG = [
    'token_name'       => 'GSM',
    'conversion_rate'  => 100,     // 100 GSM = 1 SSP Gospel Credit
    'credit_name'      => 'SSP Gospel Credit',
    'credit_symbol'    => '♪',
    'gospel_actions'   => [
        ['action' => 'create-track',      'cost' => 1,  'description' => 'Create a new gospel track with AI instruments'],
        ['action' => 'add-instrument',    'cost' => 0,  'description' => 'Add an instrument layer to your track (included)'],
        ['action' => 'choir-arrangement', 'cost' => 2,  'description' => 'Full choir arrangement with soprano/alto/tenor/bass'],
        ['action' => 'psalm-composition', 'cost' => 1,  'description' => 'Generate a musical composition from a Psalm of David'],
        ['action' => 'automix-session',   'cost' => 1,  'description' => 'Start a 30-minute gospel automix session'],
        ['action' => 'export-hd',         'cost' => 2,  'description' => 'Export your gospel track in HD (WAV/FLAC)'],
        ['action' => 'nature-stage',      'cost' => 0,  'description' => 'Choose a worship environment (free with any session)'],
        ['action' => 'world-translation', 'cost' => 1,  'description' => 'Translate worship lyrics into another language'],
    ],
    'ssp_studio_url'   => 'https://soundstudiopro.com/dj_mixer.php',
    'ssp_api_base'     => 'https://soundstudiopro.com/api',
    'token_swap_url'   => '/pay/token-swap.php',
];

// ══════════════════════════════════════════════════════════════
//  GOSPEL AUTOMIX PRESETS
// ══════════════════════════════════════════════════════════════
$AUTOMIX_PRESETS = [
    ['id' => 'sunday-morning',     'name' => 'Sunday Morning Service',    'duration' => 60,  'genres' => ['traditional-hymns', 'contemporary-worship', 'gospel-choir'],              'mood_flow' => ['reverent', 'uplifting', 'joyful', 'powerful', 'reverent'],           'environment' => 'env-sunrise',       'description' => 'A complete Sunday morning worship set — from quiet reverence to powerful praise and back to hushed adoration.'],
    ['id' => 'psalms-meditation',  'name' => 'Psalms of David Meditation','duration' => 45,  'genres' => ['psalms-of-david', 'worship-ambient'],                                     'mood_flow' => ['still', 'peaceful', 'devotional', 'intimate', 'awe'],               'environment' => 'env-garden-eden',   'description' => 'A meditation journey through the Psalms — harp, strings, and the quiet voice of the Spirit.'],
    ['id' => 'gospel-celebration',  'name' => 'Gospel Celebration',       'duration' => 30,  'genres' => ['gospel-choir', 'praise-dance', 'gospel-hip-hop'],                          'mood_flow' => ['joyful', 'euphoric', 'celebratory', 'bold', 'triumphant'],          'environment' => 'env-sunrise',       'description' => 'High-energy gospel celebration — choir, dance, and praise that shakes the foundations!'],
    ['id' => 'prayer-night',       'name' => 'Night of Prayer',          'duration' => 90,  'genres' => ['worship-ambient', 'acoustic-worship', 'psalms-of-david'],                   'mood_flow' => ['still', 'intimate', 'longing', 'peaceful', 'grateful'],             'environment' => 'env-olive-grove',   'description' => 'An extended prayer session with gentle worship pads, acoustic guitar, and Scripture.'],
    ['id' => 'world-worship',      'name' => 'Nations Worship Together', 'duration' => 45,  'genres' => ['world-worship', 'contemporary-worship', 'praise-dance'],                   'mood_flow' => ['devotional', 'joyful', 'global', 'powerful', 'euphoric'],           'environment' => 'env-ocean-shore',   'description' => 'Worship from every nation — African drums, Middle Eastern oud, and praise from every tongue.'],
    ['id' => 'good-friday',        'name' => 'Good Friday Contemplation','duration' => 60,  'genres' => ['orchestral-sacred', 'spiritual', 'worship-ambient'],                       'mood_flow' => ['sorrowful', 'sacred', 'repentant', 'still', 'hopeful'],             'environment' => 'env-olive-grove',   'description' => 'A solemn journey through the Passion — from Gethsemane to Golgotha, ending with the promise of resurrection.'],
    ['id' => 'resurrection-dawn',  'name' => 'Resurrection Sunday Dawn', 'duration' => 45,  'genres' => ['orchestral-sacred', 'gospel-choir', 'praise-dance'],                       'mood_flow' => ['still', 'awe', 'triumphant', 'euphoric', 'majestic'],               'environment' => 'env-sunrise',       'description' => 'From silent tomb to triumphant "He is Risen!" — the greatest story ever told in music.'],
    ['id' => 'jazz-vespers',       'name' => 'Jazz Vespers',             'duration' => 40,  'genres' => ['gospel-jazz', 'acoustic-worship'],                                          'mood_flow' => ['smooth', 'reflective', 'grateful', 'intimate', 'peaceful'],         'environment' => 'env-vineyard',      'description' => 'Evening jazz worship — saxophone, piano, and a glass of the True Vine under sunset skies.'],
];

// ══════════════════════════════════════════════════════════════
//  ACTION ROUTER
// ══════════════════════════════════════════════════════════════
switch ($action) {

    // ── Health ──
    case 'health':
        echo json_encode([
            'success'       => true,
            'service'       => 'ssp-gospel-api',
            'version'       => '1.0.0',
            'tracks'        => count($GOSPEL_TRACKS),
            'genres'        => count($GOSPEL_GENRES),
            'instruments'   => count($GOSPEL_INSTRUMENTS),
            'psalms'        => count($PSALMS_OF_DAVID),
            'environments'  => count($WORSHIP_ENVIRONMENTS),
            'names_of_jesus'=> count($NAMES_OF_JESUS),
            'automix_presets'=> count($AUTOMIX_PRESETS),
            'ssp_integration'=> true,
            'token_system'  => true,
            'timestamp'     => gmdate('c'),
        ]);
        break;

    // ── Names of Jesus across world traditions ──
    case 'names':
        $lang = isset($_GET['language']) ? strtolower(trim($_GET['language'])) : null;
        $results = $NAMES_OF_JESUS;
        if ($lang) {
            $results = array_values(array_filter($results, function($n) use ($lang) {
                return stripos($n['language'], $lang) !== false || stripos($n['tradition'], $lang) !== false;
            }));
        }
        echo json_encode([
            'success' => true,
            'count'   => count($results),
            'message' => 'Jesus Christ unites every nation, tongue, and people. He is known and loved across the world.',
            'names'   => $results,
        ]);
        break;

    // ── Gospel tracks ──
    case 'tracks':
        $genre    = isset($_GET['genre'])    ? trim($_GET['genre'])    : null;
        $mood     = isset($_GET['mood'])     ? trim($_GET['mood'])     : null;
        $search   = isset($_GET['search'])   ? trim($_GET['search'])   : null;
        $psalm    = isset($_GET['psalm'])    ? trim($_GET['psalm'])    : null;
        $results  = $GOSPEL_TRACKS;

        if ($genre) {
            $results = array_values(array_filter($results, function($t) use ($genre) {
                return $t['genre'] === $genre;
            }));
        }
        if ($mood) {
            $results = array_values(array_filter($results, function($t) use ($mood) {
                return $t['mood'] === $mood;
            }));
        }
        if ($psalm) {
            $results = array_values(array_filter($results, function($t) use ($psalm) {
                return $t['psalm_ref'] && stripos($t['psalm_ref'], $psalm) !== false;
            }));
        }
        if ($search) {
            $q = strtolower($search);
            $results = array_values(array_filter($results, function($t) use ($q) {
                return stripos($t['title'], $q) !== false
                    || stripos($t['artist'], $q) !== false
                    || stripos($t['scripture'], $q) !== false;
            }));
        }

        echo json_encode([
            'success' => true,
            'count'   => count($results),
            'tracks'  => $results,
        ]);
        break;

    // ── Single track ──
    case 'track':
        $tid = isset($_GET['id']) ? trim($_GET['id']) : null;
        if (!$tid) { echo json_encode(['success' => false, 'error' => 'Missing id']); break; }
        $found = null;
        foreach ($GOSPEL_TRACKS as $t) { if ($t['id'] === $tid) { $found = $t; break; } }
        if (!$found) { echo json_encode(['success' => false, 'error' => 'Track not found']); break; }

        // Enrich with psalm data if psalm-linked
        if ($found['psalm_ref']) {
            foreach ($PSALMS_OF_DAVID as $ps) {
                if ($ps['psalm'] === $found['psalm_ref']) {
                    $found['psalm_data'] = $ps;
                    break;
                }
            }
        }
        echo json_encode(['success' => true, 'track' => $found]);
        break;

    // ── Genres ──
    case 'genres':
        // Count tracks per genre
        $genre_counts = [];
        foreach ($GOSPEL_TRACKS as $t) {
            $genre_counts[$t['genre']] = ($genre_counts[$t['genre']] ?? 0) + 1;
        }
        $enriched = array_map(function($g) use ($genre_counts) {
            $g['track_count'] = $genre_counts[$g['id']] ?? 0;
            return $g;
        }, $GOSPEL_GENRES);

        echo json_encode([
            'success' => true,
            'count'   => count($enriched),
            'genres'  => $enriched,
        ]);
        break;

    // ── Instruments ──
    case 'instruments':
        $type = isset($_GET['type']) ? trim($_GET['type']) : null;
        $results = $GOSPEL_INSTRUMENTS;
        if ($type) {
            $results = array_values(array_filter($results, function($i) use ($type) {
                return $i['type'] === $type;
            }));
        }
        echo json_encode([
            'success'     => true,
            'count'       => count($results),
            'instruments' => $results,
        ]);
        break;

    // ── Psalms of David ──
    case 'psalms':
        $mood = isset($_GET['mood']) ? trim($_GET['mood']) : null;
        $results = $PSALMS_OF_DAVID;
        if ($mood) {
            $results = array_values(array_filter($results, function($p) use ($mood) {
                return $p['mood'] === $mood;
            }));
        }
        // Enrich with instrument details
        $instMap = [];
        foreach ($GOSPEL_INSTRUMENTS as $inst) { $instMap[$inst['id']] = $inst; }
        foreach ($results as &$ps) {
            $ps['instrument_details'] = array_map(function($iid) use ($instMap) {
                return $instMap[$iid] ?? ['id' => $iid, 'name' => $iid];
            }, $ps['instruments']);
        }
        echo json_encode([
            'success' => true,
            'count'   => count($results),
            'psalms'  => $results,
        ]);
        break;

    // ── Worship Environments ──
    case 'environments':
        $type = isset($_GET['type']) ? trim($_GET['type']) : null;
        $results = $WORSHIP_ENVIRONMENTS;
        if ($type) {
            $results = array_values(array_filter($results, function($e) use ($type) {
                return $e['type'] === $type;
            }));
        }
        echo json_encode([
            'success'      => true,
            'count'        => count($results),
            'environments' => $results,
        ]);
        break;

    // ── Token / Credit Info ──
    case 'tokens':
        echo json_encode([
            'success' => true,
            'config'  => $SSP_TOKEN_CONFIG,
        ]);
        break;

    // ── Create Gospel Track (POST) ──
    case 'create':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'POST required']);
            break;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $title   = isset($input['title'])       ? trim($input['title'])       : '';
        $genre   = isset($input['genre'])       ? trim($input['genre'])       : '';
        $psalm   = isset($input['psalm'])       ? trim($input['psalm'])       : null;
        $instIds = isset($input['instruments']) ? $input['instruments']       : [];
        $envId   = isset($input['environment']) ? trim($input['environment']) : 'env-garden-eden';

        if (empty($title) || empty($genre)) {
            echo json_encode(['success' => false, 'error' => 'Title and genre are required']);
            break;
        }

        // Look up instruments
        $instMap = [];
        foreach ($GOSPEL_INSTRUMENTS as $inst) { $instMap[$inst['id']] = $inst; }
        $selected_instruments = [];
        foreach ($instIds as $iid) {
            if (isset($instMap[$iid])) $selected_instruments[] = $instMap[$iid];
        }

        // Look up environment
        $env = null;
        foreach ($WORSHIP_ENVIRONMENTS as $e) { if ($e['id'] === $envId) { $env = $e; break; } }

        // Look up psalm if specified
        $psalm_data = null;
        if ($psalm) {
            foreach ($PSALMS_OF_DAVID as $ps) {
                if (stripos($ps['psalm'], $psalm) !== false) { $psalm_data = $ps; break; }
            }
        }

        // Find genre details
        $genre_info = null;
        foreach ($GOSPEL_GENRES as $g) { if ($g['id'] === $genre) { $genre_info = $g; break; } }
        $bpm = $genre_info ? rand($genre_info['bpm_range'][0], $genre_info['bpm_range'][1]) : 80;

        $track_id = 'gt-custom-' . bin2hex(random_bytes(4));

        echo json_encode([
            'success'     => true,
            'message'     => 'Your gospel track has been created! Open it in SoundStudioPro to mix and refine.',
            'track'       => [
                'id'          => $track_id,
                'title'       => $title,
                'genre'       => $genre,
                'genre_name'  => $genre_info ? $genre_info['name'] : $genre,
                'bpm'         => $bpm,
                'key'         => $psalm_data ? $psalm_data['key'] : 'C major',
                'mood'        => $genre_info ? $genre_info['mood'] : 'worship',
                'instruments' => $selected_instruments,
                'environment' => $env,
                'psalm_data'  => $psalm_data,
            ],
            'ssp_studio_url' => $SSP_TOKEN_CONFIG['ssp_studio_url'] . '?track=' . $track_id,
            'cost'        => ['credits' => 1, 'gsm_equivalent' => 100],
            'timestamp'   => gmdate('c'),
        ]);
        break;

    // ── Automix Presets ──
    case 'automix':
        $presetId = isset($_GET['preset']) ? trim($_GET['preset']) : null;

        if ($presetId) {
            $found = null;
            foreach ($AUTOMIX_PRESETS as $p) { if ($p['id'] === $presetId) { $found = $p; break; } }
            if (!$found) { echo json_encode(['success' => false, 'error' => 'Preset not found']); break; }

            // Build track playlist matching genres
            $playlist = [];
            foreach ($GOSPEL_TRACKS as $t) {
                if (in_array($t['genre'], $found['genres'])) $playlist[] = $t;
            }
            shuffle($playlist);

            // Find environment
            $env = null;
            foreach ($WORSHIP_ENVIRONMENTS as $e) { if ($e['id'] === $found['environment']) { $env = $e; break; } }

            $found['playlist']    = array_slice($playlist, 0, 8);
            $found['environment_data'] = $env;
            echo json_encode(['success' => true, 'preset' => $found]);
        } else {
            echo json_encode([
                'success' => true,
                'count'   => count($AUTOMIX_PRESETS),
                'presets' => $AUTOMIX_PRESETS,
            ]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action. Available: health, names, tracks, track, genres, instruments, psalms, environments, tokens, create, automix']);
        break;
}
