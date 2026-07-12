<?php
/**
 * GoSiteMe Discord Bot — Extra Games Module
 * ═════════════════════════════════════════
 * /games2 (hangman|wordle|slots|coinflip|duel|tower)
 * Additional casino and word games with KGD economy.
 */

function handleGames2($data): void {
    $sub = $data['data']['options'][0]['name'] ?? 'coinflip';
    $opts = [];
    foreach (($data['data']['options'][0]['options'] ?? []) as $o) {
        $opts[$o['name']] = $o['value'];
    }

    $userId   = $data['member']['user']['id'] ?? ($data['user']['id'] ?? '');
    $username = $data['member']['user']['username'] ?? ($data['user']['username'] ?? 'User');
    $db       = getDiscordDB();
    if (!$db) { respond('❌ Database unavailable.'); return; }
    $user = getOrCreateUser($userId, $username);

    $db->exec("CREATE TABLE IF NOT EXISTS discord_games2 (
        id INT AUTO_INCREMENT PRIMARY KEY,
        discord_id VARCHAR(32) NOT NULL,
        game_type VARCHAR(50) NOT NULL,
        game_data JSON NOT NULL,
        status VARCHAR(20) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_type (discord_id, game_type),
        INDEX idx_status (status)
    )");

    switch ($sub) {
        case 'hangman':
            $action = $opts['action'] ?? 'start';

            if ($action === 'start') {
                $words = ['ALGORITHM','BLOCKCHAIN','CYBERNETIC','DEVELOPER','ENCRYPTION','FRAMEWORK','HOLOGRAM','INTERFACE','JAVASCRIPT','KUBERNETES','LAMBDA','METAMASK','NEURONAL','OVERFLOW','PROTOCOL','QUANTUM','RECURSION','SYNTHESIS','TERRAFORM','UNIVERSAL','VIRTUALIZE','WEBSOCKET','XENOMORPH','YIELD','ZERODAY',
                    'ARTIFICIAL','BANDWIDTH','COMPILER','DATABASE','ETHEREUM','FIREWALL','GATEWAY','HARDWARE','ITERATION','JUNCTION'];
                $word = $words[array_rand($words)];
                $masked = str_repeat('_ ', strlen($word));
                $maxGuesses = 8;

                $gameData = json_encode([
                    'word' => $word,
                    'guessed' => [],
                    'remaining' => $maxGuesses,
                    'revealed' => array_fill(0, strlen($word), false),
                ]);

                // End any active hangman games
                $db->prepare("UPDATE discord_games2 SET status = 'abandoned' WHERE discord_id = ? AND game_type = 'hangman' AND status = 'active'")->execute([$userId]);

                $stmt = $db->prepare("INSERT INTO discord_games2 (discord_id, game_type, game_data) VALUES (?, 'hangman', ?)");
                $stmt->execute([$userId, $gameData]);
                $gameId = $db->lastInsertId();

                $hangmanArt = getHangmanArt(0);

                respond(null, [embed("🎯 Hangman", "```\n$hangmanArt\n```\n**Word:** `$masked`\n**Remaining:** $maxGuesses guesses\n**Letters Used:** None", 0x3498DB, [
                    field('Category', 'Tech & Programming', true),
                    field('Letters', (string)strlen($word), true),
                ], [
                    'footer' => ['text' => "Game #$gameId • Click a button to guess"],
                ])], [
                    actionRow(btn(1,'A',"hm_{$gameId}_A"),btn(1,'E',"hm_{$gameId}_E"),btn(1,'I',"hm_{$gameId}_I"),btn(1,'O',"hm_{$gameId}_O"),btn(1,'U',"hm_{$gameId}_U")),
                    actionRow(btn(2,'S',"hm_{$gameId}_S"),btn(2,'T',"hm_{$gameId}_T"),btn(2,'R',"hm_{$gameId}_R"),btn(2,'N',"hm_{$gameId}_N"),btn(2,'L',"hm_{$gameId}_L")),
                    actionRow(btn(2,'C',"hm_{$gameId}_C"),btn(2,'D',"hm_{$gameId}_D"),btn(2,'M',"hm_{$gameId}_M"),btn(2,'P',"hm_{$gameId}_P"),btn(2,'H',"hm_{$gameId}_H")),
                ]);
                awardXP($userId, 2);
            } else {
                respondEphemeral("Use `/games2 hangman` to start a new game!");
            }
            break;

        case 'wordle':
            $action = $opts['action'] ?? 'start';

            if ($action === 'start') {
                $words = ['CRANE','SLATE','TRACE','AUDIO','ADIEU','GHOST','FLAME','CRISP','PLUMB','DROWN','CHUNK','BLITZ','PROXY','QUERY','PIXEL','GLYPH','NEXUS','VAULT','STORM','FORGE','SWIFT','BRAIN','LIGHT','PRIME','DRIVE'];
                $word = $words[array_rand($words)];

                $db->prepare("UPDATE discord_games2 SET status = 'abandoned' WHERE discord_id = ? AND game_type = 'wordle' AND status = 'active'")->execute([$userId]);

                $gameData = json_encode([
                    'word' => $word,
                    'guesses' => [],
                    'max_guesses' => 6,
                ]);

                $stmt = $db->prepare("INSERT INTO discord_games2 (discord_id, game_type, game_data) VALUES (?, 'wordle', ?)");
                $stmt->execute([$userId, $gameData]);
                $gameId = $db->lastInsertId();

                $grid = str_repeat("⬛⬛⬛⬛⬛\n", 6);

                respond(null, [embed("📝 Wordle", "Guess the 5-letter word in 6 tries!\n\n$grid\n🟩 = Correct position\n🟨 = Wrong position\n⬛ = Not in word\n\nType your guess with `/games2 wordle guess:<word>`", 0x538D4E, [
                    field('Attempts', '0/6', true),
                    field('Game', "#$gameId", true),
                ], [
                    'footer' => ['text' => 'Wordle • 5-letter words only'],
                ])]);
                awardXP($userId, 2);
            } elseif ($action === 'guess') {
                $guess = strtoupper($opts['guess'] ?? '');
                if (strlen($guess) !== 5 || !ctype_alpha($guess)) {
                    respondEphemeral('❌ Guess must be exactly 5 letters.');
                    return;
                }

                $stmt = $db->prepare("SELECT id, game_data FROM discord_games2 WHERE discord_id = ? AND game_type = 'wordle' AND status = 'active' ORDER BY created_at DESC LIMIT 1");
                $stmt->execute([$userId]);
                $game = $stmt->fetch();

                if (!$game) {
                    respondEphemeral("No active Wordle game. Start one with `/games2 wordle`.");
                    return;
                }

                $gd = json_decode($game['game_data'], true);
                $word = $gd['word'];
                $guesses = $gd['guesses'];

                // Generate result
                $result = '';
                $letterResults = [];
                for ($i = 0; $i < 5; $i++) {
                    if ($guess[$i] === $word[$i]) {
                        $result .= '🟩';
                        $letterResults[] = 'correct';
                    } elseif (str_contains($word, $guess[$i])) {
                        $result .= '🟨';
                        $letterResults[] = 'present';
                    } else {
                        $result .= '⬛';
                        $letterResults[] = 'absent';
                    }
                }

                $guesses[] = ['word' => $guess, 'result' => $result];
                $won = ($guess === $word);
                $lost = !$won && count($guesses) >= $gd['max_guesses'];

                // Build grid
                $grid = '';
                foreach ($guesses as $g) {
                    $grid .= $g['result'] . " `{$g['word']}`\n";
                }
                for ($i = count($guesses); $i < 6; $i++) {
                    $grid .= "⬛⬛⬛⬛⬛\n";
                }

                if ($won || $lost) {
                    $db->prepare("UPDATE discord_games2 SET status = ?, game_data = ? WHERE id = ?")->execute([$won ? 'won' : 'lost', json_encode(array_merge($gd, ['guesses' => $guesses])), $game['id']]);

                    if ($won) {
                        $xpReward = max(5, 30 - (count($guesses) * 5));
                        awardXP($userId, $xpReward);
                        respond(null, [embed("📝 Wordle — WIN! 🎉", "$grid\n**The word was `$word`!**\nSolved in **" . count($guesses) . "/6** attempts!", 0x538D4E, [
                            field('XP Earned', "+$xpReward", true),
                        ])], [actionRow(btn(1, '🔄 Play Again', 'games2_wordle_start'))]);
                    } else {
                        respond(null, [embed("📝 Wordle — Game Over", "$grid\n**The word was `$word`**\nBetter luck next time!", 0xE74C3C)], [actionRow(btn(1, '🔄 Play Again', 'games2_wordle_start'))]);
                    }
                } else {
                    $gd['guesses'] = $guesses;
                    $db->prepare("UPDATE discord_games2 SET game_data = ? WHERE id = ?")->execute([json_encode($gd), $game['id']]);

                    respond(null, [embed("📝 Wordle", "$grid\n" . count($guesses) . "/6 attempts used", 0x538D4E, [], [
                        'footer' => ['text' => 'Type /games2 wordle guess:<word> to continue'],
                    ])]);
                }
            }
            break;

        case 'slots':
            $bet = (int)($opts['bet'] ?? 10);
            $bet = max(1, min(1000, $bet));

            if ((int)$user['coins'] < $bet) {
                respondEphemeral("❌ Not enough coins! You have {$user['coins']} KGD.");
                return;
            }

            $symbols = ['🍒','🍋','🍊','🍇','💎','7️⃣','⭐','🔔'];
            $weights = [25, 20, 18, 15, 8, 5, 5, 4]; // Lower = rarer

            $spin = function() use ($symbols, $weights) {
                $total = array_sum($weights);
                $rand = mt_rand(1, $total);
                $cum = 0;
                foreach ($weights as $i => $w) {
                    $cum += $w;
                    if ($rand <= $cum) return $symbols[$i];
                }
                return $symbols[0];
            };

            $r1 = [$spin(), $spin(), $spin()];
            $r2 = [$spin(), $spin(), $spin()];
            $r3 = [$spin(), $spin(), $spin()];
            $middle = $r2; // Middle row is the payline

            // Calculate win
            $multiplier = 0;
            if ($middle[0] === $middle[1] && $middle[1] === $middle[2]) {
                // Three of a kind
                $multiplier = match($middle[0]) {
                    '7️⃣' => 50, '💎' => 25, '⭐' => 15, '🔔' => 10,
                    '🍇' => 8, '🍊' => 5, '🍋' => 3, '🍒' => 2,
                    default => 2,
                };
            } elseif ($middle[0] === $middle[1] || $middle[1] === $middle[2]) {
                $multiplier = 1; // Pair = break even
            }

            $winnings = $bet * $multiplier;
            $net = $winnings - $bet;

            // Update balance
            $db->prepare("UPDATE discord_users SET coins = coins + ? WHERE discord_id = ?")->execute([$net, $userId]);
            $newBalance = (int)$user['coins'] + $net;

            $slotDisplay = "╔═══════════╗\n║ {$r1[0]} {$r1[1]} {$r1[2]} ║\n║ {$r2[0]} {$r2[1]} {$r2[2]} ║ ← \n║ {$r3[0]} {$r3[1]} {$r3[2]} ║\n╚═══════════╝";

            $resultText = $multiplier > 1 ? "🎉 **JACKPOT!** {$multiplier}x — +$winnings KGD" : ($multiplier === 1 ? "😐 Pair — Break even" : "💀 No match — -$bet KGD");
            $color = $multiplier > 1 ? 0x2ECC71 : ($multiplier === 1 ? 0xF39C12 : 0xE74C3C);

            respond(null, [embed("🎰 Slot Machine", "```\n$slotDisplay\n```\n$resultText", $color, [
                field('Bet', "$bet KGD", true),
                field('Won', $winnings > 0 ? "$winnings KGD" : "0 KGD", true),
                field('Balance', "$newBalance KGD", true),
            ], [
                'footer' => ['text' => "Multipliers: 🍒2x 🍋3x 🍊5x 🍇8x 🔔10x ⭐15x 💎25x 7️⃣50x"],
            ])], [actionRow(
                btn(1, '🎰 Spin Again', "slots_$bet"),
                btn(2, '🎰 Bet 50', 'slots_50'),
                btn(2, '🎰 Bet 100', 'slots_100'),
                btn(3, '🎰 Bet 500', 'slots_500')
            )]);
            if ($multiplier > 1) awardXP($userId, min(20, $multiplier));
            break;

        case 'coinflip':
            $bet = (int)($opts['bet'] ?? 10);
            $choice = strtolower($opts['side'] ?? 'heads');
            $bet = max(1, min(1000, $bet));

            if ((int)$user['coins'] < $bet) {
                respondEphemeral("❌ Not enough coins! You have {$user['coins']} KGD.");
                return;
            }

            $result = mt_rand(0, 1) === 0 ? 'heads' : 'tails';
            $won = ($choice === $result);
            $net = $won ? $bet : -$bet;

            $db->prepare("UPDATE discord_users SET coins = coins + ? WHERE discord_id = ?")->execute([$net, $userId]);
            $newBalance = (int)$user['coins'] + $net;

            $coinEmoji = $result === 'heads' ? '🪙' : '💿';
            $resultEmoji = $won ? '🎉' : '💀';

            respond(null, [embed("$coinEmoji Coin Flip", "$resultEmoji The coin lands on **" . ucfirst($result) . "**!\n\nYou chose **" . ucfirst($choice) . "** — " . ($won ? "**YOU WIN +$bet KGD!**" : "**You lose -$bet KGD**"), $won ? 0x2ECC71 : 0xE74C3C, [
                field('Bet', "$bet KGD", true),
                field('Result', ucfirst($result), true),
                field('Balance', "$newBalance KGD", true),
            ])], [actionRow(
                btn(1, '🪙 Heads', "coinflip_heads_$bet"),
                btn(1, '💿 Tails', "coinflip_tails_$bet"),
                btn(2, '🎰 Slots', 'slots_' . $bet),
                btn(2, '💰 Balance', 'economy_balance')
            )]);
            if ($won) awardXP($userId, 3);
            break;

        case 'duel':
            $opponent = $opts['opponent'] ?? '';
            $bet = (int)($opts['bet'] ?? 50);
            $bet = max(10, min(500, $bet));

            if (!$opponent) {
                respondEphemeral('❌ Mention an opponent to duel!');
                return;
            }

            if ((int)$user['coins'] < $bet) {
                respondEphemeral("❌ Not enough coins! You have {$user['coins']} KGD.");
                return;
            }

            // Create duel challenge
            $duelId = mt_rand(10000, 99999);
            $stmt = $db->prepare("INSERT INTO discord_games2 (discord_id, game_type, game_data, status) VALUES (?, 'duel', ?, 'pending')");
            $stmt->execute([$userId, json_encode([
                'challenger' => $userId,
                'opponent' => $opponent,
                'bet' => $bet,
                'duel_id' => $duelId,
            ])]);

            respond("<@$opponent>", [embed("⚔️ Duel Challenge!", "<@$userId> challenges <@$opponent> to a duel!\n\n**Wager:** $bet KGD\n\nThe challenged player has 60 seconds to accept!", 0xE74C3C, [], [
                'footer' => ['text' => "Duel #$duelId"],
            ])], [actionRow(
                btn(1, '⚔️ Accept Duel', "duel_accept_$duelId"),
                btn(4, '🏳️ Decline', "duel_decline_$duelId")
            )]);
            break;

        case 'tower':
            $action = $opts['action'] ?? 'start';
            $bet = (int)($opts['bet'] ?? 25);
            $bet = max(5, min(500, $bet));

            if ($action === 'start') {
                if ((int)$user['coins'] < $bet) {
                    respondEphemeral("❌ Not enough coins! You have {$user['coins']} KGD.");
                    return;
                }

                $db->prepare("UPDATE discord_users SET coins = coins - ? WHERE discord_id = ?")->execute([$bet, $userId]);

                $db->prepare("UPDATE discord_games2 SET status = 'abandoned' WHERE discord_id = ? AND game_type = 'tower' AND status = 'active'")->execute([$userId]);

                $gameData = json_encode([
                    'bet' => $bet,
                    'floor' => 0,
                    'multiplier' => 1.0,
                    'max_floor' => 10,
                    'safe_spots' => array_map(fn() => mt_rand(0, 2), range(1, 10)),
                ]);

                $stmt = $db->prepare("INSERT INTO discord_games2 (discord_id, game_type, game_data) VALUES (?, 'tower', ?)");
                $stmt->execute([$userId, $gameData]);
                $gameId = $db->lastInsertId();

                $towerDisplay = "🏗️ Tower Challenge\n\n";
                for ($i = 10; $i >= 1; $i--) {
                    $towerDisplay .= "Floor $i: 🟦🟦🟦 — " . number_format(1.0 + ($i * 0.5), 1) . "x\n";
                }
                $towerDisplay .= "\n**You are on the Ground Floor**";

                respond(null, [embed("🗼 Risk Tower", $towerDisplay, 0xF39C12, [
                    field('Bet', "$bet KGD", true),
                    field('Multiplier', '1.0x', true),
                    field('Potential', "$bet KGD", true),
                ], [
                    'footer' => ['text' => "Game #$gameId • Choose a door to climb"],
                ])], [actionRow(
                    btn(1, '🚪 Door 1', "tower_{$gameId}_0"),
                    btn(1, '🚪 Door 2', "tower_{$gameId}_1"),
                    btn(1, '🚪 Door 3', "tower_{$gameId}_2"),
                    btn(3, '💰 Cash Out', "tower_{$gameId}_cashout")
                )]);
                awardXP($userId, 2);
            }
            break;

        default:
            respondEphemeral("Unknown subcommand. Try `/games2 hangman`, `/games2 wordle`, `/games2 slots`, `/games2 coinflip`, `/games2 duel`, or `/games2 tower`.");
    }
}

function getHangmanArt(int $mistakes): string {
    $stages = [
        "  +---+\n  |   |\n      |\n      |\n      |\n      |\n=========",
        "  +---+\n  |   |\n  O   |\n      |\n      |\n      |\n=========",
        "  +---+\n  |   |\n  O   |\n  |   |\n      |\n      |\n=========",
        "  +---+\n  |   |\n  O   |\n /|   |\n      |\n      |\n=========",
        "  +---+\n  |   |\n  O   |\n /|\\  |\n      |\n      |\n=========",
        "  +---+\n  |   |\n  O   |\n /|\\  |\n /    |\n      |\n=========",
        "  +---+\n  |   |\n  O   |\n /|\\  |\n / \\  |\n      |\n=========",
        "  +---+\n  |   |\n [O]  |\n /|\\  |\n / \\  |\n      |\n=========",
        "  +---+\n  |   |\n [X]  |\n /|\\  |\n / \\  |\n      |\n=========",
    ];
    return $stages[min($mistakes, count($stages) - 1)];
}
