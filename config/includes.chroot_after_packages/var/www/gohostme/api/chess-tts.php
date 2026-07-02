<?php
/**
 * Chess TTS — Lightweight OpenAI TTS proxy for VR Chess voice
 * Uses the same 'onyx' voice as the Alfred toll-free line.
 * No auth required, IP-rate-limited, aggressive text-hash caching.
 *
 * GET ?text=Knight+to+e4        → returns { "url": "...?play=HASH" }
 * GET ?play=HASH                → streams cached MP3 audio
 */

define('GOSITEME_API', true);
require_once dirname(__DIR__) . '/includes/db-config.inc.php';

// ── Config ──
$OPENAI_KEY  = getenv('OPENAI_API_KEY') ?: '';
$VOICE       = 'onyx';
$MODEL       = 'tts-1';        // tts-1 = fast/low-latency
$MAX_LEN     = 200;             // chess announcements are short
$RATE_LIMIT  = 120;             // per IP per hour
$CACHE_DIR   = dirname(__DIR__) . '/cache/chess-tts/';

// ── Serve cached audio directly ──
$playHash = $_GET['play'] ?? '';
if ($playHash !== '' && preg_match('/^[a-f0-9]{16}$/', $playHash)) {
    $file = $CACHE_DIR . $playHash . '.mp3';
    if (is_readable($file) && filesize($file) > 100) {
        header('Content-Type: audio/mpeg');
        header('Content-Length: ' . filesize($file));
        header('Cache-Control: public, max-age=86400');
        header('Access-Control-Allow-Origin: https://gositeme.com');
        readfile($file);
        exit;
    }
    http_response_code(404);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://gositeme.com');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// ── Rate limit by IP ──
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$rlFile = sys_get_temp_dir() . '/chess-tts-rl-' . md5($ip) . '.json';
$rl = is_readable($rlFile) ? json_decode(file_get_contents($rlFile), true) : null;
$now = time();
if (!$rl || ($now - ($rl['reset'] ?? 0)) > 3600) {
    $rl = ['count' => 0, 'reset' => $now];
}
if ($rl['count'] >= $RATE_LIMIT) {
    http_response_code(429);
    echo json_encode(['error' => 'Rate limit exceeded']);
    exit;
}

// ── Validate text ──
$text = trim($_GET['text'] ?? '');
if ($text === '' || strlen($text) < 2) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing or too-short text']);
    exit;
}
if (strlen($text) > $MAX_LEN) {
    $text = substr($text, 0, $MAX_LEN);
}

// ── Check cache (hash of normalized text) ──
$hash = substr(hash('sha256', strtolower($text)), 0, 16);
$cacheFile = $CACHE_DIR . $hash . '.mp3';
if (file_exists($cacheFile) && filesize($cacheFile) > 100) {
    echo json_encode(['url' => '/api/chess-tts.php?play=' . $hash, 'cached' => true]);
    exit;
}

// ── Call OpenAI TTS ──
if (!$OPENAI_KEY) {
    http_response_code(503);
    echo json_encode(['error' => 'TTS not configured']);
    exit;
}

$ch = curl_init('https://api.openai.com/v1/audio/speech');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode([
        'model' => $MODEL,
        'input' => $text,
        'voice' => $VOICE,
        'speed' => 1.0,
    ]),
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $OPENAI_KEY,
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
]);
$audio = curl_exec($ch);
$code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($code !== 200 || !$audio || strlen($audio) < 100) {
    http_response_code(502);
    echo json_encode(['error' => 'TTS generation failed', 'http' => $code]);
    exit;
}

// ── Save to cache ──
if (!is_dir($CACHE_DIR)) mkdir($CACHE_DIR, 0750, true);
file_put_contents($cacheFile, $audio);

// ── Count rate limit ──
$rl['count']++;
file_put_contents($rlFile, json_encode($rl), LOCK_EX);

echo json_encode(['url' => '/api/chess-tts.php?play=' . $hash, 'cached' => false]);
