<?php

declare(strict_types=1);

$key = @trim((string) @file_get_contents('/home/gositeme/.vault/control-api-key'));
if ($key === '') {
    fwrite(STDERR, "Missing control API key\n");
    exit(1);
}

$url = 'https://gositeme.com/control/billing/index.php';
$payload = [
    'action' => 'generate_renewals',
    'limit' => 200,
];

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_POST => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HTTPHEADER => [
        'X-Control-Key: ' . $key,
        'Content-Type: application/json',
        'Accept: application/json',
    ],
    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES),
]);

$out = curl_exec($ch);
$err = curl_error($ch);
$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($err !== '') {
    fwrite(STDERR, "curl error: $err\n");
    exit(1);
}

echo "HTTP:$code\n";
echo (string) $out . "\n";

if ($code < 200 || $code >= 300) {
    exit(1);
}
