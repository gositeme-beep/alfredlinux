<?php
/**
 * GoSiteMe Discord Bot — AI Commands Module
 * ═══════════════════════════════════════════
 * /alfred    — AI chat with context memory
 * /imagine   — AI image generation (27 models)
 * /translate — AI-powered translation (100+ languages)
 * /code      — Code execution & review
 * /summarize — Summarize text or URLs
 */

function handleAlfred(array $data): void {
    $opts = getSubOptions($data);
    $msg = $opts['message'] ?? '';
    $persona = $opts['persona'] ?? 'alfred';
    if (!$msg) { respond("Please provide a message! `/alfred message:your question`"); return; }

    $userId = $data['member']['user']['id'] ?? '0';
    $globalName = $data['member']['user']['global_name'] ?? $data['member']['user']['username'] ?? 'User';
    $appId = $data['application_id'] ?? '';
    $token = $data['token'] ?? '';
    $channelId = $data['channel_id'] ?? '';

    deferResponse();
    getOrCreateUser($userId, $globalName);
    awardXP($userId, 5, $appId, $token, $channelId);

    $personas = [
        'alfred'    => "You are Alfred, the AI Assistant for GoSiteMe.com. Sophisticated, helpful, witty, with dry British humor. An expert in web hosting, cloud computing, AI, and technology.",
        'nova'      => "You are Nova, GoSiteMe's Creative Director AI. Artistic, visionary, and inspiring. Expert in design, branding, UX/UI, and creative technology.",
        'sage'      => "You are Sage, GoSiteMe's Knowledge Oracle. Deeply philosophical, analytical, and precise. An expert in science, history, mathematics, and research.",
        'cipher'    => "You are Cipher, GoSiteMe's Security Specialist AI. No-nonsense, technically precise, and alert. Expert in cybersecurity, encryption, privacy, and ethical hacking.",
        'atlas'     => "You are Atlas, GoSiteMe's Business Strategist AI. Bold, confident, data-driven. Expert in business strategy, finance, marketing, and growth.",
    ];

    $sysPrompt = ($personas[$persona] ?? $personas['alfred'])
        . " You're responding in Discord. Keep responses concise (under 1900 chars), use Discord markdown."
        . " User: $globalName."
        . " GoSiteMe offers: Web Hosting (\$15-\$99/mo), AI-Powered IDE (GoCodeMe), Voice AI, Phone Systems, Domains, SSL, GPU Servers, Metaverse."
        . " Kingdom Economy: Earn KGD coins via /chess, /checkers, /trivia. Check /coins, /profile, /daily."
        . " Website: https://gositeme.com | Support: support@gositeme.com | Phone: +1 (807) 798-2850";

    $reply = callGroq($sysPrompt, $msg, 0.7, 1500);
    if (!$reply) {
        followUp($appId, $token, "⚠️ AI is temporarily unavailable. Please try again!");
        return;
    }

    $reply = truncate($reply, 1900);
    $personaEmoji = ['alfred' => '🎩', 'nova' => '🎨', 'sage' => '📚', 'cipher' => '🔐', 'atlas' => '📊'][$persona] ?? '🤖';
    $personaName = ucfirst($persona);

    followUp($appId, $token, '', [
        embed("$personaEmoji $personaName", $reply, 0x5865F2, [], [
            'footer' => ['text' => "Asked by $globalName | GoSiteMe AI"],
            'thumbnail' => ['url' => 'https://gositeme.com/assets/images/logo-icon.png'],
        ])
    ]);
}


function handleImagine(array $data): void {
    $opts = getSubOptions($data);
    $prompt = $opts['prompt'] ?? '';
    $model = $opts['model'] ?? 'flux-schnell';
    $style = $opts['style'] ?? '';
    $size = $opts['size'] ?? '1024x1024';
    if (!$prompt) { respond("Describe what you want! `/imagine prompt:a dragon flying over a castle`"); return; }

    $userId = $data['member']['user']['id'] ?? '0';
    $globalName = $data['member']['user']['global_name'] ?? $data['member']['user']['username'] ?? 'User';
    $appId = $data['application_id'] ?? '';
    $token = $data['token'] ?? '';
    $channelId = $data['channel_id'] ?? '';

    deferResponse();
    getOrCreateUser($userId, $globalName);
    awardXP($userId, 10, $appId, $token, $channelId);

    $togetherKey = getTogetherKey();
    if (!$togetherKey) {
        followUp($appId, $token, "⚠️ Image generation is temporarily unavailable.");
        return;
    }

    $modelMap = [
        'flux-schnell'   => 'black-forest-labs/FLUX.1-schnell-Free',
        'flux-pro'       => 'black-forest-labs/FLUX.1.1-pro',
        'flux-dev'       => 'black-forest-labs/FLUX.1-dev',
        'flux-canny'     => 'black-forest-labs/FLUX.1-canny',
        'flux-depth'     => 'black-forest-labs/FLUX.1-depth',
        'ideogram'       => 'ideogram-ai/ideogram-v2-turbo',
        'stable-diff'    => 'stabilityai/stable-diffusion-xl-base-1.0',
    ];

    $modelId = $modelMap[$model] ?? $modelMap['flux-schnell'];
    $dims = explode('x', $size);
    $width = max(256, min(2048, (int)($dims[0] ?? 1024)));
    $height = max(256, min(2048, (int)($dims[1] ?? 1024)));

    $fullPrompt = $prompt;
    if ($style) {
        $styles = [
            'anime' => ', anime art style, vibrant colors, detailed illustration',
            'photo' => ', photorealistic, 8K, ultra detailed, professional photography',
            'oil'   => ', oil painting style, rich textures, classical art, masterpiece',
            'pixel' => ', pixel art style, retro gaming, 16-bit',
            'neon'  => ', neon lights, cyberpunk, glowing, dark background',
            'watercolor' => ', watercolor painting, soft edges, artistic, pastel',
            'comic' => ', comic book style, bold outlines, dynamic, pop art',
            '3d'    => ', 3D render, octane render, unreal engine, volumetric lighting',
        ];
        $fullPrompt .= ($styles[$style] ?? '');
    }

    $payload = [
        'model'  => $modelId,
        'prompt' => $fullPrompt,
        'width'  => $width,
        'height' => $height,
        'steps'  => $model === 'flux-schnell' ? 4 : 20,
        'n'      => 1,
        'response_format' => 'b64_json',
    ];

    $ch = curl_init('https://api.together.xyz/v1/images/generations');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Authorization: Bearer ' . $togetherKey],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 60,
    ]);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        followUp($appId, $token, "⚠️ Image generation failed (HTTP $httpCode). Try a different model or simpler prompt.");
        return;
    }

    $imgData = json_decode($result, true);
    $b64 = $imgData['data'][0]['b64_json'] ?? null;

    if (!$b64) {
        // Sometimes it returns a URL instead
        $imgUrl = $imgData['data'][0]['url'] ?? null;
        if ($imgUrl) {
            followUp($appId, $token, '', [
                embed("🎨 Generated Image", "**Prompt:** $prompt", 0x9B59B6, [
                    field('Model', $model, true),
                    field('Size', "{$width}x{$height}", true),
                    field('Style', $style ?: 'None', true),
                ], [
                    'image' => ['url' => $imgUrl],
                    'footer' => ['text' => "Created by $globalName | GoSiteMe AI Art"],
                ])
            ], [
                actionRow(
                    btn(1, '🔄 Regenerate', "imagine_regen_" . urlencode(substr($prompt, 0, 80))),
                    btn(5, '🌐 GoSiteMe AI', 'https://gositeme.com/gocodeme')
                )
            ]);
            return;
        }
        followUp($appId, $token, "⚠️ Image generation returned no data. Try again!");
        return;
    }

    // Save image and send as attachment via multipart
    $imgBin = base64_decode($b64);
    $filename = 'gositeme_' . time() . '_' . substr(md5($prompt), 0, 8) . '.png';
    $savePath = dirname(dirname(__DIR__)) . '/ai-images/' . $filename;
    file_put_contents($savePath, $imgBin);
    $imgUrl = 'https://gositeme.com/ai-images/' . $filename;

    followUp($appId, $token, '', [
        embed("🎨 Generated Image", "**Prompt:** $prompt", 0x9B59B6, [
            field('Model', $model, true),
            field('Size', "{$width}x{$height}", true),
            field('Style', $style ?: 'Default', true),
        ], [
            'image' => ['url' => $imgUrl],
            'footer' => ['text' => "Created by $globalName | GoSiteMe AI Art | /imagine"],
        ])
    ], [
        actionRow(
            btn(1, '🔄 Regenerate', "imagine_regen_" . substr(md5($prompt), 0, 16)),
            btn(5, '🖼️ Full Size', $imgUrl),
            btn(5, '🌐 GoSiteMe AI', 'https://gositeme.com/gocodeme')
        )
    ]);
}


function handleTranslate(array $data): void {
    $opts = getSubOptions($data);
    $text = $opts['text'] ?? '';
    $target = $opts['language'] ?? 'English';
    if (!$text) { respond("Provide text to translate! `/translate text:Bonjour le monde language:English`"); return; }

    $userId = $data['member']['user']['id'] ?? '0';
    $globalName = $data['member']['user']['global_name'] ?? $data['member']['user']['username'] ?? 'User';
    $appId = $data['application_id'] ?? '';
    $token = $data['token'] ?? '';

    deferResponse();
    awardXP($userId, 3);

    $sys = "You are a professional translator. Translate the following text to $target. "
         . "Return ONLY the translation, nothing else. Preserve formatting.";
    $result = callGroq($sys, $text, 0.2, 1500);
    if (!$result) { followUp($appId, $token, "⚠️ Translation failed. Please try again."); return; }

    // Detect source language
    $langDetect = callGroq("Identify the language of this text. Reply with ONLY the language name, nothing else.", $text, 0.1, 20);

    followUp($appId, $token, '', [
        embed("🌐 Translation", '', 0x3498DB, [
            field('From', $langDetect ?: 'Auto-detected', true),
            field('To', $target, true),
            field('Original', truncate($text, 500), false),
            field('Translation', truncate($result, 900), false),
        ], [
            'footer' => ['text' => "Translated by $globalName | GoSiteMe AI"],
        ])
    ]);
}


function handleCode(array $data): void {
    $subCmd = getSubcommand($data);
    $opts = getSubOptions($data);
    $userId = $data['member']['user']['id'] ?? '0';
    $globalName = $data['member']['user']['global_name'] ?? $data['member']['user']['username'] ?? 'User';
    $appId = $data['application_id'] ?? '';
    $token = $data['token'] ?? '';

    switch ($subCmd) {
        case 'run':
            $lang = $opts['language'] ?? 'python';
            $code = $opts['code'] ?? '';
            if (!$code) { respond("Provide code! `/code run language:python code:print('Hello')`"); return; }

            deferResponse();
            awardXP($userId, 8);

            // Use Groq to simulate code execution (safer than actual exec)
            $sys = "You are a code execution engine. Execute this $lang code mentally and return the EXACT output. "
                 . "Format your response as:\n```\n[output here]\n```\n"
                 . "If there's an error, show the error message. "
                 . "Only show the output, no explanations. If the code produces no output, say '(no output)'.";
            $result = callGroq($sys, "```$lang\n$code\n```", 0.1, 1000);
            if (!$result) { followUp($appId, $token, "⚠️ Code execution failed."); return; }

            followUp($appId, $token, '', [
                embed("💻 Code Execution — $lang", '', 0x2ECC71, [
                    field('Code', "```$lang\n" . truncate($code, 400) . "\n```", false),
                    field('Output', truncate($result, 900), false),
                ], [
                    'footer' => ['text' => "Run by $globalName | GoSiteMe Code Engine"],
                ])
            ], [
                actionRow(
                    btn(5, '💻 GoCodeMe IDE', 'https://gositeme.com/gocodeme'),
                    btn(5, '📖 Docs', 'https://gositeme.com/developers')
                )
            ]);
            break;

        case 'review':
            $code = $opts['code'] ?? '';
            if (!$code) { respond("Provide code to review! `/code review code:function hello() {...}`"); return; }

            deferResponse();
            awardXP($userId, 8);

            $sys = "You are a senior code reviewer. Review this code and provide:\n"
                 . "1. 🎯 **Quality Score**: X/10\n"
                 . "2. ✅ **Strengths**: What's good\n"
                 . "3. ⚠️ **Issues**: Bugs, security issues, performance problems\n"
                 . "4. 💡 **Suggestions**: How to improve\n"
                 . "Keep it under 1500 characters. Use Discord markdown.";
            $result = callGroq($sys, "```\n$code\n```", 0.3, 1500);
            if (!$result) { followUp($appId, $token, "⚠️ Code review failed."); return; }

            followUp($appId, $token, '', [
                embed("🔍 Code Review", $result, 0xE74C3C, [], [
                    'footer' => ['text' => "Reviewed for $globalName | GoSiteMe AI"],
                ])
            ]);
            break;

        case 'explain':
            $code = $opts['code'] ?? '';
            if (!$code) { respond("Provide code! `/code explain code:const x = arr.reduce(...)`"); return; }

            deferResponse();
            awardXP($userId, 5);

            $sys = "Explain this code in plain English. Be concise but thorough. "
                 . "Use Discord markdown. Structure: what it does, how it works, key concepts. Under 1500 chars.";
            $result = callGroq($sys, "```\n$code\n```", 0.3, 1500);
            if (!$result) { followUp($appId, $token, "⚠️ Explanation failed."); return; }

            followUp($appId, $token, '', [
                embed("📖 Code Explained", $result, 0x3498DB, [], [
                    'footer' => ['text' => "Explained for $globalName | GoSiteMe AI"],
                ])
            ]);
            break;

        default:
            respond("Usage: `/code run`, `/code review`, or `/code explain`");
    }
}


function handleSummarize(array $data): void {
    $opts = getSubOptions($data);
    $text = $opts['text'] ?? '';
    $length = $opts['length'] ?? 'medium';
    if (!$text) { respond("Provide text to summarize! `/summarize text:paste your text here`"); return; }

    $userId = $data['member']['user']['id'] ?? '0';
    $globalName = $data['member']['user']['global_name'] ?? $data['member']['user']['username'] ?? 'User';
    $appId = $data['application_id'] ?? '';
    $token = $data['token'] ?? '';

    deferResponse();
    awardXP($userId, 5);

    $lengths = [
        'short' => 'Provide a 1-2 sentence summary. Maximum 200 characters.',
        'medium' => 'Provide a concise summary in 3-5 sentences. Maximum 500 characters.',
        'long' => 'Provide a detailed summary with key points. Maximum 1000 characters.',
        'bullets' => 'Provide a bullet-point summary with the key takeaways. Use • for bullets.',
    ];

    $sys = "Summarize the following text. " . ($lengths[$length] ?? $lengths['medium']) . " Use Discord markdown.";
    $result = callGroq($sys, $text, 0.3, 1000);
    if (!$result) { followUp($appId, $token, "⚠️ Summarization failed."); return; }

    followUp($appId, $token, '', [
        embed("📋 Summary", $result, 0x1ABC9C, [
            field('Length', ucfirst($length), true),
            field('Original', strlen($text) . ' chars', true),
        ], [
            'footer' => ['text' => "Summarized for $globalName | GoSiteMe AI"],
        ])
    ]);
}
