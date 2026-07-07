<?php
/**
 * GoHostMe Panel Gateway — gositeme.com/gohostme/
 * 
 * Shows a marketing landing page at /gohostme/ root.
 * Proxies all /gohostme/api/* and panel requests to the backend on 127.0.0.1:2224.
 */

$requestUri = $_SERVER['REQUEST_URI'];
$internalPath = preg_replace('#^/gohostme/?#', '/', $requestUri);
$internalPath = $internalPath ?: '/';

// Root path → show landing page
$cleanPath = strtok($internalPath, '?');
if ($cleanPath === '/' || $cleanPath === '') {
    // Check if it's an API-key bearing request (panel proxy)
    if (!empty($_SERVER['HTTP_X_API_KEY']) || !empty($_SERVER['HTTP_AUTHORIZATION'])) {
        // Proxy to backend
        proxyToBackend($internalPath);
        exit;
    }
    // Show landing page
    include __DIR__ . '/landing.php';
    exit;
}

// Dashboard → show management panel
if ($cleanPath === '/dashboard' || $cleanPath === '/dashboard/') {
    include __DIR__ . '/dashboard.php';
    exit;
}

// Products catalog → show all 121 products
if ($cleanPath === '/products' || $cleanPath === '/products/') {
    include __DIR__ . '/products.php';
    exit;
}

// Everything else → proxy
proxyToBackend($internalPath);

function proxyToBackend($internalPath) {
    $backendUrl = 'http://127.0.0.1:2224' . $internalPath;
    $ch = curl_init($backendUrl);
    $method = $_SERVER['REQUEST_METHOD'];
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

    if (in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
        $body = file_get_contents('php://input');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }

    $forwardHeaders = [];
    $headerNames = ['Content-Type', 'X-API-Key', 'Authorization', 'Accept'];
    foreach ($headerNames as $name) {
        $key = 'HTTP_' . str_replace('-', '_', strtoupper($name));
        if (isset($_SERVER[$key])) {
            $forwardHeaders[] = "$name: " . $_SERVER[$key];
        }
    }
    if (isset($_SERVER['CONTENT_TYPE'])) {
        $forwardHeaders[] = "Content-Type: " . $_SERVER['CONTENT_TYPE'];
    }
    $forwardHeaders[] = 'X-Forwarded-For: ' . ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
    $forwardHeaders[] = 'X-Forwarded-Proto: https';
    $forwardHeaders[] = 'X-Real-IP: ' . ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
    curl_setopt($ch, CURLOPT_HTTPHEADER, $forwardHeaders);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

    if ($response === false) {
        http_response_code(502);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'GoHostMe backend unavailable']);
        exit;
    }
    curl_close($ch);

    $responseHeaders = substr($response, 0, $headerSize);
    $responseBody = substr($response, $headerSize);

    http_response_code($httpCode);
    foreach (explode("\r\n", $responseHeaders) as $line) {
        if (empty($line)) continue;
        if (stripos($line, 'HTTP/') === 0) continue;
        if (stripos($line, 'Transfer-Encoding:') === 0) continue;
        if (stripos($line, 'Connection:') === 0) continue;
        header($line);
    }
    echo $responseBody;
}
