<?php
/**
 * Alfred STT API — Speech-to-Text for Alfred IDE
 *
 * Uses OpenAI Whisper (whisper-1) for transcription.
 *
 * POST /api/stt.php
 *   Body: multipart/form-data with "audio" file field
 *   Returns: { "text": "transcribed text" }
 */

header('Access-Control-Allow-Origin: https://gositeme.com');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Alfred-Source, X-Alfred-IDE-Token');
header('Access-Control-Allow-Credentials: true');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    http_response_code(405);
    echo json_encode(['error' => 'POST only']);
    exit;
}

// --- Debug logging (Android STT troubleshooting) ---
$_sttDbg = function($msg) {
    $line = '[' . date('c') . '] ' . $msg . "\n";
    @file_put_contents('/home/gositeme/domains/gositeme.com/logs/stt-debug.log', $line, FILE_APPEND);
};
$_ua = $_SERVER['HTTP_USER_AGENT'] ?? '?';
$_ct = $_SERVER['CONTENT_TYPE'] ?? '?';
$_cl = $_SERVER['CONTENT_LENGTH'] ?? '?';
$_sttDbg("REQUEST ua=" . substr($_ua,0,80) . " ct=$_ct cl=$_cl");
define('STT_DEBUG_LOG', 1);


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

$_sttToken = alfred_resolve_ide_bearer_token();
if (!$_sttToken) $_sttToken = $_COOKIE['alfred_ide_token'] ?? '';

$_sttUser = null;
if ($_sttToken) {
    $_sttHash = hash('sha256', $_sttToken);
    $_sttUser = alfred_ide_lookup_user_by_token_hash(
        $GLOBALS['db'] ?? (new PDO("mysql:host=localhost;dbname=gositeme_whmcs;unix_socket=/run/mysql/mysql.sock", DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION])),
        $_sttHash
    );
}

if (!$_sttUser && !$trustedVoiceLive) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

$openaiKey = getenv('OPENAI_API_KEY');

$audioData = null;
$tmpFile = null;

if (!empty($_FILES['audio']['tmp_name']) && is_uploaded_file($_FILES['audio']['tmp_name'])) {
    $tmpFile = $_FILES['audio']['tmp_name'];
    $audioData = true;
} else {
    $raw = file_get_contents('php://input');
    if ($raw && strlen($raw) > 100) {
        $tmpFile = tempnam(sys_get_temp_dir(), 'alfred_stt_');
        if (strpos($raw, 'data:') === 0) {
            $comma = strpos($raw, ',');
            if ($comma !== false) $raw = base64_decode(substr($raw, $comma + 1));
        }
        file_put_contents($tmpFile, $raw);
        $audioData = true;
    }
}

$_sttDbg('upload_check tmp=' . ($tmpFile ?: 'null') . ' exists=' . (($tmpFile && file_exists($tmpFile)) ? 'yes' : 'no') . ' size=' . (($tmpFile && file_exists($tmpFile)) ? filesize($tmpFile) : 'NA') . ' files_audio_set=' . (isset($_FILES['audio']) ? 'yes' : 'no'));
if (!$audioData || !$tmpFile || !file_exists($tmpFile) || filesize($tmpFile) < 100) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['error' => 'No audio data received (size=' . (($tmpFile && file_exists($tmpFile)) ? filesize($tmpFile) : 0) . ')']);
    exit;
}

if (filesize($tmpFile) > 25 * 1024 * 1024) {
    header('Content-Type: application/json');
    http_response_code(413);
    echo json_encode(['error' => 'Audio too large (max 25MB)']);
    exit;
}

$ext = 'webm';
$mime = $_FILES['audio']['type'] ?? '';
if (strpos($mime, 'wav') !== false) $ext = 'wav';
elseif (strpos($mime, 'mp4') !== false || strpos($mime, 'm4a') !== false) $ext = 'm4a';
elseif (strpos($mime, 'ogg') !== false) $ext = 'ogg';
elseif (strpos($mime, 'mpeg') !== false || strpos($mime, 'mp3') !== false) $ext = 'mp3';

$namedFile = $tmpFile . '.' . $ext;
rename($tmpFile, $namedFile);

// --- TIER 1: Groq Whisper (fast, free quota) ---
$response = false; $httpCode = 0; $curlErr = ''; $provider = '';
$groqKey = getenv('GROQ_API_KEY');
if ($groqKey) {
    $ch = curl_init('https://api.groq.com/openai/v1/audio/transcriptions');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => [
            'file' => new CURLFile($namedFile, $mime ?: 'audio/webm', 'audio.' . $ext),
            'model' => 'whisper-large-v3',
            'language' => 'en',
            'response_format' => 'json',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $groqKey],
        CURLOPT_TIMEOUT => 60,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);
    $_sttDbg("groq http=$httpCode err=" . substr((string)$curlErr,0,80) . " resp_head=" . substr((string)$response,0,140));
    if ($httpCode === 200 && $response) {
        $provider = 'groq-whisper-large-v3';
    } else {
        $response = false;
    }
}

// --- TIER 2: OpenAI Whisper (fallback) ---
if ($response === false && $openaiKey) {
    $ch = curl_init('https://api.openai.com/v1/audio/transcriptions');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => [
            'file' => new CURLFile($namedFile, $mime ?: 'audio/webm', 'audio.' . $ext),
            'model' => 'whisper-1',
            'language' => 'en',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $openaiKey],
        CURLOPT_TIMEOUT => 60,
        CURLOPT_CONNECTTIMEOUT => 10
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);
    $_sttDbg("openai http=$httpCode err=" . substr((string)$curlErr,0,80) . " resp_head=" . substr((string)$response,0,140));
    if ($httpCode === 200 && $response) {
        $provider = 'openai-whisper';
    } else {
        $response = false;
    }
}

@unlink($namedFile);

if ($response === false) {
    header('Content-Type: application/json');
    http_response_code(502);
    echo json_encode(['error' => 'STT failed (' . $httpCode . '): ' . substr($curlErr ?: 'all providers down', 0, 160)]);
    exit;
}

$result = json_decode($response, true);
$text = trim($result['text'] ?? '');

header('Content-Type: application/json');
header('X-STT-Provider: ' . $provider);
echo json_encode(['text' => $text, 'provider' => $provider]);
