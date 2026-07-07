<?php
// Proxy to Next.js server that supports client-side features
$requestUri = $_SERVER['REQUEST_URI'];
$url = 'http://127.0.0.1:3000' . $requestUri;

// Initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
curl_setopt($ch, CURLOPT_HEADER, true); // Get headers too

// Forward headers
$headers = [];
foreach (getallheaders() as $name => $value) {
    if (strtolower($name) !== 'host') {
        $headers[] = "$name: $value";
    }
}
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Forward request method and body
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents('php://input'));
}

// Get response with headers
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Parse headers and body
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headerText = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

// Set response code
http_response_code($httpCode);

// Forward original headers from Next.js
$headers = explode("\n", $headerText);
foreach ($headers as $header) {
    $header = trim($header);
    if (strpos($header, ':') !== false && !empty($header)) {
        header($header);
    }
}

// Output body
echo $body;

curl_close($ch);
?> 