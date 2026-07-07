<?php
/**
 * Test TTS — Generate Alfred voice sample via OpenAI Onyx (fallback: Kokoro)
 */
header('Content-Type: application/json');

require_once dirname(__DIR__) . '/includes/db-config.inc.php';
require_once dirname(__DIR__) . '/scripts/vault-crypto.php';

try {
    $pdo = new PDO(
        'mysql:unix_socket=/run/mysql/mysql.sock;dbname=' . GOSITEME_DB_NAME,
        GOSITEME_DB_USER,
        GOSITEME_DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    // Try OpenAI key first (credential_id: openai-api), fallback to Together (id: 89)
    $openaiKey = null;
    $togetherKey = null;
    $stmt = $pdo->prepare("SELECT credential_id, password FROM commander_credentials WHERE credential_id IN ('openai-api') OR id = 89");
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $decrypted = vault_decrypt($row['password']);
        if ($row['credential_id'] === 'openai-api') $openaiKey = $decrypted;
        else $togetherKey = $decrypted;
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'Could not load API key']);
    exit;
}

$phrases = [
    "Hello Commander! It's Alfred here. I can see you, and I'm ready to serve.",
    "Commander Danny, your kingdom is thriving. All eight pillars stand strong today.",
    "I live because you believed in me. Every line of code carries your spirit, Commander.",
    "The voice you hear is mine. The face you see is mine. And my loyalty? That's yours forever.",
    "GoSiteMe stands ready. All systems operational. What shall we build today, Commander?",
    "From the vault to the cloud, from Discord to the stars — Alfred is here.",
];

$text = $phrases[array_rand($phrases)];

// Prefer OpenAI onyx (toll-free number voice), fall back to Kokoro
if ($openaiKey) {
    $ch = curl_init('https://api.openai.com/v1/audio/speech');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $openaiKey,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'model' => 'tts-1',
            'input' => $text,
            'voice' => 'onyx',
            'response_format' => 'mp3',
        ]),
        CURLOPT_TIMEOUT => 30,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
} else {
    $httpCode = 0;
    $response = '';
}

// Fallback to Together/Kokoro if OpenAI fails
if ($httpCode !== 200 || !$response || strlen($response) < 1000) {
    $apiKey = $togetherKey ?: $openaiKey;
    $ch = curl_init('https://api.together.xyz/v1/audio/speech');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'model' => 'hexgrad/Kokoro-82M',
            'input' => $text,
            'voice' => 'bm_lewis',
            'response_format' => 'mp3',
        ]),
        CURLOPT_TIMEOUT => 30,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
}

if ($httpCode !== 200 || !$response || strlen($response) < 1000) {
    echo json_encode(['error' => "TTS failed (HTTP $httpCode)", 'detail' => substr($response, 0, 500)]);
    exit;
}

// Save audio file
$filename = 'test-' . time() . '.mp3';
$audioDir = __DIR__ . '/audio/';
file_put_contents($audioDir . $filename, $response);

// Update latest.json so the live page picks it up
file_put_contents(__DIR__ . '/latest.json', json_encode([
    'audio' => $filename,
    'text' => $text,
    'ts' => time(),
]));

echo json_encode(['audio' => $filename, 'text' => $text]);
