<?php
/**
 * GoSiteMe Discord Bot — Core Module
 * ═══════════════════════════════════
 * Response builders, DB layer, user management, utilities.
 * Included by all other modules.
 */

// ─── Response Helpers ───────────────────────────────────────────────────

function respond(?string $content, array $embeds = [], array $components = [], int $flags = 0): void {
    $d = [];
    if ($content !== null) $d['content'] = $content;
    if ($embeds) $d['embeds'] = $embeds;
    if ($components) $d['components'] = $components;
    if ($flags) $d['flags'] = $flags;
    echo json_encode(['type' => 4, 'data' => $d]);
}

function respondEphemeral(string $content, array $embeds = [], array $components = []): void {
    respond($content, $embeds, $components, 64);
}

function deferResponse(bool $ephemeral = false): void {
    $p = ['type' => 5];
    if ($ephemeral) $p['data'] = ['flags' => 64];
    echo json_encode($p);
    if (function_exists('fastcgi_finish_request')) { fastcgi_finish_request(); }
    else { ob_end_flush(); flush(); }
}

function deferComponentUpdate(): void {
    echo json_encode(['type' => 6]);
    if (function_exists('fastcgi_finish_request')) { fastcgi_finish_request(); }
    else { ob_end_flush(); flush(); }
}

function respondModal(string $customId, string $title, array $components): void {
    echo json_encode([
        'type' => 9,
        'data' => [
            'custom_id' => $customId,
            'title'     => $title,
            'components' => $components,
        ]
    ]);
}

function followUp(string $appId, string $token, string $content = '', array $embeds = [], array $components = [], int $flags = 0): void {
    $url = "https://discord.com/api/v10/webhooks/$appId/$token";
    $p = [];
    if ($content) $p['content'] = $content;
    if ($embeds) $p['embeds'] = $embeds;
    if ($components) $p['components'] = $components;
    if ($flags) $p['flags'] = $flags;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($p),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

function editOriginal(string $appId, string $token, string $content = '', array $embeds = [], array $components = []): void {
    $url = "https://discord.com/api/v10/webhooks/$appId/$token/messages/@original";
    $p = [];
    if ($content) $p['content'] = $content;
    if ($embeds) $p['embeds'] = $embeds;
    if ($components) $p['components'] = $components;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => 'PATCH',
        CURLOPT_POSTFIELDS     => json_encode($p),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

function discordApi(string $endpoint, string $method = 'GET', ?array $body = null): ?array {
    $token = getenv('DISCORD_BOT_TOKEN');
    $url = "https://discord.com/api/v10$endpoint";
    $ch = curl_init($url);
    $h = ['Authorization: Bot ' . $token, 'Content-Type: application/json'];
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => $h,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
    ]);
    if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    $r = curl_exec($ch);
    curl_close($ch);
    return json_decode($r, true);
}


// ─── Database Layer ─────────────────────────────────────────────────────

function getDiscordDB(): ?PDO {
    static $pdo = null;
    if ($pdo) return $pdo;

    $dbFile = dirname(__DIR__) . '/includes/db-config.inc.php';
    if (file_exists($dbFile)) require_once $dbFile;

    try {
        $pdo = new PDO(
            'mysql:host=' . (defined('GOSITEME_DB_HOST') ? GOSITEME_DB_HOST : 'localhost') .
            ';dbname=' . (defined('GOSITEME_DB_NAME') ? GOSITEME_DB_NAME : 'gositeme_whmcs') .
            ';charset=utf8mb4',
            defined('GOSITEME_DB_USER') ? GOSITEME_DB_USER : 'gositeme_whmcs',
            defined('GOSITEME_DB_PASS') ? GOSITEME_DB_PASS : '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        ensureAllTables($pdo);
        return $pdo;
    } catch (PDOException $e) {
        error_log('[discord-bot] DB: ' . $e->getMessage());
        return null;
    }
}

function ensureAllTables(PDO $pdo): void {
    static $done = false;
    if ($done) return;
    $done = true;

    $pdo->exec("CREATE TABLE IF NOT EXISTS discord_users (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        discord_id      VARCHAR(25) UNIQUE NOT NULL,
        discord_name    VARCHAR(100) NOT NULL,
        avatar_url      VARCHAR(300) DEFAULT NULL,
        client_id       INT DEFAULT NULL,
        kgd_balance     BIGINT DEFAULT 100,
        xp              INT DEFAULT 0,
        level           INT DEFAULT 1,
        elo_chess       INT DEFAULT 1000,
        elo_checkers    INT DEFAULT 1000,
        games_played    INT DEFAULT 0,
        games_won       INT DEFAULT 0,
        games_lost      INT DEFAULT 0,
        games_drawn     INT DEFAULT 0,
        trivia_correct  INT DEFAULT 0,
        trivia_played   INT DEFAULT 0,
        title           VARCHAR(50) DEFAULT 'Peasant',
        badges          JSON DEFAULT NULL,
        daily_streak    INT DEFAULT 0,
        last_daily      DATE DEFAULT NULL,
        total_earned    BIGINT DEFAULT 0,
        total_spent     BIGINT DEFAULT 0,
        premium_tier    TINYINT DEFAULT 0,
        referral_code   VARCHAR(20) DEFAULT NULL,
        referred_by     VARCHAR(25) DEFAULT NULL,
        joined_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_seen       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_discord (discord_id),
        INDEX idx_level (level DESC),
        INDEX idx_kgd (kgd_balance DESC)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS discord_games (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        game_type       VARCHAR(20) NOT NULL DEFAULT 'chess',
        player_white    VARCHAR(25) NOT NULL,
        player_black    VARCHAR(25) NOT NULL,
        state           TEXT NOT NULL,
        fen             VARCHAR(200) DEFAULT NULL,
        status          VARCHAR(20) DEFAULT 'active',
        winner          VARCHAR(25) DEFAULT NULL,
        move_count      INT DEFAULT 0,
        wager           INT DEFAULT 0,
        channel_id      VARCHAR(25) DEFAULT NULL,
        guild_id        VARCHAR(25) DEFAULT NULL,
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_players (player_white, player_black),
        INDEX idx_status (status),
        INDEX idx_type (game_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS discord_economy (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        discord_id      VARCHAR(25) NOT NULL,
        entry_type      VARCHAR(30) NOT NULL,
        amount          BIGINT NOT NULL,
        reason          VARCHAR(200) NOT NULL,
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (discord_id),
        INDEX idx_type (entry_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS discord_polls (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        guild_id        VARCHAR(25) NOT NULL,
        channel_id      VARCHAR(25) NOT NULL,
        message_id      VARCHAR(25) DEFAULT NULL,
        creator_id      VARCHAR(25) NOT NULL,
        question        VARCHAR(300) NOT NULL,
        options         JSON NOT NULL,
        votes           JSON DEFAULT '{}',
        multi_vote      TINYINT DEFAULT 0,
        anonymous       TINYINT DEFAULT 1,
        closes_at       TIMESTAMP NULL DEFAULT NULL,
        status          VARCHAR(10) DEFAULT 'active',
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_guild (guild_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS discord_giveaways (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        guild_id        VARCHAR(25) NOT NULL,
        channel_id      VARCHAR(25) NOT NULL,
        message_id      VARCHAR(25) DEFAULT NULL,
        creator_id      VARCHAR(25) NOT NULL,
        prize           VARCHAR(200) NOT NULL,
        description     TEXT DEFAULT NULL,
        winner_count    INT DEFAULT 1,
        entries         JSON DEFAULT '[]',
        requirement     VARCHAR(50) DEFAULT NULL,
        ends_at         TIMESTAMP NOT NULL,
        status          VARCHAR(10) DEFAULT 'active',
        winners         JSON DEFAULT NULL,
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_guild (guild_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS discord_tickets (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        guild_id        VARCHAR(25) NOT NULL,
        channel_id      VARCHAR(25) DEFAULT NULL,
        creator_id      VARCHAR(25) NOT NULL,
        subject         VARCHAR(200) NOT NULL,
        category        VARCHAR(50) DEFAULT 'general',
        priority        VARCHAR(10) DEFAULT 'normal',
        status          VARCHAR(10) DEFAULT 'open',
        messages        JSON DEFAULT '[]',
        assigned_to     VARCHAR(25) DEFAULT NULL,
        closed_at       TIMESTAMP NULL DEFAULT NULL,
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_guild (guild_id),
        INDEX idx_status (status),
        INDEX idx_creator (creator_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS discord_reminders (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        discord_id      VARCHAR(25) NOT NULL,
        channel_id      VARCHAR(25) DEFAULT NULL,
        message         VARCHAR(500) NOT NULL,
        remind_at       TIMESTAMP NOT NULL,
        status          VARCHAR(10) DEFAULT 'pending',
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (discord_id),
        INDEX idx_time (remind_at),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS discord_warnings (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        guild_id        VARCHAR(25) NOT NULL,
        user_id         VARCHAR(25) NOT NULL,
        moderator_id    VARCHAR(25) NOT NULL,
        reason          VARCHAR(500) NOT NULL,
        action_taken    VARCHAR(20) DEFAULT 'warn',
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_guild_user (guild_id, user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS discord_shop_items (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        name            VARCHAR(100) NOT NULL,
        description     VARCHAR(300) NOT NULL,
        emoji           VARCHAR(10) DEFAULT '🎁',
        price           INT NOT NULL,
        category        VARCHAR(30) DEFAULT 'general',
        item_type       VARCHAR(30) DEFAULT 'cosmetic',
        data            JSON DEFAULT NULL,
        stock           INT DEFAULT -1,
        required_level  INT DEFAULT 0,
        active          TINYINT DEFAULT 1,
        INDEX idx_cat (category),
        INDEX idx_active (active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS discord_inventory (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        discord_id      VARCHAR(25) NOT NULL,
        item_id         INT NOT NULL,
        quantity        INT DEFAULT 1,
        equipped        TINYINT DEFAULT 0,
        acquired_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_user_item (discord_id, item_id),
        INDEX idx_user (discord_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS discord_server_config (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        guild_id        VARCHAR(25) UNIQUE NOT NULL,
        welcome_channel VARCHAR(25) DEFAULT NULL,
        welcome_message TEXT DEFAULT NULL,
        log_channel     VARCHAR(25) DEFAULT NULL,
        mod_role        VARCHAR(25) DEFAULT NULL,
        automod_enabled TINYINT DEFAULT 0,
        automod_rules   JSON DEFAULT NULL,
        level_up_channel VARCHAR(25) DEFAULT NULL,
        level_roles     JSON DEFAULT NULL,
        ticket_category VARCHAR(25) DEFAULT NULL,
        prefix          VARCHAR(5) DEFAULT '!',
        premium         TINYINT DEFAULT 0,
        settings        JSON DEFAULT NULL,
        INDEX idx_guild (guild_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS discord_afk (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        user_id         VARCHAR(25) NOT NULL,
        guild_id        VARCHAR(25) NOT NULL,
        message         VARCHAR(500) DEFAULT 'AFK',
        set_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_afk (user_id, guild_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS discord_birthdays (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        user_id         VARCHAR(25) NOT NULL,
        guild_id        VARCHAR(25) NOT NULL,
        birth_month     TINYINT NOT NULL,
        birth_day       TINYINT NOT NULL,
        UNIQUE KEY uniq_bday (user_id, guild_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS discord_todos (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        user_id         VARCHAR(25) NOT NULL,
        guild_id        VARCHAR(25) NOT NULL,
        task            VARCHAR(500) NOT NULL,
        completed       TINYINT DEFAULT 0,
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_todo (user_id, guild_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS discord_riddles (
        id              VARCHAR(10) PRIMARY KEY,
        answer          VARCHAR(500) NOT NULL,
        created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Seed shop items if empty
    $count = $pdo->query("SELECT COUNT(*) FROM discord_shop_items")->fetchColumn();
    if ($count == 0) {
        $pdo->exec("INSERT INTO discord_shop_items (name, description, emoji, price, category, item_type) VALUES
            ('Title: Lord', 'Unlock the Lord title for your profile', '👑', 500, 'titles', 'title'),
            ('Title: Champion', 'Unlock the Champion title', '🏆', 1000, 'titles', 'title'),
            ('Title: Legend', 'Unlock the legendary title', '⭐', 5000, 'titles', 'title'),
            ('Title: Sovereign', 'The ultimate title — Sovereign', '💎', 25000, 'titles', 'title'),
            ('Profile Badge: Fire', 'Add a fire badge to your profile', '🔥', 200, 'badges', 'badge'),
            ('Profile Badge: Star', 'Add a star badge', '⭐', 200, 'badges', 'badge'),
            ('Profile Badge: Crown', 'Add a crown badge', '👑', 500, 'badges', 'badge'),
            ('Profile Badge: Diamond', 'Diamond badge — rare!', '💎', 1500, 'badges', 'badge'),
            ('Profile Badge: Skull', 'Skull badge — intimidating!', '💀', 750, 'badges', 'badge'),
            ('XP Boost 2x', '2x XP for 24 hours', '⚡', 300, 'boosts', 'xp_boost'),
            ('KGD Boost 2x', '2x KGD earnings for 24 hours', '💰', 500, 'boosts', 'kgd_boost'),
            ('Lucky Charm', '+10% gambling win rate for 24h', '🍀', 400, 'boosts', 'luck_boost'),
            ('Chess Skin: Gold', 'Gold chess pieces in your games', '✨', 2000, 'skins', 'chess_skin'),
            ('Profile Color: Red', 'Red profile embed border', '🔴', 150, 'colors', 'profile_color'),
            ('Profile Color: Gold', 'Gold profile embed border', '🟡', 300, 'colors', 'profile_color'),
            ('Profile Color: Purple', 'Purple profile embed border', '🟣', 300, 'colors', 'profile_color'),
            ('Loot Box: Bronze', 'Contains 50-200 KGD + random item chance', '📦', 100, 'lootbox', 'lootbox'),
            ('Loot Box: Silver', 'Contains 200-1000 KGD + guaranteed item', '🎁', 500, 'lootbox', 'lootbox'),
            ('Loot Box: Gold', 'Contains 1000-5000 KGD + rare item!', '💫', 2000, 'lootbox', 'lootbox'),
            ('Hosting Coupon 10%', '10% off any GoSiteMe hosting plan', '🎟️', 10000, 'real', 'coupon')
        ");
    }
}


// ─── User Management ────────────────────────────────────────────────────

function getOrCreateUser(string $discordId, string $username): array {
    $pdo = getDiscordDB();
    $default = ['discord_id' => $discordId, 'discord_name' => $username, 'kgd_balance' => 100,
        'xp' => 0, 'level' => 1, 'elo_chess' => 1000, 'elo_checkers' => 1000,
        'games_played' => 0, 'games_won' => 0, 'title' => 'Peasant', 'daily_streak' => 0,
        'badges' => '[]', 'premium_tier' => 0, 'total_earned' => 0, 'total_spent' => 0,
        'trivia_correct' => 0, 'trivia_played' => 0, 'games_lost' => 0, 'games_drawn' => 0];
    if (!$pdo) return $default;

    $stmt = $pdo->prepare("SELECT * FROM discord_users WHERE discord_id = ?");
    $stmt->execute([$discordId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $refCode = strtoupper(substr(md5($discordId . time()), 0, 8));
        $pdo->prepare("INSERT INTO discord_users (discord_id, discord_name, kgd_balance, referral_code) VALUES (?, ?, 100, ?)")
            ->execute([$discordId, $username, $refCode]);
        $pdo->prepare("INSERT INTO discord_economy (discord_id, entry_type, amount, reason) VALUES (?, 'signup_bonus', 100, '🎉 Welcome to GoSiteMe!')")
            ->execute([$discordId]);
        $stmt->execute([$discordId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $pdo->prepare("UPDATE discord_users SET last_seen = NOW(), discord_name = ? WHERE discord_id = ?")
            ->execute([$username, $discordId]);
    }
    return $user ?: $default;
}

function awardXP(string $discordId, int $amount, string $appId = '', string $token = '', string $channelId = ''): void {
    $pdo = getDiscordDB();
    if (!$pdo) return;
    $pdo->prepare("UPDATE discord_users SET xp = xp + ? WHERE discord_id = ?")->execute([$amount, $discordId]);

    $user = $pdo->prepare("SELECT xp, level, discord_name FROM discord_users WHERE discord_id = ?");
    $user->execute([$discordId]);
    $u = $user->fetch(PDO::FETCH_ASSOC);
    if (!$u) return;

    $newLevel = calcLevel($u['xp']);
    if ($newLevel > $u['level']) {
        $reward = $newLevel * 50;
        $pdo->prepare("UPDATE discord_users SET level = ?, kgd_balance = kgd_balance + ?, total_earned = total_earned + ? WHERE discord_id = ?")
            ->execute([$newLevel, $reward, $reward, $discordId]);
        $pdo->prepare("INSERT INTO discord_economy (discord_id, entry_type, amount, reason) VALUES (?, 'earn', ?, ?)")
            ->execute([$discordId, $reward, "🎉 Level up to $newLevel!"]);

        if ($channelId && getenv('DISCORD_BOT_TOKEN')) {
            discordApi("/channels/$channelId/messages", 'POST', [
                'embeds' => [[
                    'title' => '🎉 LEVEL UP!',
                    'description' => "**{$u['discord_name']}** reached **Level $newLevel**!\n+$reward KGD bonus",
                    'color' => 0xFEE75C,
                    'thumbnail' => ['url' => 'https://gositeme.com/assets/images/logo-icon.png'],
                ]]
            ]);
        }
    }
}

function calcLevel(int $xp): int {
    // Each level needs level*100 XP. Level 1=0, Level 2=100, Level 3=300, etc.
    $level = 1;
    $needed = 0;
    while ($xp >= $needed + ($level * 100)) {
        $needed += $level * 100;
        $level++;
    }
    return $level;
}

function xpForNextLevel(int $xp, int $level): array {
    $needed = 0;
    for ($l = 1; $l < $level; $l++) $needed += $l * 100;
    $nextNeeded = $level * 100;
    $progress = $xp - $needed;
    return ['current' => $progress, 'needed' => $nextNeeded, 'pct' => $nextNeeded > 0 ? round($progress / $nextNeeded * 100) : 100];
}


// ─── Title & Badge System ───────────────────────────────────────────────

function eloTitle(int $elo): string {
    if ($elo >= 2400) return '👑 Grandmaster';
    if ($elo >= 2000) return '🏰 King';
    if ($elo >= 1800) return '⚔️ Duke';
    if ($elo >= 1600) return '🛡️ Earl';
    if ($elo >= 1400) return '🗡️ Baron';
    if ($elo >= 1200) return '🐴 Knight';
    return '🌾 Peasant';
}

function levelTitle(int $level): string {
    if ($level >= 100) return '🌟 Ascended';
    if ($level >= 75)  return '💎 Mythic';
    if ($level >= 50)  return '👑 Legendary';
    if ($level >= 35)  return '⚔️ Master';
    if ($level >= 25)  return '🏰 Expert';
    if ($level >= 15)  return '🛡️ Veteran';
    if ($level >= 10)  return '🗡️ Adept';
    if ($level >= 5)   return '🐴 Apprentice';
    return '🌾 Novice';
}

function xpBar(int $pct): string {
    $filled = (int)round($pct / 10);
    $empty = 10 - $filled;
    return str_repeat('█', $filled) . str_repeat('░', $empty) . " $pct%";
}

function generateReferralCode(string $discordId): string {
    return strtoupper(substr(md5($discordId . 'gositeme'), 0, 8));
}


// ─── Groq AI Helper ────────────────────────────────────────────────────

function getGroqKey(): string {
    static $key = null;
    if ($key !== null) return $key;
    $mcpEnv = dirname(dirname(__DIR__)) . '/gocodeme/mcp-server/.env';
    if (file_exists($mcpEnv)) {
        $c = file_get_contents($mcpEnv);
        if (preg_match('/GROQ_API_KEY=(.+)/', $c, $m)) { $key = trim($m[1]); return $key; }
    }
    $key = getenv('GROQ_API_KEY') ?: '';
    return $key;
}

function getTogetherKey(): string {
    static $key = null;
    if ($key !== null) return $key;
    $mcpEnv = dirname(dirname(__DIR__)) . '/gocodeme/mcp-server/.env';
    if (file_exists($mcpEnv)) {
        $c = file_get_contents($mcpEnv);
        if (preg_match('/TOGETHER_API_KEY=(.+)/', $c, $m)) { $key = trim($m[1]); return $key; }
    }
    $key = '';
    return $key;
}

function callGroq(string $systemPrompt, string $userMsg, float $temp = 0.7, int $maxTokens = 1024, string $model = 'llama-3.3-70b-versatile'): ?string {
    $key = getGroqKey();
    if (!$key) return null;

    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode([
            'model' => $model, 'max_tokens' => $maxTokens, 'temperature' => $temp,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userMsg],
            ],
        ]),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Authorization: Bearer ' . $key],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $r = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200) return null;
    $d = json_decode($r, true);
    return $d['choices'][0]['message']['content'] ?? null;
}


// ─── Embed Builder ──────────────────────────────────────────────────────

function embed(string $title, string $desc = '', int $color = 0x5865F2, array $fields = [], array $extra = []): array {
    $e = ['title' => $title, 'color' => $color];
    if ($desc) $e['description'] = $desc;
    if ($fields) $e['fields'] = $fields;
    return array_merge($e, $extra);
}

function field(string $name, string $value, bool $inline = true): array {
    return ['name' => $name, 'value' => $value, 'inline' => $inline];
}

function btn(int $style, string $label, string $customIdOrUrl, string $emoji = ''): array {
    $b = ['type' => 2, 'style' => $style, 'label' => $label];
    if ($style === 5) $b['url'] = $customIdOrUrl;
    else $b['custom_id'] = $customIdOrUrl;
    if ($emoji) $b['emoji'] = ['name' => $emoji];
    return $b;
}

function actionRow(array ...$components): array {
    return ['type' => 1, 'components' => $components];
}

function selectMenu(string $customId, string $placeholder, array $options, int $minValues = 1, int $maxValues = 1): array {
    return [
        'type' => 3,
        'custom_id' => $customId,
        'placeholder' => $placeholder,
        'min_values' => $minValues,
        'max_values' => $maxValues,
        'options' => $options,
    ];
}

function selectOption(string $label, string $value, string $desc = '', string $emoji = ''): array {
    $o = ['label' => $label, 'value' => $value];
    if ($desc) $o['description'] = $desc;
    if ($emoji) $o['emoji'] = ['name' => $emoji];
    return $o;
}

function textInput(string $customId, string $label, int $style = 1, bool $required = true, string $placeholder = '', int $maxLength = 1000): array {
    $t = ['type' => 4, 'custom_id' => $customId, 'label' => $label, 'style' => $style, 'required' => $required, 'max_length' => $maxLength];
    if ($placeholder) $t['placeholder'] = $placeholder;
    return $t;
}


// ─── Board Rendering ────────────────────────────────────────────────────

function renderChessBoard(string $fen): string {
    $pieces = [
        'K'=>'♔','Q'=>'♕','R'=>'♖','B'=>'♗','N'=>'♘','P'=>'♙',
        'k'=>'♚','q'=>'♛','r'=>'♜','b'=>'♝','n'=>'♞','p'=>'♟',
    ];
    $rows = explode('/', explode(' ', $fen)[0]);
    $board = "```\n  a b c d e f g h\n";
    $rank = 8;
    foreach ($rows as $row) {
        $line = "$rank ";
        for ($i = 0; $i < strlen($row); $i++) {
            $c = $row[$i];
            if (is_numeric($c)) $line .= str_repeat('· ', (int)$c);
            else $line .= ($pieces[$c] ?? $c) . ' ';
        }
        $board .= "$line$rank\n";
        $rank--;
    }
    return $board . "  a b c d e f g h\n```";
}

function renderCheckersBoard(): string {
    $rows = [
        ['·','⚫','·','⚫','·','⚫','·','⚫'],
        ['⚫','·','⚫','·','⚫','·','⚫','·'],
        ['·','⚫','·','⚫','·','⚫','·','⚫'],
        ['·','·','·','·','·','·','·','·'],
        ['·','·','·','·','·','·','·','·'],
        ['🔴','·','🔴','·','🔴','·','🔴','·'],
        ['·','🔴','·','🔴','·','🔴','·','🔴'],
        ['🔴','·','🔴','·','🔴','·','🔴','·'],
    ];
    $board = "```\n  1 2 3 4 5 6 7 8\n";
    foreach ($rows as $i => $row) $board .= ($i+1) . ' ' . implode(' ', $row) . "\n";
    return $board . "```";
}


// ─── Utility Functions ──────────────────────────────────────────────────

function getOption(array $data, string $name) {
    foreach (($data['data']['options'] ?? []) as $opt) {
        if ($opt['name'] === $name) return $opt['value'] ?? null;
        // Handle subcommands
        if (isset($opt['options'])) {
            foreach ($opt['options'] as $sub) {
                if ($sub['name'] === $name) return $sub['value'] ?? null;
            }
        }
    }
    return null;
}

function getSubcommand(array $data): ?string {
    foreach (($data['data']['options'] ?? []) as $opt) {
        if (($opt['type'] ?? 0) === 1) return $opt['name'];
    }
    return null;
}

function getSubOptions(array $data): array {
    foreach (($data['data']['options'] ?? []) as $opt) {
        if (($opt['type'] ?? 0) === 1) {
            $opts = [];
            foreach (($opt['options'] ?? []) as $sub) $opts[$sub['name']] = $sub['value'] ?? null;
            return $opts;
        }
    }
    $opts = [];
    foreach (($data['data']['options'] ?? []) as $opt) $opts[$opt['name']] = $opt['value'] ?? null;
    return $opts;
}

function hasPerm(array $data, int $perm): bool {
    $perms = (int)($data['member']['permissions'] ?? 0);
    return ($perms & $perm) === $perm;
}

const PERM_ADMIN = 0x8;
const PERM_MANAGE_GUILD = 0x20;
const PERM_KICK = 0x2;
const PERM_BAN = 0x4;
const PERM_MANAGE_MESSAGES = 0x2000;
const PERM_MANAGE_ROLES = 0x10000000;

function httpGet(string $url, int $timeout = 10): ?string {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT      => 'GoSiteMe-Bot/1.0',
    ]);
    $r = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $code === 200 ? $r : null;
}

function truncate(string $s, int $max = 2000): string {
    return strlen($s) > $max ? substr($s, 0, $max - 3) . '...' : $s;
}
