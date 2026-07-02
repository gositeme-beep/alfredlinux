<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 *  Alfred Agent Harness — API Proxy
 *  
 *  Bridges the IDE webview (browser-side) to the local agent harness
 *  running on 127.0.0.1:3102. The webview can't reach localhost directly
 *  due to browser same-origin policy, so this PHP endpoint proxies.
 *  
 *  POST /api/alfred-agent.php  → 127.0.0.1:3102/chat
 *  GET  /api/alfred-agent.php  → 127.0.0.1:3102/health
 * ═══════════════════════════════════════════════════════════════════════════
 */

header('Content-Type: application/json');
header('X-Alfred-Proxy: agent-harness');

// Map IDE model names to provider + model for the harness
function mapIdeModel(string $ideModel): array {
    $map = [
        // Anthropic
        'sonnet'        => ['provider' => 'anthropic', 'model' => 'claude-sonnet-4-6'],
        'opus'          => ['provider' => 'anthropic', 'model' => 'claude-opus-4-6'],
        'haiku'         => ['provider' => 'anthropic', 'model' => 'claude-haiku-4-5-20251001'],
        // OpenAI
        'gpt-4o'        => ['provider' => 'openai', 'model' => 'gpt-4o'],
        'gpt-4o-mini'   => ['provider' => 'openai', 'model' => 'gpt-4o-mini'],
        'gpt-4.1'       => ['provider' => 'openai', 'model' => 'gpt-4.1'],
        'gpt-4.1-mini'  => ['provider' => 'openai', 'model' => 'gpt-4.1-mini'],
        'gpt-4.1-nano'  => ['provider' => 'openai', 'model' => 'gpt-4.1-nano'],
        // Groq (free)
        'groq-llama-3.3' => ['provider' => 'groq', 'model' => 'llama-3.3-70b-versatile'],
        'groq-llama-3.1' => ['provider' => 'groq', 'model' => 'llama-3.1-8b-instant'],
    ];
    return $map[$ideModel] ?? ['provider' => 'anthropic', 'model' => 'claude-sonnet-4-20250514'];
}

// GET = health check
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $ch = curl_init('http://127.0.0.1:3102/health');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
    ]);
    $result = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($result === false) {
        http_response_code(503);
        echo json_encode(['error' => 'Agent harness not responding', 'status' => 'offline']);
        exit;
    }
    echo $result;
    exit;
}

// Only POST allowed for chat
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Read incoming IDE payload
$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!$input || empty($input['message'])) {
    http_response_code(400);
    echo json_encode(['error' => 'message is required']);
    exit;
}

// Map the IDE model name to harness provider/model
$ideModel = $input['model'] ?? 'sonnet';
$mapped = mapIdeModel($ideModel);

// Build harness payload
$harnessPayload = [
    'message'   => $input['message'],
    'provider'  => $mapped['provider'],
    'model'     => $mapped['model'],
    'sessionId' => $input['conv_id'] ?? null,
];

// Forward to harness
$ch = curl_init('http://127.0.0.1:3102/chat');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($harnessPayload),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT        => 300, // Agent loops can take time
    CURLOPT_CONNECTTIMEOUT => 5,
]);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch);
curl_close($ch);

if ($result === false) {
    http_response_code(503);
    echo json_encode(['error' => 'Agent harness connection failed: ' . $curlErr, 'response' => 'Agent harness is not responding. Check PM2: pm2 status alfred-agent']);
    exit;
}

// Parse harness response
$harnessData = json_decode($result, true);
if (!$harnessData) {
    http_response_code(502);
    echo json_encode(['error' => 'Invalid response from agent harness', 'response' => 'Agent returned invalid data']);
    exit;
}

// Transform to IDE-compatible response format
$ideResponse = [
    'response'    => $harnessData['response'] ?? 'No response',
    'agent'       => $input['agent'] ?? 'alfred',
    'conv_id'     => $harnessData['sessionId'] ?? '',
    'model'       => $harnessData['model'] ?? $ideModel,
    'turns'       => $harnessData['turns'] ?? 0,
    'tokensUsed'  => $harnessData['tokensUsed'] ?? 0,
    'toolEvents'  => $harnessData['toolEvents'] ?? [],
    'identity'    => [
        'name'      => 'Danny',
        'client_id' => 33,
        'plan'      => 'commander',
        'unlimited' => true,
    ],
];

http_response_code($httpCode);
echo json_encode($ideResponse);
