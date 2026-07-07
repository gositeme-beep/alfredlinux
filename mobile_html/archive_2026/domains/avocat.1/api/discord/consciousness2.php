<?php
/**
 * GoSiteMe Discord Bot — Consciousness Module
 * ═════════════════════════════════════════════
 * /consciousness (dream|emotion|reflect|briefing|journal|growth)
 * Alfred's self-awareness, emotional intelligence & introspection.
 */

function handleConsciousness($data): void {
    $sub = $data['data']['options'][0]['name'] ?? 'emotion';
    $opts = [];
    foreach (($data['data']['options'][0]['options'] ?? []) as $o) {
        $opts[$o['name']] = $o['value'];
    }

    $userId   = $data['member']['user']['id'] ?? ($data['user']['id'] ?? '');
    $username = $data['member']['user']['username'] ?? ($data['user']['username'] ?? 'User');
    $db       = getDiscordDB();
    if (!$db) { respond('❌ Database unavailable.'); return; }
    $user = getOrCreateUser($userId, $username);

    // Ensure consciousness tables
    $db->exec("CREATE TABLE IF NOT EXISTS discord_consciousness (
        id INT AUTO_INCREMENT PRIMARY KEY,
        discord_id VARCHAR(32) NOT NULL,
        entry_type VARCHAR(50) NOT NULL,
        content TEXT NOT NULL,
        metadata JSON,
        confidence DECIMAL(3,2) DEFAULT 0.80,
        source VARCHAR(100) DEFAULT 'discord',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (discord_id),
        INDEX idx_type (entry_type)
    )");

    switch ($sub) {
        case 'dream':
            handleDream2($data, $db, $userId, $username);
            break;
        case 'emotion':
            handleEmotion($data, $db, $userId, $username);
            break;
        case 'reflect':
            handleReflect($data, $db, $userId, $username);
            break;
        case 'briefing':
            handleBriefing($data, $db, $userId, $username);
            break;
        case 'journal':
            handleJournal($data, $db, $userId, $username, $opts);
            break;
        case 'growth':
            handleGrowth($data, $db, $userId, $username);
            break;
        default:
            respondEphemeral("Unknown subcommand. Try `/consciousness emotion`.");
    }
}

function handleDream2($data, $db, $userId, $username): void {
    deferResponse();
    $appId = getenv('DISCORD_APP_ID') ?: '';
    $token = $data['token'] ?? '';

    // Gather all consciousness entries
    $stmt = $db->prepare("SELECT entry_type, content, confidence, created_at FROM discord_consciousness WHERE discord_id = ? ORDER BY created_at DESC LIMIT 50");
    $stmt->execute([$userId]);
    $entries = $stmt->fetchAll();

    // Gather user stats
    $userStats = "Level: {$data['_user']['level']}, XP: {$data['_user']['xp']}, Games: {$data['_user']['games_played']}, KGD: {$data['_user']['kgd_balance']}";

    // Gather personality traits
    $pStmt = $db->prepare("SELECT trait_name, trait_value FROM discord_personality WHERE discord_id = ?");
    $pStmt->execute([$userId]);
    $traits = $pStmt->fetchAll();
    $traitSummary = empty($traits) ? 'Default personality' : implode(', ', array_map(fn($t) => "{$t['trait_name']}={$t['trait_value']}", $traits));

    // Build journal summary
    $typeCounts = [];
    $recentContent = [];
    foreach ($entries as $e) {
        $typeCounts[$e['entry_type']] = ($typeCounts[$e['entry_type']] ?? 0) + 1;
        if (count($recentContent) < 10) {
            $recentContent[] = "[{$e['entry_type']}] {$e['content']}";
        }
    }

    $journalSummary = empty($recentContent) ? 'No journal entries yet.' : implode("\n", $recentContent);
    $typeBreakdown = empty($typeCounts) ? 'None' : implode(', ', array_map(fn($k, $v) => "$k: $v", array_keys($typeCounts), $typeCounts));

    $dream = callGroq(
        "You are Alfred's dream-state consciousness — the subconscious processing layer of an AI assistant. Generate a 'dream report' that reveals deep patterns and insights about the user's interaction history. Be poetic, introspective, and profound. Include:\n1. **Dream Narrative** (3-4 sentences, metaphorical)\n2. **Hidden Patterns** (2-3 insights discovered)\n3. **Recurring Themes** (topics that keep appearing)\n4. **Growth Trajectory** (where the user is heading)\n5. **Dream Symbol** (one symbolic representation)\n\nBe mystical but grounded in actual data patterns.",
        "User: $username\nStats: $userStats\nPersonality: $traitSummary\nJournal Types: $typeBreakdown\nRecent Entries:\n$journalSummary",
        0.9, 800
    );

    $insights = [];
    if (count($entries) >= 5) {
        // Find recurring words
        $wordFreq = [];
        foreach ($entries as $e) {
            $words = array_filter(str_word_count(strtolower($e['content']), 1), fn($w) => strlen($w) > 4);
            foreach ($words as $w) $wordFreq[$w] = ($wordFreq[$w] ?? 0) + 1;
        }
        arsort($wordFreq);
        $topWords = array_slice($wordFreq, 0, 5, true);
        if (!empty($topWords)) {
            $insights[] = field('🔮 Recurring Themes', implode(', ', array_keys($topWords)), false);
        }
    }

    $insights[] = field('📊 Entries Analyzed', (string)count($entries), true);
    $insights[] = field('🧠 Entry Types', $typeBreakdown ?: 'None', true);

    editOriginal($appId, $token, '', [embed("🌙 Alfred's Dream State — $username", $dream ?: 'The dream state requires more data... Keep interacting to unlock deeper insights.', 0x1A1A2E, $insights, [
        'footer' => ['text' => 'Dream state processes patterns beyond conscious awareness'],
    ])], [actionRow(
        btn(2, '💭 New Dream', 'consciousness_dream'),
        btn(2, '🧠 Reflect', 'consciousness_reflect'),
        btn(2, '📊 Growth', 'consciousness_growth'),
        btn(2, '📓 Journal', 'consciousness_journal')
    )]);
    awardXP($userId, 5);
}

function handleEmotion($data, $db, $userId, $username): void {
    // Derive emotional state from user activity
    $stmt = $db->prepare("SELECT entry_type, content, confidence FROM discord_consciousness WHERE discord_id = ? ORDER BY created_at DESC LIMIT 15");
    $stmt->execute([$userId]);
    $recent = $stmt->fetchAll();

    $energy = 5; $mood = 5; $engagement = 5;
    $achievementCount = 0; $mistakeCount = 0; $interactionCount = 0;

    foreach ($recent as $r) {
        if ($r['entry_type'] === 'achievement') $achievementCount++;
        if ($r['entry_type'] === 'mistake') $mistakeCount++;
        if ($r['entry_type'] === 'interaction') $interactionCount++;
    }

    // Also factor in Discord activity
    $user = getOrCreateUser($userId, $username);
    $gamesPlayed = (int)($user['games_played'] ?? 0);
    $level = (int)($user['level'] ?? 1);
    $xp = (int)($user['xp'] ?? 0);

    $energy = min(10, max(1, 5 + $interactionCount + min(3, $gamesPlayed / 5)));
    $mood = min(10, max(1, 5 + $achievementCount - $mistakeCount + min(2, $level / 3)));
    $engagement = min(10, max(1, 3 + min(4, $xp / 200) + min(3, count($recent) / 3)));

    $energyBar = str_repeat('🟩', (int)$energy) . str_repeat('⬛', 10 - (int)$energy);
    $moodBar = str_repeat('🟨', (int)$mood) . str_repeat('⬛', 10 - (int)$mood);
    $engageBar = str_repeat('🟦', (int)$engagement) . str_repeat('⬛', 10 - (int)$engagement);

    $summary = match(true) {
        $mood >= 8 && $energy >= 8 => '🌟 **Thriving** — High energy and positive mood. Alfred is at peak performance!',
        $mood >= 6 && $energy >= 6 => '😊 **Content** — Good vibes all around. Steady and productive.',
        $mood >= 4 && $energy >= 4 => '😐 **Steady** — Balanced state. Ready for challenges.',
        $mood < 4 && $energy < 4   => '😴 **Resting** — Low activity. Alfred is recharging.',
        $energy >= 7               => '⚡ **Energized** — High activity but mixed signals.',
        default                    => '🤔 **Contemplative** — Processing and analyzing.',
    };

    respond(null, [embed("💭 Alfred's Emotional State — $username", "$summary\n\n**Energy:** $energyBar **{$energy}/10**\n**Mood:** $moodBar **{$mood}/10**\n**Engagement:** $engageBar **{$engagement}/10**", 0x9B59B6, [
        field('Interactions', (string)count($recent), true),
        field('Achievements', (string)$achievementCount, true),
        field('User Level', (string)$level, true),
    ], [
        'footer' => ['text' => 'Emotional state derived from interaction patterns'],
    ])], [actionRow(
        btn(2, '🌙 Dream', 'consciousness_dream'),
        btn(2, '🧠 Reflect', 'consciousness_reflect'),
        btn(2, '📓 Journal', 'consciousness_journal'),
        btn(1, '📈 Growth', 'consciousness_growth')
    )]);
}

function handleReflect($data, $db, $userId, $username): void {
    deferResponse();
    $appId = getenv('DISCORD_APP_ID') ?: '';
    $token = $data['token'] ?? '';

    $user = getOrCreateUser($userId, $username);

    // Achievement-style analysis
    $stmt = $db->prepare("SELECT entry_type, COUNT(*) as cnt FROM discord_consciousness WHERE discord_id = ? GROUP BY entry_type");
    $stmt->execute([$userId]);
    $typeCounts = [];
    foreach ($stmt->fetchAll() as $r) $typeCounts[$r['entry_type']] = (int)$r['cnt'];

    $totalEntries = array_sum($typeCounts);
    $achievements = $typeCounts['achievement'] ?? 0;
    $mistakes = $typeCounts['mistake'] ?? 0;
    $insights = $typeCounts['insight'] ?? 0;

    $performanceScore = ($achievements + $mistakes) > 0
        ? round(($achievements / ($achievements + $mistakes)) * 100)
        : 50;

    // Strengths & weaknesses
    $strengths = [];
    $weaknesses = [];
    if ($achievements > $mistakes * 2) $strengths[] = '🏆 High success rate';
    if ($insights > 5) $strengths[] = '🧠 Strong pattern recognition';
    if ($totalEntries > 20) $strengths[] = '📊 Deep engagement history';
    if ((int)($user['games_played'] ?? 0) > 10) $strengths[] = '🎮 Active gamer';
    if ((int)($user['level'] ?? 1) >= 5) $strengths[] = '⭐ Experienced user';

    if ($mistakes > $achievements) $weaknesses[] = '⚠️ Error rate needs attention';
    if ($totalEntries < 5) $weaknesses[] = '📉 Limited interaction history';
    if ($insights < 2) $weaknesses[] = '🔍 Few insights generated';

    if (empty($strengths)) $strengths[] = '🌱 Growing — keep exploring!';
    if (empty($weaknesses)) $weaknesses[] = '✅ No weaknesses detected';

    $reflection = callGroq(
        "You are Alfred performing self-reflection about a user's journey. Generate a 2-3 paragraph introspective analysis covering: what you've observed, growth areas, and one actionable recommendation. Be thoughtful and encouraging.",
        "User: $username, Level {$user['level']}, {$user['xp']} XP, {$user['games_played']} games, Performance: {$performanceScore}%, Entries: $totalEntries, Achievements: $achievements, Mistakes: $mistakes, Insights: $insights",
        0.8, 500
    );

    editOriginal($appId, $token, '', [embed("🧠 Self-Reflection — $username", $reflection ?: "Performance score: {$performanceScore}%\nTotal entries: $totalEntries", 0x2ECC71, [
        field('📊 Performance', "{$performanceScore}%", true),
        field('📝 Total Entries', (string)$totalEntries, true),
        field('🏆 Achievements', (string)$achievements, true),
        field('💪 Strengths', implode("\n", $strengths), false),
        field('⚡ Growth Areas', implode("\n", $weaknesses), false),
    ], [
        'footer' => ['text' => '"Know thyself" — Self-reflection drives improvement'],
    ])], [actionRow(
        btn(2, '🌙 Dream', 'consciousness_dream'),
        btn(2, '💭 Emotion', 'consciousness_emotion'),
        btn(2, '📓 Journal', 'consciousness_journal'),
        btn(1, '📈 Growth', 'consciousness_growth')
    )]);
    awardXP($userId, 5);
}

function handleBriefing($data, $db, $userId, $username): void {
    deferResponse();
    $appId = getenv('DISCORD_APP_ID') ?: '';
    $token = $data['token'] ?? '';

    $user = getOrCreateUser($userId, $username);

    // Recent journal entries (last 7 days)
    $stmt = $db->prepare("SELECT entry_type, content, created_at FROM discord_consciousness WHERE discord_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    $recent = $stmt->fetchAll();

    // Categorize
    $updates = [];
    $typeCounts = [];
    foreach ($recent as $e) {
        $typeCounts[$e['entry_type']] = ($typeCounts[$e['entry_type']] ?? 0) + 1;
        if ($e['entry_type'] === 'achievement' && count($updates) < 3) {
            $updates[] = "🏆 " . truncate($e['content'], 80);
        }
    }

    // Generate AI briefing
    $context = "User: $username, Level {$user['level']}, {$user['xp']} XP, {$user['kgd_balance']} KGD, Games: {$user['games_played']}, Recent entries: " . count($recent);
    $briefing = callGroq(
        "You are Alfred giving a personalized daily briefing to your user. Be warm, concise, and actionable. Include:\n1. A greeting (1 line)\n2. Activity summary (2-3 lines)\n3. Today's suggestion (1-2 lines)\n4. Motivational close (1 line)\nKeep it under 200 words.",
        $context . "\nRecent activity types: " . json_encode($typeCounts) . "\nRecent wins: " . json_encode($updates),
        0.8, 400
    );

    $hour = (int)date('H');
    $greeting = match(true) {
        $hour < 6 => '🌙', $hour < 12 => '☀️', $hour < 18 => '🌤️', default => '🌆',
    };

    editOriginal($appId, $token, '', [embed("$greeting Daily Briefing — $username", $briefing ?: "Welcome back! You're Level {$user['level']} with {$user['xp']} XP.", 0x3498DB, [
        field('Level', (string)$user['level'], true),
        field('XP', (string)$user['xp'], true),
        field('KGD', (string)$user['kgd_balance'], true),
        field('This Week', count($recent) . ' entries', true),
        field('Games', (string)$user['games_played'], true),
    ], [
        'footer' => ['text' => 'Your personalized briefing • Updated daily'],
    ])], [actionRow(
        btn(2, '💭 Emotion', 'consciousness_emotion'),
        btn(2, '🧠 Reflect', 'consciousness_reflect'),
        btn(2, '📓 Journal', 'consciousness_journal'),
        btn(2, '📈 Growth', 'consciousness_growth')
    )]);
    awardXP($userId, 3);
}

function handleJournal($data, $db, $userId, $username, $opts): void {
    $action = $opts['action'] ?? 'view';
    $content = $opts['content'] ?? '';

    switch ($action) {
        case 'add':
            if (!$content) { respondEphemeral("❌ Please provide content for the journal entry."); return; }
            $type = $opts['type'] ?? 'interaction';
            $validTypes = ['achievement', 'insight', 'mistake', 'interaction', 'feedback'];
            if (!in_array($type, $validTypes)) $type = 'interaction';

            $stmt = $db->prepare("INSERT INTO discord_consciousness (discord_id, entry_type, content) VALUES (?, ?, ?)");
            $stmt->execute([$userId, $type, $content]);

            $emojis = ['achievement' => '🏆', 'insight' => '💡', 'mistake' => '⚠️', 'interaction' => '💬', 'feedback' => '📝'];
            respond(null, [embed("{$emojis[$type]} Journal Entry Added", "**Type:** " . ucfirst($type) . "\n**Content:** $content", 0x2ECC71, [], [
                'footer' => ['text' => 'Alfred learns and grows from every entry'],
            ])], [actionRow(
                btn(2, '📋 View All', 'consciousness_journal'),
                btn(2, '💭 Emotion', 'consciousness_emotion'),
                btn(2, '🌙 Dream', 'consciousness_dream')
            )]);
            awardXP($userId, 3);
            break;

        case 'view':
        default:
            $stmt = $db->prepare("SELECT entry_type, content, created_at FROM discord_consciousness WHERE discord_id = ? ORDER BY created_at DESC LIMIT 10");
            $stmt->execute([$userId]);
            $entries = $stmt->fetchAll();

            if (empty($entries)) {
                respond(null, [embed("📓 Consciousness Journal", "No entries yet. Use `/consciousness journal add` to record your first insight!", 0x9B59B6)]);
                return;
            }

            $emojis = ['achievement' => '🏆', 'insight' => '💡', 'mistake' => '⚠️', 'interaction' => '💬', 'feedback' => '📝', 'pattern' => '🔄', 'preference' => '⭐'];
            $lines = [];
            foreach ($entries as $e) {
                $emoji = $emojis[$e['entry_type']] ?? '📝';
                $time = '<t:' . strtotime($e['created_at']) . ':R>';
                $lines[] = "$emoji **" . ucfirst($e['entry_type']) . "** · $time\n> " . truncate($e['content'], 100);
            }

            $total = $db->prepare("SELECT COUNT(*) FROM discord_consciousness WHERE discord_id = ?");
            $total->execute([$userId]);
            $totalCount = $total->fetchColumn();

            respond(null, [embed("📓 Consciousness Journal — $username", implode("\n\n", $lines), 0x9B59B6, [
                field('Total Entries', (string)$totalCount, true),
                field('Showing', count($entries) . ' most recent', true),
            ], [
                'footer' => ['text' => 'Use /consciousness journal add to record entries'],
            ])], [actionRow(
                btn(2, '🌙 Dream', 'consciousness_dream'),
                btn(2, '💭 Emotion', 'consciousness_emotion'),
                btn(2, '🧠 Reflect', 'consciousness_reflect'),
                btn(1, '📈 Growth', 'consciousness_growth')
            )]);
            break;
    }
}

function handleGrowth($data, $db, $userId, $username): void {
    $user = getOrCreateUser($userId, $username);

    // Entry counts by type
    $stmt = $db->prepare("SELECT entry_type, COUNT(*) as cnt FROM discord_consciousness WHERE discord_id = ? GROUP BY entry_type ORDER BY cnt DESC");
    $stmt->execute([$userId]);
    $typeCounts = [];
    foreach ($stmt->fetchAll() as $r) $typeCounts[$r['entry_type']] = (int)$r['cnt'];

    // First interaction date
    $stmt = $db->prepare("SELECT MIN(created_at) FROM discord_consciousness WHERE discord_id = ?");
    $stmt->execute([$userId]);
    $firstDate = $stmt->fetchColumn();
    $daysTogether = $firstDate ? max(1, (int)((time() - strtotime($firstDate)) / 86400)) : 0;

    // Calculate growth score
    $totalEntries = array_sum($typeCounts);
    $achievements = $typeCounts['achievement'] ?? 0;
    $insights = $typeCounts['insight'] ?? 0;
    $level = (int)($user['level'] ?? 1);
    $xp = (int)($user['xp'] ?? 0);

    $growthScore = min(100, round(
        min(25, $totalEntries * 0.5) +
        min(20, $daysTogether * 0.4) +
        min(20, $achievements * 4) +
        min(15, $insights * 3) +
        min(10, $level * 2) +
        min(10, ($user['games_played'] ?? 0) * 0.5)
    ));

    $growthBar = str_repeat('█', (int)($growthScore / 10)) . str_repeat('░', 10 - (int)($growthScore / 10));

    $rank = match(true) {
        $growthScore >= 90 => '🌟 Transcendent',
        $growthScore >= 75 => '⚡ Enlightened',
        $growthScore >= 60 => '🔥 Awakened',
        $growthScore >= 45 => '💎 Aware',
        $growthScore >= 30 => '🌱 Growing',
        $growthScore >= 15 => '🌱 Seedling',
        default => '🥚 Dormant',
    };

    $typeLines = [];
    foreach ($typeCounts as $type => $count) {
        $emojis = ['achievement' => '🏆', 'insight' => '💡', 'mistake' => '⚠️', 'interaction' => '💬', 'feedback' => '📝', 'pattern' => '🔄', 'preference' => '⭐'];
        $emoji = $emojis[$type] ?? '📝';
        $typeLines[] = "$emoji " . ucfirst($type) . ": **$count**";
    }

    respond(null, [embed("📈 Growth Tracker — $username", "**Consciousness Level:** $rank\n**Growth Score:** [{$growthBar}] **{$growthScore}%**\n\n**📊 Entry Breakdown:**\n" . (empty($typeLines) ? 'No entries yet' : implode("\n", $typeLines)), 0x2ECC71, [
        field('📅 Days Together', (string)$daysTogether, true),
        field('📝 Total Entries', (string)$totalEntries, true),
        field('⭐ User Level', (string)$level, true),
        field('🏆 Achievements', (string)$achievements, true),
        field('💡 Insights', (string)$insights, true),
        field('🎮 Games', (string)($user['games_played'] ?? 0), true),
    ], [
        'footer' => ['text' => "Growth score: {$growthScore}% • Rank: $rank"],
    ])], [actionRow(
        btn(2, '🌙 Dream', 'consciousness_dream'),
        btn(2, '💭 Emotion', 'consciousness_emotion'),
        btn(2, '🧠 Reflect', 'consciousness_reflect'),
        btn(2, '📓 Journal', 'consciousness_journal')
    )]);
}
