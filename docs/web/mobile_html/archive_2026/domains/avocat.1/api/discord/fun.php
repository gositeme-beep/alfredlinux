<?php
/**
 * GoSiteMe Discord Bot — Fun Module
 * ══════════════════════════════════
 * /debate    — Watch two AI personas debate any topic
 * /roast     — AI roast battle (friendly)
 * /story     — Collaborative AI story with branching
 * /dream     — AI dream interpretation
 * /recipe    — AI recipe generator from ingredients
 * /interview — AI mock job interview
 * /riddle    — AI riddle challenge
 * /encrypt   — Encryption & hashing tools
 * /wisdom    — Daily AI wisdom & life advice
 * /persona   — Talk to famous historical figures
 *
 * UNIQUE: AI debate arena, dream analysis, mock interviews — all from Discord.
 */

function handleDebate(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $topic = '';
    foreach ($opts as $o) { if ($o['name'] === 'topic') $topic = $o['value']; }

    $userId = $data['member']['user']['id'] ?? '0';
    $appId = $data['application_id'] ?? '';
    $token = $data['token'] ?? '';
    $channelId = $data['channel_id'] ?? '';

    if (!$topic) { respond("⚔️ Usage: `/debate topic:Is AI smarter than humans?`"); return; }

    deferResponse();

    $result = callGroq(
        "You are a debate moderator. Generate a heated but respectful debate between two AI personas:\n"
        . "🔴 **APOLLO** (conservative, traditional, logical)\n"
        . "🔵 **NOVA** (progressive, innovative, empathetic)\n\n"
        . "Format: 3 rounds of back-and-forth. Each round:\n"
        . "🔴 **Apollo:** [argument]\n🔵 **Nova:** [counter-argument]\n\n"
        . "End with 🏛️ **Verdict:** A balanced conclusion noting valid points from both sides.\n"
        . "Use Discord markdown. Be engaging, witty, and substantive.",
        "Debate topic: $topic",
        0.9, 1500
    );

    followUp($appId, $token, '', [embed(
        "⚔️ AI Debate Arena",
        "**Topic:** $topic\n\n$result",
        0xE74C3C,
        [],
        ['footer' => ['text' => 'GoSiteMe AI Debate Arena']]
    )], [actionRow(
        btn(2, '🔄 New Round', 'debate_more_' . substr(md5($topic), 0, 8)),
        btn(2, '⚔️ New Debate', 'debate_new')
    )]);

    awardXP($userId, 8, $appId, $token, $channelId);
}


function handleRoast(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $targetUser = null; $intensity = 'medium';
    foreach ($opts as $o) {
        if ($o['name'] === 'user') $targetUser = $o['value'];
        if ($o['name'] === 'intensity') $intensity = $o['value'];
    }

    $userId = $data['member']['user']['id'] ?? '0';
    $appId = $data['application_id'] ?? '';
    $token = $data['token'] ?? '';
    $channelId = $data['channel_id'] ?? '';
    $globalName = $data['member']['user']['global_name'] ?? $data['member']['user']['username'] ?? 'User';

    $target = $targetUser ? "<@$targetUser>" : $globalName;

    deferResponse();

    $intensityMap = [
        'mild' => 'gentle, playful teasing. Keep it very light and wholesome.',
        'medium' => 'standard comedy roast. Witty and clever but not mean-spirited.',
        'savage' => 'intense roast battle style. Hard-hitting but still humorous, never cruel or personal.',
    ];

    $result = callGroq(
        "You are a comedy roast master. Generate a roast for a Discord user. Rules:\n"
        . "- 5 roast lines, each on its own line with a number\n"
        . "- Use Discord-related humor (gaming, typing, being online too much, etc.)\n"
        . "- NEVER use slurs, discriminatory language, or truly hurtful content\n"
        . "- Keep it fun and lighthearted — it's entertainment\n"
        . "- End with a 'But seriously...' compliment\n"
        . "- Intensity: " . ($intensityMap[$intensity] ?? $intensityMap['medium']),
        "Roast the Discord user named: $target",
        0.95, 500
    );

    followUp($appId, $token, '', [embed(
        "🔥 Roast Machine",
        "**Target:** $target\n**Intensity:** " . ucfirst($intensity) . "\n\n$result",
        0xFF6B35,
        [],
        ['footer' => ['text' => 'All in good fun! 😄']]
    )], [actionRow(
        btn(2, '🔥 Roast Again', 'roast_again'),
        btn(2, '💀 Go Savage', 'roast_savage')
    )]);

    awardXP($userId, 5, $appId, $token, $channelId);
}


function handleStory(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $genre = 'fantasy'; $beginning = '';
    foreach ($opts as $o) {
        if ($o['name'] === 'genre') $genre = $o['value'];
        if ($o['name'] === 'beginning') $beginning = $o['value'];
    }

    $userId = $data['member']['user']['id'] ?? '0';
    $appId = $data['application_id'] ?? '';
    $token = $data['token'] ?? '';
    $channelId = $data['channel_id'] ?? '';

    deferResponse();

    $startPrompt = $beginning ?: "Begin an original story. Be creative.";

    $result = callGroq(
        "You are a master storyteller. Write a short story segment (200-300 words) in the **$genre** genre. "
        . "Use vivid descriptions and compelling dialogue. Use Discord markdown for formatting.\n\n"
        . "At the end, present exactly 3 choices for what happens next, formatted as:\n"
        . "1️⃣ [Choice A]\n2️⃣ [Choice B]\n3️⃣ [Choice C]\n\n"
        . "Make each choice lead to drastically different outcomes.",
        "Story beginning: $startPrompt",
        0.9, 800
    );

    followUp($appId, $token, '', [embed(
        "📖 Story Time — " . ucfirst($genre),
        $result,
        0x9B59B6,
        [],
        ['footer' => ['text' => 'Choose your path! React below.']]
    )], [actionRow(
        btn(2, '1️⃣ Choice A', 'story_choice_1'),
        btn(2, '2️⃣ Choice B', 'story_choice_2'),
        btn(2, '3️⃣ Choice C', 'story_choice_3'),
        btn(2, '📖 New Story', 'story_new')
    )]);

    awardXP($userId, 8, $appId, $token, $channelId);
}


function handleDream(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $description = '';
    foreach ($opts as $o) { if ($o['name'] === 'description') $description = $o['value']; }

    $userId = $data['member']['user']['id'] ?? '0';
    $appId = $data['application_id'] ?? '';
    $token = $data['token'] ?? '';
    $channelId = $data['channel_id'] ?? '';

    if (!$description) { respond("🌙 Usage: `/dream description:I was flying over a city made of glass...`"); return; }

    deferResponse();

    $result = callGroq(
        "You are a dream interpretation expert combining Jungian psychology, cultural symbolism, and modern dream analysis. "
        . "Analyze the dream with:\n"
        . "🔮 **Symbol Analysis:** Key symbols and their meanings\n"
        . "🧠 **Psychological Insight:** What this might reveal about the dreamer\n"
        . "💫 **Emotional Theme:** The dominant emotions and what they signify\n"
        . "🌟 **Life Message:** Practical insight or advice from this dream\n\n"
        . "Use Discord markdown. Be thoughtful, not generic. Max 400 words.",
        "Interpret this dream: $description",
        0.8, 700
    );

    followUp($appId, $token, '', [embed(
        "🌙 Dream Interpretation",
        "**Your dream:** " . truncate($description, 200) . "\n\n$result",
        0x2C3E50,
        [],
        ['footer' => ['text' => 'Dream analysis is for entertainment purposes']]
    )], [actionRow(
        btn(2, '🔄 Deeper Analysis', 'dream_deeper_' . substr(md5($description), 0, 8)),
        btn(2, '🌙 New Dream', 'dream_new')
    )]);

    awardXP($userId, 5, $appId, $token, $channelId);
}


function handleRecipe(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $ingredients = ''; $cuisine = '';
    foreach ($opts as $o) {
        if ($o['name'] === 'ingredients') $ingredients = $o['value'];
        if ($o['name'] === 'cuisine') $cuisine = $o['value'];
    }

    $userId = $data['member']['user']['id'] ?? '0';
    $appId = $data['application_id'] ?? '';
    $token = $data['token'] ?? '';
    $channelId = $data['channel_id'] ?? '';

    if (!$ingredients) { respond("🍳 Usage: `/recipe ingredients:chicken, rice, garlic cuisine:italian`"); return; }

    deferResponse();

    $cuisineStr = $cuisine ? " Cuisine style: $cuisine." : '';

    $result = callGroq(
        "You are a world-class chef. Create a recipe using the given ingredients. Format:\n"
        . "🍽️ **[Recipe Name]**\n"
        . "⏱️ Prep: X min | Cook: X min | Serves: X\n\n"
        . "📋 **Ingredients:**\n- [list with quantities]\n\n"
        . "👨‍🍳 **Instructions:**\n1. [step-by-step]\n\n"
        . "💡 **Chef's Tips:** [1-2 pro tips]\n\n"
        . "Use Discord markdown. Be creative but practical.",
        "Create a recipe with these ingredients: $ingredients.$cuisineStr",
        0.8, 800
    );

    followUp($appId, $token, '', [embed(
        "🍳 AI Recipe Generator",
        $result,
        0xE67E22,
        [],
        ['footer' => ['text' => 'Generated by GoSiteMe Chef AI']]
    )], [actionRow(
        btn(2, '🔄 Another Recipe', 'recipe_another'),
        btn(2, '🥗 Make it Healthy', 'recipe_healthy'),
        btn(2, '⚡ Make it Quick', 'recipe_quick')
    )]);

    awardXP($userId, 5, $appId, $token, $channelId);
}


function handleInterview(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $role = '';
    foreach ($opts as $o) { if ($o['name'] === 'role') $role = $o['value']; }

    $userId = $data['member']['user']['id'] ?? '0';
    $appId = $data['application_id'] ?? '';
    $token = $data['token'] ?? '';
    $channelId = $data['channel_id'] ?? '';

    if (!$role) { respond("💼 Usage: `/interview role:Software Engineer at Google`"); return; }

    deferResponse();

    $result = callGroq(
        "You are a technical interviewer at a top tech company. Generate a mock interview for the given role:\n\n"
        . "Format exactly as:\n"
        . "📋 **Interview: [Role]**\n\n"
        . "**Round 1 — Behavioral:**\n❓ [Question]\n💡 **What they're looking for:** [hint]\n\n"
        . "**Round 2 — Technical:**\n❓ [Question]\n💡 **Key points:** [hint]\n\n"
        . "**Round 3 — Problem Solving:**\n❓ [Scenario/coding question]\n💡 **Approach:** [hint]\n\n"
        . "**Round 4 — Culture Fit:**\n❓ [Question]\n💡 **Tip:** [hint]\n\n"
        . "End with 🎯 **Interview Tips for this Role:** [3 specific tips]",
        "Generate interview questions for: $role",
        0.8, 1000
    );

    followUp($appId, $token, '', [embed(
        "💼 Mock Interview",
        "**Role:** $role\n\n$result",
        0x3498DB,
        [],
        ['footer' => ['text' => 'GoSiteMe Career Prep']]
    )], [actionRow(
        btn(2, '🔄 New Questions', 'interview_new_' . substr(md5($role), 0, 8)),
        btn(2, '📝 Answer Tips', 'interview_tips')
    )]);

    awardXP($userId, 10, $appId, $token, $channelId);
}


function handleRiddle(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $difficulty = 'medium';
    foreach ($opts as $o) { if ($o['name'] === 'difficulty') $difficulty = $o['value']; }

    $userId = $data['member']['user']['id'] ?? '0';
    $appId = $data['application_id'] ?? '';
    $token = $data['token'] ?? '';
    $channelId = $data['channel_id'] ?? '';

    deferResponse();

    $result = callGroq(
        "Generate a creative riddle. Difficulty: $difficulty.\n\n"
        . "Format:\n"
        . "🧩 **Riddle:**\n[The riddle — 2-4 lines, poetic if possible]\n\n"
        . "Difficulty: [easy/medium/hard]\n"
        . "Category: [logic/wordplay/lateral thinking/math]\n\n"
        . "Do NOT reveal the answer. End with \"Think you know? Click the button below!\"\n\n"
        . "Also generate the answer but put it after the marker ANSWER: on a new line at the very end.",
        "Generate an original $difficulty riddle",
        0.95, 400
    );

    // Split answer from riddle
    $parts = preg_split('/\bANSWER:\s*/i', $result, 2);
    $riddleText = trim($parts[0]);
    $answer = trim($parts[1] ?? 'Think about it carefully!');

    // Store answer for reveal button
    $riddleId = substr(md5($riddleText . time()), 0, 10);
    $pdo = getDiscordDB();
    if ($pdo) {
        $stmt = $pdo->prepare("INSERT INTO discord_riddles (id, answer) VALUES (?, ?) ON DUPLICATE KEY UPDATE answer = VALUES(answer)");
        $stmt->execute([$riddleId, $answer]);
    }

    $rewards = ['easy' => 5, 'medium' => 10, 'hard' => 20];
    $reward = $rewards[$difficulty] ?? 10;

    followUp($appId, $token, '', [embed(
        "🧩 Riddle Challenge",
        "$riddleText\n\n🏆 **Reward:** $reward KGD for solving!",
        0x9B59B6,
        [],
        ['footer' => ['text' => 'GoSiteMe Riddle Master']]
    )], [actionRow(
        btn(2, '💡 Reveal Answer', "riddle_reveal_$riddleId"),
        btn(2, '🧩 New Riddle', 'riddle_new'),
        btn(2, '🔥 Harder', 'riddle_hard')
    )]);

    awardXP($userId, 3, $appId, $token, $channelId);
}


function handleEncrypt(array $data): void {
    $subCmd = getSubcommand($data);
    $subOpts = getSubOptions($data);

    $appId = $data['application_id'] ?? '';
    $token = $data['token'] ?? '';
    $userId = $data['member']['user']['id'] ?? '0';
    $channelId = $data['channel_id'] ?? '';

    switch ($subCmd) {
        case 'hash':
            $text = $subOpts['text'] ?? '';
            $algo = $subOpts['algorithm'] ?? 'sha256';
            if (!$text) { respondEphemeral("Usage: `/encrypt hash text:hello algorithm:sha256`"); return; }

            $algos = ['md5', 'sha1', 'sha256', 'sha512'];
            if (!in_array($algo, $algos)) { respondEphemeral("Supported: " . implode(', ', $algos)); return; }

            $hashed = hash($algo, $text);

            respondEphemeral(null, [embed(
                "🔐 Hash Result",
                "**Algorithm:** `$algo`\n**Input:** `" . truncate($text, 50) . "`\n**Hash:**\n```\n$hashed\n```",
                0x2ECC71
            )]);
            awardXP($userId, 3, $appId, $token, $channelId);
            break;

        case 'encode':
            $text = $subOpts['text'] ?? '';
            $format = $subOpts['format'] ?? 'base64';
            if (!$text) { respondEphemeral("Usage: `/encrypt encode text:hello format:base64`"); return; }

            if ($format === 'base64') {
                $encoded = base64_encode($text);
            } elseif ($format === 'hex') {
                $encoded = bin2hex($text);
            } elseif ($format === 'rot13') {
                $encoded = str_rot13($text);
            } elseif ($format === 'binary') {
                $encoded = implode(' ', array_map(fn($c) => sprintf('%08b', ord($c)), str_split($text)));
            } elseif ($format === 'reverse') {
                $encoded = strrev($text);
            } else {
                $encoded = base64_encode($text);
            }

            respondEphemeral(null, [embed(
                "🔐 Encoded",
                "**Format:** `$format`\n**Input:** `" . truncate($text, 50) . "`\n**Output:**\n```\n" . truncate($encoded, 1500) . "\n```",
                0x3498DB
            )]);
            awardXP($userId, 3, $appId, $token, $channelId);
            break;

        case 'decode':
            $text = $subOpts['text'] ?? '';
            $format = $subOpts['format'] ?? 'base64';
            if (!$text) { respondEphemeral("Usage: `/encrypt decode text:aGVsbG8= format:base64`"); return; }

            if ($format === 'base64') {
                $decoded = base64_decode($text, true);
                if ($decoded === false) { respondEphemeral("❌ Invalid Base64 input."); return; }
            } elseif ($format === 'hex') {
                $decoded = @hex2bin($text);
                if ($decoded === false) { respondEphemeral("❌ Invalid hex input."); return; }
            } elseif ($format === 'rot13') {
                $decoded = str_rot13($text);
            } elseif ($format === 'binary') {
                $decoded = implode('', array_map(fn($b) => chr(bindec($b)), explode(' ', $text)));
            } elseif ($format === 'reverse') {
                $decoded = strrev($text);
            } else {
                $decoded = base64_decode($text, true) ?: $text;
            }

            respondEphemeral(null, [embed(
                "🔓 Decoded",
                "**Format:** `$format`\n**Input:** `" . truncate($text, 50) . "`\n**Output:**\n```\n" . truncate($decoded, 1500) . "\n```",
                0xE74C3C
            )]);
            awardXP($userId, 3, $appId, $token, $channelId);
            break;

        case 'password':
            $length = min(128, max(8, (int)($subOpts['length'] ?? 16)));
            $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+[]{}|;:,.<>?';
            $password = '';
            for ($i = 0; $i < $length; $i++) {
                $password .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $strength = $length >= 20 ? '🟢 Very Strong' : ($length >= 14 ? '🟡 Strong' : ($length >= 10 ? '🟠 Medium' : '🔴 Weak'));

            respondEphemeral(null, [embed(
                "🔑 Password Generator",
                "**Length:** $length\n**Strength:** $strength\n**Password:**\n```\n$password\n```",
                0x2ECC71
            )]);
            awardXP($userId, 2, $appId, $token, $channelId);
            break;

        default:
            respondEphemeral("Usage: `/encrypt hash`, `/encrypt encode`, `/encrypt decode`, or `/encrypt password`");
    }
}


function handleWisdom(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $topic = '';
    foreach ($opts as $o) { if ($o['name'] === 'topic') $topic = $o['value']; }

    $userId = $data['member']['user']['id'] ?? '0';
    $appId = $data['application_id'] ?? '';
    $token = $data['token'] ?? '';
    $channelId = $data['channel_id'] ?? '';

    deferResponse();

    $topicStr = $topic ? " on the topic of: $topic" : '';

    $result = callGroq(
        "You are a wise sage who blends ancient philosophy with modern psychology. "
        . "Generate a unique piece of daily wisdom$topicStr. Format:\n\n"
        . "🌟 **[A short, powerful statement — 1 sentence]**\n\n"
        . "[2-3 sentences expanding on this wisdom]\n\n"
        . "📚 **Ancient Connection:** [Link to an ancient philosophy or text]\n"
        . "🧠 **Modern Science:** [Relevant psychological or scientific backing]\n"
        . "🎯 **Today's Challenge:** [One specific action to try today]\n\n"
        . "Use Discord markdown. Be profound but accessible.",
        "Generate daily wisdom" . ($topic ? " about: $topic" : ""),
        0.9, 500
    );

    followUp($appId, $token, '', [embed(
        "🌟 Daily Wisdom",
        $result,
        0xF39C12,
        [],
        ['footer' => ['text' => date('F j, Y') . ' • GoSiteMe Wisdom']]
    )], [actionRow(
        btn(2, '🔄 More Wisdom', 'wisdom_new'),
        btn(2, '📖 Deep Dive', 'wisdom_deep')
    )]);

    awardXP($userId, 5, $appId, $token, $channelId);
}


function handlePersona(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $name = ''; $message = '';
    foreach ($opts as $o) {
        if ($o['name'] === 'name') $name = $o['value'];
        if ($o['name'] === 'message') $message = $o['value'];
    }

    $userId = $data['member']['user']['id'] ?? '0';
    $appId = $data['application_id'] ?? '';
    $token = $data['token'] ?? '';
    $channelId = $data['channel_id'] ?? '';

    if (!$name || !$message) { respond("🎭 Usage: `/persona name:Albert Einstein message:What inspired you?`"); return; }

    deferResponse();

    $result = callGroq(
        "You ARE $name. Respond as this historical/famous figure would. Rules:\n"
        . "- Speak in their voice, using their known speech patterns and vocabulary\n"
        . "- Reference their actual work, achievements, and known beliefs\n"
        . "- Stay in character completely\n"
        . "- If they're known for certain phrases or mannerisms, use them\n"
        . "- Keep response under 300 words\n"
        . "- Use Discord markdown for formatting\n"
        . "- Start with a characteristic greeting",
        "As $name, respond to: $message",
        0.85, 600
    );

    // Color based on era
    $color = 0x5865F2;

    followUp($appId, $token, '', [embed(
        "🎭 $name",
        $result,
        $color,
        [],
        ['footer' => ['text' => "Historical AI Persona • Not real $name"]]
    )], [actionRow(
        btn(2, '💬 Ask Again', 'persona_again_' . substr(md5($name), 0, 8)),
        btn(2, '🎭 New Persona', 'persona_new')
    )]);

    awardXP($userId, 5, $appId, $token, $channelId);
}