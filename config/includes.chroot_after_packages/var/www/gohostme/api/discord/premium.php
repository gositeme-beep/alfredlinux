<?php
/**
 * GoSiteMe Discord Bot — Premium Module
 * ══════════════════════════════════════
 * /tts        — AI text-to-speech (OpenAI TTS voices)
 * /sms        — Send SMS from Discord via Telnyx
 * /search     — AI-powered web search
 * /screenshot — Website screenshot capture
 * /calc       — Calculator & unit converter
 * /music      — AI music recommendations
 * /deploy     — Deploy a website from Discord (GoSiteMe exclusive)
 */

function handleTts(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $text = ''; $voice = 'alloy';
    foreach ($opts as $o) {
        if ($o['name'] === 'text') $text = $o['value'];
        if ($o['name'] === 'voice') $voice = $o['value'];
    }
    if (!$text) { respond("Enter text to speak! `/tts text:Hello world voice:nova`"); return; }
    if (strlen($text) > 1000) { respond("Text too long! Max 1000 characters."); return; }

    deferResponse();

    $userId = $data['member']['user']['id'] ?? '0';
    $appId = $data['application_id'] ?? '';
    $token = $data['token'] ?? '';
    $channelId = $data['channel_id'] ?? '';
    $globalName = $data['member']['user']['global_name'] ?? $data['member']['user']['username'] ?? 'User';

    $apiKey = getenv('OPENAI_API_KEY') ?: '';

    if ($apiKey) {
        $ch = curl_init('https://api.openai.com/v1/audio/speech');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'model' => 'tts-1',
                'input' => $text,
                'voice' => $voice,
                'response_format' => 'mp3',
            ]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', "Authorization: Bearer $apiKey"],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);
        $audio = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $audio) {
            $filename = 'tts_' . substr(md5($text . $voice . time()), 0, 12) . '.mp3';
            $dir = dirname(dirname(__DIR__)) . '/cache/tts';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            file_put_contents("$dir/$filename", $audio);

            // Send as multipart with audio file attached
            $webhookUrl = "https://discord.com/api/v10/webhooks/$appId/$token";
            $embedData = [embed("🔊 Text-to-Speech", "**Voice:** " . ucfirst($voice) . "\n**Text:** " . truncate($text, 200), 0x5865F2)];
            $payload = json_encode(['embeds' => $embedData]);

            $boundary = uniqid('', true);
            $body = "--$boundary\r\n"
                . "Content-Disposition: form-data; name=\"payload_json\"\r\nContent-Type: application/json\r\n\r\n$payload\r\n"
                . "--$boundary\r\n"
                . "Content-Disposition: form-data; name=\"files[0]\"; filename=\"$filename\"\r\nContent-Type: audio/mpeg\r\n\r\n$audio\r\n"
                . "--$boundary--\r\n";

            $ch2 = curl_init($webhookUrl);
            curl_setopt_array($ch2, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => ["Content-Type: multipart/form-data; boundary=$boundary"],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
            ]);
            curl_exec($ch2);
            curl_close($ch2);

            awardXP($userId, 5, $appId, $token, $channelId);
            return;
        }
    }

    followUp($appId, $token, '', [embed(
        "🔊 Text-to-Speech",
        "**Text:** " . truncate($text, 300) . "\n**Voice:** " . ucfirst($voice) . "\n\n_Audio TTS requires an OpenAI API key. Meanwhile, try `/voiceclone` for AI voice synthesis, or subscribe with `/deploy billing` for full access._",
        0x3498DB,
        [],
        ['footer' => ['text' => 'GoSiteMe AI Voice Services']]
    )]);
}


function handleSms(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $phone = ''; $message = '';
    foreach ($opts as $o) {
        if ($o['name'] === 'phone') $phone = $o['value'];
        if ($o['name'] === 'message') $message = $o['value'];
    }

    $userId = $data['member']['user']['id'] ?? '0';
    $appId = $data['application_id'] ?? '';
    $token = $data['token'] ?? '';
    $globalName = $data['member']['user']['global_name'] ?? $data['member']['user']['username'] ?? 'User';

    if (!$phone || !$message) { respondEphemeral("Usage: `/sms phone:+15551234567 message:Hello!`"); return; }
    if (strlen($message) > 160) { respondEphemeral("SMS limited to 160 characters."); return; }

    // Validate phone format
    $phone = preg_replace('/[^+\d]/', '', $phone);
    if (!preg_match('/^\+?1?\d{10,15}$/', $phone)) {
        respondEphemeral("Invalid phone number format. Include country code: +15551234567");
        return;
    }
    if ($phone[0] !== '+') $phone = '+1' . $phone;

    // Rate limit: max 3 SMS per user per day
    $pdo = getDiscordDB();
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM discord_economy WHERE discord_id = ? AND reason LIKE 'SMS sent%' AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)");
        $stmt->execute([$userId]);
        if ($stmt->fetchColumn() >= 3) {
            respondEphemeral("📱 Daily SMS limit reached (3/day). Resets in 24h.");
            return;
        }
    }

    // Check KGD balance (5 KGD per SMS)
    if ($pdo) {
        $user = getOrCreateUser($userId, $globalName);
        if (($user['kgd_balance'] ?? 0) < 5) {
            respondEphemeral("📱 Sending SMS costs 5 KGD. Your balance: {$user['kgd_balance']} KGD");
            return;
        }
    }

    deferResponse(true); // Ephemeral

    // Send via Telnyx
    $telnyxKey = getenv('TELNYX_API_KEY') ?: '';
    $fromNumber = getenv('TELNYX_FROM_NUMBER') ?: '+18077982850';

    if (!$telnyxKey) {
        followUp($appId, $token, '⚠️ SMS service temporarily unavailable.', [], [], 64);
        return;
    }

    $ch = curl_init('https://api.telnyx.com/v2/messages');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'from' => $fromNumber,
            'to' => $phone,
            'text' => "[GoSiteMe] From $globalName: $message",
            'type' => 'SMS',
        ]),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            "Authorization: Bearer $telnyxKey",
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
    ]);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $resp = json_decode($result, true);

    if ($httpCode >= 200 && $httpCode < 300) {
        // Deduct KGD
        if ($pdo) {
            $pdo->prepare("UPDATE discord_users SET kgd_balance = kgd_balance - 5, total_spent = total_spent + 5 WHERE discord_id = ?")
                ->execute([$userId]);
            $pdo->prepare("INSERT INTO discord_economy (discord_id, entry_type, amount, reason) VALUES (?, 'spend', 5, ?)")
                ->execute([$userId, "SMS sent to " . substr($phone, 0, 4) . '****' . substr($phone, -4)]);
        }

        $masked = substr($phone, 0, 4) . '****' . substr($phone, -4);
        followUp($appId, $token, '', [embed("📱 SMS Sent!", "**To:** $masked\n**Message:** $message\n**Cost:** 5 KGD", 0x57F287)], [], 64);
    } else {
        $error = $resp['errors'][0]['detail'] ?? 'Unknown error';
        followUp($appId, $token, "❌ SMS failed: $error", [], [], 64);
    }
}


function handleSearch(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $query = '';
    foreach ($opts as $o) { if ($o['name'] === 'query') $query = $o['value']; }
    if (!$query) { respond("Enter a search query! `/search query:how to deploy a website`"); return; }

    deferResponse();

    $userId = $data['member']['user']['id'] ?? '0';
    $appId = $data['application_id'] ?? '';
    $token = $data['token'] ?? '';
    $channelId = $data['channel_id'] ?? '';

    $searchPrompt = "You are a search engine assistant. Provide a comprehensive answer with: "
        . "1) Direct answer (2-3 sentences), 2) Key facts (bullet points), "
        . "3) If applicable: a code example or step-by-step guide. "
        . "Use Discord markdown. Be accurate, concise, and helpful. Today's date is " . date('F j, Y') . ".";

    $result = callGroq($searchPrompt, $query, 0.5, 800);

    awardXP($userId, 3, $appId, $token, $channelId);

    followUp($appId, $token, '', [embed(
        "🔍 Search: " . truncate($query, 80),
        truncate($result, 3900),
        0x4285F4,
        [],
        ['footer' => ['text' => 'Powered by GoSiteMe AI']]
    )], [actionRow(
        btn(2, '🔍 More Details', 'search_more_' . substr(md5($query), 0, 8))
    )]);
}


function handleScreenshot(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $url = '';
    foreach ($opts as $o) { if ($o['name'] === 'url') $url = $o['value']; }
    if (!$url) { respond("Enter a URL! `/screenshot url:example.com`"); return; }

    // Sanitize URL — extract domain only
    $url = preg_replace('#^(https?://)?#', '', $url);
    $url = preg_replace('#[/\\\\?#].*$#', '', $url);
    if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9.\-]{1,253}\.[a-zA-Z]{2,}$/', $url)) {
        respond("❌ Invalid domain format."); return;
    }

    deferResponse();

    $appId = $data['application_id'] ?? '';
    $token = $data['token'] ?? '';

    $screenshotUrl = "https://image.thum.io/get/width/1280/crop/720/https://$url";

    $emb = embed("📸 Screenshot — $url", '', 0x5865F2);
    $emb['image'] = ['url' => $screenshotUrl];

    followUp($appId, $token, '', [$emb], [actionRow(
        btn(2, '📐 Full Page', "screenshot_full_$url"),
        btn(5, '🌐 Visit Site', "https://$url")
    )]);
}


function handleCalc(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $expression = '';
    foreach ($opts as $o) { if ($o['name'] === 'expression') $expression = $o['value']; }
    if (!$expression) { respond("Enter an expression! `/calc expression:sqrt(144) + 50`"); return; }

    // Check if it's a unit conversion
    if (preg_match('/^\s*([\d.]+)\s*(km|mi|lb|kg|°?[cfCF]|cm|in|m|ft|oz|g|l|gal)\s+(?:to|in)\s+(km|mi|lb|kg|°?[cfCF]|cm|in|m|ft|oz|g|l|gal)\s*$/i', $expression, $m)) {
        $val = (float)$m[1];
        $from = strtolower($m[2]);
        $to = strtolower($m[3]);
        $result = convertUnit($val, $from, $to);
        if ($result !== null) {
            respond(null, [embed("🧮 Conversion", "`$val $from` = **$result $to**", 0x3498DB)]);
            return;
        }
    }

    // Safe math evaluation via Groq
    $result = callGroq(
        "You are a calculator. Respond with ONLY the numerical result. Solve exactly. No explanation.",
        "Calculate: $expression",
        0.1, 50
    );
    $result = trim($result);

    respond(null, [embed("🧮 Calculator", "**Input:** `$expression`\n**Result:** **$result**", 0x3498DB)]);
}

function convertUnit(float $val, string $from, string $to): ?string {
    $conversions = [
        'km_mi' => fn($v) => round($v * 0.621371, 4),
        'mi_km' => fn($v) => round($v * 1.60934, 4),
        'kg_lb' => fn($v) => round($v * 2.20462, 4),
        'lb_kg' => fn($v) => round($v * 0.453592, 4),
        'cm_in' => fn($v) => round($v * 0.393701, 4),
        'in_cm' => fn($v) => round($v * 2.54, 4),
        'm_ft'  => fn($v) => round($v * 3.28084, 4),
        'ft_m'  => fn($v) => round($v * 0.3048, 4),
        'c_f'   => fn($v) => round($v * 9/5 + 32, 2),
        'f_c'   => fn($v) => round(($v - 32) * 5/9, 2),
        'l_gal' => fn($v) => round($v * 0.264172, 4),
        'gal_l' => fn($v) => round($v * 3.78541, 4),
        'oz_g'  => fn($v) => round($v * 28.3495, 4),
        'g_oz'  => fn($v) => round($v * 0.035274, 4),
    ];
    $from = str_replace('°', '', $from);
    $to = str_replace('°', '', $to);
    $key = "{$from}_{$to}";
    if (isset($conversions[$key])) return (string)$conversions[$key]($val);
    return null;
}


function handleMusic(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $mood = 'chill'; $genre = '';
    foreach ($opts as $o) {
        if ($o['name'] === 'mood') $mood = $o['value'];
        if ($o['name'] === 'genre') $genre = $o['value'];
    }

    deferResponse();

    $appId = $data['application_id'] ?? '';
    $token = $data['token'] ?? '';
    $genreStr = $genre ? " Genre preference: $genre." : '';

    $result = callGroq(
        "You are a music recommendation AI. Recommend 5 songs. Format each as:\n🎵 **Song Title** — Artist Name\n   Brief reason (1 line)\n\nInclude a mix of classics and modern hits. End with a 'Playlist Vibe:' summary (1 sentence).",
        "Recommend songs for mood: \"$mood\".$genreStr",
        0.8, 500
    );

    followUp($appId, $token, '', [embed(
        "🎶 Music Recommendations",
        "**Mood:** " . ucfirst($mood) . ($genre ? " | **Genre:** " . ucfirst($genre) : '') . "\n\n$result",
        0x1DB954,
        [],
        ['footer' => ['text' => 'Powered by GoSiteMe AI']]
    )], [actionRow(
        btn(2, '🔄 More Recs', 'music_more'),
        btn(2, '📋 Full Playlist', 'music_playlist')
    )]);
}


function handleDeploy(array $data): void {
    $subCmd = getSubcommand($data);
    $subOpts = getSubOptions($data);
    $userId = $data['member']['user']['id'] ?? '0';
    $appId = $data['application_id'] ?? '';
    $token = $data['token'] ?? '';
    $globalName = $data['member']['user']['global_name'] ?? $data['member']['user']['username'] ?? 'User';
    $channelId = $data['channel_id'] ?? '';

    switch ($subCmd) {
        case 'website':
            $name = $subOpts['name'] ?? '';
            $description = $subOpts['description'] ?? 'A modern website';
            $template = $subOpts['template'] ?? 'landing';

            if (!$name) { respond("Name your project! `/deploy website name:my-site description:A portfolio site`"); return; }
            $name = preg_replace('/[^a-z0-9\-]/', '', strtolower($name));
            if (strlen($name) < 3 || strlen($name) > 30) { respond("Name must be 3-30 characters (letters, numbers, hyphens)."); return; }

            deferResponse();

            $templatePrompts = [
                'landing' => "Hero section with big headline + CTA button, features grid (6 items with icons), testimonials section, pricing table (3 tiers), FAQ accordion, footer with links. Use gradient backgrounds and glassmorphism cards.",
                'portfolio' => "Sticky navigation, hero with animated text + profile photo placeholder, project showcase grid with hover effects and modals, about section with timeline, skills progress bars, contact form with validation, footer.",
                'ecommerce' => "Header with cart icon + badge, hero carousel/banner, product grid (8 items) with add-to-cart buttons, categories sidebar, featured products section, newsletter signup, trust badges, footer with payment icons.",
                'blog' => "Magazine-style layout with featured post hero, article grid with thumbnails + excerpts + read time, sidebar with categories/tags/recent posts, newsletter signup, author bios section, pagination, footer.",
                'saas' => "Clean SaaS landing: sticky nav with CTA, hero with product screenshot mockup, logo bar (trusted by), feature sections with alternating image/text, pricing toggle (monthly/annual), integration logos, CTA section, footer.",
                'restaurant' => "Full-screen food hero image, menu sections (appetizers/mains/desserts/drinks) with prices, reservations section with form, about/story section, photo gallery grid, location map placeholder, hours, footer.",
                'agency' => "Bold creative agency: video hero background placeholder, services with icons, case studies/portfolio grid, team members with social links, client logos, process/workflow section, contact with map, footer.",
                'resume' => "Professional single-page resume: header with name/title/photo, summary, work experience timeline, education, skills tag cloud, certifications, projects with links, downloadable PDF button placeholder, contact info.",
            ];

            $templateDetail = $templatePrompts[$template] ?? $templatePrompts['landing'];

            $html = callGroq(
                "You are an elite web developer who builds STUNNING production-quality websites. Generate a COMPLETE single-page HTML website.\n\n"
                . "STRICT REQUIREMENTS:\n"
                . "- All CSS inline in <style> tag (NO external deps, NO CDNs)\n"
                . "- Professional color scheme with CSS custom properties\n"
                . "- Fully responsive: mobile-first with media queries for tablet + desktop\n"
                . "- CSS Grid + Flexbox layouts throughout\n"
                . "- Smooth scroll behavior, scroll-snap where appropriate\n"
                . "- CSS animations: fade-in on scroll (IntersectionObserver JS), hover transitions, micro-interactions\n"
                . "- Modern typography: system font stack (-apple-system, BlinkMacSystemFont, etc.)\n"
                . "- Dark/light mode toggle with CSS variables\n"
                . "- Accessible: proper semantic HTML, ARIA labels, focus states, skip-nav link\n"
                . "- Include placeholder images using solid colored divs with icons/text\n"
                . "- Include a working hamburger menu for mobile\n"
                . "- Favicon link, meta viewport, meta description, OpenGraph tags\n"
                . "- Minimum 6 distinct sections\n"
                . "- Footer with copyright, social media icon links, powered-by credit\n\n"
                . "TEMPLATE SPECIFICS ($template):\n$templateDetail\n\n"
                . "Output ONLY the complete HTML. Start with <!DOCTYPE html>. No markdown, no explanation.",
                "Build a '$template' website for: $description\nSite name: $name",
                0.7, 8000
            );

            // Extract HTML
            if (preg_match('/<!DOCTYPE html>.*<\/html>/si', $html, $match)) {
                $html = $match[0];
            }

            // Save to public directory
            $deployDir = dirname(dirname(__DIR__)) . "/demos/discord/$name";
            if (!is_dir($deployDir)) mkdir($deployDir, 0755, true);
            file_put_contents("$deployDir/index.html", $html);

            $url = "https://gositeme.com/demos/discord/$name/";

            awardXP($userId, 25, $appId, $token, $channelId);

            followUp($appId, $token, '', [embed(
                "🚀 Website Deployed!",
                "**$globalName** deployed a website from Discord!\n\n"
                . "🌐 **Live URL:** [$url]($url)\n"
                . "📝 **Project:** $name\n"
                . "🎨 **Template:** " . ucfirst($template) . "\n"
                . "📝 **Description:** $description\n\n"
                . "✨ **Features:** Dark/light mode, responsive, animations, accessibility\n"
                . "⚡ +25 XP earned!",
                0x57F287
            )], [actionRow(
                btn(5, '🌐 View Live Site', $url),
                btn(2, '✏️ Edit', "deploy_edit_$name")
            )]);
            break;

        case 'status':
            respond(null, [embed("🚀 Deploy Status", "Visit [gositeme.com](https://gositeme.com) to manage your deployments.\n\nFull deployment pipeline: AI Brief → Design → Code → Review → Deploy", 0x5865F2)], [
                actionRow(btn(5, '📊 Dashboard', 'https://gositeme.com/dashboard')),
            ]);
            break;

        case 'billing':
            $plan = $subOpts['plan'] ?? 'starter';
            $validPlans = [
                'starter' => ['name' => 'Alfred Starter', 'price' => '$3.99/mo', 'features' => '100 tools, 60 voice min/day, 3 agents, 10K API calls/day'],
                'professional' => ['name' => 'Alfred Professional', 'price' => '$9.99/mo', 'features' => 'ALL 1,220+ tools, unlimited voice, 5 agents, 100K API calls/day'],
                'enterprise' => ['name' => 'Alfred Enterprise', 'price' => '$24.99/mo', 'features' => 'ALL tools + priority, 20 agents, 500K API calls/day, team management'],
                'enterprise_plus' => ['name' => 'Alfred Enterprise Plus', 'price' => '$99/mo', 'features' => 'SSO, audit logging, unlimited API, voice cloning, data residency'],
            ];
            $p = $validPlans[$plan] ?? $validPlans['starter'];
            $checkoutUrl = "https://gositeme.com/api/discord-billing.php?plan=" . urlencode($plan)
                         . "&discord_id=" . urlencode($userId)
                         . "&discord_name=" . urlencode($globalName);

            respondEphemeral(null, [embed(
                "⭐ Subscribe to {$p['name']}",
                "**Price:** {$p['price']} (14-day free trial)\n\n"
                . "**Includes:**\n{$p['features']}\n\n"
                . "Click the button below to subscribe via Stripe (secure checkout).\n"
                . "You can cancel anytime from the billing portal.",
                0xF39C12,
                [],
                ['footer' => ['text' => 'Powered by Stripe • Secure payment processing']]
            )], [actionRow(
                btn(5, '💳 Subscribe Now', $checkoutUrl),
                btn(5, '📋 All Plans', 'https://gositeme.com/pricing')
            )]);
            break;

        case 'plans':
            respondEphemeral(null, [embed(
                "💎 Alfred Premium Plans",
                "**⭐ Starter — \$3.99/mo**\n100 tools, 60 voice min, 3 agents, email support\n\n"
                . "**🚀 Professional — \$9.99/mo**\nALL 1,220+ tools, unlimited voice, marketplace publish\n\n"
                . "**👑 Enterprise — \$24.99/mo**\n20 agents, 500K API/day, team management, 24/7 support\n\n"
                . "**💎 Enterprise+ — \$99/mo**\nSSO, audit logs, voice cloning, dedicated CSM\n\n"
                . "All plans include a **14-day free trial**. Cancel anytime.",
                0x5865F2,
                [],
                ['footer' => ['text' => 'Use /deploy billing plan:<plan> to subscribe']]
            )], [actionRow(
                btn(5, '🌐 Full Pricing', 'https://gositeme.com/pricing'),
                btn(5, '📊 Dashboard', 'https://gositeme.com/dashboard')
            )]);
            break;

        default:
            respond("Use `/deploy website name:my-site description:A portfolio` or `/deploy status`.");
    }
}
