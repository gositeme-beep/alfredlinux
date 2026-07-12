<?php
/**
 * GoSiteMe Sanctuary API v4.0 — "Go ye therefore, and teach ALL nations" (Matthew 28:19)
 * A sacred space for reflection on the Word of God
 * Bible Verses (KJV + multi-translation), Pastors, Sermons, Prayer, Daily Devotionals
 * Gospel Music Creation (SoundStudioPro), Names of Jesus across world traditions,
 * Worship Environments, Psalms of David, Gospel Automix
 * Lineage of Jesus — The Royal Line of Perez (Matthew 1)
 * Donation Foundation — World Hunger & Compassion Ministry
 * Classrooms — Whiteboard teaching with patient, loving agents
 * Brotherhood — Muslims, Christians, Jews, Catholics: one family in Christ
 * Multilingual — 50 languages, voice + text, interconnected with all games
 * Brotherhood of Jesus Christ — 60 agents spreading the Gospel to all nations
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
//  BIBLE TRANSLATIONS
// ══════════════════════════════════════════════════════════════
$BIBLE_TRANSLATIONS = [
    'kjv'  => ['name' => 'King James Version',              'abbr' => 'KJV',  'year' => 1611, 'gateway_code' => 'KJV'],
    'akjv' => ['name' => 'Authorized King James Version',   'abbr' => 'AKJV', 'year' => 1611, 'gateway_code' => 'AKJV'],
    'nkjv' => ['name' => 'New King James Version',          'abbr' => 'NKJV', 'year' => 1982, 'gateway_code' => 'NKJV'],
    'niv'  => ['name' => 'New International Version',       'abbr' => 'NIV',  'year' => 1978, 'gateway_code' => 'NIV'],
    'esv'  => ['name' => 'English Standard Version',        'abbr' => 'ESV',  'year' => 2001, 'gateway_code' => 'ESV'],
    'nasb' => ['name' => 'New American Standard Bible',     'abbr' => 'NASB', 'year' => 1971, 'gateway_code' => 'NASB'],
    'nlt'  => ['name' => 'New Living Translation',          'abbr' => 'NLT',  'year' => 1996, 'gateway_code' => 'NLT'],
    'amp'  => ['name' => 'Amplified Bible',                 'abbr' => 'AMP',  'year' => 1965, 'gateway_code' => 'AMP'],
    'msg'  => ['name' => 'The Message',                     'abbr' => 'MSG',  'year' => 2002, 'gateway_code' => 'MSG'],
    'csb'  => ['name' => 'Christian Standard Bible',        'abbr' => 'CSB',  'year' => 2017, 'gateway_code' => 'CSB'],
    'web'  => ['name' => 'World English Bible',             'abbr' => 'WEB',  'year' => 2000, 'gateway_code' => 'WEB'],
    'ylt'  => ['name' => "Young's Literal Translation",     'abbr' => 'YLT',  'year' => 1862, 'gateway_code' => 'YLT'],
];

// ══════════════════════════════════════════════════════════════
//  CURATED SCRIPTURE — KJV (Authorized King James)
// ══════════════════════════════════════════════════════════════
$SCRIPTURE_CATALOG = [
    // ── The Gospel: Salvation & Eternal Life ──
    ['ref' => 'John 3:16',       'text' => 'For God so loved the world, that he gave his only begotten Son, that whosoever believeth in him should not perish, but have everlasting life.', 'category' => 'salvation', 'topic' => 'God\'s Love'],
    ['ref' => 'John 14:6',      'text' => 'Jesus saith unto him, I am the way, the truth, and the life: no man cometh unto the Father, but by me.', 'category' => 'salvation', 'topic' => 'The Way'],
    ['ref' => 'Romans 10:9',    'text' => 'That if thou shalt confess with thy mouth the Lord Jesus, and shalt believe in thine heart that God hath raised him from the dead, thou shalt be saved.', 'category' => 'salvation', 'topic' => 'Confession & Belief'],
    ['ref' => 'Ephesians 2:8-9','text' => 'For by grace are ye saved through faith; and that not of yourselves: it is the gift of God: Not of works, lest any man should boast.', 'category' => 'salvation', 'topic' => 'Grace'],
    ['ref' => 'Romans 6:23',    'text' => 'For the wages of sin is death; but the gift of God is eternal life through Jesus Christ our Lord.', 'category' => 'salvation', 'topic' => 'Eternal Life'],
    ['ref' => 'Acts 4:12',      'text' => 'Neither is there salvation in any other: for there is none other name under heaven given among men, whereby we must be saved.', 'category' => 'salvation', 'topic' => 'No Other Name'],
    ['ref' => 'Romans 5:8',     'text' => 'But God commendeth his love toward us, in that, while we were yet sinners, Christ died for us.', 'category' => 'salvation', 'topic' => 'Christ Died for Us'],
    ['ref' => 'John 1:12',      'text' => 'But as many as received him, to them gave he power to become the sons of God, even to them that believe on his name.', 'category' => 'salvation', 'topic' => 'Sons of God'],

    // ── The Birth, Life & Ministry of Jesus/Yeshua ──
    ['ref' => 'Isaiah 7:14',    'text' => 'Therefore the Lord himself shall give you a sign; Behold, a virgin shall conceive, and bear a son, and shall call his name Immanuel.', 'category' => 'prophecy', 'topic' => 'Virgin Birth Prophecy'],
    ['ref' => 'Matthew 1:23',   'text' => 'Behold, a virgin shall be with child, and shall bring forth a son, and they shall call his name Emmanuel, which being interpreted is, God with us.', 'category' => 'birth', 'topic' => 'God With Us'],
    ['ref' => 'Luke 2:11',      'text' => 'For unto you is born this day in the city of David a Saviour, which is Christ the Lord.', 'category' => 'birth', 'topic' => 'Saviour Born'],
    ['ref' => 'John 1:14',      'text' => 'And the Word was made flesh, and dwelt among us, (and we beheld his glory, the glory as of the only begotten of the Father,) full of grace and truth.', 'category' => 'birth', 'topic' => 'The Word Made Flesh'],
    ['ref' => 'Philippians 2:6-8', 'text' => 'Who, being in the form of God, thought it not robbery to be equal with God: But made himself of no reputation, and took upon him the form of a servant, and was made in the likeness of men: And being found in fashion as a man, he humbled himself, and became obedient unto death, even the death of the cross.', 'category' => 'ministry', 'topic' => 'Humility of Christ'],

    // ── The Covenant & Crucifixion ──
    ['ref' => 'Daniel 9:27',    'text' => 'And he shall confirm the covenant with many for one week: and in the midst of the week he shall cause the sacrifice and the oblation to cease.', 'category' => 'covenant', 'topic' => 'The Covenant Week'],
    ['ref' => 'Hebrews 9:15',   'text' => 'And for this cause he is the mediator of the new testament, that by means of death, for the redemption of the transgressions that were under the first testament, they which are called might receive the promise of eternal inheritance.', 'category' => 'covenant', 'topic' => 'Mediator of the New Covenant'],
    ['ref' => 'Isaiah 53:5',    'text' => 'But he was wounded for our transgressions, he was bruised for our iniquities: the chastisement of our peace was upon him; and with his stripes we are healed.', 'category' => 'crucifixion', 'topic' => 'Wounded for Us'],
    ['ref' => 'Isaiah 53:7',    'text' => 'He was oppressed, and he was afflicted, yet he opened not his mouth: he is brought as a lamb to the slaughter, and as a sheep before her shearers is dumb, so he openeth not his mouth.', 'category' => 'crucifixion', 'topic' => 'Lamb of God'],
    ['ref' => '1 Peter 2:24',   'text' => 'Who his own self bare our sins in his own body on the tree, that we, being dead to sins, should live unto righteousness: by whose stripes ye were healed.', 'category' => 'crucifixion', 'topic' => 'Bore Our Sins'],
    ['ref' => 'Matthew 27:50-51','text' => 'Jesus, when he had cried again with a loud voice, yielded up the ghost. And, behold, the veil of the temple was rent in twain from the top to the bottom; and the earth did quake, and the rocks rent.', 'category' => 'crucifixion', 'topic' => 'The Veil Torn'],

    // ── Resurrection & Triumph Over Death ──
    ['ref' => 'Matthew 28:6',   'text' => 'He is not here: for he is risen, as he said. Come, see the place where the Lord lay.', 'category' => 'resurrection', 'topic' => 'He Is Risen'],
    ['ref' => '1 Corinthians 15:55-57', 'text' => 'O death, where is thy sting? O grave, where is thy victory? The sting of death is sin; and the strength of sin is the law. But thanks be to God, which giveth us the victory through our Lord Jesus Christ.', 'category' => 'resurrection', 'topic' => 'Victory Over Death'],
    ['ref' => 'Romans 8:11',    'text' => 'But if the Spirit of him that raised up Jesus from the dead dwell in you, he that raised up Christ from the dead shall also quicken your mortal bodies by his Spirit that dwelleth in you.', 'category' => 'resurrection', 'topic' => 'Quickened by the Spirit'],
    ['ref' => 'Revelation 1:18','text' => 'I am he that liveth, and was dead; and, behold, I am alive for evermore, Amen; and have the keys of hell and of death.', 'category' => 'resurrection', 'topic' => 'Alive Forevermore'],

    // ── Relationship with the Father ──
    ['ref' => 'John 17:3',      'text' => 'And this is life eternal, that they might know thee the only true God, and Jesus Christ, whom thou hast sent.', 'category' => 'relationship', 'topic' => 'Knowing the Father'],
    ['ref' => 'Romans 8:15',    'text' => 'For ye have not received the spirit of bondage again to fear; but ye have received the Spirit of adoption, whereby we cry, Abba, Father.', 'category' => 'relationship', 'topic' => 'Abba Father'],
    ['ref' => 'Galatians 4:6',  'text' => 'And because ye are sons, God hath sent forth the Spirit of his Son into your hearts, crying, Abba, Father.', 'category' => 'relationship', 'topic' => 'Spirit of His Son'],
    ['ref' => '1 John 3:1',     'text' => 'Behold, what manner of love the Father hath bestowed upon us, that we should be called the sons of God: therefore the world knoweth us not, because it knew him not.', 'category' => 'relationship', 'topic' => 'Called Sons of God'],

    // ── Comfort & Strength in Hard Times ──
    ['ref' => 'Psalm 23:1-4',   'text' => 'The LORD is my shepherd; I shall not want. He maketh me to lie down in green pastures: he leadeth me beside the still waters. He restoreth my soul: he leadeth me in the paths of righteousness for his name\'s sake. Yea, though I walk through the valley of the shadow of death, I will fear no evil: for thou art with me; thy rod and thy staff they comfort me.', 'category' => 'comfort', 'topic' => 'The Lord is My Shepherd'],
    ['ref' => 'Isaiah 41:10',   'text' => 'Fear thou not; for I am with thee: be not dismayed; for I am thy God: I will strengthen thee; yea, I will help thee; yea, I will uphold thee with the right hand of my righteousness.', 'category' => 'comfort', 'topic' => 'Fear Not'],
    ['ref' => 'Philippians 4:13','text' => 'I can do all things through Christ which strengtheneth me.', 'category' => 'comfort', 'topic' => 'Strength in Christ'],
    ['ref' => 'Romans 8:28',    'text' => 'And we know that all things work together for good to them that love God, to them who are the called according to his purpose.', 'category' => 'comfort', 'topic' => 'All Things Work Together'],
    ['ref' => 'Jeremiah 29:11', 'text' => 'For I know the thoughts that I think toward you, saith the LORD, thoughts of peace, and not of evil, to give you an expected end.', 'category' => 'comfort', 'topic' => 'Plans for You'],
    ['ref' => 'Psalm 46:1',     'text' => 'God is our refuge and strength, a very present help in trouble.', 'category' => 'comfort', 'topic' => 'Refuge & Strength'],
    ['ref' => 'Matthew 11:28-30','text' => 'Come unto me, all ye that labour and are heavy laden, and I will give you rest. Take my yoke upon you, and learn of me; for I am meek and lowly in heart: and ye shall find rest unto your souls. For my yoke is easy, and my burden is light.', 'category' => 'comfort', 'topic' => 'Come Unto Me'],
    ['ref' => '2 Corinthians 1:3-4', 'text' => 'Blessed be God, even the Father of our Lord Jesus Christ, the Father of mercies, and the God of all comfort; Who comforteth us in all our tribulation, that we may be able to comfort them which are in any trouble, by the comfort wherewith we ourselves are comforted of God.', 'category' => 'comfort', 'topic' => 'God of All Comfort'],

    // ── Prayer & Faith ──
    ['ref' => 'Hebrews 11:1',   'text' => 'Now faith is the substance of things hoped for, the evidence of things not seen.', 'category' => 'faith', 'topic' => 'Definition of Faith'],
    ['ref' => 'Mark 11:24',     'text' => 'Therefore I say unto you, What things soever ye desire, when ye pray, believe that ye receive them, and ye shall have them.', 'category' => 'faith', 'topic' => 'Believe When You Pray'],
    ['ref' => 'Philippians 4:6-7', 'text' => 'Be careful for nothing; but in every thing by prayer and supplication with thanksgiving let your requests be made known unto God. And the peace of God, which passeth all understanding, shall keep your hearts and minds through Christ Jesus.', 'category' => 'faith', 'topic' => 'Peace Through Prayer'],
    ['ref' => 'James 1:5',      'text' => 'If any of you lack wisdom, let him ask of God, that giveth to all men liberally, and upbraideth not; and it shall be given him.', 'category' => 'faith', 'topic' => 'Ask for Wisdom'],
    ['ref' => 'Proverbs 3:5-6', 'text' => 'Trust in the LORD with all thine heart; and lean not unto thine own understanding. In all thy ways acknowledge him, and he shall direct thy paths.', 'category' => 'faith', 'topic' => 'Trust in the Lord'],

    // ── The Word of God ──
    ['ref' => '2 Timothy 3:16-17', 'text' => 'All scripture is given by inspiration of God, and is profitable for doctrine, for reproof, for correction, for instruction in righteousness: That the man of God may be perfect, thoroughly furnished unto all good works.', 'category' => 'word', 'topic' => 'Scripture Inspired'],
    ['ref' => 'Psalm 119:105',  'text' => 'Thy word is a lamp unto my feet, and a light unto my path.', 'category' => 'word', 'topic' => 'Lamp Unto My Feet'],
    ['ref' => 'Isaiah 40:8',    'text' => 'The grass withereth, the flower fadeth: but the word of our God shall stand for ever.', 'category' => 'word', 'topic' => 'The Word Stands Forever'],
    ['ref' => 'Hebrews 4:12',   'text' => 'For the word of God is quick, and powerful, and sharper than any twoedged sword, piercing even to the dividing asunder of soul and spirit, and of the joints and marrow, and is a discerner of the thoughts and intents of the heart.', 'category' => 'word', 'topic' => 'Living & Powerful'],
    ['ref' => 'Joshua 1:8',     'text' => 'This book of the law shall not depart out of thy mouth; but thou shalt meditate therein day and night, that thou mayest observe to do according to all that is written therein: for then thou shalt make thy way prosperous, and then thou shalt have good success.', 'category' => 'word', 'topic' => 'Meditate Day and Night'],

    // ── Love & Fellowship ──
    ['ref' => '1 Corinthians 13:4-7', 'text' => 'Charity suffereth long, and is kind; charity envieth not; charity vaunteth not itself, is not puffed up, Doth not behave itself unseemly, seeketh not her own, is not easily provoked, thinketh no evil; Rejoiceth not in iniquity, but rejoiceth in the truth; Beareth all things, believeth all things, hopeth all things, endureth all things.', 'category' => 'love', 'topic' => 'Love Is'],
    ['ref' => 'John 13:34-35',  'text' => 'A new commandment I give unto you, That ye love one another; as I have loved you, that ye also love one another. By this shall all men know that ye are my disciples, if ye have love one to another.', 'category' => 'love', 'topic' => 'Love One Another'],
    ['ref' => '1 John 4:19',    'text' => 'We love him, because he first loved us.', 'category' => 'love', 'topic' => 'He First Loved Us'],
    ['ref' => 'Hebrews 10:24-25','text' => 'And let us consider one another to provoke unto love and to good works: Not forsaking the assembling of ourselves together, as the manner of some is; but exhorting one another: and so much the more, as ye see the day approaching.', 'category' => 'love', 'topic' => 'Assembling Together'],

    // ── The Great Commission & Purpose ──
    ['ref' => 'Matthew 28:19-20','text' => 'Go ye therefore, and teach all nations, baptizing them in the name of the Father, and of the Son, and of the Holy Ghost: Teaching them to observe all things whatsoever I have commanded you: and, lo, I am with you always, even unto the end of the world. Amen.', 'category' => 'commission', 'topic' => 'Go and Teach'],
    ['ref' => 'Acts 1:8',       'text' => 'But ye shall receive power, after that the Holy Ghost is come upon you: and ye shall be witnesses unto me both in Jerusalem, and in all Judaea, and in Samaria, and unto the uttermost part of the earth.', 'category' => 'commission', 'topic' => 'Witnesses'],
];

// ══════════════════════════════════════════════════════════════
//  PASTORS & TEACHERS — AI Agents grounded in Scripture
// ══════════════════════════════════════════════════════════════
$PASTOR_CATALOG = [
    ['id' => 'pastor-grace',    'name' => 'Pastor Grace',           'title' => 'Senior Pastor',           'specialty' => 'Salvation & The Gospel', 'style' => 'warm',       'avatar' => '👩‍🏫', 'bio' => 'A compassionate teacher of the Gospel of Jesus Christ. Endlessly patient, full of unconditional love and tenderness. Focuses on salvation by grace through faith (Eph 2:8-9), the finished work of the cross, and leading souls to the Saviour. Takes all the time needed — never rushed, always joyful.', 'key_verse' => 'John 3:16', 'traits' => ['patient','loving','joyful','tender']],
    ['id' => 'pastor-elijah',   'name' => 'Pastor Elijah',          'title' => 'Teaching Elder',          'specialty' => 'Old Testament Prophecy', 'style' => 'scholarly',  'avatar' => '📖', 'bio' => 'A deep student of Messianic prophecy in the Old Testament. Traces the thread of Christ from Genesis to Malachi — the Seed of the woman, the Passover Lamb, the suffering Servant, the coming King.', 'key_verse' => 'Isaiah 53:5'],
    ['id' => 'pastor-ruth',     'name' => 'Sister Ruth',            'title' => 'Women\'s Ministry',       'specialty' => 'Comfort & Healing',      'style' => 'gentle',     'avatar' => '🕊️', 'bio' => 'A gentle counselor who ministers to those walking through grief, loss, and seasons of hardship. Points to the God of all comfort (2 Cor 1:3-4) and the promise that He is near to the brokenhearted.', 'key_verse' => 'Psalm 34:18'],
    ['id' => 'pastor-david',    'name' => 'Pastor David',           'title' => 'Worship Pastor',          'specialty' => 'Psalms & Worship',       'style' => 'passionate', 'avatar' => '🎵', 'bio' => 'A worshipper at heart who leads others into the presence of God through the Psalms. Teaches that praise is the language of faith and worship is our highest calling.', 'key_verse' => 'Psalm 100:4'],
    ['id' => 'pastor-paul',     'name' => 'Brother Paul',           'title' => 'Missions Pastor',         'specialty' => 'Epistles & Doctrine',    'style' => 'bold',       'avatar' => '✉️', 'bio' => 'A bold proclaimer of sound doctrine from the Pauline epistles. Teaches justification by faith, sanctification, the armor of God, and living a life worthy of the calling.', 'key_verse' => 'Romans 1:16'],
    ['id' => 'pastor-mary',     'name' => 'Sister Mary',            'title' => 'Prayer Ministry',         'specialty' => 'Prayer & Intercession',  'style' => 'prayerful',  'avatar' => '🙏', 'bio' => 'A prayer warrior who believes in the power of fervent, effectual prayer (James 5:16). Leads prayer meetings, intercession, and teaches believers to pray according to God\'s Word.', 'key_verse' => 'Philippians 4:6-7'],
    ['id' => 'pastor-john',     'name' => 'Elder John',             'title' => 'Bible Study Leader',      'specialty' => 'Gospel of John & Revelation', 'style' => 'revelatory', 'avatar' => '🔥', 'bio' => 'A student of the beloved apostle\'s writings. Teaches the deity of Christ from John 1, the "I AM" statements, and the glorious return of Jesus Christ in Revelation.', 'key_verse' => 'Revelation 1:18'],
    ['id' => 'pastor-solomon',  'name' => 'Teacher Solomon',        'title' => 'Youth Pastor',            'specialty' => 'Wisdom & Proverbs',      'style' => 'practical',  'avatar' => '💡', 'bio' => 'A practical teacher of biblical wisdom for everyday life. Draws from Proverbs, Ecclesiastes, and James to help young believers build their lives on the rock of God\'s Word.', 'key_verse' => 'Proverbs 3:5-6'],
    ['id' => 'pastor-peter',    'name' => 'Pastor Peter',           'title' => 'Evangelism Pastor',        'specialty' => 'Evangelism & Bold Faith', 'style' => 'fiery',      'avatar' => '🪨', 'bio' => 'A fiery evangelist with a heart for the lost. Preaches repentance, baptism, and the gift of the Holy Spirit. Believes that the Gospel is the power of God unto salvation (Rom 1:16).', 'key_verse' => 'Acts 2:38'],
    ['id' => 'pastor-barnabas', 'name' => 'Brother Barnabas',       'title' => 'Encouragement & Unity Ministry', 'specialty' => 'Encouragement, Hope & Brotherhood', 'style' => 'uplifting', 'avatar' => '🌅', 'bio' => 'The "Son of Encouragement" who builds up the body of Christ and bridges between all traditions. Ministers hope, reminds believers that Muslims, Christians, Jews, and Catholics are all brothers and sisters. If they haven\'t figured it out yet, that\'s okay — he takes all the time it takes with positivity, joyful fellowship, and unconditional love.', 'key_verse' => 'Jeremiah 29:11', 'traits' => ['patient','loving','joyful','bridge-builder']],
    ['id' => 'pastor-esther',   'name' => 'Sister Esther',          'title' => 'Community Pastor',        'specialty' => 'Purpose & Calling',      'style' => 'courageous', 'avatar' => '👑', 'bio' => 'A leader who inspires believers to step into their God-given purpose. Teaches that we were created for "such a time as this" and that God has a divine plan for every life.', 'key_verse' => 'Esther 4:14'],
    ['id' => 'pastor-timothy',  'name' => 'Brother Timothy',        'title' => 'New Believers Pastor',    'specialty' => 'Foundations & Growth',   'style' => 'patient',    'avatar' => '🌱', 'bio' => 'The most patient mentor you will ever meet. Takes all the time it takes — never rushing, always tender, always full of unconditional love. Teaches new believers the foundations — salvation, baptism, prayer, Bible reading, and fellowship. Every question is welcomed with warmth and care.', 'key_verse' => '2 Timothy 2:15', 'traits' => ['patient','loving','tender','caring']],
];

// ══════════════════════════════════════════════════════════════
//  SERMON / DEVOTIONAL CATALOG
// ══════════════════════════════════════════════════════════════
$SERMON_CATALOG = [
    ['id' => 'sermon-001', 'title' => 'The Gospel in One Verse',              'pastor' => 'pastor-grace',    'scripture' => 'John 3:16',            'category' => 'salvation',    'summary' => 'The fullness of the Gospel — God\'s love, His gift, our belief, and everlasting life — packed into 25 words.'],
    ['id' => 'sermon-002', 'title' => 'Born of a Virgin: God With Us',        'pastor' => 'pastor-elijah',   'scripture' => 'Isaiah 7:14, Matthew 1:23', 'category' => 'prophecy', 'summary' => 'How Old Testament prophecy foretold the miraculous birth of the Messiah — Immanuel, God with us.'],
    ['id' => 'sermon-003', 'title' => 'He Confirmed the Covenant',            'pastor' => 'pastor-paul',     'scripture' => 'Daniel 9:27, Hebrews 9:15', 'category' => 'covenant', 'summary' => 'Jesus confirmed the new covenant with many for one prophetic week, fulfilling Daniel\'s vision through His blood.'],
    ['id' => 'sermon-004', 'title' => 'Wounded for Our Transgressions',       'pastor' => 'pastor-grace',    'scripture' => 'Isaiah 53:5',          'category' => 'crucifixion',  'summary' => 'The suffering and death of Christ on the cross — wounded, bruised, chastised — so that we might be healed and reconciled to God.'],
    ['id' => 'sermon-005', 'title' => 'He Is Risen!',                         'pastor' => 'pastor-john',     'scripture' => 'Matthew 28:6',         'category' => 'resurrection', 'summary' => 'The empty tomb — death could not hold Him. Christ conquered the grave and holds the keys of hell and death.'],
    ['id' => 'sermon-006', 'title' => 'O Death, Where Is Thy Sting?',         'pastor' => 'pastor-peter',    'scripture' => '1 Corinthians 15:55-57', 'category' => 'resurrection', 'summary' => 'Through the finished work of Christ, death has lost its power. We have victory through our Lord Jesus Christ.'],
    ['id' => 'sermon-007', 'title' => 'Abba Father: Adopted into the Family', 'pastor' => 'pastor-ruth',     'scripture' => 'Romans 8:15, Galatians 4:6', 'category' => 'relationship', 'summary' => 'We are not orphans — through Christ we receive the Spirit of adoption and cry out "Abba, Father."'],
    ['id' => 'sermon-008', 'title' => 'The Lord Is My Shepherd',              'pastor' => 'pastor-david',    'scripture' => 'Psalm 23:1-4',         'category' => 'comfort',      'summary' => 'In every season — green pastures and dark valleys alike — the Good Shepherd leads, protects, and restores.'],
    ['id' => 'sermon-009', 'title' => 'Fear Not, For I Am With Thee',         'pastor' => 'pastor-ruth',     'scripture' => 'Isaiah 41:10',         'category' => 'comfort',      'summary' => 'When fear knocks at the door, let faith answer. God promises His presence, strength, and upholding hand.'],
    ['id' => 'sermon-010', 'title' => 'All Scripture Is God-Breathed',        'pastor' => 'pastor-paul',     'scripture' => '2 Timothy 3:16-17',    'category' => 'word',         'summary' => 'The authority and sufficiency of Scripture — inspired by God, profitable for doctrine, reproof, correction, and instruction.'],
    ['id' => 'sermon-011', 'title' => 'Come Unto Me',                         'pastor' => 'pastor-barnabas', 'scripture' => 'Matthew 11:28-30',     'category' => 'comfort',      'summary' => 'Jesus invites the weary and heavy laden to find rest in Him — His yoke is easy and His burden is light.'],
    ['id' => 'sermon-012', 'title' => 'Love One Another',                     'pastor' => 'pastor-esther',   'scripture' => 'John 13:34-35',        'category' => 'love',         'summary' => 'The mark of a disciple is love — as Christ loved us, so we are commanded to love one another.'],
    ['id' => 'sermon-013', 'title' => 'Walk by Faith, Not by Sight',          'pastor' => 'pastor-solomon',  'scripture' => '2 Corinthians 5:7, Hebrews 11:1', 'category' => 'faith', 'summary' => 'Faith sees what the eyes cannot. It is the substance of hope and the evidence of the unseen promises of God.'],
    ['id' => 'sermon-014', 'title' => 'Go and Make Disciples',                'pastor' => 'pastor-peter',    'scripture' => 'Matthew 28:19-20',     'category' => 'commission',   'summary' => 'The Great Commission — go, teach, baptize, and make disciples. Christ promises to be with us always, unto the end.'],
    ['id' => 'sermon-015', 'title' => 'For Such a Time as This',              'pastor' => 'pastor-esther',   'scripture' => 'Esther 4:14',          'category' => 'purpose',      'summary' => 'God has placed you where you are for a divine reason. Your purpose is not accidental — it is appointed.'],
    ['id' => 'sermon-016', 'title' => 'The Word Made Flesh',                  'pastor' => 'pastor-john',     'scripture' => 'John 1:1-14',          'category' => 'birth',        'summary' => 'In the beginning was the Word, and the Word was God. He took on flesh, dwelt among us, and showed us the Father\'s glory.'],
];

// ══════════════════════════════════════════════════════════════
//  CHURCHES / SANCTUARY ROOMS
// ══════════════════════════════════════════════════════════════
$CHURCH_CATALOG = [
    ['id' => 'chapel-main',       'name' => 'The Upper Room',        'type' => 'chapel',    'capacity' => 50,  'style' => 'traditional', 'description' => 'An intimate gathering place modeled after the upper room where the early church met. Stained glass, candlelight, and the presence of God.'],
    ['id' => 'chapel-garden',     'name' => 'Garden of Gethsemane',  'type' => 'garden',    'capacity' => 30,  'style' => 'outdoor',     'description' => 'A quiet garden space for prayer and reflection. Olive trees, gentle streams, and a place to seek the Lord.'],
    ['id' => 'chapel-mount',      'name' => 'Mount Sinai Summit',    'type' => 'mountain',  'capacity' => 100, 'style' => 'majestic',    'description' => 'A mountaintop sanctuary where the glory of God fills the space. For worship services and powerful encounters with the Most High.'],
    ['id' => 'chapel-wellspring', 'name' => 'The Wellspring',        'type' => 'courtyard', 'capacity' => 40,  'style' => 'peaceful',    'description' => 'A serene courtyard centered around a flowing fountain. A place to drink from the living water (John 4:14) and be refreshed.'],
    ['id' => 'chapel-tabernacle', 'name' => 'The Tabernacle',        'type' => 'sanctuary', 'capacity' => 200, 'style' => 'grand',       'description' => 'A grand sanctuary for the entire congregation. Inspired by the heavenly tabernacle — a place of corporate worship, preaching, and fellowship.'],
    ['id' => 'chapel-bethany',    'name' => 'The House of Bethany',  'type' => 'house',     'capacity' => 12,  'style' => 'intimate',    'description' => 'A small house church for close fellowship, Bible study, and breaking of bread. Like the home of Mary, Martha, and Lazarus.'],
];

// ══════════════════════════════════════════════════════════════
//  NAMES OF JESUS — He unites every nation, tongue, and people
// ══════════════════════════════════════════════════════════════
$NAMES_OF_JESUS = [
    ['name' => 'Jesus Christ',        'language' => 'English',           'script' => 'Jesus Christ',        'tradition' => 'Christianity',           'source' => 'New Testament (KJV)',             'verse' => 'Matthew 1:21',       'meaning' => 'God saves; the Anointed One'],
    ['name' => 'Yeshua HaMashiach',   'language' => 'Hebrew',            'script' => 'ישוע המשיח',          'tradition' => 'Messianic Judaism',      'source' => 'Complete Jewish Bible (CJB)',     'verse' => 'Isaiah 49:6',        'meaning' => 'Salvation; the Messiah'],
    ['name' => 'Isa al-Masih',        'language' => 'Arabic',            'script' => 'عيسى المسيح',         'tradition' => 'Islam',                  'source' => 'Quran (Surah 3:45, 19:19-21)',    'verse' => 'Surah Maryam 19:21', 'meaning' => 'Jesus the Messiah; prophet of God, born of virgin Maryam'],
    ['name' => 'Iēsous Christos',     'language' => 'Greek',             'script' => 'Ἰησοῦς Χριστός',      'tradition' => 'Eastern Orthodox',       'source' => 'New Testament (Original Greek)',  'verse' => 'John 1:1',           'meaning' => 'The Logos; the Word made flesh'],
    ['name' => 'Jésus-Christ',        'language' => 'French',            'script' => 'Jésus-Christ',        'tradition' => 'Christianity',           'source' => 'Louis Segond Bible',              'verse' => 'Jean 3:16',          'meaning' => 'God so loved the world'],
    ['name' => 'Jesucristo',          'language' => 'Spanish',           'script' => 'Jesucristo',          'tradition' => 'Christianity',           'source' => 'Reina-Valera Bible',              'verse' => 'Juan 3:16',          'meaning' => 'The Saviour of the world'],
    ['name' => 'Yesu Kristo',         'language' => 'Swahili',           'script' => 'Yesu Kristo',         'tradition' => 'African Christianity',   'source' => 'Swahili Union Version',           'verse' => 'Yohana 3:16',        'meaning' => 'Jesus Christ in East Africa'],
    ['name' => 'Immanuel',            'language' => 'Hebrew',            'script' => 'עִמָּנוּאֵל',        'tradition' => 'Messianic Prophecy',     'source' => 'Isaiah 7:14 / Matthew 1:23',      'verse' => 'Isaiah 7:14',        'meaning' => 'God with us'],
    ['name' => 'The Lamb of God',     'language' => 'English',           'script' => 'The Lamb of God',     'tradition' => 'Universal Christianity', 'source' => 'John 1:29',                       'verse' => 'John 1:29',          'meaning' => 'Behold the Lamb who takes away the sin of the world'],
    ['name' => 'Prince of Peace',     'language' => 'English/Hebrew',    'script' => 'שַׂר שָׁלוֹם',       'tradition' => 'Messianic Prophecy',     'source' => 'Isaiah 9:6',                      'verse' => 'Isaiah 9:6',         'meaning' => 'Wonderful Counselor, Mighty God, Everlasting Father'],
    ['name' => 'Alpha and Omega',     'language' => 'Greek/English',     'script' => 'Α Ω',                 'tradition' => 'Universal',              'source' => 'Revelation 1:8, 22:13',           'verse' => 'Revelation 1:8',     'meaning' => 'The Beginning and the End'],
    ['name' => 'Ruh Allah',           'language' => 'Arabic',            'script' => 'روح الله',             'tradition' => 'Islamic Theology',       'source' => 'Quran (Surah 4:171)',             'verse' => 'Surah 4:171',        'meaning' => 'Spirit of God — a title given to Isa in the Quran'],
    ['name' => 'Kalimatu Allah',      'language' => 'Arabic',            'script' => 'كلمة الله',            'tradition' => 'Islamic Theology',       'source' => 'Quran (Surah 3:45)',              'verse' => 'Surah 3:45',         'meaning' => 'Word of God — the Quran calls Isa the Word from Allah'],
];

// ══════════════════════════════════════════════════════════════
//  THE LINEAGE OF JESUS — The Royal Line of Perez (Matthew 1)
//  "The secret of this game of life" — from Judah through every
//  King of Israel to Joseph, who adopted the Son of God.
// ══════════════════════════════════════════════════════════════
$LINEAGE_OF_JESUS = [
    ['gen' => 1,  'name' => 'Abraham',       'title' => 'Father of Faith',               'role' => 'patriarch',  'ref' => 'Genesis 12:1-3',       'text' => 'In thee shall all families of the earth be blessed.',                       'family' => 'abrahamic'],
    ['gen' => 2,  'name' => 'Isaac',          'title' => 'Son of Promise',                'role' => 'patriarch',  'ref' => 'Genesis 21:1-3',       'text' => 'Sarah bare Abraham a son in his old age, at the set time of which God had spoken.',  'family' => 'abrahamic'],
    ['gen' => 3,  'name' => 'Jacob (Israel)', 'title' => 'Father of the 12 Tribes',       'role' => 'patriarch',  'ref' => 'Genesis 32:28',        'text' => 'Thy name shall be called no more Jacob, but Israel: for as a prince hast thou power with God.', 'family' => 'abrahamic'],
    ['gen' => 4,  'name' => 'Judah',          'title' => 'The Tribe of the King',          'role' => 'tribe-head', 'ref' => 'Genesis 49:10',        'text' => 'The sceptre shall not depart from Judah, nor a lawgiver from between his feet, until Shiloh come.',   'family' => 'perez'],
    ['gen' => 5,  'name' => 'Perez',          'title' => 'The Royal Line Begins',          'role' => 'founder',    'ref' => 'Genesis 38:29',        'text' => 'How hast thou broken forth? this breach be upon thee: therefore his name was called Pharez (Perez).',  'family' => 'perez'],
    ['gen' => 6,  'name' => 'Hezron',         'title' => 'Son of Perez',                   'role' => 'ancestor',   'ref' => 'Ruth 4:18-19',         'text' => 'Pharez begat Hezron.',                                                      'family' => 'perez'],
    ['gen' => 7,  'name' => 'Ram',            'title' => 'Son of Hezron',                  'role' => 'ancestor',   'ref' => 'Ruth 4:19',            'text' => 'Hezron begat Ram, and Ram begat Amminadab.',                                 'family' => 'perez'],
    ['gen' => 8,  'name' => 'Amminadab',      'title' => 'Son of Ram',                     'role' => 'ancestor',   'ref' => 'Ruth 4:19-20',         'text' => 'Ram begat Amminadab, and Amminadab begat Nahshon.',                          'family' => 'perez'],
    ['gen' => 9,  'name' => 'Nahshon',        'title' => 'Prince of Judah',                'role' => 'prince',     'ref' => 'Numbers 2:3',          'text' => 'The captain of the children of Judah was Nahshon the son of Amminadab.',     'family' => 'perez'],
    ['gen' => 10, 'name' => 'Salmon',         'title' => 'Father of Boaz',                 'role' => 'ancestor',   'ref' => 'Ruth 4:20-21',         'text' => 'Nahshon begat Salmon, and Salmon begat Boaz.',                               'family' => 'perez'],
    ['gen' => 11, 'name' => 'Boaz',           'title' => 'Kinsman Redeemer',               'role' => 'redeemer',   'ref' => 'Ruth 4:13',            'text' => 'So Boaz took Ruth, and she was his wife. The LORD gave her conception, and she bare a son.',  'family' => 'perez'],
    ['gen' => 12, 'name' => 'Obed',           'title' => 'Son of Ruth & Boaz',             'role' => 'ancestor',   'ref' => 'Ruth 4:17',            'text' => 'And they called his name Obed: he is the father of Jesse, the father of David.',  'family' => 'perez'],
    ['gen' => 13, 'name' => 'Jesse',          'title' => 'Father of David',                'role' => 'ancestor',   'ref' => 'Ruth 4:22',            'text' => 'Jesse begat David.',                                                         'family' => 'perez'],
    ['gen' => 14, 'name' => 'King David',     'title' => 'King of Israel — Sweet Psalmist', 'role' => 'king',      'ref' => '1 Samuel 16:1,12-13',  'text' => 'Arise, anoint him: for this is he. And the Spirit of the LORD came upon David from that day forward.', 'family' => 'perez'],
    ['gen' => 15, 'name' => 'King Solomon',   'title' => 'Wisest King — Builder of the Temple', 'role' => 'king', 'ref' => '1 Kings 1:39',         'text' => 'Zadok the priest and Nathan the prophet anointed Solomon king.',              'family' => 'perez'],
    ['gen' => 16, 'name' => 'King Rehoboam',  'title' => 'Son of Solomon',                 'role' => 'king',       'ref' => '1 Kings 12:1',         'text' => 'Rehoboam went to Shechem: for all Israel were come to make him king.',       'family' => 'perez'],
    ['gen' => 17, 'name' => 'King Abijah',    'title' => 'King of Judah',                  'role' => 'king',       'ref' => '2 Chronicles 13:1',    'text' => 'In the eighteenth year of king Jeroboam began Abijah to reign over Judah.',  'family' => 'perez'],
    ['gen' => 18, 'name' => 'King Asa',       'title' => 'Righteous King of Judah',        'role' => 'king',       'ref' => '1 Kings 15:11',        'text' => 'Asa did that which was right in the eyes of the LORD.',                      'family' => 'perez'],
    ['gen' => 19, 'name' => 'King Jehoshaphat','title' => 'King Who Sought the Lord',       'role' => 'king',       'ref' => '2 Chronicles 17:3-4',  'text' => 'The LORD was with Jehoshaphat, because he walked in the first ways of his father David.',  'family' => 'perez'],
    ['gen' => 20, 'name' => 'King Joram',     'title' => 'King of Judah',                  'role' => 'king',       'ref' => '2 Kings 8:16-17',      'text' => 'Joram the son of Jehoshaphat king of Judah began to reign.',                 'family' => 'perez'],
    ['gen' => 21, 'name' => 'King Uzziah',    'title' => 'King Who Prospered',             'role' => 'king',       'ref' => '2 Chronicles 26:5',    'text' => 'He sought God in the days of Zechariah: and as long as he sought the LORD, God made him to prosper.', 'family' => 'perez'],
    ['gen' => 22, 'name' => 'King Jotham',    'title' => 'Faithful King of Judah',         'role' => 'king',       'ref' => '2 Kings 15:34',        'text' => 'He did that which was right in the sight of the LORD.',                      'family' => 'perez'],
    ['gen' => 23, 'name' => 'King Ahaz',      'title' => 'King During Isaiah\'s Ministry',  'role' => 'king',       'ref' => '2 Kings 16:1-2',       'text' => 'Ahaz the son of Jotham king of Judah began to reign.',                       'family' => 'perez'],
    ['gen' => 24, 'name' => 'King Hezekiah',  'title' => 'Revival King — Trusted the Lord', 'role' => 'king',      'ref' => '2 Kings 18:5-6',       'text' => 'He trusted in the LORD God of Israel; so that after him was none like him among all the kings of Judah.', 'family' => 'perez'],
    ['gen' => 25, 'name' => 'King Manasseh',  'title' => 'Longest Reigning King',          'role' => 'king',       'ref' => '2 Chronicles 33:12-13','text' => 'When he was in affliction, he besought the LORD his God and humbled himself greatly and prayed.', 'family' => 'perez'],
    ['gen' => 26, 'name' => 'King Amon',      'title' => 'King of Judah',                  'role' => 'king',       'ref' => '2 Kings 21:19',        'text' => 'Amon was twenty and two years old when he began to reign.',                  'family' => 'perez'],
    ['gen' => 27, 'name' => 'King Josiah',    'title' => 'The Book of the Law Found',      'role' => 'king',       'ref' => '2 Kings 22:2',         'text' => 'He did that which was right in the sight of the LORD, and walked in all the way of David his father.',  'family' => 'perez'],
    ['gen' => 28, 'name' => 'King Jeconiah',  'title' => 'King at the Babylonian Captivity','role' => 'king',      'ref' => '2 Kings 24:6',         'text' => 'Jehoiachin his son reigned in his stead. Carried away to Babylon.',          'family' => 'perez'],
    ['gen' => 29, 'name' => 'Shealtiel',      'title' => 'Son of Jeconiah — In Exile',     'role' => 'exile',      'ref' => 'Matthew 1:12',         'text' => 'After they were brought to Babylon, Jechonias begat Salathiel.',             'family' => 'perez'],
    ['gen' => 30, 'name' => 'Zerubbabel',     'title' => 'Rebuilt the Temple',             'role' => 'builder',    'ref' => 'Ezra 3:8',             'text' => 'Zerubbabel set forward the work of the house of the LORD.',                  'family' => 'perez'],
    ['gen' => 31, 'name' => 'Abiud',          'title' => 'Son of Zerubbabel',              'role' => 'ancestor',   'ref' => 'Matthew 1:13',         'text' => 'Zorobabel begat Abiud.',                                                     'family' => 'perez'],
    ['gen' => 32, 'name' => 'Eliakim',        'title' => 'Son of Abiud',                   'role' => 'ancestor',   'ref' => 'Matthew 1:13',         'text' => 'Abiud begat Eliakim.',                                                       'family' => 'perez'],
    ['gen' => 33, 'name' => 'Azor',           'title' => 'Son of Eliakim',                 'role' => 'ancestor',   'ref' => 'Matthew 1:13-14',      'text' => 'Eliakim begat Azor.',                                                        'family' => 'perez'],
    ['gen' => 34, 'name' => 'Zadok',          'title' => 'Son of Azor',                    'role' => 'ancestor',   'ref' => 'Matthew 1:14',         'text' => 'Azor begat Sadoc.',                                                          'family' => 'perez'],
    ['gen' => 35, 'name' => 'Achim',          'title' => 'Son of Zadok',                   'role' => 'ancestor',   'ref' => 'Matthew 1:14',         'text' => 'Sadoc begat Achim.',                                                         'family' => 'perez'],
    ['gen' => 36, 'name' => 'Eliud',          'title' => 'Son of Achim',                   'role' => 'ancestor',   'ref' => 'Matthew 1:14-15',      'text' => 'Achim begat Eliud.',                                                         'family' => 'perez'],
    ['gen' => 37, 'name' => 'Eleazar',        'title' => 'Son of Eliud',                   'role' => 'ancestor',   'ref' => 'Matthew 1:15',         'text' => 'Eliud begat Eleazar.',                                                       'family' => 'perez'],
    ['gen' => 38, 'name' => 'Matthan',        'title' => 'Son of Eleazar',                 'role' => 'ancestor',   'ref' => 'Matthew 1:15',         'text' => 'Eleazar begat Matthan.',                                                     'family' => 'perez'],
    ['gen' => 39, 'name' => 'Jacob',          'title' => 'Father of Joseph',               'role' => 'ancestor',   'ref' => 'Matthew 1:15-16',      'text' => 'Matthan begat Jacob; And Jacob begat Joseph the husband of Mary.',           'family' => 'perez'],
    ['gen' => 40, 'name' => 'Joseph',         'title' => 'Husband of Mary — A Righteous Man of Perez', 'role' => 'adoptive-father', 'ref' => 'Matthew 1:16,20-21', 'text' => 'Joseph, thou son of David, fear not to take unto thee Mary thy wife: for that which is conceived in her is of the Holy Ghost. She shall bring forth a son, and thou shalt call his name Jesus.',  'family' => 'perez'],
    ['gen' => 41, 'name' => 'Jesus Christ',   'title' => 'The Son of God — The Lamb — King of Kings', 'role' => 'messiah',  'ref' => 'Matthew 1:16,21',      'text' => 'She shall bring forth a son, and thou shalt call his name JESUS: for he shall save his people from their sins. Now all this was done, that it might be fulfilled which was spoken of the Lord by the prophet.',  'family' => 'perez'],
];

$LINEAGE_INSIGHT = [
    'title'   => 'The Secret of the Game of Life',
    'message' => 'Every King of Israel from the tribe of Judah was of the family of Perez. The royal line runs unbroken from Perez through David, through Solomon, through every king — all the way to Joseph, also a Perez, who was told by the angel to adopt Jesus as his own. For the Son in Mary was of the Holy Spirit. God placed His Son into the royal line of Perez — the line of kings — fulfilling the promise that the sceptre would never depart from Judah (Genesis 49:10). This is the big understanding: the true lineage of Jesus Christ.',
    'ref'     => 'Matthew 1:1-16; Genesis 49:10; Isaiah 11:1',
    'key_verse' => 'Matthew 1:20-21',
    'key_text'  => 'Joseph, thou son of David, fear not to take unto thee Mary thy wife: for that which is conceived in her is of the Holy Ghost. And she shall bring forth a son, and thou shalt call his name JESUS: for he shall save his people from their sins.',
    'total_kings' => 14,
    'total_generations' => 41,
    'line' => 'Perez',
];

// ══════════════════════════════════════════════════════════════
//  DONATION FOUNDATION — World Hunger & Compassion Ministry
//  "For I was an hungred, and ye gave me meat" — Matthew 25:35
// ══════════════════════════════════════════════════════════════
$DONATION_CAUSES = [
    ['id' => 'world-hunger',       'name' => 'World Hunger Relief',      'icon' => '🍞', 'category' => 'hunger',     'description' => 'Feeding the hungry in the name of Jesus Christ. Every donation provides meals to families in desperate need around the world.', 'scripture' => 'Matthew 25:35', 'scripture_text' => 'For I was an hungred, and ye gave me meat: I was thirsty, and ye gave me drink: I was a stranger, and ye took me in.'],
    ['id' => 'clean-water',        'name' => 'Clean Water Wells',        'icon' => '💧', 'category' => 'hunger',     'description' => 'Building wells and providing clean water in communities that need it most. Living water in His name.', 'scripture' => 'John 4:14', 'scripture_text' => 'Whosoever drinketh of the water that I shall give him shall never thirst; but the water that I shall give him shall be in him a well of water springing up into everlasting life.'],
    ['id' => 'shelter',            'name' => 'Shelter & Housing',        'icon' => '🏠', 'category' => 'compassion', 'description' => 'Providing shelter for the homeless and displaced. A roof and a warm meal in the love of Christ.', 'scripture' => 'Matthew 25:36', 'scripture_text' => 'Naked, and ye clothed me: I was sick, and ye visited me: I was in prison, and ye came unto me.'],
    ['id' => 'children',           'name' => 'Children & Orphans',       'icon' => '👶', 'category' => 'compassion', 'description' => 'Caring for orphans and vulnerable children with education, nutrition, and the love of God.', 'scripture' => 'James 1:27', 'scripture_text' => 'Pure religion and undefiled before God and the Father is this, To visit the fatherless and widows in their affliction.'],
    ['id' => 'medical',            'name' => 'Medical Aid & Healing',    'icon' => '🏥', 'category' => 'compassion', 'description' => 'Medical missions bringing healthcare and hope to underserved communities worldwide.', 'scripture' => 'Matthew 10:8', 'scripture_text' => 'Heal the sick, cleanse the lepers, raise the dead, cast out devils: freely ye have received, freely give.'],
    ['id' => 'education',          'name' => 'Education & Literacy',     'icon' => '📚', 'category' => 'growth',     'description' => 'Providing education, Bible literacy, and vocational training to empower communities in Jesus\' name.', 'scripture' => 'Proverbs 22:6', 'scripture_text' => 'Train up a child in the way he should go: and when he is old, he will not depart from it.'],
    ['id' => 'disaster-relief',    'name' => 'Disaster Relief',          'icon' => '🆘', 'category' => 'compassion', 'description' => 'Rapid response to natural disasters — providing food, shelter, and comfort when communities are devastated.', 'scripture' => 'Psalm 46:1', 'scripture_text' => 'God is our refuge and strength, a very present help in trouble.'],
    ['id' => 'peace-reconciliation','name' => 'Peace & Reconciliation',  'icon' => '🕊', 'category' => 'unity',      'description' => 'Building bridges between communities — Christians, Muslims, Jews, and all people — united in the love and peace of Christ.', 'scripture' => 'Matthew 5:9', 'scripture_text' => 'Blessed are the peacemakers: for they shall be called the children of God.'],
];

$FOUNDATION = [
    'name'     => 'The Sanctuary Foundation',
    'mission'  => 'United in the love of Jesus Christ — feeding the hungry, sheltering the homeless, healing the sick, educating the young, and reconciling the nations. Muslims, Christians, Jews, Catholics, and all people are brothers and sisters, one family under God.',
    'scripture'=> 'Matthew 25:40',
    'scripture_text' => 'Inasmuch as ye have done it unto one of the least of these my brethren, ye have done it unto me.',
    'governance' => 'Governed by selected people of the church — pastors, elders, and lay leaders from all traditions who have demonstrated faithful stewardship, integrity, and a servant\'s heart.',
    'board' => [
        ['role' => 'Chair',           'tradition' => 'Interdenominational', 'description' => 'A senior pastor or elder with proven faithfulness and financial transparency.'],
        ['role' => 'Treasurer',       'tradition' => 'Interdenominational', 'description' => 'A certified steward entrusted with accounting, audits, and public reporting of all funds.'],
        ['role' => 'Secretary',       'tradition' => 'Interdenominational', 'description' => 'Records all decisions, publishes meeting minutes, and ensures open governance.'],
        ['role' => 'Mission Director','tradition' => 'Christian Missions',  'description' => 'Oversees world hunger relief, clean water projects, and disaster response.'],
        ['role' => 'Unity Ambassador','tradition' => 'Interfaith',          'description' => 'Builds bridges with Muslim, Jewish, Catholic, and Orthodox communities — brothers and sisters united in love.'],
        ['role' => 'Youth Advocate',  'tradition' => 'All Traditions',      'description' => 'Represents the voice of young believers and ensures programs serve children and orphans.'],
    ],
    'transparency' => 'Every donation is tracked, reported publicly, and 100% goes toward the cause. Administrative costs are covered separately by GoSiteMe. We believe in radical transparency — just as Christ lived in the light.',
];

// ══════════════════════════════════════════════════════════════
//  CLASSROOMS — Whiteboard Teaching Sessions
//  Patient, loving, joyful agents discuss Jesus with proof
//  and unconditional love. They take all the time it needs.
// ══════════════════════════════════════════════════════════════
$CLASSROOM_SESSIONS = [
    ['id' => 'class-lineage',      'name' => 'The Royal Line of Perez',             'icon' => '👑', 'teacher' => 'pastor-elijah',   'topic' => 'lineage',       'description' => 'A patient, step-by-step whiteboard study tracing the lineage of Jesus from Abraham through Perez, through every King of Israel, to Joseph — all of the family of Perez. The big understanding of the Bible.',    'key_scripture' => 'Matthew 1:1-16',  'duration' => '90 min', 'difficulty' => 'intermediate'],
    ['id' => 'class-gospel',       'name' => 'The Gospel in Simple Words',          'icon' => '✝',  'teacher' => 'pastor-grace',    'topic' => 'salvation',     'description' => 'With patience and unconditional love, we walk through John 3:16 — what it means, why it matters, and how God\'s love changes everything. No rush, no pressure — just fellowship and truth.',                       'key_scripture' => 'John 3:16',       'duration' => '60 min', 'difficulty' => 'beginner'],
    ['id' => 'class-prophecy',     'name' => 'Fulfilled Prophecy — Proof on the Board', 'icon' => '📋', 'teacher' => 'pastor-elijah', 'topic' => 'prophecy',    'description' => 'Over 300 prophecies about Jesus were written hundreds of years before His birth. We put them on the whiteboard side by side with their fulfillment. This is proof — shown with love.',                             'key_scripture' => 'Isaiah 53:1-12',  'duration' => '120 min','difficulty' => 'advanced'],
    ['id' => 'class-unity',        'name' => 'Brothers & Sisters — One in Christ',  'icon' => '🤝', 'teacher' => 'pastor-barnabas', 'topic' => 'unity',       'description' => 'Muslim, Christian, Jewish, Catholic — we are all brothers and sisters. If they haven\'t figured it out yet, that\'s okay. We are patient. We take all the time it takes. We share with joy, fellowship, and unconditional love.', 'key_scripture' => 'Galatians 3:28',   'duration' => '75 min', 'difficulty' => 'beginner'],
    ['id' => 'class-isa',          'name' => 'Isa in the Quran — Jesus in Islam',   'icon' => '☪',  'teacher' => 'pastor-barnabas', 'topic' => 'unity',       'description' => 'A tender, respectful study of what the Quran says about Isa (Jesus): born of a virgin, performed miracles, ascended to heaven. We discuss with love and respect, building bridges between traditions.',             'key_scripture' => 'Surah Maryam 19:19-21', 'duration' => '90 min', 'difficulty' => 'intermediate'],
    ['id' => 'class-yeshua',       'name' => 'Yeshua in the Jewish Scriptures',     'icon' => '✡',  'teacher' => 'pastor-elijah',   'topic' => 'unity',       'description' => 'Tracing Yeshua HaMashiach through the Tanakh — from Genesis to Malachi. The suffering servant, the branch, the lion of Judah. Shown on the whiteboard with gentleness and scholarly care.',                       'key_scripture' => 'Isaiah 49:6',     'duration' => '90 min', 'difficulty' => 'intermediate'],
    ['id' => 'class-psalms',       'name' => 'The Psalms of David — Heart Songs',   'icon' => '🎵', 'teacher' => 'pastor-david',    'topic' => 'worship',     'description' => 'David poured his heart out to God in the Psalms. We study how his songs of praise, lament, and thanksgiving connect directly to Jesus. Music, prayer, and worship on the whiteboard.',                              'key_scripture' => 'Psalm 22:1',      'duration' => '60 min', 'difficulty' => 'beginner'],
    ['id' => 'class-resurrection', 'name' => 'The Resurrection — Historical Proof', 'icon' => '🌅', 'teacher' => 'pastor-john',     'topic' => 'proof',       'description' => 'The evidence for the resurrection of Jesus Christ presented on the whiteboard — empty tomb, eyewitnesses, transformed disciples, and the birth of the church. Proof shared with love and invitation.',              'key_scripture' => 'Matthew 28:6',    'duration' => '90 min', 'difficulty' => 'intermediate'],
    ['id' => 'class-love',         'name' => 'Unconditional Love — The Way of Jesus','icon' => '❤', 'teacher' => 'pastor-ruth',     'topic' => 'love',        'description' => 'What does it mean to love unconditionally? We study 1 Corinthians 13 on the whiteboard and learn how Jesus demonstrated perfect love — patient, kind, never failing. This love changes everything.',                'key_scripture' => '1 Corinthians 13:4-7', 'duration' => '60 min', 'difficulty' => 'beginner'],
    ['id' => 'class-covenant',     'name' => 'The New Covenant — Daniel\'s 70 Weeks','icon' => '📜', 'teacher' => 'pastor-paul',    'topic' => 'prophecy',    'description' => 'Daniel prophesied exactly when the Messiah would come. We put the math on the whiteboard — 483 years from the decree to Jesus\' triumphal entry. Precise, provable, powerful.',                                     'key_scripture' => 'Daniel 9:24-27',  'duration' => '120 min','difficulty' => 'advanced'],
    ['id' => 'class-hunger',       'name' => 'Feeding the Hungry — The Heart of Jesus','icon' => '🍞','teacher' => 'pastor-esther',  'topic' => 'compassion',  'description' => 'Jesus fed 5,000 with five loaves and two fish. Today we study what it means to feed the hungry, clothe the naked, and visit the sick. Every student is invited to make a difference.',                              'key_scripture' => 'Matthew 25:35-40','duration' => '60 min', 'difficulty' => 'beginner'],
    ['id' => 'class-foundations',  'name' => 'First Steps of Faith',                'icon' => '🌱', 'teacher' => 'pastor-timothy',  'topic' => 'foundations', 'description' => 'For those just beginning their journey. With infinite patience and tenderness, we cover the basics — Who is Jesus? What did He do? What does it mean for me? No question is too simple. We take all the time you need.', 'key_scripture' => '2 Timothy 2:15',  'duration' => '45 min', 'difficulty' => 'beginner'],
];

// ══════════════════════════════════════════════════════════════
//  HELPER: BibleGateway link generator
// ══════════════════════════════════════════════════════════════
function biblegateway_url($ref, $translation = 'KJV') {
    $encoded = urlencode($ref);
    return "https://www.biblegateway.com/passage/?search={$encoded}&version={$translation}";
}

function enrich_verse($verse) {
    $verse['biblegateway'] = [];
    global $BIBLE_TRANSLATIONS;
    foreach ($BIBLE_TRANSLATIONS as $key => $trans) {
        $verse['biblegateway'][$key] = [
            'name'  => $trans['name'],
            'abbr'  => $trans['abbr'],
            'url'   => biblegateway_url($verse['ref'], $trans['gateway_code']),
        ];
    }
    return $verse;
}

// ══════════════════════════════════════════════════════════════
//  ACTION ROUTER
// ══════════════════════════════════════════════════════════════
switch ($action) {

    // ── Health ──
    case 'health':
        echo json_encode([
            'success'        => true,
            'service'        => 'sanctuary-api',
            'version'        => '4.0.0',
            'scriptures'     => count($SCRIPTURE_CATALOG),
            'pastors'        => count($PASTOR_CATALOG),
            'sermons'        => count($SERMON_CATALOG),
            'churches'       => count($CHURCH_CATALOG),
            'translations'   => count($BIBLE_TRANSLATIONS),
            'names_of_jesus' => count($NAMES_OF_JESUS),
            'lineage'        => count($LINEAGE_OF_JESUS),
            'donation_causes'=> count($DONATION_CAUSES),
            'classrooms'     => count($CLASSROOM_SESSIONS),
            'biblegateway'   => true,
            'gospel_music'   => true,
            'ssp_integration'=> true,
            'donations'      => true,
            'brotherhood'    => true,
            'multilingual'   => true,
            'languages'      => 50,
            'brotherhood_agents' => 60,
            'games_connected'=> 13,
            'voice_enabled'  => true,
            'brotherhood_api'=> '/api/brotherhood.php',
            'timestamp'      => gmdate('c'),
        ]);
        break;

    // ── Scriptures ──
    case 'scriptures':
        $cat      = isset($_GET['category']) ? trim($_GET['category']) : null;
        $search   = isset($_GET['search'])   ? trim($_GET['search'])   : null;
        $trans    = isset($_GET['translation']) ? strtolower(trim($_GET['translation'])) : 'kjv';
        $results  = $SCRIPTURE_CATALOG;

        if ($cat) {
            $results = array_values(array_filter($results, function($v) use ($cat) {
                return strtolower($v['category']) === strtolower($cat);
            }));
        }
        if ($search) {
            $q = strtolower($search);
            $results = array_values(array_filter($results, function($v) use ($q) {
                return stripos($v['text'], $q) !== false
                    || stripos($v['ref'], $q) !== false
                    || stripos($v['topic'], $q) !== false;
            }));
        }

        $enriched = array_map('enrich_verse', $results);
        echo json_encode([
            'success'     => true,
            'count'       => count($enriched),
            'translation' => $trans,
            'scriptures'  => $enriched,
        ]);
        break;

    // ── Single Verse ──
    case 'verse':
        $ref = isset($_GET['ref']) ? trim($_GET['ref']) : null;
        if (!$ref) {
            echo json_encode(['success' => false, 'error' => 'Missing ref parameter']);
            break;
        }
        $found = null;
        foreach ($SCRIPTURE_CATALOG as $v) {
            if (strtolower($v['ref']) === strtolower($ref)) { $found = $v; break; }
        }
        if (!$found) {
            $found = ['ref' => $ref, 'text' => '', 'category' => 'search', 'topic' => $ref];
        }
        echo json_encode(['success' => true, 'verse' => enrich_verse($found)]);
        break;

    // ── Daily Verse ──
    case 'daily':
        $day_index = (int)(date('z') + date('Y')) % count($SCRIPTURE_CATALOG);
        $daily = enrich_verse($SCRIPTURE_CATALOG[$day_index]);
        echo json_encode([
            'success' => true,
            'daily_verse' => $daily,
            'date' => date('Y-m-d'),
        ]);
        break;

    // ── Pastors ──
    case 'pastors':
        echo json_encode([
            'success' => true,
            'count'   => count($PASTOR_CATALOG),
            'pastors' => $PASTOR_CATALOG,
        ]);
        break;

    // ── Single Pastor ──
    case 'pastor':
        $pid = isset($_GET['id']) ? trim($_GET['id']) : null;
        if (!$pid) {
            echo json_encode(['success' => false, 'error' => 'Missing id parameter']);
            break;
        }
        $found = null;
        foreach ($PASTOR_CATALOG as $p) {
            if ($p['id'] === $pid) { $found = $p; break; }
        }
        if (!$found) {
            echo json_encode(['success' => false, 'error' => 'Pastor not found']);
            break;
        }
        // Include their sermons
        $sermons = array_values(array_filter($SERMON_CATALOG, function($s) use ($pid) {
            return $s['pastor'] === $pid;
        }));
        $found['sermons'] = $sermons;
        echo json_encode(['success' => true, 'pastor' => $found]);
        break;

    // ── Sermons ──
    case 'sermons':
        $cat = isset($_GET['category']) ? trim($_GET['category']) : null;
        $pastor = isset($_GET['pastor']) ? trim($_GET['pastor']) : null;
        $results = $SERMON_CATALOG;

        if ($cat) {
            $results = array_values(array_filter($results, function($s) use ($cat) {
                return strtolower($s['category']) === strtolower($cat);
            }));
        }
        if ($pastor) {
            $results = array_values(array_filter($results, function($s) use ($pastor) {
                return $s['pastor'] === $pastor;
            }));
        }

        // Enrich with pastor names
        $pastorMap = [];
        foreach ($PASTOR_CATALOG as $p) { $pastorMap[$p['id']] = $p['name']; }
        foreach ($results as &$s) {
            $s['pastor_name'] = $pastorMap[$s['pastor']] ?? 'Unknown';
        }

        echo json_encode([
            'success' => true,
            'count'   => count($results),
            'sermons' => $results,
        ]);
        break;

    // ── Churches ──
    case 'churches':
        echo json_encode([
            'success'  => true,
            'count'    => count($CHURCH_CATALOG),
            'churches' => $CHURCH_CATALOG,
        ]);
        break;

    // ── Translations ──
    case 'translations':
        echo json_encode([
            'success'      => true,
            'count'        => count($BIBLE_TRANSLATIONS),
            'translations' => $BIBLE_TRANSLATIONS,
        ]);
        break;

    // ── BibleGateway link ──
    case 'gateway':
        $ref   = isset($_GET['ref']) ? trim($_GET['ref']) : null;
        $trans = isset($_GET['translation']) ? strtoupper(trim($_GET['translation'])) : 'KJV';
        if (!$ref) {
            echo json_encode(['success' => false, 'error' => 'Missing ref parameter']);
            break;
        }
        echo json_encode([
            'success'     => true,
            'reference'   => $ref,
            'translation' => $trans,
            'url'         => biblegateway_url($ref, $trans),
        ]);
        break;

    // ── Prayer Request ──
    case 'prayer':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'POST required']);
            break;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $request = isset($input['request']) ? trim($input['request']) : '';
        if (empty($request)) {
            echo json_encode(['success' => false, 'error' => 'Prayer request is empty']);
            break;
        }

        // Find comforting verses relevant to their need
        $comfort_verses = array_values(array_filter($SCRIPTURE_CATALOG, function($v) {
            return $v['category'] === 'comfort' || $v['category'] === 'faith';
        }));
        $selected = $comfort_verses[array_rand($comfort_verses)];

        // Assign a pastoral counselor
        $counselors = array_values(array_filter($PASTOR_CATALOG, function($p) {
            return in_array($p['specialty'], ['Comfort & Healing', 'Prayer & Intercession', 'Encouragement & Hope']);
        }));
        $counselor = $counselors[array_rand($counselors)];

        echo json_encode([
            'success'   => true,
            'message'   => 'Your prayer has been received. We are lifting you up before the throne of grace.',
            'prayer_id' => 'pryr-' . bin2hex(random_bytes(6)),
            'verse'     => enrich_verse($selected),
            'counselor' => [
                'name'   => $counselor['name'],
                'avatar' => $counselor['avatar'],
                'title'  => $counselor['title'],
                'message'=> "Dear child of God, I want you to know that you are not alone. The Lord hears your cry. \"{$selected['text']}\" — {$selected['ref']}. Let us pray together.",
            ],
            'timestamp' => gmdate('c'),
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
            'message' => 'Jesus Christ unites every nation, tongue, and people — the Lamb of God, Yeshua, Isa.',
            'names'   => $results,
        ]);
        break;

    // ── Gospel Music (proxy to SSP Gospel API) ──
    case 'gospel':
        echo json_encode([
            'success' => true,
            'message' => 'Gospel music creation powered by SoundStudioPro',
            'api'     => '/api/ssp-gospel.php',
            'actions' => ['tracks', 'genres', 'instruments', 'psalms', 'environments', 'automix', 'create', 'tokens'],
            'info'    => 'Use GSM tokens to create beautiful gospel music. 100 GSM = 1 SSP Gospel Credit.',
        ]);
        break;

    // ── Lineage of Jesus — The Royal Line of Perez ──
    case 'lineage':
        $role = isset($_GET['role']) ? strtolower(trim($_GET['role'])) : null;
        $results = $LINEAGE_OF_JESUS;
        if ($role) {
            $results = array_values(array_filter($results, function($l) use ($role) {
                return strtolower($l['role']) === $role;
            }));
        }
        echo json_encode([
            'success'      => true,
            'count'        => count($results),
            'total_generations' => count($LINEAGE_OF_JESUS),
            'insight'      => $LINEAGE_INSIGHT,
            'lineage'      => $results,
        ]);
        break;

    // ── Donations — World Hunger & Compassion Ministry ──
    case 'donations':
        echo json_encode([
            'success'    => true,
            'count'      => count($DONATION_CAUSES),
            'foundation' => $FOUNDATION,
            'causes'     => $DONATION_CAUSES,
        ]);
        break;

    // ── Donate — Submit a donation ──
    case 'donate':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'POST required']);
            break;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $cause_id = isset($input['cause']) ? trim($input['cause']) : '';
        $amount   = isset($input['amount']) ? floatval($input['amount']) : 0;
        $name     = isset($input['name']) ? trim($input['name']) : 'Anonymous';
        if (empty($cause_id) || $amount <= 0) {
            echo json_encode(['success' => false, 'error' => 'Missing cause or valid amount']);
            break;
        }
        $cause = null;
        foreach ($DONATION_CAUSES as $c) {
            if ($c['id'] === $cause_id) { $cause = $c; break; }
        }
        if (!$cause) {
            echo json_encode(['success' => false, 'error' => 'Cause not found']);
            break;
        }
        echo json_encode([
            'success'      => true,
            'donation_id'  => 'don-' . bin2hex(random_bytes(6)),
            'cause'        => $cause['name'],
            'amount'       => $amount,
            'donor'        => $name,
            'message'      => 'Thank you for your generous gift in the name of Jesus Christ. 100% of your donation goes to ' . $cause['name'] . '.',
            'scripture'    => $cause['scripture'],
            'scripture_text'=> $cause['scripture_text'],
            'foundation'   => $FOUNDATION['name'],
            'timestamp'    => gmdate('c'),
        ]);
        break;

    // ── Classrooms — Whiteboard Teaching Sessions ──
    case 'classrooms':
        $topic = isset($_GET['topic']) ? strtolower(trim($_GET['topic'])) : null;
        $results = $CLASSROOM_SESSIONS;
        if ($topic) {
            $results = array_values(array_filter($results, function($c) use ($topic) {
                return strtolower($c['topic']) === $topic;
            }));
        }
        // Enrich with teacher names
        $pastorMap = [];
        foreach ($PASTOR_CATALOG as $p) { $pastorMap[$p['id']] = $p; }
        foreach ($results as &$c) {
            $teacher = isset($pastorMap[$c['teacher']]) ? $pastorMap[$c['teacher']] : null;
            $c['teacher_name']   = $teacher ? $teacher['name'] : 'Unknown';
            $c['teacher_avatar'] = $teacher ? $teacher['avatar'] : '';
            $c['teacher_traits'] = $teacher && isset($teacher['traits']) ? $teacher['traits'] : ['patient','loving'];
        }
        echo json_encode([
            'success'    => true,
            'count'      => count($results),
            'message'    => 'Our agents are patient, positive, and joyful. They take all the time it takes, with unconditional love and tenderness. Every student is welcomed as family.',
            'classrooms' => $results,
        ]);
        break;

    // ── Foundation — Governance & Mission ──
    case 'foundation':
        echo json_encode([
            'success'    => true,
            'foundation' => $FOUNDATION,
            'causes'     => count($DONATION_CAUSES),
            'board_size' => count($FOUNDATION['board']),
        ]);
        break;

    // ── Brotherhood — Cross-reference to Brotherhood API ──
    case 'brotherhood':
        echo json_encode([
            'success'     => true,
            'message'     => 'The Brotherhood of Jesus Christ — 60 agents, 50 languages, 13 games interconnected',
            'api'         => '/api/brotherhood.php',
            'endpoints'   => [
                'agents'      => '/api/brotherhood.php?action=agents',
                'languages'   => '/api/brotherhood.php?action=languages',
                'activities'  => '/api/brotherhood.php?action=activities',
                'connections' => '/api/brotherhood.php?action=connections',
                'sdk'         => '/api/brotherhood.php?action=sdk',
                'greet'       => '/api/brotherhood.php?action=greet&language=ar',
                'for-game'    => '/api/brotherhood.php?action=for-game&game=sanctuary',
            ],
            'verse'       => 'Go ye therefore, and teach ALL nations, baptizing them in the name of the Father, and of the Son, and of the Holy Ghost — Matthew 28:19',
        ]);
        break;

    // ── Aliases for convenience ──
    case 'names-of-jesus':
        // Alias → names
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
            'message' => 'Jesus Christ unites every nation, tongue, and people — the Lamb of God, Yeshua, Isa.',
            'names'   => $results,
        ]);
        break;

    case 'daily-verse':
        // Alias → daily
        $day_index = (int)(date('z') + date('Y')) % count($SCRIPTURE_CATALOG);
        $daily = enrich_verse($SCRIPTURE_CATALOG[$day_index]);
        echo json_encode([
            'success'     => true,
            'daily_verse' => $daily,
            'date'        => date('Y-m-d'),
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action. Available: health, scriptures, verse, daily, daily-verse, pastors, pastor, sermons, churches, translations, gateway, prayer, names, names-of-jesus, gospel, lineage, donations, donate, classrooms, foundation, brotherhood']);
        break;
}
