<?php
/**
 * /api/vapi-web-call.php — Mints a VAPI WebSocket call for the browser.
 *
 * Uses the server-side VAPI_API_KEY (private) to POST /call with
 * transport: vapi.websocket, returning the wss:// URL the browser
 * connects to. The private key NEVER leaves the server.
 *
 * The same VAPI assistant Alfred uses on the phone is used here →
 * identical voice (OpenAI onyx), identical brain (Claude Sonnet 4),
 * billed to the same VAPI account (no OpenAI bill on us).
 *
 * Request:  POST  (no body required)
 * Response: { ok: true, callId: "...", websocketCallUrl: "wss://..." }
 */

header('Content-Type: application/json');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'POST required']);
    exit;
}

// Trust check: only allow same-origin from gositeme.com pages
$origin  = (string)($_SERVER['HTTP_ORIGIN']  ?? '');
$referer = (string)($_SERVER['HTTP_REFERER'] ?? '');
$trusted = ($origin === 'https://gositeme.com')
    || (strpos($referer, 'https://gositeme.com/') === 0);
if (!$trusted) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Forbidden origin']);
    exit;
}

require_once __DIR__ . '/../../.env.php';

$vapiKey      = getenv('VAPI_API_KEY');
$assistantId  = getenv('VAPI_ASSISTANT_ID');
if (!$vapiKey || !$assistantId) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'VAPI not configured']);
    exit;
}

$payload = json_encode([
    'assistantId' => $assistantId,
    'transport'   => [
        'provider'         => 'vapi.websocket',
        'audioFormat'      => [
            'format'      => 'pcm_s16le',
            'sampleRate'  => 16000,
            'container'   => 'raw',
        ],
    ],
]);

$ch = curl_init('https://api.vapi.ai/call');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . $vapiKey,
        'Content-Type: application/json',
    ],
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_CONNECTTIMEOUT => 5,
]);
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err  = curl_error($ch);
curl_close($ch);

if ($resp === false || $code >= 400) {
    http_response_code(502);
    echo json_encode([
        'ok'       => false,
        'error'    => 'VAPI call mint failed',
        'http'     => $code,
        'curl_err' => $err,
        'body'     => json_decode($resp, true) ?: $resp,
    ]);
    exit;
}

$data = json_decode($resp, true);
$ws   = $data['transport']['websocketCallUrl'] ?? null;
$id   = $data['id'] ?? null;
if (!$ws || !$id) {
    http_response_code(502);
    echo json_encode(['ok' => false, 'error' => 'No websocketCallUrl in response', 'body' => $data]);
    exit;
}

echo json_encode([
    'ok'               => true,
    'callId'           => $id,
    'websocketCallUrl' => $ws,
    'sampleRate'       => 16000,
    'format'           => 'pcm_s16le',
]);
