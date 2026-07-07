<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

$logFile = __DIR__ . '/debug.log';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $msg = $data['msg'] ?? '';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $line = date('Y-m-d H:i:s') . " | $ip | $msg\n";
    file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    echo json_encode(['ok' => true]);
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (file_exists($logFile)) {
        header('Content-Type: text/plain');
        echo file_get_contents($logFile);
    } else {
        echo "No logs yet.\n";
    }
}
