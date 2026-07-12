<?php
/**
 * GoSiteMe Discord Bot — Interactions Module
 * ═══════════════════════════════════════════
 * Handles all button clicks, modal submits, and select menus
 */

function handleButton(array $data): void {
    $customId = $data['data']['custom_id'] ?? '';
    $userId = $data['member']['user']['id'] ?? '0';
    $globalName = $data['member']['user']['global_name'] ?? $data['member']['user']['username'] ?? 'User';
    $guildId = $data['guild_id'] ?? '';
    $channelId = $data['channel_id'] ?? '';
    $pdo = getDiscordDB();

    // ── Help category buttons ──
    if (str_starts_with($customId, 'help_')) {
        $cat = substr($customId, 5);
        handleHelp(['data' => ['options' => [['name' => 'category', 'value' => $cat]]]]);
        return;
    }

    // ── Daily claim ──
    if ($customId === 'daily_claim') {
        handleDaily(['member' => $data['member'], 'guild_id' => $guildId, 'channel_id' => $channelId, 'data' => ['options' => []]]);
        return;
    }

    // ── Shop browse/category ──
    if (str_starts_with($customId, 'shop_cat_')) {
        $cat = substr($customId, 9);
        handleShop(['member' => $data['member'], 'guild_id' => $guildId, 'channel_id' => $channelId,
            'data' => ['options' => [['name' => 'browse', 'type' => 1, 'options' => [['name' => 'category', 'value' => $cat]]]]]]);
        return;
    }
    if ($customId === 'shop_browse') {
        handleShop(['member' => $data['member'], 'guild_id' => $guildId, 'channel_id' => $channelId,
            'data' => ['options' => [['name' => 'browse', 'type' => 1, 'options' => []]]]]);
        return;
    }

    // ── Leaderboard type buttons ──
    if (str_starts_with($customId, 'lb_')) {
        $type = substr($customId, 3);
        handleCoins(['member' => $data['member'], 'guild_id' => $guildId, 'channel_id' => $channelId,
            'data' => ['options' => [['name' => 'leaderboard', 'type' => 1, 'options' => [['name' => 'type', 'value' => $type]]]]]]);
        return;
    }
    if ($customId === 'leaderboard_view') {
        handleCoins(['member' => $data['member'], 'guild_id' => $guildId, 'channel_id' => $channelId,
            'data' => ['options' => [['name' => 'leaderboard', 'type' => 1, 'options' => []]]]]);
        return;
    }

    // ── Coins balance ──
    if ($customId === 'coins_balance') {
        handleCoins(['member' => $data['member'], 'guild_id' => $guildId, 'channel_id' => $channelId,
            'data' => ['options' => [['name' => 'balance', 'type' => 1, 'options' => []]]]]);
        return;
    }

    // ── Poll vote ──
    if (str_starts_with($customId, 'poll_vote_')) {
        $parts = explode('_', $customId);
        $pollId = (int)$parts[2];
        $optionIdx = (int)($parts[3] ?? 0);

        if (!$pdo) { respondEphemeral("⚠️ DB unavailable"); return; }
        $stmt = $pdo->prepare("SELECT * FROM discord_polls WHERE id = ? AND status = 'active'");
        $stmt->execute([$pollId]);
        $poll = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$poll) { respondEphemeral("This poll has ended."); return; }

        $votes = json_decode($poll['votes'], true) ?: [];
        // Remove previous vote
        foreach ($votes as &$v) { $v = array_values(array_diff($v, [$userId])); }
        unset($v);
        // Add new vote
        if (!isset($votes[$optionIdx])) $votes[$optionIdx] = [];
        $votes[$optionIdx][] = $userId;

        $pdo->prepare("UPDATE discord_polls SET votes = ? WHERE id = ?")->execute([json_encode($votes), $pollId]);
        $total = array_sum(array_map('count', $votes));
        respondEphemeral("✅ Vote recorded! ($total total votes)");
        return;
    }

    // ── Giveaway enter ──
    if (str_starts_with($customId, 'giveaway_enter_')) {
        $giveawayId = (int)substr($customId, 15);
        if (!$pdo) { respondEphemeral("⚠️ DB unavailable"); return; }

        $stmt = $pdo->prepare("SELECT * FROM discord_giveaways WHERE id = ? AND status = 'active'");
        $stmt->execute([$giveawayId]);
        $giveaway = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$giveaway) { respondEphemeral("This giveaway has ended!"); return; }
        if (strtotime($giveaway['ends_at']) < time()) { respondEphemeral("This giveaway has expired!"); return; }

        $entries = json_decode($giveaway['entries'], true) ?: [];
        if (in_array($userId, $entries)) { respondEphemeral("You've already entered! 🎉"); return; }
        $entries[] = $userId;
        $pdo->prepare("UPDATE discord_giveaways SET entries = ? WHERE id = ?")->execute([json_encode($entries), $giveawayId]);
        respondEphemeral("🎉 You've entered the giveaway! " . count($entries) . " total entries.");
        return;
    }

    // ── Ticket buttons ──
    if (str_starts_with($customId, 'ticket_reply_')) {
        $ticketId = (int)substr($customId, 13);
        respondModal('ticket_reply_modal_' . $ticketId, 'Reply to Ticket #' . $ticketId, [
            actionRow([textInput('reply_text', 'Your Reply', 2, true, '', 1, 1000, 'Type your reply...')]),
        ]);
        return;
    }
    if (str_starts_with($customId, 'ticket_close_')) {
        $ticketId = (int)substr($customId, 13);
        if (!$pdo) { respondEphemeral("⚠️ DB unavailable"); return; }
        $pdo->prepare("UPDATE discord_tickets SET status = 'closed', closed_at = NOW() WHERE id = ? AND (user_id = ? OR ? IN (SELECT user_id FROM discord_users WHERE guild_id = ? AND 1=1))")
            ->execute([$ticketId, $userId, $userId, $guildId]);
        respond(null, [embed("🎫 Ticket #$ticketId Closed", "Closed by <@$userId>.", 0x95A5A6)]);
        return;
    }

    // ── Chess accept/decline challenge ──
    if (str_starts_with($customId, 'chess_accept_')) {
        $challengerId = substr($customId, 13);
        if (!$pdo) { respondEphemeral("⚠️ DB unavailable"); return; }

        // Find the challenge
        $stmt = $pdo->prepare("SELECT * FROM discord_games WHERE player1 = ? AND player2 = ? AND game_type = 'chess' AND status = 'pending' ORDER BY id DESC LIMIT 1");
        $stmt->execute([$challengerId, $userId]);
        $game = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$game) { respondEphemeral("No pending challenge found."); return; }

        $pdo->prepare("UPDATE discord_games SET status = 'active' WHERE id = ?")->execute([$game['id']]);
        $board = renderChessBoard($game['board_state']);
        respond(null, [embed("♟️ Chess Match Started!", "<@$challengerId> ⚔️ <@$userId>\n\n$board\n\n<@$challengerId>'s turn (White)", 0x57F287)]);
        return;
    }
    if (str_starts_with($customId, 'chess_decline_')) {
        $challengerId = substr($customId, 14);
        respond("❌ <@$userId> declined the chess challenge from <@$challengerId>.");
        return;
    }

    // ── Checkers accept/decline ──
    if (str_starts_with($customId, 'checkers_accept_')) {
        $challengerId = substr($customId, 16);
        if (!$pdo) { respondEphemeral("⚠️ DB unavailable"); return; }
        $stmt = $pdo->prepare("SELECT * FROM discord_games WHERE player1 = ? AND player2 = ? AND game_type = 'checkers' AND status = 'pending' ORDER BY id DESC LIMIT 1");
        $stmt->execute([$challengerId, $userId]);
        $game = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$game) { respondEphemeral("No pending challenge."); return; }
        $pdo->prepare("UPDATE discord_games SET status = 'active' WHERE id = ?")->execute([$game['id']]);
        $board = renderCheckersBoard($game['board_state']);
        respond(null, [embed("🏁 Checkers Match!", "<@$challengerId> ⚔️ <@$userId>\n\n$board\n\n<@$challengerId>'s turn", 0x57F287)]);
        return;
    }
    if (str_starts_with($customId, 'checkers_decline_')) {
        $challengerId = substr($customId, 17);
        respond("❌ <@$userId> declined the checkers challenge from <@$challengerId>.");
        return;
    }

    // ── RPS challenge ──
    if (str_starts_with($customId, 'rps_challenge_')) {
        $parts = explode('_', $customId);
        // rps_challenge_{challengerId}_{choice}
        $challengerId = $parts[2] ?? '';
        $challengerChoice = $parts[3] ?? '';
        if ($userId === $challengerId) { respondEphemeral("Can't play against yourself."); return; }

        respond(null, [embed("✊ RPS Challenge!", "<@$challengerId> challenges <@$userId>!\nPick your weapon:", 0xFEE75C)], [
            actionRow([
                btn("rps_answer_{$challengerId}_{$challengerChoice}_rock", '🪨 Rock', 2),
                btn("rps_answer_{$challengerId}_{$challengerChoice}_paper", '📄 Paper', 2),
                btn("rps_answer_{$challengerId}_{$challengerChoice}_scissors", '✂️ Scissors', 2),
            ]),
        ]);
        return;
    }
    if (str_starts_with($customId, 'rps_answer_')) {
        $parts = explode('_', $customId);
        $challengerId = $parts[2] ?? '';
        $p1Choice = $parts[3] ?? '';
        $p2Choice = $parts[4] ?? '';
        $emojis = ['rock' => '🪨', 'paper' => '📄', 'scissors' => '✂️'];
        $beats = ['rock' => 'scissors', 'paper' => 'rock', 'scissors' => 'paper'];

        if ($p1Choice === $p2Choice) $result = "🤝 It's a **tie**!";
        elseif ($beats[$p1Choice] === $p2Choice) $result = "🏆 <@$challengerId> wins!";
        else $result = "🏆 <@$userId> wins!";

        respond(null, [embed("✊ RPS Result", "<@$challengerId> threw {$emojis[$p1Choice]} | <@$userId> threw {$emojis[$p2Choice]}\n\n$result", 0x5865F2)]);
        return;
    }

    // ── RPS quick play ──
    if (str_starts_with($customId, 'rps_quick_')) {
        $choice = substr($customId, 10);
        // Reconstruct data for handleRPS
        handleRPS(['member' => $data['member'], 'guild_id' => $guildId, 'channel_id' => $channelId,
            'data' => ['options' => [['name' => 'choice', 'value' => $choice]]]]);
        return;
    }

    // ── Trivia answer ──
    if (preg_match('/^trivia_(\d+)_([A-D])$/', $customId, $m)) {
        $triviaId = $m[1];
        $answer = $m[2];
        if (!$pdo) { respondEphemeral("⚠️ DB unavailable"); return; }

        $stmt = $pdo->prepare("SELECT * FROM discord_trivia_active WHERE id = ?");
        $stmt->execute([$triviaId]);
        $trivia = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$trivia) { respondEphemeral("This trivia has expired."); return; }
        if ($trivia['answered_by']) { respondEphemeral("Already answered!"); return; }

        $correct = $trivia['correct_answer'] === $answer;
        $pdo->prepare("UPDATE discord_trivia_active SET answered_by = ?, answer_given = ? WHERE id = ?")
            ->execute([$userId, $answer, $triviaId]);

        $reward = 0;
        if ($correct) {
            $rewards = ['easy' => 10, 'medium' => 25, 'hard' => 50];
            $reward = $rewards[$trivia['difficulty']] ?? 10;
            $user = getOrCreateUser($pdo, $userId, $guildId, $globalName);
            $pdo->prepare("UPDATE discord_users SET kgd = kgd + ?, total_earned = total_earned + ? WHERE user_id = ? AND guild_id = ?")
                ->execute([$reward, $reward, $userId, $guildId]);
            awardXP($pdo, $userId, $guildId, 10, $channelId);
        }

        $icon = $correct ? '✅' : '❌';
        $msg = $correct ? "**Correct!** +$reward KGD, +10 XP" : "**Wrong!** The answer was **{$trivia['correct_answer']}**";
        respond(null, [embed("$icon Trivia Result", "$msg\n\n📝 {$trivia['question']}\n✅ Answer: **{$trivia['correct_answer']}**", $correct ? 0x57F287 : 0xED4245)]);
        return;
    }

    // ── Gamble buttons ──
    if ($customId === 'gamble_flip') {
        handleGamble(['member' => $data['member'], 'guild_id' => $guildId, 'channel_id' => $channelId,
            'data' => ['options' => [['name' => 'coinflip', 'type' => 1, 'options' => [['name' => 'amount', 'value' => 10]]]]]]);
        return;
    }
    if ($customId === 'gamble_dice') {
        handleGamble(['member' => $data['member'], 'guild_id' => $guildId, 'channel_id' => $channelId,
            'data' => ['options' => [['name' => 'dice', 'type' => 1, 'options' => [['name' => 'amount', 'value' => 10]]]]]]);
        return;
    }
    if (str_starts_with($customId, 'gamble_slots_')) {
        $amount = (int)substr($customId, 13);
        handleGamble(['member' => $data['member'], 'guild_id' => $guildId, 'channel_id' => $channelId,
            'data' => ['options' => [['name' => 'slots', 'type' => 1, 'options' => [['name' => 'amount', 'value' => max(10, $amount)]]]]]]);
        return;
    }

    // ── Imagine regenerate ──
    if (str_starts_with($customId, 'imagine_regen_')) {
        respondEphemeral("🔄 Use `/imagine` again with the same prompt to regenerate!");
        return;
    }

    // ── Quick game shortcuts ──
    if ($customId === 'quick_chess') {
        handleChess(['member' => $data['member'], 'guild_id' => $guildId, 'channel_id' => $channelId,
            'data' => ['options' => [['name' => 'play', 'type' => 1, 'options' => []]]]]);
        return;
    }
    if ($customId === 'quick_checkers') {
        handleCheckers(['member' => $data['member'], 'guild_id' => $guildId, 'channel_id' => $channelId,
            'data' => ['options' => [['name' => 'play', 'type' => 1, 'options' => []]]]]);
        return;
    }

    // ── Profile sub-views ──
    if (str_starts_with($customId, 'profile_games_')) {
        $targetId = substr($customId, 14);
        if (!$pdo) { respondEphemeral("⚠️ DB unavailable"); return; }
        $stmt = $pdo->prepare("SELECT game_type, status, player1, player2, created_at FROM discord_games WHERE (player1 = ? OR player2 = ?) ORDER BY id DESC LIMIT 10");
        $stmt->execute([$targetId, $targetId]);
        $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $desc = '';
        foreach ($games as $g) {
            $type = ucfirst($g['game_type']);
            $opp = $g['player1'] === $targetId ? $g['player2'] : $g['player1'];
            $oppStr = $opp === 'ai' ? 'AI' : "<@$opp>";
            $result = '🔄';
            if ($g['status'] === 'won_p1') $result = $g['player1'] === $targetId ? '🏆' : '💀';
            if ($g['status'] === 'won_p2') $result = $g['player2'] === $targetId ? '🏆' : '💀';
            if ($g['status'] === 'draw') $result = '🤝';
            $desc .= "$result **$type** vs $oppStr\n";
        }
        respondEphemeral(null, [embed("🎮 Recent Games — <@$targetId>", $desc ?: 'No games yet.', 0x5865F2)]);
        return;
    }
    if (str_starts_with($customId, 'profile_inventory_')) {
        $targetId = substr($customId, 18);
        if (!$pdo) { respondEphemeral("⚠️ DB unavailable"); return; }
        $stmt = $pdo->prepare("SELECT item_name, quantity FROM discord_inventory WHERE user_id = ? ORDER BY acquired_at DESC LIMIT 15");
        $stmt->execute([$targetId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $desc = '';
        foreach ($items as $it) { $desc .= "• {$it['item_name']} x{$it['quantity']}\n"; }
        respondEphemeral(null, [embed("🎒 Inventory — <@$targetId>", $desc ?: 'Empty inventory.', 0x5865F2)]);
        return;
    }
    if (str_starts_with($customId, 'profile_achievements_')) {
        $targetId = substr($customId, 21);
        if (!$pdo) { respondEphemeral("⚠️ DB unavailable"); return; }
        $user = getOrCreateUser($pdo, $targetId, $guildId, $globalName);
        $level = calcLevel($user['xp']);

        $achievements = [];
        if ($level >= 5) $achievements[] = '⭐ Apprentice (Reach Level 5)';
        if ($level >= 10) $achievements[] = '🌟 Adept (Reach Level 10)';
        if ($level >= 25) $achievements[] = '💎 Master (Reach Level 25)';
        if ($level >= 50) $achievements[] = '👑 Legend (Reach Level 50)';
        if ($user['daily_streak'] >= 7) $achievements[] = '🔥 Week Warrior (7-day streak)';
        if ($user['daily_streak'] >= 30) $achievements[] = '🔥 Monthly Devotion (30-day streak)';
        if ($user['kgd'] >= 1000) $achievements[] = '💰 Thousand Club';
        if ($user['kgd'] >= 10000) $achievements[] = '💰 Moneybags (10K KGD)';
        if ($user['elo'] >= 1200) $achievements[] = '♟️ Chess Enthusiast (1200 ELO)';
        if ($user['elo'] >= 1500) $achievements[] = '♟️ Chess Expert (1500 ELO)';

        respondEphemeral(null, [embed("🏅 Achievements — <@$targetId>", !empty($achievements) ? implode("\n", $achievements) : 'No achievements yet. Keep playing!', 0xFEE75C)]);
        return;
    }

    // ── Status recheck ──
    if (str_starts_with($customId, 'status_recheck_')) {
        $url = substr($customId, 15);
        handleStatus(['data' => ['options' => [['name' => 'url', 'value' => $url]]]]);
        return;
    }

    // ── Quote new ──
    if ($customId === 'quote_new') {
        handleQuote(['member' => $data['member'], 'guild_id' => $guildId, 'channel_id' => $channelId,
            'application_id' => $data['message']['interaction']['id'] ?? ($GLOBALS['discord_interaction']['application_id'] ?? ''),
            'token' => $GLOBALS['discord_interaction']['token'] ?? '',
            'data' => ['options' => []]]);
        return;
    }

    // ── Music recommendations ──
    if ($customId === 'music_more') {
        handleMusic(['member' => $data['member'], 'guild_id' => $guildId, 'channel_id' => $channelId,
            'application_id' => $GLOBALS['discord_interaction']['application_id'] ?? '',
            'token' => $GLOBALS['discord_interaction']['token'] ?? '',
            'data' => ['options' => [['name' => 'mood', 'value' => 'chill']]]]);
        return;
    }
    if ($customId === 'music_playlist') {
        respondEphemeral("📋 Use `/music mood:your-mood genre:your-genre` to get a curated playlist!");
        return;
    }

    // ── Search more details ──
    if (str_starts_with($customId, 'search_more_')) {
        respondEphemeral("🔍 Use `/search query:your-question` for a new, more detailed search!");
        return;
    }

    // ── Screenshot full page ──
    if (str_starts_with($customId, 'screenshot_full_')) {
        $domain = substr($customId, 16);
        deferComponentUpdate();
        $appId = $GLOBALS['discord_interaction']['application_id'] ?? '';
        $token = $GLOBALS['discord_interaction']['token'] ?? '';
        $fullUrl = "https://image.thum.io/get/width/1280/noanimate/https://$domain";
        $emb = embed("📸 Full Screenshot — $domain", '', 0x5865F2);
        $emb['image'] = ['url' => $fullUrl];
        followUp($appId, $token, '', [$emb]);
        return;
    }

    // ── Deploy edit ──
    if (str_starts_with($customId, 'deploy_edit_')) {
        respondEphemeral("✏️ Site editing from Discord coming soon! For now, use `/deploy website` to redeploy.");
        return;
    }

    // ── Video regenerate ──
    if (str_starts_with($customId, 'video_regen_')) {
        respondEphemeral("🎬 Use `/video prompt:your prompt` to generate another video!");
        return;
    }

    // ── Music generation ──
    if (str_starts_with($customId, 'musicgen_regen_')) {
        respondEphemeral("🎵 Use `/musicgen prompt:your prompt` to generate another track!");
        return;
    }

    // ── Stock refresh ──
    if (str_starts_with($customId, 'stock_refresh_')) {
        $coinId = substr($customId, 14);
        handleStock(['member' => $data['member'], 'guild_id' => $guildId, 'channel_id' => $channelId,
            'application_id' => $GLOBALS['discord_interaction']['application_id'] ?? '',
            'token' => $GLOBALS['discord_interaction']['token'] ?? '',
            'data' => ['options' => [['name' => 'ticker', 'value' => $coinId]]]]);
        return;
    }

    // ── Debate ──
    if ($customId === 'debate_new') {
        respondEphemeral("⚔️ Use `/debate topic:your topic` to start a new debate!");
        return;
    }
    if (str_starts_with($customId, 'debate_more_')) {
        respondEphemeral("⚔️ Use `/debate topic:your topic` to continue the debate!");
        return;
    }

    // ── Roast ──
    if ($customId === 'roast_again' || $customId === 'roast_savage') {
        respondEphemeral("🔥 Use `/roast user:@someone intensity:savage` to roast again!");
        return;
    }

    // ── Story ──
    if (str_starts_with($customId, 'story_choice_') || $customId === 'story_new') {
        respondEphemeral("📖 Use `/story genre:fantasy beginning:your starting scene` to continue!");
        return;
    }

    // ── Dream ──
    if ($customId === 'dream_new' || str_starts_with($customId, 'dream_deeper_')) {
        respondEphemeral("🌙 Use `/dream description:your dream` for a new interpretation!");
        return;
    }

    // ── Recipe ──
    if ($customId === 'recipe_another' || $customId === 'recipe_healthy' || $customId === 'recipe_quick') {
        respondEphemeral("🍳 Use `/recipe ingredients:your ingredients` for a new recipe!");
        return;
    }

    // ── Interview ──
    if ($customId === 'interview_tips' || str_starts_with($customId, 'interview_new_')) {
        respondEphemeral("💼 Use `/interview role:your target role` for new questions!");
        return;
    }

    // ── Riddle ──
    if (str_starts_with($customId, 'riddle_reveal_')) {
        $riddleId = substr($customId, 14);
        $pdo = getDiscordDB();
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT answer FROM discord_riddles WHERE id = ?");
            $stmt->execute([$riddleId]);
            $answer = $stmt->fetchColumn();
            if ($answer) {
                respondEphemeral(null, [embed("💡 Riddle Answer", "**Answer:** $answer", 0x57F287)]);
                return;
            }
        }
        respondEphemeral("💡 This riddle has expired. Try `/riddle` for a new one!");
        return;
    }
    if ($customId === 'riddle_new') {
        respondEphemeral("🧩 Use `/riddle difficulty:hard` for a new riddle!");
        return;
    }
    if ($customId === 'riddle_hard') {
        respondEphemeral("🔥 Use `/riddle difficulty:hard` for a harder riddle!");
        return;
    }

    // ── Wisdom ──
    if ($customId === 'wisdom_new' || $customId === 'wisdom_deep') {
        handleWisdom(['member' => $data['member'], 'guild_id' => $guildId, 'channel_id' => $channelId,
            'application_id' => $GLOBALS['discord_interaction']['application_id'] ?? '',
            'token' => $GLOBALS['discord_interaction']['token'] ?? '',
            'data' => ['options' => []]]);
        return;
    }

    // ── Persona ──
    if ($customId === 'persona_new' || str_starts_with($customId, 'persona_again_')) {
        respondEphemeral("🎭 Use `/persona name:Einstein message:your question` to chat!");
        return;
    }

    // ── News ──
    if (str_starts_with($customId, 'news_refresh_')) {
        $cat = substr($customId, 13);
        handleNews(['member' => $data['member'], 'guild_id' => $guildId, 'channel_id' => $channelId,
            'application_id' => $GLOBALS['discord_interaction']['application_id'] ?? '',
            'token' => $GLOBALS['discord_interaction']['token'] ?? '',
            'data' => ['options' => [['name' => 'category', 'value' => $cat]]]]);
        return;
    }
    if ($customId === 'news_digest') {
        handleDigest(['member' => $data['member'], 'guild_id' => $guildId, 'channel_id' => $channelId,
            'application_id' => $GLOBALS['discord_interaction']['application_id'] ?? '',
            'token' => $GLOBALS['discord_interaction']['token'] ?? '',
            'data' => ['options' => []]]);
        return;
    }
    if ($customId === 'news_random') {
        $cats = ['tech', 'crypto', 'security', 'ai', 'science', 'world'];
        $cat = $cats[array_rand($cats)];
        handleNews(['member' => $data['member'], 'guild_id' => $guildId, 'channel_id' => $channelId,
            'application_id' => $GLOBALS['discord_interaction']['application_id'] ?? '',
            'token' => $GLOBALS['discord_interaction']['token'] ?? '',
            'data' => ['options' => [['name' => 'category', 'value' => $cat]]]]);
        return;
    }
    if (str_starts_with($customId, 'news_cat_')) {
        $cat = substr($customId, 9);
        handleNews(['member' => $data['member'], 'guild_id' => $guildId, 'channel_id' => $channelId,
            'application_id' => $GLOBALS['discord_interaction']['application_id'] ?? '',
            'token' => $GLOBALS['discord_interaction']['token'] ?? '',
            'data' => ['options' => [['name' => 'category', 'value' => $cat]]]]);
        return;
    }

    // ── Legal ──
    if (str_starts_with($customId, 'legal_motion_') || str_starts_with($customId, 'legal_detail_')) {
        respondEphemeral("⚖️ Use `/legal query:your question` for more legal research!");
        return;
    }

    // ── Web Search ──
    if ($customId === 'websearch_new') {
        respondEphemeral("🔍 Use `/websearch query:your search` to search the web!");
        return;
    }
    if ($customId === 'research_more' || $customId === 'research_deep') {
        respondEphemeral("📚 Use `/research topic:your topic depth:" . ($customId === 'research_deep' ? 'deep' : 'standard') . "`");
        return;
    }
    if (str_starts_with($customId, 'whois_refresh_')) {
        $domain = substr($customId, 14);
        deferResponse();
        handleWhois(['member' => $data['member'], 'guild_id' => $guildId, 'channel_id' => $channelId,
            'application_id' => $GLOBALS['discord_interaction']['application_id'] ?? '',
            'token' => $GLOBALS['discord_interaction']['token'] ?? '',
            'data' => ['options' => [['name' => 'domain', 'value' => $domain]]]]);
        return;
    }

    // ── Admin ──
    if ($customId === 'health_refresh') {
        handleHealth($GLOBALS['discord_interaction']);
        return;
    }
    if ($customId === 'stats_refresh') {
        handleBotstats($GLOBALS['discord_interaction']);
        return;
    }
    if ($customId === 'profile_view') {
        respondEphemeral("👤 Use `/profile` to view your profile!");
        return;
    }

    // ── Creative ──
    if ($customId === 'poem_another' || $customId === 'poem_sonnet' || $customId === 'poem_spokenword' || $customId === 'poem_rap') {
        $styles = ['poem_another' => 'free verse', 'poem_sonnet' => 'sonnet', 'poem_spokenword' => 'spoken word', 'poem_rap' => 'rap'];
        respondEphemeral("✨ Use `/poem topic:your topic style:" . ($styles[$customId] ?? 'free verse') . "`");
        return;
    }
    if ($customId === 'lyrics_another' || $customId === 'lyrics_rock' || $customId === 'lyrics_hiphop' || $customId === 'lyrics_country') {
        $genres = ['lyrics_another' => 'pop', 'lyrics_rock' => 'rock', 'lyrics_hiphop' => 'hiphop', 'lyrics_country' => 'country'];
        respondEphemeral("🎵 Use `/lyrics topic:your topic genre:" . ($genres[$customId] ?? 'pop') . "`");
        return;
    }
    if ($customId === 'script_another' || $customId === 'script_sketch' || $customId === 'script_horror' || $customId === 'script_sitcom') {
        $fmts = ['script_another' => 'sketch', 'script_sketch' => 'sketch', 'script_horror' => 'horror', 'script_sitcom' => 'sitcom'];
        respondEphemeral("🎬 Use `/script premise:your idea format:" . ($fmts[$customId] ?? 'sketch') . "`");
        return;
    }

    // ── Social2 ──
    if (str_starts_with($customId, 'confess_react_')) {
        $reacts = ['heart' => '❤️', 'laugh' => '😂', 'shock' => '😮', 'hug' => '🤗'];
        foreach ($reacts as $key => $emoji) {
            if (str_contains($customId, $key)) {
                respondEphemeral("$emoji Reaction recorded!");
                return;
            }
        }
    }
    if ($customId === 'wyr_new') {
        respondEphemeral("🤔 Use `/wouldyourather` for a new question!");
        return;
    }
    if (str_starts_with($customId, 'wyr_a_') || str_starts_with($customId, 'wyr_b_')) {
        $vote = str_starts_with($customId, 'wyr_a_') ? '🅰️ Option A' : '🅱️ Option B';
        respondEphemeral("✅ You voted for **$vote**!");
        return;
    }
    if (str_starts_with($customId, 'wyr_explain_')) {
        respondEphemeral("💡 Both options are designed to be equally difficult. There's no wrong answer!");
        return;
    }
    if ($customId === 'compat_new') {
        respondEphemeral("💕 Use `/compatibility user:@someone` to check compatibility!");
        return;
    }
    if (str_starts_with($customId, 'compat_ship_')) {
        $targetId = substr($customId, 12);
        $userId = $data['member']['user']['id'] ?? '';
        $name1 = substr($data['member']['user']['username'] ?? 'User1', 0, 4);
        $targetInfo = discordApi("/users/$targetId");
        $name2 = substr($targetInfo['username'] ?? 'User2', 0, 4);
        $shipName = ucfirst(strtolower($name1)) . strtolower($name2);
        respondEphemeral("💕 Your ship name: **$shipName** 🚢");
        return;
    }
    if ($customId === 'tier_rerank' || $customId === 'tier_spicy' || $customId === 'tier_new') {
        respondEphemeral("📊 Use `/tierlist topic:your topic` for a new tier list!");
        return;
    }

    // ── Utility ──
    if ($customId === 'math_graph' || $customId === 'math_explain' || $customId === 'math_new') {
        respondEphemeral("🔢 Use `/math expression:your problem` to solve more!");
        return;
    }
    if ($customId === 'define_new') {
        respondEphemeral("📖 Use `/define word:your word` for another definition!");
        return;
    }

    // ── Personality Module buttons ──
    if (str_starts_with($customId, 'personality_preset_')) {
        $preset = substr($customId, 19);
        $presets = [
            'comedian' => ['humor' => 10, 'sarcasm' => 8, 'formality' => 2, 'creativity' => 9],
            'pro'      => ['humor' => 3, 'sarcasm' => 1, 'formality' => 9, 'verbosity' => 7],
            'chaos'    => ['humor' => 10, 'sarcasm' => 10, 'creativity' => 10, 'formality' => 1],
        ];
        if (isset($presets[$preset])) {
            $db = getDiscordDB();
            if ($db) {
                foreach ($presets[$preset] as $trait => $val) {
                    $db->prepare("INSERT INTO discord_personality (discord_id, trait_name, trait_value)
                        VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE trait_value = VALUES(trait_value), updated_at = NOW()")
                        ->execute([$userId, $trait, (string)$val]);
                }
            }
            respondEphemeral("✅ Preset **" . ucfirst($preset) . "** applied! Use `/personality view` to see changes.");
        }
        return;
    }
    if ($customId === 'personality_reset') {
        $db = getDiscordDB();
        if ($db) $db->prepare("DELETE FROM discord_personality WHERE discord_id = ?")->execute([$userId]);
        respondEphemeral("🔄 Personality reset to defaults.");
        return;
    }
    if ($customId === 'personality_view') {
        handlePersonality(['member' => $data['member'], 'data' => ['options' => [['name' => 'view', 'options' => []]]]]);
        return;
    }
    if (str_starts_with($customId, 'mood_')) {
        $mood = substr($customId, 5);
        handleMood(['member' => $data['member'], 'data' => ['options' => [['name' => 'mood', 'value' => $mood]]]]);
        return;
    }
    if ($customId === 'memory_list') {
        $db = getDiscordDB();
        if (!$db) { respondEphemeral("❌ DB unavailable."); return; }
        $stmt = $db->prepare("SELECT memory, created_at FROM discord_memories WHERE discord_id = ? ORDER BY created_at DESC LIMIT 10");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();
        $lines = empty($rows) ? ['No memories stored yet.'] : array_map(fn($r) => "• {$r['memory']}", $rows);
        respondEphemeral("🧠 **Your Memories:**\n" . implode("\n", $lines));
        return;
    }
    if ($customId === 'memory_clear') {
        $db = getDiscordDB();
        if ($db) $db->prepare("DELETE FROM discord_memories WHERE discord_id = ?")->execute([$userId]);
        respondEphemeral("🗑️ All memories cleared.");
        return;
    }
    if ($customId === 'adapt_refresh') {
        handleAdapt(['member' => $data['member'], 'token' => $GLOBALS['discord_interaction']['token'] ?? '', 'data' => ['options' => []]]);
        return;
    }

    // ── Document Module buttons ──
    if (str_starts_with($customId, 'doc_summarize_') || str_starts_with($customId, 'doc_analyze_') || str_starts_with($customId, 'doc_extract_')) {
        respondEphemeral("📄 Use `/doc` with a file attachment for this action!");
        return;
    }
    if ($customId === 'ocr_summarize' || $customId === 'ocr_analyze') {
        respondEphemeral("📸 Use `/ocr` with a new image to process!");
        return;
    }
    if ($customId === 'docsummary_refresh') {
        respondEphemeral("📋 Use `/summarizedoc url:URL` to summarize another document!");
        return;
    }

    // ── Kingdom Module buttons ──
    if (str_starts_with($customId, 'zone_')) {
        $zone = substr($customId, 5);
        handleKingdom(['member' => $data['member'], 'data' => ['options' => [['name' => 'zone', 'options' => [['name' => 'zone', 'value' => $zone]]]]]]);
        return;
    }
    if ($customId === 'kingdom_zones') {
        handleZones(['member' => $data['member'], 'data' => ['options' => []]]);
        return;
    }
    if ($customId === 'kingdom_lb') {
        handleLeaderboardCmd(['member' => $data['member'], 'data' => ['options' => [['name' => 'type', 'value' => 'xp']]]]);
        return;
    }
    if ($customId === 'kingdom_achievements') {
        handleAchievements(['member' => $data['member'], 'data' => ['options' => []]]);
        return;
    }
    if ($customId === 'kingdom_ledger') {
        respondEphemeral("💰 Use `/transfer` to send KGD or check `/leaderboard` for rankings!");
        return;
    }
    if ($customId === 'lb_xp' || $customId === 'lb_kgd' || $customId === 'lb_games') {
        $type = substr($customId, 3);
        handleLeaderboardCmd(['member' => $data['member'], 'data' => ['options' => [['name' => 'type', 'value' => $type]]]]);
        return;
    }

    // ── Scripture Module buttons ──
    if ($customId === 'verse_random' || str_starts_with($customId, 'verse_')) {
        $cat = $customId === 'verse_random' ? '' : substr($customId, 6);
        handleVerse(['member' => $data['member'], 'data' => ['options' => $cat ? [['name' => 'category', 'value' => $cat]] : []]]);
        return;
    }
    if ($customId === 'devotional_new') {
        handleDevotional(['member' => $data['member'], 'token' => $GLOBALS['discord_interaction']['token'] ?? '', 'data' => ['options' => []]]);
        return;
    }
    if ($customId === 'prayer_wall') {
        handlePrayer(['member' => $data['member'], 'data' => ['options' => [['name' => 'wall', 'options' => []]]]]);
        return;
    }
    if (str_starts_with($customId, 'pray_for_')) {
        $prayerId = (int)substr($customId, 9);
        $db = getDiscordDB();
        if ($db) {
            $db->prepare("UPDATE discord_prayers SET pray_count = pray_count + 1 WHERE id = ?")->execute([$prayerId]);
            $cnt = $db->prepare("SELECT pray_count FROM discord_prayers WHERE id = ?");
            $cnt->execute([$prayerId]);
            $count = $cnt->fetchColumn();
            respondEphemeral("🙏 Thank you for praying! This request now has **$count** prayers.");
        }
        return;
    }

    // ── Agents Module buttons ──
    if ($customId === 'agent_roster' || $customId === 'agent_leaderboard' || $customId === 'agent_stats' || $customId === 'agent_games') {
        handleAgents(['member' => $data['member'], 'data' => ['options' => []]]);
        return;
    }
    if ($customId === 'agent_deploy') {
        respondEphemeral("🚀 Use `/delegate task:description` to deploy agents to tasks!");
        return;
    }
    if (str_starts_with($customId, 'delegate_to_')) {
        $agentId = substr($customId, 12);
        respondEphemeral("📋 Use `/delegate task:description agent:$agentId` to assign a task!");
        return;
    }
    if ($customId === 'delegate_reroute') {
        respondEphemeral("🔄 Use `/delegate task:description` to re-route to a different agent!");
        return;
    }
    if ($customId === 'goal_list' || $customId === 'goal_dashboard') {
        handleGoal(['member' => $data['member'], 'data' => ['options' => [['name' => 'list', 'options' => []]]]]);
        return;
    }
    if ($customId === 'goal_new') {
        respondEphemeral("➕ Use `/goal create description:your goal` to create a new goal!");
        return;
    }
    if (str_starts_with($customId, 'goal_decompose_')) {
        $goalId = (int)substr($customId, 15);
        handleGoal(['member' => $data['member'], 'token' => $GLOBALS['discord_interaction']['token'] ?? '',
            'data' => ['options' => [['name' => 'decompose', 'options' => [['name' => 'id', 'value' => $goalId]]]]]]);
        return;
    }
    if ($customId === 'decision_new') {
        handleDecision(['member' => $data['member'], 'token' => $GLOBALS['discord_interaction']['token'] ?? '', 'data' => ['options' => []]]);
        return;
    }
    if ($customId === 'wager_again' || $customId === 'wager_menu') {
        respondEphemeral("🎰 Use `/wager agent:name amount:KGD game:type` to place a bet!");
        return;
    }
    if ($customId === 'ecosystem_stats') {
        handleEcosystem(['member' => $data['member'], 'data' => ['options' => []]]);
        return;
    }

    // ── Help category buttons ──
    if (str_starts_with($customId, 'help_')) {
        $cat = substr($customId, 5);
        handleHelp(['member' => $data['member'], 'guild_id' => $guildId, 'channel_id' => $channelId,
            'application_id' => $GLOBALS['discord_interaction']['application_id'] ?? '',
            'token' => $GLOBALS['discord_interaction']['token'] ?? '',
            'data' => ['options' => [['name' => 'category', 'value' => $cat]]]]);
        return;
    }

    // ── Consciousness Module buttons ──
    if ($customId === 'consciousness_dream' || $customId === 'dream2_again') {
        handleConsciousness(['member' => $data['member'], 'token' => $GLOBALS['discord_interaction']['token'] ?? '',
            'data' => ['options' => [['name' => 'dream', 'options' => []]]]]);
        return;
    }
    if ($customId === 'consciousness_emotion') {
        handleConsciousness(['member' => $data['member'], 'token' => $GLOBALS['discord_interaction']['token'] ?? '',
            'data' => ['options' => [['name' => 'emotion', 'options' => []]]]]);
        return;
    }
    if ($customId === 'consciousness_reflect') {
        handleConsciousness(['member' => $data['member'], 'token' => $GLOBALS['discord_interaction']['token'] ?? '',
            'data' => ['options' => [['name' => 'reflect', 'options' => []]]]]);
        return;
    }
    if ($customId === 'consciousness_briefing') {
        handleConsciousness(['member' => $data['member'], 'token' => $GLOBALS['discord_interaction']['token'] ?? '',
            'data' => ['options' => [['name' => 'briefing', 'options' => []]]]]);
        return;
    }
    if ($customId === 'consciousness_growth') {
        handleConsciousness(['member' => $data['member'], 'token' => $GLOBALS['discord_interaction']['token'] ?? '',
            'data' => ['options' => [['name' => 'growth', 'options' => []]]]]);
        return;
    }
    if ($customId === 'consciousness_journal') {
        handleConsciousness(['member' => $data['member'], 'token' => $GLOBALS['discord_interaction']['token'] ?? '',
            'data' => ['options' => [['name' => 'journal', 'options' => [['name' => 'action', 'value' => 'view']]]]]]);
        return;
    }

    // ── Learning Module buttons ──
    if ($customId === 'learn_insights') {
        handleLearn(['member' => $data['member'], 'token' => $GLOBALS['discord_interaction']['token'] ?? '',
            'data' => ['options' => [['name' => 'insights', 'options' => []]]]]);
        return;
    }
    if ($customId === 'learn_experiments') {
        handleLearn(['member' => $data['member'], 'token' => $GLOBALS['discord_interaction']['token'] ?? '',
            'data' => ['options' => [['name' => 'experiments', 'options' => [['name' => 'action', 'value' => 'list']]]]]]);
        return;
    }
    if ($customId === 'learn_performance') {
        handleLearn(['member' => $data['member'], 'data' => ['options' => [['name' => 'performance', 'options' => []]]]]);
        return;
    }
    if ($customId === 'learn_patterns') {
        handleLearn(['member' => $data['member'], 'token' => $GLOBALS['discord_interaction']['token'] ?? '',
            'data' => ['options' => [['name' => 'patterns', 'options' => []]]]]);
        return;
    }
    if ($customId === 'learn_feedback') {
        respondEphemeral("📝 Use `/learn feedback rating:<1-10>` to submit feedback!");
        return;
    }
    if (str_starts_with($customId, 'experiment_vote_')) {
        $parts = explode('_', $customId);
        $expId = (int)($parts[2] ?? 0);
        $vote = $parts[3] ?? 'a';
        if ($expId > 0 && $pdo) {
            $col = $vote === 'b' ? 'votes_b' : 'votes_a';
            $pdo->prepare("UPDATE discord_experiments SET $col = $col + 1 WHERE id = ?")->execute([$expId]);
            respondEphemeral("✅ Your vote for " . strtoupper($vote) . " on experiment #$expId has been recorded!");
        }
        return;
    }

    // ── Feeds Module buttons ──
    if ($customId === 'feeds_list') {
        handleFeeds(['member' => $data['member'], 'data' => ['options' => [['name' => 'list', 'options' => []]]]]);
        return;
    }
    if ($customId === 'feeds_digest') {
        handleFeeds(['member' => $data['member'], 'token' => $GLOBALS['discord_interaction']['token'] ?? '',
            'data' => ['options' => [['name' => 'digest', 'options' => []]]]]);
        return;
    }
    if ($customId === 'feeds_news' || str_starts_with($customId, 'feeds_news_')) {
        $category = str_starts_with($customId, 'feeds_news_') ? substr($customId, 11) : 'technology';
        handleFeeds(['member' => $data['member'], 'token' => $GLOBALS['discord_interaction']['token'] ?? '',
            'data' => ['options' => [['name' => 'news', 'options' => [['name' => 'category', 'value' => $category]]]]]]);
        return;
    }
    if ($customId === 'feeds_subscribe_prompt' || $customId === 'feeds_manage') {
        respondEphemeral("📡 Use `/feeds subscribe url:<url> name:<name>` to add a feed!");
        return;
    }

    // ── DeFi Module buttons ──
    if ($customId === 'defi_portfolio') {
        handleDefi(['member' => $data['member'], 'data' => ['options' => [['name' => 'portfolio', 'options' => []]]]]);
        return;
    }
    if ($customId === 'defi_chains') {
        handleDefi(['member' => $data['member'], 'data' => ['options' => [['name' => 'chains', 'options' => []]]]]);
        return;
    }
    if ($customId === 'defi_alerts_list') {
        handleDefi(['member' => $data['member'], 'data' => ['options' => [['name' => 'alerts', 'options' => [['name' => 'action', 'value' => 'list']]]]]]);
        return;
    }
    if (str_starts_with($customId, 'defi_close_')) {
        $asset = substr($customId, 11);
        handleDefi(['member' => $data['member'], 'data' => ['options' => [['name' => 'positions', 'options' => [
            ['name' => 'action', 'value' => 'close'], ['name' => 'asset', 'value' => $asset]
        ]]]]]);
        return;
    }
    if ($customId === 'defi_position_prompt' || $customId === 'defi_convert_prompt' || str_starts_with($customId, 'defi_alert_prompt_')) {
        respondEphemeral("📈 Use `/defi positions action:open asset:BTC amount:1` to open a position!");
        return;
    }

    // ── Source Card Module buttons ──
    if ($customId === 'sourcecard_view') {
        handleSourcecard(['member' => $data['member'], 'data' => ['options' => [['name' => 'view', 'options' => []]]]]);
        return;
    }
    if ($customId === 'sourcecard_reputation') {
        handleSourcecard(['member' => $data['member'], 'data' => ['options' => [['name' => 'reputation', 'options' => []]]]]);
        return;
    }
    if ($customId === 'sourcecard_tier') {
        handleSourcecard(['member' => $data['member'], 'data' => ['options' => [['name' => 'tier', 'options' => []]]]]);
        return;
    }
    if ($customId === 'sourcecard_lineage') {
        handleSourcecard(['member' => $data['member'], 'data' => ['options' => [['name' => 'lineage', 'options' => []]]]]);
        return;
    }
    if ($customId === 'sourcecard_contribute_prompt') {
        respondEphemeral("🎁 Use `/sourcecard contribute title:<name> type:<type>` to log a contribution!");
        return;
    }

    // ── Server Module buttons ──
    if ($customId === 'server_settings' || $customId === 'server_config_view') {
        respondEphemeral("⚙️ Use `/server` subcommands to configure server settings!");
        return;
    }
    if ($customId === 'server_autorole_remove') {
        respondEphemeral("🗑️ Use `/server autorole` without a role to view/clear the setting.");
        return;
    }

    // ── Games2 Module buttons ──
    if (str_starts_with($customId, 'hm_')) {
        // Hangman letter guess: hm_{gameId}_{letter}
        $parts = explode('_', $customId);
        $gameId = (int)($parts[1] ?? 0);
        $letter = $parts[2] ?? '';
        if ($gameId > 0 && $letter && $pdo) {
            $stmt = $pdo->prepare("SELECT game_data FROM discord_games2 WHERE id = ? AND game_type = 'hangman' AND status = 'active'");
            $stmt->execute([$gameId]);
            $row = $stmt->fetch();
            if (!$row) { respondEphemeral("Game not found or ended."); return; }
            $gd = json_decode($row['game_data'], true);
            $word = $gd['word'];
            $guessed = $gd['guessed'];
            $remaining = $gd['remaining'];
            $revealed = $gd['revealed'];

            if (in_array($letter, $guessed)) { respondEphemeral("You already guessed '$letter'."); return; }
            $guessed[] = $letter;
            $hit = false;
            for ($i = 0; $i < strlen($word); $i++) {
                if ($word[$i] === $letter) { $revealed[$i] = true; $hit = true; }
            }
            if (!$hit) $remaining--;
            $gd['guessed'] = $guessed; $gd['remaining'] = $remaining; $gd['revealed'] = $revealed;

            $display = '';
            $won = true;
            for ($i = 0; $i < strlen($word); $i++) {
                if ($revealed[$i]) { $display .= $word[$i] . ' '; } else { $display .= '_ '; $won = false; }
            }

            if ($won) {
                $pdo->prepare("UPDATE discord_games2 SET status = 'won', game_data = ? WHERE id = ?")->execute([json_encode($gd), $gameId]);
                awardXP($userId, 15);
                respond(null, [embed("🎯 Hangman — WIN! 🎉", "```\n" . getHangmanArt(8 - $remaining) . "\n```\n**Word:** `$word`\n\n🎉 You got it!", 0x2ECC71)], [
                    actionRow(btn(1, '🔄 Play Again', 'games2_hangman_start'))
                ]);
            } elseif ($remaining <= 0) {
                $pdo->prepare("UPDATE discord_games2 SET status = 'lost', game_data = ? WHERE id = ?")->execute([json_encode($gd), $gameId]);
                respond(null, [embed("🎯 Hangman — Game Over", "```\n" . getHangmanArt(8) . "\n```\n**The word was:** `$word`", 0xE74C3C)], [
                    actionRow(btn(1, '🔄 Play Again', 'games2_hangman_start'))
                ]);
            } else {
                $pdo->prepare("UPDATE discord_games2 SET game_data = ? WHERE id = ?")->execute([json_encode($gd), $gameId]);
                $art = getHangmanArt(8 - $remaining);
                respond(null, [embed("🎯 Hangman", "```\n$art\n```\n**Word:** `$display`\n**Remaining:** $remaining\n**Used:** " . implode(', ', $guessed), 0x3498DB, [], [
                    'footer' => ["text" => "Game #$gameId"],
                ])], [
                    actionRow(btn(1,'A',"hm_{$gameId}_A"),btn(1,'E',"hm_{$gameId}_E"),btn(1,'I',"hm_{$gameId}_I"),btn(1,'O',"hm_{$gameId}_O"),btn(1,'U',"hm_{$gameId}_U")),
                    actionRow(btn(2,'S',"hm_{$gameId}_S"),btn(2,'T',"hm_{$gameId}_T"),btn(2,'R',"hm_{$gameId}_R"),btn(2,'N',"hm_{$gameId}_N"),btn(2,'L',"hm_{$gameId}_L")),
                ]);
            }
        }
        return;
    }
    if (str_starts_with($customId, 'tower_')) {
        $parts = explode('_', $customId);
        $gameId = (int)($parts[1] ?? 0);
        $choice = $parts[2] ?? '';
        if ($gameId > 0 && $pdo) {
            $stmt = $pdo->prepare("SELECT discord_id, game_data FROM discord_games2 WHERE id = ? AND game_type = 'tower' AND status = 'active'");
            $stmt->execute([$gameId]);
            $row = $stmt->fetch();
            if (!$row) { respondEphemeral("Game not found or ended."); return; }
            if ($row['discord_id'] !== $userId) { respondEphemeral("Not your game!"); return; }
            $gd = json_decode($row['game_data'], true);

            if ($choice === 'cashout') {
                $winnings = (int)($gd['bet'] * $gd['multiplier']);
                $pdo->prepare("UPDATE discord_users SET coins = coins + ? WHERE discord_id = ?")->execute([$winnings, $userId]);
                $pdo->prepare("UPDATE discord_games2 SET status = 'won' WHERE id = ?")->execute([$gameId]);
                awardXP($userId, min(20, (int)$gd['floor'] * 2));
                respond(null, [embed("🗼 Tower — Cashed Out!", "**Floor:** {$gd['floor']}/{$gd['max_floor']}\n**Multiplier:** {$gd['multiplier']}x\n**Winnings:** $winnings KGD 💰", 0x2ECC71)], [
                    actionRow(btn(1, '🔄 Play Again', 'games2_tower_start'))
                ]);
                return;
            }

            $door = (int)$choice;
            $safe = $gd['safe_spots'][$gd['floor']] ?? 0;
            if ($door === $safe) {
                // Safe! Climb
                $gd['floor']++;
                $gd['multiplier'] = round(1.0 + ($gd['floor'] * 0.5), 1);
                $pdo->prepare("UPDATE discord_games2 SET game_data = ? WHERE id = ?")->execute([json_encode($gd), $gameId]);
                $potential = (int)($gd['bet'] * $gd['multiplier']);

                if ($gd['floor'] >= $gd['max_floor']) {
                    $pdo->prepare("UPDATE discord_users SET coins = coins + ? WHERE discord_id = ?")->execute([$potential, $userId]);
                    $pdo->prepare("UPDATE discord_games2 SET status = 'won' WHERE id = ?")->execute([$gameId]);
                    awardXP($userId, 25);
                    respond(null, [embed("🗼 Tower — TOP REACHED! 🎉", "**You conquered the tower!**\n**Multiplier:** {$gd['multiplier']}x\n**Winnings:** $potential KGD 🏆", 0xF1C40F)], [
                        actionRow(btn(1, '🔄 Play Again', 'games2_tower_start'))
                    ]);
                } else {
                    respond(null, [embed("🗼 Tower — Floor {$gd['floor']}", "✅ Safe! You climb higher!\n\n**Multiplier:** {$gd['multiplier']}x\n**Potential:** $potential KGD\n\nChoose a door or cash out!", 0x2ECC71, [], [
                        'footer' => ["text" => "Game #$gameId • Floor {$gd['floor']}/{$gd['max_floor']}"],
                    ])], [actionRow(
                        btn(1, '🚪 Door 1', "tower_{$gameId}_0"),
                        btn(1, '🚪 Door 2', "tower_{$gameId}_1"),
                        btn(1, '🚪 Door 3', "tower_{$gameId}_2"),
                        btn(3, '💰 Cash Out', "tower_{$gameId}_cashout")
                    )]);
                }
            } else {
                // BOOM
                $pdo->prepare("UPDATE discord_games2 SET status = 'lost' WHERE id = ?")->execute([$gameId]);
                respond(null, [embed("🗼 Tower — COLLAPSE! 💥", "**Floor {$gd['floor']}** — You chose the wrong door!\n**Lost:** {$gd['bet']} KGD\n\nThe safe door was Door " . ($safe + 1), 0xE74C3C)], [
                    actionRow(btn(1, '🔄 Play Again', 'games2_tower_start'))
                ]);
            }
        }
        return;
    }
    if (str_starts_with($customId, 'slots_')) {
        $bet = (int)substr($customId, 6);
        handleGames2(['member' => $data['member'], 'data' => ['options' => [['name' => 'slots', 'options' => [['name' => 'bet', 'value' => $bet]]]]]]);
        return;
    }
    if (str_starts_with($customId, 'coinflip_')) {
        $parts = explode('_', $customId);
        $side = $parts[1] ?? 'heads';
        $bet = (int)($parts[2] ?? 10);
        handleGames2(['member' => $data['member'], 'data' => ['options' => [['name' => 'coinflip', 'options' => [['name' => 'side', 'value' => $side], ['name' => 'bet', 'value' => $bet]]]]]]);
        return;
    }
    if (str_starts_with($customId, 'duel_accept_')) {
        $duelId = (int)substr($customId, 12);
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT game_data FROM discord_games2 WHERE game_type = 'duel' AND status = 'pending' AND JSON_EXTRACT(game_data, '$.duel_id') = ?");
            $stmt->execute([$duelId]);
            $row = $stmt->fetch();
            if (!$row) { respondEphemeral("Duel not found or expired."); return; }
            $gd = json_decode($row['game_data'], true);
            if ($gd['opponent'] !== $userId) { respondEphemeral("This duel isn't for you!"); return; }

            // Resolve duel
            $challengerRoll = mt_rand(1, 100);
            $opponentRoll = mt_rand(1, 100);
            $winner = $challengerRoll >= $opponentRoll ? $gd['challenger'] : $gd['opponent'];
            $loser = $winner === $gd['challenger'] ? $gd['opponent'] : $gd['challenger'];

            $pdo->prepare("UPDATE discord_users SET coins = coins + ? WHERE discord_id = ?")->execute([$gd['bet'], $winner]);
            $pdo->prepare("UPDATE discord_users SET coins = coins - ? WHERE discord_id = ?")->execute([$gd['bet'], $loser]);
            $pdo->prepare("UPDATE discord_games2 SET status = 'completed' WHERE game_type = 'duel' AND JSON_EXTRACT(game_data, '$.duel_id') = ?")->execute([$duelId]);
            awardXP($winner, 10);

            respond(null, [embed("⚔️ Duel Result!", "<@{$gd['challenger']}> rolled **$challengerRoll** vs <@{$gd['opponent']}> rolled **$opponentRoll**\n\n🏆 <@$winner> wins **{$gd['bet']} KGD**!", $winner === $gd['challenger'] ? 0x2ECC71 : 0xE74C3C)]);
        }
        return;
    }
    if (str_starts_with($customId, 'duel_decline_')) {
        respondEphemeral("🏳️ Duel declined.");
        return;
    }
    if ($customId === 'games2_wordle_start' || $customId === 'games2_hangman_start' || $customId === 'games2_tower_start') {
        $game = str_replace(['games2_', '_start'], '', $customId);
        respondEphemeral("🎮 Use `/games2 $game` to start a new game!");
        return;
    }
    if ($customId === 'economy_balance') {
        handleCoins(['member' => $data['member'], 'guild_id' => $guildId, 'data' => ['options' => [['name' => 'balance', 'type' => 1, 'options' => []]]]]);
        return;
    }

    // ── Fallback ──
    respondEphemeral("🔘 Button action not recognized: `$customId`");
}


function handleModalSubmit(array $data): void {
    $customId = $data['data']['custom_id'] ?? '';
    $userId = $data['member']['user']['id'] ?? '0';

    // Ticket reply modal
    if (str_starts_with($customId, 'ticket_reply_modal_')) {
        $ticketId = (int)substr($customId, 19);
        $components = $data['data']['components'] ?? [];
        $replyText = '';
        foreach ($components as $row) {
            foreach ($row['components'] ?? [] as $c) {
                if ($c['custom_id'] === 'reply_text') $replyText = $c['value'] ?? '';
            }
        }
        if (!$replyText) { respondEphemeral("Reply cannot be empty."); return; }

        $pdo = getDiscordDB();
        if (!$pdo) { respondEphemeral("⚠️ DB unavailable"); return; }

        $stmt = $pdo->prepare("SELECT * FROM discord_tickets WHERE id = ? AND status = 'open'");
        $stmt->execute([$ticketId]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$ticket) { respondEphemeral("Ticket not found or closed."); return; }

        $messages = json_decode($ticket['messages'], true) ?: [];
        $messages[] = ['user' => $userId, 'text' => $replyText, 'time' => date('Y-m-d H:i:s')];
        $pdo->prepare("UPDATE discord_tickets SET messages = ?, updated_at = NOW() WHERE id = ?")
            ->execute([json_encode($messages), $ticketId]);

        respond(null, [embed("🎫 Ticket #$ticketId — Reply", "<@$userId>: $replyText", 0x5865F2)], [
            actionRow([
                btn("ticket_reply_$ticketId", '💬 Reply', 1),
                btn("ticket_close_$ticketId", '🔒 Close', 4),
            ]),
        ]);
        return;
    }

    respondEphemeral("Modal action not recognized.");
}


function handleSelectMenu(array $data): void {
    $customId = $data['data']['custom_id'] ?? '';
    $values = $data['data']['values'] ?? [];

    respondEphemeral("Selection received: " . implode(', ', $values));
}
