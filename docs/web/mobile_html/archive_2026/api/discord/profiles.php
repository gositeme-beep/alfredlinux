<?php
/**
 * GoSiteMe Discord Bot — Profiles Module
 * ═══════════════════════════════════════
 * /profile — Rich user profile card
 * /level   — XP & level info
 * /help    — Full command reference
 */

function handleProfile(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $targetUser = null;
    foreach ($opts as $o) { if ($o['name'] === 'user') $targetUser = $o['value']; }

    $userId = $targetUser ?? ($data['member']['user']['id'] ?? '0');
    $pdo = getDiscordDB();
    if (!$pdo) { respond("⚠️ Database unavailable."); return; }

    $user = getOrCreateUser($pdo, $userId, $data['guild_id'] ?? '', $data['member']['user']['username'] ?? 'User');

    $level = calcLevel($user['xp']);
    $needed = xpForNextLevel($level);
    $currentXpInLevel = $user['xp'] - array_sum(array_map(fn($l) => (int)(100 * pow(1.15, $l - 1)), range(1, $level)));
    $bar = xpBar($currentXpInLevel, $needed);

    // Game stats
    $stmt = $pdo->prepare("SELECT
        COUNT(*) as total_games,
        SUM(CASE WHEN (player1 = ? AND status = 'won_p1') OR (player2 = ? AND status = 'won_p2') THEN 1 ELSE 0 END) as wins,
        SUM(CASE WHEN status = 'draw' THEN 1 ELSE 0 END) as draws
        FROM discord_games WHERE player1 = ? OR player2 = ?");
    $stmt->execute([$userId, $userId, $userId, $userId]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalGames = (int)$stats['total_games'];
    $wins = (int)$stats['wins'];
    $draws = (int)$stats['draws'];
    $losses = $totalGames - $wins - $draws;
    $winRate = $totalGames > 0 ? round(($wins / $totalGames) * 100) : 0;

    // Titles
    $levelT = levelTitle($level);
    $eloT = eloTitle($user['elo']);

    // Badges
    $badges = [];
    if ($level >= 50) $badges[] = '👑';
    elseif ($level >= 25) $badges[] = '💎';
    elseif ($level >= 10) $badges[] = '⭐';
    if ($user['elo'] >= 1500) $badges[] = '♟️';
    if ($user['kgd'] >= 10000) $badges[] = '💰';
    if ($user['daily_streak'] >= 30) $badges[] = '🔥';
    if ($totalGames >= 100) $badges[] = '🎮';
    if ($wins >= 50) $badges[] = '🏆';
    $badgeStr = !empty($badges) ? implode(' ', $badges) : '—';

    // Extra badges from inventory
    $invBadges = $pdo->prepare("SELECT i.item_name FROM discord_inventory i JOIN discord_shop_items s ON i.item_id = s.id WHERE i.user_id = ? AND s.item_type = 'badge'");
    $invBadges->execute([$userId]);
    $customBadges = $invBadges->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($customBadges)) $badgeStr .= ' ' . implode(' ', $customBadges);

    // Account age
    $joined = strtotime($user['created_at']);
    $daysOld = max(1, intdiv(time() - $joined, 86400));

    $desc = "**$badgeStr**\n$bar";

    $fields = [
        field('🎖️ Level', "**$level** — $levelT", true),
        field('⚡ XP', number_format($user['xp']) . " total", true),
        field('💰 KGD', number_format($user['kgd']), true),
        field('♟️ ELO', "{$user['elo']} — $eloT", true),
        field('🎮 Games', "$totalGames played", true),
        field('🏆 W/L/D', "$wins / $losses / $draws ($winRate%)", true),
        field('🔥 Daily Streak', "{$user['daily_streak']} days", true),
        field('💸 Earned / Spent', number_format($user['total_earned']) . ' / ' . number_format($user['total_spent']), true),
        field('📅 Member For', "$daysOld days", true),
    ];

    if ($user['referral_code']) {
        $fields[] = field('🔗 Referral Code', "`{$user['referral_code']}`", true);
    }

    $avatar = "https://cdn.discordapp.com/avatars/$userId/" . ($data['member']['user']['avatar'] ?? '') . ".png?size=128";

    respond(null, [array_merge(
        embed("📋 Profile — <@$userId>", $desc, 0x5865F2, $fields),
        $data['member']['user']['avatar'] ? ['thumbnail' => ['url' => $avatar]] : []
    )], [actionRow([
        btn("profile_games_$userId", '🎮 Games', 2),
        btn("profile_inventory_$userId", '🎒 Inventory', 2),
        btn("profile_achievements_$userId", '🏅 Achievements', 2),
    ])]);
}


function handleLevel(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $targetUser = null;
    foreach ($opts as $o) { if ($o['name'] === 'user') $targetUser = $o['value']; }

    $userId = $targetUser ?? ($data['member']['user']['id'] ?? '0');
    $pdo = getDiscordDB();
    if (!$pdo) { respond("⚠️ Database unavailable."); return; }

    $user = getOrCreateUser($pdo, $userId, $data['guild_id'] ?? '', $data['member']['user']['username'] ?? 'User');
    $level = calcLevel($user['xp']);
    $needed = xpForNextLevel($level);
    $currentXpInLevel = $user['xp'] - array_sum(array_map(fn($l) => (int)(100 * pow(1.15, $l - 1)), range(1, $level)));
    $pct = $needed > 0 ? round(($currentXpInLevel / $needed) * 100) : 100;
    $bar = xpBar($currentXpInLevel, $needed, 20);
    $title = levelTitle($level);
    $nextTitle = levelTitle($level + 1);

    // Rank in server
    $guildId = $data['guild_id'] ?? '';
    $rankStmt = $pdo->prepare("SELECT COUNT(*) + 1 FROM discord_users WHERE guild_id = ? AND xp > ?");
    $rankStmt->execute([$guildId, $user['xp']]);
    $rank = $rankStmt->fetchColumn();

    respond(null, [embed("⬆️ Level Progress — <@$userId>", '', 0x5865F2, [
        field('Level', "**$level** — $title", true),
        field('Server Rank', "#$rank", true),
        field('Total XP', number_format($user['xp']), true),
        field('Progress', "$bar\n$currentXpInLevel / $needed XP ($pct%)", false),
        field('Next Level', "Level " . ($level + 1) . " — $nextTitle", true),
        field('XP Needed', number_format($needed - $currentXpInLevel), true),
    ])]);
}


function handleHelp(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $category = '';
    foreach ($opts as $o) { if ($o['name'] === 'category') $category = $o['value']; }

    $categories = [
        'ai' => [
            'title' => '🤖 AI & Intelligence',
            'commands' => [
                '`/alfred` — AI chat with 5 personas (Alfred, Nova, Sage, Cipher, Atlas)',
                '`/imagine` — AI image generation (7 models: FLUX, Stable Diffusion, Ideogram)',
                '`/translate` — AI translation (100+ languages, auto-detects source)',
                '`/code` — Code run/review/explain (all major languages)',
                '`/summarize` — Summarize text (short, medium, detailed, bullets)',
            ],
        ],
        'games' => [
            'title' => '🎮 Games & Competition',
            'commands' => [
                '`/chess` — Full chess vs AI or humans (play, challenge, move, resign, stats)',
                '`/checkers` — Checkers vs AI or humans (play, challenge, move, resign)',
                '`/trivia` — AI trivia with KGD rewards (17 categories, 3 difficulties)',
                '`/8ball` — Ask the Magic 8-Ball',
                '`/rps` — Rock Paper Scissors vs AI or challenge friends',
            ],
        ],
        'economy' => [
            'title' => '💰 Economy & Trading',
            'commands' => [
                '`/coins` — Balance, send KGD, view leaderboards',
                '`/daily` — Daily KGD reward (streak bonuses, milestones)',
                '`/shop` — Browse & buy items, loot boxes, view inventory',
                '`/gamble` — Coinflip, dice, slots (various multipliers)',
            ],
        ],
        'community' => [
            'title' => '🏘️ Community & Events',
            'commands' => [
                '`/poll` — Create interactive polls with buttons',
                '`/giveaway` — Run giveaways with timed entries',
                '`/ticket` — Support ticket system (open, reply, close)',
                '`/embed` — Build custom rich embeds',
                '`/remind` — Set personal reminders',
                '`/announce` — Server announcements with pings',
            ],
        ],
        'mod' => [
            'title' => '🛡️ Moderation & Safety',
            'commands' => [
                '`/mod kick` — Kick a member',
                '`/mod ban` — Ban a member (+ message cleanup)',
                '`/mod unban` — Unban by user ID',
                '`/mod mute` — Timeout a member',
                '`/mod unmute` — Remove timeout',
                '`/mod warn` — Issue a warning',
                '`/mod warnings` — View a user\'s warnings',
                '`/mod purge` — Bulk delete messages',
                '`/mod slowmode` — Set channel slowmode',
                '`/automod` — Enable/disable auto-moderation',
                '`/audit` — View moderation audit log',
            ],
        ],
        'tools' => [
            'title' => '🔧 Tools & Utilities',
            'commands' => [
                '`/status` — Website uptime & SSL monitor',
                '`/weather` — Weather lookup (any city worldwide)',
                '`/domain` — Domain availability, DNS, SSL check',
                '`/qr` — Generate QR codes',
                '`/crypto` — Live crypto prices (BTC, ETH, SOL, 20+ coins)',
                '`/color` — Color information (HEX, RGB, HSL, palette)',
            ],
        ],
        'social' => [
            'title' => '👥 Social',
            'commands' => [
                '`/serverinfo` — Server statistics & information',
                '`/userinfo` — Detailed user information',
                '`/afk` — Set AFK status with custom message',
                '`/birthday` — Birthday tracking (set, check, today, upcoming)',
                '`/quote` — AI-generated inspirational quotes',
                '`/horoscope` — Daily horoscope for your zodiac sign',
                '`/todo` — Personal to-do list manager',
            ],
        ],
        'premium' => [
            'title' => '⭐ Premium',
            'commands' => [
                '`/tts` — AI text-to-speech (ElevenLabs, 8 voices)',
                '`/sms` — Send real SMS messages via Telnyx',
                '`/search` — Deep AI web research',
                '`/screenshot` — Capture website screenshots',
                '`/calc` — Advanced math/science calculator',
                '`/music` — AI music generation (Replicate)',
                '`/deploy` — Deploy code snippets to GoCodeMe',
            ],
        ],
        'voice' => [
            'title' => '📞 Voice & Communications',
            'commands' => [
                '`/call` — Place real phone calls via Telnyx (10 KGD)',
                '`/fax` — Send fax documents via Telnyx (15 KGD)',
                '`/email` — Send branded emails (3 KGD)',
            ],
        ],
        'media' => [
            'title' => '🎬 Media Generation',
            'commands' => [
                '`/video` — AI video generation via fal.ai (25 KGD)',
                '`/musicgen` — AI music from text prompts (15 KGD)',
                '`/voiceclone` — AI text-to-speech with voice selection (10 KGD)',
            ],
        ],
        'finance' => [
            'title' => '📈 Finance & Markets',
            'commands' => [
                '`/stock` — Live crypto/stock prices with charts',
                '`/portfolio` — Your financial overview & transaction history',
            ],
        ],
        'fun' => [
            'title' => '🎭 Fun & Entertainment',
            'commands' => [
                '`/debate` — AI debate between two personas',
                '`/roast` — AI roast generator (3 intensities)',
                '`/story` — Interactive branching stories (6 genres)',
                '`/dream` — AI dream interpretation (Jungian)',
                '`/recipe` — AI recipe generator (8 cuisines)',
                '`/interview` — Mock job interview practice',
                '`/riddle` — Riddles with reveal buttons & rewards',
                '`/encrypt` — Hash, encode, decode, password tools',
                '`/wisdom` — Daily AI wisdom & life advice',
                '`/persona` — Chat with historical figures via AI',
            ],
        ],
        'news' => [
            'title' => '📰 News & Research',
            'commands' => [
                '`/news` — Live RSS news feeds (8 categories)',
                '`/legal` — AI legal research (Canadian/Quebec law)',
                '`/digest` — AI-powered multi-source news digest',
            ],
        ],
        'websearch' => [
            'title' => '🔍 Web Search & Research',
            'commands' => [
                '`/websearch` — Search the web with AI summaries',
                '`/readurl` — Read & extract content from any URL',
                '`/research` — Deep AI research on any topic',
                '`/whois` — WHOIS/RDAP + DNS + SSL lookup',
            ],
        ],
        'admin' => [
            'title' => '🖥️ Admin & System',
            'commands' => [
                '`/health` — System health check (DB, APIs, server)',
                '`/botlogs` — View bot logs (owner only)',
                '`/botstats` — Bot usage statistics & leaderboards',
                '`/serverban` — View server ban list',
                '`/backup` — Database overview & table stats',
            ],
        ],
        'creative' => [
            'title' => '✍️ Creative Writing',
            'commands' => [
                '`/poem` — AI poetry (8 styles: sonnet, haiku, rap...)',
                '`/lyrics` — AI song lyrics (8 genres: pop, rock, hip-hop...)',
                '`/script` — AI screenplay/sketch writing (6 formats)',
            ],
        ],
        'social2' => [
            'title' => '🎲 Social Games',
            'commands' => [
                '`/confess` — Post anonymous confessions',
                '`/wouldyourather` — AI-generated dilemmas with voting',
                '`/compatibility` — Check compatibility with a user',
                '`/tierlist` — AI tier list rankings',
            ],
        ],
        'utility' => [
            'title' => '🛠️ Utility',
            'commands' => [
                '`/timestamp` — Generate Discord timestamp formats',
                '`/avatar` — Get user avatars in full resolution',
                '`/banner` — ASCII art text banners',
                '`/math` — AI math solver with step-by-step',
                '`/define` — Dictionary definitions & examples',
            ],
        ],
        'personality' => [
            'title' => '🧠 Personality Engine',
            'commands' => [
                '`/personality` — View & customize AI personality traits',
                '`/mood` — Set Alfred\'s response mood (8 moods)',
                '`/style` — Set response style (concise, ELI5, academic...)',
                '`/memorize` — Teach Alfred facts about you',
                '`/adapt` — AI personality analysis & recommendations',
            ],
        ],
        'documents' => [
            'title' => '📄 Document Processor',
            'commands' => [
                '`/doc` — Parse, summarize, or analyze uploaded files',
                '`/ocr` — Extract text from images (OCR)',
                '`/summarizedoc` — Summarize any web document by URL',
                '`/fileinfo` — Get detailed file metadata',
            ],
        ],
        'kingdom' => [
            'title' => '🏰 Kingdom / Metaverse',
            'commands' => [
                '`/kingdom` — Your persistent Kingdom profile & zones',
                '`/transfer` — Send KGD to another player',
                '`/leaderboard` — Global rankings (XP, KGD, Games)',
                '`/achievements` — View unlocked achievements',
                '`/zones` — Explore Kingdom zones & populations',
            ],
        ],
        'scripture' => [
            'title' => '📖 Scripture & Faith',
            'commands' => [
                '`/verse` — Random Bible verse by category (KJV)',
                '`/devotional` — AI-generated daily devotional',
                '`/prayer` — Prayer wall (share & pray for requests)',
                '`/bible` — Search the Bible by keyword or topic',
            ],
        ],
        'agents' => [
            'title' => '🤖 AI Agents & Goals',
            'commands' => [
                '`/agents` — View the 8 AI agent roster & status',
                '`/goal` — Create & track persistent goals',
                '`/delegate` — Delegate tasks to AI agents',
                '`/decision` — View Alfred\'s autonomous decision log',
                '`/roster` — Detailed agent profiles & bios',
                '`/wager` — Bet KGD on agent performance',
                '`/ecosystem` — Live agent ecosystem status',
            ],
        ],
        'profile' => [
            'title' => '👤 Profile & Progression',
            'commands' => [
                '`/profile` — Your rich profile card with stats',
                '`/level` — XP progress and server rank',
                '`/help` — This help menu',
            ],
        ],
    ];

    if ($category && isset($categories[$category])) {
        $cat = $categories[$category];
        respond(null, [embed($cat['title'], implode("\n", $cat['commands']), 0x5865F2)]);
        return;
    }

    // Full help overview
    $desc = "**GoSiteMe Bot** — The most sophisticated Discord bot\n"
        . "Powered by AI, games, economy, communications & more.\n\n"
        . "Use `/help category:name` for detailed info on each category.\n\n";

    $fields = [];
    foreach ($categories as $key => $cat) {
        $count = count($cat['commands']);
        $fields[] = field($cat['title'], "$count commands — `/help category:$key`", true);
    }

    $totalCmds = array_sum(array_map(fn($c) => count($c['commands']), $categories));

    respond(null, [embed("📚 GoSiteMe Bot — Help", $desc . "**$totalCmds total commands** across " . count($categories) . " categories", 0x5865F2, $fields, [
        'footer' => ['text' => 'GoSiteMe Bot v5.0 — gositeme.com', 'icon_url' => 'https://gositeme.com/assets/images/logo-icon.png'],
    ])], [actionRow(
        btn(2, '🤖 AI', 'help_ai'),
        btn(2, '🎮 Games', 'help_games'),
        btn(2, '💰 Economy', 'help_economy'),
        btn(2, '🏘️ Community', 'help_community'),
        btn(2, '🛡️ Mod', 'help_mod')
    ), actionRow(
        btn(2, '🔧 Tools', 'help_tools'),
        btn(2, '👥 Social', 'help_social'),
        btn(2, '⭐ Premium', 'help_premium'),
        btn(2, '📞 Voice', 'help_voice'),
        btn(2, '🎬 Media', 'help_media')
    ), actionRow(
        btn(2, '📈 Finance', 'help_finance'),
        btn(2, '🎭 Fun', 'help_fun'),
        btn(2, '📰 News', 'help_news'),
        btn(2, '🔍 WebSearch', 'help_websearch'),
        btn(2, '🖥️ Admin', 'help_admin')
    ), actionRow(
        btn(2, '✍️ Creative', 'help_creative'),
        btn(2, '🎲 Social2', 'help_social2'),
        btn(2, '🛠️ Utility', 'help_utility'),
        btn(2, '🧠 Personality', 'help_personality'),
        btn(2, '📄 Docs', 'help_documents')
    ), actionRow(
        btn(2, '🏰 Kingdom', 'help_kingdom'),
        btn(2, '📖 Scripture', 'help_scripture'),
        btn(2, '🤖 Agents', 'help_agents'),
        btn(2, '👤 Profile', 'help_profile'),
        btn(5, '➕ Invite', 'https://discord.com/oauth2/authorize?client_id=1479627736208375981&permissions=8&scope=bot%20applications.commands')
    )]);
}
