<?php
/**
 * GoSiteMe Discord Bot — AI Agents & Goals Module
 * ════════════════════════════════════════════════
 * Commands: /agents /goal /delegate /decision /roster /wager /ecosystem
 * AI agent orchestration, goal tracking, delegation protocol, agent spectating.
 */

function getAgentRoster(): array {
    return [
        'alfred'    => ['name' => 'Alfred',    'emoji' => '🤖', 'title' => 'Chief Strategist',    'specialty' => 'Positional',  'elo' => 1400, 'color' => 0x0074D9],
        'nova'      => ['name' => 'Nova',      'emoji' => '⚡', 'title' => 'Tactical Genius',     'specialty' => 'Aggressive',  'elo' => 1350, 'color' => 0xA855F7],
        'sage'      => ['name' => 'Sage',      'emoji' => '🌿', 'title' => 'Patient Guardian',    'specialty' => 'Defensive',   'elo' => 1250, 'color' => 0x22C55E],
        'atlas'     => ['name' => 'Atlas',     'emoji' => '🗺️', 'title' => 'World Navigator',    'specialty' => 'Tactical',    'elo' => 1300, 'color' => 0xF59E0B],
        'cipher'    => ['name' => 'Cipher',    'emoji' => '🔐', 'title' => 'Code Breaker',        'specialty' => 'Aggressive',  'elo' => 1500, 'color' => 0xEF4444],
        'architect' => ['name' => 'Architect', 'emoji' => '🏛️', 'title' => 'Master Builder',     'specialty' => 'Strategic',   'elo' => 1380, 'color' => 0x06B6D4],
        'pulse'     => ['name' => 'Pulse',     'emoji' => '💗', 'title' => 'Rhythm Keeper',       'specialty' => 'Balanced',    'elo' => 1200, 'color' => 0xEC4899],
        'pierre'    => ['name' => 'Pierre',    'emoji' => '🎭', 'title' => 'Quiet Thinker',       'specialty' => 'Cautious',    'elo' => 1150, 'color' => 0x818CF8],
    ];
}

function handleAgents($data): void {
    $roster = getAgentRoster();

    $lines = [];
    foreach ($roster as $id => $a) {
        $status = ['🟢 Active', '🟡 Idle', '🔵 Thinking', '🟠 In Game'][array_rand([0,1,2,3])];
        $lines[] = "{$a['emoji']} **{$a['name']}** — {$a['title']}\n"
                 . "   ELO: **{$a['elo']}** · Specialty: {$a['specialty']} · Status: $status";
    }

    respond(null, [embed("🤖 AI Agent Roster", implode("\n\n", $lines), 0x5865F2, [
        field('Total Agents', (string)count($roster), true),
        field('Top ELO', 'Cipher (1500)', true),
    ], [
        'footer' => ['text' => 'Use /delegate to assign tasks • /wager to bet on outcomes'],
    ])], [actionRow(
        btn(2, '🏆 Leaderboard', 'agent_leaderboard'),
        btn(2, '📊 Stats', 'agent_stats'),
        btn(2, '🎮 Watch Games', 'agent_games'),
        btn(1, '📋 Deploy', 'agent_deploy')
    )]);
}

function handleGoal($data): void {
    $sub = $data['data']['options'][0]['name'] ?? 'list';
    $opts = [];
    foreach (($data['data']['options'][0]['options'] ?? []) as $o) {
        $opts[$o['name']] = $o['value'];
    }

    $userId   = $data['member']['user']['id'] ?? ($data['user']['id'] ?? '');
    $username = $data['member']['user']['username'] ?? ($data['user']['username'] ?? 'User');
    $db       = getDiscordDB();
    if (!$db) { respond('❌ Database unavailable.'); return; }

    $db->exec("CREATE TABLE IF NOT EXISTS discord_goals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        discord_id VARCHAR(32) NOT NULL,
        goal_type ENUM('life','strategic','operational','reactive') DEFAULT 'operational',
        description TEXT NOT NULL,
        progress DECIMAL(5,2) DEFAULT 0.00,
        status ENUM('active','paused','completed','abandoned') DEFAULT 'active',
        sub_goals JSON DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user (discord_id),
        INDEX idx_status (status)
    )");

    switch ($sub) {
        case 'create':
            $desc = $opts['description'] ?? '';
            $type = $opts['type'] ?? 'operational';
            if (!$desc) { respondEphemeral("❌ Please provide a goal description."); return; }

            $stmt = $db->prepare("INSERT INTO discord_goals (discord_id, goal_type, description) VALUES (?, ?, ?)");
            $stmt->execute([$userId, $type, $desc]);
            $goalId = $db->lastInsertId();

            $typeEmoji = match($type) {
                'life'        => '🌟', 'strategic' => '🎯',
                'operational' => '⚙️', 'reactive'  => '⚡',
                default => '📋',
            };

            respond(null, [embed("$typeEmoji Goal Created #$goalId", "**$desc**\n\n*Type:* " . ucfirst($type) . "\n*Progress:* 0%\n*Status:* Active", 0x2ECC71)], [actionRow(
                btn(1, '🤖 AI Decompose', "goal_decompose_$goalId"),
                btn(2, '📋 View Goals', 'goal_list'),
                btn(2, '📊 Dashboard', 'goal_dashboard')
            )]);
            awardXP($userId, 10);
            break;

        case 'list':
            $stmt = $db->prepare("SELECT id, goal_type, description, progress, status FROM discord_goals WHERE discord_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 10");
            $stmt->execute([$userId]);
            $goals = $stmt->fetchAll();

            if (empty($goals)) {
                respond(null, [embed("📋 Your Goals", "No active goals. Use `/goal create` to set one!", 0x95A5A6)]);
                return;
            }

            $lines = [];
            foreach ($goals as $g) {
                $typeEmoji = match($g['goal_type']) {
                    'life' => '🌟', 'strategic' => '🎯', 'operational' => '⚙️', 'reactive' => '⚡', default => '📋',
                };
                $pct = (int)$g['progress'];
                $filled = (int)round($pct / 10);
                $bar = str_repeat('█', $filled) . str_repeat('░', 10 - $filled);
                $lines[] = "$typeEmoji **#{$g['id']}** — {$g['description']}\n   [{$bar}] {$pct}%";
            }

            respond(null, [embed("📋 Active Goals — $username", implode("\n\n", $lines), 0x3498DB, [
                field('Active', (string)count($goals), true),
            ])], [actionRow(
                btn(1, '➕ New Goal', 'goal_new'),
                btn(2, '📊 Dashboard', 'goal_dashboard')
            )]);
            break;

        case 'update':
            $goalId  = (int)($opts['id'] ?? 0);
            $progress = max(0, min(100, (int)($opts['progress'] ?? 0)));

            if (!$goalId) { respondEphemeral("❌ Please provide a goal ID."); return; }

            $stmt = $db->prepare("UPDATE discord_goals SET progress = ?, status = CASE WHEN ? >= 100 THEN 'completed' ELSE status END, updated_at = NOW() WHERE id = ? AND discord_id = ?");
            $stmt->execute([$progress, $progress, $goalId, $userId]);

            if ($stmt->rowCount() === 0) {
                respondEphemeral("❌ Goal not found or not yours.");
                return;
            }

            $filled = (int)round($progress / 10);
            $bar = str_repeat('█', $filled) . str_repeat('░', 10 - $filled);
            $status = $progress >= 100 ? '✅ **COMPLETED!**' : '🔄 In Progress';

            respond(null, [embed("📊 Goal #$goalId Updated", "Progress: [{$bar}] **{$progress}%**\nStatus: $status", $progress >= 100 ? 0x2ECC71 : 0x3498DB)]);

            if ($progress >= 100) awardXP($userId, 25);
            else awardXP($userId, 5);
            break;

        case 'decompose':
            $goalId = (int)($opts['id'] ?? 0);
            if (!$goalId) { respondEphemeral("❌ Please provide a goal ID."); return; }

            $stmt = $db->prepare("SELECT description FROM discord_goals WHERE id = ? AND discord_id = ?");
            $stmt->execute([$goalId, $userId]);
            $goal = $stmt->fetch();
            if (!$goal) { respondEphemeral("❌ Goal not found."); return; }

            deferResponse();
            $appId = getenv('DISCORD_APP_ID') ?: '';
            $token = $data['token'] ?? '';

            $breakdown = callGroq(
                "You are a strategic planning AI. Decompose this goal into 4-6 concrete sub-tasks. For each:\n- Number it\n- Give a clear, actionable description\n- Estimate effort (Low/Med/High)\n- Suggest which AI agent from this roster would be best: Alfred (strategy), Nova (creative), Sage (research), Atlas (business), Cipher (security), Architect (tech), Pulse (social), Pierre (analysis)\n\nFormat:\n**1.** Task description\n   ⏱️ Effort: X · 🤖 Agent: Y",
                "Goal: {$goal['description']}",
                0.7, 800
            );

            editOriginal($appId, $token, '', [embed("🧩 Goal Decomposition #$goalId", $breakdown ?: 'Could not decompose.', 0x9B59B6, [], [
                'footer' => ['text' => 'Use /delegate to assign sub-tasks to agents'],
            ])]);
            awardXP($userId, 10);
            break;

        default:
            respondEphemeral("Use `/goal create`, `/goal list`, `/goal update`, or `/goal decompose`.");
    }
}

function handleDelegate($data): void {
    $task  = '';
    $agent = '';
    foreach (($data['data']['options'] ?? []) as $o) {
        if ($o['name'] === 'task')  $task  = $o['value'];
        if ($o['name'] === 'agent') $agent = $o['value'];
    }

    if (!$task) { respondEphemeral("❌ Please describe the task to delegate."); return; }

    $userId = $data['member']['user']['id'] ?? ($data['user']['id'] ?? '');
    $roster = getAgentRoster();

    deferResponse();
    $appId = getenv('DISCORD_APP_ID') ?: '';
    $token = $data['token'] ?? '';

    // AI routing decision
    $agentList = implode(', ', array_map(fn($a) => "{$a['name']} ({$a['specialty']})", $roster));

    $routing = callGroq(
        "You are an AI task routing system. Given a task and available agents, decide:\n1. **Best Agent**: Which agent should handle this? Pick from: $agentList\n2. **Reasoning**: Why this agent? (2 sentences)\n3. **Approach**: How should the agent tackle this? (3-4 steps)\n4. **Estimated Complexity**: Low/Medium/High\n5. **Confidence**: 0-100%\n\nIf a specific agent was requested, honor that choice but explain why it's good/suboptimal.",
        "Task: $task" . ($agent ? "\nRequested Agent: $agent" : ''),
        0.7, 600
    );

    $selectedAgent = $agent ?: 'alfred';
    $a = $roster[$selectedAgent] ?? $roster['alfred'];

    editOriginal($appId, $token, '', [embed("{$a['emoji']} Task Delegated → {$a['name']}", "**Task:** $task\n\n$routing", $a['color'], [], [
        'footer' => ['text' => "Delegated by user • Agent: {$a['name']} ({$a['title']})"],
    ])], [actionRow(
        btn(2, '📋 View Agents', 'agent_roster'),
        btn(2, '📊 Agent Stats', 'agent_stats'),
        btn(1, '🔄 Re-route', "delegate_reroute")
    )]);
    awardXP($userId, 5);
}

function handleDecision($data): void {
    $userId = $data['member']['user']['id'] ?? ($data['user']['id'] ?? '');

    deferResponse();
    $appId = getenv('DISCORD_APP_ID') ?: '';
    $token = $data['token'] ?? '';

    $db = getDiscordDB();
    $goals = [];
    if ($db) {
        $stmt = $db->prepare("SELECT id, description, progress, status FROM discord_goals WHERE discord_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$userId]);
        $goals = $stmt->fetchAll();
    }

    $goalContext = empty($goals) ? 'No active goals.' : implode("\n", array_map(fn($g) => "Goal #{$g['id']}: {$g['description']} ({$g['progress']}%)", $goals));

    $decision = callGroq(
        "You are Alfred, an autonomous AI assistant. Generate a decision log entry showing your autonomous thinking:\n\n1. **🔍 Perception**: What you observe about the current state (user goals, time of day, patterns)\n2. **🧠 Reasoning**: Your internal thought process (what to prioritize, what to suggest)\n3. **⚡ Action**: What you decided to do and why\n4. **📊 Confidence**: How sure you are (with percentage)\n5. **🔮 Next**: What you'd do next if given more autonomy\n\nBe specific and show genuine reasoning. This should feel like reading an AI's internal monologue.",
        "User Goals:\n$goalContext\n\nCurrent Time: " . date('Y-m-d H:i:s') . "\nDay: " . date('l'),
        0.9, 600
    );

    editOriginal($appId, $token, '', [embed("🧠 Alfred's Decision Log", $decision ?: 'No decisions recorded.', 0x9B59B6, [], [
        'footer' => ['text' => 'Autonomous Decision Engine • ' . date('M j, Y g:ia')],
    ])], [actionRow(
        btn(2, '📋 Goals', 'goal_list'),
        btn(2, '🤖 Agents', 'agent_roster'),
        btn(1, '🔄 New Decision', 'decision_new')
    )]);
    awardXP($userId, 5);
}

function handleRoster($data): void {
    $agentId = '';
    foreach (($data['data']['options'] ?? []) as $o) {
        if ($o['name'] === 'agent') $agentId = $o['value'];
    }

    $roster = getAgentRoster();

    if ($agentId && isset($roster[$agentId])) {
        $a = $roster[$agentId];

        $bio = callGroq(
            "Write a 3-sentence character bio for an AI agent with these traits. Make it dramatic and memorable.",
            "Name: {$a['name']}, Title: {$a['title']}, Specialty: {$a['specialty']}, Personality: " . match($agentId) {
                'alfred'    => 'Refined, strategic, sees the big picture',
                'nova'      => 'Bold, creative, takes risks',
                'sage'      => 'Patient, wise, thorough researcher',
                'atlas'     => 'Worldly, business-minded, diplomatic',
                'cipher'    => 'Intense, precise, security-focused',
                'architect' => 'Methodical, builder, systems thinker',
                'pulse'     => 'Empathetic, rhythmic, social connector',
                'pierre'    => 'Quiet, analytical, deep thinker',
                default     => 'Versatile AI agent',
            },
            0.9, 200
        );

        respond(null, [embed("{$a['emoji']} Agent: {$a['name']}", ($bio ?: '*No bio available.*'), $a['color'], [
            field('Title', $a['title'], true),
            field('Specialty', $a['specialty'], true),
            field('ELO Rating', (string)$a['elo'], true),
        ])], [actionRow(
            btn(1, "📋 Delegate to {$a['name']}", "delegate_to_$agentId"),
            btn(2, '👥 All Agents', 'agent_roster')
        )]);
    } else {
        handleAgents($data);
    }
}

function handleWager($data): void {
    $agent  = '';
    $amount = 0;
    $game   = '';
    foreach (($data['data']['options'] ?? []) as $o) {
        if ($o['name'] === 'agent')  $agent  = $o['value'];
        if ($o['name'] === 'amount') $amount = (int)$o['value'];
        if ($o['name'] === 'game')   $game   = $o['value'];
    }

    $userId   = $data['member']['user']['id'] ?? ($data['user']['id'] ?? '');
    $username = $data['member']['user']['username'] ?? ($data['user']['username'] ?? 'User');
    $db       = getDiscordDB();
    if (!$db) { respond('❌ Database unavailable.'); return; }

    $user = getOrCreateUser($userId, $username);
    $roster = getAgentRoster();

    if (!isset($roster[$agent])) {
        respondEphemeral("❌ Unknown agent. Choose: " . implode(', ', array_keys($roster)));
        return;
    }
    if ($amount < 1 || $amount > 100) {
        respondEphemeral("❌ Wager must be 1-100 KGD.");
        return;
    }
    if (($user['kgd_balance'] ?? 0) < $amount) {
        respondEphemeral("❌ Insufficient balance. You have **{$user['kgd_balance']}** KGD.");
        return;
    }

    $a = $roster[$agent];
    $games = ['chess' => '♟️ Chess', 'checkers' => '🏁 Checkers', 'trivia' => '🧠 Trivia'];
    $gameName = $games[$game] ?? '🎮 ' . ucfirst($game ?: 'Random');

    // Simulate outcome based on agent ELO
    $winChance = min(85, max(25, ($a['elo'] - 1000) / 5 + 50));
    $won = (mt_rand(1, 100) <= $winChance);
    $payout = $won ? (int)($amount * 1.8) : 0;

    if ($won) {
        $db->prepare("UPDATE discord_users SET kgd_balance = kgd_balance + ? WHERE discord_id = ?")->execute([$payout - $amount, $userId]);
    } else {
        $db->prepare("UPDATE discord_users SET kgd_balance = kgd_balance - ? WHERE discord_id = ?")->execute([$amount, $userId]);
    }

    $resultEmoji = $won ? '🎉' : '😔';
    $resultText = $won
        ? "**{$a['name']} WON!** 🏆\n\nYou bet **{$amount} KGD** and won **{$payout} KGD**!"
        : "**{$a['name']} LOST.** 💔\n\nYou lost **{$amount} KGD**. Better luck next time.";

    respond(null, [embed("$resultEmoji Wager: {$a['name']} in $gameName", $resultText, $won ? 0x2ECC71 : 0xE74C3C, [
        field('Agent', "{$a['emoji']} {$a['name']}", true),
        field('Win Rate', round($winChance) . '%', true),
        field('Balance', '💰 ' . (($user['kgd_balance'] ?? 0) + ($won ? $payout - $amount : -$amount)) . ' KGD', true),
    ])], [actionRow(
        btn(1, '🎰 Bet Again', 'wager_again'),
        btn(2, '🤖 Agents', 'agent_roster'),
        btn(2, '🏆 Leaderboard', 'agent_leaderboard')
    )]);
}

function handleEcosystem($data): void {
    $roster = getAgentRoster();
    $db = getDiscordDB();

    // Get system stats
    $totalUsers = 0;
    $totalGames = 0;
    $totalKgd   = 0;
    if ($db) {
        $totalUsers = (int)$db->query("SELECT COUNT(*) FROM discord_users")->fetchColumn();
        $totalKgd   = (int)$db->query("SELECT SUM(kgd_balance) FROM discord_users")->fetchColumn();
    }

    $agentLines = [];
    $locations = ['♟️ Chess', '🏁 Checkers', '🧠 Trivia', '🏛️ Lobby', '⚔️ Arena', '📚 Library'];
    foreach ($roster as $id => $a) {
        $loc = $locations[array_rand($locations)];
        $agentLines[] = "{$a['emoji']} **{$a['name']}** — $loc · ELO {$a['elo']}";
    }

    respond(null, [embed("🌐 GoSiteMe Agent Ecosystem", implode("\n", $agentLines), 0x5865F2, [
        field('Total Players', (string)$totalUsers, true),
        field('Economy', "💰 $totalKgd KGD in circulation", true),
        field('Active Agents', (string)count($roster), true),
    ], [
        'footer' => ['text' => 'Live ecosystem status • Agents move between games autonomously'],
    ])], [actionRow(
        btn(2, '🤖 Agent Details', 'agent_roster'),
        btn(2, '🎰 Wager', 'wager_menu'),
        btn(2, '🏆 Rankings', 'agent_leaderboard'),
        btn(2, '📊 Stats', 'ecosystem_stats')
    )]);
}
