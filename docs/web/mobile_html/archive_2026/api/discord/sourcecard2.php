<?php
/**
 * GoSiteMe Discord Bot — Source Card Identity Module
 * ══════════════════════════════════════════════════
 * /sourcecard (view|contribute|reputation|tier|lineage)
 * Sovereign identity, reputation, and contribution tracking.
 */

function handleSourcecard($data): void {
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

    $db->exec("CREATE TABLE IF NOT EXISTS discord_source_cards (
        id INT AUTO_INCREMENT PRIMARY KEY,
        discord_id VARCHAR(32) UNIQUE NOT NULL,
        source_id VARCHAR(64) NOT NULL,
        display_name VARCHAR(100),
        bio TEXT,
        skills JSON,
        reputation INT DEFAULT 0,
        contributions INT DEFAULT 0,
        tier VARCHAR(30) DEFAULT 'Observer',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_tier (tier),
        INDEX idx_reputation (reputation)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS discord_contributions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        discord_id VARCHAR(32) NOT NULL,
        type VARCHAR(50) NOT NULL,
        title VARCHAR(200) NOT NULL,
        description TEXT,
        impact INT DEFAULT 1,
        verified TINYINT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (discord_id),
        INDEX idx_type (type)
    )");

    // Helper: calculate tier from reputation
    $calcTier = function(int $rep): string {
        return match(true) {
            $rep >= 10000 => 'Architect',
            $rep >= 5000  => 'Visionary',
            $rep >= 2500  => 'Pioneer',
            $rep >= 1000  => 'Builder',
            $rep >= 500   => 'Contributor',
            $rep >= 100   => 'Explorer',
            $rep >= 25    => 'Apprentice',
            default       => 'Observer',
        };
    };

    $tierEmoji = function(string $tier): string {
        return match($tier) {
            'Architect'   => '👑',
            'Visionary'   => '🌟',
            'Pioneer'     => '🚀',
            'Builder'     => '🔨',
            'Contributor' => '⚡',
            'Explorer'    => '🔍',
            'Apprentice'  => '📘',
            default       => '👁️',
        };
    };

    // Get or create card
    $getCard = function($db, $userId, $username) use ($calcTier) {
        $stmt = $db->prepare("SELECT * FROM discord_source_cards WHERE discord_id = ?");
        $stmt->execute([$userId]);
        $card = $stmt->fetch();
        if (!$card) {
            $sourceId = 'SC-' . strtoupper(substr(hash('sha256', $userId . time()), 0, 12));
            $db->prepare("INSERT INTO discord_source_cards (discord_id, source_id, display_name, skills) VALUES (?, ?, ?, '[]')")->execute([$userId, $sourceId, $username]);
            $stmt->execute([$userId]);
            $card = $stmt->fetch();
        }
        return $card;
    };

    switch ($sub) {
        case 'view':
            $targetUser = $opts['user'] ?? null;
            $targetId = $targetUser ?: $userId;
            $card = $getCard($db, $targetId, $username);

            $emoji = $tierEmoji($card['tier']);
            $skills = json_decode($card['skills'] ?: '[]', true);
            $skillStr = !empty($skills) ? implode(' · ', $skills) : 'None set';

            // Contribution count
            $stmt = $db->prepare("SELECT COUNT(*), SUM(impact) FROM discord_contributions WHERE discord_id = ?");
            $stmt->execute([$targetId]);
            $contribRow = $stmt->fetch();
            $contribCount = (int)$contribRow[0];
            $impactTotal = (int)$contribRow[1];

            // Reputation bar
            $nextTierRep = match($card['tier']) {
                'Observer' => 25, 'Apprentice' => 100, 'Explorer' => 500,
                'Contributor' => 1000, 'Builder' => 2500, 'Pioneer' => 5000,
                'Visionary' => 10000, default => 99999,
            };
            $progress = min(100, round(($card['reputation'] / max(1, $nextTierRep)) * 100));
            $progBar = str_repeat('█', (int)($progress / 10)) . str_repeat('░', 10 - (int)($progress / 10));

            respond(null, [embed("$emoji Source Card — {$card['display_name']}", ($card['bio'] ?: '_No bio set_') . "\n\n**Source ID:** `{$card['source_id']}`", 0xE67E22, [
                field('Tier', "$emoji {$card['tier']}", true),
                field('Reputation', "**{$card['reputation']}** RP", true),
                field('Contributions', (string)$contribCount, true),
                field('Skills', $skillStr, false),
                field('Progress', "[$progBar] {$progress}% → Next: $nextTierRep RP", false),
                field('Impact Score', (string)$impactTotal, true),
                field('Level', (string)$user['level'], true),
            ], [
                'footer' => ['text' => "Source Card • Sovereign Identity on GoSiteMe"],
            ])], [actionRow(
                btn(1, '🎁 Contribute', 'sourcecard_contribute_prompt'),
                btn(2, '📊 Reputation', 'sourcecard_reputation'),
                btn(2, '🏆 Tier', 'sourcecard_tier'),
                btn(2, '📜 Lineage', 'sourcecard_lineage')
            )]);
            break;

        case 'contribute':
            $type = $opts['type'] ?? 'content';
            $title = $opts['title'] ?? 'Contribution';
            $description = $opts['description'] ?? '';
            $validTypes = ['content', 'tool', 'agent', 'code', 'bug_fix', 'community', 'documentation'];
            if (!in_array($type, $validTypes)) $type = 'content';

            $card = $getCard($db, $userId, $username);

            // Impact based on type
            $impact = match($type) {
                'agent' => 5, 'tool' => 4, 'code' => 4, 'bug_fix' => 3,
                'documentation' => 2, 'community' => 2, default => 1,
            };

            $stmt = $db->prepare("INSERT INTO discord_contributions (discord_id, type, title, description, impact) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $type, $title, $description, $impact]);

            // Update reputation
            $repGain = $impact * 5;
            $newRep = $card['reputation'] + $repGain;
            $newTier = $calcTier($newRep);
            $db->prepare("UPDATE discord_source_cards SET reputation = reputation + ?, contributions = contributions + 1, tier = ? WHERE discord_id = ?")->execute([$repGain, $newTier, $userId]);

            $tierChanged = $newTier !== $card['tier'];
            $typeEmoji = match($type) {
                'agent' => '🤖', 'tool' => '🔧', 'code' => '💻', 'bug_fix' => '🐛',
                'documentation' => '📄', 'community' => '👥', default => '📝',
            };

            $desc = "$typeEmoji **$title**\n" . ($description ? "_${description}_\n" : '') . "\n**Impact:** $impact · **RP Gained:** +$repGain";
            if ($tierChanged) {
                $desc .= "\n\n🎉 **TIER UP!** {$card['tier']} → $newTier!";
            }

            respond(null, [embed("🎁 Contribution Logged", $desc, 0x2ECC71, [
                field('Type', ucfirst($type), true),
                field('Reputation', "$newRep RP", true),
                field('Tier', $tierEmoji($newTier) . " $newTier", true),
            ], [
                'footer' => ['text' => "Total contributions: " . ($card['contributions'] + 1)],
            ])], [actionRow(
                btn(2, '👁️ View Card', 'sourcecard_view'),
                btn(2, '📊 Reputation', 'sourcecard_reputation'),
                btn(2, '📜 Lineage', 'sourcecard_lineage')
            )]);
            awardXP($userId, $impact * 3);
            break;

        case 'reputation':
            $card = $getCard($db, $userId, $username);

            // Breakdown
            $stmt = $db->prepare("SELECT type, COUNT(*) as cnt, SUM(impact) as total_impact FROM discord_contributions WHERE discord_id = ? GROUP BY type ORDER BY total_impact DESC");
            $stmt->execute([$userId]);
            $breakdown = $stmt->fetchAll();

            $lines = [];
            foreach ($breakdown as $b) {
                $typeEmoji = match($b['type']) {
                    'agent' => '🤖', 'tool' => '🔧', 'code' => '💻', 'bug_fix' => '🐛',
                    'documentation' => '📄', 'community' => '👥', default => '📝',
                };
                $lines[] = "$typeEmoji **" . ucfirst($b['type']) . "**: {$b['cnt']} contributions · Impact: {$b['total_impact']}";
            }

            if (empty($lines)) $lines[] = 'No contributions yet. Start with `/sourcecard contribute`!';

            respond(null, [embed("📊 Reputation — $username", implode("\n", $lines), 0x3498DB, [
                field('Total RP', (string)$card['reputation'], true),
                field('Tier', $tierEmoji($card['tier']) . " " . $card['tier'], true),
                field('Contributions', (string)$card['contributions'], true),
            ], [
                'footer' => ['text' => 'Reputation grows with each contribution'],
            ])], [actionRow(
                btn(2, '👁️ View Card', 'sourcecard_view'),
                btn(1, '🎁 Contribute', 'sourcecard_contribute_prompt'),
                btn(2, '🏆 Tier', 'sourcecard_tier')
            )]);
            break;

        case 'tier':
            $card = $getCard($db, $userId, $username);

            $tiers = [
                ['Observer', 0, '👁️'], ['Apprentice', 25, '📘'], ['Explorer', 100, '🔍'],
                ['Contributor', 500, '⚡'], ['Builder', 1000, '🔨'], ['Pioneer', 2500, '🚀'],
                ['Visionary', 5000, '🌟'], ['Architect', 10000, '👑'],
            ];

            $lines = [];
            foreach ($tiers as $t) {
                $current = $card['tier'] === $t[0];
                $achieved = $card['reputation'] >= $t[1];
                $marker = $current ? ' ← **YOU**' : ($achieved ? ' ✅' : '');
                $lines[] = ($achieved ? $t[2] : '⬛') . " **{$t[0]}** — {$t[1]}+ RP$marker";
            }

            respond(null, [embed("🏆 Tier Progression — $username", implode("\n", $lines), 0xE67E22, [
                field('Current Tier', $tierEmoji($card['tier']) . " " . $card['tier'], true),
                field('Reputation', (string)$card['reputation'] . " RP", true),
            ], [
                'footer' => ['text' => 'Contribute to ascend the ranks'],
            ])], [actionRow(
                btn(2, '👁️ View Card', 'sourcecard_view'),
                btn(1, '🎁 Contribute', 'sourcecard_contribute_prompt'),
                btn(2, '📊 Reputation', 'sourcecard_reputation')
            )]);
            break;

        case 'lineage':
            $stmt = $db->prepare("SELECT type, title, description, impact, verified, created_at FROM discord_contributions WHERE discord_id = ? ORDER BY created_at DESC LIMIT 15");
            $stmt->execute([$userId]);
            $contribs = $stmt->fetchAll();

            if (empty($contribs)) {
                respond(null, [embed("📜 Contribution Lineage", "No contributions recorded yet.\nStart your legacy with `/sourcecard contribute`!", 0x95A5A6)]);
                return;
            }

            $lines = [];
            foreach ($contribs as $c) {
                $typeEmoji = match($c['type']) {
                    'agent' => '🤖', 'tool' => '🔧', 'code' => '💻', 'bug_fix' => '🐛',
                    'documentation' => '📄', 'community' => '👥', default => '📝',
                };
                $verified = $c['verified'] ? ' ✅' : '';
                $date = date('M j', strtotime($c['created_at']));
                $lines[] = "$typeEmoji **{$c['title']}**$verified\n   Impact: {$c['impact']} · $date";
            }

            respond(null, [embed("📜 Contribution Lineage — $username", implode("\n\n", $lines), 0x9B59B6, [
                field('Total Shown', (string)count($contribs), true),
            ], [
                'footer' => ['text' => 'Your legacy of contributions'],
            ])], [actionRow(
                btn(2, '👁️ View Card', 'sourcecard_view'),
                btn(1, '🎁 Contribute', 'sourcecard_contribute_prompt'),
                btn(2, '📊 Reputation', 'sourcecard_reputation')
            )]);
            break;

        default:
            respondEphemeral("Unknown subcommand. Try `/sourcecard view`, `/sourcecard contribute`, `/sourcecard reputation`, `/sourcecard tier`, or `/sourcecard lineage`.");
    }
}
