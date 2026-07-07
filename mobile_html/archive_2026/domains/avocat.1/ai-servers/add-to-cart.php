<?php
/**
 * Store AI server build in session and redirect to cart.
 * POST body: JSON { "build": { "gpu": "id", "cpu": "id", ... } }
 * Requires AI_SERVERS_PRODUCT_ID to be set in config.
 */
session_start();
require_once __DIR__ . '/../includes/lang.php';

$pid = defined('AI_SERVERS_PRODUCT_ID') && AI_SERVERS_PRODUCT_ID > 0 ? (int) AI_SERVERS_PRODUCT_ID : 0;
if ($pid <= 0) {
    header('Location: /ai-servers/');
    exit;
}

if (!empty($_POST['build'])) {
    $build = is_string($_POST['build']) ? json_decode($_POST['build'], true) : $_POST['build'];
    $total = isset($_POST['total']) ? (float) $_POST['total'] : null;
    $currency = isset($_POST['currency']) ? trim((string) $_POST['currency']) : '';
} else {
    $raw = file_get_contents('php://input');
    $input = $raw ? json_decode($raw, true) : [];
    $build = isset($input['build']) ? $input['build'] : (is_array($input) ? $input : []);
    $total = isset($input['total']) ? (float) $input['total'] : null;
    $currency = isset($input['currency']) ? trim((string) ($input['currency'] ?? '')) : '';
}
$build = is_array($build) ? $build : [];

$required = ['gpu', 'cpu', 'motherboard', 'ram', 'storage', 'psu', 'case'];
$ok = true;
foreach ($required as $k) {
    if (empty($build[$k])) { $ok = false; break; }
}

if ($ok) {
    $_SESSION['ai_server_build'] = $build;
    $_SESSION['ai_server_build_time'] = time();
    if ($total !== null && $total >= 0) {
        $_SESSION['ai_server_build_total'] = $total;
        $_SESSION['ai_server_build_currency'] = $currency ?: 'CAD';
    }
}

$cartPath = '/cart?a=add&pid=' . $pid;
if (function_exists('billing_link')) {
    $cartPath = billing_link('cart.php?a=add&pid=' . $pid);
}
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
header('Location: ' . $scheme . '://' . $host . '/' . $cartPath);
exit;
