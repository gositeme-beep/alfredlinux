<?php
/**
 * GoSiteMe Discord Bot — Social Module
 * ═════════════════════════════════════
 * /serverinfo — Server statistics & info
 * /userinfo   — Detailed user information
 * /afk        — AFK status system
 * /birthday   — Birthday tracking & today's birthdays
 * /quote      — AI-generated inspirational quotes
 * /horoscope  — Daily AI horoscope
 * /todo       — Personal task management
 */

function handleServerinfo(array $data): void {
    $guildId = $data['guild_id'] ?? '';
    if (!$guildId) { respond("This command can only be used in a server."); return; }

    deferResponse();

    $appId = $data['application_id'] ?? '';
    $token = $data['token'] ?? '';

    $guild = discordApi("/guilds/$guildId?with_counts=true");
    if (!$guild) { followUp($appId, $token, '❌ Failed to fetch server info.'); return; }

    $name = $guild['name'] ?? 'Unknown';
    $ownerId = $guild['owner_id'] ?? '';
    $memberCount = $guild['approximate_member_count'] ?? '?';
    $onlineCount = $guild['approximate_presence_count'] ?? '?';
    $roles = count($guild['roles'] ?? []);
    $emojis = count($guild['emojis'] ?? []);
    $boostLevel = $guild['premium_tier'] ?? 0;
    $boosts = $guild['premium_subscription_count'] ?? 0;
    $created = $guild['id'] ? date('M j, Y', ((int)$guild['id'] >> 22) / 1000 + 1420070400) : 'Unknown';
    $verificationLevels = ['None', 'Low', 'Medium', 'High', 'Very High'];
    $verification = $verificationLevels[$guild['verification_level'] ?? 0] ?? 'Unknown';
    $icon = isset($guild['icon']) ? "https://cdn.discordapp.com/icons/$guildId/{$guild['icon']}.png?size=256" : '';
    $banner = isset($guild['banner']) ? "https://cdn.discordapp.com/banners/$guildId/{$guild['banner']}.png?size=512" : '';
    $features = $guild['features'] ?? [];

    $boostBar = str_repeat('🟪', min($boostLevel, 3)) . str_repeat('⬜', 3 - min($boostLevel, 3));

    $featureStr = '';
    $featureMap = [
        'COMMUNITY' => '🏘️ Community', 'DISCOVERABLE' => '🔍 Discoverable',
        'PARTNERED' => '🤝 Partnered', 'VERIFIED' => '✅ Verified',
        'VANITY_URL' => '🔗 Vanity URL', 'ANIMATED_ICON' => '🎭 Animated Icon',
        'BANNER' => '🖼️ Banner', 'ROLE_ICONS' => '🎨 Role Icons',
    ];
    foreach ($features as $f) {
        if (isset($featureMap[$f])) $featureStr .= $featureMap[$f] . "\n";
    }

    $pdo = getDiscordDB();
    $botStats = '';
    if ($pdo) {
        $userCount = $pdo->prepare("SELECT COUNT(*) FROM discord_users");
        $userCount->execute();
        $gameCount = $pdo->prepare("SELECT COUNT(*) FROM discord_games");
        $gameCount->execute();
        $botStats = "\n**Bot Stats:** {$userCount->fetchColumn()} registered users, {$gameCount->fetchColumn()} games played";
    }

    $emb = embed("📊 Server Info — $name", $botStats, 0x5865F2, [
        field('👥 Members', "$memberCount total\n$onlineCount online", true),
        field('👑 Owner', "<@$ownerId>", true),
        field('📅 Created', $created, true),
        field('🔒 Verification', $verification, true),
        field('🚀 Boosts', "$boosts ($boostBar Lvl $boostLevel)", true),
        field('😀 Emojis', (string)$emojis, true),
        field('📁 Roles', (string)$roles, true),
    ]);
    if ($icon) $emb['thumbnail'] = ['url' => $icon];
    if ($banner) $emb['image'] = ['url' => $banner];
    if ($featureStr) $emb['fields'][] = field('✨ Features', $featureStr, false);

    followUp($appId, $token, '', [$emb]);
}


function handleUserinfo(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $targetUser = null;
    foreach ($opts as $o) { if ($o['name'] === 'user') $targetUser = $o['value']; }

    $userId = $targetUser ?? ($data['member']['user']['id'] ?? '0');
    $guildId = $data['guild_id'] ?? '';

    deferResponse();

    $appId = $data['application_id'] ?? '';
    $token = $data['token'] ?? '';

    // Get guild member info
    $member = discordApi("/guilds/$guildId/members/$userId");
    if (!$member) { followUp($appId, $token, '❌ User not found.'); return; }

    $user = $member['user'] ?? [];
    $username = $user['username'] ?? 'Unknown';
    $displayName = $user['global_name'] ?? $username;
    $discriminator = $user['discriminator'] ?? '0';
    $bot = ($user['bot'] ?? false) ? '🤖 Bot' : '👤 Human';
    $created = date('M j, Y', ((int)$userId >> 22) / 1000 + 1420070400);
    $joined = isset($member['joined_at']) ? date('M j, Y', strtotime($member['joined_at'])) : 'Unknown';
    $nick = $member['nick'] ?? 'None';
    $roles = $member['roles'] ?? [];
    $roleStr = empty($roles) ? '@everyone' : implode(', ', array_map(fn($r) => "<@&$r>", array_slice($roles, 0, 10)));
    if (count($roles) > 10) $roleStr .= ' +' . (count($roles) - 10) . ' more';

    $avatar = isset($user['avatar'])
        ? "https://cdn.discordapp.com/avatars/$userId/{$user['avatar']}.png?size=256"
        : "https://cdn.discordapp.com/embed/avatars/" . ((int)$userId >> 22) % 6 . ".png";

    $boosting = isset($member['premium_since']) ? 'Since ' . date('M j, Y', strtotime($member['premium_since'])) : 'Not boosting';

    $fields = [
        field('📛 Username', "$username" . ($discriminator !== '0' ? "#$discriminator" : ''), true),
        field('🏷️ Display Name', $displayName, true),
        field('🆔 ID', "`$userId`", true),
        field('🤖 Type', $bot, true),
        field('📅 Account Created', $created, true),
        field('📥 Joined Server', $joined, true),
        field('📝 Nickname', $nick, true),
        field('🚀 Boosting', $boosting, true),
        field("🎭 Roles (" . count($roles) . ")", $roleStr, false),
    ];

    // Bot profile data
    $botUser = getOrCreateUser($userId, $username);
    $level = calcLevel($botUser['xp']);
    $fields[] = field('⬆️ Level', (string)$level . ' — ' . levelTitle($level), true);
    $fields[] = field('💰 KGD', number_format($botUser['kgd_balance']), true);
    $fields[] = field('♟️ ELO', (string)$botUser['elo_chess'], true);

    $emb = embed("ℹ️ User Info — $displayName", '', 0x5865F2, $fields);
    $emb['thumbnail'] = ['url' => $avatar];

    followUp($appId, $token, '', [$emb]);
}


function handleAfk(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $message = 'AFK';
    foreach ($opts as $o) { if ($o['name'] === 'message') $message = $o['value']; }

    $userId = $data['member']['user']['id'] ?? '0';
    $guildId = $data['guild_id'] ?? '';
    $globalName = $data['member']['user']['global_name'] ?? $data['member']['user']['username'] ?? 'User';
    $pdo = getDiscordDB();
    if (!$pdo) { respond("⚠️ Database unavailable."); return; }

    $message = substr(strip_tags($message), 0, 200);

    $pdo->prepare("INSERT INTO discord_afk (user_id, guild_id, message, set_at) VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE message = ?, set_at = NOW()")
        ->execute([$userId, $guildId, $message, $message]);

    respond(null, [embed("💤 AFK Set", "**$globalName** is now AFK: $message\n\nI'll let people know when they mention you.", 0x95A5A6)]);
}


function handleBirthday(array $data): void {
    $subCmd = getSubcommand($data);
    $opts = getSubOptions($data);
    $userId = $data['member']['user']['id'] ?? '0';
    $guildId = $data['guild_id'] ?? '';
    $pdo = getDiscordDB();
    if (!$pdo) { respond("⚠️ Database unavailable."); return; }

    switch ($subCmd) {
        case 'set':
            $month = max(1, min(12, (int)($opts['month'] ?? 1)));
            $day = max(1, min(31, (int)($opts['day'] ?? 1)));
            $monthNames = ['', 'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'];

            $pdo->prepare("INSERT INTO discord_birthdays (user_id, guild_id, birth_month, birth_day) VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE birth_month = ?, birth_day = ?")
                ->execute([$userId, $guildId, $month, $day, $month, $day]);

            respond(null, [embed("🎂 Birthday Set!", "Your birthday is set to **{$monthNames[$month]} $day**\n\nI'll announce it in the server!", 0xEB459E)]);
            break;

        case 'check':
            $target = $opts['user'] ?? $userId;
            $stmt = $pdo->prepare("SELECT * FROM discord_birthdays WHERE user_id = ? AND guild_id = ?");
            $stmt->execute([$target, $guildId]);
            $bday = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$bday) { respondEphemeral("No birthday set for <@$target>. Use `/birthday set` to set yours!"); return; }

            $monthNames = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $now = new DateTime();
            $nextBday = new DateTime(date('Y') . "-{$bday['birth_month']}-{$bday['birth_day']}");
            if ($nextBday < $now) $nextBday->modify('+1 year');
            $daysUntil = $now->diff($nextBday)->days;

            respond(null, [embed("🎂 Birthday — <@$target>", "**{$monthNames[$bday['birth_month']]} {$bday['birth_day']}**\n\n" .
                ($daysUntil === 0 ? "🎉 **IT'S TODAY! HAPPY BIRTHDAY!** 🎉" : "📅 $daysUntil days until their birthday"), 0xEB459E)]);
            break;

        case 'today':
            $month = (int)date('n');
            $day = (int)date('j');
            $stmt = $pdo->prepare("SELECT user_id FROM discord_birthdays WHERE guild_id = ? AND birth_month = ? AND birth_day = ?");
            $stmt->execute([$guildId, $month, $day]);
            $birthdays = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (empty($birthdays)) {
                respond(null, [embed("🎂 Today's Birthdays", "No birthdays today! 📅", 0xEB459E)]);
            } else {
                $mentions = implode("\n", array_map(fn($id) => "🎉 <@$id>", $birthdays));
                respond(null, [embed("🎂 Happy Birthday!", "Today's birthday stars:\n\n$mentions\n\n🥳 Wish them a happy birthday!", 0xEB459E)]);
            }
            break;

        case 'upcoming':
            $stmt = $pdo->prepare("SELECT user_id, birth_month, birth_day FROM discord_birthdays WHERE guild_id = ? ORDER BY birth_month, birth_day LIMIT 15");
            $stmt->execute([$guildId]);
            $all = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $monthNames = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            $now = new DateTime();
            // Sort by next occurrence
            usort($all, function($a, $b) use ($now) {
                $da = new DateTime(date('Y') . "-{$a['birth_month']}-{$a['birth_day']}");
                $db = new DateTime(date('Y') . "-{$b['birth_month']}-{$b['birth_day']}");
                if ($da < $now) $da->modify('+1 year');
                if ($db < $now) $db->modify('+1 year');
                return $da <=> $db;
            });

            $desc = '';
            foreach (array_slice($all, 0, 10) as $b) {
                $desc .= "🎂 <@{$b['user_id']}> — {$monthNames[$b['birth_month']]} {$b['birth_day']}\n";
            }
            respond(null, [embed("📅 Upcoming Birthdays", $desc ?: 'No birthdays registered yet! Use `/birthday set`.', 0xEB459E)]);
            break;

        default:
            respond("Use `/birthday set month:3 day:15` to set yours, or `/birthday today`.");
    }
}


function handleQuote(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $category = 'inspirational';
    foreach ($opts as $o) { if ($o['name'] === 'category') $category = $o['value']; }

    deferResponse();

    $appId = $data['application_id'] ?? '';
    $token = $data['token'] ?? '';

    $result = callGroq(
        "Generate one unique, powerful quote. Format: just the quote text, then on a new line '— Author Name'. Mix famous historical figures, philosophers, scientists, artists. Use real quotes when possible.",
        "Give me a $category quote.",
        0.9, 100
    );
    $lines = explode("\n", trim($result));
    $quoteText = $lines[0] ?? $result;
    $author = '';
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '—') || str_starts_with(trim($line), '-')) {
            $author = trim($line, " —-");
            break;
        }
    }

    $colors = [0xE74C3C, 0x3498DB, 0x2ECC71, 0x9B59B6, 0xF39C12, 0x1ABC9C, 0xE91E63];
    $color = $colors[array_rand($colors)];
    $emojis = ['💡', '✨', '🌟', '💎', '🔥', '🌊', '🎯', '🧠', '💫', '🦋'];
    $emoji = $emojis[array_rand($emojis)];

    followUp($appId, $token, '', [embed(
        "$emoji Quote" . ($category !== 'inspirational' ? " — " . ucfirst($category) : ''),
        "*\"$quoteText\"*" . ($author ? "\n\n— **$author**" : ''),
        $color
    )], [actionRow(
        btn(2, '🔄 Another Quote', 'quote_new')
    )]);
}


function handleHoroscope(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $sign = 'aries';
    foreach ($opts as $o) { if ($o['name'] === 'sign') $sign = strtolower($o['value']); }

    $signs = [
        'aries' => ['♈', 'Mar 21 - Apr 19'], 'taurus' => ['♉', 'Apr 20 - May 20'],
        'gemini' => ['♊', 'May 21 - Jun 20'], 'cancer' => ['♋', 'Jun 21 - Jul 22'],
        'leo' => ['♌', 'Jul 23 - Aug 22'], 'virgo' => ['♍', 'Aug 23 - Sep 22'],
        'libra' => ['♎', 'Sep 23 - Oct 22'], 'scorpio' => ['♏', 'Oct 23 - Nov 21'],
        'sagittarius' => ['♐', 'Nov 22 - Dec 21'], 'capricorn' => ['♑', 'Dec 22 - Jan 19'],
        'aquarius' => ['♒', 'Jan 20 - Feb 18'], 'pisces' => ['♓', 'Feb 19 - Mar 20'],
    ];

    if (!isset($signs[$sign])) { respond("Invalid sign. Choose: " . implode(', ', array_keys($signs))); return; }

    deferResponse();

    $appId = $data['application_id'] ?? '';
    $token = $data['token'] ?? '';
    $today = date('F j, Y');

    $result = callGroq(
        "You are a mystical astrologer. Generate a daily horoscope. Include: 1) Overall energy (1-2 sentences), 2) Love & relationships (1 sentence), 3) Career & money (1 sentence), 4) Lucky number (1-99), 5) Lucky color, 6) Compatibility sign. Be specific, positive but realistic. Use mystical language. Under 200 words.",
        "Daily horoscope for $sign on $today.",
        0.8, 300
    );

    [$emoji, $dates] = $signs[$sign];
    $colors = [0xE74C3C, 0xF39C12, 0x2ECC71, 0x3498DB, 0x9B59B6, 0xE91E63,
               0x1ABC9C, 0x8B0000, 0xDAA520, 0x2F4F4F, 0x4169E1, 0x008080];
    $idx = array_search($sign, array_keys($signs));

    followUp($appId, $token, '', [embed(
        "$emoji " . ucfirst($sign) . " — Daily Horoscope",
        "$result\n\n*$dates*",
        $colors[$idx % count($colors)],
        [],
        ['footer' => ['text' => "Horoscope for $today | GoSiteMe Bot"]]
    )]);
}


function handleTodo(array $data): void {
    $subCmd = getSubcommand($data);
    $opts = getSubOptions($data);
    $userId = $data['member']['user']['id'] ?? '0';
    $guildId = $data['guild_id'] ?? '';
    $pdo = getDiscordDB();
    if (!$pdo) { respond("⚠️ Database unavailable."); return; }

    switch ($subCmd) {
        case 'add':
            $task = substr(strip_tags($opts['task'] ?? ''), 0, 500);
            if (!$task) { respond("Specify a task! `/todo add task:Buy groceries`"); return; }

            $count = $pdo->prepare("SELECT COUNT(*) FROM discord_todos WHERE user_id = ? AND guild_id = ? AND completed = 0");
            $count->execute([$userId, $guildId]);
            if ($count->fetchColumn() >= 25) { respondEphemeral("You have 25 pending tasks. Complete some first!"); return; }

            $pdo->prepare("INSERT INTO discord_todos (user_id, guild_id, task) VALUES (?, ?, ?)")
                ->execute([$userId, $guildId, $task]);
            $id = $pdo->lastInsertId();

            respond(null, [embed("✅ Task Added", "**#$id** — $task", 0x57F287)]);
            break;

        case 'list':
            $stmt = $pdo->prepare("SELECT * FROM discord_todos WHERE user_id = ? AND guild_id = ? ORDER BY completed, id LIMIT 25");
            $stmt->execute([$userId, $guildId]);
            $todos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $desc = '';
            $pending = 0; $done = 0;
            foreach ($todos as $t) {
                $check = $t['completed'] ? '~~' : '';
                $icon = $t['completed'] ? '✅' : '⬜';
                $desc .= "$icon **#{$t['id']}** {$check}{$t['task']}{$check}\n";
                $t['completed'] ? $done++ : $pending++;
            }
            if (!$desc) $desc = 'No tasks yet! Use `/todo add task:Your task`';

            respond(null, [embed("📋 Your Todo List", $desc, 0x5865F2, [
                field('Pending', (string)$pending, true),
                field('Complete', (string)$done, true),
            ])]);
            break;

        case 'done':
            $id = (int)($opts['id'] ?? 0);
            if (!$id) { respond("Specify a task ID! `/todo done id:1`"); return; }
            $stmt = $pdo->prepare("UPDATE discord_todos SET completed = 1 WHERE id = ? AND user_id = ? AND guild_id = ?");
            $stmt->execute([$id, $userId, $guildId]);
            if ($stmt->rowCount() === 0) { respondEphemeral("Task #$id not found or already done."); return; }

            // Award XP for completing tasks
            awardXP($userId, 5);

            respond(null, [embed("✅ Task Complete!", "Marked **#$id** as done! +5 XP", 0x57F287)]);
            break;

        case 'remove':
            $id = (int)($opts['id'] ?? 0);
            if (!$id) { respond("Specify a task ID! `/todo remove id:1`"); return; }
            $stmt = $pdo->prepare("DELETE FROM discord_todos WHERE id = ? AND user_id = ? AND guild_id = ?");
            $stmt->execute([$id, $userId, $guildId]);
            if ($stmt->rowCount() === 0) { respondEphemeral("Task #$id not found."); return; }
            respond(null, [embed("🗑️ Task Removed", "Deleted task **#$id**", 0xED4245)]);
            break;

        case 'clear':
            $pdo->prepare("DELETE FROM discord_todos WHERE user_id = ? AND guild_id = ? AND completed = 1")
                ->execute([$userId, $guildId]);
            respond(null, [embed("🧹 Cleared", "All completed tasks removed!", 0x95A5A6)]);
            break;

        default:
            respond("Use `/todo add`, `/todo list`, `/todo done`, `/todo remove`, or `/todo clear`.");
    }
}
