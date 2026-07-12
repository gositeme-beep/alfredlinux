<?php
/**
 * GoSiteMe Discord Bot — Social Games Module
 * Commands: /confess, /wouldyourather, /compatibility, /tierlist
 * Interactive social and party game commands
 */

namespace GoSiteMe\Discord;
require_once __DIR__ . '/core.php';

// ─── /confess ──────────────────────────────────────────────────────────
function handleConfess(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $confession = '';
    foreach ($opts as $o) { if ($o['name'] === 'text') $confession = trim($o['value']); }
    if (!$confession) { respondEphemeral('❌ Write your confession!'); return; }

    $userId = $data['member']['user']['id'];
    $username = $data['member']['user']['username'];
    getOrCreateUser($userId, $username);

    // Store confession count
    $pdo = getDiscordDB();
    $confNum = 1;
    if ($pdo) {
        $stmt = $pdo->query("SELECT COALESCE(MAX(CAST(SUBSTRING_INDEX(reason, '#', -1) AS UNSIGNED)), 0) + 1 FROM discord_economy WHERE entry_type = 'confession'");
        $confNum = (int)$stmt->fetchColumn() ?: 1;
        $pdo->prepare("INSERT INTO discord_economy (discord_id, entry_type, amount, reason) VALUES (?, 'confession', 0, ?)")
            ->execute([$userId, "Confession #$confNum"]);
    }

    // Public anonymous embed
    respond(null, [embed(
        "🕵️ Anonymous Confession #$confNum",
        $confession,
        0x2C3E50,
        [],
        ['footer' => ['text' => 'Send your own with /confess']]
    )], [actionRow(
        btn(2, '❤️ Relate', "confess_react_heart_$confNum"),
        btn(2, '😂 Funny', "confess_react_laugh_$confNum"),
        btn(2, '😮 Shocked', "confess_react_shock_$confNum"),
        btn(2, '🤗 Support', "confess_react_hug_$confNum")
    )]);

    awardXP($userId, 3);
}

// ─── /wouldyourather ───────────────────────────────────────────────────
function handleWouldyourather(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $category = 'random';
    foreach ($opts as $o) { if ($o['name'] === 'category') $category = $o['value']; }

    $userId = $data['member']['user']['id'];
    $username = $data['member']['user']['username'];
    $appId = $data['application_id'];
    $token = $data['token'];

    deferResponse();
    getOrCreateUser($userId, $username);

    $categories = [
        'random' => 'Generate a creative, thought-provoking "Would You Rather" dilemma.',
        'funny' => 'Generate a hilarious, absurd "Would You Rather" that will make people laugh.',
        'deep' => 'Generate a deep philosophical "Would You Rather" that challenges moral values.',
        'gross' => 'Generate a disgusting but funny "Would You Rather" (PG-13, nothing sexual).',
        'superpowers' => 'Generate a "Would You Rather" about superpowers or fantasy abilities.',
        'money' => 'Generate a "Would You Rather" about money, wealth, and financial dilemmas.',
        'impossible' => 'Generate an impossibly difficult "Would You Rather" where both options are equally bad/good.',
    ];

    $catPrompt = $categories[$category] ?? $categories['random'];

    $result = callGroq(
        "You create viral 'Would You Rather' questions. $catPrompt\nRespond in EXACTLY this format:\nOPTION A: [first option]\nOPTION B: [second option]\nTWIST: [a surprising twist or consequence that makes it harder to choose]\n\nMake both options roughly equal in appeal/horror. Be creative and specific.",
        "Category: $category. Create one new unique would you rather question.",
        0.95, 300
    );

    if (!$result) {
        editOriginal($appId, $token, '❌ Couldn\'t generate a dilemma. Try again.');
        return;
    }

    // Parse the response
    preg_match('/OPTION A:\s*(.+)/i', $result, $matchA);
    preg_match('/OPTION B:\s*(.+)/i', $result, $matchB);
    preg_match('/TWIST:\s*(.+)/i', $result, $matchTwist);

    $optA = $matchA[1] ?? 'Option A';
    $optB = $matchB[1] ?? 'Option B';
    $twist = $matchTwist[1] ?? '';

    $wyrId = substr(md5(time() . $userId), 0, 8);

    $desc = "**🅰️ Option A:** $optA\n\n**🅱️ Option B:** $optB";
    if ($twist) $desc .= "\n\n> 🌀 **Twist:** $twist";

    followUp($appId, $token, '', [embed(
        "🤔 Would You Rather?",
        $desc,
        0xF39C12,
        [field('Category', ucfirst($category), true), field('Votes', 'Vote below! 👇', true)],
        ['footer' => ['text' => 'Click to vote!']]
    )], [actionRow(
        btn(1, '🅰️ Option A', "wyr_a_$wyrId"),
        btn(4, '🅱️ Option B', "wyr_b_$wyrId"),
        btn(2, '🔄 New Question', 'wyr_new'),
        btn(2, '💡 Explain', "wyr_explain_$wyrId")
    )]);

    awardXP($userId, 3, $appId, $token);
}

// ─── /compatibility ────────────────────────────────────────────────────
function handleCompatibility(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $targetId = '';
    foreach ($opts as $o) { if ($o['name'] === 'user') $targetId = $o['value']; }
    if (!$targetId) { respondEphemeral('❌ Mention a user to check compatibility with!'); return; }

    $userId = $data['member']['user']['id'];
    $username = $data['member']['user']['username'];
    $appId = $data['application_id'];
    $token = $data['token'];

    if ($targetId === $userId) { respondEphemeral('😅 You\'re 100% compatible with yourself!'); return; }

    deferResponse();
    $user1 = getOrCreateUser($userId, $username);
    $user2 = getOrCreateUser($targetId, 'User');

    // Deterministic score based on both user IDs (so it's consistent)
    $seed = crc32($userId < $targetId ? "$userId:$targetId" : "$targetId:$userId");
    mt_srand($seed);
    $score = mt_rand(1, 100);
    mt_srand(); // Reset

    // Categories with deterministic variation
    $cats = ['💕 Romance' => mt_rand(1,100), '🤝 Friendship' => mt_rand(1,100),
             '🎮 Gaming' => mt_rand(1,100), '💼 Business' => mt_rand(1,100),
             '🧠 Intellectual' => mt_rand(1,100), '😂 Humor' => mt_rand(1,100)];

    // Reset seed for categories
    mt_srand($seed + 42);
    foreach ($cats as $k => &$v) { $v = mt_rand(1, 100); }
    mt_srand();

    // Emoji rating
    $emoji = match(true) {
        $score >= 90 => '💖',
        $score >= 75 => '❤️',
        $score >= 60 => '💛',
        $score >= 40 => '🧡',
        $score >= 20 => '💔',
        default => '☠️',
    };

    $verdict = match(true) {
        $score >= 90 => 'Soulmates! A match made in heaven.',
        $score >= 75 => 'Highly compatible! Great chemistry.',
        $score >= 60 => 'Pretty good match. Worth pursuing!',
        $score >= 40 => 'Some compatibility. Could work with effort.',
        $score >= 20 => 'Opposites... might attract? Or not.',
        default => 'Run. Just run. 🏃',
    };

    $bar = str_repeat('█', intdiv($score, 10)) . str_repeat('░', 10 - intdiv($score, 10));

    $catFields = [];
    foreach ($cats as $name => $val) {
        $b = str_repeat('█', intdiv($val, 20)) . str_repeat('░', 5 - intdiv($val, 20));
        $catFields[] = field($name, "$b $val%", true);
    }

    followUp($appId, $token, '', [embed(
        "$emoji Compatibility Report",
        "<@$userId> × <@$targetId>\n\n$bar **$score%**\n\n> $verdict",
        $score >= 60 ? 0xE91E63 : 0x95A5A6,
        $catFields,
        ['footer' => ['text' => 'GoSiteMe Compatibility Engine™']]
    )], [actionRow(
        btn(2, '🔄 Try Someone Else', 'compat_new'),
        btn(2, '💕 Ship Name', "compat_ship_$targetId")
    )]);

    awardXP($userId, 3, $appId, $token);
}

// ─── /tierlist ─────────────────────────────────────────────────────────
function handleTierlist(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $topic = ''; $items = '';
    foreach ($opts as $o) {
        if ($o['name'] === 'topic') $topic = trim($o['value']);
        if ($o['name'] === 'items') $items = trim($o['value']);
    }
    if (!$topic) { respondEphemeral('❌ What should the tier list be about?'); return; }

    $userId = $data['member']['user']['id'];
    $username = $data['member']['user']['username'];
    $appId = $data['application_id'];
    $token = $data['token'];

    deferResponse();
    getOrCreateUser($userId, $username);

    $itemPrompt = $items ? "Rank THESE items: $items" : "Come up with 15-20 well-known items for this category and rank them.";

    $result = callGroq(
        "You are a tier list expert. Create a definitive tier list.\n$itemPrompt\n\nFormat EXACTLY as:\n🏆 **S TIER** (Legendary)\n• item1 — one-line justification\n• item2 — justification\n\n🥇 **A TIER** (Excellent)\n• items...\n\n🥈 **B TIER** (Good)\n• items...\n\n🥉 **C TIER** (Average)\n• items...\n\n💀 **D TIER** (Below Average)\n• items...\n\n🗑️ **F TIER** (Trash)\n• items...\n\nBe opinionated and entertaining. Give hot takes. Be controversial but justify your choices.",
        "Tier list topic: $topic",
        0.9, 1800
    );

    if (!$result) {
        editOriginal($appId, $token, '❌ Tier list generation failed.');
        return;
    }

    followUp($appId, $token, '', [embed(
        "📊 Tier List: $topic",
        truncate($result, 4000),
        0xFFD700,
        [],
        ['footer' => ['text' => "Ranked by AI | Requested by $username"]]
    )], [actionRow(
        btn(2, '🔄 Re-Rank', 'tier_rerank'),
        btn(2, '🌶️ Spicier Takes', 'tier_spicy'),
        btn(2, '📊 New Topic', 'tier_new')
    )]);

    awardXP($userId, 5, $appId, $token);
}
