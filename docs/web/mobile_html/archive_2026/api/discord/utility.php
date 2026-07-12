<?php
/**
 * GoSiteMe Discord Bot — Utility Module
 * Commands: /timestamp, /avatar, /banner, /math, /define
 * Handy utility commands for Discord power users
 */

namespace GoSiteMe\Discord;
require_once __DIR__ . '/core.php';

// ─── /timestamp ────────────────────────────────────────────────────────
function handleTimestamp(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $dateStr = ''; $format = 'all';
    foreach ($opts as $o) {
        if ($o['name'] === 'datetime') $dateStr = trim($o['value']);
        if ($o['name'] === 'format') $format = $o['value'];
    }

    $userId = $data['member']['user']['id'];
    $username = $data['member']['user']['username'];
    getOrCreateUser($userId, $username);

    $ts = $dateStr ? strtotime($dateStr) : time();
    if ($ts === false) {
        respondEphemeral('❌ Could not parse date. Try formats like: `tomorrow 3pm`, `2026-12-25`, `next friday`, `+2 hours`');
        return;
    }

    $formats = [
        't' => ['Short Time', date('g:i A', $ts)],
        'T' => ['Long Time', date('g:i:s A', $ts)],
        'd' => ['Short Date', date('m/d/Y', $ts)],
        'D' => ['Long Date', date('F j, Y', $ts)],
        'f' => ['Short DateTime', date('F j, Y g:i A', $ts)],
        'F' => ['Long DateTime', date('l, F j, Y g:i A', $ts)],
        'R' => ['Relative', 'time ago/from now'],
    ];

    if ($format !== 'all' && isset($formats[$format])) {
        respond("**Discord Timestamp:**\n`<t:$ts:$format>` → <t:$ts:$format>");
        return;
    }

    $lines = ["**Unix Timestamp:** `$ts`\n"];
    foreach ($formats as $code => [$name, $preview]) {
        $lines[] = "**$name** (`$code`): `<t:$ts:$code>` → <t:$ts:$code>";
    }

    respond(null, [embed(
        "🕐 Discord Timestamps",
        implode("\n", $lines) . "\n\nCopy any `<t:...>` format to use in your messages!",
        0x3498DB,
        [field('Input', $dateStr ?: 'now', true), field('Unix', (string)$ts, true)]
    )]);

    awardXP($userId, 2);
}

// ─── /avatar ───────────────────────────────────────────────────────────
function handleAvatar(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $targetId = ''; $size = 1024;
    foreach ($opts as $o) {
        if ($o['name'] === 'user') $targetId = $o['value'];
        if ($o['name'] === 'size') $size = (int)$o['value'];
    }

    $userId = $data['member']['user']['id'];
    $username = $data['member']['user']['username'];
    getOrCreateUser($userId, $username);

    $lookupId = $targetId ?: $userId;

    // Get user info from Discord API
    $userInfo = discordApi("/users/$lookupId");
    if (!$userInfo) {
        respondEphemeral('❌ Could not fetch user info.');
        return;
    }

    $uname = $userInfo['username'] ?? 'Unknown';
    $hash = $userInfo['avatar'] ?? null;
    $discrim = $userInfo['discriminator'] ?? '0';

    if (!$hash) {
        $defaultIndex = ($discrim === '0')
            ? (((int)$lookupId >> 22) % 6)
            : ((int)$discrim % 5);
        $avatarUrl = "https://cdn.discordapp.com/embed/avatars/$defaultIndex.png";
    } else {
        $ext = str_starts_with($hash, 'a_') ? 'gif' : 'png';
        $avatarUrl = "https://cdn.discordapp.com/avatars/$lookupId/$hash.$ext?size=$size";
    }

    // Also check for guild avatar
    $guildAvatar = null;
    $guildId = $data['guild_id'] ?? '';
    if ($guildId) {
        $member = discordApi("/guilds/$guildId/members/$lookupId");
        if ($member && !empty($member['avatar'])) {
            $gHash = $member['avatar'];
            $gExt = str_starts_with($gHash, 'a_') ? 'gif' : 'png';
            $guildAvatar = "https://cdn.discordapp.com/guilds/$guildId/users/$lookupId/avatars/$gHash.$gExt?size=$size";
        }
    }

    // Banner
    $bannerUrl = null;
    if (!empty($userInfo['banner'])) {
        $bHash = $userInfo['banner'];
        $bExt = str_starts_with($bHash, 'a_') ? 'gif' : 'png';
        $bannerUrl = "https://cdn.discordapp.com/banners/$lookupId/$bHash.$bExt?size=1024";
    }

    $fields = [
        field('User', "<@$lookupId>", true),
        field('ID', "`$lookupId`", true),
        field('Size', "{$size}px", true),
    ];

    if ($guildAvatar) $fields[] = field('Server Avatar', "[Link]($guildAvatar)", true);
    if ($bannerUrl) $fields[] = field('Banner', "[Link]($bannerUrl)", true);

    $embeds = [embed(
        "🖼️ Avatar — $uname",
        '',
        $userInfo['accent_color'] ?? 0x5865F2,
        $fields,
        ['image' => ['url' => $avatarUrl]]
    )];

    respond(null, $embeds, [actionRow(
        btn(5, '📥 Full Size', $avatarUrl),
        ...(($guildAvatar) ? [btn(5, '🏠 Server Avatar', $guildAvatar)] : []),
        ...(($bannerUrl) ? [btn(5, '🎨 Banner', $bannerUrl)] : [])
    )]);
}

// ─── /banner ───────────────────────────────────────────────────────────
function handleBanner(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $text = '';
    $style = 'block';
    foreach ($opts as $o) {
        if ($o['name'] === 'text') $text = trim($o['value']);
        if ($o['name'] === 'style') $style = $o['value'];
    }
    if (!$text) { respondEphemeral('❌ Provide text for the banner.'); return; }

    $userId = $data['member']['user']['id'];
    $username = $data['member']['user']['username'];
    getOrCreateUser($userId, $username);

    $text = strtoupper(substr($text, 0, 20));

    $letters = [
        'A' => ["  █  ", " █ █ ", "█████", "█   █", "█   █"],
        'B' => ["████ ", "█   █", "████ ", "█   █", "████ "],
        'C' => [" ████", "█    ", "█    ", "█    ", " ████"],
        'D' => ["████ ", "█   █", "█   █", "█   █", "████ "],
        'E' => ["█████", "█    ", "████ ", "█    ", "█████"],
        'F' => ["█████", "█    ", "████ ", "█    ", "█    "],
        'G' => [" ████", "█    ", "█  ██", "█   █", " ████"],
        'H' => ["█   █", "█   █", "█████", "█   █", "█   █"],
        'I' => ["█████", "  █  ", "  █  ", "  █  ", "█████"],
        'J' => ["█████", "   █ ", "   █ ", "█  █ ", " ██  "],
        'K' => ["█  █ ", "█ █  ", "██   ", "█ █  ", "█  █ "],
        'L' => ["█    ", "█    ", "█    ", "█    ", "█████"],
        'M' => ["█   █", "██ ██", "█ █ █", "█   █", "█   █"],
        'N' => ["█   █", "██  █", "█ █ █", "█  ██", "█   █"],
        'O' => [" ███ ", "█   █", "█   █", "█   █", " ███ "],
        'P' => ["████ ", "█   █", "████ ", "█    ", "█    "],
        'Q' => [" ███ ", "█   █", "█ █ █", "█  █ ", " ██ █"],
        'R' => ["████ ", "█   █", "████ ", "█  █ ", "█   █"],
        'S' => [" ████", "█    ", " ███ ", "    █", "████ "],
        'T' => ["█████", "  █  ", "  █  ", "  █  ", "  █  "],
        'U' => ["█   █", "█   █", "█   █", "█   █", " ███ "],
        'V' => ["█   █", "█   █", " █ █ ", " █ █ ", "  █  "],
        'W' => ["█   █", "█   █", "█ █ █", "██ ██", "█   █"],
        'X' => ["█   █", " █ █ ", "  █  ", " █ █ ", "█   █"],
        'Y' => ["█   █", " █ █ ", "  █  ", "  █  ", "  █  "],
        'Z' => ["█████", "   █ ", "  █  ", " █   ", "█████"],
        '0' => [" ███ ", "█  ██", "█ █ █", "██  █", " ███ "],
        '1' => [" █   ", "██   ", " █   ", " █   ", "███  "],
        '2' => [" ███ ", "█   █", "  ██ ", " █   ", "█████"],
        '3' => ["████ ", "    █", " ███ ", "    █", "████ "],
        '4' => ["█  █ ", "█  █ ", "█████", "   █ ", "   █ "],
        '5' => ["█████", "█    ", "████ ", "    █", "████ "],
        '6' => [" ███ ", "█    ", "████ ", "█   █", " ███ "],
        '7' => ["█████", "   █ ", "  █  ", " █   ", "█    "],
        '8' => [" ███ ", "█   █", " ███ ", "█   █", " ███ "],
        '9' => [" ███ ", "█   █", " ████", "    █", " ███ "],
        ' ' => ["     ", "     ", "     ", "     ", "     "],
        '!' => ["  █  ", "  █  ", "  █  ", "     ", "  █  "],
        '?' => [" ███ ", "█   █", "  ██ ", "     ", "  █  "],
    ];

    $output = [];
    for ($row = 0; $row < 5; $row++) {
        $line = '';
        foreach (str_split($text) as $char) {
            $charData = $letters[$char] ?? $letters[' '];
            if ($style === 'dots') {
                $line .= str_replace(['█', ' '], ['⬜', '⬛'], $charData[$row]) . ' ';
            } else {
                $line .= $charData[$row] . ' ';
            }
        }
        $output[] = $line;
    }

    $banner = "```\n" . implode("\n", $output) . "\n```";
    if (strlen($banner) > 1990) {
        respondEphemeral("❌ Text too long for banner. Try fewer characters.");
        return;
    }

    respond($banner);
    awardXP($userId, 2);
}

// ─── /math ─────────────────────────────────────────────────────────────
function handleMath(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $expression = '';
    foreach ($opts as $o) { if ($o['name'] === 'expression') $expression = trim($o['value']); }
    if (!$expression) { respondEphemeral('❌ Provide a math expression.'); return; }

    $userId = $data['member']['user']['id'];
    $username = $data['member']['user']['username'];
    $appId = $data['application_id'];
    $token = $data['token'];

    deferResponse();
    getOrCreateUser($userId, $username);

    $result = callGroq(
        "You are a world-class mathematician and math tutor. Solve the following math problem step by step.\n\nRules:\n1. Show your work clearly with numbered steps\n2. Use Discord markdown (bold, code blocks for equations)\n3. Give the final answer clearly marked with **Answer:**\n4. If it's a word problem, identify the variables first\n5. For complex problems, explain the approach before solving\n6. For calculus: show derivatives/integrals step by step\n7. For statistics: show formulas used\n8. For algebra: show simplification steps\n\nIf the expression is simple arithmetic, just give the answer concisely.",
        "Problem: $expression",
        0.2, 1500
    );

    if (!$result) {
        editOriginal($appId, $token, '❌ Could not solve. Try rephrasing.');
        return;
    }

    followUp($appId, $token, '', [embed(
        "🔢 Math Solver",
        truncate($result, 4000),
        0x2196F3,
        [field('Expression', "`$expression`", false)],
        ['footer' => ['text' => 'GoSiteMe Math Engine']]
    )], [actionRow(
        btn(2, '📊 Graph', 'math_graph'),
        btn(2, '📝 Explain More', 'math_explain'),
        btn(2, '🔢 New Problem', 'math_new')
    )]);

    awardXP($userId, 5, $appId, $token);
}

// ─── /define ───────────────────────────────────────────────────────────
function handleDefine(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $word = '';
    foreach ($opts as $o) { if ($o['name'] === 'word') $word = trim($o['value']); }
    if (!$word) { respondEphemeral('❌ What word to define?'); return; }

    $userId = $data['member']['user']['id'];
    $username = $data['member']['user']['username'];
    $appId = $data['application_id'];
    $token = $data['token'];

    deferResponse();
    getOrCreateUser($userId, $username);

    // Free Dictionary API
    $apiWord = rawurlencode(strtolower($word));
    $raw = httpGet("https://api.dictionaryapi.dev/api/v2/entries/en/$apiWord");
    $data_dict = $raw ? json_decode($raw, true) : null;

    if ($data_dict && is_array($data_dict) && !isset($data_dict['title'])) {
        $entry = $data_dict[0];
        $phonetic = '';
        foreach ($entry['phonetics'] ?? [] as $p) {
            if (!empty($p['text'])) { $phonetic = $p['text']; break; }
        }

        $fields = [];
        if ($phonetic) $fields[] = field('Pronunciation', $phonetic, true);

        $origin = $entry['origin'] ?? '';
        if ($origin) $fields[] = field('Origin', truncate($origin, 100), true);

        $desc = '';
        foreach (array_slice($entry['meanings'] ?? [], 0, 4) as $meaning) {
            $pos = $meaning['partOfSpeech'] ?? '';
            $desc .= "\n**$pos**\n";
            foreach (array_slice($meaning['definitions'] ?? [], 0, 3) as $i => $def) {
                $num = $i + 1;
                $desc .= "$num. {$def['definition']}\n";
                if (!empty($def['example'])) $desc .= "> *\"{$def['example']}\"*\n";
            }
            $syns = array_slice($meaning['synonyms'] ?? [], 0, 5);
            if ($syns) $desc .= "Synonyms: " . implode(', ', $syns) . "\n";
        }

        followUp($appId, $token, '', [embed(
            "📖 $word",
            truncate($desc, 4000),
            0x8E24AA,
            $fields,
            ['footer' => ['text' => 'Free Dictionary API']]
        )], [actionRow(
            btn(2, '🔄 Another', 'define_new'),
            btn(5, '📚 More Info', "https://en.wiktionary.org/wiki/$apiWord")
        )]);
    } else {
        // Fallback to AI definition
        $result = callGroq(
            "You are a dictionary. Define the word comprehensively:\n1. Pronunciation (IPA if possible)\n2. Part of speech\n3. All major definitions (numbered)\n4. Example sentences for each\n5. Etymology/origin\n6. Synonyms and antonyms\n\nUse Discord markdown.",
            "Define: $word",
            0.3, 800
        );

        followUp($appId, $token, '', [embed(
            "📖 $word",
            truncate($result ?: "No definition found for **$word**.", 4000),
            0x8E24AA,
            [],
            ['footer' => ['text' => 'AI Definition']]
        )]);
    }

    awardXP($userId, 3, $appId, $token);
}
