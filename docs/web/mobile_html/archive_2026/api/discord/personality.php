<?php
/**
 * GoSiteMe Discord Bot — Personality Engine Module
 * ══════════════════════════════════════════════════
 * Commands: /personality /mood /style /memory /adapt
 * Persistent AI personality customization per user.
 */

function handlePersonality($data): void {
    $sub = $data['data']['options'][0]['name'] ?? 'view';
    $opts = [];
    foreach (($data['data']['options'][0]['options'] ?? []) as $o) {
        $opts[$o['name']] = $o['value'];
    }

    $userId   = $data['member']['user']['id'] ?? ($data['user']['id'] ?? '');
    $username = $data['member']['user']['username'] ?? ($data['user']['username'] ?? 'User');
    $db       = getDiscordDB();
    if (!$db) { respond('❌ Database unavailable.'); return; }
    $user = getOrCreateUser($userId, $username);

    // Ensure table
    $db->exec("CREATE TABLE IF NOT EXISTS discord_personality (
        id INT AUTO_INCREMENT PRIMARY KEY,
        discord_id VARCHAR(32) NOT NULL,
        trait_name VARCHAR(50) NOT NULL,
        trait_value VARCHAR(200) NOT NULL,
        confidence DECIMAL(3,2) DEFAULT 1.00,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_user_trait (discord_id, trait_name)
    )");

    $defaultTraits = [
        'humor'      => ['value' => 7, 'emoji' => '😂', 'label' => 'Humor'],
        'formality'  => ['value' => 5, 'emoji' => '🎩', 'label' => 'Formality'],
        'empathy'    => ['value' => 8, 'emoji' => '💗', 'label' => 'Empathy'],
        'creativity' => ['value' => 7, 'emoji' => '🎨', 'label' => 'Creativity'],
        'verbosity'  => ['value' => 5, 'emoji' => '📝', 'label' => 'Verbosity'],
        'sarcasm'    => ['value' => 3, 'emoji' => '😏', 'label' => 'Sarcasm'],
    ];

    switch ($sub) {
        case 'view':
            $stmt = $db->prepare("SELECT trait_name, trait_value, confidence FROM discord_personality WHERE discord_id = ?");
            $stmt->execute([$userId]);
            $rows = $stmt->fetchAll();

            $traits = $defaultTraits;
            foreach ($rows as $r) {
                if (isset($traits[$r['trait_name']])) {
                    $traits[$r['trait_name']]['value'] = (int)$r['trait_value'];
                }
            }

            $lines = [];
            foreach ($traits as $key => $t) {
                $bar = str_repeat('█', $t['value']) . str_repeat('░', 10 - $t['value']);
                $lines[] = "{$t['emoji']} **{$t['label']}**: [{$bar}] {$t['value']}/10";
            }

            respond(null, [embed("🧠 {$username}'s AI Personality", implode("\n", $lines), 0x9B59B6, [], [
                'footer' => ['text' => 'Use /personality set to customize • Changes affect all AI responses'],
            ])], [actionRow(
                btn(1, '🎭 Preset: Comedian', 'personality_preset_comedian'),
                btn(1, '🎩 Preset: Professional', 'personality_preset_pro'),
                btn(1, '🤖 Preset: Chaos', 'personality_preset_chaos'),
                btn(2, '🔄 Reset Default', 'personality_reset')
            )]);
            break;

        case 'set':
            $trait = $opts['trait'] ?? 'humor';
            $level = max(1, min(10, (int)($opts['level'] ?? 5)));

            if (!isset($defaultTraits[$trait])) {
                respondEphemeral("❌ Unknown trait. Choose: " . implode(', ', array_keys($defaultTraits)));
                return;
            }

            $stmt = $db->prepare("INSERT INTO discord_personality (discord_id, trait_name, trait_value)
                VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE trait_value = VALUES(trait_value), updated_at = NOW()");
            $stmt->execute([$userId, $trait, (string)$level]);

            $t = $defaultTraits[$trait];
            $bar = str_repeat('█', $level) . str_repeat('░', 10 - $level);
            respond(null, [embed("✅ Personality Updated", "{$t['emoji']} **{$t['label']}**: [{$bar}] {$level}/10\n\nAlfred will now adjust responses accordingly.", 0x2ECC71)]);
            awardXP($userId, 3);
            break;

        case 'export':
            $stmt = $db->prepare("SELECT trait_name, trait_value FROM discord_personality WHERE discord_id = ?");
            $stmt->execute([$userId]);
            $rows = $stmt->fetchAll();

            $traits = $defaultTraits;
            foreach ($rows as $r) {
                if (isset($traits[$r['trait_name']])) {
                    $traits[$r['trait_name']]['value'] = (int)$r['trait_value'];
                }
            }

            $prompt = callGroq(
                "Generate a 2-sentence personality description for someone with these AI interaction preferences",
                json_encode(array_map(fn($t) => $t['value'], $traits)),
                0.8, 150
            );

            respond(null, [embed("🪪 Personality Card — $username", $prompt ?: 'Could not generate description.', 0x9B59B6, [
                field('Humor', $traits['humor']['value'] . '/10', true),
                field('Formality', $traits['formality']['value'] . '/10', true),
                field('Empathy', $traits['empathy']['value'] . '/10', true),
                field('Creativity', $traits['creativity']['value'] . '/10', true),
                field('Verbosity', $traits['verbosity']['value'] . '/10', true),
                field('Sarcasm', $traits['sarcasm']['value'] . '/10', true),
            ])]);
            break;

        default:
            respondEphemeral("Unknown subcommand. Use `view`, `set`, or `export`.");
    }
}

function handleMood($data): void {
    $mood = $data['data']['options'][0]['value'] ?? 'neutral';

    $moods = [
        'happy'      => ['emoji' => '😊', 'color' => 0xFFD700, 'style' => 'upbeat and encouraging, use cheerful language'],
        'sad'        => ['emoji' => '😢', 'color' => 0x3498DB, 'style' => 'gentle, empathetic, comforting with warm language'],
        'excited'    => ['emoji' => '🎉', 'color' => 0xFF6B6B, 'style' => 'high energy, enthusiastic, use exclamation marks freely'],
        'chill'      => ['emoji' => '😎', 'color' => 0x1ABC9C, 'style' => 'relaxed, casual, laid-back vibes'],
        'focused'    => ['emoji' => '🎯', 'color' => 0xE74C3C, 'style' => 'precise, efficient, minimal filler words'],
        'mysterious' => ['emoji' => '🔮', 'color' => 0x8E44AD, 'style' => 'cryptic, thought-provoking, philosophical undertones'],
        'pirate'     => ['emoji' => '🏴‍☠️', 'color' => 0x2C3E50, 'style' => 'talk like a pirate, use nautical terms, arr matey'],
        'shakespeare'=> ['emoji' => '🎭', 'color' => 0xC0392B, 'style' => 'Shakespearean English, dramatic flair, poetic phrasing'],
    ];

    if (!isset($moods[$mood])) {
        respondEphemeral("Unknown mood. Choose: " . implode(', ', array_keys($moods)));
        return;
    }

    $m = $moods[$mood];
    $userId = $data['member']['user']['id'] ?? ($data['user']['id'] ?? '');
    $db = getDiscordDB();
    if ($db) {
        $db->exec("CREATE TABLE IF NOT EXISTS discord_personality (
            id INT AUTO_INCREMENT PRIMARY KEY, discord_id VARCHAR(32) NOT NULL,
            trait_name VARCHAR(50) NOT NULL, trait_value VARCHAR(200) NOT NULL,
            confidence DECIMAL(3,2) DEFAULT 1.00,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_user_trait (discord_id, trait_name)
        )");
        $stmt = $db->prepare("INSERT INTO discord_personality (discord_id, trait_name, trait_value)
            VALUES (?, 'mood', ?) ON DUPLICATE KEY UPDATE trait_value = VALUES(trait_value), updated_at = NOW()");
        $stmt->execute([$userId, $mood]);
    }

    respond(null, [embed("{$m['emoji']} Mood Set: " . ucfirst($mood), "Alfred will now respond in **{$mood}** mode.\n\n*Style: {$m['style']}*", $m['color'])], [actionRow(
        btn(2, '😊 Happy', 'mood_happy'),
        btn(2, '🎯 Focused', 'mood_focused'),
        btn(2, '😎 Chill', 'mood_chill'),
        btn(2, '🏴‍☠️ Pirate', 'mood_pirate'),
        btn(2, '🎭 Shakespeare', 'mood_shakespeare')
    )]);
    awardXP($userId, 2);
}

function handleStyle($data): void {
    $style = $data['data']['options'][0]['value'] ?? 'balanced';

    $styles = [
        'concise'    => ['emoji' => '⚡', 'desc' => 'Short, direct answers. No fluff.', 'color' => 0xE74C3C],
        'detailed'   => ['emoji' => '📖', 'desc' => 'Thorough explanations with examples and context.', 'color' => 0x3498DB],
        'eli5'       => ['emoji' => '🧒', 'desc' => 'Explain Like I\'m 5 — simple language, analogies.', 'color' => 0x2ECC71],
        'academic'   => ['emoji' => '🎓', 'desc' => 'Formal, citations-style, structured arguments.', 'color' => 0x9B59B6],
        'storyteller'=> ['emoji' => '📚', 'desc' => 'Narrative form, weaves answers into stories.', 'color' => 0xF39C12],
        'bullet'     => ['emoji' => '📋', 'desc' => 'Bullet points and lists. Scannable format.', 'color' => 0x1ABC9C],
    ];

    if (!isset($styles[$style])) {
        respondEphemeral("Unknown style. Choose: " . implode(', ', array_keys($styles)));
        return;
    }

    $s = $styles[$style];
    $userId = $data['member']['user']['id'] ?? ($data['user']['id'] ?? '');
    $db = getDiscordDB();
    if ($db) {
        $stmt = $db->prepare("INSERT INTO discord_personality (discord_id, trait_name, trait_value)
            VALUES (?, 'style', ?) ON DUPLICATE KEY UPDATE trait_value = VALUES(trait_value), updated_at = NOW()");
        $stmt->execute([$userId, $style]);
    }

    respond(null, [embed("{$s['emoji']} Response Style: " . ucfirst($style), $s['desc'] . "\n\nAll `/alfred` responses will follow this format.", $s['color'])]);
    awardXP($userId, 2);
}

function handleMemorize($data): void {
    $fact = $data['data']['options'][0]['value'] ?? '';
    if (!$fact) { respondEphemeral("❌ Please provide a fact to remember."); return; }

    $userId   = $data['member']['user']['id'] ?? ($data['user']['id'] ?? '');
    $username = $data['member']['user']['username'] ?? ($data['user']['username'] ?? 'User');
    $db       = getDiscordDB();
    if (!$db) { respond('❌ Database unavailable.'); return; }

    $db->exec("CREATE TABLE IF NOT EXISTS discord_memories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        discord_id VARCHAR(32) NOT NULL,
        memory TEXT NOT NULL,
        category VARCHAR(50) DEFAULT 'general',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (discord_id)
    )");

    $stmt = $db->prepare("INSERT INTO discord_memories (discord_id, memory) VALUES (?, ?)");
    $stmt->execute([$userId, $fact]);

    $count = $db->prepare("SELECT COUNT(*) FROM discord_memories WHERE discord_id = ?");
    $count->execute([$userId]);
    $total = $count->fetchColumn();

    respond(null, [embed("🧠 Memory Stored", "**Remembered:** $fact\n\nAlfred now knows **$total** things about you.", 0x9B59B6, [], [
        'footer' => ['text' => "Alfred will use this in future conversations"],
    ])], [actionRow(
        btn(2, '📋 View All Memories', 'memory_list'),
        btn(4, '🗑️ Clear All', 'memory_clear')
    )]);
    awardXP($userId, 5);
}

function handleAdapt($data): void {
    $userId   = $data['member']['user']['id'] ?? ($data['user']['id'] ?? '');
    $username = $data['member']['user']['username'] ?? ($data['user']['username'] ?? 'User');
    $db       = getDiscordDB();
    if (!$db) { respond('❌ Database unavailable.'); return; }

    // Gather user data
    $stmt = $db->prepare("SELECT trait_name, trait_value FROM discord_personality WHERE discord_id = ?");
    $stmt->execute([$userId]);
    $traits = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $stmt2 = $db->prepare("SELECT memory FROM discord_memories WHERE discord_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt2->execute([$userId]);
    $memories = $stmt2->fetchAll(PDO::FETCH_COLUMN);

    $stmt3 = $db->prepare("SELECT xp, level, kgd_balance FROM discord_users WHERE discord_id = ?");
    $stmt3->execute([$userId]);
    $stats = $stmt3->fetch();

    deferResponse();

    $profile = json_encode([
        'traits'   => $traits ?: ['default' => true],
        'memories' => $memories ?: ['No memories yet'],
        'level'    => $stats['level'] ?? 1,
        'xp'       => $stats['xp'] ?? 0,
    ]);

    $analysis = callGroq(
        "You are an AI personality analyst. Given this user's profile data, generate a brief personality analysis with 3 specific recommendations for how Alfred should interact with them. Be warm and insightful.\n\nFormat:\n🔍 **Analysis**: (2-3 sentences)\n\n💡 **Recommendations**:\n1. ...\n2. ...\n3. ...\n\n🎯 **Interaction Style**: (1 sentence summary)",
        "User: $username\nProfile: $profile",
        0.8, 500
    );

    $appId = getenv('DISCORD_APP_ID') ?: '';
    $token = $data['token'] ?? '';

    editOriginal($appId, $token, '', [embed("🔬 Personality Analysis — $username", $analysis ?: 'Could not analyze.', 0x9B59B6)], [actionRow(
        btn(1, '🧠 View Personality', 'personality_view'),
        btn(2, '🔄 Re-Analyze', 'adapt_refresh')
    )]);
}
