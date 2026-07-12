<?php
/**
 * GoSiteMe Discord Bot — Media Module
 * ════════════════════════════════════
 * /video      — AI video generation (Kling/MiniMax via fal.ai)
 * /musicgen   — AI music generation (MusicGen via Replicate)
 * /voiceclone — Premium TTS with ElevenLabs voices
 *
 * UNIQUE: Generate videos, music, and clone voices — all from Discord.
 */

function handleVideo(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $prompt = '';
    foreach ($opts as $o) { if ($o['name'] === 'prompt') $prompt = $o['value']; }

    $userId = $data['member']['user']['id'] ?? '0';
    $appId = $data['application_id'] ?? '';
    $token = $data['token'] ?? '';
    $globalName = $data['member']['user']['global_name'] ?? $data['member']['user']['username'] ?? 'User';
    $channelId = $data['channel_id'] ?? '';

    if (!$prompt) { respond("🎬 Usage: `/video prompt:A cat dancing on the moon`"); return; }
    if (strlen($prompt) > 500) { respond("Prompt too long (max 500 chars)."); return; }

    // Check balance (25 KGD — video is expensive)
    $user = getOrCreateUser($userId, $globalName);
    if (($user['kgd_balance'] ?? 0) < 25) {
        respondEphemeral("🎬 Video generation costs 25 KGD. Balance: {$user['kgd_balance']} KGD");
        return;
    }

    // Rate limit: 3 videos per day
    $pdo = getDiscordDB();
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM discord_economy WHERE discord_id = ? AND reason LIKE 'AI video%' AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)");
        $stmt->execute([$userId]);
        if ($stmt->fetchColumn() >= 3) {
            respondEphemeral("🎬 Daily video limit reached (3/day).");
            return;
        }
    }

    deferResponse();

    $falKey = getenv('FAL_API_KEY') ?: '';
    if (!$falKey) {
        // Fallback: Generate a cinematic AI image instead using Together AI (which IS available)
        $togetherKey = '';
        $envPath = dirname(dirname(dirname(__DIR__))) . '/.env.php';
        if (file_exists($envPath)) {
            $envContent = file_get_contents($envPath);
            if (preg_match("/TOGETHER_API_KEY['\"]?\s*[,=]\s*['\"]([^'\"]+)/", $envContent, $m)) {
                $togetherKey = $m[1];
            }
        }
        // Also check gocodeme .env
        $gocodemeEnv = dirname(dirname(__DIR__)) . '/gocodeme/.env';
        if (!$togetherKey && file_exists($gocodemeEnv)) {
            $envLines = file($gocodemeEnv, FILE_IGNORE_NEW_LINES);
            foreach ($envLines as $line) {
                if (str_starts_with($line, 'TOGETHER_API_KEY=')) {
                    $togetherKey = trim(substr($line, strlen('TOGETHER_API_KEY=')), '"\'');
                }
            }
        }
        // Also check gocodeme/middleware .env
        $mwEnv = dirname(dirname(__DIR__)) . '/gocodeme/middleware/.env';
        if (!$togetherKey && file_exists($mwEnv)) {
            $envLines = file($mwEnv, FILE_IGNORE_NEW_LINES);
            foreach ($envLines as $line) {
                if (str_starts_with($line, 'TOGETHER_API_KEY=')) {
                    $togetherKey = trim(substr($line, strlen('TOGETHER_API_KEY=')), '"\'');
                }
            }
        }

        if ($togetherKey) {
            // Generate cinematic storyboard image as video preview
            $ch = curl_init('https://api.together.xyz/v1/images/generations');
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([
                    'model' => 'black-forest-labs/FLUX.1-schnell-Free',
                    'prompt' => "Cinematic 16:9 film frame, movie scene: $prompt, professional cinematography, dramatic lighting, 4K quality",
                    'width' => 1024,
                    'height' => 576,
                    'n' => 4,
                ]),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json', "Authorization: Bearer $togetherKey"],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
            ]);
            $result = curl_exec($ch);
            curl_close($ch);

            $imageData = json_decode($result, true);
            $images = [];
            foreach (($imageData['data'] ?? []) as $img) {
                if (isset($img['url'])) $images[] = $img['url'];
                elseif (isset($img['b64_json'])) {
                    $imgFile = 'storyboard_' . substr(md5($prompt . count($images)), 0, 8) . '.png';
                    $imgDir = dirname(dirname(__DIR__)) . '/ai-images';
                    if (!is_dir($imgDir)) mkdir($imgDir, 0755, true);
                    file_put_contents("$imgDir/$imgFile", base64_decode($img['b64_json']));
                    $images[] = "https://gositeme.com/ai-images/$imgFile";
                }
            }

            if (!empty($images)) {
                if ($pdo) {
                    $pdo->prepare("UPDATE discord_users SET kgd_balance = kgd_balance - 10, total_spent = total_spent + 10 WHERE discord_id = ?")
                        ->execute([$userId]);
                    $pdo->prepare("INSERT INTO discord_economy (discord_id, entry_type, amount, reason) VALUES (?, 'spend', 10, ?)")
                        ->execute([$userId, "AI video storyboard"]);
                }

                awardXP($userId, 10, $appId, $token, $channelId);

                $embeds = [embed(
                    "🎬 AI Cinematic Storyboard",
                    "**Prompt:** " . truncate($prompt, 200) . "\n**Frames:** " . count($images) . " cinematic shots\n**Cost:** 10 KGD\n\n_Full video generation coming soon with fal.ai integration!_",
                    0xE91E63,
                    [],
                    ['image' => ['url' => $images[0]], 'footer' => ['text' => "Generated by $globalName • GoSiteMe AI"]]
                )];
                // Show additional frames
                for ($i = 1; $i < min(4, count($images)); $i++) {
                    $embeds[] = ['image' => ['url' => $images[$i]]];
                }
                followUp($appId, $token, '', $embeds);
                return;
            }
        }

        followUp($appId, $token, "⚠️ Video generation service temporarily unavailable. Use `/deploy billing plan:professional` to unlock full video generation.");
        return;
    }

    // Use MiniMax Video via fal.ai
    $ch = curl_init('https://queue.fal.run/fal-ai/minimax/video/01/live');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['prompt' => $prompt]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', "Authorization: Key $falKey"],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 120,
    ]);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $resp = json_decode($result, true);
    $videoUrl = $resp['video']['url'] ?? $resp['video_url'] ?? null;

    if ($videoUrl) {
        // Deduct KGD
        if ($pdo) {
            $pdo->prepare("UPDATE discord_users SET kgd_balance = kgd_balance - 25, total_spent = total_spent + 25 WHERE discord_id = ?")
                ->execute([$userId]);
            $pdo->prepare("INSERT INTO discord_economy (discord_id, entry_type, amount, reason) VALUES (?, 'spend', 25, ?)")
                ->execute([$userId, "AI video generated"]);
        }

        awardXP($userId, 20, $appId, $token, $channelId);

        followUp($appId, $token, '', [embed(
            "🎬 AI Video Generated!",
            "**Prompt:** " . truncate($prompt, 200) . "\n**Cost:** 25 KGD\n\n🎥 [Watch Video]($videoUrl)",
            0xE91E63,
            [],
            ['footer' => ['text' => "Generated by $globalName • GoSiteMe AI"]]
        )], [actionRow(
            btn(5, '🎥 Watch', $videoUrl),
            btn(2, '🔄 Regenerate', 'video_regen_' . substr(md5($prompt), 0, 8))
        )]);
    } else {
        $error = $resp['detail'] ?? $resp['error'] ?? 'Generation failed';
        followUp($appId, $token, "❌ Video generation failed: $error\n\nTip: Try a simpler prompt.");
    }
}


function handleMusicgen(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $prompt = ''; $duration = 15;
    foreach ($opts as $o) {
        if ($o['name'] === 'prompt') $prompt = $o['value'];
        if ($o['name'] === 'duration') $duration = min(30, max(5, (int)$o['value']));
    }

    $userId = $data['member']['user']['id'] ?? '0';
    $appId = $data['application_id'] ?? '';
    $token = $data['token'] ?? '';
    $globalName = $data['member']['user']['global_name'] ?? $data['member']['user']['username'] ?? 'User';
    $channelId = $data['channel_id'] ?? '';

    if (!$prompt) { respond("🎵 Usage: `/musicgen prompt:upbeat electronic dance track with synths`"); return; }

    // Check balance (15 KGD)
    $user = getOrCreateUser($userId, $globalName);
    if (($user['kgd_balance'] ?? 0) < 15) {
        respondEphemeral("🎵 Music generation costs 15 KGD. Balance: {$user['kgd_balance']} KGD");
        return;
    }

    // Rate limit: 5/day
    $pdo = getDiscordDB();
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM discord_economy WHERE discord_id = ? AND reason LIKE 'AI music%' AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)");
        $stmt->execute([$userId]);
        if ($stmt->fetchColumn() >= 5) {
            respondEphemeral("🎵 Daily music gen limit reached (5/day).");
            return;
        }
    }

    deferResponse();

    $replicateKey = getenv('REPLICATE_API_TOKEN') ?: '';
    if (!$replicateKey) {
        // Fallback: Use Groq to generate a detailed music composition description
        $composition = callGroq(
            "You are a professional music producer. Given a prompt, create a detailed musical composition breakdown with: tempo (BPM), key signature, instruments, structure (intro/verse/chorus/outro), mood progression, and mixing notes. Keep it under 300 words. Format it beautifully.",
            "Create a composition plan for: $prompt (duration: {$duration}s)",
            0.8, 1024
        );

        if ($composition) {
            followUp($appId, $token, '', [embed(
                "🎵 AI Music Composition Plan",
                "**Prompt:** " . truncate($prompt, 150) . "\n**Duration:** {$duration}s\n\n$composition\n\n_Full audio generation coming soon! Use `/deploy billing` to unlock premium features._",
                0x1DB954,
                [],
                ['footer' => ['text' => "Composed by AI for $globalName"]]
            )]);
            awardXP($userId, 5, $appId, $token, $channelId);
            return;
        }
        followUp($appId, $token, "⚠️ Music generation service temporarily unavailable. Subscribe with `/deploy billing plan:professional` for full access.");
        return;
    }

    // Step 1: Create prediction
    $ch = curl_init('https://api.replicate.com/v1/predictions');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'version' => 'b05b1dff1d8c6dc63d14b0cdb42135571e41c36afb3c9dc95898e32a5a1af0c3',
            'input' => [
                'prompt' => $prompt,
                'duration' => $duration,
                'temperature' => 1.0,
                'model_version' => 'large',
            ],
        ]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', "Authorization: Bearer $replicateKey"],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
    ]);
    $result = curl_exec($ch);
    curl_close($ch);

    $prediction = json_decode($result, true);
    $predictionId = $prediction['id'] ?? null;

    if (!$predictionId) {
        followUp($appId, $token, "❌ Music generation failed to start.");
        return;
    }

    // Step 2: Poll for result (max 90 seconds)
    $musicUrl = null;
    for ($i = 0; $i < 18; $i++) {
        sleep(5);
        $ch = curl_init("https://api.replicate.com/v1/predictions/$predictionId");
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => ["Authorization: Bearer $replicateKey"],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        $pollResult = curl_exec($ch);
        curl_close($ch);

        $pollData = json_decode($pollResult, true);
        $status = $pollData['status'] ?? '';

        if ($status === 'succeeded') {
            $musicUrl = $pollData['output'] ?? null;
            break;
        }
        if ($status === 'failed' || $status === 'canceled') break;
    }

    if ($musicUrl) {
        if ($pdo) {
            $pdo->prepare("UPDATE discord_users SET kgd_balance = kgd_balance - 15, total_spent = total_spent + 15 WHERE discord_id = ?")
                ->execute([$userId]);
            $pdo->prepare("INSERT INTO discord_economy (discord_id, entry_type, amount, reason) VALUES (?, 'spend', 15, ?)")
                ->execute([$userId, "AI music generated"]);
        }

        awardXP($userId, 15, $appId, $token, $channelId);

        followUp($appId, $token, '', [embed(
            "🎵 AI Music Generated!",
            "**Prompt:** " . truncate($prompt, 200) . "\n**Duration:** {$duration}s\n**Cost:** 15 KGD\n\n🎶 [Listen]($musicUrl)",
            0x1DB954,
            [],
            ['footer' => ['text' => "Generated by $globalName • MusicGen"]]
        )], [actionRow(
            btn(5, '🎶 Listen', $musicUrl),
            btn(2, '🔄 New Track', 'musicgen_regen_' . substr(md5($prompt), 0, 8))
        )]);
    } else {
        followUp($appId, $token, "❌ Music generation timed out or failed. Try a shorter duration or simpler prompt.");
    }
}


function handleVoiceclone(array $data): void {
    $opts = $data['data']['options'] ?? [];
    $text = ''; $voice = 'bella';
    foreach ($opts as $o) {
        if ($o['name'] === 'text') $text = $o['value'];
        if ($o['name'] === 'voice') $voice = $o['value'];
    }

    $userId = $data['member']['user']['id'] ?? '0';
    $appId = $data['application_id'] ?? '';
    $token = $data['token'] ?? '';
    $globalName = $data['member']['user']['global_name'] ?? $data['member']['user']['username'] ?? 'User';
    $channelId = $data['channel_id'] ?? '';

    if (!$text) { respond("🎙️ Usage: `/voiceclone text:Hello world voice:bella`"); return; }
    if (strlen($text) > 2000) { respond("Text too long! Max 2000 characters."); return; }

    // Check balance (10 KGD)
    $user = getOrCreateUser($userId, $globalName);
    if (($user['kgd_balance'] ?? 0) < 10) {
        respondEphemeral("🎙️ Voice cloning costs 10 KGD. Balance: {$user['kgd_balance']} KGD");
        return;
    }

    deferResponse();

    $elevenLabsKey = getenv('ELEVENLABS_API_KEY') ?: '';
    if (!$elevenLabsKey) {
        // Fallback: generate a dramatic reading script via Groq
        $reading = callGroq(
            "You are a voice acting director. Format this text as a dramatic voice performance script with stage directions like [pause], [emphasis], [whisper], [energetic]. Include breathing and pacing notes. Keep the script under 300 words.",
            "Create a voice performance script for: $text",
            0.8, 800
        );

        if ($reading) {
            followUp($appId, $token, '', [embed(
                "🎙️ Voice Performance Script — " . ucfirst($voice),
                "**Original Text:**\n> " . truncate($text, 200) . "\n\n**Directed Script:**\n$reading\n\n_Full AI voice synthesis coming soon! Use `/deploy billing` to unlock._",
                0x9B59B6,
                [],
                ['footer' => ['text' => "Directed for $globalName"]]
            )]);
            awardXP($userId, 3, $appId, $token, $channelId);
            return;
        }
        followUp($appId, $token, "⚠️ Voice cloning service temporarily unavailable. Subscribe with `/deploy billing plan:professional` for access.");
        return;
    }

    // ElevenLabs voice IDs
    $voices = [
        'bella'   => 'EXAVITQu4vr4xnSDxMaL',
        'rachel'  => '21m00Tcm4TlvDq8ikWAM',
        'adam'    => 'pNInz6obpgDQGcFmaJgB',
        'sam'    => 'yoZ06aMxZJJ28mfd3POQ',
        'elli'   => 'MF3mGyEYCl7XYWbV9V6O',
        'josh'   => 'TxGEqnHWrfWFTfGW9XjX',
        'arnold' => 'VR6AewLTigWG4xSOukaG',
        'domi'   => 'AZnzlk1XvdvUeBnXmlld',
    ];

    $voiceId = $voices[$voice] ?? $voices['bella'];

    $ch = curl_init("https://api.elevenlabs.io/v1/text-to-speech/$voiceId");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'text' => $text,
            'model_id' => 'eleven_turbo_v2_5',
        ]),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            "xi-api-key: $elevenLabsKey",
            'Accept: audio/mpeg',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
    ]);
    $audio = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $audio && strlen($audio) > 100) {
        $filename = 'vc_' . substr(md5($text . $voice . time()), 0, 12) . '.mp3';
        $dir = dirname(dirname(__DIR__)) . '/cache/voice';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        file_put_contents("$dir/$filename", $audio);

        // Deduct KGD
        $pdo = getDiscordDB();
        if ($pdo) {
            $pdo->prepare("UPDATE discord_users SET kgd_balance = kgd_balance - 10, total_spent = total_spent + 10 WHERE discord_id = ?")
                ->execute([$userId]);
            $pdo->prepare("INSERT INTO discord_economy (discord_id, entry_type, amount, reason) VALUES (?, 'spend', 10, ?)")
                ->execute([$userId, "Voice clone ($voice)"]);
        }

        // Upload as multipart
        $webhookUrl = "https://discord.com/api/v10/webhooks/$appId/$token";
        $embedData = [embed("🎙️ Voice Clone — " . ucfirst($voice), "**Text:** " . truncate($text, 300) . "\n**Voice:** " . ucfirst($voice) . "\n**Cost:** 10 KGD", 0x9B59B6)];
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

        awardXP($userId, 10, $appId, $token, $channelId);
    } else {
        followUp($appId, $token, "❌ Voice cloning failed (HTTP $httpCode). Try again later.");
    }
}