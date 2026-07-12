<?php
// Static file handler for Next.js static assets
header('Content-Type: text/plain; charset=UTF-8');

// Get the file path from query parameter
$file_path = $_GET['file'] ?? '';

if (empty($file_path)) {
    echo "No file specified";
    exit;
}

// Security: Only allow static files
if (strpos($file_path, '/_next/static/') !== 0) {
    echo "Invalid file path";
    exit;
}

// Build the full path
$static_path = __DIR__ . '/.next/static' . $file_path;

if (!file_exists($static_path)) {
    echo "File not found: $static_path";
    exit;
}

// Get file extension
$extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

// Set appropriate content type
switch ($extension) {
    case 'js':
        header('Content-Type: application/javascript; charset=UTF-8');
        break;
    case 'css':
        header('Content-Type: text/css; charset=UTF-8');
        break;
    case 'png':
        header('Content-Type: image/png');
        break;
    case 'jpg':
    case 'jpeg':
        header('Content-Type: image/jpeg');
        break;
    case 'gif':
        header('Content-Type: image/gif');
        break;
    case 'svg':
        header('Content-Type: image/svg+xml');
        break;
    case 'woff':
        header('Content-Type: font/woff');
        break;
    case 'woff2':
        header('Content-Type: font/woff2');
        break;
    case 'ttf':
        header('Content-Type: font/ttf');
        break;
    case 'eot':
        header('Content-Type: application/vnd.ms-fontobject');
        break;
    default:
        header('Content-Type: application/octet-stream');
}

// Set cache headers
header('Cache-Control: public, max-age=31536000');
header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + 31536000));

// Serve the file
readfile($static_path);
?> 