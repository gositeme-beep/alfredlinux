<?php
/**
 * GoSiteMe Discord Bot — News & Intelligence Module
 * ══════════════════════════════════════════════════
 * /news   — Latest tech/crypto/security news (RSS feeds)
 * /legal  — Canadian case law search (CanLII)
 * /digest — AI-generated daily news digest
 *
 * UNIQUE: Real RSS feeds + legal research + AI-summarized dailies.
 */

function handleNews(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $category = 'tech';
    foreach ($opts as $o) { if ($o['name'] === 'category') $category = $o['value']; }

    $userId = $data['member']['user']['id'] ?? '0';
    $appId = $data['application_id'] ?? '';
    $token = $data['token'] ?? '';
    $channelId = $data['channel_id'] ?? '';

    deferResponse();

    // Feed URLs by category
    $feeds = [
        'tech'     => 'https://feeds.feedburner.com/TechCrunch',
        'crypto'   => 'https://cointelegraph.com/rss',
        'security' => 'https://feeds.feedburner.com/TheHackersNews',
        'ai'       => 'https://news.google.com/rss/search?q=artificial+intelligence&hl=en-US&gl=US&ceid=US:en',
        'gaming'   => 'https://kotaku.com/rss',
        'science'  => 'https://rss.nytimes.com/services/xml/rss/nyt/Science.xml',
        'world'    => 'https://rss.nytimes.com/services/xml/rss/nyt/World.xml',
        'business' => 'https://feeds.bloomberg.com/markets/news.rss',
    ];

    $feedUrl = $feeds[$category] ?? $feeds['tech'];

    // Parse RSS
    $ctx = stream_context_create([
        'http' => ['timeout' => 10, 'user_agent' => 'GoSiteMe-Bot/1.0'],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);

    $xml = @file_get_contents($feedUrl, false, $ctx);
    $items = [];

    if ($xml) {
        libxml_use_internal_errors(true);
        $feed = @simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOENT);
        if ($feed) {
            // RSS 2.0
            if (isset($feed->channel->item)) {
                foreach ($feed->channel->item as $item) {
                    $items[] = [
                        'title' => (string)($item->title ?? 'Untitled'),
                        'summary' => strip_tags((string)($item->description ?? '')),
                        'url' => (string)($item->link ?? ''),
                        'date' => !empty((string)$item->pubDate) ? date('M j', strtotime((string)$item->pubDate)) : '',
                    ];
                    if (count($items) >= 8) break;
                }
            }
            // Atom
            elseif (isset($feed->entry)) {
                foreach ($feed->entry as $entry) {
                    $link = '';
                    foreach ($entry->link as $l) {
                        if ((string)$l['rel'] === 'alternate' || empty($link)) $link = (string)$l['href'];
                    }
                    $items[] = [
                        'title' => (string)($entry->title ?? 'Untitled'),
                        'summary' => strip_tags((string)($entry->summary ?? $entry->content ?? '')),
                        'url' => $link,
                        'date' => !empty((string)$entry->published) ? date('M j', strtotime((string)$entry->published)) : '',
                    ];
                    if (count($items) >= 8) break;
                }
            }
        }
    }

    if (empty($items)) {
        followUp($appId, $token, "❌ Could not fetch news for `$category`. Try again later.");
        return;
    }

    $catEmojis = [
        'tech' => '💻', 'crypto' => '₿', 'security' => '🔒', 'ai' => '🤖',
        'gaming' => '🎮', 'science' => '🔬', 'world' => '🌍', 'business' => '📊',
    ];
    $emoji = $catEmojis[$category] ?? '📰';

    $desc = '';
    foreach ($items as $i => $item) {
        $num = $i + 1;
        $title = truncate($item['title'], 80);
        $summary = truncate($item['summary'], 100);
        $dateStr = $item['date'] ? " *({$item['date']})*" : '';
        $url = $item['url'];
        $desc .= "**$num.** [$title]($url)$dateStr\n";
        if ($summary) $desc .= "   $summary\n\n";
    }

    followUp($appId, $token, '', [embed(
        "$emoji " . ucfirst($category) . " News",
        $desc,
        0x1DA1F2,
        [],
        ['footer' => ['text' => 'Live RSS • GoSiteMe News']]
    )], [actionRow(
        btn(2, '🔄 Refresh', "news_refresh_$category"),
        btn(2, '📰 Digest', 'news_digest'),
        btn(2, '🔀 Random', 'news_random')
    )]);

    awardXP($userId, 3, $appId, $token, $channelId);
}


function handleLegal(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $query = '';
    foreach ($opts as $o) { if ($o['name'] === 'query') $query = $o['value']; }

    $userId = $data['member']['user']['id'] ?? '0';
    $appId = $data['application_id'] ?? '';
    $token = $data['token'] ?? '';
    $channelId = $data['channel_id'] ?? '';

    if (!$query) { respond("⚖️ Usage: `/legal query:tenant rights in Quebec`"); return; }

    deferResponse();

    // Search CanLII
    $searchUrl = 'https://www.canlii.org/en/search/search.do?' . http_build_query([
        'text' => $query,
        'type' => 'decision',
        'resultCount' => 5,
    ]);

    // Since CanLII doesn't have a public API, use AI to provide legal guidance
    $result = callGroq(
        "You are a legal research assistant specializing in Canadian and Quebec law. "
        . "The user is asking about a legal topic. Provide:\n\n"
        . "⚖️ **Legal Overview:** Brief explanation of the law/concept\n"
        . "📋 **Key Points:** 3-5 bullet points of important information\n"
        . "📖 **Relevant Laws:** Cite specific statutes, codes, or acts\n"
        . "🏛️ **Jurisdiction:** Which courts/bodies handle this\n"
        . "⚠️ **Disclaimer:** Always include that this is not legal advice\n\n"
        . "Be accurate. Cite real Canadian/Quebec laws when possible (e.g., Civil Code of Quebec, "
        . "Criminal Code RSC, Charter of Rights). Use Discord markdown.",
        "Legal research query: $query",
        0.5, 1000
    );

    followUp($appId, $token, '', [embed(
        "⚖️ Legal Research",
        "**Query:** $query\n\n$result",
        0x8E44AD,
        [],
        ['footer' => ['text' => '⚠️ Not legal advice • GoSiteMe Legal Research']]
    )], [actionRow(
        btn(5, '📚 CanLII Search', $searchUrl),
        btn(2, '📝 Draft Motion', 'legal_motion_' . substr(md5($query), 0, 8)),
        btn(2, '🔍 More Detail', 'legal_detail_' . substr(md5($query), 0, 8))
    )]);

    awardXP($userId, 8, $appId, $token, $channelId);
}


function handleDigest(array $data): void {
    $userId = $data['member']['user']['id'] ?? '0';
    $appId = $data['application_id'] ?? '';
    $token = $data['token'] ?? '';
    $channelId = $data['channel_id'] ?? '';

    deferResponse();

    // Fetch headlines from multiple sources
    $sources = [
        'https://feeds.feedburner.com/TechCrunch',
        'https://cointelegraph.com/rss',
        'https://rss.nytimes.com/services/xml/rss/nyt/Science.xml',
    ];

    $allHeadlines = [];
    $ctx = stream_context_create([
        'http' => ['timeout' => 8, 'user_agent' => 'GoSiteMe-Bot/1.0'],
        'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
    ]);

    foreach ($sources as $feedUrl) {
        $xml = @file_get_contents($feedUrl, false, $ctx);
        if (!$xml) continue;
        libxml_use_internal_errors(true);
        $feed = @simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOENT);
        if (!$feed || !isset($feed->channel->item)) continue;

        $count = 0;
        foreach ($feed->channel->item as $item) {
            $allHeadlines[] = (string)($item->title ?? '');
            if (++$count >= 5) break;
        }
    }

    if (empty($allHeadlines)) {
        followUp($appId, $token, "❌ Could not fetch news. Try again later.");
        return;
    }

    $headlinesList = implode("\n", $allHeadlines);

    $result = callGroq(
        "You are a news anchor. Create a brief daily digest from these headlines. Format:\n\n"
        . "📰 **Today's Top Stories** — " . date('F j, Y') . "\n\n"
        . "Summarize the 5 most important stories in 2-3 sentences each. Group by category "
        . "(Tech, Finance, Science, etc.). Add relevant emoji. End with a \"🔮 What to Watch\" "
        . "section with 2-3 predictions. Use Discord markdown.",
        "Headlines:\n$headlinesList",
        0.7, 1000
    );

    followUp($appId, $token, '', [embed(
        "📰 Daily Digest — " . date('M j, Y'),
        $result,
        0x1DA1F2,
        [],
        ['footer' => ['text' => 'AI-curated • GoSiteMe Daily Digest']]
    )], [actionRow(
        btn(2, '💻 Tech News', 'news_cat_tech'),
        btn(2, '₿ Crypto News', 'news_cat_crypto'),
        btn(2, '🔬 Science News', 'news_cat_science')
    )]);

    awardXP($userId, 5, $appId, $token, $channelId);
}