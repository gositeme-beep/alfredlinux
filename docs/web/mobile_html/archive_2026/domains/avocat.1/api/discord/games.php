<?php
/**
 * GoSiteMe Discord Bot — Games Module
 * ════════════════════════════════════
 * /chess    — Chess vs AI or humans (ELO rated)
 * /checkers — Checkers vs AI or humans
 * /trivia   — AI-generated trivia with KGD rewards
 * /8ball    — Magic 8-ball
 * /rps      — Rock Paper Scissors vs AI or humans
 */

function handleChess(array $data): void {
    $subCmd = getSubcommand($data);
    $opts = getSubOptions($data);
    $userId = $data['member']['user']['id'] ?? '0';
    $username = $data['member']['user']['username'] ?? 'User';
    $globalName = $data['member']['user']['global_name'] ?? $username;
    $resolved = $data['data']['resolved'] ?? [];
    $channelId = $data['channel_id'] ?? '';
    $guildId = $data['guild_id'] ?? '';
    $user = getOrCreateUser($userId, $username);
    $pdo = getDiscordDB();

    switch ($subCmd ?? 'play') {
        case 'play':
            if (!$pdo) { respond("⚠️ Database unavailable."); return; }
            $stmt = $pdo->prepare("SELECT id FROM discord_games WHERE (player_white = ? OR player_black = ?) AND status = 'active' AND game_type = 'chess' LIMIT 1");
            $stmt->execute([$userId, $userId]);
            if ($stmt->fetch()) { respond("♟️ You already have an active game! Use `/chess board` to see it, or `/chess resign`."); return; }

            $fen = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';
            $state = json_encode(['fen' => $fen, 'moves' => [], 'vs' => 'AI', 'captures' => ['w' => [], 'b' => []]]);
            $pdo->prepare("INSERT INTO discord_games (game_type, player_white, player_black, state, fen, status, channel_id, guild_id) VALUES ('chess', ?, 'AI', ?, ?, 'active', ?, ?)")
                ->execute([$userId, $state, $fen, $channelId, $guildId]);
            $pdo->prepare("UPDATE discord_users SET games_played = games_played + 1 WHERE discord_id = ?")->execute([$userId]);
            awardXP($userId, 10);

            $board = renderChessBoard($fen);
            respond(null, [
                embed('♟️ New Chess Game', "$board\n\n**$globalName** (White ♔) vs **Alfred AI** (Black ♚)\n\n🎯 Your move! Use `/chess move move:e4`\n\n*Earn 5 KGD every 5 moves. Win for +25 ELO and 100 KGD!*", 0x5865F2, [
                    field('⚡ ELO', (string)$user['elo_chess'], true),
                    field('💎 KGD', number_format($user['kgd_balance']), true),
                    field('🏆 Record', "{$user['games_won']}W / {$user['games_lost']}L", true),
                ], [
                    'footer' => ['text' => 'GoSiteMe Chess | /chess help for commands'],
                    'thumbnail' => ['url' => 'https://gositeme.com/assets/images/logo-icon.png'],
                ])
            ], [
                actionRow(
                    btn(1, '📋 Openings', 'chess_openings'),
                    btn(2, '📊 My Stats', 'chess_stats'),
                    btn(4, '🏳️ Resign', 'chess_resign')
                )
            ]);
            break;

        case 'challenge':
            $opponentId = $opts['opponent'] ?? null;
            $wager = max(0, min(10000, (int)($opts['wager'] ?? 50)));
            if (!$opponentId) { respond("Mention a user! `/chess challenge opponent:@user`"); return; }
            if ($opponentId === $userId) { respond("Can't challenge yourself! Try `/chess play`"); return; }
            if ($wager > ($user['kgd_balance'] ?? 0)) { respond("Not enough KGD! You have {$user['kgd_balance']} KGD."); return; }

            $opName = $resolved['users'][$opponentId]['global_name'] ?? $resolved['users'][$opponentId]['username'] ?? 'Opponent';
            respond(null, [
                embed("⚔️ Chess Challenge!", "**$globalName** challenges **$opName** to a chess match!", 0xFEE75C, [
                    field('🏆 Wager', "$wager KGD", true),
                    field('⏱️ Expires', '5 minutes', true),
                    field('⚡ Challenger ELO', (string)$user['elo_chess'], true),
                ])
            ], [
                actionRow(
                    btn(3, '✅ Accept', "chess_accept_{$userId}_{$opponentId}_{$wager}"),
                    btn(4, '❌ Decline', "chess_decline_{$userId}_{$opponentId}")
                )
            ]);
            break;

        case 'move':
            $move = $opts['move'] ?? '';
            if (!$move) { respond("Specify a move! `/chess move move:e4`\nUse algebraic notation: e4, Nf3, Bb5, O-O, Qxd4"); return; }
            handleChessMove($userId, $globalName, $move, $data);
            break;

        case 'board':
            if (!$pdo) { respond("⚠️ Database unavailable."); return; }
            $stmt = $pdo->prepare("SELECT * FROM discord_games WHERE (player_white = ? OR player_black = ?) AND status = 'active' AND game_type = 'chess' ORDER BY updated_at DESC LIMIT 1");
            $stmt->execute([$userId, $userId]);
            $game = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$game) { respond("No active game. Start one with `/chess play`"); return; }

            $stateData = json_decode($game['state'], true);
            $board = renderChessBoard($game['fen']);
            $moveList = implode(', ', array_slice($stateData['moves'] ?? [], -10));
            $opp = $game['player_black'] === 'AI' ? 'Alfred AI' : ('<@' . ($game['player_white'] === $userId ? $game['player_black'] : $game['player_white']) . '>');
            $turn = (strpos($game['fen'], ' w ') !== false) ? 'White' : 'Black';

            respond(null, [
                embed("♟️ Chess — Move {$game['move_count']}", "$board\n\n**You** vs **$opp**\n🔄 {$turn}'s turn", 0x5865F2, [
                    field('📜 Last Moves', $moveList ?: 'None yet', false),
                ], [
                    'footer' => ['text' => "Game #{$game['id']} | /chess move move:your_move"],
                ])
            ]);
            break;

        case 'resign':
            if (!$pdo) { respond("⚠️ Database unavailable."); return; }
            $stmt = $pdo->prepare("SELECT * FROM discord_games WHERE (player_white = ? OR player_black = ?) AND status = 'active' AND game_type = 'chess' LIMIT 1");
            $stmt->execute([$userId, $userId]);
            $game = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$game) { respond("No active game to resign from."); return; }

            $winner = $game['player_white'] === $userId ? $game['player_black'] : $game['player_white'];
            $pdo->prepare("UPDATE discord_games SET status = 'resigned', winner = ? WHERE id = ?")->execute([$winner, $game['id']]);
            $pdo->prepare("UPDATE discord_users SET elo_chess = GREATEST(100, elo_chess - 15), games_lost = games_lost + 1 WHERE discord_id = ?")->execute([$userId]);

            respond(null, [embed("🏳️ Resignation", "**$globalName** resigned from chess.\n*-15 ELO*", 0x95A5A6)]);
            break;

        case 'stats':
            $target = $opts['user'] ?? $userId;
            $tUser = getOrCreateUser($target, 'User');
            $tName = $tUser['discord_name'];
            $wr = $tUser['games_played'] > 0 ? round($tUser['games_won'] / $tUser['games_played'] * 100) . '%' : 'N/A';
            respond(null, [
                embed("♟️ Chess Stats — $tName", '', 0x5865F2, [
                    field('⚡ ELO', (string)$tUser['elo_chess'], true),
                    field('🏆 Title', eloTitle($tUser['elo_chess']), true),
                    field('🎮 Games', (string)$tUser['games_played'], true),
                    field('✅ Wins', (string)$tUser['games_won'], true),
                    field('❌ Losses', (string)$tUser['games_lost'], true),
                    field('📊 Win Rate', $wr, true),
                ])
            ]);
            break;

        default:
            respond("♟️ **Chess Commands:**\n`/chess play` — New game vs AI\n`/chess challenge` — Challenge a player\n`/chess move` — Make a move\n`/chess board` — View your board\n`/chess resign` — Give up\n`/chess stats` — View stats");
    }
}


function handleChessMove(string $userId, string $globalName, string $move, array $data): void {
    $pdo = getDiscordDB();
    $appId = $data['application_id'] ?? '';
    $token = $data['token'] ?? '';
    $channelId = $data['channel_id'] ?? '';
    if (!$pdo) { respond("⚠️ Database unavailable."); return; }

    $stmt = $pdo->prepare("SELECT * FROM discord_games WHERE (player_white = ? OR player_black = ?) AND status = 'active' AND game_type = 'chess' ORDER BY updated_at DESC LIMIT 1");
    $stmt->execute([$userId, $userId]);
    $game = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$game) { respond("No active game. Start one with `/chess play`"); return; }

    $stateData = json_decode($game['state'], true);
    $isWhiteTurn = (strpos($game['fen'], ' w ') !== false);
    $isPlayerWhite = ($game['player_white'] === $userId);

    if ($isWhiteTurn !== $isPlayerWhite) {
        respond("⏳ It's not your turn! Wait for your opponent.");
        return;
    }

    $stateData['moves'][] = $move;
    $moveCount = $game['move_count'] + 1;

    // AI response for AI games
    $aiMove = '';
    if ($game['player_black'] === 'AI') {
        $aiMove = getAIChessMove($game['fen'], $move, $stateData['moves']);
        if ($aiMove) {
            $stateData['moves'][] = $aiMove;
            $moveCount++;
        }
    }

    $newState = json_encode($stateData);
    $pdo->prepare("UPDATE discord_games SET state = ?, move_count = ?, updated_at = NOW() WHERE id = ?")
        ->execute([$newState, $moveCount, $game['id']]);

    $moveHistory = implode(', ', array_slice($stateData['moves'], -6));
    awardXP($userId, 3, $appId, $token, $channelId);

    $fields = [field('📜 Recent', $moveHistory, false)];
    $bonus = '';

    if ($moveCount % 5 === 0) {
        $pdo->prepare("UPDATE discord_users SET kgd_balance = kgd_balance + 5, total_earned = total_earned + 5 WHERE discord_id = ?")->execute([$userId]);
        $pdo->prepare("INSERT INTO discord_economy (discord_id, entry_type, amount, reason) VALUES (?, 'earn', 5, 'Chess: 5-move milestone')") ->execute([$userId]);
        $bonus = "\n\n💰 **+5 KGD** (move milestone!)";
    }

    $desc = "♟️ **$globalName** plays: `$move`";
    if ($aiMove) $desc .= "\n🤖 **Alfred AI** responds: `$aiMove`";
    $desc .= $bonus;
    $desc .= "\n\n➡️ `/chess move move:your_move`";

    respond(null, [embed("♟️ Move #{$moveCount}", $desc, 0x5865F2, $fields)], [
        actionRow(
            btn(2, '📋 Board', 'chess_view_board'),
            btn(4, '🏳️ Resign', 'chess_resign')
        )
    ]);
}


function getAIChessMove(string $fen, string $playerMove, array $history): string {
    $histStr = implode(', ', array_slice($history, -20));
    $prompt = "You are a chess engine. FEN position after player's move: consider the game with moves: $histStr. "
        . "The player just played $playerMove. Reply with ONLY your next move in algebraic notation (e.g., e5, Nf6, Bb5, O-O). "
        . "Play at intermediate level — challenging but beatable. Just the move, nothing else.";

    $move = callGroq('You are a chess engine. Reply with only the move in algebraic notation.', $prompt, 0.3, 10);
    if ($move) {
        $move = trim($move);
        $move = preg_replace('/[^a-hA-H1-8KQRBNOxo+#=\-]/', '', $move);
    }
    return $move ?: 'e5';
}


function handleCheckers(array $data): void {
    $subCmd = getSubcommand($data);
    $opts = getSubOptions($data);
    $userId = $data['member']['user']['id'] ?? '0';
    $username = $data['member']['user']['username'] ?? 'User';
    $globalName = $data['member']['user']['global_name'] ?? $username;
    $resolved = $data['data']['resolved'] ?? [];
    $user = getOrCreateUser($userId, $username);
    $pdo = getDiscordDB();

    switch ($subCmd ?? 'play') {
        case 'play':
            if (!$pdo) { respond("⚠️ Database unavailable."); return; }
            $stmt = $pdo->prepare("SELECT id FROM discord_games WHERE (player_white = ? OR player_black = ?) AND status = 'active' AND game_type = 'checkers' LIMIT 1");
            $stmt->execute([$userId, $userId]);
            if ($stmt->fetch()) { respond("🔴 You already have an active checkers game!"); return; }

            $state = json_encode(['board' => 'standard_8x8', 'moves' => [], 'vs' => 'AI']);
            $pdo->prepare("INSERT INTO discord_games (game_type, player_white, player_black, state, status) VALUES ('checkers', ?, 'AI', ?, 'active')")
                ->execute([$userId, $state]);
            $pdo->prepare("UPDATE discord_users SET games_played = games_played + 1 WHERE discord_id = ?")->execute([$userId]);
            awardXP($userId, 10);

            $board = renderCheckersBoard();
            respond(null, [
                embed('🔴 New Checkers Game', "$board\n\n**$globalName** (Red) vs **Alfred AI** (Black)\n\n🎯 Use `/checkers move move:11-15`\n*Squares numbered 1-32*", 0xED4245, [
                    field('⚡ ELO', (string)$user['elo_checkers'], true),
                    field('💎 KGD', number_format($user['kgd_balance']), true),
                ])
            ], [
                actionRow(btn(4, '🏳️ Resign', 'checkers_resign'))
            ]);
            break;

        case 'move':
            $move = $opts['move'] ?? '';
            if (!$move) { respond("Specify a move! `/checkers move move:11-15`"); return; }
            if (!$pdo) { respond("⚠️ Database unavailable."); return; }

            $stmt = $pdo->prepare("SELECT * FROM discord_games WHERE (player_white = ? OR player_black = ?) AND status = 'active' AND game_type = 'checkers' LIMIT 1");
            $stmt->execute([$userId, $userId]);
            $game = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$game) { respond("No active game. Start with `/checkers play`"); return; }

            $stateData = json_decode($game['state'], true);
            $stateData['moves'][] = $move;
            $moveCount = $game['move_count'] + 1;

            $aiMove = '';
            if ($game['player_black'] === 'AI') {
                $aiMove = getAICheckersMove($stateData['moves']);
                if ($aiMove) { $stateData['moves'][] = $aiMove; $moveCount++; }
            }

            $pdo->prepare("UPDATE discord_games SET state = ?, move_count = ?, updated_at = NOW() WHERE id = ?")
                ->execute([json_encode($stateData), $moveCount, $game['id']]);
            awardXP($userId, 3);

            $desc = "🔴 **$globalName** plays: `$move`";
            if ($aiMove) $desc .= "\n⚫ **Alfred AI** responds: `$aiMove`";

            if ($moveCount % 5 === 0) {
                $pdo->prepare("UPDATE discord_users SET kgd_balance = kgd_balance + 5, total_earned = total_earned + 5 WHERE discord_id = ?")->execute([$userId]);
                $pdo->prepare("INSERT INTO discord_economy (discord_id, entry_type, amount, reason) VALUES (?, 'earn', 5, 'Checkers milestone')")->execute([$userId]);
                $desc .= "\n\n💰 **+5 KGD** milestone!";
            }
            $desc .= "\n\n➡️ `/checkers move move:your_move`";
            respond(null, [embed("🔴 Move #{$moveCount}", $desc, 0xED4245)]);
            break;

        case 'challenge':
            $opponentId = $opts['opponent'] ?? null;
            if (!$opponentId || $opponentId === $userId) { respond("Mention someone! `/checkers challenge opponent:@user`"); return; }
            $opName = $resolved['users'][$opponentId]['username'] ?? 'Opponent';
            respond(null, [
                embed("⚔️ Checkers Challenge!", "**$globalName** challenges **$opName**!\n🏆 **30 KGD** at stake!", 0xFEE75C)
            ], [
                actionRow(
                    btn(3, '✅ Accept', "checkers_accept_{$userId}_{$opponentId}_30"),
                    btn(4, '❌ Decline', "checkers_decline_{$userId}_{$opponentId}")
                )
            ]);
            break;

        case 'board':
            if (!$pdo) { respond("⚠️ Database unavailable."); return; }
            $stmt = $pdo->prepare("SELECT * FROM discord_games WHERE (player_white = ? OR player_black = ?) AND status = 'active' AND game_type = 'checkers' LIMIT 1");
            $stmt->execute([$userId, $userId]);
            $game = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$game) { respond("No active checkers game."); return; }
            respond(null, [embed("🔴 Checkers — Move {$game['move_count']}", renderCheckersBoard(), 0xED4245)]);
            break;

        case 'resign':
            if (!$pdo) { respond("⚠️ Database unavailable."); return; }
            $stmt = $pdo->prepare("SELECT id FROM discord_games WHERE (player_white = ? OR player_black = ?) AND status = 'active' AND game_type = 'checkers' LIMIT 1");
            $stmt->execute([$userId, $userId]);
            $game = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$game) { respond("No active checkers game."); return; }
            $pdo->prepare("UPDATE discord_games SET status = 'resigned' WHERE id = ?")->execute([$game['id']]);
            $pdo->prepare("UPDATE discord_users SET elo_checkers = GREATEST(100, elo_checkers - 10), games_lost = games_lost + 1 WHERE discord_id = ?")->execute([$userId]);
            respond("🏳️ **$globalName** resigned from checkers. *-10 ELO*");
            break;

        default:
            respond("🔴 **Checkers Commands:**\n`/checkers play` — New game vs AI\n`/checkers challenge` — Challenge a player\n`/checkers move` — Make a move\n`/checkers board` — View board\n`/checkers resign` — Give up");
    }
}

function getAICheckersMove(array $history): string {
    $histStr = implode(', ', array_slice($history, -10));
    $move = callGroq(
        'You are a checkers engine. Reply with ONLY the move (e.g., 22-18, 23x14). Just the move.',
        "Game history: $histStr. Your turn as Black. What's your move?", 0.3, 10
    );
    if ($move) $move = preg_replace('/[^0-9x\-]/', '', trim($move));
    return $move ?: '22-18';
}


function handleTrivia(array $data): void {
    $opts = getSubOptions($data);
    $category = $opts['category'] ?? 'random';
    $diff = $opts['difficulty'] ?? 'medium';
    $userId = $data['member']['user']['id'] ?? '0';
    $username = $data['member']['user']['username'] ?? 'User';
    $globalName = $data['member']['user']['global_name'] ?? $username;
    $appId = $data['application_id'] ?? '';
    $token = $data['token'] ?? '';

    deferResponse();
    $user = getOrCreateUser($userId, $username);
    awardXP($userId, 5);

    $categories = ['history', 'science', 'technology', 'geography', 'movies', 'music', 'sports', 'gaming', 'anime', 'food', 'nature', 'math', 'literature', 'art', 'mythology', 'space', 'programming'];
    if ($category === 'random') $category = $categories[array_rand($categories)];

    $rewards = ['easy' => 10, 'medium' => 25, 'hard' => 50];
    $reward = $rewards[$diff] ?? 25;

    $sys = "Generate a {$diff} difficulty trivia question about {$category}. "
         . "Format EXACTLY as:\nQUESTION: [the question]\nA) [option]\nB) [option]\nC) [option]\nD) [option]\nANSWER: [letter]\nFACT: [fun fact about the answer]";

    $result = callGroq($sys, "Generate a unique, interesting $category trivia question.", 0.9, 500);
    if (!$result) { followUp($appId, $token, "⚠️ Trivia generation failed."); return; }

    // Parse the response
    preg_match('/QUESTION:\s*(.+)/i', $result, $qMatch);
    preg_match('/A\)\s*(.+)/i', $result, $aMatch);
    preg_match('/B\)\s*(.+)/i', $result, $bMatch);
    preg_match('/C\)\s*(.+)/i', $result, $cMatch);
    preg_match('/D\)\s*(.+)/i', $result, $dMatch);
    preg_match('/ANSWER:\s*([A-D])/i', $result, $ansMatch);
    preg_match('/FACT:\s*(.+)/i', $result, $factMatch);

    $question = trim($qMatch[1] ?? 'What is the capital of France?');
    $options = [
        'A' => trim($aMatch[1] ?? 'Paris'),
        'B' => trim($bMatch[1] ?? 'London'),
        'C' => trim($cMatch[1] ?? 'Berlin'),
        'D' => trim($dMatch[1] ?? 'Madrid'),
    ];
    $answer = strtoupper(trim($ansMatch[1] ?? 'A'));
    $fact = trim($factMatch[1] ?? '');

    $optText = '';
    foreach ($options as $l => $o) $optText .= "**$l)** $o\n";

    $diffEmoji = ['easy' => '🟢', 'medium' => '🟡', 'hard' => '🔴'][$diff] ?? '🟡';

    // Encode answer in button ID
    $triviaId = substr(md5(time() . $userId), 0, 12);

    // Store in DB for verification
    $pdo = getDiscordDB();
    if ($pdo) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS discord_trivia_active (
            id VARCHAR(12) PRIMARY KEY,
            answer CHAR(1) NOT NULL,
            reward INT NOT NULL,
            fact VARCHAR(500) DEFAULT '',
            user_id VARCHAR(25) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB");
        $pdo->prepare("INSERT INTO discord_trivia_active (id, answer, reward, fact, user_id) VALUES (?, ?, ?, ?, ?)")
            ->execute([$triviaId, $answer, $reward, $fact, $userId]);
    }

    followUp($appId, $token, '', [
        embed("$diffEmoji Trivia — " . ucfirst($category), "**$question**\n\n$optText\n💎 **Reward: $reward KGD**\n⏱️ 30 seconds to answer!", 0x9B59B6, [
            field('Category', ucfirst($category), true),
            field('Difficulty', ucfirst($diff), true),
        ], [
            'footer' => ['text' => "$globalName's trivia | GoSiteMe"],
        ])
    ], [
        actionRow(
            btn(1, 'A', "trivia_{$triviaId}_A"),
            btn(1, 'B', "trivia_{$triviaId}_B"),
            btn(1, 'C', "trivia_{$triviaId}_C"),
            btn(1, 'D', "trivia_{$triviaId}_D")
        )
    ]);
}


function handleEightBall(array $data): void {
    $opts = getSubOptions($data);
    $question = $opts['question'] ?? 'Will I be lucky today?';
    $globalName = $data['member']['user']['global_name'] ?? $data['member']['user']['username'] ?? 'User';
    $userId = $data['member']['user']['id'] ?? '0';
    awardXP($userId, 2);

    $responses = [
        ['🟢', 'It is certain.'], ['🟢', 'Without a doubt.'], ['🟢', 'Yes — definitely.'],
        ['🟢', 'You may rely on it.'], ['🟢', 'As I see it, yes.'], ['🟢', 'Most likely.'],
        ['🟢', 'Outlook good.'], ['🟢', 'Yes.'], ['🟢', 'Signs point to yes.'],
        ['🟡', 'Reply hazy, try again.'], ['🟡', 'Ask again later.'], ['🟡', 'Better not tell you now.'],
        ['🟡', 'Cannot predict now.'], ['🟡', 'Concentrate and ask again.'],
        ['🔴', "Don't count on it."], ['🔴', 'My reply is no.'], ['🔴', 'My sources say no.'],
        ['🔴', 'Outlook not so good.'], ['🔴', 'Very doubtful.'], ['🔴', 'Absolutely not.'],
    ];
    $r = $responses[array_rand($responses)];
    $color = ['🟢' => 0x57F287, '🟡' => 0xFEE75C, '🔴' => 0xED4245][$r[0]];

    respond(null, [
        embed('🎱 Magic 8-Ball', '', $color, [
            field('❓ Question', $question, false),
            field("{$r[0]} Answer", "**{$r[1]}**", false),
        ], [
            'footer' => ['text' => "Asked by $globalName"],
        ])
    ]);
}


function handleRPS(array $data): void {
    $opts = getSubOptions($data);
    $choice = strtolower($opts['choice'] ?? 'rock');
    $opponent = $opts['opponent'] ?? null;
    $userId = $data['member']['user']['id'] ?? '0';
    $username = $data['member']['user']['username'] ?? 'User';
    $globalName = $data['member']['user']['global_name'] ?? $username;
    $resolved = $data['data']['resolved'] ?? [];

    if ($opponent && $opponent !== $userId) {
        // Challenge mode
        $opName = $resolved['users'][$opponent]['username'] ?? 'Opponent';
        respond(null, [
            embed("✊ Rock Paper Scissors!", "**$globalName** challenges **$opName**!\n\nPick your move:", 0x1ABC9C)
        ], [
            actionRow(
                btn(1, '🪨 Rock', "rps_challenge_{$userId}_{$opponent}_rock"),
                btn(1, '📄 Paper', "rps_challenge_{$userId}_{$opponent}_paper"),
                btn(1, '✂️ Scissors', "rps_challenge_{$userId}_{$opponent}_scissors")
            )
        ]);
        return;
    }

    // VS AI
    $user = getOrCreateUser($userId, $username);
    awardXP($userId, 3);
    $choices = ['rock', 'paper', 'scissors'];
    $aiChoice = $choices[array_rand($choices)];
    $emojis = ['rock' => '🪨', 'paper' => '📄', 'scissors' => '✂️'];

    $result = 'tie';
    if ($choice === $aiChoice) $result = 'tie';
    elseif (
        ($choice === 'rock' && $aiChoice === 'scissors') ||
        ($choice === 'paper' && $aiChoice === 'rock') ||
        ($choice === 'scissors' && $aiChoice === 'paper')
    ) $result = 'win';
    else $result = 'lose';

    $pdo = getDiscordDB();
    $reward = 0;
    if ($result === 'win') {
        $reward = 15;
        $resultText = "🎉 **You win!** +15 KGD";
        $color = 0x57F287;
        if ($pdo) {
            $pdo->prepare("UPDATE discord_users SET kgd_balance = kgd_balance + 15, games_won = games_won + 1, total_earned = total_earned + 15 WHERE discord_id = ?")->execute([$userId]);
            $pdo->prepare("INSERT INTO discord_economy (discord_id, entry_type, amount, reason) VALUES (?, 'earn', 15, 'RPS win')")->execute([$userId]);
        }
    } elseif ($result === 'lose') {
        $resultText = "💀 **You lose!**";
        $color = 0xED4245;
        if ($pdo) $pdo->prepare("UPDATE discord_users SET games_lost = games_lost + 1 WHERE discord_id = ?")->execute([$userId]);
    } else {
        $resultText = "🤝 **It's a tie!** +5 KGD";
        $color = 0xFEE75C;
        if ($pdo) {
            $pdo->prepare("UPDATE discord_users SET kgd_balance = kgd_balance + 5, total_earned = total_earned + 5 WHERE discord_id = ?")->execute([$userId]);
            $pdo->prepare("INSERT INTO discord_economy (discord_id, entry_type, amount, reason) VALUES (?, 'earn', 5, 'RPS tie')")->execute([$userId]);
        }
    }

    respond(null, [
        embed('✊ Rock Paper Scissors', '', $color, [
            field("$globalName", $emojis[$choice] ?? '❓', true),
            field('vs', '⚔️', true),
            field('Alfred AI', $emojis[$aiChoice] ?? '❓', true),
            field('Result', $resultText, false),
        ])
    ], [
        actionRow(
            btn(1, '🪨 Rock', 'rps_quick_rock'),
            btn(1, '📄 Paper', 'rps_quick_paper'),
            btn(1, '✂️ Scissors', 'rps_quick_scissors')
        )
    ]);
}
