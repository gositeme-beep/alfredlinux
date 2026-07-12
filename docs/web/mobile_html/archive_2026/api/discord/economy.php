<?php
/**
 * GoSiteMe Discord Bot — Economy Module
 * ══════════════════════════════════════
 * /coins  — Balance, send, leaderboard
 * /daily  — Daily reward with streaks
 * /shop   — Buy items with KGD
 * /gamble — Coin flip, dice, slots
 */

function handleCoins(array $data): void {
    $subCmd = getSubcommand($data);
    $opts = getSubOptions($data);
    $userId = $data['member']['user']['id'] ?? '0';
    $username = $data['member']['user']['username'] ?? 'User';
    $globalName = $data['member']['user']['global_name'] ?? $username;
    $resolved = $data['data']['resolved'] ?? [];
    $user = getOrCreateUser($userId, $username);
    $pdo = getDiscordDB();

    switch ($subCmd ?? 'balance') {
        case 'balance':
            $target = $opts['user'] ?? $userId;
            $tUser = ($target === $userId) ? $user : getOrCreateUser($target, 'User');
            $tName = $tUser['discord_name'];
            $title = eloTitle(max($tUser['elo_chess'] ?? 1000, $tUser['elo_checkers'] ?? 1000));
            $lvlTitle = levelTitle($tUser['level'] ?? 1);

            $history = '';
            if ($pdo) {
                $stmt = $pdo->prepare("SELECT entry_type, amount, reason, created_at FROM discord_economy WHERE discord_id = ? ORDER BY created_at DESC LIMIT 8");
                $stmt->execute([$target]);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $tx) {
                    $sign = in_array($tx['entry_type'], ['earn', 'transfer_in', 'wager_win', 'signup_bonus']) ? '+' : '-';
                    $history .= "`{$sign}{$tx['amount']}` {$tx['reason']}\n";
                }
            }

            respond(null, [
                embed("💰 {$tName}'s Kingdom Wallet", $history ? "**Recent Activity:**\n$history" : '', 0xF1C40F, [
                    field('💎 KGD Balance', number_format($tUser['kgd_balance'] ?? 100), true),
                    field('📊 Level', "Lv.{$tUser['level']} $lvlTitle", true),
                    field('👑 ELO Title', $title, true),
                    field('📈 Total Earned', number_format($tUser['total_earned'] ?? 0) . ' KGD', true),
                    field('📉 Total Spent', number_format($tUser['total_spent'] ?? 0) . ' KGD', true),
                    field('🔥 Daily Streak', ($tUser['daily_streak'] ?? 0) . ' days', true),
                ], [
                    'footer' => ['text' => 'GoSiteMe Economy | /daily for free coins!'],
                    'thumbnail' => ['url' => 'https://gositeme.com/assets/images/logo-icon.png'],
                ])
            ], [
                actionRow(
                    btn(1, '🎁 Daily', 'daily_claim'),
                    btn(1, '🛒 Shop', 'shop_browse'),
                    btn(1, '🏆 Leaderboard', 'leaderboard_view'),
                    btn(5, '🌐 GoSiteMe', 'https://gositeme.com')
                )
            ]);
            break;

        case 'send':
            $targetId = $opts['user'] ?? null;
            $amount = max(0, (int)($opts['amount'] ?? 0));
            if (!$targetId || $amount < 1) { respond("Usage: `/coins send user:@someone amount:50`"); return; }
            if ($targetId === $userId) { respond("You can't send coins to yourself!"); return; }
            if (($user['kgd_balance'] ?? 0) < $amount) { respond("Insufficient balance! You have {$user['kgd_balance']} KGD."); return; }
            if ($amount > 100000) { respond("Maximum single transfer: 100,000 KGD."); return; }
            if (!$pdo) { respond("⚠️ Database unavailable."); return; }

            $targetName = $resolved['users'][$targetId]['username'] ?? 'User';
            getOrCreateUser($targetId, $targetName);

            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("UPDATE discord_users SET kgd_balance = kgd_balance - ?, total_spent = total_spent + ? WHERE discord_id = ? AND kgd_balance >= ?");
                $stmt->execute([$amount, $amount, $userId, $amount]);
                if ($stmt->rowCount() === 0) { $pdo->rollBack(); respond("Insufficient balance!"); return; }

                $pdo->prepare("UPDATE discord_users SET kgd_balance = kgd_balance + ?, total_earned = total_earned + ? WHERE discord_id = ?")
                    ->execute([$amount, $amount, $targetId]);
                $pdo->prepare("INSERT INTO discord_economy (discord_id, entry_type, amount, reason) VALUES (?, 'transfer_out', ?, ?)")
                    ->execute([$userId, $amount, "Sent to $targetName"]);
                $pdo->prepare("INSERT INTO discord_economy (discord_id, entry_type, amount, reason) VALUES (?, 'transfer_in', ?, ?)")
                    ->execute([$targetId, $amount, "Received from $globalName"]);
                $pdo->commit();

                respond(null, [embed("💸 Transfer Complete", "**$globalName** sent **$amount KGD** to <@$targetId>", 0x57F287, [
                    field('Amount', number_format($amount) . ' KGD', true),
                    field('New Balance', number_format(($user['kgd_balance'] ?? 0) - $amount) . ' KGD', true),
                ])]);
            } catch (\Exception $e) {
                $pdo->rollBack();
                respond("⚠️ Transfer failed. Please try again.");
            }
            break;

        case 'leaderboard':
            if (!$pdo) { respond("⚠️ Database unavailable."); return; }
            $type = $opts['type'] ?? 'kgd';
            $orderCol = ['kgd' => 'kgd_balance', 'level' => 'xp', 'chess' => 'elo_chess', 'wins' => 'games_won'][$type] ?? 'kgd_balance';
            $stmt = $pdo->query("SELECT discord_name, kgd_balance, level, xp, elo_chess, games_won FROM discord_users ORDER BY $orderCol DESC LIMIT 15");
            $leaders = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $board = '';
            $medals = ['🥇', '🥈', '🥉'];
            foreach ($leaders as $i => $l) {
                $medal = $medals[$i] ?? '`' . ($i + 1) . '.`';
                $val = match($type) {
                    'level' => "Lv.{$l['level']} ({$l['xp']} XP)",
                    'chess' => "ELO {$l['elo_chess']}",
                    'wins' => "{$l['games_won']} wins",
                    default => number_format($l['kgd_balance']) . ' KGD',
                };
                $board .= "$medal **{$l['discord_name']}** — $val\n";
            }

            $titles = ['kgd' => '💎 Richest Players', 'level' => '📊 Highest Levels', 'chess' => '♟️ Top Chess Players', 'wins' => '🏆 Most Wins'];
            respond(null, [
                embed($titles[$type] ?? '🏆 Leaderboard', $board ?: 'No players yet!', 0xF1C40F, [], [
                    'footer' => ['text' => 'GoSiteMe | /coins balance to check yours'],
                ])
            ], [
                actionRow(
                    btn($type==='kgd'?1:2, '💎 KGD', 'lb_kgd'),
                    btn($type==='level'?1:2, '📊 Level', 'lb_level'),
                    btn($type==='chess'?1:2, '♟️ Chess', 'lb_chess'),
                    btn($type==='wins'?1:2, '🏆 Wins', 'lb_wins')
                )
            ]);
            break;
    }
}


function handleDaily(array $data): void {
    $userId = $data['member']['user']['id'] ?? '0';
    $username = $data['member']['user']['username'] ?? 'User';
    $globalName = $data['member']['user']['global_name'] ?? $username;
    $pdo = getDiscordDB();
    if (!$pdo) { respond("⚠️ Database unavailable."); return; }

    $user = getOrCreateUser($userId, $username);
    $lastDaily = $user['last_daily'] ?? null;
    $today = date('Y-m-d');

    if ($lastDaily === $today) {
        respond(null, [embed("⏰ Already Claimed!", "You already claimed your daily reward today!\nCome back <t:" . strtotime('tomorrow midnight') . ":R>", 0xED4245)]);
        return;
    }

    // Calculate streak
    $streak = ($user['daily_streak'] ?? 0);
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    if ($lastDaily === $yesterday) {
        $streak++;
    } else {
        $streak = 1; // Reset streak
    }

    // Base reward + streak bonus + level bonus
    $base = 50;
    $streakBonus = min($streak * 10, 200); // Max 200 bonus from streak
    $levelBonus = ($user['level'] ?? 1) * 5;
    $total = $base + $streakBonus + $levelBonus;

    // Lucky bonus (10% chance of 2x)
    $lucky = mt_rand(1, 10) === 1;
    if ($lucky) $total *= 2;

    // Streak milestones
    $milestone = '';
    if ($streak === 7) { $total += 200; $milestone = "\n🎉 **7-DAY STREAK BONUS: +200 KGD!**"; }
    elseif ($streak === 30) { $total += 1000; $milestone = "\n🎉 **30-DAY STREAK BONUS: +1000 KGD!**"; }
    elseif ($streak === 100) { $total += 5000; $milestone = "\n🎉 **100-DAY STREAK BONUS: +5000 KGD!**"; }
    elseif ($streak === 365) { $total += 25000; $milestone = "\n🎉 **365-DAY STREAK BONUS: +25000 KGD!!**"; }

    $pdo->prepare("UPDATE discord_users SET kgd_balance = kgd_balance + ?, daily_streak = ?, last_daily = ?, total_earned = total_earned + ? WHERE discord_id = ?")
        ->execute([$total, $streak, $today, $total, $userId]);
    $pdo->prepare("INSERT INTO discord_economy (discord_id, entry_type, amount, reason) VALUES (?, 'earn', ?, ?)")
        ->execute([$userId, $total, "Daily reward (day $streak)"]);
    awardXP($userId, 15);

    $newBal = ($user['kgd_balance'] ?? 0) + $total;
    $desc = "**+$total KGD** claimed!\n\n";
    $desc .= "📦 Base: $base KGD\n";
    $desc .= "🔥 Streak ($streak days): +$streakBonus KGD\n";
    $desc .= "📊 Level bonus (Lv.{$user['level']}): +$levelBonus KGD\n";
    if ($lucky) $desc .= "🍀 **LUCKY! 2x BONUS!**\n";
    $desc .= $milestone;
    $desc .= "\n💎 New Balance: **" . number_format($newBal) . " KGD**";

    $streakBar = str_repeat('🔥', min($streak, 7)) . str_repeat('⬜', max(0, 7 - $streak));
    $desc .= "\n\n$streakBar";
    if ($streak < 7) $desc .= " *" . (7 - $streak) . " more days to weekly bonus!*";

    respond(null, [
        embed("🎁 Daily Reward — Day $streak", $desc, $lucky ? 0xF1C40F : 0x57F287, [], [
            'footer' => ['text' => "Next daily: tomorrow | GoSiteMe"],
            'thumbnail' => ['url' => 'https://gositeme.com/assets/images/logo-icon.png'],
        ])
    ], [
        actionRow(
            btn(1, '💰 Balance', 'coins_balance'),
            btn(1, '🛒 Shop', 'shop_browse'),
            btn(1, '🎮 Play Chess', 'quick_chess')
        )
    ]);
}


function handleShop(array $data): void {
    $subCmd = getSubcommand($data);
    $opts = getSubOptions($data);
    $userId = $data['member']['user']['id'] ?? '0';
    $username = $data['member']['user']['username'] ?? 'User';
    $globalName = $data['member']['user']['global_name'] ?? $username;
    $pdo = getDiscordDB();
    if (!$pdo) { respond("⚠️ Database unavailable."); return; }
    $user = getOrCreateUser($userId, $username);

    switch ($subCmd ?? 'browse') {
        case 'browse':
            $category = $opts['category'] ?? 'all';
            $where = $category === 'all' ? '' : "AND category = " . $pdo->quote($category);
            $stmt = $pdo->query("SELECT * FROM discord_shop_items WHERE active = 1 $where ORDER BY price ASC LIMIT 15");
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $desc = '';
            foreach ($items as $it) {
                $stock = $it['stock'] == -1 ? '∞' : $it['stock'];
                $desc .= "{$it['emoji']} **{$it['name']}** — `{$it['price']} KGD`\n";
                $desc .= "   {$it['description']} (Stock: $stock)\n\n";
            }
            if (!$desc) $desc = 'No items available in this category.';

            $cats = ['all', 'titles', 'badges', 'boosts', 'skins', 'colors', 'lootbox', 'real'];
            respond(null, [
                embed("🛒 Kingdom Shop", $desc, 0x9B59B6, [
                    field('💎 Your Balance', number_format($user['kgd_balance']) . ' KGD', true),
                    field('📦 Category', ucfirst($category), true),
                ], [
                    'footer' => ['text' => '/shop buy item:name | /shop inventory'],
                ])
            ], [
                actionRow(
                    btn(1, '🏷️ Titles', 'shop_cat_titles'),
                    btn(1, '⭐ Badges', 'shop_cat_badges'),
                    btn(1, '⚡ Boosts', 'shop_cat_boosts'),
                    btn(1, '📦 Loot Boxes', 'shop_cat_lootbox'),
                    btn(2, '🎫 Real Rewards', 'shop_cat_real')
                )
            ]);
            break;

        case 'buy':
            $itemName = $opts['item'] ?? '';
            if (!$itemName) { respond("Specify an item! `/shop buy item:XP Boost 2x`"); return; }

            $stmt = $pdo->prepare("SELECT * FROM discord_shop_items WHERE name = ? AND active = 1");
            $stmt->execute([$itemName]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$item) {
                // Try fuzzy search
                $stmt = $pdo->prepare("SELECT * FROM discord_shop_items WHERE name LIKE ? AND active = 1 LIMIT 1");
                $stmt->execute(["%$itemName%"]);
                $item = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            if (!$item) { respond("Item not found: `$itemName`. Use `/shop browse` to see available items."); return; }

            if (($user['kgd_balance'] ?? 0) < $item['price']) {
                respond("Not enough KGD! The **{$item['name']}** costs {$item['price']} KGD, you have {$user['kgd_balance']}.");
                return;
            }
            if ($item['stock'] == 0) { respond("Sorry, **{$item['name']}** is out of stock!"); return; }
            if ($item['required_level'] > ($user['level'] ?? 1)) { respond("You need Level {$item['required_level']} to buy this item! You're Level {$user['level']}."); return; }

            $pdo->beginTransaction();
            try {
                $pdo->prepare("UPDATE discord_users SET kgd_balance = kgd_balance - ?, total_spent = total_spent + ? WHERE discord_id = ? AND kgd_balance >= ?")
                    ->execute([$item['price'], $item['price'], $userId, $item['price']]);
                if ($item['stock'] > 0) $pdo->prepare("UPDATE discord_shop_items SET stock = stock - 1 WHERE id = ?")->execute([$item['id']]);

                // Handle loot boxes specially
                if ($item['item_type'] === 'lootbox') {
                    $lootReward = handleLootBox($item, $userId, $globalName, $pdo);
                    $pdo->prepare("INSERT INTO discord_economy (discord_id, entry_type, amount, reason) VALUES (?, 'spend', ?, ?)")
                        ->execute([$userId, $item['price'], "Bought {$item['name']}"]);
                    $pdo->commit();
                    respond(null, [embed("{$item['emoji']} Loot Box Opened!", $lootReward, 0xF1C40F)]);
                    return;
                }

                // Add to inventory
                $pdo->prepare("INSERT INTO discord_inventory (discord_id, item_id, quantity) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE quantity = quantity + 1")
                    ->execute([$userId, $item['id']]);
                $pdo->prepare("INSERT INTO discord_economy (discord_id, entry_type, amount, reason) VALUES (?, 'spend', ?, ?)")
                    ->execute([$userId, $item['price'], "Bought {$item['name']}"]);
                $pdo->commit();

                respond(null, [embed("{$item['emoji']} Item Purchased!", "You bought **{$item['name']}** for **{$item['price']} KGD**!\n\nNew balance: " . number_format(($user['kgd_balance'] ?? 0) - $item['price']) . ' KGD', 0x57F287)]);
            } catch (\Exception $e) {
                $pdo->rollBack();
                respond("⚠️ Purchase failed. Please try again.");
            }
            break;

        case 'inventory':
            $stmt = $pdo->prepare("SELECT i.*, s.name, s.emoji, s.item_type FROM discord_inventory i JOIN discord_shop_items s ON i.item_id = s.id WHERE i.discord_id = ? ORDER BY i.acquired_at DESC");
            $stmt->execute([$userId]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $desc = '';
            foreach ($items as $it) {
                $eq = $it['equipped'] ? ' *(equipped)*' : '';
                $desc .= "{$it['emoji']} **{$it['name']}** x{$it['quantity']}$eq\n";
            }
            if (!$desc) $desc = "Your inventory is empty! Visit `/shop browse` to buy items.";

            respond(null, [
                embed("🎒 {$globalName}'s Inventory", $desc, 0x3498DB, [
                    field('💎 Balance', number_format($user['kgd_balance']) . ' KGD', true),
                    field('📦 Items', (string)count($items), true),
                ])
            ]);
            break;
    }
}


function handleLootBox(array $item, string $userId, string $name, PDO $pdo): string {
    $tier = 'bronze';
    if (stripos($item['name'], 'Silver') !== false) $tier = 'silver';
    elseif (stripos($item['name'], 'Gold') !== false) $tier = 'gold';

    $kgdRanges = ['bronze' => [50, 200], 'silver' => [200, 1000], 'gold' => [1000, 5000]];
    $range = $kgdRanges[$tier];
    $kgdWon = mt_rand($range[0], $range[1]);

    $pdo->prepare("UPDATE discord_users SET kgd_balance = kgd_balance + ?, total_earned = total_earned + ? WHERE discord_id = ?")
        ->execute([$kgdWon, $kgdWon, $userId]);
    $pdo->prepare("INSERT INTO discord_economy (discord_id, entry_type, amount, reason) VALUES (?, 'earn', ?, ?)")
        ->execute([$userId, $kgdWon, "Loot Box ($tier)"]);

    $result = "💎 **$kgdWon KGD**\n";

    // Item chance
    $itemChance = ['bronze' => 30, 'silver' => 70, 'gold' => 100][$tier];
    if (mt_rand(1, 100) <= $itemChance) {
        $rarity = $tier === 'gold' ? 'rare' : ($tier === 'silver' ? 'uncommon' : 'common');
        $prizes = [
            'common' => ['🔥 Fire Badge', '⭐ Star Badge'],
            'uncommon' => ['👑 Crown Badge', '⚡ XP Boost 2x'],
            'rare' => ['💎 Diamond Badge', '💰 KGD Boost 2x', '✨ Gold Chess Skin'],
        ];
        $prize = $prizes[$rarity][array_rand($prizes[$rarity])];
        $result .= "🎁 **Bonus Item:** $prize\n";
    }

    $result .= "\n*Opened by $name*";
    return $result;
}


function handleGamble(array $data): void {
    $subCmd = getSubcommand($data);
    $opts = getSubOptions($data);
    $userId = $data['member']['user']['id'] ?? '0';
    $username = $data['member']['user']['username'] ?? 'User';
    $globalName = $data['member']['user']['global_name'] ?? $username;
    $pdo = getDiscordDB();
    if (!$pdo) { respond("⚠️ Database unavailable."); return; }
    $user = getOrCreateUser($userId, $username);

    switch ($subCmd ?? 'coinflip') {
        case 'coinflip':
            $bet = max(1, min(50000, (int)($opts['bet'] ?? 10)));
            $call = strtolower($opts['call'] ?? 'heads');
            if (($user['kgd_balance'] ?? 0) < $bet) { respond("Not enough KGD! Balance: {$user['kgd_balance']}"); return; }

            awardXP($userId, 3);
            $result = mt_rand(0, 1) === 0 ? 'heads' : 'tails';
            $won = $call === $result;

            if ($won) {
                $pdo->prepare("UPDATE discord_users SET kgd_balance = kgd_balance + ?, games_won = games_won + 1, total_earned = total_earned + ? WHERE discord_id = ?")->execute([$bet, $bet, $userId]);
                $pdo->prepare("INSERT INTO discord_economy (discord_id, entry_type, amount, reason) VALUES (?, 'wager_win', ?, 'Coin flip win')")->execute([$userId, $bet]);
                $desc = "🪙 The coin lands on **" . ucfirst($result) . "**!\n\n🎉 **You win $bet KGD!**\n💎 New balance: " . number_format(($user['kgd_balance'] ?? 0) + $bet);
                $color = 0x57F287;
            } else {
                $pdo->prepare("UPDATE discord_users SET kgd_balance = kgd_balance - ?, games_lost = games_lost + 1, total_spent = total_spent + ? WHERE discord_id = ? AND kgd_balance >= ?")->execute([$bet, $bet, $userId, $bet]);
                $pdo->prepare("INSERT INTO discord_economy (discord_id, entry_type, amount, reason) VALUES (?, 'wager_loss', ?, 'Coin flip loss')")->execute([$userId, $bet]);
                $desc = "🪙 The coin lands on **" . ucfirst($result) . "**!\n\n💀 **You lose $bet KGD.**\n💎 Balance: " . number_format(($user['kgd_balance'] ?? 0) - $bet);
                $color = 0xED4245;
            }

            respond(null, [embed('🪙 Coin Flip', $desc, $color, [
                field('Your Call', ucfirst($call), true),
                field('Result', ucfirst($result), true),
                field('Bet', "$bet KGD", true),
            ])], [
                actionRow(
                    btn(1, '🪙 Flip Again', "gamble_flip_{$bet}"),
                    btn(2, '🎰 Slots', 'gamble_slots_view'),
                    btn(2, '🎲 Dice', 'gamble_dice_view')
                )
            ]);
            break;

        case 'dice':
            $bet = max(1, min(50000, (int)($opts['bet'] ?? 10)));
            if (($user['kgd_balance'] ?? 0) < $bet) { respond("Not enough KGD!"); return; }

            awardXP($userId, 3);
            $playerDice = [mt_rand(1, 6), mt_rand(1, 6)];
            $aiDice = [mt_rand(1, 6), mt_rand(1, 6)];
            $playerTotal = array_sum($playerDice);
            $aiTotal = array_sum($aiDice);
            $diceEmoji = ['⚀','⚁','⚂','⚃','⚄','⚅'];

            $pEmoji = $diceEmoji[$playerDice[0]-1] . $diceEmoji[$playerDice[1]-1];
            $aEmoji = $diceEmoji[$aiDice[0]-1] . $diceEmoji[$aiDice[1]-1];

            if ($playerTotal > $aiTotal) {
                $winnings = $bet;
                if ($playerDice[0] === $playerDice[1]) $winnings = $bet * 2; // Doubles = 2x
                $pdo->prepare("UPDATE discord_users SET kgd_balance = kgd_balance + ?, games_won = games_won + 1, total_earned = total_earned + ? WHERE discord_id = ?")->execute([$winnings, $winnings, $userId]);
                $pdo->prepare("INSERT INTO discord_economy (discord_id, entry_type, amount, reason) VALUES (?, 'wager_win', ?, 'Dice win')")->execute([$userId, $winnings]);
                $resultText = "🎉 **You win " . ($playerDice[0]===$playerDice[1] ? "DOUBLES! " : "") . "$winnings KGD!**";
                $color = 0x57F287;
            } elseif ($playerTotal < $aiTotal) {
                $pdo->prepare("UPDATE discord_users SET kgd_balance = kgd_balance - ?, games_lost = games_lost + 1, total_spent = total_spent + ? WHERE discord_id = ? AND kgd_balance >= ?")->execute([$bet, $bet, $userId, $bet]);
                $pdo->prepare("INSERT INTO discord_economy (discord_id, entry_type, amount, reason) VALUES (?, 'wager_loss', ?, 'Dice loss')")->execute([$userId, $bet]);
                $resultText = "💀 **You lose $bet KGD.**";
                $color = 0xED4245;
            } else {
                $resultText = "🤝 **Tie! Bet returned.**";
                $color = 0xFEE75C;
            }

            respond(null, [embed('🎲 Dice Roll', '', $color, [
                field("$globalName", "$pEmoji ($playerTotal)", true),
                field('vs', '⚔️', true),
                field('Alfred', "$aEmoji ($aiTotal)", true),
                field('Result', $resultText, false),
            ])], [actionRow(btn(1, '🎲 Roll Again', "gamble_dice_{$bet}"))]);
            break;

        case 'slots':
            $bet = max(1, min(50000, (int)($opts['bet'] ?? 10)));
            if (($user['kgd_balance'] ?? 0) < $bet) { respond("Not enough KGD!"); return; }

            awardXP($userId, 3);
            $symbols = ['🍒', '🍋', '🍊', '🍇', '🔔', '💎', '7️⃣', '👑'];
            $weights = [25, 20, 18, 15, 10, 7, 3, 2]; // rarities
            $reels = [];
            for ($r = 0; $r < 3; $r++) {
                $rand = mt_rand(1, array_sum($weights));
                $cumul = 0;
                foreach ($weights as $i => $w) {
                    $cumul += $w;
                    if ($rand <= $cumul) { $reels[] = $symbols[$i]; break; }
                }
            }

            $multipliers = [
                '👑👑👑' => 100, '7️⃣7️⃣7️⃣' => 50, '💎💎💎' => 25,
                '🔔🔔🔔' => 15, '🍇🍇🍇' => 10, '🍊🍊🍊' => 8,
                '🍋🍋🍋' => 5, '🍒🍒🍒' => 3,
            ];

            $reelStr = implode('', $reels);
            $multi = $multipliers[$reelStr] ?? 0;
            // Two of a kind = 1.5x
            if ($multi === 0 && ($reels[0] === $reels[1] || $reels[1] === $reels[2] || $reels[0] === $reels[2])) {
                $multi = 1.5;
            }

            $display = "╔════════════╗\n║ " . implode(' │ ', $reels) . " ║\n╚════════════╝";

            if ($multi > 0) {
                $winnings = (int)($bet * $multi);
                $pdo->prepare("UPDATE discord_users SET kgd_balance = kgd_balance + ?, games_won = games_won + 1, total_earned = total_earned + ? WHERE discord_id = ?")->execute([$winnings, $winnings, $userId]);
                $pdo->prepare("INSERT INTO discord_economy (discord_id, entry_type, amount, reason) VALUES (?, 'wager_win', ?, ?)")->execute([$userId, $winnings, "Slots {$multi}x win"]);
                $resultText = "🎉 **{$multi}x — YOU WIN $winnings KGD!**";
                $color = $multi >= 25 ? 0xF1C40F : 0x57F287;
                if ($multi >= 50) $resultText = "🎰🎰🎰 **JACKPOT!! {$multi}x — $winnings KGD!!** 🎰🎰🎰";
            } else {
                $pdo->prepare("UPDATE discord_users SET kgd_balance = kgd_balance - ?, games_lost = games_lost + 1, total_spent = total_spent + ? WHERE discord_id = ? AND kgd_balance >= ?")->execute([$bet, $bet, $userId, $bet]);
                $pdo->prepare("INSERT INTO discord_economy (discord_id, entry_type, amount, reason) VALUES (?, 'wager_loss', ?, 'Slots loss')")->execute([$userId, $bet]);
                $resultText = "💀 **No match. Lost $bet KGD.**";
                $color = 0x95A5A6;
            }

            respond(null, [embed("🎰 Slots", "```\n$display\n```\n\n$resultText", $color, [
                field('Bet', "$bet KGD", true),
                field('Multiplier', $multi > 0 ? "{$multi}x" : '0x', true),
            ])], [
                actionRow(
                    btn(1, '🎰 Spin Again', "gamble_slots_{$bet}"),
                    btn(2, '📊 Payouts', 'slots_payouts')
                )
            ]);
            break;
    }
}
