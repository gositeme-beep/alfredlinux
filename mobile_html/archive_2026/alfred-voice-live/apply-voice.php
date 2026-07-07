<?php
/**
 * Apply Voice — Updates Alfred's TTS voice across all services
 * Updates: test-tts.php, discord-bot.js, alfred-livestream.js
 * Then restarts PM2 services
 */
header('Content-Type: application/json');

$allowed = ['am_michael','am_adam','bm_george','bm_lewis','af_heart','af_bella','af_sarah','af_nicole','af_sky','bf_emma','bf_isabella'];
$voice = $_GET['voice'] ?? '';

if (!in_array($voice, $allowed, true)) {
    echo json_encode(['error' => 'Invalid voice: ' . htmlspecialchars($voice)]);
    exit;
}

$errors = [];
$basePath = dirname(__DIR__);

// 1. Update test-tts.php
$testTts = $basePath . '/alfred-voice-live/test-tts.php';
if (file_exists($testTts)) {
    $content = file_get_contents($testTts);
    $content = preg_replace("/'voice'\s*=>\s*'[a-z_]+'/", "'voice' => '$voice'", $content);
    file_put_contents($testTts, $content);
} else {
    $errors[] = 'test-tts.php not found';
}

// 2. Update discord-bot.js 
$discordBot = $basePath . '/websocket/discord-bot.js';
if (file_exists($discordBot)) {
    $content = file_get_contents($discordBot);
    $content = preg_replace("/voice:\s*'[a-z_]+'/", "voice: '$voice'", $content);
    file_put_contents($discordBot, $content);
} else {
    $errors[] = 'discord-bot.js not found';
}

// 3. Update alfred-livestream.js
$livestream = $basePath . '/websocket/alfred-livestream.js';
if (file_exists($livestream)) {
    $content = file_get_contents($livestream);
    $content = preg_replace("/voice:\s*'[a-z_]+'/", "voice: '$voice'", $content);
    file_put_contents($livestream, $content);
} else {
    $errors[] = 'alfred-livestream.js not found';
}

// 4. Restart PM2 services
exec('pm2 restart alfred-discord 2>&1', $out1, $ret1);
exec('pm2 restart alfred-livestream 2>&1', $out2, $ret2);

if ($ret1 !== 0) $errors[] = 'Failed to restart alfred-discord';
if ($ret2 !== 0) $errors[] = 'Failed to restart alfred-livestream';

// 5. Save preference
file_put_contents(__DIR__ . '/voice-preference.json', json_encode([
    'voice' => $voice,
    'updated' => date('Y-m-d H:i:s'),
]));

if ($errors) {
    echo json_encode(['success' => false, 'error' => implode(', ', $errors)]);
} else {
    echo json_encode(['success' => true, 'voice' => $voice, 'restarted' => ['alfred-discord', 'alfred-livestream']]);
}
