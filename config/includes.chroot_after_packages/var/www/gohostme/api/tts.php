<?php
/**
 * Alfred TTS API — Voice synthesis for Alfred IDE & chat
 * 
 * Priority: OpenAI "onyx" (VAPI toll-free voice) → Groq Orpheus → gTTS (Google)
 * No Microsoft dependencies.
 * 
 * POST /api/tts.php
 *   Body: { "text": "...", "voice": "onyx" }
 *   Returns: audio binary
 */

header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Alfred-Source, X-Alfred-IDE-Token');
header('Access-Control-Allow-Credentials: true');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

require_once __DIR__ . '/../includes/db-config.inc.php';
require_once __DIR__ . '/../includes/alfred-ide-bearer.inc.php';
require_once __DIR__ . '/../../.env.php';

// --- Auth: allow trusted Alfred Voice Live requests, otherwise require IDE session ---
$origin = (string)($_SERVER['HTTP_ORIGIN'] ?? '');
$referer = (string)($_SERVER['HTTP_REFERER'] ?? '');
$trustedVoiceLive = (
    $origin === 'https://gositeme.com'
    && strpos($referer, 'https://gositeme.com/alfred-voice-live/') === 0
);

$_ttsToken = alfred_resolve_ide_bearer_token();
if (!$_ttsToken) $_ttsToken = $_COOKIE['alfred_ide_token'] ?? '';

$_ttsUser = null;
if ($_ttsToken) {
    $_ttsHash = hash('sha256', $_ttsToken);
    $_ttsUser = alfred_ide_lookup_user_by_token_hash(
        $GLOBALS['db'] ?? (new PDO("mysql:host=localhost;dbname=gositeme_whmcs;unix_socket=/run/mysql/mysql.sock", DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION])),
        $_ttsHash
    );
}

if (!$_ttsUser && !$trustedVoiceLive) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$text = trim($input['text'] ?? '');
$voice = $input['voice'] ?? 'onyx';

if (!$text) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['error' => 'No text provided']);
    exit;
}

$clean = strip_tags($text);
$clean = preg_replace('/```[\s\S]*?```/', ' code block ', $clean);
$clean = preg_replace('/[*_`#~\[\]]/', '', $clean);
$clean = preg_replace('/https?:\/\/\S+/', '', $clean);
$clean = preg_replace('/\s+/', ' ', $clean);
$clean = trim($clean);

if (!$clean || strlen($clean) < 2) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['error' => 'No speakable text']);
    exit;
}

if (strlen($clean) > 4000) {
    $clean = substr($clean, 0, 4000);
    $lastSpace = strrpos($clean, ' ');
    if ($lastSpace > 3500) $clean = substr($clean, 0, $lastSpace);
}

// === TIER 1: OpenAI TTS — exact "onyx" voice (same as VAPI toll-free) ===
$openaiKey = getenv('OPENAI_API_KEY');
if ($openaiKey) {
    $openaiVoices = ['alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer'];
    $onyxVoice = in_array($voice, $openaiVoices) ? $voice : 'onyx';
    $audio = openaiTTS($openaiKey, $clean, $onyxVoice);
    if ($audio !== false) {
        header('Content-Type: audio/mpeg');
        header('Content-Length: ' . strlen($audio));
        header('Cache-Control: no-cache');
        header('X-TTS-Provider: openai');
        echo $audio;
        exit;
    }
}

// === TIER 2: Groq Orpheus — natural expressive voice ===
$groqKey = getenv('GROQ_API_KEY');
if ($groqKey) {
    $audio = groqTTS($groqKey, $clean, 'daniel');
    if ($audio !== false) {
        header('Content-Type: audio/wav');
        header('Content-Length: ' . strlen($audio));
        header('Cache-Control: no-cache');
        header('X-TTS-Provider: groq-orpheus');
        echo $audio;
        exit;
    }
}

// === TIER 2.5: Local espeak-ng → ffmpeg MP3 (British male, offline, free) ===
//   Used as the preferred FREE fallback when paid services (OpenAI / Groq) are down.
//   Voice: en-gb-x-rp (Received Pronunciation) — the iconic British butler accent.
//   Beats Google TTS fallback because Google's gTTS default voice is female.
$audio = espeakTTS($clean);
if ($audio !== false) {
    header('Content-Type: audio/mpeg');
    header('Content-Length: ' . strlen($audio));
    header('Cache-Control: no-cache');
    header('X-TTS-Provider: espeak-ng-rp');
    echo $audio;
    exit;
}

// === TIER 3: Google Translate TTS (no API key needed, no Microsoft) ===
//   WARNING: female voice — only used if espeak-ng pipeline also fails.
$audio = googleTTS($clean);
if ($audio !== false) {
    header('Content-Type: audio/mpeg');
    header('Content-Length: ' . strlen($audio));
    header('Cache-Control: no-cache');
    header('X-TTS-Provider: google-tts');
    echo $audio;
    exit;
}

header('Content-Type: application/json');
http_response_code(502);
echo json_encode(['error' => 'All TTS providers failed']);


function openaiTTS(string $apiKey, string $text, string $voice): string|false {
    $payload = json_encode([
        'model' => 'tts-1-hd',
        'input' => substr($text, 0, 4096),
        'voice' => $voice,
        'response_format' => 'mp3',
        'speed' => 1.0
    ]);
    $ch = curl_init('https://api.openai.com/v1/audio/speech');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $apiKey, 'Content-Type: application/json'],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpCode !== 200 || !$response || strlen($response) < 200) return false;
    return $response;
}

function groqTTS(string $apiKey, string $text, string $voice): string|false {
    $chunks = chunkText($text, 195);
    $allAudio = '';
    foreach ($chunks as $chunk) {
        $payload = json_encode([
            'model' => 'canopylabs/orpheus-v1-english',
            'input' => $chunk,
            'voice' => $voice,
            'response_format' => 'wav'
        ]);
        $ch = curl_init('https://api.groq.com/openai/v1/audio/speech');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $apiKey, 'Content-Type: application/json'],
            CURLOPT_TIMEOUT => 30
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode !== 200 || !$response || strlen($response) < 100) return false;
        $allAudio .= $response;
    }
    return $allAudio ?: false;
}

function googleTTS(string $text): string|false {
    $text = substr($text, 0, 200);
    $encoded = urlencode($text);
    $url = "https://translate.google.com/translate_tts?ie=UTF-8&q={$encoded}&tl=en&client=tw-ob";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36',
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpCode !== 200 || !$response || strlen($response) < 100) return false;
    return $response;
}

function chunkText(string $text, int $maxLen): array {
    if (strlen($text) <= $maxLen) return [$text];
    $chunks = [];
    $sentences = preg_split('/(?<=[.!?])\s+/', $text);
    $current = '';
    foreach ($sentences as $sentence) {
        if (strlen($sentence) > $maxLen) {
            if ($current) { $chunks[] = $current; $current = ''; }
            foreach (explode(' ', $sentence) as $word) {
                $test = $current ? "$current $word" : $word;
                if (strlen($test) > $maxLen) { if ($current) $chunks[] = $current; $current = $word; }
                else $current = $test;
            }
            continue;
        }
        $test = $current ? "$current $sentence" : $sentence;
        if (strlen($test) > $maxLen) { $chunks[] = $current; $current = $sentence; }
        else $current = $test;
    }
    if ($current) $chunks[] = $current;
    return $chunks;
}

/**
 * espeakTTS: Local offline British male TTS via espeak-ng + ffmpeg.
 * Voice: en-gb-x-rp (Received Pronunciation), rate 145 wpm, pitch 35 (deep).
 * Returns MP3 bytes or false on failure.
 */
function espeakTTS(string $text): string|false {
    $espeak = trim((string)@shell_exec('command -v espeak-ng 2>/dev/null'));
    if (!$espeak) return false;
    // ffmpeg may live in $HOME/bin (gositeme) — check both PATH and known location
    $ffmpeg = trim((string)@shell_exec('command -v ffmpeg 2>/dev/null'));
    if (!$ffmpeg && is_executable('/home/gositeme/bin/ffmpeg')) {
        $ffmpeg = '/home/gositeme/bin/ffmpeg';
    }
    if (!$ffmpeg) return false;

    $tmpWav = tempnam(sys_get_temp_dir(), 'alfred_es_') . '.wav';
    $tmpMp3 = $tmpWav . '.mp3';
    $payload = escapeshellarg(substr($text, 0, 4000));

    // Synthesize WAV (en-gb-x-rp = British Received Pronunciation, butler-style)
    $cmd1 = sprintf(
        '%s -v en-gb-x-rp -s 145 -p 35 -a 180 %s -w %s 2>/dev/null',
        escapeshellcmd($espeak), $payload, escapeshellarg($tmpWav)
    );
    @shell_exec($cmd1);
    if (!file_exists($tmpWav) || filesize($tmpWav) < 1000) {
        @unlink($tmpWav);
        return false;
    }

    // Convert WAV → MP3 (browser-friendly, smaller)
    $cmd2 = sprintf(
        '%s -loglevel error -y -i %s -codec:a libmp3lame -b:a 64k %s 2>/dev/null',
        escapeshellcmd($ffmpeg), escapeshellarg($tmpWav), escapeshellarg($tmpMp3)
    );
    @shell_exec($cmd2);
    @unlink($tmpWav);

    if (!file_exists($tmpMp3) || filesize($tmpMp3) < 500) {
        @unlink($tmpMp3);
        return false;
    }
    $bytes = @file_get_contents($tmpMp3);
    @unlink($tmpMp3);
    return $bytes ?: false;
}
