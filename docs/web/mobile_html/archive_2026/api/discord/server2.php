<?php
/**
 * GoSiteMe Discord Bot — Server Management Module
 * ════════════════════════════════════════════════
 * /server (reactionroles|starboard|welcome|autorole|counting|slowmode)
 * Discord server configuration and management tools.
 */

function handleServer($data): void {
    $sub = $data['data']['options'][0]['name'] ?? 'welcome';
    $opts = [];
    foreach (($data['data']['options'][0]['options'] ?? []) as $o) {
        $opts[$o['name']] = $o['value'];
    }

    $userId   = $data['member']['user']['id'] ?? ($data['user']['id'] ?? '');
    $username = $data['member']['user']['username'] ?? ($data['user']['username'] ?? 'User');
    $guildId  = $data['guild_id'] ?? '';
    $db       = getDiscordDB();
    if (!$db) { respond('❌ Database unavailable.'); return; }

    $db->exec("CREATE TABLE IF NOT EXISTS discord_server_config (
        id INT AUTO_INCREMENT PRIMARY KEY,
        guild_id VARCHAR(32) NOT NULL,
        config_key VARCHAR(100) NOT NULL,
        config_value TEXT NOT NULL,
        metadata JSON,
        updated_by VARCHAR(32),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_guild_key (guild_id, config_key),
        INDEX idx_guild (guild_id)
    )");

    // Helper: check admin permissions
    $isAdmin = function() use ($data): bool {
        $perms = $data['member']['permissions'] ?? '0';
        return ((int)$perms & 0x8) === 0x8; // ADMINISTRATOR
    };

    // Helper: get/set config
    $getConfig = function(string $key, string $default = '') use ($db, $guildId): string {
        $stmt = $db->prepare("SELECT config_value FROM discord_server_config WHERE guild_id = ? AND config_key = ?");
        $stmt->execute([$guildId, $key]);
        return $stmt->fetchColumn() ?: $default;
    };

    $setConfig = function(string $key, string $value, $meta = null) use ($db, $guildId, $userId): void {
        $stmt = $db->prepare("INSERT INTO discord_server_config (guild_id, config_key, config_value, metadata, updated_by) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE config_value = VALUES(config_value), metadata = VALUES(metadata), updated_by = VALUES(updated_by)");
        $stmt->execute([$guildId, $key, $value, $meta ? json_encode($meta) : null, $userId]);
    };

    if (!$guildId) {
        respondEphemeral('❌ This command can only be used in a server.');
        return;
    }

    switch ($sub) {
        case 'reactionroles':
            if (!$isAdmin()) { respondEphemeral('❌ Admin permission required.'); return; }

            $action = $opts['action'] ?? 'create';

            if ($action === 'create') {
                $title = $opts['title'] ?? 'Role Selection';
                $description = $opts['description'] ?? 'React to get a role!';

                // Store the config
                $setConfig('reaction_roles_title', $title);
                $setConfig('reaction_roles_desc', $description);

                respond(null, [embed("🎭 Reaction Roles Setup", "**$title**\n\n$description\n\n**How to complete setup:**\n1. Use Alfred's admin panel to assign emoji→role mappings\n2. Members react to get the role automatically\n\n⚠️ Requires `Manage Roles` bot permission", 0x9B59B6, [
                    field('Created By', "<@$userId>", true),
                    field('Guild', $guildId, true),
                ], [
                    'footer' => ['text' => "Reaction Roles • Server Management"],
                ])], [actionRow(
                    btn(2, '⚙️ Server Settings', 'server_settings'),
                    btn(2, '📋 View Config', 'server_config_view')
                )]);
            } else {
                // List existing
                $title = $getConfig('reaction_roles_title', 'Not configured');
                respond(null, [embed("🎭 Reaction Roles", "**Current Config:**\nTitle: $title\n\nUse `/server reactionroles create` to set up new reaction roles.", 0x9B59B6)]);
            }
            break;

        case 'starboard':
            if (!$isAdmin()) { respondEphemeral('❌ Admin permission required.'); return; }

            $channel = $opts['channel'] ?? '';
            $threshold = (int)($opts['threshold'] ?? 3);
            $threshold = max(1, min(25, $threshold));

            if ($channel) {
                $setConfig('starboard_channel', $channel);
                $setConfig('starboard_threshold', (string)$threshold);

                respond(null, [embed("⭐ Starboard Configured", "**Channel:** <#$channel>\n**Threshold:** $threshold ⭐ reactions\n\nMessages reaching $threshold stars will be pinned to the starboard!", 0xF1C40F, [], [
                    'footer' => ['text' => "Set by $username"],
                ])], [actionRow(
                    btn(2, '⚙️ Server Settings', 'server_settings')
                )]);
            } else {
                $currentChannel = $getConfig('starboard_channel', 'Not set');
                $currentThreshold = $getConfig('starboard_threshold', '3');
                respond(null, [embed("⭐ Starboard Settings", "**Channel:** " . ($currentChannel !== 'Not set' ? "<#$currentChannel>" : 'Not set') . "\n**Threshold:** $currentThreshold ⭐\n\nUse `/server starboard channel:<#channel> threshold:<number>` to configure.", 0xF1C40F)]);
            }
            break;

        case 'welcome':
            if (!$isAdmin()) { respondEphemeral('❌ Admin permission required.'); return; }

            $message = $opts['message'] ?? '';
            $channel = $opts['channel'] ?? '';

            if ($message) {
                $setConfig('welcome_message', $message);
                if ($channel) $setConfig('welcome_channel', $channel);

                $preview = str_replace(['{user}', '{server}', '{count}'], ["<@$userId>", 'Server Name', '100'], $message);

                respond(null, [embed("👋 Welcome Message Set", "**Template:**\n```\n$message\n```\n**Preview:**\n$preview\n\n**Variables:**\n`{user}` — Mentions the user\n`{server}` — Server name\n`{count}` — Member count", 0x2ECC71, $channel ? [field('Channel', "<#$channel>", true)] : [], [
                    'footer' => ['text' => "Set by $username"],
                ])], [actionRow(
                    btn(2, '⚙️ Server Settings', 'server_settings')
                )]);
            } else {
                $current = $getConfig('welcome_message', 'Not set');
                $ch = $getConfig('welcome_channel', 'Not set');
                respond(null, [embed("👋 Welcome Settings", "**Message:** " . ($current !== 'Not set' ? "`$current`" : 'Not configured') . "\n**Channel:** " . ($ch !== 'Not set' ? "<#$ch>" : 'Not set') . "\n\nUse `/server welcome message:<text> channel:<#channel>` to configure.", 0x2ECC71)]);
            }
            break;

        case 'autorole':
            if (!$isAdmin()) { respondEphemeral('❌ Admin permission required.'); return; }

            $role = $opts['role'] ?? '';

            if ($role) {
                $setConfig('autorole', $role);
                respond(null, [embed("🎯 Auto-Role Set", "New members will automatically receive <@&$role>\n\n⚠️ Requires `Manage Roles` bot permission and the bot's role must be higher than the target role.", 0x3498DB, [], [
                    'footer' => ['text' => "Set by $username"],
                ])], [actionRow(
                    btn(2, '⚙️ Server Settings', 'server_settings'),
                    btn(3, '🗑️ Remove', 'server_autorole_remove')
                )]);
            } else {
                $current = $getConfig('autorole', 'Not set');
                respond(null, [embed("🎯 Auto-Role", "**Current:** " . ($current !== 'Not set' ? "<@&$current>" : 'Not configured') . "\n\nUse `/server autorole role:<@role>` to set.", 0x3498DB)]);
            }
            break;

        case 'counting':
            $channel = $opts['channel'] ?? '';

            if ($channel) {
                if (!$isAdmin()) { respondEphemeral('❌ Admin permission required.'); return; }
                $setConfig('counting_channel', $channel);
                $setConfig('counting_current', '0');

                respond(null, [embed("🔢 Counting Game Set Up", "**Channel:** <#$channel>\n**Current Number:** 0\n\nMembers count sequentially in the channel. Wrong numbers reset the count!", 0xF39C12, [], [
                    'footer' => ['text' => "Set by $username"],
                ])]);
            } else {
                $ch = $getConfig('counting_channel', '');
                $current = $getConfig('counting_current', '0');
                if ($ch) {
                    respond(null, [embed("🔢 Counting Game", "**Channel:** <#$ch>\n**Current Number:** $current\n**Next:** " . ((int)$current + 1), 0xF39C12)]);
                } else {
                    respond(null, [embed("🔢 Counting Game", "Not configured. An admin can set it up with:\n`/server counting channel:<#channel>`", 0x95A5A6)]);
                }
            }
            break;

        case 'slowmode':
            if (!$isAdmin()) { respondEphemeral('❌ Admin permission required.'); return; }

            $channel = $opts['channel'] ?? '';
            $seconds = (int)($opts['seconds'] ?? 0);
            $seconds = max(0, min(21600, $seconds)); // Discord max slowmode

            if (!$channel) { respondEphemeral('❌ Specify a channel.'); return; }

            // Use Discord API to set slowmode
            $result = discordApi("channels/$channel", 'PATCH', ['rate_limit_per_user' => $seconds]);

            if ($result) {
                $durationStr = $seconds === 0 ? 'Disabled' : ($seconds < 60 ? "{$seconds}s" : ($seconds < 3600 ? round($seconds/60) . "m" : round($seconds/3600, 1) . "h"));
                respond(null, [embed("🐌 Slowmode Updated", "**Channel:** <#$channel>\n**Slowmode:** $durationStr", $seconds > 0 ? 0xF39C12 : 0x2ECC71, [], [
                    'footer' => ['text' => "Set by $username"],
                ])]);
            } else {
                respondEphemeral("❌ Failed to update slowmode. Ensure the bot has `Manage Channels` permission.");
            }
            break;

        default:
            respondEphemeral("Unknown subcommand. Try `/server reactionroles`, `/server starboard`, `/server welcome`, `/server autorole`, `/server counting`, or `/server slowmode`.");
    }
}
