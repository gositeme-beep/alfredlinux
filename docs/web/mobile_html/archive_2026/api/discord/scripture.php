<?php
/**
 * GoSiteMe Discord Bot — Scripture & Faith Module
 * ════════════════════════════════════════════════
 * Commands: /verse /devotional /prayer /bible
 * Multi-translation Bible, daily devotionals, prayer wall.
 */

// ─── Curated Scripture Catalog (KJV) ───────────────────────────────────
function getScriptureCatalog(): array {
    return [
        // Salvation & Gospel
        ['ref' => 'John 3:16',      'text' => 'For God so loved the world, that he gave his only begotten Son, that whosoever believeth in him should not perish, but have everlasting life.', 'cat' => 'salvation'],
        ['ref' => 'John 14:6',      'text' => 'Jesus saith unto him, I am the way, the truth, and the life: no man cometh unto the Father, but by me.', 'cat' => 'salvation'],
        ['ref' => 'Romans 10:9',    'text' => 'That if thou shalt confess with thy mouth the Lord Jesus, and shalt believe in thine heart that God hath raised him from the dead, thou shalt be saved.', 'cat' => 'salvation'],
        ['ref' => 'Ephesians 2:8-9','text' => 'For by grace are ye saved through faith; and that not of yourselves: it is the gift of God: Not of works, lest any man should boast.', 'cat' => 'salvation'],

        // Strength & Courage
        ['ref' => 'Joshua 1:9',     'text' => 'Have not I commanded thee? Be strong and of a good courage; be not afraid, neither be thou dismayed: for the LORD thy God is with thee whithersoever thou goest.', 'cat' => 'strength'],
        ['ref' => 'Philippians 4:13','text' => 'I can do all things through Christ which strengtheneth me.', 'cat' => 'strength'],
        ['ref' => 'Isaiah 41:10',   'text' => 'Fear thou not; for I am with thee: be not dismayed; for I am thy God: I will strengthen thee; yea, I will help thee; yea, I will uphold thee with the right hand of my righteousness.', 'cat' => 'strength'],
        ['ref' => 'Deuteronomy 31:6','text' => 'Be strong and of a good courage, fear not, nor be afraid of them: for the LORD thy God, he it is that doth go with thee; he will not fail thee, nor forsake thee.', 'cat' => 'strength'],

        // Peace & Comfort
        ['ref' => 'Philippians 4:6-7','text' => 'Be careful for nothing; but in every thing by prayer and supplication with thanksgiving let your requests be made known unto God. And the peace of God, which passeth all understanding, shall keep your hearts and minds through Christ Jesus.', 'cat' => 'peace'],
        ['ref' => 'Psalm 23:4',     'text' => 'Yea, though I walk through the valley of the shadow of death, I will fear no evil: for thou art with me; thy rod and thy staff they comfort me.', 'cat' => 'peace'],
        ['ref' => 'Matthew 11:28',  'text' => 'Come unto me, all ye that labour and are heavy laden, and I will give you rest.', 'cat' => 'peace'],
        ['ref' => 'John 14:27',     'text' => 'Peace I leave with you, my peace I give unto you: not as the world giveth, give I unto you. Let not your heart be troubled, neither let it be afraid.', 'cat' => 'peace'],

        // Love
        ['ref' => '1 Corinthians 13:4-7','text' => 'Charity suffereth long, and is kind; charity envieth not; charity vaunteth not itself, is not puffed up, Doth not behave itself unseemly, seeketh not her own, is not easily provoked, thinketh no evil; Rejoiceth not in iniquity, but rejoiceth in the truth; Beareth all things, believeth all things, hopeth all things, endureth all things.', 'cat' => 'love'],
        ['ref' => '1 John 4:8',     'text' => 'He that loveth not knoweth not God; for God is love.', 'cat' => 'love'],
        ['ref' => 'Romans 8:38-39', 'text' => 'For I am persuaded, that neither death, nor life, nor angels, nor principalities, nor powers, nor things present, nor things to come, Nor height, nor depth, nor any other creature, shall be able to separate us from the love of God, which is in Christ Jesus our Lord.', 'cat' => 'love'],

        // Wisdom
        ['ref' => 'Proverbs 3:5-6', 'text' => 'Trust in the LORD with all thine heart; and lean not unto thine own understanding. In all thy ways acknowledge him, and he shall direct thy paths.', 'cat' => 'wisdom'],
        ['ref' => 'James 1:5',      'text' => 'If any of you lack wisdom, let him ask of God, that giveth to all men liberally, and upbraideth not; and it shall be given him.', 'cat' => 'wisdom'],
        ['ref' => 'Proverbs 4:7',   'text' => 'Wisdom is the principal thing; therefore get wisdom: and with all thy getting get understanding.', 'cat' => 'wisdom'],

        // Faith
        ['ref' => 'Hebrews 11:1',   'text' => 'Now faith is the substance of things hoped for, the evidence of things not seen.', 'cat' => 'faith'],
        ['ref' => 'Matthew 17:20',  'text' => 'If ye have faith as a grain of mustard seed, ye shall say unto this mountain, Remove hence to yonder place; and it shall remove; and nothing shall be impossible unto you.', 'cat' => 'faith'],
        ['ref' => 'Romans 8:28',    'text' => 'And we know that all things work together for good to them that love God, to them who are the called according to his purpose.', 'cat' => 'faith'],

        // Psalms
        ['ref' => 'Psalm 46:1',     'text' => 'God is our refuge and strength, a very present help in trouble.', 'cat' => 'psalms'],
        ['ref' => 'Psalm 91:1-2',   'text' => 'He that dwelleth in the secret place of the most High shall abide under the shadow of the Almighty. I will say of the LORD, He is my refuge and my fortress: my God; in him will I trust.', 'cat' => 'psalms'],
        ['ref' => 'Psalm 119:105',  'text' => 'Thy word is a lamp unto my feet, and a light unto my path.', 'cat' => 'psalms'],
        ['ref' => 'Psalm 27:1',     'text' => 'The LORD is my light and my salvation; whom shall I fear? the LORD is the strength of my life; of whom shall I be afraid?', 'cat' => 'psalms'],
        ['ref' => 'Psalm 34:18',    'text' => 'The LORD is nigh unto them that are of a broken heart; and saveth such as be of a contrite spirit.', 'cat' => 'psalms'],
    ];
}

function handleVerse($data): void {
    $category = '';
    foreach (($data['data']['options'] ?? []) as $o) {
        if ($o['name'] === 'category') $category = $o['value'];
    }

    $catalog = getScriptureCatalog();

    if ($category) {
        $filtered = array_filter($catalog, fn($v) => $v['cat'] === $category);
        $filtered = array_values($filtered);
        if (empty($filtered)) {
            respondEphemeral("❌ No verses found for category: $category");
            return;
        }
        $verse = $filtered[array_rand($filtered)];
    } else {
        $verse = $catalog[array_rand($catalog)];
    }

    $catEmoji = match($verse['cat']) {
        'salvation' => '✝️', 'strength' => '💪', 'peace' => '☮️', 'love' => '❤️',
        'wisdom' => '🦉', 'faith' => '🕊️', 'psalms' => '🎵', default => '📖',
    };

    $userId = $data['member']['user']['id'] ?? ($data['user']['id'] ?? '');

    respond(null, [embed("$catEmoji {$verse['ref']}", ">>> *{$verse['text']}*\n\n— **King James Version**", 0xDAA520, [
        field('Category', ucfirst($verse['cat']), true),
        field('Translation', 'KJV', true),
    ], [
        'footer' => ['text' => '"Go ye therefore, and teach all nations" — Matthew 28:19'],
    ])], [actionRow(
        btn(1, '📖 Another Verse', 'verse_random'),
        btn(2, '✝️ Salvation', 'verse_salvation'),
        btn(2, '💪 Strength', 'verse_strength'),
        btn(2, '☮️ Peace', 'verse_peace'),
        btn(2, '❤️ Love', 'verse_love')
    )]);
    awardXP($userId, 2);
}

function handleDevotional($data): void {
    $userId   = $data['member']['user']['id'] ?? ($data['user']['id'] ?? '');
    $username = $data['member']['user']['username'] ?? ($data['user']['username'] ?? 'User');

    deferResponse();
    $appId = getenv('DISCORD_APP_ID') ?: '';
    $token = $data['token'] ?? '';

    // Get random verse for the devotional
    $catalog = getScriptureCatalog();
    $verse = $catalog[array_rand($catalog)];

    $devotional = callGroq(
        "You are a compassionate pastor writing a brief daily devotional. Based on the given Bible verse:\n\n1. Open with the verse reference and text\n2. Write a 3-4 paragraph reflection (warm, encouraging, applicable to modern life)\n3. Include a practical 'Today's Action' step\n4. Close with a short prayer\n\nKeep total length under 400 words. Be loving, not preachy. Write in second person (you/your).",
        "Today's verse: {$verse['ref']} — \"{$verse['text']}\"",
        0.8, 800
    );

    editOriginal($appId, $token, '', [embed("🌅 Daily Devotional", $devotional ?: 'Could not generate devotional.', 0xDAA520, [], [
        'footer' => ['text' => "Based on {$verse['ref']} (KJV) • " . date('F j, Y')],
    ])], [actionRow(
        btn(1, '📖 New Devotional', 'devotional_new'),
        btn(2, '🙏 Prayer Wall', 'prayer_wall'),
        btn(2, '📖 Today\'s Verse', 'verse_random')
    )]);
    awardXP($userId, 5);
}

function handlePrayer($data): void {
    $sub = $data['data']['options'][0]['name'] ?? 'request';
    $userId   = $data['member']['user']['id'] ?? ($data['user']['id'] ?? '');
    $username = $data['member']['user']['username'] ?? ($data['user']['username'] ?? 'User');
    $db       = getDiscordDB();
    if (!$db) { respond('❌ Database unavailable.'); return; }

    $db->exec("CREATE TABLE IF NOT EXISTS discord_prayers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        discord_id VARCHAR(32) NOT NULL,
        prayer_text TEXT NOT NULL,
        pray_count INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (discord_id)
    )");

    switch ($sub) {
        case 'request':
            $text = '';
            foreach (($data['data']['options'][0]['options'] ?? []) as $o) {
                if ($o['name'] === 'text') $text = $o['value'];
            }
            if (!$text) { respondEphemeral("❌ Please enter your prayer request."); return; }

            $stmt = $db->prepare("INSERT INTO discord_prayers (discord_id, prayer_text) VALUES (?, ?)");
            $stmt->execute([$userId, $text]);
            $prayerId = $db->lastInsertId();

            respond(null, [embed("🙏 Prayer Request #{$prayerId}", ">>> $text\n\n*Shared by* **$username**\n*Press the button below to pray for this request.*", 0xDAA520, [], [
                'footer' => ['text' => '"Again I say unto you, That if two of you shall agree on earth as touching any thing that they shall ask, it shall be done" — Matthew 18:19'],
            ])], [actionRow(
                btn(1, '🙏 Pray (0)', "pray_for_$prayerId"),
                btn(2, '📋 Prayer Wall', 'prayer_wall')
            )]);
            awardXP($userId, 5);
            break;

        case 'wall':
            $stmt = $db->query("SELECT id, discord_id, prayer_text, pray_count, created_at FROM discord_prayers ORDER BY created_at DESC LIMIT 5");
            $rows = $stmt->fetchAll();

            if (empty($rows)) {
                respond(null, [embed("🙏 Prayer Wall", "No prayer requests yet. Use `/prayer request` to share yours.", 0xDAA520)]);
                return;
            }

            $lines = [];
            foreach ($rows as $r) {
                $time = '<t:' . strtotime($r['created_at']) . ':R>';
                $text = truncate($r['prayer_text'], 100);
                $lines[] = "**#{$r['id']}** — $text\n🙏 {$r['pray_count']} prayers · $time";
            }

            respond(null, [embed("🙏 Prayer Wall", implode("\n\n", $lines), 0xDAA520, [
                field('Total Requests', (string)count($rows), true),
            ], [
                'footer' => ['text' => 'Press a prayer ID button to pray for that request'],
            ])]);
            break;

        default:
            respondEphemeral("Use `/prayer request` or `/prayer wall`.");
    }
}

function handleBible($data): void {
    $query = '';
    foreach (($data['data']['options'] ?? []) as $o) {
        if ($o['name'] === 'search') $query = $o['value'];
    }

    if (!$query) { respondEphemeral("❌ Please enter a search term."); return; }

    $userId = $data['member']['user']['id'] ?? ($data['user']['id'] ?? '');
    $catalog = getScriptureCatalog();

    // Search in text and references
    $queryLower = strtolower($query);
    $matches = [];
    foreach ($catalog as $v) {
        if (stripos($v['ref'], $query) !== false || stripos($v['text'], $query) !== false) {
            $matches[] = $v;
        }
    }

    if (empty($matches)) {
        // Use AI to find related verses
        deferResponse();
        $appId = getenv('DISCORD_APP_ID') ?: '';
        $token = $data['token'] ?? '';

        $result = callGroq(
            "The user is searching the Bible for a topic. Provide 3 relevant KJV Bible verses with:\n- Book Chapter:Verse reference\n- The full verse text (KJV)\n- A brief 1-sentence explanation of relevance\n\nFormat each as:\n📖 **Reference**\n> Verse text\n*Explanation*",
            "Bible search: $query",
            0.5, 800
        );

        editOriginal($appId, $token, '', [embed("📖 Bible Search: \"$query\"", $result ?: 'No results found.', 0xDAA520, [], [
            'footer' => ['text' => 'AI-assisted Bible search • KJV'],
        ])], [actionRow(
            btn(2, '📖 Random Verse', 'verse_random'),
            btn(2, '🌅 Devotional', 'devotional_new')
        )]);
    } else {
        $lines = [];
        foreach (array_slice($matches, 0, 5) as $v) {
            $catEmoji = match($v['cat']) {
                'salvation' => '✝️', 'strength' => '💪', 'peace' => '☮️', 'love' => '❤️',
                'wisdom' => '🦉', 'faith' => '🕊️', 'psalms' => '🎵', default => '📖',
            };
            $lines[] = "$catEmoji **{$v['ref']}**\n>>> *{$v['text']}*";
        }

        respond(null, [embed("📖 Bible Search: \"$query\"", implode("\n\n", $lines), 0xDAA520, [
            field('Results', count($matches) . ' found', true),
            field('Translation', 'KJV', true),
        ])], [actionRow(
            btn(2, '📖 Random Verse', 'verse_random'),
            btn(2, '🌅 Devotional', 'devotional_new')
        )]);
    }
    awardXP($userId, 3);
}
