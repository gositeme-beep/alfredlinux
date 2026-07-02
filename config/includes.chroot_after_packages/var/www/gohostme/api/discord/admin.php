<?php
/**
 * GoSiteMe Discord Bot — Admin & System Module
 * Commands: /health, /botlogs, /botstats, /serverban, /backup
 * Owner-only administrative commands for system monitoring
 */

namespace GoSiteMe\Discord;
require_once __DIR__ . '/core.php';

define('BOT_OWNER_ID', getenv('DISCORD_BOT_OWNER_ID') ?: '');

function isOwner(string $userId): bool {
    if (!BOT_OWNER_ID) {
        error_log('[discord-admin] BOT_OWNER_ID not configured — all admin commands blocked');
        return false;
    }
    return $userId === BOT_OWNER_ID;
}

// ─── /health ───────────────────────────────────────────────────────────
function handleHealth(array $data): void {
    $userId = $data['member']['user']['id'];
    $username = $data['member']['user']['username'];
    $appId = $data['application_id'];
    $token = $data['token'];

    deferResponse();
    getOrCreateUser($userId, $username);

    $checks = [];

    // Database
    $dbStart = microtime(true);
    $pdo = getDiscordDB();
    $dbMs = round((microtime(true) - $dbStart) * 1000);
    $checks[] = $pdo ? "✅ Database: **{$dbMs}ms**" : "❌ Database: **DOWN**";

    // DB stats
    if ($pdo) {
        $userCount = $pdo->query("SELECT COUNT(*) FROM discord_users")->fetchColumn();
        $gameCount = $pdo->query("SELECT COUNT(*) FROM discord_games")->fetchColumn();
        $txCount = $pdo->query("SELECT COUNT(*) FROM discord_economy")->fetchColumn();
        $checks[] = "👥 Users: **$userCount** | 🎮 Games: **$gameCount** | 💰 Transactions: **$txCount**";
    }

    // Groq API
    $groqStart = microtime(true);
    $groqTest = callGroq("Reply with OK", "test", 0.1, 5);
    $groqMs = round((microtime(true) - $groqStart) * 1000);
    $checks[] = $groqTest ? "✅ Groq AI: **{$groqMs}ms**" : "❌ Groq AI: **DOWN**";

    // Discord API
    $dcStart = microtime(true);
    $dcTest = discordApi('/users/@me');
    $dcMs = round((microtime(true) - $dcStart) * 1000);
    $checks[] = $dcTest ? "✅ Discord API: **{$dcMs}ms**" : "❌ Discord API: **DOWN**";

    // Server
    $load = sys_getloadavg();
    $loadStr = implode(' / ', array_map(fn($l) => round($l, 2), $load));
    $mem = round(memory_get_usage(true) / 1024 / 1024, 1);
    $disk = round(disk_free_space('/') / 1024 / 1024 / 1024, 1);
    $uptime = trim(shell_exec('uptime -p') ?: 'Unknown');

    $checks[] = "🖥️ Load: **$loadStr**";
    $checks[] = "💾 Memory: **{$mem}MB** | Disk: **{$disk}GB free**";
    $checks[] = "⏱️ Uptime: $uptime";
    $checks[] = "🐘 PHP: **" . PHP_VERSION . "** | 🕐 Time: **" . date('Y-m-d H:i:s T') . "**";

    followUp($appId, $token, '', [embed(
        "🏥 System Health Check",
        implode("\n", $checks),
        0x2ECC71,
        [],
        ['footer' => ['text' => 'GoSiteMe Bot v3.0 Health Monitor']]
    )], [actionRow(
        btn(2, '🔄 Refresh', 'health_refresh'),
        btn(5, '📊 Status Page', 'https://gositeme.com/status.php')
    )]);
}

// ─── /botlogs ──────────────────────────────────────────────────────────
function handleBotlogs(array $data): void {
    $userId = $data['member']['user']['id'];
    if (!isOwner($userId)) { respondEphemeral('❌ Owner-only command.'); return; }

    $appId = $data['application_id'];
    $token = $data['token'];
    deferResponse(true);

    $opts = $data['data']['options'] ?? [];
    $lines = 20;
    foreach ($opts as $o) { if ($o['name'] === 'lines') $lines = min((int)$o['value'], 50); }

    $logFile = dirname(dirname(__DIR__)) . '/logs/discord-bot.log';
    $content = 'No log file found.';
    if (file_exists($logFile)) {
        $allLines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $recent = array_slice($allLines, -$lines);
        $content = "```\n" . implode("\n", $recent) . "\n```";
    }

    editOriginal($appId, $token, '', [embed(
        "📋 Bot Logs (last $lines lines)",
        truncate($content, 4000),
        0xFFA000
    )]);
}

// ─── /botstats ─────────────────────────────────────────────────────────
function handleBotstats(array $data): void {
    $userId = $data['member']['user']['id'];
    $username = $data['member']['user']['username'];
    $appId = $data['application_id'];
    $token = $data['token'];

    deferResponse();
    getOrCreateUser($userId, $username);

    $pdo = getDiscordDB();
    if (!$pdo) { editOriginal($appId, $token, '❌ Database unavailable.'); return; }

    // User stats
    $totalUsers = $pdo->query("SELECT COUNT(*) FROM discord_users")->fetchColumn();
    $activeToday = $pdo->query("SELECT COUNT(*) FROM discord_users WHERE last_seen >= CURDATE()")->fetchColumn();
    $activeWeek = $pdo->query("SELECT COUNT(*) FROM discord_users WHERE last_seen >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetchColumn();

    // Economy stats
    $totalKGD = $pdo->query("SELECT SUM(kgd_balance) FROM discord_users")->fetchColumn() ?: 0;
    $totalEarned = $pdo->query("SELECT SUM(total_earned) FROM discord_users")->fetchColumn() ?: 0;
    $totalSpent = $pdo->query("SELECT SUM(total_spent) FROM discord_users")->fetchColumn() ?: 0;

    // Game stats
    $totalGames = $pdo->query("SELECT COUNT(*) FROM discord_games")->fetchColumn();
    $activeGames = $pdo->query("SELECT COUNT(*) FROM discord_games WHERE status = 'active'")->fetchColumn();

    // Top users
    $topXP = $pdo->query("SELECT discord_name, xp FROM discord_users ORDER BY xp DESC LIMIT 3")->fetchAll(\PDO::FETCH_ASSOC);
    $topKGD = $pdo->query("SELECT discord_name, kgd_balance FROM discord_users ORDER BY kgd_balance DESC LIMIT 3")->fetchAll(\PDO::FETCH_ASSOC);

    $topXPStr = '';
    foreach ($topXP as $i => $u) {
        $medals = ['🥇', '🥈', '🥉'];
        $topXPStr .= ($medals[$i] ?? '') . " **{$u['discord_name']}** — " . number_format($u['xp']) . " XP\n";
    }

    $topKGDStr = '';
    foreach ($topKGD as $i => $u) {
        $medals = ['🥇', '🥈', '🥉'];
        $topKGDStr .= ($medals[$i] ?? '') . " **{$u['discord_name']}** — " . number_format($u['kgd_balance']) . " KGD\n";
    }

    followUp($appId, $token, '', [embed(
        "📊 Bot Statistics",
        '',
        0x3498DB,
        [
            field('👥 Total Users', number_format($totalUsers), true),
            field('🟢 Active Today', number_format($activeToday), true),
            field('📅 Active (7d)', number_format($activeWeek), true),
            field('💰 Total KGD Supply', number_format($totalKGD), true),
            field('📈 Total Earned', number_format($totalEarned), true),
            field('📉 Total Spent', number_format($totalSpent), true),
            field('🎮 Total Games', number_format($totalGames), true),
            field('▶️ Active Games', number_format($activeGames), true),
            field('🤖 Commands', '88', true),
            field('🏆 XP Leaders', $topXPStr ?: 'None yet', false),
            field('💎 KGD Leaders', $topKGDStr ?: 'None yet', false),
        ],
        ['footer' => ['text' => 'GoSiteMe Bot Analytics']]
    )], [actionRow(
        btn(2, '🔄 Refresh', 'stats_refresh'),
        btn(2, '👤 My Profile', 'profile_view')
    )]);
}

// ─── /serverban ────────────────────────────────────────────────────────
function handleServerban(array $data): void {
    $userId = $data['member']['user']['id'];
    $appId = $data['application_id'];
    $token = $data['token'];

    // Check permissions (MANAGE_GUILD = 0x20)
    $permissions = (int)($data['member']['permissions'] ?? 0);
    if (!($permissions & 0x20) && !isOwner($userId)) {
        respondEphemeral('❌ You need **Manage Server** permission.');
        return;
    }

    deferResponse(true);

    $guildId = $data['guild_id'] ?? '';
    if (!$guildId) { editOriginal($appId, $token, '❌ Guild not found.'); return; }

    $bans = discordApi("/guilds/$guildId/bans?limit=50");
    if (!$bans || !is_array($bans)) {
        editOriginal($appId, $token, '❌ Could not fetch ban list. Missing permissions?');
        return;
    }

    if (empty($bans)) {
        editOriginal($appId, $token, '', [embed("🔨 Server Ban List", "No bans found — this server is clean! 🎉", 0x2ECC71)]);
        return;
    }

    $list = '';
    foreach (array_slice($bans, 0, 25) as $ban) {
        $user = $ban['user'] ?? [];
        $name = $user['username'] ?? 'Unknown';
        $id = $user['id'] ?? '?';
        $reason = $ban['reason'] ?? 'No reason provided';
        $list .= "**$name** (`$id`)\n> $reason\n";
    }

    $total = count($bans);
    editOriginal($appId, $token, '', [embed(
        "🔨 Server Ban List ($total bans)",
        truncate($list, 4000),
        0xE74C3C,
        [],
        ['footer' => ['text' => "Showing up to 25 of $total bans"]]
    )]);
}

// ─── /backup ───────────────────────────────────────────────────────────
function handleBackup(array $data): void {
    $userId = $data['member']['user']['id'];
    if (!isOwner($userId)) { respondEphemeral('❌ Owner-only command.'); return; }

    $appId = $data['application_id'];
    $token = $data['token'];
    deferResponse(true);

    $pdo = getDiscordDB();
    if (!$pdo) { editOriginal($appId, $token, '❌ Database unavailable.'); return; }

    $tables = $pdo->query("SHOW TABLES LIKE 'discord_%'")->fetchAll(\PDO::FETCH_COLUMN);
    $stats = [];
    $totalRows = 0;

    foreach ($tables as $table) {
        $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        $totalRows += $count;
        $stats[] = "`$table` — **$count** rows";
    }

    editOriginal($appId, $token, '', [embed(
        "💾 Database Overview",
        implode("\n", $stats) . "\n\n**Total:** $totalRows rows across " . count($tables) . " tables",
        0x9B59B6,
        [],
        ['footer' => ['text' => 'GoSiteMe Discord Database']]
    )]);
}
