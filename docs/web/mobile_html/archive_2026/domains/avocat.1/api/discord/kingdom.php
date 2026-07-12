<?php
/**
 * GoSiteMe Discord Bot — Kingdom / Metaverse Module
 * ══════════════════════════════════════════════════
 * Commands: /kingdom /transfer /leaderboard /achievements /zones
 * Persistent cross-game identity, economy, social graph.
 */

function ensureKingdomTables(): void {
    $db = getDiscordDB();
    if (!$db) return;

    $db->exec("CREATE TABLE IF NOT EXISTS kingdom_profiles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        discord_id VARCHAR(32) UNIQUE NOT NULL,
        display_name VARCHAR(50) NOT NULL,
        title VARCHAR(50) DEFAULT 'Peasant',
        elo_rating INT DEFAULT 1000,
        current_zone VARCHAR(50) DEFAULT 'central_square',
        games_played INT DEFAULT 0,
        games_won INT DEFAULT 0,
        achievements JSON DEFAULT NULL,
        joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_elo (elo_rating)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS kingdom_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        from_discord_id VARCHAR(32) NOT NULL,
        to_discord_id VARCHAR(32) DEFAULT NULL,
        type ENUM('earn','spend','transfer','reward') NOT NULL,
        amount INT NOT NULL,
        description VARCHAR(200),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_from (from_discord_id),
        INDEX idx_to (to_discord_id)
    )");
}

function getTitle(int $level): string {
    return match(true) {
        $level >= 50 => '👑 Emperor',
        $level >= 40 => '⚜️ King',
        $level >= 30 => '🏰 Duke',
        $level >= 20 => '⚔️ Knight',
        $level >= 15 => '🛡️ Squire',
        $level >= 10 => '🏠 Merchant',
        $level >= 5  => '🌾 Farmer',
        default      => '🧑 Peasant',
    };
}

function handleKingdom($data): void {
    $sub = $data['data']['options'][0]['name'] ?? 'profile';
    $userId   = $data['member']['user']['id'] ?? ($data['user']['id'] ?? '');
    $username = $data['member']['user']['username'] ?? ($data['user']['username'] ?? 'User');
    $db       = getDiscordDB();
    if (!$db) { respond('❌ Database unavailable.'); return; }
    ensureKingdomTables();

    $user = getOrCreateUser($userId, $username);

    // Ensure kingdom profile
    $stmt = $db->prepare("INSERT IGNORE INTO kingdom_profiles (discord_id, display_name) VALUES (?, ?)");
    $stmt->execute([$userId, $username]);

    $kp = $db->prepare("SELECT * FROM kingdom_profiles WHERE discord_id = ?");
    $kp->execute([$userId]);
    $profile = $kp->fetch();

    $title = getTitle($user['level'] ?? 1);

    // Update title if changed
    if ($profile && $profile['title'] !== $title) {
        $db->prepare("UPDATE kingdom_profiles SET title = ? WHERE discord_id = ?")->execute([$title, $userId]);
    }

    switch ($sub) {
        case 'profile':
            $kgd = $user['kgd_balance'] ?? 0;
            $xp = $user['xp'] ?? 0;
            $lvl = $user['level'] ?? 1;

            // Get game stats
            $games = $profile['games_played'] ?? 0;
            $wins  = $profile['games_won'] ?? 0;
            $winrate = $games > 0 ? round(($wins / $games) * 100) : 0;

            // Get transaction history
            $txStmt = $db->prepare("SELECT COUNT(*) as cnt, SUM(CASE WHEN type = 'earn' THEN amount ELSE 0 END) as earned,
                SUM(CASE WHEN type = 'spend' THEN amount ELSE 0 END) as spent
                FROM kingdom_transactions WHERE from_discord_id = ?");
            $txStmt->execute([$userId]);
            $txStats = $txStmt->fetch();

            $zone = match($profile['current_zone'] ?? 'central_square') {
                'central_square' => '🏛️ Central Square',
                'market'         => '🏪 Market District',
                'arena'          => '⚔️ Battle Arena',
                'library'        => '📚 Grand Library',
                'tavern'         => '🍺 Tavern',
                'castle'         => '🏰 Royal Castle',
                default          => '🌍 ' . ucfirst($profile['current_zone'] ?? 'central_square'),
            };

            respond(null, [embed("$title $username — Kingdom Profile", '', 0xFFD700, [
                field('Title', $title, true),
                field('Level', "**$lvl** ({$xp} XP)", true),
                field('KGD Balance', "💰 $kgd", true),
                field('ELO Rating', "🏆 " . ($profile['elo_rating'] ?? 1000), true),
                field('Games', "🎮 $games played · $wins won ({$winrate}%)", true),
                field('Current Zone', $zone, true),
                field('Total Earned', '💰 ' . ($txStats['earned'] ?? 0) . ' KGD', true),
                field('Total Spent', '💸 ' . ($txStats['spent'] ?? 0) . ' KGD', true),
                field('Member Since', '<t:' . strtotime($profile['joined_at'] ?? 'now') . ':R>', true),
            ], [
                'thumbnail' => ['url' => "https://cdn.discordapp.com/avatars/$userId/" . ($data['member']['user']['avatar'] ?? '') . ".png?size=256"],
            ])], [actionRow(
                btn(2, '🏛️ Zones', 'kingdom_zones'),
                btn(2, '🏆 Leaderboard', 'kingdom_lb'),
                btn(2, '🎖️ Achievements', 'kingdom_achievements'),
                btn(2, '💰 Ledger', 'kingdom_ledger')
            )]);
            break;

        case 'zone':
            $zone = $data['data']['options'][0]['options'][0]['value'] ?? 'central_square';
            $zones = [
                'central_square' => ['name' => '🏛️ Central Square', 'desc' => 'The heart of the kingdom. Trade, socialize, and find quests.', 'pop' => 'High'],
                'market'         => ['name' => '🏪 Market District', 'desc' => 'Buy and sell items. Economy hub. Merchant NPCs.', 'pop' => 'High'],
                'arena'          => ['name' => '⚔️ Battle Arena', 'desc' => 'Challenge players to games. Earn ELO and glory.', 'pop' => 'Medium'],
                'library'        => ['name' => '📚 Grand Library', 'desc' => 'Knowledge awaits. Research, learn, discover.', 'pop' => 'Low'],
                'tavern'         => ['name' => '🍺 Tavern', 'desc' => 'Rest and hear stories. Social hub. Mini-games.', 'pop' => 'Medium'],
                'castle'         => ['name' => '🏰 Royal Castle', 'desc' => 'Seat of power. Guild management. Royal quests.', 'pop' => 'Low'],
            ];

            if (!isset($zones[$zone])) {
                respondEphemeral("❌ Unknown zone. Available: " . implode(', ', array_keys($zones)));
                return;
            }

            $z = $zones[$zone];
            $db->prepare("UPDATE kingdom_profiles SET current_zone = ? WHERE discord_id = ?")->execute([$zone, $userId]);

            // Count players in zone
            $cnt = $db->prepare("SELECT COUNT(*) FROM kingdom_profiles WHERE current_zone = ?");
            $cnt->execute([$zone]);
            $pop = $cnt->fetchColumn();

            respond(null, [embed("🚶 Traveled to {$z['name']}", $z['desc'], 0x3498DB, [
                field('Population', "$pop players here", true),
                field('Traffic', $z['pop'], true),
            ])], [actionRow(
                btn(2, '🏛️ Square', 'zone_central_square'),
                btn(2, '🏪 Market', 'zone_market'),
                btn(2, '⚔️ Arena', 'zone_arena'),
                btn(2, '📚 Library', 'zone_library'),
                btn(2, '🍺 Tavern', 'zone_tavern')
            )]);
            awardXP($userId, 3);
            break;

        default:
            respondEphemeral("Use `/kingdom profile` or `/kingdom zone`.");
    }
}

function handleTransferKgd($data): void {
    $targetId = '';
    $amount   = 0;
    foreach (($data['data']['options'] ?? []) as $o) {
        if ($o['name'] === 'user')   $targetId = $o['value'];
        if ($o['name'] === 'amount') $amount = (int)$o['value'];
    }

    $userId   = $data['member']['user']['id'] ?? ($data['user']['id'] ?? '');
    $username = $data['member']['user']['username'] ?? ($data['user']['username'] ?? 'User');

    if ($targetId === $userId) {
        respondEphemeral("❌ You can't transfer to yourself.");
        return;
    }
    if ($amount < 1) {
        respondEphemeral("❌ Amount must be at least 1 KGD.");
        return;
    }

    $db = getDiscordDB();
    if (!$db) { respond('❌ Database unavailable.'); return; }
    ensureKingdomTables();

    $user = getOrCreateUser($userId, $username);
    if (($user['kgd_balance'] ?? 0) < $amount) {
        respondEphemeral("❌ Insufficient balance. You have **{$user['kgd_balance']}** KGD.");
        return;
    }

    // Get target username from resolved
    $targetUser = $data['data']['resolved']['users'][$targetId] ?? null;
    $targetName = $targetUser['username'] ?? 'Unknown';
    getOrCreateUser($targetId, $targetName);

    $db->beginTransaction();
    try {
        $db->prepare("UPDATE discord_users SET kgd_balance = kgd_balance - ? WHERE discord_id = ?")->execute([$amount, $userId]);
        $db->prepare("UPDATE discord_users SET kgd_balance = kgd_balance + ? WHERE discord_id = ?")->execute([$amount, $targetId]);
        $db->prepare("INSERT INTO kingdom_transactions (from_discord_id, to_discord_id, type, amount, description) VALUES (?, ?, 'transfer', ?, ?)")
            ->execute([$userId, $targetId, $amount, "Transfer to $targetName"]);
        $db->commit();
    } catch (\Exception $e) {
        $db->rollback();
        respond('❌ Transfer failed.');
        return;
    }

    respond(null, [embed("💰 Transfer Complete", "**$username** → **$targetName**\n\n💸 **{$amount} KGD** sent successfully!", 0x2ECC71, [
        field('Your Balance', '💰 ' . (($user['kgd_balance'] ?? 0) - $amount) . ' KGD', true),
    ])]);
}

function handleLeaderboardCmd($data): void {
    $type = '';
    foreach (($data['data']['options'] ?? []) as $o) {
        if ($o['name'] === 'type') $type = $o['value'];
    }
    $type = $type ?: 'xp';

    $db = getDiscordDB();
    if (!$db) { respond('❌ Database unavailable.'); return; }
    ensureKingdomTables();

    $title = '';
    $rows = [];

    switch ($type) {
        case 'xp':
            $title = '🏆 XP Leaderboard';
            $stmt = $db->query("SELECT username, xp, level FROM discord_users ORDER BY xp DESC LIMIT 10");
            $rows = $stmt->fetchAll();
            break;
        case 'kgd':
            $title = '💰 KGD Leaderboard';
            $stmt = $db->query("SELECT username, kgd_balance FROM discord_users ORDER BY kgd_balance DESC LIMIT 10");
            $rows = $stmt->fetchAll();
            break;
        case 'games':
            $title = '🎮 Games Leaderboard';
            $stmt = $db->query("SELECT p.display_name, p.games_won, p.games_played, p.elo_rating FROM kingdom_profiles p ORDER BY p.elo_rating DESC LIMIT 10");
            $rows = $stmt->fetchAll();
            break;
    }

    $medals = ['🥇', '🥈', '🥉'];
    $lines = [];
    foreach ($rows as $i => $r) {
        $medal = $medals[$i] ?? '`' . ($i + 1) . '.`';
        if ($type === 'xp') {
            $lines[] = "$medal **{$r['username']}** — Level {$r['level']} ({$r['xp']} XP)";
        } elseif ($type === 'kgd') {
            $lines[] = "$medal **{$r['username']}** — 💰 {$r['kgd_balance']} KGD";
        } else {
            $wr = $r['games_played'] > 0 ? round(($r['games_won'] / $r['games_played']) * 100) : 0;
            $lines[] = "$medal **{$r['display_name']}** — ELO {$r['elo_rating']} ({$r['games_won']}W/{$r['games_played']}G, {$wr}%)";
        }
    }

    respond(null, [embed($title, implode("\n", $lines) ?: 'No data yet.', 0xFFD700)], [actionRow(
        btn(2, '🏆 XP', 'lb_xp'),
        btn(2, '💰 KGD', 'lb_kgd'),
        btn(2, '🎮 Games', 'lb_games')
    )]);
}

function handleAchievements($data): void {
    $userId   = $data['member']['user']['id'] ?? ($data['user']['id'] ?? '');
    $username = $data['member']['user']['username'] ?? ($data['user']['username'] ?? 'User');
    $db       = getDiscordDB();
    if (!$db) { respond('❌ Database unavailable.'); return; }

    $user = getOrCreateUser($userId, $username);
    $level = $user['level'] ?? 1;
    $kgd   = $user['kgd_balance'] ?? 0;
    $xp    = $user['xp'] ?? 0;

    // Dynamic achievement check
    $achievements = [
        ['emoji' => '🌱', 'name' => 'First Steps',     'desc' => 'Reach Level 2',             'unlocked' => $level >= 2],
        ['emoji' => '⭐', 'name' => 'Rising Star',      'desc' => 'Reach Level 5',             'unlocked' => $level >= 5],
        ['emoji' => '🔥', 'name' => 'On Fire',          'desc' => 'Reach Level 10',            'unlocked' => $level >= 10],
        ['emoji' => '💎', 'name' => 'Diamond Rank',     'desc' => 'Reach Level 20',            'unlocked' => $level >= 20],
        ['emoji' => '👑', 'name' => 'Royal Blood',      'desc' => 'Reach Level 30',            'unlocked' => $level >= 30],
        ['emoji' => '💰', 'name' => 'First Fortune',    'desc' => 'Earn 100 KGD',              'unlocked' => $kgd >= 100],
        ['emoji' => '🏦', 'name' => 'Banker',           'desc' => 'Earn 1,000 KGD',            'unlocked' => $kgd >= 1000],
        ['emoji' => '💎', 'name' => 'Tycoon',           'desc' => 'Earn 10,000 KGD',           'unlocked' => $kgd >= 10000],
        ['emoji' => '🎯', 'name' => 'Dedicated',        'desc' => 'Earn 1,000 XP',             'unlocked' => $xp >= 1000],
        ['emoji' => '🏆', 'name' => 'Legend',           'desc' => 'Earn 10,000 XP',            'unlocked' => $xp >= 10000],
    ];

    $unlocked = array_filter($achievements, fn($a) => $a['unlocked']);
    $locked   = array_filter($achievements, fn($a) => !$a['unlocked']);

    $lines = [];
    foreach ($unlocked as $a) {
        $lines[] = "{$a['emoji']} ~~{$a['name']}~~ ✅ — *{$a['desc']}*";
    }
    foreach ($locked as $a) {
        $lines[] = "🔒 **{$a['name']}** — *{$a['desc']}*";
    }

    $progress = count($unlocked) . '/' . count($achievements);

    respond(null, [embed("🎖️ Achievements — $username", implode("\n", $lines), 0xFFD700, [
        field('Progress', "$progress unlocked", true),
        field('Completion', round((count($unlocked) / count($achievements)) * 100) . '%', true),
    ])]);
}

function handleZones($data): void {
    $db = getDiscordDB();
    if (!$db) { respond('❌ Database unavailable.'); return; }
    ensureKingdomTables();

    $zones = [
        'central_square' => ['name' => '🏛️ Central Square', 'desc' => 'Trade & socialize'],
        'market'         => ['name' => '🏪 Market District', 'desc' => 'Economy hub'],
        'arena'          => ['name' => '⚔️ Battle Arena', 'desc' => 'PvP games'],
        'library'        => ['name' => '📚 Grand Library', 'desc' => 'Knowledge & research'],
        'tavern'         => ['name' => '🍺 Tavern', 'desc' => 'Stories & mini-games'],
        'castle'         => ['name' => '🏰 Royal Castle', 'desc' => 'Guilds & royal quests'],
    ];

    $counts = $db->query("SELECT current_zone, COUNT(*) as cnt FROM kingdom_profiles GROUP BY current_zone")->fetchAll(PDO::FETCH_KEY_PAIR);

    $lines = [];
    foreach ($zones as $key => $z) {
        $pop = $counts[$key] ?? 0;
        $lines[] = "{$z['name']} — {$z['desc']} · **{$pop}** players";
    }

    respond(null, [embed("🌍 Kingdom Zones", implode("\n\n", $lines), 0x3498DB, [
        field('Total Players', (string)array_sum($counts ?: [0]), true),
    ])], [actionRow(
        btn(1, '🏛️ Go to Square', 'zone_central_square'),
        btn(1, '🏪 Go to Market', 'zone_market'),
        btn(1, '⚔️ Go to Arena', 'zone_arena'),
        btn(1, '📚 Go to Library', 'zone_library'),
        btn(1, '🍺 Go to Tavern', 'zone_tavern')
    )]);
}
