<?php
/**
 * GoSiteMe Discord Bot — Learning & Optimization Module
 * ══════════════════════════════════════════════════════
 * /learn (feedback|insights|experiments|patterns|performance)
 * A/B testing, behavioral analysis, and self-improvement.
 */

function handleLearn($data): void {
    $sub = $data['data']['options'][0]['name'] ?? 'insights';
    $opts = [];
    foreach (($data['data']['options'][0]['options'] ?? []) as $o) {
        $opts[$o['name']] = $o['value'];
    }

    $userId   = $data['member']['user']['id'] ?? ($data['user']['id'] ?? '');
    $username = $data['member']['user']['username'] ?? ($data['user']['username'] ?? 'User');
    $db       = getDiscordDB();
    if (!$db) { respond('❌ Database unavailable.'); return; }
    $user = getOrCreateUser($userId, $username);

    $db->exec("CREATE TABLE IF NOT EXISTS discord_learning (
        id INT AUTO_INCREMENT PRIMARY KEY,
        discord_id VARCHAR(32) NOT NULL,
        type VARCHAR(50) NOT NULL,
        content TEXT NOT NULL,
        metadata JSON,
        rating INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (discord_id),
        INDEX idx_type (type)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS discord_experiments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        discord_id VARCHAR(32) NOT NULL,
        name VARCHAR(200) NOT NULL,
        variant_a TEXT NOT NULL,
        variant_b TEXT NOT NULL,
        votes_a INT DEFAULT 0,
        votes_b INT DEFAULT 0,
        status VARCHAR(20) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (discord_id)
    )");

    switch ($sub) {
        case 'feedback':
            $rating = (int)($opts['rating'] ?? 5);
            $comment = $opts['comment'] ?? '';
            $rating = max(1, min(10, $rating));

            $stmt = $db->prepare("INSERT INTO discord_learning (discord_id, type, content, rating) VALUES (?, 'feedback', ?, ?)");
            $stmt->execute([$userId, $comment ?: "Rating: $rating/10", $rating]);

            $stars = str_repeat('⭐', min(5, (int)ceil($rating / 2))) . str_repeat('☆', 5 - min(5, (int)ceil($rating / 2)));
            respond(null, [embed("📝 Feedback Recorded", "**Rating:** $stars ($rating/10)\n" . ($comment ? "**Comment:** $comment" : ''), $rating >= 7 ? 0x2ECC71 : ($rating >= 4 ? 0xF39C12 : 0xE74C3C), [], [
                'footer' => ['text' => 'Alfred learns from every piece of feedback'],
            ])], [actionRow(
                btn(2, '📊 Insights', 'learn_insights'),
                btn(2, '🧪 Experiments', 'learn_experiments'),
                btn(2, '📈 Performance', 'learn_performance')
            )]);
            awardXP($userId, 5);
            break;

        case 'insights':
            deferResponse();
            $appId = getenv('DISCORD_APP_ID') ?: '';
            $token = $data['token'] ?? '';

            // Gather feedback data
            $stmt = $db->prepare("SELECT type, content, rating, created_at FROM discord_learning WHERE discord_id = ? ORDER BY created_at DESC LIMIT 30");
            $stmt->execute([$userId]);
            $entries = $stmt->fetchAll();

            // Gather user interaction patterns
            $stmt2 = $db->prepare("SELECT entry_type, COUNT(*) as cnt FROM discord_consciousness WHERE discord_id = ? GROUP BY entry_type");
            $stmt2->execute([$userId]);
            $consciousness = [];
            foreach ($stmt2->fetchAll() as $r) $consciousness[$r['entry_type']] = (int)$r['cnt'];

            $avgRating = 0;
            $feedbackCount = 0;
            foreach ($entries as $e) {
                if ($e['type'] === 'feedback' && $e['rating'] > 0) {
                    $avgRating += $e['rating'];
                    $feedbackCount++;
                }
            }
            $avgRating = $feedbackCount > 0 ? round($avgRating / $feedbackCount, 1) : 0;

            $analysis = callGroq(
                "You are an AI learning analyst. Analyze the user's interaction data and generate:\n1. **Key Patterns** (3 bullet points)\n2. **Improvement Areas** (2 bullet points)\n3. **Recommendation** (1 actionable suggestion)\n4. **Learning Score** (out of 100)\nBe data-driven and specific.",
                "User: $username, Level {$user['level']}, Avg Rating: $avgRating, Feedback Count: $feedbackCount, Consciousness data: " . json_encode($consciousness) . ", Recent entries: " . json_encode(array_slice($entries, 0, 10)),
                0.7, 500
            );

            editOriginal($appId, $token, '', [embed("💡 Learning Insights — $username", $analysis ?: 'Not enough data for insights yet. Use `/learn feedback` to provide data.', 0x3498DB, [
                field('Avg Rating', $avgRating ? "$avgRating/10" : 'N/A', true),
                field('Feedback Given', (string)$feedbackCount, true),
                field('Data Points', (string)count($entries), true),
            ], [
                'footer' => ['text' => 'Insights improve with more feedback'],
            ])], [actionRow(
                btn(2, '📝 New Feedback', 'learn_feedback'),
                btn(2, '🧪 Experiments', 'learn_experiments'),
                btn(1, '📈 Performance', 'learn_performance')
            )]);
            awardXP($userId, 3);
            break;

        case 'experiments':
            $action = $opts['action'] ?? 'list';

            if ($action === 'create') {
                $name = $opts['name'] ?? 'Unnamed Experiment';
                $va = $opts['variant_a'] ?? 'Option A';
                $vb = $opts['variant_b'] ?? 'Option B';

                $stmt = $db->prepare("INSERT INTO discord_experiments (discord_id, name, variant_a, variant_b) VALUES (?, ?, ?, ?)");
                $stmt->execute([$userId, $name, $va, $vb]);
                $expId = $db->lastInsertId();

                respond(null, [embed("🧪 Experiment Created", "**#{$expId}: $name**\n\n🅰️ **Variant A:** $va\n🅱️ **Variant B:** $vb\n\nVote below!", 0x9B59B6)], [actionRow(
                    btn(1, '🅰️ Vote A', "experiment_vote_{$expId}_a"),
                    btn(1, '🅱️ Vote B', "experiment_vote_{$expId}_b"),
                    btn(2, '📊 Results', "experiment_results_{$expId}")
                )]);
                awardXP($userId, 5);
            } else {
                // List experiments
                $stmt = $db->prepare("SELECT id, name, variant_a, variant_b, votes_a, votes_b, status, created_at FROM discord_experiments WHERE discord_id = ? ORDER BY created_at DESC LIMIT 5");
                $stmt->execute([$userId]);
                $exps = $stmt->fetchAll();

                if (empty($exps)) {
                    respond(null, [embed("🧪 A/B Experiments", "No experiments yet. Create one with:\n`/learn experiments create`", 0x9B59B6)]);
                    return;
                }

                $lines = [];
                foreach ($exps as $e) {
                    $total = $e['votes_a'] + $e['votes_b'];
                    $status = $e['status'] === 'active' ? '🟢' : '🔴';
                    $winner = $e['votes_a'] > $e['votes_b'] ? 'A' : ($e['votes_b'] > $e['votes_a'] ? 'B' : 'Tied');
                    $lines[] = "$status **#{$e['id']}: {$e['name']}**\n🅰️ {$e['votes_a']} vs 🅱️ {$e['votes_b']} votes · Winner: $winner";
                }

                respond(null, [embed("🧪 A/B Experiments", implode("\n\n", $lines), 0x9B59B6, [
                    field('Total Experiments', (string)count($exps), true),
                ], [
                    'footer' => ['text' => 'A/B testing helps optimize everything'],
                ])]);
            }
            break;

        case 'patterns':
            deferResponse();
            $appId = getenv('DISCORD_APP_ID') ?: '';
            $token = $data['token'] ?? '';

            // Analyze command usage patterns
            $stmt = $db->prepare("SELECT type, content, created_at FROM discord_learning WHERE discord_id = ? ORDER BY created_at DESC LIMIT 50");
            $stmt->execute([$userId]);
            $entries = $stmt->fetchAll();

            // Time patterns
            $hourCounts = array_fill(0, 24, 0);
            $dayCounts = array_fill(0, 7, 0);
            foreach ($entries as $e) {
                $hourCounts[(int)date('G', strtotime($e['created_at']))]++;
                $dayCounts[(int)date('w', strtotime($e['created_at']))]++;
            }

            $peakHour = array_search(max($hourCounts), $hourCounts);
            $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            $peakDay = $days[array_search(max($dayCounts), $dayCounts)];

            $patternAnalysis = callGroq(
                "Analyze these usage patterns and give 3-4 brief insights about the user's behavior:\n- Peak hour: {$peakHour}:00\n- Peak day: $peakDay\n- Total data points: " . count($entries),
                "Hourly distribution: " . json_encode($hourCounts) . "\nDaily: " . json_encode($dayCounts),
                0.7, 300
            );

            editOriginal($appId, $token, '', [embed("🔍 Usage Patterns — $username", $patternAnalysis ?: "**Peak Hour:** {$peakHour}:00\n**Peak Day:** $peakDay", 0xF39C12, [
                field('Peak Hour', "{$peakHour}:00", true),
                field('Peak Day', $peakDay, true),
                field('Data Points', (string)count($entries), true),
            ], [
                'footer' => ['text' => 'Patterns emerge from consistent usage'],
            ])], [actionRow(
                btn(2, '💡 Insights', 'learn_insights'),
                btn(2, '📈 Performance', 'learn_performance'),
                btn(2, '🧪 Experiments', 'learn_experiments')
            )]);
            awardXP($userId, 3);
            break;

        case 'performance':
            $user = getOrCreateUser($userId, $username);

            // Aggregate stats
            $stmt = $db->prepare("SELECT COUNT(*) FROM discord_learning WHERE discord_id = ?");
            $stmt->execute([$userId]);
            $totalLearning = (int)$stmt->fetchColumn();

            $stmt = $db->prepare("SELECT AVG(rating) FROM discord_learning WHERE discord_id = ? AND type = 'feedback' AND rating > 0");
            $stmt->execute([$userId]);
            $avgRating = round((float)$stmt->fetchColumn(), 1);

            $stmt = $db->prepare("SELECT COUNT(*) FROM discord_experiments WHERE discord_id = ?");
            $stmt->execute([$userId]);
            $totalExperiments = (int)$stmt->fetchColumn();

            $stmt = $db->prepare("SELECT COUNT(*) FROM discord_consciousness WHERE discord_id = ?");
            $stmt->execute([$userId]);
            $totalConsciousness = (int)$stmt->fetchColumn();

            $totalDataPoints = $totalLearning + $totalConsciousness;
            $performanceScore = min(100, round(
                min(25, $totalDataPoints * 0.5) +
                min(25, $avgRating * 2.5) +
                min(20, (int)$user['level'] * 4) +
                min(15, $totalExperiments * 5) +
                min(15, (int)($user['games_played'] ?? 0) * 0.5)
            ));

            $performanceBar = str_repeat('█', (int)($performanceScore / 10)) . str_repeat('░', 10 - (int)($performanceScore / 10));

            $grade = match(true) {
                $performanceScore >= 90 => 'S+',
                $performanceScore >= 80 => 'S',
                $performanceScore >= 70 => 'A',
                $performanceScore >= 60 => 'B',
                $performanceScore >= 50 => 'C',
                $performanceScore >= 40 => 'D',
                default => 'F',
            };

            respond(null, [embed("📈 Performance Dashboard — $username", "**Overall Score:** [{$performanceBar}] **{$performanceScore}%**\n**Grade:** $grade", 0x2ECC71, [
                field('Avg Rating', $avgRating ? "$avgRating/10" : 'N/A', true),
                field('Data Points', (string)$totalDataPoints, true),
                field('Level', (string)$user['level'], true),
                field('Experiments', (string)$totalExperiments, true),
                field('Learning Entries', (string)$totalLearning, true),
                field('Games Played', (string)($user['games_played'] ?? 0), true),
            ], [
                'footer' => ['text' => "Grade: $grade • Score: $performanceScore%"],
            ])], [actionRow(
                btn(2, '💡 Insights', 'learn_insights'),
                btn(2, '🔍 Patterns', 'learn_patterns'),
                btn(2, '🧪 Experiments', 'learn_experiments'),
                btn(1, '📝 Feedback', 'learn_feedback')
            )]);
            break;

        default:
            respondEphemeral("Unknown subcommand. Try `/learn feedback`, `/learn insights`, `/learn experiments`, `/learn patterns`, or `/learn performance`.");
    }
}
