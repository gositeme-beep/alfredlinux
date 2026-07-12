<?php
/**
 * GoSiteMe Discord Bot — Feeds & Information Module
 * ══════════════════════════════════════════════════
 * /feeds (subscribe|list|digest|news|unsubscribe)
 * RSS, news digests, and AI-curated information feeds.
 */

function handleFeeds($data): void {
    $sub = $data['data']['options'][0]['name'] ?? 'list';
    $opts = [];
    foreach (($data['data']['options'][0]['options'] ?? []) as $o) {
        $opts[$o['name']] = $o['value'];
    }

    $userId   = $data['member']['user']['id'] ?? ($data['user']['id'] ?? '');
    $username = $data['member']['user']['username'] ?? ($data['user']['username'] ?? 'User');
    $db       = getDiscordDB();
    if (!$db) { respond('❌ Database unavailable.'); return; }
    getOrCreateUser($userId, $username);

    $db->exec("CREATE TABLE IF NOT EXISTS discord_feeds (
        id INT AUTO_INCREMENT PRIMARY KEY,
        discord_id VARCHAR(32) NOT NULL,
        name VARCHAR(200) NOT NULL,
        url VARCHAR(500) NOT NULL,
        category VARCHAR(50) DEFAULT 'general',
        last_fetched TIMESTAMP NULL,
        status VARCHAR(20) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (discord_id),
        INDEX idx_status (status)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS discord_feed_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        feed_id INT NOT NULL,
        title VARCHAR(500) NOT NULL,
        url VARCHAR(500),
        summary TEXT,
        fetched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_feed (feed_id)
    )");

    switch ($sub) {
        case 'subscribe':
            $url = $opts['url'] ?? '';
            $name = $opts['name'] ?? 'My Feed';
            $category = $opts['category'] ?? 'general';

            if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
                respondEphemeral('❌ Please provide a valid URL.');
                return;
            }

            // Limit feeds per user
            $stmt = $db->prepare("SELECT COUNT(*) FROM discord_feeds WHERE discord_id = ? AND status = 'active'");
            $stmt->execute([$userId]);
            if ((int)$stmt->fetchColumn() >= 10) {
                respondEphemeral('❌ Maximum 10 active feeds. Unsubscribe from one first.');
                return;
            }

            // Check for duplicates
            $stmt = $db->prepare("SELECT id FROM discord_feeds WHERE discord_id = ? AND url = ? AND status = 'active'");
            $stmt->execute([$userId, $url]);
            if ($stmt->fetch()) {
                respondEphemeral('⚠️ You are already subscribed to this feed.');
                return;
            }

            $stmt = $db->prepare("INSERT INTO discord_feeds (discord_id, name, url, category) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userId, $name, $url, $category]);
            $feedId = $db->lastInsertId();

            respond(null, [embed("📡 Feed Subscribed", "**$name**\n🔗 `$url`\n📂 Category: $category\n🆔 Feed #$feedId", 0x2ECC71, [], [
                'footer' => ['text' => 'Use /feeds digest to get AI-curated summaries'],
            ])], [actionRow(
                btn(2, '📋 My Feeds', 'feeds_list'),
                btn(2, '📰 Digest', 'feeds_digest'),
                btn(5, '🔗 Open Feed', $url)
            )]);
            awardXP($userId, 3);
            break;

        case 'list':
            $stmt = $db->prepare("SELECT id, name, url, category, status, created_at FROM discord_feeds WHERE discord_id = ? ORDER BY created_at DESC");
            $stmt->execute([$userId]);
            $feeds = $stmt->fetchAll();

            if (empty($feeds)) {
                respond(null, [embed("📋 Your Feeds", "No feeds yet! Subscribe with `/feeds subscribe`.", 0x95A5A6)], [actionRow(
                    btn(1, '📡 Subscribe', 'feeds_subscribe_prompt'),
                    btn(2, '📰 Quick News', 'feeds_news')
                )]);
                return;
            }

            $lines = [];
            foreach ($feeds as $f) {
                $status = $f['status'] === 'active' ? '🟢' : '🔴';
                $lines[] = "$status **#{$f['id']}: {$f['name']}**\n📂 {$f['category']} · `{$f['url']}`";
            }

            respond(null, [embed("📋 Your Feeds", implode("\n\n", $lines), 0x3498DB, [
                field('Total', (string)count($feeds), true),
                field('Active', (string)count(array_filter($feeds, fn($f) => $f['status'] === 'active')), true),
            ], [
                'footer' => ['text' => 'Use /feeds unsubscribe to remove a feed'],
            ])], [actionRow(
                btn(2, '📰 Digest', 'feeds_digest'),
                btn(2, '🗞️ Quick News', 'feeds_news'),
                btn(3, '🗑️ Manage', 'feeds_manage')
            )]);
            break;

        case 'digest':
            deferResponse();
            $appId = getenv('DISCORD_APP_ID') ?: '';
            $token = $data['token'] ?? '';

            // Get user's feeds
            $stmt = $db->prepare("SELECT id, name, url, category FROM discord_feeds WHERE discord_id = ? AND status = 'active' LIMIT 5");
            $stmt->execute([$userId]);
            $feeds = $stmt->fetchAll();

            $allContent = [];

            if (!empty($feeds)) {
                // Fetch fresh content from feeds using Jina Reader
                foreach ($feeds as $f) {
                    $result = httpGet("https://r.jina.ai/" . $f['url'], 8);
                    if ($result) {
                        $snippet = substr($result, 0, 1000);
                        $allContent[] = "**{$f['name']}** ({$f['category']}):\n$snippet";

                        // Cache item
                        $title = $f['name'] . ' — ' . date('Y-m-d');
                        $stmt2 = $db->prepare("INSERT INTO discord_feed_items (feed_id, title, url, summary) VALUES (?, ?, ?, ?)");
                        $stmt2->execute([$f['id'], $title, $f['url'], substr($result, 0, 500)]);

                        $db->prepare("UPDATE discord_feeds SET last_fetched = NOW() WHERE id = ?")->execute([$f['id']]);
                    }
                }
            }

            if (empty($allContent)) {
                // Fallback: use Jina for trending tech news
                $trending = httpGet("https://s.jina.ai/latest+technology+news", 10);
                if ($trending) {
                    $allContent[] = "**Trending News:**\n" . substr($trending, 0, 2000);
                }
            }

            $digest = callGroq(
                "Create a professional news digest with:\n- 📬 **Digest Title** (creative)\n- 🔹 3-5 key items, each with a 1-2 sentence summary\n- 🎯 **Key Takeaway** (1 sentence)\n- 📊 **Relevance Score** (1-10)\nBe concise and informative. Use emojis for categories.",
                "Content to digest for user $username:\n" . implode("\n---\n", $allContent),
                0.7, 800
            );

            editOriginal($appId, $token, '', [embed("📰 AI Digest — " . date('M j, Y'), $digest ?: 'No content available. Subscribe to feeds first!', 0xE67E22, [
                field('Sources', (string)count($allContent), true),
                field('User', $username, true),
            ], [
                'footer' => ['text' => '🤖 Curated by Alfred AI'],
            ])], [actionRow(
                btn(2, '📋 My Feeds', 'feeds_list'),
                btn(2, '🗞️ Quick News', 'feeds_news'),
                btn(1, '🔄 Refresh', 'feeds_digest')
            )]);
            awardXP($userId, 5);
            break;

        case 'news':
            deferResponse();
            $appId = getenv('DISCORD_APP_ID') ?: '';
            $token = $data['token'] ?? '';

            $category = $opts['category'] ?? 'technology';
            $validCategories = ['technology', 'crypto', 'ai', 'gaming', 'science', 'business', 'world'];
            if (!in_array($category, $validCategories)) $category = 'technology';

            $searchQuery = match($category) {
                'technology' => 'latest technology news today',
                'crypto' => 'cryptocurrency bitcoin ethereum news today',
                'ai' => 'artificial intelligence AI news today',
                'gaming' => 'video games gaming news today',
                'science' => 'science discoveries research news today',
                'business' => 'business finance economy news today',
                'world' => 'world news headlines today',
            };

            $result = httpGet("https://s.jina.ai/" . urlencode($searchQuery), 12);

            if (!$result) {
                editOriginal($appId, $token, '', [embed("📰 News — " . ucfirst($category), "Unable to fetch news. Try again later.", 0xE74C3C)]);
                return;
            }

            $summary = callGroq(
                "Summarize this news content into 5 bullet points. Each bullet:\n- 🔹 **Headline** — 1 sentence summary\nEnd with a one-line **Analysis** of the overall trend.",
                "Category: $category\nContent: " . substr($result, 0, 3000),
                0.7, 600
            );

            $emoji = match($category) {
                'technology' => '💻',
                'crypto' => '₿',
                'ai' => '🤖',
                'gaming' => '🎮',
                'science' => '🔬',
                'business' => '💼',
                'world' => '🌍',
            };

            editOriginal($appId, $token, '', [embed("$emoji News — " . ucfirst($category), $summary ?: substr($result, 0, 2000), 0xE67E22, [], [
                'footer' => ['text' => "Category: $category • Powered by Jina + Groq"],
            ])], [actionRow(
                btn(2, '💻 Tech', 'feeds_news_technology'),
                btn(2, '₿ Crypto', 'feeds_news_crypto'),
                btn(2, '🤖 AI', 'feeds_news_ai'),
                btn(2, '🎮 Gaming', 'feeds_news_gaming'),
                btn(2, '🔬 Science', 'feeds_news_science')
            )]);
            awardXP($userId, 3);
            break;

        case 'unsubscribe':
            $feedId = (int)($opts['feed_id'] ?? 0);

            if ($feedId <= 0) {
                respondEphemeral('❌ Provide a feed ID. Use `/feeds list` to see your feeds.');
                return;
            }

            $stmt = $db->prepare("UPDATE discord_feeds SET status = 'inactive' WHERE id = ? AND discord_id = ?");
            $stmt->execute([$feedId, $userId]);

            if ($stmt->rowCount() > 0) {
                respond(null, [embed("🗑️ Unsubscribed", "Feed #$feedId has been deactivated.", 0xE74C3C)], [actionRow(
                    btn(2, '📋 My Feeds', 'feeds_list'),
                    btn(1, '📡 Subscribe New', 'feeds_subscribe_prompt')
                )]);
            } else {
                respondEphemeral("❌ Feed #$feedId not found or already inactive.");
            }
            break;

        default:
            respondEphemeral("Unknown subcommand. Try `/feeds subscribe`, `/feeds list`, `/feeds digest`, `/feeds news`, or `/feeds unsubscribe`.");
    }
}
