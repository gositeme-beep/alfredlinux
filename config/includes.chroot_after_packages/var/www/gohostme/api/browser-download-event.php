<?php
declare(strict_types=1);

header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit;
}

$raw = file_get_contents('php://input') ?: '';
$payload = json_decode($raw, true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$label = trim((string)($payload['label'] ?? ''));
$href = trim((string)($payload['href'] ?? ''));
if ($label === '' && $href === '') {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'missing_payload']);
    exit;
}

$record = [
    'ts' => gmdate('c'),
    'action' => substr(trim((string)($payload['action'] ?? 'click')), 0, 32),
    'label' => substr($label, 0, 120),
    'platform' => substr(trim((string)($payload['platform'] ?? '')), 0, 32),
    'channel' => substr(trim((string)($payload['channel'] ?? '')), 0, 32),
    'version' => substr(trim((string)($payload['version'] ?? '')), 0, 32),
    'size' => substr(trim((string)($payload['size'] ?? '')), 0, 32),
    'sha256' => substr(preg_replace('/[^a-f0-9]/i', '', (string)($payload['sha256'] ?? '')), 0, 64),
    'href' => substr($href, 0, 255),
    'page' => substr(trim((string)($payload['page'] ?? '')), 0, 120),
    'context' => substr(trim((string)($payload['context'] ?? '')), 0, 120),
    'referrer' => substr(trim((string)($payload['referrer'] ?? '')), 0, 120),
    'ip_hash' => substr(hash('sha256', (string)($_SERVER['REMOTE_ADDR'] ?? '') . '|' . (string)($_SERVER['HTTP_USER_AGENT'] ?? '')), 0, 16),
    'ua_hash' => substr(hash('sha256', (string)($_SERVER['HTTP_USER_AGENT'] ?? '')), 0, 16),
];

$logFile = dirname(__DIR__) . '/logs/browser-download-events.log';
if (!is_dir(dirname($logFile))) {
    mkdir(dirname($logFile), 0755, true);
}

error_log(json_encode($record, JSON_UNESCAPED_SLASHES) . PHP_EOL, 3, $logFile);

http_response_code(204);
exit;