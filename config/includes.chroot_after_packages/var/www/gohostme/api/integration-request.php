<?php
/**
 * Integration Request API
 * Saves integration requests from the roadmap page.
 */

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$platform = trim($input['platform'] ?? '');
$email    = trim($input['email'] ?? '');
$useCase  = trim($input['use_case'] ?? '');

if (!$platform || !$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Valid platform name and email are required']);
require_once dirname(__DIR__) . '/includes/api-security.php';
    exit;
}

// Sanitize
$platform = substr(htmlspecialchars($platform, ENT_QUOTES, 'UTF-8'), 0, 200);
$useCase  = substr(htmlspecialchars($useCase, ENT_QUOTES, 'UTF-8'), 0, 2000);

// Rate limit by IP — max 5 requests per hour
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateLimitFile = dirname(__DIR__) . '/cache/int-req-' . md5($ip) . '.json';
$now = time();

$fp = fopen($rateLimitFile, 'c+');
if ($fp && flock($fp, LOCK_EX)) {
    $rateData = json_decode(stream_get_contents($fp), true) ?: [];
    $rateData = array_filter($rateData, fn($t) => ($now - $t) < 3600);
    if (count($rateData) >= 5) {
        flock($fp, LOCK_UN);
        fclose($fp);
        http_response_code(429);
        echo json_encode(['success' => false, 'error' => 'Too many requests. Please try again later.']);
        exit;
    }
    $rateData[] = $now;
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode(array_values($rateData)));
    flock($fp, LOCK_UN);
    fclose($fp);
} else {
    if ($fp) fclose($fp);
}

// Save to log file
$logDir = dirname(__DIR__) . '/logs';
if (!is_dir($logDir)) mkdir($logDir, 0750, true);

$entry = [
    'timestamp' => date('c'),
    'platform'  => $platform,
    'email'     => $email,
    'use_case'  => $useCase,
    'ip'        => $ip,
];

file_put_contents(
    $logDir . '/integration-requests.log',
    json_encode($entry) . "\n",
    FILE_APPEND | LOCK_EX
);

echo json_encode(['success' => true]);
