<?php
/**
 * GoSiteMe Discord Bot — Community Module
 * ════════════════════════════════════════
 * /poll     — Create interactive polls
 * /giveaway — Run giveaway events
 * /ticket   — Support ticket system
 * /embed    — Custom embed creator
 * /remind   — Set reminders
 * /announce — Server announcements
 */

function handlePoll(array $data): void {
    $subCmd = getSubcommand($data);
    $opts = getSubOptions($data);
    $userId = $data['member']['user']['id'] ?? '0';
    $globalName = $data['member']['user']['global_name'] ?? $data['member']['user']['username'] ?? 'User';
    $guildId = $data['guild_id'] ?? '';
    $channelId = $data['channel_id'] ?? '';
    $pdo = getDiscordDB();
    if (!$pdo) { respond("⚠️ Database unavailable."); return; }

    switch ($subCmd ?? 'create') {
        case 'create':
            $question = $opts['question'] ?? '';
            if (!$question) { respond("Provide a question! `/poll create question:What's your favorite color?`"); return; }

            // Parse options from fields (up to 5)
            $pollOpts = [];
            for ($i = 1; $i <= 5; $i++) {
                $o = $opts["option$i"] ?? null;
                if ($o) $pollOpts[] = $o;
            }
            if (count($pollOpts) < 2) {
                $pollOpts = ['Yes ✅', 'No ❌']; // Default yes/no poll
            }

            $emojis = ['1️⃣', '2️⃣', '3️⃣', '4️⃣', '5️⃣'];
            $pdo->prepare("INSERT INTO discord_polls (guild_id, channel_id, creator_id, question, options, votes) VALUES (?, ?, ?, ?, ?, '{}')")
                ->execute([$guildId, $channelId, $userId, $question, json_encode($pollOpts)]);
            $pollId = $pdo->lastInsertId();

            $desc = '';
            $buttons = [];
            foreach ($pollOpts as $i => $opt) {
                $desc .= "{$emojis[$i]} **{$opt}** — `0 votes`\n";
                $buttons[] = btn(2, $emojis[$i] . ' ' . substr($opt, 0, 40), "poll_vote_{$pollId}_{$i}");
            }
            $desc .= "\n📊 Total votes: **0**";

            respond(null, [
                embed("📊 $question", $desc, 0x3498DB, [], [
                    'footer' => ['text' => "Poll #$pollId | Created by $globalName | Click to vote!"],
                ])
            ], [actionRow(...$buttons)]);
            awardXP($userId, 5);
            break;

        case 'results':
            $pollId = $opts['id'] ?? 0;
            if (!$pollId) { respond("Specify a poll ID! `/poll results id:123`"); return; }
            $stmt = $pdo->prepare("SELECT * FROM discord_polls WHERE id = ?");
            $stmt->execute([$pollId]);
            $poll = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$poll) { respond("Poll not found."); return; }

            $options = json_decode($poll['options'], true);
            $votes = json_decode($poll['votes'], true) ?: [];
            $emojis = ['1️⃣', '2️⃣', '3️⃣', '4️⃣', '5️⃣'];
            $totalVotes = count($votes);

            $desc = '';
            $voteCounts = [];
            foreach ($votes as $uid => $optIdx) {
                $voteCounts[$optIdx] = ($voteCounts[$optIdx] ?? 0) + 1;
            }
            foreach ($options as $i => $opt) {
                $count = $voteCounts[$i] ?? 0;
                $pct = $totalVotes > 0 ? round($count / $totalVotes * 100) : 0;
                $bar = str_repeat('█', (int)round($pct / 10)) . str_repeat('░', 10 - (int)round($pct / 10));
                $desc .= "{$emojis[$i]} **{$opt}**\n$bar {$pct}% ({$count} votes)\n\n";
            }

            respond(null, [embed("📊 {$poll['question']}", $desc, 0x3498DB, [
                field('Total Votes', (string)$totalVotes, true),
                field('Status', ucfirst($poll['status']), true),
            ])]);
            break;

        case 'close':
            $pollId = $opts['id'] ?? 0;
            $stmt = $pdo->prepare("SELECT * FROM discord_polls WHERE id = ? AND creator_id = ?");
            $stmt->execute([$pollId, $userId]);
            $poll = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$poll) { respond("Poll not found or you're not the creator."); return; }
            $pdo->prepare("UPDATE discord_polls SET status = 'closed' WHERE id = ?")->execute([$pollId]);
            respond("📊 Poll **#{$pollId}** has been closed. Use `/poll results id:$pollId` to see final results.");
            break;
    }
}


function handleGiveaway(array $data): void {
    $subCmd = getSubcommand($data);
    $opts = getSubOptions($data);
    $userId = $data['member']['user']['id'] ?? '0';
    $globalName = $data['member']['user']['global_name'] ?? $data['member']['user']['username'] ?? 'User';
    $guildId = $data['guild_id'] ?? '';
    $channelId = $data['channel_id'] ?? '';
    $pdo = getDiscordDB();
    if (!$pdo) { respond("⚠️ Database unavailable."); return; }

    switch ($subCmd ?? 'create') {
        case 'create':
            if (!hasPerm($data, PERM_MANAGE_GUILD)) { respondEphemeral("You need **Manage Server** permission to create giveaways."); return; }
            $prize = $opts['prize'] ?? '';
            $duration = $opts['duration'] ?? '1h';
            $winners = max(1, min(10, (int)($opts['winners'] ?? 1)));
            if (!$prize) { respond("Specify a prize! `/giveaway create prize:Nitro winners:1 duration:24h`"); return; }

            // Parse duration
            preg_match('/(\d+)\s*(m|h|d)/', $duration, $m);
            $num = (int)($m[1] ?? 1);
            $unit = $m[2] ?? 'h';
            $seconds = $num * ['m' => 60, 'h' => 3600, 'd' => 86400][$unit];
            $endsAt = date('Y-m-d H:i:s', time() + $seconds);

            $pdo->prepare("INSERT INTO discord_giveaways (guild_id, channel_id, creator_id, prize, winner_count, ends_at) VALUES (?, ?, ?, ?, ?, ?)")
                ->execute([$guildId, $channelId, $userId, $prize, $winners, $endsAt]);
            $giveId = $pdo->lastInsertId();

            $endTs = time() + $seconds;
            respond(null, [
                embed("🎉 GIVEAWAY!", "**$prize**\n\n🎊 Click the button to enter!\n👥 Winners: **$winners**\n⏰ Ends: <t:$endTs:R>\n\n📋 Entries: **0**", 0xFEE75C, [], [
                    'footer' => ['text' => "Giveaway #$giveId | Hosted by $globalName"],
                    'thumbnail' => ['url' => 'https://gositeme.com/assets/images/logo-icon.png'],
                ])
            ], [
                actionRow(
                    btn(3, '🎉 Enter Giveaway', "giveaway_enter_{$giveId}"),
                    btn(2, '👥 Entries', "giveaway_count_{$giveId}")
                )
            ]);
            awardXP($userId, 10);
            break;

        case 'end':
            $giveId = $opts['id'] ?? 0;
            if (!hasPerm($data, PERM_MANAGE_GUILD)) { respondEphemeral("Need **Manage Server** permission."); return; }
            $stmt = $pdo->prepare("SELECT * FROM discord_giveaways WHERE id = ? AND guild_id = ?");
            $stmt->execute([$giveId, $guildId]);
            $give = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$give) { respond("Giveaway not found."); return; }

            $entries = json_decode($give['entries'], true) ?: [];
            if (empty($entries)) { respond("No one entered the giveaway! 😢"); $pdo->prepare("UPDATE discord_giveaways SET status = 'ended' WHERE id = ?")->execute([$giveId]); return; }

            $winnerCount = min($give['winner_count'], count($entries));
            $winnerKeys = array_rand($entries, $winnerCount);
            if (!is_array($winnerKeys)) $winnerKeys = [$winnerKeys];
            $winners = array_map(fn($k) => $entries[$k], $winnerKeys);

            $pdo->prepare("UPDATE discord_giveaways SET status = 'ended', winners = ? WHERE id = ?")->execute([json_encode($winners), $giveId]);
            $winnerMentions = implode(', ', array_map(fn($w) => "<@$w>", $winners));

            respond(null, [embed("🎉 Giveaway Ended!", "**Prize:** {$give['prize']}\n\n🏆 **Winners:** $winnerMentions\n\n📋 Total Entries: " . count($entries), 0x57F287)]);
            break;

        case 'reroll':
            $giveId = $opts['id'] ?? 0;
            if (!hasPerm($data, PERM_MANAGE_GUILD)) { respondEphemeral("Need **Manage Server** permission."); return; }
            $stmt = $pdo->prepare("SELECT * FROM discord_giveaways WHERE id = ? AND status = 'ended'");
            $stmt->execute([$giveId]);
            $give = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$give) { respond("Giveaway not found or not ended yet."); return; }

            $entries = json_decode($give['entries'], true) ?: [];
            if (empty($entries)) { respond("No entries to reroll!"); return; }
            $newWinner = $entries[array_rand($entries)];
            respond("🎉 **Reroll** — New winner: <@$newWinner>! Prize: **{$give['prize']}**");
            break;
    }
}


function handleTicket(array $data): void {
    $subCmd = getSubcommand($data);
    $opts = getSubOptions($data);
    $userId = $data['member']['user']['id'] ?? '0';
    $globalName = $data['member']['user']['global_name'] ?? $data['member']['user']['username'] ?? 'User';
    $guildId = $data['guild_id'] ?? '';
    $pdo = getDiscordDB();
    if (!$pdo) { respond("⚠️ Database unavailable."); return; }

    switch ($subCmd ?? 'open') {
        case 'open':
            $subject = $opts['subject'] ?? 'General Support';
            $category = $opts['category'] ?? 'general';
            $priority = $opts['priority'] ?? 'normal';

            // Check max open tickets
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM discord_tickets WHERE creator_id = ? AND status = 'open'");
            $stmt->execute([$userId]);
            if ($stmt->fetchColumn() >= 3) { respond("You have too many open tickets (max 3). Close some first!"); return; }

            $pdo->prepare("INSERT INTO discord_tickets (guild_id, creator_id, subject, category, priority) VALUES (?, ?, ?, ?, ?)")
                ->execute([$guildId, $userId, $subject, $category, $priority]);
            $ticketId = $pdo->lastInsertId();

            $prioEmoji = ['low' => '🟢', 'normal' => '🟡', 'high' => '🟠', 'urgent' => '🔴'][$priority] ?? '🟡';

            respond(null, [
                embed("🎫 Ticket #{$ticketId} Created", "**Subject:** $subject\n\n$prioEmoji **Priority:** " . ucfirst($priority) . "\n📁 **Category:** " . ucfirst($category) . "\n\nA team member will respond soon!", 0x3498DB, [], [
                    'footer' => ['text' => "Created by $globalName | /ticket reply to add a message"],
                ])
            ], [
                actionRow(
                    btn(1, '💬 Reply', "ticket_reply_{$ticketId}"),
                    btn(4, '🔒 Close', "ticket_close_{$ticketId}")
                )
            ]);
            awardXP($userId, 5);
            break;

        case 'reply':
            $ticketId = $opts['id'] ?? 0;
            $message = $opts['message'] ?? '';
            if (!$ticketId || !$message) { respond("Usage: `/ticket reply id:123 message:your message`"); return; }

            $stmt = $pdo->prepare("SELECT * FROM discord_tickets WHERE id = ? AND (creator_id = ? OR ? IN (SELECT moderator_id FROM discord_warnings WHERE guild_id = ?))");
            $stmt->execute([$ticketId, $userId, $userId, $guildId]);
            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$ticket && !hasPerm($data, PERM_MANAGE_GUILD)) { respond("Ticket not found or you don't have access."); return; }
            if (!$ticket) {
                $stmt = $pdo->prepare("SELECT * FROM discord_tickets WHERE id = ?");
                $stmt->execute([$ticketId]);
                $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            if (!$ticket) { respond("Ticket not found."); return; }
            if ($ticket['status'] !== 'open') { respond("This ticket is closed."); return; }

            $msgs = json_decode($ticket['messages'], true) ?: [];
            $msgs[] = ['user' => $userId, 'name' => $globalName, 'msg' => $message, 'time' => date('Y-m-d H:i')];
            $pdo->prepare("UPDATE discord_tickets SET messages = ? WHERE id = ?")->execute([json_encode($msgs), $ticketId]);

            respond(null, [embed("🎫 Ticket #{$ticketId}", "**$globalName** replied:\n> $message", 0x3498DB, [
                field('Subject', $ticket['subject'], true),
                field('Messages', (string)count($msgs), true),
            ])]);
            break;

        case 'close':
            $ticketId = $opts['id'] ?? 0;
            $stmt = $pdo->prepare("SELECT * FROM discord_tickets WHERE id = ? AND (creator_id = ? OR 1 = ?)");
            $stmt->execute([$ticketId, $userId, hasPerm($data, PERM_MANAGE_GUILD) ? 1 : 0]);
            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$ticket) { respond("Ticket not found or no permission."); return; }

            $pdo->prepare("UPDATE discord_tickets SET status = 'closed', closed_at = NOW() WHERE id = ?")->execute([$ticketId]);
            respond(null, [embed("🔒 Ticket #{$ticketId} Closed", "**{$ticket['subject']}** has been resolved.", 0x95A5A6)]);
            break;

        case 'list':
            $status = $opts['status'] ?? 'open';
            $stmt = $pdo->prepare("SELECT * FROM discord_tickets WHERE guild_id = ? AND status = ? ORDER BY FIELD(priority, 'urgent', 'high', 'normal', 'low'), created_at DESC LIMIT 10");
            $stmt->execute([$guildId, $status]);
            $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $desc = '';
            foreach ($tickets as $t) {
                $prio = ['low' => '🟢', 'normal' => '🟡', 'high' => '🟠', 'urgent' => '🔴'][$t['priority']] ?? '🟡';
                $msgs = count(json_decode($t['messages'], true) ?: []);
                $desc .= "$prio **#{$t['id']}** — {$t['subject']} ({$msgs} msgs) <@{$t['creator_id']}>\n";
            }
            if (!$desc) $desc = 'No tickets found.';

            respond(null, [embed("🎫 Tickets — " . ucfirst($status), $desc, 0x3498DB)]);
            break;
    }
}


function handleEmbed(array $data): void {
    if (!hasPerm($data, PERM_MANAGE_MESSAGES)) { respondEphemeral("You need **Manage Messages** permission."); return; }

    $opts = getSubOptions($data);
    $title = $opts['title'] ?? 'Custom Embed';
    $description = $opts['description'] ?? '';
    $colorStr = $opts['color'] ?? '#5865F2';
    $image = $opts['image'] ?? null;
    $thumbnail = $opts['thumbnail'] ?? null;
    $footer = $opts['footer'] ?? null;

    $colorHex = hexdec(ltrim($colorStr, '#'));
    $extra = [];
    if ($image) $extra['image'] = ['url' => $image];
    if ($thumbnail) $extra['thumbnail'] = ['url' => $thumbnail];
    if ($footer) $extra['footer'] = ['text' => $footer];

    $userId = $data['member']['user']['id'] ?? '0';
    awardXP($userId, 3);

    respond(null, [embed($title, $description, $colorHex, [], $extra)]);
}


function handleRemind(array $data): void {
    $opts = getSubOptions($data);
    $message = $opts['message'] ?? 'Reminder!';
    $timeStr = $opts['time'] ?? '1h';
    $userId = $data['member']['user']['id'] ?? '0';
    $globalName = $data['member']['user']['global_name'] ?? $data['member']['user']['username'] ?? 'User';
    $channelId = $data['channel_id'] ?? '';
    $pdo = getDiscordDB();
    if (!$pdo) { respond("⚠️ Database unavailable."); return; }

    // Parse time
    preg_match('/(\d+)\s*(m|h|d|w)/', $timeStr, $m);
    $val = (int)($m[1] ?? 1);
    $unit = $m[2] ?? 'h';
    $seconds = $val * ['m' => 60, 'h' => 3600, 'd' => 86400, 'w' => 604800][$unit];

    if ($seconds < 60) { respond("Minimum reminder time is 1 minute."); return; }
    if ($seconds > 2592000) { respond("Maximum reminder time is 30 days."); return; }

    $remindAt = date('Y-m-d H:i:s', time() + $seconds);
    $pdo->prepare("INSERT INTO discord_reminders (discord_id, channel_id, message, remind_at) VALUES (?, ?, ?, ?)")
        ->execute([$userId, $channelId, $message, $remindAt]);

    $ts = time() + $seconds;
    awardXP($userId, 3);
    respond(null, [embed("⏰ Reminder Set!", "I'll remind you <t:$ts:R>\n\n📝 **{$message}**", 0x3498DB, [], [
        'footer' => ['text' => "Set by $globalName"],
    ])]);
}


function handleAnnounce(array $data): void {
    if (!hasPerm($data, PERM_MANAGE_GUILD)) { respondEphemeral("You need **Manage Server** permission."); return; }

    $opts = getSubOptions($data);
    $title = $opts['title'] ?? 'Announcement';
    $message = $opts['message'] ?? '';
    $ping = $opts['ping'] ?? 'none';
    $userId = $data['member']['user']['id'] ?? '0';
    $globalName = $data['member']['user']['global_name'] ?? $data['member']['user']['username'] ?? 'User';
    if (!$message) { respond("Provide a message! `/announce title:Update message:We've added new features!`"); return; }

    $pingText = '';
    if ($ping === 'everyone') $pingText = '@everyone ';
    elseif ($ping === 'here') $pingText = '@here ';

    awardXP($userId, 5);
    respond($pingText ?: null, [embed("📢 $title", $message, 0x5865F2, [], [
        'footer' => ['text' => "Announced by $globalName"],
        'thumbnail' => ['url' => 'https://gositeme.com/assets/images/logo-icon.png'],
        'timestamp' => date('c'),
    ])]);
}
