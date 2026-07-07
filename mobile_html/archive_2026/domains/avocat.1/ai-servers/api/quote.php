<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$build = isset($input['build']) ? $input['build'] : [];
$contact = isset($input['contact']) ? $input['contact'] : [];

// Validate minimal build
$required = ['gpu', 'cpu', 'motherboard', 'ram', 'storage', 'psu', 'case'];
foreach ($required as $key) {
    if (empty($build[$key])) {
        echo json_encode(['success' => false, 'error' => 'Missing component: ' . $key]);
        exit;
    }
}

// In production: send email, store in DB, or forward to billing API
$to = defined('AI_SERVERS_QUOTE_EMAIL') && AI_SERVERS_QUOTE_EMAIL ? AI_SERVERS_QUOTE_EMAIL : null;
if ($to) {
    $subject = 'AI Server Quote Request - ' . ($contact['email'] ?? 'no email');
    $body = "Build:\n" . json_encode($build, JSON_PRETTY_PRINT) . "\n\nContact:\n" . json_encode($contact, JSON_PRETTY_PRINT);
    @mail($to, $subject, $body);
}

echo json_encode([
    'success' => true,
    'message' => 'Quote request received. We will contact you shortly.',
    'buildId' => 'Q' . date('YmdHis')
]);
