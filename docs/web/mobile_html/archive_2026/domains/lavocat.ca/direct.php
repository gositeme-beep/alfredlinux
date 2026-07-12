<?php
// Direct serve without any proxy complications
$url = 'http://localhost:3000' . ($_SERVER['REQUEST_URI'] ?? '/');

// Use file_get_contents for direct access
$context = stream_context_create([
    'http' => [
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
        'timeout' => 10,
        'ignore_errors' => true
    ]
]);

$response = file_get_contents($url, false, $context);

if ($response === false) {
    // Fallback page
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Avocat.Quebec - Service Temporairement Indisponible</title>
        <style>
            body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
            .logo { font-size: 2.5em; margin-bottom: 20px; }
            .message { margin: 20px 0; }
            .link { color: #667eea; text-decoration: none; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class="logo">Avocat.Quebec</div>
        <div class="message">Service temporairement indisponible</div>
        <div class="message">
            <a href="mailto:support@lavocat.ca" class="link">Contactez le support</a>
        </div>
    </body>
    </html>';
} else {
    // Output the response directly without any headers
    echo $response;
}
?> 