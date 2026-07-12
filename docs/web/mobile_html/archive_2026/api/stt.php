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

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Alfred-Source');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    http_response_code(405);
    echo json_encode(['error' => 'POST only']);
    exit;
}

require_once __DIR__ . '/../../.env.php';

$openaiKey = getenv('OPENAI_API_KEY');
if (!$openaiKey) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'OpenAI API key not configured']);
    exit;
}

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

if (!$audioData || !$tmpFile || !file_exists($tmpFile) || filesize($tmpFile) < 100) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['error' => 'No audio data received']);
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

$ch = curl_init('https://api.openai.com/v1/audio/transcriptions');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => [
        'file' => new CURLFile($namedFile, $mime ?: 'audio/webm', 'audio.' . $ext),
        'model' => 'whisper-1',
        'language' => 'en',
        'response_format' => 'json'
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $openaiKey],
    CURLOPT_TIMEOUT => 60,
    CURLOPT_CONNECTTIMEOUT => 10
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch);
curl_close($ch);

@unlink($namedFile);

if ($httpCode !== 200 || !$response) {
    header('Content-Type: application/json');
    http_response_code(502);
    echo json_encode(['error' => 'Whisper API failed', 'detail' => $curlErr ?: substr($response, 0, 200)]);
    exit;
}

$result = json_decode($response, true);
$text = trim($result['text'] ?? '');

header('Content-Type: application/json');
echo json_encode(['text' => $text, 'provider' => 'openai-whisper']);
