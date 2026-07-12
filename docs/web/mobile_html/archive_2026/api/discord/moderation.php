<?php
/**
 * GoSiteMe Discord Bot — Moderation Module
 * ═════════════════════════════════════════
 * /mod     — kick, ban, mute, warn, purge, unban
 * /automod — Auto-moderation configuration
 * /audit   — View moderation logs
 */

function handleMod(array $data): void {
    $subCmd = getSubcommand($data);
    $opts = getSubOptions($data);
    $userId = $data['member']['user']['id'] ?? '0';
    $globalName = $data['member']['user']['global_name'] ?? $data['member']['user']['username'] ?? 'User';
    $guildId = $data['guild_id'] ?? '';
    $channelId = $data['channel_id'] ?? '';
    $pdo = getDiscordDB();

    switch ($subCmd) {
        case 'kick':
            if (!hasPerm($data, PERM_KICK)) { respondEphemeral("You need **Kick Members** permission."); return; }
            $target = $opts['user'] ?? null;
            $reason = $opts['reason'] ?? 'No reason specified';
            if (!$target) { respond("Specify a user! `/mod kick user:@user reason:spam`"); return; }
            if ($target === $userId) { respond("You can't kick yourself!"); return; }

            discordApi("/guilds/$guildId/members/$target", 'DELETE');
            if ($pdo) {
                $pdo->prepare("INSERT INTO discord_warnings (guild_id, user_id, moderator_id, reason, action_taken) VALUES (?, ?, ?, ?, 'kick')")
                    ->execute([$guildId, $target, $userId, $reason]);
            }
            respond(null, [embed("👢 User Kicked", "<@$target> was kicked by **$globalName**\n\n📝 **Reason:** $reason", 0xED4245)]);
            break;

        case 'ban':
            if (!hasPerm($data, PERM_BAN)) { respondEphemeral("You need **Ban Members** permission."); return; }
            $target = $opts['user'] ?? null;
            $reason = $opts['reason'] ?? 'No reason specified';
            $days = max(0, min(7, (int)($opts['delete_days'] ?? 0)));
            if (!$target) { respond("Specify a user! `/mod ban user:@user`"); return; }
            if ($target === $userId) { respond("You can't ban yourself!"); return; }

            discordApi("/guilds/$guildId/bans/$target", 'PUT', ['delete_message_days' => $days]);
            if ($pdo) {
                $pdo->prepare("INSERT INTO discord_warnings (guild_id, user_id, moderator_id, reason, action_taken) VALUES (?, ?, ?, ?, 'ban')")
                    ->execute([$guildId, $target, $userId, $reason]);
            }
            respond(null, [embed("🔨 User Banned", "<@$target> was banned by **$globalName**\n\n📝 **Reason:** $reason\n🗑️ Messages deleted: last $days days", 0xED4245)]);
            break;

        case 'unban':
            if (!hasPerm($data, PERM_BAN)) { respondEphemeral("Need **Ban Members** permission."); return; }
            $targetId = $opts['user_id'] ?? '';
            if (!$targetId || !ctype_digit($targetId)) { respond("Provide a user ID! `/mod unban user_id:123456789`"); return; }
            discordApi("/guilds/$guildId/bans/$targetId", 'DELETE');
            respond(null, [embed("✅ User Unbanned", "User `$targetId` has been unbanned by **$globalName**.", 0x57F287)]);
            break;

        case 'mute':
            if (!hasPerm($data, PERM_MANAGE_ROLES)) { respondEphemeral("Need **Manage Roles** permission."); return; }
            $target = $opts['user'] ?? null;
            $duration = $opts['duration'] ?? '10m';
            $reason = $opts['reason'] ?? 'No reason specified';
            if (!$target) { respond("Specify a user! `/mod mute user:@user duration:10m`"); return; }

            // Parse duration for timeout
            preg_match('/(\d+)\s*(m|h|d)/', $duration, $m);
            $val = (int)($m[1] ?? 10);
            $unit = $m[2] ?? 'm';
            $seconds = $val * ['m' => 60, 'h' => 3600, 'd' => 86400][$unit];
            $seconds = min($seconds, 2419200); // Max 28 days
            $until = date('c', time() + $seconds);

            discordApi("/guilds/$guildId/members/$target", 'PATCH', ['communication_disabled_until' => $until]);
            if ($pdo) {
                $pdo->prepare("INSERT INTO discord_warnings (guild_id, user_id, moderator_id, reason, action_taken) VALUES (?, ?, ?, ?, 'mute')")
                    ->execute([$guildId, $target, $userId, "$reason (duration: $duration)"]);
            }
            respond(null, [embed("🔇 User Muted", "<@$target> was muted by **$globalName**\n\n📝 **Reason:** $reason\n⏱️ **Duration:** $duration", 0xFEE75C)]);
            break;

        case 'unmute':
            if (!hasPerm($data, PERM_MANAGE_ROLES)) { respondEphemeral("Need **Manage Roles** permission."); return; }
            $target = $opts['user'] ?? null;
            if (!$target) { respond("Specify a user!"); return; }
            discordApi("/guilds/$guildId/members/$target", 'PATCH', ['communication_disabled_until' => null]);
            respond(null, [embed("🔊 User Unmuted", "<@$target> was unmuted by **$globalName**.", 0x57F287)]);
            break;

        case 'warn':
            if (!hasPerm($data, PERM_MANAGE_MESSAGES)) { respondEphemeral("Need **Manage Messages** permission."); return; }
            $target = $opts['user'] ?? null;
            $reason = $opts['reason'] ?? 'No reason specified';
            if (!$target) { respond("Specify a user!"); return; }

            if ($pdo) {
                $pdo->prepare("INSERT INTO discord_warnings (guild_id, user_id, moderator_id, reason, action_taken) VALUES (?, ?, ?, ?, 'warn')")
                    ->execute([$guildId, $target, $userId, $reason]);
                $count = $pdo->prepare("SELECT COUNT(*) FROM discord_warnings WHERE guild_id = ? AND user_id = ?");
                $count->execute([$guildId, $target]);
                $warnCount = $count->fetchColumn();

                $extra = '';
                if ($warnCount >= 5) $extra = "\n\n⚠️ **This user has $warnCount warnings!** Consider a mute or ban.";
                elseif ($warnCount >= 3) $extra = "\n\n⚠️ $warnCount warnings — next offense may result in a mute.";

                respond(null, [embed("⚠️ Warning Issued", "<@$target> was warned by **$globalName**\n\n📝 **Reason:** $reason\n📋 **Total Warnings:** $warnCount$extra", 0xFEE75C)]);
            } else {
                respond(null, [embed("⚠️ Warning", "<@$target> warned: $reason", 0xFEE75C)]);
            }
            break;

        case 'warnings':
            $target = $opts['user'] ?? $userId;
            if (!$pdo) { respond("⚠️ Database unavailable."); return; }
            $stmt = $pdo->prepare("SELECT * FROM discord_warnings WHERE guild_id = ? AND user_id = ? ORDER BY created_at DESC LIMIT 10");
            $stmt->execute([$guildId, $target]);
            $warns = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $desc = '';
            foreach ($warns as $w) {
                $actions = ['warn' => '⚠️', 'kick' => '👢', 'ban' => '🔨', 'mute' => '🔇'];
                $icon = $actions[$w['action_taken']] ?? '📋';
                $desc .= "$icon **{$w['action_taken']}** — {$w['reason']}\n   By <@{$w['moderator_id']}> on {$w['created_at']}\n\n";
            }
            if (!$desc) $desc = 'No warnings for this user.';

            respond(null, [embed("📋 Warnings for <@$target>", $desc, 0xFEE75C, [
                field('Total', (string)count($warns), true),
            ])]);
            break;

        case 'purge':
            if (!hasPerm($data, PERM_MANAGE_MESSAGES)) { respondEphemeral("Need **Manage Messages** permission."); return; }
            $count = max(1, min(100, (int)($opts['count'] ?? 10)));

            // Get messages then bulk delete
            $msgs = discordApi("/channels/$channelId/messages?limit=$count");
            if (!$msgs || !is_array($msgs)) { respond("Failed to fetch messages."); return; }

            $ids = array_map(fn($m) => $m['id'], $msgs);
            if (count($ids) > 1) {
                discordApi("/channels/$channelId/messages/bulk-delete", 'POST', ['messages' => $ids]);
            } elseif (count($ids) === 1) {
                discordApi("/channels/$channelId/messages/{$ids[0]}", 'DELETE');
            }

            respondEphemeral("🗑️ Purged **" . count($ids) . "** messages.");
            if ($pdo) {
                $pdo->prepare("INSERT INTO discord_warnings (guild_id, user_id, moderator_id, reason, action_taken) VALUES (?, 'channel', ?, ?, 'purge')")
                    ->execute([$guildId, $userId, "Purged " . count($ids) . " messages in <#$channelId>"]);
            }
            break;

        case 'slowmode':
            if (!hasPerm($data, PERM_MANAGE_MESSAGES)) { respondEphemeral("Need **Manage Messages** permission."); return; }
            $seconds = max(0, min(21600, (int)($opts['seconds'] ?? 0)));
            discordApi("/channels/$channelId", 'PATCH', ['rate_limit_per_user' => $seconds]);
            if ($seconds === 0) respond("✅ Slowmode disabled in this channel.");
            else respond("✅ Slowmode set to **{$seconds}s** in this channel.");
            break;

        default:
            respond("🛡️ **Moderation Commands:**\n"
                . "`/mod kick` — Kick a user\n`/mod ban` — Ban a user\n`/mod unban` — Unban by ID\n"
                . "`/mod mute` — Timeout a user\n`/mod unmute` — Remove timeout\n`/mod warn` — Issue a warning\n"
                . "`/mod warnings` — View warnings\n`/mod purge` — Delete messages\n`/mod slowmode` — Set slowmode");
    }
}


function handleAutomod(array $data): void {
    if (!hasPerm($data, PERM_ADMIN)) { respondEphemeral("You need **Administrator** permission."); return; }

    $subCmd = getSubcommand($data);
    $opts = getSubOptions($data);
    $guildId = $data['guild_id'] ?? '';
    $pdo = getDiscordDB();
    if (!$pdo) { respond("⚠️ Database unavailable."); return; }

    switch ($subCmd ?? 'status') {
        case 'enable':
            $pdo->prepare("INSERT INTO discord_server_config (guild_id, automod_enabled) VALUES (?, 1) ON DUPLICATE KEY UPDATE automod_enabled = 1")
                ->execute([$guildId]);
            respond(null, [embed("🛡️ AutoMod Enabled", "Automatic moderation is now **active** for this server.\n\nDefault rules:\n• Anti-spam (5 msgs/5s)\n• Anti-invite links\n• Excessive caps detection\n• Mass mention prevention (5+)", 0x57F287)]);
            break;

        case 'disable':
            $pdo->prepare("UPDATE discord_server_config SET automod_enabled = 0 WHERE guild_id = ?")->execute([$guildId]);
            respond(null, [embed("🛡️ AutoMod Disabled", "Automatic moderation has been **disabled**.", 0xED4245)]);
            break;

        case 'status':
            $stmt = $pdo->prepare("SELECT * FROM discord_server_config WHERE guild_id = ?");
            $stmt->execute([$guildId]);
            $config = $stmt->fetch(PDO::FETCH_ASSOC);
            $enabled = ($config['automod_enabled'] ?? 0) ? '🟢 Enabled' : '🔴 Disabled';
            $rules = json_decode($config['automod_rules'] ?? '{}', true);

            respond(null, [embed("🛡️ AutoMod Status", '', 0x3498DB, [
                field('Status', $enabled, true),
                field('Anti-Spam', ($rules['anti_spam'] ?? true) ? '✅' : '❌', true),
                field('Anti-Links', ($rules['anti_links'] ?? true) ? '✅' : '❌', true),
                field('Anti-Caps', ($rules['anti_caps'] ?? true) ? '✅' : '❌', true),
                field('Mass Mentions', ($rules['anti_mentions'] ?? true) ? '✅' : '❌', true),
                field('Log Channel', $config['log_channel'] ? '<#' . $config['log_channel'] . '>' : 'Not set', true),
            ])]);
            break;
    }
}


function handleAudit(array $data): void {
    if (!hasPerm($data, PERM_MANAGE_GUILD)) { respondEphemeral("Need **Manage Server** permission."); return; }

    $opts = getSubOptions($data);
    $guildId = $data['guild_id'] ?? '';
    $pdo = getDiscordDB();
    if (!$pdo) { respond("⚠️ Database unavailable."); return; }

    $filter = $opts['action'] ?? 'all';
    $where = $filter === 'all' ? '' : " AND action_taken = " . $pdo->quote($filter);
    $stmt = $pdo->query("SELECT * FROM discord_warnings WHERE guild_id = " . $pdo->quote($guildId) . "$where ORDER BY created_at DESC LIMIT 15");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $icons = ['warn' => '⚠️', 'kick' => '👢', 'ban' => '🔨', 'mute' => '🔇', 'purge' => '🗑️'];
    $desc = '';
    foreach ($logs as $l) {
        $icon = $icons[$l['action_taken']] ?? '📋';
        $target = $l['user_id'] === 'channel' ? '#channel' : "<@{$l['user_id']}>";
        $desc .= "$icon **{$l['action_taken']}** — $target\n   {$l['reason']} | <@{$l['moderator_id']}>\n   " . date('M j H:i', strtotime($l['created_at'])) . "\n\n";
    }
    if (!$desc) $desc = 'No moderation actions found.';

    respond(null, [embed("📋 Moderation Audit Log", $desc, 0x95A5A6, [
        field('Filter', ucfirst($filter), true),
        field('Total Actions', (string)count($logs), true),
    ])]);
}
