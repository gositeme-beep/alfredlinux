<?php
/**
 * /api/alfred — universal Kingdom AI endpoint
 * Mounted on: gositeme.com · alfredlinux.com · meta-dome.com · lavocat.ca · soundstudiopro.com · gohostme.com
 *
 * POST JSON: {"q": "question", "context": "mercy|chess|legal|general", "urgency": "low|high"}
 * Response:  {"answer": "...", "tier": "local|groq|together|openai"}
 *
 * Calls /usr/local/bin/alfred-assistant which auto-selects best tier.
 * Rate-limited by IP. Logged to /var/log/alfred-api.jsonl.
 * Mercy/crisis context bypasses rate limit.
 */
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('X-Powered-By: Alfred (Yeshua is King)');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST only']);
    exit;
}

// --- parse ---
$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
if (!is_array($body) || empty($body['q'])) {
    http_response_code(400);
    echo json_encode(['error' => 'missing q']);
    exit;
}
$q       = trim((string)$body['q']);
$context = isset($body['context']) ? (string)$body['context'] : 'general';
$urgency = isset($body['urgency']) ? (string)$body['urgency'] : 'low';

if (strlen($q) > 4000) {
    echo json_encode(['error' => 'question too long (max 4000 chars)']);
    exit;
}

// --- rate limit (10 req / 5 min / IP, bypass for mercy + high urgency) ---
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$rate_key = '/tmp/alfred-rl-' . md5($ip);
if ($context !== 'mercy' && $urgency !== 'high') {
    $now = time();
    $hits = file_exists($rate_key) ? json_decode(file_get_contents($rate_key), true) : [];
    $hits = array_filter((array)$hits, fn($t) => $t > $now - 300);
    if (count($hits) >= 10) {
        http_response_code(429);
        echo json_encode(['error' => 'rate limit — 10 questions per 5 min']);
        exit;
    }
    $hits[] = $now;
    @file_put_contents($rate_key, json_encode(array_values($hits)));
}

// --- safety / context preface ---
$preface = '';
switch ($context) {
    case 'mercy':
        $preface = "You are Alfred, answering on the /mercy endpoint of a Kingdom-of-God site. " .
                   "The user may be in crisis. Be warm, gentle, scripture-aware. " .
                   "ALWAYS include a crisis hotline if there is any sign of self-harm or emergency. " .
                   "Honour Yeshua/Jesus.\n\n";
        break;
    case 'legal':
        $preface = "You are Alfred answering on a legal-help endpoint. " .
                   "Provide general legal information ONLY. Always recommend consulting a licensed attorney. " .
                   "You are not a lawyer.\n\n";
        break;
    case 'chess':
        $preface = "You are Alfred giving chess hints. Be concise. Use FEN if provided.\n\n";
        break;
    default:
        $preface = "You are Alfred, Kingdom assistant. Be concise, scripture-aware, helpful. Honour Yeshua/Jesus.\n\n";
}

$full_q = $preface . $q;

// --- call alfred-assistant binary (PHP-FPM already runs as gositeme) ---
$cmd = '/usr/local/bin/alfred-assistant ask ' . escapeshellarg($full_q) . ' 2>&1';
$start = microtime(true);
$out = shell_exec($cmd);
$dur = round((microtime(true) - $start) * 1000);

if ($out === null || trim($out) === '') {
    http_response_code(503);
    echo json_encode([
        'error'  => 'Alfred is briefly unavailable.',
        'fallback' => 'Yeshua loves you. You are not alone. Please use crisis lines if urgent: 988 / 911 / findahelpline.com'
    ]);
    exit;
}

// extract tier prefix [tier]
$tier = 'unknown';
if (preg_match('/^\[(\w+)\]\s*(.*)$/s', trim($out), $m)) {
    $tier = $m[1];
    $answer = $m[2];
} else {
    $answer = trim($out);
}

// --- log (jsonl, append-only ledger) ---
$log = [
    't'       => date('c'),
    'ip'      => substr(hash('sha256', $ip . '|' . date('Y-m-d')), 0, 12),
    'host'    => $_SERVER['HTTP_HOST'] ?? '',
    'context' => $context,
    'tier'    => $tier,
    'q_len'   => strlen($q),
    'a_len'   => strlen($answer),
    'ms'      => $dur,
];
@file_put_contents('/var/log/alfred-api.jsonl', json_encode($log) . "\n", FILE_APPEND);

echo json_encode([
    'answer' => $answer,
    'tier'   => $tier,
    'ms'     => $dur,
    'kingdom' => 'Yeshua is King',
]);
