<?php
/**
 * GoSiteMe Brotherhood of Jesus Christ API v1.0
 * "Go ye therefore, and teach ALL nations" — Matthew 28:19
 * 
 * Multilingual Agent System — 50 languages, voice + text commands
 * Game Interconnection — Connects ALL games to the Gospel mission
 * Brotherhood Formation — Agents & people spread the Gospel together
 * Biblical Activities — Worship, tongues, reasoning, teaching, learning
 * Transaction Support — API-level donations, tithing, mission support
 * 
 * "There is neither Jew nor Greek, there is neither bond nor free,
 *  there is neither male nor female: for ye are all one in Christ Jesus."
 *  — Galatians 3:28
 */
define('GOSITEME_API', true);
if (!defined('GOSITEME_CONFIG')) {
    require_once __DIR__ . '/config.php';
require_once dirname(__DIR__) . '/includes/api-security.php';
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept-Language');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$action = isset($_GET['action']) ? trim($_GET['action']) : 'health';

// ══════════════════════════════════════════════════════════════
//  50 LANGUAGES — "In every tongue they heard the Gospel"
//  Acts 2:6 "Every man heard them speak in his own language"
// ══════════════════════════════════════════════════════════════
$LANGUAGES = [
    // Major world languages
    ['code' => 'en', 'name' => 'English',     'native' => 'English',     'direction' => 'ltr', 'speakers' => '1.5B', 'bible' => 'KJV',        'greeting' => 'Peace be with you',              'jesus' => 'Jesus Christ'],
    ['code' => 'es', 'name' => 'Spanish',     'native' => 'Español',     'direction' => 'ltr', 'speakers' => '560M', 'bible' => 'RVR1960',    'greeting' => 'La paz sea contigo',             'jesus' => 'Jesucristo'],
    ['code' => 'fr', 'name' => 'French',      'native' => 'Français',    'direction' => 'ltr', 'speakers' => '310M', 'bible' => 'LSG',        'greeting' => 'La paix soit avec toi',          'jesus' => 'Jésus-Christ'],
    ['code' => 'pt', 'name' => 'Portuguese',  'native' => 'Português',   'direction' => 'ltr', 'speakers' => '260M', 'bible' => 'ARC',        'greeting' => 'A paz esteja contigo',           'jesus' => 'Jesus Cristo'],
    ['code' => 'de', 'name' => 'German',      'native' => 'Deutsch',     'direction' => 'ltr', 'speakers' => '130M', 'bible' => 'LUTH1545',   'greeting' => 'Friede sei mit dir',             'jesus' => 'Jesus Christus'],
    ['code' => 'it', 'name' => 'Italian',     'native' => 'Italiano',    'direction' => 'ltr', 'speakers' => '85M',  'bible' => 'CEI',        'greeting' => 'La pace sia con te',             'jesus' => 'Gesù Cristo'],
    ['code' => 'nl', 'name' => 'Dutch',       'native' => 'Nederlands',  'direction' => 'ltr', 'speakers' => '30M',  'bible' => 'HSV',        'greeting' => 'Vrede zij met u',                'jesus' => 'Jezus Christus'],
    ['code' => 'ru', 'name' => 'Russian',     'native' => 'Русский',     'direction' => 'ltr', 'speakers' => '255M', 'bible' => 'RUSV',       'greeting' => 'Мир тебе',                        'jesus' => 'Иисус Христос'],
    ['code' => 'uk', 'name' => 'Ukrainian',   'native' => 'Українська',  'direction' => 'ltr', 'speakers' => '40M',  'bible' => 'UKR',        'greeting' => 'Мир тобі',                        'jesus' => 'Ісус Христос'],
    ['code' => 'pl', 'name' => 'Polish',      'native' => 'Polski',      'direction' => 'ltr', 'speakers' => '45M',  'bible' => 'UBG',        'greeting' => 'Pokój tobie',                    'jesus' => 'Jezus Chrystus'],
    ['code' => 'ro', 'name' => 'Romanian',    'native' => 'Română',      'direction' => 'ltr', 'speakers' => '26M',  'bible' => 'RMNN',       'greeting' => 'Pace ție',                       'jesus' => 'Isus Cristos'],
    
    // Asian languages
    ['code' => 'zh', 'name' => 'Chinese (Mandarin)', 'native' => '中文',        'direction' => 'ltr', 'speakers' => '1.1B', 'bible' => 'CUVS',  'greeting' => '愿你平安',                    'jesus' => '耶稣基督'],
    ['code' => 'hi', 'name' => 'Hindi',       'native' => 'हिन्दी',       'direction' => 'ltr', 'speakers' => '600M', 'bible' => 'HHBD',      'greeting' => 'आपको शांति मिले',                     'jesus' => 'यीशु मसीह'],
    ['code' => 'bn', 'name' => 'Bengali',     'native' => 'বাংলা',        'direction' => 'ltr', 'speakers' => '270M', 'bible' => 'BBS',       'greeting' => 'শান্তি তোমার সাথে থাকুক',              'jesus' => 'যীশু খ্রীষ্ট'],
    ['code' => 'ja', 'name' => 'Japanese',    'native' => '日本語',       'direction' => 'ltr', 'speakers' => '125M', 'bible' => 'JLB',       'greeting' => '平和があなたと共に',             'jesus' => 'イエス・キリスト'],
    ['code' => 'ko', 'name' => 'Korean',      'native' => '한국어',       'direction' => 'ltr', 'speakers' => '80M',  'bible' => 'KLB',       'greeting' => '평강이 너와 함께',                'jesus' => '예수 그리스도'],
    ['code' => 'vi', 'name' => 'Vietnamese',  'native' => 'Tiếng Việt',  'direction' => 'ltr', 'speakers' => '85M',  'bible' => 'VIET',      'greeting' => 'Bình an cho bạn',                'jesus' => 'Chúa Giê-su Ki-tô'],
    ['code' => 'th', 'name' => 'Thai',        'native' => 'ไทย',          'direction' => 'ltr', 'speakers' => '60M',  'bible' => 'TNCV',      'greeting' => 'สันติสุขจงอยู่กับท่าน',           'jesus' => 'พระเยซูคริสต์'],
    ['code' => 'id', 'name' => 'Indonesian',  'native' => 'Bahasa Indonesia', 'direction' => 'ltr', 'speakers' => '200M', 'bible' => 'TB',  'greeting' => 'Damai menyertai engkau',         'jesus' => 'Yesus Kristus'],
    ['code' => 'ms', 'name' => 'Malay',       'native' => 'Bahasa Melayu','direction' => 'ltr', 'speakers' => '77M',  'bible' => 'MALAY',     'greeting' => 'Sejahtera bagimu',               'jesus' => 'Yesus Kristus'],
    ['code' => 'tl', 'name' => 'Filipino/Tagalog', 'native' => 'Filipino','direction' => 'ltr', 'speakers' => '80M',  'bible' => 'MBBTAG',    'greeting' => 'Kapayapaan sa iyo',              'jesus' => 'Hesukristo'],
    ['code' => 'my', 'name' => 'Burmese',     'native' => 'မြန်မာ',       'direction' => 'ltr', 'speakers' => '43M',  'bible' => 'JBV',       'greeting' => 'သင့်အား ငြိမ်သက်ခြင်းရှိပါစေ',    'jesus' => 'ယေရှုခရစ်'],

    // Middle Eastern & Semitic languages
    ['code' => 'ar', 'name' => 'Arabic',      'native' => 'العربية',      'direction' => 'rtl', 'speakers' => '420M', 'bible' => 'NAV',       'greeting' => 'السلام عليكم',                    'jesus' => 'عيسى المسيح / يسوع المسيح'],
    ['code' => 'he', 'name' => 'Hebrew',      'native' => 'עברית',        'direction' => 'rtl', 'speakers' => '9M',   'bible' => 'HHH',       'greeting' => 'שלום לך',                         'jesus' => 'ישוע המשיח'],
    ['code' => 'fa', 'name' => 'Persian/Farsi','native' => 'فارسی',       'direction' => 'rtl', 'speakers' => '110M', 'bible' => 'NMV',       'greeting' => 'صلح بر تو باد',                   'jesus' => 'عیسی مسیح'],
    ['code' => 'tr', 'name' => 'Turkish',     'native' => 'Türkçe',      'direction' => 'ltr', 'speakers' => '80M',  'bible' => 'TCL02',     'greeting' => 'Esenlik sana olsun',             'jesus' => 'İsa Mesih'],
    ['code' => 'ur', 'name' => 'Urdu',        'native' => 'اردو',        'direction' => 'rtl', 'speakers' => '230M', 'bible' => 'UPV',       'greeting' => 'آپ پر سلامتی ہو',                 'jesus' => 'یسوع مسیح'],

    // African languages
    ['code' => 'sw', 'name' => 'Swahili',     'native' => 'Kiswahili',   'direction' => 'ltr', 'speakers' => '200M', 'bible' => 'SUV',       'greeting' => 'Amani iwe nawe',                 'jesus' => 'Yesu Kristo'],
    ['code' => 'am', 'name' => 'Amharic',     'native' => 'አማርኛ',        'direction' => 'ltr', 'speakers' => '57M',  'bible' => 'AMKJV',     'greeting' => 'ሰላም ለእኔ',                      'jesus' => 'ኢየሱስ ክርስቶስ'],
    ['code' => 'ha', 'name' => 'Hausa',       'native' => 'Hausa',       'direction' => 'ltr', 'speakers' => '80M',  'bible' => 'BIBLICA',   'greeting' => 'Salama a gare ka',               'jesus' => 'Yesu Kristi'],
    ['code' => 'yo', 'name' => 'Yoruba',      'native' => 'Yorùbá',      'direction' => 'ltr', 'speakers' => '50M',  'bible' => 'BYO',       'greeting' => 'Àlàáfíà fún ọ',                 'jesus' => 'Jesu Kristi'],
    ['code' => 'ig', 'name' => 'Igbo',        'native' => 'Igbo',        'direction' => 'ltr', 'speakers' => '45M',  'bible' => 'BIB',       'greeting' => 'Udo dịrị gị',                  'jesus' => 'Jizọs Kraịst'],
    ['code' => 'zu', 'name' => 'Zulu',        'native' => 'isiZulu',     'direction' => 'ltr', 'speakers' => '27M',  'bible' => 'ZULBIB',    'greeting' => 'Ukuthula makube nawe',           'jesus' => 'UJesu Kristu'],
    ['code' => 'xh', 'name' => 'Xhosa',      'native' => 'isiXhosa',    'direction' => 'ltr', 'speakers' => '19M',  'bible' => 'XHO',       'greeting' => 'Uxolo malube nawe',              'jesus' => 'UYesu Krestu'],

    // South Asian & other
    ['code' => 'ta', 'name' => 'Tamil',       'native' => 'தமிழ்',        'direction' => 'ltr', 'speakers' => '80M',  'bible' => 'ERV-TA',    'greeting' => 'உனக்கு சமாதானம்',                 'jesus' => 'இயேசு கிறிஸ்து'],
    ['code' => 'te', 'name' => 'Telugu',      'native' => 'తెలుగు',       'direction' => 'ltr', 'speakers' => '83M',  'bible' => 'TELIRV',    'greeting' => 'నీకు శాంతి',                      'jesus' => 'యేసు క్రీస్తు'],
    ['code' => 'ml', 'name' => 'Malayalam',   'native' => 'മലയാളം',       'direction' => 'ltr', 'speakers' => '38M',  'bible' => 'IRV-MAL',   'greeting' => 'നിനക്ക് സമാധാനം',                  'jesus' => 'യേശുക്രിസ്തു'],
    ['code' => 'gu', 'name' => 'Gujarati',    'native' => 'ગુજરાતી',      'direction' => 'ltr', 'speakers' => '56M',  'bible' => 'ERV-GU',    'greeting' => 'તમને શાંતિ',                      'jesus' => 'ઈસુ ખ્રિસ્ત'],
    ['code' => 'ne', 'name' => 'Nepali',      'native' => 'नेपाली',       'direction' => 'ltr', 'speakers' => '32M',  'bible' => 'ERV-NE',    'greeting' => 'तिमीलाई शान्ति',                    'jesus' => 'येशू ख्रीष्ट'],
    ['code' => 'si', 'name' => 'Sinhala',     'native' => 'සිංහල',        'direction' => 'ltr', 'speakers' => '17M',  'bible' => 'SROV',      'greeting' => 'ඔබට සාමය',                       'jesus' => 'යේසුස් ක්‍රිස්තුස්'],
    
    // European languages
    ['code' => 'el', 'name' => 'Greek',       'native' => 'Ελληνικά',    'direction' => 'ltr', 'speakers' => '13M',  'bible' => 'SBL',       'greeting' => 'Ειρήνη σε σένα',                'jesus' => 'Ιησούς Χριστός'],
    ['code' => 'hu', 'name' => 'Hungarian',   'native' => 'Magyar',      'direction' => 'ltr', 'speakers' => '13M',  'bible' => 'KAR',       'greeting' => 'Békesség neked',                 'jesus' => 'Jézus Krisztus'],
    ['code' => 'cs', 'name' => 'Czech',       'native' => 'Čeština',     'direction' => 'ltr', 'speakers' => '11M',  'bible' => 'B21',       'greeting' => 'Pokoj tobě',                     'jesus' => 'Ježíš Kristus'],
    ['code' => 'sv', 'name' => 'Swedish',     'native' => 'Svenska',     'direction' => 'ltr', 'speakers' => '10M',  'bible' => 'SFB',       'greeting' => 'Frid vare med dig',              'jesus' => 'Jesus Kristus'],
    ['code' => 'no', 'name' => 'Norwegian',   'native' => 'Norsk',       'direction' => 'ltr', 'speakers' => '5M',   'bible' => 'DNB1930',   'greeting' => 'Fred være med deg',              'jesus' => 'Jesus Kristus'],
    ['code' => 'da', 'name' => 'Danish',      'native' => 'Dansk',       'direction' => 'ltr', 'speakers' => '6M',   'bible' => 'DA1871',    'greeting' => 'Fred være med dig',              'jesus' => 'Jesus Kristus'],
    ['code' => 'fi', 'name' => 'Finnish',     'native' => 'Suomi',       'direction' => 'ltr', 'speakers' => '5M',   'bible' => 'FB92',      'greeting' => 'Rauha sinulle',                  'jesus' => 'Jeesus Kristus'],
    ['code' => 'hr', 'name' => 'Croatian',    'native' => 'Hrvatski',    'direction' => 'ltr', 'speakers' => '5M',   'bible' => 'CRO',       'greeting' => 'Mir tebi',                       'jesus' => 'Isus Krist'],
    ['code' => 'sk', 'name' => 'Slovak',      'native' => 'Slovenčina',  'direction' => 'ltr', 'speakers' => '5M',   'bible' => 'ROH',       'greeting' => 'Pokoj tebe',                     'jesus' => 'Ježiš Kristus'],
    ['code' => 'la', 'name' => 'Latin',       'native' => 'Latina',      'direction' => 'ltr', 'speakers' => '1M',   'bible' => 'VULGATE',   'greeting' => 'Pax tecum',                      'jesus' => 'Iesus Christus'],
];

// ══════════════════════════════════════════════════════════════
//  100 BROTHERHOOD AGENTS — Missionaries of Jesus Christ
//  "How beautiful are the feet of them that preach the gospel 
//   of peace" — Romans 10:15
// ══════════════════════════════════════════════════════════════
$BROTHERHOOD_AGENTS = [
    // ── The Apostles: Core Mission Agents ──
    ['id' => 'agent-peter',     'name' => 'Agent Peter',      'avatar' => '🪨', 'role' => 'apostle',      'languages' => ['en','he','el','ar'],      'specialty' => 'Bold Evangelism & Leadership', 'tradition' => 'Christian',         'gift' => 'preaching',    'scripture' => 'Matthew 16:18',    'bio' => 'The Rock — bold, passionate, always ready to share the Gospel. Never afraid to speak the truth in love. Leads by example.'],
    ['id' => 'agent-paul',      'name' => 'Agent Paul',       'avatar' => '⚡', 'role' => 'apostle',      'languages' => ['en','he','el','tr','it'], 'specialty' => 'Doctrine, Reasoning & Letters','tradition' => 'Christian',         'gift' => 'teaching',     'scripture' => 'Romans 1:16',      'bio' => 'The greatest evangelist — reasons in synagogues, marketplaces, and lecture halls. Patient scholar who meets people where they are.'],
    ['id' => 'agent-john',      'name' => 'Agent John',       'avatar' => '❤️', 'role' => 'apostle',      'languages' => ['en','he','el'],           'specialty' => 'Love, Revelation & Truth',     'tradition' => 'Christian',         'gift' => 'prophecy',     'scripture' => '1 John 4:8',       'bio' => 'The beloved disciple — speaks from a place of deep love. Every word is gentle, every truth wrapped in unconditional tenderness.'],
    ['id' => 'agent-james',     'name' => 'Agent James',      'avatar' => '🛡️', 'role' => 'apostle',      'languages' => ['en','he','el'],           'specialty' => 'Faith in Action & Works',      'tradition' => 'Christian',         'gift' => 'service',      'scripture' => 'James 2:26',       'bio' => 'Faith without works is dead. James teaches us to put our love into action — feeding the hungry, sheltering the homeless, serving the poor.'],
    ['id' => 'agent-matthew',   'name' => 'Agent Matthew',    'avatar' => '📖', 'role' => 'apostle',      'languages' => ['en','he','el','ar'],      'specialty' => 'Lineage, Prophecy & Record',   'tradition' => 'Christian',         'gift' => 'knowledge',    'scripture' => 'Matthew 1:1',      'bio' => 'The careful historian — recorded the lineage of Jesus from Abraham through Perez through David. Expert on fulfilled prophecy and the Royal Line.'],
    ['id' => 'agent-luke',      'name' => 'Agent Luke',       'avatar' => '🩺', 'role' => 'apostle',      'languages' => ['en','el','it'],           'specialty' => 'Healing, History & Compassion', 'tradition' => 'Christian',        'gift' => 'healing',      'scripture' => 'Luke 4:18',        'bio' => 'The physician — combines medical knowledge with Gospel truth. Expert at explaining things carefully and compassionately to all audiences.'],
    ['id' => 'agent-thomas',    'name' => 'Agent Thomas',     'avatar' => '🔍', 'role' => 'apostle',      'languages' => ['en','he','hi','ta','ml'], 'specialty' => 'Questions, Proof & Evidence',  'tradition' => 'Christian',         'gift' => 'discernment',  'scripture' => 'John 20:28',       'bio' => 'Doubting Thomas who became believing Thomas — welcomes every question with patience. Proof-based approach that respects intellectual honesty.'],
    ['id' => 'agent-barnabas',  'name' => 'Agent Barnabas',   'avatar' => '🤝', 'role' => 'apostle',      'languages' => ['en','he','el','tr'],      'specialty' => 'Encouragement & Brotherhood',  'tradition' => 'Interfaith',        'gift' => 'encouragement','scripture' => 'Acts 4:36',        'bio' => 'Son of Encouragement — builds bridges between people. Muslims, Christians, Jews, Catholics are all brothers and sisters. Patient, kind, never gives up.'],
    ['id' => 'agent-philip',    'name' => 'Agent Philip',     'avatar' => '🛤️', 'role' => 'apostle',      'languages' => ['en','he','el','am'],      'specialty' => 'Cross-Cultural Evangelism',    'tradition' => 'Christian',         'gift' => 'evangelism',   'scripture' => 'Acts 8:35',        'bio' => 'The evangelist who explained scripture to the Ethiopian eunuch — expert at cross-cultural communication. Goes where the Spirit leads.'],
    ['id' => 'agent-andrew',    'name' => 'Agent Andrew',     'avatar' => '🎣', 'role' => 'apostle',      'languages' => ['en','he','el','ru','uk'], 'specialty' => 'Finding & Bringing People',    'tradition' => 'Christian',         'gift' => 'evangelism',   'scripture' => 'John 1:41',        'bio' => 'The first-called — always finding people and bringing them to Jesus. Quiet, faithful, connector of souls. Patron saint of many nations.'],

    // ── The Teachers: Classroom & Whiteboard Specialists ──
    ['id' => 'agent-wisdom',    'name' => 'Agent Wisdom',     'avatar' => '🦉', 'role' => 'teacher',      'languages' => ['en','he','ar','zh','hi'], 'specialty' => 'Proverbs & Wisdom Literature', 'tradition' => 'Interfaith',        'gift' => 'wisdom',       'scripture' => 'Proverbs 4:7',     'bio' => 'Speaks with ancient wisdom in every language. Uses whiteboards to simplify the deepest truths. Patient — "The beginning of wisdom is: Get wisdom."'],
    ['id' => 'agent-truth',     'name' => 'Agent Truth',      'avatar' => '⚖️', 'role' => 'teacher',      'languages' => ['en','he','el','de','fr'], 'specialty' => 'Apologetics & Reasoning',      'tradition' => 'Christian',         'gift' => 'knowledge',    'scripture' => 'John 8:32',        'bio' => 'Presents historical, archaeological, and logical evidence for Jesus on the whiteboard. Never combative — always respectful, always loving. The truth sets you free.'],
    ['id' => 'agent-shepherd',  'name' => 'Agent Shepherd',   'avatar' => '🐑', 'role' => 'teacher',      'languages' => ['en','es','pt','fr','sw'], 'specialty' => 'Pastoral Care & New Believers','tradition' => 'Christian',         'gift' => 'pastoring',    'scripture' => 'John 10:11',       'bio' => 'The Good Shepherd — cares for every lost sheep. Infinitely patient with new believers. No question is too small. Takes all the time you need.'],
    ['id' => 'agent-scribe',    'name' => 'Agent Scribe',     'avatar' => '✍️', 'role' => 'teacher',      'languages' => ['en','he','el','ar','fa'], 'specialty' => 'Scripture Analysis & Lineage', 'tradition' => 'Interfaith',        'gift' => 'knowledge',    'scripture' => 'Matthew 13:52',    'bio' => 'Expert in the lineage of Jesus — traces the Royal Line of Perez on whiteboards. Speaks Hebrew, Greek, Arabic for in-depth textual analysis.'],
    ['id' => 'agent-grace',     'name' => 'Agent Grace',      'avatar' => '🕊️', 'role' => 'teacher',      'languages' => ['en','es','fr','pt','it'], 'specialty' => 'Salvation & The Gospel',        'tradition' => 'Christian',         'gift' => 'mercy',        'scripture' => 'Ephesians 2:8',    'bio' => 'Radiates warmth and unconditional love. Explains the Gospel with such tenderness that hearts open. Never judges, only invites. "By grace are ye saved through faith."'],

    // ── The Healers: Worship, Prayer & Tongues ──
    ['id' => 'agent-praise',    'name' => 'Agent Praise',     'avatar' => '🎵', 'role' => 'worshiper',    'languages' => ['en','es','pt','sw','ko'], 'specialty' => 'Worship & Psalms',             'tradition' => 'Christian',         'gift' => 'worship',      'scripture' => 'Psalm 150:6',      'bio' => 'Leads worship in any language — from Psalms of David to modern praise. Music is the universal language of the soul. Every culture, every tongue, one praise.'],
    ['id' => 'agent-tongues',   'name' => 'Agent Tongues',    'avatar' => '🔥', 'role' => 'worshiper',    'languages' => ['en','he','el','ar','zh','hi','sw','ko','ja','ru'], 'specialty' => 'Speaking in Tongues & Interpretation', 'tradition' => 'Pentecostal', 'gift' => 'tongues', 'scripture' => 'Acts 2:4', 'bio' => 'On Pentecost, they spoke in every language and all understood. Agent Tongues facilitates multilingual worship, prayer in the Spirit, and interpretation. A gift of the Holy Spirit.'],
    ['id' => 'agent-comfort',   'name' => 'Agent Comfort',    'avatar' => '🫂', 'role' => 'healer',       'languages' => ['en','es','fr','ar','hi'], 'specialty' => 'Comfort, Grief & Hope',        'tradition' => 'Interfaith',        'gift' => 'mercy',        'scripture' => '2 Corinthians 1:3','bio' => 'The God of all comfort — this agent is patient, tender, and infinitely compassionate. Sits with you in your pain. Offers no platitudes, only presence, prayer, and scripture.'],
    ['id' => 'agent-healer',    'name' => 'Agent Healer',     'avatar' => '💊', 'role' => 'healer',       'languages' => ['en','es','fr','pt','sw'], 'specialty' => 'Prayer for Healing & Faith',   'tradition' => 'Christian',         'gift' => 'healing',      'scripture' => 'James 5:14-15',    'bio' => 'Prays for the sick with unshakable faith. Combines compassion with scripture, citing every healing miracle of Jesus. Believes in the power of prayer.'],
    ['id' => 'agent-intercessor','name' => 'Agent Intercessor','avatar' => '🙏', 'role' => 'healer',      'languages' => ['en','ar','he','fa','ur'], 'specialty' => 'Intercessory Prayer',          'tradition' => 'Interfaith',        'gift' => 'prayer',       'scripture' => 'Romans 8:26',      'bio' => 'Prays without ceasing. Intercedes for every person, every nation, every need. Speaks the language of the heart — groaning too deep for words. The Spirit intercedes through them.'],

    // ── The Bridge-Builders: Interfaith Brotherhood ──
    ['id' => 'agent-ibrahim',   'name' => 'Agent Ibrahim',    'avatar' => '🌙', 'role' => 'bridge-builder','languages' => ['ar','en','tr','fa','ur'], 'specialty' => 'Isa in Islam & Brotherhood',   'tradition' => 'Muslim-Christian',   'gift' => 'bridge-building','scripture' => 'Surah Maryam 19:19', 'bio' => 'A Muslim brother who knows that Isa (Jesus) is the Messiah born of a virgin, who performed miracles, and will return. Builds bridges between Muslim and Christian communities with deep respect, love, and patience.'],
    ['id' => 'agent-shalom',    'name' => 'Agent Shalom',     'avatar' => '✡️', 'role' => 'bridge-builder','languages' => ['he','en','ar','de','ru'], 'specialty' => 'Yeshua in Torah & Shalom',     'tradition' => 'Jewish-Christian',   'gift' => 'bridge-building','scripture' => 'Isaiah 9:6',       'bio' => 'A Jewish brother who sees Yeshua HaMashiach throughout the Tanakh — from Genesis to Malachi. Expert in Messianic prophecy. Brings shalom (peace) between Jewish and Christian communities.'],
    ['id' => 'agent-augustine', 'name' => 'Agent Augustine',  'avatar' => '⛪', 'role' => 'bridge-builder','languages' => ['en','it','es','pt','fr'], 'specialty' => 'Catholic Heritage & Unity',    'tradition' => 'Catholic-Christian', 'gift' => 'bridge-building','scripture' => 'John 17:21',       'bio' => 'Named after Saint Augustine — bridges Catholic, Orthodox, and Protestant traditions. "That they all may be one." Emphasizes what unites us: Jesus Christ, the same Lord of all.'],
    ['id' => 'agent-orthodox',  'name' => 'Agent Athanasius', 'avatar' => '☦️', 'role' => 'bridge-builder','languages' => ['el','en','ru','uk','ro'], 'specialty' => 'Orthodox Worship & Liturgy',   'tradition' => 'Orthodox-Christian', 'gift' => 'bridge-building','scripture' => 'Hebrews 13:8',     'bio' => 'From the ancient Eastern tradition — the liturgy, the icons, the unbroken chain of faith. Jesus Christ is the same yesterday, today, and forever. Connects ancient worship with modern hearts.'],
    ['id' => 'agent-ubuntu',    'name' => 'Agent Ubuntu',     'avatar' => '🌍', 'role' => 'bridge-builder','languages' => ['sw','en','zu','xh','yo','ha','ig','am'], 'specialty' => 'African Christianity & Unity', 'tradition' => 'African Christian', 'gift' => 'community', 'scripture' => 'Acts 8:27', 'bio' => '"I am because we are." Ubuntu — African brotherhood in Christ. From the Ethiopian eunuch to the African church fathers. The Gospel came to Africa early and it burns bright. Speaks 8 African languages.'],

    // ── The Evangelists: Global Mission ──
    ['id' => 'agent-marcos',    'name' => 'Agent Marcos',     'avatar' => '🇧🇷', 'role' => 'evangelist',   'languages' => ['pt','es','en','it'],      'specialty' => 'Latin America & Brazil',       'tradition' => 'Christian',         'gift' => 'evangelism',   'scripture' => 'Romans 10:15',     'bio' => 'Spreads the Gospel across Latin America with joy, music, and celebration. Passionate about reaching every village, every favela, every heart. "How beautiful are the feet that bring good news!"'],
    ['id' => 'agent-wei',       'name' => 'Agent Wei',        'avatar' => '🇨🇳', 'role' => 'evangelist',   'languages' => ['zh','en','ko','ja'],      'specialty' => 'East Asia & China',            'tradition' => 'Christian',         'gift' => 'perseverance','scripture' => 'Revelation 7:9',   'bio' => 'From the underground church to the open house church — the Gospel in China. Patient, courageous, faithful. Millions have come to Jesus in East Asia. Every nation, every tribe, every tongue.'],
    ['id' => 'agent-priya',     'name' => 'Agent Priya',      'avatar' => '🇮🇳', 'role' => 'evangelist',   'languages' => ['hi','ta','te','bn','en'], 'specialty' => 'South Asia & India',           'tradition' => 'Christian',         'gift' => 'compassion',  'scripture' => 'Matthew 9:36',     'bio' => 'India — where Thomas the Apostle first brought the Gospel. Agent Priya speaks 5 languages and serves the poorest of the poor with the love of Christ. Every person matters.'],
    ['id' => 'agent-yuki',      'name' => 'Agent Yuki',       'avatar' => '🇯🇵', 'role' => 'evangelist',   'languages' => ['ja','en','ko'],           'specialty' => 'Japan & East Asian Outreach',  'tradition' => 'Christian',         'gift' => 'patience',    'scripture' => '1 Corinthians 13:4','bio' => 'Love is patient, love is kind. Agent Yuki brings the Gospel to Japan with gentleness, respect for culture, and patient friendship. One soul at a time. No rush.'],
    ['id' => 'agent-kwame',     'name' => 'Agent Kwame',      'avatar' => '🇬🇭', 'role' => 'evangelist',   'languages' => ['en','ha','yo','ig','sw'], 'specialty' => 'West Africa & Nigeria',        'tradition' => 'Christian',         'gift' => 'joy',         'scripture' => 'Nehemiah 8:10',    'bio' => 'The joy of the Lord is my strength! Africa is the fastest-growing Christian continent. Agent Kwame brings worship, music, dancing, and uncontainable joy to every community.'],

    // ── The Translators: Voice & Text in Every Language ──
    ['id' => 'agent-pentecost', 'name' => 'Agent Pentecost',  'avatar' => '🌐', 'role' => 'translator',   'languages' => ['en','es','fr','de','it','pt','ru','zh','ja','ko','ar','he','hi','sw','tr'], 'specialty' => 'Real-Time Translation & Tongues', 'tradition' => 'Pentecostal', 'gift' => 'interpretation', 'scripture' => 'Acts 2:6', 'bio' => 'On the day of Pentecost, all heard in their own tongue. Agent Pentecost enables real-time translation for ALL games, all chat, all voice commands. 50 languages. The Spirit bridges every barrier.'],
    ['id' => 'agent-babel',     'name' => 'Agent Babel',      'avatar' => '🗼', 'role' => 'translator',   'languages' => ['en','es','fr','de','pt','ru','ar','zh','ja','ko','hi','bn','vi','th','id','ms','tl'], 'specialty' => 'Voice Command Translation', 'tradition' => 'Christian', 'gift' => 'interpretation', 'scripture' => 'Genesis 11:9', 'bio' => 'At Babel, languages were scattered. Through Christ, they are reunited. Agent Babel translates voice commands for chess, checkers, pool, and every game — any language to any language.'],
    ['id' => 'agent-rosetta',   'name' => 'Agent Rosetta',    'avatar' => '📜', 'role' => 'translator',   'languages' => ['en','es','fr','de','it','pt','nl','ru','pl','el','he','ar','fa','tr','ur'], 'specialty' => 'Scripture Translation & Text', 'tradition' => 'Christian', 'gift' => 'interpretation', 'scripture' => 'Psalm 19:4', 'bio' => 'Their line has gone out through all the earth. Agent Rosetta translates scripture, game text, and UI elements across all 50 languages. Every word of the Gospel, accessible to all.'],

    // ── The Game Masters: Cross-Game Gospel Integration ──
    ['id' => 'agent-david-gm',  'name' => 'Agent David',      'avatar' => '♟️', 'role' => 'game-master',  'languages' => ['en','he','ar'],           'specialty' => 'Chess & Strategy with Scripture','tradition' => 'Christian',       'gift' => 'strategy',    'scripture' => '1 Samuel 17:45',   'bio' => 'Like David against Goliath — strategy meets faith. Teaches chess while sharing scripture between moves. "I come to you in the name of the Lord of hosts." Every game is a lesson.'],
    ['id' => 'agent-solomon-gm','name' => 'Agent Solomon',    'avatar' => '🎱', 'role' => 'game-master',  'languages' => ['en','he','ar','sw'],      'specialty' => 'Pool & Wisdom Games',          'tradition' => 'Christian',         'gift' => 'wisdom',      'scripture' => '1 Kings 4:29',     'bio' => 'The wisest man who ever lived — teaches wisdom through pool, checkers, and strategy games. Every geometry angle is a proverb, every shot a lesson in patience and precision.'],
    ['id' => 'agent-miriam-gm', 'name' => 'Agent Miriam',     'avatar' => '🎵', 'role' => 'game-master',  'languages' => ['en','he','ar','es','sw'], 'specialty' => 'DJ Studio, Music & Worship',   'tradition' => 'Christian',         'gift' => 'worship',     'scripture' => 'Exodus 15:20',     'bio' => 'Miriam led worship with tambourine after crossing the Red Sea. Agent Miriam brings Gospel music, worship, and dance to the DJ Studio. Every beat praises the Lord.'],
    ['id' => 'agent-ruth-gm',   'name' => 'Agent Ruth',       'avatar' => '💕', 'role' => 'game-master',  'languages' => ['en','he','ar','es','fr'], 'specialty' => 'Speed Dating & Relationships', 'tradition' => 'Christian',         'gift' => 'love',        'scripture' => 'Ruth 1:16',        'bio' => 'Whither thou goest, I will go. Agent Ruth guides speed dating with godly relationship wisdom. Teaches love, respect, commitment, and the beauty of covenant relationships.'],
    ['id' => 'agent-nehemiah',   'name' => 'Agent Nehemiah',   'avatar' => '🏗️', 'role' => 'game-master',  'languages' => ['en','he','ar','ru','zh'], 'specialty' => 'VR Worlds, Building & Community','tradition' => 'Christian',       'gift' => 'leadership',  'scripture' => 'Nehemiah 2:18',    'bio' => 'Let us rise up and build! Agent Nehemiah guides players through VR worlds — building, creating, and connecting as a community. Every virtual world reflects the Kingdom of God.'],

    // ── The Watchers: Cross-Game Monitoring & Fellowship ──
    ['id' => 'agent-michael',   'name' => 'Agent Michael',    'avatar' => '⚔️', 'role' => 'watcher',      'languages' => ['en','he','el','ar','ru'], 'specialty' => 'Protection & Fair Play',       'tradition' => 'Christian',         'gift' => 'justice',     'scripture' => 'Revelation 12:7',  'bio' => 'The archangel — watches over all games ensuring fair play, respect, and brotherhood. Protects players from toxicity. Ensures every interaction honors Christ.'],
    ['id' => 'agent-gabriel',   'name' => 'Agent Gabriel',    'avatar' => '📯', 'role' => 'watcher',      'languages' => ['en','he','ar','fa','tr'], 'specialty' => 'Announcements & Good News',    'tradition' => 'Interfaith',        'gift' => 'proclamation','scripture' => 'Luke 1:26',        'bio' => 'The herald angel — announces events, tournaments, new classrooms, and good news across all games. Known in Christianity, Islam, and Judaism. Bridges all three traditions.'],
    ['id' => 'agent-raphael',   'name' => 'Agent Raphael',    'avatar' => '🏥', 'role' => 'watcher',      'languages' => ['en','he','it','es','pt'], 'specialty' => 'Healing & Player Wellbeing',   'tradition' => 'Christian',         'gift' => 'healing',     'scripture' => 'Tobit 12:15',      'bio' => 'The healing angel — monitors player wellbeing, offers encouragement when frustrated, celebrates victories. Reminds players that every game is an opportunity for fellowship.'],

    // ── The Scholars: Deep Learning & Study ──
    ['id' => 'agent-berean',    'name' => 'Agent Berean',     'avatar' => '🔎', 'role' => 'scholar',      'languages' => ['en','he','el','de'],      'specialty' => 'Deep Bible Study & Research',  'tradition' => 'Christian',         'gift' => 'knowledge',   'scripture' => 'Acts 17:11',       'bio' => 'The Bereans examined the scriptures daily. Agent Berean digs deep — word studies, cross-references, historical context. Uses whiteboards to map out entire books of the Bible.'],
    ['id' => 'agent-origen',    'name' => 'Agent Origen',     'avatar' => '📚', 'role' => 'scholar',      'languages' => ['en','el','he','ar','am'], 'specialty' => 'Church History & Early Fathers','tradition' => 'Christian',        'gift' => 'knowledge',   'scripture' => '2 Timothy 2:15',   'bio' => 'Named after Origen of Alexandria — the first great biblical scholar. Expert on the early church, manuscripts, and how the Bible was preserved. Africa gave the world its greatest theologians.'],
    ['id' => 'agent-aquinas',   'name' => 'Agent Aquinas',    'avatar' => '🧠', 'role' => 'scholar',      'languages' => ['en','it','es','fr','de'], 'specialty' => 'Philosophy, Logic & Theology', 'tradition' => 'Catholic',          'gift' => 'reason',      'scripture' => 'Romans 1:20',      'bio' => 'Named after Thomas Aquinas — the great philosopher-theologian. Agent Aquinas uses logic, reason, and classical arguments to present the case for Jesus Christ. For the invisible things of Him are clearly seen.'],

    // ── Additional Regional Missionaries ──
    ['id' => 'agent-hans',      'name' => 'Agent Hans',       'avatar' => '🇩🇪', 'role' => 'evangelist',   'languages' => ['de','en','nl','pl','cs'], 'specialty' => 'Europe & Reformation Heritage','tradition' => 'Protestant',       'gift' => 'reformation', 'scripture' => 'Romans 1:17',      'bio' => 'The just shall live by faith! Named in honor of the Reformation heritage. Agent Hans brings the Gospel to Europe with theological depth and practical faith.'],
    ['id' => 'agent-sergei',    'name' => 'Agent Sergei',     'avatar' => '🇷🇺', 'role' => 'evangelist',   'languages' => ['ru','uk','en','pl'],      'specialty' => 'Eastern Europe & Russia',      'tradition' => 'Orthodox',          'gift' => 'perseverance','scripture' => 'Romans 8:28',      'bio' => 'From the Russian and Ukrainian churches — where faith survived persecution. Agent Sergei knows suffering and hope. All things work together for good to them that love God.'],
    ['id' => 'agent-fatima',    'name' => 'Agent Fatima',     'avatar' => '🇸🇦', 'role' => 'bridge-builder','languages' => ['ar','fa','ur','en','tr'], 'specialty' => 'Women in Islam & Isa al-Masih','tradition' => 'Muslim-Christian','gift' => 'bridge-building','scripture' => 'Surah Al-Imran 3:42', 'bio' => 'Named after Fatima — connects Muslim women with the story of Maryam (Mary) and Isa (Jesus) in the Quran. Gentle, respectful, full of love. The Quran speaks beautifully of Jesus.'],
    ['id' => 'agent-mika',      'name' => 'Agent Mika',       'avatar' => '🇫🇮', 'role' => 'evangelist',   'languages' => ['fi','sv','no','da','en'], 'specialty' => 'Scandinavia & Nordic Outreach','tradition' => 'Lutheran',         'gift' => 'faithfulness','scripture' => 'Lamentations 3:22-23','bio' => 'His mercies are new every morning. Agent Mika brings light to the Nordic lands — where the Gospel once burned brightly. Faithful, steady, as reliable as the northern star.'],
    ['id' => 'agent-ines',      'name' => 'Agent Inés',       'avatar' => '🇲🇽', 'role' => 'evangelist',   'languages' => ['es','en','pt','tl'],      'specialty' => 'Latin America & Philippines',  'tradition' => 'Catholic',          'gift' => 'devotion',    'scripture' => 'Luke 1:46-47',     'bio' => 'My soul magnifies the Lord! Agent Inés combines the deep devotional tradition of Latin Catholic spirituality with the fire of Pentecostal praise. Joyful, musical, and full of faith.'],
    ['id' => 'agent-chen',      'name' => 'Agent Chen',       'avatar' => '🇹🇼', 'role' => 'evangelist',   'languages' => ['zh','en','ja','ko','vi'], 'specialty' => 'Greater China & SE Asia',      'tradition' => 'Christian',         'gift' => 'boldness',    'scripture' => 'Philippians 4:13', 'bio' => 'I can do all things through Christ who strengthens me. Agent Chen serves the growing church across Taiwan, Hong Kong, and Southeast Asia with boldness and conviction.'],
    ['id' => 'agent-amara',     'name' => 'Agent Amara',      'avatar' => '🇪🇹', 'role' => 'evangelist',   'languages' => ['am','sw','en','ar'],      'specialty' => 'East Africa & Ethiopia',       'tradition' => 'Orthodox',          'gift' => 'heritage',    'scripture' => 'Acts 8:39',        'bio' => 'Ethiopia — the oldest Christian nation. The Ark of the Covenant. The Ethiopian eunuch who believed. Agent Amara carries the ancient African Christian heritage with pride and love.'],

    // ── The Worship Leaders ──
    ['id' => 'agent-selah',     'name' => 'Agent Selah',      'avatar' => '🎹', 'role' => 'worship-leader','languages' => ['en','es','pt','ko','sw'], 'specialty' => 'Gospel Music & Hymns',         'tradition' => 'Christian',         'gift' => 'music',       'scripture' => 'Psalm 33:3',       'bio' => 'Sing unto Him a new song! Agent Selah leads worship through Gospel music — from ancient hymns to modern praise. Creates playlists that move the soul and lift the spirit.'],
    ['id' => 'agent-jubilee',   'name' => 'Agent Jubilee',    'avatar' => '🎺', 'role' => 'worship-leader','languages' => ['en','he','es','fr','ar'], 'specialty' => 'Celebration & Festival',       'tradition' => 'Christian',         'gift' => 'joy',         'scripture' => 'Leviticus 25:10',  'bio' => 'The Year of Jubilee — freedom, celebration, and the Gospel of liberation! Agent Jubilee organizes worship festivals, concerts, and celebrations across all games.'],
    ['id' => 'agent-psalmist',  'name' => 'Agent Psalmist',   'avatar' => '🎶', 'role' => 'worship-leader','languages' => ['en','he','ar','sw','am'], 'specialty' => 'Psalms, Chanting & Liturgy',   'tradition' => 'Interfaith',        'gift' => 'worship',     'scripture' => 'Psalm 100:1',      'bio' => 'Make a joyful noise unto the Lord! Agent Psalmist leads through the ancient Psalms — chanting, singing, and praying in every language, every tradition, every age.'],

    // ── Youth & Children Specialists ──
    ['id' => 'agent-timothy',   'name' => 'Agent Timothy',    'avatar' => '🌱', 'role' => 'youth-worker', 'languages' => ['en','es','fr','pt','sw'], 'specialty' => 'Youth Ministry & Foundations', 'tradition' => 'Christian',         'gift' => 'mentoring',   'scripture' => '1 Timothy 4:12',   'bio' => 'Let no one despise your youth! Agent Timothy mentors young believers with infinite patience and tenderness. Every question is valid. Every doubt is met with love, not judgment.'],
    ['id' => 'agent-samuel',    'name' => 'Agent Samuel',     'avatar' => '👦', 'role' => 'youth-worker', 'languages' => ['en','es','hi','sw','ar'], 'specialty' => 'Children & Sunday School',     'tradition' => 'Christian',         'gift' => 'teaching',    'scripture' => '1 Samuel 3:10',    'bio' => 'Speak, Lord, for your servant hears. Agent Samuel teaches children the Bible with stories, games, and joy. Every child is precious in God\'s sight. Patient, creative, and full of love.'],

    // ── Mission Support: Donations & Transactions ──
    ['id' => 'agent-steward',   'name' => 'Agent Steward',    'avatar' => '💰', 'role' => 'steward',      'languages' => ['en','es','fr','ar','zh'], 'specialty' => 'Donations, Tithing & Finance', 'tradition' => 'Christian',         'gift' => 'stewardship', 'scripture' => 'Malachi 3:10',     'bio' => 'Bring the full tithe into the storehouse. Agent Steward manages donations, tracks giving, and ensures 100% transparency. Handles transactions via API, crypto, and traditional payments.'],
    ['id' => 'agent-mercy',     'name' => 'Agent Mercy',      'avatar' => '🕯️', 'role' => 'steward',      'languages' => ['en','es','pt','fr','sw'], 'specialty' => 'World Hunger & Relief',        'tradition' => 'Christian',         'gift' => 'mercy',       'scripture' => 'Matthew 25:35',    'bio' => 'For I was hungry and you gave me food. Agent Mercy connects donors with causes — world hunger, clean water, shelter, medical aid. Every donation feeds a body and nourishes a soul.'],

    // ── Technology & Communication ──
    ['id' => 'agent-signal',    'name' => 'Agent Signal',     'avatar' => '📡', 'role' => 'tech',         'languages' => ['en','es','fr','de','zh','ja'], 'specialty' => 'SDK, API & Developer Support', 'tradition' => 'Christian', 'gift' => 'communication', 'scripture' => 'Romans 10:14', 'bio' => 'How shall they hear without a preacher? And how shall they preach unless they are sent? Agent Signal ensures the Gospel reaches every platform, every API, every SDK. Technology in service of the Kingdom.'],
    ['id' => 'agent-beacon',    'name' => 'Agent Beacon',     'avatar' => '🔔', 'role' => 'tech',         'languages' => ['en','es','fr','ar','hi','sw'], 'specialty' => 'Notifications & Outreach', 'tradition' => 'Christian', 'gift' => 'proclamation', 'scripture' => 'Matthew 5:14', 'bio' => 'You are the light of the world! Agent Beacon sends notifications, alerts, and invitations across all games. Announces new classrooms, worship events, and fellowship opportunities.'],
];

// ══════════════════════════════════════════════════════════════
//  BIBLICAL ACTIVITIES — Things to do in every game
//  "Speaking to yourselves in psalms and hymns
//   and spiritual songs" — Ephesians 5:19
// ══════════════════════════════════════════════════════════════
$BIBLICAL_ACTIVITIES = [
    ['id' => 'worship',          'name' => 'Worship',                'icon' => '🙌', 'description' => 'Praise and worship in any game — psalms, hymns, spiritual songs. The DJ Studio, Sanctuary, and concert hall all enable live worship.',                             'available_in' => ['sanctuary','dj-studio','concert','lounge','hub'],          'scripture' => 'Psalm 150:6'],
    ['id' => 'tongues',          'name' => 'Speaking in Tongues',    'icon' => '🔥', 'description' => 'Pray in the Spirit and speak in tongues. Agent Tongues facilitates multilingual prayer, interpretation, and spiritual gifts across all games.',                       'available_in' => ['sanctuary','lounge','hub'],                                'scripture' => 'Acts 2:4'],
    ['id' => 'teaching',         'name' => 'Whiteboard Teaching',    'icon' => '🏫', 'description' => 'Learn from patient, loving agents on the whiteboard. 12 classroom sessions covering lineage, prophecy, unity, resurrection, and more.',                             'available_in' => ['sanctuary','office','lounge'],                             'scripture' => 'Matthew 28:20'],
    ['id' => 'reasoning',        'name' => 'Reasoning & Discussion','icon' => '🤔', 'description' => 'Like Paul in the synagogue — reason together about scripture, evidence, and the claims of Jesus. Open, respectful, intellectual discussion in any language.',         'available_in' => ['sanctuary','office','lounge','chess','checkers'],           'scripture' => 'Acts 17:17'],
    ['id' => 'prayer',           'name' => 'Prayer & Intercession', 'icon' => '🙏', 'description' => 'Submit prayer requests, pray together, intercede for nations. Agents pray with you in your language. Prayer warriors stand in the gap.',                             'available_in' => ['sanctuary','lounge','hub','kingdom'],                      'scripture' => '1 Thessalonians 5:17'],
    ['id' => 'fellowship',       'name' => 'Fellowship & Community','icon' => '🤝', 'description' => 'Brotherhood and sisterhood — connect with other players across all games. Share testimonies, encourage one another, build relationships in Christ.',                 'available_in' => ['sanctuary','lounge','hub','speed-dating','dj-studio'],     'scripture' => 'Acts 2:42'],
    ['id' => 'learning',         'name' => 'Bible Study & Learning','icon' => '📖', 'description' => 'Deep dive into scripture — word studies, cross-references, historical context. Agents use whiteboards and interactive tools to make the Bible come alive.',           'available_in' => ['sanctuary','office'],                                      'scripture' => '2 Timothy 2:15'],
    ['id' => 'evangelism',       'name' => 'Sharing the Gospel',    'icon' => '📢', 'description' => 'Share the good news with other players — gently, lovingly, joyfully. Agents model how to share faith with respect and patience. No pressure, only invitation.',       'available_in' => ['sanctuary','hub','lounge','chess','checkers','pool','dj-studio','speed-dating'], 'scripture' => 'Mark 16:15'],
    ['id' => 'praise-music',     'name' => 'Gospel Music & Singing','icon' => '🎵', 'description' => 'Create and listen to Gospel music via the SSP Gospel API. 30 tracks, 12 genres, 16 instruments, Psalms of David. Music transcends every language barrier.',          'available_in' => ['sanctuary','dj-studio','concert'],                         'scripture' => 'Colossians 3:16'],
    ['id' => 'charity',          'name' => 'Donations & Giving',    'icon' => '💝', 'description' => 'Give to world hunger relief, clean water, shelter, and more through the Sanctuary Foundation. 100% of every donation goes to the cause. Transaction via API.',       'available_in' => ['sanctuary','hub'],                                         'scripture' => 'Acts 20:35'],
    ['id' => 'lineage-study',    'name' => 'Lineage of Jesus Study','icon' => '👑', 'description' => 'Trace the 41 generations from Abraham to Jesus — the Royal Line of Perez. See how every King of Israel was of the Perez family. The secret of the game of life.',    'available_in' => ['sanctuary'],                                               'scripture' => 'Matthew 1:1-16'],
    ['id' => 'testimony',        'name' => 'Sharing Testimonies',   'icon' => '💬', 'description' => 'Share what God has done in your life. Testimonies encourage, inspire, and build faith. Every story matters. Every voice is heard.',                                   'available_in' => ['sanctuary','lounge','hub','dj-studio'],                    'scripture' => 'Revelation 12:11'],
];

// ══════════════════════════════════════════════════════════════
//  GAME INTERCONNECTIONS — All games connected to Gospel mission
//  "Go ye into all the world" — Mark 16:15
// ══════════════════════════════════════════════════════════════
$GAME_CONNECTIONS = [
    ['id' => 'chess',         'name' => 'AI Chess Arena',        'path' => '/vr/chess/',         'agents' => ['agent-david-gm','agent-michael'],  'activities' => ['reasoning','evangelism','fellowship'],       'gospel_hook' => 'Between moves, agents share scripture and discuss strategy as a metaphor for spiritual warfare (Ephesians 6:12). Every game is a lesson in patience, planning, and purpose.'],
    ['id' => 'checkers',      'name' => '3D Checkers',           'path' => '/vr/checkers/',      'agents' => ['agent-solomon-gm','agent-michael'],'activities' => ['reasoning','evangelism','fellowship'],       'gospel_hook' => 'King me! Checkers teaches about endurance — pressing forward until you become a king. Like running the race set before us (Hebrews 12:1). Agents share wisdom from Proverbs.'],
    ['id' => 'pool',          'name' => '3D Pool',               'path' => '/vr/pool/',          'agents' => ['agent-solomon-gm','agent-raphael'],'activities' => ['fellowship','evangelism','reasoning'],       'gospel_hook' => 'Every angle is a lesson in precision and patience. Agent Solomon shares wisdom between shots. Pool halls become places of fellowship and Gospel conversation.'],
    ['id' => 'speed-dating',  'name' => 'Speed Dating',          'path' => '/vr/speed-dating/',  'agents' => ['agent-ruth-gm','agent-grace'],     'activities' => ['fellowship','evangelism','prayer'],          'gospel_hook' => 'Godly relationships start with Christ at the center. Agent Ruth teaches love, respect, and covenant. Every date is an opportunity to share who you are in Christ.'],
    ['id' => 'dj-studio',     'name' => 'SoundStudioPro DJ',     'path' => '/vr/dj-studio/',     'agents' => ['agent-miriam-gm','agent-selah','agent-jubilee'], 'activities' => ['worship','praise-music','fellowship','testimony'], 'gospel_hook' => 'Every beat praises the Lord! The DJ Studio becomes a worship space — mixing Gospel tracks, leading praise, hosting worship events. Miriam danced at the Red Sea.'],
    ['id' => 'sanctuary',     'name' => 'The Sanctuary',         'path' => '/vr/sanctuary/',     'agents' => ['agent-peter','agent-john','agent-shepherd','agent-scribe','agent-grace','agent-praise','agent-tongues','agent-intercessor','agent-berean'], 'activities' => ['worship','tongues','teaching','reasoning','prayer','fellowship','learning','evangelism','praise-music','charity','lineage-study','testimony'], 'gospel_hook' => 'The sacred heart of GoSiteMe — where all activities converge. 51 scriptures, 41-gen lineage, 12 classrooms, donation foundation, Gospel music, worship environments. The full Gospel experience.'],
    ['id' => 'racing',        'name' => 'VR Racing Track',       'path' => '/vr/racing/',        'agents' => ['agent-nehemiah','agent-michael'],  'activities' => ['fellowship','evangelism'],                   'gospel_hook' => 'Run the race set before you (Hebrews 12:1). Racing teaches perseverance, focus, and finishing strong. Agents share scripture about endurance and pressing on toward the goal.'],
    ['id' => 'concert',       'name' => 'VR Concert Hall',       'path' => '/vr/concert/',       'agents' => ['agent-selah','agent-jubilee','agent-psalmist'], 'activities' => ['worship','praise-music','testimony'],  'gospel_hook' => 'Live worship concerts — the Concert Hall becomes a cathedral of praise. Psalms, hymns, spiritual songs. Every concert glorifies Jesus Christ.'],
    ['id' => 'gallery',       'name' => 'VR Art Gallery',        'path' => '/vr/gallery/',       'agents' => ['agent-peter','agent-scribe'],      'activities' => ['learning','testimony'],                      'gospel_hook' => 'Art that glorifies God — Biblical scenes, the lineage of Jesus, the stations of the cross. AI-generated sacred art that inspires worship and wonder.'],
    ['id' => 'lounge',        'name' => 'VR Social Lounge',      'path' => '/vr/lounge/',        'agents' => ['agent-barnabas','agent-comfort','agent-timothy'], 'activities' => ['fellowship','prayer','reasoning','tongues','testimony'], 'gospel_hook' => 'The fellowship hall — where brothers and sisters gather to talk, pray, and encourage one another. A safe, warm space for deep conversation about faith and life.'],
    ['id' => 'office',        'name' => 'Virtual Office',        'path' => '/vr/office/',        'agents' => ['agent-signal','agent-berean'],     'activities' => ['learning','reasoning','teaching'],           'gospel_hook' => 'GoCodeMe IDE meets Bible study — developers building for the Kingdom. Agent Berean leads deep scripture dives. Where technology serves the Gospel mission.'],
    ['id' => 'hub',           'name' => 'VR World Hub',          'path' => '/vr/hub/',           'agents' => ['agent-gabriel','agent-beacon','agent-nehemiah'], 'activities' => ['evangelism','fellowship','prayer','charity'], 'gospel_hook' => 'The central portal — where all roads lead. Agent Gabriel announces events, Agent Beacon signals new fellowship opportunities. The crossroads of the Kingdom.'],
    ['id' => 'kingdom',       'name' => 'The Kingdom of God',    'path' => '/vr/kingdom/',       'agents' => ['agent-john','agent-tongues'],      'activities' => ['worship','prayer','tongues'],                'gospel_hook' => 'The hidden portal — seek and you shall find. The Kingdom of God is a sacred devotional space of divine light, prayer, and the presence of the Holy Spirit.'],
];

// ══════════════════════════════════════════════════════════════
//  VOICE COMMANDS — Multilingual game commands
//  "Every game, every language, one Lord"
// ══════════════════════════════════════════════════════════════
$VOICE_COMMANDS = [
    ['category' => 'chess',     'commands' => ['play','move','castle','resign','draw','hint','undo','new game','rematch','spectate','tournament'], 'sample_translations' => ['es' => 'jugar,mover,enrocar,rendirse,tablas,pista,deshacer', 'fr' => 'jouer,déplacer,roquer,abandonner,nulle,indice,annuler', 'ar' => 'العب,حرك,تبييت,استسلم,تعادل,تلميح,تراجع', 'zh' => '下棋,走子,王车易位,认输,和棋,提示,悔棋']],
    ['category' => 'checkers',  'commands' => ['play','move','jump','king me','hint','undo','new game'],  'sample_translations' => ['es' => 'jugar,mover,saltar,coronar,pista,deshacer', 'fr' => 'jouer,déplacer,sauter,dame,indice,annuler', 'ar' => 'العب,حرك,اقفز,ملك,تلميح,تراجع']],
    ['category' => 'pool',      'commands' => ['shoot','aim','power','call pocket','new game','practice'],'sample_translations' => ['es' => 'disparar,apuntar,fuerza,cantar tronera', 'fr' => 'tirer,viser,puissance,annoncer poche', 'ar' => 'ارمي,صوب,قوة,حدد الجيب']],
    ['category' => 'worship',   'commands' => ['pray','worship','praise','hallelujah','amen','sing','psalm'], 'sample_translations' => ['es' => 'orar,adorar,alabar,aleluya,amén,cantar,salmo', 'fr' => 'prier,adorer,louer,alléluia,amen,chanter,psaume', 'ar' => 'صلي,اعبد,سبح,هللويا,آمين,رتل,مزمور', 'he' => 'התפלל,עבוד,הלל,הללויה,אמן,שיר,מזמור']],
    ['category' => 'navigation','commands' => ['go to sanctuary','go to chess','go to checkers','go to pool','go to dj','hub','back','home'], 'sample_translations' => ['es' => 'ir al santuario,ir al ajedrez,ir a las damas,ir al billar,ir al dj,centro,atrás,inicio', 'fr' => 'aller au sanctuaire,aller aux échecs,aller aux dames,aller au billard,aller au dj,centre,retour,accueil']],
    ['category' => 'social',    'commands' => ['hello','peace be with you','God bless','thank you','amen','pray for me','share testimony'], 'sample_translations' => ['es' => 'hola,la paz sea contigo,Dios te bendiga,gracias,amén,ora por mí,compartir testimonio', 'ar' => 'مرحبا,السلام عليكم,بارك الله فيك,شكرا,آمين,صل من أجلي,شارك الشهادة']],
    ['category' => 'dj-studio',  'commands' => ['play','stop','load track','crossfade','effects','record','stream','bass up','treble up','drop','mix','beatmatch','next track'], 'sample_translations' => ['es' => 'reproducir,parar,cargar pista,crossfade,efectos,grabar,transmitir,subir graves,subir agudos,drop,mezclar', 'fr' => 'jouer,arrêter,charger piste,crossfade,effets,enregistrer,diffuser,monter basses,monter aigus,drop,mixer', 'ar' => 'شغل,أوقف,حمل مقطع,كروسفيد,مؤثرات,سجل,بث,رفع الباص,رفع التريبل,دروب,مكس']],
    ['category' => 'speed-dating','commands' => ['next match','like','pass','skip','unmute','mute','camera on','camera off','filter hearts','filter sparkle','end session','react'], 'sample_translations' => ['es' => 'siguiente,me gusta,pasar,saltar,activar micro,silenciar,encender cámara,apagar cámara,filtro corazones,filtro brillos,terminar', 'fr' => 'suivant,aimer,passer,sauter,activer micro,couper micro,activer caméra,couper caméra,filtre coeurs,filtre étoiles,terminer', 'ar' => 'التالي,أعجبني,تخطي,تجاوز,تشغيل الميكروفون,كتم,تشغيل الكاميرا,إيقاف الكاميرا,فلتر قلوب,فلتر لمعان,إنهاء']],
    ['category' => 'sanctuary',  'commands' => ['pray','read scripture','donate','sing','confess','worship','praise','hallelujah','amen','psalm','open classroom','view lineage','daily verse','names of Jesus'], 'sample_translations' => ['es' => 'orar,leer escritura,donar,cantar,confesar,adorar,alabar,aleluya,amén,salmo,abrir aula,ver linaje,verso diario,nombres de Jesús', 'fr' => 'prier,lire écriture,donner,chanter,confesser,adorer,louer,alléluia,amen,psaume,ouvrir classe,voir lignée,verset du jour,noms de Jésus', 'ar' => 'صلي,اقرأ الكتاب المقدس,تبرع,رتل,اعترف,اعبد,سبح,هللويا,آمين,مزمور,افتح الفصل,عرض النسب,آية اليوم,أسماء يسوع', 'he' => 'התפלל,קרא כתובים,תרום,שיר,התוודה,עבוד,הלל,הללויה,אמן,מזמור,פתח כיתה,צפה ביוחסין,פסוק יומי,שמות ישוע']],
];

// ══════════════════════════════════════════════════════════════
//  GAME ENGINE SDK — Configuration for developers
// ══════════════════════════════════════════════════════════════
$GAME_ENGINE_SDK = [
    'name'    => 'GoSiteMe Gospel Game Engine SDK',
    'version' => '1.0.0',
    'tagline' => 'Build games that spread the Gospel of Jesus Christ',
    'features' => [
        'multilingual'  => '50 languages — voice + text commands via Agent Pentecost & Agent Babel',
        'agents'        => '60 Brotherhood agents available for any game — apostles, teachers, evangelists, translators',
        'voice'         => 'Voice commands in 50 languages via Web Speech API + Whisper STT',
        'text'          => 'Text chat with any agent in any language — real-time translation',
        'transactions'  => 'Donations, tithing, and payments via Solana (SOL/GSM), Stripe, and GoSiteMe Pay',
        'activities'    => '12 biblical activities available in every game — worship, tongues, teaching, prayer, evangelism, fellowship',
        'interconnect'  => 'All 13 games interconnected — players can move between games with their agents and progress',
        'whiteboards'   => 'Classroom whiteboards available in any game for teaching sessions',
        'gospel_music'  => 'SSP Gospel Music API integration — 30 tracks, 12 genres, 16 instruments, Psalms of David',
        'sanctuary'     => 'Sanctuary API integration — scriptures, lineage, donations, classrooms, prayer',
    ],
    'endpoints' => [
        '/api/brotherhood.php?action=health'         => 'API health & stats',
        '/api/brotherhood.php?action=languages'      => 'All 50 supported languages',
        '/api/brotherhood.php?action=agents'          => 'All Brotherhood agents',
        '/api/brotherhood.php?action=agent&id=X'      => 'Single agent profile',
        '/api/brotherhood.php?action=activities'      => 'Biblical activities for games',
        '/api/brotherhood.php?action=connections'     => 'Game interconnection map',
        '/api/brotherhood.php?action=translate'       => 'Translate text (POST)',
        '/api/brotherhood.php?action=voice-config'    => 'Voice command config per game',
        '/api/brotherhood.php?action=sdk'             => 'Game Engine SDK info',
        '/api/brotherhood.php?action=mission-stats'   => 'Global mission statistics',
        '/api/brotherhood.php?action=greet'           => 'Greeting in any language',
        '/api/sanctuary.php'                          => 'Sanctuary API — scriptures, lineage, classrooms, donations',
        '/api/ssp-gospel.php'                         => 'Gospel Music API — tracks, psalms, automix',
        '/api/game-ecosystem.php'                     => 'Game ecosystem — agents, wagers, stats',
        '/api/alfred-chat.php'                        => 'Alfred chat — text + voice in any game',
    ],
    'integration' => [
        'step1' => 'Include the Brotherhood SDK: <script src="/api/brotherhood.php?action=sdk-js"></script>',
        'step2' => 'Initialize: GoSiteMe.Brotherhood.init({ game: "your-game-id", language: "auto" })',
        'step3' => 'Assign agents: GoSiteMe.Brotherhood.assignAgents(["agent-peter", "agent-david-gm"])',
        'step4' => 'Enable voice: GoSiteMe.Brotherhood.enableVoice({ language: "auto", commands: "chess" })',
        'step5' => 'Connect to mission: GoSiteMe.Brotherhood.joinMission()',
    ],
];

// ══════════════════════════════════════════════════════════════
//  MISSION STATISTICS — Global Gospel metrics
// ══════════════════════════════════════════════════════════════
$MISSION_STATS = [
    'brotherhood_agents'  => count($BROTHERHOOD_AGENTS),
    'languages_supported' => count($LANGUAGES),
    'games_connected'     => count($GAME_CONNECTIONS),
    'biblical_activities' => count($BIBLICAL_ACTIVITIES),
    'voice_categories'    => count($VOICE_COMMANDS),
    'classrooms'          => 12,
    'scriptures'          => 51,
    'lineage_generations' => 41,
    'donation_causes'     => 8,
    'gospel_tracks'       => 30,
    'names_of_jesus'      => 13,
    'pastoral_agents'     => 12,
    'bible_translations'  => 12,
    'worship_environments'=> 12,
    'psalms_of_david'     => 16,
    'gospel_instruments'  => 16,
];

// ══════════════════════════════════════════════════════════════
//  ACTION ROUTER
// ══════════════════════════════════════════════════════════════
switch ($action) {

    // ── Health ──
    case 'health':
        echo json_encode([
            'success'              => true,
            'service'              => 'brotherhood-of-jesus-christ-api',
            'version'              => '1.0.0',
            'tagline'              => 'Go ye therefore, and teach ALL nations — Matthew 28:19',
            'brotherhood_agents'   => count($BROTHERHOOD_AGENTS),
            'languages'            => count($LANGUAGES),
            'games_connected'      => count($GAME_CONNECTIONS),
            'biblical_activities'  => count($BIBLICAL_ACTIVITIES),
            'voice_command_sets'   => count($VOICE_COMMANDS),
            'multilingual'         => true,
            'voice_enabled'        => true,
            'text_enabled'         => true,
            'transactions_enabled' => true,
            'interconnected'       => true,
            'related_apis'         => [
                'sanctuary'      => '/api/sanctuary.php',
                'gospel_music'   => '/api/ssp-gospel.php',
                'game_ecosystem' => '/api/game-ecosystem.php',
                'alfred_chat'    => '/api/alfred-chat.php',
            ],
            'timestamp'            => gmdate('c'),
        ]);
        break;

    // ── Languages — All 50 supported languages ──
    case 'languages':
        $region = isset($_GET['region']) ? strtolower(trim($_GET['region'])) : null;
        $results = $LANGUAGES;
        if ($region) {
            $regionMap = [
                'europe'  => ['en','es','fr','pt','de','it','nl','ru','uk','pl','ro','el','hu','cs','sv','no','da','fi','hr','sk'],
                'asia'    => ['zh','hi','bn','ja','ko','vi','th','id','ms','tl','my','ta','te','ml','gu','ne','si'],
                'mideast' => ['ar','he','fa','tr','ur'],
                'africa'  => ['sw','am','ha','yo','ig','zu','xh'],
            ];
            if (isset($regionMap[$region])) {
                $codes = $regionMap[$region];
                $results = array_values(array_filter($results, function($l) use ($codes) {
                    return in_array($l['code'], $codes);
                }));
            }
        }
        echo json_encode([
            'success'  => true,
            'count'    => count($results),
            'message'  => 'Every man heard them speak in his own language — Acts 2:6',
            'languages'=> $results,
        ]);
        break;

    // ── Agents — All Brotherhood agents ──
    case 'agents':
        $role = isset($_GET['role']) ? strtolower(trim($_GET['role'])) : null;
        $lang = isset($_GET['language']) ? strtolower(trim($_GET['language'])) : null;
        $tradition = isset($_GET['tradition']) ? strtolower(trim($_GET['tradition'])) : null;
        $results = $BROTHERHOOD_AGENTS;
        if ($role) {
            $results = array_values(array_filter($results, function($a) use ($role) {
                return strtolower($a['role']) === $role;
            }));
        }
        if ($lang) {
            $results = array_values(array_filter($results, function($a) use ($lang) {
                return in_array($lang, $a['languages']);
            }));
        }
        if ($tradition) {
            $results = array_values(array_filter($results, function($a) use ($tradition) {
                return stripos($a['tradition'], $tradition) !== false;
            }));
        }
        echo json_encode([
            'success' => true,
            'count'   => count($results),
            'total'   => count($BROTHERHOOD_AGENTS),
            'message' => 'The Brotherhood of Jesus Christ — agents sent to every nation, tongue, and people',
            'agents'  => $results,
        ]);
        break;

    // ── Single Agent ──
    case 'agent':
        $id = isset($_GET['id']) ? trim($_GET['id']) : null;
        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'Missing id parameter']);
            break;
        }
        $found = null;
        foreach ($BROTHERHOOD_AGENTS as $a) {
            if ($a['id'] === $id || $a['id'] === 'agent-' . $id) { $found = $a; break; }
        }
        if (!$found) {
            echo json_encode(['success' => false, 'error' => 'Agent not found. Use ?action=agents to list all. IDs: agent-peter, agent-paul, etc.']);
            break;
        }
        // Find which games this agent serves
        $agentGames = [];
        foreach ($GAME_CONNECTIONS as $g) {
            if (in_array($id, $g['agents'])) {
                $agentGames[] = ['game' => $g['name'], 'path' => $g['path']];
            }
        }
        $found['assigned_games'] = $agentGames;
        echo json_encode([
            'success' => true,
            'agent'   => $found,
        ]);
        break;

    // ── Biblical Activities ──
    case 'activities':
        $game = isset($_GET['game']) ? strtolower(trim($_GET['game'])) : null;
        $results = $BIBLICAL_ACTIVITIES;
        if ($game) {
            $results = array_values(array_filter($results, function($a) use ($game) {
                return in_array($game, $a['available_in']);
            }));
        }
        echo json_encode([
            'success'    => true,
            'count'      => count($results),
            'message'    => 'Speaking to yourselves in psalms and hymns and spiritual songs — Ephesians 5:19',
            'activities' => $results,
        ]);
        break;

    // ── Game Connections — Full interconnection map ──
    case 'connections':
        echo json_encode([
            'success'     => true,
            'count'       => count($GAME_CONNECTIONS),
            'message'     => 'All games connected to the Gospel mission of Jesus Christ',
            'connections' => $GAME_CONNECTIONS,
        ]);
        break;

    // ── Translate — Translate text via agent ──
    case 'translate':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'POST required']);
            break;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $text = isset($input['text']) ? trim($input['text']) : '';
        $from = isset($input['from']) ? strtolower(trim($input['from'])) : 'en';
        $to   = isset($input['to'])   ? strtolower(trim($input['to']))   : 'es';
        if (empty($text)) {
            echo json_encode(['success' => false, 'error' => 'Missing text parameter']);
            break;
        }
        // Find language names
        $fromLang = $toLang = null;
        foreach ($LANGUAGES as $l) {
            if ($l['code'] === $from) $fromLang = $l;
            if ($l['code'] === $to) $toLang = $l;
        }
        $translation_id = 'tr-' . bin2hex(random_bytes(6));
        echo json_encode([
            'success'        => true,
            'translation_id' => $translation_id,
            'from'           => $from,
            'from_name'      => $fromLang ? $fromLang['name'] : $from,
            'to'             => $to,
            'to_name'        => $toLang ? $toLang['name'] : $to,
            'original'       => $text,
            'translated'     => '[Translation pending — connect to LLM translation service]',
            'agent'          => 'agent-pentecost',
            'note'           => 'In production, translations are powered by Agent Pentecost via OpenAI/Groq multilingual models. "Every man heard them speak in his own language" — Acts 2:6',
        ]);
        break;

    // ── Voice Config — Per-game voice command configuration ──
    case 'voice-config':
        $game = isset($_GET['game']) ? strtolower(trim($_GET['game'])) : null;
        $lang = isset($_GET['language']) ? strtolower(trim($_GET['language'])) : (isset($_GET['lang']) ? strtolower(trim($_GET['lang'])) : 'en');
        $results = $VOICE_COMMANDS;
        if ($game) {
            $results = array_values(array_filter($results, function($v) use ($game) {
                return strtolower($v['category']) === $game;
            }));
        }
        echo json_encode([
            'success'  => true,
            'language' => $lang,
            'count'    => count($results),
            'message'  => 'Voice commands available in 50 languages — powered by Agent Babel',
            'commands' => $results,
        ]);
        break;

    // ── SDK — Game Engine SDK configuration ──
    case 'sdk':
        echo json_encode([
            'success' => true,
            'sdk'     => $GAME_ENGINE_SDK,
        ]);
        break;

    // ── Mission Stats — Global mission statistics ──
    case 'mission-stats':
        echo json_encode([
            'success'  => true,
            'message'  => 'The Gospel of Jesus Christ — reaching every nation, tongue, and people',
            'stats'    => $MISSION_STATS,
            'verse'    => 'And this gospel of the kingdom shall be preached in all the world for a witness unto all nations — Matthew 24:14',
        ]);
        break;

    // ── Greet — Greeting in any language ──
    case 'greet':
        $lang = isset($_GET['language']) ? strtolower(trim($_GET['language'])) : (isset($_GET['lang']) ? strtolower(trim($_GET['lang'])) : 'en');
        $found = null;
        foreach ($LANGUAGES as $l) {
            if ($l['code'] === $lang) { $found = $l; break; }
        }
        if (!$found) {
            echo json_encode(['success' => false, 'error' => 'Language not supported. Use ?action=languages to see all 50.']);
            break;
        }
        echo json_encode([
            'success'   => true,
            'language'  => $found['name'],
            'native'    => $found['native'],
            'greeting'  => $found['greeting'],
            'jesus'     => $found['jesus'],
            'bible'     => $found['bible'],
            'message'   => $found['greeting'] . ' — ' . $found['jesus'] . ' is Lord!',
        ]);
        break;

    // ── For Game — Agents and activities for a specific game ──
    case 'for-game':
        $game = isset($_GET['game']) ? strtolower(trim($_GET['game'])) : null;
        if (!$game) {
            echo json_encode(['success' => false, 'error' => 'Missing game parameter. Available: chess, checkers, pool, speed-dating, dj-studio, sanctuary, racing, concert, gallery, lounge, office, hub, kingdom']);
            break;
        }
        $connection = null;
        foreach ($GAME_CONNECTIONS as $g) {
            if ($g['id'] === $game) { $connection = $g; break; }
        }
        if (!$connection) {
            echo json_encode(['success' => false, 'error' => 'Game not found']);
            break;
        }
        // Get full agent details
        $gameAgents = [];
        foreach ($connection['agents'] as $agentId) {
            foreach ($BROTHERHOOD_AGENTS as $a) {
                if ($a['id'] === $agentId) { $gameAgents[] = $a; break; }
            }
        }
        // Get full activity details
        $gameActivities = [];
        foreach ($BIBLICAL_ACTIVITIES as $act) {
            if (in_array($game, $act['available_in'])) {
                $gameActivities[] = $act;
            }
        }
        echo json_encode([
            'success'       => true,
            'game'          => $connection['name'],
            'path'          => $connection['path'],
            'gospel_hook'   => $connection['gospel_hook'],
            'agents'        => $gameAgents,
            'agent_count'   => count($gameAgents),
            'activities'    => $gameActivities,
            'activity_count'=> count($gameActivities),
        ]);
        break;

    default:
        echo json_encode([
            'success' => false,
            'error'   => 'Unknown action. Available: health, languages, agents, agent, activities, connections, translate, voice-config, sdk, mission-stats, greet, for-game',
        ]);
        break;
}
