<?php
/**
 * GoSiteMe Blog RSS Feed
 * /feed/rss.php → https://gositeme.com/feed/rss
 */
header('Content-Type: application/rss+xml; charset=utf-8');
header('Cache-Control: public, max-age=3600');

$articlesDir = __DIR__ . '/../articles';

$articleMeta = [
    'getting-started-with-alfred' => ['title' => 'Getting Started with Alfred AI', 'desc' => 'Your complete guide to setting up and using Alfred — from first login to 1,220+ tools.', 'category' => 'Getting Started', 'date' => '2026-03-01'],
    '875-tools-complete-guide' => ['title' => '1,220+ Tools: The Complete Guide', 'desc' => 'Every tool category explained — file management, WordPress, databases, AI media, security, and more.', 'category' => 'Deep Dive', 'date' => '2026-03-01'],
    'alfred-for-students' => ['title' => 'Alfred AI for Students', 'desc' => 'How students use Alfred for essays, research, coding assignments, and building portfolios.', 'category' => 'Use Cases', 'date' => '2026-03-02'],
    'voice-first-ai-future' => ['title' => 'Voice-First AI: The Future is Now', 'desc' => 'Why voice interfaces are replacing dashboards.', 'category' => 'Industry', 'date' => '2026-03-02'],
    'alfred-legal-aid-canada' => ['title' => 'Alfred Legal Aid for Canada', 'desc' => 'The Jailhouse Lawyer program — AI-powered legal assistance for Canadian inmates.', 'category' => 'Social Impact', 'date' => '2026-03-02'],
    'fleet-management-guide' => ['title' => 'Fleet Management Guide', 'desc' => 'Deploy and manage multiple AI agents at enterprise scale.', 'category' => 'Enterprise', 'date' => '2026-03-02'],
    'alfred-vs-chatgpt' => ['title' => 'Alfred vs ChatGPT: Full Comparison', 'desc' => 'What makes Alfred different from ChatGPT? Tools, hosting, voice, and more.', 'category' => 'Comparison', 'date' => '2026-03-03'],
    'small-business-ai-tools' => ['title' => 'AI Tools for Small Business', 'desc' => 'How small businesses save time and money with AI tools.', 'category' => 'Business', 'date' => '2026-03-03'],
    'building-ai-agents' => ['title' => 'Building AI Agents', 'desc' => 'Create custom AI voice agents for your business.', 'category' => 'Tutorial', 'date' => '2026-03-03'],
    'ai-conference-rooms' => ['title' => 'AI Conference Rooms', 'desc' => 'Multi-participant voice rooms with Alfred AI.', 'category' => 'Features', 'date' => '2026-03-03'],
    'ai-voice-agent-setup-guide' => ['title' => 'AI Voice Agent Setup Guide', 'desc' => 'Step-by-step guide to AI voice agents.', 'category' => 'Tutorial', 'date' => '2026-03-04'],
    'ai-receptionist-cost' => ['title' => 'AI Receptionist: Cost Breakdown', 'desc' => 'AI vs human receptionist ROI analysis.', 'category' => 'Business', 'date' => '2026-03-04'],
    'small-business-ai-tools-2025' => ['title' => 'Small Business AI Tools in 2025', 'desc' => 'The definitive guide to AI tools for small businesses.', 'category' => 'Business', 'date' => '2026-03-04'],
    'ai-phone-answering-service-2025' => ['title' => 'AI Phone Answering Service 2025', 'desc' => 'AI phone answering vs traditional services.', 'category' => 'Industry', 'date' => '2026-03-04'],
    'reduce-customer-support-costs' => ['title' => 'Reduce Customer Support Costs', 'desc' => 'How AI voice agents cut support costs by 60%.', 'category' => 'Business', 'date' => '2026-03-04'],
    'chatbot-vs-voice-ai' => ['title' => 'Chatbot vs Voice AI', 'desc' => 'Text chatbots vs conversational voice AI.', 'category' => 'Comparison', 'date' => '2026-03-04'],
];

$articles = [];
if (is_dir($articlesDir)) {
    foreach (glob("$articlesDir/*.php") as $file) {
        $slug = basename($file, '.php');
        if ($slug === 'index' || $slug === 'article-template.inc') continue;
        $meta = $articleMeta[$slug] ?? [
            'title' => ucwords(str_replace('-', ' ', $slug)),
            'desc' => '',
            'category' => 'Article',
            'date' => date('Y-m-d', filemtime($file)),
        ];
        $articles[] = array_merge($meta, ['slug' => $slug]);
    }
    foreach (glob("$articlesDir/*/index.php") as $file) {
        $slug = basename(dirname($file));
        if (isset($articleMeta[$slug]) && !in_array($slug, array_column($articles, 'slug'))) {
            $articles[] = array_merge($articleMeta[$slug], ['slug' => $slug]);
        }
    }
}
usort($articles, fn($a, $b) => strcmp($b['date'], $a['date']));

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
    <title>GoSiteMe Blog</title>
    <link>https://gositeme.com/blog</link>
    <description>Guides, tutorials, and insights on AI hosting, Alfred AI, voice agents, and web development.</description>
    <language>en-us</language>
    <lastBuildDate><?php echo date('r'); ?></lastBuildDate>
    <atom:link href="https://gositeme.com/feed/rss" rel="self" type="application/rss+xml"/>
    <image>
        <url>https://gositeme.com/brand/logo.png</url>
        <title>GoSiteMe Blog</title>
        <link>https://gositeme.com/blog</link>
    </image>
<?php foreach (array_slice($articles, 0, 50) as $article): ?>
    <item>
        <title><?php echo htmlspecialchars($article['title']); ?></title>
        <link>https://gositeme.com/articles/<?php echo htmlspecialchars($article['slug']); ?></link>
        <description><?php echo htmlspecialchars($article['desc']); ?></description>
        <category><?php echo htmlspecialchars($article['category']); ?></category>
        <pubDate><?php echo date('r', strtotime($article['date'])); ?></pubDate>
        <guid isPermaLink="true">https://gositeme.com/articles/<?php echo htmlspecialchars($article['slug']); ?></guid>
    </item>
<?php endforeach; ?>
</channel>
</rss>
